<?php

namespace Posvenda\Fabricas\_165;

use Posvenda\Model\Os as OsModel;
use Posvenda\Os as OsPosvenda;
use Posvenda\Regras;

class Os extends OsPosvenda
{

    private $_fabrica;

    public function __construct($fabrica,$os)
    {
        $this->_fabrica = $fabrica;
        parent::__construct($fabrica,$os);
    }

    public function getSuaOs($os){
        $pdo = $this->_model->getPDO();

        $sql = "SELECT sua_os FROM tbl_os WHERE os = $os";
        $query = $pdo->prepare($sql);

        if ($query->execute()) {
            $res = $query->fetchAll(\PDO::FETCH_ASSOC);
            return $res[0]['sua_os'];
        }
        return $os;
    }

    public function getFamiliaDiasAbertoServicos($os)
    {

        $pdo = $this->_model->getPDO();
        
        $sql = "
            SELECT DISTINCT
                o.os,
                p.familia,
                oi.servico_realizado,
                CURRENT_DATE - (o.data_abertura)::DATE AS os_dias_aberto
            FROM tbl_os o
            JOIN tbl_os_produto op USING(os)
	    JOIN tbl_os_item oi USING(os_produto)
	    JOIN tbl_servico_realizado sr ON sr.servico_realizado = oi.servico_realizado AND sr.fabrica = {$this->_fabrica}
	    LEFT JOIN tbl_pedido_item pi ON pi.pedido_item = oi.pedido_item
            JOIN tbl_produto p ON p.produto = op.produto
	    WHERE o.os = {$os}
	    AND sr.ativo IS TRUE
	    AND (oi.pedido_item IS NULL
    	    OR pi.qtde_faturada > 0 or pi.qtde_cancelada = pi.qtde);
        ";

        $query = $pdo->prepare($sql);

        if ($query->execute()) {
            $res = $query->fetchAll(\PDO::FETCH_ASSOC);
        }

        $return = array();
        $linha = 0;

        if (!empty($res)) {
            foreach ($res as $servicos) {
                $return = array(
                    "os" => $servicos["os"],
                    "dias_aberto" => $servicos["os_dias_aberto"],
                    "servicos" => array(
                        $linha => $servicos["servico_realizado"]
                    ),
                    "familia" => array(
                        $linha => $servicos["familia"]
                    )
                );
            }
            return $return;
        } else {
            return false;
        }

    }

    public function getMobraConstatado($os){

        $pdo = $this->_model->getPDO();
        
        $sql = "SELECT count(tbl_os_item.os_item) as qtde_pecas
                 FROM tbl_os_item
                 inner join tbl_os_produto ON tbl_os_produto.os_produto = tbl_os_item.os_produto
                 inner join tbl_os ON tbl_os.os = tbl_os_produto.os
                 where tbl_os.os = $os ";
        $query = $pdo->prepare($sql);
        if ($query->execute()) {
            $res = $query->fetch(\PDO::FETCH_ASSOC);
        }

        if ($res['qtde_pecas'] == 0) {
            $sqlconstatado = "SELECT tbl_defeito_constatado.mao_de_obra
            from tbl_defeito_constatado
            inner join tbl_os on tbl_defeito_constatado.defeito_constatado = tbl_os.defeito_constatado
            where tbl_os.os = $os";
            $query = $pdo->prepare($sqlconstatado);
            if ($query->execute()) {
                $resConst = $query->fetch(\PDO::FETCH_ASSOC);
                $resConst['mao_de_obra'];
            }
        }

        return $resConst;

    }

    public function getMobra($dadosOrdem)
    {

        $pdo = $this->_model->getPDO();

        $os             = $dadosOrdem['os'];
        $diasAberto     = $dadosOrdem['dias_aberto'];
        $servicos       = implode(",", $dadosOrdem['servicos']);
	$familias       = implode(",", $dadosOrdem['familia']);

        $sql = "
            SELECT DISTINCT
                mosr.mao_de_obra,
                CASE WHEN $diasAberto > 30 THEN 30 ELSE mosr.tempo_estimado END AS tempo_estimado
            FROM tbl_mao_obra_servico_realizado mosr
            WHERE mosr.fabrica = {$this->_fabrica}
            AND mosr.familia IN ({$familias})
            AND mosr.servico_realizado IN ({$servicos})
	    AND (mosr.tempo_estimado >= {$diasAberto}
    	    OR {$diasAberto} > 30)
	    ORDER BY tempo_estimado ASC
            LIMIT 1;
        ";

        $query = $pdo->prepare($sql);

        if ($query->execute()) {
            $res = $query->fetch(\PDO::FETCH_ASSOC);
        }

        if (!empty($res)) {
            return $res;
        } else {
            return array('mao_de_obra',0);
        }
    }

    public function calculaOs($os)
    {
        if (empty($os)) {
            $os = $this->_os;
        }

        try {
            $km = $this->_model->calculaKM($os)->getKM($os);

	    $familia_dias_aberto_servico = $this->getFamiliaDiasAbertoServicos($os);
	    $posto_interno = $this->verificaPostoInterno($os);

        /*if (!$familia_dias_aberto_servico && $posto_interno == false) {
            throw new \Exception("A OS ".$this->getSuaOs($os)." não possui nenhum serviço realizado");
        }*/

	    if (is_array($familia_dias_aberto_servico)) {
		    $mobra = $this->getMobra($familia_dias_aberto_servico);
	    }else{
            $mobra = $this->getMobraConstatado($os);
        }

        if (!$mobra && $posto_interno == false) {
            throw new \Exception("A OS ".$this->getSuaOs($os)." não possui nenhum lançamento de mão de obra");
        }

	    $mao_de_obra = $mobra['mao_de_obra'];

	    if (!$mobra && $posto_interno == true) {
		    $mao_de_obra = 0;
	    }

            if (is_array($mobra) || $posto_interno == true) {
                
                $mo          = new MaoDeObra($os, $this->getFabrica());
                $mao_de_obra = $mo->calculaMaoDeObra($mao_de_obra, $os)->getMaoDeObra();

            }

            $valor_adicional = $this->_model->calculaValorAdicional($os);

        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }

        return $this;

    }
}
