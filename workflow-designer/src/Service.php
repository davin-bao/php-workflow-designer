<?php
namespace DavinBao\WorkflowDesigner;
use DavinBao\WorkflowCore\Config;
use DavinBao\WorkflowCore\ActivityParser;

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

    public function templates(){
        $templatesXml = $this->getCoreActivitiesForTemplate();
        echo '<mxEditor><Array as="templates">
		<add as="group">
			<Group label="" description="" href="">
				<mxCell vertex="1" style="group" connectable="0"/>
			</Group>
		</add>
		<add as="edge">
			<Connector label="" href="">
				<mxCell edge="1">
					<mxGeometry as="geometry" relative="1"/>
				</mxCell>
			</Connector>
		</add>'.$templatesXml.'
		</Array></mxEditor>';
    }

    public function toolbars(){
        $toolbarXml = $this->getCoreActivitiesForToolbar();
        echo '<mxDefaultToolbar>' .$toolbarXml. '</mxDefaultToolbar>';
    }

    private function getCoreActivitiesForTemplate(){
        $path = '../../workflow-core/src/Activities';
        $namespace = 'DavinBao\WorkflowCore\Activities\\';

        $classes = ActivityParser::getClassesOfPath($path, $namespace);
        $templatesXml = '';
        foreach($classes as $class){
            $templatesXml .= ActivityParser::getTemplate($class);
        }
        return $templatesXml;
    }

    private function getCoreActivitiesForToolbar(){
        $path = '../../workflow-core/src/Activities';
        $namespace = 'DavinBao\WorkflowCore\Activities\\';

        $classes = ActivityParser::getClassesOfPath($path, $namespace);
        $templatesXml = '';
        foreach($classes as $class){
            $templatesXml .= ActivityParser::getToolbar($class);
        }
        return $templatesXml;
    }
}




