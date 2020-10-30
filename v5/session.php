<?php


//use xuserver\v5\model;









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
    var $logas = 0; // is admin logged as another user
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
    function authorisations($profile){
        $this->authorisations = $profile->__Authorisations();
        $this->save();
    }
    function show() {
        $prop = "";
        $ret = "";
        foreach ($this as $k => $v) {
            if ($k == "authorisations" or $k == "last") {
                continue;
            }
            $prop .= "<li class='text-primary' style='cursor:pointer'><span>$k $v</span></li>";
        }
        
        foreach ($this->authorisations as $k => $v) {
            
            
            $ret .= "<li class='text-success' style='cursor:pointer'><span>$k " . $v . "</span></li>";
        }
        return "<li><span>SESSION</span><ul>$prop</ul></li><li><span>PERMISSIONS</span><ul>$ret</ul></li>";
    }
    
    function modules(){
        $menu="";
        $permission = Build("auth_permission");
        $href="auth_user-$this->id";
        
        foreach ($this->authorisations as $key=>$crud){
            $permission->__Key($key);
            $permission->__CRUD($crud);
            if($permission->linestyle=="module" ){
                $menu .= xs_link("", $href, "__session_tables('$permission->permModule')", $permission->permModule, "list-group-item list-group-item-action list-group-item-primary", "click to list instances");
                
                $menu .="<div id='session-modules-$permission->permModule-uid' class='list-group'></div>";
                
                
            }
        }
        return "<div id='session-modules-uid' class='list-group'>$menu</div>" ;
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
                $menu .= xs_link("", $permission->permTable, "read", $caption, "list-group-item list-group-item-action", "click to list instances");
            }
        }
        return "<div id='session-modules-$module-uid' class='list-group'>$menu</div>" ;
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
     * can the session di something ? 
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
        }else{
            $authKey = $Key;
            $CAN = "use";
        }
        
        if (isset($this->authorisations[$authKey])) {
            $permission->__CRUD($this->authorisations[$authKey]);
            $return = $permission->__can($CAN);
        }else{
            $return = 0;
        }
        if ($return) {
            
        }else{
            
        }
        
        
        return $return;
    }
    
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
    function logAs($bool="") {
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