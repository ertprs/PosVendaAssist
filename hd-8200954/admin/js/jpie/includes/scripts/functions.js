function loadJPIE(img) { 	
	inc("includes/crop/crop.js");
	inc("includes/rotate/rotate.js");
	inc("includes/open/open.js");
	inc("includes/save/save.js");
	inc("includes/resize/resize.js");
	inc("includes/brightContrast/brightContrast.js");
	
	//Here we load the actual image path. TODO: historial path
	historyImages[historyPosition] = img;
	
	//Load actual image into canvas
	mainImage.src = imagePath + historyImages[historyPosition];
}

var canvas = document.getElementById('canvas');
var mainImage = document.getElementById('mainImage');


var lastMouseDown;
var lastMouseUp;
var currentSelection = new Object();
var historyImages = new Array();
var historyPosition = 0;
var imagePath = "tmpImages/";


//Coordinates inside the canvas
var mousePos; // [x,y]


function updateImage(newImg) {
	// Updates history array and loads the current image
	historyPosition = historyPosition +1;
	historyImages[historyPosition] = newImg;
	mainImage.src = imagePath + historyImages[historyPosition];
	removeSelection();
}

function undo(){
 	//TODO: Controlate the first item
 	
	historyPosition = historyPosition - 1;
	mainImage.src = imagePath + historyImages[historyPosition];
	removeSelection();		
}

function redo(){
 	//TODO: Controlate the last item
	historyPosition = historyPosition + 1;
	mainImage.src = imagePath + historyImages[historyPosition];
	removeSelection();
}
var refresh_function = function(){return false;};

//get mouse coordinate inside de "canvas"
function mouseCoords(ev){
	if(ev.pageX || ev.pageY){
		return {x:ev.pageX - getPosition(canvas).x, y:ev.pageY- getPosition(canvas).y};
	}
	return {
		x:ev.clientX + document.body.scrollLeft - document.body.clientLeft - getPosition(canvas).x,
		y:ev.clientY + document.body.scrollTop  - document.body.clientTop - getPosition(canvas).y
	}
}


// get position of de "e" object
function getPosition(e){
	var left = 0;
	var top  = 0;

	while (e.offsetParent){
		left += e.offsetLeft;
		top  += e.offsetTop;
		e     = e.offsetParent;
	}

	left += e.offsetLeft;
	top  += e.offsetTop;

	return {x:left, y:top};
}



// function removeSelection

function removeSelection() {
	var Node = document.getElementById("selection");
	if (Node!= null) {
		Node.parentNode.removeChild(Node);
	}
	
}
// Funcion selección
function create_selection() {
 
	//Remove "selection" node if exists
	removeSelection();
	
	var selection  = document.createElement('div');
	selection.setAttribute('id','selection')
	canvas.appendChild(selection);
	
	selection.style.border = "1px dashed #000000";
	selection.style.position = "absolute";
	selection.style.top = mousePos.y + "px";
	selection.style.left = mousePos.x+ "px";
	selection.style.zIndex = "2";
		
}

var refresh_selection = function() {
	var selection = document.getElementById('selection');

	selection.style.width  = (Number(mousePos.x) - Number(lastMouseDown.x)) + "px";
	selection.style.height  = (Number(mousePos.y) - Number(lastMouseDown.y)) + "px";
}

function delete_selection() {
	refresh_function = function(){return false;};
	currentSelection.x = Number(lastMouseDown.x);
	currentSelection.y = Number(lastMouseDown.y);
	currentSelection.width = Number(mousePos.x) - Number(lastMouseDown.x);
	currentSelection.height = Number(mousePos.y) - Number(lastMouseDown.y);
}

function inc(filename) {
	var body = document.getElementsByTagName('body').item(0);
	script = document.createElement('script');
	script.src = filename;
	script.type = 'text/javascript';
	body.appendChild(script)
}

function callEffect() {
	  http.open("GET", actualURL, true);
	  http.onreadystatechange = handleHttpResponse;
	  http.send(null);
}

// ---- Funciones AJAX ----
function handleHttpResponse() {
  if (http.readyState == 4 && http.status == 200) {
   //alert(http.responseText);
	if ( http.responseText == "Error 500" ) {
		//Error	  
	} else {
		updateImage(http.responseText);
	};
  }
}

function getHTTPObject() {
	var xmlhttp;
	/*@cc_on
	@if (@_jscript_version >= 5)
	try {
	  xmlhttp = new ActiveXObject("Msxml2.XMLHTTP");
	} catch (e) {
	  try {
		xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
	  } catch (E) {
		xmlhttp = false;
	  }
	}
	@else
	xmlhttp = false;
	@end @*/
	if (!xmlhttp && typeof XMLHttpRequest != 'undefined') {
	try {
	  xmlhttp = new XMLHttpRequest();
	} catch (e) {
	  xmlhttp = false;
	}
	}
	return xmlhttp;
}

var http = getHTTPObject(); // We create the HTTP Object