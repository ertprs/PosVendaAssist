function Geocoder() {

    var ajax = new XMLHttpRequest();

    var endereco = {
        endereco: null,
        numero: null,
        bairro: null,
        cidade: null,
        estado: null,
        pais: null,
	cep: null
    };

    this.last_url = null;

    var unaccent = function(v) {
        var str = v;

        var dict = {
            'a':new RegExp('[áàãâä]', 'g'),
            'e':new RegExp('[éèêë]', 'g'),
            'i':new RegExp('[íìïî]', 'g'),
            'o':new RegExp('[óòôõö]', 'g'),
            'u':new RegExp('[úùüû]', 'g'),
            'n':'ñ',
            'c':'ç',
            'A':new RegExp('[ÁÀÃÂÄ]', 'g'),
            'E':new RegExp('[ÉÈÊË]', 'g'),
            'I':new RegExp('[ÍÌÏÎ]', 'g'),
            'O':new RegExp('[ÓÒÔÕÖ]', 'g'),
            'U':new RegExp('[ÚÙÜÛ]', 'g'),
            'N':'Ñ',
            'C':'Ç'
        };

        for(vogal in dict) {
            str = str.replace(dict[vogal], vogal);
        }

        return v.replace(v.valueOf(), str);
    }

    this.setEndereco = function(e) {
        if (typeof e != "object") {
            throw new Error("Endereço não informado para a busca da Geolocalização");
        }

        (Object.keys(e)).forEach(function(v, k) {
            if (e[v] != null) {
		if (v == "cep") {
			e[v] = e[v].replace(/\./g, '');
		}
                endereco[v] = e[v];
            }
        });
    };

    this.getEndereco = function() {
        var e = {};

        (Object.keys(endereco)).forEach(function(v, k) {
            if (endereco[v] != null && endereco[v].length > 0) {
                e[v] = endereco[v];
            }
        });

        return e;
    };

    this.getLatLon = function() {
        if (endereco.cidade == null || endereco.estado == null || endereco.pais == null) {
            throw new Error("Cidade, Estado e País são obrigatórios para a busca da Geolocalização");
        }

        var url = [];

        (Object.keys(this.getEndereco())).forEach(function(v, k) {
            url.push(v+"="+unaccent(endereco[v].trim()));
        });

        url = url.join("&");

        this.last_url = "controllers/TcMaps.php?ajax=geocode&"+url;

        return new Promise(function(resolve, reject) {
            ajax.open("GET", "controllers/TcMaps.php?ajax=geocode&"+url, true);
            ajax.timeout = 60000;
            ajax.send();

            ajax.ontimeout = function () {
                reject("Erro ao buscar Geolocalização, Tempo limite esgotado");
            }

            ajax.onreadystatechange = function() {
                if (ajax.readyState == 4) {
                    var status = parseInt((String(ajax.status))[0]);

                    switch (status) {
                        case 2:
                            if (ajax.responseText.length == 0 || ajax.responseText == "null") {
                                reject("Erro ao buscar Geolocalização, sem resposta do servidor");
                                break;
                            }

                            var resposta = JSON.parse(ajax.responseText);

                            if (resposta.error) {
                                reject(resposta.error);
                            }

                            resolve(resposta);
                            break;

                        case 4:
                        case 5:
                            reject("Erro ao buscar Geolocalização #1");
                            break;

                        default:
                            reject("Erro ao buscar Geolocalização #2");
                            break;
                    }
                }
            };
        });
    };
}
