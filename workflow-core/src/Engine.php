<?php
namespace DavinBao\WorkflowCore;

use DavinBao\WorkflowCore\Activities;
use DavinBao\WorkflowCore\Flows\Flow;
use DavinBao\WorkflowCore\Exceptions\WorkflowException;
use DavinBao\WorkflowCore\Model\Model;
use DavinBao\WorkflowCore\Model\Process;

/**
 * Engine Class
 *
 * @class  Activity
 */
class Engine {

    public function init(){
        if(!self::isInstall()){
            self::install();
        }
    }

    public function start($processId){
        $this->init();
        $process = Process::get($processId);
        if(is_null($process)){
            throw new WorkflowException('process(ID: ' . $processId . ') is not exist');
        }
        $process->start();
    }

    public static function isInstall(){
        return file_exists('../install.lock');
    }

    public static function install(){
        Model::newInstance()->exec('CREATE TABLE `process` (
                      `id` int(10) NOT NULL AUTO_INCREMENT,
                      `parameters` text COMMENT \'输入参数\',
                      `flow_label` varchar(255) DEFAULT NULL COMMENT \'流程标签\',
                      `flow_name` varchar(255) DEFAULT NULL COMMENT \'流程名称\',
                      `flow` text COMMENT \'流程数据\',
                      `current_activity_label` varchar(255) DEFAULT NULL COMMENT \'当前活动标签\',
                      `status` tinyint(3) unsigned DEFAULT \'0\' COMMENT \'执行状态(0 进行中、1 已结束)\',
                      `created_at` datetime DEFAULT NULL COMMENT \'开始时间\',
                      `updated_at` datetime DEFAULT NULL COMMENT \'最近执行时间\',
                      PRIMARY KEY (`id`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;');

        file_put_contents('../install.lock', '1');
        return true;
    }
}

