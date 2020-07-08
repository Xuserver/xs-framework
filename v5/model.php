<?php 
/**
 * GJ 25/06/2020
 */


namespace xuserver\v5;

use PDO;

function debug($s,$echo=false){
    $alert = "<div class='alert alert-warning'> <button type='button' class='close' data-dismiss='alert' aria-label='Close'><span aria-hidden='true'>&times;</span></button> $s </div>";
    if($echo){
        echo $alert;
    }else{
        return $alert;
    }
    
}

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
            $property->type("pk");
            
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
    private $_sqlstmt="";
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
    
    
    public function SQL_SELECT(){
        return $this->_SELECT;
    }
    
    public function SQL_WHERE(){
        return $this->_WHERE;
    }
    
    
    /**
     * reset sql part
     * chainable
     * @return \xuserver\v5\sql
     */
    public function reset(){
        $this->_SELECT=array();
        //$this->_FROM="";
        $this->_JOIN=array();
        $this->_WHERE=array();
        $this->_ORDERBY=array();
        $this->_LIMIT="";
        $this->_OFFSET="";
        $this->_SET=array();
        return $this;
    }
    
    
    public function build(){
        $this->reset();
        $SQL=$this;
        $MODEL=$SQL->parent;
        $SQL->parent->properties()->all(function( property $PROPERTY) use(&$SQL,$MODEL){
            
            if($PROPERTY->type()=="fk" ){
                $sametable=false;
                if($PROPERTY->fk_tablename()==$PROPERTY->db_tablename()){
                    // jointure vers la m�me table
                    $sametable=true;
                    $TABLENAME = $PROPERTY->db_tablename() ; // epc_affaire
                    $FOREIGNKEYNAME = $PROPERTY->name(); // epc_programme
                    $EXTERNAL_TABLENAME = $PROPERTY->db_tablename() ; // epc_affaire
                    $EXTERNAL_TABLEALIAS = $FOREIGNKEYNAME;
                    $EXTERNAL_TABLEKEY = $PROPERTY->fk_index(); // id_affaire
                    $NEW_MODEL = $MODEL;
                    $SQL->_JOIN[]= " LEFT JOIN $EXTERNAL_TABLENAME AS $EXTERNAL_TABLEALIAS ON $TABLENAME.$FOREIGNKEYNAME = $EXTERNAL_TABLEALIAS.$EXTERNAL_TABLEKEY";
                }else{
                    // jointure vers une autre table
                    $sametable=false;
                    $TABLENAME= $MODEL->db_tablename(); // epc_affaire
                    $FOREIGNKEYNAME= $PROPERTY->name(); // moa_cdp
                    $EXTERNAL_TABLENAME = $PROPERTY->fk_tablename(); // auth_user
                    $EXTERNAL_TABLEALIAS = $FOREIGNKEYNAME; // moa_cdp
                    $EXTERNAL_TABLEKEY = $PROPERTY->fk_index(); // id_user
                    $NEW_MODEL = $PROPERTY->load();
                    $SQL->_JOIN[]= " LEFT JOIN $EXTERNAL_TABLENAME ON $TABLENAME.$FOREIGNKEYNAME = $EXTERNAL_TABLENAME.$EXTERNAL_TABLEKEY" ;
                }
                // creating virtual properties
                $NEW_MODEL->properties()->all(function( property $VIRTUAL) use(&$SQL, &$MODEL, &$EXTERNAL_TABLEALIAS, &$sametable){
                    if($VIRTUAL->type()=="text" and ! $VIRTUAL->virtual()){
                        $property = new property();
                        $alias = $EXTERNAL_TABLEALIAS. "_" . $VIRTUAL->name();
                        $property->name($alias);
                        $property->realname($VIRTUAL->name() );
                        $property->type($VIRTUAL->type());
                        $property->virtual(1);
                        if($sametable){
                            $EXTERNAL_TABLENAME=$EXTERNAL_TABLEALIAS;
                        }else{
                            $EXTERNAL_TABLENAME=$VIRTUAL->parent->db_tablename();
                        }
                        $property->db_tablename($EXTERNAL_TABLENAME);
                        $MODEL->properties()->attach($property);
                    }else{
                        
                    }
                    
                });//->resetAll();
                
            }else{
                
            }
        }); // ->resetAll()
        ;
        
        $_JOIN = implode(" ", $this->_JOIN);
        $this->_FROM=$this->parent->db_tablename() . $_JOIN;
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
        
        $this->reset();
        if($list=="__AUTO__"){
            $SQL=$this;
            
            $SQL->parent->properties()->each(function( property $PROPERTY) use(&$SQL){
                $SQL->_SELECT[$PROPERTY->name()]=$PROPERTY->db_tablename() . "." . $PROPERTY->realname() ." AS " . $PROPERTY->name();
            })->resetAll();
            ;
            $this->parent->properties()->sort($SQL->_SELECT);
            
        }else if(is_null($list)){
            $this->_SELECT=array();
        }else{
            $this->_SELECT=explode(", ", $list);
            
            foreach ($this->_SELECT as &$value) {
                $value=$this->parent->db_tablename().".".$value;
            }
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
    
    public function where($list="__AUTO__",$andor="AND") {
        $self=$this;
        $case = $self->parent->state() ;
        
        if($list=="__AUTO__"){
            $this->_WHERE=array();
            if($case =="is_instance"){
                $this->_WHERE[$this->parent->db_index()] = $this->parent->db_tablename().".".$this->parent->db_index()."=".$this->parent->db_id();
            }else{
                $this->parent->properties()->each(function( property $PROPERTY) use(&$self,&$andor){
                    $value = $PROPERTY->val();
                    if($value=="" or is_null($value) ){
                        
                    }else{
                        if($PROPERTY->type()=="checkbox" or $PROPERTY->type()=="number" or $PROPERTY->type()=="pk" or $PROPERTY->type()=="fk"){
                            $this->_WHERE[$PROPERTY->name()] = " $andor " . $PROPERTY->db_tablename().".". $PROPERTY->realname()."=\"$value\"";
                        }else{
                            $this->_WHERE[$PROPERTY->name()] = " $andor " . $PROPERTY->db_tablename().".". $PROPERTY->realname()." LIKE \"%$value%\"";
                        }
                    }
                })->resetAll();
            }
        }else if(is_null($list)){
            $this->_WHERE=array();
        }else{
            $this->_WHERE=explode(",", $list);
        }
        return $this;
    }
    
    /**
     * @todo use p->tb_tablename ()
     * @param string $list
     * @param string $direction
     * @return \xuserver\v5\sql
     */
    public function orderby($list="__AUTO__",$direction="ASC") {
        $self=$this;
        if($list=="__AUTO__"){
            $self->parent->properties()->each(function( property $prop) use(&$self,$direction){
                if($prop->db_tablename()==$self->parent->db_tablename()){
                    $self->_ORDERBY[] = $prop->db_tablename().".". $prop->realname()." $direction ";
                }else{
                    $self->_ORDERBY[] = $prop->db_tablename().".". $prop->realname()." $direction ";
                }
            })->resetAll();
        }else if(is_null($list)){
            $self->_ORDERBY[] = array();
        }else{
            $this->_ORDERBY = explode(",", $list);
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
        }else{
            $this->_FROM="";
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
     * offset sql clause
     * @param string $offset
     * @return \xuserver\v5\sql
     */
    public function offset($offset="0") {
        if($offset=="0" or $offset==""){
            $this->_OFFSET="";
        }else{
            $this->_OFFSET=" OFFSET $offset ";
        }
        return $this;
    }

    public function update( $list="__AUTO__") {
        /*
         UPDATE table_name
         SET column1 = value1, column2 = value2, ...
         */
        $this->_sqltype="UPDATE";
        if($list=="__AUTO__"){
            $this->_SET=array();
            $self=$this;
            $this->parent->properties()->each(function( property $PROPERTY) use(&$self){
                $val = $PROPERTY->val();
                if($PROPERTY->is_selected() and !$PROPERTY->is_primarykey() ){
                    $self->_SET[]=$PROPERTY->db_tablename() . "." . $PROPERTY->realname() ." = '$val'" ;
                }else{
                    
                }
            })->resetAll();
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
     * build the sql statement
     * @return string
     */
    public function statement(){
        $self=$this;
        $case = $self->parent->state() ;
        if($this->_sqltype=="SELECT"){
            $_SELECT = implode(", ", $this->_SELECT);
            //$_JOIN = implode(" ", $this->_JOIN);
            
            if($case =="is_instance"){
                $_LIMIT = "";
                $_OFFSET = "";
                $_ORDERBY ="" ;
            }else{
                if(count($this->_ORDERBY)>0){
                    $_ORDERBY = " ORDER BY  ".implode(" ,", $this->_ORDERBY) ;
                }else{
                    $_ORDERBY ="" ;
                }
                if(strpos($this->_LIMIT, "LIMIT") ===false ){
                    $_LIMIT = "LIMIT 150";
                }else{
                    $_LIMIT = $this->_LIMIT;
                }
                
                if(strpos($this->_OFFSET, "OFFSET") ===false ){
                    $_OFFSET = "";
                }else{
                    $_OFFSET = $this->_OFFSET;
                }
            }
            
            if(count($this->_WHERE)>0){
                $firstkey = NULL;
                foreach ($this->_WHERE as $firstkey => $value) {$value;break;}
                if (null === $firstkey) {
                    $_WHERE="";
                }else{
                    $this->_WHERE[$firstkey] = preg_replace('/AND/', ' ', $this->_WHERE[$firstkey], 1);
                    $_WHERE = " WHERE  ".implode(" ", $this->_WHERE);
                }
            }else{
                $_WHERE ="";
            }
            $this->_sqlstmt="SELECT $_SELECT FROM $this->_FROM $_WHERE $_ORDERBY $_LIMIT $_OFFSET";
            
        }else if($this->_sqltype=="UPDATE"){
            
            $_SET = implode(", ", $this->_SET);
            if(count($this->_WHERE)>0){
                $firstkey = NULL;
                foreach ($this->_WHERE as $firstkey => $value) {$value;break;}
                if (null === $firstkey) {
                    $_WHERE="";
                }else{
                    $this->_WHERE[$firstkey] = preg_replace('/AND/', ' ', $this->_WHERE[$firstkey], 1);
                    $_WHERE = " WHERE  ".implode(" ", $this->_WHERE);
                }
            }else{
                $_WHERE ="";
            }
            $this->_sqlstmt="UPDATE $this->_FROM SET $_SET $_WHERE ";
            
        }
        return $this->_sqlstmt;
    }
    
    function statement_current(){
        return $this->_sqlstmt;
    }
    
    
    /**
     * sql statement to read an instance of parent model  
     * @return string
     */
    public function statement_read_instance(){
        if ($this->_sqlstmt =="") {
            $this->_sqlstmt = $this->select()->where()->statement();
        }
        return $this->_sqlstmt ;
    }
    /**
     * sql statement to list model as a selection of instances that match certain where clause
     * @return string
     */
    public function statement_read_selection(){
        if ($this->_sqlstmt =="") {
            $this->_sqlstmt = $this->select()->where()->statement();
        }
        return $this->_sqlstmt ;
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
     * generate html table using the actual sql statement, for debug purpose only
     * @deprecated
     */
    public function ui_table(){
        $qry = $this->parent->db()->query($this->_sqlstmt);
        $html = "<table class='table'>";
        $cnt=0;
        foreach($qry as $row) {
            if($cnt<1){
                $html .= "<tr>";
                foreach($row as $column => $value) {
                    if(is_string($column)){
                        $html.="<th>".$column."</th>";
                    }
                    
                }
                $html .= "</tr>";
            }
            $html .= "<tr>";
            $cnt++;
            foreach($row as $column => $value) {
                if(is_string($column)){
                    $html.="<td>".$value."</td>";
                }
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
    private $__debug="";
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
     * point to the model itself
     * @var \xuserver\v5\model
     */
    public $parent; 
    
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
     * @todo - is_init : the model doesnt map anything
     * @todo - is_virgin : the model only maps the table  
     * @todo - is_model : the model maps its table and foreign keys
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
        $property = $this->_properties->byName($name);
        if($property==false){
            //$this->debug("Magic property <b>$name</b>  doens't exist");
            $this->$name = $value;
        }else{
            $property->val($value);
            $this->_properties->append($property);
        }
    }
    
    
    public function __get($name){
        $property = $this->_properties->byName($name);
        if($property==false){
            return $this->$name;
        }else{
            return $property->val();
        }
    }
    
    
    /**
     * @todo reflechir a son emploi
     * @return \xuserver\v5\iterator
     */
    public function __invoke(){
        return $this->_iterator;
    }
    
    function debug($m=""){
        if( is_null($m) ){
            $this->__debug = "";
            return $this;
        }else if($m==""){
            return $this->__debug;
        }else{
            $this->__debug .= debug($m);
            return $this;
        }
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
                $property->realname($property_name);
                $property->db_tablename($this->db_tablename);
                
                $this->properties()->attach($property);
                
                if ($property_key == "PRI") {
                    if (strpos($row["Extra"], "auto_increment") !== false) {
                        $this->db_index = $property_name;
                        $property->is_primarykey($property_name);
                    }
                }else{
                }
                
                $fkkey=$this->db_tablename."_".$property_name;
                if (isset($this->db->FK[$fkkey])) {
                    $elt=$this->db->FK[$fkkey];
                    $property->is_foreignkey();
                    $property->fk_tablename($elt["db_tablename"]);
                    $property->fk_index($elt["db_index"]);
                    
                }
                
                if (isset($row["Comment"])) {
                    $property->comment($row["Comment"]);
                }
                if (isset($row["Default"])) {
                    //$property->val($row["Default"]);
                }
                
                
            }
            $this->sql()->build();
            $this->properties()->reset("model.build($this->db_tablename)");
            
            /**
             * RELATIONS
             */
            foreach ($this->db->FK as $row) {
                if($row["db_tablename"]==$this->db_tablename()){
                    $relation = new relation();
                    $relation->name($row["table"]);
                    $relation->db_tablename($this->db_tablename());
                    $relation->type("relation");
                    $relation->val($row["index"]);
                    $this->relations()->attach($relation);
                }
                //echo "<br/><span style='color:$col'>".$this->db_tablename() . " </span> ".$row["db_tablename"]. "  <span style='color:$col'>".$row["table"]. " </span>.<span style='color:$col'>".$row["db_index"]. " </span> ";
            }

            $this->state("is_model");
            //$this->read();
            
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
            $this->state("is_instance");
            $this->db_id = $id;
            $sql_read=$this->sql()->statement_read_instance();
            //$this->debug("model(is_instance).read($sql_read)");
            try {
                $instance = $this->db->query($sql_read)->fetchObject();
                
                $this->val($instance);
                $this->iterator()->init();
                $this->iterator()->attach($this);
            }catch(\PDOException $exception) {
                $this->state("is_model");
                echo "<div><h1>ERROR model::read()<h1></div><div>$exception</div>";
                //echo "<div>SQL $sql_read</div>";
            }
        }else if ( $id=="__NULL__" ) {
            $this->state("is_selection");
            $sql_read=$this->sql()->statement_read_selection();
            //$this->debug("model(is_selection).read($sql_read)");
            try {
                $qry = $this->db->query($sql_read);
                $selection = $qry->fetchAll(\PDO::FETCH_OBJ);
                
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
        
        
        $this->properties()->reset("model.read($this->db_tablename)");
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
        //$this->_properties->reset();
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
    
    
    
    public function label(){
        $label="";
        $this->properties()->find("text","type")->each(function(property $prop)use(&$label){
            $label .= " ". $prop->val();
        });
        return $label;
    }
      
    public function val($values="__NULL__"){
        if(is_null($values) or $values=="__NULL__"){
            $this->properties()->each(function(property $prop){
                $prop->val(null);
            });
        }else if(is_object($values)){
            
            //debug (count($this->_properties->selection));
            $this->properties()->each(function(property $prop)use(&$values){
                $name=$prop->name();
                //debug("<br />".$name . " : " . $values->$name);
                
                if(isset( $values->$name )){
                    $prop->val( $values->$name );
                    
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
        
        //echo debug ("Call method ".__CLASS__."->$method(".implode(', ',$arguments) .")");
        
        //$this->val($instance);
        if($this->_itemType=="FETCH_ITERATORITEM"){
            $this->each(function(iteratorItem $item)use(&$method,&$arguments){
                call_user_func_array(array($item, $method), $arguments);
            });
        }else if($this->_itemType=="FETCH_OBJ"){
            $iterator=$this;
            $temp = $this->parent;
            $this->each(function($item)use(&$method,&$arguments,&$iterator){
                
                //print_r($item);
                $iterator->parent->val($item);
                
                //echo "<br />ICI $temp ".$item->affaire ;
                call_user_func_array(array($iterator->parent, $method), $arguments);
            });
                $iterator->parent->val($temp);
            $temp= explode('\\', get_class($this->parent));
            $temp = array_pop($temp);
            //echo "$temp SLSLSLS";
            
        }
        return $this;
        
    }
    
    public function model(){
        return $this->parent;
    }
    
    public function count($context="list"){
        return count($this->$context);
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
     * empty all iterator arrays
     * @return \xuserver\v5\iterator
     */
    public function init(){
        $this->list=array();
        $this->reset("init");
        return $this;
    }
    
    /**
     * empty selection and found arrays
     * @return \xuserver\v5\iterator
     */
    public function reset($message=""){
        //echo debug("$message");
        $this->selection=array();
        $this->found=array();
        return $this;
    }
    
    /**
     * initialise selection and found arrays
     * @return \xuserver\v5\iterator
     */
    public function resetAll(){
        $this->selection=$this->list;
        $this->found=$this->list;
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
            return false;
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
            //$this->selection=$this->list;
            $this->resetAll();
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
                        if(strpos($value, "." ) ===0 or strpos($value, "#" ) ===0 ){// starting with "." or #
                            if(".".$p->$by() == $value) {
                                //echo "<h2> searching exact $value : ". $p->$by() . "</h2>";
                                $this->append($p);
                            }else if("#".$p->$by() == $value) {
                                //echo "<h2> searching exact $value : ". $p->$by() . "</h2>";
                                $this->append($p);
                            }
                            
                        }else{
                            if(strpos($p->$by(), $value) !==false ){
                                //echo "<h2> found by $by like $value : ". $p->name() . "</h2>";
                                $this->append($p);
                                
                            }else{
                                //echo "<h2> not found by $by like $value : ". $p->$by() . "</h2>";
                            }
                        }
                    }
                }
            }
        }
        return $this;
    }
    public function then($needle="__ALL__",$by="name"){
        $this->find($needle,$by,false);
        return $this;
    }
    
    
    public function append($item ){
        
        //echo "dd".$item->name();
        $this->selection[$item->name()]=$item;
        $this->found[$item->name()]=$item;
    }
    public function remove($item){
        unset($this->selection[$item->name()]);
        unset($this->found[$item->name()]);
    }
    
    public function found(){
        $this->selection = $this->found;
        return $this;
    }
    
    
    /**
     * recursive function applyed on selection, found or list arrays
     * @param $closure
     * @param string $context
     * @return \xuserver\v5\iterator
     */
    public function each($closure,$context="selection"){
        
        if($context=="list"){
            $context=&$this->list;
        }elseif ($context=="found"){
            $context=&$this->found;
        }else{
            $context=&$this->selection;
        }
        
        if( is_callable($closure) ){
            if(count($context)===0){
                $this->find();
            }
            
            if($this->_itemType=="FETCH_ITERATORITEM"){
                foreach ($context as $item){
                    $closure($item);
                }
            }else if($this->_itemType=="FETCH_OBJ"){
                foreach ($context as $item){
                    $instance = $this->parent->val($item);
                    $closure($instance);
                }
            }
            
        }else{
            foreach ($context as $item){
                $item->load()->$closure();
            }
        }
        
        return $this;
    }
    
    /**
     * each alias on list array, dont affect selection
     * @param $closure
     * @return \xuserver\v5\iterator
     */
    public function all($closure){
        $this->each($closure,"list");
        return $this;
    }
    
    
    function sort($order=""){
        
        if( is_array($order)){
            
            $ordered = array();
            foreach ($order as $key => $val) {
                if (array_key_exists($key, $this->list)) {
                    $ordered[$key] = $this->list[$key];
                    unset($this->list[$key]);
                }
            }
            $this->list= $ordered + $this->list;
            
            
        }else if($order!="ksort"){
            
        }else{
            ksort($this->list);
            ksort($this->selection);
            ksort($this->found);
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
    
    
    public function select(){
        $this->found();
        $this->parent->sql()->select()->statement();
        $this->reset("properties.select");
        return $this;
    }
    
    public function where($andor="AND"){
        $this->found();
        $this->parent->sql()->where("__AUTO__", $andor)->statement();
        $this->reset("properties.where");
        return $this;
    }
    
    public function orderby($direction="ASC"){
        $this->found();
        $this->parent->sql()->orderby("__AUTO__",$direction)->statement();
        $this->reset("properties.orderby");
        return $this;
    }
    
    public function update(){
        $this->found();
        $this->parent->sql()->update();
        $this->reset("properties.update");
        return $this;
    }
    
    /**
     * @deprecated
     * @param string $list
     * @return \xuserver\v5\properties
     */
    public function sqlSelect($list="__AUTO__"){
        $this->parent->sql()->select($list);
        $this->reset("properties.sqlSelect");
       return $this;
    }
    /**
     * @deprecated
     * @param string $list
     * @return \xuserver\v5\properties
     */
    public function sqlUpdate($list="__AUTO__"){
        $this->found()->parent->sql()->update();
        $this->reset("properties.sqlUpdate");
        return $this;
    }
    /**
     * @deprecated
     * @param string $list
     * @return \xuserver\v5\properties
     */
    public function sqlWhere($list="__AUTO__"){
        $this->parent->sql()->where();
        return $this;
    }
    /**
     * @deprecated
     * @param string $list
     * @return \xuserver\v5\properties
     */
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
    
    public function db_tablename($set="__NULL__"){
        if($set!="__NULL__"){
            $this->db_tablename=$set;
            return $this;
        }else{
            return $this->db_tablename;
        }
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
    public $parent; // point to the model, should point ot the properties
    
    private $db_tablename="";
    
    private $fk_tablename="";
    private $_virtual=0;
    protected $db_index ="";
    
    private $realname="";
    
    private $db_typelen="";
    private $_values="";
    private $_comment="";
    
    //private $selected=0;

    //    private $_type="text";
    //    private $_name="";
    //    private $_value="";
    
    protected $is_key=0;
    
    protected $is_disabled=0;
    protected $is_required=0;
    
    
    public function __construct(){
        $this->ui=new property_ui($this);
    }
    
    /**
     * is propery selected in SELECT statement
     * @return \xuserver\v5\property|number|string
     */
    public function is_selected(){
        if(isset($this->parent->sql()->SQL_SELECT()[$this->name()])){
            return 1;
        }else{
            return 0;
        }
    }
    
    /**
     * is propery selected in WHERE statement
     * @param string $set
     * @return \xuserver\v5\property|number|string
     */
    public function is_where(){
        if(isset($this->parent->sql()->SQL_WHERE()[$this->name()])){
            return 1;
        }else{
            return 0;
        }
    }
    
    public function realname($set="__NULL__"){
        if($set!="__NULL__"){
            $this->realname=$set;
            return $this;
        }else{
            return $this->realname;
        }
    }
    public function virtual($set="__NULL__"){
        if($set!="__NULL__"){
            $this->_virtual=$set;
            return $this;
        }else{
            return $this->_virtual;
        }
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
    
    /**
     * retrieve or set tb_tablename on property
     * @param string $set
     * @return \xuserver\v5\property|string
     */
    public function db_tablename($set="__NULL__"){
        if($set!="__NULL__"){
            $this->db_tablename=$set;
            return $this;
        }else{
            return $this->db_tablename;
        }
    }
    
    /**
     * retrieve or set tb_tablename on property
     * @param string $set
     * @return \xuserver\v5\property|string
     */
    public function fk_tablename($set="__NULL__"){
        if($set!="__NULL__"){
            $this->fk_tablename=$set;
            return $this;
        }else{
            return $this->fk_tablename;
        }
    }
    
    
    public function fk_index($set="__NULL__"){
        if($set!="__NULL__"){
            $this->db_index=$set;
            return $this;
        }else{
            return $this->db_index;
        }
    }
    
    public function is_primarykey($fieldname="__NULL__"){
        if($fieldname!="__NULL__"){
            //$this->parent->db_index=$fieldname;
            $this->is_key=1;
            $this->type("pk");
            return $this;
        }else{
            if($this->is_key and $this->type()=="pk"){
                return 1;
            }else{
                return 0;
            }
        }
    }
    
    
    public function is_foreignkey(){
        $this->is_key=1;
        $this->type("fk");
    }
    
    public function is($key){
        $keyName="is_$key";
        return $this->$keyName;
    }
    
    /**
     * @deprecated
     * @param string $set
     * @return \xuserver\v5\property|number|string
     */
    public function is_key($set="__RETURN__"){
        if($set!="__RETURN__"){
            $this->is_key=$set;
            if($set){
                $this->type("pk");
            }else{
                $this->type("text");
            }
            return $this;
        }else{
            return $this->is_key;
        }
    }
    
    
    /**
     * tries to return a model instance corresponding to foreign key
     * @return \xuserver\v5\model
     */
    public function load(){
        if($this->type()=="fk"){
            $dual=new model();
            $dual->build($this->fk_tablename);
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
    
    
    public function val($set="__NULL__"){
        
        if($set!="__NULL__"){
            if($this->is_primarykey()){
                //debug("<br />set PK ".$name . " : " . $values->$name);
                $this->parent->db_id($set);
                $this->parent->state("is_instance");
            }
        }else{
            
        }
        return parent::val($set);
        
        
    }
    
    public function and() {
        //$list="__AUTO__",$direction="ASC"
        $this->parent->properties()->find(".".$this->name())->where("AND");
    }
    
    public function or() {
        //$list="__AUTO__",$direction="ASC"
        $this->parent->properties()->find(".".$this->name())->where("OR");
    }
    
    public function asc() {
        //$list="__AUTO__",$direction="ASC"
        $this->parent->properties()->find(".".$this->name())->orderby("ASC");
    }
    public function desc() {
        //$list="__AUTO__",$direction="ASC"
        $this->parent->properties()->find(".".$this->name())->orderby("DESC");
    }
}


class model_ui{
    private $parent="";
    public function __construct(model &$parent){
        $this->parent=$parent;
    }
    public function structure(){
        $return="";
        
        
        $sql = $this->parent->sql()->statement_current();
        
        $table="<tr><th colspan='6'><h2>model <u>".$this->parent->db_tablename()."</u></h2></th></tr>";
        $table.="<tr><th>tablename :</th><td>".$this->parent->db_tablename()."</td><th>state :</th><td>".$this->parent->state()."</td><td></td><td></td></tr>";
        $table.="<tr><th>primary key :</th><td>".$this->parent->db_index()."</td><th>value :</th><td>".$this->parent->db_id()."</td><td></td><td></td></tr>";

        $lines="";
        if($sql==""){
            $sql="SQL clause is empty, model isn't built.";
        }else{
            if($this->parent->iterator()->count() >0){
                /*
                $this->parent->iterator()->all(function ($item) use (&$lines){
                    $lines .= "<li>".$item->label()."</li>";
                });
                */
            }else{
                $lines="iterator is empty.";
            }
        }
        
        $table.="<tr><th><h3>>iterator :</h3></th><td>".$this->parent->iterator()->count()." elements</td><td colspan='4'></td></tr>";
        $table.="<tr><td colspan='6'><code class='small'>$sql</code></td></tr>";
        //$table.="<tr><td colspan='4'>$lines</td></tr>";
        
        $table.="<tr><th colspan='6'><h3>>properties :</h3></th></tr>";
        $table.="<tr><th>name</th><th>value</th><th>type</th><th>table</th><th>selected</th><th>searched</th></tr>";
        $lines="";
        $this->parent->properties()->all(function (property $item) use (&$lines){
            if($item->type()=="fk"){
                $name="<a href=''>".$item->name()."</a>";
            }else{
                $name=$item->name();
            }
            if($item->is_selected()){$s="yes";}else{$s="";}
            if($item->is_where()){$w="yes";}else{$w="";}
            
            $lines .= "<tr><td>$name</td><td>".$item->val()."</td><td>".$item->type()."</td><td>".$item->db_tablename()."</td><td>$s</td><td>$w</td></tr>";
            
        });
        $table.=$lines;
        
        $table.="<tr><th colspan='6'><h3>>relations :</h3></th></tr>";
        $lines="";
        $this->parent->relations()->all(function (relation $item) use (&$lines){
            $lines .= "<tr><td>".$item->name()."</td><td>".$item->val()."</td><td>".$item->type()."</td><td>".$item->db_tablename()."</td><td></td><td></td></tr>";
        });
        $table.=$lines;
        $return .= "<h1>structure </h1><table class='table'>$table</table>";
        return $return;
    }
    
    
    
    
    public function table(){
        $thead = "<thead>";
        $tbody = "<tbody>";
        $thead .= "<tr>";
        $cntspan = -1;
         
        $this->parent->properties()->each(function($prop)use(&$thead,&$cntspan){
            if($prop->is_selected()){
                $thead.="<th>".$prop->name()."</th>";
                $cntspan++;
            }
        })
        ;
        $thead .= "</tr>";
        
        $cnt = 0;
        $this->parent->iterator()->each(function($item)use(&$tbody,&$cnt){
            $tbody .= "<tr>";
            $cnt++;
            $item->properties()->each(function($prop)use(&$tbody,&$cnt){
                if($prop->is_selected()){
                    if($prop->type()=="file"){
                        $tbody.="<td>file</td>";
                    }else{
                        $tbody.="<td>".$prop->val()."</td>";
                    }
                }
            });
            $tbody .= "</tr>";
            
        })
        ;
        
        $this->parent->properties()->reset();
        $tfoot = "<tr><td colspan='".($cntspan--)."'>$cnt rows found <td></tr>";
        return "<table class='table table-stripped table-bordered '>".$thead.$tbody.$tfoot."</table>";
    }
    
    public function form(){
            
        $return ="";
        $pk=$this->parent->db_index();
        if($this->parent->properties()->count("selection")<1){
            $this->parent->properties()->find("text","type");
        }
        
        $this->parent->properties()->all(function (property $prop) use(&$return){
            if($prop->is_selected() or $prop->is_primarykey()){
                $return.=$prop->ui->row();
            }
        });
        
        
        return "<form method='post'>".$return."<div><input type='submit' value='update' name='method_update' /></div></form>";
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
    
    public function formid(){
        return $this->parent->name()."dd";
        
    }
    public function input(){
        $required ="";  $disabled ="";
        if ($this->parent->is("disabled")) {$disabled="disabled='disabled'";}
        if ($this->parent->is("required")) {$required="required='required'";}
        
        if ($this->parent->is_primarykey()) {
            $input="<input class='form-control' type=\"hidden\" $required $disabled name='".$this->parent->name()."' id='".$this->formid()."' value=\"".$this->parent->val()."\"  />";
            
        }else if ($this->parent->type() == "xhtml") {
            if ($this->parent->val() == "") {$xhtml = "";}else {$xhtml = $this->parent->val();}
            $input = "<textarea class='form-control' $required $disabled rows='3' cols='50' autocomplete='false' name='".$this->parent->name()."' id='".$this->formid()."'>" . ($xhtml) . "</textarea>";
        }else if ($this->parent->type()=="select"){
            $input = "<select name='".$this->parent->name()."' $required $disabled id='".$this->formid()."'>";
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
                $input="<input class='form-check-input' type=\"".$this->parent->type()."\" $required $disabled $CHECKED name='".$this->parent->name()."' id='".$this->formid()."' value=\"1\" placeholder=\"".$this->parent->comment()."\" />";
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
            $input="<input class='form-control' type=\"".$this->parent->type()."\" $required $disabled name='".$this->parent->name()."' id='".$this->formid()."' value=\"".$this->parent->val()."\" placeholder=\"".$this->parent->comment()."\" />";
        }
        return $input;
    }
    
    public function row(){
        $p = $this->parent;
        if($p->is_primarykey()){
            return "
            <div class='form-group'>
                ".$p->ui->input()." 
            </div>"; 
        }else if($p->type()=="checkbox" and !$this->parent->attr("db_typelen")>1 ){
            $classlabel="form-check-label";
            $classdiv="form-check";
            return "
            <div class='form-group $classdiv'>
                <label class='form-group $classlabel' for='".$this->formid()."'>
                ".$p->ui->input()." ".$p->name()."
                </label>
                <small class='form-text text-muted'>".$p->comment()."</small>
            </div>"; 
        }else{
            $classlabel="";
            $classdiv="";
            return "
            <div class='form-group $classdiv'>
                <label class='form-group $classlabel' for='".$this->formid()."'>".$p->name()."</label>
                ".$p->ui->input()."
                <small class='form-text text-muted'>".$p->comment()."</small>
            </div>";
        }
        
        
           
        
        //return "<div>".$this->parent->type()." ".$this->parent->name()." ".$this->input()." </div>";
    }
    
}







?>
