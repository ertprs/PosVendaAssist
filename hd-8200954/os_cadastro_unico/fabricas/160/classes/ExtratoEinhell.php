<?php

    /* Regras de valores de extrato para a Mondial */

    class RegrasExtrato{

        private $_fabrica;
        private $_classExtrato;
        private $_totalMobraBonificada;
        private $_totalMobraDebito;

        public function __construct($classExtrato, $fabrica = ""){
            $this->_classExtrato         = $classExtrato;
            $this->_fabrica              = $fabrica;
            $this->_totalMobraBonificada = 0;
            $this->_totalMobraDebito     = 0;
        }

        public function run(){
            echo "regras de extrato para a fabrica ".$this->_fabrica." ativa...";
        }

        public function verificaLGR($extrato = "", $posto = "", $data_15 = "", $fabrica = "", $lgr_troca_produto = false){

            if(empty($extrato)){
                $desc_posto = (!empty($posto)) ? "- Posto : {$posto}" : "";
                throw new \Exception("Extrato não informado para a verificação de LGR {$desc_posto}");
            }

            if(empty($posto)){
                throw new \Exception("Posto não informado para a verificação de LGR - Extrato : {$extrato}");
            }

            if(empty($data_15)){
                throw new \Exception("Período de geração não informado para a verificação de LGR - Extrato : {$extrato}");
            }

            if(empty($fabrica)){
                $fabrica = $this->_fabrica;
            }

            $pdo = $this->_classExtrato->_model->getPDO();

            /* 1 */

            if ($lgr_troca_produto == true) {
                 $sql = "UPDATE tbl_faturamento_item SET
                        extrato_devolucao = $extrato
                        FROM tbl_os_item,tbl_faturamento,tbl_extrato, tbl_peca
                        WHERE tbl_os_item.os_item = tbl_faturamento_item.os_item
                        AND tbl_faturamento.posto = tbl_extrato.posto
                        AND tbl_faturamento.faturamento = tbl_faturamento_item.faturamento
                        AND tbl_faturamento.fabrica = $fabrica
                        AND tbl_faturamento.emissao >='2016-01-01'
                        AND tbl_faturamento.emissao <='$data_15'
                        AND tbl_faturamento.cancelada IS NULL
                        AND tbl_faturamento_item.extrato_devolucao IS NULL
                        AND tbl_peca.peca = tbl_os_item.peca
                        AND (tbl_os_item.peca_obrigatoria OR tbl_peca.produto_acabado IS TRUE)
                        AND tbl_os_item.fabrica_i=$fabrica
                        AND (tbl_faturamento.cfop ILIKE '59%' OR tbl_faturamento.cfop ILIKE '69%')
                        AND tbl_extrato.extrato = $extrato";
            } else {
                $sql = "UPDATE tbl_faturamento_item SET
                        extrato_devolucao = $extrato
                        FROM tbl_os_item,tbl_faturamento,tbl_extrato, tbl_peca
                        WHERE tbl_os_item.os_item = tbl_faturamento_item.os_item
                        AND tbl_faturamento.posto = tbl_extrato.posto
                        AND tbl_faturamento.faturamento = tbl_faturamento_item.faturamento
                        AND tbl_faturamento.fabrica = $fabrica
                        AND tbl_faturamento.emissao >='2016-01-01'
                        AND tbl_faturamento.emissao <='$data_15'
                        AND tbl_faturamento.cancelada IS NULL
                        AND tbl_faturamento_item.extrato_devolucao IS NULL
                        AND tbl_os_item.peca_obrigatoria
                        AND tbl_os_item.fabrica_i=$fabrica
                        AND (tbl_faturamento.cfop ILIKE '59%' OR tbl_faturamento.cfop ILIKE '69%')
                        AND tbl_extrato.extrato = $extrato";
            }

            $query  = $pdo->query($sql);

            if(!$query){
                $this->_erro = $pdo->errorInfo();
                throw new \Exception("Erro ao verificar o LGR do extrato {$extrato} - /* SQL 1 */");
            }

            /* 2 */

           $sql = "UPDATE tbl_faturamento SET extrato_devolucao = $extrato
                    FROM tbl_faturamento_item
                    WHERE tbl_faturamento.faturamento = tbl_faturamento_item.faturamento
                    AND tbl_faturamento.posto = $posto
                    AND tbl_faturamento.fabrica = $fabrica
                    AND tbl_faturamento.emissao >='2016-01-01'
                    AND tbl_faturamento.emissao <='$data_15'
                    AND tbl_faturamento_item.extrato_devolucao = $extrato";
            $query  = $pdo->query($sql);

            if(!$query){
                $this->_erro = $pdo->errorInfo();
                throw new \Exception("Erro ao verificar o LGR do extrato {$extrato} - /* SQL 2 */");
            }

            /* 3 */

           $sql = "INSERT INTO tbl_extrato_lgr (extrato, posto, peca, qtde)
                    SELECT
                    tbl_extrato.extrato,
                    tbl_extrato.posto,
                    tbl_faturamento_item.peca,
                    SUM (tbl_faturamento_item.qtde)
                    FROM tbl_extrato
                    JOIN tbl_faturamento_item ON tbl_extrato.extrato = tbl_faturamento_item.extrato_devolucao
                    WHERE tbl_extrato.fabrica = $fabrica
                    AND tbl_extrato.extrato = $extrato
                    GROUP BY tbl_extrato.extrato,
                    tbl_extrato.posto,
                    tbl_faturamento_item.peca";
            $query  = $pdo->query($sql);

            if(!$query){
                $this->_erro = $pdo->errorInfo();
                throw new \Exception("Erro ao verificar o LGR do extrato {$extrato} - /* SQL 3 */");
            }
        }

        public function bonificacaoMO($extrato, $posto) {
            $pdo = $this->_classExtrato->_model->getPDO();

            $sql = "SELECT  tbl_os.os,
                            tbl_os.mao_de_obra,
                            tbl_os_produto.os_produto,
                            tbl_os.data_conserto,
                            (
                                SELECT (tbl_os_item.digitacao_item::date - tbl_os.data_abertura::date) 
                                FROM tbl_os_item 
                                WHERE tbl_os_item.os_produto = tbl_os_produto.os_produto
                                ORDER BY tbl_os_item.digitacao_item DESC
                                LIMIT 1
                            ) as digitacao_item_maior_que_abertura,
                            (
                                SELECT (tbl_os.data_conserto::date - tbl_faturamento.conferencia::date)
                                FROM tbl_faturamento_item
                                JOIN tbl_faturamento ON tbl_faturamento_item.faturamento = tbl_faturamento.faturamento
                                WHERE tbl_faturamento_item.os = tbl_os.os
                                ORDER BY tbl_faturamento.faturamento ASC 
                                LIMIT 1
                            ) as dias_em_conserto,
                            (
                                SELECT os_item
                                FROM tbl_os_item
                                JOIN tbl_servico_realizado ON tbl_os_item.servico_realizado = tbl_servico_realizado.servico_realizado
                                WHERE tbl_os_item.os_produto = tbl_os_produto.os_produto
                                AND tbl_servico_realizado.troca_de_peca IS TRUE
                                LIMIT 1 
                            )  as bonifica_por_ajuste
                    FROM tbl_os
                    INNER JOIN tbl_os_extra   ON tbl_os.os = tbl_os_extra.os
                    INNER JOIN tbl_os_produto ON tbl_os_produto.os = tbl_os.os
                    WHERE tbl_os_extra.extrato = $extrato
                    AND   tbl_os.data_abertura::date > '2018-05-01'::date";

            $query  = $pdo->query($sql);
            
            if(!$query){
                $this->_erro = $pdo->errorInfo();
                throw new \Exception("Erro ao buscar OSs do extrato");
            }

            $res = $query->fetchAll();

            $total_mobra_bonificada = 0;

            foreach ($res as $value) {

                $auditoria_nota_baixa  = false;
                $bonificacao_digitacao = 0;
                $bonificacao_conserto  = 0;
                $bonificacao_nota      = 0;
                $bonificacao_ajuste    = 0;
                $bonificacao_total     = 0;
                $descricao_avulso      = "M.O.";

                $mao_de_obra            = $value["mao_de_obra"];
                $os                     = $value["os"];
                $bonifica_por_digitacao = $value["digitacao_item_maior_que_abertura"];
                $dias_em_conserto       = $value["dias_em_conserto"];
                $bonifica_por_ajuste    = $value["bonifica_por_ajuste"];


                if (!empty($bonifica_por_ajuste)) {
                    //Caso o posto tenha digitado todas as peças num intervalo menor que 1 dia: 10%
                    if ($bonifica_por_digitacao <= 1 && is_numeric($bonifica_por_digitacao)) {
                        $bonificacao_digitacao += 10;

                        $descricao_avulso .= " + 10% (Itens digitados em 1 dia)";
                    }

                    if ($dias_em_conserto <= 3 && is_numeric($dias_em_conserto)) {
                        $bonificacao_conserto += 10;

                        $descricao_avulso .= " + 10% (Produto consertado em menos de 3 dias)";
                    }
                }

                $sqlNotaConsumidor = "SELECT tbl_sms_resposta.resposta 
                                      FROM tbl_sms 
                                      JOIN tbl_sms_resposta ON tbl_sms.sms = tbl_sms_resposta.sms
                                      WHERE tbl_sms.os = {$os}
                                      AND tbl_sms.texto_sms ILIKE '%PROD.ENTREGUE%'
                                      ORDER BY tbl_sms_resposta.data DESC
                                      LIMIT 1";
                $resNotaConsumidor = $pdo->query($sqlNotaConsumidor);

                if(!$resNotaConsumidor){
                    $this->_erro = $pdo->errorInfo();
                    throw new \Exception("Erro ao buscar nota do consumidor");
                }

                $resNota           = $resNotaConsumidor->fetch(\PDO::FETCH_ASSOC);

                $notaConsumidor    = $resNota['resposta'];
                
                if (!empty($notaConsumidor) && is_numeric($notaConsumidor)) {
                    
                    if ($notaConsumidor >= 5) {
                        $bonificacao_nota += 10;
                        $descricao_avulso .= " + 10% (Avaliação positiva do consumidor)";
                    } else if ($notaConsumidor <= 2) {
                        $auditoria_nota_baixa = true;
                    }

                }

                $bonificacao_total = $bonificacao_nota + $bonificacao_conserto + $bonificacao_digitacao + $bonificacao_ajuste;  
                
                $sqlValoresAdicionais = "SELECT valores_adicionais 
                                         FROM tbl_os_campo_extra
                                         WHERE os = $os";
                $resValoresAdicionais = $pdo->query($sqlValoresAdicionais);

                if(!$resValoresAdicionais){
                    $this->_erro = $pdo->errorInfo();
                    throw new \Exception("Erro ao buscar nota do consumidor");
                }

                $resValores           = $resValoresAdicionais->fetch(\PDO::FETCH_ASSOC);

                $obj_valores_adicionais = json_decode($resValores["valores_adicionais"]);

                $obj_valores_adicionais->bonificacao_digitacao_item  = $bonificacao_digitacao;
                $obj_valores_adicionais->nota_consumidor             = $notaConsumidor;
                $obj_valores_adicionais->bonificacao_nota_consumidor = $bonificacao_nota;
                $obj_valores_adicionais->bonificacao_tempo_conserto  = $bonificacao_conserto;
                $obj_valores_adicionais->bonificacao_ajuste          = $bonificacao_ajuste;
                $obj_valores_adicionais->bonificacao_total           = $bonificacao_total;

                $json_valores_adicionais = json_encode($obj_valores_adicionais);

                $sqlUpdateValores = "UPDATE tbl_os_campo_extra SET valores_adicionais = '$json_valores_adicionais' WHERE os = $os";
                $resUpdate = $pdo->query($sqlUpdateValores);

                if(!$resUpdate){
                    $this->_erro = $pdo->errorInfo();
                    throw new \Exception("Erro ao adicionar os valores adicionais");
                }

                if ($bonificacao_total > 0) {

                    if ($auditoria_nota_baixa) {
                        $sqlAuditoria = "INSERT INTO tbl_auditoria_os (os,auditoria_status,observacao) VALUES ($os, 6, 'Auditoria de Aprovação da Bonificação')";
                        $resAuditoria = $pdo->query($sqlAuditoria);

                        if(!$resAuditoria){
                            $this->_erro = $pdo->errorInfo();
                            throw new \Exception("Erro ao inserir auditoria");
                        }

                        //Retira o extrato do avulso até aprovar ou reprovar a auditoria
                        $extrato_lancamento = 'null';
                    } else {
                        $extrato_lancamento = $extrato;
                    }

                    $mao_de_obra_bonificada = (($bonificacao_total / 100) * $mao_de_obra);

                    $id_lancamento_credito = $this->getLancamento('C');

                    $sqlAvulso = "INSERT INTO tbl_extrato_lancamento (posto,fabrica,extrato,os,lancamento,valor,descricao,automatico,historico,debito_credito) 
                    VALUES ($posto,$this->_fabrica,$extrato_lancamento,$os,$id_lancamento_credito,$mao_de_obra_bonificada,'bonificacao','t','$descricao_avulso','C')";
                    $resAvulso = $pdo->query($sqlAvulso);

                    if(!$resAvulso){
                        $this->_erro = $pdo->errorInfo();
                        throw new \Exception("Erro ao inserir avulso");
                    }
                    if (!$auditoria_nota_baixa) {
                        $total_mobra_bonificada += $mao_de_obra_bonificada; 
                    }
                }
            }

            $this->setMobraBonificada($total_mobra_bonificada);
        }

        public function osComRespostaSemAvulso($posto,$extrato) {
            $pdo = $this->_classExtrato->_model->getPDO();

            $sql = "SELECT DISTINCT ON (tbl_os.os) 
                        tbl_os.os,
                        tbl_os.mao_de_obra,
                        tbl_sms_resposta.resposta,
                        tbl_sms.sms,
                        tbl_auditoria_os.auditoria_os AS passou_por_auditoria
                    FROM tbl_os
                    JOIN tbl_sms ON tbl_sms.os = tbl_os.os
                    JOIN tbl_sms_resposta ON tbl_sms_resposta.sms = tbl_sms.sms
                    JOIN tbl_os_extra ON tbl_os.os = tbl_os_extra.os
                    JOIN tbl_extrato ON tbl_extrato.extrato = tbl_os_extra.extrato
                    LEFT JOIN tbl_auditoria_os ON tbl_auditoria_os.os = tbl_os.os
                    AND TO_ASCII(tbl_auditoria_os.observacao, 'LATIN-9') = 'Auditoria de Aprovacao da Bonificacao'
                    WHERE tbl_os_extra.extrato IS NOT NULL
                    AND tbl_os_extra.extrato < $extrato
                    AND tbl_sms_resposta.data > tbl_extrato.data_geracao
                    AND tbl_os.posto = $posto
                    AND tbl_os.fabrica = $this->_fabrica
                    AND tbl_os.data_abertura::date > '2018-05-01'::date
                    AND tbl_auditoria_os.auditoria_os IS NULL
                    AND (
                        SELECT extrato_lancamento 
                        FROM tbl_extrato_lancamento
                        WHERE tbl_extrato_lancamento.os = tbl_os.os
                        AND TO_ASCII(tbl_extrato_lancamento.historico, 'LATIN-9') ILIKE '%Avaliacao positiva do consumidor apos geracao%'
                        LIMIT 1
                    ) IS NULL
                    ";
            $query = $pdo->query($sql);

            if (!$query) {
                $this->_erro = $pdo->errorInfo();
                throw new \Exception("Erro ao buscar OSs");
            }

            $res = $query->fetchAll();

            $total_mobra_bonificada = 0;
            $total_mobra_debito     = 0;

            if (count($res) > 0) {
                foreach ($res as $value) {
                    $os                     = $value['os'];
                    $nota                   = (int) $value['resposta'];
                    $mao_de_obra            = $value['mao_de_obra']; 
                    $bonificacao            = 0;
                    $auditoria_nota_baixa   = false;

                    if ($nota == 5) {
                        $bonificacao = 10;
                        $descricao_avulso = "M.O. + 10% (Avaliação positiva do consumidor após geração do extrato)";
                    } else if ($nota <= 2) {
                        $auditoria_nota_baixa = true;
                        $descricao_avulso = "Bonificação aguardando auditoria (Avaliação negativa do consumidor após geração do extrato)";
                    }

                    if (!$auditoria_nota_baixa && $bonificacao > 0) {
                        $mao_de_obra_bonificada = ($bonificacao / 100) * $mao_de_obra;
                                                                                
                        $id_lancamento_credito = $this->getLancamento('C');

                        $sqlAvulso = "INSERT INTO tbl_extrato_lancamento (posto,fabrica,extrato,os,lancamento,valor,descricao,automatico,historico,debito_credito) VALUES ($posto,$this->_fabrica,$extrato,$os,'$id_lancamento_credito',$mao_de_obra_bonificada,'bonificacao','t','$descricao_avulso','C')";
                        $resAvulso = $pdo->query($sqlAvulso);

                        if (!$resAvulso) {
                            $this->_erro = $pdo->errorInfo();
                            throw new \Exception("Erro ao lançar credito avulso");
                        }

                        $total_mobra_bonificada += $mao_de_obra_bonificada;

                    } else if ($auditoria_nota_baixa) {
                        $sqlBonificacaoAnterior = "SELECT 
                                                    json_field('bonificacao_total', valores_adicionais::text) as bonificacao_anterior 
                                                 FROM tbl_os_campo_extra
                                                 WHERE os = $os";

                        $resBonificacaoAnterior = $pdo->query($sqlBonificacaoAnterior);
                        $resValor               = $resBonificacaoAnterior->fetch(\PDO::FETCH_ASSOC);

                        $bonificacao_anterior = $resValor['bonificacao_anterior']; 

                        $mao_de_obra_debito    = ($bonificacao_anterior / 100) * $mao_de_obra;

                        $id_lancamento_debito  = $this->getLancamento('D');

                        $sqlAuditoria = "INSERT INTO tbl_auditoria_os (os,auditoria_status,observacao) VALUES ($os, 6, 'Auditoria de Aprovação da Bonificação')";
                        $resAuditoria = $pdo->query($sqlAuditoria);

                        if(!$resAuditoria){
                            $this->_erro = $pdo->errorInfo();
                            throw new \Exception("Erro ao inserir auditoria");
                        }
                        
                        $sqlAvulso = "INSERT INTO tbl_extrato_lancamento (posto,fabrica,extrato,os,lancamento,valor,descricao,automatico,historico,debito_credito) VALUES ($posto,$this->_fabrica,$extrato,$os,'$id_lancamento_debito',$mao_de_obra_debito,'bonificacao','t','$descricao_avulso','D')";
                        $resAvulso = $pdo->query($sqlAvulso);

                        if (!$resAvulso) {
                            $this->_erro = $pdo->errorInfo();
                            throw new \Exception("Erro ao lançar debito avulso");
                        }

                        //$total_mobra_debito += $mao_de_obra_debito;

                    }
                }

                $this->setMobraBonificada($total_mobra_bonificada);
                $this->setMobraDebito($total_mobra_debito);
            }
        }

        public function getLancamento($tipo) {
            $pdo = $this->_classExtrato->_model->getPDO();

            $sql = "SELECT lancamento 
                     FROM tbl_lancamento 
                     WHERE fabrica = {$this->_fabrica}
                     AND debito_credito = '{$tipo}'
                     LIMIT 1";
            $res = $pdo->query($sql);
            $resLancamento  = $res->fetch(\PDO::FETCH_ASSOC);

            return $resLancamento['lancamento'];
        }

        public function setMobraDebito($debito) {
            $this->_totalMobraDebito += $debito;
        }

        public function setMobraBonificada($mobra) {
            $this->_totalMobraBonificada += $mobra;
        }

        public function getMobraBonificada() {
            return $this->_totalMobraBonificada;
        }

        public function getMobraDebito() {
            return $this->_totalMobraDebito;
        }

    }
    

?>
