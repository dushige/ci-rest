<?php

use dkm\libraries\base\Task_base;
use dkm\libraries\service\UserService;
use Faker\Factory;

class Fake_user extends Task_base {
    public function execute() {
        $userService = UserService::get_instance();
        $faker = Factory::create('zh_CN');

        for ($i = 1; $i < 500; $i++) {
            $name = $faker->name;
            $email = $faker->email;
            $username = $faker->userName;
            $password = 'AAAaaa111';
            $tel = $faker->phoneNumber;
            $register_result = $userService->register($username, $password, $name, $email, $tel);
            if (!$register_result->success) {
                daemon_log_info("fail  $register_result->message | username: $username, name: $name, email: $email, tel: $tel");
            } else {
                daemon_log_info("success |  username: $username, name: $name, email: $email, tel: $tel");
            }
        }

        exit('done');
    }
}
