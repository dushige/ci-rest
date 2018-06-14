<?php

use dkm\libraries\base\Task_base;
use dkm\libraries\util\MemcachedFactory;
use Faker\Factory;

class Fake_memcached extends Task_base {
    public function execute() {
        $m_client = MemcachedFactory::get_dkm_client();
        $faker = Factory::create();
        $expiration = 600;
        for ($i = 1; $i < 1000000; $i++) {
            $key = $faker->md5;
            $value = $faker->md5 . $faker->md5 . $faker->md5 . $faker->md5;
            $result = $m_client->set($key, $value, $expiration);
            daemon_log_info("$key  $value  " . ($result ? "success" : "fail"));
        }

        exit('done');
    }
}
