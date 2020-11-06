<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';
include 'funcoes.php';
include_once dirname(__FILE__) . '/../class/AuditorLog.php';

$AuditorLog = new AuditorLog;

$title = "CADASTRO PERGUNTAS TÉCNICAS";
$layout_menu = "callcenter";
$admin_privilegios="call_center";

if ($_SERVER['HTTP_HOST'] != 'novodevel.telecontrol.com.br') {
    $arq_jason = file_get_contents("../bloqueio_pedidos/outros_motivos_bd.txt");
    $tbl_faq_black = "tbl_faq_black";
}else{
    $arq_jason = file_get_contents("../outros_motivos_bd.txt");
    $tbl_faq_black = "tbl_faq_black_devel";
}

//Auditor log
require __DIR__.'/../classes/api/Client.php';
use api\Client;

//Anexo S3
include_once S3CLASS;
    $s3 = new AmazonTC("motivos", $login_fabrica);

//Funcão Auditor Log
function auditorLogCallcenter($primary_key,$auditor_antes_func,$auditor_depois_func, $table, $program_url = null, $action_func){
    global $login_fabrica, $login_admin;

    $auditor_ip = $_SERVER['REMOTE_ADDR'];
    if(isset($_SERVER['HTTP_X_FORWARDED_FOR']) && $_SERVER['HTTP_X_FORWARTDED_FOR'] != '') {
        $auditor_ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        $auditor_ip = $_SERVER['REMOTE_ADDR'];
    }

    if (strlen ($auditor_ip) == 0) {
        $auditor_ip = "0.0.0.0";
    }

    $auditor_url_api = "https://api2.telecontrol.com.br/auditor/auditor";

    $auditor_array_dados = array (

        "application" => "02b970c30fa7b8748d426f9b9ec5fe70",
        "table" => $table,
        "ip_access" => "$auditor_ip",
        "owner" => "$login_fabrica",
        "action" => $action_func,
        "program_url" => $program_url,
        "primary_key" => $login_fabrica . "*" . $primary_key,
        "user" => "$login_admin",
        "user_level" => "admin",
        "content" => json_encode (array ("antes" => $auditor_antes_func , "depois" => $auditor_depois_func))
    );


    $auditor_json_dados = json_encode($auditor_array_dados);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $auditor_url_api);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_HEADER, FALSE);
    curl_setopt($ch, CURLOPT_POST, TRUE);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $auditor_json_dados);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: application/json"));
    $response = curl_exec($ch);

    curl_close($ch);
}

//Ação Responder

if($_REQUEST['resposta'] == "sim"){
    $responder_pergunta = "true";
    $readonly = "readonly";
    $display = "style='display:none;'";
}

//Ação Alterar Da Lista
if (isset($_REQUEST['pergunta_id']) ){
    $id_pergunta = $_REQUEST['pergunta_id'];

    $sql = "SELECT
                tbl_faq.faq,
                tbl_faq.produto,
                tbl_faq.situacao,
                tbl_faq.hd_chamado,
                tbl_faq_causa.causa,
                tbl_faq_solucao.solucao
                FROM tbl_faq
                   LEFT JOIN tbl_faq_causa ON tbl_faq_causa.faq = tbl_faq.faq
                   LEFT JOIN tbl_faq_solucao ON tbl_faq_causa.faq_causa = tbl_faq_solucao.faq_causa
                WHERE tbl_faq.faq = $id_pergunta
                AND tbl_faq.fabrica = $login_fabrica;";
    $res = pg_query($con,$sql);
    //echo nl2br($sql);

    if (pg_num_rows($res) > 0) {
        $faq                = pg_fetch_result($res, 0, faq);
        $outros_motivos     = pg_fetch_result($res, 0, situacao);
        $num_chamado        = pg_fetch_result($res, 0, hd_chamado);
        $pergunta_cadastro  = pg_fetch_result($res, 0, causa);
        $resposta_cadastro  = pg_fetch_result($res, 0, solucao);

        $produto_faq        = pg_fetch_result($res, 0, produto);
       //echo $produto_faq.">>>";

        if (strlen($produto_faq)) {
            $sql_prod = "SELECT referencia,descricao FROM tbl_produto WHERE produto = $produto_faq AND fabrica_i = $login_fabrica;";
            $res_prod = pg_query($con,$sql_prod);
            //echo nl2br($sql_prod);
            if (pg_num_rows($res_prod)> 0) {
                $produto_referencia = pg_fetch_result($res_prod, 0, referencia);
                $produto_descricao = pg_fetch_result($res_prod, 0, descricao);
            }
        }

        //echo $produto_referencia."<<<";
    }else{
        $msg_erro["msg"][] = "Pergunta não encontrada!";        
    }
}

//Ação de Gravar a Resposta da Pergunta
//if ($btn_acao == "gravar_resposta_faq" ) {
if($_POST['responder'] == 'Responder') {

    $id_faq       = $_POST['pergunta_id'];
    $resposta     = $_POST['resposta_cadastro'];
    $anexo_r      = $_POST['anexo_r'];
    $anexo_s3_r   = $_POST["anexo_s3_r"];

    if(strlen(trim($resposta)) == 0){

        $msg_erro["msg"][] = "Inserir Resposta Válida!";     
        $erro_resposta = "sim";
        // $retorno = array("error" => utf8_encode("Erro ao inserir Resposta!"));

    }


    $sql_causa = "SELECT faq,faq_causa FROM tbl_faq_causa WHERE faq = {$id_faq}";
    $res_causa = pg_query($con,$sql_causa);

    if (pg_num_rows($res_causa) > 0 AND (!isset($msg_erro)) ) {

        $id_faq_causa = pg_fetch_result($res_causa, 0, faq_causa);

        $res = pg_query($con,"BEGIN");
        $AuditorLog3 = new AuditorLog();
        //Auditor-Anterior
        $sql_auditor = "SELECT  tbl_faq.faq,
                                        tbl_faq.produto,
                                        tbl_faq.situacao,
                                        tbl_faq.linha,
                                        tbl_faq.familia,
                                        tbl_faq.hd_chamado,
                                        tbl_faq.fabrica,
                                        tbl_faq_causa.faq_causa,
                                        tbl_faq_causa.causa as pergunta,
                                        tbl_faq_solucao.faq_solucao,
                                        tbl_faq_solucao.solucao as resposta,
                                        tbl_faq_solucao.descricao,
                                        tbl_faq_solucao.porcentagem
                            FROM tbl_faq
                            LEFT JOIN tbl_faq_causa ON tbl_faq_causa.faq = tbl_faq.faq
                            LEFT JOIN tbl_faq_solucao ON tbl_faq_solucao.faq = tbl_faq.faq
                            WHERE tbl_faq.faq = $id_faq";
        $res_auditor = pg_query($con,$sql_auditor);
        $auditor_antes = pg_fetch_assoc($res_auditor);
        $AuditorLog3->retornaDadosSelect($sql_auditor);
        $action = "update";
        //Fim Auditor-Anterior

        if($login_fabrica == 1){
            $resposta = str_replace("'", "''", $resposta);    
        }        

        $sql_s = "INSERT INTO tbl_faq_solucao (
                                    faq_causa,
                                    faq,
                                    solucao
                                    )VALUES(
                                    $id_faq_causa,
                                    $id_faq,
                                    '$resposta') RETURNING faq_causa;";

        //die(nl2br($sql_s));                                    
        $res_s = pg_query($con,$sql_s);

        if (count($anexo_r)>0) {
            $ano = '2015';
            $mes = '09';

            $arquivos = array();
            foreach ($anexo_r as $key => $value) {
                if ($anexo_s3_r[$key] != "t" && strlen($value) > 0) {
                    $ext = preg_replace("/.+\./", "", $value);
                    $arquivos[] = array(
                        "file_temp" => $value,
                        "file_new"  => "Resposta_{$login_fabrica}_{$pergunta_id}_{$key}.{$ext}"
                    );
                }
            }

            if (count($arquivos) > 0) {
                //var_dump($arquivos);exit;
                $s3->moveTempToBucket($arquivos, $ano, $mes, false);
            }
        }

        if(pg_last_error($con)){
            $res = pg_query($con,"ROLLBACK");
            $msg_erro["msg"][] = "Erro ao inserir Resposta!";
            // $retorno = array("error" => utf8_encode("Erro ao inserir Resposta!"));

        }else{
            $res = pg_query($con,"COMMIT");
            $msg = "Resposta gravada com sucesso!";
            // $retorno = array("ok" => utf8_encode("Resposta gravada com sucesso!"));

            $faq_causa_auditor = pg_fetch_result($res_s, 0, "faq_causa");

            //Auditor-Depois
            $sql_auditor = "SELECT  tbl_faq.faq,
                                            tbl_faq.produto,
                                            tbl_faq.situacao,
                                            tbl_faq.linha,
                                            tbl_faq.familia,
                                            tbl_faq.hd_chamado,
                                            tbl_faq.fabrica,
                                            tbl_faq_causa.faq_causa,
                                            tbl_faq_causa.causa as pergunta,
                                            tbl_faq_solucao.faq_solucao,
                                            tbl_faq_solucao.solucao as resposta,
                                            tbl_faq_solucao.descricao,
                                            tbl_faq_solucao.porcentagem
                                FROM tbl_faq
                                LEFT JOIN tbl_faq_causa ON tbl_faq_causa.faq = tbl_faq.faq
                                LEFT JOIN tbl_faq_solucao ON tbl_faq_solucao.faq = tbl_faq.faq
                                WHERE tbl_faq.faq = $id_faq";
            $res_auditor = pg_query($con,$sql_auditor);
            $auditor_depois = pg_fetch_assoc($res_auditor);
            $auditor_depois['data_alteracao'] = date('d-m-Y h:i:s');
            $auditor_depois['admin'] = $login_admin;
            //Chama função para gravar as informações
            $nome_servidor = $_SERVER['SERVER_NAME'];
            $nome_uri = $_SERVER['REQUEST_URI'];
            $nome_url = $nome_servidor.$nome_uri;
            //auditorLog($faq_causa_auditor,$auditor_antes,$auditor_depois,"tbl_faq_solucao",$nome_url,$action);
            $AuditorLog3->retornaDadosSelect()->EnviarLog("insert", 'tbl_faq',"$login_fabrica*$pergunta_id");
            auditorLogCallcenter($id_faq,$auditor_antes,$auditor_depois,$tbl_faq_black,$nome_url,$action);
            unset($faq_causa_auditor);
            unset($auditor_antes);
            unset($auditor_depois);
            unset($nome_url);
            unset($action);
            header("Location: callcenter_cadastro_pergunta_tecnica.php?msg={$msg}");
        }
    }else{
        $msg_erro["msg"][] = "Resposta Técnica não cadastrada!";        
        // $retorno = array("error" => utf8_encode("Pergunta Técnica não cadastrada!"));
    }

}

//Ação de Excluir a Pergunta e Sua Resposta
if ($btn_acao == "excluir_pergunta" ) {

    $sql_auditor = "SELECT  tbl_faq.faq,
                                            tbl_faq.produto,
                                            tbl_faq.situacao,
                                            tbl_faq.linha,
                                            tbl_faq.familia,
                                            tbl_faq.hd_chamado,
                                            tbl_faq.fabrica,
                                            tbl_faq_causa.faq_causa,
                                            tbl_faq_causa.causa as pergunta,
                                            tbl_faq_solucao.faq_solucao,
                                            tbl_faq_solucao.solucao as resposta,
                                            tbl_faq_solucao.descricao,
                                            tbl_faq_solucao.porcentagem
                                FROM tbl_faq
                                LEFT JOIN tbl_faq_causa ON tbl_faq_causa.faq = tbl_faq.faq
                                LEFT JOIN tbl_faq_solucao ON tbl_faq_solucao.faq = tbl_faq.faq
                                WHERE tbl_faq.faq = $id_faq";
    $res_auditor = pg_query($con,$sql_auditor);


    if (pg_num_rows($res_auditor) > 0) {

        $auditor_antes = pg_fetch_assoc($res_auditor);
        $action = "delete";
        //Fim Auditor-Anterior

        $res = pg_query($con,"BEGIN");

        $sql_d = "  DELETE FROM tbl_faq_solucao
                        WHERE tbl_faq_solucao.faq = $id_faq;

                    DELETE FROM tbl_faq_causa
                        WHERE tbl_faq_causa.faq = $id_faq;

                    DELETE FROM tbl_faq
                        WHERE tbl_faq.faq = $id_faq;
                    ";
        $res_d = pg_query($con,$sql_d);

        $ano_ex = '2015';
        $mes_ex = '09';
        $anexos_per = $s3->getObjectList("Pergunta_{$login_fabrica}_{$id_faq}_", false, $ano_ex, $mes_ex);
        if (count($anexos_per) > 0) {

            $nome_anexo = basename($anexos_per[0]);

            if (strlen($nome_anexo) > 0) {
                $s3->deleteObject($nome_anexo, false, $ano_ex, $mes_ex);
            }
        }

        $anexos_resp = $s3->getObjectList("Resposta_{$login_fabrica}_{$id_faq}_", false, $ano_ex, $mes_ex);
        if (count($anexos_resp) > 0) {
            $nome_anexo = basename($anexos_resp[0]);

            if (strlen($nome_anexo) > 0) {
                $s3->deleteObject($nome_anexo, false, $ano_ex, $mes_ex);
            }
        }

        if(pg_last_error($con)){
            $res = pg_query($con,"ROLLBACK");
            $retorno = array("error" => utf8_encode("Erro ao Excluir Pergunta!"));

        }else{
            $res = pg_query($con,"COMMIT");
            $retorno = array("ok" => utf8_encode("Pergunta excluida com sucesso!"));

            //Auditor-Depois
            $sql_auditor = "SELECT  tbl_faq.faq,
                                    tbl_faq.produto,
                                    tbl_faq.situacao,
                                    tbl_faq.linha,
                                    tbl_faq.familia,
                                    tbl_faq.hd_chamado,
                                    tbl_faq.fabrica,
                                    tbl_faq_causa.faq_causa,
                                    tbl_faq_causa.causa as pergunta,
                                    tbl_faq_solucao.faq_solucao,
                                    tbl_faq_solucao.solucao as resposta,
                                    tbl_faq_solucao.descricao,
                                    tbl_faq_solucao.porcentagem
                                FROM tbl_faq
                                LEFT JOIN tbl_faq_causa ON tbl_faq_causa.faq = tbl_faq.faq
                                LEFT JOIN tbl_faq_solucao ON tbl_faq_solucao.faq = tbl_faq.faq
                                WHERE tbl_faq.faq = $id_faq";
            $res_auditor = pg_query($con,$sql_auditor);
            $auditor_depois = pg_fetch_assoc($res_auditor);
            $auditor_depois['data_alteracao'] = date('d-m-Y h:i:s');
            $auditor_depois['admin'] = $login_admin;
            //Chama função para gravar as informações
            $nome_servidor = $_SERVER['SERVER_NAME'];
            $nome_uri = $_SERVER['REQUEST_URI'];
            $nome_url = $nome_servidor.$nome_uri;
            //auditorLog($faq_causa_auditor,$auditor_antes,$auditor_depois,"tbl_faq",$nome_url,$action);
            auditorLogCallcenter($faq_causa_auditor,$auditor_antes,$auditor_depois,$tbl_faq_black,$nome_url,$action);
            unset($faq_causa_auditor);
            unset($auditor_antes);
            unset($auditor_depois);
            unset($nome_url);
            unset($action);

        }
    }else{
        $retorno = array("error" => utf8_encode("Pergunta Técnica não cadastrada!"));
    }

    //print_r($_POST);
    //    echo "ok";
    exit(json_encode($retorno));
}

//Ação de Gravar ou Alterar os dados da Pergunta
if ($_POST['gravar'] == 'Gravar' || $_POST['alterar'] == 'Alterar' ) {

    $chamado_num = $_POST['num_chamado'];
    $prod_refe = $_POST['produto_referencia'];
    $prod_desc = $_POST['produto_descricao'];
    $cadastro_pergunta = $_POST['pergunta_cadastro'];

    if (!strlen($cadastro_pergunta)) {
        $msg_erro['campos'] = 'pergunta_cadastro';
    }
    if ((!strlen($prod_refe)) AND (!strlen($outros_motivos))) {
        $msg_erro['campos'] = 'produto';
        $msg_erro['campos'] = 'outros_motivos';
    }
    if (!strlen($outros_motivos)) {
        $outros_motivos_sql = "null";
    }else{

        $outros_motivos_sql = "";
        // if ($_SERVER['HTTP_HOST'] != 'devel.telecontrol.com.br') {
        //     $arq_jason = file_get_contents("../bloqueio_pedidos/outros_motivos_bd.txt");
        // }else{
        //     $arq_jason = file_get_contents("outros_motivos_bd.txt");
        // }
        //$arq_jason = file_get_contents("../bloqueio_pedidos/outros_motivos_bd.txt");
        //$arq_jason = file_get_contents("outros_motivos_bd.txt");
        $arq_jason = explode("\n", $arq_jason);
        foreach ($arq_jason as $key => $value) {
            /*  Comentado no HD-3103180 Suporte solicitou a descrição no value do campo não o código,
                estava dando diferença no auditor.

                $decod_motivos = json_decode($value,true);
                $outros_motivos_cod = array_keys($decod_motivos);
                $outros_motivos_cod = $outros_motivos_cod[0];
                $outros_motivos_desc = $decod_motivos[$outros_motivos_cod];
                if ($outros_motivos_cod == $outros_motivos) {
                    $outros_motivos_sql = "'$outros_motivos_cod'";
                }
            */
            $decod_motivos = json_decode($value,true);
            foreach ($decod_motivos as $keyx => $valuex) {
                $valuex = utf8_decode($valuex);
                if ($valuex == $outros_motivos) {
                    $outros_motivos_sql = "'$valuex'";
                }
            }
        }
    }

    if (!strlen($chamado_num)) {
        $chamado_num_sql = "null";
    }else{
        $chamado_num_sql = "$chamado_num";
    }

    if (count($msg_erro['campos']) > 0) {
        $msg_erro["msg"][] = "Preencher os campos obrigatórios!";
    }

    if (strlen($chamado_num) > 0 AND (!isset($msg_erro))) {
        $sql_c = "SELECT hd_chamado
                        FROM tbl_hd_chamado
                        WHERE hd_chamado = {$chamado_num}
                            AND fabrica_responsavel = {$login_fabrica};";
        $res_c = pg_query($con,$sql_c);

        if(pg_num_rows($res_c) == 0){
            $msg_erro["msg"][] = "Chamado não encontrado, favor verificar!";
        }
    }

    if (!isset($msg_erro) AND strlen($prod_refe) > 0) {
        $sql_p = "SELECT produto, linha, familia
                    FROM tbl_produto
                    WHERE referencia = '{$prod_refe}'
                    AND fabrica_i = {$login_fabrica};";
        $res_p = pg_query($con,$sql_p);

        if (pg_num_rows($res_p) == 0) {
            $msg_erro["msg"][] = "Produto não encontrado!";
        }else{
            $prod_id = pg_fetch_result($res_p, 0, produto);
            $prod_linha = pg_fetch_result($res_p, 0, linha);
            $prod_familia = pg_fetch_result($res_p, 0, familia);
        }
    }else{
        $prod_id = 'null';
        $prod_linha = 'null';
        $prod_familia = 'null';
    }

    //Inserir / Alterar a pergunta na tabela tbl_faq
    if (!isset($msg_erro)) {

        if ($_POST['alterar'] == 'Alterar') {
            $pergunta_id = $_POST['pergunta_id'];
            $AuditorLog2 = new AuditorLog();
            $sql_auditor = "SELECT  tbl_faq.faq,
                                            tbl_produto.descricao as produto,
                                            tbl_faq.situacao,
                                            tbl_linha.nome as linha,
                                            tbl_familia.descricao as familia, 
                                            tbl_faq.hd_chamado,
                                            tbl_faq.fabrica,
                                            tbl_faq_causa.faq_causa,
                                            tbl_faq_causa.causa as pergunta,
                                            tbl_faq_solucao.faq_solucao,
                                            tbl_faq_solucao.solucao as resposta,
                                            tbl_faq_solucao.descricao,
                                            tbl_faq_solucao.porcentagem
                                FROM tbl_faq
                                left join tbl_linha on tbl_linha.linha = tbl_faq.linha
                                left join tbl_familia on tbl_familia.familia = tbl_faq.familia
                                LEFT JOIN tbl_produto ON tbl_produto.produto = tbl_faq.produto
                                LEFT JOIN tbl_faq_causa ON tbl_faq_causa.faq = tbl_faq.faq
                                LEFT JOIN tbl_faq_solucao ON tbl_faq_solucao.faq = tbl_faq.faq
                                WHERE tbl_faq.faq = $pergunta_id";
            $res_auditor = pg_query($con,$sql_auditor);

            $AuditorLog->retornaDadosSelect($sql_auditor);
            $AuditorLog2->retornaDadosSelect($sql_auditor);

            if (pg_num_rows($res_auditor) > 0) {

                //$auditor_antes = pg_fetch_assoc($res_auditor);
                $action = "update";
                //Fim do auditor

                $res = pg_query($con,"BEGIN");

                $outros_motivos_sql = utf8_encode($outros_motivos_sql); //HD-3103180

                $sql_up = "UPDATE tbl_faq
                                SET
                                    produto     = $prod_id,
                                    situacao    = $outros_motivos_sql,
                                    linha       = $prod_linha,
                                    familia     = $prod_familia,
                                    hd_chamado  = $chamado_num_sql,
                                    fabrica     = $login_fabrica
                                WHERE faq = $pergunta_id;";
                $res_up = pg_query($con,$sql_up);
                #echo nl2br($sql_up);exit;

                if(pg_last_error($con)){
                    $msg_erro["msg"][] = "Erro ao alterar Pergunta!";
                }else{
                    $faq = pg_fetch_result($res_up, 0, faq);
                }

                if (!isset($msg_erro)) {
                    $cadastro_pergunta = str_replace("\r", " ", $cadastro_pergunta);
                    $cadastro_pergunta = str_replace("\n", " ", $cadastro_pergunta);
                    if($login_fabrica == 1){
                        $cadastro_pergunta = str_replace("'", "''", $cadastro_pergunta);
                    }
                    $sql_up = "UPDATE tbl_faq_causa
                                    SET causa = '$cadastro_pergunta'
                                    WHERE faq = $pergunta_id;";
                    $res_up = pg_query($con,$sql_up);

                    if (pg_last_error($con)) {
                        $msg_erro["msg"][] = "Erro ao alterar Pergunta!";
                    }
                }

                $anexo_faq  = $_POST["anexo"];
                $anexo_faq_s3  = $_POST["anexo_s3"];
                $ano = '2015';
                $mes = '09';

                if (count($anexo_faq)>0) {
                    $arquivos = array();

                    foreach ($anexo_faq as $key => $value) {
                        if ($anexo_faq_s3[$key] != "t" && strlen($value) > 0) {
                            $ext = preg_replace("/.+\./", "", $value);
                            $arquivos[] = array(
                                "file_temp" => $value,
                                "file_new"  => "Pergunta_{$login_fabrica}_{$pergunta_id}_{$key}.{$ext}"
                            );
                        }
                    }

                    if (count($arquivos) > 0) {
                        $s3->moveTempToBucket($arquivos, $ano, $mes, false);
                    }
                }

                $anexo_r      = $_POST['anexo_r'];
                $anexo_s3_r   = $_POST["anexo_s3_r"];

                if (count($anexo_r)>0) {

                    $arquivos = array();
                    foreach ($anexo_r as $key => $value) {
                        if ($anexo_s3_r[$key] != "t" && strlen($value) > 0) {
                            $ext = preg_replace("/.+\./", "", $value);
                            $arquivos[] = array(
                                "file_temp" => $value,
                                "file_new"  => "Resposta_{$login_fabrica}_{$pergunta_id}_{$key}.{$ext}"
                            );
                        }
                    }

                    if (count($arquivos) > 0) {
                        $s3->moveTempToBucket($arquivos, $ano, $mes, false);
                    }
                }


                if (!isset($msg_erro)) {
                    $res = pg_query($con,"COMMIT");
                    $msg = "Pergunta alterada com sucesso!";

                    $AuditorLog->retornaDadosSelect()->EnviarLog("update", 'tbl_faq',"$login_fabrica");
                    $AuditorLog2->retornaDadosSelect()->EnviarLog("update", 'tbl_faq',"$login_fabrica*$pergunta_id");


                    $auditor_depois = pg_fetch_assoc($res_auditor);
                    $auditor_depois['data_alteracao'] = date('d-m-Y h:i:s');
                    $auditor_depois['admin'] = $login_admin;

                    //Chama função para gravar as informações
                    $nome_servidor = $_SERVER['SERVER_NAME'];
                    $nome_uri = $_SERVER['REQUEST_URI'];
                    $nome_url = $nome_servidor.$nome_uri;

                    //auditorLog($faq,$auditor_antes,$auditor_depois,"tbl_faq",$nome_url,$action);
                    //auditorLogCallcenter($pergunta_id,$auditor_antes,$auditor_depois,$tbl_faq_black,$nome_url,$action);
                    unset($faq);
                    unset($auditor_antes);
                    unset($auditor_depois);
                    unset($nome_url);
                    unset($action);

                }else{
                    $res = pg_query($con,"ROLLBACK");
                }



            }else{
                $msg_erro["msg"][] = "Pergunta não encontrada!";                
            }


        }else{

            //Auditor-Anterior
            $auditor_antes = Array( "faq" => "",
                                    "produto" => "",
                                    "situacao" => "",
                                    "linha" => "",
                                    "familia" => "",
                                    "hd_chamado" => "",
                                    "fabrica" => "",
                                    "faq_causa" => "",
                                    "pergunta" => "",
                                    "faq_solucao" => "",
                                    "resposta" => "",
                                    "descricao" => "",
                                    "porcentagem" => "",
                                    "admin" => "");
            $action = "insert";
            //Fim Auditor-Anterior

            $res = pg_query($con,"BEGIN");

            $sql_nc = "SELECT numero_cliente FROM tbl_faq WHERE fabrica = $login_fabrica ORDER BY numero_cliente DESC LIMIT 1;";
            $res_nc = pg_query($con,$sql_nc);
            if (pg_num_rows($res_nc) > 0) {
                $numero_cliente = pg_fetch_result($res_nc, 0, numero_cliente);
                $numero_cliente++;
            }else{
                $numero_cliente = 1;
            }

            $outros_motivos_sql = utf8_encode($outros_motivos_sql); //HD-3103180
            $sql_ins = "INSERT INTO tbl_faq (
                                        produto,
                                        situacao,
                                        linha,
                                        familia,
                                        hd_chamado,
                                        fabrica,
                                        numero_cliente,
                                        admin
                                        )VALUES(
                                        $prod_id,
                                        $outros_motivos_sql,
                                        $prod_linha,
                                        $prod_familia,
                                        $chamado_num_sql,
                                        $login_fabrica,
                                        $numero_cliente,
                                        $login_admin
                                        ) RETURNING faq;";
            $res_ins = pg_query($con,$sql_ins);
            //echo nl2br($sql_ins);
            if(pg_last_error($con)){
                $msg_erro["msg"][] = "Erro ao inserir Pergunta!";
            }else{
                $faq = pg_fetch_result($res_ins, 0, faq);
            }

            if (!isset($msg_erro)) {
                $cadastro_pergunta = str_replace("\r", " ", $cadastro_pergunta);
                $cadastro_pergunta = str_replace("\n", " ", $cadastro_pergunta);
                if($login_fabrica == 1){
                    $cadastro_pergunta = str_replace("'", "''", $cadastro_pergunta);
                }
                $sql_fc = "INSERT INTO tbl_faq_causa (
                                            faq,
                                            causa
                                            )VALUES(
                                            $faq,
                                            '$cadastro_pergunta'
                                            );";
                $res_fc = pg_query($con,$sql_fc);

                if (pg_last_error($con)) {
                    $msg_erro["msg"][] = "Erro ao inserir Pergunta!";
                }
            }

            //Anexo
            $anexo_faq  = $_POST["anexo"];

            $anexo_faq_s3  = $_POST["anexo_s3"];

            //exit;
            if (count($anexo_faq)>0) {
                //list($dia, $mes, $ano) =  explode("/",date('d/m/Y')) ;
                $ano = '2015';
                $mes = '09';

                $arquivos = array();

                foreach ($anexo_faq as $key => $value) {
                    if ($anexo_faq_s3[$key] != "t" && strlen($value) > 0) {
                        $ext = preg_replace("/.+\./", "", $value);
                        $arquivos[] = array(
                            "file_temp" => $value,
                            "file_new"  => "Pergunta_{$login_fabrica}_{$faq}_{$key}.{$ext}"
                        );
                    }
                }

                if (count($arquivos) > 0) {
                    $s3->moveTempToBucket($arquivos, $ano, $mes, false);
                }
            }

            if (!isset($msg_erro)) {
                $res = pg_query($con,"COMMIT");

                //Auditor-Depois
                $sql_auditor = "SELECT  tbl_faq.faq,
                                            tbl_produto.descricao as produto,
                                            tbl_faq.situacao,
                                            tbl_linha.nome as linha,
                                            tbl_familia.descricao as familia, 
                                            tbl_faq.hd_chamado,
                                            tbl_faq.fabrica,
                                            tbl_faq_causa.faq_causa,
                                            tbl_faq_causa.causa as pergunta,
                                            tbl_faq_solucao.faq_solucao,
                                            tbl_faq_solucao.solucao as resposta,
                                            tbl_faq_solucao.descricao,
                                            tbl_faq_solucao.porcentagem
                                FROM tbl_faq
                                left join tbl_linha on tbl_linha.linha = tbl_faq.linha
                                left join tbl_familia on tbl_familia.familia = tbl_faq.familia
                                LEFT JOIN tbl_produto ON tbl_produto.produto = tbl_faq.produto
                                LEFT JOIN tbl_faq_causa ON tbl_faq_causa.faq = tbl_faq.faq
                                LEFT JOIN tbl_faq_solucao ON tbl_faq_solucao.faq = tbl_faq.faq
                                WHERE tbl_faq.faq = $faq";
                // $res_auditor = pg_query($con,$sql_auditor);
                // $auditor_depois = pg_fetch_assoc($res_auditor);
                // $auditor_depois['data_alteracao'] = date('d-m-Y h:i:s');
                // $auditor_depois['admin'] = $login_admin;

                // //Chama função para gravar as informações
                // $nome_servidor = $_SERVER['SERVER_NAME'];
                // $nome_uri = $_SERVER['REQUEST_URI'];
                // $nome_url = $nome_servidor.$nome_uri;
                // auditorLogCallcenter($faq,$auditor_antes,$auditor_depois,$tbl_faq_black,$nome_url,$action);

                unset($faq);
                unset($auditor_antes);
                unset($auditor_depois);
                unset($nome_url);
                unset($action);
                $produto_referencia = "";
                $produto_descricao = "";
                $outros_motivos = "";
                $pergunta_cadastro = "";
                $anexo_faq  = "";
                $anexo_faq_s3  = "";
                unset($_POST);
                $msg = "Pergunta cadastrada com sucesso!";


            }else{
                $res = pg_query($con,"ROLLBACK");
            }

        }
    }
}

//Lê o arquivo com os Outros Motivos Cadastrados
if($btn_acao == "carregaMotivos"){
    $retorno = "";

    //$arq_jason = file_get_contents("../bloqueio_pedidos/outros_motivos_bd.txt");
    //$arq_jason = file_get_contents("outros_motivos_bd.txt");
    $arq_jason = explode("\n", $arq_jason);
    $retorno = "<option></option>";

    $motivo = utf8_decode($motivo);

    foreach ($arq_jason as $key => $value) {
        $decod_motivos = json_decode($value,true);

        /*  Comentado no HD-3103180 Suporte solicitou a descrição no value do campo não o código,
            estava dando diferença no auditor.

            $outros_motivos_cod = array_keys($decod_motivos);
            $outros_motivos_cod = $outros_motivos_cod[0];
            $outros_motivos_desc = $decod_motivos[$outros_motivos_cod];
            if (empty($outros_motivos_desc)) {
                continue;
            }


            if ($outros_motivos_cod == $motivo) {
                $selected = "selected";
            }else{
                $selected = "";
            }
            $value = utf8_decode($outros_motivos_desc);
            $retorno .= "<option value='$outros_motivos_cod' ". $selected ." >".utf8_decode($outros_motivos_desc)."</option>";
        */
        foreach ($decod_motivos as $keyx => $valuex) {
            if (empty($valuex)) {
                continue;
            }
            $valuex = utf8_decode($valuex);

            if ($valuex == $motivo) {
                $selected = "selected";
                $outros_motivos_sql = "'$valuex'";
            }else{
                $selected = "";
            }
            $retorno .= "<option value='$valuex' ". $selected ." >".$valuex."</option>";
        }

    }

    echo $retorno;
    exit;
}

/**
* Cria a chave do anexo
*/
if (!strlen(getValue("anexo_chave"))) {
    $anexo_chave = sha1(date("Ymdhi")."{$login_fabrica}".(($areaAdmin === true) ? $login_admin : $login_posto));
    $anexo_chave_r = sha1(date("Ymdhi")."{$login_fabrica}".(($areaAdmin === true) ? $login_admin : $login_posto).'resposta');
} else {
    $anexo_chave = getValue("anexo_chave");
    $anexo_chave_r = getValue("anexo_chave_r");

}
/**
* Inclui o arquivo no s3
*/
if (isset($_POST["ajax_anexo_upload"])) {
    $posicao = $_POST["anexo_posicao"];
    $chave   = $_POST["anexo_chave"];

    $arquivo = $_FILES["anexo_upload_{$posicao}"];

    $ext = strtolower(preg_replace("/.+\./", "", $arquivo["name"]));

    if ($ext == "jpeg") {
        $ext = "jpg";
    }

    if (strlen($arquivo["tmp_name"]) > 0) {
        if (!in_array($ext, array("png", "jpg", "jpeg", "bmp", "pdf", "doc", "docx"))) {
            $retorno = array("error" => utf8_encode("Arquivo em formato inválido, são aceitos os seguintes formatos: png, jpeg, bmp, pdf, doc, docx"));
        } else {
            $arquivo_nome = "{$chave}_{$posicao}";

            $s3->tempUpload("{$arquivo_nome}", $arquivo);

            if($ext == "pdf"){
                $link = "imagens/pdf_icone.png";
            } else if(in_array($ext, array("doc", "docx"))) {
                $link = "imagens/docx_icone.png";
            } else {
                $link = $s3->getLink("thumb_{$arquivo_nome}.{$ext}", true);
            }

            $href = $s3->getLink("{$arquivo_nome}.{$ext}", true);

            if (!strlen($link)) {
                $retorno = array("error" => utf8_encode("Erro ao anexar arquivo"));
            } else {
                $retorno = array("link" => $link, "arquivo_nome" => "{$arquivo_nome}.{$ext}", "href" => $href, "ext" => $ext);
            }
        }
    } else {
        $retorno = array("error" => utf8_encode("Erro ao anexar arquivo"));
    }

    $retorno["posicao"] = $posicao;

    exit(json_encode($retorno));
}

/**
* Inclui o arquivo no s3 Resposta
*/
if (isset($_POST["ajax_anexo_upload_r"])) {
    $posicao = $_POST["anexo_posicao_r"];
    $chave   = $_POST["anexo_chave_r"];

    $arquivo = $_FILES["anexo_r_upload_{$posicao}"];

    $ext = strtolower(preg_replace("/.+\./", "", $arquivo["name"]));

    if ($ext == "jpeg") {
        $ext = "jpg";
    }

    if (strlen($arquivo["tmp_name"]) > 0) {
        if (!in_array($ext, array("png", "jpg", "jpeg", "bmp", "pdf", "doc", "docx"))) {
            $retorno = array("error" => utf8_encode("Arquivo em formato inválido, são aceitos os seguintes formatos: png, jpeg, bmp, pdf, doc, docx"));
        } else {
            $arquivo_nome = "{$chave}_{$posicao}";

            $s3->tempUpload("{$arquivo_nome}", $arquivo);

            if($ext == "pdf"){
                $link = "imagens/pdf_icone.png";
            } else if(in_array($ext, array("doc", "docx"))) {
                $link = "imagens/docx_icone.png";
            } else {
                $link = $s3->getLink("thumb_{$arquivo_nome}.{$ext}", true);
            }

            $href = $s3->getLink("{$arquivo_nome}.{$ext}", true);

            if (!strlen($link)) {
                $retorno = array("error" => utf8_encode("Erro ao anexar arquivo"));
            } else {
                $retorno = array("link" => $link, "arquivo_nome" => "{$arquivo_nome}.{$ext}", "href" => $href, "ext" => $ext);
            }
        }
    } else {
        $retorno = array("error" => utf8_encode("Erro ao anexar arquivo"));
    }

    $retorno["posicao"] = $posicao;

    exit(json_encode($retorno));
}

/**
* Excluir anexo Pergunta
*/
if (isset($_POST["ajax_anexo_exclui"])) {
    $anexo_nome_excluir = $_POST['anexo_nome_excluir'];
    $numero_processo = $_POST['numero_processo'];

    if (count($anexo_nome_excluir) > 0) {
        $ano_ex = '2015';
        $mes_ex = '09';
        $s3->deleteObject($anexo_nome_excluir, false, $ano_ex, $mes_ex);
        $retorno = array("ok" => utf8_encode("Excluído com sucesso!"));
    }else{
        $retorno = array("error" => utf8_encode("Erro ao excluir arquivo"));
    }
     exit(json_encode($retorno));
}

/**
* Excluir anexo Resposta
*/
if (isset($_POST["ajax_anexo_exclui_r"])) {
    $anexo_nome_excluir = $_POST['anexo_nome_excluir_r'];
    $numero_processo = $_POST['numero_resposta_r'];

    if (count($anexo_nome_excluir) > 0) {
        $ano_ex = '2015';
        $mes_ex = '09';
        $s3->deleteObject($anexo_nome_excluir, false, $ano_ex, $mes_ex);
        $retorno = array("ok" => utf8_encode("Excluído com sucesso!"));
    }else{
        $retorno = array("error" => utf8_encode("Erro ao excluir arquivo"));
    }
     exit(json_encode($retorno));
}


include "cabecalho_new.php";

$plugins = array(
    "autocomplete",
    "datepicker",
    "shadowbox",
    "mask",
    "ajaxform",
    "fancyzoom",
    "dataTable"
);

include("plugin_loader.php");
?>

<script type="text/javascript">
$(function() {
    Shadowbox.init();

    // Ocultar botão Gravar durante a gravação
    $("#gravar").click(function(){
        $("#gravar").hide();
    });

    // Ocultar botão Responder durante a gravação
    $("#responder").click(function(){
        $("#responder").hide();
    });

    // Ocultar botão Alterar durante a gravação
    $("#alterar").click(function(){
        $("#alterar").hide();
    });

    /**
     * Evento que chama a função de lupa para a lupa clicada
     */
    $("span[rel=lupa]").click(function() {
        $.lupa($(this));
    });
    //Auto complete
    $.autocompleteLoad(Array("produto"));


    carregaMotivos();

    $("#inserir_alterar").click(function() {
        Shadowbox.open({
            content: 'outros_motivos_bd_ajaxx.php',
            player: 'iframe'
            //carregaMotivos();
        });
    });

    $("#atualizar").click(function(){
        carregaMotivos()
    });


    /**
    * Eventos para anexar/excluir imagem
    */
    $("button.btn_acao_anexo").click(function(){
        var name = $(this).attr("name");

        if (name == "anexar") {
            $(this).trigger("anexar_s3", [$(this)]);
        }else{
            $(this).trigger("excluir_s3", [$(this)]);
        }
    });

    $("button.btn_acao_anexo").bind("anexar_s3",function(){

        var posicao = $(this).attr("rel");

        var button = $(this);

        $("input[name=anexo_upload_"+posicao+"]").click();
    });

    $("button.btn_acao_anexo").bind("excluir_s3",function(){

        var posicao = $(this).attr("rel");
        var numero_processo = $("#pergunta_id").val();

        var button = $(this);
        var nome_an_p = $("input[name='anexo["+posicao+"]']").val();
        // alert(nome_an_p);
        // return;
        $.ajax({
            url: "callcenter_cadastro_pergunta_tecnica.php",
            type: "POST",
            data: { ajax_anexo_exclui: true, anexo_nome_excluir: nome_an_p, numero_processo: numero_processo },
            beforeSend: function() {
                $("#div_anexo_"+posicao).find("button").hide();
                $("#div_anexo_"+posicao).find("img.anexo_thumb").hide();
                $("#div_anexo_"+posicao).find("img.anexo_loading").show();
            },
            complete: function(data) {
                data = $.parseJSON(data.responseText);

                if (data.error) {
                    alert(data.error);
                } else {
                    $("#div_anexo_"+posicao).find("a[target='_blank']").remove();
                    $("#baixar_"+posicao).remove();
                    $(button).text("Anexar").attr({
                        id:"anexar_"+posicao,
                        class:"btn btn-mini btn-primary btn-block",
                        name: "anexar"
                    });
                    $("input[name='anexo["+posicao+"]']").val("f");
                    $("#div_anexo_"+posicao).prepend('<img class="anexo_thumb" style="width: 100px; height: 90px;" src="imagens/imagem_upload.png">');

                    $("#div_anexo_"+posicao).find("img.anexo_loading").hide();
                    $("#div_anexo_"+posicao).find("button").show();
                    $("#div_anexo_"+posicao).find("img.anexo_thumb").show();
                    alert(data.ok);
                }

            }
        });
    });

    /**
    * Eventos para anexar imagem
    */
    $("form[name=form_anexo]").ajaxForm({
        complete: function(data) {
            data = $.parseJSON(data.responseText);

            if (data.error) {
                alert(data.error);
            } else {
                var imagem = $("#div_anexo_"+data.posicao).find("img.anexo_thumb").clone();
                $(imagem).attr({ src: data.link });

                $("#div_anexo_"+data.posicao).find("img.anexo_thumb").remove();

                var link = $("<a></a>", {
                    href: data.href,
                    target: "_blank"
                });

                $(link).html(imagem);

                $("#div_anexo_"+data.posicao).prepend(link);

                if ($.inArray(data.ext, ["doc", "pdf", "docx"]) == -1) {
                    setupZoom();
                }

                $("#div_anexo_"+data.posicao).find("input[rel=anexo]").val(data.arquivo_nome);
            }

            $("#div_anexo_"+data.posicao).find("img.anexo_loading").hide();
            $("#div_anexo_"+data.posicao).find("button").show();
            $("#div_anexo_"+data.posicao).find("img.anexo_thumb").show();
        }
    });
    $("input[name^=anexo_upload_]").change(function() {
        var i = $(this).parent("form").find("input[name=anexo_posicao]").val();

        $("#div_anexo_"+i).find("button").hide();
        $("#div_anexo_"+i).find("img.anexo_thumb").hide();
        $("#div_anexo_"+i).find("img.anexo_loading").show();
        $(this).parent("form").submit();
    });
    //Fim do anexa imagem pergunta

    /**
    * Eventos para anexar/excluir imagem Resposta
    */
    $("button.btn_acao_anexo_r").click(function(){
        var name = $(this).attr("name");

        if (name == "anexar_r") {
            $(this).trigger("anexar_s3_r", [$(this)]);
        }else{
            $(this).trigger("excluir_s3_r", [$(this)]);
        }
    });

    $("button.btn_acao_anexo_r").bind("anexar_s3_r",function(){

        var posicao = $(this).attr("rel");

        var button = $(this);

        $("input[name=anexo_r_upload_"+posicao+"]").click();
    });

    $("button.btn_acao_anexo_r").bind("excluir_s3_r",function(){

        var posicao = $(this).attr("rel");
        var numero_processo = $("#resposta_id").val();

        var button = $(this);
        var nome_an_p = $("input[name='anexo_r["+posicao+"]']").val();
        // alert(nome_an_p);
        // return;
        $.ajax({
            url: "callcenter_cadastro_pergunta_tecnica.php",
            type: "POST",
            data: { ajax_anexo_exclui: true, anexo_nome_excluir: nome_an_p, numero_processo: numero_processo },
            beforeSend: function() {
                $("#div_anexo_r_"+posicao).find("button").hide();
                $("#div_anexo_r_"+posicao).find("img.anexo_thumb").hide();
                $("#div_anexo_r_"+posicao).find("img.anexo_loading").show();
            },
            complete: function(data) {
                data = $.parseJSON(data.responseText);

                if (data.error) {
                    alert(data.error);
                } else {
                    $("#div_anexo_r_"+posicao).find("a[target='_blank']").remove();
                    $("#baixar_r_"+posicao).remove();
                    $(button).text("Anexar").attr({
                        id:"anexar_r_"+posicao,
                        class:"btn btn-mini btn-primary btn-block",
                        name: "anexar_r"
                    });
                    $("input[name='anexo_r["+posicao+"]']").val("f");
                    $("#div_anexo_r_"+posicao).prepend('<img class="anexo_thumb" style="width: 100px; height: 90px;" src="imagens/imagem_upload.png">');

                    $("#div_anexo_r_"+posicao).find("img.anexo_loading").hide();
                    $("#div_anexo_r_"+posicao).find("button").show();
                    $("#div_anexo_r_"+posicao).find("img.anexo_thumb").show();
                    alert(data.ok);
                }

            }
        });
    });

    /**
    * Eventos para anexar imagem
    */
    $("form[name=form_anexo_r]").ajaxForm({
        complete: function(data) {
            data = $.parseJSON(data.responseText);

            if (data.error) {
                alert(data.error);
            } else {
                var imagem = $("#div_anexo_r_"+data.posicao).find("img.anexo_thumb").clone();
                $(imagem).attr({ src: data.link });

                $("#div_anexo_r_"+data.posicao).find("img.anexo_thumb").remove();

                var link = $("<a></a>", {
                    href: data.href,
                    target: "_blank"
                });

                $(link).html(imagem);

                $("#div_anexo_r_"+data.posicao).prepend(link);

                if ($.inArray(data.ext, ["doc", "pdf", "docx"]) == -1) {
                    setupZoom();
                }

                $("#div_anexo_r_"+data.posicao).find("input[rel=anexo_r]").val(data.arquivo_nome);
            }

            $("#div_anexo_r_"+data.posicao).find("img.anexo_loading").hide();
            $("#div_anexo_r_"+data.posicao).find("button").show();
            $("#div_anexo_r_"+data.posicao).find("img.anexo_thumb").show();
        }
    });
    $("input[name^=anexo_r_upload_]").change(function() {
        var i = $(this).parent("form").find("input[name=anexo_posicao_r]").val();

        $("#div_anexo_r_"+i).find("button").hide();
        $("#div_anexo_r_"+i).find("img.anexo_thumb").hide();
        $("#div_anexo_r_"+i).find("img.anexo_loading").show();
        $(this).parent("form").submit();
    });
    //fim do anexa imagem resposta
});

function retorna_produto (retorno) {
    $("#produto_referencia").val(retorno.referencia);
    $("#produto_descricao").val(retorno.descricao);
}

function carregaMotivos(){
    var motivo = $("#outros_motivos").attr("data-motivo");
    $.ajax({
        url: "callcenter_cadastro_pergunta_tecnica.php",
        type: "POST",
        data: {
            btn_acao: "carregaMotivos",
            motivo: motivo
        },
        complete: function (data) {
            data = data.responseText;

            if(data != ""){
                $("#outros_motivos").html(data);
            }
        }
    });
}

//Ação do Botão Excluir
$(document).on("click","button[id^=btn_remove_]",function(){
    var linha     = $(this).parents("td");
    var id_faq    = $(linha).find("input[id^=id_tbl_faq_]").val();

    if (ajaxAction()) {
        $.ajax({
            async: false,
            url: "<?=$_SERVER['PHP_SELF']?>",
            type: "POST",
            dataType: "JSON",
            data: {
                btn_acao: "excluir_pergunta",
                id_faq: id_faq
            },
            complete: function (data) {
                //var retorno = data.responseText;
                data = $.parseJSON(data.responseText);

                if (data.error) {
                    alert(data.error);
                } else {
                    alert(data.ok);
                    $("#tr_"+id_faq).remove();
                    $("#tr_res_"+id_faq).remove();
                }
            }
        });
    }
});

//Ação do Botão Responder
/*$(document).on("click","button[id^=responder]",function(){    
    var linha     = $(this).parents("tr");
    var resposta  = $(linha).find("textarea[id^=resposta_cadastro]").val();
    var id_faq    = $(linha).find("input[id^=id_faq]").val();
    var anexo     = $(linha).find("input[name^=anexo]").val();
    var anexo_s3  = $(linha).find("input[name^=anexo_s3]").val();
    //alert(anexo);
    //alert(anexo_s3);

    var alert_erro = '';
    if(resposta == '' || resposta == null){
        alert_erro = "Resposta em branco, favor inserir resposta!";
        alert(alert_erro);                 
    }

    if(alert_erro == ''){
      if (ajaxAction()) {
        $.ajax({
            async: false,
            url: "<?=$_SERVER['PHP_SELF']?>",
            type: "POST",
            dataType: "JSON",
            data: {
                btn_acao: "gravar_resposta_faq",
                anexo: anexo,
                anexo_s3: anexo_s3,
                resposta: resposta,
                id_faq: id_faq
            },
            complete: function (data) {
                //var retorno = data.responseText;                
                data = $.parseJSON(data.responseText);

                if (data.error) {
                    alert(data.error);                    
                } else {                    
                    $("#tr_"+id_faq).remove();
                    $("#tr_res_"+id_faq).remove();
                }
            }
        });
      }
    } 
});*/
</script>

<?php
if (count($msg_erro["msg"]) > 0) {
?>
    <div class="alert alert-error">
        <h4><?=implode("<br />", $msg_erro["msg"])?></h4>
    </div>
<?php
}
if (count($msg) > 0) {
    ?>
    <div class="alert alert-success">
        <h4><?php echo $msg ?></h4>
    </div>
    <?
}
?>
<div class="row">
    <b class="obrigatorio pull-right">  * Campos obrigatórios </b>
</div>
<form name="frm_relatorio" METHOD="POST" ACTION="<? echo $PHP_SELF ?>" align='center' class='form-search form-inline tc_formulario'>
    <input type="hidden" name="resposta" value="<?=$_REQUEST['resposta']?>">
    <div class='titulo_tabela '>Cadastro de Pergunta Técnica</div>
    <br/>
    <div class='row-fluid'>
        <div class='span2'></div>
        <div class='span4'>
            <div class='control-group <?=(in_array("produto", $msg_erro["campos"])) ? "error" : ""?>'>
                <label class='control-label' for='produto_referencia'>Ref. Produto</label>
                <div class='controls controls-row'>
                    <div class='span10 input-append'>
                        <h5 class='asteristico'>*</h5>
                        <input type="text" id="produto_referencia" name="produto_referencia" class='span12' maxlength="20" value="<? echo $produto_referencia ?>" <?=$readonly?> >
                        <span class='add-on' rel="lupa" <?=$display?>><i class='icon-search'></i></span>
                        <input type="hidden" name="lupa_config" tipo="produto" parametro="referencia" />                        
                    </div>
                </div>
            </div>
        </div>
        <div class='span4'>
            <div class='control-group <?=(in_array("produto", $msg_erro["campos"])) ? "error" : ""?>'>
                <label class='control-label' for='produto_descricao'>Descrição Produto</label>
                <div class='controls controls-row'>
                    <div class='span11 input-append'>
                        <h5 class='asteristico'>*</h5>
                        <input type="text" id="produto_descricao" name="produto_descricao" class='span12' value="<? echo $produto_descricao ?>" <?=$readonly?> >
                        <span class='add-on' rel="lupa" <?=$display?> ><i class='icon-search' ></i></span>
                        <input type="hidden" name="lupa_config" tipo="produto" parametro="descricao" />
                    </div>
                </div>
            </div>
        </div>
        <div class='span2'></div>
    </div>
    <div class="row-fluid">
        <div class='span2'></div>
        <div class="span4">
            <div class="control-group <?=(in_array('outros_motivos', $msg_erro['campos'])) ? "error" : "" ?>">
                <label class="control-label" for="tabela">Outros Motivos</label>
                <div class="controls controls-row">
                    <div class='span12'>
                        <h5 class='asteristico'>*</h5>
                        <select id="outros_motivos" name="outros_motivos" class="span11" data-motivo="<?php echo utf8_decode($outros_motivos);?>" <?=$readonly?> >

                        </select>

                    <?php
                    $sql_ad = "SELECT privilegios FROM tbl_admin WHERE fabrica = $login_fabrica AND privilegios = '*' AND admin = $login_admin";
                    $res_ad = pg_query($con,$sql_ad);

                    if (pg_num_rows($res_ad) > 0) {
                    ?>
                        <br />
                        <button id="inserir_alterar" type="button" class="btn btn-mini btn-danger" name="inserir_alterar" <?=$display?>>Inserir/Alterar</button>
                        <button id="atualizar" type="button" class="btn btn-mini btn-info" name="atualizar" <?=$display?> >Atualizar</button>
                    <?php
                    }
                    ?>
                    </div>
                </div>
            </div>
        </div>

         <div class="span4">
            <div class="control-group">
                <label class="control-label" for="tabela">Numero do Chamado</label>
                <div class="controls controls-row">
                    <div class="span12">
                        <input id="num_chamado" name="num_chamado" class="span12" type="text" value="<?php echo $num_chamado ?>" <?=$readonly?> />
                    </div>
                </div>
            </div>
        </div>
        <div class='span2'></div>
    </div>
    <br>
    <div class="row-fluid">
        <div class="span2"></div>
        <div class="span8">
            <div class="control-group <?=(in_array('pergunta_cadastro', $msg_erro['campos'])) ? "error" : "" ?>" >
                <label class="control-label" for="pergunta_cadastro">Cadastre Sua Pergunta</label>
                <div class="controls controls-row">
                    <div class="span12">
                        <h5 class='asteristico'>*</h5>
                        <textarea id="pergunta_cadastro" name="pergunta_cadastro" class="span12" style="height: 50px;" <?=$readonly?> ><?php  echo $pergunta_cadastro?></textarea>
                    </div>
                </div>
            </div>
            </div>
        <div class="span2"></div>
    </div>
    <br />

    <!-- ANexo -->
    <div id="div_anexos" class="tc_formulario">
        <div class="titulo_tabela">Anexo da Pergunta</div>
        <br />

        <div class="tac" >
        <?php
        $fabrica_qtde_anexos = 1;
        if ($fabrica_qtde_anexos > 0) {
            $ano = '2015';
            $mes = '09';

            echo "<input type='hidden' name='anexo_chave' value='{$anexo_chave}' />";

            for ($i = 0; $i < $fabrica_qtde_anexos; $i++) {
                unset($anexo_link);

                $anexo_imagem = "imagens/imagem_upload.png";
                $anexo_s3     = false;
                $anexo        = "";
                if (strlen($_POST['anexo['.$i.']']) > 0 && $_POST['anexo_s3['.$i.']'] != "t") {

                    $anexos       = $s3->getObjectList(getValue("anexo[{$i}]"), true);

                    $ext = strtolower(preg_replace("/.+\./", "", basename($anexos[0])));

                    if ($ext == "pdf") {
                        $anexo_imagem = "imagens/pdf_icone.png";
                    } else if (in_array($ext, array("doc", "docx"))) {
                        $anexo_imagem = "imagens/docx_icone.png";
                    } else {
                        $anexo_imagem = $s3->getLink("thumb_".basename($anexos[0]), true);
                    }

                    $anexo_link = $s3->getLink(basename($anexos[0]), true);

                    $anexo        = getValue("anexo[$i]");
                 } else if(strlen($pergunta_id) > 0) {

                    $anexos = $s3->getObjectList("Pergunta_{$login_fabrica}_{$pergunta_id}_{$i}", false, $ano, $mes);

                    if (count($anexos) > 0) {

                        $ext = strtolower(preg_replace("/.+\./", "", basename($anexos[0])));
                        if ($ext == "pdf") {
                            $anexo_imagem = "imagens/pdf_icone.png";
                        } else if (in_array($ext, array("doc", "docx"))) {
                            $anexo_imagem = "imagens/docx_icone.png";
                        } else {
                            $anexo_imagem = $s3->getLink("thumb_".basename($anexos[0]), false, $ano, $mes);
                        }

                        $anexo_link = $s3->getLink(basename($anexos[0]), false, $ano, $mes);

                        $anexo        = basename($anexos[0]);
                        $anexo_s3     = true;
                    }
                }
                ?>
                <div id="div_anexo_<?=$i?>" class="tac" style="display: inline-block; margin: 0px 5px 0px 5px;">
                    <?php if (isset($anexo_link)) { ?>
                        <a href="<?=$anexo_link?>" target="_blank" >
                    <?php } ?>

                    <img src="<?=$anexo_imagem?>" class="anexo_thumb" style="width: 100px; height: 90px;" />

                    <?php if (isset($anexo_link)) { ?>
                        </a>
                        <script>setupZoom();</script>
                    <?php } ?>

                    <?php
                    if ($anexo_s3 === false) {
                    ?>
                        <button id="anexar_<?=$i?>" type="button" class="btn btn-mini btn-primary btn-block btn_acao_anexo" name="anexar" rel="<?=$i?>" >Anexar</button>
                    <?php
                    }
                    ?>

                    <img src="imagens/loading_img.gif" class="anexo_loading" style="width: 64px; height: 64px; display: none;" />

                    <input type="hidden" rel="anexo" name="anexo[<?=$i?>]" value="<?=$anexo?>" />
                    <input type="hidden" name="anexo_s3[<?=$i?>]" value="<?=($anexo_s3) ? 't' : 'f'?>" />
                    <?php
                    if ($anexo_s3 === true) {?>
                        <button id="excluir_<?=$i?>" type="button" class="btn btn-mini btn-danger btn-block btn_acao_anexo" name="excluir" rel="<?=$i?>" >Excluir</button>
                        <button id="baixar_<?=$i?>" type="button" class="btn btn-mini btn-info btn-block" name="baixar" onclick="window.open('<?=$anexo_link?>')">Baixar</button>

                    <?php
                    }
                    ?>
                </div>
            <?php
            }
        }
        ?>
        </div>
        <br />
    </div>
    <!-- Fim anexo-->
    <br />
    <?
    if (!is_null($pergunta_id)) { ?>
        <?php if($responder_pergunta == "true"){ ?>
            <div class="titulo_tabela">Cadastro de Resposta Técnica</div>
            <br />
            <div class="row-fluid">
                <div class="span2"></div>
                <div class="span8">
                    <div class="control-group" >
                        <label class="control-label" for="resposta_cadastro">Cadastre a Resposta</label>
                        <div class="controls controls-row">
                            <div class="span12">
                                <textarea id="resposta_cadastro" name="resposta_cadastro" class="span12" style="height: 50px;" ><?php  echo $resposta_cadastro?></textarea>
                            </div>
                        </div>
                    </div>
                    </div>
                <div class="span2"></div>
            </div>
            <br />
        <!-- ANexo -->
        <div id="div_anexos_r" class="tc_formulario">
            <div class="titulo_tabela">Anexo da Resposta</div>
            <br />
            <div class="tac" >
            <?php
            $fabrica_qtde_anexos_r = 1;
            if ($fabrica_qtde_anexos_r > 0) {
                $ano_r = '2015';
                $mes_r = '09';

                echo "<input type='hidden' name='anexo_chave_r' value='{$anexo_chave_r}' />";

                for ($i = 0; $i < $fabrica_qtde_anexos_r; $i++) {
                    unset($anexo_link_r);

                    $anexo_imagem_r = "imagens/imagem_upload.png";
                    $anexo_s3_r     = false;
                    $anexo_r        = "";

                    //if (strlen(getValue("anexo_r[{$i}]")) > 0 && getValue("anexo_s3_r[{$i}]") != "t") {
                    if (strlen($_POST['anexo_r['.$i.']']) > 0 && $_POST['anexo_s3_r['.$i.']'] != "t") {

                        $anexos_r = $s3->getObjectList(getValue("anexo_r[{$i}]"), true);

                        $ext_r = strtolower(preg_replace("/.+\./", "", basename($anexos_r[0])));

                        if ($ext_r == "pdf") {
                            $anexo_imagem_r = "imagens/pdf_icone.png";
                        } else if (in_array($ext_r, array("doc", "docx"))) {
                            $anexo_imagem_r = "imagens/docx_icone.png";
                        } else {
                            $anexo_imagem_r = $s3->getLink("thumb_".basename($anexos_r[0]), true);
                        }

                        $anexo_link_r = $s3->getLink(basename($anexos_r[0]), true);

                        $anexo_r        = getValue("anexo_r[$i]");
                     }elseif(strlen($pergunta_id) > 0) {

                        $anexos_r = $s3->getObjectList("Resposta_{$login_fabrica}_{$pergunta_id}_{$i}", false, $ano_r, $mes_r);
                        //print_r( $anexos);

                        if (count($anexos_r) > 0) {

                            $ext_r = strtolower(preg_replace("/.+\./", "", basename($anexos_r[0])));
                            if ($ext_r == "pdf") {
                                $anexo_imagem_r = "imagens/pdf_icone.png";
                            } else if (in_array($ext_r, array("doc", "docx"))) {
                                $anexo_imagem_r = "imagens/docx_icone.png";
                            } else {
                                $anexo_imagem_r = $s3->getLink("thumb_".basename($anexos_r[0]), false, $ano_r, $mes_r);
                            }

                            $anexo_link_r = $s3->getLink(basename($anexos_r[0]), false, $ano_r, $mes_r);

                            $anexo_r        = basename($anexos_r[0]);
                            $anexo_s3_r     = true;
                        }
                    }
                    ?>
                    <div id="div_anexo_r_<?=$i?>" class="tac" style="display: inline-block; margin: 0px 5px 0px 5px;">
                        <?php if (isset($anexo_link_r)) { ?>
                            <a href="<?=$anexo_link_r?>" target="_blank" >
                        <?php } ?>

                        <img src="<?=$anexo_imagem_r?>" class="anexo_thumb" style="width: 100px; height: 90px;" />

                        <?php if (isset($anexo_link_r)) { ?>
                            </a>
                            <script>setupZoom();</script>
                        <?php } ?>

                        <?php
                        if ($anexo_s3_r === false) {
                        ?>
                            <button id="anexar_r_<?=$i?>" type="button" class="btn btn-mini btn-primary btn-block btn_acao_anexo_r" name="anexar_r" rel="<?=$i?>" >Anexar</button>
                        <?php
                        }
                        ?>

                        <img src="imagens/loading_img.gif" class="anexo_loading" style="width: 64px; height: 64px; display: none;" />

                        <input type="hidden" rel="anexo_r" name="anexo_r[<?=$i?>]" value="<?=$anexo_r?>" />
                        <input type="hidden" name="anexo_s3_r[<?=$i?>]" value="<?=($anexo_s3_r) ? 't' : 'f'?>" />
                        <?php
                        if ($anexo_s3_r === true) {?>
                            <button id="excluir_r_<?=$i?>" type="button" class="btn btn-mini btn-danger btn-block btn_acao_anexo_r" name="excluir_r" rel="<?=$i?>" >Excluir</button>
                            <button id="baixar_r_<?=$i?>" type="button" class="btn btn-mini btn-info btn-block" name="baixar_r" onclick="window.open('<?=$anexo_link_r?>')">Baixar</button>

                        <?php
                        }
                        ?>
                    </div>
                <?php
                }
            }
            ?>
            </div>
            <br />
        </div>
        <!-- Fim anexo-->
        <?php } ?>

    <?php
    }
    ?>

    <p><br/>
        <?php
        if (isset($_GET['pergunta_id']) OR isset($_POST['pergunta_id'])){
            ?>
            <input type="hidden" id="pergunta_id" name="pergunta_id" value="<?php echo $pergunta_id ?>" />
            <?
            if ($_GET['resposta'] == 'sim' OR $erro_resposta == "sim") {
                ?>
                <input type="submit" class="btn btn_responder" name="responder" id="responder" value="Responder" />               
                <?php
            }else{
                ?>
                <input type="submit" class="btn btn_responder" name="alterar" id="alterar" value="Alterar" />
                <?php
            }

        }else{
            ?>
            <input type="submit" class="btn btn_responder" name="gravar" id="gravar" value="Gravar" />
            <?php
        }
        ?>
    </p><br/>

</FORM>
</div>
<?php
//Inicio anexo
if ($fabrica_qtde_anexos > 0) {
    for ($i = 0; $i < $fabrica_qtde_anexos; $i++) {
    ?>
        <form name="form_anexo" method="post" action="callcenter_cadastro_pergunta_tecnica.php" enctype="multipart/form-data" style="display: none;" >
            <input type="file" name="anexo_upload_<?=$i?>" value="" />

            <input type="hidden" name="ajax_anexo_upload" value="t" />
            <input type="hidden" name="anexo_posicao" value="<?=$i?>" />
            <input type="hidden" name="anexo_chave" value="<?=$anexo_chave?>" />
        </form>
    <?php
    }
}
if ($fabrica_qtde_anexos_r > 0) {
    for ($i = 0; $i < $fabrica_qtde_anexos_r; $i++) {
    ?>
        <form name="form_anexo_r" method="post" action="callcenter_cadastro_pergunta_tecnica.php" enctype="multipart/form-data" style="display: none;" >
            <input type="file" name="anexo_r_upload_<?=$i?>" value="" />
            <input type="hidden" name="ajax_anexo_upload_r" value="t" />
            <input type="hidden" name="anexo_posicao_r" value="<?=$i?>" />
            <input type="hidden" name="anexo_chave_r" value="<?=$anexo_chave_r?>" />
        </form>
    <?php
    }
}
//Fim anexo
?>
<!-- Tabela -->
<?php
// echo $pergunta_id."<<<<<<<<<<<<<<<<<<";
//if (is_null($pergunta_id)) {
    //Lista a Consulta das Peguntas Técnicas
    $sql_t = " SELECT   tbl_faq.faq,
                        tbl_faq.admin,
                        tbl_faq.hd_chamado,
                        tbl_faq.numero_cliente,
                        TO_CHAR(tbl_faq.data_input, 'DD/MM/YYYY HH24:MI') as data_input,
                        tbl_faq_causa.causa,
                        tbl_produto.referencia,
                        tbl_produto.descricao
                    FROM tbl_faq
                    JOIN tbl_faq_causa ON tbl_faq.faq =tbl_faq_causa.faq
                    LEFT JOIN tbl_produto ON tbl_faq.produto = tbl_produto.produto AND tbl_produto.fabrica_i = {$login_fabrica}
                    LEFT JOIN tbl_faq_solucao ON tbl_faq_solucao.faq = tbl_faq.faq
                    WHERE tbl_faq.fabrica = {$login_fabrica}
                    AND tbl_faq_solucao.solucao IS NULL;";
    $res_t = pg_query($con,$sql_t);

    if (pg_num_rows($res_t) > 0) {
    ?>
        <table class='table table-striped table-bordered table-hover table-fixed'>
            <thead>
                <tr class='titulo_coluna'>
                    <td colspan="7" class="tac">Pergunta(s) Aguardando Resposta(s)</td>
                </tr>
                <tr class='titulo_coluna'>
                    <td>Nº da Pergunta</td>
                    <td>Pergunta</td>
                    <td>HD Chamado</td>
                    <td>Produto</td>
                    <td>Admin</td>
                    <td>Data Cadastro</td>
                    <td>Ações</td>
                </tr>
            </thead>
            <tbody>
            <?php
            for ($i = 0 ; $i < pg_num_rows($res_t) ; $i++) {

                $faq_t = pg_fetch_result($res_t, $i, faq);
                $admin_t = pg_fetch_result($res_t, $i, admin);
                $numero_cliente_t = pg_fetch_result($res_t, $i, numero_cliente);
                $hd_chamado_t = pg_fetch_result($res_t, $i, hd_chamado);
                $desc_t = pg_fetch_result($res_t, $i, descricao);
                $ref_t = pg_fetch_result($res_t, $i, referencia);
                $causa_t = pg_fetch_result($res_t, $i, causa);
                $data_cadastro = pg_fetch_result($res_t, $i, 'data_input');

                if( !empty($admin_t) ){
                    $stmt = $pdo->query("SELECT nome_completo FROM tbl_admin where admin = {$admin_t} and fabrica = {$login_fabrica}");
                    $admin_t = $stmt->fetch(PDO::FETCH_ASSOC)['nome_completo'];
                }

                ?>
                <tr id="tr_<?=$faq_t?>" >
                    <td><?echo $numero_cliente_t?></td>
                    <td><?echo $causa_t?></td>
                    <td><a target="_blank" href="helpdesk_cadastrar.php?hd_chamado=<?=$hd_chamado_t?>"><?echo $hd_chamado_t?></a></td>
                    <td><?echo $ref_t." - ".$desc_t?></td>
                    <td> <?= $admin_t ?> </td>
                    <td> <?= $data_cadastro ?> </td>
                    <td>
                        <?php
                        sort($array_nome_id);
                        $array_nome_id_sort = $array_nome_id['0']['admin'];
                        if ($login_admin == $array_nome_id_sort OR pg_num_rows($res_ad) > 0){?>
                            <a href='callcenter_cadastro_pergunta_tecnica.php?pergunta_id=<?=$faq_t?>&pergunta=sim'>
                            <button id="btn_alter_<?=$faq_t?>" type="button" class="btn btn-mini btn-primary btn-block" name="auditor" >Alterar</button>
                            </a>
                            <br>
                            <button id="btn_remove_<?=$faq_t?>" type="button" class="btn btn-mini btn-danger btn-block" name="auditor" >Excluir</button>
                            <input type="hidden" id="id_tbl_faq_<?=$faq_t?>" name="id_faq_<?=$faq_t?>" value="<?php echo $faq_t ?>" />
                            <br>
                        <?php
                        }
                        unset($array_nome_id);
                        ?>
                        <a href='callcenter_cadastro_pergunta_tecnica.php?pergunta_id=<?=$faq_t?>&resposta=sim'>
                        <button id="resposta_<?=$faq_t?>" type="button" class="btn btn-mini btn-block" name="resposta_<?=$faq_t?>" >Responder</button>
                        </a>
                        <br>

                        <a rel='shadowbox' href='relatorio_log_alteracao_new.php?parametro=tbl_faq&id=<?=$faq_t?>' class="btn btn-mini btn-warning btn-block" name="btnAuditorLog">Visualizar Log</a>

                    </td>
                </tr>
            <?
            }
            ?>
            </tbody>
        </table>
    <?php
    }
//}
include "rodape.php" ?>