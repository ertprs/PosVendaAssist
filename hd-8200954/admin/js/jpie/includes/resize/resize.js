function resize(scale_x,scale_y) {
 	if ((scale_x != null) || (scale_y != null)){
		//Close the file browser window.
		resizeFile.window.close(); 	 
		actualURL = "js/jpie/includes/resize/resize.php?w="+scale_x+"&h="+scale_y+"&src=" + historyImages[historyPosition];	
		setTimeout("callEffect()", 0);
	} else {
		resizeFile = window.open("js/jpie/includes/resize/resizeFile.php?src="+ historyImages[historyPosition],"resizeFile","width=300,height=200,scrollbars=NO");	
	}
}