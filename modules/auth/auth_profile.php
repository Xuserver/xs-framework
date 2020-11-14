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
    
    public function formular($type="update"){
        $session = session();
        
        // not admin mode !
        if(! $session->admin()){
            $this->methods()->select(0);
            $this->properties()->find("#profile,description")->select()->disabled(1);
            $this->ui->head("","");
            $this->ui->footer("vous n'avez pas la possibilité de modifier cet objet");
            return $this->ui->form();
        }
        // admin mode !
        
        $this->methods()->select(0);
        $this->§update()->caption("description");
        
        if($session->logas()){ //session admin user using another profile
            $this->§log_back()->select(1)->caption("back to admin")->bsClass("danger");
            
            if($session->logasProfile==$this->id_profile or $this->id_profile==XUSERVER_ADMIN_ID){
                $this->§log_as()->select(1)->disabled(0);
                $this->ui->head("","");
            }else{
                $this->§log_as()->select(1);
                $this->ui->head("","");
            }
            
        }else{
            if($this->id_profile==XUSERVER_ADMIN_ID){
                $this->§log_as()->disabled(1);
            }else{
                
            }
            $this->§log_back()->select(0);
            $this->§log_as()->select(1);
            $this->ui->head("","Liste des permissions du profile");
        }
        
        $this->methods()->find("update")->select(1);
        $this->methods()->find("defaults,defined")->select(1)->bsClass("btn-info");
        $this->methods()->find("update,log_as,panel")->sort();
        
        
        if($type=="update"){
            $this->properties()->find("#profile,description")->select();
            $this->ui->footer("");
            $this->ui->head($this->title(),"Formulaire de description du profile");
        }else if($type=="panel_defaults"){
            $this->ui->head($this->title(),"Autorisations par défaut du profile");
            $this->properties()->find("profile,may_")->select();
            $this->§profile->required(1);
        }else if($type=="panel_defined"){
            $this->ui->head($this->title(),"Choix des autorisations du profile");
            $this->properties()->find("profile")->select()->type("hidden");
            $panel=$this->__define_profile();
            $this->ui->footer($panel);
        }
        
        return $this->ui->form();
    }
    
    public function defaults(){
        if(isset($_POST["may_update"])){
            $this->update($_POST);
        }
        return $this->formular("panel_defaults");
    }
    public function defined(){
        return $this->formular("panel_defined");
    }
    public function log_as(){
        $session = session();
        if($session->admin()){//do log as
            $session->logas($this);
            echo notify("You are now using <b>".$this->title()."</b> profile","info","LOG AS");
            return $this->formular("update");
        }else{
            return "";
        }
    }
    
    public function log_back(){
        $session = session();
        if($session->admin()){
            if($session->logas()){ //do log back
                $profile = Build("auth_profile");
                $profile->read($session->id_profile);
                $session->logas($profile);
                echo notify("You are now back into ADMIN profile","info","LOG AS");
                return $this->formular("update");
            }else{//do log as
                return $this->log_as();
            }
        }else{
            return "";
        }
    }
    
    
    
    /** 
     * Meta Private method
     * 
     *  
     */
    public function __define_profile(){
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
            
            if(isset($temp_authorisations[$key])){ // le profile poss�de une autorisation sur le privilege
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
                    if( isset($temp_authorisations[$try]) ){// le profile ne poss�de pas d'autorisation pour le privilege de la Table, mais il en poss�de une pour le Module
                        $permission->__CRUD($temp_authorisations[$try]["crud"]);
                        
                    }else{
                        
                    }
                    
                }else if($permission->linestyle =="method"){
                    
                    $try = $permission->permModule."-".$permission->permTable;
                    if( isset($temp_authorisations[$try]) ){// le profile ne poss�de pas d'autorisation pour le privilege de la Methode, mais il en poss�de une pour la Table
                        $permission->__CRUD($temp_authorisations[$try]["crud"]);
                        //echo notify("$try autorisation herit�e de la table" );
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
     * création de la table virtuelle des privileges, lecture des tables de la DB et des m�thodes des objets possédant une définition dans un fichier php.
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
                    // controled by table privilege
                    
                }else if(strpos($name, "__") ===0){
                    // private public methods, do not  
                }else if(strpos($name, "_") ===0)  {
                    // protected public methods, create a privilege on it 
                    $Key="$key-$name";
                    $sys_privileges[$Key]=$Key;
                }else{
                    // public public methods : no privilege, access to anybody, not controled
                    
                }
            });
        }
        foreach($sys_modules as $module){
            foreach($sys_privileges as $priv){
                $parts=explode("-", $priv);
                $privModule=$parts[0];
                
                if(isset($parts[1]) and ! isset($parts[2])){
                    $privTable=$parts[1];
                    if( strpos($privTable,$module."_")===0 and $privModule!=$module){ // le nom de la table commence par le nom d'un module mais le module du privil�ge est diff�rent (v5, par d�faut) 
                        // on renome la clef du privil�ge
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
        $profile->__define_profile();
        
        echo notify(count($profile->authorisations) . " autorisations loaded","secondary clear",$profile->title());
        return $profile->authorisations;
        
    }
    
}


?>