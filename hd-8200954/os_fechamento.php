<?php
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include "autentica_usuario.php";
include "funcoes.php";
include "class/AuditorLog.php";
include "class/aws/anexaS3.class.php";
include_once 'class/communicator.class.php';
date_default_timezone_set('America/Sao_Paulo');

function verificaTodasAuditoria($status_oss) {
	$tipos = array(
		"62"  => array(64,13,131),
		"67"  => array(19,90,13,131),
		"68"  => array(19,90,13,131),
		"70"  => array(19,90,13,131),
		"95"  => array(19,13),
		"134"  => array(19,90,13,131,135,139),
		"102"  => array(103,104),
		"157"  => array(19,90,13,131),
	);

	TIRARSTATUS:

    foreach($tipos as $tipo_key => $tipo_value ) {
		if(in_array($tipo_key,$status_oss)){
			$os_status = array_search($tipo_key,$status_oss);
			foreach($tipo_value as $tipo_aprovado) {
				if(in_array($tipo_aprovado,$status_oss)){
					$status_os = array_search($tipo_aprovado,$status_oss);
					unset($status_oss[$os_status]);
					$status_oss_aux = $status_oss;
					unset($status_oss[$status_os]);
					if(in_array($tipo_key,$status_oss)){
						goto TIRARSTATUS;
					}

					if($os_status < $status_os ){
						unset($tipos[$tipo_key]);
						goto TIRARSTATUS;
					}else{
						if(in_array($tipo_aprovado,$status_oss)){
							$status_os2 = array_search($tipo_aprovado,$status_oss);
							unset($status_oss[$status_os2]);
							$status_oss[$status_os] = $tipo_aprovado;
							goto TIRARSTATUS;
						}
					}
				}
			}

			if(count($status_oss) == 0) return true;

			return false;
		}else{
			unset($tipos[$tipo_key]);
			goto TIRARSTATUS;
		}
	}
	return true;
}

if (in_array($login_fabrica, [30])) {
	$sqlValidaPosto = "select digita_os, parametros_adicionais from tbl_posto_fabrica where fabrica = {$login_fabrica} and posto = {$login_posto}";
	$resValidaPosto = pg_query($con, $sqlValidaPosto);
	$parametros_adicionais = json_decode(pg_fetch_result($resValidaPosto, 0, 'parametros_adicionais'));
	$posto_digita_os = pg_fetch_result($resValidaPosto, 0, 'digita_os');

	if ((isset($parametros_adicionais->digita_os_consumidor) == false || $parametros_adicionais->digita_os_consumidor == 'f') && $posto_digita_os <> 't') {
		header("location:menu_inicial.php");
	}
}

if ($_POST["verificaPedido"]) {
	$xos = $_POST["os"];

	$sql_x = "	SELECT tbl_pedido_item.qtde_faturada,tbl_pedido_item.qtde_cancelada,
						tbl_os_item_nf.nota_fiscal
				FROM tbl_os_produto
				JOIN tbl_os_item ON tbl_os_produto.os_produto = tbl_os_item.os_produto
				JOIN tbl_pedido_item ON tbl_os_item.pedido_item = tbl_pedido_item.pedido_item
				JOIN tbl_pedido ON tbl_os_item.pedido = tbl_pedido.pedido
				LEFT JOIN tbl_os_item_nf ON tbl_os_item.os_item = tbl_os_item_nf.os_item
				WHERE tbl_os_item.fabrica_i = $login_fabrica
				AND tbl_os_produto.os = $xos";
	$res_x = pg_query($con, $sql_x);
	if (pg_num_rows($res_x) > 0) {
		$qtdeFaturada = pg_fetch_all($res_x);
		foreach ($qtdeFaturada as $key => $value) {
			if (!empty($value["nota_fiscal"])) {
				echo "nao";
				exit();
			}

			if ($value["qtde_faturada"] == 0 && $value["qtde_cancelada"] == 0 && empty($value["nota_fiscal"])) {
				echo "ok";
				exit();		
			}
		}
	}

	echo "nao";
	exit();

}

if ($_POST["verificaEstoque"]) {
	$xos = $_POST["os"];

	$sql_peca = "	SELECT  tbl_os_item.peca, 
							tbl_os_item.qtde,
							tbl_os_item.os_item
					FROM tbl_os_produto
					JOIN tbl_os_item ON tbl_os_produto.os_produto = tbl_os_item.os_produto
					LEFT JOIN tbl_pedido_item ON tbl_os_item.pedido_item = tbl_pedido_item.pedido_item
					WHERE tbl_os_item.fabrica_i = $login_fabrica
					AND tbl_os_produto.os = $xos
					AND (tbl_os_item.pedido IS NULL OR (tbl_pedido_item.qtde_faturada = 0 AND tbl_pedido_item.qtde_cancelada = 0))";
	$res_peca = pg_query($con, $sql_peca);
	if (pg_num_rows($res_peca) > 0) {
		$pecas = pg_fetch_all($res_peca);
		$pecas_faltantes = $pecas;

		foreach ($pecas as $key => $value) {
			$sql_estoque = " SELECT (SUM(coalesce(qtde_entrada,0)) - SUM(coalesce(qtde_saida, 0))) AS estoque_ano 
							 FROM tbl_estoque_posto_movimento 
							 WHERE fabrica = $login_fabrica 
							 AND posto = $login_posto
							 AND peca = {$value['peca']} 
							 AND data BETWEEN (CURRENT_DATE - INTERVAL '1 year') AND CURRENT_DATE";
			$res_estoque = pg_query($con, $sql_estoque);
			if (pg_num_rows($res_estoque) > 0) {
				$estoque_ano = pg_fetch_result($res_estoque, 0, 'estoque_ano');
				if  ($estoque_ano >= $value['qtde']) {
					unset($pecas_faltantes[$key]);
				}
			}else{
				exit('Sem Estoque');
				break;
			}
		}

	} else {
		echo "sim";
		exit();
	}

	if (count($pecas_faltantes) > 0) {
		$pecas_desc = [];
		foreach ($pecas_faltantes as $k => $v) {
			$sql_p = "SELECT referencia || ' - ' || descricao AS pc 
					  FROM tbl_peca 
					  WHERE peca = {$v['peca']}
					  AND fabrica = $login_fabrica";
			$res_p = pg_query($con, $sql_p);
			if (pg_num_rows($res_p) > 0) {
				$pecas_desc[] = pg_fetch_result($res_p, 0, 'pc'); 
			} 
		}
		$pecas_todas = implode(",", $pecas_desc);
		echo $pecas_todas;
		exit();
	} else {
		echo 'sim';
		exit();
	}
}

if ($_POST["ajax_grava_inicio_fim"]) {
	try {
		$os = $_POST["os"];

		if (isset($_POST["inicio_atendimento"])) {
			$data = $_POST["inicio_atendimento"];
			$coluna = "inicio_atendimento";
			$campo = "Inicio Atendimento";
		}

		if (isset($_POST["fim_atendimento"])) {
			$data = $_POST["fim_atendimento"];
			$coluna = "termino_atendimento";
			$campo = "Fim Atendimento";
		}

		pg_query($con, "BEGIN");

		if (empty($os)) {
			throw new Exception("Erro ao gravar o {$campo}, OS não informada");
		}

		$sql = "SELECT os, data_digitacao,descricao FROM tbl_os join tbl_posto_fabrica using(posto, fabrica) join tbl_tipo_posto using(tipo_posto) WHERE os = {$os} AND tbl_os.fabrica = {$login_fabrica} AND posto = {$login_posto}";
		//$sql = "SELECT os, extract(HOURS FROM (current_timestamp-data_digitacao)) AS datadigitacao FROM tbl_os WHERE os = {$os} AND fabrica = {$login_fabrica} AND posto = {$login_posto}";
		$res = pg_query($con, $sql);

		if (!pg_num_rows($res)) {
			throw new Exception("Erro ao gravar o {$campo}, não foi possível buscar os dados da OS {$os}");
		}


		if (!empty($data)) {
			list($data, $hora)     = explode(" ", $data);
			list($dia, $mes, $ano) = explode("/", $data);

			$data = "$ano-$mes-$dia $hora";

			if (!strtotime($data) || preg_match("/^24/", $hora) || strtotime($data) > strtotime(date("Y-m-d H:i"))) {
				throw new Exception("Erro ao gravar o {$campo} da OS {$os}, data inválida");
			}
			$xdata = $data;
			$data = "'{$data}'";
		} else {
			$data = "null";
		}

		$update = "
			UPDATE tbl_os_extra SET
				{$coluna} = {$data}
			WHERE os = {$os};

			INSERT into tbl_os_interacao(
				os,data, posto, comentario, fabrica, programa
			)values(
				$os, now(), $login_posto, 'OS com $campo: $xdata preenchida', $login_fabrica, 'os_fechamento.php'
			);

		";
		$resUpdate = pg_query($con, $update);

		if (strlen(pg_last_error()) > 0) {
			throw new Exception("Erro ao gravar o {$campo} da OS {$os}". pg_last_error());
		}

		if (isset($_POST["fim_atendimento"])) {
			$update = "
				UPDATE tbl_os SET
					data_conserto = {$data}
				WHERE fabrica = {$login_fabrica}
				AND os = {$os}
			";
			$resUpdate = pg_query($con, $update);

			if (strlen(pg_last_error()) > 0) {
				throw new Exception("Erro ao gravar o {$campo} da OS {$os}");
			}
		}

		pg_query($con, "COMMIT");

		exit(json_encode(array("sucesso" => true)));
	} catch(Exception $e) {
		pg_query($con, "ROLLBACK");

		exit(json_encode(array("erro" => utf8_encode($e->getMessage()))));
	}
}

function convertData($data){
    $data = explode(" ",$data);
    if(strpos($data[0],"/") == true){
	    list($dia, $mes, $ano) = explode("/", $data[0]);
	    $data[0] = $ano."-".$mes."-".$dia;
    }
    return $data[0];
}

function validaAgendamentoData($data, $os) {
	global $con, $login_fabrica;

	$sqlTp = "	SELECT confirmado, data_agendamento
				FROM tbl_os 
				JOIN tbl_tecnico_agenda USING(os)
				JOIN tbl_tipo_atendimento USING(tipo_atendimento)
				WHERE tbl_os.os = $os
				AND UPPER(tbl_tipo_atendimento.descricao) = 'RECALL DOMICÍLIO'
				AND tbl_os.fabrica = $login_fabrica
			 ";
	$resTp = pg_query($con, $sqlTp);
	if (pg_num_rows($resTp) > 0) {
		$confirmado = pg_fetch_result($resTp, 0, 'confirmado');
		$data_ag    = pg_fetch_result($resTp, 0, 'data_agendamento'); 

		if (empty($confirmado)) {
			return true;
		}

		$data = str_replace("'", '', $data);

		if (strtotime($data_ag) > strtotime($data)) {
			return true;
		}
	}

	return false;
}

function verifica_tipo_posto($tipo, $valor) {
	global $con, $login_fabrica, $login_posto, $areaAdmin, $posto_id;

	if (empty($areaAdmin)) {
		$areaAdmin = preg_match('/\/admin\//',$_SERVER['PHP_SELF']) > 0 ? true : false;
	}

	$id_posto  = ($areaAdmin == true) ? $posto_id : $login_posto;
	$sql = "
		SELECT tbl_tipo_posto.tipo_posto
		FROM tbl_posto_fabrica
		INNER JOIN tbl_tipo_posto ON tbl_tipo_posto.tipo_posto = tbl_posto_fabrica.tipo_posto
		WHERE tbl_posto_fabrica.fabrica = {$login_fabrica}
		AND tbl_posto_fabrica.posto = {$id_posto}
		AND tbl_tipo_posto.{$tipo} IS {$valor}
	";
	$res = pg_query($con, $sql);

	if (pg_num_rows($res) > 0) {
		return true;
	} else {
		return false;
	}
}

function data_conserto_vazia($os) {
	global $con, $login_fabrica;

	$sql = "SELECT data_conserto FROM tbl_os WHERE os = $os AND fabrica = $login_fabrica AND data_conserto NOTNULL";
	$res = pg_query($con, $sql);

	if (pg_num_rows($res) > 0) {
		return false;
	} else {
		$sql = " SELECT tbl_servico_realizado.servico_realizado 
				 FROM tbl_os_produto 
				 JOIN tbl_os_item USING(os_produto) 
				 JOIN tbl_servico_realizado USING(servico_realizado) 
				 WHERE tbl_servico_realizado.fabrica = $login_fabrica 
				 AND tbl_os_produto.os = $os 
				 AND tbl_servico_realizado.troca_produto IS TRUE ";
		$res = pg_query($con, $sql);
		if (pg_num_rows($res) > 0) {
			return false;
		} else {
			return true;
		}
	}	
}

function tem_anexo($os, $gravar = null) {
	global $con, $login_fabrica;
	$tem_termo = false;


    if (data_corte_termo($os)) {
		//Termo de retirada só deve ficar disponível para anexo em ordens de serviço que estiverem com status consertada ou troca de produto.

    	$sql_imprimiu_termo = "SELECT campos_adicionais
                      		   FROM tbl_os_campo_extra
                      		   WHERE os = {$os}
                      		   AND fabrica = {$login_fabrica}";
        $res_imprimiu_termo = pg_query($con, $sql_imprimiu_termo);
        if (pg_num_rows($res_imprimiu_termo) > 0) {
        	$termos = json_decode(pg_fetch_result($res_imprimiu_termo, 0, 'campos_adicionais'), true);
        	if (isset($termos['termo_retirada_produto'])) {
                $tem_termo = true;
            }
        }

        if ($tem_termo) {
	    	$sql_consertada = " SELECT DISTINCT tbl_os.os 
	    				   		FROM tbl_os 
		    				    LEFT JOIN tbl_os_troca ON tbl_os.os = tbl_os_troca.os 
		    				    WHERE tbl_os.os = {$os} 
		    				    AND tbl_os.fabrica = {$login_fabrica}
		    				    AND (tbl_os.data_conserto IS NOT NULL OR tbl_os_troca.os = {$os})";
			$res_consertada = pg_query($con, $sql_consertada);
			if (pg_num_rows($res_consertada) > 0) {
				$tem_termo = false;
				$sql_termo = "SELECT obs FROM tbl_tdocs WHERE referencia_id = $os AND fabrica = $login_fabrica AND situacao = 'ativo'";
	            $res_termo = pg_query($con, $sql_termo);
	            if (pg_num_rows($res_termo) > 0) {
	                for ($t=0; $t < pg_num_rows($res_termo); $t++) { 
	                    $anexou_termo = pg_fetch_result($res_termo, $t, 'obs');
	                    $anexou_termo = json_decode($anexou_termo, true);
	                    if ($anexou_termo[0]['termo_devolucao'] == 'ok') {
	                        $tem_termo = true;
	                        break;
	                    }
	                }
	                if ($tem_termo === true) {
	                	return 'ok';
	                } else {
	                	return 'anexa_termo';
	                }
	            } else {
					return 'anexa_termo';
				}
			} else {
				return 'erro';
			}
		} else {
			return 'imprimir_termo';
		}

    } else {
        return 'ok';
    }
}


function valida_numero_de_serie_jfa($serie,$produto){
    global $con, $login_fabrica;

    $sql = "SELECT 	numero_serie_obrigatorio
    				referencia
    			FROM tbl_produto
    			WHERE produto = {$produto}
    				AND fabrica_i = {$login_fabrica}";
    $res = pg_query($con, $sql);

    if(pg_num_rows($res) > 0){
        $numero_serie_obrigatorio = pg_fetch_result($res, 0, "numero_serie_obrigatorio");
        $prod_referencia = pg_fetch_result($res, 0, "referencia");

        if(!empty($serie)){
        	$dataSerieInvalida = false ;

            $serieData = substr($serie, 0,4);
            $serieDataMes = substr($serie, 0,2);
            $serieDataAno = substr($serie, 2,2);
            $serieReferencia = substr($serie, 4);

            if (!is_numeric($serieData)) {
                $dataSerieInvalida = true;
            }

            if (!preg_match('/^(0[1-9]|1[0-2])$/', $serieDataMes)) {
                $dataSerieInvalida = true;
            }

            if (!preg_match('/^(10|[1-9][1-9])$/', $serieDataAno)) {
                $dataSerieInvalida = true;
            }

            //verificar se é a referencia do produto
            if ( mb_strtoupper($prod_referencia) != mb_strtoupper($serieReferencia) ) {
                $dataSerieInvalida = true;
            }

            if ($dataSerieInvalida == false) {
            	return true;
            }
        }
    }
    return false;
}

$programa_insert = $_SERVER['PHP_SELF'];

include_once "class/sms/sms.class.php";
include_once('anexaNF_inc.php');

$envia_pesquisa_finaliza_os = array(161);

$count = 0;
// if(isset($_POST["os"])){
// 	foreach ($_POST["os"] as $i => $value) {
// 		if($count == 10){
// 			echo "</br>";
// 			$count = 0;
// 		}
// 		echo ",".$value["os_$i"];
// 		$count++;
// 	}
// 	exit;
// }

if(in_array($login_fabrica, array(15,140))){
	include "class/log/log.class.php";
	$email_consumidor = new Log();
}

if ($login_posto == '7214') {
	header("location:os_fechamento_posto_intelbras.php");
}

if ($login_fabrica == 35) {
	header("location:menu_os.php");
}

if ($_GET['remanufaturar'] == true) {
    $os = $_GET['os'];

    if (empty($os)) {
    	$retorno = array("error" => utf8_encode(traduz("OS não informada")));
    } else {
	    pg_query($con, "BEGIN");

	    $sql = "SELECT data_conserto FROM tbl_os WHERE fabrica = {$login_fabrica} AND os = {$os} AND data_conserto IS NOT NULL";
	    $res = pg_query($con, $sql);

	    if (pg_num_rows($res) > 0) {
		    $sql = "SELECT classificacao_os
		            FROM tbl_classificacao_os
		            WHERE fabrica = $login_fabrica
		            AND lower(descricao) = 'remanufaturada'";
		    $res = pg_query($con, $sql);

		    if (pg_num_rows($res) > 0) {

		        $classificacao_os = pg_fetch_result($res, 0, 'classificacao_os');

		        $sql = "UPDATE tbl_os_extra SET
		        			classificacao_os = $classificacao_os
		                WHERE os = $os";
		        $res = pg_query($con, $sql);

		        if (!strlen(pg_last_error())) {
		            $sql = "SELECT fn_os_status_checkpoint_os($os)";
		            $res = pg_query($con, $sql);

		            if (!strlen(pg_last_error())) {
		                $status_checkpoint = pg_fetch_result($res, 0, 0);

		                $sql = "UPDATE tbl_os SET status_checkpoint = {$status_checkpoint} WHERE os = $os";
		                $res = pg_query($con, $sql);
		            } else {
		            	$retorno = array("error" => utf8_encode(traduz("Erro ao remanufaturar OS")));
		            }
		        } else {
		        	$retorno = array("error" => utf8_encode(traduz("Erro ao remanufaturar OS")));
		        }
		    }
		} else {
			$retorno = array("error" => utf8_encode(traduz("Informe a data de conserto para remanufaturar")));
		}

	    if (isset($retorno["error"])) {
	    	pg_query($con, "ROLLBACK");
	    } else {
	    	pg_query($con, "COMMIT");
	    	$retorno = array("success" => true);
	    }
	}

    exit(json_encode($retorno));
}

if ($_POST["ajax_grava_codigo_rastreio"] == true) {
	try {
		$os              = $_POST["os"];
		$codigo_rastreio = utf8_decode(trim($_POST["codigo_rastreio"]));
		$tipo_entrega = trim($_POST["tipo_entrega"]);
		$posto_interno = $_POST["posto_interno"];

		if ($login_fabrica == 165 && $tipo_entrega == "correios" && empty($codigo_rastreio)) {
			$codigo_rastreio = "sem_rastreio";
		}

		if (empty($os)) {
			throw new Exception(traduz("Ordem de Serviço não informada"));
		}

		$sql = "SELECT os FROM tbl_os_extra WHERE os = {$os}";
		$res = pg_query($con, $sql);

		if ($login_fabrica == 165 && $posto_interno == true) {
			$objLog = new AuditorLog();
			$objLog->retornaDadosSelect("SELECT os, pac FROM tbl_os_extra WHERE os = {$os}");
		}

		if (pg_num_rows($res) > 0) {
			$sql = "UPDATE tbl_os_extra SET pac = '{$codigo_rastreio}' WHERE os = {$os}";
			$tipo_acao = "update";
		} else {
			$sql = "INSERT INTO tbl_os_extra (os, pac) VALUES ({$os}, '{$codigo_rastreio}')";
			$tipo_acao = "insert";
		}

		$res = pg_query($con, $sql);

		if (strlen(pg_last_error()) > 0) {
			throw new Exception(traduz("Erro ao gravar código de rastreio"));
		}

		if ($login_fabrica == 165 && $posto_interno == true) {
			//auditorlog de codigo de rastreio
			if (!empty($objLog)) {
				$objLog->retornaDadosSelect()->enviarLog($tipo_acao, "tbl_os_extra", $login_fabrica."*".$os);
			}
		}

		$sqlStatus = "SELECT fn_os_status_checkpoint_os({$os}) AS status;";
	    $resStatus = pg_query($con, $sqlStatus);

	    if (strlen(pg_last_error()) > 0) {
			throw new Exception(traduz("Erro ao gravar código de rastreio"));
		}

	    $statusCheckpoint = pg_fetch_result($resStatus, 0, "status");

	    $updateStatus = "UPDATE tbl_os SET status_checkpoint = {$statusCheckpoint} WHERE fabrica = {$login_fabrica} AND os = {$os}";
	    $resStatus = pg_query($con, $updateStatus);

	    if (strlen(pg_last_error()) > 0) {
			throw new Exception(traduz("Erro ao gravar código de rastreio"));
		}

		if ($login_fabrica == 141) {

            $sqlUltimaOs = "SELECT tbl_os.os, 
            					   tbl_os.sua_os,
            					   tbl_hd_chamado_extra.email,
								   tbl_os_extra.pac,
            					   tbl_os.nota_fiscal_saida,
            					   to_char(tbl_os.data_nf_saida, 'dd/mm/yyyy') as data_nf_saida
                            FROM tbl_os
                            JOIN tbl_os_revenda ON tbl_os_revenda.os_revenda = tbl_os.os_numero
                            JOIN tbl_hd_chamado_extra USING(os)
                          	JOIN tbl_os_extra ON tbl_os_extra.os = tbl_os.os
                            WHERE 
                            (
                                SELECT os_numero FROM tbl_os
                                WHERE os = {$os}
                                LIMIT 1
                            ) = tbl_os.os_numero
                            AND tbl_os.nota_fiscal_saida IS NOT NULL
                            AND tbl_os_extra.pac IS NOT NULL
                            ORDER BY tbl_os.os_sequencia DESC
                            LIMIT 1";
            $resUltimaOs = pg_query($con, $sqlUltimaOs);

            $ultimaOsRevenda = pg_fetch_result($resUltimaOs, 0, 'os');

            if (pg_num_rows($resUltimaOs) > 0 && $ultimaOsRevenda == $os) {

            	$sua_os_pesquisa   = pg_fetch_result($resUltimaOs, 0, 'sua_os');
                $email_atendimento = pg_fetch_result($resUltimaOs, 0, 'email');
                $nf_saida          = pg_fetch_result($resUltimaOs, 0, 'nota_fiscal_saida');
                $emissao_nf        = pg_fetch_result($resUltimaOs, 0, 'data_nf_saida');
				$codigo_rastreio_os = pg_fetch_result($resUltimaOs, 0, 'pac');

                $rastreio_transportadora = ($tipo_entrega == 'correios') ? 'Rastreio' : 'Transportadora';
                
                $assunto = "Serviço de Atendimento UNICOBA";
                $mensagem = "Ordem de serviço {$sua_os_pesquisa} de revenda expedida:<br /><br />
                		Nota fiscal: {$nf_saida} <br />
                		Data de Emissão: {$emissao_nf} <br />
                		Transportador: {$tipo_entrega} <br />
                		{$rastreio_transportadora}: {$codigo_rastreio_os}
                ";

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

		$retorno = array("success" => true);
	} catch(Exception $e) {
		$retorno = array("error" => utf8_encode($e->getMessage()));
	}

	exit(json_encode($retorno));
}

$sua_os = trim($_GET['os']);

$fabricas_usam_abertura_callcenter = array(1);

if (in_array($login_fabrica,array(137,141,144,156,162,164,165,173))) {// Verifica se o posto é Interno

    $sql = "
        SELECT  posto
        FROM    tbl_posto_fabrica
        JOIN    tbl_tipo_posto  ON  tbl_tipo_posto.tipo_posto   = tbl_posto_fabrica.tipo_posto
                                AND tbl_tipo_posto.fabrica      = tbl_posto_fabrica.fabrica
                                AND tbl_tipo_posto.posto_interno IS TRUE
        WHERE tbl_posto_fabrica.fabrica = " . $login_fabrica . "
        AND tbl_posto_fabrica.posto = " . $login_posto;
    $res = pg_query($con,$sql);

	if( pg_num_rows($res) > 0) {

		$posto_interno = true;

	}else{

		$posto_interno = false;

	}

}
// Verifica se o posto é Interno ou Revenda
if (in_array($login_fabrica, array(163))) {
	$fazer_paginacao = 'nao';
	$sql = "
        SELECT  posto
        FROM    tbl_posto_fabrica
        JOIN    tbl_tipo_posto  ON  tbl_tipo_posto.tipo_posto   = tbl_posto_fabrica.tipo_posto
                                AND tbl_tipo_posto.fabrica      = tbl_posto_fabrica.fabrica
                                AND (tbl_tipo_posto.posto_interno IS TRUE
                                	OR tbl_tipo_posto.tipo_revenda IS TRUE)
        WHERE tbl_posto_fabrica.fabrica = " . $login_fabrica . "
        AND tbl_posto_fabrica.posto = " . $login_posto;
    $res = pg_query($con,$sql);

    if( pg_num_rows($res) > 0) {

		$posto_interno_revenda = true;

	}else{

		$posto_interno_revenda = false;

	}
}

/*	HD 135436(+Mondial) HD 193563 (+Dynacom)
	Para adicionar ou excluir uma fábrica ou posto, alterar só essa condição aqui,
	na os_consulta_lite, os_press, admin/os_press e na admin/os_fechamento, sempre nesta função
*/
#HD 311411 - Adicionado Fábrica 6 (TecToy)
function usaDataConserto($posto, $fabrica) {
	if ($posto == 4311) {
		return true;
	}

	if (!in_array($fabrica, array(11,158,172)) && $posto == 6359) {
		return true;
	}

	if ((in_array($fabrica, array(1, 2, 3, 5, 6, 7, 11, 14, 15, 20, 30, 35, 43, 45, 40)) || $fabrica > 50) && !in_array($fabrica, array(158))) {
		return true;
	}

	return false;
}

if (isset($_POST['gravarPac']) AND isset($_POST['os'])) {
	$gravarPac = trim($_POST['gravarPac']);
	$os = trim($_POST['os']);
	if (strlen($os)>0){
		$sql = "UPDATE  tbl_os_extra SET
		pac   = '$gravarPac'
		WHERE   tbl_os_extra.os   = $os ";
		$res  = @pg_query ($con,$sql);
		$msg_erro = pg_errormessage($con);
	}
	exit;
}

if (isset($_POST['gravarDataconserto']) AND isset($_POST['os'])) {
	$gravarDataconserto = trim($_POST['gravarDataconserto']);
	$dataTela = trim($_POST['gravarDataconserto']);

	$os = trim($_POST['os']);
	$erro = '';

	if (strlen($os)>0){
		if(strlen($gravarDataconserto ) > 0) {

			if ($login_fabrica <> 1){

				$data = $gravarDataconserto.":00";
				$aux_ano  = substr ($data,6,4);
				$aux_mes  = substr ($data,3,2);
				$aux_dia  = substr ($data,0,2);
				$aux_hora = substr ($data,11,5).":00";
				$gravarDataconserto = "'". $aux_ano."-".$aux_mes."-".$aux_dia." ".$aux_hora."'";

		        if ($login_fabrica == 30) {
		            /* Se a OS estiver em algum HelpDesk do tipo 'Solicitação de troca de produto' o mesmo não será concertado */
		            $sql = "SELECT
		                        os_bloqueada
		                    FROM tbl_os_campo_extra
		                    WHERE fabrica = {$login_fabrica}
		                        AND os = {$os};";

		            $res = pg_query($con, $sql);
		            if (pg_num_rows($res) > 0) {
		                $os_bloqueada = pg_fetch_result($res, 0, "os_bloqueada");
		                $erro = ($os_bloqueada == 't') ? traduz("Esta OS  $os não pode ser consertada pois existe um HelpDesk aberto como 'Solicitação de troca de produto'.") : "";
		            }
		        }

			}else{

				list($di, $mi, $yi) = explode("/", $gravarDataconserto);
				if(!checkdate($mi,$di,$yi))
					$erro = "Data Inválida";


				if(strlen($erro)==0){
					$gravarDataconserto = "$yi-$mi-$di";
				}
				if(strlen($erro)==0){

					$data_atual = date();

					$sql = "SELECT '$gravarDataconserto' > CURRENT_DATE ";
					$res = pg_query($con,$sql);
					if (pg_fetch_result($res,0,0) == 't'){
						$erro = traduz("data.de.conserto.nao.pode.ser.superior.a.data.atual", $con, $cook_idioma);
					}

				}

				if (empty($erro)){
					$gravarDataconserto = "'".$gravarDataconserto."'";
				}

			}

		} else {
			$gravarDataconserto ='null';
		}

		//hd 24714
		if ($gravarDataconserto != 'null'){
			if ($login_fabrica <> 1){

				$data_atual = date("Y-d-m H:i:s");
				$sql = "SELECT $gravarDataconserto > CURRENT_TIMESTAMP ";
				#$res = @pg_query($con,$sql);
				if (strtotime($gravarDataconserto) > strtotime($data_atual)){
					$erro = traduz("data.de.conserto.nao.pode.ser.superior.a.data.atual", $con, $cook_idioma);
				}

				if (in_array($login_fabrica, array(151,169,170))) {
		            if(in_array($login_fabrica, array(169,170))){
		                $join_at = " JOIN tbl_tipo_atendimento ON tbl_os.tipo_atendimento = tbl_tipo_atendimento.tipo_atendimento
		                             AND tbl_tipo_atendimento.fabrica = {$login_fabrica} ";

		                $cond_at = " AND tbl_tipo_atendimento.fora_garantia IS NOT TRUE
		                             AND tbl_tipo_atendimento.km_google IS NOT TRUE ";
		            }

		            $sqlConRev = "
		                SELECT  tbl_os.consumidor_revenda
		                FROM tbl_os
		                $join_at
		                WHERE tbl_os.fabrica = {$login_fabrica}
		                AND tbl_os.os = {$os}
		                $cond_at
		            ";
		            $resConRev = pg_query($con, $sqlConRev);

		            if(pg_num_rows($resConRev) > 0){
		                $os_consumidor_revenda = strtoupper(pg_fetch_result($resConRev, 0, "consumidor_revenda"));
		            }
		        }

				if (((in_array($login_fabrica,array(101,160)) || (in_array($login_fabrica, array(169,170)) && $os_consumidor_revenda == "C")) || (in_array($login_fabrica,array(80)) and !in_array($login_posto, array(40222, 368942)))  )   && empty($erro) /*and $_SERVER['HTTP_HOST'] != 'devel.telecontrol.com.br'*/ ) {

					$sms = new SMS();

			        $sql_celular = "SELECT consumidor_celular, sua_os, referencia, descricao, nome, os_troca
			                        FROM tbl_os
			                        JOIN tbl_produto USING(produto)
			                        JOIN tbl_posto USING(posto)
			                        LEFT JOIN tbl_os_troca USING(os)
			                        WHERE os = $os";
					$res_celular = pg_query($con, $sql_celular);
			        $envia_sms = false;

			        if (pg_num_rows($res_celular) > 0) {
						$consumidor_celular   = pg_fetch_result($res_celular, 0, 'consumidor_celular');
						$sms_os               = pg_fetch_result($res_celular, 0, 'sua_os');
						$sms_produto          = pg_fetch_result($res_celular, 0, 'referencia') . ' - ' . pg_fetch_result($res_celular, 0, 'descricao');
						$sms_produto_descricao= pg_fetch_result($res_celular, 0, 'descricao');
						$sms_posto            = pg_fetch_result($res_celular, 0, 'nome');
						$sms_os_troca         = pg_fetch_result($res_celular, 0, 'os_troca');

			            if (!empty($consumidor_celular)) {
			                $envia_sms = true;
			            }

                        $sqlEnviouSms = "
                            SELECT  JSON_FIELD('enviou_sms',campos_adicionais) AS enviou_sms
                            FROM    tbl_os_campo_extra
                            WHERE os = $os
                        ";
                        $resEnviouSms = pg_query($con, $sqlEnviouSms);

                        $enviouSms = pg_fetch_result($resEnviouSms,0,enviou_sms);

                        if ($enviouSms  == 't') {
                            $envia_sms = false;
                        }

                        if (in_array($login_fabrica, array(169,170)) AND strlen($sms_os_troca) > 0){
		                    $envia_sms = false;
		                }

			            if (true === $envia_sms) {
							$fabnome = $sms->nome_fabrica;

							if($login_fabrica == 101){

								$sms_msg = traduz("Conserto de Produto DeLonghi-Kenwood - OS %. Informamos que seu produto % que esta no Posto autorizado %, já esta consertado. Por favor solicitamos comparecer ao Posto para retirada. Atenciosamente, DeLonghi Kenwood.", null, null, [$sms_os,$sms_produto,$sms_posto]);

							}else if ($login_fabrica == 151) {
								$sms_msg = traduz("MONDIAL - OS %. Informamos que (o/a) % que está no posto autorizado está consertado.", null, null, [$sms_os,$sms_produto]);

							}else if (in_array($login_fabrica, array(169,170))){
								$sms_msg = traduz("Olá! Midea Carrier informa: A O.S % com o posto %, já está consertado. Entre em contato em contato com a rede Blue Service.", null, null, [$sms_os,$sms_posto]);
							}else if ((in_array($login_fabrica, array(160)) or $replica_einhell)){
								$primeira_descricao = explode(" ",substr($sms_produto_descricao, 0, 14));

								$sms_msg = traduz("OS % CONCLUIDA: Seu produto % esta PRONTO.Aguardamos você na autorizada %, para retirada do produto", null, null, [$sms_os,$primeira_descricao[0],$sms_posto]);
							}else{

								$sms_msg = traduz("Produto % - OS %. Informamos que seu produto % que está em nosso posto autorizado %, já está consertado. Solicitamos sua presença para retirada com breviedade.", null, null, [$fabnome,$sms_os,$sms_produto,$sms_posto]);

							}

							// echo $sms_msg;exit;
							// echo $sms->enviarMensagem($consumidor_celular, $sms_os, '', $sms_msg);exit;
							if ($sms->enviarMensagem($consumidor_celular, $os, '', $sms_msg)) {
								$ins_campos_adicionais = "INSERT INTO tbl_os_campo_extra (os, fabrica, campos_adicionais) VALUES ($os, $login_fabrica, '{\"enviou_sms\": \"t\"}') on conflict(os) do  update set campos_adicionais = jsonb_set(regexp_replace(tbl_os_campo_extra.campos_adicionais,'(\w)\\\\u','\1\\\\\\\\u','g')::jsonb,'{enviou_sms}','\"t\"'::jsonb);";
								$qry_campos_adicionais = pg_query($con, $ins_campos_adicionais);
							}
			            }
			        }
		   		}
			}
		}

		//VALIDAÇÃO DE DATA
		if ( $gravarDataconserto != 'null' ) {

			if ($login_fabrica <> 1){

				$sql = "SELECT $gravarDataconserto::timestamp";
				$res = pg_query($con,$sql);

				if (pg_num_rows($res)==0){
					echo traduz("Informe uma data correta para o campo 'Data de conserto'");
					exit;
				}

			}

		}
		//hd 24714
		if ($gravarDataconserto != 'null'){
			$sql = "SELECT $gravarDataconserto < tbl_os.data_abertura FROM tbl_os where os=$os";
			$res = pg_query($con,$sql);
			if (pg_fetch_result($res,0,0) == 't'){
				$erro = traduz("data.de.conserto.nao.pode.ser.anterior.a.data.de.abertura", $con, $cook_idioma);
			}

			if($login_fabrica == 7) {
				$sql = " SELECT $gravarDataconserto < hora_chegada_cliente from tbl_os_visita where os=$os order by hora_chegada_cliente asc limit 1;";
				$res = @pg_query($con,$sql);
				if (pg_fetch_result($res,0,0) == 't'){
					$erro = traduz(" A Data de Conserto não pode ser anterior a data de visita");
				}
			}
		}

		#HD 161176
		if(in_array($login_fabrica, array(11,172))){
			$sqlD = "SELECT tbl_os.os
			FROM tbl_os
			WHERE tbl_os.os    = $os
			AND tbl_os.fabrica IN (11,172)
			AND tbl_os.defeito_constatado IS NOT NULL
			AND tbl_os.solucao_os         IS NOT NULL";
			$resD = @pg_query($con,$sqlD);
			$msg_erro = pg_errormessage($con);
			if(pg_num_rows($resD)==0){
				$erro = traduz("por.favor.verifique.os.dados.digitados.defeito.constatado.e.solucao.na.tela.de.lancamento.de.itens",$con,$cook_idioma);
			}
		}

		if (strlen($erro) == 0 && in_array($login_fabrica, array(141,144)) && $posto_interno) {
			$select_campo_extra = "SELECT campos_adicionais FROM tbl_os_campo_extra WHERE fabrica = {$login_fabrica} AND os = {$os}";
            $res_campo_extra    = pg_query($con, $select_campo_extra);

            $campo_extra = json_decode(pg_fetch_result($res_campo_extra, 0, 'campos_adicionais'),true);

            if(is_array($campo_extra)){

            	if(array_key_exists("os_remanufatura", $campo_extra)){

            		if($campo_extra["os_remanufatura"] == "t"){

            			$sqlClassificacao = "SELECT classificacao_os
            									FROM tbl_classificacao_os
            									WHERE fabrica = $login_fabrica
            									AND lower(fn_retira_especiais(descricao)) = lower('nao remanufaturada')";
            			$resClassificacao = pg_query($con,$sqlClassificacao);

            			if(pg_num_rows($resClassificacao) > 0){
	            			$classificacao_os = pg_fetch_result($resClassificacao, 0, 'classificacao_os');

	            			$sqlOSExtra = "UPDATE tbl_os_extra SET classificacao_os = $classificacao_os WHERE os = $os";
	            			$resOSExtra = pg_query($con,$sqlOSExtra);
	            		}

            		}

            	}

            }

		}

		if(in_array($login_fabrica, [177])){		
			$sql_constatado = "SELECT defeito_constatado FROM tbl_os_produto where os = {$os}";
		        $res_constatado = pg_query($con, $sql_constatado);
	        	$res_constatado = pg_fetch_array($res_constatado);

		        if ($res_constatado['defeito_constatado'] == ""){
		        	$erro =  traduz("os.sem.defeito.constatado.nao.pode.ser.consertada");
	        	}
		}

		if (in_array($login_fabrica, [169, 170])) {
			if (validaAgendamentoData($gravarDataconserto, $os)) {
				$erro = traduz("OS com agendamento pendente ou data de conserto inferior a data do agendamento !");	
			}
		}

		if (strlen($erro) == 0) {

			if ($login_fabrica == 165 && $posto_interno == true) {
				$objLog = new AuditorLog();
				$objLog->retornaDadosSelect("SELECT os, data_conserto FROM tbl_os WHERE os = {$os} AND fabrica = $login_fabrica AND posto = $login_posto");
			}

			$cond_pesquisa_fabrica = (in_array($login_fabrica, array(11,172))) ? " fabrica IN (11,172) " : " fabrica = {$login_fabrica} ";

			$sql = "
				UPDATE tbl_os
				SET data_conserto = {$gravarDataconserto}
				WHERE os = {$os}
				AND {$cond_pesquisa_fabrica}
				AND posto = {$login_posto};
			";
			$res = pg_query($con,$sql);
		} else {
			echo $erro;
		}

		if (in_array($login_fabrica, [165,203]) && strlen($gravarDataconserto ) > 0 && strlen($erro) == 0) {

			//auditorlog de data de conserto
			if (!empty($objLog) && $login_fabrica != 203) {
				$objLog->retornaDadosSelect()->enviarLog("update", "tbl_os", $login_fabrica."*".$os);
			}

			$newStatus = "(SELECT fn_os_status_checkpoint_os({$os}))";

			if (in_array($login_fabrica, [203])) {
				$sqlVer = "SELECT * FROM tbl_os_troca WHERE os = {$os} AND fabric = {$login_fabrica}";
				$resVer = pg_query($con,$sqlVer);

				if (pg_num_rows($resVer) > 0) {
					$newStatus = "4";
				}
			}

			$sql = "
				UPDATE tbl_os
				SET status_checkpoint = {$newStatus}
				WHERE os = {$os}
				AND fabrica = {$login_fabrica}
				AND posto = {$login_posto};
			";

			$res = pg_query($con,$sql);

		}

		if($login_fabrica == 30 && strlen($dataTela) > 0){
            $sqlData = "
                INSERT INTO tbl_os_interacao (
                	programa,
                    os,
                    data,
                    comentario,
                    interno,
                    posto
                ) VALUES (
                	'$programa_insert',
                    $os,
                    CURRENT_TIMESTAMP,
                    'A OS foi consertada na data $dataTela',
                    TRUE,
                    $login_posto
                )
            ";
            $resData = pg_query($con,$sqlData);
            $erro = pg_last_error($con);
		}

		if(strlen($msg_erro) == 0 AND in_array($login_fabrica, array(141,144))){
            $calculaMo = new \Posvenda\ExcecaoMobra($os,$login_fabrica);
		}


		if(strlen($erro) ==0){
            if (in_array($login_fabrica, [104, 123])) {
                $sql_sms = "SELECT campos_adicionais FROM tbl_os_campo_extra WHERE os = $os";
                $qry_sms = pg_query($con, $sql_sms);
                $campos_adicionais = array();
                $insert_campo_extra = false;

                if (pg_num_rows($qry_sms) == 0) {
                    $insert_campo_extra = true;
                } else {
                    $campos_adicionais = json_decode(pg_fetch_result($qry_sms, 0, 'campos_adicionais'), true);
                }

                if (!array_key_exists("enviou_msg_consertado", $campos_adicionais) or $campos_adicionais["enviou_msg_consertado"] <> "t") {
                    $helper = new \Posvenda\Helpers\Os();

                    $sql_contatos_consumidor = "
                        SELECT consumidor_email,
                            consumidor_celular,
                            referencia,
                            descricao,
                            tbl_posto.nome
                        FROM tbl_os
                        JOIN tbl_produto USING(produto)
                        JOIN tbl_posto USING(posto)
                        WHERE os = $os";
                    $qry_contatos_consumidor = pg_query($con, $sql_contatos_consumidor);

                    $consumidor_email = pg_fetch_result($qry_contatos_consumidor, 0, 'consumidor_email');
                    $consumidor_celular = pg_fetch_result($qry_contatos_consumidor, 0, 'consumidor_celular');
                    $produto_os = pg_fetch_result($qry_contatos_consumidor, 0, 'referencia') . ' - ' . pg_fetch_result($qry_contatos_consumidor, 0, 'descricao');
                    $xref = pg_fetch_result($qry_contatos_consumidor, 0, 'referencia');
                    $posto_os = pg_fetch_result($qry_contatos_consumidor, 0, 'nome');

                    if ($login_fabrica == 104) {
	                    $msg_conserto_os = traduz("Produto Vonder - OS %. Informamos que seu produto % que está em nosso posto % já está consertado. Solicitamos sua presença para retirada com brevidade.",null,null,[$os,$produto_os,$posto_os]);

	                    if (!empty($consumidor_email)) {
	                        $helper->comunicaConsumidor($consumidor_email, $msg_conserto_os);
	                    }
                    } else {
						$msg_conserto_os = traduz("O reparo do seu equipamento $xref foi concluído e encontra-se disponível para ser retirado. OS $os. Obrigada por escolher a nossa marca.");                    	
                    }

                    if (!empty($consumidor_celular)) {
                        $helper->comunicaConsumidor($consumidor_celular, $msg_conserto_os , $login_fabrica, $os);
                    }

                    $campos_adicionais["enviou_msg_consertado"] = "t";
                    $json_campos_adicionais = json_encode($campos_adicionais);

                    if (true === $insert_campo_extra) {
                        $sql_msg_consertado = "
                            INSERT INTO tbl_os_campo_extra (os, fabrica, campos_adicionais)
                                VALUES ({$os}, {$login_fabrica}, '{$json_campos_adicionais}')";
                    } else {
                        $sql_msg_consertado = "
                            UPDATE tbl_os_campo_extra SET
                                campos_adicionais = '{$json_campos_adicionais}'
                            WHERE os = $os";
                    }

                    $qry_msg_consertado = pg_query($con, $sql_msg_consertado);
                }
            }

            if ($login_fabrica == 141) {

	            $sqlUltimaOs = "SELECT tbl_os.os,tbl_hd_chamado_extra.email,tbl_os.os_numero
	                            FROM tbl_os
	                            JOIN tbl_os_revenda ON tbl_os_revenda.os_revenda = tbl_os.os_numero
	                            JOIN tbl_hd_chamado_extra USING(os)
	                            WHERE 
	                            (
	                                SELECT os_numero FROM tbl_os
	                                WHERE os = {$os}
	                                LIMIT 1
	                            ) = tbl_os.os_numero
	                            AND tbl_os.data_conserto IS NULL
	                            ORDER BY tbl_os.os_sequencia DESC
	                            LIMIT 1";
	            $resUltimaOs = pg_query($con, $sqlUltimaOs);

	            $ultimaOsRevenda = pg_fetch_result($resUltimaOs, 0, 'os');

	            if (pg_num_rows($resUltimaOs) > 0 && $ultimaOsRevenda == $os) {

	            	$sua_os_pesquisa   = pg_fetch_result($resUltimaOs, 0, 'os_numero');
	                $email_atendimento = pg_fetch_result($resUltimaOs, 0, 'email');
	                
	                $assunto = "Serviço de Atendimento UNICOBA";
	                $mensagem = "Ordem de serviço {$sua_os_pesquisa} de revenda consertada e aguardando expedição";

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

            if (in_array($login_fabrica, array(14,43,66,117))) {
				$novo_status_os = traduz("CONSERTADO");
				include('os_email_consumidor.php');
			}

			//HD 845144
			if(in_array($login_fabrica, array(3))){
				$sql = "SELECT
				tbl_os.data_conserto::date - tbl_os.data_abertura AS dias,
				tbl_os.data_conserto 		,
				tbl_os.consumidor_email 	,
				tbl_os.sua_os 				,
				tbl_os.consumidor_nome		,
				tbl_posto.nome AS nome_posto,
				tbl_posto.endereco			,
				tbl_posto.numero 			,
				tbl_posto.fone                          ,
				tbl_marca.marca as id_marca             ,
				tbl_marca.nome as marca
				FROM tbl_os
				JOIN tbl_produto USING(produto)
				JOIN tbl_marca  ON tbl_produto.marca = tbl_marca.marca
				JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_os.posto AND tbl_posto_fabrica.fabrica = {$login_fabrica}
				JOIN tbl_posto ON tbl_posto.posto = tbl_posto_fabrica.posto
				WHERE tbl_os.os = $os";
				$res = pg_query($con, $sql);

				if(pg_num_rows($res) > 0){
					$data_conserto 	= pg_fetch_result($res, 0, 'data_conserto');
					$nome 			= pg_fetch_result($res, 0, 'consumidor_nome');
					$nome_posto 	= pg_fetch_result($res, 0, 'nome_posto');
					$endereco 		= pg_fetch_result($res, 0, 'endereco');
					$numero 		= pg_fetch_result($res, 0, 'numero');
					$fone 			= pg_fetch_result($res, 0, 'fone');
					$dias 			= pg_fetch_result($res, 0, 'dias');
					$email 			= trim(pg_fetch_result($res, 0, 'consumidor_email'));
					$id_marca       = pg_fetch_result($res, 0, 'id_marca');
					$marca 			= trim(pg_fetch_result($res, 0, 'marca'));
					$sua_os 		= strlen(pg_fetch_result($res, 0, 'sua_os')) == 0 ? $os : pg_fetch_result($res, 0, 'sua_os');

					if(filter_var($email, FILTER_VALIDATE_EMAIL) AND $dias <= 29 AND strlen($data_conserto) > 4){
						include_once 'class/email/mailer/class.phpmailer.php';
						$mailer = new PHPMailer();

						$mensagem = "Prezado(a) {$nome},<br/>Seu atendimento nº {$sua_os} foi concluído e o produto se encontra disponível para retirada o mais breve possível.<br/><br/>Posto: {$nome_posto}<br/>Endereço: {$endereco}, {$numero}<br/>Tel: {$fone}<br/><br/>Este e-mail é gerado automaticamente.<br/><br/>SAC Britânia<br/>0800 4176 44<br/>sac@britania.com.br<br/><br/>SAC Philco<br/>0800 6458 300<br/>sac@philco.com.br<br/><br/>De segunda a sexta das 08:00 às 18:00";

						$mailer->IsSMTP();
						$mailer->IsHTML();
						$mailer->AddReplyTo("sac@britania.com.br","SAC Britânia - Telecontrol Pós Venda");
						$mailer->AddAddress($email);
                        if($id_marca == 110) {
                        	$mailer->AddAddress("produtoconsertado@philco.com.br");
                        }else {
                            $mailer->AddAddress("produtoconsertado@britania.com.br");
                        }
						$mailer->Subject = "Atendimento $marca nº {$sua_os} - Concluido";
						$mailer->Body = $mensagem;
						$mailer->Send();
						$headers  = "MIME-Version: 1.0 \r\n";
						$headers .= "Content-type: text/html; charset=iso-8859-1 \r\n";
						$headers .= "From: helpdesk@telecontrol.com.br \r\n";

						$assunto =  "Atendimento $marca nº {$sua_os} - Concluido";

			    #mail($email, utf8_encode($assunto), utf8_encode($mensagem), $headers);

					}
				}
			}
		}

		//if(strlen($erro) ==0 AND strlen($msg_erro) == 0 AND in_array($login_fabrica, array(43,117))){
		if(strlen($erro) ==0 AND strlen($msg_erro) == 0 AND in_array($login_fabrica, array(43))){
			$observacao=$_POST['observacao_'.$i];
			$res=pg_query($con,$sql);
			$sqlm="SELECT tbl_os.sua_os          ,
			tbl_os.consumidor_email,
			tbl_os.serie           ,
			tbl_posto.nome         ,
			tbl_produto.descricao  ,
			tbl_produto.referencia  ,
			to_char(tbl_os.data_fechamento,'DD/MM/YYYY') as data_fechamento
			from tbl_os
			join tbl_produto using(produto)
			join tbl_posto on tbl_os.posto = tbl_posto.posto
			where tbl_os.os=$os
			AND tbl_os.fabrica = $login_fabrica";
			$resm=pg_query($con,$sqlm);
			$msg_erro .= pg_errormessage($con) ;
			$sua_osm           = trim(pg_fetch_result($resm,0,sua_os));
			$consumidor_emailm = trim(pg_fetch_result($resm,0,consumidor_email));
			$seriem            = trim(pg_fetch_result($resm,0,serie));
			$data_fechamentom  = trim(pg_fetch_result($resm,0,data_fechamento));
			$nomem             = trim(pg_fetch_result($resm,0,nome));
			$descricaom        = trim(pg_fetch_result($resm,0,descricao));
			$referenciam        = trim(pg_fetch_result($resm,0,referencia));

			$nome         = "TELECONTROL";
			$email_from   = "helpdesk@telecontrol.com.br";
			$assunto      = "ORDEM DE SERVIÇO FECHADA";
			$destinatario = $consumidor_emailm;
			$boundary = "XYZ-" . date("dmYis") . "-ZYX";

			if(strlen($consumidor_emailm) > 0 AND $login_fabrica == 43){

				$mensagem = traduz("A ORDEM DE SERVIÇO % REFERENTE AO PRODUTO % COM NÚMERO DE SÉRIE % FOI FECHADA PELO POSTO % NO DIA %.",null,null, [$sua_osm,$descricaom,$seriem,$nomem,$data_fechamentom]);
				$mensagem .= "<br>Observação do Posto: $observacao";

			}

			if(strlen($consumidor_emailm) > 0 AND $login_fabrica == 117){
				$mensagem = "
			    Foi finalizada a Ordem de Serviço nº {$sua_osm}
			    <br /> <br />
			    Data: ".date("d/m/Y")." Produto: {$referenciam} - {$descricaom}
			    <br />
			    Segue abaixo o link para consultar o andamento de sua O.S..
			    <br />
			    http://posvenda.telecontrol.com.br/assist/externos/institucional/statusos.html
			    <br /> <br />
			    Serviço Elgin de Atendimento
			  ";
			}

			$body_top = "--Message-Boundary\n";
			$body_top .= "Content-type: text/html; charset=iso-8859-1\n";
			$body_top .= "Content-transfer-encoding: 7BIT\n";
			$body_top .= "Content-description: Mail message body\n\n";

			@mail($destinatario, utf8_encode($assunto), utf8_encode($mensagem), "From: ".$email_from." \n $body_top ");
		}

		if (in_array($login_fabrica, array(169,170,174))){
			$sql = "
				SELECT tbl_hd_chamado_postagem.admin
				FROM tbl_os
				JOIN tbl_hd_chamado_postagem ON tbl_hd_chamado_postagem.hd_chamado = tbl_os.hd_chamado AND tbl_hd_chamado_postagem.fabrica = {$login_fabrica}
				WHERE tbl_os.os = {$os}
				AND tbl_os.fabrica = {$login_fabrica}
				AND tbl_hd_chamado_postagem.admin IS NOT NULL
				ORDER BY tbl_hd_chamado_postagem.hd_chamado_postagem DESC LIMIT 1;
			";
			$res = pg_query($con, $sql);

			if (pg_num_rows($res) > 0){
				echo "solicitar_postagem";
			}
		}

		if($login_fabrica == 3){
			$sqlBuscaOs25Dias = "SELECT os, consumidor_celular, sua_os, tbl_marca.nome as nome_marca
								FROM tbl_os
								INNER JOIN tbl_produto ON tbl_produto.produto = tbl_os.produto AND tbl_produto.fabrica_i = $login_fabrica
			                        INNER JOIN tbl_marca ON tbl_marca.marca = tbl_produto.marca AND tbl_marca.fabrica = $login_fabrica
								WHERE tbl_os.fabrica = $login_fabrica
								AND (CURRENT_DATE - data_abertura) > 25
								AND (CURRENT_DATE - data_abertura) < 31
								AND tbl_os.os = $os
								AND tbl_os.consumidor_celular notnull
								AND data_conserto is not null ";
			$resBuscaOs25Dias = pg_query($con, $sqlBuscaOs25Dias);

			if(pg_num_rows($resBuscaOs25Dias)>0){

				$consumidor_celular = pg_fetch_result($resBuscaOs25Dias, 0, consumidor_celular);
				$sua_os 			= pg_fetch_result($resBuscaOs25Dias, 0, sua_os);
				$nome_marca 		= pg_fetch_result($resBuscaOs25Dias, 0, nome_marca);

				$msg_sms = traduz("Consumidor, seu produto OS % foi consertado e encontra-se disponível para retirada no posto autorizado ou utilização.Atendimento %",null,null,[$sua_os,$nome_marca]);

				$sms = new SMS();

				$enviar = $sms->enviarMensagem($consumidor_celular,
					$sua_os,
					' ',
					$msg_sms);

				if($enviar == false){
					$sms->gravarSMSPendente($os);
				}
			}
		}


	}

	exit;
}

if (filter_input(INPUT_POST,'ajax',FILTER_VALIDATE_BOOLEAN)) {
    $ajaxType = filter_input(INPUT_POST,'ajaxType');

    if ($ajaxType == "status_nf_saida") {
        $nf = filter_input(INPUT_POST,'nf');
        $dataNf = filter_input(INPUT_POST,'data_nf');
        $osAjax = filter_input(INPUT_POST,'os');


        list($dnf, $mnf, $ynf) = explode("/", $dataNf);

        if (!checkdate($mnf,$dnf,$ynf)) {
            echo "Erro";
            exit;
        }

        $xdata_nf_saida = $ynf."-".$mnf."-".$dnf;

        $res = pg_query($con,"BEGIN TRANSACTION");

        if ($login_fabrica == 165 && $posto_interno == true) {

			$objLog = new AuditorLog();
			$objLog->retornaDadosSelect("SELECT os, nota_fiscal_saida, data_nf_saida FROM tbl_os WHERE os = {$osAjax} AND fabrica = $login_fabrica");
        	$status = 29;

        } else {
        	$status = 12;
        }

        $sqlUpNf = "
            UPDATE  tbl_os
            SET     nota_fiscal_saida = '$nf',
                    data_nf_saida     = '$xdata_nf_saida',
                    status_checkpoint = {$status}
            WHERE   os      = $osAjax
            AND     fabrica = $login_fabrica
        ";
        //echo $sqlUpNf;exit;
        $resUpNf = pg_query($con,$sqlUpNf);

        if (pg_last_error($con)) {
            echo "Erro ao gravar".pg_last_error($con);
            $res = pg_query($con,"ROLLBACK TRANSACTION");
            exit;
        }

        $res = pg_query($con,"COMMIT TRANSACTION");

        if ($login_fabrica == 165 && $posto_interno == true) {
			//auditorlog de nf, data nf
			if (!empty($objLog)) {
				$objLog->retornaDadosSelect()->enviarLog("update", "tbl_os", $login_fabrica."*".$os);
			}
		}

        echo json_encode(array("ok" => true,"msg" => traduz("Nota Fiscal de Saida gravada.")));
        exit;
    }
}

#------------ Fecha Ordem de Servico ------------#
$btn_acao = strtolower($_POST['btn_acao']);

if ($btn_acao == 'continuar') {
	$data_fechamento     = $_POST['data_fechamento'];
	$qtde_os             = $_POST['qtde_os'];
	$data_fechamento_sms = $data_fechamento;
	$usa_estoque_posto   = $_POST['usa_estoque_posto'];

	//HD-2938154 - TRAVA CASO NAO TENHA "defeito reclamado, defeito constatado ou solução"
	if ($login_fabrica == 72 || $login_fabrica == 3) {
		for ($i = 0 ; $i < $qtde_os ; $i++) {
			$os    = trim($_POST["os"][$i]['os_' . $i]);
			$ativo = trim($_POST["os"][$i]['ativo_'. $i]);

			if(empty($ativo)) continue;

			$sqlValida = "SELECT
	            tbl_os.defeito_reclamado_descricao,
	            tbl_os.defeito_reclamado,
	            tbl_os.defeito_constatado,
	            tbl_os.solucao_os,
	            tbl_os.sua_os
	            FROM tbl_os
			WHERE tbl_os.fabrica ={$login_fabrica}
			and     tbl_os.troca_garantia       IS NOT TRUE
			and     tbl_os.ressarcimento        IS NOT TRUE
	        AND tbl_os.os ={$os}";
	        //echo $sqlValida;die;
			$resValida = pg_query($con, $sqlValida);

			if (pg_num_rows($resValida) > 0) {
				$rowValida = pg_fetch_array($resValida);
				$sua_os = $rowValida['sua_os'];
				if (!$rowValida['defeito_reclamado'] && empty($rowValida['defeito_reclamado_descricao'])) {
					$erroValidado = 'Defeito Reclamado';
					$msg_erro     .= traduz("A OS % não pode ser fechada, pois % é obrigatório <br />",null,null,[$sua_os,$erroValidado]);
				}
				if (!$rowValida['defeito_constatado']) {
					$erroValidado = 'Defeito Constatado';
					$msg_erro     .= traduz("A OS % não pode ser fechada, pois % é obrigatório <br />",null,null,[$sua_os,$erroValidado]);
				}
				if (!$rowValida['solucao_os']) {
					$erroValidado = 'Solução da OS';
					$msg_erro     .= traduz("A OS % não pode ser fechada, pois % é obrigatório <br />",null,null,[$sua_os,$erroValidado]);
				}
			}
		}
	}

	if (in_array($login_fabrica, [169,170])) {
		
		for ($i = 0 ; $i < $qtde_os ; $i++) {
			$os    = trim($_POST["os"][$i]['os_' . $i]);
			$ativo = trim($_POST["os"][$i]['ativo_'. $i]);

			if (empty($ativo)) {
				continue;
			}

	        $dtF = date_create_from_format('d/m/Y', $data_fechamento);
	        $dtF = date_format($dtF, 'Y-m-d H:i:s');
	        
			if (validaAgendamentoData($dtF, $os)) {
				$msg_erro .= traduz("A OS $os não pode ser fechada, pois está pendente de agendamento ou a data de fechamento é inferior ao agendamento <br />");
			}
		}
	}

	if ($login_fabrica == 158) {
		for ($i = 0 ; $i < $qtde_os ; $i++) {
			$os    = trim($_POST["os"][$i]['os_' . $i]);
			$ativo = trim($_POST["os"][$i]['ativo_'. $i]);

			if(empty($ativo)) continue;

			$sqlValida = "SELECT
		            tbl_os.defeito_constatado AS df_os,
		            tbl_os_defeito_reclamado_constatado.defeito_constatado AS df_os_os,
		            tbl_os.sua_os
	            FROM tbl_os
	            LEFT JOIN tbl_os_defeito_reclamado_constatado USING(os)
				WHERE tbl_os.fabrica ={$login_fabrica}
	        	AND tbl_os.os = {$os}
				AND (tbl_os.defeito_constatado NOTNULL OR tbl_os_defeito_reclamado_constatado.defeito_constatado NOTNULL)
				LIMIT 1";
	        	
				$resValida = pg_query($con, $sqlValida);

			if (pg_num_rows($resValida) > 0) {
				$rowValida = pg_fetch_array($resValida);
				$sua_os = $rowValida['sua_os'];

				if (!$rowValida['df_os'] && !$rowValida['df_os_os']) {
					$erroValidado = 'Defeito Constatado';
					$msg_erro     .= traduz("A OS % não pode ser fechada, pois % é obrigatório <br />",null,null,[$sua_os,$erroValidado]);
				}
			}
		}
	}

	if ($login_fabrica == 153 or $login_fabrica == 160 or $replica_einhell) {

		if($login_fabrica == 153){
			$data_3dias = strtotime("-3 day");
			$dias_ = "3";
		}elseif($login_fabrica == 160 or $replica_einhell){
			$data_3dias = strtotime("-7 day");
			$dias_ = "7";
		}

        list($df, $mf, $yf) = explode("/", $data_fechamento);
        $data = "$yf-$mf-$df";

		$data_fechamento_ = strtotime($data);

		if($data_fechamento_ < $data_3dias){
			$msg_erro = traduz("data.de.fechamento.nao.pode.menor.que.$dias_.dias", $con, $cook_idioma);
		}
	}

	if ($login_fabrica == 164) {
		for ($t = 0 ; $t < $qtde_os ; $t++) {
			$at       = false;
			$os       = trim($_POST["os"][$t]['os_' . $t]);
			$anexados = [];

			$sqlDataOs = "SELECT distinct os.os, case when os_troca notnull then 't' else 'f' end as troca_produto , p.status_pedido
                        FROM tbl_os AS os
                        JOIN tbl_os_produto     AS op ON op.os           = os.os 
                        JOIN tbl_os_item        AS oi ON oi.os_produto   = op.os_produto
                        JOIN tbl_pedido         AS p  ON oi.pedido       = p.pedido 
						LEFT JOIN tbl_os_troca ON os.os = tbl_os_troca.os
						WHERE os.os    = {$os}
                        AND os.fabrica = $login_fabrica
                        AND data_abertura >= '2019-11-01'";
	        $resDataOs = pg_query($con,$sqlDataOs);

	        if (pg_num_rows($resDataOs) > 0) {
	            $troca_produto      = (pg_fetch_result($resDataOs, 0, 'troca_produto') == true || pg_fetch_result($resDataOs, 0, 'troca_produto') == 't') ? true : false; 
				$status_pedido = pg_fetch_result($resDataOs, 0, 'status_pedido'); 

				$sqlDataEntrada = "SELECT sr.descricao, op.os_produto, oi.servico_realizado, oi.parametros_adicionais::jsonb->>'data_recebimento' as data_recebimento
		                            FROM tbl_os_produto AS op 
		                                INNER JOIN tbl_os_item AS oi ON oi.os_produto = op.os_produto AND oi.fabrica_i = $login_fabrica 
		                                INNER JOIN tbl_servico_realizado AS sr ON sr.servico_realizado = oi.servico_realizado AND sr.fabrica = $login_fabrica
		                            WHERE op.os = {$os}";
		        $resDataEntrada   = pg_query($con, $sqlDataEntrada);
		        
		        if (pg_num_rows($resDataEntrada) > 0) {
		            $data_recebimento          = pg_fetch_result($resDataEntrada, 0, data_recebimento);
		            $os_servico_realizado      = pg_fetch_result($resDataEntrada, 0, servico_realizado);
		            $os_servico_realizado_desc = strtolower(pg_fetch_result($resDataEntrada, 0, descricao));
		        } 

		        $isCancelado = ($status_pedido == 14) ? true : false;

				$sqlTdocs_anexo = "SELECT obs AS tipo_anexo FROM tbl_tdocs 
									WHERE contexto = 'os' AND referencia_id = {$os} AND fabrica = $login_fabrica AND situacao = 'ativo'";
				$resTdocs_anexo = pg_query($con, $sqlTdocs_anexo); 
				
				if (pg_num_rows($resTdocs_anexo) > 0) {
					for ($i_anexo = 0; $i_anexo < pg_num_rows($resTdocs_anexo); $i_anexo++) {
						$obs = json_decode(pg_fetch_result($resTdocs_anexo, $i_anexo, 'tipo_anexo'), true);    
					   
						for ($j_anexo = 0; $j_anexo < count($obs); $j_anexo++) {
							$anexados[] = $obs[$j_anexo]['typeId'];
						}
					}
				}

				if (verifica_tipo_posto("posto_interno", "false")) {
					if (!in_array('comprovante_saida', $anexados) || !in_array('comprovante_entrada', $anexados) || !in_array('evidencia', $anexados)) {
						if (!$data_recebimento && !in_array($os_servico_realizado, [11235,11237]) && !$troca_produto && $isCancelado == false) {
							$msg_erro .= traduz('a.os.nao.pode.ser.finalizada.sem.data.de.conferencia.', $con, $cook_idioma);
						} else {
							if ($isCancelado == false || $isCancelado == true && ($os_servico_realizado == 11237 || $troca_produto)) { 
								$msg_erro .= traduz('para.finalizar.a.os.e.necessario.que.os.seguintes.anexos.sejam.inseridos.:.comprovante.de.entrada.,.evidencia.,.comprovante.de.saida', $con, $cook_idioma);
							}
						}
					} else {
						if (!$data_recebimento && !in_array($os_servico_realizado, [11235,11237]) && !$troca_produto && $isCancelado == false) {
							$msg_erro .= traduz('a.os.nao.pode.ser.finalizada.sem.data.de.conferencia.', $con, $cook_idioma);
						}
					}
				}
			}
		}
	}

	if (in_array($login_fabrica, [123,160])) {
		for ($t = 0 ; $t < $qtde_os ; $t++) {
			$at = false;
			$os    = trim($_POST["os"][$t]['os_' . $t]);
			if (isset($_POST['os'][$t]['ativo_'. $t])) {
				$at = true; 
			}

			if (data_corte_termo($os)) {
				$tem_anexo = tem_anexo($os); 
				if ($at && ($tem_anexo === 'erro' || $tem_anexo === 'imprimir_termo' || $tem_anexo === 'anexa_termo')) {
					
					$msg_erro = traduz("Anexar o termo de retirada da OS %", $con, $cook_idioma, [$os]);
				}
			}
		}
	}

	if($login_fabrica == 123){
		for ($ii = 0 ; $ii < $qtde_os ; $ii++) {
			$ativo = trim($_POST["os"][$ii]['ativo_'. $ii]);
			$os    = trim($_POST["os"][$ii]['os_' . $ii]);

			if($ativo == 't') {
				$sql_auditoria = "SELECT auditoria_os
						FROM tbl_auditoria_os 
						WHERE os = $os
						and auditoria_status = 6 
						AND (bloqueio_pedido IS TRUE OR cancelada IS NOT NULL OR reprovada IS NOT NULL)
						ORDER BY auditoria_os DESC ";
				$res_auditoria = pg_query($con, $sql_auditoria);
				if(pg_num_rows($res_auditoria)>0){
					$msg_erro .= traduz("A OS % não pode ser fechada, pois esta em auditoria. <br />",null,null,[$os]);
				}
			}
		}
	}

	// valida checklist
	if($login_fabrica == 19){
		for ($ii = 0 ; $ii < $qtde_os ; $ii++) {
			$ativo = trim($_POST["os"][$ii]['ativo_'. $ii]);
			$os    = trim($_POST["os"][$ii]['os_' . $ii]);

			if($ativo == 't') {
				$sql = "SELECT tipo_atendimento, sua_os FROM tbl_os where os = $os AND fabrica = $login_fabrica";
				$res = pg_query($con, $sql);
				if (pg_num_rows($res) > 0) {
					$tp_at = pg_fetch_result($res, 0, 'tipo_atendimento');
					$xsua_os = pg_fetch_result($res, 0, 'sua_os');
					if (verifica_checklist_tipo_atendimento($tp_at) && verifica_checklist_lancado($os)) {
						$msg_erro .= traduz("A OS % não pode ser fechada, informe o checklist. ",null,null,[$xsua_os])."<a href='os_item.php?os=$os' target='_blank'>".traduz("Informar Checklist")."</a><br />";
					}
				}
			}
		}
	}

	/* hd-2979097 - fputti
	if($login_fabrica == 80){
		$sms = new SMS();
	}*/

	if (in_array($login_fabrica,array(1))) {
		$msg_erro_2 = "";
		$osPedidoFaturado = "";

		for ($ii = 0 ; $ii < $qtde_os ; $ii++) {
			
			if ($usa_estoque_posto == "sim" && $_POST["os"][$ii]['ativo2_'. $ii] == 't') {
				$ativo = trim($_POST["os"][$ii]['ativo2_'. $ii]);
			} else {
				$ativo = trim($_POST["os"][$ii]['ativo_'. $ii]);
			}

			$os    = trim($_POST["os"][$ii]['os_' . $ii]);

			if($ativo == 't') {
				$sua_os = "";

				// verifica se a OS tem peça lançada mas ainda não gerou pedido.
				$sql = "SELECT
							tbl_os_item.os_item,
							tbl_os.sua_os
						FROM tbl_os
						INNER JOIN tbl_os_produto ON tbl_os_produto.os = tbl_os.os
						INNER JOIN tbl_os_item ON tbl_os_item.os_produto = tbl_os_produto.os_produto
						INNER JOIN tbl_servico_realizado ON tbl_os_item.servico_realizado = tbl_servico_realizado.servico_realizado
						WHERE tbl_os.fabrica = {$login_fabrica}
						AND tbl_servico_realizado.gera_pedido is true
						AND tbl_os_item.pedido IS NULL
						AND tbl_os.os = {$os}";

				$res = pg_query($con,$sql);

				if(pg_num_rows($res) > 0){

					$count = pg_num_rows($res);

					for($j = 0; $j < $count; $j++){
						$os_item = pg_fetch_result($res, $j, "os_item");
						$sua_os = pg_fetch_result($res, $j, "sua_os");
					}

					$sua_os = $login_codigo_posto . $sua_os;
					$msg_erro .= traduz("A OS % não pode ser fechada, pois existem peças pendentes! <br />",null,null,[$sua_os]);
				}else{
					// Se a OS tiver pedido verifica se o pedido não foi faturado
					$sql = "SELECT
								tbl_os_item.os_item,
								tbl_os.sua_os
							FROM tbl_os
							INNER JOIN tbl_os_produto ON tbl_os_produto.os = tbl_os.os
							INNER JOIN tbl_os_item ON tbl_os_item.os_produto = tbl_os_produto.os_produto
							LEFT JOIN tbl_os_item_nf ON tbl_os_item.os_item = tbl_os_item_nf.os_item
							LEFT JOIN tbl_pedido_cancelado USING(pedido,peca)
							WHERE tbl_os.fabrica = {$login_fabrica}
							AND tbl_os_item_nf.os_item isnull
							AND tbl_os_item.pedido notnull
							AND tbl_pedido_cancelado.pedido ISNULL
							AND tbl_os.os = {$os}";
					$res = pg_query($con, $sql);

					if(pg_num_rows($res) > 0){

						$count = pg_num_rows($res);

						for($j = 0; $j < $count; $j++){
							$os_item = pg_fetch_result($res, $j, "os_item");
							$sua_os = pg_fetch_result($res, $j, "sua_os");
						}

						$sua_os = $login_codigo_posto . $sua_os;
						$msg_erro .= traduz("A OS % não pode ser fechada, pois existe um pedido não faturado! <br />",null,null,[$sua_os]);
					}else{
						$osPedidoFaturado = "sim";
					}
				}

				if (empty($_POST["os"][$ii]['data_conserto_'. $ii]) && data_conserto_vazia($os)) {
					$msg_erro_2 .= traduz("Informe a Data de Conserto da OS %! <br />",null,null,[$sua_os]);
				}
			}
		}
	}
	
	// hd-6101045
	if ($login_fabrica == 151) {
		for ($a = 0 ; $a < $qtde_os ; $a++) {
			$os    = trim($_POST["os"][$a]['os_' . $a]);
			$sql = "SELECT      tbl_os_extra.recolhimento,
	                            TO_CHAR(tbl_faturamento.emissao,'DD/MM/YYYY') AS emissao,
	                            tbl_os.sua_os
	                FROM    tbl_os
	                JOIN    tbl_os_produto          ON  tbl_os.os                   = tbl_os_produto.os
	                JOIN    tbl_os_item             ON  tbl_os_produto.os_produto   = tbl_os_item.os_produto
	                JOIN    tbl_os_extra            ON  tbl_os_extra.os             = tbl_os.os
	                JOIN    tbl_peca                ON  tbl_peca.peca               = tbl_os_item.peca
	                                                AND tbl_os_extra.extrato        IS NULL
	                JOIN    tbl_faturamento_item    ON  (
	                                                    tbl_faturamento_item.pedido     = tbl_os_item.pedido
	                                                OR  tbl_faturamento_item.os_item    = tbl_os_item.os_item
	                                                )
	                                                AND tbl_faturamento_item.peca   = tbl_os_item.peca
	                JOIN    tbl_faturamento         ON  tbl_faturamento.faturamento = tbl_faturamento_item.faturamento
	                                                AND tbl_faturamento.fabrica     = $login_fabrica
	                                                
	                 WHERE tbl_os.posto = $login_posto
	                 AND tbl_os.fabrica = $login_fabrica
	                 AND tbl_os.finalizada isnull
	                 AND tbl_os.os = $os";
	        $res_sql = pg_query($con,$sql);
	        $sua_os         = pg_fetch_result($res_sql, 0, 'sua_os');
	        $emissao_f      = pg_fetch_result($res_sql, 0, 'emissao');
	        $recolhimento_f = pg_fetch_result($res_sql, 0, 'recolhimento');
	        if (!empty($emissao_f)) {
	            $sql_data = "SELECT ((cast('$emissao_f' AS DATE) + INTERVAL '30 DAYS') < CURRENT_DATE) as data";
	            $res_data = pg_query($con,$sql_data);
	            $new_emissao = pg_fetch_result($res_data, 0, 'data');
	        }
	        if ($new_emissao == 'f' && $recolhimento == 't') {
				$msg_erro .= traduz("A OS % não pode ser fechada, pois existem peças pendentes! <br />",null,null,[$sua_os]);
	        } elseif ($new_emissao == 't') {
	            $sql_recolhimento = "UPDATE tbl_os_extra SET recolhimento = false, obs_fechamento = 'Faturamento excedeu 30 dias.' WHERE os = $os AND i_fabrica = $login_fabrica";
	            $res_recolhimento = pg_query($con, $sql_recolhimento);
	        }
		}		
	}

	if (strlen($data_fechamento) == 0){
		$msg_erro = traduz("digite.a.data.de.fechamento", $con, $cook_idioma).'<br/>';
	} else if(strlen($msg_erro) == 0 && empty($msg_erro_2)) {
		$xdata_fechamento = fnc_formata_data_pg ($data_fechamento);
		if($xdata_fechamento > "'".date("Y-m-d")."'"){
			$msg_erro = traduz("data.fechamento.nao.pode.ser.maior.atual", $con, $cook_idioma);
		}

		if ($login_fabrica == 85) {
            $hora_fechamento = filter_input(INPUT_POST,'hora_fechamento');
            if (empty($hora_fechamento)) {
                $msg_erro = traduz("digite.a.hora.de.fechamento", $con, $cook_idioma);
            } else {
                $horas = substr($hora_fechamento, 0,2);
                $minutos = substr($hora_fechamento, 3,2);
                if (($horas > "23") || ($minutos > "59")) {
                    $msg_erro = traduz("digite.a.hora.de.fechamento", $con, $cook_idioma);
                } else {

                    $xdata_hora_fechamento = fnc_formata_data_hora_pg($data_fechamento." ".$hora_fechamento);
                }
            }
		}

		if (isFabrica(1, 30)) { // HD 158420
    		$enviar_email_pesquisa = $_POST['enviar_email_pesquisa'];

    		foreach ($_POST["os"] as $i => $value) {

    			if ($login_fabrica == 1) {
    				if ($usa_estoque_posto == "sim" && $_POST["os"][$i]['ativo2_'. $i] == 't') {
    					$ativo = trim($_POST["os"][$i]['ativo2_'. $i]);
    				} else {
						$ativo = trim($_POST["os"][$i]['ativo_'. $i]);    					
    				}	
    			} else {
					$ativo = trim($_POST["os"][$i]['ativo_'. $i]);
    			}

				$os = trim($_POST["os"][$i]['os_' . $i]);

				if ($login_fabrica == 1 && empty($_POST["os"][$i]['data_conserto_'. $i]) && data_conserto_vazia($os)) {
					continue;
				}

				if($ativo =='t') {
					if ($login_fabrica == 30) {
						/* Se a OS estiver em algum HelpDesk do tipo 'Solicitação de troca de produto' o mesmo não será concertado */
						$sql = "SELECT
									os_bloqueada
								FROM tbl_os_campo_extra
								WHERE fabrica = {$login_fabrica}
									AND os = {$os};";

						$res = pg_query($con, $sql);
						if (pg_num_rows($res) > 0) {
							$os_bloqueada = pg_fetch_result($res, 0, "os_bloqueada");
							$msg_erro = ($os_bloqueada == 't') ? traduz("Esta OS $os não pode ser consertada pois existe um HelpDesk aberto como 'Solicitação de troca de produto'.") : "";
							$erro = $msg_erro;
						}
					}

                    if($login_fabrica == 1){
                        $os_email[] =$os;

                        if($enviar_email_pesquisa == "sim"){
                            /**
                             * Gravando em tbl_os_campo_extra
                             * para enviar pesquisa
                             */

                            $sqlRetOS = "SELECT COUNT(*) AS contador FROM tbl_os_campo_extra where os = $os;";
                            $retOS = pg_query($con, $sqlRetOS);

                            if (strlen(pg_last_error()) > 0) {
								$msg_erro .= pg_errormessage($con)."<br />";
						    }

                            $contOS = trim(pg_fetch_result($retOS, 0, contador));

                            	if ($contOS == 0){

	                            $sql = "
	                                INSERT INTO tbl_os_campo_extra (
	                                    os,
	                                    fabrica,
	                                    enviar_email
	                                ) VALUES (
	                                    $os,
	                                    $login_fabrica,
	                                    TRUE
	                                )
	                            ";
	                            $res = pg_query($con,$sql);

                        	}
						}
                    }

                    if ($login_fabrica == '30' and empty($msg_erro)) {

                        $p_serie = $_POST['serie_' . $i];

                        if (empty($p_serie)) {
                            continue;
                        } else {
                        	$res_serie = pg_query($con, "BEGIN");

                            $sql_serie = "UPDATE tbl_os SET serie = '$p_serie' WHERE os = $os; SELECT fn_valida_os_item($os, $login_fabrica);";
                            $res_serie = pg_query($con, $sql_serie);
							$msg_erro .= pg_last_error();
							$erro .= pg_last_error();
                            if (pg_affected_rows($res_serie) > 1) {
                                $res_serie = pg_query($con, "ROLLBACK");
                                unset($_POST['serie_' . $i]);
                            } else {
                                if (!pg_last_error($con)) {
                                    $res_serie = pg_query($con, "COMMIT");
                                } else {
                                    $res_serie = pg_query($con, "ROLLBACK");
									$erro .= pg_last_error();
                                    unset($_POST['serie_' . $i]);
                                }
                            }
                        }
                    }

					// O que este trecho de código está fazendo aqui!??
                    if (in_array($login_fabrica, array(173)) ) {

                        $p_serie = $_POST['serie_' . $i];

                        if (empty($p_serie)) {
                            continue;
                        } else {
                        	if (verifica_tipo_posto("posto_interno", "true")){

                        		$sqlAS = "SELECT tbl_os.serie, tbl_os.produto FROM tbl_os WHERE fabrica = {$login_fabrica} AND os = {$os};";
                        		$resAS = pg_query($con,$sqlAS);

                        		$serieAtual = pg_fetch_result($resAS, 0, serie);
                        		$serieProd = pg_fetch_result($resAS, 0, produto);

                        		//executar a validação do numero de serie novo
                        		if (valida_numero_de_serie_jfa($p_serie ,$serieProd) ) {
                        			$res_serie = pg_query($con, "BEGIN");
	                        		if (!empty($serieAtual)) {
	                        			$sql_serie = "UPDATE tbl_os SET serie = '{$p_serie}', serie_reoperado = '{$serieAtual}' WHERE os = $os; SELECT fn_valida_os_item($os, $login_fabrica);";
	                        		} else {
	                        			$sql_serie = "UPDATE tbl_os SET serie = '$p_serie' WHERE os = $os; SELECT fn_valida_os_item($os, $login_fabrica);";
	                        		}
		                            $res_serie = pg_query($con, $sql_serie);

		                            if (pg_affected_rows($res_serie) > 1) {
		                            	$erro .= pg_last_error();
		                                $msg_erro .= pg_last_error();

		                                $res_serie = pg_query($con, "ROLLBACK");
		                                unset($_POST['serie_' . $i]);
		                            } else {
		                                if (!pg_last_error($con)) {
		                                    $res_serie = pg_query($con, "COMMIT");
		                                } else {
		                                	$erro .= pg_last_error();
		                                	$msg_erro .= pg_last_error();

		                                    $res_serie = pg_query($con, "ROLLBACK");

		                                    unset($_POST['serie_' . $i]);
		                                }
		                            }
                        		} else {
	                        		$erro .= traduz("Erro ao validar número de série!");
	                        	}
                        	}
                        }
                    }

				    if($login_fabrica == 1){
							$sql = "SELECT fn_valida_os_item($os, $login_fabrica)";
							$res = @pg_query ($con,$sql);

							if (strlen(pg_last_error()) > 0) {
								$msg_erro .= pg_errormessage($con)."<br />";
							}

							# esta alteracao foi necessaria devido ao chamado 1419
							# Na verdade o valida os item deve ser realizado quando digitar o item, mas
							# quando a Fabiola/Silvania questionou sobre OS com item que não constavam na
							# lista básica, o Tulio começou a validar os itens no fechamento tambem.
							# começou a causar problemas com o Type, e substituição de peças.
							if (strpos ($msg_erro,"na lista b") > 0 and strpos ($msg_erro,"m o TYPE desta") > 0) $msg_erro = '';
							if (strpos ($msg_erro,"Referência") > 0 and strpos ($msg_erro,"mudou para") > 0) $msg_erro = '';
							if (strpos ($msg_erro,"obsoleta") !==false) $msg_erro = "";
				    }
				}
			}
		}

		if (strlen($msg_erro) == 0){

			// HD  27468
			if(in_array($login_fabrica,array(1,3,6,7,11,172))){
				$res = pg_query ($con,"BEGIN TRANSACTION");
			}

			if(in_array($login_fabrica, array(11,172))){ #HD 346804
				foreach ($_POST["os"] as $y => $value) {
					$ativo_os_nf = trim($_POST["os"][$y]['ativo_' . $y]);

					if($ativo_os_nf == 't'){
						$array_os[] = trim($_POST["os"][$y]['os_' . $y]);
					}
				}
			}


			/**
			 * - Verifica, para TECTOY, se alguma OS em fechamento
			 * teve um anexo gravado nesta tela
			 * - Caso positivo:
			 *  -- Não será finalizada;
			 *  -- Entrará em Auditoria
			 *
			 * @author William Ap. Brandino
			 */
			if($login_fabrica == 6){
				foreach ($_POST["os"] as $an => $value) {
                    $ativo = trim($_POST["os"][$an]['ativo_'. $an]);
                    $os    = trim($_POST["os"][$an]['os_' . $an]);

                    if($ativo == 't' && $_FILES['foto_nf_'.$an]['name'] != ""){
                        if(temNF($os,'count') > 0){
                            $anexo_exclui = temNF($os,'path');
                            excluirNF($anexo_exclui[0]);
                        }
                        $anexou = anexaNF($os, $_FILES['foto_nf_'.$an]);
                        if($anexou === 0) {
                            $sqlVerifica = "SELECT  tbl_os_status.status_os
                                            FROM    tbl_os_status
                                            WHERE   os = $os
                                            AND     status_os IN (189,190,191)
                                            ORDER BY      os_status DESC
                                            LIMIT   1";
                            $resVerifica = pg_query($con,$sqlVerifica);

                            if(pg_fetch_result($resVerifica,0,status_os) != 189){
                                $sql = "INSERT INTO tbl_os_status (
                                            os,
                                            status_os,
                                            observacao
                                        ) VALUES (
                                            $os,
                                            189,
                                            'OS em auditoria de nota fiscal, incluída no fechamento'
                                        )";
                                $res = pg_query($con, $sql);
                                if(!pg_last_error($con)){
                                    $array_os_volta_auditoria[] = $os;
                                }
                            }
                        }else{
                            $msg_erro .= traduz("Não foi possível anexar a Nota Fiscal da OS.");
                        }
                    }
                }
			}

			if(in_array($login_fabrica , [120,201]) and $ajax == true){
				$_POST['os']= array("0" => $os);
			}

			$linha_erro = array(); // HD 101630
			$msg_erro = "";

			foreach ($_POST["os"] as $i => $value) {
				$erro = "";
				if ($login_fabrica == 1) {
					if ($usa_estoque_posto == 'sim' && $_POST["os"][$i]['ativo2_'. $i] == 't') {
						$ativo      = trim($_POST["os"][$i]['ativo2_'. $i]);
					} else {
						$ativo      = trim($_POST["os"][$i]['ativo_'. $i]);
					}
			
					if (empty($_POST["os"][$i]['data_conserto_'. $i]) && data_conserto_vazia($_POST["os"][$i]['os_' . $i])) {
						continue;
					}

				} else {
					$ativo          = trim($_POST["os"][$i]['ativo_'. $i]);
				}

				$os                 = trim($_POST["os"][$i]['os_' . $i]);
				$serie              = trim($_POST['serie_'. $i]);
				$defeito_constatado = trim($_POST['defeito_constatado_'. $i]);
				$observacao         = trim($_POST['observacao_'. $i]);
				$serie_reoperado    = trim($_POST['serie_reoperado_'. $i]);
				$nota_fiscal_saida  = trim($_POST['nota_fiscal_saida_'. $i]);
				$valor_fiscal_saida = trim($_POST['valor_fiscal_saida_'. $i]);
				$nota_fiscal_verif  = trim($_POST['nota_fiscal_verif_'. $i]);
				$data_nf_saida      = trim($_POST['data_nf_saida_'. $i]);
				$motivo_fechamento  = trim($_POST['motivo_fechamento_'. $i]);
				$tecnico            = (int) trim(@$_POST['tecnico_'. $i]);

				if (in_array($login_fabrica, [177]) && $ativo == 't') {

					if (isset($_POST['lote_peca_nova_'.$i]) && empty($_POST['lote_peca_nova_'.$i])) {
						$msg_erro .= traduz("Para finalizar a OS %, é obrigatório informar o novo lote da peça <br />",null,null,[$os]);
					} else {
						$lote_peca_nova = trim($_POST['lote_peca_nova_'.$i]);
					}
					
				}

				if (in_array($login_fabrica, array(11,172))) {

					$sql_fabrica_os_dc = "SELECT fabrica, sua_os FROM tbl_os WHERE os = {$os}";
					$res_fabrica_os_dc = pg_query($con, $sql_fabrica_os_dc);

					$sua_os        = pg_fetch_result($res_fabrica_os_dc, 0, "sua_os");
					$fabrica_os_dc = pg_fetch_result($res_fabrica_os_dc, 0, "fabrica");

				}else{
					$fabrica_os_dc = $login_fabrica;
				}

				if (usaDataConserto($login_posto, $fabrica_os_dc) && $ativo == 't') {
					$retira_hora = explode(" ", $_POST["os"][$i]['data_conserto_'.$i]);

					if(in_array($login_fabrica, array(11,172))){
						if(strlen($retira_hora) == 0){

							$sql_data_conserto = "SELECT data_conserto FROM tbl_os WHERE os = {$os}";
							$res_data_conserto = pg_query($con, $sql_data_conserto);

							$retira_hora = pg_fetch_result($res_data_conserto, 0, "data_conserto");

							$retira_hora = explode(" ", $retira_hora);

						}
					}

					$data_conserto_formatada   = formata_data($retira_hora[0]);
					$data_fechamento_valida = $_POST['data_fechamento'];
					$oDtDf = DateTime::createFromFormat('d/m/Y', $data_fechamento_valida);
					$data_fechamento_formatada = $oDtDf->format('Y-m-d');

					$os_desc = (in_array($login_fabrica, array(11,172))) ? $sua_os : $os;

					if (strtotime($data_fechamento_formatada) < strtotime($data_conserto_formatada) && !empty($data_conserto_formatada)) {
						$erro = traduz("Data de fechamento da OS $os_desc não pode ser anterior à data de conserto <br />");
					}

				}

				if(in_array($login_fabrica, [167, 203])){
					$xdescricao_tipo_atendimento = $_POST['descricao_tipo_atendimento_'. $i];
				}

				if(in_array($login_fabrica, [157]) && $ativo == "t"){

					$sql_os_item = "SELECT os_item FROM tbl_os_item WHERE os_produto IN (SELECT os_produto FROM tbl_os_produto WHERE os = {$os})";
					$res_os_item = pg_query($con, $sql_os_item);

					if(pg_num_rows($res_os_item) == 0){
						$msg_erro .= traduz("Para finalizar a OS %, é obrigatório o lançamento de peça <br />",null,null,[$os]);
					}

				}

				//fputti hd-2892486
			    if (in_array($login_fabrica, array(50))) {
			        $sqlOSDec = "SELECT A.consumidor_nome_assinatura, to_char(B.termino_atendimento, 'DD/MM/YYYY')  termino_atendimento
			                       FROM tbl_os A
			                       JOIN tbl_os_extra B ON B.os=A.os
			                      WHERE A.os={$os}";
			        $resOSDec = pg_query($con, $sqlOSDec);
					if(pg_num_rows($resOSDec) > 0) {
						$dataRecebimento = pg_fetch_result($resOSDec, 0, 'consumidor_nome_assinatura');
						$recebidoPor     = pg_fetch_result($resOSDec, 0, 'termino_atendimento');
						if (strlen($dataRecebimento) == 0 && strlen($recebidoPor) == 0) {
							$msg_erro .= traduz("ERROR: É obrigatório o preenchimento da declaração na OS %. <br />",null,null,[$os]);
						}
					}
			    }

				if (isFabrica(3, 42) && $ativo == "t") {

					$sql_os_canc = "SELECT cancelada FROM tbl_os WHERE os = {$os} AND fabrica = {$login_fabrica}";

                    if ($cancelaOS) {
                        $sql_os_canc = "SELECT status_checkpoint = 28 FROM tbl_os WHERE os = $os AND fabrica = $login_fabrica";
                    }
					$res_os_canc  = pg_query($con, $sql_os_canc);
					$os_cancelada = pg_fetch_result($res_os_canc, 0, "cancelada");

					if ($os_cancelada == "t") {

						$erro .= traduz("A OS % está Cancelada, assim não podendo ser Fechada <br />",null,null,[$os]);

                    }

                    if (!$os_cancelada and isFabrica(42)) {

						$sql_auditoria_cortesia = "SELECT liberada FROM tbl_auditoria_os WHERE os = {$os} AND observacao = 'Auditoria de Solicitação de Cortesia Comercial'";
						$res_auditoria_cortesia = pg_query($con, $sql_auditoria_cortesia);

						if(pg_num_rows($res_auditoria_cortesia) > 0){

							$liberado_cortesia = pg_fetch_result($res_auditoria_cortesia, 0, "liberada");

							if(strlen($liberado_cortesia) == 0){
								$erro .= traduz("A OS % está em Auditoria de Solicitação de Cortesia Comercial <br />",null,null,[$os]);
							}

						}

			   			$sql_aud_media = "SELECT liberada FROM tbl_auditoria_os WHERE os = {$os} AND observacao = 'Quantidade de OSs abertas no mês atual é maior que o dobro da média.'";
			            $res_aud_media = pg_query($con, $sql_aud_media);

			            if (pg_num_rows($res_aud_media) > 0) {

							$liberado = pg_fetch_result($res_aud_media, 0, "liberada");

			            	if (strlen($liberado) == 0) {
			                	$erro .= traduz("A OS % está em Auditoria e aguardando aprovação da fábrica",null,null,[$os]);
			            	}
			            }

					}

				}
				

				if ($login_fabrica == 158) {
					$data_digitacao      = $_POST["os"][$i]["data_digitacao"];
					$inicio_atendimento = $_POST["os"][$i]["inicio_atendimento"];
					$fim_atendimento    = $_POST["os"][$i]["fim_atendimento"];

					$sql_dados_posto = "SELECT os, data_digitacao,descricao 
										FROM tbl_os 
										JOIN tbl_posto_fabrica using(posto,fabrica) 
										JOIN tbl_tipo_posto using(tipo_posto) 
										WHERE os = {$os} 
										AND tbl_os.fabrica = {$login_fabrica} 
										AND posto = {$login_posto}";
                    $res_dados_posto = pg_query($con, $sql_dados_posto);

				}

				if ($login_fabrica == 158 && $ativo == "t") {
					if (empty($inicio_atendimento)) {
						$msg_erro .= traduz("Informe o ínicio de atendimento da OS % <br />",null,null,[$os]);
						//$erro = true;
					} else {
						$dd = new DateTime($data_digitacao);
						$dd->setTimeZone(new DateTimeZone('America/Sao_Paulo'));
						$data_digitacao = $dd->format("Y-m-d H:i");

						list($data, $horario)  = explode(" ", $inicio_atendimento);
						list($dia, $mes, $ano) = explode("/", $data);

						$aux_inicio_atendimento = "{$ano}-{$mes}-{$dia} {$horario}";

						if (!strtotime($aux_inicio_atendimento) || preg_match("/^24/", $horario) || strtotime($aux_inicio_atendimento) > strtotime(date("Y-m-d H:i"))) {
							$msg_erro .= traduz("OS % inicio de atendimento inválido <br />",null,null,[$os]);
							//$erro = true;
						} else if (strtotime($aux_inicio_atendimento) < strtotime($data_digitacao)) {
							$msg_erro .= traduz("OS % inicio de atendimento não pode ser inferior a data de abertura<br />",null,null,[$os]);
							//$erro = true;
						}

						$tipo_posto_descricao = pg_fetch_result($res_dados_posto,0,'descricao');

                           	if ($tipo_posto_descricao == "Terceiro") {

                                   $data_digitacao = pg_fetch_result($res_dados_posto, 0, 'data_digitacao');
                                   $hj = date("Y-m-d H:i:s");

                                   $dataDigitacao = new DateTime($data_digitacao);
                                   $dataHoje      = new DateTime($hj);

                                   $data1  = $dataDigitacao->format('Y-m-d H:i:s');
                                   $data2  = $dataHoje->format('Y-m-d H:i:s');

                                   $diff              = $dataDigitacao->diff($dataHoje);
                                   $horasEmAberto = $diff->h + ($diff->days * 24);

                                   list($dataF, $horaF)     = explode(" ", $fim_atendimento);
                                   list($diaF, $mesF, $anoF) = explode("/", $dataF);

                                   $dataFim = "$anoF-$mesF-$diaF $horaF";

                                   $sqlData   = "SELECT current_timestamp - interval '72 hours';";
                                   $resData   = pg_query($con, $sqlData);
                                   $dataMenos = pg_fetch_result($resData, 0, 0);

                                   if ($horasEmAberto > 72 && ($dataFim < $dataMenos)) {
                                           $msg_erro .= "OS {$os}. Não é possível inserir a data Fim de Atendimento, pois ultrapassa o prazo de 72 horas da data atual. Favor inserir uma data no período de 72 horas ou entrar em contato com a área Administrativa de sua região.<br />";
                                   }
                         	}

					}

					if (empty($fim_atendimento)) {
						$msg_erro .= traduz("Informe o fim de atendimento da OS % <br />",null,null,[$os]);
						//$erro = true;
					} else {
						list($data, $horario)  = explode(" ", $fim_atendimento);
						list($dia, $mes, $ano) = explode("/", $data);

						$aux_fim_atendimento = "{$ano}-{$mes}-{$dia} {$horario}";

						if (!strtotime($aux_fim_atendimento) || preg_match("/^24/", $horario) || strtotime($aux_fim_atendimento) > strtotime(date("Y-m-d H:i"))) {
							$msg_erro .= traduz("OS % fim de atendimento inválido <br />",null,null,[$os]);
							//$erro = true;
						}
					}

					if (empty($msg_erro) && strtotime($aux_inicio_atendimento) >= strtotime($aux_fim_atendimento)) {
						$msg_erro .= traduz("OS % inicio do atendimento não pode ser superior ou igual ao o fim de atendimento <br />",null,null,[$os]);
						//$erro = true;
					}

					$linha_erro[] = $os;
				}

				$cond_pesquisa_fabrica = (in_array($login_fabrica, array(11,172))) ? " fabrica IN (11,172) " : " fabrica = $login_fabrica ";

				if((in_array($login_fabrica, array(11,160,164,167,169,170,172,203)) or $replica_einhell) && $ativo == 't'){

					$sql = "SELECT
								data_conserto,
								os_troca,
								sua_os
							FROM tbl_os
							LEFT JOIN tbl_os_troca USING(os)
							WHERE
								tbl_os.os = $os
								AND {$cond_pesquisa_fabrica}";

					$res = pg_query($con, $sql);

					if(pg_num_rows($res)>0){
						$data_conserto = pg_fetch_result($res, 0, "data_conserto");
						$os_troca      = pg_fetch_result($res, 0, "os_troca");
						$sua_os        = pg_fetch_result($res, 0, "sua_os");
					}

					if(in_array($login_fabrica, [167, 203])){
						if($xdescricao_tipo_atendimento == "Orçamento" OR $xdescricao_tipo_atendimento == "Garantia Recusada"){
							$fechamento = str_replace("'"," ",$xdata_fechamento);
							$data_conserto = $fechamento;
							$hora_fechamento = date('H:i:s');
							$data_conserto = "'".$data_conserto.' '.$hora_fechamento."'";

							$update_dc = "UPDATE tbl_os SET data_conserto = {$data_conserto} WHERE os = {$os} AND fabrica = {$login_fabrica}";
							$res_update_dc = pg_query($con, $update_dc);
						}
					}

					if(!in_array($login_fabrica, [167, 203])){
						if(empty($os_troca)) {
							if(strlen(trim($data_conserto)) == 0 && in_array($login_fabrica, array(164))){

								$sql_dc = "UPDATE tbl_os SET data_conserto = CURRENT_TIMESTAMP WHERE os = {$os} AND fabrica = {$login_fabrica}";
								$res_dc = pg_query($con, $sql_dc);

								$data_conserto = date("d/m/Y H:i");
							}

							if(!in_array($login_fabrica, array(164))){
								if(strlen(trim($data_conserto)) == 0){
									$os_desc = (in_array($login_fabrica, array(11,172))) ? $sua_os: $os;
									$erro .= traduz("O produto deve ser consertado para o fechamento da O.S: % <br />",null,null,[$sua_os]);
								}
							}
						}
					}
				}

				if($login_fabrica == 52 and $ativo == 't'){

			        $sql = "SELECT current_date - data_abertura AS interval, motivo_atraso FROM tbl_os WHERE os = {$os}";
			        $res = pg_query($con, $sql);

			        $interval = pg_fetch_result($res, 0, "interval");
			        $motivo_atraso = pg_fetch_result($res, 0, "motivo_atraso");

			        /* maaior que 72 horas */
			        if($interval > 3 && strlen($motivo_atraso) == 0){

			            $msg_erro .= traduz("Favor informar o motivo de atraso para realizar o fechamento da OS % <br />",$con,$cook_idioma,[$os]);

			        }

			    }

				if($login_fabrica == 6 && $ativo == "t"){
                    if(in_array($os,$array_os_volta_auditoria)){
                        $nao_fechou .= traduz("A OS % voltou para auditoria, por cadastro de anexo de nota fiscal.",null,null,[$os]);
                        continue;
                    }
                    $anexoAnterior = temNF($os,'count');
                    if($anexoAnterior == 0){
                        $nao_fechou .= traduz("A OS $os necessita ter um anexo da nota fiscal.",null,null,[$os]);
                        continue;
                    }
				}

				if($login_fabrica == 59){

					$sql_nome_tecnico = "SELECT tecnico FROM tbl_os WHERE os = $os";
					$res_nome_tecnico = pg_query($con, $sql_nome_tecnico);

					$nome_tecnico = pg_fetch_result($res_nome_tecnico, 0, 'tecnico');

					if(strlen($nome_tecnico) == 0){
						$msg_erro .= traduz("Por favor insira o nome do Técnico na OS: <strong>$os</strong> <br />",null,null,[$os]);
					}

				}

				if ($login_fabrica == 94) {
			        $verifica_pedido = temPedido($os);
			        if (!empty($verifica_pedido)) {
			            $msg_erro = $verifica_pedido;
			        }
			    }

				if (in_array($login_fabrica, array(165)) && $posto_interno == true && $ativo == "t") {
					$sqlSuaOs = "SELECT sua_os FROM tbl_os WHERE fabrica = {$login_fabrica} AND posto = {$login_posto} AND os = {$os}";
					$resSuaOs = pg_query($con, $sqlSuaOs);

					$suaOs = pg_fetch_result($resSuaOs, 0, "sua_os");

					$tipo_entrega    = $_POST["os"][$os]["tipo_entrega"];
					$codigo_rastreio = $_POST["os"][$os]["codigo_rastreio"];

					if (empty($tipo_entrega)) {
						$msg_erro .= traduz("Informe o tipo de entrega da OS: %<br />",null,null,[$suaOs]);
					} else if ($tipo_entrega == "correios" && empty($codigo_rastreio)) {
						$msg_erro .= "Informe o código de rastreio da OS: {$suaOs}<br />";
					} else {
						$sql = "UPDATE tbl_os_extra SET pac = '$codigo_rastreio' WHERE os = {$os}";
						$res = pg_query($con, $sql);

						if (strlen(pg_last_error()) > 0) {
							$msg_erro .= traduz("Erro ao gravar código de rastreio<br />");
						}
					}
				}

				if(in_array($login_fabrica,array(24,40,50,104,105,120,201,156)) && $ativo =='t'){
                    if($login_fabrica != 50){
                        $sqlAuditoria = "
                            SELECT  tbl_auditoria_os.os,tbl_os.sua_os
                            FROM    tbl_auditoria_os
                            JOIN    tbl_os                  ON  tbl_os.os = tbl_auditoria_os.os
                                                            AND tbl_os.fabrica = $login_fabrica
                            JOIN    tbl_auditoria_status    ON  tbl_auditoria_status.auditoria_status = tbl_auditoria_os.auditoria_status
                            WHERE   tbl_auditoria_os.os = $os
                            AND     tbl_auditoria_os.liberada IS NULL
                            AND     tbl_auditoria_os.cancelada IS NULL
                            AND     tbl_auditoria_os.reprovada IS NULL
                      ORDER BY      data_input DESC";
                        $resAuditoria = pg_query($con,$sqlAuditoria);
                        if(pg_num_rows($resAuditoria) > 0) {
                            $sua_os = pg_fetch_result($resAuditoria, 0, 'sua_os');
                            $msg_erro = traduz("OS % em auditoria, não pode ser fechada <br/>",null,null,[$sua_os]);
                        }
                    }

                    if ($login_fabrica != 24) {

						$sql = "SELECT
									status_os
								FROM tbl_os_status
								WHERE
									status_os in(62, 64, 81)
									AND os = {$os}
								ORDER BY data DESC
								LIMIT 1";

						if(in_array($login_fabrica, array(40,120,201))){

							$sql = "SELECT
										status_os
									FROM tbl_os_status
									WHERE
										os = {$os}
									ORDER BY data DESC
									LIMIT 1";

						}

						$res = @pg_query($con,$sql);

						if (pg_num_rows($res) > 0) {

							$os_status = pg_result($res,0,0);

							if($os_status == 62){
								$msg_erro = traduz("OS : % em intervenção técnica do fabricante, não pode ser fechada<br />",null,null,[$os]);
							}

							if($os_status == 102){
								$msg_erro = traduz("OS : % em intervenção de Número de Série, não pode ser fechada<br />",null,null,[$os]);
							}

							if($os_status == 118){
								$msg_erro = traduz("OS : % em intervenção 3 ou mais peças, não pode ser fechada<br />",null,null,[$os]);
							}

							if($os_status == 13 AND $login_fabrica <> 40){
								$msg_erro = traduz("OS : % recusada pelo fabricante, não pode ser fechada<br />",null,null,[$os]);
							}

						} 

						if (in_array($login_fabrica, [120,201]) && empty($msg_erro)) {
							$sql = "SELECT
										status_os
									FROM tbl_os_status
									WHERE
										os = {$os}";
							$res = pg_query($con,$sql);
							if (pg_num_rows($res) > 0) {
								$dadosRes = pg_fetch_all($res);
								$statusArr = [];
								foreach ($dadosRes as $k => $v) {
									$statusArr[] = $v["status_os"];
								}
								$verifica_aprovado = verificaTodasAuditoria($statusArr);
								if (!$verifica_aprovado) {
									$msg_erro = traduz("OS $os em auditoria, não pode ser fechada <br/>");
								}
							}
						}
					}

					if(empty($msg_erro) && !in_array($login_fabrica,array(24,40,50,161))){
						$sql = "SELECT descricao, orientacao
						FROM tbl_os
						JOIN tbl_defeito_constatado USING(defeito_constatado)
						WHERE os = $os";
						$res = pg_query($con,$sql);
						if(pg_numrows($res) > 0){
							$defeito_constatado_descricao = pg_result($res,0,0);
							$defeito_constatado_orientacao = pg_result($res,0,1);

							if($defeito_constatado_orientacao != 't' ){
								$sql = "SELECT COUNT(1)
								FROM tbl_os
								JOIN tbl_os_produto ON tbl_os_produto.os = tbl_os.os
								JOIN tbl_os_item ON tbl_os_item.os_produto = tbl_os_produto.os_produto
								WHERE tbl_os.os = $os";
								$res = @pg_query($con,$sql);

								$itens = pg_result($res,0,0);
								if($itens == 0){
									$msg_erro = traduz("OS : % sem lançamento de peças, não pode ser fechada<br />",null,null,[$os]);
								}
							}
						}
					}
				}

				if( in_array($login_fabrica,array(11,96,172))){
					$sql = "SELECT sua_os FROM tbl_os WHERE os = $os";
					$res = pg_query($con,$sql);
					$sua_os = pg_result($res,0,0);
					if( !empty($nota_fiscal_verif) and $ativo == 't' ){
						if(!empty($data_nf_saida) and !empty($nota_fiscal_saida)){
							list($dnf, $mnf, $ynf) = explode("/", $data_nf_saida);
							if(!checkdate($mnf,$dnf,$ynf)){
								$erro .= traduz("Data de Saída de NF da OS : % está incorreta<br />",null,null,[$sua_os]);
							}else{
								list($df, $mf, $yf) = explode("/", $data_fechamento);

								$aux_data_nf_saida = "$ynf-$mnf-$dnf";
								$aux_data_fechamento = "$yf-$mf-$df";

								if(strtotime($aux_data_nf_saida) < strtotime($aux_data_fechamento)){
									$erro .= traduz("Data de Saída de NF da OS : % não pode ser inferior a Data de Fechamento<br />",null,null,[$sua_os]);
								}
							}
						}
						else{
							$erro .= traduz("O número e data da NF devem ser informados na OS : %<br />",null,null,[$sua_os]);
						}
					}
				}

				if (in_array($login_fabrica, array(156)) && $posto_interno == true) {
					$nf_retorno       = $_POST['nf_retorno_'.$i];
					$data_nf_retorno  = $_POST['data_nf_retorno_'.$i];
					$valor_nf_retorno = $_POST['valor_nf_retorno_'.$i];

					if (empty($nf_retorno)) {
						$erro .= traduz("Informe a nota fiscal de retorno da OS: %<br />",null,null,[$os]);
					}

					if (!empty($data_nf_retorno)) {
						list($dnf, $mnf, $ynf) = explode("/", $data_nf_retorno);

						if (!checkdate($mnf, $dnf, $ynf)) {
							$erro .= traduz("Data de Retorno de NF da OS : % está incorreta<br />",null,null,[$os]);
						}
					} else {
						$erro .= traduz("A Data da NF de Retorno deve ser informada na OS : %<br />",null,null,[$os]);
					}

                    if(empty($valor_nf_retorno)) {
						$erro .= traduz("É obrigatório preencher valor da nota fiscal de retorno da os: %<br />",null,null,[$os]);
					}
				}

				if($login_fabrica == 59 && $ativo =='t')  { // HD 337877
					$sql = 'SELECT	defeito_constatado
					FROM	tbl_os
					WHERE	os = ' . $os . '
					AND		fabrica = ' . $login_fabrica . '
					AND		defeito_constatado IS NOT NULL;';
					//echo nl2br($sql);
					$res = pg_query($con,$sql);
					if(pg_num_rows($res) == 0) {
						$nao_fechou .= traduz('A OS % não pode ser fechada, cadastre um defeito constatado.<br />',null,null,[$os]);
						continue;
					}
				}

				if($login_fabrica==2) {
					$pac = trim($_POST['pac_'. $i]);
				}

				if($login_fabrica==1) {
					$ativo_revenda             = trim($_POST["os"][$i]['ativo_revenda_'. $i]);
				}

				if (in_array($login_fabrica, array(11,172))) {
					$cond_pesquisa_fabrica = (in_array($login_fabrica, array(11,172))) ? " tbl_os.fabrica IN(11,172) " : " tbl_os.fabrica = $login_fabrica ";
					$sql = "SELECT consumidor_revenda FROM tbl_os WHERE os = $os AND {$cond_pesquisa_fabrica} ";
					$res = pg_query($con,$sql);
					$consumidor_revenda = pg_fetch_result($res,0,consumidor_revenda);
				}

				//hd 24714
				if($ativo =='t' and strlen($erro) == 0) {
					$sql = "SELECT $xdata_fechamento < tbl_os.data_abertura FROM tbl_os where os=$os AND tbl_os.fabrica = $login_fabrica";
					$res = @pg_query($con,$sql);
					if (@pg_fetch_result($res,0,0) == 't'){
						$erro = traduz("data.de.fechamento.nao.pode.ser.anterior.a.data.de.abertura", $con, $cook_idioma); /*"Data de fechamento não pode ser anterior a data de abertura.";*/
					}
				}

				/*
				if($login_fabrica == 3 AND $ativo == 't' and strlen($erro) == 0){
					if($tecnico == 0){
						$erro = "Informe um técnico";
					}
				}
				*/

				if($login_fabrica == 3 AND $ativo == 't' and strlen($erro) == 0){

					$sql = "SELECT tbl_os_item.os_item, tbl_os_item.pedido, tbl_os_item.peca, tbl_os_item.qtde
					FROM tbl_os_produto
					JOIN tbl_os_item           ON tbl_os_produto.os_produto     = tbl_os_item.os_produto
					JOIN tbl_servico_realizado ON tbl_os_item.servico_realizado = tbl_servico_realizado.servico_realizado
					LEFT JOIN tbl_faturamento_item on tbl_os_item.peca = tbl_faturamento_item.peca and tbl_os_item.pedido = tbl_faturamento_item.pedido
					WHERE tbl_os_produto.os = $os
					AND tbl_servico_realizado.gera_pedido IS TRUE
					AND  tbl_faturamento_item.faturamento_item IS NULL
					LIMIT 1";
					$res = @pg_query($con,$sql);

					$cancelado = "";

					//HD 6477
					if(pg_num_rows($res)>0) {
						$xpedido = pg_fetch_result($res,0,pedido);
						$xpeca   = pg_fetch_result($res,0,peca);
						$xqtde   = pg_fetch_result($res,0,qtde);
						if(strlen($xpedido) > 0) {
							$sqlC = "SELECT os
							FROM tbl_pedido_cancelado
							WHERE pedido  = $xpedido
							AND   peca    = $xpeca
							AND   qtde    = $xqtde
							AND   os      = $os
							AND   fabrica = $login_fabrica";
							$resC = @pg_query($con, $sqlC);

							if(pg_num_rows($resC)>0) $cancelado = pg_result($resC,0,0);
						}
					}

					if(pg_num_rows($res)>0 and strlen($cancelado)==0 and strlen($motivo_fechamento)==0){
						$erro .= traduz("os.com.pecas.pendentes,.favor.informar.o.motivo.na.tela.de.fechamento.da.os", $con, $cook_idioma); /*"OS com peças pendentes, favor informar o motivo do fechamento<BR>"; */
						$xmotivo_fechamento = "null";
						array_push($linha_erro,$os);
					}else{
						$xmotivo_fechamento = "'$motivo_fechamento'";
					}
				}else{
					$xmotivo_fechamento = "null";
				}

				if($login_fabrica==3 AND $login_posto==6359){// hd 16018
					$sql = "SELECT aprovado
					FROM tbl_os_atendimento_domicilio
					JOIN tbl_os USING(os)
					WHERE tbl_os.posto   = $login_posto
					AND   tbl_os.fabrica = $login_fabrica
					AND   tbl_os.os      = $os";
					$res = pg_query($con, $sql);
					if(pg_num_rows($res)>0){
						$aprovado = pg_fetch_result($res, 0, aprovado);
						if($aprovado=='f'){
							$erro .= traduz("os.com.atendimento.em.domicilio,.aguardando.aprovacao.do.fabricante", $con, $cook_idioma); /*"OS com atendimento em domicilio, aguardando aprovação do fabricante."; */
						}
					}
				}

				// Verifica se o status da OS for 62 (intervencao da fabrica) // Fábio 02/01/2007
				//Acrescentado $sua_os chamado= 2699 erro recebido no e-mail.
				if ( (in_array($login_fabrica,array(1,3,6,11,51,81,90,141,144,165,172))) AND ($ativo == 't' or $ativo_revenda=='t' and strlen($erro) == 0) ) {

					$cond_validacao = (in_array($login_fabrica, array(141,144,165))) ? "72,73,62,64,65,87,88,116,117,158,159,192,193,194,202" : "72,73,62,64,65,87,88,116,117,158,159";

					$sql = "SELECT  status_os, sua_os, posto
								FROM    tbl_os_status
									JOIN tbl_os using(os)
								WHERE   tbl_os_status.os = $os
									AND tbl_os.fabrica = $login_fabrica
									AND tbl_os_status.status_os IN ($cond_validacao)
								ORDER BY tbl_os_status.data DESC
								LIMIT 1";
					$res = @pg_query($con,$sql);

					if (pg_num_rows($res) > 0) {
						$os_intervencao_fabrica = trim(pg_fetch_result($res,0,status_os));
						$sua_os                 = trim(pg_fetch_result($res,0,sua_os));
						$posto                  = trim(pg_fetch_result($res,0,posto));
						if ($login_fabrica==1){
							$sql2 =	"	SELECT codigo_posto
											FROM tbl_posto_fabrica
											WHERE posto = $posto
												AND fabrica = $login_fabrica";
							$res2 = @pg_query($con,$sql2);
							if (pg_num_rows($res2) > 0) {
								$cod_posto = trim(pg_fetch_result($res2,0,codigo_posto));
								$sua_os = $cod_posto.$sua_os;
							}
						}
						if ($os_intervencao_fabrica == '65') {
							$erro .= traduz("os.%.esta.em.reparo.na.assistencia.tecnica.da.fabrica.nao.pode.ser.fechada", $con, $cook_idioma, $sua_os); /*"OS $sua_os está em reparo na assistência técnica da Fábrica. Não pode ser fechada."; */
						}

						if (in_array($os_intervencao_fabrica,array('62','72','87','116','158'))) {
							if ($login_fabrica == 51 AND $os_intervencao_fabrica == '62') { // HD 59408
								$sql = " INSERT INTO tbl_os_status
								(os,status_os,data,observacao)
								VALUES ($os,64,current_timestamp,'OS Fechada pelo posto')";
								$res = pg_query($con,$sql);
								$erro .= pg_errormessage($con);

								$sql = "UPDATE tbl_os_item SET servico_realizado = 671 FROM tbl_os_produto
								WHERE tbl_os_produto.os_produto = tbl_os_item.os_produto
								AND   tbl_os_produto.os = $os";
								$res = pg_query($con,$sql);
								$erro .= pg_errormessage($con);

								$sql = "UPDATE tbl_os SET defeito_constatado = 10536,solucao_os = 491
								WHERE tbl_os.os = $os";
								$res = pg_query($con,$sql);
								$erro .= pg_errormessage($con);
							}else{
								$erro .= traduz("os.%.esta.em.intervencao.nao.pode.ser.fechada", $con, $cook_idioma, $sua_os)."<br>"; /*"OS $sua_os está em intervenção. Não pode ser fechada."; */
							}
						}else if (in_array($login_fabrica, [141])) {
							if (in_array($os_intervencao_fabrica,array(192))) {
								$erro .= traduz("os.%.esta.em.intervencao.de.troca.e.nao.pode.ser.fechada", $con, $cook_idioma, $sua_os)."<br />";
							}
						}else if(in_array($os_intervencao_fabrica,array(192,193)) ){							
							$erro .= traduz("os.%.esta.em.intervencao.de.troca.e.nao.pode.ser.fechada", $con, $cook_idioma, $sua_os)."<br />";
						}
					}
				}

				if ($login_fabrica == 52 and $ativo =='t') {
					$sqlAd =	"SELECT interv_reinc.os
					FROM (
						SELECT
						ultima_reinc.os,
						(SELECT status_os FROM tbl_os_status WHERE fabrica_status= $login_fabrica AND tbl_os_status.os = ultima_reinc.os AND status_os IN (98,99,100,101) ORDER BY data DESC LIMIT 1) AS ultimo_reinc_status
						FROM (SELECT DISTINCT os FROM tbl_os_status WHERE fabrica_status= $login_fabrica AND status_os IN (98,99,100,101) ) ultima_reinc
						) interv_reinc
						WHERE interv_reinc.ultimo_reinc_status IN (98) and interv_reinc.os = $os";

						$resAd = pg_query($con, $sqlAd);
						if(pg_num_rows($resAd)>0){
							$erro .= traduz("os.%.esta.em.intervencao.nao.pode.ser.fechada", $con, $cook_idioma, $os); /*"OS $sua_os está em intervenção. Não pode ser fechada."; */
						}

					}

				if($login_fabrica==3 AND ($ativo == 't' or $ativo_revenda=='t')){ //HD 56464 - HD 92000
					$sqlAd = "SELECT status_os
					FROM  tbl_os
					JOIN  tbl_os_status USING(os)
					WHERE os=$os
					AND tbl_os.fabrica = $login_fabrica
					AND status_os IN (120, 122, 123, 126, 140, 141, 142, 143)
					ORDER BY data DESC LIMIT 1";
					$resAd = pg_query($con, $sqlAd);
					if(pg_num_rows($resAd)>0){
						$status_os = pg_fetch_result($resAd, 0, status_os);
						if ($status_os == 120 || $status_os == 122 || $status_os == 126){
							$erro .= traduz("auditoria.de.os.aberta.a.mais.de.90.dias.os.nao.ser.alterada", $con, $cook_idioma, $sua_os);
							/*"Auditoria de OS aberta a mais de 90 dias, OS nao ser alterada."; */
						} else if ($status_os == 140 || $status_os == 141 || $status_os == 143) {
							$erro .= traduz("auditoria.de.os.aberta.a.mais.de.45.dias.os.nao.ser.alterada", $con, $cook_idioma, $sua_os);
							/*"Auditoria de OS aberta a mais de 45 dias, OS nao ser alterada."; */
						}
					}
				}

				$xdata_nf_saida = (strlen($data_nf_saida) == 0) ? 'null' : fnc_formata_data_pg ($data_nf_saida) ;
				$xnota_fiscal_saida = (strlen($nota_fiscal_saida) == 0) ? 'null' : "'".$nota_fiscal_saida."'";

				if(in_array($login_fabrica, array(164)) && $posto_interno == true){

					$sql = "UPDATE tbl_os
							SET
								nota_fiscal_saida = {$xnota_fiscal_saida},
                        		data_nf_saida = {$xdata_nf_saida}
                        	WHERE
                        		fabrica = {$login_fabrica}
                        		AND os = {$os}
                        ";
                    $res = pg_query($con, $sql);

				}

				if((in_array($login_fabrica, array(120,201, 139)) || isset($novaTelaOs)) AND empty($erro) AND empty($msg_erro)){
					if(in_array($login_fabrica,[120,201]) and $ajax == true){
						$os 	= $_POST['os'][0];
						$ativo 	= 't';
						$ajax   = $_POST["ajax"];
					}

					if($ativo == 't' or $ativo_revenda == 't'){
						try {
							if ($login_fabrica == 156) {

                                if ($posto_interno == true) {
                                    $data_liberacao_produto = $_POST["os"][$i]["data_liberacao_{$i}"];

                                    if (empty($data_liberacao_produto)) {
                                        throw new Exception(utf8_encode(traduz("Informe a Data da liberação do produto na OS %",null,null,[$os])));
                                    }

                                    $oLiberacaoProduto = DateTime::createFromFormat('d/m/Y', $data_liberacao_produto);
                                    $data_liberacao_produto_format = $oLiberacaoProduto->format('Y-m-d');

                                    $sql = "
                                        UPDATE tbl_os SET
                                        data_nf_saida = '{$data_liberacao_produto_format}'
                                        WHERE fabrica = {$login_fabrica}
                                        AND os = {$os}
                                        ";
                                    $query = $pdo->query($sql);

                                    if (!$query) {
                                        throw new Exception(traduz("Erro ao finalizar a OS %",null,null,[$os]));
                                    }
                                }

								$sql = "SELECT campos_adicionais
										FROM tbl_os_campo_extra
										WHERE os = {$os}
										AND fabrica = {$login_fabrica}";
								$res = pg_query($con, $sql);

								if (!pg_num_rows($res)) {
									$campos_adicionais = array(
										"nf_retorno"       => $nf_retorno,
										"data_nf_retorno"  => $data_nf_retorno,
										"valor_nf_retorno" => $valor_nf_retorno
									);

									$campos_adicionais = json_encode($campos_adicionais);

									$sql = "
										INSERT INTO tbl_os_campo_extra
											(os, fabrica, campos_adicionais)
										VALUES
											({$os}, {$login_fabrica}, '{$campos_adicionais}')
									";
								} else {
									$campos_adicionais = pg_fetch_result($res, 0, "campos_adicionais");
									$campos_adicionais = json_decode($campos_adicionais, true);
									$campos_adicionais["nf_retorno"]       = $nf_retorno;
									$campos_adicionais["data_nf_retorno"]  = $data_nf_retorno;
									$campos_adicionais["valor_nf_retorno"] = $valor_nf_retorno;

									$campos_adicionais = json_encode($campos_adicionais);

									$sql = "
										UPDATE tbl_os_campo_extra SET
											campos_adicionais = '{$campos_adicionais}'
										WHERE os = {$os}
									";
								}

								$res = pg_query($con, $sql);

								if (strlen(pg_last_error()) > 0) {
									throw new Exception(traduz("Erro ao gravar informações de nota fiscal de retorno da OS: %",null,null,[$os]));
								}
							}


                            if(in_array($login_fabrica, [167, 203])){
                            	$sqlReprovada = "SELECT reprovada FROM tbl_auditoria_os WHERE os = $os AND reprovada IS NOT NULL";
                            	$resReprovada = pg_query($con, $sqlReprovada);

                            	if(pg_num_rows($resReprovada) == 0){
                            		if(empty($data_conserto)){
										throw new Exception(utf8_encode(traduz("Informe a Data de conserto do produto na OS %",null,null,[$os])));
									}
                            	}
                        	}

                        	if (in_array($login_fabrica, array(169,170))) {
                        		$sql = "
                        			SELECT
                        				CASE WHEN COUNT(*) > 0 THEN 't' ELSE 'f' END
                        			FROM tbl_os_defeito_reclamado_constatado
                        			JOIN tbl_defeito_constatado USING(defeito_constatado,fabrica)
                        			WHERE fabrica = {$login_fabrica}
                        			AND os = {$os}
                        			AND lista_garantia = 'fora_garantia';
                    			";
                        		$res = pg_query($con,$sql);

                        		if (pg_num_rows($res) > 0) {
                        			$defeito_fora_garantia = pg_fetch_result($res, 0, 0);
                        		} else {
									$defeito_fora_garantia = null;
								}
                        	}

							if (file_exists("classes/Posvenda/Fabricas/_{$login_fabrica}/Os.php")) {
								include_once "classes/Posvenda/Fabricas/_{$login_fabrica}/Os.php";
								$className = '\\Posvenda\\Fabricas\\_' . $login_fabrica . '\\Os';

								if (in_array($login_fabrica, array(169, 170))) {
									$classOs = new $className($login_fabrica, $os, $con);
								} else {
									$classOs = new $className($login_fabrica, $os);
								}
							} else {
								$classOs = new \Posvenda\Os($login_fabrica, $os);
							}

							if($reparoNaFabrica){
								$osVinculada = $classOs->verificaOsVinculada($os);

								if($osVinculada == true){
									throw new \Exception(utf8_encode(traduz("OS % não pode ser fechada pois ainda não foi reparada na F&aacute;brica",null,null,[$os])));
								}

								if($login_fabrica == 156 AND $posto_interno){
									$classOs->enviaComunicadoOSVinculada($os);

                                    $sql = 'SELECT os FROM tbl_os WHERE fabrica = '.$login_fabrica.' AND os_numero ='.$os.' LIMIT 1';
                                    $res = pg_query($con, $sql);
                                    $sua_os = pg_fetch_result($res,0,'os');

                                    $sql = 'SELECT referencia as peca_referencia,
                                             descricao as peca_descricao,
                                             tbl_os_item.custo_peca
                                        FROM tbl_os_produto
                                        INNER JOIN tbl_os_item on tbl_os_item.os_produto = tbl_os_produto.os_produto
                                        INNER JOIN tbl_peca on tbl_peca.peca = tbl_os_item.peca
                                        WHERE tbl_os_produto.os = '. $os;

									$res = pg_query($con, $sql);

									$pecas = pg_fetch_all($res);

									$str = '<ul>';

									foreach ($pecas as $dados) {
										$dados['custo_peca'] = number_format($dados['custo_peca'], 2, ',','');
										$str .= '<li>' . $dados['peca_referencia'] . ' - ' . $dados['peca_descricao'] . ': R\$' . $dados['custo_peca'] . '</li>';
									}
									$str .= '</ul>';

									$msg = "'Reparo OS interno concluido, Segue peças ultilizadas. <br />
												Peças: <br />{$str}'";

									if(!empty($sua_os)) {
										insereInteracaoOs(array('os' => $sua_os, 'posto' => $login_posto, 'comentario'=>$msg, 'fabrica' => $login_fabrica));
									}
								}
							}

							$calcula_os = true;

							if (in_array($login_fabrica, array(169,170)) && $defeito_fora_garantia == 't') {
								$calcula_os = false;
							}

							if (in_array($login_fabrica, [177]) && !empty($lote_peca_nova)) {

								$sqlUpdateLote = "UPDATE tbl_os_item 
												SET  peca_serie = '{$lote_peca_nova}' 
												FROM tbl_os_produto 
												WHERE tbl_os_produto.os = {$os} 
												AND tbl_os_item.os_produto = tbl_os_produto.os_produto
												AND tbl_os_item.peca_serie_trocada IS NOT NULL";
								$resUpdateLote = pg_query($con, $sqlUpdateLote);
								
							}

							if ($login_fabrica == 158) {
								$pdo = $classOs->_model->getPDO();

								$sql = "
									UPDATE tbl_os_extra SET
										inicio_atendimento = '{$aux_inicio_atendimento}',
										termino_atendimento = '{$aux_fim_atendimento}'
									WHERE os = {$os};
								";
        						$query = $pdo->query($sql);

        						if (!$query) {
        							throw new Exception(traduz("Erro ao finalizar a OS %",null,null,[$os]));
        						}

        						$sql = "
									UPDATE tbl_os SET
										data_conserto = '{$aux_fim_atendimento}'
									WHERE fabrica = {$login_fabrica}
									AND os = {$os};
								";

        						$query = $pdo->query($sql);

        						if (!$query) {
        							throw new Exception(traduz("Erro ao finalizar a OS %",null,null,[$os]));
        						}

								$arr = json_decode($json_info_posto, true);
								$tp = array_keys($arr["tipo_posto"]);

								if ($arr["tipo_posto"][$tp[0]]["tecnico_proprio"] == true || $arr["tipo_posto"][$tp[0]]["posto_interno"] == true) {
									$calcula_os = false;
								}
							}

							if ($login_fabrica == 174) {
								$sql = "SELECT fora_garantia FROM tbl_os JOIN tbl_tipo_atendimento USING(tipo_atendimento,fabrica) WHERE fabrica = {$login_fabrica} AND os = {$os} AND fora_garantia IS TRUE;";
								$res = pg_query($con, $sql);

								if (pg_num_rows($res) > 0) {
									$calcula_os = false;
								}
							}
							
							if ($calcula_os == true && !in_array($login_fabrica, array(171,175))) {
								if ($login_fabrica == 145) {
									$tipo_os = $classOs->verificaOsRevisao($os);

									if ($tipo_os == true) {
										$classOs->calculaMaoDeObraRevisao($os);
									} else {
										$classOs->calculaOs();
									}
								} else {
									$classOs->calculaOs();
								}
							}
							
							$atendimento_callcenter = NULL;

                            if (in_array($login_fabrica, array(156,158,171))) {
                                $atendimento_callcenter = $classOs->verificaAtendimentoCallcenter($os);

                                if (($atendimento_callcenter && in_array($login_fabrica, array(156)))) {
                                    if (!temNF($os, 'bool')) {
                                        throw new Exception(traduz("Favor anexar a OS % assinada",null,null,[$os]));
                                    }
                                }

                              if (in_array($login_fabrica, array(158,167,171,176,203))) {

                                 $sql_anexo_fechamento = "SELECT obs FROM tbl_tdocs WHERE contexto = 'os'  AND referencia_id = $os AND fabrica = $login_fabrica";
                                 $res_anexo_fechamento = pg_query($con, $sql_anexo_fechamento);

                                 $tiposInseridos = [];
                                 while ($dadosAnexo = pg_fetch_object($res_anexo_fechamento)) {

                                 	$arrObsAnexo = json_decode($dadosAnexo->obs);

                                 	$tiposInseridos[] = $arrObsAnexo[0]->typeId;

                                 }

                                 if (!in_array('assinatura', $tiposInseridos)) {
                                    throw new Exception(traduz("Favor anexar a OS % assinada",null,null,[$os]));
                                 }     
                                   
                              }  	

                            }

                            if (in_array($login_fabrica, array(158))) {
                            	if (!$classOs->_model->verificaDefeitoConstatado($con)) {
                            		 throw new Exception(traduz("Por favor, informe o defeito constatado na OS %",null,null,[$os]));
                            	}
                            }

							if ($login_fabrica == 145 && $tipo_os == true) {
								$classOs->finalizaRevisao($con);
							} else {

								if ($login_fabrica != 171) {
									$classOs->finaliza($con);
								}

                                if ($atendimento_callcenter && $login_fabrica != 171) {
                                    $classOs->finalizaAtendimento($atendimento_callcenter);
								}

								if (in_array($login_fabrica, array(169,170))) {
									$sql = "
										SELECT o.sua_os
										FROM tbl_auditoria_os ao
										INNER JOIN tbl_os o ON o.os = ao.os AND o.fabrica = {$login_fabrica}
										WHERE ao.os = {$os}
										AND ao.liberada IS NULL
										AND ao.cancelada IS NULL
										AND ao.reprovada IS NULL
									";
									$res = pg_query($con, $sql);

									if (pg_num_rows($res) > 0) {
										$sua_os = pg_fetch_result($res, 0, "sua_os");
										$msg_auditoria .= traduz("OS em auditoria e aguardando aprovação da fábrica<br />");
									}

									$cookie_login = get_cookie_login($_COOKIE['sess']);
									if (array_key_exists('cook_admin', $cookie_login)) {
										$sqlCampoExtra = "SELECT os FROM tbl_os_campo_extra WHERE os = {$os} LIMIT 1";
										$resCampoExtra = pg_query($con, $sqlCampoExtra);
										$ver_os  = pg_fetch_result($resCampoExtra, 0, 0);
										if (empty($ver_os)) {
											$aux_admin["admin_finaliza_os"] = $cookie_login['cook_admin'];
											$aux_admin = json_encode($aux_admin);
	
											$sqlCampoExtraDados = "INSERT INTO tbl_os_campo_extra (os, fabrica, campos_adicionais) VALUES ({$os}, {$login_fabrica}, '{$aux_admin}');";
										} else {
											$aux_sql = "SELECT campos_adicionais FROM tbl_os_campo_extra WHERE os = {$os}";
											$aux_res = pg_query($con, $aux_sql);
	
											$aux_admin = pg_fetch_result($aux_res, 0, 0);
											$aux_admin = (array) json_decode($aux_admin);
											$aux_admin["admin_finaliza_os"] = $cookie_login['cook_admin'];
	
											$aux_admin = json_encode($aux_admin);
	
											$sqlCampoExtraDados = "UPDATE tbl_os_campo_extra SET campos_adicionais = '{$aux_admin}' WHERE fabrica = {$login_fabrica} AND os = {$os};";
										}

										pg_query($con, $sqlCampoExtraDados);
										if (strlen(pg_last_error()) > 0) {
											throw new Exception(traduz("Ocorreu um erro gravando dados de fechamento na OS"));
										}
									}
								}

                                if (in_array($login_fabrica, array(171))) {

									if (!$classOs->_model->verificaDefeitoConstatado($con)) {
										throw new Exception(traduz("A OS % está sem Defeito Constatado",null,null,[$os]));
									}

									$pedidoPendente = $classOs->_model->verificaPedidoPecasNaoFaturadasOS($con);

									if (!empty($pedidoPendente)) {
										throw new Exception($pedidoPendente);
									}

                                	$sql = "SELECT liberada
                                			FROM tbl_auditoria_os
                                			WHERE liberada IS NULL
                                			AND cancelada IS NULL
                                			AND reprovada IS NULL
                                			AND os = {$os}";
                                	$res = pg_query($con, $sql);

                                	if (pg_num_rows($res) == 0) {
										$sql = "UPDATE tbl_os SET status_checkpoint = 14 WHERE os = {$os} AND fabrica = {$login_fabrica}";
										pg_query($con, $sql);

										if (strlen(pg_last_error()) > 0)
											throw new Exception(traduz("Ocorreu um erro ao tentar finalizar a OS: %",null,null,[$os]));

										$sql = "SELECT auditoria_os
											FROM tbl_auditoria_os
											WHERE os = {$os}
											AND observacao = 'Auditoria de Fechamento'
											AND liberada IS NULL
											AND cancelada IS NULL
											AND reprovada IS NULL ";
										$res = pg_query($con, $sql);

										if (pg_num_rows($res) == 0) {
											$sqlx = "INSERT INTO tbl_auditoria_os(os,auditoria_status,observacao) VALUES({$os},6,'Auditoria de Fechamento')";
											$resx = pg_query($con, $sqlx);
											if (strlen(pg_last_error()) > 0) {
												throw new Exception(traduz("Ocorreu um erro ao tentar finalizar a OS: %",null,null,[$os]));
											} else {
												$msg_auditoria_grohe = traduz("OS(s) em auditoria de Fechamento e aguardando aprovação da fábrica para ser finalizada");

												$classOs->finalizaAtendimentoCallcenter($atendimento_callcenter);
											}
										} else {
											$msg_auditoria_grohe = traduz("OS(s) em auditoria de Fechamento e aguardando aprovação da fábrica para ser finalizada");
										}
									} else {
										throw new Exception(traduz("Existem auditorias não aprovadas pela fábrica para a OS: %",null,null,[$os]));
									}
                                }
							}

							if ((in_array($login_fabrica, array(160)) or $replica_einhell) && empty($erro)) {
								$enviaSms = new \Posvenda\Helpers\Os();

								$sqlOs = "SELECT tbl_os.consumidor_celular
										  FROM tbl_os
										  WHERE tbl_os.os = $os";
								$resOs = pg_query($con, $sqlOs);

								if (pg_num_rows($resOs) > 0) {
									$consumidor_celular = pg_fetch_result($resOs, 0, 'consumidor_celular');

									if (!empty($consumidor_celular)) {

										$msg_conserto_os = traduz("OS % PROD.ENTREGUE: Que nota vc atribui, entre 0 (insatisfeito) a 5(satisfeito),quanto ao atendimento geral prestado?responda de 0 a 5 (SMS sem custo)",null,null,[$os]);

										$enviaSms->comunicaConsumidor($consumidor_celular, $msg_conserto_os, $login_fabrica, $os);

									}

								}

							}

                            if ($login_fabrica == 158) {
								$cockpit = new \Posvenda\Cockpit($login_fabrica);

								$sqlOsMobile = "
						            SELECT os_mobile
						            FROM tbl_os_mobile
						            WHERE fabrica = {$login_fabrica}
						            AND os = {$os}
						            AND conferido IS NOT TRUE
						        ";
						        $qryOsMobile = pg_query($con, $sqlOsMobile);

						        if (pg_num_rows($qryOsMobile) > 0) {
						            throw new Exception(utf8_encode(traduz("Erro ao finalizar OS %, por possuir registros de integração Mobile x Web que ainda não foram conferidos. A Fábrica deverá corrigir a situação para prosseguir com a finalização da OS",null,null,[$os])));
						        }

								$id_externo = $cockpit->getOsIdExterno($os, $con);

                                if (strlen($tipo_posto) > 0) {
                                    $sql = "SELECT tecnico_proprio, posto_interno FROM tbl_tipo_posto WHERE tipo_posto = {$tipo_posto};";
                                    $res = pg_query($con, $sql);
                                }

                                if (pg_num_rows($res) > 0) {
                                    $tecnico_proprio = pg_fetch_result($res, 0, tecnico_proprio);
                                    $posto_interno = pg_fetch_result($res, 0, posto_interno);
                                }

                                if (empty($tecnico_proprio) && is_array($tipo_posto)) {
                                	$tecnico_proprio = $tipo_posto[key($tipo_posto)]['tecnico_proprio'];
                                }

                                if (empty($posto_interno) && is_array($tipo_posto)) {
                                	$posto_interno = $tipo_posto[key($tipo_posto)]['posto_interno'];
                                }


								if (!empty($id_externo) && $tecnico_proprio == 't') {
									#$finalizou_mobile = $cockpit->finalizaOsMobile($id_externo);

									#if (empty($finalizou_mobile) || $finalizou_mobile["error"]) {
										#throw new Exception(utf8_encode("Erro ao finalizar OS no dispostivo móvel"));
									#}
								}

                                $sql = "
                                	SELECT ta.fora_garantia, ta.tipo_atendimento
                                	FROM tbl_os os
                                	INNER JOIN tbl_tipo_atendimento ta ON ta.tipo_atendimento = os.tipo_atendimento AND ta.fabrica = {$login_fabrica}
                                	WHERE os.fabrica = {$login_fabrica}
                                	AND  os.os = {$os}";
                                $res = pg_query($con, $sql);

                                if (pg_num_rows($res) > 0) {
                                    $tipo_atendimento_fora_garantia = pg_fetch_result($res, 0, fora_garantia);
                                    $tipo_atendimento = pg_fetch_result($res, 0, tipo_atendimento);
                                }

								$oPedido            = new \Posvenda\Pedido($login_fabrica);
								$oExportaPedido     = new \Posvenda\Fabricas\_158\ExportaPedido($oPedido, $classOs, $login_fabrica);
								$oPedidoBonificacao = new \Posvenda\Fabricas\_158\PedidoBonificacao($oPedido);
								$pedido = $oExportaPedido->getPedido($os, null, $posto_interno);

								if (!empty($pedido)) {
									$garantia_antecipada = $pedido[0]['garantia_antecipada'];
									$pedido_em_garantia = $pedido[0]['pedido_em_garantia'];

									if ($garantia_antecipada != 't' && $pedido_em_garantia == 't' && $tipo_atendimento_fora_garantia == "t" && $tipo_atendimento <> 252) {
										$pedido = $oPedidoBonificacao->organizaEstoque($pedido, true);
										if ($oExportaPedido->pedidoIntegracao($pedido, "cobranca_kof", true) === false) {
											throw new Exception((traduz("Pedido não foi enviado para o SAP")));
										}
									}

									if ($posto_interno == 't') {
										$pedido = $oPedidoBonificacao->organizaEstoque($pedido, true);
										if ($oExportaPedido->pedidoIntegracao($pedido, "posto_interno", true) === false) {
											throw new Exception((traduz("OS não foi enviada para o SAP")));
										}
									}
								}

								if (!empty($_COOKIE["cook_admin"])) {
									$insInteracaoOsFinalizada = "
					            		INSERT INTO tbl_os_interacao
					            		(os, data, admin, comentario, fabrica)
					            		VALUES
					            		({$os}, CURRENT_TIMESTAMP, {$_COOKIE['cook_admin']}, 'OS finalizada', {$login_fabrica})
					            	";
								} else {
									$insInteracaoOsFinalizada = "
					            		INSERT INTO tbl_os_interacao
					            		(os, data, posto, comentario, fabrica)
					            		VALUES
					            		({$os}, CURRENT_TIMESTAMP, {$login_posto}, 'OS finalizada', {$login_fabrica})
					            	";
					            }
				            	$qryInsInteracaoOsFinalizada = pg_query($con, $insInteracaoOsFinalizada);
							}
						} catch(Exception $e) {
							if ($login_fabrica == 158) {
								$sqlRollback = "
									UPDATE tbl_os SET
										finalizada = null,
										data_fechamento = null
									WHERE fabrica = {$login_fabrica}
									AND os = {$os}
								";
								$resRollback = pg_query($con, $sqlRollback);
							}

							if ($login_fabrica == 120 or $login_fabrica == 201) {
								$erro = $e->getMessage();
							} else {
								if (preg_match("/\\u/", $e->getMessage())) {
									$erro = utf8_decode($e->getMessage());
								} else {
									$erro = $e->getMessage();
								}
							}

			            	$msg_erro .= $erro;
							$msg_ok = "";
			            	break;
			            }


						if(empty($erro)){
							$msg_ok = "ok";
							
							// Todas as OS finalizadas devem entrar em Auditoria de termo HD-6376083
							if (in_array($login_fabrica,[160]) && data_corte_termo($os)) {
								
								$sql_auditoria_termo = "INSERT INTO tbl_auditoria_os (os, auditoria_status, observacao) VALUES ($os, 6, 'Auditoria de Termo')";
								$res_auditoria_termo = pg_query($con, $sql_auditoria_termo);

								if (pg_last_error()) {
									$msg_ok = "";
									$msg_erro .= pg_errormessage($con)."<br />";
								}
							}			

							if(in_array($login_fabrica,[120,201]) and $ajax == true){
								echo "ok";
								exit;
							}
						} else {
							$msg_erro .= $erro."<br />";
							if(in_array($login_fabrica,[120,201]) and $ajax == true){
								echo $msg_erro;
								exit;
							}
						}
					}
					
				} else if (($ativo == 't' or $ativo_revenda=='t') and strlen(trim($erro))==0 and strlen(trim($msg_erro))==0) {

					$xserie_reoperado = "null";
					if($login_fabrica == 15){
						//7667 Gustavo 14/2/2008
						$xserie_reoperado = (strlen($serie_reoperado) == 0) ? "null" : "'".$serie_reoperado."'";

						$sql = "SELECT consumidor_revenda FROM tbl_os WHERE os = $os ";
						$res = pg_query($con,$sql);
						$con_rev = pg_fetch_result($res,0,consumidor_revenda);
						if($con_rev == 'R'){
							if($xnota_fiscal_saida == 'null'){
								$erro .= traduz("preencha.o.campo.nota.fiscal.de.saida", $con, $cook_idioma); /*"Preencha o campo Nota Fiscal de Saída.";*/
							}
							if($xdata_nf_saida == 'null'){
								$erro .= traduz("preencha.o.campo.nota.fiscal.de.saida", $con, $cook_idioma); /*" Preencha o campo Data da Nota Fiscal de Saída.";*/
							}
						}
					}

					$xserie= 'null';
					if($login_fabrica == 30 or $login_fabrica ==85){
                        $xserie= "'".$serie."'";
					}

					//hd 6701 - nao deixar o posto 019876-IVO CARDOSO fechar sem lancar NF
					if($login_fabrica == 6 AND $login_posto == 4260){
						if($xnota_fiscal_saida == 'null' or strlen($xnota_fiscal_saida) == 0){
							$erro .= traduz("preencha.o.campo.nota.fiscal.de.saida", $con, $cook_idioma); /*"Preencha o campo Nota Fiscal de Saída.";*/
						}
						if($xdata_nf_saida == 'null'){
							$erro .= traduz("preencha.o.campo.data.nota.fiscal.de.saida", $con, $cook_idioma); /*" Preencha o campo Data da Nota Fiscal de Saída.";*/
						}
					}

					//HD 281072: Como foi retirado da fn_finaliza_os_suggar a validação da OS reincidente, estou incluindo aqui
					if ($login_fabrica == 24 && strlen($erro) == 0) {
						$sql = "SELECT fn_valida_os_reincidente($os,$login_fabrica)";
						$res1 = @pg_query($con,$sql);
						$erro = pg_errormessage($con);
					}
					if (strlen ($erro) == 0) {
						// HD 27468
						if(!in_array($login_fabrica,array(1,3,6,7,11,158,172))){
							$res = pg_query ($con,"BEGIN TRANSACTION");
						}
						if(strlen(trim($serie)) > 0){
                            $upd_serie = (in_array($login_fabrica,array(30,85,137))) ? "serie = '$serie'," : "";
						}
						$erro = $msg_erro;
						if (strlen ($erro) == 0) {
							if ($login_fabrica==1){

								$data_conserto_bd = ($_POST["os"][$i]['data_conserto_'.$i]) ? $_POST["os"][$i]['data_conserto_'.$i] : '';

								if (empty($data_conserto_bd)){
									$sqlAAA = "SELECT tbl_os.sua_os,tbl_posto_fabrica.codigo_posto,tbl_os.data_conserto
									from tbl_os join tbl_posto_fabrica using (posto,fabrica)
									where tbl_os.os=$os
									and tbl_os.posto = $login_posto
									and tbl_os.fabrica = $login_fabrica";
									$res = pg_query($con,$sqlAAA);
									if (pg_num_rows($res)>0){
										$sua_os_err = pg_fetch_result($res, 0, 0);
										$codigo_posto_err = pg_fetch_result($res, 0, 1);
										$data_conserto_err = pg_fetch_result($res, 0, 2);
									}

									$sqlOsTroca = "SELECT os from tbl_os_troca where os=$os";
									$resOsTroca = pg_query($con,$sqlOsTroca);

									if (empty($data_conserto_err) and pg_num_rows($resOsTroca)==0){

										$erro = traduz("Informe a data de conserto da OS: ").$codigo_posto_err.$sua_os_err;
									}
								} else{
									$data_conserto_bd = formata_data($data_conserto_bd);
									$campo_data_conserto = " , data_conserto = '$data_conserto_bd' "; 
								}
								//black data de fechamento
								if (empty($erro)){
									$arraySMS[] = $os;
 									$sql = "UPDATE  tbl_os SET
									data_fechamento   = $xdata_fechamento $campo_data_conserto 
									WHERE   tbl_os.os         = $os";
								}

							} else {
								if(in_array($login_fabrica, array(11,172)) and strlen($erro) == 0){ #HD 96191
									$sql_nf = "SELECT sua_os,os FROM tbl_os
									WHERE fabrica = $login_fabrica
									AND posto = $login_posto
									AND nota_fiscal_saida = $xnota_fiscal_saida
									ORDER BY finalizada";

									$res_nf = pg_query($con,$sql_nf);
									if (pg_num_rows($res_nf) > 0){
										$sua_os_nf = " ";
										$nf_ja_utilizada = false;

										for ($x = 0 ; $x < pg_num_rows($res_nf); $x++) {
											$os_nf = trim(pg_fetch_result($res_nf,$x,'os'));
											$sua_os_nf = trim(pg_fetch_result($res_nf,$x,'sua_os'));

											if(!in_array($os_nf,$array_os)){
												$sua_os_utilizadas .= $sua_os_nf.'<br />';
												$nf_ja_utilizada = true;
											}

										}

										if($nf_ja_utilizada == true){
											$erro .= traduz("Nota Fiscal já utilizada para devolução da(s) OS<br> ").$sua_os_utilizadas;
										}
									}
								}

								#HD 150828
								$os_troca = false;
								if ( in_array($login_fabrica, array(11,172)) ){
									$sql_troca = "SELECT os FROM tbl_os_troca WHERE os = $os";
									$res_troca = pg_query($con,$sql_troca);
									if (pg_num_rows($res_troca) > 0){
										$os_troca = true;
									}

									$sql_lenoxx_conserto = "SELECT data_conserto from tbl_os where os=$os";
									$res_lenoxx_conserto = pg_query($con,$sql_lenoxx_conserto);

									if (pg_num_rows($res_lenoxx_conserto)==0){
										$upd_conserto_lenoxx = " , data_conserto = current_timestamp";
									}else{
										$upd_conserto_lenoxx =  '';
									}

								}

								if($login_fabrica == 137 && $posto_interno) {
									$defeito_constatado = (empty($defeito_constatado)) ? "null" : $defeito_constatado;
								}else{
									$defeito_constatado = "defeito_constatado";
								}

								if ($login_fabrica == 165 && $posto_interno == true) {
									$objLog = new AuditorLog();
									$objLog->retornaDadosSelect("SELECT os, data_fechamento FROM tbl_os WHERE os = {$os}");
								}

                                if ($login_fabrica == 85) {
                                    $updDataHoraFechamento = "
                                        data_digitacao_fechamento = $xdata_hora_fechamento,
                                    ";
                                }

								if($login_fabrica == 50){//HD-3321672  estava tirando validação de NS
									$sql = "UPDATE  tbl_os SET
									data_fechamento   = $xdata_fechamento  ,
									$upd_serie
									nota_fiscal_saida = $xnota_fiscal_saida,
									defeito_constatado = $defeito_constatado,
									data_nf_saida     = $xdata_nf_saida
									WHERE   tbl_os.os         = $os";
								} else {
									$sql = "UPDATE  tbl_os SET
									data_fechamento   = $xdata_fechamento  ,
									$upd_serie
									$upd_conserto_lenoxx
									$updDataHoraFechamento
									serie_reoperado   = $xserie_reoperado   ,
									nota_fiscal_saida = $xnota_fiscal_saida,
									defeito_constatado = $defeito_constatado,
									data_nf_saida     = $xdata_nf_saida
									WHERE   tbl_os.os         = $os";
								}

								$sql_conserto = "SELECT data_conserto from tbl_os where os = $os";

								$res_conserto = pg_query($con,$sql_conserto);

								$data_conserto = pg_fetch_result($res_conserto,0,data_conserto);

								#HD 163061 - OS de troca
								if ( strlen($data_conserto)==0 AND $os_troca == false AND !in_array($login_fabrica,array(30,52,141,144,165))) {
									$data_conserto = explode("'",$xdata_fechamento);
									$data_conserto = $data_conserto[1];
									$hora_conserto = date('H:i:s');
									$sql_conserto = "UPDATE tbl_os set data_conserto = '$data_conserto $hora_conserto' where os = $os";
									$res_conserto = pg_query($con,$sql_conserto);

									if ($login_fabrica == 104) {
                                        $sql_sms = "SELECT campos_adicionais FROM tbl_os_campo_extra WHERE os = $os";
                                        $qry_sms = pg_query($con, $sql_sms);
                                        $campos_adicionais = array();
                                        $insert_campo_extra = false;

                                        if (pg_num_rows($qry_sms) == 0) {
                                            $insert_campo_extra = true;
                                        } else {
                                            $campos_adicionais = json_decode(pg_fetch_result($qry_sms, 0, 'campos_adicionais'), true);
                                        }

                                        if (!array_key_exists("enviou_msg_consertado", $campos_adicionais) or $campos_adicionais["enviou_msg_consertado"] <> "t") {
                                            $helper = new \Posvenda\Helpers\Os();

                                            $sql_contatos_consumidor = "
                                                SELECT consumidor_email,
                                                    consumidor_celular,
                                                    referencia,
                                                    descricao,
                                                    tbl_posto.nome
                                                FROM tbl_os
                                                JOIN tbl_produto USING(produto)
                                                JOIN tbl_posto USING(posto)
                                                WHERE os = $os";
                                            $qry_contatos_consumidor = pg_query($con, $sql_contatos_consumidor);

                                            $consumidor_email = pg_fetch_result($qry_contatos_consumidor, 0, 'consumidor_email');
                                            $consumidor_celular = pg_fetch_result($qry_contatos_consumidor, 0, 'consumidor_celular');
                                            $produto_os = pg_fetch_result($qry_contatos_consumidor, 0, 'referencia') . ' - ' . pg_fetch_result($qry_contatos_consumidor, 0, 'descricao');
                                            $posto_os = pg_fetch_result($qry_contatos_consumidor, 0, 'nome');

                                            $msg_conserto_os = traduz("Produto Vonder - OS %. Informamos que seu produto % que está em nosso posto % já está consertado. Solicitamos sua presença para retirada com brevidade.",null,null,[$os,$produto_os,$posto_os]);

                                            if (!empty($consumidor_email)) {
                                                $helper->comunicaConsumidor($consumidor_email, $msg_conserto_os);
                                            }

                                            if (!empty($consumidor_celular)) {
                                                $helper->comunicaConsumidor($consumidor_celular, $msg_conserto_os, $login_fabrica, $os);
                                            }

                                            $campos_adicionais["enviou_msg_consertado"] = "t";
                                            $json_campos_adicionais = json_encode($campos_adicionais);

                                            if (true === $insert_campo_extra) {
                                                $sql_msg_consertado = "
                                                    INSERT INTO tbl_os_campo_extra (os, fabrica, campos_adicionais)
                                                        VALUES ({$os}, {$login_fabrica}, '{$json_campos_adicionais}')";
                                            } else {
                                                $sql_msg_consertado = "
                                                    UPDATE tbl_os_campo_extra SET
                                                        campos_adicionais = '{$json_campos_adicionais}'
                                                    WHERE os = $os";
                                            }

                                            $qry_msg_consertado = pg_query($con, $sql_msg_consertado);
                                        }
                                    }
								}

							}

							// echo nl2br($sql);exit;
							$res  = @pg_query ($con,$sql);

							$erro .= pg_errormessage ($con);

							if($login_fabrica==3){
								$sql = "UPDATE  tbl_os_extra SET
								obs_fechamento   = $xmotivo_fechamento
								WHERE   tbl_os_extra.os         = $os";
								$res  = @pg_query ($con,$sql);
								$erro .= pg_errormessage ($con);
							}

							if($login_fabrica == 30){
                                $sqlInteracao = "
                                    INSERT INTO tbl_os_interacao (
                                        os,
                                        data,
                                        comentario,
                                        interno,
                                        posto
                                    ) VALUES (
                                        $os,
                                        CURRENT_TIMESTAMP,
                                        'A OS foi finalizada na data $data_fechamento',
                                        TRUE,
                                        $login_posto
                                    )
                                ";
                                $resInteracao = pg_query($con,$sqlInteracao);

                                if (!empty($login_unico)) {
		                            $sql_lu = "UPDATE tbl_os_extra SET obs_fechamento = '$login_unico_nome' WHERE os = $os ;";
		                            $res_lu = pg_query($con,$sql_lu);
		                        }else{
		                            $sql_lu = "UPDATE tbl_os_extra SET obs_fechamento = '$login_codigo_posto' WHERE os = $os ;";
		                            $res_lu = pg_query($con,$sql_lu);
		                        }
							}

							if($login_fabrica == 50){
					            $sql = "SELECT os from tbl_os_extra WHERE os = $os and i_fabrica = $login_fabrica";
					            $res = pg_query($con, $sql);
					            if(pg_num_rows($res)>0){
					                $sql_ce = "UPDATE tbl_os_extra SET obs_fechamento = '$login_codigo_posto' WHERE os = $os ;";
					            }else{
					                $sql_ce = "INSERT INTO tbl_os_extra (os, obs_fechamento) VALUES ($os, '$login_codigo_posto')";
					            }
					            $res_ce = pg_query ($con,$sql_ce);
					            $msg_erro .= pg_errormessage($con);
					        }


					        if ($login_fabrica == 165 && $posto_interno == true) {
								//auditorlog de data fechamento
								if (!empty($objLog)) {
									$objLog->retornaDadosSelect()->enviarLog('update', "tbl_os", $login_fabrica."*".$os);
								}
							}

							if ($login_fabrica == 1) {
                                /*
                                 * - Verifica se o Posto faz o cálculo
                                 * da Taxa Administrativa
                                 *
                                 * ( funcoes.php )
                                 */

                                calculaTaxaAdministrativa($con,$login_fabrica,$login_posto,$os);
							}
						}

						//HD 204146: Fechamento automático de OS
						if ($login_fabrica == 3) {
							$sql = "UPDATE tbl_os SET sinalizador=20 WHERE os=$os AND sinalizador=18";
							@$res_sinalizador = pg_query($con, $sql);
							$erro = pg_errormessage($con);

							if(intval($tecnico) != 0){
								$sql = "UPDATE tbl_os SET tecnico = {$tecnico} WHERE os=$os";
								@$res_tecnico = pg_query($con, $sql);
								$erro = pg_errormessage($con);
							}

							if ($erro) {
								$erro = traduz("Erro no sistema, contate o HelpDesk");
							}
						}
						if($login_fabrica == 85 && empty($erro)){

							$validaFechamento = new \Posvenda\Validacao\_85\FechamentoOs($os, $con);

							if (false === $validaFechamento->validaFechamento()) {
								$erro = $validaFechamento->getErros();
							}
						}

						if(in_array($login_fabrica, array(11,172))){
							$aux_sql = "
				                SELECT DISTINCT(tbl_os_item.pedido)
				                FROM tbl_os_item
				                JOIN tbl_os_produto ON tbl_os_produto.os_produto = tbl_os_item.os_produto
								WHERE tbl_os_produto.os = $os
								AND tbl_os_item.pedido notnull
				            ";
				            $aux_res      = pg_query($con, $aux_sql);
				            $aux_total    = pg_num_rows($aux_res);
				            $pedidos      = array();
				            $pedido_itens = array();

				            for ($x = 0; $x < $aux_total; $x++) {
				                $temp_pedido = pg_fetch_result($aux_res, $x, 'pedido');
				                if (!in_array($pedidos, $temp_pedido)) {
				                    $pedidos[] = $temp_pedido;
				                }
				                unset($temp_pedido);
				            }

				            if (count($pedidos) > 0) {
				                foreach ($pedidos as $pedido) {
				                    $aux_sql   = "SELECT pedido_item FROM tbl_pedido_item WHERE pedido = $pedido";
				                    $aux_res   = pg_query($con, $aux_sql);
				                    $aux_total = pg_num_rows($aux_res);

				                    for ($x = 0; $x < $aux_total; $x++) {
				                        $temp_pedido_item = pg_fetch_result($aux_res, $x, 'pedido_item');
				                        if (!in_array($pedido_itens, $temp_pedido_item)) {
				                            $pedido_itens[] = $temp_pedido_item;
				                        }
				                        unset($temp_pedido_item);
				                    }
				                }

				                if (count($pedido_itens) > 0) {
				                    foreach ($pedido_itens as $pedido_item) {
				                        $aux_sql = "
				                            SELECT pedido, qtde, qtde_faturada, qtde_cancelada
				                            FROM tbl_pedido_item
				                            WHERE pedido_item = $pedido_item
				                            LIMIT 1
				                        ";
				                        $aux_res        = pg_query($con, $aux_sql);
				                        $pedido         = (int) pg_fetch_result($aux_res, 0, 'pedido');
				                        $qtde           = (int) pg_fetch_result($aux_res, 0, 'qtde');
				                        $qtde_cancelada = (int) pg_fetch_result($aux_res, 0, 'qtde_cancelada');
				                        $qtde_faturada  = (int) pg_fetch_result($aux_res, 0, 'qtde_faturada');

				                        if($qtde_cancelada == 0 && $qtde_faturada == 0) {
				                            $sql_cancel = "
				                                UPDATE tbl_pedido_item SET
				                                qtde_cancelada = $qtde
				                                WHERE pedido_item = $pedido_item;

				                                SELECT fn_atualiza_status_pedido(fabrica, pedido) from tbl_pedido where pedido = $pedido;
				                            ";
				                            $res_cancel = pg_query($con, $sql_cancel);

				                            if (pg_num_rows($res_cancel) <= 0) {
				                                $msg_erro = traduz("Erro ao excluir o pedido pendente da OS");
				                            }
				                        } else {
				                        }
				                    }
				                }
				            }
				            unset($aux_sql, $aux_res, $aux_total, $pedidos, $pedido_itens);

							$sql_fabrica = "SELECT fabrica FROM tbl_os WHERE os = {$os}";
							$res_fabrica = pg_query($con, $sql_fabrica);

							$fabrica_os = pg_fetch_result($res_fabrica, 0, "fabrica");

							$sql = "SELECT fn_finaliza_os($os, $fabrica_os)";
							$res = @pg_query ($con,$sql);

                            if (pg_last_error($con)){
                                $erro .= pg_errormessage($con)."<br />";
                                $msg_erro .= pg_errormessage($con)."<br />";
                                $res = @pg_query ($con,"ROLLBACK TRANSACTION");
                                break;
                            }
						} else {
                            if (empty($erro)) {
                            	if ($login_fabrica == 1 && $usa_estoque_posto == 'sim' && $osPedidoFaturado != "sim") {

									$sql_1 = "UPDATE tbl_os SET conferido_saida = TRUE WHERE os = $os AND fabrica = $login_fabrica";
									$res_1 = pg_query($con, $sql_1);
									if (pg_last_error()) {
	                                    $erro .= pg_last_error()."<br />";
	                                    $msg_erro .= pg_last_error()."<br />";
	                                }

	                                $sql_2 = "	    SELECT  tbl_os_item.peca, 
															tbl_os_item.qtde,
															tbl_os_item.os_item
													FROM tbl_os_produto
													JOIN tbl_os_item ON tbl_os_produto.os_produto = tbl_os_item.os_produto
													JOIN tbl_servico_realizado ON tbl_os_item.servico_realizado = tbl_servico_realizado.servico_realizado
													LEFT JOIN tbl_pedido_item ON tbl_os_item.pedido_item = tbl_pedido_item.pedido_item
													LEFT JOIN tbl_os_troca ON tbl_os_troca.os = tbl_os_produto.os
													WHERE tbl_os_item.fabrica_i = $login_fabrica
													AND tbl_os_produto.os = $os
													AND tbl_servico_realizado.gera_pedido
													AND tbl_os_troca.os isnull
													AND (tbl_os_item.pedido IS NULL OR (tbl_os_item.pedido_item = tbl_pedido_item.pedido_item AND tbl_pedido_item.qtde_faturada = 0 AND tbl_pedido_item.qtde_cancelada = 0))";
									$res_2 = pg_query($con, $sql_2);
									if (pg_num_rows($res_2) > 0) {
										$pecas2 = pg_fetch_all($res_2);

										foreach ($pecas2 as $key => $value) {

											$sql_3 = "INSERT INTO tbl_estoque_posto_movimento (fabrica, 
																								posto, 
																								os, 
																								peca, 
																								data, 
																								qtde_saida,
																								os_item
																							) VALUES (
																								$login_fabrica, 
																								$login_posto, 
																								$os, 
																								{$value['peca']},
																								now(),
																								{$value['qtde']},
																								{$value['os_item']}
																							)";
											$res_3 = pg_query($con, $sql_3);
											if (pg_last_error()) {
			                                    $erro .= pg_last_error()."<br />";
			                                    $msg_erro .= pg_last_error()."<br />";
			                                }

											$sql_4 = "UPDATE tbl_os_item SET peca_reposicao_estoque = TRUE WHERE os_item = {$value['os_item']}";
											$res_4 = pg_query($con, $sql_4);
											if (pg_last_error()) {
			                                    $erro .= pg_last_error()."<br />";
			                                    $msg_erro .= pg_last_error()."<br />";
			                                }

			                                $sql_5 = "UPDATE tbl_estoque_posto SET qtde = qtde - {$value['qtde']} WHERE peca = {$value['peca']} AND posto = {$login_posto} AND fabrica = {$login_fabrica}";
			                                $res_5 = pg_query($con, $sql_5);

											if(pg_affected_rows($res_5) == 0) {
												$erro = "Posto sem estoque para finalizar OS "; 
												$msg_erro  = "Posto sem estoque para finalizar OS "; 
											}
			                                if (pg_last_error()) {
			                                    $erro .= pg_last_error()."<br />";
			                                    $msg_erro .= pg_last_error()."<br />";
			                                }

										}
									}
                            	}

                                $sql = "SELECT fn_finaliza_os($os, $login_fabrica)";
                                $res = @pg_query ($con,$sql);

                                if (pg_last_error($con) || !empty($erro)){
                                    $erro .= pg_errormessage($con)."<br />";
                                    $msg_erro .= pg_errormessage($con)."<br />";
                                    $res = @pg_query ($con,"ROLLBACK TRANSACTION");
                                    break;
                                } elseif ($login_fabrica == 123 && data_corte_termo($os)) {
                                	
                                	// Todas as OS finalizadas devem entrar em Auditoria de termo HD-6376083
                                	$sql_auditoria_termo = "INSERT INTO tbl_auditoria_os (os, auditoria_status, observacao) VALUES ($os, 6, 'Auditoria de Termo')";
									$res_auditoria_termo = pg_query($con, $sql_auditoria_termo);
                                	
                                	if (pg_last_error($con)){
	                                    $erro .= pg_errormessage($con)."<br />";
	                                    $msg_erro .= pg_errormessage($con)."<br />";
	                                    $res = @pg_query ($con,"ROLLBACK TRANSACTION");
	                                    break;
                                	}
                                }
                            }
						}


						if($login_fabrica == 50 AND strlen(trim($msg_erro))==0){
				            $sql_ver_peca_obrigatoria = "SELECT tbl_os.os, tbl_faturamento_item.pedido, tbl_faturamento_item.faturamento_item
				                    FROM tbl_os
				                    INNER JOIN tbl_os_produto ON tbl_os_produto.os = tbl_os.os AND tbl_os.fabrica = $login_fabrica
				                    INNER JOIN tbl_os_item ON tbl_os_item.os_produto = tbl_os_produto.os_produto
				                    INNER JOIN tbl_servico_realizado ON tbl_servico_realizado.servico_realizado = tbl_os_item.servico_realizado
				                    LEFT JOIN tbl_faturamento_item ON (tbl_faturamento_item.pedido = tbl_os_item.pedido OR tbl_faturamento_item.os_item = tbl_os_item.os_item ) AND tbl_os_item.peca = tbl_faturamento_item.peca
				                    WHERE tbl_os.os = $os
				                    AND tbl_os.fabrica = $login_fabrica
				                    /*AND tbl_os_item.pedido is not null*/
				                    AND tbl_os_item.peca_obrigatoria = 't'
				                    AND tbl_servico_realizado.troca_de_peca is true ";
				            $res_ver_peca_obrigatoria = pg_query($con, $sql_ver_peca_obrigatoria);
				            if(pg_num_rows($res_ver_peca_obrigatoria) > 0){
				                $sql = "SELECT os FROM tbl_os_campo_extra where os = $os AND fabrica = $login_fabrica";
				                $res = pg_query($con, $sql);
				                if(pg_num_rows($res)==0){

				                	$campos_adicionais['data'] = date("Y-m-d");
				                	$campos_adicionais['obs'] = utf8_encode('Pendente de devolução de peças');
				                	$campos_adicionais = json_encode($campos_adicionais);
				                    $sql_campo_extra = "INSERT INTO tbl_os_campo_extra (os, fabrica, os_bloqueada, campos_adicionais) VALUES ($os, $login_fabrica, true, '$campos_adicionais' )";
				                }else{

				                	$campos_adicionais = json_decode($campos_adicionais, true);
				                	$campos_adicionais['data'] = date("Y-m-d H:i:s");
				                	$campos_adicionais['obs'] = utf8_encode('Pendente de devolução de peças');
				                	$campos_adicionais = json_encode($campos_adicionais);

				                    $sql_campo_extra = "UPDATE tbl_os_campo_extra SET os_bloqueada = true , campos_adicionais = '$campos_adicionais' WHERE os = $os AND fabrica = $login_fabrica ";
				                }
				                $res_campo_extra = pg_query($con, $sql_campo_extra);
				                include 'grava_faturamento_peca_estoque_colormaq.php';
				            }
				        }

						if (strlen ($erro) == 0) {
							// estava aqui fn_finaliza_os

							if($login_fabrica == 15){

								$sql_os_hd_chamado = "SELECT atendente, hd_chamado FROM tbl_hd_chamado_extra JOIN tbl_hd_chamado USING(hd_chamado) WHERE os = {$os}";
								$res_os_hd_chamado = pg_query($con, $sql_os_hd_chamado);

								if(pg_num_rows($res_os_hd_chamado) > 0){

									$admin = pg_fetch_result($res_os_hd_chamado, 0, "atendente");
									$hd_chamado = pg_fetch_result($res_os_hd_chamado, 0, "hd_chamado");

									$sql_email = "SELECT email, nome_completo FROM tbl_admin WHERE admin = $admin";
									$res_email = pg_query($con, $sql_email);

									if(pg_num_rows($res_email) > 0){

										$nome = pg_fetch_result($res_email, 0, "nome_completo");
										$email = pg_fetch_result($res_email, 0, "email");

										$email_consumidor->adicionaLog(array("titulo" => "OS Finalizada: ".$os));

										$mensagem_email = "
										Olá {$nome}, informamos que a Ordem de Serviço nº {$os} foi finalizada pelo posto,
										por favor finalizar o chamado {$hd_chamado} no Call-Center. <br /> <br />
										Email automático, favor não respoder.
										";

										$email_consumidor->adicionaLog($mensagem_email);

										$email_consumidor->adicionaTituloEmail("Finalização da OS Latina - ".$os);
										$email_consumidor->adicionaEmail($email);
										$email_consumidor->enviaEmails();
										$email_consumidor->limpaDados();

									}

								}

							}

							if($login_fabrica == 104 and 1==2){

								$sql_celular = "SELECT consumidor_celular, consumidor_fone, sua_os, posto FROM tbl_os WHERE os = $os";
								$res_celular = pg_query($con, $sql_celular);

								if(pg_num_rows($res_celular) > 0){

									$consumidor_celular = pg_fetch_result($res_celular, 0, 'consumidor_celular');
									$consumidor_fone    = pg_fetch_result($res_celular, 0, 'consumidor_fone');
									$sua_os             = pg_fetch_result($res_celular, 0, 'sua_os');
									$posto              = pg_fetch_result($res_celular, 0, 'posto');

									$sua_os = (strlen($sua_os) > 0) ? $sua_os : $os;

									$destinatario = (strlen($consumidor_celular) > 0) ? $consumidor_celular : $consumidor_fone;

									if(strlen($destinatario) > 0){

										$sql_posto  = "SELECT nome FROM tbl_posto WHERE posto = {$posto}";
										$res_posto  = pg_query($con, $sql_posto);
										$nome_posto = pg_fetch_result($res_posto, 0, "nome");

										$sql_produto = "SELECT descricao FROM tbl_produto WHERE produto IN (SELECT produto FROM tbl_os_produto WHERE os = {$os})";
										$res_produto = pg_query($con, $sql_produto);

										$desc_produto = pg_fetch_result($res_produto, 0, "descricao");

										$msg = "Prezado(a) Consumidor(a) OVD, Informamos que seu produto {$desc_produto} foi consertado pelo posto {$nome_posto} através da ordem de serviço (nº {$sua_os}) e encontra-se disponível para retirada. Para maiores informações entrar em contato com nosso suporte OVD.";

										$sms = new SMS();

										$qtde_sms = ($_serverEnvironment == 'development') ? 5 : 500;

										if($sms->obterSaldo() <= $qtde_sms){

											$sms->gravarSMSPendente($os);

										}else{

											$enviar = $sms->enviarMensagem($destinatario, $sua_os, $data_fechamento_sms, $msg);

											if($enviar == false){
												$sms->gravarSMSPendente($os);
											}
										}
									}
								}
							}
						}

						if($login_fabrica == 1 AND strlen($erro) == 0){ //HD-3236684

							$sqlOSitem = "SELECT peca_reposicao_estoque
										FROM tbl_os_item
                                       	JOIN tbl_os_produto ON tbl_os_produto.os_produto = tbl_os_item.os_produto
                                        WHERE tbl_os_produto.os = $os
                                        AND peca_reposicao_estoque IS TRUE ";
                            $resOSitem = pg_query($con, $sqlOSitem);
                            $erro = pg_errormessage($con);
							if(strlen($erro) == 0){
								if(pg_num_rows($resOSitem) > 0){
									$sql = "SELECT fn_estoque_os($os, $login_fabrica)";
									$res = @pg_query ($con,$sql);
									$erro = pg_errormessage($con);
								}
							}
						}

						if (strlen ($erro) == 0 and (in_array($login_fabrica,[24,120,201]))) {
							$sql = "SELECT fn_estoque_os($os, $login_fabrica)";
							$res = @pg_query ($con,$sql);
							$erro = pg_errormessage($con);
						}

						//HD 11082 17347
						if((strlen($erro) == 0 && in_array($login_fabrica, array(11,172)) && $login_posto==14301)){
							$observacao=$_POST['observacao_'.$i];
							// echo $sql="INSERT INTO tbl_os_interacao (os,comentario) values ($os,'$observacao')";
							$res=pg_query($con,$sql);

							$sqlm="SELECT tbl_os.sua_os          ,
							tbl_os.consumidor_email,
							tbl_os.serie           ,
							tbl_posto.nome         ,
							tbl_produto.descricao  ,
							to_char(tbl_os.data_fechamento,'DD/MM/YYYY') as data_fechamento
							from tbl_os
							join tbl_produto using(produto)
							join tbl_posto on tbl_os.posto = tbl_posto.posto
							where os=$os
							AND tbl_os.fabrica = $login_fabrica";
							$resm=pg_query($con,$sqlm);
							$msg_erro .= pg_errormessage($con);

							$sua_osm           = trim(pg_fetch_result($resm,0,sua_os));
							$consumidor_emailm = trim(pg_fetch_result($resm,0,consumidor_email));
							$seriem            = trim(pg_fetch_result($resm,0,serie));
							$data_fechamentom  = trim(pg_fetch_result($resm,0,data_fechamento));
							$nomem             = trim(pg_fetch_result($resm,0,nome));
							$descricaom        = trim(pg_fetch_result($resm,0,descricao));

							if(strlen($consumidor_emailm) > 0){
								$nome         = "TELECONTROL";
								$email_from   = "helpdesk@telecontrol.com.br";
								$assunto      = "ORDEM DE SERVIÇO FECHADA";
								$destinatario = $consumidor_emailm;
								$boundary = "XYZ-" . date("dmYis") . "-ZYX";
								$mensagem = "A ORDEM DE SERVIÇO $sua_osm REFERENTE AO PRODUTO $descricaom COM NÚMERO DE SÉRIE $seriem FOI FECHADA PELO POSTO $nomem NO DIA $data_fechamento.";
								$mensagem .= "<br>Observação do Posto: $observacao";
								$body_top = "--Message-Boundary\n";
								$body_top .= "Content-type: text/html; charset=iso-8859-1\n";
								$body_top .= "Content-transfer-encoding: 7BIT\n";
								$body_top .= "Content-description: Mail message body\n\n";
								@mail($destinatario, utf8_encode($assunto), utf8_encode($mensagem), "From: ".$email_from." \n $body_top ");
							}
						}

						if (strlen ($erro) > 0) {
							//echo $erro;
							// HD 27468
							if($login_fabrica <> 1 and $login_fabrica <> 7){
								array_push($linha_erro,$os);
								if( !in_array($login_fabrica, array(11,172)) ){
									$res = @pg_query ($con,"ROLLBACK TRANSACTION");
								}
								/* HD 175123 */
								$msg_erro = $erro;
								$msg_ok	 = "";
								$erro = '';
							}

							if($login_fabrica == 1) {
								array_push($linha_erro,$os);
								$msg_erro = $erro;
								$msg_ok	 = "";
								$erro = '';
								break;
							}
						}else{
							if($login_fabrica == 96){ //HD 399700

								$sql = "SELECT tbl_cliente_admin.email
								FROM tbl_os
								JOIN tbl_hd_chamado USING(hd_chamado)
								JOIN tbl_cliente_admin ON tbl_hd_chamado.cliente_admin = tbl_cliente_admin.cliente_admin
								WHERE tbl_os.os = $os";
								$res = pg_query($con,$sql);
								if(pg_num_rows($res) > 0){
									$email = pg_result($res,0,0);
								}

								if(!empty($email)){
									$nome         = "TELECONTROL";
									$email_from   = "helpdesk@telecontrol.com.br";
									$assunto      = "ORDEM DE SERVIÇO FECHADA";
									$destinatario = $email;
									$boundary = "XYZ-" . date("dmYis") . "-ZYX";
									$mensagem = "Prezado,<br /> <br />A Ordem De Serviço {$os} foi fechada pelo Posto Autorizado no dia {$data_fechamento}.<br /><br />--<br />Att,<br />Suporte Telecontrol<br /><b>Essa é uma mensagem automática, não responda este e-mail.</b>";

									$body_top = "--Message-Boundary\n";
									$body_top .= "Content-type: text/html; charset=iso-8859-1\n";
									$body_top .= "Content-transfer-encoding: 7BIT\n";
									$body_top .= "Content-description: Mail message body\n\n";
									@mail($destinatario, utf8_encode($assunto), utf8_encode($mensagem), "From: ".$email_from." \n $body_top ");
								}
							}

							// HD 27468
							if(!in_array($login_fabrica, array(1,11,7,172)) ){
								$res = @pg_query ($con,"COMMIT TRANSACTION");

								if(in_array($login_fabrica, array(94,101))) {
							        $excecaoMO = new \Posvenda\ExcecaoMobra($os,$login_fabrica);
							        $excecaoMO->calculaExcecaoMobra();
								}

							}
							$data_fechamento   = "";
							$serie             = "";
							$serie_reoperado   = "";
							$nota_fiscal_saida = "";
							$data_nf_saida     = "";
							$msg_ok = 1;
						}

						if(empty($msg_erro)){

							if($login_fabrica == 140){

								$sql_email = "SELECT
								tbl_os.consumidor_nome,
								tbl_os.consumidor_email,
								tbl_produto.referencia,
								tbl_produto.descricao
								FROM tbl_os
								JOIN tbl_produto ON tbl_produto.produto = tbl_os.produto AND tbl_produto.fabrica_i = {$login_fabrica}
								WHERE tbl_os.os = {$os}
								AND tbl_os.fabrica = {$login_fabrica}";
								$res_email = pg_query($con, $sql_email);

								$nome               = pg_fetch_result($res_email, 0, 'consumidor_nome');
								$consumidor_email   = pg_fetch_result($res_email, 0, 'consumidor_email');
								$referencia   		= pg_fetch_result($res_email, 0, 'referencia');
								$descricao   		= pg_fetch_result($res_email, 0, 'descricao');

								if(!empty($consumidor_email)){

									$email_consumidor->adicionaLog(array("titulo" => "Produto Consertado | Lavor - OS: ".$os));

									$mensagem_email = "
									Sua Ordem de Serviço nº {$os} foi finalizada.
									<br /> <br />
									O produto {$referencia} - {$descricao} está à disposição caso não tenha sido retirado.
									<br /> <br />
									Favor apresentar a Ordem de Serviço nº {$os} para a retirada.
									<br /> <br />
									Serviço Lavor de Atendimento.
									";

									$email_consumidor->adicionaLog($mensagem_email);

									$email_consumidor->adicionaTituloEmail("Finalização da OS Lavor - ".$os);
									$email_consumidor->adicionaEmail($consumidor_email);
									$email_consumidor->enviaEmails();
									$email_consumidor->limpaDados();

								}

							}

						}

					} else{
						$msg_erro .= $erro;
					}

				}else{
					$msg_erro .= $erro;
				}

				if (empty($msg_erro) AND in_array($login_fabrica, $envia_pesquisa_finaliza_os) AND $ativo == "t") {
					$sql_pesquisa = "SELECT pesquisa , categoria, texto_ajuda
                        				FROM tbl_pesquisa
                        				WHERE fabrica = {$login_fabrica}
                            				AND ativo IS TRUE
                            				AND categoria in ('ordem_de_servico_email')
                            				AND ativo IS TRUE";
					$res_pesquisa = pg_query($con, $sql_pesquisa);

					if (pg_num_rows($res_pesquisa) > 0) {
						$texto_ajuda = pg_fetch_result($res_pesquisa, 0, texto_ajuda);

						$sql_envia = "SELECT  tbl_os.consumidor_email,
										tbl_os.consumidor_nome,
										tbl_produto.descricao,
										tbl_produto.referencia
									FROM tbl_os
									JOIN tbl_produto ON tbl_produto.produto = tbl_os.produto
									AND tbl_produto.fabrica_i = $login_fabrica
									WHERE os = $os";
						$res_envia = pg_query($con,$sql_envia);

						//echo nl2br($sql_envia);

						if (pg_num_rows($res_envia) > 0) {
							$email_envia = pg_fetch_result($res_envia,0,'consumidor_email');
							$produto_referencia_envia = pg_fetch_result($res_envia,0,'referencia');
							$produto_nome_envia = pg_fetch_result($res_envia,0,'descricao');
							$consumidor_nome_envia = pg_fetch_result($res_envia,0,'consumidor_nome');
							//$link_temp_envia = explode("admin/",$HTTP_REFERER);
							$link_temp = explode("os_",$HTTP_REFERER);

							//if ($login_fabrica == 161) {
							$from_fabrica           = "no_reply@telecontrol.com.br";
							$from_fabrica_descricao = "Pós-Venda Cristófoli";
							$link_pesquisa = $link_temp[0]."externos/cristofoli/callcenter_pesquisa_satisfacao2.php?os=$os";
							$assunto  = "Pesquisa de Satisfação - Cristófoli";
							//}

							if(strlen($email_envia) > 0){
								$valida_email = filter_var($email_envia,FILTER_VALIDATE_EMAIL);
								if($valida_email !== false){

									$mensagem = "Produto: $produto_referencia_envia - $produto_nome_envia <br>";
						            $mensagem .= "Ordem de Serviço: $os, <br>";
						            $mensagem .= "Prezado(a) $consumidor_nome_envia, <br>";
						            //$mensagem .= "Sua opinião é muito importante para melhorarmos nossos serviços<br>";
						            //$mensagem .= "Por favor, faça uma avaliação sobre nossos produtos e atendimento através do link abaixo: <br />";
						            $mensagem .= nl2br($texto_ajuda) ."<br>";
						            $mensagem .= "Pesquisa de Satisfação: <a href='$link_pesquisa' target='_blank'>Acesso Aqui</a> <br><br>Att <br>Equipe ".$login_fabrica_nome;

						            $headers  = "MIME-Version: 1.0 \r\n";
						            $headers .= "Content-type: text/html \r\n";
						            $headers .= "From: $from_fabrica_descricao <no_reply@telecontrol.com.br> \r\n";
									$mailTc = new TcComm('smtp@posvenda');

									$mailTc->sendMail(
										array($email_envia,'silvia@cristofoli.com'),
										$assunto,
										$mensagem,
										'noreply@telecontrol.com.br'
									);

								}
							}
						}
					}
				}
				// hd-6641566 -- pediram para comentar o envio de email para 144
/*				if ($login_fabrica == 144 AND $ativo == "t") {

		            $sqlUltimaOs = "SELECT tbl_os.os, 
		            					   tbl_os.sua_os,
		            					   tbl_os.consumidor_email,
										   tbl_os_extra.pac,
		            					   tbl_os.nota_fiscal_saida,
		            					   to_char(tbl_os.data_nf_saida, 'dd/mm/yyyy') as data_nf_saida
		                            FROM tbl_os
		                          	JOIN tbl_os_extra ON tbl_os_extra.os = tbl_os.os
		                            WHERE tbl_os.os = {$os}
		                            LIMIT 1";
		            $resUltimaOs = pg_query($con, $sqlUltimaOs);

		            if (pg_num_rows($resUltimaOs) > 0) {

		            	$sua_os_pesquisa    = pg_fetch_result($resUltimaOs, 0, 'sua_os');
		                $email_atendimento  = pg_fetch_result($resUltimaOs, 0, 'consumidor_email');
		                $nf_saida           = pg_fetch_result($resUltimaOs, 0, 'nota_fiscal_saida');
		                $emissao_nf         = pg_fetch_result($resUltimaOs, 0, 'data_nf_saida');
						$codigo_rastreio_os = pg_fetch_result($resUltimaOs, 0, 'pac');
		                
		                if ($posto_interno) {
		                	$dadosRastreio = "Nota fiscal: {$nf_saida} <br />
		                		Data de Emissão: {$emissao_nf} <br />
		                		Rastreio: {$codigo_rastreio_os}";
		                }

		                $assunto = "Serviço de Atendimento HIKARI";
		                $mensagem = "Ordem de serviço {$sua_os_pesquisa} foi finalizada.<br /><br />{$dadosRastreio}";

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

		        }*/

			}//for

			if(in_array($login_fabrica,array(3,6,11,96,172))){ #HD 96191

				if (strlen($msg_erro) >0 or strlen($erro) >0) {

					$res = pg_query ($con,"ROLLBACK TRANSACTION");
					$msg_ok = "";

				}else{

					$res = pg_query ($con,"COMMIT TRANSACTION");
					$nota_fiscal_saida = "";
					$data_nf_saida     = "";

				}

			}

			if($login_fabrica == 85 && empty($msg_erro)){

				$sql = "SELECT hd_chamado FROM tbl_os WHERE os = {$os}";
				$res = pg_query($con, $sql);

				if(pg_num_rows($res) > 0){

					$hd_chamado = pg_fetch_result($res, 0, 'hd_chamado');

					if(!empty($hd_chamado)){

						$sql_admin = "
						SELECT tbl_admin.email
						FROM tbl_hd_chamado
						JOIN tbl_admin ON tbl_admin.admin = tbl_hd_chamado.atendente
						WHERE tbl_hd_chamado.hd_chamado = $hd_chamado
						";
						$res_admin = pg_query($con, $sql_admin);

						$email_atendente = pg_fetch_result($res_admin, 0, 'email');

						include "class/log/log.class.php";

						$log = new Log();

						$log->adicionaLog("Informamos que a OS $os foi finalizada pelo Posto");

						$log->adicionaTituloEmail("Finalização da OS $os pelo Posto");

						$log->adicionaEmail($email_posto);

						$log->enviaEmails();


					}

				}

			}

			// HD 27468
			if (($login_fabrica ==1 or $login_fabrica ==7) and strlen($msg_erro) == 0){

				//HD 36209 - Verifica se todas as OSs revenda que tem o mesmo "pai" foram fechadas
				foreach ($_POST["os"] as $i => $value) {
					$os                = trim($_POST["os"][$i]['os_' . $i]);
					$ativo_revenda     = trim($_POST["os"][$i]['ativo_revenda_'. $i]);

					if ($ativo_revenda == 't') {
						$sqlr = "SELECT tbl_os.os, substr(tbl_os.sua_os,0,strpos(tbl_os.sua_os,'-')) as sua_os, tbl_posto_fabrica.codigo_posto
						FROM tbl_os
						JOIN (
							SELECT os, fabrica, posto, os_numero
							FROM tbl_os
							WHERE os = $os
							AND fabrica = $login_fabrica
							AND posto = $login_posto
							) x ON tbl_os.fabrica = x.fabrica
						AND tbl_os.posto = x.posto
						AND tbl_os.excluida IS NOT TRUE
						AND tbl_os.consumidor_revenda = 'R'
						AND tbl_os.os_numero = x.os_numero
						AND tbl_os.os <> x.os
						AND data_fechamento IS NULL
						JOIN tbl_posto_fabrica ON tbl_posto_fabrica.fabrica = $login_fabrica AND tbl_posto_fabrica.posto = $login_posto";
						$resr = pg_query ($con,$sqlr);

						if (pg_num_rows($resr)) {
							$sua_os       = pg_fetch_result($resr,0,sua_os);
							$codigo_posto = pg_fetch_result($resr,0,codigo_posto);
							$numero_os = $codigo_posto.$sua_os;
							$msg_erro= traduz ("a.os.de.revenda.%.foi.explodida.para.varios.produtos.e.o.fechamento.podera.ser.concluido.somente.quando.todos.os.produtos.dessa.os.forem.entregues.para.o.cliente.nesse.caso,.sera.necessario.efetuar.o.fechamento.de.todas.as.oss.de.revenda.com.esse.mesmo.numero.", $con, $cook_idioma,$numero_os);
							/*"A O.S. DE REVENDA $codigo_posto$sua_os FOI EXPLODIDA PARA VÁRIOS PRODUTOS E O FECHAMENTO PODERÁ SER CONCLUÍDO SOMENTE QUANDO TODOS OS PRODUTOS DESSA O.S. FOREM ENTREGUES PARA O CLIENTE. NESSE CASO, SERÁ NECESSÁRIO EFETUAR O FECHAMENTO DE TODAS AS OS'S DE REVENDA COM ESSE MESMO NÚMERO.";*/
							$msg_ok="";
							break;
						}

						if($login_fabrica == 1) {
							if(!empty($os)) {

								$sql = "SELECT
									tbl_os_item.os_item,
									tbl_os.sua_os
									FROM tbl_os
									INNER JOIN tbl_os_produto ON tbl_os_produto.os = tbl_os.os
									INNER JOIN tbl_os_item ON tbl_os_item.os_produto = tbl_os_produto.os_produto
									LEFT JOIN tbl_os_item_nf ON tbl_os_item.os_item = tbl_os_item_nf.os_item
									LEFT JOIN tbl_pedido_cancelado USING(pedido,peca)
									WHERE tbl_os.fabrica = {$login_fabrica}
									AND tbl_os_item_nf.os_item isnull
									AND tbl_os_item.pedido notnull
									AND tbl_pedido_cancelado.pedido ISNULL
									AND tbl_os.os = {$os}";
								$res = pg_query($con, $sql);

								if(pg_num_rows($res) > 0){

									$count = pg_num_rows($res);

									for($j = 0; $j < $count; $j++){
										$os_item = pg_fetch_result($res, $j, "os_item");
										$sua_os = pg_fetch_result($res, $j, "sua_os");
									}

									$sua_os = $login_codigo_posto . $sua_os;
									$msg_erro .= traduz("A OS % não pode ser fechada, pois existe um pedido não faturado! <br />",null,null,[$sua_os]);
									break;
								}
							}
						}
					}
				}

				if (strlen($msg_erro) >0 or strlen($erro) >0) {
					$res = @pg_query ($con,"ROLLBACK TRANSACTION");
					$msg_ok = "";
				}else{
					//$res = @pg_query ($con,"ROLLBACK TRANSACTION");
					$res = @pg_query ($con,"COMMIT TRANSACTION");

					if (in_array($login_fabrica, [1])) {
						$helper = new \Posvenda\Helpers\Os();
						foreach ($arraySMS as $idArray => $numOS) {
							$sqlValidaSMS = "SELECT * FROM tbl_sms WHERE fabrica = $login_fabrica AND os = $numOS;";
							$resValidaSMS = pg_query($con, $sqlValidaSMS);

							if ( pg_num_rows($resValidaSMS) === 0) {

								$sql_contatos_consumidor = "
								SELECT tbl_posto_fabrica.codigo_posto||tbl_os.sua_os as suaos,
										consumidor_email ,
								        consumidor_celular,
								        referencia,
								        descricao,
								        tbl_posto.nome,
								        tbl_marca.marca,
								        tbl_marca.nome as nome_marca
								    FROM tbl_os
								    JOIN tbl_produto USING(produto)
								    JOIN tbl_posto USING(posto)
								    JOIN tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto
								    	AND tbl_posto_fabrica.fabrica  = {$login_fabrica}
								    LEFT JOIN tbl_marca ON tbl_marca.marca = tbl_produto.marca and tbl_marca.fabrica = {$login_fabrica}
								    WHERE os = {$numOS};";
								$qry_contatos_consumidor = pg_query($con, $sql_contatos_consumidor);

								$consumidor_email = pg_fetch_result($qry_contatos_consumidor, 0, 'consumidor_email');
								$consumidor_celular = pg_fetch_result($qry_contatos_consumidor, 0, 'consumidor_celular');
								$produto_os = pg_fetch_result($qry_contatos_consumidor, 0, 'referencia') . ' - ' . pg_fetch_result($qry_contatos_consumidor, 0, 'descricao');
								$posto_os = pg_fetch_result($qry_contatos_consumidor, 0, 'nome');
								$suaOSB = pg_fetch_result($qry_contatos_consumidor, 0, 'suaos');

								$nome_marca = pg_fetch_result($qry_contatos_consumidor, 0, 'nome_marca');
								$marca = pg_fetch_result($qry_contatos_consumidor, 0, marca);

								switch ($marca) {
									case '11':
										# B&D
										$msg_conserto_os = traduz("BLACK&DECKER: Em uma escala de 1 a 5, com base na experiência com o reparo do seu produto, o quanto você indicaria a marca aos seus familiares ou amigos?");
										break;

									case '237':
										# Dewalt
									$msg_conserto_os = traduz("DEWALT: Em uma escala de 1 a 5, com base na experiência com o reparo do seu produto, o quanto você indicaria a marca aos seus familiares ou amigos?");
										break;

									case '239':
										# Stanley
									$msg_conserto_os = traduz("STANLEY: Em uma escala de 1 a 5, com base na experiência com o reparo do seu produto, o quanto você indicaria a marca aos seus familiares ou amigos?");
										break;

									default:
										$msg_conserto_os = traduz("Em uma escala de 1 a 5, com base na experiência com o reparo do seu produto, o quanto você indicaria a marca aos seus familiares ou amigos?");
										break;

								}
								//Retirada no hd-3961943
								//$msg_conserto_os = "SBD: Em uma escala de 0 a 10, com base na experiência com o reparo do seu produto, o quanto você indicaria a Stanley Black&Decker aos seus familiares ou amigos?";

								if (!empty($consumidor_celular)) {
									$helper->comunicaConsumidor($consumidor_celular, $msg_conserto_os, $login_fabrica, $numOS);
								}
							}
						}
					}

					if($login_fabrica == 1 && $enviar_email_pesquisa == "sim" and count($os_email) > 0  ){
						#include_once 'class/email/PHPMailer/class.phpmailer.php';
						#include_once 'class/email/PHPMailer/PHPMailerAutoload.php';

						$os_email_aux = implode(",",$os_email);

						$sqlEmail = "
							SELECT  tbl_os.os           AS os_email     ,
							consumidor_nome     AS nome_email   ,
							consumidor_email    AS email_email
							FROM    tbl_os
							JOIN tbl_produto ON tbl_produto.produto = tbl_os.produto
							AND tbl_produto.linha IN (866,867,863,869,198,200,467,865,923,924,925)
							JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_os.posto AND tbl_posto_fabrica.fabrica = {$login_fabrica} AND
							tbl_posto_fabrica.categoria IN ('Autorizada', 'Locadora Autorizada')
							WHERE   tbl_os.fabrica = $login_fabrica
							AND     tbl_os.os IN ($os_email_aux)
							";
						$resEmail = pg_query($con,$sqlEmail);

						$resultado = pg_fetch_all($resEmail);
						$link_temp = explode("os_",$HTTP_REFERER);
						$link_pesquisa = $link_temp[0]."externos/blackedecker/pesquisa_email.php";
						$link_imagem = $link_temp[0]."externos/blackedecker/images/topo_pt.png";

						$mailer = new TcComm('noreply@tc');

						#$mailer = new TcComm('smtp@posvenda');

						foreach($resultado as $k=>$v){
							$os_email = $v['os_email'];
							$msg = "<a href='$link_pesquisa?os=$os_email'><img src='$link_imagem' border='0' /></a>";

							$mailer->sendMail(
								array($v['nome_email']=>$v['email_email']),
								'PESQUISA DE SATISFAÇÃO - BLACK&DECKER',
								$msg,
								'noreply@telecontrol.com.br'
							);

							/*
							$mailer->From = "pesquisa@stanleyblackanddecker.com.br";
							$mailer->FromName = "Stanley Black & Decker";
							$mailer->addAddress($v['email_email'],$v['nome_email']);
							$mailer->IsHTML(true);

							$mailer->Subject = "PESQUISA DE SATISFAÇÃO - BLACK&DECKER";
							$mailer->Body = $msg;

							$mailer->Send();
							 */
						}
					}


					$sql = "SELECT tbl_os.os
						FROM tbl_os
						WHERE tbl_os.fabrica = $login_fabrica
						AND   tbl_os.posto   = $login_posto
						AND   (tbl_os.data_abertura + INTERVAL '60 days') <= current_date
						AND   tbl_os.data_fechamento IS NULL
						AND   tbl_os.cortesia IS FALSE
						AND  tbl_os.excluida is FALSE LIMIT 1";

					$res = pg_query ($con,$sql);
					if(pg_num_rows($res) > 0){
						$tem_os_aberta = pg_fetch_result($res, 0, 'os');
					}


					if(empty($tem_os_aberta) and strlen ($erro) == 0){
						echo `/usr/bin/php rotinas/blackedecker/bloqueia-posto.php $login_posto`;
					}

				}
			}else if (($login_fabrica ==1 or $login_fabrica ==7) and strlen($msg_erro) > 0){
				$res = @pg_query ($con,"ROLLBACK TRANSACTION");
				$msg_ok = "";
			}

		} // if msg_erro

	}//if

	if(in_array($login_fabrica,[120,201]) and $ajax == TRUE){
		if(empty($erro)){
			echo "ok";
		} else {
			$msg_erro .= $erro."<br />";
			echo $msg_erro;
		}
		exit;
	}

	/*HD - 4299264*/
	if (in_array($login_fabrica, array(174)) && empty($msg_erro) AND 1==2) {
		$aux_sql = "SELECT consumidor_celular FROM tbl_os WHERE os = $os";
		$aux_res = pg_query($con, $aux_sql);
		$aux_row = pg_num_rows($aux_res);

		if ($aux_row > 0) {
			$consumidor_celular = trim(pg_fetch_result($aux_res, 0, 'consumidor_celular'));

			if (!empty($consumidor_celular)) {
				include_once "class/sms/sms.class.php";
				$sms = new SMS();

				$quebra             = array('(', ')', '-', ' ');
				$consumidor_celular = str_replace($quebra, '', $consumidor_celular);

				$sms_msg = "OS {$os} PROD.ENTREGUE: Que nota vc atribui, entre 0 (insatisfeito) a 5(satisfeito),quanto ao atendimento geral prestado?responda de 0 a 5 (SMS sem custo)";
				$enviar_sms = $sms->enviarMensagem($consumidor_celular, $os, '', $sms_msg);

				if ($envia_sms === false) {
					$msg_erro .= "Erro ao enviar o SMS ao consumidor <br>";
				}
			}
		}
	}
}

$title = traduz("fechamento.de.ordem.de.servico", $con, $cook_idioma);
$layout_menu = 'os';
include "cabecalho.php";
include_once '_traducao_erro.php';

?>

<script language="JavaScript">

	function finalizaOsNewmaq(num_os, posicao){

		var data_fechamento = $('input[name=data_fechamento]').val();

		$.ajax({
	        url: "os_fechamento.php",
	        type: "post",
	        data: { btn_acao: 'continuar', os: num_os, ajax: true, data_fechamento: data_fechamento },
	        success: function(data) {
	            if (data == 'ok') {
	            	alert("O.S finalizada com sucesso.");
	            	$(".btn_finaliza_"+posicao).text('');
	            	$(".checkbox_"+posicao).attr('disabled', 'disabled');
	            } else {
	                alert(data);
	            }
	        }
	    });
	}

	<? if($login_fabrica == 131) { ?>
		function confirmaRecebimentoPeca(os, tela){
	    var login_fabrica = <?=$login_fabrica;?>;    

		    if(login_fabrica == 131){
		        Shadowbox.init();
		        Shadowbox.open({
		            player: "iframe",
		            content: "confirma_recebimento_peca.php?os="+os+"&tela="+tela,
		            height: 220,
		            width: 800,
		            options: {
		                modal: true,
		                enableKeys: false,
		                displayNav: false
		            }
		        });        
		    }    
		}

		function confirmafecha(){
			if (document.frm_os.btn_acao.value == '' ){
				document.frm_os.btn_acao.value='continuar';
				document.frm_os.submit();
			} else {
				alert ('Aguarde submissão')
			}
		}
	<? } ?>

	var checkflag = "false";
	var filtro_status = -1;
	function SelecionaTodos(field) {

		if($("th input.frm").is(":checked")){
			$("table.tabela_resultado tbody tr > td  > input[type=checkbox]").attr('checked',false);
			if(filtro_status >= 0){
				$("tr[rel=status_"+filtro_status+"] > td > input[type=checkbox]").attr('checked','checked');
			}else{
				$("table.tabela_resultado tbody tr > td  > input[type=checkbox]").attr('checked','checked');
			}
		}else{
			$("input[type=checkbox].os").attr("checked",false);
		}
	}

</script>
<link rel="stylesheet" href="js/jquery.tooltip.css" />
<script type="text/javascript" src="admin/js/jquery-1.8.3.min.js"></script>
<script type="text/javascript" src="admin/js/jquery.maskedinput.js"></script>
<script type="text/javascript" src="js/jquery.tooltip.js"></script>
<style>
	.subtitulo{
		min-width: 760px;
	}
	.alert{
		min-width: 760px;
		text-align: center !important;
	}
</style>

<script type="text/javascript">

	$(document).ready(function(){
		$(".tabela_resultado tr").mouseover(function(){$(this).addClass("over");}).mouseout(function(){$(this).removeClass("over");});
		//$(".tabela_resultado tr:even").addClass("alt");
		$(".tabela_resultado tr[rel='sem_defeito']").addClass("sem_defeito");
		$(".tabela_resultado tr[rel='mais_30']").addClass("mais_30");
		$(".tabela_resultado tr[rel='erro_post']").addClass("erro_post");

<?php if ($login_fabrica == 1) { ?>
		$("#checkbox_revenda").on("click",function(){
			if($(this).is(":checked") == true){
				$("#tabela_os_revenda tbody tr > td  > input[type=checkbox]").attr('checked','checked');
				// $("#ativo_revenda").attr("checked","checked");
			}else{
				$("#tabela_os_revenda tbody tr > td  > input[type=checkbox]").attr('checked',false);
				// $("#ativo_revenda").attr("checked",false);
			}
		});
<?php } ?>
	});


	function formata_data(campo_data, form, campo){
		var mycnpj = '';
		mycnpj = mycnpj + campo_data;
		myrecord = campo;
		myform = form;

		if (mycnpj.length == 2){
			mycnpj = mycnpj + '/';
			window.document.forms["" + myform + ""].elements[myrecord].value = mycnpj;
		}
		if (mycnpj.length == 5){
			mycnpj = mycnpj + '/';
			window.document.forms["" + myform + ""].elements[myrecord].value = mycnpj;
		}

	}
	function mostraDados(peca){
		if (document.getElementById('dados_'+peca)){
			var style2 = document.getElementById('dados_'+peca);
			if (style2==false) return;
			if (style2.style.display=="block"){
				style2.style.display = "none";
			}else{
				style2.style.display = "block";
			}
		}
	}

</script>
<style type="text/css">
	@import "plugins/jquery/datepick/telecontrol.datepick.css";

</style>
<script type="text/javascript" src="js/assist.js"></script>
<script type="text/javascript" src='ajax.js'></script>
<script type="text/javascript" src="js/bibliotecaAJAX.js"></script>
<script type="text/javascript" src="js/niftycube.js"></script>
<script src="js/jquery-ui.min.js" type="text/javascript"></script>
<link rel="stylesheet" href="js/jquery.tooltip.css" />
<script type="text/javascript" src="js/jquery.tooltip.min.js"></script>
<script type="text/javascript" src="js/jquery.base64.js"></script>

<?php if($login_fabrica == 3){ ?>
	<script src="plugins/shadowbox_lupa/shadowbox.js" type="text/javascript"></script>
	<link rel="stylesheet" type="text/css" href="plugins/shadowbox_lupa/shadowbox.css" media="all">

<?php }else{ ?>

	<script src="plugins/shadowbox/shadowbox.js" type="text/javascript"></script>
	<link rel="stylesheet" type="text/css" href="plugins/shadowbox/shadowbox.css" media="all">
<?php } ?>

<script type="text/javascript" src="js/jquery.maskedinput2.js"></script>
<?/*    MLG 23/03/2010 - HD 205816 - Refiz o 'prompt' para evitar (novidade...) problemas com usuários do MSIE... */?>
<script type="text/javascript" src="plugins/jquery/datepick/jquery.datepick.js"></script>
<script type="text/javascript" src="plugins/jquery/datepick/jquery.datepick-pt-BR.js"></script>
<script type="text/javascript" src="js/jquery.alphanumeric.js"></script>

<?php
    if(in_array($login_fabrica, array(141,144,165)) && $posto_interno == true){
?>

<script src="plugins/shadowbox_lupa/shadowbox.js" ></script>
<link rel="stylesheet" href="plugins/shadowbox_lupa/shadowbox.css" type="text/css" />

<script type="text/javascript">

$(function() {
    Shadowbox.init();

	<?php

    if (in_array($login_fabrica, [141,144,165])) { ?>
    	$("input[name^=data_nf_saida_]").datepick({startdate:'01/01/2000'});
		$("input[name^=data_nf_saida_]").maskedinput("99/99/9999");

    	$("input[name*=_saida_]").change(function(){
    <?php
    } else { ?>
    	$("input[name*=_saida_]").blur(function(){
    <?php
    }
    ?>

        var aux = $(this).attr("name").split("_");
        var linha = aux[3];
        var os      = $("input[name*=os_"+linha+"]").val();
        var nf      = $("input[name=nota_fiscal_saida_"+linha+"]").val();
        var data_nf = $("input[name=data_nf_saida_"+linha+"]").val();

        if (nf.length > 0 && (data_nf.length > 0 && data_nf != '__/__/____')) {
            $.ajax({
                url:"os_fechamento.php",
                type:"POST",
                dataType:"JSON",
                data:{
                    ajax:true,
                    ajaxType:"status_nf_saida",
                    os:os,
                    nf:nf,
                    data_nf:data_nf
                }
            })
            .done(function(data){
                if (data.ok) {
                    alert(data.msg);
                }
            });
        }
    });
});

function listaOS(os){

    var url = "lista_os_revendas.php?os_revenda=" + os +"&tipo=comum";

    Shadowbox.open({
        content: url,
        player: "iframe",
        width: 800,
        height: 300,
        options: {
            modal: true,
            enableKeys: false,
            displayNav: false
        }
    });

}

</script>

<?php
    }
?>

<script src="plugins/price_format/jquery.price_format.1.7.min.js" ></script>

<script type="text/javascript" charset="utf-8">
	$(function() {

<?php if ($login_fabrica == 3) { ?>
		$(".check").each(function(){
			if ($(this).is(":checked")){
				var numero_linha = $(this).attr("numero");
				mostraDados(numero_linha);
			}
		});

		$(".check").click(function(){
			var numero_linha = $(this).attr("numero");
			mostraDados(numero_linha);
		});
<?php } ?>

	    $('input[rel=nf_saida]').numeric(); //hd_chamado=2788473

		<?php
		if ($login_fabrica == 1) {
		?>
			$("input[rel='data_conserto']").datepick({startdate:'01/01/2000'});
			$("input[rel='data_conserto']").maskedinput("99/99/9999");
		<?php
		} else {
		?>
			$("input[rel='data_conserto']").maskedinput("99/99/9999 99:99");
			$("input[rel='lote_data_conserto']").maskedinput("99/99/9999 99:99");
		<?php
		}

		if (in_array($login_fabrica, array(165))) {
		?>
 			$("input[name^=data_nf_saida_]").datepick({startdate:'01/01/2000'});
			$("input[name^=data_nf_saida_]").maskedinput("99/99/9999");
		<?php
        }

        if (in_array($login_fabrica, array(156)) and $posto_interno == true) {
		?>
			$("input[rel='data_liberacao']").datepick({startdate:'01/01/2000'});
			$("input[rel='data_liberacao']").maskedinput("99/99/9999");
		<?php
		}
		?>

		$("input[name='data_fechamento']").datepick({startdate:'01/01/2000'});
		$("input[name='data_fechamento']").maskedinput("99/99/9999");
<?php
        if ($login_fabrica == 85) {
?>
        $("#hora_fechamento").maskedinput("99:99");
<?php
        }

?>
		$("input.mask-datetimepicker").maskedinput("99/99/9999 99:99:00");

		$("input.decimal").priceFormat({
			prefix: '',
            thousandsSeparator: '',
            centsSeparator: '.',
            centsLimit: 2
		});
	});

	function anexar_termo(os) {
		Shadowbox.init();                 
        Shadowbox.open({
            content: "shadowbox_anexar_termo.php?os="+os,
            player: "iframe",
            options: {
                enableKeys: false
            },
            title: "Anexar Termo",
            width: 400,
            height: 250
        });
	}


	function anexarAuditoria(os) {
		Shadowbox.init();
		Shadowbox.open({
	        content: "anexo_comprovante.php?os=" + os,
	        player: "iframe",
	        width: 850,
	        height:400,
	        title: "Ordem de Serviço " + os,
	        options: {
	            modal: true,
	            enableKeys: true,
	            displayNav: true
	        }
	    });
	}

	function modelocomprovante() {
		Shadowbox.init();
		Shadowbox.open({
			content: "comprovante_retirada_download.html",
			player: "iframe",
			width: 850,
			height:280,
		});
	}
	<?php if (in_array($login_fabrica, array(167,173,176,203))) { ?>

	function informeDataConserto(dados){
		Shadowbox.init();
		Shadowbox.open({
	        content: "anexo_data_conserto.php?os="+dados,
	        player: "iframe",
	        width: 850,
	        height:280,
	        title: "Ordem de Serviço "+dados,
	        options: {
	            modal: true,
	            enableKeys: false,
	            displayNav: false
	        }
	    });
	}
	<?php } ?>

	function retorna_data_conserto(numero_os,data_informada,gravado){
		if(gravado == 'true'){
			<?php
			if (in_array($login_fabrica, array(167,173,203))) { ?>
				var remover_linha = $("button.fechamento_os[data-anexo-fechamento="+numero_os+"]").parent('td').parent('tr');
				$("button.fechamento_os[data-anexo-fechamento="+numero_os+"]").parent('td').html(data_informada);
				remover_linha.remove();
				//$("button.fechamento_os[data-anexo-fechamento="+numero_os+"]").parent('td').html(data_informada);
			<?php
			} else { ?>
				$("button.fechamento_os[data-anexo-fechamento="+numero_os+"]").parent('td').html(data_informada);
			<?php
			}
			?>
	    }
	    Shadowbox.close();
	}

	<?php
	if (in_array($login_fabrica, array(141,144,165)) && $posto_interno == true) {
	?>

        $(function() {

	        $(".tipo_entrega").change(function(){
	        	
	        	let os = $(this).data('os');
	        	let tipo_entrega = $(this).find("option:selected").val();

				if (tipo_entrega == "transportadora" || tipo_entrega == "correios") {

					$("#codigo_rastreio_"+os).attr("readonly", false);


				} else {

					$("#codigo_rastreio_"+os).prop("readonly", true);

				}

			});

			var count = 0;
            $(".codigo_rastreio").blur(function(){
				var total_os_selecionada = $("input[class*=checkbox_]:checked").length;
                var codigo_rastreio = $(this).val();
                var os              = $(this).attr("data-os");
                var tipo_entrega    = $("select[data-os="+os+"]");
                var data_conserto   = $("input[rel=data_conserto][alt="+os+"]").val();
                var campo_rastreio  = $(this);

                if (data_conserto.length == 0) {
                    tipo_entrega.val("").trigger("change");
                    alert('<?= traduz("Informe a data de conserto antes de informar o tipo de entrega e código de rastreio") ?>');
                } else if (codigo_rastreio != "") {

                    $.ajax({
                        url: "os_fechamento.php",
                        type: "post",
                        dataType:"JSON",
                        data: {
                            ajax_grava_codigo_rastreio: true,
                            os: os,
                            tipo_entrega: tipo_entrega.val(),
                            posto_interno: '<?php echo $posto_interno;?>',
                            codigo_rastreio: codigo_rastreio
                        }
                    })
                    .always(function(data) {
                    	count += 1;
                        if (data.error) {
                            tipo_entrega.val("").trigger("change");
                            alert(data.error);
                        } else {
                        	$(campo_rastreio).attr("readonly", true);
                        	$(tipo_entrega).attr("readonly", true);
                        }
                        if (total_os_selecionada >= count) {
	                		$("body #loading").hide();
	                		$("body #loading-block").hide();
                		}
                    });

                }
            });
        });
	<? }

	if (in_array($login_fabrica, array(141,144)) && $posto_interno) { ?>
		function remanufaturarOS(os, button){
		     $.ajax({
		        url: "os_fechamento.php",
		        type: "get",
		        data: { remanufaturar: true, os: os },
		        beforeSend: function() {
		        	$(button).text("Aguarde...");
		        	$(button).prop({ disabled: true });
		        },
		        success: function(data) {
		            data = JSON.parse(data);

		            if (data.error) {
		                alert(data.error);
		                $(button).text("Remanufaturar");
		        		$(button).prop({ disabled: false });
		            } else {
		                $(button).parent("td").text("Remanufaturada");
		            }
		        }
		    });
		}
	<? }

	if (in_array($login_fabrica, array(169,170,174))){ ?>
		function retornoPostagem(status,hd_chamado, numero_os){
		    if(status == "true"){
		    	$("#lgr_correios_"+numero_os).hide();
		    }
		    Shadowbox.close();
		}

		function solicitaPostagem(hd_chamado, codigo_posto) {
			Shadowbox.init();
		    Shadowbox.open({
		        content :   "solicitacao_postagem_correios_produto.php?hd_chamado="+hd_chamado+"&codigo_posto="+codigo_posto,
		        player  :   "iframe",
		        title   :   "Solicitar Autorização de Postagem",
		        width   :   1000,
		        height  :   700,
		        options: {
		            modal: true,
		            enableKeys: false,
		            displayNav: false
		        }
		    });
		}
	<? } ?>
</script>

<script type="text/javascript">
	var ajax_data_conserto = {};

	$().ready(function() {

	var listar = $('#campo_aux').val();

	$("#lote_tipo_entrega").change(function () {
		var input = $("#lote_codigo_rastreio");
		if (this.value == "balcão") {
			input.val("balcão");
			input.attr("class", "frm input_readonly");
		} else if (this.value == "correios") {
			input.val("");
			input.attr("class", "frm");
			input.removeAttr("readonly");
			input.removeClass("input_readonly");
		} else {
			input.attr("readonly", "readonly");
			input.val("");
			input.attr("class", "frm input_readonly");
		}
	});


	$(".btn_copia_lote").click(function () {
		var checked = $("input[class*=checkbox_]:checked");

		if (checked.length == 0) {
			alert("Escolha ao menos uma OS");
			return false;
		} else {

			var de_data_conserto     = $("#lote_data_conserto").val();
			var de_nota_fiscal_saida = $("input[name=lote_nota_fiscal_saida]").val();
			var de_data_nf_saida 	 = $("input[name=lote_data_nf_saida]").val();
			var de_tipo_entrega 	 = $("#lote_tipo_entrega").val();
			var de_codigo_rastreio   = $("#lote_codigo_rastreio").val();

			$.each(checked, function (indice, index) {
			  	var tr = $(this).parents('tr')[0];

			  	if (de_data_conserto != '') {
			  		var data_conserto = $(tr).find("input[id^=data_conserto_]");
			  		data_conserto.val(de_data_conserto);
			  		data_conserto.blur();
			  	}

			  	if (de_nota_fiscal_saida != '') {
					var nota_fiscal_saida = $(tr).find("input[name^=nota_fiscal_saida_]");
					nota_fiscal_saida.val(de_nota_fiscal_saida);
					nota_fiscal_saida.blur();
				}

			  	if (de_data_nf_saida != '') {
					var data_nf_saida = $(tr).find("input[name^=data_nf_saida_]");
					data_nf_saida.val(de_data_nf_saida);
					data_nf_saida.blur();
				}

			  	if (de_tipo_entrega != '') {
			  		var tipo_entrega = $(tr).find("select[id^=tipo_entrega_]");
			  		tipo_entrega.val(de_tipo_entrega);
			  	}

			  	if (de_tipo_entrega == "balcão") {
			  		$("body #loading").show();
            		$("body #loading-block").show();
			  		var codigo_rastreio = $(tr).find("input[id^=codigo_rastreio_]");
			  		codigo_rastreio.val(de_codigo_rastreio);
			  		codigo_rastreio.blur();
			  	}

			  	if (de_tipo_entrega == "correios" && de_codigo_rastreio == "") {
			  		$("body #loading").show();
            		$("body #loading-block").show();
			  		var codigo_rastreio = $(tr).find("input[id^=codigo_rastreio_]");
			  		codigo_rastreio.val("");
			  		codigo_rastreio.blur();
			  	}

			  	if (de_tipo_entrega == "correios" && de_codigo_rastreio != "") {
			  		$("body #loading").show();
            		$("body #loading-block").show();
			  		var codigo_rastreio = $(tr).find("input[id^=codigo_rastreio_]");
			  		codigo_rastreio.val(de_codigo_rastreio);
			  		codigo_rastreio.blur();
			  	}

			});

		}
	});

		<?php
		if ($login_fabrica == 1) {
		?>
			$.each($("input[rel=data_conserto]"), function () {
				ajax_data_conserto[$(this).attr("alt")] = false;
			});

			$("button[name=grava_data_conserto]").click(function () {

				let td            = $(this).parent("td");
				let os            = $(td).find("input[rel=data_conserto]").attr("alt");
				let data_conserto = $(td).find("input[rel=data_conserto]").val();
				let posicao       = $(td).find("input[rel=data_conserto]").attr("data-posicao");

				if (data_conserto == undefined || data_conserto.length == 0) {
					alert("Informe a data de conserto");
					return false;
				}

				if ($(".usa_estoque_posto").val() == "sim") {

					$(".usa_estoque_posto").val('');
					document.frm_os.btn_acao.value = 'erro';
					
					$.ajax({
						url: "os_fechamento.php",
						async: false,
						type: "POST",
						data: { verificaPedido : true, os: os },
						beforeSend: function () {
							$(td).find("img.loading").show();
						},
						complete: function (data) {
							data = data.responseText;
							if (data == "ok") {
								var alerta = confirm("A OS ainda possui pedido que não foi atendido ou está pendente de envio. Deseja utilizar as peças de seu estoque?");
								if (alerta == true) {
									$.ajax({
										url: "os_fechamento.php",
										type: "POST",
										data: { verificaEstoque : true, os: os },
										
										complete: function (data2) {
											
											data2 = data2.responseText;
											if (data2 != "sim") {
												alert('Sem saldo no estoque da(s) peça(s) '+data2+'. Gentileza aguardar o pedido em garantia. Qualquer dúvida ou problema, gentileza contatar seu suporte.');
												document.frm_os.btn_acao.value = 'erro';
												$(td).find("img.loading").hide();
												$(".usa_estoque_posto").val('sim');
												return false;
											} else {
												$(td).find("input[name^=data_conserto_]").attr({ "readonly": "readonly" });
												$(td).find("input[name^=data_conserto_]").removeClass('hasDatepick');
												$(td).find("button[name=grava_data_conserto]").remove();
												$(".usa_estoque_posto").val("sim");
												$(".ativo2_"+posicao).val('t');
												$(td).find("img.loading").hide();
												document.frm_os.btn_acao.value = '';
											}
										}
									});  
								} else {
									document.frm_os.btn_acao.value='erro';
									$(".usa_estoque_posto").val('sim');					
									$(td).find("img.loading").hide();
									return false;
								}
							} else if (data == "nao") {
								let data_con_var  = $(td).find("input[rel=data_conserto]").val();
								let data_con_this = $(td).find("input[rel=data_conserto]");

								valida_data_conserto(data_con_var, data_con_this);
								
								$(td).find("img.loading").hide();	
								document.frm_os.btn_acao.value='';
							} else {
								alert('Erro no processamento da requisição');
								$(".usa_estoque_posto").val('sim');
								document.frm_os.btn_acao.value='erro';
								$(td).find("img.loading").hide();
								return false;
							}
						}
					});

		            $("#enviar_email_pesquisa").val("sim");
		            $(".checkbox_"+posicao).prop("checked", true);
					$("#data_conserto_finalizar").val(data_conserto);
					
					setTimeout(function(){  
						if (document.frm_os.btn_acao.value == '' ) {
								document.frm_os.btn_acao.value = "continuar";
								document.frm_os.submit();
						
						} else if (document.frm_os.btn_acao.value == 'erro') {
							$(".usa_estoque_posto").val('sim');
							$(td).find("img.loading").hide();
							document.frm_os.btn_acao.value = '';
							return false;
						} else {
							alert("Aguarde submissão");
						}
					}, 2000);
					
					

					/*$.ajax({
						url: "os_fechamento.php",
						async: true,
						type: "POST",
						data: { gravarDataconserto : data_conserto, os: os },
						beforeSend: function () {
							ajax_data_conserto[os] = true;
							$(td).find("img.loading").show();
						},
						complete: function (data) {
							data = data.responseText;

							if (data != undefined && data.length > 0) {
								alert(data);
							} else {
								$(td).find("input[name^=data_conserto_]").attr({ "disabled": "disabled" });
								$(td).find("button[name=grava_data_conserto]").remove();
							}

							ajax_data_conserto[os] = false;
							$(td).find("img.loading").hide();
						}
					});*/
				} else {
					let data_con_var  = $(td).find("input[rel=data_conserto]").val();
					let data_con_this = $(td).find("input[rel=data_conserto]");

					valida_data_conserto(data_con_var, data_con_this);
				}
			});
		<?php
		} else {
		?>
			$("input[rel='data_conserto']").blur(function(){

				if($(this).val() != ""){
					valida_data_conserto($(this).val(), $(this));
				}				
			});
		<?php
		}


		
		?>

		$("input[rel='pac']").blur(function(){
			var campo = $(this);
			$.post('<? echo $PHP_SELF; ?>',
			{
				gravarPac : campo.val(),
				os: campo.attr("alt")
			},
			//24714
			function(resposta) {
				if (resposta.length > 0){
					alert(resposta);
					campo.val('');
				}
			});
		});

	});


	function valida_data_conserto(valor_campo, campo) {
		var data_fechamento = $("input[name=data_fechamento]").val();
		var numero_os = campo.attr("alt");
		var id_fabrica = <?= $login_fabrica ?>

		<? if (in_array($login_fabrica, array(169,170))) { ?>

			if ($(campo).val() != ''){
				var defeito_constatado_os = $(campo).attr('data-defeito_constatado_os');
				if (defeito_constatado_os == '' || defeito_constatado_os == undefined){
					alert('<?= traduz("Os sem defeito constatado não pode ser consertada") ?>');
					$(campo).val('');
					return;
				}
			}
		<? } else { ?>
			if(id_fabrica == 11 && (data_fechamento == "" || valor_campo == "")){
				return;
			}
		<? } ?>

		$.post('<?= $PHP_SELF; ?>',
		{
			gravarDataconserto : valor_campo,
			os: campo.attr("alt"),
			data_fechamento : data_fechamento
		},
		//24714
		
		function(resposta) {

			if (resposta.length > 0){
				alert(resposta);

				<?php if(!in_array($login_fabrica, array(11,172))){ ?>
					campo.val('');
				<?php } ?>

				$(campo).focus();

				<? if(in_array($login_fabrica, array(169,170,174))){ ?>
					if (resposta.length > 0 ){
						if (resposta == 'solicitar_postagem'){
							if(campo.val() != ''){
								$("#lgr_correios_"+numero_os).show();
							}else{
								$("#lgr_correios_"+numero_os).hide();
							}
						}else{
							//alert(resposta);
							campo.val('');
							$(campo).focus();
							var validado = false;
							$("#lgr_correios_"+numero_os).hide();
						}
					} else {
						var validado = true;
						if (campo.val() == ''){
							$("#lgr_correios_"+numero_os).hide();
						}
					}
				<? } else { ?>
					if (resposta.length > 0){
						alert(resposta);
						campo.val('');
						$(campo).focus();
						var validado = false;
					} else {
						var validado = true;
					}
				<? } ?>
			} else {
				<?php if ($login_fabrica == 1) { ?>
						alert("Data de conserto gravado com sucesso !")
						window.location.reload();
				<?php } ?>
			}
		});
	}

	<?php
	if ($login_fabrica == 1) {
		?>
		function submitForm () {
			var submit = true;

			$.each($("input[rel=data_conserto]:checked"), function(key, value){
				if(this.value == ""){
					submit = false;
					return false;
				}
			});

			$.each(ajax_data_conserto, function (key, value) {
				if (value == true) {
					submit = false;
					return false;
				}
			});

			if (submit == true) {
	                	$("#enviar_email_pesquisa").val("sim");
				if (document.frm_os.btn_acao.value == '' ) {
					document.frm_os.btn_acao.value = "continuar";
					document.frm_os.submit();
				} else {
					alert("Aguarde submissão");
				}
			} else {
				alert('<?= traduz("Por favor, espere o término do processo de gravação da data de conserto") ?>');
			}
		}
		<?php
	}
	?>

	//HD 234532
	function filtrar(status){
		if(status >= 0){

			//$("table.tabela_resultado tbody tr").hide();
			//$("tr[rel=status_"+status+"]").show();
			window.location.href = "<? echo $PHP_SELF?>?&listar=todas&status_check="+status;

			if($("th input.frm").is(":checked")){
				$("table.tabela_resultado tbody tr > td > input[type=checkbox]").attr('checked',false);
				$("tr[rel=status_"+status+"] > td > input[type=checkbox]").attr('checked','checked');
			}

		}else{

			if (status == -1) {
				window.location.href = "<? echo $PHP_SELF?>?listar=todas&status_check="+status;	
			} else {
				//$("table.tabela_resultado tbody tr").show();
				window.location.href = "<? echo $PHP_SELF?>?&listar=todas";

				if($("th input.frm").is(":checked")){
					$("table.tabela_resultado tbody tr > td > input[type=checkbox]").attr('checked','checked');
				}
			}
		}

		filtro_status = status;

	}

	function disableConserto(){
		$("input[rel=data_conserto]").attr('disabled',true);
	}

</script>


<script type="text/javascript">
	window.onload=function(){
		Nifty("ul#split h3","top");
		Nifty("ul#split div","none same-height");
	}
</script>
<style type="text/css">
	table.sample {
		border-collapse: collapse;
		width: 700px;
		font-size: 1.1em;
	}
	table.sample th {
		background: #3e83c9;
		color: #fff;
		font-weight: bold;
		padding: 2px 11px;
		text-align: left;
		border-right: 1px solid #fff;
		line-height: 1.2;
	}
	table.sample td {
		padding: 1px 11px;
		border-bottom: 1px solid #95bce2;
	}

	/*
	table.sample td * {
		padding: 1px 11px;
	}
	*/
	table.sample tr.alt td {
		background: #ecf6fc;
	}
	table.sample tr.over td {
		background: #bcd4ec;
	}
	table.sample tr.clicado td {
		background: #FF9933;
	}
	table.sample tr.sem_defeito td {
		background: #FFCC66;
	}
	table.sample tr.mais_30 td {
		background: #FF0000;
	}
	table.sample tr.erro_post td {
		background: #99FFFF;
	}

	.titulo {
		background:#7392BF;
		width: 700px;
		text-align: center;
		padding: 4px 4px; /* padding greater than corner height|width */
		/*	margin: 1em 0.25em;*/
		font-size:12px;
		color:#FFFFFF;
	}
	.titulo h1 {
		color:white;
		font-size: 120%;
	}

	.subtitulo {
		background:#FCF0D8;
		width: 600px;
		text-align: center;
		padding: 2px 2px; /* padding greater than corner height|width */
		margin: 10px auto;
		color:#392804;
	}
	.subtitulo h1 {
		color:black;
		font-size: 120%;
	}

	.content {
		background:#CDDBF1;
		width: 600px;
		text-align: center;
		padding: 5px 30px; /* padding greater than corner height|width */
		margin: 1em 0.25em;
		color:#000000;
		text-align:left;
	}
	.content h1 {
		color:black;
		font-size: 120%;
	}

	.Titulo {
		text-align: center;
		font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
		font-size: 10px;
		font-weight: bold;
		color: #FFFFFF;
		background-color: #596D9B;
	}
	.Conteudo {
		font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
		font-size: 10px;
		font-weight: normal;
	}
	.fechamento{
		font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
		font-size: 9px;
		font-weight: bold;
		color: #FFFFFF;
		background-color: #C50A0A;
	}
	.fechamento_content{
		font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
		font-size: 9px;
		color: #FFFFFF;
		background-color: #F9DBD0;
	}

	.txt_acao_massa{
		font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
		font-size: 10px;
		font-weight: bold;
		color: #FFFFFF;
		background-color: #002A3A;
		padding: 5px;
	}


	.ctx_acao_massa{
		font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
		font-size: 10px;
		color: #333;
		background-color: #eee;
		padding: 5px;
	}

	.inputs_acao_massa{
		width: 100%;
	}

	.Relatorio {
		border-collapse: collapse;
		width: 700px;
		font-size: 1.1em;
	}
	.msg_erro{
		background-color:#FF0000;
		font: bold 16px "Arial";
		color:#FFFFFF;
		text-align:center;
	}
	.Relatorio th {
		background: #3e83c9;
		color: #fff;
		font-weight: bold;
		padding: 2px 11px;
		text-align: left;
		border-right: 1px solid #fff;
		line-height: 1.2;
	}
	.Relatorio td {
		padding: 1px 11px;
		border-bottom: 1px solid #95bce2;
	}

	.titulo_tabela{
		background-color:#596d9b;
		font: bold 14px "Arial";
		color:#FFFFFF;
		text-align:center;
	}

	.titulo_coluna{
		background-color:#596d9b;
		font: bold 14px "Arial";
		color:#FFFFFF;
		text-align:center;
	}

	.formulario{
		background-color:#D9E2EF;
		font:11px Arial;
		text-align:left;
	}

	.sucesso{
		background-color:#008000;
		font: bold 14px "Arial";
		color:#FFFFFF;
		text-align:center;
	}

</style><?php

if ($sistema_lingua) $msg_erro = traducao_erro($msg_erro,$sistema_lingua);

if (strlen ($msg_erro) > 0 || strlen($nao_fechou) > 0) {

	if (strpos($msg_erro,"data_fechamento_anterior_abertura") > 0) $msg_erro = traduz("data.de.fechamento.nao.pode.ser.anterior.a.data.de.abertura", $con, $cook_idioma);
	if (strpos($msg_erro,"Bad date external ") > 0) $msg_erro = traduz("data.de.fechamento.invalida", $con, $cook_idioma); /*"Data de fechamento inválida";*/
	if (strpos($msg_erro,'"tbl_os" violates check constraint "data_fechamento"') > 0) $msg_erro = traduz("data.de.fechamento.invalida", $con, $cook_idioma); /*"Data de fechamento inválida";*/
	if (strpos($msg_erro,"É necessário informar a solução na OS") > 0) $msg_solucao = 1;
	if (strpos($msg_erro,"Para esta solução é necessário informar as peças trocadas") > 0) $msg_solucao = 1;
	if (strpos($msg_erro,"ERROR: ") !== false) { // retira palavra ERROR:
		$msg_erro = substr($msg_erro, 6);
	}

	if (strpos($msg_erro,"CONTEXT:")) {
		$x = explode('CONTEXT:',$msg_erro);
		$msg_erro = $x[0];
	}

	if ($erro == $msg_erro) {
		$erro = '';
	}

}

#echo '<br />';
if (strlen($msg_erro) > 0 || strlen($nao_fechou) > 0 || strlen($msg_erro_2) > 0) {
	$msg_erro = explode('<br/>', $msg_erro);
	$msg_erro_2 = explode('<br/>', $msg_erro_2);
	$nao_fechou = explode('<br/>', $nao_fechou); ?>
	<div class='alerts'>
		<div class='alert danger margin-top'>
			<?=implode("<br />", $msg_erro)?>
			<?=implode("<br />", $msg_erro_2)?>
			<?=implode("<br />", $nao_fechou)?>
		</div>
	</div>
	<br />
	<?php
}

if (strlen ($msg_ok) > 0 || ($login_fabrica == 171 && !empty($msg_auditoria_grohe))) {

	if ($login_fabrica == 171 && !empty($msg_auditoria_grohe)) {
	?>
		<table width="700" border="0" cellpadding="0" cellspacing="0" align="center" class='error'>
			<tr>
				<td height="27" valign="middle" align="center"><?php
					echo "<font size='2'><b>";
					echo $msg_auditoria_grohe;	
					echo "</b></font>";?>
				</td>
			</tr>
		</table>
	<?php	
	} else {
	?>
		<table width="700" border="0" cellpadding="0" cellspacing="0" align="center" class='sucesso'>
			<tr>
				<td height="27" valign="middle" align="center"><?php
					echo "<font size='2'><b>";
					fecho ("os.fechada.com.sucesso", $con, $cook_idioma);
					if (in_array($login_fabrica, array(169,170))) {
						echo "<br/>".$msg_auditoria;
					}
					echo "</b></font>";?>
				</td>
			</tr>
		</table><?php
	}
}

$tipo_os      = trim($_POST['tipo_os']);
$sua_os       = trim($_POST['sua_os']);
$codigo_posto = $_POST['codigo_posto'];

if (strlen($sua_os ) == 0 AND ($login_fabrica == 15 OR $login_fabrica == 24)) {
	$sua_os       = trim($_GET['sua_os']);
}

$colspan = ( in_array($login_fabrica, array(11,172)) ) ? 2 : null;
$width   = ( in_array($login_fabrica, array(11,172)) ) ? "50%" : null;?>
<?php


?>

<form name='frm_os_pesquisa' action='<? echo $PHP_SELF; ?>' method='post' >
	<table width="700" border="0" cellpadding="0" cellspacing="0" align="center">
		<table width="700" align="center" border="0" cellspacing="0" cellpadding="0" class='formulario'>

			<tr  height="30">
				<td align="center" class="titulo_tabela" colspan='<?=$colspan?>'><?php
					if ($cook_idioma == "ES") {
						fecho ("selecione.os.parametros.para.a.pesquisa", $con, $cook_idioma);
					} else {
						echo traduz("Parâmetros de Pesquisa");
					}?>
				</td>
			</tr>

			<tr>
				<td>&nbsp;</td>
			</tr>

			<tr class="Conteudo" bgcolor="#D9E2EF" align='left' >
				<td align='center' colspan='<?=$colspan?>'><b><? fecho ("numero.da.os", $con, $cook_idioma); ?></b>
					<input type='text' name='sua_os' size='10' value='<? echo $sua_os ?>'>
				<?php if (in_array($login_fabrica, array(169,170))) { ?>
					<b><?php fecho ("tipo.de.os", $con, $cook_idioma); ?></b>
					<select name='tipo_os'>
						<option value=""></option>
						<option value="C" <?=($tipo_os == 'C') ? 'selected' : ''?>>Consumidor</option>
						<option value="R" <?=($tipo_os == 'R') ? 'selected' : ''?>>Revenda</option>
					</select>
				<?php } ?>
				</td>
				</tr>

				<tr>
					<td>&nbsp;</td>
				</tr><?php

				if ( (in_array($login_fabrica, array(11,172)) && $login_posto == '14301') || (in_array($login_fabrica, array(11,172)) &&  $login_posto == '6359' && !isset($novaTelaOs))) {?>
				<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
					<td align='center' colspan='<?=$colspan?>'><b><? fecho ("box", $con, $cook_idioma);

						echo "/";
						fecho ("prateleira", $con, $cook_idioma);  ?></b>

						<SELECT NAME="prateleira_box">
							<OPTION VALUE=''></OPTION>
							<OPTION VALUE='CONSERTO'><? fecho ("conserto.maiu", $con, $cook_idioma); ?></OPTION>
							<OPTION VALUE='TROCA'><? fecho ("troca.maiu", $con, $cook_idioma); ?></OPTION>
							<OPTION VALUE='REEMBOLSO'><? fecho ("reembolso.maiu", $con, $cook_idioma); ?></OPTION>
						</SELECT>
					</td>
				</tr><?php
			}?>

			<tr>
				<td>&nbsp;</td>
			</tr><?php
			$align = ( in_array($login_fabrica, array(11,172)) ) ? "right" : "center" ;?>
			<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
				<td align='<?=$align?>' width="<?=$width?>">
					<input type="button" value="<?= traduz("Listar todas as suas OS's") ?>" name="btn_listar_todas_os" id="btn_listar_todas_os" onclick='window.location="<? echo $PHP_SELF."?listar=todas"; ?>"' /> &nbsp;
				</td><?php
				if ( in_array($login_fabrica, array(11,172)) ) {?>

				<td width="<?=$width?>">
					<input type="button" value='Listar OS de Revenda Consertadas' name="btn_listar_os_consertada" id="btn_listar_os_consertada" onclick='window.location="<? echo $PHP_SELF."?listar=consertadas"; ?>"' />
				</td><?php

			}?>

		</tr>

		<tr>
			<td>&nbsp;</td>
		</tr><?php

		if ($login_e_distribuidor == 't' && !isset($novaTelaOs)) {?>

		<tr height="22" bgcolor="#bbbbbb">
			<TD>
				<font size="2" face="Geneva, Arial, Helvetica, san-serif">
					<b><? fecho ("listar.todas.as.os.do.posto", $con, $cook_idioma);?> </b>
				</font>
				<input type='text' name='codigo_posto' size='8' value='<? echo $codigo_posto ?>'>
				<input type='submit' value='Listar' name='btn_listar_posto'>
			</TD>
		</tr>

		<tr>
			<td>&nbsp;</td>
		</tr><?php

	} ?>
	<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
		<td align='center' colspan='<?=$colspan?>'>
			<input type="button" value='<?= traduz("Continuar") ?>' onclick="javascript: if (document.frm_os_pesquisa.btn_acao_pesquisa.value == '' ) { document.frm_os_pesquisa.btn_acao_pesquisa.value='continuar' ; document.frm_os_pesquisa.submit() } else { alert ('Aguarde submissão') }" style='cursor: pointer' />

		</td>
	</tr>

	<tr><td>&nbsp;</td></tr>
</table>

<input type='hidden' name='btn_acao_pesquisa' value=''>
<input type='hidden' name='campo_aux' id='campo_aux' value='<?=$listar;?>'>
</table>
</form>

<?
$btn_acao_pesquisa = trim($_POST['btn_acao_pesquisa']);
$listar            = trim($_POST['listar']);
$sua_os            = trim($_POST['sua_os']);
$codigo_posto      = trim($_POST['codigo_posto']);

if (strlen($_GET['btn_acao_pesquisa']) > 0) $btn_acao_pesquisa = trim($_GET['btn_acao_pesquisa']);
if (strlen($_GET['listar']) > 0)            $listar            = trim($_GET['listar'])           ;
if (strlen($_GET['sua_os']) > 0)            $sua_os            = trim($_GET['sua_os'])           ;
if (strlen($_GET['codigo_posto']) > 0)      $codigo_posto      = trim($_GET['codigo_posto'])     ;

$posto = $login_posto;

if (strlen ($codigo_posto) > 0) {
	$sql = "SELECT posto FROM tbl_posto_fabrica WHERE codigo_posto = '$codigo_posto' AND fabrica = $login_fabrica";
	$res = pg_query ($con,$sql);
	$posto = pg_fetch_result ($res,0,0);
}

//	HD 135436(+Mondial)
if (usaDataConserto($login_posto, $login_fabrica) OR $login_fabrica == 1) {
	$sql_data_conserto=", to_char(tbl_os.data_conserto, 'DD/MM/YYYY HH24:MI' )	as data_conserto ";
}

if ( in_array($login_fabrica, array(11,172)) && $login_posto == 14301) {
	$sql_obs = " , tbl_os.consumidor_email ";
}

if ((strlen($sua_os) > 0 AND $btn_acao_pesquisa == 'continuar') OR strlen($listar) > 0 OR strlen ($codigo_posto) > 0 OR (strlen($prateleira_box) > 0 AND $btn_acao_pesquisa == 'continuar') OR (strlen($tipo_os) > 0 AND $btn_acao_pesquisa == 'continuar')) {
	// Ebano: removi este código e coloquei dentro do FOR para buscar o os_item

	if ($listar == 'consertadas' && in_array($login_fabrica, array(11,172)) ) {
		$fazer_paginacao = 'nao';
	}

	if ($login_posto == '4311' or $login_posto == '6359' or $login_posto == '14301') {
		$sql_add2 =", tbl_os.prateleira_box ";
	}

	if ($login_fabrica == 19) $sql_adiciona .= " AND tbl_os.consumidor_revenda = 'C' ";

	if ( in_array($login_fabrica, array(11,172)) && ($login_posto == '14301' || $login_posto == '6359')) {

		if (strlen ($prateleira_box) > 0) {
			$sql_adiciona .= " AND tbl_os.prateleira_box = '$prateleira_box'";
		}

	}

	if($_REQUEST['status_check']){
		$status_check = $_REQUEST['status_check'];

		if ($status_check == -1) {
			$cond_status_check =  ($login_fabrica == 1) ? "AND tbl_os.data_abertura + interval '60 days' <= current_date" : "AND tbl_os.data_abertura + interval '90 days' <= current_date";
		} else {
			$cond_status_check = " AND tbl_os.status_checkpoint = $status_check ";
		}

	}

	if ( strlen ($codigo_posto) == 0) {
		$sql_adiciona .= " AND tbl_os.posto = $login_posto ";
	} else {
		$sql_adiciona .= " AND tbl_posto_fabrica.codigo_posto = '$codigo_posto' AND (tbl_os.posto = $login_posto OR tbl_os.posto IN (SELECT posto FROM tbl_posto_linha WHERE distribuidor = $login_posto))";
	}

		//hd 45142
	if ($login_fabrica == 1) {
		$fazer_paginacao = 'nao';
	}
	if (in_array($login_fabrica, array(11,172)) && ($login_posto == 6940 || $login_posto == 4567 || $login_posto == 14236 || $login_posto == 1809 || $login_posto == 27401 || $login_posto == 6945 || $login_posto == 17674 || $login_posto == 5993 || $login_posto == 14254)) {
		$fazer_paginacao = 'nao';
	}

		//HD 18229

	if (strlen($sua_os) > 0) {

		$fazer_paginacao = 'nao';

		if ($login_fabrica == 1) {

			$pos    = strpos($sua_os, "-");
			$pos    = ($pos === false) ? strlen($sua_os) - 6 : $pos - 6;
			$sua_os = substr($sua_os, $pos, strlen($sua_os));

		}

		$sua_os = strtoupper($sua_os);
		$pos    = strpos($sua_os, "-");

		if(in_array($login_fabrica, array(52))){
			$sql_adiciona .= " AND tbl_os.sua_os = '$sua_os' ";
		}else{

			if ($pos === false) {
				if ($login_fabrica == 165) {
	            	$sql_adiciona .= (isset($novaTelaOs)) ? " AND tbl_os.sua_os ilike '$sua_os%' " : " AND tbl_os.os_numero ilike '$sua_os%' ";
				} else {
	            	$sql_adiciona .= (isset($novaTelaOs) || in_array($login_fabrica, array(11,172))) ? " AND tbl_os.sua_os ilike '$sua_os%' " : " AND tbl_os.os_numero = '$sua_os' ";
				}
			} else {

				$conteudo = explode("-", $sua_os);
				$os_numero    = $conteudo[0];
				$os_sequencia = $conteudo[1];

	            if ($login_fabrica <> 1 and $login_fabrica <> 7) {
	            	if(isset($novaTelaOs)){
						#$sql_adiciona .= " AND (tbl_os.sua_os = '$os_numero' or os_numero='$os_numero') AND tbl_os.os_sequencia = '$os_sequencia' ";
	            		$sql_adiciona .= "AND tbl_os.sua_os ilike '$sua_os%'";
	            	} else{
	            		$sql_adiciona .= " AND tbl_os.os_numero = '$os_numero' AND tbl_os.os_sequencia = '$os_sequencia' ";
	            	}

	            } else {
	                    //HD 9013 24484
	                $sql_adiciona .= " AND tbl_os.os_numero = '$os_numero' ";
	            }
			}
		}
	}

	if ( in_array($login_fabrica, array(11,172)) and ($login_posto == 6359 or $login_posto == 14301)) {
		$sql_order .= "ORDER BY data_abertura ASC ";
	} else if ($login_fabrica == 1 and $login_posto == 6359) {
		$sql_order .= "ORDER BY tbl_os.sua_os DESC, tbl_os.os DESC ";
	}else{
		 $sql_order .= "ORDER BY tbl_os.sua_os DESC, tbl_os.os DESC ";
	}


		if ($login_fabrica == 3) { // HD 53760 2/12/2008
			// Samuel retirou not in tbl_os_status pq atualizou todos os status_os_ultimo que estavam
			// diferente da tbl_os_status.
			$sql_os_cancelada = " AND (tbl_os.status_os_ultimo <> 126 OR tbl_os.status_os_ultimo IS NULL) and tbl_os.cancelada is not true ";
		}

		$sql_linha = " AND NOT (tbl_produto.linha = 549) ";

		if ($login_fabrica == 19) {
			$sql_linha = " AND NOT (tbl_produto.linha in (549, 928)) ";
		}

		//HD 214236: OS em auditoria não podem ser fechadas
		if ($login_fabrica == 14 || $login_fabrica == 43) {
			$sql_auditoria = "AND tbl_os.os NOT IN (SELECT DISTINCT os FROM tbl_os_auditar WHERE liberado IS FALSE AND cancelada IS FALSE AND tbl_os_auditar.os=tbl_os.os)";
		}
		if($login_fabrica == 6){
            $sql_auditoria = "AND tbl_os.os NOT IN (SELECT DISTINCT os FROM tbl_os_status WHERE status_os IN (189,190,191) AND os = tbl_os.os AND (SELECT status_os FROM tbl_os_status WHERE status_os IN (189,190,191) AND os = tbl_os.os ORDER BY data DESC LIMIT 1) IN (189,190)) ";
		}

		/**
		 *
		 * HD 749695 - Latinatec não listar OSs em auditoria (há mais de 60 dias)
		 *
		 */
		$extraCond = '';
		if ($login_fabrica == 15){
			$extraCond = "AND tbl_os.os not in (select distinct os from tbl_os_status where status_os in (120, 122, 123, 126) and os = tbl_os.os  and (select status_os from tbl_os_status where status_os in (120, 122, 123, 126) and os = tbl_os.os order by data desc limit 1) = 120)";
		}
		/**
		* ROWA não lista OS de Garantia para postos que não são internos ou revendas
		**/
		if (!$posto_interno_revenda && $login_fabrica == 163) {
			$joinTipoAtendimento = " join tbl_tipo_atendimento on tbl_os.tipo_atendimento = tbl_tipo_atendimento.tipo_atendimento
			and tbl_tipo_atendimento.fabrica = {$login_fabrica}
			and tbl_tipo_atendimento.fora_garantia is true";
		}

		if ($login_fabrica == 175){
			$joinTipoAtendimento = " join tbl_tipo_atendimento on tbl_os.tipo_atendimento = tbl_tipo_atendimento.tipo_atendimento
			and tbl_tipo_atendimento.fabrica = {$login_fabrica}
			and tbl_tipo_atendimento.fora_garantia is true";
		}

		if(in_array($login_fabrica, [167, 203])){
			$campo_atendimento_descricao = " tbl_tipo_atendimento.descricao AS descricao_tipo_atendimento , ";
			$joinTipoAtendimento = " join tbl_tipo_atendimento on tbl_os.tipo_atendimento = tbl_tipo_atendimento.tipo_atendimento
			and tbl_tipo_atendimento.fabrica = {$login_fabrica}";
		}

		if (in_array($login_fabrica,array(24,148))){
			$cond_data = " AND data_digitacao > '2013-09-30 00:00:00' ";
			$cond_cancelada = "AND tbl_os.cancelada IS NOT TRUE ";
		}

        if($login_fabrica == 30){
			$sql_estado = "
                        SELECT  tbl_posto_fabrica.contato_estado, tbl_posto_fabrica.parametros_adicionais
                        FROM    tbl_posto_fabrica
                        WHERE   tbl_posto_fabrica.posto     = $posto
                        AND     tbl_posto_fabrica.fabrica   = $login_fabrica
                    ";

                    $res_estado = pg_query($con,$sql_estado);
                    $resultContatoEstado = pg_fetch_result($res_estado,0,contato_estado);

                    $json_parametros_adicionais = pg_fetch_result($res_estado,0,parametros_adicionais);
                    $array_parametros_adicionais = json_decode($json_parametros_adicionais);

                    $posto_digita_os_consumidor = $array_parametros_adicionais->digita_os_consumidor;
					$posto_digita_os_consumidor = empty($posto_digita_os_consumidor) ? 'f': $posto_digita_os_consumidor;
            $WhereOs = (($posto_digita_os_consumidor && $posto_digita_os_consumidor != 't') ) ? "AND tbl_os.consumidor_revenda = 'R'" : "";
        }

		if (in_array($login_fabrica,array(74,151))){
			$leftJoinOsCampoExtra    = "LEFT JOIN tbl_os_campo_extra ON tbl_os_campo_extra.os = tbl_os.os";
			$cond_cancelada = "AND tbl_os_campo_extra.os_bloqueada IS NOT TRUE ";
		}

		//HD-3073983
		$cond_12_meses = "";
		if ($login_fabrica == 85 && strlen($sua_os) == 0) {
			$cond_12_meses = " AND tbl_os.data_abertura BETWEEN '".date("Y-m-d",strtotime("-12 Months"))."' AND '".date('Y-m-d')."' ";
		}

		if($login_fabrica == 42 ){
			$cond_12_meses = " AND tbl_os.data_abertura BETWEEN '".date("Y-m-d",strtotime("-6 Months"))."' AND '".date('Y-m-d')."' AND cancelada IS NULL ";
		}

		if (in_array($login_fabrica, array(169,170)) && !empty($tipo_os)) {
			$where_consumidor_revenda = " AND tbl_os.consumidor_revenda = '{$tipo_os}'";
		}

		if ($login_fabrica == 3) {

			$sql = "SELECT  tbl_os.os                                                  ,
			tbl_os.sua_os                                              ,
			tbl_os.status_checkpoint								   ,
			tbl_os.serie                                               ,
			tbl_os.cortesia                                            ,
			tbl_os.tipo_os_cortesia                                    ,
			tbl_produto.referencia                                     ,
			tbl_produto.produto                                        ,
			tbl_produto.descricao                                      ,
			tbl_produto.nome_comercial                                 ,
			tbl_os.tecnico              	                           ,
			to_char(tbl_os.data_abertura,'DD/MM/YYYY') AS data_abertura,
			tbl_os.consumidor_nome                                     ,
			tbl_os.consumidor_revenda                                  ,
			tbl_os.defeito_constatado                                  ,
			tbl_os.admin                                               ,
			tbl_os_extra.pac                                           ,
			tbl_os.tipo_atendimento
			$sql_add1
			$sql_add2
			$sql_data_conserto
			$sql_obs
			FROM    tbl_os
			JOIN    tbl_produto            USING (produto)
			JOIN    tbl_os_extra           USING (os)
			JOIN    tbl_posto_fabrica      ON tbl_os.posto = tbl_posto_fabrica.posto AND tbl_os.fabrica = tbl_posto_fabrica.fabrica
			WHERE   tbl_os.fabrica = $login_fabrica
			/* HD 204146: Fechamento automático de OS */
			AND    tbl_os.data_fechamento IS NULL
			AND    tbl_os.excluida        IS NOT TRUE
			$sql_adiciona
			$sql_linha
			$sql_os_cancelada
			UNION
			SELECT  tbl_os.os                                                  ,
			tbl_os.sua_os                                              ,
			tbl_os.status_checkpoint								   ,
			tbl_os.serie                                               ,
			tbl_os.cortesia                                            ,
			tbl_os.tipo_os_cortesia                                    ,
			tbl_produto.referencia                                     ,
			tbl_produto.produto                                        ,
			tbl_produto.descricao                                      ,
			tbl_produto.nome_comercial                                 ,
			tbl_os.tecnico                                			   ,
			to_char(tbl_os.data_abertura,'DD/MM/YYYY') AS data_abertura,
			tbl_os.consumidor_nome                                     ,
			tbl_os.consumidor_revenda                                  ,
			tbl_os.defeito_constatado                                  ,
			tbl_os.admin                                               ,
			tbl_os_extra.pac                                           ,
			tbl_os.tipo_atendimento
			$sql_add1
			$sql_add2
			$sql_data_conserto
			$sql_obs
			FROM    tbl_os
			JOIN    tbl_produto            USING (produto)
			JOIN    tbl_os_extra           USING (os)
			JOIN    tbl_posto_fabrica      ON tbl_os.posto = tbl_posto_fabrica.posto AND tbl_os.fabrica = tbl_posto_fabrica.fabrica
			WHERE   tbl_os.fabrica = $login_fabrica
			/* HD 204146: Fechamento automático de OS */
			AND    tbl_os.sinalizador = 18
			AND    tbl_os.excluida        IS NOT TRUE
			$sql_adiciona
			$sql_linha
			$sql_os_cancelada
			ORDER BY 2 DESC, 1 DESC";

		} else {

			if (in_array($login_fabrica, array(141,144)) && $posto_interno) {
				$columnClassificacaoOs   = ", LOWER(tbl_classificacao_os.descricao) AS classificacao_os";
				$columnRemanufatura      = ", (CASE WHEN tbl_os_campo_extra.campos_adicionais ~* E'\"os_remanufatura\":\"t\"' THEN TRUE ELSE FALSE END) AS remanufatura";
				$leftJoinClassificacaoOs = "LEFT JOIN tbl_classificacao_os ON tbl_classificacao_os.classificacao_os = tbl_os_extra.classificacao_os";
				$leftJoinOsCampoExtra    = "LEFT JOIN tbl_os_campo_extra ON tbl_os_campo_extra.os = tbl_os.os";
			}
// echo ">>".$posto_interno."-->".$login_unico_master;
            if ($login_fabrica == 162 && $posto_interno && strlen($login_unico_tecnico) > 0 && !$login_unico_master) {
                $whereTecnico = " AND tbl_os.tecnico = $login_unico_tecnico";
            }

            if(in_array($login_fabrica, array(152,180,181,182))){
            	$sql_os_cancelada = "AND ( tbl_os.cancelada <> 't' OR tbl_os.cancelada is null )";
            }


            if($login_fabrica == 120 or $login_fabrica == 201){
            	$sql_os_cancelada_newmaq = " AND (tbl_os.cancelada IS NOT TRUE OR tbl_os.cancelada is null)";
            }

			if (isset($novaTelaOs)) {
				$distinctOs = "DISTINCT ON(tbl_os.os) tbl_os.os,";
				$sql_order  = "ORDER BY tbl_os.os";
			} else {
				$distinctOs = "DISTINCT tbl_os.os,";
			}

			if (in_array($login_fabrica, array(74,169,170))) {
				$campo_hd_chamado = ", tbl_os.hd_chamado ";
			}

			if ($login_fabrica == 1) {
				$estoque_posto_campo = "tbl_posto_fabrica.reembolso_peca_estoque, ";
			}

			$sql = "SELECT {$distinctOs}
			tbl_os.sua_os                                              ,
			tbl_os.status_checkpoint                                   ,
			tbl_os.fabrica                                             ,
			tbl_os.serie                                               ,
			tbl_os.cortesia                                            ,
			tbl_os.obs                                                 ,
			tbl_posto_fabrica.codigo_posto 							   ,
			tbl_os.tipo_os_cortesia                                    ,
			$estoque_posto_campo
			tbl_os.nota_fiscal_saida,
			{$campo_atendimento_descricao}
			tbl_os.data_nf_saida,";
			if(!isset($novaTelaOs)){
				$sql .= "tbl_linha.nome AS linha_nome,";
			}
            $sql .= "tbl_produto.produto                                         ,
			tbl_produto.referencia                                       ,
            tbl_produto.descricao                                       ,";
			if(isset($novaTelaOs)){
				$sql .= "
				tbl_produto.nome_comercial                             ,";
			}
			
			$case_90_dias = ($login_fabrica == 1) ? "CASE
														WHEN tbl_os.data_abertura + interval '60 days' <= current_date THEN
														'sim'
														ELSE
														'nao'
														END AS aberta_90_dias                                      ," : 
														"CASE
															WHEN tbl_os.data_abertura + interval '90 days' <= current_date THEN
															'sim'
															ELSE
															'nao'
															END AS aberta_90_dias                                      ,";

			$sql .="
				to_char(tbl_os.data_abertura,'DD/MM/YYYY') AS data_abertura,
					tbl_os.data_abertura AS abertura,
			tbl_os.data_digitacao,
			$case_90_dias
			tbl_os.consumidor_nome                                     ,
			tbl_os.consumidor_revenda                                  ,
			tbl_os.defeito_constatado                                  ,
			tbl_os.admin                                               ,
			tbl_os.finalizada                                          ,
			tbl_os_extra.pac                                           ,
			TO_CHAR(tbl_os_extra.inicio_atendimento, 'DD/MM/YYYY HH24:MI:SS') AS inicio_atendimento,
			TO_CHAR(tbl_os_extra.termino_atendimento, 'DD/MM/YYYY HH24:MI:SS') AS termino_atendimento,
			tbl_os_extra.regulagem_peso_padrao,
			tbl_os.tipo_atendimento                                    ,
			CASE
			WHEN tbl_os.cortesia IS TRUE THEN
			'Cortesia'
			WHEN tbl_os.tipo_atendimento = 35 THEN
			'Troca cortesia'
			WHEN tbl_os.consumidor_revenda = 'C' AND (tbl_os.tipo_atendimento = 17 OR tbl_os.tipo_atendimento = 18) THEN
			'Troca consumidor'
			WHEN tbl_os.consumidor_revenda = 'R' AND (tbl_os.tipo_atendimento = 17 OR tbl_os.tipo_atendimento = 18) THEN
			'Troca de revenda'
			WHEN tbl_os.consumidor_revenda = 'R' THEN
			'Revenda'
			ELSE
			'Consumidor'
			END AS tipo_os
			$sql_add1
			$sql_add2
			$sql_data_conserto
			$sql_obs
			$columnClassificacaoOs
			$columnRemanufatura
			{$campo_hd_chamado}
			FROM    tbl_os ";

			if(isset($novaTelaOs)){
				$sql .= " JOIN tbl_os_produto ON tbl_os_produto.os = tbl_os.os
						  JOIN tbl_produto ON tbl_produto.produto = tbl_os_produto.produto ";
			}else{
				$sql .= " JOIN    tbl_produto            USING (produto) JOIN tbl_linha USING(linha) ";
			}

			$cond_pesquisa_fabrica = (in_array($login_fabrica, array(11,172))) ? " tbl_os.fabrica IN (11,172) " : " tbl_os.fabrica = $login_fabrica ";

			if ($login_fabrica == 177){
				$sql .= " LEFT JOIN tbl_os_extra ON tbl_os_extra.os = tbl_os.os " ;
			}else{
				$sql .= "JOIN    tbl_os_extra           ON tbl_os_extra.os = tbl_os.os " ;
			}
			$sql .= "JOIN    tbl_posto_fabrica      ON tbl_os.posto = tbl_posto_fabrica.posto AND tbl_os.fabrica = tbl_posto_fabrica.fabrica
			$joinTipoAtendimento
			$joinAuditoria
			$leftJoinClassificacaoOs
			$leftJoinOsCampoExtra
			WHERE {$cond_pesquisa_fabrica}
			$whereAuditoriaGrohe
            $WhereOs
            $whereTecnico
            ";
			if ( in_array($login_fabrica, array(11,172)) and $listar == "consertadas") { #HD 346804 - Listar os Consertadas para Lenoxx
				$sql .= "
				AND tbl_os.consumidor_revenda='R'
				AND tbl_os.data_conserto IS NOT NULL
				";
			}
			if ($login_fabrica == 86 ){
				#nao listar os sem defeito constatado.HD-1938192
				$sql .= " AND tbl_os.defeito_constatado IS NOT NULL ";
			}

			$sql .= "
			/* HD 204146: Fechamento automático de OS */
			AND    tbl_os.data_fechamento IS NULL
			AND    tbl_os.excluida        IS NOT TRUE
			$where_consumidor_revenda
			$extraCond
			$cond_data
			$cond_status_check
			$cond_12_meses
			/*hd- 2107401 Os canceladas */
			$cond_cancelada
			/*HD 214236: OS em auditoria não podem ser fechadas*/
			$sql_auditoria
			$sql_adiciona
			$sql_linha
			$sql_os_cancelada_newmaq
			$sql_os_cancelada
			$sql_order
			";
		}
		$res = pg_query($con, $sql);
		$sqlCount  = "SELECT count(*) FROM (";
			$sqlCount .= $sql;
			$sqlCount .= ") AS count";

 		// ##### PAGINACAO ##### //
		require "_class_paginacao.php";
		/* Alterado HD 44973 - Colocado número da Página */
		// definicoes de variaveis
		$max_links = 15;// máximo de links à serem exibidos
		$max_res   = 50;// máximo de resultados à serem exibidos por tela ou pagina

		/* Nos casos de busca por OS, mostrar paginacao longa, pois a Black precisa mostrar todas as OS na mesma tela  */
		if ($fazer_paginacao == 'nao') {
			$max_res   = 3000;
		}

		$mult_pag  = new Mult_Pag();// cria um novo objeto navbar
		$mult_pag->num_pesq_pag = $max_res; // define o número de pesquisas (detalhada ou não) por página

		$res = $mult_pag->executar($sql, $sqlCount, $con, "otimizada", "pgsql");
// echo pg_num_rows($res);

		if (pg_num_rows($res) > 0) {
            if($login_fabrica == 6){
                $enctype = " enctype='multipart/form-data' ";
            }
			echo "<form name='frm_os' method='post' action='$PHP_SELF' $enctype>";
	    echo "<div id='layout'>";
	    		if(!in_array($login_fabrica,array(177))){
				echo "<div class='subtitulo'>";
				fecho ("com.o.fechamento.da.os.voce.se.habilita.ao.recebimento.dos.valores.que.serao.pagos.no.proximo.extrato", $con, $cook_idioma);
				echo "</div>";
			}
			if (in_array($login_fabrica, array(171))) {
				echo "<div class='subtitulo'>Para aprovação do fechamento é necessário anexar a assinatura do cliente na Ordem de Serviço</div>";
			}
			echo "</div>";
			echo "<table width='700' border='0' cellpadding='0' cellspacing='0' align='center'>";
			echo "<tr>";
			echo "<td><img height='1' width='20' src='imagens/spacer.gif'></td>";
			echo "<td valign='top' align='center'>";

			if($login_fabrica == 1){

				$sqlOS =	"SELECT tbl_os.os
				FROM tbl_os
				JOIN tbl_produto ON tbl_produto.produto = tbl_os.produto AND tbl_produto.fabrica_i=$login_fabrica
				LEFT JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_os.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
				WHERE tbl_os.fabrica = $login_fabrica
				AND   tbl_os.posto   = $login_posto
				AND   (tbl_os.data_abertura + INTERVAL '90 days') <= current_date
				AND   tbl_os.data_fechamento IS NULL
				$extraCond
				$sql_cond
				AND  tbl_os.excluida is FALSE LIMIT 3";

				$resOS = pg_query($con,$sqlOS);

				if (pg_num_rows($resOS) > 0) {
					echo "<table width='700' border='1' cellpadding='2' cellspacing='0' style='background-color:#FFA500; border-collapse: collapse; text-align:justify; font-weight:bold;>";
					echo "<tr class='Titulo' height='15' >";
					echo "<td colspan='3'>";
					echo traduz("O SEU POSTO DE SERVIÇOS POSSUI OS'S ABERTAS HÁ MAIS DE 30 DIAS QUE NÃO FORAM FINALIZADAS. SE ESSAS OS'S NÃO FOREM FECHADAS NUM PRAZO DE ATÉ 60 DIAS A SUA TELA SERÁ BLOQUEADA PARA CADASTRO DE NOVAS OS'S. SE HOUVER ALGUMA O.S COM PENDÊNCIA OU DÚVIDA QUE PRECISE DO NOSSO AUXÍLIO SOLICITAMOS QUE ABRA UM CHAMADO PARA O SEU SUPORTE. <a href='os_consulta_avancada.php?btn_acao=listar_90' style='color:#FF0000;' target='_blank'>CLIQUE AQUI</a> PARA VISUALIZAR ESSAS OS'S.");
					echo "</td>";
					echo "</tr>";
					echo "</table>";

				}
					##### OS QUE EXCEDERAM O PRAZO LIMITE DE 90 DIAS PARA FECHAMENTO #####
			}

			##### LEGENDAS - INÍCIO - HD 234532 #####
			/*
			 0 | Aberta Call-Center               | #D6D6D6
             1 | Aguardando Analise               | #FF8282
             2 | Aguardando Peças                 | #FAFF73
             3 | Aguardando Conserto              | #EF5CFF
             4 | Aguardando Retirada              | #9E8FFF
             9 | Finalizada                       | #8DFF70
             13| Pedido Cancelado                 | #EE9A00
			*/

			#Se for Bosh Security modificar a condição para pegar outros status também.
             if ($login_fabrica == 96) {
             	$condicao_status = '0,1,2,3,5,6,7';
			} else if ($login_fabrica == 1) {//HD 424292
				$condicao_status = '1,3,4';
			} else if(in_array($login_fabrica, array(141))){
				$condicao_status = '0,1,14,2,8,11,3,10,12,9,29';
            } else if ($login_fabrica == 165 && $posto_interno) {
				$condicao_status = '0,1,14,2,8,11,3,12,4,9,29,30';
			} else if($login_fabrica == 144){
				$condicao_status = '0,1,14,2,8,11,3,10,4,9';
			}elseif($login_fabrica == 131){
				$condicao_status = '0,1,2,3,4,9,13';
			}else {
				$condicao_status = '0,1,2,3,4,8';
			}
			$sqlPostoInterno = "SELECT posto_interno FROM tbl_posto_fabrica JOIN tbl_tipo_posto USING (tipo_posto) WHERE posto = {$posto} AND tbl_tipo_posto.fabrica = {$login_fabrica}";
			$resPostoInterno = pg_query($con, $sqlPostoInterno);
			$postoInterno = pg_fetch_result($resPostoInterno, 0, posto_interno);
			if (in_array($login_fabrica, [174]) && $postoInterno == 't') {
				$condicao_status .= ',40,41,42,43';
			}

			$where_status = '';
			if ($login_fabrica == 30) {
				$where_status = "AND fabricas isnull OR {$login_fabrica} = any(fabricas)";
			}

			if (in_array($login_fabrica, array(169, 170))) {
				$condicao_status = "0,1,2,3,4,8,9,14,28,30,45,46,47,48,49,50";

				$ordemStatus = "
					, CASE WHEN status_checkpoint = 0 THEN 0
					WHEN status_checkpoint = 1 THEN 1
					WHEN status_checkpoint = 2 THEN 2
					WHEN status_checkpoint = 8 THEN 3
					WHEN status_checkpoint = 45 THEN 4
					WHEN status_checkpoint = 46 THEN 5
					WHEN status_checkpoint = 47 THEN 6
					WHEN status_checkpoint = 3 THEN 7
					WHEN status_checkpoint = 4 THEN 8
					WHEN status_checkpoint = 14 THEN 9
					WHEN status_checkpoint = 30 THEN 10
					WHEN status_checkpoint = 9 THEN 11
					WHEN status_checkpoint = 48 THEN 12
					WHEN status_checkpoint = 49 THEN 13
					WHEN status_checkpoint = 50 THEN 14
					WHEN status_checkpoint = 28 THEN 15 END AS ordem
				";
				$orderByStatus = "ORDER BY ordem ASC";
			} else if ($telecontrol_distrib) {
	            $ordemStatus = "
	               ,CASE WHEN status_checkpoint = 1 THEN 0
	                WHEN status_checkpoint = 37 THEN 1
	                WHEN status_checkpoint = 35 THEN 2
	                WHEN status_checkpoint = 2 THEN 3
	                WHEN status_checkpoint = 36 THEN 4
	                WHEN status_checkpoint = 3 THEN 5
	                WHEN status_checkpoint = 4 THEN 6
	                WHEN status_checkpoint = 9 THEN 7
	                WHEN status_checkpoint = 0 THEN 8
	                WHEN status_checkpoint = 39 THEN 9
	                END AS ordem
	            ";
	            $orderByStatus = "ORDER BY ordem ASC";
	        }
			
			if (in_array($login_fabrica, array(177))) {
				$condicao_status .= ",14";
			}

			$sql_status = "SELECT status_checkpoint,descricao,cor $ordemStatus FROm tbl_status_checkpoint WHERE status_checkpoint IN (".$condicao_status.") {$where_status} $orderByStatus";

			$res_status = pg_query($con,$sql_status);
			$total_status = pg_num_rows($res_status);?>
			<style>
				.status_checkpoint{width:15px;height:15px;margin:2px 5px;padding:0 5px;border:1px solid #666;}
				.status_checkpoint_sem{width:15px;height:15px;margin:2px 5px;padding:0 5px;}
			</style>
			<div align='left' style='position: relative; left: -2'>
				<br>
				<table border='0' width='700'>
					<tr>
						<td>
							<table border='0' cellspacing='0' cellpadding='0'>
								<?php
								for($i=0;$i<$total_status;$i++){

									$id_status = pg_fetch_result($res_status,$i,'status_checkpoint');
									$cor_status = pg_fetch_result($res_status,$i,'cor');
									$descricao_status = pg_fetch_result($res_status,$i,'descricao');

                                    if ($login_fabrica == 165 && $posto_interno == true) {
                                        switch ($descricao_status) {
                                            case "Aguardando Faturamento":
                                                $descricao_status = "Aguardando Expedição";
                                                break;
                                            default:
                                                $descricao_status = $descricao_status;
                                                break;
                                        }
                                    }

								#Array utilizado posteriormente para definir as cores dos status
									$array_cor_status[$id_status] = $cor_status;
									?>

									<tr height='25px'>
										<td width='18px' height='20px'>
											<span class="status_checkpoint" style="background-color:<?php echo $cor_status;?>">&nbsp;</span>
										</td>
										<td align='left'>
											<font size='1'>
												<b>
													<a href="javascript:void(0)" onclick="filtrar(<?php echo $id_status;?>);">
														<?php echo $descricao_status.'';?>
													</a>
												</b>
											</font>
										</td>
									</tr>
									<?php }?>

									<tr height='30px'>
										<td width='18' >
											<span class="status_checkpoint">&nbsp;</span>
										</td>
										<td align='left'>
											<font size='1'>
												<b>
													<a href="javascript:void(0)" onclick="filtrar(-1);">
														Listar Todos
													</a>
												</b>
											</font>
										</td>
									</tr>

								</table>
							</td>

							<?php if($login_fabrica == 1){?>
							<td>
								<table border='0' cellspacing='0' cellpadding='0' >
									<tr height='25px' valign='top'>
										<td width='18px' height='20px'>
											<span class="status_checkpoint" style="background-color:#8B3A3A">&nbsp;</span>
										</td>
										<td align='left'>
											<font size='1'>
												<b>
													<a href="javascript:void(0)" onclick="filtrar(-1);">
														<?= traduz('OS aberta a mais de 60 dias') ?>
													</a>
												</b>
											</font>
										</td>
									</tr>
								</table>
							</td>
							<?php }?>
						</tr>
					</table>
				</div>

<?php
            $data_fechamento = (in_array($login_fabrica, array(11,30,172))) ? date("d/m/Y") : ""; //HD 13239

			if (in_array($login_fabrica,array(11,30,172))) {
				$readonly_data_abertura = 'readonly="readonly"';
			} else {
				$readonly_data_abertura = '';
			}
?>

			<!-- ------------- Formulário ----------------- -->
			<input type='hidden' name='qtde_os' value='<? echo pg_num_rows ($res); ?>'>
			<input type='hidden' name='sua_os' value='<? echo $sua_os; ?>'>
			<input type='hidden' name='tipo_os' value='<? echo $tipo_os; ?>'>
			<input type='hidden' name='btn_acao_pesquisa' value='<? echo $btn_acao_pesquisa ?>'>
			<input type='hidden' name='listar' value='<? echo $listar ?>'>
<?php
            if ($login_fabrica == 1) {
?>
            <input type='hidden' name='enviar_email_pesquisa' id="enviar_email_pesquisa" value=''>
<?php
            }
            if ($login_fabrica == 165 && $posto_interno == true) {
?>
<div id="loading-block" style="width:100%;height:100%;position:fixed;left:0px;top:0px;text-align:center;vertical-align: middle;background-color:#000;opacity:0.3;display:none;z-index:10" >
</div>
<div id="loading" style="display: none;" >
	<img src="admin/imagens/loading_img.gif" style="z-index:11" />
	<div style="position:fixed;top:0;left:0;width:100%;height:100%;z-index:10000;"></div>
</div>
<?php
            }

            if (in_array($login_fabrica, array(169,170)) || ($login_fabrica == 173 && $posto_interno != true)) {
?>
				<div style="padding: 10px 5px !important; background-color: #fcf8e3 !important; border: 1px solid #fbeed5 !important;" >
					<h1 style="color: #c09853 !important;" >É obrigatório anexar a nota fiscal do produto e a O.S assinada pelo consumidor</h1>
				</div>
				<br />
<?php
			}
            if (!in_array($login_fabrica, array(167,176,203))) {
                $width_data_fechamento = ($login_fabrica != 85) ? 530 : 200;
                if ($login_fabrica != 173 || ($login_fabrica == 173 && $posto_interno == true)) {
                	$xwidth = 700; 
                	if ($login_fabrica == 123) {
                		$xwidth = 730; 
                	}
?>

				<TABLE width="<?=$xwidth?>" border="0" cellpadding="2" cellspacing="0" align="center" >
					<tr>
						<TD width='120' class="fechamento">
							<b>
								<? fecho ("data.de.fechamento", $con, $cook_idioma); ?>
                		</TD>
                    	<TD nowrap  width='<?=$width_data_fechamento?>' class="fechamento_content">
                        		&nbsp;&nbsp;&nbsp;&nbsp;
                        <?php

                        if (is_array($msg_erro)) {

                        	$data_fechamento = (count($msg_erro)  && !empty($_POST['data_fechamento'])) ? $_POST['data_fechamento'] : "";

                        } else {

                        	$data_fechamento = (!empty($msg_erro) && !empty($_POST['data_fechamento'])) ? $_POST['data_fechamento'] : "";

                        }

						?>
                        <input class="frm" type='text' name='data_fechamento' <?=$readonly_data_abertura?> size='12' maxlength='10' value='<?=$data_fechamento ?>' />
                    </TD>
<?php
                if ($login_fabrica == 85) {
?>
                    <td width='120' class="fechamento">
<?php
                        fecho ("hora.fechamento", $con, $cook_idioma);
?>
                    </td>
                    <td class="fechamento_content">
                        &nbsp;&nbsp;&nbsp;&nbsp;
                        <input class="frm" type="text" name="hora_fechamento" id="hora_fechamento" size="8" maxlength="5" value="<?=$hora_fechamento?>" />
                    </td>
<?php
                }

?>

					</TR>
				</TABLE>
			<?php
				}
			}
			if ($login_fabrica == 165 && $posto_interno == true) {?>
				<table width="100%" border="1" cellpadding="1" cellspacing="1" align="center" >
					<tr>
						<td class="txt_acao_massa" width="10%">Ações em massa:</td>
						<td class="ctx_acao_massa"><?php fecho ("data.de.conserto", $con, $cook_idioma);?>:</td>
						<td class="ctx_acao_massa" width="12%">
							<input class='frm inputs_acao_massa' type='text' id='lote_data_conserto' name='lote_data_conserto' rel='lote_data_conserto' maxlength='18' />
						</td>
						<td class="ctx_acao_massa"><?php fecho ("nf.saida", $con, $cook_idioma);?>:</td>
						<td class="ctx_acao_massa">
							<input class='frm inputs_acao_massa' type='text' rel='lote_nota_fiscal_saida' name='lote_nota_fiscal_saida' maxlength='10'>
						</td>
						<td class="ctx_acao_massa" ><?php fecho ("data.nf.saida", $con, $cook_idioma);?>:</td>
						<td class="ctx_acao_massa">
							<input class='frm inputs_acao_massa' type='text' name='lote_data_nf_saida'  onKeyUp="formata_data(this.value,'frm_os', 'lote_data_nf_saida');"  maxlength='10'>
						</td>
						<td class="ctx_acao_massa"><?php fecho ("tipo.de.entrega", $con, $cook_idioma);?>:</td>
						<td class="ctx_acao_massa" width="10%">
						<?php
							$sqlPac = "SELECT pac FROM tbl_os_extra WHERE os = {$os}";
							$resPac = pg_query($con, $sqlPac);

							$codigo_rastreio = pg_fetch_result($resPac, 0, "pac");

							echo "<select id='lote_tipo_entrega' class='inputs_acao_massa lote_tipo_entrega' name='lote_tipo_entrega'>";
								if (isset($_POST["lote_tipo_entrega"])) {
									$tipo_entrega = $_POST["lote_tipo_entrega"];
								} else {
									$tipo_entrega = "";
								}

								if ($codigo_rastreio == "balcão") {
									$tipo_entrega = "balcão";
								} else if (!empty($codigo_rastreio) || $codigo_rastreio == "sem_rastreio") {
									$tipo_entrega = "correios";
								}
							echo "
								<option value='' selected >Tipo de Entrega</option>
								<option value='balcão' ".(($tipo_entrega == "balcão") ? "selected" : "")." >Balcão</option>
								<option value='correios' ".(($tipo_entrega == "correios") ? "selected" : "")." >Correios</option>";

								switch ($tipo_entrega) {
									case 'balcão':
										$codigo_rastreio    = "balcão";
										$classInputReadonly = "input_readonly";
										$inputReadonly      = "readonly='readonly'";
										break;

									case 'correios':
										if (empty($codigo_rastreio)) {
											$codigo_rastreio = $_POST["lote_codigo_rastreio"];
										}
										$classInputReadonly = "";
										$inputReadonly      = "";
										break;

									default:
										$codigo_rastreio = "";
										$classInputReadonly = "input_readonly";
										$inputReadonly      = "readonly='readonly'";
										break;
								}

							echo "</select>";
						?>
						</td>
						<td class="ctx_acao_massa"><?php fecho ("código.de.rastreio", $con, $cook_idioma);?>:</td>
						<td class="ctx_acao_massa">
							<input id='lote_codigo_rastreio' class='frm inputs_acao_massa <?php echo $classInputReadonly;?>' type='text' name='lote_codigo_rastreio' style='width: 100px;' maxlength='13' value='<?php echo $codigo_rastreio;?>' <?php echo $inputReadonly;?> />
						</td>
						<td>
							<button type='button' class="btn_copia_lote">Copiar</button>
						</td>
					</tr>
				</table>
			<?php
			}?>
			<table  border="0" cellspacing="1" cellpadding="4" align="center" style='width : <?=$xwidth?> !important; font-family: verdana; font-size: 10px' class='tabela_resultado Relatorio'>
				<input type='hidden' id="status_check" value="<?=$status_check?>" name='status_check'>
				<!-- class='tabela_resultado sample'-->
				<?php		//HD 9013
			if($login_fabrica==1 or $login_fabrica ==7){?>
				<caption colspan='100%' style='font-family: verdana; font-size: 20px'><? fecho ("os.de.consumidor", $con, $cook_idioma); /*OS de Consumidor*/?></font><caption>
			<?}?>
			<thead>
				<tr height="20px">
					<?php
					if (!in_array($login_fabrica, array(167,176,203))) { 
						if ($login_fabrica != 173 || ($login_fabrica == 173 && $posto_interno == true)) {
						?>
							<th style="width: 30px; text-align: center" <?= $login_fabrica == 24 ? 'colspan="2"' : ''?>>
								<input type='checkbox' class='frm' name='marcar' value='tudo' title='<? fecho ("selecione.ou.desmarque.todos", $con, $cook_idioma); /*Selecione ou desmarque todos*/?>' onClick='SelecionaTodos(this.form.ativo);' style='cursor:pointer;'>
							</th>
						<?php
						}
					}
					if ($login_fabrica == 24) { ?>
						<th>  </th>
					<?php }
					?>
					<th width="100px"><? fecho ("os", $con, $cook_idioma); /*OS*/ echo " "; if($login_fabrica<>20){ fecho ("fabricante", $con, $cook_idioma); /*Fabricante*/ } ?></th>
					<? //HD 23623 ?>
					<? if ( in_array($login_fabrica, array(11,172)) and $login_posto==14301){ ?><th nowrap><? fecho ("box", $con, $cook_idioma);
					echo "/";
					fecho ("prateleira", $con, $cook_idioma);

					if ($login_fabrica == 176){
						$th_width = "width='420px'";
					}else{
						$th_width = "width='100px'";
					}

					/*Box/Prateleira*/ ?></th><?}?>
					<th width="100px"><? fecho ("data.abertura", $con, $cook_idioma); /*if($sistema_lingua == 'ES') echo "Fecha Abertura";else echo "Data Abertura";*/?></th>
					<th width="100px"><? fecho ("consumidor", $con, $cook_idioma);/*if($sistema_lingua == 'ES') echo "Usuário";else echo "Consumidor";*/?></th>
					<th <?=$th_width?> ><? fecho ("produto", $con, $cook_idioma); /*if($sistema_lingua == 'ES') echo "Producto";else echo "Produto";*/?></th>
					<?php if($login_fabrica == 3){?>
					<th nowrap><? fecho ("tecnico", $con, $cook_idioma); /*Produto*/ ?></th>
					<?php } ?>
					<? if ($login_fabrica == 15){ ?><th nowrap><? fecho ("n.serie.reoperado", $con, $cook_idioma); /*N. Série Reoperado*/?></th><?}?>
					<? if (in_array($login_fabrica, array(30,85,173)) ){ ?><th nowrap><? fecho ("n.serie", $con, $cook_idioma); /*N. Série*/ ?></th><?}?>
					<? if (in_array($login_fabrica, array(173)) ){ ?><th nowrap><? fecho ("cod.barra", $con, $cook_idioma); /*Cód. Barra.*/ ?></th><?}?>
					<? if ((!in_array($login_fabrica, array(1,2,20,141,147,157,160,158,156,161,164,165,169,170,173,171,174,176,177,180,181,182)) and !$replica_einhell) || ($login_fabrica == 137 && !$posto_interno) || ($login_fabrica == 165 and $posto_interno)){ ?>
						<th width="100px"><? fecho ("nf.saida", $con, $cook_idioma); /*NF de Saída*/ ?></th>
						<th width="100px"><? fecho ("data.nf.saida", $con, $cook_idioma); /*Data NF de Saída*/ ?></th>
					<?php
					}

					if (in_array($login_fabrica, [177])) { ?>
						<th width="100px"><?= traduz('Lote nova peça') ?></th>
					<?php
					}

					if(in_array($login_fabrica, array(141)) && $posto_interno == true){
					?>
						<th width="100px"><?= traduz('NF de Saida') ?></th>
						<th width="100px"><?= traduz('Data NF de Saida') ?></th>
					<?php
					}

					if ($login_fabrica == 156 && $posto_interno == true) {
					?>
						<th><?= traduz('NF de Retorno') ?></th>
						<th><?= traduz('Data NF de Retorno') ?></th>
						<th><?= traduz('Valor NF de Retorno') ?></th>
					<?php
					}

					if ($login_fabrica == 158) {
					?>
						<th><?= traduz('Início Atendimento') ?></th>
						<th><?= traduz('Fim Atendimento') ?></th>
					<?php
					}

					if (in_array($login_fabrica, array(141,144,165)) && $posto_interno === true) {
						echo "
						<th>".traduz('Tipo Entrega')."</th>
						<th>".traduz('Código de Rastreio')."</th>
						";
					}

					if (in_array($login_fabrica, array(141,144)) && $posto_interno === true) {
						echo "
						<th>".traduz('Remanufaturar')."</th>
						";
					}

					if($login_fabrica == 137 && $posto_interno){
						?>
						<th width="100px"><? fecho ("lote", $con, $cook_idioma); ?></th>
						<th width="100px"><? fecho ("defeito.constatado", $con, $cook_idioma); ?></th>
						<?php
					}

					?>
					<?if($login_fabrica==20){?>
					<th width="100px"><? fecho ("valor.das.pecas", $con, $cook_idioma); /*if($sistema_lingua=='ES')echo "Valor de Piezas";else echo "Valor das Peças";*/?>
					</th>
					<th width="100px"><? fecho ("mao.de.obra", $con, $cook_idioma); /*if($sistema_lingua=='ES')echo "Mano de Obra";else echo "Mão-de-Obra";*/?>
					</th>
					<? } ?>
					<?

					if(($login_posto=='4311') && $login_fabrica != 137 and !isset($novaTelaOs)) {
						echo "<th nowrap>";
						fecho ("box", $con, $cook_idioma);
						echo "</th>";
					}
					if($login_fabrica == 6){
?>
                    <th><?= traduz('Tem anexo') ?></th>
                    <th><?= traduz('Anexo NF') ?></th>
<?
					}
			//HD 12521       HD 13239    HD 14121   HD 135436(+Mondial))
					if (in_array($login_fabrica, array(169,170,174))){
						$th_span = "colspan='2'";
					}

					if(usaDataConserto($login_posto, $login_fabrica) && !in_array($login_fabrica, array(171))) {
						if ($login_fabrica != 173 || ($login_fabrica == 173 && $posto_interno == true)) {
							echo "<th nowrap $th_span>";
							fecho ("data.de.conserto", $con, $cook_idioma); /*Data de conserto*/
							echo "</th>";
						}
						
						// Retirada 160 HD-6796695
						if (in_array($login_fabrica, [123])) {
							echo "<th nowrap $th_span>";
							fecho ("anexar.termo", $con, $cook_idioma); /*Data de conserto*/
							echo "</th>";	
						}
					}

					if (in_array($login_fabrica, array(173)) && $posto_interno != true) {
						echo "<th nowrap $th_span>";
						fecho ("data.de.fechamento", $con, $cook_idioma); /*Data de Fechamento*/
						echo "</th>";
					}


					if (in_array($login_fabrica, array(120,201,174))) {
						echo "<th nowrap>";
						fecho ("Ações", $con, $cook_idioma); /*Ações - somente newmaq*/
						echo "</th>";
					}

                    if ($login_fabrica == 156 and $posto_interno == true) {
                        echo "<th>".traduz('Data da liberação do produto')."</th>";
                    }

					if(in_array($login_fabrica, array(165)) && $posto_interno == true){
						echo "<th>Upload</th>";
					}

			//HD 180939 (Dynacon)
					if($login_fabrica == 2) {
						echo "<th nowrap>";
						fecho ("Número PAC", $con, $cook_idioma); /*Número do PAC-Correios*/
						echo "</th>";
					} ?>

					<?php
					if($login_fabrica == 52){
						echo "<th nowrap  align='center'>";
						fecho ("valores da os", $con, $cook_idioma); /*Valores da OS*/
						echo "</th>";
					}
					?>
					<?php
					if($login_fabrica == 1){
						echo "<th nowrap  align='center'>";
						fecho ("tipo.os", $con, $cook_idioma); /*Valores da OS*/
						echo "</th>";
					}
					?>
				</tr>
			</thead>

			<tbody><?php
				$total_os = pg_num_rows($res);

				$sql_dc = "SELECT defeito_constatado, codigo, descricao FROM tbl_defeito_constatado WHERE fabrica = {$login_fabrica} AND ativo";
				$res_dc = pg_query($con, $sql_dc);
				$array_dc = pg_fetch_all($res_dc);

				for ($i = 0; $i < $total_os; $i++) {

					if ( in_array($login_fabrica, array(11,96,172,179)) AND strlen($msg_ok) == 0) {
						$data_nf_saida     = $_POST['data_nf_saida_' . $i];
						$nota_fiscal_saida = $_POST['nota_fiscal_saida_' . $i];
						$valor_fiscal_saida = $_POST['valor_fiscal_saida_' . $i];
					}

					if(in_array($login_fabrica,array(156,165)) AND $posto_interno==true){
						$nf_retorno = $_POST['nf_retorno_'.$i];
						$data_nf_retorno = $_POST['data_nf_retorno_'.$i];
						$valor_nf_retorno = $_POST['valor_nf_retorno_'.$i];
					}

					$flag_cor           = '';
					$cor                = ($i % 2 == 0) ? '#F1F4FA' : "#FFFFFF";
					$consumidor_revenda = trim(pg_fetch_result ($res, $i, 'consumidor_revenda'));
					$referencia         = trim(pg_fetch_result ($res, $i, 'referencia'));
					$data_conserto      = trim(pg_fetch_result ($res, $i, 'data_conserto'));
					$finalizada      = trim(pg_fetch_result ($res, $i, 'finalizada'));

					if (in_array($login_fabrica, array(141,144)) && $posto_interno) {
						$remanufatura     = pg_fetch_result($res, $i, "remanufatura");
						$classificacao_os = pg_fetch_result($res, $i, "classificacao_os");
					}

					//HD 9013
					if (($consumidor_revenda == 'C' and ($login_fabrica == 1 or $login_fabrica == 7)) or ($login_fabrica <> 1 and $login_fabrica <> 7)) {

						$os               = trim(pg_fetch_result($res, $i, 'os'));
						$sua_os           = trim(pg_fetch_result($res, $i, 'sua_os'));
						$fabrica_os       = trim(pg_fetch_result($res, $i, 'fabrica'));
						$cortesia         = trim(pg_fetch_result($res, $i, 'cortesia'));
						$tipo_os_cortesia = trim(pg_fetch_result($res, $i, 'tipo_os_cortesia'));
						$admin            = trim(pg_fetch_result($res, $i, 'admin'));
						$tipo_atendimento = trim(pg_fetch_result($res, $i, 'tipo_atendimento'));
						$produto          = trim(pg_fetch_result($res, $i, 'produto'));
						$status_checkpoint=	pg_fetch_result($res, $i,'status_checkpoint');
						$tipo_os          =	pg_fetch_result($res, $i,'tipo_os');
						$observacao       =	pg_fetch_result($res, $i,'obs');
						$abertura	  = pg_fetch_result($res, $i, 'abertura');

						if($login_fabrica <> 1){
							$tecnico   	      =	pg_fetch_result($res, $i,'tecnico');
						}
						$aberta_90_dias = pg_fetch_result($res, $i,'aberta_90_dias');

						if ($login_fabrica == 158) {
							if (isset($_POST["os"][$i]) && $_POST["os"][$i]["os_{$i}"] == $os && $_POST["os"][$i]["ativo_{$i}"] == "t") {
								$data_digitacao      = $_POST["os"][$i]["data_digitacao"];
								$inicio_atendimento = $_POST["os"][$i]["inicio_atendimento"];
								$fim_atendimento    = $_POST["os"][$i]["fim_atendimento"];

							} else {
								$data_digitacao      = pg_fetch_result($res, $i, "data_digitacao");
								$inicio_atendimento = pg_fetch_result($res, $i, "inicio_atendimento");
								$fim_atendimento    = pg_fetch_result($res, $i, "termino_atendimento");

							}
						}

						if(in_array($login_fabrica, [167, 203])){
							$descricao_tipo_atendimento = pg_fetch_result($res, $i, 'descricao_tipo_atendimento');
						}
						if ($login_fabrica == 74) {
							$linha_nome = pg_fetch_result($res, $i, "linha_nome");
							$hd_chamado = pg_fetch_result($res, $i, "hd_chamado");
						}

						if (in_array($login_fabrica, array(169,170))){
							$codigo_posto = pg_fetch_result($res, $i, 'codigo_posto');
							$hd_chamado = pg_fetch_result($res, $i, "hd_chamado");

							$sqlDefeito = "SELECT defeito_constatado AS defeito_constatado_os
											FROM tbl_os_defeito_reclamado_constatado
											WHERE os = {$os}
											AND fabrica = {$login_fabrica} ";
							$resDefeito = pg_query($con, $sqlDefeito);
							$defeito_constatado_os = "";
							if (pg_num_rows($resDefeito) > 0){
								$defeito_constatado_os = pg_fetch_result($resDefeito, 0, 'defeito_constatado_os');
							}
						}
		//HD 12521

						if ($login_fabrica == 3 or $login_posto == 4311) {

							$sql = "SELECT   OI.os_item
							FROM      tbl_os_produto        OP
							JOIN      tbl_os_item           OI ON OP.os_produto        = OI.os_produto
							JOIN      tbl_servico_realizado SR ON OI.servico_realizado = SR.servico_realizado
							LEFT JOIN tbl_faturamento_item  FI ON OI.peca              = FI.peca              AND OI.pedido = FI.pedido
							WHERE OP.os = $os
							AND   SR.gera_pedido      IS TRUE
							AND   FI.faturamento_item IS NULL
							LIMIT 1 ";

							$res_os_item = pg_query($con, $sql);

							if (pg_num_rows($res_os_item)) {
								$os_item = trim(pg_fetch_result($res_os_item, 0, 'os_item'));
							}

						}

		//HD 2499938 (Elgin)

						if ($login_fabrica == 117) {

							$sqlStatusOS = "SELECT status_os FROM tbl_os_status WHERE os = {$os} ORDER BY data DESC LIMIT 1;";

							$resStatusOS= pg_query($con, $sqlStatusOS);

							$statusOS = trim(pg_fetch_result($resStatusOS, 0, 'status_os'));

							if ($statusOS == 81) {
								$flag_bloqueio = "t";
							}

						}

		//HD 13239   HD 135436(+Mondial))

						if(in_array($login_fabrica, array(11,172))){
							if (usaDataConserto($login_posto, $fabrica_os)) {
								$data_conserto = trim(pg_fetch_result ($res,$i,data_conserto));
							}
							if(strlen($msg_erro) > 0){
								$data_conserto = $_POST["os"][$i]["data_conserto_{$i}"];
							}
						}else{
							if (usaDataConserto($login_posto, $login_fabrica) OR $login_fabrica == 1) {
								$data_conserto = trim(pg_fetch_result ($res,$i,data_conserto));
							}
						}

		//HD 180939
						if ($login_fabrica == 2) {
							$pac = trim(pg_fetch_result($res, $i, 'pac'));
						}

		//HD 4291 Paulo --- HD 23623 - acrescentado 14301
						if ($login_posto == 4311 or $login_posto == 6359 or $login_posto == 14301) {
							$prateleira_box = trim(pg_fetch_result ($res,$i,prateleira_box));
						}

						if ( in_array($fabrica_os, array(11,172)) and $login_posto == 14301) {
							$consumidor_email = trim(pg_fetch_result($res, $i, 'consumidor_email'));
						}

						if (strlen($sua_os) == 0) $sua_os = $os;

						$descricao = pg_fetch_result ($res, $i, 'nome_comercial') ;
						if (strlen($descricao) == 0) $descricao = pg_fetch_result ($res, $i, 'descricao');

						$defeito_constatado = trim(pg_fetch_result ($res, $i, 'defeito_constatado'));

		//--=== Tradução para outras linguas ============================= Raphael HD:1212
						if (strlen($produto) > 0 ){
							$sql_idioma = "SELECT * FROM tbl_produto_idioma WHERE produto = $produto AND upper(idioma) = '$sistema_lingua'";

							$res_idioma = @pg_query($con, $sql_idioma);
							if (@pg_num_rows($res_idioma) > 0) {
								$descricao  = trim(@pg_fetch_result($res_idioma, 0, 'descricao'));
							}
						}
		//--=== Tradução para outras linguas ================================================

		#150828
						$os_troca = false;

						if ($msg_erro){
							$ativo = $_POST["os"][$i]['ativo_'.$i];
						}

						if ( in_array($login_fabrica, array(1,11,172)) ) {

							$sql = "SELECT os
							FROM   tbl_os
							JOIN   tbl_os_troca USING(os)
							WHERE  os = $os
							AND    tbl_os.fabrica = $login_fabrica";

							$resY = pg_query($con, $sql);

							if (pg_num_rows($resY) > 0) {
								$os_troca = true;
							}

						}

						if ($login_fabrica == 1) {

							if ($status_checkpoint == 4 or $status_checkpoint == 3) {
								$flag_bloqueio = "";
							} else {
								$flag_bloqueio = "t";
							}


							if ($tipo_os_cortesia == 'Devolução de valor' && $cortesia == 't') {
								$flag_bloqueio = '';
							} else if (strlen($defeito_constatado) == 0 and $status_checkpoint != 4) {

								$flag_cor = "t";
								$flag_bloqueio = "t";
							} else if ($flag_bloqueio == "t" && strlen($defeito_constatado) != 0) {
								$flag_bloqueio = "t";
							} else {
								$flag_bloqueio = "";

							}
							$sql = "SELECT * FROM tbl_laudo_tecnico_os WHERE os = {$os}";
							$res3 = pg_query($con, $sql);
							if(pg_num_rows($res3) > 0){
								$flag_bloqueio = "";
							}
						}

		//HD 4291 Paulo verificar a peça pendente da os e mudar cor

						$erros = (!empty($linha_erro)) ? implode($linha_erro,",") : null;
						if (strpos($erros,$os) > 0 or $erros == $os) {
							$cor = "#FF0000";
						}

						if($aberta_90_dias == 'sim' and $login_fabrica == 1){
							$cor = "#8B3A3A";
						}

						if ($login_fabrica == 74) {
							if (strtoupper($linha_nome) == "FOGO" && !empty($hd_chamado) && strtotime($abertura) > strtotime('2017-05-09')) {
								$flag_bloqueio = true;
							} else {
								$flag_bloqueio = "";
							}
						}


							if ($login_fabrica == 24) {

								$os = pg_fetch_result ($res, $i, 'os');

								$queryComprovante = "SELECT os_troca 
													  FROM tbl_os_troca
													  WHERE os = {$os} and fabric = $login_fabrica ";													  
								$resComprovante = pg_query($con, $queryComprovante);
								$btnComprovante = false;								
								if (pg_num_rows($resComprovante) > 0) {
									$btnComprovante = true;
								}
							}
						?>
						<tr bgcolor="<?php echo $cor;?>" <? echo " rel='status_$status_checkpoint' "; if (($erros == $os or strpos($erros,$os) > 0) and strlen ($msg_erro) > 0 )?>>
							<?php
							if (!in_array($login_fabrica, array(167,176,203))) {
								if ($login_fabrica != 173 || ($login_fabrica == 173 && $posto_interno == true)) {
							?>
							<?php 

							if ($login_fabrica == 24 && $btnComprovante == true) { ?>
								<td align="center">
									<button type="button" title="Modelo Comprovante de Retirada" class="fechamento_os"onClick="modelocomprovante()"> 
										Modelo Comprovante 
									</button>
								</td>
								<td align="center">	
									<button type="button" title="Anexar Comprovante de Retirada" class="fechamento_os" onClick="anexarAuditoria('<?=$os?>');">
										<?= traduz('Anexar') ?>
									</button>
								</td>
							<?php } else { ?>
								<td align="center" <?= $login_fabrica == 24 ? 'colspan="2"' : ''?>>
									<input type='hidden' name='os[<?=$i?>][os_<? echo $i ?>]' value='<? echo pg_fetch_result ($res,$i,os) ?>' >
									<?if($login_fabrica==1){?>
									<input type='hidden' name='os[<?=$i?>][conta_<? echo $i ?>]' value='<? echo $i;?>'>
									<input type='hidden' name='os[<?=$i?>][consumidor_revenda_<? echo $i ?>]' value='<? echo pg_fetch_result ($res,$i,consumidor_revenda)?>'>
									<?}?>
									<? if($login_fabrica == 3 and strlen($os_item)>0){?>
									<input type='hidden' name='os_item_<? echo $i ?>' value='<? echo "$os_item"; ?>'>
									<? } ?>
									<? if (strlen($flag_bloqueio) == 0){
										if ($_POST["os"][$i]['ativo_'.$i] == 't' && $os == $_POST["os"][$i]["os_{$i}"]){
											$checked_ativo = "checked='CHECKED'";
										}else{
											$checked_ativo = "";
										}

										?>
										<input rel="checkbox_os" numero="<?= $i ?>" type="checkbox" class="frm check os checkbox_<?=$i?>" name="os[<?=$i?>][ativo_<?echo $i?>]" <?php echo $checked_ativo ?> id="ativo"  value="t" >

									<?php } ?>
								</td>
							<? } ?>
							<?php
								}
							}

							if ($login_fabrica == 1) {
							?>
								<input type="hidden" class="ativo2_<?=$i?>" name="os[<?=$i?>][ativo2_<?=$i?>]" id="ativo2" value=''>
							<?php
							}

							//Alterado por Wellington 06/12/2006 a pedido de Luiz Antonio, posto Jundservice (Lenoxx) deve abrir os_item ao clicar na OS
			//HD 234532
								if(strlen($status_checkpoint)> 0 ) {

									if ($telecontrol_distrib && (!isset($novaTelaOs) || (in_array($login_fabrica, [160]) or $replica_einhell))) {
                                    	$sql_abastecimento = "SELECT tbl_status_checkpoint.descricao 
                                    						  FROM tbl_os
                                    						  JOIN tbl_status_checkpoint ON tbl_status_checkpoint.status_checkpoint = tbl_os.status_checkpoint
                                    						  WHERE fabrica = $login_fabrica
                                    						  AND tbl_os.os = $os";
                                    	$res_abastecimento = pg_query($con, $sql_abastecimento);

                                    	$descricao_status_os = pg_fetch_result($res_abastecimento, 0, 'descricao');

                                    	if (strtoupper($descricao_status_os) == 'AGUARD. ABASTECIMENTO ESTOQUE') {
                                    		$cor_status_os = '<span class="status_checkpoint" style="background-color: #FAFF73">&nbsp;</span>';
                                    	} else {
                                    		$cor_status_os = '<span class="status_checkpoint" style="background-color:'.$array_cor_status[$status_checkpoint].'">&nbsp;</span>';
                                    	}
                                    } else {
                                    	$cor_status_os = '<span class="status_checkpoint" style="background-color:'.$array_cor_status[$status_checkpoint].'">&nbsp;</span>';
                                    }

								} else {
                                    $cor_status_os = '<span class="status_checkpoint_sem">&nbsp;</span>';
								}
								?>
								<td nowrap>
									<? echo $cor_status_os;?>
									<a href='<? if ($cor == "#FFCC66" or ( in_array($login_fabrica, array(11,172)) and $login_posto==14254)) echo "os_item"; else echo "os_press"; ?>.php?os=<? echo $os ?>' target='_blank'><? if ($login_fabrica == 1)echo $login_codigo_posto; echo $sua_os; ?></a>
								</td>
								<? if( in_array($login_fabrica, array(11,172)) and $login_posto == 14301){
?>
                                <td align='left'><?=$prateleira_box?></td>
<?
                                }
?>
								<td align='left'><? echo pg_fetch_result ($res,$i,data_abertura); ?></td>
								<?php if($login_fabrica == 87){
									echo "<td nowrap>{$tipo_atendimento_descricao}</td>";
								}?>
								<td align='left'><? echo substr (pg_fetch_result ($res,$i,consumidor_nome),0,10); ?></td>

                                <? if(in_array($login_fabrica ,array(30,85,137,173))) {

									$serie = pg_fetch_result ($res,$i,serie); ?>

									<td><?php echo ($login_fabrica != 137) ? substr ($descricao,0,15) : $referencia. " - " . substr ($descricao,0,15); ?></td>
								<? }else{
									?>
                                        <td><?
                                            if ( in_array($login_fabrica, array(11,172)) ) {
                                               echo  pg_fetch_result ($res,$i,serie)." - ".substr($referencia,0,15);
                                            }else if($login_fabrica == 147){
                                                echo  substr($descricao,0,15);
                                            }else if($login_fabrica == 1){
                                                echo substr($referencia,0,15);
                                            }else{
                                                echo pg_fetch_result ($res,$i,serie) . " - " . substr ($descricao,0,15);
                                            } ?>
                                        </td>
									<? }
									if($login_fabrica == 3){?>
									<td>
										<select name='tecnico_<?php echo $i;?>' class='frm'>
											<?php
											if(intval($tecnico) == 0){
												$tecnico = @$_POST["tecnico_{$i}"];
											}

											$sql = "SELECT
											tbl_tecnico.tecnico AS tecnico_id,
											tbl_tecnico.nome
											FROM tbl_os
											JOIN tbl_produto ON tbl_os.produto = tbl_produto.produto AND tbl_produto.fabrica_i = $login_fabrica
											JOIN tbl_tecnico  ON  tbl_produto.linha = ANY(tbl_tecnico.linhas)
											WHERE
											tbl_tecnico.ativo
											AND tbl_tecnico.fabrica = {$login_fabrica}
											AND tbl_tecnico.posto = {$login_posto}
											AND tbl_os.os = $os
											ORDER BY tbl_tecnico.nome;";
											$res_tecnico = pg_query($con, $sql);

											if(empty($tecnico)){
												echo "<option value='0' selected='selected'> Selecione</option>";
											}

											if(pg_num_rows($res_tecnico) > 0){
												for ($x=0; $x < pg_num_rows($res_tecnico); $x++) {
													extract(pg_fetch_array($res_tecnico));

													$selected = ($tecnico_id == $tecnico) ? "selected='selected' " : "";
													echo "<option value='{$tecnico_id}' {$selected} > $nome</option>";
												}
											}else{
												echo "<option value='0' selected='selected'> Posto sem técnico cadastrado!</option>";
											}
											?>
										</select>
										<?php }

                                        if (!in_array($login_fabrica, array(1,2,20)) || ($login_fabrica == 137 && !$posto_interno)){
                                        	if (
                                        		($consumidor_revenda == 'R' )
                                                OR $login_fabrica == 96
                                                OR (in_array($login_fabrica, array(141,144,156,165)) && $posto_interno == true)
                                             ){

												if($login_fabrica == 15){
													echo "<td><input class='frm' type='text' name='serie_reoperado_$i' size='15' maxlength='20' value='$serie_reoperado'></td>";
												}
												if( (($login_fabrica == 30 or $login_fabrica == 85) and strlen($serie) == 0) || in_array($login_fabrica, array(173))){
                                                    if ($login_fabrica == '30') {
                                                        $maxlen = '14';
                                                    } if (in_array($login_fabrica, array(173))) {
                                                    	$maxlen = '30';
                                                    } else {
                                                        $maxlen = '20';
                                                    }
													echo "<td><input class='frm' type='text' name='serie_$i' size='15' maxlength='{$maxlen}' value='$serie'></td>";
												}else if(strlen($serie) > 0 && !in_array($login_fabrica , array(137,141,156))){
													echo "<td>$serie";
													echo "<input type='hidden' name='serie_$i' value='$serie'>";
													echo "</td>";
												}

												if($login_fabrica == 156 and $posto_interno == true){
													if(!empty($msg_erro)){
														$nf_retorno = $_POST['nf_retorno_'.$i];
														$data_nf_retorno = $_POST['data_nf_retorno_'.$i];
														$valor_nf_retorno = $_POST['valor_nf_retorno_'.$i];
													}else{
														$sql = "SELECT campos_adicionais
																FROM tbl_os_campo_extra
																WHERE os = {$os}";
														$result = pg_query($con, $sql);

														$campos_adicionais = pg_fetch_result($result, 0, "campos_adicionais");

														if (!empty($campos_adicionais)) {
															$campos_adicionais = json_decode($campos_adicionais, true);

															$nf_retorno       = $campos_adicionais["nf_retorno"];
															$data_nf_retorno  = $campos_adicionais["data_nf_retorno"];
															$valor_nf_retorno = $campos_adicionais["valor_nf_retorno"];
														} else {
															unset($nf_retorno, $data_nf_retorno, $valor_nf_retorno);
														}
													}

												}

                                        if(!in_array($login_fabrica,array(141,144,147,156,157,160,161,164,165,169,170,171,173,174,176,177,180,181,182)) and !$replica_einhell){
											echo "
											<td>
												<input class='frm' type='text' rel='nf_saida' name='nota_fiscal_saida_$i' size='8' maxlength='10' value='$nota_fiscal_saida'>
												<input class='frm' type='hidden' name='nota_fiscal_verif_$i' size='8' maxlength='10' value='$i'>
											</td>
											<td>
												<input class='frm' type='text' name='data_nf_saida_$i' size='12'  onKeyUp=\"formata_data(this.value,'frm_os', 'data_nf_saida_$i')\"  maxlength='10' value='$data_nf_saida'>
											</td>";
                                		}


                                		if((in_array($login_fabrica, array(141,144,165)) && $posto_interno == true)) {

                                			if(strlen($_POST["nota_fiscal_saida_{$i}"]) == 0){
                                				$nota_fiscal_saida = pg_fetch_result($res, $i, "nota_fiscal_saida");
                                			}else{
                                				$nota_fiscal_saida = $_POST["nota_fiscal_saida_{$i}"];
                                			}

                                        	if(strlen($_POST["data_nf_saida_{$i}"]) == 0){
                                        		$data_nf_saida = pg_fetch_result($res, $i, "data_nf_saida");
                                        		list($ano, $mes, $dia) = explode("-", $data_nf_saida);
                                        		$data_nf_saida = $dia."/".$mes."/".$ano;
                                        	}else{
                                        		$data_nf_saida = $_POST["data_nf_saida_{$i}"];
                                        	}

											echo "
											<td>
												<input class='frm' type='text' rel='nf_saida' name='nota_fiscal_saida_$i' size='8' maxlength='10' value='$nota_fiscal_saida'>
												<input class='frm' type='hidden' name='nota_fiscal_verif_$i' size='8' maxlength='10' value='$i'>
											</td>
											<td>
												<input class='frm' type='text' name='data_nf_saida_$i' size='12'  onKeyUp=\"formata_data(this.value,'frm_os', 'data_nf_saida_$i')\"  maxlength='10' value='$data_nf_saida'>
											</td>";
                                		}

                                		if ($login_fabrica == 156 && $posto_interno == true) {
                                			echo"
                                			<td>
                                				<input class='frm' size='8' type='text' name='nf_retorno_$i' maxlength='10' value='$nf_retorno' />
                                			</td>
                                			<td>
                                				<input class='frm date' size='8' type='text' name='data_nf_retorno_$i' onKeyUp=\"formata_data(this.value, 'frm_os', 'data_nf_retorno_$i')\" value='$data_nf_retorno' />
                                			</td>
                                			<td>
                                				<input class='frm decimal' size='8' type='text' name='valor_nf_retorno_$i' value='$valor_nf_retorno' />
                                			</td>
                                			";
                                		}

										if (in_array($login_fabrica, array(141,144,165)) && $posto_interno == true) {
											$sqlPac = "SELECT pac FROM tbl_os_extra WHERE os = {$os}";
											$resPac = pg_query($con, $sqlPac);

											$codigo_rastreio = pg_fetch_result($resPac, 0, "pac");

											echo "
											<td>
												<select data-os='{$os}' id='tipo_entrega_$os' class='tipo_entrega' name='os[$os][tipo_entrega]' >
											";
													if (isset($_POST["os"][$os]["tipo_entrega"])) {
														$tipo_entrega = $_POST["os"][$os]["tipo_entrega"];
													} else {
														$tipo_entrega = "";
													}

													if ($codigo_rastreio == "balcão") {
														$tipo_entrega = (in_array($login_fabrica, [141,144])) ? "transportadora" : "balcão";
													} else if (!empty($codigo_rastreio) || $codigo_rastreio == "sem_rastreio") {
														$tipo_entrega = "correios";
													}

													echo "
													<option value=''>Tipo de Entrega</option>
													";

													if (in_array($login_fabrica, [141,144])) {
														echo "<option value='transportadora' ".(($tipo_entrega == "tranportadora") ? "selected" : "")." >Transportadora</option>";
													} else {
														echo "<option value='balcão' ".(($tipo_entrega == "balcão") ? "selected" : "")." >Balcão</option>";
													}

													echo "
													<option value='correios' ".(($tipo_entrega == "correios") ? "selected" : "")." >Correios</option>
													";

													switch ($tipo_entrega) {
														case 'transportadora':
															$codigo_rastreio    = "transportadora";
															$classInputReadonly = "input_readonly";
															$inputReadonly      = "readonly='readonly'";
															break;

														case 'correios':
															if (empty($codigo_rastreio)) {
																$codigo_rastreio = $_POST["os"][$os]["codigo_rastreio"];
															}
															if ($codigo_rastreio == "sem_rastreio") {
																$codigo_rastreio = "";
															}
															$classInputReadonly = "";
															$inputReadonly      = "";
															break;

														default:
															$codigo_rastreio = "";
															$classInputReadonly = "input_readonly";
															$inputReadonly      = "readonly='readonly'";
															break;
													}

													if (in_array($login_fabrica, [141,144])) {
														$classInputReadonly = "";
														$inputReadonly      = "";
													}

											echo "
												</select>
											</td>
											<td>
												<input data-os='{$os}' id='codigo_rastreio_{$os}' class='frm {$classInputReadonly} codigo_rastreio' type='text' name='os[$os][codigo_rastreio]' style='width: 100px;' maxlength='13' value='{$codigo_rastreio}' {$inputReadonly} />
											</td>";
										}

										if (in_array($login_fabrica, array(141,144)) && $posto_interno === true) {
											echo "<td>";
												if ($classificacao_os == "remanufaturada") {
													echo "Remanufaturada";
												} else if ($remanufatura == "t") {
													echo "<button type='button' style='cursor: pointer;' onclick='remanufaturarOS($os, $(this));' >Remanufaturar</button>";
												}
											echo "</td>";
										
										}

										if (in_array($login_fabrica, [177])) {

											$sqlPecaLote = "SELECT tbl_os_item.os_item
															FROM tbl_os_produto
															JOIN tbl_os_item ON tbl_os_item.os_produto = tbl_os_produto.os_produto
															JOIN tbl_servico_realizado ON tbl_os_item.servico_realizado = tbl_os_item.servico_realizado
															AND tbl_servico_realizado.troca_de_peca = 't'
															JOIN tbl_peca using(peca)
															WHERE tbl_os_produto.os = {$os}
															AND JSON_FIELD('lote', tbl_peca.parametros_adicionais) = 't'
															AND tbl_os_item.peca_serie IS NULL ";
											$resPecaLote = pg_query($con, $sqlPecaLote);

											echo "<td>";

												if (pg_num_rows($resPecaLote) > 0) {
												?>
													<input class="frm" type="text" name="lote_peca_nova_<?= $i ?>" value="<?= $_POST['lote_peca_nova_$i'] ?>" />
												<?php
												}

											echo "</td>";
										}


									}else{
										if(($login_fabrica == 30 or $login_fabrica == 85) and strlen($serie)==0){
                                            if ($login_fabrica == '30') {
                                                $maxlen = '14';
                                            } else {
                                                $maxlen = '20';
                                            }
											echo "<td><input class='frm' type='text' name='serie_$i' size='15' maxlength='$maxlen' value='$serie'></td>";
										}else if(strlen($serie)>0 && $login_fabrica != 137){
											echo "<td>$serie";
											echo "<input type='hidden' name='serie_$i' value='$serie'>";
											echo "</td>";
                                        }

                                        if ($login_fabrica == 173 AND empty($serie)){
                                        	echo "<td>&nbsp;</td>";
                                        }

                                        if(!in_array($login_fabrica, array(141,147,156,157,160,158,161,164,169,170,171,173,174,176,177,180,181,182)) and !$replica_einhell){
	    									echo "<td>&nbsp;</td>";
                                            echo "<td>&nbsp;</td>";
                                        }

                                       // HD-6936556 - ADMIN ALEGA QUE NÃO ULTILIZA!
                                       /* if(in_array($login_fabrica, array(164)) && $posto_interno == true){

                                        	if(strlen($_POST["nota_fiscal_saida_{$i}"]) == 0){
                                				$nota_fiscal_saida = pg_fetch_result($res, $i, "nota_fiscal_saida");
                                			}else{
                                				$nota_fiscal_saida = $_POST["nota_fiscal_saida_{$i}"];
                                			}

                                        	if(strlen($_POST["data_nf_saida_{$i}"]) == 0){
                                        		$data_nf_saida = pg_fetch_result($res, $i, "data_nf_saida");
                                        		list($ano, $mes, $dia) = explode("-", $data_nf_saida);

                                        		if (!empty($data_nf_saida)) {
                                        			$data_nf_saida = $dia."/".$mes."/".$ano;
                                        		}
                                        	}else{
                                        		$data_nf_saida = $_POST["data_nf_saida_{$i}"];
                                        	}

											echo "
											<td>
												<input class='frm' type='text' rel='nf_saida' name='nota_fiscal_saida_$i' size='8' maxlength='10' value='$nota_fiscal_saida'>
												<input class='frm' type='hidden' name='nota_fiscal_verif_$i' size='8' maxlength='10' value='$i'>
											</td>
											<td>
												<input class='frm' type='text' name='data_nf_saida_$i' size='12'  onKeyUp=\"formata_data(this.value,'frm_os', 'data_nf_saida_$i')\"  maxlength='10' value='$data_nf_saida'>
											</td>";
                                		}*/

                                		if (in_array($login_fabrica, [177])) {

                                			$sqlPecaLote = "SELECT tbl_os_item.os_item
                                							FROM tbl_os_produto
                                							JOIN tbl_os_item ON tbl_os_item.os_produto = tbl_os_produto.os_produto
                                							JOIN tbl_servico_realizado ON tbl_os_item.servico_realizado = tbl_os_item.servico_realizado
                                							AND tbl_servico_realizado.troca_de_peca = 't'
															JOIN tbl_peca using(peca)
															WHERE tbl_os_produto.os = {$os}
															AND JSON_FIELD('lote', tbl_peca.parametros_adicionais) = 't'
															AND tbl_os_item.peca_serie IS NULL ";
                                			$resPecaLote = pg_query($con, $sqlPecaLote);

                                			echo "<td>";

                                				if (pg_num_rows($resPecaLote) > 0) {
	                                			?>
	                                				<input class="frm" type="text" name="lote_peca_nova_<?= $i ?>" value="<?= $_POST['lote_peca_nova_$i'] ?>" />
	                                			<?php
                                				}

                                			echo "</td>";
                                		}

                                		if (in_array($login_fabrica, [144]) && $posto_interno) {
                                			echo "<td></td><td></td><td></td>";
                                		}

									}
								}
								if ($login_fabrica == 173) {
									echo "<td><input type='text' name='cod_barra_serie' data-os='{$os}' class='frm' size='18' /></td>";
								}

								if ($login_fabrica == "20") {

									$pecas              = 0;
									$mao_de_obra        = 0;
									$tabela             = 0;
									$desconto           = 0;
									$desconto_acessorio = 0;

									$ysql = "SELECT mao_de_obra FROM tbl_produto_defeito_constatado WHERE produto = (SELECT produto FROM tbl_os WHERE os = $os) AND defeito_constatado = (SELECT defeito_constatado FROM tbl_os WHERE os = $os)";
									$yres = pg_query ($con,$ysql);
									if (pg_num_rows ($yres) == 1) {
										$mao_de_obra = pg_fetch_result ($yres,0,mao_de_obra);
									}

									$ysql = "SELECT tabela,desconto,desconto_acessorio FROM tbl_posto_fabrica WHERE posto = $login_posto AND fabrica = $login_fabrica";
									$yres = pg_query ($con,$ysql);

									if (pg_num_rows ($yres) == 1) {
										$tabela             = pg_fetch_result ($yres,0,tabela)            ;
										$desconto           = pg_fetch_result ($yres,0,desconto)          ;
										$desconto_acessorio = pg_fetch_result ($yres,0,desconto_acessorio);
									}
									if (strlen ($desconto) == 0) $desconto = "0";

									if (strlen ($tabela) > 0) {

										$ysql = "SELECT SUM (tbl_tabela_item.preco * tbl_os_item.qtde) AS total
										FROM tbl_os
										JOIN tbl_os_produto USING (os)
										JOIN tbl_os_item    USING (os_produto)
										JOIN tbl_tabela_item ON tbl_os_item.peca = tbl_tabela_item.peca AND tbl_tabela_item.tabela = $tabela
										WHERE tbl_os.os = $os
										AND tbl_os.fabrica = $login_fabrica";
										$yres = pg_query ($con,$ysql);

										if (pg_num_rows ($yres) == 1) {
											$pecas = pg_fetch_result ($yres,0,0);
										}
									}else{
										$pecas = "0";
									}

									$valor_liquido = 0;

									if ($desconto > 0 and $pecas <> 0) {

										$ysql = "SELECT produto FROM tbl_os WHERE os = $os AND fabrica = $login_fabrica";
										$yres = pg_query ($con,$ysql);

										if (pg_num_rows ($res) == 1) {
											$produto = pg_fetch_result ($yres,0,0);
										}

										if ($produto == '20567') {
											$desconto_acessorio = '0.2238';
											$valor_desconto = round((round($pecas,2) * $desconto_acessorio) ,2);
										} else {
											$valor_desconto = round((round($pecas,2) * $desconto / 100) ,2);
										}

										$valor_liquido = $pecas - $valor_desconto;

									}

									$acrescimo = 0;

									if ($login_pais <> "BR") {

										$ysql = "select pecas,mao_de_obra  from tbl_os where os=$os";
										$yres = pg_query($con,$ysql);

										if (pg_num_rows($yres) == 1) {
											$valor_liquido = pg_fetch_result($yres, 0, 'pecas');
											$mao_de_obra   = pg_fetch_result($yres, 0, 'mao_de_obra');
										}

										$ysql = "select imposto_al from tbl_posto_fabrica where posto = $login_posto and fabrica = $login_fabrica";
										$yres = pg_query($con, $ysql);

										if (pg_num_rows ($yres) == 1) {
											$imposto_al = pg_fetch_result ($yres,0,imposto_al);
											$imposto_al = $imposto_al / 100;
											$acrescimo  = ($valor_liquido + $mao_de_obra) * $imposto_al;
										}

									}

				//HD 9469 - Alteração no cálculo da BOSCH do Brasil HD 48439
									if ($login_pais == "BR") {

										$sqlxx = "select pecas,mao_de_obra  from tbl_os where os=$os";
										$resxx = pg_query ($con,$sqlxx);

										if (pg_num_rows($resxx) == 1) {
											$valor_liquido = pg_fetch_result($resxx, 0, 'pecas');
											$mao_de_obra   = pg_fetch_result($resxx, 0, 'mao_de_obra');
										}

									}

									$total          = $valor_liquido + $mao_de_obra + $acrescimo;
									$total          = number_format($total,2,",",".");
									$mao_de_obra    = number_format($mao_de_obra ,2,",",".");
									$acrescimo      = number_format($acrescimo ,2,",",".");
									$valor_desconto = number_format($valor_desconto,2,",",".");
									$valor_liquido  = number_format($valor_liquido ,2,",",".");

									echo "<td align='center'>" ;
									echo "<font size='2' face='Geneva, Arial, Helvetica, san-serif'>$valor_liquido</font>" ;
									echo "</td>";
									echo "<td align='center'><font size='2' face='Geneva, Arial, Helvetica, san-serif'>$mao_de_obra</font></td>";

								}

								if (($login_posto == '4311') && $login_fabrica != 137 and !isset($novaTelaOs)) {
									echo "<td align='center'>$prateleira_box</td>";
								}
								if($login_fabrica == 6){

                                    $temImg = temNF($os, 'count');
?>
								<td><?=($temImg > 0) ? "Sim" : "Não"?></td>
								<td><input type="file" name="foto_nf_<?=$i?>" class="filestyle" data-size="sm" data-input="false" data-buttonText="Foto" data-buttonName="btn-primary"/></td>
<?
                                }

								if($login_fabrica == 137 && $posto_interno){

									echo "<td>
										<input class='frm' type='text' name='serie_$i' size='8' maxlength='10' value='$serie'>
									</td>";
									echo "<td>";

									echo "<select name='defeito_constatado_$i'>
									<option value=''></option>";
									for($j = 0; $j < count($array_dc); $j++){
										$defeito 	= $array_dc[$j]['defeito_constatado'];
										$codigo 	= $array_dc[$j]['codigo'];
										$descricao 	= $array_dc[$j]['descricao'];
										$selected = ($defeito_constatado == $defeito) ? "SELECTED" : "";
										echo "<option value='{$defeito}' {$selected}>{$codigo} - {$descricao}</option>";
									}
									echo "</select>";

									echo "</td>";

								}

							if ($login_fabrica == 158) {
							?>
								<td>
									<input type="hidden" name="os[<?=$i?>][data_digitacao]" value="<?=$data_digitacao?>" />
									<input type="text" class="mask-datetimepicker input-inicio-atendimento" data-os="<?=$os?>" name="os[<?=$i?>][inicio_atendimento]" style="width: 150px;" value="<?=$inicio_atendimento?>" />
								</td>
								<td>
									<input type="text" class="mask-datetimepicker input-fim-atendimento" data-os="<?=$os?>" name="os[<?=$i?>][fim_atendimento]" style="width: 150px;" value="<?=$fim_atendimento?>" />
								</td>
							<?php
							}

			//HD 12521 //HD13239 hd 14121 HD 48989(bosch)
							if (usaDataConserto($login_posto, $login_fabrica) && !in_array($login_fabrica, array(171))) {
								$align_td = "center";
								if (in_array($login_fabrica, array(169,170,174))){
									$tem_postagem = "false";
									if (strlen(trim($hd_chamado)) > 0){
										$sql_lgr_correios = "
						                    SELECT hd_chamado_postagem, tbl_hd_chamado_postagem.admin
						                    FROM tbl_hd_chamado_postagem
						                    WHERE fabrica = $login_fabrica
						                    AND hd_chamado = $hd_chamado
						                    ORDER BY hd_chamado_postagem DESC LIMIT 1";
						                $res_lgr_correios = pg_query($con, $sql_lgr_correios);
						                if (pg_num_rows($res_lgr_correios) > 0){
						                    $admin_postagem = pg_fetch_result($res_lgr_correios, 0, 'admin');
						                    if (strlen(trim($admin_postagem)) == 0){
						                    	$disabled_conserto = "disabled";
						                    }

						                }

						                if (strlen(trim($data_conserto)) > 0 AND strlen(trim($admin_postagem)) > 0){
						                	$tem_postagem = "true";
						                }
									}
									if ($tem_postagem == "false"){
										$td_span = "colspan='2'";
										$align_td = "left";
									}else{
										$td_span = "";
										$align_td = "center";
									}
							   	}
								#$td_span = " colspan='2' ";//monteiro   os = 	46543834
                                echo "<td align='$align_td' nowrap $td_span >";
				#hd 150828
								if ( ( ( in_array($login_fabrica, array(11,172)) ) AND $os_troca == '' ) or ( !in_array($login_fabrica, array(11,172)) ) ) {

									if ($login_fabrica == 1){										

										if ($os_troca == '') {
											if ($status_checkpoint <> 3){
												$mostra_data = '';
											}else{
												$mostra_data = '1';
											}
										} else {
											$sqlW = "	SELECT os
														FROM   tbl_os
														JOIN   tbl_os_troca USING(os)
														WHERE  os = $os
														AND    tbl_os.fabrica = $login_fabrica";
											$resW = pg_query($con, $sqlW);
											if (pg_num_rows($resW) > 0) {
												$sqlZ = "	SELECT tbl_os_item.os_item
															FROM tbl_os_item
															JOIN tbl_os_produto USING(os_produto)
															JOIN tbl_os_item_nf USING(os_item)
															WHERE tbl_os_produto.os = $os 
															AND tbl_os_item.fabrica_i = $login_fabrica
															AND tbl_os_item_nf.data_nf + interval '3 days' <= current_date";
												$resZ = pg_query($con, $sqlZ);
												if (pg_num_rows($resZ) > 0) {
													$mostra_data = '1';
												} else {
													$mostra_data = '';
												}
											}
										}
										
										if (empty($mostrar_data)) {
											$sql_reembolso = "  SELECT posto 
																FROM tbl_posto_fabrica
																WHERE posto = $login_posto
																AND tbl_posto_fabrica.reembolso_peca_estoque IS TRUE";
											$res_reembolso = pg_query($con, $sql_reembolso);
											if (pg_num_rows($res_reembolso) > 0) {
												$mostra_data = '1';
											}
										}

									}else{
										$mostra_data = '1';
									}

									if ($data_conserto){
										$mostra_data = '1';
									}

									if (!empty($mostra_data)){

										// $disabled_conserto = (( in_array($login_fabrica, array(11,172)) ) OR ($login_fabrica == 117 AND $flag_bloqueio == "t")) ? "disabled='disabled'" : '' ;
										$disabled_conserto = ($login_fabrica == 117 AND $flag_bloqueio == "t") ? "disabled='disabled'" : '' ;
										$size_bd = ($login_fabrica == 1) ? "10" : "18";
										$maxlength_bd = ($login_fabrica == 1) ? "10" : "18";

										if (in_array($login_fabrica, array(173))) {
											$sqlJFA = "SELECT 	auditoria_os,
																reprovada,
																liberada,
																cancelada
															FROM tbl_auditoria_os
															WHERE os = $os
																AND (liberada is null OR reprovada is not null)";
											$resJFA = pg_query($con, $sqlJFA);

											if(pg_num_rows($resJFA) > 0){
												$audi_reprovada = pg_fetch_result($res_167, 0, 'reprovada');

												if(strlen(trim($audi_reprovada)) > 0){
													echo "<span style='color: red;'>OS REPROVADA DA AUDITORIA</span>";
												}else{
													echo "<span style='color: red;'>OS EM AUDITORIA</span>";
												}
											}else{
												if(empty($data_conserto)){ 
													if ($posto_interno != true) {
													?>
														<button type="button" class="fechamento_os" data-anexo-fechamento="<?=$os?>" onClick="informeDataConserto('<?=$os?>');"><?= traduz('Informar data de Fechamento') ?></button>
													<?php
													} else {
														echo "<input class='frm' data-defeito_constatado_os='$defeito_constatado_os'  data-hd_chamado='$hd_chamado' type='text' id='data_conserto_$i' name='os[$i][data_conserto_$i]' alt='$os' rel='data_conserto' $disabled_conserto size='$size_bd' maxlength='$maxlength_bd' value='$data_conserto' >";
													}
												}else{ ?>
													<span><?=$data_conserto?></span>
												<?php
												}
											}
										} elseif (in_array($login_fabrica, array(176))) {

											if($descricao_tipoz_atendimento <> "Orçamento" AND $descricao_tipo_atendimento <> "Garantia Recusada"){
												$sql_176 = "SELECT auditoria_os,reprovada,liberada, cancelada
																FROM tbl_auditoria_os
																	WHERE os = $os
																	AND (liberada is null OR reprovada is not null)";
												$res_167 = pg_query($con, $sql_176);

												$audi_liberada = pg_fetch_result($res_167, 0, 'liberada');

												if(strlen(trim($audi_liberada)) === 0 && pg_num_rows($res_167) > 0){
													$audi_reprovada = pg_fetch_result($res_167, 0, 'reprovada');

													if(strlen(trim($audi_reprovada)) > 0){
														echo "<span style='color: red;'>OS REPROVADA DA AUDITORIA</span>";
													}else{
														echo "<span style='color: red;'>OS EM AUDITORIA</span>";
													}
												}else{
													if( (empty($data_conserto) or (!empty($data_conserto) and empty($finalizada))) OR (!empty($data_conserto) AND in_array($login_fabrica, [167, 203])) ){
														$botao_titulo = (in_array($login_fabrica, [167, 203])) ? traduz("Informar conserto/fechamento") : traduz("Informar data de conserto");
													?>
														<button type="button" class="fechamento_os" data-anexo-fechamento="<?=$os?>" onClick="informeDataConserto('<?=$os?>');"><?=$botao_titulo?></button>
													<?php
													}else{
													?>
														<span><?=$data_conserto?></span>
													<?php
													}
												}
											}
											echo "<input type='hidden' name='descricao_tipo_atendimento_$i' value='$descricao_tipo_atendimento'>";
										// HD - 4361872 - Alterado para deixar fechar todos os tipos de OS, até as reprovadas.
										}elseif(in_array($login_fabrica, array(167,203))) {
											$sql_167 = "SELECT auditoria_os, reprovada, liberada, cancelada
																		FROM tbl_auditoria_os
																			WHERE os = $os ";

											$res_167 = pg_query($con, $sql_167);

											if(pg_num_rows($res_167) > 0){
												$cancelada_167 = pg_fetch_result($res_167, 0, 'cancelada');
												if (empty($cancelada_167)){
													$liberada_167 = pg_fetch_result($res_167, 0, 'liberada');
													$reprovada_167 = pg_fetch_result($res_167, 0, 'reprovada');
													if (empty($liberada_167) && empty($reprovada_167)) {
														$botao_titulo = "";
echo "<span style='color: red;'>OS EM AUDITORIA</span>";
													}else{
														$botao_titulo = traduz("Informar conserto/fechamento");
														?>
															<button type="button" class="fechamento_os" data-anexo-fechamento="<?=$os?>" onClick="informeDataConserto('<?=$os?>');"><?=$botao_titulo?></button>
														<?php
													}
												}else{
													$botao_titulo = "";
echo "<span style='color: red;'>OS EM AUDITORIA</span>";
												}
											}else{
												$botao_titulo = traduz("Informar conserto/fechamento");
												?>
													<button type="button" class="fechamento_os" data-anexo-fechamento="<?=$os?>" onClick="informeDataConserto('<?=$os?>');"><?=$botao_titulo?></button>
												<?php
											}
																		
										} else {
											if ($login_fabrica == 160) {
												
	                                            $queryDataConserto = "	SELECT COUNT(tbl_os_item.peca) total
																		FROM tbl_os_item
																		JOIN tbl_os_produto USING(os_produto)
																		JOIN tbl_servico_realizado USING(servico_realizado)
																		LEFT JOIN tbl_pedido_item ON tbl_os_item.pedido_item = tbl_pedido_item.pedido_item 
																		LEFT JOIN tbl_faturamento_item ON tbl_pedido_item.pedido = tbl_faturamento_item.pedido 
																		AND (tbl_pedido_item.peca = tbl_faturamento_item.peca OR tbl_pedido_item.peca_alternativa = tbl_faturamento_item.peca)
																		LEFT JOIN tbl_faturamento ON tbl_faturamento.faturamento = tbl_faturamento_item.faturamento
																		LEFT JOIN tbl_embarque_item ON tbl_pedido_item.pedido_item = tbl_embarque_item.pedido_item
																		WHERE tbl_os_produto.os = {$os}
																		AND tbl_servico_realizado.troca_de_peca IS TRUE
																		AND tbl_servico_realizado.gera_pedido IS TRUE
																		AND (tbl_os_item.pedido IS NULL 
																		      OR (tbl_os_item.pedido IS NOT NULL AND tbl_faturamento_item.pedido IS NULL AND tbl_pedido_item.qtde > (tbl_pedido_item.qtde_cancelada + tbl_pedido_item.qtde_faturada_distribuidor)) 
																		      OR (tbl_faturamento.nota_fiscal = '000000' AND tbl_faturamento_item.pedido IS NOT NULL)
																		      OR (tbl_faturamento.nota_fiscal <> '000000' AND tbl_faturamento_item.pedido IS NOT NULL AND tbl_pedido_item.qtde > (tbl_pedido_item.qtde_cancelada + tbl_pedido_item.qtde_faturada_distribuidor))
																		      OR (tbl_os_item.pedido IS NOT NULL AND tbl_faturamento.nota_fiscal IS NULL AND tbl_faturamento_item.pedido IS NULL AND tbl_pedido_item.qtde > (tbl_pedido_item.qtde_cancelada + tbl_pedido_item.qtde_faturada_distribuidor))
																		      OR (tbl_os_item.pedido IS NOT NULL AND tbl_faturamento.nota_fiscal IS NULL AND tbl_faturamento_item.pedido IS NULL AND tbl_embarque_item.pedido_item IS NOT NULL)
																		    )";

                                        		$resDataConserto = pg_query($con, $queryDataConserto);
	                                            
	                                           	$total_pecas = pg_fetch_result($resDataConserto, 0, 'total');
										
	                                           	if ($total_pecas == 0) { 
		                                            
	                                           		echo "<input class='frm' data-defeito_constatado_os='$defeito_constatado_os'  data-hd_chamado='$hd_chamado' type='text' id='data_conserto_$i' name='os[$i][data_conserto_$i]' alt='$os' rel='data_conserto' $disabled_conserto size='$size_bd' maxlength='$maxlength_bd' value='$data_conserto'>";
	                                            } else {
	                                            	echo "&nbsp;";
	                                            }
											} else {
												
													$data_conserto_mostrar = $data_conserto;
													if ($login_fabrica == 1 && $_POST['data_conserto_finalizar'] != "" && !empty($checked_ativo)) {
														$sqlCom = "SELECT data_conserto FROM tbl_os WHERE os = $os AND fabrica = $login_fabrica AND data_conserto NOTNULL";
														$resCom = pg_query($con, $sqlCom);
														if (pg_num_rows($resCom) > 0) {
															$data_conserto_mostrar = (empty($data_conserto)) ? $_POST['data_conserto_finalizar'] : $data_conserto;
														} else {
															$data_conserto_mostrar = "";
														}
													}

													echo "<input class='frm' data-defeito_constatado_os='$defeito_constatado_os'  data-hd_chamado='$hd_chamado' type='text' id='data_conserto_$i' name='os[$i][data_conserto_$i]' alt='$os' rel='data_conserto' $disabled_conserto size='$size_bd' maxlength='$maxlength_bd' value='$data_conserto_mostrar' data-posicao='$i' ";
											echo (($login_fabrica == 3 or $login_fabrica ==7 or $login_fabrica == 1) AND strlen($data_conserto_mostrar)>0) ? " disabled " : "";
											echo ">"; 
												
											} 	
										}

										// Retirada 160 HD-6796695
										if (in_array($login_fabrica, [123])) {
											$xtem_anexo = '';
											$xtem_anexo = tem_anexo($os);
									
											if ($xtem_anexo === 'imprimir_termo') {
												echo "<td align='center' nowrap>
						                    		  	<a href='termo_retirada.php?os=$os' target='_blank'>Imprimir</a>
						               	 		      </td>";	
											} else if ($xtem_anexo === 'anexa_termo') {
												echo "<td align='center' nowrap>
							                    		<input type='button' onclick='anexar_termo({$os})' value='Anexar'>
							               	 		  </td>";
							               	} else if ($xtem_anexo === 'erro') {
							               		echo "<td align='center' nowrap>
							                    		Para anexar a OS deve estar Consertada
							               	 		  </td>";
							               	} else {
							               		echo "<td align='center' nowrap>
							                    		OK
							               	 		  </td>";
							               	}
										}

										if ($login_fabrica == 1 && !strlen($data_conserto)) {
											echo "&nbsp;";
											echo "<button type='button' name='grava_data_conserto' >".traduz('Gravar e Finalizar')."</button>";
											echo "<img class='loading' src='imagens/ajax-loader.gif' style='vertical-align: middle; display: none;' />";
										}
									}else{
										echo "&nbsp;";
									}

									if(in_array($login_fabrica,array(165)) && $posto_interno == true){

						                echo "<td align='center' nowrap>
						                    <button type='button' onclick='listaOS({$os})' style='cursor: pointer; margin: 5px;'>NF de Saída</button>
						                </td>";
						            }

								}

								echo "</td>";

								if (in_array($login_fabrica, array(169,170,174)) AND strlen(trim($hd_chamado)) > 0){
								    if(strlen($data_conserto) > 0 AND strlen($admin_postagem) > 0){
				                        $display_lgr = "";
				                    }else{
				                        $display_lgr = "display: none;";
				                    }
					            ?>
									<td style="width: 320px; text-align: center;">
				                        <button id='lgr_correios_<?=$os?>' style="padding:5px; margin-top: 4px; margin-bottom: 4px; cursor: pointer; <?=$display_lgr?>" type="button" onclick="javascript: solicitaPostagem('<?=$hd_chamado?>','<?=$codigo_posto;?>');"><?= traduz('Solicitação Postagem/Coleta') ?></button>
				                    </td>
								<?
								}
								if($login_fabrica == 120 or $login_fabrica == 201){
									echo "<td class='btn_finaliza_$i'><img src='imagens/btn_fechar_azul.gif' onclick='finalizaOsNewmaq($os, $i)'> </td>";
								}

								#hd 180939
								if ($login_fabrica == 2) {
									echo "<td align='center'>";
									echo "<input class='frm' type='text' name='pac_$i' alt='$os' rel='pac' size='13' maxlength='13' value='$pac' >";
									echo "</td>";
								}

                                if ($login_fabrica == 156 and $posto_interno == true) {
                                    echo "<td align='center'>";
                                    echo "<input class='frm' type='text' name='os[$i][data_liberacao_$i]' rel='data_liberacao' size='13' value='{$_POST["os"][$i]["data_liberacao_{$i}"]}'>";
                                    echo "</td>";
                                }


							}

							if($login_fabrica == 52){

								$sqlKm = "SELECT os FROM tbl_os_status WHERE os = $os";
								$resKm = pg_query($con,$sqlKm);
								if(pg_numrows($resKm) > 0){
									$sqlI = "SELECT os
									INTO TEMP tmp_auditoria_os_$os
									FROM tbl_os_status
									WHERE os = $os
									AND   fabrica_status = $login_fabrica
									AND status_os IN (13,19,67,68,70,127,131);

									SELECT
									interv.os
									INTO TEMP tmp_auditoria_os2_$os
									FROM (
										SELECT
										ultima.os,
										(
											SELECT status_os
											FROM tbl_os_status
											WHERE status_os IN (13,19,67,68,70,127,131)
											AND fabrica_status = $login_fabrica
											AND tbl_os_status.os = ultima.os
											ORDER BY data
											DESC LIMIT 1
											) AS ultimo_status
                                        FROM (
                                            SELECT os FROM tmp_auditoria_os_$os
                                            ) ultima
                                        ) interv
                                        WHERE interv.ultimo_status IN (19)
                                        ;";
                                        $resI = pg_query($con,$sqlI);
                                        if(pg_numrows($resI) > 0){
                                            $sqlKm = "SELECT tbl_os.qtde_km_calculada, tbl_os.mao_de_obra FROM tbl_os WHERE os = $os";
                                            $resKm = pg_query($con,$sqlKm);
                                        }
                                        } else {
                                            $sqlKm = "SELECT tbl_os.qtde_km_calculada, tbl_os.mao_de_obra FROM tbl_os WHERE os = $os";
                                            $resKm = pg_query($con,$sqlKm);
                                        }


                                        if($login_fabrica == 52){
                                            $sqlKm = "SELECT tbl_os.qtde_km_calculada, tbl_os.mao_de_obra FROM tbl_os WHERE os = $os";
                                            $resKm = pg_query($con,$sqlKm);
                                        }

                                        $qtde_km_calculada = pg_result($resKm,0,qtde_km_calculada);
                                        $mao_de_obra = pg_result($resKm,0,mao_de_obra);


                                        $total_os_valores = $mao_de_obra + $qtde_km_calculada;
?>
<td align="center">
	<input type="button" value="Valores da OS" onclick="javascript: if(document.getElementById('valores_os_<?php echo $i; ?>').style.display == 'none'){document.getElementById('valores_os_<?php echo $i; ?>').style.display = 'block';} else {document.getElementById('valores_os_<?php echo $i; ?>').style.display = 'none';}">
	<table id="valores_os_<?php echo $i; ?>" border="0" class="tabela" style="display:none; border:solid 1px;">

		<?php if($login_fabrica == 52){ ?>
		<tr>
			<td colspan='2' align="left"><strong style="color:red;"><b>*<?= traduz('Valores sujeito a alteracao até o fechamento do extrato') ?></b></strong></td>
		</tr>
		<?php } ?>
		<tr>
			<td align="left"><b><?= traduz('Serviço executado') ?></b></td>
			<td align="left">R$ <?php echo number_format($mao_de_obra,2,',','.') ;?></td>
		</tr>
		<tr>
			<td><b><?= traduz('Quilometragem') ?></b></td>
			<td>R$ <?php echo number_format($qtde_km_calculada,2,',','.') ;?></td>
		</tr>
		<tr>
			<td><b><?= traduz('Total da OS') ?></b></td>
			<td>R$ <?php echo number_format($total_os_valores,2,',','.') ;?></td>
		</tr>
	</table>
</td>
<?php
}
?>
<?php
if($login_fabrica == 1){
	echo "<td>$tipo_os</td>";
}
?>
</tr><?php

if ($login_fabrica == 3 and strlen($os) > 0) {
			//HD 6477
	$sqlp = "SELECT peca, pedido, qtde FROM tbl_os_item WHERE os_item = $os_item;";

			#HD 51236  - gera_pedido IS TRUE
			#HD 160093 - Alterado para verificar se a peça tem faturamento
	$sqlP = "SELECT  tbl_os_item.os_item,
	tbl_os_item.pedido ,
	tbl_os_item.peca   ,
	tbl_os_item.qtde
	FROM tbl_os_produto
	JOIN tbl_os_item           ON tbl_os_produto.os_produto     = tbl_os_item.os_produto
	JOIN tbl_servico_realizado ON tbl_os_item.servico_realizado = tbl_servico_realizado.servico_realizado
	LEFT JOIN tbl_faturamento_item on tbl_os_item.peca = tbl_faturamento_item.peca and tbl_os_item.pedido = tbl_faturamento_item.pedido
	WHERE tbl_os_produto.os = $os
	AND tbl_servico_realizado.gera_pedido IS TRUE
	AND  tbl_faturamento_item.faturamento_item IS NULL
	LIMIT 1";

	$resP = pg_query($con, $sqlP);

	if (pg_num_rows($resP) > 0) {

		$pendente = "f";

		$pedido = pg_fetch_result($resP, 0, 'pedido');
		$peca   = pg_fetch_result($resP, 0, 'peca');
		$qtde   = pg_fetch_result($resP, 0, 'qtde');

		if (strlen($pedido) > 0) {

			$sqlC = "SELECT os
			FROM tbl_pedido_cancelado
			WHERE pedido  = $pedido
			AND   peca    = $peca
			AND   qtde    = $qtde
			AND   os      = $os
			AND   fabrica = $login_fabrica";

			$resC = pg_query($con, $sqlC);

			if (pg_num_rows($resC) == 0) $pendente = "t";

		} else {
			$pendente = "t";
		}

		if ($pendente == "t") {?>
		<tr>
			<td colspan='7'>
				<div id='dados_<? echo $i; ?>' style='position:relative; display:none; border: 1px solid #FF6666;background-color: #FFCC99;width:100%; font-size:9px'><? fecho ("esta.os.que.voce.esta.fechando.tem.pecas", $con, $cook_idioma); /*Esta OS que você está fechando tem peças*/?> <strong><? fecho ("pendentes", $con, $cook_idioma); /*pendentes*/?></strong>! <? fecho ("motivo.do.fechamento", $con, $cook_idioma); /*Motivo do Fechamento: */?>
					<input class='frm' type='text' name='motivo_fechamento_<?echo$i;?>' size='30' maxlength='100' value='' />
				</div>
			</td>
		</tr><?php
	}

}

}

if (( in_array($login_fabrica, array(11,172)) and $login_posto == 14301 and strlen($consumidor_email) > 0) OR $login_fabrica == 137) {?>
<TR bgcolor="<?echo $cor;?>"><td colspan="100%"><? fecho ("observacao", $con, $cook_idioma); /*Observação*/?>: <input type="text" name="observacao_<?echo $i;?>" size="100" maxlength="200" value="" title="<? if($login_fabrica != 137) { fecho ("esta.informacao.sera.inserido.na.interacao.da.os.mandado.junto.com.o.email", $con, $cook_idioma); /*Esta informação será inserido na interação da OS e mandado junto com o email*/ } ?>" value="<?=$observacao?>"></td></TR><?php
}

}

$os_anterior = $os;

}

if ($login_fabrica == 1) {
	$xusa_estoque_posto = "nao";
	$usa = pg_fetch_result($res, 0, 'reembolso_peca_estoque');
	
	if (strtolower($usa) == 't' || strtolower($usa) == 'true') {
		$xusa_estoque_posto = "sim";
	}
}

?>
<input type='hidden' name='data_conserto_finalizar' id='data_conserto_finalizar' value=''>
<input type='hidden' class="usa_estoque_posto" name='usa_estoque_posto' id='usa_estoque_posto' value='<?=$xusa_estoque_posto?>'>
</tbody>
</table>
<br><br>

<?//HD 9013

if ($login_fabrica == 1 or $login_fabrica == 7) {?>
<table width="700px" border="0" cellspacing="1" cellpadding="4" align="center" style='font-family: verdana; font-size: 10px' class='tabela_resultado sample' id="tabela_os_revenda">
	<caption colspan='100%' style='font-family: verdana; font-size: 20px'><?php
		if ($login_fabrica == 7) {
			fecho ("os.de.manutencao", $con, $cook_idioma); /*"OS de Manutenção"*/
		} else {

			if ($login_fabrica == 1) {
				fecho ("os.de.revenda", $con, $cook_idioma); /*"OS de Revenda / Metal Sanitario"*/
			} else {
				fecho ("os.de.revenda", $con, $cook_idioma); /*"OS de Revenda"*/
			}

		}?>
	</caption>
	<thead>
		<tr height="20">
			<th>
				<input type='checkbox' class='frm' id="checkbox_revenda" name='marcar' value='tudo' title='<? fecho ("selecione.ou.desmarque.todos", $con, $cook_idioma) /*Selecione ou desmarque todos*/;?>' style='cursor: hand;'>
			</th>
			<th nowrap width="150px"><b><? fecho ("os.fabricante", $con, $cook_idioma); /*OS Fabricante*/ ?></b></th>
			<th nowrap><b><? fecho ("data.abertura", $con, $cook_idioma); /*Data Abertura*/ ?></b></th>
			<th nowrap><b><? fecho ("consumidor", $con, $cook_idioma); /*Consumidor*/ ?></b></th>
			<th nowrap><b><? fecho ("produto", $con, $cook_idioma); /*Produto*/ ?></b></th>
			<?php
			if ($login_fabrica <> 1) {
				echo "<th nowrap><strong>";
				fecho ("nf.saida", $con, $cook_idioma); /*NF de Saída*/
				echo "</strong></th>";
				echo "<th nowrap><strong>";
				fecho ("data.nf.saida", $con, $cook_idioma); /*Data NF de Saída*/
				echo "</strong></th>";
			}

			if (($login_posto == 4311) && $login_fabrica != 137 and !isset($novaTelaOs)) {
				echo "<th nowrap><strong>";
				fecho ("box", $con, $cook_idioma); /*Box*/
				echo "</strong></th>";
			}

				//HD 12521       HD 13239    HD 14121   HD 135436(+Mondial))
			if (usaDataConserto($login_posto, $login_fabrica)) {
				echo "<th nowrap><strong>";
				fecho ("data.de.conserto", $con, $cook_idioma); /*Data de conserto*/
				echo "</strong></th>";
			}
			if ($login_fabrica == 1) {
				echo "<th nowrap><strong>";
				fecho ("tipo.os", $con, $cook_idioma); /*Data de conserto*/
				echo "</strong></th>";
			}?>
		</tr>
	</thead>
	<tbody><?php

		for ($i = 0; $i < pg_num_rows($res); $i++) {

			$flag_cor           = "";
			$cor                = ($i % 2 == 0) ? '#F1F4FA' : "#FFFFFF";
			$consumidor_revenda = trim(pg_fetch_result ($res,$i,consumidor_revenda));

			if ($consumidor_revenda == 'R' and ($login_fabrica == 1 or $login_fabrica == 7)) {

				$os               = trim(pg_fetch_result($res, $i, 'os'));
				$sua_os           = trim(pg_fetch_result($res, $i, 'sua_os'));
				$cortesia         = trim(pg_fetch_result($res, $i, 'cortesia'));
				$tipo_os_cortesia = trim(pg_fetch_result($res, $i, 'tipo_os_cortesia'));
				$admin            = trim(pg_fetch_result($res, $i, 'admin'));
				$tipo_atendimento = trim(pg_fetch_result($res, $i, 'tipo_atendimento'));
				$produto          = trim(pg_fetch_result($res, $i, 'produto'));
				$status_checkpoint=	pg_fetch_result($res, $i,'status_checkpoint');
				$tipo_os 		  =	pg_fetch_result($res, $i,'tipo_os');

				//HD 13239
				//HD 12521       HD 13239    HD 14121   HD 135436(+Mondial))
				if (usaDataConserto($login_posto, $login_fabrica)) {
					$data_conserto           = trim(pg_fetch_result ($res,$i,data_conserto));
				}

				//HD 4291 Paulo
				if (($login_posto == 4311 or $login_posto == 6359) && $login_fabrica != 137 and !isset($novaTelaOs)) {
					$prateleira_box = trim(pg_fetch_result ($res, $i, 'prateleira_box'));
				}

				if (strlen($sua_os) == 0) $sua_os = $os;

				$descricao = pg_fetch_result ($res, $i, 'nome_comercial');
				if (strlen($descricao) == 0) $descricao = pg_fetch_result ($res, $i, 'descricao');

				$consumidor_revenda = trim(pg_fetch_result ($res, $i, 'consumidor_revenda'));
				$defeito_constatado = trim(pg_fetch_result ($res, $i, 'defeito_constatado'));

				if ($login_fabrica == 1) {

					if ($status_checkpoint == 4 or $status_checkpoint == 3) {
						$flag_bloqueio = "";
						$flag_cor      = "";
					} else {
						$flag_cor      = "t";
						$flag_bloqueio = "t";
					}

					if ($tipo_os_cortesia == 'Devolução de valor' && $cortesia == 't') {
						$flag_bloqueio = '';
					} else if (strlen($defeito_constatado) == 0) {

						$flag_cor      = "t";
						$flag_bloqueio = "t";
					} else if ($flag_bloqueio == "t" && strlen($defeito_constatado) != 0) {
						$flag_bloqueio = "t";
					} else {
						$flag_bloqueio = "";

					}

					if(strlen($defeito_constatado) == 0){

								$sql = "SELECT os FROM tbl_laudo_tecnico_os WHERE os = $os";
								$resL = pg_query($con, $sql);
								if(pg_num_rows($resL) > 0){ echo "sim";
									$flag_bloqueio = "";
								}else{
									$flag_bloqueio = "t";
								}
					}

				} //HD 4291 Fim

				// HD 101630
				$erros = (!empty($linha_erro)) ? implode($linha_erro,",") : null;
				if(strpos($erros,$os) > 0 or $erros == $os) {
					$cor = "#FF0000";
				}?>
				<tr bgcolor=<? echo $cor;echo " rel='status_$status_checkpoint' ";?> <? if ((strpos($erros,$os) > 0 or $erros == $os) and strlen ($msg_erro) > 0 )?>>
					<input type='hidden' name='os[<?=$i?>][os_<? echo $i ?>]' value='<? echo pg_fetch_result ($res,$i,os) ?>' >
					<input type='hidden' name='os[<?=$i?>][conta_<? echo $i ?>]' value='<? echo $i;?>'>
					<input type='hidden' name='os[<?=$i?>][consumidor_revenda_<? echo $i ?>]' value='<? echo pg_fetch_result ($res,$i,consumidor_revenda)?>'>
					<td align="center"><?php
						if (strlen($flag_bloqueio) == 0) {
							if ($_POST["os"][$i]['ativo_revenda_'.$i] == 't'){
								$checked_ativo_revenda = "checked='checked'";
							}else{
								$checked_ativo_revenda = "";
							}
							?>
							<input type="checkbox" class="frm os" name="os[<?=$i?>][ativo_revenda_<?echo $i?>]" <?php echo $checked_ativo_revenda ?> id="ativo_revenda" value="t" ><?php
						}?>
					</td>
					<td><a href='<? if ($cor == "#FFCC66" or ( in_array($login_fabrica, array(11,172)) and $login_posto == 14254)) echo "os_item"; else echo "os_press"; ?>.php?os=<? echo $os ?>' target='_blank'><? if ($login_fabrica == 1)echo $login_codigo_posto; echo $sua_os; ?></a></td>
					<td><? echo pg_fetch_result ($res,$i,data_abertura) ?></td>
					<td NOWRAP><? echo substr(pg_fetch_result ($res,$i,consumidor_nome),0,10) ?></td>
					<? if($login_fabrica == 1){ ?>
						<td NOWRAP><? echo pg_fetch_result($res,$i,referencia) ?></td>
					<?}else{?>
						<td NOWRAP><? echo pg_fetch_result($res,$i,serie) . " - " . substr ($descricao,0,15) ?></td>
<?
					}
					if(!in_array($login_fabrica ,array(1,147,156,180,181,182))){
						echo "<td><input class='frm' type='text' name='nota_fiscal_saida_$i' size='8' maxlength='10' value='$nota_fiscal_saida'></td>";
						echo "<td><input class='frm' type='text' name='data_nf_saida_$i' size='12'  onKeyUp=\"formata_data(this.value,'frm_os', 'data_nf_saida_$i')\"  maxlength='10' value='$data_nf_saida'></td>";
					}

					if ($login_fabrica == 156 && $posto_interno == true) {
            			echo"
            			<td>
            				<input class='frm' size='8' type='text' name='nf_retorno_$i' maxlength='10' value='$nf_retorno' />
            			</td>
            			<td>
            				<input class='frm date' size='8' type='text' name='data_nf_retorno_$i' onKeyUp=\"formata_data(this.value, 'frm_os', 'data_nf_retorno_$i')\" value='$data_nf_retorno' />
            			</td>
            			<td>
            				<input class='frm decimal' size='8' type='text' name='valor_nf_retorno_$i' value='$valor_nf_retorno' />
            			</td>
            			";
            		}

					if( in_array($login_fabrica, array(11,172)) or $login_posto == '6359' and !isset($novaTelaOs)) {
						echo "<td align='center'>$prateleira_box</td>";
					}

					//HD 12521       HD 13239    HD 14121   HD 135436(+Mondial))
					$mostrar_data = '';
					if(usaDataConserto($login_posto, $login_fabrica)) {

						if ($login_fabrica == 1){

							if ($status_checkpoint <> 3){
								$mostrar_data = '';
							}else{
								$mostrar_data = '1';
							}

							$sqlY = "SELECT os
							FROM   tbl_os
							JOIN   tbl_os_troca USING(os)
							WHERE  os = $os
							AND    tbl_os.fabrica = $login_fabrica";

							$resY = pg_query($con, $sqlY);

							if (pg_num_rows($resY) > 0) {
								$sqlZ = "	SELECT tbl_os_item.os_item
											FROM tbl_os_item
											JOIN tbl_os_produto USING(os_produto)
											JOIN tbl_os_item_nf USING(os_item)
											WHERE tbl_os_produto.os = $os 
											AND tbl_os_item.fabrica_i = $login_fabrica
											AND tbl_os_item_nf.data_nf + interval '3 days' <= current_date";
								$resZ = pg_query($con, $sqlZ);
								if (pg_num_rows($resZ) > 0) {
									$mostra_data = '1';
								} else {
									$mostra_data = '';
								}
							}else{
								$mostrar_data = $mostrar_data;
							}
							
							if (empty($mostrar_data)) {
								$sql_reembolso = "  SELECT posto 
													FROM tbl_posto_fabrica
													WHERE posto = $login_posto
													AND tbl_posto_fabrica.reembolso_peca_estoque IS TRUE";
								$res_reembolso = pg_query($con, $sql_reembolso);
								if (pg_num_rows($res_reembolso) > 0) {
									$mostra_data = '1';
								}
							}

						}else{

							$mostra_data = "1";

						}

						if ($data_conserto){
							$mostrar_data = '1';
						}

						if (!empty($mostrar_data)){

							echo "<td align='center'> ";
							if(strlen($data_conserto)>0){

								echo "<input class='frm' type='text' name='os[$i][data_conserto_$i]' alt='$os' rel='data_conserto' size='18' maxlength='16' value='$data_conserto' disabled>";
							}else{
								echo "<input class='frm' type='text' name='os[$i][data_conserto_$i]' alt='$os' rel='data_conserto' size='18' maxlength='16' value='$data_conserto'>";
								if ($login_fabrica == 1 && !strlen($data_conserto)) {
									echo "&nbsp;";
									echo "<button type='button' name='grava_data_conserto' >Gravar e Finalizar</button>";
									echo "<img class='loading' src='imagens/ajax-loader.gif' style='vertical-align: middle; display: none;' />";
								}
							}
							echo "</td>";

						}else{

							if ($login_fabrica != 1) {
								echo "<td align='center'>";
								echo "&nbsp;";
								echo "</td>";
							}

						}

						if($login_fabrica == 1){
							echo "<td align='center'>";
							echo "$tipo_os";
							echo "</td>";
						}

					}?>
				</tr>
				<?}
			}?>
		</tbody>
	</table>

	<?}?>
</td>
<td><img height="1" width="16" src="imagens/spacer.gif"></td>
</tr>

<?php if($login_fabrica <> 176){ ?>
<tr><td>&nbsp;</td></tr>
<tr>
	<td height="27" valign="middle" align="center" colspan="3" background="" bgcolor="#FFFFFF">
		<input type="hidden" name="btn_acao" value="">
		<?php
		if ($sistema_lingua == "ES") {
		    if ($login_fabrica == 1) {

				?>
				<img src='imagens/btn_cerrar_maior.gif' onclick="submitForm();" ALT="Continuar con orden de servicio" border='0' style='cursor: pointer'>
				<?php
    		} else {
				?>
				<img src='imagens/btn_cerrar_maior.gif' onclick="javascript:  disableConserto()
				if (document.frm_os.btn_acao.value == '' ){
				document.frm_os.btn_acao.value='continuar' ; document.frm_os.submit()
					} else {
					alert ('Aguarde submissão')
				};" ALT="Continuar con orden de servicio" border='0' style='cursor: pointer'>
			<?php
			}
		} else {
			if ($login_fabrica == 1) {
				$total_registro = $mult_pag->Retorna_Resultado();
				?>
				<img src='imagens/btn_fechar_azul.gif' onclick="submitForm();" ALT="Continuar com Ordem de Serviço" border='0' style='cursor: pointer'>
				<input type="hidden" id="total_registro" value="<?=$total_registro?>" />
				<?php
			} else {
				if($login_fabrica == 3){
					?>
					<!--
					  <script src="plugins/shadowbox_lupa/shadowbox.js" ></script>
    				  <link rel="stylesheet" href="plugins/shadowbox_lupa/shadowbox.css" type="text/css" /> -->

					<script type="text/javascript">

						function confirmafechamento(){

								Shadowbox.init();

					            Shadowbox.open({
					                content: '<div style="width: 90%; padding: 20px;"> \
					                            <h1>Declaro para os devidos fins, que a data informada da retirada do produto está em \
					                            concordância com a data registrada na Ordem de Serviço assinada pelo Consumidor</h1> <br /> \
					                            <br /> \
					                            <button style="float: right; color: #fff; background-color: #d9534f; border-color: #d43f3a;" 					                            onClick="recusafecha()">Não Concordo</button> \
					                            <button style="float: right; color: #fff; background-color: #5cb85c; border-color: #4cae4c;" 					                            onClick="confirmafecha()">Concordo</butt3on> \
					                        </div>',
					                player: "html",
					                title: "Confirmação de Entrega",
					                width: 800,
					                height: 500
					            });
					        }
						function recusafecha (){
							Shadowbox.close();
							return false;
						}
						function confirmafecha (){
							if (document.frm_os.btn_acao.value == '' ){
								document.frm_os.btn_acao.value='continuar';
								document.frm_os.submit();
							} else {
								alert ('Aguarde submissão')
							}
						}

					</script>
				<?php
				}
					if(!in_array($login_fabrica, [167, 203])){
					?>
					<img src='imagens/btn_fechar_azul.gif' onclick="javascript:
							<? if($login_fabrica == 3){ ?>
								confirmafechamento();
							<? } else if($login_fabrica == 131){ ?>
									confirmaRecebimentoPeca(<?=$os?>,'os_fechamento');
							<? } else { ?>
								if (document.frm_os.btn_acao.value == '' ){
									document.frm_os.btn_acao.value='continuar';
									document.frm_os.submit();
								} else {
									alert ('Aguarde submissão')
								}
							<? } ?>
						" ALT="Continuar com Ordem de Serviço" border='0' style='cursor: pointer'>
				<?php
				}
			}
		}
?>
</td>
</tr>
<?php } ?>
</table>
</form>
<?php

		// ##### PAGINACAO ##### //
		// links da paginacao
echo "<br>";
echo "<div>";

if ($pagina < $max_links) {
	$paginacao = pagina + 1;
} else {
	$paginacao = pagina;
}

		// paginacao com restricao de links da paginacao

		// pega todos os links e define que 'Próxima' e 'Anterior' serão exibidos como texto plano
$todos_links		= $mult_pag->Construir_Links("todos", "sim");

		// função que limita a quantidade de links no rodape
$links_limitados	= $mult_pag->Mostrar_Parte($todos_links, $coluna, $max_links);

for ($n = 0; $n < count($links_limitados); $n++) {
	echo "<font color='#DDDDDD'>".$links_limitados[$n]."</font>&nbsp;&nbsp;";
}
echo "</div>";

$resultado_inicial = ($pagina * $max_res) + 1;
$resultado_final   = $max_res + ( $pagina * $max_res);
$registros         = $mult_pag->Retorna_Resultado();
$valor_pagina   = $pagina + 1;
$numero_paginas = intval(($registros / $max_res) + 1);

if ($valor_pagina == $numero_paginas) $resultado_final = $registros;

if ($registros > 0) {

	echo "<br>";
	echo "<div>";
	fecho("resultados.de.%.a.%.do.total.de.%.registros",$con,$cook_idioma,array("<b>$resultado_inicial</b>","<b>$resultado_final</b>","<b>$registros</b>"));
	echo "<font color='#cccccc' size='1'>";
	fecho("pagina.%.de.%",$con,$cook_idioma,array("<b>$valor_pagina</b>","<b>$numero_paginas</b>"));
	echo "</font>";
	echo "</div>";

}
		// ##### PAGINACAO ##### //

} else {?>

<table width="700" border="0" cellpadding="0" cellspacing="0" align="center">
	<tr>
		<td valign="top" align="center">
			<h4><?php fecho ("nao.foram.encontradas.os(s).para.finalizar", $con, $cook_idioma); ?></h4>
		</td>
	</tr>
</table>
<?php

}

}

if ($login_fabrica == 173 && $posto_interno == TRUE) { ?>
<script type="text/javascript">
	$(function() {
		$("[name='cod_barra_serie']").on('blur', function() {
			var id = $(this).data('os');
			var fabrica = <?php echo $login_fabrica ;?>;
			$.ajax({
	            url: "editar_novo_numero_serie.php",
	            type: "POST",
	            data: { ajax: 'sim', action: 'consultar_serie', os: id, numero: $(this).val(), fabrica: fabrica }
	        }).done(function(data){
	            if (data == 'true') {
	            	alert('OS finalizada');
	            	window.location.reload();
	            }
	        });
		});
	});

</script>
<?php } ?>
<p>
<? include "rodape.php"; ?>
