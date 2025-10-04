<?php
abstract class TinyPHP_ActiveRecord {

    // -----------------------------
    // Core Properties
    // -----------------------------
    private static $db;
    private static $hasActiveTransaction = false;
    private $transactionActivatedByCurrentClass = false;
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
    private $applicationIgnoreFields = array('db', 'isEmpty', 'tableName', 'error_list', 'idField', 'ignoreFieldList', 'applicationIgnoreFields', 'dbIgnoreFields', 'tableInfo', 'dbEventListeners', 'updatedRows', 'deletedRows', '__currentAction', '_models' , 'parentModelHandles','transactionActivatedByCurrentClass', 'lazyLoadProperties');
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

        if ($this->tableName != "") {
            $this->tableInfo = self::$db->select("SHOW COLUMNS FROM `{$this->tableName}`");
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

    private function getDbFieldType($_fieldName) {
        $dbFieldType = "";
        foreach ($this->tableInfo as $field) {
            if ($_fieldName == $field->Field) {
                $dbFieldType = $field->Type;
                break;
            }
        }
        return $dbFieldType;
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
            return self::$db->table($tableName)->insertGetId($fieldValues);
        } else {
            return self::$db->table($tableName)->whereRaw($where)->update($fieldValues);
        }
    }

    public static function insertMultiple($tableName, $fields, $data) {
        foreach ($data as $row) {
            $fieldValues = [];
            foreach ($fields as $key => $field) {
                $fieldValues[$field] = $row[$key] ?? "";
            }
            self::$db->table($tableName)->insert($fieldValues);
        }
        return true;
    }

    public static function query($sql, $bind = [], $cached = true) {
        $type = strtolower(substr(trim($sql), 0, 6));

        if ($type === 'select') {
            global $dataCache;
            return $dataCache->getData($sql, $bind, $cached);
        } else if ($type === 'delete') {
            return self::$db->delete($sql, $bind);
        } else {
            return self::$db->statement($sql, $bind);
        }
    }

    public static function getOne($sql, $bind = []) {
        global $dataCache;
        return $dataCache->getOne($sql, $bind, false);
    }

    public static function getCol($sql, $bind = []) {
        $rows = self::$db->select($sql, $bind);
        return array_map(fn($row) => array_values((array)$row)[0], $rows);
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
                $this->addError("Exception occured when creating object of " . $this->tableName . " " . $e->getMessage());
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
            } catch (Exception $e) {
                $this->addError("Exception occured when updating object of " . $this->tableName . " " . $e->getMessage());
            }

            if (!$this->hasErrors()) $this->_notify('afterUpdate');
            return !$this->hasErrors();
        }
    }

    public function delete($whereClause = "") {
        $this->__currentAction = "delete";
        $deleteWhere = $whereClause ?: "id=" . $this->id;

        if ($this->_notify('beforeDelete')) {
            $sql = "DELETE FROM " . $this->tableName . " WHERE " . $deleteWhere;
            $deletedRow = $this->query($sql);
            if ($deletedRow > 0) {
                $this->deletedRows = $deletedRow;
                $this->_notify('afterDelete');
            }
        }
    }

    public function fetchById($id, $field_list = "*", $use_cache = true) {
        $this->__currentAction = "init";
        $sql = "SELECT $field_list FROM " . $this->tableName . " WHERE " . (empty($this->idField) ? "id" : $this->idField) . " = $id LIMIT 0,1";
        $res = $this->query($sql, [], $use_cache);
        if (is_array($res) && count($res) > 0) {
            $this->fillObjectVars($res[0]);
            $this->isEmpty = false;
        }
    }

    public function fetchByProperty($property, $property_value, $field_list = "*", $use_cache = true) {
        $this->__currentAction = "init";
        $where_clause = is_array($property) && is_array($property_value)
            ? implode(' AND ', array_map(fn($k, $v) => "$k='$v'", $property, $property_value))
            : "$property='$property_value'";

        $sql = "SELECT $field_list FROM " . $this->tableName . " WHERE $where_clause LIMIT 0,1";
        $res = $this->query($sql, [], $use_cache);
        if (is_array($res) && count($res) > 0) {
            $this->fillObjectVars($res[0]);
            $this->isEmpty = false;
        }
        $this->init();
    }

    public function getAll($_fields = array(), $filter = "", $order_by = array(), $offset = null, $limit = null, $distinct = false, $use_cache = true) {
        $field_list = empty($_fields) ? "*" : implode(",", $_fields);
        $sql = "SELECT " . ($distinct ? "DISTINCT " : "") . "$field_list FROM {$this->tableName}";

        if (!empty($filter)) $sql .= " WHERE $filter";
        if (!empty($order_by)) $sql .= " ORDER BY " . implode(", ", array_map(fn($k, $v) => "$k $v", array_keys($order_by), $order_by));
        if (!empty($offset)) $sql .= " LIMIT $offset" . (!empty($limit) ? " $limit" : "");

        global $dataCache;
        return $dataCache->getData($sql, [], $use_cache);
    }

    public function refreshById($id) {
        global $dataCache;
        $dataCache->ignoreCache();
        $this->fetchById($id);
        $this->init();
    }

    public function refreshByProperty($property, $property_value, $field_list = "*") {
        global $dataCache;
        $dataCache->ignoreCache();
        $this->fetchByProperty($property, $property_value, $field_list);
    }

    // -----------------------------
    // Transaction Methods
    // -----------------------------
    final public function startTransaction() {
        if (!self::$hasActiveTransaction) {
            self::$db->beginTransaction();
            self::$hasActiveTransaction = true;
            $this->transactionActivatedByCurrentClass = true;
            return true;
        } else {
            $this->transactionActivatedByCurrentClass = false;
            return false;
        }
    }

    final public function commit() {
        if (self::$hasActiveTransaction && $this->transactionActivatedByCurrentClass) {
            self::$db->commit();
            self::$hasActiveTransaction = false;
            $this->transactionActivatedByCurrentClass = false;
        }
    }

    final public function rollback() {
        if (self::$hasActiveTransaction && $this->transactionActivatedByCurrentClass) {
            if ($this->_getCurrentAction() == "create") $this->id = 0;
            self::$db->rollBack();
            self::$hasActiveTransaction = false;
            $this->transactionActivatedByCurrentClass = false;
        }
    }

    final public function hasActiveTransaction() {
        return self::$hasActiveTransaction;
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
        if (empty($index)) array_push($this->error_list, $errorMsg);
        else $this->error_list[$index] = $errorMsg;
    }

    public function addErrors($errors) {
        if (is_array($errors)) foreach ($errors as $err) $this->addError($err);
    }

    public function getErrors($index = null) {
        if (empty($index)) return $this->error_list;
        return $this->error_list[$index] ?? null;
    }

    public function hasErrors() {
        return count($this->getErrors()) > 0;
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
        global $dataCache;
        if (!class_exists($modelClassName, true)) {
            trigger_error("$modelClassName does not exist", E_USER_ERROR);
        }
        if (!$useCache) $dataCache->ignoreCache();
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