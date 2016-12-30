<?php
namespace DavinBao\WorkflowCore\Activities;

use DavinBao\WorkflowCore\Exceptions\FlowParseException;
use DOMNodeList;
use DOMElement;
use DavinBao\WorkflowCore\ActivityParser;

/**
 * Activity Base Class
 *
 * @class  Activity
 */
abstract class Activity extends DataPool {
    use EventTrait, LogTrait;

    //常用返回码定义
    const BEGIN_CODE = 0;
    const END_CODE = -1;
    const USER_VIEW_RENDER_RETURN_CODE = -2;    //用户界面渲染后的返回码

    const EXCEPTION_CODE = -500;

    //活动可能的所有返回码
    const RETURN_CODE = [];
    //下一项活动的列表 key 为 当前活动返回码 ， value 为活动实体
    public $nextActivities = [];
    //传递的参数
    public $parameters = [];

    public $id = null;
    //活动名称
    public $label = '';
    //活动的图形
    public $shape = 'ellipse';
    //活动的图形的填充色
    public $shapeFillColor = '#FFFFFF';
    //活动的图形宽度
    public $shapeWidth = '32';
    //活动的图形高度
    public $shapeHeight = '32';
    public $shapeIcon = 'images/ellipse.gif';
    public $shapePressedIcon = 'images/ellipse.gif';

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

    public function init(DOMElement $xmlDoc = null, DOMNodeList $connectorsDocs = null){
        $activityName = $xmlDoc->tagName;
        $activitiesName = ActivityParser::getAllActivityClass();
        foreach($activitiesName as $value){
            if(substr($value, strlen($value) - strlen($activityName),strlen($activityName)) == $activityName){
                $activity = new $value();
                return $activity->parse($xmlDoc, $connectorsDocs);
            }
        }

        throw new FlowParseException('node ' .$activityName. ' Class is not exist');
    }

    public function parse(DOMElement $xmlDoc = null, DOMNodeList $connectorsDocs = null){
        //赋值所有非参数属性
        $this->initAttributes($xmlDoc);

        if($xmlDoc->tagName === 'Workflow'){
            $beginDocs = $xmlDoc->parentNode->getElementsByTagName('BeginActivity');
            if(!$beginDocs || $beginDocs->length <= 0){
                throw new FlowParseException('node BeginActivity is not exist');
            }
            $beginDoc = $beginDocs->item(0);
            $this->beginActivity = $this->nextActivities[0] = Activity::init($beginDoc, $connectorsDocs);
        }elseif($xmlDoc->tagName === 'EndActivity'){
            //
        }else{
            foreach(static::RETURN_CODE as $code){
                $nextActivityDoc = $this->getNextActivityDoc($xmlDoc, $connectorsDocs, $code);
                $this->nextActivities[$code] = Activity::init($nextActivityDoc, $connectorsDocs);;
            }
        }
        return $this;
    }

    /**
     * 获取下一个 Activity 实例
     * @param DOMElement|null $xmlDoc
     * @param array $parameters
     * @param DOMNodeList|null $connectorsDocs
     * @param $code
     * @return mixed
     * @throws FlowParseException
     */
    private function getNextActivityDoc(DOMElement $xmlDoc = null, DOMNodeList $connectorsDocs = null, $code){
        foreach($connectorsDocs as $connectorsDoc){
            if(!$connectorsDoc->hasAttribute('label')){
                throw new FlowParseException('connector(ID:' . $connectorsDoc->getAttribute('id') . ') has not label attribute');
            }
            $label = $connectorsDoc->getAttribute('label');
            $mxCellDocs = $connectorsDoc->getElementsByTagName('mxCell');
            if(!$mxCellDocs || $mxCellDocs->length <= 0){
                throw new FlowParseException('connector(ID:' . $connectorsDoc->getAttribute('id') . ') has not child mxCell');
            }
            $mxCellDoc = $mxCellDocs->item(0);
            if(!$mxCellDoc->hasAttribute('source') || !$mxCellDoc->hasAttribute('target')){
                throw new FlowParseException('connector(ID:' . $connectorsDoc->getAttribute('id') . ')\'s mxCell has not attribute source or target');
            }
            if(intval($label) === $code && $mxCellDoc->getAttribute('source') === $this->id){
                $targetId = $mxCellDoc->getAttribute('target');
                //查找下一个 Activity
                foreach($xmlDoc->parentNode->childNodes as $childNode){
                    if($childNode->nodeType != XML_ELEMENT_NODE  || !$childNode->hasAttribute('id')){
                        continue;
                    }
                    if($targetId === $childNode->getAttribute('id')){
                        return $childNode;
                    }
                }
            }
        }
        throw new FlowParseException('activity (ID:' .$this->id. ') code(' . $code . ') is not exist connector');
    }
}

