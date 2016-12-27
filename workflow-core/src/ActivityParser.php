<?php
namespace DavinBao\WorkflowCore;

use Psy\Reflection\ReflectionConstant;

class ActivityParser {

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

    public static function getTemplate($className){
        $class = new \ReflectionClass($className);
        if($class->isAbstract() || strpos($class->getShortName(), 'Activity') === false) return '';
        //获取 Activity Name
        $activityFullName = $class->getShortName();
        $activityShortName = substr($activityFullName, 0, strlen($activityFullName) - 8);
        $activityStyle = empty($class->newInstance()->shape) ? 'ellipse' : $class->newInstance()->shape;
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
        <add as="' .$activityShortName. '">
			<' .$activityFullName. ' label="' .$activityShortName. '" description="" ' .$inPropertiesStr. ' ' .$outPropertiesStr. '>
				<mxCell vertex="1" style="' .$activityStyle. '">
					<mxGeometry as="geometry" width="32" height="32"/>
				</mxCell>
			</' .$activityFullName. '>
		</add>';
    }

    public static function getToolbar($className){
        $class = new \ReflectionClass($className);
        if($class->isAbstract() || strpos($class->getShortName(), 'Activity') === false) return '';
        //获取 Activity Name
        $activityFullName = $class->getShortName();
        $activityShortName = substr($activityFullName, 0, strlen($activityFullName) - 8);
        $activityLabel = $class->newInstance()->label;
        $activityStyle = empty($class->newInstance()->shape) ? 'ellipse' : $class->newInstance()->shape;
        $activityIcon = $activityStyle . '.gif';
        return '<add as="' .$activityShortName. '" template="' .$activityShortName. '" icon="images/' .$activityIcon. '"/>';
    }

}
