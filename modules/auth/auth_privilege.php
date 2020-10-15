<?php
namespace xuserver\modules\auth;


class auth_privilege extends \xuserver\v5\model{
}

/**
 * @deprecated
 * @author Zotac Local
 *

class auth_privileges extends \xuserver\v5\model{
    var $authorisations = array();
    var $permissions = "";
    
    protected function OnBuild(){
        
    }
    protected function OnInstance(){
        
    }
    public function build($db_tablename=""){
        $p = new property();
        $p->name("id_privilege");
        $p->db_name("id_privilege");
        $p->type("pk");
        $this->db_index = "id_privilege";
        $p->is_primarykey("id_privilege");
        
        $this->properties()->Attach($p);
        
        $p = new property();
        $p->name("code");
        $p->db_name("code");
        $p->type("text");
        
        $this->properties()->Attach($p);
    }
    
    public function read($id=""){
        $tables = $this->db()->query("SHOW TABLES from ".XUSERVER_DB_NAME) ;
        $arr = array();
        foreach ($tables as $k ){
            $kk = $k[0];
            $item = Build($kk);
            $arr[]=$kk;
            $item->methods()->all(function($meth)use(&$arr,&$kk){
                $name = $meth->name();
                if($name =="update" or $name =="delete" or $name =="create" or $name =="read" ){
                }else{
                    $arr[]=$kk."-".$name;
                }
            });
                
        }
        
        $return = "";
        
        $this->iterator()->Init();
        
        foreach ($arr as $kp => $priv ){
            $return .= "<div>$priv</div>";
            //$obj->iterator()->Attach($item);
        }
    }
    
    
}
 */

?>