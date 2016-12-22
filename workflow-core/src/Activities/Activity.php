<?php
namespace DavinBao\WorkflowCore\Activities;

/**
 * Activity Base Class
 *
 * @class  Activity
 */
abstract class Activity extends Event {

    //常用返回码定义
    const BEGIN_CODE = 0;
    const END_CODE = -1;
    const USER_VIEW_RENDER_RETURN_CODE = -2;    //用户界面渲染后的返回码

    //活动可能的所有返回码
    const RETURN_CODE = [];
    //下一项活动的列表 key 为 当前活动返回码 ， value 为活动实体
    public $nextActivities = [];

    //活动名称
    public $label = '';
    //活动的图形
    public $shape = '';

    /**
     * @param array $parameters
     * @return mixed
     */
    final function run(array $parameters){
        try{
            $this->trigger('start', $parameters);
            $returnCode = $this->action($parameters);
        }catch(\Exception $e){
            $returnCode = -2;
        }
        $this->trigger('stop', $returnCode);
        return $returnCode;
    }

    abstract protected function action(array $parameters);

    public function onStart($handle){
        $this->on('start', $handle);
    }

    public function onStop($handle){
        $this->on('stop', $handle);
    }
}

