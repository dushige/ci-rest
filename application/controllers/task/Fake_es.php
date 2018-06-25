<?php

use dkm\libraries\base\Task_base;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Psr\Http\Message\ResponseInterface;
use Faker\Factory;

class Fake_es extends Task_base {
    public function execute() {
        $http_client = new Client([
            'base_uri' => 'http://localhost:9200/person/man'
        ]);

        $faker_factory = Factory::create('zh_CN');

        for ($i = 1; $i < 500; $i++) {
            $data = [
                'name' => $faker_factory->name,
                'age' => mt_rand(10, 80),
                'country' => $faker_factory->country
            ];
            $http_client->postAsync('', $data)->then(
                function (ResponseInterface $res) {
                    daemon_log_info($res->getStatusCode());
                },
                function (RequestException $e) {
                    daemon_log_info($e->getMessage());
                }
            );
        }

        exit('done');
    }
}