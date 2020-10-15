<?php 

include $_SERVER["DOCUMENT_ROOT"]."/xs-framework/v5/config.php";
//use function xuserver\v5\debug;
//use function xuserver\v5\notify;
//use function xuserver\v5\Build;


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

if(count($_GET)>0){
    if(isset($_GET["autoComplete"])){
        header("Content-Type: application/json");
        $model = $_GET["autoComplete"];
        $question = $_GET["q"];
        $results=array();
        
        $obj = Build($model);
        $obj->properties()->find("text","type")->then("pk","type")->val($question)->like()->or();
        $obj->read()->iterator()->each(function(xuserver\v5\model $item)use(&$results){
            $text="";
            $line=array();
            /*
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


if(count($_POST)>0){
     _DECRYPT_POST();
    
    if(isset($_POST["model_method"])){
        $method = $_POST["model_method"];
        
        
        //echo notify($method);
        
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
            
            echo $obj->formular();
            
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
                //$obj=setSearchform($obj);
            }
            
            echo $obj->formular();
            
            
        }else{
            $a= explode("-", $_POST["model_build"]);
            $obj = Build($a[0]);
            $obj->read($a[1]);
            $strpos = strpos($method, "(");
            if($strpos===false){
                echo $obj->$method();
            }else{
                /*
                $args=array();
                if( preg_match( '!\(([^\)]+)\)!', $method, $args ) );
                $arguments = $args[1];
                $methodName = substr($method , 0 , $strpos );
                */
                
                eval("echo \$obj->$method;");
                
            }
            
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
