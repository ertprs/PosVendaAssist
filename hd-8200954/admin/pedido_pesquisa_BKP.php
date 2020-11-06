<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';

include 'funcoes.php';

$btn_acao = trim (strtolower ($_POST['btn_acao']));

$msg_erro = "";

if ($btn_acao == "pesquisar") {
	
	$cnpj               = $_POST['cnpj'];
	$pedido_cliente     = $_POST['pedido_cliente'];
	$referencia         = $_POST['referencia'];
	$data_inicial       = $_POST['data_inicial'];
	$data_final         = $_POST['data_final'];

	if (strlen($cnpj) > 0) {
		$cnpj = str_replace (".","",$cnpj);
		$cnpj = str_replace ("-","",$cnpj);
		$cnpj = str_replace ("/","",$cnpj);
		$cnpj = str_replace (" ","",$cnpj);
	}
	
	if (strlen($referencia) > 0) {
		$referencia = str_replace (".","",$referencia);
		$referencia = str_replace ("-","",$referencia);
		$referencia = str_replace ("/","",$referencia);
	}
	
	if (strlen($data_inicial) > 0) {
		$data_inicial = formata_data ($data_inicial);
	}
	
	if (strlen($data_final) > 0) {
		$data_final = formata_data ($data_final);
	}
	
	header ("Location: pedido_relacao.php?cnpj=$cnpj&pedido_cliente=$pedido_cliente&produto=$referencia&data_inicial=$data_inicial&data_final=$data_final");
	exit;

}


$layout_menu = "callcenter";
$title = "Pesquisa de Pedidos de Peças";
$body_onload = "javascript: document.frm_pedido.condicao.focus()";


include "cabecalho.php";

?>

<script language="JavaScript">
/* ============= Função PESQUISA DE PRODUTOS ====================
Nome da Função : fnc_pesquisa_produto (codigo,descricao)
		Abre janela com resultado da pesquisa de Produtos pela
		referência (código) ou descrição (mesmo parcial).
=================================================================*/

function fnc_pesquisa_produto (campo, campo2, tipo) {
	if (tipo == "referencia" ) {
		var xcampo = campo;
	}

	if (tipo == "descricao" ) {
		var xcampo = campo2;
	}


	if (xcampo.value != "") {
		var url = "";
		url = "produto_pesquisa_2.php?campo=" + xcampo.value + "&tipo=" + tipo ;
		janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=500, height=400, top=18, left=0");
		janela.referencia	= campo;
		janela.descricao	= campo2;
		janela.focus();
	}
}

function fnc_pesquisa_peca (campo, campo2, tipo) {
	if (tipo == "referencia" ) {
		var xcampo = campo;
	}

	if (tipo == "descricao" ) {
		var xcampo = campo2;
	}


	if (xcampo.value != "") {
		var url = "";
		url = "peca_pesquisa_2.php?campo=" + xcampo.value + "&tipo=" + tipo ;
		janela = window.open(url, "janela", "toolbar=no, location=yes, status=yes, scrollbars=yes, directories=no, width=500, height=400, top=18, left=0");
		janela.referencia	= campo;
		janela.descricao	= campo2;
		janela.focus();
	}
}


function fnc_pesquisa_posto (campo, campo2, tipo) {
	if (tipo == "nome" ) {
		var xcampo = campo;
	}

	if (tipo == "cnpj" ) {
		var xcampo = campo2;
	}


	if (xcampo.value != "") {
		var url = "";
		url = "posto_pesquisa.php?campo=" + xcampo.value + "&tipo=" + tipo ;
		janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=600, height=300, top=0, left=0");
		janela.retorno = "<? echo $PHP_SELF ?>";
		janela.nome	= campo;
		janela.cnpj	= campo2;
		janela.focus();
	}
}


</script>

<p>

<style type="text/css">

.menu_top {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: x-small;
	font-weight: bold;
	border: 1px solid;
	color:#160C51;
	background-color: #CED7E7
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

<? 
if (strlen ($msg_erro) > 0) {
	if (strpos ($msg_erro,"Cannot insert a duplicate key into unique index tbl_os_sua_os") > 0) $msg_erro = "Esta ordem de serviço já foi cadastrada";

?>

<table width="600" border="0" cellpadding="0" cellspacing="0" align="center">
<tr class="menu_top">
	<td height="27" valign="middle" align="center">
		<b>
		<? echo $msg_erro ?>
		</b>
	</td>
</tr>
</table>
<? } ?>


<!-- ------------- Formulário ----------------- -->
<form name="frm_pedido" method="post" action="<? echo $PHP_SELF ?>">
<input class="frm" type="hidden" name="pedido" value="<? echo $_GET['pedido'] ?>">

<table width='600' align='center' border='0' cellspacing='3' cellpadding='3'>
<tr class="menu_top">
	<td align='center'>
		<b>
		Código ou CNPJ
		</b>
	</td>
	<td align='center'>
		<b>
		Razão Social
		</b>
	</td>
</tr>

<tr>
	<td align='center'>
		<input type="text" name="cnpj" size="14" maxlength="14" value="<? echo $cnpj ?>" class="textbox" style="width:150px" >&nbsp;<img src='../imagens/btn_buscar5.gif' border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_posto (document.frm_pedido.nome,document.frm_pedido.cnpj,'cnpj')" style="cursor:pointer;">
	</td>
	<td align='center'>
		<input type="text" name="nome" size="50" maxlength="60" value="<? echo $nome ?>" class="textbox" style="width:300px">&nbsp;<img src='../imagens/btn_buscar5.gif' border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_posto (document.frm_pedido.nome,document.frm_pedido.cnpj,'nome')" style="cursor:pointer;">
	</td>
</tr>
</table>

<table width='600' align='center' border='0' cellspacing='3' cellpadding='3'>
<tr class="menu_top">
	<td align='center'>
		<b>
		Pedido Cliente
		</b>
	</td>
	<td align='center'>
		<b>
		Referência do Produto
		</b>
	</td>
 	<td align='center'>
		<b>
		Descrição do Produto
		</b>
	</td>
</tr>

<tr>
	<td align='center'>
		<input type="text" name="pedido_cliente" size="10" maxlength="20" value="<? echo $pedido_cliente ?>" class="textbox">&nbsp;<img src='../imagens/btn_buscar5.gif' border='0' align='absmiddle' onclick="" style="cursor:pointer;">
	</td>
	<td>
		<input type="text" name="referencia" size="10" maxlength="20" value="<? echo $referencia ?>" class="textbox" >&nbsp;<img src='../imagens/btn_buscar5.gif' border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_produto (document.frm_pedido.referencia,document.frm_pedido.descricao,'referencia')"style="cursor:pointer;">
	</td>
 	<td align='center'>
		<input type="text" name="descricao" size="30" maxlength="60" value="<? echo $descricao ?>" class="textbox">&nbsp;<img src='../imagens/btn_buscar5.gif' border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_produto (document.frm_pedido.referencia,document.frm_pedido.descricao,'descricao')" style="cursor:pointer;">
	</td>
</tr>
</table>



<table width='600' align='center' border='0' cellspacing='3' cellpadding='3'>
<tr class="menu_top">
	<td align='center'>
		<b>
		Data Inicial do Pedido
		</b>
	</td>
	<td align='center'>
		<b>
		Data Final do Pedido
		</b>
	</td>
</tr>

<tr>
	<td align='center'>
		<input type="text" name="data_inicial" size="10" maxlength="20" value="<? echo $data_inicial ?>" class="textbox">&nbsp;<img src='../imagens/btn_buscar5.gif' border='0' align='absmiddle' onclick="" style="cursor:pointer;">
	</td>
	<td>
		<input type="text" name="data_final" size="10" maxlength="20" value="<? echo $data_final ?>" class="textbox" >&nbsp;<img src='../imagens/btn_buscar5.gif' border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_produto (document.frm_pedido.referencia,document.frm_pedido.descricao,'referencia')"style="cursor:pointer;">
	</td>
</tr>

</table>


<center>

<input type='submit' name='btn_acao' value='Pesquisar'>

<!-- <tr>
	<td height="27" valign="middle" align="center" colspan="3" bgcolor="#FFFFFF">

		<input type='hidden' name='btn_acao' value=''>

		<a href='#'><img src="imagens_admin/btn_gravar.gif" onclick="javascript: if (document.frm_pedido.btn_acao.value == '' ) { document.frm_pedido.btn_acao.value='gravar' ; document.frm_pedido.submit() } else { alert ('Aguarde submissão') }" ALT="Gravar formulário" border='0'></a>

		<input type='hidden' name='btn_descredenciar' value=''>
		<a href='#'><img src="imagens_admin/btn_apagar.gif" onclick="javascript: if (document.frm_pedido.btn_acao.value == '' ) { document.frm_pedido.btn_acao.value='apagar' ; document.frm_pedido.submit() } else { alert ('Aguarde submissão') }" ALT="Apagar Pedido" border='0'></a>

		<a href='#'><img src="imagens_admin/btn_limpar.gif" onclick="javascript: if (document.frm_pedido.btn_acao.value == '' ) { document.frm_pedido.btn_acao.value='reset' ; window.location='<? echo $PHP_SELF ?>' } else { alert ('Aguarde submissão') }" ALT="Limpar campos" border='0'></a>

	</td>
</tr>
 -->

</form>


</table>

<p>


<? include "rodape.php"; ?>