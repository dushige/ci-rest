<?php

class DKM_Autologin_hook {
    public function login() {
        $no_login = [
            ['api', 'login'],
            ['api', 'regist']
        ];

        $CI = &get_instance();
        $req_segment_arr = [$CI->uri->segment(1), $CI->uri->segment(2)];
        if (in_array($req_segment_arr, $no_login)) {
            return;
        }

        if (is_login()) {
            return;
        }

        redirect('/api/login');
    }
}
