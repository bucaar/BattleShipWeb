#!/bin/bash

path="/var/www/html"

if [ -z $1 ]
  then
    echo "Clear all"
    echo "" > "$path"/BattleShipServer/logs/results.log
    echo "" > "$path"/BattleShipServer/logs/games.log
    echo "" > "$path"/BattleShipServer/logs/connections.log
    exit 0
  else
    echo "Clear for $1"

    awk -v user="$1" '$1 != user && $3 != user' "$path"/BattleShipServer/logs/results.log > "$path"/results.tmp
    awk -v user="$1" '$1 != user && $3 != user' "$path"/BattleShipServer/logs/games.log > "$path"/games.tmp

    cat "$path"/results.tmp > "$path"/BattleShipServer/logs/results.log
    cat "$path"/games.tmp > "$path"/BattleShipServer/logs/games.log

    rm results.tmp
    rm games.tmp
fi

