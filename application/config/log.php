<?php

$config['log_path'] = '';
$config['log_handlers'] = [\dkm\libraries\util\LogFactory::HANDLER_STREAM];
$config['log_format'] = \dkm\libraries\util\LogFactory::FORMAT_LINE;
$config['log_level'] = \dkm\libraries\util\LogFactory::LEVEL_INFO;
$config['log_extension'] = '.log';
