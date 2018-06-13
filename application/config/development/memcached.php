<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
| -------------------------------------------------------------------------
| Memcached settings
| -------------------------------------------------------------------------
| Your Memcached servers can be specified below.
|
|	See: https://codeigniter.com/user_guide/libraries/caching.html#memcached
|
*/
$default = [
    'host' => '127.0.0.1',
    'port' => '11211',
    'weight' => '1',
];

$dkm = [
    'host' => '127.0.0.1',
    'port' => '11211',
    'weight' => '1',
];

$config['default'] = $default;
$config['dkm'] = $dkm;
