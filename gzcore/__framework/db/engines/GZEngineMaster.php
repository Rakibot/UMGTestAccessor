<?php

namespace GZCore\__framework\db\engines;
use \GZCore\__framework\db\GZOrmMaster;

abstract class GZEngineMaster extends GZOrmMaster {
    protected $params;
    protected $query;

    protected abstract function formatTableColumnName(string $columnTableName): string;
    protected abstract function groupConcat(string $column): GZEngineMaster;
    public abstract function __toString(): string;
}
