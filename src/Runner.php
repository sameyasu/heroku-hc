<?php
/**
 * HerokuHC/Runner
 *
 * @author sameyasu
 * @version 0.1
 * @copyright 2019 sameyasu
 * @package HerokuHC
 * @license MIT
 **/
namespace HerokuHC;

/**
 * Runner Class
 **/
final class Runner
{
    use MonologLoggerTrait;

    /**
     * Constructor
     **/
    private function __construct()
    {
        $this->initLogger();
    }

    /**
     * Starting runner
     * @return void
     */
    public static function start() : void
    {
        $me = new self();
        $me->run();
    }

    /**
     * Running
     */
    public function run() : void
    {
        $this->logger->info('Started');
    }
}
