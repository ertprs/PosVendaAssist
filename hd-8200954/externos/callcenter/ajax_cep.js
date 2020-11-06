function devolveCEP (http,endereco,bairro,cidade,estado) {
	if (http.readyState == 4) {
		if (http.status == 200) {
			
			results = http.responseText.split(";");

			if (results[0] != 'ok'){ return false; }

			if (results[4] != undefined) estado.value   = results[4];
			if (results[3] != undefined) cidade.value   = results[3];
			if (results[1] != undefined && results[1].length > 0) endereco.value = results[1];
			if (results[2] != undefined && results[2].length > 0) bairro.value   = results[2];
			
			
		}
	}
}

function buscaCEP(cep,endereco,bairro,cidade,estado) {
	if (endereco.value.length == 0 || 1 == 1) {
		url = "ajax_cep.php?cep=" + escape(cep);
		http.open("GET", url, true);
		http.onreadystatechange = function () { devolveCEP (http,endereco,bairro,cidade,estado) ; } ;
		http.send(null);
	}
}


