<?php
namespace DavinBao\WorkflowCore\Models;

use DateTime;
use DavinBao\WorkflowCore\Activities\Activity;
use DavinBao\WorkflowCore\Activities\EndActivity;
use DavinBao\WorkflowCore\Flows\Flow;

/**
 * Engine Class
 *
 * @class  Activity
 */
class Process extends Model {

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['id', 'flow_name', 'flow_label', 'flow_parameters', 'current_activity_id_list', 'current_last_activity_parameters', 'status', 'created_at', 'updated_at'];

    /**
     * 创建一个新的进程
     * @param string $flowName
     * @param array $parameters
     * @return void|static
     */
    public static function newInstance($flowName, array $parameters= []){
        $model = new Process();
        $flow = Flow::newInstance($flowName);
        $flow->setInAttributes($parameters);
        $currentActivities = $flow->getCurrentActivityList();
        $currentActivityIds = [];
        $currentLastActivityParameters = [];
        foreach($currentActivities as $key=>$currentActivity){
            $currentActivityIds[$key] = $currentActivity->id;
            $currentLastActivityParameters = $currentActivity->getParameters();
        }

        $res = $model->save([
            'flow_name' => $flowName,
            'flow_label' => $flow->label,
            'flow_parameters' => json_encode($parameters),
            'current_activity_id_list' => json_encode($currentActivityIds),
            'current_last_activity_parameters' => json_encode($currentLastActivityParameters),
            'status' => 0,
            'updated_at' => new DateTime()
        ]);

        return $res;
    }

    public function start(){
        $process = $this;
        $flow = Flow::newInstance($process->flow_name);
        $flow->setInAttributes(json_decode($process->flow_parameters, true));

        if(!($flow instanceof Flow)){
            throw new WorkflowException('process(ID: ' . $process->id . ')\'s flow unserialize fail');
        }
        $currentActivityIds = json_decode($process->current_activity_id_list, true);
        $currentLastActivityParameters = json_decode($process->current_last_activity_parameters, true);
        $flow->setCurrentActivities($currentActivityIds, $currentLastActivityParameters);

        $flow->onStop(function($returnCode) use($process, $flow){
            $process->stop($flow);
        });

        $flow->run();
    }

    /**
     * 停止该进程
     *
     * @param $flow
     */
    public function stop($flow){
        $currentActivities = $flow->getCurrentActivityList();
        $currentActivityIds = [];
        $currentLastActivityParameters = [];
        foreach($currentActivities as $key=>$currentActivity){
            $currentActivityIds[$key] = $currentActivity->id;
            $currentLastActivityParameters = $currentActivity->getParameters();
        }
        $isEnd = false;
        if($flow->currentActivity instanceof EndActivity){
            $isEnd = true;
        }

        $this->save([
            'flow_parameters' => json_encode($flow->getParameters()),
            'current_activity_id_list' => json_encode($currentActivityIds),
            'current_last_activity_parameters' => json_encode($currentLastActivityParameters),
            'status' => $isEnd ? 1 : 0,
            'updated_at' => new DateTime()
        ], true);
    }

    public static function installSql(){
        return 'CREATE TABLE `process` (
                      `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                      `flow_label` varchar(255),
                      `flow_name` varchar(255),
                      `flow_parameters` text,
                      `current_activity_id_list` varchar(255),
                      `current_last_activity_parameters` text,
                      `status` tinyint(3),
                      `created_at` datetime,
                      `updated_at` datetime
                    );';
    }

}

