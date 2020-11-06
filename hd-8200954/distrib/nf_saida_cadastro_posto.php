<?
//OBS: ESTE ARQUIVOS UTILIZA AJAX: nf_saida_ret_ajax.php

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

$btn_acao		= $_POST["btn_acao"];

if(strlen($btn_acao)==0)
	$btn_acao = $_POST["btn_acao"];


//SE NAO FOR O POSTO DE TESTE OU O DISTRIB.
if(($login_posto <> 6359) and ($login_posto <> 4311)){
	echo "NÃO É PERMITIDO LANÇAR NOTA FISCAL - login: $login_posto";
	exit;
}

if(strlen($btn_acao)>0){
	$cnpj  = $_POST['cnpj'];
	$xcnpj = preg_replace('/\D/', '', $cnpj); // Não precisa do trim, pq o /\D/ já se encarrega dos espaços e qualquer outro caractere...

	// VERIFICA SE POSTO ESTÁ CADASTRADO
	if (strlen($xcnpj) > 0) {
		$sql = "SELECT posto,nome,cidade,estado
				FROM   tbl_posto
				WHERE  cnpj = '$xcnpj'";
		$res = pg_exec ($con,$sql);

		if (pg_numrows ($res) > 0) {
			$nome   = trim(pg_result($res,0,nome));
			$cidade = trim(pg_result($res,0,cidade));
			$estado = trim(pg_result($res,0,estado));
			echo "0|Fornecedor já cadastrado!\n\n$nome - $cidade / $estado"; // posto já cadastrado
			exit();
		}
	}

	$msg_erro = "";
	if(strlen($xcnpj) == 0) $msg_erro = "Digite o CNPJ/CPF do Posto";
	//if(strlen($xcnpj) == 0) $xcnpj="NULL";
	else  $xcnpj = "$xcnpj";
	
	if(strlen($xcnpj) < 11 OR strlen($xcnpj) > 15 ) $msg_erro = "CNPJ/CPF inválido";

	$ie = trim($_POST["ie"]);
//	if (strlen($ie) == 0) $msg_erro .= "\nDigite a Inscrição Estadual";

	$fone = trim($_POST["fone"]);
	if (strlen($fone) == 0) $msg_erro .= "\nDigite o telefone";

//	if (strlen($fone) < 6)  $msg_erro .= "\nTelefone Incorreto";

	$fax = trim($_POST["fax"]);
	//if (strlen($fax) == 0) $msg_erro .= "\nDigite o FAX.";

	$contato = trim($_POST["contato"]);
//	if (strlen($contato) == 0) $msg_erro .= "\nDigite o Contato";

	$nome = trim($_POST["nome"]);
	if (strlen($nome) == 0) $msg_erro .= "\nDigite o nome";
	else
	if (strlen($nome) < 2) $msg_erro .= "\nNome incorreto";

	$endereco = trim($_POST["endereco"]);
	if (strlen($endereco) == 0) $msg_erro .= "\nDigite o endereço";
	//else
	//if (strlen($endereco) < 4) $msg_erro .= "\nEndereço incorreto";

	$numero = trim($_POST["numero"]);
	if (strlen($numero) == 0) $msg_erro .= "\nDigite o número";

	$complemento = trim($_POST["complemento"]);
	//if (strlen($complemento) == 0) $msg_erro .= "\nDigite o complemento.";

	$cep = trim($_POST["cep"]);
	$cep = preg_replace ('/\D/', '', $cep);

	if (strlen($cep) == 0) $msg_erro .= "\nDigite o CEP.";
	//else
	//if (strlen($cep) != 8) $msg_erro .= "\nCEP incorreto";

	$cidade = trim($_POST["cidade"]);
	if (strlen($cidade) == 0) $msg_erro .= "\nDigite a cidade";

	$estado = trim($_POST["estado"]);
	if (strlen($estado) == 0) $msg_erro .= "\nDigite o estado";
	//else
	//if (strlen($estado) != 2) $msg_erro .= "\nEstado incorreto";

	$nome_fantasia = trim($_POST['nome_fantasia']);
	if (strlen($nome_fantasia) == 0) $msg_erro .= "\nDigite o nome fantasia";

	$email = trim($_POST["email"]);

	//if (strlen($email) == 0) $msg_erro .= "\nDigite o email";

	$contato= trim($_POST["contato"]);
	//$capital_interior = trim($_POST["capital_interior"]);
	//if (strlen($capital_interior) == 0) $msg_erro .= "\nSelecione Capital/Interior";


	if(strlen($msg_erro)==0){

		$sql="INSERT INTO tbl_posto(
						cnpj			,
						ie				,
						nome			,
						nome_fantasia	,
						endereco		,
						numero			,
						complemento		,
						bairro			,
						cidade			,
						estado			,
						cep				,
						fone			,
						fax				,
						email			,
						contato
					)values(
						'$cnpj'			,
						'$ie'			,
						'$nome'			,
						'$nome_fantasia',
						'$endereco'		,
						'$numero'		,
						'$complemento'	,
						'$bairro'		,
						'$cidade'		,
						'$estado'		,
						'$cep'			,
						'$fone'			,
						'$fax'			,
						'$email'		,
						'$contato')";

		$res = pg_exec($con, $sql);
		$msg_erro .= pg_errormessage($con);

		if (strlen($msg_erro) == 0 ) {
			echo "Cadastro realizado com sucesso!";
		}else{
			echo nl2br($msg_erro);
		}
	}else{
		echo nl2br($msg_erro);
	}
}
?>
<html>
<title>Cadastro de Nota Fiscal de Saída</title>
<link type="text/css" rel="stylesheet" href="css/css.css">
<head>
	<script type="text/javascript" src="js/ajax_busca.js"></script>
	<script language='javascript' src='../ajax.js'></script>
<?include "javascript_calendario.php"; ?>
	<script type='text/javascript' src='js/jquery.autocomplete.js'></script>
<link rel="stylesheet" type="text/css" href="js/jquery.autocomplete.css" />
	<script type='text/javascript' src='js/jquery.bgiframe.min.js'></script>
	<script type='text/javascript' src='js/dimensions.js'></script>
	<script type="text/javascript" src="js/thickbox.js"></script>
	<link rel="stylesheet" href="js/thickbox.css" type="text/css" media="screen" />
	<style type="text/css">
	.titulo {
		font-family: Arial;
		font-size: 10pt;
		color: #000000;
		background: #ced7e7;
	}
	.Carregar{
		background-color:#ffffff;
		filter: alpha(opacity=90);
		opacity: .90 ;
		width:350px;
		border-color:#cccccc;
		border:1px solid #bbbbbb;
		display:none;

		position:absolute;
	}
	</style>
</head>

<body>
<center><h1>Cadastro de Cliente</h1></center>
<form name="frm_posto" id="frm_posto" method="post" action="<? echo $PHP_SELF ?>">
<table width='700' style='border-collapse: collapse'  bordercolor='#ccccff' class='table_line' border='1' cellspacing='1' cellpadding='0'>
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
		<td><input type="text" name="cnpj" id="cnpj" size="15" maxlength="20" value="<? echo $cnpj ?>"></td>
		<td><input type="text" name="ie" id="ie" size="20" maxlength="20" value="<? echo $ie ?>"></td>
		<td><input type="text" name="fone" id="fone" size="10" maxlength="20" value="<? echo $fone ?>"></td>
		<td><input type="text" name="fax" id="fax" size="10" maxlength="20" value="<? echo $fax ?>"></td>
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

<table width='700' style='border-collapse: collapse'  bordercolor='#ccccff' class='table_line' border='1' cellspacing='1' cellpadding='0'>
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
		<td>CONTATO</td>
	</tr>
	<tr class="table_line" align='center'>
		<td>
			<input type="text" name="nome_fantasia" size="30" maxlength="40" value="<? echo $nome_fantasia ?>" >
		</td>
		<td>
			<input type="text" name="email" size="30" maxlength="50" value="<? echo $email ?>">
		</td>
		<td>
			<input type="text" name="contato" size="30" maxlength="50" value="<? echo $contato?>">
		</td>
	</tr>
</table>
<center>
<INPUT TYPE='submit' name='btn_acao' id='bt_cad_forn' value='Gravar' >
</center>
</form>

<p>

<? #include "rodape.php"; ?>

</body>
</html>