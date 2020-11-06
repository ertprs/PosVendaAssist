<?php
namespace Lojavirtual;
use Lojavirtual\Controller;
use Lojavirtual\Produto;

require_once __DIR__ . DIRECTORY_SEPARATOR . "../../class/communicator.class.php";
require_once __DIR__ . DIRECTORY_SEPARATOR . "../../class/tdocs.class.php";

class Comunicacao extends Controller {

    protected $externalid;
    protected $objProduto;
    protected $tDocs;

    public function __construct($externalId) {
        parent::__construct();
        $this->externalid = $externalId;
        $this->objProduto = new Produto($login_posto);
        $this->tDocs      = new \TDocs($this->_con, $this->_fabrica);
        $this->tDocs->setContext('lojalogo');
    }

    public function enviaNovoPedido($dadosPedidos) {

        if (empty($dadosPedidos)) {
            return false;
        }

        $assunto        = 'Novo pedido gerado - Nº '.$dadosPedidos["pedido"].' - B2B';
        $mensagem       = $this->montaCorpoEmailPedido($dadosPedidos);
        $externalId     = 'smtp@posvenda';
        $externalEmail  = 'noreply@telecontrol.com.br';

        $mailTc = new \TcComm($externalId);
        $res = $mailTc->sendMail(
            $dadosPedidos["email_posto"],
            $assunto,
            $mensagem,
            $externalEmail
        );
        return $res;
    }

    public function enviaNovoPedidoFornecedor($dadosPedidos) {

        if (empty($dadosPedidos)) {
            return false;
        }

        $assunto        = 'Novo pedido gerado - Nº '.$dadosPedidos["pedido"].' - B2B';
        $mensagem       = $this->montaCorpoEmailPedidoFornecedor($dadosPedidos);
        $externalId     = 'smtp@posvenda';
        $externalEmail  = 'noreply@telecontrol.com.br';

        $mailTc = new \TcComm($externalId);
        $res = $mailTc->sendMail(
            $dadosPedidos["email_fornecedor"],
            $assunto,
            $mensagem,
            $externalEmail
        );
        return $res;
    }

    public function enviaAviseMe($dados) {

        if (empty($dados)) {
            return false;
        }

        $assunto        = 'Avise-me - produto já esta disponível para compra.';
        $mensagem       = $this->montaCorpoEmailAviseMe($dados);
        $externalId     = 'smtp@posvenda';
        $externalEmail  = 'noreply@telecontrol.com.br';

        $mailTc = new \TcComm($externalId);
        $res = $mailTc->sendMail(
            $dados["email_posto"],
            $assunto,
            $mensagem,
            $externalEmail
        );
        return $res;
    }


    public function montaCorpoEmailAviseMe($dados) {
        $logoLoja = $this->tDocs->getDocumentsByRef($this->_loja)->url;
        $mensagem = "
            <table width='100%' style='border:solid 1px #dddddd'>
                <tr>
                    <td width='80%' valign='top' style='padding:5px;'>
                        <img src='{$logoLoja}'>
                    </td>
                    <td valign='middle' style='padding:5px;background:#dddddd;text-align:center'>
                        <h3>Avise-me</h3>
                    </td>
                </tr>
                <tr>
                    <td valign='top' colspan='2' style='border-top:solid 2px #dddddd'>
                        <p>Olá <b>".$dados["nome_posto"]."</b>.</p>
                        <p>O produto <b>".$dados["produto"]."</b> já esta disponível para compra em nossa loja.</p><br />
                        Atenciosamente<br />
                        <b>".strtoupper($this->_nomeFabrica)."</b>
                    </td>
                </tr>
            </table>";
        
        return $mensagem;
       
    }


    public function montaCorpoEmailPedido($dadosPedidos) {
        $logoLoja = $this->tDocs->getDocumentsByRef($this->_loja)->url;
        $itens = "";
        $i = 1;

        foreach ($dadosPedidos["itens"] as $kCart => $vCart) {
            $totalPedido[] = ($vCart["qtde"]*$vCart["preco"]);
            $dadosProduto  =  $this->objProduto->get(null, $vCart["peca"]);
            if ($i % 2 == 0) {
                $cor = "#eeeeee";
            } else {
                $cor = "#ffffff";
            }
            
            $tm = "";
            if (isset($vCart["tamanho"]) && strlen($vCart["tamanho"]) > 0) {
                $tm = "<br><em>Tamanho selecionado: <b>".$vCart["tamanho"]."</b></em>";
            }


            $itens .= '<tr bgcolor="'.$cor.'">
                        <td style="padding:5px;">' . $dadosProduto[0]["nome_peca"] . ' '.$tm.'</td>
                        <td style="padding:5px;" align="center">' . $vCart["qtde"] . ' </td>
                        <td style="padding:5px;" align="center">R$ ' . number_format($vCart["preco"], 2, ',', '.') . '</td>
                        <td style="padding:5px;" align="center">R$ ' . number_format(($vCart["qtde"]*$vCart["preco"]), 2, ',', '.') . '</td>
                    </tr>';
            $i ++;
        }
            
        $mensagem = "
            <table width='100%' style='border:solid 1px #dddddd'>
                <tr>
                    <td width='80%' valign='top' style='padding:5px;'>
                        <img  style='width: 250px;' src='{$logoLoja}'>
                    </td>
                    <td valign='top' style='padding:5px;background:#dddddd;text-align:center'>
                        <b>PEDIDO Nº</b>
                        <h1>" . $dadosPedidos["pedido"] . "</h1>
                    </td>
                </tr>
                <tr>
                    <td valign='top' colspan='2' style='border-top:solid 1px #cccccc'>
                        <table width='100%'>
                            <tr bgcolor='#eeeeee'>
                                <th style='padding:5px;'>STATUS DO PEDIDO</th>
                                <th style='padding:5px;'>" . $dadosPedidos["status"]["descricao"] . "</th>
                                <th style='padding:5px;'>CONDIÇÃO DE PAGAMENTO</th>
                                <th style='padding:5px;'>" . $dadosPedidos["condicaopagamento"]["descricao"] . "</th>
                            </tr>    
                        </table> 
                    </td>
                </tr>
                <tr>
                    <td valign='top' colspan='2'>
                        <p style='margin-bottom:10px;margin-top:10px;width:100%;padding:10px;background-color: #51a351;font: bold 16px Arial !important;color: #FFFFFF;text-align: center;'>
                        Resumo do Pedido
                        </p>
                    </td>
                </tr>
                <tr>
                    <td valign='top' colspan='2'>
                        <table width='100%' border='0'>
                            <thead>
                                <tr style='background-color:#333333;color:#ffffff;'>
                                    <th style='padding:5px;text-align:left'>PRODUTO</th>
                                    <th style='padding:5px;' width='5%' align='center'>QUANTIDADE</th>
                                    <th style='padding:5px;' width='18%' align='center'>VALOR UNIT.</th>
                                    <th style='padding:5px;' width='5%' align='center'>SUBTOTAL</th>
                                </tr>    
                            </thead>  
                            <tbody>{$itens}</tbody>
                            <tfoot> 
                                <tr>
                                    <td colspan='2' style='padding:5px;font-size:16px;' align='right'><b>TOTAL DO PEDIDO</b></td>
                                    <td align='center' colspan='2' style='padding:5px;font-size:16px;'><b>R$ " . number_format(array_sum($totalPedido), 2, ',', '.') . "</b></td>
                                </tr> 
                            </tfoot>
                        </table> 
                    </td>
                </tr>
            </table>";
        
        return $mensagem;
       
    }

    public function montaCorpoEmailPedidoFornecedor($dadosPedidos) {
        $logoLoja = $this->tDocs->getDocumentsByRef($this->_loja)->url;
        $itens = "";
        $i = 1;

        foreach ($dadosPedidos["itens"] as $kCart => $vCart) {
            $totalPedido[] = ($vCart["qtde"]*$vCart["valor_unitario"]);
            $dadosProduto  =  $this->objProduto->get(null, $vCart["loja_b2b_peca"]);
            if ($i % 2 == 0) {
                $cor = "#eeeeee";
            } else {
                $cor = "#ffffff";
            }

            $tm = "";
            if (isset($vCart["tamanho"]) && strlen($vCart["tamanho"]) > 0) {
                $tm = "<br><em>Tamanho selecionado: <b>".$vCart["tamanho"]."</b></em>";
            }

            $itens .= '<tr bgcolor="'.$cor.'">
                        <td style="padding:5px;">'.$vCart["produto"]["ref_peca"]. "-" . $vCart["produto"]["nome_peca"] . ' '.$tm.'</td>
                        <td style="padding:5px;" align="center">' . $vCart["qtde"] . ' </td>
                        <td style="padding:5px;" align="center">R$ ' . number_format($vCart["valor_unitario"], 2, ',', '.') . '</td>
                        <td style="padding:5px;" align="center">R$ ' . number_format(($vCart["qtde"]*$vCart["valor_unitario"]), 2, ',', '.') . '</td>
                    </tr>';
            $i ++;
        }
            
        $mensagem = "
            <table width='100%' style='border:solid 1px #dddddd'>
                <tr>
                    <td width='23%' valign='top' style='padding:5px;'>
                        <img src='{$logoLoja}' style='width: 250px;'>
                    </td>
                    <td width='57%' valign='top' style='padding:5px;'>
                        <strong>CNPJ:</strong> ".$dadosPedidos['cnpj_posto']."&nbsp;&nbsp;
                        <strong>Razão Social:</strong> ".$dadosPedidos['nome_posto']." <br />
                        <strong>I.E:</strong> ".$dadosPedidos['inscricao_estadual']." &nbsp;&nbsp;
                        <strong>CEP:</strong> ".$dadosPedidos['cep_posto']."&nbsp;&nbsp;
                        <strong>Endereço:</strong> ".$dadosPedidos['endereco_posto'].", ".$dadosPedidos['numero_posto']."&nbsp;&nbsp;
                        <strong>Complemento:</strong> ".$dadosPedidos['complemento_posto']."&nbsp;&nbsp;
                        <br />
                        <strong>Bairro:</strong> ".$dadosPedidos['bairro_posto']."&nbsp;&nbsp;
                        <strong>Cidade:</strong> ".$dadosPedidos['cidade_posto']."&nbsp;&nbsp;
                        <strong>Estado:</strong> ".$dadosPedidos['estado_posto']."
                        <br />
                        <strong>E-mail:</strong> ".$dadosPedidos['email_posto']."&nbsp;&nbsp;
                        <strong>Telefone:</strong> ".$dadosPedidos['telefone_posto']."&nbsp;&nbsp;
                    </td>
                    <td valign='top' style='padding:5px;background:#dddddd;text-align:center'>
                        <b>PEDIDO Nº</b>
                        <h1>" . $dadosPedidos["pedido"] . "</h1>
                        <h2>" . $dadosPedidos["data_ultimo_item"] . "</h2>
                    </td>
                </tr>
                <tr>
                    <td valign='top' colspan='2'>
                        <p style='margin-bottom:10px;margin-top:10px;width:100%;padding:10px;background-color: #51a351;font: bold 16px Arial !important;color: #FFFFFF;text-align: center;'>
                        Resumo do Pedido
                        </p>
                    </td>
                </tr>
                <tr>
                    <td valign='top' colspan='2'>
                        <table width='100%' border='0'>
                            <thead>
                                <tr style='background-color:#333333;color:#ffffff;'>
                                    <th style='padding:5px;text-align:left'>PRODUTO</th>
                                    <th style='padding:5px;' width='5%' align='center'>QUANTIDADE</th>
                                    <th style='padding:5px;' width='18%' align='center'>VALOR UNIT.</th>
                                    <th style='padding:5px;' width='5%' align='center'>SUBTOTAL</th>
                                </tr>    
                            </thead>  
                            <tbody>{$itens}</tbody>
                            <tfoot>
                                <tr>
                                    <td colspan='2' style='padding:5px;font-size:16px;' align='right'><b>CONDIÇÃO DE PAGAMENTO</b></td>
                                    <td align='center' colspan='2' style='padding:5px;font-size:16px;'><b>".$dadosPedidos["condicaopagamento"]["descricao"]."</b></td>
                                </tr> 
                                <tr>
                                    <td colspan='2' style='padding:5px;font-size:16px;' align='right'><b>FRETE</b></td>
                                    <td align='center' colspan='2' style='padding:5px;font-size:16px;'>".$dadosPedidos['forma_envio']." - <b> R$ " . number_format($dadosPedidos['total_frete'], 2, ',', '.') . "</b></td>
                                </tr>
                                <tr>
                                    <td colspan='2' style='padding:5px;font-size:16px;' align='right'><b>TOTAL DO PEDIDO</b></td>
                                    <td align='center' colspan='2' style='padding:5px;font-size:16px;'><b>R$ " . number_format(array_sum($totalPedido)+$dadosPedidos['total_frete'], 2, ',', '.') . "</b></td>
                                </tr>
                            </tfoot>
                        </table> 
                    </td>
                </tr>
            </table>";
        
        return $mensagem;
       
    }
}
