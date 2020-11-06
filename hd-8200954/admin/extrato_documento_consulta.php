<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'funcoes.php';

$admin_privilegios="financeiro";
include 'autentica_admin.php';

$msg_erro = "";

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
		
		$res = pg_exec($con,$sql);
		if (pg_numrows ($res) > 0) {
			for ($i=0; $i<pg_numrows ($res); $i++ ){
				$cnpj = trim(pg_result($res,$i,cnpj));
				$nome = trim(pg_result($res,$i,nome));
				$codigo_posto = trim(pg_result($res,$i,codigo_posto));
				echo "$codigo_posto|$nome|$cnpj";
				echo "\n";
			}
		}
	}
	exit;
}

/*HD-15001 Contar OS*/
if($ajax=='conta'){
			$sql = "SELECT count(*) as qtde_os FROM tbl_os_extra WHERE extrato = $extrato";
			$rres = pg_exec($con,$sql);
			if(pg_numrows($rres)>0){
				$qtde_os = pg_result($rres,0,qtde_os);
			}
			echo "ok|$qtde_os";
			exit;
}

if (strlen($_POST["btnacao"]) > 0) $btnacao = trim(strtolower($_POST["btnacao"]));
if (strlen($_GET["btnacao"])  > 0) $btnacao = trim(strtolower($_GET["btnacao"]));

if (strlen($_POST["posto"]) > 0) $posto = $_POST["posto"];
if (strlen($_GET["posto"])  > 0) $posto = $_GET["posto"];

if (strlen($_POST["data_inicial"]) > 0) $data_inicial = $_POST["data_inicial"];
if (strlen($_GET["data_inicial"]) > 0)  $data_inicial = $_GET["data_inicial"];

if (strlen($_POST["data_final"]) > 0) $data_final = $_POST["data_final"];
if (strlen($_GET["data_final"]) > 0)  $data_final = $_GET["data_final"];


if(strlen($data_inicial) > 0){
	list($d, $m, $y) = explode("/", $data_inicial);

	if(!checkdate($m,$d,$y)) $msg_erro = "Data Inválida";
}
if(strlen($data_final) > 0){
	list($d, $m, $y) = explode("/", $data_final);

		if(!checkdate($m,$d,$y)) $msg_erro = "Data Inválida";
}

if (strlen($msg_erro)==0 and strlen($data_inicial) > 0) {
	$x_data_inicial = fnc_formata_data_pg($data_inicial);
	if (strlen($x_data_inicial) > 8) {
		$x_data_inicial = str_replace("'","",$x_data_inicial);
		$data_inicial = substr($x_data_inicial,8,2) . "/" . substr($x_data_inicial,5,2) . "/" . substr($x_data_inicial,0,4);
	}else{
		$msg_erro = " Data Inválida ";
	}
}


if (strlen($msg_erro)==0 and strlen($data_final) > 0) {
	$x_data_final = fnc_formata_data_pg($data_final);
	if (strlen($x_data_final) > 8) {
		$x_data_final = str_replace("'","",$x_data_final);
		$data_final = substr($x_data_final,8,2) . "/" . substr($x_data_final,5,2) . "/" . substr($x_data_final,0,4);
	}else{
		$msg_erro = " Data Inválida ";
	}
}

if(strlen($msg_erro)==0){
	if($x_data_inicial > $x_data_final){
		$msg_erro = " Data Inválida ";
	}
}

##### Pesquisa de posto #####
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
	
	$res = pg_exec($con,$sql);
	if (pg_numrows($res) == 1) {
		$posto        = pg_result($res,0,posto);
		$posto_codigo = pg_result($res,0,codigo_posto);
		$posto_nome   = pg_result($res,0,nome);
	}else{
		$msg_erro = " Posto não encontrado. ";
	}
}

//if (strlen(trim($_POST["ordenar"])) > 0) $ordenar  = trim($_POST["ordenar"]);

if (strlen(trim($_POST["download_pendencia"])) > 0) $download_pendencia  = trim($_POST["download_pendencia"]);


$layout_menu = "financeiro";
$title = "RELATÓRIO DE PENDÊNCIA DE EXTRATOS";

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
	font:bold 12px Arial;
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
<script type='text/javascript' src='js/jquery.autocomplete.js'></script>
<link rel="stylesheet" type="text/css" href="js/jquery.autocomplete.css" />
<script type='text/javascript' src='js/jquery.bgiframe.min.js'></script>
<script type='text/javascript' src='js/dimensions.js'></script>

<script language="JavaScript">


$().ready(function() {

	//$('#data_inicial').datePicker({startDate:'01/01/2000'});
	//$('#data_final').datePicker({startDate:'01/01/2000'});
	$("#data_inicial").maskedinput("99/99/9999");
	$("#data_final").maskedinput("99/99/9999");

	function formatItem(row) {
		return row[0] + " - " + row[1];
	}
	
	function formatResult(row) {
		return row[0];
	}
	
	/* Busca pelo Código */
	$("#posto_codigo").autocomplete("<? echo $PHP_SELF.'?busca=codigo'; ?>", {
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
	$("#posto_nome").autocomplete("<? echo $PHP_SELF.'?busca=nome'; ?>", {
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

<?
//var_dump($_POST);
$posto_nome   = $_POST['posto_nome'];
if (strlen($_GET['posto_nome']) > 0) $posto_nome = $_GET['posto_nome'];
if (strlen($_GET['razao']) > 0) $posto_nome = $_GET['razao'];

$posto_codigo = $_POST['posto_codigo'];
if (strlen($_GET['posto_codigo']) > 0) $posto_codigo = $_GET['posto_codigo'];
if (strlen($_GET['cnpj']) > 0) $posto_codigo = $_GET['cnpj'];

if (strlen($msg_erro) > 0) {
	echo "<table width='700' align='center' border='0' cellspacing='1' cellpadding='1' >";
	echo "<tr class='msg_erro'>";
	echo "<td> $msg_erro</td>";
	echo "</tr>";
	echo "</table>";
}
echo "<TABLE width='700' align='center' border='0' cellspacing='1' cellpadding='1' class='formulario'>\n";
echo "<FORM METHOD='post' NAME='frm_extrato' ACTION=\"$PHP_SELF\">";
echo "<input type='hidden' name='btnacao' value=''>";

echo "<TR class='titulo_tabela'>\n";
echo "	<TD COLSPAN='4' >";
echo "		Parâmetros de Pesquisa";
echo "	</TD>";
echo "<TR>\n";
echo "<tr><td colspan='2'>&nbsp;</td></tr>";
echo "<TR>\n";
echo "	<TD width='130' align='left' valign='bottom' style='padding-left:200px;'>Data Inicial <br />";
echo "	<INPUT size='12' maxlength='10' TYPE='text' name='data_inicial' id='data_inicial' value='$data_inicial' class='frm'>\n";
echo "	</TD>\n";
echo "	<TD align='left' valign='bottom'>Data Final <br>";
echo "	<INPUT size='12' maxlength='10' TYPE='text' name='data_final' id='data_final' value='$data_final' class='frm'>\n";
echo "</TD>\n";
echo "</TR>\n";
echo "<tr><td colspan='2'>&nbsp;</td></tr>";
echo "<TR >\n";
echo "	<TD COLSPAN='4' ALIGN='center' class='subtitulo'>";
echo "		Somente Extratos do Posto";
echo "	</TD>";
echo "<tr><td colspan='2'>&nbsp;</td></tr>";
echo "<TR>\n";

echo "<TR >\n";
echo "	<TD  ALIGN='left' style='padding-left:200px;' width='100'>";
echo "Código do Posto <br />";
echo "<input type='text' name='posto_codigo' id='posto_codigo' size='12' value='$posto_codigo' class='frm'>";
echo "<img src='imagens/lupa.png' style='cursor: pointer;' align='absmiddle' alt='Clique aqui para pesquisar postos pelo código' onclick=\"javascript: fnc_pesquisa_posto (document.frm_extrato.posto_codigo, document.frm_extrato.posto_nome,'codigo');\">";
echo "</td><td>";
echo "Nome do Posto <br />";
echo "<input type='text' name='posto_nome' id='posto_nome' size='30' value='$posto_nome' class='frm'>";
echo "<img src='imagens/lupa.png' style='cursor: pointer;'' align='absmiddle' alt='Clique aqui para pesquisas postos pelo nome' onclick=\"javascript: fnc_pesquisa_posto (document.frm_extrato.posto_codigo, document.frm_extrato.posto_nome,'nome');\">";
//echo "		<input type='text' name='posto_nome' size='45' value='$posto_nome' class='frm'>&nbsp;<img src='imagens/btn_buscar5.gif' border='0' align='absmiddle' onclick=\"javascript: fnc_pesquisa_posto (document.frm_extrato.posto_nome,document.frm_extrato.posto_codigo,'nome')\" style='cursor: pointer;'>";
echo "</TD>";
echo "</TR>\n";
echo "<tr><td colspan='2'>&nbsp;</td></tr>";


echo "<TR >\n";
echo "	<TD COLSPAN='4' ALIGN='center' class='subtitulo'>";
echo "		Download das Pendências";
echo "	</TD>";
echo "<TR>\n";

echo "<TR >\n";
echo "	<TD COLSPAN='4' ALIGN='center'>";
echo "Fazer o download das pendências em XLS";
echo "<input type='checkbox' name='download_pendencia' value='t' "; if (strlen($download_pendencia>0)) echo " CHECKED "; echo " class='frm'>";
echo "</TD>";
echo "<TR>\n";

echo "<tr>";
echo "<td colspan='4' align='center'>";
echo "<br><input type='button' style='cursor:pointer;' value='Filtrar' onclick=\"javascript: document.frm_extrato.btnacao.value='filtrar' ; document.frm_extrato.submit() \" ALT=\"Filtrar extratos\" border='0' >\n";
echo "</td>";
echo "</tr>";

echo "</TABLE>\n";



echo "</form>";


// INICIO DA SQL
if (strlen($posto) > 0 OR (strlen($x_data_inicial) > 0 and strlen($x_data_final) > 0 and strlen($msg_erro)==0) OR $download_pendencia=='t') {
	$sql = "SELECT distinct tbl_posto.posto                                                ,
					tbl_posto.nome                                                 ,
					tbl_posto.cnpj                                                 ,
					tbl_posto_fabrica.codigo_posto                                 ,
					tbl_tipo_posto.descricao                       AS tipo_posto   ,
					tbl_extrato.extrato                                            ,
					tbl_extrato.aprovado                                           ,
					tbl_extrato.bloqueado                                          ,
					tbl_extrato.mao_de_obra                                        ,
					tbl_extrato.pecas                                              ,
					tbl_extrato.avulso                                             ,
					tbl_extrato_extra.nota_fiscal_mao_de_obra                      ,
					LPAD(tbl_extrato.protocolo,6,'0')              AS protocolo    ,
					TO_CHAR(tbl_extrato.data_geracao,'dd/mm/yyyy') AS data_geracao ,
					tbl_extrato.total
			FROM      tbl_extrato
			JOIN      tbl_posto              ON tbl_posto.posto           = tbl_extrato.posto
			JOIN      tbl_posto_fabrica      ON tbl_posto_fabrica.posto   = tbl_posto.posto                 AND tbl_posto_fabrica.fabrica = $login_fabrica
			JOIN      tbl_tipo_posto         ON tbl_tipo_posto.tipo_posto = tbl_posto_fabrica.tipo_posto    AND tbl_tipo_posto.fabrica    = $login_fabrica
			JOIN      tbl_extrato_extra      ON tbl_extrato_extra.extrato = tbl_extrato.extrato
			LEFT JOIN tbl_extrato_financeiro ON tbl_extrato.extrato       = tbl_extrato_financeiro.extrato
			JOIN      tbl_extrato_status     ON tbl_extrato.extrato       = tbl_extrato_status.extrato
			WHERE     tbl_extrato.fabrica               = $login_fabrica
			AND       tbl_extrato_status.pendencia IS TRUE";

	if (strlen($x_data_inicial) > 0 && strlen ($x_data_final) > 0)
	$sql .= " AND tbl_extrato_status.data BETWEEN '$x_data_inicial 00:00:00' AND '$x_data_final 23:59:59'";

	if (strlen($posto) > 0) $sql .= " AND tbl_extrato.posto = $posto ";

/*	if ($login_fabrica <> 1) $sql .= " ORDER BY tbl_posto.nome, tbl_extrato.data_geracao";
	else                     $sql .= " ORDER BY tbl_posto_fabrica.codigo_posto, tbl_extrato.data_geracao";
*/
	if (strlen($ordenar)>0 and 1==2){
		$sql = "SELECT * 
				FROM (
					$sql
				) ORDENAR 
				ORDER BY $ordenar
				";
	}
/*if ($ip == '201.71.54.144'){ 
	echo nl2br($sql); 
	exit; 
}*/
//echo $sql;exit;
	$res = pg_exec ($con,$sql);

	if (pg_numrows ($res) == 0) {
		echo "<center><h2>Nenhum extrato encontrado</h2></center>";
	}else{

		if ($download_pendencia == 't'){

			$arquivo_conteudo = "";
			$arquivo_conteudo .= '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN" "http://www.w3.org/TR/REC-html40/loose.dtd">\n';
			$arquivo_conteudo .= "<html>";
			$arquivo_conteudo .= "<head>";
			$arquivo_conteudo .= "<title>Relatório OS sem movimentação - $data";
			$arquivo_conteudo .= "</title>";
			$arquivo_conteudo .= "<meta name='Author' content='TELECONTROL NETWORKING LTDA'>";
			$arquivo_conteudo .= "</head>";
			$arquivo_conteudo .= "<body>";
			$arquivo_conteudo .= "<h3>Extratos Pendentes</h3><br><br>";
			$arquivo_conteudo .= "";
			$arquivo_conteudo .= "<table style='border:2px solid #000000' border='1' cellspacing=2 celpadding=2>";
			$arquivo_conteudo .= "<tr style='background-color:#15388A;color:#F2F2F2'>";
			$arquivo_conteudo .= "<td><b>Código</b></td>";
			$arquivo_conteudo .= "<td><b>Nome do Posto</b></td>";
			$arquivo_conteudo .= "<td><b>Extrato</b></td>";
			$arquivo_conteudo .= "<td><b>Primeira Interação</b></td>";
			$arquivo_conteudo .= "<td><b>Última Interação</b></td>";
			$arquivo_conteudo .= "<td><b>Descrição 1ª Interação</b></td>";
			$arquivo_conteudo .= "<td><b>Descrição 2ª Interação</b></td>";
			$arquivo_conteudo .= "<td><b>Descrição 3ª Interação</b></td>";
			$arquivo_conteudo .= "<td><b>Descrição 4ª Interação</b></td>";
			$arquivo_conteudo .= "<td><b>Descrição 5ª Interação</b></td>";
			$arquivo_conteudo .= "</tr>";

			for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
				$posto          = trim(pg_result($res,$i,posto));
				$codigo_posto   = trim(pg_result($res,$i,codigo_posto));
				$nome           = trim(pg_result($res,$i,nome));
				$tipo_posto     = trim(pg_result($res,$i,tipo_posto));
				$extrato        = trim(pg_result($res,$i,extrato));
				$data_geracao   = trim(pg_result($res,$i,data_geracao));
				//$qtde_os        = trim(pg_result($res,$i,qtde_os));
				$total          = trim(pg_result($res,$i,total));
				$extrato        = trim(pg_result($res,$i,extrato));
				$total	        = number_format ($total,2,',','.');
				$aprovado       = trim(pg_result($res,$i,aprovado));
				$protocolo      = trim(pg_result($res,$i,protocolo));
				$bloqueado      = trim(pg_result($res,$i,bloqueado));
				$nf_mobra       = trim(pg_result($res,$i,nota_fiscal_mao_de_obra));

				$cor = "#FFFFFF";
				if ($i % 2 ==0 ){
					$cor = "#FBE89B";
				}

				$arquivo_conteudo .= "<tr style='background-color:$cor' >";
				$arquivo_conteudo .= "<td valign='top'>".$codigo_posto."</td>";
				$arquivo_conteudo .= "<td valign='top'>".$nome."</td>";
				#$arquivo_conteudo .= "<td>".$tipo_posto."</td>";
				$arquivo_conteudo .= "<td valign='top' align='center'>".$protocolo."</td>";

				$sql_interacoes = "SELECT to_char(data,'DD/MM/YYYY') AS data,
											obs
									FROM tbl_extrato_status
									WHERE extrato = $extrato
									ORDER BY tbl_extrato_status.data ASC ";
				$resInter = pg_exec ($con,$sql_interacoes);

				if (pg_numrows ($resInter) > 0) {
					for ($j = 0 ; $j < pg_numrows ($resInter) ; $j++) {
						$data = trim(pg_result($resInter,$j,data));
						$obs  = trim(pg_result($resInter,$j,obs));

						if ($j == 0){
							#Primeira Interação
							$arquivo_conteudo .= "<td valign='top' align='center'>".$data."</td>";

							#Ultima Interacao
							$data_ultima = trim(pg_result($resInter,pg_numrows ($resInter) - 1,data));
							$arquivo_conteudo .= "<td valign='top' align='center'>".$data_ultima."</td>";
						}
						$arquivo_conteudo .= "<td valign='top'>".$obs."</td>";
					}
				}
				if (pg_numrows ($resInter) == 0){
					$arquivo_conteudo .= "<td valign='top'>"."</td>";
					$arquivo_conteudo .= "<td valign='top'>"."</td>";
				}
				for ($x=pg_numrows ($resInter); $x<5; $x++){
					$arquivo_conteudo .= "<td valign='top'>"."</td>";
				}

				$arquivo_conteudo .= "</tr>";
			}
			$arquivo_conteudo .= "</table>";
			$arquivo_conteudo .= "</body>";
			$arquivo_conteudo .= "</html>";

			$arquivo = "xls/extrato_pendencia_" . $login_fabrica . ".xls";
			$fp = fopen ($arquivo,"w");
			fwrite ($fp,$arquivo_conteudo);
			fclose($fp);
			echo "<br><p><a href='".$arquivo."'>Clique aqui para fazer o download</a>";

		}else{

			echo "<table width='700' height=16 border='0' cellspacing='1' cellpadding='0' align='center' >";
			echo "<tr >";
			echo "<td align='center' width='16' bgcolor='#FFE1E1'>&nbsp;</td>";
			echo "<td align='left' style='font-size:14px;'>&nbsp; Extrato Avulso</td>";
			echo "</tr>";
			if($login_fabrica==1){
			
				echo "<tr >";
				echo "<td align='center' width='16' bgcolor='#FF9E5E'>&nbsp;</td>";
				echo "<td align='left' style='font-size:14px;'>&nbsp; Extrato Bloqueado</td>";
				echo "</tr>";
			}
			echo "</table>";

			for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
				$posto   = trim(pg_result($res,$i,posto));
				$codigo_posto   = trim(pg_result($res,$i,codigo_posto));
				$nome           = trim(pg_result($res,$i,nome));
				$tipo_posto     = trim(pg_result($res,$i,tipo_posto));
				$extrato        = trim(pg_result($res,$i,extrato));
				$data_geracao   = trim(pg_result($res,$i,data_geracao));
				//$qtde_os        = trim(pg_result($res,$i,qtde_os));
				$total          = trim(pg_result($res,$i,total));
				$avulso         = trim(pg_result($res,$i,avulso));
				$pecas          = trim(pg_result($res,$i,pecas));
				$mao_de_obra    = trim(pg_result($res,$i,mao_de_obra));
				$extrato        = trim(pg_result($res,$i,extrato));
				$total	        = number_format ($total,2,',','.');
				$aprovado       = trim(pg_result($res,$i,aprovado));
				$protocolo      = trim(pg_result($res,$i,protocolo));
				$bloqueado      = trim(pg_result($res,$i,bloqueado));
				$nf_mobra       = trim(pg_result($res,$i,nota_fiscal_mao_de_obra));
				/*
				$data_primeira_interacao = trim(pg_result($res,$i,data_primeira_interacao));
				$data_ultima_interacao   = trim(pg_result($res,$i,data_ultima_interacao));

				if (strlen($data_primeira_interacao)>0){
					$data_primeira_interacao = substr($data_primeira_interacao,8,2)."/".substr($data_primeira_interacao,5,2)."/".substr($data_primeira_interacao,0,4);
				}

				if (strlen($data_ultima_interacao)>0){
					$data_ultima_interacao = substr($data_ultima_interacao,8,2)."/".substr($data_ultima_interacao,5,2)."/".substr($data_ultima_interacao,0,4);
				}*/

				if ($i == 0) {
					echo "<form name='Selecionar' method='post' action='$PHP_SELF'>\n";
					echo "<input type='hidden' name='btnacao' value=''>";
					echo "<table width='100%' align='center' border='0' cellspacing='1' class='tabela'>\n";
					echo "<tr class = 'titulo_coluna'>\n";
					echo "<td align='center'>Código</td>\n";
					echo "<td align='center' nowrap>Nome do Posto</td>\n";
					echo "<td align='center'>Tipo</td>\n";
					echo "<td align='center'>Extrato</td>\n";
					echo "<td align='center'>Data</td>\n";
					echo "<td align='center' nowrap>Qtde. OS</td>\n";
					echo "<td align='center'>Total Peça</td>\n";
					echo "<td align='center'>Total MO</td>\n";
					echo "<td align='center'>Total Avulso</td>\n";
					echo "<td align='center'>Total Geral</td>\n";
					echo "<td align='center'>NF Autorizado</td>\n";
					//if ($login_fabrica == 1){
					//	echo "<td align='center'>Primeira Interação</td>\n";
					//	echo "<td align='center'>Última Interação</td>\n";
					//}

					if ($login_fabrica == 1) echo "<td align='center' nowrap>Pendência</td>\n";
					echo "</tr>\n";
				}

				$cor = ($i % 2 == 0) ? "#F7F5F0" : "#F1F4FA";

				##### LANÇAMENTO DE EXTRATO AVULSO - INÍCIO #####
				if (strlen($extrato) > 0) {
					$sql = "SELECT count(*) as existe
							FROM   tbl_extrato_lancamento
							WHERE  extrato = $extrato
							and    fabrica = $login_fabrica";
					$res_avulso = pg_exec($con,$sql);

					if (@pg_numrows($res_avulso) > 0) {
						if (@pg_result($res_avulso, 0, existe) > 0) $cor = "#FFE1E1";
					}
				}
				##### LANÇAMENTO DE EXTRATO AVULSO - FIM #####

				echo "<tr bgcolor='$cor'>\n";

				echo "<td align='left'>$codigo_posto</td>\n";
				echo "<td align='left' nowrap>".substr($nome,0,20)."</td>\n";
				echo "<td align='center' nowrap>$tipo_posto</td>\n";
				echo "<td align='center' ";
				
				if($bloqueado == "t" and strlen($nota_fiscal_mao_de_obra)==0){
					echo " bgcolor='#FF9E5E' ";
					}
				
				echo "><a href='extrato_consulta_os.php?extrato=$extrato&data_inicial=$data_inicial&data_final=$data_final&cnpj=$xposto_codigo&razao=$posto_nome'"; if ($login_fabrica == 1) echo " target='_blank'"; echo ">";
				echo $protocolo;
				echo "</a></td>\n";
				echo "<td align='left'>$data_geracao</td>\n";
				/*HD-15001 Contar OS*/
				echo "<td align='center' title='Clique aqui para ver a quantidade de OS'><div id='qtde_os_$i'><a href=\"javascript:conta_os('$extrato','qtde_os_$i');\">VER</div></td>";

	#if ($ip == '201.0.9.216') { echo nl2br($sql); exit; }
				if($login_fabrica == 1) {
					$sql =	"SELECT SUM(tbl_os.pecas)       AS total_pecas     ,
									SUM(tbl_os.mao_de_obra) AS total_maodeobra ,
									tbl_extrato.avulso      AS total_avulso
							FROM tbl_os
							JOIN tbl_os_extra USING (os)
							JOIN tbl_extrato ON tbl_extrato.extrato = tbl_os_extra.extrato
							WHERE tbl_os_extra.extrato = $extrato
							GROUP BY tbl_extrato.avulso;";
					$resT = pg_exec($con,$sql);
				}
				if (@pg_numrows($resT) == 1) {
					echo "<td align='right' nowrap>R$ " . number_format(pg_result($resT,0,total_pecas),2,',','.') . "</td>\n";
					echo "<td align='right' nowrap>R$ " . number_format(pg_result($resT,0,total_maodeobra),2,',','.') . "</td>\n";
					echo "<td align='right' nowrap>R$ " . number_format(pg_result($resT,0,total_avulso),2,',','.') . "</td>\n";
				}else{
					echo "<td>&nbsp;$pecas</td>\n";
					echo "<td>&nbsp;$mao_de_obra</td>\n";
					echo "<td>&nbsp;$avulso</td>\n";
				}
				echo "<td align='right' nowrap>R$ $total</td>\n";
				echo "<td align='right' nowrap>$nf_mobra</td>\n";
				//if ($login_fabrica == 1){
				//	echo "<td align='right' nowrap>$data_primeira_interacao</td>\n";
				//	echo "<td align='right' nowrap>$data_ultima_interacao</td>\n";
				//}
				if ($login_fabrica == 1){ 
					echo "<td><a href=\"javascript: AbrirJanelaObs('$extrato');\"><font size='1'>Abrir</font></a>";
					echo "</td>\n";
				}
				echo "</tr>\n";
				flush();
			}
		
			echo "</table>\n";
			echo "</form>\n";
		}
	}
}

?>
<p>
<p>
<? include "rodape.php"; ?>
