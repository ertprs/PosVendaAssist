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
<style>
	table.tabela td {
		padding: 1px 5px;
		border-bottom: 1px solid #95bce2;
		font-family: Verdana;
		font-size: 10px;
	}
</style>
<form method='POST' action="<? $PHP_SELF ?>" name='frm_receber'  id='frm_receber'>
	<input type='hidden' name='baixar_conta' value='<? echo $conta_receber; ?>'>
	<table id='boleto' border="1" cellspacing="0" width="700" align='center' style="border-collapse: collapse; border: 1px solid #000000;">
	<tr>
		<td colspan="6" width="472"><strong><big>Contas a Receber</big></strong></td>
	</tr>
	<tr>
		<td >
			<span class='topo'>Boleto <font size='1Opx' color='red'>(informar o número do boleto, ou a data de retorno (ex: 20100312 que corresponde a 12/03/2010)</font></span><br>
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
		$sql = "SELECT documento, 
					to_char (vencimento,'DD/MM/YY') AS vencimento,
					to_char (recebimento,'DD/MM/YY') AS recebimento,
					valor,
					valor_recebido,
					identificacao_ocorrencia,
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
					where nome_arquivo_retorno like 'CB_$receber_documento%';";
		//echo $sql;
		$res = pg_exec($con,$sql);
		if(pg_numrows($res)>0){
			echo "<table class='tabela'>";
			echo "<font size='2'>";
			echo "<tr><td colspan='11' align='center'>Arquivo de retorno</td></tr>";
			echo "<tr><td>Boleto</td><td>Valor</td><td>Vencimento</td><td>Valor Recebido</td><td>Recebimento</td><td>Ocorrencia</td><td>Motivo 1</td><td>Motivo 2</td><td>Motivo 3</td><td>Motivo 4</td><td>Motivo 5</td></tr>";
			for ($x = 0 ; $x < pg_numrows($res) ; $x++){
				$documento  = trim(pg_result($res,$x,documento));
				$vencimento = trim(pg_result($res,$x,vencimento));
				$recebimento = trim(pg_result($res,$x,recebimento));
				$valor = number_format(pg_result($res,$x,valor),2,',','.');
				$valor_recebido = number_format(pg_result($res,$x,valor_recebido),2,',','.');

				$identificacao_ocorrencia = trim(pg_result($res, $x, identificacao_ocorrencia))." - ".trim(pg_result($res, $x, descricao_identificacao_ocorrencia));
				$motivo_ocorrencia_1      = trim(pg_result($res, $x, motivo_ocorrencia_1))." - ".trim(pg_result($res, $x, descricao_motivo_ocorrencia_1));
				$motivo_ocorrencia_2           = trim(pg_result($res, $x, motivo_ocorrencia_2))." - ".trim(pg_result($res, $x, descricao_motivo_ocorrencia_2));
				$motivo_ocorrencia_3           = trim(pg_result($res, $x, motivo_ocorrencia_3))." - ".trim(pg_result($res, $x, descricao_motivo_ocorrencia_3));
				$motivo_ocorrencia_4           = trim(pg_result($res, $x, motivo_ocorrencia_4))." - ".trim(pg_result($res, $x, descricao_motivo_ocorrencia_4));
				$motivo_ocorrencia_5           = trim(pg_result($res, $x, motivo_ocorrencia_5))." - ".trim(pg_result($res, $x, descricao_motivo_ocorrencia_5));
				echo "<tr><td nowrap>$documento</td><td nowrap align='right'>$valor</td><td nowrap>$vencimento</td><td nowrap align='right'>$valor_recebido</td><td nowrap>$recebimento</td><td nowrap>$identificacao_ocorrencia</td><td nowrap>$motivo_ocorrencia_1</td><td nowrap>$motivo_ocorrencia_2</td><td nowrap>$motivo_ocorrencia_3</td><td nowrap>$motivo_ocorrencia_4</td><td nowrap>$motivo_ocorrencia_5</td></tr>";
			}
			echo "</font>";
			echo "</table>";
		}else{
			echo "não encontrado!";
		}
	}
}

	?>
	</td>
	</tr>
	</table>

	</form>