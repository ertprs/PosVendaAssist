<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';
include 'funcoes.php';

// somente Ibratele
$sql = "SELECT COUNT(1) as total FROM tbl_fabrica WHERE fabrica = $login_fabrica AND fatura_manualmente IS TRUE";

$res = pg_query($con,$sql);
if(pg_numrows($res) > 0){

    $total = pg_result($res,0,0);
    if($total == 0){
        header("Location: pedido_parametros.php");
        exit;
    }
}

$btn_acao = trim (strtolower ($_POST['btn_acao']));
$msg_erro = "";

if (strlen($_GET["cancelar"])>0 AND strlen($_GET["pedido"])>0 )  {
    //$res = pg_query ($con,"BEGIN TRANSACTION");

    $pedido         = trim($_GET["pedido"]);
    $motivo         = trim($_GET["motivo"]);
    $cancelar       = trim($_GET["cancelar"]);
    $qtde_cancelar  = trim($_GET["qtde_cancelar"]);
    $os             = trim($_GET["os"]);
    $pendente             = trim($_GET["pendente"]);

    if(strlen($motivo)==0) {
        $msg_erro = "Por favor informe o motivo de cancelamento <br>";
    }else{
        $aux_motivo = "'$motivo'";
    }
    //Cancela todo o pedido quando ele Ã© distribuidor

    if(strlen($qtde_cancelar)==0) {
        $msg_erro .= "Por favor informe a quantidade a cancelar";
    }
    if (strlen($msg_erro)==0) {
        if(!empty($os)){
            $cond_os = " JOIN tbl_os_item    OI ON OI.peca       = PI.peca       AND OI.pedido = PE.pedido
                         JOIN tbl_os_produto OP ON OP.os_produto = OI.os_produto AND OP.os = $os ";
            $campo_os = " OP.os        , ";
            $campo_qtde = " OI.qtde ";
        }else{
            $campo_os = " null AS os        , ";
            $campo_qtde = " PI.qtde ";
        }
        $sql = "SELECT  PI.pedido_item,
                        -($campo_qtde - PI.qtde_faturada - PI.qtde_faturada_distribuidor) as qtde       ,
                        PC.peca      ,
                        PC.referencia,
                        PC.descricao ,
                        $campo_os
                        PE.posto     ,
                        PE.distribuidor
                FROM    tbl_pedido       PE
                JOIN    tbl_pedido_item  PI ON PI.pedido     = PE.pedido
                JOIN    tbl_peca         PC ON PC.peca       = PI.peca
            $cond_os
                WHERE   PI.pedido      = $pedido
                AND     PI.pedido_item = $cancelar
                AND     PE.fabrica     = $login_fabrica";
        $res = pg_query ($con,$sql);
        if (pg_num_rows ($res) > 0) {
            $peca         = pg_fetch_result ($res,peca);
            $referencia   = pg_fetch_result ($res,referencia);
            $descricao    = pg_fetch_result ($res,descricao);
            #$qtde         = pg_fetch_result ($res,qtde);
            $os           = pg_fetch_result ($res,os);
            $posto        = pg_fetch_result ($res,posto);
            $distribuidor = pg_fetch_result ($res,distribuidor);

            if($qtde_cancelar > $pendente) {
                $msg_erro .= "Quantidade a cancelar maior que a quantidade pendente";
            }

            if(strlen($msg_erro)==0){
                $cond_fat_item = (in_array($login_fabrica,array(99,101,115,116,117,121,123,124,125,126,127,128,129,132,136,140,141,142,144,145))) ? " AND tbl_faturamento_item.pedido_item = $cancelar " : "";
                if(strlen($os)==0){
                    $os ="null";
                }

                if(in_array($login_fabrica, array(101)) AND strlen($os) > 0){
                    $sqlOS = "SELECT os_item from tbl_os_item WHERE pedido_item = $cancelar";
                    $resOS = pg_query($con,$sqlOS);
                    if(pg_num_rows($resOS) > 0 )
                        $os_item = pg_result($resOS,0,'os_item');
                }
                        //Verifica se já foi faturada
                $sql  = "SELECT trim(tbl_faturamento.nota_fiscal) As nota_fiscal ,
                                tbl_faturamento.faturamento,
                                tbl_faturamento.conhecimento
                        FROM    tbl_faturamento
                        JOIN    tbl_faturamento_item USING (faturamento)
                        JOIN    tbl_pedido_item ON  tbl_pedido_item.pedido      = tbl_faturamento.pedido
                                                AND tbl_pedido_item.pedido_item = tbl_faturamento_item.pedido_item
                                                AND tbl_pedido_item.qtde        = qtde_faturada + qtde_cancelada
                        WHERE   tbl_faturamento.posto        = $posto
                        AND     tbl_faturamento_item.pedido  = $pedido
                        AND     tbl_faturamento.pedido       = $pedido
                        AND     tbl_faturamento_item.peca    = $peca
                        AND tbl_faturamento.fabrica = $login_fabrica
                            $cond_fat_item;";
                            #echo nl2br($sql);exit;
                $resY = pg_query ($con,$sql);
                if (pg_num_rows ($resY) > 0) {
                    $msg_erro  .= "A peça $referencia - $descricao do pedido $pedido já está faturada com a nota fiscal". pg_fetch_result ($resY,nota_fiscal);
                }else{
                        if((in_array($login_fabrica,array(40,498,99,101,115,116,117,121,123,124,125,126,127,128,129,132,136,140,141,142,144,145))) AND strlen($os) > 0 AND $os != "null"){
                            $sqlOS = "  SELECT  os_item
                                        FROM    tbl_os_item
                                        JOIN    tbl_os_produto USING (os_produto)
                                        WHERE   os = $os
                                        AND     pedido_item = $cancelar
                            ";
                            #echo nl2br($sqlOS);exit;
                            $resOS = pg_query($con,$sqlOS);
                            $os_item = pg_result($resOS,0,'os_item');

                            if($login_fabrica == 40){

                                $sql  = "SELECT fn_pedido_cancela_garantia_item(
                                        null            ,
                                        $login_fabrica  ,
                                        $pedido         ,
                                        $peca           ,
                                        $os_item        ,
                                        $aux_motivo     ,
                                        $login_admin    ,
                                        $qtde_cancelar
                                    )";

                            }else{

                                $sql  = "SELECT fn_pedido_cancela_garantia(
                                            null            ,
                                            $login_fabrica  ,
                                            $pedido         ,
                                            $peca           ,
                                            $os_item        ,
                                            $aux_motivo     ,
                                            $login_admin
                                        )";

                            }

                            $res = pg_query ($con,$sql);
                        }else{
                            $sql  = "SELECT fn_pedido_cancela_gama(
                                                null            ,
                                                $login_fabrica  ,
                                                $pedido         ,
                                                $peca           ,
                                                $qtde_cancelar  ,
                                                $aux_motivo     ,
                                                $login_admin
                                            )";
							$res = pg_query ($con,$sql);
						}
                }
            }
        }
        $sql = "SELECT  fn_atualiza_status_pedido(
                            $login_fabrica,
                            $pedido
                        )" ;
        $res = pg_query ($con,$sql);
        $msg_erro .= pg_last_error($con);
    }

    if(empty($msg_erro)){
        header("Location: {$_SERVER['PHP_SELF']}?pedido=$pedido");
    }
}

$pedido = trim($_REQUEST['pedido']);

if ($btn_acao == "gravar") {
    $msg_erro = "";
    #VALIDAÇÃO DE DATAS - INICIO
    $d_emissao          = $_REQUEST["emissao"];
    $d_saida            = $_REQUEST["saida"];
    $d_previsao_chegada = $_REQUEST["previsao_chegada"];

    if (empty($d_emissao) || empty($d_saida)) {
        $msg_erro = "Data Inválida";
    }

    /* Data de Emissão */
    if(strlen($msg_erro)==0){
        list($de, $me, $ye) = explode("/", $d_emissao);
        if(!checkdate($me,$de,$ye)){
            $msg_erro = "Data de Emissão Inválida";
        }
    }

    /* Validação para não permitir data de emissão maior que dia atual */
    if(strlen($msg_erro)==0){
        $aux_data_emissao = "$ye-$me-$de";
    }
    if(strlen($msg_erro)==0){
        if(strtotime($aux_data_emissao) > strtotime('today')){
            $msg_erro = "Data de Emissão Inválida.";
        }
    }

    /* Data de Saída */
    if (strlen($msg_erro)==0) {
        list($ds, $ms, $ys) = explode("/", $d_saida);
        if(!checkdate($ms,$ds,$ys)){
            $msg_erro = "Data de Saída Inválida";
        }
    }

    /* Validação de data saida < data emissão */
    if(strlen($msg_erro)==0){
        $aux_data_saida = "$ys-$ms-$ds";
    }
    if(strlen($msg_erro)==0){
        if( strtotime($aux_data_saida) < strtotime($aux_data_emissao) ){
            $msg_erro = "Data de Saída Inválida.";
        }
    }

    /* Data de Previsão de Chegada */
    if(strlen($msg_erro)==0 && !empty($d_previsao_chegada)){
        list($dp, $mp, $yp) = explode("/", $d_previsao_chegada);
        if(!checkdate($mp,$dp,$yp)){
            $msg_erro = "Data de Previsão Inválida";
        }
    }

    if (strlen($msg_erro)==0){
        if (strlen($_POST['emissao']) > 0){
            $xemissao = "'". formata_data($_POST['emissao']) ."'";
        }else{
            $msg_erro = "Digite a data de emissão";
        }

        if (strlen($_POST['saida']) > 0){
            $xsaida = "'". formata_data($_POST['saida']) ."'";
        }else{
            $msg_erro = "Digite a data de saída";
        }

        if (strlen($_POST['previsao_chegada']) > 0){
            $xprevisao_chegada = "'". formata_data($_POST['previsao_chegada']) ."'";
        }else{
            $xprevisao_chegada = 'null';
        }

        if (strlen($_POST['transportadora']) > 0){
            $xtransportadora = "'". $_POST['transportadora'] ."'";
        }else{
            $xtransportadora = 'null';
            //$msg_erro = "Selecione a transportadora."; // Retirada a obrigatoriedade a pedido da Ibratele, Lauro - 15/09/2005
        }

        if (strlen($_POST['cfop']) > 0){
            $xcfop = "'". $_POST['cfop'] ."'";
        }else{
            $msg_erro = "Informe o CFOP.";
        }

        if (strlen($_POST['total_nota']) > 0){
            $xtotal_nota = str_replace(',','.',str_replace('.', '', $_POST['total_nota']));
        }else{
            $msg_erro = "Digite o total da Nota Fiscal.";
        }

        if ($login_fabrica == 101) {
            if(strlen($_POST['rastreio']) > 0){
                $xrastreio = "'".strtoupper($_POST['rastreio'])."'";
            } else {
                $xrastreio = "null";
            }
        } else {
            $xrastreio = "null";
        }

        if (strlen($_POST['nota_fiscal']) > 0){
            $xnota_fiscal = "'". $_POST['nota_fiscal'] ."'";
        }else{
            $msg_erro = "Digite o número da nota fiscal.";
        }

        $qtde_item = $_POST['qtde_item'];
    }

    if (strlen ($msg_erro) == 0) {
        $res = pg_query ($con,"BEGIN TRANSACTION");

        if (strlen ($faturamento) == 0) {
            #-------------- insere pedido ------------
           $sql = "INSERT INTO tbl_faturamento (
                                    fabrica          ,
                                    emissao          ,
                                    saida            ,
                                    transportadora   ,
                                    pedido           ,
                                    posto            ,
                                    previsao_chegada ,
                                    total_nota       ,
                                    cfop             ,
                                    conhecimento     ,
                                    nota_fiscal
                                ) VALUES (
                                    $login_fabrica     ,
                                    $xemissao          ,
                                    $xsaida            ,
                                    $xtransportadora   ,
                                    $pedido            ,
                                    $posto             ,
                                    $xprevisao_chegada ,
                                    $xtotal_nota       ,
                                    $xcfop             ,
                                    $xrastreio         ,
                                    $xnota_fiscal
                                )";

        } else {
            $sql = "UPDATE  tbl_faturamento
                    SET     fabrica          = $login_fabrica,
                            emissao          = $xemissao    ,
                            saida            = $xsaida,
                            transportadora   = $xtransportadora,
                            pedido           = $pedido,
                            posto            = $posto,
                            previsao_chegada = $xprevisao_chegada,
                            total_nota       = $xtotal_nota,
                            cfop             = $xcfop,
                            conhecimento     = $xrastreio,
                            nota_fiscal      = $xnota_fiscal
                    WHERE   faturamento = $faturamento
                    AND     fabrica     = $login_fabrica
            ";
        }
        $res = pg_query ($con,$sql);
        $msg_erro = pg_last_error($con);

        if (strlen($msg_erro) == 0 and strlen($faturamento) == 0) {
            $res = pg_query ($con,"SELECT CURRVAL ('seq_faturamento')");
            $faturamento = pg_result ($res,0,0);
            $msg_erro    = pg_last_error($con);
        }
        if (strlen($msg_erro) == 0) {
            $valid = false;
            $comando = array(); #HD-3207600
            foreach($_POST as $chave => $valor){
                if(is_array($valor)){
                    foreach($valor as $key=>$attr){
                        $comando[$chave][] = $attr;
                    }
                }
            }

        #HD-3207600
            $item = array();
            $peca = array();
            $qtde = array();
            $qtde_cancelada = array();
            $pendente = array();
            $qtde_faturada = array();
            $preco = array();
            $os = array();
            $sua_os = array();
            $qtde_faturamento = array();
            $preco_tab = array();

            $item               = $comando['item'];
            $peca               = $comando['peca'];
            $qtde               = $comando['qtde'];
            $qtde_cancelada     = $comando['qtde_cancelada'];
            $pendente           = $comando['pendente'];
            $qtde_faturada      = $comando['qtde_faturada'];
            $preco              = $comando['preco'];
            $os                 = $comando['os'];
            $sua_os             = $comando['sua_os'];
            $qtde_faturamento   = $comando['qtde_faturamento'];
            $preco_tab          = $comando['preco_tab'];
            //print_r($comando);
            //          list(
            //              $item[],
            //              $peca[],
            //              $qtde[],
            //              $qtde_cancelada[],
            //              $pendente[],
            //              $qtde_faturada[],
            //              $preco[],
            //              $os[],
            //              $sua_os[],
            //              $qtde_faturamento[],
            //              $preco_tab[]
            // ) = $comando;
        # FIM HD-3207600
            for ($i = 0 ; $i < $qtde_item ; $i++) {
                if ( $qtde_faturamento[$i] > 0 ) {
                    $valid = true;
                }

                /**
                 * @hd 768791
                 */
                if ( ($qtde_faturamento[$i]=='0' || strlen($qtde_faturamento[$i])==0) && in_array($login_fabrica,array(95,108,111))){
                    continue;
                }

                $msg_erro = ( $qtde_faturamento[$i]=="null" and $pendente[$i]>0 ) ? "Informe uma Qtde. de NF do item" : $msg_erro;

                if ($pendente[$i] == '0') {
                    $xpendente = $qtde[$i] - $qtde_faturamento[$i];
                }else{
                    if ($qtde_faturamento[$i] > $pendente[$i]){
                        $msg_erro = "$os[$i] A quantidade a faturar está maior do que a quantidade pendente.";
                    }
                    if(!$msg_erro){
                        $xpendente = $pendente[$i] - $qtde_faturamento[$i];
                    }
                }


                if ($pendente[$i] > 0 && !$msg_erro) {

                    if($qtde_faturamento[$i] > 0 AND strlen($peca[$i]) > 0 AND strlen($msg_erro) == 0) {

                        #$os = "null";
                        #$os_item = "null";
                        if(!empty($os[$i])) {
                            $sqlOSItem = "  SELECT  os_item, peca_obrigatoria
                                            FROM    tbl_os_item
                                            JOIN    tbl_os_produto USING (os_produto)
                                            WHERE   tbl_os_produto.os = $os[$i]
                                            AND     tbl_os_item.peca  = $peca[$i]
                                            AND     tbl_os_item.pedido_item = $item[$i]";
                            $resOSItem = pg_query($con,$sqlOSItem);
                            if(pg_num_rows($resOSItem) > 0){
                                $os_item = pg_fetch_result($resOSItem, 0, os_item);
				                $peca_obrigatoria = pg_fetch_result($resOSItem, 0, peca_obrigatoria);
                            }else{
                                $os_item = "null";
				                $peca_obrigatoria = null;
                            }
                            if($os_item == "null"){
                                if (isset($sua_os[$i]{0}) AND $sua_os[$i] <> "null") {
                                    $sqlOs = "SELECT os FROM tbl_os WHERE sua_os = '$sua_os[$i]' AND fabrica = $login_fabrica";
                                    $resOs = pg_query($con, $sqlOs);
                                    if (pg_num_rows($resOs) == 1) {
                                        $os = pg_fetch_result($resOs, 0, 'os');

                                        $sqlOSItem = "SELECT os_item, peca_obrigatoria FROM tbl_os_item JOIN tbl_os_produto ON tbl_os_item.os_produto = tbl_os_produto.os_produto WHERE tbl_os_produto.os = $os AND tbl_os_item.peca = $peca";
                                        $resOSItem = pg_query($con,$sqlOSItem);

                                        if(pg_num_rows($resOSItem) > 0){
                                            $os_item = pg_fetch_result($resOSItem, 0, 'os_item');
				 	                          $peca_obrigatoria = pg_fetch_result($resOSItem, 0, 'peca_obrigatoria');
                                        }else{
                                            $os_item = "null";
						                      $peca_obrigatoria = null;
                                        }
                                    }
                                } else {
                                    #$os = "null";
                                    $os_item = "null";
                                }
                            }
                        }else{
                            $os[$i] = "null";
                            $os_item = "null";
                        }


            			if ($peca_obrigatoria == "t") {
            				$peca_obrigatoria = "true";
            			} else {
            				$peca_obrigatoria = "false";
            			}

                        $sql = "INSERT INTO tbl_faturamento_item (
                                                faturamento             ,
                                                peca                    ,
                                                preco                   ,
                                                qtde                    ,
                                                pendente                ,
                                                pedido                  ,
                                                pedido_item             ,
                                                os                      ,
                                                os_item,
						                        devolucao_obrig
                                            ) VALUES (
                                                $faturamento            ,
                                                $peca[$i]               ,
                                                $preco[$i]              ,
                                                $qtde_faturamento[$i]   ,
                                                $xpendente              ,
                                                $pedido                 ,
                                                $item[$i]               ,
                                                $os[$i]                 ,
                                                $os_item,
						                        $peca_obrigatoria
                                            )";
                        $res = pg_query ($con,$sql);

                            $sql = "SELECT  fn_atualiza_pedido_item(
                                                $peca[$i]               ,
                                                $pedido                 ,
                                                $item[$i]               ,
                                                $qtde_faturamento[$i]
                                            );";
                            $res = pg_query ($con,$sql);
                            $msg_erro .= pg_last_error($con);

                            $sql = "SELECT  fn_atualiza_status_pedido(
                                                $login_fabrica,
                                                $pedido
                                            );";
                            $res = pg_query ($con,$sql);
                            $msg_erro .= pg_last_error($con);

                        if (strlen($msg_erro) > 0){
                            $linha_erro = $i;
                        }
                    }
                    if (strlen($msg_erro) > 0){
                        $linha_erro = $i;
                    }
                }
                if ($usaEstoquePosto) {
                    $sqlVerTipo = "
                        SELECT  descricao
                        FROM    tbl_tipo_pedido
                        JOIN    tbl_pedido USING(fabrica,tipo_pedido)
                        WHERE   fabrica = $login_fabrica
                        AND     pedido = $pedido
                    ";
                    $resVerTipo = pg_query($con,$sqlVerTipo);

                    if ( $login_fabrica == 163 OR ($login_fabrica == 165 && pg_fetch_result($resVerTipo,0,0) == "BONIFICACAO") ) {
                        $sqlInsEstoque = "
                            INSERT INTO tbl_estoque_posto_movimento (
                                fabrica,
                                posto,
                                peca,
                                qtde_entrada,
                                admin,
                                nf,
                                data,
                                obs
                            ) VALUES (
                                $login_fabrica,
                                $posto,
                                $peca[$i] ,
                                $qtde_faturamento[$i],
                                $login_admin,
                                $xnota_fiscal,
                                CURRENT_DATE,
                                E'Estoque movimentado pelo pedido <b>$pedido</b>'
                            )
                        ";
                        $resInsEstoque = pg_query($con,$sqlInsEstoque);

                        if (!pg_last_error($con)) {
                            $sqlQtdeEstoque = "
                                SELECT  qtde
                                FROM    tbl_estoque_posto
                                WHERE   fabrica = $login_fabrica
                                AND     posto = $posto
                                AND     peca = $peca[$i]
                            ";
                            $resQtdeEstoque = pg_query($con,$sqlQtdeEstoque);

                            if (pg_num_rows($resQtdeEstoque) > 0) {
                                $somar = pg_fetch_result($resQtdeEstoque,0,0);

                                $qtdeEstoque = $qtde_faturamento[$i] + $somar;

                                $sqlUpEstoque = "
                                    UPDATE  tbl_estoque_posto
                                    SET     qtde = $qtdeEstoque
                                    WHERE   fabrica = $login_fabrica
                                    AND     posto = $posto
                                    AND     peca = $peca[$i]
                                ";

                            } else {
                                $sqlUpEstoque = "
                                    INSERT INTO tbl_estoque_posto (
                                        fabrica,
                                        posto,
                                        peca,
                                        qtde,
                                        tipo,
                                        estoque_minimo,
                                        data_input
                                    ) VALUES (
                                        $login_fabrica,
                                        $posto,
                                        $peca[$i] ,
                                        $qtde_faturamento[$i],
                                        'estoque',
                                        0,
                                        CURRENT_DATE
                                    )
                                ";
                            }

                            $resUpEstoque = pg_query($con,$sqlUpEstoque);

                            $msg_erro .= pg_last_error($con);
                        }
                    }
                }
            }
            if ($valid === false ) {
                $msg_erro = 'Digite a quantidade em ao menos um item para faturar';
            }

            if ($login_fabrica == 94) {
                $sqlAdmin = "
                    UPDATE  tbl_pedido
                    SET     admin_alteracao = $login_admin
                    WHERE   pedido = $pedido
                    AND     fabrica = $login_fabrica
                ";
//                 exit(nl2br($sqlAdmin));
                $resAdmin = pg_query($con,$sqlAdmin);
            }
        }
    }
    if (strlen ($msg_erro) == 0) {
        $res = pg_query ($con,"COMMIT TRANSACTION");
        //header ("Location: pedido_parametros.php");
        header ("Location: pedido_nao_faturado_consulta.php");
        exit;
    }else{
        $res = pg_query ($con,"ROLLBACK TRANSACTION");
    }
}

#------------ Le Pedido da Base de dados ------------#
if (strlen ($pedido) > 0) {
    $sql = "SELECT  tbl_posto.posto          ,
                    tbl_posto.cnpj           ,
                    tbl_posto.nome           ,
                    tbl_pedido.condicao      ,
                    tbl_pedido.tabela        ,
                    tbl_pedido.obs           ,
                    tbl_pedido.tipo_pedido   ,
                    tbl_pedido.tipo_frete    ,
                    tbl_pedido.pedido_cliente,
                    tbl_pedido.seu_pedido    ,
                    tbl_pedido.validade      ,
                    tbl_pedido.entrega       ,
                    tbl_pedido.linha         ,
                    tbl_pedido.transportadora,
                    tbl_linha.nome                      AS nome_linha,
                    tbl_tipo_pedido.descricao           AS nome_tipo_pedido,
                    tbl_tipo_pedido.codigo              AS codigo_tipo_pedido,
                    tbl_tipo_pedido.pedido_em_garantia  AS tipo_pedido_garantia,
                    tbl_tabela.descricao                AS nome_tabela,
                    tbl_condicao.descricao              AS nome_condicao
            FROM    tbl_pedido
            JOIN    tbl_posto       USING (posto)
       LEFT JOIN    tbl_linha       USING (linha)
       LEFT JOIN    tbl_tipo_pedido USING (tipo_pedido)
       LEFT JOIN    tbl_tabela      USING (tabela)
       LEFT JOIN    tbl_condicao    USING (condicao)
            WHERE   tbl_pedido.pedido  = $pedido
            AND     tbl_pedido.fabrica = $login_fabrica";

    $res = pg_query ($con,$sql);

    if (pg_numrows ($res) > 0) {
        $posto                  = trim(pg_result ($res,0,posto));
        $cnpj                   = trim(pg_result ($res,0,cnpj));
        $cnpj                   = substr($cnpj,0,2) .".". substr($cnpj,2,3) .".". substr($cnpj,5,3) ."/". substr($cnpj,8,4) ."-". substr($cnpj,12,2);
        $nome                   = trim(pg_result ($res,0,nome));
        $obs                    = trim(pg_result ($res,0,obs));
        $tipo_frete             = trim(pg_result ($res,0,tipo_frete));
        $pedido_cliente         = trim(pg_result ($res,0,pedido_cliente));
        $seu_pedido             = trim(pg_result ($res,0,seu_pedido));
        $validade               = trim(pg_result ($res,0,validade));
        $entrega                = trim(pg_result ($res,0,entrega));
        $nome_linha             = trim(pg_result ($res,0,nome_linha));
        $nome_tipo_pedido       = trim(pg_result ($res,0,nome_tipo_pedido));
        $nome_tabela            = trim(pg_result ($res,0,nome_tabela));
        $nome_condicao          = trim(pg_result ($res,0,nome_condicao));
        $transportadora         = trim(pg_result ($res,0,transportadora));
        $codigo_tipo_pedido     = trim(pg_result ($res,0,codigo_tipo_pedido));
        $tipo_pedido_garantia   = trim(pg_result ($res,0,tipo_pedido_garantia));

        $pedido_aux = ($login_fabrica == 88 AND (!empty($seu_pedido))) ? $seu_pedido : $pedido;
    }
}

#---------------- Recarrega Form em caso de erro -------------
if (strlen ($msg_erro) > 0 and strlen($pedido)>0 ) {
    $condicao       = $_POST['condicao'];
    $tipo_pedido    = $_POST['tipo_pedido'];
    $tabela         = $_POST['tabela'];
    $linha          = $_POST['linha'];

}


$layout_menu = "callcenter";
$title       = "Cadastro de Faturamento de Pedidos de Peças";

include "cabecalho.php";

?>
<?php
    include "javascript_calendario_new.php";
    include "../js/js_css.php";
?>
<script type="text/javascript" charset="utf-8" src="js/jquery.alphanumeric.js"></script>
<script type="text/javascript" src="js/jquery.maskmoney.js"></script>
<script type="text/javaScript">

$().ready(function(){
    var fabrica = '<?=$login_fabrica?>';

    $("#nota_fiscal").numeric();
    $(".numeric").numeric();

    $( "#emissao" ).datepick({startDate : "01/01/2000"});
    $( "#emissao" ).mask("99/99/9999");

    $( "#saida" ).datepick({startDate : "01/01/2000"});
    $( "#saida" ).mask("99/99/9999");

    $( "#previsao_chegada" ).datepick({startDate : "01/01/2000"});
    $( "#previsao_chegada" ).mask("99/99/9999");


    $(".money").maskMoney({symbol:"", decimal:",", thousands:'.', precision:2, maxlength: 10});

    if(fabrica == 81){
        $(".tabela").find("input[name^=qtde_faturamento_]").each(function(){
            $(this).val(1).attr("style","text-align:center");
        });

        function totalNota(){
            var total = 0;
            $(".tabela").find("input[name^=preco_][type=text]").each(function(){

                var valor = $(this).val();
                var qtde  = $(this).parents("tr").find("input[name^=qtde_faturamento_]").val();

                if(qtde > 0){
                    valor = valor.replace(".","");
                    valor = valor.replace(",",".");

                    if(valor.length == 0){
                        valor = 0;
                    }

                    total += parseFloat(valor * qtde);
                    $("input[name=total_nota]").val(String(total.toFixed(2)).replace(".",",")).keypress();
                }

            });
        }

        totalNota();

        $(".tabela").find("input[name^=qtde_faturamento_]").change(function(){
            totalNota();
        });

    }
});

function verificaItens(){

    var retorno = false;

    <? if($login_fabrica == 95 or $login_fabrica == 108 or $login_fabrica == 111){?>
        $("input[rel='inputtext']").each(function(elemento){
            if($(this).val() != '' && $(this).val() != '0'){
                retorno = true;
            }
        });
    <? }else{ ?>
        retorno = true;
    <? } ?>

    return retorno;
}

function fnc_pesquisa_transportadora (xcampo, tipo){
    if (xcampo.value != "") {
        var url = "";
        url = "pesquisa_transportadora.php?campo=" + xcampo.value + "&tipo=" + tipo ;
        janela = window.open(url, "janela", "toolbar=no, location=yes, status=yes, scrollbars=yes, directories=no, width=500, height=400, top=0, left=0");
        janela.transportadora = document.frm_pedido.transportadora;
        janela.nome           = document.frm_pedido.transportadora_nome;
        janela.codigo           = document.frm_pedido.codigo;
        janela.cnpj           = document.frm_pedido.transportadora_cnpj;
        janela.focus();
    }
}

function fnc_valida_qtde(linha){

    qtde = $("#qtde_"+linha).val();
    qtde_cancelada = $("#qtde_cancelada_"+linha).val();
    qtde_faturamento = $("#qtde_faturamento_"+linha).val();

    qtde_total = (qtde - qtde_cancelada);

    if (qtde_faturamento > qtde_total){
        alert("Atenção: Quantidade de NF maior que Quantidade Pedida");
    }
}

function gravarFormulario(){
    if(verificaItens() == true){
        if (document.forms['frm_pedido'].btn_acao.value == '') {
            document.forms['frm_pedido'].btn_acao.value='gravar';
            document.forms['frm_pedido'].submit();
        } else {
            alert ('Aguarde submissão');
        }
    }else{
        alert('Digite Quantidade NF');
    }
}

function cancelaItens(peca_descricao,item, pedido, os, motivo, qtde_cancelar,pendente){
    //if(confirm('Deseja cancelar este item do pedido: <? echo str_replace("","",$peca_descricao); ?>?')) window.location = '<?=$PHP_SELF?>?cancelar=<?=$item?>&pedido=<?=$pedido?>&os=<?=$os?>&motivo=document.forms['frm_pedido'].motivo_<?=$i?>.value&qtde_cancelar=document.forms['frm_pedido'].qtde_a_cancelar_<?=$i?>.value;
    if(confirm('Deseja cancelar este item do pedido: '+peca_descricao+'?')) {
        window.location = "<?=$PHP_SELF?>?cancelar="+item+"&pedido="+pedido+"&os="+os+"&motivo="+motivo+"&qtde_cancelar="+qtde_cancelar+"&pendente="+pendente;
    }
}
</script>

<style type="text/css">

.menu_top {
    text-align: center;
    font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
    font-size: x-small;
    font-weight: bold;
    border: 1px solid;
    color:#ffffff;
    background-color: #596D9B
}

.table {
    font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
    font-size: x-small;
    text-align: center;
    border: 1px solid #d9e2ef;
}

.table_line {
    text-align: left;
    font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
    font-size: 10px;
    font-weight: normal;
    border: 0px solid;
}

.table_line2 {
    text-align: left;
    font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
    font-size: 10px;
    font-weight: normal;
    background-color: #CED7e7;
}

table.tabela tr td{
    font-family: verdana;
    font-size: 11px;
    empty-cells:show;
    border-collapse: collapse;
    border:1px solid #596d9b;
}

.titulo_tabela{
    background-color:#596d9b;
    font: bold 14px "Arial" !important;
    color:#FFFFFF;
    text-align:center;
}

.titulo_coluna{
    background-color:#596d9b;
    font: bold 11px "Arial";
    color:#FFFFFF;
    text-align:center;
}

</style>

<!--[if lt IE 8]>
<style>
table.tabela2{
    empty-cells:show;
    border-collapse:collapse;
    border-spacing: 2px;
}
</style>
<![endif]-->

<?
if (strlen ($msg_erro) > 0) {
?>
<table class="table" width="700" border="0" cellpadding="0" cellspacing="0" align='center' >
<tr>
<!-- class="menu_top" -->
    <td valign="middle" align="center" class='error'>
        <? echo $msg_erro ?>
    </td>
</tr>
</table>
<?
}
//echo $msg_debug ;
?>


<!-- ------------- Formulário ----------------- -->
<form name="frm_pedido" method="post" >
<input type="hidden" name="pedido" value="<? echo $pedido ?>">
<input type="hidden" name="posto" value="<? echo $posto ?>">
<?php
if ($login_fabrica == 101 && $codigo_tipo_pedido == 'TRO') {
    $sqlVerConsumidor = "
        SELECT  tbl_os_troca.envio_consumidor,
                tbl_os.consumidor_nome,
                tbl_os.consumidor_cpf
        FROM    tbl_os_troca
        JOIN    tbl_os USING(os)
        JOIN    tbl_os_produto  ON tbl_os_produto.os = tbl_os.os
        JOIN    tbl_os_item     USING(os_produto)
        WHERE   tbl_os_item.pedido  = $pedido
        AND     tbl_os.fabrica      = $login_fabrica
    ";
    $resVerConsumidor = pg_query($con,$sqlVerConsumidor);
    $destinatario = pg_fetch_result($resVerConsumidor,0,envio_consumidor);
}
?>
<?=($login_fabrica == 94) ? "<span style='color:#F00;font-size:13px;position:relative;margin-right:-400px;margin-top:220px;'>(*) Campos Obrigatórios</span>" : ""?>
<table class="table" width='600' align='center' border='0' cellspacing='3' cellpadding='3'>
<tr class="menu_top">
    <td align='center'>
        <font face='arial, verdana, times' color='#ffffff'><b>
        <?=($login_fabrica == 101 && $destinatario == 't') ? "CPF" : "Código ou CNPJ"?>
        </b></font>
    </td>
    <td align='center'>
        <font face='arial, verdana, times' color='#ffffff'><b>
        <?=($login_fabrica == 101 && $destinatario == 't') ? "Nome do Consumidor" : "Razão Social"?>
        </b></font>
    </td>
</tr>

<tr class="table_line">
    <td align='center'>
        <?=($login_fabrica == 101 && $destinatario == 't') ? pg_fetch_result($resVerConsumidor,0,consumidor_cpf) : $cnpj?>&nbsp;
    </td>
    <td align='center'>
        <?=($login_fabrica == 101 && $destinatario == 't') ? pg_fetch_result($resVerConsumidor,0,consumidor_nome) : $nome ?>&nbsp;
    </td>
</tr>
</table>

<table class="table" width='600' align='center' border='0' cellspacing='3' cellpadding='3'>
<tr class="menu_top">
    <td align='center'>
        <b>
            Tipo do Pedido
        </b>
    </td>
    <td align='center'>
        <b>
            Tabela de Preços
        </b>
    </td>
    <td align='center'>
        <b>
            Condição de Pagamento
        </b>
    </td>
    <td align='center'>
        <b>
            Tipo de Frete
        </b>
    </td>
</tr>

<tr class="table_line">
    <td align='center'>
        <? echo $nome_tipo_pedido; ?>&nbsp;
    </td>
    <td align='center'>
        <? echo $nome_tabela; ?>&nbsp;
    </td>
    <td align='center'>
        <? echo $nome_condicao; ?>&nbsp;
    </td>
    <td align='center'>
        <? echo $tipo_frete; ?>&nbsp;
    </td>
</tr>
</table>

<table class="table" width='600' align='center' border='0' cellspacing='3' cellpadding='3'>
<tr class="menu_top">
    <td align='center'>
        <b>
        Pedido Cliente
        </b>
    </td>
    <td align='center'>
        <b>
        Validade
        </b>
    </td>
    <td align='center'>
        <b>
        Entrega
        </b>
    </td>
</tr>

<tr class="table_line">
    <td align='center'>
        <? echo ($pedido_cliente) ? $pedido_cliente : $pedido_aux ?>&nbsp;
    </td>
    <td align='center'>
        <? echo $validade ?>&nbsp;
    </td>
    <td align='center'>
        <? echo $entrega ?>&nbsp;
    </td>
</tr>
</table>
<?
if($login_fabrica != 101){
?>
<table class="table" width='600' align='center' border='0' cellspacing='3' cellpadding='3'>
<tr class="menu_top">
    <td align='center'>
        <b>
        Mensagem
        </b>
    </td>
</tr>
<tr>
    <td>
        <? echo $obs; ?>&nbsp;
    </td>
</tr>
</table>
<?
}
?>
<br />
<hr width="600" />
<br />

<table class="table" width='600' align='center' border='0' cellspacing='3' cellpadding='3'>
<tr class="menu_top">
    <td align='center' width='33%'>
        <b>
        Emissão
        </b>
    </td>
    <td align='center' width='33%'>
        <b>
        Saída
        </b>
    </td>
    <td align='center' width='34%'>
        <b>
        Previsão de chegada
        </b>
    </td>
</tr>
<tr>
    <td>
        <?=($login_fabrica == 94) ? "<span style='color:#F00;font-size:13px;'>(*)</span>" : ""?>
        <input type="text" name="emissao" id='emissao' value="<?=$emissao?>" size="10" maxlength="10">
    </td>
    <td>
        <?=($login_fabrica == 94) ? "<span style='color:#F00;font-size:13px;'>(*)</span>" : ""?>
        <input type="text" name="saida" id='saida' value="<?=$saida?>" size="10" maxlength="10">
    </td>
    <td>
        <input type="text" name="previsao_chegada" id='previsao_chegada' value="<?=$previsao_chegada?>" size="10" maxlength="10">
    </td>
</tr>
</table>

<table class="table" width='600' align='center' border='0' cellspacing='3' cellpadding='3'>
    <tr class="menu_top">
        <td align="center">
            <b>
            CFOP
            </b>
        </td>
        <td align="center">
            <b>
            Transportadora
            </b>
        </td>
    </tr>
    <tr>
        <td>
            <?=($login_fabrica == 94) ? "<span style='color:#F00;font-size:13px;'>(*)</span>" : ""?>
            <input type="text" NAME="cfop" class="numeric" value="<?=$cfop?>" size="10" maxlength="10" />
        </td>
        <td>
            <input type="hidden" name="transportadora" value="">
            <input type="hidden" name="codigo" value="<?=$codigo?>">
            <input type="hidden" name="transportadora_codigo" value="<?=$transportadora_codigo?>">
            CNPJ <input type="text" name="transportadora_cnpj" size="14" maxlength="14" value="<?=$transportadora_cnpj?>" class="textbox" >&nbsp;
                <img src="../imagens/btn_buscar5.gif" border="0" align="absmiddle" onclick="javascript: fnc_pesquisa_transportadora (document.forms['frm_pedido'].transportadora_cnpj,'cnpj');" style="cursor:pointer;" />
            Nome <input type="text" name="transportadora_nome" size="30" maxlength="50" value="<?=$transportadora_nome?>" class="textbox" >&nbsp;
                <img src="../imagens/btn_buscar5.gif" border="0" align="absmiddle" onclick="javascript: fnc_pesquisa_transportadora (document.forms['frm_pedido'].transportadora_nome,'nome');" style="cursor:pointer;" />
        </td>
    </tr>
</table>
<table class="table" width='600' align='center' border='0' cellspacing='3' cellpadding='3'>
    <tr class="menu_top">
<?
if($login_fabrica == 101){
?>
        <td align='center' style="font-weight:bold;">
            Código Rastreio
        </td>
<?
}
?>
        <td align='center'>
            <b>
            Nota Fiscal
            </b>
        </td>
        <td align='center'>
            <b>
            Total Nota Fiscal
            </b>
        </td>
    </tr>
    <tr>
<?
if($login_fabrica == 101){
?>
        <td>
            <input type="text" name="rastreio" id='rastreio' value="<?=$rastreio?>" size="20" maxlength="20">
        </td>
<?
}
?>
        <td>
             <?=($login_fabrica == 94) ? "<span style='color:#F00;font-size:13px;'>(*)</span>" : ""?>
            <input type="text" name="nota_fiscal" id="nota_fiscal" value="<?=$nota_fiscal?>" size="12" maxlength="20">
        </td>
        <td>
            <?=($login_fabrica == 94) ? "<span style='color:#F00;font-size:13px;'>(*)</span>" : ""?>
            <input type="text" name="total_nota" id='total_nota' class='money' value="<?=$total_nota?>" size="12" style="text-align:right">
        </td>
    </tr>
</table>
<p>
    <table  border="0" cellspacing="1" cellpadding="0" align='center' class='tabela'>
<?
if ($login_fabrica!=95 and $login_fabrica != 108 and $login_fabrica != 111){?>
        <tr height="20" class="titulo_tabela">
            <td colspan='100%'>Se o item não foi atendido pela nota fiscal, favor deixar a quantidade faturada sem preenchimento.</td>
        </tr>
<?
}?>
        <tr height="20" class="titulo_coluna">
            <td align='center'>Referência</td>
            <td align='center'>Descrição</td>
            <td align='center'>Qtde</td>
            <td align='center'>Cancelada</td>
            <td align='center'>Faturada</td>
            <td align='center'>Pendente</td>
            <td align='center'>OS</td>
            <td align='center'>Qtde NF</td>
            <td align='center'>Valor</td>
            <td align='center'>Valor Total</td>
            <td align='center'>Cancelar Item</td>
        </tr>
<?
        if (strlen($pedido) > 0) {
            $sql_os = "SELECT tipo_pedido FROM tbl_pedido join tbl_tipo_pedido using(tipo_pedido)  WHERE pedido = $pedido and (descricao ~* 'gar' or pedido_em_garantia or descricao ~* 'bon')";
            $res_os = pg_query($con,$sql_os);

            if(pg_numrows($res_os) > 0){
            #JOIN    tbl_os_produto  ON tbl_os_produto.os_produto    = tbl_os_item.os_produto
            #JOIN    tbl_os          ON tbl_os.os                    = tbl_os_produto.os
                $sql = "SELECT  DISTINCT tbl_peca.peca,
                                tbl_pedido_item.pedido_item,
                                tbl_os.sua_os,
                                tbl_os.os,
                                tbl_pedido_cancelado.os AS os_cancelada,
                                tbl_pedido_cancelado.qtde AS qtde_peca_cancelada
                        FROM    tbl_pedido_item
                        JOIN    tbl_peca        USING (peca)
                        JOIN    tbl_pedido      USING (pedido)
                        JOIN    tbl_os_item     USING (pedido_item)
                        JOIN    tbl_os_produto  USING (os_produto)
                        JOIN    tbl_os          USING (os)
                   LEFT JOIN    tbl_pedido_cancelado ON  tbl_pedido_item.pedido             = tbl_pedido_cancelado.pedido
                                                     AND tbl_pedido_item.peca               = tbl_pedido_cancelado.peca
                                                     AND tbl_pedido_cancelado.os            = tbl_os.os
                                                     AND tbl_pedido_cancelado.pedido_item    = tbl_pedido_item.pedido_item
                        WHERE   tbl_pedido_item.pedido = $pedido
                  ORDER BY      tbl_pedido_item.pedido_item;";
            }
        else{
            PEDIDO_GARANTIA_SEM_OS:

                $sql = "SELECT  tbl_peca.peca,
                                tbl_pedido_item.pedido_item
                        FROM    tbl_pedido_item
                        JOIN    tbl_peca    USING (peca)
                        JOIN    tbl_pedido  USING (pedido)
                        WHERE   tbl_pedido_item.pedido = $pedido
                  ORDER BY      tbl_pedido_item.pedido_item;";
            }
            $ped = pg_query ($con,$sql);

        $qtde_item = @pg_numrows($ped);
        if($qtde_item == 0 and pg_num_rows($res_os) > 0 ) {
        goto PEDIDO_GARANTIA_SEM_OS;
        }
        }

        if (strlen($pedido) > 0) {
            echo "<input class='frm' type='hidden' name='qtde_item' value='$qtde_item'>";

            $sql_os = "SELECT COUNT(1) FROM tbl_os_item WHERE pedido = $pedido";
            $res_os = pg_exec($con,$sql_os);
            $pedido_os = pg_result($res_os,0,0);

            $botao = 0;
            if($pedido_os > 0){
                $campo = "sum(tbl_os_item.qtde) AS qtde, tbl_faturamento_item.os ";

            } else{
        $campo = "tbl_pedido_item.qtde";
        $campo_group = ", tbl_pedido_item.qtde ";
            }

            if(($login_fabrica == 40 or $login_fabrica == 99 or $login_fabrica == 101 ) AND $pedido_os > 0){
                $campo .= " ,tbl_faturamento_item.qtde AS pendente ";
            }else{
                $campo .= " ,(tbl_pedido_item.qtde - (tbl_pedido_item.qtde_faturada + tbl_pedido_item.qtde_cancelada)) AS pendente ";
                $campo_group .= ", (tbl_pedido_item.qtde - (tbl_pedido_item.qtde_faturada + tbl_pedido_item.qtde_cancelada)) ";
            }

            for ($i = 0 ; $i < $qtde_item ; $i++) {
                if (strlen($pedido) > 0) {
                    if (@pg_numrows($ped) > 0) {
                        $peca        = trim(@pg_result($ped,$i,peca));
                        $pedido_item = trim(@pg_result($ped,$i,pedido_item));

                        if($pedido_os > 0){
                            $qtde_peca_cancelada = "";
                            $xsua_os      = trim(@pg_result($ped,$i,sua_os));
                            $xos      = trim(@pg_result($ped,$i,os));
                            $os_cancelada = trim(@pg_result($ped,$i,os_cancelada));
                            $qtde_peca_cancelada = trim(@pg_result($ped,$i,qtde_peca_cancelada));
                            $osS      = trim(@pg_result($ped,$i,os));

                            if ($login_fabrica == 101) {
                                //$fat_os_item = pg_fetch_result($ped, $i, "fat_os_item");
                            }

                            $joins = "JOIN tbl_os_item    USING(pedido_item)
                                      JOIN tbl_os_produto USING (os_produto)
                                      JOIN tbl_os         USING (os)
                                      ";
                            $cond = (strlen($osS) > 0) ? " AND tbl_os_produto.os = $osS" : "";

                           $cond_faturamento = " AND tbl_faturamento_item.os = tbl_os.os";
                        }
                        $qtde_faturamento = $_POST['qtde_faturamento_'.$i];
                    }

                    if ($login_fabrica == 40 && strtoupper($tipo_pedido) == "GARANTIA") {
                        // $where_fat_os_item = " AND tbl_faturamento_item.os_item = $fat_os_item ";
                    }

                    $sql = "SELECT  tbl_pedido_item.pedido_item                    ,
                                    $campo                                         ,
                                    tbl_pedido_item.qtde_cancelada                 ,
                                    tbl_pedido_item.preco                          ,
                                    tbl_peca.referencia                            ,
                                    tbl_peca.descricao                             ,
                                    tbl_faturamento.nota_fiscal                    ,
                                    tbl_pedido_item.qtde_faturada
                            FROM    tbl_pedido_item
                            JOIN    tbl_peca USING (peca)
                            $joins
                       LEFT JOIN    tbl_faturamento_item    ON  tbl_faturamento_item.peca        = tbl_pedido_item.peca
                                                            AND tbl_faturamento_item.pedido_item = tbl_pedido_item.pedido_item
                                    $where_fat_os_item
                                    $cond_faturamento
                       LEFT JOIN    tbl_faturamento         ON tbl_faturamento.faturamento = tbl_faturamento_item.faturamento
                            WHERE   tbl_pedido_item.pedido = $pedido
                            AND     tbl_pedido_item.peca   = $peca
                            AND     tbl_pedido_item.pedido_item = $pedido_item
                            $cond
                            GROUP BY tbl_pedido_item.pedido_item ,
                                     tbl_faturamento_item.os ,
                                     tbl_faturamento_item.qtde ,
                                     tbl_pedido_item.qtde_cancelada ,
                                     tbl_pedido_item.preco ,
                                     tbl_peca.referencia ,
                                     tbl_peca.descricao ,
                                     tbl_faturamento.nota_fiscal ,
                    tbl_pedido_item.qtde_faturada
                    $campo_group;";
                    // echo nl2br($sql); exit;
                    $aux_ped = pg_query ($con,$sql);
                     if (pg_numrows($aux_ped) > 0) {
                        $nota_fiscal     = trim(pg_result($aux_ped,0,nota_fiscal));
                        $item            = trim(pg_result($aux_ped,0,pedido_item));
                        $peca_referencia = trim(pg_result($aux_ped,0,referencia));
                        $peca_descricao  = trim(pg_result($aux_ped,0,descricao));
                        $qtde            = trim(pg_result($aux_ped,0,qtde));
                        $qtde_cancelada  = trim(pg_result($aux_ped,0,qtde_cancelada));
                        $preco           = trim(pg_result($aux_ped,0,preco));
                        $pendente        = trim(pg_result($aux_ped,0,pendente));
                        $qtde_faturada   = trim(pg_result($aux_ped,0,qtde_faturada));
                        if(in_array($login_fabrica,array(40, 99,101)) AND $pedido_os > 0){
                            $os_faturamento = trim(pg_result($aux_ped,0,os));
                            if($login_fabrica == 40){
                                $qtde_cancelada = pg_fetch_result($aux_ped, 0, "qtde_cancelada");
                            }else{
                                $$pendente = $qtde - $pendente - $qtde_peca_cancelada;
                                $qtde_cancelada = ($qtde_peca_cancelada ) ? $qtde_peca_cancelada : 0;
                            }

                            if($login_fabrica == 40){

                                $sql_qtde = "SELECT SUM(qtde) AS qtde
                                            FROM tbl_os_item
                                            WHERE
                                                fabrica_i = {$login_fabrica}
                                                AND os_produto IN(SELECT os_produto FROM tbl_os_produto WHERE os = {$osS})
                                                AND pedido_item = {$item}";
                                $res_qtde = pg_query($con, $sql_qtde);

                                if(pg_num_rows($res_qtde) > 0){

                                    $qtde_peca = pg_fetch_result($res_qtde, 0, "qtde");

                                    $sql_qtde_faturada_os = "SELECT SUM(qtde) AS qtde FROM tbl_faturamento_item WHERE pedido_item = {$item} AND os = {$osS}";
                                    $res_qtde_faturada_os = pg_query($con, $sql_qtde_faturada_os);

                                    if(pg_num_rows($res_qtde_faturada_os) > 0){
                                        $qtde_faturada = (strlen(pg_fetch_result($res_qtde_faturada_os, 0, "qtde")) > 0) ? pg_fetch_result($res_qtde_faturada_os, 0, "qtde") : 0;
                                    }else{
                                        $qtde_faturada = 0;
                                    }

                                    $sql_qtde_cancelada_os = "SELECT qtde FROM tbl_pedido_cancelado WHERE pedido_item = {$item} AND os = {$osS} AND fabrica = {$login_fabrica}";
                                    $res_qtde_cancelada_os = pg_query($con, $sql_qtde_cancelada_os);

                                    if(pg_num_rows($res_qtde_cancelada_os) > 0){
                                        $qtde_cancelada = (strlen(pg_fetch_result($res_qtde_cancelada_os, 0, "qtde")) > 0) ? pg_fetch_result($res_qtde_cancelada_os, 0, "qtde") : 0;
                                    }else{
                                        $qtde_cancelada = 0;
                                    }

                                    // $qtde_faturada  = (strlen($qtde_faturada) == 0) ? 0 : $qtde_faturada;
                                    // $qtde_cancelada = (strlen($qtde_cancelada) == 0) ? 0 : $qtde_cancelada;
                                    $pendente       = (strlen($pendente) == 0) ? 0 : $pendente;

                                    // echo "Total:".$qtde_peca." - Cancelada:".$qtde_cancelada." - Pendente:".$pendente." - Faturada:".$qtde_faturada."<br />";

                                    if($qtde_faturada > 0){

                                        $qtde_cancelada = ($qtde_cancelada > 0) ? $qtde_cancelada : 0;
                                        $pendente = ($pendente > 0) ? ($qtde_peca - $qtde_faturada) - $qtde_cancelada : 0;

                                    }else if($qtde_faturada == 0){

                                        $qtde_cancelada = ($qtde_cancelada > 0) ? $qtde_cancelada : 0;
                                        $pendente = $qtde_peca - $qtde_cancelada;

                                    }

                                }

                            }

                        }
                    } else {
                        $qtde_faturada = 0;
                        $qtde_cancelada = 0;
                        $os_faturamento = "";
                        $peca_referencia = "";
                        $peca_descricao = "";
                    }

                    if (strlen($pendente) > 0 AND !in_array($login_fabrica, array(101))){
                        $xpendente = $pendente;
                    }else{
                        $xpendente = $qtde - $qtde_faturada - $qtde_cancelada;
                    }

                }
?>
        <input type="hidden" name="item[]" value="<?=$item?>">
                <?$cor = ($i % 2 ) ? '#F1F4FA' : "#F7F5F0";?>
        <tr bgcolor="<?=$cor?>">
            <td align="left"><?=$peca_referencia?></td>
            <td align="left" nowrap><?=$peca_descricao?></td>
            <td align="center">
                <?=$qtde;?>
                <input type="hidden" name="peca[]"                                     value="<?=$peca?>">
                <input type="hidden" name="qtde[]"            id="qtde[]"             value="<?=$qtde?>">
                <input type="hidden" name="qtde_cancelada[]"  id="qtde_cancelada[]"   value="<?=$qtde_cancelada?>">
                <input type="hidden" name="pendente[]"                                 value="<?=$xpendente?>">
                <input type="hidden" name="qtde_faturada[]"                            value="<?=$qtde_faturada?>">
                <input type="hidden" name="preco[]"                                    value="<?=$preco?>">
            </td>
            <td align="center" style="color:#FF0000"><?=$qtde_cancelada?></td>
            <td align="center" style="color:#FF0000"><?=$qtde_faturada?></td>
<?
                if (($qtde ==  $qtde_faturada + $qtde_cancelada) AND $xpendente <= 0  ) {
?>
            <td align="center" style="color:#FF0000">0</td>
<?
                }else{
?>
            <td align="center" style="color:#FF0000"><?=$xpendente?></td>
<?
                }
?>
            <td>
                <input type="hidden" name="os[]" value="<?=$xos?>">
                <input type="hidden" name="sua_os[]" value="<?=$xsua_os?>">
                &nbsp;<?=$xsua_os?>
            </td>
<?
                #if ($pendente == 0 AND strlen($pendente) > 0){
                if($qtde ==  ($qtde_faturada + $qtde_cancelada)){
?>
            <input type="hidden" name="qtde_faturamento[]" value="">
            <td align="center"><?=$qtde_faturada?></td>
            <td align="right" style="padding-right:10px;"><?=number_format($preco,'2',',','.')?></td>
<?
                    $botao++;
                }else{
                    #if ($xpendente>0) {
                        if ($login_fabrica==95 or $login_fabrica == 101 or $login_fabrica == 108 or $login_fabrica == 111){
?>
				            <td align="center">
				                <input class="frm numeric" type="text" id="qtde_faturamento_<?=$i?>" name="qtde_faturamento[]" size="5" value="<?=$qtde_faturamento?>" rel="inputtext" onBlur="javascript: fnc_valida_qtde('<?=$i?>')">
				            </td>
<?
                        }else{
                            if($login_fabrica == 40 && $qtde_faturada > 0 && $pendente > 0){

                                ?>

                                <td align="center">
                                    <input class="frm" type="text" name="qtde_faturamento[]" size="5" value="<?=$_POST['qtde_faturamento'][$i]?>">
                                </td>

                                <?php

                            }else if(strlen($os_faturamento) > 0){
?>
            					<td><?=$nota_fiscal?></td>
<?
                            }else{
                                if(strlen($os_cancelada) == 0){
?>
						            <td align="center">
						                <input class="frm" type="text" name="qtde_faturamento[]" size="5" value="<?=$_POST['qtde_faturamento'][$i]?>">
						            </td>
<?
                                }else if($login_fabrica == 40){
                                    if(strlen($os_cancelada) > 0 && $xpendente > 0){
?>
                                        <td align="center">
                                            <input class="frm" type="text" name="qtde_faturamento[]" size="5" value="<?=$_POST['qtde_faturamento'][$i]?>">
                                        </td>
<?
                                    }else{
?>
                                        <input type="hidden" name="qtde_faturamento[]" value="">
                                        <td>&nbsp;</td>
<?
                                    }
                                }else{
?>
						            <input type="hidden" name="qtde_faturamento[]" value="">
						            <td>&nbsp;</td>
<?
                                }
                            }
                        }
                        if(strlen($os_faturamento) > 0){
                            if($login_fabrica == 40 && $qtde_faturada > 0 && $pendente > 0){
                            ?>
                                <td align="center">
                                    <input type="text" class="frm" name="preco_tab[]" size="5" value="<?=number_format($preco,'2',',','.')?>" style="text-align:right;" readonly>
                                </td>
                                <td align="center">
                                    <strong><?=number_format($qtde_faturada * $preco,'2',',','.')?></strong>
                                </td>
                            <?php
                            }else{
                            ?>
            				    <td>&nbsp;</td>
                            <?
                            }
                        }else{
                            if(strlen($os_cancelada) == 0){
?>
					            <td align="center">
					                <input type="text" class="frm" name="preco_tab[]" size="5" value="<?=number_format($preco,'2',',','.')?>" style="text-align:right;" readonly>
					            </td>

					            <td align="center">
					                <strong><?=number_format($qtde * $preco,'2',',','.')?></strong>
					            </td>
<?
                            }else{
?>
            					<td align="right" style="padding-right:10px;"><?=number_format($preco,'2',',','.')?></td>

                                <?php

                                if($login_fabrica == 40){
                                    ?>
                                    <td align="center">
                                        <strong><?=number_format($qtde * $preco,'2',',','.')?></strong>
                                    </td>
                                    <?php
                                }

                                ?>
<?
                            }
                        }
                    #}else {
?>
           <!-- <td align="center" colspan="3">
                <? #echo ($qtde == $qtde_cancelada)? 'Item cancelado total':'Item faturado'; ?>
            </td> -->
<?
                    #}
                }

                $mosta_form = "";
                if($osS){
                    #if($os_cancelada <> $osS){
                    if(strlen($os_cancelada) == 0 && $xpendente > 0){
                        $mosta_form = "sim";
                    }

                    if($login_fabrica == 40){

                        if(strlen($os_cancelada) > 0 && $pendente > 0){
                            $mosta_form = "sim";
                        }

                    }

                }else{
                    $mosta_form = "sim";
                }

                if($login_fabrica == 81 OR $login_fabrica == 114){
                    $xos = $osS;
                }

                if ($xpendente > 0 AND !empty($mosta_form))  {
                    if(strlen($os_faturamento) > 0 and $xpendente == 0){ echo "ak";
?>
                        <td>&nbsp;</td>
<?
                    }else{
?>
                        <td align="left" nowrap>
                            Qtde <input type="text" size="5" name="qtde_a_cancelar_<?=$i?>" class="frm">
                            Motivo: <input type="text" name="motivo_<?=$i?>" class="frm">
                            <a href="javascript: cancelaItens('<? echo str_replace("","",$peca_descricao); ?>',<?=$item?>,<?=$pedido?>,'<?=$xos?>',document.forms['frm_pedido'].motivo_<?=$i?>.value,document.forms['frm_pedido'].qtde_a_cancelar_<?=$i?>.value,<?=$xpendente?>);">
                                <img src="imagens/icone_deletar.png" style="padding: 0; margin: 0;width: 10px;">
                            </a>
                        </td>
<?
                    }
                }
?>
        </tr>
<?
            }
        }
?>
        <tr>
            <td height="27" valign="middle" align="center" colspan="100%" bgcolor="#FFFFFF">
<?
    if ($qtde_item <> $botao){
?>
                <input type='hidden' name='btn_acao' value=''>
                <input type='button' value='Gravar' onclick="javascript: gravarFormulario();" ALT="Gravar formulário" border='0' style="cursor:pointer;">
<?
    }else{
?>
                <img src="imagens_admin/btn_voltar.gif" onclick="javascript: history.back();" ALT="Voltar" border='0' style="cursor:pointer;">
<?
    }
?>
            </td>
        </tr>
    </table>
</p>
</form>


<? include "rodape.php"; ?>
