<?php
namespace Lojavirtual;
use Lojavirtual\Controller;
use Lojavirtual\LojaTabelaPreco;
use Lojavirtual\LojaCliente;

require_once __DIR__ . DIRECTORY_SEPARATOR . "../../class/tdocs.class.php";

class Produto extends Controller {
    protected $tDocs;
    private  $tabelaPreco;
    private $tamanhoGrade = ["P","M","G","GG","EXG"];
    public function __construct($posto = null , $loja_cliente =null ) {
        parent::__construct($posto, $loja_cliente);
        $this->tabelaPreco = new LojaTabelaPreco();
        $this->tDocs = new \TDocs($this->_con, $this->_fabrica);
    }
    /*
    *   Retorna todos
    */
    public function getAll($condicoes_busca = array(), $ambriente = 'admin') {

        $cond = "";
        $retorno = array();

        if (strlen($this->_loja) == 0) {
            return array("erro" => true, "msn" => traduz("loja.nao.encontrada"));
        }

        if (count($condicoes_busca) > 0) {

            if (isset($condicoes_busca["categoria"]) && strlen($condicoes_busca["categoria"]) > 0) {
                $cond[] = "tbl_loja_b2b_peca.categoria=".$condicoes_busca["categoria"];
            }

            if (isset($condicoes_busca["kit_peca"]) && $condicoes_busca["kit_peca"]) {
                $cond[] = "tbl_loja_b2b_peca.kit_peca IS TRUE";
            } else {
                $cond[] = "tbl_loja_b2b_peca.kit_peca IS NOT TRUE";
            }

            if (isset($condicoes_busca["descricao_peca"]) && strlen($condicoes_busca["descricao_peca"]) > 0) {
                $cond[] = " (tbl_peca.descricao ILIKE '%".$condicoes_busca["descricao_peca"]."%' OR tbl_peca.referencia = '".$condicoes_busca["descricao_peca"]."')";
            }

            if (isset($condicoes_busca["referencia_peca"]) && strlen($condicoes_busca["referencia_peca"]) > 0) {
                $cond[] = " tbl_peca.referencia='".$condicoes_busca["referencia_peca"]."' ";
            }

            if ( isset($condicoes_busca["preco_inicial"]) && isset($condicoes_busca["preco_final"]) && strlen($condicoes_busca["preco_inicial"]) && strlen($condicoes_busca["preco_final"])) {
                $cond[] = "BETWEEN tbl_loja_b2b_peca.preco '".$condicoes_busca["preco_inicial"]."' AND '".$condicoes_busca["preco_final"]."'";
            } else {

                if (isset($condicoes_busca["preco_inicial"]) && strlen($condicoes_busca["preco_inicial"]) > 0) {
                    $cond[] = "tbl_loja_b2b_peca.preco >= '".$condicoes_busca["preco_inicial"]."'";
                }

                if (isset($condicoes_busca["preco_final"]) && strlen($condicoes_busca["preco_final"]) > 0) {
                    $cond[] = "tbl_loja_b2b_peca.preco <= '".$condicoes_busca["preco_final"]."'";
                }
                
            }

            if (!empty($cond)) {
                $cond = " AND " . implode(" AND ", $cond);
            }
        } else {
        }

        $sql = "SELECT 

                          tbl_peca.peca                     ,
                          tbl_loja_b2b_peca.categoria           AS peca_categoria,
                          tbl_loja_b2b_peca.qtde_estoque        AS qtde_estoque_peca,
                          tbl_loja_b2b_peca.qtde_max_posto      AS qtde_max_posto_peca,
                          tbl_loja_b2b_peca.preco               AS preco_peca,
                          tbl_loja_b2b_peca.data_input AS data,
                          tbl_peca.descricao AS nome_peca,
                          tbl_peca.referencia AS ref_peca,
                          tbl_categoria.descricao AS nome_categoria,
                          tbl_loja_b2b_peca.loja_b2b_peca AS codigo_peca,
                          tbl_loja_b2b_peca.disponivel          AS disponibilidade_peca,
                          tbl_loja_b2b_peca.destaque            AS peca_destaque,
                          tbl_loja_b2b_peca.descricao AS descricao_peca,
                          tbl_loja_b2b_peca.preco,
                          tbl_loja_b2b_peca.kit_peca,
                          tbl_loja_b2b_peca.altura,
                          tbl_loja_b2b_peca.largura,
                          tbl_loja_b2b_peca.comprimento,
                          tbl_loja_b2b_peca.peso,
                          tbl_loja_b2b_peca.preco_promocional AS preco_promocional_peca,
                          tbl_loja_b2b_peca.loja_b2b_fornecedor
                     FROM tbl_loja_b2b_peca
                     JOIN tbl_peca ON tbl_loja_b2b_peca.peca=tbl_peca.peca AND tbl_peca.fabrica={$this->_fabrica}
                     JOIN tbl_categoria ON tbl_categoria.categoria=tbl_loja_b2b_peca.categoria AND tbl_categoria.fabrica={$this->_fabrica}
                    WHERE tbl_loja_b2b_peca.ativo IS TRUE
                      AND tbl_loja_b2b_peca.loja_b2b = {$this->_loja}
                          $cond
                          ";
                          
        $res = pg_query($this->_con, $sql);
        if (pg_last_error($this->_con)) {
            return array("erro" => true, "msn" => traduz("produto.nao.encontrado"));
        }
        $preco = 0;

        foreach (pg_fetch_all($res) as $key => $value) {

            if ($this->_controlaEstoque && $value["qtde_estoque_peca"] <= 0 && $ambriente == 'posto') {
                continue;
            }

            $fotos = $this->tDocs->getDocumentsByRef($value["codigo_peca"],"lojapeca")->attachListInfo;
            if (empty($fotos)) {
                $value["fotos"][] = "loja/layout/img/sem_produto.png";
            } else {
                foreach ($fotos as $k => $vFotos) {
                    $value["fotos"][] = $vFotos["link"];
                }
            }

            if ($value["preco_promocional_peca"] > 0) {
                $preco = $value["preco_promocional_peca"];
            } else {
                $preco = $value["preco_peca"];
            }

            //busca tabela de preco
            if (!empty($this->_loja_cliente)) {

                $tabela_preco = $this->tabelaPreco->getTabelaByCliente($this->_loja_cliente);
                
                if (!empty($tabela_preco)) {
                
                    $tabela_preco_item = $this->tabelaPreco->getItem($value["codigo_peca"], $tabela_preco["loja_b2b_tabela"]);
                    if (!empty($tabela_preco_item)) {
                        $preco = $tabela_preco_item['preco'];
                    }                 
                }
            }

            $value["preco_venda"] = $preco;

            $retorno[] = $value;
        }
        $dadosKit = $this->getKit(null, $condicoes_busca);
        if (count($dadosKit) > 0) {
            $retorno = array_merge($retorno, $dadosKit);
        }
        return $retorno;
    }

    /*
    *   Retorna todos
    */
    public function get($produto = null, $peca = null) {
        $this->tDocs = new \TDocs($this->_con, $this->_fabrica);

        $retorno = array();
        $cond = "";
        $preco = 0;
        if (strlen($this->_loja) == 0) {
            return array("erro" => true, "msn" => traduz("loja.nao.encontrada"));
        }

        if (strlen($peca) > 0) {
            $cond = " AND tbl_loja_b2b_peca.peca = {$peca}";
        }

        if (strlen($produto) > 0) {
            $cond = " AND tbl_loja_b2b_peca.loja_b2b_peca = {$produto}";
        }

        $sql = "SELECT 
                          tbl_loja_b2b_peca.data_input          AS data,
                          tbl_peca.descricao                AS nome_peca,
                          tbl_peca.referencia               AS ref_peca,
                          tbl_peca.peca                     ,
                          tbl_categoria.descricao           AS nome_categoria,
                          tbl_loja_b2b_peca.categoria           AS peca_categoria,
                          tbl_loja_b2b_peca.loja_b2b_peca           AS codigo_peca,
                          tbl_loja_b2b_peca.descricao           AS descricao_peca,
                          tbl_loja_b2b_peca.qtde_estoque        AS qtde_estoque_peca,
                          tbl_loja_b2b_peca.qtde_max_posto      AS qtde_max_posto_peca,
                          tbl_loja_b2b_peca.disponivel          AS disponibilidade_peca,
                          tbl_loja_b2b_peca.destaque            AS peca_destaque,
                          tbl_loja_b2b_peca.preco               AS preco_peca,
                          tbl_loja_b2b_peca.kit_peca ,
                          tbl_loja_b2b_peca.preco_promocional   AS preco_promocional_peca,
                          tbl_loja_b2b_peca.loja_b2b_fornecedor,
                          tbl_loja_b2b_fornecedor.nome as nome_fornecedor,
                          tbl_loja_b2b_peca.altura ,
                          tbl_loja_b2b_peca.largura ,
                          tbl_loja_b2b_peca.comprimento ,
                          tbl_loja_b2b_peca.peso
                     FROM tbl_loja_b2b_peca
                     JOIN tbl_peca ON tbl_loja_b2b_peca.peca=tbl_peca.peca AND tbl_peca.fabrica={$this->_fabrica}
                     LEFT JOIN tbl_categoria ON tbl_categoria.categoria=tbl_loja_b2b_peca.categoria AND tbl_categoria.fabrica={$this->_fabrica}
                     LEFT JOIN tbl_loja_b2b_fornecedor ON tbl_loja_b2b_peca.loja_b2b_fornecedor = tbl_loja_b2b_fornecedor.loja_b2b_fornecedor
                    WHERE tbl_loja_b2b_peca.ativo IS TRUE
                      AND tbl_loja_b2b_peca.loja_b2b = {$this->_loja}
                     $cond
                     ";
        $res = pg_query($this->_con, $sql);
        if (pg_last_error($this->_con)) {
            return array("erro" => true, "msn" => traduz(["erro.ao.buscar", "produto"]));
        }

        if (strlen($produto) > 0) {
            $row =  pg_fetch_assoc($res);
            $fotos = $this->tDocs->getDocumentsByRef($row["codigo_peca"],"lojapeca")->attachListInfo;
            if (empty($fotos)) {
                $row["fotos"][] = "loja/layout/img/sem_produto.png";
            } else {
                foreach ($fotos as $k => $vFotos) {
                    $row["fotos"][] = $vFotos["link"];
                }
            }

            if ($row["preco_promocional_peca"] > 0) {
                $preco = $row["preco_promocional_peca"];
            } else {
                $preco = $row["preco_peca"];
            }

            //busca tabela de preco
            if (!empty($this->_loja_cliente)) {

                $tabela_preco = $this->tabelaPreco->getTabelaByCliente($this->_loja_cliente);
                
                if (!empty($tabela_preco)) {
                
                    $tabela_preco_item = $this->tabelaPreco->getItem($row["codigo_peca"], $tabela_preco["loja_b2b_tabela"]);
                
                    if (!empty($tabela_preco_item)) {
                        $preco = $tabela_preco_item['preco'];
                    }
                }

            } 

            $row["preco_venda"] = $preco;

           /* $dadosKit = $this->getKit();
            if (count($dadosKit) > 0) {
                $row = array_merge($retorno, $dadosKit);
            }*/

            return $row;
        }

        foreach (pg_fetch_all($res) as $key => $value) {
           
            $fotos = $this->tDocs->getDocumentsByRef($value["codigo_peca"], "lojapeca")->attachListInfo;

            if (empty($fotos)) {
                $value["fotos"][] = "loja/layout/img/sem_produto.png";
            } else {
                foreach ($fotos as $k => $vFotos) {
                    $value["fotos"][] = $vFotos["link"];
                }
            }


            //busca tabela de preco
            if (!empty($this->_loja_cliente)) {

                $tabela_preco = $this->tabelaPreco->getTabelaByCliente($this->_loja_cliente);
                
                if (!empty($tabela_preco)) {
                
                    $tabela_preco_item = $this->tabelaPreco->getItem($row["codigo_peca"], $tabela_preco["loja_b2b_tabela"]);
                
                    if (!empty($tabela_preco_item)) {
                        $preco = $tabela_preco_item['preco'];
                    } else {
                        if ($value["preco_promocional_peca"] > 0) {
                            $preco = $value["preco_promocional_peca"];
                        } else {
                            $preco = $value["preco_peca"];
                        }
                    }
                
                }

            } else {
                if ($value["preco_promocional_peca"] > 0) {
                    $preco = $value["preco_promocional_peca"];
                } else {
                    $preco = $value["preco_peca"];
                }
            }


            $value["preco_venda"] = $preco;

            $retorno[] = $value;
        }
        return $retorno;
    }

    /*
    *   Retorna todos
    */
    public function getKit($loja_b2b_kit_peca = null, $dados_busca = array()) {
        $this->tDocs = new \TDocs($this->_con, $this->_fabrica);
        $retorno = array();
        $cond    = "";
        if (strlen($this->_loja) == 0) {
            return array("erro" => true, "msn" => "Loja não encontrada!");
        }
        if (!empty($dados_busca)) {
            if (isset($dados_busca["categoria"]) && strlen($dados_busca["categoria"]) > 0) {
                $cond .= " AND tbl_loja_b2b_kit_peca.categoria = ".$dados_busca["categoria"];
            }
            if(isset($dados_busca['descricao_peca'])) {
                $cond .= " AND (tbl_loja_b2b_kit_peca.nome ILIKE  '%".$dados_busca["descricao_peca"] ."%' OR tbl_loja_b2b_kit_peca.referencia ='".$dados_busca["descricao_peca"] ."')";
            }
        }
        if (isset($dados_busca["referencia_peca"]) && strlen($dados_busca["referencia_peca"]) > 0) {
            $cond .= " AND tbl_loja_b2b_kit_peca.referencia='".$dados_busca["referencia_peca"]."' ";
        }
        if (strlen($loja_b2b_kit_peca) > 0) {
            $cond .= " AND tbl_loja_b2b_kit_peca.loja_b2b_kit_peca = {$loja_b2b_kit_peca}";
        }

        $sql = "SELECT tbl_loja_b2b_kit_peca.loja_b2b_kit_peca,
                       tbl_loja_b2b_kit_peca.loja_b2b,
                       tbl_loja_b2b_kit_peca.referencia AS ref_peca,
                       tbl_loja_b2b_kit_peca.nome AS nome_peca,
                       tbl_loja_b2b_kit_peca.descricao AS descricao_peca,
                       tbl_loja_b2b_kit_peca.disponivel AS disponibilidade_peca,
                       tbl_loja_b2b_kit_peca.destaque AS peca_destaque,
                       tbl_loja_b2b_kit_peca.ativo,
                       tbl_loja_b2b_kit_peca.categoria,
                       tbl_loja_b2b_kit_peca.ativo,
                       tbl_loja_b2b_kit_peca.ativo AS kit_peca ,
                       tbl_categoria.descricao AS nome_categoria
                  FROM tbl_loja_b2b_kit_peca
             LEFT JOIN tbl_categoria ON tbl_categoria.categoria=tbl_loja_b2b_kit_peca.categoria AND tbl_categoria.fabrica={$this->_fabrica}
                 WHERE tbl_loja_b2b_kit_peca.ativo IS TRUE
                   AND tbl_loja_b2b_kit_peca.loja_b2b = {$this->_loja}
                 $cond";
        $res = pg_query($this->_con, $sql);

        if (pg_last_error($this->_con)) {
            return array("erro" => true, "msn" => "Erro ao buscar kit");
        }

        if (strlen($loja_b2b_kit_peca) > 0) {
            $row =  pg_fetch_assoc($res);

            $fotos = $this->tDocs->getDocumentsByRef($row["loja_b2b_kit_peca"],"lojapecakit")->attachListInfo;
            if (empty($fotos)) {
                $row["fotos"][] = "loja/layout/img/sem_produto.png";
            } else {
                foreach ($fotos as $k => $vFotos) {
                    $row["fotos"][] = $vFotos["link"];
                }
            }
            
            $dadosItens = $this->getItensKit($row["loja_b2b_kit_peca"]);
            if ($dadosItens["erro"] == true) {
                return array("erro" => true, "msn" => "Erro ao buscar itens do kit");
            }
            $total_itens = array();
            foreach ($dadosItens as $key => $value) {
              $total_itens[] = ($value["qtde"]*$value["preco_venda"]);
            }
            $row["total_itens_kit"] = array_sum($total_itens);
            $row["itens_kit"] = $dadosItens;

            return $row;
        } else {

            foreach (pg_fetch_all($res) as $key => $value) {
                $fotos = $this->tDocs->getDocumentsByRef($value["loja_b2b_kit_peca"], "lojapecakit")->attachListInfo;

                if (empty($fotos)) {
                    $value["fotos"][] = "loja/layout/img/sem_produto.png";
                } else {
                    foreach ($fotos as $k => $vFotos) {
                        $value["fotos"][] = $vFotos["link"];
                    }
                }

                $dadosItens = $this->getItensKit($value["loja_b2b_kit_peca"]);
                if ($dadosItens["erro"] == true) {
                    return array("erro" => true, "msn" => "Erro ao buscar itens do kit");
                }
                $value["itens_kit"] = $dadosItens;

                $retorno[] = $value;
            }

        }
        
        return $retorno;
    }

    /*
    *   Retorna todos
    */
    public function getPecaLojaByRef($referencia_peca) {
        $this->tDocs = new \TDocs($this->_con, $this->_fabrica);
        
        $retorno = array();
        if (strlen($this->_loja) == 0) {
            return array("erro" => true, "msn" => traduz("loja.nao.encontrada"));
        }

        $sql = "SELECT 
                          tbl_loja_b2b_peca.data_input          AS data,
                          tbl_peca.peca                            ,
                          tbl_peca.descricao                AS nome_peca,
                          tbl_peca.referencia               AS ref_peca,
                          tbl_categoria.descricao           AS nome_categoria,
                          tbl_loja_b2b_peca.categoria           AS peca_categoria,
                          tbl_loja_b2b_peca.loja_b2b_peca           AS codigo_peca,
                          tbl_loja_b2b_peca.descricao           AS descricao_peca,
                          tbl_loja_b2b_peca.qtde_estoque        AS qtde_estoque_peca,
                          tbl_loja_b2b_peca.qtde_max_posto      AS qtde_max_posto_peca,
                          tbl_loja_b2b_peca.disponivel          AS disponibilidade_peca,
                          tbl_loja_b2b_peca.destaque            AS peca_destaque,
                          tbl_loja_b2b_peca.preco               AS preco_peca,
                          tbl_loja_b2b_peca.kit_peca,
                          tbl_loja_b2b_peca.altura,
                          tbl_loja_b2b_peca.largura,
                          tbl_loja_b2b_peca.comprimento,
                          tbl_loja_b2b_peca.peso,
                          tbl_loja_b2b_peca.preco_promocional   AS preco_promocional_peca
                     FROM tbl_loja_b2b_peca
                     JOIN tbl_peca ON tbl_loja_b2b_peca.peca=tbl_peca.peca AND tbl_peca.fabrica={$this->_fabrica}
                LEFT JOIN tbl_categoria ON tbl_categoria.categoria=tbl_loja_b2b_peca.categoria AND tbl_categoria.fabrica={$this->_fabrica}
                    WHERE tbl_loja_b2b_peca.loja_b2b = {$this->_loja}
                      AND tbl_peca.referencia = '{$referencia_peca}'";
        $res = pg_query($this->_con, $sql);

        if (pg_last_error($this->_con)) {
            return array("erro" => true, "msn" => traduz("produto.nao.encontrado"));
        }

        if (pg_num_rows($res) > 0) {
            $row =  pg_fetch_assoc($res);
            $fotos = $this->tDocs->getDocumentsByRef($row["codigo_peca"],"lojapeca")->attachListInfo;
            if (empty($fotos)) {
                $row["fotos"][] = "loja/layout/img/sem_produto.png";
            } else {
                foreach ($fotos as $k => $vFotos) {
                    $row["fotos"][] = $vFotos["link"];
                }
            }
            return $row;
        }

    }


    public function getGradeByProduto($loja_b2b_peca, $tamanho = null)
    {

      if (strlen($this->_loja) == 0) {
          return array("erro" => true, "msn" => traduz("loja.nao.encontrada"));
      }
      if (strlen($tamanho) > 0) {
          $cond = " AND tamanho='{$tamanho}'";
      }

      $sql = "SELECT *
                   FROM tbl_loja_b2b_peca_grade
                  WHERE loja_b2b_peca = {$loja_b2b_peca} AND ativo IS TRUE {$cond }";
      $res = pg_query($this->_con, $sql);

      if (pg_last_error($this->_con)) {
          return array("erro" => true, "msn" => traduz("produto.nao.encontrado"));
      }
      if (pg_num_rows($res) > 0) {
        if (strlen($tamanho) > 0) {
            return pg_fetch_result($res, 0, 'loja_b2b_peca_grade');
        }
        foreach (pg_fetch_all($res) as $key => $value) {
           $tamanhos[] = $value["tamanho"];
        }
        return $tamanhos;
      }
      return [];
  }

    /*
    *   cadastra
    */
    public function saveKit($dados = array()) {

        if (empty($dados)) {
            return array("erro" => true, "msn" => "Dados não enviado!");
        }

        $sql = "INSERT INTO tbl_loja_b2b_kit_peca (
                                                loja_b2b,
                                                categoria,
                                                referencia,
                                                nome,
                                                descricao,
                                                disponivel,
                                                destaque,
                                                ativo
                                            ) VALUES (
                                                ".$this->_loja.",
                                                ".$dados["categoria"].",
                                                '".$dados["referencia"]."',
                                                '".$dados["nome"]."',
                                                '".$dados["descricao"]."',
                                                '".$dados["disponivel"]."',
                                                '".$dados["destaque"]."',
                                                't'
                                            ) RETURNING loja_b2b_kit_peca;";
        $res = pg_query($this->_con, $sql);
        if (pg_last_error($this->_con)) {
            return array("erro" => true, "msn" => "Não foi possível cadastrar o kit.");
        }

        $loja_b2b_kit_peca = pg_fetch_result($res, 0, 0);

        if (count($dados["itens_kit"]) == 0) {
            return array("erro" => true, "msn" => "Itens do kit, não foram enviados.");
        } 

        $erroItens = array();
        foreach ($dados["itens_kit"] as $key => $itens) {
            $sql = "INSERT INTO tbl_loja_b2b_kit_peca_item (
                                                    loja_b2b_kit_peca,
                                                    loja_b2b_peca,
                                                    qtde
                                                ) VALUES (
                                                    ".$loja_b2b_kit_peca.",
                                                    ".$itens["loja_b2b_peca"].",
                                                    ".$itens["quantidade"]."
                                                );";
            $res = pg_query($this->_con, $sql);
            if (pg_last_error($this->_con)) {
                $erroItens[] = array("erro" => true, "msn" => "Não foi possível cadastrar o kit.");
            }
        }

        if (count($erroItens) > 0) {
            return array("erro" => true, "msn" => "Erro ao cadastrar Itens do kit.");
        } 

        return array("loja_b2b_kit_peca" => $loja_b2b_kit_peca, "sucesso" => true);
    }
    /*
    *   cadastra
    */
    public function save($dados = array()) {

        if (empty($dados)) {
            return array("erro" => true, "msn" => traduz("dados.não.enviado"));
        }


        $camposExtras = "";
        $valorExtras  = "";
        if (isset($this->_loja_config["forma_envio"]["meio"]) && count($this->_loja_config["forma_envio"]["meio"]) > 0) {
            $camposExtras = ", comprimento, largura, altura, peso";
            $valorExtras  = ", '".$dados["comprimento"]."', '".$dados["largura"]."', '".$dados["altura"]."', '".$dados["peso"]."'";
        }

        $sql = "INSERT INTO tbl_loja_b2b_peca (
                                                peca,
                                                qtde_max_posto,
                                                qtde_estoque,
                                                loja_b2b,
                                                descricao,
                                                preco_promocional,
                                                ativo,
                                                categoria,
                                                disponivel,
                                                destaque,
                                                kit_peca,
                                                preco,
                                                loja_b2b_fornecedor
                                                {$camposExtras}
                                            ) VALUES (
                                                '".$dados["peca"]."',
                                                0,
                                                '".$dados["qtde_estoque"]."',
                                                ".$this->_loja.",
                                                '".$dados["descricao"]."',
                                                '".$dados["preco_promocional"]."',
                                                't',
                                                '".$dados["categoria"]."',
                                                '".$dados["disponivel"]."',
                                                '".$dados["destaque"]."',
                                                '".$dados["kit_peca"]."',
                                                '".$dados["preco"]."',
                                                ".$dados["loja_b2b_fornecedor"]."
                                                {$valorExtras }
                                            ) RETURNING loja_b2b_peca;";
        $res = pg_query($this->_con, $sql);
        if (pg_last_error($this->_con)) {
            return array("erro" => true, "msn" => traduz(["erro.ao.cadastrar", "produto"]));
        }

        $loja_b2b_peca = pg_fetch_result($res, 0, 0);
        return array("loja_b2b_peca" => $loja_b2b_peca, "sucesso" => true);
    }

    public function cadastraGradeProduto($tamanhos = array(), $loja_b2b_peca) {
        global $login_admin;
        if (empty($tamanhos) || empty($loja_b2b_peca)) {
            return array("erro" => true, "msn" => traduz("dados.não.enviado"));
        }

        foreach ($this->tamanhoGrade as $tm) {

            $sqlValida = "SELECT * FROM tbl_loja_b2b_peca_grade WHERE TRIM(tamanho)=TRIM('{$tm}') AND loja_b2b_peca={$loja_b2b_peca}";
            $resValida  = pg_query($this->_con, $sqlValida);
            if (pg_num_rows($resValida) == 0) {
              $sql = "INSERT INTO tbl_loja_b2b_peca_grade (
                                              loja_b2b_peca,
                                              tamanho,
                                              admin,
                                              ativo
                                          ) VALUES (
                                              '".$loja_b2b_peca."',
                                              '".$tm."',
                                              '".$login_admin."',
                                              'f'
                                          )";
              $res = pg_query($this->_con, $sql);
              if (pg_last_error($this->_con)) {
                  return array("erro" => true, "msn" => traduz(["erro.ao.cadastrar", "grade"]));
              }
            }
        }

        if (count($tamanhos) > 0) {
            for ($i=0; $i < count($tamanhos); $i++) { 
                $sqlUp = "UPDATE tbl_loja_b2b_peca_grade SET ativo='t' WHERE TRIM(tamanho)=TRIM('{$tamanhos[$i]}') AND loja_b2b_peca={$loja_b2b_peca}";
                        $resUp  = pg_query($this->_con, $sqlUp);
                if (pg_last_error($this->_con)) {
                    return array("erro" => true, "msn" => traduz(["erro.ao.cadastrar", "produto"]));
                }

            }
        }
    
    }

    public function updateGradeProduto($tamanhos = array(), $loja_b2b_peca) {
      global $login_admin;

        $sqlLimpa = "UPDATE tbl_loja_b2b_peca_grade SET ativo='f' WHERE loja_b2b_peca={$loja_b2b_peca}";
        $resLimpa  = pg_query($this->_con, $sqlLimpa);
        if (count($tamanhos) > 0) {

            for ($i=0; $i < count($tamanhos); $i++) { 

                $sqlValida = "SELECT loja_b2b_peca_grade FROM tbl_loja_b2b_peca_grade WHERE TRIM(tamanho)=TRIM('{$tamanhos[$i]}') AND loja_b2b_peca={$loja_b2b_peca} LIMIT 1";
                $resValida  = pg_query($this->_con, $sqlValida);

                if (pg_num_rows($resValida) > 0) {
                    $loja_b2b_peca_grade = pg_fetch_result($resValida, 0, "loja_b2b_peca_grade");
                    $sqlUp = "UPDATE tbl_loja_b2b_peca_grade SET ativo='t' WHERE TRIM(tamanho)=TRIM('{$tamanhos[$i]}') AND loja_b2b_peca={$loja_b2b_peca} AND loja_b2b_peca_grade = {$loja_b2b_peca_grade}";
                    $resUp  = pg_query($this->_con, $sqlUp);
                    if (pg_last_error($this->_con)) {
                        return array("erro" => true, "msn" => traduz(["erro.ao.atualizar"]));
                    }
                } else {
                    $sql = "INSERT INTO tbl_loja_b2b_peca_grade (
                                                    loja_b2b_peca,
                                                    tamanho,
                                                    admin,
                                                    ativo
                                                ) VALUES (
                                                    '".$loja_b2b_peca."',
                                                    '".TRIM($tamanhos[$i])."',
                                                    '".$login_admin."',
                                                    't'
                                                )";
                    $res = pg_query($this->_con, $sql);
                    if (pg_last_error($this->_con)) {
                        return array("erro" => true, "msn" => traduz(["erro.ao.cadastrar", "grade"]));
                    }
                }
            }
        }
    }

    /*
    *   altera
    */
    public function update($dados = array()) {

        if (empty($dados)) {
            return array("erro" => true, "msn" => traduz("dados.não.enviado"));
        }

        $camposExtras = "";
        if (isset($this->_loja_config["forma_envio"]["meio"]) && count($this->_loja_config["forma_envio"]["meio"]) > 0) {
            $camposExtras = ", comprimento='".$dados["comprimento"]."', largura='".$dados["largura"]."', altura='".$dados["altura"]."', peso='".$dados["peso"]."'";
        }

        $sql = "UPDATE tbl_loja_b2b_peca  
                   SET
                      categoria='".$dados["categoria"]."', 
                      descricao='".$dados["descricao"]."', 
                      preco='".$dados["preco"]."', 
                      qtde_estoque=".$dados["qtde_estoque"].", 
                      preco_promocional='".$dados["preco_promocional"]."', 
                      disponivel='".$dados["disponivel"]."', 
                      kit_peca='".$dados["kit_peca"]."', 
                      destaque='".$dados["destaque"]."',
                      loja_b2b_fornecedor=".$dados["loja_b2b_fornecedor"].",
                      ativo = true
                      {$camposExtras}
                WHERE loja_b2b=".$this->_loja." 
                  AND loja_b2b_peca=".$dados["loja_b2b_peca"];
        $res = pg_query($this->_con, $sql);

        if (pg_last_error($this->_con)) {
            return array("erro" => true, "msn" => traduz(["erro.ao.atualizar", "produto"]));
        }

        return array("loja_b2b_peca" => $dados["loja_b2b_peca"], "sucesso" => true);
    }


    /*
    *   altera
    */
    public function updateKit($dados = array()) {

        if (empty($dados)) {
            return array("erro" => true, "msn" => "Dados não enviado!");
        }

        $sql = "UPDATE tbl_loja_b2b_kit_peca  
                   SET
                      categoria='".$dados["categoria"]."', 
                      descricao='".$dados["descricao"]."', 
                      referencia='".$dados["referencia"]."', 
                      nome='".$dados["nome"]."', 
                      disponivel='".$dados["disponivel"]."', 
                      destaque='".$dados["destaque"]."'
                WHERE loja_b2b=".$this->_loja." 
                  AND loja_b2b_kit_peca=".$dados["loja_b2b_kit_peca"];
        $res = pg_query($this->_con, $sql);

        if (pg_last_error($this->_con)) {
            return array("erro" => true, "msn" => "Não foi possível editar o kit.");
        }

        if (count($dados["itens_kit"]) > 0) {
            $erroItens = array();
            foreach ($dados["itens_kit"] as $key => $itens) {
                $sql = "INSERT INTO tbl_loja_b2b_kit_peca_item (
                                                        loja_b2b_kit_peca,
                                                        loja_b2b_peca,
                                                        qtde
                                                    ) VALUES (
                                                        ".$dados["loja_b2b_kit_peca"].",
                                                        ".$itens["loja_b2b_peca"].",
                                                        ".$itens["quantidade"]."
                                                    );";
                $res = pg_query($this->_con, $sql);
                if (pg_last_error($this->_con)) {
                    $erroItens[] = array("erro" => true, "msn" => "Não foi possível cadastrar o kit.");
                }
            }

            if (count($erroItens) > 0) {
                return array("erro" => true, "msn" => "Erro ao cadastrar Itens do kit.");
            } 
        }

        return array("loja_b2b_kit_peca" => $dados["loja_b2b_kit_peca"], "sucesso" => true);
    }


    public function getPeca($peca = null, $referencia = null) {

        $cond = "";
        if (strlen($peca) > 0 && strlen($referencia) == 0) {
            $cond = " AND peca={$peca}";
        }

        if (strlen($referencia) > 0 && strlen($peca) == 0) {
            $cond = " AND referencia='$referencia'";
        }

        $sql = "SELECT * FROM tbl_peca WHERE fabrica={$this->_fabrica} {$cond}";
        $res = pg_query($this->_con, $sql);

        if (pg_last_error($this->_con)) {
            return array("erro" => true, "msn" => traduz("peca.nao.encontrada"));
        }

        if (strlen($peca) > 0 || strlen($referencia) > 0) {
            return pg_fetch_assoc($res);
        }

        return pg_fetch_all($resPeca);
    }

    /*
    *   Deleta 
    */
    public function delete($loja_b2b_peca) {
        if (empty($loja_b2b_peca)) {
            return array("erro" => true, "msn" => traduz("dados.não.enviado"));
        }

        $sql = "UPDATE tbl_loja_b2b_peca SET ativo='f' WHERE loja_b2b=".$this->_loja." AND loja_b2b_peca=".$loja_b2b_peca;
        $res = pg_query($this->_con, $sql);

        if (pg_last_error($this->_con)) {
            return array("erro" => true, "msn" => traduz(["erro.ao.remover", "produto"]));
        }

        return array("sucesso" => true);
    }

    /*
    *   Deleta kit
    */
    public function deleteKit($loja_b2b_kit_peca) {

        if (empty($loja_b2b_kit_peca)) {
            return array("erro" => true, "msn" => "Dados não enviado!");
        }

        $sql = "UPDATE tbl_loja_b2b_kit_peca SET ativo='f' WHERE loja_b2b=".$this->_loja." AND loja_b2b_kit_peca=".$loja_b2b_kit_peca;
        $res = pg_query($this->_con, $sql);

        if (pg_last_error($this->_con)) {
            return array("erro" => true, "msn" => 'Erro ao remover kit.');
        }

        return array("sucesso" => true);
    }

   /*
    *   altera
    */
    public function updateCategoria($loja_peca, $nova_categoria) {

        if (empty($loja_peca)) {
            return array("erro" => true, "msn" => "Produto não enviado!");
        }        
        if (empty($nova_categoria)) {
            return array("erro" => true, "msn" => "Categoria não enviada!");
        }

        $sql = "UPDATE tbl_loja_b2b_peca  
                   SET categoria='".$nova_categoria."'
                WHERE loja_b2b=".$this->_loja." 
                  AND loja_b2b_peca=".$loja_peca;
        $res = pg_query($this->_con, $sql);

        if (pg_last_error($this->_con)) {
            return array("erro" => true, "msn" => "Não foi possível atualizar a categoria.");
        }

        return array("loja_b2b_peca" => $dados["loja_b2b_peca"], "sucesso" => true);
    }

    public function updatePrecos($loja_peca, $novo_preco, $novo_preco_promocional_peca){
        $altera = "";
        if (empty($loja_peca)) {
            return array("erro" => true, "msn" => "Produto não enviado!");
        }        

        if (!empty($novo_preco)) {
            $altera .= "preco='{$novo_preco}'";
        } else {
            $altera = "";
        }

        if (!empty($novo_preco_promocional_peca)) {
            if (!empty($altera)) { 
                $altera .= ",preco_promocional='{$novo_preco_promocional_peca}'";
            } else {
                $altera .= "preco_promocional='{$novo_preco_promocional_peca}'";
            }
        }
        if (strlen($altera) > 0) {

            $sql = "UPDATE tbl_loja_b2b_peca  
                       SET {$altera}
                    WHERE loja_b2b=".$this->_loja." 
                      AND loja_b2b_peca=".$loja_peca;
            $res = pg_query($this->_con, $sql);

            if (pg_last_error($this->_con)) {
                return array("erro" => true, "msn" => "Não foi possível atualizar os preços.");
            }

            return array("loja_b2b_peca" => $dados["loja_b2b_peca"], "sucesso" => true);
        } else {
            return array("erro" => true, "msn" => "Preço(s) não enviado.");
        }
    }

    public function atualizaEstoque($loja_b2b_peca, $quantidade) {

        if (empty($loja_b2b_peca)) {
            return array("erro" => true, "msn" => traduz("dados.não.enviado"));
        }

        $sql = "UPDATE tbl_loja_b2b_peca  
                   SET qtde_estoque='".$quantidade."'
                 WHERE loja_b2b=".$this->_loja." 
                   AND loja_b2b_peca=".$loja_b2b_peca;
        $res = pg_query($this->_con, $sql);

        if (pg_last_error($this->_con)) {
            return array("erro" => true, "msn" => traduz(["erro.ao.atualizar", "produto"]));
        }

        return array("erro" => false);
    }

    public function getItensKit($loja_b2b_kit_peca, $loja_b2b_peca = null) {

        $retorno = array();
        $cond = "";
        if (strlen($this->_loja) == 0) {
            return array("erro" => true, "msn" => "Loja não encontrada!");
        }
        if (!empty($loja_b2b_peca)) {
          $cond = " AND tbl_loja_b2b_kit_peca_item.loja_b2b_peca = {$loja_b2b_peca}";
        }
        $sql = "SELECT tbl_loja_b2b_kit_peca_item.loja_b2b_kit_peca,
                       tbl_loja_b2b_kit_peca_item.loja_b2b_peca,
                       tbl_loja_b2b_kit_peca_item.qtde,
                       tbl_loja_b2b_peca.preco,
                       tbl_loja_b2b_peca.preco_promocional,
                       tbl_peca.referencia,
                       tbl_peca.descricao
                  FROM tbl_loja_b2b_kit_peca_item
                  JOIN tbl_loja_b2b_kit_peca ON tbl_loja_b2b_kit_peca.loja_b2b_kit_peca = tbl_loja_b2b_kit_peca_item.loja_b2b_kit_peca
                  JOIN tbl_loja_b2b_peca ON tbl_loja_b2b_peca.loja_b2b_peca = tbl_loja_b2b_kit_peca_item.loja_b2b_peca AND tbl_loja_b2b_peca.loja_b2b = {$this->_loja}
                  JOIN tbl_peca ON tbl_peca.peca = tbl_loja_b2b_peca.peca AND tbl_peca.ativo IS TRUE AND tbl_peca.fabrica = $this->_fabrica
                 WHERE tbl_loja_b2b_kit_peca.ativo IS TRUE
                   AND tbl_loja_b2b_kit_peca_item.loja_b2b_kit_peca = {$loja_b2b_kit_peca}
                   AND tbl_loja_b2b_kit_peca.loja_b2b = {$this->_loja}
                   $cond";
        $res = pg_query($this->_con, $sql);
        if (pg_last_error($this->_con)) {
            return array("erro" => true, "msn" => "Erro ao buscar itens do kit");
        }

        if (!empty($loja_b2b_peca)) {
         $rows = pg_fetch_assoc($res) ;
              //busca tabela de preco
              if (!empty($this->_loja_cliente)) {

                  $tabela_preco = $this->tabelaPreco->getTabelaByCliente($this->_loja_cliente);
                  
                  if (!empty($tabela_preco)) {
                  
                      $tabela_preco_item = $this->tabelaPreco->getItem($rows["loja_b2b_peca"], $tabela_preco["loja_b2b_tabela"]);
                  
                      if (!empty($tabela_preco_item)) {
                          $preco = $tabela_preco_item['preco'];
                      } else {
                          if ($rows["preco_promocional"] > 0) {
                              $preco = $rows["preco_promocional"];
                          } else {
                              $preco = $rows["preco"];
                          }
                      }
                  
                  }

              } else {
                  if ($rows["preco_promocional"] > 0) {
                      $preco = $rows["preco_promocional"];
                  } else {
                      $preco = $rows["preco"];
                  }
              }

              $rows["preco_venda"] = $preco; 


          return $rows;  
           
        } else {

          $row = pg_fetch_all($res) ;
          foreach ($row as $key => $rows) {
              //busca tabela de preco
              if (!empty($this->_loja_cliente)) {

                  $tabela_preco = $this->tabelaPreco->getTabelaByCliente($this->_loja_cliente);
                  
                  if (!empty($tabela_preco)) {
                  
                      $tabela_preco_item = $this->tabelaPreco->getItem($rows["loja_b2b_peca"], $tabela_preco["loja_b2b_tabela"]);
                  
                      if (!empty($tabela_preco_item)) {
                          $preco = $tabela_preco_item['preco'];
                      } else {
                          if ($rows["preco_promocional"] > 0) {
                              $preco = $rows["preco_promocional"];
                          } else {
                              $preco = $rows["preco"];
                          }
                      }
                  
                  }

              } else {
                  if ($rows["preco_promocional"] > 0) {
                      $preco = $rows["preco_promocional"];
                  } else {
                      $preco = $rows["preco"];
                  }
              }

              $row[$key]["preco_venda"] = $preco; 

          }

          return $row;
        }
    }

}
