<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";

$admin_privilegios="cadastros,call_center";
include 'autentica_admin.php';
include 'funcoes.php';
include_once "../class/tdocs.class.php";


$visual_black = "manutencao-admin";

$layout_menu = "cadastro";
$title = "CADASTRO DE FORNECEDORES";

$tdocs = new TDocs($con, $login_fabrica, 'fornecedor');
$tipo_anexo = array("CPF_CNPJ","SINTEGRA");

if (isset($_POST['ajax_anexo_upload'])) {
    $posicao = $_POST['anexo_posicao'];

    $arquivo = $_FILES["anexo_upload_{$posicao}"];

    $ext = strtolower(preg_replace("/.+\./", "", $arquivo["name"]));
    $extx = $ext;

    if ($ext == 'jpeg') {
        $ext = 'jpg';
    }

    if (strlen($arquivo['tmp_name']) > 0) {
        if (!in_array($ext, array('pdf'))) {
            $retorno = array('error' => utf8_encode('Arquivo em formato inválido, são aceitos os seguintes formatos: PDF'));
        } else {
            $tdocs_id = $tdocs->sendFile($arquivo);
            if($tdocs_id){
                if($ext == 'pdf'){
                    $link = 'imagens/pdf_icone.png';
                } else if(in_array($ext, array('doc', 'docx'))) {
                    $link = 'imagens/docx_icone.png';
                } else {
                    $link = $tdocs->thumb;
                }

                if (!strlen($link)) {
                    $retorno = array('error' => utf8_encode('Erro ao anexar arquivo'));
                } else {
                    $retorno = array(
                        'link'         => $link,
                        'arquivo_nome' => $arquivo['name'],
                        'href'         => $link,
                        'ext'          => $ext,
                        'tdocs_id'     => $tdocs_id,
                        'size'         => $arquivo['size']
                    );
                }
            }else{
                $retorno = array('error' => utf8_encode('Erro ao anexar arquivo'));
            }
        }
    } else {
        $retorno = array('error' => utf8_encode('Erro ao anexar arquivo'));
    }

    $retorno['posicao'] = $posicao;

    exit(json_encode($retorno));
}


if ($_POST["ajax_busca_cidade"] && !empty($_POST["estado"])) {
    $estado = strtoupper($_POST["estado"]);

    if (array_key_exists($estado, $array_estados())) {
        $sql = "SELECT DISTINCT * FROM (
                    SELECT UPPER(fn_retira_especiais(nome)) AS cidade FROM tbl_cidade WHERE UPPER(estado) = UPPER('{$estado}')
                    UNION (
                        SELECT UPPER(fn_retira_especiais(cidade)) AS cidade FROM tbl_ibge WHERE UPPER(estado) = UPPER('{$estado}')
                    )
                ) AS cidade
                ORDER BY cidade ASC";
        $res = pg_query($con, $sql);

        if (pg_num_rows($res) > 0) {
            $array_cidades = array();

            while ($result = pg_fetch_object($res)) {
                $array_cidades[] = $result->cidade;
            }
            $array_cidades = array_unique($array_cidades);
            $retorno = array("cidades" => $array_cidades);
        } else {
            $retorno = array("error" => utf8_encode("nenhuma cidade encontrada para o estado: {$estado}"));
        }
    } else {
        $retorno = array("error" => utf8_encode("estado não encontrado"));
    }
    echo json_encode($retorno);
    exit;
}


if (strlen($_GET["fornecedor"]) > 0) {
	$fornecedor = trim($_GET["fornecedor"]);
}

if (strlen($_POST["fornecedor"]) > 0) {
	$fornecedor = trim($_POST["fornecedor"]);
}

if (strlen($_POST["btn_acao"]) > 0) {
	$btn_acao = strtolower($_POST["btn_acao"]);
}


/*APAGAR*/

if ($btn_acao == "apagar" and strlen($fornecedor) > 0 ) {
	$res = pg_query ($con,"BEGIN TRANSACTION");

	$sql = "DELETE FROM tbl_fornecedor_fabrica
			WHERE  tbl_fornecedor_fabrica.fornecedor   = $fornecedor
			AND    tbl_fornecedor_fabrica.fabrica = $login_fabrica;";
	$res = pg_query ($con,$sql);

	if (strlen (pg_last_error()) > 0) $msg_erro["msg"][] = "Erro ao apagar o fornecedor";

	if (count($msg_erro["msg"]) == 0) {
		$sql = "DELETE FROM tbl_fornecedor
				WHERE  tbl_fornecedor.fornecedor   = $fornecedor";
		$res = pg_query ($con,$sql);
		if (strlen (pg_last_error()) > 0) $msg_erro["msg"][] = "Não é possivél deletar esse fornecedor(ra) pois já existe uma movientação registrado a ele(ela)";
	}

	if (count($msg_erro["msg"]) == 0) {
		$res = pg_query ($con,"COMMIT TRANSACTION");
		header ("Location: $PHP_SELF?msg_success=Excluido com Sucesso!");
		exit;
	}else{
		$res = pg_query ($con,"ROLLBACK TRANSACTION");
	}
}

if ($btn_acao == "gravar") {

	$cnpj			= trim($_POST['cnpj']);
	$xcnpj			= str_replace ("-","",$cnpj);
	$xcnpj			= str_replace (".","",$xcnpj);
	$xcnpj			= str_replace ("/","",$xcnpj);
	$xcnpj			= str_replace (" ","",$xcnpj);


	if($login_fabrica == 30){
		$cor_etiqueta = $_POST['cor_etiqueta'];

		$cor_etiqueta_json = json_encode(array('cor_etiqueta' => $cor_etiqueta ));

		$campo_insert = " campos_adicionais ,";
		$campo_value = " '$cor_etiqueta_json' ,";
		$campo_update = " campos_adicionais = '$cor_etiqueta_json' ,";

	}
	//$xcnpj			= substr ($xcnpj,0,14);

	$nome = trim($_POST['nome']);

	if(strlen($xcnpj) == 0)
		$msg_erro["campos"][] = 'cnpj';
	else
		$cnpj=$xcnpj;

	if(strlen($xcnpj) > 0){
		$validar_cnpj_cpf = pg_query($con,"SELECT fn_valida_cnpj_cpf('$xcnpj')");

		if ($validar_cnpj_cpf === false) {
			$msg_erro["msg"][] = "O CPF / CNPJ informado é inválido";
			$msg_erro["campos"][] = "cnpj";
		}

		$return = checa_cnpj($xcnpj);

		if($return==1){
			$msg_erro["campos"][] = 'cnpj';
		} else if (empty($fornecedor)) {
			$sql = "SELECT tbl_fornecedor.fornecedor,tbl_fornecedor_fabrica.fabrica
					FROM   tbl_fornecedor
					LEFT JOIN tbl_fornecedor_fabrica ON(tbl_fornecedor_fabrica.fornecedor = tbl_fornecedor.fornecedor AND tbl_fornecedor_fabrica.fabrica = {$login_fabrica})
					WHERE  cnpj = '$xcnpj'";
			$res = pg_query ($con,$sql);

			$fabrica_fornecedor = pg_fetch_result($res, 0, 'fabrica');

			if (pg_num_rows ($res) > 0) {
				if (!empty($fabrica_fornecedor)) {
					$msg_erro["msg"][] = "Já existe um fornecedor cadastrado com esse CPF / CNPJ";
					$msg_erro["campos"][] = 'cnpj';
				} else {
					$fornecedor = pg_fetch_result($res, 0, 'fornecedor');
				}
			}
		}
	}

	if (count($msg_erro["msg"]) == 0){
		
		$ie				= trim($_POST['ie']);
		$endereco		= substr(trim($_POST['endereco']),0,50);
		$numero			= trim($_POST['numero']);
		$bairro			= substr(trim($_POST['bairro']),0,30);
		$complemento	= substr(trim($_POST['complemento']),0,20);
		$cep			= trim($_POST['cep']);
		$cidade			= trim($_POST['cidade']);
		$estado			= trim($_POST['estado']);
		$fone1			= trim($_POST['fone1']);
		$fone2			= trim($_POST['fone2']);
		$contato1		= trim($_POST['contato1']);
		$fax			= trim($_POST['fax']);
		$email			= trim($_POST['email']);
		$site			= trim($_POST['site']);
		$possui_email   = trim($_POST['possui_email']);

		if (strlen($nome) >= 5) {
			$aux_simples = stripos($nome, "'");
			$aux_duplas  = stripos($nome, '"');

			if(strlen($aux_simples) > 0 || strlen($aux_duplas) > 0){
				$msg_erro["msg"][] = "Não são permitidas aspas simples ou duplas na Razão Social";
				$msg_erro["campos"][] = "razao";
			} else{
				$xnome = "'".pg_escape_string($nome)."'";
			}
			unset($aux_simples, $aux_duplas);
		}
		else {
			 $msg_erro["msg"][] = "A razão social deve conter pelo menos 5 caracteres";
			 $msg_erro["campos"][] = "razao";
		}

		if (strlen($ie) > 0)
			 $xie = "'".$ie."'";
		else
			if ($login_fabrica == 1)
				$msg_erro["campos"][] = 'ie';
			else
				$xie = 'null';

		if (strlen($endereco) > 0){
			if(strlen($cep) > 0){
				$xendereco = "'".pg_escape_string($endereco)."'";
			}else{
				$aux_simples = stripos($endereco, "'");
				$aux_duplas  = stripos($endereco, '"');

				if(strlen($aux_simples) > 0 || strlen($aux_duplas) > 0){
					$msg_erro["msg"][] = "Não são permitidas aspas simples ou duplas no Endereço";
					$msg_erro["campos"][] = "endereco";
				} else{
					$xendereco = "'".pg_escape_string($endereco)."'";
				}
				unset($aux_simples, $aux_duplas);
			}
		}
		else
			if ($login_fabrica == 1)
				$msg_erro["campos"][] = 'endereco';
			else
				$xendereco = 'null';

		if (strlen($numero) > 0)
			 $xnumero = "'".$numero."'";
		else
			if ($login_fabrica == 1)
				$msg_erro["campos"][] = 'numero';
			else
				$xnumero = 'null';

		if (strlen($bairro) > 0){
			$aux_simples = stripos($bairro, "'");
			$aux_duplas  = stripos($bairro, '"');
			if(strlen($aux_simples) > 0 || strlen($aux_duplas) > 0){
				$msg_erro["msg"][] = "Não são permitidas aspas simples ou duplas no Bairro";
				$msg_erro["campos"][] = "bairro";
			} else{
				$xbairro = "'".pg_escape_string($bairro)."'";
			}
			unset($aux_simples, $aux_duplas);
		}
		else
			if ($login_fabrica == 1)
				$msg_erro["campos"][] = 'bairro';
			else
				$xbairro = 'null';

		if (strlen($complemento) > 0){
			$aux_simples = stripos($complemento, "'");
			$aux_duplas  = stripos($complemento, '"');

			if(strlen($aux_simples) > 0 || strlen($aux_duplas) > 0){
				$msg_erro["msg"][] = "Não são permitidas aspas simples ou duplas no Complemento";
				$msg_erro["campos_extras"][] = "complemento";
			} else{
				$xcomplemento = "'".pg_escape_string($complemento)."'";
			}
			unset($aux_simples, $aux_duplas);
		}else {
			 $xcomplemento = 'null';
		}

		if (strlen($cep) > 0){
			$xcep = str_replace (".","",$cep);
			$xcep = str_replace ("-","",$xcep);
			$xcep = str_replace (" ","",$xcep);
			$xcep = "'".$xcep."'";
		}else{
			$msg_erro["campos"][] = 'cep';
		}

		if (strlen($cidade) > 0)
			 $xcidade = "'".$cidade."'";
		else
			$msg_erro["campos"][] = 'cidade';

		if (strlen($estado) > 0)
			 $xestado = "'".$estado."'";
		else
			$msg_erro["campos"][] = 'estado';

		if (strlen($fone1) > 0)
			 $xfone1 = "'".$fone1."'";
		else
			$msg_erro["campos"][] = 'telefone';

		if (strlen($fone2) > 0)
			 $xfone2 = "'".$fone2."'";
		else
			 $xfone2 = 'null';

		if (strlen($contato1) > 0) {
			 $aux_simples = stripos($contato1, "'");
			$aux_duplas  = stripos($contato1, '"');

			if(strlen($aux_simples) > 0 || strlen($aux_duplas) > 0){
				$msg_erro["msg"][] = "Não são permitidas aspas simples ou duplas no Contato";
				$msg_erro["campos_extras"][] = "contato";
			} else{
				$xcontato1 = "'".$contato1."'";
			}
			unset($aux_simples, $aux_duplas);
		}
		else
			 $xcontato1 = 'null';

	//	if (strlen($contato2) > 0)
	//		 $xcontato2 = "'".$contato2."'";
	//	else
	//		 $xcontato2 = 'null';

		if (strlen($fax) > 0)
			 $xfax = "'".$fax."'";
		else
			 $xfax = 'null';

		$fornecedor_email = $email;
		if ($possui_email == "sim"){
			if (strlen($fornecedor_email) > 0){
				if(filter_input(INPUT_POST,"email",FILTER_VALIDATE_EMAIL,FILTER_FLAG_EMAIL_UNICODE)){
					$fornecedor_email = filter_input(INPUT_POST,"email",FILTER_VALIDATE_EMAIL,FILTER_FLAG_EMAIL_UNICODE);
					$xemail = "'".$fornecedor_email."'";
				}else {
					$msg_erro["msg"][] = "Favor informar um e-mail válido";
					$msg_erro["campos_extras"][] = "email";	
				}
			}else{
				$msg_erro["msg"][] = "Digite o e-mail";
				$msg_erro["campos_extras"][] = "email";				
			}
			$checked_email = "checked";
		}
		else{
			 $xemail = 'null';
		}

		if (strlen($site) > 0)
			 $xsite = "'".$site."'";
		else
			 $xsite = 'null';

		if (count($msg_erro["campos"])) {
			$msg_erro["msg"][] = "Preencha os campos obrigatórios!";
		}else{
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
					$msg_erro["msg"][] = "Cidade não encontrada";
				}
			}
		}

		if (count($msg_erro["msg"]) == 0) {
			$res = pg_query ($con,"BEGIN TRANSACTION");

				#----------------------------- Alteração de Dados ---------------------

			if (strlen ($fornecedor) > 0) {
				$sql = "UPDATE tbl_fornecedor SET
							nome			= '$nome'                   ,
							cnpj			= '$xcnpj'                  ,
							ie				= $xie                      ,
							endereco		= $xendereco                ,
							numero			= $xnumero                  ,
							bairro			= $xbairro                  ,
							cep				= $xcep                     ,
							complemento		= $xcomplemento             ,
							cidade			= $cod_cidade               ,
							fone1			= $xfone1                   ,
							fone2			= $xfone2                   ,
							fax				= $xfax                     ,
							email			= $xemail                   ,
							$campo_update 
							site			= $xsite
							WHERE fornecedor = $fornecedor";
				$res = pg_query ($con,$sql);
$msg_debug .= $sql."<br>";
				if (pg_last_error() > 0) $msg_erro["msg"][] = "Erro ao atualizar o fornecedor";

			}else{

				#-------------- INSERT ---------------
				$sql = "INSERT INTO tbl_fornecedor (
							nome            ,
							cnpj            ,
							ie              ,
							endereco        ,
							numero          ,
							bairro          ,
							cep             ,
							complemento     ,
							cidade          ,
							fone1           ,
							fone2           ,
							fax             ,
							email           ,
							$campo_insert 
							site
						) VALUES (
							$xnome                   ,
							'$xcnpj'                 ,
							$xie                     ,
							$xendereco               ,
							$xnumero                 ,
							$xbairro                 ,
							$xcep                    ,
							$xcomplemento            ,
							$cod_cidade              ,
							$xfone1                  ,
							$xfone2                  ,
							$xfax                    ,
							$xemail                  ,
							$campo_value 
							$xsite
						)";
				$res = pg_query ($con,$sql);



				if (pg_last_error() > 0) {
					$msg_erro["msg"][] = "Erro ao cadastrar o novo fornecedor";
				}

				if (count($msg_erro["msg"]) == 0){
					$sql = "SELECT CURRVAL ('seq_fornecedor')";
					$res = pg_query ($con,$sql);
					$fornecedor = pg_fetch_result ($res,0,0);

$msg_debug .= $sql." - ".$fornecedor."<br>";
				}
			}//FIM ELSE

			// grava fornecedor_fabrica
			if (count($msg_erro["msg"]) == 0){
				$contato1         = trim ($_POST['contato1']);
				$fone            = trim ($_POST['fone']);
				$email           = trim ($_POST['email']);
				$contato1 = substr($contato1, 0, 30); 

				if (strlen($contato1) > 0)
					$xcontato1 = "'".$contato1."'";
				else
					$xcontato1 = 'null';

				if (strlen($fone) > 0)
					$xfone = "'".$fone."'";
				else
					$xfone = 'null';

				if (strlen($email) > 0)
					$xemail = "'".$email."'";
				else
					$xemail = 'null';

				$sql = "SELECT	*
						FROM	tbl_fornecedor_fabrica
						WHERE	fornecedor   = $fornecedor
						AND		fabrica = $login_fabrica ";
$msg_debug .= $sql."<br>";
				$res = pg_query($con,$sql);
				$total_rows = pg_num_rows($res);

				if (pg_num_rows ($res) > 0) {
					$sql = "UPDATE tbl_fornecedor_fabrica SET
								contato    = $xcontato1        ,
								fone      = $xfone            ,
								email     = $xemail
							WHERE tbl_fornecedor_fabrica.fornecedor   = $fornecedor
							AND   tbl_fornecedor_fabrica.fabrica = $login_fabrica ";
				}else{
					$sql = "INSERT INTO tbl_fornecedor_fabrica (
								fornecedor      ,
								fabrica         ,
								contato         ,
								fone            ,
								email
							) VALUES (
								$fornecedor          ,
								$login_fabrica       ,
								$xcontato1            ,
								$xfone               ,
								$xemail
							)";
				}
$msg_debug .= $sql."<br>";
				$res = pg_query ($con,$sql);
				if (strlen (pg_last_error()) > 0) $msg_erro["msg"][] = "Erro ao cadastrar o novo fornecedor";
			}//FIM IF
		}

			if (count($msg_erro["msg"]) == 0) {
				
				if(in_array($login_fabrica, [1])){

		 			foreach ($anexo as $count => $img) {
		                if (empty($img) || empty($_POST['anexo_tdocs_'.$count])) { continue; }

		                $fileInfo = array(
		                    'tdocs_id' => $_POST['anexo_tdocs_'.$count],
		                    'name'     => utf8_encode($img),
		                    'size'     => $_POST['anexo_size_'.$count],
		                    'tipo_anexo' => $_POST['anexo_tipo_'.$count]
		                );

		                $t_tipo_anexo = $_POST['anexo_tipo_'.$count]; 

		                $json_file_info = json_encode($fileInfo);

			            if(strlen(trim($json_file_info)) == 0 ){
			                $msg_erro["msg"][] = "Falha ao gravar anexo do tipo ".$t_tipo_anexo." na posição ". ($count+1) .", por favor verifique o nome do arquivo anexado.";
			            }


				        if (count($msg_erro["msg"]) == 0) {
			                if(!$tdocs->setDocumentReference($fileInfo, $fornecedor, 'anexar', false)){
			                    throw new Exception("Erro ao tentar anexar as imagens selecionadas");
			                    break;
			                }

			                if(strlen(trim($_POST['anexo_tdocs_'.$count]))>0){
			                    $sql_upd = "UPDATE tbl_tdocs set situacao = 'inativo' WHERE tdocs_id = '" . $_POST['anexo_tdocs_id_antiga_'.$count]. "' and fabrica = $login_fabrica and referencia_id = '$fornecedor' ";    
			                    $res_upd = pg_query($con, $sql_upd);
			                }
			            }
		            }
				}

				if (count($msg_erro["msg"]) == 0) {
					$res = pg_query ($con,"COMMIT TRANSACTION");
					header ("Location: $PHP_SELF?msg_success=Gravado com Sucesso!");
					exit;
				}
				
			}else{
				$res = pg_query ($con,"ROLLBACK TRANSACTION");
			}
		}





		if(count($msg_erro["msg"]) > 0){
			$email	= trim($_POST['email']);
			if(strlen($email) > 0){
				$checked_email = "checked";
			}
		}
}

/*INICIO*/

#-------------------- Pesquisa Posto -----------------


if (strlen($_GET['fornecedor']) > 0)  $fornecedor = trim($_GET['fornecedor']);
if (strlen($_POST['fornecedor']) > 0) $fornecedor = trim($_POST['fornecedor']);

if (strlen($fornecedor) > 0 and count($msg_erro["msg"]) == 0 ) {
	$sql = "SELECT  tbl_fornecedor.nome               ,
					tbl_fornecedor.endereco           ,
					tbl_fornecedor.numero             ,
					tbl_fornecedor.bairro             ,
					tbl_fornecedor.cep                ,
					tbl_fornecedor.complemento        ,
					tbl_fornecedor.cidade             ,
					tbl_fornecedor.fone1              ,
					tbl_fornecedor.fone2              ,
					tbl_fornecedor.cnpj               ,
					tbl_fornecedor.ie                 ,
					tbl_fornecedor.fax                ,
					tbl_fornecedor.email              ,
					tbl_fornecedor.site               ,
					tbl_fornecedor_fabrica.fornecedor ,
					tbl_fornecedor_fabrica.contato    ,
					tbl_fornecedor_fabrica.fone       ,
					tbl_fornecedor_fabrica.email      ,
					tbl_fornecedor.campos_adicionais  , 
					tbl_cidade.nome AS cidade_nome    ,
					tbl_cidade.estado AS cidade_estado
			FROM	tbl_fornecedor
			LEFT JOIN tbl_fornecedor_fabrica ON tbl_fornecedor_fabrica.fornecedor = tbl_fornecedor.fornecedor
			LEFT JOIN tbl_cidade using (cidade)
			WHERE   tbl_fornecedor_fabrica.fabrica = $login_fabrica
			AND     tbl_fornecedor_fabrica.fornecedor   = $fornecedor ";
	$res = pg_query ($con,$sql);

	if (@pg_num_rows ($res) > 0) {
		$fornecedor       = trim(pg_fetch_result($res,0,fornecedor));
		$nome             = trim(pg_fetch_result($res,0,nome));
		$cnpj             = trim(pg_fetch_result($res,0,cnpj));
		$ie               = trim(pg_fetch_result($res,0,ie));

		if($login_fabrica == 30){
			$campos_adicionais = json_decode(pg_fetch_result($res, 0, 'campos_adicionais'),true);
			$cor_etiqueta = $campos_adicionais['cor_etiqueta'];
		}


		if (strlen($cnpj) == 14) {
			$cnpj = substr($cnpj,0,2) .".". substr($cnpj,2,3) .".". substr($cnpj,5,3) ."/". substr($cnpj,8,4) ."-". substr($cnpj,12,2);
		}
		if (strlen($cnpj) == 11) {
			$cnpj = substr($cnpj,0,3) .".". substr($cnpj,3,3) .".". substr($cnpj,6,3) ."-". substr($cnpj,9,2);
		}

		$endereco    = trim(pg_fetch_result($res,0,endereco));
		$endereco    = str_replace("\"","",$endereco);
		$numero      = trim(pg_fetch_result($res,0,numero));
		$bairro      = trim(pg_fetch_result($res,0,bairro));
		$cep         = trim(pg_fetch_result($res,0,cep));
		$complemento = trim(pg_fetch_result($res,0,complemento));
		$cidade      = trim(pg_fetch_result($res,0,cidade_nome));
		$estado      = trim(pg_fetch_result($res,0,cidade_estado));
		$fone1       = trim(pg_fetch_result($res,0,fone1));
		$fone2       = trim(pg_fetch_result($res,0,fone2));
		$fax         = trim(pg_fetch_result($res,0,fax));
		$email       = trim(pg_fetch_result($res,0,email));
		$site        = trim(pg_fetch_result($res,0,site));
		$contato1    = trim(pg_fetch_result($res,0,contato));
		$fone        = trim(pg_fetch_result($res,0,fone));

		if (strlen($email) > 0){
			$checked_email = "checked";
		}else {
			$checked_email = "";
		}
	}
}
/*FIM*/


$msg_success = $_GET['msg_success'];

include 'cabecalho_new.php';

$plugins = array(
    "autocomplete",
    "datepicker",
    "shadowbox",
    //"mask",
   	"maskedinput",
    "dataTable",
   	"ajaxform",
    "alphanumeric",
    "fancyzoom"
);

include("plugin_loader.php");
?>


<?php # include '../js/js_css.php'; /* Todas libs js, jquery e css usadas no Assist - HD 969678 */ ?>

<link rel="stylesheet" type="text/css" href="js/jquery.autocomplete.css" />

<script type="text/javascript">
	function fnc_pesquisa_codigo_fornecedor (codigo, nome) {
	    var url = "";
	    if (codigo != "" && nome == "") {
	        url = "pesquisa_fornecedor.php?codigo=" + codigo;
	        janela = window.open(url,"janela","toolbar=no,location=no,status=no,scrollbars=yes,directories=no,width=600,height=400,top=18,left=0");
	        janela.focus();
	    }
	}

	function fnc_pesquisa_nome_fornecedor (codigo, nome) {
	    var url = "";
	    if (codigo == "" && nome != "") {
	        url = "pesquisa_fornecedor.php?nome=" + nome;
	        janela = window.open(url,"janela","toolbar=no,location=no,status=no,scrollbars=yes,directories=no,width=600,height=400,top=18,left=0");
	        janela.focus();
	    }
	}

	// function fnc_pesquisa_fornecedor(campo, campo2, tipo) {
	// 	if (tipo == "nome" ) {
	// 		var xcampo = campo;
	// 	}

	// 	if (tipo == "cnpj" ) {
	// 		var xcampo = campo2;
	// 	}

	// 	if (xcampo.value != "") {
	// 		var url = "";
	// 		url = "fornecedor_pesquisa_new.php?forma=reload&campo=" + xcampo.value + "&tipo=" + tipo ;
	// 		janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=500, height=400, top=0, left=0");
	// 		janela.retorno = "<? echo $PHP_SELF ?>";
	// 		janela.nome	= campo;
	// 		janela.cnpj	= campo2;
	// 		janela.focus();
	// 	}

	// 	else{
	// 		alert("Preencha toda ou parte da informação para realizar a pesquisa!");
	// 	}
	// }

	function retorna_fornecedor(retorno) {
		var fornecedor_id = retorno.fornecedor;
		$("#fornecedor_id").val(fornecedor_id);
		$("#cnpj").val(retorno.cnpj);
		$("#nome").val(retorno.nome);

		window.location.href = "fornecedor_cadastro.php?fornecedor="+fornecedor_id;


		// $("#ie").val(retorno.ie);
		// $("#cep").val(retorno.cep);
		// $("#estado option").remove();
		// $("#estado").append("<option value='" + retorno.uf + "'>"+ retorno.uf + " - " + retorno.estado +"</option>");
		// $("#cidade option").remove();
		// $("#cidade").append("<option value='" + retorno.cidade + "'>"+ retorno.cidade +"</option>");
		// $("#bairro").val(retorno.bairro);
		// $("#endereco").val(retorno.endereco);$("#endereco").val(retorno.endereco);
		// $("#numero").val(retorno.numero);
		// $("#complemento").val(retorno.complemento);
		// $("#fone1").val(retorno.fone1);
		// $("#fone2").val(retorno.fone2);
		// $("#fax").val(retorno.fax);
		// $("#email").val(retorno.email)
		// $("#site").val(retorno.site)
		// $("#contato1").val(retorno.contato)
		// $("#cnpj").focus();
		// $("#nome").focus();
	}

	function busca_cidade(estado, cidade) {
	    $("#cidade").find("option").first().nextAll().remove();

	    if (estado.length > 0) {
	        $.ajax({
	            url: "<?=$PHP_SELF?>",
	            type: "POST",
	            dataType:"json",
	            data: {
	                ajax_busca_cidade: true,
	                estado: estado
	            }
	        })
	        .done(function(data) {
	            if (data.error) {
	                alert(data.error);
	            } else {
	                $.each(data.cidades, function(key, value) {
	                    var option = $("<option></option>", { value: value, text: value});
	                    $("#cidade").append(option);
	                });
	            }
	        });
	    }

	    if(typeof cidade != "undefined" && cidade.length > 0){

	        $('#cidade[value='+cidade+']').attr('selected','selected');

	    }
	}
	function BloqueiaNumeros(e)	{
	    var tecla=new Number();
	    if(window.event) {
	        tecla = e.keyCode;
	    }
	    else if(e.which) {
	        tecla = e.which;
	    }
	    else {
	        return true;
	    }
	    if((tecla >= "48") && (tecla <= "57")){
	        return false;
	    }
	}

	$(function(){
		$("button[name=anexar]").click(function() {
            var posicao = $(this).attr("rel");

            $("input[name=anexo_upload_"+posicao+"]").click();
        });

        $("input[name^=anexo_upload_]").change(function() {
            var i = $(this).parent("form").find("input[name=anexo_posicao]").val();

            $("#div_anexo_"+i).find("button").hide();
            $("#div_anexo_"+i).find("img.anexo_thumb").hide();
            $("#div_anexo_"+i).find("img.anexo_loading").show();

            $(this).parent("form").submit();
        });

		$("form[name=form_anexo]").ajaxForm({			
            complete: function(data) {
                data = $.parseJSON(data.responseText);

                if (data.error) {
                    alert(data.error);
                } else {
                    var imagem = $("#div_anexo_"+data.posicao).find("img.anexo_thumb").clone();
                    $(imagem).attr({ src: data.link });
                    $('input[name=anexo_link_'+data.posicao).val(data.link);

                    $("#div_anexo_"+data.posicao).find("img.anexo_thumb").remove();

                    var link = $("<a></a>", {
                        href: data.href,
                        target: "_blank"
                    });

                    $(link).html(imagem);

                    $("#div_anexo_"+data.posicao).prepend(link);

                    if ($.inArray(data.ext, ["pdf"]) == -1) {
                        setupZoom();
                    }

                    $("#div_anexo_"+data.posicao).find("input[rel=anexo]").val(data.arquivo_nome);
                }

                $("#div_anexo_"+data.posicao).find("img.anexo_loading").hide();
                $("#div_anexo_"+data.posicao).find("button").show();
                $("#div_anexo_"+data.posicao).find("img.anexo_thumb").show();
                $("#div_anexo_"+data.posicao).find("input[name=anexo_tdocs_"+data.posicao+"]").val(data.tdocs_id);
                $("#div_anexo_"+data.posicao).find("input[name=anexo_size_"+data.posicao+"]").val(data.size);
            }
        });

		/**
		 * Inicia o shadowbox, obrigatório para a lupa funcionar
		 */
		Shadowbox.init();

		$("#fone1").mask("(99) 9999-9999");
		$("#fone2").mask("(99) 9999-9999");
		$("#fax").mask("(99) 9999-9999");

		$("#btn_etiquetas").click(function() {
			Shadowbox.open({
                content: "cadastro_cor_etiquetas.php",
                player: "iframe",
                title:   "Cadastro de Etiquetas",
                width: 900,
                height: 750
            });
		});

		<?php
		if(strlen(trim($_POST['cpf'])) > 0){
			if(strlen(trim($_POST['cpf'])) > 14){	?>

				$("#cnpj").mask("99.999.999/9999-99");
				$("label[for=cnpj]").html("CNPJ");
				$("#lupa_fornecedor").attr('parametro',"cnpj");
			<?php
			}else{	?>

				$("#cnpj").mask("999.999.999-99");
				$("label[for=cnpj]").html("CPF");
				$("#lupa_fornecedor").attr('parametro',"cpf");
			<?php
			} ?>
		<?php
		} ?>

		$("#cnpj").focusin(function(){
			$(this).unmask();
		});

		$("#cnpj").blur(function(){
			verifica_campo_cpf_cnpj();
		});

		/**
		 * Evento que chama a função de lupa para a lupa clicada
		 */
		$("span[rel=lupa]").click(function() {
			$.lupa($(this));
		});

		$("#cep").mask("99.999-999");

		<?php
		if (!in_array($login_fabrica, [1])) { ?>
			$("#ie").numeric();
		<?php
		} ?>


		$('.addressZip').blur(function(event) { //HD-3182432
            $("input[name='numero']").focus();
        });


		$("#estado").change(function () {
            busca_cidade($(this).val());
		});

		var extraParamEstado = {
			estado: function () {
				return $("#estado").val()
			}
		};

		mostrar_email();

		$("input[name=possui_email").click(function(){
            mostrar_email();
        });

        $("#div_anexo_1").hide(); 

        verifica_campo_cpf_cnpj();
	});

	
	function setAnexo(tipo){
		if(tipo == 'CNPJ'){
			console.log('cnpj');
			$(".class_anexo_0").text("CNPJ");
			$("#div_anexo_1").show(); 
			$("#div_anexo_1 input").each(function() {
		      $(this).prop('disabled', false); 
		  });

		}else if(tipo == "CPF"){
			console.log("cpf");
			$(".class_anexo_0").text("CPF");

			$("#div_anexo_1").hide(); 
			$("#div_anexo_1 input").each(function() {
		      $(this).val("");
		      $(this).prop('disabled', true); 
		  });
		}
	}

	function verifica_campo_cpf_cnpj(){
		var tamanho = $("#cnpj").val().replace(/\D/g, '');
		if(tamanho.length > 11){
			$("#cnpj").mask("99.999.999/9999-99");
			$("label[for=cnpj]").html("CNPJ");
			$("#lupa_fornecedor").attr('parametro',"cnpj");
			setAnexo('CNPJ');
		}else{
			$("#cnpj").mask("999.999.999-99");
			$("label[for=cnpj]").html("CPF");
			$("#lupa_fornecedor").attr('parametro',"cpf");
			setAnexo('CPF');
		}
	}

	function mostrar_email(){
		var valor = $("input[name=possui_email]:checked").val();

        if (valor == "sim") {
            $("#area_email").css("display", "block");
        } else if (valor == "nao") {
            $("#area_email").css("display", "none");
            $("#email").val("");
        }
	}

	function somenteNumeros(num){
		var er = /[^0-9.]/;
		er.lastIndex = 0;
		var campo = num;
		if(er.test(campo.value)) {
			campo.value = "";
		}
	}

</script>

<?php
if (count($msg_erro["msg"]) > 0) {
?>
    <div class="alert alert-error">
		<h4><?=implode("<br />", $msg_erro["msg"])?></h4>
    </div>
<?php }

if (strlen($msg_success) > 0) {
?>
    <div class="alert alert-success">
		<h4><?=$msg_success;?></h4>
    </div>
<?php }


if (!in_array($login_fabrica, array(1))) {?>
<div class="row-fluid">
	<div class="span12">
		<p class="text-info tac">
			Para incluir um novo fornecedor, preencha somente seu CNPJ e clique em gravar. <br />
			Faremos uma pesquisa para verificar se o fornecedor já está cadastrado em nosso banco de dados.
		</p>
	</div>
</div>
<?php } ?>

<div class="row">
	<b class="obrigatorio pull-right">  * Campos obrigatórios </b>
</div>

<form name="frm_fornecedor" method="post" action="<? echo $PHP_SELF; ?>" align='center' class='form-search form-inline tc_formulario'>
	<input type="hidden" name="fornecedor" id="fornecedor_id" value="<? echo $fornecedor; ?>">
	<div class='titulo_tabela '>Cadastro de Fornecedores</div>
	<br/>

	<!-- cnpj / razao social -->
	<div class='row-fluid'>
		<div class='span2'></div>
		<div class='span4'>
			<div class='control-group <?=(in_array('cnpj', $msg_erro["campos"])) ? 'error' : '';?>'>
				<label class='control-label' for='cnpj'>CPF / CNPJ</label>
				<div class='controls controls-row'>
					<div class='span7 input-append'>
						<h5 class='asteristico'>*</h5>
						<input type="text" onkeyup="somenteNumeros(this);" id="cnpj" name="cnpj" class='span12' maxlength="20" value="<? echo $cnpj ?>" >
						<!-- <span class='add-on' rel="lupa" ><i class='icon-search' style="cursor: pointer" onclick="javascript: fnc_pesquisa_fornecedor (document.frm_fornecedor.nome,document.frm_fornecedor.cnpj,'cnpj')"></i></span> -->
						<span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
						<input type="hidden" name="lupa_config" id="lupa_fornecedor" tipo="fornecedor" parametro="cnpj" />
					</div>
				</div>
			</div>
		</div>
		<div class='span4'>
			<div class='control-group <?=(in_array('razao', $msg_erro["campos"])) ? 'error' : '';?>'>
				<label class='control-label' for='nome'>Nome / Razão Social</label>
				<div class='controls controls-row'>
					<div class='span12 input-append'>
						<input type="text" id="nome" name="nome" class='span12' value="<? echo $nome ?>" >
						<!-- <span class='add-on' rel="lupa" ><i class='icon-search' style="cursor: pointer" onclick="javascript: fnc_pesquisa_fornecedor (document.frm_fornecedor.nome,document.frm_fornecedor.cnpj,'nome')"></i></span> -->
						<span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
						<input type="hidden" name="lupa_config" tipo="fornecedor" parametro="nome" />
					</div>
				</div>
			</div>
		</div>
		<div class='span2'></div>
	</div>
	<!-- #### -->

	<!-- inscrição / endereço -->
	<div class='row-fluid'>
		<div class='span2'></div>
		<div class='span4'>
			<div class='control-group <?=(in_array('ie', $msg_erro["campos"])) ? 'error' : '';?>'>
				<label class='control-label' for='ie'>Inscrição Estadual</label>
				<div class='controls controls-row'>
					<div class='span7 input-append'>
						<?php if ($login_fabrica == 1) { ?>
							<h5 class="asteristico">*</h5>
						<?php } ?>
						<input type="text" id="ie" name="ie" class='span12' maxlength="20" value="<? echo $ie ?>" >
						</div>
				</div>
			</div>
		</div>
		<div class='span4'>
			<div class='control-group <?=(in_array('cep', $msg_erro["campos"])) ? 'error' : '';?>'>
				<label class='control-label' for='cep'>Cep</label>
				<div class='controls controls-row'>
					<div class='span12 input-append'>
                        <h5 class='asteristico'>*</h5>
						<input class='addressZip' type="text" id="cep" name="cep" class='span12' value="<? echo $cep ?>" onblur="numero.value='';this.className='frm addressZip'; displayText('&nbsp;');" onfocus="this.className='frm-on addressZip';" >
					</div>
				</div>
			</div>
		</div>
		<div class='span2'></div>
	</div>
	<!-- #### -->

	<!-- estado / cidade -->
	<div class='row-fluid'>
		<div class='span2'></div>
		<div class='span4'>
			<div class='control-group <?=(in_array('estado', $msg_erro["campos"])) ? 'error' : '';?>'>
				<label class='control-label' for='estado'>Estado</label>
				<div class='controls controls-row'>
					<div class='span7'>
                        <h5 class='asteristico'>*</h5>
						<select id="estado" class='addressState' name="estado" style="width:155px;">
							<option value=""   <? if (strlen($estado) == 0) echo " selected "; ?>>Selecione</option>
							<option value="AC" <? if ($estado == "AC") echo " selected "; ?>>AC - Acre</option>
							<option value="AL" <? if ($estado == "AL") echo " selected "; ?>>AL - Alagoas</option>
							<option value="AM" <? if ($estado == "AM") echo " selected "; ?>>AM - Amazonas</option>
							<option value="AP" <? if ($estado == "AP") echo " selected "; ?>>AP - Amapá</option>
							<option value="BA" <? if ($estado == "BA") echo " selected "; ?>>BA - Bahia</option>
							<option value="CE" <? if ($estado == "CE") echo " selected "; ?>>CE - Ceará</option>
							<option value="DF" <? if ($estado == "DF") echo " selected "; ?>>DF - Distrito Federal</option>
							<option value="ES" <? if ($estado == "ES") echo " selected "; ?>>ES - Espírito Santo</option>
							<option value="GO" <? if ($estado == "GO") echo " selected "; ?>>GO - Goiás</option>
							<option value="MA" <? if ($estado == "MA") echo " selected "; ?>>MA - Maranhão</option>
							<option value="MG" <? if ($estado == "MG") echo " selected "; ?>>MG - Minas Gerais</option>
							<option value="MS" <? if ($estado == "MS") echo " selected "; ?>>MS - Mato Grosso do Sul</option>
							<option value="MT" <? if ($estado == "MT") echo " selected "; ?>>MT - Mato Grosso</option>
							<option value="PA" <? if ($estado == "PA") echo " selected "; ?>>PA - Pará</option>
							<option value="PB" <? if ($estado == "PB") echo " selected "; ?>>PB - Paraíba</option>
							<option value="PE" <? if ($estado == "PE") echo " selected "; ?>>PE - Pernambuco</option>
							<option value="PI" <? if ($estado == "PI") echo " selected "; ?>>PI - Piauí</option>
							<option value="PR" <? if ($estado == "PR") echo " selected "; ?>>PR - Paraná</option>
							<option value="RJ" <? if ($estado == "RJ") echo " selected "; ?>>RJ - Rio de Janeiro</option>
							<option value="RN" <? if ($estado == "RN") echo " selected "; ?>>RN - Rio Grande do Norte</option>
							<option value="RO" <? if ($estado == "RO") echo " selected "; ?>>RO - Rondônia</option>
							<option value="RR" <? if ($estado == "RR") echo " selected "; ?>>RR - Roraima</option>
							<option value="RS" <? if ($estado == "RS") echo " selected "; ?>>RS - Rio Grande do Sul</option>
							<option value="SC" <? if ($estado == "SC") echo " selected "; ?>>SC - Santa Catarina</option>
							<option value="SE" <? if ($estado == "SE") echo " selected "; ?>>SE - Sergipe</option>
							<option value="SP" <? if ($estado == "SP") echo " selected "; ?>>SP - São Paulo</option>
							<option value="TO" <? if ($estado == "TO") echo " selected "; ?>>TO - Tocantins</option>
						</select>
					</div>
				</div>
			</div>
		</div>
		<div class='span4'>
			<div class='control-group <?=(in_array('cidade', $msg_erro["campos"])) ? 'error' : '';?>'>
				<label class='control-label' for='cidade'>Cidade</label>
				<div class='controls controls-row'>
					<div class='span12 input-append'>
						<h5 class='asteristico'>*</h5>
						<select id="cidade" name="cidade" class="frm addressCity" style="width:155px">
						<option value="" >Selecione</option>
						<?php
							if (strlen($estado) > 0) {
                                $sql = "SELECT DISTINCT * FROM (
                                        SELECT UPPER(fn_retira_especiais(nome)) AS cidade FROM tbl_cidade WHERE UPPER(estado) = UPPER('".$estado."')
                                            UNION (
                                                SELECT UPPER(fn_retira_especiais(cidade)) AS cidade FROM tbl_ibge WHERE UPPER(estado) = UPPER('".$estado."')
                                            )
                                        ) AS cidade
                                        ORDER BY cidade ASC";
                                $res = pg_query($con, $sql);

                                if (pg_num_rows($res) > 0) {
                                    $cidade = retira_acentos($cidade);
                                    $cidade = strtoupper($cidade);

							        while ($result = pg_fetch_object($res)) {
                                    	$selected  = (trim($result->cidade) == trim($cidade)) ? "SELECTED" : "";
                                    	echo $selected.'===X';
                                        echo "<option value='{$result->cidade}' {$selected} >{$result->cidade} </option>";
                                    }
                                }
                            }
						?>
						</select>
						<!-- <select id="cidade" class='addressCity' name="cidade" style="width:155px;">
						</select> -->
					</div>
				</div>
			</div>
		</div>
		<div class='span2'></div>
	</div>
	<!-- #### -->

	<!-- inscrição / endereço -->
	<div class='row-fluid'>
		<div class='span2'></div>
		<div class='span4'>
			<div class='control-group <?=(in_array('bairro', $msg_erro["campos"])) ? 'error' : '';?>'>
				<label class='control-label' for='bairro'>Bairro</label>
				<div class='controls controls-row'>
					<div class='span7 input-append'>
						<?php if ($login_fabrica == 1) { ?>
							<h5 class="asteristico">*</h5>
						<?php } ?>
						<input type="text" id="bairro" name="bairro" class='span12 addressDistrict' maxlength="20" value="<? echo $bairro ?>" >
						</div>
				</div>
			</div>
		</div>
		<div class='span4'>
			<div class='control-group <?=(in_array('endereco', $msg_erro["campos"])) ? 'error' : '';?>'>
				<label class='control-label' for='endereco'>Endereço</label>
				<div class='controls controls-row'>
					<div class='span12 input-append'>
						<?php if ($login_fabrica == 1) { ?>
							<h5 class="asteristico">*</h5>
						<?php } ?>
						<input type="text" id="endereco" name="endereco" class='span12 address' value="<? echo $endereco ?>" >
					</div>
				</div>
			</div>
		</div>

		<div class='span2'></div>
	</div>
	<!-- #### -->

	<!-- numero / complemento -->
	<div class='row-fluid'>
		<div class='span2'></div>
		<div class='span4'>
			<div class='control-group <?=(in_array('numero', $msg_erro["campos"])) ? 'error' : '';?>'>
				<label class='control-label' for='numero'>Numero</label>
				<div class='controls controls-row'>
					<div class='span7 input-append'>
						<?php if ($login_fabrica == 1) { ?>
							<h5 class="asteristico">*</h5>
						<?php } ?>
						<input type="text" id="numero" name="numero" class='span12' maxlength="20" value="<? echo $numero ?>" >
						</div>
				</div>
			</div>
		</div>
		<div class='span4'>
			<div class='control-group <?=(in_array("complemento", $msg_erro["campos_extras"])) ? "error" : ""?>'>
				<label class='control-label' for='complemento'>Complemento</label>
				<div class='controls controls-row'>
					<div class='span12 input-append'>
						<input maxlength="20" type="text" id="complemento" name="complemento" class='span12' value="<? echo $complemento ?>" >
					</div>
				</div>
			</div>
		</div>
		<div class='span2'></div>
	</div>
	<!-- #### -->

	<!-- telefone / contato -->
	<div class='row-fluid'>
		<div class='span2'></div>
		<div class='span4'>
			<div class='control-group <?=(in_array('telefone', $msg_erro["campos"])) ? 'error' : '';?>'>
				<label class='control-label' for='fone1'>Telefone</label>
				<div class='controls controls-row'>
					<div class='span7 input-append'>
						<h5 class='asteristico'>*</h5>
						<input type="text" id="fone1" name="fone1" class='span12' maxlength="20" value="<? echo $fone1 ?>" >
					</div>
				</div>
			</div>
		</div>
		<div class='span4'>
			<div class='control-group <?=(in_array("contato", $msg_erro["campos_extras"])) ? "error" : ""?>'>
				<label class='control-label' for='contato1'>Contato</label>
				<div class='controls controls-row'>
					<div class='span12 input-append'>
						<input type="text" id="contato1" name="contato1" class='span12' maxlength="30" value="<? echo $contato1 ?>" >
					</div>
				</div>
			</div>
		</div>
		<div class='span2'></div>
	</div>
	<!-- #### -->

	<!-- telefone alternativo / fax -->
	<div class='row-fluid'>
		<div class='span2'></div>
		<div class='span4'>
			<div class='control-group'>
				<label class='control-label' for='fone2'>Telefone Alternativo</label>
				<div class='controls controls-row'>
					<div class='span7 input-append'>
						<input type="text" id="fone2" name="fone2" class='span12' maxlength="20" value="<? echo $fone2 ?>" >
					</div>
				</div>
			</div>
		</div>
		<div class='span4'>
			<div class='control-group'>
				<label class='control-label' for='fax'>Fax</label>
				<div class='controls controls-row'>
					<div class='span12 input-append'>
						<input type="text" id="fax" name="fax" class='span12' value="<? echo $fax ?>" >
					</div>
				</div>
			</div>
		</div>
		<div class='span2'></div>
	</div>
	<!-- #### -->

	<!-- email / site -->
	<div class='row-fluid'>
		<div class='span2'></div>
		<div class='span4'>
			<div class='control-group'>
				<label class='control-label' for='possui_email'>Fornecedor Possui E-mail?</label>
				<div class='controls controls-row'>
					<div class='span7 input-append'>
						<label class="radio">
					        <input type="radio" name="possui_email" value="sim" <?php if(strlen($checked_email) > 0) echo "checked"; ?>>
					        Sim &nbsp;
					    </label>
                    	<label class="radio">
					        <input type="radio" name="possui_email" value="nao" <?php if(!strlen($checked_email) > 0) echo "checked" ?>>
					        Não
					    </label>
					</div>
				</div>
			</div>
		</div>
		<div class='span4'>
			<div class='control-group'>
				<label class='control-label' for='site'>Site</label>
				<div class='controls controls-row'>
					<div class='span12 input-append'>
						<input type="text" id="site" name="site" class='span12' maxlength="50" value="<? echo $site ?>" >
					</div>
				</div>
			</div>
		</div>
		<div class='span2'></div>
	</div>
	<div class='row-fluid' id="area_email">
		<div class='span2'></div>
		<div class='span4'>
			<div class='control-group <?=(in_array('email', $msg_erro["campos_extras"])) ? 'error' : '';?>'>
				<label class='control-label' for='email'>E-mail</label>
				<div class='controls controls-row'>
					<div class='span7 input-append'>
						<input type="email" id="email" name="email" class='span12' maxlength="50" value="<? echo $email ?>" >
					</div>
				</div>
			</div>
		</div>
		<div class='span2'></div>
	</div>
	<?php if($login_fabrica == 30){?>
	<div class='row-fluid'>
		<div class='span2'></div>
		<div class='span4'>
			<div class='control-group'>
				<label class='control-label' for='cor_etiqueta'>Cor da Etiqueta</label>
				<div class='controls controls-row'>
					<div class='span7'>
						<select name="cor_etiqueta">
							<option value=''>Selecione a Cor da Etiqueta</option>
<?php
							 $sql = "SELECT  cor,
				                            nome_cor
				                    FROM    tbl_cor
				                    WHERE   fabrica = $login_fabrica 
				                    AND ativo
				                    ORDER BY nome_cor";
				            $res = pg_query ($con,$sql);

				            for ($i = 0 ; $i < pg_num_rows ($res) ; $i++) {
				            	$cor_id   = pg_fetch_result($res, $i, 'cor');
				            	$nome_cor = pg_fetch_result($res, $i, 'nome_cor');
				            	$nome_cor = (mb_detect_encoding($nome_cor, "UTF-8") ? utf8_decode($nome_cor) : $nome_cor);
?>

								<option value='<?=$cor_id?>' <?php if($cor_id == $cor_etiqueta){ echo ' selected '; } ?>><?=$nome_cor?></option>
<?php 
							} 
?>
						</select>
					</div>
				</div>
			</div>
		</div>
	</div>
	<?php } ?>
	<!-- #### -->

<?php if(in_array($login_fabrica, [1])) { ?>
	<br />
    <div class="titulo_tabela">Anexo(s)</div>
    <br />
    <?php
    $anexos      = array();
    $qtde_anexos = 2;

    if (isset($_REQUEST['fornecedor']) && !empty($_REQUEST['fornecedor'])) {
        $ret = $tdocs->getDocumentsByRef($_REQUEST['fornecedor']);

        if (count($ret->attachListInfo)) {

            foreach ($ret->attachListInfo as $array_file) {

                $key_tipo_anexo = array_search($array_file['extra']['tipo_anexo'] ,$tipo_anexo);

                $anexos[$key_tipo_anexo] = array(
                    'anexo_imagem' => $array_file['link'],
                    'size'         => $array_file['filesize'],
                    'anexo_aux'    => $array_file['filename'],
                    'tipo_anexo'   => $array_file['extra']['tipo_anexo'],
                    'tdocs_id'     => $array_file['tdocs_id']
                );
            }
        }
    }

    for ($i = 0; $i < $qtde_anexos; $i++) {
        $anexo_tdocs = $_POST["anexo_tdocs_$i"];
        if (!empty($anexo_tdocs)) {
            $anexos[] = array(
                'anexo_imagem' => $_POST["anexo_link_$i"],
                'size'         => $_POST["anexo_size_$i"],
                'anexo_aux'    => $anexo[$i],
                'anexo_tdocs'  => $anexo_tdocs,
                'tdocs_id'     => $array_file['tdocs_id']
            );
        }
    }

    $xtipo_solicitacao = $_POST['tipo_solicitacao'];
    $xanex = $_POST['anexo'];
    $class_button = "btn-primary";
    $vazio = [];

    for ($i = 0; $i < $qtde_anexos; $i++) {
        if ($class_button == 'btn-danger' && $i != 0) {
            $class_button = 'btn-primary';
        }

        $anexo_imagem = (isset($anexos[$i]['anexo_imagem'])) ? $anexos[$i]['anexo_imagem'] : "imagens/imagem_upload.png";
        $anexo_aux    = (isset($anexos[$i]['anexo_aux'])) ? $anexos[$i]['anexo_aux'] : "";
        $anexo_tdocs  = (isset($anexos[$i]['anexo_tdocs'])) ? $anexos[$i]['anexo_tdocs'] : null;
        $anexo_size   = (isset($anexos[$i]['size'])) ? $anexos[$i]['size'] : 0;

        $disabled = (isset($_GET['visualizar'])) ? 'disabled' : '';

        $nome_tipo_anexo = (!empty($anexos[$i]['tipo_anexo'])) ? $anexos[$i]['tipo_anexo'] : $tipo_anexo[$i]; 

        /* VALIDA SE FOR UM ARQUIVO DO TIPO PDF */
        $ext = strtolower(preg_replace("/.+\./", "", basename($anexo_imagem)));
        if ($ext == "pdf" OR $ext == "file") { $anexo_imagem = "imagens/pdf_icone.png"; }

        ?>
        <div id="div_anexo_<?=$i?>" class="tac" style="display: inline-block; margin: 0px 5px 0px 5px; vertical-align: top">
            <img src="<?=$anexo_imagem?>" class="anexo_thumb" style="width: 100px; height: 90px;" />
            <button type='button' class='btn btn-mini <?=$class_button?> btn-block class_anexo_<?=$i?>' name='anexar' rel='<?=$i?>' <?=$disabled?> ><?=$nome_tipo_anexo ?></button>
            <img src="imagens/loading_img.gif" class="anexo_loading" style="width: 64px; height: 64px; display: none;" />
            <input type="hidden" rel="anexo" name="anexo[<?=$i?>]" value="<?=$anexo_aux?>" />
            <input type="hidden" name="anexo_tdocs_<?=$i?>" value="<?=$anexo_tdocs?>">
            <input type="hidden" name="anexo_size_<?=$i?>" value="<?=$anexo_size?>">
            <input type="hidden" name="anexo_link_<?=$i?>" value="<?=$anexo_imagem?>">
            <input type="hidden" name="anexo_tdocs_id_antiga_<?=$i?>" value="<?=$anexos[$i]['tdocs_id']  ?>">
            <input type="hidden" name="anexo_tipo_<?=$i?>" value="<?=$tipo_anexo[$i]?>">
        </div>
        <?php
    }
    
    ?>
    <br /><br /><br />
	<!-- #### -->
<?php } ?>
	<p><br />
		<input type='hidden' name='btn_acao' value=''>
		<?php if ($login_fabrica == 30) { ?>
				<button type="button" class="btn btn-info" name="btn_etiquetas" id="btn_etiquetas">Etiquetas</button>		
		<?php } ?>
		<input type="button" class="btn btn-success" value="Gravar" onclick="javascript: if (document.frm_fornecedor.btn_acao.value == '' ) { document.frm_fornecedor.btn_acao.value='gravar' ; document.frm_fornecedor.submit() } else { alert ('Aguarde submissão') } return false;" ALT="Gravar formulário" border='0' >
		<?php if (isset($_GET['fornecedor'])) { ?>
			<input type="button" class="btn btn-danger" value="Excluir" onclick="javascript: if (document.frm_fornecedor.btn_acao.value == '' ) { document.frm_fornecedor.btn_acao.value='apagar' ; document.frm_fornecedor.submit() } else { alert ('Aguarde submissão') } return false;" ALT="Apagar" border='0' >
			<input type="button" class="btn" value="Limpar" onclick="javascript: window.location='<? echo $PHP_SELF ?>'; return false;" ALT="Limpar campos" border='0' >
		<?php } ?>
	</p><br />
</form>

<?php
if ($qtde_anexos > 0) {
    for ($i = 0; $i < $qtde_anexos; $i++) {
    ?>
        <form name="form_anexo" method="post" action="fornecedor_cadastro.php" enctype="multipart/form-data" style="display: none;" >
            <input type="file" name="anexo_upload_<?=$i?>" value="" />

            <input type="hidden" name="ajax_anexo_upload" value="t" />
            <input type="hidden" name="anexo_posicao" value="<?=$i?>" />
            <input type="hidden" name="anexo_chave" value="<?=$anexo_chave?>" />
        </form>
    <?php
    }
} ?>
<div class="row-fluid">
	<div class="span12">
		<p class="tac">
			<input type="button" class="btn" onClick="location.href='<?echo $PHP_SELF;?>?listartudo=1'" value="Listar Fornecedores">
		</p>
	</div>
</div>

<?
	$listar = $_GET['listartudo'];
	if($listar){ ?>
		<table id="resultado_fornecedores" class='table table-striped table-bordered table-hover table-fixed' >
			<thead>
				<tr class="titulo_tabela">
					<th colspan="3">Fornecedores Cadastrados</th>
				</tr>
				<tr class="titulo_coluna">
					<th>Código</th>
					<th>Fornecedor</th>
					<th>CPF / CNPJ</th>
				</tr>
			</thead>
			<tbody>
<?
		$sql = "SELECT tbl_fornecedor.fornecedor,
					tbl_fornecedor.nome               ,
					tbl_fornecedor.cnpj
					FROM	tbl_fornecedor
					JOIN tbl_fornecedor_fabrica ON tbl_fornecedor_fabrica.fornecedor = tbl_fornecedor.fornecedor
					WHERE   tbl_fornecedor_fabrica.fabrica = $login_fabrica ORDER BY tbl_fornecedor.nome";
			$res = pg_query ($con,$sql);

		for ($i = 0 ; $i < pg_num_rows ($res) ; $i++) {
			$fornecedor = trim(pg_fetch_result($res,$i,fornecedor));
			$nome       = trim(pg_fetch_result($res,$i,nome));
			$cnpj       = trim(pg_fetch_result($res,$i,cnpj));

	?>
		<tr>
			<td><?php echo $fornecedor; ?></td>
			<td><a href="?fornecedor=<?php echo $fornecedor; ?>"><?php echo $nome; ?></a></td>
			<td><?php echo $cnpj; ?></td>
		</tr>
	<?
		}
	?>
		</tbody>
	</table>
	<?}?>

<script language='javascript' src='address_components.js'></script>
<?
include("rodape.php");
?>
