<?php 
namespace xuserver\modules\auth;

use xuserver\v5\property;

class auth_user extends \xuserver\v5\model{
    var $authorisations=array();
    
    protected function OnBuild(){
        $this->§email->type("email")->caption("e-mail")->comment("Saisir votre identifiant")->required(1);
        $this->§password->type("password")->caption("mot de passe")->comment("Saisir votre mot de passe")->required(1);
        $this->§my_photo->type("file")->caption("photo")->comment("choisir votre photo");
        $this->§terms_conditions->comment("accepter les conditions d'utilisation")->required(1);
        $this->methods()->select(0);
        $this->§logout()->bsClass("btn-warning");
        $this->§post_recover_password()->bsClass("btn-warning")->caption("password");
    }
    
    protected function OnInstance(){
        $session = session();
        if($session->exist()){
            $this->properties()->find("email,my_lastname, my_firstname,#auth_locked,#my_photo, #terms_conditions")->select();
            $this->§terms_conditions->required(0);
        }else{
            $this->properties()->find("email,my_lastname, my_firstname")->select();
        }
        $this->methods()->select(0)->find("#update,#create")->select(1);
    }
    
    public function formular($type=""){
        return $this->post_account( $this->id_user );
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
    
    public function post_session(){
        $session = session();
        $this->properties()->find()->select(0);
        $this->methods()->select("0")->find("post_account,post_profile,post_session,logout")->formmethod("get")->select(1);
        $this->ui->head("vos coordonnées","formulaire de session");
        $this->ui->footer("");
        return $this->ui->card($session->show(),"form");
    }
    
    private function form_account(){
        
        $this->properties()->find("#email, my_")->select();
        $this->properties()->find("#my_lastname, #my_firstname ")->required(1);
        $this->§email->type("display");
        $this->methods()->select("0")->find("post_account,post_profile,post_session,logout")->select(1);
        $this->methods()->find("post_profile,post_recover_password,post_session,logout")->formmethod("get")->select("1");
        $this->methods()->find("post_account,post_profile,post_session,post_recover_password,logout")->sort();
        
        $this->ui->head("vos coordonnées","formulaire de contact utilisateur");
        
    }
    
    public function post_account($id=0){
        $session = session();
        $return = "";
        if(!$session->exist()){
            $return .= $this->post_log_in();
        }else{
            if($id==0){
                $this->read($session->id);
            }else{
                $this->read($id);
            }
            $this->form_account();
            if(isset($_POST["my_firstname"])){
                $this->update($_POST);
                $return.=notify("updated ".date("Y-m-d H:i:s"),"success clear");
                $return .= $this->ui->structure();
                $session->user($this);
            }
            
            
            $return .= $this->ui->form();
        }
        $pinsession ="<div id='pinsession'>$session->name</div>";
        return $pinsession.$return; 
    }
    
    private function form_profile(){
        $this->properties()->find("profile_profile")->select();
        $this->methods()->select("0")->find("post_account,post_profile")->formmethod("get")->select("1")->sort();
    }
    
    
    
    public function post_profile(){
        $session = session();
        $return = "";
        if(!$session->exist()){
            $return .= $this->post_log_in();
        }else{
            $this->read($session->id);
            $this->form_profile();
            if(isset($_POST["auth_profile_profile"])){
                $this->update($_POST);
                $this->read($session->id);
                $this->form_profile();
                $return .= $this->ui->structure();
            }
            $session->user($this);
            $profile = $this->§auth_profile->Read();
            $panel=$profile->panel();
            $this->ui->head("votre profile","gestion de vos droits d'utilisateur $profile->profile ");
            $this->ui->footer($panel);
            $return .= $this->ui->form();
        }
        return $return; 
    }
    
    
    public function logout(){
        $session = session();
        if(!$session->exist()){
            $return = $this->post_log_in();
        }else{
            $session->kill();
            $this->val(null);
            $return = $this->post_log_in();
        }
        return $return; 
    }
    
    private function Visitor(){
        $session = session();
        if(! $session->exist()){
            $Visitor = Build($this->db_tablename());
            $Visitor->email =$_POST["email"];
            $Visitor->id_user =null;
            
        }else{
            $Visitor = Build($this->db_tablename());
            //$Visitor->email =$_POST["email"];
            $Visitor->id_user =$session->id;
            
        }
        
        if(!isset($_POST["password"])){
            $_POST["password"]="";
        }else{
        }
        if(!isset($_POST["check_code"])){
            $_POST["check_code"]="";
        }else{
        }
        $Visitor->password = $_POST["password"];
        
        $Visitor->properties()->find()->select()->where();
        $Visitor->read();
        
        $Visitor->exists = false;
        $Visitor->login_granted=false;
        $Visitor->login_code=false;
        
        if($Visitor->iterator()->count()==1){
            $Visitor->val($Visitor->iterator()->Fetch(0));
            $Visitor->exists = true;
            //$Visitor->id_user =0;
            //$Visitor->db_id("0");
            if($Visitor->email == $_POST["email"] and $Visitor->password == $_POST["password"]){
                $Visitor->login_granted=true;
            }
            
            if($Visitor->check_code == $_POST["check_code"]){
                $Visitor->login_code=true;
            }
        }else{
            
        }
        return $Visitor;
    }
    
    private function form_register(){
        $this->properties()->find("#email,#password,  #terms_conditions, #check_code")->select();
        $this->§check_code->type("hidden");
        $this->§post_log_in()->formmethod("get");
        $this->methods()->select("0")->find("#post_register,#post_log_in")->sort()->select("1");
        $this->ui->head("Créer un compte","Formulaire d'inscription.<br/>Merci de bien vouloir renseigner les champs marqués d'un astérisque");
        $this->ui->footer("Lire les <a href='#conditions'>conditions d'utilisation</a>");
    }
    public function post_register(){
        $session = session();
        $return = "";
        
        if(!$session->exist()){
            if(isset($_POST["terms_conditions"])){
                // on garde le mot de passe en mémoire et on cherche l'email dans la db
                $pwd = $_POST["password"];
                $_POST["password"]="";
                $Visitor = $this->Visitor();
                
                if($Visitor->exists){// l'email existe déjà
                    $return .= notify("This email is already registered, the account has been locked","danger");
                    // on bloque le compte et on envoie le code par email
                    $return .=$Visitor->post_send_new_code("register_attempt");
                    return $return;
                    
                }else{// l'email peut être créé
                    // on reprend le mot de passe posté par l'utilisateur
                    $_POST["password"]=$pwd;
                    // on crée un nouveau code d'activation
                    $_POST["check_code"]=$this->random_code();
                    // on crée le nouvel utilisateur
                    $Visitor = $this->create($_POST);
                    // on bloque le compte et on envoie le code par email
                    $return .= $Visitor->post_send_new_code("register_success");
                }
            }else{
                echo notify("please fill in registration form");
                $this->form_register();
                $return .= $this->ui->form();
            }
        }else{
            $return .= $this->post_account();
        }
        return $return;
    }
    
    
    private function form_log_in(){
        $this->properties()->find("#email, #password")->select()->disabled("0");
        $this->§post_register()->formmethod("get");
        $this->§post_recover_password()->formmethod("get");
        $this->methods()->select("0")->find("#post_log_in,#post_register, #post_recover_password")->sort()->select("1");
        $this->ui->head("connexion","formulaire de login utilisateur");
        $this->ui->footer("Lire les <a href='#conditions'>conditions d'utilisation</a>");
    }
    /**
     
     * @return string
     */
    public function post_log_in(){
        $session = session();
        $return = ""; 
        $this->form_log_in();
        
        if(!$session->exist()){ // no active session 
            if(isset($_POST["password"])){// client sent password 
              $Visitor = $this->Visitor(); // catch user in database
              
              if($Visitor->exists){ // corresponding user exists
                  
                  if($Visitor->login_granted){// password and email are ok
                      
                      if($Visitor->auth_locked=="1"){ // user account is locked
                          $return.=$Visitor->post_send_new_code("login_locked") ; // display recovery code formular
                          $return.=notify("Votre compte utilisateur est bloqué.","warning"); // display notification 
                          
                      }else{ // user account is not locked
                          $session->user($Visitor); // set session user
                          $Visitor->check_code = $Visitor->random_code();
                          $Visitor->date_lastlog = date("Y-m-d H:i:s");
                          $Visitor->auth_logins = $Visitor->auth_logins +1;
                          $Visitor->update();
                          $return = $Visitor->post_account(); // display account formular
                          $return.=notify("Bonjour $Visitor->my_firstname,<br/>Bienvenue dans votre espace utilisateur.","success clear");
                          echo "<div id='model-structure'>".$session->show()."</div>";
                      }
                      
                      
                  }else{
                      session(false);
                      $return .= $this->ui->form();
                      $return.=notify("Tentative de login éronée","danger");
                  }
              }else{ // user unknown
                  session(false);
                  $this->ui->footer("Utilisateur inconnu.");
                  $return .= $this->ui->form();
                  $return.=notify("L'utilisateur n'existe pas. Merci de vous identifier à nouveau.");
                  //echo "<div id='structure'>".$this->ui->structure()."</div>";
              }
          }else{// awaiting login info
            //$return.=debug("Merci de vous identifier.");
            $return .= $this->ui->form();
          }
        }else{ // active session
            $this->read($session->id);
            $return .= $this->post_account();
        }
        
        $pinsession ="<div id='pinsession'>$session->name</div>";
        return $pinsession.$return.$this->ui->structure();
    }
    
    private function form_register_check(){
        
        $this->§email->comment("email de vérification")->required(0)->readonly(1);
        $this->§check_code->comment("saisir le code de vérification")->required(0);
        $this->properties()->find("#email, #check_code")->sort()->select();
        $this->§post_log_in()->formmethod("get");
        $this->§post_check_code()->caption("vérifier");
        $this->§post_send_new_code()->caption("nouveau code")->bsclass("btn-light");
        $this->methods()->select("0")->find("#post_check_code,#post_send_new_code,#post_log_in")->sort()->select("1");
        $this->ui->head("Validation","Formulaire de vérification du compte utilisateur.");
        $this->ui->footer("Votre compte est bloqué. Merci de saisir le code de vérification qui vous a été envoyé à votre adresse mail.");
    }
    
    public function post_check_code(){
        $session = session();
        $return = "";
        
        if(!$session->exist()){
            if(isset($_POST["check_code"])){
                $Visitor = $this->Visitor();
                if($Visitor->iterator()->count()==1){// l'utilisateur existe bien
                    $user = $Visitor->iterator()->Fetch(0);
                    $Visitor->val($user);
                    
                    if($Visitor->login_code){
                        $Visitor->check_code = $Visitor->random_code();
                        $Visitor->auth_locked = 0;
                        $Visitor->update();
                        
                        $Visitor->form_log_in();
                        $Visitor->ui->footer("Votre compte est débloqué, merci de vous identifier");
                        $return .= $Visitor->ui->form();
                        
                    }else{
                        $Visitor->email="";
                        $Visitor->password="";
                        
                        $Visitor->form_log_in();
                        $Visitor->ui->footer("Le code est érroné. Votre compte est bloqué");
                        $return .= $Visitor->ui->form();
                    }
                    
                    
                }else{
                    $return .= notify("error");
                    
                }
                
            }else{
                
            }
        }
        return $return;
        
    }
    
    private function form_recover_password($step="step1"){
        
        $step_form=new property();
        $step_form->is_virtual(1)->type("hidden")->name("step");
        $this->properties()->Empty()->Attach($step_form);
        
        if($step=="step1"){
            $this->ui->head("Mot de passe - étape 1","Formulaire de modification du mot de passe.");
            $this->ui->footer("Saisir l'adresse mail à laquelle sera envoyé le code de vérification.");
            $step_form->val("step2");
            
            $this->§email->comment("saisir votre adresse mail")->required(1);
            $this->properties()->find("#email,#step")->sort()->select();
            
            $this->methods()->select("0")->find("#post_recover_password,#post_log_in")->sort()->select();
            $this->§post_log_in()->formmethod("get");
            $this->§post_recover_password()->caption("next");
            
            
            
        }else if($step=="step2"){
            $this->ui->head("Mot de passe - étape 2","Formulaire de modification du mot de passe.");
            $this->ui->footer("Saisir le code de vérification que vous avez reçu par mail.");
            $step_form->val("step3");
            
            $this->§email->comment("")->readonly(1);
            $this->§check_code->comment("saisir le code de vérification")->required(1)->val("");
            $this->properties()->Empty()->find("#email,#check_code,#step")->sort()->select();
            
            $this->methods()->select("0")->find("#post_recover_password,#post_log_in")->sort()->select();
            $this->§post_log_in()->formmethod("get");
            $this->§post_recover_password()->caption("next");
            
            
            
            
        }else if($step=="step3"){
            $this->ui->head("Mot de passe - étape 3","Formulaire de modification du mot de passe.");
            $this->ui->footer("Saisir un nouveau mot de passe.");
            $step_form->val("step4");
            
            $this->§email->comment("")->readonly(1);
            $this->§check_code->comment("code de vérification")->type("hidden");
            $this->§password->comment("Saisir le nouveau mot de passe")->required(1);
            $this->properties()->find("#email,#check_code,#password, #step")->sort()->select();
            
            $this->methods()->select("0")->find("#post_recover_password,#post_log_in")->sort()->select();
            $this->§post_log_in()->formmethod("get")->caption("cancel");
            $this->§post_recover_password()->caption("next");
            
            
            
            
        }else if($step=="step4"){
            $step_form->val("step5");
            $this->§email->comment("")->readonly(1);
            $this->§check_code->comment("")->readonly(1);
            $this->§password->comment("Saisir le nouveau mot de passe")->required(1);
            $this->properties()->find("#email,#password,#check_code, #step")->sort()->select();
            
            $this->methods()->select("0")->find("#post_recover_password,#post_log_in")->sort()->select();
            $this->§post_log_in()->formmethod("get")->caption("cancel");
            $this->§post_recover_password()->caption("next");
            
            $this->ui->head("Mot de passe - étape 4","Formulaire de modification du mot de passe.");
            $this->ui->footer("Saisir le nouveau mot de passe.");
            
            
        }else{
            session(false);
        }
        
        
    }
    
    
    public function post_recover_password(){
        $session = session();
        $return = "";
        
        if(isset($_POST["step"])){// l'utilisateur a posté un formulaire
            
            
            if($_POST["step"]=="step2"){ // envoi du code par email  
                $Visitor = $this->Visitor();
                
                if($session->exist() and $session->id != $Visitor->id_user ){
                    $this->form_account();
                    $this->ui->footer("Vous ne pouvez pas modifier le mot de passe d'un autre utilisateur.".$this->ui->footer());
                    return $this->ui->form();
                }
                
                $this->post_send_new_code("recover_password");
                $this->email = $_POST["email"];
                $this->form_recover_password("step2");
                $return.=$this->ui->form();
                
            }else if($_POST["step"]=="step3"){// étape d'authentification mail 
                
                $Visitor = $this->Visitor();
                if($Visitor->exists){// l'utilisateur existe bien
                    
                    if($session->exist() and $session->id != $Visitor->id_user ){
                        $this->form_account();
                        $this->ui->footer("Vous ne pouvez pas modifier le mot de passe d'un autre utilisateur.".$this->ui->footer());
                        return $this->ui->form();
                    }
                    
                    if($Visitor->login_code ){ // le code saisi est bon
                        $return.=notify("Le code saisi est correct","success");
                        $Visitor->check_code = $Visitor->random_code();
                        $Visitor->auth_locked = 0;
                        $Visitor->update();
                        $Visitor->form_recover_password("step3");
                        $return.=$Visitor->ui->form();
                        
                    }else{ // le code est erroné, on recommence l'étape 2
                        $return.=notify("Le code saisi est incorrect","danger");
                        $Visitor->post_send_new_code("recover_password_retry");
                        //$Visitor->email = $_POST["email"];
                        $Visitor->form_recover_password("step2");
                        $Visitor->ui->footer("Saisir le nouveau code qui vous a été envoyé");
                        $return.=$Visitor->ui->form();
                    }
                }else{ // pas d'utilisateur
                    if(!$session->exist()){
                        $this->form_recover_password();
                        $this->ui->footer("La procédure de changement de mot de passe n'a pas pu aboutir.");
                        $return.=$this->ui->form();
                    }else{
                        $this->form_account();
                        $this->ui->footer("Vous ne pouvez pas modifier le mot de passe d'un autre utilisateur.".$this->ui->footer());
                        $return .= $this->ui->form();
                    }
                    
                }
                
                
            }else if($_POST["step"]=="step4"){
                if(!isset($_POST["password"])){
                    $_POST["password"]="";
                }
                
                $pwd = $_POST["password"];
                $_POST["password"]="";// on efface password dans le POST
                $Visitor = $this->Visitor();
                
                if($Visitor->exists){// l'utilisateur existe bien
                    
                    if(! $Visitor->login_code){// le code n'est pas celui de l'utilisateur
                        
                        $Visitor->auth_locked = 0; // on bloque le login
                        $Visitor->check_code = $Visitor->random_code(); // on change le code
                        $Visitor->update();
                        $return.=notify("Tentative de hacking.","dark");
                        unset($_POST["password"]);
                        $return.=$Visitor->logout();
                        
                        
                    }else{ // le code est celui de l'utilisateur
                        $testpwd = $Visitor->is_pwd_strong($pwd);
                        if($testpwd===true){ // le mot de passe saisi est valable
                            $Visitor->password=$pwd; // on met à jour la valeur du password
                            $Visitor->auth_locked = 1; // on autorise le login
                            $Visitor->check_code = $Visitor->random_code(); // on change le code
                            $Visitor->update();
                            
                            if(!$session->exist()){
                                $Visitor->form_log_in();
                                $Visitor->ui->footer("Le mot de passe a été modifié");
                                $return.=$Visitor->ui->form();
                            }else{// l'utilisateur a ouvert une session
                                $_POST = array();
                                $this->post_account();
                                $this->ui->footer("Le mot de passe a été modifié");
                                $return.=$this->ui->form();
                            }
                            //echo notify("$Visitor->password $Visitor->id_user $Visitor->email ");
                            
                        }else{ // le mot de passe n'est pas valable
                            $msg="";
                            foreach ($testpwd as $v){
                                $msg.="<br/>".$v;
                            }
                            $return.=notify("Le mot de passe est trop faible.","dark");
                            $Visitor->check_code = $Visitor->random_code(); // on change le code
                            $Visitor->update();
                            $Visitor->form_recover_password("step3");
                            $Visitor->ui->footer($msg);
                            $return.=$Visitor->ui->form();
                        }
                    }
                }else{ // l'utilisateur n'existe pas
                    $this->form_recover_password();
                    $return.=$this->ui->form();
                }
            }
        }else{// l'utilisateur n'a pas posté de formulaire
            
            if(!$session->exist()){
                $this->form_recover_password();
                $return.=$this->ui->form();
            }else{
                $_POST["step"]="step2";
                $_POST["email"]=$this->email;
                $return=$this->post_recover_password();
                
            }
        }
        return $return;
    }
    
    private function is_pwd_strong($pwd) {
        $errors=array();
        
        if (strlen($pwd) < 8) {
            $errors[] = "Le mot de passe est trop court - 8 charactères au minimum";
        }
        
        if (!preg_match("#[0-9]+#", $pwd)) {
            $errors[] = "Le mot de passe doit comprendre au moins un chiffre.";
        }
        if (!preg_match('/[A-Z]/', $pwd)) {
            $errors[] = "Le mot de passe doit comprendre au moins une majuscule.";
        }
        if (!preg_match('/[^a-zA-Z\d]/', $pwd)) {
            $errors[] = "Le mot de passe doit comprendre au moins un caractère spécial.";
        }
        if (!preg_match("#[a-zA-Z]+#", $pwd)) {
            $errors[] = "Le mot de passe doit comprendre au moins une lettre.";
        }
        
        if(count($errors)>0 ){
            return $errors;
        }else{
            return true;
        }
    }
    
    private function random_code(){
        return bin2hex(openssl_random_pseudo_bytes(10));
    }
    
    public function post_send_new_code($msg="login_locked"){
        $_POST["check_code"] ="";
        $Visitor = $this->Visitor();
        $return ="";
        
        if($Visitor->exists){
            
            $Visitor->check_code = $Visitor->random_code();
            $Visitor->auth_locked = 1;
            $Visitor->update();
            
            
            $sendmail=true;
            if($msg=="login_locked"){
                $title="authentification";
                $p ="<p>Votre compte utilisateur est bloqué.</p><p>Veuillez trouver ci joint le code de déblocage : </p>";
                $p1="<p></p>";
                $p2="<p>$Visitor->check_code</p>";
                
            }else if($msg=="recover_password" ){
                $title="modification du mot de passe";
                $p ="<p>Votre mot de passe est en cours de modification.</p><p>Veuillez trouver ci joint le code de vérification : </p>";
                $p1="<p></p>";
                $p2="<p>$Visitor->check_code</p>";
                
            }else if($msg=="recover_password_retry"){
                $title="tentative de modification du mot de passe";
                $p ="<p>Votre mot de passe est en cours de modification.</p><p style='color:red;'>Vous n'avez pas saisi le bon code.</p>";
                $p1="<p>Veuillez trouver ci joint le nouveau code de vérification : </p>";
                $p2="<p>$Visitor->check_code</p>";
                
            }else if($msg=="register_attempt"){
                $title="nouvel utilisateur";
                $p ="<p>Quelqu'un essaye de créer un nouveau compte avec votre email.</p><p>Par sécurité, nous avons bloqué votre login.</p>";
                $p1="<p>Un code d'authentification vous sera demandé lors de votre prochaine connexion.</p>";
                $p2="<p></p>";
                
            }else if($msg=="register_success"){
                $title="modification du mot de passe";
                $p ="<p>Félicitations, vous êtes enregistré comme nouvel utilisateur.</p><p>Votre login est néanmoins bloqué.</p>";
                $p1="<p>Veuillez trouver ci joint le code d'authentification qui vous sera demandé lors de votre prochaine connexion.</p>";
                $p2="<p>$Visitor->check_code</p>";
                
            }else{
                $title="authentification";
                $p ="<p>Votre compte utilisateur est bloqué.</p><p>Veuillez trouver ci joint le code de déblocage : </p>";
                $p1="<p></p>";
                $p2="<p>$Visitor->check_code</p>";
                $sendmail=false;
            }
            
            
            $message ="<div><h3>Bonjour $Visitor->email</h3>$p $p1 $p2</div>";
            $Visitor->check_code = "";
            //
            
            // Always set content-type when sending HTML email
            $headers = "MIME-Version: 1.0" . "\r\n";
            $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
            // More headers
            $headers .= 'From: <no.reply@xuserver.net>' . "\r\n";
            //$headers .= 'Cc: myboss@example.com' . "\r\n";
            
            if($sendmail){
                mail($Visitor->email, "login : $title", $message,$headers);
            }else{
                echo debug("<h1>$Visitor->email</h1>" . $message);
            }
            
            $Visitor->form_register_check();
            $return .= $Visitor->ui->form();
        }else{
            $return .= notify("Error cant send code ","danger");
        }
        
        
        return $return;
    }
    
    
    
    


    
    
    
    
}








?>