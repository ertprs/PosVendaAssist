<?

include "dbconfig.php";
include "includes/dbconnect-inc.php";
include 'autentica_admin.php';

###############################################

$msg_erro = "";

$btn_acao = strtolower ($_POST['btn_acao']);

if ($btn_acao == "gravar") {

	/* #################### ####################*/
	$cnpj			= $_POST['cnpj'];
	$xcnpj			= str_replace ("-","",$cnpj);
	$xcnpj			= str_replace (".","",$xcnpj);
	$xcnpj			= str_replace ("/","",$xcnpj);
	$xcnpj			= substr ($xcnpj,0,14);

	$fornecedor		= $_POST['fornecedor'];
	echo $fornecedor."<br>";
	$nome			= $_POST['nome'];
	$endereco		= $_POST['endereco'];
	$numero			= $_POST['numero'];
	$bairro			= $_POST['bairro'];
	$complemento	= $_POST['complemento'];
	$cidade			= $_POST['cidade'];
	$estado			= $_POST['estado'];
	$fone1			= $_POST['fone1'];
	$fone2			= $_POST['fone2'];
	$ie				= $_POST['ie'];
	$fax			= $_POST['fax'];
	$email			= $_POST['email'];
	$site			= $_POST['site'];
	/* #################### ####################*/

	if (strlen ($fornecedor) == 0) {

		// para cadastrar novo fornecedor, primeiramente verifica CNPJ
		$sql = "SELECT 	tbl_fornecedor.*,
						tbl_cidade.*
				FROM	tbl_fornecedor
				LEFT OUTER JOIN	tbl_cidade 
				ON 		tbl_cidade.cidade = tbl_fornecedor.cidade
				WHERE	cnpj = '$xcnpj'";
		$res = pg_exec ($con,$sql);

		if (pg_numrows ($res) > 0) {

			$msg_erro = "Fornecedor já está cadastrado.";

			// mostra dados já cadastrados
			$fornecedor		= pg_result ($res,0,fornecedor);
			$nome			= pg_result ($res,0,nome);
			$endereco		= pg_result ($res,0,endereco);
			$numero			= pg_result ($res,0,numero);
			$bairro			= pg_result ($res,0,bairro);
			$complemento	= pg_result ($res,0,complemento);
			$cidade			= pg_result ($res,0,cidade);
			$estado			= pg_result ($res,0,estado);
			$fone1			= pg_result ($res,0,fone1);
			$fone2			= pg_result ($res,0,fone2);
			$cnpj			= str_replace ("-","",pg_result ($res,0,cnpj));
			$cnpj			= str_replace (".","",$cnpj);
			$cnpj			= str_replace ("/","",$cnpj);
			$cnpj			= substr ($cnpj,0,14);
			$ie				= pg_result ($res,0,ie);
			$fax			= pg_result ($res,0,fax);
			$email			= pg_result ($res,0,email);
			$site			= pg_result ($res,0,site);

		}else{

			/*================ INSERE NOVO FORNECEDOR ================*/

			if (strlen($_POST["cidade"]) == 0) {
				$msg_erro = "Favor informar a cidade.";
			} else {
				$sql = "SELECT cidade FROM tbl_cidade WHERE UPPER(fn_retira_especiais(nome)) = UPPER(fn_retira_especiais('{$cidade}')) AND UPPER(estado) = UPPER('{$estado}')";
				$res = pg_query($con, $sql);

				if (pg_num_rows($res) > 0) {
					$cod_cidade = pg_fetch_result($res, 0, "cidade");
				} else {
					$sql = "SELECT cidade, estado FROM tbl_ibge WHERE UPPER(fn_retira_especiais(cidade)) = UPPER(fn_retira_especiais('{$cidade}')) AND UPPER(estado) = UPPER('{$estado}')";
					$res = pg_query($con, $sql);

					if (pg_num_rows($res) > 0) {
						$cidade_ibge        = pg_fetch_result($res, 0, "cidade");
						$cidade_estado_ibge = pg_fetch_result($res, 0, "estado");

						$sql = "INSERT INTO tbl_cidade (
									nome, estado
								) VALUES (
									'{$cidade_ibge}', '{$cidade_estado_ibge}'
								) RETURNING cidade";
						$res = pg_query($con, $sql);

						$cod_cidade = pg_fetch_result($res, 0, "cidade");
					} else {
						$msg_erro .= "Cidade não encontrada";
					}
				}
			}

			if (strlen($msg_erro) == 0) {

				// insere dados no fornecedor
				$sql = "INSERT INTO tbl_fornecedor (
							nome       ,
							endereco   ,
							numero     ,
							bairro     ,
							complemento,
							cidade     ,
							fone1      ,
							fone2      ,
							cnpj       ,
							ie         ,
							fax        ,
							email      ,
							site
						) VALUES (
							'$nome'       ,
							'$endereco'   ,
							'$numero'     ,
							'$bairro'     ,
							'$complemento',
							'$cod_cidade'  ,
							'$fone1'      ,
							'$fone2'      ,
							'$xcnpj'       ,
							'$ie'         ,
							'$fax'        ,
							'$email'      ,
							'$site'
						)";
echo $sql."<br><br>";
				$res = pg_exec ($con,$sql);

				$res		= pg_exec ($con,"SELECT CURRVAL ('seq_fornecedor')");
				$fornecedor	= pg_result ($res,0,0);

				// insere dados no fornecedor fabrica
				$sql = "INSERT INTO tbl_fornecedor_fabrica (
							fornecedor,
							fabrica   ,
							contato   ,
							fone      ,
							email     
						) VALUES (
							'$fornecedor'   ,
							'$login_fabrica',
							'$contato1'     ,
							'$fone1'        ,
							'$email'        
						)";
echo $sql."<br><br>";

				$res = pg_exec ($con,$sql);
			}

		}

	}else{

		/*================ ALTERA =========================*/
		$sql = "UPDATE tbl_fornecedor SET
					nome        = trim ('$nome')       ,
					endereco    = trim ('$endereco')   ,
					numero      = trim ('$numero')     ,
					bairro      = trim ('$bairro')     ,
					complemento = trim ('$complemento'),
					cidade      = '$cod_cidade'        ,
					fone1       = trim ('$fone1')      ,
					fone2       = trim ('$fone2')      ,
					cnpj        = '$xcnpj'             ,
					ie          = trim ('$ie')         ,
					fax         = trim ('$fax')        ,
					email       = trim ('$email')      ,
					site        = trim ('$site')
				WHERE fornecedor = $fornecedor";
		$res = pg_exec ($con,$sql);
echo $sql;
	}

}

###############################################


$visual_black = "manutencao-admin";

$title = "Cadastro de Fornecedores";
$layout_menu = "cadastro";
include 'cabecalho.php';

?>

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

.table_line {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	border: 0px solid;
	background-color: #ffffff
}
</style>

<script>
function fnc_pesquisa_fornecedor(campo, tipo) {
	var xcampo = campo;

	if (xcampo.value != "") {
		var url = "";
		url = "fornecedor_pesquisa.php?campo=" + xcampo.value + "&tipo=" + tipo ;
		janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=503, height=400, top=18, left=0");
		janela.nome			= document.frm_fornecedor.nome;
		janela.endereco		= document.frm_fornecedor.endereco;
		janela.numero		= document.frm_fornecedor.numero;
		janela.bairro		= document.frm_fornecedor.bairro;
		janela.complemento	= document.frm_fornecedor.complemento;
		janela.cidade		= document.frm_fornecedor.cidade;
		janela.fone1		= document.frm_fornecedor.fone1;
		janela.fone2		= document.frm_fornecedor.fone2;
		janela.cnpj			= document.frm_fornecedor.cnpj;
		janela.ie			= document.frm_fornecedor.ie;
		janela.fax			= document.frm_fornecedor.fax;
		janela.email		= document.frm_fornecedor.email;
		janela.site			= document.frm_fornecedor.site;
		janela.focus();
	}
}

/* ============= Função FORMATA CNPJ =============================
Nome da Função : formata_cnpj (cnpj, form)
		Formata o Campo de CNPJ a medida que ocorre a digitação
		Parâm.: cnpj (numero), form (nome do form)
=================================================================*/
function formata_cnpj(cnpj, form){
	var mycnpj = '';
		mycnpj = mycnpj + cnpj;
		myrecord = "revenda_cnpj";
		myform = form;
		
		if (mycnpj.length == 2){
			mycnpj = mycnpj + '.';
			window.document.forms["" + myform + ""].elements[myrecord].value = mycnpj;
		}
		if (mycnpj.length == 6){
			mycnpj = mycnpj + '.';
			window.document.forms["" + myform + ""].elements[myrecord].value = mycnpj;
		}
		if (mycnpj.length == 10){
			mycnpj = mycnpj + '/';
			window.document.forms["" + myform + ""].elements[myrecord].value = mycnpj;
		}
		if (mycnpj.length == 15){
			mycnpj = mycnpj + '-';
			window.document.forms["" + myform + ""].elements[myrecord].value = mycnpj;
		}
}


</script>

<p>
<table width='700px' align='center' border='0' bgcolor='#FFFFFF' cellspacing="1" cellpadding="0">
<tr align='center'>
	<td>
		<font face='arial, verdana' color='#160C51' size='-2'>
		<b>Para incluir um novo fornecedor, preencha somente seu CNPJ e clique em gravar.
		<br>
		Faremos uma pesquisa para verificar se o fornecedor já está cadastrado em nosso banco de dados.</b>
		</font>
	</td>
</tr>
</table>

<? 
	if($msg_erro){
?>
<table width='700px' align='center' border='0' bgcolor='#FFFFFF' cellspacing="1" cellpadding="0">
<tr align='center'>
	<td>
		<font face='arial, verdana' color='#cc3333' size='2'>
		<b><? echo $msg_erro; ?></b>
		</font>
	</td>
</tr>
</table>
<?	} ?>

<form name="frm_fornecedor" method="post" action="<? echo $PHP_SELF; ?>">
<input type="hidden" name="fornecedor" value="<? echo $fornecedor; ?>">

<table width='700px' align='center' border='0' bgcolor='#FFFFFF'>
<tr class="menu_top">
	<td colspan="2">
		CNPJ
	</td>
	<td colspan="2">
		Inscrição Estadual
	</td>
	<td>
		Razão Social
	</td>
</tr>
<tr class="table_line">
	<td>
		<input type="text" name="revenda_cnpj" size="20" maxlength="14" value="<? echo $cnpj ?>" class="frm">
	</td>
	<td>
		<img src='imagens_admin/btn_buscar5.gif' style="cursor: pointer" border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_fornecedor (document.frm_fornecedor.revenda_cnpj,'cnpj')">
	</td>
	<td>
		<input type="text" name="ie" size="21" maxlength="20" value="<? echo $ie ?>" class="frm">
	</td>
	<td>
		<img src='imagens_admin/btn_buscar5.gif' style="cursor: pointer" border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_fornecedor (document.frm_fornecedor.ie,'ie')">
	</td>
	<td>
		<input type="text" name="nome" size="50" maxlength="50" value="<? echo $nome ?>" class="frm">&nbsp;<img src='imagens_admin/btn_buscar5.gif' style="cursor: pointer" border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_fornecedor (document.frm_fornecedor.nome,'nome')">
	</td>
</tr>
</table>
<p>

<!-- ======= ENDEREÇO, NUMERO E COMPLEMENTO ========== -->

<table width='700px' align='center' border='0' bgcolor='#FFFFFF'>
<tr class="menu_top">
	<td>
		Endereço
	</td>
	<td>
		Número
	</td>
	<td>
		Complemento
	</td>
</tr>
<tr class="table_line">
	<td>
		<input type="text" name="endereco" size="58" maxlength="50" value="<? echo $endereco ?>" class="frm" >
	</td>
	<td>
		<input type="text" name="numero" size="10" maxlength="10" value="<? echo $numero ?>" class="frm" style="width:60px" >
	</td>
	<td>
		<input type="text" name="complemento" size="40" maxlength="20" value="<? echo $complemento ?>" class="frm">
	</td>
</tr>
</table>

<!-- ======= BAIRRO - CEP - CIDADE ESTADO ========== -->

<table width='700px' align='center' border='0' bgcolor='#FFFFFF'>
<tr class="menu_top">
	<td>
		Bairro
	</td>
	<td>
		CEP
	</td>
	<td>
		Cidade
	</td>
	<td>
		Estado
	</td>
</tr>
<tr class="table_line">
	<td>
		<input type="text" name="bairro" size="58" maxlength="30" value="<? echo $bairro ?>" class="frm" >
	</td>
	<td>
		<input type="text" name="cep" size="10" maxlength="15" value="<? echo $cep ?>" class="frm" style="width:60px" >
	</td>
	<td>
		<input type="text" name="cidade" size="31" maxlength="20" value="<? echo $cidade ?>" class="frm">
	</td>
	<td>
		<input type="text" name="estado" size="5" maxlength="2" value="<? echo $estado ?>" class="frm">
	</td>
</tr>
</table>
<p>

<!-- ======= FONES E CONTATOS ========== -->

<table width='700px' align='center' border='0' bgcolor='#FFFFFF'>
<tr class="menu_top">
	<td>
		Telefone
	</td>
	<td>
		Contato
	</td>
	<td>
		Telefone Alternativo
	</td>
	<td>
		Contato
	</td>
</tr>
<tr class="table_line">
	<td>
		<input type="text" name="fone1" size="20" maxlength="20" value="<? echo $fone1 ?>" class="frm">
	</td>
	<td>
		<input type="text" name="contato1" size="30" maxlength="30" value="<? echo $contato1 ?>" class="frm">
	</td>
	<td>
		<input type="text" name="fone2" size="21" maxlength="20" value="<? echo $fone2 ?>" class="frm">
	</td>
	<td>
		<input type="text" name="contato2" size="30" maxlength="30" value="<? echo $contato2 ?>" class="frm">
	</td>
</tr>
</table>
<p>
<!-- ======= FAX - E-MAIL - SITE ========== -->

<table width='700px' align='center' border='0' bgcolor='#FFFFFF'>
<tr class="menu_top">
	<td>
		Fax
	</td>
	<td>
		e-Mail
	</td>
	<td>
		WebSite
	</td>
</tr>
<tr class="table_line">
	<td>
		<input type="text" name="fax" size="20" maxlength="20" value="<? echo $fax ?>" class="frm">
	</td>
	<td>
		<input type="text" name="email" size="31" maxlength="50" value="<? echo $email ?>" class="frm">
	</td>
	<td>
		<input type="text" name="site" size="54" maxlength="50" value="<? echo $site ?>" class="frm">
	</td>
</tr>
</table>

<br>

<center>

<input type='hidden' name='btn_acao' value=''>

<a href='#'><img src="imagens_admin/btn_gravar.gif" onclick="javascript: if (document.frm_fornecedor.btn_acao.value == '' ) { document.frm_fornecedor.btn_acao.value='gravar' ; document.frm_fornecedor.submit() } else { alert ('Aguarde submissão') }" ALT="Gravar formulário" border='0'></a>
<a href='#'><img src="imagens_admin/btn_apagar.gif" onclick="javascript: if (document.frm_fornecedor.btn_descredenciar.value == '' ) { document.frm_fornecedor.btn_descredenciar.value='descredenciar' ; document.frm_fornecedor.submit() } else { alert ('Aguarde submissão') }" ALT="Descredenciar" border='0'></a>
<a href='#'><img src="imagens_admin/btn_limpar.gif" onclick="javascript: if (document.frm_fornecedor.btn_acao.value == '' ) { document.frm_fornecedor.btn_acao.value='reset' ; window.location='<? echo $PHP_SELF ?>' } else { alert ('Aguarde submissão') }" ALT="Limpar campos" border='0'></a>

</center>

<!-- ============================ Botoes de Acao ========================= -->

</form>

<?
include("rodape.php");
?>
