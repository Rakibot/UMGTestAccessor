<?php

namespace GZCore\__framework\db\engines;

class GZJoin extends GZJoinMaster {
    use GZMySQLTrait;
    public function __toString(): string {
        return "{$this->type} " . $this->formatTableColumnName($this->table) .
        (
            empty($this->alias) ? '' : " AS {$this->alias} "
        ) . " ON " .
        (
            empty($this->fieldsWhere) ?
            $this->formatTableColumnName($this->field1) . " = " . $this->formatTableColumnName($this->field2)
            : implode(' AND ', $this->fieldsWhere)
        );
    }
}