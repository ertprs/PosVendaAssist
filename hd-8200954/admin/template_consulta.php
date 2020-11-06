<?php
	include "dbconfig.php";
    include "includes/dbconnect-inc.php";
    include "autentica_admin.php";
    include "funcoes.php";
	
	
	if($_POST){
		
		$data_inicial = $_POST["data_inicial"];		
		$data_final = $_POST["data_final"];
		$codigo_posto = $_POST["codigo_posto"];
		$posto_nome = $_POST["posto_nome"];

		/*Início Validação de Datas
		Este trecho da validação é para verificar se os campos de data foram preenchidos.
		Válido apenas para as telas que tornam obrigatório o preencimento das datas.
		==============Início================= */
		if(empty($data_inicial) OR empty($data_final)){
			$msg_erro = "Data Inválida";
		}
		//================Fim==================

		if(strlen($msg_erro)==0){
			list($di, $mi, $yi) = explode("/", $data_inicial);
			if(!checkdate($mi,$di,$yi)) 
				$msg_erro = "Data Inválida";
		}
		if(strlen($msg_erro)==0){
			list($df, $mf, $yf) = explode("/", $data_final);
			if(!checkdate($mf,$df,$yf)) 
				$msg_erro = "Data Inválida";
		}

		if(strlen($msg_erro)==0){
			$aux_data_inicial = "$yi-$mi-$di";
			$aux_data_final = "$yf-$mf-$df";
		}
		if(strlen($msg_erro)==0){
			if(strtotime($aux_data_final) < strtotime($aux_data_inicial)){
				$msg_erro = "Data Inválida.";
			}
		}
			
	/*O trecho abaixo, colocar apenas se o relatório não permitir pesquisa em um 
		intervalo maios que 30 dias.
	===================INICIO======================= */
	if(strlen($msg_erro)==0){
		if (strtotime($aux_data_inicial) < strtotime($aux_data_final . ' -1 month')) {
			$msg_erro = 'O intervalo entre as datas não pode ser maior que 1 mês.';
		}
	 }
//	===================FIM=======================

	
		//Fim Validação de Datas

		if(strlen($msg_erro)==0){
			if(strlen($codigo_posto)==0){
				$msg_erro = "Informe Código do Posto";
			}
			else{
				$codigo_posto = str_replace(".","",$codigo_posto);
				$codigo_posto = str_replace("-","",$codigo_posto);
				$codigo_posto = str_replace("/","",$codigo_posto);
				$codigo_posto = str_replace(" ","",$codigo_posto);
				$sql = "SELECT posto from tbl_posto where cnpj = '$codigo_posto'";
				$res = pg_exec($con,$sql);
				if(pg_numrows($res) == 0){
					$msg_erro = "Posto não Encontrado";
				}
			}
		}
	}

	
?>

<?php
	$title = "TEMPLADE DE TELA DE CONSULTA";
	include "cabecalho.php";	
    include "javascript_pesquisas.php";
	include "javascript_calendario.php";
?>
<script type='text/javascript' src='js/bibliotecaAJAX.js'></script>
<script type="text/javascript" charset="utf-8" src="js/jquery.alphanumeric.js"></script>
<script language="JavaScript">
    

$(document).ready(function(){
    $( "#data_inicial" ).datePicker({startDate : "01/01/2000"});
    $( "#data_inicial" ).maskedinput("99/99/9999");
	$( "#data_final" ).datePicker({startDate : "01/01/2000"});
    $( "#data_final" ).maskedinput("99/99/9999");
});

function fnc_pesquisa_posto2 (campo, campo2, tipo) {
	if (tipo == "codigo" ) {
		var xcampo = campo;
	}

	if (tipo == "nome" ) {
		var xcampo = campo2;
	}

	if (xcampo.value != "") {
		var url = "";
		url = "posto_pesquisa_2.php?campo=" + xcampo.value + "&tipo=" + tipo ;
		janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=600, height=400, top=18, left=0");
		janela.codigo  = campo;
		janela.nome    = campo2;
		janela.focus();
	}

	else{
		alert("Preencha toda ou parte da informação para realizar a pesquisa!");
	}
}


    $(function() {
        $("#codigo_posto").numeric();
    });


    function mascara_cnpj(campo, event) {


        var cnpj  = campo.value.length;
        var tecla = event.keyCode ? event.keyCode : event.which ? event.which : 
                                                                    event.charCode;


        if (tecla != 8 && tecla != 46) {


            if (cnpj == 2 || cnpj == 6) campo.value += '.';
            if (cnpj == 10) campo.value += '/';
            if (cnpj == 15) campo.value += '-';


        }


    }

    function formata_cpf_cnpj(campo, tipo) {


        var valor = campo.value;


        valor = valor.replace(".","");
        valor = valor.replace(".","");
        valor = valor.replace("-","");


        if (tipo == 2) {
            valor = valor.replace("/","");
        }


        if (valor.length == 11 && tipo == 1) {


            campo.value = valor.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/,"$1.$2.$3-$4");//CPF


        } else if (valor.length == 14 && tipo == 2) {


            campo.value = valor.replace(/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/,'$1.$2.$3/$4-$5');//CNPJ


        }


    }
</script>

<style type="text/css">
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
	font: bold 16px "Arial";
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
	font:bold 11px Arial;
	color: #FFFFFF;
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

.espaco tr td{
	padding-left:50px;
}

</style>
<br />
<div class="texto_avulso" style="width:700px;">Informe corretamente os dados para realizar a pesquisa.</div>
<br />

<form name='frm_pesquisa' action="<?php echo $PHP_SELF ;?>" method="post">
	<table align="center" class="formulario espaco" width="700" border="0">
		<?php
			if(strlen($msg_erro) > 0){
		?>
				<tr class="msg_erro" >
					<td colspan="4">
						<?php echo $msg_erro; ?>
					</td>
				</tr> 
		<?php
			}
		?>
		
		<tr class="titulo_tabela" >
			<td colspan="3">
				Parâmetros de Pesquisa
			</td>
		</tr>

		<tr>
			<td>
				CNPJ <br />
				<input type="text" name="codigo_posto" id="codigo_posto" 
				onkeypress="mascara_cnpj(this, event);" onfocus="formata_cpf_cnpj(this,2);" 
				class="frm" size="20" maxlength="18" value="<?php echo $codigo_posto?>" />
				<img src='imagens/lupa.png' border='0' align='absmiddle' style="cursor:pointer" onclick="javascript: fnc_pesquisa_posto2 (document.frm_pesquisa.codigo_posto,document.frm_pesquisa.posto_descricao,'codigo')" /> 
			</td>
			<td colspan="2">
				Descrição Posto <br />
				<input type="text" name="posto_nome" id="posto_nome" class="frm" value="<?php echo $posto_nome; ?>" size="50" maxlength="50" />&nbsp; <img src='imagens/lupa.png' border='0' align='absmiddle' style="cursor:pointer" onclick="javascript: fnc_pesquisa_posto2 (document.frm_pesquisa.codigo_posto,document.frm_pesquisa.posto_descricao,'nome')">
			</td>
		</tr>

		<tr>
			<td>
				Dada Inicial<br><input type="text" id="data_inicial" name="data_inicial" size="11" class="frm" value="<?php echo $data_inicial ?>"/> 
			</td>
			<td>
				Dada Final<br><input type="text" id="data_final" name="data_final" size="11" class="frm" value="<?php echo $data_final ?>" /> 
			</td>
			<td>
				<select name="estado" id="estado" style="width:120px; font-size:9px" class="frm">
					<option value=""   <?php if (strlen($estado) == 0)    echo " selected ";?> >TODOS OS ESTADOS</option>
					<option value="AC" <?php if ($estado == "AC") echo " selected "; ?>>AC - Acre</option>
					<option value="AL" <?php if ($estado == "AL") echo " selected "; ?>>AL - Alagoas</option>
					<option value="AM" <?php if ($estado == "AM") echo " selected "; ?>>AM - Amazonas</option>
					<option value="AP" <?php if ($estado == "AP") echo " selected "; ?>>AP - Amapá</option>
					<option value="BA" <?php if ($estado == "BA") echo " selected "; ?>>BA - Bahia</option>
					<option value="CE" <?php if ($estado == "CE") echo " selected "; ?>>CE - Ceará</option>
					<option value="DF" <?php if ($estado == "DF") echo " selected "; ?>>DF - Distrito Federal</option>
					<option value="ES" <?php if ($estado == "ES") echo " selected "; ?>>ES - Espírito Santo</option>
					<option value="GO" <?php if ($estado == "GO") echo " selected "; ?>>GO - Goiás</option>
					<option value="MA" <?php if ($estado == "MA") echo " selected "; ?>>MA - Maranhão</option>
					<option value="MG" <?php if ($estado == "MG") echo " selected "; ?>>MG - Minas Gerais</option>
					<option value="MS" <?php if ($estado == "MS") echo " selected "; ?>>MS - Mato Grosso do Sul</option>
					<option value="MT" <?php if ($estado == "MT") echo " selected "; ?>>MT - Mato Grosso</option>
					<option value="PA" <?php if ($estado == "PA") echo " selected "; ?>>PA - Pará</option>
					<option value="PB" <?php if ($estado == "PB") echo " selected "; ?>>PB - Paraíba</option>
					<option value="PE" <?php if ($estado == "PE") echo " selected "; ?>>PE - Pernambuco</option>
					<option value="PI" <?php if ($estado == "PI") echo " selected "; ?>>PI - Piauí</option>
					<option value="PR" <?php if ($estado == "PR") echo " selected "; ?>>PR - Paraná</option>
					<option value="RJ" <?php if ($estado == "RJ") echo " selected "; ?>>RJ - Rio de Janeiro</option>
					<option value="RN" <?php if ($estado == "RN") echo " selected "; ?>>RN - Rio Grande do Norte</option>
					<option value="RO" <?php if ($estado == "RO") echo " selected "; ?>>RO - Rondônia</option>
					<option value="RR" <?php if ($estado == "RR") echo " selected "; ?>>RR - Roraima</option>
					<option value="RS" <?php if ($estado == "RS") echo " selected "; ?>>RS - Rio Grande do Sul</option>
					<option value="SC" <?php if ($estado == "SC") echo " selected "; ?>>SC - Santa Catarina</option>
					<option value="SE" <?php if ($estado == "SE") echo " selected "; ?>>SE - Sergipe</option>
					<option value="SP" <?php if ($estado == "SP") echo " selected "; ?>>SP - São Paulo</option>
					<option value="TO" <?php if ($estado == "TO") echo " selected "; ?>>TO - Tocantins</option>
				</select> 
			</td>
		</tr>
		<tr><td colspan="3">&nbsp;</td></tr>
		<tr>
			<td colspan="3" align="center" style="padding-left:0px;">
				<input type="submit" value="Realizar Pesquisa">
			</td>
		</tr>
	</table>
</form>
	<br />
<?php
	if(strlen($msg_erro)==0){
		
		$sql = "SELECT";
?>
	<table width="700" align="center" cellspacing="1" class="tabela">
		<tr class="titulo_coluna">
			<td>Código</td>
			<td>Posto</td>
			<td>Estado</td>
		</tr>

		<?
		for($i=0;$i< 4;$i++){
			
			$cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";
		?>
		<tr bgcolor="<? echo $cor;?>">
			<td>teste</td>
			<td>teste</td>
			<td>teste</td>
		</tr>
		<?
			}
		?>
	</table>

<?php
	}
	include "rodape.php";
?>