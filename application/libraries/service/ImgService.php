<?php

namespace dkm\libraries\service;
use dkm\libraries\BaseService;

/**
 * Class ImgService
 * @package dkm\libraries\service
 * @method static ImgService get_instance()
 */
class ImgService extends BaseService {
    const IMG_STATUS_NORMAL = 1;
    const IMG_STATUS_DELETE = 2;
    const IMG_STATUS_INVISIBLE = 3;

    public static $IMG_STATUS_MAP = [
        self::IMG_STATUS_NORMAL => '正常',
        self::IMG_STATUS_DELETE => '删除',
        self::IMG_STATUS_INVISIBLE => '不可见'
    ];

    /**
     * 添加图片
     *
     * @param $uid
     * @param $url
     * @param $size
     * @param $md5
     * @return \Result
     */
    public function addImg($uid, $url, $size, $md5) {
        $result = $this->beforeAddImg($uid, $url, $size, $md5);
        if (!$result->success) {
            return $result;
        }

        $this->doAddImg($result);
        if (!$result->success) {
            return $result;
        }

        $this->afterAddImg($result);
        return $result;
    }

    /**
     * 添加图片前检查参数
     *
     * @param int $uid
     * @param string $url
     * @param int $size 字节
     * @param string $md5
     * @return \Result
     */
    private function beforeAddImg($uid, $url ,$size, $md5) {
        $result = new \Result();

        if (!check_id($uid)) {
            $result->set_error('参数错误:uid');
            return $result;
        }

        if (!check_url($url)) {
            $result->set_error('参数错误:url');
            return $result;
        }

        if (!check_img_size($size)) {
            $result->set_error('参数错误:size');
            return $result;
        }

        if (!check_md5($md5)) {
            $result->set_error('参数错误:md5');
            return $result;
        }

        $result->uid = $uid;
        $result->url = $url;
        $result->size = $size;
        $result->md5 = $md5;
        $result->set_success();
        return $result;
    }

    /**
     * 添加图片
     *
     * @param \Result $result
     * @return \Result
     */
    private function doAddImg(&$result) {
        $uid = $result->uid;
        $url = $result->url;
        $size = $result->size;
        $md5 = $result->md5;

        load_model('img');
        $img_id = $this->CI->img->add($uid, $url, $size, $md5);
        if (empty($img_id)) {
            $result->set_error('网络繁忙');
            return $result;
        }

        $result->img_id = $img_id;
        $result->set_success('添加成功');
        return $result;
    }

    /**
     * 添加图片之后的操作
     *
     * @param $result
     * @return \Result
     */
    private function afterAddImg(&$result) {
        // 暂时不做啥
        return $result;
    }

    /**
     * 删除图片
     *
     * @param $img_id
     * @return \Result
     */
    public function deleteImg($img_id) {
        $result = $this->beforeDeleteImg($img_id);
        if (!$result->success) {
            return $result;
        }

        $this->doDeleteImg($result);
        if (!$result->success) {
            return $result;
        }

        $this->afterDeleteImg($result);
        return $result;
    }

    /**
     * 删除图片前参数检查
     *
     * @param $img_id
     * @return \Result
     */
    private function beforeDeleteImg($img_id) {
        $result = new \Result();

        if (!check_id($img_id)) {
            $result->set_error('参数错误:img_id');
            return $result;
        }

        $result->img_id = $img_id;
        $result->set_success();
        return $result;
    }

    /**
     * 删除图片
     *
     * @param \Result $result
     * @return \Result
     */
    private function doDeleteImg(&$result) {
        $img_id = $result->img_id;

        load_model('img');
        $delete_result = $this->CI->img->update_by_id($img_id, ['img_status' => self::IMG_STATUS_DELETE], TRUE, TRUE);
        if (!$delete_result) {
            $result->set_error('网络繁忙');
            return $result;
        }

        $result->set_success('删除成功');
        return $result;
    }

    /**
     * 删除图片后操作
     *
     * @param \Result $result
     * @return \Result
     */
    private function afterDeleteImg(&$result) {
        // 暂时不做啥
        return $result;
    }
}