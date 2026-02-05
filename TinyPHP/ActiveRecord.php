<?php
abstract class TinyPHP_ActiveRecord {

    // -----------------------------
    // Core Properties
    // -----------------------------
    private static $db;
    //private static $hasActiveTransaction = false;
    //private $transactionActivatedByCurrentClass = false;
    private $transactionLevelAtStart = 0;
    public $isEmpty;
    public $tableName;
    public $id = 0;
    public $idField;
    protected $dbIgnoreFields = [];
    private $error_list = array();
    private $tableInfo;
    private $dbEventListeners = array();
    private $updatedRows = 0;
    private $deletedRows = 0;
    private $__currentAction = "";
    private $_models = array();
    private $parentModelHandles = array();
    public $objectVars = [];
    private $applicationIgnoreFields = array('db', 'isEmpty', 'tableName', 'error_list', 'idField', 'ignoreFieldList', 'applicationIgnoreFields', 'dbIgnoreFields', 'tableInfo', 'dbEventListeners', 'updatedRows', 'deletedRows', '__currentAction', '_models' , 'parentModelHandles','transactionLevelAtStart', 'lazyLoadProperties', 'objectVars');
    private $ignoreFieldList = array();
    private $lazyLoadProperties = array();

    const DB_EXECMODE_INSERT  = 1;
    const DB_EXECMODE_UPDATE  = 2;
    const DB_FETCHMODE_ASSOC  = 2;
    const DB_FETCHMODE_OBJECT = 5;

    // -----------------------------
    // Constructor / Initialization
    // -----------------------------
    final public function __construct($id = 0, $use_cache = true) {
        
        global $db;
        self::$db = &$db;

        $this->objectVars = get_object_vars($this);

        /*var_dump($this->emptyObj->stock_tracking_method);
        die;*/
        

        if ($this->tableName != "") {
            $this->tableInfo = self::$db->query("SHOW COLUMNS FROM `{$this->tableName}`");
        }

        $this->isEmpty = true;

        if ($id != 0) {
            $this->fetchById($id, '*', $use_cache);
        }

        $this->init();
    }

    abstract public function init();

    private function getter($property, &$value) {
        if (method_exists($this, 'lazyLoadProperty') && in_array($property, $this->lazyLoadProperties)) {
            return $value = $this->lazyLoadProperty($property);
        }
    }

    public function __get($property) {
        $value = null;
        if (is_null($this->getter($property, $value))) {
            trigger_error(get_class($this) . " GET Error: Undefined property $property", E_USER_NOTICE);
        } else {
            return $value;
        }
    }

    public function __set($property, $value) {
        trigger_error(get_class($this) . " SET Error: Undefined property $property", E_USER_NOTICE);
    }

    public function addLazyLoadProperty($prop) {
        array_push($this->lazyLoadProperties, $prop);
    }

    // -----------------------------
    // Database / Execution Methods
    // -----------------------------
    public function getDB() {
        return self::$db;
    }


    public function execute($tableName, $fields = [], $mode = self::DB_EXECMODE_INSERT, $where = "") {

        $fieldValues = [];
        $this->ignoreFieldList = array_merge($this->applicationIgnoreFields, $this->dbIgnoreFields);

        if (count($fields) == 0) {
            $objectVars = get_object_vars($this);

            foreach ($objectVars as $key => $val) {


                 // Skip ignored fields
                if (in_array($key, $this->ignoreFieldList)) {
                    continue;
                }

                $value = $this->{$key};
                if( is_array($value) ) {
                    $value = json_encode($value, JSON_UNESCAPED_UNICODE);
                } else if( !is_null($value) ) {
                    $value = stripslashes($value);
                }
                
                $fieldValues[$key] = $value;

            }
        } else {
            foreach ($fields as $fieldName) {

                $value = $this->{$fieldName};
                if( is_array($value) ) {
                    $value = json_encode($value, JSON_UNESCAPED_UNICODE);
                } else if( !is_null($value) ) {
                    $value = stripslashes($value);
                }

                $fieldValues[$fieldName] = $value;
            }
        }

        if ($mode == self::DB_EXECMODE_INSERT) {
            return self::$db->insert($tableName, $fieldValues);
        } else {
            return self::$db->update($tableName, $fieldValues, $where);
        }
    }

    public static function firstOrCreate() {
        
    }

    public static function insertMultiple($tableName, $fields, $data) {

        foreach ($data as $row) {
            
            $fieldValues = [];
            foreach ($fields as $key => $field) {
                $fieldValues[$field] = $row[$key] ?? "";
            }

            self::$db->insert($tableName, $fieldValues);
        }

        return true;
    }

    public static function query($sql, $bind=[], $cached=true) {
        
        $type = strtolower(substr(trim($sql), 0, 6));

        if ($type === 'select') {
            
            global $dataCache;
            return $dataCache->getData($sql, $bind, $cached);

        } else {
            
            return self::$db->query($sql, $bind);
        }
    }

    public static function getOne($sql, $bind=[], $cached=true) {
        
        global $dataCache;
        return $dataCache->getOne($sql, $bind, $cached);
    }

    public static function getCol($sql, $bind=[], $cached=true) {
        
        global $dataCache;
        return $dataCache->getCol($sql, $bind, $cached);
    }

    public static function getVar($sql, $bind=[], $cached=true) {
        
        global $dataCache;
        return $dataCache->getVar($sql, $bind, $cached);
    }


    // -----------------------------
    // CRUD Methods
    // -----------------------------
    public function create() {
        
        $this->__currentAction = "create";
        $result = $this->_notify('beforeCreate');
        if ($result) {
            try {
                
                $this->id = $this->execute($this->tableName, [], self::DB_EXECMODE_INSERT);
                if ($this->id > 0) $this->_notify('afterCreate');

            } catch (Exception $e) {

                $this->error_list = []; // reset any other errors
                $this->addError("Exception occured when creating object of " . $this->tableName . " " . $e->getMessage(), "_database");
            }
        }
        return $this->hasErrors() ? 0 : $this->id;
    }


    public function update($fields = []) {
        
        $this->__currentAction = "update";
        $result = $this->_notify('beforeUpdate');
        
        if ($result) {
            
            $whereClause = "id=" . $this->id;
            
            try {
                
                $this->updatedRows = $this->execute($this->tableName, $fields, self::DB_EXECMODE_UPDATE, $whereClause);
                $this->_notify('afterUpdate');

            } catch (Exception $e) {

                $this->error_list = []; // reset any other errors
                $this->addError("Exception occured when updating object of " . $this->tableName . " " . $e->getMessage(), "_database");
            }

            return !$this->hasErrors();
        }
    }

    public function delete($whereClause = "") {
        
        $this->__currentAction = "delete";
        $deleteWhere = $whereClause ?: "id=" . $this->id;

        if ($this->_notify('beforeDelete')) {


            try {

                $deletedRow = self::$db->delete($this->tableName, $deleteWhere);
                //$sql = "DELETE FROM " . $this->tableName . " WHERE " . $deleteWhere;
                //$deletedRow = $this->query($sql);
                if ($deletedRow > 0) {
                    $this->deletedRows = $deletedRow;
                    $this->_notify('afterDelete');
                }

            } catch (Exception $e) {

                $this->error_list = []; // reset any other errors
                $this->addError("Exception occured when deleting object of " . $this->tableName . " " . $e->getMessage(), "_database");
            }
            
        }
    }

    public function fetchById($id, $fieldList = "*", $cached=true) {
        
        $this->__currentAction = "init";
        
        $sql = "SELECT $fieldList FROM " . $this->tableName . " WHERE " . (empty($this->idField) ? "id" : $this->idField) . " = ? LIMIT 0,1";        
        $res = $this->getOne($sql, [$id], $cached);
        
        if ($res) {

            $this->fillObjectVars($res);
            $this->isEmpty = false;
        }
    }

    public function fetchByProperty($property, $propertyValue, $fieldList = "*", $cached=true) {
        
        $this->__currentAction = "init";

        // --- Build WHERE clause and bindings ---
        if (is_array($property) && is_array($propertyValue)) {

            $conds = [];
            $bindings = [];

            foreach ($property as $i => $col) {
                
                $val = $propertyValue[$i] ?? null;

                // If value is array, use IN
                if (is_array($val)) {
                    $placeholders = implode(',', array_fill(0, count($val), '?'));
                    $conds[] = "`$col` IN ($placeholders)";
                    foreach ($val as $v) $bindings[] = $v;
                } else {
                    $conds[] = "`$col` = ?";
                    $bindings[] = $val;
                }
            }

            $whereClause = implode(' AND ', $conds);

        } else {
            
            $whereClause = "`$property` = ?";
            $bindings = [$propertyValue];
        }

        // --- Build SQL ---
        $sql = "SELECT $fieldList FROM `{$this->tableName}` WHERE $whereClause LIMIT 0,1";
        $res = $this->getOne($sql, $bindings, $cached);

        if ($res) {

            $this->fillObjectVars($res);
            $this->isEmpty = false;
        }

        $this->init();
    }

    public function getAll(array $fields=[], array $filters=[], array $orderBy=[], int|null $offset=null, int|null $limit = null, bool $distinct=false, bool $cached = true) {
        
        $fieldList = empty($fields) ? "*" : implode(",", $fields);
        $sql = "SELECT " . ($distinct ? "DISTINCT " : "") . "$fieldList FROM `{$this->tableName}`";

        $bindings = [];
        if (!empty($filters))
        {
            $conditions = [];
            foreach ($filters as $col => $val) {
                if (is_array($val)) {
                    $placeholders = implode(',', array_fill(0, count($val), '?'));
                    $conditions[] = "`$col` IN ($placeholders)";
                    $bindings = array_merge($bindings, $val);
                } else {
                    $conditions[] = "`$col` = ?";
                    $bindings[] = $val;
                }
            }
            $sql .= " WHERE " . implode(" AND ", $conditions);
        }

        if (!empty($orderBy)) {
            $orders = [];
            foreach ($orderBy as $col => $dir) {
                $orders[] = "`$col` " . strtoupper($dir);
            }
            $sql .= " ORDER BY " . implode(", ", $orders);
        }

        if ($limit !== null) {
            $sql .= " LIMIT " . ($offset ?? 0) . ", $limit";
        }

        global $dataCache;
        return $dataCache->getData($sql, $bindings, $cached);
    }

    public function refreshById($id) {

        //global $dataCache;
        //$dataCache->ignoreCache();

        $this->fetchById($id, "*", false);
        $this->init();
    }

    public function refreshByProperty($property, $propertyValue, $fieldList = "*") {
        
        //global $dataCache;
        //$dataCache->ignoreCache();
        
        $this->fetchByProperty($property, $propertyValue, $fieldList, false);
    }

    public function toArray() {
        
        $ignore = array_merge($this->applicationIgnoreFields, $this->dbIgnoreFields);
        $result = [];

        foreach (get_object_vars($this) as $key => $val) {
            if (!in_array($key, $ignore)) {
                $result[$key] = $val;
            }
        }

        return $result;
    }

    // -----------------------------
    // Transaction Methods
    // -----------------------------
    final public function startTransaction() {
        
        $currentLevel = self::$db->transactionLevel();

        // Start transaction only if none exists
        if ($currentLevel === 0) {
            self::$db->startTransaction();
        }

        // Remember the level at which this object entered
        $this->transactionLevelAtStart = $currentLevel + 1;

        return true;

        /*
        if (!self::$hasActiveTransaction) {
            
            self::$db->startTransaction();
            self::$hasActiveTransaction = true;
            $this->transactionActivatedByCurrentClass = true;
            return true;

        } else {
            
            $this->transactionActivatedByCurrentClass = false;
            return false;
        }
        */
    }

    final public function commit() {

        $currentLevel = self::$db->transactionLevel();

        // Commit ONLY if this instance owns the top transaction
        if ($currentLevel === $this->transactionLevelAtStart) {
            self::$db->commit();
        }

        $this->transactionLevelAtStart = 0;

        /*
        if (self::$hasActiveTransaction && $this->transactionActivatedByCurrentClass) {
            self::$db->commit();
            self::$hasActiveTransaction = false;
            $this->transactionActivatedByCurrentClass = false;
        }
        */
    }

    final public function rollback() {
        
        $currentLevel = self::$db->transactionLevel();

        // Rollback only if this instance owns the transaction
        if ($currentLevel >= $this->transactionLevelAtStart && $this->transactionLevelAtStart > 0) {
            self::$db->rollBack();
        }

        if ($this->_getCurrentAction() === 'create') {
            $this->id = 0;
        }

        $this->transactionLevelAtStart = 0;


        /*
        if (self::$hasActiveTransaction && $this->transactionActivatedByCurrentClass) {
            if ($this->_getCurrentAction() == "create") $this->id = 0;
            self::$db->rollBack();
            self::$hasActiveTransaction = false;
            $this->transactionActivatedByCurrentClass = false;
        }
        */
    }

    final public function hasActiveTransaction() {
        
        return self::$db->transactionLevel() > 0;
        //return self::$hasActiveTransaction;
    }


    // -----------------------------
    // Event / Listener Methods
    // -----------------------------
    public function addListener($event, $call_back, $params = []) {
        $this->dbEventListeners[$event] = ['call_back' => $call_back, 'params' => $params];
    }

    private function _notify($event) {
        
        if (!isset($this->dbEventListeners[$event])) return true;
        
        $eventSubscriber = $this->dbEventListeners[$event];
        $callback = $eventSubscriber['call_back'] ?? null;
        $params = $eventSubscriber['params'] ?? [];
        
        return is_callable($callback) ? $callback(...(array)$params) : false;
    }

    public function attachEventHandler($_eventName, $_handlerClass) {
        
        $appEvt = TinyPHP_AppEvent::getInstance();
        $appEvt->attachHandler($_eventName, $_handlerClass);
    }

    // -----------------------------
    // Error Handling
    // -----------------------------
    public function addError($errorMsg, $index = null) {
        
        if (empty($index)) {
            $this->error_list[] = $errorMsg;
        } else {
            $this->error_list[$index] = $errorMsg;
        }
    }

    public function addErrors($errors) {
        
        if (is_array($errors)) {
            
            foreach ($errors as $key => $err) {                
                if( is_numeric($key) ) {$key = null;}
                $this->addError($err, $key);
            }
        }
    }

    public function getErrors($index = null) {
        
        if (empty($index)) {

            if( $this->getErrorType() != "validation" ) {
                return array_values($this->error_list);
            }

            return $this->error_list;
        }

        return $this->error_list[$index] ?? null;
    }

    public function hasErrors() {
        
        return count($this->error_list) > 0;
    }

    public function getErrorType() {
        
        if( !$this->hasErrors() ) return "";

        $type = "validation";
        $errors = $this->error_list;
        if( isset($errors["_database"]) ) {
            $type = "database";
        }

        return $type;
    }


    public function getErrorMessage() {
        
        $errorType = $this->getErrorType();
        if( $errorType == "" ) return "";        

        $msg = "Validation failed";
        if( $errorType == "database" ) {
            $msg = "Database error occurred";
        }

        return $msg;
    }


    public function getErrorCode() {

        $errorType = $this->getErrorType();
        if( $errorType == "" ) return "";        

        $code = 422;
        if( $errorType == "database" ) {
            $code = 500;
        }

        return $code;
    }


    // -----------------------------
    // Utility Methods
    // -----------------------------
    private function fillObjectVars($row) {

        foreach ($row as $key => $val) {
            if ($val !== null && property_exists($this, $key)) {
                $this->{$key} = $val;
            }
        }
    }

    public function objectsToArray($objects, $fields) {
        return array_map(fn($obj) => array_map(fn($f) => $obj->$f, $fields), $objects);
    }

    public function fieldList($object, $ignoreFields = [], $fieldPrefix = "") {
        
        $fields = array_keys(get_object_vars($object));
        $fields = array_diff($fields, $ignoreFields);
        
        if ($fieldPrefix) $fields = array_map(fn($f) => $fieldPrefix . $f, $fields);
        return implode(",", $fields);
    }

    public function getPostData($ignoreFields = []) {
        if ($_SERVER['REQUEST_METHOD'] === "POST") {
            foreach ($_POST as $key => $val) {
                if (!in_array($key, $ignoreFields) && property_exists($this, $key)) {
                    $this->{$key} = $val;
                }
            }
        }
    }

    public function fillFromRequest($request, $ignoreFields=[]) {
        
        $inputs = $request->getInputs();
        foreach($inputs as $key => $val) {
            
            if (!in_array($key, $ignoreFields) && property_exists($this, $key)) {

                $definedNull = array_key_exists($key, $this->objectVars) && is_null($this->objectVars[$key]) ? true : false;
                $this->{$key} = $val ? $val : ($definedNull ? null : "");
            }
        }
    }

    public function fillFromArray(array $data, array $ignoreFields=[]) {
        
        foreach($data as $key => $val) {
            
            if (!in_array($key, $ignoreFields) && property_exists($this, $key)) {

                $definedNull = array_key_exists($key, $this->objectVars) && is_null($this->objectVars[$key]) ? true : false;
                $this->{$key} = $val ? $val : ($definedNull ? null : "");
            }
        }
    }

    public function getDeletedRows() {
        return $this->deletedRows;
    }

    public function getUpdatedRows() {
        return $this->updatedRows;
    }

    // -----------------------------
    // Parent / Related Model Loading
    // -----------------------------
    public function addParentModel($modelHandle, $modelClass, $fkName) {
        if (!array_key_exists($modelHandle, $this->parentModelHandles)) {
            $this->parentModelHandles[$modelHandle] = ['model' => $modelClass, 'fk' => $fkName];
        }
    }

    public function loadParentModel($_modelHandle, $_idValue = 0, $_property = 'id', $_cached = true) {
        if (!array_key_exists($_modelHandle, $this->parentModelHandles)) {
            trigger_error('Invalid model handle ' . $_modelHandle, E_USER_ERROR);
            return;
        }

        $modelClassName = $this->parentModelHandles[$_modelHandle]['model'];
        $idValue = $_idValue ?: $this->{$this->parentModelHandles[$_modelHandle]['fk']};
        $modelKey = md5($modelClassName . "_" . $idValue . "_" . $_property);

        if ($_cached && array_key_exists($modelKey, $this->_models)) return $this->_models[$modelKey];

        $obj = new $modelClassName();
        $obj->fetchByProperty($_property, $idValue, '*', false);
        $this->_models[$modelKey] = $obj;
        return $obj;
    }

    public function loadModel($modelClassName, $property, $propertyValue, $useCache = true) {
        //global $dataCache;
        if (!class_exists($modelClassName, true)) {
            trigger_error("$modelClassName does not exist", E_USER_ERROR);
        }
        //if (!$useCache) $dataCache->ignoreCache();
        $obj = new $modelClassName();
        $obj->fetchByProperty($property, $propertyValue);
        return $obj;
    }

    // -----------------------------
    // Misc Helpers
    // -----------------------------
    public function _getCurrentAction() {
        return $this->__currentAction;
    }
}
?>