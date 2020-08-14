<?php 

include $_SERVER["DOCUMENT_ROOT"]."/xs-framework/v5/config.php";
use function xuserver\v5\debug;
use function xuserver\v5\notify;
use function xuserver\v5\Build;


function buildInstance($input){
    $a= explode("-", $input);
    $db_tablename = $a[0];
    $db_id = $a[1];
    $obj = Build($db_tablename);
    $obj->read($db_id);
    $find = implode(",#", array_keys($_POST));
    $obj->properties()->find("#$find")->select()->sort($_POST);
    return $obj;
}

if(count($_POST)>0){
    if(isset($_POST["method"])){
        $method = $_POST["method"];
        
        
        
        if($method=="create"){
            $obj = buildInstance($_POST["model_build"]);
            if($obj->state()=="is_selection"){
                $obj->db_id("-1");
                echo $obj->ui->form();
            }else{
                $obj->val($_POST);
                $new = $obj->create();
                $blank = Build($new->db_tablename())->read();
                echo notify("created");
                echo $blank->ui->table().$new->ui->form();
            }
            
            
        }else if($method=="read"){
            $a= explode("-", $_POST["model_build"]);
            $obj = Build($a[0]);
            unset($_POST[$obj->db_index()]);
            $obj->val($_POST);
            $obj=setSearchform($obj);
            $form = $obj->ui->form();
            
            $obj->properties()->find()->like()->where();
            $obj->read();
            $table = $obj->ui->table();
            echo debug($obj->sql()->statement_current());
            echo $form.$table;
            
        }else if($method=="update"){
            $obj = buildInstance($_POST["model_build"]);
            $obj->val($_POST);
            $obj->update();
            echo notify("updated");
            echo $obj->ui->form();
            
        }else if($method=="delete"){
            $obj = buildInstance($_POST["model_build"]);
            if($obj->state()=="is_selection"){
                if(isset($_POST["ids"])){
                    $empty = $obj->db_id($_POST["ids"])->delete();
                    echo $empty->ui->table();
                }else{
                    
                }
                
            }else{
                //$empty = $obj->delete();
                echo $empty->ui->table();
            }
            
            
            
        }else if($method=="form"){
            $a= explode("-", $_POST["model_build"]);
            $obj = Build($a[0]);
            $obj->read($a[1]);
                        
            if($obj->state()=="is_instance"){
            }else{
                $obj=setSearchform($obj);
            }
            
            echo $obj->ui->form();
            
            
        }else{
            $a= explode("-", $_POST["model_build"]);
            $obj = Build($a[0]);
            $obj->read($a[1]);
            //$obj = buildInstance($_POST["model_build"]);
            //echo "<h1>$method</h1>";            
            echo $obj->$method();
        }
        
        
        //
        
    }
}


function setSearchform($obj){
  $obj->properties()->find("#text, #textarea","type")->val(null);
  $obj->properties()->find("#checkbox","type")->type("radio");
  
  $obj->properties()->find()->select();
  $obj->methods()->select(0)->find("read")->select(1);
  
  return $obj;
}


?>
