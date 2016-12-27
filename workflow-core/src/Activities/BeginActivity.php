<?php
namespace DavinBao\WorkflowCore\Activities;

/**
 * Begin Activity
 *
 * @class  Activity
 */
class BeginActivity extends Activity {

    const RETURN_CODE = [
        self::BEGIN_CODE
    ];

    public $shape = 'begin';

    /**
     * @param array $parameters
     * @return mixed
     */
    protected function action(array $parameters){
        return self::BEGIN_CODE;
    }
}

