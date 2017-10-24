#!/usr/bin/python3

import os
import re

PATH = "/var/www/html"
JARS_DIR = PATH + "/jars"
RESULTS_PATH = PATH + "/BattleShipServer/logs/results.log"
REQUEST_PATH = PATH + "/gamerequests.txt"
AUTO_USERNAME = "Match_Maker_2000"

def main():
  jar_matcher = re.compile("(\w+)\.jar")
  team_matcher = re.compile("(\w+)\s+>\s+(\w+)")

  jars_list = [jar_matcher.match(x).group(1) for x in os.listdir(JARS_DIR) if jar_matcher.match(x)]
  with open(RESULTS_PATH, "r") as f:
    results_raw = [x.strip() for x in f.readlines()]

  results = {jar: {other: 0 for other in jars_list if other != jar} for jar in jars_list}
  for result in results_raw:
    team_match = team_matcher.match(result)
    if team_match:
      teams = team_match.group(1, 2)
      if teams[0] not in results:
        print("{} is not in dictionary but is in results...".format(teams[0]))
        continue
      if teams[1] not in results:
        print("{} is not in dictionary but is in results...".format(teams[1]))
        continue
      if teams[0] == teams[1]:
        print("{} has a result for themselves...".format(teams[0]))
        continue
      results[teams[0]][teams[1]] += 1

  sortable_results = {k: [(k2, v2) for k2, v2 in v.items()] for k, v in results.items()}

  for k in sortable_results:
    sortable_results[k].sort(key=lambda x: x[1])
    with open(REQUEST_PATH, "a") as f:
      f.write("{} {}.jar {}.jar\n".format(AUTO_USERNAME, k, sortable_results[k][0][0]))
  print(sortable_results)
  
if __name__ == "__main__":
  main()
