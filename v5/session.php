<?php


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



/**
 * define or retrieve session user object
 * USAGE :
 * auth(false) => destroy current user session
 * auth($user) => create session corresponding to given $user
 * auth() => return current user session or false if no session is active
 * auth($object) => applies current user session CRUD permissions on given $object, depending on defined system privileges
 *
 * @param xsClass $user
 * @return unknown|string
 */
session_start();
function session($action =""){
    $auth = new session();
    $auth->load();
    if($action === "") { // test user session
        return $auth;
    }else if ( is_null($action) or $action===false) {
        $auth->kill();
        return $auth;
    }else if( is_object($action) ) {
        $auth->set($action);
        $auth->save();
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
    
    var $authorisations = array();
    
    // tells if using admin logas mode
    var $logas = 0;
    function __construct() {
        
    }
    function exist(){
        if (!isset($_SESSION["user"])) {
            return 0;
        }else{
            return 1;
        } 
    }
    function kill(){
        unset($_SESSION["user"]);
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
        $this->save();
        //$this->setAuthorisations($user);
    }
    function profile($profile) {
        $this->profile = $profile->profile;
        //$profile->get_authorisations();
        //$this->setAuthorisations($profile);
        
    }
    function setAuthorisations($object) {
        $this->authorisations = $object->authorisations;
        $this->save();
    }
    
    
    
    
    function logAs($bool="") {
        if($bool==""){ // getter
        }else{
            $this->logAs = $bool;
        }
        return (bool)$this->logAs;
    }
    function isAdmin() {
        $GLOBAL_PROFILE_ADMIN_NAME = "administrator";
        if ($this->logAs) {
            return true;
        }
        if ($this->profileName == $GLOBAL_PROFILE_ADMIN_NAME) {
            return true;
        }
        else {
            return false;
        }
        
    }
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
    
    function has($match) {
        if (isset($this->authorisations[$match])) {
            return $this->authorisations[$match];
        }
        else {
            return false;
        }
    }
    function show() {
        $prop = "";
        $ret = "";
        foreach ($this as $k => $v) {
            if ($k == "authorisations") {
                continue;
            }
            $prop .= "<li class='text-primary' style='cursor:pointer'><span>$k $v</span></li>";
        }
        
        /*
        foreach ($this->authorisations as $k => $v) {
            $ret .= "<li class='text-success' style='cursor:pointer'><span>$k " . $v["crud"] . "</span></li>";
        }
        */
        return "<li><span>SESSION</span><ul>$prop</ul></li><li><span>PERMISSIONS</span><ul>$ret</ul></li>";
    }
    
}

?>