<?php 
namespace xuserver\modules\auth;

use function xuserver\v5\Build;
use function xuserver\v5\notify;

class auth_user extends \xuserver\v5\model{
    
    protected function OnBuild(){
        $this->§email->type("email")->caption("e-mail")->comment("your email")->required(1);
        $this->§password->type("password")->caption("mot de passe")->comment("saisir votre mot de passe")->required(1)->disabled(1);
        $this->§my_photo->type("text")->caption("photo")->comment("choisir votre photo");
        $this->§terms_conditions->comment("accepter les conditions d'utilisation")->required(1);
        $this->methods()->select(0);
    }
    
    protected function OnInstance(){
        $session = session();
        $this->properties()->find("email,my_lastname, my_firstname")->select();
        if($session->exist()){
            $this->properties()->find("email,my_lastname, my_firstname,#auth_locked,#terms_conditions")->select();
            $this->§terms_conditions->required(0);
        }else{
            $this->properties()->find("email,my_lastname, my_firstname")->select();
        }
        $this->methods()->select(0)->find("#update,#create")->select(1);
    }
    
    public function lock($opt="0"){
        $this->properties()->find("#id_user, name,#auth_locked,#terms_conditions")->select();
        $this->properties()->find("#terms_conditions")->val($opt)->where();
        $this->read();
        $return = $this->ui->table().$this->ui->structure();
        
        if($opt=="0"){
            $this->val(null)->properties()->find("#auth_locked")->val(1)->update();
            $this->val(null)->properties()->find("#terms_conditions")->val(1)->where();
            echo notify($opt.$this->sql()->statement());
        }else{
            $this->val(null)->properties()->find("#auth_locked")->val($opt)->update();
            $this->val(null)->properties()->find("#terms_conditions")->val(1)->where();
            echo notify($opt.$this->sql()->statement());
        }
        return $return;
    }
    
    public function form_account(){
        $session = session();
        $return = "";
        if(!$session->exist()){
            $return .= $this->form_signin();
        }else{
            $this->read($session->id);
            $this->update($_POST);
            if(isset($_POST["my_firstname"])){
                $return.=notify("updated","success");
                $return .= $this->ui->structure();
            }
            $session->user($this);
            $this->properties()->find("#email, my_")->select();
            $this->methods()->select("0")->find("form_account,form_profile,logout")->select("1");
            $return .= $this->ui->form();
        }
        $pinsession ="<div id='pinsession'>$session->name</div>";
        return $pinsession.$return; 
    }
    
    public function form_profile(){
        $session = session();
        $return = "";
        if(!$session->exist()){
            $return .= $this->form_signin();
        }else{
            $this->read($session->id);
            
            if(isset($_POST["auth_profile_profile"])){
                $this->update($_POST);
                $return.=notify("updated","success");
                $return .= $this->ui->structure();
            }
            $session->user($this);
            $this->properties()->find("profile")->select()->disabled("1");
            $this->methods()->select("0")->find("form_profile,form_account")->select("1");
            $return .= $this->ui->form();
        }
        
        return $return; 
    }
    
    public function logout(){
        $session = session();
        if(!$session->exist()){
            $return = $this->form_signin();
        }else{
            $session->kill();
            $this->val(null);
            $return = $this->form_signin();
        }
        return $return; 
    }
    
    
    public function form_signin(){
        $session = session();
        $return = ""; 
        $this->properties()->find("#email, #password")->select()->disabled("0");
        $this->methods()->select("0")->find("form_signin")->select("1");
        
        
        if(!$session->exist()){
            
            if(isset($_POST["password"])){
              //$this->val($_POST); 
              $Visitor = Build($this->db_tablename());
              $Visitor->email =$_POST["email"];
              $Visitor->password =$_POST["password"];
              $Visitor->id_user =null;
              $Visitor->properties()->find()->select()->where();
              $Visitor->read();
              if($Visitor->iterator()->count()==1){
                  $user = $Visitor->iterator()->Fetch(0);
                  if($user->email =$_POST["email"] and $user->password =$_POST["password"]){
                      $Visitor->val($user);
                      $session->user($Visitor);
                      $return = $Visitor->form_account();
                      $return.=notify("Welcome $Visitor->my_firstname,<br/>login granted","success");
                      echo "<div id='model-structure'>".$session->show()."</div>";
                  }else{
                      session(false);
                      $return .= $this->ui->form();
                      $return.=notify("attack");
                  }
              }else{
                  session(false);
                  $return .= $this->ui->form();
                  $return.=notify("user unknown");
                  //echo "<div id='structure'>".$this->ui->structure()."</div>";
              }
          }else{
            $return.=notify("Please login");
            $return .= $this->ui->form();
          }
        }else{
            //$Visitor = Build($this->db_tablename());
            $this->read($session->id);
            $return .= $this->form_account();
        }
        
        $pinsession ="<div id='pinsession'>$session->name</div>";
        return $pinsession.$return.$this->ui->structure();
    }
    
    
    
    public function form_signup(){
        $session = session();
        $return = ""; 
        $this->properties()->find("#email, #password, #terms_conditions")->select();
        $this->methods()->find("#form_signup")->select(1);
        if(!$session->exist()){
            if(isset($_POST["password"])){
                //$this->insert($_POST);
            }
            $return .= $this->ui->form();
            
        }else{
            $return .= $this->form_account();
        }
        return $return;
    }
    
    
}

?>