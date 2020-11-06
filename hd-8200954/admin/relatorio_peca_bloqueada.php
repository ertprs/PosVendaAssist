<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="call_center";
include 'autentica_admin.php';
include 'funcoes.php';



# Pesquisa pelo AutoComplete AJAX
$q = trim($_GET["q"]);
if (isset($_GET["q"])){
	$busca      = $_GET["busca"];
	$tipo_busca = $_GET["tipo_busca"];
	if (strlen($q)>2){
		if ($tipo_busca=="produto"){
			$sql = "SELECT tbl_produto.produto,
							tbl_produto.referencia,
							tbl_produto.descricao
					FROM tbl_produto
					JOIN tbl_linha ON tbl_linha.linha = tbl_produto.linha
					WHERE tbl_linha.fabrica = $login_fabrica ";

			if ($busca == "codigo"){
				$sql .= " AND UPPER(tbl_produto.referencia) like UPPER('%$q%') ";
			}else{
				$sql .= " AND UPPER(tbl_produto.descricao) like UPPER('%$q%') ";
			}

			$res = pg_exec($con,$sql);
			if (pg_numrows ($res) > 0) {
				for ($i=0; $i<pg_numrows ($res); $i++ ){
					$produto    = trim(pg_result($res,$i,produto));
					$referencia = trim(pg_result($res,$i,referencia));
					$descricao  = trim(pg_result($res,$i,descricao));
					echo "$produto|$descricao|$referencia";
					echo "\n";
				}
			}
		}
	}
	exit;
}

if (strlen($_POST['btn_lista']) > 0) $btn_lista = $_POST['btn_lista'];
else                                 $btn_lista = $_GET['btn_lista'];

if (strlen($_POST['produto']) > 0) $produto = $_POST['produto'];
else                               $produto = $_GET["produto"];

if (strlen($_POST['referencia']) > 0) $referencia = $_POST['referencia'];
else                                  $referencia = $_GET["referencia"];

if (strlen ($btn_lista) > 0) {//se o botão foi clicado
	if (strlen($referencia) > 0) {
		$sql = "SELECT  tbl_produto.referencia,
						tbl_produto.descricao ,
						tbl_produto.produto
				FROM    tbl_produto
				JOIN    tbl_linha    ON tbl_linha.linha   = tbl_produto.linha
									AND tbl_linha.fabrica = $login_fabrica
				WHERE   tbl_produto.referencia ilike '$referencia'";
		$res = pg_exec ($con,$sql);
		if (pg_numrows($res) > 0) {
			$referencia = trim(pg_result($res,0,referencia));
			$descricao  = trim(pg_result($res,0,descricao));
			$produto    = trim(pg_result($res,0,produto));
		}
	}

	if (strlen ($referencia) == 0) $msg_erro = "Preencha a referência do produto";
}

$layout_menu = "callcenter";
$title = "CONSULTA DE PEÇAS BLOQUEADAS PARA GARANTIA";
include 'cabecalho.php';

?>

<script language='javascript'>
function fnc_pesquisa_produto (campo, campo2, tipo) {
	if (tipo == "referencia" ) {
		var xcampo = campo;
	}

	if (tipo == "descricao" ) {
		var xcampo = campo2;
	}

	if (xcampo.value != "") {
		var url = "";
		url = "produto_pesquisa_2.php?campo=" + xcampo.value + "&tipo=" + tipo + "&lbm=1" ;
		janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=500, height=400, top=18, left=0");
		janela.referencia	= campo;
		janela.descricao	= campo2;
		janela.produto		= document.frm_lbm.produto;
		janela.focus();
	}
}



</script>

<? include "javascript_calendario.php"; //adicionado por Fabio 27-09-2007 ?>

<script type='text/javascript' src='js/jquery.autocomplete.js'></script>
<link rel="stylesheet" type="text/css" href="js/jquery.autocomplete.css" />
<script type='text/javascript' src='js/jquery.bgiframe.min.js'></script>
<script type='text/javascript' src='js/dimensions.js'></script>

<script language="JavaScript">
$().ready(function() {

	function formatItem(row) {
		return row[2] + " - " + row[1];
	}

	/* Busca por Produto */
	$("#referencia").autocomplete("<?echo $PHP_SELF.'?tipo_busca=produto&busca=codigo'; ?>", {
		minChars: 3,
		delay: 150,
		width: 350,
		matchContains: true,
		formatItem: formatItem,
		formatResult: function(row) {return row[2];}
	});

	$("#referencia").result(function(event, data, formatted) {
		$("#descricao").val(data[1]) ;
	});

	/* Busca pelo Nome */
	$("#descricao").autocomplete("<?echo $PHP_SELF.'?tipo_busca=produto&busca=nome'; ?>", {
		minChars: 3,
		delay: 150,
		width: 350,
		matchContains: true,
		formatItem: formatItem,
		formatResult: function(row) {return row[1];}
	});

	$("#descricao").result(function(event, data, formatted) {
		$("#referencia").val(data[2]) ;
	});


});
</script>
<style>
.Titulo {
	text-align: center;
	font-family: Arial;
	font-size: 12px;
	font-weight: bold;
	color: #FFFFFF;
	background-color: #485989;
}
.Titulo2 {
	text-align: center;
	font-family: Arial;
	font-size: 12px;
	font-weight: bold;
	color: #330000;
}
.Conteudo {
	font-family: Arial;
	font-size: 9px;
	font-weight: normal;
}
</style>

<body>

<DIV ID='wrapper'>
<form name="frm_lbm" method="post" action="<? echo $PHP_SELF ?>">

<? if (strlen($msg_erro) > 0) {
	if (strpos($msg_erro,"ERROR: ") !== false) {
			$erro = "Foi detectado o seguinte erro:<br>";
			$msg_erro = substr($msg_erro, 6);
		}

		// retira CONTEXT:
	if (strpos($msg_erro,"CONTEXT:")) {
		$x = explode('CONTEXT:',$msg_erro);
		$msg_erro = $x[0];
	}


?>
<div class='error'><? echo $msg_erro; ?></div>
<p>
<? } ?>

<center>
<?
echo "<INPUT TYPE=\"hidden\" name='produto' value='$produto'>";
?>
</center>
<table width='500' class='Conteudo' style='background-color: #485989' border='1' cellpadding='5' cellspacing='1' align='center'>
	<tr>
		<td class='Titulo' background='imagens_admin/azul.gif' colspan='2'><? echo $title;?></td>
	</tr>
	<tr>
	<td bgcolor='#DBE5F5' valign='bottom'>

	<table width='100%' border='0' cellspacing='1' cellpadding='2' >
	<caption class='Titulo2'>SELECIONE O PRODUTO PARA FAZER A PESQUISA</caption>
	<tr bgcolor='#d9e2ef' class="Conteudo">
		<td align='center'>
			<b>Referência</b>
		</td>
		<td align='center'>
			<b>Descrição</b>
		</td>
	</tr>

	<tr>
		<td align='center'>
			<input type="text" name="referencia" id="referencia" value="<? echo $referencia ?>" size="15" maxlength="20" >&nbsp;<img src='imagens/lupa.png' style="cursor: hand;" border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_produto (document.frm_lbm.referencia,document.frm_lbm.descricao,'referencia')">
		</td>
		<td align='center'>
			<input type="text" name="descricao" id="descricao" value="<? echo $descricao; ?>" size="50" maxlength="50" <? if ($login_fabrica == 5) { ?> onblur="javascript: fnc_pesquisa_produto (document.frm_lbm.referencia,document.frm_lbm.descricao,'descricao')" <? } ?>>&nbsp;<img src='imagens/lupa.png' border='0' style="cursor:pointer" align='absmiddle' onclick="javascript: fnc_pesquisa_produto (document.frm_lbm.referencia,document.frm_lbm.descricao,'descricao')">
		</td>
	</tr>
	</table><br>
			<input type='submit' style="cursor:pointer" name='btn_lista' value='Consultar'>
		</td>
	</tr>
</table>
<br><br>
</form>
</div>


<? if(strlen($btn_lista) > 0 AND strlen($msg_erro)==0 AND strlen($produto) > 0 ) {
		$sql = "SELECT  tbl_peca.peca,
					tbl_peca.referencia,
					tbl_peca.descricao
			FROM    tbl_lista_basica
			JOIN    tbl_produto USING (produto)
			JOIN    tbl_peca USING (peca)
			WHERE   tbl_peca.fabrica = $login_fabrica
			AND     tbl_lista_basica.fabrica=$login_fabrica
			AND     tbl_lista_basica.produto=$produto
			AND     tbl_peca.bloqueada_garantia IS TRUE
			ORDER BY    tbl_peca.descricao;";
	$res = pg_exec ($con,$sql);

	if(pg_numrows($res) >0) {
		echo "<table width='450' align='center' border='1' class='conteudo' cellpadding='2' cellspacing='0'>";
		echo "<caption>Produto: $referencia - $descricao</caption>";
		echo "<tr bgcolor='#D9E2EF'>";
		echo "<td align='center' width='150'>";
		echo "<b>Referência</b>";
		echo "</td>";
		echo "<td align='center'>";

		echo "<b>Descrição</b>";
		echo "</td>";
		echo "</tr>";
		for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
			$peca = pg_result($res,0,peca);
			if ($i % 2 == 0) {$cor = '#FFFFFF';}else{$cor = '#e6eef7';}
			echo "<tr bgcolor='$cor'>";
			echo "<td align='center'>";
			echo pg_result ($res,$i,referencia);
			echo "</td>";

			echo "<td align='center' nowrap>";
			echo "<a href='peca_consulta_dados.php?peca=$peca' target='_blank'>";
			echo pg_result ($res,$i,descricao);
			echo "</a>";
			echo "</td>";
			echo "</tr>";
		}
		echo "</table>";
	}
}
?>


<?
	include "rodape.php";
?>

</body>
</html>
