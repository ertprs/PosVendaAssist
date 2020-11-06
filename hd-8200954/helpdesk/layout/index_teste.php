<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">
<HTML>
<center>
<HEAD>
<TITLE> Manual Layout Telecontrol </TITLE>
<style type="text/css">
.frm {
	background-color:#F0F0F0;
	border:1px solid #888888;
	font-family:Verdana;
	font-size:8pt;
	font-weight:bold;
}
.titulo_tabela{
	background-color:#596d9b;
	font: bold 14px "Arial";
	color:#FFFFFF;
	text-align:center;
}


.titulo_coluna{
	background-color:#596d9b;
	font: bold 11px "Arial";
	color:#FFFFFF;
	text-align:center;
}


.msg_erro{
	background-color:#FF0000;
	font: bold 14px "Arial";
	color:#FFFFFF;
	text-align:center;
}

.sucesso{
	background-color:#008000;
	font: bold 14px "Arial";
	color:#FFFFFF;
	text-align:center;
}

.formulario{
	background-color:#D9E2EF;
	font:11px Arial;
	text-align:left;
}

.subtitulo{

	background-color: #7092BE;
	font:bold 14px Arial;
	color: #FFFFFF;
	text-align:center;
}

table.tabela tr td{
	font-family: verdana;
	font-size: 11px;
	border-collapse: collapse;
	border:1px solid #596d9b;
}

.texto_avulso{
	font: 14px Arial; color: rgb(89, 109, 155);
	background-color: #d9e2ef;
	text-align: center;
	width:700px;
	margin: 0 auto;
	border-collapse: collapse;
	border:1px solid #596d9b;
}

.informacao{
	font: 14px Arial; color:rgb(89, 109, 155);
	background-color: #C7FBB5;
	text-align: center;
	width:700px;
	margin: 0 auto;
	border-collapse: collapse;
	border:1px solid #596d9b;
}

.espaco{padding:0 0 0 80px; }

</style>



<script type='text/javascript' src='http://www.telecontrol.com.br/assist/admin/js/jquery.js'></script>

<link rel="stylesheet" type="text/css" href="http://www.telecontrol.com.br/assist/admin/js/datePicker.v1.css" title="default" media="screen" />
<script type="text/javascript" src="http://www.telecontrol.com.br/assist/admin/js/datePicker.v1.js"></script>
<script type="text/javascript" src="http://www.telecontrol.com.br/assist/admin/js/jquery.maskedinput.js"></script>
<script type="text/javascript" src="http://www.telecontrol.com.br/assist/admin/js/jquery.alphanumeric.js"></script>
<script type="text/javascript" src="funcoes.js"></script>





<!-- ARQUIVOS PARA CARRREGAR JANELA MODAL ------->
<script type="text/javascript" src="http://www.telecontrol.com.br/assist/js/modal/ajax.js"></script>
<script type="text/javascript" src="http://www.telecontrol.com.br/assist/js/modal/modal-message.js"></script>
<script type="text/javascript" src="http://www.telecontrol.com.br/assist/js/modal/ajax-dynamic-contentt.js"></script>
<script type="text/javascript" src="http://www.telecontrol.com.br/assist/js/modal/main.js"></script>
<link rel="stylesheet" href="http://www.telecontrol.com.br/assist/css/modal/modal-message.css" type="text/css">
<!-- -------------------------------------------->

<!-- ARQUIVOS PARA MONTAR TABELA DE PAGINAÇÃO --->
<script src="http://www.telecontrol.com.br/assist/js/table/jquery.dataTables.js" type="text/javascript"></script>
<script src="http://www.telecontrol.com.br/assist/js/table/demo_page.js" type="text/javascript"></script>
<script src="http://www.telecontrol.com.br/assist/js/table/jquery-ui-1.7.2.custom.js" type="text/javascript"></script>
<!-- ----------------------------------------- -->

<!--- CSS DA TABELA DE PAGINAÇÃO ---------------->
<link rel="stylesheet" href="http://www.telecontrol.com.br/assist/css/table/demo_table_jui.css" type="text/css" />
<link rel="stylesheet" href="http://www.telecontrol.com.br/assist/css/table/jquery-ui-1.7.2.custom.css" type="text/css" />
<!-- -------------------------------------------->	



<script>













		try{
			xmlhttp = new XMLHttpRequest();
		}catch(ee){
			try{
				xmlhttp = new ActiveXObject("Msxml2.XMLHTTP");
			}catch(e){
				try{
					xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
				}catch(E){
					xmlhttp = false;
				}
			}
		}
	


		function busca_dados_1(){

			displayMessage("include/modal_3.php",'800','500');//MONTA A JANELA MODAL
				$(document).mousemove( function() {
				//---TABLE DE PAGINAÇÃO---
				fnFeaturesInit();
				$(document).mousemove(function() {
					oTable = $('#example').dataTable({
						"bJQueryUI": true,
						"sPaginationType": "full_numbers",
						"bPaginate": true,
						"iDisplayLength": 10,
						//RETIRA EVENTO MOUSEMOVE DA JANELA MODAL
						fnInitComplete:function() { 
							$(document).unbind("mousemove");//RETIRA EVENTOS MOUSEMOVE DA TABELA DE PAGINAÇÃO
						}
					});
				})
			});

		}

		function fnFeaturesInit (){
			$('ul.limit_length>li').each( function(i) {
				if ( i > 10 ) {
					this.style.display = 'none';
				}
			} );
			
			$('ul.limit_length').append( '<li class="css_link">Show more<\/li>' );
			$('ul.limit_length li.css_link').click( function () {
				$('ul.limit_length li').each( function(i) {
					if ( i > 5 ) {
						this.style.display = 'list-item';
					}
				} );
				$('ul.limit_length li.css_link').css( 'display', 'none' );
			} );
		}
		
		function closeMessage_1(){
			messageObj.close();//FECHA A JANELA MODAL
		}
		
		function verifica_campo_text(campo,variavel){
			var objnome1 = document.getElementsByName(campo).length;//VERIFICA SE CAMPO EXISTE NO FORMULARIO
			if(objnome1 =="1"){
				document.getElementById(campo).value = "";//LIMPA CAMPO
				document.getElementById(campo).value = variavel;//ADICIONA CONTEUDO
			}
		}

		function retorna_dados_1(variavel){
			verifica_campo_text('nome_1',variavel);
			messageObj.close();//FECHA A JANELA MODAL
		}




		function busca_dados_2(){
			displayMessage("include/modal_4.php",'800','500');//MONTA A JANELA MODAL
		}
		
		function closeMessage_2(){
			messageObj.close();//FECHA A JANELA MODAL
		}
		
		function retorna_dados_2(variave2){
			var objnome2 = document.getElementsByName('nome_2').length;//VERIFICA SE CAMPO EXISTE NO FORMULARIO
			if(variave2!="" && objnome2 =="1"){
				document.getElementById("nome_2").value = "";//LIMPA CAMPO
				document.getElementById("nome_2").value = variave2;//ADICIONA CONTEUDO
			}
			messageObj.close();//FECHA A JANELA MODAL
		}


</script>

</head>

<body>
		<div style='width:100%; margin-left:30px;'> <img src='cabecalho.jpg' width='97%'/></div> 
		<br />
			<!-- =============================Início Funções JavaScript==================================-->
			<div class="informacao" style="width:700px;">JavaScript<img id="seta_java" src="mais.bmp" onclick="javascript: mostraDiv('java');" style="float:right;"></div>
		
			<center>
			<div id="java" style="display:none;width:695px; " >
				<?php include "include/funcoes.php"; ?>
			</div>
			<center>
			<!-- =============================Fim Funções JavaScript==================================-->
			<br />

			<!-- =============================Início Includes==================================-->
			<div class="informacao" style="width:700px;">Includes<img id="seta_include" src="mais.bmp" onclick="javascript: mostraDiv('include');" style="float:right;"></div>
		
			<center>
			<div id="include" style="display:none;width:695px; text-align:left; border:solid 1px #7092BE; background:url(zebrado2.jpg);" >
				<?php include "include/includes.php"; ?>	
				
			</div>
			<center>
			<!-- =============================Fim Includes==================================-->

			<br />

			<!-- =============================Início Validação de Datas==================================-->
			<div class="informacao" style="width:700px;">Validação de Datas<img id="seta_valida" src="mais.bmp" onclick="javascript: mostraDiv('valida');" style="float:right;"></div>
		
			<center>
			<div id="valida" style="display:none;width:695px; text-align:left; border:solid 1px #7092BE; background:url(zebrado2.jpg);" >
					
				<?php
					include "include/valida_data.php";
				?>	
			</div>
			<center>
			<!-- =============================Fim Validação de Datas==================================-->


			<br />
		


		<!-- =============================Início Texto Avulso==================================-->
		<div class="texto_avulso" style="width:700px;">Texto Avulso<img id="seta_avulso" src="mais.bmp" onclick="javascript: mostraDiv('avulso');" style="float:right;"></div>
		
			<center>
			<div id="avulso" style="display:none;width:695px; text-align:left; border:solid 1px #7092BE; background:url(zebrado2.jpg);" >
				<?php include "include/texto_avulso.php"; ?>
			</div>
			<center>
		<!-- =============================Fim Texto Avulso==================================-->



		<br>



		<form id='frmLayout' name='frmLayout' action='#'>


		<!-- =============================Início Data Inválida==================================-->
		<table align="center" class="formulario" width="700" border="0">
			<tr><td colspan="4"><img id="seta_form" src="mais.bmp" onclick="javascript: mostraDiv('form');" style="float:right;"></td></tr>
			<tr class="msg_erro">
				<td colspan="4"> Data Inválida <img id="seta" src="mais.bmp" onclick="javascript: mostraDiv('erro1');" style="float:right;"></td>
			</tr>
		</table>

		<center>
			<div id="form" style="display:none;width:695px; text-align:left; border:solid 1px #7092BE; background:url(zebrado2.jpg);" >
					
					<?php
						highlight_string('
.formulario{
	background-color:#D9E2EF;
	font:11px Arial;
	text-align:left;
}


<table align="center" class="formulario" width="700" border="0">....</table>');
					
					?>	
			</div>
		<center>
		
			<center>
			<div id="erro1" style="display:none;width:695px; text-align:left; border:solid 1px #7092BE; background:url(zebrado2.jpg);" >
					
					<?php
						highlight_string('
.msg_erro{
	background-color:#FF0000;
	font: bold 16px "Arial";
	color:#FFFFFF;
	text-align:center;
}


<tr class="msg_erro" >
	<td colspan="4">
		<?php echo $msg_erro; ?>
	</td>
</tr>');
					
					?>	
			</div>
			<center>
			<!-- =============================Fim Data Inválida==================================-->




			<!-- =============================Início Gravado com sucesso==================================-->
			<table align="center" class="formulario" width="700" border="0">
			<tr class="sucesso">
				<td colspan="4">Gravado com Sucesso! <img id="seta1" src="mais.bmp" onclick="javascript: mostraDiv('sucesso1');" style="float:right;"></td>
			</tr>
			</table>
						<center>
			<div id="sucesso1" style="display:none;width:695px; text-align:left; border:solid 1px #7092BE; background:url(zebrado2.jpg); ">
					
					<?php
						highlight_string('
.sucesso{
	background-color:#008000;
	font: bold 14px "Arial";
	color:#FFFFFF;
	text-align:center;
}


<tr class="sucesso" >
	<td colspan="4">
		<?php echo $msg; ?>
	</td>
</tr>');
					
					?>	
			</div>
			<center>
			<!-- =============================Fim Gravado com sucesso==================================-->



			
			<!-- =============================Início Título Formulário==================================-->
			<table align="center" class="formulario" width="700" border="0">
			<tr class="titulo_tabela">
				<td colspan="4">Parâmetros de Pesquisa <img id="seta2" src="mais.bmp" onclick="javascript: mostraDiv('titulo_tabela1');" style="float:right;"> </td>
			</tr>
			</table>
						<center>
			<div id="titulo_tabela1" style="display:none;width:695px; text-align:left; border:solid 1px #7092BE; background:url(zebrado2.jpg); ">
			
					<?php
						highlight_string('
.titulo_tabela{
	background-color:#596d9b;
	font: bold 14px "Arial";
	color:#FFFFFF;
	text-align:center;
}


<tr class="titulo_tabela" >
	<td colspan="4">
		Parâmetros de Pesquisa
	</td>
</tr>');
					
					?>	
			</div>
			</center>
			<!-- =============================Fim Título formulário==================================-->	

			<table align="center" class="formulario" width="700" border="0">
			<tr bgcolor='#D9E2EF'>
					<td bgcolor='#D9E2EF' width='180' class='espaco'>
						Data Inicial <br />
						<input type="text" id='data_inicial' name="data_inicial" class="frm" style='width: 100px'>
						<img id="seta_data" src="mais.bmp" onclick="javascript: mostraDiv('campo_data');" />
					</td>

					<td bgcolor='#D9E2EF'>
						Data Final <br />
						<input type="text" id='data_final' name="data_final" class="frm" style='width: 100px'>
					</td>
				<tr>
			</table>
				<center>
			<div id="campo_data" style="display:none;width:695px; text-align:left; border:solid 1px #7092BE; background:url(zebrado2.jpg); ">
				
				<?php
					highlight_string('
$().ready(function(){
	$( "#data_inicial" ).datePicker({startDate : "01/01/2000"});
	$( "#data_inicial" ).maskedinput("99/99/9999");
});

<?php include "javascript_calendario.php";?>
					
<input type="text" id=" data_inicial" name="data_inicial" class="frm" />');
				?>
					
			</div>
			</center>

			<!-- =============================Início Subtítulo Formulário==================================-->
			<table align="center" class="formulario" width="700" border="0">
			<tr class="subtitulo">
				<td colspan="4">Informações do Posto <img id="seta3" src="mais.bmp" onclick="javascript: mostraDiv('subtitulo1');" style="float:right;" /></td>
			</tr>
			</table>
			<center>
			<div id="subtitulo1" style="display:none;width:695px; text-align:left; border:solid 1px #7092BE; background:url(zebrado2.jpg); ">
					
					<?php
						highlight_string('
.subtitulo{
	background-color: #7092BE;
	font:bold 11px Arial;
	color: #FFFFFF;
}


<tr class="subtitulo" >
	<td colspan="4">
		Informações do Posto
	</td>
</tr>');
					
					?>	
			</div>
			<center>
			<!-- =============================Fim Subtítulo Formulário==================================-->




			
			<!-- =============================Início Campos do Formulário==================================-->

				<table align="center" class="formulario" width="700" border="0">
				<tr>
					<td width='180' class='espaco'>
						Código do Posto <br />
						<input type="text" name="cod_posto" class="frm">
						<img src='lupa.png' style="cursor:pointer;"/>
						<img id="seta_codigo" src="mais.bmp" onclick="javascript: mostraDiv('codigo');" />
					</td>
					<td colspan="3">
						Nome do Posto <br />
						<input type="text" name="nome_posto" class="frm" size="50">
						<img src='lupa.png' style="cursor:pointer;"/>
					</td>
				</tr>
			</table>
			<center>
				<div id="codigo" style="display:none;width:695px; text-align:left; border:solid 1px #7092BE; background:url(zebrado2.jpg);">
					<?php include "include/campo_posto.php"; ?>
				</div>
			</center>

			<table align="center" class="formulario" width="700" border="0">
		
				<tr class='subtitulo'><td colspan='3'>Informações do Consumidor</td></tr>
				<tr>
					<td bgcolor='#D9E2EF' class='espaco'>
						CPF <br />
						<input class="frm" type="text" name="cpf_consumidor" id="cpf_consumidor" 
						onkeypress="mascara_cpf(this, event);" size="17" maxlength="14" value="<?php echo 
						$cpf_consumidor ?>" onfocus="formata_cpf_cnpj(this,1)" />

						<img src='lupa.png' border='0' align='absmiddle' 
						onclick="fnc_pesquisa_consumidor(document.frm_rel.cpf_consumidor,'cpf')" 
						style='cursor: pointer' />&nbsp;
						<img id="seta_cpf" src="mais.bmp" onclick="javascript: mostraDiv('campo_cpf');" />
						
					</td>
				
					<td bgcolor='#D9E2EF' colspan='2'>
						Nome Consumidor <br />
						<input class="frm" type="text" name="nome_consumidor" id="nome_consumidor" size="50" 
						maxlength="60" value="<?php echo $nome_consumidor ?>" 
						onkeyup="somenteMaiusculaSemAcento(this);this.value = this.value.toUpperCase();" />

						<img src='lupa.png' border='0' align='absmiddle' 
						onclick="fnc_pesquisa_consumidor(document.frm_rel.nome_consumidor, 'nome')" 
						style='cursor: pointer' />
					</td>
				</tr>
		
				<tr>

					<td bgcolor='#D9E2EF' class='espaco'>
						CEP <br />
						<input type="text" name="cep" id="cep" size="10" class="frm">
						<img id="seta_cep" src="mais.bmp" onclick="javascript: mostraDiv('campo_cep');" />
					</td>

					<td bgcolor='#D9E2EF' width='230'>
						Estado <br />
						<?php
						$array_estado = array("AC"=>"AC - Acre","AL"=>"AL - Alagoas","AM"=>"AM - Amazonas","AP"=>"AP - Amapá","BA"=>"BA - Bahia", "CE"=>"CE - Ceará","DF"=>"DF - Distrito Federal","ES"=>"ES - Espírito Santo", "GO"=>"GO - Goiás","MA"=>"MA - Maranhão","MG"=>"MG - Minas Gerais","MS"=>"MS - Mato Grosso do Sul","MT"=>"MT - Mato Grosso", "PA"=>"PA - Pará","PB"=>"PB - Paraíba","PE"=>"PE - Pernambuco","PI"=>"PI - Piauí","PR"=>"PR - Paraná","RJ"=>"RJ - Rio de Janeiro",
							"RN"=>"RN - Rio Grande do Norte","RO"=>"RO - Rondônia","RR"=>"RR - Roraima","RS"=>"RS - Rio Grande do Sul", "SC"=>"SC - Santa Catarina","SE"=>"SE - Sergipe","SP"=>"SP - São Paulo","TO"=>"TO - Tocantins");
						?>
						<select name="estado" class="frm" id="estado"><?php
							foreach ($array_estado as $k => $v) {
							echo '<option value="'.$k.'"'.($estado == $k ? ' selected="selected"' : '').'>'.ucwords($v)."</option>\n";
							}?>
						</select>
						<img id="seta_estado" src="mais.bmp" onclick="javascript: mostraDiv('campos');">
					</td>
				
					<td>
						Meses <br />
						<?php
						$mes_extenso = array('01' => "janeiro", '02' => "fevereiro", '03' => "março", '04' => "abril", '05' => "maio", '06' => "junho", '07' => "julho", '08' => "agosto", '09' => "setembro", '10' => "outubro", '11' => "novembro", '12' => "dezembro");
						?>
						<select name="mes" class="frm" id="mes"><?php
							foreach ($mes_extenso as $k => $v) {
							echo '<option value="'.$k.'"'.($mes == $k ? ' selected="selected"' : '').'>'.ucwords($v)."</option>\n";
							}?>
						</select>
						&nbsp;
						<img id="seta_mes" src="mais.bmp" onclick="javascript: mostraDiv('campo_mes');" />
					</td>
				</tr>
			</table>

		
			<center>
			<div id="campo_cpf" style="display:none;width:695px; text-align:left; border:solid 1px #7092BE; background:url(zebrado2.jpg); ">
				<?php include "include/campo_cpf.php"; ?>
			</div>
			</center>

			<center>
			<div id="campo_cep" style="display:none;width:695px; text-align:left; border:solid 1px #7092BE; background:url(zebrado2.jpg); font: 12px Arial;">
					
					<?php
						highlight_string('
$().ready(function(){
	$( "#cep" ).maskedinput("99.999-999")
});


<input type="text" name="cep" id="cep" size="10" class="frm" />');
					
					?>	
			</div>
			</center>

			<center>
			<div id="campos" style="display:none;width:695px; text-align:left; border:solid 1px #7092BE; background:url(zebrado2.jpg);font:11px Arial;" >
				<?php include "include/estado.php"; ?>
			</div>
			</center>

			<center>
			<div id="campo_mes" style="display:none;width:695px; text-align:left; border:solid 1px #7092BE; background:url(zebrado2.jpg); ">
				<?php include "include/campo_mes.php"; ?>
			</div>
			</center>


		


		

			<table align="center" class="formulario" width="700" border="0">
				<tr class='subtitulo'><td colspan='2'>Informações da Revenda</td></tr>
				<tr>
					<td nowrap width='180' class='espaco'>
						CNPJ <br />
						<input type="text" name="cnpj_revenda" id="cnpj_revenda" onkeypress="mascara_cnpj(this, event);" onfocus="formata_cpf_cnpj(this,2);" class="frm" size="20" maxlength="18" value="<?php echo $cnpj_revenda?>" />
						<img src="lupa.png" border="0" style="cursor:pointer" align="absmiddle" onclick="fnc_revenda_pesquisa(document.frm_rel.nome_revenda, document.frm_rel.cnpj_revenda, \'cnpj\')" />
						<img id="seta_cnpj" src="mais.bmp" onclick="javascript: mostraDiv('campo_cnpj');">
					</td>
					
					<td>
						Nome Revenda <br />
						<input type="text" name="nome_revenda" id="nome_revenda" size="50" maxlength="60" value="<?php echo $nome_revenda ?>" class='frm' />
						<img src="lupa.png" border="0" style="cursor:pointer" align="absmiddle" onclick="fnc_revenda_pesquisa(document.frm_rel.nome_revenda, document.frm_rel.cnpj_revenda, 'nome')" />
					</td>
				</tr>

			</table>




		<center>
			<div id="campo_cnpj" style="display:none;width:695px; text-align:left; border:solid 1px #7092BE; background:url(zebrado2.jpg); ">
				<?php include "include/campo_cnpj.php"; ?>
			</div>
			</center>

		
			<table width='700px' class='formulario' align='center'>
				<tr align='center'>
					<td style="padding:20px 0 20px 0;"><input type="button" value="Pesquisar"/>
					<img id="seta_pesquisa" src="mais.bmp" onclick="javascript: mostraDiv('pesquisar');"></td>
				</tr>
			</table>
				<center>
				<div id="pesquisar" style="display:none;width:695px; text-align:left; border:solid 1px #7092BE; background:url(zebrado2.jpg); font:11px Arial;">
					<?php
						highlight_string('
						Todos os botões do sistema deverão seguir este padrão.

						<input type="button" value="Pesquisas" onclick=""/>');
					?>	
				</div>
			<center>



		<table align="center" class="formulario" width="700" border="0">
			<tr class='subtitulo'><td colspan='2'>Lupa Busca</td></tr>
			<tr>
				<td nowrap width='180' class='espaco'>
					Posto <br/>
					<input class="frm" type="text" name="nome_1" id="nome_1" size="17" maxlength="14" value=""/>
					<img src="btn_buscar5.gif" style="cursor:pointer" align="absmiddle" onclick="busca_dados_1('');"/>
					<img id="seta_pesquisa" src="mais.bmp" onclick="javascript: mostraDiv('lupa_1');"><br>
					Janela Modal <img id="seta_pesquisa" src="mais.bmp" onclick="javascript: mostraDiv('lupa_1_1');">
				</td>

				<td>
					Posto <br/>
					<input class="frm" type="text" name="nome_1" id="nome_1" size="17" maxlength="14" value=""/>
					<img src="btn_buscar5.gif" style="cursor:pointer" align="absmiddle" onclick="busca_dados_2('');"/>
					<img id="seta_pesquisa" src="mais.bmp" onclick="javascript: mostraDiv('lupa_2');"><br>
					Janela Modal <img id="seta_pesquisa" src="mais.bmp" onclick="javascript: mostraDiv('lupa_2_1');">
				</td>
			</tr>
			
			<tr>
				<td>
					&nbsp;
					<br>
				</td>
			</tr>
		</table>
		<center>
			<div id="lupa_1" style="display:none;width:695px; text-align:left; border:solid 1px #7092BE; background:url(zebrado2.jpg); font:12px Arial;">
				<?php include "include/lupa3.php";?>
			</div>
			<div id="lupa_2" style="display:none;width:695px; text-align:left; border:solid 1px #7092BE; background:url(zebrado2.jpg); font:12px Arial;">
				<?php include "include/lupa4.php";?>
			</div>
			<div id="lupa_1_1" style="display:none;width:695px; text-align:left; border:solid 1px #7092BE; background:url(zebrado2.jpg); font:12px Arial;">
				<?php include "include/lupa5.php";?>
			</div>
			<div id="lupa_2_1" style="display:none;width:695px; text-align:left; border:solid 1px #7092BE; background:url(zebrado2.jpg); font:12px Arial;">
				<?php include "include/lupa6.php";?>
			</div>
		<center>


	<!-- =============================fim Campos do Formulários==================================-->



	<!-- =============================Início Tabela de Resultado==================================-->
		<table align="center" class="tabela" width="700" cellspacing="1">
			<tr class="titulo_coluna">
				<td>Código Posto</td>
				<td width="500">Nome do Posto
					<img id="seta_tabela" src="mais.bmp" onclick="javascript: mostraDiv('tab_result');">
				</td>
				<td> <label for='titulo_coluna'> Banco</label></td>
			</tr>
			<tr bgcolor="#F7F5F0">
				<td>000</td>
				<td width="500">Posto Número 000001</td>
				<td>001</td>
			</tr>
			<tr bgcolor="#F1F4FA">
				<td>003</td>
				<td>Posto Número 000002</td>
				<td>004</td>
			</tr>
			
			<tr bgcolor="#F7F5F0">
				<td>006</td>
				<td>Posto Número 000003</td>
				<td>007</td>
			</tr>
			<tr bgcolor="#F1F4FA">
				<td align="left"><input type="text" readonly="readonly" style="text-align: left; font-weight: normal; border : #F7F5F0; background-color: #F1F4FA; color : black " class="titulo_coluna" value="005" /></td>
				<td>Posto Número 000004</td>
				<td>009</td>
			</tr>
		</table>
		
		<center>
			<div id="tab_result" style="display:none;width:695px; text-align:left; border:solid 1px #7092BE; background:url(zebrado2.jpg);">
				<?php include "include/tabela.php"; ?>
			</div>
		<center>		

	<!-- =============================Fim Tabela de Resultado==================================-->
		</form>
</body>
</html>
