#!/bin/bash

path="/var/www/html"

if [ -z $1 ]
  then
    exit 0
fi

awk -v user="$1" '$1 != user && $3 != user' "$path"/BattleShipServer/logs/results.log > "$path"/results.tmp

cat "$path"/results.tmp > "$path"/BattleShipServer/logs/results.log
rm results.tmp
