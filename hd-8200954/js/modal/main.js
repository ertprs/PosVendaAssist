messageObj = new DHTML_modalMessage();	// We only create one object of this class
messageObj.setShadowOffset(8);	// Large shadow

function displayMessage(url,largura,altura)
{
	
	messageObj.setSource(url);
	messageObj.setCssClassMessageBox(false);
	messageObj.setSize(largura,altura);
	messageObj.setShadowDivVisible(true);	// Enable shadow for these boxes
	messageObj.display();

	//$('#lp_content_table').css("height",(altura - 130)+'px');
}

function displayStaticMessage(messageContent,cssClass)
{
	messageObj.setHtmlContent(messageContent);
	messageObj.setSize(900,500);
	messageObj.setCssClassMessageBox(cssClass);
	messageObj.setSource(false);	// no html source since we want to use a static message here.
	messageObj.setShadowDivVisible(false);	// Disable shadow for these boxes	
	messageObj.display();
}

