<?

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

include "../funcoes.php";

$msg_erro = '';

$btn_baixa = $_POST['btn_baixa'];

if($btn_baixa == 'Baixar'){
	
	$codigo_posto       = $_POST['codigo_posto'];
	$posto_nome         = $_POST['posto_nome'];
	$documento          = $_POST['documento'];
	$vencimento         = $_POST['vencimento'];
	$valor_baixa        = $_POST['valor_baixa'];
	$valor        = $_POST['valor'];
	$status             = $_POST['status'];
	$data_baixa			= $_POST['data_baixa'];
	//coloca os zeros a esquerda para completar 10 digitos
	$documento = str_pad($documento, 10, "0", STR_PAD_LEFT);
	$aux_documento = $documento;
	
	#$sql = "SELECT documento FROM tbl_contas_receber WHERE documento = '$documento' AND recebimento IS NOT NULL";
	#$res = pg_query($con,$sql);


		//valida para que seja digitado o codigo do posto
	//	if ($codigo_posto == ''){
	//		$msg_erro = 'Favor digitar o codigo do posto';
	//	}

		//se o documento foi preenchido com zeros ow estiver nulo é por q nao foi digitado o documento
		if (($documento == '0000000000') OR ($documento == null)){
			$msg_erro = 'Favor digitar o nº do documento';
		}
		
		//Validacao para ver se o posto foi encontrado
		$posto_nome = trim($posto_nome);
		if ($posto_nome == 'Posto não cadastrado'){
			$msg_erro = 'Posto não encontrado.';
		}
		
		//validacao para ver se o documento foi encontrado
		$valor = trim($valor);
		if ($valor == 'Documento não encontrado'){
			$msg_erro = 'Documento não encontrado.';
		}
		
		if(empty($data_baixa)){
			$msg_erro = "Preencha a data da baixa";
		}

		if(strlen($msg_erro)==0){
			list($di, $mi, $yi) = explode("/", $data_baixa);
			if(!checkdate($mi,$di,$yi)){
				$msg_erro = "Data da baixa inválida";
			} else {
				$aux_data_baixa = "$yi-$mi-$di";
			}
		} 

		 if(strlen($msg_erro)==0){
			if(strtotime($aux_data_baixa) > strtotime('today')){
				$msg_erro = "Data baixa maior do que data atual";
			}
		}

		if(strlen($msg_erro)==0){
			if(empty($vencimento)){
				$msg_erro = "Especifique um vencimento";
			}
			list($di, $mi, $yi) = explode("/", $vencimento);
			if(!checkdate($mi,$di,$yi)){
				$msg_erro = "Data do vencimento inválida";
			}else {
				$aux_vencimento = "$yi-$mi-$di";
			}
		} 
		
		//transforma as datas para datas no formato do banco YYYY-MM-DD
		//$data_baixa = fnc_formata_data_pg ($data_baixa);
		//$vencimento = fnc_formata_data_pg ($vencimento);

	
			
		if($valor_baixa <= 0){
			$msg_erro = "Informe o valor da baixa";			
		} 

		if(empty($msg_erro)){
			$valor_baixa = str_replace(".","",$valor_baixa);
			$valor_baixa = str_replace(",",".",$valor_baixa);
			$valor = str_replace(",",".",$valor);
		}

		if($msg_erro == null){
			$sql = "SELECT nome FROM tbl_login_unico WHERE login_unico = $login_unico";
			$res = pg_query($con,$sql);

			if(pg_numrows($res) > 0){
				$nome = pg_result($res,0,'nome');

				$sql = "UPDATE tbl_contas_receber 
							SET valor_recebido = $valor_baixa  ,
								recebimento    = '$aux_data_baixa' ,
								vencimento     = '$aux_vencimento' ,
								status         = '$status'     ,
								obs            = 'Boleto baixado pelo usuário $nome'
							WHERE tbl_contas_receber.documento = '$documento' ";
				$res = pg_exec ($con,$sql);
				if (strlen (pg_errormessage ($con)) > 0) {
					$msg_erro = pg_errormessage ($con);
				}
			} else {
				$msg_erro = "Admin não encontrado";
			}

		}

		if(empty($msg_erro)){
			$msg = "Boleto baixado com sucesso!";
			echo "<script>setTimeout('ocultaMsg()', 3000);</script>";
		}
	
	
}


	$documento = $_GET['documento'];


if (strlen ($documento) > 0) {
	include "../ajax_cabecalho.php";
	
	//coloca os zeros a esquerda para completar 10 digitos
	$documento = str_pad($documento, 10, "0", STR_PAD_LEFT);

	$sql = "SELECT	tbl_contas_receber.documento      ,
					tbl_contas_receber.valor          ,
					tbl_contas_receber.valor_recebido ,
					tbl_contas_receber.posto          ,
					tbl_contas_receber.status         ,
					tbl_posto.nome                    ,
					TO_CHAR (vencimento,'DD/MM/YYYY') AS vencimento,
					TO_CHAR (recebimento,'DD/MM/YYYY') AS recebimento,
					obs
			FROM tbl_contas_receber
			JOIN    tbl_posto ON tbl_posto.posto = tbl_contas_receber.posto
			WHERE documento = '$documento'
			ORDER BY contas_receber DESC LIMIT 1";

	$res = pg_exec ($con,$sql);
	if (pg_numrows ($res) == 0) {
		echo "Documento não encontrado;";
	}else{
		$valor = pg_result ($res,0,valor);
		$valor = number_format($valor, '2', ',', '.');//tratamento para retornar o valor com duas casas decimais.
		$vencimento =  pg_result ($res,0,vencimento);
		$recebimento = pg_result ($res,0,recebimento);
		$valor_recebido = pg_result ($res,0,valor_recebido);//tratamento para retornar o valor com duas casas decimais
		$valor_recebido = number_format($valor_recebido, '2', ',', '.');
		$posto = pg_result ($res,0,posto);
		$nome = pg_result ($res,0,nome);
		$status = pg_result ($res,0,status);
		$obs = pg_result ($res,0,obs);

		echo "$valor|$vencimento|$recebimento|$valor_recebido|$posto|$nome|$status|$obs";

	} 
	exit;
}

$ajax = $_GET['ajax'];
if (strlen ($ajax) > 0) exit;

//include 'javascript_calendario.php';

?>
<link rel="stylesheet" type="text/css" href="js/jquery.autocomplete.css" />
<script type='text/javascript' src='js/jquery-1.6.1.min.js'></script>
<script type='text/javascript' src='js/jquery.bgiframe.min.js'></script>
<script type='text/javascript' src='js/jquery.ajaxQueue.js'></script>
<script type='text/javascript' src='js/jquery.autocomplete.js'></script>
<script type="text/javascript" src="js/jquery.maskedinput.js"></script>
<script type="text/javascript" src="../admin/js/jquery.maskmoney.js"></script>
<script type='text/javascript' src='js/dimensions.js'></script>

<script language='javascript' src='js_tulio/bibliotecaAJAX.js'></script>
<!--     Nome do Posto        -->
<script language='javascript'>
$(document).ready(function() {

	$("#data_baixa").maskedinput("99/99/9999");
	$("#vencimento").maskedinput("99/99/9999");
	
	$("#valor").maskMoney({showSymbol:true, symbol:"R$", decimal:",", thousands:".", precision:2, maxlength: 12});
	$("#valor_baixa").maskMoney({showSymbol:true, symbol:"R$", decimal:",", thousands:".", precision:2, maxlength: 12});
	

});

function ajax_valor () {
	var doc = $("#documento").val();
	$.ajax({
			url: "baixa_manual.php?ajax=1&documento="+escape(doc),
			cache: false,
			success: function(data) {
				campos_array = data.split('|');

				if (campos_array[0]!="") {
					var valor        = campos_array[0];
					var vencimento   = campos_array[1];
					var data_baixa   = campos_array[2];
					var valor_baixa  = campos_array[3];
					var codigo_posto = campos_array[4];
					var posto_nome   = campos_array[5];
					var status       = campos_array[6];
					var obs          = campos_array[7];
					$('#valor').val( valor );
					$('#vencimento').val( vencimento );
					$('#data_baixa').val( data_baixa );
					$('#valor_baixa').val( valor_baixa );
					$('#codigo_posto').val( codigo_posto );
					$('#posto_nome').val( posto_nome );
					$('#status').val( status );

					if(data_baixa != ""){
						document.getElementById('obs_linha').style.display = "table-row";
						$('#obs').css( "font-size","14px" );
						$('#obs').html( obs );
					} else {
						document.getElementById('obs_linha').style.display = "none";
					}
				} 

			}

		});	
	
	
}

function ocultaMsg(){
	document.getElementById('msg_sucesso').style.display = 'none';
	$('#documento').val('');
	$('#valor').val('');
	$('#vencimento').val('');
	$('#data_baixa').val('');
	$('#valor_baixa').val('');
	$('#codigo_posto').val('');
	$('#posto_nome').val('');
	$('#status').val('');
}


</script>

<HTML>
<HEAD>
<TITLE> Baixa Manual </TITLE>
<link type="text/css" rel="stylesheet" href="css/css.css">

<style type="text/css">
.sucesso{
    background-color:#008000;
    font: bold 14px "Arial";
    color:#FFFFFF;
    text-align:center;
}

.msg_erro{
    background-color:#FF0000;
    font: bold 16px "Arial";
    color:#FFFFFF;
    text-align:center;
}
</style>

</HEAD>

<? include 'menu.php' ?>


<!-- Erros=========================== -->
<? if(!empty($msg_erro)){ ?>
<br>
<table width='450' align="center">
<tr class="msg_erro">
	<td><? echo $msg_erro; ?></td>
</tr>
</table>
<? } ?>

<? if(!empty($msg)){ ?>
<br>
<table width='450' align="center" id="msg_sucesso">
<tr class="sucesso">
	<td><? echo $msg; ?></td>
</tr>
</table>
<? } ?>
<!-- Erros=========================== -->

<BODY>
<br>
<TABLE BORDER="0" align="center" cellspacing='2' cellpadding='0' style='font-family: verdana; font-size: 10px;'>
<TR>
	<TD colspan='2' height='20' bgcolor='eeeeee'>
		<b><center>BAIXA MANUAL</center></b>
	</TD>
</TR>

<TR>
<form name='frm_baixa' action='<? echo $PHP_SELF ?>' method='post'>
<input type='hidden' name='posto'>
<input type='hidden' name='documento'>
	<TD>
		<B>Nº Documento</B>
	</TD>
	<TD>
		<B>Valor</B>
	</TD>
</TR>
<TR>
	<td>
		<input type='text' id="documento" name='documento' size='10' value="<?php echo $aux_documento; ?>" >
		<input type="button" value="Consultar" onclick="javascript: ajax_valor() ">
	</td>
	<td>
		<INPUT TYPE="text" size='10' NAME="valor" readonly="true" id='valor' value="<?php echo $valor; ?>">
	</td>
</TR>
<TR>
	<td nowrap >
		<B>Código Posto</B> 
	</td>
	<td>
		<B>Nome do Posto</B>
	</td>
</TR>

<TR height='25'>
	<td>
		<input type='text' name='codigo_posto' size='10' id='codigo_posto' value="<?php echo $codigo_posto; ?>">
	</td>
	<td>
		<INPUT TYPE="text" size='40' NAME="posto_nome" id='posto_nome' value="<?php echo $posto_nome; ?>">
	</td>
</TR>
<TR>
	<TD>
		<B>Data Baixa</B>
	</TD>
	<TD>
		<B>Valor Baixa</B>
	</TD>
</TR>
<TR>
	<TD>
		<INPUT type="text" size='10' maxlength="15" name="data_baixa" id="data_baixa" value="<? echo $data_baixa; ?>">
	</TD>
	<TD>
		<INPUT TYPE="text" size='10' NAME="valor_baixa" id="valor_baixa" value="<?php echo $valor_baixa; ?>" >
	</TD>
</TR>

<TR>
	<TD colspan='2'>
		<B>Vencimento</B>
	</TD>
</TR>
<TR>
	<TD colspan='2'>
		<INPUT TYPE="text" NAME="vencimento" size='10' id='vencimento' value="<? echo $vencimento; ?>">
	</TD>
</TR>

<TR>
	<TD colspan='2' align='center'>
		<B>OBSERVAÇÃO</B>
	</TD>
</TR>
<TR>
	<TD colspan='2' align='center'>
		<INPUT TYPE="text" size='70' NAME="status" id='status' value="<? echo $status; ?>">
	</TD>
</TR>

<TR id="obs_linha" style="display:none;">
	<TD colspan='2' align='center'>
	<br />
		<span style="color:#006400;" id="obs"></span>
	</TD>
</TR>

<TR>
	<TD ALIGN="CENTER" colspan='2'><br><br>
		<INPUT TYPE="submit" NAME="btn_baixa" VALUE="Baixar">
	</TD>
</TR>
</form>
</TABLE>


</BODY>
</HTML>
