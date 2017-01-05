<?php
namespace DavinBao\WorkflowCore\Activities;
use DavinBao\WorkflowCore\Flows\Flow;

/**
 * Sub Flow Activity
 *
 * @class  Activity
 */
class SubFlowActivity extends TaskActivity {

    const RETURN_CODE = [];

    protected $inParameterKeys = [
        'flow_name'
    ];

    public function action(){
        return Flow::newInstance($this->flow_name, $this->parameters)->run();
    }
}

