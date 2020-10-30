<?php
namespace xuserver\modules\auth;


class auth_profile extends \xuserver\v5\model{
    var $authorisations = array();
    var $menuLinks = array();
    var $permissions = "";
    
    protected function OnBuild(){
        $this->permissions = $this->relations()->Fetch("auth_permission");
    }
    protected function OnInstance(){
        
    }
    
    
    
    /** 
     * écran de gestion des autorisations du profil courant.
     */
    public function define_profile(){
        $return="";
        
        $permission = Build("auth_permission");
        $temp_authorisations = array();
        $profile = $this;
        $profile->permissions = $profile->permissions->Read();
        $privileges = $this->load_privileges();
        
        $profile->permissions->iterator()->each(function(\xuserver\v5\model $item)use(&$return,&$temp_authorisations){
            $key = $item->__Key();
            $db_id = $item->db_id();
            $temp_authorisations[$key]=array("crud"=>$item->__CRUD(),"db_id"=>$db_id);
        });
        
        $_IDS=array();
        $destroyed="";
        foreach ($temp_authorisations as $key => $perm ){
            if(!isset($privileges[$key])){
                $_IDS[]= $perm["db_id"];
                $destroyed.="<li>$key</li>";
            }
        }
        if(count($_IDS)>0){
            $permission->db_id($_IDS)->delete();
            echo notify(count($_IDS)." undefined permissions destroyed on profile", "warning");
            echo debug($destroyed);
        }
        
        
        
        foreach ($privileges as $key  ){
            $permission->__Key($key);
            $permission->fk_profile = $this->id_profile;
            
            if(isset($temp_authorisations[$key])){ // le profile possède une autorisation sur le privilege
                $db_id= $temp_authorisations[$key]["db_id"];
                $permission->db_id($db_id);
                $permission->__CRUD($temp_authorisations[$key]["crud"]);
                $return .= $permission->formular("line_update");
                $profile->authorisations[$permission->__Key()]=  $permission->__CRUD();
                
            }else{
                if($permission->permModule =="v5" ){ // aucun module connu , on applique les permissions "may" du profile
                    $permission->__CRUD($this->__CRUD());
                }else{
                    $permission->__CRUD("0000"); // 
                }
                
                if($permission->linestyle =="module"){ 

                        //echo notify("$key pas d'autorisation sur le module" );
                }else if($permission->linestyle =="table"){
                    
                    $try = $permission->permModule;
                    if( isset($temp_authorisations[$try]) ){// le profile ne possède pas d'autorisation pour le privilege de la Table, mais il en possède une pour le Module
                        $permission->__CRUD($temp_authorisations[$try]["crud"]);
                        
                    }else{
                        
                    }
                    
                }else if($permission->linestyle =="method"){
                    
                    $try = $permission->permModule."-".$permission->permTable;
                    if( isset($temp_authorisations[$try]) ){// le profile ne possède pas d'autorisation pour le privilege de la Methode, mais il en possède une pour la Table
                        $permission->__CRUD($temp_authorisations[$try]["crud"]);
                        //echo notify("$try autorisation heritée de la table" );
                    }else{
                        $permission->__CRUD("0000");
                    }
                }else{
                    echo notify("ERROR " . $key);
                }
                $return .= $permission->formular("line_create");
                $profile->authorisations[$permission->__Key()]=  $permission->__CRUD();
            }
        }
        
        return $return;
        
        
    }
    
    
    
    /**
     * création de la table virtuelle des privileges, lecture des tables de la DB et des méthodes des objets possédant une définition dans un fichier php.
     * @return string[]|mixed[]
     */
    private function load_privileges(){
        $tables = $this->db()->query("SHOW TABLES from ".XUSERVER_DB_NAME) ;
        $sys_privileges = array();
        $sys_modules = array();
        
        foreach ($tables as $k ){
            $db_tablename = $k[0];
            $item = Build($db_tablename);
            
            $module= $item->__module();
            if( ! isset($sys_privileges[$module]) ){
                $sys_privileges[$module]=$module;
                $sys_modules[$module]=$module;
            }
            
            //$module=$item->__module();
            $key = "$module-$db_tablename";
            $sys_privileges[$key]=$key;
            $item->methods()->all(function($meth)use(&$sys_privileges,&$key){
                $name = $meth->name();
                if($name =="update" or $name =="delete" or $name =="create" or $name =="read" or $name =="formular" ){
                }else{
                    $Key="$key-$name";
                    $sys_privileges[$Key]=$Key;
                }
            });
        }
        foreach($sys_modules as $module){
            foreach($sys_privileges as $priv){
                $parts=explode("-", $priv);
                $privModule=$parts[0];
                
                if(isset($parts[1]) and ! isset($parts[2])){
                    $privTable=$parts[1];
                    if( strpos($privTable,$module."_")===0 and $privModule!=$module){ // le nom de la table commence par le nom d'un module mais le module du privilège est différent (v5, par défaut) 
                        // on renome la clef du privilège
                        $Key = "$module-$privTable";
                        $sys_privileges[$Key]=$Key;
                        unset($sys_privileges[$priv]);
                    }
                }
            }
        }
        ksort($sys_privileges);
        return $sys_privileges;
    }
    
    
    private function __CRUD(){
        return "$this->may_create$this->may_read$this->may_update$this->may_delete";
    }
    
    
    
    public function __Authorisations(){
        $profile=$this;
        $profile->authorisations = array();
        $profile->define_profile();
        echo notify(count($profile->authorisations) . " autorisations loaded","secondary clear");
        return $profile->authorisations;
        
        /*
        $profile=$this;
        $profile->permissions = $profile->permissions->Read();
        
        $profile->permissions->iterator()->each(function(\xuserver\v5\model $item)use(&$profile){
            $profile->authorisations[$item->__Key()]=  $item->__CRUD();
        });
        echo notify(count($profile->authorisations) . " autorisations loaded","secondary clear");
        return $profile->authorisations;
        */
    }
    
}


?>