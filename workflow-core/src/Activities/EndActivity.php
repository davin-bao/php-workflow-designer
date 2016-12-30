<?php
namespace DavinBao\WorkflowCore\Activities;

/**
 * Activity Base Class
 *
 * @class  Activity
 */
class EndActivity extends Activity {

    const RETURN_CODE = [
        self::END_CODE
    ];

    public $shapeIcon = 'images/end.gif';
    public $shapePressedIcon = 'images/end-pressed.gif';

    /**
     * @param array $parameters
     * @return mixed
     */
    protected function action(){
        return self::END_CODE;
    }
}

