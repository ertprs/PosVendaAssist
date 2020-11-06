<?
require '../dbconfig.php';
require '../includes/dbconnect-inc.php';

require 'autentica_revenda.php';
include '../ajax_cabecalho.php';



if (strlen($_POST["revenda"]) > 0) $revenda  = trim($_POST["revenda"]);
if (strlen($_GET["revenda"]) > 0)  $revenda  = trim($_GET["revenda"]);

#-------------------- GRAVAR -----------------

$btn_acao = $_POST ['btn_acao'];

if ($btn_acao == "Gravando...") {
	if (strlen($cnpj) > 0){
		$xcnpj = str_replace (".","",$cnpj);
		$xcnpj = str_replace ("-","",$xcnpj);
		$xcnpj = str_replace ("/","",$xcnpj);
		$xcnpj = str_replace (" ","",$xcnpj);
		$xcnpj = "'".$xcnpj."'";
	}else{
			$msg_erro = "Digite o CNPJ.";
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

	if (strlen($email) > 0) $xemail = "'".$email."'"; else $xemail = 'null';
	if (strlen($fone) > 0)  $xfone  = "'".$fone."'";  else $xfone  = 'null';
	if (strlen($fax) > 0)   $xfax   = "'".$fax."'";   else $xfax   = 'null';

	if (strlen($contato) > 0){
		$contato = str_replace ("'","\\'",$contato);
		$xcontato = "'".$contato."'";
	}else{
		$xcontato = 'null';
	}

	if (strlen($cidade) == 0) {
		$msg_erro = "Favor informar a cidade.";
	}else{
		$cidade = str_replace ("'","\\'",$cidade);
	}

	if (strlen($estado) == 0) {
		$msg_erro = "Favor informar o estado.";
	}

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

	if (strlen ($msg_erro) == 0){

		$sql = "UPDATE tbl_revenda SET
					nome           = $xnome        ,
					endereco       = $xendereco    ,
					numero         = $xnumero      ,
					complemento    = $xcomplemento ,
					bairro         = $xbairro      ,
					cep            = $xcep         ,
					cidade         = $cod_cidade  ,
					contato        = $xcontato     ,
					fone           = $xfone        ,
					fax            = $xfax         ,
					ie             = $xie
				WHERE tbl_revenda.revenda = '$login_revenda'";
		$res = @pg_exec ($con,$sql);
		if (pg_errormessage ($con) > 0) $msg_erro = pg_errormessage ($con);

		$sql = "UPDATE tbl_revenda_fabrica SET email = $xemail 
			WHERE revenda = $login_revenda 
			AND   fabrica = $login_fabrica";
		$res = @pg_exec ($con,$sql);
		if (pg_errormessage ($con) > 0) $msg_erro = pg_errormessage ($con);
	}

	if(strlen($msg_erro) == 0){
		header("Location: $PHP_SELF?ok");
		exit;
	}

}

#-------------------- Pesquisa Revenda -----------------
if (strlen($login_revenda) > 0 and strlen ($msg_erro) == 0 ) {
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
					tbl_revenda.ie           ,
					tbl_cidade.nome AS cidade,
					tbl_cidade.estado        ,
					tbl_revenda_fabrica.email
		FROM    tbl_revenda
		JOIN    tbl_cidade          USING (cidade)
		JOIN    tbl_revenda_fabrica USING (revenda)
		WHERE   tbl_revenda.revenda         = $login_revenda
		AND     tbl_revenda_fabrica.fabrica = $login_fabrica
		
		";
	$res = @pg_exec ($con,$sql);
	
	if (@pg_numrows ($res) > 0) {
		$nome             = trim(pg_result($res,0,nome));
		$cnpj             = trim(pg_result($res,0,cnpj));
		$cnpj             = substr($cnpj,0,2) .".". substr($cnpj,2,3) .".". substr($cnpj,5,3) ."/". substr($cnpj,8,4) ."-". substr($cnpj,12,2);
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

$aba = 6;
$title = "Informações da Revenda";
include 'cabecalho.php';

?>


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
if(isset($_GET["ok"])){
	echo "<br><div class='OK'>Gravado com Sucesso!</div>";
}
?>
<center><br>

<form name="frm_revenda" method="post" action="<? echo $PHP_SELF ?>">
<input type="hidden" name="revenda" value="<? echo $revenda ?>">

<table class='HD' width='650' align='center' border='0' cellpadding="1" cellspacing="3">
	<tr class="Conteudo">
		<td>CNPJ</td>
		<td>I.E.</td>
		<td>FONE</td>
		<td>FAX</td>
	</tr>
	<tr class="Conteudo">
		<td><input type="text" class='Caixa' class='Caixa' name="cnpj" size="18" maxlength="18" value="<? echo $cnpj ?>"></td>
		<td><input type="text" class='Caixa' name="ie" size="18" maxlength="20" value="<? echo $ie ?>" style="width:100px"></td>
		<td><input type="text" class='Caixa' name="fone" size="15" maxlength="20" value="<? echo $fone ?>"></td>
		<td><input type="text" class='Caixa' name="fax" size="15" maxlength="20" value="<? echo $fax ?>"></td>
	</tr>
	<tr class="Conteudo">

		<td>CONTATO</td>
		<td colspan ='3'>RAZÃO SOCIAL</td>
	</tr>
	<tr class="table_line">
		<td><input type="text" class='Caixa' name="contato" size="30" maxlength="30" value="<? echo $contato ?>" ></td>
		<td colspan ='3'><input type="text" class='Caixa' name="nome" size="50" maxlength="60" value="<? echo $nome ?>" style="width:300px"></td>
	</tr>
</table>
<br>
<table class='HD' width='650' align='center' border='0' cellpadding="1" cellspacing="3">
	<tr class="Conteudo">
		<td colspan="2">ENDEREÇO</td>
		<td>NÚMERO</td>
		<td colspan="2">COMPLEMENTO</td>
	</tr>
	<tr class="Conteudo">
		<td colspan="2"><input type="text" class='Caixa' name="endereco" size="42" maxlength="50" value="<? echo $endereco ?>"></td>
		<td><input type="text" class='Caixa' name="numero" size="10" maxlength="10" value="<? echo $numero ?>"></td>
		<td colspan="2"><input type="text" class='Caixa' name="complemento" size="35" maxlength="40" value="<? echo $complemento ?>"></td>
	</tr>
	<tr class="Conteudo">
		<td colspan="2">BAIRRO</td>
		<td>CEP</td>
		<td>CIDADE</td>
		<td>ESTADO</td>
	</tr>
	<tr class="Conteudo">
		<td colspan="2"><input type="text" class='Caixa' name="bairro" size="40" maxlength="20" value="<? echo $bairro ?>"></td>
		<td><input type="text" class='Caixa' name="cep"    size="10" maxlength="8" value="<? echo $cep ?>"></td>
		<td><input type="text" class='Caixa' name="cidade" size="30" maxlength="30" value="<? echo $cidade ?>"></td>
		<td><input type="text" class='Caixa' name="estado" size="2"  maxlength="2"  value="<? echo $estado ?>"></td>
	</tr>
</table>
<br>
<table class='HD' width='650' align='center' border='0' cellpadding="1" cellspacing="3">
	<tr class="Conteudo">
		<td colspan="3">E-MAIL</td>
	</tr>
	<tr class="Conteudo">
		<td align="left">
			<input type="text" class='Caixa' name="email" size="40" maxlength="50" value="<? echo $email ?>">
		</td>
 	</tr>
</table>
<br>
<center>

<input type='submit' name='btn_acao' value='Gravar' onclick="javascript: if (document.frm_revenda.btn_acao.value == 'Gravar' ) { document.frm_revenda.btn_acao.value='Gravando...' ; document.frm_revenda.submit() } else { alert ('Aguarde submissão') }">



</center>
<br>
<br>

</form>

<p>


<p>

<? include "rodape.php"; ?>
