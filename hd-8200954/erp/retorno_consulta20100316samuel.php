<?
header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
header("Pragma: no-cache"); // HTTP/1.0

include '../dbconfig.php';
include '../includes/dbconnect-inc.php';
include 'ajax_cabecalho.php';
include 'autentica_usuario_empresa.php';
//include 'cabecalho_fabio.php';
include 'menu.php';

?>
	<form method='POST' action="<? $PHP_SELF ?>" name='frm_receber'  id='frm_receber'>
	<input type='hidden' name='baixar_conta' value='<? echo $conta_receber; ?>'>
	<table id='boleto' border="1" cellspacing="0" width="700" align='center' style="border-collapse: collapse; border: 1px solid #000000;">
	<tr>
		<td colspan="6" width="472"><strong><big>Contas a Receber</big></strong></td>
	</tr>
	<tr>
		<td width="80">
			<span class='topo'>Boleto</span><br>
			<input type='text' name='receber_documento' value'<? echo $receber_documento; ?>'>
		</td>
	</tr>
	
	<tr>
		<td width="80">
			<input type='submit' name='btn_acao' value='consultar'>
		</td>
	</tr>
	<tr>
		<td>
	<?
	$receber_documento = trim($_POST["receber_documento"]);
if(strlen($receber_documento) == 0){
	$receber_documento = trim($_GET["receber_documento"]);
}
//echo $receber_documento;
if(strlen($receber_documento) > 0){
	$sql = "SELECT identificacao_ocorrencia,
					descricao_identificacao_ocorrencia,
					motivo_ocorrencia_1,
					descricao_motivo_ocorrencia_1,
					motivo_ocorrencia_2,
					descricao_motivo_ocorrencia_2,
					motivo_ocorrencia_3,
					descricao_motivo_ocorrencia_3,
					motivo_ocorrencia_4,
					descricao_motivo_ocorrencia_4,
					motivo_ocorrencia_5,
					descricao_motivo_ocorrencia_5
					FROM tbl_contas_receber
					where documento = '$receber_documento';";
		//echo $sql;
	$res = pg_exec($con,$sql);
	if(pg_numrows($res)>0){
		$identificacao_ocorrencia           = trim(pg_result($res, 0, identificacao_ocorrencia));
		$descricao_identificacao_ocorrencia           = trim(pg_result($res, 0, descricao_identificacao_ocorrencia));
		$motivo_ocorrencia_1           = trim(pg_result($res, 0, motivo_ocorrencia_1));
		$descricao_motivo_ocorrencia_1           = trim(pg_result($res, 0, descricao_motivo_ocorrencia_1));
		$motivo_ocorrencia_2           = trim(pg_result($res, 0, motivo_ocorrencia_2));
		$descricao_motivo_ocorrencia_2           = trim(pg_result($res, 0, descricao_motivo_ocorrencia_2));
		$motivo_ocorrencia_3           = trim(pg_result($res, 0, motivo_ocorrencia_3));
		$descricao_motivo_ocorrencia_3           = trim(pg_result($res, 0, descricao_motivo_ocorrencia_3));
		$motivo_ocorrencia_4           = trim(pg_result($res, 0, motivo_ocorrencia_4));
		$descricao_motivo_ocorrencia_4           = trim(pg_result($res, 0, descricao_motivo_ocorrencia_4));
		$motivo_ocorrencia_5           = trim(pg_result($res, 0, motivo_ocorrencia_5));
		$descricao_motivo_ocorrencia_5           = trim(pg_result($res, 0, descricao_motivo_ocorrencia_5));
		echo "$identificacao_ocorrencia - 
					$descricao_identificacao_ocorrencia<br>
					$motivo_ocorrencia_1 - 
					$descricao_motivo_ocorrencia_1<br>
					$motivo_ocorrencia_2 - 
					$descricao_motivo_ocorrencia_2<br>
					$motivo_ocorrencia_3 - 
					$descricao_motivo_ocorrencia_3<br>
					$motivo_ocorrencia_4 - 
					$descricao_motivo_ocorrencia_4<br>
					$motivo_ocorrencia_5 - 
					$descricao_motivo_ocorrencia_5";
	}else{
		echo "não encontrado!";
	}
}

	?>
	</td>
	</tr>
	</table>

	</form>