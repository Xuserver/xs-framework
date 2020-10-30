<?php 
namespace xuserver\modules\boutique;


class customers extends \xuserver\v5\model{
    
    public function orders($status="Shipped"){
        
        /**
         * Specific method for customers class, inherit model
         * Return $relation with given a status 
         * @var $relation
         */
        $relation = $this->relations()->Fetch("orders")->load();
        $relation->properties()->find("customerName");
        $relation->properties()->find("orderNumber, orderDate, requiredDate, shippedDate, status")->select();
        $relation->properties()->find("#status")->val($status)->where();
        $relation->read();
        return $relation;
    }
    
}

?>