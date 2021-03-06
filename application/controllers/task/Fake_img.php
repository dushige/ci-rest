<?php

use dkm\libraries\base\Task_base;
use dkm\libraries\service\ImgService;
use Faker\Factory;

class Fake_img extends Task_base {
    public function execute() {
        $imgService = ImgService::get_instance();
        $factory = Factory::create();

        for ($i = 1; $i < 500; $i++) {
            $uid = mt_rand(1, 400);
            $url = $factory->imageUrl();
            $size = mt_rand(2591, 20971520);
            $md5 = $factory->md5;
            $add_result = $imgService->addImg($uid, $url, $size, $md5);
            if (!$add_result->success) {
                daemon_log_info("fail $add_result->message |uid: $uid, url: $url, size: $size, md5: $md5");
            } else {
                daemon_log_info("success   |id:$add_result->img_id ,uid: $uid, url: $url, size: $size, md5: $md5");
            }
        }

        exit('done');
    }
}
