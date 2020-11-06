<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';

if($login_fabrica != 7 ){
	header("Location: menu_os.php");
	exit;
}

$msg_erro = "";

$title = "Ordem de Serviço - Agrupamento para Faturamento";
$layout_menu = "callcenter";
include 'cabecalho.php';

?>

<style type="text/css">

.menu_top {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 9px;
	font-weight: bold;
	border: 1px solid;
	color:#596d9b;
	background-color: #d9e2ef
}

.border {
	border: 1px solid #ced7e7;
}

.table_line {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 9px;
	font-weight: normal;
	border: 0px solid;
	background-color: #ffffff
}

.table_line2 {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 9px;
	font-weight: normal;
	border: 0px solid;
	background-color: #ffffff
}

input {
	font-size: 10px;
}

.top_lst {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: bold;
	color:#596d9b;
	background-color: #d9e2ef
}

.line_lst {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	color:#596d9b;
	background-color: #ffffff
}

input {
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 09px;
	font-weight: normal;
	border: 1x solid #a0a0a0;
	background-color: #FFFFFF;
}

TEXTAREA {
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	border: 1x solid #a0a0a0;
	background-color: #FFFFFF;
}

</style>

<script>
function fnc_pesquisa_consumidor (campo, tipo) {
	var url = "";
	if (tipo == "nome") {
		url = "pesquisa_consumidor.php?nome=" + campo.value + "&tipo=nome";
	}
	if (tipo == "cpf") {
		url = "pesquisa_consumidor.php?cpf=" + campo.value + "&tipo=cpf";
	}
	janela = window.open(url,"janela","toolbar=no,location=yes,status=yes,scrollbars=yes,directories=no,width=500,height=400,top=18,left=0");
	janela.nome        = document.frm_os.cliente_nome;
	janela.cpf         = document.frm_os.cliente_cpf;
	janela.cliente     = document.frm_os.cliente;
	janela.rg          = document.frm_os.rg;
	janela.cidade      = document.frm_os.cidade;
	janela.fone        = document.frm_os.fone;
	janela.endereco    = document.frm_os.endereco;
	janela.numero      = document.frm_os.numero;
	janela.complemento = document.frm_os.complemento;
	janela.bairro      = document.frm_os.bairro;
	janela.cep         = document.frm_os.cep;
	janela.estado      = document.frm_os.estado;
	janela.focus();
}

function fnc_pesquisa_revenda (campo, tipo) {
	var url = "";
	if (tipo == "nome") {
		url = "pesquisa_revenda.php?nome=" + campo.value + "&tipo=nome";
	}
	if (tipo == "cnpj") {
		url = "pesquisa_revenda.php?cnpj=" + campo.value + "&tipo=cnpj";
	}
	janela = window.open(url,"janela","toolbar=no,location=yes,status=yes,scrollbars=yes,directories=no,width=500,height=400,top=18,left=0");
	janela.nome			= document.frm_os.revenda_nome;
	janela.cnpj			= document.frm_os.revenda_cnpj;
	janela.fone			= document.frm_os.revenda_fone;
	janela.cidade		= document.frm_os.revenda_cidade;
	janela.estado		= document.frm_os.revenda_estado;
	janela.endereco		= document.frm_os.revenda_endereco;
	janela.numero		= document.frm_os.revenda_numero;
	janela.complemento	= document.frm_os.revenda_complemento;
	janela.bairro		= document.frm_os.revenda_bairro;
	janela.cep			= document.frm_os.revenda_cep;
	janela.email		= document.frm_os.revenda_email;
	janela.focus();
}

</script>

<? if (strlen($msg_erro) > 0){ ?>
<TABLE>
<TR>
	<TD><? echo $msg_erro; ?></TD>
</TR>
</TABLE>
<?}?>

<form name='frm_os' action='os_faturamento_lote_filizola.php' method="POST">

<table class="border" width='700' align='center' border='0' cellpadding="3" cellspacing="3">
	<tr>
		<td colspan=3 class="menu_top">NOVO LOTE DE FATURAMENTO</td>
	</tr>
	<tr>
		<td class="menu_top">&nbsp;</td>
		<td class="menu_top">CNPJ DO CLIENTE</td>
		<td class="menu_top">NOME DO CLIENTE</td>
	</tr>
	<tr>
		<TD class="table_line2" width="30%">FATURADO PARA CLIENTE</TD>
		<TD class="table_line2" width="30%"><center><input type='text' name='cliente_cpf' value='<? //echo $cliente_cpf ?>' size='19'>&nbsp;<IMG src="imagens/btn_lupa.gif" style="cursor:pointer " align='absmiddle' alt="Clique aqui para pesquisas pelo cpf do consumidor." onclick="javascript: fnc_pesquisa_consumidor (document.frm_os.cliente_cpf,'cpf')"></center></TD>
		<TD class="table_line2" width="40%"><center><input type='text' name='cliente_nome' value='<? //echo $cliente_nome ?>' size='35'>&nbsp;<IMG src="imagens/btn_lupa.gif" style="cursor:pointer " align='absmiddle' alt="Clique aqui para pesquisas pelo nome do consumidor." onclick="javascript: fnc_pesquisa_consumidor (document.frm_os.cliente_nome,'nome')"></center></TD>
<INPUT TYPE="hidden" name='cliente' value=''>
<INPUT TYPE="hidden" name='rg' value=''>
<INPUT TYPE="hidden" name='cidade' value=''>
<INPUT TYPE="hidden" name='fone' value=''>
<INPUT TYPE="hidden" name='endereco' value=''>
<INPUT TYPE="hidden" name='numero' value=''>
<INPUT TYPE="hidden" name='complemento' value=''>
<INPUT TYPE="hidden" name='bairro' value=''>
<INPUT TYPE="hidden" name='cep' value=''>
<INPUT TYPE="hidden" name='estado' value=''>
	</tr>

	<tr>
		<td class="menu_top">&nbsp;</td>
		<td class="menu_top">CNPJ DA REVENDA</td>
		<td class="menu_top">NOME DA REVENDA</td>
	</tr>
	<tr>
		<TD class="table_line2" width="30%">FATURADO PARA REVENDA</TD>
		<TD class="table_line2" width="30%"><center><input type='text' name='revenda_cnpj' value='<? //echo $revenda_cpf ?>' size='19'>&nbsp;<IMG src="imagens/btn_lupa.gif" style="cursor:pointer " align='absmiddle' alt="Clique aqui para pesquisas pelo cpf do consumidor." onclick="javascript: fnc_pesquisa_revenda (document.frm_os.revenda_cnpj,'cnpj')"></center></TD>
		<TD class="table_line2" width="40%"><center><input type='text' name='revenda_nome' value='<? //echo $revenda_nome ?>' size='35'>&nbsp;<IMG src="imagens/btn_lupa.gif" style="cursor:pointer " align='absmiddle' alt="Clique aqui para pesquisas pelo nome do consumidor." onclick="javascript: fnc_pesquisa_revenda (document.frm_os.revenda_nome,'nome')"></center></TD>
<INPUT TYPE="hidden" name='revenda' value=''>
<input type="hidden" name="revenda_cidade" value="">
<input type="hidden" name="revenda_estado" value="">
<input type="hidden" name="revenda_endereco" value="">
<input type="hidden" name="revenda_cep" value="">
<input type="hidden" name="revenda_numero" value="">
<input type="hidden" name="revenda_complemento" value="">
<input type="hidden" name="revenda_bairro" value="">
<input type="hidden" name="revenda_fone" value="">
<input type="hidden" name="revenda_email" value="">
	</tr>
	<tr>
		<td align='center' colspan='7'>
			<input type='hidden' name='btn_acao' value=''>
			<img src="imagens/btn_continuar.gif" style="cursor: pointer;" onclick="javascript: if (document.frm_os.btn_acao.value == '' ) { document.frm_os.btn_acao.value='confirmar' ; document.frm_os.submit() }" ALT="Confirmar " border='0'>
		</td>
	</tr>
</table>

</form>

<BR>
<?

$sql  = "SELECT * FROM (
			(
			SELECT  tbl_os_faturamento.os_faturamento                                       ,
					to_char(tbl_os_faturamento.data_abertura, 'DD/MM/YYYY') AS data_abertura,
					tbl_cliente.nome                                        AS nome         ,
					tbl_cliente.cpf                                         AS cnpj_cpf
			FROM    tbl_os_faturamento
			JOIN    tbl_cliente USING (cliente)
			WHERE   tbl_os_faturamento.data_fechamento IS NULL
			AND     tbl_os_faturamento.revenda         IS NULL
			AND     tbl_os_faturamento.cliente         NOTNULL
			) UNION (
			SELECT  tbl_os_faturamento.os_faturamento                                       ,
					to_char(tbl_os_faturamento.data_abertura, 'DD/MM/YYYY') AS data_abertura,
					tbl_revenda.nome                                        AS nome         ,
					tbl_revenda.cnpj                                        AS cnpj_cpf
			FROM    tbl_os_faturamento
			JOIN    tbl_revenda USING (revenda)
			WHERE   tbl_os_faturamento.data_fechamento IS NULL
			AND     tbl_os_faturamento.cliente         IS NULL
			AND     tbl_os_faturamento.revenda         NOTNULL
			)
		) AS x ORDER BY x.data_abertura;";

// tbl_os.fabrica = $login_fabrica
$res = pg_exec($con,$sql);

if (pg_numrows($res) > 0){

?>
<br>

<table class="border" width='700' align='center' border='0' cellpadding="1" cellspacing="3">
	<tr>
		<td colspan='3' class="menu_top">&nbsp;</td>
	</tr>
	<tr>
		<td class="menu_top">CNPJ</td>
		<td class="menu_top">NOME</td>
		<td class="menu_top">DATA ABERTURA</td>
	</tr>
<?
	for ($i=0; $i<pg_numrows($res); $i++) {
		$os_faturamento = trim(pg_result($res,$i,os_faturamento));
		$data_abertura  = trim(pg_result($res,$i,data_abertura));
		$cnpj_cpf       = trim(pg_result($res,$i,cnpj_cpf));
		$nome           = trim(pg_result($res,$i,nome));
		
		echo "<tr>\n";
		echo "	<TD class='table_line2'>$cnpj_cpf</TD>\n";
		echo "	<TD class='table_line2'>$nome</TD>\n";
		echo "	<TD class='table_line'>$data_abertura</TD>\n";
		echo "	<TD><a href='os_faturamento_lote_filizola.php?os_faturamento=$os_faturamento'><img src='imagens/btn_alterarcinza.gif' border='0' width='72'></a></TD>\n";
		echo "</tr>\n";
	}
}
?>

</table>

<br>

<?
include 'rodape.php';
?>