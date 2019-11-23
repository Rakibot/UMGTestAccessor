<?php

namespace GZCore\__framework\db;

class GZJoin extends engines\GZJoin {

    /** Manda llamar un metodo estatico; los metodos estaticos aceptados son:
      * inner,left o right.
      * Se intenta realizar una sobrecarga. Los parametros aceptados son:
      * (string $table, GZWhere ...$fieldsWhere)
      * (string $table, string $field1, string $field2)
      */
    public static function __callStatic(string $name, array $arguments): GZJoin
    {
        $argsLenght = count($arguments);
        $type = '';
        switch ($name) {
            case 'inner':
                $type = 'INNER JOIN';
                break;
            case 'left':
                $type = 'LEFT JOIN';
                break;
            case 'right':
                $type = 'RIGHT JOIN';
                break;
            default:
                throw new Exception('Los metodos estaticos soportados son: inner, left y right', 1000);
        }

        if ($argsLenght == 2) {
            if (!is_string($arguments[0]) || !is_array($arguments[1]))
                throw new Exception('Cuando son 2 argumentos, el primero debe ser un string y el segundo un arreglo de GZWhere', 1000);
            foreach ($arguments[1] as $where) {
                if (!($where instanceof GZWhere)) {
                    throw new Exception('No todos los elementos dentro del array son instancias de GZWhere', 1000);
                }
            }
            return new GZJoin($type, $arguments[0], '', '', ...$arguments[1]);
        } else if ($argsLenght == 3) {
            if (!is_string($arguments[0]) || !is_string($arguments[1]) || !is_string($arguments[2]))
                throw new Exception('Cuando son 3 argumentos, los 3 deben ser string', 1000);
            return new GZJoin($type, $arguments[0], $arguments[1], $arguments[2]);
        } else {
            throw new Exception('Solo deben ser enviados 2 o 3 elementos', 1000);
        }
    }

    private function __construct(string $type, string $table, string $field1 = '', string $field2 = '', GZWhere ...$fieldsWhere) {
        $this->type = $type;
        $this->table = $table;
        $this->field1 = $field1;
        $this->field2 = $field2;
        $this->fieldsWhere = $fieldsWhere;
    }

    public function &__get(string $name): string {
        return $this->{$name};
    }
}
