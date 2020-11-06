function rotate(){
	actualURL = "includes/rotate/rotate.php?x=90&src=" + historyImages[historyPosition];
	setTimeout("callEffect()", 0);
}

function rotate_e(){
	actualURL = "includes/rotate/rotate.php?x=270&src=" + historyImages[historyPosition];
	setTimeout("callEffect()", 0);
}