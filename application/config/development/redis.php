<?php

$redis_dkm_hosts_arr = [
    '127.0.0.1:6379',
];

$dkm['hosts'] = [];
$dkm['hosts'] = $redis_dkm_hosts_arr;
$dkm['password'] = '3dfe1d0dd78234d1d00d9baf511473a2';
$dkm['timeout'] = 1;
$dkm['read_timeout'] = 1;

$config['dkm'] = $dkm;
