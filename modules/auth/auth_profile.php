<?php
namespace xuserver\modules\auth;

//use function xuserver\v5\Build;
//use function xuserver\v5\notify;
//use function xuserver\v5\debug;


class auth_profile extends \xuserver\v5\model{
    var $authorisations = array();
    var $permissions = "";
    
    protected function OnBuild(){
        $this->permissions = $this->relations()->Fetch("auth_permission");
    }
    protected function OnInstance(){
        
    }
    
    /*
    private function _old__panel(){
        $profile = $this;
        $profile->permissions = $profile->permissions->Read();
        $privs = $this->_old__system_privileges();
        return $privs .$this->permissions->panel();
    }
    private function _old__system_privileges(){
        //$obj = Build("auth_privilege");
        //$obj->iterator()->Init();
        
        $return = "";
        $privs = $this->_system_privileges_array();
        
        foreach ($privs as $kp => $priv ){
            $return .= "<div>$kp $priv</div>";
            //$obj->iterator()->Attach($item);
        }
        return ("<div>$return</div>");
    }
    */
    
    
    public function panel(){
        $return="";
        
        $permission = Build("auth_permission");
        $authorizations = array();
        $profile = $this;
        $profile->permissions = $profile->permissions->Read();
        $privileges = $this->load_privileges();
        
        $profile->permissions->iterator()->each(function(\xuserver\v5\model $item)use(&$return,&$authorizations){
            $key = $item->__Key();
            $db_id = $item->db_id();
            $authorizations[$key]=array("crud"=>$item->__CRUD(),"db_id"=>$db_id);
        });
        
        
        
        foreach ($privileges as $key  ){
            $permission->__Key($key);
            $permission->fk_profile = $this->id_profile;
            
            if(isset($authorizations[$key])){ // le profile possède une autorisation sur le privilege
                $db_id= $authorizations[$key]["db_id"];
                $permission->db_id("$db_id");
                $permission->__CRUD($authorizations[$key]["crud"]);
                $return .= $permission->formular("line_update");
                
            }else{
                if($permission->permModule =="v5" ){
                    $permission->__CRUD($this->__CRUD());
                }else{
                    $permission->__CRUD("0000");
                }
                
                if($permission->linestyle =="module"){ 

                        //echo notify("$key pas d'autorisation sur le module" );
                }else if($permission->linestyle =="table"){
                    
                    $try = $permission->permModule;
                    if( isset($authorizations[$try]) ){// le profile ne possède pas d'autorisation pour le privilege de la Table, mais il en possède une pour le Module
                        $permission->__CRUD($authorizations[$try]["crud"]);
                        
                    }else{
                        
                    }
                    
                }else if($permission->linestyle =="method"){
                    
                    $try = $permission->permModule."-".$permission->permTable;
                    if( isset($authorizations[$try]) ){// le profile ne possède pas d'autorisation pour le privilege de la Methode, mais il en possède une pour la Table
                        $permission->__CRUD($authorizations[$try]["crud"]);
                        //echo notify("$try autorisation heritée de la table" );
                    }else{
                        $permission->__CRUD("0000");
                    }
                }else{
                    echo notify("ERROR " . $key);
                }
                $return .= $permission->formular("line_create");
            }
        }
        
        return $return;
        
        $return ="";
        foreach ($privileges as $key  ){
            if(isset($authorizations[$key])){ // le profile possède une autorisation sur le privilege
                
                $permission->__Key($key);
                $permission->fk_profile = $this->id_profile;
                $permission->__CRUD($authorizations[$key]["crud"]);
                $return .= $permission->formular("line_update");
                
            }else{
                $permission->__Key($key);
                $permission->fk_profile = $this->id_profile;
                if( isset($authorizations[$permission->permModule]) ){// le profile possède d'autorisation sur le privilege, mais il possède un autorisation sur le module
                    if($permission->linestyle =="module"){
                        $permission->__CRUD("0000");
                    }else if($permission->linestyle =="table"){
                        $permission->__CRUD($authorizations[$permission->permModule]["crud"]);
                    }else if($permission->linestyle =="method"){
                        $permission->__CRUD("0000");
                    }
                    
                    $return .= $permission->formular("line_create");
                }else{// le profile ne possède aucune autorisation
                    $permission->__CRUD("0000");
                    $return .= $permission->formular("line_create");
                }
            }
        }
        
        return $return;
    }
    
    private function load_privileges(){
        $tables = $this->db()->query("SHOW TABLES from ".XUSERVER_DB_NAME) ;
        $arr = array();
        foreach ($tables as $k ){
            $db_tablename = $k[0];
            $item = Build($db_tablename);
            
            $module= $item->_module();
            if( ! isset($arr[$module]) ){
                $arr[$module]=$module;
            }
            
            $module=$item->_module();
            $key = "$module-$db_tablename";
            $arr[$key]=$key;
            $item->methods()->all(function($meth)use(&$arr,&$key){
                $name = $meth->name();
                if($name =="update" or $name =="delete" or $name =="create" or $name =="read" or $name =="formular" ){
                }else{
                    $Key="$key-$name";
                    $arr[$Key]=$Key;
                }
            });
        }
        return $arr;
    }
    
    
    private function __CRUD(){
        return "$this->may_create$this->may_read$this->may_update$this->may_delete";
    }
    
    
    
    public function __Authorisations(){
        $profile=$this;
        $profile->permissions = $profile->permissions->Read();
        
        $profile->permissions->iterator()->each(function(\xuserver\v5\model $item)use(&$profile){
            $profile->authorisations[$item->__Key()]=  $item->__CRUD();// $item->__Key() $item->__CRUD();
        });
        echo notify(count($profile->authorisations) . " autorisations loaded","secondary clear");
        return $profile->authorisations;
    }
    
}


?>