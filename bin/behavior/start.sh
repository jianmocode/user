#!/bin/bash
BASEDIR=$(dirname "$0")
daemonize=$1
if [ -z $daemonize ]; then
    daemonize=0
fi
cd "$BASEDIR/../../controller" && /bin/su -s /bin/sh www-data -c  '''PATH=$PATH:/opt/php7/bin /bin/xpm app run Run.php BehaviorStart -q "{\"daemonize\":1}"'''