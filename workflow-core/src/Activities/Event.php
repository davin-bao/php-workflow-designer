<?php
namespace DavinBao\WorkflowCore\Activities;

/**
 * Event Class
 *
 * @class  Event
 */
class Event {
    private $eventMap = array();

    protected function on($key, $handle) {
        $this->eventMap[$key] = $handle;
    }

    protected function trigger($key, $scope = null) {
        call_user_func_array( $this->eventMap[$key] , $scope);
    }
}

