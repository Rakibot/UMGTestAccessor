<?php

namespace GZCore\__framework\db;

class GZParam
{
    private $paramName;
    private $value;

    public function __construct(string $paramName, $value)
    {
        $this->paramName = $paramName;
        $this->value = $value;
    }

    public function &__get($name): string
    {
        return $this->{$name};
    }
}