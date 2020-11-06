<?php
$dbhost = "localhost";
$dbnome = "telecontrol_testes";
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios = "financeiro";

include 'autentica_admin.php';

$TITULO = "Controle de Implantação";
$layout_menu = "financeiro";
$title = $TITULO;


$btn_acao = $_POST['finaliza_parcelas'];
if(isset($_POST['finaliza_parcelas'])) {
	$codigo_implantacao_finaliza	= $_GET["cod_implantacao_finaliza"];
	if($cod_implantacao_finaliza <> ''){
		$sql_upd = "UPDATE tbl_controle_implantacao	 SET
					 finalizada	= 't' ,
					 data_fechamento = CURRENT_DATE
					 WHERE controle_implantacao = $codigo_implantacao_finaliza";
			$res_upd = pg_query ($con,$sql_upd);
			if(strlen(pg_errormessage($con)) > 0 ) {
				echo "1";
			}else{
				echo "2";
			}
	}else{
		echo "2";
	}
	exit;
}

$btn_acao = $_POST['cod_implantacao_para_excluir'];
if(isset($_POST['cod_implantacao_para_excluir'])) {
	$cod_implantacao_excluir	= $_GET["cod_implantacao_excluir"];
	if($cod_implantacao_excluir <> ''){
		$sql_upd = "UPDATE tbl_controle_implantacao	 SET
					excluido	= 't'
					WHERE controle_implantacao = $cod_implantacao_excluir";
			//echo $sql_upd;
			$res_upd = pg_query ($con,$sql_upd);
			if(strlen(pg_errormessage($con)) > 0 ) {
				echo "1";
			}else{
				echo "2";
			}
	}else{
		echo "2";
	}
	exit;
}

$btn_acao = $_POST['atualiza_parcelas'];
if(isset($_POST['atualiza_parcelas'])) {

	//echo "5|10,00|50,00";exit;
	$controle_implantacao		= $_GET["cod_implantacao"];

	$sql_total_parcelas_pg = "SELECT
								SUM(valor_entrada) AS valor_entrada
								FROM tbl_controle_parcela_implantacao
							  WHERE controle_implantacao = '$controle_implantacao'
							  AND pago ='t'";
							 //echo "<BR>SQL =".$sql_bus_parcela."<BR>";
	$res_total_parcelas_pg = @pg_exec ($con,$sql_total_parcelas_pg);
	if (@pg_numrows($res_total_parcelas_pg) > 0) {
		$tolta_pg = pg_result($res_total_parcelas_pg,'0',valor_entrada);
	}else{
		$tolta_pg = "0";
	}


	$sql_total_entrada_pg = "SELECT
								valor_entrada
								FROM tbl_controle_implantacao
							  WHERE controle_implantacao = '$controle_implantacao'";
							 //echo "<BR>SQL =".$sql_bus_parcela."<BR>";
	$res_total_entrada_pg = @pg_exec ($con,$sql_total_entrada_pg);
	if (@pg_numrows($res_total_entrada_pg) > 0) {
		$tolta_entrada_pg = pg_result($res_total_entrada_pg,'0',valor_entrada);
	}else{
		$tolta_entrada_pg = "0";
	}
	$tolta_pg = $tolta_pg + $tolta_entrada_pg;
	$total_valor_pago		= "R$ ".number_format($tolta_pg, 2, ',', '.');
	$title_total_valor_pago	= "R$ ".number_format($tolta_pg, 2, ',', '.');

	$sql_total_pagar = " SELECT (tbl_controle_implantacao.valor_implantacao - tbl_controle_implantacao.valor_entrada) AS ttl_pagar
						 FROM tbl_controle_implantacao
						 WHERE tbl_controle_implantacao.controle_implantacao = '$controle_implantacao'";
						 //echo "<BR>SQL =".$sql_total_parcelas_rc."<BR>";
	$res_total_pagar = @pg_exec ($con,$sql_total_pagar);
	if (@pg_numrows($res_total_pagar) > 0) {
		$tolta_pagar	= pg_result($res_total_pagar,'0',ttl_pagar);
	}else{
		$tolta_pagar = "0";
	}

	$sql_total_parcelas = " SELECT count(tbl_controle_parcela_implantacao.controle_parcela_implantacao)  AS total_parcelas
						FROM tbl_controle_parcela_implantacao
						WHERE tbl_controle_parcela_implantacao.controle_implantacao = '$controle_implantacao'
						AND pago ='t'";
						//echo "<BR>SQL =".$sql_total_parcelas_rc."<BR>";
	$res_total_parcelas = @pg_exec ($con,$sql_total_parcelas);
	if (@pg_numrows($res_total_parcelas) > 0) {
		$total_parcelas_pagas	= pg_result($res_total_parcelas,'0',total_parcelas);
	}else{
		$total_parcelas_pagas = "0";
	}

	$sql_total_pago = " SELECT
						SUM(COALESCE(tbl_controle_parcela_implantacao.valor_entrada,0))  AS total_receber
						FROM tbl_controle_parcela_implantacao
						WHERE tbl_controle_parcela_implantacao.controle_implantacao = '$controle_implantacao'
						AND pago ='t'";
						//echo "<BR>SQL =".$sql_total_parcelas_rc."<BR>";
	$res_total_pago = @pg_exec ($con,$sql_total_pago);
	if (@pg_numrows($res_total_pago) > 0) {
		$tolta_pago	= pg_result($res_total_pago,'0',total_receber);
	}else{
		$tolta_pago = "0";
	}

	$valore_a_pagar	= number_format($tolta_pagar, 2, '.', '');
	$valore_ja_pago	= number_format($tolta_pago, 2, '.', '');

	$tolta_rc = $tolta_pagar. $tolta_pago;

	$tolta_rc = $valore_a_pagar - $valore_ja_pago."<br>";

	if($tolta_rc < 0) {
		$tolta_rc = "0";
	}

	$tolta_rc	= number_format($tolta_rc, 2, ',', '.');

	if($tolta_rc == ''){
		$tolta_rc = $valor_implantacao;
	}
	$valor_total_a_receber			= "R$ ".$tolta_rc;
	$title_valor_total_a_receber	= "R$ ".$tolta_rc;

//$total_parcelas_pagas;
//$title_total_valor_pago;
//$total_valor_pago;
//$title_total_valor_pago;
//$valor_total_a_receber;
//$title_valor_total_a_receber;


echo $total_parcelas_pagas."|".$total_valor_pago."|".$valor_total_a_receber."|".$title_total_valor_pago."|".$title_total_valor_pago."|".$title_valor_total_a_receber;

exit;
}

$btn_acao = $_POST['validar_data'];
if(isset($_POST['validar_data'])) {
	$data_inicial		= $_GET["data_inicial"];
	$data_final			= $_GET["data_final"];
	$data_inicial_modif = preg_replace('/^(\d{2}).(\d{2}).(\d{4})$/', '$3-$2-$1', $data_inicial);
	$data_final_modif	= preg_replace('/^(\d{2}).(\d{2}).(\d{4})$/', '$3-$2-$1', $data_final);

	if(strlen($msg_erro)==0){
		list($df, $mf, $yf) = explode("/", $data_inicial);
		if(!checkdate($mf,$df,$yf))
			$msg_erro = "false";
	}

	if(strlen($msg_erro)==0){
		list($df, $mf, $yf) = explode("/", $data_final);
		if(!checkdate($mf,$df,$yf))
			$msg_erro = "false";
	}

	if(strlen($msg_erro)==0){
		$sqlX = "SELECT '$data_inicial_modif'::date  <= '$data_final_modif'";
		$resX = pg_query($con,$sqlX);
		$periodo_data = pg_fetch_result($resX,0,0);
	}

	if($periodo_data == f){
		$msg_erro = "false";
	}

	if(strlen($msg_erro)==0){
		$msg_erro = "true";
	}
	echo $msg_erro;exit;
}


$btn_alterar = $_POST['alterar_dados_fabrica_implantacao'];
if(isset($_POST['alterar_dados_fabrica_implantacao'])) {
		$valor_implantacao_fabrica		 = $_GET['valor_implantacao_fabrica'];
		$numero_parcelas_fabrica		 = $_GET['numero_parcelas_fabrica'];
		$valor_entrada_fabrica			 = $_GET['valor_entrada_fabrica'];
		$data_inicio_implantacao_fabrica = $_GET['data_inicio_implantacao_fabrica'];
		$data_final_implantacao_fabrica  = $_GET['data_final_implantacao_fabrica'];
		$codigo_da_implantacao			 = $_GET['codigo_da_impantacao'];


		$valor_implantacao_fabrica = str_replace('.','',$valor_implantacao_fabrica);
		$valor_implantacao_fabrica = str_replace(',','.',$valor_implantacao_fabrica);
		$valor_implantacao_fabrica = number_format($valor_implantacao_fabrica,2,'.','');

		$valor_entrada_fabrica = str_replace('.','',$valor_entrada_fabrica);
		$valor_entrada_fabrica = str_replace(',','.',$valor_entrada_fabrica);
		$valor_entrada_fabrica = number_format($valor_entrada_fabrica,2,'.','');

		if($valor_implantacao_fabrica == '') {
			$valor_implantacao_fabrica = "0";
		}

		if($valor_entrada_fabrica == '') {
			$valor_entrada_fabrica = "0";
		}

		$data_inicio_implantacao_fabrica	= preg_replace('/(\d\d).(\d{2}).(\d{4})/','$3-$2-$1',utf8_decode($data_inicio_implantacao_fabrica));
		$data_final_implantacao_fabrica		= preg_replace('/(\d\d).(\d{2}).(\d{4})/','$3-$2-$1',utf8_decode($data_final_implantacao_fabrica));

		//echo "N PARCELA =".$numero_parcelas_fabrica."\n\n\n VALOR ENTRADA =".$valor_entrada_fabrica."\n\n\n DATA INCIO =".$data_inicio_implantacao_fabrica."\n\n\n CODIGO IMPLANTACAO".$codigo_da_implantacao."\n\n\n";

		if(!$valor_implantacao_fabrica || !$numero_parcelas_fabrica || !$valor_entrada_fabrica || !$data_inicio_implantacao_fabrica || !$data_final_implantacao_fabrica || !$codigo_da_impantacao ) {
			echo "1";
		}else{
			$sql_upd = "UPDATE tbl_controle_implantacao	 SET
					 valor_implantacao	= '$valor_implantacao_fabrica',
					 valor_entrada		= '$valor_entrada_fabrica',
					 numero_parcela		= '$numero_parcelas_fabrica',
					 data_implantacao	= '$data_inicio_implantacao_fabrica',
					 data_finalizacao	= '$data_final_implantacao_fabrica'
					 WHERE controle_implantacao = $codigo_da_impantacao";
			//echo "\n\n\n\n\n";
			//echo $sql_upd;
			//echo "\n\n\n\n\n";
			$res_upd = pg_query ($con,$sql_upd);
			if(strlen(pg_errormessage($con)) > 0 ) {
				echo "1";
			}else{
				echo "2";
			}
		}
	exit;
}

$btn_cad_parcela = $_POST['cadastrar_parcelas'];
if(isset($_POST['cadastrar_parcelas'])) {

	$cod_implantacao		= $_GET['cod_implantacao'];
	$parcela				= $_GET['parcela'];
	$bolheto				= $_GET['bolheto'];

	$valor_parcela			= $_GET['valor_parcela'];
	$data_prevista			= $_GET['data_prevista'];
	$check_pago				= $_GET['check_pago'];
	$pg_data_pagamento		= $_GET['pg_data_pagamento'];
	$numero_nota_fiscal		= $_GET['numero_nota_fiscal'];
	$observacao				= $_GET['observacao'];
	$observacao				= utf8_decode($observacao);
	$cod_update				= $_GET['cod_update'];

	$valor_parcela			= str_replace('.','',$valor_parcela);
	$valor_parcela			= str_replace(',','.',$valor_parcela);
	$valor_parcela			= number_format($valor_parcela,2,'.','');
	if($valor_parcela == '') {
		$valor_parcela  ="0";
	}

	$data_prevista			= preg_replace('/(\d\d).(\d{2}).(\d{4})/','$3-$2-$1',utf8_decode($data_prevista));
	$pg_data_pagamento		= preg_replace('/(\d\d).(\d{2}).(\d{4})/','$3-$2-$1',utf8_decode($pg_data_pagamento));

	if($pg_data_pagamento !='') {
		$label_inser_dt_pagamento	= "data_pagamento ,";
		$cod_inser_dt_pagamento		= "'".$pg_data_pagamento."' ,";
	}

	$sql_ver_parcela = "SELECT * FROM tbl_controle_parcela_implantacao where controle_implantacao = '$cod_implantacao' and parcela = '$parcela'";
	$res_ver_parcela = @pg_exec ($con,$sql_ver_parcela);
	if (@pg_numrows($res_ver_parcela) == 0) {
		$sql_inserte = "INSERT INTO tbl_controle_parcela_implantacao
						 (controle_implantacao,
						 parcela,
						 valor_entrada,
						 data_prevista,
						 $label_inser_dt_pagamento
						 nf,
						 admin,
						 observacao,
						 pago)
						VALUES
						 ('$cod_implantacao',
						  '$parcela',
						  '$valor_parcela',
						  '$data_prevista',
						  $cod_inser_dt_pagamento
						  '$numero_nota_fiscal',
						  '$login_admin',
						  '$observacao',
						  '$check_pago')";
						  //echo nl2br($sql_inserte);
		$res = pg_query ($con,$sql_inserte);
		if(strlen(pg_errormessage($con)) > 0 ) {
			echo "1";
		}else {
			echo "2";
		}
	}else{
		$cod_update = pg_fetch_result($res_ver_parcela, 0, 'controle_parcela_implantacao');

		if($pg_data_pagamento !='') {
			$update_dt_pagamento = "data_pagamento	= '".$pg_data_pagamento."',";
		}
		$sql_update = "UPDATE tbl_controle_parcela_implantacao SET
						parcela			= '$parcela',
						valor_entrada	= '$valor_parcela',
						data_prevista	= '$data_prevista',
						$update_dt_pagamento
						nf				= '$numero_nota_fiscal',
						admin			= '$login_admin',
						pago			= '$check_pago',
						observacao		= '$observacao'
						WHERE controle_parcela_implantacao = $cod_update ;";
						//echo nl2br($sql_update);
		$res = pg_query ($con,$sql_update);
		$msg_erro = pg_errormessage($con);
		if(strlen(pg_errormessage($con)) > 0 ) {
			echo "1";
		}else {
			echo "2";
		}
	}
	exit;
}


$btn_acao = $_POST['gravar_dados_fabrica_implantacao'];
if(isset($_POST['gravar_dados_fabrica_implantacao'])) {
	$fabrica							= $_GET['aux_fabrica'];
	$valor_implantacao_cadastro			= $_GET['aux_valor_implantacao_cadastro'];
	$numero_parcelas_cadastro			= $_GET['aux_numero_parcelas_cadastro'];
	$valor_entrada_cadastro				= $_GET['aux_valor_entrada_cadastro'];
	$data_inicio_implantacao_cadastro	= $_GET['aux_data_inicio_implantacao_cadastro'];
	$data_final_implantacao_cadastro	= $_GET['aux_data_final_implantacao_cadastro'];




	$valor_implantacao_cadastro			= str_replace('.','',$valor_implantacao_cadastro);
	$valor_implantacao_cadastro			= str_replace(',','.',$valor_implantacao_cadastro);
	$valor_implantacao_cadastro			= number_format($valor_implantacao_cadastro,2,'.','');

	//echo $valor_implantacao_cadastro;exit;

	if($valor_implantacao_cadastro == '') {
		$valor_implantacao_cadastro  ="0";
	}

	$valor_entrada_cadastro = str_replace('.','',$valor_entrada_cadastro);
	$valor_entrada_cadastro = str_replace(',','.',$valor_entrada_cadastro);
	$valor_entrada_cadastro = number_format($valor_entrada_cadastro,2,'.','');
	if($valor_entrada_cadastro == '') {
		$valor_entrada_cadastro  ="0";
	}

	$data_inicio_implantacao_cadastro	= preg_replace('/(\d\d).(\d{2}).(\d{4})/','$3-$2-$1',utf8_decode($data_inicio_implantacao_cadastro));
	$data_final_implantacao_cadastro	= preg_replace('/(\d\d).(\d{2}).(\d{4})/','$3-$2-$1',utf8_decode($data_final_implantacao_cadastro));

	if(!$fabrica || !$valor_implantacao_cadastro || !$numero_parcelas_cadastro || !$valor_entrada_cadastro || !$data_inicio_implantacao_cadastro || !$data_final_implantacao_cadastro ) {
		echo "erro";
	}else {
		$sql = "INSERT INTO tbl_controle_implantacao
				 (fabrica,
				 valor_implantacao,
				 valor_entrada,
				 numero_parcela,
				 data_implantacao,
				 data_finalizacao,
				 admin)
				VALUES
				 ($fabrica,
				 '$valor_implantacao_cadastro',
				 '$valor_entrada_cadastro',
				 $numero_parcelas_cadastro,
				 '$data_inicio_implantacao_cadastro',
				 '$data_final_implantacao_cadastro',
				 '$login_admin')";
		//echo nl2br($sql);
		$res = pg_query ($con,$sql);
		if(strlen(pg_errormessage($con)) > 0 ) {
			echo "1";
		}else {
			echo "2";
		}
	}


	exit;
}


$btn_acao = $_POST['pesquisar'];
if(isset($_POST['pesquisar'])) {
	$fabrica_busca			= $_POST['fabrica_busca'];
	$aux_mes				= $_POST['mes'];
	$aux_ano				= $_POST['ano'];
	$com_horas_faturadas	= $_POST['com_horas_faturadas'];
	if(empty($aux_mes)){
		$msg_erro = "Selecione o Mês";
	}

	if(empty($aux_ano) && strlen($msg_erro) == 0){
		$msg_erro = "Selecione o Ano";
	}

}
include 'cabecalho.php';
?>
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

.formulario, .formulario td{
	background-color:#D9E2EF!important;
	font:12px Arial;
}

.infor_valor {
	color:#5A6D9C;
	font:14px Arial;
}


table.numero_parcelas_quadro {
font-family: arial;
background-color: #D9E2EF;
margin: 0px 0pt 0px;
font-size: 8pt;
width: 100%;
text-align: left;
}

table.numero_parcelas_quadro_obs {
font-family: arial;
background-color: white;
margin: 0px 0pt 0px;
font-size: 8pt;
width: 100%;
text-align: left;
}

table.infor_fabrica {
font-family: arial;
background-color: white;
margin: 0px 0pt 0px;
font-size: 8pt;
width: 100%;
text-align: left;
}

table.info_pagamento {
font-family: arial;
background-color: white;
margin: 0px 0pt 0px;
font-size: 8pt;
width: 100%;
text-align: left;
}

table.info_pagamento_alterar {
font-family: arial;
background-color: white;
margin: 0px 0pt 0px;
font-size: 8pt;
width: 100%;
text-align: right;
}

.mostra_dados{
	font-family: arial;
	font-size: 10pt;
	text-align: left;
}

.parcelas_fabrica{
	font-family: arial;
	font-size: 8pt;
	text-align: left;
	color:black;
	text-decoration: none
}

.parcelas_fabrica:hover{
	color:black;
	font-family: arial;
	font-size: 9pt;
	text-align: left;
	text-decoration: none
}


.alterar_dados_parcela{
	font-family: arial;
	font-size: 8pt;
	text-align: left;
	color:#5A6D9C;
	text-decoration: none
}

.mostra_dados_a {
	font-family: arial;
	font-size: 10pt;
	text-align: left;
	color:white;
	text-decoration: none
}

.mostra_dados_a:hover {
	font-family: arial;
	font-size: 10pt;
	text-align: left;
	color:#CDC9C9;
	text-decoration: none
}


.finalizar_implantacao_a {
	font-family: arial;
	font-size: 8pt;
	text-align: left;
	color:white;
	text-decoration: none;
}

.finalizar_implantacao_a:hover {
	font-family: arial;
	font-size: 8pt;
	text-align: left;
	color:#CDC9C9;
	text-decoration: none;
}

.alterar_dados_parcela:hover{
	font-family: arial;
	font-size: 8pt;
	text-align: left;
	color:#5A6D9C;
	text-decoration: none
}

table.alterar_parcelas{
	font-family: arial;
	background-color: white;
	margin: 0px 0pt 0px;
	font-size: 8pt;
	text-align: right;
	margin-top:0px;
	width: 100%;
}

.parcela_paga {
	color: #006400;
}


.campos_obrigatorios {
	color:red;
}


.infor_fabrica td{
	background-color: #D9E2EF;
}

.odd td, .odd th{
	background-color: #F7F5F0;
}

.even td, .even th{
	background-color: #F1F4FA;
}

.debito td, .debito th{
	background-color: #CDF4D9;
}

.credito td, .credito th{
	background-color: #F9DEDE;
}

.titulo, .titulo td, .titulo td a, .titulo td a span{
	background-color:#596d9b;
	font: bold 11px "Arial";
	color:#FFFFFF !important;
	text-align:center;
}

/* VALIDAÇÃO */
* { font-family: Verdana; font-size: 96%; }
label { display: block; margin-top: 10px; }
label.error { float: none; color: red; margin: 0 .5em 0 0; vertical-align: top; font-size: 10px }
p { clear: both; }
.submit { margin-top: 1em; }
em { font-weight: bold; padding-right: 1em; vertical-align: top;}

.msg{
	color: #FFF !important;
	text-align: center;
}
</style>
<?

function converte_data($date){
	//$date = explode("-", ereg_replace('/', '-', $date));
	$date = explode("-", preg_replace('/\//', '-', $date));
	$date2 = ''.$date[2].'/'.$date[1].'/'.$date[0];
	if (sizeof($date)==3)
		return $date2;
	else return false;
}
?>
<script type="text/javascript" src="js/jquery-1.6.1.min.js"></script>
<script type="text/javascript" src="js/jquery.tablesorter.js"></script>
<link rel="stylesheet" href="../helpdesk/js/blue/style.css" type="text/css" id="" media="print, projection, screen" />

<script language='javascript' src='../ajax.js'></script>
<script type="text/javascript" src="../js/bibliotecaAJAX.js"></script>
<script type="text/javascript" src="../helpdesk/js/jquery.price_format.1.5.js"></script>
<script src="../js/jquery.validate.js" type="text/javascript"></script>
<? include "javascript_calendario_new.php";
include '../js/js_css.php';
?>

<script type="text/javascript">
	$(function() {
		$('#data_inicio_implantacao_cadastro').datepick({startdate:'01/01/2000'});
		$('#data_final_implantacao_cadastro').datepick({startdate:'01/01/2000'});
		$('.pg_data_prevista').datepick({startdate:'01/01/2000'});
		$('.pg_data_pagamento').datepick({startDate:'01/01/2000'});
		$("#data_inicio_implantacao_cadastro").mask("99/99/9999");
		$("#data_final_implantacao_cadastro").mask("99/99/9999");
		$(".pg_data_prevista").mask("99/99/9999");
		$(".pg_data_pagamento").mask("99/99/9999");

		$('.data_inicio_implantacao_fabrica').datepick({startDate:'01/01/2000'});
		$(".data_inicio_implantacao_fabrica").mask("99/99/9999");

		$('.data_final_implantacao_fabrica').datepick({startDate:'01/01/2000'});
		$(".data_final_implantacao_fabrica").mask("99/99/9999");

		$.tablesorter.defaults.widgets = ['zebra'];
		$('.relatorio').tablesorter();
		$('.info_pagamento').tablesorter();

	$('#valor_implantacao_cadastro').priceFormat({
		prefix: 'R$ ',
		centsSeparator: ',',
		thousandsSeparator: '.'
	});

	$('#valor_entrada_cadastro').priceFormat({
		prefix: 'R$ ',
		centsSeparator: ',',
		thousandsSeparator: '.'
	});

	$('.valor_pg_entrada').priceFormat({
		prefix: 'R$ ',
		centsSeparator: ',',
		thousandsSeparator: '.'
	});

	$('.valor_implantacao_fabrica').priceFormat({
		prefix: 'R$ ',
		centsSeparator: ',',
		thousandsSeparator: '.'
	});

	$('.valor_entrada_fabrica').priceFormat({
		prefix: 'R$ ',
		centsSeparator: ',',
		thousandsSeparator: '.'
	});

	$('.valor_pg_entrada_fabrica').priceFormat({
		prefix: 'R$ ',
		centsSeparator: ',',
		thousandsSeparator: '.'
	});

	$('.total_pago').priceFormat({
		prefix: 'R$ ',
		centsSeparator: ',',
		thousandsSeparator: '.'
	});

	$('.valor_receber').priceFormat({
		prefix: 'R$ ',
		centsSeparator: ',',
		thousandsSeparator: '.'
	});

	$('.valor_pg_entrada_parcela').priceFormat({
		prefix: 'R$ ',
		centsSeparator: ',',
		thousandsSeparator: '.'
	});

});

$(document).ready(init);
function init(){
	$.datePicker.setDateFormat('dmy', '/');
	$.datePicker.setLanguageStrings(
		['Domingo', 'Segunda', 'Terça', 'Quarta', 'Quinta', 'Sexta', 'Sábado'],
		['Janeiro', 'Fevereiro', 'Março', 'Abril', 'maio', 'Junho', 'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'],
		{p:'Anterior', n:'Próximo', c:'Fechar', b:'Abrir Calendário'}
	);

}

$().ready(function() {
	$('.excluir_implantacao').live('click', function(){

		var codigo					=	$(this).attr('rel');
		var cod_implantacao_excluir	=	$(this).attr('alt');
		//alert(cod_implantacao_volta);

		if(confirm("Deseja excluir a implantação?")) {

			$("#dados_relatorio_none").load('<?=$PHP_SELF?>?cod_implantacao_excluir='+cod_implantacao_excluir,{'cod_implantacao_para_excluir':'cod_implantacao_para_excluir'},function(response, status, xhr) {
				// alert(status);
				// alert(response);
				// alert(xhr);
				// alert(response);
				if(response == 2) {
					//alert("OK");
					$("#infor_fabrica"+codigo).css('display','none');
					$("#msg_fabrica"+codigo).css('display','none');
					$("#voltar_implantacao_"+codigo).css('display','none');
				}else {
					alert("ERRO A EXCLUIR IMPLANTAÇÃO.");
				}

			});

		}

	});

	$('.href_impantacao').click(function(){

		var cod_impantacao			= $(this).attr('rel'); //CODIGO DA DIV
		var cod_impantacao_status	= $(this).attr('alt'); //VALOR QUE VERIFICA O STATUS DA DIV

		var div_href				= "href_impantacao_"+cod_impantacao;//VALOR DA DIV DO HREF
		var div_conteudo			= 'href_dados_impantacao_'+cod_impantacao; //DIV DO CONTEUDO COM OS DADOS DA IMPLANTAÇÃO
		//ALT 0 FECHADA /// ALT 1 ABERTA
		var div_conteudo_status		= 'conteudo_status_'+cod_impantacao;//LABEL COM (+) (-) MOSTRANDO STATUS DO CONTEUDO

		if(cod_impantacao_status == '0'){
			$("."+div_conteudo_status).html('-');
			$(this).attr('alt', '1');
			$("."+div_conteudo).show('fast');
			$("#bl_parcelas_"+cod_impantacao).fadeIn();
		}else{
			$("."+div_conteudo_status).html('+');
			$(this).attr('alt', '0');
			$("."+div_conteudo).hide('fast');
			$("#bl_parcelas_"+cod_impantacao).fadeOut();
		}

	});
});

/* VALIDAÇÃO DE CAMPOS */


// function valida(v){
//	alert(v);
//	return false;
// }

function valida(which) {
var pass=true;

if (document.images) {
	for (i=0;i<which.length;i++) {
		tempobj=which.elements[i];
		if (tempobj.name.substring(0,8)=="required") {
			if (((tempobj.type=="text"||tempobj.type=="textarea")&&tempobj.value=='')||
			(tempobj.type.toString().charAt(0)=="s"&& tempobj.selectedIndex==0)) {
				pass=false;
				break;
			}
		}
	}
}
if (!pass) {
	shortFieldName=tempobj.name.substring(8,30).toUpperCase();
	//alert("O campo "+shortFieldName+" deve ser preenchido.");
	return false;
}
else
return true;
}

$(document).ready( function() {


	function verificaNumero(e) {
		if (e.which != 8 && e.which != 0 && (e.which < 48 || e.which > 57)) {
			return false;
		}
	}


    function valida_campo_fabrica_cadastro(obj){
		var valor	= obj.val();
		var campo	= obj.attr('alt');

		if(valor == '' || valor == '__/__/____') {
			$("#"+campo).css("background-color",'#F4E6D7');
		}else{
			$("#"+campo).css("background-color",'white');
		}
	}

	function valida_campo_fabrica(obj){
		var valor	= obj.val();
		var campo	= obj.attr('alt');

		if(valor == '' || valor == '__/__/____') {
			$("."+campo).css("background-color",'#F4E6D7');
		}else{
			$("."+campo).css("background-color",'white');
		}
	}

	function valida_campo_fabrica_parcela(obj){
		var valor		= obj.val();
		var campo_alt	= obj.attr('alt');
		var campo_rel	= obj.attr('rel');

		if(valor == '' || valor == '__/__/____') {
			$("#"+campo_alt+campo_rel).css("background-color",'#F4E6D7');
		}else{
			$("#"+campo_alt+campo_rel).css("background-color",'white');
		}
	}

	function valida_campo_pago(obj){
		var codigo_rel	= obj.attr('rel');

		var campo_1 = "valor_pg_entrada"+codigo_rel;
		var campo_2 = "pg_data_prevista"+codigo_rel;
	    var campo_3 = "check_pago"+codigo_rel;

		var valor_pg_entrada	= $("#"+campo_1).val();
		var pg_data_prevista	= $("#"+campo_2).val();

		if(valor_pg_entrada == '' || pg_data_prevista == ''  || pg_data_prevista == '__/__/____'){
			$("#"+campo_3).removeAttr('checked');
		}

	}

	/* COMEÇO CAMPO DE CADASTRO */
	$("#fabrica_cadastro").focus(function() {
		valida_campo_fabrica_cadastro($(this));
	});
	$("#fabrica_cadastro").blur(function() {
		valida_campo_fabrica_cadastro($(this));
	});
	//=============
	$("#valor_implantacao_cadastro").focus(function() {
		valida_campo_fabrica_cadastro($(this));
	});
	$("#valor_implantacao_cadastro").blur(function() {
		valida_campo_fabrica_cadastro($(this));
	});
	//=============
	$("#numero_parcelas_cadastro").focus(function() {
		valida_campo_fabrica_cadastro($(this));
	});
	$("#numero_parcelas_cadastro").blur(function() {
		valida_campo_fabrica_cadastro($(this));
	});
	$("#numero_parcelas_cadastro").keypress(verificaNumero);
	//=============
	$("#valor_entrada_cadastro").focus(function() {
		valida_campo_fabrica_cadastro($(this));
	});
	$("#valor_entrada_cadastro").blur(function() {
		valida_campo_fabrica_cadastro($(this));
	});
	//=============
	$("#data_inicio_implantacao_cadastro").focus(function() {
		valida_campo_fabrica_cadastro($(this));
	});
	$("#data_inicio_implantacao_cadastro").blur(function() {
		valida_campo_fabrica_cadastro($(this));
	});
	//=============
	$("#data_final_implantacao_cadastro").focus(function() {
		valida_campo_fabrica_cadastro($(this));
	});
	$("#data_final_implantacao_cadastro").blur(function() {
		valida_campo_fabrica_cadastro($(this));
	});
	/* FIM CAMPO DE CADASTRO */

	/* COMEÇO CAMPO DE PARCELAS */
	$(".valor_implantacao_fabrica").focus(function() {
		valida_campo_fabrica($(this));
	});
	$(".valor_implantacao_fabrica").blur(function() {
		valida_campo_fabrica($(this));
	});
	//=============
	$(".numero_parcelas_fabrica").focus(function() {
		valida_campo_fabrica($(this));
	});
	$(".numero_parcelas_fabrica").blur(function() {
		valida_campo_fabrica($(this));
	});
	$(".numero_parcelas_fabrica").keypress(verificaNumero);
	//=============
	$(".valor_entrada_fabrica").focus(function() {
		valida_campo_fabrica($(this));
	});
	$(".valor_entrada_fabrica").blur(function() {
		valida_campo_fabrica($(this));
	});
	//=============
	$(".data_inicio_implantacao_fabrica").focus(function() {
		valida_campo_fabrica($(this));
	});
	$(".data_inicio_implantacao_fabrica").blur(function() {
		valida_campo_fabrica($(this));
	});
	//=============
	$("#data_final_implantacao_fabrica").focus(function() {
		valida_campo_fabrica($(this));
	});
	$(".data_final_implantacao_fabrica").blur(function() {
		valida_campo_fabrica($(this));
	});
	//=============
	$(".pg_data_prevista").focus(function() {
		valida_campo_fabrica($(this));
	});
	$(".pg_data_prevista").blur(function() {
		valida_campo_pago($(this));
		valida_campo_fabrica($(this));
	});
	//=============
	$(".pg_data_pagamento").focus(function() {
		valida_campo_fabrica($(this));
	});
	$(".pg_data_pagamento").blur(function() {
		valida_campo_fabrica($(this));
	});
	/* FIM CAMPO DE PARCELAS */


	$(".check_pago").click(function() {
		valida_campo_pago($(this));
	});

	//=========== PARCELAS ================

	$('.data_prevista_parcela').datePicker({startDate:'01/01/2000'});
	$(".data_prevista_parcela").maskedinput("99/99/9999");

	$('.data_pagamento_parcela').datePicker({startDate:'01/01/2000'});
	$(".data_pagamento_parcela").maskedinput("99/99/9999");

	//=============
	//=============

	$(".valor_pg_entrada_parcela").focus(function() {
		valida_campo_fabrica_parcela($(this));
		valida_campo_pago($(this));
	});
	$(".valor_pg_entrada_parcela").blur(function() {
		valida_campo_fabrica_parcela($(this));
		valida_campo_pago($(this));
	});

	//=============

	$(".data_prevista_parcela").focus(function() {
		valida_campo_fabrica_parcela($(this));
		valida_campo_pago($(this));
	});
	$(".data_prevista_parcela").blur(function() {
		valida_campo_fabrica_parcela($(this));
		valida_campo_pago($(this));
	});

	//=============

	$(".data_pagamento_parcela").focus(function() {
		valida_campo_fabrica_parcela($(this));
	});
	$(".data_pagamento_parcela").blur(function() {
		valida_campo_fabrica_parcela($(this));
	});



	//=============
});


function checarDatas(){
    var NomeForm = document.Formulario;

	var data_1 = $("#data_inicio_implantacao_cadastro").val();
	var data_2  = $("#data_final_implantacao_cadastro").val();

    var Compara01 = parseInt(data_1.split("/")[2].toString() + data_1.split("/")[1].toString() + data_1.split("/")[0].toString());
    var Compara02 = parseInt(data_2.split("/")[2].toString() + data_2.split("/")[1].toString() + data_2.split("/")[0].toString());

    if (Compara01 > Compara02) {
        return 0;
    }
    else {
        return 1;
    }
    return false;
}

function limpa_div(){
	$("#status_mensagem_cad").hide('fast');
	$(".div_fabrica_cadastro_nome").html();
	$("#status_erro_cad").hide('fast');
	$(".div_fabrica_cadastro_erro").html();
	$("#status_sucesso_cad").hide('fast');
	$(".div_fabrica_cadastro_sucesso").html();
	return true;
}

function gravar_fabrica_implantacao(){

	limpa_div();
	var fabrica							 = $("#fabrica_cadastro").val();
	var valor_implantacao_cadastro		 = $("#valor_implantacao_cadastro").val();
	valor_implantacao_cadastro			 = valor_implantacao_cadastro.replace("R$", "");
	valor_implantacao_cadastro			 = valor_implantacao_cadastro.replace(" ", "");

	var numero_parcelas_cadastro		 = $("#numero_parcelas_cadastro").val();

	var valor_entrada_cadastro			 = $("#valor_entrada_cadastro").val();
	valor_entrada_cadastro				 = valor_entrada_cadastro.replace("R$", "");
	valor_entrada_cadastro				 = valor_entrada_cadastro.replace(" ", "");

	var data_inicio_implantacao_cadastro = $("#data_inicio_implantacao_cadastro").val();

	var data_final_implantacao_cadastro  = $("#data_final_implantacao_cadastro").val();

	if(data_inicio_implantacao_cadastro && data_final_implantacao_cadastro) {
		limpa_div();
		$(".div_fabrica_cadastro_nome").load('<?=$PHP_SELF?>?data_inicial='+data_inicio_implantacao_cadastro+'&data_final='+data_final_implantacao_cadastro,{'validar_data':'validar_data'},function(response, status, xhr) {
			if(response == 'false'){
				$(".div_fabrica_cadastro_erro").html('Datas Inválidas');
				$("#status_erro_cad").show('fast');
				return false;
			}
		});
	}

	if(!fabrica || !valor_implantacao_cadastro || !numero_parcelas_cadastro || !valor_entrada_cadastro || !data_inicio_implantacao_cadastro || !data_final_implantacao_cadastro) {
		$(".div_fabrica_cadastro_erro").html('* Campos obrigatorios');
		$("#status_erro_cad").show('fast');
	}else{
		limpa_div();

		$(".div_fabrica_cadastro_nome").load('<?=$PHP_SELF?>?aux_fabrica='+fabrica+'&aux_valor_implantacao_cadastro='+valor_implantacao_cadastro+'&aux_numero_parcelas_cadastro='+numero_parcelas_cadastro+'&aux_valor_entrada_cadastro='+valor_entrada_cadastro+'&aux_data_inicio_implantacao_cadastro='+data_inicio_implantacao_cadastro+'&aux_data_final_implantacao_cadastro='+data_final_implantacao_cadastro,{'gravar_dados_fabrica_implantacao':'implantacao'},function(response, status, xhr) {
			//alert(response);
			//alert(status);
			//alert(xhr);
			if(response == 2){
				window.location = "<?php echo $_SERVER['PHP_SELF']; ?>";
				limpa_div();
				$(".div_fabrica_cadastro_sucesso").html('&nbsp;&nbsp;DADOS CADASTRADO COM SUCESSO ...');
				$("#status_sucesso_cad").show('fast');
				window.location = "<?php echo $_SERVER['PHP_SELF']; ?>";
			}else{
				limpa_div();
				$(".div_fabrica_cadastro_erro").html('&nbsp;&nbsp;ERRO A GRAVAR DADOS ...');
				$("#status_erro_cad").show('fast');
			}

		});
	}
}

function alterar_dados_fabrica(codigo) {
	var response;
	var status;
	var xhr;

	var input_controle_implantacao		= $("#input_controle_implantacao"+codigo).val();
	var codigo_da_impantacao			= $("#codigo_da_impantacao_"+codigo).val();
	var valor_implantacao_fabrica		= $("#valor_implantacao_fabrica_"+codigo).val();
	var numero_parcelas_fabrica			= $("#numero_parcelas_fabrica_"+codigo).val();
	var valor_entrada_fabrica			= $("#valor_entrada_fabrica_"+codigo).val();
	var data_inicio_implantacao_fabrica = $("#data_inicio_implantacao_fabrica_"+codigo).val();
	var data_final_implantacao_fabrica	= $("#data_final_implantacao_fabrica_"+codigo).val();

	//alert(input_controle_implantacao+' = '+input_controle_implantacao+' = '+codigo_da_impantacao+' = '+valor_implantacao_fabrica+' = '+numero_parcelas_fabrica+' = '+valor_entrada_fabrica+' = '+data_inicio_implantacao_fabrica+' = '+data_final_implantacao_fabrica);

	valor_implantacao_fabrica			= valor_implantacao_fabrica.replace("R$", "");
	valor_implantacao_fabrica			= valor_implantacao_fabrica.replace(" ", "");

	valor_entrada_fabrica				= valor_entrada_fabrica.replace("R$", "");
	valor_entrada_fabrica				= valor_entrada_fabrica.replace(" ", "");

	if(!valor_implantacao_fabrica || !numero_parcelas_fabrica || !valor_entrada_fabrica || !data_inicio_implantacao_fabrica || !data_final_implantacao_fabrica) {

	}else{
		$(".div_fabrica_cadastro_nome").load('<?=$PHP_SELF?>?codigo_da_impantacao='+codigo_da_impantacao+'&valor_implantacao_fabrica='+valor_implantacao_fabrica+'&numero_parcelas_fabrica='+numero_parcelas_fabrica+'&valor_entrada_fabrica='+valor_entrada_fabrica+'&data_inicio_implantacao_fabrica='+data_inicio_implantacao_fabrica+'&data_final_implantacao_fabrica='+data_final_implantacao_fabrica,{'alterar_dados_fabrica_implantacao':'alterar'},function(response, status, xhr) {
		   //alert(status);
		   //alert(response);
		  // alert(xhr);
		   $(".mensagem_div_alt_implantacao_1_"+codigo).hide('fast');
		   $(".mensagem_div_alt_implantacao_2_"+codigo).html();
		   if(response == 2) {
				$(".mensagem_div_alt_implantacao_2_"+codigo).html('&nbsp;&nbsp;<B>DADOS ALTERADO COM SUCESSO ...</B>');
				$(".mensagem_div_alt_implantacao_2_"+codigo).css('color','green');
				$(".mensagem_div_alt_implantacao_1_"+codigo).show('fast').delay(1800).hide(400);
				atualiza_dados_implantacao(input_controle_implantacao,codigo);
			}else {
				$(".mensagem_div_alt_implantacao_2_"+codigo).html('&nbsp;&nbsp;<B>ERRO AO ALTERAR OS DADOS ...</B>');
				$(".mensagem_div_alt_implantacao_2_"+codigo).css('color','red');
				$(".mensagem_div_alt_implantacao_1_"+codigo).show('fast').delay(1800).hide(400);
				if(!input_controle_implantacao) {
					atualiza_dados_implantacao(input_controle_implantacao,codigo);
				}
			}

		});

	}

}

function cadastrar_parcela(cod_implantacao,parcela,bolheto,n_parcelas,cod_update) {
	/*alert(cod_implantacao);
	alert(parcela);
	alert(bolheto);
	alert(n_parcelas);
	alert("COD ="+cod_update);*/

	var valor_parcela		 = $("#valor_pg_entrada"+parcela+bolheto).val();
	var data_prevista		 = $("#pg_data_prevista"+parcela+bolheto).val();
	var check_pago			 = $("#check_pago"+parcela+bolheto).is(':checked');
	//alert("#check_pago"+parcela+bolheto);
	//alert(check_pago);
	var pg_data_pagamento	 = $("#pg_data_pagamento"+parcela+bolheto).val();
	//console.log(pg_data_pagamento);
	var numero_nota_fiscal   = $("#numero_nota_fiscal"+parcela+bolheto).val();
	var observacao			 = $("#observacao"+parcela+bolheto).val();
	observacao = encodeURIComponent(observacao);

	var div_mensagem_parcela = "mensagem_parcela"+parcela+bolheto;

	//alert("OBSERVAÇÃO ="+observacao);
	valor_parcela			 = valor_parcela.replace("R$", "");
	valor_parcela			 = valor_parcela.replace(" ", "");
	//cod_implantacao
	//alert("TESTE");
	if(!cod_implantacao || !valor_parcela || !data_prevista) {

	}else {
		$(".div_fabrica_cadastro_nome").load('<?=$PHP_SELF?>?cod_implantacao='+cod_implantacao+'&parcela='+n_parcelas+'&bolheto='+bolheto+'&valor_parcela='+valor_parcela+'&data_prevista='+data_prevista+'&check_pago='+check_pago+'&pg_data_pagamento='+pg_data_pagamento+'&numero_nota_fiscal='+numero_nota_fiscal+'&observacao='+observacao+'&cod_update='+cod_update,{'cadastrar_parcelas':'parcelas'},function(response, status, xhr) {
		   //alert(status);
		   //alert(response);
		   //alert(xhr);
		   //$("#"+div_mensagem_parcela).hide('fast');
		   //$("#"+div_mensagem_parcela).html();

		   response = response.replace(" ","");
		   if(response == 2) {
				$("#"+div_mensagem_parcela).html('<B>PARCELA CADASTRADA ...</B>');
				$("#"+div_mensagem_parcela).css('color','green');
				$("#"+div_mensagem_parcela).show('fast').delay(1800).hide(400);
				atualiza_dados_implantacao(cod_implantacao,parcela);
			}else {
				$("#"+div_mensagem_parcela).html('<B>ERRO A CADASTRAR PARCELA d...</B>');
				$("#"+div_mensagem_parcela).css('color','red');
				$("#"+div_mensagem_parcela).show('fast').delay(1800).hide(400);
				atualiza_dados_implantacao(cod_implantacao,parcela);
			}

		});
	}

}

function atualiza_dados_implantacao(cod_implantacao,parcela) {

	//alert(cod_implantacao);
	//alert(parcela);
	if(!cod_implantacao) {

	}else {
		$(".div_fabrica_cadastro_nome").load('<?=$PHP_SELF?>?cod_implantacao='+cod_implantacao,{'atualiza_parcelas':'atualiza_parcelas'},function(response, status, xhr) {
			//alert(status);
			//alert(response);
			//alert(xhr);
		    //echo "5|10,00|50,00";exit;
			var ttl_atualiza=response.split("|");

			$("#total_parcelas_pagas"+parcela).val(ttl_atualiza[0]);
			$("#total_pago"+parcela).val(ttl_atualiza[1]);
			$("#valor_receber"+parcela).val(ttl_atualiza[2]);

			$("#total_parcelas_pagas"+parcela).attr('title',ttl_atualiza[3]);
			$("#total_pago"+parcela).attr('title',ttl_atualiza[4]);
			$("#valor_receber"+parcela).attr('title',ttl_atualiza[5]);
		});
	}

}

$().ready(function() {
	$(".finalizar_implantacao_a").click(function() {
		var codigo						=	$(this).attr('rel');
		var cod_implantacao_finaliza	=	$(this).attr('alt');
		var ttl_pago					=	$("#total_pago"+codigo).val();
		var ttl_rece					=	$("#valor_receber"+codigo).val();
		//alert(cod_implantacao_finaliza);
		if(confirm("Deseja finalizar a implantação.")) {

			$(".div_fabrica_cadastro_nome").load('<?=$PHP_SELF?>?cod_implantacao_finaliza='+cod_implantacao_finaliza,{'finaliza_parcelas':'finaliza_parcelas'},function(response, status, xhr) {

				if(response == 2) {
					//alert("OK");
					$("#infor_fabrica"+codigo).css('display','none');
					$(".href_dados_impantacao_"+codigo).css('display','none');
					$("#msg_fabrica"+codigo).css('display','none');
				}else {
					alert("ERRO A FINALIZAR IMPLANTAÇÃO.");
				}

			});

		}

	});
});

</script>


<form name='filtrar' method='POST' ACTION='$PHP_SELF' id="formularioconteudo" onsubmit="return valida(this);">
 <center>
	<table width='800px'>
		<tr>
			<td>
				<table width="800px">
						<tr>
							<td width="850px" id="status_mensagem_cad" style="background:#F4E6D7;text-align:center;color:red;font-family: Verdana;font-size: 14px;font-weight: bolder;display:none;">
								<div width="850px" class="div_fabrica_cadastro_nome"></div>
							</td>
						</tr>
						<tr>
							<td width="850px" id="status_erro_cad" style="background:#F4E6D7;text-align:center;color:red;font-family: Verdana;font-size: 14px;font-weight: bolder;display:none;">
								<div width="850px" class="div_fabrica_cadastro_erro"></div>
							</td>
						</tr>
						<tr>
							<td width="850px" id="status_sucesso_cad" style="background:green;text-align:center;color:white;font-family: Verdana;font-size: 14px;font-weight: bolder;display:none;">
								<div width="850px" class="div_fabrica_cadastro_sucesso"></div>
							</td>
						</tr>
				</table>

				<table cellspacing="0" cellpadding="0" border="0">
					<tbody>
						<tr height="18">
							<td width="18">
								<div class="status_checkpoint" style="background-color:oliveDrab;">&nbsp;</div>
							</td>
							<td width="100">
								<b>&nbsp;Parcelas pagas</b>
							</td>

							<td width="18">
								<div class="status_checkpoint" style="background-color:#F33;">&nbsp;</div>
							</td>
							<td width="100">
								<b>&nbsp;Parcelas vencidas</b>
							</td>
						</tr>
					</tbody>
				</table>



				<?php
					$sqlX = "SELECT TO_CHAR(current_date,'YYYY-MM-DD')";
					$resX = pg_query ($con,$sqlX);
					$aux_atual = pg_fetch_result ($resX,0,0);
				?>

				<table border='0' cellpadding='2' cellspacing='0' class="tablesorter formulario"  width="800px"  style="border:solid 1px #5A6D9C;background:#5A6D9C;">
					<tbody>
						<tr>
							<th style="paddig: 3px" colspan='4' class='titulo_coluna'>Fabricas em Implantação</th>
						</tr>
						<tr>
							<?php
								$sqlfabrica = " SELECT   *
												FROM tbl_fabrica
												ORDER BY nome";
												$resfabrica = pg_query ($con,$sqlfabrica);
												$n_fabricas = pg_num_rows($res);
							?>
							<td colspan='3'>Fabrica<br />
							<select name="requiredfabrica" id="fabrica_cadastro" alt="fabrica_cadastro" class="frm" style="width:520px" >
								<option value="">SELECIONE UMA FABRICA</option>
								<?php
									for($x = 0 ; $x < pg_num_rows($resfabrica) ; $x++){
										$fabrica   = trim(pg_fetch_result($resfabrica,$x,fabrica));
										$nome      = trim(pg_fetch_result($resfabrica,$x,nome));
								?>
								<option value='<?php echo $fabrica;?>'><?php echo $nome;?></option>
								<?php
									}
								?>
							</select><span class="campos_obrigatorios">&nbsp;*</span>
							</td>

							<td>Valor de Implantação<br /><input type="text" name="requiredvalor_implantacao" id="valor_implantacao_cadastro" alt="valor_implantacao_cadastro" maxlength="25" size="15" class="frm" value=""><span class="campos_obrigatorios">&nbsp;*</span></td>
						</tr>
						<tr>
							<td>N º de parcelas<br /><input type="text" name="requirednumero_parcelas" id="numero_parcelas_cadastro" alt="numero_parcelas_cadastro" maxlength="10" size="5" class="frm"><span class="campos_obrigatorios">&nbsp;*</span></td>
							<td>Entrada<br /><input type="text" name="requiredvalor_entrada" id="valor_entrada_cadastro" maxlength="20" size="10" alt="valor_entrada_cadastro" class="frm"><span class="campos_obrigatorios">&nbsp;*</span></td>
							<td>Data Implantação<br /><input type="text" name="requireddata_inicio_implantacao" id="data_inicio_implantacao_cadastro" maxlength="10" size="10" alt="data_inicio_implantacao_cadastro" class="frm"><span class="campos_obrigatorios">&nbsp;*</span></td>
							<td>Data Finalização<br /><input type="text" name="requireddata_final_implantacao" id="data_final_implantacao_cadastro" alt="data_final_implantacao_cadastro" maxlength="10" size="10" class="frm"><span class="campos_obrigatorios">&nbsp;*</span></td>
						</tr>
						<tr>
							<td colspan='4' style='padding: 10px; text-align: center'>
								<input type="button" name="cadastrar" id="cadastrar" value="Cadastrar" class="frm" style="padding: 3px 15px;" onclick="gravar_fabrica_implantacao('');">
							</td>
						</tr>
					</tbody>
				</table>



			   <?php
					$busca_fabrica ="SELECT
										tbl_controle_implantacao.controle_implantacao,
										tbl_controle_implantacao.fabrica			 ,
										tbl_controle_implantacao.valor_implantacao	 ,
										tbl_controle_implantacao.valor_entrada		 ,
										tbl_controle_implantacao.numero_parcela		 ,
										to_char(tbl_controle_implantacao.data_implantacao,'DD/MM/YYYY') AS data_implantacao,
										to_char(tbl_controle_implantacao.data_finalizacao,'DD/MM/YYYY') AS data_finalizacao,
										tbl_controle_implantacao.finalizada			 ,
										tbl_controle_implantacao.admin				 ,
										tbl_controle_implantacao.data_input			 ,
										tbl_fabrica.nome AS descricao_fabrica
									 FROM   tbl_controle_implantacao
									 JOIN tbl_fabrica   ON	 tbl_controle_implantacao.fabrica = tbl_fabrica.fabrica
									 WHERE tbl_controle_implantacao.finalizada = 'false'
									 	AND excluido is false
									 ORDER BY tbl_controle_implantacao.controle_implantacao";
									 //echo nl2br($busca_fabrica);
						$res_busca_fabrica = @pg_exec ($con,$busca_fabrica);
						if (@pg_numrows($res_busca_fabrica) > 0) {
							for($b=0;$b < pg_numrows($res_busca_fabrica);$b++) {
								$controle_implantacao	= pg_result($res_busca_fabrica,$b,controle_implantacao);
								$cod_fabrica			= pg_result($res_busca_fabrica,$b,fabrica);
								$valor_implantacao		= pg_result($res_busca_fabrica,$b,valor_implantacao);
								$valor_entrada			= pg_result($res_busca_fabrica,$b,valor_entrada);
								$numero_parcela			= pg_result($res_busca_fabrica,$b,numero_parcela);
								$data_implantacao		= pg_result($res_busca_fabrica,$b,data_implantacao);
								$data_finalizacao		= pg_result($res_busca_fabrica,$b,data_finalizacao);
								$finalizada				= pg_result($res_busca_fabrica,$b,finalizada);
								$admin					= pg_result($res_busca_fabrica,$b,admin);
								$data_input				= pg_result($res_busca_fabrica,$b,data_input);
								$descricao_fabrica		= pg_result($res_busca_fabrica,$b,descricao_fabrica);
								$valor_implantacao_1    = $valor_implantacao;

								$title_valor_implantacao	 = "R$ ".number_format($valor_implantacao, 2, ',', '.');
								$valor_implantacao			 = number_format($valor_implantacao, 2, ',', '.');
								$title_valor_entrada		 = "R$ ".number_format($valor_entrada, 2, ',', '.');
								$valor_entrada				 = number_format($valor_entrada, 2, ',', '.');

								$valor_total_a_receber		 = "0";
								$title_valor_total_a_receber = "R$ ".@number_format($tolta_valor_pago, 2, ',', '.');


								$sql_total_parcelas_pg = "SELECT
															SUM(valor_entrada) AS valor_entrada
															FROM tbl_controle_parcela_implantacao
														  WHERE controle_implantacao = '$controle_implantacao'
														  AND pago ='t'";
														 //echo "<BR>SQL =".$sql_bus_parcela."<BR>";
								$res_total_parcelas_pg = @pg_exec ($con,$sql_total_parcelas_pg);
								if (@pg_numrows($res_total_parcelas_pg) > 0) {
									$tolta_pg = pg_result($res_total_parcelas_pg,'0',valor_entrada);
								}else{
									$tolta_pg = "0";
								}

								$sql_total_entrada_pg = "SELECT
															valor_entrada
														FROM tbl_controle_implantacao
														WHERE controle_implantacao = '$controle_implantacao'";
														//echo "<BR>SQL =".$sql_bus_parcela."<BR>";
								$res_total_entrada_pg = @pg_exec ($con,$sql_total_entrada_pg);
								if (@pg_numrows($res_total_entrada_pg) > 0) {
									$tolta_entrada_pg = pg_result($res_total_entrada_pg,'0',valor_entrada);
								}else{
									$tolta_entrada_pg = "0";
								}
								$tolta_pg = $tolta_pg + $tolta_entrada_pg;
								$tolta_valor_pago		= "R$ ".number_format($tolta_pg, 2, ',', '.');
								$title_total_valor_pago	= "R$ ".number_format($tolta_pg, 2, ',', '.');

								$sql_total_pagar = " SELECT (tbl_controle_implantacao.valor_implantacao - tbl_controle_implantacao.valor_entrada) AS ttl_pagar
													 FROM tbl_controle_implantacao
													 WHERE tbl_controle_implantacao.controle_implantacao = '$controle_implantacao'";
													 //echo "<BR>SQL =".$sql_total_parcelas_rc."<BR>";
								$res_total_pagar = @pg_exec ($con,$sql_total_pagar);
								if (@pg_numrows($res_total_pagar) > 0) {
									$tolta_pagar	= pg_result($res_total_pagar,'0',ttl_pagar);
								}else{
									$tolta_pagar = "0";
								}

								$sql_total_parcelas = " SELECT count(tbl_controle_parcela_implantacao.controle_parcela_implantacao)  AS total_parcelas
													FROM tbl_controle_parcela_implantacao
													WHERE tbl_controle_parcela_implantacao.controle_implantacao = '$controle_implantacao'
													AND pago ='t'";
													//echo "<BR>SQL =".$sql_total_parcelas_rc."<BR>";
								$res_total_parcelas = @pg_exec ($con,$sql_total_parcelas);
								if (@pg_numrows($res_total_parcelas) > 0) {
									$total_parcelas_pagas	= pg_result($res_total_parcelas,'0',total_parcelas);
								}else{
									$total_parcelas_pagas = "0";
								}

								$sql_total_pago = " SELECT
													SUM(COALESCE(tbl_controle_parcela_implantacao.valor_entrada,0))  AS total_receber
													FROM tbl_controle_parcela_implantacao
													WHERE tbl_controle_parcela_implantacao.controle_implantacao = '$controle_implantacao'
													AND pago ='t'";
													//echo "<BR>SQL =".$sql_total_parcelas_rc."<BR>";
								$res_total_pago = @pg_exec ($con,$sql_total_pago);
								if (@pg_numrows($res_total_pago) > 0) {
									$tolta_pago	= pg_result($res_total_pago,'0',total_receber);
								}else{
									$tolta_pago = "0";
								}

								$valore_a_pagar	= number_format($tolta_pagar, 2, '.', '');
								$valore_ja_pago	= number_format($tolta_pago, 2, '.', '');

								$tolta_rc = $tolta_pagar. $tolta_pago;

								$tolta_rc = $valore_a_pagar - $valore_ja_pago."<br>";


							    if($tolta_rc == 0) {
									$verif_finalizar_implantacao = 0;
								}else{
									$verif_finalizar_implantacao = $tolta_pago - $tolta_rc;
								}

								//echo "PG **".$tolta_pago." - REC **".$tolta_rc." = TOTAL **".$verif_finalizar_implantacao;

								if($verif_finalizar_implantacao == 0) {
									$display_finaliza_implantacao = 'style="display:block; color: #fff"';
								}else{
									$display_finaliza_implantacao = 'style="display:block; color: #fff"';
								}

								if($tolta_rc < 0) {
									$tolta_rc = "0";
								}

								$tolta_rc	= number_format($tolta_rc, 2, ',', '.');

								if($tolta_rc == ''){
									$tolta_rc = $valor_implantacao;
								}
								$valor_total_a_receber			= "R$ ".$tolta_rc;
								$title_valor_total_a_receber	= "R$ ".$tolta_rc;

								$status_fabrica_implantacao = "";
								$status_fabrica_implantacao = "border:solid 2px #5A6D9C;background:#5A6D9C;margin-top:0px;";
								$sql_verifica_status = "SELECT COUNT(controle_parcela_implantacao) AS controle_parcela_implantacao FROM tbl_controle_parcela_implantacao WHERE data_prevista < '$aux_atual' AND pago = 'f' AND controle_implantacao = '$controle_implantacao'";
								//echo nl2br($sql_verifica_status);
								$res_verifica_status = @pg_exec ($con,$sql_verifica_status);
								if (@pg_numrows($res_verifica_status) > 0) {
									$status_das_parcelas = pg_result($res_verifica_status,'0',controle_parcela_implantacao);

									if($status_das_parcelas > 0) {
										$status_fabrica_implantacao = "border:solid 2px #F33;background:#F33;margin-top:0px;";
									}

								}


								$status_total_pago = "SELECT SUM(tbl_controle_parcela_implantacao.valor_entrada) + tbl_controle_implantacao.valor_entrada AS total
													  FROM tbl_controle_implantacao
													  JOIN tbl_controle_parcela_implantacao
													  ON   tbl_controle_implantacao.controle_implantacao = tbl_controle_parcela_implantacao.controle_implantacao
													  AND tbl_controle_parcela_implantacao.pago = 't'
													  WHERE tbl_controle_implantacao.controle_implantacao = '$controle_implantacao'
													  GROUP BY tbl_controle_implantacao.valor_entrada";
								//echo nl2br($status_total_pago);
								$res_status_total_pago = @pg_exec ($con,$status_total_pago);
								if (@pg_numrows($res_status_total_pago) > 0) {
									$total_parcelas_pagas = pg_result($res_status_total_pago,'0',total);
								}
								//$valor_implantacao
								//echo "<br> VALORES =".$valor_implantacao_1."  =   ".$total_parcelas_pagas."<br>";
								if($valor_implantacao_1 <= $total_parcelas_pagas) {
									 $status_fabrica_implantacao = "border:solid 2px oliveDrab;background:oliveDrab;margin-top:0px";
								}

				?>
						   <input type="hidden" name="codigo_da_impantacao_<?php echo $b;?>" id="codigo_da_impantacao_<?php echo $b;?>" value="<?php echo $controle_implantacao;?>">

						    <table width="810px"  id="msg_fabrica<?php echo $b;?>" style="border:solid 0px #D9E2EF;background:#D9E2EF;margin-top:10px;font-weight: bold;font-family: Verdana;font-size: 10pt;">
								<tbody>
									<tr style="display:none;" class="mensagem_div_alt_implantacao_1_<?php echo $b;?>">
										<th style="color:white;">
											<div class="mensagem_div_alt_implantacao_2_<?php echo $b;?>">AA</div>
										</th>
									</tr>
								</tbody>
							</table>

						   <table border='0' cellpadding='5' cellspacing='0' class="infor_fabrica" id="infor_fabrica<?php echo $b;?>" width="800px"  style="<?php echo $status_fabrica_implantacao;?>">
								<thead>
									<tr>
										<th style="color:white;font-size:14px; text-align: left" width='*' colspan='3'>
											<a href="javascript:void(0)" name="mostra_dados_impantacao" class="mostra_dados_impantacao mostra_dados_a" rel="1" style='color: #fff'><span class="href_impantacao" rel="<?php echo $b;?>" alt="0"><span class="conteudo_status_<?php echo $b;?>">+</span><span>&nbsp;<b><?php echo $descricao_fabrica;?></b></a>
										</th>
										<th style="text-align: center" width='150px'>
											<a href="javascript:void(0)" name="finalizar_implantacao" class="finalizar_implantacao_a" rel="<?php echo $b;?>" alt="<?php echo $controle_implantacao;?>" <?php echo $display_finaliza_implantacao;?> >Finalizar Implantação</a>
										</th>
										<?php if(!$filtro_ativo) {?>
											<th style="text-align: center" width='200px'>
												<a href="javascript:void(0)" name="finalizar_implantacao" style="font-family: arial;font-size: 8pt;color:white;text-decoration: none;" rel="<?php echo $b;?>" alt="<?php echo $controle_implantacao;?>" <?php echo $display_finaliza_implantacao;?>  style="font-family: arial;font-size: 10pt;text-align: left;color:white;text-decoration: none;float:left;" class="excluir_implantacao" id="excluir_implantacao_<?php echo $b;?>" rel="<?php echo $controle_implantacao;?>">Excluir Implantação</a>
											</th>
										<?php }?>
									</tr>
								<thead>
								<tbody id='bl_parcelas_<?php echo $b; ?>' style='display: none'>
									<tr>
										<td colspan='2'>
											Fabrica<br />
											<input type="text" name="requiredfabrica_fabrica" disabled value="<?php echo $descricao_fabrica;?>" title="<?php echo $descricao_fabrica;?>" id="fabrica" maxlength="150" size="32" class="frm" />
										</td>
										<td>
											Valor de Implantação<br />
											<input type="text" name="requiredvalor_implantacao_fabrica" id="valor_implantacao_fabrica_<?php echo $b;?>" class="valor_implantacao_fabrica" alt="valor_implantacao_fabrica" maxlength="25" size="16" class="frm" value="<?php echo $valor_implantacao;?>" title="<?php echo $title_valor_implantacao;?>" />
										</td>
										<td style='width: 220px'>
											N º de parcelas<br />
											<input type="text" name="requirednumero_parcelas_fabrica" id="numero_parcelas_fabrica_<?php echo $b;?>" class="numero_parcelas_fabrica" alt="numero_parcelas_fabrica" maxlength="10" size="5" class="frm" value="<?php echo $numero_parcela;?>" title="<?php echo $numero_parcela;?>" />
										</td>
										<td>
											Entrada<br />
											<input type="text" name="valor_entrada_fabrica" id="valor_entrada_fabrica_<?php echo $b;?>" class="valor_entrada_fabrica" maxlength="20" alt="valor_entrada_fabrica" size="16" class="frm" value="<?php echo $valor_entrada;?>" title="<?php echo $title_valor_entrada;?>" />
										</td>
									</tr>

									<tr>
										<td>
											Ínicio da Implantação<br />
											<input type="text" name="requireddata_inicio_implantacao_fabrica" id="data_inicio_implantacao_fabrica_<?php echo $b;?>" alt="data_inicio_implantacao_fabrica" class="data_inicio_implantacao_fabrica" maxlength="10" size="12" class="frm" value="<?php echo $data_implantacao;?>" title="<?php echo $data_implantacao;?>">
										</td>
										<td>
											Finalização<br />
											<input type="text" name="requireddata_final_implantacao_fabrica" id="data_final_implantacao_fabrica_<?php echo $b;?>" alt="data_final_implantacao_fabrica" class="data_final_implantacao_fabrica" maxlength="10" size="12" class="frm" value="<?php echo $data_finalizacao;?>" title="<?php echo $data_finalizacao;?>">
										</td>
										<td width='150px'>
											Parcelas pagas<br />
											<input type="text" name="total_parcelas_pagas" id="total_parcelas_pagas<?php echo $b;?>" value="<?php  echo pg_result($res_total_parcelas,'0',total_parcelas);?>" class="total_parcelas_pagas" maxlength="10" size="10" disabled class="frm">
										</td>
										<td width='180px'>
											Total Pago<br />
											<input type="text" disabled name="total_pago" id="total_pago<?php echo $b;?>" class="total_pago" value="<?php echo $tolta_valor_pago;?>" title="<?php echo $title_total_valor_pago;?>" maxlength="20" size="15" class="frm" style="color:#006400;">
										</td>
										<td width='120px'>
											Valor a Receber<br />
											<input type="text" disabled name="valor_receber" id="valor_receber<?php echo $b;?>" class="valor_receber" value="<?php echo $valor_total_a_receber;?>" title="<?php echo $title_valor_total_a_receber;?>" maxlength="10" size="15" class="frm" style="color:red;">
										</td>
									</tr>
									<tr>
										<td colspan='4'>&nbsp;</td>
										<td align='center'>
											<input type="button" name="alterar" id="alterar" value="Alterar Dados" class="frm" style="background:#5A6D9C;color:white;" onclick="alterar_dados_fabrica('<?php echo $b;?>');"><br /><br />
										</td>
									<tr>
<!-- 									</tr>
										<td colspan='5' style='background:#3A4868;'>
											<div style="margin: 0; padding: 3px;background:#3A4868;color:white;font-size:12px; font-weight; bold; text-align: center">Parcelas</div>
										</td>
									</tr> -->

										<?php
										for($p=0;$p < $numero_parcela;$p++) {
											$n_parcela = $p + 1;

											$sql_bus_parcela = "SELECT
																controle_parcela_implantacao,
																controle_implantacao,
																parcela,
																valor_entrada,
																data_prevista AS data_prevista_verifica,
																to_char(data_prevista,'DD/MM/YYYY') AS data_prevista,
																to_char(data_pagamento,'DD/MM/YYYY') AS data_pagamento,
																nf,
																pago,
																observacao
																FROM tbl_controle_parcela_implantacao
																WHERE controle_implantacao = '$controle_implantacao'
																AND parcela ='$n_parcela'";
											//echo nl2br($sql_bus_parcela);
											$par_controle_parcela_implantacao	= "";
											$par_controle_implantacao			= "";
											$par_parcela						= "";
											$par_valor_entrada					= "";
											$par_data_prevista					= "";
											$par_data_pagamento					= "";
											$par_nf								= "";
											$title_par_valor_entrada			= "";
											$pago								= "";
											$observacao							= "";
											$par_data_prevista_verifica			= "";
											$res_bus_parcela = @pg_exec ($con,$sql_bus_parcela);
											if (@pg_numrows($res_bus_parcela) > 0) {
												$par_controle_parcela_implantacao	= pg_result($res_bus_parcela,'0',controle_parcela_implantacao);
												$par_controle_implantacao			= pg_result($res_bus_parcela,'0',controle_implantacao);
												$par_parcela						= pg_result($res_bus_parcela,'0',parcela);
												$par_valor_entrada					= pg_result($res_bus_parcela,'0',valor_entrada);
												$par_data_prevista					= pg_result($res_bus_parcela,'0',data_prevista);
												$par_data_prevista_verifica			= pg_result($res_bus_parcela,'0',data_prevista_verifica);
												$par_data_pagamento					= pg_result($res_bus_parcela,'0',data_pagamento);
												$par_nf								= pg_result($res_bus_parcela,'0',nf);
												$pago								= pg_result($res_bus_parcela,'0',pago);
												$observacao							= pg_result($res_bus_parcela,'0',observacao);

												$title_par_valor_entrada			= "R$ ".number_format($par_valor_entrada, 2, ',', '.');
												$par_valor_entrada					= number_format($par_valor_entrada, 2, ',', '.');
											}
											//echo "PARCELA =".$b." --- BOLHETO =".$p."<br>";
											$tr_class = ($p % 2) ? "odd" : "even";

											if($pago == 't') {
												$tr_class = "debito";
											}

											$data_vencida = "";
											if(strlen($par_data_prevista_verifica) > 0 && $pago == 'f'){
												$sqlDC = "SELECT '$par_data_prevista_verifica'::date >= '$aux_atual'::date AS data_vencida";
												$resDC = pg_query($con, $sqlDC);
												$data_vencida = pg_fetch_result($resDC, 0, 0);
											}

											if($data_vencida == 'f'){
												$tr_class = "credito";
											}?>

											<tr class="<?php echo $tr_class;?> titulo">
												<td width='100px;' nowrap>
													<input type="hidden" name="input_controle_implantacao" id="input_controle_implantacao<?php echo $b;?>" value="<?php echo $controle_implantacao;?>" >
													<a href="javascript:void(0)" class="parcelas_fabrica"><span class="mostra_dados"><b>+</b></span>&nbsp;&nbsp;Nº da parcela:&nbsp;<b><span style="color:black;"><?php echo $n_parcela;?></span></b></a>
												</td>
												<td style="color:white;font-size:14px;text-align: center;"  colspan='3'>
													<span class='msg' id="mensagem_parcela<?php echo $b;?><?php echo $p;?>"></span>
												</td>
												<td style="color:white;font-size:14px;text-align: center;">
													<span class="parcela_paga"><input type="button" rel="<?php echo $p;?>" class="alterar_dados_parcela" onclick="cadastrar_parcela('<?php echo $controle_implantacao;?>','<?php echo $b;?>','<?php echo $p;?>','<?php echo $n_parcela;?>','<?php echo $par_controle_parcela_implantacao;?>');" value="Cadastrar Parcela"></span>
												</td>
											</tr>

											<tr class="<?php echo $tr_class;?>">
												<?php $check_pago = ($pago == 't') ?  "checked" : "";?>
												<td width='160px'>Valor<br><input type="text" name="requiredvalor_entrada_parcela<?php echo $b;?><?php echo $p;?>" id="valor_pg_entrada<?php echo $b;?><?php echo $p;?>" class="valor_pg_entrada_parcela" alt="valor_pg_entrada" rel="<?php echo $b;?><?php echo $p;?>" maxlength="25" size="15" class="frm" value="<?php echo $par_valor_entrada;?>" title="<?php echo $title_par_valor_entrada;?>"></td>
												<td width='200px'>Data Prevista<br><input type="text" name="requiredpg_data_prevista_parcela<?php echo $b;?><?php echo $p;?>" id="pg_data_prevista<?php echo $b;?><?php echo $p;?>" class="data_prevista_parcela" alt="pg_data_prevista" rel="<?php echo $b;?><?php echo $p;?>" maxlength="10" size="10" class="frm" value="<?php echo $par_data_prevista;?>" title="<?php echo $par_data_prevista;?>"></td>
												<td width='100px'>Pago<br><input type="checkbox" <?php echo $check_pago;?> name="check_pago_parcela<?php echo $b;?><?php echo $p;?>" id="check_pago<?php echo $b;?><?php echo $p;?>" class="check_pago" alt="check_pago" rel="<?php echo $b;?><?php echo $p;?>" maxlength="25" size="15" class="frm" value="t"></td>
												<td style='width: 200px'>Data do Pagamento<br><input type="text" name="requiredpg_data_pagamento_parcela<?php echo $b;?><?php echo $p;?>" id="pg_data_pagamento<?php echo $b;?><?php echo $p;?>" class="data_pagamento_parcela" alt="pg_data_pagamento" rel="<?php echo $b;?><?php echo $p;?>" maxlength="10" size="10" class="frm" value="<?php echo $par_data_pagamento;?>" title="<?php echo $par_data_pagamento;?>"></td>
												<td>Nota Fiscal<br /><input type="text" name="numero_nota_fiscal_parcela<?php echo $b;?><?php echo $p;?>" id="numero_nota_fiscal<?php echo $b;?><?php echo $p;?>" alt="numero_nota_fiscal_" rel="<?php echo $b;?><?php echo $p;?>" class="numero_nota_fiscal" size="22" class="frm" value="<?php echo $par_nf;?>" title="<?php echo $par_nf;?>"></td>
											</tr>
											<tr class="<?php echo $tr_class;?>">
												<td  colspan='5'>Observação<br /><input type="text" name="observacao_parcela<?php echo $b;?><?php echo $p;?>" id="observacao<?php echo $b;?><?php echo $p;?>" alt="observacao_" rel="<?php echo $b;?><?php echo $p;?>" class="observacao" size="122" class="frm" value="<?php echo $observacao;?>" title="<?php echo $observacao;?>"></td>
											</tr>
											<tr class="<?php echo $tr_class;?>" >
												<td style='padding: 1px;' colspan='5' style='background-color: #D9E2EF'>&nbsp;</td>
											</tr>
										<?php }?>

									</tbody>
								</table>
							</div>

							<!-- ========================================================================================================= -->
				<?php
							}
						}
				?>
			</td>
		</tr>
	</table>


	<table width = '500' align= 'center' cellpadding='0' cellspacing='0' border='0'  style="margin-top:10px;">
		<tr>
			<td>
				<div id="dados_relatorio_none" style='display:none;'></div>
			</td>
		</tr>
		<tr>
			<td>
				<div id="dados_relatorio"></div>
			</td>
		</tr>
	</table>
 </center>
</form><br /><br /><br /><br />
<?php

$btn_acao = $_POST['pesquisar'];
if(isset($_POST['pesquisar'])) {

}

include "rodape.php";
 ?>
</body>
</html>