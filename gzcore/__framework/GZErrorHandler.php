<?php

namespace GZCore\__framework;

class GZErrorHandler{
    private static $instance;
    
    private $errorList;
    
    public static function printWarning($message){
        error_log("WARNING--".$message);//We can include user, token or anything to have more information
    }
    
    public static function printError($message){
        error_log("ERROR--".$message);//We can include user, token or anything to have more information
    }
    
    public static function printInfo($message){
        error_log("INFO--".$message);//We can include user, token or anything to have more information
    }
}
?>
