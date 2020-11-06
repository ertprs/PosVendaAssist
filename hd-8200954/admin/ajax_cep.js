function buscaCEP(cep, endereco, bairro, cidade, estado, method, callback) {
	if (typeof cep != "undefined" && cep.length > 0) {
		if (typeof method == "undefined" || method == null || method.length == 0) {
			method = "webservice";

			$.ajaxSetup({
				timeout: 10000
			});
		} else {
			$.ajaxSetup({
                timeout: 5000
            });
		}

		$.ajax({
			url: "ajax_cep.php",
			type: "GET",
			data: { cep: cep, method: method },
			error: function(xhr, status, error) {
				buscaCEP(cep, endereco, bairro, cidade, estado, "database", callback);
			},
			success: function(data) {
				results = data.split(";");

				if (results[4] != undefined && results[4].length > 0) {
                    $("#consumidor_cidade, #cidade").removeAttr("readonly");
                } else {
                    $("#consumidor_cidade, #cidade").attr({ "readonly": "readonly" });
                }

				if (results[0] == "ok") {
					if (results[4] != undefined) estado.value = results[4];
                    if (results[3] != undefined) cidade.value = results[3];
                    if (results[1] != undefined && results[1].length > 0) endereco.value = results[1];
                    if (results[2] != undefined && results[2].length > 0) bairro.value = results[2];
				}

				if(typeof callback == "function"){
					callback(results);
				}
			}
		});
	}
}

