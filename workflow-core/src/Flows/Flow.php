<?php
namespace DavinBao\WorkflowCore\Flows;

use DavinBao\WorkflowCore\Activities\EndActivity;
use DavinBao\WorkflowCore\Connectors\Connector;
use DOMDocument;
use DOMElement;
use DOMNodeList;
use DavinBao\WorkflowCore\Activities\Activity;
use DavinBao\WorkflowCore\Activities\BeginActivity;
use DavinBao\WorkflowCore\Config;
use DavinBao\WorkflowCore\Exceptions\FlowIsNullException;
use DavinBao\WorkflowCore\Exceptions\FlowParseException;
use DavinBao\WorkflowCore\Exceptions\ReturnCodeActivityIsNullException;
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

    protected function getNextActivity($returnCode, $thisActivity) {
        if(isset($thisActivity->nextActivities[$returnCode])){
            return $thisActivity->nextActivities[$returnCode];
        }
        throw new ReturnCodeActivityIsNullException('活动（tagName:' .$thisActivity->label. '）定义的返回码（Code:' .$returnCode. ') 未找到对应的下一个活动');
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
        $self->currentActivity = $self->getNextActivity($returnCode, $activity);
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

        foreach($this->nextActivities as $nextActivity){
            $rootDoc->appendChild($doc->importNode($nextActivity->toDoc(), true));
        }

        return $mxGraphModelDoc;
    }

    public static function newInstance($flowName, array $parameters = []){
        //加载XML文件
        $flowFilePath = Config::get('flow_file_path');
        $xmlDoc = new \DOMDocument();
        $xmlDoc->load($flowFilePath . $flowName);
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
                    default:
                        array_push($activityDocs, $childNode);
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
        if(!$model->connectors || $model->connectors->length <= 0){
            throw new FlowParseException('流程(' .$flowName. ')的连接线不存在');
        }
        if(!$model->activities || $model->activities->length <= 0){
            throw new FlowParseException('流程(' .$flowName. ')的活动不存在');
        }
        $model->initNextActivities($model->connectors);
        $model->parameters = $parameters;
//        $model->currentActivity = $model->beginActivity;
        //添加停止后的事件
        $model->onStop(function($returnCode) use ($model){
            //活动结束， 记录日志
            self::getLogger()->debug('流程（'. $model->label . ')执行结束，返回码（Code:' .$returnCode. ')');
        });

        return $model;
    }

    /**
     * @see parent::initNextActivities
     * @throws FlowParseException
     */
    public function initNextActivities(){
        $xmlDoc = $this->xmlDoc;
        $connectorDocs = $this->connectorDocs;

        $beginDocs = $xmlDoc->parentNode->getElementsByTagName('BeginActivity');
        if(!$beginDocs || $beginDocs->length <= 0){
            throw new FlowParseException('流程未设置 Begin 活动');
        }
        $beginDoc = $beginDocs->item(0);
        $this->beginActivity = $this->nextActivities[0] = Activity::getInstance($beginDoc, $connectorDocs);
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
     * @throws FlowParseException
     * @throws \DavinBao\WorkflowCore\Exceptions\WorkflowException
     */
    public static function open($flowName = null){
        $flowFilePath = Config::get('flow_file_path');
        if(is_null($flowName)){
            echo '文件名称为空，打开失败！';
            die;
        }elseif(!file_exists($flowFilePath . $flowName)){
            echo '文件不存在，打开失败！';
            die;
        }

        $flow = Flow::newInstance($flowName);
        $elements = $flow->toDoc();

        $xmlDoc = new \DOMDocument();
        $xmlDoc->appendChild($xmlDoc->importNode($elements, true));
//        $xmlDoc->load($flowFilePath . $flowName);
//        $rootDocs = $xmlDoc->getElementsByTagName('root');
//        if(!$rootDocs || $rootDocs->length <= 0){
//            throw new FlowParseException('root 节点不存在');
//        }
//        $newChildNodes = new DOMNodeList();
//        foreach($rootDocs->item(0)->childNodes as $childNode){
//            if($childNode->nodeType === XML_ELEMENT_NODE){
//                $node = Activity::getInstance($childNode, null);
//                print_r($node->toDoc());
//            }
//
//        }

        header('Content-Type:text/xml');
        echo $xmlDoc->saveXML();
    }
    //endregion
}

