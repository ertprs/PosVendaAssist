<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios = "cadastros";
include 'autentica_admin.php';
include 'funcoes.php';
include_once __DIR__ . '/../class/AuditorLog.php';

use model\ModelHolder;
include_once "../class/tdocs.class.php";
$tDocs  = new TDocs($con, $login_fabrica);
//error_reporting(E_ALL);
//ini_set('display_errors',1);

if ($_POST["gerar_excel"] && in_array($login_fabrica, [104])) {

    if ($S3_sdk_OK) {
        include_once S3CLASS;

        if ($S3_online) {
            $s3 = new anexaS3('ve', (int) $login_fabrica);

            $s3->setTempoExpiracaoLink(21600);
        }
    }

    $condLinhas = implode(",", $_POST['excel_geral_listas']);

    $data = date("d-m-Y-H:i");

    $fileName = "relatorio_pecas_todos_produtos-{$data}.xls";

    $file = fopen("/tmp/{$fileName}", "w");

    $thead = "<table border='1'>
                    <thead>
                        <tr>
                            <th colspan='5' bgcolor='#D9E2EF' color='#333333' style='color: #333333 !important;'>
                                Relatório Geral de Lista Básica dos Produtos
                            </th>
                        </tr>
                        <tr>
                            <th>Código Produto</th>
                            <th>Descrição Produto</th>
                            <th>Código Peça</th>
                            <th>Descrição Peça</th>
                            <th>Vista Explodida</th>
                        </tr>
                    </thead>
                    <tbody>
    ";

    fwrite($file, $thead);

    $sqlListaBasica = "SELECT tbl_produto.referencia referencia_produto,
                              tbl_produto.descricao descricao_produto,
                              tbl_peca.referencia referencia_peca,
                              tbl_peca.descricao descricao_peca,
                              tbl_produto.produto
                       FROM tbl_lista_basica
                       JOIN tbl_produto ON tbl_produto.produto = tbl_lista_basica.produto
                       AND tbl_produto.fabrica_i = {$login_fabrica}
                       JOIN tbl_peca ON tbl_peca.peca = tbl_lista_basica.peca
                       AND tbl_peca.fabrica = {$login_fabrica}
                       WHERE tbl_lista_basica.fabrica = {$login_fabrica}
                       AND tbl_produto.ativo IS TRUE
                       AND tbl_produto.linha IN ({$condLinhas})
                       ";
    $resListaBasica = pg_query($con, $sqlListaBasica);

    if (pg_num_rows($resListaBasica) > 0) {

        while ($dados = pg_fetch_array($resListaBasica)) {

            $referencia_produto = $dados['referencia_produto'];
            $descricao_produto  = $dados['descricao_produto'];
            $referencia_peca    = $dados['referencia_peca'];
            $descricao_peca     = $dados['descricao_peca'];
            $produto            = $dados['produto'];

            if ($produto != $produto_anterior || !isset($produto_anterior)) {
                $sql = "SELECT DISTINCT comunicado, extensao
                        FROM tbl_comunicado
                        LEFT JOIN tbl_comunicado_produto CP USING (comunicado)
                        WHERE tbl_comunicado.fabrica = $login_fabrica
                        AND tbl_comunicado.tipo = 'Vista Explodida'
                        and (tbl_comunicado.produto = $produto OR CP.produto = $produto)
                        ORDER BY comunicado DESC
                        LIMIT 1";
                $res = pg_query($con,$sql);

                if (pg_num_rows($res) > 0) {
                    $vista_explodida = pg_fetch_result($res,0,'comunicado');
                    $ext             = pg_fetch_result($res,0,'extensao');
                }

                if (strlen($vista_explodida) > 0) {
                    $linkVE = null;
                    if ($S3_online) {
                        if ($s3->temAnexos($vista_explodida)){
                            $linkVE = $s3->url;
                        }
                    } else {
                        // echo '../comunicados/'.$vista_explodida.'.'.$ext;
                        if (file_exists ('../comunicados/'.$vista_explodida.'.'.$ext)) {
                            $linkVE = "../comunicados/$vista_explodida.$ext";
                        }
                    }
                }
            }

            $body .="       <tr>
                                <td nowrap align='center' valign='top'>{$referencia_produto}</td>
                                <td nowrap align='center' valign='top'>{$descricao_produto}</td>
                                <td nowrap align='center' valign='top'>{$referencia_peca}</td>
                                <td nowrap align='center' valign='top'>{$descricao_peca}</td>
                                <td nowrap align='center' valign='top'><a href='{$linkVE}' target='_blank'>Vista Explodida</a></td>
                            </tr>";

            $produto_anterior = $dados['produto'];

        }

        $body .= "</tbody>
            </table>";

        fwrite($file, $body);
        fclose($file);

        if (file_exists("/tmp/{$fileName}")) {
            system("mv /tmp/{$fileName} xls/{$fileName}");

            echo "xls/{$fileName}";
        }

    }
exit;
}

if ($login_fabrica == 158) {
    if ($_serverEnvironment == "production") {
        $chave_persys = "12984374000259-7a4e7d2cb15c403b7a33c73ccc4dc4e9";
    }else{
        $chave_persys = "4716427000141-dc3442c4774e4edc44dfcc7bf4d90447";
    }

    require_once "../class/importa_arquivos/ImportaArquivo.php";
}

if ($login_fabrica == 1) {
    require "../classes/ParametrosAdicionaisFabrica.php";
    $parametrosAdicionaisObject = new ParametrosAdicionaisFabrica($login_fabrica);

    require "../classes/form/GeraComboType.php";
    require_once "../class/importa_arquivos/ImportaArquivo.php";
}

$fabrica_cadastra_lbm_excel = in_array($login_fabrica, array(40,46));
$fabrica_cadastra_lbm_txt = in_array($login_fabrica, array(1,158));

if($_GET['produto']){
    $produto = $_GET['produto'];
}

/*if ($login_fabrica == 175){
    if($_GET['ordem_producao']){
        $ordem_producao = $_GET['ordem_producao'];
    }
}*/

if($_POST['ajax_item']){

    $produto       = $_POST['produto'];
    $lbm           = $_POST['lbm'];
    $peca          = $_POST['peca_referencia'];
    $peca_pai      = $_POST['peca_pai'];
    $ordem         = $_POST['ordem'];
    $serie_inicial = $_POST['serie_inicial'];
    $serie_final   = $_POST['serie_final'];
    $posicao       = $_POST['posicao'];
    $type          = $_POST['type'];
    $qtde          = $_POST['qtde'];
    $desgaste      = $_POST['desgaste'];
    $ativo         = $_POST['ativo'];
    if(in_array($login_fabrica, array(195))){

        if (strlen($_POST['data_de']) > 0) {
            $data_de = date('Y-m-d', strtotime(str_replace('/', '-', $_POST['data_de'])));
            $xparametros_adicionais["data_de"] = $data_de;
        }
        if (strlen($_POST['data_ate']) > 0) {
            $data_ate = date('Y-m-d', strtotime(str_replace('/', '-', $_POST['data_ate'])));
            $xparametros_adicionais["data_ate"] = $data_ate;
        }

        if (isset($xparametros_adicionais["data_de"]) || isset($xparametros_adicionais["data_ate"])) {
            $xxparametros_adicionais = json_encode($xparametros_adicionais);
        }

    }

    if(in_array($login_fabrica, array(15)) && strlen($_POST['data_fabricacao']) > 0){
        list($diaFB, $mesFB, $anoFB) = explode("/", $_POST['data_fabricacao']);
        $data_fabricacao = "{$anoFB}-{$mesFB}-{$diaFB}";
    }

    if ($login_fabrica == 1 && strlen($ordem) > 0) {

        if (strlen($lbm) > 0) {
            $aux_sql = "SELECT peca FROM tbl_lista_basica WHERE fabrica = $login_fabrica AND produto = $produto AND ordem = '$ordem' AND lista_basica <> $lbm";
        } else {
            $aux_sql = "SELECT peca FROM tbl_lista_basica WHERE fabrica = $login_fabrica AND produto = $produto AND ordem = '$ordem'";
        }

        $aux_res = pg_query($con, $aux_sql);

        $contador_res_aux = pg_num_rows($aux_res);

        if ($contador_res_aux > 0) {
            for ($x = 0; $x < $contador_res_aux; $x++) {
                $aux_row = pg_fetch_result($aux_res, $x, 'peca');
                $aux_sql = "SELECT referencia || ' - ' || descricao as peca FROM tbl_peca WHERE peca = $aux_row LIMIT 1";
                $aux_res2 = pg_query($con ,$aux_sql);
                $aux_pec = pg_fetch_result($aux_res2, 0, 'peca');
                $msg_erro .= "AVISO DE ORDEM DUPLICADA!\nVocê tentou cadastrar a ordem \"$ordem\" mas ela já está vinculada à peça \"$aux_pec\".\n\n";
            }
            echo "no|$msg_erro";
            exit;
        }
    }

    if ($usa_versao_produto) {
        $type = $_POST['versao'];
    }

    if(in_array($login_fabrica, array(153))){
        $type = $_POST['tipo'];
    }

    if (in_array($login_fabrica, array(169,170))) {
        $serie = $_POST['serie'];
    }

    if ($login_fabrica == 15) {
        $somente_kit = ($_POST['somente_kit'] == "t") ? 't' : 'f';
    }else{
        $somente_kit = 'f';
    }

    $ordem = trim ($ordem);
    $posicao = strlen(trim($posicao)) ? "'$posicao'" : 'null';

    $peca_referencia = $peca;

    $serie_inicial = trim ($serie_inicial);
    $serie_inicial = str_replace (".","",$serie_inicial);
    $serie_inicial = str_replace ("-","",$serie_inicial);
    $serie_inicial = str_replace ("/","",$serie_inicial);
    $serie_inicial = str_replace (" ","",$serie_inicial);

    $serie_final   = trim ($serie_final);
    $serie_final = str_replace (".","",$serie_final);
    $serie_final = str_replace ("-","",$serie_final);
    $serie_final = str_replace ("/","",$serie_final);
    $serie_final = str_replace (" ","",$serie_final);

    if (strlen($type) == 0) $type = "null";
    else                    $type = "'$type'";

    if ($login_fabrica == 15){
        $type = (!empty( $_POST['unica_os'] )) ? "'".$_POST['unica_os']."'" : "null" ;
    }

    if (strlen($peca_pai)==0) {
        $peca_pai = 'null';
    }

    if (strlen($qtde) == 0) $aux_qtde = 1;
    else                    $aux_qtde = $qtde;

    if (strlen($desgaste) == 0){
        $aux_desgaste = "null";
    }else{
        $aux_desgaste = $desgaste;
    }

    if (strlen($ordem) == 0) $ordem = "null";

    if($login_fabrica == 50) {
        if(strlen($ativo) == 0) {
            $ativo = "f";
        }else{
            $ativo = "t";
        }
    }else{
        if (strlen($ativo) == 0) $ativo = 't';
    }

    if (strlen($peca) > 0 AND empty($lbm)) {
        $sql_p = "SELECT peca, referencia, descricao FROM tbl_peca WHERE referencia = '{$peca}' AND fabrica = {$login_fabrica};";
        $res_p = pg_query($con, $sql_p);

        if (pg_num_rows($res_p) > 0) {
            $pecaNova = pg_fetch_result($res_p, 0, peca);

            $whereOrdem = "AND ordem = $ordem";
            if ($ordem == 'null') {
                $whereOrdem = "AND ordem is null";
            }

            $wherePosicao = "AND posicao = $posicao";
            if ($posicao == 'null') {
                $wherePosicao = "AND posicao is null";
            }

            $sqlVal = "
                SELECT
                    lista_basica
                FROM tbl_lista_basica
                WHERE produto = $produto
                AND ativo IS TRUE
                AND peca = {$pecaNova}
                AND fabrica = {$login_fabrica}
                {$wherePosicao}
                {$whereOrdem};
            ";
            $resVal = pg_query($con,$sqlVal);

            if (pg_num_rows($resVal) > 0) {
                $msg_erro .= traduz("Já existe peça cadastrada na ordem e posição informada!");
            }
        }
    }

    /*Inicia o AuditorLog Pedido */
    $auditorLog = new AuditorLog();    
    $auditorLog->retornaDadosTabela("tbl_lista_basica", array("produto"=>$produto, "fabrica"=>$login_fabrica) );

    $res = pg_query ($con,"BEGIN TRANSACTION");

    if ($login_fabrica == 171) {
        $xpeca = explode("/", $peca);
        $peca  = trim($xpeca[0]);
    }

    if (strlen($msg_erro) == 0) {
        if (strlen($peca) > 0) {
            $sql = "SELECT peca, referencia, descricao FROM tbl_peca WHERE referencia = '$peca' AND fabrica = $login_fabrica";

            $res = @pg_query ($con, $sql);

            if (pg_num_rows($res) == 0) {
                $msg_erro .= traduz("Peça % não cadastrada", null, null, [$peca]);
            }else{
                $peca = pg_fetch_result($res, 0, 0);
                $referencia = pg_fetch_result($res, 0, referencia);
                $descricao = pg_fetch_result($res, 0, descricao);

                $para = "PARA: $referencia - $descricao, quantidade: $qtde \n ";
                $paraNovos = " $referencia - $descricao, quantidade: $qtde \n ";

                if (!empty($serie)) {
                    $sqlSerie = "SELECT numero_serie FROM tbl_numero_serie WHERE fabrica = {$login_fabrica} AND (serie = UPPER('{$serie}') OR serie = UPPER('S{$serie}')) AND produto = {$produto};";
                    $resSerie = pg_query($con,$sqlSerie);
                }

                $sqlListaBasica = "
                    SELECT
                        qtde
                    FROM tbl_lista_basica
                    WHERE produto = {$produto}
                    AND peca = {$peca}
                    AND fabrica = {$login_fabrica};
                ";

                $resListaBasica = pg_query($con, $sqlListaBasica);

                if (!empty($lbm)) {
                    if (!empty($serie)) {
                        $whereSerie = "AND (tbl_numero_serie.serie = UPPER('{$serie}') OR tbl_numero_serie.serie = UPPER('S{$serie}'))";
                    }
                    $sqlVerLista = "
                        SELECT
                            lista_basica,
                            'LB' AS tipo
                        FROM tbl_lista_basica
                        WHERE fabrica = {$login_fabrica}
                        AND lista_basica = {$lbm}
                        AND produto = {$produto}
                        UNION
                        SELECT
                            tbl_numero_serie_peca.numero_serie_peca AS lista_basica,
                            'NS' AS tipo
                        FROM tbl_numero_serie_peca
                        JOIN tbl_numero_serie USING(numero_serie,fabrica)
                        WHERE tbl_numero_serie_peca.fabrica = {$login_fabrica}
                        AND tbl_numero_serie_peca.numero_serie_peca = {$lbm}
                        {$whereSerie};
                    ";

                    $resVerLista = pg_query($con,$sqlVerLista);

                    if (pg_num_rows($resVerlista) > 0) {
                        $lbm = pg_fetch_result($resVerLista, 0, lista_basica);
                        $tipo = pg_fetch_result($resVerLista, 0, tipo);
                    }
                }

                if ($tipo == 'NS' || (!empty($serie) && pg_num_rows($resListaBasica) == 0 && in_array($login_fabrica, array(169,170)))) {
                    $insert = 0;

                    if (pg_num_rows($resSerie) > 0) {
                        if(empty($lbm)) {
                            $insert = 1;
                            $numero_serie = pg_fetch_result($resSerie, 0, numero_serie);
                            $sql = "
                                INSERT INTO tbl_numero_serie_peca
                                    (fabrica,serie_peca,referencia_peca,ordem,numero_serie,peca,qtde)
                                VALUES
                                    ({$login_fabrica},'SEM SERIE','{$referencia}',{$ordem},{$numero_serie},{$peca},{$qtde})
                                RETURNING numero_serie_peca;
                            ";
                        } else {
                            $tinhaListaBasica = true;

                            $sql = "
                                SELECT *
                                FROM tbl_numero_serie_peca
                                WHERE fabrica = {$login_fabrica}
                                AND numero_serie_peca = {$lbm};
                            ";

                            $res = pg_query($con,$sql);

                            if (pg_num_rows($res) == 0) {
                                $msg_erro .= traduz("Lista Básica por número de série não encontrada");
                            } else {

                                $qtdeListaBasica = pg_fetch_result($res, 0, qtde);
                                $de = "DE: $referencia - $descricao, quantidade: $qtdeListaBasica \n";

                                $sql = "
                                    UPDATE tbl_numero_serie_peca SET
                                        referencia_peca = '{$referencia}',
                                        ordem = {$ordem},
                                        peca = {$peca},
                                        qtde = {$qtde}
                                    WHERE fabrica = {$login_fabrica}
                                    AND numero_serie_peca = {$lbm};

                                ";
                            }
                        }

                        if (empty($msg_erro)) {
                            $res = pg_query($con,$sql);
                            $msg_erro .= pg_errormessage($con);

                            if(empty($msg_erro)){
                                if ($insert) {
                                    $lbm = pg_fetch_result($res, 0, 0);
                                }
                            }
                        }
                    } else {
                        $msg_erro .= traduz("Número de série inválido");
                    }
                        
                } else {

                    if (pg_num_rows($resListaBasica) > 0) {
                        $tinhaListaBasica = true;
                        $qtdeListaBasica = pg_fetch_result ($resListaBasica,0,qtde);
                        $de = "DE: $referencia - $descricao, quantidade: $qtdeListaBasica \n";
                    }

                    if (strlen($peca_pai) > 0) {
                        $sql = "SELECT peca FROM tbl_peca WHERE referencia = '{$peca_pai}' AND fabrica = {$login_fabrica};";
                        $res = @pg_query ($con, $sql);
                        if (@pg_num_rows ($res) > 0) {
                            $peca_pai = @pg_fetch_result ($res,0,0);
                        }
                    }

                    if (in_array($login_fabrica, array(3,138)) AND empty($lbm) ) {
                        
                        $sql = "
                            SELECT
                                COUNT(peca) AS total
                            FROM tbl_lista_basica
                            WHERE produto = {$produto}
                            AND peca = {$peca}
                            AND fabrica = {$login_fabrica}
                            HAVING COUNT(peca) > 0;
                        ";

                        $res = @pg_query ($con, $sql);

                        if (@pg_num_rows ($res) > 0) {
                            $total = pg_fetch_result($res,0,total);
                            $msg_erro .= traduz("Peça % já cadastrada na lista básica deste produto", null, null, [$peca_referencia]);
                        }
                    }

                    if ($login_fabrica == 1) {

                        $ins_pa = "";
                        $ins_pa_c = "";
                        $param_adc_update = "";

                        if(strlen($peca) > 0){

                            $sql_pa = "SELECT parametros_adicionais FROM tbl_peca WHERE peca = {$peca} AND fabrica = {$login_fabrica};";
                            $res_pa = pg_query($con,$sql_pa);

                            if (pg_num_rows($res_pa) > 0) {

                                $info_pa = pg_fetch_result($res_pa, 0, parametros_adicionais);
                                $info_pa = json_decode($info_pa,true);

                                if ($info_pa['item_revenda'] == 't') {

                                    if (empty($lbm)) {

                                        $parametros_adicionais_lb['item_revenda'] = "t";
                                        $parametros_adicionais_lb = json_encode($parametros_adicionais_lb);
                                        $ins_pa = "parametros_adicionais,";
                                        $ins_pa_c = "'$parametros_adicionais_lb',";

                                    }else{

                                        $sql_lb = "SELECT parametros_adicionais FROM tbl_lista_basica WHERE lista_basica = $lbm;";
                                        $res_lb = pg_query($con,$sql_lb);

                                        if (pg_num_rows($res_lb) > 0) {

                                            $parametros_adicionais_lb = pg_fetch_result($res_lb, 0, parametros_adicionais);
                                            $parametros_adicionais_lb = json_decode($parametros_adicionais_lb,true);
                                            $parametros_adicionais_lb['item_revenda'] = "t";
                                            $parametros_adicionais_lb = json_encode($parametros_adicionais_lb);
                                            $param_adc_update = "parametros_adicionais = '$parametros_adicionais_lb',";
                                        }
                                    }
                                }else{

                                    $ins_pa = "";
                                    $ins_pa_c = "";
                                    if (!empty($lbm)){

                                        $sql_lb = "SELECT parametros_adicionais FROM tbl_lista_basica WHERE lista_basica = {$lbm};";
                                        $res_lb = pg_query($con,$sql_lb);

                                        if (pg_num_rows($res_lb) > 0) {

                                            $parametros_adicionais_lb = pg_fetch_result($res_lb, 0, parametros_adicionais);
                                            $parametros_adicionais_lb = json_decode($parametros_adicionais_lb,true);
                                            if (!empty($parametros_adicionais_lb['item_revenda'])) {
                                                $parametros_adicionais_lb['item_revenda'] = "f";
                                            }
                                            $parametros_adicionais_lb = json_encode($parametros_adicionais_lb);
                                            $param_adc_update = "parametros_adicionais = '$parametros_adicionais_lb',";
                                        }
                                    }
                                }
                            }
                        }
                    }

                    $insert = 0;
                    if (strlen ($msg_erro) == 0) {
                        if(empty($lbm)){
                            $insert = 1;
                            if ($login_fabrica == 14) {
                                $sql = "INSERT INTO tbl_lista_basica (
                                            fabrica       ,
                                            produto       ,
                                            peca          ,
                                            qtde          ,
                                            posicao       ,
                                            ordem         ,
                                            serie_inicial ,
                                            serie_final   ,
                                            type          ,
                                            ativo         ,
                                            admin         ,
                                            data_alteracao
                                        ) VALUES (
                                            $login_fabrica  ,
                                            $produto        ,
                                            $peca           ,
                                            $aux_qtde       ,
                                            $posicao      ,
                                            $ordem          ,
                                            '$serie_inicial',
                                            '$serie_final'  ,
                                            $type         ,
                                            '$ativo'        ,
                                            $login_admin    ,
                                            current_timestamp
                                ) RETURNING lista_basica;";
                            }else{
                                $in_camp_dataFB  = "";
                                $in_val_dataFB   = "";
                                if ($login_fabrica == 15 && strlen($data_fabricacao) > 0) {
                                    $in_camp_dataFB  = " data_ativa,";
                                    $in_val_dataFB   = " '$data_fabricacao', ";
                                }
                                
                                /*if ($login_fabrica == 175) {
                                    $colunaOrdemProducao = ", ordem_producao";
                                    $valorOrdemProducao  = ", '{$ordem}'";
                                    $ordem = "null";
                                }*/

                                $sql = "INSERT INTO tbl_lista_basica (
                                            fabrica       ,
                                            produto       ,
                                            peca          ,
                                            peca_pai      ,
                                            {$in_camp_dataFB}
                                            qtde          ,";
                                            if ($login_fabrica == 1) {
                                                $sql .= " garantia_peca, ";
                                            } else {
                                                $sql .= " posicao, ";
                                            }
                                            $sql .= "ordem         ,
                                            serie_inicial ,
                                            serie_final   ,
                                            type          ,
                                            ativo         ,
                                            admin         ,
                                            $ins_pa
                                            ";
                                            if ($login_fabrica == 15) {//HD 335675
                                                $sql .= " somente_kit, ";
                                            } 
                                            if ($login_fabrica == 195) {
                                                $sql .= " parametros_adicionais, ";
                                            }
                                            $sql .= "data_alteracao $colunaOrdemProducao
                                        ) VALUES (
                                            $login_fabrica  ,
                                            $produto        ,
                                            $peca           ,
                                            $peca_pai       ,
                                            {$in_val_dataFB}
                                            $aux_qtde       ,";
                                            if ($login_fabrica == 1) {
                                                $sql .= " $aux_desgaste, ";
                                            } else {
                                                $sql .= " $posicao, ";
                                            }
                                            $sql .= "$ordem          ,
                                            '$serie_inicial',
                                            '$serie_final'  ,
                                            $type         ,
                                            '$ativo'        ,
                                            $login_admin    ,
                                            $ins_pa_c
                                            ";
                                            if ($login_fabrica == 15) {
                                                $sql .= " '$somente_kit', ";
                                            }
                                            if ($login_fabrica == 195) {
                                                $sql .= " '$xxparametros_adicionais', ";
                                            }
                                            $sql .= "current_timestamp $valorOrdemProducao
                                        ) RETURNING lista_basica;";
                            }
                            $res = pg_query ($con,$sql);
                            $msg_erro .= pg_errormessage($con);

                            if(empty($msg_erro)){
                                $lbm = pg_fetch_result($res, 0, 0);
                            }

                            if ($login_fabrica == 80 AND empty($msg_erro)) {
                                $ativa_peca = pg_query($con, "UPDATE tbl_peca SET ativo = TRUE WHERE peca = $peca AND fabrica = $login_fabrica");
                            }
                        } else {
                            if ($login_fabrica == 1 && !empty($desgaste)) {
                                $auxiliar = "garantia_peca = $desgaste, ";
                            } else {
                                $auxiliar = "";
                            }
                            $up_dataFB = "";
                            if ($login_fabrica == 15 && strlen($data_fabricacao) > 0) {
                                $up_dataFB = "data_ativa='$data_fabricacao',";
                            }
                            
                            /*if ($login_fabrica == 175) {
                                $colunaOrdemProducao = ", ordem_producao = '{$ordem}'";
                                $ordem = "null";
                            }*/

                            if (in_array($login_fabrica, array(195))){
                                $sql_lb = "SELECT parametros_adicionais FROM tbl_lista_basica WHERE lista_basica = {$lbm};";
                                $res_lb = pg_query($con,$sql_lb);

                                if (pg_num_rows($res_lb) > 0) {

                                    $parametros_adicionais_lb = pg_fetch_result($res_lb, 0, parametros_adicionais);
                                    $parametros_adicionais_lb = json_decode($parametros_adicionais_lb,true);
                                    $parametros_adicionais_lb["data_de"] = $data_de;
                                    $parametros_adicionais_lb["data_ate"] = $data_ate;
                                    $parametros_adicionais_lb = json_encode($parametros_adicionais_lb);
                                    $param_adc_update = "parametros_adicionais = '$parametros_adicionais_lb',";
                                }

                            }
                            
                            $sql = "UPDATE tbl_lista_basica SET
                                        peca            = $peca,
                                        ordem           = $ordem,
                                        posicao         = $posicao,
                                        qtde            = $qtde,
                                        ativo           = '$ativo',
                                        type            = $type,
                                        serie_inicial   = '$serie_inicial',
                                        serie_final     = '$serie_final',
                                        somente_kit     = '$somente_kit',
                                        admin           = '$login_admin',
                                        $up_dataFB
                                        $auxiliar
                                        $param_adc_update
                                        data_alteracao  = current_timestamp
                                        $colunaOrdemProducao
                                    WHERE lista_basica  = $lbm";
                            $res = pg_query($con,$sql);
                            $msg_erro = pg_last_error($con);
                        }
                    }
                }
            }
        }
    }

    if (in_array($login_fabrica,array(158)) && empty($msg_erro)) {

        $sql = "SELECT referencia,tbl_produto.descricao,codigo_familia FROM tbl_produto JOIN tbl_familia ON(tbl_produto.familia = tbl_familia.familia) WHERE produto = {$produto} AND fabrica_i = {$login_fabrica};";
        $res = pg_query ($con,$sql);
        $produto_ref = pg_fetch_result ($res,0,referencia);
        $produto_descr = pg_fetch_result ($res,0,descricao);
        $codigo_familia = pg_fetch_result ($res,0,codigo_familia);

        $sql = "SELECT referencia,descricao FROM tbl_peca WHERE peca = $peca AND fabrica = $login_fabrica";
        $res = pg_query ($con,$sql);
        $peca_ref = pg_fetch_result ($res,0,referencia);
        $peca_descr = pg_fetch_result ($res,0,descricao);

        $ch2 = curl_init();
        curl_setopt($ch2, CURLOPT_URL, "http://telecontrol.eprodutiva.com.br/api/recurso/equipamento/codigo/{$produto_ref}");
        curl_setopt($ch2, CURLOPT_HEADER, FALSE);
        curl_setopt($ch2, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch2, CURLOPT_HTTPHEADER, array(
            "Content-Type: application/json",
            "Authorizationv2: $chave_persys"
        ));
        $equipamento = curl_exec($ch2);
        $equipamento = json_decode($equipamento, true);
        curl_close($ch2);
        if (isset($equipamento['error']['message']) && $equipamento['error']['message'] == "equipment not found") { /* CADASTRA NOVO EQUIPAMENTO */
            $row['codigo'] = $produto_ref;
            $row['equipamento'] = $produto_descr;
            $row['medida'] = array("id" => '301');
            $row['statusModel']  = '1';
            $json = json_encode($row);

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_POST, TRUE);
            curl_setopt($ch, CURLOPT_HEADER, FALSE);
            curl_setopt($ch, CURLOPT_URL, 'http://telecontrol.eprodutiva.com.br/api/recurso/equipamento');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                "Content-Type: application/json",
                "Authorizationv2: $chave_persys"
            ));

            $result = curl_exec($ch);
            curl_close($ch);
            /* ADICIONANDO CATEGORIA */
            $campos['categoria'] = array('codigo' => trim($codigo_familia));
            $json = json_encode($campos);

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, "http://telecontrol.eprodutiva.com.br/api/recurso/equipamento/codigo/{$produto_ref}/categorias");
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
            curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                "Content-Type: application/json",
                "Authorizationv2: $chave_persys"
            ));
            $result = curl_exec($ch);
        }

        $ch = curl_init();
        curl_setopt_array($ch, array(
            CURLOPT_URL => "http://telecontrol.eprodutiva.com.br/api/recurso/material/codigo/".$peca_ref,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_HTTPHEADER => array(
                "authorizationv2: {$chave_persys}"
            )
        ));

        $response = curl_exec($ch);
        curl_close($ch);
        $response = json_decode($response, true);
        if (isset($response['error']['message']) && $response['error']['message'] == "material not found") { /* ADICIONA O MATERIAL */
            $campos['codigo'] = $peca_ref;
            $campos['medida'] = array("id" => "301");
            $campos['material'] = utf8_encode($peca_descr);
            $json = json_encode($campos);
            $ch = curl_init();
            curl_setopt_array($ch, array(
                CURLOPT_URL => "http://telecontrol.eprodutiva.com.br/api/recurso/material",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => TRUE,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_POSTFIELDS => $json,
                CURLOPT_HTTPHEADER => array(
                    "authorizationv2: {$chave_persys}",
                    "Content-Type: application/json"
                )
            ));
            $response = curl_exec($ch);
            curl_close($ch);
            $response = json_decode($response, true);
        }
        /* VERIFICA SE JÁ POSSUI O MATERIAL CADASTRADO */
        $ch = curl_init();
        curl_setopt_array($ch, array(
            CURLOPT_URL => "http://telecontrol.eprodutiva.com.br/api/recurso/equipamento/codigo/".$produto_ref."/material",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => array(
                "authorizationv2: {$chave_persys}"
            )
        ));

        $result = curl_exec($ch);
        curl_close($ch);
        $result = json_decode($result, true);
        $inserido = 0;
        foreach ($result['data'] as $key => $value) {
            if ($value['material']['id'] == $response['id']) {
                $inserido = 1;
                break;
            }
        }

        if ($inserido == 1) {
            $data = array(
                "statusModel" => "1",
                "maxQuantity" => "$qtde",
                "minQuantity" => "1"
            );
            $operacao = "PUT";
            $url = "/codigo/".$peca_ref;
        }else{
            $data = array(
                "material" => array(
                    "id" => $response['id']
                ),
                "maxQuantity" => "$qtde",
                "minQuantity" => "1"
            );
            $operacao = "POST";
            $url = "";
        }

        $json = json_encode($data);

        $ch = curl_init();
        curl_setopt_array($ch, array(
            CURLOPT_URL => "http://telecontrol.eprodutiva.com.br/api/recurso/equipamento/codigo/".$produto_ref."/material".$url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => $operacao,
            CURLOPT_POSTFIELDS => $json,
            CURLOPT_HTTPHEADER => array(
                "authorizationv2: {$chave_persys}",
                "Content-Type: application/json"
            )
        ));

        $response = curl_exec($ch);
        curl_close($ch);
        if (!$response) {
            $res = pg_query ($con,"ROLLBACK TRANSACTION");
            $msg_erro = "Erro ao tentar atualizar a API mobile";
        }else{
            $response = json_decode($response, true);
            if (count($response['error']) !== 0) {
                $res = pg_query ($con,"ROLLBACK TRANSACTION");
                $msg_erro = "Erro ao tentar atualizar a API mobile";
            }
        }
    }

    if($tinhaListaBasica){          
        $msg_email .= "Itens Alterados\n";
        $msg_email .= $de;
        $msg_email .= $para;        
    }else{      
        $msg_email .= "Itens Novos\n";
        $msg_email .= $paraNovos;
    }

    if (strlen ($msg_erro) == 0) {
        $res = pg_query ($con,"COMMIT TRANSACTION");

        $auditorLog->retornaDadosTabela()
                   ->enviarLog("update", "tbl_lista_basica", $login_fabrica."*".$produto);

        #-------------------- Envia EMAIL ------------------
        if ($login_fabrica != 1) {
            $sql = "SELECT email_gerente FROM tbl_fabrica WHERE fabrica = $login_fabrica AND LENGTH (TRIM (email_gerente)) > 0";
            $res = pg_query ($con,$sql);

            if (pg_num_rows ($res) > 0) {
                $email_gerente = pg_fetch_result ($res,0,0);

                $sql = "SELECT referencia, descricao FROM tbl_produto WHERE produto = $produto";
                $res = pg_query ($con,$sql);
                $produto_referencia = pg_fetch_result ($res,0,referencia);
                $produto_descricao  = pg_fetch_result ($res,0,descricao);

                $msgEmail = "A lista básica do produto $produto_referencia - $produto_descricao acaba de ser alterada no site TELECONTROL \n\n  $msg_email" ;

                $email_ok = mail ("$email_gerente" , utf8_encode("Alteração de Lista Básica") , $msgEmail  , "From: Telecontrol <helpdesk@telecontrol.com.br>" , "-f helpdesk@telecontrol.com.br" );

                //utf8_encode("")
            }
        }
        #---------------------------------------------------

        echo "ok|$lbm";
    }else{
        $res = pg_query ($con,"ROLLBACK TRANSACTION");
        echo "no|$msg_erro";
    }

    exit;
}

if($_POST["ajax_subitem"] == true){
    $produto      = $_POST["produto"];
    $peca_filha   = $_POST["referencia"];
    $qtde         = $_POST["qtde"];
    $peca_pai     = $_POST["peca_pai"];
    $lista_basica = $_POST["lbm"];

    $sql = "SELECT tbl_lista_basica.lista_basica, 
            tbl_lista_basica.parametros_adicionais 
        FROM tbl_lista_basica 
            JOIN tbl_peca ON tbl_peca.peca          = tbl_lista_basica.peca
            JOIN tbl_produto ON tbl_produto.produto = tbl_lista_basica.produto
        WHERE tbl_lista_basica.fabrica = {$login_fabrica}
            AND tbl_lista_basica.lista_basica = {$lista_basica};";
    $res = pg_query($con, $sql);

    if(pg_num_rows($res) > 0){
        $parametros_adicionais = json_decode(pg_fetch_result($res, 0, 'parametros_adicionais'));
        strlen($parametros_adicionais) > 0 ? $parametros_adicionais->subitem = true : $parametros_adicionais = array("subitem" => true);
    }

    $sql = "SELECT peca, referencia
        FROM tbl_peca 
        WHERE referencia IN ('{$peca_pai}', '{$peca_filha}') 
            AND fabrica = {$login_fabrica};";
    $res_peca = pg_query($con, $sql);

    $contador_res_peca = pg_num_rows($res_peca);
    
    if(pg_num_rows($res_peca) > 0){
        for($i = 0; $i < $contador_res_peca; $i++){
            $peca       = pg_fetch_result($res_peca, $i, 'peca');
            $referencia = pg_fetch_result($res_peca, $i, 'referencia');

            if($peca_pai == $referencia){
                $peca_pai = $peca;
            } else if($peca_filha == $referencia){
                $peca_filha = $peca;
            }
        }

        pg_query($con, "BEGIN TRANSACTION");

        $sql = "INSERT INTO tbl_peca_container (
                fabrica,
                peca_mae,
                peca_filha,
                produto,
                qtde
            )VALUES(
                {$login_fabrica},
                {$peca_pai},
                {$peca_filha},
                {$produto},
                {$qtde}
            ) RETURNING peca_container;";
        $res_insert = pg_query($con, $sql);

        if(pg_last_error($con)){
            $msg['success'] = false;
            $msg['msg'][]   = pg_last_error($con);
            pg_query($con, "ROLLBACK");
        } else {
            $peca_container = pg_fetch_result($res_insert, 0, 'peca_container');
            $peca = pg_fetch_result($res_peca, 0, 'peca');

            $sql = "UPDATE tbl_lista_basica SET 
                    parametros_adicionais = '". json_encode($parametros_adicionais) ."',
                    data_alteracao        = CURRENT_TIMESTAMP
                WHERE tbl_lista_basica.fabrica        = {$login_fabrica}
                    AND tbl_lista_basica.lista_basica = {$lista_basica};";
            pg_query($con,$sql);

            if(pg_last_error($con)){
                $msg['success'] = false;
                $msg['msg'][]   = pg_last_error($con);
                pg_query($con, "ROLLBACK");
            } else {
                $msg['success']        = true;
                $msg['peca_container'] = $peca_container;
                $msg['peca_filha']     = $peca_filha;
                pg_query($con, "COMMIT");
            }
        }
    }
    echo json_encode($msg);
    exit;
}

if($_POST["ajax_subitem_remover"] == true){
    $peca_container = $_POST["peca_container"];
    $peca_filha     = $_POST["peca_filha"];

    if($peca_container != ""){
        pg_query($con, "BEGIN");

        $sql = "DELETE FROM tbl_peca_container 
            WHERE tbl_peca_container.peca_container = {$peca_container}
                AND tbl_peca_container.fabrica = {$login_fabrica}
                AND tbl_peca_container.peca_filha = {$peca_filha}";
        pg_query($con,$sql);

        if(pg_last_error($con)){
            $msg['success'] = false;
            $msg['msg'][]   = pg_last_error($con);
            pg_query($con, "ROLLBACK");
        } else {
            pg_query($con, "COMMIT");
            $msg['success'] = true;
        }
    }
    echo json_encode($msg);
    exit;
}

if($_GET['ajax_remove']){
    $lbm      = $_GET['lbm'];
    $produto  = $_GET['produto'];
    $msg_erro = "";

    if (in_array($login_fabrica,array(158))) {
        $sql3 = "
            SELECT
                tpr.referencia AS ref_prod,
                tpe.referencia AS ref_peca
            FROM tbl_lista_basica tlb
            JOIN tbl_peca tpe ON(tlb.peca = tpe.peca)
            JOIN tbl_produto tpr ON(tlb.produto = tpr.produto)
            WHERE tlb.fabrica = {$login_fabrica}
            AND tlb.lista_basica = {$lbm};
        ";
        $res3 = pg_query($con,$sql3);
        $prod_peca_cod = pg_fetch_assoc($res3);
        $data = array("statusModel" => "0");
        $json = json_encode($data);
    }
    
    /*Inicia o AuditorLog Pedido */
    $auditorLog = new AuditorLog();    
    $auditorLog->retornaDadosTabela("tbl_lista_basica", array("produto"=>$produto, "fabrica"=>$login_fabrica) );

    $sqlDadosPeca = "
        SELECT
            tbl_peca.peca,
            tbl_peca.descricao,
            tbl_peca.referencia,
            tbl_produto.referencia AS referencia_produto,
            tbl_produto.descricao AS descricao_produto
        FROM tbl_lista_basica
        JOIN tbl_peca ON tbl_peca.peca = tbl_lista_basica.peca AND tbl_peca.fabrica = {$login_fabrica}
        JOIN tbl_produto ON tbl_produto.produto = tbl_lista_basica.produto AND tbl_produto.fabrica_i = {$login_fabrica}
        WHERE tbl_lista_basica.lista_basica = {$lbm}
        AND tbl_lista_basica.fabrica = {$login_fabrica};
    ";
    $resDadosPeca = pg_query($con, $sqlDadosPeca);

    $sqlAdmin = "SELECT nome_completo FROM tbl_admin WHERE admin = {$login_admin} AND fabrica = {$login_fabrica};";
    $resAdmin = pg_query($con, $sqlAdmin);
    if (pg_num_rows($resAdmin) > 0) {
        $nome_completo = pg_fetch_result($resAdmin, 0, nome_completo);
    }

    $resS = pg_query($con,"BEGIN TRANSACTION");
    
    if (pg_num_rows($resDadosPeca) > 0) {
        $peca               = pg_fetch_result($resDadosPeca, 0, peca);
        $referencia         = pg_fetch_result($resDadosPeca, 0, referencia);
        $descricao          = pg_fetch_result($resDadosPeca, 0, descricao);
        $referencia_produto = pg_fetch_result($resDadosPeca, 0, referencia_produto);
        $descricao_produto  = pg_fetch_result($resDadosPeca, 0, descricao_produto);
        $msg_email          = "A peça $referencia - $descricao foi apagada da lista basica do produto $referencia_produto - $descricao_produto no site TELECONTROL \n Admin: $nome_completo em ". date("d/m/Y");

        if ($login_fabrica == 1) {
            $sql2 = "
                UPDATE tbl_peca
                SET garantia_diferenciada = NULL
                WHERE tbl_peca.peca IN (
                    SELECT 
                        tbl_lista_basica.peca
                    FROM tbl_lista_basica
                    WHERE tbl_lista_basica.fabrica = {$login_fabrica}
                    AND tbl_lista_basica.lista_basica = {$lbm}
                );
            ";
            $res2 = pg_query($con,$sql2);
        }

        if(in_array($login_fabrica, array(158))){
            $sql = "DELETE FROM tbl_peca_container 
                WHERE tbl_peca_container.fabrica    = {$login_fabrica} 
                    AND tbl_peca_container.peca_mae = {$peca}
                    AND tbl_peca_container.produto  = {$produto}";
            $res      = pg_query($con, $sql);
            $msg_erro = pg_last_error($con);
        }

        if($msg_erro == ""){
            $sql = "
                DELETE FROM tbl_lista_basica
                WHERE tbl_lista_basica.fabrica = {$login_fabrica}
                AND tbl_lista_basica.lista_basica = {$lbm};
            ";
            $res = pg_query($con,$sql);
            $msg_erro = pg_last_error($con);
        }
    } else {
        if (in_array($login_fabrica, array(169,170))) {
            $sqlNumSerie = "
                SELECT
                    pc.referencia AS peca_referencia,
                    pc.descricao AS peca_descricao,
                    pd.referencia AS produto_referencia,
                    pd.descricao AS produto_descricao
                FROM tbl_numero_serie_peca nsp
                JOIN tbl_peca pc ON pc.peca = nsp.peca AND pc.fabrica = {$login_fabrica}
                JOIN tbl_numero_serie ns ON ns.numero_serie = nsp.numero_serie AND ns.fabrica = {$login_fabrica}
                JOIN tbl_produto pd ON pd.produto = ns.produto AND pd.fabrica_i = {$login_fabrica}
                WHERE nsp.fabrica = {$login_fabrica}
                AND nsp.numero_serie_peca = {$lbm};
            ";
            $resNumSerie = pg_query($con, $sqlNumSerie);
            $referencia = pg_fetch_result($resNumSerie, 0, peca_referencia);
            $descricao = pg_fetch_result($resNumSerie, 0, peca_descricao);
            $referencia_produto = pg_fetch_result($resNumSerie, 0, produto_referencia);
            $descricao_produto = pg_fetch_result($resNumSerie, 0, produto_descricao);
            $msg_email = "A peça $referencia - $descricao foi apagada da lista basica do produto $referencia_produto - $descricao_produto no site TELECONTROL \n Admin: $nome_completo em ". date("d/m/Y");
            $sql = "
                DELETE FROM tbl_numero_serie_peca
                WHERE fabrica = {$login_fabrica}
                AND numero_serie_peca = {$lbm};
            ";
            $res = pg_query($con,$sql);
            $msg_erro = pg_last_error($con);
        }
    }
    if (in_array($login_fabrica,array(158)) && strlen($msg_erro) == 0) {
        $ch = curl_init();
        curl_setopt_array($ch, array(
            CURLOPT_URL => "http://telecontrol.eprodutiva.com.br/api/recurso/equipamento/codigo/".$prod_peca_cod['ref_prod']."/material/codigo/".$prod_peca_cod['ref_peca'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "PUT",
            CURLOPT_POSTFIELDS => $json,
            CURLOPT_HTTPHEADER => array(
                "authorizationv2: {$chave_persys}",
                "Content-Type: application/json"
            )
        ));
        $response = curl_exec($ch);
        curl_close($ch);
        if (!$response) {
            $msg_erro = 'Erro ao tentar atualizar a API mobile!';
        }
    }
    if (strlen ($msg_erro) == 0) {
        ###CONCLUI OPERAÇÃO DE INCLUSÃO/EXLUSÃO/ALTERAÇÃO E SUBMETE
        $resS = pg_query($con,"COMMIT TRANSACTION");
        $auditorLog->retornaDadosTabela()
                   ->enviarLog("update", "tbl_lista_basica", $login_fabrica."*".$produto);
        #-------------------- Envia EMAIL ------------------
        if ($login_fabrica != 1) {
            $sql = "SELECT email_gerente FROM tbl_fabrica WHERE fabrica = $login_fabrica AND LENGTH (TRIM (email_gerente)) > 0";
            $res = pg_query ($con,$sql);
            if (pg_num_rows ($res) > 0) {
                $email_gerente = pg_fetch_result ($res,0,0);
                $sql = "SELECT referencia, descricao FROM tbl_produto WHERE produto = $produto";
                $res = pg_query ($con,$sql);
                $produto_referencia = pg_fetch_result ($res,0,'referencia');
                $produto_descricao  = pg_fetch_result ($res,0,'descricao');
                $email_ok = mail ("$email_gerente" , utf8_encode("Item apagado da Lista Básica") , utf8_encode("$msg_email ") , "From: Telecontrol <helpdesk@telecontrol.com.br>" , "-f helpdesk@telecontrol.com.br" );
            }
        }
        #---------------------------------------------------
        echo "ok";
    }else{      ###ABORTA OPERAÇÃO DE INCLUSÃO/EXLUSÃO/ALTERAÇÃO E RECARREGA CAMPOS
        $resS = pg_query ($con,"ROLLBACK TRANSACTION");
        echo $msg_erro;
    }
    exit;
}

$acao = trim ($_GET['acao']);

if ($acao == "excluir"){
    $produto = trim($_GET['produto']);
    $serie = trim($_GET['serie']);
    
    /*if ($login_fabrica == 175) {
        $ordem_producao = $_GET['ordem_producao'];
    }*/

    if (in_array($login_fabrica, array(169,170)) && !empty($serie)) {
        $sql = "SELECT numero_serie FROM tbl_numero_serie WHERE fabrica = {$login_fabrica} AND produto = {$produto} AND serie = '{$serie}';";
        $res = pg_query($con, $sql);
        
        $numero_serie = pg_fetch_result($res, 0, "numero_serie");
    }

    /*Inicia o AuditorLog Pedido */
    $auditorLog = new AuditorLog();
    if (in_array($login_fabrica, array(169,170)) && !empty($numero_serie)) {
        $auditorLog->retornaDadosTabela("tbl_numero_serie_peca", array("numero_serie"=>$numero_serie, "fabrica"=>$login_fabrica));
        $sql = "DELETE FROM tbl_numero_serie_peca WHERE fabrica = {$login_fabrica} AND numero_serie = {$numero_serie};";
    } else {
        $auditorLog->retornaDadosTabela("tbl_lista_basica", array("produto"=>$produto, "fabrica"=>$login_fabrica));
        
        /*if ($login_fabrica == 175) {
            $sql = "DELETE FROM tbl_lista_basica WHERE fabrica = {$login_fabrica} AND produto = {$produto} AND ordem_producao = '{$ordem_producao}'";
        } else {*/
            $sql = "DELETE FROM tbl_lista_basica WHERE fabrica = {$login_fabrica} AND produto = {$produto};";
        // }
    }
    
    $res = pg_query($con,$sql);

    #-------------------- Envia EMAIL ------------------
    $sql = "SELECT email_gerente FROM tbl_fabrica WHERE fabrica = $login_fabrica AND LENGTH (TRIM (email_gerente)) > 0";
    $res = pg_query($con,$sql);
    if (pg_num_rows($res) > 0) {
        $email_gerente = pg_fetch_result ($res,0,0);

        $sql = "SELECT referencia, descricao FROM tbl_produto WHERE produto = $produto";
        $res = pg_query ($con,$sql);
        $produto_referencia = pg_fetch_result ($res,0,referencia);
        $produto_descricao  = pg_fetch_result ($res,0,descricao);

        if (in_array($login_fabrica, array(169,170)) && !empty($numero_serie)) {
            $msgEmail = utf8_encode("Toda a lista básica do Número de Série {$serie} acaba de ser apagada no site TELECONTROL");
        } else {
            $msgEmail = utf8_encode("Toda a lista básica do produto {$produto_referencia} - {$produto_descricao} acaba de ser apagada no site TELECONTROL");
        }

        $email_ok = mail($email_gerente, utf8_encode("Lista Básica Apagada"), $msgEmail, "From: Telecontrol <helpdesk@telecontrol.com.br>", "-f helpdesk@telecontrol.com.br" );
    }
    #---------------------------------------------------

    if (in_array($login_fabrica, array(169,170)) && !empty($numero_serie)) {
        $auditorLog->retornaDadosTabela()->enviarLog("update", "tbl_numero_serie_peca", $login_fabrica."*".$numero_serie);
    } else {
        $auditorLog->retornaDadosTabela()->enviarLog("update", "tbl_lista_basica", $login_fabrica."*".$produto);
    }

    header ("Location: $PHP_SELF?msg=exclui");
    exit;
}

if ($_POST["btn_acao"] == "pesquisar" OR !empty($produto)) {

    if (strlen($produto) > 0) {
        $sql = "SELECT  tbl_produto.referencia,
                        tbl_produto.descricao ,
                        tbl_produto.referencia_fabrica as referencia_fabrica_produto,
                        tbl_produto.voltagem
                FROM    tbl_produto
                WHERE   tbl_produto.produto = $produto
                AND     tbl_produto.fabrica_i = $login_fabrica";
        $res = pg_query ($con,$sql);

        if (pg_num_rows($res) > 0) {
            $referencia_produto = pg_fetch_result($res,0,'referencia');
            $descricao_produto  = pg_fetch_result($res,0,'descricao');
            $referencia_fabrica_produto = pg_fetch_result($res,0,'referencia_fabrica_produto');

            if ($login_fabrica == 1){
                $voltagem  = pg_fetch_result($res,0,'voltagem');
                $descricao = $descricao." ".$voltagem;
            }
        }
        /*if ($login_fabrica == 175){
            if (empty($ordem_producao)){
                $ordem_producao     = $_POST['ordem_producao'];
            }
        }*/
    }else{
        $produto            = $_POST['produto'];
        $produto_referencia = $_POST['produto_referencia'];
        $produto_descricao  = $_POST['produto_descricao'];
        $produto_serie      = $_POST['produto_serie'];

        if(empty($produto_referencia)){
            $msg_erro['msg'][] = traduz("Preencha os campos obrigatórios");
            $msg_erro["campos"][] = "produto";
        }

        /*if ($login_fabrica == 175){
            $ordem_producao     = $_POST['ordem_producao'];
        }*/

        if(count($msg_erro) == 0){
            $sql = "
                SELECT
                    tbl_produto.produto,
                    tbl_produto.referencia,
                    tbl_produto.referencia_fabrica as referencia_fabrica_produto,
                    tbl_produto.descricao,
                    tbl_produto.voltagem
                FROM tbl_produto
                WHERE referencia = '{$produto_referencia}'
                AND   fabrica_i  = {$login_fabrica};
            ";
            $res = pg_query($con,$sql);

            if(pg_num_rows($res) == 0){
                $msg_erro['msg'][] = traduz("Referencia % não encontrada", null, null, [$produto_referencia]);
                $msg_erro["campos"][] = "produto";
            }else{
                $produto            = pg_fetch_result($res,0,'produto');
                $referencia_produto = pg_fetch_result($res,0,'referencia');
                $referencia_fabrica_produto = pg_fetch_result($res,0,'referencia_fabrica_produto');

                $descricao_produto  = pg_fetch_result($res,0,'descricao');

                if ($login_fabrica == 1){
                    $voltagem  = pg_fetch_result($res,0,'voltagem');
                    $descricao = $descricao." ".$voltagem;
                }
            }
        }
    }
}

if ($_POST["btn_acao"] == "importar") {
    $arquivo = isset($_FILES["arquivo"]) ? $_FILES["arquivo"] : FALSE;
    $produto = $_POST['produto_excel'];

    if (count ($msg_erro['msg']) == 0) {
        $config["tamanho"] = 2048000; // Tamanho máximo do arquivo (em bytes)
        if (strlen($arquivo["tmp_name"]) > 0 && $arquivo["tmp_name"] != "none"){
            preg_match("/\.(xls){1}$/i", $arquivo["name"], $ext);

            if ($ext[1] <>'xls'){
                $msg_erro['msg'][] = "Arquivo em formato inválido!";
            } else { // Verifica tamanho do arquivo
                if ($arquivo["size"] > $config["tamanho"])
                    $msg_erro['msg'][] = "Arquivo tem tamanho muito grande! Deve ser de no máximo 2MB. Envie outro arquivo.";
            }
            if (count($msg_erro['msg']) == 0) {
                // Pega extensão do arquivo
                preg_match("/\.(xls){1}$/i", $arquivo["name"], $ext);
                $aux_extensao = "'".$ext[1]."'";

                $nome_sem_espaco = implode("", explode(" ",$arquivo["name"]));

                $nome_anexo = __DIR__ . "/xls/produto.xls";

                /*Inicia o AuditorLog Pedido */
                $auditorLog = new AuditorLog();    
                $auditorLog->retornaDadosTabela("tbl_lista_basica", array("produto"=>$produto, "fabrica"=>$login_fabrica) );

                if (count($msg_erro['msg']) == 0) {
                    if (copy($arquivo["tmp_name"], $nome_anexo)) {
                        require_once 'xls_reader.php';
                        $data = new Spreadsheet_Excel_Reader();
                        $data->setOutputEncoding('CP1251');
                        $data->read('xls/produto.xls');
                        $res = pg_query ($con,"BEGIN TRANSACTION");

                        $sql = "DELETE FROM tbl_lista_basica
                                WHERE  tbl_lista_basica.produto = $produto
                                AND    tbl_lista_basica.fabrica = $login_fabrica";
                        $res = pg_query ($con,$sql);
                        if(pg_last_error($con)){
                            $msg_erro['msg'][] = pg_last_error($con);
                        }
                        if($login_fabrica == 1) {
                            for ($i = 1; $i <= $data->sheets[0]['numRows']; $i++) {
                                    $ordem  = "";
                                    $posicao= "";
                                    $peca   = "";
                                    $type   = "";
                                    $qtde   = "";
                                for ($j = 1; $j <= $data->sheets[0]['numCols']; $j++) {
                                    if($data->sheets[0]['numCols'] <> 6) {
                                        $msg_erro['msg'][] = "Por favor, verificar o conteúdo de Excel, está faltando algumas colunas";
                                    }
                                    switch($j) {
                                        case 1: $ordem = $data->sheets[0]['cells'][$i][$j]; break;
                                        case 2: $posicao = $data->sheets[0]['cells'][$i][$j];break;
                                        case 3:
                                            $referencia_peca = str_replace (".","",$data->sheets[0]['cells'][$i][$j]);
                                            $referencia_peca = str_replace ("-","",$referencia_peca);
                                            $referencia_peca = str_replace ("/","",$referencia_peca);
                                            $referencia_peca = str_replace (" ","",$referencia_peca);

                                            $sql = " SELECT peca
                                                    FROM tbl_peca
                                                    WHERE fabrica = $login_fabrica
                                                    AND   (upper(tbl_peca.referencia_pesquisa) =  upper('$referencia_peca') or upper(tbl_peca.referencia) = upper('$referencia_peca')) ";
                                            $res = @pg_query($con,$sql);
                                            if(@pg_num_rows($res) > 0){
                                                $peca = @pg_fetch_result($res,0,0);
                                            }else{
                                                $msg_erro['msg'][] = "Peça ".$data->sheets[0]['cells'][$i][$j]." não encontrada no sistema<br>";
                                            }
                                            break;
                                        case 4: $descricao = $data->sheets[0]['cells'][$i][$j];break;
                                        case 5: $type = !empty($data->sheets[0]['cells'][$i][$j]) ? $data->sheets[0]['cells'][$i][$j]:null; break;
                                        case 6: $qtde = $data->sheets[0]['cells'][$i][$j];break;
                                    }
                                }

                                $ordem = (empty($ordem)) ? 'null' : $ordem;
                                if(count($msg_erro['msg']) == 0 and strlen($peca) > 0 and strlen($qtde) > 0) {
                                    $sql = "INSERT INTO tbl_lista_basica (
                                                fabrica        ,
                                                produto        ,
                                                peca           ,
                                                qtde           ,
                                                ordem          ,
                                                type           ,
                                                admin          ,
                                                data_alteracao ,
                                                ativo
                                            ) VALUES (
                                                $login_fabrica,
                                                $produto      ,
                                                $peca         ,
                                                $qtde         ,
                                                $ordem        ,
                                                '$type'       ,
                                                $login_admin  ,
                                                current_timestamp,
                                                't'
                                    );";
                                    $res = @pg_query ($con,$sql);
                                    if(pg_last_error($con)){
                                        $msg_erro['msg'][] = pg_last_error($con);
                                    }
                                }
                            }
                        }else{
                            for ($i = 1; $i <= $data->sheets[0]['numRows']; $i++) {
                                    $peca   = "";
                                    $qtde   = "";
                                for ($j = 1; $j <= $data->sheets[0]['numCols']; $j++) {
                                    if($data->sheets[0]['numCols'] <> 3) {
                                        $msg_erro['msg'][] = "Por favor, verificar o conteúdo de Excel, está faltando algumas colunas";
                                    }
                                    switch($j) {
                                        case 2:
                                            $referencia_peca = str_replace (".","",$data->sheets[0]['cells'][$i][$j]);
                                            $referencia_peca = str_replace ("-","",$referencia_peca);
                                            $referencia = str_replace ("/","",$referencia_peca);
                                            $referencia_peca = str_replace (" ","",$referencia_peca);
                                            $referencia_peca = trim($referencia_peca);
                                            $sql = " SELECT peca
                                                    FROM tbl_peca
                                                    WHERE fabrica = $login_fabrica
                                                    AND   (tbl_peca.referencia_pesquisa =  '$referencia_peca' or tbl_peca.referencia ='$referencia_peca'); ";
                                            $res = @pg_query($con,$sql);
                                            if(pg_last_error($con)){
                                                $msg_erro['msg'][] = pg_last_error($con);
                                            }
                                            if(@pg_num_rows($res) > 0){
                                                $peca = @pg_fetch_result($res,0,0);
                                            }else{
                                                $msg_erro['msg'][] = "Peça ".$data->sheets[0]['cells'][$i][$j]." não encontrada no sistema";
                                            }
                                            break;
                                        case 3: $qtde = $data->sheets[0]['cells'][$i][$j];
                                        $qtde = str_replace(",",".",$qtde);;
                                        break;
                                    }
                                }
                                if(count($msg_erro['msg']) == 0 and strlen($peca) > 0 and strlen($qtde) > 0) {
                                    $sql = "INSERT INTO tbl_lista_basica (
                                                fabrica        ,
                                                produto        ,
                                                peca           ,
                                                qtde           ,
                                                admin          ,
                                                data_alteracao ,
                                                ativo
                                            ) VALUES (
                                                $login_fabrica,
                                                $produto      ,
                                                $peca         ,
                                                $qtde         ,
                                                $login_admin  ,
                                                current_timestamp,
                                                't'
                                    );";
                                    $res = @pg_query ($con,$sql);
                                    if(pg_last_error($con)){
                                        $msg_erro['msg'][] = pg_last_error($con);
                                    }
                                }
                            }
                        }

                        if(count($msg_erro['msg']) == 0) {
                            $res = pg_query ($con,"COMMIT TRANSACTION");

                            $auditorLog->retornaDadosTabela()
                                       ->enviarLog("update", "tbl_lista_basica", $login_fabrica."*".$produto);

                            if ($login_fabrica != 1) {
                                $sql = "SELECT email_gerente FROM tbl_fabrica WHERE fabrica = $login_fabrica AND LENGTH (TRIM (email_gerente)) > 0";
                                $res = pg_query ($con,$sql);
                                if (pg_num_rows ($res) > 0) {
                                    $email_gerente = pg_fetch_result ($res,0,0);

                                    $sql = "SELECT referencia, descricao FROM tbl_produto WHERE produto = $produto";
                                    $res = pg_query ($con,$sql);
                                    $produto_referencia = pg_fetch_result ($res,0,referencia);
                                    $produto_descricao  = pg_fetch_result ($res,0,descricao);

                                    $email_ok = mail ("$email_gerente" , utf8_encode("Alteração de Lista Básica") , utf8_encode("A lista básica do produto $produto_referencia - $produto_descricao acaba de ser alterada no site TELECONTROL") , "From: Telecontrol <helpdesk@telecontrol.com.br>" , "-f helpdesk@telecontrol.com.br" );
                                }
                                #---------------------------------------------------
                            }
                            header ("Location: $PHP_SELF?produto=$produto&msg=importar");
                                exit;
                        }else{
                            $res = pg_query ($con,"ROLLBACK TRANSACTION");
                        }
                    }else{
                        $msg_erro['msg'][] = "Arquivo não foi enviado!!! Tente outra vez";
                    }
                }
            }
        }
    }
}

if ($_POST["btn_acao"] == "importar_txt") {
    try {
        $arquivo    = isset($_FILES["arquivo"]) ? $_FILES["arquivo"] : FALSE;
        $produto    = $_POST['produto'];

        $tmpPathInfo = pathinfo($arquivo['tmp_name']);
        $pathInfo = pathinfo($arquivo['name']);

        if (!in_array($pathInfo["extension"], array('csv', "txt" ))) {
            throw new Exception("Extensão do arquivo deve ser CSV ou TXT");
        }

        $maxFileSize = 2048000;

        if ($arquivo["size"] > $maxFileSize) {
            throw new Exception("Arquivo maior do que o permitido (2MB)");
        }

        $path = $tmpPathInfo["dirname"]."/".$tmpPathInfo["basename"];

        $hashTableFields = array("tbl_lista_basica" =>  array(
            "ordem",
            "posicao",
            "peca",
            "descricao",
            "type",
            "qtde"
            )
        );

        $fileColumns = array("ordem", "posicao", "peca", "descricao", "type", "qtde");
        
        /*HD-4074490*/
        if($login_fabrica == 1){
            $hashTableFields["tbl_lista_basica"][] = "garantia_peca";
            $fileColumns[] = "garantia_peca";
            unset($hashTableFields["tbl_lista_basica"][1], $fileColumns[1]);
        }

        if ($login_fabrica == 158) {
            array_push($fileColumns,"acao");
            $aux = array_slice($fileColumns,2);
            $aux2 = array_slice($hashTableFields["tbl_lista_basica"],2);

            array_splice($aux2,2,-1);
            array_splice($aux,2,-2);

            $fileColumns = $aux;
            array_unshift($fileColumns,"produto");
            $hashTableFields["tbl_lista_basica"] = $aux2;
        }

        $separator = ";";

        $importaArquivo = new ImportaArquivo($path, $separator, $fileColumns, $hashTableFields);

        $importaArquivo->readFile();

        $rows = $importaArquivo->getDataRows();
      

        if ($login_fabrica == 1) {

            $ordens = array();
            $linha = 1;
            foreach ($rows as $row => $value) {
                $aux_ordem = $value['tbl_lista_basica']['ordem'];
                
                $aux_sql = "SELECT peca FROM tbl_lista_basica WHERE fabrica = $login_fabrica AND produto = $produto AND ordem = '$aux_ordem'";
                $aux_res = pg_query($con, $aux_sql);

                $contador_res_aux = pg_num_rows($aux_res);
                if ($contador_res_aux > 0) {
                    for ($x = 0; $x < $contador_res_aux; $x++) {
                        $aux_row = pg_fetch_result($aux_res, $x, 'peca');
                        $aux_sql = "SELECT referencia || ' - ' || descricao as peca FROM tbl_peca WHERE peca = $aux_row LIMIT 1";
                        $aux_res2 = pg_query($con ,$aux_sql);
                        $aux_pec  = pg_fetch_result($aux_res2, 0, 'peca');
                        $aux_erro[] = "AVISO DE ORDEM DUPLICADA!<br>Você tentou importar a ordem \"$aux_ordem\" mas ela já está vinculada à peça \"$aux_pec\".";
                    }
                }
                if (in_array($aux_ordem, $ordens)) {
                    $aux_erro[] = "AVISO DE ORDEM DUPLICADA!<br>A ordem \"$aux_ordem\" informada na linha \"$linha\" está duplicada no arquivo de importação.";
                } else {
                    $ordens[] = $aux_ordem;
                }
            }

            if (count($aux_erro) > 0) {
                $aux_erro = implode("<br><br>", $aux_erro);
                throw new Exception($aux_erro);
            }

            //verifica se produto é Dwalt
            $marca = 0;
            $sql = "SELECT marca FROM tbl_produto WHERE fabrica_i = {$login_fabrica} AND produto = {$produto};";
            //die(nl2br($sql));
            $res = pg_query($con,$sql);
            $marca = pg_fetch_result($res, 0, "marca");
        }

        if ($login_fabrica ==1 AND $marca == 237) {
            $auditorLog = new AuditorLog();    
            $auditorLog->retornaDadosTabela("tbl_lista_basica", array("produto"=>$produto, "fabrica"=>$login_fabrica) );

            pg_query($con, "BEGIN TRANSACTION");

            $sql = "DELETE FROM tbl_lista_basica
                    WHERE  tbl_lista_basica.produto = $produto
                    AND    tbl_lista_basica.fabrica = $login_fabrica";
            //die(nl2br($sql));
            $res = pg_query ($con,$sql);
            //echo count($rows);
            $desgaste_dw = false;
            $contador_linha = count($rows); 
            for($i = 0; $i < $contador_linha; $i++){

                $table =  array_keys($rows[$i]);
                $table =  $table[0];
                $currentRow = $rows[$i][$table];

                $table = $table[0];
                // print_r($currentRow); exit;

                if(strlen(trim($currentRow["peca"])) == 0){
                    continue;
                }

                $currentRow["referencia"] = $currentRow["peca"];
                $currentRow["peca"] = verificaPeca(trim($currentRow["peca"]));

                if (strlen(trim($currentRow['peca'])) > 0) {

                    $sql = "INSERT INTO tbl_lista_basica (
                                    fabrica        ,
                                    produto        ,
                                    peca           ,
                                    qtde           ,
                                    ordem          ,
                                    type           ,
                                    garantia_peca  ,
                                    admin          ,
                                    data_alteracao ,                                    
                                    ativo
                                ) VALUES (
                                    $login_fabrica,
                                    $produto,
                                    {$currentRow['peca']},
                                    {$currentRow['qtde']},
                                    {$currentRow['ordem']},
                                    '{$currentRow['type']}',
                                    {$currentRow['garantia_peca']},
                                    $login_admin,
                                    current_timestamp,
                                    't'
                                );";
                    //die(nl2br($sql));
                    $res = pg_query($con, $sql);

                    if (strlen(pg_last_error()) > 0) {
                        throw new Exception("Erro ao salvar peça. Peça: " . $currentRow["referencia"]);
                    }

                    if($login_fabrica == 1 AND strlen(trim($currentRow['garantia_peca'])) > 0){
                        $desgaste_dw = true;
                        $sql = "UPDATE  tbl_peca
                                SET     garantia_diferenciada = ".$currentRow['garantia_peca']."
                                WHERE   fabrica = $login_fabrica
                                AND     peca    = ".$currentRow['peca'];
                        //die(nl2br($sql));
                        $res = pg_query($con, $sql);

                        if (strlen(pg_last_error()) > 0) {
                            throw new Exception("Erro ao salvar desgaste. Peça: " . $currentRow["referencia"]);
                        }
                    }else{
                        $sql = "UPDATE  tbl_peca
                                SET     garantia_diferenciada = null
                                WHERE   fabrica = $login_fabrica
                                AND     peca    = ".$currentRow['peca'];
                                $res = pg_query($con, $sql);
                        //die(nl2br($sql));
                        if (strlen(pg_last_error()) > 0) {
                            throw new Exception("Erro ao atualizar o desgaste. Peça: " . $currentRow["referencia"]);
                        }
                    }
                } else {
                    $erro[] = $currentRow["referencia"];
                    continue;
                }
            }

            if(count($erro) > 0){
                $pecas_erradas = implode(", ",$erro);
                throw new Exception("Peça(s) não encontada(s): ".$pecas_erradas);
            }else if ($desgaste_dw === false) {
                throw new Exception("Para os produtos da marca Dewalt é obrigatório o upload com uma ou mais peças de Desgaste Natural");
            }

            pg_query($con, "COMMIT TRANSACTION");

            $auditorLog->retornaDadosTabela()
                       ->enviarLog("update", "tbl_lista_basica", $login_fabrica."*".$produto);

            header ("Location: $PHP_SELF?produto=$produto&msg=importar");
            exit;

        } else {

            $auditorLog = new AuditorLog();    
            $auditorLog->retornaDadosTabela("tbl_lista_basica", array("produto"=>$produto, "fabrica"=>$login_fabrica) );

            pg_query($con, "BEGIN TRANSACTION");

            if ($login_fabrica != 158) {
                $sql = "DELETE FROM tbl_lista_basica
                        WHERE  tbl_lista_basica.produto = $produto
                        AND    tbl_lista_basica.fabrica = $login_fabrica";
                $res = pg_query ($con,$sql);
            }

            $contador_linha = count($rows);
            for($i = 0; $i < $contador_linha; $i++){

                if ($login_fabrica == 158) {
                    $acao = $importaArquivo->insertOrUpdate($i);
                    // print_r($acao);
                    $acaoGravacao = strtoupper($acao['acao']);

                    $referencia_produto = $acao['produto'];

                    $sqlProd = "
                        SELECT  produto
                        FROM    tbl_produto
                        WHERE   fabrica_i = $login_fabrica
                        AND     referencia = '$referencia_produto'
                    ";
                    // echo nl2br($sqlProd);exit;
                    $resProd = pg_query($con,$sqlProd);
                    $produto = pg_fetch_result($resProd,0,produto);
                }

                $table =  array_keys($rows[$i]);
                $table =  $table[0];
                $currentRow = $rows[$i][$table];

                $table = $table[0];
                $currentRow["referencia"] = $currentRow["peca"];
                $currentRow["peca"] = verificaPeca($currentRow["peca"]);

                if ($currentRow['peca']) {
                    if ($login_fabrica == 1) {
                        $aux_garantia = $currentRow['garantia_peca'];
                        $aux_ins  = " ,garantia_peca ";

                        if (!empty($aux_garantia)) {
                            $aux_ins2 = " ,{$currentRow['garantia_peca']} ";
                        } else {
                            $aux_ins2 = " ,null ";
                        }
                    }
                    if ($login_fabrica != 158) {
                        $sql = "INSERT INTO tbl_lista_basica (
                                        fabrica        ,
                                        produto        ,
                                        peca           ,
                                        qtde           ,";
                                        if ($login_fabrica != 1) {
                                            $sql .= " posicao, ";
                                        }
                                        $sql .= "ordem ,
                                        type           ,
                                        admin          ,
                                        data_alteracao ,
                                        ativo
                                        $aux_ins
                                    ) VALUES (
                                        $login_fabrica,
                                        $produto,
                                        {$currentRow['peca']},
                                        {$currentRow['qtde']},";
                                        if ($login_fabrica != 1) {
                                            $sql .= " '{$currentRow['posicao']}', ";
                                        }
                                        $sql .= "{$currentRow['ordem']},
                                        '{$currentRow['type']}',
                                        $login_admin,
                                        current_timestamp,
                                        't'
                                        $aux_ins2
                                    );";
                        $res = pg_query($con, $sql);
                        if ($res === false) {
                            throw new Exception("Erro ao salvar peça. Peça: " . $currentRow["referencia"]);
                        }
                        if($login_fabrica == 1 AND $currentRow['garantia_peca'] > 0 ){
                            $sql = "UPDATE  tbl_peca
                                    SET     garantia_diferenciada = ".$currentRow['garantia_peca']."
                                    WHERE   fabrica = $login_fabrica
                                    AND     peca    = ".$currentRow['peca'].";";
                            $res = pg_query($con, $sql);

                            if ($res ===false) {
                                throw new Exception("Erro ao salvar desgaste. Peça: " . $currentRow["referencia"]);
                            }
                        }
                    } else {

                        if (trim($acaoGravacao) == "EXCLUIR") {
                            $sql = "DELETE
                                    FROM    tbl_lista_basica
                                    WHERE   peca = ".$currentRow['peca'] ."
                                    AND     produto = $produto
                                    AND     fabrica = $login_fabrica
                            ";
                            $res = pg_query($con,$sql);
                        } else if (trim($acaoGravacao) == "INCLUIR") {
                            $sqlVer = "
                                SELECT  COUNT(1) AS contaPeca
                                FROM    tbl_lista_basica
                                WHERE   peca = ".$currentRow['peca']."
                                AND     produto = $produto
                                AND     fabrica = $login_fabrica
                            ";
                            $resVer = pg_query($con,$sqlVer);

                            if (pg_fetch_result($resVer,0,0) == 0) {
                                $sql = "
                                    INSERT INTO tbl_lista_basica (
                                        fabrica        ,
                                        produto        ,
                                        peca           ,
                                        qtde           ,
                                        admin          ,
                                        data_alteracao ,
                                        ativo
                                    ) VALUES (
                                        $login_fabrica          ,
                                        $produto                ,
                                        ".$currentRow['peca']." ,
                                        ".$currentRow['qtde']." ,
                                        $login_admin            ,
                                        CURRENT_TIMESTAMP       ,
                                        TRUE
                                    )
                                ";
                            } else {
                                $sql = "
                                    UPDATE  tbl_lista_basica
                                    SET     qtde = ".$currentRow['qtde'].",
                                            data_alteracao = CURRENT_TIMESTAMP
                                    WHERE   fabrica = $login_fabrica
                                    AND     produto = $produto
                                    AND     peca = ".$currentRow['peca']."
                                ";
                            }
                            $res = pg_query($con,$sql);
                            if ($res === false) {
                                throw new Exception("Erro ao salvar peça. Peça: " . $currentRow["referencia"]);
                            }
                        } else {
                            throw new Exception("Erro ao salvar peça. Colocar ação para gravação. Peça: " . $currentRow["referencia"]);
                        }
                    }
                } else {
                    $erro[] = $currentRow["referencia"];
                    continue;
                }
            }
            if(count($erro) > 0){
                $pecas_erradas = implode(", ",$erro);
                throw new Exception("Peça(s) não encontada(s): ".$pecas_erradas);
            }

            pg_query($con, "COMMIT TRANSACTION");

            $auditorLog->retornaDadosTabela()
                       ->enviarLog("update", "tbl_lista_basica", $login_fabrica."*".$produto);

            header ("Location: $PHP_SELF?produto=$produto&msg=importar");
            exit;

        }

    } catch(Exception $ex) {
        pg_query($con, "ROLLBACK TRANSACTION");
        $msg_erro["msg"][] = $ex->getMessage();
        $msg_erro["msg"][] = "Tente Novamente.";
    }
}

if ($_POST['btn_acao'] == "duplicar") {

    $referencia_duplicar = $_POST["referencia_duplicar"];
    $descricao_duplicar  = $_POST["descricao_duplicar"];
    $produto             = $_POST["produto"];
    $referencia_destino  = $_POST['referencia_duplicar'];
    $referencia_origem   = $_POST['produto_referencia'];

    if (empty($referencia_duplicar)) {
        $msg_erro['msg'][] = traduz("Preencha o campo Ref. Produto");
        $msg_erro["campos"][] = "referencia_duplicar";
    }elseif (empty($descricao_duplicar)) {
        $msg_erro['msg'][] = traduz("Preencha o campo Descrição Produto");
        $msg_erro["campos"][] = "descricao_duplicar";

    }else{
        /*if ($login_fabrica == 175){
            $ordem_producao = $_POST['ordem_producao'];
            $ordem_producao_duplicada     = $_POST['ordem_producao_duplicada'];
            if (empty($ordem_producao_duplicada)){
                $msg_erro['msg'][] = "Preencha os campos obrigatórios";
                $msg_erro["campos"][] = "ordem_producao_duplicada";
            }
        }*/

	$sqld = "SELECT tbl_produto.produto
		FROM   tbl_produto
		JOIN   tbl_linha USING (linha)
		WHERE  tbl_produto.referencia = '$referencia_duplicar'
		AND    tbl_linha.fabrica   = $login_fabrica ";
       
    $resd = pg_query ($con,$sqld);
    if(pg_last_error($con)){
        $msg_erro['msg'][] = pg_last_error($con);
    }

    $contador_resd = pg_num_rows ($resd);

    if ($contador_resd > 0) {
        for ($j=0; $j<$contador_resd; $j++ ){
            $produto_duplicar    = trim(pg_fetch_result($resd,$j,produto));
        }
    }else{
        $msg_erro['msg'][] = traduz("Produto Não Encontrado"); 
    }
    if (empty($produto_duplicar)){
        $msg_erro['msg'][] = traduz("Produto Inválido"); 
    }else{

    $auditorLog = new AuditorLog();    
    $auditorLog->retornaDadosTabela("tbl_lista_basica", array("produto"=>$produto, "fabrica"=>$login_fabrica) );

    $res = pg_query ($con,"BEGIN TRANSACTION");
    /*if ($login_fabrica == 175) {
        $whereOrdemProducao = "AND tbl_lista_basica.ordem_producao = '{$ordem_producao_duplicada}'";
    }*/
    
    $sql = "SELECT distinct tbl_lista_basica.produto
            FROM   tbl_lista_basica
            WHERE  tbl_lista_basica.produto = $produto_duplicar
            AND    tbl_lista_basica.fabrica = $login_fabrica
            $whereOrdemProducao
            ";
    $res = pg_query ($con,$sql);
    if(pg_last_error($con)){
        $msg_erro['msg'][] = pg_last_error($con);
    }

    if (pg_num_rows($res) == 0) {
        /*if ($login_fabrica == 175) {
            $whereOrdemProducao = "AND tbl_lista_basica.ordem_producao = '{$ordem_producao}'";
        }*/
        
        $sql = "SELECT  tbl_lista_basica.peca   ,
                    tbl_lista_basica.peca_pai       ,
                    tbl_lista_basica.qtde           ,
                    tbl_lista_basica.posicao        ,
                    tbl_lista_basica.ordem          ,
                    tbl_lista_basica.serie_inicial  ,
                    tbl_lista_basica.serie_final    ,
                    tbl_lista_basica.type           ,
                    tbl_peca.descricao              ,
                    tbl_peca.referencia
                FROM tbl_lista_basica
                JOIN tbl_peca ON tbl_lista_basica.peca = tbl_peca.peca AND tbl_peca.fabrica = $login_fabrica
                WHERE tbl_lista_basica.fabrica = $login_fabrica
                AND tbl_lista_basica.produto = $produto
                {$whereOrdemProducao}
                ";
        $res = pg_query($con,$sql);
        if(pg_last_error($con)){
            $msg_erro['msg'][] = pg_last_error($con);
        }

        $contador_res = pg_num_rows($res);

        if(pg_num_rows($res) > 0){
            for($i = 0; $i < $contador_res; $i++){
                $peca          = pg_result($res,$i,'peca') ;
                $peca_pai      = pg_result($res,$i,'peca_pai') ;
                $ordem         = pg_result($res,$i,'ordem') ;
                $serie_inicial = pg_result($res,$i,'serie_inicial') ;
                $serie_final   = pg_result($res,$i,'serie_final') ;
                $posicao       = pg_result($res,$i,'posicao') ;
                $descricao     = pg_result($res,$i,'descricao') ;
                $type          = pg_result($res,$i,'type') ;
                $qtde          = pg_result($res,$i,'qtde') ;

                $ordem = trim ($ordem);
                $posicao = trim ($posicao);

                if (strlen($peca_pai)==0) {
                    $peca_pai = 'null';
                }

                $serie_inicial = trim ($serie_inicial);
                $serie_inicial = str_replace (".","",$serie_inicial);
                $serie_inicial = str_replace ("-","",$serie_inicial);
                $serie_inicial = str_replace ("/","",$serie_inicial);
                $serie_inicial = str_replace (" ","",$serie_inicial);

                $serie_final   = trim ($serie_final);
                $serie_final = str_replace (".","",$serie_final);
                $serie_final = str_replace ("-","",$serie_final);
                $serie_final = str_replace ("/","",$serie_final);
                $serie_final = str_replace (" ","",$serie_final);

                if (strlen($type) == 0) $aux_type = null;
                else                    $aux_type = $type;

                if (strlen($qtde) == 0) $aux_qtde = 1;
                else                    $aux_qtde = $qtde;

                if (strlen($ordem) == 0) $ordem = "null";
                
                /*if ($login_fabrica == 175) {
                    $colunaOrdemProducao = ', ordem_producao';
                    $valorOrdemProducao = ", '{$ordem_producao_duplicada}'";
                }*/


                $sqlI = "INSERT INTO tbl_lista_basica (
                                        fabrica        ,
                                        produto        ,
                                        peca           ,
                                        peca_pai       ,
                                        qtde           ,";
                                        if ($login_fabrica != 1) {
                                            $sqlI .= " posicao, ";
                                        }
                                        $sqlI .= "ordem          ,
                                        serie_inicial  ,
                                        serie_final    ,
                                        type           ,
                                        admin          ,
                                        data_alteracao $colunaOrdemProducao
                                    ) VALUES (
                                        $login_fabrica,
                                        $produto_duplicar ,
                                        $peca         ,
                                        $peca_pai     ,
                                        $aux_qtde     ,";
                                        if ($login_fabrica != 1) {
                                            $sqlI .= " '$posicao', ";
                                        }
                                        $sqlI .= "$ordem        ,
                                        '$serie_inicial' ,
                                        '$serie_final'   ,
                                        '$aux_type'   ,
                                        $login_admin,
                                        current_timestamp $valorOrdemProducao
                                    )";
                $resI = pg_query($con,$sqlI);
                if(pg_last_error($con)){
                    $msg_erro['msg'][] = pg_last_error($con);
                }
            }
        }else{
            $msg_erro['msg'][] = "Falha na Gravação"; 
        }


        if (count($msg_erro['msg']) == 0) {

            /* CONSULTA TODOS O MATERIAL CADASTRADO PARA O EQUIPAMENTO DESTINO PARA ALTERAR(ATIVAR) SE JÁ EXISTIR */
            $ch = curl_init();
            curl_setopt_array($ch, array(
                CURLOPT_URL => "http://telecontrol.eprodutiva.com.br/api/recurso/equipamento/codigo/{$referencia_destino}/material/",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_HTTPHEADER => array(
                    "authorizationv2: {$chave_persys}"
                )
            ));

            $response = curl_exec($ch);
            curl_close($ch);
            $response = json_decode($response, true);

            $pecas_dest = array();
            foreach ($response['data'] as $value) {
                $pecas_dest[] = $value['material']['codigo'];
            }

            /* CONSULTA TODOS OS MATERIAIS QUE SERÃO REPLICADOS */
            $ch = curl_init();
            curl_setopt_array($ch, array(
                CURLOPT_URL => "http://telecontrol.eprodutiva.com.br/api/recurso/equipamento/codigo/{$referencia_origem}/material/",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_HTTPHEADER => array(
                    "authorizationv2: {$chave_persys}"
                )
            ));

            $response = curl_exec($ch);
            curl_close($ch);
            $response = json_decode($response, true);

            foreach ($response['data'] as $value) {
                if ($value['statusModel'] == '1') {
                    $max = str_replace('.00', '', $value['maxQuantity']);
                    $min = '1';

                    if (!in_array($value['material']['codigo'], $pecas_dest)) {
                        $operacao = 'POST';
                        $data = array(
                            "material" => array(
                                "id" => $value['material']['id']
                            ),
                            "maxQuantity" => "$max",
                            "minQuantity" => "$min"
                        );
                        $url = "";
                    }else{
                        $operacao = 'PUT';
                        $data = array(
                            "statusModel" => "1",
                            "maxQuantity" => "$max",
                            "minQuantity" => "$min"
                        );
                        $url = "/codigo/".$value['material']['codigo'];
                    }
                    $json = json_encode($data);

                    $ch = curl_init();
                    curl_setopt_array($ch, array(
                        CURLOPT_URL => "http://telecontrol.eprodutiva.com.br/api/recurso/equipamento/codigo/{$referencia_destino}/material".$url,
                        CURLOPT_RETURNTRANSFER => true,
                        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                        CURLOPT_CUSTOMREQUEST => $operacao,
                        CURLOPT_POSTFIELDS => $json,
                        CURLOPT_HTTPHEADER => array(
                            "authorizationv2: {$chave_persys}",
                            "Content-Type: application/json"
                        )
                    ));

                    $response = curl_exec($ch);
                    curl_close($ch);
                    if (!$response) {
                        $msg_erro['msg'][] = "Erro ao tentar atualizar a API mobile!";
                        break;
                    }
                }
            }

            if (count($msg_erro['msg']) == 0) {
                $res = pg_query ($con,"COMMIT TRANSACTION");

                $auditorLog->retornaDadosTabela()
                           ->enviarLog("update", "tbl_lista_basica", $login_fabrica."*".$produto);

                #-------------------- Envia EMAIL ------------------
                #   26/05/2010 MLG - HD 243869
                if ($login_fabrica != 1) {
                    $sql = "SELECT email_gerente FROM tbl_fabrica WHERE fabrica = $login_fabrica AND LENGTH (TRIM (email_gerente)) > 0";
                    $res = pg_query ($con,$sql);
                    if (pg_num_rows ($res) > 0) {
                        $email_gerente = pg_fetch_result ($res,0,0);

                        $sql = "SELECT referencia, descricao FROM tbl_produto WHERE produto = $produto";
                        $res = pg_query ($con,$sql);
                        $produto_referencia = pg_fetch_result ($res,0,referencia);
                        $produto_descricao  = pg_fetch_result ($res,0,descricao);

                        $email_ok = mail ("$email_gerente" , utf8_encode("Duplicação de Lista Básica") , utf8_encode("A lista básica do produto $produto_referencia - $produto_descricao acaba de ser criada a partir de uma duplicação no site TELECONTROL") , "From: Telecontrol <helpdesk@telecontrol.com.br>" , "-f helpdesk@telecontrol.com.br" );
                    }
                }
                #---------------------------------------------------

                /*if ($login_fabrica == 175){
                    header ("Location: $PHP_SELF?produto=$produto&ordem_producao=$ordem_producao&msg=duplicar");
                }else{*/
                    header ("Location: $PHP_SELF?produto=$produto&msg=duplicar");
                // }
                exit;
            }else{
                $res = pg_query ($con,"ROLLBACK TRANSACTION");
            }
        }
        $res = pg_query ($con,"ROLLBACK TRANSACTION");

    } else {

        $produto = $produto_duplicar;
        $sql = "SELECT tbl_produto.referencia
                FROM   tbl_produto
                JOIN   tbl_linha USING (linha)
                WHERE  tbl_produto.produto = $produto_duplicar
                AND    tbl_linha.fabrica   = $login_fabrica";
        $res = pg_query ($con,$sql);
        if (pg_num_rows($res) > 0){
            $referencia = trim(pg_fetch_result($res,0,referencia));
            $msg_erro['msg'][] = traduz("Produto % já possui lista básica e não pode ser duplicado.", null, null, [$referencia]);
            if($login_fabrica == 20){
                $btn_lista = "listar";
            }
        }
    }
    }
    }
}

##### DUPLICAR PEÇAS P/ NOVO TYPE P/ BLACK & DECKER #####
if ($_POST['btn_acao'] == "duplicartype" && ($login_fabrica == 1 or $login_fabrica == 51)) {
    $produto               = $_POST["produto"];
    $type_duplicar_origem  = $_POST["type_duplicar_origem"];
    $type_duplicar_destino = $_POST["type_duplicar_destino"];

    if (strlen($type_duplicar_origem) == 0)  $msg_erro['msg'][] = " Selecione o \"Type Origem\" p/ duplicar. ";
    if (strlen($type_duplicar_destino) == 0) $msg_erro['msg'][] = " Selecione o \"Type Destino\" p/ duplicar. ";

    if ($type_duplicar_origem == $type_duplicar_destino) $msg_erro['msg'][] = " Selecione o \"Type Destino\" diferente do \"Type Origem\". ";

    if (strlen($msg_erro) == 0) {

        $auditorLog = new AuditorLog();    
        $auditorLog->retornaDadosTabela("tbl_lista_basica", array("produto"=>$produto, "fabrica"=>$login_fabrica) );

        $res = pg_query ($con,"BEGIN TRANSACTION");

        $sql =  "SELECT tbl_lista_basica.lista_basica
                FROM    tbl_lista_basica
                WHERE   tbl_lista_basica.fabrica = $login_fabrica
                AND     tbl_lista_basica.produto = $produto
                AND     tbl_lista_basica.type    = '$type_duplicar_origem';";
        $res = pg_query($con,$sql);

        if (pg_num_rows($res) == 0) $msg_erro['msg'][] = " Não foi encontrado lista básica p/ este produto com o Type Origem \"$type_duplicar_origem\". ";

        $sql =  "SELECT tbl_lista_basica.lista_basica
                FROM    tbl_lista_basica
                WHERE   tbl_lista_basica.fabrica = $login_fabrica
                AND     tbl_lista_basica.produto = $produto
                AND     tbl_lista_basica.type    = '$type_duplicar_destino';";
        $res = pg_query($con,$sql);

        if (pg_num_rows($res) > 0) $msg_erro['msg'][] = " Type Destino \"$type_duplicar_destino\" já cadastrado na lista básica p/ este produto. ";

        if (count($msg_erro['msg']) == 0) {
            $sql =  "INSERT INTO tbl_lista_basica (
                        fabrica       ,";
                        if ($login_fabrica != 1) {
                            $sql .= " posicao, ";
                        }
                        $sql .="ordem         ,
                        serie_inicial ,
                        serie_final   ,
                        qtde          ,
                        peca          ,
                        produto       ,
                        type          ,
                        admin         ,
                        data_alteracao
                    )   SELECT  fabrica                          ,";
                                if ($login_fabrica != 1) {
                                    $sql .= " posicao, ";
                                }
                                $sql .= "ordem                            ,
                                serie_inicial                    ,
                                serie_final                      ,
                                qtde                             ,
                                peca                             ,
                                produto                          ,
                                '$type_duplicar_destino' AS type ,
                                $login_admin                     ,
                                current_timestamp
                        FROM tbl_lista_basica
                        WHERE fabrica = $login_fabrica
                        AND   produto = $produto
                        AND   type    = '$type_duplicar_origem';";
            $res = @pg_query($con,$sql);
            if(pg_last_error($con)){
                $msg_erro['msg'][] = pg_last_error($con);
            }

            if (count($msg_erro['msg']) == 0) {
                $res = pg_query ($con,"COMMIT TRANSACTION");

                $auditorLog->retornaDadosTabela()
                           ->enviarLog("update", "tbl_lista_basica", $login_fabrica."*".$produto);

                header ("Location: $PHP_SELF?produto=$produto&msg=type");
                exit;
            }else{
                $res = pg_query ($con,"ROLLBACK TRANSACTION");
            }
        }
    }
}

if (isset($_REQUEST['ajax_atualiza_lista']) && in_array($login_fabrica, array(169,170))) {
    
    $produto         = $_REQUEST['produto'];
    $produto_referencia = $_REQUEST['produto_referencia'];
    $produto_serie      = (!empty($_REQUEST['produto_serie'])) ? $_REQUEST['produto_serie'] : "*";
    $erro               = false;

    if ($serverEnvironment == 'development') {
	$urlWSDL = "http://ws.carrieronline.com.br/qa6/PSA_WebService/telecontrol.asmx?WSDL";
    } else {
        $urlWSDL = "http://ws.carrieronline.com.br/wsPSATeleControl/telecontrol.asmx?WSDL";
    }

    $client = new SoapClient($urlWSDL, array('trace' => 1,'connection_timeout' => 180));

    $params = new SoapVar(
	"<ns1:oXml>
            <Z_CB_TC_PECAS_SUBSTITUICAO xmlns='http://ws.carrieronline.com.br/PSA_WebService'>
                <PV_MATNR>{$produto_referencia}</PV_MATNR>
                <SERIES>
                    <PT_SERNR>{$produto_serie}</PT_SERNR>
                </SERIES>
            </Z_CB_TC_PECAS_SUBSTITUICAO>
        </ns1:oXml>", XSD_ANYXML
    );

    $request   = array('oXml' => $params);
    $result    = $client->Z_CB_TC_PECAS_SUBSTITUICAO($request);
    $dados_xml = $result->Z_CB_TC_PECAS_SUBSTITUICAOResult->any;
    $xml       = simplexml_load_string($dados_xml);
    $xml       = json_decode(json_encode((array)$xml), TRUE);

    if (count($xml['NewDataSet']['ZCBTC_MATERIAIS_EQUIPAMENTOTABLE']) > 0) {
	if (isset($xml['NewDataSet']["ZCBTC_MATERIAIS_EQUIPAMENTOTABLE"]["MATNR"])) {
	    $xml['NewDataSet']["ZCBTC_MATERIAIS_EQUIPAMENTOTABLE"] = [$xml['NewDataSet']["ZCBTC_MATERIAIS_EQUIPAMENTOTABLE"]];
        }

        pg_query($con, "BEGIN;");

        if ($produto_serie == "*") {
            $delLbm = "DELETE FROM tbl_lista_basica WHERE fabrica = {$login_fabrica} AND produto = {$produto};";
        } else {
            $delLbm = "
                DELETE FROM tbl_numero_serie_peca USING tbl_numero_serie WHERE tbl_numero_serie_peca.fabrica = {$login_fabrica} AND tbl_numero_serie_peca.numero_serie = tbl_numero_serie.numero_serie AND tbl_numero_serie.produto = {$produto} AND (tbl_numero_serie.serie = '{$produto_serie}' OR tbl_numero_serie.serie = 'S{$produto_serie}');";
        }

        $res = pg_query($con, $delLbm);

        if (strlen(pg_last_error()) > 0) {
            $erro = true;
        }

        if ($produto_serie != "*") {
            $sql = "SELECT numero_serie FROM tbl_numero_serie WHERE fabrica = {$login_fabrica} AND produto = {$produto} AND (serie = UPPER('{$produto_serie}') OR serie = UPPER('S{$produto_serie}'));";
            $res = pg_query($con, $sql);
            $numero_serie = pg_fetch_result($res, 0, "numero_serie");
        }

        foreach ($xml['NewDataSet']["ZCBTC_MATERIAIS_EQUIPAMENTOTABLE"] as $ponteiro => $pecas) {

            $peca_descricao     = str_replace("'", "", utf8_decode(trim($pecas['MAKTX'])));
            $peca_unidade       = trim($pecas['MEINS']);
            $qtde               = trim($pecas['MNGKO']);
            $peca_referencia    = trim($pecas['MATNR']);

            $nova_peca = false;
            $sql = "SELECT peca FROM tbl_peca WHERE fabrica = {$login_fabrica} AND referencia = '{$peca_referencia}';";
            $res_peca = pg_query($con,$sql);

            if (pg_num_rows($res_peca) == 0) {
                $nova_peca = true;
                $produto_acabado = (strtolower($peca_referencia) == strtolower($referencia)) ? 'true' : 'false';

                $sql = "
                    INSERT INTO tbl_peca (
                        fabrica,
                        referencia,
                        descricao,
                        origem,
                        unidade,
                        produto_acabado,
			intervencao_carteira
                    ) VALUES (
                        {$login_fabrica},
                        '{$peca_referencia}',
                        '{$peca_descricao}',
                        'NAC',
                        '{$peca_unidade}',
                        {$produto_acabado},
			TRUE
                    ) RETURNING peca;
                ";

                $res_peca = pg_query($con, $sql);

                if (strlen(pg_last_error()) > 0) {
                    $erro = true;
                }
            }

            $peca = pg_fetch_result($res_peca, 0, 'peca');

            if (empty($peca)) {
                $erro = true;
            }

            if ($produto_serie != "*") {
                $ins = "
                    INSERT INTO tbl_numero_serie_peca (
                        fabrica,
                        serie_peca,
                        referencia_peca,
                        numero_serie,
                        peca,
                        qtde
                    ) VALUES (
                        {$login_fabrica},
                        'SEM SERIE',
                        '{$peca_referencia}',
                        {$numero_serie},
                        {$peca},
                        {$qtde}
                    );
                ";
            } else {
                $ins = "
                    INSERT INTO tbl_lista_basica (
                        fabrica,
                        peca,
                        produto,
                        qtde
                    ) VALUES (
                        {$login_fabrica},
                        {$peca},
                        {$produto},
                        {$qtde}
                    );
                ";
            }

            pg_query($con, $ins);

            if (strlen(pg_last_error()) > 0) {
                $erro = true;
            }
        }

        if ($erro === false) {
            $retorno = array("sucesso" => utf8_encode("Lista Básica atualizada com sucesso."));
            pg_query($con, "COMMIT;");
        } else {
            $retorno = array("sucesso" => utf8_encode("Não foi possível atualizar a Lista Básica."));
            pg_query($con, "ROLLBACK;");
        }
    }

    exit(json_encode($retorno));
}

if ($_POST['btn_acao'] == "gravar") {
    $produto     = $_POST['produto'];
    $qtde_linhas = $_POST['qtde_linhas'];
    
    /*if ($login_fabrica == 175){
        $numero_ordem_producao = $_POST['ordem_producao'];
    }*/

    $auditorLog = new AuditorLog();    
    $auditorLog->retornaDadosTabela("tbl_lista_basica", array("produto"=>$produto, "fabrica"=>$login_fabrica) );

    $res = pg_query ($con,"BEGIN TRANSACTION");

        $sql = "DELETE FROM tbl_lista_basica
                WHERE  tbl_lista_basica.produto = $produto
                AND    tbl_lista_basica.fabrica = $login_fabrica";
        #$res = pg_query ($con,$sql);

        for ($i = 0 ; $i < $qtde_linhas ; $i++) {
            $ativo="";
            $peca      = $_POST ['peca_referencia_' . $i] ;
            $peca_pai  = $_POST ['peca_pai_' . $i] ;
            $ordem = $_POST ['ordem_' . $i] ;
            $serie_inicial = $_POST ['serie_inicial_' . $i] ;
            $serie_final   = $_POST ['serie_final_' . $i] ;
            $posicao   = $_POST ['posicao_' . $i] ;
            $descricao = $_POST ['peca_descricao_' . $i] ;
            $type      = $_POST ['type_' . $i] ;
            $qtde      = $_POST ['qtde_' . $i] ;
            $ativo     = $_POST ['ativo_' . $i] ;
            $lbm       = $_POST ['lbm_' . $i] ;

            if ($login_fabrica == 15) { #HD 335675 INICIO
                $somente_kit = ($_POST['somente_kit_'.$i] == "t") ? 't' : 'f';
            }#HD 335675 FIM


            if ($login_fabrica == 195) { 
                $data_de       = $_POST ['data_de_' . $i] ;
                $data_ate       = $_POST ['data_ate_' . $i] ;
                $xparametros_adicionais["data_de"] = $data_de;
                $xparametros_adicionais["data_ate"] = $data_ate;
                $xxparametros_adicionais = json_encode($xparametros_adicionais);
            }

            $ordem = trim ($ordem);
            $posicao = trim ($posicao);
            if (strlen($posicao) == 0) $posicao = "null";
            else                       $posicao = "'$posicao'";

            $peca_referencia =$peca;

            $serie_inicial = trim ($serie_inicial);
            $serie_inicial = str_replace (".","",$serie_inicial);
            $serie_inicial = str_replace ("-","",$serie_inicial);
            $serie_inicial = str_replace ("/","",$serie_inicial);
            $serie_inicial = str_replace (" ","",$serie_inicial);

            $serie_final   = trim ($serie_final);
            $serie_final = str_replace (".","",$serie_final);
            $serie_final = str_replace ("-","",$serie_final);
            $serie_final = str_replace ("/","",$serie_final);
            $serie_final = str_replace (" ","",$serie_final);

            if (strlen($type) == 0) $type = "null";
            else                    $type = "'$type'";

            if ($login_fabrica == 15){
                $type = (!empty( $_POST[ 'unica_os_'.$i ] )) ? "'".$_POST['unica_os_'.$i]."'" : "null" ;
            }

            if (strlen($peca_pai)==0) {
                $peca_pai = 'null';
            }

            if (strlen($qtde) == 0) $aux_qtde = 1;
            else                    $aux_qtde = $qtde;

            if (strlen($ordem) == 0) $ordem = "null";

            if($login_fabrica == 50) {
                if(strlen($ativo) ==0) {
                    $ativo = "f";
                }else{
                    $ativo = "t";
                }
            }else{
                if (strlen($ativo) == 0) $ativo = 't';
            }

            if (count($msg_erro['msg']) == 0) {
                if (strlen ($peca) > 0 AND empty($lbm)) {
                    $sql = "SELECT peca FROM tbl_peca WHERE referencia = '$peca' AND fabrica = $login_fabrica";
                    $res = @pg_query ($con, $sql);
                    #echo "1-".nl2br($sql).'<br>';
                    if (@pg_num_rows ($res) == 0) {
                        $msg_erro['msg'][] = traduz("Peça % não cadastrada", null, null, [$peca]);
                    }else{
                        $peca = @pg_fetch_result ($res,0,0);

                    if (strlen($peca_pai)>0) {
                        $sql = "SELECT peca FROM tbl_peca WHERE referencia = '$peca_pai' AND fabrica = $login_fabrica";
                        $res = @pg_query ($con, $sql);
                        if (@pg_num_rows ($res) > 0) {
                            $peca_pai = @pg_fetch_result ($res,0,0);
                        }
                    }

                        //NÃO PODE INSERIR 2 PEÇAS NO MESMO PRODUTO - RAPHAEL GIOVANINI
                        if($login_fabrica==3 ){
                            $sql = "SELECT count(peca)as total FROM tbl_lista_basica
                                    WHERE produto = $produto
                                    AND   peca    = $peca
                                    AND   fabrica = $login_fabrica
                                    having count(peca)>0";
                            $res = @pg_query ($con, $sql);

                            if (@pg_num_rows ($res) > 0) {
                                $total = pg_fetch_result ($res,0,total);
                                $msg_erro['msg'][] = traduz("% Peça % já cadastrada na lista básica deste produto <br><br>", null, null, [$total, $peca_referencia]);
                            }//else echo
                        }
                        if (count($msg_erro['msg']) == 0) {
                            //Intelbras com problema de itens ativos e inativos na lista básica
                            //HD 3211
                            if($login_fabrica == 14){
                                $sql = "INSERT INTO tbl_lista_basica (
                                            fabrica       ,
                                            produto       ,
                                            peca          ,
                                            qtde          ,
                                            posicao       ,
                                            ordem         ,
                                            serie_inicial ,
                                            serie_final   ,
                                            type          ,
                                            ativo         ,
                                            admin         ,
                                            data_alteracao
                                        ) VALUES (
                                            $login_fabrica  ,
                                            $produto        ,
                                            $peca           ,
                                            $aux_qtde       ,
                                            $posicao      ,
                                            $ordem          ,
                                            '$serie_inicial',
                                            '$serie_final'  ,
                                            $type         ,
                                            '$ativo'        ,
                                            $login_admin    ,
                                            current_timestamp
                                );";
                            }else{
                                /*if ($login_fabrica == 175) {
                                    $colunaOrdemProducao = ", ordem_producao";
                                    $valorOrdemProducao = ", '{$numero_ordem_producao}'";
                                }*/
                                
                                $sql = "INSERT INTO tbl_lista_basica (
                                            fabrica       ,
                                            produto       ,
                                            peca          ,
                                            peca_pai      ,
                                            qtde          ,";
                                            if ($login_fabrica != 1) {
                                                $sql .= " posicao, ";
                                            }
                                            $sql .= "ordem         ,
                                            serie_inicial ,
                                            serie_final   ,
                                            type          ,
                                            ativo         ,
                                            admin         ,";
                                            if ($login_fabrica == 15) {//HD 335675
                                                $sql .= " somente_kit, ";
                                            }
                                            if ($login_fabrica == 195) {
                                                $sql .= " parametros_adicionais, ";
                                            }
                                            $sql .= "data_alteracao {$colunaOrdemProducao}
                                        ) VALUES (
                                            $login_fabrica  ,
                                            $produto        ,
                                            $peca           ,
                                            $peca_pai       ,
                                            $aux_qtde       ,";
                                            if ($login_fabrica != 1) {
                                                $sql .= " $posicao, ";
                                            }
                                            $sql .= "$ordem          ,
                                            '$serie_inicial',
                                            '$serie_final'  ,
                                            $type         ,
                                            '$ativo'        ,
                                            $login_admin    ,";
                                            if ($login_fabrica == 15) {//HD 335675
                                                $sql .= " '$somente_kit', ";
                                            }
                                            if ($login_fabrica == 195) {
                                                $sql .= " '$xxparametros_adicionais', ";
                                            }
                                            $sql .= "current_timestamp {$valorOrdemProducao}
                                        );";
                            }
                            // echo nl2br($sql);exit;
                            $res = @pg_query ($con,$sql);
                            $msg_erro['msg'][] = pg_errormessage($con);

                            if ($login_fabrica == 80 AND empty($msg_erro)) {
                                $ativa_peca = pg_query($con, "UPDATE tbl_peca SET ativo = TRUE WHERE peca = $peca AND fabrica = $login_fabrica");
                            }
                        }
                    }
                }
            }
        }
    //}


    if (count($msg_erro['msg']) == 0) {
        $res = pg_query ($con,"COMMIT TRANSACTION");

        $auditorLog->retornaDadosTabela()
                   ->enviarLog("update", "tbl_lista_basica", $login_fabrica."*".$produto);

        #-------------------- Envia EMAIL ------------------
        #   26/05/2010 MLG - HD 243869
        if ($login_fabrica != 1) {
            $sql = "SELECT email_gerente FROM tbl_fabrica WHERE fabrica = $login_fabrica AND LENGTH (TRIM (email_gerente)) > 0";
            $res = pg_query ($con,$sql);
            if (pg_num_rows ($res) > 0) {
                $email_gerente = pg_fetch_result ($res,0,0);

                $sql = "SELECT referencia, descricao FROM tbl_produto WHERE produto = $produto";
                $res = pg_query ($con,$sql);
                $produto_referencia = pg_fetch_result ($res,0,referencia);
                $produto_descricao  = pg_fetch_result ($res,0,descricao);

                $email_ok = mail ("$email_gerente" , utf8_encode("Alteração de Lista Básica") , utf8_encode("A lista básica do produto $produto_referencia - $produto_descricao acaba de ser alterada no site TELECONTROL") , "From: Telecontrol <helpdesk@telecontrol.com.br>" , "-f helpdesk@telecontrol.com.br" );
            }
        }
        #---------------------------------------------------
        header ("Location: $PHP_SELF?produto=$produto&msg=gravar");
        exit;
    }

    $referencia = $_POST["referencia"];
    $descricao  = $_POST["descricao"];
    $res = pg_query ($con,"ROLLBACK TRANSACTION");
}

$ordem_p = true;
/*if ($login_fabrica == 175 AND empty($ordem_producao) AND !empty($produto)){
    $sql_lista_ordem = "
        SELECT 
            DISTINCT(ordem_producao),
            tbl_produto.referencia,
            tbl_produto.descricao
        FROM tbl_lista_basica
        JOIN tbl_produto ON tbl_produto.produto = tbl_lista_basica.produto AND tbl_produto.fabrica_i = $login_fabrica
        WHERE tbl_lista_basica.fabrica = $login_fabrica
        AND tbl_lista_basica.produto = $produto ";
    $res_lista_ordem = pg_query($con, $sql_lista_ordem);
    
    if (strlen(pg_num_rows($res_lista_ordem)) > 0){
        $result_lista_ordem = pg_fetch_all($res_lista_ordem);
    }
    $ordem_p = false;
}*/

if (!empty($produto) AND $ordem_p === true) {

    if ($login_fabrica == 45) {
        $slt_preco  = " tbl_tabela_item.preco,";
        $join_preco = " LEFT JOIN tbl_tabela_item USING (peca) ";
    }

    if (in_array($login_fabrica, [169,170])) {
        $whereLCP = "AND tbl_peca.intervencao_carteira IS TRUE";
    }

    if (in_array($login_fabrica, array(169,170)) && strlen($produto_serie) > 0) {
        $sqlNumSerPeca = "
            SELECT
                tbl_numero_serie.serie,
                tbl_numero_serie_peca.peca AS peca_de_verdade,
                tbl_numero_serie_peca.numero_serie_peca AS lista_basica,
                tbl_numero_serie_peca.ordem,
                '' AS posicao,
                tbl_peca.referencia,
                tbl_numero_serie_peca.qtde,
                tbl_peca.descricao,
		tbl_peca.peca,
		tbl_peca.referencia,
                tbl_peca.referencia_fabrica,
                tbl_peca.garantia_diferenciada
            FROM tbl_numero_serie_peca
            JOIN tbl_numero_serie ON tbl_numero_serie.numero_serie = tbl_numero_serie_peca.numero_serie AND tbl_numero_serie.fabrica = {$login_fabrica}
            JOIN tbl_peca ON tbl_peca.peca = tbl_numero_serie_peca.peca AND tbl_peca.fabrica = {$login_fabrica}
            WHERE tbl_numero_serie_peca.fabrica = {$login_fabrica}
            AND tbl_numero_serie.produto = {$produto}
            AND tbl_numero_serie.serie = '{$produto_serie}'
	    {$whereLCP};
        ";
        $resNumSerPeca = pg_query($con, $sqlNumSerPeca);
    }

    if (!in_array($login_fabrica, array(169,170)) || (in_array($login_fabrica, array(169,170)) && strlen($produto_serie) == 0)) {
        if ($login_fabrica == 175 AND !empty($ordem_producao)){
            $cond_ordem = " AND tbl_lista_basica.ordem_producao = '$ordem_producao' ";
        }else{
            $cond_ordem = "";
        }
        
        $sql = "
            SELECT
                $slt_preco
                TO_CHAR(tbl_lista_basica.data_ativa , 'DD/MM/yyyy') AS data_fabricacao,
                tbl_lista_basica.peca AS peca_de_verdade,
                tbl_lista_basica.lista_basica,
                tbl_lista_basica.ordem,
                tbl_lista_basica.posicao,
                tbl_lista_basica.serie_inicial,
                tbl_lista_basica.serie_final,
                tbl_lista_basica.qtde,
                tbl_lista_basica.type,
                tbl_lista_basica.somente_kit,
                tbl_lista_basica.parametros_adicionais,
                tbl_lista_basica.ativo,
                tbl_lista_basica.ordem_producao,
                tbl_peca.referencia,
                tbl_peca.referencia_fabrica,
                tbl_peca.descricao,
                tbl_peca.garantia_diferenciada ,
                (SELECT tbl_peca.descricao FROM tbl_peca WHERE tbl_peca.peca = tbl_lista_basica.peca_pai) AS descricao_pai,
                (SELECT tbl_peca.referencia FROM tbl_peca WHERE tbl_peca.peca = tbl_lista_basica.peca_pai) AS referencia_pai,
                tbl_lista_basica.peca_pai,
                tbl_peca.peca
            FROM tbl_lista_basica
            JOIN tbl_peca USING(peca)
            {$join_preco}
            WHERE tbl_lista_basica.fabrica = {$login_fabrica}
            AND tbl_lista_basica.produto = {$produto}
            $cond_ordem
	    {$whereLCP}
        ";
        $order_by = trim($_GET['ordem']);

        if (strlen($order_by) == 0) {
            if ($login_fabrica == 1) {
                $sql .= "ORDER BY tbl_lista_basica.ordem, tbl_peca.descricao";
            } else if ($login_fabrica == 51) {
				$sql .= "ORDER BY tbl_lista_basica.type, tbl_lista_basica.ordem";
			} else if ($login_fabrica == 120 or $login_fabrica == 201) {
                $sql .= "ORDER BY tbl_lista_basica.serie_inicial, tbl_lista_basica.serie_final";
            } else if ($login_fabrica == 45) {
                $sql .= "ORDER BY tbl_lista_basica.ordem"; // HD 8226 Gustavo
            } else if (in_array($login_fabrica,array(11,15,50,172))) {
                $sql .= "ORDER BY tbl_peca.descricao"; // HD 8226 Gustavo
            } else {
                $sql .= "ORDER BY tbl_peca.referencia, tbl_peca.descricao";
            }
        } else {
            switch ($order_by) {
                case 'referencia':  $sql .= "ORDER BY tbl_peca.referencia";      break;
                case 'descricao':   $sql .= "ORDER BY tbl_peca.descricao";       break;
                case 'posicao':     $sql .= "ORDER BY tbl_lista_basica.posicao"; break;
                case 'qtde':        $sql .= "ORDER BY tbl_lista_basica.qtde";    break;
                case 'ordem':       $sql .= "ORDER BY tbl_lista_basica.ordem";   break;
                case 'preco':       $sql .= "ORDER BY tbl_tabela_item.preco";    break;
            }
        }

        $resLista = pg_query ($con,$sql);
    } else {
        $resLista = $resNumSerPeca;
    }

    if(pg_last_error($con)){
        $msg_erro['msg'][] = pg_last_error($con);
    }

    // INÍCIO -- Verificação de Vista Explodida
    // HD 3697854 - Vistas Explodidas da Mondial para a AkaciaEletro
    $fabrica_comunicado = $login_fabrica == 168 ? 151 : $login_fabrica;

    /*
        $sql = "SELECT DISTINCT comunicado, extensao
                FROM tbl_comunicado
                LEFT JOIN tbl_comunicado_produto CP USING (comunicado, produto)
                JOIN tbl_produto               USING (produto)
                WHERE tbl_produto.referencia = '{$_POST['produto_referencia']}'
                AND tbl_produto.fabrica_i  = $fabrica_comunicado
                AND tbl_comunicado.tipo    = 'Vista Explodida'
                ORDER BY comunicado DESC
                LIMIT 1";
    */
    /*if ($login_fabrica == 175) {
        $whereOrdemProducao = "AND tbl_comunicado.versao = '{$ordem_producao}'";
    }*/
    
    $sql = "SELECT DISTINCT comunicado, extensao
            FROM tbl_comunicado
            LEFT JOIN tbl_comunicado_produto CP USING (comunicado)
            WHERE tbl_comunicado.fabrica = $fabrica_comunicado
            AND tbl_comunicado.tipo = 'Vista Explodida'
            and (tbl_comunicado.produto = $produto OR CP.produto = $produto)
            $whereOrdemProducao
            ORDER BY comunicado DESC
            LIMIT 1";
    $res = pg_query($con,$sql);
    if(pg_last_error($con)){
        $msg_erro['msg'][] = pg_last_error($con);
    }

    if (pg_num_rows($res) > 0) {
        $vista_explodida = pg_fetch_result($res,0,'comunicado');
        $ext             = pg_fetch_result($res,0,'extensao');
    }

    if ($S3_sdk_OK) {
        include_once S3CLASS;
        if ($S3_online)
            $s3 = new anexaS3('ve', (int) $fabrica_comunicado);
    }
    if (strlen($vista_explodida) > 0) {
        $linkVE = null;
        if ($S3_online) {
            if ($s3->temAnexos($vista_explodida))
                $linkVE = $s3->url;
        } else {
            // echo '../comunicados/'.$vista_explodida.'.'.$ext;
            if (file_exists ('../comunicados/'.$vista_explodida.'.'.$ext)) {
                $linkVE = "../comunicados/$vista_explodida.$ext";
            }
        }
    }

    //FIM -- Verificação de Vista Explodida

    // INÍCIO -- Verifica alteração
    $sql = " SELECT tbl_admin.login,to_char(tbl_lista_basica.data_alteracao,'DD/MM/YYYY HH24:MI') as data_alteracao2, data_alteracao
            FROM tbl_lista_basica
            JOIN tbl_admin USING(admin)
            WHERE produto = $produto
            AND   tbl_lista_basica.admin IS NOT NULL
            AND   tbl_lista_basica.data_alteracao IS NOT NULL
            ORDER BY data_alteracao desc limit 1";
    $res = pg_query($con,$sql);
    if(pg_last_error($con)){
        $msg_erro['msg'][] = pg_last_error($con);
    }

    if (pg_num_rows($res) > 0) {
        $login_alt      = pg_fetch_result($res,0,'login');
        $data_alteracao = pg_fetch_result($res,0,'data_alteracao2');
    }
    //FIM -- Verifica alteração
}

$layout_menu = "cadastro";
$title = traduz("CADASTRAMENTO DE LISTA BÁSICA");
include 'cabecalho_new.php';


$plugins = array(
    "autocomplete",
    "shadowbox",
    "alphanumeric",
    "price_format",
    "multiselect",
    "dataTable"
);

include("plugin_loader.php");

if(empty($qtde_linhas) OR $qtde_linhas == 0){
    $qtde_linhas = 1;
}

//Array de Legendas (cor => titulo)

$arrayLegenda = array(
                      array(
                        "cor" => "#5BB75B",
                        "titulo" => "De-Para"
                      )
                );
if($login_fabrica == 14){
    $arrayLegenda[] = array("cor" => "#F2ED84",
                            "titulo" => "Peça Inativa"
                            );
}

if(in_array($login_fabrica, [3,7,8,10,11,17,20,30,43,45,122,147,160,169,170,172,194])) {
	$arrayLegenda[] = array(
		"cor" => "#91C8FF",
		"titulo" => "Peça Alternativa"
	);
}

if($login_fabrica == 1){
    $arrayLegenda[] = array("cor" => "#E33B3B",
                            "titulo" => "Ordem Duplicada"
                            );
}

?>

<style>
.emptyLine td {
    background-color: #F00 !important;
}

.sublista_expandir {
    transform: rotate(180deg);
}

.sublista_recolher {
    transform: rotate(0deg);
}

.message_upload {
    margin-right: 1%;
    margin-left: 1%;
    width: 80%;
    white-space: normal;
}
</style>
<link type="text/css" href="../js/pikachoose/css/css3.css" rel="stylesheet" />
<script type="text/javascript" src="../js/pikachoose/js/jquery.jcarousel.min.js"></script>
<script type="text/javascript" src="../js/pikachoose/js/jquery.touchwipe.min.js"></script>
<script type="text/javascript" src="../js/pikachoose/js/jquery.pikachoose.js"></script>
<link href="../js/imgareaselect/css/imgareaselect-default.css" rel="stylesheet" type="text/css"/>
<link href="../js/imgareaselect/css/imgareaselect-animated.css" rel="stylesheet" type="text/css"/>
<script type="text/javascript" src="../js/imgareaselect/js/jquery.imgareaselect.js"></script>
<script type="text/javascript" src="../js/ExplodeView.js"></script>
<script type="text/javascript" src="../js/jquery.form.js"></script>

<script type="text/javascript" src="plugins/fixedtableheader/jquery.fixedtableheader.min.js"></script>
<script language="javascript">
    var hora = new Date();
    var engana = hora.getTime();

    $(function() {

        $('.data_fabricacao').datepicker();
        $('.data_pik').datepicker();
        $.autocompleteLoad(Array("produto"), Array("produto"));
        Shadowbox.init();

        $(document).on("focus","input[name^=qtde_],input[name^=ordem_]",function () {
            $(this).numeric({'allow':','});
            $('.data_pik').datepicker();
        });

        $("#ordem_producao").numeric();
        $("#ordem_producao_duplicada").numeric();
        $("#nova_ordem_producao").numeric();
        $(document).on("click", "span[rel=lupa]", function () {
            $.lupa($(this),Array('posicao', 'subitem' <?= ($login_fabrica == 30) ? ",'pesquisa_produto_acabado'" : ""; ?>));
        });

        $("#linha_excel").multiselect({
            selectedText: "selecionados # de #"
        });

        $(".ui-multiselect-all").hide();

        $(document).on("click", '.ui-multiselect-none', function(){
            $("input[name=multiselect_linha_excel]").prop("disabled", false);
        });

        $("#linha_excel").change(function(){

            let linhas_selecionadas = $("select#linha_excel").val();

            if (linhas_selecionadas === null) {
                linhas_selecionadas = [];
            }

            if (linhas_selecionadas.length > 0) {

                let json_excel = JSON.stringify(linhas_selecionadas);

                $("#gerar_excel").show();
                $(".excel_geral_listas").val('{"excel_geral_listas" : '+json_excel+"}");

            } else {

                $("#gerar_excel").hide();

            }

            if (linhas_selecionadas.length >= 4) {

                $("input[name=multiselect_linha_excel]:not(:checked)").prop("disabled", true);

            } else {

                $("input[name=multiselect_linha_excel]:not(:checked)").prop("disabled", false);

            }

        });
       

        $(".fixedTableHeader").fixedtableheader();

        $(document).on("blur", "table.pecas > tbody > tr > td input", function (e, input) {
            var linha = $(this).parents("tr");
            var peca_ref = $.trim($(linha).find("input[name^=peca_referencia_]").val());
            var peca_des = $.trim($(linha).find("input[name^=peca_descricao_]").val());
            var qtde     = $.trim($(linha).find("input[name^=qtde_]").val());

            if (qtde.length > 0 && peca_ref.length > 0 && peca_des.length > 0) {
                //alert("verificalinhas");
                verificaLinhas();
            }
        });;

        $(document).on("click","button[id^=sublista_linha_]",function(){
            var id_linha        = this.id.replace(/[^\d]+/g,'');
            peca_referencia = $("#peca_referencia_" + id_linha).val();
            peca_descricao  = $("#peca_descricao_" + id_linha).val();

            if($(this).hasClass("sublista_expandir")){
                $(this).removeClass("sublista_expandir").addClass("sublista_recolher");
                $(".tabela_subitem_" + id_linha).hide();
             } else if(peca_referencia != "" && peca_descricao != ""){
                $(this).removeClass("sublista_recolher").addClass("sublista_expandir");
                $(".tabela_subitem_" + id_linha).show();
            } else {
                alert("Peça não informada!");
            }
        });

        $(document).on("click","button[id^=subitem_remover_linha_]",function(){
            var idlinha        = this.id.replace('subitem_remover_linha_','');
            var peca_container = $("#subitem_peca_container_" + idlinha).val();
            var peca_filha     = $("#subitem_peca_filha_" + idlinha).val();
            var btn_remover    = "#" + this.id;

            disable_button_loading(btn_remover, "Excluindo...", true);

            if(peca_container == ""){
                alert("Erro ao excluir subitem!");
                disable_button_loading(btn_remover, "Excluir", false);
            } else {
                if (confirm('Deseja realmente excluir o subitem?') == true) {
                    $.ajax({
                        url: "lbm_cadastro.php",
                        type: "POST",
                        data: {
                            ajax_subitem_remover : "true",
                            peca_container       : peca_container,
                            peca_filha           : peca_filha
                        },
                    })
                    .done(function(data){
                        data = JSON.parse(data);
                        if(data.success){
                            $("#subitem_tr_" + peca_container).html("<td colspan='100%' class='tac' style='text-align: center;'><div class='alert-success'><h4>Item excluído com sucesso</h4></div></td>");
                            setTimeout(function(){
                                disable_button_loading(btn_remover, "Excluir", false);
                                $("#subitem_tr_" + peca_container).hide();
                            },2000);
                        } else {
                            alert(data.msg);
                        }
                    });
                } else {
                    disable_button_loading(btn_remover, "Excluir", false);
                }
            }
        });

        $(document).on("click","button[id^=remove_linha_]",function(){
            var idlinha     = this.id.replace("remove_linha_","");
            var linha       = $(this).parents("tr");
            var lbm         = $(linha).find("input[name^=lbm_]").val();
            var produto     = $(this).parents("table").find("input[name=produto]").val();
            var btn_remover = "#" + this.id;
            disable_button_loading(btn_remover, "Excluindo...", true);

            if (confirm('Deseja realmente excluir?') == true) {
                $.ajax({
                    url: "lbm_cadastro.php?ajax_remove=sim&lbm="+lbm+"&produto="+produto,
                    complete: function(data){
                        if(data.responseText == "ok"){
                            $(linha).html("<td colspan='100%' class='tac'><div class='alert-success'>Item excluído com sucesso</div></td>");
                            $(".subitem_pecas_" + idlinha).remove();

                            setTimeout(function(){
                                $(".tabela_subitem_" + idlinha).remove();
                                $(linha).remove();
                            },1000);
                        }else{
                            alert(data.responseText);
                        }
                        disable_button_loading(btn_remover, "Excluir", false);
                    }
                });
            } else {
                disable_button_loading(btn_remover, "Excluir", false);
            }
        });

        $(document).on("click","button[id^=gravar_linha_]",function(){
            var linha           = $(this).parents("tr");
            var produto         = $(this).parents("table.pecas").find("input[name=produto]").val();
            <?php if (in_array($login_fabrica, array(169,170))) { ?>
                var serie       = $(this).parents("table.pecas").find("input[name=produto_serie]").val();
            <? } ?>
            var lbm             = $(linha).find("input[name^=lbm_]").val();
            <?php /*if ($login_fabrica == 175) { ?>
                var ordem           = $("input[name^=ordem_producao]").val();
            <?php } else {*/ ?>
                var ordem           = $(linha).find("input[name^=ordem_]").val();
            <?php // } ?>
            var posicao         = $(linha).find("input[name^=posicao_]").val();
            var referencia      = $(linha).find("input[name^=peca_referencia_]").val();
            var qtde            = $(linha).find("input[name^=qtde_]").val().replace(",",".");
            var ativo           = $(linha).find("input[name^=ativo_]:checked").val();
            var somente_kit     = $(linha).find("input[name^=somente_kit_]:checked").val();
            var unica_os        = $(linha).find("input[name^=unica_os_]:checked").val();
            var type            = $(linha).find("select[name^=type_]").val();
            var desgaste        = $(linha).find("input[name^=desgaste_]").val();
            var peca_pai        = $(linha).find("input[name^=peca_pai_]").val();
            var serie_inicial   = $(linha).find("input[name^=serie_inicial_]").val();
            var serie_final     = $(linha).find("input[name^=serie_final_]").val();
            <?php if(in_array($login_fabrica, array(195))){ ?>
            var data_de = $(linha).find("input[name^=data_de_]").val();
            var data_ate = $(linha).find("input[name^=data_ate_]").val();
            <?php }?>
            <?php if (in_array($login_fabrica, array(15))) { ?>
            var data_fabricacao = $(linha).find("input[name^=data_fabricacao_]").val();
            <?php }?>
            <?php if ($usa_versao_produto) { ?>
                var versao     = $(linha).find("input[name^=versao_]").val();
            <?php }

            if (in_array($login_fabrica, array(153))) { ?>
                if($(linha).find("input[name^=tipo_]").is(':checked')){
                    var tipo     = $(linha).find("input[name^=tipo_]").val();
                }else{
                    var tipo = 'null';
                }
            <?php } ?>
            var btn_gravar    = "#" + this.id;
            disable_button_loading(btn_gravar, "Gravando...", true);

            if(referencia != "" && qtde != ""){
                $.ajax({
                    url: "lbm_cadastro.php",
                    type: "POST",
                    data: {
                        ajax_item:'sim',
                        lbm             : lbm,
                        produto         : produto,
                        <? if (in_array($login_fabrica, array(169,170))) { ?>
                            serie       : serie,
                        <? } ?>
                        <?php if(in_array($login_fabrica, array(195))){ ?>
                            data_de       : data_de,
                            data_ate       : data_ate,
                        <? } ?>
                        <?php if (in_array($login_fabrica, array(15))) { ?>
                            data_fabricacao :data_fabricacao,
                        <?php }?>
                        ordem           : ordem,
                        posicao         : posicao,
                        peca_referencia : referencia,
                        qtde            : qtde,
                        desgaste        : desgaste,
                        ativo           : ativo,
                        somente_kit     : somente_kit,
                        unica_os        : unica_os,
                        type            : type,
                        peca_pai        : peca_pai,
                        serie_inicial   : serie_inicial,
                        <? if ($usa_versao_produto) { ?>
                            versao : versao,
                        <? }
                        if(in_array($login_fabrica, array(153))){ ?>
                            tipo : tipo,
                        <? } ?>
                        serie_final     : serie_final
                    },
                })
                .done(function(data){
                    data = data.split('|');
                    if(data[0] == "ok"){
                        $(linha).find("input[name^=lbm_]").val(data[1]);
                        $(linha).find("button[id^=gravar_linha_]").hide();
                        disable_button_loading(btn_gravar, "Gravar", false);

                        $(linha).find(".alert-success").show();
                        setTimeout(function(){
                            $(linha).find(".alert-success").hide();
                            $(linha).find("button[id^=remove_linha_]").show();
                            $(linha).find("button[id^=sublista_linha_]").show();
                        },1000);
                    } else {
                        alert(data[1]);
                        disable_button_loading(btn_gravar, "Gravar", false);
                    }
                });
            } else {
                disable_button_loading(btn_gravar, "Gravar", false);
            }
        });

        $(document).on("click","button[name^=subitem_gravar_linha_]",function(){
            var componente_tr = $(this).parents("tr");
            // var idlinha    = this.id.replace(/[^\d]+/g,'');
            var idlinha       = this.id.replace('subitem_gravar_linha_','');
            var produto       = $(this).parents("table.pecas").find("input[name=produto]").val();
            var referencia    = $("#subitem_peca_referencia_" + idlinha).val();
            var qtde          = $("#subitem_qtde_" + idlinha).val().replace(",",".");
            var idlinha_pai   = $("#linha_pai_" + idlinha).val();
            var peca_pai      = $("#peca_referencia_" + idlinha_pai).val();
            var lbm           = $("input[name=lbm_" + idlinha_pai + "]").val();
            var btn_gravar    = "#" + this.id;

            disable_button_loading(btn_gravar, "Gravando...", true);
            
            if(referencia != "" && qtde != ""){
                $.ajax({
                    url: "lbm_cadastro.php",
                    type: "POST",
                    data: {
                        ajax_subitem : "true",
                        produto      : produto,
                        referencia   : referencia,
                        qtde         : qtde,
                        peca_pai     : peca_pai,
                        lbm          : lbm
                    },
                })
                .done(function(data){
                    data = JSON.parse(data);

                    if(data.success){
                        var newTrSub    = $("#subitem_tr_modelo_" + idlinha_pai).clone();
                        var qtde_linhas = $(".subitem_pecas_" + idlinha_pai + " > tbody > tr[id!=subitem_tr_modelo_" + idlinha_pai + "]").length;
                        var nova_tr     = "<tr id='subitem_tr_modelo_" + idlinha_pai + "' >";

                        var find                = "_" + qtde_linhas + '"';
                        var regular_exp         = new RegExp(find, 'g');
                        find                    = 'subitem="' + qtde_linhas + '"';
                        var regular_exp_subitem = new RegExp(find, 'g');

                        nova_tr += $(newTrSub).html().replace(/__model__/g, qtde_linhas)
                            .replace(regular_exp_subitem, 'subitem="' + (qtde_linhas+1) + '"')
                            .replace(regular_exp,"_" + (qtde_linhas+1)+'"');
                        nova_tr += "</tr>";

                        $("#subitem_tr_modelo_" + idlinha_pai).attr("id", "subitem_tr_" + data.peca_container);
                        $("#subitem_peca_container_" + idlinha).val(data.peca_container);
                        $("#subitem_peca_filha_" + idlinha).val(data.peca_filha);

                        $(".subitem_pecas_" + idlinha_pai + " > tbody > tr[id!=subitem_tr_modelo_" + idlinha_pai + "]:last").after(nova_tr);

                        var btnLinhaNova = "subitem_gravar_linha_"+idlinha.replace('_'+ qtde_linhas, '_'+ (qtde_linhas+1));
                        $("#"+btnLinhaNova).attr("disabled", false).text("Gravar");

                        $(componente_tr).find(".alert-success").show();

                        setTimeout(function(){
                            disable_button_loading(btn_gravar, "Gravar", false);
                            $(btn_gravar).hide();
                            $(componente_tr).find(".alert-success").hide();
                            $("#subitem_remover_linha_" + idlinha).show();
                        },1000);
                    } else {
                        disable_button_loading(btn_gravar, "Gravar", false);
                        alert(data.msg);
                    }
                });
            } else {
                alert("Informe a peça que deseja adicionar na lista de subitem!");
                disable_button_loading(btn_gravar, "Gravar", false);
            }
        });

        function disable_button_loading(btn_name, text, disable){
            $(btn_name).text(text).attr("disabled", disable);
        }

        $("table.pecas > tbody > tr > td input,table.pecas > tbody > tr > td select").change(function(){
            var linha = $(this).parents("tr");
            var lbm = $(linha).find("input[name^=lbm_]").val();

            if(lbm != ""){
                $(linha).find("button[id^=remove_linha_]").hide();
                $(linha).find("button[id^=gravar_linha_]").show();
            }
        });

        $("input[id^=versao_]").keyup(function(){
            var letra = jQuery.trim($(this).val());

            letra = letra.replace(/.|-|\//gi,''); // elimina .(ponto), -(hifem) e /(barra)

            var expReg = /^0+$|^1+$|^2+$|^3+$|^4+$|^5+$|^6+$|^7+$|^8+$|^9+$/;
        });

        <? if (in_array($login_fabrica, array(169,170))) { ?>
            $(document).on("click","button[id=btn_atualizar_lbm]",function() {
                var produto = $("input[name=produto]").val();
                var produto_referencia = $("input[name=produto_referencia]").val();
                var produto_serie = $("input[name=produto_serie]").val();
                var that = $(this);

                $.ajax({
                    url: "<?= $PHP_SELF; ?>",
                    type: "POST",
                    data: { ajax_atualiza_lista: true, produto: produto, produto_referencia: produto_referencia, produto_serie: produto_serie },
                    beforeSend: function () {
                        if (that.next("img").length == 0) {
                            that.after($("<img />", { src: "imagens/loading_img.gif", css: { width: "30px", height: "30px" } }));
                        }
                    },
                    complete: function(data) {
                        data = JSON.parse(data.responseText);

                        if (data.erro) {
                            alert(data.erro);
                        } else {
                            alert(data.sucesso);
                            window.location.href = "<?= $PHP_SELF; ?>";
                        }

                        if (that.next("img").length > 0) {
                            that.next("img").remove();
                        }
                    }
                });
            });
        <? } ?>
    });

    <?php /*if ($login_fabrica == 175){ ?>
    function pesquisar_ordem (referencia,descricao, ordem) {
        $("#produto_referencia_lista").val(referencia);
        $("#produto_descricao_lista").val(descricao);
        $("#ordem_producao_lista").val(ordem);
        $("#frm_lbm_lista").submit();
    }

    function cadastra_nova(referencia, descricao){
        var nova_ordem = $("#nova_ordem_producao").val();

        $("#produto_referencia_lista").val(referencia);
        $("#produto_descricao_lista").val(descricao);
        $("#ordem_producao_lista").val(nova_ordem);
        $("#frm_lbm_lista").submit();
    }

    function limpar_dados(){
        window.location="lbm_cadastro.php";
    }

    <?php }*/ ?>
    function verificaLinhas () {
        var create = true;
        var inputFocus;

        $("table.pecas > tbody > tr[id!=linhaModelo]").each(function () {
            var peca_ref = $.trim($(this).find("input[name^=peca_referencia_]").val());
            var peca_des = $.trim($(this).find("input[name^=peca_descricao_]").val());
            var qtde     = $.trim($(this).find("input[name^=qtde_]").val());
            //console.log(peca_ref+" "+peca_des+" "+qtde);

            // if (qtde.length == 0 || peca_ref.length == 0 || peca_des.length == 0) {
            //  inputFocus = $(this).find("input:first");
            //  create = false;
            //  return false;
            // }
        });

        // console.log(create);
        // alert("verificando "+create);
        if (create == true) {
            var qtde_linhas = $("table.pecas > tbody > tr[id!=linhaModelo]").length;
            //alert(qtde_linhas);
            $("input[name=qtde_linhas]").val(qtde_linhas);
            var newTr        = $("#linhaModelo").clone();

            $("table.pecas > tbody > tr[id!=linhaModelo]:last").after("<tr>"+$(newTr).html().replace(/__model__/g, qtde_linhas)+"</tr>");
            <?php if ($login_fabrica == 158) {?>
            var newSubitemTr = $("#tableSubItemModelo").clone();
            $("table.pecas > tbody > tr[id!=#tableSubItemModelo]:last").after('<tr class="tabela_subitem_' + qtde_linhas + '" style="display: none;">' + $(newSubitemTr).html().replace(/__model__/g, qtde_linhas) + "</tr>");
            <?php }?>

        } else {

            if( $(inputFocus).parents("tr").next("tr[id!=linhaModelo]").length > 0 ){
                var scroll = $(inputFocus).offset();
                $(document).scrollTop(parseInt(scroll.top) - 50);

                $(inputFocus).parents("tr").find("input").bind("verifica", function () {
                    var linha = $(this).parents("tr");

                    var peca_ref = $.trim($(linha).find("input[name^=peca_referencia_]").val());
                    var peca_des = $.trim($(linha).find("input[name^=peca_descricao_]").val());
                    var qtde     = $.trim($(linha).find("input[name^=qtde_]").val());

                    if (qtde.length > 0 && peca_ref.length > 0 && peca_des.length > 0) {
                        $(this).unbind("verifica");
                    }

                }).blur(function () {

                    $(this).trigger("verifica");
                });

            }
        }
    }

    function retorna_produto (retorno) {
        $("#produto").val(retorno.produto);
        $("#produto_referencia").val(retorno.referencia);
        $("#produto_descricao").val(retorno.descricao);

        $("#referencia_duplicar").val(retorno.referencia);
        $("#descricao_duplicar").val(retorno.descricao);

        if (typeof retorno.serie_produto != "undefined") {
            $("#produto_serie").val(retorno.serie_produto);
        }
    }

    function retorna_peca(retorno){
        var posicao = retorno.posicao;
        var campo   = "peca_";

        <?php if(in_array($login_fabrica, array(158))){ ?>
            if(retorno.hasOwnProperty('subitem') && retorno.subitem != ""){
                campo   = "subitem_" + campo;
                posicao = posicao + "_" + retorno.subitem;
            }
        <?php } ?>

        <?php if ($login_fabrica == 171) {?>
        $("#" + campo + "referencia_" + posicao).val(retorno.referencia + " / " + retorno.referencia_fabrica);
        <?php } else { ?>
        $("#" + campo + "referencia_" + posicao).val(retorno.referencia);
        <?php } ?>
        $("#" + campo + "descricao_" + posicao).val(retorno.descricao);
    }
    <?php /*if ($login_fabrica == 175){ ?>
        function fnc_impresssao(produto, ordem_producao) {
            var url = "";
            url = "lbm_cadastro_impressao.php?produto="+produto+"&ordem_producao="+ordem_producao;
            janela = window.open(url, "janela", "toolbar=no, location=yes, status=yes, scrollbars=yes, directories=no, width=920, height=500, top=18, left=0");
            janela.focus();
        }
    <?php }else{*/ ?>
        function fnc_impresssao(produto) {
            var url = "";
            url = "lbm_cadastro_impressao.php?produto="+produto;
            janela = window.open(url, "janela", "toolbar=no, location=yes, status=yes, scrollbars=yes, directories=no, width=920, height=500, top=18, left=0");
            janela.focus();
        }
    <?php // } ?>
    function jsFnVistaExplodidaClick(event){
        console.debug(event);
    }

</script>

<style type="text/css">
    .table-produto div{
        text-align: center !important;
    }

    .container_legenda{
        margin-bottom: 4px;
        border: solid #e2e2e2 1px;
        border-radius: 4px;
        padding: 5px;
    }

    #linhaModelo{
        display: none;
    }

    .titulo_legenda{
        font-size: 12px;
        font-weight: bold;
        text-align: left;
        padding-left: 2px;
        padding-right: 10px;
    }

    .cor_legenda{
        width: 10px !important;
        height: 10px !important;
        padding: 5px !important;
    }

    .valign-center {
        vertical-align: top !important;
    }

    .valign-center div{
        margin-bottom: 0px !important;
    }

    .valign-center span{
        color: #FFFFFF !important;
        margin-bottom: 0px !important;
        margin-left: 20px;
    }

    .pecaAlternativa td{
        background-color: #91C8FF !important;
    }

    .pecaDePara td{
        background-color: #5BB75B !important;
    }

    .pecaInativa td{
        background-color: #F2ED84 !important;
    }

    table.pecas{
        margin: 0 auto !important;
    }

    i{
        cursor: pointer;
    }

    .icon-edit,.icon-remove-sign{
        display: none;
        float:left;
        padding: 3px;
    }

    table tr.duplicate td{
        background-color: #e33b3b !important;
        font-color: green !important;
    }

    table tr.duplicate td input{
        color: #da4f49 !important;
    }

    button.atualiza{
        display: none;
    }
</style>

<? if (count($msg_erro["msg"]) > 0) { ?>
    <div class="alert alert-error">
        <h4><?=implode("<br />", $msg_erro["msg"])?></h4>
    </div>
<? }

if ($_GET['msg']) {
    $msg = array(   "gravar"    => "Itens gravados com sucesso",
                    "exclui"    => traduz("Lista básica excluída com sucesso"),
                    "importar"  => "Itens importados com sucesso",
                    "type"      => "Type duplicado com sucesso",
                    "duplicar"  => traduz("Lista básica duplicada com sucesso")); ?>
    <div class="alert alert-success">
        <h4><?=$msg[$_GET['msg']]?></h4>
    </div>
<? }

if (empty($produto)) { ?>
    <div class="row">
        <b class="obrigatorio pull-right">* <?=traduz('Campos obrigatórios')?></b>
    </div>

    <form name='frm_lbm' METHOD='POST' ACTION='<?=$PHP_SELF?>' align='center' enctype='multipart/form-data' class='form-search form-inline tc_formulario' >
        <div class='titulo_tabela '><?=traduz('Parâmetros de Pesquisa')?></div>
        <br/>
        <input type="hidden" name="produto" id="produto_id" value="<?=$produto?>" />
        <? if (in_array($login_fabrica, array(169,170))) { ?>
            <div class="row-fluid">
                <div class="span2"></div>
                <div class="span5">
                    <div class='control-group <?=(in_array('produto', $msg_erro['campos'])) ? "error" : ""; ?>' >
                        <label class="control-label" for="produto_serie"><?=traduz('Número de Série')?></label>
                        <div class="controls controls-row">
                            <div class="span12 input-append">
                                <input id="produto_serie" name="produto_serie" class="span10" type="text" value="<?= $produto_serie; ?>" maxlength="30" />
                                <span class="add-on" rel="lupa"><i class='icon-search'></i></span>
                                <input type="hidden" name="lupa_config" tipo="produto" parametro="numero_serie" mascara="true" produto-generico="true" />
                            </div>
                        </div>
                    </div>
                </div>
                <div class="span2"></div>
            </div>
        <? } ?>
        <div class='row-fluid'>
            <div class='span2'></div>
            <div class='span4'>
                <div class='control-group <?=(in_array("produto", $msg_erro["campos"])) ? "error" : ""?>'>
                    <label class='control-label' for='produto_referencia'><?=traduz('Ref. Produto')?></label>
                    <div class='controls controls-row'>
                        <div class='span7 input-append'>
                            <h5 class='asteristico'>*</h5>
                            <input type="text" id="produto_referencia" name="produto_referencia" class='span12' maxlength="20" value="<?= $produto_referencia ?>" >
                            <span class='add-on' rel="lupa" ><i class='icon-search'></i></span>
                            <input type="hidden" name="lupa_config" tipo="produto" parametro="referencia" />
                        </div>
                    </div>
                </div>
            </div>
            <div class='span4'>
                <div class='control-group <?=(in_array("produto", $msg_erro["campos"])) ? "error" : ""?>'>
                    <label class='control-label' for='produto_descricao'><?=traduz('Descrição Produto')?></label>
                    <div class='controls controls-row'>
                        <div class='span12 input-append'>
                            <h5 class='asteristico'>*</h5>
                            <input type="text" id="produto_descricao" name="produto_descricao" class='span12' value="<?= $produto_descricao; ?>" >
                            <span class='add-on' rel="lupa" ><i class='icon-search' ></i></span>
                            <input type="hidden" name="lupa_config" tipo="produto" parametro="descricao" />
                        </div>
                    </div>
                </div>
            </div>
            <div class='span2'></div>
        </div>
        <?php /* if ($login_fabrica == 175) { ?>
            <div class="row-fluid">
                <div class="span2"></div>
                <div class="span4">
                    <div class='control-group <?=(in_array('ordem_producao', $msg_erro['campos'])) ? "error" : ""; ?>' >
                        <label class="control-label" for="ordem_producao"><?=traduz('Ordem de Produção')?></label>
                        <div class="controls controls-row">
                            <div class="span10">
                                <h5 class='asteristico'>*</h5>
                                <input id="ordem_producao" numeric="true" name="ordem_producao" class="span10" type="text" value="<?=$ordem_producao; ?>" maxlength="11" />
                            </div>
                        </div>
                    </div>
                </div>
                <div class="span2"></div>
            </div>
        <?php }*/ ?>
        <p><br/>
            <button class='btn' id="btn_acao" type="button"  onclick="submitForm($(this).parents('form'),'pesquisar');"><?=traduz('Pesquisar')?></button>
            <input type='hidden' id="btn_click" name='btn_acao' value='' />
        </p><br/>
    </form>
    <? if ($login_fabrica == 158) { ?>
        <form name='frm_lbm_excel' METHOD='POST' ACTION='<?=$PHP_SELF?>' align='center' enctype='multipart/form-data' class='form-search form-inline tc_formulario' >
            <div class='titulo_tabela '>Realizar Upload de Arquivo para Manutenção de Lista Básica</div>
            <input type='hidden' name='btn_lista' value='listar'>
            <input type='hidden' id="btn_click" name='btn_acao' value='' />
            <br/>
            <span class="label label-important message_upload">Layout de arquivo: Código do produto,Código Peça, Descrição Peça, Quantidade, Ação (INCLUIR / EXCLUIR). Separadas por ponto-e-vírgula (";").</span>
            <br /><br />

            <div class="row-fluid" >
                <div class="span2" ></div>

                <div class="span5" >
                    <div class="control-group <?=(in_array('arquivo', $msg_erro['campos'])) ? 'error' : ''?>" >
                        <label class="control-label" for="arquivo" >Arquivo CSV / TXT</label>

                        <div class="controls controls-row" >
                            <div class="span12" >
                                <h5 class='asteristico'>*</h5>
                                <input type="file" name="arquivo" id="arquivo" class="span12" />
                            </div>
                        </div>

                    </div>
                </div>

                <div class="span2">
                    <div class="controls controls-row" >
                        <div class="span8" >
                            <br />
                            <input type="button" class="btn btn-default" id="btn_acao" onclick="submitForm($(this).parents('form'),'importar_txt');" value="Realizar Upload" />
                        </div>
                    </div>

                </div>

            </div>
        </form>
    <? }

    if (in_array($login_fabrica, [104])) { ?>

        <div class="row row-fluid tc_formulario" style="margin-left: 4px">
            <div class="titulo_tabela">Arquivo excel geral listas básicas</div>
            <br />

                <div class="row-fluid tac">
                    <span id="titulo_linha">Selecione as linhas (Máximo de 4 linhas)</span><br />
                        <?php
                        $sql_linha = "SELECT  tbl_linha.nome,
                                              tbl_linha.linha
                                        FROM    tbl_linha
                                        WHERE   tbl_linha.fabrica = $login_fabrica
                                        AND     tbl_linha.ativo IS TRUE
                                        ORDER BY tbl_linha.nome;";
                        $res_linha = pg_query ($con,$sql_linha);

                        if (pg_num_rows($res_linha) > 0) { ?>
                            <select class='span4' name='linha_excel[]' multiple id="linha_excel">
                                <?php
                                while ($linha_posto = pg_fetch_array($res_linha)) {
                                    $linha_descricao = $linha_posto['nome'];
                                    $linha_id        = $linha_posto['linha'];

                                ?>
                                    <option value="<?= $linha_id ?>"> <?= $linha_descricao ?></option>
                                <?php   
                                } ?>
                                
                            </select>
                        <?php
                        }
                        ?>
                </div>
                <br />
            <div id='gerar_excel' class="btn_excel tac" style="width: 450px;display: none;">
                <input type="hidden" id="jsonPOST" class="excel_geral_listas" value='' />
                <span><img src='imagens/excel.png' /></span>
                <span class="txt">Excel listas básicas</span>
            </div>
            <br />
        </div>
    <?php
    }

} else {
    if (in_array($login_fabrica, array(171/*,175*/)) || (in_array($login_fabrica, array(169,170)) && !empty($produto_serie))) {
        $colspan = 3;
    } else {
        $colspan = 2;
    } ?>
    <div class="container table-produto">
        <table class='table table-striped table-bordered table-hover table-fixed' id="resultado_lista_basica" >
            <thead>
                <tr class='titulo_tabela' >
                    <th colspan="<?= $colspan; ?>"><?=traduz('Lista Básica do Produto')?></th>
                </tr>
                <tr class='subtitulo_tabela' >
                    <? if (in_array($login_fabrica, array(169,170)) && !empty($produto_serie)) { ?>
                        <th><?=traduz('Número de Série')?></th>
                    <? }
                    if ($login_fabrica == 171) {?>
                        <th><?=traduz('Referência Fábrica')?></th>
                    <? } ?>
                    <th><?=traduz('Referência')?></th>
                    <th><?=traduz('Descrição')?></th>
                    <?php /*if ($login_fabrica == 175){ ?>
                    <th><?=traduz('Ordem de Produção')?></th>
                    <?php }*/ ?>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <? if (in_array($login_fabrica, array(169,170)) && !empty($produto_serie)) { ?>
                        <td><?= $produto_serie; ?></td>
                    <? }
                    if ($login_fabrica == 171) {?>
                        <td><?= $referencia_fabrica_produto; ?></td>
                    <? } ?>
                    <td><?= $referencia_produto; ?></td>
                    <td><?= $descricao_produto; ?></td>
                    <?php /*if ($login_fabrica == 175){ ?>
                    <td><?=$ordem_producao?></td>
                    <?php }*/ ?>
                </tr>
            </tbody>
        </table>

            <script>
                $.dataTableLoad({ table: "#resultado_lista_basica" });
            </script>
      
    <div>

    <?php 
        /*if ($login_fabrica == 175 AND $ordem_p === false){
            if (count($result_lista_ordem) > 0){
    ?>
            <form name='frm_lbm' id="frm_lbm_lista"  METHOD='POST' ACTION='<?=$PHP_SELF?>' align='center' class='form-search form-inline' >
                <input type="hidden" id="produto_referencia_lista" name="produto_referencia" class="span12" value="">
                <input type="hidden" id="produto_descricao_lista" name="produto_descricao" class="span12" value="">
                <input id="ordem_producao_lista" name="ordem_producao" class="span10" type="hidden" value="">

                <p><br/>
                    <input type='hidden' id="btn_click" name='btn_acao' value='pesquisar' />
                </p><br/>
            </form>

            <div class="row-fluid">
                <div class="span8">
                    <div class='control-group'>
                        <label class='control-label tal' for='produto_referencia'><?=traduz('Ordem de produção')?></label>
                        <div class='controls controls-row tal'>
                            <div class='span12' style="text-align: left !important;">
                                <input type="text" class='tal' numeric="true" id="nova_ordem_producao" name="nova_ordem_producao" maxlength="11" value="" >
                                <button class='btn tal btn-primary btn-small' style="margin-bottom: 10px;" id="btn_acao" type="button"  onclick="cadastra_nova('<?=$referencia_produto?>', '<?=$descricao_produto?>')"><?=traduz('Nova Ordem Produção')?></button>
                                
                                <button class="btn tal btn-danger btn-small" style="margin-bottom: 10px;" onclick="limpar_dados();" ><?=traduz('Limpar dados')?></button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="container table-ordem">
                <table class='table table-bordered table-fixed' id="resultado_ordens_producao">
                    <thead>
                        <tr class='titulo_tabela' >
                            <th colspan="2"><?=traduz('Ordens de produção cadastradas')?></th>
                        </tr>
                        <tr class='subtitulo_tabela' >
                            <th><?=traduz('Produto')?></th>
                            <th><?=traduz('Ordem de Produção')?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                            foreach ($result_lista_ordem as $key => $value) {
                        ?>
                        <tr>
                            <td><?=$value["referencia"].' - '.$value["descricao"]?></td>
                            <td><p style="color:#0088cc; cursor: pointer;" onclick="pesquisar_ordem('<?=$value['referencia']?>','<?=$value['descricao']?>', '<?=$value['ordem_producao']?>');"><?=$value["ordem_producao"]?></p></td>
                        </tr>
                        <?php 
                            }
                        ?>
                    </tbody>
                </table>
            </div>


            <script>
                $.dataTableLoad({ table: "#resultado_ordens_producao" });
            </script>


    <?php            
            }
            include 'rodape.php';
            exit;
        }*/
    ?>

        <? if (strlen($produto) > 0 && in_array($login_fabrica, array(11,20,46,172))) { ?>
            <div class="btn_excel">
                <span><img src='imagens/excel.png' /></span>
                <span class="txt" onclick="window.open('lbm_cadastro_xls.php?produto=<?=$produto?>');">Gerar Arquivo Excel</span>
            </div> <br />
        <? }

        if (!empty($vista_explodida)) {
            if ($linkVE) { ?>
                <button class='btn btn-info' type="button"  onclick="window.open('<?=$linkVE?>')"><?=traduz('Ver Vista Explodida')?></button>
            <? } else { ?>
                <button class='btn btn-info' type="button"  onclick="window.open('vista_explodida_cadastro.php?comunicado=<?=$vista_explodida?>')"><?=traduz('Ver Vista Explodida')?></button>
            <? 
            } 
            

            /*if ($login_fabrica == 175){
                echo '<button class="btn btn-info" type="button"  onclick="fnc_impresssao('.$produto.','.$ordem_producao.')">'.traduz("Versão para impressão").'</button>';
            }else{*/
                echo '<button class="btn btn-info" type="button"  onclick="fnc_impresssao('.$produto.')">'.traduz("Versão para impressão").'</button>';
            // }

            ?>
            </div>
            <br/>
        <? } else {

            /*if ($login_fabrica == 175){
                echo '<button class="btn btn-info" type="button"  onclick="fnc_impresssao('.$produto.','.$ordem_producao.')">'.traduz("Versão para impressão").'</button>';
            }else{*/
                echo '<button class="btn btn-info" type="button"  onclick="fnc_impresssao('.$produto.')">'.traduz("Versão para impressão").'</button>';
            // }

            echo "</div><br/>";
            echo "<div class='alert'><h4>".traduz("Produto sem vista explodida")."</h4></div>";
        }

    if (!empty($data_alteracao)) {
        echo "<div class='alert'><h4><b>".traduz("Última Atualização")." :</b> ".$login_alt." - ".$data_alteracao."</h4></div>";
    }

    if(isFabrica(46)):
        $model = ModelHolder::init('Produto');
        $explodeViews = $model->getExplodeViewImages($produto);
        $model = ModelHolder::init('ListaBasica');
        $basicLists = $model->find(array('produto'=>$produto));
        $model = ModelHolder::init('Peca');
    ?>
    <div id="explodeView" class="ExplodeView">
        <?php foreach($explodeViews as $index => $explodeView):  ?>
            <img explode-view="<?php echo $index;?>" src="<?php echo $explodeView ?>" />
        <?php endforeach; ?>
        <?php if(empty($explodeViews)): ?>
        <br /><br />
        <?php endif; ?>
        <?php foreach($basicLists as $basicList): ?>
            <?php
                $coords = array('vista'=>'1','x1'=>'0','x2'=>'0','y1'=>'0','y2'=>'0');
                $coordenadas = json_decode($basicList['coordenadas'],true);
                if(!is_array($coordenadas)){
                    $coordenadas = array();
                }
                $coords = array_merge($coords,$coordenadas);
                $basicList['peca'] = $model->select($basicList['peca']);
            ?>
            <input
                type="hidden"
                title="<?php echo $basicList['peca']['descricao'] ?>"
                href="#basic-list-<?php echo $basicList['listaBasica']; ?>"
                basic-list="<?php echo $basicList['listaBasica']; ?>"
                explode-view="<?php echo $coords['vista'] ?>"
                x1="<?php echo $coords['x1'] ?>"
                x2="<?php echo $coords['x2'] ?>"
                y1="<?php echo $coords['y1'] ?>"
                y2="<?php echo $coords['y2'] ?>"
             />
        <?php endforeach; ?>
        </div>
    </div>
    <br />
    <div class="row-fluid">
        <div class="span2"></div>
        <div class="span6">
            <form id="explode-view-add" class="ajax-form" method="POST" action="vista_explodida_ajax.php" enctype="multipart/form-data">
                <input type="hidden" name="action" value="addVista" />
                <input type="hidden" name="produto" value="<?php echo $produto ?>" />
                <input type="hidden" name="fabrica" value="<?php echo $login_fabrica ?>" />
                <input type="file" name="vista" />
                <input class="btn btn-success" type="submit" value="Adicionar Vista" />
            </form>
        </div>
        <div class="span2">
        <form id="explode-view-remove" class="ajax-form" method="POST" action="vista_explodida_ajax.php" >
            <input type="hidden" name="action" value="removeVista" />
            <input type="hidden" name="produto" value="<?php echo $produto ?>" />
            <input type="hidden" name="fabrica" value="<?php echo $login_fabrica ?>" />
            <input type="hidden" name="vista" value="" />
            <input class="btn btn-danger" style="margin-top:35px" type="submit" value="Remover Vista" />
        </form>
        </div>
        <div class="span2"></div>
    </div>
    <br />
    <?php endif; ?>
    <?php


    /*$vistaExplodida = new VistaExplodida($produto);
    $vistas = $vistaExplodida->getVistas();
    //if(!empty($vistas)){
        $element = new VistaExplodidaElement($vistaExplodida);
        $element->addListener('jsFnVistaExplodidaClick');
        echo $element->toHTML();
    //}*/
    if ($fabrica_cadastra_lbm_excel) { ?>

        <form name='frm_lbm_excel' METHOD='POST' ACTION='<?=$PHP_SELF?>' align='center' enctype='multipart/form-data' class='form-search form-inline tc_formulario' >
            <div class='titulo_tabela '>Cadastrar Lista Básica com arquivo Excel (XLS)</div>
            <br/>
            <input type="hidden" name="produto_excel" value="<?=$produto?>" />
            <div class='row-fluid'>
                <div class='span2'></div>
                <div class='span8'>
                    <div class='control-group <?=(in_array("produto", $msg_erro["campos"])) ? "error" : ""?>'>
                        <?if($login_fabrica == 1) { ?>
                        <label class='control-label' for='arquivo'>O Layout de arquivo deve ser igual o que está nessa tela. Não precisa de cabeçalho. <br />Será aceito apenas arquivos com extensão XLS.</label>
                        <? }else{?>
                        <label class='control-label' for='arquivo'>O Layout de arquivo deve ser Produto, Peça e Quantidade</label>
                        <? } ?>
                        <div class='controls controls-row'>
                            <div class='span7 input-append'>
                                <input type="file" id="arquivo" name="arquivo" class='span12' >
                            </div>
                        </div>
                    </div>
                </div>
                <div class='span2'></div>
            </div>
            <p><br/>
                <input type='hidden' value='<?=$produto?>' name='produto_excel'>
                <input type='hidden' name='btn_lista' value='listar'>
                <button class='btn' id="btn_acao" type="button"  onclick="submitForm($(this).parents('form'),'importar');">Importar</button>
                <input type='hidden' id="btn_click" name='btn_acao' value='' />
            </p><br/>
        </form>
<?php
    }
    if ($fabrica_cadastra_lbm_txt) { ?>

        <form name='frm_lbm_excel' METHOD='POST' ACTION='<?=$PHP_SELF?>' align='center' enctype='multipart/form-data' class='form-search form-inline tc_formulario' >
            <div class='titulo_tabela '>Cadastrar Lista Básica com arquivo TXT</div>
            <br/>
            <input type="hidden" name="produto" value="<?=$produto?>" />
            <div class='row-fluid'>
                <div class='span2'></div>
                <div class='span8'>
                    <div class='control-group <?=(in_array("produto", $msg_erro["campos"])) ? "error" : ""?>'>
                        <label class='control-label' for='arquivo'>O Layout de arquivo deve ser igual o que está nessa tela. Não precisa de cabeçalho. <br />Será aceito apenas arquivos com extensão TXT ou CSV (colunas separadas por ";" ).</label>
                        <div class='controls controls-row'>
                            <div class='span7 input-append'>
                                <input type="file" id="arquivo" name="arquivo" class='span12' >
                            </div>
                        </div>
                    </div>
                </div>
                <div class='span2'></div>
            </div>
            <p><br/>
                <input type='hidden' value='<?=$produto?>' name='produto_txt'>
                <input type='hidden' name='btn_lista' value='listar'>
                <button class='btn' id="btn_acao" type="button"  onclick="submitForm($(this).parents('form'),'importar_txt');">Importar</button>
                <input type='hidden' id="btn_click" name='btn_acao' value='' />
            </p><br/>
        </form>
<?php
    }
?>
    </div>
    <center><button class='btn' id="btn_acao" type="button"  onclick="javascript: window.location='<?echo $PHP_SELF?>?'"><?=traduz('Nova Pesquisa')?></button></center>
    <br />

    <div class="alert" style="text-align: right !important">
        <a class="btn btn-link btn-small" rel='shadowbox' href='relatorio_log_alteracao_new.php?parametro=tbl_lista_basica&id=<?php echo $produto; ?>' name="btnAuditorLog"><?=traduz('Visualizar Log Auditor')?></a>
    </div>

    </div>

    <div class="container">
        <table>
            <tr>
            <?php
            foreach ($arrayLegenda as $key => $legenda) {
            ?>

                <td class="cor_legenda" style="background-color:<?=$legenda['cor']?>"></td>
                <td class="titulo_legenda"><?=$legenda['titulo']?></td>

            <?php
            }
            ?>
            </tr>
        </table>
    </div>
    <?php
        /*HD - 4292944*/
        if ($login_fabrica == 120 or $login_fabrica == 201) {
            $table_large_fixed     = "fixed";
            $div_table_padding_ini = " <div style='padding: 9px;'> ";
            $div_table_padding_fim = " </div> ";
        } else {
            $table_large_fixed = "large";
        }
    ?>
    <form name='frm_lbm' METHOD='POST' ACTION='<?=$PHP_SELF?>' align='center' class='form-search form-inline' >
        <?php echo $div_table_padding_ini; ?>
        <table class='table table-striped table-bordered table-hover table-<?=$table_large_fixed;?> pecas'>
            <input type="hidden" name="ordem_producao" value="<?=$ordem_producao?>" />
            <input type="hidden" name="produto" value="<?=$produto?>" />
            <? if (in_array($login_fabrica, array(169,170))) { ?>
                <input type="hidden" name="produto_serie" value="<?= $produto_serie; ?>" />
                <input type="hidden" name="produto_referencia" value="<?= $_REQUEST['produto_referencia']; ?>" />
            <? } ?>
            <input type="hidden" name="qtde_linhas" value="<?=$qtde_linhas?>" />
            <thead>
                <tr class="titulo_coluna">
                    <?php if(isFabrica(46)): ?>
                        <th>Mapear</th>
                    <?php endif; ?>

                    <? if(in_array($login_fabrica, array(50))){ ?>
                    <th>Ativo</th>
                    <? } ?>

                    <? if(in_array($login_fabrica, array(153))){ ?>
                    <th>Acessórios</th>
                    <? } ?>

                    <? if(!in_array($login_fabrica, array(6,175))){ ?>
                    <th><?=traduz('Ordem')?></th>
                    <? } ?>

                    <? if(in_array($login_fabrica, array(6,15,120,201,201))){
                        if ($login_fabrica == 120 or $login_fabrica == 201) {
                            $lbl_in  = "Inicial";
                            $lbl_out = "Final";
                        } else {
                            $lbl_in  = "IN";
                            $lbl_out = "OUT";
                        }?>
                    <th>Série <?=$lbl_in;?></th>
                    <th>Série <?=$lbl_out;?></th>
                    <? } ?>
                    <? if(in_array($login_fabrica, array(15))){ ?>
                    <!-- Removido hd-4430702 -->
                    <!-- <th nowrap> Data Fabricação</th> -->
                    <? } ?>
                    <? if(!in_array($login_fabrica, array(1,3,138,169,170))){ ?>
                    <th><?=traduz('Posição')?></th>
                    <? } ?>

                    <? if(in_array($login_fabrica, array(3))){ ?>
                    <th>Localização</th>
                    <? } ?>

                    <th><?=traduz('Peça')?></th>
                    <th><?=traduz('Descrição')?></th>

                    <? if(in_array($login_fabrica, array(45))){ ?>
                    <th>Preço</th>
                    <? } ?>

                    <? if(in_array($login_fabrica, array(1,51))){ ?>
                    <th>Type</th>
                    <? } ?>

                    <th><?=traduz('Qtde')?></th>
                    <?php if(in_array($login_fabrica, array(195))){ ?>
                        <th><?=traduz('De')?></th>
                        <th><?=traduz('Até')?></th>
                    <?php } ?>

                    <?php if ($usa_versao_produto) { ?>
                    <th>Versão</th>
                    <?php } ?>
                    <?  if($login_fabrica == 1){ ?>
                    <th>Garantia da peça/Meses</th>
                    <th>Status</th>
                    <?  } ?>
                    <? if(in_array($login_fabrica, array(15))){ ?>
                    <th>Somente KIT</th>
                    <th>Única na OS</th>
                    <? } ?>

                    <th><?=traduz('Ação')?></th>

                    <?php
                        if (in_array($login_fabrica,array(6,11,45,46,50,115,116,124,132,172)) OR $imagemPeca) {
                            echo '<th>Imagem</th>';
                        }
                        
                        if (in_array($login_fabrica,array(158))) {
                            echo '<th>Subitem</th>';
                        }
                    ?>
                </tr>
            </thead>
            <tbody>
<?php
    $i = 0;
    if(pg_num_rows($resLista) > 0) {

        $total_itens = pg_num_rows($resLista);

        for($i = 0; $i < $total_itens; $i++){

            $lbm           = pg_fetch_result ($resLista,$i,'lista_basica');
            $ordem         = pg_fetch_result ($resLista,$i,'ordem');
            $posicao       = pg_fetch_result ($resLista,$i,'posicao');
            $serie_inicial = pg_fetch_result ($resLista,$i,'serie_inicial');
            $serie_final   = pg_fetch_result ($resLista,$i,'serie_final');
            $somente_kit   = pg_fetch_result ($resLista,$i,'somente_kit');
            $peca_de_verdade = pg_fetch_result ($resLista,$i,'peca_de_verdade');
            $peca          = pg_fetch_result ($resLista,$i,'referencia');
            $referencia_fabrica  = pg_fetch_result ($resLista,$i,'referencia_fabrica');
            $peca_pai      = pg_fetch_result ($resLista,$i,'referencia_pai');
            $descricao     = pg_fetch_result ($resLista,$i,'descricao');
            $descricao_pai = pg_fetch_result ($resLista,$i,'descricao_pai');
            $type          = pg_fetch_result ($resLista,$i,'type');
            if ($usa_versao_produto) {
                $versao        = pg_fetch_result ($resLista,$i,'type');
            }
            $qtde          = pg_fetch_result ($resLista,$i,'qtde');
            $desgaste      = pg_fetch_result ($resLista,$i,'garantia_diferenciada');
            $ativo         = pg_fetch_result ($resLista,$i,'ativo');
            $xpeca         = pg_fetch_result ($resLista,$i,'peca');
            $xpeca_pai     = pg_fetch_result ($resLista,$i,'peca_pai');
            $xdata_fabricacao   = pg_fetch_result ($resLista,$i,'data_fabricacao');

            if ($login_fabrica == 195) {
                $xparametros_adicionais   = pg_fetch_result ($resLista,$i,'parametros_adicionais');
                $xxparametros_adicionais = json_decode($xparametros_adicionais,1);;
                $explodeDataDe           = explode("/", $xxparametros_adicionais["data_de"]);
                $explodeDataAte          = explode("/", $xxparametros_adicionais["data_ate"]);

                $data_de   = (strlen($explodeDataDe[0]) > 2) ? date("d/m/Y" ,strtotime($xxparametros_adicionais["data_de"])) : $xxparametros_adicionais["data_de"];
                $data_ate  = (strlen($explodeDataAte[0]) > 2) ? date("d/m/Y" ,strtotime($xxparametros_adicionais["data_ate"])) : $xxparametros_adicionais["data_ate"];
            }

            if ($login_fabrica == 45) {
                $preco = pg_fetch_result ($resLista,$i,'preco');
                $preco = number_format($preco, 2);
                $preco = str_replace(".",",",$preco);
            }

            $class = "";

            $sql = "SELECT  tbl_peca_alternativa.para
                    FROM    tbl_peca_alternativa
                    WHERE   tbl_peca_alternativa.para    = '$peca'
                    AND     tbl_peca_alternativa.fabrica = $login_fabrica";
            $res1 = pg_query ($con,$sql);

            if (pg_num_rows($res1) > 0) $class = "pecaAlternativa";

            $sql = "SELECT  tbl_depara.de,
                            tbl_peca.descricao,
                            tbl_peca.referencia
                    FROM    tbl_depara
                    JOIN    tbl_peca on tbl_peca.referencia = tbl_depara.de and tbl_peca.fabrica = $login_fabrica
                    WHERE   tbl_depara.para    = '$peca'
                    AND     tbl_depara.fabrica = $login_fabrica;";

            $res1 = pg_query ($con,$sql);

            if (pg_num_rows($res1) > 0) {
                $xpeca_de            = pg_fetch_result ($res1,0,'de');
                $xreferencia_peca_de = pg_fetch_result ($res1,0,'referencia');
                $xdescricao_peca_de  = pg_fetch_result ($res1,0,'descricao');
                $class = "pecaDePara";
            }else{
                $xpeca_de            = "";
                $xreferencia_peca_de = "";
                $xdescricao_peca_de  = "";
            }

            if($login_fabrica == 14 and $ativo == 'f' and strlen($ativo) > 0) {
                $class = "pecaInativa";
            }

            $tamanho = (in_array($login_fabrica, array(6, 15, 120,201))) ? "inptc7":"inptc2";
            if(strlen($ativo) == 0) $ativo = "";
            
            if ($login_fabrica == 1) {
                $aux_sql = "SELECT lista_basica FROM tbl_lista_basica WHERE produto = $produto AND ordem = $ordem AND lista_basica <> $lbm";
                $aux_res = pg_query($con, $aux_sql);

                if (pg_num_rows($aux_res) > 0) {
                    $class = "duplicate";
                }
            }
?>
                <tr class="<?=$class?>">
                <? if(in_array($login_fabrica, array(153))){
                    if($type == 'acessorio'){
                        $checked = " checked ";
                    }else{
                        $checked = "";
                    }
                ?>
                    <td class="valign-center tac">
                        <input type="checkbox" id="tipo_<?=$i?>" name="tipo_<?=$i?>" class='' <?=$checked?> value="acessorio" >
                    </td>
                <?php }?>

                    <?php if(isFabrica(46)): ?>
                        <td class="tac" >
                            <a href="#vistaExplodidaMap" class="ExplodeViewMap" explode-view="explodeView" basic-list="<?= $lbm; ?>">
                                <span class="icon-move">
                                </span>
                            </a>
                        </td>
                    <?php endif; ?>

                    <? if(in_array($login_fabrica, array(50))){
                        $checked_ativo = ($ativo == 't') ? "CHECKED" : '' ;
                    ?>
                    <td class="valign-center tac">
                        <label class="checkbox" for="somente_kit_<?=$i?>">
                            <input type='checkbox' name='ativo_<?=$i?>' id='ativo_<?=$i?>' value='t' <?=$checked_ativo?>/>
                        </label>
                    </td>
                    <? } ?>

                    <? if(!in_array($login_fabrica, array(6,175))){ ?>
                    <td class="valign-center tac">
                        <input type="text" id="ordem_<?=$i?>" name="ordem_<?=$i?>" class='inptc2' value="<? echo $ordem ?>" style="width:35px;">
                    </td>
                    <? } ?>

                    <? if(in_array($login_fabrica, array(6,15,120,201))){ 
                        $maxlength = ($login_fabrica == 15) ? "maxlength = '8'" : ""; 
                    ?>
                    <td class="valign-center tac">
                        <input type="text" id="serie_inicial_<?=$i?>" name="serie_inicial_<?=$i?>" class='<?=$tamanho?>' <?=$maxlength?> value="<? echo $serie_inicial ?>" >
                    </td>
                    <td class="valign-center tac">
                        <input type="text" id="serie_final_<?=$i?>" name="serie_final_<?=$i?>" class='<?=$tamanho?>' <?=$maxlength?> value="<? echo $serie_final ?>" >
                    </td>
                    <? } ?>
                    <?php if(in_array($login_fabrica, array(15))){ ?>
                    <!-- Removido hd-4430702 -->
                    <!-- <td class="valign-center tac" nowrap>
                        <input type="text" id="data_fabricacao_<?=$i?>" name="data_fabricacao_<?=$i?>" class="data_fabricacao" style="width: 100% " value="<? echo $xdata_fabricacao; ?>" >
                    </td> -->
                    <?php } ?>

                    <? if(!in_array($login_fabrica, array(1,3,138,169,170))){ ?>
                    <td class="valign-center tac">
                        <input type="text" id="posicao_<?=$i?>" name="posicao_<?=$i?>" class='inptc2' value="<? echo $posicao ?>" >
                    </td>
                    <? } ?>

                    <? if ($login_fabrica == 5) { ?>
                        <td class="valign-center tac">
                            <div class='control-group <?=(in_array("peca_pai", $msg_erro["campos"])) ? "error" : ""?>'>
                                <div class='controls controls-row'>
                                <div class='input-append'>
                                    <input type='text' name='peca_pai_<?=$i?>'  class='frm' value='<?=$peca_pai?>' class='span2 inp-peca' maxlength='20'>
                                    <span class='add-on' rel="lupa"><i class='icon-search'></i></span>
                                    <input type="hidden" name="lupa_config" tipo="peca" posicao="<?=$i?>" parametro="referencia" />
                                </div>
                            </div>
                        </div>
                            <? if($peca){ ?>
                            <span><?=$peca?></span>
                            <? } ?>
                        </td>

                        <td class="valign-center tac">
                            <div class='input-append'>
                                <input type='text' name='descricao_pai_<?=$i?>'  class='frm' value='<?=$descricao_pai?>' class='span5 inp-descricao' maxlength='50'>
                                <span class='add-on' rel="lupa"><i class='icon-search'></i></span>
                                <input type="hidden" name="lupa_config" tipo="peca" posicao="<?=$i?>" parametro="descricao" />
                            </div>
                            <? if($descricao){ ?>
                            <span><?=$descricao?></span>
                            <? } ?>
                        </td>

                    <? } ?>
                    <?php 
                        $pecaReferenciaFabrica = "";
                        if ($login_fabrica == 171 && strlen($produto) > 0 &&  strlen($peca) > 0) {
                            $pecaReferenciaFabrica = " / ".$referencia_fabrica;
                        }

                        $descricao = mb_detect_encoding($descricao, 'UTF-8', true) ? (utf8_decode($descricao)) : $descricao;
                    ?>
                    <td class="valign-center">
                        <input type='hidden' value="<?=$lbm?>" name="lbm_<?=$i?>" />
                        <div class='input-append'>
                            <input type="text" id="peca_referencia_<?=$i?>" name="peca_referencia_<?=$i?>" class='span2 inp-peca' maxlength="20" value="<? echo $peca . $pecaReferenciaFabrica;?>" >
                            <span class='add-on' rel="lupa"><i class='icon-search'></i></span>
                            <input type="hidden" name="lupa_config" tipo="peca" posicao="<?=$i?>" parametro="referencia" <?= ($login_fabrica == 30) ? "pesquisa_produto_acabado='true'" : ""; ?> />
                        </div>
                        <? if($xpeca_de){ ?><br />
                        <span><?=$xpeca_de?></span>
                        <? } ?>
                    </td>
                    <td class="valign-center">
                        <div class='input-append'>
                            <input type="text" id="peca_descricao_<?=$i?>" name="peca_descricao_<?=$i?>" class='span3 inp-descricao' value="<? echo $descricao ?>" >
                            <span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
                            <input type="hidden" name="lupa_config" tipo="peca" posicao="<?=$i?>" parametro="descricao" <?php echo ($login_fabrica == 30) ? "pesquisa_produto_acabado='true'" : ""; ?> />
                        </div>
                        <? if($xpeca_de){ ?> <br />
                        <span><?=$xdescricao_peca_de?></span>
                        <? } ?>
                    </td>

                    <? if(in_array($login_fabrica, array(45))){ ?>
                    <td>
                        <input type='text' name='preco_<?=$i?>' value='<?=$preco?>' class="inptc6" readonly></td>
                    </td>
                    <? } ?>

                    <? if(in_array($login_fabrica, array(1))) {
                        try{
                        GeraComboType::makeComboType($parametrosAdicionaisObject, $type, null, array("index"=>$i) );
                    ?>
                            <td class="valign-center tac">
                        <?=GeraComboType::getElement();?>
                            </td>
                    <?  }catch(Exception $ex){
                          echo  $ex->getMessage();
                        }
                    } ?>
                    <td class="valign-center tac">
                        <input type="text" id="qtde_<?=$i?>" name="qtde_<?=$i?>" class='inptc1 inp-qtd' <?php echo (in_array($login_fabrica, array(140))) ? "style='width: 50px;'" : ""; ?> value="<? echo ($qtde < 1 ) ? number_format($qtde,2,",",".") : $qtde; ?>" >
                    </td>
                    <?php if(in_array($login_fabrica, array(195))){ ?>
                        <td class="valign-center">
                            <div class='input-append'>
                                <input type="text" id="data_de_<?=$i?>" name="data_de_<?=$i?>" class='span3 data_pik' value="<? echo $data_de ?>" >
                                <span class='add-on'><i class='icon-calendar' ></i></span>
                            </div>
                        </td>
                        <td class="valign-center">
                            <div class='input-append'>
                                <input type="text" id="data_ate_<?=$i?>" name="data_ate_<?=$i?>" class='span3 data_pik' value="<? echo $data_ate ?>" >
                                <span class='add-on'><i class='icon-calendar' ></i></span>
                            </div>
                        </td>
                    <?php }?>

					<?php if(in_array($login_fabrica, array(1))){
                            /* HD-4217476 */
                            $aux_sql = "SELECT garantia_peca FROM tbl_lista_basica WHERE lista_basica = $lbm LIMIT 1";
                            $aux_res = pg_query($con, $aux_sql);
                            $garantia_meses = pg_fetch_result($aux_res, 0, 'garantia_peca');

                            if (empty($garantia_meses)) {
                                $aux_sql = "SELECT garantia_diferenciada FROM tbl_peca WHERE peca = $peca_de_verdade AND fabrica = $login_fabrica LIMIT 1";
                                $aux_res = pg_query($con, $aux_sql);
                                $garantia_meses = pg_fetch_result($aux_res, 0, 'garantia_diferenciada');

                                if (empty($garantia_meses)) {
                                    $aux_sql = "SELECT garantia FROM tbl_produto WHERE produto = $produto AND fabrica_i = $login_fabrica LIMIT 1";
                                    $aux_res = pg_query($con, $aux_sql);
                                    $garantia_meses = pg_fetch_result($aux_res, 0, 'garantia');
                                }
                            }

                        /*HD-4074490*/
                        if (strlen($peca_de_verdade) > 0) {
                            $aux_sql = "SELECT informacoes FROM tbl_peca WHERE peca = $peca_de_verdade AND fabrica = $login_fabrica LIMIT 1";
                            $aux_res = pg_query($con, $aux_sql);

                            if (pg_num_rows($aux_res) > 0) {
                                $aux_ativo = pg_fetch_result($aux_res, 0, 0);
                            }

                            if (strlen($aux_ativo) > 0) {
                                $label_ativo = strtoupper($aux_ativo);
                            } else {
                                $label_ativo = "ATIVO";
                            }
                        }
						?>
					<td class="valign-center tac">
						<input type="text" id="desgaste_<?=$i?>" name="desgaste_<?=$i?>" class='inptc1 inp-qtd' style='width: 50px;' value="<?=$garantia_meses?>" >
					</td>
                    <td class="valign-center tac"><input type="text" name="label_ativo_<?=$i?>" style='width: 95px; cursor: not-allowed;' value="<?=$label_ativo;?>" readonly></td>
					<?php } ?>
					<? if(in_array($login_fabrica, array(15))){
						$checked_kit = ($somente_kit == 't') ? "CHECKED" : '' ;
						$checked_unica_os = ($type == 'UNICA') ? "CHECKED" : '' ;
					?>
					<td class="valign-center tac">
						<label class="checkbox" for="somente_kit_<?=$i?>">
							<input type='checkbox' name='somente_kit_<?=$i?>' id='somente_kit_<?=$i?>' value='t' <?=$checked_kit?>/>
						</label>
					</td>
					<td class="valign-center tac">
						<label class="checkbox" for="unica_os_<?=$i?>">
							<input type='checkbox' name='unica_os_<?=$i?>' id='unica_os_<?=$i?>' class='unica_os' rel='<?=$i?>'   value='UNICA'  <?=$checked_unica_os?> />
						</label>
					</td>
					<? } ?>

					<?php if ($usa_versao_produto) { ?>
					<td class="valign-center tac">
						<input type="text" id="versao_<?=$i?>" name="versao_<?=$i?>" maxlength="10" class=<?php echo "span1"; ?> value="<?=$versao?>" >
					</td>
					<?php } ?>
					<td class="valign-center tac" nowrap>
						<button class='btn btn-danger btn-small' id="remove_linha_<?=$i?>" rel="<?=$lbm?>" type="button" ><?=traduz('Excluir')?></button>
						<button class='btn btn-small atualiza' id="gravar_linha_<?=$i?>" type="button" ><?=traduz('Gravar')?></button>
					</td>
					<?php
					if (in_array($login_fabrica,array(6,11,45,46,50,115,116,124,132,172)) OR $imagemPeca) {

	            	$xpecas = $tDocs->getDocumentsByRef($xpeca, "peca");
		            if (!empty($xpecas->attachListInfo)) {

						$a = 1;
						foreach ($xpecas->attachListInfo as $kFoto => $vFoto) {
						    $fotoPeca = $vFoto["link"];
						    if ($a == 1){break;}
						}
						echo "<td class='valign-center tac'>";
						echo "<img src='$fotoPeca' width='60'>";
						echo "</td>";
		            } else {


							$caminho = $PHP_SELF."/".$login_fabrica;
							$teste = explode('/', $PHP_SELF, -1);
							$caminho = "../imagens_pecas/$login_fabrica";
							$diretorio_verifica=$caminho."/pequena/";						
							if(is_dir($diretorio_verifica) == true){
								$contador=0;
								if ($dh = opendir($caminho."/pequena/")) {
									while (false !== ($filename = readdir($dh))) {
										if(strlen($xpeca) > 0) {
											if($contador == 1) break;
											if (strpos($filename,$xpeca) !== false){
												$po = strlen($xpeca);
												if(substr($filename, 0,$po)==$xpeca){
													$contador++;
													echo "<td class='valign-center tac'>";
													echo "<img src='$caminho/pequena/$filename' width='60'>";
													echo "</td>";
												}
											}
										}
									}

									if ($contador == 0 AND strlen($peca) > 0) {
										if ($dh = opendir($caminho."/pequena/")) {
											while (false !== ($filename = readdir($dh))) {
												if ($contador == 1) break;
												if (strpos($filename,$peca) !== false) {
													$po = strlen($peca);
													if (substr($filename, 0,$po) == $peca) {
														$contador++;
														echo "<td class='valign-center tac'>";
														echo "<img src='$caminho/pequena/$filename' width='60'>";
														echo "</td>";
													}
												}
											}
										}
									}
									if ($contador == 0 AND strlen($peca) > 0) {
										echo "<td class='valign-center tac'><img src='plugins/bootstrap/img/no_image.png'></td>";
									}
								}else{
									echo "<td class='valign-center tac'><img src='plugins/bootstrap/img/no_image.png'></td>";
								}
							}else{
								echo "<td class='valign-center tac'><img src='plugins/bootstrap/img/no_image.png'></td>";
							}
						}
					}

                    if(in_array($login_fabrica, array(158))){
                        ?>
                        <td class='valign-center tac'>
                            <button type='button' class='btn btn-info btn-small' id='sublista_linha_<?=$i?>' >
                                <img src='imagens/icon_collapse_white.png' id="sublista_img_< ?=$i?>">
                            </button>
                        </td>
                        <?php
                    }
				?>
				</tr>
            <?php
            if(in_array($login_fabrica, array(158))){
                $modelo = false;
                ?>
                <tr class="tabela_subitem_<?=$i?>" style="display: none;">
                    <td colspan="7">
                        <?php include "lbm_table_subitem.php"; ?>
                    </td>
                </tr>
                <?php
            }
        }
    }
?>
                <tr>
                    <? if(in_array($login_fabrica, array(153))){?>
                        <td class="valign-center tac">
                            <input type="checkbox" id="tipo_<?=$i?>" name="tipo_<?=$i?>" class='' value="acessorio" >
                        </td>
                    <?php }?>

                    <?php if(isFabrica(46)): ?>
                        <td>
                        </td>
                    <?php endif; ?>
                    <? if(in_array($login_fabrica, array(50))){ ?>
                    <td class="valign-center tac">
                        <label class="checkbox" for="somente_kit_<?=$i?>">
                            <input type='checkbox' name='ativo_<?=$i?>' id='ativo_<?=$i?>' value='t' />
                        </label>
                    </td>
                    <? } ?>
                    <? if(!in_array($login_fabrica, array(6,175))){ ?>
                    <td class="valign-center tac">
                        <input type="text" id="ordem_<?=$i?>" name="ordem_<?=$i?>" class='inptc2' value="" style="width:35px;">
                    </td>
                    <? } ?>

                    <? if(in_array($login_fabrica, array(6,15,120,201))){ 
                        $maxlength = ($login_fabrica == 15) ? "maxlength = '8'" : ""; 
                    ?>
                    <td class="valign-center tac">
                        <input type="text" id="serie_inicial_<?=$i?>" name="serie_inicial_<?=$i?>" <?=$maxlength?> class='inptc2' value="" >
                    </td>
                    <td class="valign-center tac">
                        <input type="text" id="serie_final_<?=$i?>" name="serie_final_<?=$i?>" <?=$maxlength?> class='inptc2' value="" >
                    </td>
                    <? } ?>

                    <? if(in_array($login_fabrica, array(15))){ ?>
                        <!-- Removido hd-4430702 -->
                    <!-- <td class="valign-center tac">
                        <input type="text" class="data_fabricacao" id="data_fabricacao_<?=$i?>" name="data_fabricacao_<?=$i?>" style="width: 100% " value="" >
                    </td> -->
                    <? } ?>

                    <? if(!in_array($login_fabrica, array(1,3,138,169,170))){ ?>
                    <td class="valign-center tac">
                        <input type="text" id="posicao_<?=$i?>" name="posicao_<?=$i?>" class='inptc2' value="" >
                    </td>
                    <? } ?>
                    <td class="valign-center">
                        <input type='hidden' value="" name="lbm_<?=$i?>" />
                        <div class='input-append'>
                            <input type="text" id="peca_referencia_<?=$i?>" name="peca_referencia_<?=$i?>" class='span2' maxlength="20" value="" >
                            <span class='add-on' rel="lupa"><i class='icon-search'></i></span>
                            <input type="hidden" name="lupa_config" tipo="peca" posicao="<?=$i?>" parametro="referencia" <?php echo ($login_fabrica == 30) ? "pesquisa_produto_acabado='true'" : ""; ?> />
                        </div>
                    </td>
                    <td class="valign-center">
                        <div class='input-append'>
                            <input type="text" id="peca_descricao_<?=$i?>" name="peca_descricao_<?=$i?>" class='span3' value="" >
                            <span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
                            <input type="hidden" name="lupa_config" tipo="peca" posicao="<?=$i?>" parametro="descricao" <?php echo ($login_fabrica == 30) ? "pesquisa_produto_acabado='true'" : ""; ?> />
                        </div>
                    </td>

                    <? if(in_array($login_fabrica, array(45))){ ?>
                    <td>
                        <input type='text' name='preco_<?=$i?>' value='' class="inptc6" readonly></td>
                    </td>
                    <? } ?>

                    <? if(in_array($login_fabrica, array(1))) {

                        try{
                        GeraComboType::makeComboType($parametrosAdicionaisObject, $type, null,array("index"=>$i) ); ?>
                    <td class="valign-center tac">
                        <?=GeraComboType::getElement();?>
                    </td>
                    <?  }catch(Exception $ex){
                          echo  $ex->getMessage();
                        }
                    }?>
                    <td class="valign-center tac">
                        <input type="text" id="qtde_<?=$i?>" name="qtde_<?=$i?>" class=<?php echo (in_array($login_fabrica, array(140))) ? "inptc6" : "inptc1"; ?> value="" >
                    </td>
                    <?php if(in_array($login_fabrica, array(195))){ ?>
                        <td class="valign-center">
                            <div class='input-append'>
                                <input type="text" class="data_pik" id="data_de_<?=$i?>" name="data_de_<?=$i?>" value="" >
                                <span class='add-on'><i class='icon-calendar' ></i></span>
                            </div>
                        </td>
                        <td class="valign-center">
                            <div class='input-append'>
                                <input type="text" class="data_pik" id="data_ate_<?=$i?>" name="data_ate_<?=$i?>" value="" >
                                <span class='add-on'><i class='icon-calendar' ></i></span>
                            </div>
                        </td>
                    <?php }?>
                    <? if(in_array($login_fabrica, array(1))){?>
                    <td class="valign-center tac">
                        <input type="text" id="desgaste_<?=$i?>" name="desgaste_<?=$i?>" class='inptc1 inp-qtd' style='width: 50px;' value="" >
                    </td>
                    <?
                    }
                    ?>
                    <? if(in_array($login_fabrica, array(15))){ ?>
                    <td class="valign-center tac">
                        <label class="checkbox" for="somente_kit_<?=$i?>">
                            <input type='checkbox' name='somente_kit_<?=$i?>' id='somente_kit_<?=$i?>' value='t' />
                        </label>
                    </td>
                    <td class="valign-center tac">
                        <label class="checkbox" for="unica_os_<?=$i?>">
                            <input type='checkbox' name='unica_os_<?=$i?>' id='unica_os_<?=$i?>' class='unica_os' rel='<?=$i?>' value='UNICA' />
                        </label>
                    </td>
                    <? } ?>

                    <?php if ($usa_versao_produto) { ?>
                    <td class="valign-center tac">
                        <input type="text" id="versao_<?=$i?>" name="versao_<?=$i?>" maxlength="10" class=<?php echo "span1"; ?> value="" >
                    </td>
                    <?php }
                        if ($login_fabrica == 1) $aux_colspan = " colspan='2' ";
                    ?>
                    <td class="valign-center tac" <?=$aux_colspan;?> >
                        <button class='btn btn-small' id="gravar_linha_<?=$i?>" rel="" type="button" ><?=traduz('Gravar')?></button>
                        <button class='btn btn-danger btn-small' id="remove_linha_<?=$i?>" type="button" style="display:none;"><?=traduz('Excluir')?></button>
                    </td>
                    <?php
                        //if ($login_fabrica == 6 or $login_fabrica == 11 or $login_fabrica == 45 or $login_fabrica == 50) {
                        if (in_array($login_fabrica,array(6,11,45,46,50,115,116,124,132,172))) {
                            echo '<td class="valign-center tac"><img src="plugins/bootstrap/img/no_image.png" ></td>';
                        }

                        if(in_array($login_fabrica, array(158))){
                            ?>
                            <td class='valign-center tac'>
                                <button type='button' class='btn btn-info btn-small' id='sublista_linha_<?=$i?>' style="display: none;">
                                    <img src='imagens/icon_collapse_white.png' id="sublista_img_< ?=$i?>">
                                </button>
                            </td>
                            <?php
                        }
                    ?>
                </tr>
                <?php
                    if(in_array($login_fabrica, array(158))){
                        $xpeca = "";
                        $modelo = false;
                        ?>
                        <tr class="tabela_subitem_<?=$i?>" style="display: none;">
                            <td colspan="7">
                                <?php include "lbm_table_subitem.php"; ?>
                            </td>
                        </tr>
                        <?php
                    }
                ?>

                <tr id="linhaModelo">
                    <? if(in_array($login_fabrica, array(153))){?>
                        <td class="valign-center tac">
                            <input type="checkbox" id="tipo___model__" name="tipo___model__" class='' value="acessorio" >
                        </td>
                    <?php }?>

                    <?php if(isFabrica(46)): ?>
                        <td class="tac">
                            <span class="icon-move">
                            </span>
                        </td>
                    <?php endif; ?>

                    <? if(in_array($login_fabrica, array(50))){ ?>
                    <td class="valign-center tac">
                        <label class="checkbox" for="somente_kit___model__">
                            <input type='checkbox' name='ativo___model__>' id='ativo___model__' value='t' />
                        </label>
                    </td>
                    <? } ?>

                    <? if(!in_array($login_fabrica, array(6,175))){ ?>
                    <td class="valign-center tac">
                        <input type="text" id="ordem___model__" name="ordem___model__" class='inptc2' value="" style="width:35px;">
                    </td>
                    <? } ?>

                    <? if(in_array($login_fabrica, array(6,15,120,201))){ ?>
                    <td class="valign-center tac">
                        <input type="text" id="serie_inicial___model__" name="serie_inicial___model__" class='inptc2' value="" >
                    </td>
                    <td class="valign-center tac">
                        <input type="text" id="serie_final___model__" name="serie_final___model__" class='inptc2' value="" >
                    </td>
                    <? } ?>

                    <? if(in_array($login_fabrica, array(15))){ ?>
                    <!-- Removido hd-4430702 -->
                    <!-- <td class="valign-center tac">
                        <input type="text" id="data_fabricacao___model__" name="data_fabricacao___model__" class="data_fabricacao" style="width: 100% " value="" >
                    </td> -->
                    <? } ?>

                    <? if(!in_array($login_fabrica, array(1,3,138,169,170))){ ?>
                    <td class="valign-center tac">
                        <input type="text" id="posicao___model__" name="posicao___model__" class='inptc2' value="" >
                    </td>
                    <? } ?>
                    <td class="valign-center">
                        <input type='hidden' value="" name="lbm___model__" />
                        <div class='control-group <?=(in_array("peca", $msg_erro["campos"])) ? "error" : ""?>'>
                            <div class='controls controls-row'>
                                <div class='input-append'>
                                    <input type="text" id="peca_referencia___model__" name="peca_referencia___model__" class='span2' maxlength="20" value="" >
                                    <span class='add-on' rel="lupa"><i class='icon-search'></i></span>
                                    <input type="hidden" name="lupa_config" tipo="peca" posicao="__model__" parametro="referencia" <?php echo ($login_fabrica == 30) ? "pesquisa_produto_acabado='true'" : ""; ?> />
                                </div>
                            </div>
                        </div>
                    </td>
                    <td class="valign-center">
                        <div class='control-group <?=(in_array("peca", $msg_erro["campos"])) ? "error" : ""?>'>
                            <div class='controls controls-row'>
                                <div class='input-append'>
                                    <input type="text" id="peca_descricao___model__" name="peca_descricao___model__" class='span3' value="" >
                                    <span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
                                    <input type="hidden" name="lupa_config" tipo="peca" posicao="__model__" parametro="descricao" <?php echo ($login_fabrica == 30) ? "pesquisa_produto_acabado='true'" : ""; ?> />
                                </div>
                            </div>
                        </div>
                    </td>

                    <? if(in_array($login_fabrica, array(45))){ ?>
                    <td>
                        <input type='text' name='preco___model__' value='' class="inptc6" readonly></td>
                    </td>
                    <? } ?>

                    <? if(in_array($login_fabrica, array(1,51))){ ?>
                    <td class="valign-center tac">
                        <select name="type___model__" id="type___model__" class="inptc7" style="width:220px;">
                            <option value=""></option>
                            <option value='Tipo 1'  <?php if ($type == 'Tipo 1')   echo "selected"; ?>  >Tipo 1</option>
                            <option value='Tipo 2'  <?php if ($type == 'Tipo 2')   echo "selected"; ?>  >Tipo 2</option>
                            <option value='Tipo 3'  <?php if ($type == 'Tipo 3')   echo "selected"; ?>  >Tipo 3</option>
                            <option value='Tipo 4'  <?php if ($type == 'Tipo 4')   echo "selected"; ?>  >Tipo 4</option>
                            <option value='Tipo 5'  <?php if ($type == 'Tipo 5')   echo "selected"; ?>  >Tipo 5</option>
                            <option value='Tipo 6'  <?php if ($type == 'Tipo 6')   echo "selected"; ?>  >Tipo 6</option>
                            <option value='Tipo 7'  <?php if ($type == 'Tipo 7')   echo "selected"; ?>  >Tipo 7</option>
                            <option value='Tipo 8'  <?php if ($type == 'Tipo 8')   echo "selected"; ?>  >Tipo 8</option>
                            <option value='Tipo 9'  <?php if ($type == 'Tipo 9')   echo "selected"; ?>  >Tipo 9</option>
                            <option value='Tipo 10' <?php if ($type == 'Tipo 10')  echo "selected"; ?>  >Tipo 10</option>
                        </select>
                    </td>
                    <? } ?>
                    <td class="valign-center tac">
                        <input type="text" id="qtde___model__" name="qtde___model__" class='inptc1' value="" >
                    </td>
                    <?php if(in_array($login_fabrica, array(195))){ ?>
                        <td class="valign-center">
                            <div class='input-append'>
                                <input type="text" id="data_de___model__" name="data_de___model__" class=' data_pik' value="" >
                                <span class='add-on'><i class='icon-calendar' ></i></span>
                            </div>
                        </td>
                        <td class="valign-center">
                            <div class='input-append'>
                                <input type="text" id="data_ate___model__" name="data_ate___model__" class=' data_pik' value="" >
                                <span class='add-on'><i class='icon-calendar' ></i></span>
                            </div>
                        </td>
                    <?php }?>

<? if(in_array($login_fabrica, array(1))){
?>
                    <td class="valign-center tac">
                        <input type="text" id="desgaste___model__" name="desgaste___model__" class='inptc1' value="" style="width:50px;"/>
                    </td>
<?
}
?>
                    <? if(in_array($login_fabrica, array(15))){ ?>
                    <td class="valign-center tac">
                        <label class="checkbox" for="somente_kit___model__">
                            <input type='checkbox' name='somente_kit___model__' id='somente_kit___model__' value='t' />
                        </label>
                    </td>
                    <td class="valign-center tac">
                        <label class="checkbox" for="unica_os___model__">
                            <input type='checkbox' name='unica_os___model__' id='unica_os___model__' class='unica_os' rel='__model__' value='UNICA' />
                        </label>
                    </td>
                    <? } ?>

                    <?php if ($usa_versao_produto) { ?>
                    <td class="valign-center tac">
                        <input type="text" id="versao_<?=$i?>" name="versao_<?=$i?>" maxlength="10" class=<?php echo "inptc4"; ?> value="" >
                    </td>
                    <?php }
                        if ($login_fabrica == 1) $aux_colspan = " colspan='2' ";
                    ?>
                    <td class="valign-center tac" <?=$aux_colspan;?> >
                        <button class='btn btn-small' id="gravar_linha___model__" rel="" type="button" ><?=traduz('Gravar')?></button>
                        <button class='btn btn-danger btn-small' id="remove_linha___model__" type="button" style="display:none;"><?=traduz('Excluir')?></button>
                    </td>

                    <?php
                        //if ($login_fabrica == 6 or $login_fabrica == 11 or $login_fabrica == 45 or $login_fabrica == 50) {
                        if (in_array($login_fabrica,array(6,11,45,46,50,115,116,124,132,172))) {
                            echo '<td class="valign-center tac"><img src="plugins/bootstrap/img/no_image.png" ></td>';
                        }

                        if(in_array($login_fabrica, array(158))){
                            ?>
                            <td class='valign-center tac'>
                                <button type='button' class='btn btn-info btn-small' id='sublista_linha___model__' style="display: none;">
                                    <img src='imagens/icon_collapse_white.png' id="sublista_img___model__">
                                </button>
                            </td>
                            <?php
                        }
                    ?>
                </tr>
                <?php
                    if(in_array($login_fabrica, array(158))){
                        $xpeca  = "";
                        $modelo = true;
                        ?>
                        <tr class="tabela_subitem___model__" id="tableSubItemModelo" style="display: none;">
                            <td colspan="7">
                                <?php include "lbm_table_subitem.php"; ?>
                            </td>
                        </tr>
                        <?php
                    }
                ?>
                </tr>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="100%" class="tac">
                        <?
                        if($total_itens > 0){
                            /*if ($login_fabrica == 175) {
                            ?>
                                <button 
                                    class='btn' 
                                    type="button" 
                                    onclick="
                                        if (confirm('<?=traduz("Deseja realmente excluir todos os itens desta Lista Básica ?")?>') == true) { 
                                            window.location = '<?= $PHP_SELF; ?>?<?= 'produto='.$produto.'&ordem_producao='.$ordem_producao; ?>&acao=excluir';
                                        }
                                    "
                                >
                                    <?=traduz('Excluir Lista Básica')?>
                                </button>
                                <input type='hidden' id="btn_click" name='btn_acao' value='' />
                            <?php    
                            } else {*/
                            ?>
                                <button class='btn' type="button" onclick="javascript: if (confirm('<?=traduz("Deseja realmente excluir todos os itens desta Lista Básica ?")?>') == true) { window.location = '<?= $PHP_SELF; ?>?<?= (in_array($login_fabrica, array(169,170))) ? 'produto='.$produto.'&serie='.$produto_serie : 'produto='.$produto; ?>&acao=excluir'}"><?=traduz('Excluir Lista Básica')?></button>
                                <input type='hidden' id="btn_click" name='btn_acao' value='' />
                            <?php
                            //}
                        }else{ ?>
                            <div class='alert'><h4><?=traduz('Produto sem lista básica')?></h4></div>
                        <? } 
                        if (in_array($login_fabrica, array(169,170))) { ?>
                            <button id="btn_atualizar_lbm" class='btn btn-info' type="button">Atualizar Lista Básica</button>
                        <? } ?>
                    </td>
                </tr>
            </tfoot>
        </table>
        <?php echo $div_table_padding_fim; ?>
    </form>

    <div class="container">
<?php
    if ($login_fabrica == 1 or $login_fabrica == 51) {?>
        <form name='frm_type' METHOD='POST' ACTION='<?=$PHP_SELF?>' align='center' enctype='multipart/form-data' class='form-search form-inline tc_formulario' >
            <input type="hidden" name="produto" value="<?=$produto?>" />
            <div class='titulo_tabela '>Duplicar Lista Básica para Type</div>
            <br/>

            <div class='row-fluid'>
                <div class='span2'></div>
                <div class='span4'>
                    <div class='control-group <?=(in_array("origem", $msg_erro["campos"])) ? "error" : ""?>'>
                        <label class='control-label' for='type_duplicar_origem'>Type Origem</label>
                        <div class='controls controls-row'>
                            <?
                                try{
                                    GeraComboType::makeComboType($parametrosAdicionaisObject,$type_duplicar_origem,"type_duplicar_origem");
                                    echo GeraComboType::getElement();
                                }catch(Exception $ex){
                                echo $ex->getMessage();
                                }
                            ?>
                        </div>
                    </div>
                </div>
                <div class='span4'>
                    <div class='control-group <?=(in_array("destino", $msg_erro["campos"])) ? "error" : ""?>'>
                        <label class='control-label' for='type_duplicar_destino'>Type Destino</label>
                        <div class='controls controls-row'>
                             <?
                              try{
                                 GeraComboType::makeComboType($parametrosAdicionaisObject,$type_duplicar_destino,"type_duplicar_destino");
                                 echo GeraComboType::getElement();
                              }catch(Exception $ex){
                             echo $ex->getMessage();
                              }
                            ?>

                        </div>
                    </div>
                </div>
                <div class='span2'></div>
            </div>
            <p><br/>
                <button class='btn' id="btn_acao" type="button"  onclick="submitForm($(this).parents('form'),'duplicartype');">Duplicar</button>
                <input type='hidden' id="btn_click" name='btn_acao' value='' />
            </p><br/>
        </form>
        <br /><?php
    }
?>
        <form id="frm_lbm_duplicar" name='frm_lbm_duplicar' METHOD='POST' ACTION='<?=$PHP_SELF?>' align='center' enctype='multipart/form-data' class='form-search form-inline tc_formulario' >
            <input type="hidden" name="produto" value="<?=$produto?>" />
            <input type="hidden" name="ordem_producao" value="<?=$ordem_producao?>" />
            <input type="hidden" name="produto_referencia" value="<?=$produto_referencia?>" />
            <div class='titulo_tabela '><?=traduz('Duplicar Lista Básica para o Produto')?></div>
            <br/>
            <? if (in_array($login_fabrica, array(169,170))) { ?>
                <div class="row-fluid">
                    <div class="span2"></div>
                    <div class="span5">
                        <div class='control-group <?=(in_array('produto', $msg_erro['campos'])) ? "error" : ""; ?>' >
                            <label class="control-label" for="produto_serie">Número de Série</label>
                            <div class="controls controls-row">
                                <div class="span12 input-append">
                                    <input id="produto_serie" name="serie_duplicar" class="span10" type="text" value="<?= $serie_duplicar; ?>" maxlength="30" />
                                    <span class="add-on" rel="lupa"><i class='icon-search'></i></span>
                                    <input type="hidden" name="lupa_config" tipo="produto" parametro="numero_serie" mascara="true" produto-generico="true" />
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="span2"></div>
                </div>
            <? } ?>
            <div class='row-fluid'>
                <div class='span2'></div>
                <div class='span4'>
                    <div class='control-group <?=(in_array("produto", $msg_erro["campos"])) ? "error" : ""?>'>
                        <label class='control-label' for='referencia_duplicar'><?=traduz('Ref. Produto')?></label>
                        <div class='controls controls-row'>
                            <div class='span7 input-append'>
                                <input type="text" id="referencia_duplicar" name="referencia_duplicar" class='span12' maxlength="20" value="<? echo $referencia_duplicar ?>" >
                                <span class='add-on' rel="lupa" ><i class='icon-search'></i></span>
                                <input type="hidden" name="lupa_config" tipo="produto" parametro="referencia" />
                            </div>
                        </div>
                    </div>
                </div>
                <div class='span4'>
                    <div class='control-group <?=(in_array("produto", $msg_erro["campos"])) ? "error" : ""?>'>
                        <label class='control-label' for='descricao_duplicar'><?=traduz('Descrição Produto')?></label>
                        <div class='controls controls-row'>
                            <div class='span12 input-append'>
                                <input type="text" id="descricao_duplicar" name="descricao_duplicar" class='span12' value="<? echo $descricao_duplicar ?>" >
                                <span class='add-on' rel="lupa" ><i class='icon-search' ></i></span>
                                <input type="hidden" name="lupa_config" tipo="produto" parametro="descricao" />
                            </div>
                        </div>
                    </div>
                </div>
                <div class='span2'></div>
            </div>
            <?php /*if ($login_fabrica == 175) { ?>
            <div class="row-fluid">
                <div class="span2"></div>
                <div class="span4">
                    <div class='control-group <?=(in_array('ordem_producao_duplicada', $msg_erro['campos'])) ? "error" : ""; ?>' >
                        <label class="control-label" for="ordem_producao_duplicada">Ordem de Produção</label>
                        <div class="controls controls-row">
                            <div class="span10">
                                <h5 class='asteristico'>*</h5>
                                <input id="ordem_producao_duplicada" numeric="true" name="ordem_producao_duplicada" class="span10" type="text" value="<?= $ordem_producao_duplicada; ?>" maxlength="11" />
                                <input type='hidden' name='ordem_producao' value='<?=$ordem_producao?>' />
                            </div>
                        </div>
                    </div>
                </div>
                <div class="span2"></div>
            </div>
            <?php }*/ ?>
            <p><br/>
                <button class='btn' id="btn_acao" type="button"  onclick="submitForm($('#frm_lbm_duplicar'),'duplicar');">Duplicar</button>
                <input type='hidden' id="btn_click" name='btn_acao' value='' />

            </p><br/>
        </form>
    </div>
<?php
}
?>
</div>
<script type="text/javascript">
    $(function(){
        $(document).on('submit','form#explode-view-remove.ajax-form',function(){
            window.loading("show");
            var explodeViewIndex = $(window.explodeView.getSelectView()).attr('explode-view');
            $(this).find("input[name='vista']").val(explodeViewIndex);
            $(this).ajaxSubmit({
                success : function(data){
                    if(!data)
                        return;
                    window.explodeView.removeView(explodeViewIndex);
                },
                complete : function(){
                    window.loading("hide");
                }
            });
            return false;
        });
        $(document).on('submit','form#explode-view-add.ajax-form',function(){
            window.loading("show");
            $(this).ajaxSubmit({
                success : function(data){
                    if(!data)
                        return;
                    window.explodeView.putView(data.src,data.vista);
                },
                complete : function(){
                    window.loading("hide");
                }
            });
            return false;
        });
    });
    $(document).on('submit','form.ajax-form',function(){
        window.loading('show');
        $(this).ajaxSubmit({
            complete : function(){
                window.loading('hide');
            }
        });
        return false;
    });
</script>
<?php
    include "rodape.php";
    function verificaPeca($referencia){
        global $con;
        global $login_fabrica;
        $sql = "SELECT peca FROM tbl_peca where referencia = '$referencia' AND fabrica = $login_fabrica";
        $res = pg_query($con, $sql);
        if(pg_num_rows($res) == 0){
            throw new Exception("Peça não encontrada. Referencia: " . $referencia);
        }
        return pg_fetch_result($res, 0, "peca");
    }
?>
