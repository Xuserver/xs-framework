<?php 

namespace xuserver\modules\epc;


class epc_affaire extends \xuserver\v5\model{
    
    public function popo(){
        return $this->properties()->find("affaire")->val();
    }
    public function test(){
        /*
        echo "<h1> find text find etude find</h1>";
        $t="";
        $this->properties->find("text","type")->each(function ($p) use($t){
            $p->val("test");
            echo "<br/>$t".$p->name(). $p->ui->input();
        })->find("etude")->each(function ($p) use($t){
            echo "<br/>$t".$p->name(). $p->ui->input();
        })->find("user")->each(function ($p) use($t){
            echo "<br/>$t".$p->name(). $p->ui->input();
        });
        */
        echo "<h2>FORM GENERATOR #".$this->db_tablename()."(".$this->db_id().")</h2>";
        $t="ima,id,da,descr";
        echo "<h3> find($t) </h3>";
        $this->properties()->find($t)->each(function ($p) use($t){
            echo "<div>".$p->name() . " (".$p->type().") ". $p->ui->input()."</div>";
        });
        
        $this->relations()->find("da")->each(function ($p) use($t){
            echo "<div>".$p->ui->input()."</div>";
        });
        return $this;
    }
}

?>
