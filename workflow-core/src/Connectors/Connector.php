<?php
namespace DavinBao\WorkflowCore\Connectors;

use DavinBao\WorkflowCore\Exceptions\FlowParseException;
use DOMElement;
use DavinBao\WorkflowCore\Node;

/**
 * Connector Base Class
 *
 * @class  Activity
 */
abstract class Connector extends Node {

    public $parentId = 0;
    public $sourceId = 0;
    public $targetId = 0;

    public function __construct($label, $id, $sourceId, $targetId, $parentId = 1) {
        $this->label = $label;
        $this->id = $id;
        $this->parentId = $parentId;
        $this->sourceId = $sourceId;
        $this->targetId = $targetId;
        $this->xmlDoc = $this->toDoc();
    }

    /**
     * @see parent::getCnName
     * @return string
     */
    protected function getCnName() {
        return '连接线';
    }

    /**
     * @see parent::getInstance
     * @param DOMElement|null $xmlDoc
     * @return static
     * @throws FlowParseException
     * @throws \DavinBao\WorkflowCore\FlowParseException
     */
    public static function getInstance(DOMElement $xmlDoc = null){
        $model = new static();
        $model->xmlDoc = $xmlDoc;
        $model->id = $model->getAttribute('id');
        $model->label = $model->getAttribute('label');

        $mxCellDocs = $model->getElementsByTagName('mxCell');
        $mxCellDoc = $mxCellDocs->item(0);

        if(!$mxCellDoc->hasAttribute('source') || !$mxCellDoc->hasAttribute('target')){
            throw new FlowParseException($model->getCnName() . '(ID:' . $model->id . ') 的子节点不存在属性 source 或者 target');
        }
        $model->sourceId = $mxCellDoc->getAttribute('source');
        $model->targetId = $mxCellDoc->getAttribute('target');

        $mxGeometryDocs = $model->getElementsByTagName('mxGeometry');
        $mxGeometryDoc = $mxGeometryDocs->item(0);
        $model->x = $mxGeometryDoc->getAttribute('x');
        $model->y = $mxGeometryDoc->getAttribute('y');
        $model->width = $mxGeometryDoc->getAttribute('width');
        $model->height = $mxGeometryDoc->getAttribute('height');

        if(!$mxCellDoc->hasAttribute('source') || !$mxCellDoc->hasAttribute('target')){
            throw new FlowParseException($model->getCnName() . '(ID:' . $model->id . ') 的子节点不存在属性 source 或者 target');
        }

        return $model;
    }
    /**
     * @see parent::toDoc
     * @return string
     */
    public function toDoc(){
        $xmlDoc = new DOMElement();
        $xmlDoc->tagName = 'Connector';
        $xmlDoc->setAttribute('label', $this->label);
        $xmlDoc->setAttribute('id', $this->id);
        $mxCellDoc = new DOMElement();
        $mxCellDoc->tagName = 'mxCell';
        $mxCellDoc->setAttribute('parent', $this->parentId);
        $mxCellDoc->setAttribute('source', $this->sourceId);
        $mxCellDoc->setAttribute('target', $this->targetId);
        $mxCellDoc->setAttribute('edge', 1);
        $mxGeometryDoc = new DOMElement();
        $mxGeometryDoc->tagName = 'mxGeometry';
        $mxGeometryDoc->setAttribute('x', $this->x);
        $mxGeometryDoc->setAttribute('y', $this->y);
        $mxGeometryDoc->setAttribute('width', $this->width);
        $mxGeometryDoc->setAttribute('height', $this->height);
        $mxGeometryDoc->setAttribute('as', 'geometry');
        $mxCellDoc->appendChild($mxGeometryDoc);
        $xmlDoc->appendChild($mxCellDoc);
        return $xmlDoc;
    }

}

