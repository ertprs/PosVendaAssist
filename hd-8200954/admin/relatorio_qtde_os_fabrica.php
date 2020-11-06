<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="gerencia";
include 'autentica_admin.php';
include 'funcoes.php';

$layout_menu = "gerencia";
$title = "RELATÓRIO DE QUANTIDADES DE OS ANUALMENTE";

include 'cabecalho.php';

?>

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
	font: bold 14px "Arial";
	color:#FFFFFF;
	text-align:center;
}

.formulario{
	background-color:#D9E2EF;
	font:11px Arial;
	text-align:left;
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
</style>

<?
$sql = "SELECT * FROM tbl_qtde_os_fabrica WHERE fabrica = $login_fabrica ";
$res = pg_exec ($con,$sql);

if (pg_numrows($res) > 0) {

	echo "<div class='texto_avulso'>Relatório gerado mensalmente no 1° dia do mês, considerando todas as OS's pela data de abertura no sistema e as OS's pela data de fechamento no sistema e considerando apenas peças com serviço \"troca de peça gerando pedido\"</div>";
	echo "<br><table border='0' cellpadding='0' cellspacing='1' class='tabela' align='center' width='750'>";
	echo "<tr class='titulo_coluna' height='20'>";
	echo "<td >Mês</td>";
	echo "<td >Com Peças p/ Digitação</td>";
	echo "<td >Com Peças p/ Fechamento</td>";
	echo "<td >Sem Peças p/ Digitação</td>";
	echo "<td >Sem Peças p/ Fechamento</td>";
	echo "</tr>";

	for ($i=0; $i<pg_numrows($res); $i++){

		$mes                         = trim(pg_result($res,$i,mes))                        ;
		$ano                         = trim(pg_result($res,$i,ano))                        ;
		$qtde_os_peca_digitada       = trim(pg_result($res,$i,qtde_os_peca_digitada))      ;
		$qtde_os_peca_finalizada     = trim(pg_result($res,$i,qtde_os_peca_finalizada))    ;
		$qtde_os_sem_peca_digitada   = trim(pg_result($res,$i,qtde_os_sem_peca_digitada))  ;
		$qtde_os_sem_peca_finalizada = trim(pg_result($res,$i,qtde_os_sem_peca_finalizada));
		
		$cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";

		echo "<tr bgcolor='$cor'>";
		echo "<td>$mes/$ano</td>";
		echo "<td>$qtde_os_peca_digitada</td>";
		echo "<td>$qtde_os_peca_finalizada</td>";
		echo "<td>$qtde_os_sem_peca_digitada</td>";
		echo "<td>$qtde_os_sem_peca_finalizada</td>";
		echo "</tr>";
	}
	echo "</table>";
}

include 'rodape.php';
?>