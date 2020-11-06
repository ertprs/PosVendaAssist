function brightContrast(){
	var bright= prompt('Angle to rotate (CW)', '90');
	actualURL = "includes/brightContrast/brightContrast.php?x="+bright+"&src=" + historyImages[historyPosition];
	//setTimeout("callEffect()", 0);
}