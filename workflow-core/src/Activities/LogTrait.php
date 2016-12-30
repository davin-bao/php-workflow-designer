<?php
namespace DavinBao\WorkflowCore\Activities;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use DavinBao\WorkflowCore\Config;

trait LogTrait {
    private static $logger = null;

    /**
     * init Logger
     * @return Logger|null
     */
    protected static function getLogger(){
        if(is_null(self::$logger)){
            $loggerName = basename(get_called_class());
            self::$logger = new Logger($loggerName);
            $dateTime = new \DateTime('now');
            $dailyName = $dateTime->format('Y-m-d') . '.log';
            $logFile = Config::get('log_path', __DIR__) . $loggerName . '_DEBUG_' .$dailyName;
            self::$logger->pushHandler(new StreamHandler($logFile, Logger::DEBUG));
            $logFile = Config::get('log_path', __DIR__) . $loggerName . '_INFO_' .$dailyName;
            self::$logger->pushHandler(new StreamHandler($logFile, Logger::INFO));
        }
        return self::$logger;
    }

    /**
     * Exceptional occurrences that are errors.
     *
     * @param string $message
     * @param array  $context
     * @return void
     */
    protected function error($message, array $context = array()){
        $this->monolog->{__FUNCTION__}($message, $context);
    }

    /**
     * Exceptional occurrences that are not errors.
     *
     * @param string $message
     * @param array  $context
     * @return void
     */
    protected function warning($message, array $context = array()){
        $this->monolog->{__FUNCTION__}($message, $context);
    }

    /**
     * Interesting events.
     *
     * @param string $message
     * @param array  $context
     *
     * @return void
     */
    protected function info($message, array $context = array()){
        $this->monolog->{__FUNCTION__}($message, $context);
    }

    /**
     * Detailed debug information.
     *
     * @param string $message
     * @param array  $context
     *
     * @return void
     */
    protected function debug($message, array $context = array()){
        $this->monolog->{__FUNCTION__}($message, $context);
    }
}