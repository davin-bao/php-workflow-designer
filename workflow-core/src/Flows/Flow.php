<?php
namespace DavinBao\WorkflowCore\Flows;

use DavinBao\WorkflowCore\Activities\Activity;
use DavinBao\WorkflowCore\Exceptions\FlowIsNullException;

/**
 * Flow Base Class
 *
 * @class  Activity
 */
abstract class Flow extends Activity {
    //
    public $beginActivity = null;
    public $currentActivity = null;
    public $connectors = [];
    public $activities = [];
    public $parameters = [];

    private $recorder = null;

    public function setRecorder(ActivityRecorderInterface $recorder){
        $this->recorder = $recorder;
    }

    protected function action(array $parameters = null){
        if(!($this->beginActivity instanceof BeginActivity)){
            throw new FlowIsNullException();
        }

        if(is_null($this->currentActivity)){
            $this->currentActivity = $this->beginActivity;
        }

        return $this->doAction($this->currentActivity);
    }

    protected function getNextActivity($returnCode, $thisActivity) {
        foreach($this->connectors as $connector){
            if($connector->returnCode === $returnCode && $connector->fromActivity === $thisActivity){
                return $connector->toActivity;
            }
        }
        throw new ReturnCodeActivityIsNullException();
    }

    private function doAction(Activity $activity){
        $activity->onStop(function($returnCode) use ($this, $activity){
            //活动结束， 记录日志
           $this->recorder->save($this->processId, $activity, $returnCode);
        });

        $returnCode =$activity->run($this->parameters);
        if($returnCode == self::END_CODE){
            return self::END_CODE;
        }
        $this->currentActivity = $this->getNextActivity($returnCode, $activity);
        if($returnCode < 0){
            return $returnCode;
        }

        return $this->doAction($this->currentActivity);
    }

    public static function newInstance($flowName, array $parameters = []){

    }
}

