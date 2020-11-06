window.addEventListener('load', function(){

  if (document.querySelectorAll('.addressZip') != null) {
    var zipInput = document.querySelectorAll('.addressZip');
    [].forEach.call(zipInput,function(e,i){
      e.addEventListener('blur', verifyZip);
    });
  }

  if (document.querySelectorAll('.addressState') != null) {
    var stateInput = document.querySelectorAll('.addressState');
    [].forEach.call(stateInput,function(e,i){
      e.addEventListener('blur', loadCities);
    });
  }

});

function verifyZip() {
    try {
      var ajax = new ActiveXObject("Microsoft.XMLHTTP");
    } catch(e) {
      try {
        var ajax = new ActiveXObject("Msxml2.XMLHTTP");
      } catch(ex) {
        try {
          var ajax = new XMLHttpRequest();
        } catch(exc) {
            alert("Esse browser n„o tem recursos para uso do Ajax"); ajax = null;
        }
      }
    }

    zip = escape(this.value);

    if (typeof method == "undefined" || method.length == 0) {
      method = "webservice";
      $.ajaxSetup({
        timeout: 3000
      });
    } else {
      $.ajaxSetup({
        timeout: 5000
      });
    }

    var multiCep = null;
    var beforeElement = this;

    while(multiCep == null){

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
            results = ajax.responseText.split(";");

            if (results[0] != 'ok'){
                alert(results[0]);
                return false;
            }

            if (multiCep != null ) {
              address = multiCep.querySelector('.address');
              addressDistrict = multiCep.querySelector('.addressDistrict');
              addressCity = multiCep.querySelector('.addressCity');
              addressState = multiCep.querySelector('.addressState');
            }else{
              address = document.querySelector('.address');
              addressDistrict = document.querySelector('.addressDistrict');
              addressCity = document.querySelector('.addressCity');
              addressState = document.querySelector('.addressState');
            }


            if (typeof (results[3]) != 'undefined'){
              addressCity.value       = results[3];
              nameCity = retiraAcentos(results[3]);
            }

            if (typeof (results[4]) != 'undefined'){
                addressState.value      = results[4];
                loadCities(nameCity,multiCep);
            }

            if (typeof (results[3]) != 'undefined') addressCity.value       = results[3];
            if (typeof (results[2]) != 'undefined') addressDistrict.value   = results[2];
            if (typeof (results[1]) != 'undefined') address.value           = results[1];

        }
    };

    ajax.open("GET", "ajax_cep.php?cep="+zip+"&method="+method, true);
    ajax.send();
}

function loadCities(nameCity,multiCep) {
  if (multiCep != null) {
    addressState = multiCep.querySelector('.addressState').value;
  } else {
    addressState = this.value;
    addressThis = this;
  }

  if (addressState == undefined){
    addressState = document.querySelector('.addressState').value;
  }


  if (addressState != '' && addressState != null ) {

    try {
      var ajax = new ActiveXObject("Microsoft.XMLHTTP");
    } catch(e) {
      try {
        var ajax = new ActiveXObject("Msxml2.XMLHTTP");
      } catch(ex) {
        try {
          var ajax = new XMLHttpRequest();
        } catch(exc) {
            alert("Esse browser n„o tem recursos para uso do Ajax"); ajax = null;
        }
      }
    }

    if (multiCep == null ) {
      var beforeElement = addressThis;

      while(multiCep == null){

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
              addressCity = multiCep.querySelector('.addressCity');
            }else{
              addressCity = document.querySelector('.addressCity');
            }

            addressCity.innerHTML = "<option value=''>Selecione</option>";

            results.cidades.forEach(function(cidade, i) {
              var option = document.createElement("option");
              option.value = cidade;
              option.textContent = cidade;
              if (nameCity == cidade) {
                option.selected = true;
              }
              addressCity.appendChild(option);
            });
        }
    };
    ajax.open("GET", "ajax_cep.php?state="+addressState, true);
    ajax.send();

  } else {
    if (multiCep != null) {
      addressCity = multiCep.querySelector('.addressCity');
    }else{
      addressCity = document.querySelector('.addressCity');
    }
    addressCity.innerHTML = "<option value=''>Selecione</option>";
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