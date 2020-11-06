<?php

namespace Posvenda;

use Posvenda\Model\Pedido as PedidoModel;
use Posvenda\Regras;

class Pedido
{

    private $_pedido;
    private $_pedido_item;
    private $_fabrica;
    public  $_model;
    private $_descricao_pedido;
    private $_posto;
    private $_tabela;
    private $_condicao_pagamento;
    private $_limite_minimo;
    private $_total_pedido;
    private $_admin;
    private $_param;

    public function __construct($fabrica, $pedido = null, $param = null)
    {
        $this->_fabrica = $fabrica;

        if ($param != null) {
            $this->_param = $param;
        }

        if ($pedido != null) {
            $this->_pedido = $pedido;

            $this->_model = new PedidoModel($this->_fabrica, $this->_pedido);
        } else {
            $this->_model = new PedidoModel($this->_fabrica);
        }
    }

    /**
     * Retorna o pedido
     *
     * @return integer
     */
    public function getPedido() {
        return $this->_pedido;
    }

    public function setPedido($pedido) {
        $this->_pedido = $pedido;
        $this->_model->setPedido($pedido);
    }
    /**
     * Retorna o pedido item
     *
     * @return integer
     */
    public function getPedidoItem() {
        return $this->_pedido_item;
    }

    /**
     * Inserir/Atualizar o Pedido
     *
     * @param array $dados
     * @param integer $pedido
     * @return Pedido
     */
    public function grava($dados, $pedido = null, $hd_chamado = null) {

        if(empty($pedido)) {
            if (!empty($hd_chamado) AND $this->_fabrica != 151) {
                $this->verificaPedidoHD($hd_chamado);
            }
            if (!strlen($dados["fabrica"]) && $pedido == null) {
                throw new \Exception("Fábrica e Pedido não informados para gravar o pedido");
            }

            if (!strlen($dados["posto"])) {
                throw new \Exception("Posto Autorizado não informado para gravar / alterar o pedido");
            }
            if (strlen($dados["tipo_pedido"]) > 0) {
                $this->_model->select("tbl_tipo_pedido")
                             ->setCampos(array("tipo_pedido"))
                             ->addWhere("tipo_pedido = {$dados['tipo_pedido']}")
                             ->addWhere(array("fabrica" => $this->_fabrica));
                if (!$this->_model->prepare()->execute()) {
                    throw new \Exception("Erro ao verificar o tipo de pedido para o pedido {$pedido}");
                } else if ($this->_model->getPDOStatement()->rowCount() == 0) {
                    throw new \Exception("Tipo de pedido não encontrado para o pedido {$pedido}");
                }
            } else {
                throw new \Exception("Tipo de pedido não informado para gravar / alterar o pedido {$pedido}");
            }

	    if (strlen($dados["linha"]) > 0){
		$linha = $dados["linha"];
	    }
            if(empty($dados["tabela"])){
                $dados["tabela"]     = $this->_model->getTabelaPreco($dados["posto"], $dados["tipo_pedido"], $os, $linha);
            }
        }

        if($dados['tabela'] == null){
              unset($dados['tabela']);
		    }

		    if($dados['condicao'] == null){
              unset($dados['condicao']);
        }

        if ($pedido == null) {
            if (!$this->_model->insertPedido($dados)) {
                throw new \Exception("Erro ao gravar pedido");
            } else {
                $this->_pedido = $this->_model->getPDO()->lastInsertId("seq_pedido");
                $pedido = $this->_pedido;
 
                if(!empty($dados['hd_chamado'])){
                    if($this->_fabrica != 151){
                        $this->_model->atualizaHdChamadoPedido($pedido,$dados['hd_chamado']);
                    }else{
                        $this->_model->insereHdChamadoItemsPedido($pedido,$dados['hd_chamado']);
                    }
                }
            }
        } else {
            if (isset($dados["posto"])) {
                unset($dados["posto"]);
            }

            if (isset($dados["fabrica"])) {
                unset($dados["fabrica"]);
            }

            if (!$this->_model->updatePedido($dados, $pedido)) {
                throw new \Exception("Erro ao atualizar pedido {$pedido}");
            }
        }
        return $pedido;

    }


    /**
     * Inserir/Atualizar o Pedido B2B
     *
     * @param array $dados
     * @param integer $pedido
     * @return Pedido
     */
    public function gravaPedidoB2B($dados) {

        if (!strlen($dados["fabrica"]) && $pedido == null) {
            throw new \Exception("Fábrica e Pedido não informados para gravar o pedido");
        }

        if (!strlen($dados["posto"])) {
            throw new \Exception("Posto Autorizado não informado para gravar / alterar o pedido");
        }

        if (strlen($dados["tipo_pedido"]) > 0) {
            $this->_model->select("tbl_tipo_pedido")
                         ->setCampos(array("tipo_pedido"))
                         ->addWhere("tipo_pedido = {$dados['tipo_pedido']}")
                         ->addWhere(array("fabrica" => $this->_fabrica));
            if (!$this->_model->prepare()->execute()) {
                throw new \Exception("Erro ao verificar o tipo de pedido para o pedido {$pedido}");
            }
        } else {
            throw new \Exception("Tipo de pedido não informado para gravar / alterar o pedido {$pedido}");
        }

        if ($dados['tabela'] == null) {
              unset($dados['tabela']);
        }
        if (!$this->_model->insertPedido($dados)) {
            throw new \Exception("Erro ao gravar pedido");
        } else {
            $this->_pedido = $this->_model->getPDO()->lastInsertId("seq_pedido");
            $pedido = $this->_pedido;
            
        }

        return $pedido;

    }

    /**
     * Inserir/Atualizar o Item do Pedido B2B
     *
     * @param array $dadosItens
     * @return Pedido
     */
    public function gravaItemPedidoB2B($dadosItens, $pedido) {

        if (empty($this->_pedido) && empty($pedido)) {
            return array("erro" => true, "msg" => "Pedido não informado para gravar o item do pedido");
        }

        if(!empty($pedido)){
            $this->_pedido = $pedido;
        }

        if (!$this->_model->insertPedidoItem($dadosItens)) {
            return array("erro" => true, "msg" => "Erro ao gravar item do pedido: peça - ".$dadosItens["peca"]);
        } else {
            $this->_pedido_item = $this->_model->getPDO()->lastInsertId("seq_pedido_item");
        }

        return array("erro" => false, "msg" => "Item do pedido gravado com sucesso: peça - ".$dadosItens["peca"]);
    }




    /**
     * Inserir/Atualizar o Item do Pedido
     *
     * @param array $dadosItens
     * @return Pedido
     */
    public function gravaItem($dadosItens, $pedido) {
        if (empty($this->_pedido) && empty($pedido)) {
            throw new \Exception("Pedido não informado para gravar o item do pedido");
        }

        if(!empty($pedido)){
            $this->_pedido = $pedido;
        }

        $this->_model->select("tbl_pedido")
					 ->setCampos(array("exportado"))
					 ->addWhere(array("tbl_pedido.pedido" => $pedido));

    		$this->_model->prepare()->execute();
    		if ($this->_model->getPDOStatement()->rowCount() > 0) {
    			$res = $this->_model->getPDOStatement()->fetch();
    			if(!empty($res['exportado']) && $this->_fabrica != 147) {
    				throw new \Exception("Pedido já exportado");
    			}
    		}

        if (count($dadosItens) > 0) {
            foreach ($dadosItens as $dadosItem) {
                try {

                    if ($this->_fabrica == 183){
                        $nota_fiscal_posto_pedido = $dadosItem["nota_fiscal_posto_pedido"];
                        unset($dadosItem["nota_fiscal_posto_pedido"]);
                    }

                    if (!isset($dadosItem["peca_referencia"]) && isset($dadosItem["peca"])) {
                        $this->_model->select("tbl_peca")
                                     ->setCampos(array("referencia"))
                                     ->addWhere(array("fabrica" => $this->_fabrica))
                                     ->addWhere(array("peca" => $dadosItem["peca"]));
                        $this->_model->prepare()->execute();

                        $res = $this->_model->getPDOStatement()->fetch();

                        $peca_referencia = $res["referencia"];
                        $dadosItem["peca_referencia"] = $peca_referencia;
                    } else {
                        $peca_referencia = $dadosItem["peca_referencia"];
                    }

                    if(!isset($dadosItem["peca"])){
                        if (empty($dadosItem["peca_referencia"])) {
                            throw new \Exception("Peça sem referência para gravar o pedido");
                        } else {
                            $this->_model->select("tbl_peca")
                                         ->setCampos(array("peca"))
                                         ->addWhere(array("referencia" => $dadosItem["peca_referencia"]))
                                         ->addWhere(array("fabrica" => $this->_fabrica));
                            if (!$this->_model->prepare()->execute()) {
                                throw new \Exception("Erro ao gravar item do pedido: falha ao verificar a peça {$peca_referencia}");
                            } else if ($this->_model->getPDOStatement()->rowCount() == 0) {
                                throw new \Exception("Erro ao gravar item do pedido: peça {$peca_referencia} não foi encontrada");
                            } else {
                                $res = $this->_model->getPDOStatement()->fetch();

                                $dadosItem["peca"] = $res["peca"];
                            }
                        }
                    }

                    $this->_model->select("tbl_peca_fora_linha")
                                 ->setCampos(array("tbl_peca.referencia"))
                                 ->addJoin(array("tbl_peca " => "ON tbl_peca.peca = tbl_peca_fora_linha.peca"))
                                 ->addWhere(array("tbl_peca_fora_linha.fabrica" => $this->_fabrica))
                                 ->addWhere(array("tbl_peca_fora_linha.peca" => $dadosItem["peca"]));

                    if (!$this->_model->prepare()->execute()) {
                        throw new \Exception("Erro ao verificar peça fora de linha");
                    } else if ($this->_model->getPDOStatement()->rowCount() > 0) {
                        $res = $this->_model->getPDOStatement()->fetch();
                        throw new \EXception("A peça {$dadosItem['peca_referencia']} está fora de linha.");
                    }

                    if (strlen($dadosItem["preco"]) == 0 || !is_numeric($dadosItem["preco"])) {
                        throw new \Exception("Erro ao gravar item do pedido: peça {$peca_referencia} está com o preço inválido");
                    }

                    if (empty($dadosItem["qtde"])) {
                        throw new \Exception("Erro ao gravar item do pedido: peça {$peca_referencia} quantidade não informada");
                    } else if (!is_numeric($dadosItem["qtde"])) {
                        throw new \Exception("Erro ao gravar item do pedido: peça {$peca_referencia} quantidade inválida");
                    }

                    $pedido_item = $dadosItem["pedido_item"];
                    unset($dadosItem["peca_referencia"], $dadosItem["pedido_item"]);

                    if (empty($dadosItem["total_item"])) {
                        $dadosItem["total_item"] = $dadosItem["preco"] * $dadosItem["qtde"];
                    }

		            $dadosItem["pedido"] = $this->_pedido;
                    $dadosItem["ipi"] = (strlen($dadosItem["ipi"]) == 0 ) ? "0": $dadosItem["ipi"];

		            if (!strlen($dadosItem["total_item"])) {
                        throw new \Exception("Erro ao gravar item do pedido: peça {$peca_referencia} total do item não informado");
                    }

                    if ($pedido_item == null || empty($pedido_item)) {
                        if (!$this->_model->insertPedidoItem($dadosItem)) {
                            throw new \Exception("Erro ao gravar item do pedido: peça {$peca_referencia}");
                        } else {
                            $this->_pedido_item = $this->_model->getPDO()->lastInsertId("seq_pedido_item");
                        }

                        if ($this->_fabrica == 183 AND !empty($nota_fiscal_posto_pedido)){
                            $dados = array("nota_fiscal_produto" => "'$nota_fiscal_posto_pedido'", "pedido_item" => $this->_pedido_item);
                            $this->_model->insert("tbl_nf_produto_pedido_item")->setCampos($dados);

                            if (!$this->_model->prepare()->execute()) {
                                throw new \Exception("Erro ao gravar item do produto pedido: peça {$peca_referencia}");
                            }
                        }

                    } else {

                        if (isset($dadosItem["pedido"])) {
                            unset($dadosItem["pedido"]);
                        }

                        if (isset($dadosItem["peca"])) {
                            unset($dadosItem["peca"]);
                        }

                        if (!$this->_model->updatePedidoItem($dadosItem, $pedido_item)) {
                            throw new \Exception("Erro ao atualizar item do pedido: peça {$peca_referencia}");
                        }
                    }
		    } catch(\Exception $e) {
                    throw new \Exception($e->getMessage());
                }
            }

            $this->totalizaPedido();
        } else {
            throw new \Exception("Não foram informadas peças para realizar o pedido {$pedido}");
        }

        return $this;
    }

    // Observação peça alternativa Einhell
    public function gravaObsPecaAlternativa($peca, $peca_alternativa, $pedido_item, $os , $os_item){
        $pdo = $this->_model->getPDO();

        $sql_referencia_alternativa = "SELECT referencia FROM tbl_peca WHERE fabrica = $this->_fabrica AND peca = $peca_alternativa";
        $query = $pdo->query($sql_referencia_alternativa);
        $res = $query->fetchAll();

        foreach($res as $dados){
          $referencia_alternativa = $dados['referencia'];
        }

		if(!empty($os_item)) {
			$cond_os_item  = " and os_item = $os_item ";
		}
        $peca_id = $peca['peca'];
        $obs_alternativa = "A peça ".$peca['referencia']." será atendida com a peça $referencia_alternativa";

        $sql_os_item = "UPDATE tbl_os_item
						SET obs = '$obs_alternativa'
						FROM tbl_os_produto
						WHERE tbl_os_produto.os = $os
						AND tbl_os_produto.os_produto = tbl_os_item.os_produto
						AND peca = $peca_id  
                        AND fabrica_i = $this->_fabrica
						$cond_os_item
                        ";
        $query = $pdo->query($sql_os_item);

        $sql_pedido_item = "UPDATE tbl_pedido_item
                            SET obs = '$obs_alternativa'
                            WHERE pedido_item = $pedido_item";
        $query = $pdo->query($sql_pedido_item);

        return true;      
    }

    //feito para einhell
    public function IntervencaoPedido($pedido){
        $itens = $this->_model->PegaPedidoItem($this->_pedido);

        $intervencao = false;

        foreach($itens as $item){
            if($item['qtde'] > 1){
                $intervencao = true;
            }
        }

        return $intervencao;
    }

    /**
     * Totalização do pedido
     *
     * @return Pedido
     */
    public function totalizaPedido() {
            $total_pedido = $this->_model->somaTotalPedido($this->_pedido);

            if (!empty($total_pedido)) {
                $this->_model->updatePedido(array("total" => $total_pedido), $this->_pedido);
            }

        return $this;
    }


    //método criado para einhell
    public function getValorPedido($pedido){
        $this->_model->select("tbl_pedido")
                     ->setCampos(array("tbl_pedido.total, tbl_pedido.tipo_frete"))
                     ->addWhere(array("tbl_pedido.pedido" => $pedido));
        $this->_model->prepare()->execute();
        $resultado = $this->_model->getPDOStatement()->fetch();

        return $resultado;
    }


    /**
     * Verifica se a condição de pagamento possui valor minimo
     *
     * @return Pedido
     */
    public function verificaValorMinimo() {
        $valor_minimo = $this->_model->getValorMinimo($this->_pedido);
        $total_pedido = $this->_model->getTotalPedido($this->_pedido);

        if (!empty($valor_minimo) && $total_pedido < $valor_minimo) {
            throw new \Exception("O valor do pedido não pode ser menor que ".$this->formatMoney($valor_minimo). " para esta condição de pagamento");
        }

        return $this;
    }
     /**
     * Verifica se a total do pedido passou  crédito do posto
     *
     * @return Pedido
     */
    public function verificaCredito($pedido,$posto) {
        $valor_minimo = $this->_model->getCredito($posto);
        $total_pedido = $this->_model->getTotalPedido($pedido);
        // throw new \Exception("Error Processing Request".var_dump($total_pedido,$valor_minimo));

        if (!empty($valor_minimo) && $total_pedido > $valor_minimo) {
            throw new \Exception("O valor total do pedido ".$this->formatMoney($total_pedido)." é maior que o límite crédito ".$this->formatMoney($valor_minimo));
        }

        return $this;
    }

    public function verificaDepara($peca,$referencia) {

        $this->_model->select("tbl_depara")
                     ->setCampos(array("peca_para.referencia AS peca_para_referencia"))
                     ->addJoin(array("tbl_peca AS peca_para" => "ON peca_para.peca = tbl_depara.peca_para AND peca_para.fabrica = {$this->_fabrica}"))
                     ->addWhere(array("tbl_depara.fabrica" => $this->_fabrica))
                     ->addWhere("(tbl_depara.expira IS NULL OR tbl_depara.expira > CURRENT_TIMESTAMP)")
                     ->addWhere(array("tbl_depara.peca_de" => $peca));

        if (!$this->_model->prepare()->execute()) {
            return "Erro ao gravar item do pedido: falha ao veriricar de > para da peça {$referencia}" ;
        } else if ($this->_model->getPDOStatement()->rowCount() > 0) {
            $res       = $this->_model->getPDOStatement()->fetch();
            $peca_para = $res["peca_para_referencia"];

            return "Erro ao gravar item do pedido: A peça {$referencia} mudou para {$peca_para}, por favor substitua a peça." ;
        }
        return true;
    }

    /**
     * Coloca pedido em auditoria
     *
     * @return Pedido
     */
    public function auditoria() {
        #Verifica se a fábrica possui auditoria de pedido
        $auditoria_pedido = Regras::get("auditoria", "pedido_venda", $this->_fabrica);

        if ($auditoria_pedido == true) {
            #Pega o status de auditoria de pedido da fábrica
            $status_auditoria = Regras::get("status_auditoria", "pedido_venda", $this->_fabrica);

            if (!$this->_model->auditoria($status_auditoria, $this->_pedido)) {
                return "Erro ao colocar pedido em auditoria";
            }
        } else {
            throw new \Exception("Fábrica não possui auditoria de pedido configurada");
        }
    }

    public function auditoria_bonificacao($pedido) { 
        if (!$this->_model->auditoria(18, $pedido)) {
            throw new \Exception("Erro ao colocar pedido em auditoria");
        }
    }

    /**
     * Este metodo é usado somente em rotinas de geração de pedido
     */
    public function finaliza($pedido)
    {
        if (empty($pedido)) {
            throw new \Exception("Pedido não informado para realizar a finalização");
        }

        $this->_model->select("tbl_pedido_item")
                     ->setCampos(array("pedido"))
                     ->addWhere(array("tbl_pedido_item.pedido" => $pedido));
        $this->_model->prepare()->execute();
        $resultado = $this->_model->getPDOStatement()->fetch();

        if (empty($resultado)) {
            throw new \Exception("Pedido $pedido sem nenhum item cadastrado");
        }

        $this->totalizaPedido();
        $pdo = $this->_model->getPDO();

        $sql = "
            UPDATE tbl_pedido
            SET finalizado = CURRENT_TIMESTAMP
            WHERE fabrica = {$this->_fabrica}
            AND pedido = {$pedido}
        ";

	$query = $pdo->query($sql);

	return $query;

    }

    public function atualizaOsItemPedidoItem($os_item, $pedido, $pedido_item, $fabrica, $con = ""){

        $pdo = $this->_model->getPDO();

        $sql = "SELECT fn_atualiza_os_item_pedido_item($os_item,$pedido,$pedido_item,$fabrica)";

        $query = $pdo->query($sql);

        if (!$query) {
            $erro = $pdo->errorInfo();

            throw new \Exception($erro[2]);
        }
    }

    private function formatMoney($RS){

        return number_format($RS, 2, ",", ".");

    }

    /**
     * Resgata a condição de pagamento garantia
     *
     * @return integer
     */
    public function getCondicaoGarantia()
    {

        if (empty($this->_fabrica)) {
            throw new \Exception("Fabrica não informada para selecionar as condições de garantia");
        }

        $condicao = Regras::get("condicao_garantia", "pedido_garantia", $this->_fabrica);

      	if (empty($condicao)) {
      		$condicao = "GARANTIA";
      	}

        $this->_model->select('tbl_condicao')
             ->setCampos(array('condicao'))
             ->addWhere(array('fabrica' => $this->_fabrica))
             ->addWhere(" UPPER(descricao) = '$condicao' ");

        if (!$this->_model->prepare()->execute()) {
            throw new \Exception("Erro ao selecionar a condição de pagamento de garantia");
        }
        $res = $this->_model->getPDOStatement()->fetch();

        if (!empty($res["condicao"])) {
            $condicao = $res["condicao"];
        }
        
        return $condicao;
    }

    /**
     * Resgata a condição de reposição estoque, Bonificação
     *
     * @return integer
     */
    public function getCondicaoEstoque()
    {
        if (empty($this->_fabrica)) {
            throw new \Exception("Fabrica não informada para selecionar as condições de garantia");
        }

        $condicao = "BONIFICACAO";


        $this->_model->select('tbl_condicao')
             ->setCampos(array('condicao'))
             ->addWhere(array('fabrica' => $this->_fabrica))
             ->addWhere(" fn_retira_especiais(UPPER(descricao)) = '$condicao' "   );
        if (!$this->_model->prepare()->execute()) {
            throw new \Exception("Erro ao selecionar a condição de pagamento de garantia");
        }
        $res = $this->_model->getPDOStatement()->fetch();

        if (!empty($res["condicao"])) {
            $condicao = $res["condicao"];
        }

        return $condicao;
    }

    /**
     * Resgata o tipo de pedido garantia
     *
     * @return integer
     */
    public function getTipoPedidoGarantia($descricao = null, $codigo = null)
    {
        if (empty($this->_fabrica)) {
            throw new \Exception("Fabrica não informada para selecionar o tipo de pedido garantia");
        }

        if ($descricao == null) {
            $descricao = "GARANTIA";
        }

        $this->_model->select('tbl_tipo_pedido')
             ->setCampos(array('tipo_pedido'))
             ->addWhere(array('fabrica' => $this->_fabrica));

        if (is_null($codigo) && !is_null($descricao)) {
            $this->_model->addWhere("UPPER(descricao) = '{$descricao}'");
        } elseif (!is_null($codigo)) {
            $this->_model->addWhere("UPPER(codigo) = '{$codigo}'");
        }

        if (!$this->_model->prepare()->execute()) {
            throw new \Exception("Erro ao selecionar o tipo de pedido garantia");
        }

        $res = $this->_model->getPDOStatement()->fetch();

        if (!empty($res["tipo_pedido"])) {
            $tipo_pedido = $res["tipo_pedido"];
        }

        return $tipo_pedido;
    }

    public function getTipoPedidoGarantiaAntecipada()
    {
        if (empty($this->_fabrica)) {
            throw new \Exception("Fabrica não informada para selecionar o tipo de garantia antecipada");
        }

        $this->_model->select('tbl_tipo_pedido')
             ->setCampos(array('tipo_pedido'))
             ->addWhere(array('fabrica' => $this->_fabrica))
             ->addWhere(array('garantia_antecipada' => true));

        if (!$this->_model->prepare()->execute()) {
            throw new \Exception("Erro ao selecionar o tipo de pedido garantia antecipada");
        }

        $res = $this->_model->getPDOStatement()->fetch();

        if (!empty($res["tipo_pedido"])) {
            $tipo_pedido = $res["tipo_pedido"];
        }

        return $tipo_pedido;
    }

    public function getCondicaoPagamentoGarantia()
    {
        if (empty($this->_fabrica)) {
            throw new \Exception("Fabrica não informada para selecionar a condição de pagamento em garantia");
        }

        $this->_model->select('tbl_condicao')
             ->setCampos(array('condicao'))
             ->addWhere(array('fabrica' => $this->_fabrica))
             ->addWhere(array('descricao' => 'GARANTIA'));

             // throw new \Exception($this->_model->prepare()->getQuery());
        if (!$this->_model->prepare()->execute()) {
            throw new \Exception("Erro ao selecionar a condicao de garantia antecipada");
        }

        $res = $this->_model->getPDOStatement()->fetch();

        if (!empty($res["condicao"])) {
            $condicao = $res["condicao"];
        }

        return $condicao;
    }


    public function verificaPostoInterno($posto)
    {
        if (!empty($posto)){
            $this->_model->select("tbl_posto_fabrica")
                ->setCampos(array("tbl_tipo_posto.tipo_posto"))
                ->addJoin(array("tbl_tipo_posto" => "ON tbl_tipo_posto.tipo_posto = tbl_posto_fabrica.tipo_posto"))
                ->addWhere(array("tbl_posto_fabrica.fabrica" => $this->_fabrica))
                ->addWhere(array("tbl_posto_fabrica.posto" => $posto))
                ->addWhere(array("tbl_tipo_posto.posto_interno" => "true"));

            if (!$this->_model->prepare()->execute()) {
                throw new \Exception("Erro ao verificar posto interno para o posto {$posto}");
            }

            if($this->_model->getPDOStatement()->rowCount() > 0 ){
                return true;
            }else{
                return false;
            }

        }
    }

    public function verificaTabelaAlterada($os) {
        $osClass = new \Posvenda\Os($this->_fabrica);

        $informacoesOs = $osClass->getInformacoesOs($os);

        $posto   = $informacoesOs["posto"];
        $produto = $informacoesOs["produto"];

        $linha   = $osClass->getProdutoLinha($produto);

        $this->_model->select("tbl_posto_linha")
                     ->setCampos(array("tbl_posto_linha.ativo"))
                     ->addJoin(array("tbl_tabela" => "ON tbl_tabela.tabela = tbl_posto_linha.tabela"))
                     ->addWhere(array("tbl_posto_linha.posto" => $posto))
                     ->addWhere(array("tbl_posto_linha.linha" => $linha))
                     ->addWhere(array("tbl_tabela.fabrica" => $this->_fabrica));

        if (!$this->_model->prepare()->execute()) {
            throw new \Exception("Erro ao buscar tabela de garantia para a OS {$os}");
        } else {
            if ($this->_model->getPDOStatement()->rowCount() == 0) {
                return "t";
            } else {
                return false;
            }
        }             

    }

    public function getPrecoPecaGarantia($peca, $os, $tabela_padrao = null) {
        if (empty($peca)) {
            throw new \Exception("Informe a peça da Ordem de Serviço");
        }

        if (empty($os)) {
            throw new \Exception("Informe a ordem de serviço");
        } else {
            $osClass = new \Posvenda\Os($this->_fabrica);

            $informacoesOs = $osClass->getInformacoesOs($os);

            $posto   = $informacoesOs["posto"];
            $produto = $informacoesOs["produto"];

            if (empty($produto)) {
                $informacoesOsProduto = $osClass->getInformacoesOsProduto($os);
                $produto = $informacoesOsProduto[0]["produto"];
            }
        }

        $linha = $osClass->getProdutoLinha($produto);

        $this->_model->select("tbl_posto_linha")
                     ->setCampos(array("tbl_tabela.tabela"))
                     ->addJoin(array("tbl_tabela" => "ON tbl_tabela.tabela = tbl_posto_linha.tabela"))
                     ->addWhere(array("tbl_posto_linha.posto" => $posto))
                     ->addWhere(array("tbl_posto_linha.linha" => $linha))
                     ->addWhere("tbl_posto_linha.ativo IS TRUE")
                     ->addWhere(array("tbl_tabela.fabrica" => $this->_fabrica));

        if (!$this->_model->prepare()->execute()) {
            throw new \Exception("Erro ao buscar tabela de garantia para a OS {$os}");
        } else if ($this->_model->getPDOStatement()->rowCount() == 0) {

            //Caso tabela foi alterada depois de abri a os
            if (!empty($tabela_padrao)) {
                $res = $this->_model->getPDOStatement()->fetch();
                $tabela = $tabela_padrao;
            } else {
                throw new \Exception("Tabela de garantia não encontrada para a OS {$os}");
            }
        } else {
            $res = $this->_model->getPDOStatement()->fetch();

            $tabela = $res["tabela"];
        }

        $tabelaTroca = Regras::get("verifica_tabela_troca", "pedido_garantia", $this->_fabrica);

        $this->_model->select("tbl_peca")
                     ->setCampos(array("referencia", "produto_acabado"))
                     ->addWhere(array("fabrica" => $this->_fabrica))
                     ->addwhere(array("peca" => $peca));
        $this->_model->prepare()->execute();

        $res = $this->_model->getPDOStatement()->fetch();
        $referencia = $res["referencia"];
        $produto_acabado = $res["produto_acabado"];

        if ($tabelaTroca == true && $produto_acabado == "t") {
            $preco = $this->verificaPrecoTroca($peca);
        } else {
            $this->_model->select("tbl_tabela")
                         ->setCampos(array("tbl_tabela_item.preco"))
                         ->addJoin(array("tbl_tabela_item" => "ON tbl_tabela_item.tabela = tbl_tabela.tabela"))
                         ->addWhere(array("tbl_tabela.fabrica" => $this->_fabrica))
                         ->addWhere(array("tbl_tabela.tabela" => $tabela))
                         ->addWhere(array("tbl_tabela_item.peca" => $peca));

            if (!$this->_model->prepare()->execute()) {
                throw new \Exception("Erro ao selecionar o preço da peça para a OS {$os}");
            }

            $res = $this->_model->getPDOStatement()->fetch();

            $preco = $res["preco"];
        }

        if (strlen($preco) == 0 ) {
            $nao_gera_pedido_peca_sem_preco = Regras::get("nao_gera_pedido_peca_sem_preco", "pedido_garantia", $this->_fabrica);
            $nao_gera_pedido_peca_sem_preco_troca = Regras::get("nao_gera_pedido_peca_sem_preco_troca", "pedido_garantia", $this->_fabrica);

            if (($nao_gera_pedido_peca_sem_preco == true && $produto_acabado !== true) or $nao_gera_pedido_peca_sem_preco_troca == true) {
                throw new \Exception("Erro ao gerar pedido para a OS {$os}, peça {$referencia} sem preço");
            }

            $pecaPreco = Regras::get("peca_sem_preco", "pedido_garantia", $this->_fabrica);

            if(empty($pecaPreco)){
                $pecaPreco = 10000;
            }

            $dados = array(
                "tabela" => $tabela,
                "peca" => $peca,
                "preco" => $pecaPreco
            );


            $this->_model->insert("tbl_tabela_item")->setCampos($dados);

            if (!$this->_model->prepare()->execute()) {
                throw new \Exception("Erro ao inserir o preço da peça {$peca} na Tabela Item");
            } else {
                return 10000;
            }
        }

        return $preco;
    }

    public function verificaPrecoTroca($peca){
	    $this->_model->select("tbl_tabela_item")
		    ->setCampos(array("tbl_tabela_item.preco"))
		    ->addJoin(array("tbl_tabela" => " ON tbl_tabela.tabela = tbl_tabela_item.tabela"))
		    ->addWhere(array("tbl_tabela_item.peca" => $peca))
		    ->addWhere("tbl_tabela.descricao ~* 'Troca'");
	    if (!$this->_model->prepare()->execute()) {
		throw new \Exception("item {$peca} sem preço");
	    }else{
		$res = $this->_model->getPDOStatement()->fetch();
		return $res["preco"];
	    }
    }

    public function verificaPreco($peca,$tabela){
	    $this->_model->select("tbl_tabela_item")
		    ->setCampos(array("tbl_tabela_item.preco"))
		    ->addJoin(array("tbl_tabela" => " ON tbl_tabela.tabela = tbl_tabela_item.tabela"))
		    ->addWhere(array("tbl_tabela_item.peca" => $peca))
		    ->addWhere(array("tbl_tabela_item.tabela" => $tabela));
	    if (!$this->_model->prepare()->execute()) {
		throw new \Exception("item {$peca} sem preço");
	    }else{
		$res = $this->_model->getPDOStatement()->fetch();
		return $res["preco"];
	    }
    }


    public function getPrecoPecaGarantiaAntecipada($peca, $posto, $tabela = null,$peca_sem_preco = false){

        if (empty($peca)) {
            throw new \Exception("Informe a peça do Pedido para selecionar o preço da garantia antecipada");
        }

        if (empty($posto)) {
            throw new \Exception("Informe o posto do Pedido para selecionar o preço da garantia antecipada");
        }

        if (in_array($this->_fabrica, array(74))){ //hd_chamado=2782600
            $cond = 'tbl_posto_linha.tabela_bonificacao';
        }else{
            $cond = 'tbl_posto_linha.tabela';
        }
        if ($tabela == null) {
            $this->_model->select("tbl_posto_linha")
                         ->setCampos(array("tbl_tabela.tabela"))
                         ->addJoin(array("tbl_tabela" => "ON tbl_tabela.tabela = $cond"))
                         ->addWhere(array("tbl_posto_linha.posto" => $posto))
                         ->addWhere("tbl_posto_linha.ativo IS TRUE")
                         ->addWhere(array("tbl_tabela.tabela_garantia" => true))
                         ->addWhere(array("tbl_tabela.fabrica" => $this->_fabrica));

            if (!$this->_model->prepare()->execute()) {
                throw new \Exception("Erro ao buscar tabela de garantia para a peça {$peca} e posto {$posto}");
            } else if ($this->_model->getPDOStatement()->rowCount() == 0) {
                throw new \Exception("Tabela de garantia não encontrada para a peça {$peca} e posto {$posto}");
            } else {
                $res = $this->_model->getPDOStatement()->fetch();
                $tabela = $res["tabela"];
            }
        }

        $this->_model->select("tbl_tabela")
                     ->setCampos(array("tbl_tabela_item.preco"))
                     ->addJoin(array("tbl_tabela_item" => "ON tbl_tabela_item.tabela = tbl_tabela.tabela"))
                     ->addWhere(array("tbl_tabela.fabrica" => $this->_fabrica))
                     ->addWhere(array("tbl_tabela.tabela" => $tabela))
                     ->addWhere(array("tbl_tabela_item.peca" => $peca));

        if (!$this->_model->prepare()->execute()) {
            throw new \Exception("Erro ao pegar o preço da peça {$peca}");
        }

        $res = $this->_model->getPDOStatement()->fetch();

        $preco = $res["preco"];

        if(empty($preco)){
            $this->_model->select("tbl_peca")
                         ->setCampos(array("tbl_peca.referencia", "tbl_peca.descricao"))
                         ->addWhere(array("tbl_peca.fabrica" => $this->_fabrica))
                         ->addWhere(array("tbl_peca.peca" => $peca));
            if (!$this->_model->prepare()->execute()) {
                throw new \Exception("Erro ao buscar dados da peça {$peca}");
            }
            $dadosPeca = $this->_model->getPDOStatement()->fetch();

            if ($peca_sem_preco) {
              return $preco;
            } else {
              throw new \Exception("A Peça {$dadosPeca['referencia']} - {$dadosPeca['descricao']} está sem preço");
            }
        }else{
            return $preco;
        }

    }

    public function getPrecoPecaRecompra($peca, $os) {
        if (empty($peca)) {
            throw new \Exception("Informe a peça da Ordem de Serviço para selecionar o preço de recompra");
        }

        if (empty($os)) {
            throw new \Exception("Informe a ordem de serviço para selecionar o preço de recompra");
        } else {
            $osClass = new \Posvenda\Os($this->_fabrica);

            $informacoesOs = $osClass->getInformacoesOs($os);

            $posto   = $informacoesOs["posto"];
            $produto = $informacoesOs["produto"];

            if (empty($produto)) {
                $informacoesOsProduto = $osClass->getInformacoesOsProduto($os);
                $produto = $informacoesOsProduto[0]["produto"];
            }
        }

        $linha = $osClass->getProdutoLinha($produto);

        $this->_model->select("tbl_posto_linha")
                     ->setCampos(array("tbl_tabela.tabela"))
                     ->addJoin(array("tbl_tabela" => "ON tbl_tabela.tabela = tbl_posto_linha.tabela_posto"))
                     ->addWhere(array("tbl_posto_linha.posto" => $posto))
                     ->addWhere(array("tbl_posto_linha.linha" => $linha))
                     ->addWhere("tbl_posto_linha.ativo IS TRUE")
                     ->addWhere(array("tbl_tabela.fabrica" => $this->_fabrica));

        if (!$this->_model->prepare()->execute()) {
            throw new \Exception("Erro ao selecionar tabela de preço para o posto {$posto} e linha {$linha}");
        } else if ($this->_model->getPDOStatement()->rowCount() == 0) {
            throw new \Exception("Tabela de preço não encontrada para o posto {$posto} e linha {$linha}");
        } else {
            $res = $this->_model->getPDOStatement()->fetch();

            $tabela = $res["tabela"];
        }

        $this->_model->select("tbl_tabela")
                     ->setCampos(array("tbl_tabela_item.preco"))
                     ->addJoin(array("tbl_tabela_item" => "ON tbl_tabela_item.tabela = tbl_tabela.tabela"))
                     ->addWhere(array("tbl_tabela.fabrica" => $this->_fabrica))
                     ->addWhere(array("tbl_tabela.tabela" => $tabela))
                     ->addWhere(array("tbl_tabela_item.peca" => $peca));

        if (!$this->_model->prepare()->execute()) {
            throw new \Exception("Erro ao pegar o preço da peça {$peca} na tabela {$tabela}");
        }

        $res = $this->_model->getPDOStatement()->fetch();

        $preco = $res["preco"];

        if (empty($preco)) {
            throw new \Exception("A Peça {$peca} está sem preço informado na tabela {$tabela}");
        }

        return $preco;
    }

    public function getPedidoNaoExportado($pedido = null){

        $this->_model->select("tbl_tipo_pedido")
                     ->setCampos(array("tbl_pedido.pedido", "tbl_pedido.tabela", "tbl_tipo_pedido.tipo_pedido", "tbl_tipo_pedido.descricao"))
                     ->addJoin(array("tbl_pedido " => "ON tbl_pedido.tipo_pedido = tbl_tipo_pedido.tipo_pedido  AND tbl_pedido.exportado IS NULL AND tbl_pedido.finalizado IS NOT NULL"))
             ->addWhere(array("tbl_pedido.fabrica" => $this->_fabrica))
             ->addWhere("tbl_pedido.posto <> 6359")
             ->addWhere(array("tbl_pedido.status_pedido" => 1));

        if ($pedido != null) {
            $this->_model->addWhere(array("tbl_pedido.pedido" => $pedido));
        }


        if (!$this->_model->prepare()->execute()) {
            throw new \Exception("Erro ao buscar pedidos não exportados");
        } else if ($this->_model->getPDOStatement()->rowCount() > 0) {
            return $this->_model->getPDOStatement()->fetchAll();
        }
    }

    public function getVerificaPecaProduto($pedido){
        $this->_model->select("tbl_pedido_item")
                     ->setCampos(array("tbl_peca.produto_acabado"))
                     ->addJoin(array("tbl_peca " => "ON tbl_peca.peca = tbl_pedido_item.peca AND tbl_peca.fabrica = {$this->_fabrica}"))
                     ->addWhere(array("tbl_pedido_item.pedido" => $pedido))
                     ->addWhere(array("tbl_peca.produto_acabado" => "t"));

        if (!$this->_model->prepare()->execute()) {
            throw new \Exception("Erro ao verificar as peças do produto para o pedido {$pedido}");
        } else if ($this->_model->getPDOStatement()->rowCount() > 0) {
            return true;
        }else{
            return false;
        }
    }

    public function getInformacaoPedido($pedido){
        $this->_model->select("tbl_pedido")
                     ->setCampos(array("tbl_pedido.pedido","tbl_pedido.data AS data_pedido","tbl_posto.cnpj","regexp_replace(tbl_pedido.pedido_cliente,'\\s+$','') as pedido_cliente","tbl_pedido.seu_pedido","tbl_pedido.tipo_frete","to_char(tbl_pedido.data,'DD/MM/YYYY') AS data","tbl_pedido.entrega","tbl_pedido.tabela","tbl_tabela.sigla_tabela","tbl_condicao.condicao","tbl_posto_fabrica.transportadora_nome","tbl_posto_fabrica.parametros_adicionais AS parametros_adicionais_posto","tbl_tipo_pedido.descricao AS tipo_pedido", "tbl_tipo_pedido.pedido_faturado","tbl_condicao.codigo_condicao","tbl_pedido.atende_pedido_faturado_parcial", "tbl_pedido.tipo_pedido AS tipo_pedido_id"))
                     ->addJoin(array("tbl_posto " => "USING (posto)"))
                     ->addJoin(array("tbl_posto_fabrica " => "ON tbl_posto_fabrica.posto = tbl_pedido.posto AND  tbl_posto_fabrica.fabrica = {$this->_fabrica}"))
                     ->addJoin(array("tbl_tipo_pedido " => "ON tbl_tipo_pedido.tipo_pedido = tbl_pedido.tipo_pedido AND tbl_tipo_pedido.fabrica = {$this->_fabrica}"))
                     ->addJoin(array("tbl_condicao " => "USING (condicao)"))
                     ->addJoin(array("tbl_tabela " => "ON tbl_pedido.tabela = tbl_tabela.tabela"))
                     ->addWhere(array("tbl_pedido.fabrica" => $this->_fabrica))
                     ->addWhere(array("tbl_pedido.pedido" => $pedido));

        if (!$this->_model->prepare()->execute()) {
            throw new \Exception("Erro ao selecionar as informações do pedido {$pedido}");
        } else if ($this->_model->getPDOStatement()->rowCount() > 0) {
            return $this->_model->getPDOStatement()->fetch();
        }
    }

    public function getDadosCliente($pedido, $tipo_pedido = null) {

        if ($tipo_pedido == "CT" && $this->_fabrica == 151) {
            $this->_model->select("tbl_pedido")
             ->setCampos(array( 'tbl_hd_chamado_extra.nome',
                                'tbl_hd_chamado_extra.endereco',
                                'tbl_hd_chamado_extra.numero',
                                'tbl_hd_chamado_extra.complemento',
                                'tbl_hd_chamado_extra.bairro',
                                'tbl_hd_chamado_extra.cep',
                                'tbl_hd_chamado_extra.fone',
                                'tbl_hd_chamado_extra.fone2',
                                'tbl_hd_chamado_extra.email',
                                'tbl_hd_chamado_extra.cpf',
                                'tbl_hd_chamado_extra.rg',
                                'tbl_hd_chamado_extra.celular',
                                'tbl_cidade.nome AS cidade',
                                'tbl_cidade.estado'))
             ->addJoin(array(   'tbl_hd_chamado_item' => "ON tbl_hd_chamado_item.pedido = tbl_pedido.pedido",
                                'tbl_hd_chamado_extra' => "ON tbl_hd_chamado_extra.hd_chamado = tbl_hd_chamado_item.hd_chamado AND tbl_hd_chamado_item.pedido = $pedido",
                                'tbl_cidade' => "ON tbl_cidade.cidade = tbl_hd_chamado_extra.cidade"))
             ->addWhere(array('tbl_pedido.pedido' => $pedido))
             ->addWhere(array('tbl_pedido.fabrica' => $this->_fabrica));

            if (!$this->_model->prepare()->execute()) {
                throw new \Exception("Erro ao verificar os dados do Pedido {$pedido} para o Callcenter");
            } else {
                if ($this->_model->getPDOStatement()->rowCount() == 0) {
                    $this->_model->select("tbl_pedido")
                                 ->setCampos(array( 'tbl_hd_chamado_extra.nome',
                                                    'tbl_hd_chamado_extra.endereco',
                                                    'tbl_hd_chamado_extra.numero',
                                                    'tbl_hd_chamado_extra.complemento',
                                                    'tbl_hd_chamado_extra.bairro',
                                                    'tbl_hd_chamado_extra.cep',
                                                    'tbl_hd_chamado_extra.fone',
                                                    'tbl_hd_chamado_extra.fone2',
                                                    'tbl_hd_chamado_extra.email',
                                                    'tbl_hd_chamado_extra.cpf',
                                                    'tbl_hd_chamado_extra.rg',
                                                    'tbl_hd_chamado_extra.celular',
                                                    'tbl_cidade.nome AS cidade',
                                                    'tbl_cidade.estado'))
                                 ->addJoin(array(   'tbl_hd_chamado_extra' => "ON tbl_hd_chamado_extra.pedido = tbl_pedido.pedido",
                                                    'tbl_cidade' => "ON tbl_cidade.cidade = tbl_hd_chamado_extra.cidade"))
                                 ->addWhere(array('tbl_pedido.pedido' => $pedido))
                                 ->addWhere(array('tbl_pedido.fabrica' => $this->_fabrica));
                    if (!$this->_model->prepare()->execute()) {
                        throw new \Exception("Erro ao verificar os dados do Pedido {$pedido} para o Callcenter");
                    } else {
                        if ($this->_model->getPDOStatement()->rowCount() == 0) {
                            return false;
                        } else {
                            return $this->_model->getPDOStatement()->fetch();
                        }    
                    }
                } else {
                    return $this->_model->getPDOStatement()->fetch();
                }
            }
        } else {
            $this->_model->select("tbl_pedido")
                 ->setCampos(array( 'tbl_hd_chamado_extra.nome',
                                    'tbl_hd_chamado_extra.endereco',
                                    'tbl_hd_chamado_extra.numero',
                                    'tbl_hd_chamado_extra.complemento',
                                    'tbl_hd_chamado_extra.bairro',
                                    'tbl_hd_chamado_extra.cep',
                                    'tbl_hd_chamado_extra.fone',
                                    'tbl_hd_chamado_extra.fone2',
                                    'tbl_hd_chamado_extra.email',
                                    'tbl_hd_chamado_extra.cpf',
                                    'tbl_hd_chamado_extra.rg',
                                    'tbl_hd_chamado_extra.celular',
                                    'tbl_cidade.nome AS cidade',
                                    'tbl_cidade.estado'))
                 ->addJoin(array(   'tbl_hd_chamado_extra' => "ON tbl_hd_chamado_extra.pedido = tbl_pedido.pedido",
                                    'tbl_cidade' => "ON tbl_cidade.cidade = tbl_hd_chamado_extra.cidade"))
                 ->addWhere(array('tbl_pedido.pedido' => $pedido))
                 ->addWhere(array('tbl_pedido.fabrica' => $this->_fabrica));
            
            if (!$this->_model->prepare()->execute()) {
                throw new \Exception("Erro ao verificar os dados do Pedido {$pedido} para o Callcenter");
            } else {
                if ($this->_model->getPDOStatement()->rowCount() == 0) {
                    return false;
                } else {
                    return $this->_model->getPDOStatement()->fetch();
                }
            }
        }
    }

    public function getDadosClienteOS($pedido) {

        $this->_model->select("tbl_pedido")
			->setCampos(array( 'tbl_hd_chamado_extra.nome',
				'tbl_hd_chamado_extra.endereco',
				'tbl_hd_chamado_extra.numero',
				'tbl_hd_chamado_extra.complemento',
				'tbl_hd_chamado_extra.bairro',
				'tbl_hd_chamado_extra.cep',
				'tbl_hd_chamado_extra.fone',
				'tbl_hd_chamado_extra.fone2',
				'tbl_hd_chamado_extra.email',
				'tbl_hd_chamado_extra.cpf',
				'tbl_hd_chamado_extra.rg',
				'tbl_hd_chamado_extra.celular',
				'tbl_cidade.nome AS cidade',
				'tbl_cidade.estado'))
			->addJoin(array(	'tbl_os_item'     	=> "ON tbl_os_item.pedido = tbl_pedido.pedido",
                                'tbl_os_produto'  	=> "ON tbl_os_produto.os_produto = tbl_os_item.os_produto",
                                'tbl_os'          	=> "ON tbl_os.os = tbl_os_produto.os",
								'tbl_os_troca'    	=> "ON tbl_os_troca.os = tbl_os.os",
								'tbl_hd_chamado_extra' 	=> "ON tbl_hd_chamado_extra.os = tbl_os.os",
								'tbl_hd_chamado' 	=> "ON tbl_hd_chamado_extra.hd_chamado = tbl_hd_chamado.hd_chamado",
								'tbl_cidade' 	=> "ON tbl_hd_chamado_extra.cidade = tbl_cidade.cidade",
                                'tbl_pedido_item' 	=> "ON tbl_pedido_item.pedido = tbl_pedido.pedido",
                                'tbl_peca'        	=> "ON tbl_peca.peca = tbl_pedido_item.peca"))
             ->addWhere(array('tbl_pedido.pedido' => $pedido))
             ->addWhere(array('tbl_peca.produto_acabado' => "t"))
			 ->addWhere(array('tbl_os_troca.envio_consumidor' => "t"))
			 ->addWhere("tbl_hd_chamado.titulo <> 'Help-Desk Posto'")
             ->addWhere(array('tbl_pedido.fabrica' => $this->_fabrica));
        if (!$this->_model->prepare()->execute()) {
            throw new \Exception("Erro ao verificar os dados do Pedido {$pedido} para o Callcenter");
        } else {
		if ($this->_model->getPDOStatement()->rowCount() == 0) {

			$this->_model->select("tbl_pedido")
				->setCampos(array( 'tbl_hd_chamado_extra.nome',
					'tbl_hd_chamado_extra.endereco',
					'tbl_hd_chamado_extra.numero',
					'tbl_hd_chamado_extra.complemento',
					'tbl_hd_chamado_extra.bairro',
					'tbl_hd_chamado_extra.cep',
					'tbl_hd_chamado_extra.fone',
					'tbl_hd_chamado_extra.fone2',
					'tbl_hd_chamado_extra.email',
					'tbl_hd_chamado_extra.cpf',
					'tbl_hd_chamado_extra.rg',
					'tbl_hd_chamado_extra.celular',
					'tbl_cidade.nome AS cidade',
					'tbl_cidade.estado'))
					->addJoin(array(        'tbl_os_item'           => "ON tbl_os_item.pedido = tbl_pedido.pedido",
						'tbl_os_produto'        => "ON tbl_os_produto.os_produto = tbl_os_item.os_produto",
						'tbl_os'                => "ON tbl_os.os = tbl_os_produto.os",
						'tbl_os_troca'          => "ON tbl_os_troca.os = tbl_os.os",
						'tbl_hd_chamado_item'   => "ON tbl_hd_chamado_item.os = tbl_os.os",
						'tbl_hd_chamado_extra'  => "ON tbl_hd_chamado_extra.hd_chamado = tbl_hd_chamado_item.hd_chamado",
						'tbl_hd_chamado'        => "ON tbl_hd_chamado_extra.hd_chamado = tbl_hd_chamado.hd_chamado",
						'tbl_cidade'    => "ON tbl_hd_chamado_extra.cidade = tbl_cidade.cidade",
						'tbl_pedido_item'       => "ON tbl_pedido_item.pedido = tbl_pedido.pedido",
						'tbl_peca'              => "ON tbl_peca.peca = tbl_pedido_item.peca"))
						->addWhere(array('tbl_pedido.pedido' => $pedido))
						->addWhere(array('tbl_peca.produto_acabado' => "t"))
						->addWhere(array('tbl_os_troca.envio_consumidor' => "t"))
						->addWhere("tbl_hd_chamado.titulo <> 'Help-Desk Posto'")
						->addWhere(array('tbl_pedido.fabrica' => $this->_fabrica));
			#echo $this->_model->prepare()->getQuery(); exit;
			if (!$this->_model->prepare()->execute()) {
				throw new \Exception("Erro ao verificar os dados do Pedido {$pedido} para o Callcenter");
			}else{
				if ($this->_model->getPDOStatement()->rowCount() == 0) {
					$this->_model->select("tbl_pedido")
						->setCampos(array( 'tbl_os.consumidor_nome as nome',
								'tbl_os.consumidor_endereco as endereco',
								'tbl_os.consumidor_numero as numero',
								'tbl_os.consumidor_complemento as complemento',
								'tbl_os.consumidor_bairro as bairro',
								'tbl_os.consumidor_cep as cep',
								'tbl_os.consumidor_fone_comercial as fone',
								'tbl_os.consumidor_fone_recado as fone2',
								'tbl_os.consumidor_email as email',
								'tbl_os.consumidor_cpf as cpf',
								'tbl_hd_chamado_extra.rg',
								'tbl_os.consumidor_celular as celular',
								'tbl_os.consumidor_cidade AS cidade',
								'tbl_os.consumidor_estado as estado'))
						->addJoin(array('tbl_os_item'     	=> "ON tbl_os_item.pedido = tbl_pedido.pedido",
										'tbl_os_produto'  	=> "ON tbl_os_produto.os_produto = tbl_os_item.os_produto",
										'tbl_os'          	=> "ON tbl_os.os = tbl_os_produto.os",
										'tbl_os_troca'    	=> "ON tbl_os_troca.os = tbl_os.os",
										'tbl_hd_chamado_extra' 	=> "ON tbl_hd_chamado_extra.os = tbl_os.os",
										'tbl_hd_chamado' 	=> "ON tbl_hd_chamado_extra.hd_chamado = tbl_hd_chamado.hd_chamado",
										'tbl_cidade' 	=> "ON tbl_hd_chamado_extra.cidade = tbl_cidade.cidade",
										'tbl_pedido_item' 	=> "ON tbl_pedido_item.pedido = tbl_pedido.pedido",
										'tbl_peca'        	=> "ON tbl_peca.peca = tbl_pedido_item.peca"))
						 ->addWhere(array('tbl_pedido.pedido' => $pedido))
						 ->addWhere(array('tbl_peca.produto_acabado' => "t"))
						 ->addWhere(array('tbl_os_troca.envio_consumidor' => "t"))
						 ->addWhere("tbl_hd_chamado.titulo = 'Help-Desk Posto'")
						 ->addWhere(array('tbl_pedido.fabrica' => $this->_fabrica));


					if (!$this->_model->prepare()->execute()) {
						throw new \Exception("Erro ao verificar os dados do Pedido {$pedido} para o Callcenter");
					} else {
						if ($this->_model->getPDOStatement()->rowCount() == 0) {
							$this->_model->select("tbl_pedido")
							->setCampos(array( 'tbl_os.consumidor_nome as nome',
								'tbl_os.consumidor_endereco as endereco',
								'tbl_os.consumidor_numero as numero',
								'tbl_os.consumidor_complemento as complemento',
								'tbl_os.consumidor_bairro as bairro',
								'tbl_os.consumidor_cep as cep',
								'tbl_os.consumidor_fone_comercial as fone',
								'tbl_os.consumidor_fone_recado as fone2',
								'tbl_os.consumidor_email as email',
								'tbl_os.consumidor_cpf as cpf',
								'tbl_hd_chamado_extra.rg',
								'tbl_os.consumidor_celular as celular',
								'tbl_os.consumidor_cidade AS cidade',
								'tbl_os.consumidor_estado as estado'))
							 ->addJoin(array('tbl_os_item'           => "ON tbl_os_item.pedido = tbl_pedido.pedido",
										'tbl_os_produto'        => "ON tbl_os_produto.os_produto = tbl_os_item.os_produto",
										'tbl_os'                => "ON tbl_os.os = tbl_os_produto.os",
										'tbl_os_troca'          => "ON tbl_os_troca.os = tbl_os.os",
										'tbl_hd_chamado_item'   => "ON tbl_hd_chamado_item.os = tbl_os.os",
										'tbl_hd_chamado_extra'  => "ON tbl_hd_chamado_item.hd_chamado = tbl_hd_chamado_extra.hd_chamado",
										'tbl_hd_chamado'        => "ON tbl_hd_chamado_extra.hd_chamado = tbl_hd_chamado.hd_chamado",
										'tbl_cidade'    => "ON tbl_hd_chamado_extra.cidade = tbl_cidade.cidade",
										'tbl_pedido_item'       => "ON tbl_pedido_item.pedido = tbl_pedido.pedido",
										'tbl_peca'              => "ON tbl_peca.peca = tbl_pedido_item.peca"))
						 ->addWhere(array('tbl_pedido.pedido' => $pedido))
						 ->addWhere(array('tbl_peca.produto_acabado' => "t"))
						 ->addWhere(array('tbl_os_troca.envio_consumidor' => "t"))
						 ->addWhere(array('tbl_pedido.fabrica' => $this->_fabrica));

						//echo $this->_model->prepare()->getQuery();
						//			return false;



							if (!$this->_model->prepare()->execute()) {
								throw new \Exception("Erro ao verificar os dados do Pedido {$pedido} para o Callcenter");
							} else {
								if ($this->_model->getPDOStatement()->rowCount() == 0) {
									return false;
								} else {
									return $this->_model->getPDOStatement()->fetch();
								}
							}


							} else {
								return $this->_model->getPDOStatement()->fetch();
							}
					}
				}else{
					return $this->_model->getPDOStatement()->fetch();
				}
			}
            } else {
                return $this->_model->getPDOStatement()->fetch();
            }
        }
    }

    public function getInformacaoPecaPedido($pedido, $tabela){
	    $pdo = $this->_model->getPDO();


		# Não mudar sem avisar analistas 
	    $sql = "SELECT (tbl_pedido_item.qtde - tbl_pedido_item.qtde_cancelada) as qtde,
		    tbl_pedido_item.preco,
		    tbl_peca.referencia,
		    tbl_peca.ipi,
		    tbl_pedido_item.pedido_item,
		    tbl_tabela.sigla_tabela,
		   tbl_produto.referencia AS ref_produto
		    FROM tbl_pedido_item
		    JOIN tbl_pedido ON tbl_pedido.pedido = tbl_pedido_item.pedido AND tbl_pedido.fabrica = {$this->_fabrica}
		    JOIN tbl_tabela ON tbl_pedido.tabela = tbl_tabela.tabela AND tbl_tabela.fabrica = {$this->_fabrica}
		    JOIN tbl_peca  ON tbl_pedido_item.peca = tbl_peca.peca AND tbl_peca.fabrica = {$this->_fabrica}
		    JOIN tbl_tabela_item ON tbl_tabela_item.tabela = {$tabela} AND tbl_tabela_item.peca = tbl_pedido_item.peca AND tbl_tabela_item.tabela = tbl_tabela.tabela
		    LEFT JOIN tbl_os_item ON tbl_pedido_item.pedido_item = tbl_os_item.pedido_item
		    LEFT JOIN tbl_os_produto ON tbl_os_item.os_produto = tbl_os_produto.os_produto
		    LEFT JOIN tbl_produto ON tbl_os_produto.produto = tbl_produto.produto AND tbl_produto.fabrica_i = {$this->_fabrica}
			WHERE	tbl_pedido_item.pedido = {$pedido}
			AND		tbl_pedido_item.qtde - tbl_pedido_item.qtde_cancelada > 0 
			AND		tbl_pedido.exportado isnull";
	    $query = $pdo->query($sql);

        if (!$query) {
            throw new \Exception("Erro ao selecionar as informações das peças para o pedido {$pedido}");
        } else {
            return $query->fetchAll(\PDO::FETCH_ASSOC);
        }
    }

    public function getInscricaoEstadual($cnpj, $pedido){

        $pdo = $this->_model->getPDO();

        $sql = "SELECT ie
            FROM tbl_posto
            WHERE cnpj = '{$cnpj}'
            UNION
            SELECT ie
            FROM tbl_revenda
            WHERE cnpj = '{$cnpj}'";
        $query = $pdo->query($sql);

        if (!$query) {
            throw new \Exception("Erro ao encontrar IE para o pedido {$pedido}");
        } else {
            return $query->fetchAll(\PDO::FETCH_ASSOC);
        }
    }

    public function registrarPedidoExportado($pedido, $status_pedido = 2){
        /*
            $teste = $this->_model->updatePedido(array("exportado" => date("Y-m-d H:i:s"), "status_pedido" => $status_pedido), $pedido);
            echo $teste;
        */
        
        if($this->_model->updatePedido(array("exportado" => date("Y-m-d H:i:s"), "status_pedido" => $status_pedido), $pedido)){
            return true;
        }else{
            throw new \Exception("Erro ao atualizar pedido {$pedido} para o status de exportado");
        }
    }

    public function updateObservacao($pedido, $obs){
        if($this->_model->updatePedido(array("obs" => "{$obs}"), $pedido)){
            return true;
        }else{
            throw new \Exception("Erro ao atualizar observação do pedido {$pedido}");
        }
    }

    public function setOsTrocaPedido($os, $pedido, $pedido_item=null) {
        if (!is_numeric($os))
            throw new \Exception("OS não declarada");

        if (!is_numeric($pedido))
            throw new \Exception("Pedido não informado");

        if (!is_null($pedido_item) and !is_numeric($pedido_item))
            throw new \Exception("Ítem do Pedido não informado");

        return $this->_model->atualizaOsTroca($os, $pedido, $pedido_item);
    }

    public function getPostosControlamEstoque($verifica_estoque = true){

        $controla_estoque = ($verifica_estoque == true) ? true : false;

        $this->_model->select("tbl_posto_fabrica")
                     ->setCampos(array("posto", "tipo_posto"))
                     ->addWhere(array("fabrica" => $this->_fabrica))
                     ->addWhere(array("controla_estoque" => $controla_estoque))
                     ->addWhere("credenciamento <> 'DESCREDENCIADO' ");

        if (!$this->_model->prepare()->execute()) {
            throw new \Exception("Erro ao buscar postos que controlam estoque");
        } else if ($this->_model->getPDOStatement()->rowCount() > 0) {
            return $this->_model->getPDOStatement()->fetchAll();
        } else {
            return false;
        }

    }

    public function verificaEstoquePosto($posto){

        $pdo = $this->_model->getPDO();

		if ($this->_fabrica == 74) {
			$cond = " AND tbl_peca.controla_saldo is true ";
		}
        $sql = "SELECT
                    tbl_estoque_posto.peca,
                    tbl_peca.referencia,
                    (tbl_estoque_posto.estoque_maximo - case when tbl_estoque_posto.qtde isnull then 0 else tbl_estoque_posto.qtde end ) AS qtde_pedido
                FROM tbl_estoque_posto
                JOIN tbl_peca ON tbl_peca.peca = tbl_estoque_posto.peca
                WHERE tbl_peca.fabrica = $this->_fabrica
					AND tbl_peca.ativo IS TRUE
					$cond
                    AND tbl_estoque_posto.posto = $posto
                    AND tbl_estoque_posto.fabrica = $this->_fabrica
                    AND tbl_estoque_posto.estoque_maximo > 0
                    AND (tbl_estoque_posto.estoque_maximo > tbl_estoque_posto.qtde OR tbl_estoque_posto.qtde IS NULL)
                    AND (tbl_estoque_posto.qtde <= tbl_estoque_posto.estoque_minimo OR tbl_estoque_posto.qtde IS NULL)";          

        $query = $pdo->query($sql);
        return $query->fetchAll(\PDO::FETCH_ASSOC);

    }

    public function verificaEstoquePecaDistrib($peca, $qtde_pedido){

        $pdo = $this->_model->getPDO();

        $sql = "SELECT posto FROM tbl_posto_estoque 
                WHERE posto = 4311
                AND peca = $peca
                AND qtde >= $qtde_pedido";

        $query = $pdo->query($sql);

        $res = $query->fetchAll();

        if (count($res) == 0) {

            return true;

        } else {

            return false;

        }

    }

    public function atualiza_status_checkpoint($os, $descricao) {

        $pdo = $this->_model->getPDO();

        $busca_status = "SELECT status_checkpoint
                         FROM tbl_status_checkpoint
                         WHERE UPPER(descricao) = UPPER('".$descricao."')
                         LIMIT 1";

        $sql = "UPDATE tbl_os
                SET status_checkpoint = (
                    {$busca_status}
                )
                WHERE os = {$os}
                AND status_checkpoint NOT IN (
                    {$busca_status}
                )";

        $query = $pdo->query($sql);

        return true;

    }

    public function pedidoBonificadoNaoFaturado($posto)
    {

        $pdo = $this->_model->getPDO();

        $sql = "SELECT  tbl_pedido.pedido,
                        tbl_pedido_item.peca
                FROM    tbl_pedido
                JOIN    tbl_pedido_item USING(pedido)
                JOIN    tbl_tipo_pedido         ON  tbl_tipo_pedido.tipo_pedido         = tbl_pedido.tipo_pedido
                                                AND tbl_tipo_pedido.garantia_antecipada IS TRUE
           LEFT JOIN    tbl_faturamento_item    ON  tbl_faturamento_item.pedido         = tbl_pedido.pedido
                WHERE   tbl_pedido.fabrica                  = {$this->_fabrica}
                AND     tbl_pedido.posto                    = {$posto}
                AND     tbl_pedido.status_pedido            <> 14
                AND     tbl_faturamento_item.faturamento IS NULL";
        $query = $pdo->query($sql);

        $pedidos = $query->fetchAll(\PDO::FETCH_ASSOC);

        return $pedidos;

    }

    public function pedidoBonificado($posto, $estoque, $distribuidor = "null")
    {
        $pdo = $this->_model->getPDO();

        $condicao    = $this->getCondicaoGarantia();
        $tipo_pedido = $this->getTipoPedidoGarantiaAntecipada();

        $sql = "
            SELECT fatura_manualmente
            FROM tbl_fabrica
            WHERE fabrica = {$this->_fabrica}
        ";
        $query = $pdo->query($sql);

        $res = $query->fetch();

        $fatura_manualmente = $res["fatura_manualmente"];

        if ($fatura_manualmente == "t") {
            $dados = array(
                "posto"         => $posto,
                "tipo_pedido"   => $tipo_pedido,
                "condicao"      => $condicao,
                "status_pedido" => '2',
                "fabrica"       => $this->_fabrica,
                "distribuidor"  => $distribuidor,
                "finalizado" => "'".date("Y-m-d H:i:s")."'"
            );
        } else {
            $dados = array(
                "posto"         => $posto,
                "tipo_pedido"   => $tipo_pedido,
                "condicao"      => $condicao,
                "status_pedido" => '1',
                "fabrica"       => $this->_fabrica,
                "distribuidor"  => $distribuidor,
                "finalizado" => "'".date("Y-m-d H:i:s")."'"
            );
        }

        if(empty($dados['distribuidor'])){ //hd_chamado=2782600
            unset($dados['distribuidor']);
        }

        $tabela_unica = Regras::get('tabela_unica', 'pedido_bonificacao', $this->_fabrica);

        if ($tabela_unica === true) {
            $sql = "
                SELECT tbl_posto_linha.tabela
                FROM tbl_posto_linha
                INNER JOIN tbl_linha ON tbl_linha.linha = tbl_posto_linha.linha AND tbl_linha.fabrica = {$this->_fabrica}
                INNER JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto_linha.posto AND tbl_posto_fabrica.fabrica = {$this->_fabrica}
                WHERE tbl_posto_linha.posto = {$posto}
                LIMIT 1
            ";
            $query = $pdo->query($sql);

            $res = $query->fetch();

            $tabela = $res["tabela"];

            if (!empty($tabela)) {
                $dados['tabela'] = $tabela;
            }
        }

        $tabela_fixa = Regras::get('tabela', 'pedido_bonificacao', $this->_fabrica);

        if (!empty($tabela_fixa)) {
            $dados['tabela'] = $tabela_fixa;
            $tabela = $tabela_fixa;
        }

        /*
        * Grava o Pedido
        */
        $this->grava($dados);

        $pedido = $this->getPedido();

        foreach ($estoque as $key => $value) {

            unset($dadosItens);

            $peca            = $value["peca"];
            $peca_referencia = $value["referencia"];
            $qtde            = $value["qtde_pedido"];

            $dadosItens[] = array(
                "pedido"            => (int)$pedido,
                "peca"              => $peca,
                "qtde"              => $qtde,
                "qtde_faturada"     => 0,
                "qtde_cancelada"    => 0,
                "preco"             => $this->getPrecoPecaGarantiaAntecipada($peca, $posto, $tabela)
            );

            $this->gravaItem($dadosItens, $pedido);

        }

        if ($fatura_manualmente == 't') {
            $this->registrarPedidoExportado($pedido);
        }

        $this->finaliza($pedido);

    }

    //envia email com pedidos gerados no dia - mondial
    public function EnviaEmailPedidoGerados($posto, $fabrica, $emails){

        $pdo = $this->_model->getPDO();

        $data = date("d/m/Y");

        $sql = "SELECT tbl_pedido.pedido, tbl_posto.nome, tbl_posto.cnpj
                FROM
                tbl_pedido
                INNER JOIN tbl_tipo_pedido ON tbl_tipo_pedido.tipo_pedido = tbl_pedido.tipo_pedido
                INNER JOIN tbl_posto on tbl_posto.posto = tbl_pedido.posto
                WHERE tbl_tipo_pedido.descricao = 'BONIFICACAO'
                AND tbl_pedido.fabrica = $fabrica
                AND tbl_pedido.data::date = current_date ";

        $query = $pdo->query($sql);

        $dados = $query->fetchAll(\PDO::FETCH_ASSOC);

        $conteudo .= "Segue dados dos pedidos gerados. <br><br>";

        foreach($dados as $pedido){

            $conteudo .= "<table border='1' cellpadding='0' cellspacing='0'>";

                $conteudo .= "<tr>";
                    $conteudo .= "<td><b>Pedido:</b> $pedido[pedido]</td>";
                    $conteudo .= "<td><b>Posto:</b> $pedido[cnpj] - $pedido[nome]</td>";
                $conteudo .= "</tr>";

                $conteudo .= "<tr>";
                    $conteudo .= "<td><b>Qtde</b></td>";
                    $conteudo .= "<td><b>Peça</b></td>";
                $conteudo .= "</tr>";

            $sql = "SELECT tbl_pedido_item.qtde, tbl_peca.referencia, tbl_peca.descricao
            FROM tbl_pedido_item
            INNER JOIN tbl_peca on tbl_peca.peca = tbl_pedido_item.peca
            WHERE tbl_pedido_item.pedido = $pedido[pedido]";

            $query = $pdo->query($sql);

            $itensPedido = $query->fetchAll(\PDO::FETCH_ASSOC);

            foreach($itensPedido as $itens){

                $conteudo .= "<tr>";
                    $conteudo .= "<td>$itens[qtde]</td>";
                    $conteudo .= "<td>$itens[referencia] - $itens[descricao]</td>";
                $conteudo .= "</tr>";
            }
            $conteudo .= "</table> <Br>";
        }

        $headers  = 'MIME-Version: 1.0' . "\r\n";
        $headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
        $headers .= 'From: helpdesk@telecontrol.com.br' . "\r\n" .
                    'Reply-To: helpdesk@telecontrol.com.br' . "\r\n" .
                    'X-Mailer: PHP/' . phpversion();

        $dados_email = implode(",", $emails);

        mail("$dados_email", "Pedidos de Bonificação - $data ", $conteudo, $headers);

    }

    /**
     * @param integer $pedido
     * @return float
     */
    public function aplicaDesconto($pedido)
    {
        try {
            $desconto = $this->_model->getDescontoPosto($pedido);

            if (empty($desconto)) {
                $desconto = $this->_model->getDescontoTipoPosto($pedido);
            }

            $this->_model->setDescontoPedido($pedido, $desconto);
        } catch (\Exception $e) {
            $desconto = 0;
        }

        return (float) $desconto;
    }

    /**
     * Verifica se existe hd_chamado atrelado ao pedido
     *
     * @param integer $hd_chamado
     */
    public function verificaPedidoHD($hd_chamado) {

        $pdo = $this->_model->getPDO();

        #Verifica se a fábrica possui pedido atrelado na tbl_hd_chamado
        $sql_cc =  "SELECT hd_chamado
                        FROM tbl_hd_chamado
                            JOIN tbl_hd_chamado_extra USING(hd_chamado)
                        WHERE tbl_hd_chamado.hd_chamado = {$hd_chamado}
                            AND tbl_hd_chamado_extra.pedido IS NOT NULL;";
        $query = $pdo->query($sql_cc);
        $dados = $query->fetchAll(\PDO::FETCH_ASSOC);

        if (count($dados) > 0) {
            throw new \Exception("Já existe um Pedido cadastrado para o atendimento!");
        }
    }

    public function retornaDadosPeca($peca) {

        $this->_model->select("tbl_peca")
                         ->setCampos(array("tbl_peca.referencia", "tbl_peca.descricao"))
                         ->addWhere(array("tbl_peca.fabrica" => $this->_fabrica))
                         ->addWhere(array("tbl_peca.peca" => $peca));

        if (!$this->_model->prepare()->execute()) {
            throw new \Exception("Erro ao buscar dados da peça {$peca}");
        }

        $dadosPeca = $this->_model->getPDOStatement()->fetch();

        return $dadosPeca;

    }
    
    public function setPecaCritica($pedido) {
        
       $this->_model->select("tbl_peca")
                    ->setCampos(['tbl_peca.peca'])
                    ->addJoin(["tbl_pedido_item " => "ON tbl_peca.peca = tbl_pedido_item.peca"])
                    ->addWhere(['tbl_pedido_item.pedido' => $pedido])
                    ->addWhere(["tbl_peca.peca_critica_venda" => 't']);
        if (!$this->_model->prepare()->execute()) {
            throw new \Exception("Erro ao buscar dados da peça {$peca}");
        }

        $pecaCritica = $this->_model->getPDOStatement()->fetch();
        
        if ($pecaCritica != false) {
            $this->_model->updatePedido(["status_pedido" => '18'], $pedido);
        }
    }

    public function verificaPecaAlternativa($peca, $qtde) {
      $pdo = $this->_model->getPDO();

      $sql = "SELECT peca_para FROM tbl_peca_alternativa WHERE fabrica = $this->_fabrica AND peca_de = $peca ";
      $query = $pdo->query($sql);
      $res = $query->fetchAll();

      foreach($res as $dados){
        $pecaAlternativa = $dados['peca_para'];
        if(!$this->verificaEstoquePecaDistrib($pecaAlternativa, $qtde)){
          return $pecaAlternativa;
        }
      }
      return false;
    }

    public function cancelaItemPedido($pedido, $pedido_item, $motivo, $qtde = null) {

        $pdo = $this->_model->getPDO();

        if (!empty($pedido) && !empty($pedido_item)) {
            if (empty($qtde)) {
                $setUpd = "qtde_cancelada = qtde - (COALESCE(qtde_faturada, 0) + COALESCE(qtde_cancelada, 0))";
            } else {
                $setUpd = "qtde_cancelada = {$qtde}";
            }

            $updPedItem = "
                UPDATE tbl_pedido_item
                SET {$setUpd}
                WHERE pedido = {$pedido}
                AND pedido_item = {$pedido_item};
            ";

            $resPedItem = $pdo->query($updPedItem);

            if (!$resPedItem) {
                throw new \Exception("Ocorreu um erro atualizando dados de cancelamento #001");
            }

            $insPedCancel = "
                INSERT INTO tbl_pedido_cancelado (
                    pedido,
                    posto,
                    fabrica,
                    os,
                    peca,
                    qtde,
                    motivo,
                    data,
                    pedido_item
                )
                SELECT
                    tbl_pedido.pedido,
                    tbl_pedido.posto,
                    tbl_pedido.fabrica,
                    tbl_os_produto.os,
                    tbl_pedido_item.peca,
                    tbl_pedido_item.qtde_cancelada,
                    '{$motivo}',
                    CURRENT_DATE,
                    tbl_pedido_item.pedido_item
                FROM tbl_pedido
                JOIN tbl_pedido_item USING(pedido)
                JOIN tbl_os_item USING(pedido_item)
                JOIN tbl_os_produto USING(os_produto)
                WHERE tbl_pedido.pedido = {$pedido}
                AND tbl_pedido_item.pedido_item = {$pedido_item};
            ";

            $resPedCancel = $pdo->query($insPedCancel);

            if (!$resPedCancel) {
                throw new \Exception("Ocorreu um erro atualizando dados de cancelamento #002");
            }

            $atPedStatus = "SELECT fn_atualiza_status_pedido({$this->_fabrica}, {$pedido});";
            $resPedStatus = $pdo->query($atPedStatus);

            if (!$resPedStatus) {
                throw new \Exception("Ocorreu um erro atualizando dados de cancelamento #003");
            }
        } else {
            throw new \Exception("Pedido não encontrado para executar o faturamento");
        }

        return true;

    }

    public function verificaValorMinimoPosto() {
        $valor_minimo = $this->_model->getValorMinimoPosto($this->_pedido);

        if(!$valor_minimo){
            $valor_minimo = $this->_model->getValorMinimo($this->_pedido);
        }

        $total_pedido = $this->_model->getTotalPedido($this->_pedido);

        if (!empty($valor_minimo) && $total_pedido < $valor_minimo) {
            throw new \Exception("O valor do pedido não pode ser menor que ".$this->formatMoney($valor_minimo). " para esta condição de pagamento");
        }

        return $this;
    }

    public function buscaTipoFretePosto(){
        
        $tipo_frete = $this->_model->getTipoFretePosto($this->_pedido);
        return $tipo_frete;
    }
}
