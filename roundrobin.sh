#!/bin/bash

num=5
if [ -n "$1" ]
  then
    num="$1"
fi

path="jars/"

ls "$path" | grep "^.*\.jar$" | while read f1; do
  ls -I "$f1" "$path" | grep "^.*\.jar$" | sort -R | tail -"$num" | while read f2; do
    java -jar "$path$f1" $>/dev/null &
    sleep .5
    java -jar "$path$f2" $>/dev/null &
    sleep .5
  done
done
