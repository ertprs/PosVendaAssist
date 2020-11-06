<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";

$admin_privilegios="financeiro";
include "autentica_admin.php";

include "funcoes.php";

$erro = "";

if (strlen(trim($_GET["extrato"])) > 0)  $extrato = trim($_GET["extrato"]);
if (strlen(trim($_POST["extrato"])) > 0) $extrato = trim($_POST["extrato"]);
if (strlen(trim($_POST["acao"])) > 0)    $acao = trim($_POST["acao"]);

if ($acao == "ALTERAR") {
	$x_obs = trim($_POST["obs"]);
	
	if (strlen($x_obs) == 0) $erro .= " Preencha o campo Observação. ";
	
	if (strlen($erro) == 0) {
		$sql = "INSERT INTO tbl_extrato_status (
						fabrica ,
						extrato ,
						obs     ,
						data
					) VALUES (
						$login_fabrica,
						$extrato ,
						'$x_obs' ,
						current_timestamp
				);";
		$res = @pg_exec ($con,$sql);
		$erro = pg_errormessage($con);

		if (strlen($erro) == 0) {
			echo "<script language='JavaScript'>\n";
			echo "window.close();";
			echo "</script>";
		}
	}
}
?>

<html>

<head>

<title>Observação do Status do Extrato</title>

<style>
input {
	BORDER-RIGHT: #888888 1px solid;
	BORDER-TOP: #888888 1px solid;
	FONT-WEIGHT: bold;
	FONT-SIZE: 8pt;
	BORDER-LEFT: #888888 1px solid;
	BORDER-BOTTOM: #888888 1px solid;
	FONT-FAMILY: Verdana;
	BACKGROUND-COLOR: #f0f0f0
}
.erro {
  color: white;
  text-align: center;
  font: bold 12px Verdana, Arial, Helvetica, sans-serif;
  background-color: #FF0000;
}
.tabela {
    font-family: Verdana, Tahoma, Arial;
    font-size: 10pt;
    text-align: center;
}
</style>

</head>

<body>

<?
// CARREGA DADOS DO EXTRATO
$sql =	"SELECT TO_CHAR(tbl_extrato.data_geracao,'DD/MM/YYYY') AS data_geracao  ,
				TO_CHAR(tbl_extrato.aprovado,'DD/MM/YYYY')     AS data_aprovado ,
				tbl_posto_fabrica.codigo_posto                 AS posto_codigo  ,
				tbl_posto.nome                                 AS posto_nome
		FROM tbl_extrato
		JOIN tbl_posto          ON  tbl_posto.posto           = tbl_extrato.posto
		JOIN tbl_posto_fabrica  ON  tbl_extrato.posto         = tbl_posto_fabrica.posto
								AND tbl_posto_fabrica.fabrica = $login_fabrica
		WHERE tbl_extrato.extrato = $extrato;";
$res = pg_exec($con,$sql);

if (pg_numrows($res) == 1) {
	$data_geracao   = trim(pg_result($res,0,data_geracao));
	$data_aprovado  = trim(pg_result($res,0,data_aprovado));
	$posto_codigo   = trim(pg_result($res,0,posto_codigo));
	$posto_nome     = trim(pg_result($res,0,posto_nome));
	$posto_completo = $posto_codigo . " - " . $posto_nome;
}

if (strlen($erro) > 0) {
	$obs = trim($_POST["obs"]);
	echo "<div class='erro'>$erro</div>";
}
?>

<form name="frm_extrato" method="post" action="<?echo $PHP_SELF?>">

<input type="hidden" name="extrato" value="<?echo $extrato?>">
<input type="hidden" name="acao">

<table width='100%' border='0' cellspacing='1' cellpadding='1' class='tabela'>
	<tr>
		<td width='100%' colspan="3"><b>Posto</b></td>
	</tr>
	<tr>
		<td width='100%' colspan="3"><?echo substr($posto_completo,0,40)?></td>
	</tr>
	<tr>
		<td width='100%' colspan="3" height="5"></td>
	</tr>
	<tr>
		<td  width='50%'colspan="2"><b>Data Geração</b></td>
		<td width='50%'><b>Data Aprovado</b></td>
	</tr>
	<tr>
		<td width='50%' colspan="2"><?echo $data_geracao?></td>
		<td width='50%'><?echo $data_aprovado?></td>
	</tr>
	<tr>
		<td width='100%' colspan="3" height="5"></td>
	</tr>
	<tr>
		<td width='25%' valign="top"><b>Obs.:</b></td>
		<td width='75%' colspan="2"><textarea name="obs"><?echo $obs?></textarea></td>
	</tr>
</table>
<br>
<center>
<img border="0" src="imagens_admin/btn_confirmar.gif" style="cursor: hand;" onclick="javascript: if (document.frm_extrato.acao.value == '') { document.frm_extrato.acao.value='ALTERAR'; document.frm_extrato.submit(); }else{ alert('Aguarde Submissão...'); }" alt="Clique aqui para inserir a obs.">
</center>

</form>

</body>

</html>
