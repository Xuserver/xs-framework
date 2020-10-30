<?php 

namespace xuserver\modules\epc;


class epc_projet extends \xuserver\v5\model{
    
    protected function OnBuild(){
        $this->§name->required(1);
    }
    public function formular($type=""){
        $return ="";
        //$projet = $this;
        
        if($type=="create"){
            $this->ui->footer("");
        }else{
            $this->methods()->find()->select(1);
            $this->__listlots();
        }
        $return = $this->ui->form();

        return $return;
    }
    
    public function test($test="no args"){
        echo notify("youy launched $this->name $test");
    }
    
    public function btn_lots(){
        echo notify("you clicked a button ".$this->name);
    }
    
    private function __listlots(){
        $collection = $this->relations()->Fetch("epc_lot");
        $selection = $collection->Read();
        $footer = $selection->ui->list();
        $this->ui->footer($footer);
    }
    
}

?>
