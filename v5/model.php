<?php 

namespace xuserver\v5;

use PDO;

/**
 * database encapsulation class 
 * analyse relationships between tables reading mysql or postgree  INFORMATION_SCHEMA
 * @todo extend PDO
 * @author gael jaunin
 *
 */
class database{
    
    public $pdo;
    public $FK;
    
    public $aKEY = array("ID");
    public $aDATE = array("DATE", "TIMESTAMP");
    public $aXHTML = array("TEXT", "MEDIUMTEXT", "LONGTEXT", "TINYTEXT");
    public $aBLOB = array("BLOB", "MEDIUMBLOB", "LONGBLOB");
    public $aCAMERA = array("TINYTEXT");
    public $aNUMBER = array("NUMERIC", "DECIMAL", "FLOAT", "DOUBLE", "SMALLINT");
    public $aBOOL = array("TINYINT");
    public $aTEXT = array("VARCHAR", "MEDIUMINT");
    public $aOBJ = array("INTEGER", "INT", "BIGINT");
    public $aENUM = array("ENUM");
    
    public function __construct(\PDO $pdo=null) {
        $this->pdo=$pdo;
        $this->loadForeignKeys();
    }
    
    /**
     * read database and fill relatioships array
     * @return array
     */
    public function loadForeignKeys(){
        $sqlForeigkeys = "SELECT TABLE_NAME, COLUMN_NAME, REFERENCED_TABLE_NAME,REFERENCED_COLUMN_NAME FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE WHERE REFERENCED_TABLE_SCHEMA = '".XUSERVER_DB_NAME."'  ";
        $queryDescribe = $this->query($sqlForeigkeys);
        $this->FK = array();
        foreach ($queryDescribe as $row) {
            $this->FK[$row["TABLE_NAME"]."_".$row["COLUMN_NAME"]]=array("table"=>$row["TABLE_NAME"],"index"=>$row["COLUMN_NAME"] ,"db_tablename" => $row["REFERENCED_TABLE_NAME"], "db_index"=>$row["REFERENCED_COLUMN_NAME"]);
        }
        return $this->FK;
    }
    public function ForeignKeys() {
        return $this->FK;
    }
    
    /**
     * determine the default html input type  for a given property
     * @param property $property
     * @param string $db_type
     */
    public function dbtype(property &$property, string $db_type){
        $out=array();
        $found="";
        if(preg_match('/\((.*?)\)/', $db_type, $out)){
            $db_type = strtoupper(substr($db_type, 0, strpos($db_type, '(')));
            $found = $out[1];
            if (is_numeric($found)) {
                $property->attr("db_typelen",$found);
            }else {
            }
        }else{
            $db_type = strtoupper($db_type);
        }
        
        if (in_array($db_type, $this->aKEY)) {
            $property->type("id");
            
        }else if (in_array($db_type, $this->aBOOL)) {
            $property->type ("checkbox");
            
        }else if (in_array($db_type, $this->aXHTML)) {
            $property->type("xhtml");
            
        }else if (in_array($db_type, $this->aNUMBER)) {
            $property->type("number");
            
        }else if (in_array($db_type, $this->aDATE)) {
            $property->type("date");
            
        }else if (in_array($db_type, $this->aTEXT)) {
            $property->type("text");
            
        }else if (in_array($db_type, $this->aENUM)) {
            $property->values($found);
            
        }else if (in_array($db_type, $this->aBLOB)) {
            $property->type( "file");
        }else if (in_array($db_type, $this->aCAMERA)) {
            $property->type("camera");
        }else if ($db_type == "VIRTUAL") {
            $property->type("virtual");
            
        }else if ($db_type == "METHOD") {
            $property->type("method");
            
        }else {
            $property->type("text");
        }
    }
    
    /**
     * overrides pdo query method
     * @param string $stmt
     * @param string $stmt
     * @return \PDO 
     */
    public function query(string $stmt){
        return $this->pdo->query($stmt);
    }
    
    /**
     * get database driver name (mysql or postgree)
     */
    public function driver() {
        $driver = $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        return $driver;
    }
    
    
}


/**
 * sql query builder
 * allows to automatically build sql queries according model structure 
 * 
 * @author gael jaunin
 *
 */
class sql{
    private $parent;
    private $_sqlstmt;
    private $_sqltype="SELECT";
    private $_SELECT;
    private $_FROM;
    private $_JOIN;
    private $_WHERE;
    private $_ORDERBY;
    private $_LIMIT;
    private $_OFFSET;
    
    private $_SET;
    
    public function __construct(model&$model) {
        $this->parent=$model;
        $this->reset();
    }
    
    /**
     * reset sql part
     * chainable
     * @return \xuserver\v5\sql
     */
    public function reset(){
        
        $this->_SELECT=array();
        $this->_FROM="";
        $this->_JOIN=array();
        $this->_WHERE=array();
        $this->_ORDERBY=array();
        $this->_LIMIT="";
        $this->_OFFSET="";
        
        $this->_SET=array();
        return $this;
    }
    
    /**
     * select sql clause     
     * 
     * @param string $list 
     *  __AUTO__ : in automatic mode the field list is created using model properties
     *  empty : reset the field list 
     *  string : comma separated list of fields
     * @return \xuserver\v5\sql
     */
    public function select( $list="__AUTO__") {
        $this->_sqltype="SELECT";
        
        if($list=="__AUTO__"){
            $this->_SELECT=array();
            $this->_JOIN=array();
            $self=$this;
            $this->parent->properties()->each(function( property $p) use(&$self){
                $self->_SELECT[]=$self->parent->db_tablename() . "." . $p->name();
                if($p->type()=="fk"){
                    $fk= $p->load();
                    $fk->properties()->find("text","type")->each(function( property $pfk) use(&$self,&$fk){
                        $self->_SELECT[]=$pfk->parent->db_tablename() . "." . $pfk->name();
                    });
                    if($self->parent->db_tablename()== $fk->db_tablename()){
                        
                    }else{
                        $self->_JOIN[]= " LEFT JOIN ".$fk->db_tablename()." ON ".$fk->db_tablename().".".$fk->db_index() ."=".$p->db_tablename().".".$p->db_index()." ";
                    }
                }
            })
            ;
            $this->from();
            $this->_LIMIT="";
            $this->_OFFSET="";
            
        }else if($list==""){
            $this->_SELECT=array();
        }else{
            $this->_SELECT=explode(",", $list);
        }
        return $this;
        
    }
    /**
     * from sql clause
     * @param string $list
     *  __AUTO__ : in automatic mode the field list is created using model properties
     *  string : table name
     * @return \xuserver\v5\sql
     */
    public function from( $list="__AUTO__") {
        if($list=="__AUTO__"){
            $this->_FROM=$this->parent->db_tablename();
        }else{
            $this->_FROM="";
        }
        return $this;
    }
    
    /**
     * where sql clause
     * @param string $list
     *  __AUTO__ : in automatic mode the where clause is created using the parent model 
     *      if model is an instance the automatic mode uses the primary key
     *      if model is a selection of instances the automatic mode uses property values
     *  string : user defined where clause
     *  emtpy : reset the where clause
     * @return \xuserver\v5\sql
     */
    
    public function where($list="__AUTO__") {
        if($list=="__AUTO__"){
            $this->_WHERE=array();
            $self=$this;
            $case = $self->parent->state() ;
            
            if($this->_sqltype=="SELECT" ){
                
                if($case =="is_model" or $case =="is_selection" ){
                    $this->parent->properties()->each(function( property $prop) use(&$self){
                        $value = $prop->val();
                        if($prop->val()!=""){
                            if($prop->type()=="checkbox" or $prop->type()=="number" or $prop->type()=="id"){
                                $this->_WHERE[] = $this->parent->db_tablename().".". $prop->name()."=\"$value\"";
                            }else{
                                $this->_WHERE[] = $this->parent->db_tablename().".". $prop->name()." LIKE \"%$value%\"";
                                
                            }
                            
                        }
                    });
                        
                }else if($case =="is_instance"){
                    $this->_WHERE[] = $this->parent->db_tablename().".".$this->parent->db_index()."=".$this->parent->db_id();
                }else{
                    
                }
                    
            }else if($this->_sqltype=="UPDATE" ){
                
                if($case =="is_model" or $case =="is_selection"  ){
                    $this->parent->properties()->each(function( property $prop) use(&$self){
                        $value = $prop->val();
                        if($prop->val()!=""){
                            if($prop->type()=="checkbox" or $prop->type()=="number" or $prop->type()=="id"){
                                $this->_WHERE[] = $this->parent->db_tablename().".". $prop->name()."=\"$value\"";
                            }else{
                                $this->_WHERE[] = $this->parent->db_tablename().".". $prop->name()." LIKE \"%$value%\"";
                                
                            }
                        }
                    });

                }else if($case =="is_instance"){
                    $this->_WHERE[] = $this->parent->db_tablename().".".$this->parent->db_index()."=".$this->parent->db_id();
                }else{
                    
                }
            }else{
                
            }
        }else if($list==""){
            $this->_WHERE=array();
        }else{
            $this->_WHERE=explode(",", $list);
        }
        return $this;
    }
    
    /*
    public function and($list="__AUTO__"){
        $this->_WHERE[] = $this->parent->db_tablename().".".$this->parent->db_index()."=".$this->parent->db_id();
        return $this;
    }
    */
    public function update( $list="__AUTO__") {
        /*
         UPDATE table_name
         SET column1 = value1, column2 = value2, ...
         */
        
        $this->_sqltype="UPDATE";
        if($list=="__AUTO__"){
            $this->_SET=array();
            $self=$this;
            $this->parent->properties()->each(function( property $p) use(&$self){
                $val = $p->val();
                $self->_SET[]=$self->parent->db_tablename() . "." . $p->name() ." = '$val'" ;//= "'".$p->val()."'";
                
                
            })
            ;
                $this->from();
                $this->_LIMIT="";
                $this->_OFFSET="";
        }else if($list==""){
            
        }else{
            $this->_SET=explode(",", $list);
        }
        return $this;
    }
    
    
    /**
     * offset sql clause
     * @param string $offset
     * @return \xuserver\v5\sql
     */
    public function offset($offset="0") {
        if($offset=="0"){
            $this->_OFFSET="";
        }else{
            $this->_OFFSET=" OFFSET $offset ";
        }
        return $this;
    }
    /**
     * limit sql clause
     * @param string $offset
     * @return \xuserver\v5\sql
     */
    public function limit($limit="") {
        if($limit==""){
            $this->_LIMIT="";
        }else{
            $this->_LIMIT="LIMIT $limit ";
        }
        return $this;
    }
    
    
    
    
    
    
    
    
    
    
    /**
     * sql statement to read an instance of parent model  
     * @return string
     */
    public function statement_read_instance(){
        $sql = $this->select()->where($this->parent->db_tablename().".".$this->parent->db_index()."=".$this->parent->db_id() )->statement();
        return $sql;
    }
    /**
     * sql statement to list model as a selection of instances that match certain where clause
     * @return string
     */
    public function statement_read_selection(){
        $sql = $this->select()->where()->statement();
        return $sql;
    }
    
    /**
     * sql statement to update an instance of parent model
     * @return string
     */
    public function statement_update(){
        $sql = $this->update()->where( )->statement();
        return $sql;
    }
    
    /**
     * build the sql statement 
     * @return string
     */
    public function statement(){
        
        if($this->_sqltype=="SELECT"){
            $_SELECT = implode(", ", $this->_SELECT);
            $_JOIN = implode(" ", $this->_JOIN);
            if(count($this->_WHERE)>0){
                $_WHERE = " WHERE ".implode(" AND ", $this->_WHERE);
            }else{
                $_WHERE ="";
            }
            
            if(count($this->_ORDERBY)>0){
                $_ORDERBY = " ORDER BY ".implode(",", $this->_ORDERBY);
            }else{
                $_ORDERBY ="";
            }
            $this->_sqlstmt="SELECT $_SELECT FROM $this->_FROM $_JOIN $_WHERE $_ORDERBY $this->_LIMIT $this->_OFFSET";
        }else if($this->_sqltype=="UPDATE"){
            
            $_SET = implode(", ", $this->_SET);
            
            if(count($this->_WHERE)>0){
                $_WHERE = " WHERE ".implode(" AND ", $this->_WHERE);
            }else{
                $_WHERE ="";
            }
            
            $this->_sqlstmt="UPDATE $this->_FROM SET $_SET $_WHERE ";
        }
        
        return $this->_sqlstmt;
    }
    
    
    /**
     * generate html table using the actual sql statement, for debug purpose only
     * @deprecated
     */
    public function ui_table(){
        $qry = $this->parent->db()->query($this->_sqlstmt);
        $html = "<table>";
        $cnt=0;
        foreach($qry as $row) {
            if($cnt<1){
                $html .= "<tr>";
                foreach($row as $column => $value) {
                    $html.="<th>".$column."</th>";
                }
                $html .= "</tr>";
            }
            $html .= "<tr>";
            $cnt++;
            foreach($row as $column => $value) {
                $html.="<td>".$value."</td>";
            }
            $html .= "</tr>";
        }
        $html.="</table>";
        return $html;
    }
    
}


/**
 * ORM Model class
 *    
 * @author gael jaunin
 *
 */
class model extends iteratorItem{
    
    /**
     * sql class attached to the model
     * @todo rename to private var
     * @var sql
     */
    public $sql;
    /**
     * user interface attached to the model  
     * @var model_ui
     */
    public $ui;
    
    /**
     * PDO attached to the model 
     * @todo remove, using only db object
     * @var PDO
     */
    protected $pdo;
    
    /**
     * database class attached to the model 
     * @var database
     */
    protected $db;
    
    /**
     * name of the table that store model instances in database  
     * @var string
     */
    private $db_tablename="";
    /**
     * name of the primary key that identifies instances in the table
     * @var string
     */
    protected $db_index ="";
    /**
     * value of the primary key that identifies instances using primary key 
     * @var string
     */
    protected $db_id =0;
    
    /**
     * iterator that stores model properties
     * @var properties
     */
    private $_properties ;
    /**
     * iterator that stores model relations
     * @var relations
     */
    private $_relations;
    /**
     * iterator that stores model instances
     * @var mixed (model or stdClass) 
     */
    private $_iterator ;
    
    /**
     * state of the model is 
     * - is_virgin : the model doesnt map any table
     * - is_model : the model only maps a table  
     * - is_instance : the model maps a table and is an instance 
     * - is_selection : the model maps a table and is set of instances
     * @var string
     */
    private $_state="is_virgin"; 
    
    /**
     * create model on database
     */
    public function __construct(database $db=null) {
        if(is_null($db)){
            global $XUSERVER_DB;
            $this->pdo = &$XUSERVER_DB->pdo;
            $this->db = &$XUSERVER_DB;
        }else{
            $this->db = $db;
        }
    }
    
    /**
     * use overloading on model class to set instance property values using direct syntax : 
     * ex : change car color 
     * $car->color = 'blue' instead of 
     * $car->properties()->find('color')->val('blue')
     *  
     */
    public function __set($name, $value){
        //echo "Setting '$name' to '$value'\n";
        $this->_properties->byName($name)->val($value);
    }
    
    /*
    public function __get($name){
        echo "Getting  \n"; 
        if(is_null($name)){
            return false;
        }else{
            return $this->_properties->byName($name)->val();
        }
    }
    */
    
    /**
     * @todo reflechir a son emploi
     * @return \xuserver\v5\iterator
     */
    public function __invoke(){
        return $this->_iterator;
    }
    
    /**
     * init model, reseting all its internal properties
     */
    public function init(){
        $this->db_tablename="";
        $this->db_index="";
        $this->db_index="";
        $this->db_id=0;
        $this->ui=new model_ui($this);
        $this->sql =  new sql($this);
        $this->_properties =  new properties($this);
        $this->_relations = new relations($this);
        $this->_iterator = new iterator($this);
        $this->state("is_virgin");
        return $this;
    }
    
    /**
     * build model properties, relations and methods
     * @todo implement methods
     * @todo implement default values
     * @todo use iteratorItem props (_name ...) ?
     * @param string $db_tablename
     */
    public function build($db_tablename="__AUTO__"){
        // destroy current model definition;
        $this->init();
        
        // build model ;
        if($db_tablename=="__AUTO__"){
            $temp= explode('\\', get_class($this));
            $this->db_tablename= array_pop($temp);
        }else{
            $this->db_tablename=$db_tablename;
        }
        /*
        $this->_type="table";
        $this->_name=$this->db_tablename;
        $this->_value="0" // name of the first text field ?;
        */
        
        // build mysql model ;
        $driver=$this->db->driver();
        if($driver == 'mysql'){
            /**
             * PROPERTIES
             */
            $sqlDescribe ="SHOW FULL COLUMNS FROM $this->db_tablename ";
            $queryProperties = $this->db->query($sqlDescribe);
            foreach ($queryProperties as $row) {
                $property_key = $row["Key"];
                $property_name = $row["Field"];
                $db_type = $row["Type"];
                
                $property = new property();
                $this->db->dbtype($property,$db_type);
                $property->name($property_name);
                if ($property_key == "PRI") {
                    if (strpos($row["Extra"], "auto_increment") !== false) {
                        $this->db_index = $property_name;
                        $property->is_key(1);
                    }
                }else{
                }
                
                $fkkey=$this->db_tablename."_".$property_name;
                if (isset($this->db->FK[$fkkey])) {
                    $elt=$this->db->FK[$fkkey];
                    $property->is_foreignkey($elt["db_tablename"],$elt["db_index"]);
                }
                
                if (isset($row["Comment"])) {
                    $property->comment($row["Comment"]);
                }
                if (isset($row["Default"])) {
                    //$property->val($row["Default"]);
                }
                $this->properties()->attach($property);
                
            }
            /**
             * RELATIONS
             */
            foreach ($this->db->FK as $row) {
                if($row["db_tablename"]==$this->db_tablename()){
                    $relation = new relation();
                    $relation->name($row["table"]);
                    $relation->type("relation");
                    $relation->val($row["index"]);
                    $this->relations()->attach($relation);
                }
                //echo "<br/><span style='color:$col'>".$this->db_tablename() . " </span> ".$row["db_tablename"]. "  <span style='color:$col'>".$row["table"]. " </span>.<span style='color:$col'>".$row["db_index"]. " </span> ";
            }

            $this->state("is_model");
            
        }else if($driver == 'pgsql'){
            //$mysql_tablestructure = $this->pdo->query("SELECT column_name FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'some_table'");
        }
        
        return $this;
    }
    
    
    
    /**
     * read model instance from database
     * @param number $id
     * @return \xuserver\v5\model
     */
    public function read($id="__NULL__"){
        if ($this->state() == "is_virgin") {
            // model not built => cant load data
            return $this;
        }else{
            // model built => can load data
            
        }
        
        // if $id given => build sql_read and instanciate object
        if ( is_int($id) ) {
            $this->db_id = $id;
            $sql_read=$this->sql()->statement_read_instance();
            try {
                $instance = $this->db->query($sql_read)->fetchObject();
                $this->state("is_instance");
                $this->val($instance);
                $this->iterator()->init();
                $this->iterator()->attach($this);
            }catch(\PDOException $exception) {
                $this->state("is_model");
                echo "<div><h1>ERROR model::read()<h1></div><div>$exception</div>";
                //echo "<div>SQL $sql_read</div>";
            }
        }else if ( $id=="__NULL__" ) {
            $sql_read=$this->sql()->statement_read_selection();
            
            try {
                $qry = $this->db->query($sql_read);
                $selection = $qry->fetchAll(\PDO::FETCH_OBJ);
                $this->state("is_selection");
                $this->iterator()->init();
                foreach ($selection as $instance) {
                    $this->iterator()->attach($instance);
                }
                
            }catch(\PDOException $exception) {
                $this->state("is_model");
                echo "<div><h1>ERROR model::read()<h1></div><div>$exception</div>";
                //echo "<div>SQL $sql_read</div>";
            }
        }else{
            
        }
        
        return $this;
    }
    
    /**
     * update model instance or selection
     * @param mixed $values
     */
    public function update($values=null){
        $sql_update=$this->sql()->statement_update();
        
        if(is_null($values)){
            echo $sql_update;
        }else{
            
        }
        
        return $this;
    }
    
    /**
     * set model current _state
     * @param string $set
     * @return \xuserver\v5\model|string
     */
    public function state($set="__NULL__"){
        if($set!="__NULL__"){
            $this->_state=$set;
            return $this;
        }else{
            return $this->_state;
        }
    }
    
    /**
     * 
     * @return \xuserver\v5\sql
     */
    public function sql(){
        return $this->sql;
    }
    
    /**
     * 
     * @return \xuserver\v5\iterator
     */
    public function iterator(){
        return $this->_iterator;
    }
    
    /**
     * 
     * @return \xuserver\v5\properties
     */
    public function properties(){
        return $this->_properties;
    }
    /**
     *
     * @return \xuserver\v5\relations
     */
    public function relations(){
        return $this->_relations;
    }
    
    /**
     * 
     * @return \xuserver\v5\database
     */
    public function db(){
        return $this->db;
    }
    /**
     * 
     * @return string|mixed
     */
    public function db_tablename(){
        return $this->db_tablename;
    }
    
    public function db_index(){
        return $this->db_index;
    }
    /**
     * return db id value
     */
    public function db_id($set=""){
        
        if($set!=""){
            $this->db_id=$set;
            return $this;
        }else{
            return $this->db_id;
        }
        
    }
    
      
    public function val($values="__NULL__"){
        if(is_null($values) or $values=="__NULL__"){
            $this->properties()->each(function(property $prop){
                $prop->val(null);
            });
        }else if(is_object($values)){
            
            $this->properties()->each(function(property $prop)use(&$values){
                $name=$prop->name();
                if(isset( $values->$name )){
                    $prop->val( $values->$name );
                    if($prop->is_key() and  $prop->type()=="id"){
                        $prop->parent->db_id($values->$name);
                    }
                }
            });
        }else if(is_array($values)){
            
            if(count($values) > 0){
                $defaultCheckBox="0";
            }else{
                $defaultCheckBox="";
            }
            
            $this->properties()->each(function(property $prop)use(&$values,$defaultCheckBox){
                $name=$prop->name();
                if($prop->type()=="checkbox"){
                    if(isset($values[$name])){
                        $prop->val( $values[$name]);
                    }else{
                        $prop->val($defaultCheckBox);
                    }
                }else{
                    if(isset($values[$name])){
                        $prop->val( $values[$name]);
                    }
                }
            });
        }else{
            $this->properties()->each(function(property $prop)use(&$values){
                $prop->val($values);
            });
        }
        
        return $this;
    }

}



class iterator{
    private $list=array();
    private $selection=array();
    private $found=array();
    protected $parent;
    private $_itemType="FETCH_ITERATORITEM";
    
    public function __construct(model &$model){
        $this->parent=$model;
        
    }
    function __call($method, $arguments) {
        
        //echo "Call method ".__CLASS__."->$method(".implode(', ',$arguments) .")";
        
        //$this->val($instance);
        if($this->_itemType=="FETCH_ITERATORITEM"){
            $this->each(function(iteratorItem $item)use(&$method,&$arguments){
                call_user_func_array(array($item, $method), $arguments);
            });
        }else if($this->_itemType=="FETCH_OBJ"){
            $iterator=$this;
            $this->each(function($item)use(&$method,&$arguments,&$iterator){
                
                //print_r($item);
                $iterator->parent->val($item);
                
                //echo "<br />ICI $temp ".$item->affaire ;
                call_user_func_array(array($iterator->parent, $method), $arguments);
            });
            
            $temp= explode('\\', get_class($this->parent));
            $temp = array_pop($temp);
            //echo "$temp SLSLSLS";
            
        }
        return $this;
        
    }
    public function attach($item){
        $item->parent = &$this->parent;
        
        if(method_exists($item,"name")){
            $this->_itemType="FETCH_ITERATORITEM";
            $this->list[$item->name()]=$item;
        }else{ // elements are DBO::FETCH_OBJ
            $this->_itemType="FETCH_OBJ";
            $this->list[]=$item;
        }
        
        return $this;
    }
    
    public function detach(property $prop){
        unset($this->list[$prop->name()]);
        return $this;
    }
    
    /**
     * initialise all arrays
     * @return \xuserver\v5\iterator
     */
    public function init(){
        $this->list=array();
        $this->reset();
        return $this;
    }
    
    /**
     * initialise selection and found arrays
     * @return \xuserver\v5\iterator
     */
    public function reset(){
        $this->selection=array();
        $this->found=array();
        return $this;
    }
    
    /**
     * return an element by name
     */
    public function byName($name=""){
        if($name==""){
            return $this;
        }
        if(! isset($this->list[$name])){
            return $this;
        }
        
        return $this->list[$name];
    }
    /**
     *
     * @param string $needle the string to find in attributes $by
     * @param string $by name of the meta property on to search
     * @return \xuserver\v5\properties
     */
    public function find($needle="__ALL__",$by="name",$reset=true){
        if($needle=="__ALL__"){
            $this->selection=$this->list;

        }else if($needle==""){
            
        }else{
            if($reset){
                $this->selection=array();
            }
            $needle = explode(",", $needle);
            foreach ($this->list as &$p){
                foreach ($needle as $value) {
                    $value=trim($value);
                    if($value==""){
                        continue;
                    }else{
                        if(strpos($value, "." ) ===0 or strpos($value, "!" ) ===0 ){// starting with "." or !
                            if(".".$p->$by() == $value) {
                                //echo "<h2> searching exact $value : ". $p->$by() . "</h2>";
                                $this->selection[$p->name()]=$p;
                                $this->found[$p->name()]=$p;
                            }
                        }else{
                            if(strpos($p->$by(), $value) !==false ){
                                //echo "<h2> searching like $value : ". $p->$by() . "</h2>";
                                $this->selection[$p->name()]=$p;
                                $this->found[$p->name()]=$p;
                            }
                        }
                    }
                }
            }
        }
        return $this;
    }
    public function found(){
        $this->selection = $this->found;
        return $this;
    }
    
    
    
    public function each($closure){
        if( is_callable($closure) ){
            if(count($this->selection)===0){
                $this->find();
            }
            
            if($this->_itemType=="FETCH_ITERATORITEM"){
                foreach ($this->selection as $item){
                    $closure($item);
                }
            }else if($this->_itemType=="FETCH_OBJ"){
                foreach ($this->selection as $item){
                    $instance = $this->parent->val($item);
                    $closure($instance);
                }
            }
            
        }else{
            foreach ($this->selection as $item){
                $item->load()->$closure();
            }
        }
        
        return $this;
    }
}


class iteratorItem{
    protected $_type="";
    protected $_name="";
    protected $_value="";
    public function type($set="__NULL__"){
        if($set!="__NULL__"){
            $this->_type=$set;
            return $this;
        }else{
            return $this->_type;
        }
    }
    
    public function name($set="__NULL__"){
        if($set!="__NULL__"){
            $this->_name=$set;
            return $this;
        }else{
            return $this->_name;
        }
    }
    
    public function val($set="__NULL__"){
        if(is_null($set)){
            $this->_value="";
            return $this;
        }
        
        if($set!="__NULL__"){
            $this->_value=$set;
            return $this;
        }else{
            return $this->_value;
        }
    }
    
}



class relations extends iterator{
    public function fieldset(){
        $this->each(function($p){
            echo $p->ui->row();
        })
        ;
        return $this;
    }
}
class properties extends iterator{
    
    public function sqlSelect($list="__AUTO__"){
        $this->found()->parent->sql()->select($list);
        $this->reset();
       return $this;
    }
    public function sqlUpdate($list="__AUTO__"){
        $this->found()->parent->sql()->update();
        $this->reset();
        return $this;
    }
    public function sqlWhere($list="__AUTO__"){
        $this->found()->parent->sql()->where();
        return $this;
    }
    public function fieldset(){
        $this->each(function($p){
            echo $p->ui->row();
        });
        ;
        return $this;
    }
}




class relation extends iteratorItem{
    public function __construct(){
        $this->ui=new relation_ui($this);
    }
    
    /**
     * tries to return a model instance corresponding to foreign key
     * @return \xuserver\v5\model
     */
    public function load(){
        if($this->parent->db_id()>0){
            $dual=new model();
            $dual->build($this->name());
            return $dual;
        }else{
            return $this->parent;
        }
    }
    
    public function pol($a){
        echo "<h1>".$this->type()."->".$this->name()." pol de $a</h1>";
        
    }
    
}

class property extends iteratorItem{
    
    public $ui="";
    public $parent;
    protected $db_index ="";
    private $db_tablename="";
    
    private $db_typelen="";
    //    private $_type="text";
    //    private $_name="";
    //    private $_value="";
    private $_values="";
    private $_comment="";
    
    protected $is_key=0;
    
    protected $is_disabled=0;
    protected $is_required=0;
    
    
    public function __construct(){
        $this->ui=new property_ui($this);
    }
    
    public function attr($name="__NULL__",$set="__NULL__"){
        if($name=="__NULL__"){
            return $this;
        }else{
            if(isset($this->$name)){
                if($set!="__NULL__"){
                    $this->$name=$set;
                    return $this;
                }else{
                    return $this->$name;
                }
            }else{
                return $this;
            }
        }
    }
    
    public function db_tablename(){
        return $this->db_tablename;
    }
    public function db_index(){
        return $this->db_index;
    }
    
    public function is($key){
        $keyName="is_$key";
        return $this->$keyName;
    }
    
    public function is_key($set="__RETURN__"){
        if($set!="__RETURN__"){
            $this->is_key=$set;
            if($set){
                $this->type("id");
            }else{
                $this->type("text");
            }
            return $this;
        }else{
            return $this->is_key;
        }
    }
    
    
    public function is_foreignkey($db_tablename,$db_index){
        $this->is_key=1;
        $this->db_tablename=$db_tablename;
        $this->db_index=$db_index;
        $this->type("fk");
    }
    
    /**
     * tries to return a model instance corresponding to foreign key
     * @return \xuserver\v5\model
     */
    public function load(){
        if($this->type()=="fk"){
            $dual=new model();
            $dual->build($this->db_tablename);
            return $dual;
        }else{
            return $this->parent;
        }
    }
    
    
    
    
    
    
    public function comment($set=""){
        if($set!=""){
            $this->_comment=$set;
            return $this;
        }else{
            return $this->_comment;
        }
    }
    
    public function values($arr) {
        $this->_type = "select";
        if (is_array($arr)) { //given an array of values
            $this->ui->_values = $arr;
        }else if (strpos($arr, ",")) { // explode comma separated string
            $this->ui->_values = explode(",", $arr);
            foreach ($this->ui->_values as $k=>$value) {
                $this->ui->_values[$k]= trim($value, "'");
            }
            
        }else{
            $this->ui->_values = $arr;
        }
        return $this;
    }
}


class model_ui{
    private $parent="";
    public function __construct(model &$parent){
        $this->parent=$parent;
    }
    function table(){
        $thead = "<thead>";
        $tbody = "<tbody>";
        $thead .= "<tr>";
        $cntspan = -1;
        $this->parent->properties()->each(function($item)use(&$thead,&$cntspan){
            $thead.="<th>".$item->name()."</th>";
            $cntspan++;
        })
        ;
        $thead .= "</tr>";
        
        $cnt = 0;
        $this->parent->iterator()->each(function($item)use(&$tbody,&$cnt){
            $tbody .= "<tr>";
            $cnt++;
            $item->properties()->each(function($item1)use(&$tbody,&$cnt){
                
                if($item1->type()=="file"){
                    $tbody.="<td>file</td>";
                }else{
                    $tbody.="<td>".$item1->val()."</td>";
                }
                
            });
            $tbody .= "</tr>";
            
        })
        ;
            $tfoot = "<tr><td colspan='".($cntspan--)."'>$cnt rows found <td></tr>";
        return "<table class='table table-stripped table-bordered '>".$thead.$tbody.$tfoot."</table>";
    }
}


class relation_ui{
    private $parent="";
    public function __construct(relation &$parent){
        $this->parent=$parent;
    }
    public function input(){
        return "<a href=''>".$this->parent->name()."</a>.<a href=''>".$this->parent->val()."</a>";
    }
}
class property_ui{
    public $_values=array();
    private $parent="";
    
    public function __construct(property &$parent){
        $this->parent=$parent;
    }
    public function input(){
        $required ="";  $disabled ="";
        if ($this->parent->is("disabled")) {$disabled="disabled='disabled'";}
        if ($this->parent->is("required")) {$required="required='required'";}
        
        if ($this->parent->type() == "xhtml") {
            if ($this->parent->val() == "") {$xhtml = "";}else {$xhtml = $this->parent->val();}
            $input = "<textarea $required $disabled rows='3' cols='50' autocomplete='false' name='".$this->parent->name()."'>" . ($xhtml) . "</textarea>";
        }else if ($this->parent->type()=="select"){
            $input = "<select name='".$this->parent->name()."' $required $disabled >";
            $options="";
            
            if (is_array($this->_values)) { //given an array of values
                $options.="<option value=\"\" style=\"background: rgba(200, 200, 200, 0.5);font-style:italic\">...</option>";
                if (array_keys(array_keys($this->_values)) !== array_keys($this->_values)) {
                    foreach ($this->_values as $i => $item) {
                        $selected="";
                        if ($this->parent->val() == $i) {
                            $selected="selected='selected'";
                        }
                        $options.="<option value=\"$i\" $selected>$item </option>";
                    }
                }else {
                    foreach ($this->_values as $i => $item) {
                        $selected="";
                        if ($this->parent->val() == $item) {
                            $selected="selected='selected'";
                        }
                        $options.="<option value=\"$item\" $selected>$item </option>";
                    }
                }
                
            }
            
            $input = $input."$options</select>";
        }else if($this->parent->type()=="checkbox"){
            
            if($this->parent->attr("db_typelen")>1){
                if ($this->parent->val() == "1") {
                    $CHECKED1 = "checked='checked'";
                    $CHECKED0 = "";
                    $CHECKED = "";
                }else if ($this->parent->val() == "0") {
                    $CHECKED1 = "";
                    $CHECKED0 = "checked='checked'";
                    $CHECKED = "";
                }else{
                    $CHECKED1 = "";
                    $CHECKED0 = "";
                    $CHECKED = "checked='checked'";
                }
                $input="<label>true <input type=\"radio\" $required $disabled $CHECKED1 name='".$this->parent->name()."' value=\"1\" placeholder=\"".$this->parent->comment()."\" /></label> ";
                $input.="<label>false <input type=\"radio\" $required $disabled $CHECKED0 name='".$this->parent->name()."' value=\"0\" placeholder=\"".$this->parent->comment()."\" /></label> ";
                $input.="<label>both <input type=\"radio\" $required $disabled $CHECKED name='".$this->parent->name()."' value=\"\" placeholder=\"".$this->parent->comment()."\" /></label> ";
            }else{
                if ($this->parent->val() == "1") {
                    //$input->addAttribute("checked", "checked");
                    $CHECKED = "checked='checked'";
                }else{
                    $CHECKED = "";
                }
                $input="<input type=\"".$this->parent->type()."\" $required $disabled $CHECKED name='".$this->parent->name()."' value=\"1\" placeholder=\"".$this->parent->comment()."\" />";
            }
            
            /*
            if ($this->parent->val() == "1") {
                //$input->addAttribute("checked", "checked");
                $CHECKED = "checked='checked'";
            }else{
                $CHECKED = "";
            }
            $input="<input type=\"".$this->parent->type()."\" $required $disabled $CHECKED name='".$this->parent->name()."' value=\"1\" placeholder=\"".$this->parent->comment()."\" />";
            */
            
        }else{
            $input="<input type=\"".$this->parent->type()."\" $required $disabled name='".$this->parent->name()."' value=\"".$this->parent->val()."\" placeholder=\"".$this->parent->comment()."\" />";
        }
        return $input;
    }
    
    public function row(){
        return "<div>".$this->parent->type()." ".$this->parent->name()." ".$this->input()." </div>";
    }
    
}







?>
