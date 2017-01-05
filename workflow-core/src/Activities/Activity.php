<?php
namespace DavinBao\WorkflowCore\Activities;

use DavinBao\WorkflowCore\Config;
use DavinBao\WorkflowCore\Exceptions\FlowParseException;
use DavinBao\WorkflowCore\Flows\Flow;
use DavinBao\WorkflowCore\Node;
use DOMDocument;
use DOMNodeList;
use DOMElement;
use DavinBao\WorkflowCore\ActivityParser;
use Illuminate\Database\Connectors\Connector;

/**
 * Activity Base Class
 *
 * @class  Activity
 */
abstract class Activity extends Node {
    use EventTrait, LogTrait, DataPool;

    //常用返回码定义
    const BEGIN_CODE = 0;
    const END_CODE = -1;
    const USER_VIEW_RENDER_RETURN_CODE = -2;    //用户界面渲染后的返回码

    const EXCEPTION_CODE = -500;

    //活动可能的所有返回码
    const RETURN_CODE = [];
    //下一项活动的列表 key 为 当前活动返回码 ， value 为活动实体
    protected $nextActivities = [];
    //传递的参数
    protected $parameters = [];

    /**
     * 连接器模板
     * @var null
     */
    protected $connectorDocs = null;

    //活动的图形
    protected $shape = 'ellipse';
    //活动的图形的填充色
    protected $shapeFillColor = '#FFFFFF';
    protected $shapeIcon = 'images/ellipse.gif';
    protected $shapePressedIcon = 'images/ellipse.gif';

    /**
     * @see parent::getCnName
     * @return string
     */
    protected function getCnName() {
        return '活动';
    }

    //region 运行相关方法
    /**
     * @return int
     */
    final function run(){
        try{
            $this->trigger('start', [$this->parameters]);
            $returnCode = $this->action();
        }catch(\Exception $e){
            self::getLogger()->error($e->getCode() . ' ' . $e->getMessage());
            $returnCode = self::EXCEPTION_CODE;
        }
        $this->trigger('stop', [$returnCode]);
        return $returnCode;
    }

    public function onStart($handle){
        $this->on('start', $handle);
    }

    public function onStop($handle){
        $this->on('stop', $handle);
    }

    abstract protected function action();

    //endregion

    //region 模板相关方法

    /**
     * 生成活动的模板
     * @return string
     * @throws FlowParseException
     */
    public function getTemplateXml(){
        $class = new \ReflectionClass(get_called_class());
        if($class->isAbstract() || strpos($class->getShortName(), 'Activity') === false) return '';

        $activityFullName = $class->getShortName();
        $activityShortName = substr($activityFullName, 0, strlen($activityFullName) - 8);
        $model = $class->newInstance();
        $activityStyle = $model->shape;
        $activityFillColor = $model->shapeFillColor;
        $activityIcon = $model->shapeIcon;
        $activityPressedIcon = $model->shapePressedIcon;
        //获取参数
//        $properties = [];
//        $inProperties = $model->inParameterKeys;
//        $outProperties = $model->outParameterKeys;

//        foreach($class->getProperties(\ReflectionProperty::IS_PUBLIC) as $property){
//            $propertyName = $property->getName();
//            if(in_array($propertyName, ['id'])) continue;
//            $properties[$propertyName] = $property->getValue($model);
//        }
//        $properties['label'] = empty($properties['label']) ? $activityShortName : $properties['label'];

//        $propertiesStr = '';
//        foreach($properties as $key=>$value){
//            $propertiesStr .= $key  . '="' .$value. '" ';
//        }
//        $inPropertiesStr = '';
//        foreach($inProperties as $value){
//            $inPropertiesStr .= $value . '="" ';
//        }
//        $outPropertiesStr = '';
//        foreach($outProperties as $value){
//            $outPropertiesStr .= $value  . '="" ';
//        }
//        $propertiesStr = substr($propertiesStr, 0, strlen($propertiesStr) - 1);
//        $inPropertiesStr = substr($inPropertiesStr, 0, strlen($inPropertiesStr) - 1);
//        $outPropertiesStr = substr($outPropertiesStr, 0, strlen($outPropertiesStr) - 1);

        $doc = new DOMDocument();
        $templateDoc = $doc->createElement('add');
        $templateDoc->setAttribute('as', $activityShortName);
        $templateDoc->setAttribute('icon', $activityIcon);
        $templateDoc->setAttribute('pressedIcon', $activityPressedIcon);
        $templateDoc->setAttribute('style', $activityStyle);
        $templateDoc->setAttribute('fillColor', $activityFillColor);
        $templateDoc->appendChild($doc->importNode($model->toDoc(), true));
        //删除 mxCell 中的 parent 属性
        $elements = $templateDoc->getElementsByTagName('mxCell');
        if(!$elements || $elements->length <= 0){
            throw new FlowParseException($this->getCnName() . '(ID:' . $this->getAttribute('id') . ') 未定义子节点 ' . 'mxCell');
        }
        $element = $elements->item(0);
        if($element->hasAttribute('parent')){
            $element->removeAttribute('parent');
        }

        return $doc->saveHTML($templateDoc);
    }

    /**
     * @see parent::toDoc
     * @return string
     */
    public function toDoc(){
        $doc = new DOMDocument();
        $activityFullName = basename(get_called_class());
        $xmlDoc = $doc->createElement($activityFullName);
        $activityShortName = substr($activityFullName, 0, strlen($activityFullName) - 8);

        $mxCellDoc = $doc->createElement('mxCell');
//        $mxCellDoc = new DOMElement();
//        $mxCellDoc->tagName = 'mxCell';
        $mxCellDoc->setAttribute('style', $this->shape);
        $mxCellDoc->setAttribute('parent', $this->parentId);
        $mxCellDoc->setAttribute('vertex', 1);
        $mxGeometryDoc = $doc->createElement('mxGeometry');
//        $mxGeometryDoc = new DOMElement();
//        $mxGeometryDoc->tagName = 'mxGeometry';
        $mxGeometryDoc->setAttribute('x', $this->x);
        $mxGeometryDoc->setAttribute('y', $this->y);
        $mxGeometryDoc->setAttribute('width', $this->width);
        $mxGeometryDoc->setAttribute('height', $this->height);
        $mxGeometryDoc->setAttribute('as', 'geometry');
        $mxCellDoc->appendChild($doc->importNode($mxGeometryDoc, true));
        $xmlDoc->appendChild($doc->importNode($mxCellDoc, true));

        //获取参数
        $class = new \ReflectionClass(get_called_class());
        $properties = [];
        foreach($class->getProperties(\ReflectionProperty::IS_PUBLIC) as $property){
            $propertyName = $property->getName();
            if(in_array($propertyName, ['id'])) continue;
            $properties[$propertyName] = $property->getValue($this);
        }
        foreach($this->inParameterKeys as $inParameterKey){
            $properties[$inParameterKey] = '';
        }
        foreach($this->outParameterKeys as $outParameterKey){
            $properties[$outParameterKey] = '';
        }
        $properties['label'] = empty($properties['label']) ? $activityShortName : $properties['label'];

        foreach($properties as $key=>$value){
            $xmlDoc->setAttribute($key, $value);
        }

        return $xmlDoc;
    }

    /**
     * 根据模板内容生成新的活动实例
     *
     * @param DOMElement|null $xmlDoc
     * @param DOMNodeList|null $connectorDocs
     * @return mixed
     * @throws FlowParseException
     */
    public static function getInstance(DOMElement $xmlDoc = null, DOMNodeList $connectorDocs = null){
        $activityName = static::getClassByTagName($xmlDoc->tagName);
        $activity = new $activityName();
        //存储模板及连接器信息
        $activity->xmlDoc = $xmlDoc;
        $activity->connectorDocs = $connectorDocs;
        //初始化属性
        $activity->initAttributes($xmlDoc);
        //初始化以后的活动列表
        $activity->initNextActivities();

        return $activity;
    }

    /**
     * 根据流程模板定义的连接线实例化所有下一步的活动
     * @return array
     * @throws FlowParseException
     */
    public function initNextActivities(){
        $nextActivities = [];
        if(!is_null($this->connectorDocs)){
            foreach($this->connectorDocs as $connectorDoc){
                if(!$connectorDoc->hasAttribute('label')){
                    throw new FlowParseException('连接线(ID:' . $connectorDoc->getAttribute('id') . ') 未定义 label 属性');
                }
                $label = $connectorDoc->getAttribute('label');
                $mxCellDocs = $connectorDoc->getElementsByTagName('mxCell');
                if(!$mxCellDocs || $mxCellDocs->length <= 0){
                    throw new FlowParseException('连接线(ID:' . $connectorDoc->getAttribute('id') . ') 未定义子节点 mxCell');
                }
                $mxCellDoc = $mxCellDocs->item(0);
                if(!$mxCellDoc->hasAttribute('source') || !$mxCellDoc->hasAttribute('target')){
                    throw new FlowParseException('连接线(ID:' . $connectorDoc->getAttribute('id') . ')\的子节点不存在属性 source 或者 target');
                }
                if($mxCellDoc->getAttribute('source') === $this->id){
                    $key = intval($label);
                    $targetId = $mxCellDoc->getAttribute('target');
                    //查找下一个 Activity
                    foreach($this->xmlDoc->parentNode->childNodes as $childNode){
                        if($childNode->nodeType != XML_ELEMENT_NODE  || !$childNode->hasAttribute('id')){
                            continue;
                        }
                        if($targetId === $childNode->getAttribute('id')){
                            $nextActivities[$key] = Activity::getInstance($childNode, $this->connectorDocs);
                        }
                    }
                }
            }
        }

        $this->nextActivities = $nextActivities;
    }
    //endregion

    //region 活动的工具方法

    /**
     * 通过标签名称获取类名称
     * @param $tagName
     * @return string
     * @throws FlowParseException
     */
    public static function getClassByTagName($tagName){
        if($tagName === 'Workflow'){
            return '\DavinBao\WorkflowCore\Flows\Flow';
        }else{
            $activitiesName = static::getAllActivityClassName();
            foreach($activitiesName as $value){
                if(substr($value, strlen($value) - strlen($tagName),strlen($tagName)) == $tagName){
                    return $value;
                }
            }
        }

        throw new FlowParseException('活动 ' .$tagName. ' 未找到定义的类');
    }

    /**
     * 获取所有活动的类名称
     * @return array
     */
    public static function getAllActivityClassName(){
        $classes = [];
        $activitiesConfig = Config::get('activities_file_path');
        foreach($activitiesConfig as $activityConfig) {

            foreach (glob($activityConfig['path'] . '/*.php') as $file) {
                require_once $file;

                $className = $activityConfig['namespace'] . basename($file, '.php');

                if (class_exists($className)){
                    $class = new \ReflectionClass($className);
                    if($class->isAbstract() || strpos($class->getShortName(), 'Activity') === false) continue;
                    array_push($classes, $className);
                }
            }
        }
        return $classes;
    }
    //endregion
}

