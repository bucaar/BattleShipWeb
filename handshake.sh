#!/bin/bash

port="4948"
timeout="3"

if [[ -z $1 ]]; then
  exit 0
fi

if [[ $2 ]]; then
  port="$2"
fi

path="jars"

result=$(timeout "$timeout" java -jar "$path/$1.jar" "$port" 2>/dev/null)

if [[ "$1" == "$result" ]]; then
  echo "PASS"
else
  echo "FAIL"
fi
