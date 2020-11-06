<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="gerencia";
include 'autentica_admin.php';
$peca = $_GET["peca"];
$preco = $_GET["preco"];
$op   = $_GET["op"];

if (strlen ($peca) > 0 AND $op =='aprovar') {

	$sql = "SELECT tabela_item 
		FROM tbl_tabela_item 
		WHERE tabela = 30 
		AND   peca   = $peca";
	$res = @pg_exec($con,$sql);
	$msg_erro = pg_errormessage($con);
	$tabela_item = @pg_result($res,0,0);

	if(strlen($tabela_item)==0){
		$sql = "INSERT INTO tbl_tabela_item(
				tabela,
				peca  ,
				preco
			)VALUES(
				30,
				$peca,
				$preco
			)";
	}else{
		$sql = "UPDATE tbl_tabela_item SET preco = $preco WHERE tabela_item =  $tabela_item";
	}

	$res = @pg_exec($con,$sql);
	$msg_erro = pg_errormessage($con);


	$resposta = "Novo preço gravado!";

	if(strlen($msg_erro)>0) $resposta = "0|$msg_erro";
	echo "ok|$resposta";
	exit;
}


$layout_menu = "cadastro";
$title = "Preços Promoção";



include "cabecalho.php";



?>
<style>
.Titulo {
	font-family: Verdana;
	font-size: 12px;
	font-weight: bold;
	color: #000;
}

.Conteudo {
	font-family: Arial;
	font-size: 8pt;
	font-weight: normal;
}

.Caixa{
	BORDER-RIGHT: #6699CC 1px solid; 
	BORDER-TOP: #6699CC 1px solid; 
	FONT: 8pt Arial ;
	BORDER-LEFT: #6699CC 1px solid; 
	BORDER-BOTTOM: #6699CC 1px solid; 
	BACKGROUND-COLOR: #FFFFFF;
}

.D{ /* CLASSE DE COMENTÁRIO */
	FONT-FAMILY:      Arial;
	FONT-SIZE:        10px;
	FONT-WEIGHT:      normal;
	COLOR:            #777777;
} /* CLASSE DE COMENTÁRIO */

</style>

<? include "javascript_pesquisas.php" ?>
<link rel="stylesheet" href="js/blue/style.css" type="text/css" id="" media="print, projection, screen" />
<script language="javascript" src="js/cal2.js"></script>
<script language="javascript" src="js/cal_conf2.js"></script>

<script type="text/javascript" src="js/jquery-1.1.4.pack.js"></script> 
<script type="text/javascript" src="js/jquery.tablesorter.pack.js"></script> 
<script>
$(document).ready(function(){
	$.tablesorter.defaults.widgets = ['zebra'];
	$("#example1").tablesorter();

});
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
			
var http_forn = new Array();
function gravar_aprovar(peca,linha,btn,preco) {

	var botao = document.getElementById(btn);
	var l     = document.getElementById(linha);
	var p     = document.getElementById(preco).value;
	var acao='aprovar';

	url = "<?=$PHP_SELF?>?ajax=sim&op="+acao+"&peca="+escape(peca)+"&linha="+escape(linha)+"&preco="+p;

	var curDateTime = new Date();
	http_forn[curDateTime] = createRequestObject();
	http_forn[curDateTime].open('GET',url,true);
	
	http_forn[curDateTime].onreadystatechange = function(){
		if (http_forn[curDateTime].readyState == 4) 
		{
			if (http_forn[curDateTime].status == 200 || http_forn[curDateTime].status == 304) 
			{
				var response = http_forn[curDateTime].responseText.split("|");
				
				if (response[0]=="ok"){
					alert(response[1]);
 					l.style.background = '#D7FFE1';
					botao.value='Gravado';
					botao.disabled='true';
				}
				if (response[0]=="0"){
					alert(response[1]);
				}

			}
		}
	}
	http_forn[curDateTime].send(null);
}

</script>
<?

$sql = "SELECT  tbl_tabela.sigla,
		tbl_tabela.descricao
	FROM      tbl_tabela
	WHERE     tbl_tabela.fabrica = $login_fabrica
	AND       tbl_tabela.ativa IS TRUE
	ORDER BY  descricao";
$res = @pg_exec($con,$sql);

$msg_erro = pg_errormessage($con);

if (@pg_numrows($res) > 0 AND strlen($msg_erro) == 0) {

	$ROWS = pg_numrows($res);

	for ($i = 0 ; $i < $ROWS ; $i++) {

	}
}

$sql = "SELECT  tbl_peca.peca          ,
		tbl_peca.referencia    ,
		tbl_peca.descricao     ,
		tbl_tabela_item.preco
	FROM      tbl_peca
	LEFT JOIN tbl_tabela_item ON tbl_tabela_item.peca = tbl_peca.peca AND tbl_tabela_item.tabela = 30
	WHERE     tbl_peca.fabrica = $login_fabrica
	AND       tbl_peca.promocao_site IS NOT TRUE
	
	ORDER BY  descricao";
$res = @pg_exec($con,$sql);

$msg_erro = pg_errormessage($con);

if (@pg_numrows($res) > 0 AND strlen($msg_erro) == 0) {

	$ROWS = pg_numrows($res);

	echo "<center><div style='width:700px;'><table align='center'  border='0' id='example1' class='tablesorter' style=' border:#485989 1px solid; background-color: #e6eef7 '>";
	echo "<thead>";
	echo "<tr  height='15' class='Titulo'>";
	echo "<td>Peça</td>";
	echo "<td>Preço Atual</td>";
	echo "<td>Novo Preço</td>";
	echo "<td>AÇÕES</td>";
	echo "</tr>";
	echo "</thead>";

	echo "<tbody>";
	for ($i = 0 ; $i < $ROWS ; $i++) {
		$peca       = trim(pg_result($res,$i,peca));
		$referencia = trim(pg_result($res,$i,referencia));
		$descricao  = trim(pg_result($res,$i,descricao));
		$preco      = trim(pg_result($res,$i,preco));
		$preco      = number_format($preco,2,',','.');

		if ($i % 2 == 0) $cor   = "#F1F4FA";
		else             $cor   = "#F7F5F0";

		echo "<tr class='Conteudo' height='15' bgcolor='$cor' align='left' id='$i'>";
		echo "<td nowrap>" . $descricao . "</td>";
		echo "<td nowrap align='right'><font color='#009900'>$preco</font></td>";
		echo "<td nowrap align='right'><input type='text' name='preco_$peca' id='preco_$peca' size='7' class='Caixa' maxlength='10'></td>";
		echo "<td width='60' align='center'><input type='button' name='aprovar_$i' id='aprovar_$i' value='Gravar' onClick=\"if (this.value=='Gravando...'){ alert('Aguarde');}else {this.value='Gravando...'; gravar_aprovar('$peca','$i','aprovar_$i','preco_$peca');}\" ></td>\n";
		echo "</tr>";
		
	}
	echo "</tbody>";
//
	echo "</table></div>";
}

include "rodape.php" ;
?>