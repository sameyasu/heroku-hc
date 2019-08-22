<?php
namespace HerokuHC;

use Monolog\Handler\StreamHandler;
use Monolog\Formatter\LineFormatter;
use Psr\Log\LoggerAwareTrait;

/**
 * LoggerTrait for Monolog\Logger
 */
trait MonologLoggerTrait
{
    use LoggerAwareTrait;

    /**
     * initialize $this->logger
     * @return void
     */
    protected function initLogger() : void
    {
        $logger = new \Monolog\Logger(__CLASS__);

        $streamHandler = new StreamHandler('php://stderr', \Monolog\Logger::DEBUG);
        $formatter = new LineFormatter(
            null, // use default
            null, // use default
            true, // allowInlineLineBreaks: true
            true  // ignoreEmptyContextAndExtra: true
        );
        $streamHandler->setFormatter($formatter);

        $logger->pushHandler($streamHandler);

        $this->setLogger($logger);
    }
}
