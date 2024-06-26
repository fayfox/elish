<?php

namespace elish\db;

class Sql
{
    public $operators;
    /**
     * @var Db
     */
    public $db;
    protected $fields = array();
    protected $from = array();
    protected $join = array();
    protected $conditions = array();
    protected $params = array();
    protected $group = array();
    protected $having = array();
    protected $order = array();
    protected $count = null;
    protected $offset = null;
    protected $distinct = false;
    protected $count_by = '*';

    public function __construct($db = null)
    {
        if (!$db) {
            $db = Db::getInstance();
        }
        $this->db = $db;
        $this->operators = $this->db->operators;
    }

    public function select($fields)
    {
        $this->_field($fields);
        return $this;
    }

    /**
     * 构造fields数组
     * @param string $fields 若传入的fields为反选类型，则必须传入表名（用于获取表结构）
     * @param string $table 表的别名
     */
    private function _field($fields, $table = null)
    {
        if (!empty($fields)) {
            if (!is_array($fields)) {
                $fields = array($fields);
            }
            foreach ($fields as $f) {
                $f_arr = explode(',', $f);
                if ($table) {
                    foreach ($f_arr as &$fa) {
                        if (!preg_match('/^\w+\(.*\).*$/', $fa)) {//聚合函数不加前缀
                            $fa = trim($fa);
                            if (strpos($fa, '`') !== 0 && $fa != '*') {//本身没加引号，且非通配符
                                if ($pos = strpos($fa, ' ')) {//存在空格，例如设置了AS
                                    $fa = $table . '.`' . substr($fa, 0, $pos) . '`' . substr($fa, $pos);
                                } else {
                                    $fa = "{$table}.`{$fa}`";
                                }
                            } else {
                                $fa = "{$table}.{$fa}";
                            }
                        }
                    }
                }
                $this->fields = array_merge($this->fields, $f_arr);
            }
        }
    }

    public function distinct($flag = true)
    {
        $this->distinct = $flag;
        return $this;
    }

    public function from($table, $fields = '*')
    {
        if (is_array($table)) {
            list($a, $t) = each($table);
            if (is_string($a)) {
                $alias = $a;
            } else {
                if ($t instanceof Sql) {
                    throw new \RuntimeException('子查询必须设置别名');
                }
                $alias = $t;
            }
            $short_name = $t;
        } else {
            if ($table instanceof Sql) {
                throw new \RuntimeException('子查询必须设置别名');
            }
            $short_name = $table;
            $alias = $table;
        }

        if ($short_name instanceof Sql) {
            $this->from[] = array(
                'table' => '(' . $short_name->getSql() . ')',
                'alias' => $alias,
                'params' => $short_name->getParams(),
            );
        } else {
            $this->from[] = array(
                'table' => $short_name,
                'alias' => $alias,
            );
        }

        $this->_field($fields, $alias);

        return $this;
    }

    /**
     * 得到sql语句
     * 若传入$count参数，则无视前面设置的offset和count，主要用于fetchRow等特殊情况
     * @param null|int $count
     * @return string
     */
    public function getSql($count = null)
    {
        //清空params，以免多次调用本函数造成params重复
        $this->params = array();

        $sql = "SELECT \n";

        //distinct
        if ($this->distinct) {
            $sql .= "DISTINCT ";
        }

        //select
        if ($this->fields) {
            $sql .= implode(",\n", array_unique($this->fields)) . "\n";
        } else {
            $sql .= "* \n";
        }

        //from
        if ($this->from) {
            $sql .= "FROM \n";
            foreach ($this->from as $from) {
                $sql .= "{$from['table']} AS {$from['alias']}";
                if (isset($from['params'])) {
                    $this->params = array_merge($this->params, $from['params']);
                }
            }
            $sql .= "\n";
        }

        //join
        if ($this->join) {
            foreach ($this->join as $j) {
                $sql .= "{$j['type']} {$j['table']} ";
                if (!empty($j['alias'])) {
                    $sql .= "AS {$j['alias']} ";
                }
                $sql .= "ON ({$j['condition']}) \n";
                $this->params = array_merge($this->params, $j['params']);
            }
        }
        //where
        if ($this->conditions) {
            $where = $this->db->formatConditions($this->conditions);
            $sql .= "WHERE \n{$where['condition']} \n";
            $this->params = array_merge($this->params, $where['params']);
        }
        //group
        if ($this->group) {
            $sql .= "GROUP BY \n" . implode(", \n", $this->group) . " \n";
        }
        //having
        if ($this->having) {
            $having = $this->db->formatConditions($this->having);
            $sql .= "HAVING \n{$having['condition']} \n";
            $this->params = array_merge($this->params, $having['params']);
        }

        //order
        if ($this->order) {
            $sql .= "ORDER BY \n" . implode(", \n", $this->order) . " \n";
        }
        //limit
        if ($count !== null) {
            if ($this->offset !== null && $this->offset !== false) {
                $sql .= "LIMIT {$this->offset}, {$count} \n";
            } else {
                $sql .= "LIMIT {$count} \n";
            }
        } else {
            if (!empty($this->count)) {
                if ($this->offset !== null && $this->offset !== false) {
                    $sql .= "LIMIT {$this->offset}, {$this->count} \n";
                } else {
                    $sql .= "LIMIT {$this->count} \n";
                }
            }
        }
        return $sql;
    }

    public function getParams()
    {
        return $this->params;
    }

    public function join($table, $conditions, $fields = null)
    {
        $this->joinInner($table, $conditions, $fields);
        return $this;
    }

    public function joinInner($table, $conditions, $fields = null)
    {
        $this->_join('INNER JOIN', $table, $conditions, $fields);
        return $this;
    }

    private function _join($type, $table, $conditions, $fields)
    {
        if (is_array($table)) {
            list($a, $t) = each($table);
            if (is_string($a)) {
                $alias = $a;
            } else {
                $alias = $this->$t;
            }
            $short_name = $t;
        } else {
            $short_name = $table;
            $alias = $table;
        }

        $full_table_name = $short_name;
        $where = $this->db->formatConditions($conditions);
        $this->join[] = array(
            'type' => $type,
            'table' => $full_table_name,
            'alias' => $alias,
            'condition' => $where['condition'],
            'params' => $where['params'],
        );
        if (!empty($fields)) {
            $this->_field($fields, $alias ? $alias : $full_table_name);
        }
    }

    public function joinLeft($table, $conditions, $fields = null)
    {
        $this->_join('LEFT JOIN', $table, $conditions, $fields);
        return $this;
    }

    public function joinRight($table, $conditions, $fields = null)
    {
        $this->_join('RIGHT JOIN', $table, $conditions, $fields);
        return $this;
    }

    /**
     * 传入$conditions中各项以or的方式连接
     * @param array $conditions
     * @return Sql
     */
    public function orWhere($conditions)
    {
        $this->where(array(
            'or' => $conditions,
        ));
        return $this;
    }

    /**
     * 默认情况下，以and方式连接各条件
     * 也可以指定，具体方法见Db::getWhere
     * @param array|string $where
     * @param null|string $params
     * @return Sql
     */
    public function where($where, $params = null)
    {
        if (is_array($where)) {
            //如果是数组，无视第二个参数
            foreach ($where as $k => $w) {
                if (in_array(strtoupper(trim($k)), $this->operators)) {
                    //若key是关键词，即or，and这些
                    $this->conditions = array_merge($this->conditions, array($this->getConditionKey($k, $this->conditions) => $w));
                } else {
                    $this->conditions = array_merge($this->conditions, array($k => $w));
                }
            }
        } else if ($params !== null) {
            $this->conditions = array_merge($this->conditions, array($where => $params));
        } else {
            $this->conditions[] = $where;
        }
        return $this;
    }

    /**
     * 通过不停加后缀空格的方式，使关键词的键名不重名
     * @param string $key
     * @param array $conditions
     * @return string
     */
    private function getConditionKey($key, $conditions)
    {
        if (isset($conditions[$key])) {
            return $this->getConditionKey($key . ' ', $conditions);
        } else {
            return $key;
        }
    }

    public function group($group)
    {
        if (!is_array($group)) {
            $group = array($group);
        }
        foreach ($group as $g) {
            $this->group[] = $g;
        }
        return $this;
    }

    public function having($having)
    {
        if (is_array($having)) {
            foreach ($having as $k => $w) {
                if (in_array(strtoupper(trim($k)), $this->operators)) {
                    //若key是关键词，即or，and这些
                    $this->having = array_merge($this->having, array($this->getConditionKey($k, $this->having) => $w));
                } else {
                    $this->having = array_merge($this->having, array($k => $w));
                }
            }
        } else {
            $this->having[] = $having;
        }
        return $this;
    }

    public function order($order)
    {
        if (!is_array($order)) {
            $order = array($order);
        }
        foreach ($order as $o) {
            $this->order[] = $o;
        }
        return $this;
    }

    /**
     * @param int $count 数量
     * @param null|int $offset 偏移
     * @return Sql
     */
    public function limit($count, $offset = null)
    {
        $this->count = $count;
        if ($offset !== null && $offset !== false) {
            $this->offset = $offset;
        }
        return $this;
    }

    /**
     * 指定count方法根据哪个字段进行计算<br>
     * 默认为COUNT(*)
     * @param string $by
     * @return Sql
     */
    public function countBy($by)
    {
        $this->count_by = $by;
        return $this;
    }

    /**
     * 将类转换为字符串输出
     */
    public function __toString()
    {
        return $this->getSql();
    }

    public function fetchAll($reset = true, $style = 'assoc')
    {
        $result = $this->db->fetchAll($this->getSql(), $this->getParams(), $style);
        if ($reset) {
            $this->reset();
        }
        return $result;
    }

    /**
     * 重置搜索条件
     */
    public function reset()
    {
        $this->fields = array();
        $this->from = array();
        $this->join = array();
        $this->conditions = array();
        $this->params = array();
        $this->group = array();
        $this->having = array();
        $this->order = array();
        $this->count = null;
        $this->offset = null;
        $this->count_by = '*';
        $this->distinct = false;
    }

    public function fetchRow($reset = true, $style = 'assoc')
    {
        $result = $this->db->fetchRow($this->getSql(1), $this->getParams(), $style);
        if ($reset) {
            $this->reset();
        }
        return $result;
    }

    public function count()
    {
        $result = $this->db->fetchRow($this->getCountSql(), $this->getParams());
        return array_shift($result);
    }

    /**
     * 得到count用的sql语句
     * 若设置了distinct参数，则无视前面设置的distinct参数
     */
    public function getCountSql()
    {
        //清空params，以免多次调用本函数造成params重复
        $this->params = array();

        $sql = "SELECT COUNT({$this->count_by}) \n";

        //from
        if ($this->from) {
            $sql .= "FROM \n";
            foreach ($this->from as $from) {
                $sql .= "{$from['table']} AS {$from['alias']}";
                if (isset($from['params'])) {
                    $this->params = array_merge($this->params, $from['params']);
                }
            }
            $sql .= "\n";
        }

        //join
        if ($this->join) {
            foreach ($this->join as $j) {
                $sql .= "{$j['type']} {$j['table']} ";
                if (!empty($j['alias'])) {
                    $sql .= "AS {$j['alias']} ";
                }
                $sql .= "ON ({$j['condition']}) \n";
                $this->params = array_merge($this->params, $j['params']);
            }
        }
        //where
        if ($this->conditions) {
            $where = $this->db->formatConditions($this->conditions);
            $sql .= "WHERE {$where['condition']} \n";
            $this->params = array_merge($this->params, $where['params']);
        }
        return $sql;
    }
}