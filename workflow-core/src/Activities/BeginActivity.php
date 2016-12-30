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
    public $shapeIcon = 'images/begin.gif';
    public $shapePressedIcon = 'images/begin-pressed.gif';

    /**
     * @param array $parameters
     * @return mixed
     */
    protected function action(){
        return self::BEGIN_CODE;
    }
}

