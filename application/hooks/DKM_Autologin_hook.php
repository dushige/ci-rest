<?php

class DKM_Autologin_hook {
    public function login() {
        $no_login = [
            ['api', 'login'],
            ['api', 'regist'],
            ['api', 'test'],
            ['task']
        ];

        $CI = &get_instance();
        $req_segment_arr = [];
        for ($i = 1; $i < 20; $i++) {
            if (empty($CI->uri->segment($i))) {
                break;
            }
            $req_segment_arr[] = $CI->uri->segment($i);
            if (in_array($req_segment_arr, $no_login)) {
                return;
            }
        }

        if (is_login()) {
            return;
        }

        redirect('/api/login');
    }
}
