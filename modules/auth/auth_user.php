<?php 
namespace xuserver\modules\auth;



class auth_user extends \xuserver\v5\model{
    
    protected function OnBuild(){
        $this->§my_photo->type("text");
        //$this->properties()->Fetch("my_photo")->type("text");
        $this->§auth_profile_profile->type("checkbox");
        
        
        
    }
    
    public function test(){

        
        
        return $this;
    }
    
}

?>