<?php
namespace DavinBao\WorkflowCore\Activities;

/**
 * Render View Activity
 *
 * @class  Activity
 */
abstract class ViewActivity extends Activity {

    const RETURN_CODE = [
        self::USER_VIEW_RENDER_RETURN_CODE
    ];

    abstract public function getView();
    /**
     * @param array $parameters
     * @return mixed
     */
    protected function action(){
        $this->render($this->dataCache);
        return self::USER_VIEW_RENDER_RETURN_CODE;
    }

    abstract protected function render(array $parameters);
}

