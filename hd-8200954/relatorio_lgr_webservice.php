<?

ini_set('default_socket_timeout', 5000);

$areaAdmin = preg_match('/\/admin\//',$_SERVER['PHP_SELF']) > 0 ? true : false;

include_once "dbconfig.php";
include_once "includes/dbconnect-inc.php";
require_once "classes/Posvenda/Fabricas/_169/Extrato.php";

/*ini_set("display_errors", 1);
error_reporting(E_ALL);*/

if ($areaAdmin === true) {
    $admin_privilegios = "call_center";
    include __DIR__.'/admin/autentica_admin.php';
} else {
    include __DIR__.'/autentica_usuario.php';
    include __DIR__."/class/tdocs.class.php";
}

include_once 'helpdesk/mlg_funciones.php';
include __DIR__.'/funcoes.php';

$array_estados = $array_estados();
$btn_acao = $_REQUEST['btn_acao'];
$posto = $_REQUEST['posto'];

if (empty($posto) && !empty($_REQUEST['login_posto'])) {
    $posto = $_REQUEST['login_posto'];
}

unset($msg_erro);

if ($btn_acao == "pesquisar_posto" || !empty($posto)) {
    $codigo_posto = $_REQUEST['codigo_posto'];
    $descricao_posto = $_REQUEST['descricao_posto'];
    $wherePst = "";

    if (!empty($codigo_posto)) {
        $wherePst = "AND codigo_posto = '{$codigo_posto}'";
    } else if (!empty($posto)) {
        $wherePst = "AND posto = {$posto}";
    }
    
    if (!empty($wherePst)) {
    	$sqlPst = "SELECT pf.posto, pf.codigo_posto, p.nome FROM tbl_posto_fabrica pf JOIN tbl_posto p USING(posto) WHERE fabrica = {$login_fabrica} {$wherePst};";
    	$resPst = pg_query($con,$sqlPst);

    	if (pg_num_rows($resPst) > 0) {
    	    $login_posto = pg_fetch_result($resPst, 0, "posto");
    	    $codigo_posto = pg_fetch_result($resPst, 0, "codigo_posto");
    	    $descricao_posto = pg_fetch_result($resPst, 0, "nome");
    	} else {
    	    $msg_erro["msg"][] = "Posto não encontrado";
    	    $msg_erro["campos"][] = "posto";
    	}
    }   
}

if (isset($_REQUEST['ajax_dados_origem'])) {
    $ajax_cnpj_origem = $_REQUEST['cnpj_origem'];
    $retorno = array();
    if (!empty($ajax_cnpj_origem)) {
        $sql = "
            SELECT
                fn_retira_especiais(f.nome) AS nome,
                fn_retira_especiais(f.fantasia) AS fantasia,
                fn_retira_especiais(f.endereco) AS endereco,
                f.numero,
                fn_retira_especiais(f.bairro) AS bairro,
                fn_retira_especiais(f.complemento) AS complemento,
                f.cnpj,
                f.ie,
                f.cep,
                fn_retira_especiais(c.nome) AS cidade,
                f.estado
            FROM tbl_filial f
            JOIN tbl_cidade c USING(cidade)
            WHERE f.fabrica = {$login_fabrica}
            AND f.cnpj = '{$ajax_cnpj_origem}';
        ";
        $res = pg_query($con, $sql);
        if (pg_num_rows($res) > 0) {
            $dadosFilial = pg_fetch_all($res);
            $retorno = $dadosFilial[0];
        } else {
            $retorno = array("erro" => utf8_encode("Filial não encontrada"));
        }
    } else {
        $retorno = array("erro" => utf8_encode("CNPJ de origem não identificado"));
    }
    echo json_encode($retorno);
    exit;
}

if ($_POST['gerar_excel']) {

    if ($_serverEnvironment == 'development') {
        $urlWSDL = "http://ws.carrieronline.com.br/QA6/PSA_WebService/PSA.asmx?WSDL";
    } else {
        $urlWSDL = "http://ws.carrieronline.com.br/wsPSATeleControl/PSA.asmx?WSDL";
    }

    $urlWSDLDanfe = "http://ws.carrieronline.com.br/wsDownloadNFe/DownloadNFe.asmx?WSDL";

    $classExtratoFabrica = new ExtratoMideaCarrier($login_fabrica);
    $os_posto = $classExtratoFabrica->getOsPostoLgr($login_posto);

    $erro_comm = false;

    try {

        $client = new SoapClient($urlWSDL, array(
            'trace' => TRUE,
            'connection_timeout' => 5000,
            'cache_wsdl' => WSDL_CACHE_NONE,
            'keep_alive' => FALSE
        ));

        $clientDanfe = new SoapClient($urlWSDLDanfe, array('trace' => TRUE));

        $posto          = $os_posto["posto"];
        $codigo_posto   = $os_posto["codigo_posto"];
        $centro_custo   = $os_posto["centro_custo"];
        $conta_contabil = $os_posto["conta_contabil"];
        $conta_contabil = str_pad($conta_contabil, 10, "0", STR_PAD_LEFT);

        $params = new SoapVar("
            <ns1:xmlDoc>
                <criterios>
                    <PV_ARBPL>{$codigo_posto}</PV_ARBPL>
                    <PV_CLASSEA>X</PV_CLASSEA>
                </criterios>
            </ns1:xmlDoc>
        ", XSD_ANYXML);

        $array_params = array('xmlDoc' => $params);
        $result = $client->ListaPecasNaoDevolvidas($array_params);
        $dados_xml = $result->ListaPecasNaoDevolvidasResult->any;
        $xml = simplexml_load_string($dados_xml);
        $xml = json_decode(json_encode((array)$xml), TRUE);

        $sqlProdutos = "
            SELECT DISTINCT
                o.os AS QMNUM,
                p.referencia AS MATNR,
                p.descricao AS MAKTX,
                fi.qtde AS QTD_PEN,
				CASE WHEN pi.serie_locador IS NULL THEN fi.sequencia ELSE pi.serie_locador END AS DOC_ENV,
                f.nota_fiscal AS NF_ENV,
                TO_CHAR(now() - f.emissao, 'DD') AS DIAS_ENV,
                f.emissao AS PSTDAT,
                fi.faturamento_item
            FROM tbl_os o
            JOIN tbl_os_produto op USING(os)
            JOIN tbl_os_item oi USING(os_produto)
            JOIN tbl_servico_realizado sr ON sr.servico_realizado = oi.servico_realizado AND sr.fabrica = {$login_fabrica}
            JOIN tbl_pedido_item pi USING(pedido_item)
            JOIN tbl_faturamento_item fi ON fi.pedido_item = pi.pedido_item
            JOIN tbl_peca p ON p.peca = fi.peca AND p.fabrica = {$login_fabrica}
            JOIN tbl_faturamento f ON f.faturamento = fi.faturamento AND f.fabrica = {$login_fabrica}
            WHERE o.fabrica = {$login_fabrica}
            AND o.posto = {$login_posto}
            AND ((sr.troca_produto IS TRUE
        AND p.produto_acabado IS TRUE)
        OR fi.devolucao_obrig IS TRUE);
        ";

        $resProdutos = pg_query($con, $sqlProdutos);

        $produtosDevolucao = array();
        $produtosDevolucao = array_map(function($e) {
            return array_change_key_case($e, CASE_UPPER);
        } , pg_fetch_all($resProdutos));
        
    } catch(Exception $e) {
        $msg_erro['msg']['webservice'] = "Ocorreu um erro ao processar a requisição, aguarde à normalização";
        $erro_comm == true;
    } catch(Throwable $e) {    
        $msg_erro['msg']['webservice'] = "Ocorreu um erro ao processar a requisição, aguarde à normalização";
        $erro_comm == true;
    }

    

    if (!empty($xml['NewDataSet']['ZCBSM_PECAS_DEVOLUCAOTABLE']) || !empty($produtosDevolucao)) {
        $pecasDevolucao = array();

        if (!empty($xml['NewDataSet']['ZCBSM_PECAS_DEVOLUCAOTABLE']['QMNUM'])) {
            $pecasDevolucao = array(0 => $xml['NewDataSet']['ZCBSM_PECAS_DEVOLUCAOTABLE']);
        } else if (!empty($xml['NewDataSet']['ZCBSM_PECAS_DEVOLUCAOTABLE'])) {
            $pecasDevolucao = $xml['NewDataSet']['ZCBSM_PECAS_DEVOLUCAOTABLE'];
        }

        if (!empty($produtosDevolucao) && !empty($pecasDevolucao)) {

            $pecasDevolucao = array_merge_recursive($pecasDevolucao, $produtosDevolucao);        
        } else if (!empty($produtosDevolucao) && empty($pecasDevolucao)) {
            $pecasDevolucao = $produtosDevolucao;
        }

        usort($pecasDevolucao, function ($a, $b) {
            return $b['DIAS_ENV'] - $a['DIAS_ENV'];
        }); 
            
    

        $data = date("d-m-Y-H:i");

        $fileName = "relatorio_itens-pendentes-devolucao-{$login_fabrica}-{$data}.csv";

        $file = fopen("/tmp/{$fileName}", "w");

        $thead = "Material;Quantidade;Nota Fiscal;Data Emissão;Dias Pendentes;Valor Unitário;Valor ICMS;Valor IPI;OS\n";
    
        fwrite($file, $thead);
   
        $r = array();
        $tem_pendencia = false;                
        foreach($pecasDevolucao as $chave => $dados) {

            $peca = $dados['MATNR'].' - '.$dados['MAKTX'];
            $referencia = $dados['MATNR'];
            $os_tc = (int) $dados['QMNUM'];
            $os_sap = (int) $dados['AUFNR'];
            $nf = $dados['NF_ENV'];
            $qtde = (int) $dados['QTD_PEN'];
            $docnum = $dados['DOC_ENV'];
            $docnum = str_pad($docnum, 10, '0', STR_PAD_LEFT);
            $dias_env = $dados['DIAS_ENV'];
            $data_envio = $dados['PSTDAT'];
            $compressor = 'f';

            $fatItem = $dados['FATURAMENTO_ITEM'];
        
            $sqlCompressor = "SELECT remessa_garantia_compressor FROM tbl_peca WHERE fabrica = {$login_fabrica} AND referencia = '{$referencia}' AND remessa_garantia_compressor IS TRUE;";
            $resCompressor = pg_query($con, $sqlCompressor);

            if (pg_num_rows($resCompressor) > 0 || strpos(strtoupper(utf8_decode($dados['MAKTX'])), 'COMPR') !== false) {
                    $compressor = 't';
            }

                if (!empty($data_envio)) {
                    $data_envio = explode("-", $data_envio);
                    $data_envio = $data_envio[2]."/".$data_envio[1]."/".$data_envio[0];
                }

                // Zerar campos
                $cfop = "";
                $valor = "";
                $base_icms = "";
                $aliq_icms = "";
                $valor_icms = "";
                $base_ipi = "";
                $aliq_ipi = "";
                $valor_ipi = "";
                $pecasXml = array();

                $paramsDanfe = array(
                    "docnum" => $docnum,
                    "retornaxml" => true
                );

                $resultDanfe = $clientDanfe->DownloadDanfeXml_ByDocNum($paramsDanfe);
                $dados_xmlDanfe = $resultDanfe->DownloadDanfeXml_ByDocNumResult;
                $xmlDanfe = simplexml_load_string($dados_xmlDanfe);
                $xmlDanfe = json_decode(json_encode((array)$xmlDanfe), TRUE);

                if (!empty($xmlDanfe['NFe']['infNFe']['det']['prod']['cProd'])) {
                    $pecasXml[] = $xmlDanfe['NFe']['infNFe']['det'];
                } else {
                    $pecasXml = $xmlDanfe['NFe']['infNFe']['det'];
                }

                $cnpj_origem = $xmlDanfe['NFe']['infNFe']['emit']['CNPJ'];

                if ($cnpj_origem == '04222931000195') {
                        $cnpj_origem = '04222931000357';
                }

                
                if (empty($cnpj_origem)) {                             
                        continue;                    
                }
        
                $referencia_pesquisa = str_replace("-", "YY", $referencia);
                $sqlPrd = "SELECT produto FROM tbl_produto WHERE fabrica_i = {$login_fabrica} AND (UPPER(REPLACE(tbl_produto.referencia_pesquisa, '-', 'YY')) = UPPER('{$referencia_pesquisa}') OR UPPER(REPLACE(tbl_produto.referencia_fabrica, '-', 'YY')) = UPPER('{$referencia_pesquisa}') OR UPPER(REPLACE(tbl_produto.referencia, '-', 'YY')) = UPPER('{$referencia_pesquisa}'));";
                $resPrd = pg_query($con, $sqlPrd);

                $classPrd = "";
                if (pg_num_rows($resPrd) > 0) {
                    $classPrd = 'class="warning"';
                }

                $produto_encontrado = false;

                foreach ($pecasXml as $linha => $pecaXml) {
                    if (in_array($referencia, array_column($pecaXml, 'cProd'))) {
                        $produto_encontrado = true;
                    }
                }

                foreach ($pecasXml as $linha => $pecaXml) {

                    if ($pecaXml['prod']['cProd'] == $referencia || (!empty($classPrd) && !in_array($pecaXml['prod']['cProd'], array_column($r, 'referencia')) && !in_array($nf, array_column($r, 'nf')) && $pecaXml['prod']['cProd'] != $referencia && $produto_encontrado === false)) {
                        $produto_encontrado = true;
                        $referencia = $pecaXml['prod']['cProd'];
                        $peca = $referencia.' - '.$pecaXml['prod']['xProd'];
                        $qtde_aux = (int) $pecaXml['prod']['qCom'];
                        
                        if ($qtde == $qtde_aux) {
                            $valor = $pecaXml['prod']['vProd'];
                        } else {
                            $valor = $pecaXml['prod']['vUnTrib'];
                        }
                        
                        $cfop = $pecaXml['prod']['CFOP'];
                        $base_icms = $pecaXml['imposto']['ICMS']['ICMS00']['vBC'];
                        $aliq_icms = $pecaXml['imposto']['ICMS']['ICMS00']['pICMS'];
                        $valor_icms = $pecaXml['imposto']['ICMS']['ICMS00']['vICMS'];
                        $base_ipi = $pecaXml['imposto']['IPI']['IPITrib']['vBC'];
                        $aliq_ipi = $pecaXml['imposto']['IPI']['IPITrib']['pIPI'];
                        $valor_ipi = $pecaXml['imposto']['IPI']['IPITrib']['vIPI'];
                    }
                }

                $sqlValida = "
                    SELECT
                        faturamento_item
                    FROM tbl_faturamento f 
                    JOIN tbl_faturamento_item fi USING(faturamento)
                    JOIN tbl_peca p USING(peca,fabrica)
                    WHERE f.fabrica = {$login_fabrica}
                    AND fi.nota_fiscal_origem = '{$nf}'
                    AND p.referencia = '{$referencia}'
                    AND (fi.os = {$os_tc}
                    OR fi.obs_conferencia = '{$os_sap}')
                    AND ((f.devolucao_concluida IS NULL
                    AND f.cancelada IS NULL)
                    OR f.devolucao_concluida IS NOT NULL);
                ";

                $resValida = pg_query($con, $sqlValida);

                if (pg_num_rows($resValida) > 0) {
                    continue;
                }


                $r[] = array(
                    "referencia" => $referencia,
                    "os_tc" => $os_tc,
                    "os_sap" => $os_sap,
                    "nf" => $nf,
                    "qtde" => $qtde,
                    "docnum" => $docnum,
                    "cnpj_origem" => $cnpj_origem,
                    "valor" => $valor,
                    "base_icms" => $base_icms,
                    "aliq_icms" => $aliq_icms,
                    "valor_icms" => $valor_icms,
                    "base_ipi" => $base_ipi,
                    "aliq_ipi" => $aliq_ipi,
                    "valor_ipi" => $valor_ipi
                );

                $check = "";
                $disabled = "";
                if (is_array($pecas_gravar)) {
                    if (in_array($referencia, $pecas_gravar[$os_tc][$nf])) {
                        $check = 'checked';
                    }
                    if ($cnpj_origem != $cnpj_unico) {
                        $disabled = 'disabled="disabled"';
                    }
                }
        
                $sqlOs = "SELECT os FROM tbl_os WHERE os = {$os_tc} AND fabrica = {$login_fabrica};";
                $resOs = pg_query($con, $sqlOs);

                $OSTelecontrol = false;
                if (pg_num_rows($resOs) > 0) {
                    $OSTelecontrol = true;
                } 

               $mat                  = (!empty($classPrd) ? "PRODUTO" : "").$peca;
               $valor_formatado      = "R$ ".number_format($valor, 2, ',', '.');
               $valor_icms_formatado = "R$ ".number_format($valor_icms, 2, ',', '.');
               $valor_ipi_formatado  = "R$ ".number_format($valor_ipi, 2, ',', '.');
               $OSTelecontrol_novo   = ($OSTelecontrol === true) ? $os_tc : $os_sap;

                $tbody = "$mat;$qtde;$nf;$data_envio;$dias_env;$valor_formatado;$valor_icms_formatado;$valor_ipi_formatado;$OSTelecontrol_novo\n";
            
                fwrite($file, $tbody);
                
            }
            
            fclose($file);

            if (file_exists("/tmp/{$fileName}")) {
                system("mv /tmp/{$fileName} xls/{$fileName}");

                echo "xls/{$fileName}";
            }
        exit;
    }
}

if (isset($_REQUEST['ajax_dados_transp'])) {
    $is_transportadora = $_REQUEST['is_transportadora'];
    $posto = $_REQUEST['posto'];
    $retorno = array();

    if (empty($login_posto)) {
        $login_posto = $posto;
    }

    if ($is_transportadora === 'true') {
        $sql = "
            SELECT 
                t.transportadora,
                t.cnpj,
                fn_retira_especiais(t.nome) AS nome,
                t.ie,
                fn_retira_especiais(tf.contato_endereco) AS endereco,
                tf.contato_cidade AS cidade,
                tf.contato_estado AS estado,
                fn_retira_especiais(tf.contato_bairro) AS bairro,
                tf.contato_cep AS cep,
                tf.fone
            FROM tbl_transportadora_fabrica tf
            JOIN tbl_transportadora t ON t.transportadora = tf.transportadora
            JOIN tbl_posto_fabrica pf ON pf.transportadora = tf.transportadora AND pf.fabrica = {$login_fabrica}
            WHERE tf.fabrica = {$login_fabrica}
            AND pf.posto = {$login_posto};
        ";
    } else {
        $sql = "
            SELECT
                t.transportadora,
                t.cnpj,
                t.nome,
                t.ie,
                tf.contato_endereco AS endereco,
                tf.contato_cidade AS cidade,
                tf.contato_estado AS estado,
                tf.contato_bairro AS bairro,
                tf.contato_cep AS cep,
                tf.fone
            FROM tbl_transportadora_fabrica tf
            JOIN tbl_transportadora t ON t.transportadora = tf.transportadora
            WHERE tf.fabrica = {$login_fabrica}
            AND t.nome = 'CORREIOS';
        ";
    }

    $res = pg_query($con, $sql);

    if (pg_num_rows($res) > 0) {
        $dadosFilial = pg_fetch_all($res);
        $retorno = $dadosFilial[0];
    } else {
        $retorno = array("erro" => utf8_encode("Transportadora não encontrada"));
    }

    echo json_encode($retorno);
    exit;
}

if ($_REQUEST['gravar'] == 'Gravar' && $areaAdmin === false) {
    $solicitante = $_REQUEST['solicitante'];
    $nota_fiscal = $_REQUEST['nota_fiscal'];
    $fone1 = $_REQUEST['fone1'];
    $fone2 = $_REQUEST['fone2'];
    $email = $_REQUEST['email'];
    $emissao = $_REQUEST['emissao'];
    $observacoes = $_REQUEST['observacoes'];
    $pecas = json_decode($_REQUEST['pecas'], true);
    $oss = explode(",", $_REQUEST['oss']);
    $oss_item = explode(",", $_REQUEST['os_item']);
    $pecas_gravar = $_REQUEST['pecas_gravar'];
    $cnpj_unico = $_REQUEST['cnpj_unico'];
    $tipo_nf = $_REQUEST['tipo_nf'];
    $transportadora = $_REQUEST['transportadora'];
    $cfop = $_REQUEST['cfop'];
    $dados_adicionais = $_REQUEST['dados_adicionais'];

    if (empty($tipo_nf)) {
        $msg_erro["msg"][] = "É necessário informar o tipo de NF";
    }

    if (empty($pecas_gravar)) {
        $msg_erro["msg"][] = "É necessário selecionar ao menos uma peça para gravar a devolução";
    }

    if (!empty($solicitante)) {
        if (strlen($solicitante) > 70) {
            $msg_erro["msg"][] = "O campo não deve conter mais de 70 caracteres";
            $msg_erro["campos"][] = "solicitante";
        }
    } else {
        $msg_erro["msg"]["obrigatorio"] = "Preencha os campos obrigatórios";
        $msg_erro["campos"][] = "solicitante";
    }

    if (empty($nota_fiscal)) {
        $msg_erro["msg"]["obrigatorio"] = "Preencha os campos obrigatórios";
        $msg_erro["campos"][] = "nota_fiscal";
    }

    if (!empty($emissao)) {
        $emissao = formata_data($emissao);
    } else {
        $msg_erro["msg"]["obrigatorio"] = "Preencha os campos obrigatórios";
        $msg_erro["campos"][] = "emissao";
    }

    if (!empty($fone1)) {
        $aux_fone1 = preg_replace("/[^0-9]/", "", $fone1);
    } else {
        $msg_erro["msg"]["obrigatorio"] = "Preencha os campos obrigatórios";
        $msg_erro["campos"][] = "fone1";
    }

    if (!empty($fone2)) {
        $aux_fone2 = preg_replace("/[^0-9]/", "", $fone2);
    } else {
        $aux_fone2 = "";
    }

    if (!empty($email)) {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $msg_erro["msg"][] = "Informe um email válido";
            $msg_erro["campos"][] = "email";
        }
    } else {
        $msg_erro["msg"]["obrigatorio"] = "Preencha os campos obrigatórios";
        $msg_erro["campos"][] = "email";
    }

    if (!empty($total_icms)) {
        $aux_total_icms = str_replace('.', '', $total_icms);
        $aux_total_icms = str_replace(',', '.', $aux_total_icms);
    } else {
        $msg_erro["msg"]["obrigatorio"] = "Preencha os campos obrigatórios";
        $msg_erro["campos"][] = "total_icms";
    }

    if (!empty($total_ipi)) {
        $aux_total_ipi = str_replace('.', '', $total_ipi);
        $aux_total_ipi = str_replace(',', '.', $aux_total_ipi);
    } else {
        $msg_erro["msg"]["obrigatorio"] = "Preencha os campos obrigatórios";
        $msg_erro["campos"][] = "total_ipi";
    }

    if (!empty($total_nota)) {
        $aux_total_nota = str_replace('.', '', $total_nota);
        $aux_total_nota = str_replace(',', '.', $aux_total_nota);
    } else {
        $msg_erro["msg"]["obrigatorio"] = "Preencha os campos obrigatórios";
        $msg_erro["campos"][] = "total_nota";
    }

    if (empty($cfop)) {
        $msg_erro["msg"]["obrigatorio"] = "Preencha os campos obrigatórios";
        $msg_erro["campos"][] = "cfop";
    }

    if (empty($dados_adicionais)) {
        $msg_erro["msg"][] = "É necessário marcar a opção de estar ciente das informações adicionais constantes na NF";
    }

    if (count($_FILES) > 0) {
        $tem_anexos = array();
        $tem_xml = false;
        $tem_pdf = false;
        foreach($_FILES as $key => $value) {
            $type = strtolower(preg_replace("/.+\//", "", $value["type"]));
            if (!empty($value['name'])) {
                $tem_anexos[] = "ok";
            }

            if ($type == "xml" || $tipo_nf == "manual") {
                $tem_xml = true;
            }

            if ($type != "xml") {
                $tem_pdf = true;
            }
        }

        if (count($tem_anexos) != count($_FILES) && $tipo_nf == "eletronica") {
            $msg_erro["msg"]["anexo"] = "Anexos obrigatórios";
        } else if ($tipo_nf == "manual" && count($tem_anexos) < 1) {
            $msg_erro["msg"]["anexo"] = "É necessário informar pelo menos 1 anexo sendo: JPG, JPEG ou PDF da nota";
        } else {
            if ($tem_xml === false || $tem_pdf === false) {
                $msg_erro["msg"]["anexo"] = "Um anexo deve ser necessáriamente um XML e o outro pode ser (JPG, JPEG ou PDF)";
            }
        }

    }

    if (count($msg_erro['msg']) == 0) {

        try {

            pg_query($con, "BEGIN;");

	    $sql = "SELECT cancelada, devolucao_concluida FROM tbl_faturamento WHERE fabrica = {$login_fabrica} AND nota_fiscal = '{$nota_fiscal}' AND distribuidor = {$login_posto};";
            $res = pg_query($con, $sql);

            $cancelada = "";
            $temSolicitacoes = false;
            $devolucao_concluida = "";
            if (pg_num_rows($res) > 0) {
                for ($if = 0; $if < pg_num_rows($res); $if++) {
                    $cancelada = pg_fetch_result($res, $if, "cancelada");
                    $devolucao_concluida = pg_fetch_result($res, $if, "devolucao_concluida");
                    if (empty($cancelada) && $devolucao_concluida == 'f') {
                        $temSolicitacoes = true;
                    }
                }
            }

            if ($temSolicitacoes === true) {
                throw new Exception("Exite solicitação pendente para essa nota fiscal, impossível gravar");
            } else {

                $inst = "
                    INSERT INTO tbl_faturamento (
                        fabrica,
                        distribuidor,
                        emissao,
                        saida,
                        transportadora,
                        valor_icms,
                        valor_ipi,
                        total_nota,
                        nota_fiscal,
                        cfop
                    ) VALUES (
                        {$login_fabrica},
                        {$login_posto},
                        '{$emissao}',
                        now(),
                        {$transportadora},
                        {$aux_total_icms},
                        {$aux_total_ipi},
                        {$aux_total_nota},
                        '{$nota_fiscal}',
                        '{$cfop}'
                    ) RETURNING faturamento;
                ";

                $res = pg_query($con, $inst);

                if (strlen(pg_last_error()) > 0) {
                    throw new Exception("Ocorreu um erro gravando dados do LGR #001");
                } else {
                    $faturamento = pg_fetch_result($res, 0, "faturamento");
                }

                $instSol = "
                    INSERT INTO tbl_faturamento_destinatario (
                        faturamento,
                        nome,
                        fone,
                        ie,
                        email
                    ) VALUES (
                        {$faturamento},
                        '{$solicitante}',
                        '{$aux_fone1}',
                        '{$aux_fone2}',
                        '{$email}'
                    );
                ";
                pg_query($con, $instSol);

                if (strlen(pg_last_error()) > 0) {
                    throw new Exception("Ocorreu um erro gravando dados do LGR #002");
                }

            }

           if (!empty($observacoes)) {
                $instObs = "
                    INSERT INTO tbl_faturamento_interacao (
                        fabrica,
                        faturamento,
                        posto,
                        interacao
                    ) VALUES (
                        {$login_fabrica},
                        {$faturamento},
                        {$login_posto},
                        '{$observacoes}'
                    );
                ";

                pg_query($con, $instObs);

                if (strlen(pg_last_error()) > 0) {
                    throw new Exception("Ocorreu um erro gravando dados do LGR #003");
                }
            }

            $erro_pecas = "";
            foreach ($pecas as $key => $value) {
                if ($value['referencia'] == $pecas_gravar[$value['os_tc']][$value['nf']][$value['referencia']] && $cnpj_unico == $value['cnpj_origem']) {
    
                    $sqlPeca = "SELECT peca FROM tbl_peca WHERE referencia = '{$value['referencia']}' AND fabrica = {$login_fabrica};";
                    $resPeca = pg_query($con, $sqlPeca);

                    if (pg_num_rows($resPeca) > 0) {
                        $peca = pg_fetch_result($resPeca, 0, "peca");
                    } else {
                        $erro_pecas .= "Ocorreu um erro gravando dados do LGR #004<br />";
                    }

                    $sqlOs = "SELECT os FROM tbl_os WHERE os = {$value['os_tc']} AND fabrica = {$login_fabrica};";
                    $resOs = pg_query($con, $sqlOs);

                    $whereOs = "";
                    if (pg_num_rows($resOs) > 0) {
                        $os_tc = pg_fetch_result($resOs, 0, "os");
                        $osCol = "os,";
                        $osVal = "{$os_tc},";
                        $whereOs = "AND os = {$os_tc}";
                    } else {
                        $osCol = "obs_conferencia,";
                        $osVal = "'{$value['os_sap']}',";
                        $whereOs = "AND obs_conferencia = '{$value['os_sap']}'";
                    }

                    $sqlVFatItem = "SELECT * FROM tbl_faturamento_item WHERE faturamento = {$faturamento} AND peca = {$peca} {$whereOs};";
                    $resVFatItem = pg_query($con, $sqlVFatItem);

                    if (pg_num_rows($resVFatItem) > 0 && $value['nf'] != key($pecas_gravar[$value['os_tc']])) { 
                        continue;
                    }

                    $value['base_icms'] = (empty($value['base_icms'])) ? 0 : $value['base_icms'];
                    $value['aliq_icms'] = (empty($value['aliq_icms'])) ? 0 : $value['aliq_icms'];
                    $value['valor_icms'] = (empty($value['valor_icms'])) ? 0 : $value['valor_icms'];
                    $value['base_ipi'] = (empty($value['base_ipi'])) ? 0 : $value['base_ipi'];
                    $value['aliq_ipi'] = (empty($value['aliq_ipi'])) ? 0 : $value['aliq_ipi'];
                    $value['valor_ipi'] = (empty($value['valor_ipi'])) ? 0 : $value['valor_ipi'];

                    $instFatI = "
                        INSERT INTO tbl_faturamento_item (
                            faturamento,
                            peca,
                            qtde,
                            nota_fiscal_origem,
                            preco,
                            {$osCol}
                            aliq_icms,
                            base_icms,
                            valor_icms,
                            aliq_ipi,
                            base_ipi,
                            valor_ipi
                        ) VALUES (
                            {$faturamento},
                            {$peca},
                            {$value['qtde']},
                            '{$value['nf']}',
                            {$value['valor']},
                            {$osVal}
                            {$value['aliq_icms']},
                            {$value['base_icms']},
                            {$value['valor_icms']},
                            {$value['aliq_ipi']},
                            {$value['base_ipi']},
                            {$value['valor_ipi']}
                        );
                    ";

                    pg_query($con, $instFatI);

                    if (strlen(pg_last_error()) > 0) {
                        $erro_pecas .= "Ocorreu um erro adicionando o item {$value['referencia']} ao faturamento<br />";
                    }
                }
            }

            if (strlen($erro_pecas) > 0) {
                throw new Exception($erro_pecas);
            }

            unset($amazonTC, $image, $types);
            $amazonTC = new TDocs($con, $login_fabrica,"lgr");
            $types = array("xml", "pdf", "jpg", "jpeg");
            $erro_anexo = "";
            foreach($_FILES as $key => $imagem) {
                if ((strlen(trim($imagem["name"])) > 0) && ($imagem["size"] > 0)) {
                    $type = strtolower(preg_replace("/.+\//", "", $imagem["type"]));
                    if (!in_array($type, $types)) {
                        $erro_anexo .= "Formato inválido, são aceitos os seguintes formatos: pdf, xml, jpg ou jpeg<br />";
                        continue;
                    } else {
                        $imagem['name'] = "nf_devolucao_{$faturamento}_{$login_fabrica}_{$key}.$type";
                        $subir_anexo = $amazonTC->uploadFileS3($imagem, $faturamento, false, "nf_devolucao");

                        if (!$subir_anexo) {
                            $erro_anexo .= "Erro ao gravar o anexo {$key}<br />";
                        }
                    }
                }
            }

            if (!empty($erro_anexo)) {
                throw new Exception($erro_anexo);
            }

            pg_query($con, "COMMIT;");
            header("Location: relatorio_lgr_webservice_consulta.php?cadastro=ok");
        } catch (Exception $e) {
            $msg_erro['msg'][] = $e->getMessage();
            pg_query($con, "ROLLBACK;");
        }
    }
}

if ($_REQUEST['gravar'] == 'Gravar' && $areaAdmin === true) {
    $codigo_posto = $_REQUEST['codigo_posto'];
    $descricao_posto = $_REQUEST['descricao_posto'];
    $login_posto = $_REQUEST['login_posto'];
    $nota_fiscal = $_REQUEST['nota_fiscal'];
    $emissao = $_REQUEST['emissao'];
    $cnpj_unico = $_REQUEST['cnpj_unico'];
    $observacoes = $_REQUEST['observacoes'];
    $pecas = json_decode($_REQUEST['pecas'], true);
    $oss = explode(",", $_REQUEST['oss']);
    $oss_item = explode(",", $_REQUEST['os_item']);
    $pecas_gravar = $_REQUEST['pecas_gravar'];
    $transportadora = $_REQUEST['transportadora'];
    $autorizacao_coleta = $_REQUEST['autorizacao_coleta'];
    $cfop = $_REQUEST['cfop'];

    if (empty($pecas_gravar)) {
        $msg_erro["msg"][] = "É necessário selecionar ao menos uma peça para gravar a devolução";
    }

    if (empty($nota_fiscal)) {
        $msg_erro["msg"]["obrigatorio"] = "Preencha os campos obrigatórios #001";
        $msg_erro["campos"][] = "nota_fiscal";
    }

    if (!empty($emissao)) {
        $emissao = formata_data($emissao);
    } else {
        $msg_erro["msg"]["obrigatorio"] = "Preencha os campos obrigatórios #002";
        $msg_erro["campos"][] = "emissao";
    }

    if (!empty($total_icms)) {
        $aux_total_icms = str_replace('.', '', $total_icms);
        $aux_total_icms = str_replace(',', '.', $aux_total_icms);
    } else {
        $msg_erro["msg"]["obrigatorio"] = "Preencha os campos obrigatórios #003";
        $msg_erro["campos"][] = "total_icms";
    }

    if (!empty($total_ipi)) {
        $aux_total_ipi = str_replace('.', '', $total_ipi);
        $aux_total_ipi = str_replace(',', '.', $aux_total_ipi);
    } else {
        $msg_erro["msg"]["obrigatorio"] = "Preencha os campos obrigatórios #004";
        $msg_erro["campos"][] = "total_ipi";
    }

    if (!empty($total_nota)) {
        $aux_total_nota = str_replace('.', '', $total_nota);
        $aux_total_nota = str_replace(',', '.', $aux_total_nota);
    } else {
        $msg_erro["msg"]["obrigatorio"] = "Preencha os campos obrigatórios #005";
        $msg_erro["campos"][] = "total_nota";
    }

    if (empty($cfop)) {
        $msg_erro["msg"]["obrigatorio"] = "Preencha os campos obrigatórios #006";
        $msg_erro["campos"][] = "cfop";
    }

    if (empty($autorizacao_coleta)) {
        $msg_erro["msg"]["obrigatorio"] = "Preencha os campos obrigatórios #007";
        $msg_erro["campos"][] = "autorizacao_coleta";
    }

    if (empty($transportadora)) {
        $msg_erro["msg"]["obrigatorio"] = "É necessário adicionar uma transportadora ao cadastro do posto";
    }

    if (count($msg_erro['msg']) == 0) {

        try {

            pg_query($con, "BEGIN;");

            $sql = "SELECT cancelada, devolucao_concluida FROM tbl_faturamento WHERE fabrica = {$login_fabrica} AND nota_fiscal = '{$nota_fiscal}';";
            $res = pg_query($con, $sql);

            $cancelada = "";
	    $temSolicitacoes = false;
	    $devolucao_concluida = "";
            if (pg_num_rows($res) > 0) {
		for ($if = 0; $if < pg_num_rows($res); $if++) {
		    $cancelada = pg_fetch_result($res, $if, "cancelada");
		    $devolucao_concluida = pg_fetch_result($res, $if, "devolucao_concluida");
		    if (empty($cancelada) && $devolucao_concluida == 'f') {
			$temSolicitacoes = true;
		    }
		}
	    }

	    if ($temSolicitacoes === true) {
		throw new Exception("Exite solicitação pendente para essa nota fiscal, impossível gravar");
            } else {

                $inst = "
                    INSERT INTO tbl_faturamento (
                        fabrica,
                        distribuidor,
                        emissao,
                        saida,
                        transportadora,
                        valor_icms,
                        valor_ipi,
                        total_nota,
                        nota_fiscal,
                        cfop,
                        pedido_fabricante,
                        devolucao_concluida
                    ) VALUES (
                        {$login_fabrica},
                        {$login_posto},
                        '{$emissao}',
                        now(),
                        {$transportadora},
                        {$aux_total_icms},
                        {$aux_total_ipi},
                        {$aux_total_nota},
                        '{$nota_fiscal}',
                        '{$cfop}',
                        '{$autorizacao_coleta}',
                        't'
                    ) RETURNING faturamento;
                ";

                $res = pg_query($con, $inst);

                if (strlen(pg_last_error()) > 0) {
                    throw new Exception("Ocorreu um erro gravando dados do LGR #003");
                } else {
                    $faturamento = pg_fetch_result($res, 0, "faturamento");
                }

            }

            if (!empty($observacoes)) {
                $instObs = "
                    INSERT INTO tbl_faturamento_interacao (
                        fabrica,
                        faturamento,
                        admin,
                        interacao
                    ) VALUES (
                        {$login_fabrica},
                        {$faturamento},
                        {$login_admin},
                        '{$observacoes}'
                    );
                ";

                pg_query($con, $instObs);

                if (strlen(pg_last_error()) > 0) {
                    throw new Exception("Ocorreu um erro gravando dados do LGR #004");
                }
            }

            $erro_pecas = "";
            foreach ($pecas as $key => $value) {
                if ($value['referencia'] == $pecas_gravar[$value['os_tc']][$value['nf']][$value['referencia']] && $cnpj_unico == $value['cnpj_origem']) {
                    $sqlPeca = "SELECT peca FROM tbl_peca WHERE referencia = '{$value['referencia']}' AND fabrica = {$login_fabrica};";
                    $resPeca = pg_query($con, $sqlPeca);
                    if (pg_num_rows($resPeca) > 0) {
                        $peca = pg_fetch_result($resPeca, 0, "peca");
                    } else {
                        $erro_pecas .= "Ocorreu um erro gravando dados do LGR #005<br />";
                    }

                    $sqlOs = "SELECT os FROM tbl_os WHERE os = {$value['os_tc']} AND fabrica = {$login_fabrica};";
                    $resOs = pg_query($con, $sqlOs);

                    $whereOs = "";
                    if (pg_num_rows($resOs) > 0) {
                        $os_tc = pg_fetch_result($resOs, 0, "os");
                        $osCol = "os,";
                        $osVal = "{$os_tc},";
                        $whereOs = "AND os = {$os_tc}";
                    } else {
                        $osCol = "obs_conferencia,";
                        $osVal = "'{$value['os_sap']}',";
                        $whereOs = "AND obs_conferencia = '{$value['os_sap']}'";
                    }

                    $sqlVFatItem = "SELECT * FROM tbl_faturamento_item WHERE faturamento = {$faturamento} AND peca = {$peca} {$whereOs};";
                    $resVFatItem = pg_query($con, $sqlVFatItem);

                    if (pg_num_rows($resVFatItem) > 0 && $value['nf'] != key($pecas_gravar[$value['os_tc']])) {
                        continue;
                    }

                    $value['base_icms']  = (empty($value['base_icms'])) ? 0 : $value['base_icms'];
                    $value['aliq_icms']  = (empty($value['aliq_icms'])) ? 0 : $value['aliq_icms'];
                    $value['valor_icms'] = (empty($value['valor_icms'])) ? 0 : $value['valor_icms'];
                    $value['valor']      = (empty($value['valor'])) ? 0 : $value['valor'];
                    $value['base_ipi']   = (empty($value['base_ipi'])) ? 0 : $value['base_ipi'];
                    $value['aliq_ipi']   = (empty($value['aliq_ipi'])) ? 0 : $value['aliq_ipi'];
                    $value['valor_ipi']  = (empty($value['valor_ipi'])) ? 0 : $value['valor_ipi'];

                    $instFatI = "
                        INSERT INTO tbl_faturamento_item (
                            faturamento,
                            peca,
                            qtde,
                            nota_fiscal_origem,
                            preco,
                            {$osCol}
                            aliq_icms,
                            base_icms,
                            valor_icms,
                            aliq_ipi,
                            base_ipi,
                            valor_ipi
                        ) VALUES (
                            {$faturamento},
                            {$peca},
                            {$value['qtde']},
                            '{$value['nf']}',
                            {$value['valor']},
                            {$osVal}
                            {$value['aliq_icms']},
                            {$value['base_icms']},
                            {$value['valor_icms']},
                            {$value['aliq_ipi']},
                            {$value['base_ipi']},
                            {$value['valor_ipi']}
                        );
                    ";

                    pg_query($con, $instFatI);

                    if (strlen(pg_last_error()) > 0) {
                        $erro_pecas .= "Ocorreu um erro adicionando o item {$value['referencia']} ao faturamento<br />";
                    }
                }
            }

            if (strlen($erro_pecas) > 0) {
                throw new Exception($erro_pecas);
            }

            pg_query($con, "COMMIT;");
            header("Location: relatorio_lgr_webservice.php?posto={$login_posto}&cadastro=ok");
        } catch (Exception $e) {
            $msg_erro['msg'][] = $e->getMessage();
            pg_query($con, "ROLLBACK;");
        }
    }

}

$layout_menu = ($areaAdmin) ? 'financeiro' : 'devolucao';
$title = ($areaAdmin === true) ? traduz("ITENS PENDENTES DE DEVOLUÇÃO") : traduz("SOLICITAÇÃO DE DEVOLUÇÃO");

if ($areaAdmin === true) {
    include __DIR__.'/admin/cabecalho_new.php';
} else {
    include __DIR__.'/cabecalho_new.php';
}

$plugins = array(
   "datepicker",
   "shadowbox",
   "maskedinput",
   "alphanumeric",
   "ajaxform",
   "fancyzoom",
   "price_format",
   "tooltip",
   "select2",
   "dataTable",
   "leaflet"
);

include __DIR__.'/admin/plugin_loader.php';

if ($areaAdmin === true) {
    if (count($msg_erro["msg"]) > 0 && !empty($login_posto)) {
        $posto_readonly     = "readonly='readonly'";
        $posto_esconde_lupa = "style='display: none;'";
        $posto_mostra_troca = "style='display: block;'";
    }

    if (!empty($login_posto)) {
        $posto_readonly     = "readonly='readonly'";
        $posto_esconde_lupa = "style='display: none;'";
    } ?>
    <form name="frm_posto" id="frm_posto" method="POST" class="form-search form-inline" enctype="multipart/form-data">
        <div id="div_informacoes_posto" class="tc_formulario">
            <div class="titulo_tabela">Informações do Posto Autorizado</div>
            <br />
            <div class="row-fluid">
                <div class="span2"></div>
                <div class="span3">
                    <div class='control-group <?=(in_array('posto', $msg_erro['campos'])) ? "error" : "" ?>' >
                        <label class="control-label" for="posto_codigo">Código</label>
                        <div class="controls controls-row">
                            <div class="span10 input-append">
                                <h5 class="asteristico">*</h5>
                                <input id="codigo_posto" name="codigo_posto" class="span12" type="text" value="<?= $codigo_posto; ?>" <?=$posto_readonly?> />
                                <span class="add-on" rel="lupa" <?=$posto_esconde_lupa?> >
                                    <i class="icon-search"></i>
                                </span>
                                <input type="hidden" name="lupa_config" tipo="posto" parametro="codigo" />
                            </div>
                        </div>
                    </div>
                </div>
                <div class="span5">
                    <div class='control-group <?=(in_array('posto', $msg_erro['campos'])) ? "error" : "" ?>' >
                        <label class="control-label" for="posto_nome">Nome</label>
                        <div class="controls controls-row">
                            <div class="span11 input-append">
                                <h5 class="asteristico">*</h5>
                                <input id="descricao_posto" name="descricao_posto" class="span12" type="text" value="<?= $descricao_posto; ?>" <?=$posto_readonly?> />
                                <span class="add-on" rel="lupa" <?=$posto_esconde_lupa?> >
                                    <i class="icon-search"></i>
                                </span>
                                <input type="hidden" name="lupa_config" tipo="posto" parametro="nome" />
                            </div>
                        </div>
                    </div>
                </div>
                <div class="span2"></div>
            </div>
            <div id="div_trocar_posto" class="row-fluid" <?=$posto_mostra_troca?> >
                <div class="span2"></div>
                <div class="span10">
                    <input type="hidden" name="btn_acao" id="btn_acao" value="" />
                    <button type="button" id="trocar_posto" class="btn btn-danger" >Alterar Posto Autorizado</button>
                </div>
            </div>
        </div>
    </form>
    <script type="text/javascript">
    $(function() {

        $.autocompleteLoad(Array("posto"));
        Shadowbox.init();

        $("span[rel=lupa]").click(function() {
            $.lupa($(this));
        });

        $("#trocar_posto").click(function() {
            window.open("relatorio_lgr_webservice.php", "_SELF");
        });

    });

    function retorna_posto(retorno){
        $("#codigo_posto").val(retorno.codigo);
        $("#descricao_posto").val(retorno.nome);

        $("#btn_acao").val("pesquisar_posto");
        $("#frm_posto").submit();
    }
    </script>
    <? if (empty($login_posto)) {
        echo "<br />";
        include "rodape.php";
        exit;
    }
}

if ($_serverEnvironment == 'development') {
    $urlWSDL = "http://ws.carrieronline.com.br/QA6/PSA_WebService/PSA.asmx?WSDL";
} else {
    $urlWSDL = "http://ws.carrieronline.com.br/wsPSATeleControl/PSA.asmx?WSDL";
}

$urlWSDLDanfe = "http://ws.carrieronline.com.br/wsDownloadNFe/DownloadNFe.asmx?WSDL";

$classExtratoFabrica = new ExtratoMideaCarrier($login_fabrica);
$os_posto = $classExtratoFabrica->getOsPostoLgr($login_posto);

$erro_comm = false;

try {

    $client = new SoapClient($urlWSDL, array(
        'trace' => TRUE,
        'connection_timeout' => 5000,
        'cache_wsdl' => WSDL_CACHE_NONE,
        'keep_alive' => FALSE
    ));

    $clientDanfe = new SoapClient($urlWSDLDanfe, array('trace' => TRUE));

    $posto          = $os_posto["posto"];
    $codigo_posto   = $os_posto["codigo_posto"];
    $centro_custo   = $os_posto["centro_custo"];
    $conta_contabil = $os_posto["conta_contabil"];
    $conta_contabil = str_pad($conta_contabil, 10, "0", STR_PAD_LEFT);

    $params = new SoapVar("
        <ns1:xmlDoc>
            <criterios>
                <PV_ARBPL>{$codigo_posto}</PV_ARBPL>
                <PV_CLASSEA>X</PV_CLASSEA>
            </criterios>
        </ns1:xmlDoc>
    ", XSD_ANYXML);

    $array_params = array('xmlDoc' => $params);
    $result = $client->ListaPecasNaoDevolvidas($array_params);
    $dados_xml = $result->ListaPecasNaoDevolvidasResult->any;
    $xml = simplexml_load_string($dados_xml);
    $xml = json_decode(json_encode((array)$xml), TRUE);

    $sqlProdutos = "
        SELECT DISTINCT
            o.os AS QMNUM,
            p.referencia AS MATNR,
            p.descricao AS MAKTX,
            fi.qtde AS QTD_PEN,
            CASE WHEN pi.serie_locador IS NULL THEN fi.sequencia ELSE pi.serie_locador END AS DOC_ENV,
            f.nota_fiscal AS NF_ENV,
            TO_CHAR(now() - f.emissao, 'DD') AS DIAS_ENV,
            f.emissao AS PSTDAT,
            fi.faturamento_item,
            oi.os_item
        FROM tbl_os o
        JOIN tbl_os_produto op USING(os)
        JOIN tbl_os_item oi USING(os_produto)
        JOIN tbl_servico_realizado sr ON sr.servico_realizado = oi.servico_realizado AND sr.fabrica = {$login_fabrica}
        JOIN tbl_pedido_item pi USING(pedido_item)
        JOIN tbl_faturamento_item fi ON fi.pedido_item = pi.pedido_item
        JOIN tbl_peca p ON p.peca = fi.peca AND p.fabrica = {$login_fabrica}
        JOIN tbl_faturamento f ON f.faturamento = fi.faturamento AND f.fabrica = {$login_fabrica}
        WHERE o.fabrica = {$login_fabrica}
        AND o.posto = {$login_posto}
        AND ((sr.troca_produto IS TRUE
	AND p.produto_acabado IS TRUE)
	OR fi.devolucao_obrig IS TRUE);
    ";

    $resProdutos = pg_query($con, $sqlProdutos);

    $produtosDevolucao = array();
    $produtosDevolucao = array_map(function($e) {
        return array_change_key_case($e, CASE_UPPER);
    } , pg_fetch_all($resProdutos));
    
} catch(Exception $e) {
    $msg_erro['msg']['webservice'] = "Ocorreu um erro ao processar a requisição, aguarde à normalização";
    $erro_comm == true;
} catch(Throwable $e) {    
    $msg_erro['msg']['webservice'] = "Ocorreu um erro ao processar a requisição, aguarde à normalização";
    $erro_comm == true;
}

if (count($msg_erro["msg"]) > 0) { ?>
    <br />
    <div class="alert alert-error"><h4><?= implode("<br />", $msg_erro["msg"]); ?></h4></div>
    <? if ($erro_comm) {
        include "rodape.php";
        exit;
    }
} else if ($_REQUEST['cadastro'] == 'ok') { ?>
    <br />
    <div id='msg_sucesso' class="alert alert-success"><h4>Dados gravados com sucesso</h4></div>
<? } else { ?>
    <br />
<? }

if ($xml['NewDataSet']['ZCBSM_MENSAGEMTABLE']['MSGTY'] == 'E' && empty($produtosDevolucao)) { ?>
    <br />
    <div class="alert alert-info"><h4>Nenhuma pendência encontrada.</h4></div>
    <? include_once "rodape.php";
    exit;
}

if (!empty($xml['NewDataSet']['ZCBSM_PECAS_DEVOLUCAOTABLE']) || !empty($produtosDevolucao)) {
    $pecasDevolucao = array();

    if (!empty($xml['NewDataSet']['ZCBSM_PECAS_DEVOLUCAOTABLE']['QMNUM'])) {
        $pecasDevolucao = array(0 => $xml['NewDataSet']['ZCBSM_PECAS_DEVOLUCAOTABLE']);
    } else if (!empty($xml['NewDataSet']['ZCBSM_PECAS_DEVOLUCAOTABLE'])) {
        $pecasDevolucao = $xml['NewDataSet']['ZCBSM_PECAS_DEVOLUCAOTABLE'];
    }

    if (!empty($produtosDevolucao) && !empty($pecasDevolucao)) {

        $pecasDevolucao = array_merge_recursive($pecasDevolucao, $produtosDevolucao);        
    } else if (!empty($produtosDevolucao) && empty($pecasDevolucao)) {
        $pecasDevolucao = $produtosDevolucao;
    }
 
    usort($pecasDevolucao, function ($a, $b) {
        return $b['DIAS_ENV'] - $a['DIAS_ENV'];
    }); 
    ?> 

    <form name="frm_lgr" id="frm_lgr" method="POST" class="form-search form-inline" enctype="multipart/form-data" >
        <input type="hidden" id="login_posto" name="login_posto" value="<?= $login_posto; ?>" />
        <input type="hidden" id="cnpj_unico" name="cnpj_unico" value="<?= $cnpj_unico; ?>" />
        <div class="row"><b class="obrigatorio pull-right">* Campos Obrigatórios</b></div>
        <table id="lgr_pesquisa" class='table table-striped table-bordered table-hover table-large'>
            <thead>
                <tr>
                    <th colspan="11" class="titulo_coluna tac">Materiais para Devolução</th>
                </tr>
                <tr class='titulo_coluna'>
                    <th class="tac">#</th>
                    <th class="tac">Material</th>
                    <th class="tac">Qtde</th>
                    <th class="tac">NF</th>
                    <th class="tac">Data Emissão</th>
                    <th class="tac">Dias Pendentes</th>
                    <th class="tac">Valor Unit.</th>
                    <th class="tac">Valor ICMS</th>
                    <th class="tac">Valor IPI</th>
                    <th class="tac">OS</th>                    
                    <th class="tac">Interação</th>                    
                </tr>
            </thead>
            <tbody>
                <?
                $r = array();
                $tem_pendencia = false;     

                foreach($pecasDevolucao as $chave => $dados) {

					$peca = $dados['MATNR'].' - '.$dados['MAKTX'];
					$referencia = $dados['MATNR'];
					$os_tc = (int) $dados['QMNUM'];
					$os_sap = (int) $dados['AUFNR'];
					$nf = $dados['NF_ENV'];
					$qtde = (int) $dados['QTD_PEN'];
					$docnum = $dados['DOC_ENV'];
					$docnum = str_pad($docnum, 10, '0', STR_PAD_LEFT);
					$dias_env = $dados['DIAS_ENV'];
					$data_envio = $dados['PSTDAT'];
					$os_item = (int) $dados['OS_ITEM'];
					$compressor = 'f';
					$fatItem = $dados['FATURAMENTO_ITEM'];

					$sqlCompressor = "SELECT remessa_garantia_compressor FROM tbl_peca WHERE fabrica = {$login_fabrica} AND referencia = '{$referencia}' AND remessa_garantia_compressor IS TRUE;";
					$resCompressor = pg_query($con, $sqlCompressor);

					if (pg_num_rows($resCompressor) > 0 || strpos(strtoupper(utf8_decode($dados['MAKTX'])), 'COMPR') !== false) {
						$compressor = 't';
					}

					if (!empty($data_envio)) {
						$data_envio = explode("-", $data_envio);
						$data_envio = $data_envio[2]."/".$data_envio[1]."/".$data_envio[0];
					}

					$classAtraso = "";
					if ((int) $dias_env > 20) {
						$classAtraso = "alert alert-error";
						$dias_env = "<b>{$dias_env}</b>";
					}

					// Zerar campos
					$cfop = "";
					$valor = "";
					$base_icms = "";
					$aliq_icms = "";
					$valor_icms = 0;
					$base_ipi = "";
					$aliq_ipi = "";
					$valor_ipi = 0;
					$pecasXml = array();

					$paramsDanfe = array(
						"docnum" => $docnum,
						"retornaxml" => true
					);

					$resultDanfe = $clientDanfe->DownloadDanfeXml_ByDocNum($paramsDanfe);
					$dados_xmlDanfe = $resultDanfe->DownloadDanfeXml_ByDocNumResult;
					$xmlDanfe = simplexml_load_string($dados_xmlDanfe);
					$xmlDanfe = json_decode(json_encode((array)$xmlDanfe), TRUE);

					if (!empty($xmlDanfe['NFe']['infNFe']['det']['prod']['cProd'])) {
						$pecasXml[] = $xmlDanfe['NFe']['infNFe']['det'];
					} else {
						$pecasXml = $xmlDanfe['NFe']['infNFe']['det'];
					}

					$cnpj_origem = $xmlDanfe['NFe']['infNFe']['emit']['CNPJ'];

					if ($cnpj_origem == '04222931000195') {
						$cnpj_origem = '04222931000357';
					}


					if (empty($cnpj_origem)) {                             
						continue;                    
					}

					$referencia_pesquisa = str_replace("-", "YY", $referencia);
					$sqlPrd = "SELECT produto FROM tbl_produto WHERE fabrica_i = {$login_fabrica} AND (UPPER(REPLACE(tbl_produto.referencia_pesquisa, '-', 'YY')) = UPPER('{$referencia_pesquisa}') OR UPPER(REPLACE(tbl_produto.referencia_fabrica, '-', 'YY')) = UPPER('{$referencia_pesquisa}') OR UPPER(REPLACE(tbl_produto.referencia, '-', 'YY')) = UPPER('{$referencia_pesquisa}'));";
					$resPrd = pg_query($con, $sqlPrd);

					$classPrd = "";
					if (pg_num_rows($resPrd) > 0) {
						$classPrd = 'class="warning"';
					}

					$produto_encontrado = false;

					foreach ($pecasXml as $linha => $pecaXml) {
						if (in_array($referencia, array_column($pecaXml, 'cProd'))) {
							$produto_encontrado = true;
						}
					}

					foreach ($pecasXml as $linha => $pecaXml) {

						if ($pecaXml['prod']['cProd'] == $referencia || (!empty($classPrd) && !in_array($pecaXml['prod']['cProd'], array_column($r, 'referencia')) && !in_array($nf, array_column($r, 'nf')) && $pecaXml['prod']['cProd'] != $referencia && $produto_encontrado === false)) {
							$produto_encontrado = true;
							$referencia = $pecaXml['prod']['cProd'];
							$peca = $referencia.' - '.$pecaXml['prod']['xProd'];
							$qtde_aux = (int) $pecaXml['prod']['qCom'];

							if ($qtde == $qtde_aux) {
								$valor = $pecaXml['prod']['vProd'];
							} else {
								$valor = $pecaXml['prod']['vUnTrib'];
							}
							$cfop = $pecaXml['prod']['CFOP'];
							$base_icms = $pecaXml['imposto']['ICMS']['ICMS00']['vBC'];
							$aliq_icms = $pecaXml['imposto']['ICMS']['ICMS00']['pICMS'];
							$valor_icms = $pecaXml['imposto']['ICMS']['ICMS00']['vICMS'];
							$base_ipi = $pecaXml['imposto']['IPI']['IPITrib']['vBC'];
							$aliq_ipi = $pecaXml['imposto']['IPI']['IPITrib']['pIPI'];
							$valor_ipi = $pecaXml['imposto']['IPI']['IPITrib']['vIPI'];
						}
					}

					$sqlValida = "
						SELECT
							faturamento_item
						FROM tbl_faturamento f 
						JOIN tbl_faturamento_item fi USING(faturamento)
						JOIN tbl_peca p USING(peca,fabrica)
						WHERE f.fabrica = {$login_fabrica}
						AND fi.nota_fiscal_origem = '{$nf}'
						AND p.referencia = '{$referencia}'
						AND (fi.os = {$os_tc}
						OR fi.obs_conferencia = '{$os_sap}')
						AND ((f.devolucao_concluida IS NULL
						AND f.cancelada IS NULL)
						OR f.devolucao_concluida IS NOT NULL);
					";

					$resValida = pg_query($con, $sqlValida);

					if (pg_num_rows($resValida) > 0) {
						continue;
					}

					if(empty($valor) and !empty($pecaXml['prod']['vProd'])) {
						$valor = $pecaXml['prod']['vProd'];
					}

					$r[] = array(
						"referencia" => $referencia,
						"os_tc" => $os_tc,
						"os_sap" => $os_sap,
						"nf" => $nf,
						"qtde" => $qtde,
						"docnum" => $docnum,
						"cnpj_origem" => $cnpj_origem,
						"valor" => $valor,
						"base_icms" => $base_icms,
						"aliq_icms" => $aliq_icms,
						"valor_icms" => $valor_icms,
						"base_ipi" => $base_ipi,
						"aliq_ipi" => $aliq_ipi,
						"valor_ipi" => $valor_ipi,
						"os_item" => $os_item
					);

					$check = "";
					$disabled = "";
					if (is_array($pecas_gravar)) {
						if (in_array($referencia, $pecas_gravar[$os_tc][$nf])) {
							$check = 'checked';
						}
						if ($cnpj_origem != $cnpj_unico) {
							$disabled = 'disabled="disabled"';
						}
					}

					$sqlOs = "SELECT os, sua_os FROM tbl_os WHERE os = {$os_tc} AND fabrica = {$login_fabrica};";
					$resOs = pg_query($con, $sqlOs);

					$OSTelecontrol = false;
					if (pg_num_rows($resOs) > 0) {
						$OSTelecontrol = true;
						$sua_os = pg_fetch_result($resOs, 0, 'sua_os');

						if (empty($fatItem)) {
							$sqlFatItem = "
								SELECT
									fi.faturamento_item
								FROM tbl_faturamento_item fi
								JOIN tbl_faturamento f USING(faturamento)
								JOIN tbl_peca p USING(peca,fabrica)
								WHERE f.fabrica = {$login_fabrica}
								AND f.nota_fiscal = '{$nf}'
								AND p.referencia = '{$referencia}';
							";
							$resFatItem = pg_query($con, $sqlFatItem);

							if (pg_num_rows($resFatItem) > 0) {
								$fatItem = pg_fetch_result($resFatItem, 0, 'faturamento_item');
							}
						}
					} ?>
					<tr <?= $classPrd; ?>>
						<td class="tac">
							<input type="checkbox" name="pecas_gravar[<?= $os_tc; ?>][<?= $nf; ?>][<?= $referencia; ?>]" rel="<?= $chave; ?>" value="<?= $referencia; ?>" data-cnpj_origem="<?= $cnpj_origem; ?>" data-cfop="<?= $cfop; ?>" data-ostc="<?= $os_tc; ?>" data-os_item="<?= $os_item; ?>" <?= $check; ?> <?= $disabled; ?> />
							<input type="hidden" id="icms_linha_<?= $chave; ?>" name="icms_linha_<?= $chave; ?>" value="<?= $valor_icms; ?>" />
							<input type="hidden" id="ipi_linha_<?= $chave; ?>" name="ipi_linha_<?= $chave; ?>" value="<?= $valor_ipi; ?>" />
							<input type="hidden" id="valor_linha_<?= $chave; ?>" name="valor_linha_<?= $chave; ?>" value="<?= $valor; ?>" />
							<? if ($compressor == 't') { ?>
								<input type="hidden" id="is_compressor_<?= $chave; ?>" name="is_compressor_<?= $chave; ?>" value="t" />
							<? } else { ?>
								<input type="hidden" id="is_compressor_<?= $chave; ?>" name="is_compressor_<?= $chave; ?>" value="f" />
<? }
if (!empty($classPrd)) { ?>
								<input type="hidden" id="is_produto_<?= $chave; ?>" name="is_produto_<?= $chave; ?>" value="t" />
							<? } else { ?>
								<input type="hidden" id="is_produto_<?= $chave; ?>" name="is_produto_<?= $chave; ?>" value="f" />
							<? } ?>
						</td>
						<td><?= ((!empty($classPrd)) ? "<b>(PRODUTO)</b> " : "").$peca; ?></td>
						<td class="tac"><?= $qtde; ?></td>
						<td class="tac"><?= $nf; ?></td>
						<td class="tac"><?= $data_envio; ?></td>
						<td class="tac <?= $classAtraso; ?>"><?= $dias_env; ?></td>
						<td class="tar">R$ <?= number_format($valor, 2, ',', '.'); ?></td>
						<td class="tar">R$ <?= number_format($valor_icms, 2, ',', '.'); ?></td>
						<td class="tar">R$ <?= number_format($valor_ipi, 2, ',', '.'); ?></td>
						<td class="tar"><?= ($OSTelecontrol === true) ? $sua_os : $os_sap; ?></td>

						<td class="tac">
							<?php if ($OSTelecontrol === true) { ?>
								<button data-fat-item="<?= $fatItem; ?>" type="button" class="btn btn-sm btn-primary btn-interacoes">Abrir</button>
							<?php } ?>
						</td>
					</tr>
					<?php
					$tem_pendencia = true;
                }
                
		if ($tem_pendencia === false) { ?>
                    <tr>
                        <td colspan="11" class="alert alert-info tac">Nenhuma peça pendente de solicitação foi encontrada</td>
                    </tr>
                <? } ?>
            </tbody>
        </table>
        <br />
        <?php 
        if (in_array($login_fabrica, [169,170])) {

            $jsonPOST = excelPostToJson($_POST);
        ?>
            <div id='gerar_excel' class="btn_excel">
                <input type="hidden" id="jsonPOST" value='<?=$jsonPOST?>' />
                <span><img src='imagens/excel.png' /></span>
                <span class="txt">Gerar Arquivo Excel</span>
            </div>
        <?php
        }
        ?>
        <br />
        <? $xmlJson = json_encode($r);
        if ($tem_pendencia === true) {
            if ($areaAdmin === false) { ?>
                <div id="div_informacoes_retorno_nf" class="tc_formulario">
                    <div class="titulo_tabela">Destinatário</div>
                    <br />
                    <div class="row-fluid">
                        <div class="span2"></div>
                        <? if (!empty($cnpj_unico)) {
                            $displayOrigem = '';

                            $sqlDest = "
                                SELECT
                                    fn_retira_especiais(f.nome) AS nome,
                                    fn_retira_especiais(f.fantasia) AS fantasia,
                                    fn_retira_especiais(f.endereco) AS endereco,
                                    f.numero,
                                    fn_retira_especiais(f.bairro) AS bairro,
                                    fn_retira_especiais(f.complemento) AS complemento,
                                    f.cnpj,
                                    f.ie,
                                    f.cep,
                                    fn_retira_especiais(c.nome) AS cidade,
                                    f.estado
                                FROM tbl_filial f
                                JOIN tbl_cidade c USING(cidade)
                                WHERE f.fabrica = {$login_fabrica}
                                AND f.cnpj = '{$cnpj_unico}';
                            ";
                            $resDest = pg_query($con, $sqlDest);

                            $destNome = pg_fetch_result($resDest, 0, "nome");
                            $destFantasia = pg_fetch_result($resDest, 0, "fantasia");
                            $destCnpj = pg_fetch_result($resDest, 0, "cnpj");
                            $destEndereco = pg_fetch_result($resDest, 0, "endereco");
                            $destNumero = pg_fetch_result($resDest, 0, "numero");
                            $destComplemento = pg_fetch_result($resDest, 0, "complemento");
                            $destBairro = pg_fetch_result($resDest, 0, "bairro");
                            $destCidade = pg_fetch_result($resDest, 0, "cidade");
                            $destEstado = pg_fetch_result($resDest, 0, "estado");
                            $destCep = pg_fetch_result($resDest, 0, "cep");
                            $destIe = pg_fetch_result($resDest, 0, "ie");

                            if (!empty($destFantasia)) {
                                $destFantasia = ' - '.$destFantasia;
                            } else {
                                $destFantasia = "";
                            }

                            if (!empty($destNumero)) {
                                $destNumero = ' - '.$destNumero;
                            } else {
                                $destNumero = "";
                            }
                            
                            if (!empty($destComplemento)) {
                                $destComplemento = ', '.$destComplemento;
                            } else {
                                $destComplemento = "";
                            }
                            
                            $conteudoDest = "
                                <b>".$destNome.$destFantasia."</b><br />
                                <b>CNPJ:</b> ".$destCnpj."<br />
                                <b>".$destEndereco.$destNumero.$destComplemento.", ".$destBairro." - ".$destCidade."/".$destEstado."</b><br />
                                <b>CEP:</b> ".$destCep."<br />
                                <b>IE:</b> ".$destIe."<br />
                            ";

                        } else {
                            $displayOrigem = 'style="display:none;"';
                            $conteudoDest = "";
                        } ?>
                        <div id="inf_origem" class="span8" <?= $displayOrigem; ?>>
                            <?= $conteudoDest; ?>
                        </div>
                        <div class="span2"></div>
                    </div>
                    <br />
                </div>
                <br />
                <div id="div_informacoes_retorno_nf" class="tc_formulario">
                    <div class="titulo_tabela">Transportadora</div>
                    <br />
                    <div class="row-fluid">
                        <div class="span2"></div>
                        <? if (!empty($transportadora)) {
                            $sqlTransp = "
                                SELECT 
                                    t.cnpj,
                                    t.nome,
                                    t.ie,
                                    tf.contato_endereco AS endereco,
                                    tf.contato_cidade AS cidade,
                                    tf.contato_estado AS estado,
                                    tf.contato_bairro AS bairro,
                                    tf.contato_cep AS cep,
                                    tf.fone
                                FROM tbl_transportadora_fabrica tf
                                JOIN tbl_transportadora t ON t.transportadora = tf.transportadora
                                WHERE tf.fabrica = {$login_fabrica}
                                AND tf.transportadora = {$transportadora};
                            ";
                            
                            $resTransp = pg_query($con, $sqlTransp);

                            if (pg_num_rows($resTransp) > 0) {
                                $transpCnpj = pg_fetch_result($resTransp, 0, "cnpj");
                                $transpNome = pg_fetch_result($resTransp, 0, "nome");
                                $transpEndereco = pg_fetch_result($resTransp, 0, "endereco");
                                $transpBairro = pg_fetch_result($resTransp, 0, "bairro");
                                $transpCidade = pg_fetch_result($resTransp, 0, "cidade");
                                $transpEstado = pg_fetch_result($resTransp, 0, "estado");
                                $transpCep = pg_fetch_result($resTransp, 0, "cep");
                                $transpIe = pg_fetch_result($resTransp, 0, "ie");
                                $transpFone = pg_fetch_result($resTransp, 0, "fone");

                                if ($transpNome != "CORREIOS") {
                                    $conteudoTransp = "
                                        <b>{$transpNome}</b><br />
                                        <b>CNPJ:</b> {$transpCnpj}<br />
                                        <b>{$transpEndereco} - {$transpBairro} - {$transpCidade} / {$transpEstado}</b><br />
                                        <b>CEP:</b> {$transpCep}<br />
                                        <b>IE:</b> {$transpIe}
                                    ";
                                } else {
                                    $conteudoTransp = "
                                        <b>{$transpNome}</b><br />
                                        Será gerado um <b>E-TICKET</b> e informado<br />
                                        no Telecontrol assim que sua solicitação for aprovada
                                    ";
                                }
                                $conteudoTransp .= "<br /><b>Pagador do Frete = DESTINATÁRIO</b>";
                            } else {
                                $conteudoDest = "<b>É necessário adicionar uma transportadora ao cadastro do posto</b>";
                            }
                        } else {
                            $displayTransp = 'style="display:none;"';
                            $conteudoTransp = "";
                        } ?>
                        <div id="inf_transp" class="span8" <?= $displayTransp; ?>>
                            <?= $conteudoTransp; ?>
                        </div>
                        <div class="span2"></div>
                    </div>
                    <br />
                </div>
                <br />
                <input type="hidden" id="pecas" name="pecas" value='<?= $xmlJson; ?>' />
                <input type="hidden" id="transportadora" name="transportadora" value='<?= $transportadora; ?>' />
                <div id="div_informacoes_valores" class="tc_formulario">
                    <div class="titulo_tabela">Cálculo de Impostos</div>
                    <br />
                    <div class="row-fluid">
                        <div class="span2"></div>
                        <div class="span2">
                            <div class='control-group <?=(in_array('total_icms', $msg_erro['campos'])) ? "error" : "" ?>'>
                                <label class="control-label" for="total_icms">Total ICMS</label>
                                <div class="controls controls-row">
                                    <div class="span12">
                                        <h5 class="asteristico">*</h5>
                                        <input id="total_icms" name="total_icms" class="span12" type="text" value="<?= getValue('total_icms'); ?>" readonly="readonly" />
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="span2">
                            <div class='control-group <?=(in_array('total_ipi', $msg_erro['campos'])) ? "error" : "" ?>'>
                                <label class="control-label" for="total_ipi">Total IPI</label>
                                <div class="controls controls-row">
                                    <div class="span12">
                                        <h5 class="asteristico">*</h5>
                                        <input id="total_ipi" name="total_ipi" class="span12" type="text" value="<?= getValue('total_ipi'); ?>" readonly="readonly" />
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="span2">
                            <div class='control-group <?=(in_array('total_nota', $msg_erro['campos'])) ? "error" : "" ?>'>
                                <label class="control-label" for="total_nota">Total Nota</label>
                                <div class="controls controls-row">
                                    <div class="span12">
                                        <h5 class="asteristico">*</h5>
                                        <input id="total_nota" name="total_nota" class="span12" type="text" value="<?= getValue('total_nota'); ?>" readonly="readonly" />
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="span2">
                            <div class='control-group <?=(in_array('cfop', $msg_erro['campos'])) ? "error" : "" ?>'>
                                <label class="control-label" for="cfop">CFOP</label>
                                <div class="controls controls-row">
                                    <div class="span12">
                                        <h5 class="asteristico">*</h5>
                                        <input id="cfop" name="cfop" class="span12" type="text" value="<?= getValue('cfop'); ?>" readonly="readonly" />
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="span2"></div>
                    </div>
                    <br />
                </div>
                <br />
                <div class="row-fluid alert alert-warning">
                    <h5>Em sua NF no campo <b>DADOS ADICIONAIS</b>, deverá conter todos os números das Nf's de remessa e OS, após sua NF ser APROVADA escrever manualmente o número da autorização de coleta.</h5>
                    <center><h6><input type="checkbox" name="dados_adicionais" value="t" /> Estou ciente.</h6></center>
                </div>
                <br />
                <?php
                if (!in_array($login_fabrica, [169,170])) {
                ?>
                    <div class="row"><b class="obrigatorio pull-right">** NF manual não é necessário informar o XML</b></div>
                <?php
                } else {
                    $checkedTipo = "checked";
                }
                ?>
                <div id="div_anexos" class="tc_formulario">
                    <div class="titulo_tabela">Anexos (XML e PDF)</div>
                    <br />
                    <div class="row-fluid">
                        <div class="span2"></div>
                        <div class="span8">
                            <div class='control-group <?=(in_array('tipo_nf', $msg_erro['campos'])) ? "error" : "" ?>'>
                                <div class="controls controls-row">
                                    <div class="span6">
                                        <div class="radio tac">
                                            <label>
                                                <input type="radio" name="tipo_nf" value="eletronica" <?= $checkedTipo ?> />
                                                Eletrônica
                                            </label>
                                        </div>
                                    </div>
                                    <?php
                                    if (!in_array($login_fabrica, [169,170])) {
                                    ?>
                                        <div class="span6">
                                            <div class="radio tac">
                                                <label>
                                                    <input type="radio" name="tipo_nf" value="manual" />
                                                    Manual
                                                </label>
                                            </div>
                                        </div>
                                    <?php
                                    }
                                    ?>
                                </div>
                            </div>
                        </div>
                        <div class="span2"></div>
                    </div>
                    <div class="row-fluid">
                        <div class="span2"></div>
                        <?
                        unset($amazonTC, $anexos, $ext);
                        $amazonTC = new TDocs($con, $login_fabrica,"lgr");
                        $anexos = array();

                        for($i = 0; $i < 2; $i++) {
                            $anexos["$i"]["nome"] = "nf_devolucao_{$anexo_chave}_{$login_fabrica}_anexo_nf_{$i}";
							if(!empty($anexo_chave)) {
								$anexos["$i"]["url"] = $amazonTC->getDocumentsByName($anexos["$i"]["nome"], "lgr")->url;
							}?>
                            <div class="span4" id="div_nf_<?= $i; ?>">
                                <div class='control-group'>
                                    <label for="anexo_nf_<?= $i; ?>"><?= ($i == 0) ? "PDF/JPEG" : "XML"; ?></label>
                                    <div class="controls controls-row">
                                        <? if (strlen($anexos["$i"]["url"]) > 0) {
                                            $ext = pathinfo($anexos["$i"]["nome"], PATHINFO_EXTENSION);
                                            $src = "";
                                            if (in_array(strtolower($ext), array("pdf","jpg","jpeg"))) {
                                                $src = 'imagens/pdf_icone.png';
                                            } else if (strtolower($ext) == "xml") {
                                                $src = 'imagens/xml_icone.png';
                                            } ?>
                                            <br /><br />
                                            <a href="<?= $anexos["$i"]["url"]; ?>" target="_blank">
                                                <img src="<?= $src; ?>" style="max-height: 80px !important; max-width: 80px !important;" border="0">
                                            </a>
                                            <br /><br />
                                        <? } ?>
                                        <input type="file" name="anexo_nf_<?= $i; ?>">
                                    </div>
                                </div>
                            </div>
                        <? } ?>
                        <div class="span2"></div>
                    </div>
                    <div class="row-fluid">
                        <div class="span2"></div>
                        <div class="span2">
                            <div class='control-group <?=(in_array('nota_fiscal', $msg_erro['campos'])) ? "error" : "" ?>'>
                                <label class="control-label" for="nota_fiscal">NF Devolução</label>
                                <div class="controls controls-row">
                                    <div class="span12">
                                        <h5 class="asteristico">*</h5>
                                        <input id="nota_fiscal" name="nota_fiscal" class="span12" type="text" maxlength="20" value="<?= getValue('nota_fiscal'); ?>" />
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="span2">
                            <div class='control-group <?=(in_array('emissao', $msg_erro['campos'])) ? "error" : "" ?>'>
                                <label class="control-label" for="emissao">Emissão</label>
                                <div class="controls controls-row">
                                    <div class="span12">
                                        <h5 class="asteristico">*</h5>
                                        <input id="emissao" name="emissao" class="span12" type="text" value="<?= getValue('emissao'); ?>" />
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="span2"></div>
                    </div>
                    <div class="row-fluid">
                        <div class="span2"></div>
                        <div class="span8">
                            <div class='control-group <?=(in_array('observacoes', $msg_erro['campos'])) ? "error" : "" ?>' >
                                <label class="control-label" for="observacoes">Observações</label>
                                <div class="controls controls-row">
                                    <textarea id="observacoes" name="observacoes" class="span12" style="height: 50px;" ><?=getValue("observacoes")?></textarea>
                                </div>
                            </div>
                        </div>
                        <div class="span2"></div>
                    </div>
                    <br />
                </div>
                <br />
                <div id="div_informacoes_produto_venda" class="tc_formulario">
                    <div class="titulo_tabela">Informações Rede Autorizada</div>
                    <br />
                    <div class="row-fluid">
                        <div class="span2"></div>
                        <div class="span3">
                            <div class='control-group <?=(in_array('solicitante', $msg_erro['campos'])) ? "error" : "" ?>'>
                                <label class="control-label" for="solicitante">Solicitante</label>
                                <div class="controls controls-row">
                                    <div class="span12">
                                        <h5 class="asteristico">*</h5>
                                        <input id="solicitante" name="solicitante" class="span12" type="text" maxlength="70" value="<?= getValue('solicitante'); ?>" />
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="span5">
                            <div class='control-group <?=(in_array('email', $msg_erro['campos'])) ? "error" : "" ?>'>
                                <label class="control-label" for="email">Email</label>
                                <div class="controls controls-row">
                                    <div class="span12">
                                        <h5 class="asteristico">*</h5>
                                        <input id="email" name="email" class="span12" type="text" value="<?= getValue('email'); ?>" />
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="span2"></div>
                    </div>
                    <div class="row-fluid">
                        <div class="span2"></div>
                        <div class="span3">
                            <div class='control-group <?=(in_array('fone1', $msg_erro['campos'])) ? "error" : "" ?>'>
                                <label class="control-label" for="fone1">Telefone</label>
                                <div class="controls controls-row">
                                    <div class="span12">
                                        <h5 class="asteristico">*</h5>
                                        <input id="fone1" name="fone1" class="span12 fone" type="text" value="<?= getValue('fone1'); ?>" maxlength="14" />
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="span3">
                            <div class='control-group <?=(in_array('fone2', $msg_erro['campos'])) ? "error" : "" ?>'>
                                <label class="control-label" for="fone2">Celular</label>
                                <div class="controls controls-row">
                                    <div class="span12">
                                        <input id="fone2" name="fone2" class="span12 fone" type="text" value="<?= getValue('fone2'); ?>" maxlength="15" />
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="span2"></div>
                    </div>
                    <br />
                </div>
                <br />
            <? } else if ($areaAdmin === true) { ?>
                <input type="hidden" id="pecas" name="pecas" value='<?= $xmlJson; ?>' />
                <input type="hidden" id="transportadora" name="transportadora" value='<?= $transportadora; ?>' />
                <input type="hidden" id="cfop" name="cfop" value="<?= $cfop; ?>" />
                <input type="hidden" id="total_nota" name="total_nota" value="<?= $total_nota; ?>" />
                <input type="hidden" id="total_icms" name="total_icms" value="<?= $total_icms; ?>" />
                <input type="hidden" id="total_ipi" name="total_ipi" value="<?= $total_ipi; ?>" />
                <div id="div_informacoes_valores" class="tc_formulario">
                    <div class="titulo_tabela">Dados da Solicitação</div>
                    <br />
                    <div class="row-fluid">
                        <div class="span2"></div>
                        <div class="span2">
                            <div class='control-group <?=(in_array('nota_fiscal', $msg_erro['campos'])) ? "error" : "" ?>'>
                                <label class="control-label" for="nota_fiscal">NF Devolução</label>
                                <div class="controls controls-row">
                                    <div class="span12">
                                        <h5 class="asteristico">*</h5>
                                        <input id="nota_fiscal" name="nota_fiscal" class="span12" type="text" maxlength="20" value="<?= getValue('nota_fiscal'); ?>" />
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="span2">
                            <div class='control-group <?=(in_array('emissao', $msg_erro['campos'])) ? "error" : "" ?>'>
                                <label class="control-label" for="emissao">Emissão</label>
                                <div class="controls controls-row">
                                    <div class="span12">
                                        <h5 class="asteristico">*</h5>
                                        <input id="emissao" name="emissao" class="span12" type="text" value="<?= getValue('emissao'); ?>" />
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="span2">
                            <div class='control-group <?=(in_array('autorizacao_coleta', $msg_erro['campos'])) ? "error" : "" ?>'>
                                <label class="control-label" for="autorizacao_coleta">AC</label>
                                <div class="controls controls-row">
                                    <div class="span12">
                                        <h5 class="asteristico">*</h5>
                                        <input id="autorizacao_coleta" name="autorizacao_coleta" class="span12" type="text" value="<?= getValue('autorizacao_coleta'); ?>" />
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="span2"></div>
                    </div>
                    <div class="row-fluid">
                        <div class="span2"></div>
                        <div class="span8">
                            <div class='control-group <?=(in_array('observacoes', $msg_erro['campos'])) ? "error" : "" ?>' >
                                <label class="control-label" for="observacoes">Observações</label>
                                <div class="controls controls-row">
                                    <textarea id="observacoes" name="observacoes" class="span12" style="height: 50px;" ><?=getValue("observacoes")?></textarea>
                                </div>
                            </div>
                        </div>
                        <div class="span2"></div>
                    </div>
                    <br />
                </div>
                <br />
            <? } ?>
            <div class="tac">
                <input type='hidden' name="gravar" />
                <input type="hidden" id="oss" name="oss" value='' />
                <input type="hidden" id="os_item" name="os_item" value='' />
                <input type="button" class="btn btn-large" value="Gravar" id="lgr_form_submit" data-submit="" />
            </div>
        <? } ?>
    </form>
<? } ?>

<script type="text/javascript">
$(function() {

    $('.fone').keyup(function(){
        mascara( this, mtel );
    });

    $("#total_icms").priceFormat({
        prefix: '',
        thousandsSeparator: '.',
        centsSeparator: ',',
        centsLimit: 2
    });

    $("#total_ipi").priceFormat({
        prefix: '',
        thousandsSeparator: '.',
        centsSeparator: ',',
        centsLimit: 2
    });

    $("#total_nota").priceFormat({
        prefix: '',
        thousandsSeparator: '.',
        centsSeparator: ',',
        centsLimit: 2
    });

    $("#emissao").datepicker({dateFormat:"dd/mm/yy"}).mask("99/99/9999");

    <? if ($tem_pendencia !== false) { ?>
	$.dataTableLoad({ table: "#lgr_pesquisa" });
    <? } ?>

    $("#lgr_form_submit").on("click", function(e) {
        e.preventDefault();

        var submit = $(this).data("submit");
        if (submit.length == 0) {
            $(this).data({ submit: true });

            var total_chamados        = $(dataTableGlobal.fnGetNodes()).length;
            var verificar_selecionado = 0;
            var aux_chamado           = "";
            var aux_os_item           = "";
            var array_chamado = [];
            var array_os_item = [];

            for (var wx = 0; wx < total_chamados; wx++) {
                verificar_selecionado = $(dataTableGlobal.fnGetNodes()[wx]).find('td').first().find('input:checked').length;
                
                if (verificar_selecionado == 1) {
                    aux_chamado = $(dataTableGlobal.fnGetNodes()[wx]).find('td').first().find('input:checked').data("ostc");
                    array_chamado.push(aux_chamado);
                    aux_os_item = $(dataTableGlobal.fnGetNodes()[wx]).find('td').first().find('input:checked').data("os_item");
                    array_os_item.push(aux_os_item);
                }
            }

            $("#oss").val("");
            $("#oss").val(array_chamado);
            $("#os_item").val("");
            $("#os_item").val(array_os_item);

            $("input[name=gravar]").val('Gravar');
            $(this).parents("form").submit();
        } else {
           alert("Não clique no botão voltar do navegador, utilize somente os botões da tela");
        }
    });

    $("input[name=tipo_nf]").change(function() {
        if ($(this).is(":checked")) {
            if ($(this).val() == "manual") {
                $("#div_nf_1").hide();
            } else {
                $("#div_nf_1").show();
            }
        }
    });

    $(document).on("change","input[name^=pecas_gravar]", function() {
        var linha = $(this).attr("rel");
        var cnpj_origem = $(this).data("cnpj_origem");
        var cfop_origem = $(this).data("cfop");
        var selected = false;
        var is_transportadora = false;

        $("input[name^=pecas_gravar]").each(function() {
            if ($(this).is(":checked")) {
                var linha_selected = $(this).attr("rel");
                if ($("#is_produto_"+linha_selected).val() == 't' || $("#is_compressor_"+linha_selected).val() == 't') {
                    is_transportadora = true;
                }
            }
        });

        $("input[name^=pecas_gravar]").each(function() {
            if ($(this).data('cnpj_origem') != cnpj_origem) {
                $(this).attr('disabled', true);
            }
            if ($(this).is(":checked")) {
                selected = true;
            }
        });

        if (selected === false) {
            $("#inf_origem").html("");
            $("#inf_transp").html("");
            $("#cnpj_unico").val("");
            $("#cfop").val("");
            $("input[name^=pecas_gravar]").each(function() {
                $(this).attr('disabled', false);
            });

            $("#total_icms").val(0.00);
            $("#total_ipi").val(0.00);
            $("#total_nota").val(0.00);
        } else {
            $("#cnpj_unico").val(cnpj_origem);
			if($("#cfop").val() == "") {
				$("#cfop").val(cfop_origem);
			}

            <? if ($areaAdmin === false) { ?>
                if ($("#inf_origem").not(":visible")) {
                    carrega_inf_origem(cnpj_origem);
                }
            <? } ?>

            carrega_inf_transp(is_transportadora);

            if ($("#total_icms").val() == "") {
                var total_icms = 0;
            } else {
                var total_icms = parseFloat($("#total_icms").val());
            }
            if ($("#total_ipi").val() == "") {
                var total_ipi = 0;
            } else {
                var total_ipi = parseFloat($("#total_ipi").val());
            }
            if ($("#total_nota").val() == "") {
                var total_nota = 0;
            } else {
                var total_nota = parseFloat($("#total_nota").val());
            }

            if ($("#icms_linha_"+linha).val() == "") {
                var icms_linha = 0;
            } else {
                var icms_linha = parseFloat($("#icms_linha_"+linha).val());
            }

            if ($("#ipi_linha_"+linha).val() == "") {
                var ipi_linha = 0;
            } else {
                var ipi_linha = parseFloat($("#ipi_linha_"+linha).val());
            }

            if ($("#valor_linha_"+linha).val() == "") {
                var valor_linha = 0;
            } else {
                var valor_linha = parseFloat($("#valor_linha_"+linha).val());
            }

            if($(this).is(":checked")) {
                total_icms += icms_linha;
                total_ipi += ipi_linha;
                total_nota += valor_linha;
                total_nota += ipi_linha;
            } else {
                total_icms -= icms_linha;
                total_ipi -= ipi_linha;
                total_nota -= valor_linha;
                total_nota -= ipi_linha;
            }

            $("#total_icms").val(total_icms.toFixed(2));
            $("#total_ipi").val(total_ipi.toFixed(2));
            $("#total_nota").val(total_nota.toFixed(2));
        }
    });

    if ($("#msg_sucesso").is(":visible")) {
	setTimeout(function(){ $("#msg_sucesso").hide(); }, 5000);
    }

})

function carrega_inf_origem(cnpj_origem) {
    $.ajax({
        url: "<?= $PHP_SELF; ?>",
        type: "POST",
        data: { ajax_dados_origem: true, cnpj_origem: cnpj_origem },
        beforeSend: function() {
            $("#inf_origem").show().html("<img src='imagens/loading_img.gif' style='width:30px;height:30px;' />");
        },
        complete: function(data) {
            data = $.parseJSON(data.responseText);
            $("#inf_origem").html();
            if (data.erro == undefined) {

                if (data.fantasia != null) {
                    data.fantasia = ' - '+data.fantasia;
                } else {
                    data.fantasia = "";
                }

                if (data.numero != null) {
                    data.numero = ", "+data.numero;
                } else {
                    data.numero = "";
                }

                if (data.complemento != null) {
                    data.complemento = ', '+data.complemento;
                } else {
                    data.complemento = "";
                }

                $("#inf_origem").html('\
                    <b>'+data.nome+data.fantasia+'</b><br />\
                    <b>CNPJ:</b> '+data.cnpj+'<br />\
                    <b>'+data.endereco+data.numero+data.complemento+', '+data.bairro+' - '+data.cidade+'/'+data.estado+'</b><br />\
                    <b>CEP:</b> '+data.cep+'<br />\
                    <b>IE:</b> '+data.ie
                );
            }
        }
    });
}

function carrega_inf_transp(is_transportadora = true) {
    $.ajax({
        url: "<?= $PHP_SELF; ?>",
        type: "POST",
        data: { ajax_dados_transp: true, is_transportadora: is_transportadora, posto: <?= $login_posto; ?> },
        beforeSend: function() {
            <? if ($areaAdmin === false) { ?>
                $("#inf_transp").show().html("<img src='imagens/loading_img.gif' style='width:30px;height:30px;' />");
            <? } ?>
        },
        complete: function(data) {
            data = $.parseJSON(data.responseText);
            <? if ($areaAdmin === false) { ?>
                $("#inf_transp").html();
            <? } ?>
            if (data.erro == undefined) {
                $("#transportadora").val(data.transportadora);
                <? if ($areaAdmin === false) { ?>
                    if (data.nome != "CORREIOS") {
                        $("#inf_transp").html('\
                            <b>'+data.nome+'</b><br />\
                            <b>CNPJ:</b> '+data.cnpj+'<br />\
                            <b>'+data.endereco+' - '+data.bairro+' - '+data.cidade+' / '+data.estado+'</b><br />\
                            <b>CEP:</b> '+data.cep+'<br />\
                            <b>IE:</b> '+data.ie+'<br />\
                            <b>Pagador do Frete = DESTINATÁRIO</b>'
                        );
                    } else {
                        $("#inf_transp").html('\
                            <b>'+data.nome+'</b><br />\
                            Será gerado um <b>E-TICKET</b> e informado<br />\
                            no Telecontrol assim que sua solicitação for aprovada<br />\
                            <b>Pagador do Frete = DESTINATÁRIO</b>'
                        );
                    }
                <? } ?>
            } else {
                $("#transportadora").val();
                <? if ($areaAdmin === false) { ?>
                    $("#inf_transp").html('\
                        <b>'+data.erro+'</b><br />'
                    );
                <? } ?>
            }
        }
    });
}

function mascara(o,f){
    v_obj=o
    v_fun=f
    setTimeout("execmascara()",1)
}

function execmascara(){
    v_obj.value=v_fun(v_obj.value)
}

function mtel(v){
    v=v.replace(/\D/g,"");             //Remove tudo o que não é dí­gito
    v=v.replace(/^(\d{2})(\d)/g,"($1) $2"); //Coloca parênteses em volta dos dois primeiros dígitos
    v=v.replace(/(\d)(\d{4})$/,"$1-$2");    //Coloca hífen entre o quarto e o quinto dígitos
    return v;
}

function id( el ){
    return document.getElementById( el );
}

$(function(){
    Shadowbox.init();               

    $(document).on("click", ".btn-interacoes", function(){
        var fatItem =  $(this).data("fat-item");

        modal = Shadowbox.open({
                            content: "interacoes.php?tipo=LGR&reference_id="+fatItem+"&posto="+<?=$login_posto?>,
                            player: "iframe",
                            title: "Interações",
                            width: 1000,
                            height: 600
                            // options: {
                            //     onClose: function(){                            
                            //         var today = new Date();
                            //         today = today.getDate();
                            //         var checkLocal = localStorage.setItem("modalCheckInfo",today);
                            //     }   
                            // }                            
                        }); 
    });

})

</script>

<? include "rodape.php"; ?>
