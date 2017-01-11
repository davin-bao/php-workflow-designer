<?php
namespace DavinBao\WorkflowCore;

use DavinBao\WorkflowCore\Activities\SubFlowActivity;
use DavinBao\WorkflowCore\Exceptions\FlowParseException;
use DavinBao\WorkflowCore\Flows\Flow;
/**
 * Connector Base Class
 *
 * @class  Activity
 */
abstract class Node implements NodeInterface {

    public $id = null;
    public $href = '';
    public $label = '';
    protected $parent = 1;
    protected $x = 0;
    protected $y = 0;
    protected $width = 32;
    protected $height = 32;

    //输入参数列表, 生成模板时使用
    protected $inParameterKeys = [];
    //输出参数列表，生成模板时使用
    protected $outParameterKeys = [];
    //属性及数据缓存
    private $attributes = [];
    //xml 模板
    protected $xmlDoc = null;

    /**
     * 获取属性值
     * @param $key
     * @param null $default 如果参数为 null， 且未设定该属性，则抛异常
     * @return mixed
     * @throws FlowParseException
     */
    public function getAttribute($key, $default = null) {
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

    public function removeAttribute($key){
        if($this->hasAttribute($key)) unset($this->attributes[$key]);
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

    protected function getDocAttribute($attributeName){
        if(!$this->xmlDoc->hasAttribute($attributeName)){
            throw new FlowParseException($this->getCnName() . '(ID:' . $this->getDocAttribute('id') . ') 未定义 ' .$attributeName. ' 属性');
        }
        return $this->xmlDoc->getAttribute($attributeName);
    }
    protected function getDocElementsByTagName($tagName){
        $elements = $this->xmlDoc->getElementsByTagName($tagName);
        if(!$elements || $elements->length <= 0){
            throw new FlowParseException($this->getCnName() . '(ID:' . $this->getDocAttribute('id') . ') 未定义子节点 ' . $tagName);
        }
        return $elements;
    }

    protected function initAttributes(){
        //解析顶级属性
        $inParameters = [];
        $outParameters = [];
        //从xml文档中读取参数值
        foreach($this->xmlDoc->attributes as $xmlAttribute){
            $xmlAttributeName = $xmlAttribute->nodeName;
            $xmlAttributeValue = $xmlAttribute->nodeValue;
            if(strpos($xmlAttributeName, 'in') === 0){
                if(($this instanceof Flow) || key_exists($xmlAttributeName, $this->inParameterKeys)){
                    //获取输入参数的节点名称， 根据输入的节点名称声明本地的属性名称，并存入以该节点值为key 的 $parameters 中的值
                    $inParameters[$xmlAttributeName] = $xmlAttributeValue;
                }
            }elseif(strpos($xmlAttributeName, 'out') === 0){
                if(!($this instanceof Flow) && !key_exists($xmlAttributeName, $this->outParameterKeys)){
                    throw new FlowParseException('活动(tagName:' .$this->xmlDoc->tagName. ', ID:' .$this->id. ') 未定义输出参数 ' .$xmlAttributeName. '');
                }
                //获取输入参数的节点名称， 根据输入的节点名称声明本地的属性名称，并存入以该节点值为key 的 $parameters 中的值
                $outParameters[$xmlAttributeName] = $xmlAttributeValue;
            }else{
                $this->$xmlAttributeName = $xmlAttributeValue;
                if(!($this instanceof Flow) && $this->hasAttribute($xmlAttributeName)){
                    ////如果不是流程，必须判断当前类是否存在该属性， 如不存在，则删除赋值
                    $this->removeAttribute($xmlAttributeName);
                }
            }
        }
        //新定义的参数赋初值
        foreach($this->inParameterKeys as $key=>$value){
            if(!isset($inParameters[$key])) {
                $inParameters[$key] = $value;
            }
        }
        foreach($this->outParameterKeys as $key=>$value){
            if(!isset($outParameters[$key])) {
                $outParameters[$key] = $value;
            }
        }
        $this->inParameterKeys = $inParameters;
        $this->outParameterKeys = $outParameters;
        //解析子集的属性
        $mxGeometryDocs = $this->xmlDoc->getElementsByTagName('mxGeometry');
        if($mxGeometryDocs && $mxGeometryDocs->length > 0) {
            $mxGeometryDoc = $mxGeometryDocs->item(0);
            $this->x = $mxGeometryDoc->getAttribute('x');
            $this->y = $mxGeometryDoc->getAttribute('y');
            $this->width = $mxGeometryDoc->getAttribute('width');
            $this->height = $mxGeometryDoc->getAttribute('height');
        }
    }

    /**
     * 获取活动中文分类名称
     * @return string
     */
    abstract protected function getCnName();

}

