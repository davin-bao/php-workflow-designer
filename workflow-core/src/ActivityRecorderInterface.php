<?php
namespace DavinBao\WorkflowCore;

use DavinBao\WorkflowCore\Activities;
use DavinBao\WorkflowCore\Flows\Flow;
use DavinBao\WorkflowCore\Exceptions;

/**
 * Engine Class
 *
 * @class  Activity
 */
interface ActivityRecorderInterface {
    public function save($processId, $activity, $returnCode);
}

