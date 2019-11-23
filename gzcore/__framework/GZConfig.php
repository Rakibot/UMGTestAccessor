<?php

namespace GZCore\__framework;

class GZConfig
{
    public static $config;

    public static function load()
    {
        $path = __DIR__ . '/../config.ini';
        if (!file_exists($path)) {
            throw new Exception('Es necesario generar el archivo config.ini', 1000);
        }

        $config = parse_ini_file($path, true);
        if (empty($config['application'])) {
            $config['application'] = [
                'name' => 'default',
                'debug' => 'true'
            ];
        }
        GZConfig::$config = (object)$config;
        foreach ($config as $key => $value) {
            GZConfig::$config->{$key} = (object)$value;
        }
    }
}