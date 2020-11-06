<?php

include "dbconfig.php";
include "includes/dbconnect-inc.php";
include "includes/funcoes.php";

$admin_privilegios="cadastros";
include "autentica_admin.php";

include "funcoes.php";
if($login_fabrica == 1){
    require "../classes/ParametrosAdicionaisFabrica.php";
    $parametrosAdicionaisObject = new ParametrosAdicionaisFabrica($login_fabrica);
    
    require "../classes/form/GeraComboType.php";
}
$msg = "";

if (strlen($_POST["acao"]) > 0) $acao = strtoupper($_POST["acao"]);

if ($acao == "GRAVAR") {

	$posto_codigo = trim ($_POST['posto_codigo']);
	$posto_codigo = str_replace ("-","",$posto_codigo);
	$posto_codigo = str_replace (".","",$posto_codigo);
	$posto_codigo = str_replace ("/","",$posto_codigo);
	$posto_codigo = substr($posto_codigo,0,14);

	$sql = "SELECT tbl_posto.posto
			FROM   tbl_posto
			JOIN   tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto
			AND    tbl_posto_fabrica.fabrica = $login_fabrica
			WHERE  tbl_posto_fabrica.codigo_posto = '$posto_codigo'
			OR     tbl_posto.cnpj = '$posto_codigo'";
	$res = pg_exec ($con, $sql);
	if (pg_numrows($res) == 1)
		$posto = @pg_result ($res,0,0);
	else
		$msg = "Selecione o posto";

	$produto_referencia = trim($_POST["produto_referencia"]);
	$produto_descricao  = trim($_POST["produto_descricao"]);
	$produto_voltagem   = trim($_POST["produto_voltagem"]);
	$type               = trim($_POST["type"]);
	$serie              = trim($_POST["serie"]);
	$codigo_fabricacao  = trim($_POST["codigo_fabricacao"]);
	$pedido             = trim($_POST["pedido"]);
	$nota_fiscal        = trim($_POST["nota_fiscal"]);
	$data_emissao       = trim($_POST["data_emissao"]);
	$data_vencimento    = trim($_POST["data_vencimento"]);

	if (strlen($produto_referencia) > 0 || strlen($produto_voltagem) > 0) {
		$sql = "SELECT tbl_produto.produto, tbl_produto.linha
				FROM tbl_produto
				JOIN tbl_linha USING (linha)
				WHERE tbl_linha.fabrica = $login_fabrica";
		if (strlen($produto_referencia) > 0) {
			$x_produto_referencia = str_replace(".","",$produto_referencia);
			$x_produto_referencia = str_replace("-","",$x_produto_referencia);
			$x_produto_referencia = str_replace("/","",$x_produto_referencia);
			$x_produto_referencia = str_replace(" ","",$x_produto_referencia);
			$sql .= " AND tbl_produto.referencia_pesquisa = '$x_produto_referencia'";
		}
		if (strlen($produto_voltagem) > 0) {
			$sql .= " AND tbl_produto.voltagem = '$produto_voltagem'";
		}
		$res = pg_exec($con,$sql);
		if (pg_numrows($res) == 0) {
			$msg .= " Produto digitado não encontrado. ";
		} else if (pg_numrows($res) == 1) {
			$produto = trim(pg_result($res,0,produto));
			$linha   = pg_fetch_result($res, 0, 'linha');
		} else if (pg_numrows($res) > 0) {
			$msg .= " Favor preencher mais campos referente ao Produto. ";
		}
	}else{
		$msg .= " Favor preencher os campos referente ao Produto. ";
	}

	if (strlen($serie) == 0) {
		$msg .= " Favor preencher o campo Número de Série. ";
	} else {
		$x_serie = "'" . $serie . "'";

		$sqlX = "SELECT locacao FROM tbl_locacao WHERE serie = $x_serie";
		$resX = pg_query ($con, $sqlX);

		if (pg_num_rows($resX) > 0) {
			$arr_locacao = array();
			while ($fetch = pg_fetch_array($resX)) {
				$arr_locacao[] = $fetch["locacao"];
			}
			$in_locacao = implode(', ', $arr_locacao);
		}

		if (isset($in_locacao)) {
//			$x_locacao = pg_result($resX, 0, 'locacao');
			//HD 774183 - verifica a referencia do produto da locação que ja existe para este nro. de série.
			//gabrielSilveira
			//INICIO

			/**
			 * @since HD 787699  - alterado
			 */
			$sql_locacao_produto = "

				SELECT tbl_produto.referencia FROM tbl_locacao
				JOIN tbl_produto on tbl_locacao.produto = tbl_produto.produto
				WHERE locacao in ($in_locacao)

			";
			$res_locacao_produto = pg_query($con,$sql_locacao_produto);

			$x_produto_referencia = preg_replace('/^([A-Z0-9]+).*/','$1',$produto_referencia);

			while ($fetch = pg_fetch_array($res_locacao_produto)) {
				$produto_locacao_referencia = $fetch['referencia'];
				$x_produto_locacao_referencia = preg_replace('/^([A-Z0-9]+).*/','$1',$produto_locacao_referencia);

				if ($x_produto_locacao_referencia == $x_produto_referencia){
					$msg .= " Número de Série já cadastrado para o produto de referencia: $produto_locacao_referencia ";
					break;
				}
			}

			//FIM
		}
	}

	if (strlen($codigo_fabricacao) == 0) {
		$msg .= " Favor preencher o campo Código de Fabricação. ";
	}

	if (strlen($pedido) == 0) {
		$msg .= " Favor preencher o campo Número do AE. ";
	}else{
		$x_pedido = "'" . $pedido . "'";
	}

	if (strlen($nota_fiscal) == 0) {
		$msg .= " Favor preencher o campo Nota Fiscal da B&D. ";
	}else{
		$x_nota_fiscal = "'" . $nota_fiscal . "'";
	}

	$x_data_emissao = fnc_formata_data_pg($data_emissao);
	if (strlen($x_data_emissao) == 0 || $x_data_emissao == "null") {
		$msg .= " Favor preencher o campo Data de Emissão. ";
	}

	if ($linha == 687) {
		$prazo_garantia = '1 year';
	} else {
		$prazo_garantia = '6 months';
	}

	if (strlen($produto) > 0) {
		$sql =	"SELECT tbl_lista_basica.lista_basica
				FROM    tbl_lista_basica
				WHERE   tbl_lista_basica.fabrica = $login_fabrica
				AND     tbl_lista_basica.produto = $produto
				AND     tbl_lista_basica.type IS NOT NULL;";
		$res = pg_exec($con,$sql);

		if (pg_numrows($res) > 0) {
			$sql =	"SELECT tbl_lista_basica.lista_basica
					FROM    tbl_lista_basica
					WHERE   tbl_lista_basica.fabrica = $login_fabrica
					AND     tbl_lista_basica.produto = $produto
					AND     tbl_lista_basica.type    = '$type';";
			$res = pg_exec($con,$sql);
			if (pg_numrows($res) == 0) {
				$msg .= " Type informado não pertence a este produto. ";
			}else{
				$x_type = "'" . $type . "'";
			}
		}else{
			$x_type = "null";
		}
	}

	if (strlen($msg) == 0) {
		$sql =	"INSERT INTO tbl_locacao (
								posto,
								produto,
								type,
								serie,
								codigo_fabricacao,
								data_fabricacao,
								pedido,
								nota_fiscal,
								data_emissao,
								data_vencimento,
								execucao
							) VALUES (
								$posto,
								$produto,
								$x_type,
								$x_serie,
								'$codigo_fabricacao',
								current_date,
								$x_pedido,
								$x_nota_fiscal,
								$x_data_emissao,
								($x_data_emissao::date + (('$prazo_garantia')::interval))::date,
								'Locação'
							)";
		$res = pg_query($con, $sql);

		if (!pg_last_error()) {
			$msg_sucesso = 'Cadastrado com sucesso!';

			// Limpa os campos depois de gravar
			$posto_codigo = '';
			$posto_nome = '';
			$produto_referencia = '';
			$produto_descricao = '';
			$produto_voltagem = '';
			$type = '';
			$serie = '';
			$codigo_fabricacao = '';
			$pedido = '';
			$nota_fiscal = '';
			$data_emissao = '';
		} else {
			$msg = 'Erro ao cadastrar.';
		}
	}
}

$layout_menu = "cadastro";
$title = "OS DE LOCAÇÃO";

include "cabecalho.php";

?>

<style type="text/css">

.titulo_tabela{
	background-color:#596d9b;
	font: bold 14px "Arial";
	color:#FFFFFF;
	text-align:center;
}

.msg_erro{
	background-color:#FF0000;
	font: bold 16px "Arial";
	color:#FFFFFF;
	text-align:center;
}

.sucesso{
    background-color:#008000;
    font: bold 14px "Arial";
    color:#FFFFFF;
    text-align:center;
}

.formulario{
	background-color:#D9E2EF;
	font:11px Arial;
	text-align:left;
}

.espaco{
	padding-left:20px;
}

</style>

<?
	include "javascript_calendario.php";
	include "javascript_pesquisas.php";


?>
<script type='text/javascript' src='js/jquery.alphanumeric.js'></script>
<script language="javascript">
$(function(){
	$("#data_emissao").maskedinput('99/99/9999');
	$("#data_emissao").datePicker({startDate : "01/01/2000"});

	$("input[name=pedido]").numeric();
});
function fnc_pesquisa_posto2 (campo, campo2, tipo) {

	if (tipo == "codigo" ) {
		var xcampo = campo;
	}

	if (tipo == "nome" ) {
		var xcampo = campo2;
	}

	if (xcampo.value != "") {
		var url = "";
		url = "posto_pesquisa_2.php?campo=" + xcampo.value + "&tipo=" + tipo + "&proximo=";
		janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=600, height=400, top=18, left=0");
		janela.codigo  = campo;
		janela.nome    = campo2;
		if ("<? echo $pedir_sua_os; ?>" == "t") {
			janela.proximo = document.forms[0].sua_os;
		}else{
			janela.proximo = document.forms[0].data_abertura;
		}
		janela.focus();
	}

	else{
		alert("Informe toda ou parte da informação para realizar a pesquisa");
	}
}
//-->
</script>

<br>

<form name="frm_locacao" method="post" action="<? echo $PHP_SELF ?>">

<input type="hidden" name="acao">

<table width="700" border="0" cellpadding="2" cellspacing="1"  align='center' class="formulario">
	<?php if (strlen($msg) > 0) { ?>
		<tr class="msg_erro">
			<td colspan="4"><?echo $msg?></td>
		</tr>
	<? } ?>

	<?php if (strlen($msg_sucesso) > 0) { ?>
		<tr class="sucesso">
			<td colspan="4"><?echo $msg_sucesso?></td>
		</tr>
	<? } ?>

	<tr class="titulo_tabela">
		<td colspan="4">Cadastrar Ordem de Serviço de Locação</td>
	</tr>

	<tr>
		<td nowrap  class="espaco">Código do Posto</td>
		<td nowrap colspan="3">Nome do Posto</td>
	</tr>
	<tr>
		<td nowrap class="espaco"><input class="frm" type="text" name="posto_codigo" size="15" value="<? echo $posto_codigo ?>" <? if ($login_fabrica == 5) { ?> onblur="fnc_pesquisa_posto2 (document.forms[0].posto_codigo,document.forms[0].posto_nome,'codigo')" <? } ?>>&nbsp;<img src='imagens/lupa.png' border='0' align='absmiddle' style="cursor:pointer" onclick="javascript: fnc_pesquisa_posto2 (document.forms[0].posto_codigo,document.forms[0].posto_nome,'codigo')"></A></td>
		<td nowrap colspan="3"><input class="frm" type="text" name="posto_nome" size="50" value="<? echo $posto_nome ?>" <? if ($login_fabrica == 5) { ?> onblur="fnc_pesquisa_posto2 (document.forms[0].posto_codigo,document.forms[0].posto_nome,'nome')" <? } ?>>&nbsp;<img src='imagens/lupa.png' style="cursor:pointer" border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_posto2 (document.forms[0].posto_codigo,document.forms[0].posto_nome,'nome')" style="cursor:pointer;"></A></td>
	</tr>

	<tr>
		<td class="espaco">Referência</td>
		<td>Descrição</td>
		<td>Voltagem</td>
		<td>Type</td>
	</tr>
	<tr>
		<td class="espaco">
			<input type="text" name="produto_referencia" size="12" value="<? echo $produto_referencia; ?>" class="frm">
			&nbsp;
			<img src="imagens/lupa.png" border="0" align="absmiddle" style="cursor: pointer;" onclick="javascript: fnc_pesquisa_produto (document.frm_locacao.produto_referencia, document.frm_locacao.produto_descricao, 'referencia', document.frm_locacao.produto_voltagem)">
		</td>
		<td>
			<input type="text" name="produto_descricao" size="25" value="<? echo $produto_descricao; ?>" class="frm">
			&nbsp;
			<img src="imagens/lupa.png" border="0" align="absmiddle" style="cursor: pointer;" onclick="javascript: fnc_pesquisa_produto (document.frm_locacao.produto_referencia, document.frm_locacao.produto_descricao, 'descricao', document.frm_locacao.produto_voltagem)">
		</td>
		<td>
			<input type="text" name="produto_voltagem" size="8" value="<? echo $produto_voltagem; ?>" class="frm">
		</td>
		<td>
		    <? 
		     GeraComboType::makeComboType($parametrosAdicionaisObject, $type,null,array("class"=>"frm"));
		     echo GeraComboType::getElement();
		    ?>
			
		</td>
	</tr>

	<tr>
		<td class="espaco">Execução</td>
		<td>Número de Série</td>
		<td>Código Fabricação</td>
	</tr>
	<tr>
		<td class="espaco"><input type="text" name="execucao" size="12" value="Locação" class="frm" readonly></td>
		<td><input type="text" name="serie" size="12" value="<? echo $serie; ?>" class="frm"></td>
		<td><input type="text" name="codigo_fabricacao" size="12" value="<? echo $codigo_fabricacao; ?>" class="frm"></td>
	</tr>

	<tr>
		<td class="espaco">Número do AE</td>
		<td>Nota Fiscal da B&D</td>
		<td>Data de Emissão</td>
	</tr>
	<tr>
		<td class="espaco"><input type="text" name="pedido" size="12" value="<? echo $pedido; ?>" class="frm"></td>
		<td><input type="text" name="nota_fiscal" size="12" value="<? echo $nota_fiscal; ?>" class="frm"></td>
		<td><input type="text" name="data_emissao" id='data_emissao' size="12" value="<? echo $data_emissao; ?>" class="frm"></td>
	</tr>

	<tr>
		<td colspan="100%">
			&nbsp;
		</td>
	</tr>

	<tr>
		<td colspan="100%" align="center">
			<input type="button" value="Gravar" border='0' onclick="javascript: if (document.frm_locacao.acao.value == '') { document.frm_locacao.acao.value='GRAVAR'; document.frm_locacao.submit(); } else { alert('Aguarde submissão'); }" alt="Gravar Pedido" style="cursor: hand;">
			<input type="button" value="Limpar" border='0' onclick="javascript: if (document.frm_locacao.acao.value == '') { document.frm_locacao.acao.value='LIMPAR'; window.location='<? echo $PHP_SELF ?>'; } else { alert('Aguarde submissão'); }" alt="Limpar Campos" style="cursor: hand;">
		</td>
	</tr>

</table>


</form>

<br>

<?
include "rodape.php";
?>
