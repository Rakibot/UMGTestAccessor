<?php

namespace GZCore\__framework;

define('FRAMEWORK_DIR', './__framework');
define('DB_DIR', FRAMEWORK_DIR . '/db');

use \Exception;
use \GZCore\__framework\db\GZORM;

include_once(DB_DIR . '/GZOrmMaster.php');
loadAllFilesFromDir(FRAMEWORK_DIR);
loadAllFilesFromDir(DB_DIR . '/engines');

if (file_exists('./vendor/autoload.php')) {
    include("./vendor/autoload.php");
}

date_default_timezone_set('America/Mexico_City');
setlocale(LC_ALL, "es_ES");

try{
    GZConfig::load();
    switch (GZConfig::$config->database->engine) {
        case 'mysql':
            include_once(DB_DIR . '/engines/mysql/GZMySQL.php');
            loadAllFilesFromDir(DB_DIR . '/engines/mysql');
            break;
    }
    loadAllFilesFromDir(DB_DIR);
    GZHelper::loadFiles();
    GZUtils::init();
    GZStore::init();
    GZORM::init();
    GZUtils::generateMap();
    if (GZConfig::$config->application->debug) {
        setDebugCode();
    }
    $routeResponse = GZRouter::route();
    if($routeResponse === null){
        $routeResponse = array();
        GZErrorHandler::printWarning("The request returned null but there wasn't any exception, check previous logs to see if there is an internal error");
    }
    printResult("ok", 1, $routeResponse);
    
} catch (Exception $ex) {
    GZErrorHandler::printError($ex->getCode()." -- ".$ex->getMessage()." -- ".$ex->getLine()." -- ".$ex->getFile());
    printResult( $ex->getMessage(), $ex->getCode(),array());
}

function printResult($message,$code,$result) {
    header('Content-Type: application/json');
    header('Access-Control-Expose-Headers: Authorization');
    $finalObject = array();
    $finalObject["status"] = array();
    $finalObject["status"]["info_msg"] = $message;
    $finalObject["status"]["code"] = $code;
    if(is_array($result)){
        $finalObject["response"] = $result;
    }else{
        $finalObject["response"] = [$result];
    }
    /*
    if ($code < 600 && $code >= 100) {
        header("HTTP/1.1 200 $message");
    }*/
    echo json_encode($finalObject);
}

function setDebugCode() {
    $headers = getallheaders();
    ini_set("display_errors", 1);
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: *');
    if (!empty($headers['Access-Control-Request-Headers'])) {
        header('Access-Control-Allow-Headers: ' . $headers['Access-Control-Request-Headers']);
    }
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        die();
    }
}

function loadAllFilesFromDir(string $pathDir) {
    foreach (scandir($pathDir) as $file) {
        if ($file == '.' || $file == '..' || is_dir("$pathDir/$file")) {
            continue;
        }
        include_once("$pathDir/$file");
    }
}

?>