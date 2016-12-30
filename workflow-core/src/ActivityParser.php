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

    public static function getAllActivityClass(){
        $classes = [];
        $activitiesConfig = Config::get('activities_file_path');
        foreach($activitiesConfig as $activityConfig){
            $classes = array_merge($classes, static::getClassesOfPath($activityConfig['path'], $activityConfig['namespace']));
        }
        return $classes;
    }

    /**
     * 获取类名称
     * @param $path
     * @param $namespace
     * @return array
     */
    public static function getClassesOfPath($path, $namespace)
    {
        $classes = [];

        foreach (glob($path . '/*.php') as $file)
        {
            require_once $file;

            $class = $namespace . basename($file, '.php');
            if (class_exists($class))
            {
                array_push($classes, $class);
            }
        }
        return $classes;
    }

    /**
     * 获取 Activity 模板 XML
     * @param $className
     * @return string
     */
    public static function getTemplate($className){
        $class = new \ReflectionClass($className);
        if($class->isAbstract() || strpos($class->getShortName(), 'Activity') === false) return '';
        //获取 Activity Name
        $activityFullName = $class->getShortName();
        $activityShortName = substr($activityFullName, 0, strlen($activityFullName) - 8);
        $activityStyle = $class->newInstance()->shape;
        $activityFillColor = $class->newInstance()->shapeFillColor;
        $activityWidth = $class->newInstance()->shapeWidth;
        $activityHeight = $class->newInstance()->shapeHeight;
        $activityIcon = $class->newInstance()->shapeIcon;
        $activityPressedIcon = $class->newInstance()->shapePressedIcon;
        //获取出入参数
        $inProperties = [];
        $outProperties = [];
        foreach($class->getProperties(\ReflectionProperty::IS_PUBLIC) as $property){
            $propertyName = $property->getName();
            if(strpos($propertyName, 'in') === 0){
                $inProperties[$propertyName] = $property->getValue($class->newInstance());
            }elseif(strpos($propertyName, 'out') === 0){
                $outProperties[$propertyName] = $property->getValue($class->newInstance());
            }
        }
        $inPropertiesStr = '';
        foreach($inProperties as $key => $value){
            $inPropertiesStr .= $key . '="' . $value . '",';
        }
        $outPropertiesStr = '';
        foreach($outProperties as $key => $value){
            $outPropertiesStr .= $key . '="' . $value . '",';
        }
        $inPropertiesStr = substr($inPropertiesStr, 0, strlen($inPropertiesStr) - 1);
        $outPropertiesStr = substr($outPropertiesStr, 0, strlen($outPropertiesStr) - 1);

        return '
        <add as="' .$activityShortName. '"  icon="' .$activityIcon. '" pressedIcon="' .$activityPressedIcon. '" style="' .$activityStyle. '" fillColor="' .$activityFillColor. '">
			<' .$activityFullName. ' label="' .$activityShortName. '" description="" ' .$inPropertiesStr. ' ' .$outPropertiesStr. '>
				<mxCell vertex="1" style="' .$activityStyle. '">
					<mxGeometry as="geometry" width="' .$activityWidth. '" height="' .$activityHeight. '"/>
				</mxCell>
			</' .$activityFullName. '>
		</add>';
    }

}
