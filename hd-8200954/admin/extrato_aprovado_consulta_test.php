<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'funcoes.php';

$admin_privilegios="financeiro";
include 'autentica_admin.php';

$msg_erro = "";
if (strlen($_GET["pendencia"])  > 0 and 1==2){
$pendencia= $_GET["pendencia"];
$sql = "UPDATE tbl_extrato_status
				set pendente = 'f',
					confirmacao_pendente = 'f'
		WHERE extrato=$pendencia
		and pendente = 't'
		and confirmacao_pendente = 't'";
$res = pg_query($con,$sql);

}
/*HD 15001 - Não contar mais OS*/
if($ajax=='conta'){
			$sql = "SELECT count(*) as qtde_os FROM tbl_os_extra WHERE extrato = $extrato";
			$rres = pg_query($con,$sql);
			if(pg_num_rows($rres)>0){
				$qtde_os = pg_fetch_result($rres,0,qtde_os);
			}
			echo "ok|$qtde_os";
			exit;
}
$desbloquear = $_GET['desbloquear'];
$bloquear = $_GET['bloquear'];
if(strlen($bloquear)>0 or strlen($desbloquear)>0){
	if(strlen($bloquear)>0){
		$acao ="'t'";
		$extrato = $bloquear;
	}
	if(strlen($desbloquear)>0){
		$acao ="'f'";
		$extrato = $desbloquear;
	}
	$sql = "UPDATE tbl_extrato set bloqueado = $acao
			where extrato = $extrato
			and fabrica = $login_fabrica";
	$res = pg_query($con,$sql);

	if($acao =="'t'"){
		$xsql = "SELECT tbl_posto.email, tbl_posto_fabrica.codigo_posto from tbl_posto join tbl_extrato using(posto) join tbl_posto_fabrica on tbl_posto.posto = tbl_posto_fabrica.posto and tbl_posto_fabrica.fabrica= $login_fabrica where extrato = $extrato";
		$xres = pg_query($con,$xsql);
		$xemail_posto = pg_fetch_result($xres,0,email);
		$xcodigo_posto= pg_fetch_result($xres,0,codigo_posto);

		$xsql = "SELECT protocolo from tbl_extrato where extrato = $extrato";
		$xres = pg_query($con,$xsql);
		$xprotocolo = pg_fetch_result($xres,0,protocolo);

		$xsql = "SELECT nome_completo, fone, email from tbl_admin where admin=$login_admin and fabrica=$login_fabrica";
		$xres = pg_query($con,$xsql);
		$xnome_completo = pg_fetch_result($xres,0,nome_completo);
		$xfone = pg_fetch_result($xres,0,fone);
		$xemail_admin = pg_fetch_result($xres,0,email);
		$xemail_admin = "fabiola.oliveira@bdk.com";

		$remetente    = "Black&Decker <$xemail_admin>";
		$destinatario = "$xemail_posto";
		$assunto      = "Posto $xcodigo_posto seu extrato $xprotocolo foi bloqueado";

		$msg_email = "Prezado Assistente,<BR><BR>Recebemos a documentação referente ao extrato $xprotocolo, porém temos extrato(s) gerado(s) anteriormente para o seu posto que ainda estão em aberto no sistema. Gentileza entrar no link extrato e verificar os extratos com o status Pendente / Aguardando documentação.
		<BR><BR>
		O extrato $xprotocolo será bloqueado até recebermos um posicionamento sobre os outros extratos em aberto.
		<BR><BR>Obrigada,
		<BR><BR>
		Fabíola Oliveira<BR>
		Analista de Garantia<BR>
		Black & Decker do Brasil Ltda<BR>
		e-mail: fabiola.oliveira@bdk.com<BR>
		fone: (34)3318-3921 fax: (34)3318-3018";
		$headers="Return-Path: <$xemail_admin>\r\nFrom:".$remetente."\r\nBcc:$xemail_admin \r\nContent-type: text/html\n";

		if ( mail($destinatario,$assunto,$msg_email,$headers) ) {
			/*echo "<script language='JavaScript'>\n";
			echo "window.close();";
			echo "</script>";		*/
		}else{
			echo "erro";
		}
	}


}
# Pesquisa pelo AutoComplete AJAX
$q = strtolower($_GET["q"]);
if (isset($_GET["q"])){
	$tipo_busca = $_GET["busca"];

	if (strlen($q)>3){
		$sql = "SELECT tbl_posto.cnpj, tbl_posto.nome, tbl_posto_fabrica.codigo_posto
				FROM tbl_posto
				JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
				WHERE tbl_posto_fabrica.fabrica = $login_fabrica ";

		if ($tipo_busca == "codigo"){
			$sql .= " AND tbl_posto_fabrica.codigo_posto = '$q' ";
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
				echo "$codigo_posto|$nome|$cnpj";
				echo "\n";
			}
		}
	}
	exit;
}


if (strlen($_POST["btnacao"]) > 0) $btnacao = trim(strtolower($_POST["btnacao"]));
if (strlen($_GET["btnacao"])  > 0) $btnacao = trim(strtolower($_GET["btnacao"]));

if (strlen($_POST["posto"]) > 0) $posto = $_POST["posto"];
if (strlen($_GET["posto"])  > 0) $posto = $_GET["posto"];

if($btnacao=="filtrar"){
	if (strlen(trim($_POST["data_inicial"])) > 0) $data_inicial = trim($_POST["data_inicial"]);
	if (strlen(trim($_GET["data_inicial"])) > 0)  $data_inicial = trim($_GET["data_inicial"]);
	if (strlen(trim($_POST["data_final"])) > 0) $data_final = trim($_POST["data_final"]);
	if (strlen(trim($_GET["data_final"])) > 0)  $data_final = trim($_GET["data_final"]);

	if(strlen($data_inicial) > 0 AND strlen($data_final) > 0){
	//Início Validação de Datas
		if(strlen($msg_erro)==0){
			$dat = explode ("/", $data_inicial );//tira a barra
				$d = $dat[0];
				$m = $dat[1];
				$y = $dat[2];
				if(!checkdate($m,$d,$y)) $msg_erro = "Data Inválida";
		}
		if(strlen($msg_erro)==0){
			$dat = explode ("/", $data_final );//tira a barra
				$d = $dat[0];
				$m = $dat[1];
				$y = $dat[2];
				if(!checkdate($m,$d,$y)) $msg_erro = "Data Inválida";
		}
		if(strlen($msg_erro)==0){
			$d_ini = explode ("/", $data_inicial);//tira a barra
			$x_data_inicial = "$d_ini[2]-$d_ini[1]-$d_ini[0]";//separa as datas $d[2] = ano $d[1] = mes etc...


			$d_fim = explode ("/", $data_final);//tira a barra
			$x_data_final = "$d_fim[2]-$d_fim[1]-$d_fim[0]";//separa as datas $d[2] = ano $d[1] = mes etc...

			if($x_data_final < $x_data_inicial){
				$msg_erro = "Data Inválida.";
			}

			

			//Fim Validação de Datas
		}
	}
	else{
		$msg_erro = "Data Inválida";
	}
}
##### Pesquisa de produto #####
if (strlen(trim($_POST["posto_codigo"])) > 0) $posto_codigo  = trim($_POST["posto_codigo"]);
if (strlen(trim($_GET["posto_codigo"])) > 0)  $posto_codigo  = trim($_GET["posto_codigo"]);
if (strlen(trim($_POST["posto_nome"])) > 0)   $posto_nome    = trim($_POST["posto_nome"]);
if (strlen(trim($_GET["posto_nome"])) > 0)    $posto_nome    = trim($_GET["posto_nome"]);
if (strlen($posto_codigo) > 0 || strlen($posto_nome) > 0) {
	$sql =	"SELECT tbl_posto_fabrica.codigo_posto,
					tbl_posto.nome                ,
					tbl_posto.posto
			FROM tbl_posto
			JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
									AND tbl_posto_fabrica.fabrica = $login_fabrica
			WHERE tbl_posto_fabrica.fabrica = $login_fabrica";
	if (strlen($posto_codigo) > 0) $sql .= " AND   tbl_posto_fabrica.codigo_posto = '$posto_codigo'";
	if (strlen($posto_nome) > 0)   $sql .= " AND   tbl_posto.nome ILIKE '%$posto_nome%';";

	$res = pg_query($con,$sql);
	if (pg_num_rows($res) == 1) {
		$posto        = pg_fetch_result($res,0,posto);
		$posto_codigo = pg_fetch_result($res,0,codigo_posto);
		$posto_nome   = pg_fetch_result($res,0,nome);
	}else{
		$msg_erro .= " Posto não encontrado. ";
	}
}


if (strlen($_GET["aprovar"]) > 0) $aprovar = $_GET["aprovar"]; // é o numero do extrato

$btn_aprova = $_POST['btn_aprova'];
if(strlen($btn_aprova)>0){
$aprovar             = $_POST['extrato_aprovado'];

}

if (strlen($aprovar) > 0 AND $login_fabrica == 1) {
	$sql = "select extract(DOW from current_timestamp ) as data";
	$res = pg_query($con,$sql);
	$data = pg_fetch_result($res,0,data);
	$day = "1 day";
	if ($data == 5) { $day = "3 day";}
	if ($data == 6) { $day = "2 day";}
	$sql = "INSERT INTO tbl_extrato_financeiro (
				extrato   ,
				valor     ,
				data_envio
			) VALUES (
				$aprovar,
				(
					SELECT to_char(tbl_extrato.total, '999999990.99') AS total
					FROM   tbl_extrato
					WHERE  tbl_extrato.extrato = $aprovar
					AND    tbl_extrato.fabrica = $login_fabrica
				)::float ,
				current_timestamp + interval '$day'
			);";
// foi inserido conforme o chamado 592 Help Desk para acrescentar 1 dia na data da aprovação do extrato
	$res = pg_query($con,$sql);
	$msg_erro = pg_errormessage($con);
	//echo $sql;
	$nf_autorizado       = $_POST['nf_autorizado'];
//	echo "<BR>asasd = > $aprovar - $nf_autorizado<BR>";

	$sql = "UPDATE tbl_extrato_extra SET
			nota_fiscal_mao_de_obra = '$nf_autorizado'
			WHERE extrato = $aprovar";
	$res = pg_query($con,$sql);
	$msg_erro = pg_errormessage($con);


}

$layout_menu = "financeiro";
$title = "CONSULTA E MANUTENÇÃO DE EXTRATOS APROVADOS";

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

.subtitulo{

	background-color: #7092BE;
	font:bold 11px Arial;
	color: #FFFFFF;
}

table.tabela tr td{
	font-family: verdana;
	font-size: 11px;
	border-collapse: collapse;
	border:1px solid #596d9b;
}


</style>

<script language="JavaScript">
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

<? include "javascript_pesquisas.php"; ?>


<? include "javascript_calendario.php"; //adicionado por Fabio 27-09-2007 ?>

<script type="text/javascript" charset="utf-8">
	$(function(){
		$('#data_inicial').datePicker({startDate:'01/01/2000'});
		$('#data_final').datePicker({startDate:'01/01/2000'});
		$("#data_inicial").maskedinput("99/99/9999");
		$("#data_final").maskedinput("99/99/9999");
	});
</script>

<script type='text/javascript' src='js/jquery.autocomplete.js'></script>
<link rel="stylesheet" type="text/css" href="js/jquery.autocomplete.css" />
<script type='text/javascript' src='js/jquery.bgiframe.min.js'></script>
<script type='text/javascript' src='js/dimensions.js'></script>

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
			/*HD 15001 - Não contar mais OS*/
function conta_os(extrato,div) {
	var ref = document.getElementById(div);
	ref.innerHTML = "Espere...";
	url = "<?=$PHP_SELF?>?ajax=conta&extrato="+extrato;
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
						ref.innerHTML = response[1];
				}
			}
		}
	}
	http_forn[curDateTime].send(null);
}

</script>

<!--
<script language="javascript" src="js/cal2.js"></script>
<script language="javascript" src="js/cal_conf2.js"></script>
-->

<?
$posto_nome   = $_POST['posto_nome'];
if (strlen($_GET['posto_nome']) > 0) $posto_nome = $_GET['posto_nome'];
if (strlen($_GET['razao']) > 0) $posto_nome = $_GET['razao'];

$posto_codigo = $_POST['posto_codigo'];
if (strlen($_GET['posto_codigo']) > 0) $posto_codigo = $_GET['posto_codigo'];
if (strlen($_GET['cnpj']) > 0) $posto_codigo = $_GET['cnpj'];

if (strlen($msg_erro) > 0) {
	echo "<table width='700' align='center' border='0' cellspacing='1' cellpadding='1' >";
	echo "<tr class='msg_erro'>";
	echo "<td>" . $msg_erro . "</td>";
	echo "</tr>";
	echo "</table>";
	
}
echo "<FORM METHOD='GET' NAME='frm_extrato' ACTION=\"$PHP_SELF\">";
echo "<input type='hidden' name='btnacao' value=''>";

echo "<TABLE width='700' align='center' border='0' cellspacing='1' cellpadding='1' class='formulario' >\n";
echo"<tr class='titulo_tabela'><td colspan='4'>Parâmetros de Pesquisa</td></tr>";
echo "<TR class='subtitulo'>\n";
echo "	<TD COLSPAN='4' ALIGN='center'>";
echo "		Postos com Extratos Fechados por Período";
echo "	</TD>";
echo "<TR>\n";

echo "<TR>\n";
echo "<TD width='180'>&nbsp;</TD>";
echo "	<TD width='130'   valign='bottom'>Data Inicial<br>";

echo "	<INPUT size='12' maxlength='10' TYPE='text' NAME='data_inicial' id='data_inicial' value='$data_inicial' class='frm'>\n";
echo "	</TD>\n";

echo "	<TD width='130' colspan='2' valign='bottom'>Data Final <br>";

echo "	<INPUT size='12' maxlength='10' TYPE='text' NAME='data_final' id='data_final' value='$data_final' class='frm'>\n";
echo "</TD>\n";
echo "</TR>\n";
echo "<tr><td colspan='4'>&nbsp;</td></tr>";
echo "<TR class='subtitulo'>\n";
echo "	<TD COLSPAN='4' ALIGN='center'>";
echo "		Somente Extratos do Posto";
echo "	</TD>";
echo "<TR>\n";

echo "<TR >\n";
echo "<TD width='80'>&nbsp;</TD>";
echo "	<TD >";
echo "Código do Posto <br> ";
echo "<input type='text' name='posto_codigo' id='posto_codigo' size='12' value='$posto_codigo' class='frm'>";
echo "<img src='imagens/lupa.png' style='cursor: pointer;' align='absmiddle' alt='Clique aqui para pesquisar postos pelo código' onclick=\"javascript: fnc_pesquisa_posto (document.frm_extrato.posto_codigo, document.frm_extrato.posto_nome,'codigo');\">";
echo "</TD>";
echo "<TD colspan='2'>";
echo "Nome do Posto <br>";
echo "<input type='text' name='posto_nome' id='posto_nome' size='30' value='$posto_nome' class='frm'>";
echo "<img src='imagens/lupa.png' style='cursor: pointer;'' align='absmiddle' alt='Clique aqui para pesquisas postos pelo nome' onclick=\"javascript: fnc_pesquisa_posto (document.frm_extrato.posto_codigo, document.frm_extrato.posto_nome,'nome');\">";
//echo "		<input type='text' name='posto_nome' size='45' value='$posto_nome' class='frm'>&nbsp;<img src='imagens/btn_buscar5.gif' border='0' align='absmiddle' onclick=\"javascript: fnc_pesquisa_posto (document.frm_extrato.posto_nome,document.frm_extrato.posto_codigo,'nome')\" style='cursor: pointer;'>";
echo "</TD>";
echo "<TR>\n";
echo "<TR>";
echo "<TD align='center' colspan='4'>";

echo "<br><input type=\"button\" style=\"background:url(imagens_admin/btn_filtrar.gif); width:75px; cursor:pointer;\" value=\"&nbsp;\"
 onclick=\"javascript: document.frm_extrato.btnacao.value='filtrar' ; document.frm_extrato.submit() \" ALT=\"Filtrar extratos\" border='0' >\n";
echo "</TD>";
echo "</TR>";
echo "</TABLE>\n";



echo "</form>";
echo "<br />";

// INICIO DA SQL
if (strlen($posto) > 0 OR (strlen($x_data_inicial) > 0 and strlen($x_data_final) > 0) ) {
	$sql = "SELECT  tbl_posto.posto                                                ,
					tbl_posto.nome                                                 ,
					tbl_posto.cnpj                                                 ,
					tbl_posto_fabrica.codigo_posto                                 ,
					tbl_tipo_posto.descricao                       AS tipo_posto   ,
					tbl_extrato.extrato                                            ,
					tbl_extrato.aprovado                                           ,
					tbl_extrato_extra.nota_fiscal_mao_de_obra                      ,
					LPAD(tbl_extrato.protocolo,6,'0')              AS protocolo    ,
					TO_CHAR(tbl_extrato.data_geracao,'dd/mm/yy')   AS data_geracao ,
					tbl_extrato.bloqueado                                          ,
					tbl_extrato.pecas                                              ,
					tbl_extrato.mao_de_obra                                        ,
					tbl_extrato.avulso                                             ,
					tbl_extrato.total
			FROM      tbl_extrato
			JOIN      tbl_posto              ON tbl_posto.posto           = tbl_extrato.posto
			JOIN      tbl_posto_fabrica      ON tbl_extrato.posto         = tbl_posto_fabrica.posto
											AND tbl_extrato.fabrica       = tbl_posto_fabrica.fabrica
											AND tbl_posto_fabrica.fabrica = $login_fabrica
			JOIN      tbl_tipo_posto         ON tbl_tipo_posto.tipo_posto = tbl_posto_fabrica.tipo_posto
											AND tbl_tipo_posto.fabrica    = $login_fabrica
			JOIN      tbl_extrato_extra      ON tbl_extrato_extra.extrato = tbl_extrato.extrato
			LEFT JOIN tbl_extrato_financeiro ON tbl_extrato.extrato       = tbl_extrato_financeiro.extrato
			WHERE     tbl_extrato.fabrica = $login_fabrica
			AND       tbl_extrato.aprovado NOTNULL
			AND       tbl_extrato_financeiro.data_envio IS NULL";

	if (strlen($x_data_inicial) > 0 && strlen ($x_data_final) > 0)
	$sql .= " AND tbl_extrato.data_geracao BETWEEN '$x_data_inicial 00:00:00' AND '$x_data_final 23:59:59'";

	if (strlen($posto) > 0) $sql .= " AND tbl_extrato.posto = $posto ";

	if ($login_fabrica <> 1) $sql .= " ORDER BY tbl_posto.nome, tbl_extrato.data_geracao";
	else                     $sql .= " ORDER BY tbl_posto_fabrica.codigo_posto, tbl_extrato.data_geracao";
#if ($ip == '201.0.9.216') { echo nl2br($sql); exit; }
	$res = pg_query ($con,$sql);

	if (pg_num_rows ($res) == 0) {
		echo "<center><h2>Nenhum extrato encontrado</h2></center>";
	}else{

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
			$posto   = trim(pg_fetch_result($res,$i,posto));
			$codigo_posto   = trim(pg_fetch_result($res,$i,codigo_posto));
			$nome           = trim(pg_fetch_result($res,$i,nome));
if($login_fabrica==1)$nome = substr($nome,0,15);
			$tipo_posto     = trim(pg_fetch_result($res,$i,tipo_posto));
			$extrato        = trim(pg_fetch_result($res,$i,extrato));
			$data_geracao   = trim(pg_fetch_result($res,$i,data_geracao));
			//$qtde_os        = trim(pg_fetch_result($res,$i,qtde_os));
			$total          = trim(pg_fetch_result($res,$i,total));
			$extrato        = trim(pg_fetch_result($res,$i,extrato));
			$total	        = number_format ($total,2,',','.');
			$aprovado       = trim(pg_fetch_result($res,$i,aprovado));
			$protocolo      = trim(pg_fetch_result($res,$i,protocolo));
			$nf_mobra       = trim(pg_fetch_result($res,$i,nota_fiscal_mao_de_obra));
			$pecas          = trim(pg_fetch_result($res,$i,pecas));
			$mao_de_obra    = trim(pg_fetch_result($res,$i,mao_de_obra));
			$avulso         = trim(pg_fetch_result($res,$i,avulso));
			$bloqueado      = trim(pg_fetch_result($res,$i,bloqueado));

			if ($i == 0) {
				echo "<form name='Selecionar' method='post' action='$PHP_SELF'>\n";
				echo "<input type='hidden' name='btnacao' value=''>";
				echo "<table width='700' align='center' border='0' cellspacing='1' class='tabela'>\n";
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
//if ($login_fabrica == 1) echo "<td align='center' nowrap>Pendência</td>\n";
				echo "<td align='center'>Total Geral</td>\n";
				echo "<td align='center'>NF Autorizado</td>\n";
				echo "<td align='center'>Financeiro</td>\n";
				// HD 42973
				if($login_fabrica == 1) echo "<td align='center'>Valor Adicional</td>\n";
				echo "</tr>\n";
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
			echo "<td align='center' ";
			if($bloqueado == "t" and $login_fabrica == 1){
				echo " bgcolor='#FF9E5E' ";
				}
			echo "><a href='extrato_consulta_os.php?extrato=$extrato&data_inicial=$data_inicial&data_final=$data_final&cnpj=$xposto_codigo&razao=$posto_nome'"; if ($login_fabrica == 1) echo " target='_blank'"; echo ">";
			echo $protocolo;
			echo "</a></td>\n";
			echo "<td align='left'>$data_geracao</td>\n";
			/*HD 15001 - Não contar mais OS*/
			echo "<td align='center' title='Clique aqui para ver a quantidade de OS'><div id='qtde_os_$i'><a href=\"javascript:conta_os('$extrato','qtde_os_$i');\">VER</div></td>";
			$sql =	"SELECT SUM(tbl_os.pecas)       AS total_pecas     ,
							SUM(tbl_os.mao_de_obra) AS total_maodeobra ,
							tbl_extrato.avulso      AS total_avulso
					FROM tbl_os
					JOIN tbl_os_extra USING (os)
					JOIN tbl_extrato ON tbl_extrato.extrato = tbl_os_extra.extrato
					WHERE tbl_os_extra.extrato = $extrato
					GROUP BY tbl_extrato.avulso;";
			$resT = pg_query($con,$sql);
#if ($ip == '201.0.9.216') { echo nl2br($sql); exit; }

			if (pg_num_rows($resT) == 1) {
				echo "<td align='right' nowrap>R$ " . number_format(pg_fetch_result($resT,0,total_pecas),2,',','.') . " </td>\n";
				echo "<td align='right' nowrap>R$ " . number_format(pg_fetch_result($resT,0,total_maodeobra),2,',','.') . "</td>\n";
				echo "<td align='right' nowrap>R$ " . number_format(pg_fetch_result($resT,0,total_avulso),2,',','.') . "</td>\n";
			}else{
				echo "<td>&nbsp;</td>\n";
				echo "<td>&nbsp;</td>\n";
				echo "<td>&nbsp;</td>\n";
			}

			echo "<td align='right' nowrap>R$ $total</td>\n";
			echo "<td align='right' nowrap>";
			echo "<input type='text' name='nf_autorizado_$i' size='8' maxlength='20' style='font-size:10px' value='$nf_mobra'";
			if(strlen($nf_mobra)>0) echo "disabled";
			echo " class='frm'>";
			echo "</td>\n";
			echo "<td align='center' nowrap >";
			if (strlen($aprovado) > 0) {
					echo "<img src='imagens_admin/btn_aprovar_azul.gif' style=\"cursor:pointer\" onclick=\"javascript: if (document.Selecionar.btn_aprova.value == '' ) { document.Selecionar.btn_aprova.value='aprovado' ;document.Selecionar.extrato_aprovado.value='$extrato' ; document.Selecionar.nf_autorizado.value=document.Selecionar.nf_autorizado_$i.value ;  document.Selecionar.submit() } else { alert ('Aguarde submissão') } \" ALT=\"Aprovar extrato\" border='0'> ";
			}
			if($bloqueado <> "t"){
				echo " <a href='$PHP_SELF?bloquear=$extrato&btnacao=filtrar&data_inicial=$data_inicial&data_final=$data_final&posto_codigo=$posto_codigo&posto_nome=$posto_nome'><img src='imagens_admin/btn_bloquear_vermelho.gif' ALT='Bloquear o extrato'></a>";
			}else{

				echo " <a href='$PHP_SELF?desbloquear=$extrato&btnacao=filtrar&data_inicial=$data_inicial&data_final=$data_final&posto_codigo=$posto_codigo&posto_nome=$posto_nome'><img src='imagens_admin/btn_desbloquear_azul.gif' ALT='Desbloquear o extrato'></a>";
			}
			echo "</td>\n";
			if($login_fabrica == 1) {
				echo "<td>";
					echo "<a href='extrato_avulso.php?extrato=$extrato&posto=$posto'><img src='imagens/btn_adicionar_azul.gif' id='img_adicionar_$i' ALT = 'Lançar itens no extrato'></a>";
				echo "</td>\n";
			}

			echo "</tr>\n";
		}
		echo "<tr>\n";
		echo "<td colspan='7'>&nbsp;</td>\n";
		echo "<td colspan='2'>&nbsp;</td>\n";
		echo "</tr>\n";
		echo "</table>\n";
		echo "<input type='hidden' name='btn_aprova' value=''>";
		echo "<input type='hidden' name='extrato_aprovado' value=''>";
		echo "<input type='hidden' name='nf_autorizado' value=''>";
		echo "</form>\n";
	}
}

?>
<p>
<p>
<? include "rodape.php"; ?>
