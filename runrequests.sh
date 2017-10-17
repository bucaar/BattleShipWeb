#!/bin/bash

num=2
if [ -n "$1" ]
  then
    num="$1"
fi

path="jars"


cat "gamerequests.txt" | head -"$num" | while read line; do
  tokens=(${line// / })
  java -jar "$path/${tokens[1]}" $>/dev/null &
  sleep .5
  java -jar "$path/${tokens[2]}" $>/dev/null &
  sleep .5
done

cat "gamerequests.txt" | tail -n +"$((num+1))" > gamerequests.tmp
cat gamerequests.tmp > gamerequests.txt
rm gamerequests.tmp
