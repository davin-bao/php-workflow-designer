<?php
namespace DavinBao\WorkflowCore\Flows;

use DavinBao\WorkflowCore\Activities\Activity;
use DavinBao\WorkflowCore\Activities\BeginActivity;
use DavinBao\WorkflowCore\Activities\LogTrait;
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

    private $recorder = null;

    public function setRecorder(ActivityRecorderInterface $recorder){
        $this->recorder = $recorder;
    }

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
        if($returnCode == self::END_CODE){
            return self::END_CODE;
        }
        $self->currentActivity = $self->getNextActivity($returnCode, $activity);
        if($returnCode < 0){
            return $returnCode;
        }

        return $self->doAction($self->currentActivity, $nextActivityParameters);
    }

    public static function newInstance($flowName, array $parameters = []){
        //加载XML文件
        $flowFilePath = Config::get('flow_file_path');
        $xmlDoc = new \DOMDocument();
        $xmlDoc->load($flowFilePath . $flowName . '.xml');
        $workflowDocs = $xmlDoc->getElementsByTagName('Workflow');
        if(!$workflowDocs || $workflowDocs->length <= 0){
            throw new FlowParseException('Workflow 节点不存在');
        }
        $workflowDoc = $workflowDocs->item(0);
        $connectorsDocs = $xmlDoc->getElementsByTagName('Connector');
        if(!$connectorsDocs || $connectorsDocs->length <= 0){
            throw new FlowParseException('连接线不存在');
        }
        $model = new Flow();
        $model->parse($workflowDoc, $connectorsDocs);
        $model->currentActivity = $model->beginActivity;
        $model->parameters = $parameters;
        $model->onStop(function($returnCode) use ($model){
            //活动结束， 记录日志
            self::getLogger()->debug('流程（'. $model->label . ')执行结束，返回码（Code:' .$returnCode. ')');
        });

        return $model;
    }
}

