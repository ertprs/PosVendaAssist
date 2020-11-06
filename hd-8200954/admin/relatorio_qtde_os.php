<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="financeiro";
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
$sql = "SELECT * FROM tbl_qtde_os ";
$res = pg_exec ($con,$sql);

if (pg_numrows($res) > 0) {

	echo "<div class='texto_avulso'><b>Relatório gerado mensalmente no 1° dia do mês, considerando todas as OS's pela data de abertura no sistema e as OS's pela data de fechamento no sistema e que estão em extrato.</b></div>";
	echo "<br><table border='0' cellpadding='0' cellspacing='1' class='tabela' align='center' width='700'>";
	echo "<tr class='titulo_coluna' height='20'>";
	echo "<td >Mês</td>";
	echo "<td >Qtde OS p/ Digitação</td>";
	echo "<td >Qtde Peças p/ Digitação</td>";
	echo "<td >Qtde OS p/ Finalização</td>";
	echo "<td >Qtde Peças p/ Finalização</td>";
	echo "</tr>";

	for ($i=0; $i<pg_numrows($res); $i++){

		$mes                  = trim(pg_result($res,$i,mes))                 ;
		$ano                  = trim(pg_result($res,$i,ano))                 ;
		$qtde_os_digitada     = trim(pg_result($res,$i,qtde_os_digitada))    ;
		$qtde_peca_digitada   = trim(pg_result($res,$i,qtde_peca_digitada))  ;
		$qtde_os_finalizada   = trim(pg_result($res,$i,qtde_os_finalizada))  ;
		$qtde_peca_finalizada = trim(pg_result($res,$i,qtde_peca_finalizada));
		
		if($cor=="#F1F4FA")$cor = '#F7F5F0';
		else               $cor = '#F1F4FA';

		echo "<tr bgcolor='$cor'>";
			echo "<td >$mes/$ano</td>";
			echo "<td>$qtde_os_digitada</td>";
			echo "<td>$qtde_peca_digitada</td>";
			echo "<td>$qtde_os_finalizada</td>";
			echo "<td>$qtde_peca_finalizada</td>";
		echo "</tr>";
	}
	echo "</table>";
}
	














include 'rodape.php';
?>
