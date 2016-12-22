<?php
namespace DavinBao\WorkflowCore\Model;

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

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [];

    public function __construct(array $attributes = []) {
        $this->fill($attributes);
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
                array_push($insertSqlFieldList, $key);
                array_push($insertSqlValueList, $value);
                array_push($updateSqlFieldList, $key . '=\'' . $value . '\'');
            }
        }
        if(count($attributes)<=0 || count($insertSqlFieldList)<=0 || count($insertSqlValueList)<=0 || count($insertSqlFieldList) !== count($insertSqlValueList)){
            return true;
        }

        if($exist){
            $query = 'UPDATE ' . $this->getTableName() . ' SET ' . implode(',', $updateSqlFieldList) . ' WHERE '. $this->primaryKey . '=\'' . $this->getAttribute($this->primaryKey) . '\'';
        } else {
            $query = 'INSERT INTO ' . $this->getTableName() . ' ('. implode(',', $insertSqlFieldList) .') VALUES (' . implode(',', $insertSqlValueList) .')';
        }

        return $this->exec($query);
    }

    /**
     * 创建或更新、删除语句
     * @param $query
     * @return bool
     */
    public function exec($query){
        $ret = $this->getConnection()->exec($query);

        if(!$ret){
            throw new WorkflowException($this->getConnection()->lastErrorMsg());
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
        return $result->fetchArray(SQLITE3_ASSOC);
    }

    /**
     * get one instance by primary key
     * @param $id
     * @return Model
     */
    public static function get($id){
        $model = new Model();
        $result = $model->getConnection()->query('SELECT * FROM ' . $model->getTable() . ' WHERE ' . $model->primaryKey . '=' . $id);
        $result = $result->fetchArray(SQLITE3_ASSOC);
        if(isset($result[0])) {
            $model->fill($result[0]);
            return $model;
        }
        return null;
    }

    public static function newInstance($attributes = []) {
        $model = new static((array) $attributes);

        return $model;
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

