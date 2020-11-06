<?php
namespace GestaoContrato;

class TabelaPreco extends Controller {

    public function __construct($login_fabrica, $con) {
        parent::__construct($login_fabrica, $con);
    }

    public function get($contrato_tabela = null,$codigo = null) {

        $cond    = "";

        if (strlen($contrato_tabela) > 0) {
            $cond = " AND contrato_tabela = {$contrato_tabela}";
        }

        if (strlen($codigo) > 0) {
            $cond .= " AND codigo = '{$codigo}'";
            $ref   = " - Código da Tabela: {$codigo}";
        }

        $sql = "SELECT * FROM tbl_contrato_tabela WHERE fabrica={$this->_fabrica} $cond";
        $res = pg_query($this->_con, $sql);
        if (pg_last_error($this->_con)) {

            return array("erro" => true, "msn" => traduz("Nenhum registro foi encontrado {$ref}"));
        }
        if (strlen($contrato_tabela) > 0 || strlen($codigo) > 0) {
            return pg_fetch_assoc($res);
        }

        return pg_fetch_all($res);

    }

    public function getProduto($referencia) {

        $sql = "SELECT * FROM tbl_produto WHERE fabrica_i={$this->_fabrica} AND referencia = '{$referencia}'";
        $res = pg_query($this->_con, $sql);

        if (pg_last_error($this->_con)) {
            return array("erro" => true, "msn" => traduz("Produto não encontrado - Referencia: ".$referencia));
        }

        return pg_fetch_assoc($res);

    }

    public function add($dados = []) {

        $existe = false;

        if (empty($dados)) {
            return array("erro" => true, "msn" => traduz("Dados não enviado"));
        }

        $existe = $this->checaDuplicidade($dados["codigo"],$dados["descricao"]);
        if ($existe) {
            return array("erro" => true, "msn" => traduz("Já existe uma Tabela de Contrato com esse Código e Descrição"));
        }
      
        $sql = "INSERT INTO tbl_contrato_tabela (
                                        fabrica,
                                        codigo, 
                                        descricao, 
                                        ativo
                                    ) VALUES (
                                        ".$this->_fabrica.",
                                        '".$dados["codigo"]."',
                                        '".$dados["descricao"]."',
                                        '".$dados["ativo"]."'
                                    )";
        $res = pg_query($this->_con, $sql);
        if (pg_last_error()) {
            return array("erro" => true, "msn" => traduz("Erro ao gravar"));
        }
        return array("sucesso" => true);
    }

    public function addItem($dados = []) {

        $existe = false;

        if (empty($dados)) {
            return array("erro" => true, "msn" => traduz("Dados não enviado"));
        }
        $sqlCH = "SELECT contrato_tabela_item FROM tbl_contrato_tabela_item WHERE contrato_tabela=".$dados["contrato_tabela"]." AND produto=".$dados["produto"];
        $resCH = pg_query($this->_con, $sqlCH);

        if (pg_num_rows($resCH) == 0) {

            $sql = "INSERT INTO tbl_contrato_tabela_item (
                                            contrato_tabela,
                                            produto, 
                                            preco
                                        ) VALUES (
                                            ".$dados["contrato_tabela"].",
                                            ".$dados["produto"].",
                                            '".$dados["preco"]."'
                                        )";
        } else {
            $contrato_tabela_item = pg_fetch_result($resCH, 0, "contrato_tabela_item");
            $sql = "UPDATE tbl_contrato_tabela_item SET preco = '".$dados["preco"]."' WHERE contrato_tabela_item=".$contrato_tabela_item;
        }      
        $res = pg_query($this->_con, $sql);


        if (pg_last_error()) {
            return array("erro" => true, "msn" => traduz("Erro ao gravar prod: ".$dados["produto"]));
        }
        return array("sucesso" => true);
    }

    public function edit($contrato_tabela, $dados = []) {

        if (empty($contrato_tabela) || empty($dados)) {
            return array("erro" => true, "msn" => traduz("Dados não enviado"));
        }

        $sql = "UPDATE tbl_contrato_tabela 
                   SET codigo='".$dados["codigo"]."',
                       descricao='".$dados["descricao"]."',
                       ativo='".$dados["ativo"]."'
                 WHERE fabrica={$this->_fabrica} 
                   AND contrato_tabela={$contrato_tabela}";
        $res = pg_query($this->_con, $sql);

        if (pg_last_error($this->_con)) {
            return array("erro" => true, "msn" => traduz("Erro ao gravar prod: ".$dados["produto"]));
        }

        return array("sucesso" => true);
    }

    public function checaDuplicidade($codigo, $descricao, $id =null) {

        if (!empty($id)) {
            $cond = " AND contrato_tabela <> {$id}";
        }

        $sql = "SELECT contrato_tabela 
                  FROM tbl_contrato_tabela 
                 WHERE fabrica={$this->_fabrica} 
                   AND codigo='{$codigo}' 
                   AND descricao='{$descricao}'";
        $res = pg_query($this->_con, $sql);

        return (pg_num_rows($res) > 0) ? true : false;

    }

}
