<?php

include $_SERVER["DOCUMENT_ROOT"]."/xs-framework/v5/config.php";
use xuserver\v5\property;

/**********************************************************************************************************
 * project
 **********************************************************************************************************/

$session = session();
if($session->exist()){
    $pinsession = $session->name; 
}else{
    $pinsession ="visitor";
}


$pinsession ="<"."div id='pinsession'>$pinsession</div>";
if(! isset($_GET["q"])){
    $_GET["q"]="_home";
}

$func = $_GET["q"];

if(true){
  $example=new xs_Examples();
  $menubar = $example->_menubar();
  $result = $example->$func();
  $structure = "";
}else{
  $example=new xs_Examples();
  $menubar = $example->_menubar();
  $result = "site web under construction <br/> <img src='data:image/jpeg;base64,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' />";
  $structure = "not working";
}

class xs_Examples{
    function _menubar(){
        $exampleR = new ReflectionClass('xs_Examples');
        $methods = $exampleR->getMethods();
        
        $menus=array();
        
        foreach ($methods as $method){
            if( strpos($method->name, "_") === 0 ){
                continue;
            }
            $parts=explode("_", $method->name);
            $menus[$parts[0]]=$parts[0];
            
        }
        
        $html="";
        
        foreach ($menus as $menu){
            
            $html .= "<li class='nav-item dropdown'>
            <a class='nav-link dropdown-toggle' href='#' data-toggle='dropdown' aria-haspopup='true' aria-expanded='false'>$menu</a>
            <div class='dropdown-menu' aria-labelledby='dropdown10'>";
            
            foreach ($methods as $method){
                if( strpos($method->name, "_") === 0 ){
                    continue;
                }
                $parts=explode("_", $method->name);
                if( $parts[0]== $menu){
                    array_shift($parts);
                    $label = implode(" ", $parts) ;
                    if( $method->name== "help_database"){
                        $target="database";
                    }else{
                        $target="";
                    }
                    $html .= "<a class='dropdown-item' href='?q=$method->name' target='$target'>$label</a>";
                }else{
                    
                }
            }
            $html.="</div>
          	</li>";
            
        }
        
        
        return $html;
    }
    
    function _home(){
        $obj = Build("auth_user");
        return $obj->btn_account();
    }
    
    function account_my_account(){
        $obj=Build("auth_user");
        return $obj->btn_account();
    }
    
    function account_sign_in(){
        $obj = Build("auth_user");
        return $obj->btn_login();
    }
    
    function account_sign_up(){
        $obj=Build("auth_user");
        return $obj->btn_register();
    }
    
    function account_logout(){
        $obj=Build("auth_user");
        return $obj->btn_logout();
    }
    
    
    
    function admin_session(){
        
        $session = session();
        $session->kill();
        $return = "<h1>SESSION KILLED</h1>";
        return $return ;
    }
    
    
    function admin_users(){
        $session = session();
        $obj=Build("auth_user");
        if(!$session->admin()){
            $obj->properties()->find("y_lastname, y_firstname")->select();
            $obj->read();
            echo notify("Accès refusé","danger clear");
            $return = $obj->ui->table();
        }else{
            $obj->read();
	    // github template no database full names
            $obj->properties()->find("id, mail,y_lastname, y_firstname, h_locked, ms_conditions")->select();
            $return = $obj->ui->table().$obj->ui->structure();
        }
        
        return $return;
    }
    
    function admin_profile(){
        $session = session();
        if(!$session->admin()){
            return notify("Accès refusé","danger clear");
        }
        $obj=Build("auth_profile");
        $obj->properties()->find()->select();
        $obj->properties()->find("may_read")->orderby("ASC");
        $obj->read();
        return $obj->ui->table();
    }
    
    function admin_privilege(){
        $session = session();
        if(!$session->admin()){
            return notify("Accès refusé","danger clear");
        }
        $obj=new \xuserver\v5\model();
        $obj->build("auth_privilege");
        $obj->read();
        return $obj->ui->table();
    }
    
    function admin_permission(){
        $session = session();
        if(!$session->admin()){
            return notify("Accès refusé","danger clear");
        }
        $obj=new \xuserver\v5\model();
        $obj->build("auth_permission");
        $obj->read();
        return $obj->ui->table();
    }
    
    function help_database(){
        $session = session();
        if(!$session->admin()){
            return notify("Accès refusé","danger clear");
        }
        header("Location: http://youdomaine.com/yourmysqladmin.php");
        die();
    }
    function help_home(){
        
        header("Location: index.php");
        die();
    }
    function help_documentation(){
        header("Location: https://www.xuserver.net/xs-framework/documentation.php");
        die();
    }
    
}

?>



<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8" />
    <title>Gestion utilisateurs</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.0/css/bootstrap.min.css" />
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script src="../xs-framework/v5/xuserver.net.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.16.0/umd/popper.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.0/js/bootstrap.min.js"></script>
    
    <style type="text/css">
    html {
      scroll-behavior: smooth;
    }
    </style>
  </head>
  <body>
	<nav class="navbar  navbar-expand-sm navbar-light bg-light">
      <a class="navbar-brand" href="index.php"><?php echo $_SERVER["SERVER_NAME"];?></a>
      <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarCollapse" aria-controls="navbarCollapse" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse" id="navbarCollapse">
        <ul class="navbar-nav mr-auto">
          <?php echo $menubar ; ?>
        </ul>
        <a href="index.php"><?php echo $pinsession ; ?></a>
      </div>
    </nav>
    
    <div class="container-fluid">
        <div class="row ">
            <div class="col col-md-10" >
                <ul class="nav nav-pills mb-3" role="tablist">
                  <li class="nav-item">
                    <a class="nav-link active show"  data-toggle="pill" href="#xs-screen" role="tab" >Result</a>
                  </li>
                  <li class="nav-item">
                    <a class="nav-link " data-toggle="pill" href="#pills-structure" role="tab" >Help</a>
                  </li>
                </ul>
                <div class="tab-content" >
                  <div class="tab-pane fade active show xs-screen" id="xs-screen" role="tabpanel" ><?php echo $result; ?></div>
                  <div class="tab-pane fade " id="pills-structure" role="tabpanel" ><div id="model-structure" class="xs-placeholder" ><?php echo $structure; ?></div></div>
                </div>
            </div>		
        </div>
    </div>
         
	<footer style='position: fixed;  left: 0;  bottom: 0;  width: 100%; text-align: center;'>
      <div class="container-fluid">
        <span class="text-muted">xuserver 2020 </span>
      </div>
    </footer>
  </body>
</html>














