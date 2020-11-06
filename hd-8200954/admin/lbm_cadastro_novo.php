<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="cadastros";
include 'autentica_admin.php';
include 'funcoes.php';
include_once "../class/tdocs.class.php";
$tDocs = new TDocs($con, $login_fabrica);


if($login_fabrica == 1){
    require "../classes/ParametrosAdicionaisFabrica.php";
    $parametrosAdicionaisObject = new ParametrosAdicionaisFabrica($login_fabrica);
    
    require "../classes/form/GeraComboType.php";
}

# Pesquisa pelo AutoComplete AJAX
$q = trim($_GET["q"]);
if (isset($_GET["q"])){
	$busca      = $_GET["busca"];
	$tipo_busca = $_GET["tipo_busca"];
	if (strlen($q)>2){
		if ($tipo_busca=="produto"){
			$sql = "SELECT tbl_produto.produto,
							tbl_produto.referencia,
							tbl_produto.descricao
					FROM tbl_produto
					JOIN tbl_linha ON tbl_linha.linha = tbl_produto.linha
					WHERE tbl_linha.fabrica = $login_fabrica ";

			if ($busca == "codigo"){
				$sql .= " AND UPPER(tbl_produto.referencia) like UPPER('%$q%') ";
			}else{
				$sql .= " AND UPPER(tbl_produto.descricao) like UPPER('%$q%') ";
			}

			$res = pg_query($con,$sql);
			if (pg_num_rows ($res) > 0) {
				for ($i=0; $i<pg_num_rows ($res); $i++ ){
					$produto    = trim(pg_fetch_result($res,$i,produto));
					$referencia = trim(pg_fetch_result($res,$i,referencia));
					$descricao  = trim(pg_fetch_result($res,$i,descricao));
					echo "$produto|$descricao|$referencia";
					echo "\n";
				}
			}
		}
	}
	exit;
}

if (strlen($_POST['btn_lista']) > 0) $btn_lista = $_POST['btn_lista'];
else                                 $btn_lista = $_GET['btn_lista'];

if (strlen($_POST['produto']) > 0) $produto = $_POST['produto'];
else                               $produto = $_GET["produto"];

if (strlen($_POST['referencia']) > 0) $referencia = $_POST['referencia'];
else                                  $referencia = $_GET["referencia"];

/*
if ($login_fabrica == 3 and $login_login <> "priscila" and $login_login <> "tulio" and $login_login <> "hugo" and $login_login <> "henrique") {
	/*
	   liberado acesso a lista básica para Hugo e Henrique,
	   de acordo com email enviado pela Priscila,
	   assunto "Liberação de acesso" de 11/08/2005 às 08:29

	echo "Apenas PRISCILA pode fazer manutenção da Lista Básica";
	exit;
}
	*/
if ($login_fabrica == 6 and $login_login <> "brazil" and $login_login <> "leandro" and $login_login <> "andrericardo" AND $login_login <> "engenhariatt") {
	echo "Apenas CRISTINA/ANDRÉ/LEANDRO podem fazer manutenção da Lista Básica";
	exit;
}

$acao = trim ($_GET['acao']);
if ($acao == "excluir"){
	$produto = trim ($_GET['produto']);
	$sql = "DELETE FROM tbl_lista_basica WHERE fabrica = $login_fabrica AND produto = $produto";
	$res = pg_query ($con,$sql);

	#-------------------- Envia EMAIL ------------------
	$sql = "SELECT email_gerente FROM tbl_fabrica WHERE fabrica = $login_fabrica AND LENGTH (TRIM (email_gerente)) > 0";
	$res = pg_query ($con,$sql);
	if (pg_num_rows ($res) > 0) {
		$email_gerente = pg_fetch_result ($res,0,0);

		$sql = "SELECT referencia, descricao FROM tbl_produto WHERE produto = $produto";
		$res = pg_query ($con,$sql);
		$produto_referencia = pg_fetch_result ($res,0,referencia);
		$produto_descricao  = pg_fetch_result ($res,0,descricao);
// echo $sql;
		$email_ok = mail ("$email_gerente" , utf8_encode("Lista Básica Apagara") , utf8_encode("Toda a lista básica do produto $produto_referencia - $produto_descricao acaba de ser apagada no site TELECONTROL") , "From: Telecontrol <helpdesk@telecontrol.com.br>" , "-f helpdesk@telecontrol.com.br" );
	}
	#---------------------------------------------------


	header ("Location: $PHP_SELF");
	exit;
}


if ($S3_sdk_OK) {
	include_once S3CLASS;
	if ($S3_online)
		$s3 = new anexaS3('ve', (int) $login_fabrica);
}


$qtde_linhas = 450 ;
if ($login_fabrica == 3) $qtde_linhas = 150;
if ($login_fabrica == 11) $qtde_linhas = 600;
if ($login_fabrica == 14) $qtde_linhas = 600;

$msg_erro = "";

$btn_acao = trim (strtolower ($_POST['btn_acao']));
$btn_importar = trim (strtolower ($_POST['btn_importar']));
$lbm      = trim (strtolower ($_POST['lbm']));

if (trim($btn_importar) == "importar") { # HD 185184
	$arquivo                = isset($_FILES["arquivo"]) ? $_FILES["arquivo"] : FALSE;
	$produto                = $_POST['produto_excel'];
	if (strlen ($msg_erro) == 0) {
		$config["tamanho"] = 2048000; // Tamanho máximo do arquivo (em bytes)
		if (strlen($arquivo["tmp_name"]) > 0 && $arquivo["tmp_name"] != "none"){
			preg_match("/\.(xls){1}$/i", $arquivo["name"], $ext);
		
			if ($ext[1] <>'xls'){
				$msg_erro = "Arquivo em formato inválido!";
			} else { // Verifica tamanho do arquivo
				if ($arquivo["size"] > $config["tamanho"])
					$msg_erro = "Arquivo tem tamanho muito grande! Deve ser de no máximo 2MB. Envie outro arquivo.";
			}
			if (strlen($msg_erro) == 0) {
				// Pega extensão do arquivo
				preg_match("/\.(xls){1}$/i", $arquivo["name"], $ext);
				$aux_extensao = "'".$ext[1]."'";

				$nome_sem_espaco = implode("", explode(" ",$arquivo["name"]));

				$nome_anexo = __DIR__ . "/documentos/produto.xls";

				if (strlen($msg_erro) == 0) {
					if (copy($arquivo["tmp_name"], $nome_anexo)) {
						require_once 'xls_reader.php';
						$data = new Spreadsheet_Excel_Reader();
						$data->setOutputEncoding('CP1251');
						$data->read('documentos/produto.xls');
						$res = pg_query ($con,"BEGIN TRANSACTION");

						$sql = "DELETE FROM tbl_lista_basica
								WHERE  tbl_lista_basica.produto = $produto
								AND    tbl_lista_basica.fabrica = $login_fabrica";
						$res = pg_query ($con,$sql);

						for ($i = 1; $i <= $data->sheets[0]['numRows']; $i++) {
							$ordem  = "";
							$posicao= "";
							$peca   = "";
							$type   = "";
							$qtde   = "";

							for ($j = 1; $j <= $data->sheets[0]['numCols']; $j++) {
								if($data->sheets[0]['numCols'] <> 6) {
									$msg_erro .= "Por favor, verificar o conteúdo de Excel, está faltando algumas colunas";
								}
								switch($j) {
									case 1: $ordem = $data->sheets[0]['cells'][$i][$j]; break;
									case 2: $posicao = $data->sheets[0]['cells'][$i][$j];break;
									case 3:
										$referencia = str_replace (".","",$data->sheets[0]['cells'][$i][$j]);
										$referencia = str_replace ("-","",$referencia);
										$referencia = str_replace ("/","",$referencia);
										$referencia = str_replace (" ","",$referencia);
										
										$sql = " SELECT peca 
												FROM tbl_peca
												WHERE fabrica = $login_fabrica
												AND   tbl_peca.referencia_pesquisa ilike '%$referencia%'";
										$res = @pg_query($con,$sql);
										if(@pg_num_rows($res) > 0){
											$peca = @pg_fetch_result($res,0,0);
										}else{
											$msg_erro .= "Peça ".$data->sheets[0]['cells'][$i][$j]." não encontrada no sistema<br>";
										}
										break;
									case 5: $type = !empty($data->sheets[0]['cells'][$i][$j]) ? $data->sheets[0]['cells'][$i][$j]:null; break;
									case 6: $qtde = $data->sheets[0]['cells'][$i][$j];break;
								}
							}

							if(strlen($msg_erro) == 0 and strlen($peca) > 0) {
								$sql = "INSERT INTO tbl_lista_basica (
											fabrica        ,
											produto        ,
											peca           ,
											qtde           ,
											posicao        ,
											ordem          ,
											type           ,
											admin          ,
											data_alteracao ,
											ativo          
										) VALUES (
											$login_fabrica,
											$produto      ,
											$peca         ,
											$qtde         ,
											'$posicao'    ,
											$ordem        ,
											'$type'       ,
											$login_admin  ,
											current_timestamp,
											't'            
								);";
								$res = @pg_query ($con,$sql);
								$msg_erro .= pg_errormessage($con);
							}
						}

						if(strlen($msg_erro) == 0) {
							$res = pg_query ($con,"COMMIT TRANSACTION");
					
							$sql = "SELECT email_gerente FROM tbl_fabrica WHERE fabrica = $login_fabrica AND LENGTH (TRIM (email_gerente)) > 0";
							$res = pg_query ($con,$sql);
							if (pg_num_rows ($res) > 0) {
								$email_gerente = pg_fetch_result ($res,0,0);

								$sql = "SELECT referencia, descricao FROM tbl_produto WHERE produto = $produto";
								$res = pg_query ($con,$sql);
								$produto_referencia = pg_fetch_result ($res,0,referencia);
								$produto_descricao  = pg_fetch_result ($res,0,descricao);

								$email_ok = mail ("$email_gerente" , utf8_encode("Alteração de Lista Básica") , utf8_encode("A lista básica do produto $produto_referencia - $produto_descricao acaba de ser alterada no site TELECONTROL") , "From: Telecontrol <helpdesk@telecontrol.com.br>" , "-f helpdesk@telecontrol.com.br" );
							}
							#---------------------------------------------------
							header ("Location: $PHP_SELF");
							exit;
						}else{
							$res = pg_query ($con,"ROLLBACK TRANSACTION");
						}
					}else{
						$msg_erro = "Arquivo não foi enviado!!! Tente outra vez";
					}
				}
			}
		}
	}
}

if (trim($btn_acao) == "duplicar") {
	$produto = $_POST['produto'];

	$res = pg_query ($con,"BEGIN TRANSACTION");

	$sql = "SELECT distinct tbl_lista_basica.produto
			FROM   tbl_lista_basica
			WHERE  tbl_lista_basica.produto = $produto
			AND    tbl_lista_basica.fabrica = $login_fabrica";
	$res = pg_query ($con,$sql);
// echo $sql;
	if (pg_num_rows($res) == 0) {
		for ($i = 0 ; $i < $qtde_linhas ; $i++) {
			$peca      = $_POST ['peca_' . $i] ;
			$peca_pai  = $_POST ['peca_pai_' . $i] ;
			$ordem     = $_POST ['ordem_' . $i] ;
			$serie_inicial = $_POST ['serie_inicial_' . $i] ;
			$serie_final   = $_POST ['serie_final_' . $i] ;
			$posicao   = $_POST ['posicao_' . $i] ;
			$descricao = $_POST ['descricao_' . $i] ;
			$type      = $_POST ['type_' . $i] ;
			$qtde      = $_POST ['qtde_' . $i] ;

			$ordem = trim ($ordem);
			$posicao = trim ($posicao);

			if (strlen($peca_pai)==0) {
				$peca_pai = 'null';
			}

			$serie_inicial = trim ($serie_inicial);
			$serie_inicial = str_replace (".","",$serie_inicial);
			$serie_inicial = str_replace ("-","",$serie_inicial);
			$serie_inicial = str_replace ("/","",$serie_inicial);
			$serie_inicial = str_replace (" ","",$serie_inicial);

			$serie_final   = trim ($serie_final);
			$serie_final = str_replace (".","",$serie_final);
			$serie_final = str_replace ("-","",$serie_final);
			$serie_final = str_replace ("/","",$serie_final);
			$serie_final = str_replace (" ","",$serie_final);

			if (strlen($type) == 0) $aux_type = null;
			else                    $aux_type = $type;

			if (strlen($qtde) == 0) $aux_qtde = 1;
			else                    $aux_qtde = $qtde;

			if (strlen($ordem) == 0) $ordem = "null";

			if(strlen($msg_erro)==0){
				if (strlen ($peca) > 0) {
					$sql = "SELECT peca FROM tbl_peca WHERE referencia = '$peca' AND fabrica = $login_fabrica";
					$res = @pg_query ($sql);
					#echo '2-'.nl2br($sql).'<br>';
					if (@pg_num_rows ($res) == 0) {
						$msg_erro .= "Peça $peca não cadastrada";
					}else{
						$peca = @pg_fetch_result ($res,0,0);

						//NÃO PODE INSERIR 2 PEÇAS NO MESMO PRODUTO - RAPHAEL GIOVANINI
						if($login_fabrica==3){
							$sql = "SELECT * FROM tbl_lista_basica
									WHERE produto = $produto
									AND   peca    = $peca
									AND   fabrica = $login_fabrica";
								//echo $sql;
							$res = @pg_query ($con, $sql);
							if (@pg_num_rows ($res) == 0) {
								$msg_erro .= "Peça $peca_referencia já cadastrada na lista básica deste produto ";
							}
						}

						if (strlen ($msg_erro) == 0) {

							$sql = "INSERT INTO tbl_lista_basica (
										fabrica        ,
										produto        ,
										peca           ,
										peca_pai       ,
										qtde           ,
										posicao        ,
										ordem          ,
										serie_inicial  ,
										serie_final    ,
										type           ,
										admin          ,
										data_alteracao
									) VALUES (
										$login_fabrica,
										$produto      ,
										$peca         ,
										$peca_pai     ,
										$aux_qtde     ,
										'$posicao'    ,
										$ordem        ,
										'$serie_inicial' ,
										'$serie_final'   ,
										'$aux_type'   ,
										$login_admin,
										current_timestamp
							);";
							$res = @pg_query ($con,$sql);
							$msg_erro .= pg_errormessage($con);
						}
					}
				}
			}
		}

		if (strlen ($msg_erro) == 0) {
			$res = pg_query ($con,"COMMIT TRANSACTION");


			#-------------------- Envia EMAIL ------------------
			$sql = "SELECT email_gerente FROM tbl_fabrica WHERE fabrica = $login_fabrica AND LENGTH (TRIM (email_gerente)) > 0";
			$res = pg_query ($con,$sql);
			if (pg_num_rows ($res) > 0) {
				$email_gerente = pg_fetch_result ($res,0,0);

				$sql = "SELECT referencia, descricao FROM tbl_produto WHERE produto = $produto";
				$res = pg_query ($con,$sql);
				$produto_referencia = pg_fetch_result ($res,0,referencia);
				$produto_descricao  = pg_fetch_result ($res,0,descricao);

				$email_ok = mail ("$email_gerente" , utf8_encode("Duplicação de Lista Básica") , utf8_encode("A lista básica do produto $produto_referencia - $produto_descricao acaba de ser criada a partir de uma duplicação no site TELECONTROL") , "From: Telecontrol <helpdesk@telecontrol.com.br>" , "-f helpdesk@telecontrol.com.br" );
			}
			#---------------------------------------------------

			header ("Location: $PHP_SELF");
			exit;
		}

		$referencia_duplicar = $_POST["referencia_duplicar"];
		$descricao_duplicar  = $_POST["descricao_duplicar"];
		$produto             = $_POST["produto"];
		$res = pg_query ($con,"ROLLBACK TRANSACTION");
	}else{
		$sql = "SELECT tbl_produto.referencia
				FROM   tbl_produto
				JOIN   tbl_linha USING (linha)
				WHERE  tbl_produto.produto = $produto
				AND    tbl_linha.fabrica   = $login_fabrica";
		$res = pg_query ($con,$sql);
// echo "$sql";
		if (pg_num_rows($res) > 0) $referencia = trim(pg_fetch_result($res,0,referencia));

		$msg_erro .= "Produto $referencia já possui lista básica e não pode ser duplicado.";
	}
}

if (trim($btn_acao) == "gravar") {

	$produto = $_POST['produto'];

	$res = pg_query ($con,"BEGIN TRANSACTION");
/*
	$referencia = $_POST['referencia'];
	$sql = "SELECT produto FROM tbl_produto WHERE referencia = '$referencia'";
	$res = pg_query ($con,$sql);
	if (pg_num_rows ($res) == 0) {
		$msg_erro = "Produto $referencia não cadastrado";
	}else{
		$produto = pg_fetch_result ($res,0,0);
*/
		$sql = "DELETE FROM tbl_lista_basica
				WHERE  tbl_lista_basica.produto = $produto
				AND    tbl_lista_basica.fabrica = $login_fabrica";
		$res = pg_query ($con,$sql);
//echo $sql;
		for ($i = 0 ; $i < $qtde_linhas ; $i++) {
			$ativo="";
			$peca      = $_POST ['peca_' . $i] ;
			$peca_pai  = $_POST ['peca_pai_' . $i] ;
			$ordem     = $_POST ['ordem_' . $i] ;
			$serie_inicial = $_POST ['serie_inicial_' . $i] ;
			$serie_final   = $_POST ['serie_final_' . $i] ;
			$posicao   = $_POST ['posicao_' . $i] ;
			$descricao = $_POST ['descricao_' . $i] ;
			$type      = $_POST ['type_' . $i] ;
			$qtde      = $_POST ['qtde_' . $i] ;
			$ativo     = $_POST ['ativo_' . $i] ;
			$ordem = trim ($ordem);
			$posicao = trim ($posicao);

			$peca_referencia =$peca;

			$serie_inicial = trim ($serie_inicial);
			$serie_inicial = str_replace (".","",$serie_inicial);
			$serie_inicial = str_replace ("-","",$serie_inicial);
			$serie_inicial = str_replace ("/","",$serie_inicial);
			$serie_inicial = str_replace (" ","",$serie_inicial);

			$serie_final   = trim ($serie_final);
			$serie_final = str_replace (".","",$serie_final);
			$serie_final = str_replace ("-","",$serie_final);
			$serie_final = str_replace ("/","",$serie_final);
			$serie_final = str_replace (" ","",$serie_final);

			if (strlen($type) == 0) $aux_type = null;
			else                    $aux_type = "'$type'";
			
			if (strlen($peca_pai)==0) {
				$peca_pai = 'null';
			}

			if (strlen($qtde) == 0) $aux_qtde = 1;
			else                    $aux_qtde = $qtde;

			if (strlen($ordem) == 0) $ordem = "null";



			if($login_fabrica == 50) {
				if(strlen($ativo) ==0) {
					$ativo = "f";
				}else{
					$ativo = "t";
				}
			}else{
				if (strlen($ativo) == 0) $ativo = 't';
			}

			if (strlen ($msg_erro) == 0) {
				if (strlen ($peca) > 0) {
					$sql = "SELECT peca FROM tbl_peca WHERE referencia = '$peca' AND fabrica = $login_fabrica";
					$res = @pg_query ($con, $sql);
					#echo "1-".nl2br($sql).'<br>';
					if (@pg_num_rows ($res) == 0) {
						$msg_erro .= "Peça $peca não cadastrada";
					}else{
						$peca = @pg_fetch_result ($res,0,0);

					if (strlen($peca_pai)>0) {
						$sql = "SELECT peca FROM tbl_peca WHERE referencia = '$peca_pai' AND fabrica = $login_fabrica";
						$res = @pg_query ($con, $sql);
						#echo "1-".nl2br($sql).'<br>';
						if (@pg_num_rows ($res) > 0) {
							$peca_pai = @pg_fetch_result ($res,0,0);
						}
					}
					

						//NÃO PODE INSERIR 2 PEÇAS NO MESMO PRODUTO - RAPHAEL GIOVANINI
						if($login_fabrica==3 ){
							$sql = "SELECT count(peca)as total FROM tbl_lista_basica
									WHERE produto = $produto
									AND   peca    = $peca
									AND   fabrica = $login_fabrica
									having count(peca)>0";
							$res = @pg_query ($con, $sql);
	//echo $sql.'<br><br>';
							if (@pg_num_rows ($res) > 0) {
								 $total = pg_fetch_result ($res,0,total);
								$msg_erro .= "$total Peça $peca_referencia já cadastrada na lista básica deste produto <br><br>";
							}//else echo
						}
						if (strlen ($msg_erro) == 0) {
							//Intelbras com problema de itens ativos e inativos na lista básica
							//HD 3211
							if($login_fabrica == 14){
								$sql = "INSERT INTO tbl_lista_basica (
											fabrica       ,
											produto       ,
											peca          ,
											qtde          ,
											posicao       ,
											ordem         ,
											serie_inicial ,
											serie_final   ,
											type          ,
											ativo         ,
											admin         ,
											data_alteracao
										) VALUES (
											$login_fabrica  ,
											$produto        ,
											$peca           ,
											$aux_qtde       ,
											'$posicao'      ,
											$ordem          ,
											'$serie_inicial',
											'$serie_final'  ,
											'$type'         ,
											'$ativo'        ,
											$login_admin    ,
											current_timestamp
								);";
							}else{
								$sql = "INSERT INTO tbl_lista_basica (
									fabrica       ,
									produto       ,
									peca          ,
									peca_pai      ,
									qtde          ,
									posicao       ,
									ordem         ,
									serie_inicial ,
									serie_final   ,
									type          ,
									ativo         ,
									admin         ,
									data_alteracao
								) VALUES (
									$login_fabrica  ,
									$produto        ,
									$peca           ,
									$peca_pai       ,
									$aux_qtde       ,
									'$posicao'      ,
									$ordem          ,
									'$serie_inicial',
									'$serie_final'  ,
									'$type'         ,
									'$ativo'        ,
									$login_admin    ,
									current_timestamp
								);";
							}
//	echo nl2br($sql).'<br><br>'; 
							$res = @pg_query ($con,$sql);
							$msg_erro .= pg_errormessage($con);
						}
					}
				}
			}
		}
	//}

	if (strlen ($msg_erro) == 0) {
		$res = pg_query ($con,"COMMIT TRANSACTION");


		#-------------------- Envia EMAIL ------------------
		$sql = "SELECT email_gerente FROM tbl_fabrica WHERE fabrica = $login_fabrica AND LENGTH (TRIM (email_gerente)) > 0";
		$res = pg_query ($con,$sql);
		if (pg_num_rows ($res) > 0) {
			$email_gerente = pg_fetch_result ($res,0,0);

			$sql = "SELECT referencia, descricao FROM tbl_produto WHERE produto = $produto";
			$res = pg_query ($con,$sql);
			$produto_referencia = pg_fetch_result ($res,0,referencia);
			$produto_descricao  = pg_fetch_result ($res,0,descricao);

			$email_ok = mail ("$email_gerente" , utf8_encode("Alteração de Lista Básica") , utf8_encode("A lista básica do produto $produto_referencia - $produto_descricao acaba de ser alterada no site TELECONTROL") , "From: Telecontrol <helpdesk@telecontrol.com.br>" , "-f helpdesk@telecontrol.com.br" );
		}
		#---------------------------------------------------
		header ("Location: $PHP_SELF");
		exit;
	}

	$referencia = $_POST["referencia"];
	$descricao  = $_POST["descricao"];
	$res = pg_query ($con,"ROLLBACK TRANSACTION");
}

$apagar = $_POST["apagar"];

if (trim($btn_acao) == "apagar" and strlen($apagar) > 0 ) {
	$res = pg_query ($con,"BEGIN TRANSACTION");
	$sql = "DELETE FROM tbl_lista_basica
			WHERE  tbl_lista_basica.fabrica      = $login_fabrica
			AND    tbl_lista_basica.lista_basica = $apagar;";
	$res = pg_query ($con,$sql);
	$msg_erro .= pg_errormessage($con);

	if (strlen ($msg_erro) == 0) {
		###CONCLUI OPERAÇÃO DE INCLUSÃO/EXLUSÃO/ALTERAÇÃO E SUBMETE
		$res = pg_query ($con,"COMMIT TRANSACTION");


		#-------------------- Envia EMAIL ------------------
		$sql = "SELECT email_gerente FROM tbl_fabrica WHERE fabrica = $login_fabrica AND LENGTH (TRIM (email_gerente)) > 0";
		$res = pg_query ($con,$sql);
		if (pg_num_rows ($res) > 0) {
			$email_gerente = pg_fetch_result ($res,0,0);

			$sql = "SELECT referencia, descricao FROM tbl_produto WHERE produto = $produto";
			$res = pg_query ($con,$sql);
			$produto_referencia = pg_fetch_result ($res,0,referencia);
			$produto_descricao  = pg_fetch_result ($res,0,descricao);

			$email_ok = mail ("$email_gerente" , utf8_encode("Item apagado da Lista Básica") , utf8_encode("Uma peça foi apagada da lista básica do produto $produto_referencia - $produto_descricao no site TELECONTROL") , "From: Telecontrol <helpdesk@telecontrol.com.br>" , "-f helpdesk@telecontrol.com.br" );
		}
		#---------------------------------------------------

		#Alterado por Sono 29/08/2006 - 13:40, não estava atualizando a página corretamente
		header ("Location: $PHP_SELF?produto=$produto");
		exit;

	}else{
		###ABORTA OPERAÇÃO DE INCLUSÃO/EXLUSÃO/ALTERAÇÃO E RECARREGA CAMPOS

		$produto   = $HTTP_POST_VARS["produto"];
		$peca      = $HTTP_POST_VARS["peca"];
		$res = pg_query ($con,"ROLLBACK TRANSACTION");
	}
}

##### DUPLICAR PEÇAS P/ NOVO TYPE P/ BLACK & DECKER #####
if (trim($btn_acao) == "duplicartype" && ($login_fabrica == 1 or $login_fabrica == 51)) {
	$produto               = $_POST["produto"];
	$type_duplicar_origem  = $_POST["type_duplicar_origem"];
	$type_duplicar_destino = $_POST["type_duplicar_destino"];

	if (strlen($type_duplicar_origem) == 0)  $msg_erro .= " Selecione o \"Type Origem\" p/ duplicar. ";
	if (strlen($type_duplicar_destino) == 0) $msg_erro .= " Selecione o \"Type Destino\" p/ duplicar. ";

	if ($type_duplicar_origem == $type_duplicar_destino) $msg_erro .= " Selecione o \"Type Destino\" diferente do \"Type Origem\". ";

	if (strlen($msg_erro) == 0) {
		$res = pg_query ($con,"BEGIN TRANSACTION");

		$sql =	"SELECT tbl_lista_basica.lista_basica
				FROM    tbl_lista_basica
				WHERE   tbl_lista_basica.fabrica = $login_fabrica
				AND     tbl_lista_basica.produto = $produto
				AND     tbl_lista_basica.type    = '$type_duplicar_origem';";
		$res = pg_query($con,$sql);

		if (pg_num_rows($res) == 0) $msg_erro .= " Não foi encontrado lista básica p/ este produto com o Type Origem \"$type_duplicar_origem\". ";

		$sql =	"SELECT tbl_lista_basica.lista_basica
				FROM    tbl_lista_basica
				WHERE   tbl_lista_basica.fabrica = $login_fabrica
				AND     tbl_lista_basica.produto = $produto
				AND     tbl_lista_basica.type    = '$type_duplicar_destino';";
		$res = pg_query($con,$sql);

		if (pg_num_rows($res) > 0) $msg_erro .= " Type Destino \"$type_duplicar_destino\" já cadastrado na lista básica p/ este produto. ";

		if (strlen($msg_erro) == 0) {
			$sql =	"INSERT INTO tbl_lista_basica (
						fabrica       ,
						posicao       ,
						ordem         ,
						serie_inicial ,
						serie_final   ,
						qtde          ,
						peca          ,
						produto       ,
						type          ,
						admin         ,
						data_alteracao
					)	SELECT  fabrica                          ,
								posicao                          ,
								ordem                            ,
								serie_inicial                    ,
								serie_final                      ,
								qtde                             ,
								peca                             ,
								produto                          ,
								'$type_duplicar_destino' AS type ,
								$login_admin                     ,
								current_timestamp
						FROM tbl_lista_basica
						WHERE fabrica = $login_fabrica
						AND   produto = $produto
						AND   type    = '$type_duplicar_origem';";
			$res = @pg_query($con,$sql);
			$msg_erro .= pg_errormessage($con);

			if (strlen($msg_erro) == 0) {
				$res = pg_query ($con,"COMMIT TRANSACTION");
				header("Location: $PHP_SELF");
				exit;
			}else{
				$res = pg_query ($con,"ROLLBACK TRANSACTION");
			}
		}
	}
}
##### DUPLICAR PEÇAS P/ NOVO TYPE P/ BLACK & DECKER #####


if (strlen ($btn_lista) > 0) {//se o botão foi clicado
	if (strlen($produto) > 0) {
		$sql = "SELECT  tbl_produto.referencia,
						tbl_produto.descricao ,
						tbl_produto.voltagem
				FROM    tbl_produto
				JOIN    tbl_linha    ON tbl_linha.linha   = tbl_produto.linha
									AND tbl_linha.fabrica = $login_fabrica
				WHERE   tbl_produto.produto = $produto;";
		$res = pg_query ($con,$sql);
//pega o codigo do produto
// echo "$sql";
		if (pg_num_rows($res) > 0) {
			$referencia = trim(pg_fetch_result($res,0,referencia));
			$descricao  = trim(pg_fetch_result($res,0,descricao));
			if ($login_fabrica == 1){
				$voltagem  = trim(pg_fetch_result($res,0,voltagem));
				$descricao = $descricao." ".$voltagem;
			}
		}
	}

	if (strlen ($referencia) == 0) $msg_erro .= "Preencha a referência do produto";
}

$layout_menu = "cadastro";
$title = "Cadastramento de Lista Básica";
include 'cabecalho.php';

?>
<style type="text/css">
.oculto {display:none;} /* Oculta o texto em todos os navegadores   */
</style>

<!--[if gte IE 7]>
    <style type="text/css">
	.oculto {    /*  'Mostra' só no MSIE 7 e MSIE 8 */
		display:inline-block;
		_zoom:1;
		position: relative;
		z-index: 0;
		width:3px;
		height:1em;
		color:transparent
	}
    </style>
<![endif]-->

<script language='javascript'>
function fnc_pesquisa_produto (campo, campo2, tipo) {
	if (tipo == "referencia" ) {
		var xcampo = campo;
	}

	if (tipo == "descricao" ) {
		var xcampo = campo2;
	}

	if (xcampo.value != "") {
		var url = "";
		url = "produto_pesquisa_2.php?campo=" + xcampo.value + "&tipo=" + tipo + "&lbm=1"+ "&exibe=<? echo $_SERVER['REQUEST_URI']; ?>" ;
		janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=500, height=400, top=18, left=0");
		janela.referencia	= campo;
		janela.descricao	= campo2;
		janela.produto		= document.frm_lbm.produto;
		janela.focus();
	}
}

function fnc_pesquisa_peca (campo, campo2, tipo, campo_preco) {
	if (tipo == "referencia" || tipo == "referencia_pai") {
		var xcampo = campo;
	}

	if (tipo == "descricao" || tipo == "descricao_pai") {
		var xcampo = campo2;
	}

	if (xcampo.value != "") {
		var url = "";
		url = "peca_pesquisa_2.php?campo=" + xcampo.value + "&tipo=" + tipo ;
		janela = window.open(url, "janela", "toolbar=no, location=yes, status=yes, scrollbars=yes, directories=no, width=500, height=400, top=18, left=0");
		janela.referencia	= campo;
		janela.descricao	= campo2;
		//campo_preco.value = "";
		janela.focus();
	}
}

</script>

<? include "javascript_calendario.php"; //adicionado por Fabio 27-09-2007 ?>

<script type='text/javascript' src='js/jquery.autocomplete.js'></script>
<link rel="stylesheet" type="text/css" href="js/jquery.autocomplete.css" />
<script type='text/javascript' src='js/jquery.bgiframe.min.js'></script>
<script type='text/javascript' src='js/dimensions.js'></script>

<script language="JavaScript">
$().ready(function() {

	function formatItem(row) {
		return row[2] + " - " + row[1];
	}

	/* Busca por Produto */
	$("#referencia").autocomplete("<?echo $PHP_SELF.'?tipo_busca=produto&busca=codigo'; ?>", {
		minChars: 3,
		delay: 150,
		width: 350,
		matchContains: true,
		formatItem: formatItem,
		formatResult: function(row) {return row[2];}
	});

	$("#referencia").result(function(event, data, formatted) {
		$("#descricao").val(data[1]) ;
	});

	/* Busca pelo Nome */
	$("#descricao").autocomplete("<?echo $PHP_SELF.'?tipo_busca=produto&busca=nome'; ?>", {
		minChars: 3,
		delay: 150,
		width: 350,
		matchContains: true,
		formatItem: formatItem,
		formatResult: function(row) {return row[1];}
	});

	$("#descricao").result(function(event, data, formatted) {
		$("#referencia").val(data[2]) ;
	});


});
</script>

<body>

<DIV ID='wrapper'>
<form name="frm_lbm" method="post" action="<? echo $PHP_SELF ?>" enctype='multipart/form-data'>

<? if (strlen($msg_erro) > 0) {
	if (strpos($msg_erro,"ERROR: ") !== false) {
			$erro = "Foi detectado o seguinte erro:<br>";
			$msg_erro = substr($msg_erro, 6);
		}

		// retira CONTEXT:
	if (strpos($msg_erro,"CONTEXT:")) {
		$x = explode('CONTEXT:',$msg_erro);
		$msg_erro = $x[0];
	}


?>
<div class='error'><? echo $msg_erro; ?></div>
<p>
<? } ?>

<center>
<?
#$produto    = $_POST['produto'];
#$referencia = $_POST['referencia'];
#if (strlen($referencia) == 0) $referencia = trim($_GET['referencia']);

if (strlen ($produto) > 0 OR strlen ($referencia) > 0) {

	if (strlen ($referencia) > 0) {
		$sql = "SELECT tbl_produto.produto, tbl_produto.referencia, tbl_produto.descricao 
		FROM tbl_produto 
		JOIN tbl_linha using(linha)
		WHERE tbl_produto.referencia = '$referencia'
		AND   tbl_linha.fabrica = $login_fabrica";
	}
	if (strlen ($produto) > 0) {
		$sql = "SELECT tbl_produto.produto, tbl_produto.referencia, tbl_produto.descricao 
		FROM tbl_produto 
		JOIN tbl_linha using(linha)
		WHERE produto = $produto 
		AND   tbl_linha.fabrica = $login_fabrica";
	}
	$res = pg_query ($con,$sql);
	if (pg_num_rows ($res) == 0) {
		$msg_erro  .= "Produto $referencia não cadastrado";
		$descricao = "";
		$produto   = "";
	}else{
		$descricao  = pg_fetch_result ($res,0,descricao);
		$referencia = pg_fetch_result ($res,0,referencia);
		$produto    = pg_fetch_result ($res,0,produto);
	}
}

echo "<INPUT TYPE=\"hidden\" name='produto' value='$produto'>";

if (strlen ($produto) > 0 and $login_fabrica == 11) {
	echo"<table  border='0' cellspacing='2' cellpadding='2' align='center'>";
		echo"<tr>";
			echo "<td><img src='imagens/excell.gif'></td><td align='center'<font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>Clique aqui para fazer o </font><a href='lbm_cadastro_xls.php?produto=$produto'><font face='Arial, Verdana, Times, Sans' size='2' color='#0000FF'>download do arquivo em EXCEL</font></a>.<br><font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>Você pode ver, imprimir e salvar a tabela para consultas off-line.</font></td>";
		echo "</tr>";
	echo "</table>";
}

?>
</center>

<? if (strlen($btn_lista) == 0){ ?>

	<font face='arial' size='-1' color='#6699FF'><b>Para pesquisar um produto, informe parte da referência ou descrição do produto.</b></font>
	<table width='600' align='center' border='0'>
	<tr bgcolor='#d9e2ef'>
		<td align='center'>
			<b>Referência</b>
		</td>
		<td align='center'>
			<b>Descrição</b>
		</td>
	</tr>

	<tr>
		<td align='center'>
			<input type="text" name="referencia" id="referencia" value="<? echo $referencia ?>" size="15" maxlength="20" <? if ($login_fabrica == 5 or $login_fabrica == 1 or $login_fabrica == 51) { ?> onblur="javascript: fnc_pesquisa_produto (document.frm_lbm.referencia,document.frm_lbm.descricao,'referencia')" <? } ?>>&nbsp;<img src='../imagens/btn_buscar5.gif' style="cursor: hand;" border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_produto (document.frm_lbm.referencia,document.frm_lbm.descricao,'referencia')">
		</td>
		<td align='center'>
			<input type="text" name="descricao" id="descricao" value="<? echo $descricao; ?>" size="50" maxlength="50" <? if ($login_fabrica == 5) { ?> onblur="javascript: fnc_pesquisa_produto (document.frm_lbm.referencia,document.frm_lbm.descricao,'descricao')" <? } ?>>&nbsp;<img src='../imagens/btn_buscar5.gif' border='0' style="cursor:pointer" align='absmiddle' onclick="javascript: fnc_pesquisa_produto (document.frm_lbm.referencia,document.frm_lbm.descricao,'descricao')">
		</td>
	</tr>
	</table>

	<input type='hidden' name='btn_lista' value=''>
	<p align='center'><img src='imagens/btn_listabasicademateriais.gif' onclick='document.frm_lbm.btn_lista.value="listar"; document.frm_lbm.submit();' style="cursor:pointer;">

	<br>

	<center>
	<?
	if (file_exists ('/www/htdocs/assist/vistas/' . $produto . '.gif')) {
		echo "<a href='vista_explodida.php?produto=$produto' target='_blank'>Clique aqui</a> para ver a vista-explodida";
	}else{
		echo "Produto sem vista explodida";
	}
	?>
	</center>
<?
}else{
?>
	<br>
	<table width='400' align='center' border='1'>
		<tr  bgcolor='#d9e2ef'>
			<td align='center'><b>Referência</b></td>
			<td align='center'><b>Descrição</b></td>
		</tr>
		<tr>
			<td align='center'><? echo $referencia ?></td>
			<td align='center'><? echo $descricao ?></td>
		</tr>
	</table>
<BR>
	<center>
	<?
	if(strlen($produto) >0){
		$sql = "Select DISTINCT comunicado,extensao
				from tbl_comunicado
				LEFT JOIN tbl_comunicado_produto USING(comunicado)
				where fabrica=$login_fabrica
				and (tbl_comunicado.produto = $produto  OR tbl_comunicado_produto.produto = $produto)
				and tipo = 'Vista Explodida'";
		$res = pg_query($con,$sql);
		if (pg_num_rows($res) > 0) {
			$vista_explodida = pg_fetch_result($res,0,comunicado);
			$ext             = pg_fetch_result($res,0,extensao);
		}



		if (strlen($vista_explodida) > 0) {
			$linkVE = null;
			if ($S3_online) {
				if ($s3->temAnexos($vista_explodida))
					$linkVE = $s3->url;
			} else {
				if (file_exists ('../comunicados/'.$vista_explodida.'.'.$ext)) {
					$linkVE = "../comunicados/$vista_explodida.$ext";
				}
			}
			if ($linkVE) {
				echo "<a href='../comunicados/".$vista_explodida.".".$ext."' target='_blank'>Clique aqui</a> para ver a vista-explodida";
			}else {
				echo "<a href='vista_explodida_cadastro.php?comunicado=$vista_explodida' target='_blank'>Clique aqui</a> para ver a vista-explodida";
			}
		}else{
			echo "Produto sem vista explodida";
		}

			# HD 138855
			$sql = " SELECT tbl_admin.login,to_char(tbl_lista_basica.data_alteracao,'DD/MM/YYYY HH24:MI') as data_alteracao
				FROM tbl_lista_basica
				JOIN tbl_admin USING(admin)
				WHERE produto = $produto
				AND   tbl_lista_basica.admin IS NOT NULL
				AND   tbl_lista_basica.data_alteracao IS NOT NULL
				ORDER BY data_alteracao desc limit 1;";
			$res = pg_query($con,$sql);
			if(pg_num_rows($res) > 0){
				echo "<div id='wrapper'></div>";
				echo "<div id='wrapper'>ÚLTIMA ATUALIZAÇÃO</div>";
				echo "<div id='wrapper'>".pg_fetch_result($res,0,0)." - ".pg_fetch_result($res,0,1);
				echo "</div>";
				echo "<div id='wrapper'></div>";
			}

			if($login_fabrica == 1) { // HD 185184
?>
	<br/><br/>
	<table width='650' align='center' border='1' cellspacing='0' cellpadding='2'>
		<tr>
			<td>
				<div align='center' style='font-size:16px'><strong>Cadastrar Lista Básica com arquivo Excel</strong></div>
			</td>
		</tr>
		<tr>
			<td>
				<table width='650' align='center' border='0' cellspacing='0' cellpadding='2'>
					<tr >
						<td style='padding: 5 10px;text-align:center;font-size:13px;font-weight:bold'>
							O Layout de arquivo deve ser igual o que está nessa tela<br>
							Não precisa de cabeçalho
						</td>
					</tr>
					<tr >
						<td style='padding: 5 10px;text-align:center'>
							<input type='hidden' value='<?=$produto?>' name='produto_excel'>
							<input type='hidden' name='btn_lista' value='listar'>
							<input type='file' name='arquivo'><input type='submit' name='btn_importar' value='Importar'>
						</td>
					</tr>
				</table>
			</td>
		</tr>
	</table>

<? }
	} ?>
	
	</center>
<BR>

	<p align="center"><a href='<?echo $PHP_SELF?>?'>Clique aqui para pesquisar outro produto</a></p>

<?
}
?>
<!--
<p align='center'><input type='submit' name='btn_lista' style="cursor:pointer" value='Lista Básica de Materiais'>
 -->
<br>

<?
if (strlen ($btn_lista) > 0 OR strlen ($produto) > 0) {
	$msg_erro = "";
	$referencia = trim($_POST['referencia']);
	if (strlen($referencia) == 0) $referencia = trim($_GET['referencia']);

	if($login_fabrica==45){
		$slt_preco  = " tbl_tabela_item.preco                 ,";
		$join_preco = " LEFT JOIN tbl_tabela_item USING (peca) ";
	}
	if (strlen ($produto) > 0) {
		$sql = "SELECT      tbl_lista_basica.lista_basica  ,
							tbl_lista_basica.posicao       ,
							tbl_lista_basica.ordem         ,
							tbl_lista_basica.serie_inicial ,
							tbl_lista_basica.serie_final   ,
							tbl_lista_basica.qtde          ,
							tbl_lista_basica.type          ,
							tbl_peca.referencia            ,
							tbl_peca.descricao
					FROM    tbl_lista_basica
					JOIN    tbl_peca USING (peca)
					WHERE   tbl_lista_basica.fabrica = $login_fabrica
					AND     tbl_lista_basica.produto = $produto
					ORDER BY tbl_peca.referencia, tbl_peca.descricao";
#					ORDER BY lpad(tbl_lista_basica.posicao,20,''), tbl_peca.descricao";

		$sql = "SELECT		$slt_preco
							tbl_lista_basica.lista_basica  ,
							tbl_lista_basica.ordem         ,
							tbl_lista_basica.posicao       ,
							tbl_lista_basica.serie_inicial ,
							tbl_lista_basica.serie_final   ,
							tbl_lista_basica.qtde          ,
							tbl_lista_basica.type          ,
							tbl_lista_basica.ativo         ,
							tbl_peca.referencia            ,
							tbl_peca.descricao             ,
							(select tbl_peca.descricao from tbl_peca where tbl_peca.peca = tbl_lista_basica.peca_pai) as descricao_pai,
							(select tbl_peca.referencia from tbl_peca where tbl_peca.peca = tbl_lista_basica.peca_pai) as referencia_pai,
							tbl_lista_basica.peca_pai,
							tbl_peca.peca
					FROM    tbl_lista_basica
					JOIN    tbl_peca USING (peca)
					$join_preco
					WHERE   tbl_lista_basica.fabrica = $login_fabrica
					AND     tbl_lista_basica.produto = $produto ";

		$order_by = trim($_GET['ordem']);
// echo nl2br($sql);
		if (strlen($order_by) == 0) {
			if ($login_fabrica == 1 or $login_fabrica == 51)  $sql .= "ORDER BY tbl_lista_basica.type, tbl_lista_basica.ordem";
			elseif ($login_fabrica == 45) $sql .= "ORDER BY tbl_lista_basica.ordem"; // HD 8226 Gustavo
			else 					  $sql .= "ORDER BY tbl_peca.referencia, tbl_peca.descricao";
		}else{
			switch ($order_by){
				case 'referencia':	$sql .= "ORDER BY tbl_peca.referencia";	break;
				case 'descricao':	$sql .= "ORDER BY tbl_peca.descricao";	break;
				case 'posicao':		$sql .= "ORDER BY tbl_lista_basica.posicao";	break;
				case 'qtde':		$sql .= "ORDER BY tbl_lista_basica.qtde";		break;
				case 'ordem':		$sql .= "ORDER BY tbl_lista_basica.ordem";		break;
				case 'preco':	    $sql .= "ORDER BY tbl_tabela_item.preco";	break;
			}
		}

		$res = pg_query ($con,$sql);
		echo "<input type='hidden' name='btn_acao' value=''>";
		echo "<input type='hidden' name='apagar' value=''>";
		echo "<input type='hidden' name='duplicar' value=''>";

		echo "<table width='300' align='center' border='0'>";
		echo "<tr bgcolor='#FFFFFF'>";
		echo "<td align='center' bgcolor='#91C8FF'>&nbsp;&nbsp;</td>";
		echo "<td align='left'><b>Peça Alternativa</b></td>";
		echo "<td align='center' bgcolor='#00B95C'>&nbsp;&nbsp;</td>";
		echo "<td align='left'><b>De-Para</b></td>";
		if($login_fabrica == 14){
			echo "<td align='center' bgcolor='#F2ED84'>&nbsp;&nbsp;</td>";
			echo "<td align='left'><b>Peça Inativa</b></td>";
		}
		// HD 39525
		if(strlen($produto) > 0){
			$sqlc="SELECT count(*) FROM tbl_lista_basica where produto = $produto
			and  fabrica = $login_fabrica";
			$resc=pg_query($con,$sqlc);
			$qtde_total = pg_fetch_result($resc,0,0);
			$qtde_linhas = $qtde_total + 100;
		}

		echo "</tr>";
		echo "</table>";
		for ($i = 0 ; $i < $qtde_linhas ; $i++) {
			if ($i % 20 == 0) {
				if ($i > 0) echo "</table>";

				if ($i > 1) {
					echo "<p align='center'><img src='imagens_admin/btn_gravar.gif' onclick='document.frm_lbm.btn_acao.value = \"gravar\" ; document.frm_lbm.submit()' style='cursor:pointer;'>";

					echo "<p>";
				}

				echo "<table width='400' align='center' border='0'>";
				echo "<tr bgcolor='#cccccc'>";
				if ($login_fabrica ==50) {
				echo "<td align='center'><b>Ativo</b></td>";
				}
				if ($login_fabrica <> 6) {
				echo "<td align='center'><a href='$PHP_SELF?referencia=$referencia&ordem=ordem'><b>Ordem</b></a></td>";
				}
				if ($login_fabrica == 6 or $login_fabrica == 15 ) {
					echo "<td align='center'><b>Série IN</b></td>";
					echo "<td align='center'><b>Série OUT</b></td>";
				}
				echo "<td align='center'><a href='$PHP_SELF?referencia=$referencia&ordem=posicao'><b>";
				// HD38821
				if($login_fabrica == 3) echo "Localização";
				else echo "Posição";
				echo "</b></a></td>";
				if ($login_fabrica == 5) {
				echo "<td align='center'><b>Peça Pai</b></td>";
				echo "<td align='center'><b>Descrição Pai</b></td>";
				}
				echo "<td align='center'><a href='$PHP_SELF?referencia=$referencia&ordem=referencia'><b>Peça</b></a></td>";
				echo "<td align='center'><a href='$PHP_SELF?referencia=$referencia&ordem=descricao'><b>Descrição</b></a></td>";
				
				if ($login_fabrica == 1 or $login_fabrica == 51) {
					echo "<td align='center'><b>Type</b></td>";
				}
				// HD 23586
				if ($login_fabrica == 45) {
					echo "<td align='center'><a href='$PHP_SELF?referencia=$referencia&ordem=preco'><b>Preço</b></td>";
				}
				echo "<td align='center'><a href='$PHP_SELF?referencia=$referencia&ordem=qtde'><b>Qtde</b></a></td>";
				echo "<td align='center'><b>Ação</b></td>";

//  HD 113942 - Adicionar Lenoxx às fábricas que tem a imagem das peças na lista básica (cadastro, pesquisa e consulta)
				if ($login_fabrica == 6 or $login_fabrica == 11 or $login_fabrica == 45 or $login_fabrica == 50) {
					echo "<td align='center'><b>Imagem</b></td>";
				}
				echo "</tr>";
			}

			$peca = "peca_$i" ;
			$peca = $$peca;

			$ordem = "ordem_$i" ;
			$ordem = $$ordem;

			$serie_inicial = "serie_inicial_$i" ;
			$serie_inicial = $$serie_inicial;

			$serie_final = "serie_final_$i" ;
			$serie_final = $$serie_final;

			$posicao = "posicao_$i" ;
			$posicao = $$posicao;

			$descricao = "descricao_$i" ;
			$descricao = $$descricao;

			$type = "type_$i" ;
			$type = $$type;

			$qtde = "qtde_$i" ;
			$qtde = $$qtde;

			$ativo = "ativo_$i" ;
			$ativo = $$ativo;

			$preco = "preco_$i" ;
			$preco = $$preco;

			if (strlen ($btn_lista) > 0) {
				$ordem = "";
				$posicao = "";
				$peca = "";
				$xpeca = "";
				$descricao = "";
				$type = "";
				$qtde = "";
				$ativo = "";
				$preco = "";
			}
			$cor       = "#FFFFFF";
			$xpeca = "";

			if ($i < pg_num_rows ($res) AND strlen ($msg_erro) == 0) {
				$lbm           = pg_fetch_result ($res,$i,lista_basica);
				$ordem         = pg_fetch_result ($res,$i,ordem);
				$posicao       = pg_fetch_result ($res,$i,posicao);
				$serie_inicial = pg_fetch_result ($res,$i,serie_inicial);
				$serie_final   = pg_fetch_result ($res,$i,serie_final);
				$peca          = pg_fetch_result ($res,$i,referencia);
				$peca_pai      = pg_fetch_result ($res,$i,referencia_pai);
				$descricao     = pg_fetch_result ($res,$i,descricao);
				$descricao_pai = pg_fetch_result ($res,$i,descricao_pai);
				$type          = pg_fetch_result ($res,$i,type);
				$qtde          = pg_fetch_result ($res,$i,qtde);
				$ativo         = pg_fetch_result ($res,$i,ativo);
				$xpeca         = pg_fetch_result ($res,$i,peca);
				$xpeca_pai     = pg_fetch_result ($res,$i,peca_pai);
				if ($login_fabrica==45){
					$preco = pg_fetch_result ($res,$i,preco);
					$preco = number_format($preco, 2);
					$preco = str_replace(".",",",$preco);
				}
				$sql = "SELECT  tbl_peca_alternativa.para
						FROM    tbl_peca_alternativa
						WHERE   tbl_peca_alternativa.para    = '$peca'
						AND     tbl_peca_alternativa.fabrica = $login_fabrica;";
				$res1 = pg_query ($con,$sql);

				if (pg_num_rows($res1) > 0) $cor = "#91C8FF";

				$sql = "SELECT  tbl_depara.de,
								tbl_peca.descricao,
								tbl_peca.referencia
						FROM    tbl_depara
						JOIN    tbl_peca on tbl_peca.referencia = tbl_depara.de and tbl_peca.fabrica = $login_fabrica
						WHERE   tbl_depara.para    = '$peca'
						AND     tbl_depara.fabrica = $login_fabrica;";
				$res1 = pg_query ($con,$sql);

				if (pg_num_rows($res1) > 0) {
					$xpeca_de            = pg_fetch_result ($res1,0,de);
					$xreferencia_peca_de = pg_fetch_result ($res1,0,referencia);
					$xdescricao_peca_de  = pg_fetch_result ($res1,0,descricao);
					$cor = "#00B95C";
				}else{
					$xpeca_de            = "";
					$xreferencia_peca_de = "";
					$xdescricao_peca_de  = "";
				}

				if($login_fabrica == 14 and $ativo == 'f' and strlen($ativo) > 0) {
					$cor = "#F2ED84";
				}

				if(strlen($ativo) == 0) $ativo = "";

			}

			echo "<tr>";
			if($login_fabrica == 50) {
				echo "<td bgcolor='$cor'>";
				echo "<INPUT TYPE='checkbox' name='ativo_$i' value='$ativo'";
				if($ativo =='t') {
					echo " CHECKED ";
				}
				echo ">";
				echo "</td>";
			}
			if ($login_fabrica <> 6) {
			echo "<td bgcolor='$cor'>";
			echo "<input type='text' name='ordem_$i' value='$ordem' size='3' maxlength='3'><br>&nbsp;";
			echo "</td>";
			}
			if ($login_fabrica == 6 or $login_fabrica == 15) {
				echo "<td bgcolor='$cor'>";
				echo "<input type='text' name='serie_inicial_$i' value='$serie_inicial' size='10' maxlength='20'><br>&nbsp;";
				echo "</td>";

				echo "<td bgcolor='$cor'>";
				echo "<input type='text' name='serie_final_$i' value='$serie_final' size='10' maxlength='20'><br>&nbsp;";
				echo "</td>";
			}

			echo "<td bgcolor='$cor'>";
			if($login_fabrica <> 50) {
				echo "<INPUT TYPE='hidden' name='ativo_$i' value='$ativo'>";
			}
			echo "<input type='text' name='posicao_$i' value='$posicao' size='15' maxlength='50'";
			if($login_fabrica == 14 and $ativo == 'f' and strlen($ativo) > 0){ echo " readonly='readonly' "; }
			echo " ><br>&nbsp;";
			echo "</td>";
			if ($login_fabrica == 5) {
			echo "<td bgcolor='$cor' nowrap>";
				echo "<span class='oculto'>$peca_pai</span><input type='text' name='peca_pai_$i' value='$peca_pai' size='15' maxlength='20'
				onblur=\"javascript: fnc_pesquisa_peca (document.frm_lbm.peca_pai_$i , document.frm_lbm.descricao_pai_$i , 'referencia_pai', 0)\">";
				echo "&nbsp;<img src='../imagens/btn_buscar5.gif' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_peca (document.frm_lbm.peca_pai_$i , document.frm_lbm.descricao_pai_$i , \"referencia_pai\")' style='cursor:pointer'><br>&nbsp;";
				echo "<font size='-3' color='#ffffff'>$peca</font>";
				echo "</td>";

			echo "<td bgcolor='$cor' nowrap>";
			echo "<span class='oculto'>$descricao_pai</span><input type='text' name='descricao_pai_$i' value='$descricao_pai' size='30' maxlength='50' onblur=\"javascript: fnc_pesquisa_peca (document.frm_lbm.peca_pai_$i , document.frm_lbm.descricao_pai_$i , 'descricao_pai')\">";
			echo "&nbsp;<img src='../imagens/btn_buscar5.gif' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_peca (document.frm_lbm.peca_pai_$i , document.frm_lbm.descricao_pai_$i , \"descricao_pai\")' style='cursor:pointer'><br>";
			echo "<font size='-3' color='#ffffff'>$descricao</font>";
			echo "</td>";
			}
			echo "<td bgcolor='$cor' nowrap>";
			echo "<span class='oculto'>$peca</span><input type='text' name='peca_$i' value='$peca' size='20' maxlength='20'";
			if ($login_fabrica == 5  or $login_fabrica == 1) echo " onblur=\"javascript: fnc_pesquisa_peca (document.frm_lbm.peca_$i , document.frm_lbm.descricao_$i , 'referencia', 0)\"";
			if($login_fabrica == 14 and $ativo == 'f' and strlen($ativo) > 0){ echo " readonly='readonly' "; }
			echo ">";
			echo "&nbsp;<img src='../imagens/btn_buscar5.gif' border='0' align='absmiddle'";
			if ($login_fabrica==45) echo " onclick='javascript: fnc_pesquisa_peca (document.frm_lbm.peca_$i , document.frm_lbm.descricao_$i , \"referencia\" , document.frm_lbm.preco_$i)' style='cursor:pointer'><br>&nbsp;"; else echo " onclick='javascript: fnc_pesquisa_peca (document.frm_lbm.peca_$i , document.frm_lbm.descricao_$i , \"referencia\")' style='cursor:pointer'><br>&nbsp;";
			echo "<font size='-3' color='#ffffff'>$xpeca_de</font>";
			echo "</td>";

			echo "<td bgcolor='$cor' nowrap>";
			echo "<span class='oculto'>$descricao</span><input type='text' name='descricao_$i' value='$descricao' size='50' maxlength='50'";
			if ($login_fabrica == 5) echo " onblur=\"javascript: fnc_pesquisa_peca (document.frm_lbm.peca_$i , document.frm_lbm.descricao_$i , 'descricao')\"";
			if($login_fabrica == 14 and $ativo == 'f' and strlen($ativo) > 0){ echo " readonly='readonly' "; }
			echo ">";
			echo "&nbsp;<img src='../imagens/btn_buscar5.gif' border='0' align='absmiddle'";
			if ($login_fabrica==45) echo " onclick='javascript: fnc_pesquisa_peca (document.frm_lbm.peca_$i , document.frm_lbm.descricao_$i , \"descricao\", document.frm_lbm.preco_$i)' style='cursor:pointer'><br>&nbsp;"; else echo " onclick='javascript: fnc_pesquisa_peca (document.frm_lbm.peca_$i , document.frm_lbm.descricao_$i , \"descricao\")' style='cursor:pointer'><br>";
			echo "<font size='-3' color='#ffffff'>$xdescricao_peca_de</font>";
			echo "</td>";
			//HD 23586
			if ($login_fabrica == 45){
				echo "<td bgcolor='#FFFFFF' nowrap>";
				echo "<input type='text' name='preco_$i' value='$preco' size='5' maxlength='' readonly><br>&nbsp;</td>";
			}

			if ($login_fabrica == 1 or $login_fabrica == 51) {
				echo "<td bgcolor='$cor' valign='top'>";
                                GeraComboType::makeComboType($parametrosAdicionaisObject, $type, null, array("index"=>$i));
     				echo GeraComboType::getElement();
    				echo "<br>.&nbsp;";
				echo "</td>";
			}

			echo "<td bgcolor='#FFFFFF'>";
			//HD 86436
			echo "<acronym title='Quantidade não pode ser preenchida com número decimal.'>
			<input type='text' name='qtde_$i' value='$qtde' size='5' maxlength=''";
			if($login_fabrica == 14 and $ativo == 'f' and strlen($ativo) > 0){ echo " readonly='readonly' "; }
			echo ">
			</acronym>
			&nbsp";
			echo "</td>";
			echo "<td>";
			echo "<img src='../imagens/btn_apaga_15.gif' alt='Clique aqui para apagar este item da lista básica.' onclick='document.frm_lbm.btn_acao.value = \"apagar\" ; document.frm_lbm.apagar.value = \"$lbm\" ; document.frm_lbm.submit()' style='cursor:pointer'><br>&nbsp;";
			echo "</td>";

//  HD 113942 - Adicionar Lenoxx às fábricas que tem a imagem das peças na lista básica (cadastro, pesquisa e consulta)
			if ($login_fabrica == 6 or $login_fabrica == 11 or $login_fabrica == 45 or $login_fabrica == 50) {



	            $xpecas = $tDocs->getDocumentsByRef($peca, "peca");
	            if (!empty($xpecas->attachListInfo)) {

					$a = 1;
					foreach ($xpecas->attachListInfo as $kFoto => $vFoto) {
					    $fotoPeca = $vFoto["link"];
					    if ($a == 1){break;}
					}
					echo "<td bgcolor='#FFFFFF'>";
					echo "<img src='$fotoPeca'>";
					echo "</td>";
	            } else {

					$caminho = $PHP_SELF."/".$login_fabrica;
					$teste = explode('/', $PHP_SELF, -1);
					$caminho = "../imagens_pecas/$login_fabrica";
					$diretorio_verifica=$caminho."/pequena/";
					if(is_dir($diretorio_verifica) == true){
						$contador=0;
						if ($dh = opendir($caminho."/pequena/")) {
							while (false !== ($filename = readdir($dh))) {
								if(strlen($xpeca) > 0) {
									if($contador == 1) break;
									if (strpos($filename,$xpeca) !== false){
										$po = strlen($xpeca);
										if(substr($filename, 0,$po)==$xpeca){
											$contador++;
											echo "<td bgcolor='#FFFFFF'>";
											echo "<img src='$caminho/pequena/$filename'>";
											echo "</td>";
										}
									}
								}
							}

							if($contador == 0 AND strlen($peca) >0 ){
								if ($dh = opendir($caminho."/pequena/")) {
									while (false !== ($filename = readdir($dh))) {
										if($contador == 1) break;
										if (strpos($filename,$peca) !== false){
											$po = strlen($peca);
											if(substr($filename, 0,$po)==$peca){
												$contador++;
												echo "<td bgcolor='#FFFFFF'>";
												echo "<img src='$caminho/pequena/$filename'>";
												echo "</td>";
											}
										}
									}
								}
							}
						}
					}

				}
			}
			echo "</tr>";
		}


	?>
	</table>

	<p align='center'><img src='imagens_admin/btn_gravar.gif' onclick='document.frm_lbm.btn_acao.value = "gravar" ; document.frm_lbm.submit()'></p>
	<a href="javascript: if (confirm ('Deseja realmente excluir todos os itens desta Lista Básica ?') == true ) { window.location = '<? echo $PHP_SELF; ?>?produto=<? echo $produto; ?>&acao=excluir'}" >Excluir esta Lista Básica</a>
	<br>
	<br>

	<? if ($login_fabrica == 1 or $login_fabrica == 51) { ?>
	<fieldset style="width: 500">
		<legend align="center"><font face='arial' size='+1' color='#000000'><b>Duplicar Lista Básica para Type</b></font></legend>
		<br>
		<table width='400' align='center' border='0'>
			<tr>
				<td align='center'>
					<b>Type Origem</b>
				</td>
				<td align='center'>
					<b>Type Destino</b>
				</td>
			</tr>
			<tr>
				<td align='center'>
				    <? 
				     GeraComboType::makeComboType($parametrosAdicionaisObject, $type_duplicar_origem, "type_duplicar_origem");
      				     echo GeraComboType::getElement();
				    ?>
					
				</td>
				<td align='center'>
				    <? 
				     GeraComboType::makeComboType($parametrosAdicionaisObject, $type_duplicar_destino, "type_duplicar_destino");
      				     echo GeraComboType::getElement();
				    ?>
					
				</td>
			</tr>
		</table>
		<br>
		<center><img src='imagens_admin/btn_duplicar.gif' style="cursor: hand;" onclick='document.frm_lbm.btn_acao.value = "DuplicarType" ; document.frm_lbm.submit()'></center>
		<br>
	</fieldset>
	<br>
	<? } ?>

	<!-- ---------------------- Duplicar Lista Básica ---------------------- -->

	<fieldset style="width: 500">
		<legend align="center"><font face='arial' size='+1' color='#000000'><b>Duplicar Lista Básica para produto</b></font></legend>
		<br>
		<table width='400' align='center' border='0'>
			<tr>
				<td align='center'>
					<b>Referência</b>
				</td>
				<td align='center'>
					<b>Descrição</b>
				</td>
			</tr>
			<tr>
				<td align='center' nowrap>
					<input type="text" name="referencia_duplicar" value="<? echo $referencia_duplicar ?>" size="15" maxlength="20" <? if ($login_fabrica == 5) { ?> onblur="javascript: fnc_pesquisa_produto (document.frm_lbm.referencia_duplicar,document.frm_lbm.descricao_duplicar,'referencia')" <? } ?>>&nbsp;<img src='../imagens/btn_buscar5.gif' style="cursor:pointer" border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_produto (document.frm_lbm.referencia_duplicar,document.frm_lbm.descricao_duplicar,'referencia')">
				</td>
				<td align='center' nowrap>
					<input type="text" name="descricao_duplicar" value="<? echo $descricao_duplicar ?>" size="50" maxlength="50" <? if ($login_fabrica == 5) { ?> onblur="javascript: fnc_pesquisa_produto (document.frm_lbm.referencia_duplicar,document.frm_lbm.descricao_duplicar,'descricao')" <? } ?>>&nbsp;<img src='../imagens/btn_buscar5.gif' style="cursor:pointer" border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_produto (document.frm_lbm.referencia_duplicar,document.frm_lbm.descricao_duplicar,'descricao')">
				</td>
			</tr>
		</table>
		<br>
		<center><img src='imagens_admin/btn_duplicar.gif' style="cursor: hand;" onclick='document.frm_lbm.btn_acao.value = "duplicar" ; document.frm_lbm.submit()'></center>
		<br>
	</fieldset>

	<?
	}
}
?>

</form>
</div>
<?
	include "rodape.php";
?>

</body>
</html>
