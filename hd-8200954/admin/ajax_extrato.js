function devolveExtrato (http,codigo_posto,data_extrato,valor_extrato,qtde_os) {
	if (http.readyState == 4) {
		//alert('takashi4');
		if (http.status == 200) {
			//alert('takashi5');
			results = http.responseText.split(";");
			if (typeof (results[0]) != 'undefined') codigo_posto.value = results[0];
			if (typeof (results[1]) != 'undefined') data_extrato.value   = results[1];
			if (typeof (results[2]) != 'undefined') valor_extrato.value   = results[2];
			if (typeof (results[3]) != 'undefined') qtde_os.value   = results[3];
		}
	}
}

function buscaExtrato(extrato,codigo_posto,data_extrato,valor_extrato,qtde_os) {
	if (extrato.value.length > 0) {
	//	alert('takashi');
		url = "ajax_extrato.php?extrato=" + escape(extrato.value);
		http.open("GET", url, true);
	//	alert('takashi2');
		http.onreadystatechange = function () {
	//		alert('takashi3');
			devolveExtrato (http,codigo_posto,data_extrato,valor_extrato,qtde_os) ; 
			} ;
		http.send(null);
	}
}