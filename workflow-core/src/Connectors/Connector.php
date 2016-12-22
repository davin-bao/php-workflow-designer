<?php
namespace DavinBao\WorkflowCore\Connectors;

/**
 * Connector Base Class
 *
 * @class  Activity
 */
abstract class Connector {

    public $returnCode = 0;
    public $fromActivity = null;
    public $toActivity = null;

}

