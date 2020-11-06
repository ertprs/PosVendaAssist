<?php

require dirname(__FILE__) . '/../../dbconfig.php';
require dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
require __DIR__ . '/./funcoes.php';

include dirname(__FILE__) . "/../../class/aws/s3_config.php";
include S3CLASS;

date_default_timezone_set("America/Sao_Paulo");

global $login_fabrica;
$login_fabrica = 158;

$debug = true;

require __DIR__ . '/../../admin/cockpit/api/persys.php';

use Posvenda\Routine;
use Posvenda\RoutineSchedule;
use Posvenda\Log;
use Posvenda\LogError;
use Posvenda\Cockpit;

try { // $e
    if ($debug) {
        echo "\n";
        echo date('d-m-Y H:i');
        echo "\n";
        echo "Iniciando Rotina...\n";
    }

    $routine = new Routine();
    $routine->setFactory($login_fabrica);

    $arr = $routine->SelectRoutine("OS Mobile");
    $routine_id = $arr[0]["routine"];

    $routineSchedule = new RoutineSchedule();
    $routineSchedule->setRoutine($routine_id);
    $routineSchedule->setWeekDay(date("w"));

    $routine_schedule_id = $routineSchedule->SelectRoutineSchedule();

    if (strlen($routine_schedule_id) == 0) {
        throw new \Exception("Rotina não agendada para esta data");
    } else {
        $routineScheduleLog = new Log();
	$routineScheduleLogError = new LogError();

        if ($routineScheduleLog->SelectRoutineWithoutFinish($login_fabrica, $routine_id) === true && $_serverEnvironment == "production") {
            throw new \Exception("Rotina pendente de finalização anterior");
	}

	$sql = "
	    SELECT admin
	    FROM tbl_admin
	    WHERE fabrica = 158
	    AND login = 'rotinaautomatica'
	";
	$qry = pg_query($con, $sql);
	$res = pg_fetch_assoc($qry);
	$login_rotina_automatica = $res["admin"];


        if ($debug) {
            echo date('d-m-Y H:i');
            echo "\n";
            echo "Buscando dados para integração...\n";
        }

        $sql = "
            SELECT 
                tbl_os_mobile.os_mobile, 
                tbl_os_mobile.data_input,
                tbl_os.os, 
                tbl_os.posto, 
                tbl_os_mobile.dados, 
                tbl_os.data_abertura,
                tbl_os.obs,
                tbl_os.tipo_atendimento,
                tbl_os_extra.serie_justificativa AS patrimonio,
                tbl_os_produto.serie,
                tbl_os.hd_chamado,
                tbl_tipo_atendimento.fora_garantia
            FROM tbl_os_mobile 
            INNER JOIN tbl_os ON tbl_os.os = tbl_os_mobile.os AND tbl_os.fabrica = {$login_fabrica}
            INNER JOIN tbl_os_extra ON tbl_os_extra.os = tbl_os.os
            INNER JOIN tbl_os_produto ON tbl_os_produto.os = tbl_os.os
            INNER JOIN tbl_tipo_atendimento ON tbl_tipo_atendimento.tipo_atendimento = tbl_os.tipo_atendimento AND tbl_tipo_atendimento.fabrica = {$login_fabrica}
            WHERE tbl_os_mobile.fabrica = {$login_fabrica}
            AND tbl_os_mobile.conferido IS NOT TRUE
	    AND tbl_os.finalizada IS NULL
	    ORDER BY tbl_os_mobile.data_input ASC;
        ";

        $qry = pg_query($con, $sql);

        $cockpit = new Cockpit($login_fabrica);

        $routineScheduleLog->setRoutineSchedule((integer) $routine_schedule_id);
        $routineScheduleLog->setDateStart(date("Y-m-d H:i:s"));


        if (!$routineScheduleLog->Insert()) {
            throw new \Exception("Erro ao gravar log da rotina");
        }

        $routine_schedule_log_id = $routineScheduleLog->SelectId();
        $routineScheduleLog->setRoutineScheduleLog($routine_schedule_log_id);



        if ($debug && pg_num_rows($qry) > 0) {
            echo "Dados encontrados...\n";
            echo date('d-m-Y H:i');
            echo "\n";
        }

	$resultOsMobile = pg_fetch_all($qry);

	foreach ($resultOsMobile as $row => $fetch) {
	    $persys_AlteraServicoPeca = array();
            $erro_validacao = array();
            $begin = false;

            try { // $ew
                list($ano_abertura, $mes_abertura, $dia_abertura) = explode("-", $fetch["data_abertura"]);

                $obs              = $fetch["obs"];
                $os               = $fetch["os"];
                $posto            = $fetch["posto"];
                $tipo_atendimento = $fetch["tipo_atendimento"];
                $patrimonio       = $fetch["patrimonio"];
                $serie            = $fetch["serie"];
                $hd_chamado       = $fetch["hd_chamado"];
                $dados            = utf8_encode($fetch["dados"]);
                $dados            = json_decode($dados, true);
                $fora_garantia    = $fetch["fora_garantia"];
                $data             = $fetch["data_input"];
                
                if ($debug) {
                    echo "\n";
                    echo date('d-m-Y H:i');
                    echo "\n";
                    echo "OS {$os}...\n";
                    echo "Iniciando Processamento...\n";
                }
                
                $osClass = new \Posvenda\Os($login_fabrica, $os);
                $pdo     = $osClass->_model->getPDO();

                if ($debug) {
                    echo "Verificando se existe os mobile anterior para a OS que não está conferido...\n";
                }

                $sqlOsMobileAnterior = "
                    SELECT os_mobile
                    FROM tbl_os_mobile
                    WHERE fabrica = {$login_fabrica}
                    AND os = {$os}
                    AND data_input < '{$data}'
                    AND conferido IS NOT TRUE
                ";
                $qryOsMobileAnterior = $pdo->query($sqlOsMobileAnterior);

                if ($qryOsMobileAnterior->rowCount() > 0) {
                    if ($debug) {
                        echo "OS Mobile não conferido anterior ao atual encontrado. Registro não processado\n";
                    }

                    throw new \Exception("Registro anterior não conferido");
                }

                $pdo->beginTransaction();
                $begin = true;

                $keys = array("os", "defeitosConstatados", "pecas", "status");

                if ($debug) {
                    echo date('d-m-Y H:i');
                    echo "\n";
                    echo "Validando dados...\n";
                }

                foreach ($keys as $key) {
                    try { // $ewfk
                        if (!array_key_exists($key, $dados)) {
                            throw new \Exception("Campo não encontrado - {$key} #{$fetch["os_mobile"]}");
                        }
                    } catch (\Exception $ewfk) {

                        if ($debug) {
                            echo date('d-m-Y H:i');
                            echo "\n";
                            echo "\n";
                            echo "### ERRO ###\n";
                            echo $ewfk->getMessage()."...\n\n";
                        }

                        $erro_validacao[] = $ewfk->getMessage();

                    }
                }

                if ($debug) {
                    echo date('d-m-Y H:i');
                    echo "\n";
                    echo "Dados validados...\n";
                    echo "Validando OS...\n";
                }

                if ($os <> $dados["os"]) {
                    $erro_validacao[] = "Inconsistência de dados - {$os} <> {$dados["os"]} #{$fetch["os_mobile"]}";
                }

                $sqlOsP = "
                    SELECT 
                        osp.os_produto, 
                        p.produto,
                        p.familia,
                        ta.grupo_atendimento,
                        os.defeito_reclamado
                    FROM tbl_os_produto AS osp 
                    INNER JOIN tbl_os AS os ON os.os = osp.os AND os.fabrica = {$login_fabrica}
                    INNER JOIN tbl_tipo_atendimento AS ta ON ta.tipo_atendimento = os.tipo_atendimento AND ta.fabrica = {$login_fabrica}
                    INNER JOIN tbl_produto AS p ON p.produto = osp.produto AND p.fabrica_i = {$login_fabrica}
                    WHERE osp.os = {$os};
                ";
                $qryOsP = $pdo->query($sqlOsP);

                if (!$qryOsP || $qryOsP->rowCount() == 0) {
                    $erro_validacao[] = "Problemas na verificação da OS - {$os} #{$fetch["os_mobile"]}";
                }

                $resOsP = $qryOsP->fetch();

                $os_produto        = $resOsP["os_produto"];
                $familia           = $resOsP["familia"];
                $grupo_atendimento = $resOsP["grupo_atendimento"];
                $defeito_reclamado = $resOsP["defeito_reclamado"];

                if ($debug) {
                    echo date('d-m-Y H:i');
                    echo "\n";
                    echo "OS validada...\n";
                }

                if ($grupo_atendimento == 'S') {
                    if ($debug) {
                        echo date('d-m-Y H:i');
                        echo "\n";
                        echo "OS de Sanitização desconsidera informações de Defeito Constatado e Solução...\n";
                    }
                } else {

                    if ($debug) {
                        echo date('d-m-Y H:i');
                        echo "\n";
                        echo "Validando Defeitos Constatados...\n";
                    }

                    if (count($dados['defeitosConstatados']) > 0) {

                        $defeitos = array();

                        foreach ($dados["defeitosConstatados"] as $dc) {
                            try { // $ewfd
                                $defeito = explode("_", $dc);
                                $dc      = $defeito[0];

                                $sqlDc = "
                                    SELECT dc.defeito_constatado
                                    FROM tbl_diagnostico AS d
                                    INNER JOIN tbl_defeito_constatado AS dc ON dc.defeito_constatado = d.defeito_constatado AND dc.fabrica = {$login_fabrica}
                                    WHERE d.fabrica = {$login_fabrica}
                                    AND d.familia = {$familia}
                                    AND TRIM(dc.codigo) = TRIM('{$dc}')
                                    AND d.garantia IS NOT TRUE
                                    AND d.ativo IS TRUE;
                                ";
                                $qryDc = $pdo->query($sqlDc);

                                if (!$qryDc || $qryDc->rowCount() == 0) {
                                    throw new \Exception("Defeito constatado não encontrado - {$dc} #{$fetch["os_mobile"]}");
                                }

                                $resDc = $qryDc->fetch();

                                $defeito_constatado = $resDc["defeito_constatado"];

                                $sqlDrc = "
                                    SELECT defeito_constatado_reclamado
                                    FROM tbl_os_defeito_reclamado_constatado
                                    WHERE os = {$os}
                                    AND defeito_constatado = {$defeito_constatado};
                                ";
                                $qryDrc = $pdo->query($sqlDrc);

                                $defeitos[] = $defeito_constatado;

                                if ($qryDrc->rowCount() == 0) {
                                    $ins = "
                                        INSERT INTO tbl_os_defeito_reclamado_constatado (os, defeito_constatado,fabrica)
                                        VALUES ({$os}, {$defeito_constatado},{$login_fabrica});
                                    ";
                                    $qryIns = $pdo->query($ins);

                                    if (!$qryIns) {
                                        throw new \Exception("Erro ao gravar defeito constatado - {$defeito_constatado} #{$fetch["os_mobile"]}");
                                    }
                                }
                            } catch (\Exception $ewfd) {

                                if ($debug) {
                                    echo date('d-m-Y H:i');
                                    echo "\n";
                                    echo "\n";
                                    echo "### ERRO ###\n";
                                    echo $ewfd->getMessage()."...\n\n";
                                }

                                $erro_validacao[] = $ewfd->getMessage();

                            }
                        }

                        if (count($defeitos) > 0 && $fora_garantia != "t") {

                            if ($debug) {
                                echo date('d-m-Y H:i');
                                echo "\n";
                                echo "Verificando Defeitos e Reincidência...\n";
                            }

                            $sqlAudRein = "
                                SELECT os 
                                FROM tbl_os 
                                WHERE fabrica = {$login_fabrica} 
                                AND os = {$os} 
                                AND os_reincidente IS NOT TRUE
                            ";
                            $resAudRein = $pdo->query($sqlAudRein);

                            if ($resAudRein->rowCount() > 0
                                && !empty($tipo_atendimento) 
                                && !empty($hd_chamado) 
                                && !empty($defeitos) 
                                && (!empty($patrimonio) || !empty($serie)) 
                                && verifica_auditoria_unica("tbl_auditoria_status.reincidente = 't'", $os) === true
                            ) {
                                $sqlAudRein = "
                                    SELECT tbl_cliente_admin.codigo 
                                    FROM tbl_hd_chamado
                                    INNER JOIN tbl_cliente_admin ON tbl_cliente_admin.cliente_admin = tbl_hd_chamado.cliente_admin AND tbl_cliente_admin.fabrica = {$login_fabrica}
                                    WHERE tbl_hd_chamado.fabrica = {$login_fabrica}
                                    AND tbl_hd_chamado.hd_chamado = {$hd_chamado}
                                ";
                                $resAudRein = $pdo->query($sqlAudRein);

                                if (!$resAudRein || $resAudRein->rowCount() == 0) {
                                    if ($debug) {
                                        echo date('d-m-Y H:i');
                                        echo "\n";
                                        echo "\n";
                                        echo "### ERRO ###\n";
                                        echo "Erro ao verificar o cliente admin da OS - {$os} #{$fetch["os_mobile"]}...\n\n";
                                    }

                                    $erro_validacao[] = "Erro ao verificar o cliente admin da OS - {$os} #{$fetch["os_mobile"]}";
                                }

                                $resAudRein = $resAudRein->fetch();

                                $codigo_cliente_admin = $resAudRein["codigo"];

                                if (in_array(strtoupper($codigo_cliente_admin), array("158-ALPUNTO", "158-KOF"))) {
                                    $whereSeriePatrimonio = "
                                        AND tbl_os_extra.serie_justificativa = '{$patrimonio}'
                                    ";
                                    $msg = "Patrimônio e Defeito Constatado";
                                } else {
                                    $whereSeriePatrimonio = "
                                        AND tbl_os_produto.serie = '{$serie}'
                                    ";
                                    $msg = "Número de Série e Defeito Constatado";
                                }

                                $sqlAudRein = "
                                    SELECT tbl_os.os
                                    FROM tbl_os
                                    INNER JOIN tbl_os_extra ON tbl_os_extra.os = tbl_os.os
                                    INNER JOIN tbl_os_produto ON tbl_os_produto.os = tbl_os.os
                                    WHERE tbl_os.fabrica = {$login_fabrica}
                                    {$whereSeriePatrimonio}
                                    AND ARRAY[".implode(", ", $defeitos)."] && ARRAY(
                                        SELECT defeito_constatado 
                                        FROM tbl_os_defeito_reclamado_constatado 
                                        WHERE tbl_os_defeito_reclamado_constatado.os = tbl_os.os
                                    )
                                    AND tbl_os.excluida IS NOT TRUE
                                    AND tbl_os.posto = {$posto}
                                    AND tbl_os.os < {$os}
                                    AND tbl_os.data_abertura > (CURRENT_DATE - INTERVAL '90 days')
                                    ORDER BY tbl_os.data_abertura DESC
                                    LIMIT 1;
                                ";
                                $resAudRein = $pdo->query($sqlAudRein);

                                if ($resAudRein->rowCount() > 0) {
                                    $resAudRein = $resAudRein->fetch();
                                    $os_reincidente_numero = $resAudRein["os"];

                                    $busca = buscaAuditoria("tbl_auditoria_status.reincidente = 't'");

                                    if($busca['resultado']){
                                        $auditoria_status = $busca['auditoria'];
                                    }

                                    $sqlAudRein = "
                                        INSERT INTO tbl_auditoria_os (os, auditoria_status, observacao)
                                        VALUES ({$os}, {$auditoria_status}, 'OS Reincidente por {$msg}');
                                    ";
                                    $resAudRein = $pdo->query($sqlAudRein);

                                    if (!$resAudRein) {
                                        if ($debug) {
                                            echo date('d-m-Y H:i');
                                            echo "\n";
                                            echo "\n";
                                            echo "### ERRO ###\n";
                                            echo "Erro ao gravar alterações da OS - {$os} #{$fetch["os_mobile"]}...\n\n";
                                        }

                                        $erro_validacao[] = "Erro ao gravar alterações da OS - {$os} #{$fetch["os_mobile"]}";
                                    } else {
                                        $sqlAudRein = "UPDATE tbl_os SET os_reincidente = TRUE WHERE fabrica = {$login_fabrica} AND os = {$os}";
                                        $resAudRein = $pdo->query($sqlAudRein);

                                        if (!$resAudRein) {
                                            if ($debug) {
                                                echo date('d-m-Y H:i');
                                                echo "\n";
                                                echo "\n";
                                                echo "### ERRO ###\n";
                                                echo "Erro ao gravar alterações da OS - {$os} #{$fetch["os_mobile"]}...\n\n";
                                            }

                                            $erro_validacao[] = "Erro ao gravar alterações da OS - {$os} #{$fetch["os_mobile"]}";
                                        }

                                        $sqlAudRein = "UPDATE tbl_os_extra SET os_reincidente = {$os_reincidente_numero} WHERE os = {$os}";
                                        $resAudRein = $pdo->query($sqlAudRein);

                                        if (!$resAudRein) {
                                            if ($debug) {
                                                echo date('d-m-Y H:i');
                                                echo "\n";
                                                echo "\n";
                                                echo "### ERRO ###\n";
                                                echo "Erro ao gravar alterações da OS - {$os} #{$fetch["os_mobile"]}...\n\n";
                                            }

                                            $erro_validacao[] = "Erro ao gravar alterações da OS - {$os} #{$fetch["os_mobile"]}";
                                        }
                                    }
                                }
                            }

                            if ($debug) {
                                echo date('d-m-Y H:i');
                                echo "\n";
                                echo "Fim verificação Reincidência...\n";
                            }
                        }

                        if ($debug) {
                            echo date('d-m-Y H:i');
                            echo "\n";
                            echo "Defeitos Constatados validados...\n";
                        }
                    }

                    if (count($dados["solucao"]) > 0) {
                        if ($debug) {
                            echo date('d-m-Y H:i');
                            echo "\n";
                            echo "Validando Solução...\n";
                        }

                        foreach ($dados["solucao"] as $sl) {
                            try { // $ewfsl
                                $solucao = explode("_", $sl);
                                $sl = $solucao[0];

                                $sqlSolucao = "
                                    SELECT s.solucao
                                    FROM tbl_solucao AS s
                                    INNER JOIN tbl_diagnostico AS d ON d.solucao = s.solucao AND d.fabrica = {$login_fabrica}
                                    INNER JOIN tbl_os_defeito_reclamado_constatado AS odrc ON odrc.defeito_constatado = d.defeito_constatado AND odrc.os = {$os}
                                    WHERE s.fabrica = {$login_fabrica}
                                    AND TRIM(s.codigo) = TRIM('{$sl}')
                                    AND d.garantia IS NOT TRUE
                                    AND d.ativo IS TRUE
                                ";
                                $qrySolucao = $pdo->query($sqlSolucao);

                                if (!$qrySolucao || $qrySolucao->rowCount() == 0) {
                                    if ($debug) {
                                        echo date('d-m-Y H:i');
                                        echo "\n";
                                        echo "\n";
                                        echo "### ERRO ###\n";
                                        echo "Solução não encontrada - {$sl} #{$fetch["os_mobile"]}...\n\n";
                                    }

                                    throw new \Exception("Solução não encontrada - {$sl} #{$fetch["os_mobile"]}");
                                } else {
                                    $resSolucao = $qrySolucao->fetch();

                                    $solucao = $resSolucao["solucao"];

                                    if (strlen($solucao) > 0) {
                                        $sqlVerSolucao = "SELECT * FROM tbl_os_defeito_reclamado_constatado WHERE os = {$os} AND solucao = {$solucao};";
                                        $resVerSolucao = $pdo->query($sqlVerSolucao);

                                        if ($resVerSolucao->rowCount() == 0) {
                                            $inSolucao = "INSERT INTO tbl_os_defeito_reclamado_constatado (os, solucao,fabrica) VALUES ({$os}, {$solucao}, {$login_fabrica})";
                                            $qrSolucao = $pdo->query($inSolucao);

                                            if (!$qrSolucao) {
                                                if ($debug) {
                                                    echo date('d-m-Y H:i');
                                                    echo "\n";
                                                    echo "\n";
                                                    echo "### ERRO ###\n";
                                                    echo "Ocorreu um erro na gravação da solução #{$fetch["os_mobile"]}...\n\n";
                                                }

                                                throw new \Exception("Ocorreu um erro na gravação da solução #{$fetch["os_mobile"]}");
                                            }
                                        }
                                    }
                                }
                            } catch (\Exception $ewfsl) {
                                if ($debug) {
                                    echo date('d-m-Y H:i');
                                    echo "\n";
                                    echo "\n";
                                    echo "### ERRO ###\n";
                                    echo $ewfsl->getMessage()."...\n\n";
                                }

                                $erro_validacao[] = $ewfsl->getMessage();
                            }
                        }

                        if ($debug) {
                            echo date('d-m-Y H:i');
                            echo "\n";
                            echo "Solução validada...\n";
                        }
                    }
                }
               
                if (count($dados['servicos']) > 0) {
                    if ($debug) {
                        echo date('d-m-Y H:i');
                        echo "\n";
                        echo "Verificando dados para gravar laudo...\n";
                    }

                    $sqlLaudo = "SELECT * FROM tbl_laudo_tecnico_os WHERE os = {$os} AND fabrica = {$login_fabrica};";
                    $resLaudo = $pdo->query($sqlLaudo);

                    if (!$resLaudo) {
                        if ($debug) {
                            echo date('d-m-Y H:i');
                            echo "\n";
                            echo "\n";
                            echo "### ERRO ###\n";
                            echo "Ocorreu um erro verificando laudo(s) gravado(s) #{$fetch["os_mobile"]}...\n\n";
                        }

                        $erro_validacao[] = "Ocorreu um erro verificando laudos gravados #{$fetch["os_mobile"]}";
                    } else if ($resLaudo->rowCount() > 0) {
                        if ($debug) {
                            echo date('d-m-Y H:i');
                            echo "\n";
                            echo "Foram encontrados laudos gravados, serão excluídos para gravar novas informações...\n";
                        }

                        $sqlDelLaudo = "DELETE FROM tbl_laudo_tecnico_os WHERE os = {$os} AND fabrica = {$login_fabrica};";
                        $resDelLaudo = $pdo->query($sqlDelLaudo);

                        if (!$resDelLaudo) {
                            if ($debug) {
                                echo date('d-m-Y H:i');
                                echo "\n";
                                echo "\n";
                                echo "### ERRO ###\n";
                                echo "Ocorreu um erro excluindo laudo(s) gravado(s) #{$fetch["os_mobile"]}...\n\n";
                            }
                            
                            $erro_validacao[] = "Ocorreu um erro excluindo laudo(s) gravado(s) #{$fetch["os_mobile"]}";
                        }
                    }

                    foreach ($dados['servicos'] as $laudo) {
                        try { // $ewfs
                            if (count($laudo['procedimentos']) > 0) {
                                $sqlInsLaudo = "
                                    INSERT INTO tbl_laudo_tecnico_os
                                        (os, titulo, observacao, fabrica)
                                    VALUES
                                        ({$os}, '{$laudo['nome']}', '".json_encode($laudo['procedimentos'])."', {$login_fabrica});
                                ";
                                $resInsLaudo = $pdo->query($sqlInsLaudo);

                                if (!$resInsLaudo) {
                                    if ($debug) {
                                        echo date('d-m-Y H:i');
                                        echo "\n";
                                        echo "\n";
                                        echo "### ERRO ###\n";
                                        echo "Ocorreu um erro na gravação do(s) laudo(s) #{$fetch["os_mobile"]}...\n\n";
                                    }

                                    throw new \Exception("Ocorreu um erro na gravação do(s) laudo(s) #{$fetch["os_mobile"]}");
                                } else {
                                    if ($debug) {
                                        echo date('d-m-Y H:i');
                                        echo "\n";
                                        echo "Laudo(s) gravado(s) com sucesso #{$fetch["os_mobile"]}...\n\n";
                                    }
                                }
                            }
                        } catch (\Exception $ewfs) {
                            if ($debug) {
                                echo date('d-m-Y H:i');
                                echo "\n";
                                echo "\n";
                                echo "### ERRO ###\n";
                                echo $ewfs->getMessage()."...\n\n";
                            }

                            $erro_validacao[] = $ewfs->getMessage();
                        }
                    }
                    
                    if ($debug) {
                        echo date('d-m-Y H:i');
                        echo "\n";
                        echo "Fim verificação e gravação do laudo...\n";
                    }
                }

                if (count($dados["pecas"]) > 0) {
                    if ($debug) {
                        echo date('d-m-Y H:i');
                        echo "\n";
                        echo "Validando Peças...\n";
                    }

                    $nova_peca_critica = false;
                    $nova_peca_troca   = false;

                    $log_pecas_os_mobile = "/mnt/webuploads/imbera/logs/{$fetch['os_mobile']}-pecas.log";
                    file_put_contents($log_pecas_os_mobile, print_r($dados["pecas"]), FILE_APPEND);
                    file_put_contents($log_pecas_os_mobile, "\n\n", FILE_APPEND);

                    foreach ($dados["pecas"] as $peca) {
                        file_put_contents($log_pecas_os_mobile, "\n\n", FILE_APPEND);

                        if ($debug) {
                            echo "Peça {$peca['referencia']}\n";
                            file_put_contents($log_pecas_os_mobile, "Peça {$peca['referencia']}\n", FILE_APPEND);
                        }

                        //Verificando a Peça
                        file_put_contents($log_pecas_os_mobile, "Verificando a Peça\n", FILE_APPEND);
                        $sqlPeca = "
                            SELECT peca, peca_critica 
                            FROM tbl_peca 
                            WHERE fabrica = {$login_fabrica}
                            AND referencia = '{$peca['referencia']}'
                        ";
                        $qryPeca = $pdo->query($sqlPeca);

                        if (!$qryPeca || $qryPeca->rowCount() == 0) {
                            file_put_contents($log_pecas_os_mobile, "Peça {$peca['referencia']} não encontrada #{$fetch['os_mobile']}\n", FILE_APPEND);
                            file_put_contents($log_pecas_os_mobile, print_r(pg_last_error()), FILE_APPEND);
                            throw new \Exception("Peça {$peca['referencia']} não encontrada #{$fetch['os_mobile']}");
                        }

                        $resPeca = $qryPeca->fetch();

                        file_put_contents($log_pecas_os_mobile, print_r($resPeca), FILE_APPEND);

                        if ($resPeca["peca_critica"] == "t") {
                            $nova_peca_critica = true;
                        }

                        //Verificando o Serviço Realizado
                        file_put_contents($log_pecas_os_mobile, "Verificando o Serviço Realizado\n", FILE_APPEND);
                        $sqlServicoRealizado = "
                            SELECT servico_realizado, gera_pedido, troca_de_peca, peca_estoque
                            FROM tbl_servico_realizado
                            WHERE fabrica = {$login_fabrica}
                            AND servico_realizado = {$peca['servicoRealizado']}
                        ";
                        $qryServicoRealizado = $pdo->query($sqlServicoRealizado);

                        if (!$qryServicoRealizado || $qryServicoRealizado->rowCount() == 0) {
                            file_put_contents($log_pecas_os_mobile, "Serviço Realizado da Peça {$peca['referencia']} não encontrado #{$fetch['os_mobile']}\n", FILE_APPEND);
                            file_put_contents($log_pecas_os_mobile, print_r(pg_last_error()), FILE_APPEND);
                            throw new \Exception("Serviço Realizado da Peça {$peca['referencia']} não encontrado #{$fetch['os_mobile']}");
                        }

                        $resServicoRealizado = $qryServicoRealizado->fetch();

                        file_put_contents($log_pecas_os_mobile, print_r($resServicoRealizado), FILE_APPEND);

                        if ($resServicoRealizado["troca_de_peca"] == "t") {
                            $nova_peca_troca = true;
                        }

                        //Verificando Qtde da Peça
                        file_put_contents($log_pecas_os_mobile, "Verificando Qtde da Peça\n", FILE_APPEND);
                        if (empty($peca['qtde'])) {
                            file_put_contents($log_pecas_os_mobile, "Qtde da Peça {$peca['referencia']} não informada #{$fetch['os_mobile']}\n", FILE_APPEND);
                            throw new \Exception("Qtde da Peça {$peca['referencia']} não informada #{$fetch['os_mobile']}");
			             }

                        //Verifica se a Peça já está lançada na OS
                        file_put_contents($log_pecas_os_mobile, "Verifica se a Peça já está lançada na OS\n", FILE_APPEND);
                        $sqlOsItem = "
                            SELECT 
                                tbl_os_item.os_item, 
                                tbl_os_item.servico_realizado, 
                                tbl_os_item.qtde,
                                tbl_servico_realizado.troca_de_peca,
                                tbl_servico_realizado.peca_estoque,
                                tbl_servico_realizado.gera_pedido,
                				tbl_os_item.pedido_item,
                				tbl_os_item.originou_troca
                            FROM tbl_os_item
                            INNER JOIN tbl_servico_realizado ON tbl_servico_realizado.servico_realizado = tbl_os_item.servico_realizado AND tbl_servico_realizado.fabrica = {$login_fabrica}
                            WHERE tbl_os_item.os_produto = {$os_produto}
                            AND tbl_os_item.peca = {$resPeca['peca']}
                        ";
                        $qryOsItem = $pdo->query($sqlOsItem);

			            if (!$qryOsItem) {
                            file_put_contents($log_pecas_os_mobile, "Erro ao verificar se a Peça {$peca['referencia']} já está lançada na OS #{$fetch['os_mobile']}\n", FILE_APPEND);
                            file_put_contents($log_pecas_os_mobile, print_r(pg_last_error()), FILE_APPEND);
                            throw new \Exception("Erro ao verificar se a Peça {$peca['referencia']} já está lançada na OS #{$fetch['os_mobile']}");
                        }

                        if ($qryOsItem->rowCount() > 0) {
                            $resOsItem = $qryOsItem->fetch();
                        } else {
                            $resOsItem = null;
                        }

                        file_put_contents($log_pecas_os_mobile, print_r($resOsItem), FILE_APPEND);

                        //Se for uma nova Peça lança na OS
                        if (is_null($resOsItem)) {
                            file_put_contents($log_pecas_os_mobile, "Se for uma nova Peça lança na OS\n", FILE_APPEND);
                            //Se o Serviço for Ajuste grava a Peça na OS
                            if ($resServicoRealizado["peca_estoque"] != "t" && $resServicoRealizado["gera_pedido"] != "t") {
                                file_put_contents($log_pecas_os_mobile, "Se o Serviço for Ajuste grava a Peça na OS\n", FILE_APPEND);
                                $insPeca = "
                                    INSERT INTO tbl_os_item 
                                    (os_produto, peca, qtde, servico_realizado, admin)
                                    VALUES 
                                    ({$os_produto}, {$resPeca['peca']}, {$peca['qtde']}, {$resServicoRealizado['servico_realizado']}, {$login_rotina_automatica})
                                    RETURNING os_item
                                ";
                                $qryInsPeca = $pdo->query($insPeca);

                                if (!$qryInsPeca) {
                                    file_put_contents($log_pecas_os_mobile, "Erro ao gravar a Peça {$peca['referencia']} na OS\n", FILE_APPEND);
                                    file_put_contents($log_pecas_os_mobile, print_r(pg_last_error()), FILE_APPEND);
                                    throw new \Exception("Erro ao gravar a Peça {$peca['referencia']} na OS");
                                }

                                file_put_contents($log_pecas_os_mobile, "inseriu peça\n", FILE_APPEND);

                                $resOsItem = $qryInsPeca->fetch();

                                file_put_contents($log_pecas_os_mobile, "peça finalizada\n", FILE_APPEND);

                                continue;
                            //Se o Serviço for Gera Pedido faz validações adicionais
			                } else if ($resServicoRealizado["gera_pedido"] == "t") {
                                file_put_contents($log_pecas_os_mobile, "Se o Serviço for Gera Pedido faz validações adicionais\n", FILE_APPEND);
				                $servico_trocado = "FALSE";

                                //Pega o saldo da peça no estoque do posto
                                file_put_contents($log_pecas_os_mobile, "Pega o saldo da peça no estoque do posto\n", FILE_APPEND);
                                $estoquePosto = $osClass->verificaEstoquePosto($posto, $resPeca['peca'], $peca['qtde']);
                                file_put_contents($log_pecas_os_mobile, print_r($estoquePosto), FILE_APPEND);

                                //Se o posto possuir saldo da peça no estoque irá alterar o Serviço para Estoque
                                file_put_contents($log_pecas_os_mobile, "Se o posto possuir saldo da peça no estoque irá alterar o Serviço para Estoque\n", FILE_APPEND);
                                if ($estoquePosto) {
                                    //Altera o serviço para estoque
                                    file_put_contents($log_pecas_os_mobile, "Altera o serviço para estoque\n", FILE_APPEND);
                                    $sqlServicoRealizado = "
                                        SELECT servico_realizado, gera_pedido, troca_de_peca, peca_estoque
                                        FROM tbl_servico_realizado
                                        WHERE fabrica = {$login_fabrica}
                                        AND troca_de_peca IS TRUE
                                        AND peca_estoque IS TRUE
                                    ";
                                    $qryServicoRealizado = $pdo->query($sqlServicoRealizado);

                                    if (!$qryServicoRealizado || $qryServicoRealizado->rowCount() == 0) {
                                        file_put_contents($log_pecas_os_mobile, "Serviço Realizado para Gerar Pedido não encontrado #{$fetch['os_mobile']}\n", FILE_APPEND);
                                        file_put_contents($log_pecas_os_mobile, print_r(pg_last_error()), FILE_APPEND);
                                        throw new \Exception("Serviço Realizado para Gerar Pedido não encontrado #{$fetch['os_mobile']}");
                                    }

				                    $resServicoRealizado = $qryServicoRealizado->fetch();

                                    file_put_contents($log_pecas_os_mobile, print_r($resServicoRealizado), FILE_APPEND);

				                    $servico_trocado = "TRUE";
                                }

                                //Lança a Peça na OS
                                file_put_contents($log_pecas_os_mobile, "Lança a Peça na OS\n", FILE_APPEND);
                                $insPeca = "
                                    INSERT INTO tbl_os_item 
                                    (os_produto, peca, qtde, servico_realizado, originou_troca, admin)
                                    VALUES 
                                    ({$os_produto}, {$resPeca['peca']}, {$peca['qtde']}, {$resServicoRealizado['servico_realizado']}, {$servico_trocado}, {$login_rotina_automatica})
                                    RETURNING os_item
                                ";
                                $qryInsPeca = $pdo->query($insPeca);

                                if (!$qryInsPeca) {
                                    file_put_contents($log_pecas_os_mobile, "Erro ao gravar a Peça {$peca['referencia']} na OS\n", FILE_APPEND);
                                    file_put_contents($log_pecas_os_mobile, print_r(pg_last_error()), FILE_APPEND);
                                    throw new \Exception("Erro ao gravar a Peça {$peca['referencia']} na OS");
                                }

                                $resOsItem = $qryInsPeca->fetch();

                                file_put_contents($log_pecas_os_mobile, "gravou peça\n", FILE_APPEND);

                                //Se tem estoque lança movimentação de saída da peça no estoque do posto
                                file_put_contents($log_pecas_os_mobile, "Se tem estoque lança movimentação de saída da peça no estoque do posto\n", FILE_APPEND);
                                if ($estoquePosto) {
                                    file_put_contents($log_pecas_os_mobile, "movimentação de estoque\n", FILE_APPEND);
                                    file_put_contents($log_pecas_os_mobile, print_r(array(
                                        $posto,
                                        $resPeca['peca'],
                                        $peca['qtde'],
                                        $os,
                                        $resOsItem['os_item'],
                                        '',
                                        date('Y-m-d'),
                                        'saida'
                                    )), FILE_APPEND);

                                    $lancamentoMovimentoEstoque = $osClass->lancaMovimentoEstoque(
                                        $posto,
                                        $resPeca['peca'],
                                        $peca['qtde'],
                                        $os,
                                        $resOsItem['os_item'],
                                        '',
                                        date('Y-m-d'),
                                        'saida'
                                    );

                                    file_put_contents($log_pecas_os_mobile, print_r($lancamentoMovimentoEstoque), FILE_APPEND);

                                    if (!$lancamentoMovimentoEstoque) {
                                        file_put_contents($log_pecas_os_mobile, "Erro ao lançar movimentação de saída no estoque, Peça {$peca['referencia']} #{$fetch['os_mobile']}\n", FILE_APPEND);
                                        file_put_contents($log_pecas_os_mobile, print_r(pg_last_error()), FILE_APPEND);
                                        throw new \Exception("Erro ao lançar movimentação de saída no estoque, Peça {$peca['referencia']} #{$fetch['os_mobile']}");
                                    }

                                    //Armazena no array a Peça que deve ter o serviço alterado na Persys
                                    if (!isset($peca["exportado_persys"])) {
                                        $persys_AlteraServicoPeca[] = array(
                                            "pecaId"               => $resPeca['peca'],
                                            "pecaReferencia"       => $peca['referencia'],
                                            "servicoRealizado"     => $resServicoRealizado["servico_realizado"],
                                            "servicoRealizadoTipo" => "estoque"
                                        );
                                    }
                                }

                                file_put_contents($log_pecas_os_mobile, "peça finalizada\n", FILE_APPEND);

                                continue;
                            //Se for estoque faz validações adicionais
                            } else {
                                file_put_contents($log_pecas_os_mobile, "Se for estoque faz validações adicionais\n", FILE_APPEND);
                                //Pega o saldo da peça no estoque do posto
                                file_put_contents($log_pecas_os_mobile, "Pega o saldo da peça no estoque do posto\n", FILE_APPEND);
                                $estoquePosto = $osClass->verificaEstoquePosto($posto, $resPeca['peca'], $peca['qtde']);
                                file_put_contents($log_pecas_os_mobile, print_r($estoquePosto), FILE_APPEND);

                                //Verifica se o posto tem saldo suficiente da peça no estoque
                                file_put_contents($log_pecas_os_mobile, "Verifica se o posto tem saldo suficiente da peça no estoque\n", FILE_APPEND);
                                if ($estoquePosto) {
                                    //Lança a Peça na OS
                                    file_put_contents($log_pecas_os_mobile, "Lança a Peça na OS\n", FILE_APPEND);
                                    $insPeca = "
                                        INSERT INTO tbl_os_item 
                                        (os_produto, peca, qtde, servico_realizado, admin)
                                        VALUES 
                                        ({$os_produto}, {$resPeca['peca']}, {$peca['qtde']}, {$resServicoRealizado['servico_realizado']}, $login_rotina_automatica)
                                        RETURNING os_item
                                    ";
                                    $qryInsPeca = $pdo->query($insPeca);

                                    if (!$qryInsPeca) {
                                        file_put_contents($log_pecas_os_mobile, "Erro ao gravar a Peça {$peca['referencia']} na OS\n", FILE_APPEND);
                                        file_put_contents($log_pecas_os_mobile, print_r(pg_last_error()), FILE_APPEND);
                                        throw new \Exception("Erro ao gravar a Peça {$peca['referencia']} na OS");
                                    }

                                    $resOsItem = $qryInsPeca->fetch();

                                    file_put_contents($log_pecas_os_mobile, "gravou peça\n", FILE_APPEND);

                                    //Lança a movimentação de saída do estoque
                                    file_put_contents($log_pecas_os_mobile, "Lança a movimentação de saída do estoque\n", FILE_APPEND);
                                    file_put_contents($log_pecas_os_mobile, print_r(array(
                                        $posto,
                                        $resPeca['peca'],
                                        $peca['qtde'],
                                        $os,
                                        $resOsItem['os_item'],
                                        '',
                                        date('Y-m-d'),
                                        'saida'
                                    )), FILE_APPEND);
                                    $lancamentoMovimentoEstoque = $osClass->lancaMovimentoEstoque(
                                        $posto,
                                        $resPeca['peca'],
                                        $peca['qtde'],
                                        $os,
                                        $resOsItem['os_item'],
                                        '',
                                        date('Y-m-d'),
                                        'saida'
                                    );

                                    file_put_contents($log_pecas_os_mobile, print_r($lancamentoMovimentoEstoque), FILE_APPEND);

                                    if (!$lancamentoMovimentoEstoque) {
                                        file_put_contents($log_pecas_os_mobile, "Erro ao lançar movimentação de saída no estoque, Peça {$peca['referencia']} #{$fetch['os_mobile']}\n", FILE_APPEND);
                                        file_put_contents($log_pecas_os_mobile, print_r(pg_last_error()), FILE_APPEND);
                                        throw new \Exception("Erro ao lançar movimentação de saída no estoque, Peça {$peca['referencia']} #{$fetch['os_mobile']}");
                                    }

                                    continue;
                                } else {
                                    file_put_contents($log_pecas_os_mobile, "alterou para gera pedido\n", FILE_APPEND);
                                    //Altera o serviço para gerar pedido
                                    $sqlServicoRealizado = "
                                        SELECT servico_realizado, gera_pedido, troca_de_peca, peca_estoque
                                        FROM tbl_servico_realizado
                                        WHERE fabrica = {$login_fabrica}
                                        AND troca_de_peca IS TRUE
                                        AND gera_pedido IS TRUE
                                    ";
                                    $qryServicoRealizado = $pdo->query($sqlServicoRealizado);

                                    if (!$qryServicoRealizado || $qryServicoRealizado->rowCount() == 0) {
                                        file_put_contents($log_pecas_os_mobile, "Serviço Realizado para Gerar Pedido não encontrado #{$fetch['os_mobile']}\n", FILE_APPEND);
                                        file_put_contents($log_pecas_os_mobile, print_r(pg_last_error()), FILE_APPEND);
                                        throw new \Exception("Serviço Realizado para Gerar Pedido não encontrado #{$fetch['os_mobile']}");
                                    }

                                    $resServicoRealizado = $qryServicoRealizado->fetch();

                                    file_put_contents($log_pecas_os_mobile, print_r($resServicoRealizado), FILE_APPEND);

                                    //Lança a Peça na OS
                                    $insPeca = "
                                        INSERT INTO tbl_os_item 
                                        (os_produto, peca, qtde, servico_realizado, originou_troca, admin)
                                        VALUES 
                                        ({$os_produto}, {$resPeca['peca']}, {$peca['qtde']}, {$resServicoRealizado['servico_realizado']}, true, $login_rotina_automatica)
                                        RETURNING os_item
                                    ";
                                    $qryInsPeca = $pdo->query($insPeca);

                                    if (!$qryInsPeca) {
                                        file_put_contents($log_pecas_os_mobile, "Erro ao gravar a Peça {$peca['referencia']} na OS\n", FILE_APPEND);
                                        file_put_contents($log_pecas_os_mobile, print_r(pg_last_error()), FILE_APPEND);
                                        throw new \Exception("Erro ao gravar a Peça {$peca['referencia']} na OS");
                                    }

                                    file_put_contents($log_pecas_os_mobile, "gravou peça\n", FILE_APPEND);

                                    $resOsItem = $qryInsPeca->fetch();

                                    //Armazena no array a Peça que deve ter o serviço alterado na Persys
                                    if (!isset($peca["exportado_persys"])) {
                                        $persys_AlteraServicoPeca[] = array(
                                            "pecaId"               => $resPeca['peca'],
                                            "pecaReferencia"       => $peca['referencia'],
                                            "servicoRealizado"     => $resServicoRealizado["servico_realizado"],
                                            "servicoRealizadoTipo" => "gera_pedido"
                                        );
                                    }

                                    file_put_contents($log_pecas_os_mobile, "peça finalizada\n", FILE_APPEND);

                                    continue;
                                }
                            }
                        //Peça já lançada na OS
                        } else {
                            file_put_contents($log_pecas_os_mobile, "Peça já lançada na OS\n", FILE_APPEND);
                            //Se estiver alterando o serviço da Peça
                            if ($resOsItem["servico_realizado"] != $peca["servicoRealizado"] && $resOsItem["originou_troca"] != "t") {
                                file_put_contents($log_pecas_os_mobile, "Se estiver alterando o serviço da Peça\n", FILE_APPEND);
                                //Se o serviço antigo for Troca de Peça e possuir pedido não deve permitir alteração
                                file_put_contents($log_pecas_os_mobile, "Se o serviço antigo for Troca de Peça e possuir pedido não deve permitir alteração\n", FILE_APPEND);
                                if ($resOsItem["troca_de_peca"] == "t" && !empty($resOsItem["pedido_item"])) {
                                    $tipo_pedido = ($resOsItem["gera_pedido"] == "t") ? "Pedido NTP" : "Pedido de Reposição de Estoque";
                                    file_put_contents($log_pecas_os_mobile, "Não foi possível alterar o Serivço da Peça {$peca['referencia']}, já foi gerado um {$tipo_pedido}\n", FILE_APPEND);
                                    throw new \Exception("Não foi possível alterar o Serivço da Peça {$peca['referencia']}, já foi gerado um {$tipo_pedido}");
                                }

                                //Se o serviço antigo for Estoque irá fazer a devolução da peça para o estoque
                                file_put_contents($log_pecas_os_mobile, "Se o serviço antigo for Estoque irá fazer a devolução da peça para o estoque\n", FILE_APPEND);
                                if ($resOsItem["troca_de_peca"] == "t" && $resOsItem["peca_estoque"] == "t") {
                                    //Exclui a movimentação antiga de saída
                                    file_put_contents($log_pecas_os_mobile, "Exclui a movimentação antiga de saída\n", FILE_APPEND);
                                    file_put_contents($log_pecas_os_mobile, print_r(array(
                                        $posto, 
                                        $resPeca["peca"], 
                                        $os, 
                                        $resOsItem["os_item"]
                                    )), FILE_APPEND);
                                    $excluiMovimentoEstoque = $osClass->excluiMovimentacaoEstoque(
                                        $posto, 
                                        $resPeca["peca"], 
                                        $os, 
                                        $resOsItem["os_item"]
                                    );

                                    file_put_contents($log_pecas_os_mobile, print_r($excluiMovimentoEstoque), FILE_APPEND);

                                    if (!$excluiMovimentoEstoque) {
                                        file_put_contents($log_pecas_os_mobile, "Erro ao excluir movimentação de saída no estoque, Peça {$peca['referencia']} #{$fetch['os_mobile']}\n", FILE_APPEND);
                                        file_put_contents($log_pecas_os_mobile, print_r(pg_last_error()), FILE_APPEND);
                                        throw new \Exception("Erro ao excluir movimentação de saída no estoque, Peça {$peca['referencia']} #{$fetch['os_mobile']}");
                                    }
                                }

                                //Se o novo serviço for Estoque
                                if ($resServicoRealizado["troca_de_peca"] == "t" && $resServicoRealizado["peca_estoque"] == "t") {
                                    file_put_contents($log_pecas_os_mobile, "Se o novo serviço for Estoque\n", FILE_APPEND);
                                    file_put_contents($log_pecas_os_mobile, "Verifica se há saldo no estoque para a Peça\n", FILE_APPEND);
                                    //Verifica se há saldo no estoque para a Peça
                                    $estoquePosto = $osClass->verificaEstoquePosto($posto, $resPeca["peca"], $peca["qtde"]);

                                    file_put_contents($log_pecas_os_mobile, print_r($estoquePosto), FILE_APPEND);

                                    //Se tiver saldo no estoque
                                    if ($estoquePosto) {
                                        file_put_contents($log_pecas_os_mobile, "Se tiver saldo no estoque\n", FILE_APPEND);
                                        //Atualiza a Qtde e Serviço da Peça
                                        file_put_contents($log_pecas_os_mobile, "Atualiza a Qtde e Serviço da Peça\n", FILE_APPEND);
                                        $updPeca = "
                                            UPDATE tbl_os_item SET
                                                qtde = {$peca['qtde']},
                                                servico_realizado = {$resServicoRealizado['servico_realizado']}
                                            WHERE os_item = {$resOsItem['os_item']}
                                        ";
                                        $qryUpdPeca = $pdo->query($updPeca);

                                        if (!$qryUpdPeca) {
                                            file_put_contents($log_pecas_os_mobile, "Erro ao atualizar a Qtde e Serviço da Peça {$peca['referencia']} na OS\n", FILE_APPEND);
                                            file_put_contents($log_pecas_os_mobile, print_r(pg_last_error()), FILE_APPEND);
                                            throw new \Exception("Erro ao atualizar a Qtde e Serviço da Peça {$peca['referencia']} na OS");
                                        }

                                        file_put_contents($log_pecas_os_mobile, "Lança a movimentação de saída do estoque\n", FILE_APPEND);
                                        file_put_contents($log_pecas_os_mobile, print_r(array(
                                            $posto,
                                            $resPeca['peca'],
                                            $peca['qtde'],
                                            $os,
                                            $resOsItem['os_item'],
                                            '',
                                            date('Y-m-d'),
                                            'saida'
                                        )), FILE_APPEND);
                                        //Lança a movimentação de saída do estoque
                                        $lancamentoMovimentoEstoque = $osClass->lancaMovimentoEstoque(
                                            $posto,
                                            $resPeca['peca'],
                                            $peca['qtde'],
                                            $os,
                                            $resOsItem['os_item'],
                                            '',
                                            date('Y-m-d'),
                                            'saida'
                                        );

                                        file_put_contents($log_pecas_os_mobile, print_r($lancamentoMovimentoEstoque), FILE_APPEND);

                                        if (!$lancamentoMovimentoEstoque) {
                                            file_put_contents($log_pecas_os_mobile, "Erro ao lançar movimentação de saída no estoque, Peça {$peca['referencia']} #{$fetch['os_mobile']}\n", FILE_APPEND);
                                            file_put_contents($log_pecas_os_mobile, print_r(pg_last_error()), FILE_APPEND);
                                            throw new \Exception("Erro ao lançar movimentação de saída no estoque, Peça {$peca['referencia']} #{$fetch['os_mobile']}");
                                        }

                                        file_put_contents($log_pecas_os_mobile, "atualizou peça\n", FILE_APPEND);

                                        continue;
                                    } else {
                                        //Altera o serviço para gera pedido
                                        file_put_contents($log_pecas_os_mobile, "Altera o serviço para gera pedido\n", FILE_APPEND);
                                        $sqlServicoRealizado = "
                                            SELECT servico_realizado, gera_pedido, troca_de_peca, peca_estoque
                                            FROM tbl_servico_realizado
                                            WHERE fabrica = {$login_fabrica}
                                            AND troca_de_peca IS TRUE
                                            AND gera_pedido IS TRUE
                                        ";
                                        $qryServicoRealizado = $pdo->query($sqlServicoRealizado);

                                         if (!$qryServicoRealizado || $qryServicoRealizado->rowCount() == 0) {
                                            file_put_contents($log_pecas_os_mobile, "Serviço Realizado para Gerar Pedido não encontrado #{$fetch['os_mobile']}\n", FILE_APPEND);
                                            file_put_contents($log_pecas_os_mobile, print_r(pg_last_error()), FILE_APPEND);
                                            throw new \Exception("Serviço Realizado para Gerar Pedido não encontrado #{$fetch['os_mobile']}");
                                        }

                                        $resServicoRealizado = $qryServicoRealizado->fetch();

                                        file_put_contents($log_pecas_os_mobile, print_r($resServicoRealizado), FILE_APPEND);

                                        //Atualiza a Qtde e Serviço da Peça
                                        file_put_contents($log_pecas_os_mobile, "Atualiza a Qtde e Serviço da Peça\n", FILE_APPEND);
                                        $updPeca = "
                                            UPDATE tbl_os_item SET
                                                qtde = {$peca['qtde']},
                    						servico_realizado = {$resServicoRealizado['servico_realizado']},
                    						originou_troca = true
                                            WHERE os_item = {$resOsItem['os_item']}
                                        ";
                                        $qryUpdPeca = $pdo->query($updPeca);

                                        if (!$qryUpdPeca) {
                                            file_put_contents($log_pecas_os_mobile, "Erro ao atualizar a Qtde e Serviço da Peça {$peca['referencia']} na OS\n", FILE_APPEND);
                                            file_put_contents($log_pecas_os_mobile, print_r(pg_last_error()), FILE_APPEND);
                                            throw new \Exception("Erro ao atualizar a Qtde e Serviço da Peça {$peca['referencia']} na OS");
                                        }

                                        //Armazena no array a Peça que deve ter o serviço alterado na Persys
                                        if (!isset($peca["exportado_persys"])) {
                                            $persys_AlteraServicoPeca[] = array(
                                                "pecaId"               => $resPeca['peca'],
                                                "pecaReferencia"       => $peca['referencia'],
                                                "servicoRealizado"     => $resServicoRealizado["servico_realizado"],
                                                "servicoRealizadoTipo" => "gera_pedido"
                                            );
                                        }

                                        file_put_contents($log_pecas_os_mobile, "atualizou peça\n", FILE_APPEND);

                                        continue;
                                    }
                                //Se o novo serviço for Gera Pedido
                                } else if ($resServicoRealizado["troca_de_peca"] == "t" && $resServicoRealizado["gera_pedido"] == "t") {
                                    file_put_contents($log_pecas_os_mobile, "Se o novo serviço for Gera Pedido\n", FILE_APPEND);
                                    //Verifica se há saldo no estoque para a Peça
                                    file_put_contents($log_pecas_os_mobile, "Verifica se há saldo no estoque para a Peça\n", FILE_APPEND);
                                    $estoquePosto = $osClass->verificaEstoquePosto($posto, $resPeca["peca"], $peca["qtde"]);

                                    file_put_contents($log_pecas_os_mobile, print_r($estoquePosto), FILE_APPEND);

                                    //Se tiver saldo no estoque
                                    file_put_contents($log_pecas_os_mobile, "Se tiver saldo no estoque\n", FILE_APPEND);
                                    if ($estoquePosto) {
                                        //Altera o serviço para estoque
                                        file_put_contents($log_pecas_os_mobile, "Altera o serviço para estoque\n", FILE_APPEND);
                                        $sqlServicoRealizado = "
                                            SELECT servico_realizado, gera_pedido, troca_de_peca, peca_estoque
                                            FROM tbl_servico_realizado
                                            WHERE fabrica = {$login_fabrica}
                                            AND troca_de_peca IS TRUE
                                            AND peca_estoque IS TRUE
                                        ";
                                        $qryServicoRealizado = $pdo->query($sqlServicoRealizado);

                                        if (!$qryServicoRealizado || $qryServicoRealizado->rowCount() == 0) {
                                            file_put_contents($log_pecas_os_mobile, "Serviço Realizado para Estoque não encontrado #{$fetch['os_mobile']}\n", FILE_APPEND);
                                            file_put_contents($log_pecas_os_mobile, print_r(pg_last_error()), FILE_APPEND);
                                            throw new \Exception("Serviço Realizado para Estoque não encontrado #{$fetch['os_mobile']}");
                                        }

                                        $resServicoRealizado = $qryServicoRealizado->fetch();

                                        file_put_contents($log_pecas_os_mobile, print_r($resServicoRealizado), FILE_APPEND);

                                        //Atualiza a Qtde e Serviço da Peça
                                        file_put_contents($log_pecas_os_mobile, "Atualiza a Qtde e Serviço da Peça\n", FILE_APPEND);
                                        $updPeca = "
                                            UPDATE tbl_os_item SET
                                                qtde = {$peca['qtde']},
                        						servico_realizado = {$resServicoRealizado['servico_realizado']},
                        						originou_troca = true
                                            WHERE os_item = {$resOsItem['os_item']}
                                        ";
                                        $qryUpdPeca = $pdo->query($updPeca);

                                        if (!$qryUpdPeca) {
                                            file_put_contents($log_pecas_os_mobile, "Erro ao atualizar a Qtde e Serviço da Peça {$peca['referencia']} na OS\n", FILE_APPEND);
                                            file_put_contents($log_pecas_os_mobile, print_r(pg_last_error()), FILE_APPEND);
                                            throw new \Exception("Erro ao atualizar a Qtde e Serviço da Peça {$peca['referencia']} na OS");
                                        }

                                        //Lança a movimentação de saída do estoque
                                        file_put_contents($log_pecas_os_mobile, "Lança a movimentação de saída do estoque\n", FILE_APPEND);
                                        file_put_contents($log_pecas_os_mobile, print_r(array(
                                            $posto,
                                            $resPeca['peca'],
                                            $peca['qtde'],
                                            $os,
                                            $resOsItem['os_item'],
                                            '',
                                            date('Y-m-d'),
                                            'saida'
                                        )), FILE_APPEND);
                                        $lancamentoMovimentoEstoque = $osClass->lancaMovimentoEstoque(
                                            $posto,
                                            $resPeca['peca'],
                                            $peca['qtde'],
                                            $os,
                                            $resOsItem['os_item'],
                                            '',
                                            date('Y-m-d'),
                                            'saida'
                                        );

                                        file_put_contents($log_pecas_os_mobile, print_r($lancamentoMovimentoEstoque), FILE_APPEND);

                                        if (!$lancamentoMovimentoEstoque) {
                                            file_put_contents($log_pecas_os_mobile, "Erro ao lançar movimentação de saída no estoque, Peça {$peca['referencia']} #{$fetch['os_mobile']}\n", FILE_APPEND);
                                            file_put_contents($log_pecas_os_mobile, print_r(pg_last_error()), FILE_APPEND);
                                            throw new \Exception("Erro ao lançar movimentação de saída no estoque, Peça {$peca['referencia']} #{$fetch['os_mobile']}");
                                        }

                                        //Armazena no array a Peça que deve ter o serviço alterado na Persys
                                        if (!isset($peca["exportado_persys"])) {
                                            $persys_AlteraServicoPeca[] = array(
                                                "pecaId"               => $resPeca['peca'],
                                                "pecaReferencia"       => $peca['referencia'],
                                                "servicoRealizado"     => $resServicoRealizado["servico_realizado"],
                                                "servicoRealizadoTipo" => "estoque"
                                            );
                                        }

                                        file_put_contents($log_pecas_os_mobile, "atualizou peça\n", FILE_APPEND);

                                        continue;
                                    //Se não possuir saldo no estoque
                                    } else {
                                        file_put_contents($log_pecas_os_mobile, "Se não possuir saldo no estoque\n", FILE_APPEND);

                                        //Atualiza a Qtde da Peça
                                        file_put_contents($log_pecas_os_mobile, "Atualiza a Qtde da Peça\n", FILE_APPEND);
                                        $updPeca = "
                                            UPDATE tbl_os_item SET
                                                qtde = {$peca['qtde']},
                                                servico_realizado = {$resServicoRealizado['servico_realizado']}
                                            WHERE os_item = {$resOsItem['os_item']}
                                        ";
                                        $qryUpdPeca = $pdo->query($updPeca);

                                        if (!$qryUpdPeca) {
                                            file_put_contents($log_pecas_os_mobile, "Erro ao atualizar a Qtde da Peça {$peca['referencia']} na OS\n", FILE_APPEND);
                                            file_put_contents($log_pecas_os_mobile, print_r(pg_last_error()), FILE_APPEND);
                                            throw new \Exception("Erro ao atualizar a Qtde da Peça {$peca['referencia']} na OS");
                                        }

                                        file_put_contents($log_pecas_os_mobile, "atualizou peça\n", FILE_APPEND);

                                        continue;
                                    }
                                //Se o novo serviço for Ajuste
                                } else {
                                    file_put_contents($log_pecas_os_mobile, "Se o novo serviço for Ajuste\n", FILE_APPEND);

                                    $updPeca = "
                                        UPDATE tbl_os_item SET
                                            qtde = {$peca['qtde']},
                                            servico_realizado = {$resServicoRealizado['servico_realizado']}
                                        WHERE os_item = {$resOsItem['os_item']}
                                    ";
                                    $qryUpdPeca = $pdo->query($updPeca);

                                    if (!$qryUpdPeca) {
                                        file_put_contents($log_pecas_os_mobile, "Erro ao atualizar a Qtde e Serviço da Peça {$peca['referencia']} na OS\n", FILE_APPEND);
                                        file_put_contents($log_pecas_os_mobile, print_r(pg_last_error()), FILE_APPEND);
                                        throw new \Exception("Erro ao atualizar a Qtde e Serviço da Peça {$peca['referencia']} na OS");
                                    }

                                    file_put_contents($log_pecas_os_mobile, "atualizou peça\n", FILE_APPEND);

                                    continue;
                                }
                            //Se estiver alterando a Qtde da Peça
                            } else if ($resOsItem["qtde"] != $peca["qtde"]) {
                                file_put_contents($log_pecas_os_mobile, "Se estiver alterando a Qtde da Peça\n", FILE_APPEND);

                                //Se o serviço antigo for Troca de Peça e possuir pedido não deve permitir alteração
                                file_put_contents($log_pecas_os_mobile, "Se o serviço antigo for Troca de Peça e possuir pedido não deve permitir alteração\n", FILE_APPEND);

                                if ($resOsItem["troca_de_peca"] == "t" && !empty($resOsItem["pedido_item"])) {
                                    $tipo_pedido = ($resOsItem["gera_pedido"] == "t") ? "Pedido NTP" : "Pedido de Reposição de Estoque";
                                    file_put_contents($log_pecas_os_mobile, "Não foi possível alterar a Quantidade da Peça {$peca['referencia']}, já foi gerado um {$tipo_pedido}\n", FILE_APPEND);
                                    throw new \Exception("Não foi possível alterar a Quantidade da Peça {$peca['referencia']}, já foi gerado um {$tipo_pedido}");
                                }

                                //Verifica se o Serviço é Estoque
                                if ($resServicoRealizado["troca_de_peca"] == "t" && $resServicoRealizado["peca_estoque"] == "t") {
                                    file_put_contents($log_pecas_os_mobile, "Verifica se o Serviço é Estoque\n", FILE_APPEND);

                                    //Se a Nova Qtde for superior a Lançada pega a diferença para ver se há saldo no estoque para essa diferença
                                    if ($resOsItem["qtde"] < $peca["qtde"]) {
                                        file_put_contents($log_pecas_os_mobile, "Se a Nova Qtde for superior a Lançada pega a diferença para ver se há saldo no estoque para essa diferença\n", FILE_APPEND);
                                        $qtdeDiferenca = $peca["qtde"] - $resOsItem["qtde"];

                                        //Verifica se há saldo no estoque para a Peça
                                        file_put_contents($log_pecas_os_mobile, "Verifica se há saldo no estoque para a Peça\n", FILE_APPEND);
                                        $estoquePosto = $osClass->verificaEstoquePosto($posto, $resPeca["peca"], $qtdeDiferenca);

                                        file_put_contents($log_pecas_os_mobile, print_r($estoquePosto), FILE_APPEND);

                                        //Se tiver saldo no estoque
                                        if ($estoquePosto) {
                                            file_put_contents($log_pecas_os_mobile, "Se tiver saldo no estoque\n", FILE_APPEND);

                                            //Atualiza a Qtde da Peça
                                            file_put_contents($log_pecas_os_mobile, "Atualiza a Qtde da Peça\n", FILE_APPEND);
                                            $updPeca = "
                                                UPDATE tbl_os_item SET
                                                    qtde = {$peca['qtde']}
                                                WHERE os_item = {$resOsItem['os_item']}
                                            ";
                                            $qryUpdPeca = $pdo->query($updPeca);

                                            if (!$qryUpdPeca) {
                                                file_put_contents($log_pecas_os_mobile, "Erro ao atualizar a Qtde da Peça {$peca['referencia']} na OS\n", FILE_APPEND);
                                                file_put_contents($log_pecas_os_mobile, print_r(pg_last_error()), FILE_APPEND);
                                                throw new \Exception("Erro ao atualizar a Qtde da Peça {$peca['referencia']} na OS");
                                            }

                                            //Exclui a movimentação antiga de saída
                                            file_put_contents($log_pecas_os_mobile, "Exclui a movimentação antiga de saída\n", FILE_APPEND);
                                            file_put_contents($log_pecas_os_mobile, print_r(array(
                                                $posto, 
                                                $resPeca["peca"], 
                                                $os, 
                                                $resOsItem["os_item"]
                                            )), FILE_APPEND);
                                            $excluiMovimentoEstoque = $osClass->excluiMovimentacaoEstoque(
                                                $posto, 
                                                $resPeca["peca"], 
                                                $os, 
                                                $resOsItem["os_item"]
                                            );

                                            file_put_contents($log_pecas_os_mobile, print_r($excluiMovimentoEstoque), FILE_APPEND);

                                            if (!$excluiMovimentoEstoque) {
                                                file_put_contents($log_pecas_os_mobile, "Erro ao excluir movimentação de saída no estoque, Peça {$peca['referencia']} #{$fetch['os_mobile']}\n", FILE_APPEND);
                                                file_put_contents($log_pecas_os_mobile, print_r(pg_last_error()), FILE_APPEND);
                                                throw new \Exception("Erro ao excluir movimentação de saída no estoque, Peça {$peca['referencia']} #{$fetch['os_mobile']}");
                                            }

                                            //Lança a movimentação de saída do estoque
                                            file_put_contents($log_pecas_os_mobile, "Lança a movimentação de saída do estoque\n", FILE_APPEND);
                                            file_put_contents($log_pecas_os_mobile, print_r(array(
                                                $posto,
                                                $resPeca['peca'],
                                                $peca['qtde'],
                                                $os,
                                                $resOsItem['os_item'],
                                                '',
                                                date('Y-m-d'),
                                                'saida'
                                            )), FILE_APPEND);
                                            $lancamentoMovimentoEstoque = $osClass->lancaMovimentoEstoque(
                                                $posto,
                                                $resPeca['peca'],
                                                $peca['qtde'],
                                                $os,
                                                $resOsItem['os_item'],
                                                '',
                                                date('Y-m-d'),
                                                'saida'
                                            );

                                            file_put_contents($log_pecas_os_mobile, print_r($lancamentoMovimentoEstoque), FILE_APPEND);

                                            if (!$lancamentoMovimentoEstoque) {
                                                file_put_contents($log_pecas_os_mobile, "Erro ao lançar movimentação de saída no estoque, Peça {$peca['referencia']} #{$fetch['os_mobile']}\n", FILE_APPEND);
                                                file_put_contents($log_pecas_os_mobile, print_r(pg_last_error()), FILE_APPEND);
                                                throw new \Exception("Erro ao lançar movimentação de saída no estoque, Peça {$peca['referencia']} #{$fetch['os_mobile']}");
                                            }

                                            file_put_contents($log_pecas_os_mobile, "atualizou peça\n", FILE_APPEND);

                                            continue;
                                        //Se não possuir saldo no estoque
                                        } else {
                                            file_put_contents($log_pecas_os_mobile, "Se não possuir saldo no estoque\n", FILE_APPEND);

                                            //Altera o serviço para gerar pedido
                                            file_put_contents($log_pecas_os_mobile, "Altera o serviço para gerar pedido\n", FILE_APPEND);
                                            $sqlServicoRealizado = "
                                                SELECT servico_realizado, gera_pedido, troca_de_peca, peca_estoque
                                                FROM tbl_servico_realizado
                                                WHERE fabrica = {$login_fabrica}
                                                AND troca_de_peca IS TRUE
                                                AND gera_pedido IS TRUE
                                            ";
                                            $qryServicoRealizado = $pdo->query($sqlServicoRealizado);

                                            if (!$qryServicoRealizado || $qryServicoRealizado->rowCount() == 0) {
                                                file_put_contents($log_pecas_os_mobile, "Serviço Realizado para Gerar Pedido não encontrado #{$fetch['os_mobile']}\n", FILE_APPEND);
                                                file_put_contents($log_pecas_os_mobile, print_r(pg_last_error()), FILE_APPEND);
                                                throw new \Exception("Serviço Realizado para Gerar Pedido não encontrado #{$fetch['os_mobile']}");
                                            }

                                            $resServicoRealizado = $qryServicoRealizado->fetch();

                                            file_put_contents($log_pecas_os_mobile, print_r($resServicoRealizado), FILE_APPEND);

                                            //Atualiza a Qtde e Serviço da Peça
                                            file_put_contents($log_pecas_os_mobile, "Atualiza a Qtde e Serviço da Peça\n", FILE_APPEND);
                                            $updPeca = "
                                                UPDATE tbl_os_item SET
                                                    qtde = {$peca['qtde']},
                        						    servico_realizado = {$resServicoRealizado['servico_realizado']},
                        						    originou_troca = true
                                                WHERE os_item = {$resOsItem['os_item']}
                                            ";
                                            $qryUpdPeca = $pdo->query($updPeca);

                                            if (!$qryUpdPeca) {
                                                file_put_contents($log_pecas_os_mobile, "Erro ao atualizar a Qtde e Serviço da Peça {$peca['referencia']} na OS\n", FILE_APPEND);
                                                file_put_contents($log_pecas_os_mobile, print_r(pg_last_error()), FILE_APPEND);
                                                throw new \Exception("Erro ao atualizar a Qtde e Serviço da Peça {$peca['referencia']} na OS");
                                            }

                                            //Exclui a movimentação antiga de saída
                                            file_put_contents($log_pecas_os_mobile, "Exclui a movimentação antiga de saída\n", FILE_APPEND);
                                            file_put_contents($log_pecas_os_mobile, print_r(array(
                                                $posto, 
                                                $resPeca["peca"], 
                                                $os, 
                                                $resOsItem["os_item"]
                                            )), FILE_APPEND);
                                            $excluiMovimentoEstoque = $osClass->excluiMovimentacaoEstoque(
                                                $posto, 
                                                $resPeca["peca"], 
                                                $os, 
                                                $resOsItem["os_item"]
                                            );

                                            file_put_contents($log_pecas_os_mobile, print_r($excluiMovimentoEstoque), FILE_APPEND);

                                            if (!$excluiMovimentoEstoque) {
                                                file_put_contents($log_pecas_os_mobile, "Erro ao excluir movimentação de saída no estoque, Peça {$peca['referencia']} #{$fetch['os_mobile']}\n", FILE_APPEND);
                                                file_put_contents($log_pecas_os_mobile, print_r(pg_last_error()), FILE_APPEND);
                                                throw new \Exception("Erro ao excluir movimentação de saída no estoque, Peça {$peca['referencia']} #{$fetch['os_mobile']}");
                                            }

                                            //Armazena no array a Peça que deve ter o serviço alterado na Persys
                                            if (!isset($peca["exportado_persys"])) {
                                                $persys_AlteraServicoPeca[] = array(
                                                    "pecaId"               => $resPeca['peca'],
                                                    "pecaReferencia"       => $peca['referencia'],
                                                    "servicoRealizado"     => $resServicoRealizado["servico_realizado"],
                                                    "servicoRealizadoTipo" => "gera_pedido"
                                                );
                                            }

                                            file_put_contents($log_pecas_os_mobile, "atualizou peça\n", FILE_APPEND);

                                            continue;
                                        }
                                    //Se a Nova Qtde for inferior a Lançada devolve a diferença para o estoque do Posto
                                    } else if ($resOsItem["qtde"] > $peca["qtde"]) {
                                        file_put_contents($log_pecas_os_mobile, "Se a Nova Qtde for inferior a Lançada devolve a diferença para o estoque do Posto\n", FILE_APPEND);

                                        //Atualiza a Qtde da Peça
                                        file_put_contents($log_pecas_os_mobile, "Atualiza a Qtde da Peça\n", FILE_APPEND);
                                        $updPeca = "
                                            UPDATE tbl_os_item SET
                                                qtde = {$peca['qtde']}
                                            WHERE os_item = {$resOsItem['os_item']}
                                        ";
                                        $qryUpdPeca = $pdo->query($updPeca);

                                        if (!$qryUpdPeca) {
                                            file_put_contents($log_pecas_os_mobile, "Erro ao atualizar a Qtde da Peça {$peca['referencia']} na OS\n", FILE_APPEND);
                                            file_put_contents($log_pecas_os_mobile, print_r(pg_last_error()), FILE_APPEND);
                                            throw new \Exception("Erro ao atualizar a Qtde da Peça {$peca['referencia']} na OS");
                                        }

                                        //Exclui a movimentação antiga de saída
                                        file_put_contents($log_pecas_os_mobile, "Exclui a movimentação antiga de saída\n", FILE_APPEND);
                                        file_put_contents($log_pecas_os_mobile, print_r(array(
                                            $posto, 
                                            $resPeca["peca"], 
                                            $os, 
                                            $resOsItem["os_item"]
                                        )), FILE_APPEND);
                                        $excluiMovimentoEstoque = $osClass->excluiMovimentacaoEstoque(
                                            $posto, 
                                            $resPeca["peca"], 
                                            $os, 
                                            $resOsItem["os_item"]
                                        );

                                        file_put_contents($log_pecas_os_mobile, print_r($excluiMovimentoEstoque), FILE_APPEND);

                                        if (!$excluiMovimentoEstoque) {
                                            file_put_contents($log_pecas_os_mobile, "Erro ao excluir movimentação de saída no estoque, Peça {$peca['referencia']} #{$fetch['os_mobile']}\n", FILE_APPEND);
                                            file_put_contents($log_pecas_os_mobile, print_r(pg_last_error()), FILE_APPEND);
                                            throw new \Exception("Erro ao excluir movimentação de saída no estoque, Peça {$peca['referencia']} #{$fetch['os_mobile']}");
                                        }

                                        //Lança a movimentação de saída do estoque
                                        file_put_contents($log_pecas_os_mobile, "Lança a movimentação de saída do estoque\n", FILE_APPEND);
                                        file_put_contents($log_pecas_os_mobile, print_r(array(
                                            $posto,
                                            $resPeca['peca'],
                                            $peca['qtde'],
                                            $os,
                                            $resOsItem['os_item'],
                                            '',
                                            date('Y-m-d'),
                                            'saida'
                                        )), FILE_APPEND);
                                        $lancamentoMovimentoEstoque = $osClass->lancaMovimentoEstoque(
                                            $posto,
                                            $resPeca['peca'],
                                            $peca['qtde'],
                                            $os,
                                            $resOsItem['os_item'],
                                            '',
                                            date('Y-m-d'),
                                            'saida'
                                        );

                                        file_put_contents($log_pecas_os_mobile, print_r($lancamentoMovimentoEstoque), FILE_APPEND);

                                        if (!$lancamentoMovimentoEstoque) {
                                            file_put_contents($log_pecas_os_mobile, "Erro ao lançar movimentação de saída no estoque, Peça {$peca['referencia']} #{$fetch['os_mobile']}\n", FILE_APPEND);
                                            file_put_contents($log_pecas_os_mobile, print_r(pg_last_error()), FILE_APPEND);
                                            throw new \Exception("Erro ao lançar movimentação de saída no estoque, Peça {$peca['referencia']} #{$fetch['os_mobile']}");
                                        }

                                        file_put_contents($log_pecas_os_mobile, "atualizou peça\n", FILE_APPEND);

                                        continue;
                                    }
                                //Verifica se o Serviço é Gera Pedido
                                } else if ($resServicoRealizado["troca_de_peca"] == "t" && $resServicoRealizado["gera_pedido"] == "t") {
                                    file_put_contents($log_pecas_os_mobile, "Verifica se o Serviço é Gera Pedido\n", FILE_APPEND);

                                    //Verifica se há saldo no estoque para a Peça
                                    file_put_contents($log_pecas_os_mobile, "Verifica se há saldo no estoque para a Peça\n", FILE_APPEND);
                                    $estoquePosto = $osClass->verificaEstoquePosto($posto, $resPeca["peca"], $peca["qtde"]);

                                    file_put_contents($log_pecas_os_mobile, print_r($estoquePosto), FILE_APPEND);

                                    //Se tiver saldo no estoque
                                    if ($estoquePosto) {
                                        file_put_contents($log_pecas_os_mobile, "Se tiver saldo no estoque\n", FILE_APPEND);

                                        //Altera o serviço para estoque
                                        file_put_contents($log_pecas_os_mobile, "Altera o serviço para estoque\n", FILE_APPEND);
                                        $sqlServicoRealizado = "
                                            SELECT servico_realizado, gera_pedido, troca_de_peca, peca_estoque
                                            FROM tbl_servico_realizado
                                            WHERE fabrica = {$login_fabrica}
                                            AND troca_de_peca IS TRUE
                                            AND peca_estoque IS TRUE
                                        ";
                                        $qryServicoRealizado = $pdo->query($sqlServicoRealizado);

                                        if (!$qryServicoRealizado || $qryServicoRealizado->rowCount() == 0) {
                                            file_put_contents($log_pecas_os_mobile, "Serviço Realizado para Estoque não encontrado #{$fetch['os_mobile']}\n", FILE_APPEND);
                                            file_put_contents($log_pecas_os_mobile, print_r(pg_last_error()), FILE_APPEND);
                                            throw new \Exception("Serviço Realizado para Estoque não encontrado #{$fetch['os_mobile']}");
                                        }

                                        $resServicoRealizado = $qryServicoRealizado->fetch();

                                        file_put_contents($log_pecas_os_mobile, print_r($resServicoRealizado), FILE_APPEND);

                                        //Atualiza a Qtde e Serviço da Peça
                                        file_put_contents($log_pecas_os_mobile, "Atualiza a Qtde e Serviço da Peça\n", FILE_APPEND);
                                        $updPeca = "
                                            UPDATE tbl_os_item SET
                                                qtde = {$peca['qtde']},
                        						servico_realizado = {$resServicoRealizado['servico_realizado']},
                        						originou_troca = true
                                            WHERE os_item = {$resOsItem['os_item']}
                                        ";
                                        $qryUpdPeca = $pdo->query($updPeca);

                                        if (!$qryUpdPeca) {
                                            file_put_contents($log_pecas_os_mobile, "Erro ao atualizar a Qtde e Serviço da Peça {$peca['referencia']} na OS\n", FILE_APPEND);
                                            file_put_contents($log_pecas_os_mobile, print_r(pg_last_error()), FILE_APPEND);
                                            throw new \Exception("Erro ao atualizar a Qtde e Serviço da Peça {$peca['referencia']} na OS");
                                        }

                                        //Lança a movimentação de saída do estoque
                                        file_put_contents($log_pecas_os_mobile, "Lança a movimentação de saída do estoque\n", FILE_APPEND);
                                        file_put_contents($log_pecas_os_mobile, print_r(array(
                                            $posto,
                                            $resPeca['peca'],
                                            $peca['qtde'],
                                            $os,
                                            $resOsItem['os_item'],
                                            '',
                                            date('Y-m-d'),
                                            'saida'
                                        )), FILE_APPEND);
                                        $lancamentoMovimentoEstoque = $osClass->lancaMovimentoEstoque(
                                            $posto,
                                            $resPeca['peca'],
                                            $peca['qtde'],
                                            $os,
                                            $resOsItem['os_item'],
                                            '',
                                            date('Y-m-d'),
                                            'saida'
                                        );

                                        file_put_contents($log_pecas_os_mobile, print_r($lancamentoMovimentoEstoque), FILE_APPEND);

                                        if (!$lancamentoMovimentoEstoque) {
                                            file_put_contents($log_pecas_os_mobile, "Erro ao lançar movimentação de saída no estoque, Peça {$peca['referencia']} #{$fetch['os_mobile']}\n", FILE_APPEND);
                                            file_put_contents($log_pecas_os_mobile, print_r(pg_last_error()), FILE_APPEND);
                                            throw new \Exception("Erro ao lançar movimentação de saída no estoque, Peça {$peca['referencia']} #{$fetch['os_mobile']}");
                                        }

                                        //Armazena no array a Peça que deve ter o serviço alterado na Persys
                                        if (!isset($peca["exportado_persys"])) {
                                            $persys_AlteraServicoPeca[] = array(
                                                "pecaId"               => $resPeca['peca'],
                                                "pecaReferencia"       => $peca['referencia'],
                                                "servicoRealizado"     => $resServicoRealizado["servico_realizado"],
                                                "servicoRealizadoTipo" => "estoque"
                                            );
                                        }

                                        file_put_contents($log_pecas_os_mobile, "atualizou peça\n", FILE_APPEND);

                                        continue;
                                    //Se não possuir saldo no estoque
                                    } else {
                                        file_put_contents($log_pecas_os_mobile, "Se não possuir saldo no estoque\n", FILE_APPEND);

                                        //Atualiza a Qtde da Peça
                                        file_put_contents($log_pecas_os_mobile, "Atualiza a Qtde da Peça\n", FILE_APPEND);
                                        $updPeca = "
                                            UPDATE tbl_os_item SET
                                                qtde = {$peca['qtde']}
                                            WHERE os_item = {$resOsItem['os_item']}
                                        ";
                                        $qryUpdPeca = $pdo->query($updPeca);

                                        if (!$qryUpdPeca) {
                                            file_put_contents($log_pecas_os_mobile, "Erro ao atualizar a Qtde da Peça {$peca['referencia']} na OS\n", FILE_APPEND);
                                            file_put_contents($log_pecas_os_mobile, print_r(pg_last_error()), FILE_APPEND);
                                            throw new \Exception("Erro ao atualizar a Qtde da Peça {$peca['referencia']} na OS");
                                        }

                                        file_put_contents($log_pecas_os_mobile, "atualizou peça\n", FILE_APPEND);

                                        continue;
                                    }
                                //Se for Ajuste faz o UPDATE e vai para a próxima Peça
                                } else {
                                    file_put_contents($log_pecas_os_mobile, "Se for Ajuste faz o UPDATE e vai para a próxima Peça\n", FILE_APPEND);

                                    $updPeca = "
                                        UPDATE tbl_os_item SET
                                            qtde = {$peca['qtde']}
                                        WHERE os_item = {$resOsItem['os_item']}
                                    ";
                                    $qryUpdPeca = $pdo->query($updPeca);

                                    if (!$qryUpdPeca) {
                                        file_put_contents($log_pecas_os_mobile, "Erro ao atualizar a Qtde da Peça {$peca['referencia']} na OS\n", FILE_APPEND);
                                        file_put_contents($log_pecas_os_mobile, print_r(pg_last_error()), FILE_APPEND);
                                        throw new \Exception("Erro ao atualizar a Qtde da Peça {$peca['referencia']} na OS");
                                    }

                                    file_put_contents($log_pecas_os_mobile, "atualizou peça\n", FILE_APPEND);

                                    continue;
                                }
                            //Se não estiver alterando nada vai para a próxima peça
                            } else {
                                file_put_contents($log_pecas_os_mobile, "Se não estiver alterando nada vai para a próxima peça\n", FILE_APPEND);

                                continue;
                            }
                        }
                    }

                    if ($debug) {
                        echo date('d-m-Y H:i');
                        echo "\n";
                        echo "Peças validadas...\n";
                        echo "Verificar Auditoria de Peças...\n";
                    }

                    if ($fora_garantia != "t") {
                        $sql = "SELECT qtde_pecas_intervencao FROM tbl_fabrica WHERE fabrica = {$login_fabrica};";
                        $qry = $pdo->query($sql);
                        $res = $qry->fetch();

                        $qtde_pecas_intervencao = $res["qtde_pecas_intervencao"];

                        if(!strlen($qtde_pecas_intervencao)){
                            $qtde_pecas_intervencao = 0;
                        }

                        if ($qtde_pecas_intervencao > 0) {
                            $sql = "
                                SELECT COUNT(*) AS qtde_pecas
                                FROM tbl_os_item
                                INNER JOIN tbl_os_produto ON tbl_os_produto.os_produto = tbl_os_item.os_produto
                                INNER JOIN tbl_servico_realizado ON tbl_servico_realizado.servico_realizado = tbl_os_item.servico_realizado AND tbl_servico_realizado.gera_pedido IS TRUE AND tbl_servico_realizado.troca_de_peca IS TRUE
                                WHERE tbl_os_produto.os = {$os};
                            ";
                            $qry = $pdo->query($sql);
                            $res = $qry->fetch();

                            $qtde_pecas = $res["qtde_pecas"];

                            if(!strlen($qtde_pecas)){
                                $qtde_pecas = 0;
                            }

                            if($qtde_pecas > $qtde_pecas_intervencao){
                                $busca = buscaAuditoria("tbl_auditoria_status.peca = 't'");

                                if($busca['resultado']){
                                    $auditoria_status = $busca['auditoria'];
                                }

                                if (verifica_auditoria_unica("tbl_auditoria_status.peca = 't' AND tbl_auditoria_os.observacao ILIKE '%peças excedentes%'", $os) === true 
                                    || aprovadoAuditoria("tbl_auditoria_status.peca = 't' AND tbl_auditoria_os.observacao ILIKE '%peças excedentes%'", $os)
                                ) {
                                    $sql = "
                                        INSERT INTO tbl_auditoria_os (os, auditoria_status, observacao)
                                        VALUES ({$os}, {$auditoria_status}, 'OS em auditoria de peças excedentes');
                                    ";
                                    $qry = $pdo->query($sql);

                                    if (!$qry) {
                                        if ($debug) {
                                            echo date('d-m-Y H:i');
                                            echo "\n";
                                            echo "\n";
                                            echo "### ERRO ###\n";
                                            echo "Erro ao gravar auditoria de peças excedentes da OS {$os} #{$fetch["os_mobile"]}...\n\n";
                                        }

                                        $erro_validacao[] = "Erro ao gravar auditoria de peças excedentes da OS {$os} #{$fetch["os_mobile"]}";
                                    }
                                }
                            }
                        }

                        $busca = buscaAuditoria("tbl_auditoria_status.peca = 't'");

                        if($busca['resultado']){
                            $auditoria_status = $busca['auditoria'];
                        }

                        if ((verifica_auditoria_unica("tbl_auditoria_status.peca = 't' AND tbl_auditoria_os.observacao ILIKE '%peças excedentes%'", $os) === true 
                            || aprovadoAuditoria("tbl_auditoria_status.peca = 't' AND tbl_auditoria_os.observacao ILIKE '%peça crí­tica%'", $os))
                            && $nova_peca_troca == true 
                            && $nova_peca_critica == true
                        ){
                            $sql = "
                                INSERT INTO tbl_auditoria_os (os, auditoria_status, observacao)
                                VALUES ({$os}, {$auditoria_status}, 'OS em intervenção da fábrica por Peça Crí­tica')";
                            $qry = $pdo->query($sql);

                            if (!$qry) {
                                if ($debug) {
                                    echo date('d-m-Y H:i');
                                    echo "\n";
                                    echo "\n";
                                    echo "### ERRO ###\n";
                                    echo "Erro ao gravar auditoria de peça crítica da OS {$os} #{$fetch["os_mobile"]}...\n\n";
                                }

                                $erro_validacao[] = "Erro ao gravar auditoria de peça crítica da OS {$os} #{$fetch["os_mobile"]}";
                            }
                        }
                    }
                }

                if ($debug) {
                    echo date('d-m-Y H:i');
                    echo "\n";
                    echo "Fim validação de Auditoria de Peça...\n";
                    echo "Validando Interações...\n";
                }

                foreach ($dados['historico'] as $historico) {
                    try { // $ewfh
                        $data_historico = date('Y-m-d H:i:s', $historico['data']);
                        $status_historico = $historico['status'];

                        $acao = "
                        SELECT COUNT(*) FROM tbl_os_interacao
                            WHERE fabrica = {$login_fabrica}
                            AND data = '{$data_historico}'
                            AND comentario = '{$status_historico}'
                            AND os = {$os};
                        ";
                        $qry = $pdo->query($acao);
                        $res = $qry->fetch();

                        $valida_interacao = $res[0];

                        if ($valida_interacao == 0) {
                            $insInter = "INSERT INTO tbl_os_interacao
                                            (os, data, comentario, fabrica, posto)
                                        VALUES
                                            ({$os}, '{$data_historico}', '{$status_historico}', {$login_fabrica}, {$posto});";
                            $qryInsInter = $pdo->query($insInter);

                            if (!$qryInsInter) {
                                throw new \Exception("Erro ao Interagir na OS - {$os} #{$fetch["os_mobile"]}");
                            }
                        }
                    } catch (\Exception $ewfh) {
                        if ($debug) {
                            echo date('d-m-Y H:i');
                            echo "\n";
                            echo "\n";
                            echo "### ERRO ###\n";
                            echo $ewfh->getMessage()."...\n\n";
                        }

                        $erro_validacao[] = $ewfh->getMessage();
                    }
                }

                if ($debug) {
                    echo date('d-m-Y H:i');
                    echo "\n";
                    echo "Interações validadas...\n";
                    echo "Atualizando dados do Técnico e informações adicionais da OS...\n";
                }

                $update_os_extra = array();
                $update_tecnico_agenda = array();

                if (array_key_exists('status', $dados)) {
                    $status = $dados['status'];

                    if ($status['codigo'] == 'PS4') {
                        $data_inicio = date("Y-m-d H:i", $status['dataAlteracao'] / 1000);

                        if (!empty($data_inicio)) {
                            $update_os_extra[]       = "inicio_atendimento = '" . $data_inicio . "'";
                            $update_tecnico_agenda[] = "hora_inicio_trabalho = '" . $data_inicio . "'";
                        }

                        $sqlLatLng = "
                            SELECT 
                                tbl_tecnico_agenda.tecnico,
                                tbl_os_campo_extra.campos_adicionais 
                            FROM tbl_os_campo_extra 
                            INNER JOIN tbl_tecnico_agenda ON tbl_tecnico_agenda.os = tbl_os_campo_extra.os AND tbl_tecnico_agenda.fabrica = {$login_fabrica}
                            WHERE tbl_os_campo_extra.fabrica = {$login_fabrica} 
                            AND tbl_os_campo_extra.os = {$os}
                        ";
                        $qryLatLng = $pdo->query($sqlLatLng);

                        if (!$qryLatLng) {
                            $erro_validacao[] = "Erro ao buscar Latitude/Longitude - {$os} #{$fetch["os_mobile"]}";
                        }

                        $resLatLng = $qryLatLng->fetch();

                        $tecnico           = $resLatLng["tecnico"];
                        $campos_adicionais = json_decode($resLatLng["campos_adicionais"], true);

                        $latitude  = $campos_adicionais["cliente_latitude"];
                        $longitude = $campos_adicionais["cliente_longitude"];

                        if (strlen($tecnico) > 0 && strlen($latitude) > 0 && strlen($longitude) > 0) {
                            $sqlLatLng = "
                                UPDATE tbl_tecnico SET
                                    latitude = {$latitude},
                                    longitude = {$longitude}
                                WHERE tecnico = {$tecnico}
                                AND fabrica = {$login_fabrica}
                            ";
                            $qryLatLng = $pdo->query($sqlLatLng);

                            if (!$qryLatLng) {
                                $erro_validacao[] = "Erro ao gravar Latitude/Longitude para o técnico - {$os} #{$fetch["os_mobile"]}";
                            }
                        }
                    } else if ($status['codigo'] == 'PS5') {
                        $data_termino = date("Y-m-d H:i", $status['dataAlteracao'] / 1000);

                        if (!empty($data_termino)) {
                            $update_os_extra[]       = "termino_atendimento = '" . $data_termino . "'";
                            $update_tecnico_agenda[] = "hora_fim_trabalho = '" . $data_termino . "'";
                        }
                    }
                }

                if (array_key_exists('amperagem', $dados)) {
                    $update_os_extra[] = 'regulagem_peso_padrao = ' . $dado['amperagem'];
                }

                if (!empty($update_os_extra)) {
                    $sqlOsExtra = 'UPDATE tbl_os_extra SET ' . implode(', ', $update_os_extra) . ' WHERE os = ' . $os;
                    $qrOsExtra = $pdo->query($sqlOsExtra);

                    if (!$qrOsExtra) {
                        $erro_validacao[] = "Erro ao gravar informações da OS - {$os} #{$fetch["os_mobile"]}";
                    }

                    $sqlTecnicoAgenda = 'UPDATE tbl_tecnico_agenda SET ' . implode(', ', $update_tecnico_agenda) . ' WHERE os = ' . $os;
                    $qrTecnicoAgenda = $pdo->query($sqlTecnicoAgenda);

                    if (!$qrTecnicoAgenda) {
                        $erro_validacao[] = "Erro ao gravar informações da agenda do técnico - {$os} #{$fetch["os_mobile"]}";
                    }
                }

                if ($debug) {
                    echo date('d-m-Y H:i');
                    echo "\n";
                    echo "Informações do técnico e OS atualizados...\n";
                    echo "Verificação de anexos...\n";
                }

                /**
                 * Pesquisa Anexos
                 */
                $sql = "SELECT campos_adicionais FROM tbl_os_campo_extra WHERE fabrica = {$login_fabrica} AND os = {$os}";
                $qry = $pdo->query($sql);

                if ($qry->rowCount() > 0) {
                    $res = $qry->fetch();
                    $campos_adicionais = $res["campos_adicionais"];
                } else {
                    $campos_adicionais = array();
                }


                if (empty($campos_adicionais)) {
                    $campos_adicionais = array(
                        "anexos" => array()
                    );
                } else {
                    $campos_adicionais = json_decode($campos_adicionais, true);

                    if (!isset($campos_adicionais["anexos"])) {
                        $campos_adicionais["anexos"] = array();
                    }
                }

                $obs_anexo = array();

                if (count($dados["anexos"]) > 0) {
                    if ($debug) {
                        echo date('d-m-Y H:i');
                        echo "\n";
                        echo "Anexos encontrados iniciando upload...\n";
                    }

                    $s3 = new AmazonTC("os", $login_fabrica);

                    foreach ($dados["anexos"] as $anexo) {
                        if (in_array($anexo["id"], $campos_adicionais["anexos"])) {
                            continue;
                        }

                        $obs_anexo[] = $anexo["descricao"];

                        $i = count($campos_adicionais["anexos"]);

                        $arquivo = "/tmp/imbera-importa-os-mobile.d/{$os}_{$anexo["id"]}";

                        $curl = curl_init();

                        curl_setopt_array($curl, array(
                            CURLOPT_URL => "http://telecontrol.eprodutiva.com.br/api/ordem/anexo/{$anexo['id']}/imagem?x=1000&y=1000",
                            CURLOPT_RETURNTRANSFER => true,
                            CURLOPT_ENCODING => "",
                            CURLOPT_MAXREDIRS => 10,
                            CURLOPT_TIMEOUT => 30,
                            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                            CURLOPT_CUSTOMREQUEST => "GET",
                            CURLOPT_HTTPHEADER => array(
                                "authorizationv2: 12984374000259-7a4e7d2cb15c403b7a33c73ccc4dc4e9",
                                "cache-control: no-cache",
                                "content-type: application/json"
                            ),
                        ));

                        $response = curl_exec($curl);
                        $err = curl_error($curl);

                        curl_close($curl);

                        if ($err) {
                          $erro_validacao[] = "Erro ao buscar anexo - {$os} #{$fetch["os_mobile"]}";
                        } else {
                            file_put_contents(
                                $arquivo,
                                $response
                            );

                            if (filesize($arquivo) == 0) {
                                continue;
                            }

                            $tipo = exif_imagetype($arquivo);

                            switch ($tipo) {
                                case 2:
                                    $ext = "jpeg";
                                    break;
                                
                                case 3:
                                    $ext = "png";
                                    break;

                                case 6:
                                    $ext = "bmp";
                                    break;
                            }

                            system("mv {$arquivo} {$arquivo}.{$ext}");
                    
                            $s3->upload(
                                "{$os}_{$i}",
                                array(
                                    "name" => "{$arquivo}.{$ext}",
                                    "tmp_name" => "{$arquivo}.{$ext}"
                                ), 
                                $ano_abertura, 
                                $mes_abertura
                            );

                            if ($s3->result) {
                                $campos_adicionais["anexos"][] = $anexo["id"];
                            }
                        }
                    }

                    if ($debug) {
                        echo date('d-m-Y H:i');
                        echo "\n";
                        echo "Gravando informações do anexo {$anexo["id"]}...\n";
                    }

                    $campos_adicionais = json_encode($campos_adicionais);

                    $sql = "
                        UPDATE tbl_os_campo_extra SET
                            campos_adicionais = '{$campos_adicionais}'
                        WHERE fabrica = {$login_fabrica}
                        AND os = {$os}
                    ";
                    $qry = $pdo->query($sql);

                    if (!$qry) {
                        $erro_validacao[] = "Erro ao gravar informações de anexo - {$os} #{$fetch["os_mobile"]}";
                    }

                    if (count($obs_anexo) > 0) {
                        if ($debug) {
                            echo date('d-m-Y H:i');
                            echo "\n";
                            echo "Gravando Observações do anexo na OS...\n";
                        }

                        $obs .= utf8_decode(" \n".implode(" \n", $obs_anexo));
                        $obs = addslashes($obs);
                        $sql = "UPDATE tbl_os SET obs = '{$obs}' WHERE fabrica = {$login_fabrica} AND os = {$os}";
                        $qry = $pdo->query($sql);

                        if (!$qry) {
                            $erro_validacao[] = "Erro ao gravar observação - {$os} #{$fetch["os_mobile"]}";
                        }            
                    }
                }

                if ($debug) {
                    echo date('d-m-Y H:i');
                    echo "\n";
                    echo "Anexos Ok...\n";
                    echo "Atualizando Status OS Mobile...\n";
                }

                if (!empty($status['codigo'])) {
                    $up = "UPDATE tbl_os_mobile SET conferido = 't', status_os_mobile = '{$status['codigo']}' WHERE os_mobile = {$fetch["os_mobile"]}";
                } else {
                    $up = "UPDATE tbl_os_mobile SET conferido = 't' WHERE os_mobile = {$fetch["os_mobile"]}";
                }
                $qr = $pdo->query($up);

                if (!$qr) {
                    $erro_validacao[] = "Erro ao atualizar dados do status da integração";
                }

                if (count($erro_validacao) > 0) {
                    throw new \Exception(implode("<br />", $erro_validacao));
                }

		//Verifica se a Peças para alterar o serviço na Persys
		if (count($persys_AlteraServicoPeca)) {
		    echo "Atualizando serviço no Mobile\n";

		    echo "Buscando ID da OS no Mobile\n";

		    $os_id_externo = $cockpit->getOsIdExterno($os);

		    echo "ID OS Mobile -> {$os_id_externo}\n";
		    echo "Buscando os serviços da OS Mobile\n";

		    $servicos = $cockpit->getServicosOs($os_id_externo);

		    $servico_estoque = null;
		    $servico_pedido  = null;

		    foreach ($servicos as $servico) {
		        if (preg_match("/\(estoque\)/", $servico["servico"]["nome"])) {
			    $servico_estoque = $servico["servico"]["id"];
			} else if (preg_match("/\(gera pedido\)/", $servico["servico"]["nome"])) {
			    $servico_pedido = $servico["servico"]["id"];
		        }    
		    } 

		    foreach ($persys_AlteraServicoPeca as $peca) {
                        //Busca os Serviços da OS que estão gravados na Persys
                        $servicos = $cockpit->getServicosOs($os_id_externo);

                        $servico_antigo  = null;

                        //Se possuir Peça com o serviço Gera Pedido e não existir o Gera Pedido vinculado a OS na Persys, irá vincular o Serviço Gera Pedido a OS
			if ($peca["servicoRealizadoTipo"] == "gera_pedido" && is_null($servico_pedido)) {
			    echo "Vinculando o serviço gera pedido com a OS\n";
                            $servico        = $cockpit->getServico($peca["servicoRealizado"]);
                            $servico_pedido = $servico["id"];

                            $cockpit->vincularServicoOs($os_id_externo, $servico_pedido);
                        }

                        //Se possuir Peça com o serviço Estoque e não existir o Estoque vinculado a OS na Persys, irá vincular o Serviço Estoque a OS
			if ($peca["servicoRealizadoTipo"] == "estoque" && is_null($servico_estoque)) {
			    echo "Vinculando o serviço estoque com a OS\n";
                            $servico         = $cockpit->getServico($peca["servicoRealizado"]);
                            $servico_estoque = $servico["id"];

                            $cockpit->vincularServicoOs($os_id_externo, $servico_estoque);
                        }

			//Busca ID Externo da Peça na Persys
			echo "Buscando ID da peça no Mobile\n";
                        $peca_id_externo = $cockpit->getPecaIdExterno($peca['pecaReferencia']);
                        $peca_id_externo = $peca_id_externo["id"];

                        if (empty($peca_id_externo)) {
                            throw new \Exception("Erro ao buscar ID Externo da Peça {$peca['pecaReferencia']} #{$fetch['os_mobile']}");
                        }

                        //Vicula Peça ao Serviço
			if ($peca["servicoRealizadoTipo"] == "gera_pedido") {
			    echo "Vinculando peça ao serviço gera pedido\n";
                            $servico_vinculado = $servico_pedido;
                            $servico_antigo    = $servico_estoque;

                            $resVincularPecaServico = $cockpit->vincularPecaServico($os_id_externo, $peca_id_externo, $servico_pedido);
			} else if ($peca["servicoRealizadoTipo"] == "estoque") {
			    echo "Vinculnando peça ao serviço estoque\n";
                            $servico_vinculado = $servico_estoque;
                            $servico_antigo    = $servico_pedido;

                            $resVincularPecaServico = $cockpit->vincularPecaServico($os_id_externo, $peca_id_externo, $servico_estoque);
                        }

			if (empty($resVincularPecaServico) || utf8_decode($resVincularPecaServico["error"]["message"]) != "Informação já cadastrada") {
                            throw new \Exception("Erro ao vincular Serviço a Peça {$peca['pecaReferencia']} #{$fetch['os_mobile']}");
                        //Se já existir o Serviço vinculado a Peça, irá ativar o vinculo do Serviço com a Peça
			} else if (utf8_decode($resVincularPecaServico["error"]["message"]) == "Informação já cadastrada") {
			    echo "Ativa vinculo novo da peça x serviço\n";
                            $cockpit->statusVinculoPecaServico($os_id_externo, $peca_id_externo, $servico_vinculado, 1);
                        }
                        
                        //Inativa o vinculo da Peça com o Serviço Antigo
			if (!is_null($servico_antigo)) {
			    echo "Inativa vinculo antigo da peça x serviço\n";
                            $cockpit->statusVinculoPecaServico($os_id_externo, $peca_id_externo, $servico_antigo, 0);
                        }

			//echo "Confirmando alteração\n";
                        //$cockpit->confirmaServicoAlteradoPersys($fetch["os_mobile"], $peca["pecaReferencia"]);
                    }
                }

                if ($debug) {
                    echo date('d-m-Y H:i');
                    echo "\n";
                    echo "Status OS Mobile Atualizado...\n";
                }

                $pdo->commit();
            } catch (\Exception $ew) {
                if ($begin) {
                    $pdo->rollBack();
                }

                if ($debug) {
                    echo date('d-m-Y H:i');
                    echo "\n";
                    echo "\n";
                    echo "### ERRO ###\n";
                    echo $ew->getMessage()."...\n\n";
                }

                $routineScheduleLogError->setRoutineScheduleLog($routine_schedule_log_id);
                $routineScheduleLogError->setLineNumber($fetch['os_mobile']);

                $log_error = $routineScheduleLogError->SelectLogErrorsByRoutineId($routine_id);

                if ($debug) {
                    echo date('d-m-Y H:i');
                    echo "\n";
                    echo "Gravando Informações de Log de Erros...\n";
                }

                $routineScheduleLogError->setContents(utf8_encode($ew->getMessage()));
                $routineScheduleLogError->setErrorMessage(utf8_encode("Ocorreram erros durante o processamento da integração"));

                if (!$log_error) {
                    $routineScheduleLogError->Insert();
                } else {
                    $routineScheduleLogError->setRoutineScheduleLogError($log_error[0]["routine_schedule_log_error"]);
                    $routineScheduleLogError->Update();
                }

            }

            if ($debug) {
                echo date('d-m-Y H:i');
                echo "\n";
                echo "Processamento OS {$os} Finalizado...\n\n";
            }
        }
    }

    $routineScheduleLog->setStatus(1);
    $routineScheduleLog->setStatusMessage(utf8_encode("Rotina Finalizada com sucesso"));
    $routineScheduleLog->setDateFinish(date("Y-m-d H:i:s"));
    $routineScheduleLog->Update();

    if ($debug) {
        echo date('d-m-Y H:i');
        echo "\n";
        echo "Finalizada integração dos dados, verifique possíveis erros no monitor de Interface Mobile/Web.\n";
        echo "----------------------------\n\n";
    }

} catch (\Exception $e) {
    
    if ($debug) {
        echo date('d-m-Y H:i');
        echo "\n";
        echo "### ERRO PAROU A ROTINA ###\n";
        echo $e->getMessage().".\n\n";
    }

    $routineScheduleLog->setStatus(0);
    $routineScheduleLog->setStatusMessage($e->getMessage());
    $routineScheduleLog->setDateFinish(date("Y-m-d H:i"));
    $routineScheduleLog->Update();

    if ($debug) {
        echo date('d-m-Y H:i');
        echo "\n";
        echo "Finalizada integração dos dados, ".$e->getMessage()."\n";
    }

}

if ($debug) {
    echo date('d-m-Y H:i');
    echo "\n";
    echo "Verificando se existem OSs pendentes de finalização...\n";
}

try {

    $token = generateToken($applicationKey);
    $status = 'PS5'; //PS5 status concluido

    $nroOsMobile = null;

    $sql = "
        SELECT admin
        FROM tbl_admin
        WHERE fabrica = 158
        AND login = 'rotinaautomatica'
    ";
    $qry = $pdo->query($sql);
    $res = $qry->fetch();
    $login_rotina_automatica = $res["admin"]; 

    $sql = "
        SELECT DISTINCT
            tbl_os.os,
            tbl_os_mobile.conferido
        FROM tbl_os_mobile
        JOIN tbl_os using(os,fabrica)
        WHERE tbl_os.finalizada IS NULL
        AND tbl_os_mobile.conferido IS TRUE
        AND tbl_os.fabrica = {$login_fabrica}
	AND tbl_os_mobile.status_os_mobile = '{$status}';
    " ;

    $res = pg_query($con,$sql);
    $data = pg_fetch_all($res) ;

    foreach ($data as $item) {
        try {
            $osData = json_decode($item['dados'], true);
            $nroOsMobile = $item['os'];

            $sql = "
                SELECT
                    os,
                    fora_garantia
                FROM tbl_os
                JOIN tbl_tipo_atendimento USING(tipo_atendimento,fabrica)
                WHERE fabrica = {$login_fabrica}
                AND os = {$item["os"]}
                AND finalizada IS NULL
                AND data_fechamento IS NULL;
            ";
            $qry = $pdo->query($sql);

            if (!$qry || $qry->rowCount() == 0) {
                continue;
            } else {
                $dados_os = $qry->fetch();
                $tipo_atendimento_fora_garantia = $dados_os['fora_garantia'];
            }

            if ($item['conferido'] == true) {
                if ($debug) {
                    echo date('d-m-Y H:i');
                    echo "\n";
                    echo "Finalizando OS {$item['os']}...\n";
                }
                $classOs = new \Posvenda\Fabricas\_158\Os($login_fabrica, $item['os']);
                $classOs->finaliza($con, false, null, "mobile");

                $atendimento_callcenter = $classOs->verificaAtendimentoCallcenter($item['os']);

                if ($atendimento_callcenter) {
                    $classOs->finalizaAtendimento($atendimento_callcenter);
                }

                $oPedido            = new \Posvenda\Pedido($login_fabrica);
                $oExportaPedido     = new \Posvenda\Fabricas\_158\ExportaPedido($oPedido, $classOs, $login_fabrica);
                $oPedidoBonificacao = new \Posvenda\Fabricas\_158\PedidoBonificacao($oPedido);

                $pedido = $oExportaPedido->getPedido($os);

                $garantia_antecipada = $pedido[0]['garantia_antecipada'];
                $pedido_em_garantia = $pedido[0]['pedido_em_garantia'];

                if ($garantia_antecipada != 't' && $pedido_em_garantia == 't' && $tipo_atendimento_fora_garantia == "t") {
                    $pedido = $oPedidoBonificacao->organizaEstoque($pedido, true);
                    if ($oExportaPedido->pedidoIntegracao($pedido,"cobranca_kof", true) === false) {
                        throw new \Exception("Pedido não foi enviado para o SAP");
                    }                                       
                }

                $insInteracaoOsFinalizada = "
                    INSERT INTO tbl_os_interacao
                    (os, data, admin, comentario, fabrica)
                    VALUES
                    ({$item['os']}, CURRENT_TIMESTAMP, {$login_rotina_automatica}, 'OS finalizada', {$login_fabrica})
                ";
                $qry = $pdo->query($insInteracaoOsFinalizada);

                $sql = "
                    SELECT dados
                    FROM tbl_os_mobile
                    WHERE fabrica = {$login_fabrica}
                    AND os = {$item['os']}
                    AND status_os_mobile = 'PS5'
                    ORDER BY data_input DESC
                    LIMIT 1
                ";
                $query = $pdo->query($sql);

                $result = $query->fetch();

                $dados = json_decode($result["dados"], true);

                $end_date = date("Y-m-d H:i", $dados["status"]["dataAlteracao"] / 1000);

                $sql = "
                    UPDATE tbl_os_extra SET
                        termino_atendimento = '{$end_date}'
                    WHERE os = {$item['os']}
                ";
                $query = $pdo->query($sql);

                if (!$query) {
                    throw new Exception("Erro ao atualizar data do fim do atendimento");
                }
            } else {
                if ($debug) {
                    echo date('d-m-Y H:i');
                    echo "\n";
                    echo "OS {$item['os']} para finalização mas não foi conferida...\n";
                }
            }
        } catch (\Exception $ex) {
            $sql = "
                UPDATE tbl_os SET
                    data_conserto = current_timestamp,
                    data_fechamento = null,
                    finalizada = null
                WHERE os = {$item['os']};
            ";
            $qry = $pdo->query($sql);

            $sql = "
                UPDATE tbl_os_campo_extra SET
                    origem_fechamento = NULL
                WHERE os = {$item['os']};
            ";
            $qry = $pdo->query($sql);

            $sql = "
                UPDATE tbl_os_extra SET
                    obs_fechamento = '".$ex->getMessage()."'
                WHERE os = {$item['os']};
            ";
            $qry = $pdo->query($sql);

            /*$cockpit = new Cockpit($login_fabrica);
            $status = $cockpit->getMobileStatus("PS8");

            $dados = array(
                "situacaoOrdem" => array(
                    "id" => $status["id"]
                 )
             );

            $id_externo = $cockpit->getOsIdExterno($item["os"]);
            $cockpit->updateSituacao($id_externo, $dados);*/

            $routineScheduleLogError->setRoutineScheduleLog($routine_schedule_log_id);
            $routineScheduleLogError->setLineNumber($nroOsMobile);
            $routineScheduleLogError->setContents(utf8_encode($ex->getMessage()));
            $routineScheduleLogError->setErrorMessage(utf8_encode('Ocorreu(ram) erro(s) durante o fechamento da OS na integração'));
            $routineScheduleLogError->Insert();

            if ($debug) {
                echo date('d-m-Y H:i');
                echo "\n";
                echo "### ERRO ###\n";
                echo $ex->getMessage()."...\n\n";
            }

        }

    }

} catch (\Exception $ef) {

    if ($debug) {
        echo date('d-m-Y H:i');
        echo "\n";
        echo "### ERRO ###\n";
        echo $ef->getMessage()."...\n\n";
    }

}

if ($debug) {
    echo date('d-m-Y H:i');
    echo "Rotina finalizada.\n";
}

