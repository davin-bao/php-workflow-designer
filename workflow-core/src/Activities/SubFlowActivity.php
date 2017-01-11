<?php
namespace DavinBao\WorkflowCore\Activities;
use DavinBao\WorkflowCore\Flows\Flow;

/**
 * Sub Flow Activity
 *
 * @class  Activity
 */
class SubFlowActivity extends Activity {

    const RETURN_CODE = [];

    public $flowName = '';

    protected $shape = 'subflow';
    protected $shapeIcon = 'images/subflow.gif';
    protected $shapePressedIcon = 'images/subflow-pressed.gif';
    protected $shapeFillColor = '#FFAD5C';
    protected $width = 64;
    protected $height = 32;

    private $flow = null;

    public function action(){
        return $this->flow->run();
    }

    protected function initAttributes(){
        parent::initAttributes();
        $this->flow = Flow::newInstance($this->flowName, $this->parameters);
    }

    public function getFlow(){
        return $this->flow;
    }
}

