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
    
    private $aKEY = array("ID");
    private $aDATE = array("DATE", "TIMESTAMP");
    private $aXHTML = array("TEXT", "MEDIUMTEXT", "LONGTEXT", "TINYTEXT");
    private $aBLOB = array("BLOB", "MEDIUMBLOB", "LONGBLOB");
    private $aCAMERA = array("TINYTEXT");
    private $aNUMBER = array("NUMERIC", "DECIMAL", "FLOAT", "DOUBLE", "SMALLINT");
    private $aBOOL = array("TINYINT");
    private $aTEXT = array("VARCHAR", "MEDIUMINT");
    private $aOBJ = array("INTEGER", "INT", "BIGINT");
    private $aENUM = array("ENUM");
    
    public $pdo;
    
    public $FK;
    public function FK($key="__NULL__"){
        if($key!="__NULL__"){
            if(! $this->isFK($key)){
                return false;
            }else{
                return $this->FK[$key];
            }
            
        }else{
            return $this->FK;
        }
    }
    public function isFK($key){
        if (isset($this->db->FK[$key])) {
            return true;
        }else{
            return false;
        }
    }
    
    
    public $lastInsertId="";
    
    private $db_error;
    function db_error($set="__NULL__"){
        if($set!="__NULL__"){
            $this->db_error=$set;
            return $this;
        }else{
            return $this->db_error;
        }
    }
    
    
    
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
                $property->db_typelen($found);
            }else {
            }  
        }else{
            $db_type = strtoupper($db_type);
        }
        
        if (in_array($db_type, $this->aKEY)) {
            $property->type("pk");
            
        }else if (in_array($db_type, $this->aBOOL)) {
            if($property->db_typelen()>1){
                $property->type("radio");
            }else{
                $property->type("checkbox");
            }
            
        }else if (in_array($db_type, $this->aXHTML)) {
            $property->type("textarea");
            
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
    public function query(string $sql){ 
        try{
            $return= $this->pdo->query($sql);
        }catch(\Exception $e){
            echo notify("<div id='model-structure'><h4>QUERY</h4><code>$sql</code><div>".$e->getCode() . $e->getMessage() ."</div></div>" );
            $return=false;
            $this->db_error($e);
            throw $e;
        }
        return $return;
    }
    
    /**
     * 
     */
    public function execute($sql,$data=""){
        $this->lastInsertId="";
        try {
            $statement = $this->pdo->prepare($sql);
            $this->pdo->beginTransaction();
            try{
                $statement->execute($data);
                $this->lastInsertId =  $this->pdo->lastInsertId();
                $this->pdo->commit();
                $return = $statement;
            }catch(\Exception $e){
                $this->lastInsertId="";
                $t="<ul>";
                foreach ($data as $k=>$v){
                    $t.="<li>$k = $v</li>";
                }
                $t.="<ul>";
                echo debug("<div id='execute-db_error'><h4>EXECUTE</h4><code>$sql</code>$t<div>".$e->getCode() . $e->getMessage() ."</div></div>" );
                $this->pdo->rollback();
                $this->db_error($e);
                $return = false;
            }
        } catch(\Exception $e) {
            $this->pdo->rollback();
            $this->lastInsertId="";
            echo debug("<div id='prepare-db_error'><h4>PREPARE</h4><code>$sql</code>".$e->getCode() . $e->getMessage() ."</div>" );
            $this->db_error($e);
            $return = false;
            throw $e;
        }
        
        return $return;
    }
    /*
    public function EXEC(string $sql,$data=""){
        try {
            $statement = $this->pdo->prepare($sql);
            $this->pdo->beginTransaction();
            $statement->execute($data);
            $this->lastInsertId =  $this->pdo->lastInsertId();
            $this->pdo->commit();
        } catch(\PDOException $e) {
            $this->pdo->rollback();
            print "Error!: " . $e->getMessage() . "</br>";
        }
    }
    */
    
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
    private $Model;
    private $_sqlstmt="";
    private $_sqltype="SELECT";
    // COMMON & SELECT
    private $_SELECT;
    private $_FROM;
    private $_JOIN;
    private $_WHERE;
    private $_ORDERBY;
    private $_LIMIT;
    private $_OFFSET;
    // UPDATE
    private $_SET;
    // INSERT INTO
    private $_INTO;
    private $_VALUES;
    // DELETE
    private $_DELETE;
    
    public function __construct(model&$model) {
        $this->Model=$model;
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

        $this->_INTO=array();
        $this->_VALUES=array();
        $this->_DELETE=array();
        return $this;
    }
    
    
    public function build(){
        $this->reset();
        $SQL=$this;
        $MODEL=$SQL->Model;
        $SQL->Model->properties()->all(function( property $PROPERTY) use(&$SQL,$MODEL){
            $PROPERTY->title("ceci est un test");
            if($PROPERTY->type()=="fk" ){
                $sametable=false;
                if($PROPERTY->db_foreigntable()==$PROPERTY->db_tablename()){
                    // jointure vers la même table
                    $sametable=true;
                    $TABLENAME = $PROPERTY->db_tablename() ; // epc_affaire
                    $FOREIGNKEYNAME = $PROPERTY->name(); // epc_programme
                    $EXTERNAL_TABLENAME = $PROPERTY->db_tablename() ; // epc_affaire
                    $EXTERNAL_TABLEALIAS = $FOREIGNKEYNAME;
                    $EXTERNAL_TABLEKEY = $PROPERTY->db_index(); // id_affaire
                    $NEW_MODEL = $MODEL;
                    $SQL->_JOIN[]= " LEFT JOIN $EXTERNAL_TABLENAME AS $EXTERNAL_TABLEALIAS ON $TABLENAME.$FOREIGNKEYNAME = $EXTERNAL_TABLEALIAS.$EXTERNAL_TABLEKEY";
                }else{
                    // jointure vers une autre table
                    $sametable=false;
                    $TABLENAME= $MODEL->db_tablename(); // epc_affaire
                    $FOREIGNKEYNAME= $PROPERTY->name(); // moa_cdp
                    $EXTERNAL_TABLENAME = $PROPERTY->db_foreigntable(); // auth_user
                    $EXTERNAL_TABLEALIAS = $FOREIGNKEYNAME; // moa_cdp
                    $EXTERNAL_TABLEKEY = $PROPERTY->db_index(); // id_user
                    $NEW_MODEL = $PROPERTY->load();
                    $SQL->_JOIN[]= " LEFT JOIN $EXTERNAL_TABLENAME ON $TABLENAME.$FOREIGNKEYNAME = $EXTERNAL_TABLENAME.$EXTERNAL_TABLEKEY" ;
                }
                // creating virtual properties
                // defineing fk title field
                $first=0;
                $NEW_MODEL->properties()->all(function( property $VIRTUAL) use(&$SQL, &$MODEL, &$EXTERNAL_TABLEALIAS, &$sametable, &$PROPERTY,&$first){
                    if($VIRTUAL->type()=="text" and ! $VIRTUAL->is_virtual()){
                        $property = new property();
                        $alias = $EXTERNAL_TABLEALIAS. "_" . $VIRTUAL->name();
                        $property->name($alias);
                        $property->db_name($VIRTUAL->name() );
                        $property->type($VIRTUAL->type());
                        $property->is_virtual(1);
                        if($sametable){
                            $EXTERNAL_TABLENAME=$EXTERNAL_TABLEALIAS;
                        }else{
                            $EXTERNAL_TABLENAME=$VIRTUAL->Model->db_tablename();
                        }
                        $property->db_tablename($EXTERNAL_TABLENAME);
                        if($first===0){
                            $PROPERTY->title($property);
                            $first++;
                        }
                        
                        $MODEL->properties()->Attach($property);
                    }else{
                        
                    }
                    
                });//->Fill();
                
            }else{
                
            }
        }); // ->Fill()
        ;
        
        $_JOIN = implode(" ", $this->_JOIN);
        $this->_FROM=$this->Model->db_tablename() . $_JOIN;
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
            $SQL->Model->properties()->each(function( property $PROPERTY) use(&$SQL){
                $SQL->_SELECT[$PROPERTY->name()]=$PROPERTY->db_tablename() . "." . $PROPERTY->db_name() ." AS " . $PROPERTY->name();
            })->Empty();//->Fill();
            $this->Model->properties()->sort($SQL->_SELECT);
            
        }else if(is_null($list)){
            $this->_SELECT=array();
        }else{
            $this->_SELECT=explode(", ", $list);
            
            foreach ($this->_SELECT as &$value) {
                $value=$this->Model->db_tablename().".".$value;
            }
        }
        return $this;
        
    }
    
    
    public function update( $list="__AUTO__") {
        /*
         UPDATE table_name
         SET column1 = :column1, column2 = :column2, ...
         */
        $this->_sqltype="UPDATE";
        if($list=="__AUTO__"){
            $this->_SET=array();
            $this->_VALUES=array();
            $self=$this;
            $this->Model->properties()->each(function( property $PROPERTY) use(&$self){
                $value = $PROPERTY->val();
                if($PROPERTY->is_selected() and !$PROPERTY->is_primarykey() ){
                    $self->_SET[]=$PROPERTY->db_tablename() . "." . $PROPERTY->db_name() ." = :".$PROPERTY->name() ;
                    
                    if($value=="NULL" and $PROPERTY->type()=="select"){
                        $self->_VALUES[$PROPERTY->name()]=null ;
                    }else{
                        $self->_VALUES[$PROPERTY->name()]="$value" ;
                    }
                    
                }else{
                    
                }
                
            })->Empty();
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
     * 
     * @deprecated
     * @return \xuserver\v5\sql
     */
    public function old_update( $list="__AUTO__") {
        /*
         UPDATE table_name
         SET column1 = :column1, column2 = :column2, ...
         */
        $this->_sqltype="OLdUPDATE";
        if($list=="__AUTO__"){
            $this->_SET=array();
            $self=$this;
            $this->Model->properties()->each(function( property $PROPERTY) use(&$self){
                $value = $PROPERTY->val();
                if($PROPERTY->is_selected() and !$PROPERTY->is_primarykey() ){
                    $self->_SET[]=$PROPERTY->db_tablename() . "." . $PROPERTY->db_name() ." = \"$value\"" ;
                }else{
                    
                }
                
            })->Empty();
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
    
    public function insert( $list="__AUTO__") {
        /*
         INSERT INTO table_name (column1 , column2 ...)
         _VALUES ('val1', 'val2' ...)
         */
        $this->_sqltype="INSERT_INTO";
        if($list=="__AUTO__"){
            $this->_INTO=array();
            $this->_VALUES=array();
            $self=$this;
            $this->Model->properties()->all(function( property $PROPERTY) use(&$self){
                //$val = $PROPERTY->val();
                $val = $PROPERTY->valInsert();
                
                if(!$PROPERTY->is_primarykey() and !$PROPERTY->is_virtual() ){
                    $self->_INTO[$PROPERTY->name()]= $PROPERTY->db_name() ;
                    $self->_VALUES[$PROPERTY->name()]=$val ;
                    
                    
                }else{
                }
            });
            ;
            
            
            $this->from();
            $this->_LIMIT="";
            $this->_OFFSET="";
        }else if($list==""){
            
        }else{
            $this->_VALUES=explode(",", $list);
        }
        return $this;
    }
    
    public function delete( $list="__AUTO__") {
        /*
         DELETE FROM table WHERE 
         */
        $this->_sqltype="DELETE";
        if($list=="__AUTO__"){
            $this->from();
            $this->_DELETE=$this->_JOIN;
            $this->_LIMIT="";
            $this->_OFFSET="";
        }else{

        }
        return $this;
    }
    
    
    public function values(){
        return $this->_VALUES;
    }
    
    /**
     * where sql clause
     * @param string $list
     *  __AUTO__ : in automatic mode the where clause is created using the Model model
     *      if model is an instance the automatic mode uses the primary key
     *      if model is a selection of instances the automatic mode uses property values
     *  string : user defined where clause
     *  emtpy : reset the where clause
     * @return \xuserver\v5\sql
     */
    
    public function where($list="__AUTO__",$andor="AND") {
        $self=$this;
        $case = $self->Model->state();
        
        if($list=="__AUTO__"){
            $this->_WHERE=array();
            
            if($case =="is_instance"){
                
                if(is_array($this->Model->db_id())){
                    $_IN = "'".implode("','", $this->Model->db_id())."'";
                    $this->_WHERE[$this->Model->db_index()] = $this->Model->db_tablename().".".$this->Model->db_index()." IN($_IN)";
                }else{
                    $this->_WHERE[$this->Model->db_index()] = $this->Model->db_tablename().".".$this->Model->db_index()."=\"".$this->Model->db_id()."\"";
                }
                
            }else{
                $this->Model->properties()->each(function( property $PROPERTY) use(&$self,&$andor){
                    $value = $PROPERTY->val();
                    if($value=="" or is_null($value) ){
                        
                    }else{
                        $this->_WHERE[$PROPERTY->name()] = $PROPERTY->condition();
                        $self->_VALUES["where_".$PROPERTY->name()]=$value ;
                    }
                })->Fill();
            }
        }else if(is_null($list)){
            $this->_WHERE=array();
        }else{
            $this->_WHERE=explode(",", $list);
        }
        return $this;
    }
    
    /**
     * @deprecated
     */
    
    public function old_where($list="__AUTO__",$andor="AND") {
        $self=$this;
        $case = $self->Model->state();
        
        if($list=="__AUTO__"){
            $this->_WHERE=array();
            
            if($case =="is_instance"){
                
                if(is_array($this->Model->db_id())){
                    $_IN = "'".implode("','", $this->Model->db_id())."'";
                    $this->_WHERE[$this->Model->db_index()] = $this->Model->db_tablename().".".$this->Model->db_index()." IN($_IN)";
                }else{
                    $this->_WHERE[$this->Model->db_index()] = $this->Model->db_tablename().".".$this->Model->db_index()."=\"".$this->Model->db_id()."\"";
                }
                
            }else{
                $this->Model->properties()->each(function( property $PROPERTY) use(&$self,&$andor){
                    $value = $PROPERTY->val();
                    if($value=="" or is_null($value) ){
                         
                    }else{
                        $this->_WHERE[$PROPERTY->name()] = $PROPERTY->condition();
                    }
                })->Fill();
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
            $self->Model->properties()->each(function( property $prop) use(&$self,$direction){
                if($prop->db_tablename()==$self->Model->db_tablename()){
                    $self->_ORDERBY[] = $prop->db_tablename().".". $prop->db_name()." $direction ";
                }else{
                    $self->_ORDERBY[] = $prop->db_tablename().".". $prop->db_name()." $direction ";
                }
            })->Fill();
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

    
    
    
    
    
    /**
     * build the sql statement
     * @return string
     */
    public function statement(){
        $self=$this;
        $case = $self->Model->state() ;
        
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
            $_WHERE = $this->strWhere();
            $this->_sqlstmt="SELECT $_SELECT FROM $this->_FROM $_WHERE $_ORDERBY $_LIMIT $_OFFSET";
            
        }else if($this->_sqltype=="UPDATE"){
            
            $_SET = implode(", ", $this->_SET);
            $_WHERE = $this->strWhere();
            $this->_sqlstmt="UPDATE $this->_FROM SET $_SET $_WHERE ";
            
        }else if($this->_sqltype=="INSERT_INTO"){
            
            $_INTO = implode(", ", $this->_INTO);
            $_INTOPREPARE = ":".implode(",:", $this->_INTO);
            $TABLE = $this->Model->db_tablename();
            $this->_sqlstmt="INSERT INTO $TABLE($_INTO) VALUES ($_INTOPREPARE) ";
            /*
            var_dump($self->_INTO);
            var_dump($self->_VALUES);
            */
            
        }else if($this->_sqltype=="DELETE"){
            //$TABLE = implode(", ", $this->_DELETE);
            $TABLE = $this->Model->db_tablename();
            
            $_WHERE = $this->strWhere();
            $this->_sqlstmt="DELETE FROM $TABLE $_WHERE  ";
            
        }
        return $this->_sqlstmt;
    }
    
    private function strWhere(){
        $_WHERE ="";
        if(count($this->_WHERE)>0){
            $firstkey = NULL;
            foreach ($this->_WHERE as $firstkey => $value) {$value;break;}
            if (null === $firstkey) {
                $_WHERE="";
            }else{
                $this->_WHERE[$firstkey] = preg_replace('/AND/', ' ', $this->_WHERE[$firstkey], 1);
                $this->_WHERE[$firstkey] = preg_replace('/OR/', ' ', $this->_WHERE[$firstkey], 1);
                $_WHERE = " WHERE  ".implode(" ", $this->_WHERE);
            }
        }else{
            $_WHERE ="";
        }
        return $_WHERE;
    }
    
    
    function statement_current(){
        return $this->_sqlstmt;
    }
    
    
    /**
     * sql statement to read an instance of Model model  
     * @return string
     */
    public function statement_read_instance(){
        $this->Model->properties()->Fill();
        $this->_sqlstmt = $this->select()->where()->statement();
        //echo debug($this->_sqlstmt);
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
     * sql statement to update an instance or selection
     * @return string
     */
    public function statement_update(){
        $sql = $this->update()->where()->statement();
        return $sql;
    }
    
    /**
     * sql statement to update an instance or selection
     * @return string
     */
    public function statement_insert(){
        $sql = $this->insert()->statement();
        return $sql;
    }
    
    /**
     * sql statement to delete an instance 
     * @return string
     */
    public function statement_delete(){
        $sql = $this->delete()->where()->statement();
        return $sql;
    }
    

    
    
    
    /**
     * generate html table using the actual sql statement, for debug purpose only
     * @deprecated
     */
    public function ui_table(){
        $qry = $this->Model->db()->query($this->_sqlstmt);
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



class iterator{
    private $list=array();
    private $selection=array();
    private $found=array();
    private $_querystring="";
    protected $Model;
    private $_itemType="FETCH_ITERATORITEM";
    
    public function __construct(model &$model){
        $this->Model=$model;
        
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
            $temp = $this->Model;
            $this->each(function($item)use(&$method,&$arguments,&$iterator){
                
                //print_r($item);
                $iterator->Model->val($item);
                
                //echo "<br />ICI $temp ".$item->affaire ;
                call_user_func_array(array($iterator->Model, $method), $arguments);
            });
                $iterator->Model->val($temp);
            $temp= explode('\\', get_class($this->Model));
            $temp = array_pop($temp);
            //echo "$temp SLSLSLS";
            
        }
        return $this;
        
    }
    
    
    
    /*
    public function Model(){
        return $this->Model;
    }
    public function model(){
        return $this->Model;
    }*/
    
     
    /**
     * empty all iterator arrays
     * @return \xuserver\v5\iterator
     */
    public function Init(){
        $this->list=array();
        $this->Empty("init");
        return $this;
    }
    
    /**
     * empty selection and found arrays
     * @return \xuserver\v5\iterator
     */
    public function Empty($message=""){
        //echo debug("$message");
        $this->selection=array();
        $this->found=array();
        return $this;
    }
    
    /**
     * fill selection and found arrays with complete list
     * @return \xuserver\v5\iterator
     */
    public function Fill(){
        $this->selection=$this->list;
        $this->found=$this->list;
        return $this;
    }
    
    /**
     * Append an item to the selection and found arrays
     * @param iteratorItem $item
     */
    public function Append( iteratorItem $item ){
        $this->selection[$item->name()]=$item;
        $this->found[$item->name()]=$item;
    }

    /**
     * Remove an item from the selection and found arrays
     * @param iteratorItem $item
     */
    public function Remove(iteratorItem $item){
        unset($this->selection[$item->name()]);
        unset($this->found[$item->name()]);
    }
    
    /**
     * Attach an iteratorItem to its iterator
     * @param iteratorItem $item
     * the item can be a property a relation a method or a DBO::FETCH_OBJ object
     * @return \xuserver\v5\iterator
     */
    public function Attach($item){
        $item->Model = $this->Model;
        if(method_exists($item,"name")){
            $this->_itemType="FETCH_ITERATORITEM";
            $this->list[$item->name()]=$item;
        }else{ // elements are DBO::FETCH_OBJ
            $this->_itemType="FETCH_OBJ";
            //$name= current( (Array)$item ); // first property of stdClass object
            $this->list[]=$item;
        }
        return $this;
    }
    
    /**
     * Detach an iteratorItem to its iterator
     * @param property $prop
     * @return \xuserver\v5\iterator
     */
    public function Detach($item){
        unset($this->list[$item->name()]);
        return $this;
    }
    
    
    
    /**
     * Fetch an item from its iterator, return false if not found
     * @param string $name
     * @return \xuserver\v5\iteratorItem|\xuserver\v5\model|\xuserver\v5\property
     */
    public function Fetch($name="__NULL__"){
        if( is_int($name) or $name===0 ){
            $name="$name";
        }
        if($name=="__NULL__"){
            return $this;
        }
        if(! isset($this->list[$name])){
            return false;
        }
        return $this->list[$name];
    }
    
    public function First(){
        return $this->Fetch(0);
    }
    
    public function Count($context="list"){
        return count($this->$context);
    }
    
    /**
     *
     * @param string $needle the string to find in attributes $by
     * @param string $by name of the meta property on to search
     * @return \xuserver\v5\properties
     */
    public function find($needle="__ALL__",$by="name",$Empty=true){
        
        if($needle=="__ALL__"){
            $this->Fill();
        }else if($needle==""){
            
        }else{
            if($Empty){
                $this->selection=array();
                $this->_querystring = $needle;
            }else{
                $this->_querystring .= ",".$needle;
            }
            
            $needle = explode(",", $needle);
            foreach ($needle as $value) {
                foreach ($this->list as &$p){
                    $value=trim($value);
                    if($value==""){
                        continue;
                    }else{
                        if(strpos($value, "." ) ===0 or strpos($value, "#" ) ===0 ){// starting with "." or #
                            if(".".$p->$by() == $value) {
                                //echo "<h2> searching exact $value : ". $p->$by() . "</h2>";
                                $this->Append($p);
                            }else if("#".$p->$by() == $value) {
                                //echo "<h2> searching exact $value : ". $p->$by() . "</h2>";
                                $this->Append($p);
                            }
                            
                        }else{
                            
                            if(strpos($p->$by(), $value) !==false ){
                                //echo "<h2> found by $by like $value : ". $p->name() . "</h2>";
                                $this->Append($p);
                                
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
    
    function sort($order="__AUTO__",$Context="list"){
        
        if( is_string($order)){
            if($order =="__AUTO__"){
                $order=array_flip(explode(",", str_replace("#","",$this->_querystring)));
                
            }else{
                $order = explode(",", str_replace("#","",$order));
            }
        }
        
        
        if( is_array($order)){
            
            $ordered = array();
            foreach ($order as $key => $val) {
                $val;
                if (array_key_exists($key, $this->$Context)) {
                    $ordered[$key] = &$this->$Context[$key];
                    unset($this->$Context[$key]);
                }
            }
            $this->$Context= $ordered + $this->$Context;

            
        }else if($order!="ksort"){
            
        }else{
            ksort($this->list);
            ksort($this->selection);
            ksort($this->found);
        }
        
        
        return $this;
    }
    
    
    public function then($needle="__ALL__",$by="name"){
        $this->find($needle,$by,false);
        return $this;
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
        
        if( is_callable($closure) ){ // parameter is a closure function
            if(count($context)===0){
                $this->find();
            }
            
            if($this->_itemType=="FETCH_ITERATORITEM"){
                foreach ($context as $item){
                    $closure($item);
                }
            }else if($this->_itemType=="FETCH_OBJ"){
                
                //$index = $this->Model->db_index();
                foreach ($context as $item){
                    $instance = $this->Model->val($item);
                    $closure($instance);
                }
                
            }
            
        }else{
            foreach ($context as $item){
                $item->Read()->$closure();
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
        if(is_null($closure) ){
            
        }else{
            $this->each($closure,"list");
            return $this;
        }
    }

}

class iteratorItem{
    private $Model; // point to the model
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
        if($set===0){
            $this->_value=0;
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




/**
 * ORM Model class
 *
 * @author gael jaunin
 *
 */
class modelPrivate extends iteratorItem{
    
}

/**
 * ORM Model class
 *
 * @author gael jaunin
 *
 */
class modelProtected extends modelPrivate{
    protected function OnBuild() {
        
    }
    
    
    protected function OnFormular($type) {
        
    }
    
    protected function OnSelection() {
        
    }
    
    protected function OnInstance() {
        
    }
    
    protected function OnUpdate() {
        
    }
    
    protected function OnCreate() {
        
    }
    
    protected function OnDelete() {
        
    }
    
}


/**
 * ORM Model class
 *
 * @author gael jaunin
 *
 */
class modelPublic extends modelProtected{
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
     * point to the model itself (= $this) or to the owner model is case of a relation
     * @var \xuserver\v5\model
     */
    private $Model;
    
    
    
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
     * iterator that stores model methods
     * @var methods
     */
    private $_methods;
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
        if(strpos($name, "§")===0 ){ // property
            $name = str_replace ( "§", "", $name);
        }
        
        $property = $this->_properties->Fetch($name);
        if($property==false){
            //$this->debug("Magic property <b>$name</b>  doens't exist");
            $this->$name = $value;
        }else{
            $property->val($value);
            $this->_properties->Append($property);
        }
    }
    
    
    /**
     * 
     * overloading function
     * create o retrieve the property object.
     * otherwise ; set or retrieve the  property value.
     * @param string $name
     * @return \xuserver\v5\property |string
     */
    public function __get($name){
        if(strpos($name, "§")===0 ){ // property
            
            $name = str_replace ( "§", "", $name);
            $property = $this->_properties->Fetch($name);
            
            if($property==false){
                // create a propety
                $property = new property();
                $this->properties()->Attach($property->name("$name"));
                return  $property;
                
            }else{
                return $property;
            }
            
        }else{
            $property = $this->_properties->Fetch($name);
            if($property==false){
                return $this->$name;
            }else{
                return $property->val();
            }
        }
        
        
    }
    
    /**
     *
     * overloading function
     * create o retrieve a method object.
     * otherwise ;
     * @param string $name
     * @return \xuserver\v5\property|string
     */
    public function __call($name, $arguments){
        
        if(strpos($name, "§")===0 ){ // method
            $name = str_replace ( "§", "", $name);
            $method = $this->_methods->Fetch($name);
            if($method==false){
                return $this->_methods;
            }else{
                return $method;
            }
            
        }
        
        if(strpos($name, "_")===0 ){ // relation
            $name = substr($name,1);
            $relation = $this->_relations->Fetch($name);
            if($relation==false){
                echo notify("qszidqaidjaz $name");
                return $this->_relations;
            }else{
                return $relation;
            }
            
        }
    }
    
    /**
     * @todo reflechir a son emploi
     * @return \xuserver\v5\iterator
     */
    public function __invoke(){
        return $this->_iterator;
    }
    
    /**
     * @deprecated
     * @param string $m
     * @return \xuserver\v5\modelPublic|string
     */
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
    
    public function _module(){
        $class= get_class($this);
        $arr=explode("\\", $class);
        return $arr[count($arr)-2];
    }
    
    /**
     * init model, reseting all its internal properties
     */
    public function Init(){
        $this->db_tablename="";
        $this->db_index="";
        $this->db_id=0;
        $this->ui=new model_ui($this);
        $this->sql =  new sql($this);
        $this->_properties =  new properties($this);
        $this->_relations = new relations($this);
        $this->_methods = new methods($this);
        $this->_iterator = new iterator($this);
        $this->state("is_virgin");
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
        //$this->_properties->Empty();
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
     * @return \xuserver\v5\methods
     */
    public function methods(){
        return $this->_methods;
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
            $this->state("is_instance");
            $this->db_id=$set;
            return $this;
        }else{
            return $this->db_id;
        }
        
    }
    
    
    
    /**
     * @deprecated
     * @return string
     */
    public function label(){
        $label="";
        $this->properties()->find("text","type")->each(function(property $prop)use(&$label){
            $label .= " ". $prop->val();
        });
            return $label;
    }
    
}


/**
 * ORM Model class
 *
 * @author gael jaunin
 *
 */
class model extends modelPublic{
    
    private $_title="__EMPTY__";
    
    
    /**
     * build model properties, relations and methods
     * @param string $db_tablename
     * @return \xuserver\v5\model
     */
    public function build($db_tablename="__AUTO__"){
        // destroy current model definition;
        $this->Init();
        
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
                $property->db_name($property_name);
                $property->db_tablename($this->db_tablename);
                
                $this->properties()->Attach($property);
                
                if ($property_key == "PRI") {
                    $this->db_index = $property_name;
                    $property->is_primarykey($property_name);
                    
                }else{
                }
                
                $fkkey=$this->db_tablename."_".$property_name;
                if (isset($this->db->FK[$fkkey])) {
                    $elt=$this->db->FK[$fkkey];
                    $property->is_foreignkey();
                    $property->db_foreigntable($elt["db_tablename"]);
                    $property->db_index($elt["db_index"]);
                    
                }
                
                if (strpos($row["Extra"], "auto_increment") !== false) {
                    
                }else{
                    
                }
                if (isset($row["Comment"])) {
                    $property->comment($row["Comment"]);
                }
                $property->db_default($row["Default"]);
                
            }
            $this->sql()->build();
            $this->properties()->Empty("model.build($this->db_tablename)");
            
            /**
             * RELATIONS
             */
            foreach ($this->db->FK as $row) {
                if($row["db_tablename"]==$this->db_tablename()){ 
                    $relation = new relation();
                    $relation->name($row["table"]);
                    $relation->db_tablename($this->db_tablename());
                    $relation->type($row["index"]);
                    //$relation->val();
                    $this->relations()->Attach($relation);
                }
                //echo "<br/><span style='color:$col'>".$this->db_tablename() . " </span> ".$row["db_tablename"]. "  <span style='color:$col'>".$row["table"]. " </span>.<span style='color:$col'>".$row["db_index"]. " </span> ";
            }
            
            /**
             * METHODS
             */
            $class = new \ReflectionClass($this);
            $methods = $class->getMethods(); // get class properties
            foreach ($methods as $METH) {
                // skip inherited and non public properties
                if ($class->name == "xuserver\\v5\\model" or $METH->isPublic() == 0 or $METH->getDeclaringClass()->getName() !== $class->getName()) {
                    continue;
                }
                // skip methods beginning with "__"
                if ( strpos($METH->name, "__")===0 ) {
                    continue;
                }
                $method = new method();
                $method->name($METH->name);
                $this->methods()->Attach($method);
            }
            $method = new method();
            $method->name("update")->is_selected(1);
            $this->methods()->Attach($method);
            $method = new method();
            $method->name("read")->is_selected(1);
            $this->methods()->Attach($method);
            $method = new method();
            $method->name("create")->is_selected(1);
            $this->methods()->Attach($method);
            $method = new method();
            $method->name("delete")->is_selected(1);
            $this->methods()->Attach($method);
            
            
            //$this->methods()->Empty();
            
            $this->state("is_model");
            //$this->read();
            /**
             * Business Events
             */
            
            $this->OnBuild();
            
            
            
        }else if($driver == 'pgsql'){
            //$mysql_tablestructure = $this->pdo->query("SELECT column_name FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'some_table'");
        }
        
        return $this;
    }
    
    public function buildString($set="__NULL__"){
        if($set!="__NULL__"){
            return xs_encrypt($set) ;
        }else{
            return xs_encrypt($this->db_tablename()."-".$this->db_id()) ;
        }
    }
    
    
    public function title($set="__NULL__"){
        if($set!="__NULL__"){
            $this->_title=$set;
            return $this;
        }else{
            // fetch first text property
            if($this->_title=="__EMPTY__"){
                
            }else{
                return $this->_title;
            }
            
            
            $title=false;
            $titlefk=array();
            $found=0;
            
            $this->properties()->all(function(property $prop)use(&$title,&$found,&$titlefk){
                if(!$prop->is_virtual()){// prop not virtual
                    if($found===0){// title not yet found
                        if($prop->type() =="text" ){// prop is a text
                            $title=$prop; // set title prop
                            $found++;// title is found
                        }
                    }else{// title found
                    }
                }else{ // prop is virtual
                    if($prop->type() =="text" ){// prop is a text
                        $titlefk[]=$prop; // set title prop
                    }
                }
            });
                
            if($title===false){ // no text field
                // fetch all fk textfields
                $title="";
                foreach ($titlefk as $prop){
                    $title.=  $prop->val() . " | ";
                }
                return $title;
            }else{
                // fetch first prop val
                return $title->val();
            }
            /*
            if(is_object($this->_title)){
                return $this->_title->val();
            }else{
            }
            */
        }
    }
    
    /**
     * set or retrieve model property values
     * {@inheritDoc}
     * @see \xuserver\v5\iteratorItem::val($set)
     */
    private $_titleArray = array();
    public function val($values="__NULL__"){
        if(is_null($values)){
            $this->properties()->each(function(property $prop){
                $prop->val(null);
            });
            
        }else if(is_object($values)){
            $this->properties()->each(function(property $prop)use(&$values){
                $name=$prop->name();
                if(isset( $values->$name )){
                    $prop->val( $values->$name );
                }
            });
            
        }else if(is_array($values)){
            $defaultCheckBox="";
            
            if($this->state()=="is_instance"){
                $defaultCheckBox="0";
            }else{
                $defaultCheckBox="";
            }
            /*
             if(count($values) > 0){
             $defaultCheckBox="0";
             }else{
             $defaultCheckBox="";
             }
             */
            
             
            $this->properties()->all(function(property $prop)use(&$values,$defaultCheckBox){
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
            
            
            // gestion des TITLES
            //var_dump($values);
            $cnttitle = 0;
            foreach($values as $key => $val){
                if( strpos($key,XUSERVER_POST_IGNORE) ===0){
                    $cnttitle++;
                    $this->_titleArray[str_replace(XUSERVER_POST_IGNORE,"",$key)]=$val;
                }else{
                    
                }
            }
            if($cnttitle===0){
                $this->_titleArray = array();
            }
            
            
        }else{
            $values = new \stdClass();
            $this->properties()->all(function(property $prop)use(&$values){
                $name=$prop->name();
                $values->$name = $prop->val();
            });
                return $values;
        }
        $this->properties()->Empty();
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
        
        if ($id=="__NULL__" or $id=="0") {
            // $id not given => build sql_read and instanciate selection
            $this->state("is_selection");
            $sql_read=$this->sql()->statement_read_selection();
            $statement = $this->db->execute($sql_read, $this->sql()->values());
            if($statement===false){
                $this->state("is_model");
                echo debug($sql_read);
            }else{
                $selection = $statement->fetchAll(\PDO::FETCH_OBJ);
                $this->iterator()->Init();
                $this->OnSelection();
                foreach ($selection as $instance) {
                    $this->iterator()->Attach($instance);
                }
                
            }
            
        }else{
            // $id given => build sql_read and instanciate object
            $this->db_id($id);
            $sql_read=$this->sql()->statement_read_instance();
            $statement = $this->db->execute($sql_read, $this->sql()->values());
            if($statement===false){
                $this->state("is_model");
                echo debug($sql_read);
            }else{
                $MODEL=$this;
                $instance = $statement->fetchObject();
                $MODEL->val($instance);
                $MODEL->iterator()->Init();
                $MODEL->OnInstance();
                $MODEL->iterator()->Attach($this);
                /**
                 * RELATIONS : settinf fk value
                 */
                $MODEL->relations()->each(function(relation $relation)use(&$MODEL){
                    $relation->val($MODEL->db_id);
                });
            }
        }
        
        $this->properties()->Empty();
        return $this;
    }
    
    public function formular($type=""){
        $this->OnFormular($type);
        return $this->ui->form();
    }
    
    public function button($caption,$method,$bsclass,$title,$uid=""){
        if($uid==""){
            $buildString=$this->buildString();
        }else{
            $buildString=$this->buildString($uid) ; 
        }

        $spanuid= hash("sha256","span-$buildString-$caption");
        return "<a id=\"$spanuid\" href=\"$buildString\" method=\"$method\" class=\"xs-link btn $bsclass\" title=\"$title\" >$caption</a>  ";
    }
    
    /**
     * update model instance or selection
     * @param mixed $values
     */
    public function update($values="__NULL__"){
        if( is_array($values) or is_object($values)){
            $this->val($values);
        }else{
            
        }
        $sql_update=$this->sql()->update()->where()->statement();
        
        $statement = $this->db->execute($sql_update,$this->sql()->values());
        if( $statement ===false ){
            if($this->db->db_error()->getCode()==23000){
                echo notify("<h4>DUPLICATE</h4>".$this->db->db_error()->getMessage());
            }else{
                echo notify($this->db->db_error()->getMessage());
            }
            echo debug($sql_update);
            $return=$this;
        }else{
            $this->upload();
            
            if(count($this->_titleArray)>0){ // XUSERVER_POST_IGNORE
                foreach($this->_titleArray as $key => $val){
                    $this->$key = $val;
                }
                $this->_titleArray = array();
            }
            $this->OnUpdate();
            $this->properties()->Empty();
            $return=$this;
        }
        return $return;
    }
    
    public function disk($create=false){
        //$uri = str_replace('xuserver\\', 'xs-framework\\', get_class($this));
        //$uri=str_replace('\\', '/', $uri);
        $uri = "xs-framework/uploads/".$this->db_tablename()."/".$this->db_id()."/";
        
        $directory= $_SERVER['DOCUMENT_ROOT']. '/'.$uri ;
        
        if (!file_exists($directory) and $create) {
            mkdir($directory, 0777, true);
        }
        $url=str_replace('\\', '/', $uri);
        //echo debug("uri : $uri <br/>directory : $directory <br/> url : $url<br/>");
        $fileObj = new \stdClass();
        $fileObj->directory = $directory;
        $fileObj->uri = $uri;
        $fileObj->url = "/".$url;
        return $fileObj;
        //require $_SERVER["DOCUMENT_ROOT"]."/xs-framework/".str_replace('\\', '/', $class).".php";
    }
    
    /**
     * save uploaded documents on model form submit
     * @todo finish
     * @return \xuserver\v5\modelPublic
     */
    protected function upload() {
        if(count($_FILES)==0){
            return $this;
        }
        $target_dir = $this->disk(true)->directory;
        
        $message="";
        foreach($_FILES as $key => $upload){
            $uploadOk = 1;
            /*
            $max_upload_size = (int)(ini_get('upload_max_filesize'));
            if ($upload["size"] > $max_upload_size) {
                $message .="Upload complete !<br/>";
                $uploadOk = 0;
                continue;
            }*/
            $fileError = $upload["error"];
            switch($fileError) {
                case UPLOAD_ERR_INI_SIZE:
                    $message .="File exceeds " . ini_get('upload_max_filesize') ." max size ($fileError) <br/>";
                    break;
                case UPLOAD_ERR_PARTIAL:
                    $message .="File exceeds max size in html form ($fileError) <br/>";
                    break;
                case UPLOAD_ERR_NO_FILE:
                    //$message .="No file was uploaded ($fileError) <br/>";
                    break;
                case UPLOAD_ERR_NO_TMP_DIR:
                    $message .="No /tmp dir to write to ($fileError) <br/>";
                    break;
                case UPLOAD_ERR_CANT_WRITE:
                    $message .="Error writing to disk ($fileError) <br/>";
                    break;
                default:
                    // No error ";
                    break;
            }
            
            $Key=xs_decrypt($key);
            if ($fileError===0) {
                $target_file = $target_dir .$Key.".".$upload["name"] ;
                if (move_uploaded_file($upload['tmp_name'], $target_file)) {
                    $message .="Upload complete !<br/>";
                }else{
                    
                }
            }
        }
        
        if($message!=""){
            echo notify($message);
        }
        
        return $this;
    }
    
    /**
     * create model instance or selection
     * @param mixed $values
     */
    public function create($values="__NULL__"){
        if( is_array($values) or is_object($values)){
            $this->val($values);
        }else{
            
        }
        
        $sql_insert=$this->sql()->statement_insert();
        
        $statement = $this->db->execute($sql_insert,$this->sql()->values());
        if( $statement ===false ){
            
            if($this->db->db_error()->getCode()==23000){
                echo notify("<h4>DUPLICATE</h4>".$this->db->db_error()->getMessage());
            }else{
                echo notify($this->db->db_error()->getMessage());
            } 
            
            $return=$this;
            
        }else{
            $this->OnCreate();
            $this->properties()->Empty();
            $return = Build($this->db_tablename());
            $return->read($this->db->lastInsertId);
        }
        return $return;
    }
    
    /**
     * delete model instance or selection
     * @param mixed $values
     */
    public function delete($values="__NULL__"){
        if( is_array($values) or is_object($values)){
            $this->val($values);
        }else{
            
        }
        $sql_delete=$this->sql()->statement_delete();
        $statement = $this->db->execute($sql_delete,$this->sql()->values());
        
        if( $statement ===false ){
            echo debug("$sql_delete");
            $return=$this;
        }else{
            $this->OnDelete();
            $this->properties()->Empty();
            $return = Build($this->db_tablename());
            $return->read();
        }
        return $return;
    }
    
}





class properties extends iterator{
    public function select(){
        $this->found();
        $this->Model->sql()->select()->statement();
        $this->Empty("properties.select");
        return $this;
    }
    
    public function where($andor="AND"){
        
        if($this->Model->state()=="is_instance"){
            
        }else{
            $selection = "";
            $this->all(function(property $item)use(&$selection){
                if($item->val() !="" or $item->val() !=null ){
                    $selection.=",#".$item->name();
                }
            });
            $this->find($selection);
        }
        $this->found();
        $this->Model->sql()->where("__AUTO__", $andor)->statement();
        $this->Empty("properties.where");
        return $this;
    }
    
    public function orderby($direction="ASC"){
        $this->found();
        $this->Model->sql()->orderby("__AUTO__",$direction)->statement();
        $this->Empty("properties.orderby");
        return $this;
    }
    
    public function update(){
        $this->found();
        $this->Model->sql()->select()->update();
        $this->Empty("properties.update");
        return $this;
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

class methods extends iterator{
    
    
    
    
    
    
    
}


class propertyPrivate extends iteratorItem{
    
    private $is_disabled=0;
    public function disabled($set=3){
        if($set!=3){
            if($set ==0){$set="0";}else{$set="1";}
            $this->is_disabled=$set;
            return $this;
        }else{
            return $this->is_disabled;
        }
    }
    
    private $_autocomplete="off";
    public function autocomplete($set="__NULL__"){
        if($set!="__NULL__"){
            $this->_autocomplete=$set;
            return $this;
        }else{
            return $this->_autocomplete;
        }
    }
    
    private $is_hidden=0;
    public function hidden($set=3){
        if($set!=3){
            if($set ==0){$set="0";}else{$set="1";}
            $this->is_hidden=$set;
            return $this;
        }else{
            return $this->is_hidden;
        }
    }
    
    private $is_readonly=0;
    public function readonly($set=3){
        if($set!=3){
            if($set ==0){$set="0";}else{$set="1";}
            $this->is_readonly=$set;
            return $this;
        }else{
            return $this->is_readonly;
        }
    }
    
    private $is_required=0;
    public function required($set=3){
        if($set!=3){
            if($set ==0){$set="0";}else{$set="1";}
            $this->is_required=$set;
            return $this;
        }else{
            return $this->is_required;
        }
    }
    
    private $_comment="";
    public function comment($set="__NULL__"){
        if($set!="__NULL__"){
            $this->_comment=$set;
            return $this;
        }else{
            return $this->_comment;
        }
    }
    
    private $_caption="";
    public function caption($set="__NULL__"){
        if($set!="__NULL__"){
            $this->_caption=$set;
            return $this;
        }else{
            if($this->_caption==""){
                return str_replace("_"," ",$this->name());
            }else{
                return $this->_caption;
            }
        }
    }
    
    /**
     * @ deprecated
     * @param string $name
     * @param string $set
     * @return \xuserver\v5\propertyPrivate|unknown
     
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
    */
    
}

class property extends propertyPrivate{
    
    public $ui="";
    
    
    
    private $_values="";
    
    private $_title="no title";
    
    
    
    
    private $_operator="=";
    private $_andor="AND";
    
    
    protected $is_key=0;
    
    
    
    public function __construct(){
        $this->ui=new property_ui($this);
    }
    
    /**
     * retrieve or set the tablename the property belongs to
     * @param string $set
     * @return \xuserver\v5\property|string
     */
    private $db_tablename="";
    public function db_tablename($set="__NULL__"){
        if($set!="__NULL__"){
            $this->db_tablename=$set;
            return $this;
        }else{
            return $this->db_tablename; 
        }
    }
    
    /**
     * the field real name in database
     * @see iteratorItem::name()
     * @param string $set
     * @return \xuserver\v5\property|string
     */
    private $db_name="";
    public function db_name($set="__NULL__"){
        if($set!="__NULL__"){
            $this->db_name=$set;
            return $this;
        }else{
            return $this->db_name;
        }
    }
    
    /**
     * retrieve or set the foreign tablename the property points at 
     * @param string $set
     * @return \xuserver\v5\property|string
     */
    private $db_foreigntable="";
    public function db_foreigntable($set="__NULL__"){
        if($set!="__NULL__"){
            $this->db_foreigntable=$set;
            return $this;
        }else{
            return $this->db_foreigntable;
        }
    }
    
    protected $db_index ="";
    public function db_index($set="__NULL__"){
        if($set!="__NULL__"){
            $this->db_index=$set;
            return $this;
        }else{
            return $this->db_index;
        }
    }
    
    private $db_default="";
    public function db_default($set="__NULL__"){
        if($set!="__NULL__"){
            $this->db_default=$set;
            return $this;
        }else{
            return $this->db_default;
        }
    }
    
    private $db_typelen="";
    /**
     * retrieve or set the field length
     * @param string $set
     * @return \xuserver\v5\property|string
     */
    public function db_typelen($set="__NULL__"){
        if($set!="__NULL__"){
            $this->db_typelen=$set;
            return $this;
        }else{
            return $this->db_typelen;
        }
    }
    
    /**
     * is propery selected in SELECT statement
     * @return \xuserver\v5\property|number|string
     */
    public function is_selected(){
        if(isset($this->Model->sql()->SQL_SELECT()[$this->name()])){
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
        if(isset($this->Model->sql()->SQL_WHERE()[$this->name()])){
            return 1;
        }else{
            return 0;
        }
    }
    
    private $is_virtual=0;
    
    public function is_virtual($set="__NULL__"){
        if($set!="__NULL__"){
            $this->is_virtual=$set;
            return $this;
        }else{
            return $this->is_virtual;
        }
    }
    
    /**
     * is property defined with default value at insert
     * @return number
     */
    public function is_default(){
        if($this->db_default==="" or is_null($this->db_default) ){
            return 0;
        }else{
            return 1;
        }
    }
    
    public function is_primarykey($fieldname="__NULL__"){
        if($fieldname!="__NULL__"){
            //$this->Model->db_index=$fieldname;
            $this->is_key=1;
            $this->type("pk");
            return $this;
        }else{
            if($this->is_key and ($this->type()=="pk" or $this->type()=="hidden")){
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
     * tries to return a model corresponding to foreign key
     * @return \xuserver\v5\model
     */
    public function load(){
        if($this->type()=="fk"){
            $dual=Build($this->db_foreigntable);
            $dual->Model = $this->Model; // property becomes a model, ->Model becomes the calling model
            return $dual;
        }else{
            return $this->Model;
        }
    }
    
    /**
     * tries to return an instance corresponding to foreign key
     * @return \xuserver\v5\model
     */
    public function Read(){
        if($this->type()=="fk"){
            /*
             $dual=Build($this->db_foreigntable);
             $dual->read($this->val());
             $dual->Model = $this->Model;
             */
            $dual = $this->load();
            $dual->read($this->val());
            return $dual;
        }else{
            return $this->Model;
        }
    }
    
    
    
    /**
     * Set property title : 
     * 
     * , if type() is fk, return the title field
     */
    public function title($set="__NULL__"){
        if($set!="__NULL__"){
            $this->_title=$set;
            return $this;
        }else{
            if(is_object($this->_title)){
                return $this->_title->val();
            }else{
                return $this->_title;
            }
        }
    }
    
    public function titlefield(){
        return $this->_title->name();
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
                if( is_null($set) or $set=="" or $set=="0" ){
                    $set = null;
                }else{
                    $this->Model->db_id($set);
                    $this->Model->state("is_instance");
                }
            }
        }else{
        }
        return parent::val($set);
    }
    
    /**
     * 
    textarea text 
    number pk
    radio checkbox
    date
    file camera
    virtual method
    
     */
    
    
    /**
     * set property value for insert statement testing type conformity and using defalut value when necessary
     * @return number|string|\xuserver\v5\iteratorItem
     */
    public function valInsert(){
        $type= $this->type();
        $return= $this->val();
        //$debug ="";
        if($type =="textarea" or  $type =="text" or  $type =="password" or  $type =="email"){
            
            //$debug .= "<br />STRING : ".$this->_name . "($this->_type) value'$this->_value' or default '$this->db_default' => $return" ;
        }else if($type =="number" or  $type =="pk" or $type=="fk"){
            if(is_numeric($return) ){
            }else{
                if($this->is_default() ){
                    $return = $this->db_default();
                }else{
                    $return=0;
                }
            }
            //$debug .= "<br />NUMBER : ".$this->_name . "($this->_type) value'$this->_value' or default '$this->db_default' => $return" ;
            
        }else if($type =="radio" or  $type =="checkbox"){
            if($return =="0" or $return =="1" ){
                
            }else{
                if($this->is_default() ){
                    $return = $this->db_default();
                }else{
                    $return=0;
                }
            }
            //$debug .= "<br />BOOLEAN : ".$this->_name . "($this->_type) value'$this->_value' or default '$this->db_default' => $return" ;
            
            
        }else if($type =="date" ){
            if($this->isDate($return) ){
                
            }else{
                if($this->is_default() ){
                    if( $this->db_default() =="CURRENT_TIMESTAMP"){
                        $return = date("Y-m-d");
                    }else{
                        $return = $this->db_default();
                    }
                    
                }else{
                    $return=date("Y-m-d");
                }
            }
           // $debug .= "<br />DATE : ".$this->_name . "($this->_type) value'$this->_value' or default '$this->db_default' => $return" ;
        }else if($type =="file" or  $type =="camera"){
            
        }else if($type =="virtual" or  $type =="method"){
            
        }
        //$debug;
        return $return;
    }
    
    
    private function isDate($value){
        if (!$value) {
            return false;
        }
        try {
            new \DateTime($value);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
    
    public function like() {
        $this->_operator = "LIKE";
        return $this;
    }
    public function equals() {
        $this->_operator = "=";
        return $this;
    }
    
    
    public function condition() {
        $name= ":where_".$this->name();
        if($this->_operator=="="){
            $condition = $this->_andor. " " . $this->db_tablename().".". $this->db_name()."= $name";
        }else if($this->_operator=="LIKE"){
            if($this->type()=="checkbox" or $this->type()=="number" or $this->type()=="pk" or $this->type()=="fk"){
                $condition = $this->_andor. " " .$this->db_tablename().".". $this->db_name()."=$name";
            }else{
                $condition = $this->_andor. " " . $this->db_tablename().".". $this->db_name()." LIKE CONCAT('%', $name, '%')";
            }
        }
        return $condition;
    }
    
    
    public function and() {
        $this->_andor="AND";
        return $this;
    }
    public function or() {
        $this->_andor="OR";
        return $this;
    }
    
    public function asc() {
        //$list="__AUTO__",$direction="ASC"
        $this->Model->properties()->find(".".$this->name())->orderby("ASC");
    }
    public function desc() {
        //$list="__AUTO__",$direction="ASC"
        $this->Model->properties()->find(".".$this->name())->orderby("DESC");
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
    public function Read(){

        if($this->Model->state()=="is_instance"){
            $tablename = $this->name();
            $dual=Build($tablename);
            $dual->properties()->Fetch($this->_type)->val($this->_value);
            $dual->properties()->select()->where();
            $dual->read();
            $dual->Model = $this->Model;
            return $dual;
        }else{
            
            return $this->Model;
        }
    }
    
    
}



class method extends iteratorItem{
    private $is_selected = 0;
    private $_caption = "__NULL__";
    private $_bsclass = "__NULL__";
    private $_formmethod = "post";

    
    public function __construct(){
        $this->ui=new method_ui($this);
        
    }
    
    
    public function select($set=1){
        $this->is_selected("$set");
        return $this;
    }
    
    public function caption($set="__NULL__"){
        if($set!="__NULL__"){
            $this->_caption=$set;
            return $this;
        }else{
            if($this->_caption=="__NULL__"){
                if($this->_name=="formular"){
                    $this->_caption = "refresh";
                    $this->formmethod("get");
                }else{
                    $this->_caption = trim(str_replace(array("_","form","post")," ",$this->_name));
                }
            }
            return $this->_caption;
        }
        return $this;
    }
    public function bsclass($set="__NULL__"){
        if($set!="__NULL__"){
            $this->_bsclass= "btn-$set $set";
            return $this;
        }else{
            if($this->_bsclass=="__NULL__"){
                if($this->_formmethod =="post"){
                    $this->_bsclass = "btn-primary";
                }else{
                    $this->_bsclass = "btn-secondary";
                }
            }
            return $this->_bsclass;
        }
        return $this;
    }
   
    /*
    public function set_post(){
        $this->_formmethod="post";
    }
    public function set_get(){
        $this->_formmethod="get";
    }
    */
    public function formmethod($set="__NULL__"){
        if($set!="__NULL__"){
            $this->_formmethod=$set;
            return $this;
        }else{
            return $this->_formmethod;
        }
        return $this;
    }
    
    public function is_selected($set="__NULL__"){
        if($set!="__NULL__"){
            $this->is_selected=$set;
            return $this;
        }else{
            return $this->is_selected;
        }
        return $this;
    }
}










class model_ui{
    private $parent="";
    private $_uid="";
    private $_head="";
    private $_footer="";
    public function __construct(model &$parent){
        $this->parent=$parent;
    }
    
    /**
     * set or retrieve the ui id
     * @param string $set
     * @return \xuserver\v5\model_ui|\xuserver\v5\model_ui|string|string
     */
    public function uid($set="__NULL__"){
        if(is_null($set)){
            $this->_uid="__EMPTY__";
            return $this;
        }
        
        if($set!="__NULL__"){
            $this->_uid= xs_encrypt($set);
            return $this;
        }else{
            if($this->_uid==""){
                // encrypt it !
               return $this->uid($this->parent->db_tablename() ."-uid")->uid();
            }else if($this->_uid=="__EMPTY__"){
                return "";
            }else{
                // encrypt it !
               return $this->_uid;
            }
        }
    }
    
    
    public function head($set="__NULL__",$desc=""){
        if($set!="__NULL__"){
            $this->_head="<h3>$set</h3><div class='form-group small'>$desc</div>";
            return $this;
        }else{
            if($this->_head==""){
                return $this->head($this->parent->title(),$this->parent->state() . " ".date("D/M/Y H:i:s") )->head();
            }else{
                return $this->_head;
            }
        }
    }
    public function footer($set="__NULL__"){
        if($set!="__NULL__"){
            $this->_footer="<div >$set</div>";
            return $this;
        }else{
            if($this->_footer==""){
                
            }else{
                return $this->_footer;
            }
        }
    }
    
    public function card($content="",$tag="div",$class=""){
        
        $methods = "";
        $this->parent->methods()->all(function(method $method)use(&$methods){
            if($method->is_selected()){
                $methods.= $method->ui->input();
            };
        });
        
        if($tag=="form"){
            $attrs="method='post' enctype='multipart/form-data'";
            $bsClass="shadow p-3 mb-5 bg-white rounded";
        }else{
            $attrs="";
            $bsClass="bg-grey rounded";
        }
        if($class==""){
            $class=$bsClass;
        }

        return "
        <$tag id=\"".$this->uid()."\" $attrs class='$class' >
            <button type='button' class='close' data-dismiss='alert' aria-label='Close'><span aria-hidden='true'>&times;</span></button>
            ".$this->head()."
            $content
            <div class='form-group'>
                $methods
             </div>
            ".$this->footer()."
        </$tag>
        ";
    }
    
    public function form(){
        if($this->parent->properties()->Count("selection")<1){
            $this->parent->properties()->find("text","type");
        }
        
        $content ="
            <div>
                <input type='hidden' name='model_build' value=\"".$this->parent->buildString()."\" />
                <input type='hidden' name='model_method' value='' />
            </div>";
        
        $this->parent->properties()->all(function (property $prop) use(&$content){
            if($prop->is_primarykey()){
                
            }else if($prop->is_selected() ){
                $content.=$prop->ui->row();
            }
        });
        ;
        
        return $this->card($content,"form");
            
        
    }
    
    public function table(){
        $thead = "<thead>";
        $tbody = "<tbody>";
        $thead .= "<tr>";
        $thead.="<th>action</th>";
        $cntspan = 1;
        
        $this->parent->properties()->each(function($prop)use(&$thead,&$cntspan){
            if($prop->is_selected()){
                if($prop->type()=="password" or $prop->type()=="hidden" ){
                    $thead.="";
                }else{
                    $thead.="<th>".$prop->name()."</th>";
                    $cntspan++;
                }
                
                
            }
        });
        ;
        $thead .= "</tr>";
        
        $cnt = 0;
        $this->parent->iterator()->each(function($item)use(&$tbody,&$cnt){
            $tbody .= "<tr>";
            $db_id=$item->db_id();
            $tbody.="<td><input type='checkbox' class='xs-action' name=\"ids[]\" value=\"$db_id\" /></td>";
            $cnt++;
            $item->properties()->each(function(property $prop)use(&$tbody,&$cnt, &$item){
                if($prop->is_selected()){
                    if($prop->type()=="file"){
                        $tbody.="<td>file</td>";
                    }else if($prop->type()=="checkbox"){
                        if($prop->val()=="1"){
                            $tbody.="<td>Yes</td>";
                        }else{
                            $tbody.="<td>No</td>";
                        }
                    }else if($prop->type()=="password" or $prop->type()=="hidden"){
                        $tbody.="";
                    }else{
                        if($prop->is_primarykey() ){
                            $button = xs_link("", $item->buildString() , "form", "#".$prop->val(), "border-secondary", "");
                            $tbody.="<td>".$button."</td>";
                            
                            //$tbody.="<td>".$item->button("#".$prop->val(), "form", "","")."</td>";
                        }else if($prop->type()=="fk"){
                            $button = xs_link("", $prop->db_foreigntable() ."-".$prop->val(), "form", "#".$prop->val(), "", "");
                            $tbody.="<td>".$button."</td>";
                            
                            
                            //$tbody.="<td>".$prop->val().$item->button("#", "form", "","",$prop->db_foreigntable() ."-".$prop->val())."</td>";
                            //$tbody.="<td><a href='".$prop->db_foreigntable() ."-".$prop->val()."' method='form' class='xs-link' >".$prop->val()."</a></td>";
                        }else{
                            $tbody.="<td>".$prop->val()."</td>";
                        }
                    }
                }
            });
            $tbody .= "</tr>";
        });
        ;
                
        $this->parent->properties()->Empty();
        //$link=$this->parent->db_tablename();
        /*$tfoot = "<tr><th colspan='".($cntspan)."'>$cnt rows
            <a href='$link-0' method='form' class='xs-link' >found</a> |
            <a href='$link-0' method='create' class='xs-link' >create</a> |
            <a href='$link-0' method='delete' class='xs-action' >delete</a>
            </th></tr>";
        */
        $tfoot="<tr><th colspan='".($cntspan)."'>$cnt rows</th></tr>";
        
        return "<div id=\"".$this->uid()."\">
            <button type='button' class='close' data-dismiss='alert' aria-label='Close'><span aria-hidden='true'>&times;</span></button>
            ".$this->head()."
            <table class='table table-stripped table-bordered '>".$thead.$tbody.$tfoot."</table>
            ".$this->footer()."</div>";
    }
    
    public function structure(){
        $session = session();
        if(!$session->admin()){
            return notify("Aide indisponible","light");
        }
        
        $return="";
        $sql = $this->parent->sql()->statement_current();
        
        $table="<tr><th colspan='6'><h2>model <u>".$this->parent->db_tablename()."</u></h2></th></tr>";
        $table.="<tr><th>tablename :</th><td>".$this->parent->db_tablename()."</td><th>state :</th><td>".$this->parent->state()."</td><td></td><td></td></tr>";
        $table.="<tr><th>primary key :</th><td>".$this->parent->db_index()."</td><th>value :</th><td>".$this->parent->db_id()."</td><td></td><td></td></tr>";

        $lines="";
        if($sql==""){
            $sql="SQL clause is empty, model is built BUT not read.";
        }else{
            if($this->parent->iterator()->Count() >0){
                /*
                $this->parent->iterator()->all(function ($item) use (&$lines){
                    $lines .= "<li>".$item->label()."</li>";
                });
                */
            }else{
                $lines="iterator is empty.";
            }
        }
        
        $table.="<tr><th><h3>>iterator :</h3></th><td>".$this->parent->iterator()->Count()." elements</td><td colspan='4'></td></tr>";
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
        
        $date = date("D/M/Y H:i:s");
        
        $return .= "<div id=\"model-structure\"><h1>structure </h1><div class='small'>$date</div><table class='table'>$table</table></div>";
        return $return;
    }
    
    
    
    
    
    
    
}


class property_ui{
    private $parent="";
    public $_values=array();
    
    
    public function __construct(property &$parent){
        $this->parent=$parent;
    }
    
    public function uid(){
        return xs_encrypt("property-".$this->parent->name());
        //return $this->parent->name()."dd";
        
    }
    public function input(){
        
        $required ="";  $disabled =""; $readonly="";
        if ($this->parent->disabled()) {$disabled="disabled='disabled'";}
        if ($this->parent->required()) {$required="required='required'";}
        if ($this->parent->readonly()) {$readonly="readonly='readonly'";}
        
        if ($this->parent->hidden()) {
            $this->parent->type("hidden");
        }
        
        $inputname = xs_encrypt($this->parent->name());
        $uid= $this->uid();
        $type = $this->parent->type();
        $xstype = "xs-type-$type";
        
        
        if ($this->parent->is_primarykey()) {
            $input="<input class='form-control $xstype' type=\"hidden\" $required $disabled name='".$inputname."' id='".$uid."' value=\"".$this->parent->val()."\"  />";
            
        }else if ($type == "textarea") {
            if ($this->parent->val() == "") {$xhtml = "";}else {$xhtml = $this->parent->val();}
            $input = "<textarea class='form-control $xstype' $required $readonly $disabled rows='3' cols='50' autocomplete='false' name='".$inputname."' id='".$uid."'>" . ($xhtml) . "</textarea>";
            
        }else if ($type=="select"){
            $input = "<select class='form-control $xstype' name='".$inputname."' $required $readonly $disabled id='".$uid."'>";
            $options="";
            
            if (is_array($this->_values)) { //given an array of values
                $options.="<option value=\"NULL\" style=\"background: rgba(200, 200, 200, 0.5);font-style:italic\" >...</option>";
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
            
        }else if($type=="radio"  ){
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
            $idd= rand();
            $input1="<input id='$idd-1' class='$xstype' type=\"radio\" $required $readonly $disabled $CHECKED1 name='".$inputname."' value=\"1\" placeholder=\"".$this->parent->comment()."\" /> ";
            $input0="<input id='$idd-0' class='$xstype' type=\"radio\" $required $readonly $disabled $CHECKED0 name='".$inputname."' value=\"0\" placeholder=\"".$this->parent->comment()."\" /> ";
            $inputB="<input id='$idd-B' class='$xstype' type=\"radio\" $required $readonly $disabled $CHECKED name='".$inputname."' value=\"\" placeholder=\"".$this->parent->comment()."\" />";
            
            $input="
            <div class='form-check'>
                $input1
                <label class='form-check-label' for='$idd-1'>Yes</label>
            </div>
            <div class='form-check'>
                $input0
                <label class='form-check-label' for='$idd-0'>No</label>
            </div>
            <div class='form-check'>
                $inputB
                <label class='form-check-label' for='$idd-B'>Both</label>
            </div>                        
        ";
      
        }else if($type=="checkbox" ){
            
                if ($this->parent->val() == "1") {
                    //$input->addAttribute("checked", "checked");
                    $CHECKED = "checked='checked'";
                }else{
                    $CHECKED = "";
                }
                $input="<input class='form-check-input $xstype' type=\"".$type."\" $required $readonly $disabled $CHECKED name='".$inputname."' id='".$uid."' value=\"1\" placeholder=\"".$this->parent->comment()."\" />";
            
            
        }else if($type=="password"){
            $input="<input class='form-control $xstype' type=\"".$type."\" $required $readonly $disabled name='".$inputname."' id='".$uid."' value=\"\" placeholder=\"".$this->parent->comment()."\" />";
        }else if($type=="fk"){
            $dualvalue = $this->parent->title();
            $dualname = xs_encrypt(XUSERVER_POST_IGNORE.$this->parent->titlefield());
            $dual = "<input name=\"$dualname\" value=\"$dualvalue\" class='form-control border-dark' placeholder=\"$dualvalue\" autocomplete='off' />";
            $input="$dual<input class='form-control $xstype' type=\"".$this->parent->db_foreigntable()."\" $required $readonly $disabled name='".$inputname."' id='".$uid."' value=\"".$this->parent->val()."\" placeholder=\"".$this->parent->comment()."\" autocomplete=\"off\"   />";
            
        }else if($type=="file"){
            $url0 = $this->parent->Model->disk()->url.$this->parent->val();
            $url1 = xs_encrypt($url0);
            $url2 = urlencode($url1);
            
            $filepath= $this->parent->Model->disk()->directory.$this->parent->val();
            if(@is_array(getimagesize( $filepath ))){ // an image file
                $img_file = $filepath;
                $imgData = base64_encode(file_get_contents($img_file));
                $src = 'data: '.mime_content_type($img_file).';base64,'.$imgData;
                $thumb = '<img src="'.$src.'" style="max-height:50px" class="img-circle border-info" />';
            }else{
                $fileext = pathinfo($filepath, PATHINFO_EXTENSION);
                if($fileext==""){
                    $thumb = '<span ></span>';
                }else{
                    $thumb = '<span>'.($fileext).'</span>';
                }
                
            }
                
            $input="$thumb<input class='form-control $xstype' type=\"".$type."\" $required $readonly $disabled name='".$inputname."' id='".$uid."' placeholder=\"".$this->parent->comment()."\" autocomplete=\"off\" title=\"". $url2."\"/>";
            
        }else if($type=="display"){
            $input="<span class='form-control $xstype border-light bg-light' >".$this->parent->val()."</span>";
        }else{
            $input="<input class='form-control $xstype' type=\"".$type."\" $required $readonly $disabled name='".$inputname."' id='".$uid."' value=\"".$this->parent->val()."\" autocomplete=\"".$this->parent->autocomplete()."\" placeholder=\"".$this->parent->comment()."\" />";
        }
        //echo notify($this->parent->name() . " " . $this->parent->title());
        
        return $input;
    } 
    
    public function row(){
        $uid= $this->uid();
        if ($this->parent->required()) {$required="*";}else{$required="";}
        if ($this->parent->hidden()) {$this->parent->type("hidden");}
        if($this->parent->is_primarykey()){
            return "
            <div class='form-group'>
                ".$this->parent->ui->input()." 
            </div>"; 
        }else if($this->parent->type()=="checkbox" ){
            $classlabel="form-check-label";
            $classdiv="form-check";
            return "
            <div class='form-group $classdiv'>
                <label class='form-group $classlabel' for='".$uid."'>
                ".$this->parent->ui->input()." ".$this->parent->caption()." $required
                </label>
                <small class='form-text text-muted'>".$this->parent->comment()."</small>
            </div>"; 
            
        }else if($this->parent->type()=="hidden" ){
            return "
            <div class='form-group '>
                ".$this->parent->ui->input()."
            </div>";
        }else{
            $classlabel="";
            $classdiv="";
            return "
            <div class='form-group $classdiv'>
                <label class='form-group $classlabel' for='".$uid."'>".$this->parent->caption()." $required</label>
                ".$this->parent->ui->input()."
                <small class='form-text text-muted'>".$this->parent->comment()."</small>
            </div>";
        }
        
        
           
        
        //return "<div>".$this->parent->type()." ".$this->parent->name()." ".$this->input()." </div>";
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

class method_ui{
    private $parent="";
    public function __construct(method &$parent){
        $this->parent=$parent;
    }
    
    public function input(){
        $method = $this->parent->name();
        $caption = $this->parent->caption();
        $bsclass= $this->parent->bsclass();
        $formmethod = $this->parent->formmethod();
        if($formmethod=="post"){
            return "<input type='submit' method='$method' formmethod='$formmethod' value='$caption' class='btn $bsclass' title='$formmethod $caption' />  ";
        }else{
            //return $this->parent->Model->button($caption,$method,$bsclass,$formmethod);
            
            $href = $this->parent->Model->buildString();
            $title = "$formmethod $caption";
            $uid= "";
            return xs_link($uid, $href, $method, $caption, $bsclass, $title);
        }
    }
}












?>
