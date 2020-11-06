<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'includes/funcoes.php';
include 'autentica_admin.php';

$programa     = $_GET['programa'];

$sql          = "SELECT now()::date - interval '3 month' as data_inicial";
$res          = pg_query($con, $sql);
$data_inicial = pg_result($res, 0, data_inicial);

$sql          = "SELECT now()::date - interval '3 month' as data_final";
$res          = pg_query($con, $sql);
$data_final = pg_result($res, 0, data_final);

$admin_privilegios="gerencia";
$layout_menu = "gerencia";
$title = "RELATÓRIO DE ACESSO :: USUÁRIOS ADMIN";

include "cabecalho.php";
include "javascript_pesquisas.php";
include "javascript_calendario.php";

?>
<style>
	.rellinha0
	{
		background-color: #F1F4FA;
		border: solid 1px #d9e2ef;
	}
	.rellinha1
	{
		background-color: #F7F5F0;
		border: solid 1px #d9e2ef;
	}
	.rellink
	{
		text-decoration: none;
		font-weigth: normal;
		color: #000000;
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
		font: bold 16px "Arial";
		color:#FFFFFF;
		text-align:center;
	}
	.formulario{
		background-color:#D9E2EF;
		font:11px Arial;
	}
	.subtitulo{
		color: #7092BE
	}
	table.tabela tr td{
		font-family: verdana;
		font-size: 11px;
		border-collapse: collapse;
		border:1px solid #596d9b;
	}
</style>

<script language="javascript">

$(function(){
	$('#datainicial').datePicker({startDate:'01/01/2000'});
	$('#datafinal').datePicker({startDate:'01/01/2000'});
	$("#datainicial").maskedinput("99/99/9999");
	$("#datafinal").maskedinput("99/99/9999");
});

</script>

	<form method='get' name='frm_relatorio'>
	<table class='formulario' align='center' width="700" border="0">
		<? if(strlen($erro)>0){ ?>
			<tr class="msg_erro"><td colspan="4"><? echo $erro; ?></td></tr>
		<? } ?>
	</table>
	<br>
	
	</form>

<?php
$sql = "
SELECT tbl_admin.fabrica, tbl_admin.admin,tbl_admin.login, COUNT(log_programa.programa) AS acessos
FROM   tbl_admin
JOIN   log_programa ON tbl_admin.admin=log_programa.admin
WHERE  log_programa.data BETWEEN '$data_inicial' AND '$data_final'
		AND log_programa.programa = '$programa'
GROUP BY
tbl_admin.fabrica,
tbl_admin.admin,
tbl_admin.login
ORDER BY
tbl_admin.login	";
$sql = "SELECT tbl_admin.fabrica, tbl_admin.admin,tbl_admin.login, COUNT(log_programa.programa) AS acessos
FROM tbl_admin
JOIN log_programa ON tbl_admin.admin=log_programa.admin
WHERE log_programa.data BETWEEN '$data_inicial' AND '$data_final'
AND log_programa.programa = '$programa'
GROUP BY
tbl_admin.fabrica,
tbl_admin.admin,
tbl_admin.login
ORDER BY tbl_admin.fabrica, tbl_admin.login";
echo nl2br($sql);
@$res = pg_exec($con, $sql);

$colunas = 4;
	
if ($res){
	echo "
		<table class='tabela' border='0' align='center' width='700'>
			<tr class='titulo_coluna'>
				<td width=100>
					fabrica
				</td>
				<td width=100>
					Usuário
				</td>
				<td width=70>
					Acessos
				</td>
				<td width=100>
					Último Acesso
				</td>
			</tr>";

	for($i = 0; $i < pg_numrows($res); $i++){
		$linha = $i % 2;

		$fabrica   = pg_result($res, $i, fabrica);
		$admin     = pg_result($res, $i, admin);
		$login     = pg_result($res, $i, login);
		$acessos   = pg_result($res, $i, acessos);

		$sql = "SELECT MAX(data) FROM log_programa WHERE admin=$admin";
		$res_ultimo  = pg_exec($con, $sql);
		$ultima_data = pg_result($res_ultimo, 0, 0);

		$parts = explode(" ", $ultima_data);
		$parts[0] = implode("/", array_reverse(explode("-", $parts[0])));
		$parts[1] = explode(".", $parts[1]);
		$parts[1] = $parts[1][0];
		$ultima_data = $parts[0] . " " . $parts[1];

		echo "
		<tr class=rellinha$linha>
			<td>
				$fabrica
			</td>
			<td>
				$login
			</td>
			<td>
				$acessos
			</td>
			<td>
				$ultima_data
			</td>
		</tr>";
	}

	echo "
	</table>";
}else{
	echo "
	<div class=relerro>
	Nenhuma acesso encontrado para as datas informadas
	</div>";
}
?>

