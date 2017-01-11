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

    protected $shape = 'begin';
    protected $shapeIcon = 'images/begin.gif';
    protected $shapePressedIcon = 'images/begin-pressed.gif';
    protected $shapeFillColor = '#DEFFDE';

    protected $width = 64;
    protected $height = 32;

    /**
     * @param array $parameters
     * @return mixed
     */
    protected function action(){
        return self::BEGIN_CODE;
    }
}

