function fmtMoeda(valor, options) {
    if(options == undefined) {
            options = new Object;
            options.decimals = 2;
    }

    if(options.decimals == undefined) {
            options.decimals = 2;
    }
    
    valor         = valor.replace(/\D/g, '');
    var ln        = valor.length;
    var tGroups   = Math.round((ln-options.decimals)/3+0.49);
    var thGroup   = "(\\d{3})".repeat(tGroups - 1);
    var decGroup  = "(" + "\\d".repeat(options.decimals)+")";
    var regEx     = new RegExp("^(\\d{1,3})"+thGroup+decGroup+"$");
    var repStr    = new Array('','','');
    var grupos    = tGroups;
    var repString = '';

    for(i = 2; i < tGroups+1; i++) {
            repString += "$"+i+'.';
    }

    repString = "$1." + repString;
    repString = repString.replace(/\.$/, '');
    repStr[1] = repString + ',$' + i;

    return valor.replace(regEx, repStr.join(''));
}


$(function(){
	
	function formataMoedaBR(valor){ 

		var valor_total = parseInt(valor * (Math.pow(10,casas)));
		var inteiros =  parseInt(parseInt(valor * (Math.pow(10,casas))) / parseFloat(Math.pow(10,casas)));
		var centavos = parseInt(parseInt(valor * (Math.pow(10,casas))) % parseFloat(Math.pow(10,casas)));
		var casas = 2;
		var separdor_decimal = ',';
		var separador_milhar = '.';

		if(centavos%10 == 0 && centavos+"".length<2 ){
			centavos = centavos+"0";
		}else if(centavos<10){
			centavos = "0"+centavos;
		}

		var milhares = parseInt(inteiros/1000);
		inteiros = inteiros % 1000; 

		var retorno = "";

		if(milhares>0){
			retorno = milhares+""+separador_milhar+""+retorno
			
			if(inteiros == 0){
				inteiros = "000";
			} else if(inteiros < 10){
				inteiros = "00"+inteiros; 
			} else if(inteiros < 100){
				inteiros = "0"+inteiros; 
			}
		}

		retorno += inteiros+""+separdor_decimal+""+centavos;


		return retorno;

	}

	$("input[price=true]").each(function () {
		var cents = $(this).attr('pricecents');
		if(cents == undefined){

			cents = 2;
		}

		$(this).priceFormat({
			prefix: '',
            thousandsSeparator: '.',
            centsSeparator: ',',
            centsLimit: parseInt(cents)
		});
	});

});