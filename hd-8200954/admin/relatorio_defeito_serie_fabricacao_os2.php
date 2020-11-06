<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'includes/funcoes.php';


include "autentica_admin.php";

include "funcoes.php";

$layout_menu = "gerencia";
$title = "RELATÓRIO - NÚMERO DE SÉRIE";

?>

<style type="text/css">
.Titulo {
	text-align: center;
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 12px;
	font-weight: bold;
	color: #FFFFFF;
	background-color: #596D9B;
}
.Conteudo {
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	background-color: #f1f6f4;
}

</style>

<? include "javascript_pesquisas.php"; ?>

<script language="javascript" src="js/cal2.js"></script>
<script language="javascript" src="js/cal_conf2.js"></script>
<script language="javascript">
function MostraEsconde(dados){
//alert("takashi + "+dados);
	if (document.getElementById){
		var style2 = document.getElementById(dados); 
		if (style2==false) return; 
		if (style2.style.display=="block"){
			style2.style.display = "none";
		}else{
			style2.style.display = "block";
			retornaLinha(dados);
		}
	}
}

</script>
<br>

<?

$produto = $_GET['produto'];
$nserie = $_GET['nserie'];
$constatado = $_GET['constatado'];
$solucao = $_GET['solucao'];
$tipo_os = $_GET['tipo_os'];
$cond_5 = "1=1";
if (strlen ($tipo_os)  > 0) $cond_5 = " tbl_os.consumidor_revenda = '$tipo_os' ";
if (strlen($nserie) > 0 && strlen($produto) > 0) {
	
	$sql = "SELECT  DISTINCT(tbl_os.os),
					tbl_produto.descricao as produto_descricao,
					to_char(tbl_os.data_abertura,'DD/MM/YYYY') AS data_abertura,
					to_char(tbl_os.data_fechamento,'DD/MM/YYYY') AS data_fechamento,
					tbl_defeito_reclamado.descricao as defeito_reclamado,
					tbl_defeito_constatado.descricao as defeito_constatado,
					tbl_solucao.descricao as solucao,
					tbl_os.serie
			FROM tbl_os
			JOIN tbl_produto using(produto)
			JOIN tbl_defeito_reclamado using(defeito_reclamado)
			JOIN tbl_defeito_constatado using(defeito_constatado)
			JOIN tbl_solucao on tbl_os.solucao_os = tbl_solucao.solucao
			JOIN tbl_os_extra using(os)
			JOIN tbl_os_produto on tbl_os.os = tbl_os_produto.os
			JOIN tbl_os_item on tbl_os_produto.os_produto = tbl_os_item.os_produto
			JOIN tbl_servico_realizado on tbl_servico_realizado.servico_realizado = tbl_os_item.servico_realizado and tbl_servico_realizado.troca_de_peca IS FALSE
			WHERE tbl_os.fabrica= $login_fabrica
			and tbl_os.produto = $produto
			and tbl_os.serie like '$nserie%'
			and tbl_os.defeito_constatado=$constatado
			and tbl_os.solucao_os = $solucao
			and $cond_5
			and tbl_os_extra.extrato notnull
";
	$res = pg_exec($con,$sql);

	//echo nl2br($sql);

	if (pg_numrows($res) > 0) {
		
		echo "<table width='700' border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#000000' align='center'>";
	
		echo "<tr class='Titulo' height='15'>";
		echo "<td>OS</td>";
		echo "<td>Produto</td>";
		echo "<td>Abertura</td>";
		echo "<td>Fechamento</td>";
		echo "<td>Defeito Reclamado</td>";
		echo "<td>Defeito Constatado</td>";
		echo "<td>Solução</td>";
		echo "</tr>";
		$total = pg_numrows($res);
		for($x=0; pg_numrows($res) > $x;$x++){
			$produto_descricao  = pg_result($res,$x,produto_descricao);
			$data_abertura      = pg_result($res,$x,data_abertura);
			$data_fechamento    = pg_result($res,$x,data_fechamento);
			$defeito_reclamado  = pg_result($res,$x,defeito_reclamado);
			$defeito_constatado = pg_result($res,$x,defeito_constatado);
			$solucao            = pg_result($res,$x,solucao);
			$os            = pg_result($res,$x,os);
			$serie            = pg_result($res,$x,serie);
			
			echo "<tr class='Conteudo' height='15'>";
			echo "<td><a href='os_press.php?os=$os' target='blank'>$os</a></td>";
			echo "<td>$produto_descricao</td>";
			echo "<td>$data_abertura</td>";
			echo "<td>$data_fechamento</td>";
			echo "<td>$defeito_reclamado</td>";
			echo "<td>$defeito_constatado</td>";
			echo "<td>$solucao</td>";
			echo "</tr>";
		}
		echo "</table>";
	}else{
		echo "<table width='700' border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#000000' align='center'>";
		echo "<tr class='Titulo' height='15'>";
		echo "<td align='center'>Nenhuma OS sem peça</td>";
		echo "</tr>";
		echo "</table>";
	}


}

?>