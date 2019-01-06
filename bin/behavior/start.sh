#!/bin/bash
BASEDIR=$(dirname "$0")
daemonize=$1
if [ -z $daemonize ]; then
    daemonize=0
fi
cd "$BASEDIR/../../controller" && /bin/xpm app run Run.php BehaviorServer -q "{\"daemonize\":$daemonize}"