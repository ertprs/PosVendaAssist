<?php

include "dbconfig.php";
include "includes/dbconnect-inc.php";

include "funcoes.php";

$admin_privilegios = "call_center,gerencia";
include "autentica_admin.php";

# Pesquisa pelo AutoComplete AJAX
$q = strtolower($_GET["q"]);
if (isset($_GET["q"])){
	$busca      = $_GET["busca"];
	$tipo_busca = $_GET["tipo_busca"];

	if (strlen($q)>2){

		if ($tipo_busca=="posto"){
			$sql = "SELECT tbl_posto.cnpj, tbl_posto.nome, tbl_posto_fabrica.codigo_posto
					FROM tbl_posto
					JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
					WHERE tbl_posto_fabrica.fabrica = $login_fabrica ";

			$sql .= ($busca == "codigo") ? " AND tbl_posto_fabrica.codigo_posto = '$q' " : " AND UPPER(tbl_posto.nome) like UPPER('%$q%') ";

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
		if ($tipo_busca=="produto"){
			$sql = "SELECT tbl_produto.produto,
							tbl_produto.referencia,
							tbl_produto.descricao
					FROM tbl_produto
					JOIN tbl_linha ON tbl_linha.linha = tbl_produto.linha
					WHERE tbl_linha.fabrica = $login_fabrica ";

			$sql .=  ($busca == "codigo") ? " AND tbl_produto.referencia like '%$q%' " : " AND UPPER(tbl_produto.descricao) like UPPER('%$q%') ";

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

$os_excluir = $_GET['excluir']; //hd 61698 waldir

if (strlen ($os_excluir) > 0) {
	$sql = "SELECT fn_os_excluida($os_excluir,$login_fabrica,$login_admin);";
				$res = @pg_exec ($con,$sql);
	$msg_erro = pg_errormessage($con);

	if (strlen ($msg_erro) == 0) {
		header("Location: os_parametros.php");
		exit;
	}

}

$os_fechar = $_GET['fechar'];

if (strlen ($os_fechar) > 0) {
	$msg_erro = "";
	$res = pg_exec ($con,"BEGIN TRANSACTION");

	$sql = "SELECT status_os
				FROM tbl_os_status
				WHERE os = $os_fechar
				AND status_os IN (62,64,65,72,73,87,88,116,117)
				ORDER BY data DESC
				LIMIT 1";
		$res = pg_exec ($con,$sql);
		if (pg_numrows($res)>0){
			$status_os = trim(pg_result($res,0,status_os));
			if ($status_os=="72" || $status_os=="62" || $status_os=="87" || $status_os=="116"){
				if ($login_fabrica ==51) { // HD 59408
					$sql = " INSERT INTO tbl_os_status
							(os,status_os,data,observacao)
							VALUES ($os_fechar,64,current_timestamp,'OS Fechada pelo posto')";
					$res = pg_exec($con,$sql);
					$msg_erro .= pg_errormessage($con);

					$sql = "UPDATE tbl_os_item SET servico_realizado = 671 FROM tbl_os_produto
							WHERE tbl_os_produto.os_produto = tbl_os_item.os_produto
							AND   tbl_os_produto.os = $os_fechar";
					$res = pg_exec($con,$sql);
					$msg_erro .= pg_errormessage($con);

					$sql = "UPDATE tbl_os SET defeito_constatado = 10536,solucao_os = 491
							WHERE tbl_os.os = $os_fechar";
					$res = pg_exec($con,$sql);
					$msg_erro .= pg_errormessage($con);
				}else{
					$msg_erro .= traduz("os.com.intervencao,.nao.pode.ser.fechada.",$con,$cook_idioma);
				}
			}
		}

		$sql = "UPDATE tbl_os SET data_fechamento = CURRENT_TIMESTAMP WHERE os = $os_fechar AND fabrica = $login_fabrica";
		$res = pg_exec ($con,$sql);
		$msg_erro .= pg_errormessage($con) ;

		if (strlen ($msg_erro) == 0 AND $login_fabrica == 1) {
			$sql = "SELECT fn_valida_os_item($os_fechar, $login_fabrica)";
			$res = @pg_exec ($con,$sql);
			$msg_erro = pg_errormessage($con);
		}

		if (strlen ($msg_erro) == 0) {
			$sql = "SELECT fn_finaliza_os($os_fechar, $login_fabrica)";
			$res = pg_exec ($con,$sql);
			$msg_erro = pg_errormessage($con) ;
			if (strlen ($msg_erro) == 0 and ($login_fabrica==1 or $login_fabrica==24)) {
				$sql = "SELECT fn_estoque_os($os_fechar, $login_fabrica)";
				$res = @pg_exec ($con,$sql);
				$msg_erro = pg_errormessage($con);
			}
		}

		if (strlen ($msg_erro) == 0) {
			$res = pg_exec ($con,"COMMIT TRANSACTION");
			echo "ok;XX$os_fechar";
		}else{
			$res = @pg_exec ($con,"ROLLBACK TRANSACTION");
			echo "erro;$sql ==== $msg_erro ";
		}
	flush();
	exit;
}
$msg = "";

$meses = array(1 => "Janeiro", "Fevereiro", "Março", "Abril", "Maio", "Junho", "Julho", "Agosto", "Setembro", "Outubro", "Novembro", "Dezembro");

if (strlen($_POST['btn_acao']) > 0 or strlen($_GET['btn_acao']) > 0) {

	$os_off    = trim (strtoupper ($_POST['os_off']));
	if (strlen($os_off)==0) $os_off = trim(strtoupper($_GET['os_off']));
	$codigo_posto_off      = trim(strtoupper($_POST['codigo_posto_off']));
	if (strlen($codigo_posto_off)==0) $codigo_posto_off = trim(strtoupper($_GET['codigo_posto_off']));
	$posto_nome_off        = trim(strtoupper($_POST['posto_nome_off']));
	if (strlen($posto_nome_off)==0) $posto_nome_off = trim(strtoupper($_GET['posto_nome_off']));

	$sua_os    = trim (strtoupper ($_POST['sua_os']));
	if (strlen($sua_os)==0) $sua_os = trim(strtoupper($_GET['sua_os']));
	$serie     = trim (strtoupper ($_POST['serie']));
	if (strlen($serie)==0) $serie = trim(strtoupper($_GET['serie']));
	$nf_compra = trim (strtoupper ($_POST['nf_compra']));
	if (strlen($nf_compra)==0) $nf_compra = trim(strtoupper($_GET['nf_compra']));
	$consumidor_cpf = trim (strtoupper ($_POST['consumidor_cpf']));
	if (strlen($consumidor_cpf)==0) $consumidor_cpf = trim(strtoupper($_GET['consumidor_cpf']));

	$marca     = trim ($_POST['marca']);
	$cond_marca = (strlen($marca)>0) ? " tbl_marca.marca = $marca " :" 1 = 1 ";

	$regiao     = trim ($_POST['regiao']);
	if (strlen($regiao)==0) $regiao = trim($_GET['regiao']);

	$classificacao_os = trim ($_POST['classificacao_os']); // HD 75762 para Filizola
	$cond_classificacao_os = (strlen($classificacao_os)>0) ? " tbl_os_extra.classificacao_os = $classificacao_os " : " 1 = 1 ";

	$natureza = trim ($_POST['natureza']); //HD 45630
	$cond_natureza = (strlen($natureza)>0) ? " tbl_os.tipo_atendimento = $natureza " : " 1 = 1 ";

	# HD 48224
	$admin_abriu = trim ($_POST['admin_abriu']);
	if(strlen($admin_abriu) > 0){
		$cond_admin = "AND tbl_os.admin = $admin_abriu";
	}

	$rg_produto  = strtoupper(trim ($_POST['rg_produto']));

//takashi - não sei pq colocaram isso, estava com problema... caso necessite voltar, consulte o suporte
//takashi alterei novamente conforme Tulio e Samuel falaram
	if((strlen($sua_os)>0) and (strlen($sua_os)<4))$msg="Digite no minímo 4 caracteres para fazer a pesquisa";
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
	$admin              = trim($_POST['admin']);
	if (strlen($admin)==0) $admin = trim($_GET['admin']);
	$os_aberta          = trim(strtoupper($_POST['os_aberta']));
	if (strlen($os_aberta)==0) $os_aberta = trim(strtoupper($_GET['os_aberta']));
	#115630----
	$os_finalizada      = trim(strtoupper($_POST['os_finalizada']));
	if (strlen($os_finalizada)==0) $os_finalizada = trim(strtoupper($_GET['os_finalizada']));
	#----------
	$os_situacao        = trim(strtoupper($_POST['os_situacao']));
	if (strlen($os_situacao)==0) $os_situacao = trim(strtoupper($_GET['os_situacao']));
	$revenda_cnpj       = trim(strtoupper($_POST['revenda_cnpj']));
	if (strlen($revenda_cnpj)==0) $revenda_cnpj = trim(strtoupper($_GET['revenda_cnpj']));
	$pais               = trim(strtoupper($_POST['pais']));
	if (strlen($pais)==0) $pais = trim(strtoupper($_GET['pais']));

	$tipo_os               = trim(strtoupper($_POST['tipo_os']));
	if (strlen($tipo_os)==0) $tipo_os = trim(strtoupper($_GET['tipo_os']));

	$data_inicial = $_POST['data_inicial'];
	if (strlen($data_inicial)==0) $data_inicial = trim($_GET['data_inicial']);
	$data_final   = $_POST['data_final'];
	if (strlen($data_final)==0) $data_final = trim($_GET['data_final']);

	if ($login_fabrica <> 15) {
			if (strlen ($consumidor_nome) > 0 AND strlen ($codigo_posto) == 0 AND strlen ($produto_referencia) == 0) {
			$msg = "Especifique o posto ou o produto";
		}
	}

	$consumidor_cpf = str_replace (".","",$consumidor_cpf);
	$consumidor_cpf = str_replace (" ","",$consumidor_cpf);
	$consumidor_cpf = str_replace ("-","",$consumidor_cpf);
	$consumidor_cpf = str_replace ("/","",$consumidor_cpf);
	if (strlen ($consumidor_cpf) <> 11 AND strlen ($consumidor_cpf) <> 14 AND strlen ($consumidor_cpf) <> 0) {
		#HD 17333
		if ($login_fabrica<>20){
			$msg = "Tamanho do CPF do consumidor inválido";
		}
	}

	$revenda_cnpj = str_replace (".","",$revenda_cnpj);
	$revenda_cnpj = str_replace (" ","",$revenda_cnpj);
	$revenda_cnpj = str_replace ("-","",$revenda_cnpj);
	$revenda_cnpj = str_replace ("/","",$revenda_cnpj);
	if (strlen ($revenda_cnpj) <> 8 AND strlen ($revenda_cnpj) > 0) {
		$msg = "Digite os 8 primeiros dígitos do CNPJ";
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
		}
	}

	if ( (strlen ($codigo_posto) > 0 OR strlen ($posto_nome) > 0 OR strlen ($consumidor_nome) > 0 OR strlen ($produto_referencia) > 0 ) AND ( strlen ($mes) == 0 OR strlen ($ano) == 0) AND $login_fabrica<>"7")  {
		if ($login_fabrica == 15) { // HD 60665
			if (strlen($ano)==0 and strlen($consumidor_nome) > 0) {
				$msg = "Digite o ano para fazer a pesquisa";
			}elseif((strlen ($codigo_posto) > 0 OR strlen ($posto_nome) > 0  OR strlen ($produto_referencia) > 0 ) AND (strlen ($mes) == 0 OR strlen ($ano) == 0)){
				$msg = "Digite o mês e o ano para fazer a pesquisa";
			}
		}else{
			$msg = "Digite o mês e o ano para fazer a pesquisa";
		}
	}

	if ( (strlen ($codigo_posto) == 0 AND strlen ($posto_nome) == 0 AND strlen ($consumidor_nome) == 0 AND strlen ($produto_referencia) == 0 AND strlen ($admin) == 0 ) AND ( strlen ($mes) > 0 OR strlen ($ano) > 0) and ($login_fabrica==20 and ($pais=='BR' or $pais=='' )))  {
		$msg = "Especifique mais um campo para a pesquisa";
	}

	if ( strlen ($posto_nome) > 0 AND strlen ($posto_nome) < 5 ) {
		$msg = "Digite no mínimo 5 letras para o nome do posto";
	}

	if ( strlen ($consumidor_nome) > 0 AND strlen ($consumidor_nome) < 5) {
		$msg = "Digite no mínimo 5 letras para o nome do consumidor";
	}

	if ( strlen ($serie) > 0 AND strlen ($serie) < 5) {
		$msg = "Digite no mínimo 5 letras para o número de série";
	}
	if ($login_fabrica == 30) {
		$os_posto = trim (strtoupper ($_POST['os_posto']));
		if (strlen($os_posto)==0) $os_posto = trim(strtoupper($_GET['os_posto']));

		if ( strlen ($os_posto) > 0 AND strlen ($os_posto) < 5) {
			$msg = "Digite no mínimo 5 letras para o número de OS revendedor";
		}

	}

	if($login_fabrica==7){
		if(strlen($data_inicial)>0 AND $data_inicial<>"dd/mm/aaaa"){
			$xdata_inicial = fnc_formata_data_pg($data_inicial);
			$xdata_inicial = str_replace("'","",$xdata_inicial);
			$mes = "1";
		}else {
			if(strlen($sua_os)==0) {
				$msg = "Digite a data inicial para fazer a pesquisa";
			}

			if (strlen($consumidor_cpf)>7) { //hd 69020 waldir
				$msg = "";
			}
		}

		if(strlen($data_final)>0 AND $data_final<>"dd/mm/aaaa"){
			$xdata_final = fnc_formata_data_pg($data_final);
			$xdata_final = str_replace("'","",$xdata_final);
			$mes = "1";
		}else {
			if(strlen($sua_os)==0) {
				$msg = "Digite a data final para fazer a pesquisa";
			}
			if (strlen($consumidor_cpf)>7) { //hd 69020 waldir
				$msg = "";
			}
		}

		if(strlen($data_inicial)>0 AND $data_inicial<>"dd/mm/aaaa" AND strlen($data_final)>0 AND $data_final<>"dd/mm/aaaa"){
			$sqlX = "SELECT ('$xdata_final'::date - '$xdata_inicial'::date)";
			$resX = pg_exec($con,$sqlX);
			$periodo = pg_result($resX,0,0);
			if($periodo > "30") $msg = "Período entre datas não pode ser maior que 30 dias";
		}
	}else{
		if ($login_fabrica == 15) { // HD 60665
			if (strlen($mes) > 0) {
				$xdata_inicial = date("Y-m-01 00:00:00", mktime(0, 0, 0, $mes, 1, $ano));
				$xdata_final   = date("Y-m-t 23:59:59", mktime(0, 0, 0, $mes, 1, $ano));
			}elseif(strlen($mes) ==0 and strlen($ano) > 0 and strlen($consumidor_nome) >0){
				$xdata_inicial = date("Y-m-01 00:00:00", mktime(0, 0, 0, 01, 1, $ano));
				$xdata_final   = date("Y-m-t 23:59:59", mktime(0, 0, 0, 12, 1, $ano));
			}else{
				if (strlen($sua_os) == 0){
					# 64393 - se for pelo número da OS deixa pesquisar
					$msg="Especifique mais campos para pesquisa";
				}
			}
		}else{
			if (strlen($mes) > 0) {
				$xdata_inicial = date("Y-m-01 00:00:00", mktime(0, 0, 0, $mes, 1, $ano));
				$xdata_final   = date("Y-m-t 23:59:59", mktime(0, 0, 0, $mes, 1, $ano));
			}
		}
	}

	//validacao para pegar o posto qdo for digitado a os_off
	if(strlen($os_off)>0){
		if ((strlen($codigo_posto_off)==0) OR (strlen($posto_nome_off)==0)){
			$msg = "Informe o Posto desejado";
		}
	}
	//IGOR HD 1967 BLACK - PARA CONSULTAR OS É OBRIGATÓRIO SELECIONAR O POSTO
	if($login_fabrica==1) {
		if ((strlen($codigo_posto)== 0 ) and (strlen($sua_os)>0) )
			$msg = "Para consultar pelo número de OS é necessário Informar o código do posto";
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
			$res = pg_exec($con,$sql);
			if (pg_numrows($res) == 1) {
				$posto        = trim(pg_result($res,0,posto));
				$posto_codigo = trim(pg_result($res,0,codigo_posto));
				$posto_nome   = trim(pg_result($res,0,nome));
			}else{
				$erro .= " Posto não encontrado. ";
			}
		}
	}
}

$layout_menu = "callcenter";
$title = "Seleção de Parâmetros para Relação de Ordens de Serviços Lançadas";
include "cabecalho.php";
?>

<style type="text/css">
.Tabela{
	border:1px solid #596D9B;
	background-color:#596D9B;
}
.Erro{
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 12px;
	color:#CC3300;
	font-weight: bold;
	background-color:#FFFFFF;
}
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
<? include "javascript_calendario.php"; //adicionado por Fabio 27-09-2007 ?>

<script language="javascript" src="js/cal2.js"></script>
<script language="javascript" src="js/cal_conf2.js"></script>
<script type='text/javascript' src='js/jquery.autocomplete.js'></script>
<link rel="stylesheet" type="text/css" href="js/jquery.autocomplete.css" />
<script type='text/javascript' src='js/jquery.bgiframe.min.js'></script>
<script type='text/javascript' src='js/dimensions.js'></script>
<script language="javascript" src="js/assist.js"></script>
<script language='javascript' src='ajax.js'></script>

<script language="JavaScript">

$(function()
{
	$('#data_inicial').datePicker({startDate:'01/01/2000'});
	$('#data_final').datePicker({startDate:'01/01/2000'});
	$("#data_inicial").maskedinput("99/99/9999");
	$("#data_final").maskedinput("99/99/9999");
});

$().ready(function() {

	function formatItem(row) {
		return row[2] + " - " + row[1];
	}

	/* OFFF Busca pelo Código */
	$("#codigo_posto_off").autocomplete("<?echo $PHP_SELF.'?tipo_busca=posto&busca=codigo'; ?>", {
		minChars: 3,
		delay: 150,
		width: 350,
		matchContains: true,
		formatItem: formatItem,
		formatResult: function(row) {return row[2];}
	});

	$("#codigo_posto_off").result(function(event, data, formatted) {
		$("#posto_nome_off").val(data[1]) ;
	});

	/* Busca pelo Nome */
	$("#posto_nome_off").autocomplete("<?echo $PHP_SELF.'?tipo_busca=posto&busca=nome'; ?>", {
		minChars: 3,
		delay: 150,
		width: 350,
		matchContains: true,
		formatItem: formatItem,
		formatResult: function(row) {return row[1];}
	});

	$("#posto_nome_off").result(function(event, data, formatted) {
		$("#codigo_posto_off").val(data[2]) ;
		//alert(data[2]);
	});


	/* Busca pelo Código */
	$("#codigo_posto").autocomplete("<?echo $PHP_SELF.'?tipo_busca=posto&busca=codigo'; ?>", {
		minChars: 3,
		delay: 150,
		width: 350,
		matchContains: true,
		formatItem: formatItem,
		formatResult: function(row) {return row[2];}
	});

	$("#codigo_posto").result(function(event, data, formatted) {
		$("#posto_nome").val(data[1]) ;
	});

	/* Busca pelo Nome */
	$("#posto_nome").autocomplete("<?echo $PHP_SELF.'?tipo_busca=posto&busca=nome'; ?>", {
		minChars: 3,
		delay: 150,
		width: 350,
		matchContains: true,
		formatItem: formatItem,
		formatResult: function(row) {return row[1];}
	});

	$("#posto_nome").result(function(event, data, formatted) {
		$("#codigo_posto").val(data[2]) ;
		//alert(data[2]);
	});


	/* Busca por Produto */
	$("#produto_descricao").autocomplete("<?echo $PHP_SELF.'?tipo_busca=produto&busca=nome'; ?>", {
		minChars: 3,
		delay: 150,
		width: 350,
		matchContains: true,
		formatItem: formatItem,
		formatResult: function(row) {return row[1];}
	});

	$("#produto_descricao").result(function(event, data, formatted) {
		$("#produto_referencia").val(data[2]) ;
	});

	/* Busca pelo Nome */
	$("#produto_referencia").autocomplete("<?echo $PHP_SELF.'?tipo_busca=produto&busca=codigo'; ?>", {
		minChars: 3,
		delay: 150,
		width: 350,
		matchContains: true,
		formatItem: formatItem,
		formatResult: function(row) {return row[2];}
	});

	$("#produto_referencia").result(function(event, data, formatted) {
		$("#produto_descricao").val(data[1]) ;
		//alert(data[2]);
	});

});

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
					alert ('OS <? echo("fechada.com.sucesso") ?>');
					sinal.src='/assist/admin/imagens_admin/status_vermelho.gif';
					sinal.src='/assist/imagens/pixel.gif';
					excluir.src='/assist/imagens/pixel.gif';
					if(lancar){
						lancar.src='/assist/imagens/pixel.gif';
					}
				}else{
					if (http.responseText.indexOf ('de-obra para instala') > 0) {
						alert ('<? echo("esta.os.nao.tem.mao-de-obra.para.instalacao") ?>');
					}else if (http.responseText.indexOf ('Nota Fiscal de Devol') > 0) {
						alert ('<? echo("por.favor.utilizar.a.tela.de.fechamento.de.os.para.informar.a.nota.fiscal.de.devolucao") ?>');
					}else if (http.responseText.indexOf ('o-de-obra para atendimento') > 0) {
						alert ('<? echo("esta.os.nao.tem.mao-de-obra.para.este.atendimento") ?>');
					}else if (http.responseText.indexOf ('Favor informar aparência do produto e acessórios') > 0) {
						alert ('<? echo("por.favor.verifique.os.dados.digitados.aparencia.e.acessorios.na.tela.de.lancamento.de.itens") ?>');
					}else if (http.responseText.indexOf ('Type informado para o produto não é válido') > 0) {
						alert ('<? echo("type.informado.para.o.produto.nao.e.valido") ?>');
					}else if (http.responseText.indexOf ('OS com peças pendentes') > 0) {
						alert ('<? echo("os.com.pecas.pendentes,.favor.informar.o.motivo.na.tela.de.fechamento.da.os") ?>');
					}else if(http.responseText.indexOf ('OS não pode ser fechada, Favor Informar a Kilometragem') > 0){
						alert ('<? echo("os.nao.pode.ser.fechada,.favor.informar.a.kilometragem") ?>');
					}else if (http.responseText.indexOf ('OS não pode ser fechada, Kilometragem Recusada') > 0){
						alert ('<? echo("os.nao.pode.ser.fechada,.kilometragem.recusada") ?>');
					}else if (http.responseText.indexOf ('OS não pode ser fechada, aguardando aprovação de Kilometragem') > 0){
						alert ('<? echo("os.nao.pode.ser.fechada,.aguardando.aprovacao.de.kilometragem") ?>');
					}else if (http.responseText.indexOf ('Esta OS teve o número de série recusado e não pode ser finalizada') > 0){
						alert ('<? echo("esta.os.teve.o.numero.de.serie.recusado.e.nao.pode.ser.finalizada") ?>');
					}else if (http.responseText.indexOf ('Informar defeito constatado (Reparo) para OS') > 0){
						alert ('<? echo("por.favor.verifique.os.dados.digitados.em.defeito.constatado.(reparo).na.tela.de.lancamento.de.itens") ?>');
					}else if (http.responseText.indexOf ('Por favor, informar o conserto do produto na tela CONSERTADO') > 0){
						alert ('<? echo("por.favor.informar.o.conserto.do.produto.na.tela.consertado") ?>');
					}else {
						alert ('<? echo("por.favor.verifique.os.dados.digitados.defeito.constatado.e.solucao.na.tela.de.lancamento.de.itens") ?>');
					}
				}
			}else{
				alert ('<? echo("fechamento.nao.processado") ?>');
			}
		}
	}
}

function fechaOS (os , sinal , excluir , lancar ) {
	var curDateTime = new Date();
	url = "<?= $PHP_SELF ?>?fechar=" + escape(os) + '&dt='+curDateTime;
	http.open("GET", url , true);
	http.onreadystatechange = function () { retornaFechamentoOS (http , sinal, excluir, lancar) ; } ;
	http.send(null);
}

function selecionarTudo(){
	$('input[@rel=imprimir]').each( function (){
		this.checked = !this.checked;
	});
}

function imprimirSelecionados(){
	var qtde_selecionados = 0;
	var linhas_seleciondas = "";
	$('input[@rel=imprimir]:checked').each( function (){
		if (this.checked){
			linhas_seleciondas = this.value+", "+linhas_seleciondas;
			qtde_selecionados++;
		}
	});

	if (qtde_selecionados>0){
		janela = window.open('os_print_selecao.php?lista_os='+linhas_seleciondas,"janela","toolbar=no,location=no,status=no,scrollbars=yes,directories=no,width=850,height=600,top=18,left=0");
	}
}

</script>
<br>

<?
#-------------- Obriga a digitação de alguns critérios ---------------
#-------------- TULIO 26/02/2007 - Nao mudar sem me avisar -----------
if (strlen ($os_off) == 0 AND
	strlen ($sua_os) == 0 AND
	strlen ($serie)  == 0 AND
	strlen ($nf_compra) == 0 AND
	strlen ($consumidor_cpf) == 0 AND
	strlen ($mes) == 0 AND
	strlen ($ano) == 0 AND
	strlen ($consumidor_nome) == 0 AND
	strlen ($posto_codigo) == 0 AND
	strlen ($posto_nome) == 0 AND
	strlen ($produto_referencia) == 0 AND strlen($rg_produto) == 0 AND strlen($os_posto) == 0 AND (strlen($_POST['btn_acao']) > 0 or strlen($_GET['btn_acao']) > 0)) {
		$msg = "Necessário especificar mais campos para pesquisa";
}
#--------------------------------------------------------------------

if(strlen($msg)>0){
	echo "<table class='Tabela' width='700' cellspacing='0'  cellpadding='0' align='center'>";
	echo "<tr >";
	echo "<td bgcolor='FFFFFF' width='60'class='Erro'><img src='imagens/proibido2.jpg' align='middle'></td><td  class='Erro' bgcolor='FFFFFF' align='left'> $msg</td>";
	echo "</tr>";
	echo "</table><br>";
}

if ((strlen($_POST['btn_acao']) > 0 or strlen($_GET['btn_acao']) > 0) AND strlen($msg) == 0) {

		$join_especifico = "";
		$especifica_mais_1 = "1=1";
		$especifica_mais_2 = "1=1";

		if (strlen ($xdata_inicial) > 0) {
			if (strlen ($produto_referencia) > 0) {
				$sqlX = "SELECT produto FROM tbl_produto JOIN tbl_linha USING (linha) WHERE tbl_linha.fabrica = $login_fabrica AND tbl_produto.referencia = '$produto_referencia'";
				$resX = pg_exec ($con,$sqlX);
				$produto = pg_result ($resX,0,0);
				$especifica_mais_1 = "tbl_os.produto = $produto";
			}

			if (strlen ($codigo_posto) > 0) {
				$sqlX = "SELECT posto FROM tbl_posto_fabrica WHERE fabrica = $login_fabrica AND upper(codigo_posto) = upper('$codigo_posto')";
				$resX = pg_exec ($con,$sqlX);
				if (pg_numrows($resX) > 0) {
					$posto = pg_result ($resX,0,0);
					$especifica_mais_2 = "tbl_os.posto = $posto";
				}
			}
			if($login_fabrica ==50 AND $tipo_os =='OS_COM_TROCA'){ // HD 48198
				$join_troca = " JOIN tbl_os_troca ON tbl_os.os = tbl_os_troca.os ";
			}

			if($login_fabrica ==45 AND ($tipo_os =='TROCA' OR $tipo_os == 'RESSARCIMENTO')){ //HD 62394 waldir
				$join_troca = " JOIN tbl_os_troca ON tbl_os.os = tbl_os_troca.os ";
			}

			if($login_fabrica==7){
				$HI = "00:00:00";
				$HF = "23:59:59";
			}
			
			$sqlTP = "
			SELECT distinct tbl_os.os
			INTO TEMP tmp_consulta_$login_admin
			FROM tbl_os
			$join_troca
			WHERE fabrica = $login_fabrica
			AND   tbl_os.data_digitacao BETWEEN '$xdata_inicial $HI' AND '$xdata_final $HF'
			AND   $especifica_mais_1
			AND   $especifica_mais_2";
			
			if($login_fabrica == 45 and $tipo_os == 'RESSARCIMENTO'){
				$sqlTP .=" AND tbl_os_troca.ressarcimento = 't'";
			}

			$sqlTP .= ";CREATE INDEX tmp_consulta_OS_$login_admin ON tmp_consulta_$login_admin(os)";

			$resX = @pg_exec ($con,$sqlTP);

			$join_especifico = "JOIN tmp_consulta_$login_admin oss ON tbl_os.os = oss.os ";
		}
		//HD 14927
		if($login_fabrica == 11 or $login_fabrica == 45 or $login_fabrica == 15 or $login_fabrica == 3 ){
			$sql_data_conserto=" , to_char(tbl_os.data_conserto,'DD/MM/YYYY') as data_conserto ";
		}

		// OS não excluída
		$sql =  "SELECT tbl_os.os                                                         ,
						tbl_os.sua_os                                                     ,
						tbl_os.nota_fiscal                                                ,
						tbl_os.os_numero                                                  ,
						sua_os_offline                                                    ,
						LPAD(tbl_os.sua_os,20,'0')                   AS ordem             ,
						TO_CHAR(tbl_os.data_digitacao,'DD/MM/YYYY')  AS digitacao         ,
						TO_CHAR(tbl_os.data_abertura,'DD/MM/YYYY')   AS abertura          ,
						TO_CHAR(tbl_os.data_fechamento,'DD/MM/YYYY') AS fechamento        ,
						TO_CHAR(tbl_os.finalizada,'DD/MM/YYYY')      AS finalizada        ,
						tbl_os.serie                                                      ,
						tbl_os.excluida                                                   ,
						tbl_os.motivo_atraso                                              ,
						tbl_os.tipo_os_cortesia                                           ,
						tbl_os.consumidor_revenda                                         ,
						tbl_os.consumidor_nome                                            ,
						tbl_os.consumidor_fone                                            ,
						tbl_os.revenda_nome                                               ,
						tbl_os.tipo_atendimento                                           ,
						tbl_os.os_reincidente                      AS reincidencia        ,
						tbl_os.os_posto                                                   ,
						tbl_os.aparencia_produto                                          ,
						tbl_os.tecnico_nome                                               ,
						tbl_os.rg_produto                                                 ,
						tbl_tipo_atendimento.descricao                                    ,
						tbl_posto_fabrica.codigo_posto                                    ,
						tbl_posto.nome                              AS posto_nome         ,
						tbl_posto.capital_interior                                        ,
						tbl_posto.estado                                                  ,
						tbl_os_extra.impressa                                             ,
						tbl_os_extra.extrato                                              ,
						tbl_os_extra.os_reincidente                                       ,
						tbl_produto.referencia                      AS produto_referencia ,
						tbl_produto.descricao                       AS produto_descricao  ,
						tbl_produto.voltagem                        AS produto_voltagem   ,
						distrib.codigo_posto                        AS codigo_distrib     ,";
						if ($login_fabrica == 3) {
							$sql .= "tbl_marca.marca ,
										tbl_marca.nome as marca_nome,";
						}
			$sql .= " (SELECT status_os FROM tbl_os_status WHERE tbl_os.os = tbl_os_status.os ORDER BY data DESC LIMIT 1) AS status_os
			$sql_data_conserto
				FROM      tbl_os
				$join_especifico
				LEFT JOIN tbl_tipo_atendimento ON tbl_tipo_atendimento.tipo_atendimento = tbl_os.tipo_atendimento
				LEFT JOIN      tbl_posto         ON  tbl_posto.posto           = tbl_os.posto
				LEFT JOIN      tbl_posto_fabrica ON  tbl_posto_fabrica.posto   = tbl_posto.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
				LEFT JOIN      tbl_produto       ON  tbl_produto.produto       = tbl_os.produto
				LEFT JOIN      tbl_os_extra      ON  tbl_os_extra.os           = tbl_os.os";

		if (strlen($os_situacao) > 0) {
			$sql .= " JOIN tbl_extrato ON tbl_extrato.extrato = tbl_os_extra.extrato";
			if ($os_situacao == "PAGA")
				$sql .= " JOIN tbl_extrato_financeiro ON tbl_extrato_financeiro.extrato = tbl_extrato.extrato";
		}
		if ($login_fabrica == 3) {
			$sql .= " LEFT JOIN tbl_marca ON tbl_marca.marca = tbl_produto.marca ";
		}
		$sql .=	"
				LEFT JOIN tbl_posto_linha           ON tbl_posto_linha.linha         = tbl_produto.linha AND tbl_posto_linha.posto = tbl_os.posto
				LEFT JOIN tbl_posto_fabrica distrib ON tbl_os.digitacao_distribuidor = distrib.posto AND distrib.fabrica = $login_fabrica
				WHERE tbl_os.fabrica = $login_fabrica
				$cond_admin";

		if($login_fabrica <>3 AND $login_fabrica <> 11 AND $login_fabrica<>45 AND $login_fabrica<>20) {
			if($login_fabrica <>50 AND $login_fabrica <>35 AND $login_fabrica <> 14) $sql .=" AND   tbl_os.excluida IS NOT TRUE ";
			$sql .=" AND  (status_os NOT IN (13,15) OR status_os IS NULL)";
		}
		#HD 13940 - Para mostrar as OS recusadas
		if($login_fabrica==20) {
			$sql .=" AND (tbl_os.excluida IS NOT TRUE OR tbl_os_extra.status_os = 94 )
					 AND  (status_os NOT IN (13,15) OR status_os IS NULL)";
		}

		if (strlen($mes) > 0) {
			$sql .= " AND tbl_os.data_digitacao BETWEEN '$xdata_inicial $HI' AND '$xdata_final $HF'";
		}
		 //takashi colocou 11/12/07 hd 9542
		/* postos que tinham */
			if (strlen($posto_nome) > 0) {
				$posto_nome = strtoupper ($posto_nome);
				$sql .= " AND upper(tbl_posto.nome) LIKE upper('$posto_nome%') ";
			}

			if (strlen($codigo_posto) > 0) {
				$sql .= " AND (upper(tbl_posto_fabrica.codigo_posto) = '$codigo_posto' OR upper(distrib.codigo_posto) = '$codigo_posto')";
			}//TAKASHI COLOCOU OS 2 UPPERS NO CÓDIGO, POSTOS DA CADENCE QUE TEM LETRA NO CÓDIGO NAO ESTAVAM ACHANDO, FIZ O EXPLAIN E NÃO DEU DIFERENCA COM UPPER OU NÃO. Caso dê problema uma alternativa seria igualar diretamente com o posto, pois antes do sql ele ja localiza o posto.
			//HD 9542 11/12/07

		if (strlen($produto_referencia) > 0) {
			$sql .= " AND tbl_produto.referencia = '$produto_referencia' ";
		}

		if (strlen($admin) > 0) {
			$sql .= " AND tbl_os.admin = '$admin' ";
		}
		if($login_fabrica == 3 ){
			$sql .= " AND $cond_marca ";
		}
		if($login_fabrica == 7 ){
			$sql .= " AND $cond_natureza AND $cond_classificacao_os"; // HD 75762 para Filizola
		}

		if($login_fabrica == 45) {
			if(strlen($rg_produto)>0){
				$sql .= " AND tbl_os.os IN (SELECT os FROM tbl_produto_rg_item WHERE UPPER(rg) = '$rg_produto') ";
			}
		}

		if (strlen($os_posto) > 0) { // HD 72899
			$sql .= " AND tbl_os.os_posto ilike '%$os_posto%' ";
		}

		if (strlen($sua_os) > 0) {
			#A Black tem consulta separada(os_consulta_avancada.php).
			if ($login_fabrica == 1) {
				$pos = strpos($sua_os, "-");

				if ($pos === false) {
					//hd 47506
					if(strlen ($sua_os) > 11){
						$pos = strlen($sua_os) - (strlen($sua_os)-7);
					} elseif(strlen ($sua_os) > 10) {
						$pos = strlen($sua_os) - (strlen($sua_os)-6);
					} elseif(strlen ($sua_os) > 9) {
						$pos = strlen($sua_os) - (strlen($sua_os)-5);
					}else{
						$pos = strlen($sua_os);
					}
				}else{
					//hd 47506
					if(strlen (substr($sua_os,0,$pos)) > 11){#47506
						$pos = $pos - 7;
					} else if(strlen (substr($sua_os,0,$pos)) > 10) {
						$pos = $pos - 6;
					} elseif(strlen ($sua_os) > 9) {
						$pos = $pos - 5;
					}
				}
				$xsua_os = substr($sua_os, $pos,strlen($sua_os));
			}
			$sua_os = strtoupper ($sua_os);

			$pos = strpos($sua_os, "-");
			if ($pos === false) {
				if(!ctype_digit($sua_os)){
					$sql .= " AND tbl_os.sua_os = '$sua_os' ";
				}else{
					//hd 47506 - acrescentado OR "tbl_os.sua_os = '$sua_os'"
					if($login_fabrica ==1){
						$sql .= " AND (tbl_os.os_numero = '$sua_os' OR tbl_os.sua_os like '%$xsua_os' )";
					}else{
						$sql .= " AND (tbl_os.os_numero = '$sua_os' OR tbl_os.sua_os  = '$sua_os')";
					}
				}
			}else{
				$conteudo = explode("-", $sua_os);
				$os_numero    = $conteudo[0];
				$os_sequencia = $conteudo[1];
				if(!ctype_digit($os_sequencia)){
					$sql .= " AND tbl_os.sua_os = '$sua_os' ";
				}else{
					if($login_fabrica ==1) { // HD 51334
						$sua_os2 = $sua_os;
						$sua_os = "000000" . trim ($sua_os);
						if(strlen ($sua_os) > 12 AND $login_fabrica == 1) {
							$sua_os = substr ($sua_os,strlen ($sua_os) - 7 , 7);
						}elseif(strlen ($sua_os) > 11 AND $login_fabrica == 1){#46900
							$sua_os = substr ($sua_os,strlen ($sua_os) - 6 , 6);
						}else{
							$sua_os = substr ($sua_os,strlen ($sua_os) - 5 , 5);
						}
						$sua_os = strtoupper ($sua_os);

						$sql .= "   AND (
									tbl_os.sua_os = '$sua_os' OR
									tbl_os.sua_os = '0$sua_os' OR
									tbl_os.sua_os = '00$sua_os' OR
									tbl_os.sua_os = '000$sua_os' OR
									tbl_os.sua_os = '0000$sua_os' OR
									tbl_os.sua_os = '00000$sua_os' OR
									tbl_os.sua_os = '000000$sua_os' OR
									tbl_os.sua_os = '0000000$sua_os' OR
									tbl_os.sua_os = '00000000$sua_os' OR
									tbl_os.sua_os = substr('$sua_os2',6,length('$sua_os2')) OR
									tbl_os.sua_os = substr('$sua_os2',7,length('$sua_os2')) 	";
						/* hd 4111 */
						for ($i=1;$i<=40;$i++) {
							$sql .= "OR tbl_os.sua_os = '$sua_os-$i' ";
						}
						$sql .= " OR 1=2) ";
					}else{
						$sql .= " AND tbl_os.os_numero = '$os_numero' AND tbl_os.os_sequencia = '$os_sequencia' ";
					}
				}
			}
		}

		if (strlen($os_off) > 0) {
			$sql .= " AND (tbl_os.sua_os_offline LIKE '$os_off%') ";
		}

		if (strlen($serie) > 0) {
#			$sql .= " AND UPPER(tbl_os.serie) = '$serie'"; Samuel 02-07-20009
			$sql .= " AND tbl_os.serie = '$serie'";
		}

		if (strlen($nf_compra) > 0) {
			$sql .= " AND tbl_os.nota_fiscal = '$nf_compra'";
		}

		if (strlen($consumidor_nome) > 0) {
			$consumidor_nome = strtoupper ($consumidor_nome);
			$sql .= " AND upper(tbl_os.consumidor_nome) LIKE upper('$consumidor_nome%')";
		}

		if (strlen($consumidor_cpf) > 0) {
			$sql .= " AND tbl_os.consumidor_cpf = '$consumidor_cpf'";
		}

		if (strlen($os_aberta) > 0) {
			$sql .= " AND tbl_os.os_fechada IS FALSE
					  AND tbl_os.excluida IS NOT TRUE";
		}
		#HD 115630---------
		if($login_fabrica==35){
			if (strlen($os_finalizada) > 0) {
				$sql .= " AND tbl_os.os_fechada IS TRUE
						  AND tbl_os.excluida IS NOT TRUE";
			}
		}
		#------------------
		if ($os_situacao == "APROVADA") {
			$sql .= " AND tbl_extrato.aprovado IS NOT NULL ";
		}
		if ($os_situacao == "PAGA") {
			$sql .= " AND tbl_extrato_financeiro.data_envio IS NOT NULL ";
		}

		if (strlen($revenda_cnpj) > 0) {
			$sql .= " AND tbl_os.revenda_cnpj LIKE '$revenda_cnpj%' ";
		}

		if (strlen($pais) > 0) {
			$sql .= " AND tbl_posto.pais ='$pais' ";
		}

		if ($login_fabrica == 45 AND strlen($regiao) > 0) {
			if ($regiao == 1) {
				$sql .= " AND tbl_posto_fabrica.contato_estado = 'SP'";
			}
			if ($regiao == 2) {
				$sql .= " AND tbl_posto_fabrica.contato_estado IN ('PE','PB','PI','MA','CE','RN','AP','RO','RR','AM','AC','MS','MT')";
			}
			if ($regiao == 3) {
				$sql .= " AND tbl_posto_fabrica.contato_estado IN ('RS','SC')";
			}
			if ($regiao == 4) {
				$sql .= " AND tbl_posto_fabrica.contato_estado IN ('MG','PA')";
			}
			if ($regiao == 5) {
				$sql .= " AND tbl_posto_fabrica.contato_estado IN ('ES','RJ','DF','GO','TO')";
			}
			if ($regiao == 6) {
				$sql .= " AND tbl_posto_fabrica.contato_estado IN ('BA','SE','AL','PR')";
/*				$sql .= " AND (substr(tbl_os.revenda_cnpj,0,9)::text = '59291534'
							OR substr(tbl_os.revenda_cnpj,0,9)::text = '16182834'
							OR substr(tbl_os.revenda_cnpj,0,9)::text = '33021260'
							OR substr(tbl_os.revenda_cnpj,0,9)::text = '32121766'
							OR substr(tbl_os.revenda_cnpj,0,9)::text = '61099834'
							OR substr(tbl_os.revenda_cnpj,0,9)::text = '26484731'
							OR substr(tbl_os.revenda_cnpj,0,9)::text = '64282601'
							OR substr(tbl_os.revenda_cnpj,0,9)::text = '89848543'
							OR substr(tbl_os.revenda_cnpj,0,9)::text = '04967111'
							OR substr(tbl_os.revenda_cnpj,0,9)::text = '04112118'
							OR substr(tbl_os.revenda_cnpj,0,9)::text = '04049497'
							OR substr(tbl_os.revenda_cnpj,0,9)::text = '22164990'
							OR substr(tbl_os.revenda_cnpj,0,9)::text = '24444127')
				";*/
			}
		}

		if($login_fabrica == 50 AND strlen($tipo_os) >0) { // HD 48198
			if($tipo_os=='REINCIDENTE'){
				$sql .=" AND tbl_os.os_reincidente IS TRUE ";
			}elseif($tipo_os=='MAIS_CINCO_DIAS'){
				$sql .=" AND CURRENT_DATE - tbl_os.data_abertura > 5
						 AND CURRENT_DATE - tbl_os.data_abertura < 10
						 AND tbl_os.data_fechamento IS NULL
						 AND tbl_os.excluida IS NOT TRUE ";
			}elseif($tipo_os=='MAIS_DEZ_DIAS'){
				$sql .=" AND CURRENT_DATE - tbl_os.data_abertura > 10
						 AND CURRENT_DATE - tbl_os.data_abertura < 20
						 AND tbl_os.data_fechamento IS NULL
						 AND tbl_os.excluida IS NOT TRUE ";
			}elseif($tipo_os=='MAIS_VINTE_DIAS'){
				$sql .=" AND CURRENT_DATE - tbl_os.data_abertura > 20
						 AND tbl_os.data_fechamento IS NULL
						 AND tbl_os.excluida IS NOT TRUE ";
			}elseif($tipo_os == 'EXCLUIDAS'){
				$sql .=" AND tbl_os.excluida IS TRUE ";
			}
		}

		if($login_fabrica == 45 AND strlen($tipo_os) >0) { // HD 62394 waldir
			if($tipo_os=='REINCIDENTE'){
				$sql .=" AND tbl_os.os_reincidente IS TRUE ";
			}
			elseif($tipo_os=='BOM'){
				$sql .=" AND CURRENT_DATE - tbl_os.data_abertura < 16
						 AND tbl_os.data_fechamento IS NULL
						 AND tbl_os.excluida IS NOT TRUE ";
			}
			elseif($tipo_os=='MEDIO'){
				$sql .=" AND CURRENT_DATE - tbl_os.data_abertura > 15
						 AND CURRENT_DATE - tbl_os.data_abertura < 26
						 AND tbl_os.data_fechamento IS NULL
						 AND tbl_os.excluida IS NOT TRUE ";
			}
			elseif($tipo_os=='RUIM'){
				$sql .=" AND CURRENT_DATE - tbl_os.data_abertura > 25
						 AND tbl_os.data_fechamento IS NULL
						 AND tbl_os.excluida IS NOT TRUE ";
			}
			elseif($tipo_os == 'EXCLUIDA'){
				$sql .=" AND tbl_os.excluida IS TRUE ";
			}
			
		}

		if ($login_fabrica == 7){
			$sql .= " ORDER BY tbl_os.data_abertura ASC, LPAD(tbl_os.sua_os,20,'0') ASC ";
		} elseif ($login_fabrica == 45){
			$sql .= " ORDER BY tbl_os.data_abertura DESC ";
		} else {
#			$sql .= " ORDER BY LPAD(tbl_os.sua_os,20,'0') DESC "; Sameul 02-07-2009
			$sql .= " ORDER BY tbl_os.sua_os DESC ";
		}

	$sqlT = str_replace ("\n"," ",$sql) ;
	$sqlT = str_replace ("\t"," ",$sqlT) ;
	$resT = @pg_exec ($con,"/* QUERY -> $sqlT  */");

	flush();

	
	##### PAGINAÇÃO - INÍCIO #####
	$sqlCount  = "SELECT count(*) FROM (";
	$sqlCount .= $sql;
	$sqlCount .= ") AS count";

	require "_class_paginacao.php";

	// definicoes de variaveis
	$max_links = 11;				// máximo de links à serem exibidos
	$max_res   = 50;				// máximo de resultados à serem exibidos por tela ou pagina
	$mult_pag  = new Mult_Pag();	// cria um novo objeto navbar
	$mult_pag->num_pesq_pag = $max_res; // define o número de pesquisas (detalhada ou não) por página

	$res = $mult_pag->executar($sql, $sqlCount, $con, "otimizada", "pgsql");

	##### PAGINAÇÃO - FIM #####

	$resultados = pg_numrows($res);

	if (pg_numrows($res) > 0) {
		##### LEGENDAS - INÍCIO #####
		echo "<div align='left' style='position: relative; left: 25'>";
		echo "<table border='0' cellspacing='0' cellpadding='0' align='center'>";
		if ($excluida == "t" ) {
			echo "<tr height='18'>";
			echo "<td width='18' bgcolor='#FFE1E1'>&nbsp;</td>";
			echo "<td align='left'><font size='1'><b>&nbsp; Excluídas do sistema</b></font></td>";
			echo "</tr>";
			echo "<tr height='3'><td colspan='2'></td></tr>";
		}
		if ($login_fabrica != 1) {
			echo "<tr height='18'>";
			echo "<td width='18' bgcolor='#D7FFE1'>&nbsp;</td>";
			echo "<td align='left'><font size='1'><b>&nbsp; Reincidências</b></font></td>";
			echo "</tr>";
			echo "<tr height='3'><td colspan='2'></td></tr>";
		}else{
			echo "<tr height='18'>";
			echo "<td width='18' bgcolor='#FFCC66'>&nbsp;</td>";
			echo "<td align='left'><font size='1'><b>&nbsp; OSs sem lancamento de itens há mais de 5 dias, efetue o lançamento</b></font></td>";
			echo "</tr>";
			echo "<tr height='3'><td colspan='2'></td></tr>";
			echo "<tr height='18'>";
			echo "<td width='18' bgcolor='#FF0000'>&nbsp;</td>";
			echo "<td align='left'><font size='1'><b>&nbsp; OSs que excederam o prazo limite de 30 dias para fechamento, informar \"Motivo\"</b></font></td>";
			echo "</tr>";
			echo "<tr height='3'><td colspan='2'></td></tr>";
		}
		if ($login_fabrica == 14) {
			echo "<tr height='18'>";
			echo "<td width='18' bgcolor='#91C8FF'>&nbsp;</td>";
			echo "<td align='left'><font size='1'><b>&nbsp; OSs abertas há mais de 3 dias sem data de fechamento</b></font></td>";
			echo "</tr>";
			echo "<tr height='3'><td colspan='2'></td></tr>";
			echo "<tr height='18'>";
			echo "<td width='18' bgcolor='#FF0000'>&nbsp;</td>";
			echo "<td align='left'><font size='1'><b>&nbsp; OSs abertas há mais de 5 dias sem data de fechamento</b></font></td>";
			echo "</tr>";
			echo "<tr height='3'><td colspan='2'></td></tr>";
			echo "<tr height='18'>";
			echo "<td width='18' bgcolor='#FFE1E1'>&nbsp;</td>";
			echo "<td align='left'><font size='1'><b>&nbsp; Excluídas do sistema</b></font></td>";
			echo "</tr>";
			echo "<tr height='3'><td colspan='2'></td></tr>";
		}else{
			if($login_fabrica==50){
				echo "<tr height='18'>";
				echo "<td width='18' bgcolor='#91C8FF'>&nbsp;</td>";
				echo "<td align='left'><font size='1'><b>&nbsp; OSs abertas há mais de 5 dias sem data de fechamento</b></font></td>";
				echo "</tr>";
				echo "<tr height='18'>";
				echo "<td width='18' bgcolor='#FF6633'>&nbsp;</td>";
				echo "<td align='left'><font size='1'><b>&nbsp; OSs abertas há mais de 10 dias sem data de fechamento</b></font></td>";
				echo "</tr>";
				echo "<tr height='18'>";
				echo "<td width='18' bgcolor='#FF0000'>&nbsp;</td>";
				echo "<td align='left'><font size='1'><b>&nbsp; OSs abertas há mais de 20 dias sem data de fechamento</b></font></td>";
				echo "</tr>";
				echo "<tr height='18'>";
				echo "<td width='18' bgcolor='#FFE1E1'>&nbsp;</td>";
				echo "<td align='left'><font size='1'><b>&nbsp; Excluídas do sistema</b></font></td>";
				echo "</tr>";
				echo "<tr height='3'><td colspan='2'></td></tr>";
			}else{
				if ($login_fabrica == 45) {
					echo "<tr height='18'>";
					echo "<td width='18' bgcolor='#1e85c7'>&nbsp;</td>";
					echo "<td align='left'><font size='1'><b>&nbsp; BOM (OSs abertas até 15 dias sem data de fechamento)</b></font></td>";
					echo "</tr>";
					echo "<tr height='18'>";
					echo "<td width='18' bgcolor='#FF6633'>&nbsp;</td>";
					echo "<td align='left'><font size='1'><b>&nbsp; MÉDIO (OSs abertas entre 15 dias e 25 dias sem data de fechamento)</b></font></td>";
					echo "</tr>";
					echo "<tr height='18'>";
					echo "<td width='18' bgcolor='#9512cc'>&nbsp;</td>";
					echo "<td align='left'><font size='1'><b>&nbsp; RUIM (OSs abertas a mais de 25 dias sem data de fechamento)</b></font></td>";
					echo "</tr>";
				}elseif($login_fabrica == 43){
					echo "<tr height='18'>";
					echo "<td width='18' bgcolor='#FF0033'>&nbsp;</td>";
					echo "<td align='left'><font size='1'><b>&nbsp; OSs abertas há mais de 10 dias sem data de fechamento</b></font></td>";
					echo "</tr>";

				} else {
					echo "<tr height='18'>";
					echo "<td width='18' bgcolor='#91C8FF'>&nbsp;</td>";
					echo "<td align='left'><font size='1'><b>&nbsp; OSs abertas há mais de 25 dias sem data de fechamento</b></font></td>";
					echo "</tr>";
				}
			}

			if($login_fabrica==35){
				echo "<tr height='3'><td colspan='2'></td></tr>";
				echo "<tr height='18'>";
				echo "<td width='18' bgcolor='#FF0000'>&nbsp;</td>";
				echo "<td align='left'><font size='1'><b>&nbsp;Excluídas do sistema</b></font></td>";
				echo "</tr>";
				echo "<tr height='3'><td colspan='2'></td></tr>";
			}

		}
		if($login_fabrica == 3 OR $login_fabrica==11 OR $login_fabrica==51){
			echo "<tr height='18'>";
			echo "<td width='18' bgcolor='#FFCCCC'>&nbsp;</td>";
			echo "<td align='left'><font size='1'><b>&nbsp; ";
			echo "OS com Intervenção da Fábrica. Aguardando Liberação";
			echo "</b></font></td>";
			echo "</tr>";
			echo "<tr height='3'><td colspan='2'></td></tr>";
			echo "<tr height='18'>";
			echo "<td width='18' bgcolor='#FFFF99'>&nbsp;</td>";
			echo "<td align='left'><font size='1'><b>&nbsp; ";
			echo "OS com Intervenção da Fábrica. Reparo na Fábrica";
			echo "</b></font></td>";
			echo "</tr>";
			echo "<tr height='3'><td colspan='2'></td></tr>";
			echo "<tr height='18'>";
			echo "<td width='18' bgcolor='#00EAEA'>&nbsp;</td>";
			echo "<td align='left'><font size='1'><b>&nbsp; ";
			echo "OS Liberada Pela Fábrica";
			echo "</b></font></td>";
			echo "</tr>";
		}
		if($login_fabrica == 3 OR $login_fabrica == 11 OR $login_fabrica==45){
			echo "<tr height='18'>";
			echo "<td width='18' bgcolor='#FF0000'>&nbsp;</td>";
			echo "<td align='left'><font size='1'><b>&nbsp; ";
			echo "OS Cancelada";
			echo "</b></font></td>";
			echo "</tr>";
			echo "<tr height='3'><td colspan='2'></td></tr>";
			echo "<tr height='18'>";
			echo "<td width='18' bgcolor='#CCCCFF'>&nbsp;</td>";
			echo "<td align='left'><font size='1'><b>&nbsp; ";
			echo "OS com Ressarcimento Financeiro";
			echo "</b></font></td>";
			echo "</tr>";
			echo "<tr height='3'><td colspan='2'></td></tr>";
		}
		if ($login_fabrica == 20) {
			echo "<tr height='18'>";
			echo "<td width='18' bgcolor='#CACACA'>&nbsp;</td>";
			echo "<td align='left'><font size='1'><b>&nbsp; OS Reprovada pelo Promotor</b></font></td>";
			echo "</tr>";
			echo "<tr height='3'><td colspan='2'></td></tr>";
		}

		echo "<tr height='18'>";
		echo "<td width='18' bgcolor='#FFCC66'>&nbsp;</td>";
		echo "<td align='left'><font size='1'><b>&nbsp; ";
		echo "OS com Troca de Produto";
		echo "</b></font></td>";
		echo "</tr>";

		if ($login_fabrica == 51) {
			echo "<tr height='18'>";
			echo "<td width='18' bgcolor='#CACACA'>&nbsp;</td>";
			echo "<td align='left'><font size='1'><b>&nbsp; OS Recusada do extrato</b></font></td>";
			echo "</tr>";
			echo "<tr height='3'><td colspan='2'></td></tr>";
		}

		echo "<tr height='3'><td colspan='2'></td></tr>";

		echo "</table>";
		echo "</div>";
		##### LEGENDAS - FIM #####

		echo "<br>";

		for ($i = 0 ; $i < pg_numrows($res) ; $i++) {
			if ($i % 50 == 0) {
				echo "</table>";
				flush();
				echo "<table border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#000000'  align='center' width='96%'>";
			}

			if ($i % 50 == 0) {
				$colspan = ($login_fabrica==7) ? 4 : (($login_fabrica==50 or $login_fabrica == 14 or $login_fabrica == 20) ? 5 : 2) ;

				echo "<tr class='Titulo' height='15'>";
				echo "<td>OS</td>";
				echo ($login_fabrica==19 OR $login_fabrica==10 OR $login_fabrica==1) ? "<td>OS OFF LINE</td>" : "";
				echo ($login_fabrica==30) ? "<td>OS Revendedor</td>" : "" ; #HD 117540;
				echo "<td>";
				echo ($login_fabrica==35) ? "PO#" : "SÉRIE";
				echo "</td>";
				echo "<td>AB</td>";
				echo ($login_fabrica==11) ? "<td>DP</td>" : ""; // HD 74587
				echo ($login_fabrica ==3 or $login_fabrica ==11 or $login_fabrica ==15 or $login_fabrica ==45) ? "<td><acronym title='Data de conserto do produto' style='cursor:help;'>DC</a></td>" : ""; //HD 14927
				echo "<td>FC</td>";
				echo "<td>POSTO</td>";
				echo ($login_fabrica==2)  ? "<td>CONSUMIDOR/REVENDA</td>" : "<td>CONSUMIDOR</td>";
				echo "<td>TELEFONE</td>";
				echo ($login_fabrica==2)  ? "<td>NF</td>" : "";
				echo ($login_fabrica==3)  ? "<td>MARCA</td>" : "";
				echo ($login_fabrica==11) ? "<td>REFERÊNCIA</td>" : "<td>PRODUTO</td>"; // hd 74587
				echo ($login_fabrica==45) ? "<td align='center'>RG PRODUTO</td>" : "";
				echo ($login_fabrica==19) ? "<td>Atendimento</td>" : "";
				echo ($login_fabrica==19) ? "<td>Nome do técnico</td>" : "";
				echo ($login_fabrica==1)  ? "<td>APARÊNCIA</td>" : "";//TAKASHI HD925
				echo "<td colspan='$colspan'>AÇÕES</td>";
				echo ($login_fabrica==7)  ? "<td colspan='$colspan'> <a href='javascript:selecionarTudo();' style='color:#FFFFFF'><img src='imagens/img_impressora.gif'></a></td>" : "";
				echo "</tr>";
			}

			$os                 = trim(pg_result($res,$i,os));
			$sua_os             = trim(pg_result($res,$i,sua_os));
			$nota_fiscal        = trim(pg_result($res,$i,nota_fiscal));
			$os_numero          = trim(pg_result($res,$i,os_numero));
			$digitacao          = trim(pg_result($res,$i,digitacao));
			$abertura           = trim(pg_result($res,$i,abertura));
			$fechamento         = trim(pg_result($res,$i,fechamento));
			$finalizada         = trim(pg_result($res,$i,finalizada));
			$serie              = trim(pg_result($res,$i,serie));
			$excluida           = trim(pg_result($res,$i,excluida));
			$motivo_atraso      = trim(pg_result($res,$i,motivo_atraso));
			$tipo_os_cortesia   = trim(pg_result($res,$i,tipo_os_cortesia));
			$consumidor_revenda = trim(pg_result($res,$i,consumidor_revenda));
			$consumidor_nome    = trim(pg_result($res,$i,consumidor_nome));
			$consumidor_fone    = trim(pg_result($res,$i,consumidor_fone));
			$revenda_nome       = trim(pg_result($res,$i,revenda_nome));
			$codigo_posto       = trim(pg_result($res,$i,codigo_posto));
			$posto_nome         = trim(pg_result($res,$i,posto_nome));
			$impressa           = trim(pg_result($res,$i,impressa));
			$extrato            = trim(pg_result($res,$i,extrato));
			$os_reincidente     = trim(pg_result($res,$i,os_reincidente));
			$produto_referencia = trim(pg_result($res,$i,produto_referencia));
			$produto_descricao  = trim(pg_result($res,$i,produto_descricao));
			$produto_voltagem   = trim(pg_result($res,$i,produto_voltagem));
			$tipo_atendimento   = trim(pg_result($res,$i,tipo_atendimento));
			$tecnico_nome       = trim(pg_result($res,$i,tecnico_nome));
			$nome_atendimento   = trim(pg_result($res,$i,descricao));
			$sua_os_offline     = trim(pg_result($res,$i,sua_os_offline));
			$reincidencia       = trim(pg_result($res,$i,reincidencia));
			$rg_produto         = trim(pg_result($res,$i,rg_produto));
			$aparencia_produto  = trim(pg_result($res,$i,aparencia_produto));//TAKASHI HD925
			$status_os          = trim(pg_result($res,$i,status_os)); //fabio
			#117540
			if($login_fabrica==30){
				$os_posto_x   = trim(pg_result($res,$i,os_posto));
			}
			if($login_fabrica==3){
				$marca     = trim(pg_result($res,$i,marca));
				$marca_nome     = trim(pg_result($res,$i,marca_nome));
			}
			//HD 14927
			if($login_fabrica == 11 or $login_fabrica == 45 or $login_fabrica == 15 or $login_fabrica == 3 ){
				$data_conserto=trim(pg_result($res,$i,data_conserto));
			}

			$cor   = ($i % 2 == 0) ? "#F1F4FA" : "#F7F5F0";
			$botao = ($i % 2 == 0) ? "azul" : "amarelo";

			/*IGOR - HD: 44202 - 22/10/2008 */
			if($login_fabrica==3){
				$sqlI = "SELECT  status_os
						FROM    tbl_os_status
						WHERE   os = $os
						AND status_os IN (120, 122, 123, 126, 140, 141, 142, 143)
						ORDER BY data DESC LIMIT 1";
				$resI = pg_exec ($con,$sqlI);
				if (pg_numrows ($resI) > 0){
					$status_os = trim(pg_result($resI,0,status_os));
					if ($status_os == 126 || $status_os == 143) {
						$cor = "#FF0000";
						#$excluida = "t"; HD 56464
					}
				}
			}


			if ($login_fabrica==3 OR $login_fabrica==11 OR $login_fabrica==51){
				if ($status_os=="62") {
					$cor= ($login_fabrica==51) ? "#FFCCCC" : "#E6E6FA"; //HD 46730
				}
				if ($status_os=="72")  $cor="#FFCCCC";
				if ($status_os=="87")  $cor="#FFCCCC";
				if ($status_os=="116") $cor="#FFCCCC";
				if ($status_os=="120" || $status_os=="140")  $cor="#FFCCCC"; //HD: 44202
				if ($status_os=="122" || $status_os=="141")  $cor="#FFCCCC"; //HD: 44202
				if (($status_os=="64" OR $status_os=="73"  OR $status_os=="88" OR $status_os=="117") && strlen($fechamento)==0) {
					$cor="#00EAEA";
				}
				if ($status_os=="65") $cor="#FFFF99";
			}
			##### VERIFICAÇÕES PARA OS CRITÉRIOS DA LEGENDA - INÍCIO #####
			if ($reincidencia =='t') $cor = "#D7FFE1";
			if ($excluida == "t")    $cor = "#FF0000";
			if ($login_fabrica==20 AND $status_os == "94" AND $excluida == "t") {
				$cor = "#CACACA";
			}

			// OSs abertas há mais de 25 dias sem data de fechamento
			if (strlen($fechamento) == 0 && $excluida != "t" && $login_fabrica != 14) {
				$aux_abertura = fnc_formata_data_pg($abertura);

				$sqlX = "SELECT TO_CHAR($aux_abertura::date + INTERVAL '25 days','YYYY-MM-DD')";
				$resX = pg_exec ($con,$sqlX);
				$aux_consulta = pg_result($resX,0,0);

				$sqlX = "SELECT TO_CHAR(current_date,'YYYY-MM-DD')";
				$resX = pg_exec ($con,$sqlX);
				$aux_atual = pg_result ($resX,0,0);

				if ($aux_consulta < $aux_atual && strlen($fechamento) == 0) $cor = "#91C8FF";
			}

			// OSs abertas há mais de 10 dias sem data de fechamento - Nova
			if (strlen($fechamento) == 0 && $excluida != "t" && $login_fabrica == 43) {
				$aux_abertura = fnc_formata_data_pg($abertura);

				$sqlX = "SELECT TO_CHAR($aux_abertura::date + INTERVAL '10 days','YYYY-MM-DD')";
				$resX = pg_exec ($con,$sqlX);
				$aux_consulta = pg_result($resX,0,0);

				$sqlX = "SELECT TO_CHAR(current_date,'YYYY-MM-DD')";
				$resX = pg_exec ($con,$sqlX);
				$aux_atual = pg_result ($resX,0,0);

				if ($aux_consulta < $aux_atual && strlen($fechamento) == 0) $cor = "#FF0033";
			}

			// CONDIÇÕES PARA INTELBRÁS - INÍCIO
			if (strlen($fechamento) == 0 && $excluida != "t" && $login_fabrica == 14) {
				$aux_abertura = fnc_formata_data_pg($abertura);

				$sqlX = "SELECT TO_CHAR($aux_abertura::date + INTERVAL '3 days','YYYY-MM-DD')";
				$resX = pg_exec($con,$sqlX);
				$aux_consulta = pg_result($resX,0,0);

				$sqlX = "SELECT TO_CHAR(current_date,'YYYY-MM-DD')";
				$resX = pg_exec($con,$sqlX);
				$aux_atual = pg_result($resX,0,0);

				if ($aux_consulta < $aux_atual && strlen($fechamento) == 0) $cor = "#91C8FF";

				$sqlX = "SELECT TO_CHAR($aux_abertura::date + INTERVAL '5 days','YYYY-MM-DD')";
				$resX = pg_exec($con,$sqlX);
				$aux_consulta = pg_result($resX,0,0);

				$sqlX = "SELECT TO_CHAR(current_date,'YYYY-MM-DD')";
				$resX = pg_exec ($con,$sqlX);
				$aux_atual = pg_result ($resX,0,0);

				if ($aux_consulta < $aux_atual && strlen($fechamento) == 0) $cor = "#FF0000";
			}
			// CONDIÇÕES PARA INTELBRÁS - FIM

			// CONDIÇÕES PARA COLORMAQ - INÍCIO
			if (strlen($fechamento) == 0 && $excluida != "t" && $login_fabrica == 50) {
				$aux_abertura = fnc_formata_data_pg($abertura);

				$sqlX = "SELECT TO_CHAR($aux_abertura::date + INTERVAL '5 days','YYYY-MM-DD')";
				$resX = pg_exec($con,$sqlX);
				$aux_consulta = pg_result($resX,0,0);

				$sqlX = "SELECT TO_CHAR(current_date,'YYYY-MM-DD')";
				$resX = pg_exec($con,$sqlX);
				$aux_atual = pg_result($resX,0,0);

				if ($aux_consulta < $aux_atual && strlen($fechamento) == 0) $cor = "#91C8FF";

				$sqlX = "SELECT TO_CHAR($aux_abertura::date + INTERVAL '10 days','YYYY-MM-DD')";
				$resX = pg_exec($con,$sqlX);
				$aux_consulta = pg_result($resX,0,0);

				$sqlX = "SELECT TO_CHAR(current_date,'YYYY-MM-DD')";
				$resX = pg_exec ($con,$sqlX);
				$aux_atual = pg_result ($resX,0,0);

				if ($aux_consulta < $aux_atual && strlen($fechamento) == 0) $cor = "#FF6633";

				$sqlX = "SELECT TO_CHAR($aux_abertura::date + INTERVAL '20 days','YYYY-MM-DD')";
				$resX = pg_exec($con,$sqlX);
				$aux_consulta = pg_result($resX,0,0);

				$sqlX = "SELECT TO_CHAR(current_date,'YYYY-MM-DD')";
				$resX = pg_exec ($con,$sqlX);
				$aux_atual = pg_result ($resX,0,0);

				if ($aux_consulta < $aux_atual && strlen($fechamento) == 0) $cor = "#FF0000";
			}

			if($excluida=='t' AND ($login_fabrica==50 or $login_fabrica ==14)){//HD 37007 5/9/2008
				$cor = "#FFE1E1";
			}
			// CONDIÇÕES PARA COLORMAQ - FIM

			// CONDIÇÕES PARA NKS - INÍCIO
			if (strlen($fechamento) == 0 && $excluida != "t" && $login_fabrica == 45) {
				$aux_abertura = fnc_formata_data_pg($abertura);

				$sqlX = "SELECT TO_CHAR(current_date - INTERVAL '15 days','YYYY-MM-DD')";
				$resX = pg_exec($con,$sqlX);
				$aux_consulta = pg_result($resX,0,0);
				$sqlX = "SELECT TO_CHAR($aux_abertura::date,'YYYY-MM-DD')";
				$resX = pg_exec($con,$sqlX);
				$aux_consulta2 = pg_result($resX,0,0);

				if ($aux_consulta < $aux_consulta2 && strlen($fechamento) == 0) $cor = "#1e85c7";

				$sqlX = "SELECT TO_CHAR(current_date - INTERVAL '15 days','YYYY-MM-DD')";
				$resX = pg_exec($con,$sqlX);
				$aux_consulta = pg_result($resX,0,0);

				$sqlX = "SELECT TO_CHAR(current_date - INTERVAL '25 days','YYYY-MM-DD')";
				$resX = pg_exec($con,$sqlX);
				$aux_consulta2 = pg_result($resX,0,0);

				$sqlX = "SELECT TO_CHAR($aux_abertura::date,'YYYY-MM-DD')";
				$resX = pg_exec ($con,$sqlX);
				$aux_consulta3 = pg_result ($resX,0,0);

				if ($aux_consulta2 <= $aux_consulta3 AND $aux_consulta3 <= $aux_consulta && strlen($fechamento) == 0) $cor = "#FF6633";

				$sqlX = "SELECT TO_CHAR($aux_abertura::date + INTERVAL '25 days','YYYY-MM-DD')";
				$resX = pg_exec($con,$sqlX);
				$aux_consulta = pg_result($resX,0,0);

				$sqlX = "SELECT TO_CHAR(current_date,'YYYY-MM-DD')";
				$resX = pg_exec ($con,$sqlX);
				$aux_consulta2 = pg_result ($resX,0,0);

				if ($aux_consulta < $aux_consulta2 && strlen($fechamento) == 0) $cor = "#9512cc";
			}
			// CONDIÇÕES PARA NKS - FIM

			// CONDIÇÕES PARA BLACK & DECKER - INÍCIO
			// Verifica se não possui itens com 5 dias de lançamento
			if ($login_fabrica == 1) {
				$aux_abertura = fnc_formata_data_pg($abertura);

				$sqlX = "SELECT TO_CHAR(current_date + INTERVAL '5 days','YYYY-MM-DD')";
				$resX = pg_exec($con,$sqlX);
				$data_hj_mais_5 = pg_result($resX,0,0);

				$sqlX = "SELECT TO_CHAR($aux_abertura::date + INTERVAL '5 days','YYYY-MM-DD')";
				$resX = pg_exec ($con,$sqlX);
				$aux_consulta = pg_result($resX,0,0);

				$sql = "SELECT COUNT(tbl_os_item.*) AS total_item
						FROM tbl_os_item
						JOIN tbl_os_produto ON tbl_os_produto.os_produto = tbl_os_item.os_produto
						JOIN tbl_os         ON tbl_os.os                 = tbl_os_produto.os
						WHERE tbl_os.os = $os
						AND   tbl_os.data_abertura::date >= '$aux_consulta'";
				$resItem = pg_exec($con,$sql);

				$itens = pg_result($resItem,0,total_item);

				if ($itens == 0 && $aux_consulta > $data_hj_mais_5) $cor = "#FFCC66";

				$mostra_motivo = 2;
			}

			// Verifica se está sem fechamento há 20 dias ou mais da data de abertura
			if (strlen($fechamento) == 0 && $mostra_motivo == 2 && $login_fabrica == 1) {
				$aux_abertura = fnc_formata_data_pg($abertura);

				$sqlX = "SELECT TO_CHAR($aux_abertura::date + INTERVAL '20 days','YYYY-MM-DD')";
				$resX = pg_exec($con,$sqlX);
				$aux_consulta = pg_result($resX,0,0);

				$sqlX = "SELECT TO_CHAR(current_date,'YYYY-MM-DD')";
				$resX = pg_exec($con,$sqlX);
				$aux_atual = pg_result($resX,0,0);

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
				$resX = pg_exec($con,$sqlX);
				$aux_consulta = pg_result($resX,0,0);

				$sqlX = "SELECT TO_CHAR(current_date,'YYYY-MM-DD')";
				$resX = pg_exec($con,$sqlX);
				$aux_atual = pg_result($resX,0,0);

				if ($consumidor_revenda != "R"){
					if ($aux_consulta < $aux_atual && strlen($fechamento) == 0) {
						$mostra_motivo = 1;
						$cor = "#FF0000";
					}
				}
			}

			$sqlX = "SELECT os_troca,ressarcimento FROM tbl_os_troca WHERE os = $os";
			$resX = pg_exec($con,$sqlX);
			if(pg_numrows($resX)==1){
				$cor = (pg_result($resX,0,ressarcimento)=='t') ? "#CCCCFF" : "#FFCC66";
			}
			// CONDIÇÕES PARA BLACK & DECKER - FIM

			// Gama
			if ($login_fabrica==51){ // HD 65821
				$sqlX = "SELECT status_os,os FROM tbl_os JOIN tbl_os_status USING(os) WHERE os = $os AND status_os = 13";
				$resX = pg_exec($con,$sqlX);
				if(pg_numrows($resX)> 0){
					$cor = "#CACACA";
				}
			}

			##### VERIFICAÇÕES PARA OS CRITÉRIOS DA LEGENDA - FIM #####

			if (strlen($sua_os) == 0) $sua_os = $os;
			if ($login_fabrica == 1) $sua_os = "<a href='etiqueta_print.php?os=$os' target='_blank'>" . $codigo_posto.$sua_os . "</a>";

			echo "<tr class='Conteudo' height='15' bgcolor='$cor' align='left'>";
			echo "<td nowrap>" . $sua_os . "</td>";
			echo ($login_fabrica==19 OR $login_fabrica==10 OR $login_fabrica==1) ? "<td nowrap>" . $sua_os_offline . "</td>" : "";
			#117540
			if($login_fabrica ==30){
				if(strlen($os_posto_x)<=0)$os_posto_x = "-";
				echo "<td nowrap align='center'>" . $os_posto_x . "</td>";
			}
			echo "<td nowrap>" . $serie . "</td>";
			echo "<td nowrap ><acronym title='Data Abertura: $abertura' style='cursor: help;'>" . substr($abertura,0,5) . "</acronym></td>";
			if ($login_fabrica ==11) { // HD 74587
				$sql_p = " SELECT to_char(tbl_pedido.data,'DD/MM/YYYY') as data_pedido
							FROM tbl_os_produto
							JOIN tbl_os_item USING(os_produto)
							JOIN tbl_pedido  USING(pedido)
							WHERE tbl_os_produto.os = $os
							AND   tbl_pedido.fabrica = $login_fabrica
							ORDER BY tbl_pedido.pedido ASC LIMIT 1 ";
				$res_p = @pg_exec($con,$sql_p);
				echo "<td nowrap >";
				if (pg_numrows($res_p) > 0) {
					$data_pedido = pg_result($res_p,0,data_pedido);
					echo "<acronym title='Data Pedido: $data_pedido' style='cursor: help;'>" . substr($data_pedido,0,5) . "</acronym>";
				}
				echo "</td>";
			}
			//HD 14927
			if($login_fabrica ==3 or $login_fabrica ==11 or $login_fabrica ==15 or $login_fabrica ==45){
				echo "<td nowrap ><acronym title='Data do Conserto: $data_conserto' style='cursor: help;'>" . substr($data_conserto,0,5) . "</acronym></td>";
			}
			$aux_fechamento = ($login_fabrica == 1) ? $finalizada : $fechamento;
			echo "<td nowrap><acronym title='Data Fechamento: $aux_fechamento' style='cursor: help;'>" . substr($aux_fechamento,0,5) . "</acronym></td>";
			echo "<td nowrap><acronym title='Posto: $codigo_posto - $posto_nome' style='cursor: help;'>" . substr($posto_nome,0,15) . "</acronym></td>";
			/*HD: 101036*/
			if ($login_fabrica==2 and $consumidor_revenda=="R" and $consumidor_nome==''){
				echo "<td nowrap><acronym title='Revenda: $revenda_nome' style='cursor: help;'>" . substr($revenda_nome,0,15) . "</acronym></td>";
			}else{
				echo "<td nowrap><acronym title='Consumidor: $consumidor_nome' style='cursor: help;'>" . substr($consumidor_nome,0,15) . "</acronym></td>";
			}

			echo "<td nowrap><acronym title='Telefone: $consumidor_fone' style='cursor: help;'>" .
				$consumidor_fone. "</acronym></td>";
			
			/*HD: 101036*/
			echo ($login_fabrica==2) ? "<td nowrap><acronym title='NF: $nota_fiscal' style='cursor: help;'>$nota_fiscal</acronym></td>" : "";
			echo ($login_fabrica==3) ? "<td nowrap>$marca_nome</td>" : "";//TAKASHI HD925

			$produto = ($login_fabrica ==11) ? $produto_referencia : $produto_referencia . " - " . $produto_descricao; # hd 74587

			echo "<td nowrap><acronym title='Referência: $produto_referencia \nDescrição: $produto_descricao \nVoltagem: $produto_voltagem' style='cursor: help;'>" . substr($produto,0,20) . "</acronym></td>";
			echo ($login_fabrica==45) ? "<td align='center'>$rg_produto</td>" : "";
			echo ($login_fabrica==19) ? "<td>$tipo_atendimento $nome-atendimento</td>" : "";
			echo ($login_fabrica==19) ? "<td>$tecnico_nome</td>" : "";
			echo ($login_fabrica==1) ? "<td>$aparencia_produto</td>" : "";//TAKASHI HD925
			echo "<td width='60' align='center'>";
 			if($excluida <>'t'){
				if ($login_fabrica==1 AND ($tipo_atendimento==17 OR $tipo_atendimento==18)){
					echo "<a href='os_cadastro_troca.php?os=$os' target='_blank'><img border='0' src='imagens_admin/btn_alterar_$botao.gif'></a>";
				}else{
					echo "<a href='os_offline_cadastro_britania.php?os=$os' target='_blank'><img border='0' src='imagens_admin/btn_alterar_$botao.gif'></a>";
				}
			}
			echo "</td>\n";
			echo "<td width='60' align='center'>";
			echo "<a href='os_press.php?os=$os' target='_blank'><img border='0' src='imagens/btn_consultar_$botao.gif'></a>";
			echo "</td>\n";

			if (($login_fabrica==50 and ($excluida <> 't')) or ($login_fabrica == 14 and $excluida <> 't') or $login_fabrica==20) {
				if (strlen($fechamento) == 0 and $login_fabrica <> 20) {
					echo "<td><a href='os_item.php?os=$os' target='_blank'>";
						if($sistema_lingua == "ES"){
							echo "<img id='lancar_$i' border='0' src='imagens/btn_lanzar.gif'>";
						}else{
							// $data_conserto > "03/11/2008" HD 50435
							$xdata_conserto = fnc_formata_data_pg($data_conserto);

							$sqlDC = "SELECT $xdata_conserto::date > '2008-11-03'::date AS data_anterior";
							$resDC = pg_exec($con, $sqlDC);
							if(pg_numrows($resDC)>0) $data_anterior = pg_result($resDC, 0, 0);

							echo ($login_fabrica==11 AND strlen($data_conserto)>0 AND $data_anterior == 't') ? "" : "<img id='lancar_$i' border='0' src='imagens/btn_lanca.gif'>";
						}
						echo "</a></td>";
				}
				echo ((strlen($fechamento) == 0) or ($login_fabrica == 20)) ? "<td><a href=\"javascript: if (confirm('Deseja realmente excluir a os $sua_os ?') == true) { window.location='$PHP_SELF?excluir=$os'; }\"><img id='excluir_$i' border='0' src='imagens/btn_excluir.gif'></a></td>" : "<td></td>";
				if (strlen($fechamento) == 0 AND $status_os!="62" && $status_os!="65" && $status_os!="72" && $status_os!="87" && $status_os!="116" && $status_os!="120" && $status_os!="122" && $status_os!="126" && $status_os!="140" && $status_os!="141" && $status_os!="143" and $login_fabrica <> 20) {
					echo "<td><img id='sinal_$i' border='0' src='/assist/imagens/btn_fecha.gif' onclick=\"javascript: if (confirm('Caso a data da entrega do produto para o consumidor nao seja hoje, utilize a opcao de fechamento de os para informar a data correta! confirma o fechamento da os $sua_os com a data de hoje?') == true) { fechaOS ($os,sinal_$i,excluir_$i, lancar_$i) ; }\"></td>";
				}
			}

			if($login_fabrica==7){//HD 31598 48441
				echo "<td width='60' align='center'>";
				echo "<a href='os_item.php?os=$os' target='_blank'><img border='0' src='imagens/btn_lanca_$botao.gif'></a>";
				echo "</td>\n";

				echo "<td width='60' align='center'>";
				echo "<a href='os_transferencia_filizola.php?sua_os=$sua_os&posto_codigo_origem=$codigo_posto&posto_nome_origem=$posto_nome' target='_blank'><img border='0' src='imagens/btn_transferir_$botao.gif'></a>";
				echo "</td>\n";
				echo "<td width='60' align='center'>";
				echo ($consumidor_revenda=="R") ? "<a href='os_print_manutencao.php?os_manutencao=$os_numero' target='_blank'>" : "<a href='os_print.php?os=$os' target='_blank'>";//HD 80470
				echo "<img border='0' src='imagens/btn_imprimir_$botao.gif'></a></td>\n";
				echo "<td width='60' align='center'>";
				echo "<input name='imprimir_$i' type='checkbox' id='imprimir' rel='imprimir' value='".$os."' />";
				echo "</td>\n";
			}
			echo "</tr>";
		}
		if ($login_fabrica == 7) {
			echo "<tr>";
			echo "<td colspan='11'>";
			echo "";
			echo "</td>";
			echo "<td colspan='2'>";
			echo "<a href='javascript:imprimirSelecionados()' style='font-size:10px'>Imprime Selecionados</a>";
			echo "</td>";
			echo "</tr>";
		}
		echo "</table>";
	}

	##### PAGINAÇÃO - INÍCIO #####
	echo "<br>";
	echo "<div>";

	if($pagina < $max_links) $paginacao = pagina + 1;
	else                     $paginacao = pagina;

	// pega todos os links e define que 'Próxima' e 'Anterior' serão exibidos como texto plano
	$todos_links		= $mult_pag->Construir_Links("strings", "sim");

	// função que limita a quantidade de links no rodape
	$links_limitados	= $mult_pag->Mostrar_Parte($todos_links, $coluna, $max_links);

	for ($n = 0; $n < count($links_limitados); $n++) {
		echo "<font color='#DDDDDD'>".$links_limitados[$n]."</font>&nbsp;&nbsp;";
	}

	echo "</div>";

	$resultado_inicial = ($pagina * $max_res) + 1;
	$resultado_final   = $max_res + ( $pagina * $max_res);
	$registros         = $mult_pag->Retorna_Resultado();

	$valor_pagina   = $pagina + 1;
	$numero_paginas = intval(($registros / $max_res) + 1);

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
	echo "<br><h1>Resultado: $resultados registro(s).</h1>";
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
	$consumidor_fone = trim (strtoupper ($_POST['consumidor_fone']));
	if (strlen($consumidor_fone)==0) $consumidor_fone = trim(strtoupper($_GET['consumidor_fone']));
	$os_situacao     = trim (strtoupper ($_POST['os_situacao']));
	if (strlen($os_situacao)==0) $os_situacao = trim(strtoupper($_GET['os_situacao']));
?>

<form name="frm_consulta" method="post" action="<?echo $PHP_SELF?>">
<input type="hidden" name="acao">

<table width="400" align="center" border="0" cellspacing="0" cellpadding="2">
	<tr class="Titulo" height="30">
		<td align="center">Selecione os parâmetros para a pesquisa.</td>
	</tr>
</table>

<table width="400" align="center" border="0" cellspacing="0" cellpadding="2">
	<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
		<td>Número da OS</td>
		<td>
			<? echo ($login_fabrica==35) ? "PO#" : "Número de Série"; ?>
		</td>
		<td>NF. Compra</td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
		<td><input type="text" name="sua_os"    size="10" value="<?echo $sua_os?>"    class="frm"></td>
		<td><input type="text" name="serie"     size="10" value="<?echo $serie?>"     class="frm"></td>
		<td><input type="text" name="nf_compra" size="10" value="<?echo $nf_compra?>" class="frm"></td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
		<td>CPF Consumidor</td>
		<td>
		<? echo ($login_fabrica==45) ? "RG do Produto" : (($login_fabrica==30) ? "OS Revendedor" : ""); ?>
		</td>
		<td></td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
		<td><input type="text" name="consumidor_cpf" size="17" value="<?echo $consumidor_cpf?>" class="frm"></td>
		<td>
		<?if($login_fabrica==45) {?>
		<input class="frm" type="text" name="rg_produto" size="15" maxlength="20" value="<? echo $_POST['rg_produto'] ?>" >
		<? }elseif($login_fabrica==30) { ?>
		<input class="frm" type="text" name="os_posto" size="15" maxlength="20" value="<? echo $_POST['os_posto'] ?>" >
		<?}?>
		</td>
		<td></td>
	</tr>
<?if($login_fabrica==45) {?>
	<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
		<td colspan=3>Status</td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
		<td colspan='3'>
			<select name='tipo_os' id='tipo_os' style='font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;font-size: 9px;'>
				<option>TODAS AS OPÇÕES</option>
				<option value='REINCIDENTE' <? if ($tipo_os == 'REINCIDENTE') echo " SELECTED "; ?>>Reincidências</option>
				<option value='BOM' <? if ($tipo_os == 'BOM') echo " SELECTED "; ?>>BOM (OSs abertas até 15 dias sem data de fechamento)</option>
				<option value='MEDIO' <? if ($tipo_os == 'MEDIO') echo " SELECTED "; ?>>MÉDIO (OSs abertas entre 15 dias e 25 dias sem data de fechamento)</option>
				<option value='RUIM' <? if ($tipo_os == 'RUIM') echo " SELECTED "; ?>>RUIM (OSs abertas a mais de 25 dias sem data de fechamento)</option>
				<option value='EXCLUIDA' <? if ($tipo_os == 'EXCLUIDA') echo " SELECTED "; ?>>OS Cancelada </option>
				<option value='RESSARCIMENTO' <? if ($tipo_os == 'RESSARCIMENTO') echo " SELECTED "; ?>>OS com Ressarcimento Financeiro</option>
				<option value='TROCA' <? if ($tipo_os == 'TROCA') echo " SELECTED "; ?>>OS com Troca de Produto</option>
			</select>
		</td>
	</tr>
<?}?>
	<tr class="Conteudo" bgcolor="#D9E2EF">
		<td colspan='3' align='center'><br><input type="submit" name="btn_acao" value="Pesquisar"></td>
	</tr>
</table>





<!-- CONSULTA OS OFF LINE -->
<?if($login_fabrica==19 OR $login_fabrica==10){?>
	<table width="400" align="center" border="0" cellspacing="0" cellpadding="2">
		<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
			<td colspan='2'> <hr> </td>
		</tr>
		<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
			<td colspan='2'> Consulta OS Off Line</td>
		</tr>
		<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
			<td colspan='2'>OS Off Line
			</td>
		</tr>
		<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
			<td colspan='2'><input type="text" name="os_off" size="10" value="" class="frm">
			</td>
		</tr>
		<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
			<td>Posto</td>
			<td>Nome do Posto</td>
		</tr>
		<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
			<td>
				<input type="text" name="codigo_posto_off" id="codigo_posto_off" size="8" <? if ($login_fabrica == 5) { ?> onblur="javascript: fnc_pesquisa_posto (document.frm_consulta.codigo_posto_off, document.frm_consulta.posto_nome_off, 'codigo');" <? } ?> value="<? echo $codigo_posto_off ?>" class="frm">
				<img border="0" src="imagens/lupa.png" style="cursor: hand;" align="absmiddle" alt="Clique aqui para pesquisar postos pelo código" onclick="javascript: fnc_pesquisa_posto (document.frm_consulta.codigo_posto_off, document.frm_consulta.posto_nome_off, 'codigo')">
			</td>
			<td>
				<input type="text" name="posto_nome_off" id="posto_nome_off" size="30" <? if ($login_fabrica == 5) { ?> onblur="javascript: fnc_pesquisa_posto (document.frm_consulta.codigo_posto_off, document.frm_consulta.posto_nome_off, 'nome');" <? } ?> value="<?echo $posto_nome_off ?>" class="frm">
				<img border="0" src="imagens/lupa.png" style="cursor: hand;" align="absmiddle" alt="Clique aqui para pesquisar postos pelo código" onclick="javascript: fnc_pesquisa_posto (document.frm_consulta.codigo_posto_off, document.frm_consulta.posto_nome_off, 'nome')">
			</td>
		</tr>
		<tr class="Conteudo" bgcolor="#D9E2EF" align='center'>
			<td colspan='3' align='center'>
				<input type="submit" name="btn_acao" value="Pesquisar">
			</td>
		</tr>
	</table>
<?}?>

<!--fim consulta off line -->







<table width="400" align="center" border="0" cellspacing="0" cellpadding="2">
	<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
		<td colspan='2'> <hr> </td>
	</tr>

	<? if($login_fabrica==7){ ?>
		<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
			<td><acronym title='Consulta através da data de Digitação da OS' style='cursor: help;'>Data Inicial</acronym></td>
			<td><acronym title='Consulta através da data de Digitação da OS' style='cursor: help;'>Data Final</acronym></td>
		</tr>
		<tr class="Conteudo" bgcolor="#D9E2EF" valign='top'>
			<td>
				<input type="text" name="data_inicial" id="data_inicial" size="13" maxlength="10" value="<? echo (strlen($data_inicial) > 0) ? substr($data_inicial,0,10) : "dd/mm/aaaa"; ?>" onClick="javascript: if (this.value == 'dd/mm/aaaa') this.value='';" class="frm">
			</td>
			<td>
				<input type="text" name="data_final" id="data_final" size="13" maxlength="10" value="<? if (strlen($data_final) > 0) echo substr($data_final,0,10); else echo "dd/mm/aaaa"; ?>" onClick="javascript: if (this.value == 'dd/mm/aaaa') this.value='';" class="frm">
				&nbsp;Apenas OS em aberto <input type='checkbox' name='os_aberta' value='1' <? if (strlen ($os_aberta) > 0 ) echo " checked " ?> >
			</td>
		</tr>
	<? }else{ ?>
		<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
			<td> * Mês</td>
			<td> * Ano</td>
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
				for ($i = 2003 ; $i <= date("Y") ; $i++) {
					echo "<option value='$i'";
					if ($ano == $i) echo " selected";
					echo ">$i</option>";
				}
				?>
				</select>
				<script>
					//HD 115630-----
					function clika_a(){
						if ( document.getElementById('os_aberta').checked == true ) {
							document.getElementById('os_aberta').checked = false
						}
					}
					function clika_b(){
						if ( document.getElementById('os_finalizada').checked == true ) {
							document.getElementById('os_finalizada').checked = false
						}
					}
					//------------
				</script>
				&nbsp;&nbsp;&nbsp;Apenas OS em aberto <input type='checkbox' id='os_aberta' name='os_aberta' value='1' <? if (strlen ($os_aberta) > 0 ) echo " checked " ?> <?if($login_fabrica==35)echo "onClick='clika_b();'";?>>
			</td>
		</tr>
		<? if($login_fabrica==35){ #HD 115630------------------- ?>
			<tr class="Conteudo" bgcolor="#D9E2EF" align='right'>
				<td>
					&nbsp;
				</td>
				<td>
					Apenas OS Fechada <input type='checkbox' id='os_finalizada' name='os_finalizada' value='1' <? if (strlen ($os_finalizada) > 0 ) echo " checked " ?> onClick="clika_a();">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
				</td>
			</tr>
		<? } ?>
	<? } ?>
	<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
		<td>Posto</td>
		<td>Nome do Posto</td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
		<td>
			<input type="text" name="codigo_posto" id="codigo_posto" size="8" <? if ($login_fabrica == 5) { ?> onblur="javascript: fnc_pesquisa_posto (document.frm_consulta.codigo_posto, document.frm_consulta.posto_nome, 'codigo');" <? } ?> value="<? echo $codigo_posto ?>" class="frm">
			<img border="0" src="imagens/lupa.png" style="cursor: hand;" align="absmiddle" alt="Clique aqui para pesquisar postos pelo código" onclick="javascript: fnc_pesquisa_posto (document.frm_consulta.codigo_posto, document.frm_consulta.posto_nome, 'codigo')">
		</td>
		<td>
			<input type="text" name="posto_nome" id="posto_nome" size="30" <? if ($login_fabrica == 5) { ?> onblur="javascript: fnc_pesquisa_posto (document.frm_consulta.codigo_posto, document.frm_consulta.posto_nome, 'nome');" <? } ?> value="<?echo $posto_nome?>" class="frm">
			<img border="0" src="imagens/lupa.png" style="cursor: hand;" align="absmiddle" alt="Clique aqui para pesquisar postos pelo código" onclick="javascript: fnc_pesquisa_posto (document.frm_consulta.codigo_posto, document.frm_consulta.posto_nome, 'nome')">
		</td>
	</tr>

	<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
		<td><? echo ($login_fabrica==3) ? "Marca" : ""; ?></td>
		<td>Nome do Consumidor</td>
	</tr>

	<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
		<td>
		<?
		if($login_fabrica==3){
			echo "<select name='marca' size='1' class='frm' style='width:95px'>";
			echo "<option value=''></option>";
			$sql = "SELECT marca, nome from tbl_marca where fabrica = $login_fabrica order by nome";
			$res = pg_exec($con,$sql);
			if(pg_numrows($res)>0){
				for($i=0;pg_numrows($res)>$i;$i++){
					$xmarca = pg_result($res,$i,marca);
					$xnome = pg_result($res,$i,nome);
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
	<? if ($login_fabrica == 45) { ?>
	<tr class='Conteudo' bgcolor='#D9E2EF' align='left'>
		<td colspan='2'>
			Região
		</td>
	</tr>
	<? } ?>
	<? if($login_fabrica==50){ ?>
	<tr class='Conteudo' bgcolor='#D9E2EF' align='left'>
		<td colspan='2'>
			Status OS
		</td>
	</tr>
	<? } ?>
	<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
		<td colspan='2'>
			<? if($login_fabrica==45){
				echo "<select name='regiao' size='1' class='frm' style='width:320px'>";
			?>
				<option value=''></option>
				<option value='1' <? if ($regiao == 1) echo " SELECTED "; ?>>São Paulo </option>
				<option value='2' <? if ($regiao == 2) echo " SELECTED "; ?>>PE/PB/PI/MA/CE/RN/AP/RO/RR/AM/AC/MS/MT</option>
				<option value='3' <? if ($regiao == 3) echo " SELECTED "; ?>>RS/SC</option>
				<option value='4' <? if ($regiao == 4) echo " SELECTED "; ?>>MG/PA</option>
				<option value='5' <? if ($regiao == 5) echo " SELECTED "; ?>>ES/RJ/DF/GO/TO</option>
				<option value='6' <? if ($regiao == 6) echo " SELECTED "; ?>>BA/SE/AL/PR</option>
			<? echo "</SELECT>";
			}elseif($login_fabrica==50){ ?>
				<select name='tipo_os' size='1' class='frm' style='width:300px'>";
				<option value=''></option>
				<option value='reincidente' <? if ($tipo_os == 'REINCIDENTE') echo " SELECTED "; ?>>Reincidências</option>
				<option value='mais_cinco_dias' <? if ($tipo_os == 'MAIS_CINCO_DIAS') echo " SELECTED "; ?>>Mais de 5 dias sem data de fechamento</option>
				<option value='mais_dez_dias' <? if ($tipo_os == 'MAIS_DEZ_DIAS') echo " SELECTED "; ?>>Mais de 10 dias sem data de fechamento</option>
				<option value='mais_vinte_dias' <? if ($tipo_os == 'MAIS_VINTE_DIAS') echo " SELECTED "; ?>>Mais de 20 dias sem data de fechamento</option>
				<option value='excluidas' <? if ($tipo_os == 'EXCLUIDAS') echo " SELECTED "; ?>>Excluídas do sistema</option>
				<option value='os_com_troca' <? if ($tipo_os == 'OS_COM_TROCA') echo " SELECTED "; ?>>OS com Troca de Produto</option>
				</SELECT>
			<? } ?>
		</td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
		<td>Ref. Produto</td>
		<td>Descrição Produto</td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
		<td>
		<input class="frm" type="text" name="produto_referencia" id="produto_referencia" size="15" maxlength="20" value="<?=$produto_referencia?>" >
		&nbsp;
		<img src='imagens/lupa.png' border='0' align='absmiddle' style="cursor:pointer" onclick="javascript: fnc_pesquisa_produto (document.frm_consulta.produto_referencia, document.frm_consulta.produto_descricao,'referencia')">
		</td>
		<td>
		<input class="frm" type="text" name="produto_descricao" id="produto_descricao" size="30" value="<? echo $produto_descricao ?>" >
		&nbsp;
		<img src='imagens/lupa.png'  style="cursor:pointer" border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_produto (document.frm_consulta.produto_referencia, document.frm_consulta.produto_descricao,'descricao')">
	</tr>

	<? if ($login_fabrica == 3) { ?>

	<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
		<td>&nbsp;</td>
		<td>Admin</td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
		<td>&nbsp;</td>
		<td>
		<select name="admin" size="1" class="frm">
			<option value=''></option>
			<?
			$sql =	"SELECT admin, login
					FROM tbl_admin
					WHERE fabrica = $login_fabrica
					ORDER BY login;";
			$res = pg_exec($con,$sql);
			if (pg_numrows($res) > 0) {
				for ($i = 0 ; $i < pg_numrows($res) ; $i++) {
					$x_admin = pg_result($res,$i,admin);
					$x_login = pg_result($res,$i,login);
					echo "<option value='$x_admin'";
					if ($admin == $x_admin) echo " selected";
					echo ">$x_login</option>";
				}
			}
			?>
			</select>
		</td>
	</tr>

	<? } ?>

	<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
		<td><input type="radio" name="os_situacao" value="APROVADA" <? if ($os_situacao == "APROVADA") echo "checked"; ?>> OS´s Aprovadas</td>
		<td><input type="radio" name="os_situacao" value="PAGA" <? if ($os_situacao == "PAGA") echo "checked"; ?>> OS´s Pagas</td>
	</tr>

<?if($login_fabrica == 20) {
// MLG 2009-08-04 HD 136625
    $sql = 'SELECT pais,nome FROM tbl_pais';
    $res = pg_query($con,$sql);
    $p_tot = pg_num_rows($res);
    for ($i; $i<$p_tot; $i++) {
        list($p_code,$p_nome) = pg_fetch_row($res, $i);
    	$sel_paises .= "\t\t\t\t<option value='$p_code'";
        $sel_paises .= ($pais==$p_code)?" selected":"";
        $sel_paises .= ">$p_nome</option>\n";
    }
?>
	<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
		<td colspan='2'>País<br>
			<select name='pais' size='1' class='frm'>
			 <option></option>
            <?echo $sel_paises;?>
			</select>
		</td>
	</tr>
<?}?>

	<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
		<td colspan='2'> <hr> </td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
		<td colspan='2'> OS em aberto da Revenda = CNPJ
		<input class="frm" type="text" name="revenda_cnpj" size="8" value="<? echo $revenda_cnpj ?>" > /0001-00
		</td>
	</tr>

	<? if($login_fabrica==7){ // HD 75762 para Filizola ?>
		<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
			<td colspan='2'> <hr> </td>
		</tr>
		<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
			<td colspan='2'>
				Classificação da OS
				<select name='classificacao_os' id='classificacao_os' size="1" class="frm">
					<option value='' selected></option>
					<?
						$sql = "SELECT	*
								FROM	tbl_classificacao_os
								WHERE	fabrica = $login_fabrica
								AND		ativo IS TRUE
								ORDER BY descricao";
						$res = @pg_exec ($con,$sql);
						if(pg_numrows($res) > 0){
							for($i=0; $i < pg_numrows($res); $i++){
								$classificacao_os=pg_result($res,$i,classificacao_os);
								$descricao=pg_result($res,$i,descricao);
								echo "<option value='$classificacao_os'>$descricao</option>\n";
							}
						}
					?>
				</select>
			</td>
		</tr>
		<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
			<td colspan='2'> <hr> </td>
		</tr>
		<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
			<td colspan='2'>
				Natureza
				<select name="natureza" class="frm">
					<option value='' selected></option>
					<?
					$sqlN = "SELECT *
						FROM tbl_tipo_atendimento
						WHERE fabrica = $login_fabrica
						AND   ativo IS TRUE
						ORDER BY tipo_atendimento";
					$resN = pg_exec ($con,$sqlN) ;

					for ($z=0; $z<pg_numrows($resN); $z++){
						$xxtipo_atendimento = pg_result($resN,$z,tipo_atendimento);
						$xxcodigo           = pg_result($resN,$z,codigo);
						$xxdescricao        = pg_result($resN,$z,descricao);

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
			</td>
		</tr>
		<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
			<td colspan='2'> <hr> </td>
		</tr>
		<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
			<td colspan='2'>
				Aberto por
				<select name="admin_abriu" class="frm">
					<option value='' selected></option>
					<?php
						$sqlM = "SELECT admin, nome_completo
							FROM tbl_admin
							WHERE fabrica = $login_fabrica
							AND ativo IS TRUE
							ORDER BY nome_completo";
						$resM = pg_exec ($con,$sqlM);

						for ($j=0; $j<pg_numrows($resM); $j++){
							$jadmin = pg_result($resM,$j,admin);
							$jadmin_nome = pg_result($resM,$j,nome_completo);

							echo "<option ";
							if($admin_abriu == $jadmin){
								echo " selected ";
							}
							echo "value='" . $jadmin . "'>";
							echo $jadmin_nome;
							echo "</option>";
						}
						?>
				</select>
			</td>
		</tr>
	<? } ?>
</table>

<table width="400" align="center" border="0" cellspacing="0" cellpadding="2">
	<tr class="Conteudo" bgcolor="#D9E2EF">
		<td colspan='2' align='center'><br><input type="submit" name="btn_acao" value="Pesquisar"></td>
	</tr>
</table>
</table>
</form>

<?
echo $login_fabrica;
include "rodape.php" ?>