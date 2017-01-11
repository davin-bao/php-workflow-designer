<?php
namespace DavinBao\WorkflowCore\Activities;

/**
 * Select Activity
 *
 * @class  Activity
 */
abstract class SelectActivity extends Activity {

    public $shape = 'select';
    public $shapeIcon = 'images/rhombus.gif';
    public $shapePressedIcon = 'images/rhombus-pressed.gif';

    protected $width = 128;
    protected $height = 64;

}

