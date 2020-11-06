function saveImage(){
	var newName= prompt('Enter the new name', '');

	actualURL = "includes/save/save.php?newName="+newName+"&oldName=" + historyImages[historyPosition];

	setTimeout("callEffect()", 0);
}