function devolveCepRevenda (http,endereco,bairro,cidade,estado) {
	if (http.readyState == 4) {
		if (http.status == 200) {
			
			results = http.responseText.split(";");
			
			cidade.removeAttribute('readonly');
			var estados=new Array("AC", "AL", "AM", "AP","BA","CE","DF","ES","GO","MA","MT","MS","MG","PA","PB","PR","PE","PI","RJ","RN","RO","RS","RR","SC","SE","SP","TO");

			for (var i = 0; i < estados.length ; i++) {
				var estados_options = estados_options+' <option value='+estados[i]+'>  '+estados[i]+'</option>';
			} ;
			$('#revenda_estado').html(estados_options);
			if (results[0] != 'ok'){ return false; }
			cidade.setAttribute('readonly','readonly');
			$('#revenda_estado').find('option').remove();
			if (typeof (results[1]) != 'undefined') endereco.value = results[1];
			if (typeof (results[2]) != 'undefined') bairro.value   = results[2];
			if (typeof (results[3]) != 'undefined') cidade.value   = results[3];
			$('#revenda_estado').html('<option value="'+results[4]+'">'+results[4]+'</option>');
		}
	}
}

function buscaCepRevenda(cep,endereco,bairro,cidade,estado) {
	var http = getHTTPObject(); // Criado objeto HTTP
	if (endereco.value.length == 0 || 1 == 1) {
		url = "ajax_cep.php?cep=" + escape(cep)
		http.open("GET", url, true);
		http.onreadystatechange = function () { devolveCepRevenda (http,endereco,bairro,cidade,estado) ; } ;
		http.send(null);
	}
}

function devolveCEP (http,endereco,bairro,cidade,estado) {
	if (http.readyState == 4) {
		if (http.status == 200) {
			
			results = http.responseText.split(";");
			
			cidade.removeAttribute('readonly');
			estado.removeAttribute('disabled');

			if (results[0] != 'ok'){ return false; }

			cidade.setAttribute('readonly','readonly');
			if (typeof (results[1]) != 'undefined') endereco.value = results[1];
			if (typeof (results[2]) != 'undefined') bairro.value   = results[2];
			if (typeof (results[3]) != 'undefined') cidade.value   = results[3];
			if (typeof (results[4]) != 'undefined') estado.value   = results[4];
		}
	}
}

function buscaCEP(cep,endereco,bairro,cidade,estado) {
	var http = getHTTPObject(); // Criado objeto HTTP
	if (endereco.value.length == 0 || 1 == 1) {
		url = "ajax_cep.php?cep=" + escape(cep)
		http.open("GET", url, true);
		http.onreadystatechange = function () { devolveCEP (http,endereco,bairro,cidade,estado) ; } ;
		http.send(null);
	}
}

