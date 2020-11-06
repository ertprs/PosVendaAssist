<?php
// MLG 17-03-2011 - Tela para teste de leitura do campo NCM da NFe.
//                  Este campo vai ser obrigatório. Pendente de análise de
//                  de impacto e de implantação no banco de dados.
//OBS: ESTE ARQUIVO UTILIZA AJAX: form_nf_ret_ajax.php

include 'dbconfig.php';
// $dbnome = 'teste';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

//ini_set('display_errors','On');
//error_reporting(E_ALL ^ E_NOTICE);
/*
                            $ver_pre = "SELECT preco from tbl_tabela_item where peca = 780245";
                            $res_pre = pg_exec($con,$ver_pre);
                            if(pg_num_rows($res_pre) > 0){
                                $preco_peca     = pg_fetch_result($res_pre,0,preco);
                            }else{
                                $preco_peca     = 0;
                            }
echo $preco_peca; exit;
*/
$fabrica = 10;

// if (count($_POST)) {
//  echo '<pre>';print_r($_REQUEST);echo '</pre>';
//  echo '<pre>';print_r($_FILES); echo '</pre>';
//  exit;
// }

$faturamento    = $_POST["faturamento"];
if(strlen($faturamento)==0)
    $faturamento = $_GET["faturamento"];

$btn_acao= $_POST["btn_acao"];

$total_qtde_item= (strlen($_POST["total_qtde_item"]) > 0) ? $_POST["total_qtde_item"] : 5;

if(strlen($btn_acao)==0)
    $btn_acao = $_GET["btn_acao"];

$erro_msg_= $_GET["erro_msg"];
//SE NAO FOR O POSTO DE TESTE OU O DISTRIB.
if(($login_posto <> 6359) and ($login_posto <> 4311) and $login_posto <> 376542){
    echo "NÃO É PERMITIDO LANÇAR NOTA FISCAL - longin: $login_posto";
    exit;
}

$re_match_YMD   = '/(\d{4})\W?(\d{2})\W?(\d{2})/';
$re_match_DMY   = '/(\d{2})\W?(\d{2})\W?(\d{4})/';
$re_format_YMD  = '$3-$2-$1';
$re_format_DMY  = '$3/$2/$1';

//$peca_mais = array();
//$peca_sem_pedido = array();

$fornecedor_distrib          = (empty($_GET['fornecedor_distrib'])) ? trim($_POST['fornecedor_distrib']) : trim($_GET['fornecedor_distrib'])   ;
$fornecedor_distrib_fabrica  = (empty($_GET['fornecedor_distrib_fabrica'])) ? trim($_POST['fornecedor_distrib_fabrica']) : trim($_GET['fornecedor_distrib_fabrica'])   ;

if ($btn_acao == "Gravar") {

    if (!empty($fornecedor_distrib_fabrica)) {
        $fab_fornecedor_distrib = $fornecedor_distrib_fabrica;
    } else {
        $fab_fornecedor_distrib = $telecontrol_distrib;
    }

    $nota_fiscal= trim($_POST['nota_fiscal']);
    $total_nota = trim($_POST['total_nota']) ;
    $emissao    = trim($_POST["emissao"])    ;
    $saida      = trim($_POST['saida'])      ;
    $condicao   = trim($_POST['condicao'])   ;
    $cfop       = trim($_POST['cfop2'])       ;
    $serie      = trim($_POST['serie'])      ;
    $natureza   = trim($_POST['natureza'])   ;
    $garantia_compra = trim($_POST['garantia_compra']);
    $baixar_pendencia = trim($_POST['baixar_pendencia']);
    if($garantia_compra == "G"){
        $garantia_compra_aux = "Garantia";
    }elseif($garantia_compra == "C"){
        $garantia_compra_aux = "Compra";
    }else{
        $erro_msg = "Favor escolher se esta NF é de Garantia ou de Compra";
    }
    if($baixar_pendencia != "pedido" and $baixar_pendencia != "divergencia"){
        $erro_msg = "Favor escolher se a baixa é de pedido ou de divergência";
    }
    $transp     = substr($_POST['transportadora'],0,30);
    $fornecedor_distrib         = trim($_POST['fornecedor_distrib']);
    $fornecedor_distrib_fabrica = trim($_POST['fornecedor_distrib_fabrica']);
    $fornecedor_distrib_posto   = trim($_POST['fornecedor_distrib_posto']);
    $base_icms_substtituicao    = trim($_POST['base_icms_substtituicao']);
    $valor_icms_substtituicao   = trim($_POST['valor_icms_substtituicao']);
    if(strlen($base_icms_substtituicao)==0){
        $base_icms_substtituicao = 0;
    }

    if(strlen($valor_icms_substtituicao)==0){
        $valor_icms_substtituicao = 0;
    }

    if(strlen($nota_fiscal)==0)     $erro_msg .= "Digite a Nota Fiscal" ;
    if(strlen($emissao)==0)         $erro_msg .= "Digite a data de emissão $emissao<br>" ;
    if(strlen($saida)==0)           $erro_msg .= "Digite a Data de Saida<br>" ;
    if(strlen($total_nota)==0)      $erro_msg .= "Digite o Total da Nota<br>" ;
    if(strlen($cfop)==0)            $erro_msg .= "Digite o CFOP<br>";
    if(strlen($serie)==0)           $erro_msg .= "Digite o Número da Série<br>" ;
    if (!$fornecedor_distrib_posto) $erro_msg .= 'Fornecedor Desconhecido. Avise o Ger. Ronaldo para cadastrar o posto para que sirva de Fornecedor';
    if (!$fornecedor_distrib_fabrica) $erro_msg .= 'Fornecedor Desconhecido. Avise o Ger. Ronaldo para cadastrar o posto para que sirva de Fornecedor';
    if(strlen($natureza)==0)        $erro_msg .= "Digite a natureza da operação<br>" ;
    if(strlen($fornecedor_distrib)==0)  $erro_msg .= "Por favor escolha um fornecedor<br>" ;
    if(strlen($transp)==0)          $transp    = "";
    if(strlen($condicao)==0)        $condicao  = "null" ;

    $saida   = preg_replace($re_match_DMY, $re_format_YMD, $saida);
    $emissao = preg_replace($re_match_DMY, $re_format_YMD, $emissao);

    if(strlen($nota_fiscal) > 0) {
        $sql = "SELECT faturamento
        FROM tbl_faturamento
        WHERE fabrica     = $fabrica
        AND   posto       = $login_posto
        AND   nota_fiscal = '$nota_fiscal'
        AND   emissao     = '$emissao'
        ";
        $res = pg_query ($con,$sql);

        if(pg_num_rows($res)>0){
            $faturamento = trim(pg_fetch_result($res,0,faturamento));
            header ("Location: nf_cadastro.php?faturamento=$faturamento&erro_msg=Já foi Cadastrado a NF:$nota_fiscal");
            exit;
        }
    }

    if(strlen($erro_msg) == 0){
        $res = pg_query ($con,"BEGIN TRANSACTION");
        $sql= "INSERT INTO tbl_faturamento
            (fabrica          ,
            emissao           ,
            conferencia       ,
            saida             ,
            posto             ,
            distribuidor      ,
            total_nota        ,
            cfop              ,
            nota_fiscal       ,
            serie             ,
            transp            ,
            natureza          ,
            obs               ,
            base_icms_substtituicao,
            valor_icms_substtituicao
        )VALUES (
            $fabrica,
            '$emissao'          ,
            CURRENT_TIMESTAMP   ,
            '$saida'            ,
            $login_posto        ,
            $fornecedor_distrib_posto ,
            $total_nota         ,
            '$cfop'             ,
            '$nota_fiscal'      ,
            '$serie'            ,
            '$transp'           ,
            '$natureza'         ,
            '$condicao'         ,
            $base_icms_substtituicao,
            $valor_icms_substtituicao
        )
        ;";
        //echo nl2br($sql); exit;
        $res = pg_query ($con,$sql);
        if (!is_resource($res)) $erro_msg.= "Erro ao INSERIR nova NF.";

        $somatoria_nota = 0;
        if(strlen($erro_msg) > 0){
            $res = pg_query ($con,"ROLLBACK TRANSACTION");
            $erro_msg="<br>Erro ao inserir a NF:$nota_fiscal<br>$erro_msg";
        }else{
            $res = pg_query ($con,"SELECT CURRVAL ('seq_faturamento') as fat;");
            $faturamento =trim (pg_fetch_result($res, 0 , fat));
            $file_log    = fopen ('../nfephp2/log_nf_entrada.txt',"a");
            if($dbnome=="telecontrol_testes"){
                $file_log      = fopen ('log_nf_entrada.txt',"w");
            }
            $mensagem_peca_log = "";
            $mensagem_aux_cab = "<table border='1'>
                                <tr>
                                    <td align='center'>NF entrada</td>
                                    <td align='center'>Tipo Entrada</td>
                                    <td align='center'>Emissão</td>
                                    <td align='center'>CFOP</td>
                                    <td align='center'>Natureza</td>
                                    <td align='center'>Peça</td>
                                    <td align='right'>Preço NF</td>
                                    <td align='right'>Preço Tabela</td>
                                    <td align='center'>Pedido/Divergência</td>
                                    <td align='right'>Qtde baixada</td>
                                    <td align='left'>OBS</td>
                                </tr>";
            $mensagem_aux_rod = "</table>";

            for($i=0; $i< $total_qtde_item; $i++){
                $erro_item  = "" ;
                $referencia = $_POST["referencia"]; $referencia = $referencia[$i];
                $referencia = trim($referencia);
                $descricao  = $_POST["descricao"]; $descricao = $descricao[$i];
                $qtde       = $_POST["qtde"]; $qtde = $qtde[$i];
                $preco      = $_POST["preco"]; $preco = $preco[$i];
                $cfop       = $_POST["cfop"]; $cfop = $cfop[$i];
                $pedido     = $_POST["pedido"]; $pedido = $pedido[$i] ;
                $aliq_icms  = $_POST["aliq_icms"]; $aliq_icms = $aliq_icms[$i] ;
                $aliq_ipi   = $_POST["aliq_ipi"]; $aliq_ipi = $aliq_ipi[$i] ;
                $base_icms  = $_POST["base_icms"]; $base_icms = $base_icms[$i] ;
                $base_ipi   = $_POST["base_ipi"]; $base_ipi = $base_ipi[$i] ;
                $valor_ipi  = $_POST["valor_ipi"]; $valor_ipi = $valor_ipi[$i] ;
                $valor_icms = $_POST["valor_icms"]; $valor_icms = $valor_icms[$i] ;
                $ncm        = $_POST["ncm"]; $ncm = $ncm[$i] ;
                //HD 141162 Daniel
                $somatoria_nota += ($preco * $qtde) + str_replace(",",".",$valor_ipi);

                if(strlen($referencia)>0){
                    $sql = "SELECT  peca,
                            referencia,
                            descricao,
                            ncm,
                            fabrica
                            FROM   tbl_peca
                            WHERE  fabrica in ($fab_fornecedor_distrib)
                            AND    (referencia = '$referencia' OR referencia_fabrica = '$referencia');";
                    $res = pg_query ($con,$sql);
                    //print_r($res);
                    if(pg_num_rows($res)>0){
                        $peca       = trim(pg_fetch_result($res,0,'peca'));
                        $referencia = trim(pg_fetch_result($res,0,'referencia'));
                        $descricao  = trim(pg_fetch_result($res,0,'descricao'));
                        $ncm_peca   = trim(pg_fetch_result($res,0,'ncm'));
                        $xfabrica   = trim(pg_fetch_result($res,0,'fabrica'));

                        if ($ncm and !$ncm_peca) {
                            $sql_ncm = "UPDATE tbl_peca SET ncm='$ncm' WHERE peca = $peca";
                            $res_ncm = pg_query($con, $sql_ncm);
                            if (!is_resource($res_ncm)) {
                                $erro_item .= "Erro ao inserir o NCM da peça $referencia para '$ncm'.<br>";
                            } else {
                                if (pg_affected_rows($res_ncm) != 1) $erro_item .= "Erro ao atualizar o NCM da peça $referencia para '$ncm'.<br>";
                            }
                        }
                    }else{
                        //Caso não esteja cadastrado como peça ele irá procurar como Produto
                        $sql = "SELECT  produto   ,
                                referencia,
                                descricao ,
                                ipi       ,
                                origem    ,
                                fabrica
                                FROM   tbl_produto
                                JOIN   tbl_linha USING(linha)
                                WHERE  fabrica IN ($fab_fornecedor_distrib)
                                AND    referencia = '$referencia';";
                        $res = pg_query ($con,$sql);
                        if(pg_num_rows($res)>0){
                            $xproduto      = trim(pg_fetch_result($res,0,'produto'));
                            $xreferencia   = trim(pg_fetch_result($res,0,'referencia'));
                            $xdescricao    = trim(pg_fetch_result($res,0,'descricao'));
                            $xdescricao    = substr($xdescricao,0,50);
                            $xipi          = trim(pg_fetch_result($res,0,'ipi'));
                            $xorigem       = trim(pg_fetch_result($res,0,'origem'));
                            $xfabrica      = trim(pg_fetch_result($res,0,'fabrica'));
                            if(strlen($xipi)==0) $xipi = 0;
                            $sql = "INSERT INTO tbl_peca (
                                        fabrica,
                                        referencia,
                                        descricao,
                                        ipi,
                                        origem,
                                        ncm,
                                        produto_acabado
                                    ) VALUES (
                                        $xfabrica           ,
                                        '$xreferencia'      ,
                                        '$xdescricao'       ,
                                        $xipi               ,
                                        'NAC'               ,
                                        '$ncm'              ,
                                        't'
                                )" ;
                            $res = @pg_query($con,$sql);

                            if(strlen($erro_item) == 0) {
                                $sql = "SELECT CURRVAL ('seq_peca')";
                                $res = pg_query($con,$sql);
                                $peca = trim (pg_fetch_result($res, 0 , 0));
                            }else{
                                $erro_item .="Erro ao inserir peça $xreferencia<br>";
                            }
                        }else{
                            $erro_item .= "Peça $referencia não encontrada!*<br>" ;
                        }
                    }

                    if(!empty($peca)){
                        $sql = "SELECT tabela FROM tbl_tabela WHERE fabrica = $xfabrica AND tabela_garantia IS NOT TRUE LIMIT 1";
                        $res = pg_query($con,$sql);
                        if(pg_num_rows($res) > 0){
                            $tabela_venda = pg_fetch_result($res, 0, 'tabela');

                            $sql = "SELECT preco FROM tbl_tabela_item WHERE peca = $peca AND tabela = $tabela_venda";
                            $res = pg_query($con,$sql);

                            $novo_preco = $preco * 1.6;
                            if(pg_num_rows($res) == 0){
                                $sql = "INSERT INTO tbl_tabela_item(
                                                                    tabela,
                                                                    peca,
                                                                    preco)VALUES(
                                                                    $tabela_venda,
                                                                    $peca,
                                                                    $novo_preco
                                                                    )";
                                $res = pg_query($con,$sql);
                            }
                        }

                        $sql = "SELECT tabela FROM tbl_tabela WHERE fabrica = $xfabrica AND tabela_garantia IS TRUE LIMIT 1";
                        $res = pg_query($con,$sql);
                        if(pg_num_rows($res) > 0){
                            $tabela_garantia = pg_fetch_result($res, 0, 'tabela');

                            $sql = "SELECT preco FROM tbl_tabela_item WHERE peca = $peca AND tabela = $tabela_garantia";
                            $res = pg_query($con,$sql);

                            if(pg_num_rows($res) == 0){
                                $sql = "INSERT INTO tbl_tabela_item(
                                                                    tabela,
                                                                    peca,
                                                                    preco)VALUES(
                                                                    $tabela_garantia,
                                                                    $peca,
                                                                    $preco
                                                                    )";
                                $res = pg_query($con,$sql);
                            }
                        }
                    }

                    if(strlen($qtde)==0)  $erro_item.= "Digite a qtde<br>" ;
                    if(strlen($preco)==0) $erro_item.= "Digite o preço<br>";

                    if(strlen($pedido)==0){
                        $pedido      = "null";
                        $pedido_item = "null";
                    }

                    if(strlen($cfop)==0)       $cfop       = "0";
                    if(strlen($aliq_icms)==0)  $aliq_icms  = "0";
                    if(strlen($aliq_ipi)==0)   $aliq_ipi   = "0";
                    if(strlen($base_icms)==0)  $base_icms  = "0";
                    if(strlen($valor_icms)==0) $valor_icms = "0";
                    if(strlen($base_ipi)==0)   $base_ipi   = "0";
                    if(strlen($valor_ipi)==0)  $valor_ipi  = "0";
                    $base_icms  = str_replace(",",".",$base_icms);
                    $valor_icms = str_replace(",",".",$valor_icms);
                    $base_ipi   = str_replace(",",".",$base_ipi);
                    $valor_ipi  = str_replace(",",".",$valor_ipi);
                    $preco      = str_replace(",", "", number_format($preco, 2, ".", ","));

                    if(strlen($erro_item)==0){
                        $sql=  "INSERT INTO tbl_faturamento_item (
                            faturamento,
                            peca       ,
                            qtde       ,
                            preco      ,
                            cfop       ,
                            aliq_icms  ,
                            aliq_ipi   ,
                            base_icms  ,
                            valor_icms ,
                            base_ipi   ,
                            valor_ipi
                        )VALUES(
                            $faturamento,
                            $peca       ,
                            $qtde       ,
                            $preco      ,
                            $cfop       ,
                            $aliq_icms  ,
                            $aliq_ipi   ,
                            $base_icms  ,
                            $valor_icms ,
                            $base_ipi   ,
                            $valor_ipi
                        )";
                        $res = @pg_query ($con,$sql);
                        $erro_msg = pg_last_error($con);

                        if(strlen($erro_msg) > 0){
                            $erro_msg .=$erro_item . "<br>Erro ao inserir peça: $referencia";
                        }else{
                            $res = pg_query ($con,"SELECT CURRVAL ('seq_faturamento_item') as fat_item;");
                            $faturamento_item =trim (pg_fetch_result($res, 0 , fat_item));
                            //HD 793534
                            $ver_pre = "SELECT preco from tbl_tabela_item
                                        join tbl_tabela on tbl_tabela_item.tabela = tbl_tabela.tabela
                                        and tbl_tabela.fabrica = $fornecedor_distrib_fabrica
                                        and tbl_tabela.tabela_garantia IS TRUE
                                        where peca = $peca";
                            $res_pre = pg_exec($con,$ver_pre);
                            if(pg_num_rows($res_pre) > 0){
                                $preco_peca     = pg_fetch_result($res_pre,0,preco);
                            }else{
                                $preco_peca     = ($garantia_compra == "G") ? 10000.00 : 0;
                            }
                            if($preco_peca == 0){
                                $mensagem_aux = "<tr>
                                                        <td align='center'>$nota_fiscal</td>
                                                        <td align='center'>$garantia_compra_aux</td>
                                                        <td align='center'>$emissao</td>
                                                        <td align='center'>$cfop</td>
                                                        <td align='center'>$natureza</td>
                                                        <td align='center'>$referencia</td>
                                                        <td align='right'>".number_format($preco,4,',','.')."</td>
                                                        <td align='right' bgcolor='#FF9999'>".number_format($preco_peca,2,',','.')."</td>
                                                        <td align='center'>&nbsp;</td>
                                                        <td align='right'>&nbsp;</td>
                                                        <td align='left' bgcolor='#FF9999'>A NF foi recebida e o valor da peça não tem na tabela de preço. Favor arrumar para que as NFs de Saída (Garantita/Venda) não saiam com valores zerados.</td>
                                                    </tr>";
                                fputs ($file_log,$mensagem_aux);
                                $mensagem_peca_log .= $mensagem_aux;

                            }else if($garantia_compra == "G" and pg_num_rows($res_pre) == 0 ){

                                $sql_preco = "INSERT INTO tbl_tabela_item 
                                                (tabela, peca, preco) SELECT tabela, $peca, $preco_peca FROM tbl_tabela WHERE fabrica = {$fornecedor_distrib_fabrica} AND tabela_garantia IS TRUE LIMIT 1";
                                $res_preco = pg_query($con, $sql_preco);

                                if(strlen(pg_last_error()) > 0){

                                    $erro_msg .= $erro_msg . "<br /> Erro ao inserir preço de garantia para a peça {$peça}";

                                }

                            }

                            if($preco_peca != $preco and $garantia_compra == 'G'){
                                $mensagem_aux = "<tr>
                                                        <td align='center'>$nota_fiscal</td>
                                                        <td align='center'>$garantia_compra_aux</td>
                                                        <td align='center'>$emissao</td>
                                                        <td align='center'>$cfop</td>
                                                        <td align='center'>$natureza</td>
                                                        <td align='center'>$referencia</td>
                                                        <td align='right'>".number_format($preco,4,',','.')."</td>
                                                        <td align='right' bgcolor='#FF9999'>".number_format($preco_peca,2,',','.')."</td>
                                                        <td align='center'>&nbsp;</td>
                                                        <td align='right'>&nbsp;</td>
                                                        <td align='left' bgcolor='#FF9999'>A NF foi recebida e o valor da peça está com o valor diferenta da tabela de preço. Favor verificar para que não tenhamos problemas com a apuração de ICMS. </td>
                                                    </tr>";
                                fputs ($file_log,$mensagem_aux);
                                $mensagem_peca_log .= $mensagem_aux;
                            }

                            $diferenca = 0.4;
                            $porcentagem = "60%";

                            if (round($preco,2) > round(($preco_peca * $diferenca),2) and $garantia_compra == 'C'){
                                $mensagem_aux = "<tr>
                                                        <td align='center'>$nota_fiscal</td>
                                                        <td align='center'>$garantia_compra_aux</td>
                                                        <td align='center'>$emissao</td>
                                                        <td align='center'>$cfop</td>
                                                        <td align='center'>$natureza</td>
                                                        <td align='center'>$referencia</td>
                                                        <td align='right'>".number_format($preco,4,',','.')."</td>
                                                        <td align='right' bgcolor='#FF9999'>".number_format($preco_peca,2,',','.')."</td>
                                                        <td align='center'>&nbsp;</td>
                                                        <td align='right'>&nbsp;</td>
                                                        <td align='left'>A NF foi recebida e o valor da peça está com um percentual de desconto menor que $porcentagem. Favor verificar para que não tenhamos prejuízos na operação do DISTRIB.</td>
                                                    </tr>";
                                fputs ($file_log,$mensagem_aux);
                                $mensagem_peca_log .= $mensagem_aux;
                            }
                        }
                        if($baixar_pendencia=='pedido'){
                            $posto = $login_posto;
                            if($fornecedor_distrib_fabrica == 81) {
                                if($garantia_compra=="G"){
                                    $tipo_pedido = 154;
                                }
                                if($garantia_compra=="C"){
                                    $tipo_pedido = 153;
                                }
                            }elseif($fornecedor_distrib_fabrica == '51'){
                                if($garantia_compra=="G"){
                                    $tipo_pedido = 132;
                                }
                                if($garantia_compra=="C"){
                                    $tipo_pedido = 131;
                                }
                                $posto = "4311,20682";
                            }elseif($fornecedor_distrib_fabrica == '114'){
                                if($garantia_compra=="G"){
                                    $tipo_pedido = 235;
                                }
                                if($garantia_compra=="C"){
                                    $tipo_pedido = 234;
                                }
                            } else if ($fornecedor_distrib_fabrica == '122') {
                                if($garantia_compra=="G"){
                                    $tipo_pedido = 247;
                                }
                                if($garantia_compra=="C"){
                                    $tipo_pedido = 246;
                                }
                            }else{
                                if($garantia_compra=="G"){
                                    $sql = "SELECT tipo_pedido FROM tbl_tipo_pedido where fabrica = $fornecedor_distrib_fabrica and pedido_em_garantia";
                                }
                                if($garantia_compra=="C"){
                                    $sql = "SELECT tipo_pedido FROM tbl_tipo_pedido where fabrica = $fornecedor_distrib_fabrica and pedido_faturado";
                                }
                                $resp= pg_query($con,$sql);
                                $tipo_pedido = pg_fetch_result($resp,0,0);
                            }

                // Baixar pedidos dos postos Telecontrol e Acácia e somente de reposição de estoque
                $posto = '4311, 20682';

                            $sqlp = "SELECT tbl_pedido.pedido, tbl_pedido_item.pedido_item, tbl_pedido_item.qtde, tbl_pedido_item.qtde_faturada
                                    FROM tbl_pedido
                                    JOIN tbl_pedido_item USING (pedido)
                                    WHERE tbl_pedido.posto in ($posto)
                    AND fn_retira_especiais(tbl_pedido.pedido_cliente) = 'REPOSICAO ESTOQUE'
                                    and   tbl_pedido_item.qtde > ( tbl_pedido_item.qtde_cancelada + tbl_pedido_item.qtde_faturada )
                                    AND   tbl_pedido.fabrica = $fornecedor_distrib_fabrica
                                    AND   tbl_pedido.tipo_pedido = $tipo_pedido
                                    AND   tbl_pedido_item.peca   = $peca
                                    ORDER BY tbl_pedido.data ASC";

                            $resp = pg_query($con,$sqlp);
                            $total_qtde = $qtde;
                            $total_qtde = number_format($total_qtde,0,".","");

                            if(pg_num_rows($resp) > 0){
                                for($k =0;$k<pg_num_rows($resp);$k++) {
                                    $pedido        = pg_fetch_result($resp,$k,pedido);
                                    $pedido_item   = pg_fetch_result($resp,$k,pedido_item);
                                    $qtde_peca     = pg_fetch_result($resp,$k,qtde);
                                    $qtde_faturada = pg_fetch_result($resp,$k,qtde_faturada);

                                    //echo " $referencia $total_qtde $qtde_peca $pedido <br>";
                                    if(($total_qtde - $qtde_peca) >= 0) {
                                        $qtde_faturada_atualiza = $qtde_peca - $qtde_faturada;

                                        if($qtde_faturada_atualiza > 0) {
                                            $sqlq = " SELECT fn_atualiza_pedido_item_distrib($peca,$pedido,$pedido_item,$qtde_faturada_atualiza) ";
                                            $resq = pg_query($con,$sqlq);
                                            /*
                                            $mensagem_aux = "<tr>
                                                        <td align='center'>$nota_fiscal</td>
                                                        <td align='center'>$garantia_compra_aux</td>
                                                        <td align='center'>$emissao</td>
                                                        <td align='center'>$cfop</td>
                                                        <td align='center'>$natureza</td>
                                                        <td align='center'>$referencia</td>
                                                        <td align='right'>".number_format($preco,2,',','.')."</td>
                                                        <td align='right'>".number_format($preco_peca,2,',','.')."</td>
                                                        <td align='center' bgcolor='#FF9999'>$pedido</td>
                                                        <td align='right' bgcolor='#FF9999'>$qtde_faturada_atualiza</td>
                                                        <td align='left'>A NF baixou o pedido com esta quantidade.</td>
                                                    </tr>";
                                            fputs ($file_log,$mensagem_aux);
                                            $mensagem_peca_log .= $mensagem_aux;
                                            */

                                            $sqlFaturamento = "UPDATE tbl_faturamento_item 
                                            SET pedido = $pedido, pedido_item = $pedido_item 
                                            WHERE faturamento_item = $faturamento_item";

                                            pg_query($con, $sqlFaturamento);

                                            $sql_at = "INSERT INTO tbl_faturamento_item_baixa_pedido
                                                        ( faturamento_item, pedido, pedido_item, qtde_baixada )
                                                        VALUES
                                                        ( $faturamento_item, $pedido, $pedido_item, $qtde_faturada_atualiza);";
                                            $res_at = pg_query($con,$sql_at);

                                        }
                                    }else{
                                        if($total_qtde > 0) {
                                            $qtde_faturada_atualiza = $total_qtde;
                                            $sqlq = " SELECT fn_atualiza_pedido_item_distrib($peca,$pedido,$pedido_item,$qtde_faturada_atualiza) ";
                                            $resq = pg_query($con,$sqlq);
                                            /*
                                            $mensagem_aux = "<tr>
                                                        <td align='center'>$nota_fiscal</td>
                                                        <td align='center'>$garantia_compra_aux</td>
                                                        <td align='center'>$emissao</td>
                                                        <td align='center'>$cfop</td>
                                                        <td align='center'>$natureza</td>
                                                        <td align='center'>$referencia</td>
                                                        <td align='right'>".number_format($preco,2,',','.')."</td>
                                                        <td align='right'>0,00</td>
                                                        <td align='center' bgcolor='#FF9999'>$pedido</td>
                                                        <td align='right' bgcolor='#FF9999'>$qtde_faturada_atualiza</td>
                                                        <td align='left'>A NF baixou o pedido com esta quantidade.</td>
                                                    </tr>";
                                            fputs ($file_log,$mensagem_aux);
                                            $mensagem_peca_log .= $mensagem_aux;
                                            */
                                            $sqlFaturamento = "UPDATE tbl_faturamento_item 
                                            SET pedido = $pedido, pedido_item = $pedido_item 
                                            WHERE faturamento_item = $faturamento_item";

                                            pg_query($con, $sqlFaturamento);

                                            $sql_at = "INSERT INTO tbl_faturamento_item_baixa_pedido
                                                        ( faturamento_item, pedido, pedido_item, qtde_baixada )
                                                        VALUES
                                                        ( $faturamento_item, $pedido, $pedido_item, $qtde_faturada_atualiza);";
                                            $res_at = pg_query($con,$sql_at);
                                        }
                                    }

                                    $total_qtde -=$qtde_faturada_atualiza;
                                    if($total_qtde <= 0) {
                                        break;
                                    }
                                }
                                $sqlq = " SELECT fn_atualiza_status_pedido($fornecedor_distrib_fabrica,$pedido)";
                                $resq = pg_query($con,$sqlq);

                                if($total_qtde > 0) {
                                    $mensagem_aux = "<tr>
                                                <td align='center'>$nota_fiscal</td>
                                                <td align='center'>$garantia_compra_aux</td>
                                                <td align='center'>$emissao</td>
                                                <td align='center'>$cfop</td>
                                                <td align='center'>$natureza</td>
                                                <td align='center'>$referencia</td>
                                                <td align='right'>".number_format($preco,4,',','.')."</td>
                                                <td align='right'>".number_format($preco_peca,2,',','.')."</td>
                                                <td align='center'>&nbsp;</td>
                                                <td align='right' bgcolor='#FF9999'>$total_qtde</td>
                                                <td align='left'>A NF não conseguiu baixar automaticamente porque acabou os pedidos.</td>
                                            </tr>";
                                    fputs ($file_log,$mensagem_aux);
                                    $mensagem_peca_log .= $mensagem_aux;
                                    //array_push($peca_mais,$peca);
                                }

                                $sqlp = " SELECT peca
                                        FROM tbl_pedido
                                        JOIN tbl_pedido_item USING(pedido)
                                        WHERE tbl_pedido_item.peca = $peca
                                        AND   fabrica = $fornecedor_distrib_fabrica";
                                $resp = pg_query($con,$sqlp);
                                if(pg_num_rows($resp) == 0){
                                    $mensagem_aux = "<tr>
                                                <td align='center'>$nota_fiscal</td>
                                                <td align='center'>$garantia_compra_aux</td>
                                                <td align='center'>$emissao</td>
                                                <td align='center'>$cfop</td>
                                                <td align='center'>$natureza</td>
                                                <td align='center'>$referencia</td>
                                                <td align='right'>".number_format($preco,4,',','.')."</td>
                                                <td align='right'>".number_format($preco_peca,2,',','.')."</td>
                                                <td align='center'>&nbsp;</td>
                                                <td align='right' bgcolor='#FF9999'>$total_qtde</td>
                                                <td align='left'>A NF não conseguiu baixar automaticamente a quantidade porque não existe pedido.</td>
                                            </tr>";
                                    fputs ($file_log,$mensagem_aux);
                                    $mensagem_peca_log .= $mensagem_aux;
                                    //array_push($peca_sem_pedido,$peca);
                                }
                            }else{
                                $mensagem_aux = "<tr>
                                            <td align='center'>$nota_fiscal</td>
                                            <td align='center'>$garantia_compra_aux</td>
                                            <td align='center'>$emissao</td>
                                            <td align='center'>$cfop</td>
                                            <td align='center'>$natureza</td>
                                            <td align='center'>$referencia</td>
                                            <td align='right'>".number_format($preco,4,',','.')."</td>
                                            <td align='right'>".number_format($preco_peca,2,',','.')."</td>
                                            <td align='center'>&nbsp;</td>
                                            <td align='right' bgcolor='#FF9999'>$total_qtde</td>
                                            <td align='left'>A NF não encontrou nenhum pedido para baixar.</td>
                                        </tr>";
                                fputs ($file_log,$mensagem_aux);
                                $mensagem_peca_log .= $mensagem_aux;
                            }
                        }else{
                            //baixar divergencia
                            $sql_div = "SELECT tbl_faturamento_item.faturamento, tbl_faturamento.nota_fiscal, faturamento_item, qtde_quebrada, sum(qtde_acerto) as qtde_acerto_total
                                        FROM tbl_faturamento_item
                                        JOIN tbl_faturamento using(faturamento)
                                        LEFT JOIN tbl_faturamento_baixa_divergencia using(faturamento_item)
                                        WHERE peca = $peca
                                        AND    qtde_quebrada > 0
                                        GROUP BY tbl_faturamento_item.faturamento, tbl_faturamento.nota_fiscal, faturamento_item, qtde_quebrada
                                        ORDER BY tbl_faturamento_item.faturamento;";
                            $res_div = pg_query($con, $sql_div);

                            $total_qtde = $qtde;
                            $total_qtde = number_format($total_qtde,0,".","");

                            if(pg_num_rows($res_div) > 0){
                                for($m =0;$m<pg_num_rows($res_div);$m++) {
                                    $faturamento_item_div  = pg_fetch_result($res_div,$m,faturamento_item);
                                    $qtde_quebrada_div     = pg_fetch_result($res_div,$m,qtde_quebrada);
                                    $nota_fiscal_div       = pg_fetch_result($res_div,$m,nota_fiscal);
                                    $qtde_acerto_total_div = pg_fetch_result($res_div,$m,qtde_acerto_total);
                                    if(strlen($qtde_acerto_total_div)==0){
                                        $qtde_acerto_total_div = 0;
                                    }
                                    if($total_qtde>0){
                                        if($qtde_quebrada_div > $qtde_acerto_total_div){
                                            $qtde_acerto = $qtde_quebrada_div - $qtde_acerto_total_div;
                                            if($total_qtde > $qtde_acerto){
                                                $sql_div_x = "INSERT INTO tbl_faturamento_baixa_divergencia
                                                                ( faturamento_item     , qtde_acerto, nota_fiscal, serie, data )
                                                                VALUES
                                                                ( $faturamento_item_div, $qtde_acerto, $nota_fiscal, $serie, now() );";
                                                @$res_div_x = pg_query($con, $sql_div_x);
                                                $total_qtde -= $qtde_acerto;
                                                $mensagem_aux = "<tr>
                                                            <td align='center'>$nota_fiscal</td>
                                                            <td align='center'>$garantia_compra_aux</td>
                                                            <td align='center'>$emissao</td>
                                                            <td align='center'>$cfop</td>
                                                            <td align='center'>$natureza</td>
                                                            <td align='center'>$referencia</td>
                                                            <td align='right'>".number_format($preco,4,',','.')."</td>
                                                            <td align='right'>".number_format($preco_peca,2,',','.')."</td>
                                                            <td align='center' bgcolor='#FF9999'>$nota_fiscal_div</td>
                                                            <td align='right' bgcolor='#FF9999'>$qtde_acerto</td>
                                                            <td align='left'>A NF baixou divergência.</td>
                                                        </tr>";
                                                fputs ($file_log,$mensagem_aux);
                                                $mensagem_peca_log .= $mensagem_aux;
                                            }else{
                                                $sql_div_x = "INSERT INTO tbl_faturamento_baixa_divergencia
                                                                ( faturamento_item     , qtde_acerto, nota_fiscal, serie, data )
                                                                VALUES
                                                                ( $faturamento_item_div, $total_qtde, $nota_fiscal, $serie, now() );";
                                                @$res_div_x = pg_query($con, $sql_div_x);
                                                $mensagem_aux = "<tr>
                                                            <td align='center'>$nota_fiscal</td>
                                                            <td align='center'>$garantia_compra_aux</td>
                                                            <td align='center'>$emissao</td>
                                                            <td align='center'>$cfop</td>
                                                            <td align='center'>$natureza</td>
                                                            <td align='center'>$referencia</td>
                                                            <td align='right'>".number_format($preco,4,',','.')."</td>
                                                            <td align='right'>".number_format($preco_peca,2,',','.')."</td>
                                                            <td align='center' bgcolor='#FF9999'>$nota_fiscal_div</td>
                                                            <td align='right' bgcolor='#FF9999'>$total_qtde</td>
                                                            <td align='left'>A NF baixou divergência.</td>
                                                        </tr>";
                                                fputs ($file_log,$mensagem_aux);
                                                $mensagem_peca_log .= $mensagem_aux;
                                                $total_qtde = 0;
                                            }
                                        }
                                    }
                                }

                                if ($total_qtde > 0) {
                                    $mensagem_aux = "<tr>
                                                <td align='center'>$nota_fiscal</td>
                                                <td align='center'>$garantia_compra_aux</td>
                                                <td align='center'>$emissao</td>
                                                <td align='center'>$cfop</td>
                                                <td align='center'>$natureza</td>
                                                <td align='center'>$referencia</td>
                                                <td align='right'>".number_format($preco,4,',','.')."</td>
                                                <td align='right'>".number_format($preco_peca,2,',','.')."</td>
                                                <td align='center'>&nbsp;</td>
                                                <td align='right' bgcolor='#FF9999'>$total_qtde</td>
                                                <td align='left'>A NF ainda faltou divergências para baixar.</td>
                                            </tr>";
                                    fputs ($file_log,$mensagem_aux);
                                    $mensagem_peca_log .= $mensagem_aux;
                                }
                            }else{
                                $mensagem_aux = "<tr>
                                            <td align='center'>$nota_fiscal</td>
                                            <td align='center'>$garantia_compra_aux</td>
                                            <td align='center'>$emissao</td>
                                            <td align='center'>$cfop</td>
                                            <td align='center'>$natureza</td>
                                            <td align='center'>$referencia</td>
                                            <td align='right'>".number_format($preco,4,',','.')."</td>
                                            <td align='right'>".number_format($preco_peca,2,',','.')."</td>
                                            <td align='center'>&nbsp;</td>
                                            <td align='right' bgcolor='#FF9999'>$total_qtde</td>
                                            <td align='left'>A NF não encontrou divergência para baixar.</td>
                                        </tr>";
                                fputs ($file_log,$mensagem_aux);
                                $mensagem_peca_log .= $mensagem_aux;
                                $msg_erro = "Item de NF não encontrado";
                            }
                            //final baixar divergencia
                        }
                    }else{
                        $erro_msg .= $erro_item ;
                    }
                }

                if(strlen($erro_msg) > 0) {
                    break;
                }
            }

            $somatoria_nota += $valor_icms_substtituicao;

            $somatoria_nota = round($somatoria_nota,2);

            $somatoria_nota = trim(str_replace(".00","",$somatoria_nota));
            if ($somatoria_nota != $total_nota) {
                $erro_msg .= "Valor Total da Nota diferente do valor da somat&oacute;ria dos &iacute;tens da nota (soma do sistema $somatoria_nota) (total digitado $total_nota)<br>";
            }

            if(strlen($erro_msg)==0){
                
                include_once '../class/communicator.class.php'; // Classe para envio de e-mail.
                $externalId = 'smtp@posvenda';
                $mailTc = new TcComm($externalId);

                $res = pg_query ($con,"COMMIT TRANSACTION");
                $erro_email = "";
                
                if(strlen($mensagem_peca_log)> 0){

                    $nome         = "TELECONTROL";
                    $email_from   = "helpdesk@telecontrol.com.br";
                    $assunto      = "IMPORTANTE: NF $nota_fiscal entrada no DISTRIB ";
                    $destinatario = "helpdesk@telecontrol.com.br, jader.abdo@telecontrol.com.br, luis.carlos@telecontrol.com.br, eduardo.oliveira@telecontrol.com.br, eduardo.miranda@telecontrol.com.br";
                    //$destinatario = "ronaldo@telecontrol.com.br";
                    //$destinatario = "joao.santos@telecontrol.com.br"; 
                    $boundary     = "XYZ-" . date("dmYis") . "-ZYX";
                    if(in_array($fornecedor_distrib_fabrica, array(122,123,125))){
                        $mensagem = "<b>Este log envolve valores de apuração de ICMS e valores de compra / venda de peças (lucro).</b><br><br><b>Não será necessário alteração manual na tabela de preço, pois será automático.</b><br><br><br>";
                    }else{
                        $mensagem     = "Prezado,<br> verificar com carinho os logs abaixo! <br> Isto envolve valores de apuração de ICMS e valores de compra / venda de peças (lucro):<br><br>";
                    }
                    $mensagem .= $mensagem_aux_cab;
                    $mensagem .= $mensagem_peca_log;
                    $mensagem .= $mensagem_aux_rod;
                    $body_top = "--Message-Boundary\n";
                    $body_top .= "Content-type: text/html; charset=iso-8859-1\n";
                    $body_top .= "Content-transfer-encoding: 7BIT\n";
                    $body_top .= "Content-description: Mail message body\n\n";
                    $email = "ronaldo@telecontrol.com.br";
                    //echo "$destinatario,$assunto,$mensagem, From: $email_from $body_top ";

                    $res_send_mail = $mailTc->sendMail(
                        $destinatario,
                        $assunto,
                        $mensagem,
                        $email_from
                    );
            }
                /*
                if(count($peca_mais) > 0) {
                    foreach($peca_mais as $pecas){
                        $sql = "SELECT referencia,nome
                                FROM tbl_peca
                                JOIN tbl_fabrica USING(fabrica)
                                WHERE peca =".$pecas['peca'];
                        $res = pg_query($con,$sql);
                        if(pg_num_rows($res) > 0){
                            $mensagem_peca .=pg_fetch_result($res,0,referencia).",";
                            $fabrica_nome = pg_fetch_result($res,0,nome);
                        }
                    }

                    $nome         = "TELECONTROL";
                    $email_from   = "helpdesk@telecontrol.com.br";
                    $assunto      = "Peças Faturadas a Mais";
                    $destinatario ="ronaldo@telecontrol.com.br";
                    $boundary = "XYZ-" . date("dmYis") . "-ZYX";
                    $mensagem = "Prezado,<br> a(s) seguinte(s) peça(s) faturada(s) da $fabrica_nome tem a quantidade há mais que a pendência de pedido(s):<br>$mensagem_peca";
                    $body_top = "--Message-Boundary\n";
                    $body_top .= "Content-type: text/html; charset=iso-8859-1\n";
                    $body_top .= "Content-transfer-encoding: 7BIT\n";
                    $body_top .= "Content-description: Mail message body\n\n";
                    if(!empty($fabrica_nome)) {
                        if(!mail($destinatario,$assunto,$mensagem,"From: ".$email_from." \n $body_top ")){
                            $erro_email .= "Não enviou o email para o Ronaldo, favor copiar isto e enviar para ele <br> $mensagem_peca_log <br>";
                        }
                    }
                }
                 */
                 /*
                if(count($peca_sem_pedido) > 0) {
                    foreach($peca_sem_pedido as $pecas){
                        $sql = "SELECT referencia,nome
                                FROM tbl_peca
                                JOIN tbl_fabrica USING(fabrica)
                                WHERE peca =".$pecas['peca'];
                        $res = pg_query($con,$sql);
                        if(pg_num_rows($res) > 0){
                            $mensagem_peca .=pg_fetch_result($res,0,referencia).",";
                            $fabrica_nome = pg_fetch_result($res,0,nome);
                        }
                    }
                    $nome         = "TELECONTROL";
                    $email_from   = "helpdesk@telecontrol.com.br";
                    $assunto      = "Peças não encontradas";
                    $destinatario ="ronaldo@telecontrol.com.br";
                    $boundary = "XYZ-" . date("dmYis") . "-ZYX";
                    $mensagem = "Prezado,<br> a(s) seguinte(s) peça(s) faturada(s) da $fabrica_nome não foram encontradas nos pedidos pendentes:<br>$mensagem_peca";
                    $body_top = "--Message-Boundary\n";
                    $body_top .= "Content-type: text/html; charset=iso-8859-1\n";
                    $body_top .= "Content-transfer-encoding: 7BIT\n";
                    $body_top .= "Content-description: Mail message body\n\n";
                    if(!empty($fabrica_nome)) {
                        if(!mail($destinatario,$assunto,$mensagem,"From: ".$email_from." \n $body_top ")){
                            $erro_email .= "Não enviou o email para o Ronaldo, favor copiar isto e enviar para ele <br> $mensagem_peca_log <br>";
                        }
                    }
                }
                */
            }else{
                $res = pg_query ($con,"ROLLBACK TRANSACTION");
                $faturamento = "";
            }
        }//else erro inserir faturamento
    }
}//FIM BTN: GRAVAR

//ALTERAR
if ($btn_acao == "Alterar") {
    if(strlen($faturamento)==0){
        $erro_msg = "FATURAMENTO VAZIO!";
    } else {
        $sql = "SELECT faturamento
        FROM   tbl_faturamento
        WHERE posto       = $login_posto
        AND   faturamento = $faturamento";
        $res = pg_query ($con,$sql);

        if(pg_num_rows($res)==0){
            $erro_msg = "NÃO FOI ENCONTRADO O FATURAMENTO: $faturamento";
        }
    }

    if (!$erro_msg) {
        $faturamento= trim(pg_fetch_result($res,0,faturamento));

        if(strlen($nota_fiscal)==0)
            $erro_msg  .= "Digite a Nota Fiscal" ;

        $emissao=$_POST["emissao"];
        if(strlen($emissao)==0)
            $erro_msg  .= "Digite a data de emissão $emissao<br>" ;
        else
            $emissao    = preg_replace($re_match_DMY, $re_format_YMD, $emissao);

        $saida=$_POST['saida'];
        if(strlen($saida)==0)
            $erro_msg .= "Digite a Data de Saida<br>" ;
        else
            $saida = preg_replace($re_match_DMY, $re_format_YMD, $saida);

        $total_nota=$_POST['total_nota'];
        if(strlen($total_nota)==0)
            $erro_msg .= "Digite o Total da Nota<br>" ;
        else{
            $total_nota = str_replace(",",".",$total_nota);
            $total_nota = trim(str_replace(".00","",$total_nota));
        }

        $base_icms_substtituicao=$_POST['base_icms_substtituicao'];
        if(strlen($base_icms_substtituicao)==0){
            $base_icms_substtituicao = 0;
        }else{
            $base_icms_substtituicao = str_replace(",",".",$base_icms_substtituicao);
            $base_icms_substtituicao = trim(str_replace(".00","",$base_icms_substtituicao));
        }


        $valor_icms_substtituicao=$_POST['valor_icms_substtituicao'];
        if(strlen($valor_icms_substtituicao)==0)
            $valor_icms_substtituicao = 0;
        else{
            $valor_icms_substtituicao = str_replace(",",".",$valor_icms_substtituicao);
            $valor_icms_substtituicao = trim(str_replace(".00","",$valor_icms_substtituicao));
        }



        $cfop=$_POST['cfop'];
        if(strlen($cfop)==0)
            $erro_msg .= "Digite o CFOP<br>";

        $serie=$_POST['serie'];
        if(strlen($serie)==0)
            $erro_msg .= "Digite o Número da Série<br>" ;

        $transp= substr($_POST['transportadora'],0,30);
        if(strlen($transp)==0)
            $erro_msg .= "Escolha a Transportadora<br>";

        $condicao=$_POST['condicao'];
        if(strlen($condicao)==0)
        $erro_msg .= "Digite a Condição<br>" ;

        if(strlen($erro_msg)==0 ){
            $res = pg_query ($con,"BEGIN TRANSACTION");

            $sql = "UPDATE tbl_faturamento
            SET
                fabrica     = $fabrica,
                emissao     ='$emissao',
                saida       ='$saida',
                posto       = $login_posto,
                total_nota  = $total_nota,
                cfop        = $cfop,
                nota_fiscal ='$nota_fiscal',
                serie       = $serie,
                transp      ='$transp',
                condicao    = $condicao
            WHERE faturamento = $faturamento;";
            $res = @pg_query ($con,$sql);
            $erro_msg = pg_last_error($con);

            if(strlen($erro_msg) > 0){
                $res = pg_query ($con,"ROLLBACK TRANSACTION");
                $erro_msg.= "<br>Erro ao ALTERAR a NF:$nota_fiscal";
            }else{
                $somatoria_nota = 0;
                $somatoria_pecas = 0;

                //UPDATE ITENS DA NOTA
                for($i=0; $i< $total_qtde_item; $i++){
                    $erro_item  = "" ;

                    $faturamento_item   =$_POST["faturamento_item_$i"];
                    if(strlen($faturamento_item)==0)
                        $erro_item.= "Erro no Item do Faturamento<br>" ;

                    $referencia =$_POST["referencia_$i"];
                    if(strlen($referencia)>0){
                        $sql= "select peca, descricao
                        from tbl_peca
			where referencia = '$referencia' 
			OR referencia_fabrica = '$referencia';";
                        $res = pg_query ($con,$sql);
                        if(pg_num_rows($res)>0) {
                            $peca= trim(pg_fetch_result($res,0,peca));
                            $descricao  = trim(pg_fetch_result($res,0,descricao));
                        }else{
                            $erro_item .= "Peça $referencia não encontrada!<br>" ;
                        }

                        $qtde   =$_POST["qtde_$i"];
                        if(strlen($qtde)==0)
                        $erro_item.= "Digite a qtde<br>" ;

                        $preco=$_POST["preco_$i"];

                        $cfop=$_POST["cfop_$i"];

                        if(strlen($preco)==0){
                            $erro_item.= "Digite o preco<br>";
                        }else{
                            $preco = str_replace(",",".",$preco);
                            $preco= trim(str_replace(".00","",$preco));
                        }


                        $aliq_icms=$_POST["aliq_icms_$i"];
                        if(strlen($aliq_icms)==0)
                            $aliq_icms ="0";
                        else{
                            $aliq_icms = str_replace(",",".",$aliq_icms);
                            $aliq_icms = trim(str_replace(".00","",$aliq_icms));
                        }

                        $aliq_ipi=$_POST["aliq_ipi_$i"];
                        if(strlen($aliq_ipi)==0)
                            $aliq_ipi="0";
                        else{
                            $aliq_ipi = str_replace(",",".",$aliq_ipi);
                            $aliq_ipi = trim(str_replace(".00","",$aliq_ipi));
                        }

                        $base_icms=$_POST["base_icms_$i"];
                        if(strlen($base_icms)==0)
                            $base_icms ="0";
                        else{
                            $base_icms = str_replace(",",".",$base_icms);
                            $base_icms = trim(str_replace(".00","",$base_icms));
                        }

                        $valor_icms=$_POST["valor_icms_$i"];
                        if(strlen($valor_icms)==0)
                            $valor_icms ="0";
                        else{
                            $valor_icms = str_replace(",",".",$valor_icms);
                            $valor_icms = trim(str_replace(".00","",$valor_icms));
                        }

                        $base_ipi=$_POST["base_ipi_$i"];
                        if(strlen($base_ipi)==0)
                            $base_ipi ="0";
                        else{
                            $base_ipi = str_replace(",",".",$base_ipi);
                            $base_ipi = trim(str_replace(".00","",$base_ipi));
                        }

                        $valor_ipi=$_POST["valor_ipi_$i"];
                        if(strlen($valor_ipi)==0)
                            $valor_ipi ="0";
                        else{
                            $valor_ipi = str_replace(",",".",$valor_ipi);
                            $valor_ipi = trim(str_replace(".00","",$valor_ipi));
                        }
                        //HD 141162 Daniel
                            $somatoria_nota += ($preco * $qtde) + $valor_ipi;
                            //echo "preco:$preco qtde:$qtde valor.ipi:$valor_ipi somatoria:$somatoria_nota";
                        //HD
                        if(strlen($erro_item)==0){

                            $sql=  "UPDATE tbl_faturamento_item
                            SET
                                peca      =$peca,
                                qtde      =$qtde,
                                preco     =$preco,
                                cfop      =$cfop,
                                aliq_icms =$aliq_icms,
                                aliq_ipi  =$aliq_ipi,
                                base_icms =$base_icms,
                                valor_icms=$valor_icms,
                                base_ipi  =$base_ipi,
                                valor_ipi =$valor_ipi
                            WHERE faturamento_item = $faturamento_item;";
                            $res = pg_query ($con,$sql);
                            $erro_msg = pg_last_error($con);

                            if(strlen($erro_msg) > 0){
                                $erro_msg .=$erro_item . "<br>Erro ao inserir peça: $referencia (ítem " . ($i+1) . ")";
                            }
                        }else{
                            $erro_msg .= $erro_item ;
                        }
                    }
                    if(strlen($erro_msg) > 0 or strlen($erro_item) > 0) {
                        break;
                    }
                }//fim do for
                //HD 141162 Daniel
                $somatoria_nota += $valor_icms_substtituicao;
                $somatoria_nota = trim(str_replace(".00","",$somatoria_nota));
                if ($somatoria_nota != $total_nota) {
                    $erro_msg .= "Valor Total da Nota diferente do valor da somat&oacute;ria dos &iacute;tens da nota<br>";
                }


                if(strlen($erro_msg)==0 ){
                    $res = pg_query ($con,"COMMIT TRANSACTION");
                }else{
                    $res = pg_query ($con,"ROLLBACK TRANSACTION");
                    $faturamento = "";
                }
            }//else erro inserir faturamento
        }
    }
}//FIM BTN: GRAVAR

if ($btn_acao == "NFe") {   //  Processa NFe
    require_once 'includes/xml2array.php';   // Carrega as duas funções para tratar mais fácil com XML...
    if ($_FILES['Nfe_File']['tmp_name'] != '') {
        $xml_file = $_FILES['Nfe_File'];
        //      echo '<pre>'.var_dump($_FILES).'</pre>';
        if (strpos($_FILES['Nfe_File']['type'],'xml') !== false) {
            $arr_nf = xml2array($_FILES['Nfe_File']['tmp_name']);

            $nfeXml = simplexml_load_file($_FILES['Nfe_File']['tmp_name']);

            //          echo "<pre>";
            //          print_r($arr_nf);
            //          echo "</pre>";
            if (count($arr_nf) != 0) {
                $idx_nf         = "nfeProc/NFe/infNFe";
                $idx_nf_info    = $idx_nf."/ide";
                $idx_nf_emissor = $idx_nf."/emit";
                $idx_nf_destino = $idx_nf."/dest";
                $idx_nf_itens   = $idx_nf."/det";
                $nfe_id         = array_get_value($arr_nf,'nfeProc/NFe/infNFe_attr/Id');
                $cnpj_distrib   = array_get_value($arr_nf,$idx_nf_destino.'/CNPJ');

                //  Dados da NF:
                $fornecedor_cnpj    = array_get_value($arr_nf, $idx_nf_emissor.'/CNPJ');
                $fornecedor_distrib = array_get_value($arr_nf, $idx_nf_emissor.'/xNome');
                $nota_fiscal= array_get_value($arr_nf, $idx_nf_info.'/nNF');
                $total_nota = array_get_value($arr_nf, $idx_nf.'/total/ICMSTot/vNF');
                $emissao    = array_get_value($arr_nf, $idx_nf_info.'/dhEmi');
                $emissao_bd = $emissao;
                $emissao    = preg_replace($re_match_YMD, $re_format_DMY, $emissao);
				$emissao = explode('T', $emissao);
				$emissao = $emissao[0];
                $saida      = $emissao;
                $serie      = array_get_value($arr_nf, $idx_nf_info.'/serie');
                $natureza   = array_get_value($arr_nf, $idx_nf_info.'/natOp');
                $transp     = array_get_value($arr_nf, $idx_nf.'/transp/transporta/xNome');
                $base_icms_substtituicao  = array_get_value($arr_nf, $idx_nf.'/total/ICMSTot/vBCST');
                $valor_icms_substtituicao = array_get_value($arr_nf, $idx_nf.'/total/ICMSTot/vST');
                $pedido     = array_get_value($arr_nf, $idx_nf_info.'/compra/xPed');

        $sql_f = "SELECT posto, nome, tbl_posto_extra.fornecedor_distrib_fabrica
                  FROM tbl_posto JOIN tbl_posto_extra USING(posto)
                  WHERE tbl_posto_extra.fornecedor_distrib IS TRUE
                  AND cnpj = '$fornecedor_cnpj' ";
        $res_f = pg_query($con, $sql_f);
        if (!is_resource ($res_f)) {
            $erro_msg .= 'Erro ao pesquisar o Fornecedor. Tente novamente. Se continuar o erro, avise a Equipe Telecontrol.';
        } else {
            if (@pg_num_rows($res_f) == 1) {
                $fornecedor_distrib_posto   = pg_fetch_result($res_f, 0, posto);
                $fornecedor_distrib_fabrica = pg_fetch_result($res_f, 0, fornecedor_distrib_fabrica);
            //              $fornecedor_distrib         = pg_fetch_result($res_f, 0, nome); // Substitui a razão social que vem na NFe pela do banco
            } else {
                $erro_msg .= 'Fornecedor esconhecido. Avise o Ger. Ronaldo para cadastrar na Fábrica Telecontrol o posto para que sirva de Fornecedor';
            }
        }


                //  Valida o CNPJ do emissor...
                $sql = "SELECT posto,nome
                        FROM tbl_posto
                        LEFT JOIN tbl_posto_extra using(posto)
                        WHERE tbl_posto_extra.fornecedor_distrib IS TRUE
                          AND cnpj = '$fornecedor_cnpj'";
                $res = @pg_query($con,$sql);
                if (!is_resource($res)) $erro_msg = "<p>ERRO NA CONSULTA DE FORNECEDOR!</p><p>".pg_last_error($con).'</p>';
                if (is_resource($res) and @pg_num_rows($res)==0) {
                    $erro_msg = "Fornecedor com CNPJ $fornecedor_cnpj não encontrado. Contate com o gerente Ronaldo.";
                }


                if(strlen($nota_fiscal) > 0 and $fornecedor_distrib_fabrica) {
                    $sql = "SELECT faturamento
                    FROM tbl_faturamento
                    WHERE fabrica     = $fabrica
                    AND   posto       = $login_posto
                    AND   nota_fiscal = '$nota_fiscal'
                    AND   emissao     = '$emissao_bd'
                    ";
                    $res = pg_query($con,$sql);

                    if(pg_num_rows($res)>0){
                        $faturamento = trim(pg_fetch_result($res,0,faturamento));
                        header ("Location: nf_cadastro.php?faturamento=$faturamento&erro_msg=Já foi Cadastrado a NF:$nota_fiscal");
                        exit;
                    }
                }

                $nfe_itens = array();
                $i = 0;

                foreach ($nfeXml->NFe->infNFe->det as $item) {
                    $nfe_itens[$i]['referencia'] = $item->prod->cProd;
                    $nfe_itens[$i]['descricao'] = $item->prod->xProd;
                    $nfe_itens[$i]['NCM'] = $item->prod->NCM;
                    $nfe_itens[$i]['qtde'] = $item->prod->qCom;
                    $nfe_itens[$i]['preco'] = $item->prod->vUnCom;
                    $nfe_itens[$i]['cfop'] = $item->prod->CFOP;

                    $icms = array_values(get_object_vars($item->imposto->ICMS));
                    $ipi = array_values(get_object_vars($item->imposto->IPI));

                    $nfe_itens[$i]['base_icms'] = (!empty($icms[0]->vBC)) ? $icms[0]->vBC : '0';
                    $nfe_itens[$i]['aliq_icms'] = (!empty($icms[0]->pICMS)) ? $icms[0]->pICMS : '0';
                    $nfe_itens[$i]['valor_icms'] = (!empty($icms[0]->vICMS)) ? $icms[0]->vICMS : '0';

                    $nfe_itens[$i]['base_ipi'] = (!empty($ipi[1]->vBC)) ? $ipi[1]->vBC : '0';
                    $nfe_itens[$i]['aliq_ipi'] = (!empty($ipi[1]->pIPI)) ? $ipi[1]->pIPI : '0';
                    $nfe_itens[$i]['valor_ipi'] = (!empty($ipi[1]->vIPI)) ? $ipi[1]->vIPI : '0';

                    $somatoria_nota += ($nfe_itens[$i]['preco'] * $nfe_itens[$i]['qtde']) + $nfe_itens[$i]['valor_ipi'];

                    $i++;
                }


                $cfop = $nfe_itens[--$i]['cfop']; // Por enquanto...
//              echo "CFOP da NF: $cfop<br>";
                // Guardando o XML de NF de entrada.
                $sql_data = "select to_char(current_date, 'mm') as mes, to_char(current_date, 'yyyy') as ano;";
                $res_data = pg_exec($con,$sql_data);
                $mes = pg_result($res_data,0,mes);
                $ano = pg_result($res_data,0,ano);

                $diretorio_entrada = "../nfephp2/entrada/".$mes.$ano;
                if($dbnome=="telecontrol_testes"){
                    $diretorio_entrada = "../entrada/".$mes.$ano;
                }
                if( !is_dir($diretorio_entrada) ) {
                    if( !mkdir($diretorio_entrada, 0777 ) ){
                        $err_msg = "Não foi possível mover o XML da NF de entrada para o diretório $diretorio_entrada ";
                    }
                }
                if(!move_uploaded_file($_FILES['Nfe_File']['tmp_name'], $diretorio_entrada."/".$_FILES['Nfe_File']['name'])){
                    $erro_msg = "Não foi possível fazer a cópia do XML para o diretório de entrada: $diretorio_entrada";
                }
            } else {
                $erro_msg = 'Não foi possível interpretar o arquivo '.$xml_file['name'];
            }
        } else {
            $erro_msg = 'O arquivo '.$xml_file['name'].' não parece um XML.';
        }
    } else {
        $erro_msg = 'Arquivo XML não recebido';
    }
}// FIM Processa NFe enviada pela Britânia

$title = "Cadastro de Nota Fiscal";
?>

<html>

<title><?php echo $title ?></title>
<link type="text/css" rel="stylesheet" href="css/css.css">
<head>

<script language='javascript' src='../ajax.js'></script>
<?include "javascript_calendario_new.php"; ?>
<script type='text/javascript' src='js/jquery.autocomplete.js'></script>
<link rel="stylesheet" type="text/css" href="js/jquery.autocomplete.css" />
<script type='text/javascript' src='js/jquery.bgiframe.min.js'></script>
<script type='text/javascript' src='js/dimensions.js'></script>
<!-- <script type="text/javascript" src="../admin/js/jquery.maskmoney.js"></script> -->
<script type="text/javascript" src="js/thickbox.js"></script>
<link rel="stylesheet" href="js/thickbox.css" type="text/css" media="screen" />
<script type="text/javascript" language="javascript">


$(function(){

    $('#emissao').datePicker({startDate:'01/01/2000'});
    $('#saida').datePicker({startDate:'01/01/2000'});
    $("#emissao").maskedinput("99/99/9999");
    $("#saida").maskedinput("99/99/9999");
    $('.qtde_prod').each(function() {
        if ( $(this).val().length == 0 || $(this).val() <= 0 ) { return; }
        var _id = $(this).attr('id');
        if ( _id != undefined ) {
            var _tmp = _id.split('_');
            var _i   = _id[1];
            calc_base_icms(_i);
        }
    });
//  Mostra ou escone o formulário para enviar a Nota Fiscal eletrônica NF-e
    $('#openNFeForm').click(function () {
        $('#NFeForm').toggle('normal');
    });
});



function autocompletaCampos(){
    function formatItem(row) {
        //alert(row);
        return row[0] + " - " + row[1];
    }

    function formatResult(row) {
        return row[0];
    }

    var fab_fornecedor = $("#fornecedor_distrib_fabrica").val();

    if (fab_fornecedor !== undefined && fab_fornecedor !== "") {
        var fabrica_fornecedor = fab_fornecedor;
    } else {
        var fabrica_fornecedor = null;
    }

    /* Busca pela Descricao */
    $("input[rel='descricao']").autocomplete("nf_cadastro_ajax.php?tipo=produto&busca=descricao&fabrica=<?=$fabrica?>&fabrica_fornecedor="+fabrica_fornecedor, {
        minChars: 0,
        delay: 0,
        width: 350,
        max:50,
        matchContains: true,
        formatItem: function(row, i, max) {
            return row[0] + " - " + row[1];
        },
        formatResult: function(row) {
            return row[1];
        }
    });

    $("input[rel='descricao']").result(function(event, data, formatted) {
        $("input[id='"+$(this).attr("alt")+"']").val(data[0]) ;
        $(this).focus();
    });

            /* Busca pelo Referencia */
    $("input[rel='referencia']").autocomplete("nf_cadastro_ajax.php?tipo=produto&busca=referencia&fabrica=<?=$fabrica?>&fabrica_fornecedor="+fabrica_fornecedor, {
        minChars: 0,
        delay: 0,
        width: 350,
        max:50,
        matchContains: true,
        formatItem: function(row, i, max) {
            return row[0] + " - " + row[1];
        },
        formatResult: function(row) {
            return row[0];
        }
    });

    $("input[rel='referencia']").result(function(event, data, formatted) {
        $("input[id='"+$(this).attr("alt")+"']").val(data[1]) ;
        $(this).focus();
    });

}


function setFocus(lin) {
    $('#qtde_'+lin).focus();
}

//FUNÇÃO PARA CARREGAR FATURAMENTO
function retornaFat(http,componente) {
    var com = document.getElementById('f2');
    if (http.readyState == 1) {
        com.style.display    ='inline';
        com.style.visibility = "visible"
        com.innerHTML        = "&nbsp;&nbsp;<font color='#333333'>Consultando...</font>";
    }
    if (http.readyState == 4) {
        if (http.status == 200) {
            results = http.responseText.split("|");
            if (typeof (results[0]) != 'undefined') {
                if (results[0] == 'ok') {
                    com.innerHTML = results[1];
                    setTimeout('esconde_carregar()',3000);
                }else{
                    com.innerHTML = "&nbsp;&nbsp;<font color='#0000ff'>Sem faturamentos para esse fornecedor</font>";

                }
            }else{
                alert ('Fechamento nao processado');
            }
        }
    }
}

function exibirFat(componente,conta_pagar, documento, acao) {
    var nota_fiscal = document.getElementById('nota_fiscal').value;
    var fabrica     = document.getElementById('fabrica').value;

    if (nota_fiscal.length > 0) {
        url = "nf_cadastro_ajax?ajax=sim&nota_fiscal="+escape(nota_fiscal)+"&fabrica="+fabrica;
        http.open("GET", url , true);
        http.onreadystatechange = function () { retornaFat (http,componente,nota_fiscal) ; } ;
        http.send(null);
    }
}

function esconde_carregar(componente_carregando) {
    document.getElementById('f2').style.visibility = "hidden";
}

//FUNÇÃO PARA CALCULAR O TOTAL SELECIONADO DE CADA FORNECEDOR
function calc_base_icms(i){
    var base=0.0, aliq_icms=0.0, valor_icms=0.0, aliq_ipi=0.0, valor_ipi=0.0;;
    preco= document.getElementById('preco_'+i).value;
    qtde= document.getElementById('qtde_'+i).value;
    aliq_icms   = document.getElementById('aliq_icms_'+i).value;
    aliq_ipi    = document.getElementById('aliq_ipi_'+i).value;

/*
    preco= preco.toString().replace( ".", "" );
    qtde= qtde.toString().replace( ".", "" );
    aliq_icms   = aliq_icms.toString().replace( ".", "" );
    aliq_ipi    = aliq_ipi.toString().replace( ".", "" );
*/
    preco       = preco.toString().replace( ",", "." );
    qtde        = qtde.toString().replace( ",", "." );
    aliq_icms   = aliq_icms.toString().replace( ",", "." );
    aliq_ipi    = aliq_ipi.toString().replace( ",", "." );

    preco       = parseFloat(preco);
    qtde        = parseFloat(qtde);
    aliq_icms   = parseFloat(aliq_icms);
    aliq_ipi    = parseFloat(aliq_ipi);

    base        = parseFloat(preco * qtde);
    base        = base.toFixed(2);
    valor_icms  = ((base * aliq_icms)/100);
    valor_icms  = valor_icms.toFixed(2);
    valor_ipi   = ((base *  aliq_ipi)/100);
    valor_ipi   = valor_ipi.toFixed(2);

    if(aliq_icms > 0) {
        document.getElementById('base_icms_'+i).value = base.toString().replace( ".", "," );
        document.getElementById('valor_icms_'+i).value = valor_icms.toString().replace( ".", "," );
    }else{
        document.getElementById('base_icms_'+i).value = '0';
        document.getElementById('valor_icms_'+i).value = '0';
    }

    if(aliq_ipi > 0) {
        document.getElementById('base_ipi_'+i).value = base.toString().replace( ".", "," );
        document.getElementById('valor_ipi_'+i).value = valor_ipi.toString().replace( ".", "," );
    }else{
        document.getElementById('base_ipi_'+i).value = '0';
        document.getElementById('valor_ipi_'+i).value = '0';
    }
}

function checarNumero(campo){
    var num = campo.value.replace(",",".");
    campo.value = parseFloat(num).toFixed(2);
    if (campo.value=='NaN') {
        campo.value='0';
    }
}

//deixar campo preço unitario com 4 casas decimais.
function checarPreco(campo){
    var valorPreco = campo.value.replace(",",".");
    campo.value = parseFloat(valorPreco).toFixed(4);
    if (campo.value=='NaN') {
        campo.value='0';
    }
}



function addTr(numero){
    var numero2 = numero + 1;
    var cor = (numero2 % 2 == 0) ? "#ffffff" : "#FFEECC";

    if($("#"+numero2).length == 0) {
        $("#"+numero).after("<tr style='font-size: 12px' bgcolor='"+cor+"' id="+numero2+">\n<td align='right' nowrap>"+numero2+"</td>\n<td align='right' nowrap><input type='text' class='frm' name='referencia_"+numero+"' id='referencia_"+numero+"' value='' size='10' maxlength='20' rel='referencia' alt='descricao_"+numero+"' ;'></td>\n<td align='right' nowrap><input type='text' class='frm' name='descricao_"+numero+"' id='descricao_"+numero+"' alt='referencia_"+numero+"' value='' size='10' maxlength='20' rel='descricao' ></td>\n <td align='right' nowrap><input class='frm' type='text' name='qtde_"+numero+"' class='qtde_prod' id='qtde_"+numero+"' value='' size='5' maxlength='10' onKeyUp='calc_base_icms("+numero+");' onblur=\"checarNumero(this);\"></td>\n<td align='right' nowrap><input class='frm' type='text' name='preco_"+numero+"' id='preco_"+numero+"' value='' size='5' maxlength='12' onKeyUp='calc_base_icms("+numero+");' onblur=\"checarNumero(this);\"></td>\n<td align='right' nowrap><input class='frm' type='text' name='aliq_icms_"+numero+"' id='aliq_icms_"+numero+"' value='' size='5' maxlength='10' onKeyUp='calc_base_icms("+numero+");' onblur=\"checarNumero(this);\"></td>\n<td align='right' nowrap><input class='frm' type='text' name='aliq_ipi_"+numero+"' id='aliq_ipi_"+numero+"' value='' size='5' maxlength='10' onKeyUp='calc_base_icms("+numero+");' onblur=\"checarNumero(this); addTr("+numero2+")\"></td>\n<td align='right' nowrap><input class='frm' type='text' name='base_icms_"+numero+"' id='base_icms_"+numero+"' value='' size='5' maxlength='10' style='background-color: "+cor+"; border: none;' onfocus='form_nf.referencia_"+numero2+".focus();' readonly></td>\n<td align='right' nowrap><input class='frm' type='text' name='valor_icms_"+numero+"' id='valor_icms_"+numero+"' value='' size='5' maxlength='10' style='background-color: "+cor+"; border: none;' onfocus='form_nf.referencia_"+numero2+".focus();' readonly></td>\n<td align='right' nowrap><input class='frm' type='text' name='base_ipi_"+numero+"' id='base_ipi_"+numero+"' value='' size='5' maxlength='10' style='background-color: "+cor+"; border: none;' onfocus='form_nf.referencia_"+numero2+".focus();' readonly></td>\n<td align='right' nowrap><input class='frm' type='text' name='valor_ipi_"+numero+"' id='valor_ipi_"+numero+"' value='' size='5' maxlength='10' style='background-color: "+cor+"; border: none;' onfocus='form_nf.referencia_"+numero2+".focus();' readonly></td>\n</tr>\n");
        $('#descricao_'+numero).blur(function(){
            setFocus(numero);
        });
        $('#referencia_'+numero).blur(function(){
            setFocus(numero);
        });
        $('#total_qtde_item').val(numero2);
        autocompletaCampos();
    }
}

$().ready(function() {
    $("#fornecedor_distrib").autocomplete("nf_cadastro_ajax_busca.php?tipo=fornecedor", {
        minChars: 2,
        delay: 0,
        width: 350,
        max:50,
        matchContains: true,
        formatItem: function(row, i, max) {
            $("#fornecedor_distrib").focus();
            return row[0] ;
        },
        formatResult: function(row) {
        $("#fornecedor_distrib").focus();
            return row[0];
        }
    });

    $("#fornecedor_distrib").result(function(event, data, formatted) {
        $("#fornecedor_distrib").focus();
        $('#fornecedor_distrib_posto').val(data[1]);
    });
    $("#fornecedor_distrib").result(function(event, data, formatted) {
        $("#fornecedor_distrib").focus();
        $('#fornecedor_distrib_fabrica').val(data[2]);
        autocompletaCampos(); 
    });

    $("#transportadora").autocomplete("nf_cadastro_ajax_busca.php?tipo=transportadora", {
        minChars: 2,
        delay: 0,
        width: 350,
        max:50,
        matchContains: true,
        formatItem: function(row, i, max) {
            return row[0] ;
        },
        formatResult: function(row) {return row[0];}
            });

    $("#transportadora").result(function(event, data, formatted) {
        $(this).focus();
    });

    $("#condicao").autocomplete("nf_cadastro_ajax_busca.php?tipo=condicao", {
        minChars: 1,
        delay: 0,
        width: 350,
        max:50,
        matchContains: true,
        formatItem: function(row, i, max) {
            return row[0] ;
        },
        formatResult: function(row) {return row[0];}
            });

    $("#condicao").result(function(event, data, formatted) {
        $(this).focus();
    });

    $("input[type='text']").keydown(function(event) {
        if (event.keyCode == 13) {
            event.preventDefault();
        }
    });

    autocompletaCampos();
})

</script>

<style type="text/css">
    .titulo_tabela{
            background-color:#596d9b;
            font: bold 14px "Arial";
            color:#FFFFFF;
            text-align:center;
    }
    .titulo_coluna{
            background-color:#596d9b;
            font: bold 11px "Arial";
            color:#FFFFFF;
            text-align:center;
    }
    .msg_erro{
            background-color:#FF0000;
            font: bold 14px "Arial";
            color:#FFFFFF;
            text-align:center;
            margin: 0 auto;
            width: 900px;
            padding: 5px 0;
    }
    .formulario{
            background-color:#D9E2EF;
            font:11px Arial;
            text-align:left;
            margin: 0 auto;
            width: 900px;
            border: 1px solid #596d9b ;
    }
    table.tabela tr td{
            font-family: verdana;
            font-size: 11px;
            border-collapse: collapse;
            border:1px solid #596d9b;
    }

    .sucesso{
        background-color:#008000;
        font: bold 14px "Arial";
        color:#FFFFFF;
        text-align:center;
    }
    .texto_avulso{
        font: 14px Arial; color: rgb(89, 109, 155);
        background-color: #d9e2ef;
        text-align: center;
        width:700px;
        margin: 0 auto;
        border-collapse: collapse;
        border:1px solid #596d9b;
    }
    .subtitulo{
        background-color: #7092BE;
        font:bold 11px Arial;
        color: #FFFFFF;
    }
</style>
</head>

<body>

<? include 'menu.php';?>

<center><h1>Cadastro de Notas Fiscais - NF:<? echo $nota_fiscal ?></h1></center>

<p>
    <form name="NFeForm" id="NFeForm" action="<?=$PHP_SELF?>" method="post" title="Inserir NF-e" accept="text/xml" accept-charset="iso-8859-1" enctype="multipart/form-data">
        <table border='0' cellspacing='1' cellpadding='2' class='formulario'>
            <tr>
                <td colspan='6' class='titulo_coluna'>Importação de Arquivo XML</td>
            </tr>
            <? if ($_FILES['Nfe_File']['tmp_name'] != '' and $erro_msg == '') { ?>
                    <tr>
                        <td colspan='6'>
                            <p>Foi processado o arquivo <?=$_FILES['Nfe_File']['name']?>,
                            que contém a NF-e nº <?=$nota_fiscal?> com ID: <b><?=$nfe_id?></b></p>
                        </td>
                    </tr>
            <?}?>
            <tr>
                <td width='100px'>&nbsp;</td>
                <td colspan='2'>
                    <p class="descricao">
                        <b style='color:red'>Upload NF-e</b><br>
                            Para inserir uma NF-e, selecione o arquivo e clique em 'NFe'
                    </p>
                </td>
                <td colspan='2' align='center' style='vertical-align: middle'>
                    <input  type="file"     id="Nfe_File" accept="text/xml"  name="Nfe_File" title="Selecione o arquivo da NFe" class='frm' />
                    <button type="submit"   id='btn_acao'   name='btn_acao' value="NFe" title='Processar NF-e' style='cursor:pointer'>NFe</button>
                </td>
                <td width='100px'>&nbsp;</td>
        </table>
    </form>

    <?php
        if(strlen($erro_email) > 0){
            echo "<div class='msg_erro'>{$erro_email}</div>";
        }

        $erro_msg_= $erro_msg;
        if(strlen($erro_msg_) > 0){
            echo "<div class='msg_erro'>{$erro_msg_}</div>";
        }

        if(strlen($erro_msg)==0 AND strlen($faturamento) > 0 ){
            $sql = "SELECT  tbl_faturamento.faturamento                                          ,
                        tbl_fabrica.fabrica                                                  ,
                        tbl_fabrica.nome                                   AS fabrica_nome   ,
                        tbl_faturamento.nota_fiscal                                          ,
                        tbl_faturamento.natureza                                             ,
                        TO_CHAR (tbl_faturamento.emissao,'DD/MM/YYYY')     AS emissao        ,
                        TO_CHAR (tbl_faturamento.saida,'DD/MM/YYYY')       AS saida          ,
                        TO_CHAR (tbl_faturamento.conferencia,'DD/MM/YYYY') AS conferencia    ,
                        TO_CHAR (tbl_faturamento.cancelada,'DD/MM/YYYY')   AS cancelada      ,
                        tbl_faturamento.cfop                                                 ,
                        tbl_faturamento.serie                                                ,
                        tbl_faturamento.condicao                                             ,
                        tbl_faturamento.transp                                               ,
                        tbl_transportadora.nome                            AS transp_nome    ,
                        tbl_transportadora.fantasia                        AS transp_fantasia,
                        to_char (tbl_faturamento.total_nota,'999999.99')   AS total_nota     ,
                        to_char (tbl_faturamento.valor_icms_substtituicao,'999999.99')   AS valor_icms_substtituicao     ,
                        to_char (tbl_faturamento.base_icms_substtituicao,'999999.99')   AS base_icms_substtituicao     ,
                        tbl_condicao.descricao                                               ,
                        tbl_faturamento.obs                                                  ,
                        tbl_posto.nome as distribuidor
                    FROM      tbl_faturamento
                        JOIN      tbl_fabrica        USING (fabrica)
                        LEFT JOIN tbl_transportadora USING (transportadora)
                        LEFT JOIN tbl_condicao       USING (condicao)
                        LEFT JOIN tbl_posto ON tbl_posto.posto = tbl_faturamento.distribuidor
                    WHERE   tbl_faturamento.posto       = $login_posto
                        AND     tbl_faturamento.faturamento = $faturamento
                    ORDER BY tbl_faturamento.emissao     DESC,
                         tbl_faturamento.nota_fiscal DESC";
            $res = pg_query ($con,$sql);
            $erro_msg = pg_last_error($con);
            if(strlen($erro_msg) > 0) $erro_msg.= "<font color='#ff0000'>Erro ao consultar faturamento!</font>";
            if(pg_num_rows($res)>0){
                $conferencia      = trim(pg_fetch_result($res,0,conferencia)) ;
                $faturamento      = trim(pg_fetch_result($res,0,faturamento)) ;
                $fabrica          = trim(pg_fetch_result($res,0,fabrica)) ;
                $fabrica_nome     = trim(pg_fetch_result($res,0,fabrica_nome)) ;
                $nota_fiscal      = trim(pg_fetch_result($res,0,nota_fiscal));
                $emissao          = trim(pg_fetch_result($res,0,emissao));
                $saida            = trim(pg_fetch_result($res,0,saida));
                $cancelada        = trim(pg_fetch_result($res,0,cancelada));
                $cfop             = trim(pg_fetch_result($res,0,cfop));
                $serie            = trim(pg_fetch_result($res,0,serie));
                $condicao         = trim(pg_fetch_result($res,0,condicao));
                $transp           = trim(pg_fetch_result($res,0,transp));
                $natureza         = trim(pg_fetch_result($res,0,natureza));
                $transp_nome      = trim(pg_fetch_result($res,0,transp_nome));
                $transp_fantasia  = trim(pg_fetch_result($res,0,transp_fantasia));
                $total_nota       = trim(pg_fetch_result($res,0,total_nota));
                $descricao        = trim(pg_fetch_result($res,0,descricao));
                $obs              = trim(pg_fetch_result($res,0,obs));
                $fornecedor_distrib= trim(pg_fetch_result($res,0,distribuidor));
                $fabrica           = trim(pg_fetch_result($res,0,fabrica));
                $base_icms_substtituicao  = trim(pg_fetch_result($res,0,base_icms_substtituicao));
                $valor_icms_substtituicao = trim(pg_fetch_result($res,0,valor_icms_substtituicao));


                $condicao = (!empty($condicao)) ? $descricao : $obs;
            }else{
                $faturamento="";
            }
        }else{
            if (count($_FILES) == 0) {  // Só recarregar o formulário se NÃO houver upload de NFe
                $nota_fiscal = trim($_POST['nota_fiscal']);
                $emissao     = $_POST["emissao"]          ;
                $saida       = $_POST['saida']            ;
                $total_nota  = $_POST['total_nota']       ;
                $cfop        = $_POST['cfop']             ;
                $serie       = $_POST['serie']            ;
                $transp      = $_POST['transportadora']   ;
                $condicao    = $_POST['condicao']         ;
                $base_icms_substtituicao   = $_POST['base_icms_substtituicao']       ;
                $valor_icms_substtituicao  = $_POST['valor_icms_substtituicao']       ;
            }
        }
            if (strlen ($transp_nome) > 0)     $transp = $transp_nome;
            if (strlen ($transp_fantasia) > 0) $transp = $transp_fantasia;
            $transp = strtoupper ($transp);
    ?>

    <form name='form_nf' method="POST" action='<? echo $PHP_SELF?>'>
        <?php if(strlen($fabrica)>0) echo "<input type='hidden' name='fabrica' value='$fabrica' id='fabrica'>"; ?>
        <input type='hidden' name='faturamento' value='<?=$faturamento?>'>
        <table border='0' cellspacing='1' cellpadding='2' width='900px;' style='margin: 0 auto;' class='formulario'>
            <tr>
                <td colspan='7' class='titulo_coluna'>Dados da Nota Fiscal</td>
                <td colspan='2' class='titulo_coluna' style='text-align: right; padding-right: 10px;'>
                    <a href='nf_cadastro.php?novo=novo' style='color: #F00'>Nova Nota Fiscal</a>
                </td>
            </tr>
            <tr>
                <td width='50px'>&nbsp;</td>
                <td width='114px'>&nbsp;</td>
                <td width='114px'>&nbsp;</td>
                <td width='114px'>&nbsp;</td>
                <td width='114px'>&nbsp;</td>
                <td width='114px'>&nbsp;</td>
                <td width='114px'>&nbsp;</td>
                <td width='114px'>&nbsp;</td>
                <td width='50px'>&nbsp;</td>
            </tr>
            <tr>
                <td>&nbsp;</td>
                <td colspan='4'>
                    Fornecedor<br />
                    <?php
                        echo "<input type='text' name='fornecedor_distrib' id='fornecedor_distrib' style='width: 430px;' class='frm' value='$fornecedor_distrib'>";
                        echo "<input type='hidden' name='fornecedor_distrib_posto' id='fornecedor_distrib_posto' value='$fornecedor_distrib_posto' >";
                        echo "<input type='hidden' name='fornecedor_distrib_fabrica' id='fornecedor_distrib_fabrica' value='$fornecedor_distrib_fabrica' >";
                        if(strlen($fornecedor_distrib)>0) echo "<input type='hidden' name='fornecedor_distrib' value='$fornecedor_distrib'>";
                    ?>
                </td>
                <td colspan='1'>
                    Nota Fiscal<br />
                    <?php echo "<input type='text' name='nota_fiscal' id='nota_fiscal' value='$nota_fiscal' style='width: 95px;' class='frm'  maxlength='8' onBlur=\"exibirFat('dados','','','alterar')\"><br><div name='f2' id='f2' class='carregar'></div>"; ?>
                </td>
                <td colspan='2'>
                    Série<br />
                    <?php echo "<input type='text' name='serie' id='serie' value='$serie' style='width: 210px;' class='frm' maxlength='10' >"; ?>
                </td>
                <td>&nbsp;</td>
            </tr>
            <tr>
                <td>&nbsp;</td>
                <td>
                    Emissão<br />
                    <?php echo "<input type='text' name='emissao' id='emissao' value='$emissao' style='width: 85px' class='frm'  maxlength='10' >"; ?>
                </td>
                <td>
                    Saida<br />
                    <?php echo "<input type='text' name='saida' id='saida' value='$saida' style='width: 85px' class='frm' maxlength='10' >"; ?>
                </td>
                <td title='CFOP da NF: <?php echo "$cfop"; ?>'>
                    CFOP<br />
                    <?php echo "<input type='text' name='cfop2' id='cfop' value='$cfop' style='width: 95px' class='frm'  maxlength='8' >"; ?>
                </td>
                <td colspan='2'>
                    Natureza<br />
                    <?php echo "<input type='text' name='natureza' id='natureza' value='$natureza' style='width: 213px' class='frm'  maxlength='30' >"; ?>
                </td>
                <td colspan='2'>
                    Condição<br />
                    <?php echo "<input type='text' name='condicao' value='$condicao' id='condicao' style='width: 211px' class='frm'>"; ?>
                </td>
                <td>&nbsp;</td>
            </tr>
            <tr>
                <td>&nbsp;</td>
                <td colspan='2'>
                    Transportadora<br />
                    <?php echo "<input type='text' name='transportadora' id='transportadora' value='$transp' style='width: 210px' class='frm' maxlength='30'>"; ?>
                </td>
                <td colspan='2' title='Colocar neste campo o valor Base de ICMS de  Substituição Tributária.'>
                    Base ICMS Substituição Tributária<br />
                    <?php echo "<input type='text' name='base_icms_substtituicao' id='base_icms_substtituicao' value='$base_icms_substtituicao'   style='width: 210px' class='frm'  maxlength='12' onblur=\"checarNumero(this);\" ></td>"; ?>
                </td>
                <td colspan='2' title='Colocar neste campo o valor ICMS de Substituição Tributária'>
                    Valor ICMS Substituição Tributária<br />
                    <?php echo "<input type='text' name='valor_icms_substtituicao' id='valor_icms_substtituicao' value='$valor_icms_substtituicao'   style='width: 210px' class='frm' maxlength='12' onblur=\"checarNumero(this);\" ></td>"; ?>
                </td>
                <td title='Colocar neste campo o valor total da Nota (total das peças/produtos + impostos).'>
                    Valor Total NF(?)<br />
                    <?php echo "<input type='text' name='total_nota' id='total_nota' value='$total_nota'  style='width: 97px' class='frm'  maxlength='12' onblur=\"checarNumero(this);\" ></td>"; ?>
                </td>

                <td>&nbsp;</td>
            </tr>
            <tr>
                <td>&nbsp;</td>
                <td colspan='3' title='Favor informar se a NF é de ENTRADA de GARANTIA ou COMPRA (reflete no extrato)'>
                    NF de Garantia ou Compra (?)<br />
                    <?php
                    echo "<select name='garantia_compra' size='1' style='width: 330px' class='frm'>";
                        echo "<option value='' ";
                        if($garantia_compra == ""){
                            echo "selected";
                        }
                        echo ">Escolher</option>";

                        echo "<option value='G' ";
                        if($garantia_compra == "G"){
                            echo "selected";
                        }
                        echo ">NF de Garantia</option>";
                        echo "<option value='C' ";
                        if($garantia_compra == "C"){
                            echo "selected";
                        }
                        echo ">NF de Compra</option>";
                    echo "</select>";
                    ?>
                </td>
                <td>&nbsp;</td>
                <td colspan='3' title='Favor informar se a NF irá baixar Pendência de pedido ou pendência de divergência'>
                    Baixar automático Pedido ou Divergência (?)<br />
                    <?php
                    echo "<select name='baixar_pendencia' size='1' style='width: 330px' class='frm'>";
                        echo "<option value='' ";
                        if($baixar_pendencia == ""){
                            echo "selected";
                        }
                        echo ">Escolher</option>";

                        echo "<option value='pedido' ";
                        if($baixar_pendencia == "pedido"){
                            echo "selected";
                        }
                        echo ">Baixar pedido</option>";
                        echo "<option value='divergencia' ";
                        if($baixar_pendencia == "divergencia"){
                            echo "selected";
                        }
                        echo ">Divergencia</option>";
                    echo "</select>";
                    ?>
                </td>
                <td>&nbsp;</td>
            </tr>
            <tr>
                <td colspan='8'>&nbsp;</td>
            </tr>
        </table>
        <table border='0' cellspacing='1' cellpadding='1' width='900px;' style='margin: 1px auto;' class='formulario'>
            <?php
                $colspan = (strlen($faturamento) > 0) ? 13 : 12;
            ?>
            <tr>
                <td colspan='<?php echo $colspan; ?>' class='titulo_coluna'>Itens da Nota</td>
            </tr>
            <tr class='titulo_coluna'>
                <td>Peça / Produto</td>
                <td>Descrição</td>
                <td>
                    <a href="http://aplicacao.sefaz.go.gov.br/arquivos/TabelaNCM.pdf" title="Consulte o NCM na Receita Federal" target="_blank">NCM</a></td>
                <td>Qtd</td>
                <td>Preço</td>
                <?php
                    if(strlen($faturamento)>0)
                        echo "<td align='center'>Subtotal</td>";
                ?>
                <td title='Adicionada coluna de CFOP por ítem, novo padrão para NF-e'>CFOP</td>
                <td>Alíq. ICMS</td>
                <td>Alíq. IPI</td>
                <td>Base ICMS</td>
                <td>Valor ICMS</td>
                <td>Base IPI</td>
                <td>Valor IPI</td>
            </tr>
            <tbody id='tb_itens'>
                <?php
                    if(strlen($faturamento) == 0 ){
                        //if(is_array($nfe_itens)){
                        if(count($_FILES) != 0){
                            if(count($nfe_itens) > 3){
                                $total_qtde_item = count($nfe_itens) + 2;
                            }
                        }
                        for ($i = 0 ; $i < $total_qtde_item; $i++) {

                            //INSERIR ITENS DA NOTA
                            if (count($_FILES) == 0) {
                                $referencia     = $_POST["referencia"][$i]  ;
                                $descricao      = $_POST["descricao"][$i]  ;
                                $ncm            = $_POST["ncm"][$i];
                                $qtde           = $_POST["qtde"][$i]        ;
                                $preco          = $_POST["preco"][$i]       ;
                                $cfop           = $_POST["cfop"][$i]       ;
                                $aliq_icms      = $_POST["aliq_icms"][$i]   ;
                                $aliq_ipi       = $_POST["aliq_ipi"][$i]    ;
                                $base_icms      = $_POST["base_icms"][$i]   ;
                                $valor_icms     = $_POST["valor_icms"][$i]   ;
                                $base_ipi       = $_POST["base_ipi"][$i]     ;
                                $valor_ipi      = $_POST["valor_ipi"][$i]    ;
                            } else {
                                //10/03/2010 MLG - INSERIR ÍTENS DA NF-e
                                $referencia     = $nfe_itens[$i]['referencia'];
                                $descricao      = $nfe_itens[$i]['descricao'];
                                $ncm            = $nfe_itens[$i]['NCM'];
                                $qtde           = $nfe_itens[$i]['qtde'];
                                $preco          = $nfe_itens[$i]['preco'];
                                $cfop           = $nfe_itens[$i]['cfop'];
                                $aliq_icms      = $nfe_itens[$i]['aliq_icms'];
                                $aliq_ipi       = $nfe_itens[$i]['aliq_ipi'];
                                $base_icms      = $nfe_itens[$i]['base_icms'];
                                $valor_icms     = $nfe_itens[$i]['valor_icms'];
                                $base_ipi       = $nfe_itens[$i]['base_ipi'];
                                $valor_ipi      = $nfe_itens[$i]['valor_ipi'];
                            }

                            $qtde_linha = $i+ 1;

                            echo "<tr>";
                                //echo "<td align='right' nowrap>".($i+1)."</td>\n";
                                echo "<td align='right' nowrap><input type='text' class='frm' name='referencia[]' id='referencia_$i' value='$referencia' size='10' maxlength='20' rel='referencia' alt='descricao_$i' onBlur='setFocus(\"$i\");'></td>\n";
                                echo "<td align='right' nowrap><input type='text' class='frm' name='descricao[]' id='descricao_$i' alt='referencia_$i' value='$descricao' size='30' maxlength='20' rel='descricao' onBlur='setFocus(\"$i\");'></td>\n";
                                echo "<td align='right' nowrap><input class='frm' type='text' size='5' maxlength='10' name='ncm[]' id='ncm_$i'          value='$ncm'        class='qtde_prod'></td>\n";
                                echo "<td align='right' nowrap><input class='frm' type='text' size='5' maxlength='10' name='qtde[]' id='qtde_$i'        value='$qtde'       class='qtde_prod' onKeyUp='calc_base_icms($i);' onblur=\"checarNumero(this);\"></td>\n";
                                echo "<td align='right' nowrap><input class='frm' type='text' size='5' maxlength='12' name='preco[]' id='preco_$i'      value='$preco'      onKeyUp='calc_base_icms($i);' onblur=\"checarPreco(this);\"></td>\n";
                                echo "<td align='right' nowrap><input class='frm' type='text' size='5' maxlength='12' name='cfop[]' id='cfop_$i'        value='$cfop'></td>\n";
                                echo "<td align='right' nowrap><input class='frm' type='text' size='5' maxlength='10' name='aliq_icms[]' id='aliq_icms_$i'  value='$aliq_icms'  onKeyUp='calc_base_icms($i);' onblur=\"checarNumero(this);\"></td>\n";
                                echo "<td align='right' nowrap><input class='frm' type='text' size='5' maxlength='10' name='aliq_ipi[]' id='aliq_ipi_$i'    value='$aliq_ipi'   onKeyUp='calc_base_icms($i);' onblur=\"checarNumero(this); addTr($qtde_linha)\"></td>\n";
                                echo "<td align='right' nowrap><input class='frm' type='text' size='5' maxlength='10' name='base_icms[]' id='base_icms_$i'  value='$base_icms'  onfocus='form_nf.referencia_".($i+1).".focus();' readonly style='border: none; background: none;'></td>\n";
                                echo "<td align='right' nowrap><input class='frm' type='text' size='5' maxlength='10' name='valor_icms[]' id='valor_icms_$i'    value='$valor_icms' onfocus='form_nf.referencia_".($i+1).".focus();' readonly style='border: none; background: none;'></td>\n";
                                echo "<td align='right' nowrap><input class='frm' type='text' size='5' maxlength='10' name='base_ipi[]' id='base_ipi_$i'    value='$base_ipi'   onfocus='form_nf.referencia_".($i+1).".focus();' readonly style='border: none; background: none;'></td>\n";
                                echo "<td align='right' nowrap><input class='frm' type='text' size='5' maxlength='10' name='valor_ipi[]' id='valor_ipi_$i'  value='$valor_ipi'  onfocus='form_nf.referencia_".($i+1).".focus();' readonly style='border: none; background: none;'></td>\n";
                            echo "</tr>";
                        }
                    } else {
                        $sql= "SELECT tbl_faturamento_item.faturamento   ,
                                tbl_faturamento_item.faturamento_item,
                                tbl_faturamento_item.peca            ,
                                tbl_faturamento_item.qtde            ,
                                tbl_faturamento_item.preco           ,
                                tbl_faturamento_item.pedido          ,
                                tbl_faturamento_item.os              ,
                                tbl_faturamento_item.aliq_icms       ,
                                tbl_faturamento_item.aliq_ipi        ,
                                tbl_faturamento_item.base_icms       ,
                                tbl_faturamento_item.valor_icms      ,
                                tbl_faturamento_item.base_ipi        ,
                                tbl_faturamento_item.valor_ipi       ,
                                tbl_faturamento_item.cfop            ,
                                tbl_peca.referencia                  ,
                                tbl_peca.ncm                         ,
                                tbl_peca.descricao                   ,
                                tbl_os.sua_os
                            FROM      tbl_faturamento_item
                                    JOIN      tbl_peca  ON tbl_faturamento_item.peca= tbl_peca.peca
                                    LEFT JOIN tbl_os    ON tbl_faturamento_item.os  = tbl_os.os
                                    WHERE faturamento = $faturamento;";

                        $res = pg_query ($con,$sql);

                        $subtotal         = 0;
                        $valor_total      = 0;
                        $total_valor_ipi  = 0;
                        $total_valor_icms = 0;

                        for ($i = 0 ; $i < pg_num_rows($res); $i++) {
                            $faturamento_item = trim(pg_fetch_result($res,$i,faturamento_item)) ;
                            $referencia       = trim(pg_fetch_result($res,$i,referencia)) ;
                            $descricao        = trim(pg_fetch_result($res,$i,descricao));
                            $qtde             = trim(pg_fetch_result($res,$i,qtde));
                            $ncm              = trim(pg_fetch_result($res,$i,ncm));
                            $preco            = trim(pg_fetch_result($res,$i,preco));
                            $pedido           = trim(pg_fetch_result($res,$i,pedido));
                            $sua_os           = trim(pg_fetch_result($res,$i,sua_os));
                            $cfop             = trim(pg_fetch_result($res,$i,cfop));
                            $aliq_icms        = trim(pg_fetch_result($res,$i,aliq_icms));
                            $aliq_ipi         = trim(pg_fetch_result($res,$i,aliq_ipi));
                            $base_icms        = trim(pg_fetch_result($res,$i,base_icms));
                            $valor_icms       = trim(pg_fetch_result($res,$i,valor_icms));
                            $base_ipi         = trim(pg_fetch_result($res,$i,base_ipi));
                            $valor_ipi        = trim(pg_fetch_result($res,$i,valor_ipi));

                            $subtotal         = $preco * $qtde;
                            $valor_total      = $valor_total     + $subtotal;
                            $total_valor_ipi  = $total_valor_ipi + $valor_ipi;
                            $total_valor_icms = $total_valor_icms+ $valor_icms;

                            $preco            = number_format ($preco,      4, ',', '.');
                            $aliq_icms        = number_format ($aliq_icms,  2, ',', '-.');
                            $aliq_ipi         = number_format ($aliq_ipi,   2, ',', '.-');
                            $base_icms        = number_format ($base_icms,  2, ',', '.');
                            $valor_icms       = number_format ($valor_icms, 2, ',', '.');
                            $base_ipi         = number_format ($base_ipi,   2, ',', '.');
                            $valor_ipi        = number_format ($valor_ipi,  2, ',', '.');
                            $subtotal         = number_format ($subtotal,   2, ',', '.');

                            #   $preco = number_format ($preco,2,',','.');
                            if ($qtde_estoque == 0)  $qtde_estoque  = "";
                            if ($qtde_quebrada == 0) $qtde_quebrada = "";
                        ?>
                        <tr style='font-size: 12px'>
                            <!--
                                <td align='right' nowrap>
                                    <input type='hidden' name='faturamento_item_<?=$i?>' value='<?=$faturamento_item?>'>
                                    <input type='hidden' name='peca_<?=$i?>' value='<?=$peca?>'>
                                    <?//=($i+1)?>
                                </td>
                            -->
                            <td align='right' nowrap>
                                <input type='text' name='referencia_<?=$i?>'  value='<?=$referencia?>'  size='7'  maxlength='10' class='frm' >
                                <input type='hidden' name='faturamento_item_<?=$i?>' value='<?=$faturamento_item?>'>
                                <input type='hidden' name='peca_<?=$i?>' value='<?=$peca?>'>
                            </td>
                            <td align='left' nowrap><?=$descricao?></td>
                            <td align='left' nowrap><?=$ncm?></td>
                            <td align='right' nowrap><input class='frm' type='text' size='5' maxlength='10' name='qtde_<?=$i?>'     id='qtde_<?=$i?>'       value='<?=$qtde?>'      onKeyUp='calc_base_icms(<?=$i?>);'></td>
                            <td align='right' nowrap><input class='frm' type='text' size='5' maxlength='12' name='preco_<?=$i?>'        id='preco_<?=$i?>'      value='<?=$preco?>'     onKeyUp='calc_base_icms(<?=$i?>);'></td>
                            <td align='right' nowrap><?=$subtotal?></td>
                            <td align='right' nowrap><input class='frm' type='text' size='5' maxlength='12' name='cfop_<?=$i?>'     id='cfop_<?=$i?>'       value='<?=$cfop?>'></td>
                            <td align='right' nowrap><input class='frm' type='text' size='5' maxlength='10' name='aliq_icms_<?=$i?>'    id='aliq_icms_<?=$i?>'  value='<?=$aliq_icms?>' onKeyUp='calc_base_icms(<?=$i?>);'></td>
                            <td align='right' nowrap><input class='frm' type='text' size='5' maxlength='10' name='aliq_ipi_<?=$i?>' id='aliq_ipi_<?=$i?>'   value='<?=$aliq_ipi?>'  onKeyUp='calc_base_icms(<?=$i?>);'></td>
                            <td align='right' nowrap><input class='frm' type='text' size='5' maxlength='10' name='base_icms_<?=$i?>'    id='base_icms_<?=$i?>'  value='<?=$base_icms?>' style='background-color: <?=$cor?>; border: none;' onfocus='alert();' readonly></td>
                            <td align='right' nowrap><input class='frm' type='text' size='5' maxlength='10' name='valor_icms_<?=$i?>'   id='valor_icms_<?=$i?>' value='<?=$valor_icms?>'    style='background-color: <?=$cor?>; border: none;' readonly></td>
                            <td align='right' nowrap><input class='frm' type='text' size='5' maxlength='10' name='base_ipi_<?=$i?>' id='base_ipi_<?=$i?>'   value='<?=$base_ipi?>'  style='background-color: <?=$cor?>; border: none;' readonly></td>
                            <td align='right' nowrap><input class='frm' type='text' size='5' maxlength='10' name='valor_ipi_<?=$i?>'    id='valor_ipi_<?=$i?>'  value='<?=$valor_ipi?>' style='background-color: <?=$cor?>; border: none;' readonly></td>
                        </tr>
                        <?php
                        }


                        $valor_comp       = number_format ($valor_total+$total_valor_ipi+$valor_icms_substtituicao,2,',','.');
                        $valor_total      = number_format ($valor_total,2,',','.');
                        $total_valor_icms = number_format ($total_valor_icms,2,',','.');
                        $total_valor_ipi  = number_format ($total_valor_ipi,2,',','.');
                        ?>

                        <tr bgcolor='#d1d4eA' style='font-size: 12px' bgcolor='<?=$cor?>'>
                            <td align='right' nowrap colspan= '5'> Totais&nbsp;</td>
                            <td align='right' nowrap><?=$valor_total?></td>
                            <td align='right' nowrap colspan='5'><?=$total_valor_icms?></td>
                            <td align='right' nowrap colspan='2'><?=$total_valor_ipi?></td>
                        </tr>
                        <tr colspan='<?php echo $colspan; ?>' style='font-size: 12px' bgcolor='<?=$cor?>'>
                            <th align='right' nowrap colspan='100%'>Valor Total dos Produtos Nota: <?php echo $valor_total; ?></th>
                        </tr>
                        <?php

                            $total_nota = number_format ($total_nota,2,',','.');
                            if($valor_comp != $total_nota){
                                echo "<tr class='msg_erro'>";
                                    echo "<td colspan='{$colspan}'><div >Valor Total da Nota: $valor_total + $total_valor_ipi IPI está diferente de Total cadatrado: $total_nota</div></td>";
                                echo "</tr>";
                            }
                    }

                    echo "</tbody>";
                    $desc_bt = (strlen($faturamento)>0) ? 'Alterar' : 'Gravar';
                ?>
                <?php if (strlen($faturamento)==0) { ?>
                    <tr>
                        <td colspan="<?php echo $colspan; ?>" align="center" style='padding: 20px 0; '>
                            <input type='hidden' name='total_qtde_item' id='total_qtde_item' value='<?=$total_qtde_item?>'>
                            <input type='hidden' name='btn_acao' value=''>
                            <input type='button' name='btn_grava' value='<?=$desc_bt?>'
                                onclick='if (document.form_nf.btn_acao.value=="") {this.disabled="disabled";document.form_nf.btn_acao.value=this.value; document.form_nf.submit();}'>
                        </td>
                    </tr>
                <?php } ?>
        </table>
    </form>
    <? include "rodape.php"; ?>
    <script type="text/javascript">
        $(document).ready(function(){

            fnZebraItens();
            verificaLinha();
        });

        function fnZebraItens(){
            $('table tbody#tb_itens tr:even td').css('background-color','#98C7D3');

            autocompletaCampos();
            verificaGeraLinha();
        }

        function countElement(){
            return $("#tb_itens tr").size();
        }

        function verificaLinha(){
            var registro = 0;

            $('[id*=referencia_]').each(function(indice){
                if($(this).val() != ''){
                    registro += 1;
                }
            });

            var linha = countElement() - registro;
            if(linha < 2){
                for (i = 0; i < 2 ; i++){
                    geraLinha();
                }
            }

            return linha;
        }

        function geraLinha(){
            var indice = countElement();
            var html;

            html += "<tr>";
                html += "<td align='right' nowrap><input type='text' class='frm' size='10' maxlength='20' name='referencia_"+indice+"'  id='referencia_"+indice+"'      rel='referencia' alt='descricao_"+indice+"' onBlur='setFocus("+indice+");'></td>";
                html += "<td align='right' nowrap><input type='text' class='frm' size='10' maxlength='20' name='descricao_"+indice+"'   id='descricao_"+indice+"'       alt='referencia_"+indice+"'  rel='descricao' onBlur='setFocus("+indice+");'></td>";
                html += "<td align='right' nowrap><input class='frm' type='text' size='5'  maxlength='10' name='ncm_"+indice+"'         id='ncm_"+indice+"'             class='qtde_prod'></td>";
                html += "<td align='right' nowrap><input class='frm' type='text' size='5'  maxlength='10' name='qtde_"+indice+"'        id='qtde_"+indice+"'            class='qtde_prod' onKeyUp='calc_base_icms("+indice+");' onblur='checarNumero(this);'></td>";
                html += "<td align='right' nowrap><input class='frm' type='text' size='5'  maxlength='12' name='preco_"+indice+"'       id='preco_"+indice+"'           onKeyUp='calc_base_icms("+indice+");' onblur='checarNumero(this);'></td>";
                html += "<td align='right' nowrap><input class='frm' type='text' size='5'  maxlength='12' name='cfop_"+indice+"'        id='cfop_"+indice+"'            ></td>";
                html += "<td align='right' nowrap><input class='frm' type='text' size='5'  maxlength='10' name='aliq_icms_"+indice+"'   id='aliq_icms_"+indice+"'       onKeyUp='calc_base_icms("+indice+");' onblur='checarNumero(this);'></td>";
                html += "<td align='right' nowrap><input class='frm' type='text' size='5'  maxlength='10' name='aliq_ipi_"+indice+"'    id='aliq_ipi_"+indice+"'        onKeyUp='calc_base_icms("+indice+");' onblur='checarNumero(this);'></td>";
                html += "<td align='right' nowrap><input class='frm' type='text' size='5'  maxlength='10' name='base_icms_"+indice+"'   id='base_icms_"+indice+"'       onfocus='form_nf.referencia_"+(indice+1)+".focus();' readonly style='border: none; background: none;'></td>";
                html += "<td align='right' nowrap><input class='frm' type='text' size='5'  maxlength='10' name='valor_icms_"+indice+"'  id='valor_icms_"+indice+"'      onfocus='form_nf.referencia_"+(indice+1)+".focus();' readonly style='border: none; background: none;'></td>";
                html += "<td align='right' nowrap><input class='frm' type='text' size='5'  maxlength='10' name='base_ipi_"+indice+"'    id='base_ipi_"+indice+"'        onfocus='form_nf.referencia_"+(indice+1)+".focus();' readonly style='border: none; background: none;'></td>";
                html += "<td align='right' nowrap><input class='frm' type='text' size='5'  maxlength='10' name='valor_ipi_"+indice+"'   id='valor_ipi_"+indice+"'       onfocus='form_nf.referencia_"+(indice+1)+".focus();' readonly style='border: none; background: none;'></td>";
            html += "</tr>";

            $("table tbody#tb_itens").append(html);


            fnZebraItens();
        }

        function verificaGeraLinha(){
            $('[id*=referencia_]').focus(function(){
                verificaLinha();
            });

            $("#total_qtde_item").val(countElement());
        }
    </script>
</body>
</html>
