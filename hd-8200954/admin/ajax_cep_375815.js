function devolveCEP (http,endereco,bairro,cidade,estado) {
	if (http.readyState == 4) {
		if (http.status == 200) {
			results = http.responseText.split(";");
			if (typeof (results[0]) != 'undefined') endereco.value = results[0];
			if (typeof (results[1]) != 'undefined') bairro.value   = results[1];
			if (typeof (results[2]) != 'undefined') cidade.value   = results[2];
			if (typeof (results[3]) != 'undefined') estado.value   = results[3];
		}
	}
}

function buscaCEP(cep,endereco,bairro,cidade,estado) {
	if (endereco.value.length == 0) {
		http.open("GET", "http://www.telecontrol.com.br/assist/admin/ajax_cep_375815.php?cep=" + escape(cep), true);
		http.onreadystatechange = function () { devolveCEP (http,endereco,bairro,cidade,estado) ; } ;
		http.send(null);
	}
}
