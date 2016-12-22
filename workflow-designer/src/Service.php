<?php
namespace DavinBao\WorkflowDesigner;
use DavinBao\WorkflowCore\Config;

/**
 * Service Class
 *
 * @class  Activity
 */
class Service {

    public function __construct(array $request){
        $action = array_get($request, '_action',  'open');

        call_user_func_array(array($this, $action), array($request));
    }

    public function open(){
        echo 'open';
    }

    public function save($request){
        $filename = array_get($request, 'filename');
        $xml = array_get($request, 'xml');
        if(is_null($filename) || is_null($xml)){
            echo '输入测文件名称或内容为空，保存失败！';
            die;
        }

        $flowFilePath = Config::get('flow_file_path');
        file_put_contents($flowFilePath . DIRECTORY_SEPARATOR . $filename, urldecode($xml));

        echo '保存成功';
        die;
    }

    public function export(){
        echo 'export';
    }

}




