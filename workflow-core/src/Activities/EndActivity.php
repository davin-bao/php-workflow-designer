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

    protected $shape = 'end';
    protected $shapeIcon = 'images/end.gif';
    protected $shapePressedIcon = 'images/end-pressed.gif';
    protected $shapeFillColor = '#FFDEDE';

    protected $width = 64;
    protected $height = 32;

    public $returnCode = self::END_CODE;

    /**
     * @param array $parameters
     * @return mixed
     */
    protected function action(){
        return $this->returnCode;
    }
}

