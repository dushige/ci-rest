<?php

class DKM_Autologin_hook {
    public function login() {
        $no_login = [
            [],
            ['api', 'login'],
            ['api', 'regist'],
            ['api', 'test'],
            ['task']
        ];

        $CI = &get_instance();
        for ($i = 0; $i <= count($CI->uri->segments); $i++) {
            $req_segment_arr = array_slice($CI->uri->segments, 0, $i);
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
