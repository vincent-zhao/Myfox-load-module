# ! /bin/bash
#
# vim: set expandtab tabstop=4 shiftwidth=4 foldmethod=marker: #
# ----------------------------------------------------------------
#
# Myfox部署在 HDFS 网关机上的客户端
#
# @author: Aleafs <pengchun@taobao.com>
# ----------------------------------------------------------------

export LANG=en_US.UTF-8

declare -r CFG_MYFOX_RPC_SERVER=(\
    ##myfox.rpc.server.list##
)
declare -r CFG_HTTP_USER_AGENT="##myfox.rpc.agent##"

# @远程服务器获取本机文件的协议前缀
declare -r CFG_FILENAME_PREFIX="##net.filename.prefix##"

# {{{ function usage() #
function usage() {
cat <<EOF
Usage: /bin/bash `basename ${0}` [options...] data_file <is_ready>

[OPTION]:
  -t        logic table name in myfox, such as "numsplit"
  -r        route value, such as "thedate=2011-06-10,cid=1"
EOF
}
# }}} #

# {{{ 运行时变量 #

declare -r __RUN_ROOT__="$(dirname -- $(readlink -f -- ${BASH_SOURCE[0]}))"
declare -r RUN_HTTP_OUT="${__RUN_ROOT__}/http.out"
declare -r RUN_AGENT_IP=`hostname -i | awk -F. '{print lshift($1,24)+lshift($2,16)+lshift($3,8)+$4}'`

if [ -z "${CFG_FILENAME_PREFIX}" ] ; then
    declare -r RUN_FILENAME_PREFIX=""
else
    declare -r RUN_FILENAME_PREFIX=${CFG_FILENAME_PREFIX//\{HOST\}/`hostname -i`}
fi

declare OPT_TABLE_NAME=""
declare OPT_ROUTE_TEXT=""
while getopts 't:r:' opt ; do
    case ${opt} in
        t)
            OPT_TABLE_NAME="${OPTARG}";;
        r)
            OPT_ROUTE_TEXT="${OPTARG//=/:}";;
        *);;
    esac
done
shift $(($OPTIND - 1))
declare OPT_FILE_NAME=$(readlink -f -- "${1}")
declare OPT_IS_READY="${2}"

if [ -z "${OPT_TABLE_NAME}" -o -z "${OPT_FILE_NAME}" ] ; then
    usage
    exit 100
fi

if [ ! -f ${OPT_FILE_NAME} ] ; then
    echo "No such file named as \"${OPT_FILE_NAME}\"."
    exit 200
fi

if [ -z "${OPT_IS_READY}" ] ; then
    OPT_IS_READY=0
fi

# }}} #

# {{{ function log()
function log() {
    printf "%s:\t[%s %s]\t{%d}\t%s\t%s\n" \
    `echo "${1}" | tr a-z A-Z` `date +"%Y-%m-%d %H:%M:%S"` \
    ${$} `echo ${2} | tr a-z A-Z` "${3}"
}
# }}}

# {{{ function urlencode() #
function urlencode() {
    echo "${1}" | xxd -plain | tr -d "\n" | sed 's/\(..\)/%\1/g'
}
# }}} #

# {{{ function http() #
function http() {
    local url="${1}"
    local ext="${2}"

    for prefix in ${CFG_MYFOX_RPC_SERVER[@]} ; do 
        curl -s -m30 ${ext} -A"${CFG_HTTP_USER_AGENT}" -o"${RUN_HTTP_OUT}" "http://${prefix}${url}" &> /dev/null
        if [ ${?} -eq 0 -a `grep -c "\[0\] OK" ${RUN_HTTP_OUT}` -gt 0 ] ; then
            return 0
        fi
    done

    return 1
}
# }}} #

# {{{ Call API Hello() #

declare -i RUN_FILE_LINES=`wc -l ${OPT_FILE_NAME} | cut -d" " -f1`

log "NOTICE" "START" "table=${OPT_TABLE_NAME}&route=${OPT_ROUTE_TEXT}&file=${OPT_FILE_NAME}&line=${RUN_FILE_LINES}"

declare -r OPT_TABLE_NAME=`urlencode "${OPT_TABLE_NAME}"`
declare -r OPT_ROUTE_TEXT=`urlencode "${OPT_ROUTE_TEXT}"`
declare -r OPT_FILE_NAME=`urlencode "${RUN_FILENAME_PREFIX}/${OPT_FILE_NAME}"`
declare -r RUN_POST_DATA="table=${OPT_TABLE_NAME}&route=${OPT_ROUTE_TEXT}&file=${OPT_FILE_NAME}&lines=${RUN_FILE_LINES}"

http "/import/hello" "-d${RUN_POST_DATA}"
if [ ${?} -ne 0 ] ; then
    log "FATAL" "RPC_HELLO_FAIL" "`head -n1 ${RUN_HTTP_OUT}`"
    exit 210
fi

log "NOTICE" "RPC_HELLO"

if [ "${OPT_IS_READY}" -gt 0 ] ; then
    http "/import/ready"
    log "NOTICE" "RPC_READY"
fi

if [ -f "${RUN_HTTP_OUT}" ] ; then
    /bin/rm -f "${RUN_HTTP_OUT}"
fi

exit 0

# }}} #

