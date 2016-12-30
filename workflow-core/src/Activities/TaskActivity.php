<?php
namespace DavinBao\WorkflowCore\Activities;

/**
 * Task Activity
 *
 * @class  Activity
 */
abstract class TaskActivity extends Activity {

    public $shape = 'task';
    public $shapeIcon = 'images/task.gif';
    public $shapePressedIcon = 'images/task-pressed.gif';

}

