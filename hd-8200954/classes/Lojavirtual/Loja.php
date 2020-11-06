<?php
namespace Lojavirtual;
use Lojavirtual\Controller;

class Loja extends Controller {
    public $loja;
    public $usacheckout;
    public $configuracao_pagamento;
    public $configuracao_envio;

    public function __construct() {
        parent::__construct();
        $this->loja          = $this->_loja;
        $this->usacheckout   = $this->_usacheckout;
 
        $configLoja          = $this->getConfigLoja();
        $configLojaFrete     = json_decode($configLoja["pa_forma_envio"], 1);
        $configLojaPagamento = json_decode($configLoja["pa_forma_pagamento"], 1);

        $this->configuracao_pagamento = $configLojaPagamento;
        $this->configuracao_envio     = $configLojaFrete;
    }

    /*
    *   Retorna uma loja cadastradas
    */
    public function get($loja_b2b = null) {

        $cond = "";
        if (strlen($this->_loja) == 0) {
            return array("erro" => true, "msn" => traduz("loja.nao.encontrada"));
        }
        $dadosFabrica = array();
        $sql = "SELECT * FROM tbl_loja_b2b WHERE fabrica = {$this->_fabrica}";
        $res = pg_query($this->_con, $sql);

        if (pg_last_error($this->_con)) {
            return array("erro" => true, "msn" => traduz(["erro.ao.buscar", "loja"]));
        }

        $dados = pg_fetch_assoc($res);
        $dadosFabrica = $this->getFabrica($dados["fabrica"]);
        $dados["fabrica_nome"] = $dadosFabrica["nome"];

        return $dados;
    }

    /*
    *   Retorna uma ou todas as lojas cadastradas
    */
    public function getByLoja($loja_b2b = 0) {
        $retorno      = array();
        $dadosFabrica = array();
        $cond    = "";

        if ($loja_b2b > 0) {
            $cond = " AND loja_b2b = {$loja_b2b}";
        }

        $sql = "SELECT * FROM tbl_loja_b2b WHERE 1=1 $cond";
        $res = pg_query($this->_con, $sql);

        if (pg_last_error($this->_con)) {
            return array("erro" => true, "msn" => traduz(["erro.ao.buscar", "loja"]));
        }
        if ($loja_b2b > 0) {
            $dados = pg_fetch_assoc($res);
            $dadosFabrica = $this->getFabrica($dados["fabrica"]);
            $dados["fabrica_nome"] = $dadosFabrica["nome"];
            return $dados;
        }
        $dados = pg_fetch_all($res);
        foreach ($dados as $key => $rows) {
            $dadosFabrica = $this->getFabrica($rows["fabrica"]);
            $dados[$key]["fabrica_nome"] = $dadosFabrica["nome"];
        }
        return $dados;
    }


    /*
    *   Retorna o layout da loja
    */
    public function getLayout($loja_b2b) {

        if (strlen($loja_b2b) == 0) {
            return array("erro" => true, "msn" => traduz("loja.nao.encontrada"));
        }

        $sql = "SELECT layout FROM tbl_loja_b2b WHERE loja_b2b={$loja_b2b} AND  fabrica={$this->_fabrica}";
        $res = pg_query($this->_con, $sql);

        if (pg_last_error($this->_con)) {
            return array("erro" => true, "msn" => traduz(["erro.ao.buscar", "loja"]));
        }

        $layout = pg_fetch_result($res, 0, "layout");
        return json_decode($layout, 1);
    }

    /*
    *   Cria a loja na telecontrol
    */
    public function criaLoja($dados = array()) {
        $existe = false;
        if (empty($dados)) {
            return array("erro" => true, "msn" => traduz("dados.não.enviado"));
        }

        $existe = $this->verificaLojaExistente($dados["fabrica"]);
        if ($existe) {
            return array("erro" => true, "msn" => traduz("já.existe.uma.loja.criada.para.esta.fábrica"));
        }

        $layout = "";
        if (file_exists(__DIR__ . '/Layout/layout_padrao.json')) {
            $layout = file_get_contents(__DIR__. "/Layout/layout_padrao.json");
        }


        $checkout = ($dados["checkout"] == 't') ? 't' : 'f'; 
        $ativo    = ($dados["ativo"]    == 't') ? 't' : 'f'; 
        $externa  = ($dados["externa"]  == 't') ? 't' : 'f'; 
        $sql = "INSERT INTO tbl_loja_b2b (
                                        fabrica,
                                        checkout, 
                                        ativo, 
                                        externa, 
                                        layout
                                    ) VALUES (
                                        ".$dados["fabrica"].",
                                        '".$checkout."',
                                        '".$ativo."',
                                        '".$externa."',
                                        '".$layout."'
                                    )";
        $res = pg_query($this->_con, $sql);
        if (pg_last_error($this->_con)) {
            return array("erro" => true, "msn" => traduz(["erro.ao.cadastrar", "loja"]));
        }

        return array("sucesso" => true);
    }


    /*
    *   Atualiza layout da loja
    */
    public function atualizaLayout($layout) {

        if (empty($layout) || !$this->_loja) {
            return array("erro" => true, "msn" => traduz("dados.não.enviado"));
        }

        $sql = "UPDATE tbl_loja_b2b SET layout='$layout' WHERE fabrica={$this->_fabrica} AND loja_b2b={$this->_loja}";
        $res = pg_query($this->_con, $sql);

        if (pg_last_error($this->_con)) {
            return array("erro" => true, "msn" => traduz(["erro.ao.atualizar", "loja"]));
        }

        return array("sucesso" => true);
    }

    /*
    *   Atualiza paramentros adicionais da loja
    */
    public function atualizaParamentrosAdicionais($dados) {

        if (empty($dados) || !$this->_loja) {
            return array("erro" => true, "msn" => traduz("dados.não.enviado"));
        }

        $sql = "SELECT parametros_adicionais 
                  FROM tbl_loja_b2b 
                 WHERE fabrica={$this->_fabrica} 
                   AND loja_b2b={$this->_loja}";
        $res = pg_query($this->_con, $sql);
        $paramentros = json_decode(pg_fetch_result($res, 0, parametros_adicionais), 1);

        $paramentros["valor_pedido_minimo"] = $dados["valor_pedido_minimo"];
        
        $paramentros["controla_estoque"] = $dados["controla_estoque"];
        $novoParametro  = json_encode($paramentros);

        $sql = "UPDATE tbl_loja_b2b 
                   SET parametros_adicionais='$novoParametro' 
                 WHERE fabrica={$this->_fabrica} 
                   AND loja_b2b={$this->_loja}";
        $res = pg_query($this->_con, $sql);

        if (pg_last_error($this->_con)) {
            return array("erro" => true, "msn" => traduz(["erro.ao.atualizar", "loja"]));
        }

        return array("sucesso" => true);
    }

    /*
    *   Verifica se a fábrica já tem uma loja criada
    */
    public function verificaLojaExistente($fabrica) {

        if (strlen($fabrica) == 0) {
            return false;
        }

        $sql = "SELECT loja_b2b FROM tbl_loja_b2b WHERE fabrica={$fabrica}";
        $res = pg_query($this->_con, $sql);

        return (pg_num_rows($res) > 0) ? true : false;
    }

    /*
    *   Retorna fabrica
    */
    public function getFabrica($fabrica) {

        if (strlen($fabrica) == 0) {
            return array("erro" => true, "msn" => traduz("fábrica.não.encontrada"));
        }

        $sql = "SELECT *
                  FROM tbl_fabrica
                 WHERE ativo_fabrica  
                   AND fabrica={$fabrica}
              ORDER BY nome";
        $res = pg_query($this->_con, $sql);

        if (pg_last_error($this->_con)) {
            return array("erro" => true, "msn" => traduz(["erro.ao.buscar", "loja"]));
        }
        return pg_fetch_assoc($res);
    }


    /*
    *   Retorna confgurações da loja
    */
    public function getConfigLoja($loja_b2b = null) {

        if (strlen($this->_loja) == 0) {
            return array("erro" => true, "msn" => traduz("loja.nao.encontrada"));
        }
        $cond = "loja_b2b = {$this->_loja}";

        if (strlen($loja_b2b) > 0) {
            $cond = "loja_b2b = {$loja_b2b}";
        }

        $sql = "SELECT * 
                  FROM tbl_loja_b2b_configuracao 
                 WHERE $cond";
        $res = pg_query($this->_con, $sql);
        if (pg_last_error($this->_con)) {
            return array("erro" => true, "msn" => traduz(["erro.ao.buscar", "loja"]));
        }

        return pg_fetch_assoc($res);
    }



    /*
    *   Retorna confgurações da loja
    */
    public function getAllConfigLoja() {

        $sql = "SELECT * 
                  FROM tbl_loja_b2b_configuracao ";
        $res = pg_query($this->_con, $sql);

        if (pg_last_error($this->_con)) {
            return array("erro" => true, "msn" => traduz(["erro.ao.buscar", "loja"]));
        }

        return pg_fetch_all($res);
    }



    /*
    *   Retorna confgurações da loja
    */
    public function gravaConfigPagamento($dados) {
        $dadosSave = array();

        if (empty($dados)) {
            return array("erro" => true, "msn" => traduz("dados.nao.enviado"));
        }

        $verificaConfig  = $this->getConfigLoja($dados["loja_escolhida"]);
        $dadosSave       = json_encode($this->trataConfigPagamento($dados));

        if (empty($verificaConfig)) {

            $sql = "INSERT INTO tbl_loja_b2b_configuracao (
                                                            forma_envio, 
                                                            forma_pagamento, 
                                                            loja_b2b, 
                                                            pa_forma_pagamento
                                                          ) VALUES 
                                                          (
                                                            0,
                                                            0,
                                                            ".$dados["loja_escolhida"].",
                                                            '{$dadosSave}'
                                                          )";
            $res = pg_query($this->_con, $sql);

            if (pg_last_error($this->_con)) {
                return array("erro" => true, "msn" => traduz(["erro.ao.cadastrar", "loja"]));
            }
            return array("erro" => false, "msn" => "Forma de pagamento configurada com sucesso");

        } else {

            $sql = "UPDATE tbl_loja_b2b_configuracao  
                        SET pa_forma_pagamento = '{$dadosSave}'
                      WHERE loja_b2b_configuracao=".$verificaConfig["loja_b2b_configuracao"];
            $res = pg_query($this->_con, $sql);
            if (pg_last_error($this->_con)) {
                return array("erro" => true, "msn" => traduz(["erro.ao.atualizar", "loja"]));
            }
            return array("erro" => false, "msn" => "Forma de pagamento configurada com sucesso");

        }
    }

    public function trataConfigPagamento($dados) {
        $retorno = array();
        if (strlen($this->_loja) == 0) {
            return array("erro" => true, "msn" => traduz("loja.nao.encontrada"));
        }

        if (empty($dados)) {
            return array("erro" => true, "msn" => traduz("dados.nao.enviado"));
        }

        foreach ($dados["pagseguro"]["status_pagamento"] as $key => $statusps) {
            if (strlen(trim($dados["pagseguro"]["status_pagamento"][$key])) == 0) {
                unset($dados["pagseguro"]["status_pagamento"][$key]);
                continue;
            }
            $dados["pagseguro"]["status_pagamento"][$key] = utf8_encode($statusps);
        }

        unset($dados["xloja_b2b"]);
        unset($dados["loja_escolhida"]);
        unset($dados["forma_escolhida"]);
        unset($dados["btn_acao"]);
        unset($dados["token_form"]);
        $dados["cielo"]["instrucao_boleto"] = utf8_encode($dados["cielo"]["instrucao_boleto"]);
        $dados["maxipago"]["instrucao_boleto"] = utf8_encode($dados["maxipago"]["instrucao_boleto"]);

        $retorno["meio"] = $dados;
        return $retorno;
    }


    public function gravaConfigEnvio($dados) {
        $dadosSave = array();

        if (empty($dados)) {
            return array("erro" => true, "msn" => traduz("dados.nao.enviado"));
        }

        $verificaConfig  = $this->getConfigLoja($dados["loja_escolhida"]);
        $dadosSave       = json_encode($this->trataConfigEnvio($dados));

        if (empty($verificaConfig)) {

            $sql = "INSERT INTO tbl_loja_b2b_configuracao (
                                                            forma_envio, 
                                                            forma_pagamento, 
                                                            loja_b2b, 
                                                            pa_forma_envio
                                                          ) VALUES 
                                                          (
                                                            0,
                                                            0,
                                                            ".$dados["loja_escolhida"].",
                                                            '{$dadosSave}'
                                                          )";
            $res = pg_query($this->_con, $sql);

            if (pg_last_error($this->_con)) {
                return array("erro" => true, "msn" => traduz(["erro.ao.cadastrar", "loja"]));
            }
            return array("erro" => false, "msn" => "Forma de envio configurada com sucesso");

        } else {

            $sql = "UPDATE tbl_loja_b2b_configuracao  
                        SET pa_forma_envio = '{$dadosSave}'
                      WHERE loja_b2b_configuracao=".$verificaConfig["loja_b2b_configuracao"];
            $res = pg_query($this->_con, $sql);
            if (pg_last_error($this->_con)) {
                return array("erro" => true, "msn" => traduz(["erro.ao.atualizar", "loja"]));
            }
            return array("erro" => false, "msn" => "Forma de envio configurada com sucesso");

        }
    }


    public function trataConfigEnvio($dados) {

        $retorno = array();
        if (strlen($this->_loja) == 0) {
            return array("erro" => true, "msn" => traduz("loja.nao.encontrada"));
        }

        if (empty($dados)) {
            return array("erro" => true, "msn" => traduz("dados.nao.enviado"));
        }

        unset($dados["xloja_b2b"]);
        unset($dados["loja_escolhida"]);
        unset($dados["forma_escolhida"]);
        unset($dados["btn_acao"]);
        unset($dados["token_form"]);

        $retorno["meio"] = $dados;

        return $retorno;
    }


}