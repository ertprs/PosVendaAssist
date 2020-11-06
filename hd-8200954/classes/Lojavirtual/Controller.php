<?php
namespace Lojavirtual;
//require_once __DIR__ . DIRECTORY_SEPARATOR . "../../class/AuditorLog.php";

class Controller {

    protected $_fabrica;
    protected $_con;
    public    $_loja;
    public    $_login_admin;
    public    $_loja_cliente;
    public    $_posto;
    public    $_usacheckout;
    public    $_pedidominimo;
    public    $_nomeFabrica;
    public    $_controlaEstoque;
    public    $_loja_config;
    public    $_fornecedorSelecionado = 0;

    public function __construct($posto = null, $loja_cliente = null) {
        global $login_fabrica, $login_admin, $con;

        $this->_con           = $con;
        $this->_fabrica       = $login_fabrica;
        $this->_login_admin   = $login_admin;
        $this->_posto         = $posto;
        $this->_loja_cliente  = $loja_cliente;

        $dadosLoja = $this->verificaLoja();

        if (!empty($dadosLoja)) {
            $this->_loja            = $dadosLoja["loja_b2b"];
            $this->_nomeFabrica     = $dadosLoja["nome"];
            $this->_usacheckout     = $this->getUsaCheckout($dadosLoja["checkout"]);
            $this->_pedidominimo    = $this->getCondicaoPedidoMinimo($dadosLoja["parametros_adicionais"]);
            $this->_controlaEstoque = $this->getControlaEstoque($dadosLoja["parametros_adicionais"]);
            $this->_loja_config     = $this->getConfiguracoes();
        }

    }


    /*
    *   Verifica se a fábrica já tem uma loja criada
    */
    public function verificaLoja() {

        if (strlen($this->_fabrica) == 0) {
            return false;
        }

        $sql = "SELECT tbl_loja_b2b.loja_b2b,
                       tbl_loja_b2b.checkout,
                       tbl_loja_b2b.parametros_adicionais,
                       tbl_fabrica.nome
                  FROM tbl_loja_b2b 
                  JOIN tbl_fabrica USING(fabrica) 
                 WHERE tbl_loja_b2b.fabrica={$this->_fabrica} 
                   AND tbl_loja_b2b.ativo IS TRUE";
        $res = pg_query($this->_con, $sql);
        if (pg_num_rows($res) > 0) {
            return pg_fetch_array($res);
        }
        return false;
    }

    public function getConfiguracoes() {

        if (strlen($this->_loja) == 0) {
            return array();
        }

        $sql = "SELECT *
                  FROM tbl_loja_b2b_configuracao 
                 WHERE loja_b2b = {$this->_loja}";
        $res = pg_query($this->_con, $sql);
        if (pg_num_rows($res) > 0) {

            $dados = pg_fetch_assoc($res);


            $dados["forma_pagamento"]    = json_decode($dados["pa_forma_pagamento"], 1);
            $dados["forma_envio"]        = json_decode($dados["pa_forma_envio"], 1);
            if (isset($dados["forma_pagamento"]["meio"])) {

                foreach ($dados["forma_pagamento"]["meio"]  as $forma => $rows) {
                    if ($rows["status"] <> "1") {
                        unset($dados["forma_pagamento"]["meio"][$forma]);
                    }
                }
            }
            if (isset($dados["forma_envio"]["meio"])) {
               foreach ($dados["forma_envio"]["meio"]  as $forma => $rows) {
                    if ($rows["status"] <> "1") {
                        unset($dados["forma_envio"]["meio"][$forma]);
                    }
                }
            }

            unset($dados["pa_forma_pagamento"]);
            unset($dados["pa_forma_envio"]);

            return $dados;
        }
        return array();
    }



    public function getUsaCheckout($checkout) {

        if (strlen($this->_loja) == 0) {
            return false;
        }

        if ($checkout == 't') {
            return "S";
        } else {
            return "N";
        }
    }

    public function regrasCondicao($total_pedido, $posto, $estado_posto) {
        //Regras de condição de pagamento, atualmente esta manual
        if ($this->_fabrica == 3) {$condicao = 27;return $condicao;}
        
        if ($this->_fabrica == 198) { $condicao = 4051; return $condicao; }

        if ($this->_fabrica == 15) {

            $estados_ex = array('AM','AP','RO','RR','TO','PA','AC','MA','PI','AL','RN','PB','PE','SE','BA','CE','MT','MS');

            $postos_blacklist = array(73212);

            if (in_array($posto, $postos_blacklist)) {
                $condicao = 3586;
            } else {
                if (in_array($estado_posto, $estados_ex)) {

                    if ($total_pedido >= 100 && $total_pedido <= 500) {
                        $condicao = 3583;
                    } elseif ($total_pedido > 500 && $total_pedido <= 1000) {
                        $condicao = 3584;
                    } elseif ($total_pedido > 1000) {
                        $condicao = 3585;
                    }

                } else {

                    if ($total_pedido >= 100 && $total_pedido <= 500) {
                        $condicao = 74;
                    } elseif ($total_pedido > 500 && $total_pedido <= 1000) {
                        $condicao = 98;
                    } elseif ($total_pedido > 1000) {
                        $condicao = 99;
                    }

                }
                
            }

            return $condicao;

        }

        if ($this->_fabrica == 91) {

            if ($total_pedido >= 50 && $total_pedido <= 100) {
                $condicao = 1469;
            } elseif ($total_pedido > 100 && $total_pedido <= 200) {
                $condicao = 1470;
            } elseif ($total_pedido > 200 && $total_pedido <= 300) {
                $condicao = 1473;
            } elseif ($total_pedido > 300 && $total_pedido < 500) {
                $condicao = 1471;
            } elseif ($total_pedido >= 500) {
                $condicao = 1474;
            }
             return $condicao;
        }

        if ($this->_fabrica == 42) {

            if ($total_pedido >= 50 && $total_pedido <= 300) {
                $condicao = 1383;
            } elseif ($total_pedido > 300 && $total_pedido <= 500) {
                $condicao = 1352;
            } elseif ($total_pedido > 500) {
                $condicao = 1382;
            }

            return $condicao;
            
        }


    }

    public function getCondicaoPedidoMinimo($parametros_adicionais) {

        if (strlen($this->_loja) == 0 || empty($parametros_adicionais)) {
            return false;
        }
        $dados = json_decode($parametros_adicionais,1);

        if (isset($dados["valor_pedido_minimo"]) && $dados["valor_pedido_minimo"] > 0) {
            return $dados["valor_pedido_minimo"];
        } else {
            return 0;
        }
    }

    public function getControlaEstoque($parametros_adicionais) {

        if (strlen($this->_loja) == 0 || empty($parametros_adicionais)) {
            return false;
        }
        $dados = json_decode($parametros_adicionais,1);

        if (isset($dados["controla_estoque"])) {
            return $dados["controla_estoque"];
        } 
    }

}