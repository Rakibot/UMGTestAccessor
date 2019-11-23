<?php

namespace GZCore\__framework\db;

class GZOrmJson extends GZOrmMaster
{
    protected function __construct(string $operation, string $tableName)
    {
        parent::__construct($operation, $tableName);
    }

    public static function select(string $tableName): GZOrmJson
    {
        return new GZOrmJson('SELECT', $tableName);
    }

    public function __toString(): string
    {
        if (empty($this->fields)) {
            throw new Exception("Para la generacón del JSON, es necesario especificar los 'fields'", 1000);
        }

        $fields = [];
        $fieldLength = count($this->fields);
        for ($i = 0; $i < $fieldLength; $i += 2) {
            if ($i + 1 >= $fieldLength) {
                break;
            }
            $sedondField = $this->fields[$i + 1];
            if ($sedondField instanceof GZOrmMaster) { //Add a new subquery
                $secondFieldValue = "({$sedondField})";
            } else { //Add fields
                if ($i + 2 >= $fieldLength) { //I'm in the last iteration
                    $secondFieldValue = "COALESCE(CONCAT('\"', REPLACE({$sedondField}, '\"', '\\\\\"'), '\"'), 'null')";
                } else {
                    $secondFieldValue = "COALESCE(CONCAT('\"', REPLACE({$sedondField}, '\"', '\\\\\"'), '\"', ','), 'null,')";
                }
            }
            $fields[] = "'\"{$this->fields[$i]}\":'";
            $fields[] = $secondFieldValue;

        }
        $query = "{$this->operation}
            CONCAT(
                '[',
                GROUP_CONCAT('{', " . implode(', ', $fields) . ", '}'),
                ']'
            ) FROM {$this->tableName}";
        if (empty($this->wheres)) {
            return $query;
        }

        foreach ($this->wheres as $key => $where) {
            $columnSplited = $this->splitColumnName($where->field1);
            if (!$this->verifyColumn($columnSplited)) {
                unset($this->wheres[$key]);
            }
        }
        $query .= " WHERE " . implode(' AND ', $this->wheres);
        return $query;
    }
}
