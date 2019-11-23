<?php

namespace GZCore\__framework\db\engines;
use \GZCore\__framework\GZUtils;
use \GZCore\__framework\GZConfig;
use \GZCore\__framework\db\GZORM;
use \GZCore\__framework\db\GZJoin;
use \GZCore\__framework\db\GZWhere;
use \GZCore\__framework\db\GZOrmMaster;
//use \GZCore\__framework\db\engines\GZEngineMaster;

class GZEngine extends GZMySQL {

    public static function getConnectionString(): string {
        $hostName = GZConfig::$config->database->host;
        $dbName = GZConfig::$config->database->name;
        $dbPort = GZConfig::$config->database->port;
        return "mysql:host=$hostName;dbname=$dbName;port=$dbPort;charset=utf8";
    }

    public static function getAllTables(): array {
        return GZORM::select('INFORMATION_SCHEMA.TABLES')
            ->addFields('INFORMATION_SCHEMA.TABLES.TABLE_NAME AS table_name')
            ->addWhere(new GZWhere('INFORMATION_SCHEMA.TABLES.TABLE_SCHEMA', '=', GZConfig::$config->database->name))
            ->doQuery();
    }

    public static function getTableSchema(string $tableName): array {
        $whereColumnName = new GZWhere('COLUMNS.COLUMN_NAME', '=', 'KEY_COLUMN_USAGE.COLUMN_NAME');
        $whereTableName = new GZWhere('COLUMNS.TABLE_NAME', '=', 'KEY_COLUMN_USAGE.TABLE_NAME');
        $whereSchemaName = new GZWhere('COLUMNS.TABLE_SCHEMA', '=', 'KEY_COLUMN_USAGE.TABLE_SCHEMA');
        $whereColumnName2 = new GZWhere('COLUMNS.COLUMN_NAME', '=', 'REF_BY.REFERENCED_COLUMN_NAME');
        $whereTableName2 = new GZWhere('COLUMNS.TABLE_NAME', '=', 'REF_BY.REFERENCED_TABLE_NAME');
        $whereSchemaName2 = new GZWhere('COLUMNS.TABLE_SCHEMA', '=', 'REF_BY.TABLE_SCHEMA');

        return GZORM::select('INFORMATION_SCHEMA.COLUMNS')
            ->addFields(
                'INFORMATION_SCHEMA.COLUMNS.COLUMN_NAME AS name',
                'INFORMATION_SCHEMA.COLUMNS.DATA_TYPE AS type',
                'INFORMATION_SCHEMA.COLUMNS.COLUMN_KEY AS key',
                "CONCAT('{\"table\":\"', INFORMATION_SCHEMA.KEY_COLUMN_USAGE.REFERENCED_TABLE_NAME, '\",\"column\":\"', INFORMATION_SCHEMA.KEY_COLUMN_USAGE.REFERENCED_COLUMN_NAME, '\"}') AS reference_to",
                "CONCAT('[', GROUP_CONCAT(CONCAT('{\"table\":\"', REF_BY.table_name, '\",\"column\":\"', REF_BY.column_name, '\"}')), ']') AS referenced_by"
            )->addJoin(GZJoin::left(
                'INFORMATION_SCHEMA.KEY_COLUMN_USAGE', [
                    $whereColumnName->enableFullCondition(),
                    $whereTableName->enableFullCondition(),
                    $whereSchemaName->enableFullCondition()
                ])
            )->addJoin(GZJoin::left(
                'INFORMATION_SCHEMA.KEY_COLUMN_USAGE', [
                    $whereColumnName2->enableFullCondition(),
                    $whereTableName2->enableFullCondition(),
                    $whereSchemaName2->enableFullCondition()
                ])->setAlias('REF_BY')
            )->addWhere(new GZWhere('INFORMATION_SCHEMA.COLUMNS.TABLE_SCHEMA', '=', GZConfig::$config->database->name))
            ->addWhere(new GZWhere('INFORMATION_SCHEMA.COLUMNS.TABLE_NAME', '=', $tableName))
            ->setGroupBy('INFORMATION_SCHEMA.COLUMNS.COLUMN_NAME')
            ->setOrderByDesc('COLUMN_KEY')
            ->doQuery();
    }

    public function groupConcat(string $column): GZEngineMaster {
        $fieldSplited = $this->splitColumnName($column);
        $fullName = empty($fieldSplited['table']) ? $fieldSplited['column'] : "{$fieldSplited['table']}.{$fieldSplited['column']}";
        if (!empty($fieldSplited['alias'])) {
            $this->addFields("GROUP_CONCAT($fullName AS {$fieldSplited['alias']}");
        } else {
            $this->addFields("GROUP_CONCAT($fullName)");
        }
        return $this;
    }

    public function __toString(): string {
        if (!empty($this->query)) {
            return $this->query;
        }
        $query = $this->operation . ' ';
        if ($this->operation == 'SELECT') {
            $fields = [];
            foreach ($this->fields as $field) {
                if ($field instanceof GZOrmMaster) {
                    $fields[] = "({$field}) AS '{$field->getAlias()}'";
                } else {
                    $fieldSplited = $this->splitColumnName($field);
                    $fieldRealName = empty($fieldSplited['table']) ? $fieldSplited['column'] : "{$fieldSplited['table']}.{$fieldSplited['column']}";
                    $fieldAlias = empty($fieldSplited['alias']) ? '' : " AS '{$fieldSplited['alias']}'";
                    if ($this->isFunction($fieldRealName)) {
                        $fields[] = $fieldRealName . $fieldAlias;
                    } else {
                        $fields[] = $this->formatTableColumnName($fieldRealName) . $fieldAlias;
                    }
                }
            }
            $query .= (empty($fields) ? '*' : implode(', ', $fields)) . ' FROM ';
        } 

        $query .= $this->formatTableColumnName($this->tableName) . ' ';
        switch ($this->operation) {
            case 'INSERT INTO':
                $keysWitoutSpecialChars = [];
                $fieldsformatd = [];
                foreach (array_keys($this->values) as $key) {
                    $keysWitoutSpecialChars[] = ':' . GZUtils::removeSpecialChars($key);
                    $fieldsformatd[] = $this->formatTableColumnName($key);
                }
                $query .= '(' . implode(', ', $fieldsformatd) . ') ';
                $query .= 'VALUES (' . implode(', ', $keysWitoutSpecialChars) . ')';
                return $query;
            case 'UPDATE':
                $values = [];
                foreach ($this->values as $key => $value) {
                    $values[] = $this->formatTableColumnName($key) . ' = :' . GZUtils::removeSpecialChars($key);
                }
                $query .= 'SET ' . implode(', ', $values) . ' ';
                break;
        }

        if ($this->operation == 'SELECT' && !empty($this->joins)) {
            $query .= implode(' ', $this->joins) . ' ';
        }
        if (!empty($this->wheres)) {
            $query .= 'WHERE ' . implode(' AND ', $this->wheres) . ' ';
        }

        if ($this->operation == 'SELECT') {
            if (!empty($this->groupBy)) {
                $query .= 'GROUP BY ' . implode(', ', $this->formatTablesName($this->groupBy)) . ' ';
            }
            if (!empty($this->orderBy)) {
                $query .= 'ORDER BY ' . implode(', ', $this->formatTablesName($this->orderBy)) . ' ';
            }

            if ($this->size > 0) {
                $query .= "LIMIT {$this->size} ";
                if ($this->skip > 0) {
                    $query .= 'OFFSET ' . (($this->skip - 1) * $this->size);
                }
            }
        }
        return $query;
    }

    private function formatTablesName(array $columnNames): array {
        $columnNamesCopy = [];
        foreach ($columnNames as $singleColumnName) {
            $columnNamesCopy[] = $this->formatTableColumnName($singleColumnName);
        }
        return $columnNamesCopy;
    }
}