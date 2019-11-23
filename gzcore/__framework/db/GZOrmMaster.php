<?php

namespace GZCore\__framework\db;
use \GZCore\__framework\GZUtils;
use \Exception;

class GZOrmMaster
{
    protected $operation;
    protected $tableName;
    protected $fields;
    protected $joins;
    protected $wheres;
    protected $tablesJoined;
    protected $values;
    protected $primaryKey;
    protected $skip;
    protected $size;
    protected $groupBy;
    protected $orderBy;
    protected $orderByDesc;
    protected $alias;

    protected function __construct(string $operation, string $tableName)
    {
        $this->operation = $operation;
        $this->tableName = $tableName;
        $this->fields = [];
        $this->joins = [];
        $this->wheres = [];
        $this->tablesJoined = [];
        $this->values = [];
        $this->skip = 0;
        $this->size = 100;
        $this->groupBy = [];
        $this->orderBy = [];
        $this->orderByDesc = false;
        $this->tablesJoined[$tableName] = empty($this->tableName) ? [] : $this->getColumns($this->tableName);
    }

    public function setAlias(string $alias): GZOrmMaster {
        $this->alias = $alias;
        return $this;
    }

    public function getAlias(): string {
        return $this->alias ?? $this->tableName;
    }

    public function addFields(...$fields): GZOrmMaster
    {
        if ($this->operation != 'SELECT') {
            throw new Exception('Agregar campos solo aplica para select', 1000);
        }

        foreach ($fields as $field) {
            if ($field instanceof GZOrmMaster) {
                foreach ($field->tablesJoined as $key => $value) {
                    $this->tablesJoined["{$this->tableName}.$key"] = $value;
                }
            }
        }
        $this->fields = array_merge($this->fields, $fields);
        return $this;
    }

    public function addValues(array $values): GZOrmMaster
    {
        if ($this->operation == 'DELETE FROM' || $this->operation == 'SELECT') {
            throw new Exception('Agregar valores no aplica para delete ni select', 1000);
        }
        if ($this->operation == 'UPDATE') {
            foreach ($values as $key => $value) {
                if ($key != $this->primaryKey) {
                    $this->values[$key] = $value;
                }
            }
        } else {
            $this->values = array_merge($this->values, $values);
        }

        return $this;
    }

    public function addJoin(GZJoin $join): GZOrmMaster
    {
        if ($this->operation != 'SELECT') {
            throw new Exception('Los join solo pueden ser aplicados a los select', 1000);
        }

        $this->tablesJoined[$join->table] = $this->getColumns($join->table);
        $this->joins[] = $join;
        return $this;
    }

    public function addWhere(GZWhere $where): GZOrmMaster
    {
        if ($this->operation == 'INSERT INTO') {
            throw new Exception('Las condiciones no aplican para insert', 1000);
        }

        if ($where->field1 instanceof GZOrmMaster) {
            foreach ($where->field1->tablesJoined as $key => $value) {
                $this->tablesJoined["{$this->tableName}.$key"] = $value;
            }
        }
        if ($where->field2 instanceof GZOrmMaster) {
            foreach ($where->field2->tablesJoined as $key => $value) {
                $this->tablesJoined["{$this->tableName}.$key"] = $value;
            }
        }
        $this->wheres[] = $where;
        return $this;
    }

    public function setSize(int $size): GZOrmMaster
    {
        if ($size > -1) {
            $this->size = $size;
        }
        return $this;
    }

    public function setSkip(int $skip): GZOrmMaster
    {
        if ($skip > 0) {
            $this->skip = $skip;
        }
        return $this;
    }

    public function setGroupBy(string ...$fields): GZOrmMaster
    {
        if (!empty($fields)) {
            $this->groupBy = $fields;
        }
        return $this;
    }

    public function setOrderBy(string ...$fields): GZOrmMaster
    {
        if (!empty($fields)) {
            $this->orderBy = $fields;
        }
        return $this;
    }

    public function setOrderByDesc(string ...$fields): GZOrmMaster
    {
        $this->orderByDesc = true;
        return $this->setOrderBy(...$fields);
    }

    public function addQuery(string $query, string ...$fields): GZOrmMaster
    {
        if ($this->operation != 'SELECT') {
            throw new Exception('La busqueda solo aplica para select', 1000);
        }
        $wheres = [];
        foreach ($fields as $field) {
            $wheres[] = new GZWhere($field, 'LIKE', "%$query%");
        }
        $this->wheres[] = '(' . implode(' OR ', $wheres) . ')';
    }

    public function __get($name) {
        return $this->{$name};
    }

    protected function splitColumnName($columnName): array
    {
        if ($columnName instanceof GZOrmMaster) {
            return [
                'table' => null,
                'alias' => $columnName->getAlias(),
                'column' => $columnName
            ];
        }

        $fieldSplited = preg_split("/\s+as\s+/i", $columnName);
        $fieldRealName = $fieldSplited[0];
        if ($this->isFunction($fieldRealName)) {
            return [
                'table' => null,
                'alias' => $fieldSplited[1] ?? '',
                'column' => $fieldRealName
            ];
        }

        $columnSplited = explode('.', $fieldRealName);
        if (count($columnSplited) > 1) {
            $column = array_pop($columnSplited);
            return [
                'table' => implode('.', $columnSplited),
                'alias' => $fieldSplited[1] ?? '',
                'column' => $column
            ];
        } else {
            return [
                'table' => '',
                'alias' => empty($fieldSplited[1]) ? '' : $fieldSplited[1],
                'column' => $fieldRealName
            ];
        }
    }

    protected function verifyColumn(array $columnSplited): bool {
        $existsJoins = !empty($this->joins);
        if ($columnSplited['table'] === null) {
            return true;
        } else if ($columnSplited['table'] === '') {
            if ($existsJoins) {
                throw new Exception("Se debe de agregar el nombre de la tabla en cada campo al existir al menos 1 join para la columna {$columnSplited['table']}", 1000);
            }

            $columns = $this->tablesJoined[$this->tableName];
            if (!empty($columns) && !in_array($columnSplited['column'], $columns)) {
                return false;
            }
        } else if (array_key_exists($columnSplited['table'], $this->tablesJoined)) {
            $columns = $this->tablesJoined[$columnSplited['table']];
            if (!empty($columns) && !in_array($columnSplited['column'], $columns)) {
                return false;
            }
        } else if (array_key_exists("{$this->tableName}.{$columnSplited['table']}", $this->tablesJoined)) {
            $columns = $this->tablesJoined["{$this->tableName}.{$columnSplited['table']}"];
            if (!empty($columns) && !in_array($columnSplited['column'], $columns)) {
                return false;
            }
        } else {
            return false;
        }
        return true;
    }

    protected function isFunction(string $columnName): bool {
        return preg_match("/^\\w+\\(.*\\)$/", $columnName) === 1;
    }

    private function getColumns(string $tableName): array {
        $columns = [];
        try {
            $tableMap = GZUtils::getTableMap($tableName);
            if (empty($tableMap)) {
                return $columns;
            }
            foreach ($tableMap['columns'] as $column) {
                $columns[] = $column['name'];
            }
            $this->primaryKey = $tableMap['primaryKey'];
        } catch(Exception $e) {

        } finally {
            return $columns;
        }
    }
}