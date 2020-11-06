<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";

$admin_privilegios="financeiro";
include "autentica_admin.php";

include 'funcoes.php';
# Pesquisa pelo AutoComplete AJAX
$q = strtolower($_GET["q"]);
if (isset($_GET["q"])){
	$tipo_busca = $_GET["busca"];

	if (strlen($q)>3){
		$sql = "SELECT tbl_posto.cnpj,
						tbl_posto.nome,
						tbl_posto_fabrica.codigo_posto
				FROM tbl_posto
				JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
				WHERE tbl_posto_fabrica.fabrica = $login_fabrica ";

		if ($tipo_busca == "codigo"){
			$sql .= " AND (tbl_posto.cnpj = '$q' or tbl_posto_fabrica.codigo_posto='$q') ";
		}else{
			$sql .= " AND UPPER(tbl_posto.nome) like UPPER('%$q%') ";
		}

		$sql .= " LIMIT 50 ";
		$res = pg_query($con,$sql);
		if (pg_num_rows ($res) > 0) {
			for ($i=0; $i<pg_num_rows ($res); $i++ ){
				$cnpj = trim(pg_fetch_result($res,$i,cnpj));
				$nome = trim(pg_fetch_result($res,$i,nome));
				$codigo_posto = trim(pg_fetch_result($res,$i,codigo_posto));
				echo "$cnpj|$nome|$codigo_posto";
				echo "\n";
			}
		}
	}
	exit;
}

/*HD-15001 Contar OS*/
if($ajax=='conta'){
			$sql = "SELECT count(*) as qtde_os FROM tbl_os_extra WHERE extrato = $extrato";
			$rres = pg_query($con,$sql);
			if(pg_num_rows($rres)>0){
				$qtde_os = pg_fetch_result($rres,0,qtde_os);
			}
			echo "ok|$qtde_os";
			exit;
}
$msg_erro = "";

if (strlen($_POST["btnacao"]) > 0) $btnacao = trim(strtolower($_POST["btnacao"]));
if (strlen($_GET["btnacao"])  > 0) $btnacao = trim(strtolower($_GET["btnacao"]));

if (strlen($_POST["posto"]) > 0) $posto = $_POST["posto"];
if (strlen($_GET["posto"])  > 0) $posto = $_GET["posto"];

$layout_menu = "financeiro";
$title = "CONSULTA E MANUTENÇÃO DE EXTRATOS ENVIADOS AO FINANCEIRO";

include "cabecalho.php";

?>

<p>

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
	font: bold 16px "Arial";
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


</style>
<?
	include "javascript_calendario.php"; //adicionado por Fabio 27-09-2007
	include "../js/js_css.php";
?>

<script type="text/javascript" charset="utf-8">
	$(function(){
		$('#data_inicial').datepick({startDate:'01/01/2000'});
		$('#data_final').datepick({startDate:'01/01/2000'});
		$("#data_inicial").mask("99/99/9999");
		$("#data_final").mask("99/99/9999");
	});
</script>

<script type='text/javascript' src='js/jquery.autocomplete.js'></script>
<link rel="stylesheet" type="text/css" href="js/jquery.autocomplete.css" />

<script language="JavaScript">
$().ready(function() {

	function formatItem(row) {
		return row[0] + " - " + row[1];
	}

	function formatResult(row) {
		return row[0];
	}

	/* Busca pelo Código */
	$("#posto_codigo").autocomplete("<?echo $PHP_SELF.'?busca=codigo'; ?>", {
		minChars: 5,
		delay: 150,
		width: 350,
		matchContains: true,
		formatItem: formatItem,
		formatResult: function(row) {return row[0];}
	});

	$("#posto_codigo").result(function(event, data, formatted) {
		$("#posto_nome").val(data[1]) ;
	});

	/* Busca pelo Nome */
	$("#posto_nome").autocomplete("<?echo $PHP_SELF.'?busca=nome'; ?>", {
		minChars: 5,
		delay: 150,
		width: 350,
		matchContains: true,
		formatItem: formatItem,
		formatResult: function(row) {return row[1];}
	});

	$("#posto_nome").result(function(event, data, formatted) {
		$("#posto_codigo").val(data[0]) ;
		//alert(data[2]);
	});

});

/*HD-15001 Contar OS*/
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

function conta_os(extrato,div) {
	var ref = document.getElementById(div);
	var curDateTime = new Date();
	$.ajax({
		type: 'GET',
		url:  '<?=$PHP_SELF?>',
		data: 'ajax=conta&extrato='+extrato+'&data='+curDateTime,
		beforeSend: function(){
			$(ref).html("Espere...");
		},
		complete: function(resposta) {
			var response = resposta.responseText.split("|");
			if (response[0]=="ok"){
					ref.innerHTML = response[1];
			}
		}
	})
}

</script>

<script language="JavaScript">

/* ============= Função PESQUISA DE POSTOS ====================
Nome da Função : fnc_pesquisa_posto (cnpj,nome)
		Abre janela com resultado da pesquisa de Postos pela
		Código ou CNPJ (cnpj) ou Razão Social (nome).
=================================================================*/

function fnc_pesquisa_posto (campo, campo2, tipo) {
	if (tipo == "nome" ) {
		var xcampo = campo;
	}

	if (tipo == "cnpj" ) {
		var xcampo = campo2;
	}

	if (xcampo.value != "") {
		var url = "";
		url = "posto_pesquisa.php?campo=" + xcampo.value + "&tipo=" + tipo ;
		janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=600, height=300, top=0, left=0");
		janela.retorno = "<? echo $PHP_SELF ?>";
		janela.nome	= campo;
		janela.cnpj	= campo2;
		janela.posto_codigo = '';
		janela.focus();
	}
	else{
		alert("Informe toda ou parte da informação para realizar a pesquisa");
	}
}

var checkflag = "false";
function check(field) {
    if (checkflag == "false") {
        for (i = 0; i < field.length; i++) {
            field[i].checked = true;
        }
        checkflag = "true";
        return true;
    }
    else {
        for (i = 0; i < field.length; i++) {
            field[i].checked = false;
        }
        checkflag = "false";
        return true;
    }
}

function AbrirJanela (extrato) {
	var largura  = 350;
	var tamanho  = 200;
	var lar      = largura / 2;
	var tam      = tamanho / 2;
	var esquerda = (screen.width / 2) - lar;
	var topo     = (screen.height / 2) - tam;
	var link = "extrato_financeiro_envio.php?extrato=" + extrato;
	window.open(link, "janela", "toolbar=no, location=no, status=yes, menubar=no, scrollbars=no, directories=no, resizable=no, width=" + largura + ", height=" + tamanho + ", top=" + topo + ", left=" + esquerda + "");
}
function AbrirJanelaObs (extrato) {
	var largura  = 750;
	var tamanho  = 550;
	var lar      = largura / 2;
	var tam      = tamanho / 2;
	var esquerda = (screen.width / 2) - lar;
	var topo     = (screen.height / 2) - tam;
	var link = "extrato_status_aprovado.php?extrato=" + extrato;
	window.open(link, "janela", "toolbar=no, location=no, status=yes, menubar=no, scrollbars=yes, directories=no, resizable=no, width=" + largura + ", height=" + tamanho + ", top=" + topo + ", left=" + esquerda + "");
}
</script>

<?
if ($btnacao) {

    $data_inicial = $_POST['data_inicial'];

    if (strlen($_GET['data_inicial']) > 0) $data_inicial = $_GET['data_inicial'];

    $data_final   = $_POST['data_final'];
    if (strlen($_GET['data_final']) > 0) $data_final = $_GET['data_final'];

    $posto_nome   = $_POST['posto_nome'];
    if (strlen($_GET['posto_nome']) > 0) $posto_nome = $_GET['posto_nome'];
    if (strlen($_GET['razao']) > 0) $posto_nome = $_GET['razao'];

    $posto_codigo = $_POST['posto_codigo'];
    if (strlen($_GET['posto_codigo']) > 0) $posto_codigo = $_GET['posto_codigo'];
    if (strlen($_GET['cnpj']) > 0) $posto_codigo = $_GET['cnpj'];

    if ($login_fabrica == 1) {
        $tipo_data = filter_input(INPUT_POST,'tipo_data');
        if (empty($tipo_data)) {
            $msg_erro = "Escolha o tipo de data";
        }
    }

    $extrato = $_REQUEST['extrato'];

##### Validação de datas  ####
	if(empty($data_inicial) and empty($data_final) and empty($posto_codigo) and empty($extrato)) {
		$msg_erro = 'Informe o período ou posto';
	}

	if(!empty($data_inicial) and !empty($data_final)) {
		if(strlen($msg_erro)==0){
			list($di, $mi, $yi) = explode("/", $data_inicial);
			if(!checkdate($mi,$di,$yi))
				$msg_erro = "Data Inválida";
		}
		if(strlen($msg_erro)==0){
			list($df, $mf, $yf) = explode("/", $data_final);
			if(!checkdate($mf,$df,$yf))
				$msg_erro = "Data Inválida";
		}

		if(strlen($msg_erro)==0){
			$aux_data_inicial = "$yi-$mi-$di";
			$aux_data_final = "$yf-$mf-$df";
		}

		if(strlen($msg_erro)==0){
			if(strtotime($aux_data_final) < strtotime($aux_data_inicial)){
				$msg_erro = "Data Inválida.";
			}
		}
		if(strlen($msg_erro)==0){
			if (strtotime($aux_data_inicial) < strtotime($aux_data_final . ' -6 month')) {
				$msg_erro = 'O intervalo entre as datas não pode ser maior que 6 meses.';
			}
		}
	}

	if(strlen($extrato) > 0 and $login_fabrica == 1){
		$extrato = preg_replace('/\D/','',$extrato);
		$sql_posto = "select posto from tbl_extrato where fabrica = $login_fabrica and LPAD(protocolo,'6','0') = '$extrato'";
		$res_posto = pg_query($con, $sql_posto);

		if(pg_num_rows($res_posto) > 0){
			$posto = pg_fetch_result($res_posto, 0, 'posto');
			$where_posto = " and tbl_extrato.posto = $posto ";
		}else{
			$msg_erro = "Extrato não encontrado <br/>";
		}
	}
}
##### Pesquisa de produto #####
if (strlen(trim($_POST["posto_codigo"])) > 0) $posto_codigo  = trim($_POST["posto_codigo"]);
if (strlen(trim($_GET["posto_codigo"])) > 0)  $posto_codigo  = trim($_GET["posto_codigo"]);
if (strlen(trim($_POST["posto_nome"])) > 0)   $posto_nome    = trim($_POST["posto_nome"]);
if (strlen(trim($_GET["posto_nome"])) > 0)    $posto_nome    = trim($_GET["posto_nome"]);
if (strlen($posto_codigo) > 0 || strlen($posto_nome) > 0) {

	$posto_codigo = str_replace("-", "", $posto_codigo);
	$posto_codigo = str_replace("/", "", $posto_codigo);
	$posto_codigo = str_replace(".", "", $posto_codigo);

    $sql = "
        SELECT  tbl_posto_fabrica.codigo_posto,
                tbl_posto.nome                ,
                tbl_posto.posto
        FROM    tbl_posto
        JOIN    tbl_posto_fabrica   ON  tbl_posto_fabrica.posto   = tbl_posto.posto
                                    AND tbl_posto_fabrica.fabrica = $login_fabrica
        WHERE   tbl_posto_fabrica.fabrica = $login_fabrica";
	if (strlen($posto_codigo) > 0) $sql .= " AND   (tbl_posto.cnpj = '$posto_codigo' or tbl_posto_fabrica.codigo_posto='$posto_codigo')";
	if (strlen($posto_nome) > 0) $sql .= " AND   tbl_posto.nome ILIKE '%$posto_nome%';";


	$res = pg_query($con,$sql);
	if (pg_num_rows($res) == 1) {
		$posto        = pg_fetch_result($res,0,posto);
		$posto_codigo = pg_fetch_result($res,0,codigo_posto);
		$posto_nome   = pg_fetch_result($res,0,nome);
	}else{
		$msg_erro .= " Posto não encontrado. ";
	}
}
echo "<TABLE width='700' align='center' border='0' cellspacing='1' cellpadding='1' class='formulario'>\n";
if(strlen($msg_erro)>0){
	echo "<tr class='msg_erro'><td COLSPAN='4'>$msg_erro</td></tr>";
}
echo "<FORM METHOD='POST' NAME='frm_extrato'>";
echo "<input type='hidden' name='btnacao' value=''>";

echo "<TR class='titulo_tabela'>\n";
echo "	<TD COLSPAN='4' ALIGN='center'>";
echo "		Parâmetros de Pesquisa";
echo "	</TD>";
echo "</TR>\n";
if($login_fabrica == 1){
	echo "<tr>";
		echo "	<TD colspan='2' width='130' style='padding:0 0 0 130px;' >Extrato <br />";
		echo "	<INPUT size='12' maxlength='15' TYPE='text' NAME='extrato' id='extrato' value='$extrato' class='frm'> </TD>\n";
		echo "<td colspan='2'>&nbsp;</td>";
	echo "</tr>";
}
echo "<TR>\n";
echo "	<TD colspan='2' width='130' style='padding:0 0 0 130px;' >Data Inicial <br />";
echo "	<INPUT size='12' maxlength='10' TYPE='text' NAME='data_inicial' id='data_inicial' value='$data_inicial' class='frm'>\n";
echo "	</TD>\n";
echo "	<TD colspan='2' >Data Final <br />";
echo "	<INPUT size='12' maxlength='10' TYPE='text' NAME='data_final' id='data_final' value='$data_final' class='frm'>\n";
echo "</TD>\n";
echo "</TR>\n";
?>
        <tr>

            <td colspan="4" width="130" style="padding:0 0 0 130px;">
                <input type="radio" name="tipo_data" value="financeiro" <?=($tipo_data == "financeiro" or empty($tipo_data)) ? "checked" : ""?> />Data Envio ao Financeiro
                <input type="radio" name="tipo_data" value="geracao"    <?=($tipo_data == "geracao") ? "checked" : ""?> />Data Geração
            </td>
        </tr>
<?php
echo "<TR >\n";
echo "	<TD COLSPAN='2' style='padding:0 0 0 130px;' nowrap>";
echo "CNPJ/Código <br />";
echo "		<input type='text' name='posto_codigo' id='posto_codigo' size='18' value='$posto_codigo' class='frm'>&nbsp;<img src='imagens/lupa.png' border='0' align='absmiddle' style='cursor: pointer;' onclick=\"javascript: fnc_pesquisa_posto (document.frm_extrato.posto_nome,document.frm_extrato.posto_codigo,'cnpj')\">";
echo "</td>";
echo "	<TD COLSPAN='2' nowrap>";
echo "Razão Social <br />";
echo "		<input type='text' name='posto_nome' id='posto_nome' size='45' value='$posto_nome' class='frm'>&nbsp;<img src='imagens/lupa.png' border='0' align='absmiddle' onclick=\"javascript: fnc_pesquisa_posto (document.frm_extrato.posto_nome,document.frm_extrato.posto_codigo,'nome')\" style='cursor: pointer;'>";
echo "	</TD>";
echo "</TR>\n";
echo "<TR>";
echo "	<TD COLSPAN='4' align='center' style='padding:10px 0 10px 0px;'>";
echo "<input type='button' value='Filtrar' onclick=\"javascript: document.frm_extrato.btnacao.value='filtrar' ; document.frm_extrato.submit() \" ALT=\"Filtrar extratos\" border='0' style=\"cursor:pointer;\">\n";
echo "</TD>";
echo "</TR>";
echo "</TABLE>\n";
echo "</form>";
echo "<br />";
// INICIO DA SQL
$data_inicial = $_POST['data_inicial'];
if (strlen($_GET['data_inicial']) > 0)  $data_inicial = $_GET['data_inicial'];
if (strlen($_POST['data_inicial']) > 0) $data_inicial = $_POST['data_inicial'];
if (strlen($_GET['data_final']) > 0)  $data_final = $_GET['data_final'];
if (strlen($_POST['data_final']) > 0) $data_final = $_POST['data_final'];
if (strlen($_GET['cnpj']) > 0) $posto_codigo = $_GET['posto_codigo'];

$data_inicial = str_replace (" " , "" , $data_inicial);
$data_inicial = str_replace ("-" , "" , $data_inicial);
$data_inicial = str_replace ("/" , "" , $data_inicial);
$data_inicial = str_replace ("." , "" , $data_inicial);

$data_final = str_replace (" " , "" , $data_final);
$data_final = str_replace ("-" , "" , $data_final);
$data_final = str_replace ("/" , "" , $data_final);
$data_final = str_replace ("." , "" , $data_final);

if (strlen ($data_inicial) == 6) $data_inicial = substr ($data_inicial,0,4) . "20" . substr ($data_inicial,4,2);
if (strlen ($data_final)   == 6) $data_final   = substr ($data_final  ,0,4) . "20" . substr ($data_final  ,4,2);

if (strlen ($data_inicial) > 0) $data_inicial = substr ($data_inicial,0,2) . "/" . substr ($data_inicial,2,2) . "/" . substr ($data_inicial,4,4);
if (strlen ($data_final)   > 0) $data_final   = substr ($data_final,0,2)   . "/" . substr ($data_final,2,2)   . "/" . substr ($data_final,4,4);

if (!empty($btnacao) and strlen($msg_erro)==0) {

	/*SQL ANTIGO

	$sql = "SELECT  tbl_posto.posto                                                         ,
					tbl_posto.nome                                                          ,
					tbl_posto.cnpj                                                          ,
					tbl_posto_fabrica.codigo_posto                                          ,
					tbl_tipo_posto.descricao                                AS tipo_posto   ,
					tbl_extrato.extrato                                                     ,
					tbl_extrato.bloqueado                                                   ,
					tbl_extrato_extra.nota_fiscal_mao_de_obra                               ,
					TO_CHAR(tbl_extrato.aprovado,'dd/mm/yy')              AS aprovado     ,
					LPAD(tbl_extrato.protocolo,6,'0')                       AS protocolo    ,
					TO_CHAR(tbl_extrato.data_geracao,'dd/mm/yy')          AS data_geracao ,
					tbl_extrato.total                                                       ,
					(
						SELECT	count (tbl_os.os)
						FROM	tbl_os JOIN tbl_os_extra USING (os)
						WHERE tbl_os_extra.extrato = tbl_extrato.extrato
					)                                                       AS qtde_os      ,
					TO_CHAR(tbl_extrato_financeiro.data_envio,'dd/mm/yy') AS data_envio
			FROM      tbl_extrato
			JOIN      tbl_posto USING (posto)
			JOIN      tbl_posto_fabrica     ON  tbl_extrato.posto         = tbl_posto_fabrica.posto
											AND tbl_posto_fabrica.fabrica = $login_fabrica
			JOIN      tbl_tipo_posto        ON tbl_tipo_posto.tipo_posto  = tbl_posto_fabrica.tipo_posto
											AND tbl_tipo_posto.fabrica    = $login_fabrica
			JOIN      tbl_extrato_financeiro ON tbl_extrato.extrato       = tbl_extrato_financeiro.extrato
			JOIN      tbl_extrato_extra on tbl_extrato_extra.extrato = tbl_extrato.extrato
			WHERE     tbl_extrato.fabrica = $login_fabrica
			AND       tbl_extrato.aprovado NOTNULL
			AND       tbl_extrato_financeiro.data_envio NOTNULL";
*/
	$cond_data = " 1=1 ";
	$cond_posto = " 1=1 ";

	$xposto_codigo = str_replace (" " , "" , $posto_codigo);
	$xposto_codigo = str_replace ("-" , "" , $xposto_codigo);
	$xposto_codigo = str_replace ("/" , "" , $xposto_codigo);
	$xposto_codigo = str_replace ("." , "" , $xposto_codigo);

	if (strlen($posto_codigo) > 0 || strlen($posto_nome) > 0) {
		$sql =	"SELECT tbl_posto_fabrica.codigo_posto,
						tbl_posto.nome                ,
						tbl_posto.posto
				FROM tbl_posto
				JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
										AND tbl_posto_fabrica.fabrica = $login_fabrica
				WHERE tbl_posto_fabrica.fabrica = $login_fabrica";
		if (strlen ($posto_codigo) > 0 ) $sql .= " AND (tbl_posto.cnpj = '$xposto_codigo' or tbl_posto_fabrica.codigo_posto = '$xposto_codigo') ";
		if (strlen($posto_nome) > 0) $sql .= " AND   tbl_posto.nome ILIKE '%$posto_nome%';";

		$res = pg_query($con,$sql);
		if (pg_num_rows($res) == 1) {
			$posto        = pg_fetch_result($res,0,posto);
			$posto_codigo = pg_fetch_result($res,0,codigo_posto);
			$posto_nome   = pg_fetch_result($res,0,nome);
			$cond_posto= " tbl_posto.posto = $posto ";
        }else{
            $msg_erro .= " Posto não encontrado. ";
        }

	}

	if (strlen ($data_inicial) < 8) $x_data_inicial = "";
		$x_data_inicial = substr ($data_inicial,6,4) . "-" . substr ($data_inicial,3,2) . "-" . substr ($data_inicial,0,2);

	if (strlen ($data_final) < 10) $x_data_final = "";
		$x_data_final = substr ($data_final,6,4) . "-" . substr ($data_final,3,2) . "-" . substr ($data_final,0,2);

	if (strlen ($x_data_inicial) == 10 AND strlen ($x_data_final) == 10) {
        if ($login_fabrica == 1) {
            switch ($tipo_data) {
                case "geracao":
                    $join_data = "";
                    $cond_data = "tbl_extrato.data_geracao BETWEEN '$x_data_inicial 00:00:00' AND '$x_data_final 23:59:59'";
                    break;
                case "financeiro":
                    $join_data = " JOIN tbl_extrato_financeiro USING(extrato)";
                    $cond_data = " tbl_extrato_financeiro.data_envio BETWEEN '$x_data_inicial 00:00:00' AND '$x_data_final 23:59:59'";
                    break;
            }
        } else {
            $join_data = "";
            $cond_data = " tbl_extrato.data_geracao BETWEEN '$x_data_inicial 00:00:00' AND '$x_data_final 23:59:59'";
        }
	}
	$sql = "
			SELECT
			 tbl_extrato.extrato,
				tbl_extrato.bloqueado ,
				tbl_extrato.aprovado,
				tbl_extrato.protocolo,
				tbl_extrato.data_geracao,
				tbl_extrato.total ,
				tbl_extrato.posto ,
				tbl_extrato.avulso ,
				tbl_extrato.mao_de_obra,
				tbl_extrato.pecas
			into temp table tmp_extrato_consulta
			FROM tbl_extrato
			$join_data
			WHERE tbl_extrato.fabrica = $login_fabrica
				AND tbl_extrato.aprovado NOTNULL
				AND $cond_data
				$where_posto
				;

			CREATE INDEX tmp_extrato_consulta_extrato on tmp_extrato_consulta(extrato);
			CREATE INDEX tmp_extrato_consulta_posto on tmp_extrato_consulta(posto);

/* <br> */
			SELECT
				tmp_extrato_consulta.extrato,
				tmp_extrato_consulta.bloqueado ,
				tmp_extrato_consulta.aprovado ,
				tmp_extrato_consulta.protocolo,
				tmp_extrato_consulta.data_geracao,
				tmp_extrato_consulta.total ,
				tmp_extrato_consulta.avulso ,
				tmp_extrato_consulta.mao_de_obra,
				tmp_extrato_consulta.pecas,
				tbl_extrato_financeiro.data_envio,
				tbl_extrato_extra.nota_fiscal_mao_de_obra ,
				tbl_posto.posto ,
				tbl_posto.nome ,
				tbl_posto.cnpj ,
				tbl_posto_fabrica.codigo_posto,
				tbl_tipo_posto.descricao AS tipo_posto
			into temp table tmp_extrato_consulta_new
			FROM tmp_extrato_consulta
			JOIN tbl_extrato_financeiro ON tmp_extrato_consulta.extrato = tbl_extrato_financeiro.extrato
			JOIN tbl_extrato_extra on tbl_extrato_extra.extrato = tmp_extrato_consulta.extrato
			JOIN tbl_posto USING (posto)
			JOIN tbl_posto_fabrica ON tmp_extrato_consulta.posto = tbl_posto_fabrica.posto
				AND tbl_posto_fabrica.fabrica = $login_fabrica
			JOIN tbl_tipo_posto ON tbl_tipo_posto.tipo_posto = tbl_posto_fabrica.tipo_posto
				AND tbl_tipo_posto.fabrica = $login_fabrica
			WHERE tbl_extrato_financeiro.data_envio NOTNULL
					AND $cond_posto;

			CREATE INDEX tmp_extrato_consulta_new_posto on tmp_extrato_consulta_new(posto);
			CREATE INDEX tmp_extrato_consulta_new_extrato on tmp_extrato_consulta_new(extrato);
			CREATE INDEX tmp_extrato_consulta_new_data_geracao on tmp_extrato_consulta_new(data_geracao);

/* <br> */
			SELECT
				tmp_extrato_consulta_new.extrato,
				tmp_extrato_consulta_new.bloqueado ,
				TO_CHAR(tmp_extrato_consulta_new.aprovado,'dd/mm/yy') AS aprovado  ,
				LPAD(tmp_extrato_consulta_new.protocolo,6,'0') AS protocolo ,
				TO_CHAR(tmp_extrato_consulta_new.data_geracao,'dd/mm/yy') AS data_geracao ,
				tmp_extrato_consulta_new.total ,
				tmp_extrato_consulta_new.avulso ,
				tmp_extrato_consulta_new.mao_de_obra,
				tmp_extrato_consulta_new.pecas,
				tmp_extrato_consulta_new.posto ,
				tmp_extrato_consulta_new.nome ,
				tmp_extrato_consulta_new.cnpj ,
				tmp_extrato_consulta_new.codigo_posto ,
				tmp_extrato_consulta_new.tipo_posto ,
				tmp_extrato_consulta_new.nota_fiscal_mao_de_obra ,
				TO_CHAR(tmp_extrato_consulta_new.data_envio,'dd/mm/yy') AS data_envio
			FROM tmp_extrato_consulta_new
			ORDER BY
			tmp_extrato_consulta_new.nome,
			tmp_extrato_consulta_new.data_geracao;";

/* Retirado Igor HD - 13327
	$sql .= " GROUP BY tbl_posto.posto                ,
					tbl_posto.nome                    ,
					tbl_posto.cnpj                    ,
					tbl_posto_fabrica.codigo_posto    ,
					tbl_tipo_posto.descricao          ,
					tbl_extrato.extrato               ,
					tbl_extrato.bloqueado             ,
					tbl_extrato_extra.nota_fiscal_mao_de_obra                      ,
					tbl_extrato.total                 ,
					tbl_extrato.aprovado              ,
					LPAD(tbl_extrato.protocolo,6,'0') ,
					tbl_extrato.data_geracao          ,
					tbl_extrato_financeiro.data_envio
			ORDER BY tbl_posto.nome, tbl_extrato.data_geracao";
*/
	$res = pg_query ($con,$sql);


	if (pg_num_rows ($res) == 0) {
		echo "<center><h2>Nenhum extrato encontrado</h2></center>";
	}

	if (pg_num_rows ($res) > 0) {

		echo "<table width='700' height=16 border='0' cellspacing='0' cellpadding='0' align='center'>";
		echo "<tr>";
		echo "<td align='center' width='16' bgcolor='#FFE1E1'>&nbsp;</td>";
		echo "<td align='left'><font size=1><b>&nbsp; Extrato Avulso</b></font></td>";
		echo "</tr>";
		if($login_fabrica==1){

			echo "<tr>";
			echo "<td align='center' width='16' bgcolor='#FF9E5E'>&nbsp;</td>";
			echo "<td align='left'><font size=1><b>&nbsp; Extrato Bloqueado</b></font></td>";
			echo "</tr>";
		}
		echo "</table>";

		for ($i = 0 ; $i < pg_num_rows ($res) ; $i++) {
			$posto        = trim(pg_fetch_result($res,$i,posto));
			$codigo_posto = trim(pg_fetch_result($res,$i,codigo_posto));
			$nome         = trim(pg_fetch_result($res,$i,nome));
			$nome = substr($nome,0,15);
			$tipo_posto   = trim(pg_fetch_result($res,$i,tipo_posto));
			$extrato      = trim(pg_fetch_result($res,$i,extrato));
			$data_geracao = trim(pg_fetch_result($res,$i,data_geracao));
			//$qtde_os      = trim(pg_fetch_result($res,$i,qtde_os));

			$total        = trim(pg_fetch_result($res,$i,total));
			$totalTx = somaTxExtratoBlack($extrato);
			$total += $totalTx;

			$total	      = number_format($total,2,',','.');

			$total_avulso = trim(pg_fetch_result($res,$i,avulso));
			$total_avulso = number_format($total_avulso ,2,',','.');

			$total_mao_de_obra= trim(pg_fetch_result($res,$i,mao_de_obra));
			$total_mao_de_obra= number_format($total_mao_de_obra,2,',','.');

			$total_pecas= trim(pg_fetch_result($res,$i,pecas));
			$total_pecas= number_format($total_pecas,2,',','.');


			$data_envio   = trim(pg_fetch_result($res,$i,data_envio));
			$extrato      = trim(pg_fetch_result($res,$i,extrato));

			$aprovado     = trim(pg_fetch_result($res,$i,aprovado));
			$bloqueado    = trim(pg_fetch_result($res,$i,bloqueado));
			$protocolo    = trim(pg_fetch_result($res,$i,protocolo));
			$nf_mo        = trim(pg_fetch_result($res,$i,nota_fiscal_mao_de_obra));
			if ($i == 0) {
				echo "<form name='Selecionar' method='post' action='$PHP_SELF'>\n";
				echo "<input type='hidden' name='btnacao' value=''>";
				echo "<table width='700' align='center' border='0' cellspacing='1' class='tabela' >\n";
				echo "<tr class = 'titulo_coluna'>\n";
				echo "<td align='center'>Código</td>\n";
				echo "<td align='center' nowrap>Nome do Posto</td>\n";
				echo "<td align='center'>Tipo</td>\n";
				echo "<td align='center'>Extrato</td>\n";
				echo "<td align='center'>Data</td>\n";
				echo "<td align='center' nowrap>OS</td>\n";
				echo "<td align='center'>Total Peça</td>\n";
				echo "<td align='center'>Total MO</td>\n";
				echo "<td align='center'>Total Avulso</td>\n";
				echo "<td align='center'>Total Geral</td>\n";
				echo "<td align='center'>Aprovação</td>\n";
				echo "<td align='center'>NF Autor.</td>\n";
	if ($login_fabrica == 1) echo "<td align='center' nowrap>Pendência</td>\n";
				echo "<td align='center'>Financeiro</td>\n";
				echo "</tr>\n";

				$cabecalho = "
                    <thead>
                        <tr>
                            <th bgcolor='#5A6D9C'><font color='#ffffff'>Código</font></th>
                            <th bgcolor='#5A6D9C'><font color='#ffffff'>Nome do Posto</font></th>
                            <th bgcolor='#5A6D9C'><font color='#ffffff'>Tipo</font></th>
                            <th bgcolor='#5A6D9C'><font color='#ffffff'>Extrato</font></th>
                            <th bgcolor='#5A6D9C'><font color='#ffffff'>Data</font></th>
                            <th bgcolor='#5A6D9C'><font color='#ffffff'>OS</font></th>
                            <th bgcolor='#5A6D9C'><font color='#ffffff'>Total Peça</font></th>
                            <th bgcolor='#5A6D9C'><font color='#ffffff'>Total MO</font></th>
                            <th bgcolor='#5A6D9C'><font color='#ffffff'>Total Avulso</font></th>
                            <th bgcolor='#5A6D9C'><font color='#ffffff'>Total Geral</font></th>
                            <th bgcolor='#5A6D9C'><font color='#ffffff'>Aprovação</font></th>
                            <th bgcolor='#5A6D9C'><font color='#ffffff'>NF. Autor.</font></th>
                            <th bgcolor='#5A6D9C'><font color='#ffffff'>Financeiro</font></th>
                        </tr>
                    </thead>
				";
			}

			$cor = ($i % 2 == 0) ? "#F7F5F0" : "#F1F4FA";

			##### LANÇAMENTO DE EXTRATO AVULSO - INÍCIO #####
			if (strlen($extrato) > 0) {
				$sql = "SELECT count(*) as existe
						FROM   tbl_extrato_lancamento
						WHERE  extrato = $extrato
						and    fabrica = $login_fabrica";
				$res_avulso = pg_query($con,$sql);

				if (@pg_num_rows($res_avulso) > 0) {
					if (@pg_fetch_result($res_avulso, 0, existe) > 0) $cor = "#FFE1E1";
				}

			}
			##### LANÇAMENTO DE EXTRATO AVULSO - FIM #####

			echo "<tr bgcolor='$cor'>\n";

			echo "<td align='left'>$codigo_posto</td>\n";
			echo "<td align='left' nowrap>".substr($nome,0,20)."</td>\n";
			echo "<td align='center' nowrap>$tipo_posto</td>\n";
			echo "<td align='center'><a href='extrato_consulta_os.php?extrato=$extrato&data_inicial=$data_inicial&data_final=$data_final&cnpj=$xposto_codigo&razao=$posto_nome'"; if ($login_fabrica == 1) echo " target='_blank'"; echo ">";
			echo $protocolo;
			echo "</a></td>\n";
			echo "<td align='left'>$data_geracao</td>\n";
			/*HD-15001 Contar OS*/
			echo "<td align='center' title='Clique aqui para ver a quantidade de OS'><div id='qtde_os_$i'><a href=\"javascript:conta_os('$extrato','qtde_os_$i');\">VER</div></td>";

            $conteudo .= "
                <tr>
                    <td align='left'>$codigo_posto</td>
                    <td align='left' nowrap>$nome</td>
                    <td align='left' nowrap>$tipo_posto</td>
                    <td align='left' nowrap>$protocolo</td>
                    <td align='left' nowrap>$data_geracao</td>
            ";

			if($login_fabrica == 1){
                $sqlOs = "
                    SELECT COUNT(tbl_os.os) AS qtde_os
                    FROM tbl_os
                    JOIN tbl_os_extra USING (os)
                    JOIN tbl_extrato ON tbl_extrato.extrato = tbl_os_extra.extrato
                    WHERE tbl_os_extra.extrato = $extrato
                    AND tbl_os.fabrica = $login_fabrica
                ";
                $resOs = pg_query($con,$sqlOs);
				/*IGOR -HD 13327 */
					echo "<td align='right' nowrap>R$ " . number_format(pg_fetch_result($res,$i,pecas),2,',','.') . "</td>\n";
					echo "<td align='right' nowrap>R$ " . number_format(pg_fetch_result($res,$i,mao_de_obra),2,',','.') . "</td>\n";
					echo "<td align='right' nowrap>R$ " . number_format(pg_fetch_result($res,$i,avulso),2,',','.') . "</td>\n";
                $conteudo .= "
                    <td align='right' nowrap>" . pg_fetch_result($resOs,0,qtde_os). "</td>
                    <td align='right' nowrap>R$ " . number_format(pg_fetch_result($res,$i,pecas),2,',','.') . "</td>
                    <td align='right' nowrap>R$ " . number_format(pg_fetch_result($res,$i,mao_de_obra),2,',','.') . "</td>
                    <td align='right' nowrap>R$ " . number_format(pg_fetch_result($res,$i,avulso),2,',','.') . "</td>
                ";
			}else{
				$sql =	"SELECT SUM(tbl_os.pecas)       AS total_pecas     ,
								SUM(tbl_os.mao_de_obra) AS total_maodeobra ,
								tbl_extrato.avulso      AS total_avulso
						FROM tbl_os
						JOIN tbl_os_extra USING (os)
						JOIN tbl_extrato ON tbl_extrato.extrato = tbl_os_extra.extrato
						WHERE tbl_os_extra.extrato = $extrato
							AND tbl_os.fabrica = $login_fabrica
						GROUP BY tbl_extrato.avulso;";
				$resT = pg_query($con,$sql);

				if (pg_num_rows($resT) == 1) {
					echo "<td align='right' nowrap>R$ " . number_format(pg_fetch_result($resT,0,total_pecas),2,',','.') . "</td>\n";
					echo "<td align='right' nowrap>R$ " . number_format(pg_fetch_result($resT,0,total_maodeobra),2,',','.') . "</td>\n";
					echo "<td align='right' nowrap>R$ " . number_format(pg_fetch_result($resT,0,total_avulso),2,',','.') . "</td>\n";

                    $conteudo .= "

                        <td align='right' nowrap>R$ " . number_format(pg_fetch_result($res,$i,total_pecas),2,',','.') . "</td>
                        <td align='right' nowrap>R$ " . number_format(pg_fetch_result($res,$i,total_maodeobra),2,',','.') . "</td>
                        <td align='right' nowrap>R$ " . number_format(pg_fetch_result($res,$i,total_avulso),2,',','.') . "</td>
                    ";
				} else {
					echo "<td>&nbsp;</td>\n";
					echo "<td>&nbsp;</td>\n";
					echo "<td>&nbsp;</td>\n";

					$conteudo .= "
                        <td align='right' nowrap>&nbsp;</td>
                        <td align='right' nowrap>&nbsp;</td>
                        <td align='right' nowrap>&nbsp;</td>
                    ";
				}
			}
			echo "<td align='right' nowrap>R$ $total</td>\n";
			echo "<td align='center' nowrap>" . $aprovado . "</td>\n";
			echo "<td align='center' nowrap>" . $nf_mo . "</td>\n";
			if ($login_fabrica == 1){
				echo "<td ";
				if($bloqueado == "t" and strlen($data_envio)==0){
					echo "bgcolor='#FF9E5E' ";
				}
				echo "><a href=\"javascript: AbrirJanelaObs('$extrato');\"><font size='1'>Abrir</font></a>";
				echo "</td>\n";
			}
			echo "<td align='center' nowrap><a href=\"javascript: AbrirJanela('$extrato');\">" . $data_envio . "</a></td>\n";

			echo "</tr>\n";

			$conteudo .= "
                <td align='right' nowrap>R$ $total</td>
                <td align='center' nowrap>" . $aprovado . "</td>
                <td align='center' nowrap>" . $nf_mo . "</td>
                <td align='center' nowrap>$data_envio</td>
            </tr>
			";
		}

		echo "</table>\n";
		echo "</form>\n";
	}
    $arquivo = "<table border='1'>";
    $arquivo .= $cabecalho;
    $arquivo .= "<tbody>";
    $arquivo .= $conteudo;
    $arquivo .= "</tbody>";
    $arquivo .= "</table>";

    $caminho = "xls/relatorio-extrato-financeiro-$login_fabrica-".date('Y-m-d').".xls";
    $fp = fopen($caminho,"w");
    fwrite($fp,$arquivo);
    fclose($fp);

?>
<br />
<input type='button' onclick="window.open('<?=$caminho?>')" value='Download Excel' />
<?php
}
?>
<p>
<p>
<? include "rodape.php"; ?>
