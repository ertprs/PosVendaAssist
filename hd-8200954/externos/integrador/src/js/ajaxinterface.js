var urlBase = window.location.href;

function makeRequest(url,data,method,functionReturn){
	$.ajax({
		type: method,
		url: url,
		data: data,
		success: functionReturn
	});

}

