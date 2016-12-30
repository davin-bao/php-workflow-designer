<?php
namespace DavinBao\WorkflowCore\Activities;

/**
 * Log Activity
 *
 * @class  Activity
 */
class LogActivity extends TaskActivity {

    const RETURN_CODE = [1];

    public $inParameterKeys = [
        'inLogContent'
    ];

    public $outParameterKeys = [
        'outLogFileName'
    ];

    public function action(){
        file_put_contents('action.log', ((new \DateTime())->format('H:i:s')) . $this->inLogContent . PHP_EOL, FILE_APPEND);
        $this->outLogFileName = array('action.log');
        return 1;
    }

}

