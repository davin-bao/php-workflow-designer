<?php
namespace DavinBao\WorkflowCore\Models;

use DavinBao\WorkflowCore\Config;
use DavinBao\WorkflowCore\Exceptions\WorkflowException;
use PDO;

/**
 * Engine Class
 *
 * @class  Activity
 */
class Model {

    protected $connection = null;

    protected $primaryKey = 'id';

    /**
     * The model's attributes.
     *
     * @var array
     */
    protected $attributes = [];

    protected $dates = ['created_at', 'updated_at'];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [];

    protected static $unguarded = false;

    public function __construct(array $attributes = []) {
        $this->fill($attributes);
    }

    protected function getTableName(){
        return strtolower(basename(get_called_class()));
    }

    /**
     * 连接 SQLITE
     * @return null|PDO
     */
    public function getConnection(){
        if(is_null($this->connection)){
            $dbFile = Config::get('db_path');
            $this->connection = new PDO('sqlite:' . $dbFile);
            // Set errormode to exceptions
            $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        }
        return $this->connection;
    }

    /**
     * 保存
     * @param array $attributes
     * @param bool $exist
     * @return bool
     */
    public function save(array $attributes = [], $exist = false){

        $insertSqlFieldList = [];
        $insertSqlValueList = [];
        $updateSqlFieldList = [];

        foreach($attributes as $key=>$value){
            if(in_array($key, $this->fillable)){
                if(in_array($key, $this->dates)){
                    $value = $this->asDateTime($value);
                }
                array_push($insertSqlFieldList, $key);
                array_push($insertSqlValueList, '\'' . $value . '\'');
                array_push($updateSqlFieldList, $key . '=\'' . $value . '\'');
            }
        }
        if(in_array('created_at', $this->dates)){
            array_push($insertSqlFieldList, 'created_at');
            array_push($insertSqlValueList, '\'' . $this->asDateTime(new \DateTime('now')) . '\'');
        }
        if(count($attributes)<=0 || count($insertSqlFieldList)<=0 || count($insertSqlValueList)<=0 || count($insertSqlFieldList) !== count($insertSqlValueList)){
            return true;
        }

        if($exist){
            $query = 'UPDATE ' . $this->getTableName() . ' SET ' . implode(',', $updateSqlFieldList) . ' WHERE '. $this->primaryKey . '=\'' . $this->getAttribute($this->primaryKey) . '\'';
        } else {
            $query = 'INSERT INTO ' . $this->getTableName() . ' ('. implode(',', $insertSqlFieldList) .') VALUES (' . implode(',', $insertSqlValueList) .')';
        }

        $this->exec($query);
        if(!$exist){
            $id = $conn = $this->getConnection()->lastInsertId();
            $id = is_numeric($id) ? (int) $id : $id;
            $modelName = get_called_class();
            return $modelName::get($id);
        }
        return $this;
    }

    protected function asDateTime($value){
        if ($value instanceof \DateTime) {
            return $value->format('Y-m-d H:i:s');
        }if (is_numeric($value)) {
            return (new \DateTime($value))->format('Y-m-d H:i:s');
        }if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $value)) {
            return $value . ' 00:00:00';
        }
        return $value;
    }

    /**
     * 创建或更新、删除语句
     * @param $query
     * @return bool
     */
    public function exec($query){
        $conn = $this->getConnection();
        $ret = $conn->exec($query);

        if($ret === false){
            throw new WorkflowException($conn->lastErrorMsg());
        } else {
            return true;
        }
    }

    /**
     * 查询语句
     * @param $query
     * @return mixed
     */
    public function query($query){
        $result = $this->getConnection()->query($query);
        return $result->fetchAll();
    }

    /**
     * get one instance by primary key
     * @param $id
     * @return Model
     */
    public static function get($id){
        $model = new static();
        $result = $model->getConnection()->query('SELECT * FROM ' . $model->getTableName()  . ' WHERE ' . $model->primaryKey . '="' . $id . '"');

        $result = $result->fetchAll();
        if(isset($result[0])) {
            $model->fill($result[0]);
            return $model;
        }
        return null;
    }

    /**
     * Get the fillable attributes of a given array.
     *
     * @param  array  $attributes
     * @return array
     */
    protected function fillableFromArray(array $attributes) {
        if (count($this->fillable) > 0 && ! static::$unguarded) {
            return array_intersect_key($attributes, array_flip($this->fillable));
        }

        return $attributes;
    }

    public function fill(array $attributes) {
        foreach ($this->fillableFromArray($attributes) as $key => $value) {
            $this->setAttribute($key, $value);
        }
        return $this;
    }

    public function getAttribute($key) {
        return isset($this->attributes[$key]) ? $this->attributes[$key] : null;
    }

    public function setAttribute($key, $value) {
        $this->attributes[$key] = $value;

        return $this;
    }

    /**
     * Dynamically retrieve attributes on the model.
     *
     * @param  string  $key
     * @return mixed
     */
    public function __get($key) {
        return $this->getAttribute($key);
    }

    /**
     * Dynamically set attributes on the model.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return void
     */
    public function __set($key, $value) {
        $this->setAttribute($key, $value);
    }
}

