#!/bin/bash
BASEDIR=$(dirname "$0")
only=$1
if [ -z $only ]; then
    only=0
fi
cd "$BASEDIR/../../controller" && /bin/su -s /bin/sh www-data -c  '''PATH=$PATH:/opt/php7/bin /bin/xpm app run Run.php BehaviorReload -q "{\"worker_only\":0}"'''