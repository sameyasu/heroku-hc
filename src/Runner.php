<?php declare(strict_types=1);
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
        require_once __DIR__ . '/../vendor/autoload.php';

        $tz = getenv('TZ');
        if ($tz !== false) {
            date_default_timezone_set($tz);
        }

        $me = new self();
        $me->run();
    }

    /**
     * Running
     */
    public function run() : void
    {
        $parser = new OptionParser();
        $url = $parser->parseUrl(getenv('HC_URL') ?: '');
        if ($url === null) {
            throw new \RuntimeException('URL is invalid');
        }

        list($minInterval, $maxInterval) = $parser->parseInterval(getenv('INTERVAL') ?: '');
        $runningHours = $parser->parseHours(getenv('HOURS') ?: '');

        $this->logger->info(
            'Started Options',
            [
                'interval' => [$minInterval, $maxInterval],
                'hours' => $runningHours,
            ]
        );

        $checker = new HttpUrlChecker($url);

        (new Timer(
            function () use ($checker) {
                $checker->check();
            }
        ))
        ->setRandomInterval($minInterval, $maxInterval)
        ->setRunningHours($runningHours)
        ->start();
    }
}
