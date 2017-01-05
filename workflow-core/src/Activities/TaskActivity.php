<?php
namespace DavinBao\WorkflowCore\Activities;

/**
 * Task Activity
 *
 * @class  Activity
 */
abstract class TaskActivity extends Activity {

    protected $shape = 'task';
    protected $shapeIcon = 'images/task.gif';
    protected $shapePressedIcon = 'images/task-pressed.gif';

}

