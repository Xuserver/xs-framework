<?php
/**
 * GJ 25/06/2020
 */

/**********************************************************************************************************
 * _config
 **********************************************************************************************************/


    
    define('XUSERVER_DB_SERVER',"");   // Database server internet address
    define('XUSERVER_DB_NAME',"");     // Database name
    define('XUSERVER_DB_USERNAME',"");   // Database username
    define('XUSERVER_DB_PASSWORD',"");  // Database password


spl_autoload_register(function($class){
    $class=str_replace('xuserver\\', '', $class);
    require $_SERVER["DOCUMENT_ROOT"]."/xs-framework/".str_replace('\\', '/', $class).".php";
});
    
require $_SERVER["DOCUMENT_ROOT"]."/xs-framework/v5/session.php";
require $_SERVER["DOCUMENT_ROOT"]."/xs-framework/v5/model.php";

// system DBO object
try{
    $XUSERVER_DB_PDO = new PDO("mysql:host=".XUSERVER_DB_SERVER.";dbname=".XUSERVER_DB_NAME.";charset=utf8", XUSERVER_DB_USERNAME, XUSERVER_DB_PASSWORD);
    //$dbprod->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $XUSERVER_DB =new xuserver\v5\database($XUSERVER_DB_PDO);
    
}catch(PDOException  $e ){
    echo ($e->getCode());
    echo ($e->getMessage());
    
    throw $e;
}
    
?>
