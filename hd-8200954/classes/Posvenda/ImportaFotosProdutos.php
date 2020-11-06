<?php
namespace Posvenda;
use Posvenda\Model\Produto;
include_once dirname(__FILE__) . '/../../class/aws/s3_config.php';
include_once dirname(__FILE__) . '/../../class/tdocs.class.php';
include_once S3CLASS;

class ImportaFotosProdutos {

    private   $_modelProduto;
    protected $_con;
    protected $_fabrica;
    protected $_s3;
    protected $_tdocs;
    protected $servico;

    public function __construct($fabrica, $con, $servico) {

        $this->servico       = $servico;
        $this->_fabrica      = $fabrica;
        $this->_con          = $con;
        $this->_modelProduto = new Produto($this->_fabrica);

        if ($this->servico == "amazon") {
            $this->_s3    = new \AmazonTC('produto', $this->_fabrica);
        }

        if ($this->servico == "tdocs") {
            $this->_tdocs = new \TDocs($this->_con, $this->_fabrica);
        }

    }

    public function preparaDadosImportacao($diretorioFotos, $tipo) {

        if (empty($diretorioFotos)) {
            return array("erro" => true, "msg" => "Diretório não informado");
        }

        if (!is_dir($diretorioFotos)) {
            return array("erro" => true, "msg" => "Diretório não existe");
        }

        $diretorio  = dir($diretorioFotos);
        $listaFotos = array();

        while (($arquivo = $diretorio->read()) !== false) {

            list($nome_arquivo, $extensao) = explode(".", $arquivo);

            if (strlen($nome_arquivo) == 0) {
                continue;
            }

            $listaFotos[$arquivo] = $nome_arquivo;

        }

        $diretorio->close();

        foreach ($listaFotos as $key => $referencia) {

            if ($tipo == "produto") {
                $dadosProduto = $this->_modelProduto->getProdutoByRef(trim($referencia), $this->_fabrica);

                if (empty($dadosProduto["produto"])) {

                    $dados["produtos_nao_encontrados"][] = trim($referencia);

                } else {

                    $dados["produtos_encontrados"][$key] = $dadosProduto["produto"];

                }
            }

            if ($tipo == "peca") {

                $sql = "SELECT peca, referencia FROM tbl_peca WHERE referencia='{$referencia}' AND fabrica={$this->_fabrica}";
                $res = pg_query($this->_con, $sql);
                $dadosProduto = pg_fetch_array($res);

                if (empty($dadosProduto["peca"])) {

                    $dados["produtos_nao_encontrados"][] = trim($referencia);

                } else {

                    $dados["produtos_encontrados"][$key] = $dadosProduto["peca"];

                }
            }

        }
        $dados["total_arquivos"] = count($listaFotos);
        $dados["total_arquivos_encontrados"] = count($dados["produtos_encontrados"]);
        $dados["total_arquivos_nao_encontrados"] = count($dados["produtos_nao_encontrados"]);
        return $dados;
    }

    public function importaFotos($diretorioFotos, $tipo) {
            
            if (empty($diretorioFotos)) {
                return array("erro" => true,"msg" => "Diretório não informado");
            }
       
            if (empty($tipo)) {
                return array("erro" => true,"msg" => "Tipo não informado");
            }

            $dadosImportacao = $this->preparaDadosImportacao($diretorioFotos, $tipo);

            
            foreach ($dadosImportacao["produtos_encontrados"] as $arquivo => $produto) {
                $tipo  = strtolower(preg_replace("/.+\./", "", $arquivo));

                $tipo  = ($tipo == "jpeg") ? "jpg" : $tipo;
                list($nome_arquivo, $extensao) = explode(".", $arquivo);

                if (in_array($tipo, array("jpg", "jpeg", "png", "bmp"))) {

                    $this->_s3->upload($produto, $diretorioFotos.$arquivo);

                    if (empty($this->_s3->result)) {

                        $dadosImportacao["produtos_nao_importados"][$nome_arquivo] = $nome_arquivo;

                    } else {

                        $dadosImportacao["produtos_importados"][$nome_arquivo] = $nome_arquivo;

                    }
                } else {

                    $dadosImportacao["produtos_nao_importados"][$arquivo] = $nome_arquivo;

                }

            }
            $dadosImportacao["total_produtos_importados"] = count($dadosImportacao["produtos_importados"]);
            $dadosImportacao["total_produtos_nao_importados"] = count($dadosImportacao["produtos_nao_importados"]);

            return $this->trataRetorno($dadosImportacao);

    }

    public function trataRetorno($dadosImportacao) {

        if (empty($dadosImportacao)) {
            return array("erro" => true,"msg" => "Dados não informado");
        }

        //RESUMO DA IMPORTAÇÃO DE FOTOS
        $resumo = "<table border='1' style='border-color:#cccccc' cellpadding='0' cellspacing='0' width='100%'>
                    <thead>
                        <tr bgcolor='#494a86'>
                            <th colspan='4' align='center' style='padding:3px;color:#ffffff'>RESUMO IMPORTAÇÃO DE FOTOS</th>
                        </tr>
                    </thead>
                    <tr>
                        <td style='padding:3px;'>TOTAL DE FOTOS NO DIRETORIO</td>
                        <td style='padding:3px;font-weight:bold;'>".$dadosImportacao['total_arquivos']."</td>
                    </tr>
                    <tr>
                        <td style='padding:3px;'>TOTAL DE PRODUTOS ENCONTRADOS</td>
                        <td style='padding:3px;font-weight:bold;'>".$dadosImportacao['total_arquivos_encontrados']."</td>
                    </tr>
                    <tr>
                        <td style='padding:3px;'>TOTAL DE PRODUTOS NÃO ENCONTRADOS</td>
                        <td style='padding:3px;font-weight:bold;'>".$dadosImportacao['total_arquivos_nao_encontrados']."</td>
                    </tr>
                    <tr>
                        <td style='padding:3px;'>TOTAL DE FOTOS IMPORTADAS</td>
                        <td style='padding:3px;font-weight:bold;'>".$dadosImportacao['total_produtos_importados']."</td>
                    </tr>
                    <tr>
                        <td style='padding:3px;'>TOTAL DE FOTOS NÃO IMPORTADAS</td>
                        <td style='padding:3px;font-weight:bold;'>".$dadosImportacao['total_produtos_nao_importados']."</td>
                    </tr>
                </table>
                ";

        //DETALHE FOTOS IMPORTADAS
        if (!empty($dadosImportacao["produtos_nao_encontrados"])) {

            $resumo .= "<br /><br /><br /><table border='1' style='border-color:green' cellpadding='0' cellspacing='0' width='100%'>
                        <thead>
                            <tr bgcolor='green'>
                                <th align='center' style='padding:3px;color:#ffffff'>FOTOS IMPORTADAS</th>
                            </tr>
                        </thead>";
                        foreach ($dadosImportacao["produtos_importados"] as $k => $referencia) {
                            $resumo .= "<tr>
                                            <td style='padding:3px;'>Referência : <b>$referencia<b/></td>
                                        </tr>";
                        }
            $resumo .= "
                    </table>
                    ";

        }


        //DETALHE  FOTOS NÃO IMPORTADAS
        if (!empty($dadosImportacao["produtos_nao_importados"])) {

            $resumo .= "<br /><br /><br /><table border='1' style='border-color:#D90000' cellpadding='0' cellspacing='0' width='100%'>
                        <thead>
                            <tr bgcolor='#D90000'>
                                <th align='center' style='padding:3px;color:#ffffff'>FOTOS NÃO IMPORTADAS</th>
                            </tr>
                        </thead>";
                        foreach ($dadosImportacao["produtos_nao_importados"] as $k => $referencia) {
                            $resumo .= "<tr>
                                            <td style='padding:3px;'>Referência : <b>$referencia<b/></td>
                                        </tr>";
                        }
            $resumo .= "
                    </table>
                    ";

        }

        //DETALHE PRODUTOS NÃO ENCONTRADOS
        if (!empty($dadosImportacao["produtos_nao_encontrados"])) {

            $resumo .= "<br /><br /><br /><table border='1' style='border-color:#d90000' cellpadding='0' cellspacing='0' width='100%'>
                        <thead>
                            <tr bgcolor='#d90000'>
                                <th align='center' style='padding:3px;color:#ffffff'>PRODUTOS NÃO ENCONTRADOS</th>
                            </tr>
                        </thead>";
                        foreach ($dadosImportacao["produtos_nao_encontrados"] as $k => $referencia) {
                            $resumo .= "<tr>
                                            <td style='padding:3px;'>Referência : <b>$referencia<b/></td>
                                        </tr>";
                        }
            $resumo .= "
                    </table>
                    ";

        }

        return $resumo;

    }

}
