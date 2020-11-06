<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include "autentica_usuario.php";
include "funcoes.php";

if ($login_fabrica == 1) {
	header ("Location: os_consulta_avancada.php");
	exit;
}

if (strlen($_POST["btn_acao"]) > 0) $btn_acao = strtoupper($_POST["btn_acao"]);
if (strlen($_GET["btn_acao"]) > 0) $btn_acao = strtoupper($_GET["btn_acao"]);

if (strlen($_POST["btn_acao_pre_os"]) > 0) $btn_acao_pre_os = strtoupper($_POST["btn_acao_pre_os"]);
if (strlen($_GET["btn_acao_pre_os"]) > 0) $btn_acao_pre_os = strtoupper($_GET["btn_acao_pre_os"]);

# ---- excluir ---- #
$os = $_GET['excluir'];

if (strlen ($os) > 0) {
	if($login_fabrica==50){//HD 37007 5/9/2008
		$sql = "UPDATE tbl_os SET excluida = 't' WHERE os = $os AND fabrica = $login_fabrica";
		$res = @pg_query ($con,$sql);
		$msg_erro = pg_errormessage($con);
	}else{
		$sql = "SELECT fn_os_excluida($os,$login_fabrica,null);";
		$res = @pg_query ($con,$sql);
		$msg_erro = pg_errormessage($con);
	}
}


 //hd 88308 waldir

# ---- fechar ---- #
$os = $_GET['consertado'];
if (strlen ($os) > 0) {
	$msg_erro = "";

	if($login_fabrica == 11){
		$sqlD = "SELECT os
				FROM tbl_os
				WHERE os = $os
				AND fabrica  = $login_fabrica
				AND defeito_constatado IS NOT NULL
				AND solucao_os IS NOT NULL";
		$resD = @pg_query($con,$sqlD);
		$msg_erro = pg_errormessage($con);
		if(pg_num_rows($resD)==0){
			$msg_erro = traduz("por.favor.verifique.os.dados.digitados.defeito.constatado.e.solucao.na.tela.de.lancamento.de.itens",$con,$cook_idioma);
		}
	}

	if (strlen($msg_erro)==0){
		$sql = "UPDATE tbl_os SET data_conserto = CURRENT_TIMESTAMP WHERE os=$os";
		$res = @pg_query($con,$sql);
		$msg_erro = pg_errormessage($con);
	}

	if (strlen($msg_erro)==0){
		echo "ok|ok";
	}else{
		echo "erro|$msg_erro";
	}
	exit;
}

# ---- fechar ---- #
$os = $_GET['fechar'];
if (strlen ($os) > 0) {
//	include "ajax_cabecalho.php";

	$msg_erro = "";
	$res = pg_query ($con,"BEGIN TRANSACTION");
	if($login_fabrica == 3){
		$sql = "SELECT tbl_os_item.os_item , tbl_os_extra.obs_fechamento
				FROM tbl_os_produto
				JOIN tbl_os_item           ON tbl_os_produto.os_produto     = tbl_os_item.os_produto
				JOIN tbl_servico_realizado ON tbl_os_item.servico_realizado = tbl_servico_realizado.servico_realizado
				JOIN tbl_os_extra          ON tbl_os_produto.os             = tbl_os_extra.os
				LEFT JOIN tbl_faturamento_item on tbl_os_item.peca = tbl_faturamento_item.peca and tbl_os_item.pedido = tbl_faturamento_item.pedido
				WHERE tbl_os_produto.os = $os
				AND tbl_servico_realizado.gera_pedido IS TRUE
				AND tbl_faturamento_item.faturamento_item IS NULL
				LIMIT 1";
		$res = @pg_query($con,$sql);
		if(pg_num_rows($res)>0){
			$os_item = trim(pg_fetch_result($res,0,os_item));
			$obs_fechamento = trim(pg_fetch_result($res,0,obs_fechamento));
			if(strlen($os_item)>0 and strlen($obs_fechamento)==0){
				$msg_erro .= traduz("os.com.pecas.pendentes,.favor.informar.o.motivo.do.fechamento",$con,$cook_idioma);
			}
		}

		$sql = "SELECT tbl_os.os FROM tbl_os WHERE tbl_os.os = $os AND tbl_os.defeito_constatado IS NULL";
		$res = pg_query ($con,$sql);
		if (pg_num_rows ($res) > 0) {
			$sql = "UPDATE tbl_os SET defeito_constatado = 0 WHERE tbl_os.os = $os";
			$res = pg_query ($con,$sql);
		}

		$sql = "SELECT tbl_os.os FROM tbl_os WHERE tbl_os.os = $os AND tbl_os.solucao_os IS NULL";
		$res = pg_query ($con,$sql);
		if (pg_num_rows ($res) > 0) {
			$sql = "UPDATE tbl_os SET solucao_os = 0 WHERE tbl_os.os = $os";
			$res = pg_query ($con,$sql);
		}

		$sql = "SELECT tbl_os.os FROM tbl_os JOIN tbl_os_produto USING (os) JOIN tbl_os_item USING (os_produto) WHERE tbl_os.os = $os AND tbl_os_item.peca_serie_trocada IS NULL";
		$res = pg_query ($con,$sql);
		if (pg_num_rows ($res) > 0) {
			$sql = "UPDATE tbl_os_item SET peca_serie_trocada = '0000000000000' FROM tbl_os_produto JOIN tbl_os USING (os) WHERE tbl_os_item.os_produto = tbl_os_produto.os_produto AND tbl_os.os = $os";
			$res = pg_query ($con,$sql);
		}
	}

	$sql = "SELECT status_os
			FROM tbl_os_status
			WHERE os = $os
			AND status_os IN (62,64,65,72,73,87,88,116,117)
			ORDER BY data DESC
			LIMIT 1";
	$res = pg_query ($con,$sql);
	if (pg_num_rows($res)>0){
		$status_os = trim(pg_fetch_result($res,0,status_os));
		if ($status_os=="72" || $status_os=="62" || $status_os=="87" || $status_os=="116"){
			if ($login_fabrica ==51) { // HD 59408
				$sql = " INSERT INTO tbl_os_status
						(os,status_os,data,observacao)
						VALUES ($os,64,current_timestamp,'OS Fechada pelo posto')";
				$res = pg_query($con,$sql);
				$msg_erro .= pg_errormessage($con);

				$sql = "UPDATE tbl_os_item SET servico_realizado = 671 FROM tbl_os_produto
						WHERE tbl_os_produto.os_produto = tbl_os_item.os_produto
						AND   tbl_os_produto.os = $os";
				$res = pg_query($con,$sql);
				$msg_erro .= pg_errormessage($con);

				$sql = "UPDATE tbl_os SET defeito_constatado = 10536,solucao_os = 491
						WHERE tbl_os.os = $os";
				$res = pg_query($con,$sql);
				$msg_erro .= pg_errormessage($con);
			}else{
				$msg_erro .= traduz("os.com.intervencao,.nao.pode.ser.fechada.",$con,$cook_idioma);
			}
		}
	}

	$sql = "UPDATE tbl_os SET data_fechamento = CURRENT_TIMESTAMP WHERE os = $os AND fabrica = $login_fabrica";
	$res = pg_query ($con,$sql);
	$msg_erro .= pg_errormessage($con) ;

	if (strlen ($msg_erro) == 0 AND $login_fabrica == 1) {
		$sql = "SELECT fn_valida_os_item($os, $login_fabrica)";
		$res = @pg_query ($con,$sql);
		$msg_erro = pg_errormessage($con);
	}

	if (strlen ($msg_erro) == 0) {
		$sql = "SELECT fn_finaliza_os($os, $login_fabrica)";
		$res = pg_query ($con,$sql);
		$msg_erro = pg_errormessage($con) ;
		if (strlen ($msg_erro) == 0 and ($login_fabrica==1 or $login_fabrica==24)) {
			$sql = "SELECT fn_estoque_os($os, $login_fabrica)";
			$res = @pg_query ($con,$sql);
			$msg_erro = pg_errormessage($con);
		}
	}
	if (strlen ($msg_erro) == 0 and $login_fabrica==24) { //HD 3426
		$sql = "SELECT fn_estoque_os($os, $login_fabrica)";
		$res = @pg_query ($con,$sql);
	}
		//HD 11082 17347
	if(strlen($msg_erro) ==0 and $login_fabrica==11 and $login_posto==14301){
		$sqlm="SELECT tbl_os.sua_os          ,
					 tbl_os.consumidor_email,
					 tbl_os.serie           ,
					 tbl_posto.nome         ,
					 tbl_produto.descricao  ,
 					 to_char(tbl_os.data_fechamento,'DD/MM/YYYY') as data_fechamento
				from tbl_os
				join tbl_produto using(produto)
				join tbl_posto on tbl_os.posto = tbl_posto.posto
				where os=$os";
		$resm=pg_query($con,$sqlm);
		$msg_erro .= pg_errormessage($con) ;

		$sua_osm           = trim(pg_fetch_result($resm,0,sua_os));
		$consumidor_emailm = trim(pg_fetch_result($resm,0,consumidor_email));
		$seriem            = trim(pg_fetch_result($resm,0,serie));
		$data_fechamentom  = trim(pg_fetch_result($resm,0,data_fechamento));
		$nomem             = trim(pg_fetch_result($resm,0,nome));
		$descricaom        = trim(pg_fetch_result($resm,0,descricao));

		if(strlen($consumidor_emailm) > 0){

			$nome         = "TELECONTROL";
			$email_from   = "helpdesk@telecontrol.com.br";
			$assunto      = traduz("ordem.de.servico.fechada",$con,$cook_idioma);
			$destinatario = $consumidor_emailm;
			$boundary = "XYZ-" . date("dmYis") . "-ZYX";

			$mensagem = traduz("a.ordem.de.serviço.%.referente.ao.produto.%.com.número.de.série.%.foi.fechada.pelo.posto.%.no.dia.%",$con,$cook_idioma,array($sua_osm,$descricaom,$seriem,$nomem,$data_fechamentom));


			$body_top = "--Message-Boundary\n";
			$body_top .= "Content-type: text/html; charset=iso-8859-1\n";
			$body_top .= "Content-transfer-encoding: 7BIT\n";
			$body_top .= "Content-description: Mail message body\n\n";
			@mail($destinatario, utf8_encode($assunto), utf8_encode($mensagem), "From: ".$email_from." \n $body_top ");
		}
	}

	if (strlen ($msg_erro) == 0) {
		$res = pg_query ($con,"COMMIT TRANSACTION");

		//Envia e-mail para o consumidor, avisando da abertura da OS
		//HD 150972
		if (($login_fabrica == 14) || ($login_fabrica == 43) || ($login_fabrica == 66))
		{
			$novo_status_os = "FECHADA";
			include('os_email_consumidor.php');
		}

		echo "ok;XX$os";
	}else{
		$res = @pg_query ($con,"ROLLBACK TRANSACTION");
		echo "erro;$sql ==== $msg_erro ";
	}


	flush();
	exit;
}


$msg = "";


$meses = array(1 => traduz("janeiro",$con,$cook_idioma), traduz("fevereiro",$con,$cook_idioma), traduz("marco",$con,$cook_idioma), traduz("abril",$con,$cook_idioma), traduz("maio",$con,$cook_idioma), traduz("junho",$con,$cook_idioma), traduz("julho",$con,$cook_idioma), traduz("agosto",$con,$cook_idioma), traduz("setembro",$con,$cook_idioma), traduz("outubro",$con,$cook_idioma), traduz("novembro",$con,$cook_idioma), traduz("dezembro",$con,$cook_idioma));






if (strlen($btn_acao) > 0 ) {
	$os_off    = trim (strtoupper ($_POST['os_off']));
	if (strlen($os_off)==0) $os_off = trim(strtoupper($_GET['os_off']));
	$codigo_posto_off       = trim(strtoupper($_POST['codigo_posto_off']));
	if (strlen($codigo_posto_off)==0) $codigo_posto_off = trim(strtoupper($_GET['codigo_posto_off']));
	$posto_nome_off        = trim(strtoupper($_POST['posto_nome_off']));
	if (strlen($posto_nome_off)==0) $posto_nome_off = trim(strtoupper($_GET['posto_nome_off']));

	$marca     = trim ($_POST['marca']);
	if (strlen($marca)==0) $marca = trim($_GET['marca']);
	if(strlen($marca)>0){ $cond_marca = " tbl_marca.marca = $marca ";}else{ $cond_marca = " 1 = 1 ";}

	$sua_os = trim (strtoupper ($_POST['sua_os']));
	if (strlen($sua_os) == 0)
		$sua_os    = trim (strtoupper ($_GET['sua_os']));


	if(strlen($sua_os)>0 AND strlen($sua_os)<4){
		$msg = traduz("favor.digitar.no.minimo.4(quatro).caracteres",$con,$cook_idioma);
	}
	$serie     = trim (strtoupper ($_POST['serie']));
	if (strlen($serie)==0) $serie = trim(strtoupper($_GET['serie']));
	$nf_compra = trim (strtoupper ($_POST['nf_compra']));
	if (strlen($nf_compra)==0) $nf_compra = trim(strtoupper($_GET['nf_compra']));
	$consumidor_cpf = trim (strtoupper ($_POST['consumidor_cpf']));
	if (strlen($consumidor_cpf)==0) $consumidor_cpf = trim(strtoupper($_GET['consumidor_cpf']));

	$mes = trim (strtoupper ($_POST['mes']));
	if (strlen($mes)==0) $mes = trim(strtoupper($_GET['mes']));
	$ano = trim (strtoupper ($_POST['ano']));
	if (strlen($ano)==0) $ano = trim(strtoupper($_GET['ano']));

	$codigo_posto       = trim(strtoupper($_POST['codigo_posto']));
	if (strlen($codigo_posto)==0) $codigo_posto = trim(strtoupper($_GET['codigo_posto']));
	$posto_nome         = trim(strtoupper($_POST['posto_nome']));
	if (strlen($posto_nome)==0) $posto_nome = trim(strtoupper($_GET['posto_nome']));
	$consumidor_nome    = trim(strtoupper($_POST['consumidor_nome']));
	if (strlen($consumidor_nome)==0) $consumidor_nome = trim(strtoupper($_GET['consumidor_nome']));
	$produto_referencia = trim(strtoupper($_POST['produto_referencia']));
	if (strlen($produto_referencia)==0) $produto_referencia = trim(strtoupper($_GET['produto_referencia']));
	$os_aberta          = trim(strtoupper($_POST['os_aberta']));
	if (strlen($os_aberta)==0) $os_aberta = trim(strtoupper($_GET['os_aberta']));
	$revenda_cnpj       = trim(strtoupper($_POST['revenda_cnpj']));
	if (strlen($revenda_cnpj)==0) $revenda_cnpj = trim(strtoupper($_GET['revenda_cnpj']));

	$natureza = trim($_POST['natureza']); //HD 45630

	if(strlen($natureza)>0){
		$cond_natureza = " tbl_os.tipo_atendimento = $natureza ";
	}else{
		$cond_natureza = " 1 = 1 ";
	}

	if ($login_e_distribuidor <> 't') $codigo_posto = $login_codigo_posto ;

	$consumidor_cpf = str_replace (".","",$consumidor_cpf);
	$consumidor_cpf = str_replace (" ","",$consumidor_cpf);
	$consumidor_cpf = str_replace ("-","",$consumidor_cpf);
	$consumidor_cpf = str_replace ("/","",$consumidor_cpf);
	if (strlen ($consumidor_cpf) <> 11 AND strlen ($consumidor_cpf) <> 14 AND strlen ($consumidor_cpf) <> 0) {
		#HD 17333
		$msg = traduz("tamanho.do.cpf.do.consumidor.invalido",$con,$cook_idioma);
	}

	$revenda_cnpj = str_replace (".","",$revenda_cnpj);
	$revenda_cnpj = str_replace (" ","",$revenda_cnpj);
	$revenda_cnpj = str_replace ("-","",$revenda_cnpj);
	$revenda_cnpj = str_replace ("/","",$revenda_cnpj);
	if (strlen ($revenda_cnpj) <> 8 AND strlen ($revenda_cnpj) > 0) {
		$msg = traduz("digite.os.8.primeiros.digitos.do.cnpj",$con,$cook_idioma);
	}


	if (strlen ($nf_compra) > 0 ) {
		if (($login_fabrica==19) and strlen($nf_compra) > 6) {
			$nf_compra = "0000000" . $nf_compra;
			$nf_compra = substr ($nf_compra,strlen ($nf_compra)-7);
		} elseif($login_fabrica <> 11) {
			if($login_fabrica == 3){
				$nf_compra = $nf_compra;
			}else{
				$nf_compra = "000000" . $nf_compra;
				$nf_compra = substr ($nf_compra,strlen ($nf_compra)-6);
			}
			//echo $nf_compra;
		}
	}

	/*if (strlen ($nf_compra) > 0 ) {
		if ($login_fabrica==19 or $login_fabrica==11) {
			$nf_compra = "0000000" . $nf_compra;
			$nf_compra = substr ($nf_compra,strlen ($nf_compra)-7);
		} else {
			$nf_compra = "000000" . $nf_compra;
			$nf_compra = substr ($nf_compra,strlen ($nf_compra)-6);
		}
	}*/

	$os_posto = trim (strtoupper ($_POST['os_posto']));
	if (strlen($os_posto)==0) $os_posto = trim(strtoupper($_GET['os_posto']));

	if ( strlen ($sua_os) == 0 AND strlen ($rg_produto) == 0 AND strlen ($serie) == 0 AND strlen ($nf_compra) == 0 AND strlen ($consumidor_cpf) == 0 AND  strlen ($mes) == 0 AND strlen ($ano) == 0 AND $login_fabrica<>"7" AND strlen($os_posto)==0) {
		$msg = traduz("selecione.o.mes.e.o.ano.para.fazer.a.pesquisa",$con,$cook_idioma);
	}

	if (strlen ($mes) == 0 AND strlen ($ano) > 0 AND $login_fabrica<>"7") {
		$msg = traduz("selecione.o.mes",$con,$cook_idioma);
	}

	if ( strlen ($posto_nome) > 0 AND strlen ($posto_nome) < 4 ) {
		$msg = traduz("digite.no.minimo.4.letras.para.o.nome.do.posto",$con,$cook_idioma);
	}

	if ( strlen ($consumidor_nome) > 0 AND strlen ($consumidor_nome) < 4) {
		$msg = traduz("digite.no.minimo.4.letras.para.o.nome.do.consumidor",$con,$cook_idioma);
	}

	if ( strlen ($serie) > 0 AND strlen ($serie) < 5) {
		$msg = traduz("digite.no.minimo.5.letras.para.o.numero.de.serie",$con,$cook_idioma);
	}

if($login_fabrica != 2){ // HD 81252
	if ( strlen ($os_posto) > 0 AND strlen ($os_posto) < 5) {
		$msg = traduz("digite.no.minimo.5.letras.para.a.os.revendedor",$con,$cook_idioma);
	}
}

	if($login_fabrica==7){
		if(strlen($data_inicial)>0 AND $data_inicial<>"dd/mm/aaaa"){
			$xdata_inicial = fnc_formata_data_pg($data_inicial);
			$xdata_inicial = str_replace("'","",$xdata_inicial);
			$mes = "1";
		}else if(strlen($sua_os)==0){
			$msg = traduz("digite.a.data.inicial.para.fazer.a.pesquisa",$con,$cook_idioma);
		}

		if(strlen($data_final)>0 AND $data_final<>"dd/mm/aaaa"){
			$xdata_final = fnc_formata_data_pg($data_final);
			$xdata_final = str_replace("'","",$xdata_final);
			$mes = "1";
		}else if(strlen($sua_os)==0){
			$msg = traduz("digite.a.data.final.para.fazer.a.pesquisa",$con,$cook_idioma);
		}

		if(strlen($data_inicial)>0 AND $data_inicial<>"dd/mm/aaaa" AND strlen($data_final)>0 AND $data_final<>"dd/mm/aaaa"){
			$sqlX = "SELECT ('$xdata_final'::date - '$xdata_inicial'::date)";
			#echo $sqlX;
			$resX = pg_query($con,$sqlX);
			$periodo = pg_fetch_result($resX,0,0);
			if($periodo > "30") $msg = traduz("periodo.nao.pode.ser.maior.que.30.dias",$con,$cook_idioma);
		$data_inicial = $data_inicial." 00:00:00";
		$data_final = $data_final." 23:59:59";
		}
	}else{
		if (strlen($mes) > 0) {
			$data_inicial = date("Y-m-01 00:00:00", mktime(0, 0, 0, $mes, 1, $ano));
			$data_final   = date("Y-m-t 23:59:59", mktime(0, 0, 0, $mes, 1, $ano));
		}
	}

	if (strlen($msg) == 0 && strlen($opcao2) > 0) {
		if (strlen(trim($_POST["posto_codigo"])) > 0) $posto_codigo = trim($_POST["posto_codigo"]);
		if (strlen(trim($_GET["posto_codigo"])) > 0)  $posto_codigo = trim($_GET["posto_codigo"]);
		if (strlen(trim($_POST["posto_nome"])) > 0) $posto_nome = trim($_POST["posto_nome"]);
		if (strlen(trim($_GET["posto_nome"])) > 0)  $posto_nome = trim($_GET["posto_nome"]);
		if (strlen(trim($_GET["produto_referencia"])) > 0)  $produto_referencia = trim($_GET["produto_referencia"]);

		if (strlen($posto_codigo) > 0 && strlen($posto_nome) > 0) {
			$sql =	"SELECT tbl_posto.posto                ,
							tbl_posto.nome                 ,
							tbl_posto_fabrica.codigo_posto
					FROM tbl_posto
					JOIN tbl_posto_fabrica USING (posto)
					WHERE tbl_posto_fabrica.fabrica = $login_fabrica
					AND   tbl_posto_fabrica.codigo_posto = '$posto_codigo';";
			$res = pg_query($con,$sql);
			if (pg_num_rows($res) == 1) {
				$posto        = trim(pg_fetch_result($res,0,posto));
				$posto_codigo = trim(pg_fetch_result($res,0,codigo_posto));
				$posto_nome   = trim(pg_fetch_result($res,0,nome));
			}else{
				$erro .= traduz("posto.nao.encontrado",$con,$cook_idioma);
			}
		}
	}
}

$layout_menu = "os";
$title = traduz("selecao.de.parametros.para.relacao.de.ordens.de.servicos.lancadas",$con,$cook_idioma);

include "cabecalho.php";
?>

<style type="text/css">
.Titulo {
	text-align: center;
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: bold;
	color: #FFFFFF;
	background-color: #596D9B;
}
.Conteudo {
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
}
</style>

<? include "javascript_pesquisas.php"; ?>
<script language="javascript" src="js/cal2.js"></script>
<script language="javascript" src="js/cal_conf2.js"></script>
<script language="javascript" src="js/assist.js"></script>
<script language='javascript' src='ajax.js'></script>
<script type="text/javascript" src="js/bibliotecaAJAX.js"></script>
<? include "javascript_calendario_new.php"; //adicionado por Fabio 27-09-2007 ?>

<script language='javascript'>

	/* HD 133499 */
	function disp_prompt(os, sua_os){
		var motivo =prompt("Qual o Motivo da Exclusão da os "+sua_os+" ?",'',"Motivo da Exclusão");
		if (motivo !=null && $.trim(motivo) !="" && motivo.length > 0 ){
			var resultado = $.ajax({
				type: "GET",
				url: 'grava_obs_excluida.php',
				data: 'motivo=' + motivo + '&os=' + os,
				cache: false,
				async: false,
				complete: function(resposta) {
					verifica_res = resposta.responseText;
					if (verifica_res =='ok'){
						return true;
					}
				}
			 }).responseText;

			if (resultado =='ok'){
				return true;
			}else{
				alert(resultado,'Erro');
			}
		}else{
			alert('Digite um motivo por favor!','Erro');
			return false;
		}
	}

function DataHora(evento, objeto){
	var keypress=(window.event)?event.keyCode:evento.which;
	campo = eval (objeto);
	if (campo.value == '00/00/0000')
	{
		campo.value=""
	}

	caracteres = '0123456789';
	separacao1 = '/';
	separacao2 = ' ';
	separacao3 = ':';
	conjunto1 = 2;
	conjunto2 = 5;
	conjunto3 = 10;
	conjunto4 = 13;
	conjunto5 = 16;
	if ((caracteres.search(String.fromCharCode (keypress))!=-1) && campo.value.length < (19))
	{
		if (campo.value.length == conjunto1 )
		campo.value = campo.value + separacao1;
		else if (campo.value.length == conjunto2)
		campo.value = campo.value + separacao1;
		else if (campo.value.length == conjunto3)
		campo.value = campo.value + separacao2;
		else if (campo.value.length == conjunto4)
		campo.value = campo.value + separacao3;
		else if (campo.value.length == conjunto5)
		campo.value = campo.value + separacao3;
	}
	else
		event.returnValue = false;
}



$(function()
{
	$('#data_inicial').datePicker({startDate:'01/01/2000'});
	$('#data_final').datePicker({startDate:'01/01/2000'});
	$("#data_inicial").maskedinput("99/99/9999");
	$("#data_final").maskedinput("99/99/9999");


});

function Trim(s){
	var l=0;
	var r=s.length -1;

	while(l < s.length && s[l] == ' '){
		l++;
	}
	while(r > l && s[r] == ' '){
		r-=1;
	}
	return s.substring(l, r+1);
}
function _trim (s)
{
   //   /            open search
   //     ^            beginning of string
   //     \s           find White Space, space, TAB and Carriage Returns
   //     +            one or more
   //   |            logical OR
   //     \s           find White Space, space, TAB and Carriage Returns
   //     $            at end of string
   //   /            close search
   //   g            global search

   return s.replace(/^\s+|\s+$/g, "");
}

function retornaFechamentoOS (http , sinal, excluir, lancar) {
	if (http.readyState == 4) {
		if (http.status == 200) {
			results = http.responseText.split(";");
			if (typeof (results[0]) != 'undefined') {
				if (_trim(results[0]) == 'ok') {
					alert ('OS <? fecho("fechada.com.sucesso",$con,$cook_idioma) ?>');
					sinal.src='/assist/admin/imagens_admin/status_vermelho.gif';
					sinal.src='/assist/imagens/pixel.gif';
					excluir.src='/assist/imagens/pixel.gif';
					if(lancar){
						lancar.src='/assist/imagens/pixel.gif';
					}
				}else{
					if (http.responseText.indexOf ('de-obra para instala')>0){
						alert ('<? fecho("esta.os.nao.tem.mao-de-obra.para.instalacao",$con,$cook_idioma) ?>');
					}else if (http.responseText.indexOf ('Nota Fiscal de Devol')>0){
						alert ('<? fecho("por.favor.utilizar.a.tela.de.fechamento.de.os.para.informar.a.nota.fiscal.de.devolucao",$con,$cook_idioma) ?>');
					}else if (http.responseText.indexOf ('o-de-obra para atendimento')>0){
						alert ('<? fecho("esta.os.nao.tem.mao-de-obra.para.este.atendimento",$con,$cook_idioma) ?>');
					}else if (http.responseText.indexOf ('Favor informar aparência do produto e acessórios')>0){
						alert ('<? fecho("por.favor.verifique.os.dados.digitados.aparencia.e.acessorios.na.tela.de.lancamento.de.itens",$con,$cook_idioma) ?>');
					}else if (http.responseText.indexOf ('Type informado para o produto não é válido')>0){
						alert ('<? fecho("type.informado.para.o.produto.nao.e.valido",$con,$cook_idioma) ?>');
					}else if (http.responseText.indexOf ('OS com peças pendentes')>0){
						alert ('<? fecho("os.com.pecas.pendentes,.favor.informar.o.motivo.na.tela.de.fechamento.da.os",$con,$cook_idioma) ?>');
					}else if (http.responseText.indexOf ('OS não pode ser fechada, Favor Informar a Kilometragem')>0){
						alert ('<? fecho("os.nao.pode.ser.fechada,.favor.informar.a.kilometragem",$con,$cook_idioma) ?>');
					}else if (http.responseText.indexOf ('OS não pode ser fechada, Kilometragem Recusada')>0){
						alert ('<? fecho("os.nao.pode.ser.fechada,.kilometragem.recusada",$con,$cook_idioma) ?>');
					}else if (http.responseText.indexOf ('OS não pode ser fechada, aguardando aprovação de Kilometragem')>0){
						alert ('<? fecho("os.nao.pode.ser.fechada,.aguardando.aprovacao.de.kilometragem",$con,$cook_idioma) ?>');
					}else if (http.responseText.indexOf ('Esta OS teve o número de série recusado e não pode ser finalizada')>0){
						alert ('<? fecho("esta.os.teve.o.numero.de.serie.recusado.e.nao.pode.ser.finalizada",$con,$cook_idioma) ?>');
					}else if (http.responseText.indexOf ('Informar defeito constatado (Reparo) para OS')>0){
						alert ('<? fecho("por.favor.verifique.os.dados.digitados.em.defeito.constatado.(reparo).na.tela.de.lancamento.de.itens",$con,$cook_idioma) ?>');
					}else if (http.responseText.indexOf ('Por favor, informar o conserto do produto na tela CONSERTADO')>0){
						alert ('<? fecho("por.favor.informar.o.conserto.do.produto.na.tela.consertado",$con,$cook_idioma) ?>');
					}else if (http.responseText.indexOf ('Favor informar solução tomada para a ordem de serviço')>0){
						alert ('<? fecho("oss.sem.solucao.e.sem.itens.lancados",$con,$cook_idioma) ?>');
					}else if (http.responseText.indexOf ('Favor informar o defeito constatado para a ordem de serviço')>0){
						alert ('<? fecho("oss.sem.defeito.constatado",$con,$cook_idioma) ?>');
					}else {alert ('<? fecho("por.favor.verifique.os.dados.digitados.defeito.constatado.e.solucao.na.tela.de.lancamento.de.itens",$con,$cook_idioma) ?>');
					}
				}
			}else{
				alert ('<? fecho("fechamento.nao.processado",$con,$cook_idioma) ?>');
			}
		}
	}
}

function fechaOSnovo(linha) {


div = document.getElementById('div_fechar_'+linha);

div.style.display='block';

}

function retornaFechamentoOS2(http,sinal,excluir,lancar,linha,div_anterior) {
	var div;
	div = document.getElementById('div_fechar_'+linha);
	if (http.readyState == 4) {
		if (http.status == 200) {
			results = http.responseText.split(";");
			if (typeof (results[0]) != 'undefined'){
				if (_trim(results[0]) == 'ok') {
					sinal.src='/assist/admin/imagens_admin/status_vermelho.gif';
					sinal.src='/assist/imagens/pixel.gif';
					excluir.src='/assist/imagens/pixel.gif';
					div.style.display='none';
					if(lancar){
						lancar.src='/assist/imagens/pixel.gif';
					}
					alert ('fechada.com.sucesso');
				}
				else {
					var msg = _trim(results[5]);
					alert(msg);
					div.innerHTML = div_anterior;
					}
			}
		}
	}
}


function fechaOSnovo2(os,data,sinal,excluir,lancar,linha) {
	var data_fechamento = data;
	var div = document.getElementById('div_fechar_'+linha);
	var divmostrar = document.getElementById('mostrar_'+linha);
	var hora;
	var div_anterior;
	hora = new Date();


	div.style.display = "none";
	divmostrar.innerHTML = "<img src='admin/a_imagens/ajax-loader.gif'>"
	divmostrar.style.display = "block";

	var url = "ajax_fecha_os.php?fecharnovo=sim&os=" + escape(os) + '&data_fechamento='+data+'&cachebypass='+hora.getTime();
	var fecha = $.ajax({
					type: "GET",
					url: url,
					cache: false,
					async: false
	 }).responseText;

	var fecha_array = 0;
	fecha_array = fecha.split(";");

		if (fecha_array[0]=='ok') {
			sinal.src='/assist/admin/imagens_admin/status_vermelho.gif';
			sinal.src='/assist/imagens/pixel.gif';
			excluir.src='/assist/imagens/pixel.gif';
			div.style.display='none';
			if(lancar){
				lancar.src='/assist/imagens/pixel.gif';
			}
			alert('Os Fechada com Sucesso');
			divmostrar.style.display = "none";

		}
		else {
			var msg               = fecha_array[1];
			if (msg == 'tbl_os&quot') {
				alert('Por favor confira a data digitada!');
			}
			else {
				var msg               = fecha_array[1];
				alert('Por favor confira a data digitada!');
			}

		divmostrar.style.display = "none";
		div.style.display = "block";
		$('#ajax_'+linha).val(fecha);
		}
}

function fechaOS (os , sinal , excluir , lancar ) {
	var curDateTime = new Date();
	url = "<?= $PHP_SELF ?>?fechar=" + escape(os) + '&dt='+curDateTime;
	http.open("GET", url , true);
	http.onreadystatechange = function () { retornaFechamentoOS (http , sinal, excluir, lancar) ; } ;
	http.send(null);
}


function retornaConsertadoOS (http ,botao ) {
	if (http.readyState == 4) {
		if (http.status == 200) {
			var results = http.responseText.split("|");
			if (typeof (results[0]) != 'undefined'){
				if (_trim(results[0]) == 'ok') {
					botao.style.display='none';
				}else{
					if(results[1]){
						alert(results[1]);
					}
					alert('<? fecho("acao.nao.concluida.tente.novamente",$con,$cook_idioma) ?>');
				}
			}else{
				alert ('<? fecho("acao.nao.foi.concluida.com.sucesso",$con,$cook_idioma) ?>');
			}
		}
	}
}

function consertadoOS (os , botao ) {
	var curDateTime = new Date();
	url = "<?= $PHP_SELF ?>?consertado=" + escape(os)+'&dt='+curDateTime ;
	http.open("GET", url , true);
	http.onreadystatechange = function () { retornaConsertadoOS (http , botao ) ; } ;
	http.send(null);
}

</script>


<br>




<?
if (strlen($msg) > 0) {
	echo "<h1>$msg</h1>";
}

if (strlen($msg_erro) > 0) {
	echo "<font face='arial' size='+1' color='#FF6633'><b>$msg_erro</b></font>";
}


if ((strlen($btn_acao) > 0 AND strlen($msg) == 0) OR strlen($btn_acao_pre_os) > 0) {
	if (strlen($btn_acao_pre_os) > 0){
		$sqlinf = "SELECT hd_chamado, '' as sua_os, serie, nota_fiscal,
		TO_CHAR(tbl_hd_chamado.data,'DD/MM/YYYY')   AS data,
		tbl_hd_chamado_extra.nome, tbl_marca.nome as marca_nome, tbl_produto.referencia, tbl_produto.descricao
		FROM tbl_hd_chamado_extra
		JOIN tbl_hd_chamado using(hd_chamado)
		LEFT JOIN tbl_produto on tbl_hd_chamado_extra.produto = tbl_produto.produto
		LEFT JOIN tbl_marca   on tbl_produto.marca = tbl_marca.marca
		WHERE tbl_hd_chamado.fabrica = $login_fabrica
		AND tbl_hd_chamado_extra.posto = $login_posto
		AND tbl_hd_chamado_extra.abre_os = 't'
		AND tbl_hd_chamado.status != 'Resolvido'
		AND tbl_hd_chamado_extra.os is null;";
		//echo nl2br($sqlinf);
		$res = @pg_query ($con,$sqlinf);

	}else{

		if ($login_e_distribuidor <> 't') {
			$posto = $login_posto ;
		}

		$join_especifico = " FROM tbl_os ";
		$especifica_mais_1 = "1=1";
		$especifica_mais_2 = "1=1";

		if (strlen ($data_inicial) > 0 AND $data_inicial <> "dd/mm/aaaa") {
			if (strlen ($produto_referencia) > 0) {
				$sqlX = "SELECT produto FROM tbl_produto JOIN tbl_linha USING (linha) WHERE tbl_linha.fabrica = $login_fabrica AND tbl_produto.referencia = '$produto_referencia'";
				$resX = pg_query ($con,$sqlX);
				$produto = pg_fetch_result ($resX,0,0);
				$especifica_mais_1 = "tbl_os.produto = $produto";
			}

			if (strlen($os_aberta) > 0) {
				$especifica_mais_2 = "tbl_os.os_fechada IS FALSE
									  AND tbl_os.excluida IS NOT TRUE ";
			}

			$join_especifico = "FROM (  SELECT os
										FROM tbl_os
										JOIN tbl_os_extra USING (os)
										LEFT JOIN tbl_produto ON tbl_os.produto = tbl_produto.produto
										JOIN (SELECT posto FROM tbl_posto_linha JOIN tbl_linha USING (linha)
												WHERE tbl_linha.fabrica = $login_fabrica
												AND tbl_posto_linha.distribuidor = $login_posto
												UNION
												SELECT $login_posto
										) posto ON tbl_os.posto = posto.posto
										WHERE fabrica      = $login_fabrica
										AND   tbl_os.posto = $login_posto
										AND   tbl_os.data_digitacao BETWEEN '$data_inicial' AND '$data_final'
										AND   $especifica_mais_1
										AND   $especifica_mais_2
								) oss JOIN tbl_os ON tbl_os.os = oss.os";
								
			###-- Tulio --- 2009-08-13 - Decidi parar de pesquisar os postos do distribuidor ---#
			$join_especifico = "FROM (  SELECT os
										FROM tbl_os
										JOIN tbl_os_extra USING (os)
										LEFT JOIN tbl_produto ON tbl_os.produto = tbl_produto.produto
										WHERE fabrica      = $login_fabrica
										AND   tbl_os.posto = $login_posto
										AND   tbl_os.data_digitacao BETWEEN '$data_inicial' AND '$data_final'
										AND   $especifica_mais_1
										AND   $especifica_mais_2
								) oss JOIN tbl_os ON tbl_os.os = oss.os";
								
								
								
		}
		//HD 14927
		if($login_fabrica == 11 or $login_fabrica == 45 or $login_fabrica == 15 or $login_fabrica == 3 or $login_fabrica ==43 or $login_fabrica ==66 or $login_fabrica == 14){
			$sql_data_conserto=" , to_char(tbl_os.data_conserto,'DD/MM/YYYY') as data_conserto ";
		}
		// OS não excluída
		$sql =  "SELECT distinct tbl_os.os                                                ,
						tbl_os.sua_os                                                     ,
						sua_os_offline                                                    ,
						LPAD(tbl_os.sua_os,20,'0')                   AS ordem             ,
						TO_CHAR(tbl_os.data_digitacao,'DD/MM/YYYY')  AS digitacao         ,
						TO_CHAR(tbl_os.data_abertura,'DD/MM/YYYY')   AS abertura          ,
						tbl_os.data_abertura                                              ,
						TO_CHAR(tbl_os.data_fechamento,'DD/MM/YYYY') AS fechamento        ,
						TO_CHAR(tbl_os.finalizada,'DD/MM/YYYY')      AS finalizada        ,
						tbl_os.serie                                                      ,
						tbl_os.excluida                                                   ,
						tbl_os.motivo_atraso                                              ,
						tbl_os.tipo_os_cortesia                                           ,
						tbl_os.consumidor_revenda                                         ,
						tbl_os.consumidor_nome                                            ,
						tbl_os.revenda_nome                                               ,
						tbl_os.tipo_atendimento                                           ,
						tbl_os.tecnico_nome                                               ,
						tbl_os.admin                                                      ,
						tbl_os.rg_produto                                                 ,
						tbl_os.os_reincidente                      AS reincidencia        ,
						tbl_os.valores_adicionais                                         ,
						tbl_os.nota_fiscal                                                ,
						tbl_os.nota_fiscal_saida                                          ,
						tbl_tipo_atendimento.descricao                                    ,
						tbl_posto_fabrica.codigo_posto                                    ,
						tbl_posto.nome                              AS posto_nome         ,
						tbl_os_extra.impressa                                             ,
						tbl_os_extra.extrato                                              ,
						tbl_os_extra.os_reincidente                                       ,
						tbl_produto.referencia                      AS produto_referencia ,
						tbl_produto.descricao                       AS produto_descricao  ,
						tbl_produto.voltagem                        AS produto_voltagem   ,
						tbl_produto.linha                                                 ,
						distrib.codigo_posto                        AS codigo_distrib     ,";
						if ($login_fabrica == 3) {
								$sql .= "tbl_marca.marca ,
										tbl_marca.nome as marca_nome,";
						}
						$sql .= "
						(SELECT status_os FROM tbl_os_status WHERE tbl_os.os = tbl_os_status.os ORDER BY data DESC LIMIT 1) AS status_os,
						tbl_os.consumidor_email
						$sql_data_conserto
				$join_especifico
				JOIN      tbl_posto         ON  tbl_posto.posto           = tbl_os.posto
				JOIN      tbl_posto_fabrica ON  tbl_posto_fabrica.posto   = tbl_posto.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
				LEFT JOIN tbl_produto       ON  tbl_produto.produto       = tbl_os.produto
				JOIN      tbl_os_extra      ON  tbl_os_extra.os           = tbl_os.os";
				//colocado takashi
		if (strlen($os_situacao) > 0) {
			$sql .= " JOIN tbl_extrato ON tbl_extrato.extrato = tbl_os_extra.extrato";
			if ($os_situacao == "PAGA")
				$sql .= " JOIN tbl_extrato_financeiro ON tbl_extrato_financeiro.extrato = tbl_extrato.extrato";
		}
		if ($login_fabrica == 3) {
			$sql .= " LEFT JOIN tbl_marca ON tbl_marca.marca = tbl_produto.marca ";
		}

		if($login_fabrica == 7 ){
			$sql .= " AND $cond_natureza ";
		}

		###-- Tulio --- 2009-08-13 - Decidi parar de pesquisar os postos do distribuidor ---#
		###		AND   (tbl_os.posto  = $login_posto  OR (tbl_posto_linha.distribuidor = $login_posto) )";
		$sql .=	"
				LEFT JOIN tbl_posto_fabrica distrib ON tbl_os.digitacao_distribuidor = distrib.posto AND distrib.fabrica = $login_fabrica
				LEFT JOIN tbl_posto_linha           ON tbl_posto_linha.linha         = tbl_produto.linha AND tbl_posto_linha.posto = tbl_os.posto
				LEFT JOIN tbl_tipo_atendimento      ON tbl_tipo_atendimento.tipo_atendimento = tbl_os.tipo_atendimento
				WHERE tbl_os.fabrica = $login_fabrica
				AND   tbl_os.posto  = $login_posto ";


		if($login_fabrica <>3 AND $login_fabrica<>11 AND $login_fabrica<>20 AND $login_fabrica<>50 AND $login_fabrica<>35 and $login_fabrica <> 14) {
			$sql .=" AND   tbl_os.excluida IS NOT TRUE
					 AND  (status_os NOT IN (13,15) OR status_os IS NULL)";
		}
		#HD 13940 - Para mostrar as OS recusadas
		if($login_fabrica==20) {
			$sql .=" AND (tbl_os.excluida IS NOT TRUE OR tbl_os_extra.status_os = 94 )
					 AND  (status_os NOT IN (13,15) OR status_os IS NULL)";
		}

		$sql .= "AND  (status_os NOT IN (13,15) OR status_os IS NULL)";

		if (strlen($mes) > 0) {
			$sql .= " AND tbl_os.data_digitacao BETWEEN '$data_inicial' AND '$data_final'";
		}

		if (strlen($posto_nome) > 0) {
			$posto_nome = strtoupper ($posto_nome);
			$sql .= " AND tbl_posto.nome LIKE '$posto_nome%' ";
		}
		if (strlen($codigo_posto) > 0) {
			$sql .= " AND (tbl_posto_fabrica.codigo_posto = '$codigo_posto' OR (distrib.codigo_posto = '$codigo_posto' AND distrib.codigo_posto IS NOT NULL ))";
		}

		if (strlen($produto_referencia) > 0) {
			$sql .= " AND tbl_produto.referencia = '$produto_referencia' ";
		}

		if (strlen($admin) > 0) {
			$sql .= " AND tbl_os.admin = '$admin' ";
		}
		if($login_fabrica == 3 ){
			$sql .= " AND $cond_marca ";
		}
		#SISTEMA RG
		if(strlen($rg_produto)>0){
			if($login_fabrica== 6) {
				if($login_posto== 4262) {
					$sql .= " AND tbl_os.rg_produto ilike '%$rg_produto%'";
				}
			} else {
				$sql .= " AND tbl_os.os IN (SELECT os FROM tbl_produto_rg_item WHERE rg = '$rg_produto') ";
			}
		}
		if(strlen($os_posto)>0){
			$sql .= " AND tbl_os.os_posto ilike '%$os_posto%'";
		}
		if (strlen($sua_os) > 0) {
			#A Black tem consulta separada(os_consulta_avancada.php).
			if ($login_fabrica == 1) {
				$pos = strpos($sua_os, "-");
				if ($pos === false) {
					$pos = strlen($sua_os) - 5;
				}else{
					$pos = $pos - 5;
				}
				$sua_os = substr($sua_os, $pos,strlen($sua_os));
			}
			$sua_os = strtoupper ($sua_os);

			$pos = strpos($sua_os, "-");
			if ($pos === false) {
				if(!ctype_digit($sua_os)){
					$sql .= " AND tbl_os.sua_os = '$sua_os' ";
				}else{
					$sql .= " AND tbl_os.os_numero = '$sua_os' ";
				}
			}else{
				$conteudo = explode("-", $sua_os);
				$os_numero    = $conteudo[0];
				$os_sequencia = $conteudo[1];
				if(!ctype_digit($os_sequencia)){
					$sql .= " AND tbl_os.sua_os = '$sua_os' ";
				}else{
					$sql .= " AND tbl_os.os_numero = '$os_numero' AND tbl_os.os_sequencia = '$os_sequencia' ";
				}
			}
		}

		if (strlen($os_off) > 0) {
			$sql .= " AND (tbl_os.sua_os_offline LIKE '$os_off%' OR tbl_os.sua_os_offline LIKE '0$os_off%' OR tbl_os.sua_os_offline LIKE '00$os_off%') ";
		}



		if (strlen($serie) > 0) {
#			$sql .= " AND UPPER(tbl_os.serie) = '$serie'"; # samuel alterou 02-07-2009
			$sql .= " AND tbl_os.serie = '$serie'";
		}

		if (strlen($nf_compra) > 0) {
			$sql .= " AND tbl_os.nota_fiscal = '$nf_compra'";
		}

		if (strlen($consumidor_nome) > 0) {
			$consumidor_nome = strtoupper ($consumidor_nome);
			$sql .= " AND tbl_os.consumidor_nome ILIKE '%$consumidor_nome%'";
		}

		if (strlen($consumidor_cpf) > 0) {
			$sql .= " AND tbl_os.consumidor_cpf = '$consumidor_cpf'";
		}

		if (strlen($os_aberta) > 0) {
			$sql .= " AND tbl_os.os_fechada IS FALSE
					  AND tbl_os.excluida IS NOT TRUE";
		}

		if (strlen($revenda_cnpj) > 0) {
			$sql .= " AND (tbl_os.data_fechamento IS NULL AND tbl_os.consumidor_revenda = 'R' AND tbl_os.revenda_cnpj LIKE '$revenda_cnpj%') ";
		}

		if($login_fabrica==1){
			$sql .= " AND tbl_os.consumidor_revenda = 'C' AND tbl_os.cortesia is not true ";
		}

		if ($login_fabrica == 7){
			$sql .= " ORDER BY tbl_os.data_abertura ASC, LPAD(tbl_os.sua_os,20,'0') ASC ";
		} else{
#			$sql .= " ORDER BY LPAD(tbl_os.sua_os,20,'0') DESC"; Samuel alterou 02-07-2009
			$sql .= " ORDER BY tbl_os.sua_os DESC";
		}

    //echo nl2br($sql);
	$sqlT = str_replace ("\n"," ",$sql) ;
	$sqlT = str_replace ("\t"," ",$sqlT) ;
	$resT = @pg_query ($con,"/* QUERY -> $sqlT  */");

	flush();
	if ($ip=="189.96.95.181") {
		//echo nl2br($sql);
	}
	//$res = pg_query($con,$sql);flush();

	##### PAGINAÇÃO - INÍCIO #####
	$sqlCount  = "SELECT count(*) FROM (";
	$sqlCount .= $sql;
	$sqlCount .= ") AS count";

	#require "_class_paginacao_teste.php";
	require "_class_paginacao.php";

	// definicoes de variaveis
	$max_links = 11;				// máximo de links à serem exibidos
	$max_res   = 50;				// máximo de resultados à serem exibidos por tela ou pagina
	$mult_pag= new Mult_Pag();	// cria um novo objeto navbar
	$mult_pag->num_pesq_pag = $max_res; // define o número de pesquisas (detalhada ou não) por página

	$res = $mult_pag->Executar($sql, $sqlCount, $con, "otimizada", "pgsql");

	##### PAGINAÇÃO - FIM #####

	}
	$resultados = pg_num_rows($res);

	if (pg_num_rows($res) > 0) {
		##### LEGENDAS - INÍCIO #####

		echo "<div align='left' style='position: relative; left: 25'>";
		echo "<table border='0' cellspacing='0' cellpadding='0'>";
		if ($excluida == "t") {
			echo "<tr height='18'>";
			echo "<td width='18' bgcolor='#FFE1E1'>&nbsp;</td>";
			echo "<td align='left'><font size='1'><b>&nbsp;";fecho("excluidas.do.sistema",$con,$cook_idioma);echo "</b></font></td>";
			echo "</tr>";
			echo "<tr height='3'><td colspan='2'></td></tr>";
		}
		if ($login_fabrica != 1) {
			echo "<tr height='18'>";
			echo "<td width='18' bgcolor='#D7FFE1'>&nbsp;</td>";
			echo "<td align='left'><font size='1'><b>&nbsp; ";
			fecho("reincidencia",$con,$cook_idioma);
			echo "</b></font></td>";
			echo "</tr>";
			echo "<tr height='3'><td colspan='2'></td></tr>";

			if($login_fabrica <> 14){
				echo "<tr height='18'>";
				echo "<td width='18' bgcolor='#91C8FF'>&nbsp;</td>";
				echo "<td align='left'><font size='1'><b>&nbsp; ";

				fecho("os.aberta.a.mais.de.25.dias",$con,$cook_idioma);

				echo "</b></font></td>";
				echo "</tr>";
				echo "<tr height='3'><td colspan='2'></td></tr>";
			}

			if($login_fabrica == 50){
				echo "<tr height='18'>";
				echo "<td width='18' bgcolor='#FF9933'>&nbsp;</td>";
				echo "<td align='left'><font size='1'><b>&nbsp; ";
					fecho("os.recusada",$con,$cook_idioma);
				echo "</b></font></td>";
				echo "</tr>";
				echo "<tr height='3'><td colspan='2'></td></tr>";
				echo "<tr height='18'>";
				echo "<td width='18' bgcolor='#FFE1E1'>&nbsp;</td>";
				echo "<td align='left'><font size='1'><b>&nbsp;";
					fecho("excluidas.do.sistema",$con,$cook_idioma);
				echo "</b></font></td>";
				echo "</tr>";
				echo "<tr height='3'><td colspan='2'></td></tr>";
			}

			if($login_fabrica==35){
				echo "<tr height='18'>";
				echo "<td width='18' bgcolor='#FF0000'>&nbsp;</td>";
				echo "<td align='left'><font size='1'><b>&nbsp;";
					fecho("excluidas.do.sistema",$con,$cook_idioma);
				echo "</b></font></td>";
				echo "</tr>";
				echo "<tr height='3'><td colspan='2'></td></tr>";
			}

			if($login_fabrica == 45){//HD 14584 26/2/2008
				echo "<tr height='3'><td colspan='2'></td></tr>";
				echo "<tr height='18'>";
				echo "<td width='18' bgcolor='#CCCCFF'>&nbsp;</td>";
				echo "<td align='left'><font size='1'><b>&nbsp; ";
				fecho("os.com.ressarcimento.financeiro",$con,$cook_idioma);
				echo "</b></font></td>";
				echo "</tr>";
				echo "<tr height='3'><td colspan='2'></td></tr>";
				echo "<tr height='18'>";
				echo "<td width='18' bgcolor='#FFCC66'>&nbsp;</td>";
				echo "<td align='left'><font size='1'><b>&nbsp; ";
				fecho("os.com.troca.de.produto",$con,$cook_idioma);
				echo "</b></font></td>";
				echo "</tr>";
				echo "<tr height='3'><td colspan='2'></td></tr>";
				echo "<tr height='18'>";
				echo "<td width='18' bgcolor='#FFCEFF'>&nbsp;</td>";
				echo "<td align='left'><font size='1'><b>&nbsp; ";
				fecho("os.consertada",$con,$cook_idioma);
				echo "</b></font></td>";
				echo "</tr>";
			}

			if($login_fabrica == 15){
				echo "<tr height='18'>";
				echo "<td width='18' bgcolor='#999933'>&nbsp;</td>";
				echo "<td align='left'><font size='1'><b>&nbsp; ";
				fecho("os.digitada.por.administrador",$con,$cook_idioma);

				echo "</b></font></td>";
				echo "</tr>";
				echo "<tr height='3'><td colspan='2'></td></tr>";
			}

			if($login_fabrica == 3 OR $login_fabrica == 6 OR $login_fabrica == 11 OR $login_fabrica == 25 OR $login_fabrica == 51) {
				echo "<tr height='18'>";
				echo "<td width='18' bgcolor='#FFCCCC'>&nbsp;</td>";
				echo "<td align='left'><font size='1'><b>&nbsp; ";
				fecho("os.com.intervencao.da.fabrica.aguardando.liberacao",$con,$cook_idioma);
				echo "</b></font></td>";
				echo "</tr>";
				echo "<tr height='3'><td colspan='2'></td></tr>";

				echo "<tr height='18'>";
				echo "<td width='18' bgcolor='#FFFF99'>&nbsp;</td>";
				echo "<td align='left'><font size='1'><b>&nbsp; ";
				fecho("os.com.intervencao.da.fabrica.reparo.na.fabrica",$con,$cook_idioma);
				echo "</b></font></td>";
				echo "</tr>";
				echo "<tr height='3'><td colspan='2'></td></tr>";

				echo "<tr height='18'>";
				echo "<td width='18' bgcolor='#CCFFFF'>&nbsp;</td>";
				echo "<td align='left'><font size='1'><b>&nbsp; ";
				fecho("os.liberada.pela.fabrica",$con,$cook_idioma);
				echo "</b></font></td>";
				echo "</tr>";
				echo "<tr height='3'><td colspan='2'></td></tr>";
			}
			if ($login_fabrica==3){
				echo "<tr height='18'>";
				echo "<td width='18' bgcolor='#FF0000'>&nbsp;</td>";
				echo "<td align='left'><font size='1'><b>&nbsp "; fecho("canceladas",$con,$cook_idioma); echo "</b></font></td>";
				echo "</tr>";
				echo "<tr height='3'><td colspan='2'></td></tr>";
			}
		}else{
			echo "<tr height='18'>";
			echo "<td width='18' bgcolor='#FFCC66'>&nbsp;</td>";
			echo "<td align='left'><font size='1'><b>&nbsp;".fecho("oss.sem.lancamento.de.itens.a.mais.de.5.dias,.efetue.o.lancamento",$con,$cook_idioma)."</b></font></td>";
			echo "</tr>";
			echo "<tr height='3'><td colspan='2'></td></tr>";
			echo "<tr height='18'>";
			echo "<td width='18' bgcolor='#FF0000'>&nbsp;</td>";
			echo "<td align='left'><font size='1'><b>&nbsp; ".fecho("oss.que.excederam.o.prazo.limite.de.30.dias.para.fechamento,.informar.motivo\"",$con,$cook_idioma)."</b></font></td>";
			echo "</tr>";
			echo "<tr height='3'><td colspan='2'></td></tr>";
			echo "<tr height='18'>";
			echo "<td width='18' bgcolor='#91C8FF'>&nbsp;</td>";
			echo "<td align='left'><font size='1'><b>&nbsp; ";
			fecho ("os.aberta.a.mais.de.25.dias",$con,$cook_idioma);
			echo "</b></font></td>";
			echo "</tr>";
			echo "<tr height='3'><td colspan='2'></td></tr>";
			echo "<tr height='18'>";
			echo "<td width='18' bgcolor='#D7FFE1'>&nbsp;</td>";
			echo "<td align='left'><font size='1'><b>&nbsp; ";
			fecho("reincidencia",$con,$cook_idioma);
			echo "</b></font></td>";
			echo "</tr>";
			echo "<tr height='3'><td colspan='2'></td></tr>";
		}
		if ($login_fabrica == 11 ) {
			echo "<tr height='18'>";
			echo "<td width='18' bgcolor='#FFE1E1'>&nbsp;</td>";
			echo "<td align='left'><font size='1'><b>&nbsp; "; fecho("excluidas.do.sistema",$con,$cook_idioma); echo" </b></font></td>";
			echo "</tr>";
			echo "<tr height='3'><td colspan='2'></td></tr>";
		}
		if ($login_fabrica == 20 ) {
			echo "<tr height='18'>";
			echo "<td width='18' bgcolor='#CACACA'>&nbsp;</td>";
			echo "<td align='left'><font size='1'><b>&nbsp; "; fecho("os.reprovada.pelo.promotor",$con,$cook_idioma); echo"</b></font></td>";
			echo "</tr>";
			echo "<tr height='3'><td colspan='2'></td></tr>";
		}

		if ($login_fabrica == 3) {
				echo "<tr height='18'>";
				echo "<td width='18' bgcolor='#FFCC66'>&nbsp;</td>";
				echo "<td align='left'><font size='1'><b>&nbsp; ";
				fecho("os.com.troca.de.produto",$con,$cook_idioma);
				echo "</b></font></td>";
				echo "</tr>";
		}

		echo "</table>";
		echo "</div>";
		##### LEGENDAS - FIM #####

		echo "<br>";

		for ($i = 0 ; $i < pg_num_rows($res) ; $i++) {
			if ($i % 50 == 0) {
				echo "</table>";
				flush();
			if ($login_fabrica == 20 and $login_posto == 6359) {
				echo "<table border='1' cellpadding='0' cellspacing='0' style='border-collapse: collapse' bordercolor='#000000' width='98%'>";
				}
				else
				echo "<table border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#000000' width='98%'>";
			}

			if ($i % 50 == 0) {
				echo "<tr class='Titulo' height='25' background='admin/imagens_admin/azul.gif'>";
				echo "<td width='100' nowrap>OS</td>";
				if($login_fabrica==19 OR $login_fabrica==10){
					echo "<td>OS OFF LINE</td>";
				}
				//HD 8431 OS interna para Argentina
				if($login_fabrica == 20 AND $login_pais == 'AR') echo "<td>".traduz("os.interna",$con,$cook_idioma)."</td>";
				echo "<td width='150'>";
				if($login_fabrica ==35){
					echo "PO#";
				}else{
					fecho("serie",$con,$cook_idioma);
				}
				echo "</td>";
				//hd 12737 31/1/2008
				if ($login_fabrica != 11) { // HD 92774
					echo "<td>"; fecho ("nf",$con,$cook_idioma); echo "</td>";
				}
				echo "<td>"; fecho ("ab",$con,$cook_idioma); echo "</td>";
				if ($login_fabrica == 11) { // HD 92774
					echo "<td><acronym title='".traduz("Data do pedido",$con,$cook_idioma)."' style='cursor:help;'>DP</a></td>";
				}
				//HD 14927
				if($login_fabrica ==3 or $login_fabrica ==11 or $login_fabrica ==15 or $login_fabrica ==45 or $login_fabrica ==43 or $login_fabrica ==66 or $login_fabrica == 14){
					echo "<td><acronym title='".traduz("data.de.conserto.do.produto",$con,$cook_idioma)."' style='cursor:help;'>DC</a></td>";
				}
				echo "<td><acronym title='".traduz("data.de.fechamento.registrada.pelo.sistema",$con,$cook_idioma)."' style='cursor:help;'>".traduz("fc",$con,$cook_idioma)."</a></td>";
				echo "<td>".strtoupper(traduz("consumidor",$con,$cook_idioma))."</td>";
				if ($login_fabrica == 11) { // HD 92774
					echo "<td>".strtoupper(traduz("telefone",$con,$cook_idioma))."</td>";
				}
				if($login_fabrica==3){
					echo "<td>".strtoupper(traduz("marca",$con,$cook_idioma))."</td>";
				}
				echo "<td>";
				if ($login_fabrica == 11) { // HD 92774
					echo strtoupper(traduz("referência",$con,$cook_idioma));
				}else{
					echo strtoupper(traduz("produto",$con,$cook_idioma));
				}
				echo "</td>";
				if($login_fabrica == 56){
					echo "<td>".strtoupper(traduz("atendimento",$con,$cook_idioma))."</td>";
				}
				if($login_fabrica==19){
					echo "<td>".strtoupper(traduz("atendimento",$con,$cook_idioma))."</td>";
					echo "<td nowrap>".strtoupper(traduz("tecnico",$con,$cook_idioma))."</td>";
					}
				#SISTEMA RG
				if($login_posto==6359 OR $login_posto==4311 or ($login_posto== 4262 and $login_fabrica==6)){
					echo "<td>".traduz("rg.produto",$con,$cook_idioma)."</td>";
				}
				echo "<td><img border='0' src='imagens/img_impressora.gif' alt='Imprimir OS'></td>";
				if($login_fabrica == 1 ){
					echo "<td><img border='0' width='20' heigth='20' src='imagens/envelope.png' alt='Carta Registrada'></td>";
				}
				if ($login_fabrica == 1) {
					echo "<td>".traduz("item",$con,$cook_idioma)."</td>";
					$colspan = "8";
				}elseif($login_fabrica ==11 or $login_fabrica ==45 or $login_fabrica ==3){
					$colspan = "6";
				}else{
					$colspan = "5";
				}

				echo "<td colspan='$colspan'>";
				echo strtoupper(traduz("acoes",$con,$cook_idioma));
				echo "</td>";
			}

			if(strlen($btn_acao_pre_os)>0){
				$hd_chamado         = trim(pg_fetch_result($res,$i,hd_chamado));
				$sua_os             = trim(pg_fetch_result($res,$i,sua_os));
				$serie              = trim(pg_fetch_result($res,$i,serie));
				$nota_fiscal        = trim(pg_fetch_result($res,$i,nota_fiscal));
				$abertura           = trim(pg_fetch_result($res,$i,data));
				$consumidor_nome    = trim(pg_fetch_result($res,$i,nome));
				$marca_nome         = trim(pg_fetch_result($res,$i,marca_nome));
				$produto_referencia = trim(pg_fetch_result($res,$i,referencia));
				$produto_descricao  = trim(pg_fetch_result($res,$i,descricao));
			}else{
				$os                 = trim(pg_fetch_result($res,$i,os));
				$sua_os             = trim(pg_fetch_result($res,$i,sua_os));
				$digitacao          = trim(pg_fetch_result($res,$i,digitacao));
				$abertura           = trim(pg_fetch_result($res,$i,abertura));
				$fechamento         = trim(pg_fetch_result($res,$i,fechamento));
				$finalizada         = trim(pg_fetch_result($res,$i,finalizada));
				$serie              = trim(pg_fetch_result($res,$i,serie));
				$excluida           = trim(pg_fetch_result($res,$i,excluida));
				$motivo_atraso      = trim(pg_fetch_result($res,$i,motivo_atraso));
				$tipo_os_cortesia   = trim(pg_fetch_result($res,$i,tipo_os_cortesia));
				$consumidor_revenda = trim(pg_fetch_result($res,$i,consumidor_revenda));
				$consumidor_nome    = trim(pg_fetch_result($res,$i,consumidor_nome));
				$revenda_nome       = trim(pg_fetch_result($res,$i,revenda_nome));
				$codigo_posto       = trim(pg_fetch_result($res,$i,codigo_posto));
				$posto_nome         = trim(pg_fetch_result($res,$i,posto_nome));
				$impressa           = trim(pg_fetch_result($res,$i,impressa));
				$extrato            = trim(pg_fetch_result($res,$i,extrato));
				$os_reincidente     = trim(pg_fetch_result($res,$i,os_reincidente));
				$valores_adicionais = trim(pg_fetch_result($res,$i,valores_adicionais));	//
				$nota_fiscal        = trim(pg_fetch_result($res,$i,nota_fiscal));//hd 12737 31/1/2008
				$nota_fiscal_saida  = trim(pg_fetch_result($res,$i,nota_fiscal_saida));	//
				$reincidencia       = trim(pg_fetch_result($res,$i,reincidencia));
				$produto_referencia = trim(pg_fetch_result($res,$i,produto_referencia));
				$produto_descricao  = trim(pg_fetch_result($res,$i,produto_descricao));
				$produto_voltagem   = trim(pg_fetch_result($res,$i,produto_voltagem));
				$tipo_atendimento   = trim(pg_fetch_result($res,$i,tipo_atendimento));	//
				$tecnico_nome       = trim(pg_fetch_result($res,$i,tecnico_nome));
				$nome_atendimento   = trim(pg_fetch_result($res,$i,descricao));
				$admin              = trim(pg_fetch_result($res,$i,admin));
				$sua_os_offline     = trim(pg_fetch_result($res,$i,sua_os_offline));
				$status_os          = trim(pg_fetch_result($res,$i,status_os));
				$rg_produto         = trim(pg_fetch_result($res,$i,rg_produto));
				$linha              = trim(pg_fetch_result($res,$i,linha));
				if($login_fabrica==3){
					$marca     = trim(pg_fetch_result($res,$i,marca));
					$marca_nome= trim(pg_fetch_result($res,$i,marca_nome));
				}
				//HD 13239 14927
				if($login_fabrica == 11 or $login_fabrica == 45 or $login_fabrica == 15 or $login_fabrica == 3 or $login_fabrica ==43 or $login_fabrica ==66 or $login_fabrica == 14){
					$data_conserto=trim(pg_fetch_result($res,$i,data_conserto));
				}
				$consumidor_email   = trim(pg_fetch_result($res,$i,consumidor_email));
			}
			if ($i % 2 == 0) {
				$cor   = "#F1F4FA";
				$botao = "azul";
			}else{
				$cor   = "#F7F5F0";
				$botao = "amarelo";
			}

			##### VERIFICAÇÕES PARA OS CRITÉRIOS DA LEGENDA - INÍCIO #####

			if($login_fabrica == 15 && strlen($fechamento) != 0 && $reincidencia != "t"){

				if (strlen($admin)>0)    $cor = "#999933";

			}
			if ($reincidencia =='t' and $status_os<>86 and $login_fabrica<>6)     $cor = "#D7FFE1";
			if ($excluida == "t" and $login_fabrica<>6) $cor = "#FF0000";

			if ($login_fabrica==20 AND $excluida == "t"){
				$cor = "#CACACA";
			}
			//hd 3646 28/08/07 tectoy nao aparece que é reincidente para posto
			// OSs abertas há mais de 25 dias sem data de fechamento
			if (strlen($fechamento) == 0 && $excluida != "t" && $login_fabrica != 14) {
				$aux_abertura = fnc_formata_data_pg($abertura);

				$sqlX = "SELECT TO_CHAR($aux_abertura::date + INTERVAL '25 days','YYYY-MM-DD')";
				$resX = pg_query ($con,$sqlX);
				$aux_consulta = pg_fetch_result($resX,0,0);

				$sqlX = "SELECT TO_CHAR(current_date,'YYYY-MM-DD')";
				$resX = pg_query ($con,$sqlX);
				$aux_atual = pg_fetch_result ($resX,0,0);

				if ($aux_consulta < $aux_atual && strlen($fechamento) == 0) $cor = "#91C8FF";
			}


			/*IGOR - HD: 44202 - 22/10/2008 */
			if($login_fabrica==3 AND strlen($os) > 0){
				$sqlI = "SELECT  status_os
						FROM    tbl_os_status
						WHERE   os = $os
						AND status_os IN (120, 122, 123, 126, 140, 141, 142, 143)
						ORDER BY data DESC LIMIT 1";
				$resI = pg_query ($con,$sqlI);
				if (pg_num_rows ($resI) > 0){
					$status_os = trim(pg_fetch_result($resI,0,status_os));
					if($status_os == 126 || $status_os == 143) {
						$cor="#FF0000";
						$excluida = "t";
					}
				}
			}


			if ($status_os=="62")  $cor="#FFCCCC";
			if ($status_os=="72")  $cor="#FFCCCC";
			if ($status_os=="87")  $cor="#FFCCCC";
			if ($status_os=="116") $cor="#FFCCCC";

			if ($status_os=="120" || $status_os=="140")  $cor="#FFCCCC"; //HD: 44202 e 207142
			if ($status_os=="122" || $status_os=="141")  $cor="#FFCCCC"; //HD: 44202 e 207142

			if ($status_os=="64"  && strlen($fechamento)==0) $cor="#CCFFFF";
			if ($status_os=="73"  && strlen($fechamento)==0) $cor="#CCFFFF";
			if ($status_os=="117" && strlen($fechamento)==0) $cor="#CCFFFF";

			if ($status_os=="65") $cor="#FFFF99";

			if($login_fabrica==50){
				$sqlI = "SELECT  status_os
						FROM    tbl_os_status
						WHERE   os = $os
						AND status_os IN (101, 104)
						ORDER BY data DESC LIMIT 1";
				$resI = pg_query ($con,$sqlI);
				if (pg_num_rows ($resI) > 0){
					$status_os = trim(pg_fetch_result($resI,0,status_os));
					if($status_os==103 or $status_os==104){
						$cor="#FF9933";
					}
				}

				if($excluida=='t'){
					$cor="#FFE1E1";
				}
			}


			if($login_fabrica == 1){
				if(strlen($tipo_atendimento) > 0) $cor = "#FFCC66";
			}

			// CONDIÇÕES PARA NKS - INÍCIO
			if($login_fabrica == 45){//HD 14584 26/2/2008
				if(strlen($data_conserto)>0){
					$cor = "#FFCEFF";
				}

				$sqlX = "SELECT os_troca,ressarcimento FROM tbl_os_troca WHERE os = $os";
				$resX = pg_query($con,$sqlX);
				if(pg_num_rows($resX)==1){
					$cor = "#FFCC66";
					if(pg_fetch_result($resX,0,ressarcimento)=='t'){
						$cor = "#CCCCFF";
					}
				}
			}
			// CONDIÇÕES PARA NKS - FIM

			// CONDIÇÕES PARA INTELBRÁS - INÍCIO
			if (strlen($fechamento) == 0 && $excluida != "t" && $login_fabrica == 14) {
				$aux_abertura = fnc_formata_data_pg($abertura);

				$sqlX = "SELECT TO_CHAR($aux_abertura::date + INTERVAL '3 days','YYYY-MM-DD')";
				$resX = pg_query($con,$sqlX);
				$aux_consulta = pg_fetch_result($resX,0,0);

				$sqlX = "SELECT TO_CHAR(current_date,'YYYY-MM-DD')";
				$resX = pg_query($con,$sqlX);
				$aux_atual = pg_fetch_result($resX,0,0);

				if ($aux_consulta < $aux_atual && strlen($fechamento) == 0) $cor = "#91C8FF";

				$sqlX = "SELECT TO_CHAR($aux_abertura::date + INTERVAL '5 days','YYYY-MM-DD')";
				$resX = pg_query($con,$sqlX);
				$aux_consulta = pg_fetch_result($resX,0,0);

				$sqlX = "SELECT TO_CHAR(current_date,'YYYY-MM-DD')";
				$resX = pg_query ($con,$sqlX);
				$aux_atual = pg_fetch_result ($resX,0,0);

				if ($aux_consulta < $aux_atual && strlen($fechamento) == 0) $cor = "#FF0000";
			}
			// CONDIÇÕES PARA INTELBRÁS - FIM

			// CONDIÇÕES PARA BLACK & DECKER - INÍCIO
			// Verifica se não possui itens com 5 dias de lançamento
			if ($login_fabrica == 1) {
				$aux_abertura = fnc_formata_data_pg($abertura);

				$sqlX = "SELECT TO_CHAR(current_date + INTERVAL '5 days','YYYY-MM-DD')";
				$resX = pg_query($con,$sqlX);
				$data_hj_mais_5 = pg_fetch_result($resX,0,0);

				$sqlX = "SELECT TO_CHAR($aux_abertura::date + INTERVAL '5 days','YYYY-MM-DD')";
				$resX = pg_query ($con,$sqlX);
				$aux_consulta = pg_fetch_result($resX,0,0);

				$sql = "SELECT COUNT(tbl_os_item.*) AS total_item
						FROM tbl_os_item
						JOIN tbl_os_produto ON tbl_os_produto.os_produto = tbl_os_item.os_produto
						JOIN tbl_os         ON tbl_os.os                 = tbl_os_produto.os
						WHERE tbl_os.os = $os
						AND   tbl_os.data_abertura::date >= '$aux_consulta'";
				$resItem = pg_query($con,$sql);

				$itens = pg_fetch_result($resItem,0,total_item);

				if ($itens == 0 && $aux_consulta > $data_hj_mais_5) $cor = "#FFCC66";

				$mostra_motivo = 2;
			}

			// Verifica se está sem fechamento há 20 dias ou mais da data de abertura
			if (strlen($fechamento) == 0 && $mostra_motivo == 2 && $login_fabrica == 1) {
				$aux_abertura = fnc_formata_data_pg($abertura);

				$sqlX = "SELECT TO_CHAR($aux_abertura::date + INTERVAL '20 days','YYYY-MM-DD')";
				$resX = pg_query($con,$sqlX);
				$aux_consulta = pg_fetch_result($resX,0,0);

				$sqlX = "SELECT TO_CHAR(current_date,'YYYY-MM-DD')";
				$resX = pg_query($con,$sqlX);
				$aux_atual = pg_fetch_result($resX,0,0);

				if ($consumidor_revenda != "R") {
					if ($aux_consulta < $aux_atual && strlen($fechamento) == 0) {
						$mostra_motivo = 1;
						$cor = "#91C8FF";
					}
				}
			}

			// Se estiver acima dos 30 dias, não exibirá os botões
			if (strlen($fechamento) == 0 && $login_fabrica == 1) {
				$aux_abertura = fnc_formata_data_pg($abertura);

				$sqlX = "SELECT TO_CHAR($aux_abertura::date + INTERVAL '30 days','YYYY-MM-DD')";
				$resX = pg_query($con,$sqlX);
				$aux_consulta = pg_fetch_result($resX,0,0);

				$sqlX = "SELECT TO_CHAR(current_date,'YYYY-MM-DD')";
				$resX = pg_query($con,$sqlX);
				$aux_atual = pg_fetch_result($resX,0,0);

				if ($consumidor_revenda != "R"){
					if ($aux_consulta < $aux_atual && strlen($fechamento) == 0) {
						$mostra_motivo = 1;
						$cor = "#FF0000";
					}
				}
			}
			// CONDIÇÕES PARA BLACK & DECKER - FIM

			//STATUS DE TROCA HD 72717
			if($login_fabrica==3 AND strlen($os)>0){
				$sqlT = "SELECT os_troca FROM tbl_os_troca WHERE os = $os";
				$resT = pg_query($con,$sqlT);
				if(pg_num_rows($resT)==1){
					$cor = "#FFCC66";
				}
			}

			##### VERIFICAÇÕES PARA OS CRITÉRIOS DA LEGENDA - FIM #####

			if (strlen($sua_os) == 0) $sua_os = $os;
			if ($login_fabrica == 1) $xsua_os =  $codigo_posto.$sua_os ;


			echo "<tr class='Conteudo' height='15' bgcolor='$cor' align='left'>";
			echo "<td  width='60' nowrap>" ;
			if ($login_fabrica == 1){ echo $xsua_os; }else{ echo $sua_os;}
			echo "</td>";
			//HD 8431 OS interna para Argentina
			if($login_fabrica==19 OR $login_fabrica==10 OR ($login_fabrica == 20 AND $login_pais=='AR')){
				echo "<td nowrap>" . $sua_os_offline . "</td>";
			}
			echo "<td width='55' nowrap>" . $serie . "</td>";
			//hd 12737 31/1/2008
			if ($login_fabrica != 11) { // HD 92774
				echo "<td nowrap>" ;
				echo $nota_fiscal;
				echo "</td>";
			}
			echo "<td nowrap ><acronym title='".traduz("data.abertura",$con,$cook_idioma).": $abertura' style='cursor: help;'>" . substr($abertura,0,5) . "</acronym></td>";
			if ($login_fabrica == 11) { // HD 92774
				$sql_p = " SELECT to_char(tbl_pedido.data,'DD/MM/YYYY') as data_pedido
							FROM tbl_os_produto
							JOIN tbl_os_item USING(os_produto)
							JOIN tbl_pedido  USING(pedido)
							WHERE tbl_os_produto.os = $os
							AND   tbl_pedido.fabrica = $login_fabrica
							ORDER BY tbl_pedido.pedido ASC LIMIT 1 ";
				$res_p = @pg_query($con,$sql_p);
				echo "<td nowrap >";
				if (pg_num_rows($res_p) > 0) {
					$data_pedido = pg_fetch_result($res_p,0,data_pedido);
					echo "<acronym title='Data Pedido: $data_pedido' style='cursor: help;'>" . substr($data_pedido,0,5) . "</acronym>";
				}
				echo "</td>";
			}
			//HD 14927
			if($login_fabrica ==3 or $login_fabrica ==11 or $login_fabrica ==15 or $login_fabrica ==45 or $login_fabrica ==43 or $login_fabrica ==66 or $login_fabrica == 14){
				echo "<td nowrap ><acronym title='".traduz("data.do.conserto",$con,$cook_idioma).": $data_conserto' style='cursor: help;'>" . substr($data_conserto,0,5) . "</acronym></td>";
			}

			if ($login_fabrica == 1) $aux_fechamento = $finalizada;
			else                     $aux_fechamento = $fechamento;
			echo "<td nowrap><acronym title='".traduz("data.fechamento",$con,$cook_idioma).": ";
			echo "$aux_fechamento' style='cursor: help;'>" . substr($aux_fechamento,0,5) . "</acronym></td>";
			echo "<td width='120' nowrap><acronym title='".traduz("consumidor",$con,$cook_idioma).": $consumidor_nome' style='cursor: help;'>" . substr($consumidor_nome,0,15) . "</acronym></td>";
			if ($login_fabrica == 11) { // HD 92774
				echo "<td nowrap><acronym title='Telefone: $consumidor_fone' style='cursor: help;'>" .$consumidor_fone. "</acronym></td>";
			}
			if($login_fabrica==3){//TAKASHI HD925
				echo "<td nowrap>$marca_nome</td>";
			}
			if ($login_fabrica == 11) { // HD 92774
				$produto = $produto_referencia;
			}else{
				$produto = $produto_referencia . " - " . $produto_descricao;
			}
			echo "<td width='150' nowrap><acronym title='";

			fecho ("referencia",$con,$cook_idioma);
			echo " : $produto_referencia ";
			fecho ("descricao",$con,$cook_idioma);
			echo " : $produto_descricao ";
			fecho ("voltagem",$con,$cook_idioma);
			echo ": $produto_voltagem' style='cursor: help;'>" . substr($produto,0,20) . "</acronym></td>";

			if($login_fabrica == 56){
				echo"<td nowrap>$nome_atendimento</td>";
			}
			if($login_fabrica==19){
				echo"<td nowrap>$tipo_atendimento - $nome_atendimento </td>";
				echo"<td width='90' nowrap><acronym title='".traduz("nome.do.tecnico",$con,$cook_idioma).": $tecnico_nome' style='cursor: help;'>" . substr($tecnico_nome,0,11) . "</acronym></td>";
				}
			if($login_posto==6359 OR $login_posto ==4311 or ($login_posto== 4262 and $login_fabrica==6)){
				echo "<td>$rg_produto</td>";
			}

			##### VERIFICAÇÃO SE A OS FOI IMPRESSA #####
			echo "<td width='30' align='center'>";
			if (strlen($admin) > 0 and $login_fabrica == 19) echo "<img border='0' src='imagens/img_sac_lorenzetti.gif' alt='OS lançada pelo SAC Lorenzetti'>";
			else if (strlen($impressa) > 0)                  echo "<img border='0' src='imagens/img_ok.gif' alt='OS já foi impressa'>";
			else                                             echo "<img border='0' src='imagens/img_impressora.gif' alt='Imprimir OS'>";
			echo "</td>";

			##### VERIFICAÇÃO SE A OS FOI ENVIADA CARTA REGISTRADA #####
			if($login_fabrica == 1 and $consumidor_revenda == 'C' ){
				echo "<td width='30' align='center'>";
				if(strlen($fechamento) == 0){
					$sql_sedex = "SELECT SUM(current_date - data_abertura)as final FROM tbl_os WHERE os=$os ;";
					$res_sedex = pg_query($con,$sql_sedex);
					$sedex_dias = pg_fetch_result($res_sedex,0,'final');
					if($sedex_dias > 15){
						$sql_sedex = "SELECT sua_os_origem FROM tbl_os_sedex WHERE sua_os_origem = $os AND fabrica = $login_fabrica";
						$res_sedex = pg_query($con,$sql_sedex);
						if(pg_num_rows($res_sedex) == 0){
							echo "<a href='carta_registrada.php?os=$os'><img border='0' width='20' heigth='20' src='imagens/envelope.png' alt='Inserir informações da Carta Registrada'></a>";
						}else{
							echo "<a href='carta_registrada.php?os=$os'><img border='0' width='20' heigth='20' src='imagens/img_ok.gif' alt='Visualizar as informações da Carta Registrada'></a>";
						}
					}
					echo "&nbsp;";
				}else{
					echo "&nbsp;";
				}
				echo "</td>";
			}

			if(strlen($btn_acao_pre_os)>0){

				echo "<td align='center' nowrap>";
				if ($login_fabrica <> 14 and $login_fabrica <> 66 and $login_fabrica <> 43) {
				echo "<a href='os_cadastro_tudo.php?pre_os=t&serie=$serie&hd_chamado=$hd_chamado'>".traduz("abrir.pre-os",$con,$cook_idioma)."</a>";
				}
				else {
				echo "<a href='os_cadastro_intelbras_ajax_test.php?pre_os=t&serie=$serie&hd_chamado=$hd_chamado'>".traduz("abrir.pre-os",$con,$cook_idioma)."</a>";
				}
				echo "</td>";
	}else{
			##### VERIFICAÇÃO SE TEM ITEM NA OS PARA A FÁBRICA 1 #####
			if ($login_fabrica == 1) {
				echo "<td width='30' align='center'>";
				if ($qtde_item > 0) echo "<img border='0' src='imagens/img_ok.gif' alt='OS com item'>";
				else                echo "&nbsp;";
				echo "</td>";
			}
			echo "<td width='60' align='center'>";
			if($sistema_lingua == "ES"){
				if ($excluida == "f" || strlen($excluida) == 0) echo "<a href='os_press.php?os=$os' target='_blank'><img border='0' src='imagens/btn_busca.gif'></a>";
			}else{
				 echo "<a href='os_press.php?os=$os' target='_blank'><img border='0' src='imagens/btn_consulta.gif'></a>";
			}
			echo "</td>\n";

			echo "<td width='60' align='center'>";

			if ($excluida == "f" || strlen($excluida) == 0 and strlen($btn_acao_pre_os)==0) {
				if ($login_fabrica == 1 && $tipo_os_cortesia == "Compressor") {
					if($login_posto=="6359"){
							echo "<a href='os_print.php?os=$os' target='_blank'>";
					}else{
						echo "<a href='os_print_blackedecker_compressor.php?os=$os' target='_blank'>";
					//takashi alterou 03/11
					}
				}else{
					$sql = "SELECT os
							FROM tbl_os_troca_motivo
							WHERE os = $os ";
					$resxxx = pg_query($con,$sql);
					if($login_fabrica==20 AND pg_num_rows($resxxx)>0) {
						echo "<a href='os_finalizada.php?os=$os' target='_blank'>";
					}else{
						echo "<a href='os_print.php?os=$os' target='_blank'>";
					}
				}
				echo "<img border='0' src='imagens/btn_imprime.gif'></a>";
			}
			echo "</td>\n";

			if ($login_fabrica == 1) {
				echo "<td width='60' align='center'>";
				if (($excluida == "f" || strlen($excluida) == 0) && strlen($fechamento) == 0) {
					if($tipo_atendimento <> 17 AND $tipo_atendimento <> 18 )
						echo "<a href='os_cadastro.php?os=$os'><img border='0' src='imagens/btn_alterar_cinza.gif'></a>";
					else
						if(strlen($valores_adicionais) == 0 AND strlen($nota_fiscal_saida) == 0)
							echo "<a href='os_cadastro_troca.php?os=$os'><img border='0' src='imagens/btn_alterar_cinza.gif'></a>";
				}else{
					echo "&nbsp;";
				}
				echo "</td>\n";
			}

			$sql_critico = "select produto_critico from tbl_produto where referencia = '$produto_referencia'";
			$res_critico = pg_query($con,$sql_critico);

			if (pg_num_rows($res_critico)>0) {
				$produto_critico = pg_fetch_result($res_critico,0,produto_critico);
			}

			echo "<td width='60' align='center' nowrap>";
			if ($troca_garantia == "t" OR (($status_os=="62" and $produto_critico <> 't') || $status_os=="65" || $status_os=="72" || $status_os=="87" || $status_os=="116" || $status_os=="120" || $status_os=="122" || $status_os=="126" || $status_os=="140" || $status_os=="141" || $status_os=="143")) {
			}elseif (($login_fabrica == 3 || $login_fabrica == 6) && strlen ($fechamento) == 0) {
				if ($excluida == "f" || strlen($excluida) == 0) {
					echo "<a href='os_item.php?os=$os' target='_blank'><img id='lancar_$i' border='0' src='imagens/btn_lanca.gif'></a>";
				}
			}elseif ($login_fabrica == 1 && strlen ($fechamento) == 0 ) {
				if ($excluida == "f" || strlen($excluida) == 0) {
					if ($login_fabrica == 1 AND $tipo_os_cortesia == "Compressor") {
						if($login_posto=="6359"){
							echo "<a href='os_item.php?os=$os' target='_blank'>";
						}else{
							echo "<a href='os_print_blackedecker_compressor.php?os=$os' target='_blank'>";
						//takashi alterou 03/11
						}
					}else{
						echo "<a href='os_item.php?os=$os' target='_blank'>";
					}//
					if($login_fabrica == 1 AND $tipo_atendimento <> 17 AND $tipo_atendimento <> 18)
						echo "<img id='lancar_$i' border='0' src='imagens/btn_lanca.gif'></a>";
					else
						echo "<p id='lancar_$i' border='0'></p></a>";
				}
			}elseif ($login_fabrica == 7 && strlen ($fechamento) == 0 ) {
				echo "<a href='os_filizola_valores.php?os=$os' target='_blank'><img id='lancar_$i' border='0' src='imagens/btn_lanca.gif'></a>";
			}elseif (strlen($fechamento) == 0 ) {
				if ($excluida == "f" OR strlen($excluida) == 0) {
					if ($login_fabrica == 1) {
						if($tipo_os_cortesia == "Compressor"){
							if($login_posto=="6359"){
								echo "<a href='os_item.php?os=$os' target='_blank'>";
							}else{
								echo "<a href='os_print_blackedecker_compressor.php?os=$os' target='_blank'>";
							//takashi alterou 03/11
							}
						}
						if(strlen($tipo_atendimento) == 0){
							echo "<a href='os_item.php?os=$os' target='_blank'>";
						}
					}else{
						//
						if($login_fabrica==19){
							if($consumidor_revenda<>'R'){
								echo "<a href='os_item.php?os=$os' target='_blank'>";
								if($sistema_lingua == "ES"){
									echo "<img id='lancar_$i' border='0' src='imagens/btn_lanzar.gif'></a>";
								}else{
									echo "<img id='lancar_$i' border='0' src='imagens/btn_lanca.gif'></a>";
								}
							}
						}else{
							echo "<a href='os_item.php?os=$os' target='_blank'>";
							if($sistema_lingua == "ES"){
								echo "<img id='lancar_$i' border='0' src='imagens/btn_lanzar.gif'>";
							}else{
								// $data_conserto > "03/11/2008" HD 50435
								$xdata_conserto = fnc_formata_data_pg($data_conserto);

								$sqlDC = "SELECT $xdata_conserto::date > '2008-11-03'::date AS data_anterior";
								#echo $sqlDC;
								$resDC = pg_query($con, $sqlDC);
								if(pg_num_rows($resDC)>0) $data_anterior = pg_fetch_result($resDC, 0, 0);

								if($login_fabrica==11 AND strlen($data_conserto)>0 AND $data_anterior == 't'){
									echo "";
								}else{
									echo "<img id='lancar_$i' border='0' src='imagens/btn_lanca.gif'>";
								}
							}
							echo "</a>";
						}
						//
					}
				}
			}elseif (strlen($fechamento) > 0 && strlen($extrato) == 0 AND strlen($rg_produto)==0) {
				if ($excluida == "f" || strlen($excluida) == 0) {
					if (strlen ($importacao_fabrica) == 0) {
						if($login_fabrica == 20){
							/*if($status_os<>'13' AND ($tipo_atendimento<>13 and $tipo_atendimento <> 66))
								echo "<a href='os_cadastro.php?os=$os&reabrir=ok'><img border='0' src='imagens/btn_reabriros.gif'></a>";*/
							// HD 61323
						}
						else if($login_fabrica == 1 AND ($tipo_atendimento == 17 OR $tipo_atendimento == 18)) echo "&nbsp;";
							else{
								//HD 15368 - Raphael, se a os for troca não pode irá reabrir
								$sqlX = "SELECT os_troca,ressarcimento FROM tbl_os_troca WHERE os = $os";
								$resX = @pg_query($con,$sqlX);
								if(@pg_num_rows($resX)==0) {
									if($login_fabrica <>11){ // HD 45935
										echo "<a href='os_item.php?os=$os&reabrir=ok'><img border='0' src='imagens/btn_reabriros.gif'></a>";
									}else{
										echo "&nbsp;";
									}
								}
							}
					}
				}
			}else{
				echo "&nbsp;";
			}
			echo "</td>\n";

			if ($login_fabrica == 1) {
				echo "<td width='60' align='center'>";
				if (strlen($admin) == 0 AND strlen ($fechamento) == 0 AND ($excluida == "f" OR strlen($excluida) == 0) AND $mostra_motivo == 1) {
					echo "<a href='os_motivo_atraso.php?os=$os' target='_blank'><img border='0' src='imagens/btn_motivo.gif'></a>";
				}else{
					echo "&nbsp;";
				}
				echo "</td>\n";
			}

			echo "<td width='60' align='center'>";
			if (strlen($fechamento) == 0 && strlen($pedido) == 0) {
				if (($status_os!="62" && $status_os!="65" && $status_os!="72" && $status_os!="87" && $status_os!="116" && $status_os!="120" && $status_os!="122" && $status_os!="126" && $status_os!="140" && $status_os!="141" && $status_os!="143") ||($reincidencia=='t')){
					if ($excluida == "f" || strlen($excluida) == 0) {
						if (strlen ($admin) == 0) {
							if($login_fabrica == 1 AND ($tipo_atendimento == 17 OR $tipo_atendimento == 18) AND $valores_adicionais > 0)
								echo "<a href=\"javascript: if (confirm('".traduz("deseja.realmente.excluir.a.os",$con,$cook_idioma)." $sua_os ?') == true) { window.location='$PHP_SELF?excluir=$os'; }\"><p id='excluir_$i' border='0'></p></a>";
							else
								echo "<a href=\"javascript: if (confirm('".traduz("deseja.realmente.excluir.a.os",$con,$cook_idioma)." $sua_os ?') == true) { if(disp_prompt($os, '$sua_os') == true){window.location='$PHP_SELF?excluir=$os';} }\"><img id='excluir_$i' border='0' src='imagens/btn_excluir.gif'></a>";

						}else{
							if($login_fabrica == 20) { # 148322
								echo "<a href=\"javascript: if (confirm('".traduz("deseja.realmente.excluir.a.os",$con,$cook_idioma)." $sua_os ?') == true) { if(disp_prompt($os, '$sua_os') == true){window.location='$PHP_SELF?excluir=$os';} }\"><img id='excluir_$i' border='0' src='imagens/btn_excluir.gif'></a>";
							}else{
								echo "<img id='excluir_$i' border='0' src='imagens/pixel.gif'>";
							}
						}
					}
				}
			}else{
				echo "&nbsp;";
			}
			echo "</td>\n";
			echo "<td width='60' align='center'>";
			if (strlen($fechamento) == 0 AND $status_os!="62"  && $status_os!="65" && $status_os!="72" && $status_os!="87" && $status_os!="116" && $status_os!="120" && $status_os!="122" && $status_os!="126" && $status_os!="140" && $status_os!="141" && $status_os!="143") {
				if ($excluida == "f" || strlen($excluida) == 0) {
					if($login_fabrica == 1 AND ($tipo_atendimento == 17 OR $tipo_atendimento == 18)){
						if($nota_fiscal_saida > 0 OR ($valores_adicionais == 0 AND $nota_fiscal_saida == 0))
							echo "<a href=\"javascript: if (confirm('".traduz("caso.a.data.da.entrega.do.produto.para.o.consumidor.nao.seja.hoje,.utilize.a.opcao.de.fechamento.de.os.para.informar.a.data.correta!.confirma.o.fechamento.da.os.%.com.a.data.de.hoje",$con,$cook_idioma,array($sua_os))."?') == true) { fechaOS ($os,sinal_$i,excluir_$i, lancar_$i) ; }\"><img id='sinal_$i' border='0' src='/assist/imagens/btn_fecha.gif'></a>";
					}else{
						if($login_fabrica==19){
							if($consumidor_revenda<>'R'){
								echo "<a href=\"javascript: if (confirm('".traduz("caso.a.data.da.entrega.do.produto.para.o.consumidor.nao.seja.hoje,.utilize.a.opcao.de.fechamento.de.os.para.informar.a.data.correta!.confirma.o.fechamento.da.os.%.com.a.data.de.hoje",$con,$cook_idioma,array($sua_os))."?') == true) { fechaOS ($os,sinal_$i,excluir_$i, lancar_$i) ; }\"><img id='sinal_$i' border='0' src='/assist/imagens/btn_fecha.gif'></a>";
							}
						}else{
							if($login_fabrica<>15){
								if($login_fabrica==11 and strlen($consumidor_email)>0 and $login_posto==14301){
									echo "<a href=\"javascript: if(confirm('".traduz("esta.os.devera.ser.fechada.na.tela.fechamento.de.os",$con,$cook_idioma)."') == true) {window.location='os_fechamento.php?sua_os=$sua_os&btn_acao_pesquisa=continuar';}\"><img id='sinal_$i' border='0' src='/assist/imagens/btn_fecha.gif'></a>";
								}else{
									if ($login_fabrica == 20 and $login_posto == 6359) {
										echo "<a href='#' onclick='fechaOSnovo($i);data_fechamento_$i.focus();'><img id='sinal_$i' border='0' src='/assist/imagens/btn_fecha.gif'></a>";
									}
									else {
										//echo $consumidor_revenda;
										if($consumidor_revenda=='R' and $login_fabrica == 11){
											#HD 111421 ----->
											$sua_os_x = $sua_os;
											$ache = "-";
											$posicao = strpos($sua_os_x,$ache);
											$sua_os_x = substr($sua_os_x,0,$posicao);
											#--------------->
											echo "<a href=\"javascript: if(confirm('".traduz("os.revenda.devera.ser.fechada.na.tela.fechamento.de.os",$con,$cook_idioma)."') == true) {window.location='os_fechamento.php?sua_os=$sua_os_x&btn_acao_pesquisa=continuar';}\"><img id='sinal_$i' border='0' src='/assist/imagens/btn_fecha.gif'></a>";
										}else{
												echo "<a href=\"javascript: if (confirm('".traduz("caso.a.data.da.entrega.do.produto.para.o.consumidor.nao.seja.hoje,.utilize.a.opcao.de.fechamento.de.os.para.informar.a.data.correta!.confirma.o.fechamento.da.os.%.com.a.data.de.hoje",$con,$cook_idioma,array($sua_os))."?') == true) { fechaOS ($os,sinal_$i,excluir_$i, document.getElementById('lancar')) ; }\"><img id='sinal_$i' border='0' src='/assist/imagens/btn_fecha.gif'></a>";
										}
									}
								}
								//echo $consumidor_revenda;
							}else{
								if($consumidor_revenda<>'R'){

									echo "<a href=\"javascript: if (confirm('".traduz("caso.a.data.da.entrega.do.produto.para.o.consumidor.nao.seja.hoje,.utilize.a.opcao.de.fechamento.de.os.para.informar.a.data.correta!.confirma.o.fechamento.da.os.%.com.a.data.de.hoje",$con,$cook_idioma,array($sua_os))."?') == true) { fechaOS ($os,sinal_$i,excluir_$i, lancar_$i) ; }\"><img id='sinal_$i' border='0' src='/assist/imagens/btn_fecha.gif'></a>";
								}else{
									echo "<a href=\"javascript: if(confirm('".traduz("os.revenda.devera.ser.fechada.na.tela.fechamento.de.os",$con,$cook_idioma)."') == true) {window.location='os_fechamento.php?sua_os=$sua_os&btn_acao_pesquisa=continuar';}\"><img id='sinal_$i' border='0' src='/assist/imagens/btn_fecha.gif'></a>";
								}
							}
						}
					}
				}
			}else{
				if ($login_fabrica == 51 AND $status_os =='62') {
					echo "<a href=\"javascript: if (confirm('".traduz("caso.a.data.da.entrega.do.produto.para.o.consumidor.nao.seja.hoje,.utilize.a.opcao.de.fechamento.de.os.para.informar.a.data.correta!.confirma.o.fechamento.da.os.%.com.a.data.de.hoje",$con,$cook_idioma,array($sua_os))."?') == true) { fechaOS ($os,sinal_$i,'', '') ; }\"><img id='sinal_$i' border='0' src='/assist/imagens/btn_fecha.gif'></a>";
				}else{
					echo "&nbsp;";
				}
			}
			echo "</td>\n";
			if ($login_fabrica == 7 AND 1==2) {
				echo "<td width='60' align='center'>";
				echo "<a href='os_matricial.php?os=$os' target='_blank'>".traduz("matricial",$con,$cook_idioma)."</a>";
				echo "</td>\n";
			}

			if ($login_fabrica == 11 or $login_fabrica == 45 or $login_fabrica == 3){ //HD 13239
				echo "<td width='60' align='center'>";
				//HD:44202
				if ($login_fabrica == 3 AND ($status_os == "120" || $status_os == "122" || $status_os == "126" || $status_os == "140" || $status_os == "141" || $status_os == "143")) {
					echo "&nbsp;";
				}else{
					if(strlen($data_conserto) ==0 ){
						echo "<a href=\"javascript: if (confirm('".traduz("apenas.clicar.ok.se.tiver.certeza.que.a.data.de.conserto.do.produto.da.%.seja.hoje",$con,$cook_idioma,array($sua_os))."!') == true) { consertadoOS ($os,consertado_$i) ; }\"><img id='consertado_$i' border='0' src='/assist/imagens/btn_consertado.gif'></a>";
					}
				}

				echo "</td>";
			}
			}
			echo "</tr>";
		if ($login_fabrica == 20 and $login_posto == 6359) { //hd 88308 waldir
			echo "<form name='frm_fechar' id='frm_fechar' method='post'>";
			echo "<tr>";
				echo "<td colspan='14' align='center'><div id='mostrar_$i'></div>";
				?>
					<div id='div_fechar_<?echo $i;?>' style='display: none ; background-color:#eeeeff ; width: 300px ; height: 25px ; text-align: right; border:solid 1px #330099 ' onkeypress="if(event.keyCode==27){div_fechar_<?echo $i;?>.style.display='none' ;}">
					<div id="div_lanca_peca_fecha" style="float:right ; align:center ; width:20px ; background-color:#FFFFFF " onclick="div_fechar_<?echo $i;?>.style.display='none' ;" onmouseover="this.style.cursor='pointer'"><center><b>X</b></center>
					</div>
					<input type='hidden' size='12' name='os_fechar' id='os_fechar' value='<?echo $os;?>'>
					Data <input type='text' size='12' maxlength="10" name='data_fechamento_<? echo $i;?>' id='data_fechamento_<? echo $i?>' onKeyPress='DataHora(event, this)'> <input type='button' value='Fechar OS' onclick="javascript: if (data_fechamento_<? echo $i;?>.value.length<10){ alert('digite uma data no formato dd/mm/aaaa!');} else {fechaOSnovo2(<?php echo $os; ?>,data_fechamento_<? echo $i;?>.value,sinal_<? echo $i;?>,excluir_<?echo $i;?>,lancar_<? echo $i ?>,<?echo $i?>); }">
					</div>
				<?
				echo "<td>";
			echo "</tr>";
			echo "</form>";
		}

		}
		echo "</table>";
	}
	?>

		<!-- -------------------------------------------------------------- -->

	<?


	##### PAGINAÇÃO - INÍCIO #####
	echo "<br>";
	echo "<div>";

	if($pagina < $max_links) $paginacao = pagina + 1;
	else                     $paginacao = pagina;

	// pega todos os links e define que 'Próxima' e 'Anterior' serão exibidos como texto plano
	if (strlen($btn_acao_pre_os) ==0) {
		$todos_links = $mult_pag->Construir_Links("strings", "sim");
	}


	// função que limita a quantidade de links no rodape
	if (strlen($btn_acao_pre_os) ==0) {
		$links_limitados	= $mult_pag->Mostrar_Parte($todos_links, $coluna, $max_links);
	}
	for ($n = 0; $n < count($links_limitados); $n++) {
		echo "<font color='#DDDDDD'>".$links_limitados[$n]."</font>&nbsp;&nbsp;";
	}

	echo "</div>";

	$resultado_inicial = ($pagina * $max_res) + 1;
	$resultado_final   = $max_res + ( $pagina * $max_res);
	if (strlen($btn_acao_pre_os) ==0) {
		$registros         = $mult_pag->Retorna_Resultado();
	}

	$valor_pagina   = $pagina + 1;
	if (strlen($btn_acao_pre_os) ==0) {
		$numero_paginas = intval(($registros / $max_res) + 1);
	}
	if ($valor_pagina == $numero_paginas) $resultado_final = $registros;

	if ($registros > 0){
		echo "<br>";
		echo "<div>";
		echo "Resultados de <b>$resultado_inicial</b> a <b>$resultado_final</b> do total de <b>$registros</b> registros.";
		echo "<font color='#cccccc' size='1'>";
		echo " (Página <b>$valor_pagina</b> de <b>$numero_paginas</b>)";
		echo "</font>";
		echo "</div>";
	}
	##### PAGINAÇÃO - FIM #####

	echo "<br><h1>Resultado: $resultados ".traduz("registro(s)",$con,$cook_idioma).".</h1>";
}
?>


<?
	$sua_os             = trim (strtoupper ($_POST['sua_os']));
	if (strlen($sua_os)==0) $sua_os = trim(strtoupper($_GET['sua_os']));
	$serie              = trim (strtoupper ($_POST['serie']));
	if (strlen($serie)==0) $serie = trim(strtoupper($_GET['serie']));
	$nf_compra          = trim (strtoupper ($_POST['nf_compra']));
	if (strlen($nf_compra)==0) $nf_compra = trim(strtoupper($_GET['nf_compra']));
	$consumidor_cpf     = trim (strtoupper ($_POST['consumidor_cpf']));
	if (strlen($consumidor_cpf)==0) $consumidor_cpf = trim(strtoupper($_GET['consumidor_cpf']));
	$produto_referencia = trim (strtoupper ($_POST['produto_referencia']));
	if (strlen($produto_referencia)==0) $produto_referencia = trim(strtoupper($_GET['produto_referencia']));
	$produto_descricao  = trim (strtoupper ($_POST['produto_descricao']));
	if (strlen($produto_descricao)==0) $produto_descricao = trim(strtoupper($_GET['produto_descricao']));

	$mes = trim (strtoupper ($_POST['mes']));
	$ano = trim (strtoupper ($_POST['ano']));

	$codigo_posto    = trim (strtoupper ($_POST['codigo_posto']));
	if (strlen($codigo_posto)==0) $codigo_posto = trim(strtoupper($_GET['codigo_posto']));
	$posto_nome      = trim (strtoupper ($_POST['posto_nome']));
	if (strlen($posto_nome)==0) $posto_nome = trim(strtoupper($_GET['posto_nome']));
	$consumidor_nome = trim (strtoupper ($_POST['consumidor_nome']));
	if (strlen($consumidor_nome)==0) $consumidor_nome = trim(strtoupper($_GET['consumidor_nome']));
	$os_situacao     = trim (strtoupper ($_POST['os_situacao']));
	if (strlen($os_situacao)==0) $os_situacao = trim(strtoupper($_GET['os_situacao']));
?>


<form name="frm_consulta" method="post" action="<?echo $PHP_SELF?>">

<input type="hidden" name="acao">

<table width="400" align="center" border="0" cellspacing="0" cellpadding="2">
	<tr class="Titulo" height="30">
		<td align="center">
		<?
		fecho ("selecione.os.parametros.para.a.pesquisa",$con,$cook_idioma);
		?>
		</td>
	</tr>
</table>

<table width="400" align="center" border="0" cellspacing="0" cellpadding="2">
	<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
		<td><? fecho("numero.da.os",$con,$cook_idioma)?></td>
		<?
			if($login_fabrica==19 OR $login_fabrica==10){
				echo "<td>OS Off Line</td>";
			}
			if($login_fabrica==20 AND $login_pais =='AR') echo "<td>".traduz("os.interna",$con,$cook_idioma)."</td>";
		?>
		<td>
			<?
			if($login_fabrica==35){
				echo "PO#";
			}else{
				fecho ("numero.de.serie",$con,$cook_idioma);
			}
			?>

		</td>
		<td>
		<?
		fecho ("nf.compra",$con,$cook_idioma);
		?>
		</td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
		<td><input type="text" name="sua_os"    size="10" value="<?echo $sua_os?>"    class="frm"></td>
		<? if($login_fabrica==19 OR $login_fabrica==10 OR ($login_fabrica==20 AND $login_pais=='AR')){ ?>
			<td><input type="text" name="os_off" size="8" value="<?echo $os_off?>" class="frm"></td>
		<? } ?>
		<td><input type="text" name="serie"     size="10" value="<?echo $serie?>"     class="frm"></td>
		<td><input type="text" name="nf_compra" size="10" value="<?echo $nf_compra?>" class="frm"></td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
		<td >
		<?
		fecho ("cpf.consumidor",$con,$cook_idioma);

		?>
		</td>
		<? if($login_fabrica==19 OR $login_fabrica==10 OR ($login_fabrica==20 AND $login_pais=='AR')){ ?>
		<td></td>
		<? } ?>
		<td><? fecho("rg.do.produto",$con,$cook_idioma) ?></td>
		<td><? if($login_fabrica==30) fecho("os.revendedor",$con,$cook_idioma) ; // HD 65178
			   if($login_fabrica==2) echo "OS Posto"; // HD 81252 ?></td>

	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
		<td><input type="text" name="consumidor_cpf" size="17" value="<?echo $consumidor_cpf?>" class="frm"></td>
		<? if($login_fabrica==19 OR $login_fabrica==10 OR ($login_fabrica==20 AND $login_pais=='AR')){ ?>
		<td></td>
		<? } ?>
		<td>
			<input class="frm" type="text" name="rg_produto" size="15" maxlength="20" value="<? echo $_POST['rg_produto'] ?>" >
		</td>
		<td><? if($login_fabrica==30) { ?>
			<input class="frm" type="text" name="os_posto" size="15" maxlength="20" value="<? echo $_POST['os_posto'] ?>" >
		<? }elseif($login_fabrica == 2){ // HD 81252 ?>
			<input class="frm" type="text" name="os_posto" size="12" maxlength="10" value="<? echo $_POST['os_posto'] ?>" >
		<? } ?>
			</td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF">
		<td colspan='4' align='center'><br><input type="submit" name="btn_acao" value="<?
			fecho ("pesquisar",$con,$cook_idioma);
		?>"></td>
	</tr>
</table>


<table width="400" align="center" border="0" cellspacing="0" cellpadding="2">
	<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
		<td colspan='2'> <hr> </td>
	</tr>

	<? if($login_fabrica==7){ ?>
		<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
			<td><? fecho("data.inicial",$con,$cook_idioma); ?></td>
			<td><? fecho("data.final",$con,$cook_idioma); ?></td>
		</tr>
		<tr class="Conteudo" bgcolor="#D9E2EF" valign='top' align='center'>
			<td>
				<input type="text" name="data_inicial" id="data_inicial" size="13" maxlength="10" value="<? if (strlen($data_inicial) > 0) echo substr($data_inicial,0,10); else echo "dd/mm/aaaa"; ?>" onClick="javascript: if (this.value == 'dd/mm/aaaa') this.value='';" class="frm">
			</td>
			<td>
				<input type="text" name="data_final" id="data_final" size="13" maxlength="10" value="<? if (strlen($data_final) > 0) echo substr($data_final,0,10); else echo "dd/mm/aaaa"; ?>" onClick="javascript: if (this.value == 'dd/mm/aaaa') this.value='';" class="frm">
				&nbsp;Apenas OS em aberto <input type='checkbox' name='os_aberta' value='1' <? if (strlen ($os_aberta) > 0 ) echo " checked " ?> >
			</td>
		</tr>
	<? }else{ ?>

		<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
			<td colspan='2'><?
				fecho ("data.referente.a.digitacao.da.os.no.site.(obrigatorio.para.a.pesquisa)",$con,$cook_idioma);
			?></td>
		</tr>
		<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
			<td>
			<?
			echo "* ".traduz("mes",$con,$cook_idioma);
			?>
			</td>
			<td>
			<?
			echo "* ".traduz("ano",$con,$cook_idioma);
			?>
			</td>
		</tr>
		<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
			<td>
				<select name="mes" size="1" class="frm">
				<option value=''></option>
				<?
				for ($i = 1 ; $i <= count($meses) ; $i++) {
					echo "<option value='$i'";
					if ($mes == $i) echo " selected";
					echo ">" . $meses[$i] . "</option>";
				}
				?>
				</select>
			</td>
			<td>
				<select name="ano" size="1" class="frm">
				<option value=''></option>
				<?
				for($i = date("Y"); $i > 2003; $i--){
					echo "<option value='$i'";
					if ($ano == $i) echo " selected";
					echo ">$i</option>";
				}
				?>
				</select>

				&nbsp;&nbsp;&nbsp;

				<?
				fecho ("apenas.os.em.aberto",$con,$cook_idioma);
				?>

				<input type='checkbox' name='os_aberta' value='1' <? if (strlen ($os_aberta) > 0 ) echo " checked " ?> >
			</td>
		</tr>
	<? } ?>

	<?
	if ($login_e_distribuidor == 't' and $login_fabrica == 3) {
	?>
	<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
		<td>
			<?
			fecho ("cod.posto",$con,$cook_idioma);
			?>
		</td>
		<td>
			<?
			fecho ("nome.do.posto",$con,$cook_idioma);
			?>
		</td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
		<td>
			<input type="text" name="codigo_posto" size="8" <? if ($login_fabrica == 5) { ?> onblur="javascript: fnc_pesquisa_posto (document.frm_consulta.codigo_posto, document.frm_consulta.posto_nome, 'codigo');" <? } ?> value="<? echo $codigo_posto ?>" class="frm">
			<img border="0" src="imagens_admin/btn_lupa.gif" style="cursor: hand;" align="absmiddle" alt="
			<?
			fecho ("clique.aqui.para.pesquisar.postos.pelo.codigo",$con,$cook_idioma);
			?>" onclick="javascript: fnc_pesquisa_posto (document.frm_consulta.codigo_posto, document.frm_consulta.posto_nome, 'codigo')">
		</td>
		<td>
			<input type="text" name="posto_nome" size="30" <? if ($login_fabrica == 5) { ?> onblur="javascript: fnc_pesquisa_posto (document.frm_consulta.codigo_posto, document.frm_consulta.posto_nome, 'nome');" <? } ?> value="<?echo $posto_nome?>" class="frm">
			<img border="0" src="imagens_admin/btn_lupa.gif" style="cursor: hand;" align="absmiddle" alt="<?if($sistema_lingua == 'ES') echo "click aquí para efetuar la busca";else echo "Clique aqui para pesquisar postos pelo código";?>" onclick="javascript: fnc_pesquisa_posto (document.frm_consulta.codigo_posto, document.frm_consulta.posto_nome, 'nome')">
		</td>
	</tr>
	<?
	}
	?>




	<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
		<td><?
		if($login_fabrica==3){
			fecho ("marca",$con,$cook_idioma);
		}
		?></td>
		<td>
			<?
			fecho ("nome.do.consumidor",$con,$cook_idioma);
			?>
		</td>
	</tr>

	<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
		<td>
		<?
		if($login_fabrica==3){
			echo "<select name='marca' size='1' class='frm' style='width:95px'>";
			echo "<option value=''></option>";
			$sql = "SELECT marca, nome from tbl_marca where fabrica = $login_fabrica order by nome";
			$res = pg_query($con,$sql);
			if(pg_num_rows($res)>0){
				for($i=0;pg_num_rows($res)>$i;$i++){
					$xmarca = pg_fetch_result($res,$i,marca);
					$xnome = pg_fetch_result($res,$i,nome);
					?>
					<option value="<?echo $xmarca;?>" <? //HD 73808 if ($xmarca == $marca) echo " SELECTED "; ?>><?echo $xnome;?></option>

					<?

				}

			}
			echo "</SELECT>";
		}
		?>
		</td>
		<td><input type="text" name="consumidor_nome" size="30" value="<?echo $consumidor_nome?>" class="frm"></td>
	</tr>




	<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
		<td>
			<?
			fecho ("ref.produto",$con,$cook_idioma);
			?>
		</td>
		<td>

			<?
			fecho ("descricao.produto",$con,$cook_idioma);
			?>
		</td>
	</tr>

	<input type='hidden' name='voltagem' value=''>
	<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
		<td>
		<input class="frm" type="text" name="produto_referencia" size="15" maxlength="20" value="<? echo $produto_referencia ?>" >
		&nbsp;
		<img src='imagens/btn_lupa.gif' border='0' align='absmiddle' style="cursor:pointer" onclick="javascript: fnc_pesquisa_produto (document.frm_consulta.produto_referencia, document.frm_consulta.produto_descricao,'referencia',document.frm_consulta.voltagem)">
		</td>

		<td>
		<input class="frm" type="text" name="produto_descricao" size="30" value="<? echo $produto_descricao ?>" >
		&nbsp;
		<img src='imagens/btn_lupa.gif'  style="cursor:pointer" border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_produto (document.frm_consulta.produto_referencia, document.frm_consulta.produto_descricao,'descricao',document.frm_consulta.voltagem)">
	</tr>

<?
#Sistema de Informática para a Britânia de Pre-OS
$sqllinha =	"SELECT tbl_posto_linha.linha
		FROM    tbl_posto_linha
		JOIN    tbl_linha USING (linha)
		WHERE   tbl_posto_linha.posto = $login_posto
		AND     tbl_posto_linha.linha = 528
		AND     tbl_linha.fabrica = $login_fabrica";
$reslinha = pg_query($con,$sqllinha);

if (pg_num_rows($reslinha) > 0) {
	$linhainf = trim(pg_fetch_result($reslinha,0,linha)); //linha informatica para britania
}

if(($login_fabrica == 3 and $linhainf ==528) or $login_fabrica ==59 or $login_fabrica == 14 or $login_fabrica == 43 or $login_fabrica == 66){
?>
	<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
		<td colspan='2'> <hr> </td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF">
		<td ><? fecho ("pre-ordem.de.servico",$con,$cook_idioma); ?></td>
		<td colspan='1' align='center'><br><input type="submit" name="btn_acao_pre_os" value="<?
			fecho ("pesquisar",$con,$cook_idioma);
			?>"></td>
	</tr>
<?
}
?>


	<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
		<td colspan='2'> <hr> </td>
	</tr>


	<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
		<td colspan='2'> <?
			fecho ("os.em.aberto.da.revenda.=.cnpj",$con,$cook_idioma);
			?>
		<input class="frm" type="text" name="revenda_cnpj" size="8" value="<? echo $revenda_cnpj ?>" >
	<? if ($sistema_lingua<>'ES'){?>
		 /0001-00
<? } ?>
		</td>
	</tr>

	<? if($login_fabrica==7){ ?>
		<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
			<td colspan='2'> <hr> </td>
		</tr>
		<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
			<td colspan='2'>
				<? fecho ("natureza",$con,$cook_idioma); ?>
				<select name="natureza" class="frm">
					<option value='' selected></option>
					<?
					$sqlN = "SELECT *
						FROM tbl_tipo_atendimento
						WHERE fabrica = $login_fabrica
						AND   ativo IS TRUE
						ORDER BY tipo_atendimento";
					$resN = pg_query ($con,$sqlN) ;

					for ($z=0; $z<pg_num_rows($resN); $z++){
						$xxtipo_atendimento = pg_fetch_result($resN,$z,tipo_atendimento);
						$xxcodigo           = pg_fetch_result($resN,$z,codigo);
						$xxdescricao        = pg_fetch_result($resN,$z,descricao);

						echo "<option ";
						$teste1 = $natureza;
						$teste2 = $xxtipo_atendimento;
						if($natureza==$xxtipo_atendimento) echo " selected ";
						echo " value='" . $xxtipo_atendimento . "'" ;
						echo " > ";
						echo $xxcodigo . " - " . $xxdescricao;
						echo "</option>\n";
					}
					?>
				</select>
				<? #echo $teste1.' - '.$teste2; ?>
			</td>
		</tr>
	<? } ?>

</table>


<table width="400" align="center" border="0" cellspacing="0" cellpadding="2">
	<tr class="Conteudo" bgcolor="#D9E2EF">
		<td colspan='2' align='center'><br><input type="submit" name="btn_acao" value="<?
			fecho ("pesquisar",$con,$cook_idioma);
			?>"></td>
	</tr>
</table>




</table>


</form>

<? include "rodape.php" ?>
