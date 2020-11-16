<?php 
namespace xuserver\modules\auth;

use xuserver\v5\property;
use xuserver\v5\method;

class auth_user extends \xuserver\v5\model{
    var $authorisations=array();
    
    protected function OnBuild(){
        $this->§email->type("email")->caption("e-mail")->comment("Saisir votre identifiant")->required(1);
        $this->§password->type("password")->caption("mot de passe")->comment("Saisir votre mot de passe")->required(1);
        $this->§my_photo->type("file")->caption("photo")->comment("choisir votre photo");
        $this->§terms_conditions->comment("accepter les conditions d'utilisation")->required(1);
        $this->methods()->select(0);
        $this->§btn_logout()->bsClass("btn-warning");
        $this->§btn_recover_password()->bsClass("btn-warning")->caption("password");
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
    
    
    
    public function __session_modules(){
        $session = session();
        if(!$session->exist()){
            return $this->btn_login();
        }else{
            if($session->admin()){
                if($session->logas()){
                }else{
                    
                }
                return $session->modules($this);
            }else{
                return $session->modules();
            }
        }
    }
    public function __session_tables($module){
        $session = session();
        if(!$session->exist()){
            return $this->btn_login();
        }else{
            return $session->tables($module);
        }
        
    }
    
    
    
    public function btn_session(){
        $session = session();
        $this->properties()->find()->select(0);
        $this->methods()->select("0");
        
        
        $this->ui->head("Informations de session",$this->__session_modules());
        $this->ui->footer("");
        return $this->ui->card($session->show(),"form");
    }
    
    public function formular($type=""){
        return $this->_menu( );
    }
    
    public function _menu(){
        $session = session();
        $return = "";
        if(!$session->exist()){
            $return .= $this->btn_login();
        }else{
            $this->properties()->find("#email")->select();
            $this->§email->type("display");
            $this->methods()->select("0");
            //$this->methods()->find("_menu,btn_account,logout")->formmethod("get")->select("1")->sort();
            
            $this->ui->head("Bienvenue dans votre espace personnel",$this->__session_modules());
            
            if($this->my_photo==""){
                $photo = "<p>Vous n'avez pas choisi de photo pour votre compte<p>";
            }else{
                $disk=$this->disk();
                $photo = "<img class='img-fluid img-thumbnail' style='width:150px' src='$disk->url$this->my_photo' />";
            }
            if($this->my_lastname==""){
                $greetings="<p>Vous n'avez pas saisi votre nom et prénom.</p>";
                $FL="Vous";
            }else{
                $greetings="";
                $FL="$this->my_lastname $this->my_firstname, vous ";
            }
            $profname="<p>$FL avez le profil $this->auth_profile_profile</p>";
            
            $this->ui->footer("");
            $return = $this->ui->card("<p><b>Bienvenue</b></p> $profname $photo $greetings<p>Merci de choisir une action dans votre menu</p>","form");
            
        }
        return $return;
    }
    
    private function form_account(){
        
        $this->properties()->find("#email, my_")->select();
        $this->properties()->find("#my_lastname, #my_firstname ")->required(1);
        $this->§email->type("display");
        $this->methods()->select("0")->find("btn_account")->select(1)->caption("Mettre à jour");
        
        $this->ui->head("Formulaire de contact",$this->__session_modules());
        
        //$this->ui->head("Formulaire de contact","@$this->id_user saisir vos information personnelles");
        
    }
    
    
    public function btn_account(){
        $session = session();
        $return = "";
        if(!$session->exist()){
            $return .= $this->btn_login();
        }else{
            $this->form_account();
            if(isset($_POST["my_firstname"])){
                $this->update($_POST);
                $return .= $this->ui->structure();
                if($session->id == $this->id_user){
                    $session->user($this);
                }
            }
            if($session->admin() and $session->logas()){
                $this->§btn_account()->caption("back to admin")->select(1)->formmethod("get")->name("__log_back")->bsClass("danger");
                $this->§update()->caption("mettre à jour")->select(1)->formmethod("post")->bsClass("warning");
            }
            $this->ui->footer("@$this->id_user saisir vos information personnelles");
            
            $return .= $this->ui->form();
        }
        $pinsession ="<div id='pinsession'>$session->name</div>";
        return $pinsession.$return;
    }
    
    public function _log_as(){
        $profile = $this->§auth_profile->Read();
        $profile->log_as();
        return $this->btn_account() . $profile->ui->killForm(). $profile->ui->killList();
        
    }
    
    public function __log_back(){
        $profile = $this->§auth_profile->Read();
        $profile->log_back();
        return $this->btn_account() . $profile->ui->killForm(). $profile->ui->killList();
        
    }
    
    private function form_profile(){
        $this->properties()->find("profile_profile")->select();
        $this->methods()->select("0")->find("btn_account,btn_profile,btn_session")->formmethod("get")->select("1")->sort();
    }
    public function btn_profile(){
        $session = session();
        $return = "";
        if(!$session->exist()){
            $return .= $this->btn_login();
        }else{
            if($session->admin()){
                $this->properties()->find("#auth_profile")->select();
                
                $this->update($_POST);
                if($session->id == $this->id_user){
                    $session->user($this);
                }
                
                $this->methods()->select(0);
                //$this->§_menu()->select(1)->formmethod("get");
                $this->§btn_profile()->select(1)->formmethod("post")->caption("Mettre à jour");
                //$this->§btn_session()->select(1)->formmethod("get");
                $profile = $this->§auth_profile->Read();
                $panel=$profile->__define_profile();
                $panel="<div class='accordion'>
                <div class='collapse' style='overflow-y:scroll;max-height:400px' id='form-session-1' >$panel</div>
                </div>";
                
                $this->ui->head("Informations de profile",$this->__session_modules()."Vous pouvez modifier le profile de cet utilisateur $panel");
                
                /*
                if($session->logas()){
                    $this->§btn_profile()->bsClass("warning");
                    $this->ui->head($this->my_lastname . " " .$this->my_firstname,"Vous pouvez modifier le profile de cet utilisateur $panel");
                    $this->§auth_profile->caption("profile de l'utilisateur");
                }else{
                    $this->ui->head($this->my_lastname . " " .$this->my_firstname,"Attention vous modifiez le profil administrateur $panel" );
                    $this->§auth_profile->caption("mon profile");
                }
                */
                
                $go = xs_link("", $profile->href(), "formular", "Ouvrir la fiche profile","btn btn-link");
                $this->ui->footer("<a class='btn btn-warning' data-toggle='collapse' href='#form-session-1' >details</a> $go");
            }else{
                
                $this->properties()->find("auth_profile_profile")->select();
                $this->§auth_profile_profile->caption("mon profile");
                $this->methods()->select(0);
                //$this->§_menu()->select(1)->formmethod("get");
                
                $this->§btn_profile()->select(1)->formmethod("get")->caption("Mettre à jour");
                //$this->§btn_session()->select(1)->formmethod("get");
                $this->ui->head("Informations de profile",$this->__session_modules()."Vous ne pouvez pas modifier votre profile utilisateur");
                //$this->ui->head("Mon profile","Vous ne pouvez pas modifier votre profile utilisateur");
                $this->ui->footer("");
            }
            $this->methods()->find("_menu,btn_profile,btn_session")->sort();
            
            $return = $this->ui->form();
        }
        return $return; 
    }
    
    /*
    public function btn_define_profile(){
        $session = session();
        $return = "";
        if(!$session->exist()){
            $return .= $this->btn_login();
        }else{
            
            $profile->ui->head("votre profile","gestion de vos droits d'utilisateur $profile->profile ");
            if($session->admin()){
                $profile = $this->§auth_profile->Read();
                
                $panel=$profile->__define_profile();
                $profile->ui->footer($panel);
                
            }else{
                $profile->ui->footer("vos droits sont définis par l'administrateur");
            }
            $return .= $profile->formular();
        }
        return $return; 
    }
    
    /*
    
    public function btn_profile(){
        $session = session();
        $return = "";
        if(!$session->exist()){
            $return .= $this->btn_login();
        }else{
            $this->read($session->id);
            $this->form_profile();
            if(isset($_POST["auth_profile_profile"])){
                $this->update($_POST);
                $this->read($session->id);
                $this->form_profile();
                //$return .= $this->ui->structure();
            }
            
            $profile = $this->§auth_profile->Read();
            
            $this->ui->head("votre profile","gestion de vos droits d'utilisateur $profile->profile ");
            if($session->admin()){
                $session->user($this);
                
                $panel=$profile->__define_profile();
                $this->ui->footer($panel);
            }else{
                $this->ui->footer("vos droits sont définis par l'administrateur");
            }
            
            $return .= $this->ui->form();
        }
        return $return; 
    }
     */
    
    
    public function btn_logout(){
        $session = session();
        if(!$session->exist()){
            $return = $this->btn_login();
        }else{
            $session->kill();
            echo systray("Logout done");
            $this->val(null);
            $return = $this->btn_login();
        }
        return $return; 
    }
    
    
    
    private function form_register(){
        $this->properties()->find("#email,#password,  #terms_conditions, #check_code")->select();
        $this->§check_code->type("hidden");
        $this->§btn_login()->formmethod("get");
        $this->methods()->select("0")->find("#btn_register,#btn_login")->sort()->select("1");
        $this->ui->head("Créer un compte","Formulaire d'inscription.<br/>Merci de bien vouloir renseigner les champs marqués d'un astérisque");
        $this->ui->footer("Lire les <a href='terms.conditions.php' class='xs-link-get' >conditions d'utilisation</a>");
    }
    
    
    public function btn_register(){
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
                    $return .=$Visitor->btn_send_code("register_attempt");
                    return $return;
                    
                }else{// l'email peut être créé
                    // on reprend le mot de passe posté par l'utilisateur
                    $_POST["password"]=$pwd;
                    // on crée un nouveau code d'activation
                    $_POST["check_code"]=$this->__random_code();
                    // on crée le nouvel utilisateur
                    $Visitor = $this->create($_POST);
                    // on bloque le compte et on envoie le code par email
                    $return .= $Visitor->btn_send_code("register_success");
                }
            }else{
                echo notify("please fill in registration form");
                $this->form_register();
                $return .= $this->ui->form();
            }
        }else{
            $return .= $this->btn_account();
        }
        return $return;
    }
    
    
    private function form_log_in(){
        $this->properties()->find("#email, #password")->select()->disabled("0");
        $this->§btn_register()->formmethod("get");
        $this->§btn_recover_password()->formmethod("get");
        $this->methods()->select("0")->find("#btn_login,#btn_register, #btn_recover_password")->sort()->select("1");
        $this->ui->head("connexion","formulaire de login utilisateur");
        $this->ui->footer("Lire les <a href='terms.conditions.php' class='xs-link-get' >conditions d'utilisation</a>");
    }
    /**
     
     * @return string
     */
    public function btn_login(){
        $session = session();
        $return = ""; 
        $this->form_log_in();
        
        if(!$session->exist()){ // no active session 
            if(isset($_POST["password"])){// client sent password 
              $Visitor = $this->Visitor(); // catch user in database
              
              if($Visitor->exists){ // corresponding user exists
                  
                  if($Visitor->login_granted){// password and email are ok
                      
                      if($Visitor->auth_locked=="1"){ // user account is locked
                          $return.=$Visitor->btn_send_code("login_locked") ; // display recovery code formular
                          $return.=notify("Votre compte utilisateur est bloqué.","warning"); // display notification 
                          
                      }else{ // user account is not locked
                          $session->user($Visitor); // set session user
                          $Visitor->check_code = $Visitor->__random_code();
                          $Visitor->date_lastlog = date("Y-m-d H:i:s");
                          $Visitor->auth_logins = $Visitor->auth_logins +1;
                          $Visitor->update();
                          $return = $Visitor->_menu(); // display account formular
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
            $return .= $this->_menu();
        }
        
        $pinsession ="<div id='pinsession'>$session->name</div>";
        return $pinsession.$return.$this->ui->structure();
    }
    
    
    
    private function form_check_code(){
        
        $this->§email->comment("email de vérification")->required(0)->readonly(1);
        $this->§check_code->comment("saisir le code de vérification")->required(0);
        $this->properties()->find("#email, #check_code")->sort()->select();
        $this->§btn_login()->formmethod("get");
        $this->§btn_check_code()->caption("vérifier");
        $this->§btn_send_code()->caption("renvoyer le code")->bsclass("btn-light");
        $this->methods()->select("0")->find("#btn_check_code,#btn_send_code,#btn_login")->sort()->select("1");
        $this->ui->head("Validation","Formulaire de vérification du compte utilisateur.");
        $this->ui->footer("Votre compte est bloqué. Merci de saisir le code de vérification qui vous a été envoyé à votre adresse mail.");
    }
    
    
    /**
     * 
     * @return string
     */
    public function btn_check_code(){
        $session = session();
        $return = "";
        
        if(!$session->exist()){
            if(isset($_POST["check_code"])){
                $Visitor = $this->Visitor();
                if($Visitor->iterator()->count()==1){// l'utilisateur existe bien
                    $user = $Visitor->iterator()->Fetch(0);
                    $Visitor->val($user);
                    
                    if($Visitor->login_code){
                        $Visitor->check_code = $Visitor->__random_code();
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
                echo debug("probleme ?");
            }
        }
        return $return;
        
    }
    
    /**
     * Renvoyer un nouveau code d'identification sur votre adresse mail
     */
    public function btn_send_code($msg="login_locked"){
        $_POST["check_code"] ="";
        $Visitor = $this->Visitor();
        $return ="";
        
        if($Visitor->exists){
            
            $Visitor->check_code = $Visitor->__random_code();
            $Visitor->auth_locked = 1;
            $Visitor->update();
            
            
            $sendmail=true;
            if($msg=="login_locked"){
                $title="authentification";
                $p ="<p>Votre compte utilisateur est bloqué.</p>";
                $p1="<p>Veuillez trouver ci joint le code de déblocage : </p>";
                $p2="<p>$Visitor->check_code</p>";
                
            }else if($msg=="recover_password" ){
                $title="modification du mot de passe";
                $p ="<p>Votre mot de passe est en cours de modification.</p>";
                $p1="<p>Veuillez trouver ci joint le code de vérification : </p>";
                $p2="<p>$Visitor->check_code</p>";
                
            }else if($msg=="recover_password_retry"){
                $title="tentative de modification du mot de passe";
                $p ="<p>Votre mot de passe est en cours de modification.</p><p style='color:red;'>Vous n'avez pas saisi le bon code.</p>";
                $p1="<p>Veuillez trouver ci joint le nouveau code de vérification : </p>";
                $p2="<p>$Visitor->check_code</p>";
                
            }else if($msg=="register_attempt"){
                $title="nouvel utilisateur";
                $p ="<p>Quelqu'un essaye de créer un nouveau compte avec votre adresse mail.</p>";
                $p1="<p>Par sécurité, nous avons bloqué votre compte.</p>";
                $p2="<p>Un code d'authentification vous sera demandé lors de votre prochaine connexion.</p>";
                
            }else if($msg=="register_success"){
                $title="Bienvenue sur le service";
                $p ="<p>Félicitations, vous êtes enregistré comme nouvel utilisateur.</p><p>Votre login est néanmoins bloqué.</p>";
                $p1="<p>Veuillez trouver ci joint le code d'authentification qui vous sera demandé lors de votre prochaine connexion.</p>";
                $p2="<p>$Visitor->check_code</p>";
                
            }else{
                $title="authentification";
                $p ="<p>Votre compte utilisateur est bloqué.</p>";
                $p1="<p>Veuillez trouver ci joint le code de déblocage : </p>";
                $p2="<p>$Visitor->check_code</p>";
                $sendmail=false;
            }
            
            $Visitor->check_code = "";
            
            $to = $Visitor->email;
            $subject = $title;
            $from = 'webmaster@xuserver.net';
             
            // To send HTML mail, the Content-type header must be set
            $headers  = 'MIME-Version: 1.0' . "\r\n";
            $headers .= 'Content-type: text/html; charset=utf-8' . "\r\n";
            $headers .= 'From: '.$from."\r\n".'Reply-To: '.$from."\r\n" . 'X-Mailer: PHP/' . phpversion();
            // Compose a simple HTML email message
            
            $messageFooter = "<div><small>xuserver.net ".date ("M/Y")."</small></div>";
            $message ="<html><body><img src='https://xuserver.net/xs-framework/xuserver-logo.png' /><h3>Bonjour $Visitor->email</h3>$p $p1 $p2 $messageFooter</body></html>";

            if($sendmail){                                                     
                //echo debug("<h1>$Visitor->email</h1>" . $message);
                $testsent = mail($to, $subject, $message, $headers);
                if(! $testsent){
                    echo systray("SYS : error mail", "danger");
                }else{
                    
                }                
            }else{
                
            }
            
            $Visitor->form_check_code();
            $return .= $Visitor->ui->form();
        }else{
            $return .= notify("Error cant send code ","danger");
        }
        
        
        return $return;
    }
    
    
    /**
     * paramétrage du formulaire de récupération du mot de passe en fonction de l'étape de la procédure  
     * @param string $step
     */
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
            
            $this->methods()->select("0")->find("#btn_recover_password,#btn_login")->sort()->select();
            $this->§btn_login()->formmethod("get")->caption("cancel");
            $this->§btn_recover_password()->caption("next >")->bsClass("btn-primary");
            
            
            
        }else if($step=="step2"){
            $this->ui->head("Mot de passe - étape 2","Formulaire de modification du mot de passe.");
            $this->ui->footer("Saisir le code de vérification que vous avez reçu par mail.");
            $step_form->val("step3");
            
            $this->§email->comment("")->readonly(1);
            $this->§check_code->comment("saisir le code de vérification")->required(1)->val("");
            $this->properties()->Empty()->find("#email,#check_code,#step")->sort()->select();
            
            $this->methods()->select("0")->find("#btn_recover_password,#btn_login")->sort()->select();
            $this->§btn_login()->formmethod("get")->caption("cancel");
            $this->§btn_recover_password()->caption("next >")->bsClass("btn-primary");
            
            
            
            
        }else if($step=="step3"){
            $this->ui->head("Mot de passe - étape 3","Formulaire de modification du mot de passe.");
            $this->ui->footer("Saisir un nouveau mot de passe.");
            $step_form->val("step4");
            
            $this->§email->comment("")->readonly(1);
            $this->§check_code->comment("code de vérification")->type("hidden");
            $this->§password->comment("Saisir le nouveau mot de passe")->required(1);
            $this->properties()->find("#email,#check_code,#password, #step")->sort()->select();
            
            $this->methods()->select("0")->find("#btn_recover_password,#btn_login")->sort()->select();
            $this->§btn_login()->formmethod("get")->caption("cancel");
            $this->§btn_recover_password()->caption("next >")->bsClass("btn-primary");
            
            
            
            
        }else if($step=="step4"){
            $step_form->val("step5");
            $this->§email->comment("")->readonly(1);
            $this->§check_code->comment("")->readonly(1);
            $this->§password->comment("Saisir le nouveau mot de passe")->required(1);
            $this->properties()->find("#email,#password,#check_code, #step")->sort()->select();
            
            $this->methods()->select("0")->find("#btn_recover_password,#btn_login")->sort()->select();
            $this->§btn_login()->formmethod("get")->caption("cancel");
            $this->§btn_recover_password()->caption("next >")->bsClass("btn-primary");
            
            $this->ui->head("Mot de passe - étape 4","Formulaire de modification du mot de passe.");
            $this->ui->footer("Saisir le nouveau mot de passe.");
            
            
        }else{
            session(false);
        }
        
        
    }
    
    
    /**
     * procédure de récupération du mot de passe
     * @return string
     */
    public function btn_recover_password(){
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
                
                $this->btn_send_code("recover_password");
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
                        $Visitor->check_code = $Visitor->__random_code();
                        $Visitor->auth_locked = 0;
                        $Visitor->update();
                        $Visitor->form_recover_password("step3");
                        $return.=$Visitor->ui->form();
                        
                    }else{ // le code est erroné, on recommence l'étape 2
                        $return.=notify("Le code saisi est incorrect","danger");
                        $Visitor->btn_send_code("recover_password_retry");
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
                        $Visitor->check_code = $Visitor->__random_code(); // on change le code
                        $Visitor->update();
                        $return.=notify("Tentative de hacking.","dark");
                        unset($_POST["password"]);
                        $return.=$Visitor->btn_logout();
                        
                        
                    }else{ // le code est celui de l'utilisateur
                        $testpwd = $Visitor->is_pwd_strong($pwd);
                        if($testpwd===true){ // le mot de passe saisi est valable
                            $Visitor->password=$pwd; // on met à jour la valeur du password
                            $Visitor->auth_locked = 1; // on n'autorise pas le login
                            $Visitor->check_code = $Visitor->__random_code(); // on change le code
                            $Visitor->update();
                            
                            if(!$session->exist()){
                                $Visitor->form_log_in();
                                $Visitor->ui->footer("Le mot de passe a été modifié");
                                $return.=$Visitor->ui->form();
                            }else{// l'utilisateur a ouvert une session
                                $_POST = array();
                                $this->btn_account();
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
                            $Visitor->check_code = $Visitor->__random_code(); // on change le code
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
                $return=$this->btn_recover_password();
                
            }
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
    
    /**
     * vérifie la force du mot de passe choisi
     * @param string $pwd
     * @return string[]|boolean
     */
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
    
    /**
     * génère un code aléatoire
     * @return string
     */
    private function __random_code(){
        return bin2hex(openssl_random_pseudo_bytes(10));
    }
    


    
    
    
    
}








?>