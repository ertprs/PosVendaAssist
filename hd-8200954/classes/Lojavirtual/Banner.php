<?php
namespace Lojavirtual;
use Lojavirtual\Controller;
require_once __DIR__ . DIRECTORY_SEPARATOR . "../../class/tdocs.class.php";

class Banner extends Controller {
    protected $tDocs;
    public function __construct() {
        parent::__construct();
        $this->tDocs = new \TDocs($this->_con, $this->_fabrica);
    }

    /*
    *   Retorna um banner cadastrado
    */
    public function get($loja_b2b_banner = 0, $status = false) {

        $cond = "";
        if (strlen($this->_loja) == 0) {
            return array("erro" => true, "msn" => traduz("loja.nao.encontrada"));
        }

        if ($loja_b2b_banner > 0) {
            $cond .= " AND tbl_loja_b2b_banner.loja_b2b_banner={$loja_b2b_banner}";
        }
        if ($status) {
            $cond .= " AND tbl_loja_b2b_banner.ativo IS TRUE";
        }

        $sql = "SELECT 
                          tbl_loja_b2b_banner.loja_b2b_banner,
                          tbl_loja_b2b_banner.loja_b2b,
                          tbl_loja_b2b_banner.categoria,
                          tbl_loja_b2b_banner.link,
                          tbl_loja_b2b_banner.descricao,
                          tbl_loja_b2b_banner.ativo
                     FROM tbl_loja_b2b_banner
                    WHERE tbl_loja_b2b_banner.loja_b2b = {$this->_loja} 
                          $cond";
        $res = pg_query($this->_con, $sql);

        if (pg_last_error($this->_con)) {
            return array("erro" => true, "msn" => traduz(["erro.ao.buscar", "banner"]));
        }

        if ($loja_b2b_banner > 0) {

            $row =  pg_fetch_assoc($res);
            $fotos = $this->tDocs->getDocumentsByRef($row["loja_b2b_banner"],"loja","banner")->attachListInfo;
            if (empty($fotos)) {
                $row["fotos"] = "";
            } else {
               foreach ($fotos as $k => $vFotos) {
                    $row["fotos"] = $vFotos["link"];
                }
            }
            return $row;
        }

        $rowB = pg_fetch_all($res);
        foreach ($rowB as $k => $v) {
            $imagem = $this->tDocs->getDocumentsByRef($v["loja_b2b_banner"],"loja","banner")->attachListInfo;
            if (empty($imagem)) {
                $rowB[$k]["imagem"] = "";
            } else {
               foreach ($imagem as $i => $vFotos) {
                    $rowB[$k]["imagem"] = $vFotos["link"];
                }
            }
        }
        return $rowB;
    }

    /*
    *   cadastra banner
    */
    public function save($dados = array()) {
        if (empty($dados)) {
            return array("erro" => true, "msn" => traduz("dados.não.enviado"));
        }

        $sql = "INSERT INTO tbl_loja_b2b_banner (
                                        loja_b2b, 
                                        link, 
                                        descricao, 
                                        posicao, 
                                        categoria, 
                                        ativo
                                    ) VALUES (
                                        ".$this->_loja.",
                                        '".$dados["link"]."',
                                        '".$dados["descricao"]."',
                                        'F',
                                        '".$dados["categoria"]."',
                                        '".$dados["ativo"]."'
                                    ) RETURNING loja_b2b_banner;";
        $res = pg_query($this->_con, $sql);

        if (pg_last_error($this->_con)) {
            return array("erro" => true, "msn" =>  traduz(["erro.ao.cadastrar", "banner"]));
        }

        $loja_b2b_banner = pg_fetch_result($res, 0, 0);
        return array("loja_b2b_banner" => $loja_b2b_banner, "sucesso" => true);
    }

    /*
    *   altera banner
    */
    public function update($dados = array()) {
        if (empty($dados)) {
            return array("erro" => true, "msn" => traduz("dados.não.enviado"));
        }

        $sql = "UPDATE tbl_loja_b2b_banner  
                   SET
                      link='".$dados["link"]."', 
                      descricao='".$dados["descricao"]."', 
                      categoria='".$dados["categoria"]."', 
                      ativo='".$dados["ativo"]."'
                WHERE loja_b2b=".$this->_loja." 
                  AND loja_b2b_banner=".$dados["loja_b2b_banner"];
        $res = pg_query($this->_con, $sql);

        if (pg_last_error($this->_con)) {
            return array("erro" => true, "msn" => traduz(["erro.ao.atualizar", "banner"]));
        }

        return array("loja_b2b_banner" => $dados["loja_b2b_banner"], "sucesso" => true);
    }

    /*
    *   Deleta banner
    */
    public function delete($loja_b2b_banner) {
        if (empty($loja_b2b_banner)) {
            return array("erro" => true, "msn" => traduz("dados.não.enviado"));
        }

        $sql = "DELETE FROM tbl_loja_b2b_banner WHERE loja_b2b=".$this->_loja." AND loja_b2b_banner=".$loja_b2b_banner;
        $res = pg_query($this->_con, $sql);

        if (pg_last_error($this->_con)) {
            return array("erro" => true, "msn" => traduz(["erro.ao.remover", "banner"]));
        }

        return array("sucesso" => true);
    }

}