<?php
namespace Lojavirtual;
use Lojavirtual\Controller;

class Posto extends Controller {
    protected $tDocs;
    public function __construct() {
        parent::__construct();
    }


    public function get($posto) {

        if (strlen($this->_loja) == 0) {
            return array("erro" => true, "msn" => traduz("loja.nao.encontrada"));
        }

        if (strlen($posto) == 0) {
            return array("erro" => true, "msn" => traduz("posto.nao.encontrado"));
        }

        $sql = "SELECT  tbl_posto.posto,
                        tbl_posto.nome,
                        tbl_posto.cnpj,
                        tbl_posto.pais,
                        tbl_posto_fabrica.pedido_em_garantia,
                        tbl_posto_fabrica.tipo_posto,
                        tbl_posto_fabrica.distribuidor,
                        tbl_posto_fabrica.reembolso_peca_estoque,
                        tbl_posto_fabrica.codigo_posto,
                        tbl_posto_fabrica.contato_endereco,
                        tbl_posto_fabrica.contato_numero,
                        tbl_posto_fabrica.contato_complemento,
                        tbl_posto_fabrica.contato_bairro,
                        tbl_posto_fabrica.contato_cep,
                        tbl_posto_fabrica.contato_cidade,
                        tbl_posto_fabrica.contato_estado,
                        tbl_posto_fabrica.categoria,
                        tbl_tipo_posto.distribuidor AS e_distribuidor,
                        tbl_posto_fabrica.pedido_via_distribuidor,
                        tbl_posto_fabrica.credenciamento,
                        tbl_posto_fabrica.coleta_peca,
                        tbl_posto_fabrica.digita_os,
                        tbl_posto_fabrica.contato_email,
                        tbl_posto_fabrica.parametros_adicionais,
                        tbl_posto_fabrica.contato_fone_comercial
                FROM    tbl_posto
                JOIN    tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $this->_fabrica
                JOIN    tbl_tipo_posto ON tbl_posto_fabrica.tipo_posto = tbl_tipo_posto.tipo_posto
                WHERE   tbl_posto_fabrica.fabrica = $this->_fabrica
                AND     tbl_posto_fabrica.posto   = $posto";
        $res = pg_query($this->_con, $sql);

        if (pg_last_error($this->_con)) {
            return array("erro" => true, "msn" => traduz("posto.nao.encontrado"));
        }

        return pg_fetch_object($res);
    }

}