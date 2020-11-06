<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

$login_bloqueio_pedido = $_COOKIE['cook_bloqueio_pedido'];


$sql = "SELECT pedido_faturado FROM tbl_posto_fabrica WHERE posto = $login_posto AND fabrica = $login_fabrica";
$res = pg_exec ($con,$sql);
if (pg_result ($res,0,0) == 'f') {

	//hd 17625 - Suggar faz pedido em garantia manual
	if (pg_result ($res,0,0) == 'f' and $login_fabrica == 24) {
		$sql = "SELECT pedido_em_garantia FROM tbl_posto_fabrica WHERE posto = $login_posto AND fabrica = $login_fabrica";
		$res = pg_exec ($con,$sql);
	}

	if (pg_result ($res,0,0) == 'f') {
		include "cabecalho.php";
		echo "<H4>CADASTRO DE PEDIDOS FATURADOS BLOQUEADO</H4>";
		include "rodape.php";
		exit;
	}

}


#-------- Libera digitação de PEDIDOS pelo distribuidor ---------------
$posto = $login_posto ;

$limit_pedidos = 2;

/* Suggar liberou até 4 pedido em garantia - HD 22397 23862*/
if($login_fabrica==24 AND $login_posto==720){
	$limit_pedidos = 4;
}
// HD 33373
if($login_fabrica==24 AND $login_posto==20235){
	$limit_pedidos = 4;
}
// HD 60077
if($login_fabrica==24 AND $login_posto==476){
	$limit_pedidos = 4;
}

if($login_posto==2474){
	$limit_pedidos = 4;
}

if($login_posto==19566){
	$limit_pedidos = 99;
}

$btn_acao = strtolower ($_POST['btn_acao']);

$msg_erro = "";
$msg_debug = "";

$qtde_item = $_POST['qtde_item'];

if ($btn_acao == "gravar"){
	$pedido            = $_POST['pedido'];
	$condicao          = $_POST['condicao'];
	$tipo_pedido       = $_POST['tipo_pedido'];
	$pedido_cliente    = $_POST['pedido_cliente'];
	$transportadora    = $_POST['transportadora'];
	$linha             = $_POST['linha'];
	$observacao_pedido = $_POST['observacao_pedido'];

	if (strlen($condicao) == 0) {
		$aux_condicao = "null";
	}else{
		$aux_condicao = $condicao ;
	}

	if (strlen($pedido_cliente) == 0) {
		$aux_pedido_cliente = "null";
	}else{
		$aux_pedido_cliente = "'". $pedido_cliente ."'";
	}

	if (strlen($transportadora) == 0) {
		$aux_transportadora = "null";
	}else{
		$aux_transportadora = $transportadora ;
	}

	if (strlen($observacao_pedido) == 0) {
		$aux_observacao_pedido = "null";
	}else{
		$aux_observacao_pedido = "'$observacao_pedido'" ;
	}

	if (strlen($tipo_pedido) <> 0) {
		$aux_tipo_pedido = "'". $tipo_pedido ."'";
	}else{
		$sql = "SELECT	tipo_pedido
				FROM	tbl_tipo_pedido
				WHERE	descricao IN ('Faturado','Venda')
				AND		fabrica = $login_fabrica";
		$res = pg_exec ($con,$sql);
		$aux_tipo_pedido = "'". pg_result($res,0,tipo_pedido) ."'";
	}

	if (strlen($linha) == 0) {
		$aux_linha = "null";
	}else{
		$aux_linha = $linha ;
	}

	#----------- PEDIDO digitado pelo Distribuidor -----------------
	$digitacao_distribuidor = "null";

	if ($distribuidor_digita == 't'){
		$codigo_posto = strtoupper (trim ($_POST['codigo_posto']));
		$codigo_posto = str_replace (" ","",$codigo_posto);
		$codigo_posto = str_replace (".","",$codigo_posto);
		$codigo_posto = str_replace ("/","",$codigo_posto);
		$codigo_posto = str_replace ("-","",$codigo_posto);

		if (strlen ($codigo_posto) > 0) {
			$sql = "SELECT posto FROM tbl_posto_fabrica WHERE fabrica = $login_fabrica AND codigo_posto = '$codigo_posto' AND credenciamento = 'CREDENCIADO'";
			$res = @pg_exec($con,$sql);
			if (pg_numrows ($res) <> 1) {
				$msg_erro = "Posto $codigo_posto não cadastrado";
				$posto = $login_posto;
			}else{
				$posto = pg_result ($res,0,0);
				if ($posto <> $login_posto) {
					$sql = "SELECT posto FROM tbl_posto_linha WHERE posto = $posto AND distribuidor = $login_posto AND linha = $linha";
					$res = @pg_exec($con,$sql);
					if (pg_numrows ($res) <> 1) {
						$msg_erro = "Posto $codigo_posto não pertence a sua região";
						$posto = $login_posto;
					}else{
						$posto = pg_result ($res,0,0);
						$digitacao_distribuidor = $login_posto;
					}
				}
			}
		}
	}
	#------------------------------------------------------

	$res = pg_exec ($con,"BEGIN TRANSACTION");

	if($login_fabrica==24 and $tipo_pedido==104){
		$sql = "SELECT 	to_char(current_date,'MM')::INTEGER as mes,
						to_char(current_date,'YYYY') AS ano";
		$res = pg_exec($con,$sql);
		$mes = pg_result($res,0,mes);
		$ano = pg_result($res,0,ano);

		if(strlen($mes)>0){
			$data_inicial = date("Y-m-01 00:00:00", mktime(0, 0, 0, $mes, 1, $ano));
			$data_final   = date("Y-m-t 23:59:59", mktime(0, 0, 0, $mes, 1, $ano));
			$sql = "SELECT 	count(pedido) as qtde
					FROM tbl_pedido
					WHERE fabrica = $login_fabrica
					AND posto = $login_posto
					AND admin is NULL
					AND data BETWEEN '$data_inicial' AND '$data_final'
					AND tipo_pedido = 104";
			$res = pg_exec($con,$sql);
			$qtde = pg_result($res,0,qtde);
			if($qtde >= $limit_pedidos){
				$msg_erro = "Seu PA já fez $limit_pedidos pedidos de garantia este mês, por favor entre em contato com o fabricante";
			}
		}
	}
if(strlen($msg_erro)==0){
	if (strlen ($pedido) == 0 ) {
		// HD  80338
		if($login_fabrica == 24) {
			$sql_campo = " ,tipo_frete ";
			$sql_valor = " ,'CIF' ";
		}
		#-------------- insere pedido ------------
		$sql = "INSERT INTO tbl_pedido (
					posto          ,
					fabrica        ,
					condicao       ,
					pedido_cliente ,
					transportadora ,
					linha          ,
					tipo_pedido    ,
					digitacao_distribuidor,
					obs
					$sql_campo
				) VALUES (
					$posto              ,
					$login_fabrica      ,
					$aux_condicao       ,
					$aux_pedido_cliente ,
					$aux_transportadora ,
					$aux_linha          ,
					$aux_tipo_pedido    ,
					$digitacao_distribuidor,
					$aux_observacao_pedido
					$sql_valor
				)";
		$res = @pg_exec ($con,$sql);
		$msg_erro = pg_errormessage($con);
		if (strlen($msg_erro) == 0){
			$res = @pg_exec ($con,"SELECT CURRVAL ('seq_pedido')");
			$pedido  = @pg_result ($res,0,0);
		}
	}else{
		$sql = "UPDATE tbl_pedido SET
					condicao       = $aux_condicao       ,
					pedido_cliente = $aux_pedido_cliente ,
					transportadora = $aux_transportadora ,
					linha          = $aux_linha          ,
					tipo_pedido    = $aux_tipo_pedido
				WHERE pedido  = $pedido
				AND   posto   = $login_posto
				AND   fabrica = $login_fabrica";
		$res = @pg_exec ($con,$sql);
		$msg_erro = pg_errormessage($con);
	}
}

	if (strlen ($msg_erro) == 0) {
//$msg_debug .= " $i ) <b>CURRVAL Pedido </b>".$sql." - [ $pedido ]<br><br>";

		$nacional  = 0;
		$importado = 0;

		for ($i = 0 ; $i < $qtde_item ; $i++) {
			$pedido_item     = trim($_POST['pedido_item_'     . $i]);
			$peca_referencia = trim($_POST['peca_referencia_' . $i]);
			$qtde            = trim($_POST['qtde_'            . $i]);
			$preco           = trim($_POST['preco_'           . $i]);

			if (strlen ($peca_referencia) > 0 AND ( strlen($qtde) == 0 OR $qtde < 1 ) ) {
				$msg_erro = "Não foi digitada a quantidade para a Peça $peca_referencia.";
				$linha_erro = $i;
				break;
			}

			if (strlen ($peca_referencia) > 0 AND strlen($preco) == 0 AND $login_fabrica == 30 ) {
				$msg_erro = "A peça $peca_referencia está sem preço!";
				$linha_erro = $i;
				break;
			}
		
			$qtde_anterior = 0;
			$peca_anterior = "";

			if (strlen ($pedido_item) > 0 AND strlen ($peca_referencia) == 0) {
				// delete
				
				$sql = "DELETE	FROM	tbl_pedido_item
						WHERE	pedido_item = $pedido_item
						AND		pedido = $pedido";
				$res = pg_exec ($con,$sql);

			}

			if (strlen ($peca_referencia) > 0) {
				$peca_referencia = trim (strtoupper ($peca_referencia));
				$peca_referencia = str_replace ("-","",$peca_referencia);
				$peca_referencia = str_replace (".","",$peca_referencia);
				$peca_referencia = str_replace ("/","",$peca_referencia);
				$peca_referencia = str_replace (" ","",$peca_referencia);

				$sql = "SELECT  tbl_peca.peca   ,
								tbl_peca.origem ,
								tbl_peca.promocao_site,
								tbl_peca.qtde_disponivel_site ,
								tbl_peca.qtde_max_site,
								tbl_peca.multiplo_site
						FROM    tbl_peca
						WHERE   tbl_peca.referencia_pesquisa = '$peca_referencia'
						AND     tbl_peca.fabrica             = $login_fabrica";
				$res = pg_exec ($con,$sql);
				$peca          = pg_result ($res,0,peca);
				$promocao_site = pg_result ($res,0,promocao_site);
				$qtde_disp     = pg_result ($res,0,qtde_disponivel_site);
				$qtde_max      = pg_result ($res,0,qtde_max_site);
				$qtde_multi    = pg_result ($res,0,multiplo_site);

				if (pg_numrows ($res) == 0) {
					$msg_erro = "Peça $peca_referencia não cadastrada";
					$linha_erro = $i;
					break;
				}else{
					$peca   = pg_result ($res,0,peca);
					$origem = trim(pg_result ($res,0,origem));
				}

				if ($origem == "NAC" or $origem == "1") {
					$nacional = $nacional + 1;
				}

				if ($origem == "IMP" or $origem == "2") {
					$importado = $importado + 1;
				}
				#hd 16782
				if ($nacional > 0 and $importado > 0 and $login_fabrica <> 3 and $login_fabrica <> 5 and $login_fabrica <> 8 and $login_fabrica <> 24 and $login_fabrica <> 6) {
					$msg_erro = "Não é permitido realizar um pedido com peça Nacional e Importada";
					$linha_erro = $i;
					break;
				}

				/*
				if ($login_fabrica == 3 && strlen($peca_referencia) > 0) {
					$sqlX =	"SELECT referencia
							FROM tbl_peca
							WHERE referencia_pesquisa = UPPER('$peca_referencia')
							AND   fabrica = $login_fabrica
							AND   previsao_entrega > date(current_date + INTERVAL '20 days');";
					$resX = pg_exec($con,$sqlX);
					if (pg_numrows($resX) > 0) {
						$peca_previsao = pg_result($resX,0,0);
						$msg_erro = "Não há previsão de chegada da Peça $peca_previsao. Favor encaminhar e-mail para <a href='mailto:leila.beatriz@britania.com.br'>leila.beatriz@britania.com.br</a>, informando o número da ordem de serviço. Somente serão aceitas requisições via email! Não utilizar o 0800.";
					}
				}
				*/



				if (strlen ($msg_erro) == 0 AND strlen($peca) > 0) {

					if (strlen($pedido_item) == 0){
						$sql = "INSERT INTO tbl_pedido_item (
									pedido ,
									peca   ,
									qtde
								) VALUES (
									$pedido ,
									$peca   ,
									$qtde
								)";
					}else{
						$sql = "UPDATE tbl_pedido_item SET
									peca = $peca,
									qtde = $qtde
								WHERE pedido_item = $pedido_item";
					}
					$res = @pg_exec ($con,$sql);
					$msg_erro = pg_errormessage($con);

					if (strlen($msg_erro) == 0 AND strlen($pedido_item) == 0) {
						$res         = pg_exec ($con,"SELECT CURRVAL ('seq_pedido_item')");
						$pedido_item = pg_result ($res,0,0);
						$msg_erro = pg_errormessage($con);
					}

					if (strlen($msg_erro) == 0) {
						$sql = "SELECT fn_valida_pedido_item ($pedido,$peca,$login_fabrica)";
						$res = @pg_exec ($con,$sql);
						$msg_erro = pg_errormessage($con);
					}


					if (strlen ($msg_erro) > 0) {
						break ;
					}
				}
			}
		}
	}
	if (strlen ($msg_erro) == 0) {

		$sql = "SELECT fn_pedido_finaliza ($pedido,$login_fabrica)";
		$res = @pg_exec ($con,$sql);
		$msg_erro = pg_errormessage($con);

	}

	if (strlen ($msg_erro) == 0) {
		$res = pg_exec ($con,"COMMIT TRANSACTION");

		header ("Location: pedido_finalizado.php?pedido=$pedido&loc=1");
		exit;
	}else{
		$res = pg_exec ($con,"ROLLBACK TRANSACTION");
	}

}


#------------ Le Pedido da Base de dados ------------#
$pedido = $_GET['pedido'];

if (strlen ($pedido) > 0) {
	$sql = "SELECT	TO_CHAR(tbl_pedido.data, 'DD/MM/YYYY')    AS data                 ,
					tbl_pedido.tipo_frete                                             ,
					tbl_pedido.transportadora                                         ,
					tbl_transportadora.cnpj                   AS transportadora_cnpj  ,
					tbl_transportadora.nome                   AS transportadora_nome  ,
					tbl_transportadora_fabrica.codigo_interno AS transportadora_codigo,
					tbl_pedido.pedido_cliente                                         ,
					tbl_pedido.tipo_pedido                                            ,
					tbl_pedido.produto                                                ,
					tbl_produto.referencia                    AS produto_referencia   ,
					tbl_produto.descricao                     AS produto_descricao    ,
					tbl_pedido.linha                                                  ,
					tbl_pedido.condicao                                               ,
					tbl_pedido.obs                                                    ,
					tbl_pedido.exportado                                              ,
					tbl_pedido.total_original                                         ,
					tbl_pedido.permite_alteracao
			FROM	tbl_pedido
			LEFT JOIN tbl_transportadora USING (transportadora)
			left JOIN tbl_transportadora_fabrica ON tbl_transportadora_fabrica.fabrica = $login_fabrica
			LEFT JOIN tbl_produto        USING (produto)
			WHERE	tbl_pedido.pedido   = $pedido
			AND		tbl_pedido.posto    = $login_posto
			AND		tbl_pedido.fabrica  = $login_fabrica ";
	$res = pg_exec ($con,$sql);

	if (pg_numrows ($res) > 0) {
		$data                  = trim(pg_result ($res,0,data));
		$transportadora        = trim(pg_result ($res,0,transportadora));
		$transportadora_cnpj   = trim(pg_result ($res,0,transportadora_cnpj));
		$transportadora_codigo = trim(pg_result ($res,0,transportadora_codigo));
		$transportadora_nome   = trim(pg_result ($res,0,transportadora_nome));
		$pedido_cliente        = trim(pg_result ($res,0,pedido_cliente));
		$tipo_pedido           = trim(pg_result ($res,0,tipo_pedido));
		$produto               = trim(pg_result ($res,0,produto));
		$produto_referencia    = trim(pg_result ($res,0,produto_referencia));
		$produto_descricao     = trim(pg_result ($res,0,produto_descricao));
		$linha                 = trim(pg_result ($res,0,linha));
		$condicao              = trim(pg_result ($res,0,condicao));
		$exportado             = trim(pg_result ($res,0,exportado));
		$total_original        = trim(pg_result ($res,0,total_original));
		$permite_alteracao     = trim(pg_result ($res,0,permite_alteracao));
		$observacao_pedido     = @pg_result ($res,0,obs);
	}
}


#---------------- Recarrega Form em caso de erro -------------
if (strlen ($msg_erro) > 0) {
	$pedido         = $_POST['pedido'];
	$condicao       = $_POST['condicao'];
	$tipo_pedido    = $_POST['tipo_pedido'];
	$pedido_cliente = $_POST['pedido_cliente'];
	$transportadora = $_POST['transportadora'];
	$linha          = $_POST['linha'];
	$codigo_posto   = $_POST['codigo_posto'];
}

$title       = "Cadastro de Pedidos de Peças";
$layout_menu = 'pedido';

include "cabecalho.php";


?>

<link rel="stylesheet" type="text/css" href="admin/js/jquery.autocomplete.css" />
<script language='javascript' src='admin/ajax.js'></script>
<script language='javascript' src='admin/ajax_cep.js'></script>
<script type="text/javascript" src="admin/js/jquery-1.2.1.pack.js"></script>
<script type="text/javascript" src="admin/js/jquery.corner.js"></script>
<script type='text/javascript' src='admin/js/jquery.bgiframe.min.js'></script>
<script type='text/javascript' src='admin/js/jquery.ajaxQueue.js'></script>
<script type='text/javascript' src='admin/js/jquery.autocomplete.js'></script>
<script type="text/javascript" src="admin/js/jquery.maskedinput.js"></script>
<script type='text/javascript' src='admin/js/dimensions.js'></script>
<script type='text/javascript' src="admin/js/bibliotecaAJAX.js"></script>

<script language='javascript'>

function autocompletar(campo1,conteudo,campo2) {
	
	var	url = "referencia_pesquisa_suggar_ajax.php?q=" + conteudo;
//	alert(url);
	
	$('#'+campo1).autocomplete(url, {
		minChars: 3,
		delay: 150,
		width: 350,
		scroll: true,
		scrollHeight: 500,
		matchContains: false,
		highlightItem: true,
		formatItem: function (row)   {return row[1]},
		formatResult: function(row)  {return row[0];}
	});

	$('#'+campo1).result(function(event, data, formatted) {
	$('#'+campo2).val(data[2])     ;
	});
}

function autocompletar_descricao(campo1,conteudo,campo2) {
	
	var	url = "referencia_pesquisa_suggar_ajax.php?q=" + conteudo;
//	alert(url);
	
	$('#'+campo1).autocomplete(url, {
		minChars: 3,
		delay: 150,
		width: 350,
		scroll: true,
		scrollHeight: 500,
		matchContains: false,
		highlightItem: true,
		formatItem: function (row)   {return row[1]},
		formatResult: function(row)  {return row[2];}
	});

	$('#'+campo1).result(function(event, data, formatted) {
	$('#'+campo2).val(data[0])     ;
	});


}

function validacao_campos(linha) {
	
	var linha = linha;
	var referencia = document.getElementById("peca_referencia_"+linha).value;
	var descricao = document.getElementById("peca_descricao_"+linha).value;
	var erro = '';

	if (referencia == '') {
		erro += 'REFERENCIA - ';
	}

	if (descricao == '') {
		erro += 'DESCRICAO - ';
	}
	
	linha = linha + 1;
		if (erro != '') {
		alert('Existem erros nos campos: '+erro+' da linha '+linha);
		document.getElementById("validacao").value='sim';
	}
	else {
		document.getElementById("validacao").value='nao';
	}

}

function function1(linha) {

	linha = parseInt(linha) + 1;
//	alert(linha);
	/*se ainda na criou a linha de item */
	if (!document.getElementById('item'+linha)) {

		var tbl = document.getElementById('tabela_itens');
		//var lastRow = tbl.rows.length;	
		//var iteration = lastRow;

		//Atualiza a qtde de linhas
		$('#qtde_item').val(linha);

		/*Criar TR - Linha*/
		var nova_linha = document.createElement('tr');
		nova_linha.setAttribute('rel', linha);

		/********************* COLUNA 1 ****************************/

		var celula = criaCelula('');
		celula.style.cssText = 'text-align: left;';
		var el = document.createElement('input');
		el.setAttribute('type', 'text');
		el.setAttribute('name', 'peca_referencia_' + linha);
		el.setAttribute('id', 'peca_referencia_' + linha);
		el.setAttribute('size', '10');
		el.setAttribute('rel', linha);
		el.onfocus = function(){
			autocompletar('peca_referencia_'+linha,this.value,'peca_descricao_'+linha)};

		celula.appendChild(el);
		
				
		var el = document.createElement('input');
		el.setAttribute('type', 'hidden');
		el.setAttribute('name', 'pedido_item_' + linha);
		el.setAttribute('id', 'pedido_item_' + linha);
		celula.appendChild(el);

		nova_linha.appendChild(celula);

		var celula = criaCelula('');
		celula.style.cssText = 'text-align: left;';
	
		var celula = criaCelula('');
		celula.style.cssText = 'text-align: left;';
		var el = document.createElement('input');
		el.setAttribute('type', 'text');
		el.setAttribute('name', 'peca_descricao_' + linha);
		el.setAttribute('id', 'peca_descricao_' + linha);
		el.setAttribute('size', '50');
		el.setAttribute('rel', linha);
		el.onfocus = function(){
			autocompletar_descricao('peca_descricao_'+linha,this.value,'peca_referencia_'+linha)};
		celula.appendChild(el);

		nova_linha.appendChild(celula);

		var celula = criaCelula('');
		celula.style.cssText = 'text-align: left;';

		/********************* COLUNA 4 ****************************/
		/*Cria TD */
		var celula = criaCelula('');

		var el = document.createElement('input');
		el.setAttribute('type', 'text');
		el.setAttribute('name', 'qtde_' + linha);
		el.setAttribute('id', 'qtde_' + linha);
		el.setAttribute('size', '5');
		el.onblur = function() {
			validacao_campos(linha);
			chamar_funcao(linha);
		}
		
		celula.appendChild(el);
		
		var el = document.createElement('input');
		el.setAttribute('type', 'hidden');
		el.setAttribute('name', 'preco_' + linha);
		el.setAttribute('id', 'preco_' + linha);
		celula.appendChild(el);
		
		nova_linha.appendChild(celula);
		
		/************ FINALIZA LINHA DA TABELA ***********/
		var tbody = document.createElement('TBODY');
		tbody.appendChild(nova_linha);
		tbl.appendChild(tbody);
		
		
				/*Cria TD */
		var celula = criaCelula('');
		celula.style.cssText = 'text-align: right;';

		/*Campo LINHA hidden */
		var el = document.createElement('input');
		el.setAttribute('type', 'hidden');
		el.setAttribute('name', 'total_sugerido' + linha);
		el.setAttribute('id', 'total_sugerido' + linha);
		el.setAttribute('size', '8');
		el.setAttribute('readonly', 'true');
		el.style.cssText = 'text-align: right;';
		
		celula.appendChild(el);

		nova_linha.appendChild(celula);

		$('#peca_referencia_'+linha).focus();
		};
		


} ;

function chamar_funcao(linha) {

	var validar = document.getElementById("validacao").value;
	if (validar != 'sim') {
		function1(linha);
	}

}

function criaCelula(texto) {
	var celula = document.createElement('td');
	var textoNode = document.createTextNode(texto);
	celula.appendChild(textoNode);
	return celula;
}

</script>


<SCRIPT LANGUAGE="JavaScript">
function exibeTipo(){
	f = document.frm_pedido;
	if(f.linha.value == 3){
		f.tipo_pedido.disabled = false;
	}else{
		f.tipo_pedido.selectedIndex = 0;
		f.tipo_pedido.disabled = true;
	}
}

/* FUNÇÃO PARA INTELBRAS POIS TEM POSIÇÃO PARA SER PESQUISADA */


</SCRIPT>


<style type="text/css">
body {
	font: 80% Verdana,Arial,sans-serif;
	/* An explicit background color needed for the Safari browser. */
	/* Without this, Safari users will see black in the corners. */
	background: #FFF;
}

/* The styles below are NOT needed to make .corner() work in your page. */
/*

h1 {
	font: bold 150% Verdana,Arial,sans-serif;
	margin: 0 0 0.25em;
	padding: 0;
	color: #009;
}
h2 {
	font: bold 100% Verdana,Arial,sans-serif;
	margin: 0.75em 0 0.25em;
	padding: 0;
	color: #006;
}
ul {
	margin-top: 0.25em;
	padding-top: 0;
}
code {
	font: 90% Courier New,monospace;
	color: #33a;
	font-weight: bold;
}
#demo {

}*/



.titulo {
	background:#7392BF;
	width: 650px;
	text-align: center;
	padding: 1px 1px; /* padding greater than corner height|width */
/*	margin: 1em 0.25em;*/
	font-size:12px;
	color:#FFFFFF;
}
.titulo h1 {
	color:white;
	font-size: 120%;
}

.subtitulo {
	background:#FCF0D8;
	width: 600px;
	text-align: center;
	padding: 2px 2px; /* padding greater than corner height|width */
	margin: 10px auto;
	color:#392804;
}
.subtitulo h1 {
	color:black;
	font-size: 120%;
}

.content {
	background:#CDDBF1;
	width: 600px;
	text-align: center;
	padding: 5px; /* padding greater than corner height|width */
	margin: 1em 0.25em;
	color:black;
}

.content h1 {
	color:black;
	font-size: 120%;
}

.extra {
	background:#BFDCFB;
	width: 600px;
	text-align: center;
	padding: 2px 2px; /* padding greater than corner height|width */
	margin: 1em 0.25em;
	color:#000000;
	text-align:left;
}
.extra span {
	color:#FF0D13;
	font-size:14px;
	font-weight:bold;
	padding-left:30px;
}

.error {
	background:#ED1B1B;
	width: 600px;
	text-align: center;
	padding: 2px 2px; /* padding greater than corner height|width */
	margin: 1em 0.25em;
	color:#FFFFFF;
	font-size:12px;
}
.error h1 {
	color:#FFFFFF;
	font-size:14px;
	font-size:normal;
	text-transform: capitalize;
}

.inicio {
	background:#8BBEF8;
	width: 600px;
	text-align: center;
	padding: 1px 2px; /* padding greater than corner height|width */
	margin: 0.0em 0.0em;
	color:#FFFFFF;
}
.inicio h1 {
	color:white;
	font-size: 105%;
	font-weight:bold;
}


.subinicio {
	background:#E1EEFD;
	width: 550px;
	text-align: center;
	padding: 1px 2px; /* padding greater than corner height|width */
	margin: 0.0em 0.0em;
	color:#FFFFFF;
}
.subinicio h1 {
	color:white;
	font-size: 105%;
}


#tabela {
	font-size:12px;
}
#tabela td{
	font-weight:bold;
}


.xTabela{
	font-family: Verdana, Arial, Sans-serif;
	font-size:12px;
	padding:10px;
}

.xTabela td{
	/*border-bottom:2px solid #9E9E9E;*/
}

</style>

<style type="text/css">

#layout{
	width: 650px;
	margin:0 auto;
}

ul#split, ul#split li{
	margin:50px;
	margin:0 auto;
	padding:0;
	width:600px;
	list-style:none
}

ul#split li{
	float:left;
	width:600px;
	margin:0 10px 10px 0
}

ul#split h3{
	font-size:14px;
	margin:0px;
	padding: 5px 0 0;
	text-align:center;
	font-weight:bold;
	color:white;
}
ul#split h4{
	font-size:90%
	margin:0px;
	padding-top: 1px;
	padding-bottom: 1px;
	text-align:center;
	font-weight:bold;
	color:white;
}

ul#split p{
	margin:0;
	padding:5px 8px 2px
}

ul#split div{
	background: #E6EEF7
}

li#one{
	text-align:left;

}

li#one div{
	border:1px solid #596D9B
}
li#one h3{
	background: #7392BF;
}

li#one h4{
	background: #7392BF;
}

.coluna1{
	width:250px;
	font-weight:bold;
	display: inline;
	float:left;
}

</style>


<!-- Bordas Arredondadas para a JQUERY -->
<script type="text/javascript" src="js/jquery.corner.js"></script>
<script type="text/javascript">
	$(document).ready(function(){
		$(".titulo").corner("round");
		$(".subtitulo").corner("round");
		$(".content").corner("round 10px");
		$(".error").corner("dog2 10px");
		$(".extra").corner("dog");
		$(".inicio").corner("round");
		$(".subinicio").corner("round");

	});
</script>

<!-- Bordas Arredondadas para a NIFTY -->
<script type="text/javascript" src="js/niftycube.js"></script>
<script type="text/javascript">
	window.onload=function(){
		Nifty("ul#split h3","top");
		Nifty("ul#split div","none same-height");
	}
</script>


<!--  Mensagem de Erro-->
<?
if (strlen ($msg_erro) > 0) {
	if (strpos ($msg_erro,"Cannot insert a duplicate key into unique index tbl_os_sua_os") > 0) {
		$msg_erro = "Esta ordem de serviço já foi cadastrada";
	}
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
	?>
	<div id="layout">
	<div class="error">
	<? echo $erro . $msg_erro; ?>
	</div>
	</div>
<? } ?>

<?


$sql = "SELECT  tbl_condicao.*
		FROM    tbl_condicao
		JOIN    tbl_posto_condicao USING (condicao)
		WHERE   tbl_posto_condicao.posto = $login_posto
		AND     tbl_condicao.fabrica     = $login_fabrica
		AND     tbl_condicao.visivel IS TRUE
		AND     tbl_condicao.descricao ilike '%garantia%'
		ORDER BY lpad(trim(tbl_condicao.codigo_condicao)::text, 10,'0') ";
$res = pg_exec ($con,$sql);

if (pg_numrows ($res) > 0) {
	$frase = "PREENCHA SEU PEDIDO DE COMPRA/GARANTIA";
}else{
	$frase = "PREENCHA SEU PEDIDO DE COMPRA";
}
?>

<br>

<!-- TITULO -->

<div id="layout">
	<div class="titulo"><h1>Cadastro de Pedido</h1></div>
</div>


<!-- SUBTITULO -->
<div id="layout">
	<div class="subtitulo">
	<?
	if ($login_fabrica == 51) {
		echo "
		<font size='4' face='Geneva, Arial, Helvetica, san-serif' color='#FF0000'><b>*** Atenção esta tela é somente para pedidos fora de garantia ***</b></font>
		<br><br>
		<font size='2' face='Geneva, Arial, Helvetica, san-serif' color='#990000'><b>*** Pedidos realizados no valor abaixo de R$30,00 não serão faturados ***</b></font>
		<br><br>";
	}
	?>
		<b>Atenção:</b> Pedidos a prazo dependerão de análise do departamento de crédito.
	</div>
</div>

<!-- OBSERVAÇÕES -->
<div id="layout">
<? if ($login_fabrica<>30) {   //HD 70768-1 - Retirar mensagem na Esmaltec  ?>

	<div class="content">
	<? if($login_fabrica<>24){ ?>
		Para efetuar um pedido por modelo do produto, informe a referência <br> ou descrição e clique na lupa, ou simplesmente clique na lupa.

	<? }else {

		echo "O fabricante limita em $limit_pedidos pedidos de garantia por mês.<br>";

		$sql = "SELECT 	to_char(current_date,'MM')::INTEGER as mes,
						to_char(current_date,'YYYY') AS ano";
		$res = pg_exec($con,$sql);
		$mes = pg_result($res,0,mes);
		$ano = pg_result($res,0,ano);

		if(strlen($mes)>0){
			$data_inicial = date("Y-m-01 00:00:00", mktime(0, 0, 0, $mes, 1, $ano));
			$data_final   = date("Y-m-t 23:59:59", mktime(0, 0, 0, $mes, 1, $ano));
			$sql = "SELECT 	count(pedido) as qtde
					FROM tbl_pedido
					WHERE fabrica = $login_fabrica
					AND posto = $login_posto
					AND data BETWEEN '$data_inicial' AND '$data_final'
					AND tipo_pedido = 104";
			$res = pg_exec($con,$sql);
			$qtde = pg_result($res,0,qtde);
			if($qtde < 2){
				//echo "<br>";
				echo "<b>Seu PA fez <B>$qtde</B> pedido(s) em garantia este mês</b>";
				//echo "<br>";
			}else{
				//echo "<br>";
				echo "<b>Seu PA fez <B>$qtde</B> pedido(s) em garantia este mês, caso necessite de outro pedido em garantia por favor entre em contato com o fabricante.</b>";
				//echo "<br>";
			}
		}
	}
   }  

	?>
	
	</div>
</div>

<form name="frm_pedido" method="post" action="<? echo $PHP_SELF ?>">
<input class="frm" type="hidden" name="pedido" value="<? echo $pedido; ?>">
<input class="frm" type="hidden" name="voltagem" value="<? echo $voltagem; ?>">

<center>
<p>
<? if ($distribuidor_digita == 't' AND $ip == '201.0.9.216') { ?>
	<table width="100%" border="0" cellspacing="5" cellpadding="0">
	<tr valign='top' style='font-size:12px'>
		<td nowrap align='center'>
		Distribuidor pode digitar pedidos para seus postos.
		<br>
		Digite o código do posto
		<input type='text' name='codigo_posto' size='5' maxlength='10' value='<? echo $codigo_posto ?>'>
		ou deixe em branco para seus próprios pedidos.
		</td>
	</tr>
	</table>
<? } ?>
</center>

<br>


<!-- INICIA DIVISÃO -->
<ul id="split">
<li id="one">
<h3><? echo $frase; ?></h3>
<div>

	<? if ($login_fabrica <> "24" and $login_fabrica <> "30") { //HD 70768-2 Retirar campo 'pedido do cliente' ?>
		<p><span class='coluna1'>Pedido do Cliente</span>
			<input class="frm" type="text" name="pedido_cliente" size="15" maxlength="20" value="<? echo $pedido_cliente ?>">
		</p>
	<?}?>

	<?
	$res = pg_exec ("SELECT pedido_escolhe_condicao FROM tbl_fabrica WHERE fabrica = $login_fabrica");

	#permite_alteracao - HD 47695
	if (pg_result ($res,0,0) == 'f' OR $permite_alteracao == 't') {
		echo "<input type='hidden' name='condicao' value=''>";
	}else{?>

	<p><span class='coluna1'>Condição Pagamento</span>
		<select size='1' name='condicao' class='frm'>
		<?
			//hd 17625
			if ($login_fabrica == 24) {
				$sql = "SELECT pedido_em_garantia, pedido_faturado
						FROM tbl_posto_fabrica
						WHERE fabrica = $login_fabrica
						AND   posto   = $login_posto;";
				$res = pg_exec($con,$sql);

				$pede_em_garantia = pg_result($res,0,pedido_em_garantia);
				$pede_faturado    = pg_result($res,0,pedido_faturado);
			}
			if($login_posto == 4311){
				$sql = "SELECT   tbl_condicao.*
						FROM     tbl_condicao
						JOIN     tbl_posto_condicao USING (condicao)
						WHERE    tbl_posto_condicao.posto = $login_posto
						AND      tbl_condicao.fabrica     = $login_fabrica ";
			}else{
				$sql = "SELECT   tbl_condicao.*
						FROM     tbl_condicao
						JOIN     tbl_posto_condicao USING (condicao)
						WHERE    tbl_posto_condicao.posto = $login_posto
						AND      tbl_condicao.fabrica     = $login_fabrica
						AND      tbl_condicao.visivel       IS TRUE
						AND      tbl_posto_condicao.visivel IS TRUE ";
			}

			//hd 17625
			if ($login_fabrica == 24 and $pede_em_garantia == 't' and $pede_faturado == 'f') {
				$sql .= " AND tbl_condicao.condicao = 928 ";
			}

			$sql .= "ORDER BY lpad(trim(tbl_condicao.codigo_condicao)::text, 10,'0') ";
			$xxx  = $sql;
			echo $sql;
			$res = pg_exec ($con,$sql);

			if (pg_numrows ($res) == 0 or $login_fabrica==2) {
				$sql = "SELECT   tbl_condicao.*
						FROM     tbl_condicao
						WHERE    tbl_condicao.fabrica = $login_fabrica
						AND      tbl_condicao.visivel IS TRUE ";

				//hd 17625
				if ($login_fabrica == 24 and $pede_em_garantia == 't' and $pede_faturado == 'f') {
					$sql .= " AND tbl_condicao.condicao = 928 ";
				}

				$sql .= "ORDER BY lpad(trim(tbl_condicao.codigo_condicao)::text, 10,'0') ";
				$res = pg_exec ($con,$sql);
			}

			for ($i = 0 ; $i < pg_numrows ($res) ; $i++ ) {
				echo "<option value='" . pg_result ($res,$i,condicao) . "'";
				if (pg_result ($res,$i,condicao) == $condicao) echo " selected";
				echo ">" . pg_result ($res,$i,descricao) . "</option>";
			}
		?>
		</select>
	</p>

<? } ?>



		<?
		//VERIFICA SE POSTO PODE PEDIR PECA EM GARANTIA ANTECIPADA
		$sql = "SELECT garantia_antecipada FROM tbl_posto_fabrica
				WHERE fabrica=$login_fabrica AND posto=$login_posto";
		$res = pg_exec ($con,$sql);
		if (pg_numrows($res) > 0) {
			$garantia_antecipada = pg_result($res,0,0);
			if($garantia_antecipada <> "t") {
				$garantia_antecipada ="f";
			}
		}
		?>

		<p><span class='coluna1'>Tipo de Pedido</span>
		<?
		// se posto pode escolher tipo_pedido

		$sql = "SELECT   *
				FROM     tbl_posto_fabrica
				WHERE    tbl_posto_fabrica.posto   = $login_posto
				AND      tbl_posto_fabrica.fabrica = $login_fabrica";
		if($login_fabrica<>24) {
			$sql .= " AND      tbl_posto_fabrica.pedido_em_garantia IS TRUE;";
		}
		$res = pg_exec ($con,$sql);

		if (pg_numrows($res) > 0) {

			echo "<select size='1' name='tipo_pedido' class='frm'>";
			$sql = "SELECT   *
					FROM     tbl_tipo_pedido
					WHERE    fabrica = $login_fabrica ";
			if($login_fabrica==24) {
				$sql .= " AND tipo_pedido not in(107,104)";

				#HD 17625
				if ($pede_faturado == 'f') {
					$sql .= " AND tipo_pedido <> 103 ";
				}
			}
			$sql .= " ORDER BY tipo_pedido ";
			$res = pg_exec ($con,$sql);
			$xxx = $sql;

			# AND      (garantia_antecipada is false or garantia_antecipada is null)
			# takashi -  coloquei -> AND      (garantia_antecipada is false or garantia_antecipada is null)
			# efetuei testes em suggar, lenoxx, latina caso ocorra erro retirar linha a cima

			for ($i = 0 ; $i < pg_numrows ($res) ; $i++ ) {
				echo "<option value='" . pg_result($res,$i,tipo_pedido) . "'";
				if (pg_result ($res,$i,tipo_pedido) == $tipo_pedido){
					echo " selected";
				}
				echo ">" . pg_result($res,$i,descricao) . "</option>";
			}

			if($garantia_antecipada=="t"){
				//takashi - efetuei testes em suggar, lenoxx, latina caso ocorra erro retirar esse if
				$sql = "SELECT   *
						FROM     tbl_tipo_pedido
						WHERE    fabrica = $login_fabrica
						AND garantia_antecipada is true ";
				if($login_fabrica==24) {
					$sql .= " and tipo_pedido <> 107";
				}
				 $sql .= " ORDER BY tipo_pedido ";
				$res = pg_exec ($con,$sql);

				for ($i = 0 ; $i < pg_numrows ($res) ; $i++ ) {
					echo "<option value='" . pg_result($res,$i,tipo_pedido) . "'";
					if (pg_result ($res,$i,tipo_pedido) == $tipo_pedido) {
						echo " selected";
					}
					echo ">" . pg_result($res,$i,descricao) . "</option>";
				}
			}
			echo "</select>";

		}else{
			echo "<select size='1' name='tipo_pedido' class='frm' ";
			if ($login_fabrica == 3) {
				echo "disabled";
			}
			echo ">";
			$sql = "SELECT   *
					FROM    tbl_tipo_pedido
					WHERE   (tbl_tipo_pedido.descricao ILIKE '%Faturado%'
				       OR	tbl_tipo_pedido.descricao ILIKE '%Venda%')
					AND     tbl_tipo_pedido.fabrica = $login_fabrica
					AND     (garantia_antecipada is false or garantia_antecipada is null)
					ORDER BY tipo_pedido;";

			#HD 47695
			if (strlen($tipo_pedido)> 0 AND $permite_alteracao == 't'){
				$sql = "SELECT   *
						FROM     tbl_tipo_pedido
						WHERE    fabrica = $login_fabrica ";
				if (strlen($tipo_pedido)>0){
					$sql .= " AND      tbl_tipo_pedido.tipo_pedido = $tipo_pedido ";
				}
				$sql .= " ORDER BY tipo_pedido;";
			}

			$res = pg_exec ($con,$sql);

			# takashi -  coloquei : AND      (garantia_antecipada is false or garantia_antecipada is null)
			# efetuei testes em suggar, lenoxx, latina caso ocorra erro retirar linha a cima

			for ($i = 0 ; $i < pg_numrows ($res) ; $i++ ) {
				echo "<option value='" . pg_result($res,$i,tipo_pedido) . "'";
				if (pg_result ($res,$i,tipo_pedido) == $tipo_pedido) echo " selected";
				echo ">" . pg_result($res,$i,descricao) . "</option>";
			}
			if($garantia_antecipada=="t"){
				#takashi - efetuei testes em suggar, lenoxx, latina caso ocorra erro retirar esse if
				$sql = "SELECT   *
						FROM     tbl_tipo_pedido
						WHERE    fabrica = $login_fabrica
						AND garantia_antecipada is true
						ORDER BY tipo_pedido ";
				$res = pg_exec ($con,$sql);

				for ($i = 0 ; $i < pg_numrows ($res) ; $i++ ) {
					echo "<option value='" . pg_result($res,$i,tipo_pedido) . "'";
					if (pg_result ($res,$i,tipo_pedido) == $tipo_pedido){
						echo " selected";
					}
					echo ">" . pg_result($res,$i,descricao) . "</option>";
				}
			}
			echo "</select>";
		}
		?>
		</p>

		<?#-------------------- Transportadora -------------------


		#HD 47695 - Para pedidos a serem alterados, nao mostrar a transportadora.
		if ($permite_alteracao != 't'){
			$sql = "SELECT	tbl_transportadora.transportadora        ,
							tbl_transportadora.cnpj                  ,
							tbl_transportadora.nome                  ,
							tbl_transportadora_fabrica.codigo_interno
					FROM	tbl_transportadora
					JOIN	tbl_transportadora_fabrica USING(transportadora)
					JOIN	tbl_fabrica USING(fabrica)
					WHERE	tbl_transportadora_fabrica.fabrica        = $login_fabrica
					AND		tbl_transportadora_fabrica.ativo          = 't'
					AND		tbl_fabrica.pedido_escolhe_transportadora = 't'";
			$res = pg_exec ($con,$sql);
			if (pg_numrows ($res) > 0) {
			?>
				<p><span class='coluna1'>Transportadora</span>
				<?
					if (pg_numrows ($res) <= 20) {
						echo "<select name='transportadora' class='frm'>";
						echo "<option selected></option>";
						for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
							echo "<option value='".pg_result($res,$i,transportadora)."' ";
							if ($transportadora == pg_result($res,$i,transportadora) ) echo " selected ";
							echo ">";
							echo pg_result($res,$i,codigo_interno) ." - ".pg_result($res,$i,nome);
							echo "</option>\n";
						}
						echo "</select>";
					}else{
						echo "<input type='hidden' name='transportadora' value='' value='$transportadora'>";
						echo "<input type='hidden' name='transportadora_cnpj' value='$transportadora_cnpj'>";

						#echo "<input type='text' name='transportadora_cnpj' size='20' maxlength='18' value='$transportadora_cnpj' class='textbox' >&nbsp;<img src='imagens/btn_buscar5.gif' border='0' align='absmiddle' onclick=\"javascript: fnc_pesquisa_transportadora (document.frm_pedido.transportadora_cnpj,'cnpj')\" style='cursor:pointer;'>";

						echo "<input type='text' name='transportadora_codigo' size='5' maxlength='10' value='$transportadora_codigo' class='textbox' onblur='javascript: lupa_transportadora_codigo.click()'>&nbsp;<img id='lupa_transportadora_codigo' src='imagens/btn_lupa_novo.gif' border='0' align='absmiddle' onclick=\"javascript: fnc_pesquisa_transportadora (document.frm_pedido.transportadora_codigo,'codigo')\" style='cursor:pointer;'>";

						//echo "<input type='text' name='transportadora_nome' size='35' maxlength='50' value='$transportadora_nome' class='textbox' onblur='javascript: lupa_transportadora_nome.click()'>&nbsp;<img id='lupa_transportadora_nome' src='imagens/btn_buscar5.gif' border='0' align='absmiddle' onclick=\"javascript: fnc_pesquisa_transportadora (document.frm_pedido.transportadora_nome,'nome')\" style='cursor:pointer;'>";
						echo "<input type='text' name='transportadora_nome' size='35' maxlength='50' value='$transportadora_nome' class='textbox' >&nbsp;<img id='lupa_transportadora_nome' src='imagens/btn_lupa_novo.gif' border='0' align='absmiddle' onclick=\"javascript: fnc_pesquisa_transportadora (document.frm_pedido.transportadora_nome,'nome')\" style='cursor:pointer;'>";
					}
					?>
				</p>
		<? }
		}?>


		<?#-------------------- Linha do pedido -------------------

		#HD 47695 - Para alterar o pedido, mas nao a linha, por causa da tabela de preço
		if ($permite_alteracao == 't' and strlen($linha)>0){
			?><input type="hidden" name="linha" value="<? echo $linha; ?>"><?
		}else{
			$sql = "SELECT	tbl_linha.linha            ,
							tbl_linha.nome
					FROM	tbl_linha
					JOIN	tbl_fabrica USING(fabrica)
					JOIN	tbl_posto_linha  ON tbl_posto_linha.posto = $login_posto
											AND tbl_posto_linha.linha = tbl_linha.linha
					WHERE	tbl_fabrica.linha_pedido is true
					AND     tbl_linha.fabrica = $login_fabrica ";

			// BLOQUEIO DE PEDIDOS PARA A LINHA ELETRO E BRANCA EM
			// CASO DE INADIMPLÊNCIA
			// Não bloqueia pedidos do JANGADA - CARMEM LUCIA
			if ($login_fabrica == 3 and $login_bloqueio_pedido == 't' and $login_posto <> 1053) {
				$sql .= "AND tbl_linha.linha NOT IN (2,4)";
			}
			if($login_fabrica == 51){
				$sql .= " AND tbl_linha.ativo IS TRUE ";
			}
			#permite_alteracao - HD 47695
			if (strlen($tipo_pedido)> 0 AND $permite_alteracao == 't' and strlen($linha)>0){
				$sql .= " AND tbl_linha.linha = $linha ";
			}
			$res = pg_exec ($con,$sql);
			if (pg_numrows ($res) > 0) {
			?>
				<p><span class='coluna1'>Linha</span>
						<?
						echo "<select name='linha' class='frm' ";
						if ($login_fabrica == 3) echo " onChange='exibeTipo()'";
						echo ">";
						echo "<option selected></option>";
						for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
							echo "<option value='".pg_result($res,$i,linha)."' ";
							if ($linha == pg_result($res,$i,linha) ) echo " selected";
							echo ">";
							echo pg_result($res,$i,nome);
							echo "</option>\n";
						}
						echo "</select>";
						?>
				</p>
			<?
			}
		}
		?>

		<h4>Peças</h4>

		<? if($login_fabrica<>24 and $login_fabrica<>30){ //HD 70768-Retirar estes campos para a Esmaltec ?>
			<p><span class='coluna1'>Referência Produto</span><input class="frm" type="text" name="produto_referencia" size="15" maxlength="20" value="<? echo $produto_referencia ?>">&nbsp;<img src='imagens/btn_lupa_novo.gif' style="cursor:pointer" border='0' alt="Clique para pesquisar pela referência do produto" align='absmiddle' onclick="javascript: fnc_pesquisa_produto (document.frm_pedido.produto_referencia,document.frm_pedido.produto_descricao,'referencia',document.frm_pedido.produto_voltagem)">
			</p>

			<p><span class='coluna1'>Descrição Produto</span><input class="frm" type="text" name="produto_descricao" size="30" value="<? echo $produto_descricao ?>">&nbsp;<img src='imagens/btn_lupa_novo.gif' style="cursor:pointer" border='0' align='absmiddle' alt="Clique para pesquisar pela descrição do produto" onclick="javascript: fnc_pesquisa_produto (document.frm_pedido.produto_referencia,document.frm_pedido.produto_descricao,'descricao',document.frm_pedido.produto_voltagem)"><input type="hidden" name="produto_voltagem">
			</p>
			<br>
		<? }else{ ?>
			<input class="frm" type="hidden" name="produto_referencia" value="<? echo $produto_referencia; ?>">
		<? } ?>

		<!-- Peças -->
		<p>
		<table border="0" cellspacing="0" cellpadding="2" align="center" class='xTabela' id="tabela_itens">
			<tr height="20" bgcolor="#CDDBF1">
				<td align='center'><?
				if($login_fabrica<>6){?>Referência Componente<? }else{?> Código Componente<? }?></td>
				<td align='center'>Descrição Componente</font></td>
				<td align='center'>Qtde</td>
				<? if ($login_fabrica != 14 AND $login_fabrica!=24) { ?>
				<td align='center'>Preço</td>
				<? } ?>
			</tr>
			
			<? 

			if (strlen($pedido)>0) {
			$sql = "SELECT count(*) from tbl_pedido_item where pedido = $pedido";
			$res = pg_exec($con,$sql);
			
			$qtde_item = pg_result($res,0,0);
			
			}


			if (strlen($qtde_item)==0) {
				$qtde_item = 1;
			}
			for ($i = 0 ; $i < $qtde_item ; $i++) {

				if (strlen($pedido) > 0){// AND strlen ($msg_erro) == 0
					$sql = "SELECT  tbl_pedido_item.pedido_item,
									tbl_peca.referencia        ,
									tbl_peca.descricao         ,
									tbl_pedido_item.qtde       ,
									tbl_pedido_item.preco
							FROM  tbl_pedido
							JOIN  tbl_pedido_item USING (pedido)
							JOIN  tbl_peca        USING (peca)
							WHERE tbl_pedido_item.pedido = $pedido
							AND   tbl_pedido.posto   = $login_posto
							AND   tbl_pedido.fabrica = $login_fabrica
							ORDER BY tbl_pedido_item.pedido_item";
							
					$res = pg_exec ($con,$sql);

					if (pg_numrows($res) > 0) {
						$pedido_item     = trim(@pg_result($res,$i,pedido_item));
						$peca_referencia = trim(@pg_result($res,$i,referencia));
						$peca_descricao  = trim(@pg_result($res,$i,descricao));
						$qtde            = trim(@pg_result($res,$i,qtde));
						$preco           = trim(@pg_result($res,$i,preco));
						if (strlen($preco) > 0) $preco = number_format($preco,2,',','.');
					}else{
						$pedido_item     = $_POST["pedido_item_"     . $i];
						$peca_referencia = $_POST["peca_referencia_" . $i];
						$peca_descricao  = $_POST["peca_descricao_"  . $i];
						$qtde            = $_POST["qtde_"            . $i];
						$preco           = $_POST["preco_"           . $i];
					}
				}else{
					$pedido_item     = $_POST["pedido_item_"     . $i];
					$peca_referencia = $_POST["peca_referencia_" . $i];
					$peca_descricao  = $_POST["peca_descricao_"  . $i];
					$qtde            = $_POST["qtde_"            . $i];
					$preco           = $_POST["preco_"           . $i];
				}

				$peca_referencia = trim ($peca_referencia);

				#--------------- Valida Peças em DE-PARA -----------------#
				$tem_obs = false;
				$linha_obs = "";

				$sql = "SELECT para FROM tbl_depara WHERE de = '$peca_referencia' AND fabrica = $login_fabrica";
				$resX = pg_exec ($con,$sql);
				
				if (pg_numrows ($resX) > 0) {
					$linha_obs = "Peça original " . $peca_referencia . " mudou para o código acima <br>&nbsp;";
					$peca_referencia = pg_result ($resX,0,0);
					$tem_obs = true;
				}

				#--------------- Valida Peças Fora de Linha -----------------#
				$sql = "SELECT * FROM tbl_peca_fora_linha WHERE referencia = '$peca_referencia' AND fabrica = $login_fabrica";
				
				$resX = pg_exec ($con,$sql);
				if (pg_numrows ($resX) > 0) {
					$libera_garantia = pg_result ($resX,0,libera_garantia);
					#17624
					if ($login_fabrica==3 AND $libera_garantia=='t'){
						$linha_obs .= "Peça acima está fora de linha. Disponível somente para garantia. Caso necessário, favor contatar a Assistência Técnica Britânia <br>&nbsp;";
					}else{
						$linha_obs .= "Peça acima está fora de linha <br>&nbsp;";
					}
					$tem_obs = true;
				}

				if (strlen ($peca_referencia) > 0) {
					$sql = "SELECT descricao FROM tbl_peca WHERE referencia = '$peca_referencia' AND fabrica = $login_fabrica";
					$resX = pg_exec ($con,$sql);
					if (pg_numrows ($resX) > 0) {
						$peca_descricao = pg_result ($resX,0,0);
					}
				}

				$peca_descricao = trim ($peca_descricao);

				$cor="";
				if ($linha_erro == $i and strlen ($msg_erro) > 0) $cor='#ffcccc';
				if ($linha_erro == $i and strlen ($msg_erro) > 0) $cor='#ffcccc';
				if ($tem_obs) $cor='#FFCC33';
			?>
				<tr bgcolor="<? echo $cor ?>">
					<td align='left'>
						<input class="frm" type="text" id="peca_referencia_<? echo $i; ?>" name="peca_referencia_<? echo $i; ?>" size="10" onfocus="autocompletar('peca_referencia_<? echo $i; ?>',this.value,'peca_descricao_<? echo $i ?>')" value="<? echo $peca_referencia; ?>">
						<input type="hidden" name="pedido_item_<? echo $i ?>" id="pedido_item_<? echo $i ?>" size="15" value="<? echo $pedido_item; ?>">
					</td>
					<td align='left'>
					<input type="hidden" name="posicao">
					<input class="frm" type="text" id="peca_descricao_<? echo $i ?>" name="peca_descricao_<? echo $i ?>" size="50" onfocus="autocompletar_descricao('peca_descricao_<? echo $i; ?>',this.value,'peca_referencia_<? echo $i ?>')" value="<? echo $peca_descricao ?>">
					</td>
					<td align='center'>
					<input class="frm" type="text" name="qtde_<? echo $i ?>" size="5" maxlength='5' onblur="validacao_campos(<?=$i;?>);javascript: if(validacao.value != 'sim') { function1(<?echo $i;?>); };" value="<? echo $qtde ?>">
					</td>
					<? if ($login_fabrica==24){ ?>
					<input type="hidden" name="preco_<? echo $i ?>" value="<? echo $preco ?>">
					 <? } ?>
				</tr>

				<?
				if ($tem_obs) {
					echo "<tr bgcolor='#FFCC33' style='font-size:12px'>";
					echo "<td colspan='4'>$linha_obs</td>";
					echo "</tr>";
				}
				?>

			<?
			}
			?>
			<input type='hidden' name='validacao' id='validacao' value='sim'>
			<input type='hidden' id='qtde_item' name='qtde_item'>
			</table>
		</p>
		<p><center>
		<input type="hidden" name="btn_acao" value="">
		<img src='imagens/btn_gravar.gif' onclick="javascript: if (document.frm_pedido.btn_acao.value == '' ) { document.frm_pedido.btn_acao.value='gravar' ; document.frm_pedido.submit() } else { alert ('Aguarde submissão') }" ALT="Gravar pedido" border='0' style='cursor: pointer'>
		</center>
		</p>
</div>
</li>
</ul>
<!-- Fecha Divisão-->

</form>
<br clear='both'>
<p>

<? include "rodape.php"; ?>
