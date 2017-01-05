<?php
namespace DavinBao\WorkflowCore;

use DOMElement;
/**
 * Connector Base Class
 *
 * @class  Activity
 */
abstract class Node implements NodeInterface {

    public $id = null;
    protected $parentId = 1;
    //活动名称
    public $label = '';
    protected $x = 0;
    protected $y = 0;
    protected $width = 32;
    protected $height = 32;

    /**
     * xml 模板
     * @var null
     */
    protected $xmlDoc = null;

    protected function getAttribute($attributeName){
        if(!$this->xmlDoc->hasAttribute($attributeName)){
            throw new FlowParseException($this->getCnName() . '(ID:' . $this->getAttribute('id') . ') 未定义 ' .$attributeName. ' 属性');
        }
        $this->label = $this->xmlDoc->getAttribute($attributeName);
    }
    protected function getElementsByTagName($tagName){
        $elements = $this->xmlDoc->getElementsByTagName($tagName);
        if(!$elements || $elements->length <= 0){
            throw new FlowParseException($this->getCnName() . '(ID:' . $this->getAttribute('id') . ') 未定义子节点 ' . $tagName);
        }
        return $elements;
    }

    /**
     * 获取活动中文分类名称
     * @return string
     */
    abstract protected function getCnName();

}

