<?php 
namespace xuserver\modules\boutique;


class customers extends \xuserver\v5\model{
    
    public function popo(){
        return $this->properties()->find("affaire")->val();
    }
    public function form_test(){
        $return ="";
        $this->properties()->find(".affaire,.code,.id,da,hta,.moa_csps,.moa_controleur_technique ")->each(function ($p) use(&$return){
            $return .= "<div>".$p->name() . " (".$p->type().") ". $p->ui->input()."</div>";
        })->select();
        ;
        
        $this->relations()->find("epc")->each(function ($p) use(&$return){
            $return.= "<div>".$p->ui->input()."</div>";
        })
        ;
            $return="<div>$return</div>";
            return $return;
    }
}

?>