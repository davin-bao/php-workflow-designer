<?php
namespace DavinBao\WorkflowCore\Activities;

/**
 * Begin Activity
 *
 * @class  Activity
 */
abstract class BeginActivity extends Activity {

    const RETURN_CODE = [
        self::BEGIN_CODE
    ];

    /**
     * @param array $parameters
     * @return mixed
     */
    protected function action(array $parameters){
        return self::BEGIN_CODE;
    }
}

