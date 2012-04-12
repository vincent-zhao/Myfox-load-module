# !/bin/bash
# vim: set expandtab tabstop=2 shiftwidth=2 foldmethod=marker: #

export LANG=en_US.UTF-8

. /etc/init.d/functions

declare -r MYFOX_RUN_MODE="##run.mode##"
declare -r CFG_PHP_CLI_PATH="##php.cli.path##"

declare -r __ROOT__="$(dirname -- $(dirname -- $(readlink -f -- ${BASH_SOURCE[0]})))"

# {{{ function usage() #
function usage() {
echo "Usage: ${0} {start|stop|restart}"
exit 1;
}
# }}} #

# {{{ function checkuser() #
function checkuser() {
if [ "##run.user##" != `id -nu` ] ; then
  echo "Only ${MYFOX_RUN_USER} is allowed to run myfox!"
  exit 2
fi
}
checkuser
# }}} #

# {{{ function getpid() #
function getpid() {
ps uxwwww | grep "run.php" | grep -w "${1}" | awk '{print $2}'
}
# }}} #

# {{{ function start() #
function start() {
local pid=`getpid metasync`
if [ -z "${pid}" ] ; then
  echo "Start metasync process ... "
  nohup ${CFG_PHP_CLI_PATH} ${__ROOT__}/bin/run.php metasync &
  if [ ${?} -ne 0 ] ; then
    echo_failure
  else
    echo_success
  fi
else
  echo "metasync (pid: ${pid}) is running"
fi
}
# }}} #

# {{{ function stop() #
function stop() {
local pid=`getpid metasync`
if [ -z "${pid}" ] ; then
  echo "metasync is not running"
else
  echo "Stop metasync process ..."
  kill ${pid}

  for num in 1 1 2 3 ; do
    if [ ${num} -gt 0 ] ; then
      sleep ${num}
    fi

    pid=`getpid metasync`
    if [ -z "${pid}" ] ; then
      echo_success
      break
    fi

    num=`expr num + 1`
    sleep 1
  done

  if [ ! -z "${pid}" ] ; then
    kill -9 ${pid}
    echo_success
  fi
fi
}
# }}} #

case "${1}" in
  start)
    start;;  
  stop)
    stop;;
  restart)
    stop
    start
    ;;
  *)
    usage;;
esac
