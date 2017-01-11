<?php
namespace DavinBao\WorkflowCore\Activities;

use DavinBao\WorkflowCore\Config;
use DavinBao\WorkflowCore\Exceptions\FlowParseException;
use DavinBao\WorkflowCore\Exceptions\ReturnCodeActivityIsNullException;
use DavinBao\WorkflowCore\Node;
use DOMDocument;
use DOMElement;

/**
 * Activity Base Class
 *
 * @class  Activity
 */
abstract class Activity extends Node {
    use EventTrait, LogTrait;

    //常用返回码定义
    const BEGIN_CODE = 0;
    const END_CODE = 0;
    const USER_VIEW_RENDER_RETURN_CODE = -1;    //用户界面渲染后的返回码

    const EXCEPTION_CODE = -500;

    //活动可能的所有返回码
    const RETURN_CODE = [];
    //下一项活动的列表 key 为 当前活动返回码 ， value 为活动实体
    protected $nextActivities = [];
    //传递的参数
    protected $parameters = [];

    public $description = '';

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

    /**
     * 获取下一个活动
     * @param $returnCode
     * @return mixed
     * @throws ReturnCodeActivityIsNullException
     */
    public function getNext($returnCode){
        if(!isset($this->nextActivities[$returnCode])){
            throw new ReturnCodeActivityIsNullException('活动（tagName:' .$this->label. '）定义的返回码（Code:' .$returnCode. ') 未找到对应的下一个活动');
        }
        return $this->nextActivities[$returnCode];
    }

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
        $activityShortName = substr($activityFullName, 0, strlen($activityFullName) - 8);
        $xmlDoc = $doc->createElement($activityFullName);

        $mxCellDoc = $doc->createElement('mxCell');
        $mxCellDoc->setAttribute('style', $this->shape);
        $mxCellDoc->setAttribute('parent', $this->parent);
        $mxCellDoc->setAttribute('vertex', 1);
        $mxGeometryDoc = $doc->createElement('mxGeometry');
        $mxGeometryDoc->setAttribute('x', $this->x);
        $mxGeometryDoc->setAttribute('y', $this->y);
        $mxGeometryDoc->setAttribute('width', $this->width);
        $mxGeometryDoc->setAttribute('height', $this->height);
        $mxGeometryDoc->setAttribute('as', 'geometry');
        $mxCellDoc->appendChild($doc->importNode($mxGeometryDoc, true));
        $xmlDoc->appendChild($doc->importNode($mxCellDoc, true));

        $class = new \ReflectionClass(get_called_class());
        //获取返回码表
        $xmlDoc->setAttribute('RETURN_CODE', implode(',', $class->getConstant('RETURN_CODE')));
        //获取参数
        $properties = [];
        foreach($class->getProperties(\ReflectionProperty::IS_PUBLIC) as $property){
            $propertyName = $property->getName();
            $properties[$propertyName] = $property->getValue($this);
        }
        foreach($this->inParameterKeys as $key=>$value){
            $properties[$key] = $value;
        }
        foreach($this->outParameterKeys as $key=>$value){
            $properties[$key] = $value;
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
     * @return mixed
     * @throws FlowParseException
     */
    public static function getInstance(DOMElement $xmlDoc = null){
        $activityName = static::getClassByTagName($xmlDoc->tagName);
        $activity = new $activityName();
        //存储模板及连接器信息
        $activity->xmlDoc = $xmlDoc;
        //初始化顶级属性
        $activity->initAttributes();

        return $activity;
    }

    /**
     * 根据流程模板定义的连接线实例化所有下一步的活动
     * @param array $activities
     * @param array $connectors
     * @throws FlowParseException
     */
    public function initNextActivities(array $activities = [], array $connectors = []){
        $nextActivities = [];
        foreach($connectors as $connector){
            if($connector->source == $this->id){
                $key = intval($connector->label);
                foreach($activities as $activity){
                    if($connector->target === $activity->id){
                        $nextActivities[$key] = $activity;
                        $activity->initNextActivities($activities, $connectors);
                    }
                }
            }
        }
        $this->nextActivities = $nextActivities;
    }
    //endregion

    //region 活动的工具方法

    public function getParameters(){
        return $this->parameters;
    }
    public function setParameters(array $parameters=[]){
        $this->parameters = $parameters;
    }

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

