<?php
namespace DavinBao\WorkflowCore\Activities;

/**
 * Event Trait
 *
 * @class  Event
 */
trait EventTrait {
    private $eventMap = array();

    protected function on($key, $handle) {
        $this->eventMap[$key] = $handle;
    }

    protected function trigger($key, $scope = null) {
        if(isset($this->eventMap[$key])){
            call_user_func_array( $this->eventMap[$key] , $scope);
        }
    }
}

