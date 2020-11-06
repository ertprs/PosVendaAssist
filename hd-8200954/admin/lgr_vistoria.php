<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include "autentica_admin.php";

# Pesquisa pelo AutoComplete AJAX
$q = strtolower($_GET["q"]);
if (isset($_GET["q"])){
	$tipo_busca = $_GET["busca"];
	
	if (strlen($q)>2){
		$sql = "SELECT tbl_posto.cnpj, tbl_posto.nome, tbl_posto_fabrica.codigo_posto
				FROM tbl_posto
				JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
				WHERE tbl_posto_fabrica.fabrica = $login_fabrica ";
		
		if ($tipo_busca == "codigo"){
			$sql .= " AND tbl_posto_fabrica.codigo_posto = '$q' ";
		}else{
			$sql .= " AND UPPER(tbl_posto.nome) like UPPER('%$q%') ";
		}
		
		$res = pg_exec($con,$sql);
		if (pg_numrows ($res) > 0) {
			for ($i=0; $i<pg_numrows ($res); $i++ ){
				$cnpj = trim(pg_result($res,$i,cnpj));
				$nome = trim(pg_result($res,$i,nome));
				$codigo_posto = trim(pg_result($res,$i,codigo_posto));
				echo "$cnpj|$nome|$codigo_posto";
				echo "\n";
			}
		}
	}
	exit;
}


/*

$array_notas = array();

array_push($array_notas,"0001");
array_push($array_notas,"0002");
array_push($array_notas,"0003");
array_push($array_notas,"0004");
array_push($array_notas,"0021");
array_push($array_notas,"0023");
array_push($array_notas,"0021");
array_push($array_notas,"0004");
array_push($array_notas,"0003");
array_push($array_notas,"7777");
array_push($array_notas,"9999");
array_push($array_notas,"8888");
array_push($array_notas,"7777");
array_push($array_notas,"7777");
array_push($array_notas,"7777");
array_push($array_notas,"8888");

echo "<br> 0)".count($array_notas);
echo "<br> 1)".count(array_unique($array_notas));


if (is_int("5014-1")) {
 echo "is integer\n";
} else {
 echo "is not an integer\n";
}
var_dump(is_int("5014-1"));

*/

if (strlen($_POST["btnacao"]) > 0) $btnacao = trim(strtolower($_POST["btnacao"]));
if (strlen($_GET["btnacao"])  > 0) $btnacao = trim(strtolower($_GET["btnacao"]));

if (strlen($_POST["codigo_posto"]) > 0) $posto_codigo = $_POST["codigo_posto"];
if (strlen($_GET["codigo_posto"])  > 0) $posto_codigo = $_GET["codigo_posto"];

$posto_nome   = $_POST['posto_nome'];
if (strlen($_GET['posto_nome']) > 0) $posto_nome = $_GET['posto_nome'];

if (strlen ($codigo_posto) > 0 OR strlen($posto)>0 ) {

	if (strlen ($codigo_posto) > 0 ){
		$sql = "SELECT posto 
				FROM tbl_posto_fabrica
				WHERE fabrica = $login_fabrica
				AND codigo_posto = '$codigo_posto'";
		$res = pg_exec ($con,$sql);
		if (pg_numrows($res)>0){
			$posto = pg_result ($res,0,posto);
		}
	}

	if (strlen($posto)>0){
		header ("Location: lgr_vistoria_itens.php?posto=$posto");
		exit;
	}
}

$msg_erro = "";

$layout_menu = "auditoria";
$title = "Vistoria de Peças";
include "cabecalho.php";
?>
<style>
.Tabela{
	border:1px solid #596D9B;
	background-color:#596D9B;
	}

.Titulo {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-weight: bold;
	color:#ffffff;
	border: 1px solid;	
	background-color: #596D9B;
}
.Conteudo {
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
}
</style>

<script src="js/jquery-1.2.6.pack.js"        type="text/javascript"></script>
<script src="js/chili-1.7.pack.js"           type="text/javascript"></script>
<script src="js/jquery.tooltip.pack.js"      type="text/javascript"></script>
<link rel="stylesheet" href="js/jquery.tooltip.css" />

<script type="text/javascript">
	$(function() {
		$("a[@rel='ajuda'],span[@rel='ajuda']").Tooltip({
			track: true,
			delay: 0,
			showURL: false,
			opacity: 0.85,
			showBody: " - ",
			extraClass: "ajuda"
		});

		
	});
</script>

<script type='text/javascript' src='js/jquery.autocomplete.js'></script>
<link rel="stylesheet" type="text/css" href="js/jquery.autocomplete.css" />
<script type='text/javascript' src='js/jquery.bgiframe.min.js'></script>
<script type='text/javascript' src='js/dimensions.js'></script>

<script language="JavaScript">
$().ready(function() {

	function formatItem(row) {
		return row[2] + " - " + row[1];
	}
	
	function formatResult(row) {
		return row[0];
	}
	
	/* Busca pelo Código */
	$("#posto_codigo").autocomplete("<?echo $PHP_SELF.'?busca=codigo'; ?>", {
		minChars: 3,
		delay: 150,
		width: 350,
		matchContains: true,
		formatItem: formatItem,
		formatResult: function(row) {return row[2];}
	});

	$("#posto_codigo").result(function(event, data, formatted) {
		$("#posto_nome").val(data[1]) ;
		$("#btnacao").focus() ;
		
	});

	/* Busca pelo Nome */
	$("#posto_nome").autocomplete("<?echo $PHP_SELF.'?busca=nome'; ?>", {
		minChars: 3,
		delay: 150,
		width: 350,
		matchContains: true,
		formatItem: formatItem,
		formatResult: function(row) {return row[1];}
	});

	$("#posto_nome").result(function(event, data, formatted) {
		$("#posto_codigo").val(data[2]) ;
		$("#btnacao").focus() ;
	});

});

function fnc_pesquisa_posto(codigo,nome,tipo){
	if (tipo =='codigo'){
		var campo = codigo.value;
	}
	if (tipo =='nome'){
		var campo = nome.value;
	}
	if (campo.value != "") {
		var url = "";
		url = "posto_pesquisa_2.php?campo=" + campo + "&tipo=" + tipo;
		janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=600, height=400, top=18, left=0");
		janela.codigo   = codigo;
		janela.nome    = nome;
		janela.focus();
	}
}

</script>

<FORM METHOD='POST' name="frm_consulta" ACTION="<?=$PHP_SELF?>">
<table width="420" border="0" cellspacing="0" cellpadding="2" align="center" class='PesquisaTabela'>
<caption>VISTORIA DE PEÇAS</caption>
<tbody>
	<tr>
		<td>Posto</td>
		<td><input type="text" name='codigo_posto' id="posto_codigo" size="8" value="<? echo $posto_codigo ?>" class="frm"> <img border="0" src="imagens/lupa.png" style="cursor: hand;" align="absmiddle" alt="Clique aqui para pesquisar postos pelo código" onclick="javascript: fnc_pesquisa_posto (document.frm_consulta.codigo_posto, document.frm_consulta.posto_nome, 'codigo')"></td>
	</tr>

	<tr>
		<td>Nome do Posto</td>
		<td><input type="text" name="posto_nome" id="posto_nome" size="30" value="<?echo $posto_nome?>" class="frm">
		<img border="0" src="imagens/lupa.png" style="cursor: hand;" align="absmiddle" alt="Clique aqui para pesquisar postos pelo nome" onclick="javascript: fnc_pesquisa_posto (document.frm_consulta.codigo_posto, document.frm_consulta.posto_nome, 'nome')"></td>
	</tr>
	
	<tr>
		<td></td>
		<td><INPUT TYPE="submit" name='btnacao' id='btnacao' value="Pesquisar" ></td>
	</tr>

</tbody>
</table>
</form>



<? include "rodape.php"; ?>
