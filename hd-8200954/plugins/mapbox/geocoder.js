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
            'a':new RegExp('[�����]', 'g'),
            'e':new RegExp('[����]', 'g'),
            'i':new RegExp('[����]', 'g'),
            'o':new RegExp('[�����]', 'g'),
            'u':new RegExp('[����]', 'g'),
            'n':'�',
            'c':'�',
            'A':new RegExp('[�����]', 'g'),
            'E':new RegExp('[����]', 'g'),
            'I':new RegExp('[����]', 'g'),
            'O':new RegExp('[�����]', 'g'),
            'U':new RegExp('[����]', 'g'),
            'N':'�',
            'C':'�'
        };

        for(vogal in dict) {
            str = str.replace(dict[vogal], vogal);
        }

        return v.replace(v.valueOf(), str);
    }

    this.setEndereco = function(e) {
        if (typeof e != "object") {
            throw new Error("Endere�o n�o informado para a busca da Geolocaliza��o");
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
            throw new Error("Cidade, Estado e Pa�s s�o obrigat�rios para a busca da Geolocaliza��o");
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
                reject("Erro ao buscar Geolocaliza��o, Tempo limite esgotado");
            }

            ajax.onreadystatechange = function() {
                if (ajax.readyState == 4) {
                    var status = parseInt((String(ajax.status))[0]);

                    switch (status) {
                        case 2:
                            if (ajax.responseText.length == 0 || ajax.responseText == "null") {
                                reject("Erro ao buscar Geolocaliza��o, sem resposta do servidor");
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
                            reject("Erro ao buscar Geolocaliza��o #1");
                            break;

                        default:
                            reject("Erro ao buscar Geolocaliza��o #2");
                            break;
                    }
                }
            };
        });
    };
}
