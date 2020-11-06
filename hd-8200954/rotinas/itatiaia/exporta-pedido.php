<?php

require dirname(__FILE__) . '/../../dbconfig.php';
require dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
require dirname(__FILE__) . '/../../funcoes.php';
require dirname(__FILE__) .'/../../class/communicator.class.php';

include_once __DIR__.'/../../classes/autoload.php';
use Posvenda\Log;
use Posvenda\LogError;
$mailTc = new TcComm('smtp@posvenda');

$login_fabrica  = 183;

$fabrica_nome = "itatiaia";
define('APP', 'Exporta Pedido - '.$fabrica_nome);

$vet['fabrica'] = 'itatiaia';
$vet['tipo']    = 'exporta-pedido';
$vet['dest']    = array('ronald.santos@telecontrol.com.br');
$vet['log']     = 1;

function exportar_dados($dados) {
    global $_serverEnvironment;
    
    if ($_serverEnvironment == "development") {
        $curlUrl = "https://piqas.cozinhasitatiaia.com.br/RESTAdapter/ReceberOrdemVenda";
        $curlPass = "aXRhYWJhcDpBYmFwMjAxOA==";
    } else {
        $curlUrl = "https://pi.cozinhasitatiaia.com.br/RESTAdapter/ReceberOrdemVenda";
        $curlPass = "UElTVVBFUjppdGExMjM0NQ==";
    }
    
    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => $curlUrl,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 90,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_POSTFIELDS => json_encode($dados),
        CURLOPT_HTTPHEADER => array(
            "Authorization: Basic {$curlPass}",
            "Content-Type: application/json"
        ),
    ));
    $response = curl_exec($curl);
    $err = curl_error($curl);
    $response = json_decode($response,1);

    if (!empty($response)){
        $error = $response["MT_RecOrdemVenda_Resp"]["Registro"]["StatOrdemVenda"];
        if (!empty($error)){
            echo json_encode(['message' => 'error']);
        }else{
            echo json_encode(['message' => 'success']);
        }
    }
   
    curl_close($curl);
    return $response;
}

//try {


    $sql_pedidos = "
        SELECT DISTINCT 
            tbl_pedido.fabrica,
            tbl_tipo_pedido.codigo AS tipo_pedido,
            tbl_linha.codigo_linha,
            tbl_posto_fabrica.codigo_posto,
            tbl_pedido.pedido,
            t_venda.sigla_tabela AS tabela_venda,
            t_garantia.sigla_tabela AS tabela_garantia,
            tbl_os.sua_os,
            tbl_pedido.seu_pedido,
            tbl_tipo_posto.codigo AS codigo_tipo_posto,
            to_char(tbl_pedido.data,'DDMMYYYY') AS data_pedido,
            tbl_condicao.codigo_condicao AS condicao_pagamento,
            tbl_fabrica.parametros_adicionais,
            tbl_pedido.tipo_frete,
            tbl_pedido.filial_posto,
            tbl_pedido.obs
        FROM tbl_pedido
        JOIN tbl_fabrica ON tbl_fabrica.fabrica = tbl_pedido.fabrica
        JOIN tbl_tipo_pedido ON tbl_tipo_pedido.tipo_pedido = tbl_pedido.tipo_pedido AND tbl_tipo_pedido.fabrica = {$login_fabrica}
        LEFT JOIN tbl_condicao ON tbl_condicao.condicao = tbl_pedido.condicao AND tbl_condicao.fabrica = {$login_fabrica}
        JOIN tbl_linha ON tbl_linha.linha = tbl_pedido.linha AND tbl_linha.fabrica = {$login_fabrica}
        JOIN tbl_posto ON tbl_posto.posto = tbl_pedido.posto
        JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto AND tbl_posto_fabrica.fabrica = {$login_fabrica}
        JOIN tbl_tipo_posto ON tbl_tipo_posto.tipo_posto = tbl_posto_fabrica.tipo_posto AND tbl_tipo_posto.fabrica = {$login_fabrica}
        JOIN tbl_posto_linha ON tbl_posto_linha.posto = tbl_posto.posto AND tbl_posto_linha.linha = tbl_linha.linha
        LEFT JOIN tbl_tabela AS t_venda ON t_venda.tabela = tbl_posto_linha.tabela_posto AND t_venda.fabrica = {$login_fabrica}
        LEFT JOIN tbl_tabela AS t_garantia ON t_garantia.tabela = tbl_posto_linha.tabela AND t_garantia.fabrica = {$login_fabrica}
        LEFT JOIN tbl_os_item ON tbl_os_item.pedido = tbl_pedido.pedido AND tbl_os_item.fabrica_i = {$login_fabrica}
        LEFT JOIN tbl_os_produto ON tbl_os_produto.os_produto = tbl_os_item.os_produto
        LEFT JOIN tbl_os ON tbl_os.os = tbl_os_produto.os AND tbl_os.fabrica = {$login_fabrica}
        WHERE tbl_pedido.fabrica = {$login_fabrica}
        AND tbl_pedido.finalizado IS NOT NULL
	    AND tbl_pedido.exportado IS NULL
        AND tbl_pedido.status_pedido = 1";
    $res_pedido = pg_query($con, $sql_pedidos);

    if (pg_num_rows($res_pedido) > 0) {
	    $log_erro = [];
        for ($i = 0; $i < pg_num_rows($res_pedido); $i++) {
            $error = false;
        
            $array_dados = array();
            $array_itens = array();
            
            $codigo_tipo_posto      = pg_fetch_result($res_pedido, $i, "codigo_tipo_posto");
            $filial_posto           = pg_fetch_result($res_pedido, $i, "filial_posto");
            $tipo_pedido            = pg_fetch_result($res_pedido, $i, "tipo_pedido");
            $codigo_linha           = pg_fetch_result($res_pedido, $i, "codigo_linha");
            $codigo_posto           = pg_fetch_result($res_pedido, $i, "codigo_posto");
            $pedido                 = pg_fetch_result($res_pedido, $i, "pedido");
            $sua_os                 = pg_fetch_result($res_pedido, $i, "sua_os");
            $obs                    = pg_fetch_result($res_pedido, $i, "obs");
            $data_pedido            = pg_fetch_result($res_pedido, $i, "data_pedido");
            $condicao_pagamento     = pg_fetch_result($res_pedido, $i, "condicao_pagamento");
            $parametros_adicionais  = pg_fetch_result($res_pedido, $i, "parametros_adicionais");
            $parametros_adicionais  = json_decode($parametros_adicionais, true);
            $tabela_garantia        = pg_fetch_result($res_pedido, $i, "tabela_garantia");
            $tabela_venda           = pg_fetch_result($res_pedido, $i, "tabela_venda");
            $seu_pedido             = pg_fetch_result($res_pedido, $i, "seu_pedido");
            $tipo_frete             = pg_fetch_result($res_pedido, $i, "tipo_frete");

            if (strlen(trim($seu_pedido)) > 0){
                if (strlen(trim($obs)) > 0){
                    $obs .= " - Número do Pedido Representante: $seu_pedido ";
                }else{
                    $obs .= " Número do Pedido Representante: $seu_pedido ";
                }
            }
            
            if (!empty($filial_posto) AND in_array($codigo_tipo_posto, array("Rev", "Rep"))){
                $sql_posto_exporta = "SELECT tbl_posto_fabrica.codigo_posto FROM tbl_posto_fabrica WHERE tbl_posto_fabrica.fabrica = {$login_fabrica} AND tbl_posto_fabrica.posto = {$filial_posto}";
                $res_posto_exporta = pg_query($con, $sql_posto_exporta);

                if (pg_num_rows($res_posto_exporta) > 0){
                    $codigo_posto = pg_fetch_result($res_posto_exporta, 0, "codigo_posto");
                }
            }

            if (!in_array($codigo_linha, array("S1", "S2", "S3"))){
                $log_erro[$pedido] = "LINHA DO PEDIDO NÃO ENCONTRADA NO TELECONTROL - PEDIDO: $pedido - LINHA: $codigo_linha";
                $error = true;
            }

            if ($tipo_pedido == "Gar"){
                $tipo_doc_venda = "ZSSS";
                $tabela = $tabela_garantia; 
            }else{
                $tabela = $tabela_venda;
                $tipo_doc_venda = "ZVAS";
            }

            if ($tipo_doc_venda == "ZVAS"){
                $motivo_ordem = "010";
            }else{
                $motivo_ordem = "017";
            }
            
            $incoterms1 = strtoupper($tipo_frete);
            
            if ($incoterms1 == "FOB"){
                $incoterms2 = "FOB – Franco a bordo";
            }else{
                $incoterms1 = "CIF";
                $incoterms2 = "CIF – CUSTO, SEGURO e FRETE";
            }

            if (!empty($sua_os)){
                $xpedido = $pedido.'-'.$sua_os;
            }else{
                $xpedido = $pedido;
            }
            $obs = utf8_encode($obs); 
            $array_dados["Registro"][] = array(
                "TipoDocuVenda" => $tipo_doc_venda,
                "OrgaVendas" => 1001,
                "CanaDistribuicao" => "C3",
                "SetoAtividade" => $codigo_linha,
                "EmisOrdem" => $codigo_posto,
                "NrPediCliente" => $xpedido,
                "DtPedido" => $data_pedido,
                "CondPagto" => $condicao_pagamento,
                "MotiOrdem" => $motivo_ordem,
                "Incoterms1" => $incoterms1,
                "Incoterms2" => $incoterms2,
                "Observacao" => $obs,
                "ListaPreco" => $tabela
            );
            
            $sql_itens = "
                SELECT DISTINCT
                    tbl_pedido_item.pedido,
                    tbl_pedido_item.pedido_item,
                    tbl_peca.referencia AS referencia_peca,
                    tbl_pedido_item.qtde,
                    tbl_peca.produto_acabado,
                    tbl_causa_defeito.codigo AS causa_defeito
                FROM tbl_pedido_item
                JOIN tbl_peca ON tbl_peca.peca = tbl_pedido_item.peca AND tbl_peca.fabrica = {$login_fabrica}
                JOIN tbl_causa_defeito ON tbl_causa_defeito.causa_defeito = tbl_pedido_item.causa_defeito AND tbl_causa_defeito.fabrica = {$login_fabrica}
                WHERE tbl_pedido_item.pedido = {$pedido} AND (tbl_pedido_item.qtde_cancelada = 0 OR tbl_pedido_item.qtde_cancelada IS NULL)";
            $res_itens = pg_query($con, $sql_itens);
            
            if (pg_num_rows($res_itens) > 0){
                $contador_campo = 1;
                for ($x=0; $x < pg_num_rows($res_itens); $x++) { 
                    $pedido_item     = pg_fetch_result($res_itens, $x, "pedido_item");
                    $referencia_peca = pg_fetch_result($res_itens, $x, "referencia_peca");
                    $qtde            = pg_fetch_result($res_itens, $x, "qtde");
                    $causa_defeito   = pg_fetch_result($res_itens, $x, "causa_defeito");
                    $produto_acabado = pg_fetch_result($res_itens, $x, "produto_acabado");

                    if ($produto_acabado){
                        $causa_defeito = "024";
                    }
                    $campo_item_itatiaia = ($contador_campo * 100); //Solicitado pela Sandra (Itatiaia) 19/02/2020 sempre deve ser um sequencial de 100 em 100
                    $array_itens[] = array(
                        "Item" => $campo_item_itatiaia,
                        "CodiMaterial" => $referencia_peca,
                        "Qtde" => $qtde,
                        "CodiUtilizacao" => $causa_defeito
                    );
                    $contador_campo++;
                }
                $array_dados["Registro"][0]["ItemOrdemVenda"] = $array_itens;
            }else{
                $log_erro[$pedido] = "PEDIDO SEM ITENS NO TELECONTROL.";
                $error = true;
            }
            
            if ($error !== true){
                $retorno = exportar_dados($array_dados);
                
                $numero_pedido_sap = $retorno["MT_RecOrdemVenda_Resp"]["Registro"]["NrOrdemVendaSAP"];

                if (!empty($numero_pedido_sap)){
                    $up = "UPDATE tbl_pedido SET pedido_cliente = {$numero_pedido_sap}, status_pedido = 2, exportado = now() WHERE pedido = {$pedido}";
                    $res = pg_query($con, $up);
                }else{
                    $requisicao = json_encode($array_dados);
                    $msg_erro = $retorno["MT_RecOrdemVenda_Resp"]["Registro"]["StatOrdemVenda"];
                    $log_erro[$pedido] = "{$msg_erro}";
                    $error = true;
                }
            }
            
            if ($error === true){
                $manda_email = true;
            }
        }
    }


    if ($manda_email === true){
        $res = $mailTc->sendMail(
            'ronald.santos@telecontrol.com.br;guilherme.monteiro@telecontrol.com.br;luis.carlos@telecontrol.com.br;rafael.santos@cozinhasitatiaia.com.br',
            "Log de erro - Log Erro Exportação Pedido - " . date("d/m/Y H:i:s"),
            montaEmail($log_erro, 'Erro'),
            "noreply@telecontrol.com.br"
        );
    }

    function montaEmail($log, $tipo){
        if ($tipo == 'Sucesso') {
            $cor = "green";
        } else {
            $cor = "#d90000";
        }
       
        $body = '<table>
        <tr>
            <td colspan="2" style="background:'.$cor.';color:#ffffff;font-family: arial;padding:10px"><b>Log de '.$tipo.'</b></td>
        </tr>
';
	if(strtolower($tipo) == "erro"){
		$body .= "<tr><td style='background-color:#CCCCCC;color:#000000;'>LOG PEDIDO</td><td style='background-color:#CCCCCC;color:#000000'>ERRO NA INTEGRAÇÃO ENTRE SAP/TELECONTROL MSG</td></tr>";
	}

	$i = 0;

	if($tipo == "erro"){
		$data = date('Ymd_His');
		$fp = fopen("/tmp/itatiaia/logs/exporta-pedido-erro_{$data}.log","w");
		fwrite($fp,"LOG PEDIDO\tERRO NA INTEGRAÇÃO ENTRE SAP/TELECONTROL MSG");
	}

        foreach ($log as $key => $value) {
            $cor =  ($i % 2 == 0) ? "#eeeeee" : "#ffffff";        
	    if(strtolower($tipo) == "erro"){
		    $body .= '<tr style="background:'.$cor.'">
			    	<td style="font-family: arial;padding:10px">'.$key.'</td>
	        	         <td style="font-family: arial;padding:10px">'.$value.'</td>
	             	  </tr>';
	    }else{ 
	   	$body .= '<tr style="background:'.$cor.'">
                		<td style="font-family: arial;padding:10px">'.$value.'</td>
            		  </tr>';
	    }

	    $i++;

	    if($tipo == "erro"){
		fwrite($fp,$key."\t".$value."\n");
	    }
	}

	if($tipo == "erro"){
		fclose($fp);
	}

        $body .= '</table>';
        return $body;
    }

// } catch (Exception $e) {
//     $msg = 'Script: '.__FILE__.'<br />Erro na linha ' . $e->getLine() . ':<br />' . $e->getMessage();
// 	echo $msg;
//     Log::envia_email($vet, APP, $msg);
// }

