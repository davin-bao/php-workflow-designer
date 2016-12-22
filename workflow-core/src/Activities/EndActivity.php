<?php
namespace DavinBao\WorkflowCore\Activities;

/**
 * Activity Base Class
 *
 * @class  Activity
 */
abstract class EndActivity extends Activity {

    const RETURN_CODE = [
        self::END_CODE
    ];

    /**
     * @param array $parameters
     * @return mixed
     */
    protected function action(array $parameters){
        return self::END_CODE;
    }
}

