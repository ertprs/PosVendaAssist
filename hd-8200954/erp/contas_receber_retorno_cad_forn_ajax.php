<?
header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
header("Pragma: no-cache"); // HTTP/1.0

include '../dbconfig.php';
include '../includes/dbconnect-inc.php';
//include 'autentica_usuario.php';

include '../funcoes.php';

$posto = trim($_GET['posto']);

$btn_acao = trim($_GET["acao"]);
if($btn_acao=="cadastrar"){
	$cnpj  = trim($_GET['cnpj']);
	$xcnpj = str_replace (".","",$cnpj);
	$xcnpj = str_replace ("-","",$xcnpj);
	$xcnpj = str_replace ("/","",$xcnpj);
	$xcnpj = str_replace (" ","",$xcnpj);


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

	$msg_erro="";
//	if(strlen($xcnpj) == 0) $msg_erro = "Digite o CNPJ/CPF do Posto";
	if(strlen($xcnpj) == 0) $xcnpj="NULL";
	else  $xcnpj = '$xcnpj';
	
//	if(strlen($xcnpj) < 11 OR strlen($xcnpj) > 15 ) $msg_erro = "CNPJ/CPF inválido";

	$ie = trim($_GET["ie"]);
//	if (strlen($ie) == 0) $msg_erro .= "\nDigite a Inscrição Estadual";

	$fone = trim($_GET["fone"]);
//	if (strlen($fone) == 0) $msg_erro .= "\nDigite o telefone";

//	if (strlen($fone) < 6)  $msg_erro .= "\nTelefone Incorreto";

	$fax = trim($_GET["fax"]);
	//if (strlen($fax) == 0) $msg_erro .= "\nDigite o FAX.";

	$contato = trim($_GET["contato"]);
//	if (strlen($contato) == 0) $msg_erro .= "\nDigite o Contato";

	$codigo = trim($_GET["codigo"]);
//	if (strlen($codigo) == 0) $msg_erro .= "\nDigite o Código do Posto";

	$nome = trim($_GET["nome"]);
	if (strlen($nome) == 0) $msg_erro .= "\nDigite o nome";
	else
	if (strlen($nome) < 2) $msg_erro .= "\nNome incorreto";

	$endereco = trim($_GET["endereco"]);
	//if (strlen($endereco) == 0) $msg_erro .= "\nDigite o endereço";
	//else
	//if (strlen($endereco) < 4) $msg_erro .= "\nEndereço incorreto";

	$numero = trim($_GET["numero"]);
	//if (strlen($numero) == 0) $msg_erro .= "\nDigite o número";

	$complemento = trim($_GET["complemento"]);
	//if (strlen($complemento) == 0) $msg_erro .= "\nDigite o complemento.";

	$cep = trim($_GET["cep"]);
	$cep = str_replace ("-","",$cep);
	$cep = str_replace (".","",$cep);
	$cep = str_replace (" ","",$cep);

	//if (strlen($cep) == 0) $msg_erro .= "\nDigite o CEP.";
	//else
	//if (strlen($cep) != 8) $msg_erro .= "\nCEP incorreto";

	$cidade = trim($_GET["cidade"]);
	//if (strlen($cidade) == 0) $msg_erro .= "\nDigite a cidade";

	$estado = trim($_GET["estado"]);
	//if (strlen($estado) == 0) $msg_erro .= "\nDigite o estado";
	//else
	//if (strlen($estado) != 2) $msg_erro .= "\nEstado incorreto";

	$nome_fantasia = trim($_GET["nome_fantasia"]);
	if (strlen($nome_fantasia) == 0) $msg_erro .= "\nDigite o nome fantasia";

	$email = trim($_GET["email"]);
	//if (strlen($email) == 0) $msg_erro .= "\nDigite o email";

	$capital_interior = trim($_GET["capital_interior"]);
	//if (strlen($capital_interior) == 0) $msg_erro .= "\nSelecione Capital/Interior";


	if(strlen($msg_erro)==0){

		$res = @pg_exec($con,"BEGIN TRANSACTION");
		$sql="INSERT INTO tbl_posto(
						cnpj                  ,
						ie		      ,
						nome		      ,
						nome_fantasia         ,
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
						'$capital_interior'   ,
						'$cep'                ,
						'$fone'               ,
						'$fax'                ,
						'$contato'            ,
						'$email'              )";
		//echo nl2br($sql);
//		echo "ok|$sql";		
//		exit();

		$res = pg_exec($con, $sql);
		$msg_erro .= pg_errormessage($con);

		if (strlen($msg_erro) == 0 ) {
			//$res = @pg_exec ($con,"ROLLBACK TRANSACTION");
			$res = @pg_exec ($con,"COMMIT TRANSACTION");
			echo "ok|Fornecedor cadastrado com sucesso!";
		}else{
			$res = @pg_exec ($con,"ROLLBACK TRANSACTION");
			echo "2|$msg_erro";
		}
	}else{
		echo "1|$msg_erro";
	}

}
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
