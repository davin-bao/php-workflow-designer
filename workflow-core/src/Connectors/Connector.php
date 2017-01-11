<?php
namespace DavinBao\WorkflowCore\Connectors;

use DavinBao\WorkflowCore\Exceptions\FlowParseException;
use DOMDocument;
use DOMElement;
use DavinBao\WorkflowCore\Node;

/**
 * Connector Class
 *
 * @class  Connector
 */
class Connector extends Node {

    public $source = 0;
    public $target = 0;

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

        $mxCellDocs = $model->getDocElementsByTagName('mxCell');
        $mxCellDoc = $mxCellDocs->item(0);

        if(!$mxCellDoc->hasAttribute('source') || !$mxCellDoc->hasAttribute('target')){
            throw new FlowParseException($model->getCnName() . '(ID:' . $model->id . ') 的子节点不存在属性 source 或者 target');
        }
        $model->source = $mxCellDoc->getAttribute('source');
        $model->target = $mxCellDoc->getAttribute('target');

        $model->initAttributes();

        return $model;
    }
    /**
     * @see parent::toDoc
     * @return string
     */
    public function toDoc(){
        $doc = new DOMDocument();
        $xmlDoc = $doc->createElement('Connector');
        $xmlDoc->setAttribute('label', $this->label);
        $xmlDoc->setAttribute('id', $this->id);
        $mxCellDoc = $doc->createElement('mxCell');
        $mxCellDoc->setAttribute('parent', $this->parent);
        $mxCellDoc->setAttribute('source', $this->source);
        $mxCellDoc->setAttribute('target', $this->target);
        $mxCellDoc->setAttribute('edge', 1);
        $mxGeometryDoc = $doc->createElement('mxGeometry');
        $mxGeometryDoc->setAttribute('x', $this->x);
        $mxGeometryDoc->setAttribute('y', $this->y);
        $mxGeometryDoc->setAttribute('width', $this->width);
        $mxGeometryDoc->setAttribute('height', $this->height);
        $mxGeometryDoc->setAttribute('as', 'geometry');
        $mxCellDoc->appendChild($doc->importNode($mxGeometryDoc, true));
        $xmlDoc->appendChild($doc->importNode($mxCellDoc, true));
        return $xmlDoc;
    }

}

