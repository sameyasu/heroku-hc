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

use GuzzleHttp\Client;
use GuzzleHttp\Promise\Promise;
use GuzzleHttp\Promise\PromiseInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Runner Class
 **/
final class Runner
{
    use MonologLoggerTrait;

    private $httpClient;

    /**
     * Constructor
     **/
    private function __construct()
    {
        $this->initLogger();
        $this->initHttpClient();
    }

    /**
     * Starting runner
     * @return void
     */
    public static function start() : void
    {
        require_once __DIR__ . '/../vendor/autoload.php';

        $me = new self();
        $me->run();
    }

    /**
     * Running
     */
    public function run() : void
    {
        $url = getenv('HC_URL');
        $interval = getenv('INTERVAL') ? getenv('INTERVAL') : 10 * 60;

        $this->logger->info('Started', ['HC_URL' => $url, 'interval' => $interval]);

        while (true) {
            $result = $this->checkUrl($url);
            $this->logger->info('Checked', ['result' => $result]);

            sleep($interval);
        }
    }

    private function checkUrl(string $url) : array
    {
        $result = [
            'ok' => false
        ];
        $this->httpClient->requestAsync('GET', $url, [
            'on_stats' => function ($stats) use (&$result) {
                $result['transfer_time'] = $stats->getTransferTime();
            }
        ])
        ->then(
            function (ResponseInterface $response) use (&$result) {
                $result['status_code'] = $response->getStatusCode();
                if ($response->getStatusCode() === 200) {
                    $result['body'] = $response->getBody()->getContents();
                    $result['ok'] = true;
                } else {
                    $this->logger->notice('Invalid StatusCode', [
                        'statusCode' => $response->getStatusCode()
                    ]);
                }
            },
            function (RequestException $exception) {
                $this->logger->notice('Rejected', [
                    'exception' => $exception
                ]);
            }
        )
        ->wait();

        return $result;
    }

    private function initHttpClient() : void
    {
        $this->httpClient = new Client([
            'timeout'  => 10.0,
        ]);
    }
}
