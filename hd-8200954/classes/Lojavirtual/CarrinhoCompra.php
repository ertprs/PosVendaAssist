<?php
namespace Lojavirtual;
use Lojavirtual\Controller;
use Lojavirtual\Produto;
use Lojavirtual\LojaCliente;
use Lojavirtual\Loja;
use Lojavirtual\Fornecedor;
use Posvenda\Pedido;

class CarrinhoCompra extends Controller {
    private $lojaCliente;
    private $produto;
    private $_pedido;
    private $_lojaDados;
    private $_fornecedor;

    public function __construct() {
        parent::__construct();
        $this->produto      = new Produto();
        $this->lojaCliente  = new LojaCliente();
        $this->_lojaDados   = new Loja();
        $this->_pedido      = new Pedido();
        $this->_fornecedor  = new Fornecedor();
    }
    /*
    *   Retorna todos
    */

    public function getAllCarrinho($loja_b2b_cliente,$pedido,$aberto=true,$carrinho=null) {
      global $moduloB2BGrade;
        $retorno = array();
        $cond_carrinho = "";
        if(!empty($pedido)) {
          $cond = " AND tbl_loja_b2b_carrinho.aberto is false "; 
          $limit = " order by loja_b2b_carrinho desc limit 1; "; 
        }else if ($aberto) {
          $cond = " AND tbl_loja_b2b_carrinho.aberto "; 
        }

        if (!empty($carrinho)) {
            $cond_carrinho = "AND tbl_loja_b2b_carrinho.loja_b2b_carrinho = $carrinho";
        }
       
        $sql = "SELECT 
                          tbl_loja_b2b_carrinho.loja_b2b_carrinho,
                          tbl_loja_b2b_carrinho.loja_b2b_cliente,
                          tbl_loja_b2b_carrinho.loja_b2b_cupom_desconto,
                          tbl_loja_b2b_carrinho.aberto,
                          tbl_loja_b2b_carrinho.forma_envio,
                          tbl_loja_b2b_carrinho.total_frete,
                          tbl_posto.nome as nome_posto,
                          tbl_posto.cnpj as cnpj_posto,
                          tbl_posto.ie   as inscricao_estadual,
                          tbl_posto_fabrica.contato_cep  as cep_posto,
                          tbl_posto.endereco as endereco_posto,
                          tbl_posto.numero as numero_posto,
                          tbl_posto.complemento as complemento_posto,
                          tbl_posto.estado as estado_posto,
                          tbl_posto.cidade as cidade_posto,
                          tbl_posto.bairro as bairro_posto,
                          tbl_posto_fabrica.contato_email as email_posto,
                          tbl_posto_fabrica.contato_fone_comercial as telefone_posto,
                          (
                            SELECT TO_CHAR(tbl_loja_b2b_carrinho_item.data_input, 'DD/MM/YYYY')
                            FROM tbl_loja_b2b_carrinho_item
                            WHERE tbl_loja_b2b_carrinho_item.loja_b2b_carrinho = tbl_loja_b2b_carrinho.loja_b2b_carrinho
                            ORDER BY data_input DESC
                            LIMIT 1
                          ) as data_ultimo_item
                     FROM tbl_loja_b2b_carrinho
                     JOIN tbl_loja_b2b ON tbl_loja_b2b_carrinho.loja_b2b = tbl_loja_b2b.loja_b2b
                     LEFT JOIN tbl_loja_b2b_cliente 
                     ON tbl_loja_b2b_cliente.loja_b2b_cliente = tbl_loja_b2b_carrinho.loja_b2b_cliente
                     LEFT JOIN tbl_posto ON tbl_posto.posto = tbl_loja_b2b_cliente.posto
                     LEFT JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_loja_b2b_cliente.posto
                     AND tbl_posto_fabrica.fabrica = tbl_loja_b2b.fabrica
                    WHERE  tbl_loja_b2b_carrinho.loja_b2b_cliente = $loja_b2b_cliente
          AND tbl_loja_b2b_carrinho.loja_b2b = {$this->_loja}
                    $cond_carrinho
                    $cond
          $limit ";
        $res = pg_query($this->_con, $sql);
        if (pg_last_error($this->_con)) {
            return array("erro" => true, "msn" => traduz(["erro.ao.buscar", "carrinho.de.compras"]));
        }
        if (pg_num_rows($res) > 0) {
            $rows = pg_fetch_assoc($res);
            $rows["itens"] = $this->getItensCarrinho($rows["loja_b2b_carrinho"]);
            $total_pedido  = $this->getTotalCarrinho($loja_b2b_cliente, $carrinho);

            if($this->_fabrica == 42){
                $rows["data_ultimo_item"] = date('d/m/Y H:i');

                if($carrinho){
                  $this->alteraDataCarrinho($loja_b2b_cliente, $carrinho, date('Y-m-d H:i:s'));
                } 
            }
            
            $dadosCliente = $this->lojaCliente->get($rows["loja_b2b_cliente"], null);

            $rows["condicaopagamento"] = $this->condicaoPedidoB2B($this->regrasCondicao($total_pedido, $dadosCliente["posto"], $dadosCliente["contato_estado"]));

            // Verifica se o valor dos itens é o mesmo do campo tbl_loja_b2b_peca.preco, se não, substitui o valor
            $rows["itens"] = $this->verificaValoresItensCarrinho($rows["itens"]);

            $retorno = $rows;
        }

        return $retorno;
    }

    public function alteraDataCarrinho($loja_b2b_cliente, $carrinho, $data){

        $sql_update = "UPDATE tbl_loja_b2b_carrinho 
                       SET data_input = '{$data}' 
                       WHERE tbl_loja_b2b_carrinho.loja_b2b_cliente = $loja_b2b_cliente AND 
                       tbl_loja_b2b_carrinho.loja_b2b = {$this->_loja} AND
                       tbl_loja_b2b_carrinho.loja_b2b_carrinho = $carrinho";

        $res_update = pg_query($this->_con, $sql_update);
        if (pg_last_error($this->_con) || !$res_update) {
           var_dump(pg_last_error($this->_con));
        }
    }

    /*
    *   Retorna todos
    */
    public function getItensCarrinho($loja_b2b_carrinho) {
      global $moduloB2BGrade;
        $retorno = array();

        if (strlen($this->_loja) == 0) {
            return array("erro" => true, "msn" => traduz("loja.nao.encontrada"));
        }

        if (strlen($loja_b2b_carrinho) == 0) {
            return array("erro" => true, "msn" => traduz("carrinho.não.encontrado"));
        }
        if ($moduloB2BGrade) {
          $condGrade = " LEFT JOIN tbl_loja_b2b_peca_grade USING(loja_b2b_peca_grade)";
          $campoGrade = " tbl_loja_b2b_peca_grade.tamanho,";
        }
        $sql = "SELECT 
                          tbl_loja_b2b_carrinho_item.loja_b2b_carrinho_item,
                          tbl_loja_b2b_carrinho_item.loja_b2b_carrinho,
                          tbl_loja_b2b_carrinho_item.loja_b2b_peca,
                          tbl_loja_b2b_carrinho_item.loja_b2b_kit_peca,
                          tbl_loja_b2b_carrinho_item.qtde,
                          $campoGrade
                          tbl_loja_b2b_carrinho_item.valor_unitario
                     FROM tbl_loja_b2b_carrinho_item
                     $condGrade 
                    WHERE tbl_loja_b2b_carrinho_item.loja_b2b_carrinho = {$loja_b2b_carrinho}
                      AND tbl_loja_b2b_carrinho_item.ativo IS TRUE";
        $res = pg_query($this->_con, $sql);

        if (pg_last_error($this->_con)) {
            return array("erro" => true, "msn" => traduz(["erro.ao.buscar", "carrinho.de.compras"]));
        }

        $produtos = array();
        foreach (pg_fetch_all($res) as $key => $value) {
        
                $produtos = $this->produto->get($value["loja_b2b_peca"]);
                $retorno[$key] = $value;
                $retorno[$key]["produto"] = $produtos;

        }

        return $retorno;
    }

    /*
    *   abre carrinho
    */
    public function abreCarrinho($dados = array()) {


        if (empty($dados)) {
            return array("erro" => true, "msn" => traduz("dados.não.enviado"));
        }

        if (isset($dados["posto"]) && strlen($dados["posto"]) > 0) {
            //busca dados do cliente na loja
            $dadosCliente = $this->lojaCliente->get(null, $dados["posto"]);

            //caso o posto nao esteja cadastrado, cria o cadastro dele
            if (empty($dadosCliente)) {
                $insereCliente = $this->lojaCliente->savePosto($dados["posto"]);
                $dadosCliente["loja_b2b_cliente"]  = $insereCliente["loja_b2b_cliente"];
            }
        }

        $sql = "INSERT INTO tbl_loja_b2b_carrinho (
                                        loja_b2b, 
                                        loja_b2b_cliente
                                    ) VALUES (
                                        ".$this->_loja.",
                                        ".$dadosCliente["loja_b2b_cliente"]."
                                    ) RETURNING loja_b2b_carrinho;";
        $res = pg_query($this->_con, $sql);
        if (pg_last_error($this->_con) || !$res) {
            return array("erro" => true, "msn" => traduz(["erro.ao.cadastrar", "carrinho.de.compras"]));
        }

        $loja_b2b_carrinho = pg_fetch_result($res, 0, 0);
        return array("loja_b2b_carrinho" => $loja_b2b_carrinho, "sucesso" => true);
    }

    /*
    *   add item carrinho
    */
    public function addItemCarrinho($dados = array()) {
      global $moduloB2BGrade;
        if (empty($dados)) {
            return array("erro" => true, "msn" => traduz("dados.não.enviado"));
        }
        $campo = "";
        $value = "";
        $loja_b2b_kit_peca = "";
        if (isset($dados["loja_b2b_kit_peca"])) {
            $campo = "loja_b2b_kit_peca,";
            $value = $dados["loja_b2b_kit_peca"].",";
            $loja_b2b_kit_peca = $dados["loja_b2b_kit_peca"];
        }
        $campoGrade = "";
        $valueGrade = "";
        $loja_b2b_peca_grade = "";
        if (isset($dados["loja_b2b_peca_grade"]) && strlen($dados["loja_b2b_peca_grade"]) > 0) {
          $campoGrade = "loja_b2b_peca_grade,";
          $valueGrade = $dados["loja_b2b_peca_grade"].",";
          $loja_b2b_peca_grade = $dados["loja_b2b_peca_grade"];


        }

        //verifica se ja existe o produto add no carrinho, se sim atualiza, senao adiciona
        $dadosItem = $this->verificaItemCarrinho($dados);

        if (!empty($dadosItem) && !$moduloB2BGrade) {

            $qtde = $dados["qtde"]+$dadosItem["qtde"];
            $retorno = $this->atualizaItemCarrinho($dadosItem["loja_b2b_carrinho_item"], $qtde,$loja_b2b_peca_grade);
            if ($retorno["erro"]) {
                return array("erro" => true, "msn" => traduz(["erro.ao.adicionar", "produto"]));
            }

            return array("sucesso" => true, "msn" => traduz(["produto", "adicionado.com.sucesso"]));

        } else {

            $sql = "INSERT INTO tbl_loja_b2b_carrinho_item (
                                            loja_b2b_carrinho, 
                                            loja_b2b_peca, 
                                            $campo
                                            $campoGrade
                                            qtde, 
                                            valor_unitario
                                        ) VALUES (
                                            ".$dados["loja_b2b_carrinho"].",
                                            ".$dados["loja_b2b_peca"].",
                                            {$value}
                                            {$valueGrade}
                                            '".$dados["qtde"]."',
                                            '".$dados["valor_unitario"]."'
                                        )";
            $res = pg_query($this->_con, $sql);

            if (pg_last_error($this->_con) || !$res) {
                return array("erro" => true, "msn" => traduz(["erro.ao.adicionar2", "produto"]));
            }

            return array("sucesso" => true, "msn" => traduz(["produto", "adicionado.com.sucesso"]));

        }
    }

    /*
    *   remove item carrinho
    */
    public function removeItemCarrinho($id, $kit = false) {

        if (empty($id)) {
            return array("erro" => true, "msg" => traduz("item.não.enviado"));
        }

        if ($kit == true) {
            $cond = " loja_b2b_kit_peca={$id}";
        } else {
            $cond = " loja_b2b_carrinho_item={$id}";
        }
        $sql = "UPDATE tbl_loja_b2b_carrinho_item SET ativo='f' WHERE {$cond}";
        $res = pg_query($this->_con, $sql);

        if (pg_last_error($this->_con) || !$res) {
            return array("erro" => true, "msg" => traduz(["erro.ao.adicionare", "produto"]));
        }

        return array("sucesso" => true, "msg" => traduz(["produto", "removido.com.sucesso"]));
    }

    /*
    *   atualiza item carrinho
    */
    public function atualizaItemCarrinho($id, $qtde, $loja_b2b_peca_grade = null) {

        if (empty($id)) {
            return array("erro" => true, "msg" => traduz("item.não.enviado"));
        }
        $campoGrade = "";
        if (strlen($loja_b2b_peca_grade) > 0) {
          $campoGrade = ",loja_b2b_peca_grade=".$loja_b2b_peca_grade;


        }

        $sql = "UPDATE tbl_loja_b2b_carrinho_item 
                   SET qtde = $qtde {$campoGrade}
                 WHERE loja_b2b_carrinho_item={$id}";
        $res = pg_query($this->_con, $sql);

        if (pg_last_error($this->_con) || !$res) {
            return array("erro" => true, "msg" => traduz(["erro.ao.atualizar", "produto"]));
        }

        return array("sucesso" => true, "msg" => traduz(["produto", "atualizado.com.sucesso"]));
    }

    /*
    *   verifica se existe carrinho em aberto
    */
    public function verificaCarrinhoAberto($loja_b2b_cliente) {
        if (empty($loja_b2b_cliente)) {
            return false;
        }
        $retorno = array();
        $sql = "SELECT *
                     FROM tbl_loja_b2b_carrinho
          WHERE tbl_loja_b2b_carrinho.aberto IS TRUE
          and tbl_loja_b2b_carrinho.loja_b2b_cliente = $loja_b2b_cliente
                      AND tbl_loja_b2b_carrinho.loja_b2b = {$this->_loja}
                      AND tbl_loja_b2b_carrinho.loja_b2b_cliente = {$loja_b2b_cliente}
                          ";
        $res = pg_query($this->_con, $sql);
        if (pg_last_error($this->_con)) {
            return array("erro" => true, "msn" => traduz(["erro.ao.buscar", "carrinho.de.compras"]));
        }
        return (pg_num_rows($res) > 0) ? pg_fetch_assoc($res) : false;
    }

    /*
    *   retorna total de item adicionado no carrinho
    */
    public function getTotalItemCarrinho($loja_b2b_cliente) {

        $sql = " SELECT
                        linha
                    FROM (
                            SELECT loja_b2b_kit_peca AS linha
                              FROM tbl_loja_b2b_carrinho
                              JOIN tbl_loja_b2b_carrinho_item USING(loja_b2b_carrinho)
                             WHERE tbl_loja_b2b_carrinho.aberto IS TRUE
                               AND tbl_loja_b2b_carrinho_item.ativo IS TRUE
                AND tbl_loja_b2b_carrinho.loja_b2b_cliente = $loja_b2b_cliente
                               AND tbl_loja_b2b_carrinho.loja_b2b = {$this->_loja}
                               AND loja_b2b_kit_peca IS NOT  NULL
                           group by loja_b2b_kit_peca
                ) X 
                UNION
                SELECT
                        linha
                    FROM (
                            SELECT loja_b2b_carrinho_item AS linha
                              FROM tbl_loja_b2b_carrinho
                              JOIN tbl_loja_b2b_carrinho_item USING(loja_b2b_carrinho)
                             WHERE tbl_loja_b2b_carrinho.aberto IS TRUE
                               AND tbl_loja_b2b_carrinho_item.ativo IS TRUE
                and tbl_loja_b2b_carrinho.loja_b2b_cliente = $loja_b2b_cliente
                               AND tbl_loja_b2b_carrinho.loja_b2b = {$this->_loja}
                               AND loja_b2b_kit_peca IS NULL
                 ) X ";
        $res = pg_query($this->_con, $sql);
        if (pg_last_error($this->_con)) {
            return array("erro" => true, "msn" => traduz(["erro.ao.buscar", "carrinho.de.compras"]));
        }
        if (pg_num_rows($res) > 0) {
            $retorno["count"] = pg_num_rows($res);
        } else {
            $retorno["count"] = 0;
        }
        return $retorno;
    }

    public function getTotalCarrinho($loja_b2b_cliente,$loja_b2b_carrinho = null) {

        if (!empty($loja_b2b_carrinho)) {

            $cond = "AND tbl_loja_b2b_carrinho.loja_b2b_carrinho = $loja_b2b_carrinho";

        } else {

            $cond = "AND tbl_loja_b2b_carrinho.aberto IS TRUE";

        }

        $retorno = array();
        $sql = "SELECT sum(valor_unitario*qtde) AS total
                     FROM tbl_loja_b2b_carrinho
                     JOIN tbl_loja_b2b_carrinho_item USING(loja_b2b_carrinho)
                    WHERE tbl_loja_b2b_carrinho_item.ativo IS TRUE
                     {$cond}
           and tbl_loja_b2b_carrinho.loja_b2b_cliente = {$loja_b2b_cliente}
                      AND tbl_loja_b2b_carrinho.loja_b2b = {$this->_loja}
                      
                          ";
        $res = pg_query($this->_con, $sql);

        if (pg_last_error($this->_con)) {
            return array("erro" => true, "msn" => traduz(["erro.ao.buscar", "carrinho.de.compras"]));
        }
        return (pg_num_rows($res) > 0) ? pg_fetch_result($res, 0, total) : false;
    }


    /*
    *   retorna
    */
    public function verificaItemCarrinho($dados, $sem_peca = false, $aberto = true) {

        $retorno = array();

        if (!$sem_peca) {
            $cond = " AND tbl_loja_b2b_carrinho_item.loja_b2b_peca=".$dados["loja_b2b_peca"];
        }
        if (isset($dados["loja_b2b_kit_peca"])) {
            $cond .= " AND tbl_loja_b2b_carrinho_item.loja_b2b_kit_peca=".$dados["loja_b2b_kit_peca"];
        }

        if ($aberto) {
            $cond .= " AND tbl_loja_b2b_carrinho.aberto IS TRUE";
        }

        $sql = "SELECT *
                     FROM tbl_loja_b2b_carrinho
                     JOIN tbl_loja_b2b_carrinho_item USING(loja_b2b_carrinho)
                    WHERE tbl_loja_b2b_carrinho_item.ativo IS TRUE
                      AND tbl_loja_b2b_carrinho_item.loja_b2b_carrinho=".$dados["loja_b2b_carrinho"]."
                      $cond
                      AND tbl_loja_b2b_carrinho.loja_b2b = {$this->_loja}
                          ";
        $res = pg_query($this->_con, $sql);
        if (pg_last_error($this->_con)) {
            return array("erro" => true, "msn" => traduz(["erro.ao.buscar", "carrinho.de.compras"]));
        }
        if (pg_num_rows($res) > 0) {
            if (!$sem_peca) {
                return pg_fetch_assoc($res);
            }
            return pg_fetch_all($res);
        } else {
            return false;
        }
    }

    public function geraPedidoB2B($dados, $compagamento = false,  $dadosFrete = array()) {

        if (empty($dados)) {
            return array("erro" => true, "msg" => traduz("dados.do.pedido.não.foram.enviados"));
        }

        if ($dados["aberto"] <> 't') {
            return array("erro" => true, "msg" => traduz("dados.do.pedido.não.foram.enviados"));
        }

        //caso controla estoque, checa antes de gerar pedido
        if ($this->_lojaDados->_controlaEstoque == true) {
            $retorno_estoque = array();
            foreach ($dados["itens"] as $key => $rows) {
                if (!$this->checaEstoqueB2B($rows['loja_b2b_peca'], $rows['qtde'])) {
                    $retorno_estoque[] = utf8_encode("Peça ".trim($rows['produto']["nome_peca"])." está sem estoque");
                }
            }
            if (count($retorno_estoque) > 0) {
                return array("erro" => true, "msg" => implode("\n", $retorno_estoque));
            }
        }


        $dadosClientes = $this->lojaCliente->get($dados["loja_b2b_cliente"], null);
        $tipo_pedido = $this->getTipoPedido();
        if ($this->_fabrica == 15) {
          $tipoPedido = 89;
        } elseif ($this->_fabrica == 157) {
          $tipoPedido = 337;
        } else {
          $tipoPedido = $tipo_pedido;
        }

        $dadosPedido  = array(
            "posto"             => $dadosClientes["posto"],
            "fabrica"           => $this->_fabrica,
            "tipo_pedido"       => $tipoPedido,//faturado
            "condicao"          => $dados['condicaopagamento']['condicao'],
            "status_pedido"     => 1,//aguardando exportacao
            "tabela"            => null,
            "pedido_loja_virtual" => "'t'",
        );

        if ($this->_fabrica == 157) {
          unset($dadosPedido["condicao"]);
        }
        if (!empty($dadosFrete)) {
            $dadosPedido["valor_frete"]  = $dadosFrete['valorEnvio'];

            //buscar na tbl_forma_envio com o codigo de servico
            $retornoForma = $this->buscaFormaEnvio($dadosFrete['codigoEnvio'], $dadosFrete['servicoEnvio']);
            if (isset($retornoForma["erro"])) {
              return array("erro" => true, "msg" => $retornoForma["msg"]);
            }
            $dadosPedido["forma_envio"]  = "{$retornoForma}";
        }

        if ($compagamento == true) {
            $dadosPedido["status_pedido"]  = 2; //Aguardando Faturamento
        } else {
            $dadosPedido["status_pedido"]  = 1; //Aguardando exportacao  
        }

        $this->_pedido->gravaPedidoB2B($dadosPedido);
        $pedido = $this->_pedido->getPedido();

        if (isset($dados["itens"]) && strlen($pedido) > 0) {
            $valor_total_pedido = [];
            foreach ($dados["itens"] as $key => $rowsItens) {

                $dadosItens = array(
                    "pedido" => (int)$pedido,
                    "peca"   => $rowsItens["produto"]["peca"],
                    "qtde"   => $rowsItens["qtde"],
                    "preco"  => $rowsItens["valor_unitario"],
                );
                $valor_total_pedido[] = $rowsItens["qtde"]*$rowsItens["valor_unitario"];
                $retorno[] = $this->_pedido->gravaItemPedidoB2B($dadosItens, $pedido);

            }

            $sql = "UPDATE tbl_pedido SET total=".array_sum($valor_total_pedido).",  finalizado = current_timestamp
                     WHERE tbl_pedido.fabrica = {$this->_fabrica}
                       AND tbl_pedido.pedido=".$pedido;
            $res = pg_query($this->_con, $sql);

            
            if (count($retorno) > 0) {
                foreach ($retorno as $key => $value) {
                    if ($value["erro"]) {
                        return array("erro" => true, "msg" => $value["msg"]);
                    }
                }
                $this->fechaCarrinho($dados["loja_b2b_carrinho"]);

                if ($this->_lojaDados->_controlaEstoque == true) {
                    $this->insereMovimentoEstoqueB2B($dados, $pedido);
                }
                return array("sucesso" => true, "msg" => traduz("pedido.criado.com.sucesso"), "pedido" => $pedido);
            }
        } else {
            return array("erro" => true, "msg" => traduz(["erro.ao.cadastrar", "pedido"]));
        }
    }

    /*
    *   fecha carrinho aberto
    */
    public function fechaCarrinho($loja_b2b_carrinho) {

        $sql = "UPDATE tbl_loja_b2b_carrinho SET aberto = 'f'
                    WHERE tbl_loja_b2b_carrinho.loja_b2b_carrinho = {$loja_b2b_carrinho}
                      AND tbl_loja_b2b_carrinho.loja_b2b = {$this->_loja}
                          ";
        $res = pg_query($this->_con, $sql);

        if (pg_last_error($this->_con)) {
            return false;
        }
        return true;
    }

    public function getPedidoB2B($pedido, $compagamento = false) {
        $retorno = array();
        $sql = "SELECT  *, 
                          tbl_posto.nome as nome_posto,
                          tbl_posto.cnpj as cnpj_posto,
                          tbl_posto.ie   as inscricao_estadual,
                          tbl_posto_fabrica.contato_cep  as cep_posto,
                          tbl_posto.endereco as endereco_posto,
                          tbl_posto.numero as numero_posto,
                          tbl_posto.complemento as complemento_posto,
                          tbl_posto.estado as estado_posto,
                          tbl_posto.cidade as cidade_posto,
                          tbl_posto.bairro as bairro_posto,
                          tbl_posto_fabrica.contato_email as email_posto,
                          tbl_posto_fabrica.contato_fone_comercial as telefone_posto                  
                  FROM tbl_pedido
                  JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_pedido.posto
                  JOIN tbl_posto ON tbl_posto.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = {$this->_fabrica}
                 WHERE tbl_pedido.pedido = {$pedido} 
                   AND tbl_pedido.fabrica = {$this->_fabrica}";
        $res = pg_query($this->_con, $sql);

        if (pg_last_error($this->_con)) {
            return array("erro" => true, "msn" => traduz(["erro.ao.buscar", "pedido"]));
        }

        if (pg_num_rows($res) > 0) {
            $retorno = pg_fetch_assoc($res);
            if ($compagamento) {
                $retorno["dados_pagamento"] = $this->getPagamentoB2B($retorno["pedido"]);
            }
            $retorno["status"] = $this->statusPedidoB2B($retorno["status_pedido"]);
            $retorno["condicaopagamento"] = $this->condicaoPedidoB2B($retorno["condicao"]);
            $retorno["itens"]  = $this->getPedidoItemB2B($pedido);
            $retorno["forma_envio"]  = $this->getFormaEnvioPedidoB2B($retorno["forma_envio"]);

        }
        return $retorno;

    } 

    public function getAllCarrinhoPedido($condicoes = array()) {
        
        if (empty($condicoes)) {
            return array("erro" => true, "msn" => traduz(["erro.ao.buscar", "pedido"]));
        }

        if (isset($condicoes["numero_pedido"])) {
            $cond .= " AND tbl_loja_b2b_carrinho.loja_b2b_carrinho = ".$condicoes["numero_pedido"];
        }

        if (isset($condicoes["data_inicial"]) && isset($condicoes["data_final"])) {
            $cond .= " AND tbl_loja_b2b_carrinho.data_input BETWEEN '".$condicoes["data_inicial"]." 00:00:00' AND '".$condicoes["data_final"]." 23:59:59'";
        }

        if (isset($condicoes["posto"])) {
            $cond .= " AND tbl_loja_b2b_cliente.posto = ".$condicoes["posto"];
        }

        $retorno = array();
         $sql = "SELECT 
                          tbl_loja_b2b_carrinho.loja_b2b_carrinho,
                          tbl_loja_b2b_carrinho.loja_b2b_cliente,
                          tbl_loja_b2b_carrinho.loja_b2b_cupom_desconto,
                          tbl_loja_b2b_carrinho.aberto,
                          tbl_loja_b2b_carrinho.gera_pedido,
                          TO_CHAR(tbl_loja_b2b_carrinho.data_input, 'DD/MM/YYYY HH24:MI') AS data_pedido,
                          tbl_loja_b2b_cliente.posto
                     FROM tbl_loja_b2b_carrinho
                     JOIN tbl_loja_b2b_cliente ON tbl_loja_b2b_carrinho.loja_b2b_cliente = tbl_loja_b2b_cliente.loja_b2b_cliente
                    WHERE tbl_loja_b2b_carrinho.loja_b2b = {$this->_loja}
                    AND tbl_loja_b2b_carrinho.gera_pedido IS NOT TRUE
                    AND tbl_loja_b2b_carrinho.aberto IS NOT TRUE
                    $cond";
        $res = pg_query($this->_con, $sql);
        if (pg_last_error($this->_con)) {
            return array("erro" => true, "msn" => traduz(["erro.ao.buscar", "carrinho.de.compras"]));
        }
        if (pg_num_rows($res) > 0) {

            $retorno = pg_fetch_all($res);

            foreach ($retorno as $key => $rows) {

                $retorno[$key]["itens"]             = $this->verificaItemCarrinho($rows, true, false);

                $retorno[$key]["dadosposto"]        = $this->lojaCliente->get(null, $rows["posto"]);

                $retorno[$key]['nome_fornecedor']   = $this->retornaFornecedorCarrinho($rows['loja_b2b_carrinho']);

            }
            
            return $retorno;
        }

    }      

    public function retornaFornecedorCarrinho($loja_b2b_carrinho) {
       
        $sql = "SELECT DISTINCT tbl_loja_b2b_fornecedor.nome as nome_fornecedor
                  FROM tbl_loja_b2b_carrinho
                  JOIN tbl_loja_b2b_carrinho_item ON tbl_loja_b2b_carrinho_item.loja_b2b_carrinho = tbl_loja_b2b_carrinho.loja_b2b_carrinho
                  JOIN tbl_loja_b2b_peca ON tbl_loja_b2b_carrinho_item.loja_b2b_peca = tbl_loja_b2b_peca.loja_b2b_peca
                  JOIN tbl_loja_b2b_fornecedor ON tbl_loja_b2b_peca.loja_b2b_fornecedor = tbl_loja_b2b_fornecedor.loja_b2b_fornecedor
                 WHERE tbl_loja_b2b_peca.loja_b2b_fornecedor IS NOT NULL
                 AND tbl_loja_b2b_carrinho.loja_b2b_carrinho = {$loja_b2b_carrinho}
                 AND tbl_loja_b2b_carrinho.loja_b2b = {$this->_loja}
                 AND tbl_loja_b2b_carrinho_item.ativo IS TRUE";
        $res = pg_query($this->_con, $sql);

        if (pg_last_error($this->_con)) {
            return array("erro" => true, "msg" =>  traduz(["erro.ao.buscar", "fornecedor"]));
        } else {
            return pg_fetch_result($res, 0, 'nome_fornecedor');
        }
    }

    public function getAllPedidoB2B($condicoes = array()) {
        
        if (empty($condicoes)) {
            return array("erro" => true, "msn" => traduz(["erro.ao.buscar", "pedido"]));
        }
        $cond = " 1=1 ";
        $joinPagamento = "";
        if (isset($condicoes["numero_pedido"])) {
            $cond .= " AND tbl_pedido.pedido = ".$condicoes["numero_pedido"];
        }

        if (isset($condicoes["data_inicial"]) && isset($condicoes["data_final"])) {
            $cond .= " AND tbl_pedido.data BETWEEN '".$condicoes["data_inicial"]." 00:00:00' AND '".$condicoes["data_final"]." 23:59:59'";
        }
      
        if (isset($condicoes["pedido_status"])) {
            $cond .= " AND tbl_pedido.status_pedido = ".$condicoes["pedido_status"];
        }
        if (isset($condicoes["pedido_status_pagamento"])) {
            $condPgto   = $condicoes["pedido_status_pagamento"];

            $joinPagamento = " JOIN tbl_loja_b2b_pagamento USING(pedido)";
            $cond .= " AND tbl_loja_b2b_pagamento.status_pagamento = ".$condicoes["pedido_status_pagamento"];

        }

        if (isset($condicoes["posto"])) {
            $cond .= " AND tbl_pedido.posto = ".$condicoes["posto"];
        }

        $retorno = array();
        $sql = "SELECT  *, TO_CHAR(tbl_pedido.data, 'DD/MM/YYYY') AS data_pedido
                  FROM tbl_pedido 
                  $joinPagamento
                 WHERE $cond 
                   AND fabrica = {$this->_fabrica} 
                   AND pedido_loja_virtual IS TRUE";
        $res = pg_query($this->_con, $sql);

        if (pg_last_error($this->_con)) {
            return array("erro" => true, "msn" => traduz(["erro.ao.buscar", "pedido"]));
        }
        if (pg_num_rows($res) == 0) {
            return array("naoencontrado" => true, "msn" => traduz("nenhum.pedido.encontrado"));
        } else {
            $retorno = pg_fetch_all($res);
            foreach ($retorno as $key => $rows) {
                if ($this->_usacheckout == 'S') {
                    $retorno[$key]["dados_pagamento"] = $this->getPagamentoB2B($rows["pedido"], $condPgto);
                }
                $retorno[$key]["status"]            = $this->statusPedidoB2B($rows["status_pedido"]);
                $retorno[$key]["condicaopagamento"] = $this->condicaoPedidoB2B($rows["condicao"]);
                $retorno[$key]["itens"]             = $this->getPedidoItemB2B($rows["pedido"]);
                $retorno[$key]["dadosposto"]        = $this->lojaCliente->get(null, $rows["posto"]);
            }
            
            return $retorno;
        }
    }    

    public function statusPedidoB2B($status_pedido) {

        $retorno = array();
        $sql = "SELECT  * FROM tbl_status_pedido WHERE status_pedido = {$status_pedido}";
        $res = pg_query($this->_con, $sql);

        if (pg_last_error($this->_con)) {
            return array("erro" => true, "msn" => traduz(["erro.ao.buscar", "status.pedido"]));
        }

        return pg_fetch_assoc($res);

    }

    public function condicaoPedidoB2B($condicao) {

        $retorno = array();
        $sql = "SELECT  * FROM tbl_condicao WHERE condicao = {$condicao} AND fabrica = {$this->_fabrica}";
        $res = pg_query($this->_con, $sql);

        if (pg_last_error($this->_con)) {
            return array("erro" => true, "msn" => traduz(["erro.ao.buscar", "condição"]));
        }

        return pg_fetch_assoc($res);

    }


  public function getTipoPedido() {
        $retorno = array();
        $sql = "SELECT  tipo_pedido FROM tbl_tipo_pedido where pedido_faturado AND fabrica = {$this->_fabrica}";
        $res = pg_query($this->_con, $sql);

        if (pg_last_error($this->_con)) {
            return array("erro" => true, "msn" => pg_last_error($this->_con));
    }
    if(pg_num_rows($res) > 0) {
      $tipo_pedido = pg_fetch_result($res, 0, 'tipo_pedido'); 
      return $tipo_pedido;
    }else{
            return array("erro" => true, "msn" => 'sem tipo de pedido de venda');
    }

  }

    public function getPedidoItemB2B($pedido) {

        $retorno = array();
        $sql = "SELECT  * FROM tbl_pedido_item WHERE pedido = {$pedido}";
        $res = pg_query($this->_con, $sql);

        if (pg_last_error($this->_con)) {
            return array("erro" => true, "msn" => traduz(["erro.ao.buscar", "pedido"]));
        }

        return pg_fetch_all($res);

    }

    public function getFormaEnvioPedidoB2B($forma_envio) {

        if (strlen($this->_loja) == 0) {
            return array("erro" => true, "msn" => traduz("loja.nao.encontrada"));
        }

        if (count($forma_envio) == 0) {
            return array("erro" => true, "msn" => traduz("dados.não.enviado"));
        }
        
     
        $sql = "SELECT descricao, codigo_servico 
                  FROM tbl_forma_envio 
                 WHERE forma_envio  = ".$forma_envio ."
                   AND fabrica = $this->_fabrica";
        $res = pg_query($this->_con, $sql);
        if (pg_last_error($this->_con) || !$res) {
            return array("erro" => true, "msn" => traduz(["erro.ao.buscar.forma.de.envio"]));
        }

        return pg_fetch_assoc($res);

    }


    public function getPagamentoB2B($pedido, $status_pagamento = null) {
        $cond = "";
        if (strlen($this->_loja) == 0) {
            return array("erro" => true, "msn" => traduz("loja.nao.encontrada"));
        }

        if (count($pedido) == 0) {
            return array("erro" => true, "msn" => traduz("dados.não.enviado"));
        }
        if (strlen($status_pagamento) > 0) {
            $cond = " AND tbl_loja_b2b_pagamento.status_pagamento = ".$status_pagamento;
        }
     
        $sql = "SELECT * FROM tbl_loja_b2b_pagamento 
                        WHERE pedido = $pedido 
                        {$cond}
                        AND loja_b2b = $this->_loja";

        $res = pg_query($this->_con, $sql);
        if (pg_last_error($this->_con) || !$res) {
            return array("erro" => true, "msn" => traduz(["erro.ao.buscar", "pagamento"]));
        }

        return pg_fetch_assoc($res);



    }

    public function inserePagamentoB2B($dados, $tipo_pagamento, $totalPedido, $pedido) {

        if (strlen($this->_loja) == 0) {
            return array("erro" => true, "msn" => traduz("loja.nao.encontrada"));
        }

        if (count($dados) == 0) {
            return array("erro" => true, "msn" => traduz("dados.não.enviado"));
        }

        if ($tipo_pagamento == "BOLETO") {
         
            $sql = "INSERT INTO tbl_loja_b2b_pagamento 
                                        (
                                            loja_b2b, 
                                            pedido, 
                                            request,
                                            response,
                                            tipo_pagamento, 
                                            total_pedido
                                        ) VALUES (
                                            ".$this->_loja.",
                                            ".$pedido.",
                                            '".json_encode($dados)."',
                                            '{}',
                                            'B',
                                            ".$totalPedido."
                                        ) RETURNING loja_b2b_pagamento;";
            $res = pg_query($this->_con, $sql);
            if (pg_last_error($this->_con) || !$res) {
                return array("erro" => true, "msn" => traduz(["erro.ao.cadastrar", "pagamento"]));
            }

            $loja_b2b_pagamento = pg_fetch_result($res, 0, 0);
            return $loja_b2b_pagamento;

        }

        if ($tipo_pagamento == "CREDIT_CARD") {
            
            $sql = "INSERT INTO tbl_loja_b2b_pagamento 
                                        (
                                            loja_b2b, 
                                            pedido, 
                                            request,
                                            response,
                                            tipo_pagamento, 
                                            total_pedido
                                        ) VALUES (
                                            ".$this->_loja.",
                                            ".$pedido.",
                                            '".json_encode($dados)."',
                                            '{}',
                                            'C',
                                            ".$totalPedido."
                                        ) RETURNING loja_b2b_pagamento;";
            $res = pg_query($this->_con, $sql);
            if (pg_last_error($this->_con) || !$res) {
                return array("erro" => true, "msn" => traduz(["erro.ao.cadastrar", "pagamento"]));
            }

            $loja_b2b_pagamento = pg_fetch_result($res, 0, 0);
            return $loja_b2b_pagamento;

        }


    }

    public function atualizaPagamentoB2B($id_pagamento, $dados, $tipo_pagamento, $pedido, $status_pagamento) {


        if (strlen($this->_loja) == 0) {
            return array("erro" => true, "msn" => traduz("loja.nao.encontrada"));
        }
        if (empty($dados) || strlen($id_pagamento) == 0) {
            return array("erro" => true, "msn" => traduz("dados.não.enviado"));
        }

        if ($tipo_pagamento == "BOLETO") {

            $dataVenc = $dados["date"]; 

            $sql = "UPDATE tbl_loja_b2b_pagamento 
                       SET
                           loja_b2b = ".$this->_loja.",
                           response = '".json_encode($dados)."',
                           status_pagamento = '".$status_pagamento."', 
                           data_vencimento = '".date('Y-m-d', strtotime("+3 days",strtotime($dataVenc)))."'
                     WHERE pedido = ".$pedido." 
                       AND loja_b2b = ".$this->_loja;
            $res = pg_query($this->_con, $sql);
            if (pg_last_error($this->_con) || !$res) {
                return array("erro" => true, "msn" => traduz(["erro.ao.cadastrar", "pagamento"]));
            }

            return array("loja_b2b_pagamento" => $loja_b2b_pagamento);

        }

        if ($tipo_pagamento == "CREDIT_CARD") {

            $sql = "UPDATE tbl_loja_b2b_pagamento 
                       SET
                           loja_b2b = ".$this->_loja.",
                           response = '".json_encode($dados)."',
                           status_pagamento = '".$status_pagamento."'
                     WHERE pedido = ".$pedido." 
                       AND loja_b2b = ".$this->_loja;
            $res = pg_query($this->_con, $sql);
            if (pg_last_error($this->_con) || !$res) {
                return array("erro" => true, "msn" => traduz(["erro.ao.cadastrar", "pagamento"]));
            }

            return array("loja_b2b_pagamento" => $loja_b2b_pagamento);

        }

    }

    public function getStatusPagamento($pedido) {

        if (strlen($this->_loja) == 0) {
            return array("erro" => true, "msn" => traduz("loja.nao.encontrada"));
        }

        if (strlen($pedido) == 0) {
            return array("erro" => true, "msn" => traduz("dados.não.enviado"));
        }


        $sql = "SELECT status_pagamento 
                  FROM tbl_loja_b2b_pagamento 
                 WHERE pedido = ".$pedido." 
                  AND loja_b2b = ".$this->_loja;
        $res = pg_query($this->_con, $sql);
        if (pg_last_error($this->_con) || !$res) {
            return array("erro" => true, "msn" => traduz(["erro.ao.buscar", "status.pagamento"]));
        }

        return pg_fetch_result($res, 0, 'status_pagamento');

    }

    public function insereMovimentoEstoqueB2B($dados, $pedido) {

        if (strlen($this->_loja) == 0) {
            return array("erro" => true, "msn" => traduz("loja.nao.encontrada"));
        }

        if (empty($dados) || strlen($pedido) == 0) {
            return array("erro" => true, "msg" => traduz("dados.do.pedido.não.foram.enviados"));
        }

        foreach ($dados["itens"] as $k => $rows) {

            $this->baixaEstoqueB2B($rows);

            $sql = "INSERT INTO tbl_loja_b2b_movimentacao_estoque 
                                                                (
                                                                    loja_b2b, 
                                                                    loja_b2b_peca,
                                                                    pedido, 
                                                                    qtde_saida, 
                                                                    reservado
                                                                ) VALUES (
                                                                    ".$this->_loja.",
                                                                    ".$rows['loja_b2b_peca'].",
                                                                    ".$pedido.",
                                                                    ".$rows['qtde'].",
                                                                    't'
                                                                )";
            $res = pg_query($this->_con, $sql);
        }

        if (pg_last_error($this->_con)) {
            return array("erro" => true, "msn" => traduz("erro.ao.reservar.a.estoque"));
        }

        return array();

    }

    public function baixaEstoqueB2B($dados) {

        if (strlen($this->_loja) == 0) {
            return array("erro" => true, "msn" => traduz("loja.nao.encontrada"));
        }

        if (empty($dados)) {
            return array("erro" => true, "msg" => traduz("dados.do.pedido.não.foram.enviados"));
        }

        $sqle = "SELECT qtde_estoque 
                  FROM tbl_loja_b2b_peca 
                 WHERE loja_b2b_peca = ".$dados['loja_b2b_peca']." 
                   AND loja_b2b = ".$this->_loja;
        $rese = pg_query($this->_con, $sqle);

        if (pg_last_error($this->_con)) {
            return array("erro" => true, "msg" => traduz(["erro.ao.buscar", "estoque"]));
        }

        $estoqueAtual = pg_fetch_result($rese, 0, "qtde_estoque");
        $novoEstoque  = $estoqueAtual-$dados['qtde'];
        $sqlAtualiza  = "UPDATE tbl_loja_b2b_peca 
                            SET qtde_estoque = ".$novoEstoque." 
                          WHERE loja_b2b_peca = ".$dados['loja_b2b_peca']." 
                            AND loja_b2b = ".$this->_loja;
        $resAtualiza  = pg_query($this->_con, $sqlAtualiza);

        if (pg_last_error($this->_con)) {
            return array("erro" => true, "msn" => traduz("erro.ao.reservar.a.estoque"));
        }
    }

    public function checaEstoqueB2B($loja_b2b_peca, $qtde) {

        if (strlen($this->_loja) == 0) {
            return array("erro" => true, "msn" => traduz("loja.nao.encontrada"));
        }

        if (empty($loja_b2b_peca) || strlen($qtde) == 0) {
            return array("erro" => true, "msg" => traduz("peça.e.ou.quantidade.não.informado"));
        }

        $sql = "SELECT qtde_estoque 
                  FROM tbl_loja_b2b_peca 
                 WHERE loja_b2b_peca = ".$loja_b2b_peca." 
                   AND loja_b2b = ".$this->_loja;
        $res = pg_query($this->_con, $sql);

        if (pg_last_error($this->_con)) {
            return array("erro" => true, "msg" =>  traduz(["erro.ao.buscar", "estoque"]));
        }

        $estoqueAtual = pg_fetch_result($res, 0, "qtde_estoque");

        if ($estoqueAtual >= $qtde) {
            return true;
        } else {
            return false;
        }
    }


    public function buscaFormaEnvio($codigo_servico, $servicoEnvio) {
      
      if (strlen($this->_loja) == 0) {
            return array("erro" => true, "msn" => traduz("loja.nao.encontrada"));
        }

        if (empty($codigo_servico) || strlen($codigo_servico) == 0) {
            return array("erro" => true, "msg" => traduz("codigo.do.servico.não.informado"));
        }

        $sql = "SELECT forma_envio 
                  FROM tbl_forma_envio 
                 WHERE codigo_servico = '".$codigo_servico."' 
                   AND fabrica = ".$this->_fabrica;
        $res = pg_query($this->_con, $sql);

        if (pg_last_error($this->_con)) {
            return array("erro" => true, "msg" =>  traduz(["erro.ao.buscar.forma.envio"]));
        }

        if (pg_num_rows($res) > 0) {
      return pg_fetch_result($res, 0, 'forma_envio');
        }

        $sqlX = "INSERT INTO tbl_forma_envio (descricao, fabrica, ativo, codigo_servico) VALUES ('".strtoupper(trim($servicoEnvio))."', {$this->_fabrica}, 't', '".trim($codigo_servico)."') RETURNING forma_envio";
        $resX = pg_query($this->_con, $sqlX);
        if (pg_last_error($this->_con)) {
            return array("erro" => true, "msg" =>  traduz(["erro.ao.buscar.forma.envio"]));
        }
    return pg_fetch_result($resX, 0, 'forma_envio');
    }


    public function verificaMesmoFornecedorCarrinho($fornecedor,$loja_b2b_carrinho) {

        $sqle = "SELECT tbl_loja_b2b_carrinho.loja_b2b_carrinho 
                  FROM tbl_loja_b2b_carrinho
                  JOIN tbl_loja_b2b_carrinho_item ON tbl_loja_b2b_carrinho_item.loja_b2b_carrinho = tbl_loja_b2b_carrinho.loja_b2b_carrinho
                  JOIN tbl_loja_b2b_peca ON tbl_loja_b2b_carrinho_item.loja_b2b_peca = tbl_loja_b2b_peca.loja_b2b_peca
                  AND tbl_loja_b2b_peca.loja_b2b_fornecedor != {$fornecedor}
                 WHERE tbl_loja_b2b_peca.loja_b2b_fornecedor IS NOT NULL
                 AND tbl_loja_b2b_carrinho.loja_b2b_carrinho = {$loja_b2b_carrinho}
                 AND tbl_loja_b2b_carrinho.loja_b2b = {$this->_loja}
                 AND tbl_loja_b2b_carrinho_item.ativo IS TRUE";
        $rese = pg_query($this->_con, $sqle);

        if (pg_num_rows($rese) > 0) {
            return array("erro" => true);
        } 

    }

    public function dadosFornecedorCarrinho($loja_b2b_carrinho) {
       
        $sql = "SELECT DISTINCT tbl_loja_b2b_fornecedor.nome as nome_fornecedor,
                                tbl_loja_b2b_fornecedor.celular as cel_fornecedor,
                                tbl_loja_b2b_fornecedor.fone as fone_fornecedor,
                                tbl_loja_b2b_fornecedor.email as email_fornecedor
                  FROM tbl_loja_b2b_carrinho
                  JOIN tbl_loja_b2b_carrinho_item ON tbl_loja_b2b_carrinho_item.loja_b2b_carrinho = tbl_loja_b2b_carrinho.loja_b2b_carrinho
                  JOIN tbl_loja_b2b_peca ON tbl_loja_b2b_carrinho_item.loja_b2b_peca = tbl_loja_b2b_peca.loja_b2b_peca
                  JOIN tbl_loja_b2b_fornecedor ON tbl_loja_b2b_peca.loja_b2b_fornecedor = tbl_loja_b2b_fornecedor.loja_b2b_fornecedor
                 WHERE tbl_loja_b2b_peca.loja_b2b_fornecedor IS NOT NULL
                 AND tbl_loja_b2b_carrinho.loja_b2b_carrinho = {$loja_b2b_carrinho}
                 AND tbl_loja_b2b_carrinho.loja_b2b = {$this->_loja}
                 AND tbl_loja_b2b_carrinho_item.ativo IS TRUE
                 LIMIT 1";
        $res = pg_query($this->_con, $sql);

        if (pg_last_error($this->_con)) {
            return array("erro" => true, "msg" =>  traduz(["erro.ao.buscar", "fornecedor"]));
        } else {
            return pg_fetch_array($res);
        }
    }

    public function finalizaCarrinhoFornecedor($dados_carrinho) {
       
        $sql = "UPDATE tbl_loja_b2b_carrinho SET gera_pedido = 'f',aberto = 'f' WHERE loja_b2b_carrinho = ".$dados_carrinho['loja_b2b_carrinho']." AND tbl_loja_b2b_carrinho.loja_b2b = {$this->_loja}";
        $res = pg_query($this->_con, $sql);

        if (pg_last_error($this->_con)) {
            return array("erro" => true, "msg" =>  traduz(["erro.ao.finalizar", "carrinho"]));
        } else {
            return array("sucesso" => true, "msg" =>  traduz("pedido.finalizado.com.sucesso"));
        }
    }

    public function retornaFornecedorEmail($loja_b2b_carrinho) {
       
        $sql = "SELECT DISTINCT tbl_loja_b2b_fornecedor.email
                  FROM tbl_loja_b2b_carrinho
                  JOIN tbl_loja_b2b_carrinho_item ON tbl_loja_b2b_carrinho_item.loja_b2b_carrinho = tbl_loja_b2b_carrinho.loja_b2b_carrinho
                  JOIN tbl_loja_b2b_peca ON tbl_loja_b2b_carrinho_item.loja_b2b_peca = tbl_loja_b2b_peca.loja_b2b_peca
                  JOIN tbl_loja_b2b_fornecedor ON tbl_loja_b2b_peca.loja_b2b_fornecedor = tbl_loja_b2b_fornecedor.loja_b2b_fornecedor
                 WHERE tbl_loja_b2b_peca.loja_b2b_fornecedor IS NOT NULL
                 AND tbl_loja_b2b_carrinho.loja_b2b_carrinho = {$loja_b2b_carrinho}
                 AND tbl_loja_b2b_carrinho.loja_b2b = {$this->_loja}
                 AND tbl_loja_b2b_carrinho_item.ativo IS TRUE";
        $res = pg_query($this->_con, $sql);

        if (pg_last_error($this->_con)) {
            return array("erro" => true, "msg" =>  traduz(["erro.ao.buscar", "fornecedor"]));
        } else {
            return pg_fetch_result($res, 0, 'email');
        }
    }

    public function gravaFreteCarrinho($loja_b2b_carrinho, $forma_envio, $total_frete) {
        $sql = "UPDATE tbl_loja_b2b_carrinho 
                SET forma_envio = '$forma_envio', total_frete = $total_frete
                WHERE loja_b2b_carrinho = $loja_b2b_carrinho";

        $res = pg_query($this->_con, $sql);

        if (pg_last_error($this->_con)) {
            return false;
        } else {
            return true;
        }
    }

    public function retornaPostoCarrinho($loja_b2b_carrinho) {
        $sql = "SELECT DISTINCT tbl_loja_b2b_cliente.posto
                  FROM tbl_loja_b2b_carrinho
                  JOIN tbl_loja_b2b_cliente 
                  ON tbl_loja_b2b_carrinho.loja_b2b_cliente = tbl_loja_b2b_cliente.loja_b2b_cliente
                 WHERE tbl_loja_b2b_carrinho.loja_b2b_carrinho = {$loja_b2b_carrinho}";

        $res = pg_query($this->_con, $sql);

        if (pg_last_error($this->_con)) {
            return false;
        } else {
            return pg_fetch_result($res, 0, 'posto');
        }
    }

    public function verificaValoresItensCarrinho($itens){

      foreach($itens as $key => $item){

        $peca = $item['loja_b2b_peca'];

        $sql = "SELECT tbl_loja_b2b_peca.preco 
                FROM tbl_loja_b2b_peca 
                WHERE loja_b2b_peca = $peca AND
                      loja_b2b = $this->_loja";

        $res = pg_query($this->_con, $sql);

        if(pg_num_rows($res) > 0){

          $preco = pg_fetch_result($res, 0, 'preco');

          if(!empty($preco) && $preco != $item['valor_unitario']){
            $itens[$key]['valor_unitario'] = $preco;
          }
        }
      }

      return $itens;
    }

    public function removeItensExpiradosCarrinho($loja_b2b_carrinho){

     // Alterar para day antes de efetivar

      $sql = "UPDATE tbl_loja_b2b_carrinho_item SET ativo='f' 
              WHERE tbl_loja_b2b_carrinho_item.loja_b2b_carrinho = {$loja_b2b_carrinho}
              AND tbl_loja_b2b_carrinho_item.ativo = 't'
              AND (SELECT tbl_loja_b2b_carrinho.loja_b2b 
                   FROM tbl_loja_b2b_carrinho
                   WHERE tbl_loja_b2b_carrinho.loja_b2b = {$this->_loja} AND
                         tbl_loja_b2b_carrinho.loja_b2b_carrinho = {$loja_b2b_carrinho}
              ) IS NOT NULL
              AND (DATE_PART('day', (current_date + current_time)::timestamp - tbl_loja_b2b_carrinho_item.data_input::timestamp)) >= 2";
             
      $res = pg_query($this->_con, $sql);

      if(pg_last_error($this->_con)){
        return ['erro' => true, 'removidos' => false, 'msg' => pg_last_error($this->_con)];
      }else{

        if(pg_affected_rows($res)){
          return ['erro' => false, 'removidos' => true, 'msg' => 'Registros removidos com sucesso'];
        }else{
           return ['erro' => false, 'removidos' => false, 'msg' => 'Nenhum item encontrado para ser removido'];
        }
      }
    }
}
