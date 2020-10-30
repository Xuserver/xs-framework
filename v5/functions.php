<?php 

/** GLOBAL CONSTANTS 
 * @see config.php
 **/



/** GLOBAL FUNCTIONS **/

/**
 *
 * @param string $module
 * @param string $tablename
 * @return \xuserver\v5\model
 */
function BuildClass($module,$tablename){
    $Class=null;
    $chain = "\$Class = new \xuserver\modules\\$module\\$tablename"."();";
    eval($chain);
    
    if( is_null($Class)){
        $Class = new \xuserver\v5\model();
    }else{
        
    }
    return $Class ;
}

/**
 * Build a model given a tablename. Uses the business class when php file is found
 * @param string $tablename
 * @return \xuserver\v5\model
 */
function Build($tablename){
    $Class=null;
    $dir = $_SERVER["DOCUMENT_ROOT"]."/xs-framework/modules";
    $elts = scandir($dir, 1);
    foreach ($elts as $module){
        if( $module=="." or $module==".." ){
        }else if( is_dir($dir."/".$module) and strpos($module,".") ===false ){
            if(is_file($dir."/$module/$tablename.php")){
                $Class= BuildClass($module,$tablename);
                $Class->build($tablename);
                return $Class;
            }
        }
    }
    $Class = new \xuserver\v5\model();
    $Class->build($tablename);
    return $Class;
}



/**
 * print a debug message
 * @param string $s
 * @param boolean $echo
 * @return string
 */
function debug($s,$echo=false){
    $alert = "<div id='xs-debug' class='alert alert-light'> <button type='button' class='close' data-dismiss='alert' aria-label='Close'><span aria-hidden='true'>&times;</span></button><h6>debugger</h6> $s </div>";
    if($echo){
        echo $alert;
    }else{
        return $alert;
    }
}
/**
 * Show a notification message
 * @param string $s
 * @param string $class
 * @return string
 */
function notify($s,$class="warning"){
    $id="xs-info-".rand();
    $alert = "<div id='$id' class='xs-notify  alert alert-$class' style='display:none; user-select: none; cursor:pointer;'> <button type='button' class='close' data-dismiss='alert' aria-label='Close'><span aria-hidden='true'>&times;</span></button><h6>notification</h6> $s </div>";
    return $alert;
}


/**
 * decrypt $_POST keys
 * @return Array
 */
function _DECRYPT_POST(){
    $new_POST=array();
    
    // FILES ARE UPLOADED => CREATE $_POST VALUES
    if(count($_FILES)>0){
        _DECRYPT_FILE();
    }
    // DECRYPT _POST KEYS and VALUES
    foreach($_POST as $key => $val){
        if($key=="model_build"){
            $new_POST[$key] = xs_decrypt($val) ;
        }else if($key=="model_method"){
            $new_POST[$key] = $val ;
        }else{
            $new_POST[xs_decrypt($key)] = $val ;
        }
    }
    $_POST = $new_POST;
    return $new_POST;
}
/**
 * create $_POST keys for $_FILES properties
 * @return Array
 */
function _DECRYPT_FILE(){
    foreach($_FILES as $key => $upload){
        $Key=xs_decrypt($key);
        if ($upload["size"]>0 && $upload["name"]!="") {
            $_POST[$key]=$Key.".".$upload["name"];
        }else {
            $_POST[$key]=null;
        }
    }
}




/**
 * encrypt db column names
 * @param string $input
 * @return string
 */
function xs_encrypt($input){
    /*
    $session = session();
    if($session->admin()){
        return $input;
    }
    */
    return openssl_encrypt($input, "AES-128-ECB" ,XUSERVER_ENCRYPT);
}
/**
 * decrypt db column names
 * @param string $input
 * @return string
 */
function xs_decrypt($input){
    /*
    $session = session();
    if($session->admin()){
        return $input;
    }
    */
    return openssl_decrypt($input, "AES-128-ECB" ,XUSERVER_ENCRYPT);
}


function xs_link($uid,$href,$method,$caption,$bsclass="",$title=""){
    $href=xs_encrypt($href);

    if($uid!=""){
        $attrID = "id=\"$uid\"";
    }else{
        $attrID = "";
    }
    if($bsclass!=""){
        if(strpos($bsclass, "btn")===0){
            $bsclass = "btn $bsclass";
        }
        
        
    }
    return "<a $attrID href=\"$href\" method=\"$method\" class=\"xs-link $bsclass\" title=\"$title\" >$caption</a> ";
}

/*
function getDocumentation($inspectclass) {
    $methods = get_class_methods($inspectclass);
    $class =get_class($inspectclass);
    $arr = [];
    foreach($methods as $method) {
        $ref=new ReflectionMethod( $class, $method);
        if($ref->isPublic()) {
            $arr[$method] = $ref->getDocComment();
        }
    }
    return dump($arr);
}
*/



?>