<?

//Arquivo criado para atender HD 163220
//Desenvolvedor: Ébano Lopes
//
//Este arquivo faz a busca de consumidores através de vários parametros

include "dbconfig.php";
include "includes/dbconnect-inc.php";
include 'autentica_admin.php';

if ($_GET["exata"]) {
	$busca_exata = true;
}
else {
	$busca_exata = false;
}

if ($_GET["ajax"]) {
	$localizar = trim($_GET["q"]);
	$localizar_numeros = preg_replace( '/[^0-9]+/', '', $localizar);
	$resultados = "LIMIT 5";
	$busca_produtos = false;
}
else {
	$localizar = trim($_GET["localizar"]);
	$localizar_numeros = preg_replace( '/[^0-9]+/', '', $localizar);
	$busca_produtos = true;
?>

<!doctype html public "-//W3C//DTD HTML 4.0 Transitional//EN">
<html>
<head>
<title>Pesquisa Consumidores...</title>
<meta http-equiv=pragma content=no-cache>

<style>
	img {
		border: none;
	}

	.buscaprincipal {
		font-size: 8pt;
		font-family: Arial, Verdana, sans-serif;
	}

	.buscatitulo {
		background-color: #6688EE;
	}

	.buscalinha0 {
		background-color: #FFFFFF;
		overflow: hidden;
	}

	.buscalinha1 {
		background-color: #CCDDEE;
		overflow: hidden;
	}

	.instrucao {
		font-size:8pt;
		background-color: #DDDDFF;
		color: #222255;
	}

	.sematendimento {
		font-size: 7pt;
		font-weight: bold;
		background-color: #CC5555;
		color: #FFFFFF;
	}

	.semos {
		font-size: 7pt;
		font-weight: bold;
		background-color: #CC5555;
		color: #FFFFFF;
	}

	.buscalabel {
		text-align: right;
	}

	.buscainputtd {
	}

	.buscainput {
		border: 1px solid #555555;
		font-size: 8pt;
		font-family: Arial, Verdana, sans-serif;
	}
</style>

<script type="text/javascript" src="js/bibliotecaAJAX.js"></script>
<script language='javascript'>
formulario = window.opener.document.frm_callcenter;

//Esta função busca os dados da matriz de array consumidores e retorna para a janela principal
//Este array é alimentado por um código gerado em PHP neste mesmo programa
function retorna_dados_cliente(cliente) {
	formulario.consumidor_nome.value = consumidores[cliente]['nome'];
	formulario.consumidor_cpf.value = consumidores[cliente]['cpf_cnpj'];
	formulario.consumidor_rg.value = consumidores[cliente]['rg'];
	formulario.consumidor_email.value = consumidores[cliente]['email'];
	formulario.consumidor_fone.value = consumidores[cliente]['fone'];
	formulario.consumidor_cep.value = consumidores[cliente]['cep'];
	formulario.consumidor_endereco.value = consumidores[cliente]['endereco'];
	formulario.consumidor_numero.value = consumidores[cliente]['numero'];
	formulario.consumidor_complemento.value = consumidores[cliente]['complemento'];
	formulario.consumidor_bairro.value = consumidores[cliente]['bairro'];
	formulario.consumidor_cidade.value = consumidores[cliente]['nome_cidade'];
	formulario.consumidor_estado.value = consumidores[cliente]['estado'];
	//tipo
	formulario.consumidor_fone2.value = consumidores[cliente]['fone2'];
	formulario.consumidor_fone3.value = consumidores[cliente]['fone3'];

	if ((typeof formulario.consumidor_cpf != "undefined") && (typeof formulario.consumidor_cnpj != "undefined")) {
		if (consumidores[cliente]['cpf_cnpj'].length > 11) {
			formulario.consumidor_cnpj.checked = true;
		}
		else {
			formulario.consumidor_cpf.checked = true;
		}
	}

	if (typeof formulario.consumidor_revenda_c != "undefined") {
		switch (consumidores[cliente]['tipo']) {
			case "O":
				formulario.consumidor_revenda_c.checked = true;
			break;

			case "C":
				formulario.consumidor_revenda_c.checked = true;
			break

			case "R":
				formulario.consumidor_revenda_r.checked = true;
			break

			case "A":
				formulario.consumidor_revenda_a.checked = true;
			break
		}
	}
}

function preenche_os(cliente) {
	if (typeof formulario.os != "undefined") {
		for (var i in formulario.os) {
			formulario.os[i].value = consumidores[cliente]['sua_os'];
		}
	}
}

function retorna_dados_produto(cliente) {
	formulario.produto_referencia.value = consumidores[cliente]['produto_referencia'];
	formulario.produto.value = consumidores[cliente]['produto_id'];
	formulario.produto_nome.value = consumidores[cliente]['produto_descricao'];
	formulario.voltagem.value = consumidores[cliente]['produto_voltagem'];
	formulario.serie.value = consumidores[cliente]['serie'];
	formulario.nota_fiscal.value = consumidores[cliente]['nota_fiscal'];
	formulario.data_nf.value = consumidores[cliente]['data_nf'];

	if (typeof formulario.cnpj_revenda != "undefined") {
		formulario.cnpj_revenda.value = consumidores[cliente]['revenda_cnpj'];
	}

	formulario.mapa_linha.value = consumidores[cliente]['produto_linha'];
}

function retorna_dados_posto(cliente) {
	if (consumidores[cliente]['posto']) {
		url = "pesquisa_consumidor_callcenter_new_ajax.php?c=posto&fabrica=<?php echo $login_fabrica ?>&posto=" + consumidores[cliente]['posto'];
		requisicaoHTTP("GET", url, true, "trata_retorna_dados_posto", cliente);
	} else {
		window.close();
	}
}

function trata_retorna_dados_posto(retorno, cliente) {
	dados = retorno.split('|');
	formulario.mapa_estado.value = dados[0];
	formulario.mapa_cidade.value = dados[1];
	formulario.codigo_posto_tab.value = dados[2];
	formulario.posto_tab.value = consumidores[cliente]['posto'];
	formulario.posto_nome_tab.value = dados[3];
	formulario.posto_fone_tab.value = dados[4];
	formulario.posto_email_tab.value = dados[5];

	if (typeof formulario.codigo_posto != "undefined") {
		formulario.codigo_posto.value = dados[2];
	}

	if (typeof formulario.posto_nome != "undefined") {
		formulario.posto_nome.value = dados[3];
	}

	window.close();
}

function fnc_pesquisa_produto (campo, campo2, tipo) {
	if (tipo == "referencia" ) {
		var xcampo = campo;
	}

	if (tipo == "descricao" ) {
		var xcampo = campo2;
	}

	if (xcampo.value != "") {
		var url = "produto_pesquisa_2.php?campo=" + xcampo.value + "&tipo=" + tipo;

		if (typeof janela != "undefined") {
			if (janela != null && !janela.closed) {
				janela.location = url;
				janela.focus();
			}
			else if (janela != null && janela.closed) {
				janela = null;
			}
		}
		else {
			janela = null;
		}

		if (janela == null) {
			janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=600, height=400, top=18, left=0");
			janela = window.janela;
			janela.referencia   = campo;
			janela.descricao    = campo2;
			janela.focus();
		}
	}
}

function funcao_continuar_busca() {
	if (document.frm_callcenter.produto_referencia.value == '') {
		return(confirm("Continuar a busca sem informar o produto?\n\nATENÇÃO: desta forma o sistema não buscará o consumidor nas Ordens de Serviço"));
	}
	else {
		return true;
	}
}

</script>

</head>

<body style="margin: 0px 0px 0px 0px;">
<img src="imagens/pesquisa_consumidor.gif">

<?php
} //fim else do if ($_GET["ajax"])

if (strlen($localizar) < 5) {
	$msg_erro = "Valor de busca digitado incorreto ou em branco. Digite pelo menos 5 caracteres para a busca";
}

//Este array define em quais tabelas o sistema irá buscar consumidores
//	O: tlb_os
//	C: tbl_hd_chamado
//	R: tbl_revenda
//	A: tbl_posto
$buscarem = array("O", "C", "R", "A");

//Array que armazena os parametros da cláusula WHERE para filtrar a busca
$busca = array();

$separador_implode = "";			//Define com qual operador estará entre as cláusulas do WHERE
$separador_clausulas_where = "";	//Define qual operador estará entre as cláusulas fixas do WHERE e as definidas no array $busca

if ((strlen($localizar_numeros) == 0) && (in_array($_GET["tipo"], array("cpf", "cep", "telefone")))) {
	$_GET["tipo"] = "nenhum";
}

//HD 230057: Na busca por nomes, solicitar produto e estado quando for pesquisa por nome
//HD 216395: Desabilitando esta funcionalidade, a pesquisa de OS será na bi_os
if ($_GET["tipo"] == "nome" && strlen($_GET["estado"]) != 2 && false) {
?>

	<table class=buscaprincipal width=760>
		<tr>
			<td colspan=2 class=instrucao>
				<font style='font-size: 9pt; font-weight: bold;'>ATENÇÃO</font><br>
				Para efetuar busca pelo nome do consumidor, é obrigatório informar o estado.<br>
				<br>
				<font color='#DD0000'><b>BUSCA EM ORDENS DE SERVIÇO:</b></font> O PRODUTO é obrigatório para a consulta nas Ordens de Serviço. Caso o produto não seja informado o sistema buscará nos chamados do CallCenter e cadastros de postos e revendas.
			</td>
		</tr>
		<form id="frm_callcenter" name="frm_callcenter" method="get">
		<input type="hidden" name="tipo" value="<? echo $_GET["tipo"]; ?>" />
		<input type="hidden" name="localizar" value="<? echo $_GET["localizar"]; ?>" />
		<tr>
			<td class='buscalabel buscalinha0'>Estado:</td>
			<td align='left' class='buscalinha0'>
				<select name='estado' id='estado' class='buscainput'>
						<option value='AC'>Acre</option>
						<option value='AL'>Alagoas</option>
						<option value='AP'>Amapá</option>
						<option value='AM'>Amazonas</option>
						<option value='BA'>Bahia</option>
						<option value='CE'>Ceará</option>
						<option value='DF'>Distrito Federal</option>
						<option value='GO'>Goiás</option>
						<option value='ES'>Espírito Santo</option>
						<option value='MA'>Maranhão</option>
						<option value='MT'>Mato Grosso</option>
						<option value='MS'>Mato Grosso do Sul</option>
						<option value='MG'>Minas Gerais</option>
						<option value='PA'>Pará</option>
						<option value='PB'>Paraiba</option>
						<option value='PR'>Paraná</option>
						<option value='PE'>Pernambuco</option>
						<option value='PI'>Piauí</option>
						<option value='RJ'>Rio de Janeiro</option>
						<option value='RN'>Rio Grande do Norte</option>
						<option value='RS'>Rio Grande do Sul</option>
						<option value='RO'>Rondônia</option>
						<option value='RR'>Roraima</option>
						<option value='SP'>São Paulo</option>
						<option value='SC'>Santa Catarina</option>
						<option value='SE'>Sergipe</option>
						<option value='TO'>Tocantins</option>
				</select>
			</td>
		</tr>
		<tr>
			<td class='buscalabel buscalinha1'>Referência do Produto:</td>
			<td class='buscainputtd buscalinha1'>
				<input name="produto_referencia" class="buscainput" type="text" size="15" maxlength="15">
				<img src='imagens/lupa.png' border='0' align='absmiddle' style='cursor: pointer' onclick="javascript: fnc_pesquisa_produto(document.frm_callcenter.produto_referencia, document.frm_callcenter.produto_nome, 'referencia');">
			</td>
		</tr>
		<tr>
			<td class='buscalabel buscalinha0'>Descrição do Produto:</td>
			<td class='buscainputtd buscalinha0'>
				<input name="produto_nome" class="buscainput" type="text" size="35" maxlength="500">
				<img src='imagens/lupa.png' border='0' align='absmiddle' style='cursor: pointer' onclick="javascript: fnc_pesquisa_produto(document.frm_callcenter.produto_referencia, document.frm_callcenter.produto_nome, 'descricao');">
			</td>
		</tr>
		<tr>
			<td class='buscalabel buscalinha1'></td>
			<td class='buscainputtd buscalinha1'>
				<input type="submit" class="buscainput" value="Continuar Busca" onclick="return funcao_continuar_busca();">
			</td>
		</tr>
	</table>
	<script language='javascript'>
		document.getElementById("estado").focus();
	</script>

<?
	die;
}
else {
	$estado = $_GET["estado"];

	if ($_GET["produto_referencia"]) {
		$produto_referencia = $_GET["produto_referencia"];

		$sql = "
		SELECT
		produto,
		descricao

		FROM
		tbl_produto
		JOIN tbl_linha ON tbl_produto.linha=tbl_linha.linha

		WHERE
		tbl_linha.fabrica=$login_fabrica
		AND tbl_produto.referencia='$produto_referencia'

		LIMIT 1
		";
		$res = pg_query($con, $sql);

		if (pg_num_rows($res)) {
			$produto = pg_result($res, 0, produto);
			$produto_descricao = pg_result($res, 0, descricao);
		}
		else {
			$produto = "";
		}
	}
}

switch($_GET["tipo"]) {
	case "cpf":
		//cpf/cnpj - busca somente CPF/CNPJ completos, separados ou não por pontos ou traços
		if((strlen($localizar_numeros) == 11 && strlen($localizar) <= 14) || (strlen($localizar_numeros) == 14 && strlen($localizar) <= 18)) {
			$busca["O"][] = "AND (tbl_os.consumidor_cpf = '$localizar_numeros')";
			$busca["C"][] = "AND (tbl_hd_chamado_extra.cpf = '$localizar_numeros')";
			$busca["R"][] = "AND (tbl_revenda.cnpj = '$localizar_numeros')";
			$busca["A"][] = "AND (tbl_posto.cnpj = '$localizar_numeros')";
		}
		else {
			$msg_erro = "Valor de busca digitado incorreto ou em branco. O CPF deve ser digitado com 11 dígitos, podendo ou não conter pontos e traços";
		}
	break;

	case "nome":
		//nome - não pode conter números
		if(($localizar_numeros != $localizar) && (strlen($localizar_numeros) == 0) && ((strlen($localizar) - strlen($localizar_numeros)) >= 5)) {
			if ($busca_exata) {
				$busca["O"][] = "AND tbl_os.consumidor_nome = '$localizar'";
				$busca["C"][] = "AND tbl_hd_chamado_extra.nome = '$localizar'";
				$busca["R"][] = "AND tbl_revenda.nome = '$localizar'";
				$busca["A"][] = "AND tbl_posto.nome = '$localizar'";
			}
			else {
				$busca["O"][] = "AND tbl_os.consumidor_nome LIKE '$localizar%'";
				$busca["C"][] = "AND tbl_hd_chamado_extra.nome LIKE '$localizar%'";
				$busca["R"][] = "AND tbl_revenda.nome LIKE '$localizar%'";
				$busca["A"][] = "AND tbl_posto.nome LIKE '$localizar%'";
			}
		}
		else {
			$msg_erro = "Valor de busca digitado incorreto ou em branco. O nome do consumidor não deve conter números";
		}
	break;

	case "atendimento":
		//atendimento - somente números
		if ($localizar_numeros == $localizar) {
			$buscarem = array("C");
			$busca["C"][] = "AND tbl_hd_chamado.hd_chamado=$localizar";
		}
		else {
			$msg_erro = "Valor de busca digitado incorreto ou em branco. O número do atendimento deve ser composto somente por números";
		}
	break;

	case "os":
		//os - busca OSs com tamanho de no mínimo 5 números e com no máximo 3 separadores não numéricos
		if ($localizar_numeros && (strlen($localizar_numeros)+3 >= strlen($localizar)) && strlen($localizar_numeros) > 5) {
			$buscarem = array("O");
			$busca["O"][] = "AND tbl_os.sua_os='$localizar'";
		}
		else {
			$msg_erro = "Valor de busca digitado incorreto ou em branco. O número da OS deve ser composto apenas por números, contendo separador ou não";
		}
	break;

	case "serie":
		$buscarem = array("O");
		$busca["O"][] = "AND tbl_os.serie='" . strtoupper($localizar) . "'";
	break;

	case "cep":
		if (strlen($localizar_numeros) == 8) {
			$busca["O"][] = "AND (tbl_os.consumidor_cep = '$localizar_numeros')";
			$busca["C"][] = "AND (tbl_hd_chamado_extra.cep = '$localizar_numeros')";
			$busca["R"][] = "AND (tbl_revenda.cep = '$localizar_numeros')";
			$busca["A"][] = "AND (tbl_posto.cep = '$localizar_numeros')";
		}
		else {
			$msg_erro = "Valor de busca digitado incorreto ou em branco. O CEP deve ser digitado com 8 dígitos, podendo ou não conter o traço";
		}
	break;

	case "telefone":
		if (strlen($localizar_numeros) >= 8) {
			$pesquisa_os = "* O sistema está pesquisando somente nas OSs à partir de ".(Date('Y') - 2)."<br />";
			if ($busca_exata) {
				$busca["O"][] = "AND (tbl_os.consumidor_fone = '$localizar' OR  tbl_os.consumidor_fone = '$localizar_numeros') AND tbl_os.data_digitacao >= NOW() - INTERVAL '2 year' ";
				$busca["C"][] = "AND (tbl_hd_chamado_extra.fone = '$localizar' OR  tbl_hd_chamado_extra.fone = '$localizar_numeros')";
				$busca["R"][] = "AND (tbl_revenda.fone = '$localizar' OR  tbl_revenda.fone = '$localizar_numeros')";
				$busca["A"][] = "AND (tbl_posto.fone = '$localizar' OR  tbl_posto.fone = '$localizar_numeros')";
			}else {
				$busca["O"][] = "AND (tbl_os.consumidor_fone LIKE '%$localizar%' OR  tbl_os.consumidor_fone LIKE '%$localizar_numeros') AND tbl_os.data_digitacao >= NOW() - INTERVAL '2 year' ";
				$busca["C"][] = "AND (tbl_hd_chamado_extra.fone ILIKE '%$localizar%' OR  tbl_hd_chamado_extra.fone ILIKE '%$localizar_numeros')";
				$busca["R"][] = "AND (tbl_revenda.fone ILIKE '%$localizar%' OR  tbl_revenda.fone ILIKE '%$localizar_numeros')";
				$busca["A"][] = "AND (tbl_posto.fone ILIKE '%$localizar%' OR  tbl_posto.fone ILIKE '%$localizar_numeros')";
			}
		}else {
			$msg_erro = "Valor de busca digitado incorreto ou em branco. O telefone deve ser digitado com pelo menos 8 números, contendo separadores ou não";
		}
	break;

	case "todos":
		$separador_implode = " OR ";
		$separador_clausulas_where = " AND ";

		//cpf/cnpj - busca somente CPF/CNPJ completos, separados ou não por pontos ou traços
		if((strlen($localizar_numeros) == 11 && strlen($localizar) <= 14) || (strlen($localizar_numeros) == 14 && strlen($localizar) <= 18)) {
			$busca["O"][] = "(tbl_os.consumidor_cpf = '$localizar_numeros')";
			$busca["C"][] = "(tbl_hd_chamado_extra.cpf = '$localizar_numeros')";
			$busca["R"][] = "(tbl_revenda.cnpj = '$localizar_numeros')";
			$busca["A"][] = "(tbl_posto.cnpj = '$localizar_numeros')";
		}

		//nome - não pode conter números
		if(($localizar_numeros != $localizar) && (strlen($localizar_numeros) == 0) && ((strlen($localizar) - strlen($localizar_numeros)) >= 5)) {
			if ($busca_exata) {
//				$busca["O"][] = "tbl_os.consumidor_nome = '$localizar'";
				$busca["C"][] = "tbl_hd_chamado_extra.nome = '$localizar'";
				$busca["R"][] = "tbl_revenda.nome = '$localizar'";
				$busca["A"][] = "tbl_posto.nome = '$localizar'";
			}
			else {
//				$busca["O"][] = "tbl_os.consumidor_nome ILIKE '%$localizar%'";
				$busca["C"][] = "tbl_hd_chamado_extra.nome ILIKE '%$localizar%'";
				$busca["R"][] = "tbl_revenda.nome ILIKE '%$localizar%'";
				$busca["A"][] = "tbl_posto.nome ILIKE '%$localizar%'";
			}
		}

		//atendimento - somente números
		if ($localizar_numeros == $localizar) {
			$busca["C"][] = "tbl_hd_chamado.hd_chamado=$localizar";
		}

		//os - busca OSs com tamanho de no mínimo 5 números e com no máximo 3 separadores não numéricos
		if ($localizar_numeros && (strlen($localizar_numeros)+3 >= strlen($localizar)) && strlen($localizar_numeros) > 5) {
			$busca["O"][] = "AND tbl_os.sua_os='$localizar'";
		}

		//serie
		$busca["O"][] = "tbl_os.serie='" . strtoupper($localizar) . "'";

		//cep - busca CEPs com tamanho de 8 números e com no máximo 2 separadores não numéricos
		if (strlen($localizar_numeros == 8) && (strlen($localizar_numeros)+2 >= strlen($localizar))) {
			$busca["O"][] = "(tbl_os.consumidor_cep = '$localizar_numeros')";
			$busca["C"][] = "(tbl_hd_chamado_extra.cep = '$localizar_numeros')";
			$busca["R"][] = "(tbl_revenda.cep = '$localizar_numeros')";
			$busca["A"][] = "(tbl_posto.cep = '$localizar_numeros')";
		}

		//telefone
		if (strlen($localizar_numeros) > 8) {
			if ($busca_exata) {
				$busca["O"][] = "(regexp_replace(tbl_os.consumidor_fone, '[^0-9]*', '', 'g') = '$localizar_numeros')";
				$busca["C"][] = "(regexp_replace(tbl_hd_chamado_extra.fone, '[^0-9]*', '', 'g') = '$localizar_numeros')";
				$busca["R"][] = "(regexp_replace(tbl_revenda.fone, '[^0-9]*', '', 'g') = '$localizar_numeros')";
				$busca["A"][] = "(regexp_replace(tbl_posto.fone, '[^0-9]*', '', 'g') = '$localizar_numeros')";
			}
			else {
				$busca["O"][] = "(regexp_replace(tbl_os.consumidor_fone, '[^0-9]*', '', 'g') ILIKE '%$localizar_numeros%')";
				$busca["C"][] = "(regexp_replace(tbl_hd_chamado_extra.fone, '[^0-9]*', '', 'g') ILIKE '%$localizar_numeros%')";
				$busca["R"][] = "(regexp_replace(tbl_revenda.fone, '[^0-9]*', '', 'g') ILIKE '%$localizar_numeros%')";
				$busca["A"][] = "(regexp_replace(tbl_posto.fone, '[^0-9]*', '', 'g') ILIKE '%$localizar_numeros%')";
			}
		}
	break;

	default:
		$msg_erro = "Nenhum parametro válido foi informado para a busca";
}

if (strlen($estado) == 2) {
	$busca["O"][] = " AND tbl_os.consumidor_estado = '$estado'";
	$busca["C"][] = " AND tbl_hd_chamado_extra.cidade IN (SELECT cidade FROM tbl_cidade WHERE estado='$estado')";
	$busca["R"][] = " AND tbl_revenda.cidade IN (SELECT cidade FROM tbl_cidade WHERE estado='$estado')";
	$busca["A"][] = " AND tbl_posto.estado = '$estado'";
}

if (strlen($produto) > 0) {
	$busca["O"][] = " AND tbl_os.produto = $produto";
	$busca["C"][] = " AND tbl_hd_chamado_extra.produto = $produto";
}

if (in_array("O", $buscarem)) {
	if (is_array($busca["O"])) {
		if (in_array("C", $buscarem)) {
			$exclui_os_com_atendimento = "AND tbl_hd_chamado_extra.os IS NULL";
		}

		$busca_O = implode("$separador_implode", $busca["O"]);
	}
	else {
		$indice = array_search("O", $buscarem);
		unset($buscarem[$indice]);
	}
}

if (in_array("C", $buscarem)) {
	if (is_array($busca["C"])) {
		$busca_C = implode("$separador_implode", $busca["C"]);
	}
	else {
		$indice = array_search("C", $buscarem);
		unset($buscarem[$indice]);
	}
}

if (in_array("R", $buscarem)) {
	if (is_array($busca["R"])) {
		$busca_R = implode("$separador_implode", $busca["R"]);
	}
	else {
		$indice = array_search("R", $buscarem);
		unset($buscarem[$indice]);
	}
}

if (in_array("A", $buscarem)) {
	if (is_array($busca["A"])) {
		$busca_A = implode("$separador_implode", $busca["A"]);
	}
	else {
		$indice = array_search("A", $buscarem);
		unset($buscarem[$indice]);
	}
}

if ($_GET["tipo"] == "todos") {
	$busca_O = "(" . $busca_O . ")";
	$busca_C = "(" . $busca_C . ")";
	$busca_R = "(" . $busca_R . ")";
	$busca_A = "(" . $busca_A . ")";
}

if ($busca_produtos) {
	$busca_produtos_select_O = "
		tbl_produto.produto AS produto_id,
		tbl_produto.referencia || ' - ' || tbl_produto.descricao AS produto,
		tbl_produto.referencia AS produto_referencia,
		tbl_produto.descricao AS produto_descricao,
		tbl_produto.voltagem AS produto_voltagem,";

	$busca_produtos_select_C = "
		tbl_produto.produto AS produto_id,
		tbl_produto.referencia || ' - ' || tbl_produto.descricao AS produto,
		tbl_produto.referencia AS produto_referencia,
		tbl_produto.descricao AS produto_descricao,
		tbl_produto.voltagem AS produto_voltagem,";

	$busca_produtos_select = "
		0 as produto_id,
		'' AS produto,
		'' AS produto_referencia,
		'' AS produto_descricao,
		'' AS produto_voltagem,";

	$busca_produtos_from_O = "
		JOIN tbl_produto ON tbl_os.produto=tbl_produto.produto AND tbl_produto.fabrica_i = {$login_fabrica}";

	$busca_produtos_from_C = "
		LEFT JOIN tbl_produto ON tbl_hd_chamado_extra.produto=tbl_produto.produto  AND tbl_produto.fabrica_i = {$login_fabrica}";
}

$sql_busca["O"] = "
	(
		SELECT
		tbl_os.os as id,
		tbl_os.consumidor_nome AS nome,
		tbl_os.consumidor_endereco AS endereco,
		tbl_os.consumidor_numero AS numero,
		tbl_os.consumidor_complemento AS complemento,
		tbl_os.consumidor_bairro AS bairro,
		tbl_os.consumidor_cep AS cep,
		0 AS cidade,
		tbl_os.consumidor_fone AS fone,
		tbl_os.consumidor_fone_comercial AS fone2,
		tbl_os.consumidor_celular AS fone3,
		tbl_os.consumidor_cpf as cpf_cnpj,
		''::text AS rg,
		tbl_os.consumidor_email AS email,
		tbl_os.consumidor_cidade AS nome_cidade,
		tbl_os.consumidor_estado AS estado,
		tbl_os.sua_os AS sua_os,
		$busca_produtos_select_O
		tbl_os.serie,
		TO_CHAR(tbl_os.data_nf, 'DD/MM/YYYY') AS data_nf,
		tbl_os.nota_fiscal,
		'' AS status,
		'' AS categoria,
		tbl_hd_chamado_extra.hd_chamado AS referencia,
		tbl_os.posto,
		'O'::text AS tipo

		FROM
		tbl_os
		JOIN tbl_posto_fabrica ON tbl_os.posto=tbl_posto_fabrica.posto AND tbl_os.fabrica=tbl_posto_fabrica.fabrica
		$busca_produtos_from_O
		LEFT JOIN tbl_hd_chamado_extra ON tbl_os.os=tbl_hd_chamado_extra.os

		WHERE
		tbl_os.fabrica = $login_fabrica
		AND tbl_os.excluida IS NOT TRUE
		$separador_clausulas_where $busca_O

		$resultados
	)
";

$sql_busca["C"] = "
	(
		SELECT
		tbl_hd_chamado_extra.hd_chamado as id,
		tbl_hd_chamado_extra.nome,
		tbl_hd_chamado_extra.endereco,
		tbl_hd_chamado_extra.numero,
		tbl_hd_chamado_extra.complemento,
		tbl_hd_chamado_extra.bairro,
		tbl_hd_chamado_extra.cep,
		tbl_hd_chamado_extra.cidade,
		tbl_hd_chamado_extra.fone,
		tbl_hd_chamado_extra.fone2,
		tbl_hd_chamado_extra.celular AS fone3,
		tbl_hd_chamado_extra.cpf as cpf_cnpj,
		tbl_hd_chamado_extra.rg,
		tbl_hd_chamado_extra.email,
		tbl_cidade.nome AS nome_cidade,
		tbl_cidade.estado,
		(SELECT tbl_os.sua_os FROM tbl_os WHERE tbl_os.os=tbl_hd_chamado_extra.os AND tbl_os.fabrica = {$login_fabrica}) AS sua_os,
		$busca_produtos_select_C
		serie,
		TO_CHAR(data_nf, 'DD/MM/YYYY') AS data_nf,
		nota_fiscal,
		tbl_hd_chamado.status AS status,
		tbl_hd_chamado.categoria AS categoria,
		tbl_hd_chamado_extra.os AS referencia,
		tbl_hd_chamado.posto,
		'C'::text as tipo

		FROM
		tbl_hd_chamado_extra
		JOIN tbl_hd_chamado ON tbl_hd_chamado_extra.hd_chamado=tbl_hd_chamado.hd_chamado AND tbl_hd_chamado.fabrica = {$login_fabrica}
		LEFT JOIN tbl_cidade ON tbl_hd_chamado_extra.cidade=tbl_cidade.cidade
		$busca_produtos_from_C

		WHERE
		fabrica_responsavel = $login_fabrica
		$separador_clausulas_where $busca_C

		$resultados
	)
";

$sql_busca["R"] = "
	(
		SELECT
		tbl_revenda.revenda as id,
		tbl_revenda.nome,
		tbl_revenda.endereco,
		tbl_revenda.numero,
		tbl_revenda.complemento,
		tbl_revenda.bairro,
		tbl_revenda.cep,
		tbl_revenda.cidade,
		tbl_revenda.fone,
		tbl_revenda.fax AS fone2,
		'' AS fone3,
		tbl_revenda.cnpj as cpf_cnpj,
		''::text AS rg,
		tbl_revenda.email,
		tbl_cidade.nome AS nome_cidade,
		tbl_cidade.estado,
		'' AS sua_os,
		$busca_produtos_select
		'' AS serie,
		'' AS data_nf,
		'' AS nota_fiscal,
		'' AS status,
		'' AS categoria,
		0 AS referencia,
		0 AS posto,
		'R'::text AS tipo

		FROM
		tbl_revenda
		LEFT JOIN tbl_cidade USING (cidade)

		WHERE
		1=1
		$separador_clausulas_where $busca_R

		$resultados
	)
";

$sql_busca["A"] = "
	(
		SELECT
		tbl_posto.posto AS id,
		tbl_posto.nome AS nome,
		tbl_posto.endereco AS endereco,
		tbl_posto.numero AS numero,
		tbl_posto.complemento AS complemento,
		tbl_posto.bairro AS bairro,
		tbl_posto.cep AS cep,
		0 AS cidade,
		tbl_posto.fone AS fone,
		tbl_posto.fax AS fone2,
		'' AS fone3,
		tbl_posto.cnpj AS cpf_cnpj,
		''::text AS rg,
		tbl_posto.email AS email,
		tbl_posto.cidade AS nome_cidade,
		tbl_posto.estado AS estado,
		'' AS sua_os,
		$busca_produtos_select
		'' AS serie,
		'' AS data_nf,
		'' AS nota_fiscal,
		'' AS status,
		'' AS categoria,
		0 AS referencia,
		0 AS posto,
		'A'::text AS tipo

		FROM
		tbl_posto
		JOIN tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = {$login_fabrica}

		WHERE
		tbl_posto_fabrica.fabrica = $login_fabrica
		$separador_clausulas_where $busca_A

		$resultados
	)
";

$busca_sql_final = array();

//Este bloco de código verifica o array $buscarem para verificar quais opcoes
//de busca foram selecionadas. Para cada item do array $buscarem a rotina
//inserte no array $busca_sql_final a sql correspondente do array $sql_busca

foreach($buscarem AS $indice => $opcao) {
	$busca_sql_final[] = $sql_busca[$opcao];
}

if (count($sql_busca)) {
	$busca_sql_final = implode(" UNION ", $busca_sql_final);
}else {
	$msg_erro = "A busca não retornou resultados";
}
//Ao modificar a SQL abaixo, acrescentar campos na cláusula SELECT sempre no final,
//pois este arquivo é utilizado por um AJAX que usa a ordem dos campos para retornar dados
$sql = "
SELECT
*

FROM
(
	$busca_sql_final
) AS Dados
ORDER BY
tipo, id DESC
$resultados
";

//echo "<pre>$sql"; die;

if ($msg_erro) {
	if ($_GET["ajax"]) {
		echo "erro|$msg_erro";
	}
	else {
		echo "<div>$msg_erro</div>";
	}
}
elseif ($_GET["ajax"]) {
	$res = pg_query($con, $sql);

	for($i = 0; $i < pg_num_rows($res); $i++) {
		$valores = array();

		for($f = 0; $f < pg_num_fields($res); $f++) {
			$valores[] = pg_fetch_result($res, $i, $f);
		}

		$valores = implode("|", $valores);
		echo $valores . "\n";
	}
}else {
	//echo nl2br($sql);
	//exit;
	$res = pg_query($con, $sql);

	echo "
	<script language=javascript>
	consumidores = new Array();
	formulario = window.opener.document.frm_callcenter;
	</script>

	<table class=buscaprincipal width=760>
		<tr>
			<td colspan=7 class=instrucao>
				<font style='font-size: 9pt; font-weight: bold;'>ATENÇÃO</font><br>
				- Escolhendo o <b>número do atendimento</b>, irá continuar o atendimento selecionado<br>
				- Escolhendo o <b>número da Ordem de Serviço</b>:<br>
				... Caso <u>existir</u> atendimento:<br>
				....... Caso o atendimento <u><i>não esteja resolvido</i></u> irá continuar o atendimento selecionado<br>
				....... Caso o atendimento <u><i>esteja resolvido</i></u> irá abrir um novo chamado para o consumidor<br>
				... Caso <u>não existir</u> atendimento, irá abrir um novo atendimento com o número da OS selecionada<br>
				- Escolhendo o <b>nome do consumidor</b>, irá abrir um novo chamado para o consumidor<br>
				- Escolhendo o <b>produto</b>, irá abrir um novo chamado para o produto e consumidor da linha selecionada<br>
				<br>
				Pare o cursor do mouse sobre os itens para instruções / informações adicionais
			</td>
		</tr>";

		echo "

		<tr>
			<td height=5>
			</td>
		</tr>

		<tr>
			<td colspan=7 class=instrucao>
				<font style='font-size: 9pt; font-weight: bold;'>FILTROS EMPREGADOS</font><br>";

	echo "
				Tipo de busca: " . strtoupper($_GET["tipo"]) . "<br>";

	if ($estado) {
		echo "
				Estado: $estado<br>";
	}

	if ($produto) {
		echo "
				Produto: $produto_descricao<br>";
	}

	if(!empty($pesquisa_os)){
		echo $pesquisa_os;
	}

	echo "
			</td>
		</tr>

		<tr>
			<td height=5>
			</td>
		</tr>

		<tr>
			<td class=buscatitulo width=80>
				Atendimento
			</td>
			<td class=buscatitulo width=40>
				Data
			</td>
			<td class=buscatitulo width=200>
				Cliente
			</td>
			<td class=buscatitulo width=100>
				Ordem Serviço
			</td>
			<td class=buscatitulo width=110>
				Produto
			</td>
			<td class=buscatitulo width=60>
				Status
			</td>
			<td class=buscatitulo width=110>
				Tipo Atendimento
			</td>
		</tr>";

	$dados_javascript = "";

	for($i = 0; $i < pg_num_rows($res); $i++) {
		//Este bloco de código gera variáveis com os nomes dos campos, usando os recursos:
		//função: pg_num_fields() : retorna o número de campos (colunas) da sql
		//função: pg_field_name() : retorna o nome do campo
		//
		//Com o nome do campo na variável $campo, basta fazer $$campo = "valor"
		//Quando se usa dois $ antes de uma variável o PHP gera uma segunda variável com o nome sendo
		//o conteúdo da primeira, ex:
		//
		//$primeira = "teste";
		//$$primeira = "conteúdo segunda";
		//echo $teste;
		//
		//O código acima irá imprimir na tela o valor "conteúdo segunda". A variável $teste
		//foi gerada na segunda linha do código acima
		for($f = 0; $f < pg_num_fields($res); $f++) {
			$campo = pg_field_name($res, $f);
			$valor = pg_fetch_result($res, $i, $f);
			$$campo = $valor;

			//Este código gera os dados dos clientes em uma matriz de arrays javascript para que as funções
			//retorna_dados_cliente() e retorna_dados_produto() possam buscar e retornar os valores
			if ($f == 0) {
			echo "
			<script language=javascript>
			consumidores[$i] = new Array();
			</script>";
			}
			echo "
			<script language=javascript>
			consumidores[$i]['$campo'] = '" . addslashes($valor) . "';
			</script>";
		}

		switch($tipo) {
			case "O":
				$os = $sua_os;
				$atendimento = $referencia;
				$linkos = "<a href='os_press.php?os=$id' target=_blank><img src=imagens/lupa2.png></a>";

				if ($atendimento) {
					$sql = "
					SELECT
					status,
					categoria

					FROM
					tbl_hd_chamado

					WHERE
					hd_chamado=$atendimento
					";
					$res_hd = pg_query($con, $sql);

					$status = pg_fetch_result($res_hd, 0, status);
					$categoria = pg_fetch_result($res_hd, 0, categoria);
				}
			break;

			case "C":
				$os = $sua_os;
				$atendimento = $id;
				$linkos = "<a href='os_press.php?os=$referencia' target=_blank><img src=imagens/lupa2.png></a>";
			break;

			case "R":
				$os = 0;
				$atendimento = 0;
			break;

			case "A":
				$os = 0;
				$atendimento = 0;
			break;
		}

		if ($atendimento) {
			$atendimento_link = "<a href=\"javascript:opener.document.location = 'callcenter_interativo_new.php?callcenter=$atendimento'; window.close();\" title='Clique neste link para continuar o atendimento $atendimento'> $atendimento</a>";

			if ($status == "Resolvido" || $status == "Cancelado") {
				if ($os) {
					$os_link = "<a href=\"javascript:retorna_dados_cliente($i); preenche_os($i); retorna_dados_produto($i); retorna_dados_posto($i);\"  title='Clique neste link para abrir um novo atendimento relativo à OS $sua_os'> $sua_os</a>";
				}
				else {
					$os_link = "<font class=semos>SEM ORDEM SERVIÇO</font>";
					$linkos = "";
				}
			}
			else {
				if ($os) {
					$os_link = "<a href=\"javascript:opener.document.location = 'callcenter_interativo_new.php?callcenter=$atendimento';\"  title='Clique neste link para continuar o atendimento $atendimento'> $os</a>";
				}
				else {
					$os_link = "<font class=semos>SEM ORDEM SERVIÇO</font>";
					$linkos = "";
				}
			}
		}
		else {
			$atendimento_link = "<font class=sematendimento>SEM ATENDIMENTO</font>";
			$os_link = $os;

			if ($os) {
				$os_link = "<a href=\"javascript:retorna_dados_cliente($i); preenche_os($i); retorna_dados_produto($i); retorna_dados_posto($i);\"  title='Clique neste link para abrir um novo atendimento relativo à OS $sua_os'> $sua_os</a>";
			}
			else {
				$os_link = "<font class=semos>SEM ORDEM SERVIÇO</font>";
				$linkos = "";
			}
		}

		$l = $i % 2;

		echo "
		<tr>
			<td class=buscalinha$l nowrap>
				$atendimento_link
			</td>
			<td class=buscalinha$l nowrap>
				$data_atendimento
			</td>
			<td class=buscalinha$l nowrap>
				<a href='javascript: retorna_dados_cliente($i); window.close();' title='Clique neste link para abrir novo chamado com os dados deste cliente: $nome, $endereco, $numero, $complemento, $bairro, $cep, $cidade, $fone, $cpf_cnpj, $rg, $email, $nome_cidade, $estado'>" . substr($nome, 0, 30) . "</a>
			</td>
			<td class=buscalinha$l nowrap>
				$os_link $linkos
			</td>
			<td class=buscalinha$l nowrap>
				<a href='javascript: retorna_dados_cliente($i); retorna_dados_produto($i); window.close();' title='$produto'>" . substr($produto, 0, 20) . "</label>
			</td>
			<td class=buscalinha$l nowrap>
				$status
			</td>
			<td class=buscalinha$l nowrap>
				$categoria
			</td>
		</tr>";
	}

	echo "
	</table>
</body>
</html>";
}

?>
