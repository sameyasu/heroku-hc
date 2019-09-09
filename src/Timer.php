<?php
/**
 * HerokuHC/Timer
 *
 * @author sameyasu
 * @version 0.1
 * @copyright 2019 sameyasu
 * @package HerokuHC
 * @license MIT
 **/
namespace HerokuHC;

/**
 * Timer Class
 **/
final class Timer
{
    use MonologLoggerTrait;

    private $callback;

    private $minInterval;

    private $maxInterval;

    private $runningHours;

    private $runningWeekdays;

    /**
     * Constructor
     **/
    public function __construct(callable $callback)
    {
        $this->initLogger();

        $this->callback = $callback;
    }

    /**
     * Set Random Seconds Interval
     * @param int $minInSeconds
     * @param int $maxInSeconds
     * @return self
     */
    public function setRandomInterval(int $minInSeconds, int $maxInSeconds) : self
    {
        $this->minInterval = $minInSeconds;
        $this->maxInterval = $maxInSeconds;
        return $this;
    }

    /**
     * Set Interval n seconds
     * @param int $inSeconds
     * @return self
     */
    public function setInterval(int $inSeconds) : self
    {
        $this->minInterval = $this->maxInterval = $inSeconds;
        return $this;
    }

    /**
     * Set running hours in array
     * @param array $hours
     * @return self
     */
    public function setRunningHours(array $hours) : self
    {
        $this->runningHours = $hours;
        return $this;
    }

    /**
     * Set running weekdays in array
     * @param array $weekdays
     * @return self
     */
    public function setRunningWeekdays(array $weekdays) : self
    {
        $this->runningWeekdays = $weekdays;
        return $this;
    }

    /**
     * Start timer
     * @return void
     */
    public function start() : void
    {
        while (true) {
            if ($this->isRunningTime(time())) {
                $this->logger->info('Run');
                // runs callback
                ($this->callback)();
            } else {
                $this->logger->info('Skipped (not running time)');
            }

            $intervalInSec = $this->getNextIntarval();
            $nextRuns = date('Y-m-d H:i:sP', time() + $intervalInSec);
            $this->logger->debug('Interval', ['seconds' => $intervalInSec, 'next' => $nextRuns]);
            sleep($intervalInSec);
        }
    }

    /**
     * Get next interval in seconds
     * @return int
     */
    private function getNextIntarval() : int
    {
        if ($this->minInterval === $this->maxInterval || $this->maxInterval < $this->minInterval) {
            return $this->minInterval;
        }

        return mt_rand($this->minInterval, $this->maxInterval);
    }

    /**
     * Is it running time ?
     * @param int $unixtime
     * @return bool
     */
    private function isRunningTime(int $unixtime) : bool
    {
        $hour = intval(date('G', $unixtime));
        if (in_array($hour, $this->runningHours, false)) {
            $weekday = intval(date('N', $unixtime));
            if (in_array($weekday, $this->runningWeekdays, false)) {
                return true;
            }
        }

        return false;
    }
}
