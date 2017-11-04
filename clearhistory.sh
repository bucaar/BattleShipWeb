#!/bin/bash

path="/var/www/html"

if [ -z $1 ]
  then
    exit 0
fi

awk -v user="$1" '$1 != user && $3 != user' "$path"/BattleShipServer/logs/results.log > "$path"/results.tmp
awk -v user="$1" '$1 != user && $3 != user' "$path"/BattleShipServer/logs/games.log > "$path"/games.tmp

cat "$path"/results.tmp > "$path"/BattleShipServer/logs/results.log
cat "$path"/games.tmp > "$path"/BattleShipServer/logs/games.log

rm results.tmp
rm games.tmp
