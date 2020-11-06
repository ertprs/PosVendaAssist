<?
header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
header("Pragma: no-cache"); // HTTP/1.0

include '../dbconfig.php';
include '../includes/dbconnect-inc.php';
//include 'autentica_usuario_empresa.php';

include '../funcoes.php';

$posto		= trim($_POST['posto']);
$btn_acao	= trim($_POST['acao']);

if($btn_acao=="cadastrar"){

	$cnpj  = trim($_POST['cnpj']);
	$xcnpj = str_replace (".","",$cnpj);
	$xcnpj = str_replace ("-","",$xcnpj);
	$xcnpj = str_replace ("/","",$xcnpj);
	$xcnpj = str_replace (" ","",$xcnpj);


	// VERIFICA SE POSTO ESTÁ CADASTRADO
	if (strlen($xcnpj) > 0) {
		$sql = "SELECT pessoa,nome,cidade,estado
				FROM   tbl_pessoa
				WHERE  cnpj = '$xcnpj'
				AND empresa=$login_empresa";
		$res = pg_exec ($con,$sql);

		if (pg_numrows ($res) > 0) {
			$nome   = trim(pg_result($res,0,nome));
			$cidade = trim(pg_result($res,0,cidade));
			$estado = trim(pg_result($res,0,estado));
			$msg_erro = "Fornecedor já cadastrado! ($nome - $cidade / $estado)"; // posto já cadastrado
		}
	}


//	if(strlen($xcnpj) == 0) $msg_erro = "Digite o CNPJ/CPF do Posto";
	if(strlen($xcnpj) == 0){
		$xcnpj="NULL";
	}else{
		$xcnpj = "$xcnpj";
	}
	
//	if(strlen($xcnpj) < 11 OR strlen($xcnpj) > 15 ) $msg_erro = "CNPJ/CPF inválido";

	$ie				= trim($_POST["ie"]);
	$fone			= trim($_POST["fone"]);
	$fax			= trim($_POST["fax"]);
	$contato		= trim($_POST["contato"]);
	$codigo			= trim($_POST["codigo"]);
	$nome			= trim($_POST["nome"]);
	$endereco		= trim($_POST["endereco"]);
	$numero			= trim($_POST["numero"]);
	$complemento	= trim($_POST["complemento"]);
	$cep			= trim($_POST["cep"]);
	$cidade			= trim($_POST["cidade"]);
	$estado			= trim($_POST["estado"]);
	$nome_fantasia	= trim($_POST["nome_fantasia"]);
	$email			= trim($_POST["email"]);
	$capital_interior= trim($_POST["capital_interior"]);

//	if (strlen($ie) == 0)		$msg_erro .= "\nDigite a Inscrição Estadual";
//	if (strlen($fone) == 0)		$msg_erro .= "\nDigite o telefone";
//	if (strlen($fone) < 6)		$msg_erro .= "\nTelefone Incorreto";
//	if (strlen($fax) == 0)		$msg_erro .= "\nDigite o FAX.";
//	if (strlen($contato) == 0)	$msg_erro .= "\nDigite o Contato";
//	if (strlen($codigo) == 0)	$msg_erro .= "\nDigite o Código do Posto";

	if (strlen($nome) == 0)		$msg_erro .= "\nDigite o nome";
	else
	if (strlen($nome) < 2)		$msg_erro .= "\nNome incorreto";

//	if (strlen($endereco) == 0)	$msg_erro .= "\nDigite o endereço";
//	else
//	if (strlen($endereco) < 4)	$msg_erro .= "\nEndereço incorreto";

//	if (strlen($numero) == 0)	$msg_erro .= "\nDigite o número";
//	if (strlen($complemento)==0)$msg_erro .= "\nDigite o complemento.";

	$cep = str_replace ("-","",$cep);
	$cep = str_replace (".","",$cep);
	$cep = str_replace (" ","",$cep);

	//if (strlen($cep) == 0)		$msg_erro .= "\nDigite o CEP.";
	//else
	//if (strlen($cep) != 8)		$msg_erro .= "\nCEP incorreto";

	//if (strlen($cidade) == 0)		$msg_erro .= "\nDigite a cidade";

	//if (strlen($estado) == 0)		$msg_erro .= "\nDigite o estado";
	//else
	//if (strlen($estado) != 2)		$msg_erro .= "\nEstado incorreto";

	if (strlen($nome_fantasia) == 0) $msg_erro .= "\nDigite o nome fantasia";

	//if (strlen($email) == 0)				$msg_erro .= "\nDigite o email";
	//if (strlen($capital_interior) == 0)	$msg_erro .= "\nSelecione Capital/Interior";


	if(strlen($msg_erro)==0){

		$res = pg_exec($con,"BEGIN TRANSACTION");
		$sql="INSERT INTO tbl_pessoa(
						cnpj                  ,
						ie		              ,
						nome		          ,
						nome_fantasia         ,
						endereco              ,
						numero                ,
						complemento           ,
						bairro                ,
						cidade                ,
						estado                ,
						cep                   ,
						fone_residencial      ,
						fone_comercial        ,
						cel                   ,
						fax                   ,
						email                 ,
						empresa
					)values(
						$xcnpj                ,
						'$ie'                 ,
						'$nome'               ,
						'$nome_fantasia'      ,
						'$endereco'           ,
						'$numero'             ,
						'$complemento'        ,
						'$bairro'             ,
						'$cidade'             ,
						'$estado'             ,
						'$cep'                ,
						'$fone'               ,
						'$fone'               ,
						'$fone'               ,
						'$fax'                ,
						'$email'              ,
						$login_empresa)";
		//echo nl2br($sql);
		$res = pg_exec($con, $sql);
		$msg_erro .= pg_errormessage($con);

		if (strlen($msg_erro) == 0 ) {
			$res = pg_exec ($con,"SELECT CURRVAL ('tbl_pessoa_pessoa_seq')");
			$pessoa  = pg_result ($res,0,0);
			$msg_erro .= pg_errormessage($con);

			$sql="INSERT INTO tbl_pessoa_fornecedor(
					empresa,
					pessoa,
					ativo
				)values(
					$login_empresa,
					$pessoa,
					't'
				)";
			$res = pg_exec($con, $sql);
			$msg_erro .= pg_errormessage($con);
		}
		if (strlen($msg_erro) == 0 ) {
			$res = pg_exec ($con,"COMMIT TRANSACTION");
			$msg = "Fornecedor cadastrado com sucesso!";
		}else{
			$res = pg_exec ($con,"ROLLBACK TRANSACTION");
		}
	}

	if (strlen($msg_erro)>0){
		echo "A operação não foi concluída: $msg_erro";
	}else{
		echo $msg;
	}
	exit;
}


?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<title>Contas a Pagar - Cadastro de Fornecedor</title>
	<meta http-equiv="content-Type"  content="text/html; charset=iso-8859-1">
	<meta http-equiv="Expires"       content="0">
	<meta http-equiv="Pragma"        content="no-cache, public">
	<meta http-equiv="Cache-control" content="no-cache, public, must-revalidate, post-check=0, pre-check=0">
	<meta name      ="Author"        content="Telecontrol Networking Ltda">
	<meta name      ="Generator"     content="na mão...">
	<meta name      ="Description"   content="Sistema de gerenciamento para Postos de Assistência Técnica e Fabricantes.">
	<meta name      ="KeyWords"      content="Assistência Técnica, Postos, Manutenção, Internet, Webdesign, Orçamento, Comercial, Jóias, Callcenter">

	<script src="jquery/jquery-latest.pack.js"	type="text/javascript"></script>
	<script src="jquery/jquery.form.js"			type="text/javascript" language="javascript"></script>
	<script src="jquery/jquery.maskedinput.js"	type="text/javascript"></script>
	<script src="jquery/jquery.corner.js"		type="text/javascript" language="javascript"></script>

	<style>
	body{
		font-size:12px;
		font-family:Verdana,Arial;
	}
	.menu_top{
		font-weight:bold;
	}
	.table_line{
			font-size:12px;
	}
	</style>
</head>
<body>

<DIV class='exibe' id='dados' align='center'></DIV>

<form name="frm_cadastro" id="frm_cadastro" method="post" action="<? echo $PHP_SELF ?>">
<table width='660' align='center' border='0' cellpadding="1" cellspacing="3">
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
	</tr>
	<tr class="table_line" align='center'>
		<td><input type="text" name="cnpj"	id="cnpj"	size="15"	maxlength="20"	value="<? echo $cnpj ?>"></td>
		<td><input type="text" name="ie"	id="ie"		size="20"	maxlength="20"	value="<? echo $ie ?>"></td>
		<td><input type="text" name="fone"	id="fone"	size="10"	maxlength="20"	value="<? echo $fone ?>"></td>
		<td><input type="text" name="fax"	id="fax" s	ize="10"	maxlength="20"	value="<? echo $fax ?>"></td>
	</tr>
	<tr bgcolor='#d9e2ef' class="table_line" align='center'>
		<td colspan="2"><? echo "CÓDIGO";?></td>
		<td colspan="3"><? echo "NOME (RAZÃO SOCIAL)";?></td>
	</tr>
	<tr class="table_line" align='center'>
		<td colspan="2"><input type="text" name="codigo" id="codigo" value="<? echo $codigo ?>"></td>		
		<td colspan="3"><input type="text" name="nome" id="nome" value="<? echo $nome ?>"></td>
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
	</tr>
	<tr class="table_line" align='center'>
		<td>
			<input type="text" name="nome_fantasia" size="30" maxlength="40" value="<? echo $nome_fantasia ?>" >
		</td>
		<td>
			<input type="text" name="email" size="30" maxlength="50" value="<? echo $email ?>">
		</td>
	</tr>
</table>
<center>
<INPUT TYPE='button' name='bt_cad_forn' id='bt_cad_forn' value='Gravar' onClick="if (this.value=='Gravando...'){ alert('Aguarde');}else {this.value='Gravando...'; gravar_fonecedor(this.form);}">
<INPUT TYPE='button' name='bt_fecha_forn' id='bt_fecha_forn' value='Fechar' onClick="exibeFornec('0')">
</center>
</form>
</body>
<html>