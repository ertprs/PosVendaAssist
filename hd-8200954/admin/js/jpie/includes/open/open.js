function openImage(filename) {
 	if (filename != null ){
		mainImage.src = imagePath + filename;

		// Clear the cache array
		historyPosition = 0;
		historyImages[historyPosition] = filename;

		//Close the file browser window
		openFile.window.close();
	} else {
		openFile = window.open("includes/open/openfile.php","openFile","width=400,height=375,scrollbars=NO");	
	}
	
}