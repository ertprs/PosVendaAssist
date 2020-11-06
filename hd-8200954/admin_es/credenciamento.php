<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";

$admin_privilegios="cadastros,call_center";
include 'autentica_admin.php';

$msg_erro = "";

if (strlen($_POST['btn_acao']) > 0) $btn_acao = $_POST['btn_acao'];

if (strlen($_POST['posto']) > 0) $posto = $_POST['posto'];// hidden=post
if (strlen($_GET['posto']) > 0)  $posto = $_GET['posto'];

if (strlen($_GET["credenciamento"]) > 0)  $credenciamento = trim($_GET["credenciamento"]);
if (strlen($_POST["credenciamento"]) > 0) $credenciamento = trim($_POST["credenciamento"]);

if (strlen($_GET["tipo_credenciamento"]) > 0)  $tipo_credenciamento = trim($_GET["tipo_credenciamento"]);
if (strlen($_POST["tipo_credenciamento"]) > 0) $tipo_credenciamento = trim($_POST["tipo_credenciamento"]);

if ($btn_acao == "Credenciamiento"){
	$var = 'CREDENCIADO';
	$btn_acao = "Credenciar";
}else if ($btn_acao == "Descredenciar"){
	$var = "DESCREDENCIADO";
	$btn_acao = "Descredenciar";
}else if ($btn_acao == "Em Credenciamiento"){
	$var = "EM CREDENCIAMENTO";
	$btn_acao = "Em Credenciamento";
}else if ($btn_acao == "En descredenciamiento"){
	$var = "EM DESCREDENCIAMENTO";
	$btn_acao = "Em Descredenciamento";
}
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
			$msg_erro = "Informe la cantidad de días";
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
		//echo "sql:$sql";
		$res = pg_exec ($con,$sql);
		$msg_erro = pg_errormessage($con);

		if(strlen($msg_erro) == 0 ){
			$sql = "UPDATE  tbl_posto_fabrica SET
							credenciamento = '$var'
					WHERE   fabrica = $login_fabrica
					AND     posto   = $posto;";
			//echo "sql:$sql";
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

$title       = "Autorización y desautorización de servicios";
$cabecalho   = "Autorización y desautorización de servicios";
$layout_menu = "cadastro";

include 'cabecalho.php';

?>

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

<style type="text/css">

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

.table_line_2 {
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
		<td COLSPAN='2' ALIGN='CENTER'>CONSULTAR SERVICIOS</td>
	</tr>
	<tr class="table_line_2">
		<td COLSPAN='2' align='center'>
			<select name='tipo_credenciamento'>
				<option value=''>ELIJA</option>
<?
					$sql = "SELECT	distinct credenciamento 
							FROM	tbl_posto_fabrica 
							WHERE	fabrica = $login_fabrica";
					$res = pg_exec ($con,$sql);
					for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
						echo "<option value='" . pg_result ($res,$i,credenciamento) . "' " ;
						if ($tipo_credenciamento == pg_result($res,$i,credenciamento)) echo " selected ";
						echo ">";
						if(pg_result ($res,$i,credenciamento)=="CREDENCIADO"){echo "Credenciamiento";}
						if(pg_result ($res,$i,credenciamento)=="DESCREDENCIADO"){echo "Descredenciar";}
						if(pg_result ($res,$i,credenciamento)=="EM DESCREDENCIAMENTO"){echo "En descredenciamiento";}
						echo "</option>";
					}
?>
			</select>
		</td>
	</TR>
	<tr class="menu_top">
		<td>CÓDIGO</td>
		<td>NOMBRE OFICIAL SERVICIO</td>
	</tr>
	<?
	if (strlen($codigo) > 0){
		$sql = "SELECT	tbl_posto.nome                ,
						tbl_posto.posto               ,
						tbl_posto_fabrica.credenciamento
				FROM tbl_posto_fabrica
				JOIN tbl_posto ON tbl_posto_fabrica.posto = tbl_posto.posto
				WHERE tbl_posto_fabrica.codigo_posto = '$codigo'
				AND   tbl_posto.pais                 = '$login_pais'
				AND   tbl_posto_fabrica.fabrica      = $login_fabrica";
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
		<td align='center'><input type="text" name="codigo" size="14" maxlength="14" value="<? echo $codigo ?>" style="width:150px">&nbsp;<img src='../imagens/btn_buscar5.gif' style="cursor:pointer" border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_posto (document.frm_credenciamento.nome,document.frm_credenciamento.posto,document.frm_credenciamento.codigo,'codigo')"></td>
		<td align='center'><input type="text" name="nome" size="50" maxlength="60" value="<? echo $nome ?>" style="width:300px">&nbsp;<img src='../imagens/btn_buscar5.gif' style="cursor:pointer" border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_posto (document.frm_credenciamento.nome,document.frm_credenciamento.posto,document.frm_credenciamento.codigo,'nome')"></td>
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
					tbl_posto_fabrica.codigo_posto  ,
					tbl_posto.estado                ,
					tbl_posto.cidade                ,
					tbl_posto.fone                  ,
					tbl_posto_fabrica.credenciamento
			FROM tbl_posto_fabrica
			JOIN tbl_posto ON tbl_posto_fabrica.posto = tbl_posto.posto
			WHERE tbl_posto_fabrica.codigo_posto = '$codigo'
			AND   tbl_posto_fabrica.fabrica = $login_fabrica
			AND   tbl_posto.pais            = '$login_pais'";
	$res = pg_exec($con,$sql);

	$nome                = pg_result($res,0,nome);
	$codigo              = pg_result($res,0,codigo_posto);
	$estado              = pg_result($res,0,estado);
	$cidade              = pg_result($res,0,cidade);
	$fone                = pg_result($res,0,fone);
	$tipo_credenciamento = pg_result($res,0,credenciamento);

	echo "<form name='frm_credenciamento_2' method='POST' action='$PHP_SELF'>";
	echo "<input type='hidden' name='posto' value='$posto'>";
	echo "<input type='hidden' name='codigo' value='$codigo'>";
	echo "<input type='hidden' name='listar' value='1'>";

	echo "<table class='border' width='650' align='center' border='0' cellpadding='1' cellspacing='3'>";
	echo "<tr>";
	echo "<td class='menu_top' width='55%'>SERVICIO</td>";
	echo "<td class='menu_top' width='15%'>CIUDAD</td>";
	echo "<td class='menu_top' width='15%'>PROVINCIA</td>";
	echo "<td class='menu_top' width='15%'>TELÉFONO</td>";
	echo "</tr>";
	echo "<tr>";
	echo "<td class='table_line_2' align='left'>$codigo - $nome</td>";
	echo "<td class='table_line_2' align='center'>$cidade</td>";
	echo "<td class='table_line_2' align='center'>$estado</td>";
	echo "<td class='table_line_2' align='center'>$fone</td>";
	echo "</tr>";
	echo "</table>";
	echo "<br>";

	$sql = "SELECT	tbl_credenciamento.status,
					tbl_credenciamento.dias  ,
					tbl_credenciamento.texto ,
					to_char(tbl_credenciamento.data,'DD-MM-YYYY') AS data
			FROM	tbl_credenciamento
			WHERE	tbl_credenciamento.fabrica = $login_fabrica
			AND		tbl_credenciamento.posto   = $posto
			ORDER BY tbl_credenciamento.credenciamento DESC";
	$res = pg_exec($con,$sql);

	if (pg_numrows($res) > 0){
		echo "<table class='border' width='650' align='center' border='0' cellpadding='1' cellspacing='3'>";
		echo "<tr>";
		echo "<td class='menu_top' colspan='4'>HISTÓRICO PARA LOS RESULTADOS DE LOS SERVICIOS</td>";
		echo "</tr>";
		echo "<tr>";
		echo "<td class='menu_top' width='20%'>FECHA DE GENERACIÓN</td>";
		echo "<td class='menu_top' width='25%'>ESTATUS</td>";
		echo "<td class='menu_top' width='15%'>QTDE DIAS</td>";
		echo "<td class='menu_top' width='40%'>COMENTARIO</td>";
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
			AND     tbl_posto.pais = '$login_pais'
			ORDER BY tbl_credenciamento.credenciamento DESC LIMIT 1";
	$res = pg_exec($con,$sql);
//echo $sql;
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
			echo "<tr class='menu_top'><td colspan='3'> O POSTO \"$posto_nome\" DEBERÁ PERMANECER \"$status\" <br> ATÉ O DIA \"$dt_expira\" (RESTAM \"$dia_hoje\" DIAS)</td></tr>";
			echo "</table>";
		}
	}

	echo "<table class='border' width='650' align='center' border='0' cellpadding='1' cellspacing='3'>";

	if ($tipo_credenciamento == 'CREDENCIADO'){
		echo "<tr>";
		echo "<td align='center' class='menu_top' colspan='4'>DESCREDENCIAR/ PONER EN DESCREDENCIAMIENTO	</td>";
		echo "</tr>";
		echo "<tr>";
		echo "<td CLASS='line_list' width='35%'><b>CTD. DÍAS PARA DESCREDENCIAR</b></TD>";
		echo "<td CLASS='line_list'width='65%'><input type='text' name='qtde_dias' value='$qtde_dias' size='3' maxlength='5'><font color='#489191' size='1' face='verdana'> (*) Obligatorio para status 'En descredenciamiento'</font></td>";
		echo "</tr>";
		echo "<tr><td CLASS='line_list' width='35%'><B>OBSERVACIONES: </B></td>";
		echo "<td  width='65%'><textarea name='texto' rows='3' cols='50'>$texto</textarea></td></tr>";
		echo "<tr>";
		echo "<td colspan='2'>";
		echo "<center>";

		echo "<input type='submit' name='btn_acao' value='Descredenciar'>";
		echo "<input type='submit' name='btn_acao' value='En descredenciamiento'>";
		echo "</center>";
		echo "</td>";
		echo "</tr>";
	}else if ($tipo_credenciamento == 'DESCREDENCIADO'){
		echo "<tr>";
		echo "<td align='center' class='menu_top' colspan='4'>CREDENCIAR/ PONER EN CREDENCIAMINETO	</td>";
		echo "</tr>";
		echo "<tr>";
		echo "<td CLASS='line_list' width='35%'><b>CTD. DÍAS PARA CREDENCIAR: </b></TD>";
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
		echo "<input type='submit' name='btn_acao' value='Credenciamiento'>";
		echo "<input type='submit' name='btn_acao' value='Em Credenciamiento'>";
		echo "</center>";
		echo "</td>";
		echo "</tr>";
	}else if ($tipo_credenciamento == 'EM CREDENCIAMENTO' OR $tipo_credenciamento == 'EM DESCREDENCIAMENTO'){
		echo "<tr>";
		echo "<td colspan='4'>";
		echo "<center>";
		echo "<input type='submit' name='btn_acao' value='Credenciamiento'>";
		echo "<input type='submit' name='btn_acao' value='Descredenciar'>";
		echo "</center>";
		echo "</td>";
		echo "</tr>";
	}
	echo "</table>";

}
#...................................      BUSCA PELO COMBOBOX     .................................#
else if ($listar == 2){

	$sql = "SELECT	tbl_posto_fabrica.posto         ,
					tbl_posto_fabrica.codigo_posto  ,
					tbl_posto.nome                  ,
					tbl_posto.cidade                ,
					tbl_posto.estado                ,
					tbl_posto.fone                  ,
					tbl_posto_fabrica.credenciamento
			FROM	tbl_posto_fabrica
			JOIN	tbl_posto ON tbl_posto.posto     = tbl_posto_fabrica.posto
			WHERE	tbl_posto_fabrica.fabrica        = $login_fabrica
			AND		tbl_posto_fabrica.credenciamento = '$tipo_credenciamento'
			AND     tbl_posto.pais                   = '$login_pais'
			ORDER BY tbl_posto.nome         ";
	$res = pg_exec($con,$sql);

	if (pg_numrows($res) > 0){
		echo "<table class='border' width='650' align='center' border='0' cellpadding='1' cellspacing='3'>";
		echo "<tr>";
		echo "<td class='menu_top'>SERVICIO</td>";
		echo "<td class='menu_top'>CIUDAD</td>";
		echo "<td class='menu_top'>ESTADO</td>";
		echo "<td class='menu_top'>TELEFÓNO</td>";
		echo "</tr>";

		for ($i=0; $i < pg_numrows($res); $i++){
			$posto      = pg_result($res,$i,posto);
			$posto_nome = pg_result($res,$i,nome);
			$cidade     = pg_result($res,$i,cidade);
			$estado     = pg_result($res,$i,estado);
			$fone       = pg_result($res,$i,fone);
			$tipo_credenciamento = pg_result($res,$i,credenciamento);
			$codigo       = pg_result($res,$i,codigo_posto);

			echo "<tr>";
			echo "<td class='table_line_2' align='left'><a href='credenciamento.php?posto=$posto&codigo=$codigo&listar=1'>$posto_nome</a></td>";
			echo "<td class='table_line_2' align='left'>$cidade</td>";
			echo "<td class='table_line_2' align='center'>$estado</td>";
			echo "<td class='table_line_2' align='right'>$fone</td>";
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
