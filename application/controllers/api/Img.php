<?php

use dkm\libraries\base\API_Controller;
use dkm\libraries\service\ImgService;

class Img extends API_Controller {
    public function index_put() {
        $response = new Result();
        $uid = $this->put('uid');
        $url = $this->put('url');
        $size = intval($this->put('size'));
        $md5 = $this->put('md5');

        $imgService = ImgService::get_instance();
        $add_result = $imgService->addImg($uid, $url, $size, $md5);
        if (!$add_result->success) {
            $response->set_error($add_result->message);
            $this->response($response);
        }

        $response->img_id = $add_result->img_id;
        $response->set_success($add_result->message);
        $this->response($response);
    }

    public function index_delete() {
        $response = new Result();
        $img_id = $this->delete('img_id');

        $imgService = ImgService::get_instance();
        $delete_result = $imgService->deleteImg($img_id);
        if (!$delete_result->success) {
            $response->set_error($delete_result->message);
            $this->response($response);
        }

        $response->set_success('删除成功');
        $this->response($response);
    }

    public function index_get() {
        $response = new Result();
        $img_id = $this->get('img_id');

        $imgService = ImgService::get_instance();
        $get_result = $imgService->getImgById($img_id);
        if (!$get_result->success) {
            $response->set_error($get_result->message);
            $this->response($response);
        }

        $response->img = $get_result->img;
        $response->set_success($get_result->message);
        $this->response($response);
    }

    public function list_get() {
        $response = new Result();
        $uid = $this->get('uid');
        $page = $this->get('page') ? $this->get('page') : 0;
        $page_size = $this->get('size') ? $this->get('size') : 100;

        $imgService = ImgService::get_instance();
        $list_result  =$imgService->listImgByUid($uid, $page, $page_size);
        if (!$list_result->success) {
            $response->set_error($list_result->message);
            $this->response($response);
        }

        $response->imgs = $list_result->imgs;
        $response->set_success($list_result->message);
        $this->response($response);
    }
}
