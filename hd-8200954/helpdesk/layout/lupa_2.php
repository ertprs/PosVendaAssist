<?php
highlight_string(utf8_decode("<!-- ARQUIVOS PARA CARRREGAR JANELA MODAL ------->
	<script type='text/javascript' src='js/modal/ajax.js'></script>
	<script type='text/javascript' src='js/modal/modal-message.js'></script>
	<script type='text/javascript' src='js/modal/ajax-dynamic-contentt.js'></script>
	<script type='text/javascript' src='js/modal/main.js'></script>
	<link rel='stylesheet' href='css/modal/modal-message.css' type='text/css'>
	<!-- -------------------------------------------->


	<!-- ARQUIVOS PARA MONTAR TABELA DE PAGINA플O --->
	<script src='js/jquery.js' type='text/javascript'></script>
	<script src='js/table/jquery.dataTables.js' type='text/javascript'></script>
	<script src='js/table/demo_page.js' type='text/javascript'></script>
	<script src='js/table/jquery-ui-1.7.2.custom.js' type='text/javascript'></script>
	<!-- ----------------------------------------- -->


	<!--- CSS DA TABELA DE PAGINA플O ---------------->
	<link rel='stylesheet' href='css/table/demo_table_jui.css' type='text/css' />
	<link rel='stylesheet' href='css/table/jquery-ui-1.7.2.custom.css' type='text/css' />
	<!-- -------------------------------------------->	
	

<script type='text/javascript' charset='utf-8'>
		try{
			xmlhttp = new XMLHttpRequest();
		}catch(ee){
			try{
				xmlhttp = new ActiveXObject('Msxml2.XMLHTTP');
			}catch(e){
				try{
					xmlhttp = new ActiveXObject('Microsoft.XMLHTTP');
				}catch(E){
					xmlhttp = false;
				}
			}
		}


		function busca_dados_2(){
			displayMessage('modal_2.php','800','500');//MONTA A JANELA MODAL
		}
		
		//FUN플O DA PAGINA플O
		function fnFeaturesInit (){
			$('ul.limit_length>li').each( function(i) {
				if ( i > 10 ) {
					this.style.display = 'none';
				}
			} );
			
			$('ul.limit_length').append( '<li class='css_link'>Show more<\/li>' );
			$('ul.limit_length li.css_link').click( function () {
				$('ul.limit_length li').each( function(i) {
					if ( i > 5 ) {
						this.style.display = 'list-item';
					}
				} );
				$('ul.limit_length li.css_link').css( 'display', 'none' );
			} );
		}


		function closeMessage_2(){
			messageObj.close();//FECHA A JANELA MODAL
		}
		
		function retorna_dados_2(variave2){
			var objnome2 = document.getElementsByName('nome_2').length;//VERIFICA SE CAMPO EXISTE NO FORMULARIO
			if(variave2!='' && objnome2 =='1'){
				document.getElementById('nome_2').value = '';//LIMPA CAMPO
				document.getElementById('nome_2').value = variave2;//ADICIONA CONTEUDO
			}
			messageObj.close();//FECHA A JANELA MODAL
		}


		
	</script>
	

	<td>
		JANELA MODAL COM PAGINA플O<br>
		<input class='frm' type='text' name='nome_1' id='nome_1' size='17' maxlength='14' value=''/>
		<img src='btn_buscar5.gif' onclick='busca_dados_1('');'/>
	</td>
"));
?>