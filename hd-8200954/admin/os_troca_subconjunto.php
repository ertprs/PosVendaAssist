<?php

include __DIR__.'/dbconfig.php';
include __DIR__.'/includes/dbconnect-inc.php';
include __DIR__.'/autentica_admin.php';
include __DIR__.'/funcoes.php';

use Posvenda\Regras;

include_once "class/aws/s3_config.php";
include_once S3CLASS;

include_once '../class/communicator.class.php';

if(in_array($login_fabrica, array(151))){
	require_once "./os_cadastro_unico/fabricas/151/classes/CancelarPedido.php";
	$cancelaPedidoClass = new CancelarPedido($login_fabrica);

	include_once "../class/Posvenda/Os.php";
}

if($login_fabrica == 178 AND file_exists("os_cadastro_unico/fabricas/{$login_fabrica}/ajax.php")){
    include_once "os_cadastro_unico/fabricas/{$login_fabrica}/ajax.php";
}

// No array, fábricas que usam os input produto referência/produto descrição
// e não o padrão, que é com selects família -> produto
$fabrica_usa_familia_produto = !in_array($login_fabrica, array(151));

$nao_fn_pedido_cancela_garantia = array(147, 160);
if($replica_einhell) $nao_fn_pedido_cancela_garantia[] = $login_fabrica;
$auditoria_liberada = true;

$s3 = new AmazonTC("os", $login_fabrica);

if ($login_fabrica == 183) {
	$array_estados = $array_estados();

	if (isset($_POST['atualiza_dados_consumidor']) && $_POST['atualiza_dados_consumidor']) {
        
        $os_consulta 				= $_POST['os_consulta'];
        $consumidor_cep 			= $_POST['consumidor_cep'];
        $consumidor_cep 			= preg_replace("/[\.\-\/]/", "", $consumidor_cep);
		$consumidor_endereco 		= $_POST['consumidor_endereco'];
		$consumidor_numero 			= $_POST['consumidor_numero'];
		$consumidor_complemento 	= $_POST['consumidor_complemento'];
		$consumidor_bairro 			= $_POST['consumidor_bairro'];
		$consumidor_referencia 		= $_POST['consumidor_referencia'];
		$consumidor_estado 			= $_POST['consumidor_estado'];
		$consumidor_cidade 			= $_POST['consumidor_cidade'];

		pg_query($con, "BEGIN");

		$up_os = "UPDATE tbl_os SET consumidor_cep = '{$consumidor_cep}', 
				consumidor_endereco = '{$consumidor_endereco}', 
				consumidor_numero = '{$consumidor_numero}',
				consumidor_complemento = '{$consumidor_complemento}',
				consumidor_bairro = '{$consumidor_bairro}',
				consumidor_estado = '{$consumidor_estado}',
				consumidor_cidade = '{$consumidor_cidade}' 
				WHERE fabrica = {$login_fabrica} AND os = {$os_consulta}";
		$res_os = pg_query($con, $up_os);
		
		if (strlen(pg_last_error()) > 0){
			$error = "error";
		}

		$up_ose = "UPDATE tbl_os_extra SET obs = '{$consumidor_referencia}' WHERE os = {$os_consulta}";
		$res_ose = pg_query($con, $up_ose);
		
		if (strlen(pg_last_error()) > 0){
			$error = "error";
		}	
		
		if (empty($error)) {
			pg_query($con, "COMMIT");
			exit(json_encode(array("retorno" => "success")));
		}else{
			pg_query($con, "ROLLBACK");
			exit(json_encode(array("retorno" => "error")));
		}
		exit;
	}

	if (isset($_POST['visualizar_endereco']) && $_POST['visualizar_endereco']) {
		$os_consulta = $_POST['os_consulta'];
		
		$sql_os = "
			SELECT 
				tbl_os.consumidor_endereco,
				tbl_os.consumidor_numero,
				tbl_os.consumidor_cep,
				tbl_os.consumidor_complemento, 
				tbl_os.consumidor_bairro,
				tbl_os.consumidor_cidade,
				tbl_os.consumidor_estado,
				tbl_os_extra.obs AS consumidor_referencia
			FROM tbl_os
			JOIN tbl_os_extra ON tbl_os_extra.os = tbl_os.os
			WHERE tbl_os.fabrica = $login_fabrica 
			AND tbl_os.os = $os_consulta";
		$res_os = pg_query($con, $sql_os);
	
		if (pg_num_rows($res_os) > 0) {
			$dados = array(
				"consumidor_endereco" => utf8_encode(pg_fetch_result($res_os, 0, 'consumidor_endereco')),
				"consumidor_numero" => pg_fetch_result($res_os, 0, 'consumidor_numero'),
				"consumidor_cep" => pg_fetch_result($res_os, 0, 'consumidor_cep'),
				"consumidor_complemento" => utf8_encode(pg_fetch_result($res_os, 0, 'consumidor_complemento')),
				"consumidor_bairro" => utf8_encode(pg_fetch_result($res_os, 0, 'consumidor_bairro')),
				"consumidor_cidade" => utf8_encode(pg_fetch_result($res_os, 0, 'consumidor_cidade')),
				"consumidor_estado" => pg_fetch_result($res_os, 0, 'consumidor_estado'),
				"consumidor_referencia" => utf8_encode(pg_fetch_result($res_os, 0, 'consumidor_referencia'))
			);
		}else{
			$dados = array("error" => "error");
		}
		exit(json_encode($dados));
	}
}

if (isset($_POST['posto_credenciado']) && $_POST['posto_credenciado']) {
	$os_posto = $_POST['os_posto'];
	$sql_posto = "SELECT credenciamento 
				  FROM tbl_posto_fabrica 
				  JOIN tbl_posto USING(posto)
				  LEFT JOIN tbl_os USING(posto) 
				  WHERE tbl_posto_fabrica.fabrica = $login_fabrica 
				  AND tbl_os.os = $os_posto";
	$res_posto = pg_query($con, $sql_posto);
	if (pg_num_rows($res_posto) > 0) {
		if (strtoupper(pg_fetch_result($res_posto, 0, 'credenciamento')) == 'DESCREDENCIADO') {
			echo 'ok';
		}
	}
	exit();
}

if ($_GET["ajax_busca_defeito_peca"]) {
	try {
		$defeito_constatado = $_GET["defeito_constatado"];

		if (empty($defeito_constatado)) {
			throw new Exception("Defeito Constatado não informado");
		}

		$defeitos = array();

		$sqlx = "
		        SELECT
                	        d.defeito,
                                d.codigo_defeito || ' - ' || d.descricao AS descricao
                        FROM tbl_diagnostico dg
                        JOIN tbl_defeito d ON d.defeito = dg.defeito AND d.fabrica = {$login_fabrica} and d.ativo
                        WHERE dg.fabrica = {$login_fabrica}
                        AND dg.defeito_constatado = $defeito_constatado
                ";
                $res = pg_query($con, $sqlx);

		if (!pg_num_rows($res)) {
			throw new Exception("Nenhum defeito encontrado para o defeito constatado informado");
		}

		while ($row = pg_fetch_object($res)) {
			$defeitos[] = array("defeito" => $row->defeito, "descricao" => utf8_encode($row->descricao));
		}

		exit(json_encode(array("defeitos" => $defeitos)));
	} catch (Exception $e) {
		exit(json_encode(array(
			"erro" => utf8_encode($e->getMessage())
		)));
	}
}

function numero_de_serie_jfa_obrigatorio(){
    global $con, $login_fabrica;

    $k = key($_REQUEST['produto_troca']);
  	$produto_id = $_REQUEST['produto_troca'][$k][0];
    $serie = $_REQUEST["novo_n_serie"];

    $sql = "SELECT  numero_serie_obrigatorio,
                    referencia 
                FROM tbl_produto 
                WHERE produto = {$produto_id} 
                AND fabrica_i = {$login_fabrica}
                AND numero_serie_obrigatorio IS TRUE";

    $res = pg_query($con, $sql);

    if(pg_num_rows($res) > 0){
        $numero_serie_obrigatorio = pg_fetch_result($res, 0, "numero_serie_obrigatorio");
        $prod_referencia = pg_fetch_result($res, 0, "referencia");
        
        if($numero_serie_obrigatorio == "t" && empty($serie)){
            $ms = "Número de Série Obrigatório";
            return $ms;
        } else {
            $dataSerieInvalida = false;
            $serieData = substr($serie, 0,4);
            $serieDataMes = substr($serie, 0,2);
            $serieDataAno = substr($serie, 2,2);
            $serieReferencia = substr($serie, 4,3);
            
           if (!is_numeric($serie) || strlen($serie) != 13) {
			    $dataSerieInvalida = true;
			}

            if (!is_numeric($serieData)) {
                $dataSerieInvalida = true;
            }

            if (!preg_match('/^(0[1-9]|1[0-2])$/', $serieDataMes)) {
                $dataSerieInvalida = true;
            }

            if (!preg_match('/^(10|[0-9][0-9])$/', $serieDataAno)) {
                $dataSerieInvalida = true;
            }

            //verificar se é a referencia do produto
            if ( mb_strtoupper($prod_referencia) != mb_strtoupper($serieReferencia) ) {
                $dataSerieInvalida = true;
            }

            if ($dataSerieInvalida == true) {
                $ms = "Número de Série Inválido";
            } else {
            	$ms = "ok";
            }
            return $ms;
        }
    } else {
    	$ms = "ok";
    	return $ms;
    }
}

if ($_GET["ajax_busca_produto"] == true) {
	$familia = $_GET["familia"];

	if (empty($familia)) {
		$retorno = array("error" => utf8_encode("Família não informada"));
	} else {
		$sql = "SELECT familia FROM tbl_familia WHERE fabrica = {$login_fabrica} AND familia = {$familia}";
		$res = pg_query($con, $sql);

		if (!pg_num_rows($res)) {
			$retorno = array("error" => utf8_encode("Família não encontrada"));
		} else {
			$sql = "SELECT
						produto,
						(referencia || ' - ' || descricao) AS referencia_descricao
					FROM tbl_produto
					WHERE fabrica_i = {$login_fabrica}
					AND familia = {$familia}
					AND ativo IS TRUE
					AND lista_troca IS TRUE
					ORDER BY referencia ASC";
			$res = pg_query($con, $sql);

			if (pg_num_rows($res) > 0) {
				$retorno = array("produtos" => pg_fetch_all($res));

				$produtos = array();

				while ($produto = pg_fetch_object($res)) {
					$produtos[] = array(
						"id" => $produto->produto,
						"referencia_descricao" => utf8_encode($produto->referencia_descricao)
					);
				}

				$retorno = array("produtos" => $produtos);
			} else {
				$retorno = array("error" => "Nenhum produto encontrado");
			}
		}
	}

	exit(json_encode($retorno));
}

if (filter_input(INPUT_POST,'ajax_data_pagamento',FILTER_VALIDATE_BOOLEAN)) {
    $os = filter_input(INPUT_POST,'os');
    $data_pagamento = filter_input(INPUT_POST,'data_pagamento');

    list($dia,$mes,$ano) = explode("/",$data_pagamento);

    $gravaData = $ano."-".$mes."-".$dia;

    pg_query($con,"BEGIN TRANSACTION");

    $sqlGravar = "
        UPDATE  tbl_ressarcimento
        SET     liberado = '$gravaData'
        WHERE   os = $os
    ";
    $resGravar = pg_query($con,$sqlGravar);

    if (pg_last_error($con)) {
        pg_query($con,"ROLLBACK TRANSACTION");
        echo "erro";
        exit;
    }

    pg_query($con,"COMMIT TRANSACTION");
    echo json_encode(array("ok" => TRUE));
    exit;
}

if (isset($_POST["ajax_anexo_upload"])) {
    $chave   = $_POST["anexo_chave"];

    $arquivo = $_FILES["anexo_upload"];

    $ext = strtolower(preg_replace("/.+\./", "", $arquivo["name"]));

    if ($ext == "jpeg") {
        $ext = "jpg";
    }

    if (strlen($arquivo["tmp_name"]) > 0) {
        if (!in_array($ext, array("png", "jpg", "jpeg", "bmp"))) {
            $retorno = array("error" => utf8_encode("Arquivo em formato inválido, são aceitos os seguintes formatos: png, jpeg, bmp"));
        } else {
            $arquivo_nome = "{$chave}";

            $s3->tempUpload("{$arquivo_nome}", $arquivo);
            $link = $s3->getLink("thumb_{$arquivo_nome}.{$ext}", true);

            if (!strlen($link)) {
                $retorno = array("error" => utf8_encode("Erro ao anexar arquivo"));
            } else {
                $retorno = array("link" => $link, "arquivo_nome" => "{$arquivo_nome}.{$ext}");
            }
        }
    } else {
        $retorno = array("error" => utf8_encode("Erro ao anexar arquivo"));
    }

    exit(json_encode($retorno));
}

if ($_POST["btn_acao"] == "submit") {
	$produtos      = $_POST['os_produto'];
	$hd_chamado    = $_POST['hd_chamado'];
	$troca_produto = $_POST['troca_produto'];

	if(strlen(trim($hd_chamado))==0){
        $hd_chamado = null;
    }

	if ($telecontrol_distrib) {
		if (strlen(trim($troca_produto))==0) {
			$troca_produto = 'NULL';
		}
		$campo_distribuidor = ", distribuidor ";
		$value_distribuidor = ", $troca_produto ";
	}

	if (in_array($login_fabrica, array(151,183))) {
		$interventor_admin = $_POST["interventor_admin"];
		$autoriza_gravacao = $_POST["autoriza_gravacao"];
	}

	$produto_form = array();
	$qtde_produto = 0;
	foreach ($produtos as $key => $os_produto) {

		$qtde_produto++;

		$acao = $_POST["acao"][$os_produto];

		$sql = "SELECT (tbl_produto.referencia || ' - ' || tbl_produto.descricao) AS produto_nome
				FROM tbl_produto
				INNER JOIN tbl_os_produto ON tbl_os_produto.produto = tbl_produto.produto
				WHERE tbl_os_produto.os_produto = {$os_produto}";
		$res = pg_query($con, $sql);

		$produto_nome = pg_fetch_result($res, 0, "produto_nome");

		if (!empty($acao)) {
			switch ($acao) {
				case "trocar":

					$produto_form[$os_produto]["familia"]               = $_POST["familia"][$os_produto];
					$produto_form[$os_produto]["produto_troca"]         = $_POST["produto_troca"][$os_produto];
					$produto_form[$os_produto]["numero_registro"]       = $_POST["numero_registro"][$os_produto];
					$produto_form[$os_produto]["causa_troca"]           = $_POST["causa_troca"][$os_produto];
					$produto_form[$os_produto]["pecas"]                 = $_POST["pecas"][$os_produto];
                    			$produto_form[$os_produto]["produto_troca_qtde"]    = $_POST["produto_troca_qtde"][$os_produto];
					$produto_form[$os_produto]["marca_troca"]    	    = $_POST["marca_troca"][$os_produto];
					$produto_form[$os_produto]["enviar_para"]    	    = $_POST["enviar_para"][$os_produto];
					//                     $produto_form[$os_produto]["produto_troca_qtde"]    = 1;
					// 					$produto_form[$os_produto]["peca_produto_troca"]    = array();

					if (empty($produto_form[$os_produto]["produto_troca"])) {
						$msg_erro["msg"]["produto_troca"] = "Preencha os campos obrigatórios";
						$msg_erro["campos"][]             = "{$os_produto}|produto_troca";
					}

					if (empty($produto_form[$os_produto]["causa_troca"])) {
						$msg_erro["msg"]["causa_troca"] = "Preencha os campos obrigatórios";
						$msg_erro["campos"][]           = "{$os_produto}|causa_troca";
					}

					if(in_array($login_fabrica, [178,183])){
						
						if ($login_fabrica == 178){
							$sql_valida = "
								SELECT 
									tbl_os.os,
									tbl_os.defeito_constatado,
									tbl_os.defeito_constatado_grupo,
									tbl_os.tipo_atendimento
								FROM tbl_os 
								WHERE tbl_os.os = {$os}";
							$res_valida = pg_query($con, $sql_valida);

							if (pg_num_rows($res_valida) > 0){
								$array_valida = pg_fetch_array($res_valida);
								if (empty($array_valida['defeito_constatado_grupo']) OR empty($array_valida['defeito_constatado'])){
									$msg_erro["msg"][] = "Favor preencher os campos de defeito";
								}
								if (empty($array_valida['tipo_atendimento'])){
									$msg_erro["msg"][] = "Favor preencher o campo tipo atendimento";
								}
							}
						}
						
						if ($login_fabrica == 183){
							$sql_causa_defeito = "
								SELECT 
									tbl_causa_defeito.causa_defeito
								FROM tbl_causa_defeito
								WHERE fabrica = $login_fabrica
								AND (UPPER(descricao))  = fn_retira_especiais(UPPER('Atendimento garantia'))";
							$res_causa_defeito = pg_query($con, $sql_causa_defeito);

							if (pg_num_rows($res_causa_defeito) > 0){
								$causa_defeito = pg_fetch_result($res_causa_defeito, 0, "causa_defeito");

								$campo_defeito_peca = ", causa_defeito";
								$valor_defeito_peca = ", $causa_defeito";
							}else{
								$msg_erro["msg"][] = "Erro ao realizar a troca do produto, Causa Defeito não encontrada";
							}
							
							$sql_valida = "
								SELECT 
									tbl_os.os,
									tbl_os_defeito_reclamado_constatado.defeito_constatado,
									tbl_os.tipo_atendimento
								FROM tbl_os
								JOIN tbl_os_defeito_reclamado_constatado ON tbl_os_defeito_reclamado_constatado.os = tbl_os.os AND tbl_os_defeito_reclamado_constatado.fabrica = {$login_fabrica}
								WHERE tbl_os.os = {$os}";
							$res_valida = pg_query($con, $sql_valida);

							if (pg_num_rows($res_valida) > 0){
								$array_valida = pg_fetch_array($res_valida);
								if (empty($array_valida['defeito_constatado'])){
									$msg_erro["msg"][] = "Favor preencher os campos de defeito";
								}
								if (empty($array_valida['tipo_atendimento'])){
									$msg_erro["msg"][] = "Favor preencher o campo tipo atendimento";
								}
							}
						}
						
						if ($_POST["os_consumidor_revenda"][$os_produto] == "R"){
							$revenda_cnpj = $_POST["revenda_cnpj"][$os_produto];
							$revenda_cnpj = preg_replace("/\D/", "", $revenda_cnpj);

							if (empty($revenda_cnpj)){
								$msg_erro["msg"]["revenda_cnpj"] = "Preencha os campos obrigatórios";
								$msg_erro["campos"][]             = "{$os_produto}|revenda_cnpj";
							}

							if (!empty($revenda_cnpj)) {

						        if(strlen($revenda_cnpj) < 14){
						            $msg_erro["msg"]["revenda_cnpj"] = "CNPJ da Revenda inválido";
									$msg_erro["campos"][]            = "{$os_produto}|revenda_cnpj";
						        }
						        
						        if (strlen($revenda_cnpj) > 0) {
						            $sql = "SELECT fn_valida_cnpj_cpf('{$revenda_cnpj}')";
						        	$res = pg_query($con, $sql);

						            if (strlen(pg_last_error()) > 0) {
					                	$msg_erro["msg"]["revenda_cnpj"] = "CNPJ da Revenda inválido";
										$msg_erro["campos"][]             = "{$os_produto}|revenda_cnpj";
						            }
						        }
						        $produto_form[$os_produto]["revenda_cnpj"] = $revenda_cnpj;
						    }
					    }


					    if(in_array($login_fabrica, [178])){
						    if (empty($produto_form[$os_produto]["marca_troca"])) {
								$msg_erro["msg"]["causa_troca"] = "Preencha os campos obrigatórios";
								$msg_erro["campos"][]           = "{$os_produto}|marca_troca";
							}
						}
		
						if (empty($produto_form[$os_produto]["enviar_para"])) {
							$msg_erro["msg"]["causa_troca"] = "Preencha os campos obrigatórios";
							$msg_erro["campos"][]           = "{$os_produto}|enviar_para";
						}
					}

					if ($login_fabrica == 145) {
                        foreach ($produto_form[$os_produto]["produto_troca_qtde"] as $valor) {
                            if ($valor == 0 || $valor == "") {
                                $msg_erro["msg"]["produto_troca_qtde"] = "Preencha os campos obrigatórios";
                                $msg_erro["campos"][]                  = "{$os_produto}|produto_troca_qtde";
                                break;
                            }
                        }
					}

					break;

				case "ressarcimento":

					$produto_form[$os_produto]["numero_registro"]     = $_POST["numero_registro"][$os_produto];
					$produto_form[$os_produto]["causa_troca"]         = $_POST["causa_troca"][$os_produto];
					$produto_form[$os_produto]["pecas"]               = $_POST["pecas"][$os_produto];
					$produto_form[$os_produto]["nome_cliente"]        = $_POST["nome_cliente"][$os_produto];
					$produto_form[$os_produto]["cpf_cliente"]         = $_POST["cpf_cliente"][$os_produto];
					$produto_form[$os_produto]["banco"]               = $_POST["banco"][$os_produto];
					$produto_form[$os_produto]["agencia"]             = $_POST["agencia"][$os_produto];
					$produto_form[$os_produto]["agencia_digito"]      = $_POST["agencia_digito"][$os_produto];
					$produto_form[$os_produto]["conta"]               = $_POST["conta"][$os_produto];
					$produto_form[$os_produto]["conta_digito"]        = $_POST["conta_digito"][$os_produto];
					$produto_form[$os_produto]["tipo_conta"]          = $_POST["tipo_conta"][$os_produto];
					$produto_form[$os_produto]["valor_ressarcimento"] = $_POST["valor_ressarcimento"][$os_produto];
					$produto_form[$os_produto]["previsao_pagamento"]  = $_POST["previsao_pagamento"][$os_produto];

					if (empty($produto_form[$os_produto]["causa_troca"])) {
						$msg_erro["msg"]["causa_troca"] = "Informe a causa da troca/ressarcimento";
						$msg_erro["campos"][]             = "{$os_produto}|causa_troca";
					}

					if (empty($produto_form[$os_produto]["cpf_cliente"])) {
						$msg_erro["msg"]["cpf_cliente"] = "Informe o CPF do Cliente";
						$msg_erro["campos"][]             = "{$os_produto}|cpf_cliente";
					}

					if (empty($produto_form[$os_produto]["nome_cliente"])) {
						$msg_erro["msg"]["nome_cliente"] = "Informe o Nome do Cliente";
						$msg_erro["campos"][]             = "{$os_produto}|nome_cliente";
					}

					if (empty($produto_form[$os_produto]["valor_ressarcimento"])) {
						$msg_erro["msg"]["valor_ressarcimento"] = "Informe o Valor do Ressarcimento";
						$msg_erro["campos"][]             = "{$os_produto}|valor_ressarcimento";
					}

					if (!in_array($login_fabrica, [169, 170])) {
						if (empty($produto_form[$os_produto]["previsao_pagamento"])) {
							$msg_erro["msg"]["previsao_pagamento"] = "Informe a Previsão de Pagamento";
							$msg_erro["campos"][]             = "{$os_produto}|previsao_pagamento";
						}

						if (!strlen($produto_form[$os_produto]["banco"])) {
							$msg_erro["msg"]["banco"] = "Informe o Banco";
							$msg_erro["campos"][]     = "{$os_produto}|banco";
						}

						if (!strlen($produto_form[$os_produto]["agencia"])) {
							$msg_erro["msg"]["agencia"] = "Informe a Agência";
							$msg_erro["campos"][]       = "{$os_produto}|agencia";
						}

						if (!strlen($produto_form[$os_produto]["conta"])) {
							$msg_erro["msg"]["conta"] = "Informe o número da Conta";
							$msg_erro["campos"][]     = "{$os_produto}|conta";
						}

						if (!strlen($produto_form[$os_produto]["conta_digito"])) {
							$msg_erro["msg"]["conta"] = "Informe o número da Conta";
							$msg_erro["campos"][]     = "{$os_produto}|conta";
						}
					}


					// if (!strlen($produto_form[$os_produto]["agencia_digito"])) {
					// 	$msg_erro["msg"]["agencia"] = "Informe a Agência";
					// 	$msg_erro["campos"][]       = "{$os_produto}|agencia";
					// }

					break;

                case "base_troca":
                    $produto_form[$os_produto]["referencia"]        = $_POST["referencia"][$os_produto];
                    $produto_form[$os_produto]["valor_base_troca"]  = $_POST["valor_base_troca"][$os_produto];

                    if (!filter_input(INPUT_POST,"anexo")) {
                        $msg_erro["msg"]["anexo"] = "Para esse tipo de troca, anexe um arquivo ao pedido";
                        $msg_erro["campos"][]     = "anexo";
                    }

                    if ($produto_form[$os_produto]["valor_base_troca"] == "" || $produto_form[$os_produto]["valor_base_troca"] == 0) {
                        $msg_erro["msg"]["anexo"] = "Produto escolhido fora da base de troca";
                        $msg_erro["campos"][]     = "valor_base_troca";
                    }
                    break;

				case "consertar":
					continue;
					break;
			}

			$sqlFabrica = "SELECT fabrica, sua_os FROM tbl_os WHERE os = {$os}";
			$resFabrica = pg_query($con, $sqlFabrica);

			$fabrica = pg_fetch_result($resFabrica, 0, "fabrica");
			$sua_os = pg_fetch_result($resFabrica, 0, "sua_os");

			if ($fabrica != $login_fabrica) {
			    $msg_erro["msg"][] = "Erro";
			}

			if(in_array($login_fabrica,array(169,170))) {
				$defeito_constatado = $_POST['defeito_constatado'];
				$defeito_peca = $_POST['defeito_peca'];

				if(empty($defeito_constatado)) {
					$msg_erro["msg"][] = "É necessário informar um defeito constatado";
		                        $msg_erro["campos"][] = "defeito_constatado";
					break;
				}

				if(!empty($defeito_peca)) {
					$campo_defeito_peca = ", defeito" ;
					$valor_defeito_peca = ", $defeito_peca";
				} else {
					$msg_erro["msg"][] = "É necessário informar o defeito da peça";
					$msg_erro["campos"][] = "defeito_peca";
					break;
				}

				if (empty($msg_erro["msg"]) && empty($valor_defeito_peca)) {
					$msg_erro["msg"][] = "É necessário informar o defeito da peça";
					$msg_erro["campos"][] = "defeito_peca";
				}
			}
			/**
			 * Verifica se o produto já está trocado
			 * EXCEÇÃO: PST, será feita uma nova troca
			 */
			if (strtolower($acao) == "trocar" && !in_array($login_fabrica, array(153,169,170))) {
				$sql = "SELECT tbl_os_troca.os_troca
						  FROM tbl_os_troca
						  JOIN tbl_os_produto ON tbl_os_produto.os = tbl_os_troca.os
						   AND tbl_os_troca.produto      = tbl_os_produto.produto
						   AND tbl_os_produto.os_produto = {$os_produto}
						   JOIN tbl_os_item ON tbl_os_item.os_produto = tbl_os_produto.os_produto
						   JOIN tbl_servico_realizado ON tbl_os_item.servico_realizado = tbl_servico_realizado.servico_realizado AND tbl_servico_realizado.troca_produto IS TRUE AND tbl_os_item.pedido IS NOT NULL
						 WHERE tbl_os_troca.fabric = {$login_fabrica}
						   AND tbl_os_troca.os     = {$os}";
				$res = pg_query($con, $sql);

				if (pg_num_rows($res) > 0) {
					$msg_erro["msg"][] = "Produto {$produto_nome} já possui troca/ressarcimento lançado";
				}
			}

			/**
			 * Verifica se a Ordem de Serviço já está em Extrato
			 */
            if($login_fabrica <> 151){ //HD-2732207
    			$sql = "SELECT tbl_os_extra.extrato
    					FROM tbl_os
    					INNER JOIN tbl_os_extra ON tbl_os_extra.os = tbl_os.os
    					WHERE tbl_os.fabrica = {$login_fabrica}
    					AND tbl_os.os = {$os}
    					AND tbl_os_extra.extrato IS NOT NULL";
    			$res = pg_query($con, $sql);

    			if (pg_num_rows($res) > 0) {
    				$msg_erro["msg"]["os_extrato"] = "Não foi possível lançar a troca/ressarcimento, a Ordem de Serviço já está em Extrato";
    			}
            }

			/**
			 * Verifica se o produto a ser trocado está marcado como listra_troca = true
			 */
			if ($acao == "trocar") {

				foreach($produto_form[$os_produto]['produto_troca'] as $key => $produto) {
					$sql = "SELECT 
								tbl_produto.lista_troca, 
								tbl_produto.parametros_adicionais::jsonb->>'fora_linha' AS fora_linha
							FROM tbl_produto
							WHERE produto = {$produto}
							AND lista_troca IS TRUE";
					$res = pg_query($con, $sql);
					if (pg_num_rows($res) == 0) {
						$msg_erro["msg"][] = "Não foi possível trocar o produto {$produto_nome} o produto selecionado para troca não está habilitado para realizar a troca";
					}else if ($login_fabrica == 178){
						$xfora_linha = pg_fetch_result($res, 0 , "fora_linha");
						if ($xfora_linha == "true"){
							$msg_erro["msg"][] = "Não foi possível trocar o produto {$produto_nome} o produto selecionado para troca está fora de linha";
						}
					}
				}

			}

			/**
			 * Verifica se o produto está na tbl_peca
			 */
			if (in_array($acao, array("trocar","ressarcimento","base_troca"))) {
				if ($acao == "trocar") {

                    if(!isset($msg_erro) and $login_fabrica <> 151){

                        $sql_os_pecas = "SELECT os_item, peca, pedido, pedido_item, qtde, posto_i, os_produto FROM tbl_os_item WHERE os_produto IN (SELECT os_produto FROM tbl_os_produto WHERE os = {$os})";
                        $res_os_pecas = pg_query($con, $sql_os_pecas);

                        if(pg_num_rows($res_os_pecas) > 0){

                            $cont_pecas = pg_num_rows($res_os_pecas);

                            for($p = 0; $p < $cont_pecas; $p++){

                                $os_item_id          = pg_fetch_result($res_os_pecas, $p, "os_item");
                                $os_item_peca        = pg_fetch_result($res_os_pecas, $p, "peca");
                                $os_item_pedido      = pg_fetch_result($res_os_pecas, $p, "pedido");
                                $os_item_pedido_item = pg_fetch_result($res_os_pecas, $p, "pedido_item");
                                $os_item_qtde        = pg_fetch_result($res_os_pecas, $p, "qtde");
                                $os_item_posto       = pg_fetch_result($res_os_pecas, $p, "posto_i");
                                $os_item_os_produto  = pg_fetch_result($res_os_pecas, $p, "os_produto");

                                $motivo_cancelamento_pedido = "Peça cancelada por Troca de Produto na OS";

								if(!empty($os_item_pedido)) {
									$sql_pedido_cancelado = "SELECT
										pedido
										FROM tbl_pedido_cancelado
										WHERE
										pedido = {$os_item_pedido}
										AND posto = {$os_item_posto}
										AND fabrica = {$login_fabrica}
										AND os = {$os}
										AND peca = {$os_item_peca}
										";
									$res_pedido_cancelado = pg_query($con, $sql_pedido_cancelado);

									if(pg_num_rows($res_pedido_cancelado) == 0 and !empty($os_item_pedido)){
									}

									if(strlen(pg_last_error($con)) > 0){

										$msg_erro["msg"][] = "Não foi possível cancelar o pedido das peças para a OS {$os}";

									}else{

										$sql_cancela_item_pedido = "select fn_pedido_item_cancela($os_item_pedido_item, $os_item_qtde); ";
										$res_cancela_item_pedido = pg_query($con, $sql_cancela_item_pedido);

										if (in_array($login_fabrica, array(35,81,114,122,125,131,147,160)) or $replica_einhell) {

												$sql_insere_cancelado = "INSERT INTO tbl_pedido_cancelado (
				                                        pedido ,
				                                        posto  ,
				                                        fabrica,
				                                        os     ,
				                                        peca   ,
				                                        qtde   ,
				                                        data   ,
				                                        admin  ,
														pedido_item,
														motivo
				                                    )VALUES(
				                                        $os_item_pedido,
				                                        $os_item_posto,
				                                        $login_fabrica,
				                                        $os,
				                                        $os_item_peca,
				                                        $os_item_qtde,
				                                        current_date,
				                                        $login_admin,
														$os_item_pedido_item,
														'$motivo_cancelamento_pedido'
				                                    );";

			                                	$res_insere_cancelado = pg_query ($con,$sql_insere_cancelado);
			                            }

									}
								}
                            }

                        }
 
                    } 

					foreach ($produto_form[$os_produto]['produto_troca'] as $key => $produto) {
						if ($login_fabrica == 178){
							$cond_produto_acabado = "";
						}else{
							$cond_produto_acabado = " AND tbl_peca.produto_acabado IS TRUE ";
						}

						$sql = "
							SELECT
								tbl_peca.peca
                            FROM tbl_peca
                      		INNER JOIN tbl_produto ON  tbl_produto.referencia = tbl_peca.referencia AND tbl_produto.fabrica_i = {$login_fabrica}
							WHERE tbl_peca.fabrica = {$login_fabrica}
							$cond_produto_acabado
							AND tbl_produto.produto = {$produto};
						";

						$res = pg_query($con, $sql);

						if (pg_num_rows($res) == 0) {

							$sql = "SELECT referencia, descricao, ipi, origem
									FROM tbl_produto
									WHERE fabrica_i = {$login_fabrica}
									AND produto = {$produto}";
							$res = pg_query($con, $sql);

							$troca_referencia = pg_fetch_result($res, 0, "referencia");
							$troca_descricao  = substr(pg_fetch_result($res, 0, "descricao"),0,50);
							$troca_ipi        = pg_fetch_result($res, 0, "ipi");
							$troca_origem     = pg_fetch_result($res, 0, "origem");

							if (strlen($troca_ipi) == 0) {
								$troca_ipi = 0;
							}

							$sql = "SELECT peca
                                    FROM tbl_peca
									WHERE fabrica = $login_fabrica
									$cond_produto_acabado
									and referencia = '$troca_referencia'";
							$res = pg_query($con, $sql);
                            
							if(pg_num_rows($res) == 0) {
                                if (in_array($login_fabrica, array(171, 177)))
                                {
                                    $sql_prod = "SELECT referencia, parametros_adicionais
                                        FROM tbl_produto
                                        WHERE fabrica_i = {$login_fabrica}
                                        AND produto = {$produto}";
                                    $res_prod              = pg_query($con, $sql_prod);
                                    $object                = pg_fetch_object($res_prod);
                                    $parametros_adicionais = $object->parametros_adicionais;
                                    $referencia_fabrica    = $object->referencia;


                                    $campos = ",referencia_fabrica, parametros_adicionais ";
                                    $value_campos = ",'".$referencia_fabrica."','".$parametros_adicionais."' ";

                                    if ($login_fabrica == 177) {
                                    	$campos 	 .= ", peso";

                                    	$array_parametros = json_decode($parametros_adicionais, true);

                                    	if(isset($array_parametros['peso'])){
                                    		$array_parametros['peso'] = str_replace(",", ".", $array_parametros['peso']);
                                    	}

                                    	$value_campos .= ",".$array_parametros['peso'];
                                    }


                                }

								$sql = "
									INSERT INTO tbl_peca
										(fabrica,referencia,descricao,ipi,origem,produto_acabado {$campos})
									VALUES
										({$login_fabrica},'{$troca_referencia}','{$troca_descricao}',{$troca_ipi},'{$troca_origem}', TRUE {$value_campos})
									RETURNING peca;";	
								$res = pg_query($con, $sql);

								if (!pg_last_error($con)) {
                                    $produto_form[$os_produto]["peca_produto_troca"][] = pg_fetch_result($res, 0, "peca");
								} else {
                                    $msg_erro["msg"]["peca_produto_troca"] = "Erro ao lançar troca";
								}

							}else{
								$produto_form[$os_produto]["peca_produto_troca"][] = pg_fetch_result($res, 0, "peca");
							}

						} else {
							$produto_form[$os_produto]["peca_produto_troca"][] = pg_fetch_result($res, 0, "peca");
						}

					}

				} else if ($acao == "ressarcimento") {
					$sql = "
						SELECT
							tbl_peca.peca
						FROM tbl_os
						INNER JOIN tbl_produto ON tbl_produto.produto = tbl_os.produto AND tbl_produto.fabrica_i = {$login_fabrica}
						INNER JOIN tbl_peca ON tbl_peca.referencia = tbl_produto.referencia AND tbl_peca.fabrica = {$login_fabrica}
						WHERE tbl_os.fabrica = {$login_fabrica}
						AND tbl_os.os = {$os}
						AND tbl_peca.fabrica = {$login_fabrica}
						AND tbl_peca.produto_acabado IS TRUE;
					";

					$res = pg_query($con, $sql);

					if (!pg_num_rows($res)) {
						$sql = "
							SELECT
								referencia,
								descricao,
								ipi
                                			FROM tbl_os
                                			INNER JOIN tbl_produto ON tbl_produto.produto = tbl_os.produto AND tbl_produto.fabrica_i = {$login_fabrica}
                               			 	WHERE tbl_os.fabrica = {$login_fabrica}
                                			AND tbl_os.os = {$os};
						";
						$res = pg_query($con, $sql);

                        			$troca_referencia = pg_fetch_result($res, 0, "referencia");
                        			$troca_descricao  = substr(pg_fetch_result($res, 0, "descricao"),0,50);
                        			$troca_ipi        = pg_fetch_result($res, 0, "ipi");

                        			if (!strlen($troca_ipi)) {
                                			$troca_ipi = 0;
                        			}

						$sql = "SELECT peca FROM tbl_peca
								WHERE fabrica = $login_fabrica
								AND tbl_peca.produto_acabado IS TRUE
								and referencia = '$troca_referencia'";
						$res = pg_query($con, $sql);

						if(pg_num_rows($res) == 0) {

                            if (in_array($login_fabrica, array(171,177)))
                                {
                                    $sql_prod = "SELECT referencia, parametros_adicionais
                                        FROM tbl_produto
                                        WHERE fabrica_i = {$login_fabrica}
                                        AND produto = {$produto}";
                                    $res_prod              = pg_query($con, $sql_prod);
                                    $parametros_adicionais = pg_fetch_result($res_prod, 0, "parametros_adicionais");
                                    $referencia_fabrica    = pg_fetch_result($res_prod, 0, "referencia_fabrica");

                                    $campos = ", referencia_fabrica, parametros_adicionais ";
                                    $value_campos = ", '$referencia_fabrica', '$parametros_adicionais' ";

                                    if ($login_fabrica == 177) {
                                    	$campos 	 .= ", peso";

                                    	$array_parametros = json_decode($parametros_adicionais, true);

                                    	$value_campos .= ",".$array_parametros['peso'];
                                    }

                                }

							$sql = "
								INSERT INTO tbl_peca
									(fabrica, referencia, descricao, ipi, origem, produto_acabado {$campos})
								VALUES
									({$login_fabrica}, '{$troca_referencia}', '{$troca_descricao}', {$troca_ipi}, 'NAC', TRUE {$value_campos})
								RETURNING peca;
							";
							$res = pg_query($con, $sql);

							if (!pg_last_error($con)) {
                                				$produto_form[$os_produto]["peca_produto_troca"] = pg_fetch_result($res, 0, "peca");
							} else {
                                				$msg_erro["msg"]["peca_produto_troca"] = "Erro ao lançar troca";
							}
						}
					} else {
						 $produto_form[$os_produto]["peca_produto_troca"] = pg_fetch_result($res, 0, "peca");
					}
				} else if ($acao == "base_troca") {

            			$sql = "
            				SELECT
							tbl_peca.peca
							FROM tbl_peca
							INNER JOIN tbl_produto ON tbl_produto.referencia = tbl_peca.referencia
							AND tbl_produto.fabrica_i = {$login_fabrica}
							WHERE tbl_peca.fabrica = {$login_fabrica}
							AND tbl_peca.produto_acabado IS TRUE
							AND tbl_produto.referencia = '".$produto_form[$os_produto]["referencia"]."';
						";

						$res = pg_query($con, $sql);

                    			if (!pg_num_rows($res)) {
                        			$sql = "
                        				SELECT
                        					descricao,
                        					ipi,
                        					origem
							FROM tbl_produto
							WHERE fabrica_i = {$login_fabrica}
							AND referencia = '".$produto_form[$os_produto]["referencia"]."';
						";

						$res = pg_query($con, $sql);

                       				$troca_referencia = $produto_form[$os_produto]["referencia"];
						$troca_descricao  = substr(pg_fetch_result($res, 0, "descricao"),0,50);
						$troca_ipi        = pg_fetch_result($res, 0, "ipi");
						$troca_origem     = pg_fetch_result($res, 0, "origem");

                        			if (!strlen($troca_ipi)) {
                           	 			$troca_ipi = 0;
                        			}

                        			$sql = "
                        				SELECT
                        					peca
                    					FROM tbl_peca
                    					WHERE fabrica = {$login_fabrica}
										AND tbl_peca.produto_acabado IS TRUE
                    					AND referencia = '{$troca_referencia}';
                				";

                        			$res = pg_query($con, $sql);

                        			if(pg_num_rows($res) == 0) {

                                            if (in_array($login_fabrica, array(171,177)))
                                            {
                                                $sql_prod = "SELECT referencia, parametros_adicionais
                                                    FROM tbl_produto
                                                    WHERE fabrica_i = {$login_fabrica}
                                                    AND produto = {$produto}";
                                                $res_prod              = pg_query($con, $sql_prod);
                                                $parametros_adicionais = pg_fetch_result($res_prod, 0, "parametros_adicionais");
                                                $referencia_fabrica    = pg_fetch_result($res_prod, 0, "referencia_fabrica");

                                                $campos = ", referencia_fabrica, parametros_adicionais ";
                                                $value_campos = ", '$referencia_fabrica', '$parametros_adicionais' ";

                                                if ($login_fabrica == 177) {
			                                    	$campos 	 .= ", peso";

			                                    	$array_parametros = json_decode($parametros_adicionais, true);
			                                    	if(isset($array_parametros['peso'])){
			                                    		$array_parametros['peso'] = str_replace(",", ".", $array_parametros['peso']);
			                                    	}

			                                    	$value_campos .= ",".$array_parametros['peso'];
			                                    }

                                            }

                            				$sql = "
                            					INSERT INTO tbl_peca
                            						(fabrica, referencia, descricao, ipi, origem, produto_acabado {$campos})
                        					VALUES
                        						({$login_fabrica}, '{$troca_referencia}', '{$troca_descricao}', {$troca_ipi}, '{$troca_origem}', TRUE {$value_campos})
                    						RETURNING peca;
                	    				";

							$res = pg_query($con, $sql);

                            				if (!pg_last_error($con)) {
                                				$produto_form[$os_produto]["peca_produto_troca"] = pg_fetch_result($res, 0, "peca");
                            				} else {
                                				$msg_erro["msg"]["peca_produto_troca"] = "Erro ao lançar troca";
                            				}
                        			}else{
                            				$produto_form[$os_produto]["peca_produto_troca"] = pg_fetch_result($res, 0, "peca");
                        			}
                    			} else {
                        			$produto_form[$os_produto]["peca_produto_troca"] = pg_fetch_result($res, 0, "peca");
                    			}
				}

				/**
				 * Verifica se o produto está na lista básica
				 */
				if ($login_fabrica != 143 && $acao == "trocar") {
					foreach($produto_form[$os_produto]["produto_troca"] as $key => $produto) {
						$peca_produto = $produto_form[$os_produto]["peca_produto_troca"][$key];

						$sql = "SELECT lista_basica
								FROM tbl_lista_basica
								WHERE fabrica = {$login_fabrica}
								AND produto = '{$produto}'
								AND peca = {$peca_produto}";
						$res = pg_query($con, $sql);

						if (!pg_num_rows($res)) {
							$sql = "INSERT INTO tbl_lista_basica
								(produto, peca, qtde, fabrica, ativo)
								VALUES
								({$produto}, {$peca_produto}, 1, {$login_fabrica}, TRUE)";
							$res = pg_query($con, $sql);

							if (strlen(pg_last_error()) > 0) {
								$msg_erro["msg"]["peca_produto_troca"] = "Erro ao lançar troca";
							}
						}
					}
				}

                /**
                * - Verifica, para PST, se há uma nova troca.
                * Se houver, apaga a anterior
                */
                if($acao == "trocar"){
                    $res = pg_query($con,"BEGIN TRANSACTION");

                    $sqlDel = "
                        DELETE  FROM tbl_os_troca
                        WHERE   os              = $os
                        AND     fabric          = $login_fabrica
                        AND     ressarcimento   IS NOT TRUE
                    ";
                    $resDel = pg_query($con,$sqlDel);
                    if(!pg_last_error($con)){
                        $res = pg_query($con,"COMMIT TRANSACTION");
                    }else{
                        $res = pg_query($con,"ROLLBACK TRANSACTION");
                        $msg_erro["msg"]["peca_produto_troca"] = "Erro ao lançar nova troca";
                    }
                }
			}

			/**
			 * Verifica se o posto da Ordem de Serviço está credenciado
			 */
			$sql = "
				SELECT
					tbl_posto_fabrica.posto
				FROM tbl_posto_fabrica
				JOIN tbl_os ON tbl_os.posto = tbl_posto_fabrica.posto AND tbl_os.fabrica = {$login_fabrica}
				WHERE tbl_posto_fabrica.fabrica = {$login_fabrica}
				AND tbl_os.os = {$os}
				AND tbl_posto_fabrica.credenciamento = 'DESCREDENCIADO';
			";

			$res = pg_query($con, $sql);

			if (pg_num_rows($res) > 0 AND $login_fabrica != 151) {
				$msg_erro["msg"]["posto_descredenciado"] = "Não foi possível lançar a troca/ressarcimento o Posto Autorizado da Ordem de Serviço está DESCREDENCIADO";
			}
		} else {
			$msg_erro["msg"]["acao"] = "Selecione uma ação para o produto";
			$msg_erro["campos"][] = "{$os_produto}|acao";
		}
	}

	$setor_responsavel    = $_POST["setor_responsavel"];
	$situacao_atendimento = $_POST["situacao_atendimento"];
	$gerar_pedido         = $_POST["gerar_pedido"];

	if (in_array($login_fabrica, [186])) {
		$gerar_pedido = (strlen($_POST['gerar_pedido_com_at']) > 0) ? "t" : "f";
	}

	if ($envio_consumidor != "t") {
		$envio_consumidor = "f";
	}

	if ($gerar_pedido != "t" || $acao == "base_troca") {
		$gerar_pedido = "f";
	}

	if (in_array($login_fabrica, array(169,170))) {
		$sqlValida = "
			SELECT tbl_os.serie, tbl_os_defeito_reclamado_constatado.defeito_constatado
			FROM tbl_os
			JOIN tbl_os_defeito_reclamado_constatado ON tbl_os_defeito_reclamado_constatado.os = tbl_os.os AND tbl_os_defeito_reclamado_constatado.defeito_constatado IS NOT NULL
			WHERE tbl_os.os = {$os}
			AND tbl_os.fabrica = {$login_fabrica}
			AND tbl_os.serie IS NOT NULL
		";
		$resValida = pg_query($con, $sqlValida);

		if (pg_num_rows($resValida) == 0){
			$msg_erro["msg"]["grava_os_troca"] = "Obrigatório na OS os campos Número Série e Defeito Constatado ";
		}

		$sqlPostoInterno = "
			SELECT o.os
			FROM tbl_os o
			INNER JOIN tbl_posto_fabrica pf ON pf.posto = o.posto AND pf.fabrica = {$login_fabrica}
			INNER JOIN tbl_tipo_posto tp ON tp.tipo_posto = pf.tipo_posto AND tp.fabrica = {$login_fabrica}
			WHERE o.os = {$os}
			AND tp.posto_interno IS TRUE
		";
		$resPostoInterno = pg_query($con, $sqlPostoInterno);

		if (pg_num_rows($resPostoInterno) > 0) {
			$gerar_pedido = "f";
		} else {
			$gerar_pedido = "t";
		}
	}

    if ($telecontrol_distrib && $troca_produto == 'NULL' && $gerar_pedido == 't' && $acao =='trocar') {
        $msg_erro["msg"]["grava_os_troca"] = "Troca via fábrica não gera pedido";
    }

	$observacao = pg_escape_string(trim($_POST["observacao"]));

	if (empty($setor_responsavel)) {
		$msg_erro["msg"]["setor_responsavel"] = "Preencha os campos obrigatórios";
		$msg_erro["campos"][] = "setor_responsavel";
	}

	if (!strlen($situacao_atendimento)) {
		$msg_erro["msg"]["situacao_atendimento"] = "Preencha os campos obrigatórios";
		$msg_erro["campos"][] = "situacao_atendimento";
	}

	if (!isset($msg_erro)) {
		pg_query($con, "BEGIN");

        $acao_troca_ressarcimento  = false;
        $troca_ressarcimento_total = 0;
        $libera_auditoria          = true;

		if ($login_fabrica == 151) {
			$spd = array();
		}

		foreach ($produto_form as $os_produto => $campos) {
			$acao = $_POST["acao"][$os_produto];

			switch ($acao) {
				case "trocar":
					$peca          = $campos["peca_produto_troca"];
					$pecas         = $campos["pecas"];
					$ressarcimento = "FALSE";
					$acao_troca_ressarcimento = true;
                    $produto_troca_qtde = $campos["produto_troca_qtde"];
					$troca_ressarcimento_total++;
					break;
				case "ressarcimento":
					$peca          = $campos["peca_produto_troca"];
					$pecas         = $campos["pecas"];
					$ressarcimento = "TRUE";
					$acao_troca_ressarcimento = true;
					$troca_ressarcimento_total++;
					break;
                case "base_troca":
                    $peca          = $campos["peca_produto_troca"];
                    $ressarcimento = "FALSE";
                    $libera_auditoria = false;
                    $acao_troca_ressarcimento = true;

                    $sqlCausaTroca = "
                        SELECT
                        	causa_troca
                        FROM tbl_causa_troca
                        WHERE fabrica = $login_fabrica
                        AND descricao ILIKE 'Base de Troca';
                    ";

                    $resCausaTroca = pg_query($con,$sqlCausaTroca);

                    $causa_troca = pg_fetch_result($resCausaTroca,0,0);
                    break;
				case "consertar":
					$libera_auditoria = false;
					continue;
					break;
			}

			$sql = "SELECT produto FROM tbl_os_produto WHERE os = {$os} AND os_produto = {$os_produto}";
			$res = pg_query($con, $sql);

			$produto = pg_fetch_result($res, 0, "produto");

            if (in_array($acao,array("trocar","ressarcimento","consertar"))) {
                $causa_troca    = $campos["causa_troca"];
            }
			$ri             = $campos["numero_registro"];
			$originou_troca = $campos["pecas"];

			if (in_array($login_fabrica, array(151,183))){

				if (strlen($autoriza_gravacao) > 0 AND $autoriza_gravacao != "desbloqueado") {
					$msg_erro = "A troca precisa de autorização do interventor.";
				} else {
					if (strlen($interventor_admin) > 0) {
						$interventor_admin_campo = ", admin_autoriza ";
						$interventor_admin_value = ", {$interventor_admin}";
					}
				}
			}

			if (is_array($peca)) {

				if ($login_fabrica == 151 AND strlen($hd_chamado) > 0) {
					$sql_pedido_faturado = "
						SELECT
							tbl_os_item.peca,
							tbl_os_item.qtde,
							tbl_pedido_item.qtde_faturada,
							tbl_pedido.pedido,
							tbl_os_item.os_item,
							tbl_os.os,
							tbl_pedido.status_pedido,
							tbl_pedido_item.pedido_item,
							tbl_pedido_item.qtde_faturada_distribuidor
						FROM
						tbl_os_item
						INNER JOIN tbl_os_produto ON tbl_os_item.os_produto=tbl_os_produto.os_produto
						INNER JOIN tbl_os ON tbl_os.os = tbl_os_produto.os AND tbl_os.finalizada IS NULL
						INNER JOIN tbl_hd_chamado_item ON tbl_os_produto.os=tbl_hd_chamado_item.os
						INNER JOIN tbl_pedido_item ON tbl_os_item.pedido = tbl_pedido_item.pedido AND tbl_os_item.pedido_item = tbl_pedido_item.pedido_item
						INNER JOIN tbl_pedido ON tbl_pedido.pedido  = tbl_pedido_item.pedido
						WHERE tbl_hd_chamado_item.hd_chamado  = {$hd_chamado};
					";

					$res_pedido_faturado = pg_query($con, $sql_pedido_faturado);

                    if (pg_num_rows($res_pedido_faturado) > 0) {
                    	for ($xy=0; $xy < pg_num_rows($res_pedido_faturado); $xy++) {
	                        $qtde_faturada  = pg_fetch_result($res_pedido_faturado, $xy, qtde_faturada);
	                        $qtde_faturada_distribuidor  = pg_fetch_result($res_pedido_faturado, $xy, qtde_faturada_distribuidor);
	                        $qtde           = pg_fetch_result($res_pedido_faturado, $xy, qtde);
	                        $xpeca          = pg_fetch_result($res_pedido_faturado, $xy, peca);
	                        $xos            = pg_fetch_result($res_pedido_faturado, $xy, os);
	                        $pedido            = pg_fetch_result($res_pedido_faturado, $xy, pedido);
	                        $pedido_item            = pg_fetch_result($res_pedido_faturado, $xy, pedido_item);

							if($qtde_faturada == 0 and $qtde_faturada_distribuidor == 0 ) {
								$retorno_cancelamento_send = $cancelaPedidoClass->cancelaPedidoItem($pedido, $pedido_item, "Produto foi Ressarcido/Trocado para o cliente");
								$pos = strpos($retorno_cancelamento_send,'OT possui quantidade faturada');

								if (!is_bool($retorno_cancelamento_send) AND $pos === false){
									$msg_erro["msg"][] = $retorno_cancelamento_send;
									$erro_send = true;
								}else{

									$sqlVerPedidoFat = "SELECT fn_pedido_cancela_garantia(null,$login_fabrica,pedido,peca,os_item,'Produto Trocado',$login_admin)
										FROM tbl_os_produto
										JOIN tbl_os_item USING(os_produto)
										WHERE tbl_os_produto.os = $xos
										AND   tbl_os_item.pedido NOTNULL
										AND tbl_os_item.peca = $xpeca";
									$resVerPedidoFat = pg_query($con, $sqlVerPedidoFat);
								}
		                    }
	                	}
	                }
					foreach($peca as $key => $value) {
						$sql = "INSERT INTO tbl_os_troca
							(fabric, os, admin, produto, setor, situacao_atendimento, peca, observacao, causa_troca, gerar_pedido, ressarcimento, ri, envio_consumidor $campo_distribuidor $interventor_admin_campo)
							VALUES
							({$login_fabrica}, {$os}, {$login_admin}, {$produto}, '{$setor_responsavel}', {$situacao_atendimento}, {$value}, '{$observacao}', {$causa_troca}, '{$gerar_pedido}', {$ressarcimento}, '{$ri}', '{$envio_consumidor}' $value_distribuidor $interventor_admin_value)";
	                    $res = pg_query($con, $sql);
					}

				} else {
					foreach($peca as $key => $value) {
	                    //PST
	                    //Verificar se o pedido da O.S não esta faturado, se não estiver, fazer o cancelamento do PEDIDO.
	                    $sql_pedido_faturado = "SELECT tbl_os_item.peca, tbl_os_item.qtde,  tbl_pedido_item.qtde_faturada, tbl_pedido.pedido, tbl_os_item.os_item, tbl_os.os, tbl_pedido.status_pedido,tbl_pedido_item.qtde_faturada_distribuidor
	                                              FROM tbl_os_item
	                                        INNER JOIN tbl_os_produto  ON tbl_os_produto.os_produto = tbl_os_item.os_produto
	                                        INNER JOIN tbl_os          ON tbl_os.os                 = tbl_os_produto.os
	                                        INNER JOIN tbl_pedido_item ON tbl_os_item.pedido        = tbl_pedido_item.pedido
	                                                                  AND tbl_os_item.pedido_item   = tbl_pedido_item.pedido_item
	                                        INNER JOIN tbl_pedido      ON tbl_pedido.pedido         = tbl_pedido_item.pedido
											WHERE tbl_os.os  = $os
											AND tbl_os_item.peca = $value";
	                    $res_pedido_faturado = pg_query($con, $sql_pedido_faturado);
	                    if(pg_num_rows($res_pedido_faturado) > 0){
	                        $qtde_faturada  = pg_fetch_result($res_pedido_faturado, 0, qtde_faturada);
	                        $qtde_faturada_distribuidor  = pg_fetch_result($res_pedido_faturado, 0, qtde_faturada_distribuidor);
	                        $qtde           = pg_fetch_result($res_pedido_faturado, 0, qtde);
	                    }

						if ($qtde_faturada == 0 and $qtde_faturada_distribuidor == 0 ) {
                            $pedido_cancela_garantia = true;

                            if (in_array($login_fabrica, $nao_fn_pedido_cancela_garantia)) {
                                $sql_embarque = "SELECT embarque
                                    FROM tbl_os_produto
                                    JOIN tbl_os_item USING(os_produto)
                                    JOIN tbl_embarque_item USING(os_item)
                                    WHERE tbl_os_produto.os = $os
                                    AND   tbl_os_item.pedido IS NOT NULL
                                    AND tbl_os_item.peca = $value
                                    AND (
                                        tbl_embarque_item.liberado IS NOT NULL
                                        OR tbl_embarque_item.impresso IS NOT NULL
                                    )";
                                $res_embarque = pg_query($con, $sql_embarque);

                                if (pg_num_rows($res_embarque) > 0) {
                                    $pedido_cancela_garantia = false;
                                }
                            }

                            if (false === $pedido_cancela_garantia) {
                                $sql_audit_0 = "SELECT tbl_auditoria_os.auditoria_status
                                    FROM tbl_auditoria_os
                                    INNER JOIN tbl_auditoria_status ON tbl_auditoria_status.auditoria_status = tbl_auditoria_os.auditoria_status
                                    WHERE os = $os
                                    AND tbl_auditoria_os.observacao = 'OS em intervenção da fábrica por Troca de Produto'
                                    AND cancelada IS NULL
                                    ORDER BY data_input DESC";
                                $res_audit_0 = pg_query($con, $sql_audit_0);

                                if (pg_num_rows($res_audit_0) == 0) {
                                    $sql_audit = "INSERT INTO tbl_auditoria_os (
                                            os,
                                            auditoria_status,
                                            observacao
                                        ) VALUES (
                                            $os,
                                            3,
                                            'OS em intervenção da fábrica por Troca de Produto'
                                        )";
                                    $res_audit = pg_query($con, $sql_audit);

                                    $auditoria_liberada = false;
                                }
                            } else {
                                $sqlVerPedidoFat = "SELECT fn_pedido_cancela_garantia(null,$login_fabrica,pedido,peca,os_item,'Produto Trocado',$login_admin)
                                    From tbl_os_produto
                                    JOIN tbl_os_item USING(os_produto)
                                    WHERE tbl_os_produto.os = $os
                                    AND   tbl_os_item.pedido NOTNULL
                                    AND tbl_os_item.peca = $value";

                                $resVerPedidoFat = pg_query($con, $sqlVerPedidoFat);
                            }
                        }

						$sql = "INSERT INTO tbl_os_troca
							(fabric, os, admin, produto, setor, situacao_atendimento, peca, observacao, causa_troca, gerar_pedido, ressarcimento, ri, envio_consumidor $campo_distribuidor $interventor_admin_campo)
							VALUES
							({$login_fabrica}, {$os}, {$login_admin}, {$produto}, '{$setor_responsavel}', {$situacao_atendimento}, {$value}, '{$observacao}', {$causa_troca}, '{$gerar_pedido}', {$ressarcimento}, '{$ri}', '{$envio_consumidor}' $value_distribuidor $interventor_admin_value)";
	                   $res = pg_query($con, $sql);

	                   	if($login_fabrica == 178){
	                   		$sql_os_revenda = "
                   				SELECT
                   					tbl_os_revenda.campos_extra,
                   					tbl_os_revenda.os_revenda
                   				FROM tbl_os_campo_extra
                   				JOIN tbl_os_revenda ON tbl_os_revenda.os_revenda = tbl_os_campo_extra.os_revenda AND tbl_os_revenda.fabrica = {$login_fabrica}
                   				WHERE tbl_os_campo_extra.os = {$os}
                   				AND tbl_os_campo_extra.fabrica = {$login_fabrica} ";
                   			$res_os_revenda = pg_query($con, $sql_os_revenda);

                   			if (pg_num_rows($res_os_revenda) > 0){
                   				$campos_extra = pg_fetch_result($res_os_revenda, 0, "campos_extra");
                   				$campos_extra = json_decode($campos_extra, true);
                   				$os_revenda = pg_fetch_result($res_os_revenda, 0, "os_revenda");

                   				if ($campos["revenda_cnpj"] AND $campos["enviar_para"] == "R"){
        							$campos_extra["os_troca_revenda_cnpj"] = $campos["revenda_cnpj"];
        						}

        						if ($campos["enviar_para"]){
        							$campos_extra["enviar_para"] = $campos["enviar_para"];
        						}

        						if (is_array($campos_extra)){
        							$campos_extra_json = json_encode($campos_extra);

        							$updateOsRevenda = "UPDATE tbl_os_revenda SET campos_extra = '$campos_extra_json' WHERE os_revenda = $os_revenda";
        							$resUpdateOsRevenda = pg_query($con, $updateOsRevenda);
        						}
                   			}

							$sql = "UPDATE tbl_os SET marca = {$campos["marca_troca"]} WHERE os = {$os}";
							$res = pg_query($con, $sql);

							if($acao == "trocar" AND $gerar_pedido != "t"){

								$sql = "SELECT tbl_estoque_posto.qtde, tbl_estoque_posto.posto 
										FROM tbl_estoque_posto 
										JOIN tbl_os ON tbl_os.posto = tbl_estoque_posto.posto AND tbl_os.fabrica = tbl_estoque_posto.fabrica
										WHERE tbl_estoque_posto.fabrica = {$login_fabrica}
										AND tbl_estoque_posto.peca = {$value}
										AND tbl_os.os = {$os}";
								$res = pg_query($con, $sql);

								if(pg_num_rows($res) > 0){

									$qtde_estoque = pg_fetch_result($res, 0, 'qtde');
									$posto_os = pg_fetch_result($res, 0, 'posto');

									if($qtde_estoque > 0){
										$sql = "UPDATE tbl_estoque_posto SET qtde = qtde - 1
												FROM tbl_os
												WHERE tbl_estoque_posto.fabrica = tbl_os.fabrica 
												AND tbl_estoque_posto.posto = tbl_os.posto 
												AND tbl_estoque_posto.peca = {$value}
												AND tbl_os.os = {$os}";										
										$res = pg_query($con, $sql);

										$sql = "INSERT INTO tbl_estoque_posto_movimento(fabrica,posto,os,peca,data,admin,qtde_saida, obs) 
												VALUES({$login_fabrica},{$posto_os},{$os},{$value},CURRENT_DATE,{$login_admin}, 1, 'Ultilizada na OS <strong>{$sua_os}</strong>')";
										$res = pg_query($con, $sql);
									}
								}
							}
						}

						if(in_array($login_fabrica, [183])){
							if ($campos["revenda_cnpj"]){
								$recomendacoes["os_troca_revenda_cnpj"] = $campos["revenda_cnpj"];
								$recomendacoes = json_encode($recomendacoes);

								$sql_up = "UPDATE tbl_os_extra SET recomendacoes = '$recomendacoes' WHERE os = {$os}";
								$res_up = pg_query($con, $sql_up);
							}
						
							$sql = "UPDATE tbl_os_extra SET faturamento_cliente_revenda = '{$campos["enviar_para"]}' WHERE os = {$os}";
							$res = pg_query($con, $sql);
						}
	                }
				}
			} else {

				if ($acao == "ressarcimento") {
					if($login_fabrica == 153){
						$gerar_pedido = 'f';
					}else{
						$peca = "null";
					}

					if ($login_fabrica == 176){
						$gerar_pedido = 'f';
					}
				}

                //PST
                //Verificar se o pedido da O.S não esta faturado, se não estiver, fazer o cancelamento do PEDIDO.
				$sql_pedido_faturado = "SELECT
                                            tbl_os_item.peca,
                                            tbl_os_item.qtde,
                                            tbl_pedido_item.qtde_faturada,
                                            tbl_pedido.pedido,
                                            tbl_os_item.os_item,
                                            tbl_os.os,
                                            tbl_pedido.status_pedido,
                                            tbl_pedido_item.qtde_faturada_distribuidor,
                                            tbl_os_item.peca,
                                            tbl_pedido_item.qtde_cancelada
                                        FROM tbl_os_item
                                        INNER JOIN tbl_os_produto on tbl_os_produto.os_produto  = tbl_os_item.os_produto
                                        INNER JOIN tbl_os on tbl_os.os = tbl_os_produto.os
                                        INNER JOIN tbl_pedido_item on tbl_os_item.pedido = tbl_pedido_item.pedido and tbl_os_item.pedido_item = tbl_pedido_item.pedido_item
                                        INNER JOIN tbl_pedido on tbl_pedido.pedido = tbl_pedido_item.pedido
					                    WHERE tbl_os.os  = $os";
                $res_pedido_faturado = pg_query($con, $sql_pedido_faturado);

                if(pg_num_rows($res_pedido_faturado) > 0){

					for($c=0;$c<pg_num_rows($res_pedido_faturado);$c++) {
						$qtde_faturada  = pg_fetch_result($res_pedido_faturado, $c, qtde_faturada);
						$qtde_cancelada = pg_fetch_result($res_pedido_faturado, $c, qtde_cancelada);
						$qtde           = pg_fetch_result($res_pedido_faturado, $c, qtde);
						$peca_cancela   = pg_fetch_result($res_pedido_faturado, $c, peca);
						$qtde_faturada_distribuidor  = pg_fetch_result($res_pedido_faturado, $c, qtde_faturada_distribuidor);

						if($qtde_faturada == 0 and $qtde_faturada_distribuidor == 0 and $qtde_cancelada == 0 ){
                            $pedido_cancela_garantia = true;

                            if (in_array($login_fabrica, $nao_fn_pedido_cancela_garantia)) {
                                $sql_embarque = "SELECT embarque
                                    FROM tbl_os_produto
                                    JOIN tbl_os_item USING(os_produto)
                                    JOIN tbl_embarque_item USING(os_item)
                                    WHERE tbl_os_produto.os = $os
                                    AND   tbl_os_item.pedido IS NOT NULL
                                    AND tbl_os_item.peca = $peca_cancela
                                    AND (
                                        tbl_embarque_item.liberado IS NOT NULL
                                        OR tbl_embarque_item.impresso IS NOT NULL
                                    )";
                                $res_embarque = pg_query($con, $sql_embarque);

                                if (pg_num_rows($res_embarque) > 0) {
                                    $pedido_cancela_garantia = false;
                                }
                            }

                            if (false === $pedido_cancela_garantia) {
                                $sqlVerPedidoFat = "SELECT fn_pedido_cancela_garantia(null,$login_fabrica,pedido,peca,os_item,'Produto Trocado',$login_admin)
                                    From tbl_os_produto
                                    JOIN tbl_os_item USING(os_produto)
                                    WHERE tbl_os_produto.os = $os
                                    AND   tbl_os_item.pedido NOTNULL
                                    AND tbl_os_item.peca = $value";

                                $resVerPedidoFat = pg_query($con, $sqlVerPedidoFat);
                            } else {
                                $sqlVerPedidoFat = "SELECT fn_pedido_cancela_garantia(null,$login_fabrica,pedido,peca,os_item,'Ressarcimento Financeiro',$login_admin)
                                    From tbl_os_produto
                                    JOIN tbl_os_item USING(os_produto)
                                    WHERE tbl_os_produto.os = $os
                                    AND		tbl_os_item.peca = $peca_cancela
                                    AND   tbl_os_item.pedido NOTNULL";
                                $resVerPedidoFat = pg_query($con, $sqlVerPedidoFat);
                            }
						}
					}
				}

				$sql = "INSERT INTO tbl_os_troca
						(fabric, os, admin, produto, setor, situacao_atendimento,  observacao, causa_troca,peca, gerar_pedido, ressarcimento, ri, envio_consumidor $campo_distribuidor)
						VALUES
						({$login_fabrica}, {$os}, {$login_admin}, {$produto}, '{$setor_responsavel}', {$situacao_atendimento},  '{$observacao}', {$causa_troca}, {$peca}, '{$gerar_pedido}', {$ressarcimento}, '{$ri}', '{$envio_consumidor}' $value_distribuidor) RETURNING os_troca";
              	$res = pg_query($con, $sql);

			}

            if (pg_last_error($con)) {
                $msg_erro["msg"]["grava_os_troca"] = "Erro ao lançar troca/ressarcimento";
				break;
			} else {
				if ($acao == "ressarcimento") {
					$os_troca = pg_fetch_result($res, 0, "os_troca");

					$nome           = trim($campos["nome_cliente"]);
					$cpf            = preg_replace("/\D/", "", $campos["cpf_cliente"]);
					$valor_original = str_replace(",", ".", str_replace(".", "", $campos["valor_ressarcimento"]));
					$agencia        =  (strlen($campos["agencia_digito"])>0) ? $campos["agencia"]."-".$campos["agencia_digito"] : $campos["agencia"] ;
					$banco          = $campos["banco"];
					$conta          = $campos["conta"]."-".$campos["conta_digito"];
					$tipo_conta     = $campos["tipo_conta"];
					list($dia, $mes, $ano) = explode("/", $campos["previsao_pagamento"]);
					$previsao_pagamento = "{$ano}-{$mes}-{$dia}";

					$xhd_chamado = (empty($hd_chamado)) ? NULL : $hd_chamado;
					$xobservacao = (empty($observacao)) ? NULL : $observacao;

					$ressarcimentoColumns = [
						"fabrica",
						"os_troca",
						"os",
						"admin",
						"nome",
						"cpf",
						"tipo_conta",
						"valor_original",
						"valor_alterado",
						"observacao",
						"hd_chamado"
					];

					$ressarcimentoValues = [
						$login_fabrica,
						$os_troca,
						$os,
						$login_admin,
						$nome,
						$cpf,
						$tipo_conta,
						$valor_original,
						$valor_original,
						$xobservacao,
						$xhd_chamado
					];

					if (!in_array($login_fabrica, [169, 170])) {
						array_push($ressarcimentoColumns, "banco", "agencia", "conta", "previsao_pagamento");
						array_push($ressarcimentoValues, $banco, $agencia, $conta, $previsao_pagamento);
					} else {
						if (strlen($banco > 0)) {
							array_push($ressarcimentoColumns, "banco");
							array_push($ressarcimentoValues, $banco);
						}

						if (strlen($agencia > 0)) {
							array_push($ressarcimentoColumns, "agencia");
							array_push($ressarcimentoValues, $agencia);
						}

						if (strlen(preg_replace("/\.|-/", "", $conta) > 0)) {
							array_push($ressarcimentoColumns, "conta");
							array_push($ressarcimentoValues, $conta);
						}

						if (strlen(preg_replace("/\.|-/", "", $previsao_pagamento) > 0)) {
							array_push($ressarcimentoColumns, "previsao_pagamento");
							array_push($ressarcimentoValues, $previsao_pagamento);
						}
					}

					$x = 0;
					$xvalues = [];
					while($x < count($ressarcimentoValues)) {
						$x++;
						array_push($xvalues, "$" . $x);
					}

					$sqlRessarcimento = "
					INSERT INTO tbl_ressarcimento (
						". implode(", ", $ressarcimentoColumns)."
					) VALUES (
						". implode(", ", $xvalues) ."
					) RETURNING ressarcimento";

					$resRessarcimento = pg_query_params($con, $sqlRessarcimento, $ressarcimentoValues);

					if (pg_last_error($con)) {
						$msg_erro["msg"]["grava_os_troca"] = "Erro ao lançar troca/ressarcimento" . pg_last_error();
						break;
					} else if ($login_fabrica == 151) {
						$ressarcimento = pg_fetch_result($resRessarcimento, 0, "ressarcimento");

						$sqlConsumidor = "SELECT
							tbl_cliente.cep,
							tbl_cliente.endereco,
							tbl_cliente.numero,
							tbl_cliente.complemento,
							tbl_cliente.bairro,
							tbl_cidade.estado,
							tbl_cidade.nome AS cidade,
							tbl_cliente.email,
							tbl_cliente.fone
							FROM tbl_cliente
							LEFT JOIN tbl_cidade ON tbl_cidade.cidade = tbl_cliente.cidade
							INNER JOIN tbl_fabrica_cliente ON tbl_fabrica_cliente.cliente = tbl_cliente.cliente
							WHERE tbl_fabrica_cliente.fabrica = {$login_fabrica}
							AND tbl_cliente.cpf = '{$cpf}'";
						$resConsumidor = pg_query($con, $sqlConsumidor);

						if (!pg_num_rows($resConsumidor)) {
							$msg_erro["msg"]["grava_os_troca"] = "Cliente não cadastrado";
							break;
						} else {
							include "../os_cadastro_unico/fabricas/151/classes/Participante.php";

							$dadosParticipante = array();

							if(strlen($cpf) > 11){
								$tipo_pessoa = "J";
							}else{
								$tipo_pessoa = "F";
							}

							$dadosParticipante["SdEntParticipante"] = array(
								"RelacionamentoCodigo"                  => "ConsumidorFinal",
								"ParticipanteTipoPessoa"                => $tipo_pessoa,
								"ParticipanteFilialCPFCNPJ"             => $cpf,
								"ParticipanteRazaoSocial"               => utf8_encode($nome),
								"ParticipanteFilialNomeFantasia"        => utf8_encode($nome),
								"ParticipanteStatus"                    => "A",
								"Enderecos"                             => array(
									array(
										"ParticipanteFilialEnderecoSequencia"   => 1,
										"ParticipanteFilialEnderecoTipo"        => "Cobranca",
										"ParticipanteFilialEnderecoCep"         => pg_fetch_result($resConsumidor, 0, "cep"),
										"ParticipanteFilialEnderecoLogradouro"  => utf8_encode(pg_fetch_result($resConsumidor, 0, "endereco")),
										"ParticipanteFilialEnderecoNumero"      => utf8_encode(pg_fetch_result($resConsumidor, 0, "numero")),
										"ParticipanteFilialEnderecoComplemento" => utf8_encode(pg_fetch_result($resConsumidor, 0, "complemento")),
										"ParticipanteFilialEnderecoBairro"      => utf8_encode(pg_fetch_result($resConsumidor, 0, "bairro")),
										"PaisCodigo"                            => 1058,
										"PaisNome"                              => "Brasil",
										"UnidadeFederativaCodigo"               => "",
										"UnidadeFederativaNome"                 => utf8_encode(pg_fetch_result($resConsumidor, 0, "estado")),
										"MunicipioNome"                         => utf8_encode(pg_fetch_result($resConsumidor, 0, "cidade")),
										"ParticipanteFilialEnderecoStatus"      => "A",
										"InscricaoEstadual"                     => "ISENTO"
									)
								),
								"Contatos"                              => array(
									array(
										"ParticipanteFilialEnderecoContatoEmail"        => utf8_encode(pg_fetch_result($resConsumidor, 0, "email")),
										"ParticipanteFilialEnderecoContatoTelefoneDDI"  => 55,
										"ParticipanteFilialEnderecoContatoTelefone"     => pg_fetch_result($resConsumidor, 0, "fone")
									)
								)
							);

							$participante = new Participante();

							$participanteRet = $participante->gravaParticipante($dadosParticipante);

							if (!is_bool($participanteRet) || (is_bool($participanteRet) && $participanteRet !== true)) {
								$msg_erro["msg"]["grava_os_troca"] = $participanteRet;
								break;
							}
						}

						$spd[] = $ressarcimento;
					}
				}

                if($login_fabrica == 151){
                    $sql_verifica_os_finalizada = "SELECT os FROM tbl_os where os = {$os} and finalizada IS NOT NULL and data_fechamento IS NOT NULL ";
                    $res_verifica_os_finalizada = pg_query($con, $sql_verifica_os_finalizada);

                    if(pg_num_rows($res_verifica_os_finalizada)>0){
                        $sql_reabrir_os = "UPDATE tbl_os SET finalizada = null, data_fechamento = null WHERE fabrica = {$login_fabrica} AND os = {$os}";
                        $res_reabrir_os = pg_query($con, $sql_reabrir_os);
					}
					$nota_fiscal = $_POST['nota_fiscal'];
					$data_nota_fiscal = $_POST['data_nota_fiscal'];
			        $data_nota_formatar = date_create_from_format('d/m/Y', $data_nota_fiscal);
			        $data_nota_fiscal = date_format($data_nota_formatar, 'Y-m-d');

					if(!empty($nota_fiscal) and !empty($data_nota_fiscal)) {
						$sqlnf = "UPDATE tbl_os set nota_fiscal = '$nota_fiscal', data_nf='$data_nota_fiscal' where os = $os and length(trim(nota_fiscal)) = 0  ";
						$resnf = pg_query($con,$sqlnf);
					}
				}

				if (count($originou_troca) > 0) {
					foreach ($originou_troca as $os_item) {
						$sql = "UPDATE tbl_os_item SET originou_troca = TRUE WHERE os_produto = {$os_produto} AND os_item = {$os_item}";
						$res = pg_query($con, $sql);

					}
				}
                //hd-2926550
                $sqlVerificaSubconjunto = "SELECT pro.produto
                                            FROM tbl_produto pro
                                            JOIN tbl_subproduto sub ON (sub.produto_pai = pro.produto OR sub.produto_filho = pro.produto)
                                            WHERE pro.fabrica_i = {$login_fabrica}
                                            AND pro.produto = {$produto}";

                $resVerificaSubconjunto = pg_query($con, $sqlVerificaSubconjunto);


                if (pg_num_rows($resVerificaSubconjunto) == 0 && $login_fabrica <> 138) {

					$sql = "SELECT servico_realizado
						FROM tbl_servico_realizado
						WHERE fabrica = {$login_fabrica}
						AND UPPER(descricao) = UPPER('CANCELADO')";
					$res = pg_query($con, $sql);

					$servico_realizado_cancela_peca = pg_fetch_result($res, 0, "servico_realizado");

					if (empty($servico_realizado_cancela_peca)) {
						$msg_erro["msg"]["servico_realizado_cancela_peca"] = "Troca de produto não configurada";
						break;
					}

					$sql = "
						SELECT tbl_os_item.os_item, tbl_os_item.pedido_item, tbl_os_item.qtde, tbl_os_item.pedido, tbl_os.posto, tbl_os.os, tbl_os_item.peca
						FROM tbl_os_item
						INNER JOIN tbl_peca ON tbl_peca.peca = tbl_os_item.peca AND tbl_peca.fabrica = {$login_fabrica}
						INNER JOIN tbl_os_produto ON tbl_os_produto.os_produto = tbl_os_item.os_produto
						INNER JOIN tbl_os ON tbl_os.os = tbl_os_produto.os AND tbl_os.fabrica = {$login_fabrica}
						LEFT JOIN tbl_pedido ON tbl_pedido.pedido = tbl_os_item.pedido AND tbl_pedido.fabrica = {$login_fabrica}
						WHERE tbl_os.os = $os
						AND (tbl_os_item.pedido IS NULL OR tbl_pedido.status_pedido IN(1, 2,12,5,14))
					";
					$res = pg_query($con, $sql);

					if (pg_num_rows($res) > 0) {
						while ($osItem = pg_fetch_object($res)) {
							$updateOsItem = "UPDATE tbl_os_item SET servico_realizado = {$servico_realizado_cancela_peca} WHERE os_item = {$osItem->os_item} ";
							$resUpdateOsItem = pg_query($con, $updateOsItem);

							if (!empty($osItem->pedido_item)) {
                                $pedido_cancela_garantia = true;

                                if (in_array($login_fabrica, $nao_fn_pedido_cancela_garantia)) {
                                    $sql_embarque = "SELECT embarque
                                        FROM tbl_os_produto
                                        JOIN tbl_os_item USING(os_produto)
                                        JOIN tbl_embarque_item USING(os_item)
                                        WHERE tbl_os_produto.os = $os
                                        AND   tbl_os_item.pedido IS NOT NULL
                                        AND tbl_os_item.os_item = {$osItem->os_item}
                                        AND (
                                            tbl_embarque_item.liberado IS NOT NULL
                                            OR tbl_embarque_item.impresso IS NOT NULL
                                        )";
                                    $res_embarque = pg_query($con, $sql_embarque);

                                    if (pg_num_rows($res_embarque) > 0) {
                                        $pedido_cancela_garantia = false;
                                    }
                                }

                                if (false === $pedido_cancela_garantia) {
                                    $sql_audit_0 = "SELECT tbl_auditoria_os.auditoria_status
                                        FROM tbl_auditoria_os
                                        INNER JOIN tbl_auditoria_status ON tbl_auditoria_status.auditoria_status = tbl_auditoria_os.auditoria_status
                                        WHERE os = $os
                                        AND tbl_auditoria_os.observacao = 'OS em intervenção da fábrica por Troca de Produto'
                                        AND cancelada IS NULL
                                        ORDER BY data_input DESC";
                                    $res_audit_0 = pg_query($con, $sql_audit_0);

                                    if (pg_num_rows($res_audit_0) == 0) {
                                        $sql_audit = "INSERT INTO tbl_auditoria_os (
                                                os,
                                                auditoria_status,
                                                observacao
                                            ) VALUES (
                                                $os,
                                                3,
                                                'OS em intervenção da fábrica por Troca de Produto'
                                            )";
                                        $res_audit = pg_query($con, $sql_audit);

                                        $auditoria_liberada = false;
                                    }
                                } else {
                                    $updatePedidoItem = "SELECT fn_pedido_cancela_garantia(null,$login_fabrica,{$osItem->pedido},{$osItem->peca},{$osItem->os_item},'Produto Trocado',$login_admin) from tbl_pedido_item
                                                        WHERE pedido_item = {$osItem->pedido_item} and qtde -(qtde_faturada+qtde_cancelada) > 0 ";
                                    $resUpdatePedidoItem = pg_query($con, $updatePedidoItem);
                                }

								$atualizaStatusPedido = "SELECT fn_atualiza_status_pedido({$login_fabrica}, {$osItem->pedido})";
								$resAtualizaStatusPedido = pg_query($con, $atualizaStatusPedido);
							}

							if (pg_last_error($con)) {
								$msg_erro["msg"]["grava_os_troca"] = "Erro ao lançar troca/ressarcimento";
								break;
							}
						}
					}
                }
				if (in_array($acao,array("trocar","base_troca"))) {
					$sql = "SELECT servico_realizado
						FROM tbl_servico_realizado
						WHERE fabrica = {$login_fabrica}
						AND troca_produto IS TRUE";					

					if($login_fabrica == 178 AND $gerar_pedido != "t"){
						$sql = "SELECT servico_realizado
						FROM tbl_servico_realizado
						WHERE fabrica = {$login_fabrica}
						AND troca_produto IS NOT TRUE
						AND gera_pedido IS NOT TRUE
						AND peca_estoque IS TRUE
						AND troca_de_peca IS TRUE
						AND ativo IS TRUE";
					}

					$res = pg_query($con, $sql);

					$servico_realizado_troca_produto = pg_fetch_result($res, 0, "servico_realizado");

					if (empty($servico_realizado_troca_produto)) {
						$msg_erro["msg"]["servico_realizado_troca_produto"] = "Troca de produto não configurada";
						break;
					}

					if (is_array($peca)) {
						foreach($peca as $key => $value) {

                            if ($login_fabrica == 145) {
                                $qtde = $produto_troca_qtde[$key];
                            } else {
                                $qtde = 1;
                            }

                            $sql = "SELECT devolucao_obrigatoria FROM tbl_peca WHERE peca = {$value} AND fabrica = {$login_fabrica}";
                            $res = pg_query($con, $sql);

                            $devolucao_obrigatoria = pg_fetch_result($res, 0, "devolucao_obrigatoria");
							$devolucao_obrigatoria = (empty($devolucao_obrigatoria)) ?"f":$devolucao_obrigatoria;

							$sql = "INSERT INTO tbl_os_item
								(os_produto, peca, qtde, servico_realizado, admin, peca_obrigatoria $campo_defeito_peca)
								VALUES
								({$os_produto}, {$value}, $qtde, {$servico_realizado_troca_produto}, {$login_admin}, '{$devolucao_obrigatoria}' $valor_defeito_peca)";
                            $res = pg_query($con, $sql);

						}
					} else {

                        $sql = "SELECT devolucao_obrigatoria FROM tbl_peca WHERE peca = {$peca} AND fabrica = {$login_fabrica}";
                        $res = pg_query($con, $sql);

                        $devolucao_obrigatoria = pg_fetch_result($res, 0, "devolucao_obrigatoria");
						$devolucao_obrigatoria = (empty($devolucao_obrigatoria)) ?"f":$devolucao_obrigatoria;

						$sql = "INSERT INTO tbl_os_item
							(os_produto, peca, qtde, servico_realizado, admin, peca_obrigatoria $campo_defeito_peca)
							VALUES
							({$os_produto}, {$peca}, 1, {$servico_realizado_troca_produto}, {$login_admin}, '{$devolucao_obrigatoria}' $valor_defeito_peca)";
                        $res = pg_query($con, $sql);
					}

                    if (pg_last_error($con)) {
                        $msg_erro["msg"]["grava_os_troca"] = "Erro ao lançar troca/ressarcimento";
						break;
					}
				}
			}
		}

		if (count($msg_erro["msg"]) == 0) {

			$mensagem = "";

			foreach ($produto_form as $os_produto => $campos) {
				$acao = $_POST["acao"][$os_produto];
				$sql = "SELECT (tbl_produto.referencia || ' - ' || tbl_produto.descricao) AS produto FROM tbl_os_produto INNER JOIN tbl_produto ON tbl_produto.produto = tbl_os_produto.produto WHERE tbl_os_produto.os_produto = {$os_produto}";
				$res = pg_query($con, $sql);

				$produto_descricao = pg_fetch_result($res, 0, "produto");

				switch ($acao) {
					case "trocar":
						if (is_array($campos["produto_troca"])) {
							$produtos_trocar_descricao = array();

							foreach ($campos["produto_troca"] as $key => $value) {
								$sql = "SELECT (referencia || ' - ' || descricao) AS produto FROM tbl_produto WHERE produto = {$value}";
                                $res = pg_query($con, $sql);

								$produtos_trocar_descricao[] = pg_fetch_result($res, 0, "produto");
							}
						} else {
							$sql = "SELECT (referencia || ' - ' || descricao) AS produto FROM tbl_produto WHERE produto = {$campos['produto_troca']}";
                            $res = pg_query($con, $sql);

                            $produtos_trocar_descricao = pg_fetch_result($res, 0, "produto");
						}

						$mensagem .= "O produto {$produto_descricao} será trocado pelo(s) produto(s) <strong>".((is_array($produtos_trocar_descricao)) ? implode(",", $produtos_trocar_descricao) : $produtos_trocar_descricao)."</strong>, as peças lançadas para este produto foram canceladas. ";
                        $mensagem .= (in_array($login_fabrica, array(165))) ? "Não será necessário o reparo do produto! <br />" : "<br />";
						break;

					case "ressarcimento":
						$mensagem .= "O produto {$produto_descricao} terá seu valor ressarcido<br />";
						break;
                    case "base_troca":
                        $peca_produto_troca = $campos['peca_produto_troca'];

                        $sql = "
                            SELECT  tbl_peca.referencia,
                                    tbl_peca.descricao,
                                    tbl_produto.valor_troca
                            FROM    tbl_peca
                            JOIN    tbl_produto USING(referencia)
                            WHERE   fabrica     = $login_fabrica
                            AND     fabrica_i   = $login_fabrica
                            AND     peca        = $peca_produto_troca
                        ";
                        $res = pg_query($con,$sql);

                        $descricao_troca  = pg_fetch_result($res,0,descricao);
                        $valor_base_troca = pg_fetch_result($res,0,valor_troca);
                        $valor_base_troca = number_format($valor_base_troca,2,',','');

                        $mensagem .= "O Produto {$produto_descricao} será trocado pelo produto ".$campos['referencia']." - ".$descricao_troca." pelo valor de R$".$valor_base_troca;
                        break;
					case "consertar":
						continue;
						break;
				}
			}

			if ($acao_troca_ressarcimento == true) {

				$sql = "SELECT sua_os, posto FROM tbl_os WHERE fabrica = {$login_fabrica} AND os = {$os}";
				$res = pg_query($con, $sql);

                $sua_os = pg_fetch_result($res, 0, "sua_os");
                $posto  = pg_fetch_result($res, 0, "posto");

                if (in_array($acao,array("trocar","ressarcimento"))) {
                    $desc = "'Troca/Ressarcimento de Produto(s) da OS {$sua_os}'";
                } else if ($acao == "base_troca") {
                    $desc = "'Troca de Produto da OS {$sua_os}'";
                }

				$sql = "INSERT INTO tbl_comunicado (
							fabrica,
							posto,
							obrigatorio_site,
							tipo,
							ativo,
							descricao,
							mensagem
						) VALUES (
							{$login_fabrica},
							{$posto},
							true,
							'Com. Unico Posto',
							true,
							$desc,
							'{$mensagem}'
						)";
				$res = pg_query($con, $sql);

			}

			if ($libera_auditoria === true && count($msg_erro["msg"]) == 0) {
				$sql = "SELECT status_os FROM tbl_os_status WHERE os= {$os} AND status_os IN (19,20,62,64,65,70,72,73,87,88,116,117,127) ORDER BY data DESC LIMIT 1";
				$res = pg_query($con,$sql);

				if(pg_num_rows($res) > 0){
					$status_os = pg_fetch_result($res,0,'status_os');

					if(in_array($status_os,array(20,62,65,72,87,116,127,70))){
						if(in_array($status_os,array(20,70))){
							$status_aprova = '19';
						}else{
							$status_aprova = '64';
						}
						$sql = "INSERT INTO tbl_os_status (os, status_os, observacao, admin) VALUES ({$os}, $status_aprova, 'Troca/Ressarcimento efetuado', {$login_admin})";
						$res = pg_query($con,$sql);
					}

				}

				$sql = "INSERT INTO tbl_os_status(os, status_os, observacao,admin ) SELECT os, 187, 'Aprovada peça excedente por troca do produto', $login_admin FROM tbl_os_status where os = $os and status_os = 118";
				$res = pg_query($con, $sql);

                if (pg_last_error($con)) {
                    $msg_erro["msg"]["grava_os_troca"] = "Erro ao lançar troca/ressarcimento";
				}
			}

			if ($login_fabrica == 151 AND strlen($hd_chamado) > 0) {

				$sqlOS = "SELECT tbl_os.sua_os,tbl_os.os, tbl_os.tipo_atendimento
								FROM
								tbl_hd_chamado_item
								JOIN tbl_os ON tbl_hd_chamado_item.os=tbl_os.os AND tbl_os.finalizada IS NULL
								WHERE
								tbl_hd_chamado_item.hd_chamado={$hd_chamado}";
				$resOS = pg_query($con, $sqlOS);
                if(pg_num_rows($resOS) > 0){
                	$os_mensagem = array();
                	$tipo_atendimento = array();
                	$resultado   = pg_fetch_all($resOS);
                	foreach ($resultado as $keyResultado => $valueResultado) {
                		$os_mensagem[] = $valueResultado['sua_os'];
                		if ($valueResultado['tipo_atendimento'] == 336) {
                			$tipo_atendimento[] = $valueResultado['tipo_atendimento'];
                		} else {
                			
							$sql = "UPDATE tbl_os SET
									finalizada = CURRENT_TIMESTAMP,
									data_fechamento = CURRENT_TIMESTAMP
								WHERE fabrica = {$login_fabrica}
								AND os = {$valueResultado['os']}";
							$res = pg_query($con, $sql);

							if (pg_last_error($con)) {
								$msg_erro["msg"]["grava_os_troca"] = "Erro ao lançar troca/ressarcimento";
							}
                		}
					}

					if (!count($msg_erro)) {
						$os_finalizada = true;
					}

					if (in_array(336, $tipo_atendimento)) {
						$os_finalizada = false;
					}
				}
			}
			if (in_array($login_fabrica, array(169,170))) {
				$sqlPostoInterno = "
					SELECT o.os
					FROM tbl_os o
					INNER JOIN tbl_posto_fabrica pf ON pf.posto = o.posto AND pf.fabrica = {$login_fabrica}
					INNER JOIN tbl_tipo_posto tp ON tp.tipo_posto = pf.tipo_posto AND tp.fabrica = {$login_fabrica}
					WHERE o.os = {$os}
					AND tp.posto_interno IS TRUE
				";
				$resPostoInterno = pg_query($con, $sqlPostoInterno);

				if (pg_num_rows($resPostoInterno) > 0) {
					$sql = "
						UPDATE tbl_os SET
							finalizada = CURRENT_TIMESTAMP,
							data_fechamento = CURRENT_TIMESTAMP,
                            baixada = NULL
						WHERE fabrica = {$login_fabrica}
						AND os = {$os}
					";
					$res = pg_query($con, $sql);

					if (strlen(pg_last_error()) > 0) {
						$msg_erro["msg"]["grava_os_troca"] = "Erro ao lançar troca/ressarcimento";
					}
				}

				if(!empty($defeito_constatado) && !in_array($acao,array("trocar","base_troca"))) {

					$sql = "INSERT INTO tbl_os_defeito_reclamado_constatado (os, defeito_constatado,fabrica $campo_defeito_peca) values ($os, $defeito_constatado,$login_fabrica $valor_defeito_peca) ; ";
					$res = pg_query($con,$sql);
					if (strlen(pg_last_error()) > 0) {
						$msg_erro["msg"]["grava_os_troca"] = "Erro ao lançar troca/ressarcimento";
					}
				}
			}

			if (in_array($login_fabrica, array(178))) {
				$sqlPostoInterno = "
					SELECT o.os
					FROM tbl_os o
					INNER JOIN tbl_posto_fabrica pf ON pf.posto = o.posto AND pf.fabrica = {$login_fabrica}
					INNER JOIN tbl_tipo_posto tp ON tp.tipo_posto = pf.tipo_posto AND tp.fabrica = {$login_fabrica}
					WHERE o.os = {$os}
					AND tp.posto_interno IS TRUE
				";
				$resPostoInterno = pg_query($con, $sqlPostoInterno);

				if (pg_num_rows($resPostoInterno) > 0) {

					$sql = "SELECT os_produto 
							FROM tbl_os_produto 
							WHERE os = {$os}
							AND defeito_constatado IS NOT NULL";
            		$res = pg_query($con, $sql);

            		if (pg_num_rows($res) > 0) {

						$sql = "
							UPDATE tbl_os SET
								finalizada = CURRENT_TIMESTAMP,
								data_fechamento = CURRENT_TIMESTAMP,
								data_conserto = CURRENT_TIMESTAMP
							WHERE fabrica = {$login_fabrica}
							AND os = {$os};

							UPDATE tbl_auditoria_os
							SET liberada = current_timestamp,
								admin = {$login_admin}
							WHERE os = {$os}
							AND liberada IS NULL
							AND cancelada IS NULL
							AND reprovada IS NULL;
						";
						$res = pg_query($con, $sql);

						if (strlen(pg_last_error()) > 0) {
							$msg_erro["msg"]["grava_os_troca"] = "Erro ao lançar troca/ressarcimento";
						}

					} else {

						$msg_erro["msg"]["grava_os_troca"] = "Informe o defeito constatado para fechar a ordem de serviço";

					}
				}

			}

            if(!in_array($login_fabrica, array(165,169,170))){
				$sql_solucao = "SELECT solucao
								FROM tbl_solucao
								WHERE fabrica = $login_fabrica
								AND troca_produto IS TRUE ";
				$res_solucao = pg_query($con, $sql_solucao);

				if (pg_last_error($con)) {
					$msg_erro["msg"]["grava_os_troca"] = "Erro ao selecionar Solução.";
				}
				if(pg_num_rows($res_solucao) > 0){
					$solucao_os = pg_fetch_result($res_solucao, 0, 'solucao');

					$sqlDefeito = "SELECT defeito_constatado
							FROM tbl_defeito_constatado
							WHERE fabrica = $login_fabrica
							AND descricao ~* 'TROCA DE PRODUTO'";
					$resDefeito = pg_query($con, $sqlDefeito);
					if (pg_last_error($con)) {
						$msg_erro["msg"]["grava_os_troca"] = "Erro ao selecionar Defeito Constatado.";
					}
					if(pg_num_rows($resDefeito) > 0){
						$id_defeito = pg_fetch_result($resDefeito, 0, 'defeito_constatado');
					}else{
						$sqlIn = "INSERT INTO tbl_defeito_constatado (
										fabrica, descricao
									)VALUES(
										$login_fabrica, 'TROCA DE PRODUTO'
									)";
						$resIn = pg_query($con, $sqlIn);
						if (pg_last_error($con)) {
							$msg_erro["msg"]["grava_os_troca"] = "Erro ao inserir Defeito Constatado.";
						}
						$res = @pg_query ($con,"SELECT CURRVAL ('seq_defeito_constatado')");
						$id_defeito  = pg_fetch_result ($res,0,0);
					}

					if(in_array($login_fabrica, array(138, 142, 145))) {
						$sqlUp = "UPDATE tbl_os_produto set defeito_constatado = $id_defeito
									WHERE os = $os AND defeito_constatado IS NULL";
						$resUp = pg_query($con, $sqlUp);
						if (pg_last_error($con)) {
							$msg_erro["msg"]["grava_os_troca"] = "Erro ao atualizar solucação.";
						}

						$sqlUp2 = "UPDATE tbl_os set solucao_os = $solucao_os
									WHERE os = $os AND fabrica = $login_fabrica
									AND solucao_os IS NULL";
						$resUp2 = pg_query($con, $sqlUp2);
						if (pg_last_error($con)) {
							$msg_erro["msg"]["grava_os_troca"] = "Erro ao atualizar solução.";
						}

					}else{
						$sqlUp = "UPDATE tbl_os set solucao_os = $solucao_os,
									defeito_constatado = $id_defeito
									WHERE os = $os AND fabrica = $login_fabrica
									AND solucao_os IS NULL
									AND defeito_constatado IS NULL";
						$resUp = pg_query($con, $sqlUp);
						if (pg_last_error($con)) {
							$msg_erro["msg"]["grava_os_troca"] = "Erro ao atualizar solução.";
						}
					}
					if($auditoria_unica == true and $auditoria_liberada == true){
						$sqlAud = "UPDATE tbl_auditoria_os SET liberada = now(), admin = $login_admin WHERE os = $os AND liberada IS NULL AND bloqueio_pedido is true";
						$res_aud = pg_query($con, $sqlAud);
						if (pg_last_error($con)) {
							$msg_erro["msg"]["grava_os_troca"] = "Erro ao liberar da auditoria.";
						}
					}
					//Altera o status para liberado .
					$sql = "SELECT status_os FROM tbl_os_status WHERE os=$os AND status_os IN (19,20,62,64,65,72,73,87,88,116,117,127,199,200) ORDER BY data DESC LIMIT 1";
					$res = pg_query($con,$sql);
					$qtdex = pg_num_rows($res);
					if ($qtdex>0){
					    $observacao = 'OS Liberada';
					    $statuss=pg_fetch_result($res,0,status_os);
					    $status_arr = array(20,62,65,72,87,116,127,199);
					    if (in_array($statuss,$status_arr)){

					        $proximo_status = "64";

					        if( $statuss == "72"){
					            $proximo_status = "73";
					        }
					        if( $statuss == "87"){
					            $proximo_status = "88";
					        }
					        if( $statuss == "116"){
					            $proximo_status = "117";
					        }
					        if( $statuss == "20"){
					            $proximo_status = "19";
					        }

					        if( $statuss == "199"){
					        	$proximo_status = "200";
					        }

					        $sql = 'INSERT INTO tbl_os_status
					        (os,status_os,data,observacao,admin)
					        VALUES ($1,$2,current_timestamp,$3,$4)';
					        $params = array($os,$proximo_status,$observacao,$login_admin);
					        $res = pg_query_params($con,$sql,$params);

					        if (pg_last_error($con)) {
								$msg_erro["msg"]["grava_os_troca"] = "Erro ao liberar da auditoria.";
							}
					    }
					}
				}
            }



			// Fim hd_chamado=3245566
			if (count($msg_erro["msg"]) == 0) {
				$sql = "SELECT fn_os_status_checkpoint_os({$os}) AS status";
				$res = pg_query($con, $sql);

				$status = pg_fetch_result($res, 0, "status");

				$sql = "UPDATE tbl_os SEt status_checkpoint = $status WHERE fabrica = {$login_fabrica} AND os = {$os}";
				$res = pg_query($con, $sql);

                if (pg_last_error($con)) {
                    $msg_erro["msg"]["grava_os_troca"] = "Erro ao lançar troca/ressarcimento";
				}
			}

			if (in_array($login_fabrica, array(35)) && strtolower($acao) == "trocar") {//hd-3919692

				$sqlSolucao = "SELECT solucao FROM tbl_solucao WHERE descricao = 'Troca do produto' AND fabrica = $login_fabrica";
				$resSolucao = pg_query($con, $sqlSolucao);
				if (pg_last_error($con)) {
					$msg_erro["msg"]["grava_os_troca"] = "Erro ao atualizar solução.";
				}

				if (pg_num_rows($resSolucao)) {
					$solucao_troca_produto = pg_fetch_result($resSolucao, 0, 'solucao');
					$sqlUpSolucao = "UPDATE tbl_os 
								        SET solucao_os = $solucao_troca_produto
								      WHERE os = $os 
								        AND fabrica = $login_fabrica";
					$resUpSolucao = pg_query($con, $sqlUpSolucao);
					if (pg_last_error($con)) {
						$msg_erro["msg"]["grava_os_troca"] = "Erro ao atualizar solução.";
					}
				} else {
					$msg_erro["msg"]["grava_os_troca"] = "Erro ao atualizar solução.";
				}
			}

			if (in_array($login_fabrica, array(169,170))) {
				if (strtolower($acao) == "trocar") {
					$sqlAuditoria = "
						SELECT ao.auditoria_os
						FROM tbl_auditoria_os ao
						INNER JOIN tbl_auditoria_status a ON a.auditoria_status = ao.auditoria_status
						WHERE ao.os = {$os}
						AND a.produto IS TRUE
						AND ao.liberada IS NULL
						AND ao.reprovada IS NULL
						AND ao.cancelada IS NULL
						AND ao.observacao = 'OS em auditoria de troca de produto'
					";
					$resAuditoria = pg_query($con, $sqlAuditoria);

					if (!pg_num_rows($resAuditoria)) {
						$sqlAuditoriaProduto = "
							SELECT auditoria_status FROM tbl_auditoria_status WHERE produto IS TRUE
						";
						$resAuditoriaProduto = pg_query($con, $sqlAuditoriaProduto);

						$auditoria_status = pg_fetch_result($resAuditoriaProduto, 0, 'auditoria_status');

						$insertAuditoria = "
							INSERT INTO tbl_auditoria_os
							(os, auditoria_status, observacao)
							VALUES
							({$os}, {$auditoria_status}, 'OS em auditoria de troca de produto')
						";
						$resInsertAuditoria = pg_query($con, $insertAuditoria);

						if (strlen(pg_last_error()) > 0) {
							$msg_erro["msg"][] = "Erro ao gravar auditoria #1";
						}
					}
				} else if (strtolower($acao) == "ressarcimento") {
					$sqlAuditoria = "
						SELECT ao.auditoria_os
						FROM tbl_auditoria_os ao
						INNER JOIN tbl_auditoria_status a ON a.auditoria_status = ao.auditoria_status
						WHERE ao.os = {$os}
						AND a.produto IS TRUE
						AND ao.liberada IS NULL
						AND ao.reprovada IS NULL
						AND ao.cancelada IS NULL
						AND ao.observacao = 'OS em auditoria de ressarcimento'
					";
					$resAuditoria = pg_query($con, $sqlAuditoria);

					if (!pg_num_rows($resAuditoria)) {
						$sqlAuditoriaProduto = "
							SELECT auditoria_status FROM tbl_auditoria_status WHERE produto IS TRUE
						";
						$resAuditoriaProduto = pg_query($con, $sqlAuditoriaProduto);

						$auditoria_status = pg_fetch_result($resAuditoriaProduto, 0, 'auditoria_status');

						$insertAuditoria = "
							INSERT INTO tbl_auditoria_os
							(os, auditoria_status, observacao)
							VALUES
							({$os}, {$auditoria_status}, 'OS em auditoria de ressarcimento')
						";
						$resInsertAuditoria = pg_query($con, $insertAuditoria);

						if (strlen(pg_last_error()) > 0) {
							$msg_erro["msg"][] = "Erro ao gravar auditoria #2";
						}

						$sqlAuditoria = "
							SELECT ao.auditoria_os
							FROM tbl_auditoria_os ao
							INNER JOIN tbl_auditoria_status a ON a.auditoria_status = ao.auditoria_status
							WHERE ao.os = {$os}
							AND a.produto IS TRUE
							AND ao.liberada IS NULL
							AND ao.reprovada IS NULL
							AND ao.cancelada IS NULL
							AND ao.observacao = 'OS em auditoria de troca de produto'
						";
						$resAuditoria = pg_query($con, $sqlAuditoria);

						if (pg_num_rows($resAuditoria) > 0) {
							$auditoria_os = pg_fetch_result($resAuditoria, 0, 'auditoria_os');

							$cancelaAuditoria = "
								UPDATE tbl_auditoria_os SET
									cancelada = CURRENT_TIMESTAMP,
									admin = {$login_admin},
									justificativa = 'Cancelamento automático por ter sido realizado um ressarcimento'
								WHERE auditoria_os = {$auditoria_os}
							";
							$resCanceladaAuditoria = pg_query($con, $cancelaAuditoria);

							if (strlen(pg_last_error()) > 0) {
								$msg_erro["msg"][] = "Erro ao gravar auditoria #3";
							}
						}
					}
				}
			}

			if (!count($msg_erro["msg"]) AND $hd_chamado AND $login_fabrica == 151) {

				include "../class/sms/sms.class.php";
				$sms = new SMS();

				// function textoProvidencia($texto, $hd_chamado,$consumidor_nome,$numero_objeto){
				// 	$alteracoes["[_consumidor_]"] = $consumidor_nome;
				// 	$alteracoes["[_protocolo_]"]  = $hd_chamado;
				// 	$alteracoes["[_rastreio_]"]   = $numero_objeto;
				// 	foreach ($alteracoes as $key => $value) {
				// 		$texto = str_replace($key, $value, $texto);
				// 	}

				// 	return $texto;
				// }


				$sql ="SELECT nome, email, celular FROM tbl_hd_chamado_extra WHERE hd_chamado = {$hd_chamado}";
				$res = pg_query($con, $sql);
				$nome_cliente    = pg_fetch_result($res, 0, "nome");
				$email_cliente   = pg_fetch_result($res, 0, "email");
				$celular_cliente = pg_fetch_result($res, 0, "celular");

				$sql = "SELECT sua_os FROM tbl_os WHERE fabrica = {$login_fabrica} AND os = {$os}";
				$res = pg_query($con,$sql);
				$sua_os =pg_fetch_result($res,0, "sua_os");

                if (empty($msg_erro["msg"])) {

                    if ($login_fabrica == 151) {
                        $sql_prov = "SELECT hd_motivo_ligacao FROM tbl_hd_chamado_extra WHERE hd_chamado = $hd_chamado;";
                        $res_prov = pg_query($con,$sql_prov);

                        if (pg_num_rows($res) > 0) {
                            $hd_motivo_ligacao_ant = pg_fetch_result($res_prov, 0, hd_motivo_ligacao);
                        }
                    }

    				if($acao == "trocar"){

    					$sql = "SELECT  prazo_dias,
							descricao, texto_email, 
							texto_sms  
						FROM tbl_hd_motivo_ligacao 
						WHERE hd_motivo_ligacao = 78 ";
    					$res = pg_query($con, $sql);

    					$prazo_dias = pg_fetch_result($res, 0, "prazo_dias");
    					$texto_email = pg_fetch_result($res, 0, "texto_email");
    					$texto_sms = pg_fetch_result($res, 0, "texto_sms");
    					$descricao_providencia = pg_fetch_result($res, 0, "descricao");

    					if(strlen($email_cliente)>0 and strlen($nome_cliente)>0){

    						if(strlen($texto_email)>0){
    							$text =  textoProvidencia($texto_email,$hd_chamado, $nome_cliente);
    							$header = "From: Telecontrol <noreply@telecontrol.com.br>";
    							mail($email_cliente,utf8_encode('Protocolo de atendimento Mondial '.$hd_chamado),utf8_encode($text), $header );
    						}

    					}

    					if(strlen($nome_cliente)>0 and strlen($celular_cliente)>0){
    						if(strlen($texto_sms)){
    							$text =  textoProvidencia($texto_sms,$hd_chamado, $nome_cliente);
								$sms->enviarMensagem($celular_cliente, $sua_os, '', $text) ;
							}
    					}
					$sql = "select fn_calcula_previsao_retorno(current_date,prazo_dias,$login_fabrica)::date AS data_providencia from tbl_hd_motivo_ligacao where hd_motivo_ligacao = 78";
					$resP = pg_query($con,$sql);
					$data_de_providencia = pg_fetch_result($resP, 0, 'data_providencia');

    					$sql = "UPDATE tbl_hd_chamado SET data_providencia = '{$data_de_providencia}' where hd_chamado = {$hd_chamado} " ;
    					$res = pg_query($con,$sql);

    					if(strlen(pg_last_error() > 0 )) {
    						$msg_erro['msg']['grava_os_troca'] = "Erro ao Alterar data de providência";
    					}

                        $hd_motivo_ligacao_up = 78;
    					$sql = "UPDATE tbl_hd_chamado_extra SET
    						hd_motivo_ligacao = 78
    						WHERE hd_chamado = {$hd_chamado}";
    					$res = pg_query($con, $sql);

    					if (strlen(pg_last_error()) > 0) {
    						$msg_erro["msg"]["grava_os_troca"] = "Erro ao alterar providência";
    					}

    					if (!empty($os_mensagem) &&  $login_fabrica == 151) {
    						$mensagem = "Foi realizada a troca do produto na Ordem de Serviço {$os}, as seguintes OS  foram finalizadas: <b>(".implode(',', $os_mensagem).")</b> e a providência do atendimento alterada para $descricao_providencia";
    					} else {

                            if(in_array($login_fabrica, array(165))){
                                $mensagem = "Foi realizada a troca do produto na Ordem de Serviço {$os} e a providência do atendimento alterada para $descricao_providencia. Não será necessário o reparo do produto!";
                            }else{
                                $mensagem = "Foi realizada a troca do produto na Ordem de Serviço {$os} e a providência do atendimento alterada para $descricao_providencia";
                            }

    					}

    				}else if ($acao == "ressarcimento") {

    					$sql = "SELECT fn_calcula_previsao_retorno(current_date,prazo_dias,$login_fabrica)::date AS data_providencia,
    							descricao, texto_email, texto_sms FROM tbl_hd_motivo_ligacao WHERE hd_motivo_ligacao = 80 ";
    					$res = pg_query($con, $sql);

    					$data_de_providencia = pg_fetch_result($res, 0, "data_providencia");
    					$texto_email = pg_fetch_result($res, 0, "texto_email");
    					$texto_sms = pg_fetch_result($res, 0, "texto_sms");
    					$descricao_providencia = pg_fetch_result($res, 0, "descricao");


    					if(strlen($email_cliente)>0 and strlen($nome_cliente)>0){
    						if(strlen($texto_email)>0){
    							$text =  textoProvidencia($texto_email,$hd_chamado, $nome_cliente);
    							$header = "From: Telecontrol <noreply@telecontrol.com.br>";
    							mail($email_cliente,utf8_encode('Protocolo de atendimento Mondial '.$hd_chamado),utf8_encode($text), $header );
    						}
    					}
    					if(strlen($nome_cliente)>0 and strlen($celular_cliente)>0){
    						if(strlen($texto_sms)){
                                $text =  textoProvidencia($texto_sms,$hd_chamado, $nome_cliente);
                                $sms->enviarMensagem($celular_cliente, $sua_os, '', $text) ;
    						}
    					}

    					$sql = "UPDATE tbl_hd_chamado SET data_providencia = '{$data_de_providencia}' where hd_chamado = {$hd_chamado} " ;
    					$res = pg_query($con,$sql);

    					if(strlen(pg_last_error() > 0 )){
    						$msg_erro['msg']['grava_os_troca'] = "Erro ao Alterar data de providência";
    					}

                        $hd_motivo_ligacao_up = 80;
    					$sql = "UPDATE tbl_hd_chamado_extra SET
    						hd_motivo_ligacao = 80
    						WHERE hd_chamado = {$hd_chamado}";
    					$res = pg_query($con, $sql);

    					if (strlen(pg_last_error()) > 0) {
    						$msg_erro["msg"]["grava_os_troca"] = "Erro ao alterar providência";
    					}

    					$mensagem = "Foi realizado o ressarcimento financeiro na Ordem de Serviço {$os} e a providência do atendimento alterada para $descricao_providencia";

    				}

    				$sql = "SELECT status FROM tbl_hd_chamado WHERE hd_chamado = {$hd_chamado}";
    				$res = pg_query($con,$sql);
    				$status_item = pg_fetch_result($res, 0, 'status');

    				$sql = "INSERT INTO tbl_hd_chamado_item
    					(hd_chamado, interno, comentario, status_item, admin)
    					VALUES
    					({$hd_chamado}, TRUE, '{$mensagem}', '{$status_item}', {$login_admin})";
    				$res = pg_query($con, $sql);

    				if (strlen(pg_last_error()) > 0) {
    					$msg_erro["msg"][] = "Erro ao interagir ao gravar interação no atendimento {$hd_chamado}";
    				}else{

    					$sql = "SELECT tbl_os.sua_os, tbl_os.posto
    							FROM tbl_os
    							JOIN tbl_posto_fabrica ON tbl_os.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = {$login_fabrica}
    							JOIN tbl_tipo_posto ON tbl_posto_fabrica.tipo_posto = tbl_tipo_posto.tipo_posto AND tbl_tipo_posto.fabrica = {$login_fabrica}
    							WHERE tbl_os.fabrica = {$login_fabrica}
    							AND os = {$os}
    							AND tbl_tipo_posto.posto_interno IS NOT TRUE";
    					$res = pg_query($con, $sql);

    					if(pg_num_rows($res) > 0){
    						$sua_os = pg_fetch_result($res, 0, "sua_os");
    						$posto  = pg_fetch_result($res, 0, "posto");

    						$sql = "INSERT INTO tbl_comunicado (
    									fabrica,
    									posto,
    									obrigatorio_site,
    									tipo,
    									ativo,
    									descricao,
    									mensagem
    								) VALUES (
    									{$login_fabrica},
    									{$posto},
    									true,
    									'Com. Unico Posto',
    									true,
    									'Troca de Produto(s) da OS {$sua_os}',
    									'{$mensagem}'
    								)";
    						$res = pg_query($con, $sql);
    					}
    				}
                }
			}

			if(!count($msg_erro["msg"]) && $auditoria_unica == true and $auditoria_liberada == true){
				if (in_array($login_fabrica, array(169,170))) {
					$whereAuditoria = "AND observacao !~ 'OS em auditoria de troca de produto|OS em auditoria de ressarcimento'";
				}

				$sqlAuditoria = "
					UPDATE tbl_auditoria_os SET
						liberada = CURRENT_TIMESTAMP
					WHERE os = $os
					AND liberada IS NULL
					AND cancelada IS NULL
					AND reprovada IS NULL
					AND bloqueio_pedido IS TRUE
					{$whereAuditoria}
				";
				pg_query($con,$sqlAuditoria);

				if(strlen(pg_last_error()) > 0){
					$msg_erro["msg"][] = "Erro ao liberar auditoria da OS";
				}
			}
			
			if (in_array($login_fabrica, [35]) && strtolower($acao) == "trocar") {

				$sqlPrecoProduto = "SELECT DISTINCT tbl_tabela_item.preco
									FROM tbl_os_produto
									JOIN tbl_produto ON tbl_os_produto.produto = tbl_produto.produto
									JOIN tbl_peca ON UPPER(tbl_peca.referencia) = UPPER(tbl_produto.referencia)
									AND tbl_peca.produto_acabado IS TRUE
									JOIN tbl_tabela_item ON tbl_tabela_item.peca = tbl_peca.peca
									JOIN tbl_tabela ON tbl_tabela.tabela = tbl_tabela_item.tabela
									AND tbl_tabela.tabela_garantia IS TRUE
									WHERE tbl_os_produto.os = {$os}
									";
				$resPrecoProduto = pg_query($con, $sqlPrecoProduto);
				
				$precoProduto = (float) pg_fetch_result($resPrecoProduto, 0, 'preco');
				
				if (pg_num_rows($resPrecoProduto) > 0 && $precoProduto >= 200) {
					
					$sqlAuditoria = "
							SELECT ao.auditoria_os
							FROM tbl_auditoria_os ao
							INNER JOIN tbl_auditoria_status a ON a.auditoria_status = ao.auditoria_status
							WHERE ao.os = {$os}
							AND a.produto IS TRUE
							AND ao.liberada IS NULL
							AND ao.reprovada IS NULL
							AND ao.cancelada IS NULL
							AND ao.observacao = 'OS em auditoria de troca de produto'
						";
					$resAuditoria = pg_query($con, $sqlAuditoria);

					if (!pg_num_rows($resAuditoria)) {
						$sqlAuditoriaProduto = "
							SELECT auditoria_status FROM tbl_auditoria_status WHERE produto IS TRUE
						";
						$resAuditoriaProduto = pg_query($con, $sqlAuditoriaProduto);

						$auditoria_status = pg_fetch_result($resAuditoriaProduto, 0, 'auditoria_status');

						$insertAuditoria = "
							INSERT INTO tbl_auditoria_os
							(os, auditoria_status, observacao)
							VALUES
							({$os}, {$auditoria_status}, 'OS em auditoria de troca de produto')
						";
						$resInsertAuditoria = pg_query($con, $insertAuditoria);

						if (strlen(pg_last_error()) > 0) {
							$msg_erro["msg"][] = "Erro ao gravar auditoria #1";
						}
					}

				}

			}

			if ($login_fabrica == 151 && !count($msg_erro["msg"]) && isset($spd) && count($spd) > 0) {
				include "../os_cadastro_unico/fabricas/151/classes/NotaFiscalServico.php";
				$NotaFiscalServico = new NotaFiscalServico($login_fabrica);

				foreach ($spd as $ressarcimento) {
					$spdRetorno = json_decode($NotaFiscalServico->gravaDespesaWs(null, $ressarcimento), true);

					if ($spdRetorno["SdRetSPD"]["SdErro"]["ErroCod"] == 1) {
						$pos = strpos($spdRetorno["SdRetSPD"]["SdErro"]["ErroCod"], 'encontra-se cadastrado em outro SPD');
						if($pos !== false){
							$msg_erro["msg"][] = utf8_decode($spdRetorno["SdRetSPD"]["SdErro"]["ErroDesc"]);
							break;
						}
					}
				}
			}

            // Efetuar Cancelamento de Pedido na Send
            if (in_array($login_fabrica, array(151)) && !count($msg_erro["msg"])) {
                $sqlVerPedidoExp = "SELECT DISTINCT pi.pedido_item, p.pedido, case when pi.qtde = pi.qtde_cancelada then true else false end as cancelada
                                    FROM tbl_os_produto op
                                    JOIN tbl_os o USING(os)
                                    JOIN tbl_os_item oi ON op.os_produto = oi.os_produto
                                    INNER JOIN tbl_pedido_item pi USING(pedido)
                                    LEFT JOIN tbl_pedido p ON pi.pedido = p.pedido AND p.fabrica = {$login_fabrica}
                                    LEFT JOIN tbl_faturamento_item fi ON pi.pedido_item = fi.pedido_item
				    LEFT JOIN tbl_pedido_cancelado ON pi.pedido_item = tbl_pedido_cancelado.pedido_item
                                    WHERE o.fabrica = {$login_fabrica}
                                    AND fi.faturamento IS NULL
									AND p.exportado IS NOT NULL
									AND tbl_pedido_cancelado.exportado isnull
                                    AND op.os = {$os};";
                $resVerPedidoExp = pg_query($con, $sqlVerPedidoExp);

                $pedidosExportados = pg_fetch_all($resVerPedidoExp);

				if (count($pedidosExportados) > 0 ) {
					foreach ($pedidosExportados as $pedidoItem) {
						if($pedidoItem['cancelada']) {
							$retorno_cancelamento_send = $cancelaPedidoClass->cancelaPedidoItem($pedidoItem['pedido'], $pedidoItem['pedido_item'], "Produto foi Ressarcido/Trocado para o cliente");
							$pos = strpos($retorno_cancelamento_send,'OT possui quantidade faturada');

							if (!is_bool($retorno_cancelamento_send) AND $pos === false){
								$msg_erro["msg"][] = $retorno_cancelamento_send;
								$erro_send = true;
							}else{
								$sqlEx = "UPDATE tbl_pedido_cancelado set exportado = now() WHERE pedido_item = ". $pedidoItem['pedido_item'] ;
								pg_query($con, $sqlEx);
							}
						}
                    			}
                		}
		}

			if($login_fabrica == 165){
				$sql = "UPDATE tbl_os SET status_checkpoint = fn_os_status_checkpoint_os({$os}) WHERE os = {$os}";
				$res = pg_query($con,$sql);
			}

			if ($login_fabrica == 173 && $acao <> 'ressarcimento') {
        		$retorno_serie = numero_de_serie_jfa_obrigatorio();
        		if ($retorno_serie == 'ok') {
					$updateOsExtra = "UPDATE tbl_os_extra SET serie_justificativa = '{$_REQUEST['novo_n_serie']}' WHERE os = {$os}";
					$resOsExtra = pg_query($con, $updateOsExtra);
	        		if (pg_last_error()) {
	        			$msg_erro["msg"]["grava_os_troca"] = "Erro Número de Série";		
	        		}
	            } else {
	            	$msg_erro["msg"]["grava_os_troca"] = $retorno_serie;
	            }
        	}

			if ($login_fabrica == 178 && $acao <> 'ressarcimento') {
				$updateOsExtra = "UPDATE tbl_os_extra SET faturamento_cliente_revenda = '{$_POST["enviar_para"][$os_produto]}' WHERE os = {$os}";
				$resOsExtra = pg_query($con, $updateOsExtra);
			}

			if (!count($msg_erro["msg"]) && $login_fabrica == 151 && $_POST['posto_status'] == 'DESCREDENCIADO') {
				$sql = "UPDATE tbl_os SET troca_garantia = true, troca_garantia_admin = $login_admin WHERE os = $os ANd fabrica = $login_fabrica";
				$res = pg_query($con, $sql);
				if (pg_last_error()) {
					$msg_erro["msg"]["update_troca_garantia"] = "Erro";	
				}
			}

			if (in_array($login_fabrica, [186]) && $acao === "trocar" && strlen($gerar_pedido_com_at = $_POST['gerar_pedido_com_at']) > 0) {
				$query_tipo_atendimento = "SELECT
					tbl_os.tipo_atendimento
				FROM tbl_tipo_atendimento
				JOIN tbl_os ON tbl_os.tipo_atendimento = tbl_tipo_atendimento.tipo_atendimento
				WHERE tbl_os.os = $1
				AND tbl_os.fabrica = $2
				AND tbl_tipo_atendimento.fora_garantia IS FALSE";

				$res_tipo_atendimento = pg_query_params($con, $query_tipo_atendimento, [$os, $login_fabrica]);
				if (pg_num_rows($res_tipo_atendimento) > 0) {
					$query_os_campo_extra = "SELECT
						campos_adicionais
					FROM tbl_os_campo_extra
					WHERE os = $1
					AND fabrica = $2";

					$res_os_campo_extra = pg_query_params($con, $query_os_campo_extra, [$os, $login_fabrica]);
					if (pg_num_rows($res_os_campo_extra) === 1) {
						$extra_campos_adicionais = pg_fetch_result($res_os_campo_extra, 0, "campos_adicionais");
						$extra_campos_adicionais = json_decode($extra_campos_adicionais, true);

						$extra_campos_adicionais["tipo_gera_pedido"] = $gerar_pedido_com_at;
						$extra_campos_adicionais = json_encode($extra_campos_adicionais);

						$query_update_campo_extra = "UPDATE tbl_os_campo_extra SET
							campos_adicionais = $1
						WHERE os = $2
						AND fabrica = $3";

						$res_update_campo_extra = pg_query_params($con, $query_update_campo_extra, [$extra_campos_adicionais, $os, $login_fabrica]);
						if (strlen(pg_last_error()) > 0) {
							$msg_erro["msg"][] = "Falha ao atualizar dados da OS";
						}
					} else {
						$extra_campos_adicionais = json_encode(["tipo_gera_pedido" => $gerar_pedido_com_at]);

						$query_update_campo_extra = "INSERT INTO tbl_os_campo_extra (os, fabrica, campos_adicionais) VALUES ($1, $2, $3)";
						
						$res_update_campo_extra = pg_query_params($con, $query_update_campo_extra, [$os, $login_fabrica, $extra_campos_adicionais]);
						if (strlen(pg_last_error()) > 0) {
							$msg_erro["msg"][] = "Falha ao inserir dados da troca";
						}

					}
				}
			}

			if (!count($msg_erro["msg"])) {
				if(in_array($login_fabrica, array(169,170)) && strlen($hd_chamado) > 0){
					switch ($setor_responsavel) {
						case 'revenda':
							$setor_descricao = "Revenda";
							break;
						case 'carteira':
							$setor_descricao = "Carteira";
							break;
						case 'sac':
							$setor_descricao = "Sac";
							break;
						case 'procon':
							$setor_descricao = "Procon";
							break;
						case 'sap':
							$setor_descricao = "Sap";
							break;
						case 'suporte_tecnico':
							$setor_descricao = "Superte Técnico";
							break;
					}

					if (strtolower($acao) == "trocar") {
						$msg_interacao = "Foi realizada a troca do produto. <br/> Setor Responsável: $setor_descricao";
					}else if(strtolower($acao) == "ressarcimento"){
						$msg_interacao = "Foi realizado um Ressarcimento. <br/> Setor Responsável: $setor_descricao";
					}

					$sql = "
						SELECT tbl_hd_chamado.status, tbl_hd_chamado_extra.nome, tbl_hd_chamado_extra.celular
						FROM tbl_hd_chamado
						JOIN tbl_hd_chamado_extra ON tbl_hd_chamado_extra.hd_chamado = tbl_hd_chamado.hd_chamado
							AND tbl_hd_chamado.fabrica = $login_fabrica
						WHERE tbl_hd_chamado.hd_chamado = {$hd_chamado}";
    				$res = pg_query($con,$sql);

    				if (pg_num_rows($res) > 0){
    					$status_item = pg_fetch_result($res, 0, 'status');
	    				$celular_cliente = pg_fetch_result($res, 0, "celular");
	    				$nome_cliente    = pg_fetch_result($res, 0, "nome");

						$sql = "INSERT INTO tbl_hd_chamado_item
	    					(hd_chamado, interno, comentario, status_item, admin)
	    					VALUES
	    					({$hd_chamado}, TRUE, '{$msg_interacao}', '{$status_item}', {$login_admin})";
	    				$res = pg_query($con, $sql);

						/*include "../class/sms/sms.class.php";
						$sms = new SMS();

						if(strlen($nome_cliente) > 0 and strlen($celular_cliente) > 0){

							if(strtolower($acao) == 'trocar'){
								$sql = "
									SELECT nome
									FROM tbl_posto
									JOIN tbl_os ON tbl_os.posto = tbl_posto.posto
										AND tbl_os.fabrica = {$login_fabrica}
									WHERE os = $os";
								$res = pg_query($con, $sql);
								$posto_nome = pg_fetch_result($res, 0, 'nome');

								$texto_sms = "Informamos que o produto da OS: $os será trocado e enviado para o posto autorizado $posto_nome";
							}else if (strtolower($acao) == 'ressarcimento'){
								$texto_sms = "Informamos que será realizado o ressarcimento do valor referente a OS: $os entre em contato com a fábrica esclarecimentos";
							}

							if(strlen($texto_sms)){
								$sms->enviarMensagem($celular_cliente, $os, '', $texto_sms) ;
							}
						}*/
					}
    			}

				if (in_array($login_fabrica, array(169, 170))) {
					$sql = "
    						SELECT ta.km_google
    						FROM tbl_os o
    						INNER JOIN tbl_tipo_atendimento ta ON ta.tipo_atendimento = o.tipo_atendimento AND ta.fabrica = {$login_fabrica}
    						WHERE o.fabrica = {$login_fabrica}
    						AND o.os = {$os}
    						AND ta.km_google IS TRUE
					";
					$res = pg_query($con, $sql);

					if (pg_num_rows($res) > 0) {
						$sql = "SELECT tecnico_agenda, confirmado FROM tbl_tecnico_agenda WHERE fabrica = {$login_fabrica} AND os = {$os} ORDER BY tecnico_agenda DESC LIMIT 1;";
    						$res = pg_query($con, $sql);

    						if (pg_num_rows($res) == 0) {
							$data_agendamento = date("Y-m-d H:i:s");
        						$sql = "
            							INSERT INTO tbl_tecnico_agenda
            							(fabrica, admin, os, data_agendamento, ordem, confirmado, periodo)
            							VALUES
            							({$login_fabrica}, {$login_admin}, {$os}, '{$data_agendamento}', 1, '{$data_agendamento}', 'manha')
        						";
     							$res = pg_query($con, $sql);

        						if (strlen(pg_last_error()) > 0) {
            							$msg_erro["msg"][] = "Erro ao gravar agendamento";
        						}
   						} else {
							$confirmado = pg_fetch_result($res, 0, "confirmado");
							$tecnico_agenda = pg_fetch_result($res, 0, "tecnico_agenda");
							if (empty($confirmado) && !empty($tecnico_agenda)) {
								$upd = "UPDATE tbl_tecnico_agenda SET confirmado = now() WHERE tecnico_agenda = {$tecnico_agenda};";
								$res = pg_query($con, $upd);
								if (strlen(pg_last_error()) > 0) {
									$msg_erro["msg"][] = "Erro ao gravar agendamento";
								}
							}
						}
					}
				}

				pg_query($con, "COMMIT");

				if ($login_fabrica == 141) {

		            $sqlUltimaOs = "SELECT tbl_os.os,
		            					   tbl_hd_chamado_extra.email,
		            					   tbl_os.os_numero
		                            FROM tbl_os
		                            JOIN tbl_os_revenda ON tbl_os_revenda.os_revenda = tbl_os.os_numero
		                            JOIN tbl_hd_chamado_extra USING(os)
		                            WHERE
		                            (
		                                SELECT os_numero FROM tbl_os
		                                WHERE os = {$os}
		                                LIMIT 1
		                            ) = tbl_os.os_numero
		                            ORDER BY tbl_os.os_sequencia DESC
		                            LIMIT 1";
		            $resUltimaOs = pg_query($con, $sqlUltimaOs);

		            if (pg_num_rows($resUltimaOs) > 0) {

		                $email_atendimento = pg_fetch_result($resUltimaOs, 0, 'email');
		                $os_revenda_numero = pg_fetch_result($resUltimaOs, 0, 'os_numero');

		                $sqlProdutoTrocado = "SELECT 
		                						tbl_produto.referencia || ' - ' || tbl_produto.descricao as produto_trocado
		            						 FROM tbl_os
		            						 JOIN tbl_produto USING(produto)
		            						 WHERE os = {$os}";
		            	$resProdutoTrocado = pg_query($con, $sqlProdutoTrocado);

		                $produto_trocado   = pg_fetch_result($resProdutoTrocado, 0, 'produto_trocado');
		                
		                $assunto  = "Serviço de Atendimento UNICOBA";
		                $mensagem = "Ordem de serviço {$os_revenda_numero} teve o produto {$produto_trocado} trocado pela fábrica";

		                if(strlen(trim($email_atendimento))>0){
		                    $mailTc = new TcComm('smtp@posvenda');

		                    $mailTc->sendMail(
		                        $email_atendimento,
		                        $assunto,
		                        $mensagem,
		                        'noreply@telecontrol.com.br'
		                    );
		                }

		            }

		        }

			    if ($telecontrol_distrib && (!isset($novaTelaOs) || (in_array($login_fabrica, [160]) or $replica_einhell))) {
			        if ($troca_produto == 'NULL') {
			        	atualiza_status_checkpoint($os, "Aguardando Conserto");
			        } else {
			        	atualiza_status_checkpoint($os, "Produto Trocado");
			        }
			    }

                if($login_fabrica == 35){

                    $sql = "SELECT email, nome_completo FROM tbl_admin WHERE admin = $login_admin AND fabrica = $login_fabrica";
                    $res = pg_query($con, $sql);
                    if(pg_num_rows($res)>0){
                        $email_admin = pg_fetch_result($res, 0, email);  

                        $mensagem_email_admin = "Foi lançada uma troca para a O.S $os que será aprovada e poderá gerar o pedido de troca. ";

                        $mailTc = new TcComm($externalId);
                        $res = $mailTc->sendMail(
                            $email_admin,
                            "Troca de Produto - O.S $os",
                            $mensagem_email_admin,
                            $externalEmail
                        );
                    }
                }

				$anexo = $_POST["anexo"];

				if (!empty($anexo)) {
					$ext = preg_replace("/.+\./", "", $anexo);

					$arquivo = array();

					$arquivo[] = array(
						"file_temp" => $anexo,
						"file_new"  => "{$os}_comprovante_troca.{$ext}"
					);

					$s3->moveTempToBucket($arquivo);
				}

				if(in_array($login_fabrica, array(151))){
                    if ($hd_motivo_ligacao_ant != $hd_motivo_ligacao_up ) {
                        $sql_email = "SELECT destinatarios, texto_email_admin
                                        FROM tbl_hd_motivo_ligacao
                                        WHERE destinatarios is not null
                                            AND fabrica = {$login_fabrica}
                                            AND hd_motivo_ligacao = $hd_motivo_ligacao_up;";
                        $res_email = pg_query($con,$sql_email);

                        if (pg_num_rows($res_email) > 0) {
                            $destinatario = pg_fetch_result($res_email, 0, 'destinatarios');
                            $destinatario = json_decode($destinatario,true);
                            $destinatario = implode(";", $destinatario);

                            $texto_email_admin = pg_fetch_result($resP, 0, 'texto_email_admin');
                            if (!empty($texto_email_admin)) {
                                $texto_email_admin =  textoProvidencia($texto_email_admin,$hd_chamado, $nome_cliente);
                            }

                        }
                        $text =  "Providência Alterada!";
                        $header = "From: Telecontrol <noreply@telecontrol.com.br>";
                        mail($destinatario,utf8_encode('Alteração de providência no atendimento '.$hd_chamado),utf8_encode($texto_email_admin), $header );
                    }

                    $hdChamado = "";
					if($hd_chamado) {
						$hdChamado = "&hdChamado=".$hd_chamado;
					}
					try {
						if ($os_finalizada) {
							foreach ($resultado as $keyResultado => $valueResultado) {
								$classOs = new \Posvenda\Os($login_fabrica, $valueResultado['os']);

								$classOs->calculaOs();
							}
						}

						header("Location: os_press.php?os={$os}&troca={$acao}{$hdChamado}");

					} catch(Exception $e) {

						$msg_erro["msg"][] = $e->getMessage();;

		            }

				}else{					

					if(in_array($login_fabrica, [173]) and $acao <> 'ressarcimento'){
						$sql_f = "UPDATE tbl_os SET
		                      finalizada = now(),
		                      data_fechamento = now()
		                      WHERE fabrica = {$login_fabrica}
		                      AND os = {$os}";
			            $res_f = pg_query($con, $sql_f);
			            if (strlen(pg_last_error()) > 0) {
			                $msg_erro["msg"]["grava_os_troca"] = "Erro ao finalizar a O.S";
			            }
					}

				    if (in_array($login_fabrica, [174])) {
			        	atualiza_status_checkpoint($os, "Produto Trocado");
			        }

                    if (($login_fabrica <> 160 && $acao <> 'ressarcimento') || $login_fabrica == 191 ) {
					   header("Location: os_press.php?os={$os}");
                    }
				}

				if ($login_fabrica <> 160 && $acao <> 'ressarcimento' and !$replica_einhell) {
				    exit;
                }
			} else {
				pg_query($con, "ROLLBACK");
			}
		} else {
			pg_query($con, "ROLLBACK");
		}
	}

	/*if ( in_array($login_fabrica, [173]) ) {
		if (numero_de_serie_jfa_obrigatorio()) {
			$updateOsExtra = "UPDATE tbl_os_extra SET serie_justificativa = '{$_REQUEST['novo_n_serie']}' WHERE os = {$os}";
			pg_query($con, $updateOsExtra);
			$msg_erro[] = pg_last_error();
		} else {
			$msg_erro["msg"]["grava_os_troca"] = "Erro Número de Série";		
		}
	}*/

    if (empty($msg_erro)) {
        if ((in_array($login_fabrica, array(160,177)) && $acao == 'ressarcimento') or ($replica_einhell && $login_fabrica != 187)) {
            $sql_f = "UPDATE tbl_os SET
                      finalizada = now(),
                      data_fechamento = now()
                      WHERE fabrica = {$login_fabrica}
                      AND os = {$os}";
            $res_f = pg_query($con, $sql_f);
            if (strlen(pg_last_error()) > 0) {
                $msg_erro["msg"]["grava_os_troca"] = "Erro ao lançar troca/ressarcimento";
            } else {
                try {
                    $classOs = new \Posvenda\Os($login_fabrica, $os, $con);
                    $classOs->calculaOs();
                    header("Location: os_press.php?os={$os}");
                    exit;
                } catch(Exception $e) {
                    $msg_erro["msg"][] = $e->getMessage();;
                }
            }
        }
    }
}

if ($login_fabrica == 151) {
	if(!empty($hd_chamado)) {
		$condHD = " AND tbl_hd_chamado_extra.hd_chamado = $hd_chamado ";
	}
	$sqlAtendimento = "SELECT nome, cpf, hd_chamado FROM tbl_hd_chamado_extra WHERE os = {$os} $condHD";
	$resAtendimento = pg_query($con, $sqlAtendimento);

	if (pg_num_rows($resAtendimento) == 0) {
		$sqlAtendimento = "SELECT nome, cpf, hd_chamado FROM tbl_hd_chamado_extra JOIN tbl_hd_chamado_item USING(hd_chamado) WHERE tbl_hd_chamado_item.os = {$os} $condHD";
		$resAtendimento = pg_query($con, $sqlAtendimento);

	}

	if (pg_num_rows($resAtendimento) > 0) {
		$hd_chamado       = pg_fetch_result($resAtendimento, 0, hd_chamado);
		$hd_chamado_nome  = pg_fetch_result($resAtendimento, 0, nome);
		$hd_chamado_cpf   = pg_fetch_result($resAtendimento, 0, cpf);
	}

	$sqlOs = "SELECT data_abertura FROM tbl_os WHERE os = {$os};";
	$resOs = pg_query($con,$sqlOs);
	if (pg_num_rows($resOs) > 0) {
		$dataOS = pg_fetch_result($resOs, 0, data_abertura);

		$data1 = new DateTime($dataOS);
		$qtde_dias = $data1->diff(new DateTime())->format('%a');
	}
}

if (in_array($login_fabrica, array(169,170))) {
    $hd_chamado = $_REQUEST["hd_chamado"];
}

if(in_array($login_fabrica, [178,183])){
	$sql_marcas_produto = "SELECT 	tbl_os.marca AS marca_os,
					tbl_os.troca_garantia,
					tbl_os.consumidor_revenda,
					tbl_os.revenda_cnpj,
					tbl_os.revenda_nome,
					tbl_os.consumidor_nome,
					tbl_os.consumidor_cpf,
					tbl_os_extra.faturamento_cliente_revenda AS enviar_para,
					tbl_produto.parametros_adicionais::jsonb->>'marcas' AS marcas_produto,
					tbl_produto.referencia,
					tbl_produto.parametros_adicionais::jsonb->>'fora_linha' AS fora_linha,
					tbl_os_revenda.campos_extra->>'enviar_para' AS troca_enviar_para,
					tbl_os_revenda.campos_extra->>'os_troca_revenda_cnpj' AS os_troca_revenda_cnpj,
					tbl_produto.familia
				FROM tbl_os
				JOIN tbl_produto USING(produto)
				JOIN tbl_os_extra USING(os)
				JOIN tbl_os_campo_extra ON tbl_os_campo_extra.os = tbl_os.os AND tbl_os_campo_extra.fabrica = {$login_fabrica}
				JOIN tbl_os_revenda ON tbl_os_revenda.os_revenda = tbl_os_campo_extra.os_revenda AND tbl_os_revenda.fabrica = {$login_fabrica}
				WHERE tbl_produto.fabrica_i = {$login_fabrica}
				AND tbl_os.fabrica = {$login_fabrica}
				AND tbl_os.os = {$os}";
	$res_marcas_produto = pg_query($con, $sql_marcas_produto);

	$troca_garantia = pg_fetch_result($res_marcas_produto,0,'troca_garantia');
    $enviar_para = pg_fetch_result($res_marcas_produto,0,'enviar_para');
    $marcas = str_replace('"','',pg_fetch_result($res_marcas_produto,0,'marcas_produto'));
    $marcaOS = pg_fetch_result($res_marcas_produto,0,'marca_os');
    $familia_produto = pg_fetch_result($res_marcas_produto,0,'familia');
    $os_consumidor_revenda = pg_fetch_result($res_marcas_produto, 0, 'consumidor_revenda');
    $revenda_cnpj = pg_fetch_result($res_marcas_produto, 0, 'revenda_cnpj');
    $revenda_nome = pg_fetch_result($res_marcas_produto, 0, 'revenda_nome');
    $consumidor_nome = pg_fetch_result($res_marcas_produto, 0, 'consumidor_nome');
    $consumidor_cpf  = pg_fetch_result($res_marcas_produto, 0, 'consumidor_cpf');
    $consumidor_nome = (empty($consumidor_nome)) ? $revenda_nome : $consumidor_nome;
    $consumidor_cpf = (empty($consumidor_cpf)) ? $revenda_cnpj : $consumidor_cpf;
    $troca_enviar_para = pg_fetch_result($res_marcas_produto, 0, "troca_enviar_para");
    $xos_troca_revenda_cnpj = pg_fetch_result($res_marcas_produto, 0, "os_troca_revenda_cnpj");
    $fora_linha = pg_fetch_result($res_marcas_produto, 0, "fora_linha");
    $xprod_referencia = pg_fetch_result($res_marcas_produto, 0, "referencia");

    if (!empty($xos_troca_revenda_cnpj)){
   	 	$sqlRevenda = "SELECT nome, cnpj FROM tbl_revenda WHERE cnpj = '{$xos_troca_revenda_cnpj}'";
    	$resRevenda = pg_query($con, $sqlRevenda);
    	
    	if (pg_num_rows($resRevenda) > 0){
    		$revenda_cnpjx = pg_fetch_result($resRevenda, 0, 'cnpj');
			$revenda_nomex = pg_fetch_result($resRevenda, 0, 'nome');
			$readOnlyRevenda = "readOnly";
    	}
    }

    if (!empty($revenda_cnpjx)){
    	$revenda_cnpj = $revenda_cnpjx;
		$revenda_nome = $revenda_nomex;
    }

}

include_once "class/aws/s3_config.php";
include_once S3CLASS;

$layout_menu = "callcenter";
$title       = "TROCA DE PRODUTO DA ORDEM DE SERVIÇO";

include __DIR__.'/cabecalho_new.php';

$plugins = array(
   "alphanumeric",
   "autocomplete",
   "jquery_multiselect",
   "shadowbox",
   "ajaxform",
   "mask",
   "datepicker",
   "price_format",
   "select2"
);

include __DIR__."/plugin_loader.php";

if($login_fabrica == 151){
    $sql = "SELECT parametros_adicionais
                FROM tbl_admin
            WHERE fabrica = $login_fabrica
            and admin = $login_admin";
    $res = pg_query($con, $sql);
    if(pg_num_rows($res)>0){
        $parametros_adicionais = pg_fetch_result($res, 0, parametros_adicionais);
        if(strlen(trim($parametros_adicionais))>0){
            $parametros_adicionais = json_decode($parametros_adicionais, true);
            if($parametros_adicionais['troca_reembolso'] != 't'){ ?>
                <br />
                <div class="alert alert-error"><h4>Usuário sem permissão para Troca/ Reembolso</h4></div>
                <?php
                include "rodape.php";
                exit;
            }
        }
    }
}

?>

<style>

.ms-container {
	width: 92%;
	margin: 0 auto;
}

div.troca, div.troca_ressarcimento, div.ressarcimento, div.gera_pedido, div.base_troca {
	display: none;
}

span.select2-container {
	width: 100% !important;
}

span.select2-dropdown {
	width: 330px !important;
}

li.select2-results__option {
	border-bottom: 1px solid #ddd;
	margin-bottom: 3px;
}

</style>

<script>

function retorna_produto(retorno) {
    var compara = [];
    var login_fabrica = <?=$login_fabrica?>;
    var tipo_troca = $("input[name^=acao]:checked").val();

    $("#produto-troca-" + retorno.retornaIndice + " > option").each(function(k,val){
        compara.push(this.value);
    });

    if (tipo_troca != "base_troca") {
        if ($.inArray(retorno.produto,compara) == -1) {
            $("div.produto-selecionado-"+retorno.retornaIndice).find("ul").append("\
                <li class='active' style='float: none;' data-produto-id='"+retorno.produto+"'  data-produto-referencia='"+retorno.referencia+"' data-os-produto='"+retorno.retornaIndice+"'  >\
                <a href='#' class='remover-produto' ><button type='button' class='btn btn-danger btn-mini' ><i class='icon-remove icon-white'></i></button> "+retorno.referencia+" - "+retorno.descricao+"</a>\
                </li>\
            ");

            $("#produto-troca-"+retorno.retornaIndice).append("\
                <option value='"+retorno.produto+"' selected >"+retorno.referencia+"</value>\
            ");

            if (login_fabrica == 145) {
                $("div.produto-selecionado-"+retorno.retornaIndice).find(".troca_qtde").append("\
                <input type='text' style='height:40px;' class='span4 numeric' maxlength='3' name='produto_troca_qtde["+retorno.retornaIndice+"][]' id='produto_troca_qtde_"+retorno.produto+"' value='' /><br id='pular_"+retorno.produto+"' />");

            }

            <?php if($login_fabrica == 178){?>
            		verificaEstoqueRoca();
            <?php } ?>
        } else {
            alert("Esse produto já está na lista de troca para essa OS");
        }
    } else {
        $("input[name^=referencia]").val(retorno.referencia);
        $("input[name^=produto_descricao]").val(retorno.descricao);
        $("input[name^=valor_base_troca]").val(retorno.valor_troca);
    }
}

$(function() {
    var login_fabrica = <?=$login_fabrica?>;
    var telecontrol_distrib = '<?=$telecontrol_distrib?>';

   	if($("#cpf_radio").is('checked')){
   		$("#cpf_cnpj").mask("99.999.999/9999-99");
   	}else{
   		$("#cpf_cnpj").mask("999.999.999-99");
   	}

    $("input[name='consumidor[cnpjCpf]']").change(function(){
        var tipo = $(this).val();
        $("#cpf_cnpj").unmask();
        if(tipo == 'cnpj'){
            $("#cpf_cnpj").mask("99.999.999/9999-99");
        }else{
            $("#cpf_cnpj").mask("999.999.999-99");
        }
    });

    $("input.numeric").numeric();
    $("#data_pagamento").datepicker({ dateFormat: "dd/mm/yy" }).mask("99/99/9999");
    $("#previsao_pagamento").datepicker({ minDate: 0, dateFormat: "dd/mm/yy" }).mask("99/99/9999");
    $("select.banco").select2();


    $(document).on("click", "a.remover-produto", function(e) {
        e.preventDefault();
        var li = $(this).parents("li");
        var produto = $(li).data("produto-id");
        var os_produto = $(li).data("os-produto");
        $("#produto-troca-"+os_produto).find("option[value="+produto+"]").first().remove();
        $(li).remove();

        if (login_fabrica == 145) {
            $("#produto_troca_qtde_"+produto).remove();
            $("#pular_"+produto).remove();
        }
    });

	$("select.pecas").multiSelect();

	Shadowbox.init();

	$("span[rel=lupa]").click(function () {
		if (login_fabrica == 165 && $(this).parents("div.informacoes_produto").find("input[type=radio][name^=acao]:checked").val() == 'trocar') {
			$.lupa($(this), ["listaTroca", "retornaIndice"], undefined, undefined, 0);
		} else if(login_fabrica == 178){
			$.lupa($(this), ["listaTroca", "retornaIndice","marca","familia"]);
		}else {
			$.lupa($(this), ["listaTroca", "retornaIndice"]);
		}
	});
	var qtde_dias = $("#qtde_dias").val();
	//console.log(qtde_dias);
	if(login_fabrica == 151) {
        if (qtde_dias <= 29) {
            var os_troca = <?=$os?>;
            setTimeout(function() {
                // Shadowbox.init();
                Shadowbox.open({
                    content: "verifica_interventor.php?os_troca_subconjunto=TRUE&os_troca="+os_troca ,
                    player: "iframe",
                    width: 700,
                    height: 250,
                    options: {
                        modal: true,
                        enableKeys: false
                    }
                });
                $("#sb-nav").css({ display: "none" });
            },
            1000);
        }
	}

    if (telecontrol_distrib) {
        $("input[type=radio][name=troca_produto]").change(function() {
            if ($('input[name="troca_produto"]:checked').val() == '4311') {
                $('input[name="gerar_pedido"]').removeAttr("disabled");
                $('input[name="gerar_pedido"]').prop("checked", "checked");
            } else {
                $('input[name="gerar_pedido"]').removeAttr("checked");
                $('input[name="gerar_pedido"]').prop("disabled", "disabled");
            }
        });
    }

	$("input[type=radio][name^=acao]").change(function() {
        var divProduto  = $(this).parents("div.informacoes_produto");
        var osProduto   = $("input[name^=os_produto]").val();
        var acao        = $(divProduto).find("input[type=radio][name^=acao]:checked").val();

		switch(acao) {
			case "trocar":
                $(divProduto).find("div.troca_ressarcimento, div.troca").show();
                $(divProduto).find("div.ressarcimento").hide();
                $(divProduto).find("div.base_troca").hide();

                if (telecontrol_distrib) {
                    if ($('input[name="troca_produto"]:checked').val() == '') {
                        $('input[name="gerar_pedido"]').removeAttr("checked");
                        $('input[name="gerar_pedido"]').prop("disabled", "disabled");
                    }
                }

                if (login_fabrica == 165) {
                    $.each($('input[name=lupa_config]'), function() {
                        if ($(this).attr('tipo') == 'produto') {
                            $(this).attr('listaTroca', true);
                        }
                    });
                }

                if (login_fabrica == 178) {
                	verificaEstoqueRoca();
                }

                break;

			case "base_troca":
				$(divProduto).find("div.troca_ressarcimento, div.troca").show();
				$(divProduto).find("div.ressarcimento").hide();
				$(divProduto).find("div.base_troca").hide();

                if (telecontrol_distrib) {
                    if ($('input[name="troca_produto"]:checked').val() == '') {
                        $('input[name="gerar_pedido"]').removeAttr("checked");
                        $('input[name="gerar_pedido"]').prop("disabled", "disabled");
                    }
                }
                if (login_fabrica == 165) {

                    $("div.produto-selecionado-"+osProduto).hide();
                    $("div.troca_ressarcimento").hide();
                    $("div.base_troca").show();

                    $.each($('input[name=lupa_config]'), function() {
                        if ($(this).attr('tipo') == 'produto') {
                            $(this).removeAttr('listaTroca');
                        }
                    });

                }
				break;

			case "ressarcimento":
				$(divProduto).find("div.troca_ressarcimento, div.ressarcimento").show();
				$(divProduto).find("div.troca").hide();
				break;

			case "consertar":
				$(divProduto).find("div.troca_ressarcimento, div.troca, div.ressarcimento").hide();
				break;
		}

		if ($("input[type=radio][name^=acao]:checked").length > 1) {
			var mostra_gera_pedido = false;

			$("input[type=radio][name^=acao]:checked").each(function() {
				if ($(this).val() == "trocar") {
					mostra_gera_pedido = true;
					return false;
				}
			});

			if (mostra_gera_pedido == true) {
				$("div.gera_pedido").show();
			} else {
				$("div.gera_pedido").hide();
			}
		} else {
			if (acao == "trocar") {
				$("div.gera_pedido").show();
			} else {
				$("div.gera_pedido").hide();
			}
		}
	});

	$("select[name^=familia]").change(function() {
		var familia = $(this).val();
		var selectProduto = $(this).parents("div.row-fluid").find("select[name^=produto]");

		$.ajax({
			url: "os_troca_subconjunto.php",
			type: "get",
			data: { ajax_busca_produto: true, familia: familia },
			beforeSend: function() {
				$(selectProduto).hide().before("<div class='alert alert-info' style='margin-bottom: 0px;'>Carregando produtos, aguarde...</div>");
				$(selectProduto).find("option").first().nextAll().remove();
			}
		}).always(function(data) {
			data = $.parseJSON(data);

			if (data.error) {
				alert(data.error);
			} else {
				$.each(data.produtos, function(key, produto) {
					var option = $("<option></option>", { value: produto.id, text: produto.referencia_descricao });
					$(selectProduto).append(option);
				});
			}

			$(selectProduto).show().prev("div.alert-info").remove();
		});
	});

	if (login_fabrica == 162) {
        $("button[name=pagar]").click(function(e){
            e.preventDefault();

            var data_pagamento = $("#data_pagamento").val();
            var os  = <?=$os?>;
            if (data_pagamento.length > 0) {
                $.ajax({
                    url:"os_troca_subconjunto.php",
                    type:"POST",
                    dataType:"JSON",
                    data:{
                        ajax_data_pagamento:true,
                        data_pagamento:data_pagamento,
                        os:os
                    }
                })
                .done(function(data){
                    if (data.ok) {
                        location.reload();
                    }
                });
            }
        });
	}
	<?php
	if (in_array($login_fabrica, array(138,165))) {
	?>
		$("form[name=form_anexo]").ajaxForm({
	        complete: function(data) {
				data = $.parseJSON(data.responseText);

				if (data.error) {
					alert(data.error);
				} else {
					$("#div_anexo").find("img.anexo_thumb").attr({ src: data.link });
			       $("#div_anexo").find("input[rel=anexo]").val(data.arquivo_nome);
				}

				$("#div_anexo").find("img.anexo_loading").hide();
				$("#div_anexo").find("button").show();
				$("#div_anexo").find("img.anexo_thumb").show();
	    	}
	    });

		$("button[name=anexar]").click(function() {
			$("input[name=anexo_upload]").click();
		});

		$("input[name=anexo_upload]").change(function() {
			$("#div_anexo").find("button").hide();
			$("#div_anexo").find("img.anexo_thumb").hide();
			$("#div_anexo").find("img.anexo_loading").show();

			$(this).parent("form").submit();
	    });
	<?php
	}
	?>

	if(login_fabrica == 178){

		$("#marca_torca").change(function(){
			addParametrosLupa();
		});

		addParametrosLupa();
	}
});


<?php if($login_fabrica == 178){ ?>

function verificaEstoqueRoca(){

	let referencia = "";

    $("ul.produtos_troca").find("li").each(function(){

    	referencia = $(this).data("produto-referencia");   
    
	    $.ajax({
	        url: "os_troca_subconjunto.php",
	        type: "POST",
	        dataType:"JSON",
	        data: {ajax_estoque_roca: true, referencia: referencia, os: <?=$os?>}
	    }).done(function(data){
	        if(data.qtde > 0){
	            alert("Identificamos que o Posto Autorizado possui em estoque o produto selecionado para a troca. A opção para Gerar Pedido foi desmarcada. Caso queira gerar pedido para a troca, selecione novamente a opção.");

	            $("#gerar_pedido").attr({"checked":false});
	        }
	    });
    });
}
<?php } ?>

function valida_interventor(os_troca) {
    setTimeout(function() {
        Shadowbox.open({
            content: "verifica_interventor.php?os_troca_subconjunto=TRUE&os_troca="+os_troca ,
            player: "iframe",
            width: 700,
            height: 250,
            options: {
                modal: true,
                enableKeys: false
            }
        });
        $("#sb-nav").css({ display: "none" });
    },
    1000);
	}

function addParametrosLupa(){

	var marca = $("#marca_troca").val();
	var familia = '<?=$familia_produto?>';
	$("input[name^=lupa_config]").attr({"marca":marca,"familia":familia});
}

function retorna_interventor(retorno) {
	$("#interventor_admin").val(retorno);
}

function defeitoPeca() {
	var defeito_constatado = $("#produto_defeito_constatado").val();

	$("#div_produto_defeito_peca").show();
        $("#produto_defeito_peca").val("").find("option").first().nextAll().remove();

	if (defeito_constatado != null && defeito_constatado.length > 0) {
		$("#produto_defeito_peca").prop({ disabled: true }).find("option").first().text("Carregando Defeitos");

		$.ajax({
			url: window.location,
			type: "get",
			data: { ajax_busca_defeito_peca: true, defeito_constatado: defeito_constatado },
			timeout: 60000,
			async: true
		}).fail(function(res) {
			$("#produto_defeito_peca").prop({ disabled: false }).find("option").first().text("Selecione");
			alert("Erro ao carregar defeitos");
		}).done(function(res) {
			res = JSON.parse(res);

			if (res.erro) {
				$("#produto_defeito_peca").prop({ disabled: false }).find("option").first().text("Selecione");
	                        alert(res.erro);
			} else {
				$("#produto_defeito_peca").prop({ disabled: false }).find("option").first().text("Selecione");

				res.defeitos.forEach(function(value, key) {
					var option = $("<option></option>", {
						value: value.defeito,
						text: value.descricao
					});

					$("#produto_defeito_peca").append(option);
				});
			}
		});
	}
}

</script>

<?php
if(count($msg_erro["msg"]) > 0) {
    $msg = array_unique($msg_erro["msg"]);
?>
	<br />
	<div class="alert alert-error"><h4><?=implode("<br />", $msg)?></h4></div>
<?php
}

if ($login_fabrica == 151 && empty($hd_chamado)) {	
?>
	<br />
	<div class="alert alert-error"><h4>É necessário um protocolo para realizar uma troca de produto/ressarcimento</h4></div>
	<?php
	include "rodape.php";
	exit;
}

if ($login_fabrica == 178 AND $fora_linha == "true"){
?>
	<div class="alert alert-info"><h4>Produto: <?=$xprod_referencia?> está fora de linha</h4></div>
<?php	
}

if (in_array($login_fabrica, array(169, 170, 178))) {
?>
    <p class="tac" >
	<a href="cadastro_os.php?os_id=<?=$os?>" class="btn btn-info" target="_blank" >Incluir/Alterar dados na Ordem de Serviço</a>
    </p>
<?php
}
?>

<div class="row">
	<b class="obrigatorio pull-right"> * Campos obrigatórios </b>
</div>

<form name="frm_os" method="POST" class="form-search form-inline tc_formulario" enctype="multipart/form-data" >
	<div class="titulo_tabela">Trocar Produto em Garantia</div>
	<input type="hidden" name="hd_chamado" value="<?=$hd_chamado?>" />
	<input type="hidden" id="qtde_dias" name="qtde_dias" value="<?=$qtde_dias?>" />
	<input type="hidden" id="autoriza_gravacao" name="autoriza_gravacao" value="" />
	<input type="hidden" name="posto_status" id="posto_status" value="" />

	<? if ($login_fabrica == 151) {
		$campoExtra = "CASE
				WHEN length(tbl_hd_chamado_item.nota_fiscal) > 1  THEN
					tbl_hd_chamado_item.nota_fiscal
				WHEN length(tbl_hd_chamado_extra.nota_fiscal) > 1 THEN
					tbl_hd_chamado_extra.nota_fiscal
				END AS hd_nota_fiscal,
				CASE
				 WHEN tbl_hd_chamado_item.data_nf IS NOT NULL  THEN
					 TO_CHAR(tbl_hd_chamado_item.data_nf, 'DD/MM/YYYY')
				 WHEN tbl_hd_chamado_extra.data_nf IS NOT NULL THEN
					 TO_CHAR(tbl_hd_chamado_extra.data_nf, 'DD/MM/YYYY')
				 END AS hd_data_nota_fiscal, ";
		$condExtra  = "LEFT JOIN tbl_hd_chamado_extra ON tbl_hd_chamado_extra.hd_chamado = tbl_hd_chamado.hd_chamado AND tbl_hd_chamado_extra.os = {$os}
				LEFT JOIN tbl_hd_chamado_item ON tbl_hd_chamado_item.hd_chamado = tbl_hd_chamado.hd_chamado AND (tbl_hd_chamado_item.os = {$os} or tbl_hd_chamado_item.os isnull and tbl_hd_chamado_item.produto notnull)";
	}else if ($login_fabrica == 178){
		$campoExtra = "tbl_hd_chamado_extra.nota_fiscal AS hd_nota_fiscal,
						TO_CHAR(tbl_hd_chamado_extra.data_nf, 'DD/MM/YYYY') AS hd_data_nota_fiscal,
						tbl_os_campo_extra.campos_adicionais,";
		$condExtra  = "INNER JOIN tbl_os_campo_extra ON tbl_os_campo_extra.os = tbl_os.os AND tbl_os_campo_extra.fabrica = $login_fabrica
						LEFT JOIN tbl_hd_chamado_extra ON tbl_hd_chamado_extra.hd_chamado = tbl_hd_chamado.hd_chamado";
	} else {
		$campoExtra = "tbl_hd_chamado_extra.nota_fiscal AS hd_nota_fiscal,
						TO_CHAR(tbl_hd_chamado_extra.data_nf, 'DD/MM/YYYY') AS hd_data_nota_fiscal,";
		$condExtra  = "LEFT JOIN tbl_hd_chamado_extra ON tbl_hd_chamado_extra.hd_chamado = tbl_hd_chamado.hd_chamado";
	}

	$sql_produto = "
		SELECT
			tbl_os_produto.os_produto,
			(tbl_produto.referencia || ' - ' || tbl_produto.descricao) AS produto,
			tbl_os.nota_fiscal,
			{$campoExtra}
			TO_CHAR(tbl_os.data_nf, 'DD/MM/YYYY') AS data_nota_fiscal
		FROM tbl_os_produto
		INNER JOIN tbl_produto ON tbl_produto.produto = tbl_os_produto.produto
		INNER JOIN tbl_os ON tbl_os.os = tbl_os_produto.os AND tbl_os.fabrica = {$login_fabrica}
		LEFT JOIN tbl_hd_chamado ON tbl_hd_chamado.hd_chamado = tbl_os.hd_chamado AND tbl_hd_chamado.fabrica = {$login_fabrica}
		{$condExtra}
		WHERE tbl_os_produto.os = {$os}
		ORDER BY tbl_os_produto.os_produto, tbl_produto.produto ASC";
		
	$res_produto = pg_query($con, $sql_produto);
	
	if (pg_num_rows($res_produto) > 0) {
		$total_trocados   = 0;
		$nota_fiscal      = pg_fetch_result($res_produto, 0, "nota_fiscal");
		$data_nota_fiscal = pg_fetch_result($res_produto, 0, "data_nota_fiscal");

		if (empty($nota_fiscal)) {
			$nota_fiscal      = pg_fetch_result($res_produto, 0, "hd_nota_fiscal");
		}

		if(in_array($login_fabrica, [178,183])){
			$campos_adicionais = pg_fetch_result($res_produto, 0, "campos_adicionais");

            if (!empty($campos_adicionais)){
                $campos_adicionais = json_decode($campos_adicionais, true);
                $produto_troca_posto = $campos_adicionais["produto_troca_posto"];
            }
		}

		if(empty($data_nota_fiscal)) {
            $data_nota_fiscal = pg_fetch_result($res_produto, 0, "hd_data_nota_fiscal");
		}

        $xproduto = "";//hd_chamado=3032797
        while ($produto = pg_fetch_object($res_produto)) {
            if ($produto->produto <> $xproduto) { //hd_chamado=3032797
                $xproduto = $produto->produto;
            } else {
                continue;
            } 
            if ($login_fabrica == 195 && isset($_REQUEST["troca_antecipada"]) && $_REQUEST["troca_antecipada"] == 'true') {
				$sqlCausa = "SELECT causa_troca FROM tbl_causa_troca WHERE fabrica = {$login_fabrica} AND ativo IS TRUE AND descricao='Troca Antecipada'";
				$resCausa = pg_query($con, $sqlCausa);

				if (pg_num_rows($resCausa) > 0) {
					$idcausa = pg_fetch_result($resCausa, 0, 'causa_troca');
				} else {
					$idcausa = "";
				}

				$_RESULT["acao"][$produto->os_produto] = "trocar";
				$_RESULT["causa_troca"][$produto->os_produto] = $idcausa;
				$_RESULT["setor_responsavel"] = "sac";
				$_RESULT["situacao_atendimento"] = "0";
			}

            ?>
			<div class="informacoes_produto">
				<input type="hidden" name="os_produto[]" value="<?=$produto->os_produto?>" />
				<div class="subtitulo_tabela"><?=$produto->produto?></div>
				<?php
				$sql_troca_efetuada = "
					SELECT
						tbl_os_troca.os_troca,
						tbl_os_troca.ressarcimento
					FROM tbl_os_troca
					JOIN tbl_os_produto ON tbl_os_produto.os = tbl_os_troca.os AND tbl_os_troca.produto = tbl_os_produto.produto AND tbl_os_produto.os_produto = {$produto->os_produto}
					JOIN tbl_os_item ON tbl_os_item.os_produto = tbl_os_produto.os_produto
					JOIN tbl_servico_realizado ON tbl_os_item.servico_realizado = tbl_servico_realizado.servico_realizado AND tbl_servico_realizado.troca_produto IS TRUE AND tbl_os_item.pedido IS NOT NULL
					WHERE tbl_os_troca.fabric = {$login_fabrica}
					AND tbl_os_troca.os = {$os}
					ORDER BY tbl_os_troca.os_troca DESC
					LIMIT 1
				";
				$res_troca_efetuada = pg_query($con, $sql_troca_efetuada);

				$troca_ressarcimento_efetuado = false;

				if (pg_num_rows($res_troca_efetuada) > 0 || (pg_num_rows($res_troca_efetuada) > 0 && pg_fetch_result($res_troca_efetuada, 0, "ressarcimento") != "t")) {
					$troca_ressarcimento_efetuado = true;
				}else{
					$sql_troca_efetuada = "SELECT ressarcimento From tbl_os_troca WHERE os = $os and ressarcimento "; 
					$res_troca_efetuada = pg_query($con, $sql_troca_efetuada); 
					if(pg_num_rows($res_troca_efetuada) > 0) {
						$troca_ressarcimento_efetuado = true;
					}
				}

				if (in_array($login_fabrica, array(169,170)) && pg_fetch_result($res_troca_efetuada, 0, 'ressarcimento') == "t") {
					$sql_troca_efetuada = "
						SELECT cancelado, aprovado FROM tbl_ressarcimento WHERE fabrica = {$login_fabrica} AND os = {$os} ORDER BY ressarcimento DESC LIMIT 1
					";
					$res_troca_efetuada = pg_query($con, $sql_troca_efetuada);

					$cancelado = pg_fetch_result($res_troca_efetuada, 0, 'cancelado');
					$aprovado = pg_fetch_result($res_troca_efetuada, 0, 'aprovado');

					if (!empty($cancelado)) {
						$troca_ressarcimento_efetuado = false;
					} else if (empty($aprovado)) {
						$troca_ressarcimento_efetuado = true;
					}
				} else if (in_array($login_fabrica, array(169,170))) {
					$troca_ressarcimento_efetuado = false;
				}

				$trocaCancelada = false;
				if (($telecontrol_distrib || $replica_einhell) && $novaTelaOs && $troca_ressarcimento_efetuado) {
					$sqlCancelouTroca =  "	SELECT
												tbl_os_troca.os_troca
											FROM tbl_os_troca
											JOIN tbl_os_produto ON tbl_os_produto.os = tbl_os_troca.os AND tbl_os_troca.produto = tbl_os_produto.produto AND tbl_os_produto.os_produto = {$produto->os_produto}
				 							JOIN tbl_os_item ON tbl_os_item.os_produto = tbl_os_produto.os_produto
				 							JOIN tbl_servico_realizado ON tbl_os_item.servico_realizado = tbl_servico_realizado.servico_realizado AND tbl_servico_realizado.troca_produto IS TRUE AND tbl_os_item.pedido IS NOT NULL
				 							JOIN tbl_pedido_item ON tbl_os_item.pedido_item = tbl_pedido_item.pedido_item 
				 							WHERE tbl_os_troca.fabric = {$login_fabrica}
				 							AND tbl_os_troca.os = {$os}
				 							AND tbl_os_item.qtde = tbl_pedido_item.qtde_cancelada
				 							ORDER BY tbl_os_troca.os_troca DESC
				 							LIMIT 1";
				 	$resCancelouTroca = pg_query($con, $sqlCancelouTroca);
				 	if (pg_num_rows($resCancelouTroca) > 0) {
				 		$troca_ressarcimento_efetuado = false;
				 		$trocaCancelada = true;
				 	}
				}

				if ($troca_ressarcimento_efetuado === false) { ?>
					<div class="row-fluid">
						<div class="span1"></div>
						<div class="span10">
							<div class='control-group <?=(in_array("$produto->os_produto|acao", $msg_erro["campos"])) ? "error" : ""?>' >
								<div class="controls controls-row">
									<div class="span12" style="text-align: center !important;">
										<br />
										<? if (!(pg_num_rows($res_troca_efetuada) > 0 && pg_fetch_result($res_troca_efetuada, 0, "ressarcimento") != "t") || in_array($login_fabrica, array(169,170)) || $trocaCancelada) {
                                            if($login_fabrica != 153) { ?>
	                                            <strong class="asteristico" style="float: none;">*</strong> &nbsp;
												<label class="radio" >
													<input type="radio" name="acao[<?=$produto->os_produto?>]" value="trocar" <? if (getValue("acao[{$produto->os_produto}]") == "trocar") echo "checked"; ?> />Troca
												</label>
											 	&nbsp;
											<? }
										} else { ?>
											<div class="alert alert-danger" >
												<strong>Esse produto já teve uma troca efetuada pelo(s) seguinte(s) produto(s):</strong>
												<br />
												<? $sqlHistoricoTroca = "
													SELECT
														tbl_peca.referencia || ' - ' || tbl_peca.descricao AS produto
													FROM tbl_os_troca
													JOIN tbl_peca ON tbl_peca.peca = tbl_os_troca.peca AND tbl_peca.fabrica = {$login_fabrica}
													JOIN tbl_os ON tbl_os.os = tbl_os_troca.os AND tbl_os.fabrica = {$login_fabrica}
													JOIN tbl_os_produto ON tbl_os_produto.os = tbl_os.os AND tbl_os_produto.produto = tbl_os_troca.produto
													WHERE tbl_os_troca.os = {$os}
													AND tbl_os_produto.os_produto = {$produto->os_produto};
												";

												$resHistoricoTroca = pg_query($con, $sqlHistoricoTroca);

												echo "<ul>";
													while ($historico_troca = pg_fetch_object($resHistoricoTroca)) {
														echo "<li>{$historico_troca->produto}</li>";
													}
												echo "</ul>"; ?>
											</div>
										<? }
										if ($login_fabrica == 153) { ?>
											<label class="radio" >
												<input type="radio" name="acao[<?=$produto->os_produto?>]" value="trocar" <? if (getValue("acao[{$produto->os_produto}]") == "trocar") echo "checked"; ?> />Troca
											</label>
											&nbsp;
										<? } ?>
										<label class="radio" >
											<input type="radio" name="acao[<?=$produto->os_produto?>]" value="ressarcimento" <? if (getValue("acao[{$produto->os_produto}]") == "ressarcimento") echo "checked"; ?> />Ressarcimento
										</label>
										 &nbsp;
										<? if ($login_fabrica == 138) { ?>
											<label class="radio" >
												<input type="radio" name="acao[<?=$produto->os_produto?>]" value="consertar" <? if (getValue("acao[{$produto->os_produto}]") == "consertar") echo "checked"; ?> />Consertar
											</label>
										<? }
                                        if ($login_fabrica == 165) { ?>
											<label class="radio" >
												<input type="radio" name="acao[<?=$produto->os_produto?>]" value="base_troca" <? if (getValue("acao[{$produto->os_produto}]") == "base_troca") echo "checked"; ?> />Base Troca
											</label>
										<? } ?>
									</div>
								</div>
							</div>
						</div>
					</div>
					<?
                    $produto_indice = $produto->os_produto;
                    $tipo_troca     = getValue("acao[{$produto_indice}]");

                    $display_troca               = (in_array($tipo_troca, array("trocar","base_troca")))        ? "style='display: block;'" : "";
                    $display_base_troca          = ($tipo_troca == "base_troca")                                ? "style='display: block;'" : "";
                    $display_ressarcimento       = ($tipo_troca == "ressarcimento")                             ? "style='display: block;'" : "";
                    $display_troca_ressarcimento = (in_array($tipo_troca, array("trocar", "ressarcimento")))    ? "style='display: block;'" : "";

		    if(in_array($login_fabrica, [178,183])){
				$marcas = ($troca_garantia == "t") ? $marcaOS : $marcas;

				if($troca_garantia == "t"){
            ?>
					<div class="row-fluid troca" <?=$display_troca?> >
						<div class="span1"></div>
						 <div class='span10'>
							<div class="span12 alert alert-warning">
								Troca de produto solicitada pelo Posto Autorizado
							</div>
						</div>
						<div class="span1"></div>
					</div>
		    <?php
				}

				if (!empty($troca_enviar_para)){ 
					switch ($troca_enviar_para) {
						case 'C':
							$text_alert = "O CONSUMIDOR";
							break;
						case 'P':
							$text_alert = "O POSTO AUTORIZADO";
							break;
						case 'R':
							$text_alert = "A REVENDA";
							break;
					}
			?>
					<div class="row-fluid troca" <?=$display_troca?> >
						<div class="span1"></div>
						 <div class='span10'>
							<div class="span12 alert alert-warning">
								Já exite um troca para esse lote que foi enviado para <?=$text_alert?>, portanto não é possível alterar o campo Enviar para
							</div>
						</div>
						<div class="span1"></div>
					</div>
			<?php
				} 
								
				if ($os_consumidor_revenda == "R"){
			?>
				<input type="hidden" name="os_consumidor_revenda[<?=$produto_indice?>]" value="<?=$os_consumidor_revenda?>">
				<div class="row-fluid troca" <?=$display_troca?>>
					<div class="span1"></div>
					 <div class='span10'>
						<div class="span12 alert alert-info">
							Informaçõe da Revenda para Faturamento <br/>
							Caso queira mudar a Revenda pesquise uma nova ou digite um CNPJ válido.
						</div>
					</div>
					<div class="span1"></div>
				</div>
				<div class="row-fluid troca" <?=$display_troca?>>
		            <div class="span1"></div>
		            <div class="span3">
		                <div class='control-group <?=(in_array("$produto_indice|revenda_cnpj", $msg_erro["campos"])) ? "error" : ""?>' >
		                    <label class="control-label" for="revenda_cnpj">CNPJ</label>
		                    <div class="controls controls-row">
		                        <div class="span10 input-append">
		                            <input id="revenda_cnpj" name="revenda_cnpj[<?=$produto_indice?>]" class="span12" type="text" <?=$readOnlyRevenda?> value="<?=$consumidor_cpf?>" />
		                            <?php if (empty($revenda_cnpjx)){ ?>
		                        		<span class="add-on" rel="lupa" style='cursor: pointer;'>
		                                	<i class="icon-search"></i>
		                            	</span>
		                                <input type="hidden" name="lupa_config" tipo="revenda" parametro="cnpj" />
		                        	<?php } ?>
		                        </div>
		                    </div>
		                </div>
		            </div>
		            <div class="span5">
		                <div class='control-group'>
		                    <label class="control-label" for="revenda_nome">Nome <b>(Revenda)</b></label>
		                    <div class="controls controls-row">
		                        <div class="span11 input-append">
		                            <input id="revenda_nome" name="revenda_nome[<?=$produto_indice?>]" class="span12" type="text" <?=$readOnlyRevenda?> maxlength="50" value="<?=$consumidor_nome?>" />
		                            <?php if (empty($revenda_cnpjx)){ ?>
			                            <span class="add-on" rel="lupa" style='cursor: pointer;'>
			                                <i class="icon-search"></i>
			                            </span>
			                            <input type="hidden" name="lupa_config" tipo="revenda" parametro="razao_social" />
			                        <?php } ?>
		                        </div>
		                    </div>
		                </div>
		            </div>
		            <div class="span1"></div>
		        </div>
			<?php	
				}
		    ?>
			<div class="row-fluid troca" <?=$display_troca?> >
			     <div class="span1"></div>
				<?php if ($login_fabrica == 178){?>
			    <div class='span4'>
					<div class='control-group <?=(in_array("$produto_indice|marca_troca", $msg_erro["campos"])) ? "error" : ""?>' >
						<label class="control-label" for="marca_troca">Marca do produto</label>
						<div class="controls controls-row">
						    <div class="span12">
							<h5 class="asteristico">*</h5>
							<select class='span12' id="marca_troca" name="marca_troca[<?=$produto_indice?>]" >
								<option value="">Selecione</option>
							    <?php

								$sqlMarcas = "SELECT marca,nome
										FROM tbl_marca
										WHERE fabrica = {$login_fabrica}
										AND marca IN(".$marcas.");";
								$resMarcas = pg_query($con,$sqlMarcas);
								$marcas = pg_fetch_all($resMarcas);
			
								$marca_produto_post = getValue("marca_troca[{$produto_indice}]");

								$marca_produto_post = (strlen($marca_produto_post) == 0) ? $marcaOS : $marca_produto_post;

								while ($marcas = pg_fetch_object($resMarcas)) {
									$selected = ($marcas->marca == $marca_produto_post) ? "selected" : "";

									echo "<option value='{$marcas->marca}' {$selected} >{$marcas->nome}</option>";
								}
							   ?>
							</select>
						    </div>
						</div>
					</div>
			    </div>
				<?php }?>

			    <div class='span4'>
					<div class='control-group <?=(in_array("$produto_indice|enviar_para", $msg_erro["campos"])) ? "error" : ""?>' >
						<label class="control-label" for="enviar_para">Enviar para</label>
						<div class="controls controls-row">
						    <div class="span12">
							<h5 class="asteristico">*</h5>
							<select id="enviar_para" class='span12' name="enviar_para[<?=$produto_indice?>]" >
								<option value="">Selecione</option>
								<?php
									$enviar_para_post = getValue("enviar_para[{$produto_indice}]");
									if (!empty($troca_enviar_para)){
										$enviar_para = $troca_enviar_para;
									}
									$enviar_para_post = (strlen($enviar_para) > 0) ? $enviar_para : $enviar_para_post;

									$array_enviar_para = array("C"=>"Cliente","P"=>"Posto Autorizado","R"=>"Revenda");

									if(in_array($login_fabrica, [183])){
										if ($os_consumidor_revenda == "R"){
											unset($array_enviar_para["C"]);
										}else{
											unset($array_enviar_para["R"]);
										}
									}

									if ($login_fabrica == 178){
										if ($os_consumidor_revenda == "R"){
											unset($array_enviar_para["C"]);
											if ($troca_enviar_para == "P"){
												unset($array_enviar_para["R"]);
											} else if ($troca_enviar_para == "R"){
												unset($array_enviar_para["P"]);
											}
										}else{
											unset($array_enviar_para["R"]);
											if ($troca_enviar_para == "P"){
												unset($array_enviar_para["C"]);
											} else if ($troca_enviar_para == "C"){
												unset($array_enviar_para["P"]);
											}
										}
									}

									foreach($array_enviar_para AS $key => $value){
										$selected = ($key == $enviar_para_post) ? "selected" : "";
										echo "<option value='{$key}' {$selected}>{$value}</option>";
									}
								?>
							</select>
						    </div>
						</div>
					</div>
			    </div>

			    <?php if ($login_fabrica == 183){ ?>
					<div class='span3' id="div_btn_dados" style="display: none;" >
						<div class='control-group'>
							<label></label>
							<div class="controls controls-row">
								<button type="button" id="btn_endereco" class="btn btn-primary">Confirmar endereço cliente</button>
							</div>
						</div>
				    </div>
			    <?php } ?>
			</div>
		    <?php
		    }
		    ?>
					<div class="row-fluid troca" <?=$display_troca?> >
						<div class="span1"></div>

						<div class='span4'>
							<div class='control-group <?=(in_array("$produto_indice|produto_troca", $msg_erro["campos"])) ? "error" : ""?>'>
								<label class='control-label' for='produto_referencia[<?=$produto_indice?>]'>Trocar pelo produto:</label>
								<div class='controls controls-row'>
									<div class='span10  input-append'>
										<input type='hidden' data-product-index="<?=$produto_indice?>" name='produto_troca[<?=$produto_indice?>]' value='<?$_POST["produto_troca[$produto_indice"]?>' />
										<input type='hidden' data-product-index="<?=$produto_indice?>" name="familia[<?=$produto_indice?>]" value='<?$_POST["familia[$produto_indice"]?>' />
										<input type='text'   data-product-index="<?=$produto_indice?>" name="referencia[<?=$produto_indice?>]" class='span8 ' value=""
											placeholder="Referência" />
										<span class='add-on' rel='lupa'><i class='icon-search'></i></span>
										<input type='hidden' name='lupa_config' tipo='produto' retornaIndice="<?=$produto_indice?>"
										 listaTroca="true" parametro='referencia'  />
									</div>
								</div>
							</div>
						</div>
						<div class='span4'>
							<div class='control-group '>
								<label class='control-label' for='produto_descricao'></label>
								<div class='controls controls-row'>
									<div class='span12 input-append'>
									<input type='text' class='span10' data-product-index="<?=$produto_indice?>"
										   name='produto_descricao[<?=$produto_indice?>]'
									placeholder="Descrição" value="" />
										<span class='add-on' rel='lupa'><i class='icon-search'></i></span>
										<input type='hidden' name='lupa_config' tipo='produto' retornaIndice="<?=$produto_indice?>"
										 listaTroca="true" parametro='descricao' />
									</div>
								</div>
							</div>
						</div>
					</div>
					<? if ($login_fabrica == 165 && $tipo_troca == "base_troca") {
                        $display_troca = "";
                        $display_base_troca = "style='display:block;'";
                    } ?>
                    <div class="row-fluid base_troca" <?=$display_base_troca?>>
                        <div class='control-group '>
                            <div class="span1" ></div>
                            <div class="span10">
                                <label class='control-label' for='valor_base_troca'>Valor</label>
                                <div class="controls controls-row">
                                    <div class='span12 input-append'>
                                        <input type="text" readOnly class="tar" name="valor_base_troca[<?=$produto_indice?>]" value="" />
                                    </div>
                                </div>
                            </div>
                            <div class="span1" ></div>
                        </div>
                    </div>

					<div class="row-fluid produto-selecionado-<?=$produto_indice?> troca" <?=$display_troca?> >
						<div class="span1" ></div>
						<div class="span8" >
							<?php
							$produto_troca = array();

							foreach($_POST["produto_troca"][$produto_indice] as $key => $produto) {
	                        	$sql = "SELECT produto, referencia, descricao FROM tbl_produto WHERE fabrica_i = {$login_fabrica} AND produto = {$produto}";
								$res = pg_query($con, $sql);

								$produto_troca[] = array(
									"produto" => pg_fetch_result($res, 0, "produto"),
									"referencia" => pg_fetch_result($res, 0, "referencia"),
									"descricao" => pg_fetch_result($res, 0, "descricao")
								);
							}

							if (!count($msg_erro["msg"])) {
								if ($usaProdutoGenerico) {
									$whereProdutoGerencico = "AND tbl_produto.produto_principal IS TRUE";
								}

								if ($login_fabrica == 178 AND !empty($produto_troca_posto)){
									$sql = "SELECT tbl_produto.produto, tbl_produto.referencia, tbl_produto.descricao
											FROM tbl_produto
											WHERE fabrica_i = $login_fabrica
											AND produto = $produto_troca_posto";
									$res = pg_query($con, $sql);
									if (pg_num_rows($res) > 0) {
										$produto_troca[] = array(
											"produto" => pg_fetch_result($res, 0, "produto"),
											"referencia" => pg_fetch_result($res, 0, "referencia"),
											"descricao" => pg_fetch_result($res, 0, "descricao")
										);
									}
								}else{
									$sql = "SELECT tbl_produto.produto, tbl_produto.referencia, tbl_produto.descricao
											FROM tbl_os
											INNER JOIN tbl_os_produto ON tbl_os_produto.os = tbl_os.os
											INNER JOIN tbl_produto ON tbl_produto.produto = tbl_os_produto.produto AND tbl_produto.fabrica_i = {$login_fabrica}
											WHERE tbl_os.fabrica = {$login_fabrica}
											AND tbl_os_produto.os_produto = {$produto_indice}
											{$whereProdutoGerencico}";
									$res = pg_query($con, $sql);
									if (pg_num_rows($res) > 0) {
										$produto_troca[] = array(
											"produto" => pg_fetch_result($res, 0, "produto"),
											"referencia" => pg_fetch_result($res, 0, "referencia"),
											"descricao" => pg_fetch_result($res, 0, "descricao")
										);
									}
								}
							}

							if(count($produto_troca) == 0) {
							?>
							<span class="label label-important" >É necessário informar pelo menos um produto</span>
							<br />
							<? } ?>
							<select id="produto-troca-<?=$produto_indice?>" name="produto_troca[<?=$produto_indice?>][]" style="display: none;" multiple="multiple" >
								<?php
								foreach($produto_troca as $key => $produto) {
									echo "<option value='{$produto['produto']}' selected >{$produto['referencia']}</option>";
								}
								?>
        	                </select>
                            <label class="control-label" for="produto_troca_qtde">Lista de Produtos</label>
							<ul class="nav nav-tabs produtos_troca">
								<?php
								foreach($produto_troca as $key => $produto) {
									echo "
										<li class='active' style='float: none;' data-produto-id='{$produto['produto']}' data-produto-referencia='{$produto['referencia']}' data-os-produto='{$produto_indice}'  >
									        <a href='#' class='remover-produto' ><button type='button' class='btn btn-danger btn-mini' ><i class='icon-remove icon-white'></i></button> {$produto['referencia']} - {$produto['descricao']}</a>
								                </li>

									";
								}
								?>
							</ul>
						</div>
						<? if ($login_fabrica == 145) { ?>
                        <div class="span2" >
                            <div class='control-group <?=(in_array("$produto_indice|produto_troca_qtde", $msg_erro["campos"])) ? "error" : ""?>' >
                                <strong class="asteristico" style="float: none;">*</strong>
                                <label class="control-label" for="produto_troca_qtde">Qtde</label>
                                <div class="controls controls-row">
                                    <div class="span12 troca_qtde">
<?php
                                    foreach($produto_troca as $key => $produto) {
?>
                                        <input type="text" style="height:40px;" class='span4 numeric' maxlength="3" name="produto_troca_qtde[<?=$produto_indice?>][]" id="produto_troca_qtde_<?=$produto['produto']?>" value="<?=getValue("produto_troca_qtde[$produto_indice][$key]")?>" /><br id="pular_<?=$produto['produto']?>" />
<?php
                                    }
?>
                                    </div>
                                </div>
                            </div>
                        </div>
<?php
                        }
?>
					</div>

<?php
					$nome_cliente = getValue("nome_cliente[$produto_indice]");
					$cpf_cliente = getValue("cpf_cliente[$produto_indice]");

					if ($login_fabrica == 151 && empty($msg_erro["msg"])) {
						$nome_cliente = $hd_chamado_nome;
						$cpf_cliente  = $hd_chamado_cpf;
					}
					?>

					<div class="ressarcimento row-fluid" <?=$display_ressarcimento?> >
						<div class="span1" ></div>

						<div class="span4">
							<div class='control-group <?=(in_array("$produto_indice|nome_cliente", $msg_erro["campos"])) ? "error" : ""?>' >
								<label class="control-label" for="cpf_cliente">Nome do Cliente</label>
								<div class="controls controls-row">
									<div class="span12">
										<h5 class="asteristico">*</h5>
										<input type="text" class='span12' name="nome_cliente[<?=$produto_indice?>]" value="<?=$nome_cliente?>" />
									</div>
								</div>
							</div>
						</div>

						<div class="span3">
							<div class='control-group <?=(in_array("$produto_indice|cpf_cliente", $msg_erro["campos"])) ? "error" : ""?>' >
								<label class="control-label" for="cpf_cliente">
									CPF <input type="radio" id="cpf_radio" checked name="consumidor[cnpjCpf]" <?= (getValue('consumidor[cnpjCpf]') == "cpf") ? 'checked="checked"': ''; ?> value="cpf" />
									CNPJ <input type="radio" id="cnpj_radio" name="consumidor[cnpjCpf]" <?= (getValue('consumidor[cnpjCpf]') == "cnpj") ? 'checked="checked"': ''; ?> value="cnpj" />
								</label>
								

								<div class="controls controls-row">
									<div class="span12">
										<h5 class="asteristico">*</h5>
										<input id="cpf_cnpj" type="text" class='span12 <?= $class_cpf_cnpj ?>' name="cpf_cliente[<?=$produto_indice?>]" value="<?=$cpf_cliente?>" />
									</div>
								</div>
							</div>
						</div>

						<?php
						if ($login_fabrica == 151) {
						?>
                        <div class="span2" >
                            <div class='control-group' >
                                <label class="control-label" >&nbsp;</label>
                                <div class="controls controls-row">
                                    <div class="span12">
                                        <button type="button" class="btn btn-link btn-small" onclick="window.open('cadastro_cliente.php');" >Cadastrar Cliente</button>
                                    </div>
                                </div>
                            </div>
                        </div>
						<?php
						}
						?>
					</div>

					<div class="ressarcimento row-fluid" <?=$display_ressarcimento?> >
						<div class="span1" ></div>

						<div class="span2">
							<div class='control-group' >
								<label class="control-label" >Nota Fiscal</label>
								<div class="controls controls-row">
									<div class="span12">
										<input type="text" class='span12' value="<?=$nota_fiscal?>" disabled />
										<input type="hidden" name='nota_fiscal' value="<?=$nota_fiscal?>"  />
									</div>
								</div>
							</div>
						</div>

						<div class="span2">
							<div class='control-group' >
								<label class="control-label" >Data da Nota Fiscal</label>
								<div class="controls controls-row">
									<div class="span12">
										<input type="text" class='span12' value="<?=$data_nota_fiscal?>" disabled />
										<input type="hidden" name='data_nota_fiscal' value="<?=$data_nota_fiscal?>"  />
									</div>
								</div>
							</div>
						</div>

						<div class="span3">
							<div class='control-group <?=(in_array("$produto_indice|valor_ressarcimento", $msg_erro["campos"])) ? "error" : ""?>' >
								<label class="control-label" for="valor_ressarcimento">Valor do Ressarcimento</label>
								<div class="controls controls-row">
									<div class="span12">
										<h5 class="asteristico">*</h5>
										<input type="text" class='span12' price="true" name="valor_ressarcimento[<?=$produto_indice?>]" value="<?=getValue("valor_ressarcimento[$produto_indice]")?>" />
									</div>
								</div>
							</div>
						</div>

						<div class="span3">
							<div class='control-group <?=(in_array("$produto_indice|previsao_pagamento", $msg_erro["campos"])) ? "error" : ""?>' >
								<label class="control-label" for="previsao_pagamento">Previsão de Pagamento</label>
								<div class="controls controls-row">
									<div class="span12">
									<?php if (!in_array($login_fabrica, [169, 170])) { ?>
										<h5 class="asteristico">*</h5>
									<?php } ?>
										<input type="text" class='span12 data' id="previsao_pagamento" name="previsao_pagamento[<?=$produto_indice?>]" value="<?=getValue("previsao_pagamento[$produto_indice]")?>" />
									</div>
								</div>
							</div>
						</div>
					</div>

					<div class="ressarcimento row-fluid" <?=$display_ressarcimento?> >
						<div class="span1" ></div>

						<div class="span2">
							<div class='control-group <?=(in_array("$produto_indice|banco", $msg_erro["campos"])) ? "error" : ""?>' >
								<label class="control-label" for="banco">Banco</label>
								<div class="controls controls-row">
									<div class="span12">
									<?php if (!in_array($login_fabrica, [169, 170])) { ?>
										<h5 class="asteristico">*</h5>
									<?php } ?>
										<select class="span12 banco" name="banco[<?=$produto_indice?>]" >
											<option value="" >Selecione</option>
											<?php
											$sqlBanco = "SELECT banco, codigo, nome FROM tbl_banco ORDER BY codigo ASC";
											$resBanco = pg_query($con, $sqlBanco);

											while ($banco = pg_fetch_object($resBanco)) {
												$selected = ($banco->banco == getValue("banco[{$produto_indice}]")) ? "selected" : "";

												echo "<option value='{$banco->banco}' {$selected} >{$banco->codigo} - {$banco->nome}</option>";
											}
											?>
										</select>
									</div>
								</div>
							</div>
						</div>

						<div class="span3">
							<div class='control-group <?=(in_array("$produto_indice|agencia", $msg_erro["campos"])) ? "error" : ""?>' >
								<label class="control-label" for="agencia">Agência</label>
								<div class="controls controls-row">
									<div class="span12">
									<?php if (!in_array($login_fabrica, [169, 170])) { ?>
										<h5 class="asteristico">*</h5>
									<?php } ?>
										<input type="text" class='span9 numeric' name="agencia[<?=$produto_indice?>]" value="<?=getValue("agencia[$produto_indice]")?>" maxlength="8" />
										<input type="text" class='span2 numeric' name="agencia_digito[<?=$produto_indice?>]" value="<?=getValue("agencia_digito[$produto_indice]")?>" maxlength="1" />
									</div>
								</div>
							</div>
						</div>

						<div class="span3">
							<div class='control-group <?=(in_array("$produto_indice|conta", $msg_erro["campos"])) ? "error" : ""?>' >
								<label class="control-label" for="conta">Conta</label>
								<div class="controls controls-row">
									<div class="span12">
									<?php if (!in_array($login_fabrica, [169, 170])) { ?>
										<h5 class="asteristico">*</h5>
									<?php } ?>
										<input type="text" class='span9 numeric' name="conta[<?=$produto_indice?>]" value="<?=getValue("conta[$produto_indice]")?>" maxlength="15" />
										<input type="text" class='span2 numeric' name="conta_digito[<?=$produto_indice?>]" value="<?=getValue("conta_digito[$produto_indice]")?>" maxlength="2" />
									</div>
								</div>
							</div>
						</div>

						<div class="span2">
							<div class='control-group' >
								<label class="control-label" for="tipo_conta">Tipo de Conta</label>
								<div class="controls controls-row">
									<div class="span12">
										<select class="span12" name="tipo_conta[<?=$produto_indice?>]" >
											<option value="C" <?=(getValue("tipo_conta[$produto_indice]") == 'C') ? 'checked' : ''?> >Conta Corrente</option>
											<option value="P" <?=(getValue("tipo_conta[$produto_indice]") == 'P') ? 'checked' : ''?> >Conta Poupança</option>
										</select>
									</div>
								</div>
							</div>
						</div>
					</div>

					<div class="row-fluid troca_ressarcimento" <?=$display_troca_ressarcimento?> >
						<div class="span1"></div>

						<div class="span2">
							<div class='control-group' >
								<label class="control-label" for="posto_nome">Número de Registro</label>
								<div class="controls controls-row">
									<div class="span12">
										<input type="text" class='span12' name="numero_registro[<?=$produto_indice?>]" value="<?=getValue("numero_registro[$produto_indice]")?>" maxlength="10" />
									</div>
								</div>
							</div>
						</div>

						<? if($login_fabrica == 151) { /*HD - 3495172*/ ?>
						<div class="span4">
							<div class='control-group <?=(in_array("$produto_indice|causa_troca", $msg_erro["campos"])) ? "error" : ""?>' >
								<label class="control-label" for="posto_nome">Causa da Troca/Ressarcimento</label>
								<div class="controls controls-row">
									<div class="span12">
										<h5 class="asteristico">*</h5>
										<select class='span12' name="causa_troca[<?=$produto_indice?>]" >
											<option value="" >Selecione</option>
											<?php

											$sql_causa_troca = "SELECT causa_troca, descricao, codigo FROM tbl_causa_troca WHERE fabrica = {$login_fabrica} AND ativo IS TRUE ORDER BY codigo ASC";
											$res_causa_troca = pg_query($con, $sql_causa_troca);

											if (pg_num_rows($res_causa_troca)) {
												$causa_troca_post = getValue("causa_troca[{$produto_indice}]");

												while ($causa_troca = pg_fetch_object($res_causa_troca)) {
													$selected = ($causa_troca->causa_troca == $causa_troca_post) ? "selected" : "";

													echo "<option value='{$causa_troca->causa_troca}' {$selected} >{$causa_troca->codigo} - {$causa_troca->descricao}</option>";
												}
											}

											?>
										</select>
									</div>
								</div>
							</div>
						</div>

						<? }else{ ?>
						<div class="span4">
							<div class='control-group <?=(in_array("$produto_indice|causa_troca", $msg_erro["campos"])) ? "error" : ""?>' >
								<label class="control-label" for="posto_nome">Causa da Troca/Ressarcimento</label>
								<div class="controls controls-row">
									<div class="span12">
										<h5 class="asteristico">*</h5>
										<select class='span12' name="causa_troca[<?=$produto_indice?>]" >
											<option value="" >Selecione</option>
											<?php

											$sql_causa_troca = "SELECT causa_troca, descricao FROM tbl_causa_troca WHERE fabrica = {$login_fabrica} AND ativo IS TRUE ORDER BY descricao ASC";
											$res_causa_troca = pg_query($con, $sql_causa_troca);

											if (pg_num_rows($res_causa_troca)) {
												$causa_troca_post = getValue("causa_troca[{$produto_indice}]");

												while ($causa_troca = pg_fetch_object($res_causa_troca)) {
													$selected = ($causa_troca->causa_troca == $causa_troca_post) ? "selected" : "";

													echo "<option value='{$causa_troca->causa_troca}' {$selected} >{$causa_troca->descricao}</option>";
												}
											}

											?>
										</select>
									</div>
								</div>
							</div>
						</div>
						<? } 
						if (in_array($login_fabrica, [173])) { ?>
						<div class="span2">
							<div class='control-group' >
								<label class="control-label" for="posto_nome">Novo Nº Serie</label>
								<div class="controls controls-row">
									<div class="span12">
										<input type="text" class='span12' name="novo_n_serie" value="<?=getValue("novo_n_serie")?>"  />
									</div>
								</div>
							</div>
						</div>
						<?php } ?>
					</div>

					<? if(in_array($login_fabrica, array(169,170))) {?>
					<div class="row-fluid troca_ressarcimento" <?=$display_troca_ressarcimento?> >
						<div class="span1"></div>

						<div class="span3" >
		                    <div class="control-group <?=(in_array('defeito_constatado', $msg_erro['campos'])) ? "error" : "" ?>">
								<label class="control-label" for="produto_defeito_constatado">Defeito Constatado</label>
								<div class="controls controls-row">
									<div class="span12">
									<h5 class='asteristico'>*</h5>
								<?
									$span = "span9";
									?>
									<select id="produto_defeito_constatado" name="defeito_constatado" onblur='defeitoPeca()' class="<?php echo $span; ?>" >
										<option value="">Selecione</option>
										<?
										$whereTipoAtendimento = "";

										if (strlen($os) > 0) {



											if (in_array($login_fabrica, array(169,170))) {
												if ($grupo_atendimento == 'R' && in_array($tipo_atendimento_descricao, array('RMA','Triagem'))) {
													$whereTipoAtendimento = "AND tbl_defeito_constatado.descricao = '{$tipo_atendimento_descricao}'";
												} else {
													$whereTipoAtendimento = "AND tbl_defeito_constatado.descricao NOT IN ('Triagem', 'RMA')";
												}
											}

											$sql = "
												SELECT DISTINCT
													tbl_defeito_constatado.defeito_constatado,
													tbl_defeito_constatado.descricao,
													tbl_defeito_constatado.lancar_peca,
													tbl_defeito_constatado.codigo,
													tbl_defeito_constatado.lista_garantia
												FROM tbl_diagnostico
												JOIN tbl_defeito_constatado ON tbl_defeito_constatado.defeito_constatado = tbl_diagnostico.defeito_constatado AND tbl_defeito_constatado.fabrica = $login_fabrica
												JOIN tbl_familia ON tbl_familia.familia = tbl_diagnostico.familia AND tbl_familia.fabrica   = $login_fabrica
												JOIN tbl_produto ON tbl_produto.familia = tbl_familia.familia AND tbl_produto.fabrica_i = $login_fabrica
												JOIN tbl_os ON tbl_os.produto = tbl_produto.produto and tbl_os.fabrica = tbl_diagnostico.fabrica
												WHERE tbl_diagnostico.fabrica = $login_fabrica
												AND tbl_os.os = $os
												AND tbl_diagnostico.ativo IS TRUE
												{$whereTipoAtendimento}
												ORDER BY tbl_defeito_constatado.descricao ASC;
											";

											$res = pg_query($con, $sql);
											$dcs = array();
											if (pg_num_rows($res) > 0) {
												while ($result = pg_fetch_object($res)) {

													$selected = ($result->defeito_constatado == getValue("defeito_constatado")) ? "selected" : "";
													$dcs[] = $result->defeito_constatado;
													 ?>

													<option
														value='<?=$result->defeito_constatado?>'
														data-lancar-pecas='<?=$result->lancar_peca?>'
														data-lista-garantia='<?=$result->lista_garantia?>'
														<?=$selected?>
													>
															<?=$result->codigo." - ".$result->descricao ?>
													</option>
												<? }
												$defeitos_constatados = implode(",",$dcs);
											}
										} ?>
										</select>
									</div>
								</div>
							</div>
						</div>
						<? if (in_array($login_fabrica, array(169,170)) && !empty(getValue('defeito_constatado'))) {
							$display_defeito_peca = 'style="display:block;"';
						} else {
							$display_defeito_peca = 'style="display:none;"';
						} ?>
						<div id="div_produto_defeito_peca" class="span3" <?= $display_defeito_peca; ?>>
							<div class="control-group <?=(in_array('defeito_peca', $msg_erro['campos'])) ? "error" : "" ?>">
								<label class="control-label" for="produto_defeito_peca">Defeito da Peça</label>
								<div class="controls controls-row">
									<div class="span12">
										<h5 class='asteristico'>*</h5>
										<select id="produto_defeito_peca" name="defeito_peca" class="span12">
											<option value="">Selecione</option>
											<?
											if (in_array($login_fabrica, array(169,170)) && !empty(getValue('defeito_constatado'))) {
												$sqlx = "
													SELECT
														d.defeito,
														d.codigo_defeito,
														d.descricao
													FROM tbl_diagnostico dg
													JOIN tbl_defeito d ON d.defeito = dg.defeito AND d.fabrica = {$login_fabrica} and d.ativo
                                                                                                        JOIN tbl_defeito_constatado dc ON dc.defeito_constatado = dg.defeito_constatado AND dc.fabrica = {$login_fabrica} AND dc.ativo
													WHERE dg.fabrica = {$login_fabrica}
													AND dg.defeito_constatado = ".getValue("defeito_constatado").";
												";
												$res = pg_query($con, $sqlx);

												if (pg_num_rows($res) > 0) {
													while ($result = pg_fetch_object($res)) {
														$selected = ($result->defeito == getValue("defeito_peca")) ? "selected" : ""; ?>
														<option value='<?= $result->defeito; ?>' <?= $selected; ?>><?= $result->codigo_defeito.' - '.($result->descricao); ?></option>
													<? }
												}
											} ?>
										</select>
									</div>
								</div>
							</div>
						</div>
					</div>

					<? } ?>
					<div class="row-fluid troca_ressarcimento" <?=$display_troca_ressarcimento?> >
						<div class="span1"></div>

						<div class="span8">
							<div class='control-group' >
								<label class="control-label" >Se o motivo da troca/ressarcimento for peça selecione as peças</label>
								<div class="controls controls-row">
									<div class="span12">
										<select class="pecas" name="pecas[<?=$produto_indice?>][]" multiple="multiple" >
											<?php

											$sql_pecas = "SELECT
															tbl_os_item.os_item,
															(tbl_peca.referencia || ' - ' || tbl_peca.descricao) AS peca
														  FROM tbl_os
														  INNER JOIN tbl_os_produto ON tbl_os_produto.os = tbl_os.os
														  INNER JOIN tbl_os_item ON tbl_os_item.os_produto = tbl_os_produto.os_produto
														  INNER JOIN tbl_peca ON tbl_peca.peca = tbl_os_item.peca AND tbl_peca.fabrica = {$login_fabrica}
														  WHERE tbl_os.fabrica = {$login_fabrica}
														  AND tbl_os_produto.os_produto = {$produto_indice}
														  ORDER BY tbl_peca.referencia ASC";

											$res_pecas = pg_query($con, $sql_pecas);

											if (pg_num_rows($res_pecas) > 0) {
												$pecas_post = getValue("pecas[{$produto->os_produto}]");

												while ($peca = pg_fetch_object($res_pecas)) {
													$selected = (in_array($peca->os_item, $pecas_post)) ? "selected" : "";

													echo "<option value='{$peca->os_item}' {$selected} >{$peca->peca}</option>";
												}
											}

											?>
										</select>
									</div>
								</div>
							</div>
						</div>


					</div>
				<?php
				} else {
					$total_trocados++;
					?>
					<br />
					<div class="alert alert-block alert-success" style="margin-bottom: 0px; margin: 10px;" >
						<h4>Produto já trocado/ressarcido</h4>
					</div>
<?php
                    if ($login_fabrica == 162  ) {
                        $sql = "SELECT COUNT(1) FROM tbl_ressarcimento WHERE os = $os";
                        $res = pg_query($con,$sql);
                        if (pg_fetch_result($res,0,0) == 1) {
                            $sql2 = "SELECT liberado FROM tbl_ressarcimento WHERE os = $os";
                            $res2 = pg_query($con,$sql2);
                            $temData = pg_fetch_result($res2,0,0);
                            if (empty($temData)) {
?>
                    <div class="row-fluid">
                        <div class="span1"></div>
                        <div class="span4">
                            <div class='control-group' >
                                <label class="control-label" for="data_pagamento">Data Pagamento</label>
                                <div class="controls controls-row">
                                    <div class="span12">
                                        <input type="text" class='data' id="data_pagamento" name="data_pagamento" value="" maxlength="10" />
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="span6">
                            <div class='control-group' >
                                <div class="controls controls-row">
                                    <div class="span2">
                                        <br />
                                        <button type="button" class="btn btn-primary btn-block" name="pagar"  >Gravar</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="span1"></div>
                    </div>

<?php

                            }
                        }
                    }
				}
?>
			</div>
			<br />
		<?php
		}
	}

	foreach (getValue("acao") as $os_produto => $acao) {
		if ($acao == "trocar") {
			$display_gera_pedido = "style='display: block;'";
			break;
		}
	}

	if ($total_trocados != pg_num_rows($res_produto)) {
	?>
		<div class="titulo_tabela">Informações Adicionais</div>

		<br />

		<div class="row-fluid">
			<div class="span2"></div>

			<div class="span4">
				<div class='control-group <?=(in_array("setor_responsavel", $msg_erro["campos"])) ? "error" : ""?>' >
					<div class="controls controls-row">
						<div class="span12 ">
								<h5 class="asteristico">*</h5>
							    <ul class="nav nav-list">
								    <li class="nav-header">Setor Responsável</li>
								    <?php if ($login_fabrica == 178){ ?>
									    <li><label class="radio"><input type="radio" name="setor_responsavel" value="sac" checked />SAC</label></li>
								    <?php } else { ?>
								    	<li><label class="radio"><input type="radio" name="setor_responsavel" value="revenda" <? if (getValue("setor_responsavel") == "revenda") echo "checked"; ?> />Revenda</label></li>
									    <li><label class="radio"><input type="radio" name="setor_responsavel" value="carteira" <? if (getValue("setor_responsavel") == "carteira") echo "checked"; ?> />Carteira</label></li>
									    <li><label class="radio"><input type="radio" name="setor_responsavel" value="sac" <? if (getValue("setor_responsavel") == "sac" || ($login_fabrica == 151 && !strlen(getValue("setor_responsavel")))) echo "checked"; ?> />SAC</label></li>
									    <li><label class="radio"><input type="radio" name="setor_responsavel" value="procon" <? if (getValue("setor_responsavel") == "procon") echo "checked"; ?> />Procon</label></li>
									<?php if(!in_array($login_fabrica,array(186))){ ?>
									    <li><label class="radio"><input type="radio" name="setor_responsavel" value="sap" <? if (getValue("setor_responsavel") == "sap") echo "checked"; ?> />SAP</label></li>
									<?php } ?>
									    <li><label class="radio"><input type="radio" name="setor_responsavel" value="suporte_tecnico" <? if (getValue("setor_responsavel") == "suporte_tecnico") echo "checked"; ?> />Suporte Técnico</label></li>
								    <?php } ?>
							    </ul>
						</div>
					</div>
				</div>
			</div>
			<div class="span4">
				<div class='control-group <?=(in_array("situacao_atendimento", $msg_erro["campos"])) ? "error" : ""?>' >
					<div class="controls controls-row">
						<div class="span12 ">
							<h5 class="asteristico">*</h5>
						    <ul class="nav nav-list">
							    <li class="nav-header">Situação do Atendimento</li>

							    <?php
							    	if(!in_array($login_fabrica, array(151,178))){
						    	?>
							    <li><label class="radio"><input type="radio" name="situacao_atendimento" value="0" <? if (getValue("situacao_atendimento") == "0") echo "checked"; ?> />Produto em Garantia</label></li>
							    <li><label class="radio"><input type="radio" name="situacao_atendimento" value="50" <? if (getValue("situacao_atendimento") == "50") echo "checked"; ?> />Faturado 50%</label></li>
							    <li><label class="radio"><input type="radio" name="situacao_atendimento" value="100" <? if (getValue("situacao_atendimento") == "100") echo "checked"; ?> />Faturado 100%</label></li>
							    <?php
									}else{
						    	?>
							    	<li><label class="radio"><input type="radio" name="situacao_atendimento" value="0" checked />Produto em Garantia</label></li>
						    	<?php
						    		}
					    		?>
						    </ul>
						</div>
					</div>
				</div>
			    <?php
			    if(in_array($login_fabrica, array(151))){
		    	?>
					<div class='gera_pedido control-group <?=(in_array("envio_consumidor", $msg_erro["campos"])) ? "error" : ""?>' <?=$display_gera_pedido?> >
						<div class="controls controls-row">
							<div class="span12 ">
								<h5 class="asteristico">*</h5>
							    <ul class="nav nav-list">
								    <li class="nav-header">Enviar o Produto</li>

								    <li><label class="radio"><input type="radio" name="envio_consumidor" value="t" <? if (getValue("envio_consumidor") == "t" || !strlen(getValue("envio_consumidor"))) echo "checked"; ?> />Consumidor</label></li>
								    <li><label class="radio"><input type="radio" name="envio_consumidor" value="f" <? if (getValue("envio_consumidor") == "f") echo "checked"; ?> />Posto Autorizado</label></li>
							    </ul>
							</div>
						</div>
					</div>
			    <?php
		    	}if($telecontrol_distrib){
		    	?>
		    		<div class='control-group' >
						<div class="controls controls-row">
							<div class="span12 ">
								<h5 class="asteristico">*</h5>
							    <ul class="nav nav-list">
								    <li class="nav-header">Troca Produto</li>
                                    <li><label class="radio"><input type="radio" name="troca_produto" id="fabrica_fabrica" value="" <? if (getValue("troca_produto") == "") echo "checked"; ?> />
                                    Fábrica <?=($login_fabrica==153) ? '(Envio pela PST)': ''?>
                                        </label>
                                    </li>
								    <li><label class="radio"><input type="radio" name="troca_produto" id="fabrica_distrib" value="4311" <? if (getValue("troca_produto") == "4311") echo "checked"; ?> />Distribuidor <?=($login_fabrica==153) ? '(Envio pela Telecontrol)': ''?></label></li>
							    </ul>
							</div>
						</div>
					</div>

		    	<?php
		    	 }
	    		?>
			</div>

			<div class="span2"></div>
		</div>
		<br />
		<br />

		<?php
		if (!in_array($login_fabrica, array(169, 170, 186))) {
		?>
			<div class="row-fluid gera_pedido" <?=$display_gera_pedido?> >
				<div class="span2"></div>

				<div class="span8">
					<div class='control-group' >
						<div class="controls controls-row">
							<div class="span12">
								<div class="alert alert-warning">
									<label type="checkbox">
										<h5>
											<input type="checkbox" name="gerar_pedido" id="gerar_pedido" value="t" <? if (getValue("gerar_pedido") == "t" || (!isset($_POST["btn_acao"]) && !in_array($login_fabrica, [167, 203]))) echo "checked"; ?> />
											Gerar Pedido ?
										</h5>
									</label>
								</div>
							</div>
						</div>
					</div>
				</div>

				<div class="span2"></div>
			</div>

			<br />
		<?php
		} elseif (in_array($login_fabrica, [186])) {
		?>
			<div class="row-fluid">
				<div class="span2"></div>
				<div class="span8">
					<div class="control-group">
						<div class="controls control-row">
							<div class="span12">
								<div class="alert alert-warning">
									<label type="radio">
										<h5>
											<input type="radio" name="gerar_pedido_com_at" value="troca_com" <?= ($_POST['gerar_pedido_com_at'] == "troca_com") ? "checked" : "" ?>>Gerar Pedido Troca Comercial
										</h5>
									</label>
									<br />
									<label type="radio">
										<h5>
											<input type="radio" name="gerar_pedido_com_at" value="troca_at" <?= ($_POST['gerar_pedido_com_at'] == "troca_at") ? "checked" : "" ?>>Gerar Pedido Troca Assistência
										</h5>
									</label>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		<?php
		}
		?>

		<div class="row-fluid" >
			<div class="span2"></div>

			<div class="span8">
				<div class='control-group' >
					<label class="control-label" for="observacao">Observações</label>
					<div class="controls controls-row">
						<div class="span12">
							<textarea name="observacao" class="span12" ><?=getValue("observacao")?></textarea>
						</div>
					</div>
				</div>
			</div>

			<div class="span2"></div>
		</div>

		<?php
		if (in_array($login_fabrica, array(138,165))) {
		?>
			<br /><br />

			<div id="div_anexo" class="tc_formulario">
				<div class="titulo_tabela">Anexo</div>

				<br />

				<div class="tac" >
					<?php
					if (!strlen(getValue("anexo_chave"))) {
					       $anexo_chave = sha1(date("Ymdhi")."{$login_fabrica}{$login_posto}");
					} else {
					       $anexo_chave = getValue("anexo_chave");
					}

					echo "<input type='hidden' name='anexo_chave' value='{$anexo_chave}' />";

					$anexo_imagem = "imagens/imagem_upload.png";
					$anexo        = "";

					if (strlen(getValue("anexo")) > 0) {
						$anexos       = $s3->getObjectList(getValue("anexo"), true);
						$anexo_imagem = $s3->getLink("thumb_".basename($anexos[0]), true);
						$anexo        = getValue("anexo");
					} else {
					    $anexos = $s3->getObjectList("{$os}_comprovante_troca.", false);

					    if (count($anexos) > 0) {
							$anexo_imagem = $s3->getLink("thumb_".basename($anexos[0]), false);
							$anexo        = basename($anexos[0]);
					    }
					}
					?>
					<div class="tac" style="display: inline-block; margin: 0px 5px 0px 5px;">
						<img src="<?=$anexo_imagem?>" class="anexo_thumb" style="width: 100px; height: 90px;" />

						<img src="imagens/loading_img.gif" class="anexo_loading" style="width: 64px; height: 64px; display: none;" />

						<input type="hidden" rel="anexo" name="anexo" value="<?=$anexo?>" />
						<button type="button" class="btn btn-mini btn-primary btn-block" name="anexar"  >Anexar</button>
					</div>
				</div>
			</div>
		<?php
		}
		?>

		<?php
		if(in_array($login_fabrica, array(151))){
			$classOs = new \Posvenda\Os($login_fabrica);

			$status_os_peca = $classOs->verificaLancamentoPecaOsTroca($os);

			if($status_os_peca == true){

		?>
				<br />
				<div class="row-fluid" >
					<div class="span2"></div>

					<div class="span8">
						<div class="alert alert-block alert-warning">
							Há peças na Ordem de Serviço que foram trocadas utilizando o estqoue do posto, as mesmas serão
							canceladas. Qualquer ajuste no estoque do posto deve ser realizado manualmente.
						</div>
					</div>

					<div class="span2"></div>
				</div>
		<?php
			}
		}
		?>

		<br />

        <?php if($btn_gravar_hidden != true){ ?>

		<p>
            <br />
            <button class='btn' id="btn_acao" type="button"  onclick="submitForm($(this).parents('form'));">Gravar</button>
			<input type='hidden' id="btn_click" name='btn_acao' value='' />
			<input type='hidden' id="interventor_admin" name='interventor_admin' value='' />

		</p>

        <br />

	<?php
        }
	}
	?>
</form>

<?php 
if ($login_fabrica == 183){ ?>
<div style="width: 800px; overflow-y: auto; vertical-align: middle; margin-left: -360px;" id="modal-aprova-os" class="modal hide fade" data-backdrop="static" data-keyboard="false" >
    <div class="modal-header">
    </div>
    <div class="modal-body">
        <form>
            <fieldset>
                <legend>Endereço cliente</legend>
                <div class="alert alert-error alert_endereco" style="display: none;">
					<h4>Erro ao pesquisar endereço</h4>
			    </div>
				<div class="row-fluid div_dados" >
					<div class="span4">
						<div class='control-group' >
							<label class="control-label" for="consumidor_cep">Cep</label>
							<div class="controls controls-row">
								<div class="span12">
									<input type='text' id="consumidor_cep" name='consumidor_cep' class='numeric input_end' value=''>
								</div>
							</div>
						</div>
					</div>
					<div class="span4">
						<div class='control-group' >
							<label class="control-label" for="consumidor_endereco">Endereço</label>
							<div class="controls controls-row">
								<div class="span12">
									<input type='text' id="consumidor_endereco" name='consumidor_endereco' class='input_end' value=''>
								</div>
							</div>
						</div>
					</div>
					<div class="span2">
						<div class='control-group' >
							<label class="control-label" for="consumidor_numero">Número</label>
							<div class="controls controls-row">
								<div class="span12">
									<input type='text' id="consumidor_numero" name='consumidor_numero' class='input_end' value=''>
								</div>
							</div>
						</div>
					</div>
				</div>
				
				<div class="row-fluid div_dados" >
					<div class="span4">
						<div class='control-group' >
							<label class="control-label" for="consumidor_complemento">Complemento</label>
							<div class="controls controls-row">
								<div class="span12">
									<input type='text' id="consumidor_complemento" name='consumidor_complemento' class='input_end' value=''>
								</div>
							</div>
						</div>
					</div>
					<div class="span4">
						<div class='control-group' >
							<label class="control-label" for="consumidor_bairro">Bairro</label>
							<div class="controls controls-row">
								<div class="span12">
									<input type='text' id="consumidor_bairro" name='consumidor_bairro' class='input_end' value=''>
								</div>
							</div>
						</div>
					</div>
					<div class="span2">
						<div class='control-group' >
							<label class="control-label" for="consumidor_referencia">Ponto referência</label>
							<div class="controls controls-row">
								<div class="span12">
									<input type='text' id="consumidor_referencia" name='consumidor_referencia' class='input_end' value=''>
								</div>
							</div>
						</div>
					</div>
				</div>

				<div class="row-fluid div_dados" >
					<div class="span4">
						<div class='control-group' >
							<label class="control-label" for="consumidor_estado">Estado</label>
							<div class="controls controls-row">
								<select id="consumidor_estado" name="consumidor_estado" class="span12" >
	                                <option value="">Selecione</option>
	                                <? foreach ($array_estados as $sigla => $nome_estado) { ?>
	                                    <option value="<?= $sigla; ?>" <?= $selected; ?>><?= $nome_estado; ?></option>
	                                <? } ?>
	                            </select>
							</div>
						</div>
					</div>
					<div class="span4">
						<div class='control-group' >
							<label class="control-label" for="consumidor_cidade">Cidade</label>
							<div class="controls controls-row">
								<select id="consumidor_cidade" name="consumidor_cidade" class="span12" >
                                	<option value="" >Selecione</option>
                                </select>
							</div>
						</div>
					</div>
				</div>
			</fieldset>
        </form>
    </div>
    <div class="modal-footer">
        <button type="button" id="btn-close-modal-aprova-os" class="btn">Fechar</button>
        <button type="button" id="btn-aprovar-os" class="btn btn-success">Atualizar</button>
    </div>
</div>

<?php } ?>
<?php if (in_array($login_fabrica, array(138,165))) { ?>
	<form name="form_anexo" method="post" action="os_troca_subconjunto.php" enctype="multipart/form-data" style="display: none;" >
		<input type="file" name="anexo_upload" value="" />

		<input type="hidden" name="ajax_anexo_upload" value="t" />
		<input type="hidden" name="anexo_chave" value="<?=$anexo_chave?>" />
	</form>
<?php } ?>

<script type="text/javascript">
	$(document).ready(function(){

		<?php if ($login_fabrica == 183 AND strlen(trim($interventor_admin)) == 0){ ?>
			let os_troca_valida = <?=$os?>;
            setTimeout(function() {
                Shadowbox.open({
                    content: "verifica_interventor.php?os_troca_subconjunto=TRUE&os_troca="+os_troca_valida ,
                    player: "iframe",
                    width: 700,
                    height: 270,
                    options: {
                        modal: true,
                        enableKeys: false
                    }
                });
                $("#sb-nav").css({ display: "none" });
            },1000);

			$("input[name='consumidor_cep']").mask("99999-999",{placeholder:""});
			
			$("#enviar_para").change(function(){
				let enviar_para = $(this).val();
				if (enviar_para == "C"){
					$("#div_btn_dados").show();
				}else{
					$("#div_btn_dados").hide();
				}
			});

			$("#btn_endereco").click(function(){
				let os_consulta = '<?=$os?>';
				$.ajax({
                    url:"os_troca_subconjunto.php",
                    type:"POST",
                    data:{
                        visualizar_endereco:true,
                        os_consulta:os_consulta
                    }
                })
                .done(function(data){
                	data = JSON.parse(data);
                	$("#modal-aprova-os").modal("show");
                	if (data.error == "error"){
                		$(".alert_endereco").show();
                		$(".div_dados").hide();
                	}else{
                		$(".alert_endereco").hide();
                		$(".div_dados").show();
                		busca_cidade(data.consumidor_estado);
                		$("input[name='consumidor_bairro']").val(data.consumidor_bairro);
                		$("input[name='consumidor_cep']").val(data.consumidor_cep);
                		$("select[name='consumidor_cidade']").val(data.consumidor_cidade);
                		$("input[name='consumidor_complemento']").val(data.consumidor_complemento);
                		$("input[name='consumidor_endereco']").val(data.consumidor_endereco);
                		$("select[name='consumidor_estado']").val(data.consumidor_estado);
                		$("input[name='consumidor_numero']").val(data.consumidor_numero);
                		$("input[name='consumidor_referencia']").val(data.consumidor_referencia);
                	}
                });
            });

			$("#btn-close-modal-aprova-os").on("click", function() {
			    $("#modal-aprova-os").modal("hide");
			    $(".alert_modal").remove();
			});

			$("input[name='consumidor_cep']").blur(function() {
		        if ($(this).attr("readonly") == undefined) {
		            busca_cep($(this).val());
		        }
		    });

		    $("select[name='consumidor_estado']").change(function() {
		        busca_cidade($(this).val());
		    });

		    $("#btn-aprovar-os").on("click", function() {
			    $(".alert_modal").remove();
			    let btn         = $(this);
			    let btn_fechar  = $("#btn-close-modal-aprova-os");
			    let os_consulta = '<?=$os?>';
			   	
			    let consumidor_cep 			= $("#consumidor_cep").val();
			    let consumidor_endereco 	= $("#consumidor_endereco").val();
			    let consumidor_numero 		= $("#consumidor_numero").val();
			    let consumidor_complemento 	= $("#consumidor_complemento").val();
			    let consumidor_bairro		= $("#consumidor_bairro").val();
			    let consumidor_referencia 	= $("#consumidor_referencia").val();
			    let consumidor_estado 		= $("#consumidor_estado").val();
			    let consumidor_cidade 		= $("#consumidor_cidade").val();

			    var data_ajax = {
			        atualiza_dados_consumidor: true,
			        os_consulta: os_consulta,
			        consumidor_cep: consumidor_cep,
					consumidor_endereco: consumidor_endereco,
					consumidor_numero: consumidor_numero,
					consumidor_complemento: consumidor_complemento,
					consumidor_bairro: consumidor_bairro,
					consumidor_referencia: consumidor_referencia,
					consumidor_estado: consumidor_estado,
					consumidor_cidade: consumidor_cidade
			    };
			   
			    $.ajax({
			        url: "os_troca_subconjunto.php",
			        type: "post",
			        data: data_ajax,
			        beforeSend: function() {
			            $(btn).prop({ disabled: true }).text("Atualizando...");
			            $(btn_fechar).prop({ disabled: true });
			        },
			        async: false,
			        timeout: 10000
			    }).fail(function(res) {
			        $("#modal-aprova-os").find("div.modal-body").prepend("<div class='alert alert-danger' >Ocorreu um erro ao aprovar a OS</div>");
			        $(btn).prop({ disabled: false }).text("Atualizar");
			        $(btn_fechar).prop({ disabled: false });
			    }).done(function(res) {
			        res = JSON.parse(res);
			        
			        if (res.retorno == "error") {
			            $("#modal-aprova-os").find("div.modal-body").prepend("<div class='alert alert-danger alert_modal' >Erro ao atualizar o endereço</div>");
			            $(btn).prop({ disabled: false }).text("Atualizar");
			        } else {
			            $("#modal-aprova-os").find("div.modal-body").prepend("<div class='alert alert-success alert_modal' >Endereço atualizado</div>");
			            $(btn).text("Atualizar");
			            $(btn).prop({ disabled: false }).text("Atualizar");
			        }

			        $(btn_fechar).prop({ disabled: false });
			    });
			});
		<?php } ?>
		
		<?php if ($login_fabrica == 151) { ?>
				let os_posto = '<?=$os?>';
				
				$.ajax({
                    url:"os_troca_subconjunto.php",
                    type:"POST",
                    data:{
                        posto_credenciado:true,
                        os_posto:os_posto
                    }
                })
                .done(function(data){
                	if (data == 'ok') {
                        valida_interventor(os_posto);
                        $("#posto_status").val('DESCREDENCIADO');
                    }
                });
		<?php } ?>

		/**
		 * Função de retorno da lupa de revenda
		 */
	});
	function retorna_revenda(retorno) {
	    $("#revenda_nome").val(retorno.razao);
	    $("#revenda_cnpj").val(retorno.cnpj);
	}

	<?php if ($login_fabrica == 183){ ?>
		function busca_cep(cep, method) {
		    if (cep.length > 0) {
		        var img = $("<img />", { src: "imagens/loading_img.gif", css: { width: "30px", height: "30px" } });

		        if (typeof method == "undefined" || method.length == 0) {
		            method = "webservice";
		            $.ajaxSetup({
		                timeout: 3000
		            });
		        } else {
		            $.ajaxSetup({
		                timeout: 5000
		            });
		        }

		        $.ajax({
		            async: true,
		            url: "ajax_cep.php",
		            type: "GET",
		            data: { cep: cep, method: method },
		            beforeSend: function() {
		                $("#consumidor_estado").next("img").remove();
		                $("#consumidor_cidade").next("img").remove();
		                $("#consumidor_bairro").next("img").remove();
		                $("#consumidor_endereco").next("img").remove();

		                $("#consumidor_estado").hide().after(img.clone());
		                $("#consumidor_cidade").hide().after(img.clone());
		                $("#consumidor_bairro").hide().after(img.clone());
		                $("#consumidor_endereco").hide().after(img.clone());
		            },
		            error: function(xhr, status, error) {
		                busca_cep(cep, "database");
		            },
		            success: function(data) {
		                results = data.split(";");
		                
		                if (results[0] != "ok") {
		                    alert(results[0]);
		                    $("#consumidor_cidade").show().next().remove();
		                } else {
		                    $("#consumidor_estado").val(results[4]);

		                    busca_cidade(results[4]);
		                    results[3] = results[3].replace(/[()]/g, '');

		                    $("#consumidor_cidade").val(retiraAcentos(results[3]).toUpperCase());

		                    if (results[2].length > 0) {
		                        $("#consumidor_bairro").val(results[2]);
		                    }

		                    if (results[1].length > 0) {
		                        $("#consumidor_endereco").val(results[1]);
		                    }
		                }

		                $("#consumidor_estado").show().next().remove();
		                $("#consumidor_bairro").show().next().remove();
		                $("#consumidor_endereco").show().next().remove();

		                if ($("#consumidor_bairro").val().length == 0) {
		                    $("#consumidor_bairro").focus();
		                } else if ($("#consumidor_endereco").val().length == 0) {
		                    $("#consumidor_endereco").focus();
		                } else if ($("#consumidor_numero").val().length == 0) {
		                    $("#consumidor_numero").focus();
		                }

		                $.ajaxSetup({
		                    timeout: 0
		                });
		            }
		        });
		    }
		}

		function busca_cidade(estado, cidade) {
		    $("#consumidor_cidade").find("option").first().nextAll().remove();

		    if (estado.length > 0) {
		        $.ajax({
		            async: false,
		            url: "cadastro_os_revenda.php",
		            type: "POST",
		            data: { ajax_busca_cidade: true, estado: estado },
		            beforeSend: function() {
		                if ($("#consumidor_cidade").next("img").length == 0) {
		                    $("#consumidor_cidade").hide().after($("<img />", { src: "imagens/loading_img.gif", css: { width: "30px", height: "30px" } }));
		                }
		            },
		            complete: function(data) {
		                data = $.parseJSON(data.responseText);

		                if (data.error) {
		                    alert(data.error);
		                } else {
		                    $.each(data.cidades, function(key, value) {
		                        var option = $("<option></option>", { value: value, text: value });
		                        $("#consumidor_cidade").append(option);
		                    });
		                }

		                $("#consumidor_cidade").show().next().remove();
		            }
		        });
		    }

		    if(typeof cidade != "undefined" && cidade.length > 0){
		        $("#consumidor_cidade option[value='"+cidade+"']").attr('selected','selected');
		    }
		}

		function retiraAcentos(palavra){
		    if (!palavra) {
		        return "";
		    }

		    var com_acento = 'áàãâäéèêëíìîïóòõôöúùûüçÁÀÃÂÄÉÈÊËÍÌÎÏÓÒÕÖÔÚÙÛÜÇ';
		    var sem_acento = 'aaaaaeeeeiiiiooooouuuucAAAAAEEEEIIIIOOOOOUUUUC';
		    var newPalavra = "";

		    for(i = 0; i < palavra.length; i++) {
		        if (com_acento.search(palavra.substr(i, 1)) >= 0) {
		            newPalavra += sem_acento.substr(com_acento.search(palavra.substr(i, 1)), 1);
		        } else {
		            newPalavra += palavra.substr(i, 1);
		        }
		    }

		    return newPalavra.toUpperCase();
		}
	<?php } ?>
</script>

<?php
include "rodape.php";
?>
