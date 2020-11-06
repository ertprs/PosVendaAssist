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
	$status             = $_POST['status'];
	//coloca os zeros a esquerda para completar 10 digitos
	$documento = str_pad($documento, 10, "0", STR_PAD_LEFT);


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

	
	//transforma as datas para datas no formato do banco YYYY-MM-DD
	//$data_baixa = fnc_formata_data_pg ($data_baixa);
	//$vencimento = fnc_formata_data_pg ($vencimento);

	if (strlen ($vencimento) == 0) $msg_erro = "Especifique um vencimento";
	$vencimento = "'" . substr ($vencimento,6,4) . "-" . substr ($vencimento,3,2) . "-" . substr ($vencimento,0,2) . "'" ;

	if (strlen ($data_baixa) == 0) {
		$data_baixa = "null";
	}else{
		$data_baixa = "'" . substr ($data_baixa,6,4) . "-" . substr ($data_baixa,3,2) . "-" . substr ($data_baixa,0,2) . "'" ;
	}

	$valor_baixa = str_replace(".","",$valor_baixa);
	$valor_baixa = str_replace(",",".",$valor_baixa);
	$valor_baixa = trim(str_replace(".00","",$valor_baixa));
	$valor = str_replace(",",".",$valor);
	$valor = trim(str_replace(".00","",$valor));
	
	

	if($msg_erro == null){
		$sql = "UPDATE tbl_contas_receber 
					SET valor_recebido = $valor_baixa  ,
						recebimento    = $data_baixa   ,
						vencimento     = $vencimento   ,
						status         = '$status'
					WHERE tbl_contas_receber.documento = '$documento' ";
		
		$res = pg_exec ($con,$sql);
		if (strlen (pg_errormessage ($con)) > 0) {
			$msg_erro = pg_errormessage ($con);
		}

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
					TO_CHAR (recebimento,'DD/MM/YYYY') AS recebimento
			FROM tbl_contas_receber
			JOIN    tbl_posto ON tbl_posto.posto = tbl_contas_receber.posto
			WHERE documento = '$documento'";

	$res = pg_exec ($con,$sql);
	if (pg_numrows ($res) == 0) {
		echo "Documento não encontrado;";
	}else{
		$valor = pg_result ($res,0,valor);
		echo number_format($valor, '2', ',', '.');//tratamento para retornar o valor com duas casas decimais.
		echo ";";
		echo pg_result ($res,0,vencimento);
		echo ";";
		echo pg_result ($res,0,recebimento);
		echo ";";
		$valor_recebido = pg_result ($res,0,valor_recebido);//tratamento para retornar o valor com duas casas decimais
		echo number_format($valor_recebido, '2', ',', '.');
		echo ";";
		echo pg_result ($res,0,posto);
		echo ";";
		echo pg_result ($res,0,nome);
		echo ";";
		echo pg_result ($res,0,status);

	}
	exit;
}

$ajax = $_GET['ajax'];
if (strlen ($ajax) > 0) exit;

?>
<link rel="stylesheet" type="text/css" href="js/jquery.autocomplete.css" />
<script type="text/javascript" src="js/jquery.js"></script>
<script type='text/javascript' src='js/jquery.bgiframe.min.js'></script>
<script type='text/javascript' src='js/jquery.ajaxQueue.js'></script>
<script type='text/javascript' src='js/jquery.autocomplete.js'></script>
<script type="text/javascript" src="js/jquery.maskedinput.js"></script>
<script type='text/javascript' src='js/dimensions.js'></script>

<script language='javascript' src='js_tulio/bibliotecaAJAX.js'></script>
<!--     Nome do Posto        -->
<script language='javascript'>
function ajax_valor (documento , valor, vencimento , data_baixa, valor_baixa, codigo_posto, posto_nome, status) {
	var url = "baixa_manual.php?ajax=1&documento=" + escape(documento);
	var campos = $.ajax({ 
		type: "GET",
		url: url,
		cache: false,
		async: false
	}).responseText;;
	campos_array = campos.split(";");
	if (campos_array.length>1){
		var valor        = campos_array[0];
		var vencimento   = campos_array[1];
		var data_baixa   = campos_array[2];
		var valor_baixa  = campos_array[3];
		var codigo_posto = campos_array[4];
		var posto_nome   = campos_array[5];
		$('#valor').val( valor );
		$('#vencimento').val( vencimento );
		$('#data_baixa').val( data_baixa );
		$('#valor_baixa').val( valor_baixa );
		$('#codigo_posto').val( codigo_posto );
		$('#posto_nome').val( posto_nome );
	}
}
</script>

<HTML>
<HEAD>
<TITLE> Baixa Manual </TITLE>
<link type="text/css" rel="stylesheet" href="css/css.css">
</HEAD>

<? include 'menu.php' ?>


<!-- Erros=========================== -->
<? if($msg_erro <> null){ ?>
<br>
<table width='300'>
<tr>
	<td bgcolor='#FF0000' align='center'><? echo "<FONT COLOR=\"#FFFFFF\"><B>$msg_erro</B></FONT>"; ?></td>
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
		<input type='text' name='documento' size='10' onblur="javascript: ajax_valor (this.value, document.getElementById('valor'), document.getElementById('vencimento'), document.getElementById('data_baixa'), document.getElementById('valor_baixa'), document.getElementById('codigo_posto'), document.getElementById('posto_nome'), document.getElementById('status') ) " >
	</td>
	<td>
		<INPUT TYPE="text" size='10' NAME="valor" readonly="true" id='valor'>
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
		<input type='text' name='codigo_posto' size='10' id='codigo_posto'>
	</td>
	<td>
		<INPUT TYPE="text" size='40' NAME="posto_nome" id='posto_nome'>
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
		<INPUT TYPE="text" size='15' maxlength="15" NAME="data_baixa" id="data_baixa" value="<? echo date('d/m/Y'); ?>">
	</TD>
	<TD>
		<INPUT TYPE="text" size='10' NAME="valor_baixa" id="valor_baixa" >
	</TD>
</TR>

<TR>
	<TD colspan='2'>
		<B>Vencimento</B>
	</TD>
</TR>
<TR>
	<TD colspan='2'>
		<INPUT TYPE="text" NAME="vencimento" size='15' id='vencimento'>
	</TD>
</TR>

<TR>
	<TD colspan='2' align='center'>
		<B>COMENTÁRIO</B>
	</TD>
</TR>
<TR>
	<TD colspan='2' align='center'>
		<INPUT TYPE="text" size='70' NAME="status" id='status'>
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
