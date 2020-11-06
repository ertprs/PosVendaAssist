<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'includes/funcoes.php';


include "autentica_admin.php";

include "funcoes.php";

$layout_menu = "gerencia";
$title = "RELATÓRIO - NÚMERO DE SÉRIE";

if (!empty($_GET['tipo_os'])) {
	$tipo_os = $_GET['tipo_os'];
}

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
<script language="JavaScript">
function createRequestObject(){
	var request_;
	var browser = navigator.appName;
	if(browser == "Microsoft Internet Explorer"){
		 request_ = new ActiveXObject("Microsoft.XMLHTTP");
	}else{
		 request_ = new XMLHttpRequest();
	}
	return request_;
}
function MostraOs(abre_os,defeito,peca,produto){
//al/ert(abre_os + data_inicial + data_final + peca + defeito);
//alert(abre_os);
	if (document.getElementById){

		var style2 = document.getElementById(abre_os);
		if (style2==false) return;
		if (style2.style.display=="block"){
			style2.style.display = "none";
		}else{
			style2.style.display = "block";
			retornaAtencao(abre_os,defeito,peca,produto);
		}
	}
}
var http3 = new Array();
function retornaAtencao(abre_os,defeito,peca,produto){

	var curDateTime = new Date();
	http3[curDateTime] = createRequestObject();
	var serie = document.frm_fcr.serie.value;
//alert('takashi'+serie);
	url = "ajax_fcr_defeitos_os_pecas.php?peca="+ peca +"&defeito="+ defeito +"&serie="+ serie + "&produto="+produto + "&tipo_os=<?php echo $tipo_os ?>";
//alert(url);
	http3[curDateTime].open('get',url);
	var abre_os = document.getElementById(abre_os);
	http3[curDateTime].onreadystatechange = function(){
		if(http3[curDateTime].readyState == 1) {
			abre_os.innerHTML = "<font size='1'>Aguarde..</font>";
		}
		if (http3[curDateTime].readyState == 4){
			if (http3[curDateTime].status == 200 || http3[curDateTime].status == 304){
				var results = http3[curDateTime].responseText;
				abre_os.innerHTML   = results;
			}else {
				abre_os.innerHTML = "Erro";
			}
		}
	}
	http3[curDateTime].send(null);

}
</script>
<br>

<?

$produto = $_GET['produto'];
$nserie = $_GET['nserie'];
$peca = $_GET['peca'];

$condConsumidorRevenda = '';
if (!empty($tipo_os)) {
	$condConsumidorRevenda = "AND tbl_os.consumidor_revenda = '$tipo_os'";
}


if (strlen($nserie) > 0 && strlen($produto) > 0) {

	$sql = "SELECT  tbl_produto.descricao as produto_descricao,
					tbl_peca.descricao as peca,
					tbl_peca.peca as peca_peca,
					tbl_defeito.descricao as servico_realizado,
					tbl_defeito.defeito as defeito,
					count(tbl_os_item.peca) as qtde
			FROM tbl_os
			JOIN tbl_os_produto on tbl_os_produto.os = tbl_os.os
			join tbl_os_item on tbl_os_item.os_produto = tbl_os_produto.os_produto
			JOIN tbl_defeito on tbl_defeito.defeito = tbl_os_item.defeito
			JOIN tbl_os_extra on tbl_os.os=tbl_os_extra.os
			JOIN tbl_peca on tbl_peca.peca = tbl_os_item.peca
			Join tbl_produto on tbl_produto.produto = tbl_os.produto
			JOIN tbl_servico_realizado ON tbl_os_item.servico_realizado=tbl_servico_realizado.servico_realizado
			WHERE tbl_os.fabrica =  $login_fabrica
			and tbl_os.produto = $produto
			and tbl_os.serie like '$nserie%'
			and tbl_os_item.peca = $peca
			$condConsumidorRevenda
			AND tbl_os.solucao_os <>127
			and tbl_os_extra.extrato notnull
			AND tbl_servico_realizado.gera_pedido IS TRUE
			GROUP BY tbl_produto.descricao, tbl_peca.descricao, tbl_peca.peca, tbl_defeito.defeito,
					tbl_defeito.descricao
			ORDER by qtde desc";
	$res = pg_exec($con,$sql);

//	echo nl2br($sql);

	if (pg_numrows($res) > 0) {
		$produto_descricao  = pg_result($res,0,produto_descricao);
		echo "<table width='700' border='0' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#000000' align='center'>";
		echo "<tr>";
		echo "<td align='center'><font size='2' face='verdana'>Produto: $produto_descricao</font></td>";
		echo "</tr>";
		echo "</table><BR><BR>";
echo "<form name='frm_fcr' method='post'>";
echo "<input type='hidden' name='serie' value='$nserie'>";
echo "</form>";
		echo "<table width='700' border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#000000' align='center'>";

		echo "<tr class='Titulo' height='15'>";
		echo "<td>Peça</td>";
		echo "<td>Defeito</td>";
		echo "<td>Ocorrência</td>";
		echo "</tr>";
		$total = pg_numrows($res);
		for($x=0; pg_numrows($res) > $x;$x++){

			$peca      = pg_result($res,$x,peca);
			$peca_peca = pg_result($res,$x,peca_peca);
			$servico_realizado    = pg_result($res,$x,servico_realizado);
			$defeito  = pg_result($res,$x,defeito);
			$qtde  = pg_result($res,$x,qtde);

			echo "<tr class='Conteudo' height='15'>";
			echo "<td><a href='javascript: MostraOs(\"abre_os_$x\",$defeito,$peca_peca,$produto);'>$peca</a></td>";
			echo "<td>$servico_realizado</td>";
			echo "<td align='center'>$qtde</td>";
			echo "</tr>";
		echo "<TR><TD colspan=3 align='left'><div id='abre_os_$x' style='position:absolute; display:none; border: 1px solid #949494;background-color: #f4f4f4; width:452px'></div></td></tr>";
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
