<?php
require dirname(__FILE__) . '/../../dbconfig.php';
require dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
require dirname(__FILE__) . '/../../class/tdocs.class.php';
require dirname(__FILE__) . '/../funcoes.php';
require dirname(__FILE__) .'/../../class/communicator.class.php';

include_once __DIR__.'/../../classes/autoload.php';
use Posvenda\Routine;
use Posvenda\RoutineSchedule;
use Posvenda\Log;
use Posvenda\LogError;
$mailTc = new TcComm('smtp@posvenda');

$login_fabrica  = 189;

    $arquivo        = file_get_contents("/home/maicon/NotaFiscal_11.csv");
    $trata_arquivo  = str_replace("\r\n", "\n", $arquivo);
    $trata_arquivo  = str_replace("\r", "\n", $arquivo);
    $arquivo        = explode("\n", $trata_arquivo);
    $registro       = array_filter($arquivo);
    if (count($registro) > 0 && strlen($msg_erro) == 0) {
        unset($registro[0]);

        foreach ($registro as $key => $rows) {

            $linha =  explode(";", $rows);
            if (count($linha) > 7){
                $pedido = $linha[0];
                $nf     = $linha[7];
                $retorno[$pedido][$nf]["dados_pedidos"] = $linha;
            } else {
                $retorno[$pedido][$nf]["itens_pedido"][] = $linha;
            }
           
        }
    }
    $total_pedido_gerado_tc = [];

    $total_faturamento_gerado_tc = [];
    foreach ($retorno as $key => $rows) {
        foreach ($rows as $nf => $row) {

            $logErro = [];
            $dadosPosto          = "";
            $dadosTransportadora = "";
            $tipoPedido          = "";
            $deParaStatus        = "";
            $xstatus             = "";
            list($pedidoCliente,$seuPedido,$posto,$status,$data,$transportadora,$emissao,$notaFiscal,$serie,$obsPedido,$dadosEntrega,$nomeRepresentante,$emailRepresentante) = $row["dados_pedidos"];

            $dadosPosto          = checaPosto($con,$posto);
            $dadosTransportadora = checaTransportadora($con,$transportadora);
            $tipoPedido          = getTipoPedido($con,'VEN');
            $deParaStatus        = deParaStatus($status);
            $xstatus             = getStatus($con,$deParaStatus);

            /*
                $sql = "SELECT tbl_pedido.pedido,
                               tbl_faturamento_item.faturamento 
                          FROM tbl_pedido JOIN tbl_faturamento_item using(pedido) 
                          WHERE tbl_pedido.fabrica = 189 
                            AND tbl_pedido.pedido_cliente = '$pedidoCliente'";
                $res = pg_query($con, $sql);

                $pedido = pg_fetch_result($res, 0, 'pedido');
                $faturamento = pg_fetch_result($res, 0, 'faturamento');
                if (strlen($faturamento) > 0) {
                    $sql = "UPDATE tbl_faturamento SET fabrica = 0 WHERE fabrica = 189 AND faturamento = $faturamento";
                    $res = pg_query($con, $sql);
                } 

                if (strlen($pedido) > 0) {
                    $sql = "UPDATE tbl_pedido SET fabrica = 0 WHERE fabrica = 189 AND pedido = $pedido";
                    $res = pg_query($con, $sql);
                } else {

                    $sql = "UPDATE tbl_pedido SET fabrica = 0,pedido_cliente=null,seu_pedido=null WHERE fabrica = 189 AND pedido_cliente = '$pedidoCliente'";
                    $res = pg_query($con, $sql);
                    echo pg_last_error();

                }

                continue;
            */

            $dadosPedido = checaPedido($con, $pedidoCliente);
            $pedido = (isset($dadosPedido["pedido"]) && strlen($dadosPedido["pedido"]) > 0) ? $dadosPedido["pedido"] : false;

            /*
                if (checaPedido($con, $pedidoCliente)) {
                    $logErro[] = "Pedido já cadastrado - <b>PedidoCliente:</b> {$pedidoCliente} - <b>SeuPedido:</b> {$seuPedido}";
                }
            */

            if (!$dadosPosto) {
                $logErro[] = "Posto não encontrado - <b>Posto:</b> {$posto} - <b>PedidoCliente:</b> {$pedidoCliente}";
            }

            if (!$xstatus) {
                $logErro[] = "Status do pedido não encontrado - <b>Status:</b> {$status} - <b>PedidoCliente:</b> {$pedidoCliente}";
            }
               
            if (!$dadosTransportadora) {
                $logErro[] = "Transportadora não encontrada - <b>Transportadora:</b> {$transportadora} - <b>PedidoCliente:</b> {$pedidoCliente}";
            }
            
            if (!$tipoPedido) {
                $logErro[] = "Tipo de Pedido não encontrado -  <b>PedidoCliente:</b> {$pedidoCliente}";
            }

            if (count($logErro) == 0) {


                $dadosSavePedido = [
                    "pedido_cliente"    => $pedidoCliente,
                    "seu_pedido"        => $seuPedido,
                    "posto"             => $dadosPosto["posto"],
                    "tipo_pedido"       => $tipoPedido["tipo_pedido"],
                    "status"            => $xstatus["status_pedido"],
                    "transportadora"    => $dadosTransportadora["transportadora"],
                    "data"              => $emissao,
                    "cliente_nome"      => $nomeRepresentante,
                    "cliente_email"     => $emailRepresentante,
                    "obs"               => str_replace(["'","\"","/"], "", $obsPedido),
                    "visita_obs"        => str_replace(["'","\"","/"], "", $dadosEntrega),
                ];
                

                $resP = pg_query($con,"BEGIN TRANSACTION");

                if (!$pedido) {
     
                    $pedido = inserePedido($con, $dadosSavePedido);
     
                    if (isset($pedido["erro"])) {
                        $logErro[] = "Erro ao gravar pedido -  <b>DataSave:</b> ".json_encode($dadosSavePedido)." - <b>PedidoCliente:</b> {$pedidoCliente} - Erro: ".$pedido["erro"];
                    } 

                }

                if (count($logErro) == 0) {
                   
                    if (isset($row["itens_pedido"]) && count($row["itens_pedido"]) > 0) {

                        foreach ($row["itens_pedido"] as $ki => $rowsI) {

                            list($referencia,$qtde,$preco,$valor1,$valor2,$valor3,$valor4) = $rowsI;

                            $preco = empty($preco) ? 0 : (float)$preco;

                            $dadosPecas = getPecas($con, $referencia);

                            if (!$dadosPecas) {
                                $logErro[] = "Peça não encontrada - <b>Referência:</b> {$referencia} - <b>PedidoTc:</b> {$pedido}- <b>PedidoCliente:</b> {$pedidoCliente}";
                            }

                            $checaItem = checaPedidoItem($con, $pedido, $dadosPecas["peca"], $qtde);

                            if ($checaItem) {
                                continue;
                            }

                            if (count($logErro) == 0) {
                                $dadosSavePedidoItem = [
                                    "pedido"            => $pedido,
                                    "peca"              => $dadosPecas["peca"],
                                    "preco"             => $preco,
                                    "qtde"              => (int)$qtde,
                                    "qtde_faturada"     => (int)$qtde,
                                ];

                                $pedidoItem = inserePedidoItem($con, $dadosSavePedidoItem);

                                if (isset($pedidoItem["erro"]) && strlen($pedidoItem["erro"]) > 0) {
                                    $logErro[] = "Erro ao gravar item do pedido -  <b>DataSave:</b> ".json_encode($dadosSavePedidoItem)." - <b>Pedido: </b> {$pedido} - <b>PedidoCliente:</b> {$pedidoCliente} - Erro: ".$pedidoItem["erro"];
                                } 
                            }

                        }
                    }

                }//fecha item pedidos


                if (count($logErro) > 0) {
                    $resP = pg_query('ROLLBACK;');
                    $log_erro[] = $logErro;

                } else {
                    $resP = pg_query('COMMIT;');
                    $total_pedido_gerado_tc[$pedido] = 1;
                    $logSucesso[$pedido]["pedido"] = "Pedido criado com sucesso - <b>Pedido:</b> {$pedido} - <b>PedidoCliente:</b> {$pedidoCliente}";
                }

                if ($argv[1] == "faturado" && count($logErro) == 0) {


                    if (checaFaturamento($pedido, $emissao, $notaFiscal, $dadosPosto["posto"])) {
                        continue;
                    }


                    $resPP = pg_query($con,"BEGIN TRANSACTION");

                    $dadosSaveFaturamento = [
                        "pedido"        => $pedido,
                        "posto"         => $dadosPosto["posto"],
                        "saida"         => $emissao,
                        "emissao"       => $emissao,
                        "nota_fiscal"   => $notaFiscal,
                        "transp"        => $dadosTransportadora["nome"],
                        "total_nota"    => 0,

                    ];

                    $faturamento = insereFaturamento($con,$dadosSaveFaturamento);

                    if (isset($faturamento["erro"])) {
                        $logErro[] = "Erro ao gravar faturamento -  <b>DataSave:</b> ".json_encode($dadosSaveFaturamento)." - <b>Pedido: </b> {$pedido} - <b>PedidoCliente:</b> {$pedidoCliente}  - Erro: ".$faturamento["erro"];
                    }

                    if (count($logErro) == 0) {
                        

                         if (isset($row["itens_pedido"]) && count($row["itens_pedido"]) > 0) {
                            $totalPedido = 0;
                            foreach ($row["itens_pedido"] as $ki => $rowsI) {

                                list($referencia,$qtde,$preco,$valor1,$valor2,$valor3,$valor4) = $rowsI;

                                $preco = empty($preco) ? 0 : (float)$preco;

                                $dadosPecas = getPecas($con, $referencia);

                                $dadosPedidosItens = getPedidoItens($con,$pedido,$dadosPecas["peca"]);


                                if (count($logErro) == 0) {
                                    $dadosSaveFatItem = [
                                        "faturamento"       => $faturamento,
                                        "peca"              => $dadosPedidosItens["peca"],
                                        "preco"             => $dadosPedidosItens["preco"],
                                        "qtde"              => $dadosPedidosItens["qtde"],
                                        "pedido_item"       => $dadosPedidosItens["pedido_item"],
                                        "pedido"            => $dadosPedidosItens["pedido"],
                                        "aliq_icms"          => 0,
                                        "valor_icms"         => 0,
                                        "aliq_ipi"           => 0,
                                        "valor_ipi"          => 0,
                                    ];

                                    $fatItem = insereFatItem($con,$faturamento, $dadosSaveFatItem);

                                    if (isset($fatItem["erro"])) {
                                        $logErro[] = "Erro ao gravar item do faturamento -  <b>DataSave:</b> ".json_encode($dadosSaveFatItem)." - <b>Faturamento: </b> {$faturamento}- <b>Pedido: </b> {$pedido} - <b>PedidoCliente:</b> {$pedidoCliente} - Erro: ".$fatItem["erro"];
                                    }
                                    $totalPedido += $preco;
                                }
                            }

                        }
                    
                    }

                    if (count($logErro) > 0) {
                        $resPP = pg_query('ROLLBACK;');
                        $log_erro[] = $logErro;
                    } else {

                        $atualizaTotal    = "UPDATE tbl_faturamento SET total_nota = '{$totalPedido}' WHERE fabrica={$login_fabrica} AND faturamento = {$faturamento}";
                        $resAtualizaTotal = pg_query($con, $atualizaTotal);
                        
                        $resPP = pg_query('COMMIT;');
                        $total_faturamento_gerado_tc[$faturamento] = 1;
                        $logSucesso[$pedido]["faturamento"][$faturamento] = "Faturamento criado com sucesso - <b>Faturamento:</b> {$faturamento} -  <b>Pedido:</b> {$pedido} - <b>PedidoCliente:</b> {$pedidoCliente}";
                    }
                }//fecha argv
            } else {
                $log_erro[] = $logErro;
            }
        }//fecha foreach
    }//fecha foreach

    if (count($log_erro) > 0) {
        $res = $mailTc->sendMail(
            'felipe.marttos@telecontrol.com.br;luis.carlos@telecontrol.com.br',
            "Log de erro - Carga de Pedido / Faturado Viapol - " . date("d/m/Y H:i:s"),
            montaEmailErro($log_erro, 'Erro'),
            "noreply@telecontrol.com.br"
        );
    }


    if (count($logSucesso) > 0) {
        $res = $mailTc->sendMail(
            'felipe.marttos@telecontrol.com.br;luis.carlos@telecontrol.com.br',
            "Log de sucesso - Carga de Pedido / Faturado Viapol - " . date("d/m/Y H:i:s"),
            montaEmail($logSucesso, 'Sucesso'),
            "noreply@telecontrol.com.br"
        );
    }

    function checaPedidoItem($con, $pedido, $peca, $qtde)
    {
        global  $login_fabrica;

        if (strlen($pedido) == 0) {
            return false;
        }

        $sql = "SELECT * FROM tbl_pedido_item WHERE peca={$peca} AND qtde={$qtde} AND pedido={$pedido}";
        $res = pg_query($con, $sql);

        if (pg_last_error() || pg_num_rows($res) == 0) {
            return false;
        }

        return true;
    }

    function checaFaturamento($con, $pedido, $emissao, $nf, $posto)
    {
        global  $login_fabrica;

        if (strlen($pedido) == 0) {
            return false;
        }

        $sql = "SELECT faturamento 
                  FROM tbl_faturamento 
                 WHERE fabrica={$login_fabrica} 
                   AND pedido={$pedido} 
                   AND emissao='{$emissao}' 
                   AND posto={$posto}
                   AND nota_fiscal='{$nf}'";
        $res = pg_query($con, $sql);

        if (pg_last_error() || pg_num_rows($res) == 0) {
            return false;
        }

        return true;
    
    }

    function inserePedido($con, $dadosPedidos)
    {
        global  $login_fabrica;

        $campo = ",pedido_cliente";
        $valor = ",'".$dadosPedidos["pedido_cliente"]."'";
        $campo .= ",seu_pedido";
        $valor .= ",'".$dadosPedidos["seu_pedido"]."'";
        $campo .= ",posto";
        $valor .= ",".$dadosPedidos["posto"];
        $campo .= ",tipo_pedido";
        $valor .= ",".$dadosPedidos["tipo_pedido"];
        $campo .= ",status_pedido";
        $valor .= ",".$dadosPedidos["status"];
        $campo .= ",transportadora";
        $valor .= ",".$dadosPedidos["transportadora"];
        $campo .= ",data";
        $valor .= ",'".$dadosPedidos["data"]."'";
       
        if ($dadosPedidos["cliente_nome"]) {
            $campo .= ",cliente_nome";
            $valor .= ",'".$dadosPedidos["cliente_nome"]."'";
        }
        if ($dadosPedidos["cliente_email"]) {
            $campo .= ",cliente_email";
            $valor .= ",'".$dadosPedidos["cliente_email"]."'";
        }
        if ($dadosPedidos["obs"]) {
            $campo .= ",obs";
            $valor .= ",'".  pg_escape_string($dadosPedidos["obs"]) ."'";
        }
        if ($dadosPedidos["visita_obs"]) {
            $campo .= ",visita_obs";
            $valor .= ",'". pg_escape_string($dadosPedidos["visita_obs"]) ."'";
        }
        

        $sql = "INSERT INTO tbl_pedido (fabrica {$campo}) VALUES ({$login_fabrica} {$valor}) RETURNING pedido";
        $res = pg_query($con, $sql);

        if (pg_last_error()) {
            return ["erro" => pg_last_error()];
        }

        return pg_fetch_result($res, 0, 'pedido');

    }

    function inserePedidoItem($con, $dados)
    {
        global $login_fabrica;

        $sql = "INSERT INTO tbl_pedido_item (pedido, peca, preco, qtde, qtde_faturada) VALUES (".$dados["pedido"].",".$dados["peca"].",'".$dados["preco"]."',".$dados["qtde"].",".$dados["qtde_faturada"].") RETURNING pedido_item";
        $res = pg_query($con, $sql);

        if (pg_last_error()) {
            return ["erro" => pg_last_error()];
        }

        return pg_fetch_result($res, 0, 'pedido_item');

    }

    function insereFaturamento($con, $dados)
    {
        global $login_fabrica;

        $sql = "INSERT INTO tbl_faturamento (fabrica, pedido, posto, saida, emissao, nota_fiscal, transp, total_nota) VALUES ({$login_fabrica},".$dados["pedido"].",".$dados["posto"].",'".$dados["saida"]."','".$dados["emissao"]."','".$dados["nota_fiscal"]."','".$dados["transp"]."','".$dados["total_nota"]."') RETURNING faturamento";
        $res = pg_query($con, $sql);

        if (pg_last_error()) {
            return ["erro" => pg_last_error()];
        }

        return pg_fetch_result($res, 0, 'faturamento');

    }

    function insereFatItem($con,$faturamento, $dados)
    {
        global $login_fabrica;

        $sql = "INSERT INTO tbl_faturamento_item (
            faturamento, 
            peca, 
            preco, 
            qtde, 
            pedido_item, 
            pedido,
            aliq_ipi,
            aliq_icms,
            valor_ipi,
            valor_icms
        ) VALUES (
            ".$faturamento.",
            ".$dados["peca"].",
            '".$dados["preco"]."',
            ".$dados["qtde"].",
            ".$dados["pedido_item"].",
            ".$dados["pedido"].",
            '".$dados["aliq_ipi"]."',
            '".$dados["aliq_icms"]."',
            '".$dados["valor_ipi"]."',
            '".$dados["valor_icms"]."'
        )  RETURNING faturamento_item";
        $res = pg_query($con, $sql);

        if (pg_last_error()) {
            return ["erro" => pg_last_error()];
        }

        return pg_fetch_result($res, 0, 'faturamento_item');

    }

    function deParaStatus($statusRequest)
     {
 
        /*  status viapol
         0 - Bloqueado Financeiro
         1 - Bloqueado Vendas
         2 - Novo cliente
         3 - Analise Cadastral
         4 - Enviado ERP  
         5 - Em Aberto
         6 - Faturado Parcial
         7 - Faturado Total 
         8 - Cancelado
        */
         switch ($statusRequest) {
                 case '0':
                         $status_pedido_tc = "BLOQUEADO_FINANCEIRO";
                         break;
                 case '1':
                         $status_pedido_tc = "BLOQUEADO_VENDAS";
                         break;
                 case '2':
                         $status_pedido_tc = "NOVO_CLIENTE";
                         break;
                 case '3':
                         $status_pedido_tc = "ANALISE_CADASTRAL";
                         break;
                 case '4':
                         $status_pedido_tc = "ENVIADO_ERP";
                         break;
                 case '5':
                         $status_pedido_tc = "EM_ABERTO";
                         break;
                 case '6':
                         $status_pedido_tc = "FATURADO_PARCIAL";
                         break;
                 case '7':
                         $status_pedido_tc = "FATURADO_INTEGRAL";
                         break;
                 case '8':
                         $status_pedido_tc = "CANCELADO";
                         break;
         }
         return $status_pedido_tc;
     }

    function checaPedido($con,$pedidoCliente)
    {
        global  $login_fabrica;

        if (strlen($pedidoCliente) == 0) {
            return false;
        }

        $sql = "SELECT pedido FROM tbl_pedido WHERE fabrica={$login_fabrica} AND pedido_cliente='{$pedidoCliente}'";
        $res = pg_query($con, $sql);

        if (pg_last_error() || pg_num_rows($res) == 0) {
            return false;
        }

        return pg_fetch_assoc($res);
    }

    function getPecas($con, $referencia)
    {
        global $login_fabrica;

        if (strlen($referencia) == 0) {
            return false;
        }

        $sql = "SELECT peca FROM tbl_peca WHERE fabrica={$login_fabrica} AND referencia='{$referencia}'";
        $res = pg_query($con, $sql);

        if (pg_last_error() || pg_num_rows($res) == 0) {
            return false;
        }

        return pg_fetch_assoc($res);

    }

    function getPedidoItens($con,$pedido,$peca)
    {
        global $login_fabrica;

        if (strlen($pedido) == 0) {
            return false;
        }

        $sql = "SELECT * FROM tbl_pedido_item WHERE peca={$peca} AND  pedido={$pedido}";
        $res = pg_query($con, $sql);

        if (pg_last_error() || pg_num_rows($res) == 0) {
            return false;
        }

        return pg_fetch_assoc($res);

    }

    function checaPosto($con,$codigoPosto)
    {
        global $login_fabrica;

        if (strlen($codigoPosto) == 0) {
            return false;
        }

        $sql = "SELECT posto FROM tbl_posto_fabrica WHERE fabrica={$login_fabrica} AND codigo_posto='{$codigoPosto}'";
        $res = pg_query($con, $sql);

        if (pg_last_error() || pg_num_rows($res) == 0) {
            return false;
        }

        return pg_fetch_assoc($res);

    }

    function checaTransportadora($con,$cnpj)
    {
        global $login_fabrica;

        if (strlen($cnpj) == 0) {
            return false;
        }

        $sql = "SELECT transportadora,nome FROM tbl_transportadora WHERE cnpj='{$cnpj}'";
        $res = pg_query($con, $sql);

        if (pg_last_error() || pg_num_rows($res) == 0) {
            return false;
        }

        return pg_fetch_assoc($res);
    }

    function getTipoPedido($con,$codigo)
    {
        global $login_fabrica;

        if (strlen($codigo) == 0) {
            return false;
        }

        $sql = "SELECT tipo_pedido FROM tbl_tipo_pedido WHERE fabrica={$login_fabrica} AND codigo='{$codigo}'";
        $res = pg_query($con, $sql);
        if (pg_last_error() || pg_num_rows($res) == 0) {
            return false;
        }

        return pg_fetch_assoc($res);
    }

    function getStatus($con, $codigo)
    {
        global $login_fabrica;

        if (strlen($codigo) == 0) {
            return false;
        }

        $sql = "SELECT status_pedido FROM tbl_status_pedido WHERE codigo_status='{$codigo}'";
        $res = pg_query($con, $sql);
        if (pg_last_error() || pg_num_rows($res) == 0) {
            return false;
        }

        return pg_fetch_assoc($res);
    }

    function montaEmailErro($log, $tipo)
    {
        if ($tipo == 'Sucesso') {
            $cor = "green";
        } else {
            $cor = "#d90000";
        }
       
        $body = '<table>
        <tr>
            <td style="background:'.$cor.';color:#ffffff;font-family: arial;padding:10px"><b>Log de '.$tipo.'</b></td>
        </tr>
        ';
        $i = 0;

        foreach ($log as $key => $value) {
            $cor =  ($i % 2 == 0) ? "#eeeeee" : "#ffffff";        
            $body .= '
            <tr style="background:'.$cor.'">
                <td style="font-family: arial;padding:10px">'.implode("<br>", $value).'</td>
            </tr>
            ';
            $i++;
        }

        $body .= '</table>';

        return $body;

    }

    function montaEmail($log)
    {


        global $retorno, $total_pedido_gerado_tc, $total_faturamento_gerado_tc;
        $cor = "green";
       
        $body = '<table>
        <tr>
            <td colspan="3" style="background:'.$cor.';color:#ffffff;font-family: arial;padding:10px"><b>Log de Sucesso</b></td>
        </tr>
        <tr>
            <td colspan="3"  style="background:#333;color:#ffffff;font-family: arial;padding:10px"><b>Pedido</b></td>
        </tr>
        ';
        $i = 0;

        foreach ($log as $key => $value) {
            $cor =  ($i % 2 == 0) ? "#eeeeee" : "#ffffff";        
            $body .= '
            <tr style="background:'.$cor.'">
                <td  colspan="3" style="font-family: arial;padding:10px">'.$value["pedido"].'</td>
            </tr>
            ';
            $i++;
        }
        $body .= '
        <tr>
            <td colspan="3" style="background:blue;color:#ffffff;font-family: arial;padding:10px"><b>TOTAL PEDIDO PLANILHA</b>: '.count($retorno).'</td>
        </tr>';

        $body .= '</table>';

        return $body;

    }
