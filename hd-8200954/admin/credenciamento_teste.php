<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";

$admin_privilegios="cadastros,call_center";
include 'autentica_admin.php';




# Pesquisa pelo AutoComplete AJAX
$q = strtolower($_GET["q"]);
if (isset($_GET["q"])){
	$tipo_busca = $_GET["busca"];
	$tipo_busca = $_GET["tipo_busca"];
	
	if (strlen($q)>2){

		if ($tipo_busca=="posto"){
			$sql = "SELECT tbl_posto.posto,tbl_posto.cnpj, tbl_posto.nome, tbl_posto_fabrica.codigo_posto
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
					$posto = trim(pg_result($res,$i,posto));
					$cnpj = trim(pg_result($res,$i,cnpj));
					$nome = trim(pg_result($res,$i,nome));
					$codigo_posto = trim(pg_result($res,$i,codigo_posto));
				/*Retira todos usuários do TIME*/
				$sql = "SELECT *
						FROM  tbl_empresa_cliente
						WHERE posto   = $posto
						AND   fabrica = $login_fabrica";
				$res2 = pg_exec ($con,$sql);
				if (pg_numrows($res2) > 0) continue;
				$sql = "SELECT *
						FROM  tbl_empresa_fornecedor
						WHERE posto   = $posto
						AND   fabrica = $login_fabrica";
				$res2 = pg_exec ($con,$sql);
				if (pg_numrows($res2) > 0) continue;

				$sql = "SELECT *
						FROM  tbl_erp_login
						WHERE posto   = $posto
						AND   fabrica = $login_fabrica";
				$res2 = pg_exec ($con,$sql);
				if (pg_numrows($res2) > 0) continue;

					echo "$cnpj|$nome|$codigo_posto";
					echo "\n";
				}
			}
		}
	}
	exit;
}


$msg_erro = "";

if (strlen($_POST['btn_acao']) > 0) $btn_acao = $_POST['btn_acao'];

if (strlen($_POST['posto']) > 0) $posto = $_POST['posto'];// hidden=post
if (strlen($_GET['posto']) > 0)  $posto = $_GET['posto'];

if (strlen($_GET["credenciamento"]) > 0)  $credenciamento = trim($_GET["credenciamento"]);
if (strlen($_POST["credenciamento"]) > 0) $credenciamento = trim($_POST["credenciamento"]);

if (strlen($_GET["tipo_credenciamento"]) > 0)  $tipo_credenciamento = trim($_GET["tipo_credenciamento"]);
if (strlen($_POST["tipo_credenciamento"]) > 0) $tipo_credenciamento = trim($_POST["tipo_credenciamento"]);

if ($btn_acao == "Credenciar")
	$var = 'CREDENCIADO';
else if ($btn_acao == "Descredenciar")
	$var = "DESCREDENCIADO";
else if ($btn_acao == "Cadastro Reprovado")
	$var = "REPROVADO";
else if ($btn_acao == "Em Credenciamento")
	$var = "EM CREDENCIAMENTO";
else if ($btn_acao == "Em Descredenciamento")
	$var = "EM DESCREDENCIAMENTO";

if (strlen($btn_acao) > 0 AND strlen($var) > 0) {

	$res = pg_exec ($con,"BEGIN TRANSACTION");

	if (strlen($_POST["texto"]) > 0)
		$aux_texto = "'". trim($_POST["texto"]) ."'";
	else
		$aux_texto = "null";

	if ($btn_acao == "Em Credenciamento" OR $btn_acao == "Em Descredenciamento"){
		if (strlen($_POST["qtde_dias"]) > 0)
			$aux_qtde_dias = "'". trim($_POST["qtde_dias"]) ."'";
		else
			$msg_erro = "Informe a Quantidade de Dias";
	}

	if(strlen($msg_erro) == 0 ){
		$sql = "INSERT INTO tbl_credenciamento (
					posto             ,
					fabrica           ,
					data              ,
					status            ,
					texto             ";
		if ($btn_acao == "Em Credenciamento" OR $btn_acao == "Em Descredenciamento")
		$sql .= ", dias";
		$sql .= ") VALUES (
					$posto            ,
					$login_fabrica    ,
					current_timestamp ,
					'$var'            ,
					$aux_texto        ";
		if ($btn_acao == "Em Credenciamento" OR $btn_acao == "Em Descredenciamento")
		$sql .= ", $aux_qtde_dias";
		$sql .= ");";
		$res = pg_exec ($con,$sql);
		$msg_erro = pg_errormessage($con);

		if(strlen($msg_erro) == 0 ){
			$sql = "UPDATE  tbl_posto_fabrica SET
							credenciamento = '$var'
					WHERE   fabrica = $login_fabrica
					AND     posto   = $posto;";
			$res = pg_exec($con,$sql);
			$msg_erro = pg_errormessage($con);
		}
	}

	if (strlen ($msg_erro) == 0) {
		$res = pg_exec ($con,"COMMIT TRANSACTION");
		header ("Location: $PHP_SELF");
		exit;
	}else{
		$qtde_dias  = $_POST["qtde_dias"];
		$texto      = $_POST["texto"];
		$res = pg_exec ($con,"ROLLBACK TRANSACTION");
	}
}

$title       = "Credenciamento e Descredenciamento de Postos";
$cabecalho   = "Credenciamento e Descredenciamento de Postos";
$layout_menu = "cadastro";

include 'cabecalho.php';

?>

<? 
include "javascript_calendario.php";
include "javascript_pesquisas.php";
?>

<link rel="stylesheet" href="js/blue/relatoriostyle.css" type="text/css" id="" media="print, projection, screen" />
<script type="text/javascript" src="js/jquery.tablesorter.pack.js"></script> 
<script>
$(document).ready(function(){
	$.tablesorter.defaults.widgets = ['zebra'];
	$("#relatorio").tablesorter();

});
</script>

<script language='javascript' src='../ajax.js'></script>

<script language="JavaScript">
function fnc_pesquisa_codigo_posto (codigo, nome) {
    var url = "";
    if (codigo != "" && nome == "") {
        url = "pesquisa_posto.php?codigo=" + codigo;
        janela = window.open(url,"janela","toolbar=no,location=no,status=no,scrollbars=yes,directories=no,width=600,height=400,top=18,left=0");
        janela.focus();
    }
}

function fnc_pesquisa_nome_posto (codigo, nome) {
    var url = "";
    if (codigo == "" && nome != "") {
        url = "pesquisa_posto.php?nome=" + nome;
        janela = window.open(url,"janela","toolbar=no,location=no,status=no,scrollbars=yes,directories=no,width=600,height=400,top=18,left=0");
        janela.focus();
    }
}

function fnc_pesquisa_posto (campo, campo2, campo3, tipo) {
	if (tipo == "nome" ) {
		var xcampo = campo;
	}

	if (tipo == "cnpj" ) {
		var xcampo = campo2;
	}

	if (tipo == "codigo" ) {
		var xcampo = campo3;
	}

	if (xcampo.value != "") {
		var url = "";
		url = "posto_pesquisa_credenciamento.php?campo=" + xcampo.value + "&tipo=" + tipo ;
		janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=500, height=400, top=0, left=0");
		janela.retorno = "<? echo $PHP_SELF ?>";
		janela.nome		= campo;
		janela.posto	= campo2;
		janela.codigo	= campo3;
		janela.focus();
	}
}

</script>
<script>
$(document).ready(function() {
	$("#date-br").tableSorter({
		dateFormat: 'dd/mm/yyyy' 		// set date format for non iso dates default us, in this case override and set uk-format
	});
});
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
	
	function formatResult(row) {
		return row[0];
	}
	
	/* Busca pelo Código */
	$("#codigo").autocomplete("<?echo $PHP_SELF.'?tipo_busca=posto&busca=codigo'; ?>", {
		minChars: 3,
		delay: 150,
		width: 350,
		matchContains: true,
		formatItem: formatItem,
		formatResult: function(row) {return row[2];}
	});

	$("#codigo").result(function(event, data, formatted) {
		$("#nome").val(data[1]) ;
	});

	/* Busca pelo Nome */
	$("#nome").autocomplete("<?echo $PHP_SELF.'?tipo_busca=posto&busca=nome'; ?>", {
		minChars: 3,
		delay: 150,
		width: 350,
		matchContains: true,
		formatItem: formatItem,
		formatResult: function(row) {return row[1];}
	});

	$("#nome").result(function(event, data, formatted) {
		$("#codigo").val(data[2]) ;
		//alert(data[2]);
	});

});
</script>

<style type="text/css">
body{
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 11px;
}

.menu_top {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: bold;
	border: 1px solid;
	color:#596d9b;
	background-color: #d9e2ef
}

.border {
	border: 1px solid #ced7e7;
}

.table_line_2_2 {
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	border: 0px solid;
	background-color: #ffffff
}

input {
	font-size: 10px;
}

.top_list {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: bold;
	color:#596d9b;
	background-color: #d9e2ef
}

.line_list {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: x-small;
	font-weight: normal;
	color:#596d9b;
	background-color: #ffffff
}

</style>

<? 
if($msg_erro){
?>
	<table width='700px' align='center' border='0' bgcolor='#FFFFFF' cellspacing="1" cellpadding="0">
	<tr align='center'>
		<td class='error'>
			<? echo $msg_erro; ?>
		</td>
	</tr>
	</table>
<?
} 
?> 
<p>

<form name="frm_credenciamento" method="POST" action="<? echo $PHP_SELF; ?>">
<input type="hidden" name="credenciamento" value="<? echo $credenciamento ?>">
<input type='hidden' name='btn_acao' value=''>

<table class="border" width='650' align='center' border='0' cellpadding="1" cellspacing="3">
	<tr class="menu_top">
		<td COLSPAN='2' ALIGN='CENTER'>LISTAR POSTOS</td>
	</tr>
	<tr class="table_line_2">
		<td COLSPAN='2' align='center'>
			<select name='tipo_credenciamento'>
				<option value=''>ESCOLHA</option>
<?
					$sql = "SELECT	distinct credenciamento 
							FROM	tbl_posto_fabrica 
							WHERE	fabrica = $login_fabrica";
					$res = pg_exec ($con,$sql);
					for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
						echo "<option value='" . pg_result ($res,$i,credenciamento) . "' " ;
						if ($tipo_credenciamento == pg_result($res,$i,credenciamento)) echo " selected ";
						echo ">";
						echo pg_result ($res,$i,credenciamento);
						echo "</option>";
					}
?>
			</select>
		</td>
	</TR>
	<tr class="menu_top">
		<td>CÓDIGO</td>
		<td>RAZÃO SOCIAL</td>
	</tr>
	<?
	if (strlen($codigo) > 0){
		$sql = "SELECT	tbl_posto.nome                ,
						tbl_posto.posto               ,
						tbl_posto_fabrica.credenciamento
				FROM tbl_posto_fabrica
				JOIN tbl_posto ON tbl_posto_fabrica.posto = tbl_posto.posto
				WHERE tbl_posto_fabrica.codigo_posto = '$codigo'
				AND   tbl_posto_fabrica.fabrica = $login_fabrica";
		$res = pg_exec($con,$sql);

		if (pg_numrows($res) > 0){
			$nome           = pg_result($res,0,nome);
			$posto          = pg_result($res,0,posto);
			$tipo_credenciamento = pg_result($res,0,credenciamento);
		}
		//echo $tipo_credenciamento;
	}

	?>
	<tr class="table_line_2">
		<td align='center'><input type="text" name="codigo" id="codigo" size="14" maxlength="14" value="<? echo $codigo ?>" style="width:150px">&nbsp;<img src='imagens/lupa.png' style="cursor:pointer" border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_posto (document.frm_credenciamento.nome,document.frm_credenciamento.posto,document.frm_credenciamento.codigo,'codigo')"></td>
		<td align='center'><input type="text" name="nome" id="nome" size="50" maxlength="60" value="<? echo $nome ?>" style="width:300px">&nbsp;<img src='imagens/lupa.png' style="cursor:pointer" border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_posto (document.frm_credenciamento.nome,document.frm_credenciamento.posto,document.frm_credenciamento.codigo,'nome')"></td>
	</tr>
</table>
<input type="hidden" name="posto" value="<? echo $posto?>">
<br>

<table width='650' align='center' border='0' cellpadding="0" cellspacing="0">
	<tr>
		<td><a href='javascript:document.frm_credenciamento.submit();'><img src="imagens_admin/btn_listar.gif"></a></td>
	</tr>
</table>
</form>

<?
if (strlen($codigo) > 0 and strlen($nome) > 0 and strlen($tipo_credenciamento) > 0) $listar = 1;
if (strlen($tipo_credenciamento) > 0 ) $listar = 2;
if (strlen($codigo) > 0 and strlen($nome) > 0) $listar = 1;
?>

<br>

<?
#...................................      BUSCA PELO CODIGO/NOME DO POSTO     .................................#

if ($listar == 1){
	
	$sql = "SELECT	tbl_posto.nome                  ,
					tbl_posto.cnpj                  ,
					tbl_posto_fabrica.codigo_posto  ,
					tbl_posto_fabrica.contato_estado,
					tbl_posto_fabrica.contato_cidade,
					tbl_posto_fabrica.contato_fone_comercial as fone,
					tbl_posto_fabrica.credenciamento,
					(SELECT to_char(tbl_credenciamento.data,'DD/MM/YYYY') from tbl_credenciamento where tbl_credenciamento.posto = tbl_posto_fabrica.posto order by data DESC limit 1) AS data
			FROM tbl_posto_fabrica
			JOIN tbl_posto ON tbl_posto_fabrica.posto = tbl_posto.posto
			WHERE tbl_posto_fabrica.codigo_posto = '$codigo'
			AND   tbl_posto_fabrica.fabrica = $login_fabrica";
	$res = pg_exec($con,$sql);

	$nome                = pg_result($res,0,nome);
	$codigo              = pg_result($res,0,codigo_posto);
	$estado              = pg_result($res,0,contato_estado);
	$cidade              = pg_result($res,0,contato_cidade);
	$fone                = pg_result($res,0,fone);
	$tipo_credenciamento = pg_result($res,0,credenciamento);
	$cnpj                = pg_result($res,0,cnpj);


	echo "<form name='frm_credenciamento_2' method='POST' action='$PHP_SELF'>";
	echo "<input type='hidden' name='posto' value='$posto'>";
	echo "<input type='hidden' name='codigo' value='$codigo'>";
	echo "<input type='hidden' name='listar' value='1'>";

	echo "<table class='border' width='650' align='center' border='0' cellpadding='1' cellspacing='3'>";
	echo "<tr>";
	if($login_fabrica == 19)	echo "<td class='menu_top' width='15%'>CNPJ</td>";
	echo "<td class='menu_top' width='55%'>POSTO</td>";
	echo "<td class='menu_top' width='15%'>CIDADE</td>";
	echo "<td class='menu_top' width='15%'>ESTADO</td>";
	echo "<td class='menu_top' width='15%'>FONE</td>";
	if ($login_fabrica == 50) echo "<td class='menu_top' width='15%'>DATA</td>";
	echo "</tr>";
	echo "<tr>";
	if($login_fabrica == 19) echo "<td class='table_line_2' align='center'>$cnpj</td>";
	echo "<td class='table_line_2' align='left'>$codigo - $nome</td>";
	echo "<td class='table_line_2' align='center'>$cidade</td>";
	echo "<td class='table_line_2' align='center'>$estado</td>";
	echo "<td class='table_line_2' align='center'>$fone</td>";
	if($login_fabrica == 50) echo "<td class='table_line_2' align='center'>$data</td>";
	echo "</tr>";
	echo "</table>";
	echo "<br>";

	$sql = "SELECT	tbl_credenciamento.status,
					tbl_credenciamento.dias  ,
					tbl_credenciamento.texto ,
					to_char(tbl_credenciamento.data,'DD/MM/YYYY') AS data
			FROM	tbl_credenciamento
			WHERE	tbl_credenciamento.fabrica = $login_fabrica
			AND		tbl_credenciamento.posto   = $posto
			ORDER BY tbl_credenciamento.credenciamento DESC";
	$res = pg_exec($con,$sql);

	if (pg_numrows($res) > 0){
		echo "<table class='border' width='650' align='center' border='0' cellpadding='1' cellspacing='3'>";
		echo "<tr>";
		echo "<td class='menu_top' colspan='4'>HISTÓRICO DOS STATUS DO POSTOS<BR>";
		/* hd 153618 Tulio pediu para voltar somente para a Britania */
		if($login_fabrica != 3){
			echo "
			<BR>ATENÇÃO<BR><BR>
			A TELECONTROL não irá mais executar automaticamente o CREDENCIAMENTO e também o<BR>
			DESCREDENCIAMENTO. O motivo para esta decisão é bem simples, ou seja, todo credenciamento<BR>
			depende de acordos entre o Fabricante e o Posto Autorizado, e alguns destes acordos amparados<BR>
			por contratos que dependem de postagem via Correios, etc.<BR>
			Todo trabalho de CREDENCIAMENTO e DESCREDENCIAMENTO deverá ser realizada manualmente pelo<BR>
			usuário responsável por isto, assim que estiver com toda documentação em mãos, ou no caso de<BR>
			descredenciamento, as Ordens de Serviços já resolvidas.<BR><BR>
			Contamos com a compreensão de todos!<BR><BR>
			Suporte Telecontrol.";
		}
		echo "</td>";
		echo "</tr>";
		echo "<tr>";
		echo "<td class='menu_top' width='20%'>DATA DE GERAÇÃO</td>";
		echo "<td class='menu_top' width='25%'>STATUS</td>";
		echo "<td class='menu_top' width='15%'>QTDE DIAS</td>";
		echo "<td class='menu_top' width='40%'>OBSERVAÇÃO</td>";
		echo "</tr>";

		for ($i = 0 ; $i < pg_numrows($res) ; $i++){
			$status      = pg_result($res,$i,status);
			$mdias        = pg_result($res,$i,dias);
			$data_geracao= pg_result($res,$i,data);
			$mtexto       = pg_result($res,$i,texto);
		
			echo "<tr>";
			echo "<td class='table_line_2'>$data_geracao</td>";
			echo "<td class='table_line_2' align='left'>$status</td>";
			echo "<td class='table_line_2'>$mdias</td>";
			echo "<td class='table_line_2' align='left'>$mtexto</td>";
			echo "</tr>";
		}

		echo "</table>";

		echo "<br>";
	}

	$sql = "SELECT	tbl_credenciamento.status,
					tbl_credenciamento.dias  ,
					tbl_credenciamento.texto ,
					to_char(tbl_credenciamento.data,'YYYY-MM-DD') AS data,
					tbl_posto.nome
			FROM	tbl_credenciamento
			JOIN    tbl_posto ON tbl_posto.posto = tbl_credenciamento.posto
			WHERE	tbl_credenciamento.fabrica = $login_fabrica
			AND		tbl_credenciamento.posto   = $posto
			ORDER BY tbl_credenciamento.credenciamento DESC LIMIT 1";
	$res = pg_exec($con,$sql);

	if (pg_numrows($res) > 0){
		$status      = pg_result($res,0,status);
		$xdias        = pg_result($res,0,dias);
		$data_geracao= pg_result($res,0,data);
		$xtexto       = pg_result($res,0,texto);
		$posto_nome  = pg_result($res,0,nome);

		if ($status == 'EM CREDENCIAMENTO' OR $status == 'EM DESCREDENCIAMENTO'){
			
			$sqlX = "SELECT '$data_geracao':: date + interval '$xdias days';";
			$resX = pg_exec ($con,$sqlX);
			$dt_expira = pg_result ($resX,0,0);

			$sqlX = "SELECT '$dt_expira'::date - current_date;";
			$resX = pg_exec ($con,$sqlX);

			$dt_expira = substr ($dt_expira,8,2) . "-" . substr ($dt_expira,5,2) . "-" . substr ($dt_expira,0,4);
			$dia_hoje= pg_result ($resX,0,0);

			echo "<table class='border' width='650' align='center' border='0' cellpadding='1' cellspacing='3'>";
			echo "<tr class='menu_top'><td colspan='3'> O POSTO \"$posto_nome\" DEVERÁ PERMANECER \"$status\" <br> ATÉ O DIA \"$dt_expira\" (RESTAM \"$dia_hoje\" DIAS)</td></tr>";
			echo "</table>";
		}
	}

	echo "<table class='border' width='650' align='center' border='0' cellpadding='1' cellspacing='3'>";

	if ($tipo_credenciamento == 'CREDENCIADO'){
		echo "<tr>";
		echo "<td align='center' class='menu_top' colspan='4'>DESCREDENCIAR / COLOCAR EM DESCREDENCIAMENTO	</td>";
		echo "</tr>";
		echo "<tr>";
		echo "<td CLASS='line_list' width='35%'><b>QTDE DIAS PARA DESCREDENCIAR: </b></TD>";
		echo "<td CLASS='line_list'width='65%'><input type='text' name='qtde_dias' value='$qtde_dias' size='3' maxlength='5'><font color='#489191' size='1' face='verdana'> (*) Obrigatório para status 'EM DESCREDENCIAMENTO'</font></td>";
		echo "</tr>";
		echo "<tr><td CLASS='line_list' width='35%'><B>OBSERVAÇÕES: </B></td>";
		echo "<td  width='65%'><textarea name='texto' rows='3' cols='50'>$texto</textarea></td></tr>";
		echo "<tr>";
		echo "<td colspan='2'>";
		echo "<center>";
		echo "<input type='submit' name='btn_acao' value='Descredenciar'>";
		echo "<input type='submit' name='btn_acao' value='Em Descredenciamento'>";
		echo "<input type='submit' name='btn_acao' value='Cadastro Reprovado'>";
		echo "</center>";
		echo "</td>";
		echo "</tr>";
	}else if ($tipo_credenciamento == 'DESCREDENCIADO'){
		echo "<tr>";
		echo "<td align='center' class='menu_top' colspan='4'>CREDENCIAR / COLOCAR EM CREDENCIAMENTO	</td>";
		echo "</tr>";
		echo "<tr>";
		echo "<td CLASS='line_list' width='35%'><b>QTDE DIAS PARA CREDENCIAR: </b></TD>";
		echo "<td CLASS='line_list' width='65%'>";
		echo "<input type='text' name='qtde_dias' value='$qtde_dias' size='3' maxlength='5'>";
		echo "<font color='#489191' size='1' face='verdana'> (*) Obrigatório para status 'EM CREDENCIAMENTO'</font>";
		echo "</td>";
		echo "</tr>";
		echo "<tr><td CLASS='line_list' width='35%'><B>OBSERVAÇÕES: </B></td>";
		echo "<td width='65%'><textarea name='texto' rows='3' cols='50'>$texto</textarea></td></tr>";
		echo "<tr>";
		echo "<td colspan='2'>";
		echo "<center>";
		echo "<input type='submit' name='btn_acao' value='Credenciar'>";
		echo "<input type='submit' name='btn_acao' value='Em Credenciamento'>";
		echo "</center>";
		echo "</td>";
		echo "</tr>";
	} else if ($tipo_credenciamento == 'REPROVADO') {

		echo "<tr>";
		echo "<td align='center' class='menu_top' colspan='4'>CREDENCIAR / COLOCAR EM CREDENCIAMENTO	</td>";
		echo "</tr>";
		echo "<tr>";
		echo "<td CLASS='line_list' width='35%'><b>QTDE DIAS PARA CREDENCIAR: </b></TD>";
		echo "<td CLASS='line_list' width='65%'>";
		echo "<input type='text' name='qtde_dias' value='$qtde_dias' size='3' maxlength='5'>";
		echo "<font color='#489191' size='1' face='verdana'> (*) Obrigatório para status 'EM CREDENCIAMENTO'</font>";
		echo "</td>";
		echo "</tr>";
		echo "<tr><td CLASS='line_list' width='35%'><B>OBSERVAÇÕES: </B></td>";
		echo "<td width='65%'><textarea name='texto' rows='3' cols='50'>$texto</textarea></td></tr>";
		echo "<tr>";
		echo "<td colspan='2'>";
		echo "<center>";
		echo "<input type='submit' name='btn_acao' value='Credenciar'>";
		echo "<input type='submit' name='btn_acao' value='Em Credenciamento'>";
		echo "</center>";
		echo "</td>";
		echo "</tr>";

	} else if ($tipo_credenciamento == 'EM CREDENCIAMENTO' OR $tipo_credenciamento == 'EM DESCREDENCIAMENTO'){
		if($login_fabrica==45 AND $tipo_credenciamento == 'EM CREDENCIAMENTO'){ //HD 50730 19/11/2008
			echo "<tr>";
			echo "<tr>";
			echo "<td align='center' class='menu_top' colspan='4'>DESCREDENCIAR / COLOCAR EM DESCREDENCIAMENTO	</td>";
			echo "</tr>";
			echo "<tr>";
			echo "<td CLASS='line_list' width='35%'><b>QTDE DIAS PARA DESCREDENCIAR: </b></TD>";
			echo "<td CLASS='line_list'width='65%'><input type='text' name='qtde_dias' value='$qtde_dias' size='3' maxlength='5'><font color='#489191' size='1' face='verdana'> (*) Obrigatório para status 'EM DESCREDENCIAMENTO'</font></td>";
			echo "</tr>";
			echo "<tr><td CLASS='line_list' width='35%'><B>OBSERVAÇÕES: </B></td>";
			echo "<td  width='65%'><textarea name='texto' rows='3' cols='50'>$texto</textarea></td></tr>";
			//---------------------------------------
			echo "<tr>";
			echo "<td colspan='4'>";
			echo "<center>";
			echo "<input type='submit' name='btn_acao' value='Credenciar'>";
			echo "<input type='submit' name='btn_acao' value='Em Descredenciamento'>";
			echo "<input type='submit' name='btn_acao' value='Descredenciar'>";
			echo "</center>";
			echo "</td>";
			echo "</tr>";
		}else if($login_fabrica==45 AND $tipo_credenciamento == 'EM DESCREDENCIAMENTO'){
			echo "<tr>";
			echo "<tr>";
			echo "<td align='center' class='menu_top' colspan='4'>DESCREDENCIAR / COLOCAR EM DESCREDENCIAMENTO	</td>";
			echo "</tr>";
			echo "<tr>";
			echo "<td CLASS='line_list' width='35%'><b>QTDE DIAS PARA DESCREDENCIAR: </b></TD>";
			echo "<td CLASS='line_list'width='65%'><input type='text' name='qtde_dias' value='$qtde_dias' size='3' maxlength='5'><font color='#489191' size='1' face='verdana'> (*) Obrigatório para status 'EM DESCREDENCIAMENTO'</font></td>";
			echo "</tr>";
			echo "<tr><td CLASS='line_list' width='35%'><B>OBSERVAÇÕES: </B></td>";
			echo "<td  width='65%'><textarea name='texto' rows='3' cols='50'>$texto</textarea></td></tr>";
			//---------------------------------------
			echo "<tr>";
			echo "<td colspan='4'>";
			echo "<center>";
			echo "<input type='submit' name='btn_acao' value='Credenciar'>";
			echo "<input type='submit' name='btn_acao' value='Em Credenciamento'>";
			echo "<input type='submit' name='btn_acao' value='Descredenciar'>";
			echo "</center>";
			echo "</td>";
			echo "</tr>";
		}else{
			echo "<tr>";
			echo "<td colspan='4'>";
			echo "<center>";
			echo "<input type='submit' name='btn_acao' value='Credenciar'>";
			echo "<input type='submit' name='btn_acao' value='Descredenciar'>";
			echo "</center>";
			echo "</td>";
			echo "</tr>";
		}
	}
	echo "</table>";

}
#...................................      BUSCA PELO COMBOBOX     .................................#
else if ($listar == 2){

	$sql = "SELECT	tbl_posto_fabrica.posto         ,
					tbl_posto_fabrica.codigo_posto  ,
					tbl_posto.nome                  ,
					tbl_posto_fabrica.contato_cidade as cidade,
					tbl_posto_fabrica.contato_estado as estado,
					tbl_posto_fabrica.contato_fone_comercial as fone,
					tbl_posto.cnpj                  ,
					tbl_posto_fabrica.credenciamento,
					(SELECT to_char(tbl_credenciamento.data,'DD/MM/YYYY') from tbl_credenciamento where tbl_credenciamento.posto = tbl_posto_fabrica.posto order by data DESC limit 1) AS data
			FROM	tbl_posto_fabrica
			JOIN	tbl_posto ON tbl_posto.posto     = tbl_posto_fabrica.posto
			WHERE	tbl_posto_fabrica.fabrica        = $login_fabrica
			AND		tbl_posto_fabrica.credenciamento = '$tipo_credenciamento'
			ORDER BY tbl_posto.nome         ";
	$res = pg_exec($con,$sql);


	if (pg_numrows($res) > 0){
		echo "<table width='650' border='0' cellspacing='3' cellpadding='1' align='center' name='relatorio' id='relatorio' class='tablesorter2'>";
			echo "<thead>";
				echo "<tr>";
				if($login_fabrica == 19)echo "<td class='menu_top'>CNPJ</td>";
				echo "<td class='menu_top'>POSTO</td>";
				echo "<td class='menu_top'>CIDADE</td>";
				echo "<td class='menu_top'>ESTADO</td>";
				echo "<td class='menu_top'>FONE</td>";
				if ($login_fabrica == 50) echo "<td class='menu_top' width='15%'>DATA</td>";
				echo "</tr>";
			echo "</thead>";
			echo "<tbody>";

		for ($i=0; $i < pg_numrows($res); $i++){
			$posto      = pg_result($res,$i,posto);
			$posto_nome = pg_result($res,$i,nome);
			$cidade     = pg_result($res,$i,cidade);
			$estado     = pg_result($res,$i,estado);
			$fone       = pg_result($res,$i,fone);
			$tipo_credenciamento = pg_result($res,$i,credenciamento);
			$codigo       = pg_result($res,$i,codigo_posto);
			$cnpj         = pg_result($res,$i,cnpj);
			$data         = pg_result($res,$i,data);
			
			echo "<tr>";
			if($login_fabrica == 19)echo "<td class='table_line_2' align='left'>$cnpj</td>";
			echo "<td class='table_line_2' align='left'><a href='credenciamento.php?posto=$posto&codigo=$codigo&listar=1'>$posto_nome</a></td>";
			echo "<td class='table_line_2' align='left'>$cidade</td>";
			echo "<td class='table_line_2' align='center'>$estado</td>";
			echo "<td class='table_line_2' align='center'>$fone</td>";
			if($login_fabrica == 50)echo "<td class='table_line_2' align='left'>$data</td>";
			echo "</tr>";
			
		}
		# HD 32761 - Francisco Ambrozio (20/8/08)
		#   Incluído total de postos para Colormaq
		echo "</tbody>";
		if ($login_fabrica == 50){
				$totaldepostoc = pg_numrows($res);
				echo "<tr>";
				echo "<td class='menu_top' colspan='4'>TOTAL DE POSTOS $tipo_credenciamento";
				echo "S </td>";
				echo "<td class='table_line_2' align='center'><strong>$totaldepostoc</strong></td>";
				echo "</tr>";
				
			}
		echo "</table>";
	}
}
?>

<p>
</form>

<? include "rodape.php"; ?>
<?
/*$sql = "UPDATE tbl_posto_fabrica SET
				credenciamento = 'CREDENCIADO'
		WHERE  posto = 550
		AND    fabrica = 3";
$res = pg_exec($con,$sql);*/
?>