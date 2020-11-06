<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

if($login_fabrica != 7 ){
	header("Location: menu_os.php");
	exit;
}

$msg_erro = "";

$title = "Ordem de Serviço - Valores";
$layout_menu = "os";
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

</script>

<? if (strlen($msg_erro) > 0){ ?>
<TABLE>
<TR>
	<TD><? echo $msg_erro; ?></TD>
</TR>
</TABLE>
<?}?>

<form name='frm_os' action='<? echo $PHP_SELF; ?>' method="get">

<table class="border" width='650' align='center' border='0' cellpadding="3" cellspacing="3">
	<tr>
		<td class="menu_top">CNPJ DO CLIENTE</td>
		<td class="menu_top">NOME DO CLIENTE</td>
		<td class="menu_top">PRECIFICADO</td>
		<td class="menu_top">FATURADO</td>
	</tr>
	<tr>
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
		<TD class="table_line2" width="15%"><center><INPUT TYPE="checkbox" NAME="precificado" value='t' <? //if ($precificado == 't') echo " checked "; ?>></center></TD>
		<TD class="table_line2" width="15%"><center><INPUT TYPE="checkbox" NAME="faturado" value='t' <? //if ($faturado == 't') echo " checked "; ?>></center></TD>
	</tr>
	<tr>
		<td align='center' colspan='7'>
			<input type='hidden' name='btn_acao' value=''>
			<img src="imagens/btn_continuar.gif" style="cursor: pointer;" onclick="javascript: if (document.frm_os.btn_acao.value == '' ) { document.frm_os.btn_acao.value='confirmar' ; document.frm_os.submit() } else { alert ('Aguarde submissão') }" ALT="Confirmar " border='0'>
		</td>
	</tr>

</table>

</form>

<BR>
<?

if (strlen($_GET['cliente_cpf']) > 0)  $cliente_cpf = $_GET['cliente_cpf'];
if (strlen($_POST['cliente_cpf']) > 0) $cliente_cpf = $_POST['cliente_cpf'];

if (strlen($_GET['cliente_nome']) > 0)  $cliente_nome = $_GET['cliente_nome'];
if (strlen($_POST['cliente_nome']) > 0) $cliente_nome = $_POST['cliente_nome'];

if (strlen($_GET['precificado']) > 0)  $precificado = $_GET['precificado'];
if (strlen($_POST['precificado']) > 0) $precificado = $_POST['precificado'];

if (strlen($_GET['faturado']) > 0)  $faturado = $_GET['faturado'];
if (strlen($_POST['faturado']) > 0) $faturado = $_POST['faturado'];

//if (strlen($btn_acao) > 0 AND (strlen($cliente_cpf) > 0 OR strlen($cliente_nome) > 0)){
if (strlen($btn_acao) > 0){

	$sql  = "SELECT tbl_os.os                                                    ,
					tbl_os.sua_os                                                ,
					to_char(tbl_os.data_abertura, 'DD/MM/YYYY') AS data_abertura ,
					tbl_os.cliente                                               ,
					tbl_cliente.nome AS cliente_nome                             ,
					tbl_cliente.cpf  AS cliente_cpf                              
			FROM    tbl_os
			JOIN    tbl_cliente USING(cliente)
			WHERE   tbl_os.fabrica = $login_fabrica
			AND     tbl_os.posto   = $login_posto ";

/*
	if (strlen($cliente_nome) > 0){
		$sql .= " AND tbl_cliente.nome = '$cliente_nome' ";
	}
*/
	if (strlen($cliente_cpf) > 0){
		$sql .= " AND tbl_cliente.cpf = '$cliente_cpf' ";
	}

/* liberar qdo estiver tudo OK
	if (strlen($precificado) > 0){
		$sql .= " AND tbl_os.posto = '$login_posto' ";
	}

	if (strlen($precificado) > 0){
		$sql .= " AND tbl_os.posto = '$login_posto' ";
	}
*/

	$sql .= " ORDER BY tbl_os.sua_os DESC";

	$res = pg_exec ($con,$sql);

	if (pg_numrows($res) > 0){
?>
<table class="border" width='650' align='center' border='0' cellpadding="1" cellspacing="3">
	<tr>
		<td colspan='5' class="menu_top">Resultado da busca</td>
	</tr>
	<tr>
		<td class="menu_top">Sua OS</td>
		<td class="menu_top">Data Abertura</td>
		<td class="menu_top" colspan='2'>Cliente</td>
		<td class="menu_top" width='72'></td>
	</tr>

<?
		for ($i=0; $i<pg_numrows($res); $i++) {
			$os            = trim(pg_result($res,$i,os));
			$sua_os        = trim(pg_result($res,$i,sua_os));
			$data_abertura = trim(pg_result($res,$i,data_abertura));
			$cliente_nome  = trim(pg_result($res,$i,cliente_nome));
			$cliente_cpf   = trim(pg_result($res,$i,cliente_cpf));

			echo "	<tr>\n";
			echo "		<TD class='table_line'width='90'>$sua_os</TD>\n";
			echo "		<TD class='table_line' width='80'>$data_abertura</TD>\n";
			echo "		<TD class='table_line2' width='90'>$cliente_cpf</TD>\n";
			echo "		<TD class='table_line2'>$cliente_nome</TD>\n";
			echo "		<TD><a href='os_filizola_faturamento.php?os=$os'><img src='imagens/btn_alterarcinza.gif' border='0' width='72'></a></TD>\n";
			echo "	</tr>\n";
		}
	}else{
		echo "<table class='border' width='650' align='center' border='0' cellpadding='1' cellspacing='3'>\n";
		echo "	<tr>\n";
		echo "		<TD class='table_line'><br> Nenhum resultado encontrado!!! <br><br></TD>\n";
		echo "	</tr>\n";
	}
?>

</table>

<BR><br>

<?
} // fim do if
include 'rodape.php';

if (strlen($_GET['print']) > 0 AND strlen($_GET['os']) > 0){
?>
	<script>
		janelaimpressao = window.open('os_filizola_faturamento_print.php?os=<? echo $_GET['os'] ?>','_blank','width=790,height=450,top=0,left=0,scrollbars=yes,resizable=yes')
	</script>
<?
}
?>