<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="gerencia";
include 'autentica_admin.php';

$msg_erro = "";

$layout_menu = "gerencia";
$title = "RELATÓRIO DE P. A. QUE PARTICIPARAM DO QUESTIONÁRIO";

include "cabecalho.php";

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
table.tabela tr td{
    font-family: verdana;
    font-size: 11px;
    border-collapse: collapse;
    border:1px solid #596d9b;
}
.formulario{
	background-color:#D9E2EF;
	font:11px Arial;
	text-align:left;
}
.espaco td{
	padding:10px 0 10px;
}
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
.texto_avulso{
    font: 14px Arial; color: rgb(89, 109, 155);
    background-color: #d9e2ef;
    text-align: center;
    width:700px;
    margin: 0 auto;
}

</style>

<table align='center' border='0' cellspacing='1' cellpadding='0' width="700" class="tabela">
	<tr class='titulo_coluna'>
		<td rowspan="2">&nbsp;</td>
		<td rowspan="2" align='center'>Total</td>
		<td align='center' colspan="2">Questão 1</td>
		<td align='center' colspan="2">Questão 2</td>
	</tr>
	<tr class='titulo_coluna'>
		<td align='center'>Não</td>
		<td align='center'>Sim</td>
		<td align='center'>Não</td>
		<td align='center'>Sim</td>
	</tr>
	<tr bgcolor="#F1F4FA">
		<td align='center'>P. A. que responderam</td>
		<td align='center'>
			<? // Todos que responderam os questionário
			$sql = "select count(posto) AS postos_que_responderam from britania_fama where ja_chegaram is not null and tem_parados is not null;";
			$res = pg_exec($con,$sql);
			echo "<a href='$PHP_SELF?acao=respondeu' title='Clique aqui para consultar os P. A. que responderam'>".pg_result($res,0,postos_que_responderam)."</a>";
			?>
		</td>
		<td align='center'>
			<? // Todos que responderam não na questão 1
			$sql = "select count(ja_chegaram) as total_ja_chegaram_nao from britania_fama where ja_chegaram is false;";
			$res = pg_exec($con,$sql);
			echo pg_result($res,0,total_ja_chegaram_nao);
			?>
		</td>
		<td align='center'>
			<? // Todos que responderam sim e a quantidade total da questão 1
			$sql = "select count(ja_chegaram) as total_ja_chegaram_sim, sum(quantos_chegaram) as total_quantos_chegaram from britania_fama where ja_chegaram is true;";
			$res = pg_exec($con,$sql);
			echo pg_result($res,0,total_ja_chegaram_sim)." (".pg_result($res,0,total_quantos_chegaram).")";
			?>
		</td>
		<td align='center'>
			<? // Todos que responderam não na questão 2
			$sql = "select count(tem_parados) as total_tem_parados_nao from britania_fama where tem_parados is false;";
			$res = pg_exec($con,$sql);
			echo pg_result($res,0,total_tem_parados_nao);
			?>
		</td>
		<td align='center'>
			<? // Todos que responderam sim e a quantidade total da questão 2
			$sql = "select count(tem_parados) as total_tem_parados_sim, sum(quantos_parados) as total_quantos_parados from britania_fama where tem_parados is true;";
			$res = pg_exec($con,$sql);
			echo pg_result($res,0,total_tem_parados_sim)." (".pg_result($res,0,total_quantos_parados).")";
			?>
		</td>
	</tr>
	<tr  bgcolor="#F7F5F0">
		<td align='center'>P. A. que não responderam</td>
		<td align='center'>
			<? // Todos que não responderam os questionário
			$sql = "select count(posto) AS postos_que_nao_responderam from britania_fama where ja_chegaram isnull and tem_parados isnull;";
			$res = pg_exec($con,$sql);
			echo "<a href='$PHP_SELF?acao=n_respondeu' title='Clique aqui para consultar os P. A. que não responderam'>".pg_result($res,0,postos_que_nao_responderam)."</a>";
			?>
		</td>
		<td colspan="4">&nbsp;</td>
	</tr>
</table>
<br>

<?
if ($acao == 'respondeu') {?>
<table align='center' border='0' cellspacing='1' cellpadding='4' class='tabela' width='700'>
	<tr class='titulo_tabela'>
		<td align='center' colspan='3'>P. A. que Responderam o Questionário</td>
	</tr>
	<tr class='titulo_coluna'>
		<td>Posto</td>
		<td align='center'>Já chegou ao seu Posto Autorizado<br> para conserto o DVD Fama ou DVD Game?</td>
		<td align='center'>Há DVDs Fama ou Game<br> aguardando solução?</td>
	</tr>
<?
	$sql =	"SELECT britania_fama.ja_chegaram, britania_fama.quantos_chegaram, britania_fama.tem_parados, britania_fama.quantos_parados, tbl_posto.nome, tbl_posto_fabrica.codigo_posto
				FROM britania_fama
				JOIN tbl_posto USING (posto)
				JOIN tbl_posto_fabrica USING (posto)
				where britania_fama.ja_chegaram is not null and britania_fama.tem_parados is not null
				AND tbl_posto_fabrica.fabrica = $login_fabrica;";
	$res = pg_exec($con,$sql);

	for ($i=0; $i<pg_numrows($res); $i++) {

		$cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";
?>
	<tr bgcolor='<? echo $cor; ?>'>
		<td align='left'><? echo pg_result ($res,$i,codigo_posto)." - ".pg_result ($res,$i,nome); ?></td>
		<td align='center'>
		<?
		if (pg_result ($res,$i,ja_chegaram) == 't') echo "Sim (".pg_result ($res,$i,quantos_chegaram).")";
		else echo "Não";
		?>
		</td>
		<td align='center'>
		<?
		if (pg_result ($res,$i,tem_parados) == 't') echo "Sim (".pg_result ($res,$i,quantos_parados).")";
		else echo "Não";
		?>
		</td>
	</tr>
<?
	}
?>
</table>
<?
}

if ($acao == 'n_respondeu') {?>
<!--<table align='center' border='0' cellspacing='2' cellpadding='4'>
	<tr class='menu_top'><td>Posto</td></tr>-->
<?
	$sql =	"SELECT tbl_posto_fabrica.codigo_posto, tbl_posto.nome
				FROM britania_fama
				JOIN tbl_posto USING (posto)
				JOIN tbl_posto_fabrica USING (posto)
				where britania_fama.ja_chegaram isnull and britania_fama.tem_parados isnull
				AND tbl_posto_fabrica.fabrica = $login_fabrica";
	$res = pg_exec($con,$sql);
	$y=1;
	echo "<table border='0' cellspacing='1' cellpadding='2' class='tabela' align='center'>";
	echo "<tr class='titulo_tabela'>\n<td align='center' colspan='2'>P.A que não Responderam o Questionário</td>\n</tr>\n";
	echo "<tr class='titulo_coluna'>\n<td>Posto</td>\n<td>Posto</td>\n</tr>\n";
	echo "<tr bgcolor='#F1F4FA'>\n<td align='left'>";
	for ($i=0; $i<pg_numrows($res); $i++) {
		echo pg_result ($res,$i,codigo_posto)." - ".pg_result ($res,$i,nome);
		$resto = $y % 2;
		$y++;
		$cor = ($y % 2) ? "#F7F5F0" : "#F1F4FA";
		if($resto == 0){
			echo "</td></tr>\n";
			echo "<tr bgcolor='$cor'><td align='left'>";
		} else {
			echo "</td>\n";
			echo "<td align='left'>";
		}
	}
	echo "</td></tr>\n</table>\n";
}
?>

<? include "rodape.php"; ?>