<?php
namespace DavinBao\WorkflowCore;
use DavinBao\WorkflowCore\Exceptions\WorkflowException;


/**
 * Engine Class
 *
 * @class  Activity
 */
class Config {

    public static function get($key){
        $config = require __DIR__ .DIRECTORY_SEPARATOR. '..'.DIRECTORY_SEPARATOR. 'config' .DIRECTORY_SEPARATOR. 'config.php';

        if(!isset($config[$key])){
            throw new WorkflowException('read config failed， can\'t read key ' . $key);
        }
        return $config[$key];
    }
}

