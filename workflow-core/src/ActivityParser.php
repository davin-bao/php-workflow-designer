<?php
namespace DavinBao\WorkflowCore;

use Psy\Reflection\ReflectionConstant;

class ActivityParser {

    /**
     * get Flow list
     * @param $path
     * @return array
     */
    public static function getFlowList($path){
        $flowList = [];
        foreach (glob($path . '/*.xml') as $file) {
            try{
                $doc=new \DOMDocument();
                //加载XML文件
                $doc->load($file);
                $workflowDoc = $doc->getElementsByTagName('Workflow');
                $flowName = $workflowDoc && $workflowDoc->length > 0 && $workflowDoc->item(0)->hasAttribute('label') ? $workflowDoc->item(0)->getAttribute('label') : 'undefined';
            }catch(\Exception $e){
                $flowName = $e->getMessage();
            }
            array_push($flowList, [
                'file_name'=> basename($file),
                'flow_name'=> $flowName
            ]);
        }
        return $flowList;
    }
}
