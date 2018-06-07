<?php

if (isset($_SERVER['REMOTE_ADDR'])) {
    exit('Permission denied!');
}

/* raise or eliminate limits we would otherwise put on http requests  */
set_time_limit(0);
ini_set('memory_limit', '128M');

define('CLI_RUNTIME', TRUE);
define('_PCONNECT_', TRUE);

// 加载pcntl扩展
if (!extension_loaded('pcntl')) {
    dl('pcntl.so');
}
if (!function_exists('pcntl_fork')) {
    exit('PCNTL functions not available on this PHP installation');
}

function usage($argc, $argv) {
    echo "Usage: \nphp -c php.ini " . $argv[0] . " <taskname>\n";
    exit(2);
}

// 检查命令行参数
if ($argc < 2) {
    usage($argc, $argv);
    exit(2);
}

// enable信号处理（父子进程都有效）
declare(ticks = 1);

$g_options = NULL;
$g_task_config = NULL;
$g_child_list = [];
$g_master_pid = -1;
$g_logger = NULL;
$g_is_master = TRUE;
$g_work_exit_code = 0;

function log_master_error($msg) {
    $logfile = '/data/taskdaemon/master_error.log';
    
    $ts = time();
    $message = date('[Y-m-d H:i:s][', $ts) . posix_getpid() . '] ' . $msg . "\n";
    
    $fp_log = fopen($logfile, 'a');
    if (!$fp_log) {
        error_log("failed to open logfile: $logfile");
        exit(2);
    }
    fwrite($fp_log, $message);
    fclose($fp_log);
}

function daemon_log_to_file($level, $msg, $logfile = NULL) {
    global $g_task_config;
    global $g_is_master;
    if (is_null($g_task_config)) {
        log_master_error('daemon_log_to_file: $g_task_config is null' . "\n");
    } else {
        if (!empty($g_task_config['log_scribe']) && !$g_is_master) {
            daemon_log_to_scribe($level, $msg);
        }
    }
    $ts = time();
    $message = date('[Y-m-d H:i:s][', $ts) . posix_getpid() . '] ' . $level . ' '. $msg . "\n";
    
    if ($logfile == NULL) {
        $logfile = $g_task_config['logfile'] . '-' . date('Y-m-d', $ts);
        $fp_log = fopen($logfile, 'a');
        if (!$fp_log) {
            log_master_error("failed to open logfile: $logfile");
        }
        fwrite($fp_log, $message);
        fclose($fp_log);
    } elseif ($logfile == 'stderr') {
        fwrite(STDERR, $message);
        fflush(STDERR);
    }

}

function daemon_log_to_scribe($level, $msg) {
    global $g_logger;
    global $g_task_config;
    if (!$g_logger) {
        if (!class_exists('\\Logfactory')) {
            if (!file_exists(dirname(__FILE__) . '/application/libraries/Logfactory.php')) {
                return;
            }
            require dirname(__FILE__) . '/application/libraries/Logfactory.php';
        }
        $logfactory = new \Logfactory();
        $log_level = \Logfactory::INFO;
        if (!empty($g_task_config['enable_log_debug'])) {
            $log_level = \Logfactory::DEBUG;
        }
        if (!empty($g_task_config['enable_log_dev'])) {
            $log_level = \Logfactory::TRACE;
        }
        $g_logger = $logfactory->getLogger('task', ['cacheLimit' => 0, 'level' => $log_level]);
    }
    $action = 'unknow_task';
    if (isset($g_task_config['taskname'])) {
        $action = $g_task_config['taskname'];
    }
    if ($level === 'ERROR') {
        $g_logger->error($action, $msg);
    }
    if ($level === 'WARN') {
        $g_logger->warn($action, $msg);
    }
    if ($level === 'INFO') {
        $g_logger->info($action, $msg);
    }
    if ($level === 'DEBUG') {
        $g_logger->debug($action, $msg);
    }
    if ($level === 'DEV') {
        $g_logger->trace($action, $msg);
    }
}

function daemon_log_error($msg) {
    return daemon_log_to_file('ERROR', $msg);
}

function daemon_log_warn($msg) {
    return daemon_log_to_file('WARN', $msg);
}

function daemon_log_info($msg) {
    return daemon_log_to_file('INFO', $msg);
}

function daemon_log_debug($msg) {
    global $g_task_config;
    if (is_null($g_task_config)) {
        log_master_error('set $g_task_config before use log');
        exit(2);
    }
    if ($g_task_config['enable_log_debug']) {
        return daemon_log_to_file('DEBUG', $msg);
    }
}

function daemon_log_dev($msg) {
    global $g_task_config;
    if (is_null($g_task_config)) {
        log_master_error('set $g_task_config before use log');
        exit(2);
    }
    if ($g_task_config['enable_log_dev']) {
        return daemon_log_to_file('DEV', $msg, 'stderr');
    }
}

function daemon_log_stdout($msg) {
    $message = date('[Y-m-d H:i:s][', time()) . posix_getpid() . '] ' . $msg . "\n";
    fwrite(STDOUT, $message);
    fflush(STDOUT);
}

function daemon_log_stderr($msg) {
    $message = date('[Y-m-d H:i:s][', time()) . posix_getpid() . '] ERROR ' . $msg . "\n";
    fwrite(STDERR, $message);
    fflush(STDERR);
}

// 信号处理函数
function signal_handler($signo) {
    global $g_sig_handle_functions;
    //daemon_log_dev("catch signo $signo");

    if (isset($g_sig_handle_functions[$signo])) {
        $callback = $g_sig_handle_functions[$signo];
        call_user_func($g_sig_handle_functions[$signo]);
    }
    return;
}

// 注册信号处理函数
function register_signal_function($signo, $callback, $overwrite = TRUE) {
    global $g_sig_handle_functions;
    
    if (isset($g_sig_handle_functions[$signo]) && $overwrite == FALSE) {
        return ;
    }
    $g_sig_handle_functions[$signo] = $callback;
    pcntl_signal($signo, "signal_handler");
}

function safe_exit($exit_code = 0) {
    global $g_work_exit_code;
    remove_pid_file();
    exit($g_work_exit_code);
}

function SIGTERM_handler() {
    send_signal_to_children(SIGTERM);
    daemon_log_info('master: exit, SIGTERM');
    safe_exit(0);
}

function SIGINT_handler() {
    send_signal_to_children(SIGTERM);
    safe_exit(0);
}

function SIGUSR1_handler() {
    safe_exit(0);
}

function SIGHUP_handler() {
    load_task_config(TRUE);
    send_signal_to_children(SIGHUP);
}

function SIGCHLD_waitpid() {
    global $g_child_list;
    global $g_work_exit_code;
    $wait_pid = pcntl_wait($status, WUNTRACED); //取得子进程结束状态
    daemon_log_debug("child $wait_pid exit");
    if ($wait_pid > 0) {
        $k = array_search($wait_pid, $g_child_list);
        unset($g_child_list[$k]);
    }
    if (pcntl_wifexited($status)) {
        $exit_code = pcntl_wexitstatus($status);
        daemon_log_debug("child $wait_pid exit with code $exit_code");

        if ($exit_code != 0) {
            $g_work_exit_code = $exit_code;
        }
    }
}

// 检查并回收defunct状态的子进程
function check_defunct_child() {
    global $g_child_list;
    $i = count($g_child_list);
    while ($i > 0) {
        $i--;
        $wait_pid = pcntl_wait($status, WUNTRACED | WNOHANG); //取得子进程结束状态
        if ($wait_pid > 0) {
            $k = array_search($wait_pid, $g_child_list);
            unset($g_child_list[$k]);
            daemon_log_warn("defunct child $wait_pid done");
        }
        if ($wait_pid < 0) {
            break;
        }
    }
}

function real_sleep($n) {
    while ($n > 0) {
        $n = sleep($n);
        daemon_log_dev("sleep return $n");
    }
    return $n;
}

// kill所有子进程并等待他们退出
// 因为mq任务可能需要消费最后一个消息，再加上咱们时间控制大约都预留40s距离下次启动, 故此处最多30秒
function kill_children_wait_exit($max_retry = 10, $wait_seconds = 3) {
    global $g_child_list;
    $kill_times = 0;
    daemon_log_warn("max_retry: $max_retry, kill_times: $kill_times");
    while (count($g_child_list) > 0 && $kill_times < $max_retry) {
        $kill_times++;
        
        if ($kill_times == $max_retry) {
            // SIGKILL 不能捕获
            daemon_log_error("master: kill_children: send SIGKILL to " . implode(' ', $g_child_list));
            foreach ($g_child_list as $pid) {
                if ($pid > 0) {
                    posix_kill($pid, SIGKILL);
                }
            }
        } else {
            daemon_log_warn("master: kill_children: send SIGTERM to " . implode(' ', $g_child_list));
            foreach ($g_child_list as $pid) {
                if ($pid > 0) {
                    posix_kill($pid, SIGTERM);
                }
            }
        }
        
        $sleep = $wait_seconds;
        while ($sleep > 0) {
            $sleep = sleep($sleep);
            daemon_log_dev("sleep return $sleep");
            if (count($g_child_list) == 0) {
                break;
            }
        }
    }
    
    $left = count($g_child_list);
    if ($left > 0) {
        daemon_log_warn("master: kill_children: remain $left children not be killed.");
    }
    
    return $left;
}

// 发送信号给所有子进程
function send_signal_to_children($signo) {
    global $g_child_list;
    if (count($g_child_list) > 0) {
        daemon_log_debug("master: send $signo to " . implode(' ', $g_child_list));
        foreach ($g_child_list as $pid) {
            if ($pid > 0) {
                posix_kill($pid, $signo);
            }
        }
    }
}

// 发送信号给master进程
function send_signal_to_master($signo) {
    global $g_master_pid;
    daemon_log_debug("send $signo to master($g_master_pid)");
    if ($g_master_pid > 0) {
        posix_kill($g_master_pid, $signo);
    }
}

// 解析命令行参数
function parse_options() {
    global $g_options;
    global $argc, $argv;

    if (!is_null($g_options)) {
        return $g_options;
    }
    
    // 选项默认值
    $default_options['task_config'] = realpath(dirname(__FILE__) . "/task_config.ini");
    
    // 解析参数
    $shotopts = "";
    $longopts = array(
            'task_config:',
            'worker_num:',
            'master_loop_max:',
            'master_loop_sleep:',
    );
    $opt_array = getopt($shotopts, $longopts);

    // 删除argv中已经解析的参数
    foreach ($opt_array as $k => $v) {
        $n = array_search('-' . $k, $argv);
        if ($n) {
            unset($argv[$n]);
            unset($argv[$n + 1]);
        }
        foreach ($argv as $n => $vv) {
            if (strpos($vv, '--'.$k.'=') === 0) {
                unset($argv[$n]);
            }
        }
    }
    $argv = array_values($argv);
    $argc = count($argv);

    // 合并默认值
    $g_options = array_merge($default_options, $opt_array);
    $g_options['argv'] = $argv;
    $g_options['argc'] = $argc;

    return $g_options;
}

// 载入配置文件
function load_task_config($reload = FALSE) {
    global $g_task_config;
    global $g_options;
    global $argc, $argv;
    
    if (!is_null($g_task_config) && $reload == FALSE) {
        return $g_task_config;
    }
    
    $taskname = $argv[1];
    $config_filename = $g_options['task_config'];

    $task_config = NULL;
    clearstatcache();
    $errorinfo = '';
    if (is_readable($config_filename)) {
        $ini_config = parse_ini_file($config_filename, TRUE);
        if ($ini_config && isset($ini_config[$taskname])) {
            $task_config = $ini_config[$taskname];
        } else {
            $errorinfo = 'config file ' . $config_filename . " parse failed";
        }
    } else {
        $errorinfo = 'config file ' . $config_filename . " is not readable";
    }

    if (!is_array($task_config)) {
        if ($reload) {
            daemon_log_error("master: failed to reload task config, " . $errorinfo);
            return FALSE;
        } else {
            log_master_error("master: failed to load task config: $taskname, " . $errorinfo);
            exit(2);
        }
    }
    
    // 默认配置
    $default_config = $ini_config['default'];
    
    // 开发环境的默认配置
    if (file_exists(dirname(__FILE__) . '/development.lock')) {
        $default_config = array_merge($default_config, $ini_config['default_dev']);
    }
    
    // 父配置，多级合并
    $parent_list = array();
    $cur = $task_config;
    while (isset($cur['parent'])) {
        $parent_list[] = $cur['parent'];
        $cur = $ini_config[$cur['parent']];
    }
    $parent_config = array();
    while (count($parent_list) > 0) {
        $parent = array_pop($parent_list);
        $parent_config = array_merge($parent_config, $ini_config[$parent]);
    }
    
    // 合并多级配置项
    $task_config = array_merge($default_config, $parent_config, $task_config);
    
    // 检查目录是否存在
    if (!is_dir($task_config['pidfile_dir']) || !is_dir($task_config['log_dir'])) {
        log_master_error("dir not exist: " . $task_config['pidfile_dir'] . ' or ' . $task_config['log_dir']);
        exit(2);
    }

    // 降级级别 0 - 3
    if (!is_numeric($task_config['work_level']) || $task_config['work_level'] < 0 || $task_config['work_level'] > 3) {
        daemon_log_stderr("invalid work_level: " . $task_config['work_level']);
        exit(2);
    }

    // 增加推导出的配置
    $task_config['taskname'] = $taskname;
    $task_config['pidfile'] = $task_config['pidfile_dir'] . '/' . $taskname . '.pid';
    $task_config['logfile'] = $task_config['log_dir'] . '/'  . $taskname . '.log';
    
    // 合并命令行参数
    $task_config = array_merge($task_config, $g_options);
    
    if ($task_config['daemonize'] == 0) {
        $task_config['keep_worker_num'] = 0;
        $task_config['master_loop_max'] = 1;
        $task_config['master_loop_sleep'] = 0;
    }

    // 日志在$g_task_config有效后才能使用
    $g_task_config = $task_config;
    daemon_log_debug("master: success (re)load config $config_filename");
    
    return $g_task_config;
}

function is_daemon() {
    global $g_task_config;
    return $g_task_config['daemonize'] == 0 ? FALSE : TRUE;
}

// 写入进程号到pid文件
function lock_write_pid_file() {
    global $g_task_config;
    
    $pidfile = $g_task_config['pidfile'];
    $fp_pid = fopen($pidfile, 'c+');
    if ($fp_pid === FALSE) {
        log_master_error("failed to open pidfile");
        exit(2);
    }
    if (is_readable($pidfile)) {
        $line = fgets($fp_pid);
        if ($line != FALSE) {
            $pid = (int) $line;
            if ($pid > 0) {
                if (posix_kill($pid, 0)) {
                    daemon_log_debug("$pid is running");
                    // 如果是重复执行，因为这里逻辑已经排重，所以判定为正常退出
                    exit(0);
                }
                // pid is not run, continue.
            } else {
                daemon_log_error("failed to read pidfile");
                exit(2);
            }
        }
    }
    
    if (!flock($fp_pid, LOCK_EX | LOCK_NB)) {
        daemon_log_error("master: lock $pidfile failed");
        exit(2);
    }
    
    ftruncate($fp_pid, 0);
    rewind($fp_pid);
    $pid = posix_getpid();
    fprintf($fp_pid, '%d', $pid);
    flock($fp_pid, LOCK_UN);
    daemon_log_debug("master: write $pid to $pidfile");
    return TRUE;
}

// 删除pid文件
function remove_pid_file() {
    global $g_task_config;
    if (is_file($g_task_config['pidfile'])) {
        unlink($g_task_config['pidfile']);
    }
}

// 使用非root用户运行
function run_as_user($username) {
    $pw = posix_getpwnam($username);
    $pw_uid = $pw['uid'];
    if (posix_setuid($pw_uid) == FALSE) {
        log_master_error("run as $username, setuid($pw_uid) failed.");
        exit(3);
    }
}

// 程序推入后台守护进程
function daemonize() {
    $pid = pcntl_fork();
    if ($pid == -1) {
        log_master_error("fork failed");
        exit(5);
    } 
    if ($pid > 0) {
        exit(0); // 父进程退出
    }
    // 子进程
    pcntl_signal(SIGCHLD, SIG_IGN);
    
    // create session
    if (posix_setsid() == -1) {
        log_master_error('posix_setsid() failed');
        exit(5);
    }
    
    // fork again
    $pid = pcntl_fork();
    if ($pid == -1) {
        log_master_error("fork failed");
        exit(5);
    }
    if ($pid > 0) {
        exit(0); // 父进程退出
    }

    umask(0);
    chdir('/');
}

function get_sys_level() {
    $file = '/data/tmp/down_level.lock';
    if (!file_exists($file)) {
        return 0;
    }
    $down_level = trim(file_get_contents($file));
    if (!$down_level || !is_numeric($down_level)) {
        return 0;
    }

    $down_level = intval($down_level);
    $level = $down_level >= 0 ? $down_level : 0;
    return $level;
}

////////////////////////// starting.... //////////////////////////

// 切换执行用户
run_as_user('www');

// 解析命令行参数
parse_options();

// 加载配置文件
load_task_config();

daemon_log_debug("master: begin");

// 设置信号处理函数
register_signal_function(SIGTERM, "SIGTERM_handler");
register_signal_function(SIGINT, "SIGINT_handler");
register_signal_function(SIGHUP, "SIGHUP_handler");
register_signal_function(SIGUSR1, "SIGUSR1_handler");

// 进入后台守护进程
if ($g_task_config['daemonize'] == 1) {
    daemonize();
}

// 写pid文件
lock_write_pid_file();
$g_master_pid = posix_getpid();

// 开始主循环
daemon_log_info("master: loop starting 0/" . $g_task_config['master_loop_max']);
$master_loop_count = 0;
for ($master_loop_count = 0; $master_loop_count < $g_task_config['master_loop_max']; $master_loop_count++) {
    $worker_num = $g_task_config['worker_num'];

    // 降级
    $sys_level = get_sys_level();
    $work_level = $g_task_config['work_level'];
    if ($sys_level > $work_level) {
        $worker_num = 0;
        daemon_log_warn("master: sys level $sys_level, set worker_num 0");
    }

    $dev_dead_pids = array();
    $dev_run_pids = array();
    $dev_fork_pids = array();
    $dev_kill_pids = array();

    // 检查子进程是否存在
    $worker_run = count($g_child_list);
    if ($worker_run > 0) {

        check_defunct_child();

        foreach ($g_child_list as $i => $pid) {
            if (posix_kill($pid, 0) == FALSE) {
                unset($g_child_list[$i]);
                $dev_dead_pids[] = $pid;
            } else {
                $dev_run_pids[] = $pid;
            }
        }
        reset($g_child_list);
    }

    // fork指定数量的子进程
    $worker_run = count($g_child_list);
    if ($worker_run < $worker_num) {
        $fork_num = $worker_num - $worker_run;
        if ($g_task_config['keep_worker_num'] == 0 && $master_loop_count > 0) {
            $fork_num = 0;
        }
        for ($i = 0; $i < $fork_num; $i++) {
            $pid = pcntl_fork();
            if ($pid == -1) {
                log_master_error("fork failed");
                exit(5);
            } elseif ($pid == 0) {
                //子进程
                pcntl_signal(SIGINT, SIG_IGN);
                pcntl_signal(SIGTERM, SIG_DFL);
                
                // 加载CI，执行业务逻辑。
                $_SERVER['argv'] = [];
                $_SERVER['argv'][] = 'index.php';
                $_SERVER['argv'][] = $g_task_config['request_uri'];
                $_SERVER['argc'] = 2;
                $g_is_master = FALSE;
                require dirname(__FILE__) . '/index.php';
                exit(0);
            } else {
                //主进程
                // 注册SIGCHLD信号处理函数
                register_signal_function(SIGCHLD, "SIGCHLD_waitpid", FALSE);
                $g_child_list[] = $pid;
                $dev_fork_pids[] = $pid;
            }
        }
    } elseif ($worker_run > $worker_num) {
        $g_child_list = array_values($g_child_list);
        for ($i = 0; $i < ($worker_run - $worker_num); $i++) {
            $pid = $g_child_list[0];
            if ($pid > 0 && posix_kill($pid, SIGTERM)) {
                array_shift($g_child_list);
                $dev_kill_pids[] = $pid;
            }
        }
    }

    daemon_log_debug("master: check_child $worker_num: " . 
                    count($dev_run_pids) . ';' .
                    count($dev_fork_pids) . ';' .
                    count($dev_dead_pids) . ';' .
                    count($dev_kill_pids) . ' (' .
                    implode(' ', $dev_run_pids) . '; ' .
                    implode(' ', $dev_fork_pids) . '; ' .
                    implode(' ', $dev_dead_pids) . '; ' .
                    implode(' ', $dev_kill_pids) . ') ');
    daemon_log_debug('master: loop_count ' . $master_loop_count . '/' . $g_task_config['master_loop_max']);
    
    // sleep
    $sleep_seconds = $g_task_config['master_loop_sleep'];
    daemon_log_dev("sleep $sleep_seconds");
    real_sleep($sleep_seconds);
    daemon_log_dev("next loop");
}
daemon_log_info("master: loop finish, loop_count " . $master_loop_count . '/' . $g_task_config['master_loop_max']);

check_defunct_child();

if (count($g_child_list) > 0) {
    $wait_seconds = $g_task_config['exit_wait_child_timeout'] > 0 ? $g_task_config['exit_wait_child_timeout'] : 5;
    daemon_log_info("master: wait $wait_seconds seconds before kill children.");
    while ($wait_seconds > 0) {
        sleep(1);
        $wait_seconds--;
        check_defunct_child();
        if (count($g_child_list) == 0) {
            break;
        }
    }
    kill_children_wait_exit();
}
daemon_log_info("master: safe_exit");
safe_exit();
