<?php

$config['log_path'] = '';
$config['log_handlers'] = [\dkm\libraries\LogFactory::HANDLER_STREAM];
$config['log_format'] = \dkm\libraries\LogFactory::FORMAT_LINE;
$config['log_level'] = \dkm\libraries\LogFactory::LEVEL_INFO;
$config['log_extension'] = '.log';
