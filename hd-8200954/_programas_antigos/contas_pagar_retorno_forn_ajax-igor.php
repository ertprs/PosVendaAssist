<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

include '../funcoes.php';

$posto = trim($_POST ['posto']);
if(strlen($posto)==0){
	$posto = trim($_GET['posto']);
}

$btn_acao = $_POST["btn_acao"];
if($btn_acao=="gravar"){

	$cnpj  = trim($_POST['cnpj']);
	$xcnpj = str_replace (".","",$cnpj);
	$xcnpj = str_replace ("-","",$xcnpj);
	$xcnpj = str_replace ("/","",$xcnpj);
	$xcnpj = str_replace (" ","",$xcnpj);

	$nome  = trim($_POST ['nome']);
	$posto = trim($_POST ['posto']);

	// VERIFICA SE POSTO ESTÁ CADASTRADO
	if (strlen($nome) == 0 AND strlen($xcnpj) > 0) {

		$sql = "SELECT posto
				FROM   tbl_posto
				WHERE  cnpj = '$xcnpj'";
		$res = pg_exec ($con,$sql);

		if (pg_numrows ($res) > 0) {
			$posto = pg_result ($res,0,0);
			header ("Location: $PHP_SELF?posto=$posto&mensagem=Posto ja cadastrado!");
			exit;
		}else{
			$msg_erro = "Posto não cadastrado, favor completar os dados do cadastro.";
		}
	}

	if(strlen($xcnpj) == 0)
		$msg_erro = "Digite o CNPJ/CPF do Posto.";
	else
		$cnpj = $xcnpj;

	$cidade                    = $_POST["cidade"];
	if (strlen($cidade) > 0){

	}else{	$msg_erro .= '<BR>Digite a cidade.';}
	
	$estado                    = $_POST["estado"];
	if (strlen($estado) > 0){
	}else{	$msg_erro .= '<BR>Digite o estado.';}	
	$ie                      = $_POST["ie"];
	if (strlen($ie) > 0){
	}else{	$msg_erro .= '<BR>Digite a Inscrição Estadual.';}
	
	$codigo                  = $_POST["codigo"];
	if (strlen($codigo) > 0){
	}else{	$msg_erro .= '<BR>Digite o Código do Posto.';}

	$nome                    = $_POST["nome"];
	if (strlen($nome) > 0){
	}else{	$msg_erro .= '<BR>Digite o endereço.';}

	$endereco            = $_POST["endereco"];
	if (strlen($endereco) > 0){
	}else{	$msg_erro .= '<BR>Digite o endereço.';}
	
	$numero              = $_POST["numero"];
	if (strlen($numero) > 0){
	}else{	$msg_erro .= '<BR>Digite o número.';}
	
//	$complemento         = $_POST["complemento"];
//	if (strlen($complemento) > 0){$complemento = "'".$complemento."'";}
	
/*	$bairro              = $_POST["bairro"];
	if (strlen($bairro) > 0){$bairro = "'".$bairro."'";
	}else{	$msg_erro .= '<BR>Digite o bairro.';}
	
	$cep                 = $_POST["cep"];
	if (strlen($cep) > 0){$cep = "'".$cep."'";
	}else{	$msg_erro .= '<BR>Digite o CEP.';}
	
	$email               = $_POST["email"];
	if (strlen($email) > 0){$email = "'".$email."'";
	}else{	$msg_erro .= '<BR>Digite o e-mail.';}
	
	$fone                = $_POST["fone"];
	if (strlen($fone) > 0){$fone = "'".$fone."'";
	}else{	$msg_erro .= '<BR>Digite o telefone.';}
	
	$fax                 = $_POST["fax"];
	if (strlen($fax) > 0){$fax = "'".$fax."'";}else{$fax = "''";}
	
	$contato             = $_POST["contato"];
	if (strlen($contato) > 0){$contato = "'".$contato."'";}else{$contato = "''";}
	
	$capital_interior    = $_POST["capital_interior"];
	if (strlen($capital_interior) > 0){$capital_interior = "'".$capital_interior."'";
	}else{$capital_interior = "''";}
	
	$nome_fantasia       = $_POST["nome_fantasia"];
	if (strlen($nome_fantasia) > 0){$nome_fantasia = "'".$nome_fantasia."'";
	}else{$nome_fantasia = "''";}
	
	$obs                 = $_POST["obs"];
	if (strlen($obs) > 0){$obs = "'".$obs."'";}else{$obs = "''";}
*/
	if(strlen($msg_erro)==0){
		//$res = @pg_exec($con,"BEGIN TRANSACTION");
		$sql1="INSERT INTO tbl_posto(
											cnpj                  ,
											ie					  ,
											nome		          ,
											fantasia              ,
											endereco              ,
											numero                ,
											complemento           ,
											bairro                ,
											cidade                ,
											estado                ,
											capital_interior      ,
											cep                   ,
											fone                  ,
											fax                   ,
											contato               ,
											email
										)values(
											'$cnpj'              ,
											'$ie'                 ,
											'$nome'               ,
											'$nome_fantasia'      ,
											'$endereco'           ,
											'$numero'             ,
											'$complemento'        ,
											'$bairro'             ,
											'$cidade'             ,
											'$estado'             ,
											'$capital_interior'   ,
											'$cep'                ,
											'$fone'               ,
											'$fax'                ,
											'$contato'            ,
											'$email'              )";
		//echo nl2br($sql1);
		//$res = pg_exec($con, $sql1);
	
		if (strlen (pg_errormessage($con)) > 0 ) {
			$msg_erro.= pg_errormessage($con);
			//$res = @pg_exec ($con,"COMMIT TRANSACTION");
		}else{
			//$res = @pg_exec ($con,"ROLLBACK TRANSACTION");
		}
	}/*atualiza dados posto_fabrica*/

/*
,
	banco               = $banco                ,
	agencia             = $agencia              ,
	conta               = $conta                ,
	nomebanco           = (select nome from tbl_banco where codigo=$banco limit 1),
	favorecido_conta    = $favorecido_conta     , 
	cpf_conta           = $cpf_conta            ,
	tipo_conta          = $tipo_conta           ,
	obs_conta           = $obs_conta            ,

*/

}
echo "ok|";

if(strlen($posto)==0) {
	//echo "posto eh igual a zero:0";

}else{

	//echo "pesquisa posto ";
#-------------------- Pesquisa Posto -----------------
	$sql = "SELECT  tbl_posto_fabrica.obs                 ,
					tbl_posto_fabrica.posto               ,
					tbl_posto_fabrica.codigo_posto        ,
					tbl_posto.nome                        ,
					tbl_posto.cnpj                        ,
					tbl_posto.ie                          ,
					tbl_posto.endereco                    ,
					tbl_posto.numero                      ,
					tbl_posto.complemento                 ,
					tbl_posto.bairro                      ,
					tbl_posto.cep                         ,
					tbl_posto.cidade                      ,
					tbl_posto.estado                      ,
					tbl_posto.email                       ,
					tbl_posto.fone                        ,
					tbl_posto.fax                         ,
					tbl_posto.contato                     ,
					tbl_posto.capital_interior            ,
					tbl_posto.nome_fantasia               ,
					to_char(tbl_posto_fabrica.data_alteracao,'DD/MM/YYYY') AS data_alteracao
			FROM	tbl_posto
			LEFT JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
			WHERE   tbl_posto_fabrica.fabrica = $login_fabrica
			AND     tbl_posto_fabrica.posto   = $posto ";
	$res = pg_exec ($con,$sql);


	if (@pg_numrows ($res) > 0) {
		$codigo           = trim(pg_result($res,0,codigo_posto));
		$nome             = trim(pg_result($res,0,nome));
		$cnpj             = trim(pg_result($res,0,cnpj));
		$ie               = trim(pg_result($res,0,ie));
		$cidade              = trim(pg_result($res,0,cidade));
		$estado              = trim(pg_result($res,0,estado));
	//estes dados nao sao atualizados
		if (strlen($cnpj) == 14) {
			$cnpj = substr($cnpj,0,2) .".". substr($cnpj,2,3) .".". substr($cnpj,5,3) ."/". substr($cnpj,8,4) ."-". substr($cnpj,12,2);
		}
		if (strlen($cnpj) == 11) {
			$cnpj = substr($cnpj,0,3) .".". substr($cnpj,3,3) .".". substr($cnpj,6,3) ."-". substr($cnpj,9,2);
		}

		$endereco            = trim(pg_result($res,0,endereco));
		$endereco            = str_replace("\"","",$endereco);
		$numero              = trim(pg_result($res,0,numero));
		$complemento         = trim(pg_result($res,0,complemento));
		$bairro              = trim(pg_result($res,0,bairro));
		$cep                 = trim(pg_result($res,0,cep));
		$email               = trim(pg_result($res,0,email));
		$fone                = trim(pg_result($res,0,fone));
		$fax                 = trim(pg_result($res,0,fax));
		$contato             = trim(pg_result($res,0,contato));
		$capital_interior    = trim(pg_result($res,0,capital_interior));
		$nome_fantasia       = trim(pg_result($res,0,nome_fantasia));
		$obs                 = trim(pg_result($res,0,obs));
		/*$banco               = trim(pg_result($res,0,banco));
		$agencia             = trim(pg_result($res,0,agencia));
		$conta               = trim(pg_result($res,0,conta));
		$favorecido_conta    = trim(pg_result($res,0,favorecido_conta));
		$cpf_conta           = trim(pg_result($res,0,cpf_conta));
		$tipo_conta          = trim(pg_result($res,0,tipo_conta));
		$obs_conta           = trim(pg_result($res,0,obs_conta));*/
		$data_alteracao	     = trim(pg_result($res,0,data_alteracao));
	}
}

?>

<style type="text/css">

.menu_top {

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
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	color:#000000;
	border: 0px solid;
	background-color: #ffffff
}
.table_line2 {
	color:#000000;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	border: 0px solid;
	background-color: #e7e9ec
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
	echo "<table width='600' align='center' border='0' bgcolor='#ffeeee'>";
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

<p>

<form name="frm_posto" method="post" action="<? echo $PHP_SELF ?>">
<table class="border" width='660' align='center' border='0' cellpadding="1" cellspacing="3">
	<tr>
		<td colspan="5" class="menu_top" align='center'>
			<font color='#36425C'><? echo "INFORMAÇÕES CADASTRAIS";?>
		</td>
	</tr>
	<tr bgcolor='#d9e2ef' class="table_line" align='center'>
		<td>CNPJ/CPF</td>
		<td>I.E.</td>
		<td>FONE</td>
		<td>FAX</td>
		<td>CONTATO</td>
	</tr>
	<tr class="table_line" align='center'>
		<td><input type="text" name="cnpj" size="15" maxlength="20" value="<? echo $cnpj ?>"></td>
		<td><input type="text" name="ie" size="20" maxlength="20" value="<? echo $ie ?>"></td>
		<td><input type="text" name="fone" size="10" maxlength="20" value="<? echo $fone ?>"></td>
		<td><input type="text" name="fax" size="10" maxlength="20" value="<? echo $fax ?>"></td>
		<td><input type="text" name="contato" size="20" maxlength="30" value="<? echo $contato ?>" style="width:100px"></td>
	</tr>
	<tr bgcolor='#d9e2ef' class="table_line" align='center'>
		<td colspan="2"><? echo "CÓDIGO";?></td>
		<td colspan="3"><? echo "RAZÃO SOCIAL";?></td>
	</tr>
	<tr class="table_line" align='center'>
		<td colspan="2"><input type="text" name="codigo" value="<? echo $codigo ?>"></td>		
		<td colspan="3"><input type="text" name="nome" value="<? echo $nome ?>"></td>
		
	</tr>
</table>

<br>

<table class="border" width='660' align='center' border='0' cellpadding="1" cellspacing="3">
	<tr bgcolor='#d9e2ef' class="table_line" align='center'>
		<td colspan="2">ENDEREÇO</td>
		<td>NÚMERO</td>
		<td colspan="2">COMPLEMENTO</td>
	</tr>
	<tr class="table_line" align='center'>
		<td colspan="2"><input type="text" name="endereco" size="30" maxlength="49" value="<? echo $endereco ?>"></td>
		<td><input type="text" name="numero" size="10" maxlength="10" value="<? echo $numero ?>"></td>
		<td colspan="2"><input type="text" name="complemento" size="20" maxlength="20" value="<? echo $complemento ?>"></td>
	</tr>
	<tr bgcolor='#d9e2ef' class="table_line" align='center'>
		<td colspan="2">BAIRRO</td>
		<td>CEP</td>
		<td>CIDADE</td>
		<td>ESTADO</td>
	</tr>
	<tr class="table_line" align='center'>
		<td colspan="2"><input type="text" name="bairro" size="30" maxlength="30" value="<? echo $bairro ?>"></td>
		<td><input type="text" name="cep" size="8" maxlength="8" value="<? echo $cep ?>"></td>
		<td><input type="text" name="cidade" size="10" maxlength="30" value="<? echo $cidade ?>"></td>
		<td><input type="text" name="estado" size="2" maxlength="2" value="<? echo $estado ?>"></td>
	</tr>
</table>
<br>
<table class="border" width='660' align='center' border='0' cellpadding="1" cellspacing="3">
	<tr bgcolor='#d9e2ef' class="table_line" align='center'>
		<td>NOME FANTASIA</td>
		<td>E-MAIL</td>
		<td>CAPITAL/INTERIOR</td>
	</tr>
	<tr class="table_line" align='center'>
		<td>
			<input type="text" name="nome_fantasia" size="30" maxlength="40" value="<? echo $nome_fantasia ?>" >
		</td>
		<td>
			<input type="text" name="email" size="30" maxlength="50" value="<? echo $email ?>">
		</td>
		<td>
			<select name='capital_interior' size='1'>
				<option value='CAPITAL' <? if ($capital_interior == 'CAPITAL') echo ' selected ' ?> ><? if($sistema_lingua) echo "CAPITAL";else echo "Capital";?></option>
				<option value='INTERIOR' <? if ($capital_interior == 'INTERIOR') echo ' selected ' ?> ><? if($sistema_lingua) echo "PROVINCIA";else echo "Interior";?></option>
			</select>
		</td>
	</tr>
</table>

<p>
<!-- ============================ Botoes de Acao ========================= -->
<center>
<INPUT TYPE="hidden" name="btn_acao" value="">
<img src="../imagens/btn_gravar.gif" style="cursor: pointer;" onclick="javascript: if (document.frm_posto.btn_acao.value == '' ) { document.frm_posto.btn_acao.value='gravar' ; document.frm_posto.submit() } else { alert ('Aguarde submissão') }" ALT="Gravar formulário" border='0'>
</center>
</form>