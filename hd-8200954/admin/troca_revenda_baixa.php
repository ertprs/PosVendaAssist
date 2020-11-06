<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'includes/funcoes.php';
include "autentica_admin.php";
$admin_privilegios="auditoria";
$layout_menu = 'auditoria';
include "funcoes.php";

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

			if ($busca == "codigo"){
				$sql .= " AND tbl_posto_fabrica.codigo_posto = '$q' ";
			}else{
				$sql .= " AND UPPER(tbl_posto.nome) ilike UPPER('%$q%') ";
			}

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

			if ($busca == "codigo"){
				$sql .= " AND tbl_produto.referencia like '%$q%' ";
			}else{
				$sql .= " AND UPPER(tbl_produto.descricao) ilike UPPER('%$q%') ";
			}

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

if (strlen($_POST["acao"]) > 0) $acao = $_POST["acao"];

if ($_POST["acao"] == 0 && strtoupper($_GET["acao"]) == "PESQUISAR") {
	$acao = $_GET["acao"];
}

$acao = trim(strtoupper($acao));

$produto_referencia = trim($_POST['produto_referencia']);
if (strlen($produto_referencia) == 0) $produto_referencia = trim($_GET["produto_referencia"]);

$produto_descricao = trim($_POST['produto_descricao']);
if (strlen($produto_descricao) == 0) $produto_descricao = trim($_GET["produto_descricao"]);

$produto = trim($_POST['produto']);
if (strlen($produto) == 0) $produto = trim($_GET["produto"]);

$revenda_cnpj = trim($_POST["revenda_cnpj"]);
if (strlen($revenda_cnpj) == 0) $revenda_cnpj = $_GET["revenda_cnpj"];

$quantidade = trim($_POST["quantidade"]);
if (strlen($quantidade) == 0) $quantidade = $_GET["quantidade"];

$motivo = trim($_POST["motivo"]);
if (strlen($motivo) == 0) $motivo = $_GET["motivo"];

$historico = trim($_POST["historico"]);
if (strlen($historico) == 0) $historico = $_GET["historico"];

if ($produto_referencia == "" && $produto != "") {
	$produto = intval($produto);
	$sql = "
	SELECT
	referencia,
	descricao

	FROM
	tbl_produto

	WHERE
	produto=$produto
	";
	$res = pg_query($con, $sql);

	$produto_referencia = pg_result($res, 0, referencia);
	$produto_descricao = pg_result($res, 0, descricao);
}

$title = "Autorizações de Devoluções de Vendas";

include "cabecalho.php";
?>

<style type="text/css">
.Titulo {
	text-align: center;
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 12px;
	font-weight: bold;
	color: #FFFFFF;
	background-color: #596D9B;
}
.Erro {
	text-align: center;
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 12px;
	font-weight: bold;
	color: #FFFFFF;
	background-color: #FF0000;
}
.Mensagem {
	text-align: center;
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 12px;
	font-weight: bold;
	color: #FFFFFF;
	background-color: #007700;
}
.Total {
	text-align: center;
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 12px;
	font-weight: bold;
	color: #FFFFFF;
	background-color: #DDEEEE;
}
.Conteudo {
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
}
</style>
<link rel="stylesheet" type="text/css" href="js/jquery.autocomplete.css" />
<link rel="stylesheet" href="js/blue/style.css" type="text/css" id="" media="print, projection, screen" />

<? include "javascript_pesquisas.php"; ?>
<? include "javascript_calendario.php"; ?>
<script language="javascript" src="js/cal2.js"></script>
<script language="javascript" src="js/cal_conf2.js"></script>
<script type='text/javascript' src='js/jquery.autocomplete.js'></script>
<script type='text/javascript' src='js/jquery.bgiframe.min.js'></script>
<script type='text/javascript' src='js/dimensions.js'></script>
<script type='text/javascript' src='js/bibliotecaAJAX.js'></script>
<script type="text/javascript" src="js/jquery.tablesorter.js"></script>
<script type="text/javascript" src="js/jquery.tablesorter.pager.js"></script>

<script language="JavaScript">
$(function() {
	// add new widget called repeatHeaders
	$.tablesorter.addWidget({
		// give the widget a id
		id: "repeatHeaders",
		// format is called when the on init and when a sorting has finished
		format: function(table) {
			// cache and collect all TH headers
			if(!this.headers) {
				var h = this.headers = [];
				$("thead th",table).each(function() {
					h.push(
						"<th>" + $(this).text() + "</th>"
					);

				});
			}

			// remove appended headers by classname.
			$("tr.repated-header",table).remove();

			// loop all tr elements and insert a copy of the "headers"
			for(var i=0; i < table.tBodies[0].rows.length; i++) {
				// insert a copy of the table head every 10th row
				if((i%20) == 0) {
					if(i!=0){
					$("tbody tr:eq(" + i + ")",table).before(
						$("<tr></tr>").addClass("repated-header").html(this.headers.join(""))

					);
				}}
			}

		}
	});
	$("table").tablesorter({
		widgets: ['zebra','repeatHeaders']
	});

});


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

	/* Busca por Produto */
	$("#produto_descricao_lancamento").autocomplete("<?echo $PHP_SELF.'?tipo_busca=produto&busca=nome'; ?>", {
		minChars: 3,
		delay: 150,
		width: 350,
		matchContains: true,
		formatItem: formatItem,
		formatResult: function(row) {return row[1];}
	});

	$("#produto_descricao_lancamento").result(function(event, data, formatted) {
		$("#produto_referencia_lancamento").val(data[2]) ;
	});

	/* Busca pelo Nome */
	$("#produto_referencia_lancamento").autocomplete("<?echo $PHP_SELF.'?tipo_busca=produto&busca=codigo'; ?>", {
		minChars: 3,
		delay: 150,
		width: 350,
		matchContains: true,
		formatItem: formatItem,
		formatResult: function(row) {return row[2];}
	});

	$("#produto_referencia_lancamento").result(function(event, data, formatted) {
		$("#produto_descricao_lancamento").val(data[1]) ;
		//alert(data[2]);
	});

});

</script>

<?

if(strlen($produto_referencia)) {
	$sql="SELECT produto FROM tbl_produto JOIN tbl_linha using (linha) WHERE referencia = '$produto_referencia' and fabrica=$login_fabrica";
	$res = pg_exec($con,$sql);
	if (pg_num_rows($res) == 0) {
		$msg_erro = "Produto $produto_referencia não encontrado";
	}
	else {
		$produto = pg_result($res,0,produto);
	}
}

if (strlen($revenda_cnpj)) {
	$revenda_cnpj = preg_replace('/[^0-9]+/', '', $revenda_cnpj);
	if(strlen($revenda_cnpj) == 8) {
		$cond_revenda_cnpj = " AND tbl_os.revenda_cnpj LIKE '$revenda_cnpj%'";
	}
	else {
		$msg_erro = "CNPJ da Revenda digitado inválido";
	}
}


if ($acao == "GRAVAR" && strlen($msg_erro) == 0) {
	if ($produto_referencia == "" || $revenda_cnpj == "" || $quantidade == "" || $motivo == "" || $historico == "") {
		$msg_erro = "Todos os campos são obrigatórios para GRAVAR";
	}
	else {
		if (intval($quantidade) != $quantidade) {
			$msg_erro = "Valor da quantidade digitado ($quantidade) é inválido";
		}
		else {
			$quantidade = intval($quantidade);
			if ($quantidade == 0) {
				$msg_erro = "A quantidade deve ser maior que zero";
			}
		}

		if (intval($motivo) != $motivo || (intval($motivo) < 1 || intval($motivo) > 4)) {
			$msg_erro = "Motivo selecionado inválido";
		}
		else {
			$motivo = intval($motivo);
		}
		
		$sql = "
		INSERT INTO
		tbl_troca_revenda_baixa (
			fabrica,
			produto,
			radical_cnpj_revenda,
			quantidade,
			motivo,
			historico,
			admin
		)
		VALUES (
			$login_fabrica,
			$produto,
			'$revenda_cnpj',
			$quantidade,
			'$motivo',
			'$historico',
			$login_admin
		)
		";
		$res = pg_query($con, $sql);
		if (pg_errormessage($con)) {
		}
		else {
			echo "
			<script language=javascript>
			document.location = '" . $PHP_SELF . "?revenda_cnpj=$revenda_cnpj&produto=$produto&acao=pesquisar&msg=Lançamento GRAVADO com sucesso!';
			</script>";
			die;
		}
	}

	$acao = "PESQUISAR";
}

?>

<? if (strlen($msg_erro) > 0) { ?>
	<br>
	<table width="406" border="0" cellspacing="0" cellpadding="2" align="center" style='border-style: solid; border-color: #770000; border-width:1px;
	font-family: Verdana;
	font-size: 10px;'>
		<tr class="Erro">
			<td colspan="4" height='25'><? echo $msg_erro; ?></td>
		</tr>
	</table>
<? } ?>

<? if (strlen($_GET["msg"]) > 0) { ?>
	<br>
	<table width="406" border="0" cellspacing="0" cellpadding="2" align="center" style='border-style: solid; border-color: #770000; border-width:1px;
	font-family: Verdana;
	font-size: 10px;'>
		<tr class="Mensagem">
			<td colspan="4" height='25'><? echo $_GET["msg"]; ?></td>
		</tr>
	</table>
<? } ?>

	<br>

	<form name="frm_busca" method="post" action="<? echo $PHP_SELF ?>">
	<input type="hidden" name="acao">
	<table width="400" border="0" cellspacing="0" cellpadding="2" align="center" style='border-style: solid; border-color: #6699CC; border-width:1px;
	font-family: Verdana;
	font-size: 10px;'>
		<tr class="Titulo">
			<td colspan="4" background='imagens_admin/azul.gif' height='25'>PESQUISA</td>
		</tr>
		<tr class="Conteudo" bgcolor="#D9E2EF">
			<td colspan="4">&nbsp;</td>
		</tr>
		<!-- Busca por data - deixei esta busca que peguei em outro programa, caso precise um dia é só habilitar e jogar na sql
		<tr class="Conteudo" bgcolor="#D9E2EF" align=left>
			<td width="10">&nbsp;</td>
			<td>Data Inicial</td>
			<td>Data Final</td>
			<td width="10">&nbsp;</td>
		</tr>
		<tr class="Conteudo" bgcolor="#D9E2EF" align=left>
			<td width="10">&nbsp;</td>
			<td>
				<input type="text" name="data_inicial" id="data_inicial" size="13" maxlength="10" value="<? if (strlen($data_inicial) > 0) echo substr($data_inicial,0,10); else echo "dd/mm/aaaa"; ?>" onClick="javascript: if (this.value == 'dd/mm/aaaa') this.value='';" class="frm">
			</td>
			<td>
				<input type="text" name="data_final" id="data_final" size="13" maxlength="10" value="<? if (strlen($data_final) > 0) echo substr($data_final,0,10); else echo "dd/mm/aaaa"; ?>" onClick="javascript: if (this.value == 'dd/mm/aaaa') this.value='';" class="frm">
			</td>
			<td width="10">&nbsp;</td>
		</tr>
		-->
		<!-- Busca por posto - deixei esta busca que peguei em outro programa, caso precise um dia é só habilitar e jogar na sql
		<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
			<td width="10">&nbsp;</td>
			<td>Posto</td>
			<td>Nome do Posto</td>
			<td width="10">&nbsp;</td>
		</tr>
		<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
			<td width="10">&nbsp;</td>
			<td>
				<input type="text" name="codigo_posto" id="codigo_posto" size="15"  value="<? echo $codigo_posto ?>" class="frm">
				<img border="0" src="imagens/lupa.png" style="cursor: hand;" align="absmiddle" alt="Clique aqui para pesquisar postos pelo código" onclick="javascript: fnc_pesquisa_posto (document.frm_relatorio.codigo_posto, document.frm_relatorio.posto_nome, 'codigo')">
			</td>
			<td>
				<input type="text" name="posto_nome" id="posto_nome" size="30"  value="<?echo $posto_nome?>" class="frm">
				<img border="0" src="imagens/lupa.png" style="cursor: hand;" align="absmiddle" alt="Clique aqui para pesquisar postos pelo código" onclick="javascript: fnc_pesquisa_posto (document.frm_relatorio.codigo_posto, document.frm_relatorio.posto_nome, 'nome')">
			</td>
			<td width="10">&nbsp;</td>
		</tr>
		-->
		<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
			<td width="10">&nbsp;</td>
			<td>Ref. Produto</td>
			<td>Descrição Produto</td>
			<td width="10">&nbsp;</td>
		</tr>
		<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
			<td width="10">&nbsp;</td>
			<td nowrap>
			<input class="frm" type="text" name="produto_referencia" id="produto_referencia" size="15" maxlength="20" value="<? echo $produto_referencia ?>" >&nbsp;<img src='imagens/lupa.png' border='0' align='absmiddle' style="cursor:pointer" onclick="javascript: fnc_pesquisa_produto (document.frm_relatorio.produto_referencia, document.frm_relatorio.produto_descricao,'referencia')">
			</td>

			<td nowrap>
			<input class="frm" type="text" name="produto_descricao" id="produto_descricao" size="30" value="<? echo $produto_descricao ?>" >&nbsp;<img src='imagens/lupa.png'  style="cursor:pointer" border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_produto (document.frm_relatorio.produto_referencia, document.frm_relatorio.produto_descricao,'descricao')">
			</td>
			<td width="10">&nbsp;</td>
		</tr>

		</tr>
			<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
			<td width="10">&nbsp;</td>
			<td></td>
			<td> CNPJ da Revenda</td>
			<td width="10">&nbsp;</td>
		</tr>
		<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
			<td width="10">&nbsp;</td>
			<td></td>
			<td><input class="frm" type="text" name="revenda_cnpj" size="10" maxlength="10" value="<? echo $revenda_cnpj ?>" > /XXXX-XX
			</td>
			<td width="10">&nbsp;</td>
		</tr>

		<tr class="Conteudo" bgcolor="#D9E2EF">
			<td colspan="4">&nbsp;</td>
		</tr>
		<tr class="Conteudo" bgcolor="#D9E2EF">
			<td colspan="4"><img src="imagens_admin/btn_pesquisar_400.gif" onclick="javascript: document.frm_busca.acao.value='PESQUISAR'; document.frm_busca.submit();" style="cursor:pointer " alt="Clique AQUI para pesquisar"></td>
		</tr>
	</table>
	</form>

	<br>

	<form name="frm_lancamento" method="post" action="<? echo $PHP_SELF ?>">
	<input type="hidden" name="acao">
	<table width="406" border="0" cellspacing="0" cellpadding="2" align="center" style='border-style: solid; border-color: #6699CC; border-width:1px;
	font-family: Verdana;
	font-size: 10px;'>
		<tr class="Titulo">
			<td colspan="4" background='imagens_admin/azul.gif' height='25'>NOVO LANÇAMENTO DE BAIXA</td>
		</tr>
		<tr class="Conteudo" bgcolor="#D9E2EF">
			<td colspan="4">&nbsp;</td>
		</tr>
		<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
			<td width="10">&nbsp;</td>
			<td>Ref. Produto</td>
			<td>Descrição Produto</td>
			<td width="10">&nbsp;</td>
		</tr>
		<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
			<td width="10">&nbsp;</td>
			<td nowrap>
			<input class="frm" type="text" name="produto_referencia" id="produto_referencia_lancamento" size="15" maxlength="20" value="<? echo $produto_referencia ?>" >&nbsp;<img src='imagens/lupa.png' border='0' align='absmiddle' style="cursor:pointer" onclick="javascript: fnc_pesquisa_produto (document.frm_relatorio.produto_referencia_lancamento, document.frm_relatorio.produto_descricao_lancamento,'referencia')">
			</td>

			<td nowrap>
			<input class="frm" type="text" name="produto_descricao" id="produto_descricao_lancamento" size="30" value="<? echo $produto_descricao ?>" >&nbsp;<img src='imagens/lupa.png'  style="cursor:pointer" border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_produto (document.frm_relatorio.produto_referencia_lancamento, document.frm_relatorio.produto_descricao_lancamento,'descricao')">
			</td>
			<td width="10">&nbsp;</td>
		</tr>

		</tr>
			<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
			<td width="10">&nbsp;</td>
			<td></td>
			<td> CNPJ da Revenda</td>
			<td width="10">&nbsp;</td>
		</tr>
		<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
			<td width="10">&nbsp;</td>
			<td></td>
			<td><input class="frm" type="text" name="revenda_cnpj" size="10" maxlength="10" value="<? echo $revenda_cnpj ?>" > /XXXX-XX
			</td>
			<td width="10">&nbsp;</td>
		</tr>
		<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
			<td width="10">&nbsp;</td>
			<td>Quantidade</td>
			<td>Motivo</td>
			<td width="10">&nbsp;</td>
		</tr>
		<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
			<td width="10">&nbsp;</td>
			<td nowrap>
			<input class="frm" type="text" name="quantidade" id="quantidade" size="15" maxlength="20" value="<? echo $quantidade ?>" >
			</td>

			<td nowrap>
			<?
			switch($motivo) {
				case 1:
					$selected1 = "selected";
				break;

				case 2:
					$selected2 = "selected";
				break;

				case 3:
					$selected3 = "selected";
				break;

				case 4:
					$selected4 = "selected";
				break;
			}
			?>
			<select class="frm" type="text" name="motivo" id="motivo">
				<option value="">--- Selecione ---</option>
				<option <?echo $selected1;?> value=1>Trocado por Produto Novo</option>
				<option <?echo $selected2;?> value=2>Ressarcimento</option>
				<option <?echo $selected3;?> value=3>Abatimento em Nota Fiscal</option>
				<option <?echo $selected4;?> value=4>Administrativo</option>
			</select>
			</td>
			<td width="10">&nbsp;</td>
		</tr>

		</tr>
			<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
			<td width="10">&nbsp;</td>
			<td colspan="2">Histórico</td>
			<td width="10">&nbsp;</td>
		</tr>
		<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
			<td width="10">&nbsp;</td>
			<td colspan=2>
			<textarea class="frm" style="width:380px;" id="historico" name="historico"><? echo $historico; ?></textarea>
			</td>
			<td width="10">&nbsp;</td>
		</tr>

		<tr class="Conteudo" bgcolor="#D9E2EF">
			<td colspan="4">&nbsp;</td>
		</tr>
		<tr class="Conteudo" bgcolor="#D9E2EF">
			<td colspan="4"><img src="imagens_admin/btn_gravar.gif" onclick="javascript: document.frm_lancamento.acao.value='GRAVAR'; document.frm_lancamento.submit();" style="cursor:pointer " alt="Clique aqui para GRAVAR o lançamento"></td>
		</tr>
	</table>
	</form>

<?

if ($acao == "PESQUISAR" && strlen($msg_erro) == 0) {
	/* Busca por posto - deixei esta busca que peguei em outro programa, caso precise um dia é só habilitar e jogar na sql
	if(strlen(trim($_POST['codigo_posto'])) > 0) {
		$codigo_posto = trim($_POST['codigo_posto']);
		$sql="SELECT posto FROM tbl_posto_fabrica WHERE tbl_posto_fabrica.codigo_posto='$codigo_posto'";
		$res=pg_exec($con,$sql);
		$posto = pg_result($res,0,posto);
		$cond_posto= " AND tbl_os.posto= $posto";
	}*/
	
	if (strlen($produto)) {
		//Selecionou produto e revenda
		if (strlen($revenda_cnpj)) {
			$sql = "
			SELECT
			TO_CHAR(tbl_troca_revenda_baixa.data,'DD/MM/YYYY') AS data,
			tbl_admin.nome_completo,
			tbl_troca_revenda_baixa.quantidade,
			CASE WHEN tbl_troca_revenda_baixa.motivo = '1' THEN 'Trocado por Produto Novo'
				 WHEN tbl_troca_revenda_baixa.motivo = '2' THEN 'Ressarcimento'
				 WHEN tbl_troca_revenda_baixa.motivo = '3' THEN 'Abatimento em Nota Fiscal'
				 WHEN tbl_troca_revenda_baixa.motivo = '4' THEN 'Administrativo'
			END AS motivo_descricao,
			tbl_troca_revenda_baixa.historico

			FROM
			tbl_troca_revenda_baixa
			JOIN tbl_admin ON tbl_troca_revenda_baixa.admin=tbl_admin.admin

			WHERE
			tbl_troca_revenda_baixa.radical_cnpj_revenda = '$revenda_cnpj'
			AND tbl_troca_revenda_baixa.produto = $produto
			AND tbl_troca_revenda_baixa.fabrica = $login_fabrica

			ORDER BY
			tbl_troca_revenda_baixa.data DESC,
			tbl_admin.nome_completo,
			tbl_troca_revenda_baixa.motivo
			";
			$res = pg_query($con, $sql);
			
				echo "
				<table class=tablesorter>
				<thead>
					<tr>
						<th width=10%>Data</th>
						<th width=10%>Usuário</th>
						<th width=10%>Quantidade</th>
						<th width=20%>Motivo</th>
						<th width=50%>Histórico</th>
					</tr>
				</thead>
				<tbody>";
				
				$total = 0;
				for ($i = 0; $i < pg_num_rows($res); $i++) {
					$data = pg_result($res, $i, data);
					$nome_completo = pg_result($res, $i, nome_completo);
					$quantidade = pg_result($res, $i, quantidade);
					$motivo = pg_result($res, $i, motivo_descricao);
					$historico = pg_result($res, $i, historico);
					$total += intval($quantidade);

					echo  "
					<tr>
						<td>$data</td>
						<td>$nome_completo</td>
						<td>$quantidade</td>
						<td>$motivo</td>
						<td>$historico</td>
					</tr>";
				}

				$sql = "
				SELECT
				COUNT(tbl_os_troca.os) AS total_autorizacoes

				FROM
				tbl_os_troca
				JOIN tbl_os ON tbl_os_troca.os=tbl_os.os

				WHERE
				tbl_os_troca.fabric=$login_fabrica
				AND tbl_os.fabrica=$login_fabrica
				AND tbl_os.consumidor_revenda='R'
				AND tbl_os_troca.troca_revenda
				AND tbl_os.produto=$produto
				AND tbl_os.revenda_cnpj LIKE '$revenda_cnpj%'
				AND tbl_os.excluida IS NOT TRUE
				";
				$res = pg_query($sql);
				$total_autorizacoes = pg_result($res, 0, total_autorizacoes);

				$saldo = intval($total_autorizacoes) - intval($total);

				echo  "
				<tr class=Total>
					<td colspan=2>TOTAL DE BAIXAS</td>
					<td>$total</td>
					<td></td>
					<td></td>
				</tr>
				<tr class=Total>
					<td colspan=2>TOTAL DE AUTORIZAÇÕES</td>
					<td>$total_autorizacoes</td>
					<td></td>
					<td></td>
				</tr>
				<tr class=Total>
					<td colspan=2>SALDO DE AUTORIZAÇÕES</td>
					<td>$saldo</td>
					<td></td>
					<td></td>
				</tr>
				</tbody>
				</table>";
		}
		//Selecionou somente produto
		else {
			$sql = "
			SELECT
			SUBSTR(tbl_os.revenda_cnpj, 0, 9) AS radical_cnpj_revenda,
			COUNT(tbl_os_troca.os) AS total_autorizacoes

			FROM
			tbl_os_troca
			JOIN tbl_os ON tbl_os_troca.os=tbl_os.os

			WHERE
			tbl_os_troca.fabric=$login_fabrica
			AND tbl_os.fabrica=$login_fabrica
			AND tbl_os.consumidor_revenda='R'
			AND tbl_os_troca.troca_revenda
			AND tbl_os.produto=$produto
			AND tbl_os.excluida IS NOT TRUE

			GROUP BY
			SUBSTR(tbl_os.revenda_cnpj, 0, 9)

			ORDER BY
			SUBSTR(tbl_os.revenda_cnpj, 0, 9)
			";
			$res = pg_query($con, $sql);
			
			if (pg_num_rows($res)) {
				echo "
				<table class=tablesorter>
				<thead>
					<tr>
						<th width=25%>CNPJ Revenda</th>
						<th width=25%>Autorizações</th>
						<th width=25%>Baixas</th>
						<th width=25%>Saldo</th>
					</tr>
				</thead>
				<tbody>";

				$total_geral_autorizacoes = 0;
				$total_geral_baixas = 0;
				$total_geral_saldo = 0;
				for ($i = 0; $i < pg_num_rows($res); $i++) {
					$radical_cnpj_revenda = pg_result($res, $i, radical_cnpj_revenda);
					$total_autorizacoes = pg_result($res, $i, total_autorizacoes);
					$revenda_cnpj_imprime = substr($radical_cnpj_revenda, 0, 2) . "." .
											substr($radical_cnpj_revenda, 2, 3) . "." .
											substr($radical_cnpj_revenda, 5, 3) . "/XXXX-XX";

					$sql_baixas = "
					SELECT
					SUM(tbl_troca_revenda_baixa.quantidade) AS total_baixas
					
					FROM
					tbl_troca_revenda_baixa
					
					WHERE
					radical_cnpj_revenda='$radical_cnpj_revenda'
					AND fabrica=$login_fabrica
					AND produto=$produto
					";
					$res_baixas = pg_query($con, $sql_baixas);
					$total_baixas = pg_result($res_baixas, 0, 0);
					if ($total_baixas) {
					}
					else {
						$total_baixas = 0;
					}
					$saldo = intval($total_autorizacoes) - intval($total_baixas);

					$total_geral_autorizacoes += intval($total_autorizacoes);
					$total_geral_baixas += intval($total_baixas);
					$total_geral_saldo += intval($saldo);

					echo  "
					<tr>
						<td><a href='?produto=$produto&revenda_cnpj=$radical_cnpj_revenda&acao=pesquisar'>$revenda_cnpj_imprime</a></td>
						<td>$total_autorizacoes</td>
						<td>$total_baixas</td>
						<td>$saldo</td>
					</tr>";
				}
				echo "
					<tr class=Total>
						<td>TOTAIS</td>
						<td>$total_geral_autorizacoes</td>
						<td>$total_geral_baixas</td>
						<td>$total_geral_saldo</td>
					</tr>
				</tbody>
				</table>";
			}
		}	
	}
	//Selecionou somente revenda
	else if (strlen($revenda_cnpj)) {
		$sql = "
		SELECT
		tbl_produto.produto,
		tbl_produto.referencia || tbl_produto.descricao AS produto_referencia_descricao,
		COUNT(tbl_os_troca.os) AS total_autorizacoes

		FROM
		tbl_os_troca
		JOIN tbl_os ON tbl_os_troca.os=tbl_os.os
		JOIN tbl_produto ON tbl_os.produto=tbl_produto.produto
		JOIN tbl_linha ON tbl_produto.linha=tbl_linha.linha

		WHERE
		tbl_os_troca.fabric=$login_fabrica
		AND tbl_os.fabrica=$login_fabrica
		AND tbl_linha.fabrica=$login_fabrica
		AND tbl_os.consumidor_revenda='R'
		AND tbl_os_troca.troca_revenda
		AND tbl_os.revenda_cnpj LIKE '$revenda_cnpj%'
		AND tbl_os.excluida IS NOT TRUE

		GROUP BY
		tbl_produto.produto,
		tbl_produto.referencia,
		tbl_produto.descricao

		ORDER BY
		tbl_produto.referencia,
		tbl_produto.descricao
		";
		$res = pg_query($con, $sql);
		
		if (pg_num_rows($res)) {
			echo "
			<table class=tablesorter>
			<thead>
				<tr>
					<th width=49%>Produto</th>
					<th width=17%>Autorizações</th>
					<th width=17%>Baixas</th>
					<th width=17%>Saldo</th>
				</tr>
			</thead>
			<tbody>";

			$total_geral_autorizacoes = 0;
			$total_geral_baixas = 0;
			$total_geral_saldo = 0;

			for ($i = 0; $i < pg_num_rows($res); $i++) {
				$produto = pg_result($res, $i, produto);
				$produto_referencia_descricao = pg_result($res, $i, produto_referencia_descricao);
				$total_autorizacoes = pg_result($res, $i, total_autorizacoes);

				$sql_baixas = "
				SELECT
				SUM(tbl_troca_revenda_baixa.quantidade) AS total_baixas
				
				FROM
				tbl_troca_revenda_baixa
				
				WHERE
				radical_cnpj_revenda='$revenda_cnpj'
				AND fabrica=$login_fabrica
				AND produto=$produto
				";
				$res_baixas = pg_query($con, $sql_baixas);
				$total_baixas = pg_result($res_baixas, 0, 0);
				if ($total_baixas) {
				}
				else {
					$total_baixas = 0;
				}
				$saldo = intval($total_autorizacoes) - intval($total_baixas);

				$total_geral_autorizacoes += intval($total_autorizacoes);
				$total_geral_baixas += intval($total_baixas);
				$total_geral_saldo += intval($saldo);

				echo  "
				<tr>
					<td><a href='?produto=$produto&revenda_cnpj=$revenda_cnpj&acao=pesquisar'>$produto_referencia_descricao</a></td>
					<td>$total_autorizacoes</td>
					<td>$total_baixas</td>
					<td>$saldo</td>
				</tr>";
			}
			echo "
				<tr class=Total>
					<td>TOTAIS</td>
					<td>$total_geral_autorizacoes</td>
					<td>$total_geral_baixas</td>
					<td>$total_geral_saldo</td>
				</tr>
			</tbody>
			</table>";
		}
	}
	//Não selecionou nada
	else {
	}

	$res = pg_exec($con,$sql);
	$numero_registros = pg_numrows($res);

	if (pg_numrows($res) > 0) {
		
	}
	else {
		echo "<br><FONT size='2' COLOR=\"#FF3333\"><B>Não encontrado!</B></FONT><br><br>";
	}
}
echo "<br>";


include "rodape.php";
?>
