<?php

namespace GZCore\__framework\db\engines;
use GZCore\__framework\GZUtils;
use GZCore\__framework\db\GZOrmMaster;

class GZWhere extends GZWhereMaster {
    use GZMySQLTrait;
    public function __toString(): string {
        $field1 = ($this->field1 instanceof GZOrmMaster ? "({$this->field1})" : $this->formatTableColumnName($this->field1));
        $message = $this->fullCondition ?
        "{$field1} {$this->condition} " . ($this->field2 instanceof GZOrmMaster ? "({$this->field2})" : str_replace("'", "''", $this->field2)) :
        "{$field1} {$this->condition} :" . GZUtils::removeSpecialChars($this->paramLabel);
        return $message;
    }
}