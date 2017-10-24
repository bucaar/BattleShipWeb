#!/bin/bash

num=5
if [ -n "$1" ]
  then
    num="$1"
fi

path="/var/www/html"
port="4949"

cat "$path/gamerequests.txt" | head -"$num" | while read line; do
  tokens=(${line// / })
  java -jar "$path/jars/${tokens[1]}" "$port" $>/dev/null &
  sleep .5
  java -jar "$path/jars/${tokens[2]}" "$port" $>/dev/null &
  sleep .5
done

cat "$path/gamerequests.txt" | tail -n +"$((num+1))" > "$path/gamerequests.tmp"
cat "$path/gamerequests.tmp" > "$path/gamerequests.txt"
rm "$path/gamerequests.tmp"
