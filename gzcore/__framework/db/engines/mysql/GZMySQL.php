<?php

namespace GZCore\__framework\db\engines;

trait GZMySQLTrait {
    protected function formatTableColumnName(string $columnTableName): string {
        return "`" . str_replace('.', "`.`", $columnTableName) . "`";
    }
}

abstract class GZMySQL extends GZEngineMaster {
    use GZMySQLTrait;
}