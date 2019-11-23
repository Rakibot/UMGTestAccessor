<?php

namespace GZCore\__framework;
use \Exception;

class GZHelper {

    public static function loadFiles() {
        $helperPath = GZUtils::pathJoin(GZUtils::getCurrentPath(), '..', '__helper');
        foreach (scandir($helperPath) as $helperFile) {
            $filePath = $helperPath . DIRECTORY_SEPARATOR . $helperFile;
            $fileParts = pathinfo($filePath);
            if (is_file($filePath) && strtolower($fileParts['extension']) === 'php') {
                include($filePath);
            }
        }
        return;
    }

    public static function run(string $className, string $method, array $payload) {
        $className = "GZCore\__helper\\$className";
        if (class_exists($className)) {
            $instance = new $className;
            if ($instance instanceof \GZCore\__helper\GZHelper) {
                if (method_exists($instance, $method)) {
                    $requireAuth = $instance->requireAuth($method);
                    if (($requireAuth && GZSession::existsSession()) || !$requireAuth) {
                        return $instance->{$method}($payload);
                        //return call_user_func('$instance->' . $method, $payload);
                    } else {
                        throw new Exception(AUTH_ERROR,  401);
                    }
                } else {
                    throw new Exception("El metodo $method no existe para \\GZCore\\__helper\\$className");
                }
            } else {
                throw new Exception("La clase $className debe heredar de \\GZCore\\__helper\\GZHelper");
            }
        } else {
            throw new Exception("La clase $className no se encuentra dentro de la carpeta __helper o esta no implementa el namespace GZCore\\__helper", 1000);
        }
    }
}

namespace GZCore\__helper;
use \Exception;

class GZHelper {

    private $avoidAuth = [];

    public function __construct() {

    }

    public function requireAuth(string $methodName): bool {
        return !in_array($methodName, $this->avoidAuth);
    }

    protected function addMethodWithoutAuth(string $methodName) {
        if (method_exists($this, $methodName)) {
            $this->avoidAuth[] = $methodName;
        } else {
            throw new Exception("El metodo $methodName no existe dentro de " . get_class($this), 404);
        }
    }

    protected function onlyFor(string $httpVerb) {
        if (strtoupper($httpVerb) != $_SERVER['REQUEST_METHOD']) {
            throw new Exception("La petición solo puede ser servida a travez de un metodo $httpVerb", 403);
        }
    }
}