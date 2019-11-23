<?php

namespace GZCore\__framework;
use \Exception;

define("AUTH_ERROR", "No se ha iniciado la sesión");
define("AUTHORIZATION_ERROR", "Usted no cuenda con permisos para acceder a esta sección");

class GZRouter{
    
    private static $tableName;
    private static $dataSource = null;
    private static $params;
    private static $helper;
    //TODO change isset by empty
    public static function route(){
        if(GZRouter::$dataSource == null){
            GZRouter::$dataSource = new GZDataSource();
        }
        GZRouter::$params = [];
        foreach (explode('&', $_SERVER['QUERY_STRING']) as $part) {
            $partSplited = explode('=', $part);
            if (count($partSplited) > 1) {
                GZRouter::$params[$partSplited[0]] = $partSplited[1];
            } else {
                GZRouter::$params[$partSplited[0]] = null;
            }
        }
        //$this->tableName = substr(filter_input(INPUT_SERVER, 'PATH_INFO', FILTER_SANITIZE_STRING),1);//Removes prepended slash -- NOT WORKING IN PROD
        if (!array_key_exists('PATH_INFO', $_SERVER)) {
            throw new Exception("No se ha seleccionado ninguna tabla desde la url", 1000);
        }
        $command = substr($_SERVER["PATH_INFO"], 1);//substr to remove prepended slash
        
        GZRouter::$tableName = $command;
        GZErrorHandler::printInfo("The model/helper request is ".$command);
        $helperName = $command;
        $method = $_SERVER['REQUEST_METHOD'];
        GZGuard::verifyAccess($helperName);
        $methodAux = '__' . strtolower($method) . '_aux';

        switch($method){
            case "GET":
                return GZRouter::handleGet();
            case "POST":
                return GZRouter::handlePost();
            case "PUT":
                return GZRouter::handlePut();
            case "DELETE":
                return GZRouter::handleDelete();
            default:
                throw new Exception("Método de solicitud inválido", "1000", null);
        }
    }
    
    private static function handleGet(){
        $helperName = GZRouter::$tableName;
        $res = null;
        if (GZRouter::tryRunHelper($helperName, GZRouter::$params, $res)) {
            return $res;
        } else if (GZGuard::userWasConfigured() && !GZSession::existsSession()) {
            throw new Exception(AUTH_ERROR, 401);
        } else if (!GZGuard::rolHavePermission('Read', $helperName)) {
            throw new Exception(AUTHORIZATION_ERROR, 401);
        }
        //Se eliminan estos valors debido a que solo pueden ser agregados por el guard
        unset(GZRouter::$params['__joins']);
        GZErrorHandler::printInfo("The params of get request are ".json_encode(GZRouter::$params));
        GZGuard::addFiltersToGet(GZRouter::$params, GZRouter::$tableName);
        $arrayRes = GZRouter::$dataSource->query(GZRouter::$params, GZRouter::$tableName);
        if(count($arrayRes) == 0){
            throw new Exception("No se encontraron registros", "1001", null);
        }
        return $arrayRes;
    }
    
    private static function handlePost(){
        //replace by QUERY_STRING??
        $helperName = GZRouter::$tableName;
        $res = null;
        $tmpPayload = json_decode(file_get_contents('php://input'), true) ?? [];
        if (GZRouter::tryRunHelper($helperName, $tmpPayload, $res)) {
            return $res;
        } else if (GZGuard::userWasConfigured() && !GZSession::existsSession()) {
            throw new Exception(AUTH_ERROR, 401);
        } else if (!GZGuard::rolHavePermission('Create', $helperName)) {
            throw new Exception(AUTHORIZATION_ERROR, 401);
        }
        $payload;
        if(isset($tmpPayload[GZRouter::$tableName]) && is_array($tmpPayload[GZRouter::$tableName])){
            $payload = $tmpPayload[GZRouter::$tableName];
        }else{
            $payload = $tmpPayload;
        }
        if(!empty($payload["helper_name"])){
            $helperName = $payload["helper_name"];
            unset($payload["helper_name"]);
        }
        unset($payload['__guard_filter']);
        GZErrorHandler::printInfo("The payload of post is ".json_encode($payload));
        GZGuard::addSecurityFilters($payload, GZRouter::$tableName);
        if (!GZGuard::canInsertAccordingFilters($payload)) {
            throw new Exception("No se puede insertar los datos ingresados", 1000);
        }
        $res = GZRouter::$dataSource->post($payload, GZRouter::$tableName);
        if(!$res){
            throw new Exception("Hubo un error al tratar de crear el registro","1000", null);
        }
        return $res;
    }
    
    private static function handlePut(){
        $tmpPayload = json_decode(file_get_contents('php://input'), true) ?? [];
        $res = null;
        if (GZRouter::tryRunHelper(GZRouter::$tableName, $tmpPayload, $res)) {
            return $res;
        } else if (GZGuard::userWasConfigured() && !GZSession::existsSession()) {
            throw new Exception(AUTH_ERROR, 401);
        } else if (!GZGuard::rolHavePermission('Update', GZRouter::$tableName)) {
            throw new Exception(AUTHORIZATION_ERROR, 401);
        }
        $payload;
        if(isset($tmpPayload[GZRouter::$tableName]) && is_array($tmpPayload[GZRouter::$tableName])){
            $payload = $tmpPayload[GZRouter::$tableName];
        }else{
            $payload = $tmpPayload;
        }
        unset($payload['__guard_filter']);
        GZErrorHandler::printInfo("The payload of put is ".json_encode($payload));
        GZGuard::addSecurityFilters($payload, GZRouter::$tableName);
        $res = GZRouter::$dataSource->put($payload, GZRouter::$tableName);//Update model
        if(!$res){
            throw new Exception("Hubo un error al tratar de modificar el registro","1000", null);
        }
        return $res;
    }
    
    private static function handleDelete() {
        $res = null;
        if (GZRouter::tryRunHelper(GZRouter::$tableName, GZRouter::$params, $res)) {
            return $res;
        } else if (GZGuard::userWasConfigured() && !GZSession::existsSession()) {
            throw new Exception(AUTH_ERROR, 401);
        } else if (!GZGuard::rolHavePermission('Delete', GZRouter::$tableName)) {
            throw new Exception(AUTHORIZATION_ERROR, 401);
        }
        unset(GZRouter::$params['__guard_filter']);
        GZErrorHandler::printInfo("The delete params of delete are ".json_encode(GZRouter::$params));
        GZGuard::addSecurityFilters(GZRouter::$params, GZRouter::$tableName);
        $res = GZRouter::$dataSource->delete(GZRouter::$params, GZRouter::$tableName);//Update model
        $res = $res[0];
        if(!$res){
            throw new Exception("Hubo un error al tratar de eliminar el registro","1000", null);
        }
        return $res;
    }

    private static function tryRunHelper(string $command, array $payload, & $res): bool {
        if (preg_match('/^_\w/', $command) === 1) {
            $commandSplitted = explode('/', $command);
            if (count($commandSplitted) != 2) {
                throw new Exception("La dirección proporcionada es erronea", 1000);
            }
            $className = substr($commandSplitted[0], 1);
            $method = $commandSplitted[1];
            $res = GZHelper::run($className, $method, $payload);
            return true;
        }
        return false;
    }
}

?>
