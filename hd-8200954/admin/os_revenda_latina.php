<?php

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios = "call_center,gerencia";
include 'autentica_admin.php';

include 'funcoes.php';

# Pesquisa pelo AutoComplete AJAX
$q = strtolower($_GET["q"]);

if (isset($_GET["q"])) {

	$busca      = $_GET["busca"];
	$tipo_busca = $_GET["tipo_busca"];

	if (strlen($q) > 2) {

		if ($tipo_busca == 'revenda') {

			$sql = "SELECT tbl_revenda_fabrica.revenda,
					CASE
						WHEN UPPER(contato_razao_social) LIKE UPPER('%$q%') THEN
							tbl_revenda_fabrica.contato_razao_social
						ELSE
							tbl_revenda_fabrica.contato_nome_fantasia
						END AS nome_revenda
					FROM tbl_revenda_fabrica
					WHERE tbl_revenda_fabrica.fabrica = $login_fabrica
					AND (UPPER(contato_razao_social) LIKE UPPER('%$q%') OR UPPER(contato_nome_fantasia) LIKE UPPER('%$q%'))";

			$res = pg_query($con,$sql);

			if (pg_num_rows ($res) > 0) {

				for ($i = 0; $i < pg_num_rows($res); $i++) {

					$revenda      = trim(pg_fetch_result($res, $i, 'revenda'));
					$nome         = trim(pg_fetch_result($res, $i, 'nome_revenda'));

					echo "$revenda|$nome";
					echo "\n";

				}

			}

		}

	}

	exit;

}
$sql = "SELECT pedir_sua_os FROM tbl_fabrica WHERE fabrica = $login_fabrica";
$res = pg_exec($con, $sql);
$pedir_sua_os = pg_result($res,0,pedir_sua_os);

$msg_erro = "";

$qtde_item = 20;
if (strlen($_POST['qtde_item']) > 0)   $qtde_item = $_POST['qtde_item'];
if (strlen($_POST['qtde_linhas']) > 0) $qtde_item = $_POST['qtde_linhas'];

if (strlen($_GET["lote"]) > 0) {
	$qtde_linhas = $_GET["qtde_linhas"];
	$qtde_item   = $_GET["qtde_linhas"];
}

$btn_acao = trim(strtolower($_POST['btn_acao']));

if (strlen($_GET['os_revenda']) > 0)  $os_revenda = trim($_GET['os_revenda']);
if (strlen($_POST['os_revenda']) > 0) $os_revenda = trim($_POST['os_revenda']);

/* ====================  APAGAR  =================== */
if ($btn_acao == "apagar") {
	if(strlen($os_revenda) > 0){
		$sql = "DELETE FROM tbl_os_revenda
				WHERE  tbl_os_revenda.os_revenda = $os_revenda
				AND    tbl_os_revenda.fabrica    = $login_fabrica ";
		$res = pg_exec($con, $sql);

		$msg_erro = pg_errormessage($con);
		$msg_erro = substr($msg_erro,6);

		if (strlen($msg_erro) == 0) {
			header("Location: $PHP_SELF");
			exit;
		}
	}
}

if ($btn_acao == "gravar") {

if (strlen($_POST['posto_codigo']) > 0) {

	$posto_codigo = trim($_POST['posto_codigo']);
	$posto_codigo = str_replace("-","",$posto_codigo);
	$posto_codigo = str_replace(".","",$posto_codigo);
	$posto_codigo = str_replace("/","",$posto_codigo);
	$posto_codigo = substr($posto_codigo,0,14);

	$res = pg_exec($con,"SELECT * FROM tbl_posto JOIN tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica WHERE tbl_posto_fabrica.codigo_posto = '$posto_codigo'");
	$login_posto = pg_result($res,0,0);

} else {

	$msg_erro = "Por favor, selecione o posto.";

}

	if (strlen($_POST['sua_os']) > 0){
		$xsua_os = $_POST['sua_os'] ;		
		$xsua_os = "000000" . trim($xsua_os);
		$xsua_os = substr($xsua_os, strlen($xsua_os) - 7 , 7) ;
		$xsua_os = "'". $xsua_os ."'";
	} else {
		$xsua_os = "null";
	}

	$xdata_abertura = fnc_formata_data_pg($_POST['data_abertura']);
	$xdata_nf       = fnc_formata_data_pg($_POST['data_nf']);

	if ($xdata_nf=="null") {
			$msg_erro = "Por favor inserir a data da nota fiscal";
	}

	$nota_fiscal = $_POST["nota_fiscal"];
	if (strlen($nota_fiscal) == 0) {
		$xnota_fiscal = 'null';		
	} else {
		$nota_fiscal = trim($nota_fiscal);
		$nota_fiscal = str_replace(".","",$nota_fiscal);
		$nota_fiscal = str_replace(" ","",$nota_fiscal);
		$nota_fiscal = str_replace("-","",$nota_fiscal);
		$nota_fiscal = "000000" . $nota_fiscal;
		$nota_fiscal = substr($nota_fiscal,strlen($nota_fiscal)-6,6);
		$xnota_fiscal = "'" . $nota_fiscal . "'" ;
		
	}

	$motivo = $_POST['motivo'];
	if(strlen($motivo)==0){
		$motivo="null";		
	}

	$revenda = $_POST['revenda'];
	if(empty($revenda)){
		$msg_erro = "Informe a revenda";
	}
	
	if (strlen($_POST['revenda_cnpj']) > 0) {
		$revenda_cnpj  = $_POST['revenda_cnpj'];
		$revenda_cnpj  = str_replace(".","",$revenda_cnpj);
		$revenda_cnpj  = str_replace("-","",$revenda_cnpj);
		$revenda_cnpj  = str_replace("/","",$revenda_cnpj);
		$revenda_cnpj  = str_replace(" ","",$revenda_cnpj);
		$xrevenda_cnpj = "'". $revenda_cnpj ."'";
	} else {
		$xrevenda_cnpj = "null";
	}

	if (strlen($_POST['consumidor_cnpj']) > 0) {
		$consumidor_cnpj  = $_POST['consumidor_cnpj'];
		$consumidor_cnpj  = str_replace(".","",$consumidor_cnpj);
		$consumidor_cnpj  = str_replace("-","",$consumidor_cnpj);
		$consumidor_cnpj  = str_replace("/","",$consumidor_cnpj);
		$consumidor_cnpj  = str_replace(" ","",$consumidor_cnpj);
		$xconsumidor_cnpj = "'". $consumidor_cnpj ."'";
	} else {
		$xconsumidor_cnpj = "null";
	}

	if (strlen($_POST['taxa_visita']) > 0)
		$xtaxa_visita = "'". $_POST['taxa_visita'] ."'";
	else
		$xtaxa_visita = "null";

	if (strlen($_POST['regulagem_peso_padrao']) > 0)
		$xregulagem_peso_padrao = "'". $_POST['regulagem_peso_padrao'] ."'";
	else
		$xregulagem_peso_padrao = "null";

	if (strlen($_POST['certificado_conformidade']) > 0)
		$xcertificado_conformidade = "'". $_POST['certificado_conformidade'] ."'";
	else
		$xcertificado_conformidade = "null";

	$os_reincidente = "'f'";

	
	if (strlen($consumidor) > 0)
		$cliente = "'". $consumidor_revenda ."'";
	else
		$cliente = "null";

	if (strlen($nome) > 0)
		$xconsumidor_nome = "'". $consumidor_nome ."'";
	else
		$xconsumidor_nome = "null";

	if (strlen($cnpj) > 0)
		$xconsumidor_cnpj = "'". $consumidor_cnpj ."'";
	else
		$xconsumidor_cnpj = "null";
//--========================================--

	if (strlen($_POST['revenda_fone']) > 0) {
		$xrevenda_fone = "'". $_POST['revenda_fone'] ."'";
	} else {
		$xrevenda_fone = "null";
	}

	if (strlen($_POST['revenda_email']) > 0) {
		$xrevenda_email = "'". $_POST['revenda_email'] ."'";
	} else {
		$xrevenda_email = "null";
	}

	if (strlen($_POST['obs']) > 0) {
		$xobs = "'". $_POST['obs'] ."'";
	} else {
		$xobs = "null";
	}

	if (strlen($_POST['contrato']) > 0) {
		$xcontrato = "'". $_POST['contrato'] ."'";
	} else {
		$xcontrato = "'f'";
	}

	$tipo_atendimento = $_POST['tipo_atendimento'];
	if (strlen(trim($tipo_atendimento)) == 0) $tipo_atendimento = 'null';

	if (strlen($msg_erro) == 0) {
		$revenda = "null";
		$res = pg_exec($con,"BEGIN TRANSACTION");

		if (strlen($os_revenda) == 0) {
			#-------------- insere ------------
			$sql = "INSERT INTO tbl_os_revenda (
						fabrica          ,
						sua_os           ,
						data_abertura    ,
						data_nf          ,
						nota_fiscal      ,
						cliente          ,
						revenda          ,
						obs              ,
						digitacao        ,
						posto            ,
						tipo_atendimento ,
						contrato         ,
						consumidor_nome  ,
						consumidor_cnpj  ,
						tipo_os
					) VALUES (
						$login_fabrica                    ,
						$xsua_os                          ,
						$xdata_abertura                   ,
						$xdata_nf                         ,
						$xnota_fiscal                     ,
						$cliente                          ,
						$revenda                          ,
						$xobs                             ,
						current_timestamp                 ,
						$login_posto                      ,
						$tipo_atendimento                 ,
						$xcontrato                        ,
						$xconsumidor_nome                 ,
						$xconsumidor_cnpj                 ,
						$motivo
					)";
		} else {
			$sql = "UPDATE tbl_os_revenda SET
						fabrica          = $login_fabrica                   ,
						sua_os           = $xsua_os                         ,
						data_abertura    = $xdata_abertura                  ,
						data_nf          = $xdata_nf                        ,
						nota_fiscal      = $xnota_fiscal                    ,
						cliente          = $cliente                         ,
						revenda          = $revenda                         ,
						obs              = $xobs                            ,
						posto            = $login_posto                     ,
						tipo_atendimento = $tipo_atendimento                ,
						contrato         = $xcontrato                       ,
						consumidor_nome  = $xconsumidor_nome                ,
						consumidor_cnpj  = $xconsumidor_cnpj                ,
						tipo_os          = $motivo
					WHERE os_revenda     = $os_revenda
					AND	 posto           = $login_posto
					AND	 fabrica         = $login_fabrica ";
		}
		
		$res = @pg_exec($con, $sql);
		$msg_erro = pg_errormessage($con);

		if (strlen($msg_erro) == 0 and strlen($os_revenda) == 0) {
			$res        = pg_exec($con,"SELECT CURRVAL ('seq_os_revenda')");
			$os_revenda = pg_result($res,0,0);
			$msg_erro   = pg_errormessage($con);

			// se nao foi cadastrado número da OS Fabricante (Sua_OS)
			if ($xsua_os == 'null' AND strlen($msg_erro) == 0 and strlen($os_revenda) <> 0) {				
				$sql = "UPDATE tbl_os_revenda SET
								sua_os = '$os_revenda'
						WHERE tbl_os_revenda.os_revenda  = $os_revenda
						AND   tbl_os_revenda.posto       = $login_posto
						AND   tbl_os_revenda.fabrica     = $login_fabrica ";
				$res = pg_exec($con, $sql);
				$msg_erro = pg_errormessage($con);				
			}

			if (strlen($msg_erro) > 0) {
				$sql = "UPDATE tbl_cliente SET tbl_cliente.contrato = $xcontrato
						WHERE  tbl_cliente.cliente  = $revenda";
				$res = pg_exec($con, $sql);
				$msg_erro = pg_errormessage($con);
			}

			if (strlen($msg_erro) > 0) {
				break ;
			}
		}

		if (strlen($msg_erro) == 0) {
			//$qtde_item = $_POST['qtde_item'];
			$sql = "DELETE FROM tbl_os_revenda_item WHERE  os_revenda = $os_revenda";
			$res = pg_exec($con, $sql);
			$msg_erro = pg_errormessage($con);

			if($login_fabrica == 15){
				$qtde_item = $_POST['total_linhas'];
				if(strlen($qtde_item) == 0){
					$qtde_item = 500;
				} else {
					$qtde_item = $qtde_item + 5;
				}
			}

			for ($i = 0 ; $i < $qtde_item ; $i++) {

				$referencia               = trim($_POST["produto_referencia_".$i]);
				$serie                    = trim($_POST["produto_serie_".$i]);
				$capacidade               = $_POST["produto_capacidade_".$i];
				$type                     = $_POST["type_".$i];
				$embalagem_original       = $_POST["embalagem_original_".$i];
				$sinal_de_uso             = $_POST["sinal_de_uso_".$i];
				//takashi 27/06
				$aux_nota_fiscal          = trim($_POST["aux_nota_fiscal_".$i]);
				$aux_qtde                 = trim($_POST["aux_qtde_".$i]);

				if (strlen($embalagem_original) == 0) $embalagem_original = "f";
				if (strlen($sinal_de_uso) == 0)       $sinal_de_uso = "f";
				//echo "Qtde: $aux_qtde";
				if (strlen($aux_qtde) == 0) $aux_qtde = "1";
				

				if (strlen($serie) == 0)	$serie = "null";
				else						$serie = "'". $serie ."'";

				if (strlen($type) == 0)		$type = "null";
				else						$type = "'". $type ."'";

				$xxxos = 'null';

				if (strlen($msg_erro_serie) > 0) {
					$msg_erro = $msg_erro_serie;
					break ;
				}

				if (strlen($msg_erro) == 0) {

					if (strlen($referencia) > 0) {
						$referencia = strtoupper ($referencia);
						$referencia = str_replace("-","",$referencia);
						$referencia = str_replace(".","",$referencia);
						$referencia = str_replace("/","",$referencia);
						$referencia = str_replace(" ","",$referencia);
						$referencia = "'". $referencia ."'";

						$sql = "SELECT  produto
								FROM    tbl_produto
								JOIN    tbl_linha USING (linha)
								WHERE   upper(referencia_pesquisa) = $referencia
								AND     tbl_linha.fabrica = $login_fabrica
								AND     tbl_produto.ativo IS TRUE";
						$res = pg_exec($con, $sql);

						if (pg_numrows ($res) == 0) {
							$msg_erro = "Produto $referencia não cadastrado";
							$linha_erro = $i;
						} else {
							$produto   = pg_result($res,0,produto);
						}
					
						if (strlen($capacidade) == 0)	
							$xcapacidade = 'null';
						else
							$xcapacidade = "'".$capacidade."'";
						
						
						if(strlen($aux_nota_fiscal)==0) 
							$aux_nota_fiscal=$xnota_fiscal;
						if (strlen($msg_erro) == 0) {
							$sql = "INSERT INTO tbl_os_revenda_item (
										os_revenda            ,
										produto               ,
										serie                 ,
										nota_fiscal           ,
										data_nf               ,
										capacidade            ,
										type                  ,
										embalagem_original    ,
										sinal_de_uso          ,
										os_reincidente        ,
										qtde                  ,
										reincidente_os
									) VALUES (
										$os_revenda           ,
										$produto              ,
										$serie                ,
										$aux_nota_fiscal      ,
										$xdata_nf             ,
										$xcapacidade          ,
										$type                 ,
										'$embalagem_original' ,
										'$sinal_de_uso'       ,
										$os_reincidente       ,
										$aux_qtde             ,
										$xxxos
									)";
							$res = pg_exec($con, $sql);
							$msg_erro = pg_errormessage($con);
							if (strlen($msg_erro) > 0) {
								break ;
							}
						}
					}
				}
			}

			if (strlen($msg_erro) == 0){
				$sql = "SELECT fn_valida_os_revenda($os_revenda,$login_posto,$login_fabrica)";
				$res = @pg_exec($con, $sql);
				$msg_erro = pg_errormessage($con);
			}
		}
	}

	if (strlen($msg_erro) == 0) {
		$res = pg_exec($con,"COMMIT TRANSACTION");
		header ("Location: os_revenda_finalizada.php?os_revenda=$os_revenda");
		exit;
	} else {
		if (strpos ($msg_erro,"tbl_os_revenda_unico") > 0) $msg_erro = " O Número da Ordem de Serviço do fabricante já esta cadastrado.";
		if (strpos ($msg_erro,"null value in column \"data_abertura\" violates not-null constraint") > 0) $msg_erro = "Data da abertura deve ser informada.";

		$os_revenda = '';
		$res = pg_exec($con,"ROLLBACK TRANSACTION");
	}
//	}
}

if(strlen($msg_erro) == 0 AND strlen($os_revenda) > 0){
	// seleciona do banco de dados
	$sql = "SELECT  tbl_os_revenda.sua_os                                                ,
					tbl_os_revenda.obs                                                   ,
					tbl_os_revenda.contrato                                              ,
					to_char(tbl_os_revenda.data_abertura,'DD/MM/YYYY') AS data_abertura  ,
					to_char(tbl_os_revenda.data_nf      ,'DD/MM/YYYY') AS data_nf        ,
					tbl_os_revenda.nota_fiscal                                           ,
					tbl_os_revenda.consumidor_nome                                       ,
					tbl_os_revenda.consumidor_cnpj                                       ,
					tbl_os_revenda.revenda                                               ,
					tbl_revenda_fabrica.contato_razao_social  AS revenda_nome            ,
					tbl_revenda_fabrica.cnpj  AS revenda_cnpj                            ,
					tbl_revenda_fabrica.contato_fone  AS revenda_fone                    ,
					tbl_revenda_fabrica.contato_email AS revenda_email                   ,
					tbl_os_revenda.explodida                                             ,
					tbl_os_revenda.tipo_atendimento                                      ,
					tbl_os_revenda_item.os_revenda_item                                  ,
					tbl_os_revenda.tipo_os as motivo
			FROM	tbl_os_revenda
			LEFt JOIN tbl_os_revenda_item ON tbl_os_revenda_item.os_revenda = tbl_os_revenda.os_revenda
			LEFT JOIN tbl_revenda_fabrica         ON tbl_os_revenda.revenda         = tbl_revenda_fabrica.revenda AND tbl_revenda_fabrica.fabrica = $login_fabrica
			JOIN	tbl_fabrica ON tbl_os_revenda.fabrica = tbl_fabrica.fabrica
			JOIN    tbl_posto USING (posto)
			JOIN    tbl_posto_fabrica   ON  tbl_posto_fabrica.posto   = tbl_posto.posto
										AND tbl_posto_fabrica.fabrica = tbl_fabrica.fabrica
			WHERE	tbl_os_revenda.os_revenda = $os_revenda
			AND		tbl_os_revenda.fabrica    = $login_fabrica ";
	$res = pg_exec($con, $sql);
	
//	if (getenv("REMOTE_ADDR") == "201.0.9.216") echo nl2br($sql)."<br>".pg_numrows($res); exit;

	if (pg_numrows($res) > 0){
		$sua_os           = pg_result($res,0,sua_os);
		$data_abertura    = pg_result($res,0,data_abertura);
		$data_nf          = pg_result($res,0,data_nf);
		$nota_fiscal      = pg_result($res,0,nota_fiscal);
		$revenda          = pg_result($res,0,revenda);
		$revenda_nome     = pg_result($res,0,revenda_nome);
		$revenda_cnpj     = pg_result($res,0,revenda_cnpj);
		$revenda_fone     = pg_result($res,0,revenda_fone);
		$revenda_email    = pg_result($res,0,revenda_email);
		$obs              = pg_result($res,0,obs);
		$contrato         = pg_result($res,0,contrato);
		$explodida        = pg_result($res,0,explodida);
		$os_revenda_item  = pg_result($res,0,os_revenda_item);
		$tipo_atendimento = pg_result($res,0,tipo_atendimento);
		$motivo           = pg_result($res,0,motivo);
		$consumidor_cnpj  = pg_result($res,0,consumidor_cnpj);
		$consumidor_nome  = pg_result($res,0,consumidor_nome);
		if (strlen($explodida) > 0 and strlen($os_revenda_item) > 0){
			header("Location:os_revenda_parametros.php");
			exit;
		}

		$sql = "SELECT *
				FROM   tbl_os
				WHERE  sua_os ILIKE '$sua_os-%'
				AND    fabrica = $login_fabrica";
		$resX = pg_exec($con, $sql);

		if (pg_numrows($resX) == 0) $exclui = 1;

		$sql = "SELECT  tbl_os_revenda.nota_fiscal,
						to_char(tbl_os_revenda.data_nf, 'DD/MM/YYYY') AS data_nf
				FROM	tbl_os_revenda_item
				JOIN	tbl_os_revenda ON tbl_os_revenda.os_revenda = tbl_os_revenda_item.os_revenda
				WHERE	tbl_os_revenda.os_revenda = $os_revenda
				AND		tbl_os_revenda.fabrica    = $login_fabrica
				AND		tbl_os_revenda_item.nota_fiscal NOTNULL
				AND		tbl_os_revenda_item.data_nf     NOTNULL LIMIT 1";
		$res = pg_exec($con, $sql);

		if (pg_numrows($res) > 0){
			$nota_fiscal = pg_result($res,0,nota_fiscal);
			$data_nf     = pg_result($res,0,data_nf);
		}
	}
}

$title			= "Cadastro de Ordem de Serviço - Revenda";
$layout_menu	= 'os';

include "cabecalho.php";
include "javascript_pesquisas.php";
?>
<script type="text/javascript" src="js/jquery-1.4.2.js"></script>
<script type='text/javascript' src='js/jquery.autocomplete.js'></script>
<link rel="stylesheet" type="text/css" href="js/jquery.autocomplete.css" />
<script type='text/javascript' src='js/jquery.bgiframe.min.js'></script>
<script type='text/javascript' src='js/dimensions.js'></script>
<script type="text/javascript" src="../js/jquery.maskedinput.js"></script>
<link rel="stylesheet" type="text/css" href="../plugins/shadowbox/shadowbox.css" media="all">
<script src="../plugins/shadowbox/shadowbox.js" type="text/javascript"></script>
<script language='javascript'>
$(document).ready(function() {
	Shadowbox.init();

	function formatItem(row) {
		return row[1];
	}

	/* Busca pelo Nome */
	$("input[name=revenda_nome]").autocomplete("<?echo $PHP_SELF.'?tipo_busca=revenda&busca=nome'; ?>", {
		minChars: 3,
		delay: 150,
		width: 350,
		matchContains: true,
		formatItem: formatItem,
		formatResult: function(row) {return row[1];}
	});

	$("input[name=revenda_nome]").result(function(event, data, formatted) {
		$("input[name=revenda]").val(data[0]) ;
	});

	$("input[name=data_nf]").maskedinput("99/99/9999");

});

	
function pesquisaRevenda(campo,tipo){
	var campo = campo.value;

	if (jQuery.trim(campo).length > 2){
		Shadowbox.open({
			content:	"pesquisa_revenda_latina.php?"+tipo+"="+campo+"&tipo="+tipo,
			player:	"iframe",
			title:		"Pesquisa Revenda",
			width:	800,
			height:	500
		});
	}else
		alert("Informar toda ou parte da informação para realizar a pesquisa!");
}

function retorna_revenda(revenda,nome){
	gravaDados("revenda",revenda);
	gravaDados("revenda_nome",nome);
}


function pesquisaProduto(produto,tipo,posicao){

	if (jQuery.trim(produto.value).length > 2){
		Shadowbox.open({
			content:	"produto_pesquisa_2_nv.php?"+tipo+"="+produto.value+"&posicao="+posicao,
			player:	"iframe",
			title:		"Produto",
			width:	800,
			height:	500
		});
	}else{
		alert("Informar toda ou parte da informação para realizar a pesquisa!");
		produto.focus();
	}
}

function retorna_dados_produto(produto,linha,nome_comercial,voltagem,referencia,descricao,referencia_fabrica,garantia,ativo,valor_troca,troca_garantia,troca_faturada,mobra,off_line,capacidade,ipi,troca_obrigatoria, posicao){

	if(posicao.length > 0 && posicao != 'undefined'){
		gravaDados('produto_referencia_'+posicao,referencia);
		gravaDados('produto_descricao_'+posicao,descricao);
		gravaDados('produto_voltagem_'+posicao,voltagem);
	}else{
		gravaDados('produto_referencia2',referencia);
		gravaDados('produto_descricao2',descricao);
		gravaDados('produto_voltagem2',voltagem);
	}
}

function pesquisaPosto(campo,tipo){
	var campo = campo.value;

	if (jQuery.trim(campo).length > 2){
		Shadowbox.open({
			content:	"posto_pesquisa_2_nv.php?"+tipo+"="+campo+"&tipo="+tipo,
			player:	"iframe",
			title:		"Pesquisa Posto",
			width:	800,
			height:	500
		});
	}else
		alert("Informar toda ou parte da informação para realizar a pesquisa!");
}

function retorna_posto(codigo_posto,posto,nome,cnpj,cidade,estado,credenciamento,num_posto){
	gravaDados('posto_codigo',codigo_posto);
	gravaDados('posto_nome',nome);
}
	
function gravaDados(name, valor){
	try{
		$("input[name="+name+"]").val(valor);
	} catch(err){
		return false;
	}
}
	
function fnc_pesquisa_produto_serie (campo,campo2,campo3) {
	if (campo3.value != "") {
		var url = "";
		url = "produto_serie_pesquisa2.php?campo=" + campo3.value ;
		janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=600, height=400, top=18, left=0");
		janela.referencia	= campo;
		janela.descricao	= campo2;
		janela.serie	= campo3;
		janela.focus();
	}
}

function checarNumero(campo){
	var num = campo.value;
	campo.value = parseInt(num);
	if (campo.value=='NaN') {
		campo.value='';
		return false;
	}
}


function addRowToTable(){
	var tbl = document.getElementById('tbl_produto');
	var lastRow = tbl.rows.length;
	// if there's no header row in the table, then iteration = lastRow + 1
	var iteration = lastRow -1 ;
	var row = tbl.insertRow(lastRow);


	var cellRight1 = row.insertCell(0);
	var textNode = document.createTextNode(document.frm_os.produto_descricao2.value);
	cellRight1.setAttribute('align', 'center');
	cellRight1.appendChild(textNode);


	var el = document.createElement('input');
	el.setAttribute('type', 'hidden');
	el.setAttribute('name', 'produto_descricao_' + iteration);
	el.setAttribute('id', 'produto_descricao_' + iteration);
	el.setAttribute('value', document.frm_os.produto_descricao2.value);
	cellRight1.appendChild(el);


	var el = document.createElement('input');
	el.setAttribute('type', 'hidden');
	el.setAttribute('name', 'item_' + iteration);
	el.setAttribute('id', 'item_' + iteration);
	el.setAttribute('value', iteration);
	cellRight1.appendChild(el);


	var cellRight1 = row.insertCell(1);
	var textNode = document.createTextNode(document.frm_os.produto_referencia2.value);
	cellRight1.appendChild(textNode);


	var el = document.createElement('input');
	el.setAttribute('type', 'hidden');
	el.setAttribute('name', 'produto_referencia_' + iteration);
	el.setAttribute('value', document.frm_os.produto_referencia2.value);
	el.setAttribute('id', 'produto_referencia_' + iteration);
	cellRight1.appendChild(el);


	var cellRight1 = row.insertCell(2);
	var el = document.createElement('input');
	cellRight1.setAttribute('align', 'center');
	el.setAttribute('class', 'frm');
	el.setAttribute('type', 'text');
	el.setAttribute('name', 'produto_serie_' + iteration);
	el.setAttribute('id', 'produto_serie_' + iteration);
	el.setAttribute('size', '10');
	cellRight1.appendChild(el);


	var cellRight1 = row.insertCell(3);
	var el = document.createElement('input');
	cellRight1.setAttribute('align', 'center');
	el.setAttribute('class', 'frm');
	el.setAttribute('type', 'text');
	el.setAttribute('name', 'aux_nota_fiscal_' + iteration);
	el.setAttribute('id', 'aux_nota_fiscal_' + iteration);
	el.setAttribute('size', '10');
	cellRight1.appendChild(el);

	var tmp=document.getElementById("produto_referencia2_"+iteration);
	if (tmp){
		tmp.focus();
	}
}

function removeRowFromTable()
{
	var tbl = document.getElementById('tbl_produto');
	var lastRow = tbl.rows.length;
	if (lastRow > 2) tbl.deleteRow(lastRow - 1);
}


function adicionaLinha(linha){
	var tbl = document.getElementById('tbl_produto');
	var lastRow = tbl.rows.length;

	for (i=1;i<=linha;i++) {
		if(tbl.rows.length < 300){
			addRowToTable();
			document.getElementById("total_linhas").value = tbl.rows.length;
		} else {
			alert('Limite de campos é de 300!');
			return false;
		}
	}
}

</script>


<style type="text/css">

.menu_top {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: x-small;
	font-weight: bold;
	border: 1px solid;
	color:#ffffff;
	background-color: #596D9B
}

.table {
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: x-small;
	text-align: center;
	border: 1px solid #d9e2ef;
}

.table_line {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	border: 0px solid;
}

.table_line2 {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	background-color: #CED7e7;
}

</style>

<!-- ============= <HTML> COMEÇA FORMATAÇÃO ===================== -->

<?
if (strlen($msg_erro) > 0) {
?>
<table border="0" cellpadding="0" cellspacing="0" align="center" bgcolor="#ffffff">
<tr>
	<td height="27" valign="middle" align="center">
		<b><font face="Arial, Helvetica, sans-serif" color="#FF3333">
<?
	// retira palavra ERROR:
	if (strpos($msg_erro,"ERROR: ") !== false) {
		$erro = "Foi detectado o seguinte erro:<br>";
		$msg_erro = substr($msg_erro, 6);
	}

	// retira CONTEXT:
	if (strpos($msg_erro,"CONTEXT:")) {
		$x = explode('CONTEXT:',$msg_erro);
		$msg_erro = $x[0];
	}
	echo $erro . $msg_erro;
?>
		</font></b>
	</td>
</tr>
</table>
<?
}
//echo $msg_debug;
?>

<table width="650" border="0" cellpadding="0" cellspacing="0" align="center" bgcolor="#ffffff" class="table">
	<tr class="menu_top">
		<td nowrap><font size="1" face="Geneva, Arial, Helvetica, san-serif">ATENÇÃO: <br><br> AS ORDENS DE SERVIÇO DIGITADAS NESTE MÓDULO SÓ SERÃO VÁLIDAS APÓS O CLIQUE EM GRAVAR E DEPOIS EM EXPLODIR.</font></td>
	</tr>
</table>
<br>

<br>

<form name="frm_os" method="POST" action="<? echo $PHP_SELF ?>">

<table width="650" border="0" cellpadding="0" cellspacing="0" align="center" bgcolor="#ffffff" class="table">
	<tr >
		<td><img height="1" width="20" src="imagens/spacer.gif"></td>
		<td valign="top" align="left">

			<!--------------- Formulário ------------------->

			<table width="100%" border="0" cellspacing="3" cellpadding="2">
<?
if (strlen($_GET['os_revenda']) > 0)  $os_revenda = trim($_GET['os_revenda']);
if (strlen($_POST['os_revenda']) > 0) $os_revenda = trim($_POST['os_revenda']);
?>
			<input type='hidden' name='os_revenda' value='<? echo $os_revenda; ?>'>

			<input name="sua_os" type="hidden" value="<? echo $sua_os ?>">
				
				<tr class="menu_top">
					<? if ($pedir_sua_os == 't') { ?>
					<td nowrap>
						<font size="1" face="Geneva, Arial, Helvetica, san-serif">OS Fabricante</font>
					</td>
					<? } ?>
					<td nowrap>
						<font size="1" face="Geneva, Arial, Helvetica, san-serif">Data Abertura</font>
					</td>
		
					<td nowrap>
						<font size="1" face="Geneva, Arial, Helvetica, san-serif">Nota Fiscal</font>
					</td>
		
					<td nowrap>
						<font size="1" face="Geneva, Arial, Helvetica, san-serif">Data Nota</font>
					</td>
				</tr>
				<tr>
					<? if ($pedir_sua_os == 't') { ?>
					<td nowrap align='center'>						
						<input name="sua_os" class="frm" type="text" size="10" maxlength="10" value="<? echo $sua_os ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus ="this.className='frm-on';displayText('&nbsp;Digite aqui o número da OS do Fabricante.');">						
					</td>
					<? } ?>
					<td nowrap align='center'>
<!-- 						<input name="data_abertura" size="12" maxlength="10" value="<? if (strlen($data_abertura) == 0) $data_abertura = date("d/m/Y"); echo $data_abertura; ?>" type="text" class="frm" tabindex="0" > <font face='arial' size='1'> Ex.: <? echo date("d/m/Y"); ?></font> -->
						<input name="data_abertura" size="11" maxlength="10" value="
<?
				if (strlen($data_abertura) == 0 ) $data_abertura = date("d/m/Y");
				echo $data_abertura; 
?>" type="text" ><br><font face='arial' size='1'>Ex.: <? echo date("d/m/Y"); ?></font>
			
					</td>
		
					<td nowrap align='center'>
						
						<input name='nota_fiscal' size='6' maxlength='6' value='<? echo $nota_fiscal ?>' type='text' class='frm' tabindex='0' >
						
					</td>
		
					<td nowrap align='center'>
						<input name="data_nf" size="11" maxlength="10"value="<? echo $data_nf ?>" type="text" class="frm" tabindex="0" > <font face='arial' size='1'> Ex.: 25/10/2004</font>
					</td>
				</tr>

			</table>
			<? $revenda_aux = "Revenda";?>
			<table width="100%" border="0" cellspacing="3" cellpadding="2">
				<tr class="menu_top">
					<td>
						<font size="1" face="Geneva, Arial, Helvetica, san-serif">Nome <?=$revenda_aux;?></font>
					</td>
					<!--
					<td>
						<font size="1" face="Geneva, Arial, Helvetica, san-serif">CNPJ <?=$revenda_aux;?></font>
					</td>
					<td>
						<font size="1" face="Geneva, Arial, Helvetica, san-serif">Fone <?=$revenda_aux;?></font>
					</td>
					<td>
						<font size="1" face="Geneva, Arial, Helvetica, san-serif">e-Mail <?=$revenda_aux;?></font>
					</td>
					-->
				</tr>
			<?/* Foi modificado por Fernando. Foi colcoado o readonly nos campos Fone e e-mail 
				por ser apenas de leitura caso haja necessidade de alteração tem que ir em
				cadastro para alterar os dados da revenda. */?>
				<tr>
					<td align='center'>
						<input type='hidden' name='revenda' value="<?=$revenda?>">
						<input class="frm" type="text" name="revenda_nome" size="60" maxlength="50" value="<? echo $revenda_nome ?>" onkeyup="somenteMaiusculaSemAcento(this)">&nbsp;
						<img src='imagens/lupa.png' border='0' align='absmiddle' onclick='javascript: pesquisaRevenda (document.frm_os.revenda_nome, "nome")' style='cursor:pointer;'>
					</td>
					<!--
					<td align='center'>
						<input class="frm" type="text" name="revenda_cnpj" size="14" maxlength="14" value="<? echo $revenda_cnpj ?>">&nbsp;<img src='imagens/btn_buscar5.gif' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_revenda (document.frm_os.revenda_cnpj, "cnpj")' style='cursor:pointer;'>
					</td>
					<td align='center'>
						<input readonly class="frm" type="text" name="revenda_fone" size="11"  maxlength="20"  value="<? echo $revenda_fone ?>" >
					</td>
					<td align='center'>
						<input readonly class="frm" type="text" name="revenda_email" size="11" maxlength="50" value="<? echo $revenda_email ?>" tabindex="0">
					</td>
					-->
				</tr>
			</table>

			<table width="100%" border="0" cellspacing="3" cellpadding="2">
				<tr class="menu_top">
					<td>
						<font size="1" face="Geneva, Arial, Helvetica, san-serif">Código do posto</font>
					</td>
					<td>
						<font size="1" face="Geneva, Arial, Helvetica, san-serif">Nome do posto</font>
					</td>
				</tr>
				<tr>
					<td align='center'>
						<input class="frm" type="text" name="posto_codigo" size="12" value="<? echo $posto_codigo ?>" >&nbsp;<img src='imagens/lupa.png' border='0' align='absmiddle' onclick="javascript: pesquisaPosto (document.frm_os.posto_codigo,'codigo')" style="cursor:pointer;"></A>
					</td>
					<td align='center'>
						<input class="frm" type="text" name="posto_nome" size="50" value="<? echo $posto_nome ?>" >&nbsp;<img src='imagens/lupa.png' border='0' align='absmiddle' onclick="javascript: pesquisaPosto (document.frm_os.posto_nome,'nome')" style="cursor:pointer;"></A>
					</td>
				</tr>
			</table>


<input type="hidden" name="revenda_cidade" value="">
<input type="hidden" name="revenda_estado" value="">
<input type="hidden" name="revenda_endereco" value="">
<input type="hidden" name="revenda_cep" value="">
<input type="hidden" name="revenda_numero" value="">
<input type="hidden" name="revenda_complemento" value="">
<input type="hidden" name="revenda_bairro" value="">

			<table width="100%" border="0" cellspacing="3" cellpadding="2">
				<tr class="menu_top">
					<td>
						<font size="1" face="Geneva, Arial, Helvetica, san-serif">Observações</font>
					</td>
				</tr>
				<tr>

<?

if(strlen($qtde_linhas)==0){$qtde_linhas = '05'; $qtde_item='05';}

?>
					<td align='center'>
						<input class="frm" type="text" name="obs" size="73" value="<? echo $obs ?>">
					</td>	
				</tr>
			</table>
		</td>
		<td><img height="1" width="16" src="imagens/spacer.gif"></td>
	</tr>
</table>

<?
if (strlen($os_revenda) > 0) {
	$sql = "SELECT      tbl_produto.produto
			FROM        tbl_os_revenda_item
			JOIN        tbl_produto   USING (produto)
			JOIN        tbl_os_revenda USING (os_revenda)
			WHERE       tbl_os_revenda_item.os_revenda = $os_revenda
			ORDER BY    tbl_os_revenda_item.os_revenda_item";
	$res_os = pg_exec($con, $sql);
}

// monta o FOR
echo "<input class='frm' type='hidden' name='qtde_item' value='$qtde_item'>";
echo "<input type='hidden' name='btn_acao' value=''>";
echo "<input type='hidden' name='total_linhas' id='total_linhas' value=''>";

if($os_revenda == 0 AND $btn_acao <> 'gravar'){

	echo "<table width='650' border='0' cellpadding='1' cellspacing='2' align='center' bgcolor='#ffffff' >";
	echo "<tr class='menu_top'>";
	echo "<td colspan='4'>";
		echo "<font size='1' face='Geneva, Arial, Helvetica, san-serif'>Produto</font>";
	echo "</td>";
	echo "</tr>";
	echo "<tr>";
		echo "<input type='hidden' name='voltagem2' value=''>";
		echo "<input type='hidden' name='total_linha' value=''>";
		echo "<td align='center' style='font-size:10px'>Descrição<br><input class='frm' type='text' name='produto_descricao2' size='35' maxlength='50' value='$produto_descricao'>&nbsp;<img src='imagens/lupa.png' border='0' align='absmiddle' onclick='javascript: pesquisaProduto (document.frm_os.produto_descricao2,\"descricao\")' style='cursor:pointer;'></td>\n";
		echo "<td align='center' style='font-size:10px'>Referência<br><input class='frm' type='text' name='produto_referencia2' size='12' maxlength='50' value='$referencia_produto'>&nbsp;<img src='imagens/lupa.png' border='0' align='absmiddle' onclick='javascript: pesquisaProduto (document.frm_os.produto_referencia2,\"referencia\")' style='cursor:pointer;'></td>\n";
		echo "<td style='font-size:10px'>Qtde<br><INPUT TYPE='text' NAME='produto_qtde' id='produto_qtde' size='3'></td>";
		echo "<td><img src='imagens/btn_adicionar_azul.gif' onClick=\"javascript: adicionaLinha(document.frm_os.produto_qtde.value); document.frm_os.produto_descricao2.value=''; document.frm_os.produto_referencia2.value='';\" border='0' style='cursor:pointer;'></td>";
	echo "</tr>";
	echo "</table>";

	echo "<table width='650px' align='center' style='font-size: 10px' border='0' cellspacing='5' cellpadding='0' id='tbl_produto'>";
	echo "<thead>";
		echo "<tr>";
			echo "<td class='menu_top'>Descrição</td>";
			echo "<td class='menu_top'>Referência</td>";
			echo "<td class='menu_top'>N. Série</td>";
			echo "<td class='menu_top'>Nota Fiscal</td>";
		echo "</tr>";
	echo "</thead>";
	echo "<tbody>";
	echo "</tbody>";
	echo "</table>";

}


$qtde_item = $_POST['total_linhas'];
if(strlen($qtde_item) == 0){
	$qtde_item = 500;
} else {
	$qtde_item = $qtde_item + 5;
}


if(strlen($os_revenda) <> 0 OR $btn_acao == 'gravar'){
	$qtde_item = $_POST['total_linhas'];
	if(strlen($qtde_item) == 0){
		$qtde_item = 500;
	} else {
		$qtde_item = $qtde_item + 5;
	}
	for ($i=0; $i<$qtde_item; $i++) {
		
		$novo               = 't';
		$os_revenda_item    = "";
		$referencia_produto = "";
		$serie              = "";
		$produto_descricao  = "";
		$capacidade         = "";
		$type               = "";
		$embalagem_original = "";
		$sinal_de_uso       = "";
		$aux_nota_fiscal    = "";

		if ($i % 20 == 0) {
			#if ($i > 0) {
			#	echo "<tr>";
			#	echo "<td colspan='5' align='center'>";
			#	echo "<img src='imagens/btn_gravar.gif' onclick=\"javascript: if (document.frm_os.btn_acao.value == '' ) { document.frm_os.btn_acao.value='gravar' ; document.frm_os.submit() } else { alert ('Aguarde submissão') }\" ALT='Gravar' border='0' style='cursor:pointer;'>";

			#	if (strlen($os_revenda) > 0 AND strlen($exclui) > 0) {
			#		echo "&nbsp;&nbsp;<img src='imagens/btn_apagar.gif' style='cursor:pointer' onclick=\"javascript: if (document.frm_os.btn_acao.value == '' ) { if(confirm('Deseja realmente apagar esta OS?') == true) { document.frm_os.btn_acao.value='apagar'; document.frm_os.submit(); } else { return; }; } else { alert ('Aguarde submissão') }\" ALT='Apagar a Ordem de Serviço' border='0'>";
			#	}

			#	echo "</td>";
			#	echo "</tr>";
			#	echo "</table>";
			#}

			echo "<input type='hidden' name='total_linhas' id='total_linhas' value='$total_linhas'>";
			echo "<table width='650' border='0' cellpadding='1' cellspacing='2' align='center' bgcolor='#ffffff'>";
			echo "<tr class='menu_top'>";			
			echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif'>Número de série</font></td>";
			echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif'>Descrição do produto</font></td>";
			echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif'>Produto</font></td>";
			echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif'>Nota Fiscal</font></td>";
			echo "</tr>";
		}

		if (strlen($os_revenda) > 0){
			if (@pg_numrows($res_os) > 0) {
				$produto = trim(@pg_result($res_os,$i,produto));
			}

			if(strlen($produto) > 0){
				// seleciona do banco de dados
				$sql = "SELECT   tbl_os_revenda_item.os_revenda_item ,
								 tbl_os_revenda_item.serie              ,
								 tbl_os_revenda_item.capacidade         ,
								 tbl_os_revenda_item.nota_fiscal        ,
								 tbl_os_revenda_item.type               ,
								 tbl_os_revenda_item.embalagem_original ,
								 tbl_os_revenda_item.sinal_de_uso       ,
								 tbl_os_revenda_item.qtde               ,
								 tbl_produto.referencia                 ,
								 tbl_produto.descricao
						FROM	 tbl_os_revenda
						JOIN	 tbl_os_revenda_item ON tbl_os_revenda.os_revenda = tbl_os_revenda_item.os_revenda
						JOIN	 tbl_produto ON tbl_produto.produto = tbl_os_revenda_item.produto
						WHERE	 tbl_os_revenda_item.os_revenda = $os_revenda";
	//echo $sql;
				$res = pg_exec($con, $sql);

				if (@pg_numrows($res) == 0) {
					$novo               = 't';
					$os_revenda_item    = $_POST["item_".$i];
					$referencia_produto = $_POST["produto_referencia_".$i];
					$serie              = $_POST["produto_serie_".$i];
					$produto_descricao  = $_POST["produto_descricao_".$i];
					$capacidade         = $_POST["produto_capacidade_".$i];
					$type               = $_POST["type_".$i];
					$embalagem_original = $_POST["embalagem_original_".$i];
					$sinal_de_uso       = $_POST["sinal_de_uso_".$i];
					$aux_nota_fiscal    = $_POST["aux_nota_fiscal_".$i];
					$aux_qtde           = $_POST["aux_qtde_".$i];
				} else {
					$novo               = 'f';
					$os_revenda_item    = pg_result($res,$i,os_revenda_item);
					$referencia_produto = pg_result($res,$i,referencia);
					$produto_descricao  = pg_result($res,$i,descricao);
					$serie              = pg_result($res,$i,serie);
					$capacidade         = pg_result($res,$i,capacidade);
					$type               = pg_result($res,$i,type);
					$embalagem_original = pg_result($res,$i,embalagem_original);
					$sinal_de_uso       = pg_result($res,$i,sinal_de_uso);
					$aux_nota_fiscal    = pg_result($res,$i,nota_fiscal);
					$aux_qtde           = pg_result($res,$i,qtde);
				}
			} else {
				$novo               = 't';
			}
		} else {
			$novo               = 't';
			$os_revenda_item    = $_POST["item_".$i];
			$referencia_produto = $_POST["produto_referencia_".$i];
			$serie              = $_POST["produto_serie_".$i];
			$produto_descricao  = $_POST["produto_descricao_".$i];
			$capacidade         = $_POST["produto_capacidade_".$i];
			$type               = $_POST["type_".$i];
			$embalagem_original = $_POST["embalagem_original_".$i];
			$sinal_de_uso       = $_POST["sinal_de_uso_".$i];
			$aux_nota_fiscal    = $_POST["aux_nota_fiscal_".$i];
			$aux_qtde           = $_POST["aux_qtde_".$i];
	//echo $aux_qtde;
	//echo $os_revenda;
		}

		echo "<input type='hidden' name='novo_$i' value='$novo'>\n";
		echo "<input type='hidden' name='item_$i' value='$os_revenda_item'>\n";

		echo "<tr "; if ($linha_erro == $i AND strlen($msg_erro) > 0) echo "bgcolor='#ffcccc'"; echo ">\n";
		echo "<input type='hidden' name='voltagem_$i' value=''>";
		echo "<td align='center'><input class='frm' type='text' name='produto_serie_$i'  size='8'  maxlength='20'  value='$serie'>&nbsp;";
		echo "<img src='imagens/lupa.png' border='0' align='absmiddle' onclick=\"javascript: fnc_pesquisa_produto_serie (document.frm_os.produto_referencia_$i,document.frm_os.produto_descricao_$i,document.frm_os.produto_serie_$i)\" style='cursor:pointer;'>";
		echo "</td>\n";
		echo "<td align='center'><input class='frm' type='text' name='produto_descricao_$i' size='35' maxlength='50' value='$produto_descricao'>&nbsp;<img src='imagens/lupa.png' border='0' align='absmiddle' onclick='javascript: pesquisaProduto (document.frm_os.produto_descricao_$i,\"descricao\",$i)' style='cursor:pointer;'></td>\n";
		echo "<td align='center'><input class='frm' type='text' name='produto_referencia_$i' size='10' maxlength='50' value='$referencia_produto'>&nbsp;<img src='imagens/lupa.png' border='0' align='absmiddle' onclick='javascript: pesquisaProduto (document.frm_os.produto_referencia_$i,\"referencia\",$i)' style='cursor:pointer;'></td>\n";
		echo "<td align='center'><input class='frm' type='text' name='aux_nota_fiscal_$i'  size='6'  maxlength='6'  value='$aux_nota_fiscal'></td>\n";
		echo "</tr>\n";

		// limpa as variaveis
		$novo               = '';
		$os_revenda_item    = '';
		$referencia_produto = '';
		$serie              = '';
		$produto_descricao  = '';
		$capacidade         = '';

	}
}
echo "<tr>";
echo "<td colspan='5' align='center'>";
echo "<br>";
//echo "<input type='hidden' name='btn_acao' value=''>";
echo "<img src='imagens/btn_gravar.gif' onclick=\"javascript: if (document.frm_os.btn_acao.value == '' ) { document.frm_os.btn_acao.value='gravar' ; document.frm_os.submit() } else { alert ('Aguarde submissão') }\" ALT='Gravar' border='0' style='cursor:pointer;'>";


if (strlen($os_revenda) > 0 AND strlen($exclui) > 0) {
	echo "&nbsp;&nbsp;<img src='imagens/btn_apagar.gif' style='cursor:pointer' onclick=\"javascript: if (document.frm_os.btn_acao.value == '' ) { if(confirm('Deseja realmente apagar esta OS?') == true) { document.frm_os.btn_acao.value='apagar'; document.frm_os.submit(); } else { return; }; } else { alert ('Aguarde submissão') }\" ALT='Apagar a Ordem de Serviço' border='0'>";
}

echo "</td>";
echo "</tr>";
echo "</table>";
?>
</form>

<br>

<? include "rodape.php";?>
