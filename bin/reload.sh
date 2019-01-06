#!/bin/bash
BASEDIR=$(dirname "$0")
only=$1
if [ -z $only ]; then
    only=0
fi
cd "$BASEDIR/../controller" && /bin/xpm app run Run.php BehaviorReload -q "{\"worker_only\":$only}"