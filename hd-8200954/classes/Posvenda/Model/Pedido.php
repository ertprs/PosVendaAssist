<?php

namespace Posvenda\Model;

use Posvenda\Regras;

class Pedido extends AbstractModel
{

    private $_pedido;
    private $_fabrica;
    private $_erro;

    public function __construct($fabrica = null, $pedido = null, $conn = null)
    {
        parent::__construct('tbl_pedido', $conn);

        if(!empty($pedido)) {
            $this->_pedido = $pedido;
        }

        $this->_fabrica = $fabrica;

    }

    public function setPedido($pedido) {
        $this->_pedido = $pedido;
    }

    /* Verifica se o Pedido é da Fábrica */
    public function verificaPedidoFabrica($pedido = null)
    {

        if(empty($pedido)) {
            $pedido = $this->_pedido;
        }

        $this->select()
             ->setCampos(array('pedido'))
             ->addWhere(array('pedido' => $pedido))
             ->addWhere(array('fabrica' => $this->_fabrica));

        $res = $this->prepare()->execute();

        if ($res) {
            return true;
        }

        return false;

    }

    /* Verifica se a Condição de Pagamento existe para a Fábrica */
    public function verificaCondicaoPagamentoFabrica($pedido = null)
    {

        if(empty($pedido)) {
            $pedido = $this->_pedido;
        }

        $this->select()
             ->setCampos(array('tbl_pedido.pedido'))
             ->addJoin(array('tbl_condicao' => "ON tbl_condicao.condicao = tbl_pedido.condicao AND tbl_condicao.fabrica = {$this->_fabrica}"))
             ->addWhere(array('tbl_pedido.pedido' => $this->_pedido))
             ->addWhere(array('tbl_pedido.fabrica' => $this->_fabrica));

        $res = $this->prepare()->execute();

        if($res) {
            return true;
        }

        return false;

    }

    /* Resgata a Descrição do Pedido */
    public function getDescricaoPedido()
    {

        $this->select()
             ->setCampos(array('tbl_tipo_pedido.descricao'))
             ->addJoin(array('tbl_tipo_pedido' => "ON tbl_pedido.tipo_pedido = tbl_tipo_pedido.tipo_pedido AND tbl_tipo_pedido.fabrica = {$this->_fabrica}"))
             ->addWhere(array('tbl_pedido.pedido' => $this->_pedido))
             ->addWhere(array('tbl_pedido.fabrica' => $this->_fabrica));
        $this->prepare()->execute();

        $res = $this->getPDOStatement()->fetch();

        return $res['descricao'];

    }

    /* Regata o Código do Posto */
    public function getCodigoPosto()
    {

        $this->select()
             ->setCampos(array('posto'))
             ->addWhere(array('pedido' => $this->_pedido))
             ->addWhere(array('fabrica' => $this->_fabrica));

        $this->prepare()->execute();

        $res = $this->getPDOStatement()->fetch();

        return $res['posto'];

    }

    public function getCondicaoPagamento(){

        $this->select()
             ->setCampos(array('condicao'))
             ->addWhere(array('pedido' => $this->_pedido))
             ->addWhere(array('fabrica' => $this->_fabrica));

        $this->prepare()->execute();

        $res = $this->getPDOStatement()->fetch();

        return $res['condicao'];

    }

    public function getAdmin(){

        $this->select()
             ->setCampos(array('admin'))
             ->addWhere(array('pedido' => $this->_pedido))
             ->addWhere(array('fabrica' => $this->_fabrica));

        $this->prepare()->execute();

        $res = $this->getPDOStatement()->fetch();

        return $res['admin'];

    }

        public function getStatusAtendimento($hd_chamado){

        $this->select('tbl_hd_chamado')
             ->setCampos(array('status'))
             ->addWhere(array('hd_chamado' => $hd_chamado))
             ->addWhere(array('fabrica_responsavel' => $this->_fabrica));

        $this->prepare()->execute();

        $res = $this->getPDOStatement()->fetch();

        return $res['status'];

    }

    /* Resgata o Código da Tabela */
    public function getValorTabela($descricao_pedido = null, $posto = null)
    {

        if(empty($descricao_pedido) && empty($posto)){
            return false;
        }else{

            $tabela = (strtolower($descricao_pedido) == "garantia") ? "tabela" : "tabela_posto";

            $pdo = $this->getPDO();

            $sql = "
                SELECT {$tabela} AS tabela FROM tbl_posto_linha
                JOIN tbl_linha using(linha)
                WHERE fabrica = {$this->_fabrica}
                AND posto = {$posto}  LIMIT 1
            ";

            $query  = $pdo->query($sql);
            $res    = $query->fetch(\PDO::FETCH_ASSOC);

            return $res['tabela'];

        }

    }

    /* Atualiza os Preços nos itens do Pedido */
    public function setPrecoPedido($tabela = null, $pedido = null)
    {

        if(empty($pedido)) {
            $pedido = $this->_pedido;
        }

        $pdo = $this->getPDO();

        $sql = "
            UPDATE tbl_pedido_item
            SET preco = round(tbl_tabela_item.preco::numeric, 2)
            FROM tbl_tabela_item
            WHERE tbl_tabela_item.peca = tbl_pedido_item.peca
            AND tbl_tabela_item.tabela = {$tabela}
            AND tbl_pedido_item.pedido = {$pedido}
        ";

        return $query = $pdo->query($sql);

    }

    /* Verifica se há algum item(Peça) do Pedido sem Preço */
    public function getPecaSemPreco($tipo_pedido = null, $tabela = null, $fabrica = null, $pedido = null)
    {

        if(empty($fabrica)){
            $fabrica = $this->_fabrica;
        }

        if(empty($pedido)) {
            $pedido = $this->_pedido;
        }

        $pdo = $this->getPDO();

        if(strtolower($tipo_pedido) != "garantia"){

            $sql = "
                SELECT tbl_peca.referencia || ' - ' || tbl_peca.descricao AS peca_sem_preco
                FROM tbl_pedido_item
                JOIN tbl_peca ON tbl_pedido_item.peca = tbl_peca.peca
                JOIN tbl_pedido ON tbl_pedido_item.pedido = tbl_pedido.pedido
                WHERE tbl_pedido_item.preco ISNULL
                AND tbl_pedido.pedido = {$pedido}
                AND tbl_pedido.fabrica = {$fabrica}
                LIMIT 1
            ";

        }else{

            $sql = "
                SELECT tbl_pedido_item.peca
                FROM tbl_pedido
                JOIN tbl_pedido_item ON tbl_pedido.pedido = tbl_pedido_item.pedido
                LEFT JOIN tbl_tabela_item ON tbl_pedido_item.peca = tbl_tabela_item.peca AND tbl_tabela_item.tabela = {$tabela}
                WHERE tbl_pedido.pedido = {$pedido}
                AND tbl_tabela_item.preco ISNULL
            ";

        }

        $query = $pdo->query($sql);

        if($query && strtolower($tipo_pedido) != "garantia"){
            $res = $query->fetch(\PDO::FETCH_ASSOC);
            return (empty($res['peca_sem_preco'])) ? true : $res['peca_sem_preco'];
        }else{
            $res = $query->fetchAll(\PDO::FETCH_ASSOC);
            foreach ($res as $v) {
                $this->setPrecoPeca($tabela, $v["peca"]);
            }
        }

        return true;

    }

    public function getValorMinimoPosto($pedido){
        if (empty($pedido)) {
            throw new \Exception("Pedido não informado para selecionar o valor mínimo");
        } else {
            $this->select("tbl_posto_condicao")
                ->setCampos(array("tbl_posto_condicao.limite_minimo"))
                ->addJoin(array("tbl_pedido" => "ON tbl_pedido.condicao = tbl_posto_condicao.condicao AND tbl_posto_condicao.posto = tbl_pedido.posto AND tbl_pedido.fabrica = {$this->_fabrica }"))
                ->addWhere(array("tbl_pedido.pedido" => $pedido))
                ->addWhere("tbl_posto_condicao.visivel IS TRUE");

            if (!$this->prepare()->execute()) {
                throw new \Exception("Erro ao selecionar o valor minimo do pedido : {$pedido}");

            } else {
                $res = $this->getPDOStatement()->fetch();
                return $res["limite_minimo"];
            }
        }
    }

    public function getTipoFretePosto($pedido){

        if (empty($pedido)) {
            throw new \Exception("Pedido não informado para buscar o tipo de frete do posto");
        } else {
            $this->select("tbl_posto_fabrica")
                ->setCampos(array("tbl_posto_fabrica.parametros_adicionais"))
                ->addJoin(array("tbl_pedido" => "ON tbl_posto_fabrica.fabrica = tbl_pedido.fabrica AND tbl_posto_fabrica.posto = tbl_pedido.posto AND tbl_pedido.fabrica = {$this->_fabrica }"))
                ->addWhere(array("tbl_pedido.pedido" => $pedido));

            if (!$this->prepare()->execute()) {
                throw new \Exception("Erro ao buscar o tipo de frete do pedido : {$pedido}");

            } else {
                $res = $this->getPDOStatement()->fetch();

                if($res){
                    $parametros_adicionais = json_decode($res['parametros_adicionais'], true);
                    return $parametros_adicionais['frete'];
                }

                return false;            
            }
        }
    }

    /* Insere Preço nas Peças */
    public function setPrecoPeca($tabela = null, $peca = null){

        $pdo = $this->getPDO();
        $sql = "INSERT INTO tbl_tabela_item (tabela, peca, preco) VALUES({$tabela}, {$peca}, '0.00')";
        $query = $pdo->query($sql);

    }

    /* Verifica Limite Mínimo */
    public function getLimiteMinimo($condicao_pagamento = null, $pedido = null){

        if(empty($pedido)) {
            $pedido = $this->_pedido;
        }

        $pdo = $this->getPDO();

        $sql = "
            SELECT limite_minimo
            FROM tbl_pedido
            JOIN tbl_condicao USING (condicao)
            WHERE tbl_pedido.pedido = {$pedido}
            AND tbl_pedido.condicao = {$condicao_pagamento}
        ";

        $query = $pdo->query($sql);

        if($query){
            $res    = $query->fetch(\PDO::FETCH_ASSOC);
            return $res['limite_minimo'];
        }

        return 0;

    }

    public function somaGravaTotalPedido($tabela = null, $pedido = null){

        if(empty($pedido)) {
            $pedido = $this->_pedido;
        }

        $pdo = $this->getPDO();

        $sql = "
            UPDATE tbl_pedido SET
                total = round (
                        (
                            SELECT SUM(tbl_pedido_item.preco * tbl_pedido_item.qtde)
                            FROM tbl_pedido_item
                            WHERE tbl_pedido_item.pedido = {$pedido}
                        )::numeric , 2
                ),
                tabela = {$tabela}
            WHERE tbl_pedido.pedido = {$pedido}
        ";

        $query = $pdo->query($sql);

        return ($query) ? true : false;

    }

    /**
     * Coloca pedido em auditoria
     *
     * @param integer $status
     * @return boolean
     */
    public function auditoria($status, $pedido) {
        if (!strlen($status)) {
            throw new \Exception("Status da auditoria não informado para a geração de Pedido");
        } else if (empty($pedido)) {
            throw new \Exception("Pedido não informado para atualizar o status de auditoria");
        } else {
            $this->update()
                 ->setCampos(array('status_pedido' => $status))
                 ->addWhere(array('pedido' => $pedido))
                 ->addWhere(array('fabrica' => $this->_fabrica));

            if (!$this->prepare()->execute()) {
                return false;
            } else {
                return true;
            }
        }
    }


    public function PegaPedidoItem($pedido){
        if (empty($pedido)) {
            throw new Exception("Pedido não informado");
        } else {
            $this->select("tbl_pedido_item")
                 ->setCampos(array("tbl_pedido_item.qtde, pedido_item"))
                 ->addWhere(array("pedido" => $pedido));
            if (!$this->prepare()->execute()) {
                throw new \Exception("Erro ao buscar itens do pedido : {$pedido}");
            } else {
                $res = $this->getPDOStatement()->fetchAll();
                return $res;
            }
        }
    }


    /**
     * Soma o total do pedido
     *
     * @param integer $pedido
     * @return float
     */
    public function somaTotalPedido($pedido) {
        if (empty($pedido)) {
            throw new Exception("Pedido não informado na soma total do pedido");
        } else {
            $this->select("tbl_pedido_item")
                 ->setCampos(array("SUM(tbl_pedido_item.total_item) AS total"))
                 ->addWhere(array("pedido" => $pedido));
            if (!$this->prepare()->execute()) {
                throw new \Exception("Erro ao totalizar pedido : {$pedido}");
            } else {
                $res = $this->getPDOStatement()->fetch();

                return $res["total"];
            }
        }
    }

    /**
     * Pega o valor minimo da condição de pagamento do pedido
     *
     * @param integer $pedido
     * @return float
     */
    public function getValorMinimo($pedido) {
        if (empty($pedido)) {
            throw new \Exception("Pedido não informado para selecionar o valor mínimo");
        } else {
            $this->select("tbl_condicao")
                 ->setCampos(array("tbl_condicao.limite_minimo"))
                 ->addJoin(array("tbl_pedido" => "ON tbl_pedido.condicao = tbl_condicao.condicao AND tbl_pedido.fabrica = {$this->_fabrica}"))
                 ->addWhere(array("tbl_condicao.fabrica" => $this->_fabrica))
                 ->addWhere(array("tbl_pedido.pedido" => $pedido))
                 ->addWhere("tbl_condicao.visivel IS TRUE");

            if (!$this->prepare()->execute()) {
                throw new \Exception("Erro ao selecionar o valor minimo do pedido : {$pedido}");
            } else {
                $res = $this->getPDOStatement()->fetch();

                return $res["limite_minimo"];
            }
        }
    }
     /**
     * Pega o valor crédito do pedido
     *
     * @param integer $posto
     * @return float
     */
    public function getCredito($posto) {
        if (empty($posto)) {
            throw new \Exception("Posto não informado para selecionar o crédito");
        } else {
            $this->select("tbl_posto_fabrica")
                 ->setCampos(array("credito"))
                 ->addWhere(array("posto" => $posto))
                 ->addWhere(array("fabrica" => $this->_fabrica));

            if (!$this->prepare()->execute()) {
                throw new \Exception("Erro ao selecionar o valor crétido do posto : {$posto}");
            } else {
                $res = $this->getPDOStatement()->fetch();

                return $res["credito"];
            }
        }
    }

    /**
     * Pega o total do pedido
     *
     * @param integer $pedido
     * @return float
     */
    public function getTotalPedido($pedido) {
        if (empty($pedido)) {
            throw new \Exception("Pedido não informado para selecionar o total do pedido");
        } else {
            $this->select("tbl_pedido")
                 ->setCampos(array("total"))
                 ->addWhere(array("fabrica" => $this->_fabrica))
                 ->addWhere(array("pedido" => $pedido));

            if (!$this->prepare()->execute()) {
                throw new \EXception("Erro ao selecionar o total do pedido : {$pedido}");
            } else {
                $res = $this->getPDOStatement()->fetch();

                return $res["total"];
            }
        }
    }

    /**
     * Pega a tabela de preço do posto autorizado
     *
     * @param integer $posto
     * @param integer $tipo_pedido
     * @return integer
     */
    public function getTabelaPreco($posto, $tipo_pedido, $os = null, $linha = null) {
        if (empty($posto)) {
            throw new \Exception("Posto Autorizado não informado para selecionar a tabela de preço");
        } else {
            if ($this->isPedidoGarantia($tipo_pedido) === true) {
                $tabela = "tbl_posto_linha.tabela AS tabela_preco";
            } else {
                $this->select("tbl_tipo_pedido")->setCampos(array("uso_consumo"))->addWhere(array("fabrica"=>$this->_fabrica))->addWhere(array("tipo_pedido"=>$tipo_pedido));

                if (!$this->prepare()->execute()) {
                   throw new \Exception("Erro ao selecionar o tipo de pedido : {$tipo_pedido}");
                } else {
                    if ($this->getPDOStatement()->rowCount() == 0) {
                        throw new \Exception("Tipo de pedido não encontrado. Tipo de Pedido : {$tipo_pedido}");
                    } else {
                        $res = $this->getPDOStatement()->fetch();
                        $uso_consumo = $res["uso_consumo"];
                    }
                }

                if($uso_consumo <> true ){
                    $coluna_tabela_preco = "tabela_posto";
                }else{
                    $coluna_tabela_preco = "tabela_bonificacao";
                }

        		$uso_consumo_tabela_posto = \Posvenda\Regras::get("uso_consumo_tabela_posto", "pedido_venda", $this->_fabrica);

        		if ($uso_consumo == true && $uso_consumo_tabela_posto == true) {
        			$coluna_tabela_preco = "tabela_posto";
        		}

                $tabela = "tbl_posto_linha.{$coluna_tabela_preco} AS tabela_preco";
            }

            if ($os != null) {
                $osClass = new \Posvenda\Os($this->_fabrica);
                $informacoesOs = $osClass->getInformacoesOs($os);
                $produto = $informacoesOs["produto"];
                $linha = $osClass->getProdutoLinha($produto);

                $this->select("tbl_posto_linha")
                     ->setCampos(array($tabela))
                     ->addJoin(array("tbl_tabela" => "ON tbl_tabela.tabela = tbl_posto_linha.tabela"))
                     ->addWhere(array("tbl_posto_linha.posto" => $posto))
                     ->addWhere(array("tbl_posto_linha.linha" => $linha))
                     ->addWhere("tbl_posto_linha.ativo IS TRUE")
                     ->addWhere(array("tbl_tabela.fabrica" => $this->_fabrica));
            } else {
	  	if ($linha != null){
                    $this->select("tbl_posto_linha")
                        ->setCampos(array($tabela))
                        ->addJoin(array("tbl_linha" => "ON tbl_linha.linha = tbl_posto_linha.linha AND tbl_linha.fabrica = {$this->_fabrica}"))
                        ->addWhere(array("tbl_posto_linha.posto" => $posto))
                        ->addWhere(array("tbl_posto_linha.linha" => $linha));
                }else{
                    $this->select("tbl_posto_linha")
                        ->setCampos(array($tabela))
                        ->addJoin(array("tbl_linha" => "ON tbl_linha.linha = tbl_posto_linha.linha AND tbl_linha.fabrica = {$this->_fabrica}"))
                        ->addWhere(array("tbl_posto_linha.posto" => $posto));
                }
	    }

            if (!$this->prepare()->execute()) {
                throw new \Exception("Erro ao selecionar a tabela de preço, para o posto : {$posto} e linha : {$linha}");
            } else {
                if ($this->getPDOStatement()->rowCount() == 0) {
                    throw new \Exception("Tabela de preço não encontrada para o posto : {$posto} e linha : {$linha}");
                } else {
                    $res = $this->getPDOStatement()->fetch();

                    return $res["tabela_preco"];
                }
            }
        }
    }

    public function getTabelaId($sigla_tabela) {
		if (empty($sigla_tabela)) {
			return null;
		}
			
		$this->select("tbl_tabela")
                     ->setCampos(array("tabela"))
		     ->addWhere(array("fabrica" => $this->_fabrica))
		     ->addWhere("UPPER(sigla_tabela) = '{$sigla_tabela}'");
		if (!$this->prepare()->execute()) {
            		throw new \Exception("Erro ao buscar tabela de preço");
	        } else {
                	if ($this->getPDOStatement()->rowCount() == 0) {
	                    throw new \Exception("Tabela de preço {$sigla_tabela} não encontrada");
        	        } else {
                	    $res = $this->getPDOStatement()->fetch();

	                    return $res["tabela"];
        	        }
                }

    }


    /**
     * Verifica se o tipo de pedido é para um pedido de garantia
     *
     * @param integer $tipo_pedido
     * @return boolean
     */
    public function isPedidoGarantia($tipo_pedido) {
        if (empty($tipo_pedido)) {
            throw new \Exception("Tipo de pedido não informado, para vefificar se é do tipo garantia");
        } else {
            $this->select("tbl_tipo_pedido")
                 ->setCampos(array("descricao"))
                 ->addWhere(array("fabrica" => $this->_fabrica))
                 ->addWhere("pedido_em_garantia IS TRUE")
                 ->addWhere(array("tipo_pedido" => $tipo_pedido));

            if (!$this->prepare()->execute()) {
                throw new \Exception("Erro ao verificar tipo de pedido : {$tipo_pedido} se é de garantia");
            } else {
                if ($this->getPDOStatement()->rowCount() == 0) {
                    return false;
                } else {
                    return true;
                }
            }
        }
    }


    /**
    * Verifica se o Tipo de Pedido existe
    * @param integer $pedido
    * @param array $tipo_pedido
    * @return boolean
    **/
    public function verificaTipoPedido($pedido, $tipo_pedido){

        if(empty($pedido)){

            throw new \Exception("Pedido não informado para verificar o Tipo de Pedido");

        }else if(!is_array($tipo_pedido) || !count($tipo_pedido)){

            throw new \Exception("Pedido / Tipo de Pedido não informado para verificar o Tipo de Pedido");

        }else{

            $this->select('tbl_pedido')
                ->setCampos(array('tbl_pedido.tipo_pedido'))
                ->addWhere(array('fabrica' => $this->_fabrica))
                ->addWhere(array('pedido' => $pedido));

            if (!$this->prepare()->execute()) {
                throw new \Exception("Erro ao verificar o tipo de pedido para o pedido : {$pedido}");
            } else {
                if ($this->getPDOStatement()->rowCount() == 0) {
                    return false;
                } else {
                    $res = $this->getPDOStatement()->fetch();
                    if(in_array($res["tipo_pedido"], $tipo_pedido)){
                        return true;
                    }else{
                        return false;
                    }
                }
            }

        }

    }

    public function verificaTipoPedidoGarantia($pedido) {
        if (empty($pedido)) {
            throw new \Exception("Pedido não informado");
        }

        $this->select("tbl_pedido")
             ->setCampos(array("tbl_tipo_pedido.pedido_em_garantia", "tbl_tipo_pedido.garantia_antecipada"))
             ->addJoin(array("tbl_tipo_pedido" => "ON tbl_tipo_pedido.tipo_pedido = tbl_pedido.tipo_pedido AND tbl_tipo_pedido.fabrica = {$this->_fabrica}"))
             ->addWhere(array("tbl_pedido.fabrica" => $this->_fabrica))
             ->addWhere(array("tbl_pedido.pedido" => $pedido));

        if (!$this->prepare()->execute()) {
            throw new \Exception("Erro ao verificar o tipo de pedido para o pedido: {$pedido}");
        }

        if ($this->getPDOStatement()->rowCount() == 0) {
            throw new \Exception("Tipo de pedido não encontrado para o pedido: {$pedido}");
        }

        $res = $this->getPDOStatement()->fetch();

        if ($res["pedido_em_garantia"] == "t" || $res["garantia_antecipada"] == "t") {
            return true;
        } else {
            return false;
        }
    }

     /**
    * Regra para Einhell pega desconto do tipo posto
    **/

    public function getDescontoTipoPosto($pedido){

        if (empty($pedido)) {
            throw new \Exception("Pedido não informado, para verificar o desconto do posto");
        }

        $this->select('tbl_pedido')
            ->setCampos(array("tbl_tipo_posto.descontos[1]"))
            ->addJoin(array("tbl_posto_fabrica" => "ON tbl_posto_fabrica.posto = tbl_pedido.posto AND tbl_posto_fabrica.fabrica = {$this->_fabrica}"))
            ->addJoin(array("tbl_tipo_posto" => "on tbl_tipo_posto.tipo_posto = tbl_posto_fabrica.tipo_posto AND tbl_tipo_posto.fabrica = {$this->_fabrica}"))
            ->addWhere(array("tbl_pedido.fabrica" => $this->_fabrica))
            ->addWhere(array("tbl_pedido.pedido" => $pedido));

            if (!$this->prepare()->execute()) {
                throw new \Exception("Erro ao verificar o Desconto do Pedido : {$pedido}");
            } else {
                if ($this->getPDOStatement()->rowCount() == 0) {
                    throw new \Exception("Posto não encontrado para o Pedido : {$pedido}");
                } else {
                    $res = $this->getPDOStatement()->fetch();
                    $desconto = (strlen($res["descontos"]) > 0) ? $res["descontos"] : 0;
                    return $desconto;
                }
            }
    }

    /**
    * Resgata o desconto do Posto
    * @param integer $pedido
    * @return integer
    **/
    public function getDescontoPosto($pedido){

        if (empty($pedido)) {
            throw new \Exception("Pedido não informado, para verificar o desconto do posto");
        }

        $this->select('tbl_pedido')
            ->setCampos(array("tbl_posto_fabrica.desconto"))
            ->addJoin(array("tbl_posto_fabrica" => "ON tbl_posto_fabrica.posto = tbl_pedido.posto AND tbl_posto_fabrica.fabrica = {$this->_fabrica}"))
            ->addWhere(array("tbl_pedido.fabrica" => $this->_fabrica))
            ->addWhere(array("tbl_pedido.pedido" => $pedido));

            if (!$this->prepare()->execute()) {
                throw new \Exception("Erro ao verificar o Desconto do Pedido : {$pedido}");
            } else {
                if ($this->getPDOStatement()->rowCount() == 0) {
                    throw new \Exception("Posto não encontrado para o Pedido : {$pedido}");
                } else {
                    $res = $this->getPDOStatement()->fetch();
                    $desconto = (strlen($res["desconto"]) > 0) ? $res["desconto"] : 0;
                    return $desconto;
                }
            }

    }

    /**
     * Grava o pedido
     *
     * @param array $dados
     * @return boolean
     */
    public function insertPedido($dados) {
        if (!is_array($dados) || !count($dados)) {
            throw new \Exception("Dados inválidos para cadastrar o pedido");
        } else {
            unset($dados['hd_chamado']);
            $this->insert("tbl_pedido")
                 ->setCampos($dados);
            if (!$this->prepare()->execute()) {
                return false;
            } else {
                return true;
            }
        }
    }

    /**
     * Atualiza o pedido
     *
     * @param array $dados
     * @param integer $pedido
     * @return boolean
     */
    public function updatePedido($dados, $pedido) {
        /* var_dump($dados); */

        if (!is_array($dados) || !count($dados)) {
            throw new \Exception("Dados inválidos para atualizar o pedido");
        } else if (empty($pedido)) {
            throw new \Exception("Pedido não informado para realizar a atualização");
        } else {
            $this->update("tbl_pedido")
                 ->setCampos($dados)
                 ->addWhere(array("pedido" => $pedido))
                 ->addWhere(array("fabrica" => $this->_fabrica));

                #echo $this->prepare()->getQuery();

            if (!$this->prepare()->execute()) {
                return false;
            } else {
                return true;
            }
        }
    }

     /**
     * Grava o número do pedido na tbl_hd_chamado_extra
     * Caso o pedido tenha sido gerado através de um atendimento callcenter
     *
     * @param integer $pedido
     * @param integer $hd_chamado
     * @return boolean
     */
    public function atualizaHdChamadoPedido($pedido, $hd_chamado) {
        if (empty($pedido)) {
            throw new \Exception("Pedido não informado para atualizar no chamado referente");
        } else if(empty($hd_chamado)){
            throw new \Exception("Atendimento não informado para realizar a autorização do Pedido");
        } else {
            $this->update("tbl_hd_chamado_extra")
                 ->setCampos(array("pedido" => $pedido))
                 ->addWhere(array("hd_chamado" => $hd_chamado));
            if (!$this->prepare()->execute()) {
                return false;
            } else {
                return true;
            }
        }
    }

         /**
     * Grava o número do pedido na tbl_hd_chamado_extra
     * Caso o pedido tenha sido gerado através de um atendimento callcenter
     *
     * @param integer $pedido
     * @param integer $hd_chamado
     * @return boolean
     */
    public function insereHdChamadoItemsPedido($pedido, $hd_chamado) {
        global $login_admin;

        if (empty($pedido)) {
            throw new \Exception("Pedido não informado para atualizar no atendimento referente");
        } else if(empty($hd_chamado)){
            throw new \Exception("Atendimento não informado para realizar a autorização do Pedido");
        } else {
            $dados = [
                        "hd_chamado" => $hd_chamado,
                        "pedido" => $pedido, 
                        "comentario" => "'Foi registrado o pedido $pedido para o atendimento'",
                        "status_item" => "'".$this->getStatusAtendimento($hd_chamado)."'",
                        "interno" => "'t'",
                        "admin" => $login_admin
                    ];

            $this->insert("tbl_hd_chamado_item")
                 ->setCampos($dados);

            if (!$this->prepare()->execute()) {
                return false;
            } else {
                return true;
            }
        }
    }

    /**
    * Insere o Desconto no Pedido
    *
    * @param integer pedido
    * @param float desconto_pedido
    * @return boolean
    **/
    public function setDescontoPedido($pedido, $desconto_pedido){

        if(empty($pedido)){
            throw new \Exception("Pedido não informado para realizar o desconto");
        }elseif(empty($desconto_pedido)){
            throw new \Exception("Desconto do Pedido não informado para realizar a atualização");
        }

        $this->update("tbl_pedido")
            ->setCampos(array("desconto" => $desconto_pedido))
            ->addWhere(array("pedido" => $pedido))
            ->addWhere(array("fabrica" => $this->_fabrica));

        if (!$this->prepare()->execute()) {
            return false;
        } else {
            return true;
        }

    }

    /**
     * Grava o item do pedido
     *
     * @param array $dados
     * @return boolean
     */
    public function insertPedidoItem($dados) {
        if (!is_array($dados) || !count($dados)) {
            throw new \Exception("Dados inválidos para inserir no Item do Pedido");
        } else {
            $this->insert("tbl_pedido_item")
                 ->setCampos($dados);
            if (!$this->prepare()->execute()) {
                return false;
            } else {
                return true;
            }
        }
    }

    /**
     * Atualiza o item do pedido
     *
     * @param array $dados
     * @param integer $pedido_item
     * @return boolean
     */
    public function updatePedidoItem($dados, $pedido_item) {
        if (!is_array($dados) || !count($dados)) {
            throw new \Exception("Dados inválidos para realizar a atualização do item do pedido");
        } else if (empty($pedido_item)) {
            throw new \Exception("ID do Pedido Item não informado para realizar a atualização do Pedido");
        } else {
            $this->update("tbl_pedido_item")
                 ->setCampos($dados)
                 ->addWhere(array("pedido_item" => $pedido_item))
                 ->addWhere(array("pedido" => $this->_pedido));

            if (!$this->prepare()->execute()) {
                return false;
            } else {
                return true;
            }
        }
    }

	/**
	 * Atualiza OS Troca quando é troca de produto
	 * (executar manual)
	 * @param  integer $os
	 * @param  integer $pedido
	 * @param  optional integer $item
	 * @return boolean
	 */
	public function atualizaOsTroca($os, $pedido, $item=null) {
		if (!is_numeric($os))
			throw new \Exception("OS não informada!");

		if (!is_numeric($pedido))
			throw new \Exception("Pedido não informado!");

		if (!is_null($item) and !is_numeric($item))
			throw new \Exception("Pedido ítem não é válido!");

		$camposUpdate['pedido'] = $pedido;

		if (!is_null($item))
			$camposUpdate['pedido_item'] = $item;

		$this->update('tbl_os_troca')
			->setCampos($camposUpdate)
			->addWhere(array('os' => $os))
			->addWhere(array('fabric' => $this->_fabrica));

		if (!$this->prepare()->execute()) {
			return false;
		} else {
			return true;
		}
	}

    /**
    * Resgata o Erro
    *
    * @return string
    */
    public function getErro(){

        if(is_array($this->_erro)){
            return $this->_erro["2"];
        }else{
            return $this->_erro;
        }
    }

}

