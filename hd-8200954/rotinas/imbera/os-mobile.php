<?php

require dirname(__FILE__) . '/../../dbconfig.php';
require dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
require __DIR__ . '/./funcoes.php';

include_once dirname(__FILE__) . "/../../class/aws/s3_config.php";
include_once S3CLASS;

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

                    $nova_peca_troca      = false;
                    $nova_peca_critica    = false;
                    $novo_servico_estoque = false;

                    $continue_foreach = false;

                    foreach ($dados["pecas"] as $peca) {
                        try { // $ewfp
                            $sqlPeca = "SELECT peca, peca_critica FROM tbl_peca WHERE referencia = '{$peca["referencia"]}' AND fabrica = {$login_fabrica}";
                            $qryPeca = $pdo->query($sqlPeca);
                            $resPeca = $qryPeca->fetch();

                            $p = $resPeca["peca"];
                            $critica = $resPeca["peca_critica"];

                            if (empty($p)) {
                                throw new \Exception("Peça não encontrada - {$peca["referencia"]} #{$fetch["os_mobile"]}");
                            }

                            $sqlOsI = "SELECT os_item FROM tbl_os_item WHERE peca = {$p} AND os_produto = {$os_produto}";
                            $qryOsI = $pdo->query($sqlOsI);

                            $qtde    = $peca["qtde"];
                            $os_item = null;

                            if (array_key_exists("qtd", $peca) && strlen($qtde) == 0) {
                                $qtde = $peca["qtd"];
                            }

                            if (empty($qtde)) {
                                throw new \Exception("Peça sem Qtde. - {$peca["referencia"]} #{$fetch["os_mobile"]}");
                            }

                            if ($qryOsI->rowCount() == 0) {
                                $acao = "
                                    INSERT INTO tbl_os_item (os_produto, peca, qtde, servico_realizado)
                                    VALUES ({$os_produto}, {$p}, {$qtde}, {$peca["servicoRealizado"]})
                                    RETURNING os_item;
                                ";

                                if ($debug) {
        echo date('d-m-Y H:i');
        echo "\n";
                                    echo "Inserido peça {$p}...\n";
                                }
                            } else {
                                $resOsI = $qryOsI->fetch();
                $os_item = $resOsI["os_item"];

                if (!empty($os_item)) {
                    if ($osClass->verificaPedido($os_item)) {
                        continue;
                    }
                }

                                $acao = "
                                    UPDATE tbl_os_item
                                    SET qtde = {$qtde}, servico_realizado = {$peca["servicoRealizado"]}
                                    WHERE os_item = {$os_item};
                                ";

                                if ($debug) {
        echo date('d-m-Y H:i');
        echo "\n";
                                    echo "Atualizado dados OS Item {$os_item}...\n";
                                }
                            }

                            $qryAcao = $pdo->query($acao);

                            if (!$qryAcao) {
                                throw new \Exception("Erro na Inserção/Atualização das peças na OS - {$peca["referencia"]} #{$fetch["os_mobile"]}");
                            }

                            if (empty($os_item)) {
                                $resAcao = $qryAcao->fetch();
                                $os_item = $resAcao['os_item'];
                }

                            if (verifica_servico_realizado($peca["servicoRealizado"])) {
                                $servicoUsaEstoque = $osClass->verificaServicoUsaEstoque($peca["servicoRealizado"]);
                                $estoquePosto = $osClass->verificaEstoquePosto($posto, $p, $qtde);  

                                if ($servicoUsaEstoque) {
                                    if ($estoquePosto) {
                                        $l = $osClass->lancaMovimentoEstoque(
                                            $posto,
                                            $p,
                                            $qtde,
                                            $os,
                                            $os_item,
                                            "",
                                            date('Y-m-d'),
                                            'saida'
                                        );
                                    } else {
                                        altera_servico_realizado(
                                            $pdo,
                                            $os_item, 
                                            "gera_pedido", 
                                            $os,
                                            function($login_fabrica, $pdo, $os, $os_item, $sr) {
                                                $cockpit = new Cockpit($login_fabrica);

                                                //busca id externo
                                                $os_id_externo = $cockpit->getOsIdExterno($os);

                                                //busca serviços
                                                $servicos = $cockpit->getServicosOs($os_id_externo);

                                                $servico_estoque = null;
                                                $servico_pedido  = null;

                                                foreach ($servicos as $servico) {
                                                    if (utf8_decode($servico["servico"]["nome"]) == "Troca de Peça (estoque)") {
                                                        $servico_estoque = $servico["servico"]["id"];
                                                    } else if (utf8_decode($servico["servico"]["nome"]) == "Troca de Peça (gera pedido)") {
                                                        $servico_pedido = $servico["servico"]["id"];
                                                    }
                                                }

                                                //verifica se existe o serviço gera pedido se não existir grava
                                                if (is_null($servico_pedido)) {
                                                    $servico        = $cockpit->getServico($sr["servico_realizado"]);
                                                    $servico_pedido = $servico["id"];

                                                    $cockpit->vincularServicoOs($os_id_externo, $servico_pedido);
                                                }

                                                //busca id do material
                                                $sql = "
                                                    SELECT p.referencia
                                                    FROM tbl_os_item osi
                                                    INNER JOIN tbl_peca p ON p.peca = osi.peca AND p.fabrica = {$login_fabrica}
                                                    WHERE osi.os_item = {$os_item}
                                                ";
                                                $qry = $pdo->query($sql);

                                                if (!$qry || $qry->rowCount() == 0) {
                                                    throw new \Exception("Erro ao buscar informações da peça");
                                                }

                                                $res = $qry->fetch();

                                                $peca_referencia = $res["referencia"];
                                                $peca            = $cockpit->getPecaIdExterno($peca_referencia);

                                                //amarrar material com o serviço
                                                $res = $cockpit->vincularPecaServico($os_id_externo, $peca["id"], $servico_pedido);

                                                if (empty($res) || utf8_decode($res["error"]["message"]) != "Informação já cadastrada") {
                                                    throw new \Exception("Erro ao vincular Serviço a Peça da Ordem de Serviço");
                                                } else if (utf8_decode($res["error"]["message"]) == "Informação já cadastrada") {
                                                    //se der erro de informação já cadastrada ativar a amarração
                                                    $cockpit->statusVinculoPecaServico($os_id_externo, $peca["id"], $servico_pedido, 1);
                                                }
                                                
                                                if (!is_null($servico_estoque)) {
                                                    //inativar amrração com o serviço antigo
                                                    $cockpit->statusVinculoPecaServico($os_id_externo, $peca["id"], $servico_estoque, 0);
                                                }
                                            }
                                        );
                                    }
                                } else {
                                    if ($estoquePosto) {
                                        $novo_servico_estoque = true;

                                        altera_servico_realizado(
                                            $pdo,
                                            $os_item, 
                                            "estoque",
                                            $os,
                                            function($login_fabrica, $pdo, $os, $os_item, $sr) {
                                                $cockpit = new Cockpit($login_fabrica);

                                                //busca id externo
                                                $os_id_externo = $cockpit->getOsIdExterno($os);

                                                //busca serviços
                                                $servicos = $cockpit->getServicosOs($os_id_externo);

                                                $servico_estoque = null;
                                                $servico_pedido  = null;

                                                foreach ($servicos as $servico) {
                                                    if (utf8_decode($servico["servico"]["nome"]) == "Troca de Peça (estoque)") {
                                                        $servico_estoque = $servico["servico"]["id"];
                                                    } else if (utf8_decode($servico["servico"]["nome"]) == "Troca de Peça (gera pedido)") {
                                                        $servico_pedido = $servico["servico"]["id"];
                                                    }
                                                }

                                                //verifica se existe o serviço gera pedido se não existir grava
                                                if (is_null($servico_estoque)) {
                                                    $servico        = $cockpit->getServico($sr["servico_realizado"]);
                                                    $servico_estoque = $servico["id"];

                                                    $cockpit->vincularServicoOs($os_id_externo, $servico_estoque);
                                                }

                                                //busca id do material
                                                $sql = "
                                                    SELECT p.referencia
                                                    FROM tbl_os_item osi
                                                    INNER JOIN tbl_peca p ON p.peca = osi.peca AND p.fabrica = {$login_fabrica}
                                                    WHERE osi.os_item = {$os_item}
                                                ";
                                                $qry = $pdo->query($sql);

                                                if (!$qry || $qry->rowCount() == 0) {
                                                    throw new \Exception("Erro ao buscar informações da peça");
                                                }

                                                $res = $qry->fetch();

                                                $peca_referencia = $res["referencia"];
                                                $peca            = $cockpit->getPecaIdExterno($peca_referencia);

                                                //amarrar material com o serviço
                                                $res = $cockpit->vincularPecaServico($os_id_externo, $peca["id"], $servico_estoque);

                                                if (empty($res) || utf8_decode($res["error"]["message"]) != "Informação já cadastrada") {
                                                    throw new \Exception("Erro ao vincular Serviço a Peça da Ordem de Serviço");
                                                } else if (utf8_decode($res["error"]["message"]) == "Informação já cadastrada") {
                                                    //se der erro de informação já cadastrada ativar a amarração
                                                    $cockpit->statusVinculoPecaServico($os_id_externo, $peca["id"], $servico_estoque, 1);
                                                }
                                                
                                                if (!is_null($servico_pedido)) {
                                                    //inativar amrração com o serviço antigo
                                                    $cockpit->statusVinculoPecaServico($os_id_externo, $peca["id"], $servico_pedido, 0);
                                                }
                                            }
                                        );

                                        $osClass->lancaMovimentoEstoque(
                                            $posto,
                                            $p,
                                            $qtde,
                                            $os,
                                            $os_item,
                                            "",
                                            date('Y-m-d'),
                                            'saida'
                                        );
                                    }
                                }
                            }

                            $nova_peca_troca = true;

                            if ($critica == "t") {
                                $nova_peca_critica = true;
                            }
                        } catch (\Exception $ewfp) {
                            if ($debug) {
        echo date('d-m-Y H:i');
        echo "\n";
                                echo "\n";
                                echo "### ERRO ###\n";
                                echo $ewfp->getMessage()."...\n\n";
                            }

                            $erro_validacao[] = $ewfp->getMessage();

                            $continue_foreach = true;

                            break;
                        }
                    }

                    if ($novo_servico_estoque == true) {
                        $sqlPecasPedido = "
                            SELECT osi.os_item
                            FROM tbl_os_item osi
                            INNER JOIN tbl_os_produto osp ON osp.os_produto = osi.os_produto
                            INNER JOIN tbl_servico_realizado sr ON sr.servico_realizado = osi.servico_realizado AND sr.fabrica = {$login_fabrica}
                            WHERE osp.os = {$os}
                            AND sr.gera_pedido IS TRUE
                            AND sr.troca_de_peca IS TRUE
                        ";
                        $resPecasPedido = pg_query($con, $sqlPecasPedido);

                        if (pg_num_rows($resPecasPedido) == 0) {
                            $sqlServicoRealizado = "
                                SELECT servico_realizado
                                FROM tbl_servico_realizado
                                WHERE fabrica = {$login_fabrica}
                                AND gera_pedido IS TRUE
                                AND troca_de_peca IS TRUE
                            ";
                            $resServicoRealizado = pg_query($con, $sqlServicoRealizado);

                            if (pg_num_rows($resServicoRealizado) > 0) {
                                $servico_realizado_pedido = pg_fetch_result($resServicoRealizado, 0, "servico_realizado");

                                $cockpit->cancelaServico($os, $servico_realizado);
                            }
                        }
                    }

                    if ($continue_foreach == true) {
                        throw new \Exception("Erro ao gravar peças #{$fetch["os_mobile"]}");
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
        AND tbl_os_mobile.status_os_mobile = '{$status}'
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

