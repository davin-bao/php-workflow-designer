<?php
namespace DavinBao\WorkflowDesigner;
use DavinBao\WorkflowCore\Config;
use DavinBao\WorkflowCore\ActivityParser;
use DavinBao\WorkflowCore\Flows\Flow;

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

    public function index(){
        header('Content-Type:text/xml');
        $flowFilePath = Config::get('flow_file_path');
        $flowList = ActivityParser::getFlowList($flowFilePath);
        $flowXml = '';
        foreach($flowList as $flow){
            $flowXml .= '<Flow src="' .$flow['file_name']. '" label="' .$flow['flow_name'] .'"></Flow>';
        }
        echo '<Flows>'.$flowXml.'</Flows>';
    }

    public function open($request){
        $filename = array_get($request, 'filename');
        $flowFilePath = Config::get('flow_file_path');
        if(is_null($filename)){
            echo '文件名称为空，打开失败！';
            die;
        }elseif(!file_exists($flowFilePath . $filename)){
            echo '文件不存在，打开失败！';
            die;
        }
        header('Content-Type:text/xml');
        echo file_get_contents($flowFilePath . $filename);
    }

    public function save($request){
        $filename = array_get($request, 'filename');
        $xml = array_get($request, 'xml');
        if(is_null($filename) || is_null($xml)){
            echo '输入的文件名称或内容为空，保存失败！';
            die;
        }

        $flowFilePath = Config::get('flow_file_path');
        file_put_contents($flowFilePath . DIRECTORY_SEPARATOR . $filename, urldecode($xml));

        echo '保存成功';
        die;
    }

    public function export(){
        var_dump(Flow::newInstance('workflow', [
            'logContactKey'=>'test123'
        ])->run());
        echo 'export';
    }

    public function templates(){
        header('Content-Type:text/xml');
        $templatesXml = $this->getCoreActivitiesForTemplate();
        echo '<Activities>'.$templatesXml.'</Activities>';
    }

    private function getCoreActivitiesForTemplate(){

        $classes = ActivityParser::getAllActivityClass();
        $templatesXml = '';
        foreach($classes as $class){
            $templatesXml .= ActivityParser::getTemplate($class);
        }
        return $templatesXml;
    }
}




