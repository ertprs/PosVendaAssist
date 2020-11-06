
function exportaCliente(faturamento,tipo) {
	

	//alert(tipo);

        $.ajax({
            url: "../distrib/integracao/enviarcliente.php",
            type: "GET",
            data: "faturamento="+faturamento+"&tipo="+tipo,
            beforeSend: function  () {
                
            },
            complete: function (retorno) {
            	
	    } 
        });

}

$(function () {
    $("#emissao").datepicker({
        maxDate: 0,
        minDate: "-180d",
        dateFormat: "dd/mm/yy" 
    }).mask("99/99/9999");	

    $("#listar_todos").click(function(){
        location = "gerencia_nfe.php?q=todos";
    });

    $("#btn_voltar").click(function(){
        location = "../distrib/index.php";
    });

	$("img.exporta").click(function() {
	
		var tr = $(this).parents("tr");
		var tipo = $(tr).find("input[name=tipo]").val();
		var faturamento = $(tr).find("input[name='faturamento[]']").val();
		var td = $(this).parent("td");
	
	
		 $.ajax({
	            url: "../distrib/integracao/enviarcliente.php",
        	    type: "GET",
	            data: "faturamento="+faturamento+"&tipo="+tipo,
	            beforeSend: function  () {
        		$(td).html("<img src='ajax-load.gif'>");        
	            },
        	    complete: function (retorno) {
		        $(td).html(retorno.responseText);
 		    } 
        	});


	});

	$("#btn-envia-embarque").click(function() {

		var embarques = $(".checkEmbarque:checked");

		for (i=0;i<embarques.length;i++) {

			enviarAPI(embarques[i]);

		}

	});

	$("#btn-retorno").click(function() {

		var embarques = $(".checkEmbarque:checked");

		for (i=0;i<embarques.length;i++) {

			enviarApiRetorno(embarques[i]);

		}

	});
});

function loading(evento,embarque,ret){

	//console.log(evento);

	if(evento=='show')  {
		$(embarque).parent().html("<img src='ajax-load.gif'>");
	} else {
		var embarque2 = embarque.value;
		$('#'+embarque2).html("<p>"+ret+"</p>");
	}
}



function enviarAPI(embarque) {
	
	var tipo = $(embarque).next().val();

//	alert(tipo);
//	return false;
    $.ajax({
        url: "../distrib/integracao/enviarpedido.php",
        type: "GET",
        data: "faturamento="+embarque.value+"&tipo="+tipo,
        beforeSend: function  () {
            loading("show",embarque);
        },
        complete: function (retorno) {
            loading("hide",embarque,retorno.responseText);
        } 
    });
}


function enviarApiRetorno(embarque) {

    $.ajax({
        url: "../distrib/integracao/recebe_nfe.php",
        type: "GET",
        data: "q="+embarque.value,
        beforeSend: function  () {
            loading("show",embarque);
        },
        complete: function (retorno) {
//		console.log(retorno);
    if(retorno.responseText == '1') {
                var embarque2 = embarque.value ;
        $('#'+embarque2).parents('tr').hide('slow');
    } else {
         var embarque2 = embarque.value;
                $('#'+embarque2).html("<p>Pedido não faturado no SIGE</p>");
    }
    } 
    });
}
