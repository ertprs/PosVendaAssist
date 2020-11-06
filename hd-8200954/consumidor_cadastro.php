<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';
include 'funcoes.php';

$btn_acao = strtolower ($_POST['btn_acao']);

if (strlen($_POST['cliente']) > 0) $cliente = trim($_POST['cliente']);
if (strlen($_GET['cliente']) > 0)  $cliente = trim($_GET['cliente']);

$msg_erro = "";

if ($btn_acao == "gravar") {

	$nome    = trim($_POST['nome']);

	$cpf = str_replace ("-","",trim($_POST['cpf']));
	$cpf = str_replace (".","",$cpf);
	$cpf = str_replace ("/","",$cpf);
	$cpf = str_replace (" ","",$cpf);

	$endereco    = trim($_POST['endereco']);
	$numero      = trim($_POST['numero']);
	$complemento = trim($_POST['complemento']);

	$bairro = trim($_POST['bairro']);
	$cep    = trim($_POST['cep']);
	$cidade = trim($_POST['cidade']);
	$estado = trim($_POST['estado']);
	$fone   = trim($_POST['fone']);

	$consumidor_final = trim($_POST['consumidor_final']);

	$cep = str_replace("-","",$cep);


	if (strlen($estado) == 0)
		$msg_erro = "Selecione o estado do Consumidor.";
	else
		$xestado = "'".$estado."'";
	

	if (strlen($cidade) == 0)
		$msg_erro = "Digite a cidade do Consumidor.";
	else
		$xcidade = "'".$cidade."'";
	
	if (strlen($nome) == 0)
		$msg_erro = "Digite o nome do Consumidor.";
	else
		$xnome = "'".$nome."'";

	if (strlen($cpf) == 0)
		//$msg_erro = "Digite o CPF/CNPJ do Consumidor.";
		$xcpf = "null";
	else
		$xcpf = "'".$cpf."'";

	if (strlen($contrato) == 0)
		$xcontrato = 'f';
	else
		$xcontrato = "'".$contrato."'";

	if (strlen($rg) == 0)
		$xrg = 'null';
	else
		$xrg = "'".$rg."'";

	if (strlen($consumidor_final) == 0)
		$msg_erro = "Selecione se consumidor é final ou não.";
	else
		$xconsumidor_final = "'".$consumidor_final."'";

	if (strlen ($cidade) > 0 AND strlen ($estado) > 0) {
		$sql = "SELECT cidade FROM tbl_cidade WHERE UPPER(fn_retira_especiais(nome)) = UPPER(fn_retira_especiais('{$cidade}')) AND UPPER(estado) = UPPER('{$estado}')";
		$res = pg_query($con, $sql);

		if (pg_num_rows($res) > 0) {
			$xcidade = pg_fetch_result($res, 0, "cidade");
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

				$xcidade = pg_fetch_result($res, 0, "cidade");
			} else {
				$msg_erro .= "Cidade não encontrada";
			}
		}
	}else{
		$xcidade = 'null';
	}

	if (strlen($msg_erro) == 0) {
		$res = pg_exec($con,"BEGIN TRANSACTION");

		if (strlen ($cliente) == 0) {
			/*================ INSERE NOVO CLIENTE =========================*/
			$sql = "INSERT INTO tbl_cliente (
						nome            ,
						endereco        ,
						numero          ,
						complemento     ,
						bairro          ,
						cep             ,
						cidade          ,
						fone            ,
						cpf             ,
						contrato        ,
						rg              ,
						consumidor_final
					) VALUES (
						$xnome            ,
						'$endereco'       ,
						'$numero'         ,
						'$complemento'    ,
						'$bairro'         ,
						'$cep'            ,
						$xcidade          ,
						'$fone'           ,
						$xcpf             ,
						'$xcontrato'      ,
						$xrg              ,
						$xconsumidor_final
					)";

		}else{

			/*================ ALTERA OS =========================*/
			$sql = "UPDATE tbl_cliente SET
						nome             = $xnome            ,
						endereco         = '$endereco'       ,
						numero           = '$numero'         ,
						complemento      = '$complemento'    ,
						bairro           = '$bairro'         ,
						cep              = '$cep'            ,
						cidade           = $xcidade          ,
						fone             = '$fone'           ,
						cpf              = $xcpf             ,
						contrato         = '$xcontrato'      ,
						rg               = $xrg              ,
						consumidor_final = $xconsumidor_final
					WHERE cliente   = '$cliente'";
		}
		$res = @pg_exec ($con,$sql);
//echo $sql;

		$msg_erro = pg_errormessage($con);
		$msg_erro = substr($msg_erro,6);

	}
	
	if (strpos ($msg_erro,"duplicate key violates unique constraint \"tbl_cliente_cpf\"") > 0)
		$msg_erro = "Este CPF/CNPJ já esta cadastrado.";

	if (strlen ($msg_erro) == 0) {
		$res = pg_exec ($con,"COMMIT TRANSACTION");
		header ("Location: menu_cadastro.php");
		exit;
	}else{
		$res = pg_exec ($con,"ROLLBACK TRANSACTION");
	}
}

/*================ LE OS DA BASE DE DADOS =========================*/
$cliente = $HTTP_GET_VARS['cliente'];
if (strlen ($cliente) > 0) {
	$sql = "SELECT tbl_cliente.*                    ,
					tbl_cidade.nome   AS cidade_nome,
					tbl_cidade.estado AS estado     
			FROM tbl_cliente 
			JOIN tbl_cidade ON tbl_cidade.cidade = tbl_cliente.cidade
			WHERE cliente = $cliente";
	$res = pg_exec ($con,$sql);

	if (pg_numrows ($res) == 1) {
		$cliente          = pg_result ($res,0,cliente);
		$nome             = pg_result ($res,0,nome);
		$endereco         = pg_result ($res,0,endereco);
		$numero           = pg_result ($res,0,numero);
		$complemento      = pg_result ($res,0,complemento);
		$bairro           = pg_result ($res,0,bairro);
		$cep              = pg_result ($res,0,cep);
		$cidade           = pg_result ($res,0,cidade_nome);
		$estado           = pg_result ($res,0,estado);
		$fone             = pg_result ($res,0,fone);
		$cpf              = pg_result ($res,0,cpf);
		$contrato         = pg_result ($res,0,contrato);
		$rg               = pg_result ($res,0,rg);
		$consumidor_final = pg_result ($res,0,consumidor_final);
	}

}

/*============= RECARREGA FORM EM CASO DE ERRO ==================*/
if (strlen ($msg_erro) > 0) {
	$cliente			= $_POST['cliente'];
	$nome				= $_POST['nome'];
	$endereco			= $_POST['endereco'];
	$numero				= $_POST['numero'];
	$complemento		= $_POST['complemento'];
	$bairro				= $_POST['bairro'];
	$cep				= $_POST['cep'];
	$cidade				= $_POST['cidade'];
	$estado				= $_POST['estado'];
	$fone				= $_POST['fone'];
	$cpf				= $_POST['cpf'];
	$contrato			= $_POST['contrato'];
	$rg					= $_POST['rg'];
	$consumidor_final	= $_POST['consumidor_final'];
}

$title       = "Cadastro de CONSUMIDORES"; 
$layout_menu = 'cadastro';

include "cabecalho.php";

?>

<!--=============== <FUNÇÕES> ================================!-->
<?php
	include 'js/js_css.php'; /* Todas libs js, jquery e css usadas no Assist - HD 969678 */
?>

<script type='text/javascript'>
	$(document).ready(function()
	{
		$("#cep").mask("99.999-999");
	});
</script>

<script language="JavaScript">
/* ============= Função PESQUISA DE CONSUMIDOR POR NOME ====================
Nome da Função : fnc_pesquisa_consumidor_nome (nome, cpf)
=================================================================*/
function fnc_pesquisa_consumidor (campo, tipo) {
	var url = "";
	if (tipo == "nome") {
		url = "pesquisa_consumidor.php?forma=reload&nome=" + campo.value + "&tipo=nome&retorno=<? echo $PHP_SELF?>";
	}
	if (tipo == "cpf") {
		url = "pesquisa_consumidor.php?forma=reload&cpf=" + campo.value + "&tipo=cpf&retorno=<? echo $PHP_SELF?>";
	}
	janela = window.open(url,"janela","toolbar=no,location=yes,status=yes,scrollbars=yes,directories=no,width=500,height=400,top=18,left=0");
	janela.cliente		= document.frm_os.cliente;
	janela.nome			= document.frm_os.nome;
	janela.cpf			= document.frm_os.cpf;
	janela.rg			= document.frm_os.rg;
	janela.cidade		= document.frm_os.cidade;
	janela.estado		= document.frm_os.estado;
	janela.fone			= document.frm_os.fone;
	janela.endereco		= document.frm_os.endereco;
	janela.numero		= document.frm_os.numero;
	janela.complemento	= document.frm_os.complemento;
	janela.bairro		= document.frm_os.bairro;
	janela.cep			= document.frm_os.cep;
	janela.focus();
}

</script>

<? 
if (strlen ($msg_erro) > 0) {
?>
<table border="0" cellpadding="0" cellspacing="0" align="center" bgcolor="#ffffff" width = '730'>
<tr>
	<td valign="middle" align="center" class='error'>
		<? echo $msg_erro ?>
	</td>
</tr>
</table>
<? } ?>

<table border="0" cellpadding="0" cellspacing="0" align="center" bgcolor="#ffffff">
	<td><img height="1" width="20" src="imagens/spacer.gif"></td>
	<td valign="top" align="left">
		<!-- ------------- Formulário ----------------- -->
		<form style="MARGIN: 0px; WORD-SPACING: 0px" name="frm_os" method="post" action="<? echo $PHP_SELF ?>">
		<input class="frm" type="hidden" name="cliente" value="<? echo $cliente; ?>">
		
		<p>
		
		<table width="100%" border="0" cellspacing="1" cellpadding="2">
		<tr>
			<td bgcolor='#d9e2ef'>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">Nome Consumidor</font>
			</td>
			<td bgcolor='#d9e2ef'>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">CPF/CNPJ Consumidor</font>

			</td>
		</tr>
		<tr>
		<td><input class="frm" type="text" name="nome" size="50" maxlength="50" value="<? echo $nome ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Insira aqui o nome do Cliente.');nextfield ='cpf';">&nbsp;<img src='imagens/btn_buscar5.gif' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_consumidor (document.frm_os.nome, "nome")' style="cursor:pointer;">
		</td>
		<td>
		<input class="frm" type="text" name="cpf" size="17" maxlength="14" value="<? echo $cpf ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Digite o CPF/CNPJ do consumidor. Pode ser digitado diretamente, ou separado com pontos e traços.');">&nbsp;<img src='imagens/btn_buscar5.gif' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_consumidor (document.frm_os.cpf,"cpf")' style="cursor:pointer;">
		</td>
		</tr>


		</table>
		
		<table width="100%" border="0" cellspacing="1" cellpadding="2">
		<tr>
			<td bgcolor='#d9e2ef'>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">RG/IE Consumidor</font>

			</td>
			<td bgcolor='#d9e2ef'>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">Endereço do Consumidor</font>
			</td>
			<td bgcolor='#d9e2ef'>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">Número</font>
			</td>
			<td bgcolor='#d9e2ef'>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">Complemento</font>
			</td>
		</tr>
<tr>
<td><input class="frm" type="text" name="rg" size="15" maxlength="20" value="<? echo $rg ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;RG/IE do consumidor');"></td>
<td><input class="frm" type="text" name="endereco" size="20" maxlength="50" value="<? echo $endereco ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Endereço do consumidor (Rua, Av., etc, sem o número.');"></td>
<td><input class="frm" type="text" name="numero" size="10" maxlength="20" value="<? echo $numero ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Número do endereço do consumidor.');"></td>
<td><input class="frm" type="text" name="complemento" size="10" maxlength="30" value="<? echo $complemento ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Complemento. Ex.: Apto, sala, etc.');"></td>
</tr>




		</table>

		<hr>

		<table width="100%" border="0" cellspacing="1" cellpadding="2">
		<tr>
			<td bgcolor='#d9e2ef'>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">Bairro</font>
			</td bgcolor='#d9e2ef'>
			<td bgcolor='#d9e2ef'>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">Cep</font>
			</td>
			<td bgcolor='#d9e2ef'>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">Cidade</font>
			</td>
			<td bgcolor='#d9e2ef'>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">Estado</font>

			</td>
			<td bgcolor='#d9e2ef'>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">Fone</font>

			</td>
		</tr>
<tr>
<td><input class="frm" type="text" name="bairro" size="10" maxlength="30" value="<? echo $bairro ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Insira aqui o Bairro de residência do Cliente.');"></td>
<td><input class="frm" type="text" name="cep" id="cep" size="10" maxlength="10" value="<? echo $cep ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;CEP do endereço do consumidor.');"></td>
<td><input class="frm" type="text" name="cidade" size="15" maxlength="50" value="<? echo $cidade ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Insira aqui a cidade de residência do Cliente.');"></td>
<td><select  name="estado" size="1" class="frm" tabindex="0" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Escolha Unidade Federal (Estado).');">
					<option selected> </option>
					<?
					$sql = "SELECT * FROM tbl_estado ORDER BY estado";
					$res = pg_exec ($con,$sql);
					for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
						echo "<option ";
						if ($estado == pg_result ($res,$i,estado) ) 
							echo " selected " ;
						echo " value='" . pg_result ($res,$i,estado) . "'>";
						echo pg_result ($res,$i,estado);
						echo "</option>";
					}
					?>
				</select>
			</td>
			<td><input  class="frm telefone" type="text" name="fone" size="15" maxlength="15" value="<? echo $fone ?>" onblur="this.className='frm telefone'; displayText('&nbsp;');" onfocus="this.className='frm-on telefone'; displayText('&nbsp;Insira o telefone com o DDD. ex.: 14/4455-6677.');"></td>
		</tr>
	</table>

	<hr>

	<table width="100%" border="0" cellspacing="1" cellpadding="2">
		<tr>
			<td width='120'  bgcolor='#d9e2ef'>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">Consumidor final? </font>
			</td>
			<td width='80'>
				<input type="radio" name="consumidor_final" value="t" <? if (strlen($consumidor_final) == 0 OR $consumidor_final == 't') echo " checked"; ?>> 
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">Sim</font>
			</td>
			<td>
				<input type="radio" name="consumidor_final" value="f" <? if ($consumidor_final == 'f') echo " checked"; ?>> 
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">Não</font>
			</td>
		</tr>
	</table>

	</td>
	<td><img height="1" width="16" src="imagens/spacer.gif"></td>
</tr>
</table>

<table width="100%" border="0" cellspacing="5" cellpadding="0">
<tr>
	<td height="27" valign="middle" align="center" bgcolor="#FFFFFF">
		<input type="hidden" name="btn_acao" value="">
		<img src='imagens/btn_gravar.gif' onclick="javascript: if (document.frm_os.btn_acao.value == '' ) { document.frm_os.btn_acao.value='gravar' ; document.frm_os.submit() } else { alert ('Aguarde submissão') }" ALT="Gravar dado consumidor" border='0' style='cursor: pointer'>
	</td>
</tr>
</table>

</form>

<? include "rodape.php"; ?>
