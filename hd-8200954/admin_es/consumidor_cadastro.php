<?

include "dbconfig.php";
include "includes/dbconnect-inc.php";

$admin_privilegios="cadastros";
include 'autentica_admin.php';
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
	$rg               = trim($_POST['rg']);
	$endereco         = trim($_POST['endereco']);
	$numero           = trim($_POST['numero']);
	$complemento      = trim($_POST['complemento']);
	$bairro           = trim($_POST['bairro']);
	$cep              = trim($_POST['cep']);
	$cidade           = trim($_POST['cidade']);
	$estado           = trim($_POST['estado']);
	$fone             = trim($_POST['fone']);
	$contrato         = trim($_POST['contrato']);
	$consumidor_final = trim($_POST['consumidor_final']);
	$contrato_numero  = trim($_POST['contrato_numero']);
	
	if (strlen($estado) == 0){
		$msg_erro = "Elija la provincia del cliente.";
	}else{
		$xestado = "'".$estado."'";
	}
	if (strlen($cidade) == 0){
		$msg_erro = "Digite la ciudad del cliente";
	}else{
		$xcidade = "'".$cidade."'";
	}
	if (strlen($nome) == 0){
		$msg_erro = "Insira el nombre del cliente.";
	}else{
		$xnome = "'".$nome."'";
	}
	if (strlen($cpf) == 0){
		//$msg_erro = "Digite o CPF/CNPJ do Consumidor.";
		$xcpf = "null";
	}else{
		$xcpf = "'".$cpf."'";
	}
	if (strlen($rg) == 0){
		$xrg = 'null';
	}else{
		$xrg = "'".$rg."'";
	}
	if (strlen($contrato) == 0){
		$msg_erro = "Informe el numero de contracto del cliente.";
	}else{
		$xcontrato = "'".$contrato."'";
	}
	if (strlen($consumidor_final) == 0){

	}else{
		$xconsumidor_final = "'".$consumidor_final."'";
	}
	if (strlen($contrato_numero) == 0){
		$xcontrato_numero = 'null';
	}else{
		$xcontrato_numero = "'".$contrato_numero."'";
	}
	if (strlen($endereco) == 0){
		$xendereco = 'null';
	}else{
		$xendereco = "'".$endereco."'";
	}
	if (strlen($numero) == 0){
		$xnumero = 'null';
	}else{
		$xnumero = "'".$numero."'";
	}
	if (strlen($complemento) == 0){
		$xcomplemento = 'null';
	}else{
		$xcomplemento = "'".$complemento."'";
	}
	if (strlen($bairro) == 0){
		$xbairro = 'null';
	}else{
		$xbairro = "'".$bairro."'";
	}
	if (strlen($cep) == 0){
		$xcep = 'null';
	}else{
		$xcep = "'".$cep."'";
	}
	if (strlen($fone) == 0){
		$xfone = 'null';
	}else{
		$xfone = "'".$fone."'";
	}
	if (strlen($msg_erro) == 0) {
		$res = pg_exec($con,"BEGIN TRANSACTION");

		if (strlen ($cidade) > 0 AND strlen ($estado) > 0) {
			$sql = "SELECT * FROM tbl_cidade WHERE nome = '$cidade' AND estado = '$estado'";
			$res = pg_exec ($con,$sql);
			if (pg_numrows ($res) == 1) {
				$xcidade = pg_result ($res,0,cidade);
			}else{
				$sql = "INSERT INTO tbl_cidade (nome, estado) VALUES ('$cidade','$estado')";
				$res = pg_exec ($con,$sql);
				$sql = "SELECT currval ('seq_cidade')";
				$res = pg_exec ($con,$sql);
				$xcidade = pg_result ($res,0,0);
			}
		}else{
			$xcidade = 'null';
		}

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
						consumidor_final,
						contrato_numero
					) VALUES (
						$xnome            ,
						$xendereco        ,
						$xnumero          ,
						$xcomplemento     ,
						$xbairro          ,
						$xcep             ,
						$xcidade          ,
						$xfone            ,
						$xcpf             ,
						$xcontrato        ,
						$xrg              ,
						$xconsumidor_final,
						$xcontrato_numero
					)";

		}else{

			/*================ ALTERA OS =========================*/
			$sql = "UPDATE tbl_cliente SET
						nome             = $xnome            ,
						endereco         = $xendereco        ,
						numero           = $xnumero          ,
						complemento      = $xcomplemento     ,
						bairro           = $xbairro          ,
						cep              = $xcep             ,
						cidade           = $xcidade          ,
						fone             = $xfone            ,
						cpf              = $xcpf             ,
						contrato         = $xcontrato        ,
						rg               = $xrg              ,
						consumidor_final = $xconsumidor_final,
						contrato_numero  = $xcontrato_numero
					WHERE cliente   = '$cliente'";
		}
		$res = @pg_exec ($con,$sql);
//echo $sql;

		$msg_erro = pg_errormessage($con);
		$msg_erro = substr($msg_erro,6);

	}
	
	if (strpos ($msg_erro,"duplicate key violates unique constraint \"tbl_cliente_cpf\"") > 0)
		$msg_erro = "Esta Identificación ya esta catastrada";

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
		$contrato_numero  = pg_result ($res,0,contrato_numero);
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
	$contrato_numero	= $_POST['contrato_numero'];
}

$title       = "Catastro de Usuários"; 
$layout_menu = 'cadastro';

include "cabecalho.php";

?>

<!--=============== <FUNÇÕES> ================================!-->

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
	janela.contrato		= document.frm_os.contrato;
	janela.contrato_numero	= document.frm_os.contrato_numero;
	janela.consumidor_final	= document.frm_os.consumidor_final;

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


		<!-- ------------- Formulário ----------------- -->
		<form style="MARGIN: 0px; WORD-SPACING: 0px" name="frm_os" method="post" action="<? echo $PHP_SELF ?>">
		<input class="frm" type="hidden" name="cliente" value="<? echo $cliente; ?>">

		<table width="700" border="0" cellspacing="3" cellpadding="1" align='center'>
		<tr bgcolor='#d9e2ef'>
			<td>
				<font size="2" face="Geneva, Arial, Helvetica, san-serif">Nombre Usuario</font>
			</td>
			<td>
				<font size="2" face="Geneva, Arial, Helvetica, san-serif">ID Usuario 1</font>
			</td>			
			
		</tr>
			
		<tr>
			<td>
				<input class="frm" type="text" name="nome" size="50" maxlength="50" value="<? echo $nome ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Insira el nombre del cliente.');nextfield ='cpf';">&nbsp;<img src='imagens/btn_buscar5.gif' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_consumidor (document.frm_os.nome, "nome")' style="cursor:pointer;">
			</td>
			<td>
				<input class="frm" type="text" name="cpf" size="17" maxlength="14" value="<? echo $cpf ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Digite la Identificación del consumidor. Puede ser digitado directamente o separado por puntos y trazo.');">&nbsp;<img src='imagens/btn_buscar5.gif' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_consumidor (document.frm_os.cpf,"cpf")' style="cursor:pointer;">
			</td>
		</tr>
		</table>
		
		<table width="700" border="0" cellspacing="3" cellpadding="1" align='center'>
		<tr bgcolor='#d9e2ef'>
			<td>
				<font size="2" face="Geneva, Arial, Helvetica, san-serif">ID Usuario 2</font>
			</td>
			<td>
				<font size="2" face="Geneva, Arial, Helvetica, san-serif">Dirección del Usuario</font>
			</td>
			<td>
				<font size="2" face="Geneva, Arial, Helvetica, san-serif">Número</font>
			</td>
			<td>
				<font size="2" face="Geneva, Arial, Helvetica, san-serif">Complemento</font>
				</td>
			
			</tr>
			
			
			<tr>
			<td>	<input class="frm" type="text" name="rg" size="15" maxlength="20" value="<? echo $rg ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Identificaccion 2 del cliente');">
			</td>
			<td>	<input class="frm" type="text" name="endereco" size="20" maxlength="50" value="<? echo $endereco ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Dirección del cliente ( Calle, Av, etc... Sin el numero).');">
			</td>
			<td><input class="frm" type="text" name="numero" size="10" maxlength="20" value="<? echo $numero ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Numero de dirección del cliente.');">
			</td>
			
			<td><input class="frm" type="text" name="complemento" size="10" maxlength="30" value="<? echo $complemento ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Complemento Ex.: Apt, sala, etc.');">
			</td>
			</tr>

		</table>

		<table width="700" border="0" cellspacing="3" cellpadding="1" align='center'>
		<tr bgcolor='#d9e2ef'>
			<td>
				<font size="2" face="Geneva, Arial, Helvetica, san-serif">Barrio</font>
			</td>
			<td>
				<font size="2" face="Geneva, Arial, Helvetica, san-serif">Cep</font>
			</td>
			<td>
				<font size="2" face="Geneva, Arial, Helvetica, san-serif">Apartado Postal</font>
				</td>
			<td>
				<font size="2" face="Geneva, Arial, Helvetica, san-serif">PROVINCIA</font>
				</td>
				<td>
				<font size="2" face="Geneva, Arial, Helvetica, san-serif">Teléfono</font>
				</td>
		<tr>	
		
		<td>
		<input class="frm" type="text" name="bairro" size="10" maxlength="30" value="<? echo $bairro ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Insira el barrio de residencia.');">
		</td>
		<td>
			<input class="frm" type="text" name="cep" size="10" maxlength="10" value="<? echo $cep ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Aparato de la dirección del cliente.');">		
		</td>
		<td>
				<input class="frm" type="text" name="cidade" size="15" maxlength="50" value="<? echo $cidade ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Insira la ciudad de residencia del cliente');">
			</td>
			<td>
				<select  name="estado" size="1" class="frm" tabindex="0" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Escolha Unidade Federal (Estado).');">
					<option selected> </option>
					<?
					$sql = "SELECT * FROM tbl_estado WHERE  pais='$login_pais' ORDER BY estado";
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
			<td>	<input  class="frm" type="text" name="fone" size="10" maxlength="20" value="<? echo $fone ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Insira el teléfono.');">
			</td>
		</tr>
			


		<table width="700" border="0" cellspacing="3" cellpadding="1" align='center'>
		<tr >
			<td width='120' bgcolor='#d9e2ef'>
				<font size="2" face="Geneva, Arial, Helvetica, san-serif">Usuario Final?? </font>
			</td>
			<td width='80' bgcolor='#f4f4f4'>
				<input type="radio" name="consumidor_final" value="t" <? if (strlen($consumidor_final) == 0 OR $consumidor_final == 't') echo " checked"; ?>> 
				<font size="2" face="Geneva, Arial, Helvetica, san-serif">Si&nbsp;&nbsp;</font>

				<input type="radio" name="consumidor_final" value="f" <? if ($consumidor_final == 'f') echo " checked"; ?>> 
				<font size="2" face="Geneva, Arial, Helvetica, san-serif">No</font>
			</td>
		</tr>
		</table>

		<hr>

		<table width="700" border="0" cellspacing="5" cellpadding="1" align='center'>
		<tr bgcolor='#d9e2ef'>
			<td width='120'>
				<font size="2" face="Geneva, Arial, Helvetica, san-serif">Tiene Contracto?? </font>
			</td>
			<td width='180' bgcolor='#f4f4f4' align='left'>
				<input type="radio" name="contrato" value="t" <? if ($contrato == 't') echo " checked"; ?>> 
				<font size="2" face="Geneva, Arial, Helvetica, san-serif">Si&nbsp;&nbsp;</font>
	
				<input type="radio" name="contrato" value="f" <? if (strlen($contrato) == 0 OR $contrato == 'f') echo " checked"; ?>> 
				<font size="2" face="Geneva, Arial, Helvetica, san-serif">No</font>
			</td>
			<td width='120'>
				<font size="2" face="Geneva, Arial, Helvetica, san-serif">Número del Contracto</font>
			</td>
			<td   bgcolor='#f4f4f4' align='left'>&nbsp;
				<input class="frm" type="text" name="contrato_numero" size="10" maxlength="10" value="<? echo $contrato_numero ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Informe el numero de contracto del cliente.');">
			</td>
		</tr>
		</table>

	</td>
	


<table width="100%" border="0" cellspacing="5" cellpadding="0">
<tr>
	<td height="27" valign="middle" align="center" bgcolor="#FFFFFF">
		<input type="hidden" name="btn_acao" value="">
		<img src='imagens/btn_gravar.gif' onclick="javascript: if (document.frm_os.btn_acao.value == '' ) { document.frm_os.btn_acao.value='gravar' ; document.frm_os.submit() } else { alert ('Espere la submisión') }" ALT="Gravar del cliente" border='0' style='cursor: pointer'>
	</td>
</tr>
</table>

</form>

<? include "rodape.php"; ?>
