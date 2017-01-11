<?php
namespace DavinBao\WorkflowCore;

use DavinBao\WorkflowCore\Activities;
use DavinBao\WorkflowCore\Exceptions\WorkflowException;
use DavinBao\WorkflowCore\Models\Model;
use DavinBao\WorkflowCore\Models\Process;

/**
 * Engine Class
 *
 * @class  Activity
 */
class Engine {

    private $process = null;

    public static function init(){

        if(!self::isInstall()){
            self::install();
        }
        return new Engine();
    }

    public function createProcess($flowName, array $parameters = []){
        $this->process = Process::newInstance($flowName, $parameters);
        return $this;
    }

    public function setProcess($processId){
        $this->process = Process::get($processId);
        return $this;
    }

    public function start(){
        $processId = $this->process->id;
        $process = Process::get($processId);
        if(is_null($process)){
            throw new WorkflowException('process(ID: ' . $processId . ') is not exist');
        }
        $process->start();
    }

    public static function isInstall(){
        return file_exists(Config::get('db_path'));
    }

    public static function install(){
        (new Model([]))->exec(Process::installSql());
        return true;
    }
}

