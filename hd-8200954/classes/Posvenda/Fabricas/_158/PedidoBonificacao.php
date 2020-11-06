<?php

namespace Posvenda\Fabricas\_158;

use Posvenda\Regras;
use Posvenda\Os as OsPosvenda;
use Posvenda\Fabricas\_158\ExportaPedido;

class PedidoBonificacao {
    private $pedido;
    private $_serverEnvironment;
    private $url;

    public function __construct(\Posvenda\Pedido $pedido)
    {
        $this->pedido = $pedido;

        include "/etc/telecontrol.cfg";
	$this->_serverEnvironment = $_serverEnvironment;

	include "../../../../rotinas/imbera/funcoes.php";
	$this->url = urlSap(true);
    }

    public function verificaTecnicoProprio($posto, $fabrica)
    {

	$return = false;
	$posto = trim($posto);

        if (!empty($posto)) {
            $this->pedido->_model->select("tbl_posto_fabrica")
                ->setCampos(array("tbl_tipo_posto.tipo_posto"))
                ->addJoin(array("tbl_tipo_posto" => "ON tbl_tipo_posto.tipo_posto = tbl_posto_fabrica.tipo_posto"))
                ->addWhere(array("tbl_posto_fabrica.fabrica" => $fabrica))
                ->addWhere(array("tbl_posto_fabrica.posto" => $posto))
                ->addWhere(array("tbl_tipo_posto.tecnico_proprio" => true));

            if (!$this->pedido->_model->prepare()->execute()) {
                throw new \Exception("Erro ao verificar técnico próprio");
            }

            if($this->pedido->_model->getPDOStatement()->rowCount() > 0 ){
                $return = true;
            }

	}

	return $return;

    }

    public function verificaDistribuidor($posto = null)
    {
        $pdo = $this->pedido->_model->getPDO();

        if (!empty($posto)) {
            $sql = "SELECT * FROM tbl_distribuidor_sla_posto WHERE posto = :posto;";
            $query = $pdo->query($sql);
        }

        $query = $pdo->prepare($sql);
        $query->bindParam(':posto', $posto, \PDO::PARAM_INT);

        $res = array();

        if ($query->execute()) {
            $res = $query->fetchAll(\PDO::FETCH_ASSOC);
        }

        if (!empty($res)) {
            return true;
        }

        return false;
    }

    public function verificaEstoqueBonificacao($posto, $fabrica)
    {

        $pdo = $this->pedido->_model->getPDO();

        $posto_interno_nao_gera = Regras::get("posto_interno_nao_gera", "pedido_garantia", $fabrica);

        if ($posto_interno_nao_gera == true) {
            $wherePostoInterno = "AND tp.posto_interno IS NOT TRUE";
        }

        $sql = "
            SELECT
                pc.peca,
                pc.referencia,
                pc.descricao AS desc_peca,
                pc.unidade,
                pf.codigo_posto,
                pf.centro_custo,
                ta.codigo AS codigo_tipo_atendimento,
                ta.descricao AS desc_tipo_atendimento,
                epm.os,
                epm.os_item,
                pd.pedido,
                oi.pedido_item,
                pd.status_pedido,
                TO_CHAR(pd.data, 'YYYYMMDD') AS data_pedido,
                '' AS nf,
                CASE WHEN oi.pedido IS NULL THEN oi.qtde ELSE pi.qtde - COALESCE(pi.qtde_faturada,0) - COALESCE(pi.qtde_cancelada,0) END AS qtde_pedido,
                oce.campos_adicionais
            FROM tbl_estoque_posto ep
            JOIN tbl_peca pc ON pc.peca = ep.peca AND pc.fabrica = :fabrica
            JOIN tbl_posto_fabrica pf ON pf.posto = ep.posto AND pf.fabrica = :fabrica
            JOIN tbl_tipo_posto tp ON tp.tipo_posto = pf.tipo_posto AND tp.fabrica = :fabrica
            JOIN tbl_estoque_posto_movimento epm ON epm.peca = ep.peca AND epm.fabrica = :fabrica AND epm.posto = :posto
            JOIN tbl_os o ON o.os = epm.os AND o.fabrica = :fabrica
            JOIN tbl_os_campo_extra oce ON oce.os = o.os AND oce.fabrica = :fabrica 
            JOIN tbl_tipo_atendimento ta ON ta.tipo_atendimento = o.tipo_atendimento AND ta.fabrica = :fabrica
            JOIN tbl_os_item oi ON oi.os_item = epm.os_item
            JOIN tbl_servico_realizado sr ON sr.servico_realizado = oi.servico_realizado AND sr.fabrica = :fabrica
            LEFT JOIN tbl_pedido pd ON pd.pedido = oi.pedido AND pd.fabrica = :fabrica
            LEFT JOIN tbl_pedido_item pi ON pi.pedido = pd.pedido AND pi.pedido_item = oi.pedido_item
            WHERE ep.fabrica = :fabrica
            AND ep.posto = :posto
            AND epm.os IS NOT NULL
            AND epm.qtde_entrada IS NULL
            AND sr.gera_pedido IS FALSE
            AND sr.peca_estoque IS TRUE
            {$wherePostoInterno}
            AND ((pd.status_pedido = 1 AND pi.qtde - (COALESCE(pi.qtde_cancelada, 0) + COALESCE(pi.qtde_faturada, 0)) > 0) OR oi.pedido IS NULL)
            AND (ta.fora_garantia IS TRUE OR tp.tecnico_proprio IS TRUE)
            AND JSON_FIELD('unidadeNegocio', oce.campos_adicionais) IN (
                SELECT tbl_unidade_negocio.codigo 
                FROM tbl_unidade_negocio
                WHERE (tbl_unidade_negocio.parametros_adicionais->>'geraPedidoBonificacao')::boolean IS TRUE
            )
            ORDER BY epm.os DESC;
        ";

	$query = $pdo->prepare($sql);

        $query->bindParam(':posto', $posto, \PDO::PARAM_INT);
        $query->bindParam(':fabrica', $fabrica, \PDO::PARAM_INT);
        $query->execute();

        return $query->fetchAll(\PDO::FETCH_ASSOC);

    }

    public function verificaEstoqueAumentoKit($posto = null, $fabrica = null)
    {

        if ($posto == null || $fabrica == null) {
            throw new \Exception("Posto ou Fábrica não encontrados para buscar Estoque");
        }

        $pdo = $this->pedido->_model->getPDO();

        $sql = "
            SELECT
                p.peca,
                p.referencia,
                p.unidade,
                COALESCE(ep.estoque_maximo, 0) - (COALESCE(ep.qtde, 0) + COALESCE(xepm.qtde_saida,0) + COALESCE(xpdi.qtde,0)) AS qtde
            FROM tbl_estoque_posto ep
            JOIN tbl_peca p ON p.peca = ep.peca AND p.fabrica = :fabrica
            LEFT JOIN (
                SELECT
                    oi.peca,
                    COALESCE(SUM(epm.qtde_saida),0) AS qtde_saida
                FROM tbl_estoque_posto_movimento epm
                JOIN tbl_os_item oi USING(os_item)
                WHERE epm.fabrica = :fabrica
                AND epm.posto = :posto
                AND oi.pedido IS NULL
                GROUP BY oi.peca
            ) xepm ON xepm.peca = ep.peca
            LEFT JOIN (
                SELECT
                    pdi.peca,
                    COALESCE(SUM(pdi.qtde),0) AS qtde
                FROM tbl_pedido_item pdi
                JOIN tbl_pedido pd USING(pedido)
                JOIN tbl_tipo_pedido tpd USING(tipo_pedido,fabrica)
                LEFT JOIN tbl_os_item oi USING(pedido_item)
                WHERE pd.posto = :posto
                AND pd.fabrica = :fabrica
                AND tpd.garantia_antecipada IS TRUE
                AND (tpd.uso_consumo IS TRUE OR oi.os_item IS NOT NULL)
                AND COALESCE(pdi.qtde_faturada, 0) + COALESCE(pdi.qtde_cancelada, 0) < pdi.qtde
                GROUP BY pdi.peca
            ) xpdi ON xpdi.peca = ep.peca
            WHERE ep.fabrica = :fabrica
            AND ep.posto = :posto
            AND COALESCE(ep.estoque_maximo, 0) - (COALESCE(ep.qtde, 0) + COALESCE(xepm.qtde_saida,0) + COALESCE(xpdi.qtde,0)) > 0;
        ";

        $query = $pdo->prepare($sql);

        $query->bindParam(':posto', $posto, \PDO::PARAM_INT);
        $query->bindParam(':fabrica', $fabrica, \PDO::PARAM_INT);

        if (!$query->execute()) {
            throw new \Exception("Ocorreu um erro buscando informações do estoque do posto");
        } else {
            $res = $query->fetchAll(\PDO::FETCH_ASSOC);
        }

        return $res;
    }

    public function verificaEstoqueBonificacaoGarantia($fabrica = null, $posto = null)
    {
        if ($fabrica == null) {
            throw new Exception("Fábrica não encontrada para buscar Estoque Bonificação em Garantia");
        }

        $pdo = $this->pedido->_model->getPDO();

        if ($posto != null) {
            $wherePosto = "AND ep.posto = :posto";
        }

        $posto_interno_nao_gera = Regras::get("posto_interno_nao_gera", "pedido_garantia", $fabrica);

        if ($posto_interno_nao_gera == true) {
            $wherePostoInterno = "AND tp.posto_interno IS NOT TRUE";
        }

        if (is_array($posto)) {
            $postos = implode("','", $posto);

            $wherePosto = " AND ep.posto IN ('$postos') ";
        }

        $sql = "
            SELECT
                pc.peca,
                pc.referencia,
                pc.descricao AS desc_peca,
                pc.unidade,
                pf.posto,
                pf.codigo_posto,
                pf.centro_custo,
                ta.codigo AS codigo_tipo_atendimento,
                ta.descricao AS desc_tipo_atendimento,
                o.os,
                oi.os_item,
                COALESCE(epm.qtde_saida, 0) AS qtde_pedido
            FROM tbl_estoque_posto ep
            JOIN tbl_peca pc ON pc.peca = ep.peca AND pc.fabrica = :fabrica
            JOIN tbl_posto_fabrica pf ON pf.posto = ep.posto AND pf.fabrica = :fabrica
            JOIN tbl_tipo_posto tp ON tp.tipo_posto = pf.tipo_posto AND tp.fabrica = :fabrica
            JOIN tbl_estoque_posto_movimento epm ON epm.peca = ep.peca AND epm.posto = pf.posto AND epm.fabrica = :fabrica
            JOIN tbl_os o ON o.os = epm.os AND o.fabrica = :fabrica
            LEFT JOIN tbl_os_campo_extra oce ON oce.os = o.os AND oce.fabrica = :fabrica
            JOIN tbl_tipo_atendimento ta ON ta.tipo_atendimento = o.tipo_atendimento AND ta.fabrica = :fabrica
            JOIN tbl_os_item oi ON oi.os_item = epm.os_item
            JOIN tbl_servico_realizado sr ON sr.servico_realizado = oi.servico_realizado AND sr.fabrica = :fabrica
            WHERE ep.fabrica = :fabrica
            {$wherePosto}
            AND epm.os IS NOT NULL
            AND epm.qtde_entrada IS NULL
            AND sr.gera_pedido IS FALSE
            AND sr.peca_estoque IS TRUE
            AND oi.pedido IS NULL
            AND JSON_FIELD('unidadeNegocio', oce.campos_adicionais) IN (
                SELECT tbl_unidade_negocio.codigo 
                FROM tbl_unidade_negocio
                WHERE (tbl_unidade_negocio.parametros_adicionais->>'geraPedidoBonificacaoGarantia')::boolean IS TRUE
            )
            AND tp.tecnico_proprio IS NOT TRUE
            AND (
                SELECT COUNT(ds.*)
                FROM tbl_distribuidor_sla_posto dsp
                INNER JOIN tbl_distribuidor_sla ds ON ds.distribuidor_sla = dsp.distribuidor_sla
                WHERE dsp.posto = pf.posto
                AND dsp.fabrica = :fabrica
                AND ds.unidade_negocio NOT IN (
                    SELECT tbl_unidade_negocio.codigo 
                    FROM tbl_unidade_negocio
                    WHERE (tbl_unidade_negocio.parametros_adicionais->>'geraPedidoBonificacaoGarantia')::boolean IS TRUE
                )
            ) > 0
            AND ep.peca NOT IN (
                SELECT
                    pdi.peca
                FROM tbl_pedido_item pdi
                JOIN tbl_pedido pd USING(pedido)
                JOIN tbl_tipo_pedido tpd USING(tipo_pedido,fabrica)
                WHERE pd.posto = pf.posto
                AND pd.fabrica = :fabrica
                AND tpd.garantia_antecipada IS TRUE
                AND tpd.pedido_em_garantia IS TRUE
                AND COALESCE(pdi.qtde_faturada, 0) + COALESCE(pdi.qtde_cancelada,0) < pdi.qtde
    	    )
            {$wherePostoInterno}
            ORDER BY o.os, pf.posto DESC;
        ";

        $query = $pdo->prepare($sql);

        if ($posto != null) {
            $query->bindParam(':posto', $posto, \PDO::PARAM_INT);
        }

        $query->bindParam(':fabrica', $fabrica, \PDO::PARAM_INT);
        $query->execute();
        $res = $query->fetchAll(\PDO::FETCH_ASSOC);

        if (count($res) > 0) {
            $dados = array();
            foreach ($res as $value) {
                $dados[$value['posto']]['codigo_posto'] = $value['codigo_posto'];
                $dados[$value['posto']]['pecas'][$value['peca']] = array(
                    'os_item'     => $value['os_item'],
                    'referencia'  => utf8_encode($value['referencia']),
                    'unidade'     => utf8_encode($value['unidade']),
                    'qtde_pedido' => $value['qtde_pedido']
                );
            }

        } else {
            return null;
        }

        return $dados;

    }

    public function verificaEstoqueBonificacaoMinimo($fabrica = null, $posto = null)
    {

        if ($fabrica == null) {
            throw new Exception("Fábrica não encontrada para buscar Estoque Bonificação em Garantia");
        }

        $pdo = $this->pedido->_model->getPDO();


        if (is_array($posto)) {
            $postos = implode(",", $posto);

            $wherePosto = " AND ep.posto IN ($postos) ";
        } else {
            $wherePosto = " AND ep.posto = :posto ";
        }

        $sql = "
            SELECT
                pc.peca,
                pc.referencia,
                pc.descricao AS desc_peca,
                pc.unidade,
                pf.posto,
                pf.codigo_posto,
                pf.centro_custo,
                COALESCE(ep.estoque_maximo, 0) - COALESCE(ep.qtde, 0) AS qtde_pedido,
                ds.unidade_negocio
            FROM tbl_posto_fabrica pf
            JOIN tbl_tipo_posto tp USING(tipo_posto,fabrica)
            JOIN tbl_distribuidor_sla_posto dsp USING(posto,fabrica)
            JOIN tbl_distribuidor_sla ds ON ds.distribuidor_sla = dsp.distribuidor_sla AND ds.fabrica = :fabrica
            JOIN tbl_estoque_posto ep ON ep.posto = pf.posto AND ep.fabrica = :fabrica
            JOIN tbl_peca pc ON pc.peca = ep.peca AND pc.fabrica = :fabrica
            WHERE pf.fabrica = :fabrica
            {$wherePosto}
	    AND tp.tecnico_proprio IS NOT TRUE
            AND tp.posto_interno IS NOT TRUE
            AND ep.peca NOT IN (
                SELECT
                    pdi.peca
                FROM tbl_pedido_item pdi
                JOIN tbl_pedido pd USING(pedido)
                JOIN tbl_tipo_pedido tpd USING(tipo_pedido,fabrica)
                WHERE pd.posto = pf.posto
                AND pd.fabrica = :fabrica
                AND tpd.garantia_antecipada IS TRUE
                AND tpd.pedido_em_garantia IS TRUE
                AND COALESCE(pdi.qtde_faturada, 0) + COALESCE(pdi.qtde_cancelada,0) < pdi.qtde
    	    )
	    AND COALESCE(ep.estoque_maximo, 0) > COALESCE(ep.estoque_minimo, 0)
            AND COALESCE(ep.estoque_maximo, 0) > 0
	    AND COALESCE(ep.qtde, 0) <= COALESCE(ep.estoque_minimo, 0)
	    ORDER BY pf.posto;
	";

	$query = $pdo->prepare($sql);

	if ($posto != null) {
	    $query->bindParam(':posto', $posto, \PDO::PARAM_INT);
	}

	$query->bindParam(':fabrica', $fabrica, \PDO::PARAM_INT);
	$query->execute();

	$res = $query->fetchAll(\PDO::FETCH_ASSOC);

        if (count($res) > 0) {
            $dados = array();
            foreach ($res as $value) {
                $dados[$value['posto']]['codigo_posto'] = $value['codigo_posto'];
                $dados[$value['posto']]['pecas'][$value['peca']] = array(
                    'os_item'     => $value['os_item'],
                    'referencia'  => utf8_encode($value['referencia']),
                    'unidade'     => utf8_encode($value['unidade']),
                    'qtde_pedido' => $value['qtde_pedido']
                );
            }

        } else {
            return null;
        }

        return $dados;

    }

    public function retornaPedidoBonificado($posto, $fabrica)
    {

        $pdo = $this->pedido->_model->getPDO();

        $sql = "
            SELECT
                tbl_pedido.pedido
            FROM tbl_pedido
            JOIN tbl_tipo_pedido ON tbl_tipo_pedido.tipo_pedido = tbl_pedido.tipo_pedido
            LEFT JOIN tbl_faturamento_item ON tbl_faturamento_item.pedido = tbl_pedido.pedido
            WHERE tbl_pedido.fabrica = {$fabrica}
            AND tbl_pedido.posto = {$posto}
            AND tbl_tipo_pedido.garantia_antecipada IS TRUE
            AND tbl_faturamento_item.faturamento IS NULL;
        ";

        $query = $pdo->query($sql);
        if ($query->rowCount() > 0) {
            $retorno = $query->fetchAll(\PDO::FETCH_ASSOC);
            return $retorno[0]['pedido'];
        }

    }

    public function pedidoBonificadoNaoFaturado($posto, $fabrica)
    {

        $pdo = $this->pedido->_model->getPDO();

        $sql = "
            SELECT
                p.pedido,
                op.os,
                oi.peca
            FROM tbl_pedido p
            JOIN tbl_pedido_item pi ON pi.pedido = p.pedido
            JOIN tbl_os_item oi ON oi.pedido_item = pi.pedido_item
            JOIN tbl_os_produto op ON oi.os_produto = op.os_produto
            JOIN tbl_tipo_pedido tp ON tp.tipo_pedido = p.tipo_pedido AND tp.garantia_antecipada IS TRUE
            LEFT JOIN tbl_faturamento_item fi ON fi.pedido = p.pedido
            WHERE p.fabrica = {$fabrica}
            AND p.posto = {$posto}
            AND fi.faturamento IS NULL;
        ";

        $query = $pdo->query($sql);

        $pedidos = $query->fetchAll(\PDO::FETCH_ASSOC);

        return $pedidos;

    }

    public function buscaPedidosAumentoKit($posto, $fabrica)
    {

        $pdo = $this->pedido->_model->getPDO();

        $sql = "
            SELECT
                pc.peca,
                pc.referencia,
                pc.descricao AS desc_peca,
                pc.unidade,
                pf.codigo_posto,
                pf.centro_custo,
                pd.pedido,
                pd.status_pedido,
                TO_CHAR(pd.data, 'YYYYMMDD') AS data_pedido,
                pdi.qtde AS qtde_pedido,
                ds.unidade_negocio
            FROM tbl_pedido pd
            JOIN tbl_tipo_pedido tpd ON tpd.tipo_pedido = pd.tipo_pedido AND tpd.garantia_antecipada IS TRUE AND tpd.uso_consumo IS TRUE
            JOIN tbl_pedido_item pdi ON pdi.pedido = pd.pedido
            JOIN tbl_peca pc ON pc.peca = pdi.peca AND pc.fabrica = :fabrica
            JOIN tbl_posto_fabrica pf ON pf.posto = pd.posto AND pf.fabrica = :fabrica
            JOIN tbl_posto_distribuidor_sla_default pdsd ON pdsd.posto = pf.posto AND pdsd.fabrica = :fabrica
            JOIN tbl_distribuidor_sla ds ON ds.distribuidor_sla = pdsd.distribuidor_sla AND ds.fabrica = :fabrica
            WHERE pd.fabrica = :fabrica
            AND pf.posto = :posto
            AND pf.fabrica = :fabrica
            AND pd.status_pedido = 1;
        ";

        $query = $pdo->prepare($sql);

        $query->bindParam(':posto', $posto, \PDO::PARAM_INT);
        $query->bindParam(':fabrica', $fabrica, \PDO::PARAM_INT);
        $query->execute();

        return $query->fetchAll(\PDO::FETCH_ASSOC);

    }

    public function atualizaEstoquePedido($estoque = null)
    {
        $pdo = $this->pedido->_model->getPDO();
        if (!is_array($estoque)) {
            throw new \Exception("Ocorreu um erro durante a exportação do pedido #1");
        } else {
            $estoqueAtualizadoPedido = array();
            foreach ($estoque as $os => $dados) {
                foreach ($dados as $xpedido => $value) {
                    if (empty($xpedido)) {
                        foreach($value['pecas'] as $peca) {
                            $sql = "
                                SELECT
                                    TO_CHAR(tbl_pedido.data, 'YYYYMMDD') AS data_pedido,
                                    tbl_pedido.pedido,
                                    tbl_pedido.status_pedido
                                FROM tbl_pedido
                                JOIN tbl_os_item USING(pedido)
                                WHERE tbl_os_item.os_item = :os_item;
                            ";
                            $query = $pdo->prepare($sql);

                            $query->bindParam(':os_item', $peca['os_item'], \PDO::PARAM_INT);
                            $query->execute();

                            $infoPedido = $query->fetch(\PDO::FETCH_ASSOC);

                            if (is_array($infoPedido)) {
                                $value['data_pedido'] = $infoPedido['data_pedido'];
                                $value['status_pedido'] = $infoPedido['status_pedido'];
                            }
                        }
                        $estoqueAtualizadoPedido[$os][$infoPedido['pedido']] = $value;
                    } else {
                        $estoqueAtualizadoPedido[$os] = $dados;
                    }
                }
            }
        }
        return $estoqueAtualizadoPedido;
    }

    public function buscaNotaFiscal($peca,$posto,$fabrica)
    {

        if (empty($peca) || empty($posto) || empty($fabrica)) {
            throw new \Exception("Ocorreu um erro ao buscar nota fiscal de entrada #2");
        }

        $pdo = $this->pedido->_model->getPDO();

        $sql = "
            SELECT
                epm.peca,
                epm.nf,
                (CASE WHEN epm.qtde_usada IS NOT NULL THEN COALESCE(epm.qtde_entrada - epm.qtde_usada, 0) ELSE epm.qtde_entrada END) AS qtde
            FROM tbl_estoque_posto_movimento epm
            WHERE epm.peca = :peca
            AND (epm.nf IS NOT NULL AND epm.nf NOT ILIKE 'ETMN%')
            AND epm.qtde_entrada - COALESCE(epm.qtde_usada,0) > 0
            AND epm.fabrica = :fabrica
	    AND epm.posto = :posto
	    ORDER BY epm.data, epm.nf::integer ASC;
        ";

        $query = $pdo->prepare($sql);

        $query->bindParam(':peca', $peca, \PDO::PARAM_INT);
        $query->bindParam(':posto', $posto, \PDO::PARAM_INT);
        $query->bindParam(':fabrica', $fabrica, \PDO::PARAM_INT);
        $query->execute();

        return $query->fetchAll(\PDO::FETCH_ASSOC);

    }

    public function adicionaNotaFiscal($pedidos, $posto, $fabrica)
    {

        if (count($pedidos) == 0 || empty($fabrica) || empty($posto)) {
            throw new \Exception("Ocorreu um erro ao buscar nota fiscal de entrada #1");
        }

        $dadosOrganizados = array();

        foreach ($pedidos as $os => $dados) {
            foreach($dados as $xpedido => $value) {
                $dadosOrganizados[$os][$xpedido] = $value;
                foreach($value['pecas'] as $peca => $dados_peca) {
                    if ($os != "") {
                        $nfs = $this->buscaNotaFiscal($peca,$posto,$fabrica);
                        $qtdeTotPed = 0;
                        $qtdePendente = $dados_peca['qtde_pedido'];
                        foreach ($nfs as $key => $nf_peca) {
                            if (!empty($dadosOrganizados[$os][$xpedido]['pecas'][$peca]['nf']) || $qtdePendente == 0) {
                                continue;
                            }
                            if ($nf_peca['qtde'] >= $dados_peca['qtde_pedido'] && empty($qtdeNota)) {
                                $dadosOrganizados[$os][$xpedido]['pecas'][$peca]['nf'] = $nf_peca['nf'];
                            } else if ($qtdeTotPed < $dados_peca['qtde_pedido']) {
                                unset($dadosOrganizados[$os][$xpedido]['pecas'][$peca]);
                                if ($nf_peca['qtde'] < $qtdePendente) {
                                    $qtdeTotPed += $nf_peca['qtde'];
                                    $qtdePendente -= $nf_peca['qtde'];
                                    $qtdeNota = $nf_peca['qtde'];
                                } else {
                                    $qtdeTotPed += $qtdePendente;
                                    $qtdeNota = $qtdePendente;
                                    $qtdePendente = 0;
                                }
                                $dadosOrganizados[$os][$xpedido]['pecas'][$peca."_".$key] = array(
                                    'referencia'  => $dados_peca['referencia'],
                                    'desc_peca'   => utf8_encode($dados_peca['desc_peca']),
                                    'unidade'     => utf8_encode($dados_peca['unidade']),
                                    'qtde_pedido' => $qtdeNota,
                                    'os_item'     => $dados_peca['os_item'],
                                    'pedido_item' => $dados_peca['pedido_item'],
                                    'nf'          => $nf_peca['nf']
                                );
                            }
                        }
                    }
                }
            }
        }

        return $dadosOrganizados;
    }

    public function organizaEstoque($estoque, $faturado = false)
    {

        $osPedido = array();
        foreach ($estoque as $key => $value) {
            if ($value['status_pedido'] != 1 && $value['status_pedido'] != null && $faturado == false) {
                continue;
            }
            if ((strlen($value['os']) > 0 && $os_anterior != $value['os']) || (strlen($value['pedido']) > 0 && $pedido_anterior != $value['pedido'])) {
                $osPedido[$value['os']][$value['pedido']] = array(
                    'codigo_posto'            => $value['codigo_posto'],
                    'centro_custo'            => $value['centro_custo'],
                    'codigo_tipo_atendimento' => $value['codigo_tipo_atendimento'],
                    'desc_tipo_atendimento'   => $value['desc_tipo_atendimento'],
                    'status_pedido'           => $value['status_pedido'],
                    'data_pedido'             => $value['data_pedido']
                );
                if (!empty($value['campos_adicionais'])) {
                    $campos_adicionais = json_decode($value['campos_adicionais'], true);
                    if (!empty($campos_adicionais['unidadeNegocio'])) {
                        $osPedido[$value['os']][$value['pedido']]['unidade_negocio'] = $campos_adicionais['unidadeNegocio'];
                    }
                } else if (!empty($value['unidade_negocio'])) {
                    $osPedido[$value['os']][$value['pedido']]['unidade_negocio'] = $value['unidade_negocio'];
                }
            }

            $osPedido[$value['os']][$value['pedido']]['pecas'][$value['peca']] = array(
                'referencia'  => $value['referencia'],
                'desc_peca'   => utf8_encode($value['desc_peca']),
                'unidade'     => utf8_encode($value['unidade']),
                'qtde_pedido' => $value['qtde_pedido'],
                'os_item'     => $value['os_item'],
                'pedido_item' => $value['pedido_item'],
                'nf'          => $value['nf']
            );

            $os_anterior = $value['os'];
            $pedido_anterior = $value['pedido'];

        }

        return $osPedido;
    }

    public function getDadosPosto($posto, $fabrica)
    {

        $pdo = $this->pedido->_model->getPDO();

        $sql = "
            SELECT
                pf.codigo_posto,
                pf.centro_custo,
                pf.conta_contabil,
                ds.unidade_negocio
            FROM tbl_posto_fabrica pf
            JOIN tbl_posto_distribuidor_sla_default pdsd USING(posto,fabrica)
            JOIN tbl_distribuidor_sla ds USING(distribuidor_sla,fabrica)
            WHERE pf.posto = :posto
            AND pf.fabrica = :fabrica;
        ";

        $query = $pdo->prepare($sql);

        $query->bindParam(':posto', $posto, \PDO::PARAM_INT);
        $query->bindParam(':fabrica', $fabrica, \PDO::PARAM_INT);
        $query->execute();

        $dadosPosto = $query->fetch(\PDO::FETCH_ASSOC);

        if (count($dadosPosto) == 0) {
            throw new \Exception("Não foi possível determinar a Unidade de negócio do posto autorizado");
        }

        return $dadosPosto;

    }

    public function verificaExportacao($pedidos)
    {
        foreach ($pedidos as $os => $dados) {
            foreach($dados as $xpedido => $value) {
                if ($value['status_pedido'] != null) {
                    return true;
                }
            }
        }
        return false;
    }

    public function atualizaQtdeUsada($posto, $fabrica, $peca, $nota_fiscal, $qtde, $log = null)
    {

        $pdo = $this->pedido->_model->getPDO();

	$sql = "
	    UPDATE tbl_estoque_posto_movimento epm
	    SET qtde_usada = COALESCE(epm.qtde_usada,0) + :qtde
	    FROM (
		SELECT
		    pedido,
		    faturamento,
		    obs,
		    data_digitacao
		FROM tbl_estoque_posto_movimento
		WHERE peca = :peca
		AND posto = :posto
		AND fabrica = :fabrica
		AND nf = :nota_fiscal
		AND qtde_entrada IS NOT NULL
		AND (qtde_entrada >= COALESCE(qtde_usada,0) + :qtde)
		ORDER BY data_digitacao DESC
		LIMIT 1
	    ) up_limit
	    WHERE epm.peca = :peca
	    AND epm.posto = :posto
	    AND epm.fabrica = :fabrica
	    AND epm.nf = :nota_fiscal
	    AND COALESCE(epm.faturamento,0) = COALESCE(up_limit.faturamento,0)
	    AND COALESCE(epm.pedido,0) = COALESCE(up_limit.pedido,0)
	    AND epm.data_digitacao = up_limit.data_digitacao
	    AND COALESCE(epm.obs,'') = COALESCE(up_limit.obs,'')
	    AND epm.qtde_entrada IS NOT NULL
	    AND (epm.qtde_entrada >= COALESCE(epm.qtde_usada,0) + :qtde);
	";

        $query = $pdo->prepare($sql);

        $query->bindParam(':posto', $posto, \PDO::PARAM_INT);
        $query->bindParam(':fabrica', $fabrica, \PDO::PARAM_INT);
        $query->bindParam(':peca', $peca, \PDO::PARAM_INT);
        $query->bindParam(':qtde', $qtde, \PDO::PARAM_STR);
	$query->bindParam(':nota_fiscal', $nota_fiscal, \PDO::PARAM_STR);

	if ($log != null) {
		fwrite($log, $sql."\n");
		fwrite($log, "Parametros => Posto: {$posto}, Fabrica: {$fabrica}, Peça: {$peca}, Qtde: {$qtde}, NF: {$nota_fiscal}\n");
	}
        if (!$query->execute()) {
	    return false;
	} else {
	    return true;
	}

    }

    public function abasteceEstoque($posto, $fabrica)
    {
        try {
            $this->pedido->_model->getPDO()->beginTransaction();

            $estoque = $this->verificaEstoqueBonificacao($posto, $fabrica);
            $estoque = $this->organizaEstoque($estoque);

            if (count($estoque) == 0) {
                $this->pedido->_model->getPDO()->rollBack();
                $return = array(
                    'status' => 'error',
                    'msg' => utf8_encode('Não é necessário gerar pedidos de bonificação para este posto')
                );
                return $return;
            }

            $this->pedidoBonificado($posto, $estoque, 'NULL', $fabrica);
            $pedidos = $this->atualizaEstoquePedido($estoque);

            $this->pedido->_model->getPDO()->commit();

            $pedidos = $this->adicionaNotaFiscal($pedidos, $posto, $fabrica);
            // $this->pedidoBonificadoIntegracao($pedidos, $posto, $fabrica);

            $return = array(
                'status' => 'ok',
                'msg' => "Pedidos processados, verifique se todos foram exportados"
            );

        } catch (\Exception $e) {

            $this->pedido->_model->getPDO()->rollBack();
            $return = array(
                'status' => 'error',
                'msg' => utf8_encode($e->getMessage())
            );

        }

        return $return;

    }

    public function pedidoBonificado($posto, $estoque, $distribuidor = null, $fabrica)
    {

        $oOS = new OsPosvenda($fabrica, null, 'os');
        $oExportaPedido = new ExportaPedido($this->pedido, $oOS, $fabrica);

        $condicao    = $this->pedido->getCondicaoGarantia();
        $tipo_pedido = $oExportaPedido->getTipoPedido("BON");

        $tabela = Regras::get('tabela', 'pedido_bonificacao', $fabrica);

        if (!empty($tabela)) {
            $dados['tabela'] = $tabela;
        }

        $gerou_pedido = false;

        foreach ($estoque as $os => $info) {
            foreach($info as $xpedido => $value) {
                if (empty($xpedido)) {

                    $dados = array(
                        "posto"         => $posto,
                        "tipo_pedido"   => $tipo_pedido,
                        "condicao"      => $condicao,
                        "status_pedido" => '1',
                        "fabrica"       => $fabrica,
                        "distribuidor"  => $distribuidor,
                    );

                    $this->pedido->grava($dados);

                    $idPedido = $this->pedido->getPedido();

                    foreach ($value["pecas"] as $id => $peca) {

                        if (!empty($peca['pedido_item'])) {
                            continue;
                        }

                        unset($dadosItens);

                        $peca_referencia = $peca["referencia"];
                        $qtde = $peca["qtde_pedido"];
                        $os_item = $peca["os_item"];

                        $dadosItens[] = array(
                            "pedido"            => (int)$idPedido,
                            "peca"              => $id,
                            "qtde"              => $qtde,
                            "qtde_faturada"     => 0,
                            "qtde_cancelada"    => 0,
                            "preco"             => $this->pedido->getPrecoPecaGarantiaAntecipada($id, $posto)
                        );

                        $this->pedido->gravaItem($dadosItens, $idPedido);
                        $idPedidoItem = $this->pedido->getPedidoItem();

                        if (!empty($os_item)) {
                            $this->pedido->atualizaOsItemPedidoItem($os_item, (int)$idPedido, (int)$idPedidoItem, $fabrica);
                        }

		    }

                    $gerou_pedido = true;
		    $this->pedido->finaliza($idPedido);

                }
            }
        }

        if (!$gerou_pedido) {
            throw new \Exception("Nenhum pedido foi gerado");
        }
    }

    public function pedidoAumentoEstoque($posto = null, $fabrica = null)
    {

        try {

            if ($posto == null || $fabrica == null) {
                throw new \Exception("Posto ou Fábrica não encontrados para geração do pedido");
            }

            $oOS = new OsPosvenda($fabrica, null, 'os');
            $oExportaPedido = new ExportaPedido($this->pedido, $oOS, $fabrica);
            $dadosExpPedido = array();

            $this->pedido->_model->getPDO()->beginTransaction();

            $pecas = $this->verificaEstoqueAumentoKit($posto, $fabrica);

            if (empty($pecas)) {
                throw new \Exception("Não é necessário gerar pedidos de aumento de kit");
            }

            $tipo_pedido = $oExportaPedido->getTipoPedido("BON-KIT");
            $condicao    = $this->pedido->getCondicaoGarantia();

            $tabela = Regras::get('tabela', 'pedido_bonificacao', $fabrica);

            if (!empty($tabela)) {
                $dados['tabela'] = $tabela;
            }

            $dados = array(
                "posto"         => $posto,
                "tipo_pedido"   => $tipo_pedido,
                "condicao"      => $condicao,
                "fabrica"       => $fabrica,
                "status_pedido" => 1,
                "finalizado"       => "'".date("Y-m-d H:i:s")."'"
            );

            $this->pedido->grava($dados, null);
            $idPedido = $this->pedido->getPedido();

            $dadosPosto = $this->getDadosPosto($posto, $fabrica);

            $dadosExpPedido[$idPedido] = array(
                'codigo_posto'    => $dadosPosto['codigo_posto'],
                'centro_custo'    => $dadosPosto['centro_custo'],
                'unidade_negocio' => $dadosPosto['unidade_negocio'],
                'status_pedido'   => 1,
                'data_pedido'     => date("Ymd")
            );

            $dadosItens = array();

            foreach ($pecas as $key => $peca) {

                unset($dadosItens);

                $preco = $this->pedido->getPrecoPecaGarantiaAntecipada($peca["peca"],$posto);

                if (empty($preco)) {
                    $preco = 10000;
                }

                $dadosItens[] = array(
                    "pedido"            => (int)$idPedido,
                    "peca"              => $peca["peca"],
                    "qtde"              => $peca["qtde"],
                    "qtde_faturada"     => 0,
                    "qtde_cancelada"    => 0,
                    "preco"             => $preco,
                    "total_item"        => $preco * $peca["qtde"]
                );

                $this->pedido->gravaItem($dadosItens, $idPedido);

                $dadosExpPedido[$idPedido]['pecas'][$peca['peca']] = array(
                    'referencia'  => $peca['referencia'],
                    'unidade'     => utf8_encode($peca['unidade']),
                    'qtde_pedido' => $peca['qtde']
                );

            }

            $this->pedido->finaliza($idPedido);
            $this->pedido->_model->getPDO()->commit();

            $dadosExportacao[] = $dadosExpPedido;
            // $oExportaPedido->pedidoIntegracao($dadosExportacao, "aumento_kit");

            $return = array(
                'status_kit' => 'ok',
                'msg_kit' => utf8_encode('Pedido de aumento kit gerado com sucesso, para mais informações acesse-o')
            );

        } catch (\Exception $e) {

            $this->pedido->_model->getPDO()->rollBack();
            $return = array(
                'status_kit' => 'error_kit',
                'msg_kit' => utf8_encode($e->getMessage())
            );

        }

        return $return;

    }

    public function pedidoBonificadoIntegracao($pedidos, $posto, $fabrica)
    {

        if (is_array($pedidos)) {
            foreach ($pedidos as $os => $dados) {
                foreach ($dados as $xpedido => $value) {
                    $dadosPedidos = "";
                    if (!empty($value['unidade_negocio']) && $value['status_pedido'] == 1) {
                        foreach ($value['pecas'] as $peca) {
			    if (in_array($value['unidade_negocio'], array(6200, 6108,6107,6103,6102,6101,6104,6105,6106))) {
				    $value['unidade_negocio'] = 6004;
			    }




                            $dadosPedidos .= "
                                <T_ENTRADA>
                                    <P_TELEC>".$xpedido."</P_TELEC>
                                    <CENTRO>".$value['unidade_negocio']."</CENTRO>
                                    <CLIENTE>7310</CLIENTE>
                                    <DATA>".$value['data_pedido']."</DATA>
                                    <O_TELEC>".$os."</O_TELEC>
                                    <TIPO_O>".$value['codigo_tipo_atendimento']."</TIPO_O>
                                    <TECNICO>".$value['centro_custo']."</TECNICO>
                                    <MATERIAL>".$peca['referencia']."</MATERIAL>
                                    <CANTIDAD>".$peca['qtde_pedido']."</CANTIDAD>
                                    <UM>".$peca['unidade']."</UM>
                                    <NF>".$peca['nf']."</NF>
                                </T_ENTRADA>
                            ";
			}

			if ($this->_serverEnvironment == 'development') {

			    $url = $this->url."/XISOAPAdapter/MessageServlet?senderParty=TELECONTROL_BR&senderService=TELCONTROL&receiverParty=&receiverService=&interface=SI_ConsumoTec_Receiver&interfaceNamespace=http://imbera.com/telecontrol";

			    $authorization = "Authorization: Basic ".base64_encode("PIAPPLTC:3lT#L&C0ntr01");

			} else {

			    $url = $his->url."/XISOAPAdapter/MessageServlet?senderParty=TELECONTROL_BR&senderService=TELCONTROL&receiverParty=&receiverService=&interface=SI_ConsumoTec_Receiver&interfaceNamespace=http://imbera.com/telecontrol";

			    $authorization = "Authorization: Basic ".base64_encode("PIAPPLTC:3lT#L&C0ntr01");

			}

			$xml_post_string = '
                            <soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:tel="http://imbera.com/telecontrol">
                                <soapenv:Header/>
                                <soapenv:Body>
                                    <tel:MT_ConsumoTec_Req>
                                       '.$dadosPedidos.'
                                    </tel:MT_ConsumoTec_Req>
                                </soapenv:Body>
                            </soapenv:Envelope>
			';

			$headers = array(
                            "Content-type: text/xml;charset=\"utf-8\"",
                            "Accept: text/xml",
                            "Cache-Control: no-cache",
                            "Pragma: no-cache",
                            "Content-length: ".strlen($xml_post_string),
                            $authorization
                        );

			$ch = curl_init();
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
                        curl_setopt($ch, CURLOPT_URL, $url);
                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                        curl_setopt($ch, CURLOPT_POST, 1);
                        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml_post_string);
                        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

			$retornoCurl = curl_exec($ch);
			$erroCurl = curl_error($ch);
			$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
			curl_close($ch);

			$retornoCurl = preg_replace(("/(<\/?)(\w+):([^>]*>)/"),"$1$2$3",$retornoCurl);

			$retornoXML = new \SimpleXMLElement(utf8_encode($retornoCurl));
			$retornoXML = $retornoXML->xpath('//T_MENSAGEM');
			$retornoSoap = json_decode(json_encode((array) $retornoXML), true);
			$retornoSoap = $retornoSoap[0];

			if ($this->_serverEnvironment == "development") {
                            $file = fopen('/tmp/imbera-ws.log','a');
                        } else {
                            $file = fopen('/mnt/webuploads/imbera/logs/imbera-ws.log','a');
			}

                        fwrite($file, 'Resquest \n\r');
                        fwrite($file, 'URL: '.$url.'\n\r');
                        fwrite($file, $xml_post_string);

			fwrite($file, 'Response \n\r');
			fwrite($file, 'Error Curl: '.$erroCurl.'\n\r');
			fwrite($file, 'Http Code: '.$httpcode.'\n\r');
                        fwrite($file, utf8_decode($retornoCurl));

                        switch ($retornoSoap['ID']) {
                            case '001':
                                $obsPedido = "Técnico não possui estoque para essa peça - Retorno SAP: ".$retornoSoap['MENSAGEM'];
                                break;

                            case '002':
                                $obsPedido = "Pedido Exportado com Sucesso - Retorno SAP: ".$retornoSoap['MENSAGEM'];
				$this->pedido->registrarPedidoExportado($xpedido);
				#if ($this->verificaTecnicoProprio($posto,$fabrica)) {
				    foreach ($value['pecas'] as $id => $peca) {
					if (strpos($id, "_") != false) {
					    $idPeca = explode("_", $id);
					    $idPeca = $idPeca[0];
					} else {
					    $idPeca = $id;
					}
				        $this->atualizaQtdeUsada($posto, $fabrica, $idPeca, $peca['nf'], $peca['qtde_pedido'], $file);
				    }
				#}
                                break;

                            case '003':
                                $obsPedido = "O técnico não possui essa quantidade de peças em estoque - Retorno SAP: ".$retornoSoap['MENSAGEM'];
                                break;

                            case '004':
                                $obsPedido = "Pedido e OS já recebida pelo SAP - Retorno SAP: ".$retornoSoap['MENSAGEM'];
                                $this->pedido->registrarPedidoExportado($xpedido);
                                break;

                            case '005':
                                $obsPedido = "Centro não encontrado - Retorno SAP: ".$retornoSoap['MENSAGEM'];
                                break;

                            case '006':
                                $obsPedido = "Unidade de medida incorreta - Retorno SAP: ".$retornoSoap['MENSAGEM'];
                                break;

                            case '007':
                                $obsPedido = "NF não pode ser vazia - Retorno SAP: ".$retornoSoap['MENSAGEM'];
                                break;

                            case '008':
                                $obsPedido = "NF incorreta - Retorno SAP: ".$retornoSoap['MENSAGEM'];
                                break;

                            case '012':
                                $obsPedido = "Centro/Deposito incorreto - Retorno SAP: ".$retornoSoap['MENSAGEM'];
                                break;

                            default:
                                $obsPedido = "Ocorreu um erro durante a exportação do pedido - Retorno SAP: ".$retornoSoap['MENSAGEM'];
                                break;

                        }

			$this->pedido->updateObservacao($xpedido, 'Retorno Exportação: '.$obsPedido);
                    }

		    fclose($file);

                }
            }
        }
    }

    public function getTipoPedidoBonificadoGarantia($fabrica)
    {
        if (empty($fabrica)) {
            throw new \Exception("Fabrica não informada para selecionar o tipo de garantia antecipada");
        }

        $this->pedido->_model->select('tbl_tipo_pedido')
             ->setCampos(array('tipo_pedido'))
             ->addWhere(array('fabrica' => $fabrica))
             ->addWhere(array('garantia_antecipada' => true))
             ->addWhere(array('pedido_em_garantia' => true));

        if (!$this->pedido->_model->prepare()->execute()) {
            throw new \Exception("Erro ao selecionar o tipo de pedido garantia antecipada");
        }

        $res = $this->pedido->_model->getPDOStatement()->fetch();

        if (!empty($res["tipo_pedido"])) {
            $tipo_pedido = $res["tipo_pedido"];
        }

        return $tipo_pedido;
    }

}
