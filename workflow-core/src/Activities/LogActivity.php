<?php
namespace DavinBao\WorkflowCore\Activities;

/**
 * Log Activity
 *
 * @class  Activity
 */
class LogActivity extends SelectActivity {

    const RETURN_CODE = [1];

    public $description = '传递数据写入日志';

    protected $inParameterKeys = [
        'inLogContent'=>'12'
    ];

    protected $outParameterKeys = [
        'outLogFileName'=>'34',
        'test'=>'213'
    ];

    public function action(){
        file_put_contents('action.log', ((new \DateTime())->format('H:i:s')) . $this->inLogContent . PHP_EOL, FILE_APPEND);
        $this->outLogFileName = array('action.log');
        return 1;
    }

}

