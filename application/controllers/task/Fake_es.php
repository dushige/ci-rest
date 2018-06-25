<?php

use dkm\libraries\base\Task_base;
use GuzzleHttp\Client;
use Faker\Factory;

class Fake_es extends Task_base {
    public function execute() {
        $http_client = new Client([
            'base_uri' => 'http://47.96.101.162:9200/',
            'headers' => ['Content-Type' => 'application/json']
        ]);

        $faker_factory = Factory::create('zh_CN');

        for ($i = 1; $i < 500; $i++) {
            $data = [
                'name' => $faker_factory->name,
                'age' => mt_rand(10, 80),
                'country' => $faker_factory->country
            ];

            try {
                $res = $http_client->post('person/man', ['json' => $data]);
                daemon_log_info($res->getBody()->getContents());
            } catch (Exception $e) {
                daemon_log_info($e->getMessage());
            }

        }

        exit('done');
    }
}
