<?php


use xuserver\modules\auth\auth_profile as auth_profile;









/**
 * SESSION COOKIE TIMEOUT
 * @var integer $timeout 
 */
$timeout = 60*10; // session cookie timeout interval in seconds
session_start();

// Check existing timeout variable
if( isset( $_SESSION[ "xslastaccess"] ) ) {
    // Time difference since user sent last request
    $duration = time() - intval( $_SESSION[ "xslastaccess"] );
    // Destroy if last request was sent before the current time minus last request
    if( $duration > $timeout ) {
        // Clear the session
        session_unset();
        // Destroy the session
        session_destroy();
        // Restart the session
        session_start();
    }else{
        //echo notify("dd $duration < $timeout");
    }
}else{
    
}

// Set the last request variable
$_SESSION[ "xslastaccess"] = time();


/**
 * define or retrieve session user object
 * USAGE :
 * session(false) => destroy current user session
 * session(null) => destroy current user session
 * session() => return current user session 
 * session($object) => applies current user session CRUD permissions on given $object, depending on defined system privileges
 *
 * @param string || null || false $action
 * @return session $auth

*/

function session($action = ""){
    $auth = new session();
    $auth->load();
    
    if($action === "") { // test user session
        return $auth;
    }else if ( is_null($action) or $action===false) {
        $auth->kill();
        return $auth;
    }else if( is_object($action) ) {
        /*
        $auth->set($action);
        $auth->save();
        */
        return $auth;
    }
}


/**
 * This class is used to map user session informations
 * @author Gael JAUNIN
 *
 */

class session {
    var $id = ""; 
    var $name = ""; 
    var $profile = "";
    var $id_profile = 1;
    var $authorisations = array();
    var $duration = 0;
    private $last = 0;
    
    function __construct() {
        
    }
    function exist(){
        if (!isset($_SESSION["user"])) {
            return 0;
        }else{
            
            if($this->last ==0){
                $this->duration = 0;
            }else{
                $this->duration = $_SESSION[ "xslastaccess"] - $this->last;
            }
            $this->last = $_SESSION[ "xslastaccess"];
            return 1;
        }
    }
    function kill(){
        unset($_SESSION["user"]);
        session_unset();
        return $this;
    }
    function load() {
        if ($this->exist()) {
            $session = unserialize($_SESSION["user"]);
            foreach ($session as $key => $val) {
                $this->$key = $val;
            }
        }else{
            
        }
        return $this;
    }
    
    function save() {
        $_SESSION["user"] = serialize($this);
        return $this;
    }
    
    function user($user) {
        $this->id = $user->db_id;
        $this->name = $user->my_firstname . " " . $user->my_lastname;
        $this->profile = $user->auth_profile_profile;
        $this->id_profile = $user->auth_profile;
        $this->authorisations = $user->§auth_profile->Read()->__Authorisations();
        $this->save();
    }
    
    
    var $logas = 0; // is admin logged as another user
    var $logasProfile = "";
    function logas(auth_profile $profile=null){
        if(is_null($profile)){ // no profile given, return logas state
            return $this->logas;
        }
        
        if($profile->id_profile == $this->id_profile){ // logback into admin account
            $this->logas=0;
            $this->logasProfile=$this->id_profile;
        }else{
            $this->logas=1;
            $this->logasProfile=$profile->id_profile;
        }
        $this->authorisations = $profile->__Authorisations();
        $this->save();
        
    }
    
    function show() {
        $prop = "";
        $ret = "";
        if(! $this->admin()){
            $avoid = array("id","id_profile","logas","logasProfile", "authorisations","last");
            $isadmin=false;
        }else{
            $avoid = array("authorisations","last");
            $isadmin=true;
        }
        
        if($this->logas()){
            $badge="<span class='badge badge-warning'>log as</span>";
            $href="auth_user-$this->id";
            $back ="<div class='form-group '>".xs_link("",$href, "__log_back", "Log Back","btn-light")."</div>";
            
        }else{
            $badge="";
            $back="";
        }
        foreach ($this as $k => $v) {
            if (in_array($k, $avoid)) {
                continue;
            }
            $prop .= "
            <div class='form-group row'>
                <label class='col-sm-3 col-form-label'>$k</label>
                <div class='col'>
                    <input type='text' class='form-control' value='$v' disabled='disabled' />
                </div>
            </div>
            ";
        }
        foreach ($this->authorisations as $k => $v) {
            
            $kp=explode("-", $k);
            if(isset($kp[2])){
                $u="";$r="";$d="";
                $c=$this->pline($v[2],"U");
                $test = $v[2];
                $bsClass="text-warning";
                $caption ="$k()";
            }else{
                $c=$this->pline($v[0],"C");
                $r=$this->pline($v[1],"R");
                $u=$this->pline($v[2],"U");
                $d=$this->pline($v[3],"D");
                $test = $v[0]+$v[1]+$v[2]+$v[3];
                if(!isset($kp[1])){
                    $bsClass="text-dark";
                    $caption ="{"."$k}";
                }else{
                    $bsClass="text-primary";
                    $caption ="[$k]";
                }
            }
            
            if(! $isadmin and $test<1){
                continue;
            }
            $ret .= "
            <div class='form-group row'>
                <div class='col-sm-5 col-form-label $bsClass'>$caption</div>                
                <div class='col-sm-2'>$c$r$u$d</div>                
            </div>";
        }
        return "
            <div class='accordion'>
                <h4 class='btn btn-light' data-toggle='collapse' href='#form-session-1' >SESSION</h4><div class='collapse show' id='form-session-1' >$prop $back</div>
                <h4 class='btn btn-light' data-toggle='collapse' href='#form-session-2' >PERMISSIONS $badge</h4> <div class='collapse ' id='form-session-2' >$ret</div>
            </div>";
        
    }
    
    private function pline($v,$c){
        if($v=="1"){
            return "<span class='btn btn-success' >$c</span>";
        }else{
            return "<span class='btn btn-light' >$c</span>";
        }
        
    }
    
    function modules($user=null){
        $permission = Build("auth_permission");
        
        
        if(!is_null($user)){
            $href="auth_user-$user->id_user";
        }else{
            $href="auth_user-$this->id";
        }
        
        //$href="auth_user-$this->id";
        
        
        if($this->admin()){
            $admin_menu = "<div class='dropdown-divider'></div>";
            if($this->logas()){
                $admin_menu .=xs_link("",$href, "__log_back", "Log Back","dropdown-item");
            }else{
                $admin_menu .=xs_link("",$href, "_log_as", "Log As","dropdown-item");
            }
        }else{
            $admin_menu="";
        }
        $menu ="<div class='btn-group '>
                        <button type='button' class='btn btn-light dropdown-toggle' data-toggle='dropdown' aria-haspopup='true' aria-expanded='false'>
                            Account
                        </button>
                        <div class='dropdown-menu'>"
                            .xs_link("",$href, "_menu", "Menu","dropdown-item")
                            .$admin_menu
                            ."<div class='dropdown-divider'></div>"
                            .xs_link("",$href, "btn_account", "Contact","dropdown-item")
                            .xs_link("",$href, "btn_profile", "Profile","dropdown-item")
                            ."<div class='dropdown-divider'></div>"
                            .xs_link("",$href, "btn_session", "Session","dropdown-item ")
                            .xs_link("",$href, "btn_logout", "Log out","dropdown-item ")
                        ."</div>
                    </div>";
        
        foreach ($this->authorisations as $key=>$crud){
            $permission->__Key($key);
            $permission->__CRUD($crud);
            if($permission->linestyle=="module" ){
                $items=$this->tables($permission->permModule);
                if($items!=""){
                    //$refresh = xs_link("", $href, "__session_tables('$permission->permModule')", $permission->permModule, "btn btn-light dropdown-toggle", "click to list instances");
                    $menu .="
                    
                        <div class='btn-group '>
                            <button type='button' class='btn btn-light dropdown-toggle' data-toggle='dropdown' aria-haspopup='true' aria-expanded='false'>
                                $permission->permModule
                            </button>
                            <div class='dropdown-menu'>
                                $items            
                            </div>
                        </div>
                    ";
                }
                
            }
        }
        return "<div id='session-modules-uid' class=''>$menu</div>" ;
    }
    
    
    
    function tables($module){
        $menu="";
        $permission = Build("auth_permission");
        foreach ($this->authorisations as $key=>$crud){
            $permission->__Key($key);
            $permission->__CRUD($crud);
            if($permission->__can("read") and $permission->linestyle=="table" and  $permission->permModule==$module){
                $caption = str_replace("_"," ",$permission->permTable);
                $caption = str_replace($permission->permModule,"",$caption);
                $menu .= xs_link("", $permission->permTable, "read", $caption, "dropdown-item", "click to list instances");
            }
        }
        if($menu==""){
            return "" ;
        }
        return "$menu" ;
    }
    
    function admin(){
        if (!isset($_SESSION["user"])) {
            return 0;
        }else{
            if($this->id_profile==XUSERVER_ADMIN_ID){
                return 1;
            }else{
                return 0;
            }
            
        }
    }
    
    /**
     * can the session do something ? 
     * @param string $Key
     * @return boolean
     */
    function can($Key="__NULL__") {
        $return = 0;
        
        if($Key=="__NULL__" or is_null($Key)){
            return 0;
        }
        $permission = Build("auth_permission");
        $permission->__Key($Key);
        $privMethod = $permission->permMethod;
                
        if($privMethod=="create" or  $privMethod=="update" or $privMethod=="read" or $privMethod=="delete"){
            $authKey = "$permission->permModule-$permission->permTable";
            $CAN = $privMethod;
            $tray = "SESSION <b>$privMethod</b> CRUD functionalities $Key";
            
        }else if($privMethod=="form" or  $privMethod=="formular"){
            $authKey = "$permission->permModule-$permission->permTable";
            $CAN = "update";
            $tray = "SESSION <b>$privMethod</b> FORM functionalities $Key";
            
        //}else if($privMethod=="append"){
            
        }else{
            $authKey = $Key;
            $CAN = "use";
            $tray = "SESSION <b>$privMethod</b> USE functionalities $Key";
        }
        
        if (isset($this->authorisations[$authKey])) {
            $permission->__CRUD($this->authorisations[$authKey]);
            $return = $permission->__can($CAN);
            echo systray("$tray = $return");
            
        }else{
            //echo systray("$this->logas supposed to functionality $Key");
            
            $return = 1;
        }
        
        
        
        return $return;
    }
    /*
    function can1($Key="__NULL__") {
        if($Key=="__NULL__" or is_null($Key)){
            return false;
        }
        
        $parts=explode("-", $Key);
        $privModule=$parts[0];
        if(isset($parts[1])){
            $privTable=$parts[1];
        }
        if(isset($parts[2])){
            $privMethod=$parts[2];
        }
        
        if($privMethod=="create" or  $privMethod=="update" or $privMethod=="read" or $privMethod=="delete"){
            $Key = "$privModule-$privTable";
            if (isset($this->authorisations[$Key])) {
                            
            }else{
            }            
                                    
        }else{
                    
        }
        
        
        if (isset($this->authorisations[$Key])) {
            return true;
        }else {
            return false;
        }
    }
    
    function can0($Key="__NULL__") {
        if($Key=="__NULL__" or is_null($Key)){
            return false;
        }
        if (isset($this->authorisations[$Key])) {
            return true;
        }else {
            return false;
        }
    }
    */
    /**
     * @deprecated
     */
    function profile($profile) {
        $this->profile = $profile->profile;
        //$profile->get_authorisations();
        //$this->setAuthorisations($profile);
        
    }
    /**
     * @deprecated
     */
    function setAuthorisations($object) {
        $this->authorisations = $object->authorisations;
        $this->save();
    }
    
    
    
    /**
     * @deprecated
     */
    function old_logAs($bool="") {
        if($bool==""){ // getter
        }else{
            $this->logAs = $bool;
        }
        return (bool)$this->logAs;
    }
    
    /**
     * @deprecated
     */
    function is($object) {
        if ($object->concept == "profile") {
            if ($this->profileMci == $object->mci) {
                $return = true;
            }
            else {
                $return = false;
            }
        }
        else if ($object->concept == "user") {
            if ($this->userMci == $object->mci) {
                $return = true;
            }
            else {
                $return = false;
            }
        }
        return $return;
    }
    
    
    
}

/**
 * deprecated

function session_register($mixed = "") {
    if( is_object($mixed) ) {
        // return authrisation object loaded from active session
        $auth = new session();
        $auth->load();
        return $auth;
    }else{
        // apply permissions on $mixed == xsClass
        if (!isset($_SESSION["user"])) {
            // no session active => CRUD are 0000
            $mixed->CRUD("0000");
        }else{
            // active session
            $auth = new session();
            $auth->load();
            $perm = $auth->has($mixed->privilege());
            
            if ($perm === false) {
                $crud = "0000";
            }else {
                $crud = $perm["crud"];
            }
            $mixed->CRUD_AUTH($crud);
            $mixed->auth=$auth;
            
        }
        return $mixed;
    }
}
 */



?>