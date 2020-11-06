window.addEventListener('load', function(){

  if (document.querySelectorAll('.addressZip') != null) {
    var zipInput = document.querySelectorAll('.addressZip');
    [].forEach.call(zipInput,function(e,i){
      var descricao = "consumidor";
      if (e.dataset.beforeAjaxZip) {
        e.addEventListener('blur', function(e) {
          document[e.target.dataset["beforeAjaxZip"]](e.target).then(
            function(resolved) {
              if (e.target.dataset.afterAjaxZip) {
                verifyZip(e.target, descricao).then(
                  function(resolved) {
                    document[e.target.dataset["afterAjaxZip"]](e.target);
                  },
                  function(rejected) {
                    if (e.target.dataset.failAjaxZip) {
                      document[e.target.dataset["failAjaxZip"]](e.target);
                    }
                  }
                );
              } else {
                verifyZip(e.target, descricao);
              }
            },
            function(rejected) {

              if (rejected) {
                      alert("Erro ao carregar informaÁıes do CEP");
              }
            }
          );
        });
      } else {
        e.addEventListener('blur', function() {
          verifyZip(this, descricao);
        }); 
      }
    });
  }

  if (document.querySelectorAll('.addressZip_rev') != null) {
    var zipInput_rev = document.querySelectorAll('.addressZip_rev');
    [].forEach.call(zipInput_rev,function(e,i){
      var descricao = "revenda";
      if (e.dataset.beforeAjaxZip) {
        e.addEventListener('blur', function(e) {
          document[e.target.dataset["beforeAjaxZip"]](e.target).then(
            function(resolved) {
              if (e.target.dataset.afterAjaxZip) {
                verifyZip(e.target, descricao).then(
                  function(resolved) {
                    document[e.target.dataset["afterAjaxZip"]](e.target);
                  },
                  function(rejected) {
                    if (e.target.dataset.failAjaxZip) {
                      document[e.target.dataset["failAjaxZip"]](e.target);
                    }
                  }
                );
              } else {
                verifyZip(e.target, descricao);
              }
            },
            function(rejected) {
        if (rejected) {
                alert("Erro ao carregar informaÁıes do CEP da revenda");
        }
            }
          );
        });
      } else {
        e.addEventListener('blur', function() {
          verifyZip(this, descricao);
        }); 
      }
    });
  }

  if (document.querySelectorAll('.addressState') != null) {
    var stateInput = document.querySelectorAll('.addressState');
    [].forEach.call(stateInput,function(e,i){
      var descricao = "consumidor";
      e.addEventListener('change', function(){loadCities(null,null,descricao)});
    });
  }

  if (document.querySelectorAll('.addressState_rev') != null) {
    var stateInput_rev = document.querySelectorAll('.addressState_rev');
    [].forEach.call(stateInput_rev,function(e,i){
      var descricao = "revenda";
      e.addEventListener('change', function(){loadCities(null,null,descricao)});
    });
  }
});

var Ajax = function() {
  var ajax = null;
  try {
    ajax = new ActiveXObject("Microsoft.XMLHTTP");
  } catch(e) {
    try {
      ajax = new ActiveXObject("Msxml2.XMLHTTP");
    } catch(ex) {
      try {
        ajax = new XMLHttpRequest();
      } catch(exc) {
        alert("Seu browser n„o tem recursos para uso do Ajax");
      }
    }
  }

  if (typeof ajax == 'object')
    ajax.timeout = 10000;

  return ajax;
};

function verifyZip(e, descricao) {
  return new Promise(function(resolve, reject) {
    var ajax = Ajax();

    if (ajax === null) {
      reject(true);
      return false;
    }

    zip = escape(e.value);

    if (!zip.length) {
      reject(true);
      return false;
    }

    if (typeof method == "undefined" || method.length == 0) {
      method = "webservice";
    } else {
      ajax.timeout = 5000;
    }

    ajax.ontimeout = function () {
      verifyDatabase(zip, descricao).then(
        function(resolved) {
          resolve(true);
        },
        function(rejected) {
          reject(true);
        }
      );
    };

    var multiCep = null;
    var beforeElement = this;

    while (multiCep == null) {

      if (typeof beforeElement.parentNode == 'undefined' || beforeElement.parentNode == null) {
        break;
      }

      if (beforeElement.className.match(/multiCep/)) {
        multiCep = beforeElement;
        break;
      }
      beforeElement = beforeElement.parentNode;
    }

    ajax.onreadystatechange = function() {
      if (ajax.readyState == 4 && ajax.status == 200) {
	if (ajax.responseText.match(/^org\.hibernate\./)) {
		verifyDatabase(zip, descricao).then(
        		function(resolved) {
          			resolve(true);
        		},
        		function(rejected) {
          			reject(true);
        		}
      		);
		return false;

	}

        results = ajax.responseText.split(";");
        if (results[0] != 'ok'){
          alert(results[0]);
          reject(true);
          return false;
        }
        var city = results[3];
        city = city.replace("(", "");
        city = city.replace(")", "");
        city = city.replace("'", "");
        results[3] = city;

        if (verificaURL() == true){
          var rev_cons = "consumidor";  
        } else {
          var rev_cons = descricao;
        }

        if (multiCep != null ) {
          if (rev_cons == "consumidor") {
            address = multiCep.querySelector('.address');
            addressDistrict = multiCep.querySelector('.addressDistrict');
            addressCity = multiCep.querySelector('.addressCity');
            addressState = multiCep.querySelector('.addressState');
          } else if (rev_cons == "revenda") {
            address_rev = multiCep.querySelector('.address_rev');
            addressDistrict_rev = multiCep.querySelector('.addressDistrict_rev');
            addressCity_rev = multiCep.querySelector('.addressCity_rev');
            addressState_rev = multiCep.querySelector('.addressState_rev');
          }
        }else{
          if (rev_cons == "consumidor") {
            address = document.querySelector('.address');
            addressDistrict = document.querySelector('.addressDistrict');
            addressCity = document.querySelector('.addressCity');
            addressState = document.querySelector('.addressState');
          } else if (rev_cons == "revenda") {
            address_rev = document.querySelector('.address_rev');
            addressDistrict_rev = document.querySelector('.addressDistrict_rev');
            addressCity_rev = document.querySelector('.addressCity_rev');
            addressState_rev = document.querySelector('.addressState_rev');
          }
        }

        if (typeof (results[3]) != 'undefined'){
          if (rev_cons == "consumidor") {
            addressCity.value       = results[3];
            nameCity = retiraAcentos(results[3]);
          } else if (rev_cons == "revenda") {
            addressCity_rev.value       = results[3];
            nameCity_rev = retiraAcentos(results[3]);
          }
        }

        if (typeof (results[4]) != 'undefined'){
          if (rev_cons == "consumidor") {
            addressState.value      = results[4];
            loadCities(nameCity,multiCep, rev_cons);
          } else if (rev_cons == "revenda") {
            addressState_rev.value      = results[4];
            loadCities(nameCity_rev,multiCep, rev_cons);
          }
        }

        if (rev_cons == "consumidor") {
          if (typeof (results[3]) != 'undefined') addressCity.value       = results[3];
          if (typeof (results[2]) != 'undefined') addressDistrict.value   = results[2];
          if (typeof (results[1]) != 'undefined') address.value           = results[1];
        } else if (rev_cons == "revenda") {
          if (typeof (results[3]) != 'undefined') addressCity_rev.value       = results[3];
          if (typeof (results[2]) != 'undefined') addressDistrict_rev.value   = results[2];
          if (typeof (results[1]) != 'undefined') address_rev.value           = results[1];
        }

        resolve(true);
      }
    };

    ajax.open("GET", "ajax_cep.php?cep="+zip+"&method="+method, true);
    ajax.send();
  });
}

//hd_chamado=2905059
function verifyDatabase(cep, descricao) {
  return new Promise(function(resolve, reject) {
    var ajax = Ajax();

    if (ajax === null) {
      reject(true);
      return false;
    }

    zip = cep;
    method = "database";
    var multiCep = null;
    var beforeElement = this;

    while (multiCep == null) {

      if (typeof beforeElement.parentNode == 'undefined' || beforeElement.parentNode == null) {
        break;
      }

      if (beforeElement.className.match(/multiCep/)) {
        multiCep = beforeElement;
        break;
      }
      beforeElement = beforeElement.parentNode;
    }

    ajax.ontimeout = function () {
        alert("Erro carregar informaÁıes do CEP");
        reject(true);
    };

    ajax.onreadystatechange = function() {
      if (ajax.readyState == 4 && ajax.status == 200) {
        results = ajax.responseText.split(";");
        if (results[0] != 'ok'){
          alert(results[0]);
          reject(true);
          return false;
        }

        if (verificaURL() == true){
          var rev_cons = "consumidor";  
        } else {
          var rev_cons = descricao;
        }

        if (multiCep != null ) {
          if (rev_cons == "consumidor") {
            address = multiCep.querySelector('.address');
            addressDistrict = multiCep.querySelector('.addressDistrict');
            addressCity = multiCep.querySelector('.addressCity');
            addressState = multiCep.querySelector('.addressState');
          }  else if (rev_cons == "revenda") {
            address_rev = multiCep.querySelector('.address_rev');
            addressDistrict_rev = multiCep.querySelector('.addressDistrict_rev');
            addressCity_rev = multiCep.querySelector('.addressCity_rev');
            addressState_rev = multiCep.querySelector('.addressState_rev');
          }
        }else{
          if (rev_cons == "consumidor") {
            address = document.querySelector('.address');
            addressDistrict = document.querySelector('.addressDistrict');
            addressCity = document.querySelector('.addressCity');
            addressState = document.querySelector('.addressState');
          } else if (rev_cons == "revenda") {
            address_rev = document.querySelector('.address_rev');
            addressDistrict_rev = document.querySelector('.addressDistrict_rev');
            addressCity_rev = document.querySelector('.addressCity_rev');
            addressState_rev = document.querySelector('.addressState_rev');
          }
        }
        if (typeof (results[3]) != 'undefined'){
          if (rev_cons == "consumidor") {
            addressCity.value       = results[3];
            nameCity = retiraAcentos(results[3]);
          } else if (rev_cons == "revenda") {
            addressCity_rev.value       = results[3];
            nameCity_rev = retiraAcentos(results[3]);
          }
        }
        if (typeof (results[4]) != 'undefined'){
          if (rev_cons == "consumidor") {
            addressState.value      = results[4];
            loadCities(nameCity,multiCep, rev_cons);
          } else if (rev_cons == "revenda") {
            addressState_rev.value      = results[4];
            loadCities(nameCity_rev,multiCep, rev_cons);
          }
        }

        if (rev_cons == "consumidor") {
          if (typeof (results[3]) != 'undefined') addressCity.value       = results[3];
          if (typeof (results[2]) != 'undefined') addressDistrict.value   = results[2];
          if (typeof (results[1]) != 'undefined') address.value           = results[1];
        } else if (rev_cons == "revenda") {
          if (typeof (results[3]) != 'undefined') addressCity_rev.value       = results[3];
          if (typeof (results[2]) != 'undefined') addressDistrict_rev.value   = results[2];
          if (typeof (results[1]) != 'undefined') address_rev.value           = results[1];
        }
        resolve(true);
      }
    };

    ajax.open("GET", "ajax_cep.php?cep="+zip+"&method="+method, true);
    ajax.send();
  });
}

// FIM Monteiro
function loadCities(nameCity,multiCep, descricao) {
  if (verificaURL() == true){
    var rev_cons = "consumidor";  
  } else {
    var rev_cons = descricao;
  }
  if (multiCep != null) {
    if (rev_cons == "consumidor") {
      addressState = multiCep.querySelector('.addressState').value;
      var verifica = addressState;
    } else if (rev_cons == "revenda") {
      addressState_rev = multiCep.querySelector('.addressState_rev').value;
      var verifica = addressState_rev;
    }
  } else {
    if (rev_cons == "consumidor") {
      addressState = this.value;
      var verifica = addressState;
    } else if (rev_cons == "revenda") {
      addressState_rev = this.value;
      var verifica = addressState_rev;
    }
    
    addressThis = this;
  }

  if (rev_cons == "consumidor") {
    if (addressState == undefined){
      addressState = document.querySelector('.addressState').value;
      var verifica = addressState;
    }
  } else if (rev_cons == "revenda") {
    if (addressState_rev == undefined){
      addressState_rev = document.querySelector('.addressState_rev').value;
      var verifica = addressState_rev;
    }
  }

  if (verifica != '' && verifica != null ) {
    var ajax = Ajax();

    if (ajax === null)
      return false;

    if (multiCep == null ) {
      var beforeElement = addressThis;

      while (multiCep == null) {

        if (typeof beforeElement.parentNode == 'undefined' || beforeElement.parentNode == null) {
          break;
        }

        if (beforeElement.className.match(/multiCep/)) {
          multiCep = beforeElement;
          break;
        }
        beforeElement = beforeElement.parentNode;
      }
    }

    ajax.onreadystatechange = function() {

        if (ajax.readyState == 4 && ajax.status == 200) {

            results = JSON.parse(ajax.responseText);

            if (results.error){
                alert(results.error.toUpperCase());
                return false;
            }

            if (multiCep != null) {
              if (rev_cons == "consumidor") {
                addressCity = multiCep.querySelector('.addressCity');
              } else if (rev_cons == "revenda") {
                addressCity_rev = multiCep.querySelector('.addressCity_rev');  
              }
            }else{
              if (rev_cons == "consumidor") {
                addressCity = document.querySelector('.addressCity');
              } else if (rev_cons == "revenda") {
                addressCity_rev = document.querySelector('.addressCity_rev');  
              }
            }

            if (rev_cons == "consumidor") {
              addressCity.innerHTML = "<option value=''>Selecione</option>";
            } else if (rev_cons == "revenda") {
              addressCity_rev.innerHTML = "<option value=''>Selecione</option>";  
            }

            results.cidades.forEach(function(cidade, i) {
              var option = document.createElement("option");
              option.value = cidade;
              option.textContent = cidade;
              if (nameCity == cidade) {
                option.selected = true;
              }

              if (rev_cons == "consumidor") {
                addressCity.appendChild(option);
              } else if (rev_cons == "revenda") {
                addressCity_rev.appendChild(option);  
              }
            });

            if (typeof document.afterLoadCities == "function") {
              document.afterLoadCities();
            }
        }
    };

    if (rev_cons == "consumidor") {
      ajax.open("GET", "ajax_cep.php?state="+addressState, true);
    } else if (rev_cons == "revenda") {
      ajax.open("GET", "ajax_cep.php?state="+addressState_rev, true);
    }
    
    ajax.send();

  } else {
    if (multiCep != null) {

      if (rev_cons == "consumidor") {
        addressCity = multiCep.querySelector('.addressCity');
      } else if (rev_cons == "revenda") {
        addressCity_rev = multiCep.querySelector('.addressCity_rev');  
      }
    }else{
      if (rev_cons == "consumidor") {
        addressCity = document.querySelector('.addressCity');
      } else if (rev_cons == "revenda") {
        addressCity_rev = document.querySelector('.addressCity_rev');  
      }
    }

    if (rev_cons == "consumidor") {
      addressCity.innerHTML = "<option value=''>Selecione</option>";
    } else if (rev_cons == "revenda") {
      addressCity_rev.innerHTML = "<option value=''>Selecione</option>";
    }
  }
}

function retiraAcentos(palavra){
    var com_acento = '·‡„‚‰ÈËÍÎÌÏÓÔÛÚıÙˆ˙˘˚¸Á¡¿√¬ƒ…» ÀÕÃŒœ”“’÷‘⁄Ÿ€‹«';
    var sem_acento = 'aaaaaeeeeiiiiooooouuuucAAAAAEEEEIIIIOOOOOUUUUC';
    var newPalavra = "";

    for(i = 0; i < palavra.length; i++) {
        if (com_acento.search(palavra.substr(i, 1)) >= 0) {
            newPalavra += sem_acento.substr(com_acento.search(palavra.substr(i, 1)), 1);
        } else {
            newPalavra += palavra.substr(i, 1);
        }
    }

    return newPalavra.toUpperCase();
}

function verificaURL(){
  var auxiliar = document.getElementsByClassName("address_url");
  if (auxiliar[0] != undefined) {
    var url = auxiliar[0].value;  
  } else {
    var url = "";
  }

  delete auxiliar;

  if (url == 'callcenter_interativo_new') {
    return true;
  } else {
    return false;
  }
}
