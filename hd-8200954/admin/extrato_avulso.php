<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="financeiro";
include 'autentica_admin.php';
include_once "funcoes.php";
$msg_sucesso = $_GET['msg'];
$msg_erro = "";
$tDocs = new TDocs($con,$login_fabrica,'avulso');
if ($_POST["ajax_anexo_upload"] == true) {
    $posicao = $_POST["anexo_posicao"];
    $chave   = $_POST["anexo_chave"];
    $arquivo = $_FILES["anexo_upload_{$posicao}"];
    $ext = strtolower(preg_replace("/.+\./", "", $arquivo["name"]));
    if (strlen($arquivo['tmp_name']) > 0) {
            if ($_FILES["anexo_upload_{$posicao}"]["tmp_name"]) {
                $anexoID      = $tDocs->sendFile($_FILES["anexo_upload_{$posicao}"]);
                $arquivo_nome = json_encode($tDocs->sentData);
                if (!$anexoID) {
                    $retorno = array('error' => utf8_encode('Erro ao anexar arquivo'),'posicao' => $posicao);
                } 
            }
            if (empty($anexoID)) {
                $retorno = array('error' => utf8_encode('Erro ao anexar arquivo'),'posicao' => $posicao);
            }
            $link = '//api2.telecontrol.com.br/tdocs/document/id/'.$anexoID;
            $href = '//api2.telecontrol.com.br/tdocs/document/id/'.$anexoID;
            $tdocs_id = $anexoID;
            if (!strlen($link)) {
                $retorno = array('error' => utf8_encode(' 2'),'posicao' => $posicao);
            } else {
                $retorno = compact('link', 'arquivo_nome', 'href', 'ext', 'posicao','tdocs_id');
            }
    } else {
        $retorno = array('error' => utf8_encode('Erro ao anexar arquivo'),'posicao' => $posicao);
    }
    exit(json_encode($retorno));
}
if ($_POST["ajax_remove_anexo"] == true) {
    $posicao    = $_POST["posicao"];
    $tdocs_id   = $_POST["tdocsid"];
    $tDocs->setContext('avulso');
    $anexoID = $tDocs->deleteFileById($tdocs_id);
    if (!$anexoID) {
        $retorno = array('erro' => true, 'msg' => utf8_encode('Erro ao remover arquivo'),'posicao' => $posicao);
    }  else {
        $retorno = array('sucesso' => true, 'posicao' => $posicao);
    }
    exit(json_encode($retorno));
}
if(filter_input(INPUT_POST,"ajax")){
    $sua_os             = filter_input(INPUT_POST,"os");
    $posto_codigo   = filter_input(INPUT_POST,"posto_codigo");
    $pos = strpos($os, "-");
    if ($pos === false) {
        //hd 47506
        if(strlen ($sua_os) > 11){
            $pos = strlen($sua_os) - (strlen($sua_os)-5);
        } elseif(strlen ($sua_os) > 10) {
            $pos = strlen($sua_os) - (strlen($sua_os)-6);
        } elseif(strlen ($sua_os) > 9) {
            $pos = strlen($sua_os) - (strlen($sua_os)-5);
        }else{
            $pos = strlen($sua_os);
        }
    }else{
        //hd 47506
        if(strlen (substr($sua_os,0,$pos)) > 11){#47506
            $pos = $pos - 7;
        } else if(strlen (substr($sua_os,0,$pos)) > 10) {
            $pos = $pos - 6;
        } elseif(strlen ($sua_os) > 9) {
            $pos = $pos - 5;
        }
    }
    if(strlen ($sua_os) > 9) {
        $xsua_os = substr($sua_os, $pos,strlen($sua_os));
        $codigo_posto = substr($sua_os,0,5);
        $sqlPosto = "SELECT posto from tbl_posto_fabrica where codigo_posto = '$codigo_posto' and fabrica = $login_fabrica";
        $res = pg_query($con,$sqlPosto);
        $xposto =  pg_result($res,0,posto) ;
    }
		$sqlPri = "
			SELECT  tbl_posto_fabrica.codigo_posto
			FROM    tbl_posto_fabrica
			JOIN    tbl_os USING(posto,fabrica)
			WHERE   tbl_os.sua_os   = '$xsua_os'
			AND     tbl_os.posto    = $xposto
		";
		$resPri = pg_query($con,$sqlPri);
		$postoBuscado = pg_fetch_result($resPri,0,codigo_posto);
		if($postoBuscado != $posto_codigo){
			echo json_encode(array("erro" => utf8_encode("O posto digitado não corresponde com a OS")));
		}else{
			/**
			 * 2ª - Verificação se extrato existe e, se existir:
			 * 2.1 - Verifica sua aprovação
			 */
			$sqlEx = "
				SELECT  tbl_os.os                           ,
						tbl_os_extra.extrato                ,
						tbl_extrato.protocolo,
						tbl_extrato_financeiro.data_envio,
						tbl_extrato_lancamento.extrato_lancamento
				FROM    tbl_os
				JOIN    tbl_os_extra            USING(os)
		LEFT JOIN    tbl_extrato_financeiro  USING(extrato)
					LEFT JOIN tbl_extrato_lancamento ON tbl_extrato_lancamento.os = tbl_os.os AND tbl_extrato_lancamento.fabrica = {$login_fabrica}
					LEFT JOIN tbl_extrato ON tbl_extrato.extrato = tbl_os_extra.extrato AND tbl_extrato.fabrica = {$login_fabrica}
				WHERE   tbl_os.sua_os = '$xsua_os'
				AND     tbl_os.posto  = $xposto
			";
			$resEx              = pg_query($con,$sqlEx);
			$os_gravar          = pg_fetch_result($resEx,0,os);
			$os_extrato         = pg_fetch_result($resEx,0,extrato);
			$extrato_lancamento = pg_fetch_result($resEx,0,extrato_lancamento);
			$protocolo          = pg_fetch_result($resEx,0,protocolo);
			$os_ext_apr         = pg_fetch_result($resEx,0,data_envio);
			if(empty($os_extrato) && empty($extrato_lancamento)){
				echo json_encode(array("extrato"=>0,"os_gravar"=>$os_gravar));
			}else{
				if(strlen($extrato_lancamento) > 0 || strlen($os_extrato) > 0){
					if(empty($protocolo)){
						$protocolo = $os_extrato;
					}
					echo json_encode(array(
							"extrato"        => $protocolo,
							"status_extrato" => 2,
							"os_gravar"     =>$os_gravar
						)
					);
					exit;
				}
				if(strlen($os_ext_apr) == 0){
					echo json_encode(array("extrato"=>$os_extrato,"aprovado"=>0,"os_gravar"=>$os_gravar));
				}else{
					echo json_encode(array("extrato"=>$os_extrato,"aprovado"=>1,"os_gravar"=>$os_gravar));
				}
			}
		}
		exit;
}
if($_GET["ajax"]){
	
		try {
			$term = (!empty($_GET['term'])) ? $_GET['term'] : $_GET['os'];
			$posto_codigo = $_GET['posto_codigo'];
			
			if (empty($term) || empty($posto_codigo)) {
				exit;
			}
			
			$sqlPosto = "
				SELECT posto FROM tbl_posto_fabrica WHERE fabrica = {$login_fabrica} AND codigo_posto = '{$posto_codigo}'
			";
			$resPosto = pg_query($con, $sqlPosto);
			
			if (!pg_num_rows($resPosto)) {
				exit;
			}
			
			$posto = pg_fetch_result($resPosto, 0, 'posto');
			
			$sql = "
				SELECT o.os, o.sua_os, sc.descricao AS status
				FROM tbl_os o
				INNER JOIN tbl_status_checkpoint sc ON sc.status_checkpoint = o.status_checkpoint
				WHERE o.fabrica = {$login_fabrica} 
				AND o.posto = {$posto}
				AND o.sua_os LIKE '{$term}%'
			";
			$res = pg_query($con, $sql);
			
			$resultado = array();
			
			while ($row = pg_fetch_object($res)) {
				$resultado[] = array(
					'desc'   => $row->sua_os,
					'status' => utf8_encode($row->status)
				);
			}
			
			exit(json_encode($resultado));
		} catch (\Exception $e) {
			exit;
		}
}
function retira_acentos( $texto ){
 $array1 = array("á", "à", "â", "ã", "ä", "é", "è", "ê", "ë", "í", "ì", "î", "ï", "ó", "ò", "ô", "õ", "ö", "ú", "ù", "û", "ü", "ç" , "Á", "À", "Â", "Ã", "Ä", "É", "È", "Ê", "Ë", "Í", "Ì", "Î", "Ï", "Ó", "Ò", "Ô", "Õ", "Ö", "Ú", "Ù", "Û", "Ü", "Ç" );
 $array2 = array("a", "a", "a", "a", "a", "e", "e", "e", "e", "i", "i", "i", "i", "o", "o", "o", "o", "o", "u", "u", "u", "u", "c" , "A", "A", "A", "A", "A", "E", "E", "E", "E", "I", "I", "I", "I", "O", "O", "O", "O", "O", "U", "U", "U", "U", "C" );
 return str_replace( $array1, $array2, $texto );
}
if (strlen($_POST["btn_acao"]) > 0) $btn_acao = trim(strtolower($_POST["btn_acao"]));
if (strlen($_POST["extrato"]) > 0) $extrato = $_POST["extrato"];
if (strlen($_GET["extrato"]) > 0) $extrato = $_GET["extrato"];
if (strlen($_POST["posto"]) > 0) $posto = $_POST["posto"];
if (strlen($_GET["posto"]) > 0) $posto = $_GET["posto"];
if (strlen($_POST["posto_codigo"]) > 0) $posto_codigo = $_POST["posto_codigo"];
if (strlen($_GET["posto_codigo"]) > 0) $posto_codigo = $_GET["posto_codigo"];
if (strlen($_POST["posto_nome"]) > 0) $posto_nome = $_POST["posto_nome"];
if (strlen($_GET["posto_nome"]) > 0) $posto_nome = $_GET["posto_nome"];
if (strlen($_POST["marca"]) > 0) $marca = $_POST["marca"];
if (strlen($_GET["marca"]) > 0) $marca = $_GET["marca"];
if (strlen($_POST["lista_lancamento"]) > 0) $lista_lancamento = $_POST["lista_lancamento"];
if (strlen($_GET["lista_lancamento"]) > 0) $lista_lanamento = $_GET["lista_lancamento"];
if (strlen($_POST["total_lanca"]) > 0) $total_lanca = $_POST["total_lanca"];
if (strlen($_GET["total_lanca"]) > 0) $total_lanca = $_GET["total_lanca"];
$gera_extrato = filter_input(INPUT_POST,'gera_extrato');
if ($btn_acao == 'gravar'){
	if(strlen($posto) == 0 and strlen($posto_codigo) > 0){
		$sql = "SELECT posto
				FROM   tbl_posto_fabrica
				WHERE  tbl_posto_fabrica.codigo_posto = '$posto_codigo'
				AND    tbl_posto_fabrica.fabrica      = $login_fabrica;";
		$res = pg_query($con,$sql);
		if (pg_num_rows($res)) {
			$posto = pg_fetch_result($res,0,posto);
		}
		else {
			$msg_erro = "<br>".traduz("Posto")."&nbsp;" . $posto_codigo . traduz(" não encontrado");
		}
	}
	if(strlen($posto) == 0 && strlen($posto_codigo) == 0) {
		$msg_erro = "<br>Selecione o posto para efetuar os lançamentos";
	}
	if(empty($marca) AND $login_fabrica == 104 AND empty($extrato)){
		$msg_erro = "<br>".traduz("Selecione o posto para efetuar os lançamentos");
	}
	for($i = 0; $i < $total_lanca; $i++) {
		if($login_fabrica != 1){
			$_POST['valor_'.$i] = ((float) $_POST['valor_'.$i] == 0) ? null : $_POST['valor_'.$i];
		}		
		//Se veio alguma informação na linha do lançamento
		if (!empty($_POST["lancamento_".$i]) and (!empty($_POST['valor_'.$i]))) {
			//Se algum dos campos não veio preenchido
			if ($_POST["lancamento_".$i] == "" || $_POST["valor_".$i] == "") {
				$msg_erro .= "<br>".traduz("Para efetuar um lançamento, preencha todos os campos da linha do lançamento");
			}
			/* 
			else if ($_POST["produto_referencia_$i"] == "" && $login_fabrica==20) {
				$msg_erro .= "<br>Para efetuar um lançamento, preencha a referencia do produto";
			} */
		}
	}

    if (strlen($msg_erro) == 0) {
        $res = pg_query($con,"BEGIN TRANSACTION");
        if((in_array($login_fabrica, [81]) && $_POST['proximo_extrato']) || (in_array($login_fabrica, [20]) && $_POST['lista_lancamento'] == 'listar')){
            $prox_extrato = TRUE;
        }else{
            $prox_extrato = FALSE;
        }
        if($login_fabrica == 1 && $gera_extrato == "gerar"){
            $prox_extrato = FALSE;
		}elseif($login_fabrica == 1) {
			$prox_extrato = TRUE;
		}
		//lenoxx não fecha o extrato no ato do lançamento avulso (entra no proximo extrato)
		//takashi hd 9482 $login_fabrica <> 45  13/12/07
		if ((!in_array($login_fabrica,array(3,5,7,11,20,30,45,50,51,59,80,85,90,94,99,104,172))) and (strlen($extrato) == 0) and $prox_extrato !== TRUE AND $login_fabrica < 81){
			# HD 111271
			if($login_fabrica == 20) {
				$liberado_campo = ",liberado_telecontrol";
				$liberado_valor = ",current_timestamp";
			}
			$sql = "INSERT INTO tbl_extrato (
						posto    ,
						fabrica  ,
						total    ,
						aprovado
						$liberado_campo
					) VALUES (
						$posto            ,
						$login_fabrica    ,
						0                 ,
						current_timestamp
						$liberado_valor
					)";
// 					exit(nl2br($sql));
			$res = pg_query ($con,$sql);
			if (strlen(pg_errormessage ($con)) > 0) $msg_erro .= "<br>" . pg_errormessage ($con);
			if (strlen($msg_erro) == 0){
				$sql = "SELECT CURRVAL ('seq_extrato')";
				$res = pg_query ($con,$sql);
				$extrato = pg_fetch_result ($res,0,0);
				if (strlen(pg_errormessage ($con)) > 0) $msg_erro .= "<br>" . pg_errormessage ($con);
			}
		}
		if (strlen($extrato) == 0) $extrato = 'null';
		if($login_fabrica==1 AND strlen($extrato) > 0 AND $extrato <> "null"){//HD 46333
			$sqlA = "SELECT aprovado
					 FROM tbl_extrato
					 WHERE fabrica = $login_fabrica
					 AND   extrato = $extrato";
			$resA = pg_query($con, $sqlA);
			if(pg_numrows($resA)>0) $data_aprovado = pg_fetch_result($resA, 0, aprovado);
		}
		$sql = "UPDATE tbl_extrato SET aprovado = null
				WHERE  extrato = $extrato
				AND    fabrica = $login_fabrica";
		$res = @pg_query ($con,$sql);
		if (strlen(pg_errormessage ($con)) > 0) $msg_erro .= "<br>" . pg_errormessage ($con);
		for ($i = 0 ; $i < $total_lanca ; $i++) {
			$extrato_lancamento = $_POST ['extrato_lancamento_' . $i];
			$lancamento         = $_POST ['lancamento_' . $i] ;
			$historico          = $_POST ['historico_' . $i] ;
			$valor              = $_POST ['valor_' . $i] ;
			$ant_valor          = $_POST ['ant_valor_' . $i] ;
			$produto_referencia	= $_POST ['produto_referencia_' . $i] ;
			$os					= $_POST ['os_' . $i] ;
            $extrato_gravar     = filter_input(INPUT_POST,'extrato_avulso_'.$i);
            $os_gravar          = filter_input(INPUT_POST,'os_gravar_'.$i);
            $anexo              = $_POST['anexo'];
			//HD 157052: Acrescentado seleção de produto para o lançamento
			//A partir do produto o sistema buscará as informacoes tbl_familia.bosch_cfa e tbl_produto.origem
			//para preencher respectivamente os campos tbl_extrato_lancamento.bosch_cfa e tbl_extrato_lancamento.conta_garantia
			if ($produto_referencia) {
				$sql = "
				SELECT
				tbl_produto.produto,
				tbl_produto.origem AS conta_garantia,
				tbl_familia.bosch_cfa
				FROM
				tbl_produto
				JOIN tbl_familia ON tbl_produto.familia=tbl_familia.familia
				WHERE
				tbl_familia.fabrica=$login_fabrica
				AND tbl_produto.referencia='$produto_referencia'
				";
				$res_produto_contas = pg_query($con, $sql);
				$produto = pg_fetch_result($res_produto_contas, 0, produto);
				$conta_garantia = pg_fetch_result($res_produto_contas, 0, conta_garantia);
				$bosch_cfa = pg_fetch_result($res_produto_contas, 0, bosch_cfa);
			} else {
				$produto = "NULL";
				$conta_garantia = "";
				$bosch_cfa = "";
			}
                	if ($login_fabrica == 158) {
                    		$conta_garantia = $_POST["conta_garantia_$i"];
			}
			// HD 11015 Paulo
			if($login_fabrica == 3) {
				$competencia_futura = $_POST['competencia_futura_' . $i];
				$competencia_futura = str_replace (" " , "" , $competencia_futura);
				$competencia_futura = str_replace ("-" , "" , $competencia_futura);
				$competencia_futura = str_replace ("/" , "" , $competencia_futura);
				$competencia_futura = str_replace ("." , "" , $competencia_futura);
				if (strlen ($competencia_futura) > 0) {
					$competencia_futura = "'".substr ($competencia_futura,2,4) . "-" . substr ($competencia_futura,0,2) . "-01" ."'";
					$sql="SELECT $competencia_futura::date > current_date ";
					$res=pg_query($con,$sql);
					$data_competencia=pg_fetch_result($res,0,0);
					if($data_competencia == 'f') {
						$msg_erro = "<br>A Data de Competência Deveria Ser Maior Que A Data Atual";
					}
				}else {
					$competencia_futura = 'null';
				}
			} else {
				$competencia_futura = 'null';
			}
			//HD 11015 ^
			if (strlen($extrato_lancamento) > 0 AND strlen($lancamento) == 0 AND strlen($historico) == 0 AND strlen($valor) == 0){
				$sql = "DELETE FROM tbl_extrato_lancamento
						WHERE  extrato_lancamento = $extrato_lancamento;";
				$res = @pg_query($con,$sql);
				if (strlen(pg_errormessage ($con)) > 0) $msg_erro .= "<br>" . pg_errormessage($con);
			}
			if (strlen($lancamento) > 0 OR strlen($historico) > 0 OR strlen($valor) > 0){
				if (strlen($valor) == 0)
					$msg_erro .= "<br>".traduz("Informe o Valor");
				else
					$xvalor = trim($valor);
				if (strlen($lancamento) == 0)
					$msg_erro .= "<br>".traduz("Informe a Descrição do Lançamento");
				else
					$xlancamento = "'".trim($lancamento)."'";
				if (strlen($historico) == 0)
					$xhistorico = 'null';
				else
					$xhistorico = "'". trim(pg_escape_string($historico)) ."'";
				$total_ant_valor += $ant_valor;
				if (strlen($msg_erro) == 0) {
					if($login_fabrica == 104) {
						$marca_campo = ",marca";
						$marca_valor = ",$marca";
					}
					$sql = "SELECT debito_credito FROM tbl_lancamento WHERE lancamento = $lancamento and fabrica = $login_fabrica;";
					$resL = @pg_query($con, $sql);
					$debito_credito = @pg_fetch_result($resL,0,debito_credito);
					$sql = "SELECT fnc_limpa_moeda('$xvalor');";
					$resM = @pg_query($con, $sql);
					$xvalor = @pg_fetch_result($resM,0,0);
					// HD 682005 - A pedido do waldir alterei pra todas as fabricas
					if( $debito_credito == 'D' && empty($_POST['extrato']) && $login_fabrica == 35  ) { // HD 675867
						$msg_erro = traduz('Para lançar um avulso de débito, acesse a tela de manutenção de extratos');
						$validar = TRUE;
					}
					if ($debito_credito == 'D') $xvalor = '-'.$xvalor;
					if(strlen($os) > 0 && empty($msg_erro) && in_array($login_fabrica, array(1, 148, 152, 154, 157, 175, 180, 181, 182))) {
						$aux_os      = $os;
						$original_os = $os;
						if($login_fabrica == 1){
							$len = strlen($os) - 7;
    						$aux_os = substr($os, -7);
						}
						$sql = "SELECT os, sua_os
								from tbl_os
								where fabrica = $login_fabrica
								and posto = $posto
								and (os = $os or sua_os='$aux_os');";
						$res = pg_query($con,$sql);
						if(pg_num_rows($res) == 0) {
							$msg_erro =" OS $os não existe no sistema";
						}else{ 
							$os         = pg_fetch_result($res,0,0);
							$sua_os     = pg_fetch_result($res,0,1);
							$xhistorico = "'$historico - Referente a OS $aux_os '";
						}
					}else{
						$os = "null";
					}
                    if($login_fabrica == 1){
                    	if(empty($os)){
                    		$os = "null";
                    	}
                        $extrato    = ($extrato_gravar != 0)    ? $extrato_gravar   : (!empty($extrato)) ? $extrato : "null";
                    }
					//HD 157052: Incluído o campo conta_garantia, bosch_cfa e produto
					if (strlen ($extrato_lancamento) == 0 && strlen($msg_erro) == 0 ) {
						$sql = "INSERT INTO tbl_extrato_lancamento (
									posto              ,
									fabrica            ,
									extrato            ,
									lancamento         ,
									debito_credito	   ,
									historico          ,
									valor              ,
									admin              ,
									competencia_futura ,
									conta_garantia     ,
									bosch_cfa          ,
									produto            ,
									os
									$marca_campo
								) VALUES (
									$posto             ,
									$login_fabrica     ,
									$extrato           ,
									$xlancamento       ,
									'$debito_credito'  ,
									$xhistorico        ,
									'$xvalor'          ,
									$login_admin       ,
									$competencia_futura,
									'$conta_garantia'  ,
									'$bosch_cfa'       ,
									$produto           ,
									$os
									$marca_valor
								) RETURNING extrato_lancamento";
								// exit(nl2br($sql));
					} else if (strlen($msg_erro) == 0){
						$sql = "UPDATE tbl_extrato_lancamento SET
									lancamento         = $xlancamento          ,
									debito_credito     = '$debito_credito'	   ,
									historico          = $xhistorico           ,
									valor              = '$xvalor'             ,
									competencia_futura = $competencia_futura   ,
									conta_garantia     = '$conta_garantia'     ,
									bosch_cfa          = '$bosch_cfa'          ,
									produto            = $produto
								WHERE extrato_lancamento = $extrato_lancamento;";
					}
					$res = @pg_query($con,$sql);
					if (strlen(pg_errormessage ($con)) > 0) $msg_erro .= "<br>" . pg_errormessage($con);
					if (empty($msg_erro) && in_array($login_fabrica, [20])) {
						$extrato_lancamento_tdocs = (empty($extrato_lancamento)) ? pg_fetch_result($res, 0, 'extrato_lancamento') : $extrato_lancamento;
						if (!empty($anexo[$i])) {
							$dadosAnexo = json_decode($anexo[$i], 1);
		                    $anexoID = $tDocs->setDocumentReference($dadosAnexo, $extrato_lancamento_tdocs, "anexar", false, "avulso");
		                    if (!$anexoID) {
		                        $msg_erro .= 'Erro ao fazer upload do anexo';
		                    }
	                	}
                	}
				}
			}//fim ifao
		}//for 
		# HD 111271
		/*
		$arquivo = isset($_FILES["arquivo"]) ? $_FILES["arquivo"] : FALSE;
		if (strlen ($msg_erro) == 0 and strlen($extrato) > 0) {
			$config["tamanho"] = 2048000; // Tamanho máximo do arquivo (em bytes)
			if (strlen($arquivo["tmp_name"]) > 0 && $arquivo["tmp_name"] != "none"){
				// Verifica o mime-type do arquivo
				if (!preg_match("/\/(zip|x-zip|x-zip-compressed|x-compress|x-compressed|pdf|msword|doc|word|x-msw6|x-msword|pjpeg|jpeg|png|gif|bmp|msexcel|xls|vnd.ms-excel|richtext|plain|html)$/", $arquivo["type"])){
					$msg_erro .= "<br>Arquivo em formato inválido!";
				} else { // Verifica tamanho do arquivo
					if ($arquivo["size"] > $config["tamanho"])
						$msg_erro .= "<br>Arquivo tem tamanho muito grande! Deve ser de no máximo 2MB. Envie outro arquivo.";
				}
				if (strlen($msg_erro) == 0) {
					// Pega extensão do arquivo
					preg_match("/\.(pdf|doc|gif|bmp|png|jpg|jpeg|rtf|xls|txt|zip){1}$/i", $arquivo["name"], $ext);
					$aux_extensao = "'".$ext[1]."'";
					$arquivo["name"]=retira_acentos($arquivo["name"]);
					$nome_sem_espaco = implode("", explode(" ",$arquivo["name"]));
					$nome_anexo = "/www/assist/www/admin/documentos/" . $extrato."-".strtolower ($nome_sem_espaco);
					if (strlen($msg_erro) == 0) {
						if (copy($arquivo["tmp_name"], $nome_anexo)) {
						}else{
							$msg_erro .= "<br>Arquivo não foi enviado!!!";
						}
					}
				}
			}
		} */
		if (isset($novaTelaOs) && empty($msg_erro) && !empty($extrato) && $extrato != "null" && !in_array($login_fabrica, [35]) ) {
			$sql = "SELECT SUM(valor) AS valor
					FROM tbl_extrato_lancamento
					WHERE extrato = $extrato
					AND fabrica = $login_fabrica";
			$res = pg_query($con, $sql);
			$valor_total = pg_fetch_result($res, 0, "valor");
			$sql = "UPDATE tbl_extrato SET avulso = $valor_total WHERE fabrica = $login_fabrica AND extrato = $extrato";
			$res = pg_query($con, $sql);
			$sql = "
				SELECT
				CASE WHEN tbl_extrato.protocolo != 'GARANTIA' AND tbl_os.fabrica = 158 then tbl_extrato.mao_de_obra ELSE SUM(tbl_os.mao_de_obra) END AS total_mo,
	                    		SUM(tbl_os.qtde_km_calculada) as total_km,
	                    		SUM(tbl_os.pecas) as total_pecas,
	                    		SUM(tbl_os.valores_adicionais) as total_adicionais,
	                    		tbl_extrato.avulso
	                	FROM tbl_os
	                	JOIN tbl_os_extra USING(os)
				JOIN tbl_extrato ON tbl_extrato.extrato = tbl_os_extra.extrato
				JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_os.posto AND tbl_posto_fabrica.fabrica = {$login_fabrica}
	                	WHERE tbl_os_extra.extrato = {$extrato}
				GROUP BY tbl_extrato.avulso,tbl_os.fabrica,tbl_extrato.protocolo, tbl_extrato.mao_de_obra;
			";
	        $res = pg_query($con, $sql);
	        if (pg_num_rows($res) > 0) {
	            $total_mo         = pg_fetch_result($res, 0, "total_mo");
	            $total_km         = pg_fetch_result($res, 0, "total_km");
	            $total_pecas      = pg_fetch_result($res, 0, "total_pecas");
	            $total_adicionais = pg_fetch_result($res, 0, "total_adicionais");
	            $avulso           = pg_fetch_result($res, 0, "avulso");
	            if (!strlen($total_mo)) {
	                $total_mo = 0;
	            }
	            if (!strlen($total_km)) {
	                $total_km = 0;
	            }
	            if (!strlen($total_pecas)) {
	                $total_pecas = 0;
	            }
	            if (!strlen($total_adicionais)) {
	                $total_adicionais = 0;
	            }
	            if (!strlen($avulso)) {
	                $avulso = 0;
	            }
	            $total = $total_mo + $total_km + $total_pecas + $total_adicionais + $avulso;
	            if ($total <= 0) {
	            	$msg_erro = "O total do extrato não pode ser um valor negativo ou 0";
	            } else {
		            $sql = "UPDATE tbl_extrato SET
		                        total           = {$total},
		                        mao_de_obra     = {$total_mo},
		                        pecas           = {$total_pecas},
		                        deslocamento    = {$total_km},
		                        valor_adicional = {$total_adicionais}
		                    WHERE extrato = {$extrato}";
		            $res = pg_query($con, $sql);
		            if (pg_last_error($con)) {
		                $msg_erro = "Erro ao totalizar Extrato $extrato";
		            }
					if ($login_fabrica == 158) {
			            $sql = "SELECT codigo FROM tbl_extrato_agrupado WHERE extrato = {$extrato}";
			            $res = pg_query($con, $sql);
		            	$unidadeNegocio = pg_fetch_result($res, 0, codigo);
					    include_once "../classes/Posvenda/Fabricas/_158/Extrato.php";
					    $calcExtratoImbera = new ExtratoImbera($login_fabrica);
					    $calcExtratoImbera->calcula($extrato, $posto, $unidadeNegocio, $con);
					}
		        }
	        } else {
	            $msg_erro = "Erro ao totalizar Extrato $extrato";
	        }
		} else {
			if (strlen ($msg_erro) == 0) {
				$sql = "SELECT SUM (valor) AS valor
						FROM tbl_extrato_lancamento
						WHERE tbl_extrato_lancamento.extrato = $extrato
						AND   tbl_extrato_lancamento.fabrica = $login_fabrica;";
				$res3 = pg_query($con,$sql);
				$valor_total = pg_fetch_result($res3,0,valor);
				$valor_total = empty($valor_total) ? 0 : $valor_total;
				if($login_fabrica == 20) {
					$sql_avulso = " , avulso = $valor_total,mao_de_obra=0,pecas=0 ";
				}
				$sql = "UPDATE tbl_extrato SET
								total =  $valor_total - $total_ant_valor + total
								$sql_avulso
						WHERE extrato = $extrato;";
				$res5 = pg_query($con,$sql);
			}
			if ( (strlen ($msg_erro) == 0) and ($extrato <> 'null') ) {
                if (isset($novaTelaOs)) {
                    include "../classes/Posvenda/Extrato.php";
				    $calcExtrato = new Extrato($login_fabrica,$extrato);
				    if ($login_fabrica == 158) {
						$sql = "SELECT codigo FROM tbl_extrato_agrupado WHERE extrato = {$extrato}";
						$res = pg_query($con, $sql);
						$unidadeNegocio = pg_fetch_result($res, 0, codigo);
					    include_once "../classes/Posvenda/Fabricas/_158/Extrato.php";
					    $calcExtratoImbera = new ExtratoImbera($login_fabrica);
					    $calcExtratoImbera->calcula($extrato,$posto, $unidadeNegocio, $con);
				    } else {
					    $calcExtrato->calcula();
				    }
                }else{
                    if(strlen($extrato) > 0 && $extrato != "null" && is_numeric($extrato)){
                        $sql = "SELECT fn_calcula_extrato($login_fabrica,$extrato);";
                        $res = @pg_query($con,$sql);
                    }
                }
                if (strlen(pg_errormessage ($con)) > 0) $msg_erro .= "<br>" . pg_errormessage($con);
                if (in_array($login_fabrica, array(20)) && strlen($msg_erro) == 0 && $_POST['lista_lancamento'] != 'listar') {
                    $sqlD = "SELECT fabrica FROM tbl_extrato_lancamento WHERE extrato = {$extrato};";
                    $resD = pg_query ($con,$sqlD);
                    $possui_debito = pg_fetch_result ($resD,0,0);
                    if ($possui_debito == 0) {
                        $msg_erro .= "Esse posto não possui saldo em extrato para debitar esse valor de avulso!";
                    }
                }
                if(strlen($msg_erro) == 0 AND strlen($data_aprovado) > 0 AND $login_fabrica == 1){//HD 46333
                    $sql = "UPDATE tbl_extrato SET aprovado = '$data_aprovado'
                            WHERE  extrato = $extrato
                            AND    fabrica = $login_fabrica";
                    $res = @pg_query ($con,$sql);
                    if (strlen(pg_errormessage ($con)) > 0) $msg_erro .= "<br>" . pg_errormessage ($con);
                }
                if(in_array($login_fabrica, [20])) {
                    $sql = "SELECT extrato,total FROM tbl_extrato 
		                    WHERE extrato = $extrato
		                    AND total > 0
		                    AND fabrica = $login_fabrica";
                    $res = pg_query($con,$sql);
                    if(pg_num_rows($res) == 0){
                        $msg_erro = "O total do extrato não pode ser negativo";
                    }
                }
                if(in_array($login_fabrica, [52])) {
                    $sql = "SELECT extrato,total FROM tbl_extrato WHERE extrato = $extrato AND fabrica = $login_fabrica";
                    $res = pg_query($con,$sql);
                    if(pg_num_rows($res) == 0){
                        $msg_erro = "O total do extrato não pode ser negativo";
                    }
                }
			}
		}
		if(((empty($_POST['excluir_avulso_negativo']) && !empty($_POST['extrato'])) or !empty($extrato)) and is_int($extrato) ) {
			$sql = "SELECT total FROM tbl_extrato WHERE extrato = $extrato AND fabrica = $login_fabrica";
			$res = pg_query($con,$sql);
			if(pg_num_rows($res) == 0) {
				$extrato_negativo = TRUE;
				$msg_erro = "O extrato está com débito igual ou negativo ao crédito e será excluído. ";
				$res = pg_query ($con,"ROLLBACK TRANSACTION");
			}
		}
        if (strlen ($msg_erro) == 0) {
			$res = pg_query ($con,"COMMIT TRANSACTION");
			//header ("Location: menu_financeiro.php");
			header ("Location: $PHP_SELF?msg=".traduz("Gravado com Sucesso!"));            
			exit;
		}else{
			if($extrato_negativo !== TRUE)
				unset($extrato);
			$res = pg_query ($con,"ROLLBACK TRANSACTION");
		}
	}
}
$layout_menu = "financeiro";
$title = "LANÇAMENTOS AVULSOS";
include "cabecalho_new.php";
$plugins = array(
	"autocomplete",
	"datepicker",
	"shadowbox",
	"mask",
	"dataTable",
	"price_format",
	"ajaxform",
	"fancyzoom"
);
include("plugin_loader.php");
?>

<p>
<script type="text/javascript" charset="utf-8">
	$(function(){
		$("input[rel='mascara_data']").mask("99/9999");
		$("div[id^=div_anexo_]").each(function(i) {
            var tdocs_id = $("#div_anexo_"+i).find(".btn-remover-anexo").data("tdocsid");
            if (tdocs_id != '' && tdocs_id != null && tdocs_id != undefined) {
                $("#div_anexo_"+i).find("button[name=anexar]").hide();
                $("#div_anexo_"+i).find(".btn-remover-anexo").show();
            } else {
                $("#div_anexo_"+i).find(".btn-remover-anexo").hide();
            }
        });
        /* REMOVE DE FOTOS */
        $(document).on("click", ".btn-remover-anexo", function () {
            var tdocsid = $(this).data("tdocsid");
            var posicao = $(this).data("posicao");
            if (tdocsid != '' && tdocsid != null && tdocsid != undefined) {
                $.ajax({
                    url: 'extrato_avulso.php',
                    type: "POST",
                    dataType:"JSON",
                    data: { 
                        ajax_remove_anexo: true,
                        tdocsid: tdocsid,
                        posicao: posicao
                    }
                }).done(function(data) {
                    if (data.erro == true) {
                        alert(data.msg);
                        return false;
                    } else {
                        alert("Removido com sucesso.");
                        $("#div_anexo_"+data.posicao).find("img.anexo_loading").hide();
                        $("#div_anexo_"+data.posicao).find("button[name=anexar]").show();
                        $("#div_anexo_"+data.posicao).find(".btn-remover-anexo").hide();
                        $("#div_anexo_"+data.posicao).find(".btn-remover-anexo").data("tdocsid", "");
                        $("#div_anexo_"+data.posicao).find("input[rel=anexo]").val("");
                        $("#div_anexo_"+data.posicao).find("img.anexo_thumb").attr("src", "imagens/imagem_upload.png");
                    }
                });
            }
        });
        /* ANEXO DE FOTOS */
        $("input[name^=anexo_upload_]").change(function() {
            var i = $(this).parent("form").find("input[name=anexo_posicao]").val();
            $("#div_anexo_"+i).find("button[name=anexar]").hide();
            $("#div_anexo_"+i).find("img.anexo_thumb").hide();
            $("#div_anexo_"+i).find("img.anexo_loading").show();
            $(this).parent("form").submit();
        });
        $("button[name=anexar]").click(function() {
            var posicao = $(this).attr("rel");
            $("input[name=anexo_upload_"+posicao+"]").click();
        });
        $("form[name=form_anexo]").ajaxForm({
            complete: function(data) {
                data = $.parseJSON(data.responseText);
	            if (data.error) {
	                alert(data.error);
	                $("#div_anexo_"+data.posicao).find("img.anexo_loading").hide();
	                $("#div_anexo_"+data.posicao).find("button[name=anexar]").show();
	                $("#div_anexo_"+data.posicao).find("img.anexo_thumb").show();
	            } else {
	                var imagem = $("#div_anexo_"+data.posicao).find("img.anexo_thumb").clone();
	               
	                if (data.ext == 'pdf') {
	                	$(imagem).attr({ src: "imagens/pdf_icone.png" });
	                } else if (data.ext == "doc" || data.ext == "docx") {
	                	$(imagem).attr({ src: "imagens/docx_icone.png" });
	                } else {
	                	$(imagem).attr({ src: data.link });
	                }
	                $("#div_anexo_"+data.posicao).find("img.anexo_thumb").remove();
	                var link = $("<a></a>", {
	                    href: data.href,
	                    target: "_blank"
	                });
	                $(link).html(imagem);
	                $("#div_anexo_"+data.posicao).prepend(link);
	                setupZoom();
	                $("#div_anexo_"+data.posicao).find("input[rel=anexo]").val(data.arquivo_nome);
	            }
	            $("#div_anexo_"+data.posicao).find("img.anexo_loading").hide();
	            $("#div_anexo_"+data.posicao).find("button[name=anexar]").hide();
	            $("#div_anexo_"+data.posicao).find(".btn-remover-anexo").show();
	            $("#div_anexo_"+data.posicao).find(".btn-remover-anexo").data("tdocsid", data.tdocs_id);
	            $("#div_anexo_"+data.posicao).find("img.anexo_thumb").show();
	        }
        /* FIM ANEXO DE FOTOS */
    	});
	});
	function fn_excluir_lancamento(extrato_lancamento) {
		if (confirm("Excluir o lançamento selecionado?")) {
			document.frm_extrato_avulso.lista_lancamento.value = 'listar';
			document.getElementById("excluir_lancamento").value = extrato_lancamento;
			document.frm_extrato_avulso.submit();
		}
	}
	function apenas_numeros(origem, decimais)
	{
			if (typeof decimais == "undefined") decimais = 2;               //SE NÃO FOR PASSADO PARÂMETRO EM decimais, DEFINE PADRÃO 2
			origem.value=origem.value.replace(/,/, ".");                    //PERMITE QUE O USUÁRIO DIGITE A VÍRGULA, MAS COLOCA PONTO NO LUGAR
			parts = origem.value.split(".", 2);                                             //CONSIDERANDO APENAS O PRIMEIRO SEPARADOR DECIMAL E DESCARTANDO DEMAIS
			if(parts.length == 2) parts[1] = parts[1].substr(0, decimais);
			origem.value = parts.join(".");
			if (origem.value == ".") origem.value = "0.";                   //CASO NÃO TENHAM NÚMEROS ANTES DO SEPARADOR DECIMAL, ACREDENTA ZERO
			origem.value=origem.value.replace(/[^0-9.]/gi, "");             //EXCLUINDO O QUE NÃO FOR NÚMEROS OU PONTO
	}
	function formatar_numeros(origem, decimais)
	{
			if (typeof decimais == "undefined") decimais = 2;               //SE NÃO FOR PASSADO PARÂMETRO EM decimais, DEFINE PADRÃO 2
			parts = origem.value.split(".", 2);                                             //CONSIDERANDO APENAS O PRIMEIRO SEPARADOR DECIMAL E DESCARTANDO DEMAIS
			if(parts[0].length == 0) parts[0] = "0";
			if(parts.length == 2)
			{
					parts[1] += "00";
					parts[1] = parts[1].substr(0, decimais);
			}
			else
			{
					parts[1] = "00";
			}
			origem.value = parts.join(".");
	}
</script>
<script language="JavaScript">
//HD 157052: Copiei esta funcao do os_cadastro.php para pesquisar produtos
function fnc_pesquisa_produto2(campo, campo2, tipo, voltagem) {
	if (tipo == "referencia" ) {
		var xcampo = campo;
	}
	if (tipo == "descricao" ) {
		var xcampo = campo2;
	}
	if (xcampo.value != "") {
		var url = "";
		url = "produto_pesquisa_2.php?campo=" + xcampo.value + "&tipo=" + tipo;
		janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=600, height=400, top=18, left=0");
		janela.referencia   = campo;
		janela.descricao    = campo2;
		janela.focus();
	}
}
$(function() {
	$("#tabela tr:even").css("background-color", "#F7F5F0");
	$("#tabela tr:odd").css("background-color", "#F1F4FA");
	$("#tabela tr:first-child").css( "background-color" , "#7092BE");
	$("#tabela tr:first-child").css( "color" , '#FFFFFF' );
	$("#tabela tr:last-child").css( "background-color", "#D9E2EF" );
	$.autocompleteLoad(Array("produto", "peca", "posto"));
	Shadowbox.init();
	$("span[rel=lupa]").click(function () {
		$.lupa($(this));
	});
<?
if($login_fabrica == 1 && (!isset($gera_extrato) || $gera_extrato == "gerar")){
?>
    $("input[name^=os_]").prop("disabled","disabled");
<?
}
if($login_fabrica == 1){
	?>
	$("input[name^=os_]").on("keyup",function(){
    	$("#btn_gravar").attr("hidden", true);
	});
	<?php
}
?>
    $("input[name=gera_extrato]").click(function(){
        var valor = $(this).val();
        if(valor == "gerar"){
            $("input[name^=os_]").val("").prop("disabled","disabled");
        } else if (valor == "programar"){
            $("input[name^=os_]").prop("disabled","");
        }
	});
	
	$('input[name^=valor_]').priceFormat({
		prefix: '',
		decimals: 2,
		thousandsSeparator: '',
		centsSeparator: '.'
	});
	
	<?php
	if (in_array($login_fabrica, array(152,157,175,180,181,182)) && !empty($_GET['extrato'])) {
	?>
		$("input[name^='os_']").each(function(){
			$(this).autocomplete({
				source: 'extrato_avulso.php',
				extraParams: { 
					ajax: 'os',
					posto_codigo: function() {
						<?php
						if (!empty($_GET['extrato'])) {
						?>
							return $('div.posto-codigo').text().trim();
						<?php
						} else {
						?>
							return $('input[name=posto_codigo]').val();
						<?php
						}
						?>
					}
				},
				select: function (event, ui) {
					$(this).val(ui.item["desc"]);
					return false;
				}
			}).data("uiAutocomplete")._renderItem = function (ul, item) {
				var text = item["desc"]+' - '+item["status"];
				return $("<li></li>").data("item.autocomplete", item).append("<a>"+text+"</a>").appendTo(ul);
			};
		});
	<?php
	}
	?>
});
function buscaOS(linha){
	var posto_codigo = $('#codigo_posto').val();
	var fabrica = <?=$login_fabrica;?> ;
	var linha = linha;
	if(posto_codigo == '' || posto_codigo == 'undefined') {
		alert('informe o código do posto para fazer a busca');
		return false;
	}
	
    var os = $("input[name=os_"+linha+"]").val();
    if(os != ""){
        $.ajax({
            url:"extrato_avulso.php",
            type:"GET",
            dataType:"JSON",
            data:{
                ajax:true,
                os:os,
                posto_codigo:posto_codigo
            }
        })
        .done(function(data){
            if(!data.erro){
                if(data.extrato != 0){
                    if(data.aprovado == 1){
                        alert("A OS Já se encontra em extrato aprovado pelo financeiro e não pode ser alterado");
                        $("input[name=os_"+linha+"]").val("");
                    } else if(data.status_extrato == 2){
                    	if(data.extrato != null){
	                    	alert("A OS "+os+" já pertence ao extrato "+data.extrato);
                    	}else{
                    		alert("A OS "+os+" já foi lançado em um extrato avulsto programado");
                    	}
                        $("input[name=os_"+linha+"]").val("");
                	}else {
                        $("input[name=extrato_avulso_"+linha+"]").val(data.extrato);
                        $("input[name=os_gravar_"+linha+"]").val(data.os_gravar);
                    }
                } else {
                    $("input[name=extrato_avulso_"+linha+"]").val(data.extrato);
                    $("input[name=os_gravar_"+linha+"]").val(data.os_gravar);
                }
            }else{
                alert(data.erro);
                $("input[name='os_"+linha+"']").val("");
            }
			$("#btn_gravar").attr("hidden", false);
        })
        .fail(function(){
            alert("Não foi possível encontrar a OS");
            $("input[name='os_"+linha+"']").val("");
			$("#btn_gravar").attr("hidden", false);
        });
    }
}
function retorna_posto(retorno){
    $("#codigo_posto").val(retorno.codigo);
	$("#descricao_posto").val(retorno.nome);
}
</script>
<?if (strlen ($msg_erro) > 0) {?>
	<?
	//HD 157052: Modificado para mostrar o erro no padrão dos outros programas
	if (strtoupper(substr($msg_erro, 0, 4)) == "<BR>") {
		$msg_erro = substr($msg_erro, 4);
	}
	$msg_erro = str_replace("ERROR", "", $msg_erro);
	?>
	<div class="alert alert-danger">
		<h4><?= $msg_erro; ?></h4>
	</div>
<? } else if ( strlen( $msg_sucesso ) > 0 ) {
?>
	<div class="alert alert-success">
		<h4><?= $msg_sucesso; ?></h4>
	</div>
<?php
}
if (strlen($posto) > 0){
	$sql = "SELECT tbl_posto.nome                ,
				   tbl_posto_fabrica.codigo_posto
			FROM   tbl_posto
			JOIN   tbl_posto_fabrica ON tbl_posto_fabrica.posto    = $posto
								     AND tbl_posto_fabrica.fabrica = $login_fabrica
			WHERE  tbl_posto.posto = $posto;";
	$res = pg_query($con,$sql);
	$posto_codigo       = @pg_fetch_result($res,0,codigo_posto);
	$posto_nome         = @pg_fetch_result($res,0,nome);
}
?>
<div class="row">
	<b class="obrigatorio pull-right">  * <?=traduz('Campos obrigatórios')?> </b>
</div>
<FORM class="form-inline form-search tc_formulario" METHOD='POST' NAME='frm_extrato_avulso' enctype="multipart/form-data">
<input type='hidden' name='btn_acao_clicou' id='btn_acao_clicou' value=''>
<input type='hidden' name='btn_acao' value=''>
<input type='hidden' name='excluir_avulso_negativo' value=''>
<input type='hidden' name='posto' value='<?echo $posto;?>'>
<input type='hidden' name='extrato' value='<?echo $extrato;?>'>
<div class="titulo_tabela"><?=traduz('Lançamento de Extrato Avulso')?></div>
	<br />
	<div class="row-fluid">
		<div class="span3"></div>
		<div class="span3">
			<div class="control-group <?=(strpos($msg_erro,"Selecione o posto") !== false) ? "error" : "" ?>">
        		<label class="control-label" for=''>&nbsp;<?=traduz('Código do Posto')?>:</label>
        		
				<?
				if ((strlen($_GET['extrato']) > 0) AND (strlen($_GET['posto']) > 0)){
				?>
				<div class='controls controls-row'>
		 			<div class='span12 alert-success posto-codigo'>
		           		<input type="hidden" id="codigo_posto" value="<?= $posto_codigo ?>">
		            	<?= $posto_codigo ?>
		            </div>
		        </div>
				<?
				}else{
				?>
		 		<div class='controls controls-row'>
		 			<div class='span12 input-append'>
		 				<h5 class='asteristico'>*</h5>
		           		<input class="controls inptc8" type="text" name="posto_codigo" id="codigo_posto" size="15" value="<?= $posto_codigo ?>">
		           		<span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
						<input type="hidden" name="lupa_config" tipo="posto" parametro="codigo" />
		           	</div>	
		        </div>    
 
				<?
				}
				?>
			</div>
        </div>
        <div class="span5">
			<div class="control-group <?=(strpos($msg_erro,"Selecione o posto") !== false) ? "error" : "" ?>">
        		<label class="control-label" for=''>&nbsp;<?=traduz('Nome do Posto')?>:</label>
				<?
				if ((strlen($_GET['extrato']) > 0) AND (strlen($_GET['posto']) > 0)){
				?>
				<div class='controls controls-row'>
		 			<div class='span12 alert-success'>
		            	<?= $posto_nome ?>
		            </div>
		        </div> 
				        
				<?
				    if($login_fabrica == 104){
				?>
				<div class='controls controls-row'>
		 				<div class='span7'>	
    
				<?
				            $marca_nome = mostraMarcaExtrato($extrato);
                ?>       
					           <label class="control-label" for=''>Grupo: <?= $marca_nome; ?> </label>
				<?
					            $sqlMarca = "SELECT marca FROM tbl_marca WHERE fabrica = $login_fabrica AND nome = '$marca_nome'";
					            $resMarca = pg_query($con,$sqlMarca);
				?>
					            <input type='hidden' name='marca' value='<?=pg_fetch_result($resMarca,0,0)?>'>
					    </div>	        
				    <div class="span4"></div>		
		        </div> 
				<?
				    }
				}else{
				?>
		        <div class='controls controls-row'>
		 			<div class='span12 input-append'>
		 				<h5 class='asteristico'>*</h5>
		            	<input class="controls" type="text" name="posto_nome" id="descricao_posto" size="35" value="<?= $posto_nome ?>" >
		            	<span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
						<input type="hidden" name="lupa_config" tipo="posto" parametro="nome" />
		            </div>
		        </div> 	
        
				<?
				    if($login_fabrica == 104){
				?>
				<br />
				<div class="row-fluid">				        
				        <label class="control-label" for=''><?=traduz('Grupo')?>:</label>
				<?
				        $sqlM = "
				            SELECT  tbl_marca.marca,
				                    tbl_marca.nome
				            FROM    tbl_marca
				            WHERE   tbl_marca.fabrica = $login_fabrica
				            AND     tbl_marca.nome in('DWT','OVD')
				      ORDER BY      tbl_marca.nome
				        ";
				        $resM = pg_query($con,$sqlM);
				?>
				        <div class="row-fluid">
				            <select name='marca' class='frm'>
				                <option value=''></option>
				<?
				        for($i = 0; $i < pg_num_rows($resM); $i++){
				            $marca_aux  = pg_fetch_result($resM,$i,'marca');
				            $nome_marca = pg_fetch_result($resM,$i,'nome');
				            $selected = ($marca == $marca_aux) ? "SELECTED" : "";
				?>
				                <option value='<?=$marca_aux?>' <?=$selected?>><?=$nome_marca?></option>
				<?
				        }
				?>
				            </select>
				        </div>    
				</div>            
				<?
				    }
				?>
				<?php
				    if(in_array($login_fabrica, [81]) && !isset($_GET['extrato']) ) {
				?>
					<br />
				            <input type="checkbox" name="proximo_extrato" id="proximo_extrato" <?= ($_POST['proximo_extrato']) ? 'checked' : '' ?> />
				            <label for="proximo_extrato" style="cursor:pointer;"><?=traduz('Lançar Avulso para próximo extrato')?></label>
				<?php
				    }
				?>
							</div>
						</div>
				<?
					if (in_array($login_fabrica,array(7,11,20,45,172))) {
				?>
				      <div class="row-fluid">
				      	<div class="span2"></div> 
					    <div class="span8 tac">
					    	<br />	
						    <input type="hidden" value="" name="lista_lancamento">
						    <a class="btn btn-info" href="javascript: document.frm_extrato_avulso.lista_lancamento.value='listar' ; document.frm_extrato_avulso.submit(); " ALT="Exibir lançamentos deste Posto que ainda não entraram em um extrato" border='0'><?=traduz('Exibir Lançamentos deste Posto')?></a>
					    </div>  
					    <div class="span2"></div>   
				      </div>   
				      <br />   
				   
				<?
				    }
				?>
				<?
				/*
				if($login_fabrica == 20 &&) { // HD  111271 ?> 
						<div class="row-fluid">
							<div class="span2"></div>
								<input class="span8" type='file' name='arquivo'>
							<div class="span2"></div>	
						</div>
				<?		
					} */
				}
				if($login_fabrica == 1){
				?>
				 <div class="row-fluid">
				 		<div class="span4"></div>
					            <div class="span2">
						            <label class="radio">
						            	<input type="radio" name="gera_extrato" value="gerar" <?=($gera_extrato == "gerar" or empty($gera_extrato)) ? "checked" : ""?> />Gerar Extrato
						            </label>	
					            </div>
					            <div class="span3">
						            <label class="radio">
						            	<input type="radio" name="gera_extrato" value="programar" <?=($gera_extrato == "programar") ? "checked" : ""?> />Programar Lançamento
						            </label>	
					            </div>	 
				        <div class="span3"></div>
				  </div>          
				  
				<?
				}
				?>
</div>
<br />
				<?
	if($login_fabrica == 3) {
		?>
	<div class="alert alert-warning">
		Informar a data de competência caso necessite definir o mês do pagamento. Avulsos sem a data de competência futura entrarão automaticamente no próximo fechamento de extrato.
	</div>
	<?
	}
	unset($res);
	//lancamentos com extrato
	//HD 157052: Acrescentado seleção de produto para o lançamento
	//A partir do produto o sistema buscará as informacoes tbl_familia.bosch_cfa e tbl_produto.origem
	//para preencher respectivamente os campos tbl_extrato_lancamento.bosch_cfa e tbl_extrato_lancamento.conta_garantia
	if ($login_fabrica == 85) {
        $cond_bonificacao = " AND (tbl_extrato_lancamento.descricao NOT ILIKE '%diferenciado' OR tbl_extrato_lancamento.descricao IS NULL)";
    }
	if (strlen($extrato) > 0){
		$sql = "SELECT	lancamento                ,
						historico                 ,
						valor                     ,
                        conta_garantia,
						extrato_lancamento        ,
						to_char(competencia_futura,'MM/YYYY') as competencia_futura,
						tbl_produto.referencia,
						tbl_produto.descricao,
						tbl_extrato_lancamento.os
				FROM	tbl_extrato_lancamento
						LEFT JOIN tbl_produto ON tbl_extrato_lancamento.produto=tbl_produto.produto
				WHERE	extrato = $extrato
				AND     fabrica = $login_fabrica
				$cond_bonificacao
				;";
		$res = pg_query ($con,$sql);
	//lançamentos sem extrato
	} elseif ( ($lista_lancamento == 'listar') and (strlen(trim($posto_codigo))>0) ) {
		if ($_POST["excluir_lancamento"]) {
			$sql = "DELETE FROM tbl_extrato_lancamento WHERE extrato_lancamento=" . $_POST["excluir_lancamento"];
			$res = pg_query($con, $sql);
		}
		$sql = "SELECT posto
				FROM  tbl_posto_fabrica
				WHERE codigo_posto = '$posto_codigo'
				AND   fabrica = $login_fabrica;";
		$resX = pg_query ($con,$sql);
		$lposto = @pg_fetch_result($resX,0,posto);
		if (pg_numrows($resX) > 0) {
			$sql = "SELECT	lancamento                ,
							historico                 ,
							valor                     ,
							extrato_lancamento        ,
							to_char(competencia_futura,'MM/YYYY') as competencia_futura,
							tbl_produto.referencia,
							tbl_produto.descricao,
							tbl_extrato_lancamento.os
					FROM  tbl_extrato_lancamento
						  LEFT JOIN tbl_produto ON tbl_extrato_lancamento.produto=tbl_produto.produto
					WHERE extrato isnull
					AND   fabrica = $login_fabrica
					AND   posto   = $lposto
					$cond_bonificacao
					ORDER BY data_lancamento;";
			$res = pg_query ($con,$sql);
		}
	}
	?>
</div>
<?
if ($lista_lancamento == 'listar' && $res && pg_num_rows($res) == 0) { ?>
	<TABLE width='700px' align='center' border='0' cellspacing='1' cellpadding='1'>
	<caption class='menu_top' border='0'>
		<div class="alert alert-warning">
			<h5><?=traduz('NENHUM LANÇAMENTO LOCALIZADO PARA ESTE POSTO')?></h5>
		</div>
		<form>
			<input class="btn" type="submit" value="VOLTAR" class="frm">
			<br /><br />
		</form>
	</caption>
	</TABLE>
	<?
} else {
?>
	<TABLE id='tabela' width='700px' align='center' class='formulario table table-striped table-bordered' cellspacing='1' cellpadding='1'>
	<THEAD>
	<?php
	if (in_array($login_fabrica, [20]) && $_POST['lista_lancamento'] == 'listar') { ?>
		<tr class="titulo_tabela">
			<th colspan="100%">Lançamentos Agendados para o Próximo Extrato do Posto</th>
		</tr>
	<?php
	}
	?>
	<tr class="titulo_coluna">
	<TH><?=traduz('Descrição')?></TH>
    <TH><?=traduz('Histórico')?></TH>
    <TH><?=traduz('Valor')?></TH>

	<?
	if (in_array($login_fabrica, [20])) { ?>
		<TH><?=traduz('Anexo')?></TH>
	<?php
	}
    if ($login_fabrica == 158) {
    ?>	
       	<th><?=traduz('Tipo Extrato')?></th>
    <?    
    }
	//HD 157052: Acrescentado seleção de produto para o lançamento
	//A partir do produto o sistema buscará as informacoes tbl_familia.bosch_cfa e tbl_produto.origem
	//para preencher respectivamente os campos tbl_extrato_lancamento.bosch_cfa e tbl_extrato_lancamento.conta_garantia
    /* 
	if ($login_fabrica == 20) {
?>
		<TH>Referência Produto</TH>
		<TH>Descrição Produto</TH>
	<?
	}
	?>
    */ 
    if ($login_fabrica == 3) {?>
		 <TH><?=traduz('Competência Futura')?></TH>
	<? }
    if (in_array($login_fabrica,array(1,148,152,157,175,180,181,182))) {
?>
        <TH><?=traduz('OS')?></TH>
<?
    }
	//HD 225722: Acrescentar opção EXCLUIR
	if ( ($lista_lancamento == 'listar') and (strlen(trim($posto_codigo))>0) ) {
	?>	
	<TH><?=traduz('AÇÃO')?></TH>
	<input type=hidden name=excluir_lancamento id=excluir_lancamento>
	<?
	} 
	?>
	</tr>
		</THEAD>
	<?
	if ($res && pg_num_rows($res)) {
		$total_lanca = pg_num_rows($res);
	}
	else {
		$total_lanca = 5;
	}
	if(strlen($extrato) && $extrato != "null"){
		$sql3 = "SELECT count(*) as total_lanca FROM tbl_extrato_lancamento WHERE extrato = $extrato";
		$res3 = pg_query($con,$sql3);
		if(pg_numrows($res3)> 0){
			$total_lanca = pg_fetch_result($res3,0,total_lanca)+3;
		}
	}
	?>
	<INPUT TYPE='hidden' NAME='total_lanca' value='<?= $total_lanca ?>'>
		<tbody>
	<?
	//HD 157052: Acrescentado seleção de produto para o lançamento
	//A partir do produto o sistema buscará as informacoes tbl_familia.bosch_cfa e tbl_produto.origem
	//para preencher respectivamente os campos tbl_extrato_lancamento.bosch_cfa e tbl_extrato_lancamento.conta_garantia
	$qtdeAnexos = 0;
	for ($i = 0; $i < $total_lanca; $i++){
		if ($res && $i<pg_num_rows($res)) {
			$lancamento          = @pg_fetch_result($res,$i,lancamento);
			$historico           = @pg_fetch_result($res,$i,historico);
			$valor               = @pg_fetch_result($res,$i,valor);
			$extrato_lancamento  = @pg_fetch_result($res,$i,extrato_lancamento);
			//hd 11015 Paulo
			$competencia_futura  = @pg_fetch_result($res,$i,competencia_futura);
		//	$debito_credito      = @pg_fetch_result($res,$i,debito_credito);
			$conta_garantia		 = @pg_fetch_result($res,$i,conta_garantia);
			$produto_referencia  = pg_fetch_result($res, $i, referencia);
			$produto_descricao	 = pg_fetch_result($res, $i, descricao);
			$os					 = pg_fetch_result($res, $i, 'os');
		}
		else {
			$lancamento          = $_POST["lancamento_$i"];
			$historico           = $_POST["historico_$i"];
			$valor               = $_POST["valor_$i"];
			$extrato_lancamento  = $_POST["extrato_lancamento_$i"];
			$competencia_futura  = $_POST["competencia_futura_$i"];
			$conta_garantia		 = $_POST['conta_garantia_' . $i] ;
			$produto_referencia  = $_POST['produto_referencia_' . $i];
			$produto_descricao   = $_POST['produto_descricao_' . $i];
			$os                  = $_POST['os_' . $i];
		} ?>
		<TR bgcolor='#f8f8f8'>
		<TD class="tac">
		<?
		$sql = "SELECT  lancamento, descricao
				FROM    tbl_lancamento
				WHERE   tbl_lancamento.fabrica = $login_fabrica
				AND      tbl_lancamento.ativo IS TRUE
				ORDER BY tbl_lancamento.descricao;";
		$res1 = pg_query ($con,$sql);
		if (pg_numrows($res1) > 0) { ?>			
			
			<select class='frm' style='width: 180px;' name='lancamento_<?= $i ?>'>			
			<option value=''><?=traduz('ESCOLHA')?></option>
		<?
			for ($x = 0 ; $x < pg_numrows($res1) ; $x++){
				$aux_lancamento = trim(pg_fetch_result($res1,$x,lancamento));
				$aux_descricao  = trim(pg_fetch_result($res1,$x,descricao));
				echo "<option value='$aux_lancamento'"; if ($lancamento == $aux_lancamento) echo " SELECTED "; echo ">$aux_descricao</option>\n";
			}
		?>
			</select>
		<?	
		}else {
			?>
			<h2><a href='lancamentos_avulsos_cadastro.php'><?=traduz('Lançamento não encontrado')?></a></h2>
			<?
		}
		?>
		</TD>
		<?
	//	echo "<TD><input type='text' class='frm' name='historico_$i' value='$historico' size='50' maxlength='50'></TD>";
		$disabled = '';
		if($login_fabrica == 1 AND strlen($extrato) > 0 AND strlen($valor) > 0 ){
			$disabled = 'readonly';
		}?>
		<TD class="tac">
			<textarea value="" name="historico_<?= $i ?>" rows="3" cols="45" class='frm' <?= $disabled ?>><?= $historico ?></TEXTAREA>
		</TD>
		<TD class="tac">
			<?php 
				if(!empty($valor)){
					$valor = number_format($valor, 2, '.', '');	
				}
			?>
			<div class='input-append tac'>
				<input style="min-width: 100px;" type='text' class="<?php echo ($_GET['extrato']) ? 'span6' : 'span2';?>" name='valor_<?= $i ?>' id='valor_<?= $i ?>' value='<?= str_replace(".",",",$valor) ?>' maxlength='10' price='true'>				
				<span class='add-on'><div style="color: black;font-weight: bolder;"><?php echo $real ?></div></span>
				<input type='hidden' name='ant_valor_<?= $i ?>' value='<?= $valor ?>'>
			</div>
		</TD>
		<?php
		if (in_array($login_fabrica, [20])) { ?>
			<td class="tac">
				<?php
	                $tDocs->setContext('avulso');
                    $info = $tDocs->getDocumentsByRef($extrato_lancamento)->attachListInfo;
                     if (count($info) > 0) {
                        foreach ($info as $k => $vAnexo) {
                            $info[$k]["posicao"] = $pos++;
                        }
                    }
                    $imagemAnexo = "imagens/imagem_upload.png";
                    $linkAnexo   = "#";
                    $tdocs_id   = "";
                    if (!empty($extrato_lancamento)) {
                        if (count($info) > 0) {
                            foreach ($info as $k => $vAnexo) {
                                $linkAnexo   = $vAnexo["link"];
                                $tdocs_id = $vAnexo["tdocs_id"];
                                $ext = strtolower(preg_replace("/.+\./", "", basename($vAnexo["filename"])));
			                    if ($ext == "pdf") {
			                        $imagemAnexo = "imagens/pdf_icone.png";
			                    } else if (in_array($ext, array("doc", "docx"))) {
			                        $imagemAnexo = "imagens/docx_icone.png";
			                    } else {
			                        $imagemAnexo = $vAnexo["link"];
			                    }
                            }
                        } 
                    }
		            ?>
		            <div id="div_anexo_<?=$i?>" class="tac" style="display: inline-block; margin: 0px 5px 0px 5px; vertical-align: top">
		                <?php if ($linkAnexo != "#") { ?>
		                <a href="<?=$linkAnexo?>" target="_blank" >
		                <?php } ?>
		                    <img src="<?=$imagemAnexo?>" class="anexo_thumb" style="width: 100px; height: 90px;" />
		                <?php if ($linkAnexo != "#") { ?>
		                </a>
		                <script>setupZoom();</script>
		                <?php } ?>
		                <button type="button" style="display: none;" class="btn btn-mini btn-remover-anexo btn-danger btn-block" data-tdocsid="<?=$tdocs_id?>" data-posicao="<?=$i?>" >Remover</button>
		                <button type="button" class="btn btn-mini btn-primary btn-block" name="anexar" rel="<?=$i?>" >Anexar</button>
		                <img src="imagens/loading_img.gif" class="anexo_loading" style="width: 64px; height: 64px; display: none;" />
		                <input type="hidden" rel="anexo" name="anexo[<?=$i?>]" value='<?=$anexo[$i]?>' />
		            </div>
		            <br />
			</td>
		<?php
			$qtdeAnexos++;
		}
        if ($login_fabrica == 158) { ?>
                <td>
		    		<select class='frm' name="conta_garantia_<?= $i; ?>" >
                        <option value=''>ESCOLHA</option>
                        <option value='t' <?= ($conta_garantia == "t") ? "selected" : "" ?> >Garantia</option>
                        <option value='f' <?= (($conta_garantia == "f") ? "selected" : "") ?> >Fora de Garantia</option>
                    </select>
                </td>
           <? 
        }
		//HD 157052: Acrescentado seleção de produto para o lançamento
		//A partir do produto o sistema buscará as informacoes tbl_familia.bosch_cfa e tbl_produto.origem
		//para preencher respectivamente os campos tbl_extrato_lancamento.bosch_cfa e tbl_extrato_lancamento.conta_garantia
		/*
		if ($login_fabrica == 20) { ?>
			<td nowrap>
				<input class='frm' type='text' name='produto_referencia_<?= $i ?>' id='produto_referencia_<?= $i ?>' size='15' maxlength='20' value='<?= $produto_referencia ?>'>&nbsp;<span class='add-on' rel='lupa' src='imagens/lupa.png' border='0' align=absmiddle style='cursor:pointer' onclick='javascript: fnc_pesquisa_produto2(document.frm_extrato_avulso.produto_referencia_<?= $i ?>,document.frm_extrato_avulso.produto_descricao_<?= $i ?>,"referencia");'><i class='icon-search' ></i></span>
			</td>
			<td nowrap>
				<input class='frm' type='text' name='produto_descricao_<?= $i ?>' id='produto_descricao_<?= $i ?>' size='30' value='<?= $produto_descricao ?>'>&nbsp;<span class='add-on' rel='lupa' src='imagens/lupa.png' style='cursor:pointer' border='0' align='absmiddle' onclick='javascript:fnc_pesquisa_produto2 (document.frm_extrato_avulso.produto_referencia_<?= $i ?>,document.frm_extrato_avulso.produto_descricao_<?= $i ?>,"descricao")'><i class='icon-search' ></i></span>
			</td>
		<?
		} */
				//HD 225722: Acrescentar opção EXCLUIR
		if ( ($lista_lancamento == 'listar') and (strlen(trim($posto_codigo))>0) ) { ?>
			<td class="tac">
				<input type="hidden" value="<?= $lista_lancamento ?>" name="lista_lancamento">
				<button class='btn btn-danger' onclick='fn_excluir_lancamento(<?= $extrato_lancamento ?>)'>Excluir</button>
			</td>
		<?	
		}
		//HD 11015 Paulo
		if ($login_fabrica == 3 ) {
		?>	
			<td><input type='text' name='competencia_futura_<?= $i ?>' value='<?= $competencia_futura ?>' rel='mascara_data' size='8' maxlength='7'
		<?	
			if(strlen($extrato) > 0) {
				echo " disabled>";
			} else {
				echo " >";
			}
		?>	</td>
		<?	
		}
		if(in_array($login_fabrica,array(1,148,152,157,175,180,181,182))) {
            $evento = ($login_fabrica == 1) ? "onblur" : "onchange";
?>
				<td class="tac">
                	<input type='text' class='frm' name='os_<?=$i?>' value='<?=$os?>' <?=$evento?>='javascript:buscaOS(<?=$i?>)' />
	<?
	            if($login_fabrica == 1){
	?>
	                <input type="hidden" name="extrato_avulso_<?=$i?>" value="" />
	                <input type="hidden" name="os_gravar_<?=$i?>" value="" />
	<?
	            }
	?>		
 				</td>
 		<?
		}
		?>
</TR>
<input type='hidden' name='extrato_lancamento_<?= $i ?>' value='<?= $extrato_lancamento ?>'>
		
<?
	}
	?>
	<p>
<tr>
	<td colspan='100%' align="center">
		<div class="row">
				<div class="span5"></div>
					<div class="span5">
						<button class="btn btn-large" type="button" id="btn_gravar" onclick="javascript:
						if (document.getElementById('btn_acao_clicou').value==''){
							document.frm_extrato_avulso.btn_acao.value='gravar' ;
							document.getElementById('btn_acao_clicou').value='gravar' ;
							document.frm_extrato_avulso.submit();
						}else{
							alert('Aguarde submissão');						
						}"><?=traduz('Gravar')?></button>
					</div>
				<div class="span2"></div>
		</div>	
</form>
</td>
</tr>
</tbody>
</table>
<p>
<p>
<script type="text/javascript">
	$("#confirma_avulso_negativo").click(function() {
		document.frm_extrato_avulso.btn_acao.value='gravar' ;
		document.frm_extrato_avulso.excluir_avulso_negativo.value='true' ;
		document.getElementById('btn_acao_clicou').value='gravar' ;
		document.frm_extrato_avulso.submit();
	} );
	$("#cancela_avulso_negativo").click(function() {
//		window.location= 'menu_financeiro.php';
		$(".msg_erro").hide();
	});
</script>
<?
}
if ($qtdeAnexos > 0) {
	for ($i = 0; $i <=  $qtdeAnexos; $i++) { ?>
	    <form name="form_anexo" method="post" action="extrato_avulso.php" enctype="multipart/form-data" style="display: none !important;" >
	        <input type="file" name="anexo_upload_<?=$i?>" value="" />
	        <input type="hidden" name="ajax_anexo_upload" value="t" />
	        <input type="hidden" name="anexo_posicao" value="<?=$i?>" />
	        <input type="hidden" name="anexo_chave" value="<?=$anexo_chave?>" />
	    </form>
	<?php 
	}
}
include "rodape.php";
?>
