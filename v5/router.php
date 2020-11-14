<?php 

include $_SERVER["DOCUMENT_ROOT"]."/xs-framework/v5/config.php";
//use xuserver\v5\model;


/************************************************************************************
                                GETTING SESSION 
 ************************************************************************************/
$session = session();


/************************************************************************************ 
                                GET ACTIONS 
************************************************************************************/
if(count($_GET)>0){
    if(isset($_GET["autoComplete"])){
        header("Content-Type: application/json");
        $model = $_GET["autoComplete"];
        $question = $_GET["q"];
        $results=array();
        
        $obj = Build($model);
        $obj->properties()->find("text","type")->then("pk","type")->val($question)->like()->or();
        $obj->read()->iterator()->each(function(xuserver\v5\model $item)use(&$results){
            
            $line=array();
            /*
             $text="";
             $item->properties()->find("#text","type")->each(function($prop)use(&$text){
             $val = $prop->val();
             $text .= " $val |";
             });
             */
            $line["value"]=$item->db_id();
            $line["text"]=$item->title();
            $results[]=$line;
        });
            $json = json_encode($results);
            die ($json);
    }
    if(isset($_GET["file"])){
        $url = xs_decrypt($_GET["file"]);
        $filepath =  $_SERVER['DOCUMENT_ROOT']. '/'.$url;
        $filename= pathinfo($filepath, PATHINFO_FILENAME );
        $fileext = pathinfo($filepath, PATHINFO_EXTENSION);
        
        if(@is_array(getimagesize($filepath))){ // an image file
            $img_file = $filepath;
            $imgData = base64_encode(file_get_contents($img_file));
            $src = 'data: '.mime_content_type($img_file).';base64,'.$imgData;
            echo '<img src="'.$src.'" style="width:100%">';
        } else {
            $rename= hash("sha256",$filename).".".$fileext;
            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="'.$rename.'"');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . filesize($filepath));
            flush(); // Flush system output buffer
            readfile($filepath);
        }
        die();
    }
}



/************************************************************************************ 
                                POST ACTIONS 
************************************************************************************/

if(count($_POST)>0){
    _DECRYPT_POST();
    
    if(isset($_POST["model_method"])){
        $obj = buildInstance($_POST["model_build"]);
        $method = $_POST["model_method"];
                
        // auth preparation
        $strpos = strpos($method, "(");
        if($strpos===false){ // method name only
            $arguments="";
            $methodName = $method;
            $methodEval="$method()";
            //$method = $method;
             
        }else{ // method with parenthesis and arguments
            $args=array();
            if( preg_match( '!\(([^\)]+)\)!', $method, $args ) );
            $arguments = $args[1];
            $methodName = substr($method , 0 , $strpos );
            $methodEval=$method;
            $method= $methodName;
        }
        $KeyTable = $obj->__module()."-".$obj->db_tablename();
        $KeyMethod = "$KeyTable-$methodName";

        $canUse = $session->can($KeyMethod);
        $obj->auth($session);
        
        /*
        if($method=="append"){
            //arguments contains "'db_tablename'"
            $tablename= str_replace("'", "", $arguments);
            $objRel = Build($tablename);
            
            $KeyTable = $objRel->__module()."-".$objRel->db_tablename();
            $KeyMethod = "$KeyTable-create";
            $canUse = $session->can($KeyMethod);
            
            $objRel->auth($session);
            
        }
        */
        
        if($canUse){ // session can use method
            
        }else{
            
            $exceptions = array("log_back", "defined", "defaults", "log_as");
            if($session->logas()){
                if(! in_array($methodName,$exceptions)  ){
                    $message="current profile can't use <b>$KeyMethod</b> on <b>". $obj->db_tablename()."</b>";
                    echo(systray($message,"info"));
                    die(notify("ADMIN : $message","info","logas" ));
                }else{
                    echo (systray("Access granted to <b>$KeyMethod</b>, 'LOG AS'"));
                }
            }else{
                echo (systray("ADMIN BYPASS to <b>$KeyMethod</b>"));
            }
            
            if(!$session->admin()){
                die(notify("Access denied to your current profile","danger"));
            }else{
                
            }
            //die( $beta);
        }
        
        if($method=="create"){
        
            if($obj->state()=="is_selection"){
                if(count($_POST)<=2){
                    echo notify("please fill in form");
                    $obj->db_id("0");
                    $obj->title("new ...");
                    $obj->methods()->select(0)->find("#create")->caption("create")->select(1)->bsclass("btn-outline-primary");
                    echo $obj->formular("create");
                }else{
                    $obj->val($_POST);
                    $new = $obj->create();
                    $blank = Build($new->db_tablename())->read();
                    $obj->db_id("0");
                    $obj->title("new ...");
                    $obj->val(null);
                    $obj->methods()->select(0)->find("#create")->caption("create")->select(1)->bsclass("btn-outline-primary");
                    echo notify("ok, please fill in form");
                    echo $blank->ui->table().$obj->formular("create");
                }
                
            }else{
                $obj->val(null);
                $new = $obj->create();
                $blank = Build($new->db_tablename())->read();
                //$new->title("give a title");
                echo notify("created");
                echo $blank->ui->table().$new->formular();
            }
            
            
        }else if($method=="read"){
            //$a= explode("-", $_POST["model_build"]);
            //$obj = Build($a[0]);
            
            $form="";
            $obj->properties()->find()->select()->like()->where();
            $obj->read();
            $table = $obj->ui->table();
            echo $form.$table;
            
        }else if($method=="update"){
            //$obj = buildInstance($_POST["model_build"]);
            $obj->val($_POST);
            $obj->update();
            
            echo $obj->formular();
            
        }else if($method=="delete"){
            //$obj = buildInstance($_POST["model_build"]);
            
            if($obj->state()=="is_selection"){
                if(isset($_POST["ids"])){
                    $empty = $obj->db_id($_POST["ids"])->delete();
                    echo $obj->ui->killForm();
                    echo $empty->ui->table();
                }else{
                }
            }else{
                $empty = $obj->delete();
                echo $obj->ui->killForm();
                echo $obj->ui->killList();
                echo $obj->ui->killTr();
            }
        }else{
            /*
            $a= explode("-", $_POST["model_build"]);
            $obj = Build($a[0]);
            $obj->read($a[1]);
            if($session->can($KeyMethod)){
                eval("echo \$obj->$methodEval;");
            }else{
                eval("echo \$obj->$methodEval;");
            }
            */
            eval("echo \$obj->$methodEval;");
            
            
        }
    }
}



/************************************************************************************
                        COMMON ROUTER FUNCTIONS
 ************************************************************************************/

function buildInstance($input){
    $a= explode("-", $input);
    $db_tablename = $a[0];
    if(!isset($a[1])){
        $a[1]=0;
    }
    
    $db_id = $a[1];
    $obj = Build($db_tablename);
    $obj->read($db_id);
    $find = implode(",#", array_keys($_POST));
    $obj->properties()->find("#$find")->select()->sort($_POST);
    return $obj;
}

/**
 * @deprecated
 * @param model $obj
 * @return unknown
 
function setSearchform($obj){
  $obj->properties()->find("#text, #textarea","type")->val(null);
  $obj->properties()->find("#checkbox","type")->type("radio");
  
  $obj->properties()->find()->select();
  $obj->methods()->select(0)->find("read")->select(1);
  
  return $obj;
}
*/

?>
