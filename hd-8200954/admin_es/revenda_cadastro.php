<?php

include "dbconfig.php";
include "includes/dbconnect-inc.php";

$admin_privilegios="cadastros";
include 'autentica_admin.php';

if (strlen($_POST["revenda"]) > 0) $revenda  = trim($_POST["revenda"]);
if (strlen($_GET["revenda"]) > 0)  $revenda  = trim($_GET["revenda"]);

#-------------------- Descredenciar -----------------

$btn_descredenciar = $_POST ['btn_descredenciar'];
if ($btn_descredenciar == "descredenciar" and strlen($revenda) > 0 ) {
	$revenda = $_POST['revenda'];
	$sql = "DELETE FROM tbl_revenda WHERE revenda = $revenda;";
	$res = pg_exec ($con,$sql);
	header ("Location: $PHP_SELF");
	exit;
}

#-------------------- GRAVAR -----------------

$btn_acao = $_POST ['btn_acao'];

if ($btn_acao == "gravar") {

	/*if (strlen($_POST["nome"]) > 0) $nome  = trim($_POST["nome"]);
	if (strlen($_GET["nome"]) > 0)  $nome  = trim($_GET["nome"]);*/
 
	if (strlen($_POST["cnpj"]) > 0) $cnpj  = trim($_POST["cnpj"]);
	if (strlen($_GET["cnpj"]) > 0)  $cnpj  = trim($_GET["cnpj"]); 
	
	/*if (strlen($_POST["cidade"]) > 0) $cidade  = trim($_POST["cidade"]);
	if (strlen($_GET["cidade"]) > 0)  $cidade  = trim($_GET["cidade"]);

	if (strlen($_POST["estado"]) > 0) $estado  = trim($_POST["estado"]);
	if (strlen($_GET["estado"]) > 0)  $estado  = trim($_GET["estado"]);

	if (strlen($_POST["endereco"]) > 0) $endereco  = trim($_POST["endereco"]);
	if (strlen($_GET["endereco"]) > 0)  $endereco  = trim($_GET["endereco"]);

	if (strlen($_POST["numero"]) > 0) $numero  = trim($_POST["numero"]);
	if (strlen($_GET["numero"]) > 0)  $numero  = trim($_GET["numero"]);

	if (strlen($_POST["complemento"]) > 0) $complemento  = trim($_POST["complemento"]);
	if (strlen($_GET["complemento"]) > 0)  $complemento  = trim($_GET["complemento"]);

	if (strlen($_POST["bairro"]) > 0) $bairro  = trim($_POST["bairro"]);
	if (strlen($_GET["bairro"]) > 0)  $bairro  = trim($_GET["bairro"]);

	if (strlen($_POST["cep"]) > 0) $cep  = trim($_POST["cep"]);
	if (strlen($_GET["cep"]) > 0)  $cep  = trim($_GET["cep"]);

	if (strlen($_POST["complemento"]) > 0) $complemento  = trim($_POST["complemento"]);
	if (strlen($_GET["complemento"]) > 0)  $complemento  = trim($_GET["complemento"]);

	if (strlen($_POST["contato"]) > 0) $contato  = trim($_POST["contato"]);
	if (strlen($_GET["contato"]) > 0)  $contato  = trim($_GET["contato"]);

	if (strlen($_POST["email"]) > 0) $email  = trim($_POST["email"]);
	if (strlen($_GET["email"]) > 0)  $email  = trim($_GET["email"]);

	if (strlen($_POST["fone"]) > 0) $fone  = trim($_POST["fone"]);
	if (strlen($_GET["fone"]) > 0)  $fone  = trim($_GET["fone"]);

	if (strlen($_POST["fax"]) > 0) $fax  = trim($_POST["fax"]);
	if (strlen($_GET["fax"]) > 0)  $fax  = trim($_GET["fax"]);
	
	if (strlen($_POST["contato"]) > 0) $contato  = trim($_POST["contato"]);
	if (strlen($_GET["contato"]) > 0)  $contato  = trim($_GET["contato"]);
	
	if (strlen($_POST["ie"]) > 0) $ie  = trim($_POST["ie"]);
	if (strlen($_GET["ie"]) > 0)  $ie  = trim($_GET["ie"]);*/
	

if (strlen($cnpj) > 0){
	$xcnpj = str_replace (".","",$cnpj);
	$xcnpj = str_replace ("-","",$xcnpj);
	$xcnpj = str_replace ("/","",$xcnpj);
	$xcnpj = str_replace (" ","",$xcnpj);
	$xcnpj = "'".$xcnpj."'";
}else{
		$msg_erro = "Digite o CNPJ.";
		$msg_erro = "Digite la identificación.";

	}
	
	#----------------------------- Dados ---------------------

	if (strlen($revenda) > 0)
		$xrevenda = "'".$revenda."'";
	else
		$xrevenda = 'null';

	if (strlen($ie) > 0){
		$ie	= str_replace ("'","\\'",$ie);
		$xie = "'".$ie."'";
	}else{
		$xie = 'null';
	}

	if (strlen($nome) > 0){
		$xnome		= str_replace ("'","\\'",$nome);
		$xnome = "'".$xnome."'";
	}else{
		$xnome = 'null';
	}

	if (strlen($endereco) > 0){
		$endereco	= str_replace ("'","\\'",$endereco);
		$xendereco = "'".$endereco."'";
	}else{
		$xendereco = 'null';
	}

	if (strlen($numero) > 0)
		$xnumero = "'".$numero."'";
	else
		$xnumero = 'null';

	if (strlen($complemento) > 0)
		$xcomplemento = "'".$complemento."'";
	else
		$xcomplemento = 'null';

	if (strlen($bairro) > 0){
		$bairro = str_replace ("'","\\'",$bairro);
		$xbairro = "'".$bairro."'";
	}else{
		$xbairro = 'null';
	}

	if (strlen($cep) > 0){
		$xcep = str_replace (".","",$cep);
		$xcep = str_replace ("-","",$xcep);
		$xcep = "'".$xcep."'";
	}else{
		$xcep = 'null';
	}

	if (strlen($email) > 0)
		$xemail = "'".$email."'";
	else
		$xemail = 'null';

	if (strlen($fone) > 0)
		$xfone = "'".$fone."'";
	else
		$xfone = 'null';

	if (strlen($fax) > 0)
		$xfax = "'".$fax."'";
	else
		$xfax = 'null';


	if (strlen($contato) > 0){
		$contato = str_replace ("'","\\'",$contato);
		$xcontato = "'".$contato."'";
	}else{
		$xcontato = 'null';
	}

	if (strlen($cidade) == 0) {
		$msg_erro = "Favor informar a cidade.";
		$msg_erro = "Favor informar la ciudad.";
	}else{
		$cidade = str_replace ("'","\\'",$cidade);
	}

	if (strlen($estado) == 0) {
		$msg_erro = "Favor informar o estado.";
		$msg_erro = "Favor informar la provincia.";

	}

	if (strlen($nome) == 0){
		// verifica se revenda já está cadastrada
		$sql = "SELECT	tbl_revenda.*                     ,
						tbl_cidade.nome   AS cidade_nome  ,
						tbl_cidade.estado AS cidade_estado
				FROM	tbl_revenda
				JOIN	tbl_cidade USING (cidade)
				WHERE	tbl_revenda.cnpj = $xcnpj ";
		$res = @pg_exec ($con,$sql);

		if (@pg_numrows($res) > 0) {
			$msg_erro	 = "Revenda já está cadastrada.";
			$revenda     = pg_result($res,0,revenda);
			$ie          = pg_result($res,0,ie);
			$nome        = pg_result($res,0,nome);
			$fone        = pg_result($res,0,fone);
			$fax         = pg_result($res,0,fax);
			$contato     = pg_result($res,0,contato);
			$endereco    = pg_result($res,0,endereco);
			$numero      = pg_result($res,0,numero);
			$complemento = pg_result($res,0,complemento);
			$bairro      = pg_result($res,0,bairro);
			$cep         = pg_result($res,0,cep);
			$cidade      = pg_result($res,0,cidade_nome);
			$estado      = pg_result($res,0,cidade_estado);
			$email       = pg_result($res,0,email);
		}else{
			$msg_erro = "Revenda não cadastrada, favor completar os dados de cadastro";
			$msg_erro = "Distribuidor no catastrado, favor completar los datos de catastro";

		}
	}

	if (strlen ($msg_erro) == 0){

		// verifica se cidade já está cadastrada tbl_cidade (seleciona cidade e estado)
		$sql = "SELECT	cidade
				FROM	tbl_cidade
				WHERE	nome   = '$cidade' 
				AND		estado = '$estado'";
		$res = @pg_exec ($con,$sql);

		if(@pg_numrows($res) > 0){
			$cod_cidade = pg_result($res,0,cidade);
		}else{
			$cidade = strtoupper($cidade);
			$estado = strtoupper($estado);

			$sql = "INSERT INTO tbl_cidade(
						nome,
						estado
					)VALUES(
						'$cidade',
						'$estado'
					)";
			$res = @pg_exec ($con,$sql);

			$res		= @pg_exec ($con,"SELECT CURRVAL ('seq_cidade')");
			$cod_cidade	= pg_result ($res,0,0);
		}

		if (strlen ($revenda) > 0) {
			// update
			$sql = "UPDATE tbl_revenda SET
						nome		= $xnome        ,
						cnpj		= $xcnpj        ,
						endereco	= $xendereco    ,
						numero		= $xnumero      ,
						complemento	= $xcomplemento ,
						bairro		= $xbairro      ,
						cep			= $xcep         ,
						cidade		= $cod_cidade  ,
						contato		= $xcontato     ,
						email		= $xemail       ,
						fone		= $xfone        ,
						fax			= $xfax         ,
						ie			= $xie,
						pais      = '$login_pais'
					WHERE tbl_revenda.revenda = '$revenda'";

		}else{

			#-------------- INSERT ---------------
			$sql = "INSERT INTO tbl_revenda (
						nome       ,
						cnpj       ,
						endereco   ,
						numero     ,
						complemento,
						bairro     ,
						cep        ,
						cidade     ,
						contato    ,
						email      ,
						fone       ,
						fax        ,
						ie         ,
						pais
					) VALUES (
						$xnome       ,
						$xcnpj       ,
						$xendereco   ,
						$xnumero     ,
						$xcomplemento,
						$xbairro     ,
						$xcep        ,
						$cod_cidade ,
						$xcontato    ,
						$xemail      ,
						$xfone       ,
						$xfax        ,
						$xie         ,
						'$login_pais'
					)";
		}
		$res = @pg_exec ($con,$sql);

		if (pg_errormessage ($con) > 0) $msg_erro = pg_errormessage ($con);
	}

	if(strlen($msg_erro) == 0){
		header("Location: menu_cadastro.php");
		exit;
	}

}

#-------------------- Pesquisa Revenda -----------------
if (strlen($revenda) > 0 and strlen ($msg_erro) == 0 ) {
	$sql = "SELECT	tbl_revenda.revenda      ,
					tbl_revenda.nome         ,
					tbl_revenda.endereco     ,
					tbl_revenda.bairro       ,
					tbl_revenda.complemento  ,
					tbl_revenda.numero       ,
					tbl_revenda.cep          ,
					tbl_revenda.cnpj         ,
					tbl_revenda.fone         ,
					tbl_revenda.fax          ,
					tbl_revenda.contato      ,
					tbl_revenda.fax          ,
					tbl_revenda.email        ,
					tbl_revenda.ie           ,
					tbl_cidade.nome AS cidade,
					tbl_cidade.estado        
			FROM	tbl_revenda
			JOIN	tbl_cidade USING(cidade)
			WHERE	tbl_revenda.revenda = $revenda ";
	$res = @pg_exec ($con,$sql);
	
	if (@pg_numrows ($res) > 0) {
		$nome             = trim(pg_result($res,0,nome));
		$cnpj             = trim(pg_result($res,0,cnpj));
		$endereco         = trim(pg_result($res,0,endereco));
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
		$ie               = trim(pg_result($res,0,ie));
	}
}

$visual_black = "manutencao-admin";

$title     = "Catastro de Distribuidores";
$cabecalho = "Catastro de Distribuidores";

$layout_menu = "cadastro";
include 'cabecalho.php';

?>

<script language="JavaScript">
function fnc_pesquisa_codigo_posto (codigo, nome) {
    var url = "";
    if (codigo != "" && nome == "") {
        url = "pesquisa_posto.php?codigo=" + codigo;
        janela = window.open(url,"janela","toolbar=no,location=no,status=no,scrollbars=yes,directories=no,width=600,height=400,top=18,left=0");
        janela.focus();
    }
}

function fnc_pesquisa_nome_posto (codigo, nome) {
    var url = "";
    if (codigo == "" && nome != "") {
        url = "pesquisa_posto.php?nome=" + nome;
        janela = window.open(url,"janela","toolbar=no,location=no,status=no,scrollbars=yes,directories=no,width=600,height=400,top=18,left=0");
        janela.focus();
    }
}

function fnc_revenda_pesquisa (campo, campo2, tipo) {
	if (tipo == "nome" ) {
		var xcampo = campo;
	}

	if (tipo == "cnpj" ) {
		var xcampo = campo2;
	}

	if (xcampo.value != "") {
		var url = "";
		url = "revenda_pesquisa.php?forma=reload&campo=" + xcampo.value + "&tipo=" + tipo ;
		janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=500, height=400, top=0, left=0");
		janela.retorno = "<? echo $PHP_SELF ?>";
		janela.nome	= campo;
		janela.cnpj	= campo2;
		janela.focus();
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
if (strlen ($msg_erro) > 0) {
	echo "<table width='600' align='center' border='1' bgcolor='#ffeeee'>";
	echo "<tr>";
	echo "<td align='center'>";
	echo "	<font face='arial, verdana' color='#330000' size='-1'>";
	echo $msg_erro;
	echo "	</font>";
	echo "</td>";
	echo "</tr>";
	echo "</table>";
}
?>

<table width='600' align='center' border='0' bgcolor='#d9e2ef'>
<tr>
	<td align='center'>
		<font face='arial, verdana' color='#596d9b' size='-1'>
		Para catastrar uma nueva reventa, complete solo su identificación oficial y click guardar.
		<br>
		La eventual ya existencia de la reventa será verificada.
		</font>
	</td>
</tr>
</table>

<form name="frm_revenda" method="post" action="<? echo $PHP_SELF ?>">
<input type="hidden" name="revenda" value="<? echo $revenda ?>">
<table width='650' align='center' border='0' cellpadding="1" cellspacing="3">
	<tr>
		<td><b><?echo $erro;?></b></td>
	</tr>
</table>

<table class="border" width='650' align='center' border='0' cellpadding="1" cellspacing="3">
	<tr>
		<td colspan="4" class="menu_top">
			<font color='#36425C'>INFORMACIONES CATASTRALES
		</td>
	</tr>
	<tr class="menu_top">
		<td>IDENTIFICACIÓN SERVICIO 01</td>
		<td>IDENTIFICACIÓN SERVICIO 02</td>
		<td>TELÉFONO</td>
		<td>FAX</td>
	</tr>
	<tr class="table_line">
		<td><input type="text" name="cnpj" size="18" maxlength="18" value="<? echo $cnpj ?>">&nbsp;<img src='../imagens/btn_buscar5.gif' style="cursor:pointer" border='0' align='absmiddle' onclick="javascript: fnc_revenda_pesquisa (document.frm_revenda.nome,document.frm_revenda.cnpj,'cnpj')"></td>
		<td><input type="text" name="ie" size="18" maxlength="20" value="<? echo $ie ?>" style="width:100px"></td>
		<td><input type="text" name="fone" size="15" maxlength="20" value="<? echo $fone ?>"></td>
		<td><input type="text" name="fax" size="15" maxlength="20" value="<? echo $fax ?>"></td>
	</tr>
	<tr class="menu_top">

		<td>CONTACTO</td>
		<td colspan ='3'>NOMBRE OFICIAL DEL SERVICIO</td>
	</tr>
	<tr class="table_line">
		<td><input type="text" name="contato" size="30" maxlength="30" value="<? echo $contato ?>" style="width:100px"></td>
		<td colspan ='3'><input type="text" name="nome" size="50" maxlength="60" value="<? echo $nome ?>" style="width:300px">&nbsp;<img src='../imagens/btn_buscar5.gif' style="cursor:pointer" border='0' align='absmiddle' onclick="javascript: fnc_revenda_pesquisa (document.frm_revenda.nome,document.frm_revenda.cnpj,'nome')"></td>
	</tr>
	<tr>
		<td colspan="4">
			<a href='<? echo $PHP_SELF ?>?listar=todos#revendas'><img src="imagens/btn_listarevenda.gif"></a>
		</td>
	</tr>
</table>

<br>
<center>

<input type='hidden' name='btn_acao' value=''>
<img src="imagens_admin/btn_gravar.gif" style="cursor: pointer;" onclick="javascript: if (document.frm_revenda.btn_acao.value == '' ) { document.frm_revenda.btn_acao.value='gravar' ; document.frm_revenda.submit() } else { alert ('Aguarde submisión') }" ALT="GUARDAR FORMULARIOS" border='0'>

<input type='hidden' name='btn_descredenciar' value=''>
<img src='../imagens/btn_excluir.gif' width='72px' height='18px' style="cursor: pointer;" onclick="javascript: if (document.frm_revenda.btn_descredenciar.value == '' ) { if(confirm('Desea realmente excluir este distribuidor?') == true) { document.frm_revenda.btn_descredenciar.value='descredenciar'; document.frm_revenda.submit(); }else{ return; }; } else { alert ('Aguarde submisión') }" ALT="" border='0'>

<img src="imagens_admin/btn_limpar.gif" style="cursor: pointer;" onclick="javascript: if (document.frm_revenda.btn_acao.value == '' ) { document.frm_revenda.btn_acao.value='reset' ; window.location='<? echo $PHP_SELF ?>' } else { alert ('Aguarde submisión') }"  border='0'>

</center>
<br>
<table class="border" width='650' align='center' border='0' cellpadding="1" cellspacing="3">
	<tr class="menu_top">
		<td colspan="2">DIRECCIÓN</td>
		<td>NÚMERO</td>
		<td colspan="2">COMPLEMENTO</td>
	</tr>
	<tr class="table_line">
		<td colspan="2"><input type="text" name="endereco" size="42" maxlength="50" value="<? echo $endereco ?>"></td>
		<td><input type="text" name="numero" size="10" maxlength="10" value="<? echo $numero ?>"></td>
		<td colspan="2"><input type="text" name="complemento" size="35" maxlength="40" value="<? echo $complemento ?>"></td>
	</tr>
	<tr class="menu_top">
		<td colspan="2">BARRIO</td>
		<td>APARTADO POSTAL</td>
		<td>CIUDAD</td>
		<td>PROVINCIA</td>
	</tr>
	<tr class="table_line">
		<td colspan="2"><input type="text" name="bairro" size="40" maxlength="20" value="<? echo $bairro ?>"></td>
		<td><input type="text" name="cep"    size="10" maxlength="8" value="<? echo $cep ?>"></td>
		<td><input type="text" name="cidade" size="30" maxlength="30" value="<? echo $cidade ?>"></td>
		<td><input type="text" name="estado" size="2"  maxlength="2"  value="<? echo $estado ?>"></td>
	</tr>
</table>
<br>
<table class="border" width='650' align='center' border='0' cellpadding="1" cellspacing="3">
	<tr class="menu_top">
		<td colspan="3">E-MAIL</td>
	</tr>
	<tr class="table_line">
		<td align="center">
			<input type="text" name="email" size="40" maxlength="50" value="<? echo $email ?>">
		</td>
 	</tr>
</table>
<br>

</form>

<p>

<?
if ($_GET ['listar'] == 'todos') {
	$sql = "SELECT	tbl_revenda.revenda,
					tbl_revenda.nome           ,
					tbl_revenda.endereco        ,
					tbl_revenda.bairro         ,
					tbl_revenda.complemento    ,
					tbl_revenda.numero         ,
					tbl_revenda.cep            ,
					tbl_revenda.cnpj           ,
					tbl_revenda.fone           ,
					tbl_revenda.fax            ,
					tbl_revenda.contato        ,
					tbl_revenda.fax            ,
					tbl_revenda.email          ,
					tbl_revenda.ie             ,
					tbl_cidade.nome AS cidade  ,
					tbl_cidade.estado          
			FROM     tbl_revenda
			JOIN     tbl_cidade USING(cidade)
			JOIN     tbl_estado using(estado)
			where    tbl_revenda.pais = '$login_pais'
			ORDER BY tbl_revenda.nome ASC";
	$res = pg_exec ($con,$sql);

	for ($i = 0; $i < pg_numrows ($res); $i++) {
		if ($i % 20 == 0) {
			if ($i > 0) echo "</table>";
			flush();

			echo "<table width='650' align='center' border='0'>";
			echo "<tr class='top_list'>";

			echo "<td align='center' style='width: 200px;'>";
			echo "<b>Ciudad</b>";
			echo "</td>";

			echo "<td align='center'>";
			echo "<b>Estado</b>";
			echo "</td>";

			echo "<td align='center'>";
			echo "<b>NOMBRE</b>";
			echo "</td>";

			echo "<td align='center' nowrap>";
			echo "<b>IDENTIFICACIÓN SERVICIO 01</b>";
			echo "</td>";

			echo "</tr>";
		}

		$cnpj = pg_result ($res,$i,cnpj);
/*		$cnpj = substr($cnpj,0,2) .".". substr($cnpj,2,3) .".". substr($cnpj,5,3) ."/". substr($cnpj,8,4) ."-". substr($cnpj,12,2);
*/

		echo "<tr class='line_list'>";

		echo "<td align='left'>";
		echo pg_result ($res,$i,cidade);
		echo "</td>";

		echo "<td align='left'>";
		echo pg_result ($res,$i,estado);
		echo "</td>";

		echo "<td align='left'>";
		echo "<a href='$PHP_SELF?revenda=" . pg_result ($res,$i,revenda) . "'>";
		echo pg_result ($res,$i,nome);
		echo "</a>";
		echo "</td>";

		echo "<td align='left' nowrap>";
		echo $cnpj;
		echo "</td>";

		echo "</tr>";
	}

	echo "</table>";
}

?>

<p>

<? include "rodape.php"; ?>
