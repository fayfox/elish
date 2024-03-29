<?php

namespace elish\db;

use elish\service\DatabaseService;

class Db
{
    private static $_instance = [];
    private $pdo;
    public $operators = array('AND', 'OR', 'AND NOT', 'OR NOT');

    private function __construct()
    {
    }

    /**
     * @param string|array $config
     * @return $this
     */
    public static function getInstance($config): Db
    {
        if (is_array($config)) {
            $dbCode = $config['code'];
            if (isset(self::$_instance[$dbCode])) {
                return self::$_instance[$dbCode];
            }
            $configs = $config;
        } else {
            // 数据库连接实例已存在，直接返回
            if (isset(self::$_instance[$config])) {
                return self::$_instance[$config];
            }

            $dbCode = $config;
            $configs = self::getConfigs($dbCode);
        }

        self::$_instance[$dbCode] = new self();
        self::$_instance[$dbCode]->init($configs);

        return self::$_instance[$dbCode];
    }

    /**
     * 从服务端获取数据库信息
     * @param $configFile
     * @return array
     */
    public static function getConfigs($configFile): array
    {
        $configInDb = DatabaseService::service()->get($configFile);
        if (!$configInDb) {
            throw new \RuntimeException("db config {$configFile} not found");
        }

        return $configInDb;
    }

    protected function init($configs)
    {
        $dsn = "mysql:host={$configs['host']};port={$configs['port']};dbname={$configs['dbname']};charset={$configs['charset']}";
        try {
            $this->pdo = new \PDO($dsn, $configs['user'], $configs['password'], [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_TIMEOUT => 1,
            ]);
            //当发生错误时，以异常的形式抛出（默认只是返回false）
            $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        } catch (\PDOException $e) {
            throw new \RuntimeException($e->getMessage());
        }
        $this->pdo->exec("SET NAMES {$configs['charset']}");
    }

    public function fetchAll($sql, $params = [])
    {
        try {
            $sth = $this->pdo->prepare($sql);
            $sth->execute($params);
        } catch (\PDOException $e) {
            throw $e;
        }

        return $sth->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function fetchRow($sql, $params = [])
    {
        try {
            $sth = $this->pdo->prepare($sql);
            $sth->execute($params);
        } catch (\PDOException $e) {
            throw $e;
        }

        $result = $sth->fetch(\PDO::FETCH_ASSOC);
        return $result;
    }

    /**
     * 单条插入
     * @param string $table 表名
     * @param array $data 数据
     * @return int
     */
    public function insert($table, $data)
    {
        $fields = [];
        $pres = [];
        $values = [];
        foreach ($data as $k => $v) {
            if ($v === false) continue;
            if ($v instanceof Expr) {
                $fields[] = "`{$k}`";
                $pres[] = $v->get();
            } else {
                $fields[] = "`{$k}`";
                $pres[] = '?';
                $values[] = $v;
            }
        }
        $sql = "INSERT INTO {$table} (" . implode(',', $fields) . ') VALUES (' . implode(',', $pres) . ')';
        return $this->execute($sql, $values);
    }

    /**
     * 更新符合条件的记录
     * @param string $table 表名
     * @param array $data 数据
     * @param array|bool|false|string $condition 条件，若为false，则更新所有字段
     * @return int
     */
    public function update($table, $data, $condition = false)
    {
        if (empty($data)) {
            throw new RuntimeException('更新数据不能为空');
        }
        if (!$condition) {
            throw new RuntimeException('出于安全考虑，不允许where条件为空的update操作');
        }

        $set = [];
        $values = [];
        foreach ($data as $k => $v) {
            if ($v === false) continue;
            if ($v instanceof Expr) {
                $set[] = "`{$k}` = {$v->get()}";
            } else {
                $set[] = "`{$k}` = ?";
                $values[] = $v;
            }
        }

        $where = $this->formatConditions($condition);
        $sql = "UPDATE $table SET " . implode(',', $set) . " WHERE {$where['condition']}";
        return $this->execute($sql, array_merge($values, $where['params']));
    }

    /**
     * 构造一个where语句以及相关参数
     * @param array $where
     * @return array array('condition', 'params')
     */
    public function formatConditions($where)
    {
        if (is_array($where)) {
            $condition = '';
            $params = [];
            foreach ($where as $key => $value) {
                if ($value === false) continue;
                if (in_array(strtoupper(trim($key)), $this->operators)) {
                    $op = ' ' . strtoupper($key) . ' ';
                    $partial = $this->getPartialCondition($op, $value);
                    if ($condition != '') {
                        $condition .= ' AND ';
                    }
                    $condition .= $partial['condition'];
                    $params = array_merge($params, $partial['params']);
                } else {
                    $op = ' AND ';
                    if ($condition != '') {
                        $condition .= $op;
                    }
                    if (is_int($key)) {//'id = 1'
                        $condition .= $value;
                    } else {//'id = ?'=>1
                        if (!$this->_hasOperator($key)) {//'id'=>1
                            //不带操作符的key，默认为等于
                            $key .= ' = ?';
                        }
                        if (is_array($value)) {
                            $params = array_merge($params, $value);
                            if (substr_count($key, '?') == 1 && count($value) > 1) {
                                $key = str_replace('?', '?' . str_repeat(', ?', count($value) - 1), $key);
                            }
                        } else {
                            $params[] = $value;
                        }
                        $condition .= $key;
                    }
                }
            }
            return array(
                'condition' => $condition,
                'params' => $params,
            );
        } else {
            return array(
                'condition' => $where,
                'params' => [],
            );
        }
    }

    private function getPartialCondition($op, $condition_arr)
    {
        $partial_condition = [];
        $params = [];
        foreach ($condition_arr as $key => $value) {
            if (in_array(strtoupper($key), $this->operators)) {
                $partial = $this->getPartialCondition(' ' . strtoupper($key) . ' ', $value);
                $partial_condition[] = $partial['condition'];
                $params = array_merge($params, $partial['params']);
            } else {
                if (is_int($key)) {//'id = 1'
                    $partial_condition[] = $value;
                } else {//'id = ?'=>1
                    if (is_array($value)) {
                        $params = array_merge($params, $value);
                        if (substr_count($key, '?') == 1 && count($value) > 1) {
                            $key = str_replace('?', '?' . str_repeat(', ?', count($value) - 1), $key);
                        }
                    } else {
                        $params[] = $value;
                    }
                    $partial_condition[] = $key;
                }
            }
        }
        $condition = ' ( ' . implode($op, $partial_condition) . ' ) ';
        return array(
            'condition' => $condition,
            'params' => $params
        );
    }

    /**
     * 判断是否有SQL操作符
     * @param string $str
     * @return bool
     */
    protected function _hasOperator($str)
    {
        return (bool)preg_match('/(<|>|!|=|\sIS NULL|\sIS NOT NULL|\sEXISTS|\sBETWEEN|\sLIKE|\sIN\s*\(|\s)/i', trim($str));
    }

    public function execute($sql, $params = [])
    {
        try {
            $sth = $this->pdo->prepare($sql);
            $sth->execute($params);
        } catch (\PDOException $e) {
            throw $e;
        }

        // insert into select的场景，应该返回受影响函数
        if (strtolower(substr(trim($sql), 0, 6)) == 'insert' && stripos($sql, 'select') === false) {
            return $this->pdo->lastInsertId();
        } else {
            return $sth->rowCount();
        }
    }

    private function __clone()
    {
    }
}