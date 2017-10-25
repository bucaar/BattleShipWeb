/*
This is a visualizer script for a battleship game.
Made by Isaac Burton
*/

//======================================================================================================================================================//

//things the whole program should know
var padding = 100;
var gridx = 50;
var gridy = 50;
var bulletRadius = gridx/4;
var namePadding = 50;
var ms = 100;
var gameData;
var lines;

var current_line = 0;
var run = 0;
var stepForward = 0;
var stepBack = 0;

//gameboard and context
var ctx = null, gameBoard = null;

//index values occupied by ships for each player
var gridSpaces = new Array();
var shotSpaces = new Array();

//colors!
var shipColor = "#DDDDDD";
var oceanColor = "#00FFFF";
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

function urlLoaded(){
	var url = window.location.href;
	var param = url.split("?");

	if(param.length > 1){
		var logFile = param[1];
		var client = new XMLHttpRequest();
		client.open('GET', 'BattleShipServer/logs/' + logFile);
		client.onreadystatechange = function(){
			if(client.readyState === XMLHttpRequest.DONE && client.status === 200){
				gameData = client.responseText;
				gameBoard = document.getElementById("myCanvas");
				ctx = gameBoard.getContext("2d");
				start();
			}
		}
		client.send();
	}
}
//file:///Users/tamer/Documents/bs/canvas_v3/index.html?JavaBossVSBoss.log
//======================================================================================================================================================//

function start(){
	//start at line 1 of the file and go from there
	run = 1;

	//add event listeners for buttons that will soon be coming
	lines = gameData.split("\n");
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

//could be included in munchdata, but this makes it more modular.
//decideds the type of line and acts accordingly
function parseLine(lineData){
	var lineType = String(lineData.slice(0,3));
	if(lineType === "NAM"){
		//grab the players name and return it
		//example line:
		//NAME 0: Dummy
		var id = lineData[5];
		var playerName = lineData.slice(7, lineData.length);
		//document.getElementById("namePlayer" + player).innerHTML = lineData.slice(7, lineData.length);
		ctx.beginPath();
		ctx.font = "44px Arial";
		ctx.fillStyle = textColor;
		ctx.fillText(playerName, 10 + id * (gridx * 10 + padding), 35);
		}
	else if(lineType === "PLA"){
		//place the ships through a method, return 0?
		//example line:
		//PLACE 0: {"B": [3, 1, "h"], "C": [3, 8, "h"], "S": [1, 2, "h"], "P": [2, 6, "h"], "D": [1, 5, "h"]}
		var ships = JSON.parse(lineData.slice(9));
		var player = Number(lineData[6]);
		console.log(lineData[6]);
		for(var key in ships){
			shipConstructor(player, ships[key][0], ships[key][1], ships[key][2], key);
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
	else if(lineType === "WIN"){
		//declare winner and stop program
		//example line:
		//WIN 1
		console.log(lineData + "game over!");
		run = 0;
	}
	else{
		//something went terribly wrong! (or I did something terribly wrong :( )
		console.log("error...");
	}
}

//======================================================================================================================================================//

function shipConstructor(id, x, y, orien, type){
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
			alert("no length was set");
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
		console.log("Error setting orientation for ship " + type + " " + x + " " + y);
	}
}

//======================================================================================================================================================//

function drawGrid(id){
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
				ctx.fillStyle = shipColor;
				ctx.rect(x, y, gridx, gridy);
				ctx.fill();
				//if this has been marked as shot, draw a red circle on the ship space
				if(shotSpaces[id][i][j] === true){
					ctx.beginPath();
					ctx.fillStyle = hitColor;
					ctx.arc(x + gridx/2, y + gridy/2, bulletRadius, 0, Math.PI*2, true);
					ctx.fill();
				}
			}
			//if the space is ocean, color it as ocean
			else{
				ctx.fillStyle = oceanColor;
				ctx.rect(x, y, gridx, gridy);
				ctx.fill();
				//if this place has been hit it will draw a white circle
				if(shotSpaces[id][i][j] === false){
					ctx.beginPath();
					ctx.fillStyle = missColor;
					ctx.arc(x + gridy/2, y + gridy/2, bulletRadius, 0, Math.PI*2, true);
					ctx.fill();
				}
			}
		}
	}
}

//======================================================================================================================================================//

//======================================================================================================================================================//

//======================================================================================================================================================//

//======================================================================================================================================================//


