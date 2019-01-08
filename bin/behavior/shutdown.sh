#!/bin/bash
BASEDIR=$(dirname "$0")
cd "$BASEDIR/../../controller" && /bin/su -s /bin/sh www-data -c  '''PATH=$PATH:/opt/php7/bin /bin/xpm app run Run.php BehaviorShutdown'''