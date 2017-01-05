<?php
namespace DavinBao\WorkflowCore;

use DOMElement;
/**
 * interface Node
 *
 * @interface  NodeInterface
 */
interface NodeInterface {

    /**
     * 根据 xml 获取实例
     * @param DOMElement|null $xmlDoc
     * @return mixed
     */
    public static function getInstance(DOMElement $xmlDoc = null);

    /**
     * 转化为 xmlDoc
     * @return DOMNodeList
     */
    public function toDoc();
}

