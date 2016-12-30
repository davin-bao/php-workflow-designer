<?php
namespace DavinBao\WorkflowCore\Model;

use DateTime;
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
    protected $fillable = ['id', 'parameters', 'flow_label', 'flow_name', 'flow', 'current_activity_label', 'status', 'created_at', 'updated_at'];

    /**
     * 创建一个新的进程
     * @param string $flowName
     * @param array $parameters
     * @return void|static
     */
    public static function newInstance($flowName, array $parameters= []){
        $model = new Process();
        $flow = Flow::newInstance($flowName, $parameters);
        $model->save([
            'flow' => serialize($flow),
            'current_activity_label' => $flow->currentActivity->label,
            'status' => 0,
            'updated_at' => new DateTime()
        ], true);
    }

    public function start(){
        $process = $this;
        $flow = unserialize($process->flow);
        if(!($flow instanceof Flow)){
            throw new WorkflowException('process(ID: ' . $process->id . ')\'s flow unserialize fail');
        }
        $flow->onStop(function($returnCode) use($process, $flow){
            $isEnd = $returnCode === Flow::END_CODE;
            $process->stop($flow, $isEnd);
        });

        $flow->run();
    }

    /**
     * 停止该进程
     *
     * @param $flow
     * @param $isEnd
     */
    public function stop($flow, $isEnd){
        $this->save([
            'flow' => serialize($flow),
            'current_activity_label' => $flow->currentActivity->label,
            'status' => $isEnd ? 1 : 0,
            'updated_at' => new DateTime()
        ], true);
    }


}

