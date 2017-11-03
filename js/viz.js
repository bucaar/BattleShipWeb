/*
This is a visualizer script for a battleship game.
Made by Isaac Burton
*/

//======================================================================================================================================================//

//things the whole program should know
var padding = 100;
var gridx = 50;
var gridy = 50;
var bulletRadius = gridx/8;
var namePadding = 50;
var shipPadding = gridx/4;
var ms = 250;
var gameData;
var lines;

var current_line = 0;
var run = 0;

//gameboard and context
var ctx = null, gameBoard = null;

//index values occupied by ships for each player
var playerNames = new Array("", "");
var gridSpaces = new Array();
var shotSpaces = new Array();

//colors!
var shipColor = "#CCCCCC";
var oceanColor = "#00CCFF";
var bulletColor = "#000000";
var hitColor = "#FF0000";
var missColor = "#FFFFFF";
var textColor = "#FFFFFF"

//list of gridspaces for each player
for(var id = 0; id <= 1; id++){
	gridSpaces.push(new Array());
	//setup the mother grid
	for(var row = 0; row < 10; row++){
		gridSpaces[id].push(new Array());
		//make an array for the top left corners of all grid squares
		for(var col = 0; col < 10; col++){
			gridSpaces[id][row].push(" ");
		}
	}
}
//list of shots for each player
for(var id = 0; id <= 1; id++){
	shotSpaces.push(new Array());
	//setup the mother grid
	for(var row = 0; row < 10; row++){
		shotSpaces[id].push(new Array());
		//make an array for the top left corners of all grid squares
		for(var col = 0; col < 10; col++){
			shotSpaces[id][row].push(null);
		}
	}
}

//======================================================================================================================================================//

function bodyLoaded(){
	var url = window.location.href;
	var param = url.split("?");

	if(param.length > 1){
		var logFile = param[1];
		var client = new XMLHttpRequest();
		client.open('GET', 'BattleShipServer/logs/' + logFile);
		client.onreadystatechange = function(){
			if(client.readyState === XMLHttpRequest.DONE && client.status === 200){
				gameData = client.responseText;
				lines = gameData.split("\n");
				gameBoard = document.getElementById("myCanvas");
				ctx = gameBoard.getContext("2d");
				play();
			}
		}
		client.send();
	}
}
//file:///Users/tamer/Documents/bs/canvas_v3/index.html?JavaBossVSBoss.log
//======================================================================================================================================================//

function back(){
	pause();
	unMunchData();
}

function pause(){
	run = 0;
}

function step(){
	pause();
	munchData();
}

function play(){
	if(run) return;
	run = 1;
	munchData();
}

//======================================================================================================================================================//
//processes data
function munchData(){
	if(current_line == lines.length){
		run = 0;
		return;
	}
	parseLine(lines[current_line++]);
	drawGrid(0);
	drawGrid(1);
	if(run){
		setTimeout(function(){munchData(lines)}, ms);
	}
}

function unMunchData(){
	if(current_line == 0){
		return;
	}
	unParseLine(lines[--current_line]);
	drawGrid(0);
	drawGrid(1);
}

//could be included in munchdata, but this makes it more modular.
//decideds the type of line and acts accordingly
function parseLine(lineData){
	var lineType = String(lineData.slice(0,3));
	if(lineType === "NAM"){
		//grab the players name and return it
		//example line:
		//NAME 0: Dummy
		var id = lineData[5];
		var playerName = lineData.slice(8, lineData.length);
		playerNames[id] = playerName;
		//document.getElementById("namePlayer" + player).innerHTML = lineData.slice(7, lineData.length);
		ctx.beginPath();
		ctx.font = "44px Arial";
		ctx.fillStyle = textColor;
		ctx.fillText(playerName, 10 + id * (gridx * 10 + padding), 40);
	}
	else if(lineType === "PLA"){
		//place the ships through a method, return 0?
		//example line:
		//PLACE 0: {"B": [3, 1, "h"], "C": [3, 8, "h"], "S": [1, 2, "h"], "P": [2, 6, "h"], "D": [1, 5, "h"]}
		var ships = JSON.parse(lineData.slice(9));
		var player = Number(lineData[6]);
		for(var key in ships){
			placeShip(player, ships[key][0], ships[key][1], ships[key][2], key);
		}
	}
	else if(lineType === "SHO"){
		//send a bullet from the players ID to the coordinates specified
		//example line:
		//SHOOT 0: [5, 5]
		var location = JSON.parse(lineData.slice(8));
		var id = Math.abs(Number(lineData[6])-1);
		var wasHit = false;
		if(gridSpaces[id][location[0]][location[1]] !== " "){
			wasHit = true;
		}
		shotSpaces[id][location[0]][location[1]] = wasHit;
	}
	else if(lineType === "ERR"){
		//example line:
		//ERROR 0: XYZ
		var id = lineData[6];
		var error = lineData.slice(9, lineData.length);

                alert("Error from " + playerNames[id] + ": " + error);
	}
	else if(lineType === "WIN"){
		//declare winner and stop program
		//example line:
		//WIN 1
		var id = lineData[4];

                alert(playerNames[id] + " has won!");
	}
	else{
		//something went terribly wrong! (or I did something terribly wrong :( )
	}
}

//decideds the type of line and acts accordingly
function unParseLine(lineData){
	var lineType = String(lineData.slice(0,3));
	if(lineType === "SHO"){
		//remove bullet from the players ID to the coordinates specified
		//example line:
		//SHOOT 0: [5, 5]
		var location = JSON.parse(lineData.slice(8));
		var id = Math.abs(Number(lineData[6])-1);
		shotSpaces[id][location[0]][location[1]] = null;
	}
}

//======================================================================================================================================================//

function placeShip(id, x, y, orien, type){
	var width = gridx;
	var height = gridy;
	var length = 1;
	
	switch(type){
		case "C":
			length = 5;
			break;
		case "B":
			length = 4;
			break;
		case "S":
			length = 3;
			break;
		case "D":
			length = 3;
			break;
		case "P":
			length = 2;
			break;
		default:
			length = 0;
			break;
		}
	//set the height or width to length value
	//depending on orientation
	if(orien === "h"){
		width *= length;

		for (var i = x; i < length + x; i++) {
			gridSpaces[id][i][y] = type;
		}
	}
	else if(orien === "v"){
		height *= length;

		for (var i = y; i < length + y; i++) {
			gridSpaces[id][x][i] = type;
		}
	}
	else{
	}
}

//======================================================================================================================================================//

function drawGrid(id){
	//draw the ocean
	ctx.fillStyle = oceanColor;
	ctx.rect(id*(10 * gridx + padding), namePadding, gridx*10, gridy*10);
	ctx.fill();

	//get columns ready
	for(var i = 0; i < 10; i++){
		//for each row...
		for(var j = 0; j < 10; j++){
			//get ready to draw
			ctx.beginPath();
			var x = i*gridx + id * (10 * gridx + padding);
			var y = j*gridy + namePadding;
			//if there is not ocean, there is a ship, so fill the tile with the ships color
			if(gridSpaces[id][i][j] !== " "){
				//we need to make sure this cell is the first cell of this ship
				if(i>0 && gridSpaces[id][i][j] === gridSpaces[id][i-1][j]){
					//nothing
				}
				else if(j>0 && gridSpaces[id][i][j] === gridSpaces[id][i][j-1]){
					//nothing
				}
				else{
					var dir = "h";
					if(j<9 && gridSpaces[id][i][j] === gridSpaces[id][i][j+1]){
						dir = "v";
					}
					
					var length = gridx;
					if(gridSpaces[id][i][j] === "C") length *= 5;
					if(gridSpaces[id][i][j] === "B") length *= 4;
					if(gridSpaces[id][i][j] === "S") length *= 3;
					if(gridSpaces[id][i][j] === "D") length *= 3;
					if(gridSpaces[id][i][j] === "P") length *= 2;
					
					var width = gridx;
					var height = gridx;
					
					if(dir === "h")
						width = length;
					else
						height = length;

					ctx.fillStyle = shipColor;
					ctx.ellipse(x+width/2, y+height/2, width/2-shipPadding/2, height/2-shipPadding/2, 0, 0, 2*Math.PI);
					ctx.fill();
				}
			}
			//if this place has been hit it will draw a colored circle
			if(shotSpaces[id][i][j] !== null){
				ctx.beginPath();
				if(shotSpaces[id][i][j])
					ctx.fillStyle = hitColor;
				else
					ctx.fillStyle = missColor;
				ctx.arc(x + gridy/2, y + gridy/2, bulletRadius, 0, Math.PI*2, true);
				ctx.fill();
			}
		}
	}
}

//======================================================================================================================================================//


