<?php 
namespace xuserver\modules\auth;

//use function xuserver\v5\Build;
//use function xuserver\v5\notify;
//use function xuserver\v5\debug;


class auth_permission extends \xuserver\v5\model{
    
    /*
    public function panel(){
        $return="";
        $this->iterator()->each(function(\xuserver\v5\model $item)use(&$return){
            $return.=$item->line();
        });
        
        $return="<div><button type='button' class='close' data-dismiss='alert' aria-label='Close'><span aria-hidden='true'>&times;</span></button><h3>vos permissions </h3>$return <div id='auth_permission-uid'></div></div> ";
        return $return;
    }
    */
    public $linestyle="";
    public $permModule="";
    public $permTable="";
    public $permMethod="";
    
    
    public function __Key($set=""){
        if($set!=""){
            $this->sys_priv = $set;
        }
        $parts=explode("-", $this->sys_priv);
        if(isset($parts[2])){
            $this->linestyle ="method";
            $this->permModule=$parts[0] ;
            $this->permTable=$parts[1] ;
            $this->permMethod=$parts[2] ;
        }else if(isset($parts[1])){
            $this->linestyle ="table";
            $this->permModule=$parts[0] ;
            $this->permTable=$parts[1] ;
            $this->permMethod="" ;
        }else{
            $this->linestyle ="module";
            $this->permModule=$parts[0] ;
            $this->permTable="" ;
            $this->permMethod="" ;
        }
        
        return $this->sys_priv;
        //return $this->title();
    }
    
    public function __CRUD($set="__NULL__"){
        
        if($set=="__NULL__"){
        }else{
            $this->can_create=$set[0];
            $this->can_read=$set[1];
            $this->can_update=$set[2];
            $this->can_delete=$set[3];
        }
        return "$this->can_create$this->can_read$this->can_update$this->can_delete";
    }
    
    
    public function __can($what){
        if($what=="use"){
            $CAN = "can_update";
        }else{
            $CAN = "can_$what";
        }
        return $this->$CAN;
    }
    
    private function line($type="create"){
        $href = $this->href();
        $key = $this->__Key();
        $uid = $this->fk_profile."-$key";
        $separator = "";
        
        if($this->linestyle =="module"){
            $bsline="dark";
            $padding="";
            $caption="[".strtoupper($key)."]";
            
            $separator ="<hr />";
            if($type=="create"){
                $bsline="secondary";
                $buttons = "<div class='p-1'>".$this->btn_cant("create")."</div><div class='p-1'>".$this->btn_cant("read")."</div><div class='p-1'>".$this->btn_cant("update")."</div><div class='p-1'>".$this->btn_cant("delete")."</div>";
            }else{
                $buttons = "<div class='p-1'>".$this->btn_can("create")."</div><div class='p-1'>".$this->btn_can("read")."</div><div class='p-1'>".$this->btn_can("update")."</div><div class='p-1'>".$this->btn_can("delete")."</div>";
            }
        }else if($this->linestyle =="table"){
            $bsline="primary";
            $padding="T";
            $caption="&nbsp; &nbsp; {".$this->permTable."}";
            if($type=="create"){
                $bsline="secondary";
                $buttons = "<div class='p-1'>".$this->btn_cant("create")."</div><div class='p-1'>".$this->btn_cant("read")."</div><div class='p-1'>".$this->btn_cant("update")."</div><div class='p-1'>".$this->btn_cant("delete")."</div>";
            }else{
                $buttons = "<div class='p-1'>".$this->btn_can("create")."</div><div class='p-1'>".$this->btn_can("read")."</div><div class='p-1'>".$this->btn_can("update")."</div><div class='p-1'>".$this->btn_can("delete")."</div>";
            }
            
        }else if($this->linestyle =="method"){
            $bsline="warning";
            $padding="M";
            //$caption="&nbsp;&nbsp;$key()";
            $caption="&nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; .$this->permMethod()";
            
            if($type=="create"){
                $bsline="secondary";
                $buttons = "<div class='p-1'>".$this->btn_cant("use")."</div>";
            }else{
                $buttons = "<div class='p-1'>".$this->btn_can("use")."</div>";
            }
            
        }else{
            $bsline="danger";
            $padding="";
            $caption="$key";
            $buttons ="";
        }
        
        if($type=="create"){
            return "
        $separator
        <div class='d-flex flex-row' id=\"$uid\">
            <div class='w-25 p-1'>
                 <a href='$href' class='btn btn-outline-$bsline btn-block text-left font-weight-normal  xs-link' method=\"__createProfilePermission('".$this->fk_profile."','$key')\" >$caption</a>
            </div>
            $buttons
        </div>";
            
        }else{
            
            return "
        $separator
        <div class='d-flex flex-row ' id=\"$uid\"> 
            <div class='w-25 p-1'>
                <a href='$href' class='btn btn-$bsline   btn-block text-left font-weight-bold  xs-link' method=\"__deleteProfilePermission('".$this->fk_profile."','$key')\" >$caption</a>
            </div>
            $buttons
        </div>";
        }
        
        
    }
    
    public function __createProfilePermission($profile="",$name="create"){
        
        $this->fk_profile = $profile;
        $this->sys_priv = $name;
        
        $profile = $this->§fk_profile->Read();
        
        $this->can_create = $profile->may_create;
        $this->can_read = $profile->may_read;
        $this->can_update = $profile->may_update;
        $this->can_delete = $profile->may_delete;
        
        
        $created = $this->create();
        return $created->formular("line_update");
    }
    
    public function __deleteProfilePermission($profile="",$name="create"){
        
        $this->fk_profile = $profile;
        $this->sys_priv = $name;
        
        $this->delete();
        return $this->formular("line_create");
    }
    
    protected function OnFormular($type){
        if($type==""){
            $this->properties()->find("#fk_profile,can_")->select(1);
            $this->methods()->select(0)->find("update,formular")->select(1);
            $this->ui->head($this->sys_priv,"Formulaire de mise à jour ");
            
        }else{
            $this->ui->head($this->sys_priv,"Formulaire de suppression ");
        }
    }
    
    public function formular($type=""){
        
        if($type=="line_update"){
            return $this->line("update");
        }else if($type=="line_create"){
            return $this->line("create");
        }
        $this->ui->uid(null);
        return "<div id='auth_permission-uid'>".parent::formular($type)."</div>".$this->ui->structure();
    }
    
    
    
    
    public function __add($what="create"){
        return $this->change($what,1);
    }
    public function __remove($what="create"){
        return $this->change($what,0);
    }
    
    private function change($what,$val){
        
        if($what=="use"){
            $CAN = "can_update";
            $this->$CAN=$val;
            $this->update();
            return $this->btn_can($what);
        }else{
            $CAN = "can_$what";
            $this->$CAN=$val;
            $this->update();
            return $this->btn_can($what);
        }
        
    }
    
    
    
    private function btn_cant($caption="create"){
        if($caption=="use"){
            $CAN = "can_update";
        }else{
            $CAN = "can_$caption";
        }
        
        if($this->$CAN){
            $bsclass="btn-outline-success";
            $method = "__remove('$caption')";
        }else{
            $bsclass="btn-outline-danger";
            $method = "__add('$caption')";
        }
        
        return "<a href='javascript:void(0)' class='btn $bsclass disabled'  >$caption</a>";
    }
    private function btn_can($caption="create"){
        if($caption=="use"){
            $CAN = "can_update";
        }else{
            $CAN = "can_$caption";
        }
        
        if($this->$CAN){
            $bsclass="btn btn-success";
            $method = "__remove('$caption')";
        }else{
            $bsclass="btn btn-danger";
            $method = "__add('$caption')";
        }
        $uid = $this->fk_profile."-".$this->__Key()."-$CAN";
        
        $href = $this->href();
        
        return xs_link($uid, $href, $method, $caption, $bsclass, "click to change status");
        //return $this->button($caption, $method, $bsclass, "$uid click to change status");
        
        
        
    }
    
    
}


?>