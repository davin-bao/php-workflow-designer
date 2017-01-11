<?php
namespace DavinBao\WorkflowDesigner;

use DavinBao\WorkflowCore\Activities\Activity;
use DavinBao\WorkflowCore\Activities\LogTrait;
use DavinBao\WorkflowCore\Config;
use DavinBao\WorkflowCore\Engine;
use DavinBao\WorkflowCore\Flows\Flow;
use DavinBao\WorkflowCore\Models\Process;

/**
 * Service Class
 *
 * @class  Activity
 */
class Service {
    use LogTrait;

    public function __construct(array $request){
        $action = array_get($request, '_action',  'open');

        try{
            call_user_func_array(array($this, $action), array($request));
        }catch(\Exception $e){
            self::getLogger()->error('请求（'. $action . ')错误：' .$e->getMessage().PHP_EOL. 'Trace：' .$e->getTraceAsString());
            http_response_code(intval($e->getCode()));
            echo $e->getMessage();
            print_r($e->getTraceAsString());
            die;
        }
    }

    public function index(){
        header('Content-Type:text/xml');
        $flowList = Flow::getAll();
        $flowXml = '';
        foreach($flowList as $key=>$value){
            $flowXml .= '<Flow src="' .$value. '" label="' .$key .'"></Flow>';
        }
        echo '<Flows>'.$flowXml.'</Flows>';
    }

    public function open($request){
        $filename = array_get($request, 'filename');
        header('Content-Type:text/xml');

        echo Flow::getXml($filename);
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

//        Engine::init()->createProcess('workflow1', [
//            'logContactKey'=>'test123'
//        ])->start();
        Engine::init()->setProcess(38)->start();

//        print_r(Flow::getXml('workflow1'));
//        print_r(Flow::newInstance('workflow1', [
//            'logContactKey'=>'test123'
//        ])->run());
//        echo 'export';
    }

    /**
     * 获取所有活动模板列表
     */
    public function templates(){
        header('Content-Type:text/xml');
        $classNames = Activity::getAllActivityClassName();
        $templatesXml = '';
        foreach($classNames as $className){
            $class = new \ReflectionClass($className);
            $templatesXml .= $class->newInstance()->getTemplateXml();
        }

        echo '<Activities>'.$templatesXml.'</Activities>';
    }

    /**
     * 获取所有互动列表
     */
    public function activities(){
        header('Content-Type:text/xml');
        $classNames = Activity::getAllActivityClassName();
        $xml = '';
        foreach($classNames as $className) {
            $class = new \ReflectionClass($className);
            $instance = $class->newInstance();
            $xml .= '<Activity name="' .basename($className). '" label="' .$instance->label .'" description="' .$instance->description .'"></Activity>';
        }

        echo '<Activities>'.$xml.'</Activities>';
    }
}




