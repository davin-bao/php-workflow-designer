<?php
namespace DavinBao\WorkflowCore\Flows;

use DavinBao\WorkflowCore\Activities\EndActivity;
use DavinBao\WorkflowCore\Activities\SubFlowActivity;
use DavinBao\WorkflowCore\Connectors\Connector;
use DOMDocument;
use DavinBao\WorkflowCore\Activities\Activity;
use DavinBao\WorkflowCore\Activities\BeginActivity;
use DavinBao\WorkflowCore\Config;
use DavinBao\WorkflowCore\Exceptions\FlowIsNullException;
use DavinBao\WorkflowCore\Exceptions\FlowParseException;
/**
 * Flow Class
 *
 * @class  Activity
 */
class Flow extends Activity {
    //
    public $beginActivity = null;
    public $currentActivity = null;

    public $activities = [];
    public $connectors = [];

    /**
     * @see parent::getCnName
     * @return string
     */
    protected function getCnName() {
        return '流程';
    }
    //region 运行相关方法
    protected function action(){
        if(!($this->beginActivity instanceof BeginActivity)){
            throw new FlowIsNullException('流程（'. $this->label . ')未定义开始活动');
        }

        if(is_null($this->currentActivity)){
            $this->currentActivity = $this->beginActivity;
        }

        return $this->doAction($this->currentActivity, $this->parameters);
    }

    /**
     * 设置ID为$id的活动为当前活动
     * @param array $currentActivityIds
     * @param int $index
     * @param array $currentLastActivityParameters
     * @throws FlowIsNullException
     */
    public function setCurrentActivities(array $currentActivityIds = [], array $currentLastActivityParameters = [], &$index = 0){

        foreach($this->activities as $activity){
            if($index < count($currentActivityIds) && $activity->id == $currentActivityIds[$index]){
                $index++;
                $this->currentActivity = $activity;
                if($activity instanceof SubFlowActivity){
                    $subFlow = $this->currentActivity->getFlow();
                    $subFlow->setCurrentActivities($currentActivityIds, $currentLastActivityParameters, $index);
                }else{
                    $this->currentActivity->setParameters($currentLastActivityParameters);
                }
            }
        }
        if($index >= count($currentActivityIds)){
            return;
        }
        throw new FlowParseException('流程（'. $this->label . ')不存在活动（ID:' .$currentActivityIds[$index]. '）');
    }

    /**
     * 递归获取当前的 Activity 列表
     * @param array $currentActivities
     * @param int $index
     * @return array
     */
    public function getCurrentActivityList(&$currentActivities = [], &$index = 0){
        $currentActivities[$index++] = $this->currentActivity;

        if($this->currentActivity instanceof SubFlowActivity){
            $subFlow = $this->currentActivity->getFlow();
            return $subFlow->getCurrentActivityList($currentActivities, $index);
        }
        return $currentActivities;
    }

    private function doAction(Activity $activity, array $parameters = []){
        $self = $this;
        $activity->onStart(function(array $parameters) use ($self, $activity){
            //活动结束， 记录日志
            self::getLogger()->debug('流程（'. $this->label . ')的活动（tagName:' .$activity->label. '）开始执行，输入参数（' .json_encode($parameters, JSON_UNESCAPED_UNICODE). ')');
        });
        $activity->onStop(function($returnCode) use ($self, $activity){
            //活动结束， 记录日志
            self::getLogger()->debug('流程（'. $this->label . ')的活动（tagName:' .$activity->label. '）执行结束，返回码（Code:' .$returnCode. ')');
        });
        $activity->setInAttributes($parameters);
        $returnCode =$activity->run();
        $nextActivityParameters = array_merge($parameters, $activity->getOutAttributes());
        if($returnCode < 0 || ($activity instanceof EndActivity)){
            return $returnCode;
        }
        //获取下一个活动
        $self->currentActivity = $activity->getNext($returnCode);

        return $self->doAction($self->currentActivity, $nextActivityParameters);
    }
    //endregion
    //region 模板相关方法

    /**
     * @see parent::toDoc
     * @return null
     * @throws FlowParseException
     */
    public function toDoc(){
        $doc = new DOMDocument();
        $mxGraphModelDoc = $doc->createElement('mxGraphModel');
        $rootDoc = $doc->createElement('root');
        $mxGraphModelDoc->appendChild($doc->importNode($rootDoc, true));
        $rootDoc->appendChild($doc->importNode($this->xmlDoc, true));
        //生成绘图层
        $layerDoc = $doc->createElement('Layer');
        $layerDoc->setAttribute('id', '1');
        $layerDoc->setAttribute('label', 'Default Layer');
        $mxCellDoc = $doc->createElement('mxCell');
        $mxCellDoc->setAttribute('parent', '0');
        $layerDoc->appendChild($doc->importNode($mxCellDoc, true));
        $rootDoc->appendChild($doc->importNode($layerDoc, true));
        //生成活动列表
        foreach($this->activities as $nextActivity){
            $rootDoc->appendChild($doc->importNode($nextActivity->toDoc(), true));
        }
        //生成连接器列表
        foreach($this->connectors as $connector){
            $rootDoc->appendChild($doc->importNode($connector->toDoc(), true));
        }

        return $mxGraphModelDoc;
    }

    public static function newInstance($flowName){
        //加载XML文件
        $flowFilePath = Config::get('flow_file_path');
        $xmlDoc = new \DOMDocument();
        $xmlDoc->load($flowFilePath . $flowName . '.xml');
        //解析XML文档
        $rootDocs = $xmlDoc->getElementsByTagName('root');
        if(!$rootDocs || $rootDocs->length <= 0){
            throw new FlowParseException('root 节点不存在');
        }
        $workflowDoc = null;
        $connectorDocs = [];
        $activityDocs = [];
        foreach($rootDocs->item(0)->childNodes as $childNode){
            if($childNode->nodeType === XML_ELEMENT_NODE){
                switch($childNode->tagName){
                    case 'Workflow':
                        $workflowDoc = $childNode;
                        break;
                    case 'Connector':
                        array_push($connectorDocs, $childNode);
                        break;
                    case 'Layer':
                        continue;
                    default:
                        array_push($activityDocs, $childNode);
                        break;
                }
            }
        }
        if(!$workflowDoc){
            throw new FlowParseException('Workflow 节点不存在');
        }
        //对象实例化
        $model = Flow::getInstance($workflowDoc);

        foreach($connectorDocs as $connectorDoc){
            array_push($model->connectors, Connector::getInstance($connectorDoc));
        }
        foreach($activityDocs as $activityDoc){
            array_push($model->activities, Activity::getInstance($activityDoc));
        }
        if(!is_array($model->connectors) || count($model->connectors) <= 0){
            throw new FlowParseException('流程(' .$flowName. ')的连接线不存在');
        }
        if(!is_array($model->activities) || count($model->activities) <= 0){
            throw new FlowParseException('流程(' .$flowName. ')的活动不存在');
        }
        //初始化以后的活动列表
        $model->initNextActivities($model->activities, $model->connectors);
        $model->currentActivity = $model->beginActivity;
        //添加停止后的事件
        $model->onStop(function($returnCode) use ($model){
            //活动结束， 记录日志
            self::getLogger()->debug('流程（'. $model->label . ')执行结束，返回码（Code:' .$returnCode. ')');
        });

        return $model;
    }

    /**
     * @see parent::initNextActivities
     * @param array $activities
     * @param array $connectors
     * @throws FlowParseException
     */
    public function initNextActivities(array $activities = [], array $connectors = []){
        foreach($activities as $activity){
            if($activity instanceof BeginActivity){
                $activity->initNextActivities($activities, $connectors);
                $this->beginActivity = $this->nextActivities[0] = $activity;
                return;
            }
        }

        throw new FlowParseException('流程未设置 Begin 活动');
    }
    //endregion

    //region 流程的工具方法

    /**
     * 获取所有流程列表
     * @return array
     * @throws \DavinBao\WorkflowCore\Exceptions\WorkflowException
     */
    public static function getAll(){
        $flowList = [];
        $flowFilePath = Config::get('flow_file_path');
        foreach (glob($flowFilePath . '/*.xml') as $file) {
            try{
                $doc=new \DOMDocument();
                //加载XML文件
                $doc->load($file);
                $workflowDoc = $doc->getElementsByTagName('Workflow');
                $flowName = $workflowDoc && $workflowDoc->length > 0 && $workflowDoc->item(0)->hasAttribute('label') ? $workflowDoc->item(0)->getAttribute('label') : 'undefined';
            }catch(\Exception $e){
                $flowName = $e->getMessage();
            }
            $flowList[$flowName] = basename($file);
        }
        return $flowList;
    }

    /**
     * 打开并校验一个流程
     * @param null $flowName
     * @return string
     * @throws FlowIsNullException
     * @throws FlowParseException
     * @throws \DavinBao\WorkflowCore\Exceptions\WorkflowException
     */
    public static function getXml($flowName = null){
        //支持传递带后缀.xml的流程文件名
        if(strpos($flowName, '.xml') !== false){
            $flowName = substr($flowName, 0, strlen($flowName) - 4);
        }

        $flowFilePath = Config::get('flow_file_path');
        if(is_null($flowName)){
            throw new FlowIsNullException('文件名称为空，打开失败！');
            die;
        }elseif(!file_exists($flowFilePath . $flowName . '.xml')){
            throw new FlowIsNullException('文件不存在，打开失败！');
            die;
        }

        $flow = Flow::newInstance($flowName);
        $elements = $flow->toDoc();

        $xmlDoc = new \DOMDocument();
        $xmlDoc->appendChild($xmlDoc->importNode($elements, true));

        return $xmlDoc->saveXML();
    }
    //endregion
}

