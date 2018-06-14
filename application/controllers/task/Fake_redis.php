<?php

use dkm\libraries\base\Task_base;
use dkm\libraries\util\RedisFactory;
use Faker\Factory;

class Fake_redis extends Task_base {
    public function execute() {
        $r_client = RedisFactory::get_dkm_client();
        $faker = Factory::create();
        $expiration = 600;
        for ($i = 1; $i < 100000; $i++) {
            $key = $faker->md5;
            $value = $faker->md5 . $faker->md5 . $faker->md5 . $faker->md5;
            $result = $r_client->setEx($key, $value, $expiration);
            daemon_log_info("$key  $value  " . ($result ? "success" : "fail"));
        }

        exit('done');
    }
}
