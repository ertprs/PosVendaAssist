<?php
namespace GestaoContrato;

require_once __DIR__ . DIRECTORY_SEPARATOR . "../../class/communicator.class.php";
require_once __DIR__ . DIRECTORY_SEPARATOR . "../../class/tdocs.class.php";

class Comunicacao extends Controller {

    protected $__con;
    protected $__fabrica;
    protected $externalid;
    protected $tDocs;
    protected $logos = [190 => "logos/nilfisk_logo.png"];
    protected $url_base = "http://posvenda.telecontrol.com.br/assist/";
    //protected $url_base = "https://novodevel.telecontrol.com.br/~felipe/posvenda/";
    public function __construct($externalId, $con, $fabrica) {
        global $_serverEnvironment;
        parent::__construct(null, null,null);
        $this->externalid = $externalId;
        $this->__fabrica = $fabrica;
        $this->__con = $con;
        if ($_serverEnvironment == "development") {
            $this->url_base = "http://nilfisk.novodevel.telecontrol.com.br/";
        }
        //$this->tDocs      = new \TDocs($this->_con, $this->_fabrica);
        //$this->tDocs->setContext('lojalogo');

    }

    public function enviaPropostaAprovacaoCliente($dados) {
        if (empty($dados)) {
            return false;
        }

        $assunto        = 'Segue a Proposta de Contrato - Nº '.$dados["contrato"].' - para Aprovação';
        $mensagem       = $this->montaCorpoPropostaAprovacaoCliente($dados);
        $externalId     = 'smtp@posvenda';
        $externalEmail  = 'noreply@telecontrol.com.br';

        $mailTc = new \TcComm($externalId);
        $res = $mailTc->sendMail(
            //'felipe.marttos@telecontrol.com.br',
            $dados["cliente_email"],
            $assunto,
            $mensagem,
            $externalEmail
        );
        return $res;
    }

    public function enviaPropostaReprovadaAuditoriaRepresentante($dados) {

        if (empty($dados)) {
            return false;
        }

        $assunto        = 'Proposta de Contrato - Nº '.$dados["contrato"].' - Reprovado pela Fábrica';
        $mensagem       = $this->montaCorpoPropostaReprovadaAuditoriaRepresentante($dados);
        $externalId     = 'smtp@posvenda';
        $externalEmail  = 'noreply@telecontrol.com.br';

        $mailTc = new \TcComm($externalId);
        $res = $mailTc->sendMail(
            //'felipe.marttos@telecontrol.com.br',
            $dados["email_representante"],
            $assunto,
            $mensagem,
            $externalEmail
        );
        return $res;
    }

    public function enviaNotificacaoPropostaAprovadaReprovadaPorCliente($dados, $tipo) {

        if (empty($dados)) {
            return false;
        }

        $assunto        = 'Proposta de Contrato - Nº '.$dados["contrato"].' - foi '.$tipo.' pelo Cliente';
        $mensagem       = $this->montaCorpoNotificacaoPropostaAprovadaReprovadaPorCliente($dados,$tipo);
        $externalId     = 'smtp@posvenda';
        $externalEmail  = 'noreply@telecontrol.com.br';

        $sqlAdmins = "SELECT email FROM tbl_admin WHERE fabrica = {$this->__fabrica} AND privilegios='*' AND email IS NOT NULL AND cliente_admin IS NULL AND representante_admin IS NULL";
        $resSql = pg_query($this->__con, $sqlAdmins);

        if (pg_num_rows($resSql) > 0) {
            foreach (pg_fetch_all($resSql) as $key => $value) {
                $emails .= $value["email"].';';
            }
        } else {
            $emails = "luis.carlos@telecontrol.com.br;felipe.marttos@telecontrol.com.br;";
        }

        $mailTc = new \TcComm($externalId);
        $res = $mailTc->sendMail(
            $emails,
            $assunto,
            $mensagem,
            $externalEmail
        );
        return $res;
    }

    public function montaCorpoPropostaAprovacaoCliente($dados) {

        global $login_fabrica;
        extract($dados);
	$campo_extra = json_decode($campo_extra,1);
	if (isset($campo_extra['desconto_representante'])) {
		$xdesconto_representante = $campo_extra['desconto_representante'];
	} else {
		$xdesconto_representante = 0;
	}


        $link_aprova    = $this->url_base."/externos/contrato/confirma.php?tipo=aprova&h=".$token;
        $link_reprova   = $this->url_base."/externos/contrato/confirma.php?tipo=reprova&h=".$token;
        $botao_aprovar  = '<a href="'.$link_aprova.'" target="_blank" style=" box-shadow: 0px 10px 14px -7px #276873;background-color:#59b35e;border-radius:8px;display:inline-block;cursor:pointer;color:#ffffff;font-family:Arial;font-size:20px;font-weight:bold;padding:13px 32px;text-decoration:none;text-shadow:0px 1px 0px #3d768a;">Aprovar Proposta</a>';
        $botao_reprovar =  '<a href="'.$link_reprova.'" target="_blank" style=" box-shadow: 0px 10px 14px -7px #276873;background-color:#d90000;border-radius:8px;display:inline-block;cursor:pointer;color:#ffffff;font-family:Arial;font-size:20px;font-weight:bold;padding:13px 32px;text-decoration:none;text-shadow:0px 1px 0px #3d768a;">Reprovar Proposta</a>';
        $genero = ($genero_contrato == "M") ? "Manutenção" : "Locação";
        if (count($itens) > 0) {
            $subtotal = 0;
            foreach ($itens as $k => $value) {
                $subtotal += $value["preco"];
                $status = ($value["preventiva"] == "t") ? "Sim" : "Não";
                $items .= '
                            <tr>
                                <td style="border: 1px solid #dddddd;text-align: center;" class="tac">'.$value["referencia_produto"].'</td>
                                <td style="border: 1px solid #dddddd;text-align: left;" class="tal">'.$value["nome_produto"].'</td>
                                <td style="border: 1px solid #dddddd;text-align: center;" class="tac">R$ '.number_format($value["preco"], 2, ',', '.').'</td>
                                <td style="border: 1px solid #dddddd;text-align: center;" class="tac">'.$value["horimetro"].'</td>
                                <td style="border: 1px solid #dddddd;text-align: center;" class="tac">'.$status.'</td>
                            </tr>';
        
            } 
        } 

        $mensagem = '
        <table class="table table-bordered" style="border: 1px solid #dddddd;width: 940px;border-collapse: separate;margin: 0 auto; table-layout: fixed;font-family: arial" >
            <tr>
                <th><img width="170" src="'.$this->url_base.$this->logos[$login_fabrica].'" alt=""></th>
                <th class="tar" colspan="4">
                    <div class="tac" style="width: 200px;border: solid 1px #eee;float: right;">
                        <h4>Nº da Proposta</h4>
                        <h1>'.$contrato.'</h1>
                        <p>'.$nome_status.'</p>
                    </div>
                </th>
            </tr>
        </table>
        <table class="table table-bordered" style="border: 1px solid #dddddd;width: 940px;border-collapse: separate;margin: 0 auto; table-layout: fixed;margin-top: 5px;font-family: arial" >
            <tr>
                <th style="font-weight: bold;background-color: #596d9b;padding: 8px;line-height: 20px;text-align: left; color: #fff;" colspan="8" >Informações da Proposta</th>
            </tr>
            <tr>
                <td style="padding: 8px;line-height: 20px;text-align: left;vertical-align: top;background: #D9E2EF;border: 1px solid #dddddd;" nowrap><b>Genêro:</b> </td>
                <td style="border: 1px solid #dddddd;padding: 8px;line-height: 20px;text-align: left;vertical-align: top;">'.$genero.'</td>
                <td style="padding: 8px;line-height: 20px;text-align: left;vertical-align: top;background: #D9E2EF;border: 1px solid #dddddd;" nowrap><b>Tipo da Proposta:</b></td>
                <td style="border: 1px solid #dddddd;padding: 8px;line-height: 20px;text-align: left;vertical-align: top;">'.$tipo_contrato_nome.'</td>
                <td style="padding: 8px;line-height: 20px;text-align: left;vertical-align: top;background: #D9E2EF;border: 1px solid #dddddd;" nowrap><b>Tabela de Preço:</b></td>
                <td style="border: 1px solid #dddddd;padding: 8px;line-height: 20px;text-align: left;vertical-align: top;" colspan="3">'.$nome_tabela.'</td>
            </tr>
            <tr>
                <td style="padding: 8px;line-height: 20px;text-align: left;vertical-align: top;background: #D9E2EF;border: 1px solid #dddddd;" nowrap><b>Qtde Preventivas:</b></td>
                <td style="border: 1px solid #dddddd;padding: 8px;line-height: 20px;text-align: left;vertical-align: top;">'.$qtde_preventiva.'</td>
                <td style="padding: 8px;line-height: 20px;text-align: left;vertical-align: top;background: #D9E2EF;border: 1px solid #dddddd;" nowrap><b>Qtde Corretivas:</b></td>
                <td style="border: 1px solid #dddddd;padding: 8px;line-height: 20px;text-align: left;vertical-align: top;" colspan="5">'.$qtde_corretiva.'</td>
            </tr>
        </table>

        <table class="table table-bordered" style="border: 1px solid #dddddd;width: 940px;border-collapse: separate;margin: 0 auto; table-layout: fixed;margin-top: 5px;font-family: arial" >
            <tr>
                <th style="font-weight: bold;background-color: #596d9b;padding: 8px;line-height: 20px;text-align: left; color: #fff;" colspan="8" >Informações do Representante</th>
            </tr>
            <tr>
                <td style="padding: 8px;line-height: 20px;text-align: left;vertical-align: top;background: #D9E2EF;border: 1px solid #dddddd;" class="tar" nowrap><b>Código:</b></td>
                <td style="border: 1px solid #dddddd;padding: 8px;line-height: 20px;text-align: left;vertical-align: top;">'.$representante_codigo.'</td>
                <td style="padding: 8px;line-height: 20px;text-align: left;vertical-align: top;background: #D9E2EF;border: 1px solid #dddddd;" nowrap><b>Nome:</b></td>
                <td style="border: 1px solid #dddddd;padding: 8px;line-height: 20px;text-align: left;vertical-align: top;" colspan="5"> '.$representante_nome.'</td>
            </tr>
            <tr>
                <td style="padding: 8px;line-height: 20px;text-align: left;vertical-align: top;background: #D9E2EF;border: 1px solid #dddddd;" nowrap><b>CPF/CNPJ:</b></td>
                <td style="border: 1px solid #dddddd;padding: 8px;line-height: 20px;text-align: left;vertical-align: top;">'.$cpf_cnpj_representante.'</td>
                <td style="padding: 8px;line-height: 20px;text-align: left;vertical-align: top;background: #D9E2EF;border: 1px solid #dddddd;" nowrap><b>Telefone:</b></td>
                <td style="border: 1px solid #dddddd;padding: 8px;line-height: 20px;text-align: left;vertical-align: top;">'.$fone_representante.'</td>
                <td style="padding: 8px;line-height: 20px;text-align: left;vertical-align: top;background: #D9E2EF;border: 1px solid #dddddd;" nowrap><b>Email:</b></td>
                <td style="border: 1px solid #dddddd;padding: 8px;line-height: 20px;text-align: left;vertical-align: top;" colspan="3">'.$email_representante.'</td>
            </tr>
        </table>

        <table class="table table-bordered" style="border: 1px solid #dddddd;width: 940px;border-collapse: separate;margin: 0 auto; table-layout: fixed;margin-top: 5px;font-family: arial" >
            <tr>
                <th style="font-weight: bold;background-color: #596d9b;padding: 8px;line-height: 20px;text-align: left; color: #fff;" colspan="8" >Informações do Cliente</th>
            </tr>
            <tr>
                <td style="padding: 8px;line-height: 20px;text-align: left;vertical-align: top;background: #D9E2EF;border: 1px solid #dddddd;" nowrap><b>Nome:</b></td>
                <td style="border: 1px solid #dddddd;padding: 8px;line-height: 20px;text-align: left;vertical-align: top;" colspan="5">'.$cliente_nome.'</td>
                <td style="padding: 8px;line-height: 20px;text-align: left;vertical-align: top;background: #D9E2EF;border: 1px solid #dddddd;" nowrap><b>CPF/CNPJ:</b></td>
                <td style="border: 1px solid #dddddd;padding: 8px;line-height: 20px;text-align: left;vertical-align: top;">'.$cliente_cpf.'</td>
            </tr>
            <tr>
                <td style="padding: 8px;line-height: 20px;text-align: left;vertical-align: top;background: #D9E2EF;border: 1px solid #dddddd;" nowrap><b>E-mail:</b></td>
                <td style="border: 1px solid #dddddd;padding: 8px;line-height: 20px;text-align: left;vertical-align: top;" colspan="3">'.$cliente_email.'</td>
                <td style="padding: 8px;line-height: 20px;text-align: left;vertical-align: top;background: #D9E2EF;border: 1px solid #dddddd;" nowrap><b>Telefone:</b> </td>
                <td style="border: 1px solid #dddddd;padding: 8px;line-height: 20px;text-align: left;vertical-align: top;">'.$cliente_fone.'</td>
                <td style="padding: 8px;line-height: 20px;text-align: left;vertical-align: top;background: #D9E2EF;border: 1px solid #dddddd;" nowrap><b>Celular:</b></td>
                <td style="border: 1px solid #dddddd;padding: 8px;line-height: 20px;text-align: left;vertical-align: top;">'.$cliente_celular.'</td>
            </tr>

            <tr>
                <td style="padding: 8px;line-height: 20px;text-align: left;vertical-align: top;background: #D9E2EF;border: 1px solid #dddddd;" nowrap><b>CEP:</b></td>
                <td style="border: 1px solid #dddddd;padding: 8px;line-height: 20px;text-align: left;vertical-align: top;">'.$cliente_cep.'</td>
                <td style="padding: 8px;line-height: 20px;text-align: left;vertical-align: top;background: #D9E2EF;border: 1px solid #dddddd;" nowrap><b>Endereço:</b></td>
                <td style="border: 1px solid #dddddd;padding: 8px;line-height: 20px;text-align: left;vertical-align: top;" colspan="3">'.$cliente_endereco.'</td>
                <td style="padding: 8px;line-height: 20px;text-align: left;vertical-align: top;background: #D9E2EF;border: 1px solid #dddddd;" nowrap><b>Número:</b></td>
                <td style="border: 1px solid #dddddd;padding: 8px;line-height: 20px;text-align: left;vertical-align: top;">'.$cliente_numero.'</td>
            </tr>
            <tr>
                <td style="padding: 8px;line-height: 20px;text-align: left;vertical-align: top;background: #D9E2EF;border: 1px solid #dddddd;" nowrap><b>Complemento:</b> </td>
                <td style="border: 1px solid #dddddd;padding: 8px;line-height: 20px;text-align: left;vertical-align: top;">'.$cliente_complemento.'</td>
                <td style="padding: 8px;line-height: 20px;text-align: left;vertical-align: top;background: #D9E2EF;border: 1px solid #dddddd;" nowrap><b>Bairro:</b> </td>
                <td style="border: 1px solid #dddddd;padding: 8px;line-height: 20px;text-align: left;vertical-align: top;">'.$cliente_bairro.'</td>
                <td style="padding: 8px;line-height: 20px;text-align: left;vertical-align: top;background: #D9E2EF;border: 1px solid #dddddd;" nowrap><b>Cidade:</b> </td>
                <td style="border: 1px solid #dddddd;padding: 8px;line-height: 20px;text-align: left;vertical-align: top;">'.$cliente_cidade.'</td>
                <td style="padding: 8px;line-height: 20px;text-align: left;vertical-align: top;background: #D9E2EF;border: 1px solid #dddddd;" nowrap><b>UF:</b> </td>
                <td style="border: 1px solid #dddddd;padding: 8px;line-height: 20px;text-align: left;vertical-align: top;">'.$cliente_uf.'</td>
            </tr>
        </table>

        <table class="table table-bordered table-itens" style="border: 1px solid #dddddd;width: 940px;border-collapse: separate;margin: 0 auto; table-layout: fixed;margin-top: 5px;margin-bottom: 55px;font-family: arial" >
            <tr>
                <th style="font-weight: bold;background-color: #596d9b;padding: 8px;line-height: 20px;text-align: left; color: #fff;" colspan="5" >Informações do Produto/Serviço</th>
            </tr>
            <tr class="titulo_itens">
                <th style="padding:3px;border: 1px solid #dddddd;background-color:  #D9E2EF;text-align: center;" class="tac">Referencia</th>
                <th style="padding:3px;border: 1px solid #dddddd;background-color:  #D9E2EF;text-align: left;" class="tal">Descrição</th>
                <th style="padding:3px;border: 1px solid #dddddd;background-color:  #D9E2EF;text-align: center;" class="tac">Preço</th>
                <th style="padding:3px;border: 1px solid #dddddd;background-color:  #D9E2EF;text-align: center;" class="tac">Horimetro</th>
                <th style="padding:3px;border: 1px solid #dddddd;background-color:  #D9E2EF;text-align: center;" class="tac">Preventiva</th>
            </tr>
            '.$items.'
            <tr>
                <td class="tar" style="border: 1px solid #dddddd;padding:8px;background-color:  #D9E2EF;text-align: right;" colspan="4"><h4>Subtotal da Proposta</h4></td>
                <td style="text-align:center;font-size:14px;border: 1px solid #dddddd;" class="tac"><h4>R$ '.number_format($subtotal, 2, ',', '.').'</h4></td>
            </tr>
            <tr>
                <td class="tar" style="border: 1px solid #dddddd;padding:8px;background-color:  #D9E2EF;text-align: right;" colspan="4"><h4>Descontos</h4></td>
                <td style="text-align:center;font-size:14px;border: 1px solid #dddddd;" class="tac"><h4>'.$xdesconto_representante.'%</h4></td>
            </tr>
            <tr>
                <td class="tar" style="border: 1px solid #dddddd;padding:8px;background-color:  #D9E2EF;text-align: right;" colspan="4"><h4>Valor Total da Proposta</h4></td>
                <td style="text-align:center;font-size:19px;border: 1px solid #dddddd;" class="tac"><h4>R$ '.number_format($valor_contrato, 2, ',', '.').'</h4></td>
            </tr>
        </table>
        <table class="table table-bordered table-itens" style="border: 1px solid #dddddd;width: 940px;border-collapse: separate;margin: 0 auto; table-layout: fixed;margin-top: 5px;margin-bottom: 55px;font-family: arial" >
            <tr>
                <td style="text-align:center;padding:25px;">'.$botao_aprovar .' ' .$botao_reprovar .'</td>
            </tr>
        </table>
        <br />
        <br />
        Atenciosamente<br />
        <b>'.strtoupper($this->_nomeFabrica).'</b>
        <br /><br />
        ';

        
        return $mensagem;
       
    }

    public function montaCorpoPropostaReprovadaAuditoriaRepresentante($dados) {

        global $login_fabrica;
        extract($dados);
     
        $mensagem = ' Olá <b>'.$nome_representante.'</b>, Informamos que a proposta Nº '.$contrato.' foi reprovada, acesse seu ambiente e refaça a proposta.
        
        <br />
        Atenciosamente<br />
        <b>'.strtoupper($this->_nomeFabrica).'</b>
        <br /><br />
        ';

        
        return $mensagem;
       
    }

    public function montaCorpoNotificacaoPropostaAprovadaReprovadaPorCliente($dados, $tipo) {

        global $login_fabrica;
        extract($dados);
     
        $mensagem = 'Informamos que a proposta Nº '.$contrato.' foi '.$tipo.' pelo cliente.
        
        <br />
        Atenciosamente<br />
        <b>'.strtoupper($this->_nomeFabrica).'</b>
        <br /><br />
        ';
        
        return $mensagem;
       
    }

}
