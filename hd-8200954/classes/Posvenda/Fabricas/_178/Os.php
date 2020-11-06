<?php

namespace Posvenda\Fabricas\_178;

use Posvenda\Os as OsPosvenda;

class Os extends OsPosvenda
{

    public $_erro;
    protected $_fabrica;

    public function __construct($fabrica, $os)
    {
        $this->_fabrica = $fabrica;
        parent::__construct($fabrica, $os);
    }

    public function getOsGarantiaRoca()
    {
        $pdo = $this->_model->getPDO();

        $sql = "
            SELECT DISTINCT
		        tbl_os_revenda.posto,
                tbl_os_revenda.os_revenda,
                tbl_os_revenda_item.marca,
                tbl_produto.linha
            FROM tbl_os_revenda
            JOIN tbl_os_revenda_item USING(os_revenda)
            JOIN tbl_produto USING(produto)
            LEFT JOIN tbl_auditoria_os_revenda ON tbl_auditoria_os_revenda.os_revenda = tbl_os_revenda.os_revenda and tbl_auditoria_os_revenda.auditoria_status <> 2
            WHERE tbl_os_revenda.fabrica = {$this->_fabrica}
            AND tbl_os_revenda.explodida IS NOT NULL
            AND tbl_os_revenda.posto <> 6359
            AND tbl_os_revenda.finalizada IS NULL
            AND tbl_os_revenda.excluida IS NOT TRUE
            AND (tbl_auditoria_os_revenda.liberada IS NOT NULL OR tbl_auditoria_os_revenda.auditoria_os IS NULL)
            AND tbl_os_revenda_item.marca IS NOT NULL;
        ";
        $query  = $pdo->query($sql);
        return $query->fetchAll(\PDO::FETCH_ASSOC);
    }
    
    public function getOsTrocaGarantiaRoca()
    {
        $pdo = $this->_model->getPDO();

        $sql = "
            SELECT DISTINCT
                tbl_os_revenda.posto,
                tbl_os_revenda.os_revenda,
                tbl_os_campo_extra.marca,
                tbl_produto.linha
            FROM tbl_os_revenda
            JOIN tbl_os_revenda_item USING(os_revenda)
            JOIN tbl_os_campo_extra ON tbl_os_campo_extra.os_revenda = tbl_os_revenda.os_revenda AND tbl_os_campo_extra.os_revenda_item = tbl_os_revenda_item.os_revenda_item
            JOIN tbl_os ON tbl_os.os = tbl_os_campo_extra.os AND tbl_os.fabrica = {$this->_fabrica}
            JOIN tbl_produto ON tbl_os.produto = tbl_produto.produto
            LEFT JOIN tbl_auditoria_os_revenda ON tbl_auditoria_os_revenda.os_revenda = tbl_os_revenda.os_revenda and tbl_auditoria_os_revenda.auditoria_status <> 2
            WHERE tbl_os_revenda.fabrica = {$this->_fabrica}
            AND tbl_os_revenda.explodida IS NOT NULL
            AND tbl_os_revenda.posto <> 6359
            AND tbl_os_revenda.finalizada IS NULL
            AND tbl_os_revenda.excluida IS NOT TRUE
            AND (tbl_auditoria_os_revenda.liberada IS NOT NULL OR tbl_auditoria_os_revenda.auditoria_os IS NULL)
            AND tbl_os_campo_extra.marca IS NOT NULL
            AND tbl_os_campo_extra.fabrica = {$this->_fabrica} ";
        $query  = $pdo->query($sql);
       
        return $query->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getItensOsGarantiaRoca()
    {
        $pdo = $this->_model->getPDO();

        $sql = "
            WITH os_revenda AS(
                SELECT
                    tbl_os_revenda.os_revenda,
                    tbl_os_revenda_item.marca
                FROM tbl_os_revenda
                JOIN tbl_os_revenda_item USING(os_revenda)
                LEFT JOIN tbl_auditoria_os_revenda USING (os_revenda)
                WHERE tbl_os_revenda.fabrica = {$this->_fabrica}
                AND tbl_os_revenda.explodida IS NOT NULL
                AND tbl_os_revenda.finalizada IS NULL
                AND tbl_os_revenda.excluida IS FALSE
                AND (tbl_auditoria_os_revenda.liberada IS NOT NULL OR tbl_auditoria_os_revenda.auditoria_os IS NULL)
            ),
            itens_os AS(
                SELECT 
                    os_revenda.os_revenda,
                    os_revenda.marca,
                    tbl_os_campo_extra.os,
                    tbl_os_item.os_item,
                    tbl_os_item.peca,
                    tbl_os_item.qtde
                FROM tbl_os_campo_extra
                JOIN os_revenda USING(os_revenda)
                JOIN tbl_os_produto USING(os)
                JOIN tbl_os_item USING(os_produto)
                JOIN tbl_servico_realizado USING(servico_realizado)
                WHERE tbl_os_campo_extra.fabrica = {$this->_fabrica}
                AND tbl_os_item.pedido IS NULL
                AND tbl_servico_realizado.fabrica = {$this->_fabrica}
                AND tbl_servico_realizado.troca_de_peca IS TRUE
                AND tbl_servico_realizado.gera_pedido IS TRUE
            )

            SELECT * FROM itens_os;
        ";
                
        $query  = $pdo->query($sql);
        return $query->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function verificaOsRevendaIntervencao($os, $os_principal = 'f')
    {
        if (!empty($os)) {
            $pdo = $this->_model->getPDO();
            $bloqueiaFechamento = false;

            if ($os_principal === 'f') {
                $sqlOsRevenda = "SELECT os_revenda FROM tbl_os_campo_extra WHERE os = {$os} AND fabrica = {$this->_fabrica};";
                $queryOsRevenda = $pdo->query($sqlOsRevenda);
                $resOsRevenda = $queryOsRevenda->fetch(\PDO::FETCH_ASSOC);

                $os_revenda = $resOsRevenda['os_revenda'];
            } else {
                $os_revenda = $os;
            }

            if (!empty($os_revenda)) {
                $sql = "
                    SELECT
                        liberada,
                        cancelada
                    FROM tbl_auditoria_os_revenda
                    WHERE os_revenda = {$os_revenda};
                ";

                $query = $pdo->query($sql);
                $intervencoes = $query->fetchAll(\PDO::FETCH_ASSOC);

                if (count($intervencoes) > 0) {
                    foreach($intervencoes as $intervencao) {
                        if (empty($intervencao['liberada']) && empty($intervencao['cancelada'])) {
                            $bloqueiaFechamento = true;
                        }
                    }
                }
            }
        }

        return $bloqueiaFechamento;
    }

    public function validaInformacoesOs($os, $sua_os, $data_fechamento, $tipo_revenda = 'f', $os_produto = true)
    {
        $pdo = $this->_model->getPDO();
        
        if (!empty($os)) {
            $sql = "
                SELECT 
                    ta.tipo_atendimento,
                    os.nota_fiscal,
		    os.consumidor_revenda,
                    ta.descricao,
                    ta.km_google
                FROM tbl_os os
                JOIN tbl_tipo_atendimento ta ON ta.tipo_atendimento = os.tipo_atendimento AND ta.fabrica = $this->_fabrica
                WHERE os.os = $os
                AND os.fabrica = $this->_fabrica";
            $query = $pdo->query($sql);
            $result_query = $query->fetch(\PDO::FETCH_ASSOC);

            $tipo_atendimento = $result_query["tipo_atendimento"];
            $nota_fiscal = $result_query["nota_fiscal"];
            $km_google = $result_query["km_google"];
	    $consumidor_revenda = $result_query["consumidor_revenda"];

            if ($km_google == "t" && empty($nota_fiscal) && !in_array($consumidor_revenda, ['R','S'])) {
                throw new \Exception(traduz("OS {$sua_os} sem número de Nota Fiscal, favor preencher o número da Nota Fiscal"));
            }

            $sql = "SELECT os_revenda FROM tbl_os_campo_extra WHERE os = $os AND fabrica = $this->_fabrica";
            $query = $pdo->query($sql);
            $id_os_revenda = $query->fetch(\PDO::FETCH_ASSOC);

            if (!empty($id_os_revenda["os_revenda"])){
                $sql = "
                    SELECT 
                        tbl_tecnico_agenda.tecnico_agenda, 
                        tbl_tecnico_agenda.data_cancelado, 
                        tbl_tecnico_agenda.confirmado
                    FROM tbl_os_revenda 
                    LEFT JOIN tbl_tecnico_agenda ON tbl_tecnico_agenda.os_revenda = tbl_os_revenda.os_revenda AND tbl_tecnico_agenda.fabrica = $this->_fabrica
                    WHERE tbl_os_revenda.os_revenda = {$id_os_revenda['os_revenda']}
                    AND tbl_os_revenda.fabrica = $this->_fabrica
                    ORDER BY tecnico_agenda DESC LIMIT 1";
                $query = $pdo->query($sql);

                $tecnico_agenda = $query->fetch(\PDO::FETCH_ASSOC);

                if (empty($tecnico_agenda['tecnico_agenda']) AND $km_google == "t"){
                    throw new \Exception(traduz("OS {$sua_os} pendente de agendamento"));
                }

                if (empty($tecnico_agenda['data_cancelado']) AND empty($tecnico_agenda['confirmado']) AND $km_google == "t"){
                    throw new \Exception(traduz("OS {$sua_os} com visita pendente de confirmação"));
                }
            }

            $sql = "SELECT data_conserto, tipo_atendimento, defeito_constatado, defeito_constatado_grupo FROM tbl_os WHERE os = {$os} AND fabrica = {$this->_fabrica};";
            $query = $pdo->query($sql);
            $array_dados = $query->fetch(\PDO::FETCH_ASSOC);

            $data_conserto_bd = $array_dados['data_conserto'];

            if ($tipo_revenda == "f"){
                if (empty($array_dados['tipo_atendimento'])){
                    throw new \Exception(traduz("OS {$sua_os} sem tipo de atendimento"));
                }

                if (empty($array_dados['defeito_constatado_grupo']) and empty($array_dados['defeito_constatado'])){
                    throw new \Exception(traduz("OS {$sua_os} sem defeito constatado"));
                }
            }
            
            if ($data_fechamento > date("Y-m-d")) {
                if ($os_produto === false) {
                    return traduz("Data de fechamento da OS {$sua_os} não pode ser maior que a atual");
                } else {
                    throw new \Exception(traduz("Data de fechamento da OS {$sua_os} não pode ser maior que a atual"));
                }
            }
            
            if (!empty($data_conserto_bd) && $tipo_revenda == 'f') {
                if (strtotime($data_fechamento.'23:59:59') < strtotime($data_conserto_bd)) {
                    if ($os_produto === false) {
                        return traduz("Data de fechamento da OS {$sua_os} não pode ser anterior à data de conserto");
                    } else {
                        throw new \Exception(traduz("Data de fechamento da OS {$sua_os} não pode ser anterior à data de conserto"));
                    }
                }
            } else if ($tipo_revenda == 'f') {
                if ($os_produto === false) {
                    return traduz("É necessário informar uma data de conserto para a OS {$sua_os}");
                } else {
                    throw new \Exception(traduz("É necessário informar uma data de conserto para a OS {$sua_os}"));
                }
            }
        }

        return false;
    }

    public function finalizaAtendimento($hd_chamado)
    {
        if (empty($hd_chamado)) {
            return false;
        }

        $pdo = $this->_model->getPDO();
        $comentario = 'A OS ' . $this->_os . ' aberta para este atendimento foi finalizada.';

        $sql = "INSERT INTO tbl_hd_chamado_item (
            hd_chamado,
            data,
            comentario,
            status_item
        ) VALUES (
            $hd_chamado,
            CURRENT_TIMESTAMP,
            '$comentario',
            'Resolvido'
        )";

        $query = $pdo->query($sql);

        if (empty($query)) {
            return false;
        }

        $sql = "UPDATE tbl_hd_chamado SET status = 'Resolvido' WHERE hd_chamado = $hd_chamado";
        $query = $pdo->query($sql);

        if (empty($query)) {
            return false;
        }

        return true;
    }

    public function getPecasPedidoGarantia($os_revenda, $marca)
    {
        $pdo = $this->_model->getPDO();

        $sql = "SELECT  tbl_os_campo_extra.os,
                        tbl_os_item.os_item,
                        tbl_os_item.peca,
                        tbl_os_item.qtde
                FROM tbl_os_campo_extra
                JOIN tbl_os_produto USING(os)
                JOIN tbl_os_item USING(os_produto)
                JOIN tbl_servico_realizado USING(servico_realizado)
                LEFT JOIN tbl_auditoria_os ON tbl_os_campo_extra.os = tbl_auditoria_os.os AND tbl_auditoria_os.liberada IS NULL AND tbl_auditoria_os.bloqueio_pedido IS TRUE
                WHERE tbl_os_campo_extra.fabrica = {$this->_fabrica}
                AND tbl_os_campo_extra.os_revenda = {$os_revenda}
                AND tbl_os_campo_extra.marca = {$marca}
                AND tbl_os_item.pedido IS NULL
                AND tbl_servico_realizado.fabrica = {$this->_fabrica}
                AND tbl_servico_realizado.troca_de_peca IS TRUE
                AND tbl_servico_realizado.gera_pedido IS TRUE
                AND tbl_auditoria_os.os IS NULL";
                
        $query  = $pdo->query($sql);
        return $query->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getPecasPedidoTrocaGarantia($os_revenda, $marca)
    {
        $pdo = $this->_model->getPDO();

        $sql = "SELECT DISTINCT tbl_os_campo_extra.os,
                        tbl_os_item.os_item,
                        tbl_os_item.peca,
                        tbl_os_item.qtde
                FROM tbl_os_campo_extra
                JOIN tbl_os_produto USING(os)
                JOIN tbl_os_item USING(os_produto)
                JOIN tbl_servico_realizado USING(servico_realizado)
                JOIN tbl_peca ON tbl_peca.peca = tbl_os_item.peca AND tbl_peca.fabrica = {$this->_fabrica}
                LEFT JOIN tbl_auditoria_os ON tbl_os_campo_extra.os = tbl_auditoria_os.os 
                JOIN tbl_os ON tbl_os.os = tbl_os_campo_extra.os AND tbl_os.fabrica = {$this->_fabrica}
                LEFT JOIN tbl_os_troca ON tbl_os.os = tbl_os_troca.os
                WHERE tbl_os_campo_extra.fabrica = {$this->_fabrica}
                AND tbl_os_campo_extra.os_revenda = {$os_revenda}
                AND tbl_os_campo_extra.marca = {$marca}
                AND tbl_os_item.pedido IS NULL
                AND tbl_servico_realizado.fabrica = {$this->_fabrica}
                AND tbl_servico_realizado.troca_produto IS TRUE
                AND ( tbl_os_troca.gerar_pedido or tbl_os_troca.os isnull) 
                --AND tbl_peca.produto_acabado IS TRUE
                AND tbl_servico_realizado.gera_pedido IS TRUE
                AND tbl_servico_realizado.peca_estoque IS NOT TRUE
                AND (tbl_auditoria_os.liberada notnull or tbl_auditoria_os.os IS NULL)";
        $query  = $pdo->query($sql);
        return $query->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function finaliza($con, $troca_produto_api = false, $login_admin = null, $origem = null)
    {
        parent::finaliza($con, $troca_produto_api, $login_admin, $origem);
    }

    public function finalizaOsRevenda($con, $data_fechamento)
    {
        $pdo = $this->_model->getPDO();

        $sqlOsProduto = "
            SELECT
                tbl_os.os,
                tbl_os.sua_os,
                tbl_os.data_fechamento
            FROM tbl_os_campo_extra
            JOIN tbl_os USING(os,fabrica)
            WHERE tbl_os.fabrica = {$this->_fabrica}
            AND tbl_os.excluida IS NOT TRUE
            AND tbl_os.data_fechamento IS NULL
            AND os_revenda = {$this->_os};
        ";

        $queryOsProduto = $pdo->query($sqlOsProduto);
        $resOsProduto = $queryOsProduto->fetchAll(\PDO::FETCH_ASSOC);

        $erroOsProduto = array();
        foreach($resOsProduto as $osProduto) {

            if (empty($osProduto["data_fechamento"])){
                $erroOsProduto[] = traduz("OS {$this->_os} não pode ser fechada: OS {$osProduto['sua_os']} ainda esta em aberto");
            }

            $intervencao = $this->_model->verificaOsIntervencao($osProduto['os'], $this->_fabrica);
            if ($intervencao != false) {
                $erroOsProduto[] = traduz("OS {$osProduto['sua_os']} em intervenção da fábrica");
            }
            $validacao = $this->validaInformacoesOs($osProduto['os'], $osProduto['sua_os'], $data_fechamento, 'f', false);
            if ($validacao != false) {
                $erroOsProduto[] = $validacao;
            }
            $os_revisao = $this->verificaRevisaoTipo($osProduto['os']);
            $os_troca = $this->verificaOsTroca($osProduto['os']);
            if ($this->_model->verificaDefeitoConstatado($con, $osProduto['os']) === false && $os_revisao == false && $os_troca == false) {
                $erroOsProduto[] = "A OS ".$osProduto['sua_os']." está sem Defeito Constatado";
            }
            $text_error = implode("<br/>", $erroOsProduto);
        }

        if (count($erroOsProduto) > 0) {
            throw new \Exception("$text_error");
        }

        $updateOsRevenda = "UPDATE tbl_os_revenda SET data_fechamento = '{$data_fechamento}', finalizada = '{$data_fechamento}' WHERE os_revenda = {$this->_os} AND fabrica = {$this->_fabrica};";
        $resUpdate = $pdo->query($updateOsRevenda);
        if (!$resUpdate) {
            throw new \Exception("Ocorreu um erro atualizando as informações da OS {$this->_os}");
        }
    }

    public function cancelaOs($con, $os, $justificativa, $login_admin = null)
    {
        $pdo = $this->_model->getPDO();
	$cancelaOsRevenda = false;
	$cancelaOsNormal = false;
     
        if (empty($os)) {
            throw new \Exception("Erro ao cancelar: Ordem de Serviço não encontrada #1");
        }

        if (empty($justificativa)) {
            throw new \Exception("Erro ao cancelar: Motivo não informado #2");
        }

        $sqlOsRevenda = "SELECT os_revenda FROM tbl_os_revenda WHERE fabrica = {$this->_fabrica} AND os_revenda = {$os}";
        $queryOsRevenda = $pdo->query($sqlOsRevenda);

        $resOsRevenda = $queryOsRevenda->fetch(\PDO::FETCH_ASSOC);

	if ($resOsRevenda == false) {
	    $sqlOsRevendaByOs = "SELECT os_revenda FROM tbl_os_campo_extra WHERE os = {$os} AND fabrica = {$this->_fabrica};";
	    $queryOsRevendaByOs = $pdo->query($sqlOsRevendaByOs);
	    $resOsRevendaByOs = $queryOsRevendaByOs->fetch(\PDO::FETCH_ASSOC);
	    $os_revenda = $resOsRevendaByOs['os_revenda'];
	    $cancelaOsNormal = true;

	    $sqlOssNaoCanceladas = "
		SELECT
		    os_revenda
		FROM tbl_os_campo_extra oce
		JOIN tbl_os o USING(os,fabrica)
		WHERE oce.os_revenda = {$os_revenda}
		AND oce.fabrica = {$this->_fabrica}
		AND o.excluida IS NOT TRUE;
	    ";

	    $queryOssNaoCanceladas = $pdo->query($sqlOssNaoCanceladas);
            $resOssNaoCanceladas = $queryOssNaoCanceladas->fetch(\PDO::FETCH_ASSOC);

	    if ($resOssNaoCanceladas == false) {
		$cancelaOsRevenda = true;
	    }
	} else {
	    $cancelaOsRevenda = true;
            $os_revenda = $resOsRevenda["os_revenda"];
	}

        if ($cancelaOsRevenda === true) {
            
            $sqlOsCampoExtra = "
                SELECT
                    tbl_os_campo_extra.os,
                    tbl_os.sua_os,
                    tbl_os.posto
                FROM tbl_os_campo_extra
                JOIN tbl_os ON tbl_os.os = tbl_os_campo_extra.os AND tbl_os.fabrica = {$this->_fabrica}
                WHERE tbl_os_campo_extra.os_revenda = {$os_revenda}
                AND tbl_os_campo_extra.fabrica = {$this->_fabrica} ";
            $queryOsCampoExtra = $pdo->query($sqlOsCampoExtra);
            $resOsCampoExtra = $queryOsCampoExtra->fetchAll(\PDO::FETCH_ASSOC);

            if (count($resOsCampoExtra) > 0) {
                $array_erros = array();
                foreach ($resOsCampoExtra as $key => $value) {
                    $id_os  = $value['os'];
                    $sua_os = $value['sua_os'];
                    $posto  = $value['posto'];
                
                    $sqlOsItem = "
                        SELECT *
                        FROM tbl_os_item
                        INNER JOIN tbl_os_produto ON tbl_os_produto.os_produto = tbl_os_item.os_produto
                        INNER JOIN tbl_pedido_item ON tbl_pedido_item.pedido_item = tbl_os_item.pedido_item
                        INNER JOIN tbl_pedido ON tbl_pedido.pedido = tbl_pedido_item.pedido AND tbl_pedido.fabrica = {$this->_fabrica}
                        WHERE tbl_os_produto.os = {$id_os}
                        AND (tbl_pedido_item.qtde - (COALESCE(tbl_pedido_item.qtde_faturada, 0) + COALESCE(tbl_pedido_item.qtde_cancelada, 0))) > 0
                        AND tbl_pedido.status_pedido NOT IN(1)";
                    $queryOsItem = $pdo->query($sqlOsItem);
                    $resOsItem = $queryOsItem->fetchAll(\PDO::FETCH_ASSOC);

                    if (count($resOsItem) > 0){
                        throw new \Exception("Erro ao cancelar: A OS {$sua_os} possui peças aguardando faturamento #3");
                    }

                    $sqlUpdateOs = "
                        UPDATE tbl_os SET
                            excluida = TRUE,
                            status_checkpoint = 28
                        WHERE fabrica = {$this->_fabrica}
                        AND os = {$id_os}";
                    $queryOsItem = $pdo->query($sqlUpdateOs);
                    
                    if (!$queryOsItem) {
                        throw new \Exception("Ocorreu um erro atualizar as informações da OS {$sua_os} #4");
                    }

                    if (!empty($login_admin)){
                        $campo_admin = " admin, ";
                        $valor_admin = "$login_admin, ";

                        $campo_update_admin = " admin = $login_admin , ";

                        $mensagem = "Ordem de Serviço cancelada pela fábrica: $justificativa";
                    }else{
                        $mensagem = "Ordem de Serviço cancelada pelo Posto: $justificativa";
                    }

                    $sqlInsertInteracao = "
                        INSERT INTO tbl_os_interacao
                        (os, data, $campo_admin comentario, interno, fabrica)
                        VALUES
                        ({$id_os}, CURRENT_TIMESTAMP, {$valor_admin} E'{$mensagem}', false, {$this->_fabrica})";
                    $queryInsertInteracao = $pdo->query($sqlInsertInteracao);

                    if (!$queryInsertInteracao) {
                        throw new \Exception("Ocorreu um erro atualizar as informações da OS {$sua_os} #5");
                    }

                    $sqlInsertComunicado = "
                        INSERT INTO tbl_comunicado
                        (mensagem, descricao, tipo, fabrica, obrigatorio_site, posto, ativo)
                        VALUES
                        (E'{$justificativa}', 'Ordem de Serviço cancelada - $sua_os', 'Comunicado', {$this->_fabrica}, true, $posto, true)";
                    $queryInsertComunicado = $pdo->query($sqlInsertComunicado);

                    if (!$queryInsertComunicado) {
                        throw new \Exception("Ocorreu um erro atualizar as informações da OS {$sua_os} #6");
                    }

                    $sqlUpdateAuditoriaOs = "
                        UPDATE tbl_auditoria_os SET
                            cancelada = CURRENT_TIMESTAMP,
                            {$campo_update_admin}
                            justificativa = 'Ordem de Serviço cancelada'
                        WHERE os = {$id_os}
                        AND liberada IS NULL AND reprovada IS NULL AND cancelada IS NULL";
                    $queryUpdateAuditoriaOs = $pdo->query($sqlUpdateAuditoriaOs);

                    if (!$queryUpdateAuditoriaOs) {
                        throw new \Exception("Ocorreu um erro atualizar as informações da OS {$sua_os} #7");
                    }
                
                    $sqlServicoRealizado = "
                        SELECT 
                            servico_realizado 
                        FROM tbl_servico_realizado 
                        WHERE fabrica = {$this->_fabrica} 
                        AND LOWER(descricao) = 'cancelado'";
                    $queryServicoRealizado = $pdo->query($sqlServicoRealizado);
                    $resServicoRealizado = $queryServicoRealizado->fetch(\PDO::FETCH_ASSOC);

                    $sqlOsItem2 = "
                        SELECT 
                            oi.os_item, 
                            oi.pedido_item, 
                            pi.pedido, 
                            (pi.qtde - (COALESCE(pi.qtde_faturada, 0) + COALESCE(pi.qtde_cancelada, 0))) AS qtde_pendente
                        FROM tbl_os_item oi
                        INNER JOIN tbl_os_produto op ON op.os_produto = oi.os_produto
                        LEFT JOIN tbl_pedido_item pi ON pi.pedido_item = oi.pedido_item
                        WHERE op.os = {$id_os}";
                    $queryOsItem2 = $pdo->query($sqlOsItem2);
                    $resOsItem2 = $queryOsItem2->fetchAll(\PDO::FETCH_ASSOC);

                    if (count($resOsItem2) > 0){
                        foreach ($resOsItem2 as $i => $row) {
                            if (!empty($row['pedido_item']) && $row['qtde_pendente'] == 0) {
                                continue;
                            }
                            
                            $sqlUpdateServicoRealizado = "
                                UPDATE tbl_os_item SET
                                    servico_realizado = {$resServicoRealizado['servico_realizado']}
                                WHERE os_item = {$row['os_item']}";
                            $queryUpdateServicoRealizado = $pdo->query($sqlUpdateServicoRealizado);
                            
                            if (!$queryUpdateServicoRealizado) {
                                throw new \Exception("Ocorreu um erro atualizar as informações da OS {$sua_os} #8");
                            }
                        
                            if (!empty($row['pedido_item']) AND $row['qtde_pendente'] > 0) {
                                $sqlUpdatePedidoItem = "
                                    UPDATE tbl_pedido_item SET
                                        qtde_cancelada = {$row['qtde_pendente']}
                                    WHERE pedido_item = {$row['pedido_item']};

                                    SELECT fn_atualiza_status_pedido({$this->_fabrica}, {$row['pedido']});
                                ";
                                $queryUpdatePedidoItem = $pdo->query($sqlUpdatePedidoItem);

                                if (!$queryUpdatePedidoItem) {
                                    throw new \Exception("Ocorreu um erro atualizar as informações da OS {$sua_os} #9");
                                }

                                $sqlInsertPedidoCancelado = "
                                    INSERT INTO tbl_pedido_cancelado(
                                        pedido, posto, fabrica, os, $campo_admin motivo
                                    )VALUES(
                                        {$row['pedido']}, {$posto}, {$this->_fabrica}, {$id_os}, {$valor_admin} 'Ordem de serviço Cancelada' 
                                    )";
                                $queryInsertPedidoCancelado = $pdo->query($sqlInsertPedidoCancelado);
                                
                                if (!$queryInsertPedidoCancelado) {
                                    throw new \Exception("Ocorreu um erro atualizar as informações da OS {$sua_os} #10");
                                }
                            }
                        }
                    }
                }
            }

            $sqlUpdateOsRevenda = "UPDATE tbl_os_revenda SET excluida = 'TRUE' WHERE os_revenda = {$os_revenda}";
            $queryUpdateOsRevenda = $pdo->query($sqlUpdateOsRevenda);

            if (!$queryUpdateOsRevenda) {
                throw new \Exception("Ocorreu um erro atualizar as informações da OS {$sua_os} #11");
            }

            $sqlUpateAuditoriaOsRevenda = "
                UPDATE tbl_auditoria_os_revenda SET
                    cancelada = current_timestamp,
                    $campo_update_admin
                    justificativa = 'Ordem de Serviço cancelada'
                WHERE os_revenda = {$os_revenda}
                AND (liberada IS NULL AND cancelada IS NULL)";
            $queryUpateAuditoriaOsRevenda = $pdo->query($sqlUpateAuditoriaOsRevenda);

            if (!$queryUpateAuditoriaOsRevenda) {
                throw new \Exception("Ocorreu um erro atualizar as informações da OS {$sua_os} #11");
            }

        }

	if ($cancelaOsNormal === true) {
            $sqlTblOs = "SELECT sua_os, posto FROM tbl_os WHERE fabrica = {$this->_fabrica} AND os = $os";
            $queryTblOs = $pdo->query($sqlTblOs);
            $resTblOs = $queryTblOs->fetchAll(\PDO::FETCH_ASSOC);

            if (!$resTblOs){
                if (!$queryOsItem) {
                   throw new \Exception("Ordem de serviço não encontrada");
                }
            }

            $sua_os = $resTblOs[0]['sua_os'];
            $posto  = $resTblOs[0]['posto'];
            
            $sqlOsItem = "
                SELECT *
                FROM tbl_os_item
                INNER JOIN tbl_os_produto ON tbl_os_produto.os_produto = tbl_os_item.os_produto
                INNER JOIN tbl_pedido_item ON tbl_pedido_item.pedido_item = tbl_os_item.pedido_item
                INNER JOIN tbl_pedido ON tbl_pedido.pedido = tbl_pedido_item.pedido AND tbl_pedido.fabrica = {$this->_fabrica}
                WHERE tbl_os_produto.os = {$os}
                AND (tbl_pedido_item.qtde - (COALESCE(tbl_pedido_item.qtde_faturada, 0) + COALESCE(tbl_pedido_item.qtde_cancelada, 0))) > 0
                AND tbl_pedido.status_pedido NOT IN(1)";
            $queryOsItem = $pdo->query($sqlOsItem);
            $resOsItem = $queryOsItem->fetchAll(\PDO::FETCH_ASSOC);

            if ($resOsItem) {
                throw new \Exception("A OS $sua_os possui peças aguardando a emissão de nota fiscal");
            }
                
            if (!empty($login_admin)){
                $campo_admin = " admin, ";
                $valor_admin = "$login_admin, ";

                $campo_update_admin = " admin = $login_admin , ";

                $campo_update_admin_excluida = " ,admin_excluida = $login_admin ";

                $mensagem = "Ordem de Serviço cancelada pela fábrica: $justificativa";
            }else{
                $mensagem = "Ordem de Serviço cancelada pelo Posto: $justificativa";
            }

            $sqlUpdateOs = "
                UPDATE tbl_os SET
                    excluida = TRUE,
                    status_checkpoint = 28
                    {$campo_update_admin_excluida}
                WHERE fabrica = {$this->_fabrica}
                AND os = {$os}";
            $queryUpdateOs = $pdo->query($sqlUpdateOs);
            
            if (!$queryUpdateOs) {
                throw new \Exception("Erro ao atualizar dados da OS: $sua_os #1");
            }

            $sqlInsetOsInteracao = "
                INSERT INTO tbl_os_interacao
                (os, data, admin, comentario, interno, fabrica)
                VALUES
                ({$os}, CURRENT_TIMESTAMP, {$campo_admin} E'{$mensagem}', false, {$this->_fabrica})";
            $queryInsetOsInteracao = $pdo->query($sqlInsetOsInteracao);

            if (!queryInsetOsInteracao) {
                throw new \Exception("Erro ao atualizar dados da OS: $sua_os #2");
            }

            $sqlInsertComunicado = "
                INSERT INTO tbl_comunicado
                (mensagem, descricao, tipo, fabrica, obrigatorio_site, posto, ativo)
                VALUES
                (E'{$mensagem}', 'Ordem de Serviço cancelada - $sua_os', 'Comunicado', {$this->_fabrica}, true, $posto, true)";
            $queryInsertComunicado = $pdo->query($sqlInsertComunicado);

            if (!$queryInsertComunicado) {
                throw new \Exception("Erro ao atualizar dados da OS: $sua_os #3");
            }

            $sqlUpdateAuditoriaOs = "
                UPDATE tbl_auditoria_os SET
                    cancelada = CURRENT_TIMESTAMP,
                    {$campo_update_admin}
                    justificativa = 'Ordem de Serviço cancelada'
                WHERE os = {$os}
                AND liberada IS NULL AND reprovada IS NULL AND cancelada IS NULL";
            $queryUpdateAuditoriaOs = $pdo->query($sqlUpdateAuditoriaOs);

            if (!$queryUpdateAuditoriaOs) {
                throw new \Exception("Erro ao atualizar dados da OS: $sua_os #4");
            }

            $sqlServicoRealizado = "SELECT servico_realizado FROM tbl_servico_realizado WHERE fabrica = {$this->_fabrica} AND LOWER(descricao) = 'cancelado'";
            $queryServicoRealizado = $pdo->query($sqlServicoRealizado);
            $resServicoRealizado = $queryServicoRealizado->fetch(\PDO::FETCH_ASSOC);

            if (!$resServicoRealizado) {
                throw new \Exception("Erro ao atualizar dados da OS: $sua_os #5");
            }

            $servico_realizado = $resServicoRealizado['servico_realizado'];

            $sqlOsItem2 = "
                SELECT oi.os_item, oi.pedido_item, pi.pedido, (pi.qtde - (COALESCE(pi.qtde_faturada, 0) + COALESCE(pi.qtde_cancelada, 0))) AS qtde_pendente
                FROM tbl_os_item oi
                INNER JOIN tbl_os_produto op ON op.os_produto = oi.os_produto
                LEFT JOIN tbl_pedido_item pi ON pi.pedido_item = oi.pedido_item
                WHERE op.os = {$os}";
            $queryOsItem2 = $pdo->query($sqlOsItem2);
            $resOsItem2 = $queryOsItem2->fetchAll(\PDO::FETCH_ASSOC);

            if (!$queryOsItem2){
                throw new \Exception("Erro ao atualizar dados da OS: $sua_os #6");
            }

            if ($resOsItem2) {
                foreach (pg_fetch_all($resOsItem2) as $i => $row) {
                    if (!empty($row['pedido_item']) && $row['qtde_pendente'] == 0) {
                        continue;
                    }

                    $sqlUpdateOsItem = "
                        UPDATE tbl_os_item SET
                            servico_realizado = {$servico_realizado}
                        WHERE os_item = {$row['os_item']}";
                    $queryUpdateOsItem = $pdo->query($sqlUpdateOsItem);

                    if (!$queryUpdateOsItem) {
                        throw new \Exception("Erro ao atualizar dados da OS: $sua_os #7");
                    }

                    if (!empty($row['pedido_item']) && $row['qtde_pendente'] > 0) {
                        $sqlUpdatePedidoItem = "
                            UPDATE tbl_pedido_item SET
                                qtde_cancelada = {$row['qtde_pendente']}
                            WHERE pedido_item = {$row['pedido_item']};

                            SELECT fn_atualiza_status_pedido({$this->_fabrica}, {$row['pedido']}); ";
                        $queryUpdatePedidoItem = $pdo->query($sqlUpdatePedidoItem);

                         if (!$queryUpdatePedidoItem) {
                            throw new \Exception("Erro ao atualizar dados da OS: $sua_os #8");
                        }
                    }
                }
            }
        }
    }

    public function verificaOsPrincipal($con)
    {
        $pdo = $this->_model->getPDO();
        
        $sql = "SELECT os_revenda FROM tbl_os_campo_extra WHERE os = {$this->_os} AND fabrica = $this->_fabrica";
        $query = $pdo->query($sql);
        $result = $query->fetch(\PDO::FETCH_ASSOC);

        if ($result){
            $sqlOs ="
                SELECT oce.os
                FROM tbl_os_campo_extra oce
                JOIN tbl_os o ON o.os = oce.os AND o.fabrica = {$this->_fabrica}
                WHERE oce.fabrica = {$this->_fabrica}
                AND oce.os_revenda = {$result['os_revenda']} 
                AND o.data_fechamento IS NULL
                AND o.finalizada IS NULL ";
            $queryOs = $pdo->query($sqlOs);
            $resultOs = $queryOs->fetchAll(\PDO::FETCH_ASSOC);

            if (!$resultOs){
                $updateOsRevenda = "
                    UPDATE tbl_os_revenda 
                    SET data_fechamento = now(), finalizada = now() 
                    WHERE os_revenda = {$result['os_revenda']} 
                    AND fabrica = {$this->_fabrica}";
                $resUpdate = $pdo->query($updateOsRevenda);
                if (!$resUpdate) {
                    throw new \Exception("Ocorreu um erro atualizando as informações da OS {$this->_os}");
                }
            }
        }
    }
}
