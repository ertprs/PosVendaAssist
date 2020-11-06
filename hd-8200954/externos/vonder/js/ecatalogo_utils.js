// JavaScript Document

$(document).ready(function() {
	
	/*TOP
	-----------------------------------------------*/
	$('#top').load('includes/include-top.html', function(){
		$('ul.sf-menu').superfish({			  
			delay:       50,                            	// one second delay on mouseout 
			animation:   {opacity:'show',height:'show'},  	// fade-in and slide-down animation 
			speed:       50,                          		// faster animation speed 
			autoArrows:  false,                           	// disable generation of arrow mark-up 
			dropShadows: false                            	// disable drop shadows					  
		});
		
		
		if($('#headerBox').hasClass('Atividades'))
		{
			$("li[title|='Atividades']").addClass('activeEsq');
		}
		if($('#headerBox').hasClass('Consultas'))
		{
			$("li[title|='Consultas']").addClass('active');
		}
		if($('#headerBox').hasClass('Cadastros'))
		{
			$("li[title|='Cadastros']").addClass('active');
		}
		if($('#headerBox').hasClass('Administracao'))
		{
			$("li[title|='Administracao']").addClass('active');
		}
		if($('#headerBox').hasClass('botaoAdUm'))
		{
			$("li[title|='botaoAdUm']").addClass('active');
		}
		if($('#headerBox').hasClass('botaoAdDois'))
		{
			$("li[title|='botaoAdDois']").addClass('active');
		}
		if($('#headerBox').hasClass('botaoAdTres'))
		{
			$("li[title|='botaoAdTres']").addClass('activeDir');
		}		
	});
	
	
	/*FOOTER
	-----------------------------------------------*/
	$('#footer').load('includes/include-footer.html');
	
	
	/*ARVORE
	-----------------------------------------------*/
	$('#arvore').append('<img src="imgs/bg-arvore-top.png" width="288" height="34" border="0" alt="" style="position:absolute; left:0px; top:0px;"/>').
				append('<img src="imgs/bg-arvore-bottom.png" width="288" height="72" border="0" alt="" style="position:absolute; left:0px; bottom:0px;" />');
	
	
	/*ABAS
	-----------------------------------------------*/
	$(function() {
		$( ".abas" ).tabs();
	});
	
	
	/*BOTOES COM ÍCONES
	-----------------------------------------------*/
	$(function() {
		$( ".botInserirObservacoes button" ).button({icons: {primary: "ui-icon-comment"},text: false});
		$( ".botAdicionarFavoritos button" ).button({icons: {primary: "ui-icon-star"},text: false});
		$( ".botEditarCodigo button" ).button({icons: {primary: "ui-icon-pencil"},text: false});
		$( ".botImprimir button" ).button({icons: {primary: "ui-icon-print"},text: false});
		$( ".botEnviarEmail button" ).button({icons: {primary: "ui-icon-mail-closed"},text: false});
		$( ".botAddImagemArquivo button" ).button({icons: {primary: "ui-icon-plus"},text: false});
		$( ".botExcluirImagemArquivo button" ).button({icons: {primary: "ui-icon-close"},text: false});
		$( ".botMoverCima button" ).button({icons: {primary: "ui-icon-triangle-1-n"},text: false});
		$( ".botMoverBaixo button" ).button({icons: {primary: "ui-icon-triangle-1-s"},text: false});
		$( ".botUploadItens button" ).button({icons: {primary: "ui-icon-arrowthickstop-1-n"},text: false});
		
		
		$( ".botImagem button" ).button({icons: {primary: "ui-icon-image"},text: false});
		$( ".botProxStatus button" ).button({icons: {primary: "ui-icon-circle-triangle-e"},text: false});
		
		
		$( ".botProdRepresentante button" ).button({icons: {primary: "ui-icon-representante"},text: false});
		
		
	});
	
	
	/*BOTOES SEM ÍCONES
	-----------------------------------------------*/
	$(function() {
			   
		$( "input:submit, a, button", ".botDeTexto" ).button();
		$( "a", ".botDeTexto" ).click(function() { return false; });
		
	});
	
	

});