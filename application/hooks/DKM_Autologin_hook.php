<?php

class DKM_Autologin_hook {
    public function login() {
        $no_login = [
            [],
            ['api', 'login'],
            ['api', 'regist'],
            ['api', 'test'],
            ['api', 'user'],
            ['api', 'img'],
            ['api', 'log'],
            ['task'],
        ];

        $CI = &get_instance();
        if (empty($CI->uri->segments) && in_array([], $no_login)) {
            return;
        }
        for ($i = 1; $i <= count($CI->uri->segments); $i++) {
            $req_segment_arr = array_slice($CI->uri->segments, 0, $i);
            $req_segment_arr = array_map('strtolower', $req_segment_arr);
            if (in_array($req_segment_arr, $no_login)) {
                return;
            }
        }

        if (is_login()) {
            return;
        }

        redirect('api/login');
    }
}
