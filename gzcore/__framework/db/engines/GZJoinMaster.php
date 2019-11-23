<?php

namespace GZCore\__framework\db\engines;

class GZJoinMaster {
    protected $type;
    protected $table;
    protected $field1;
    protected $field2;
    protected $fieldWhere;
    protected $alias;

    public function setAlias(string $alias): GZJoinMaster {
        $this->alias = $alias;
        return $this;
    }
}