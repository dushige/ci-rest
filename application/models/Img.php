<?php

namespace dkm\models;
use dkm\libraries\service\ImgService;

/**
 * Class Img_model
 */
class Img_model extends \DKM_Model {

    protected $table_name = 'img';

    /**
     * add img
     * @param $uid
     * @param $url
     * @param $size
     * @param $md5
     * @param int $img_status
     * @return mixed
     */
    public function add($uid, $url, $size, $md5, $img_status = ImgService::IMG_STATUS_NORMAL) {
        $now = time();
        $data_array = [
            'uid' => $uid,
            'url' => $url,
            'size' => $size,
            'md5' => $md5,
            'img_status' => $img_status,
            'gmt_create' => $now,
            'gmt_update' => $now
        ];

        $this->insert($data_array);
        $affected_rows = $this->affected_rows();
        if (!$affected_rows) {
            return FALSE;
        }

        return $this->insert_id();
    }
}