$(function(){

	/*AppStart*/
	loadingShow();
	$.ajax('crossDomainProxy.php',{
			data: {"apiLink": "http://api2.telecontrol.com.br/institucional/regiao/fabrica/117" }	
	}).done(function(res){
		
		if(res.length > 0){
			
			$(res).each(function(idx,obj){				
				$("#sel-regiao").append(makeOption(obj.descricao,obj.regiao));						
			});	
			loadingHide();		
		}else{

		}		
	});	

	$("#sel-regiao").change(function(){
		
		regiao = $(this).val();		
		if(regiao != ""){
			loadingShow();
			loadingState(regiao);
		}
	});

	$("#sel-estado").change(function(){
		
		estado = $(this).val();		
		if(estado != ""){
			loadingShow();
			loadingCities(estado);	
		}		
	});

	$("#inp-cidades").change(function(){
		if($(this).val() == ""){
			$(this).attr("ibge","");
		}
	});

	$("#btn-consultar").click(function(){
			obj = new Object();
			if($("#inp-cidades").val().length > 0){
				ibge = $("#inp-cidades").attr("ibge");
				obj = {
					indice: "cidade",
					val : ibge
				};
			}else if($("#sel-estado").val() != ""){
				estado = $("#sel-estado").val();
				obj = {
					indice: "uf",
					val: estado
				};
			}else if($("#sel-regiao").val() != ""){
				regiao = $("#sel-regiao").val();
				obj = {
						indice: "regiao",
						val: regiao
					};
			}else{

			}
			
			loadingShow();
			loadingTrainings(obj);
	});
});


function loadingState(regiao){	
	$("#inp-cidades").attr("ibge","");
	$("#inp-cidades").val("");
	$("#sel-estado").html("<option value=''>Selecione um Estado</option>");
	

	$.ajax('crossDomainProxy.php',{
		data: {"apiLink": "http://api2.telecontrol.com.br/institucional/estado/regiao/"+regiao }	
	}).done(function(res){
		
		
		if(res.length > 0){
			
			$(res).each(function(idx,obj){				
				$("#sel-estado").append(makeOption(obj.nome,obj.estado));						
			});			
			loadingHide();		
		}else{

		}		
	});		
}

function loadingCities(estado){
	$("#inp-cidades").attr("ibge","");
	$("#inp-cidades").val("");

	$.ajax('crossDomainProxy.php',{
		data: {"apiLink": "http://api2.telecontrol.com.br/institucional/cidade/uf/"+estado }	
	}).done(function(res){
		
		
		if(res.length > 0){
			

			source = new Array();
			 $(res).each(function(idx,obj){				
			 	source.push({label: obj.cidade,ibge: obj.ibge});
			 });			

			

			$( "#inp-cidades" ).autocomplete({		
		    	source: source,
		      	select : function(evento,ui){      	
			      	ibge = ui.item.ibge;
			      	$("#inp-cidades").attr("ibge",ibge);
		      }
		    });

			loadingHide();		
		}else{

		}		
	});			
}


function loadingTrainings(obj){


	var trTraining = "	<tr><td>{titulo}</td><td>{local}</td><td class='wid-column'>{data_ini}</td><td class='wid-column'>{data_fim}</td><td>{vagas}</td><td>{inscritos}</td><td>{ativo}</td></tr>";


	$("#result").fadeOut("fast");
	$("#training-results").html("");

	$.ajax('crossDomainProxy.php',{
		data: {"apiLink": "http://api2.telecontrol.com.br/institucional/treinamento/fabrica/117/"+obj.indice+"/"+obj.val }
	}).done(function(res){
		
		htmlData = "";
		if(res.length > 0){

			$(res).each(function(idx,obj){
				titulo = obj.titulo;
				localizacao = obj.localizacao;
				data_inicio = obj.data_inicio;
				data_fim = obj.data_fim;
				vagas = obj.vagas;
				inscritos = obj.inscritos;
				ativo = obj.ativo;

				if (ativo == true) {					
					var datatual=new Date;
					
					if (Date.parse(datatual) > Date.parse(data_fim)) {
						ativo = "Realizado";
					}else{
						if (inscritos > 0) {
							ativo = "Confirmado";
						}
						if (inscritos == 0) {
							ativo = "� Confirmar";
						}
					}
					
				}else{
					ativo = "Cancelado";
				}						
				

				formatDateIni = data_inicio.split('-');
				data_inicio = formatDateIni[2]+"-"+formatDateIni[1]+"-"+formatDateIni[0];
				formatDateFim = data_fim.split('-');
				data_fim  = formatDateFim[2]+"-"+formatDateFim[1]+"-"+formatDateFim[0];

				cpyTr = trTraining;
				cpyTr = cpyTr.replace("{titulo}",titulo);
				cpyTr = cpyTr.replace("{local}",localizacao);
				cpyTr = cpyTr.replace("{data_ini}",data_inicio);
				cpyTr = cpyTr.replace("{data_fim}",data_fim);
				cpyTr = cpyTr.replace("{vagas}",vagas);
				cpyTr = cpyTr.replace("{inscritos}",inscritos);
				cpyTr = cpyTr.replace("{ativo}",ativo);

				htmlData = htmlData+cpyTr;
			});

			$("#training-results").html(htmlData);

			$("#result").fadeIn("fast");
		}else{
			errorMessage("Nenhum treinamento encontrado");
		}		

		loadingHide();	
	});		
}

function errorMessage(message){
	var patternMessage = '<div  class="col-md-12"><div class="alert alert-danger" role="alert">{message}</div></div>';
	
	cpyMessage = patternMessage;
	cpyMessage = cpyMessage.replace('{message}',message);

	$('#error-message').html(cpyMessage);
	$('#error-message').show();
	setTimeout(function(){
		$('#error-message').fadeOut(200,function(){
			$('#error-message').html("");			
		});
	},5000);

}


var stackLoading = 0;
function loadingShow(){
	stackLoading = stackLoading + 1;
	if(stackLoading == 1){		
		$("#loading").fadeIn(100);
	}
}

function loadingHide(){
	stackLoading = stackLoading - 1;
	if(stackLoading <= 0){		
		$("#loading").hide();
	}
}








function makeOption(text,value){
	option = $("<option>",{
		value: value,
		text: text
	});

	return option;
}