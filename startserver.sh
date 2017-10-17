cd BattleShipServer/logs

rm Java* 2>/dev/null
echo "" > results.log
echo "" > connections.log

cd ..

./game.py > /dev/null 2>/dev/null &
