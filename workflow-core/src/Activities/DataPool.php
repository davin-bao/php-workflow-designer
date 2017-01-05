<?php
namespace DavinBao\WorkflowCore\Activities;

use DavinBao\WorkflowCore\Exceptions\FlowParseException;
use DavinBao\WorkflowCore\Flows\Flow;
use DOMElement;

/**
 * Event Class
 *
 * @class  Event
 */
trait DataPool {

    //输入参数列表, 生成模板时使用
    protected $inParameterKeys = [];
    //输出参数列表，生成模板时使用
    protected $outParameterKeys = [];
    //属性及数据缓存
    private $attributes = [];

    public function getAttribute($key) {
        if(!isset($this->attributes[$key])){
            throw new FlowParseException('活动(tagName:' .$this->label. ', ID:' .$this->id. ') 未设置输出参数 ' .$key. ' 的值');
        }
        return $this->attributes[$key];
    }

    public function setAttribute($key, $value) {
        $this->attributes[$key] = $value;

        return $this;
    }

    public function hasAttribute($key){
        return isset($this->attributes[$key]);
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

    /**
     * 初始化参数属性
     * @param DOMElement $xmlDoc
     * @throws FlowParseException
     */
    public function initAttributes(DOMElement $xmlDoc){
        $inParameters = [];
        $outParameters = [];
        foreach($xmlDoc->attributes as $xmlAttribute){
            $xmlAttributeName = $xmlAttribute->nodeName;
            $xmlAttributeValue = $xmlAttribute->nodeValue;

            if(strpos($xmlAttributeName, 'in') === 0){
                if(!($this instanceof Flow) && !in_array($xmlAttributeName, $this->inParameterKeys)){
                    throw new FlowParseException('活动(tagName:' .$xmlDoc->tagName. ', ID:' .$this->id. ') 未定义输入参数 ' .$xmlAttributeName. ' ');
                }
                //获取输入参数的节点名称， 根据输入的节点名称声明本地的属性名称，并存入以该节点值为key 的 $parameters 中的值
                $inParameters[$xmlAttributeName] = $xmlAttributeValue;
            }elseif(strpos($xmlAttributeName, 'out') === 0){
                if(!($this instanceof Flow) && !in_array($xmlAttributeName, $this->outParameterKeys)){
                    throw new FlowParseException('活动(tagName:' .$xmlDoc->tagName. ', ID:' .$this->id. ') 未定义输出参数 ' .$xmlAttributeName. '');
                }
                //获取输入参数的节点名称， 根据输入的节点名称声明本地的属性名称，并存入以该节点值为key 的 $parameters 中的值
                $outParameters[$xmlAttributeName] = $xmlAttributeValue;
            }else{
                $this->$xmlAttributeName = $xmlAttributeValue;
            }
        }

        $this->inParameterKeys = $inParameters;
        $this->outParameterKeys = $outParameters;
    }

    /**
     * 根据输入参数 KEY 到实际数据列表中取值， 存入当前对象的属性中
     *
     * @param array $parameters 实际数据列表
     * @throws FlowParseException
     */
    public function setInAttributes(array $parameters = []){
        $this->parameters = $parameters;
        foreach($this->inParameterKeys as $key => $value){
            if(!isset($parameters[$value])){
                throw new FlowParseException('活动(tagName:' .$this->label. ', ID:' .$this->id. ') 未传入参数 ' .$value. '');
            }
            //获取输入参数的节点名称， 根据输入的节点名称声明本地的属性名称，并存入以该节点值为key 的 $parameters 中的值
            $this->$key = $parameters[$value];
        }
    }

    /**
     * 根据输出参数 KEY 到当前对象的属性中取值，存入数组返回
     *
     * @return array 输出参数键值对
     * @throws FlowParseException
     */
    public function getOutAttributes(){
        $parameters = [];
        foreach($this->outParameterKeys as $key => $value) {
            $parameters[$value]= $this->$key;
        }
        return $parameters;
    }
}

