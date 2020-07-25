<?php 

include $_SERVER["DOCUMENT_ROOT"]."/xs-framework/v5/config.php";
use function xuserver\v5\debug;
use function xuserver\v5\Build;
use xuserver;

function buildInstance($input){
    $a= explode("-", $input);
    $db_tablename = $a[0];
    $db_id = $a[1];
    
    $obj = Build($db_tablename);
    $obj->read($db_id);
    echo debug("object $db_id");
    
    $find = implode(",#", array_keys($_POST));
        
    $obj->properties()->find("#$find")->select()->sort($_POST);
    return $obj;
}

if(count($_POST)>0){
    if(isset($_POST["method"])){
        $method = $_POST["method"];
        $obj = buildInstance($_POST["model_build"]);
        
        
        if($method=="update"){
            echo "<div>".$obj->db_id()."</div>";
            $obj->val($_POST);
            $obj->update();
            echo $obj->ui->form();
            
        }else if($method=="read"){
            
            $obj->val(null);
            $obj->val($_POST);
            $obj->properties()->find()->select()->where();
            echo $obj->ui->table();
        }
        echo $obj->ui->structure();
        
        //echo debug("object updated");
        
    }
}

?>
