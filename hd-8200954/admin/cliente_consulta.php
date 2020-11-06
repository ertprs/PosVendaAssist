<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";

$admin_privilegios="cadastro,auditoria,call_center ";
include 'autentica_admin.php';

$msg_erro = "";
$msg_debug = "";

if (strlen($_POST['btn_acao']) > 0) {
	$btn_acao = $_POST['btn_acao'];
}

if ($btn_acao=='gravar'){
	$posto              = trim($_POST['posto']);
	$contrato           = trim($_POST['contrato']);
	$codigo             = trim($_POST['codigo']);
	$desconto_peca      = trim($_POST['desconto_peca']);
	$contrato        = trim($_POST['contrato']);
	$grupo_empresa      = trim($_POST['grupo_empresa']);
	$numero_contrato    = trim($_POST['numero_contrato']);
	$contrato_descricao = trim($_POST['contrato_descricao']);
	$nome_grupo         = trim($_POST['nome_grupo']);
	$grupo_descricao    = trim($_POST['grupo_descricao']);

	if (strlen($contrato)==0 OR $contrato=='f'){
		$x_contrato = "'f'";
	}else{
		$x_contrato = "'t'";
	}

	if (strlen($codigo)==0){
		$x_codigo = ' NULL ';
	}else{
		$x_codigo = "'".$codigo."'";
	}

	if (strlen($desconto_peca)==0){
		$x_desconto_peca = ' NULL ';
	}else{
		$x_desconto_peca = $desconto_peca;
	}

	if (strlen($contrato)==0){
		$x_contrato = ' NULL ';
	}else{
		$x_contrato = $contrato;
	}

	if (strlen($grupo_empresa)==0){
		$x_grupo_empresa = ' NULL ';
	}else{
		$x_grupo_empresa = $grupo_empresa;
	}

	if (strlen($posto)>0){
		$sql = "UPDATE tbl_posto_consumidor SET
					contrato      = $x_contrato     ,
					desconto_peca = $x_desconto_peca,
					codigo        = $x_codigo       ,
					grupo_empresa = $x_grupo_empresa
				WHERE posto   = $posto
				AND   fabrica = $login_fabrica";
		$res = @pg_exec($con,$sql);
		$msg_erro .= pg_errormessage($con);
		if (strlen($msg_erro)==0){
			header("Location: $PHP_SELF?cliente=$posto");
			exit;
		}
	}
}

$visual_black = "auditoria-admin";

$title       = "Consulta de Clientes";
$cabecalho   = "Consulta de Clientes";
$layout_menu = "cadastro";
include 'cabecalho.php';

?>

<script language="JavaScript">

function fnc_pesquisa_posto (campo, campo2, tipo) {
	if (tipo == "codigo" ) {
		var xcampo = campo;
	}

	if (tipo == "nome" ) {
		var xcampo = campo2;
	}

	if (xcampo.value != "") {
		var url = "";
		url = "posto_pesquisa.php?forma=reload&campo=" + xcampo.value + "&tipo=" + tipo ;
		janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=500, height=400, top=0, left=0");
		janela.retorno = "<? echo $PHP_SELF ?>";
		janela.posto_codigo	= campo;
		janela.porto_nome	= campo2;
		janela.focus();
	}
}


function fnc_pesquisa_consumidor (campo,campo2, tipo) {

	var url = "";

	if (tipo == "cpf" ) {
		var xcampo = campo;
		url = "pesquisa_consumidor.php?forma=reload&cpf=" + xcampo.value + "&tipo=" + tipo ;
	}

	if (tipo == "nome" ) {
		var xcampo = campo2;
		url = "pesquisa_consumidor.php?forma=reload&nome=" + xcampo.value + "&tipo=" + tipo ;
	}

	if (xcampo != "") {
		if (xcampo.value.length >= 3) {
			janela = window.open(url,"janela","toolbar=no,location=yes,status=yes,scrollbars=yes,directories=no,width=501,height=400,top=18,left=0");
			janela.retorno = "<? echo $PHP_SELF ?>";
			janela.cliente		= campo;
			janela.nome			= campo2;
			janela.focus();
		}else{
			alert("Digite pelo menos 3 caracteres para efetuar a pesquisa");
		}
	}
}


function fnc_pesquisa_contrato (campo,campo2, tipo) {

	var url = "";

	if (tipo == "numero_contrato" ) {
		var xcampo = campo;
		url = "pesquisa_contrato.php?numero_contrato=" + xcampo.value + "&tipo=" + tipo ;
	}

	if (tipo == "contrato_descricao" ) {
		var xcampo = campo2;
		url = "pesquisa_contrato.php?contrato_descricao=" + xcampo.value + "&tipo=" + tipo ;
	}

	if (xcampo != "") {
		if (xcampo.value.length >= 2) {
			janela = window.open(url,"janela","toolbar=no,location=yes,status=yes,scrollbars=yes,directories=no,width=501,height=400,top=18,left=0");
			janela.contrato			= document.frm_cliente.contrato;
			janela.numero_contrato		= campo;
			janela.contrato_descricao	= campo2;
			janela.focus();
		}else{
			alert("Digite pelo menos 2 caracteres para efetuar a pesquisa");
		}
	}
}


function fnc_pesquisa_grupo (campo,campo2, tipo) {

	var url = "";

	if (tipo == "nome_grupo" ) {
		var xcampo = campo;
		url = "pesquisa_grupo.php?nome_grupo=" + xcampo.value + "&tipo=" + tipo ;
	}

	if (tipo == "grupo_descricao" ) {
		var xcampo = campo2;
		url = "pesquisa_grupo.php?grupo_descricao=" + xcampo.value + "&tipo=" + tipo ;
	}

	if (xcampo != "") {
		if (xcampo.value.length >= 2) {
			janela = window.open(url,"janela","toolbar=no,location=yes,status=yes,scrollbars=yes,directories=no,width=501,height=400,top=18,left=0");
			janela.grupo_empresa	= document.frm_cliente.grupo_empresa;
			janela.nome_grupo		= campo;
			janela.grupo_descricao	= campo2;
			janela.focus();
		}else{
			alert("Digite pelo menos 2 caracteres para efetuar a pesquisa");
		}
	}
}

</script>

<style type="text/css">

.menu_top {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
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
	font-size: 10px;
	font-weight: normal;
	border: 0px solid;
	background-color: #ffffff
}

input {
	font-size: 10px;
}

.top_list {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: bold;
	color:#596d9b;
	background-color: #d9e2ef
}

.line_list {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: x-small;
	font-weight: normal;
	color:#596d9b;
	background-color: #ffffff
}

</style>

<?
	if(strlen($msg_erro)>0){
?>
<table width='700px' align='center' border='0' bgcolor='#FFFFFF' cellspacing="1" cellpadding="0">
<tr align='center'>
	<td class='error'>
		<? echo $msg_erro; ?>
	</td>
</tr>
</table>
<?	}
//echo $msg_debug;
?>
<p>

<?
if (strlen($_GET['posto']) > 0)  $posto = trim($_GET['posto']);
if (strlen($_POST['posto']) > 0) $posto = trim($_POST['posto']);

if (strlen($posto)==0){
	$posto = trim($_GET['cliente']);
}

if (strlen ($posto) > 0) {
	$sql = "SELECT	tbl_posto.posto,
					tbl_posto.nome,
					tbl_posto.cnpj,
					tbl_posto_consumidor.codigo
			FROM tbl_posto
			JOIN tbl_posto_consumidor ON tbl_posto_consumidor.posto = tbl_posto.posto AND tbl_posto_consumidor.fabrica = $login_fabrica
			WHERE tbl_posto.posto = $posto";
	$res = pg_exec ($con,$sql);
	$posto_codigo = pg_result ($res,0,codigo);
	$cpf          = pg_result ($res,0,cnpj);
	$posto_nome   = pg_result ($res,0,nome);
}
?>


<table width='600' align='center' border='0' bgcolor='#d9e2ef'>
<tr>
	<td align='center'>
		<font face='arial, verdana' color='#596d9b' size='-1'>
		Digite o CNPJ/CPF ou nome do cliente , ou clique na lupa para pesquisar.
		</font>
	</td>
</tr>
</table>

<form name="frm_pesquisa" method="post" action="<? echo $PHP_SELF ?>">


<table width="600" border="0" cellspacing="5" cellpadding="0" align='center'>
<tr>
	<td nowrap>
		<font size="1" face="Geneva, Arial, Helvetica, san-serif">CNPJ / CPF do Cliente</font>
		<br>
		<input class="frm" type="text" name="cpf" size="20" value="<? echo $cpf ?>">&nbsp;<img src='imagens/btn_buscar5.gif' border='0' align='absmiddle' style="cursor:pointer" onclick="javascript: fnc_pesquisa_consumidor (document.frm_pesquisa.cpf,document.frm_pesquisa.cliente_nome,'cpf')"></A>
	</td>

	<td nowrap>
		<font size="1" face="Geneva, Arial, Helvetica, san-serif">Nome do Cliente</font>
		<br>
		<input class="frm" type="text" name="cliente_nome" size="50" value="<? echo $posto_nome ?>" >&nbsp;<img src='imagens/btn_buscar5.gif' style="cursor:pointer" border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_consumidor (document.frm_pesquisa.cpf,document.frm_pesquisa.cliente_nome,'nome')" style="cursor:pointer;"></A>
	</td>

</tr>
</table>

<br><br>


</form>

<?
#-------------------- Pesquisa Posto -----------------


if (strlen($posto) > 0 and strlen ($msg_erro) == 0 ) {
	$sql = "SELECT  tbl_posto_consumidor.codigo                    ,
					tbl_posto.posto                                ,
					tbl_posto.nome                                 ,
					tbl_posto.cnpj                                 ,
					tbl_posto.ie                                   ,
					tbl_posto.endereco    AS endereco              ,
					tbl_posto.numero      AS numero                ,
					tbl_posto.complemento AS complemento           ,
					tbl_posto.bairro      AS bairro                ,
					tbl_posto.cep         AS cep                   ,
					tbl_posto.cidade      AS cidade                ,
					tbl_posto.estado      AS estado                ,
					tbl_posto.email       AS email                 ,
					tbl_posto.fone                                 ,
					tbl_posto.fax                                  ,
					tbl_posto.contato                              ,
					tbl_posto.capital_interior                     ,
					tbl_posto.fantasia                             ,
					tbl_posto_consumidor.obs                       ,
					tbl_posto_consumidor.contrato                  ,
					tbl_posto_consumidor.desconto_peca             ,
					tbl_posto_consumidor.contrato               ,
					tbl_posto_consumidor.grupo_empresa             ,
					tbl_contrato.numero_contrato                   ,
					tbl_contrato.descricao as contrato_descricao    ,
					tbl_grupo_empresa.nome_grupo                   ,
					tbl_grupo_empresa.descricao as grupo_descricao
			FROM	tbl_posto
			JOIN	tbl_posto_consumidor ON tbl_posto_consumidor.posto = tbl_posto.posto AND tbl_posto_consumidor.fabrica = $login_fabrica
			LEFT JOIN    tbl_contrato         ON tbl_posto_consumidor.contrato = tbl_contrato.contrato
			LEFT JOIN    tbl_grupo_empresa USING (grupo_empresa)
			WHERE   tbl_posto.posto   = $posto ";
	$res = pg_exec ($con,$sql);

	if (pg_numrows ($res) > 0) {
		$posto            = trim(pg_result($res,0,posto));
		$codigo           = trim(pg_result($res,0,codigo));
		$nome             = trim(pg_result($res,0,nome));
		$cnpj             = trim(pg_result($res,0,cnpj));
		$ie               = trim(pg_result($res,0,ie));
		if (strlen($cnpj) == 14) $cnpj = substr($cnpj,0,2) .".". substr($cnpj,2,3) .".". substr($cnpj,5,3) ."/". substr($cnpj,8,4) ."-". substr($cnpj,12,2);
		if (strlen($cnpj) == 11) $cnpj = substr($cnpj,0,3) .".". substr($cnpj,3,3) .".". substr($cnpj,6,3) ."-". substr($cnpj,9,2);
		$endereco         = trim(pg_result($res,0,endereco));
		$endereco         = str_replace("\"","",$endereco);
		$numero           = trim(pg_result($res,0,numero));
		$complemento      = trim(pg_result($res,0,complemento));
		$bairro           = trim(pg_result($res,0,bairro));
		$cep              = trim(pg_result($res,0,cep));
		$cidade           = trim(pg_result($res,0,cidade));
		$estado           = trim(pg_result($res,0,estado));
		$email            = trim(pg_result($res,0,email));
		$fone             = trim(pg_result($res,0,fone));
		$fax              = trim(pg_result($res,0,fax));
		$contato          = trim(pg_result($res,0,contato));
		$obs              = trim(pg_result($res,0,obs));
		$capital_interior = trim(pg_result($res,0,capital_interior));
		$desconto_peca    = trim(pg_result($res,0,desconto_peca));
		$contrato         = trim(pg_result($res,0,contrato));
		$contrato      = trim(pg_result($res,0,contrato));
		$grupo_empresa    = trim(pg_result($res,0,grupo_empresa));
		$numero_contrato    = trim(pg_result($res,0,numero_contrato));
		$contrato_descricao = trim(pg_result($res,0,contrato_descricao));
		$nome_grupo         = trim(pg_result($res,0,nome_grupo));
		$grupo_descricao    = trim(pg_result($res,0,grupo_descricao));
	}
?>

<form name="frm_cliente" method="post" action="<? echo $PHP_SELF ?>">
<input type='hidden' name='posto' value='<?=$posto?>'>
<table class="border" width='650' align='center' border='0' cellpadding="1" cellspacing="3">
	<tr>
		<td colspan="5">
			<img src="imagens/cab_informacoescadastrais.gif">
		</td>
	</tr>
	<tr class="menu_top">
		<td>CNPJ/CPF</td>
		<td>I.E.</td>
		<td>FONE</td>
		<td>FAX</td>
		<td>CONTATO</td>
	</tr>
	<tr class="table_line">
		<td><? echo $cnpj ?>&nbsp;</td>
		<td><? echo $ie ?></td>
		<td><? echo $fone ?></td>
		<td><? echo $fax ?></td>
		<td><? echo $contato ?></td>
	</tr>
	<tr class="menu_top">
		<td colspan="2">CÓDIGO</td>
		<td colspan="5">RAZÃO SOCIAL</td>
	</tr>
	<tr class="table_line">
		<td colspan="2"><input type='text' class='frm' name='codigo' size='15' maxlength='10' value='<? echo $codigo ?>'>&nbsp;</td>
		<td colspan="3"><? echo $nome ?></td>
	</tr>
</table>
<br>
<table class="border" width='650' align='center' border='0' cellpadding="1" cellspacing="3">
	<tr class="menu_top">
		<td colspan="2">ENDEREÇO</td>
		<td>NÚMERO</td>
		<td colspan="2">COMPLEMENTO</td>
	</tr>
	<tr class="table_line">
		<td colspan="2"><? echo $endereco ?>&nbsp;</td>
		<td><? echo $numero ?></td>
		<td colspan="2"><? echo $complemento ?></td>
	</tr>
	<tr class="menu_top">
		<td colspan="2">BAIRRO</td>
		<td>CEP</td>
		<td>CIDADE</td>
		<td>ESTADO</td>
	</tr>
	<tr class="table_line">
		<td colspan="2"><? echo $bairro ?>&nbsp;</td>
		<td><? echo $cep ?></td>
		<td><? echo $cidade ?></td>
		<td><? echo $estado ?></td>
	</tr>
</table>
<br>
<table class="border" width='650' align='center' border='0' cellpadding="1" cellspacing="3">
	<tr class="menu_top">
		<td>E-MAIL</td>
		<td>% DESCONTO EM PEÇA</td>
	</tr>
	<tr class="table_line">
		<td>
			<? echo $email ?>
		</td>
		<td><input type='text' class='frm' name='desconto_peca' size='6' maxlength='8' value='<? echo $desconto_peca ?>'>%</td>
	</tr>
	<tr class="menu_top">
		<td>CONTRATO</td>
		<td>GRUPO EMPRESA</td>
	</tr>
	<tr class="table_line">
		<td nowrap >
			<input type="hidden" name="contrato" value="">
			<input type="text" name="numero_contrato" value="<?echo $numero_contrato;?>" size='10'><a href="javascript: fnc_pesquisa_contrato (document.frm_cliente.numero_contrato,document.frm_cliente.contrato_descricao,'numero_contrato')"><IMG SRC="imagens_admin/btn_buscar5.gif" ></a>&nbsp;
			<input type="text" name="contrato_descricao" value="<?echo $contrato_descricao;?>" size='30'><a href="javascript: fnc_pesquisa_contrato (document.frm_cliente.numero_contrato,document.frm_cliente.contrato_descricao,'contrato_descricao')"><IMG SRC="imagens_admin/btn_buscar5.gif" ></a>
		</td>
		<td nowrap>
			<input type="hidden" name="grupo_empresa" value="">
			<input type="text" name="nome_grupo" value="<?echo $nome_grupo;?>" size='10'><a href="javascript: fnc_pesquisa_grupo (document.frm_cliente.nome_grupo,document.frm_cliente.grupo_descricao,'nome_grupo')"><IMG SRC="imagens_admin/btn_buscar5.gif" ></a>&nbsp;
			<input type="text" name="grupo_descricao" value="<?echo $grupo_descricao;?>" size='30'><a href="javascript: fnc_pesquisa_grupo (document.frm_cliente.nome_grupo,document.frm_cliente.grupo_descricao,'grupo_descricao')"><IMG SRC="imagens_admin/btn_buscar5.gif" ></a>
		</td>
	</tr>
</table>
<br>

<table class="border" width='650' align='center' border='0' cellpadding="1" cellspacing="3">

	<tr class="menu_top">
		<td colspan="100%">Observações</td>
	</tr>
	<tr class="table_line">
		<td colspan="100%">
			<? echo $obs ?>&nbsp;
		</td>
	</tr>
</table>


<br>
<center>

<input type='hidden' name='btn_acao' value=''>
<img src="imagens_admin/btn_gravar.gif" style="cursor: pointer;" onclick="javascript: if (document.frm_cliente.btn_acao.value == '' ) { document.frm_cliente.btn_acao.value='gravar' ; document.frm_cliente.submit() } else { alert ('Aguarde submissão') }" ALT="Gravar formulário" border='0'>
</center>

<? } ?>

</form>

<p>

<? include "rodape.php"; ?>