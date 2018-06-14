<?php

$redis_dkm_hosts_arr = [
    '127.0.0.1:6379',
];

$dkm['hosts'] = [];
$dkm['hosts'] = $redis_dkm_hosts_arr;
$dkm['timeout'] = 1;
$dkm['read_timeout'] = 1;

$config['dkm'] = $dkm;
