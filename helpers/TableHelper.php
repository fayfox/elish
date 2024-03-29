<?php

namespace elish\helpers;

use elish\db\Db;

class TableHelper
{
    /**
     * 获取表信息
     * @param string $tableName 表名
     * @param string|array $dbConfig 数据库配置代码
     * @param string|null $logicDeleteField 逻辑删除字段名
     * @return array
     */
    public static function getTableInfo(string $tableName, $dbConfig, ?string $logicDeleteField = ''): array
    {
        if (is_string($dbConfig)) {
            $dbConfig = Db::getConfigs($dbConfig);
        }

        $db = Db::getInstance($dbConfig);
        $fields = $db->fetchAll('SHOW FULL FIELDS FROM ' . addslashes($tableName));

        // 精简类名（移除表前缀）
        $simpleTableName = $tableName;
        if ($dbConfig['tablePrefix'] && StringHelper::startWith($tableName, $dbConfig['tablePrefix'])) {
            // 去除
            $simpleTableName = substr($tableName, strlen($dbConfig['tablePrefix']));
        }
        $ddl = $db->fetchRow('SHOW CREATE TABLE ' . addslashes($tableName));
        preg_match('/COMMENT=\'([\s\S]*)\'/', $ddl['Create Table'], $tableComment);
        if (!empty($tableComment[1])) {
            $tableComment = str_replace(["\r\n", "\n", '\r\n', '\n'], ' - ', $tableComment[1]);
            if (mb_substr($tableComment, -1) == '表') {
                $tableComment = mb_substr($tableComment, 0, -1);
            }
        } else {
            $tableComment = ucwords(str_replace('_', ' ', $simpleTableName));
        }

        $info = [
            'databaseId' => $dbConfig['id'] ?? 0,
            'fields' => $fields,
            'tableName' => $tableName,
            'tableComment' => $tableComment,
            'primaryField' => 'id',
            'isPrimaryAutoIncrement' => false,
            'hasCreateTimeField' => false,
            'hasUpdateTimeField' => false,
            'hasLogicDeleteField' => false,
            // state字段（我司特有的，一般是删除标记，有时候也作为状态位）
            'hasState' => false,
        ];

        foreach ($fields as $field) {

            if ($field['Extra'] == 'auto_increment') {
                $info['isPrimaryAutoIncrement'] = true;
            }
            if ($field['Key'] == 'PRI') {
                $info['primaryField'] = $field['Field'];
            }
            if ($field['Field'] == 'create_time') {
                $info['hasCreateTimeField'] = true;
            }
            if ($field['Field'] == 'update_time') {
                $info['hasUpdateTimeField'] = true;
            }
            if ($field['Field'] == 'state') {
                $info['hasState'] = true;
            }
            if ($logicDeleteField && $field['Field'] == $logicDeleteField) {
                $info['hasLogicDeleteField'] = true;
            }
        }

        return $info;
    }

    /**
     * 基于表结构，解析类信息
     * @param array $tableInfo 表信息(@see TableHelper::getTableInfo)
     * @param string $dbCode 数据库配置代码
     * @param string $projectName 项目名
     * @param string $servicePrefix 服务前缀
     * @return array
     */
    public static function parseTableClassInfo(array $tableInfo, string $dbCode, string $projectName = '', string $servicePrefix = ''): array
    {
        $dbConfig = Db::getConfigs($dbCode);

        // 精简类名（移除表前缀）
        $simpleTableName = $tableInfo['tableName'];
        if ($dbConfig['tablePrefix'] && StringHelper::startWith($tableInfo['tableName'], $dbConfig['tablePrefix'])) {
            // 去除
            $simpleTableName = substr($tableInfo['tableName'], strlen($dbConfig['tablePrefix']));
        }

        // 如果表前缀和项目名一致，去掉前缀
        // 因为包名必须是纯小写，尽量缩短一点，多个单词连在一起丑
        // 比如表名是：sg_redpack_cover, 项目名是redpack，则tablePackageName = 'cover'
        $tablePackageName = $simpleTableName;
        if ($projectName && substr($tablePackageName, 0, strlen($projectName)) == str_replace('-', '_', $projectName)) {
            $tablePackageName = substr($simpleTableName, strlen($projectName) + 1);
        }
        if (!$tablePackageName) {
            // 如果删空了，还是用原来的名字
            $tablePackageName = $simpleTableName;
        }
        $tablePackageName = str_replace('_', '', $tablePackageName);

        // 项目前缀
        $className = StringHelper::underscore2case($simpleTableName);
        $trimTableName = $simpleTableName;
        if ($servicePrefix) {
            $formatServicePrefix = StringHelper::underscore2case(str_replace('-', '_', $servicePrefix));
            if (StringHelper::startWith($className, $formatServicePrefix) && strlen($className) > strlen($formatServicePrefix)) {
                // 前綴一致，且类名更长一些，类名移除项目前缀;
                $className = ucfirst(substr($className, strlen($formatServicePrefix)));
                $trimTableName = trim(substr($trimTableName, strlen($servicePrefix)), '_');
            }
        }

        return [
            // 移除了数据库级别统一配置的表前缀后的表名
            'simpleTableName' => $simpleTableName,
            // 在移除数据库级别前缀后，又移除了项目前缀的表名
            'trimTableName' => $trimTableName,
            'tablePackageName' => $tablePackageName,
            'className' => $className,
            'classParam' => lcfirst($className),
        ];
    }

    /**
     * 获取类型字段
     * @param array $fields `SHOW FULL FIELDS`的结果
     * @param array $skipFields 排除的字段
     * @return array
     */
    public static function getTypeFields(array $fields, array $skipFields = []): array
    {
        $typeFields = [];
        foreach ($fields as $field) {
            if (in_array($field['Field'], $skipFields)) {
                continue;
            }
            if ($field['Type'] == 'timestamp' || $field['Type'] == 'datetime') {
                $typeFields[] = 'datetime';
            }
            if ($field['Type'] == 'date') {
                $typeFields[] = 'date';
            }
            if ($field['Type'] == 'time') {
                $typeFields[] = 'time';
            }
            if (StringHelper::startWith($field['Type'], 'decimal')) {
                $typeFields[] = 'decimal';
            }
        }

        return array_unique($typeFields);
    }

    /**
     * 根据SQL类型指定一个Java类型
     * @param string $sqlType 字段类型
     * @param string $fieldName 字段名
     * @return string
     */
    public static function getJavaType(string $sqlType, string $fieldName): string
    {
        // id固定为Long类型
        if ($fieldName == 'id') {
            return 'Long';
        }

        // 时间
        if ($sqlType == 'timestamp' || $sqlType == 'datetime') {
            return 'LocalDateTime';
        }

        if ($sqlType == 'date') {
            return 'LocalDate';
        }

        if ($sqlType == 'time') {
            return 'LocalTime';
        }

        // tinyint且长度是1，或者字段以is_开头，则认为是Boolean
        if (StringHelper::startWith($sqlType, 'tinyint')) {
            if (StringHelper::startWith($fieldName, 'is_') || StringHelper::startWith($sqlType, 'tinyint(1)')) {
                return 'Boolean';
            }
        }

        if (StringHelper::startWith($sqlType, 'bigint')) {
            return 'Long';
        }

        if (StringHelper::startWith($sqlType, 'decimal')) {
            return 'BigDecimal';
        }

        // 数字
        if (StringHelper::startWith($sqlType, 'int', 'mediumint', 'smallint', 'tinyint')) {
            return 'Integer';
        }

        // 默认都是字符串
        return 'String';
    }

    /**
     * 获取kotlin类型（目前已知就一个Int对应java的Integer，其它都是一样的）
     * @param string $sqlType 字段类型
     * @param string $fieldName 字段名
     * @return string
     */
    public static function getKotlinType(string $sqlType, string $fieldName): string
    {
        $javaType = self::getJavaType($sqlType, $fieldName);
        if ($javaType == 'Integer') {
            return 'Int';
        }

        return $javaType;
    }

    /**
     * 是否是可变属性（用于kotlin）
     * @param string $fullClassName 全类名
     * @return bool
     */
    public static function isMutableProperties(string $fullClassName): bool
    {
        if (StringHelper::endWith($fullClassName, 'DTO', 'VO')) {
            // 输入输出DTO和VO，都是程序填充的，一般不需要手动变更
            return false;
        }

        if (StringHelper::endWith($fullClassName, 'BO')) {
            if (StringHelper::startWith($fullClassName, 'Create', 'Update', 'Search')) {
                // 更新用的BO，可能需要手动构建，默认为可变的
                return true;
            }

            // 其实也就剩下一个详情BO了，一般就是DO直接转的
            return false;
        }

        return true;
    }

    /**
     * 渲染一个kotlin的属性
     * @param $field
     * @param string $fullClassName 全类名
     * @param bool $hasNext
     * @return string
     */
    public static function buildKtField($field, string $fullClassName, bool $hasNext): string
    {
        $fieldCase = StringHelper::underscore2case($field['Field'], false);
        $mutable = self::isMutableProperties($fullClassName);
        return implode('', [
            $mutable ? 'var' : 'val',
            " {$fieldCase}: " . self::getKotlinType($field['Type'], $field['Field']),
            self::isNullable($fullClassName, $field) ? '?' : '',
            $hasNext ? ",\n" : "\n",
        ]);
    }

    /**
     * 字段是否允许为null
     * @param string $fullClassName 全类名
     * @param $field
     * @return bool
     */
    public static function isNullable(string $fullClassName, $field): bool
    {
        $mutable = self::isMutableProperties($fullClassName);
        if (StringHelper::startWith($fullClassName, 'Create', 'Update', 'Search')) {
            // 新增/更新/搜索都是允许传部分字段的，除了主键，都是允许null的
            return $field['Key'] != 'PRI';
        } else if ($field['Null'] == 'YES' || $mutable) {
            // 本身就是允许null的字段
            // 属性是可变的，则属性默认允许null
            return true;
        }
        return  false;
    }

    /**
     * 获取字段对应的java类型
     * @param string $fieldName 字段名称
     * @param array $fields 所有字段
     * @return string|null
     */
    public static function getFieldJavaType(string $fieldName, array $fields): ?string
    {
        foreach ($fields as $field) {
            if ($field['Field'] == $fieldName) {
                return self::getJavaType($field['Type'], $fieldName);
            }
        }

        return null;
    }

    /**
     * 判断指定字段是否存在
     * @param string $fieldName 字段名称
     * @param array $fields 所有字段
     * @return bool
     */
    public static function hasField(string $fieldName, array $fields): bool
    {
        foreach ($fields as $field) {
            if ($field['Field'] == $fieldName) {
                return true;
            }
        }

        return false;
    }

    /**
     * 获取指定字段，若不存在，返回null
     * @param string $fieldName 字段名称
     * @param array $fields 所有字段
     * @return array|null
     */
    public static function getField(string $fieldName, array $fields): ?array
    {
        foreach ($fields as $field) {
            if ($field['Field'] == $fieldName) {
                return $field;
            }
        }

        return null;
    }

    /**
     * @param Db $db 数据库连接实例
     * @param string|null $prefix 表前缀
     * @param bool $filterShardingTable 是否过滤分表
     * @return array
     */
    public static function getTables(Db $db, ?string $prefix = null, bool $filterShardingTable = true): array
    {
        $tables = $db->fetchAll("SHOW TABLES");
        $result = [];
        foreach ($tables as $table) {
            $tableName = array_shift($table);
            if ($prefix && !StringHelper::startWith($tableName, $prefix)) {
                continue;
            }

            if (preg_match('/_\d+$/', $tableName)) {
                // 末尾8位全是数字，认为是分表，不返回
                continue;
            }

            $result[] = $tableName;
        }

        return $result;
    }

    /**
     * 根据sql类型，获取sqlalchemy类型
     * @param string $sqlType
     * @param string $fieldName
     * @return string|void
     */
    public static function getSqlAlchemyType(string $sqlType, string $fieldName)
    {
        // tinyint且长度是1，或者字段以is_开头，则认为是Boolean
        if (StringHelper::startWith($sqlType, 'tinyint')) {
            if (StringHelper::startWith($fieldName, 'is_') || StringHelper::startWith($sqlType, 'tinyint(1)')) {
                return 'Boolean';
            }
        }

        $sqlTypeToSqlAlchemyType = [
            'tinyint' => 'Integer',
            'smallint' => 'SmallInteger',
            'int' => 'INTEGER',
            'bigint' => 'BIGINT',
            'decimal' => 'DECIMAL',
            'varchar' => 'String',
            'datetime' => 'DateTime',
            'timestamp' => 'DateTime',
            'date' => 'Date',
        ];

        $parsedSqlType = self::parseSqlType($sqlType);

        // 数字（暂不支持无符号，因为sqlalchemy通用类型里没有无符号，如果选择dialects.mysql.INTEGER，又失去了平滑切换数据库的特性）
        if (in_array($parsedSqlType['dataType'], ['tinyint', 'smallint', 'int', 'bigint', 'datetime', 'timestamp', 'date'])) {
            if (StringHelper::contains($sqlType, 'unsigned')) {
                return "{$sqlTypeToSqlAlchemyType[$parsedSqlType['dataType']]}(unsigned=True)";
            }
            return $sqlTypeToSqlAlchemyType[$parsedSqlType['dataType']];
        }

        // 字符串，需要带上长度
        if ($parsedSqlType['dataType'] == 'varchar') {
            return "{$sqlTypeToSqlAlchemyType[$parsedSqlType['dataType']]}({$parsedSqlType['numericPrecision']})";
        }

        // 小数
        if ($parsedSqlType['dataType'] == 'decimal') {
            return "{$sqlTypeToSqlAlchemyType[$parsedSqlType['dataType']]}({$parsedSqlType['numericPrecision']}, {$parsedSqlType['numericScale']})";
        }
    }

    /**
     * 解析sql type类型
     * @param $sqlType
     * @return array
     */
    public static function parseSqlType($sqlType): array
    {
        $unsigned = false;
        if (strpos($sqlType, ' unsigned')) {
            $unsigned = true;
        }

        // 包含空格，取第一个空格前的部分
        if (StringHelper::contains($sqlType, ' ')) {
            // 目前见过带空格的，后面只有unsigned, zerofill
            $sqlType = explode(' ', $sqlType)[0];
        }

        $numericPrecision = 0;
        $numericScale = 0;
        if (preg_match('/(\w+)\(([\d,]+)\)/', $sqlType, $matches)) {
            $dataType = $matches[1];
            if (StringHelper::contains($matches[2], ',')) {
                // 没见过超过2个数字的类型
                $numericParts = explode(',', $matches[2]);
                $numericPrecision = $numericParts[0];
                $numericScale = $numericParts[1];
            } else {
                $numericPrecision = $matches[2];
            }
        } else {
            $dataType = $sqlType;
        }

        return [
            'unsigned' => $unsigned,
            'dataType' => $dataType,
            'numericPrecision' => $numericPrecision,
            'numericScale' => $numericScale,
        ];
    }

    /**
     * 根据SQL类型指定一个Python类型
     * @param string $sqlType
     * @param string $fieldName
     * @return string
     */
    public static function getPythonType(string $sqlType, string $fieldName): string
    {
        // id固定为Long类型
        if ($fieldName == 'id') {
            return 'int';
        }

        // 时间
        if ($sqlType == 'timestamp' || $sqlType == 'datetime') {
            return 'datetime';
        }

        if ($sqlType == 'date') {
            return 'date';
        }

        if ($sqlType == 'time') {
            return 'time';
        }

        // tinyint且长度是1，或者字段以is_开头，则认为是Boolean
        if (StringHelper::startWith($sqlType, 'tinyint')) {
            if (StringHelper::startWith($fieldName, 'is_') || StringHelper::startWith($sqlType, 'tinyint(1)')) {
                return 'bool';
            }
        }

        if (StringHelper::startWith($sqlType, 'decimal')) {
            return 'Decimal';
        }

        // 数字
        if (StringHelper::startWith($sqlType, 'bigint', 'int', 'mediumint', 'smallint', 'tinyint')) {
            return 'int';
        }

        // 默认都是字符串
        return 'str';
    }
}
