<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';
include 'funcoes.php';

$title = "CONSULTA PERGUNTAS TÉCNICAS";
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

include_once dirname(__FILE__) . '/../class/AuditorLog.php';

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

// echo "<pre>";
// print_r($_POST);
// echo "</pre>";

if ($_POST['listar_todos'] == "Listar Todos") {
  
    $sql_tabela = "SELECT 
                        tbl_faq.faq,
                        tbl_faq.situacao as outros_motivos,
                        tbl_faq.numero_cliente,
                        tbl_faq.hd_chamado,
                        tbl_faq.data_input,
                        tbl_faq_causa.causa,
                        tbl_faq_solucao.solucao,
                        tbl_produto.referencia,
                        tbl_produto.descricao,
                        tbl_linha.nome,
                        tbl_admin.nome_completo AS nome_admin
                    FROM tbl_faq 
                        JOIN tbl_faq_causa ON tbl_faq_causa.faq = tbl_faq.faq
                        JOIN tbl_faq_solucao ON tbl_faq_solucao.faq = tbl_faq.faq
                        JOIN tbl_admin ON tbl_admin.admin = tbl_faq.admin
                        LEFT JOIN tbl_produto ON tbl_produto.produto = tbl_faq.produto
                        LEFT JOIN tbl_linha ON tbl_linha.linha = tbl_produto.linha
                    WHERE tbl_faq.fabrica = {$login_fabrica}
                        AND tbl_faq_solucao.solucao IS NOT NULL;";
    //die(nl2br($sql_tabela));
    
    $res_tabela = pg_query($con,$sql_tabela);    
}

if ($_POST['consultar'] == "Consultar") {
    $ref_produto    = $_POST['produto_referencia'];
    $out_motivos    = $_POST['outros_motivos'];
    $linha_p        = $_POST['linha'];
    $pergunta_p     = $_POST['pergunta_cadastro'];
    $resposta_p     = $_POST['resposta_cadastro'];

    if (!empty($ref_produto)) {
        $and_prod = "AND tbl_produto.referencia = '{$ref_produto}' ";
    }
    if (!empty($out_motivos)) {
        $and_outros_m = "AND tbl_faq.situacao = '{$out_motivos}' ";
    }
    if (!empty($linha_p)) {
        $and_linha = "AND tbl_linha.linha = {$linha_p} ";
    }
    if (!empty($pergunta_p)) {
        $and_pergunta = "AND tbl_faq_causa.causa ILIKE '%{$pergunta_p}%' ";
    }
    if (!empty($resposta_p)) {
        $resposta_p = "AND tbl_faq_solucao.solucao ILIKE '%{$resposta_p}%' ";
    }

    $sql_tabela = "SELECT 
                        tbl_faq.faq,
                        tbl_faq.situacao as outros_motivos,
                        tbl_faq_causa.causa,
                        tbl_faq_solucao.solucao,
                        tbl_faq.numero_cliente,
                        tbl_faq.hd_chamado,
                        tbl_faq.data_input,
                        tbl_produto.referencia,
                        tbl_produto.descricao,                        
                        tbl_linha.nome,
                        tbl_admin.nome_completo AS nome_admin
                    FROM tbl_faq 
                        JOIN tbl_faq_causa ON tbl_faq_causa.faq = tbl_faq.faq
                        JOIN tbl_faq_solucao ON tbl_faq_solucao.faq = tbl_faq.faq
                        JOIN tbl_admin ON tbl_admin.admin = tbl_faq.admin
                        LEFT JOIN tbl_produto ON tbl_produto.produto = tbl_faq.produto
                        LEFT JOIN tbl_linha ON tbl_linha.linha = tbl_produto.linha                        
                    WHERE tbl_faq.fabrica = {$login_fabrica}
                        AND tbl_faq_solucao.solucao IS NOT NULL
                        $and_prod
                        $and_outros_m
                        $and_linha
                        $and_pergunta                        
                        $resposta_p;";

    //die(nl2br($sql_tabela));
    $res_tabela = pg_query($con,$sql_tabela);
}

/**
* Exclui Resposta.
**/
if ($btn_acao == "excluir_resposta" ) {

    if (isset($_POST['id_faq'])) {
        $id_faq = $_POST['id_faq'];
    } 
    $AuditorLog = new AuditorLog();
    $AuditorLog2 = new AuditorLog();

    $sql_auditor = "SELECT  tbl_faq.faq,
                            tbl_faq.produto,
                            tbl_faq.situacao,
                            tbl_faq.linha,
                            tbl_faq.familia,
                            tbl_faq.hd_chamado,
                            tbl_faq.fabrica,
                            tbl_faq_causa.faq_causa,
                            tbl_faq_causa.causa,
                            tbl_faq_solucao.faq_solucao,
                            tbl_faq_solucao.solucao,
                            tbl_faq_solucao.descricao,
                            tbl_faq_solucao.porcentagem  
                        FROM tbl_faq 
                        LEFT JOIN tbl_faq_causa ON tbl_faq_causa.faq = tbl_faq.faq 
                        LEFT JOIN tbl_faq_solucao ON tbl_faq_solucao.faq = tbl_faq.faq 
                        WHERE tbl_faq.faq = $id_faq";
    $res_auditor = pg_query($con,$sql_auditor);

    if (pg_num_rows($res_auditor) > 0) {

        $auditor_anterior = pg_fetch_assoc($res_auditor);
        $action_auditor = "delete";
        //Fim Auditor-Anterior

        $AuditorLog->RetornaDadosSelect($sql_auditor);

        $res = pg_query($con,"BEGIN");

        $sql_d = "  DELETE FROM tbl_faq_solucao
                        WHERE tbl_faq_solucao.faq = $id_faq;
                    
                    /*DELETE FROM tbl_faq_causa
                        WHERE tbl_faq_causa.faq = $id_faq;
                    
                    DELETE FROM tbl_faq
                        WHERE tbl_faq.faq = $id_faq;*/
                    ";
        $res_d = pg_query($con,$sql_d);
        
        if(pg_last_error($con)){
            $res = pg_query($con,"ROLLBACK");
            $retorno = array("error" => utf8_encode("Erro ao Excluir Pergunta!"));
            
        }else{
            $res = pg_query($con,"COMMIT");
            if($login_fabrica == 1){
                $retorno = array("ok" => utf8_encode("Exclusão efetuada com sucesso!"));
            } else {
                $retorno = array("ok" => utf8_encode("Pergunta excluida com sucesso!"));
            }

            //Auditor-Depois
            $sql_auditor = "SELECT  tbl_faq.faq,
                                            tbl_faq.produto,
                                            tbl_faq.situacao,
                                            tbl_faq.linha,
                                            tbl_faq.familia,
                                            tbl_faq.hd_chamado,
                                            tbl_faq.fabrica,
                                            tbl_faq_causa.faq_causa,
                                            tbl_faq_causa.causa,
                                            tbl_faq_solucao.faq_solucao,
                                            tbl_faq_solucao.solucao,
                                            tbl_faq_solucao.descricao,
                                            tbl_faq_solucao.porcentagem  
                                FROM tbl_faq 
                                LEFT JOIN tbl_faq_causa ON tbl_faq_causa.faq = tbl_faq.faq 
                                LEFT JOIN tbl_faq_solucao ON tbl_faq_solucao.faq = tbl_faq.faq 
                                WHERE tbl_faq.faq = $id_faq";
            $res_auditor = pg_query($con,$sql_auditor);
            $auditor_posterior = pg_fetch_assoc($res_auditor);
            $auditor_posterior['data_alteracao'] = date('d-m-Y h:i:s');
            $auditor_posterior['admin'] = $login_admin;
            //Chama função para gravar as informações
            $nome_servidor = $_SERVER['SERVER_NAME'];
            $nome_uri = $_SERVER['REQUEST_URI'];
            $nome_url = $nome_servidor.$nome_uri;
            $AuditorLog->RetornaDadosSelect()->enviarLog('UPDATE', 'tbl_faq', "$login_fabrica");
            $AuditorLog2->RetornaDadosSelect()->enviarLog('UPDATE', 'tbl_faq', "$login_fabrica*$id_faq");
            auditorLogCallcenter($id_faq,$auditor_anterior,$auditor_posterior,$tbl_faq_black,$nome_url,$action_auditor);
            unset($id_faq);
            unset($auditor_anterior);
            unset($auditor_posterior);
            unset($nome_url);
            unset($action_auditor);
        }
    }else{
        $retorno = array("error" => utf8_encode("Pergunta Técnica não cadastrada!"));        
    }

    //print_r($_POST);
    //    echo "ok";
    exit(json_encode($retorno));
}

//Ação de Excluir a Pergunta e Sua Resposta AuditorLog
if ($btn_acao == "excluir_pergunta_log" ) {

	if (isset($_POST['id_faq'])) {
		$id_faq = $_POST['id_faq'];
	} 

	include_once dirname(__FILE__) . '/../class/AuditorLog.php';
	$AuditorLog = new AuditorLog;
	$AuditorLog2 = new AuditorLog();

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
                                WHERE tbl_faq.faq = $id_faq
                                AND tbl_faq.fabrica = $login_fabrica";
   
    $AuditorLog->RetornaDadosSelect($sql_auditor);

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
        if($login_fabrica == 1){
            $retorno = array("ok" => utf8_encode("Exclusão efetuada com sucesso!"));
        } else {
            $retorno = array("ok" => utf8_encode("Pergunta excluida com sucesso!"));    
        }       

        //Auditor-Depois
        $AuditorLog->RetornaDadosSelect()->enviarLog('UPDATE', 'tbl_faq', "$login_fabrica");
        $AuditorLog2->RetornaDadosSelect()->enviarLog('UPDATE', 'tbl_faq', "$login_fabrica*$id_faq");
    }
    
    exit(json_encode($retorno));
}

/**
* Altera a Resposta
**/
if ($btn_acao == "alterar_resposta" ) {

    if (strlen($resposta) > 3) {

        $AuditorLog = new AuditorLog;                

        $sql_auditor = "SELECT  tbl_faq.faq,
                                tbl_faq.produto,
                                tbl_faq.situacao,
                                tbl_faq.linha,
                                tbl_faq.familia,
                                tbl_faq.hd_chamado,
                                tbl_faq.fabrica,
                                tbl_faq_causa.faq_causa,
                                tbl_faq_causa.causa,
                                tbl_faq_solucao.faq_solucao,
                                tbl_faq_solucao.solucao,
                                tbl_faq_solucao.descricao,
                                tbl_faq_solucao.porcentagem  
                            FROM tbl_faq 
                            LEFT JOIN tbl_faq_causa ON tbl_faq_causa.faq = tbl_faq.faq 
                            LEFT JOIN tbl_faq_solucao ON tbl_faq_solucao.faq = tbl_faq.faq 
                            WHERE tbl_faq.faq = $id_faq";
        $res_auditor = pg_query($con,$sql_auditor);

        $AuditorLog->RetornaDadosSelect($sql_auditor);

        if (pg_num_rows($res_auditor) > 0) {
            if($login_fabrica == 1){
                $resposta = str_replace("'", "''", $resposta);
            }
			$resposta = utf8_decode($resposta);
            $auditor_anterior = pg_fetch_assoc($res_auditor);
            $action_auditor = "update";
            //Fim Auditor-Anterior

            $res = pg_query($con,"BEGIN");

            $sql_up = "UPDATE tbl_faq_solucao
                            SET
                            solucao     = '$resposta'
                        WHERE faq = $id_faq;";
            $res_up = pg_query($con,$sql_up);
            
            if(pg_last_error($con)){
                $res = pg_query($con,"ROLLBACK");
                $retorno = array("error" => utf8_encode("Erro ao Alterar Resposta Técnica!"));
                
            }else{
                $res = pg_query($con,"COMMIT");
                $retorno = array("ok" => utf8_encode("Resposta Técnica Alterada com sucesso!"));            

                //Auditor-Depois
                $sql_auditor = "SELECT  tbl_faq.faq,
                                                tbl_faq.produto,
                                                tbl_faq.situacao,
                                                tbl_faq.linha,
                                                tbl_faq.familia,
                                                tbl_faq.hd_chamado,
                                                tbl_faq.fabrica,
                                                tbl_faq_causa.faq_causa,
                                                tbl_faq_causa.causa,
                                                tbl_faq_solucao.faq_solucao,
                                                tbl_faq_solucao.solucao,
                                                tbl_faq_solucao.descricao,
                                                tbl_faq_solucao.porcentagem  
                                    FROM tbl_faq 
                                    LEFT JOIN tbl_faq_causa ON tbl_faq_causa.faq = tbl_faq.faq 
                                    LEFT JOIN tbl_faq_solucao ON tbl_faq_solucao.faq = tbl_faq.faq 
                                    WHERE tbl_faq.faq = $id_faq";
                $res_auditor = pg_query($con,$sql_auditor);
                $auditor_posterior = pg_fetch_assoc($res_auditor);
                $auditor_posterior['data_alteracao'] = date('d-m-Y h:i:s');
                $auditor_posterior['admin'] = $login_admin;
                //Chama função para gravar as informações
                $nome_servidor = $_SERVER['SERVER_NAME'];
                $nome_uri = $_SERVER['REQUEST_URI'];
                $nome_url = $nome_servidor.$nome_uri;

                $AuditorLog->retornaDadosSelect()->EnviarLog("update", 'tbl_faq',"$login_fabrica*$id_faq");                

                auditorLogCallcenter($id_faq,$auditor_anterior,$auditor_posterior,$tbl_faq_black,$nome_url,$action_auditor);
                unset($id_faq);
                unset($auditor_anterior);
                unset($auditor_posterior);
                unset($nome_url);
                unset($action_auditor);
            }
        }else{
            $retorno = array("error" => utf8_encode("Resposta Técnica não cadastrada!"));        
        }        
    }else{
        $retorno = array("error" => utf8_encode("Resposta Técnica Incorretaa!"));        
    }
    exit(json_encode($retorno));
}

/**
* Cria a chave do anexo
*/
if (!strlen(getValue("anexo_chave"))) {
    $anexo_chave = sha1(date("Ymdhi")."{$login_fabrica}".(($areaAdmin === true) ? $login_admin : $login_posto));
} else {
    $anexo_chave = getValue("anexo_chave");
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
* Excluir anexo
*/
if ($_POST["ajax_anexo_exclui"] == 'true') {
    $anexo_nome_excluir = $_POST['anexo_nome_excluir'];
    $numero_processo = $_POST['numero_processo'];    
    $retorno = '';

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

//Verifica o Admin 
$sql_ad = "SELECT privilegios FROM tbl_admin WHERE fabrica = $login_fabrica AND privilegios = '*' AND admin = $login_admin";
$res_ad = pg_query($con,$sql_ad);


include "cabecalho_new.php";

$plugins = array(
    "autocomplete",
    "datepicker",
    "shadowbox",
    "mask",
    "ajaxform",
    "dataTable"
);

include("plugin_loader.php");

?>
<script type="text/javascript">
$(function() {
    Shadowbox.init();

    /**
     * Evento que chama a função de lupa para a lupa clicada
     */
    $("span[rel=lupa]").click(function() {
        $.lupa($(this));
    });

    // Ocultar botão Responder durante a gravação
    $("#responder").click(function(){
        $("#responder").hide();
    });

    // Ocultar botão Alterar durante a gravação
    $("#alterar").click(function(){
        $("#alterar").hide();
    });


    //Auto complete
    $.autocompleteLoad(Array("produto"));

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

        $.ajax({            
            url: "consulta_pergunta_tecnica.php",
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
    
});

function retorna_produto (retorno) {
    $("#produto_referencia").val(retorno.referencia);
    $("#produto_descricao").val(retorno.descricao);
}

function expandir(id_linha){

    var elemento = document.getElementById('tr_res_' + id_linha);
    var display = elemento.style.display;

    if (display == "none") {
      elemento.style.display = "";      
    } else {
      elemento.style.display = "none";     
    }

}

//Ação do Botão Excluir Pergunta e Resposta
$(document).on("click","button[id^=btn_remove_]",function(){
    var linha     = $(this).parents("td");    
    var id_faq    = $(linha).find("input[id^=id_tbl_faq_]").val();    
    let url_excluir = "callcenter_cadastro_pergunta_tecnica.php";
    let btn_excluir = "excluir_pergunta_log";
    
    <?php if ($login_fabrica == 1) {  ?>
   		url_excluir = "<?=$_SERVER['PHP_SELF']?>";   		
        
        let resp = confirm('Deseja excluir esta pergunta');
        if (resp == true){            
            if (ajaxAction()) {
                $.ajax({
                    async: false,
                    url: url_excluir,
                    type: "POST",
                    dataType: "JSON",
                    data: { 
                        btn_acao: btn_excluir,
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
        }
    <?php } else { ?>
            if (ajaxAction()) {
                $.ajax({
                    async: false,
                    url: url_excluir,
                    type: "POST",
                    dataType: "JSON",
                    data: { 
                        btn_acao: btn_excluir,
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
    <?php } ?>
});

//Ação do Botão Excluir Resposta
$(document).on("click","button[id^=excluir_resp]",function(){

    var linha     = $(this).parents("tr");    
    var resposta  = $(linha).find("textarea[id^=resposta_cadastro_]").val();
    var id_faq    = $(linha).find("input[id^=id_faq_]").val();
    var anexo     = $(linha).find("input[name^=anexo]").val();
    var anexo_s3  = $(linha).find("input[name^=anexo_s3]").val();
    let url_excluir = "callcenter_cadastro_pergunta_tecnica.php";
    let btn_excluir = "excluir_resposta";
    //alert(anexo);
    //alert(anexo_s3);
    
    var alert_erro = '';
    if(resposta == ''){
        alert_erro = "Resposta em branco, favor inserir resposta!";
        alert(alert_erro);
    }

    if(alert_erro == ''){
    <?php if($login_fabrica == 1) { ?>   
        url_excluir = "<?=$_SERVER['PHP_SELF']?>";        
                 
        let resp = confirm('Deseja excluir esta resposta');
        if (resp == true){            
              if (ajaxAction()) {
                $.ajax({
                    async: false,
                    url: url_excluir,
                    type: "POST",
                    dataType: "JSON",
                    data: { 
                        btn_acao: btn_excluir,
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
                            alert(data.ok);
                            //$("#tr_"+id_faq).remove();
                            $("#tr_res_"+id_faq).remove();                          
                        }            
                    }
                });
            }     
        }
    <?php } else { ?>  
              if (ajaxAction()) {
                $.ajax({
                    async: false,
                    url: url_excluir,
                    type: "POST",
                    dataType: "JSON",
                    data: { 
                        btn_acao: btn_excluir,
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
                            alert(data.ok);
                            $("#tr_"+id_faq).remove();
                            $("#tr_res_"+id_faq).remove();                          
                        }            
                    }
                });
            } 
    <?php } ?>
    }
});

//Ação do Botão Altear Resposta
$(document).on("click","button[id^=alterar_resp]",function(){

    var linha     = $(this).parents("tr");    
    var resposta  = $(linha).find("textarea[id^=resposta_cadastro_]").val();
    var id_faq    = $(linha).find("input[id^=id_faq_]").val();
    //var anexo     = $(linha).find("input[name^=anexo]").val();
    //var anexo_s3  = $(linha).find("input[name^=anexo_s3]").val();
    //alert(anexo);
    //alert(anexo_s3);
    
    var alert_erro = '';

    resposta = resposta.trim();

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
                btn_acao: "alterar_resposta",
                //anexo: anexo,
                //anexo_s3: anexo_s3,
                resposta: resposta,
                id_faq: id_faq
            },
            complete: function (data) {
                //var retorno = data.responseText;
                data = $.parseJSON(data.responseText);

                if (data.error) {
                    alert(data.error);
                } else {
                     alert(data.ok);
                }            
            }
        });
      }
    }
});

</script>

<?php
if (count($msg_erro["msg"]) > 0) {
?>
    <div class="alert alert-error">
        <h4><?=implode("<br />", $msg_erro["msg"])?></h4>
    </div>
<?php
}
?>
<div class="row">
    <b class="obrigatorio pull-right">  * Campos obrigatórios </b>
</div>
<form name="frm_relatorio" METHOD="POST" ACTION="<? echo $PHP_SELF ?>" align='center' class='form-search form-inline tc_formulario'>
    <div class='titulo_tabela '>Consulta de Pergunta Técnica</div>
    <br/>
    <div class='row-fluid'>
        <div class='span2'></div>
        <div class='span4'>
            <div class='control-group'>
                <label class='control-label' for='produto_referencia'>Ref. Produto</label>
                <div class='controls controls-row'>
                    <div class='span10 input-append'>
                        <input type="text" id="produto_referencia" name="produto_referencia" class='span12' maxlength="20" value="<? echo $produto_referencia ?>" >
                        <span class='add-on' rel="lupa" ><i class='icon-search'></i></span>
                        <input type="hidden" name="lupa_config" tipo="produto" parametro="referencia" />
                    </div>
                </div>
            </div>
        </div>
        <div class='span4'>
            <div class='control-group'>
                <label class='control-label' for='produto_descricao'>Descrição Produto</label>
                <div class='controls controls-row'>
                    <div class='span11 input-append'>
                        <input type="text" id="produto_descricao" name="produto_descricao" class='span12' value="<? echo $produto_descricao ?>" >
                        <span class='add-on' rel="lupa" ><i class='icon-search' ></i></span>
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
            <div class="control-group">
                <label class="control-label" for="tabela">Outros Motivos</label>
                <div class="controls controls-row">
                    <div class='span12'>
                        <select id="outros_motivos" name="outros_motivos" class="span11">
                        <?php
                            $retorno = "";                            
                            //$arq_jason = file_get_contents("../bloqueio_pedidos/outros_motivos_bd.txt");
                            //$arq_jason = file_get_contents("outros_motivos_bd.txt");
                            $arq_jason = explode("\n", $arq_jason);

                            $retorno = "<option></option>";
                            foreach ($arq_jason as $key => $value) {
                                $decod_motivos = json_decode($value,true);

                                $outros_motivos_cod = array_keys($decod_motivos);
                                $outros_motivos_cod = $outros_motivos_cod[0];
                                $outros_motivos_desc = $decod_motivos[$outros_motivos_cod];
                                if (empty($outros_motivos_desc)) {
                                    continue;
                                }

                                if ($_POST['outros_motivos'] == $outros_motivos_cod) {
                                    $selected = "SELECTED";
                                }else{
                                    $selected = "";
                                }

                                $retorno .= "<option value='$outros_motivos_cod' $selected >".utf8_decode($outros_motivos_desc)."</option>";        
                                
                            }
                            echo $retorno;
                        ?>
                        </select>
                    </div>
                </div>
            </div>  
        </div>

         <div class="span4">
            <div class="control-group">
                <label class="control-label" for="linha">Linha</label>
                <div class="controls controls-row">
                    <div class="span12">
                        <select id="linha" name="linha" class="span12">
                        <option value=''></option>
                        <?php
                            $sql_linha = "SELECT linha, nome
                                            FROM tbl_linha
                                            WHERE fabrica = {$login_fabrica}
                                            AND ativo = 't'; ";
                            $res_linha = pg_query($con,$sql_linha);

                            if (pg_num_rows($res_linha) > 0) {
                                $linhas = pg_fetch_all($res_linha);
                                foreach ($linhas as $resultado) {
                                    if ($_POST['linha'] == $resultado['linha']) {
                                        $selected = "SELECTED";
                                    }else{
                                        $selected = "";
                                    }
                                    
                                    echo "<option value='".$resultado['linha']."'".$selected.">".$resultado['nome']."</option>";
                                    
                                }
                            }
                        ?>                       
                        
                        </select>
                    </div>
                </div>
            </div>  
        </div>
        <div class='span2'></div>
    </div>
    <br>
    <div class="row-fluid">
        <div class='span2'></div>
        <div class="span4">
            <div class="control-group">
                <label class="control-label" for="tabela">Admin</label>
                <div class="controls controls-row">
                    <div class='span12'>
                        <select id="admin_fabrica" name="admin_fabrica" class="span11">                         
                            <option value=''></option>
                            <?php
                                $sql_admin = "SELECT admin, nome_completo
                                                FROM tbl_admin
                                                WHERE fabrica = {$login_fabrica}
                                                AND ativo = 't'
                                                ORDER BY nome_completo; ";
                                $res_admin = pg_query($con,$sql_admin);

                                if (pg_num_rows($res_admin) > 0) {
                                    $admins = pg_fetch_all($res_admin);
                                    foreach ($admins as $resultado) {
                                        if ($_POST['admin_fabrica'] == $resultado['admin']) {
                                            $selected = "SELECTED";
                                        }else{
                                            $selected = "";
                                        }
                                        
                                        echo "<option value='".$resultado['admin']."'".$selected.">".$resultado['nome_completo']."</option>";
                                        
                                    }
                                }
                            ?>
                            </select>
                    </div>
                </div>
            </div>  
        </div>
        <div class='span6'></div>
    </div>
    <br>

    <div class="row-fluid">
        <div class="span2"></div>
        <div class="span8">
            <div class="control-group" >
                <label class="control-label" for="pergunta_cadastro">Pergunta</label>
                <div class="controls controls-row">
                    <div class="span12">
                        <textarea id="pergunta_cadastro" name="pergunta_cadastro" class="span12" style="height: 25px;" ><?php  echo $pergunta_cadastro?></textarea>
                    </div>
                </div>
            </div>
            </div>
        <div class="span2"></div>
    </div>
    <div class="row-fluid">
        <div class="span2"></div>
        <div class="span8">
            <div class="control-group" >
                <label class="control-label" for="resposta_cadastro">Resposta</label>
                <div class="controls controls-row">
                    <div class="span12">
                        <textarea id="resposta_cadastro" name="resposta_cadastro" class="span12" style="height: 25px;" ><?php  echo $resposta_cadastro?></textarea>
                    </div>
                </div>
            </div>
            </div>
        <div class="span2"></div>
    </div>   
    <br />
    <p><br/>
        <input type="submit" class="btn" name="consultar" value="Consultar" />
        <input type="submit" class="btn" name="listar_todos" value="Listar Todos" />
    </p><br/>
</FORM>
</div>
<?php
if (isset($res_tabela)) {
    if (pg_num_rows($res_tabela) > 0) {
        $lista_tabela = pg_fetch_all($res_tabela);

        //cabeçalho
        $id_admin = array();
         foreach ($lista_tabela as $valor_t) {

            $client = Client::makeTelecontrolClient("auditor","auditor");
            $client->urlParams = array(
                "aplication" => "02b970c30fa7b8748d426f9b9ec5fe70",
                "table" =>"$tbl_faq_black",
                "primaryKey" => $login_fabrica."*".$valor_t['faq'],
                "limit" => "50"
            );
            $nome_admin = array();
            
            try{
                $res = $client->get();
                //int_r($res);exit;
                if(count($res)){
                    foreach ($res as $key => $value) {

                        foreach($value['data']['content']['depois'] AS $keyD => $valueD){
                             if($valueD == "t"){
                                $value['data']['content']['depois'][$keyD] = "Sim";
                             }

                             if($valueD == "f"){
                                $value['data']['content']['depois'][$keyD] = "Não";
                             }

                        }
                        
                        $sql = "SELECT nome_completo FROM tbl_admin where admin = ".$value['data']['content']['depois']['admin']." and fabrica = ".$login_fabrica;
                        $id_admin[] = $value['data']['content']['depois']['admin'];

                        $result = pg_query($con,$sql);
                        $nome = pg_result($result,0,nome_completo);

                        if($nome != ""){
                            $value['data']['admin'] = $nome;
                            $value['data']['content']['depois']['admin'] = $nome;
                        }
                        
                        $array_depois = $value['data']['content']['depois'];
                        $nome_admin[] = $nome;
                        

                        $value['data']['alteracoes'] = $alteracoes;
                        $res[$key] = $value;
                    }
                    //print_r($res);

                }else{
                    $error = "Nenhum log encontrado";
                }
            }catch(Exception $ex){
                $error = $ex->getMessage();
            }
        }

        if (in_array($admin_fabrica,$id_admin) OR empty($admin_fabrica ) ){
        	if($login_fabrica == 1){
        ?>	
		    <div class='row-fluid'>
		        <div class='span2'></div>
		        <div class='span8'>
            		<div class='control-group'>                		
                		<div class='controls controls-row'>
        <? } ?>


            <table class='table table-striped table-bordered table-hover table-fixed'>
            <thead>
                <tr class='titulo_coluna'>
                    <td>Nº da Pergunta</td>
                    <td>Pergunta</td>
                    <? if($login_fabrica == 1) { ?>
                    	<td>HD Chamado</td>
                    <? } ?>
                    <td>Anexo</td>                    
                    <td>Produto</td>
                    <td>Linha</td>
                    <td>Admin</td>
                    <? if($login_fabrica == 1) { ?>
                    	<td>Última Alteração</td>
                    <? } ?>
                    <td>Ações</td>
                </tr>
            </thead>
            <tbody>
        <?php
        }//fim cabeçalho

        //corpo
        foreach ($lista_tabela as $valor_t) {

            $client = Client::makeTelecontrolClient("auditor","auditor");
            $client->urlParams = array(
                "aplication" => "02b970c30fa7b8748d426f9b9ec5fe70",
                "table" =>"$tbl_faq_black",
                "primaryKey" => $login_fabrica."*".$valor_t['faq'],
                "limit" => "50"
            );
            $nome_admin = array();
            $array_nome_id = array();
            $id_admin = array();
            try{
                $res = $client->get();
                //int_r($res);exit;
                if(count($res)){
                    foreach ($res as $key => $value) {

                        foreach($value['data']['content']['depois'] AS $keyD => $valueD){
                             if($valueD == "t"){
                                $value['data']['content']['depois'][$keyD] = "Sim";
                             }

                             if($valueD == "f"){
                                $value['data']['content']['depois'][$keyD] = "Não";
                             }

                        }

                        $array_nome_id[$key]['data_alteracao'] = $value['data']['content']['depois']['data_alteracao'];
                        $array_nome_id[$key]['admin'] = $value['data']['content']['depois']['admin'];
                        
                        $sql = "SELECT nome_completo FROM tbl_admin where admin = ".$value['data']['content']['depois']['admin']." and fabrica = ".$login_fabrica;
                        $id_admin[] = $value['data']['content']['depois']['admin'];

                        $result = pg_query($con,$sql);
                        $nome = pg_result($result,0,nome_completo);

                        if($nome != ""){
                            $value['data']['admin'] = $nome;
                            $value['data']['content']['depois']['admin'] = $nome;
                        }
                        
                        $array_depois = $value['data']['content']['depois'];
                        $nome_admin[] = $nome;
                        

                        $value['data']['alteracoes'] = $alteracoes;
                        $res[$key] = $value;
                    }
                    //print_r($res);

                }else{
                    $error = "Nenhum log encontrado";
                }
            }catch(Exception $ex){
                $error = $ex->getMessage();
            }

            if (in_array($admin_fabrica,$id_admin) OR empty($admin_fabrica ) ){ ?>                            	
                <tr id="tr_<?=$valor_t['faq']?>" >                	
                    <td style="cursor:pointer;" onClick="expandir(<?=$valor_t['faq']?>)"><?echo $valor_t['numero_cliente']?></td>                 
                    <?php if($login_fabrica == 1) {
                                $largura_pergunta = "width='30%'";
                                $largura_produto  = "width='15%'"; ?>
                                <script>
                                    function expandirCausa(id_linha){
                                        var texto = document.getElementById("ler_resp_" + id_linha);

                                        if(texto != null){
                                            var txt = texto.value;
                                        }

                                        newWindow = window.open('','', 'toolbar=yes,scrollbars=yes,resizable=yes,top=500,left=500,width=700,height=200');
                                        newWindow.document.title = 'Expandir Pergunta';
                                        newWindow.document.write ("<div class='tac' style='text-align: justify;'>" + txt + "</div>");
                                    }
                                </script>                                
                                <input id="ler_resp_<?=$valor_t['faq']?>" name="ler_resp_<?=$valor_t['faq']?>" type="hidden" value="<?=$valor_t['causa']?>" />
                                <td style="cursor:pointer;" <?=$largura_pergunta ?> onClick="javascript:expandirCausa(<?=$valor_t['faq']?>)"><?echo substr($valor_t['causa'], 0, 250)?></td>
                    <?php } else { ?> 
                            <td style="cursor:pointer;" onClick="expandir(<?=$valor_t['faq']?>)"><?echo $valor_t['causa']?></td>                    
                    <? } if($login_fabrica == 1) { ?>
                    	<td><a target="_blank" href="helpdesk_cadastrar.php?hd_chamado=<?=$valor_t['hd_chamado']?>"><?echo $valor_t['hd_chamado']?></a></td>
                    <? } ?>
                    <td>
                        <div class="tac" >
                            <?php
                            $ano = '2015';
                            $mes = '09';
                            
                            unset($anexo_link);

                            $anexo_imagem = "imagens/imagem_upload.png";
                            $anexo_s3     = false;
                            $anexo        = "";
                                
                            if(strlen($valor_t['faq']) > 0) {

                                $anexos = $s3->getObjectList("Pergunta_{$login_fabrica}_{$valor_t['faq']}_0", false, $ano, $mes);
                                //print_r( $anexos);
                               
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
                            <div id="div_anexo_<?=$valor_t['faq']?>" class="tac" style="display: inline-block; margin: 0px 5px 0px 5px;">
                                <?php if (isset($anexo_link)) { ?>
                                    <a href="<?=$anexo_link?>" target="_blank" >
                                <?php } 
                                	if($login_fabrica == 1) {
                                ?>
                                		<img src="<?=$anexo_imagem?>" class="anexo_thumb" style="width: 70px; height: 40px;" />
                                <?php 
                                	} else {
                                ?>
                                		<img src="<?=$anexo_imagem?>" class="anexo_thumb" style="width: 100px; height: 90px;" />
                                <?php
                                	}
                                ?>

                                <?php if (isset($anexo_link)) { ?>
                                    </a>
                                    <script>setupZoom();</script>
                                <?php } ?>                           

                                <img src="imagens/loading_img.gif" class="anexo_loading" style="width: 64px; height: 64px; display: none;" />

                                <input type="hidden" rel="anexo" name="anexo[<?=$valor_t['faq']?>]" value="<?=$anexo?>" />
                                <input type="hidden" name="anexo_s3[<?=$valor_t['faq']?>]" value="<?=($anexo_s3) ? 't' : 'f'?>" />
                                <?php
                                if ($anexo_s3 === true) {?>

                                    <button id="baixar_<?=$valor_t['faq']?>" type="button" class="btn btn-mini btn-info btn-block" name="baixar" onclick="window.open('<?=$anexo_link?>')">Baixar</button>
                                    
                                <?php   
                                }
                                ?>                      
                            </div>
                        </div>
                        <form name="form_anexo" method="post" action="consulta_pergunta_tecnica.php" enctype="multipart/form-data" style="display: none;" >
                            <input type="file" name="anexo_upload_<?=$valor_t['faq']?>" value="" />

                            <input type="hidden" name="ajax_anexo_upload" value="t" />
                            <input type="hidden" name="anexo_posicao" value="<?=$valor_t['faq']?>" />
                            <input type="hidden" name="anexo_chave" value="<?=$anexo_chave?>" />
                        </form>
                        
                    </td>
                    <td <?=$largura_produto ?>><?echo $valor_t['referencia']." - ".$valor_t['descricao']?></td>
                    <td><?echo $valor_t['nome']?></td>                                        
                    <td>
                        <?php                        
                            if($login_fabrica == 1){
                                print_r($valor_t['nome_admin']);    
                            } else {
                                $nome_admin = array_unique($nome_admin);
                                $nome_admin = implode('<br>', $nome_admin);
                                echo ($nome_admin);
                            }
                        ?>

                    </td>
                    <? if($login_fabrica == 1) { ?>
                    	<td><? echo date('d/m/Y H:i:s', strtotime($valor_t['data_input'])); ?></td>
                    <? } ?>
                    <td>
                        <?php
                        sort($array_nome_id);
                        $array_nome_id_sort = $array_nome_id['0']['admin'];
                        if ($login_admin == $array_nome_id_sort OR pg_num_rows($res_ad) > 0){
                        //if (pg_num_rows($res_ad) > 0) {
                        ?>
                            <a href='callcenter_cadastro_pergunta_tecnica.php?pergunta_id=<?=$valor_t['faq']?>'>
                            <button id="btn_alter_<?=$valor_t['faq']?>" type="button" class="btn btn-mini btn-primary btn-block" name="btn_alter_<?=$valor_t['faq']?>" >Alterar</button>
                            </a>                        
                            <br>
                            <button id="btn_remove_<?=$valor_t['faq']?>" type="button" class="btn btn-mini btn-danger btn-block" name="btn_remove_<?=$valor_t['faq']?>" >Excluir</button>
                            <input type="hidden" id="id_tbl_faq_<?=$valor_t['faq']?>" name="id_faq_<?=$valor_t['faq']?>" value="<?php echo $valor_t['faq'] ?>" /> 
                            <br>
                            <button id="btn_visualiza_resp<?=$valor_t['faq']?>" type="button" class="btn btn-mini btn-block" onClick="expandir(<?=$valor_t['faq']?>)" name="btn_visualiza_resp<?=$valor_t['faq']?>" >Resposta</button>
                            <br>
                        <?php
                        } if($login_fabrica != 1) {
                        ?>
                        	<a target='_BLANK' href='relatorio_log_alteracao.php?parametro=<?=$tbl_faq_black?>&id=<?=$valor_t['faq']?>'>
                            	<button id="auditor_<?=$valor_t['faq']?>" type="button" class="btn btn-mini btn-warning btn-block" name="auditor" >Visualizar Log</button>
                        	</a>
                        <?php 
                         } else {
                        ?>                              
 							<a rel='shadowbox' href='relatorio_log_alteracao_new.php?parametro=tbl_faq&id=<?=$valor_t['faq']?>' class="btn btn-mini btn-warning btn-block" name="auditor">Visualizar Log</a>
                        <?php
                         }
                        ?>
                        
                    </td>
                </tr>

                <?php 
                	if($login_fabrica == 1) { 
                		$span_x = 'span6'; 
                		$col_x 	= 9;
                	} else {
                		$span_x = 'span10'; 
                		$col_x 	= 6;
                	}
                ?>

                <tr id="tr_res_<?=$valor_t['faq']?>" style="display: none;">            
                    <td colspan="<?=$col_x?>">
                        <form name="frm_resp_<?=$valor_t['faq']?>" method="POST" ACTION="<?=$PHP_SELF?>" align="center" class="form-search form-inline tc_formulario">
                            <div class="titulo_tabela">Resposta</div>
                            <br>
                            <div class="container">
                                <div class="row-fluid"> 
                                    <div class="<?=$span_x?>">
                                        <div class="control-group" >
                                            <div class="controls controls-row">
                                                <div class="span12">
                                                <?php
                                                if ($login_admin == $array_nome_id_sort OR pg_num_rows($res_ad) > 0){
                                                ?>
                                                    <textarea id="resposta_cadastro_<?=$valor_t['faq']?>" name="resposta_cadastro" class="span12" style="height: 100px;" ><?php  echo $valor_t['solucao']?></textarea>
                                                <?php
                                                }else{?>
                                                <textarea id="resposta_cadastro_<?=$valor_t['faq']?>" name="resposta_cadastro" class="span12" style="height: 100px;" readonly ><?php  echo $valor_t['solucao']?></textarea>
                                                <?php
                                                }
                                                ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="span2">
                                        <div class="control-group">
                                            <div class="controls controls-row">
                                                <div class="12">
                                                    <?php
                                                    $ano = '2015';
                                                    $mes = '09';

                                                    unset($anexo_link);

                                                    $anexo_imagem = "imagens/imagem_upload.png";
                                                    $anexo_s3     = false;
                                                    $anexo        = "";
                                                    
                                                        
                                                    if(strlen($valor_t['faq']) > 0) {

                                                        $anexos = $s3->getObjectList("Resposta_{$login_fabrica}_{$valor_t['faq']}", false, $ano, $mes);
                                                        
                                                               
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
                                                    <div id="div_anexo_<?=$valor_t['faq']?>" class="tac" style="display: inline-block; margin: 0px 5px 0px 5px;">
                                                        <?php if (isset($anexo_link)) { ?>
                                                            <a href="<?=$anexo_link?>" target="_blank" >
                                                        <?php } ?>

                                                        <img src="<?=$anexo_imagem?>" class="anexo_thumb" style="width: 100px; height: 90px;" />

                                                        <?php if (isset($anexo_link)) { ?>
                                                            </a>
                                                            <script>setupZoom();</script>
                                                        <?php } ?>                                                           

                                                        <img src="imagens/loading_img.gif" class="anexo_loading" style="width: 64px; height: 64px; display: none;" />

                                                        <input type="hidden" rel="anexo" name="anexo[<?=$valor_t['faq']?>]" value="<?=$anexo?>" />
                                                        <input type="hidden" name="anexo_s3[<?=$valor_t['faq']?>]" value="<?=($anexo_s3) ? 't' : 'f'?>" />
                                                        <?php
                                                        if ($anexo_s3 === true) {?>
                                                            <button id="baixar_r<?=$valor_t['faq']?>" type="button" class="btn btn-mini btn-info btn-block" name="baixar" onclick="window.open('<?=$anexo_link?>')">Baixar</button>
                                                            
                                                        <?php   
                                                        }
                                                        ?>  
                                                    </div> 
                                                </div>                                                                                            
                                            </div>    
                                        </div>                                    
                                    </div> 
                                    <? if($login_fabrica == 1) { 
                                   	?>
	                                    <div class="span2"> 
                                            <div class="container">
				                                <div class="row-fluid"> 						                                    
			                                        <div class="control-group" >
			                                            <div class="controls controls-row">
			                                                <div class="span2">	
																<div class="titulo_tabela">Admin</div>
																<div style="background-color: white; text-align: center;">
																	<?php		
                                                                    	require_once '../class/AuditorLog.php';
                                                                        $AuditorLog = new AuditorLog;
                                        
                                                                        $res = $AuditorLog->getLog('tbl_faq', $login_fabrica."*".$valor_t['faq'], 1);
                                                                        //print_r($res);
																		$usuario = $res[0]['user'];
																		$sqlAdmin = "SELECT nome_completo
																						FROM tbl_admin
																						WHERE admin = {$usuario}";
																		$res_x = pg_query($con, $sqlAdmin);

																		echo pg_fetch_result($res_x, 0, 'nome_completo');
                                                                        //echo $valor_t['faq'];
																	?>
																</div>
															</div>
															<div class="span2">												
																<div class="titulo_tabela">Última Alteração</div>
																<div style="background-color: white; text-align: center;">
																	<?
																		echo date('d/m/Y H:i:s', $res[0]['created']);    
																	?>
																</div>
															</div>
														</div>
													</div>
												</div>
                                            </div>
	                                	</div>
	                                <? } ?>
                                </div>                            
                                <br>
                            </div>
                            <p>
                            <input type="hidden" id="id_faq_<?=$valor_t['faq']?>" name="id_faq_<?=$valor_t['faq']?>" value="<?php echo $valor_t['faq'] ?>" /> 
                            <?php
                            if ($login_admin == $array_nome_id_sort OR pg_num_rows($res_ad) > 0){
                            ?>
                            <button id="alterar_resp<?=$valor_t['faq']?>" type="button" class="btn btn-info" name="alterar" >Alterar</button>
                            <button id="excluir_resp<?=$valor_t['faq']?>" type="button" class="btn btn-danger" name="responder" >Excluir</button>
                            <?php 
                            }
                            ?>
                            </p>
                            <br>           
                        </form>
                    </td>
                </tr>
            <?php
            }     
        }
        ?>
        </tbody>
    </table>
    <? if($login_fabrica == 1) { ?>
				</div>
			</div>
		</div>
		<div class='span2'></div>
	</div>
    <?php }
    	if($login_fabrica == 1){ ?>
    		<br />
		    <div class='row-fluid'>
		        <div class='span5'></div>
		        <div class='span2'>
            		<div class='control-group'>                		
                		<div class='controls controls-row'>
    						<a rel='shadowbox' href='relatorio_log_alteracao_new.php?parametro=tbl_faq' class="btn btn-mini btn-warning btn-block" name="auditor">Visualizar Log Geral</a>
    					</div>
    				</div>
    			</div>
    			<div class='span5'></div>  
    			<br />  		
    <?php 
		}
    }
}

include "rodape.php"; ?>