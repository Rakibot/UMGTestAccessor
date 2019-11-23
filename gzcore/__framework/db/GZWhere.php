<?php

namespace GZCore\__framework\db;

class GZWhere extends engines\GZWhere
{
    // $field1 y field 2 solo pueden ser instancias de string o GZOrmMaster
    public function __construct($field1, string $condition, $field2, string $paramLabel = '', $validateFields = true)
    {
        if (!($field1 instanceof GZOrmMaster)) {
            $this->validateFieldName((string)$field1);
        }
        if (!($field2 instanceof GZOrmMaster)) {
            $this->validateFieldName((string)$field2);
        }

        $this->field1 = $field1;
        $this->condition = $condition;
        $this->field2 = $field2;
        $this->paramLabel = empty($paramLabel) ? $field1 : $paramLabel;
    }

    private function validateFieldName($fieldName)
    {
        $dotsInName = substr_count($fieldName, '.');
        if ($dotsInName != 0 && $dotsInName != 1 && $dotsInName != 2) {
            throw new Exception('El nombre del campo del where debe constituir solo de su nombre o de su tabla contenedora y su nombre', 1000);
        }
    }

    public function &__get($name)
    {
        return $this->{$name};
    }

    public function enableFullCondition(): GZWhere
    {
        $this->fullCondition = true;
        return $this;
    }
}