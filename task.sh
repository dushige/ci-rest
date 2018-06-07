#!/bin/sh
PHP=/usr/bin/php
PHPINI=/etc/php/7.2/cli/php.ini

VAR_PATH=/data/taskdaemon/var
TASK_DAEMON="$(dirname $0)/task_daemon.php"
TASK_CONFIG="$(dirname $0)/task_config.ini"

function get_task_pid() {
    local pidfile=$VAR_PATH/$1.pid
    [ -f $pidfile ] && cat $pidfile || echo -1
}

function start_task() {
    $PHP -c $PHPINI $TASK_DAEMON $*
}

function stop_task() {
    TASKNAME=$1
    local pid=`get_task_pid $TASKNAME`
    [ $pid -gt 0 ] && /bin/kill $pid
}

function list_running_task() {
    local task_list=`find $VAR_PATH -type f -name "*.pid" | sort | awk -F'/' '{print $NF}' | sed -e "s/\.pid$//" `
    for name in $task_list;
    do
        pid=`get_task_pid $name`
        echo -e "$pid\t$name"
    done
}

function list_ini_task() {
    cat $TASK_CONFIG | grep '\[' | grep -v default | sort
}

function usage() {
    echo "$0 start [options] <taskname>"
    echo "$0 stop <taskname>"
    echo "$0 restart <taskname>"
    echo "$0 list"
    echo "$0 listall"
    exit
}


[ $# -lt 1 ] && usage

CMD=$1
shift
case $CMD in
    start)
        start_task $*
        ;;
    stop)
        stop_task $*
        ;;
    restart)
        stop_task $*
        start_task $*
        ;;
    list)
        list_running_task
        ;;
    listall)
        list_ini_task
        ;;
    *)
        usage
        ;;
esac

