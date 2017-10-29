cd BattleShipServer

serverrunning=$(ps aux | grep "game\.py" | wc -l)

if [ "$serverrunning" != "0" ]
  then
    echo "Server already running"
  else
    ./game.py > /dev/null 2>/dev/null &
fi

handshakerunning=$(ps aux | grep "handshakeserver\.py" | wc -l)

if [ "$handshakerunning" != "0" ]
  then
    echo "Handshake server already running"
  else
    ./handshakeserver.py > /dev/null 2>/dev/null &
fi


