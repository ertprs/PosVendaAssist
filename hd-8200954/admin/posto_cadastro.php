<?php

include_once "dbconfig.php";
include_once "includes/dbconnect-inc.php";
$admin_privilegios = "cadastros,call_center";
include_once "autentica_admin.php";
include_once "../helpdesk.inc.php";// Funcoes de HelpDesk
include_once "../helpdesk/mlg_funciones.php";//  Para o mapa do Brasil
require_once dirname(__FILE__) . '/../class_resize.php';
include_once 'funcoes.php';
include_once dirname(__FILE__) . '/../classes/Posvenda/DistribuidorSLA.php';
include_once dirname(__FILE__) . '/../class/AuditorLog.php';
include_once 'sige_sync.php';
use Posvenda\DistribuidorSLA;
use Posvenda\Cockpit;
use Lojavirtual\LojaTabelaPreco;
$obTabelaLoja = new LojaTabelaPreco();


if(in_array($login_fabrica, array(1,35))){
    $contrato_posto = true;
} 

if($contrato_posto){
    include_once "../class/tdocs.class.php";
}

if (isset($_POST["ajax_busca_cidade"]) && !empty($_POST["estado"])) {

    $estado = strtoupper($_POST["estado"]);

    $pais = $_POST['pais'];

    $arrayEstados = $array_estados($pais);

    if ($pais != "BR") {
        
        $sql = "SELECT UPPER(fn_retira_especiais(nome)) AS cidade 
                FROM tbl_cidade 
                WHERE UPPER(estado_exterior) = UPPER('{$estado}')
                AND UPPER(pais) = UPPER('{$pais}')
                ";
        $res = pg_query($con, $sql);

        if (pg_num_rows($res) > 0) {
            $array_cidades = array();

            while ($result = pg_fetch_object($res)) {
                $array_cidades[] = $result->cidade;
            }

            $retorno = array("cidades" => $array_cidades);
        } else {
            $retorno = array("error" => traduz("Nenhuma cidade encontrada para o estado: %", null, null, [$estado]));
        }

    } else {
        
        if (array_key_exists($estado, $arrayEstados)) {
            $sql = "SELECT DISTINCT * FROM (
                        SELECT UPPER(fn_retira_especiais(nome)) AS cidade FROM tbl_cidade WHERE UPPER(estado) = UPPER('{$estado}') AND pais = UPPER('{$pais}') AND cod_distrito IS NULL
                        UNION (
                            SELECT UPPER(fn_retira_especiais(cidade)) AS cidade FROM tbl_ibge WHERE UPPER(estado) = UPPER('{$estado}')
                        )
                    ) AS cidade
                    ORDER BY cidade ASC";
            $res = pg_query($con, $sql);
            if (pg_num_rows($res) > 0) {
                $array_cidades = array();

                while ($result = pg_fetch_object($res)) {
                    $array_cidades[] = $result->cidade;
                }

                $retorno = array("cidades" => $array_cidades);
            } else {
                $retorno = array("error" => utf8_encode(traduz("nenhuma.cidade.encontrada.para.o.estado") . ": {$estado}"));
            }
        } else {
            $retorno = array("error" => utf8_encode(traduz("estado.nao.encontrado")));
        }

    }

    exit(json_encode($retorno));
}

if($_POST["enviar_aprovacao"]){

    $posto = $_POST["posto"];
    $codigo_posto = $_POST["codigo_posto"];
    $msg_alteracao = trim($_POST["msg_alteracao"]);
    $observacao_interna = $_POST["observacao_interna"];

    $resS = pg_query($con,"BEGIN TRANSACTION");

    if(strlen($msg_alteracao)>0){
        $texto = traduz("Contrato Atualizado").": ". $msg_alteracao;
    }else{
        $texto = traduz("Contrato Atualizado");
    }

    $sql_credenciamento = "INSERT INTO tbl_credenciamento (fabrica, status, confirmacao_admin, posto, texto) values ($login_fabrica, 'Pre Cadastro em apr', $login_admin, $posto, '$texto') returning credenciamento";
    $res_credenciamento = pg_query($con, $sql_credenciamento);

    $sql_posto_fabrica = "UPDATE tbl_posto_fabrica SET credenciamento = 'pre cadastro em apr', observacao_credenciamento = '$observacao_interna' WHERE posto = $posto and fabrica = $login_fabrica";
    $res_posto_fabrica = pg_query($con, $sql_posto_fabrica);

    if(strlen(pg_last_error($con))==0){
        $id_credenciamento = pg_fetch_result($res_credenciamento, 0, credenciamento);

        //tdocsobs
        include_once "../class/tdocs_obs.class.php";

        $observacao_interna = pg_escape_string($observacao_interna);

        $tdocs_obs = new TDocs_obs($con, $login_fabrica, 'credenciamento');
        $retorno = $tdocs_obs->gravaObservacao($observacao_interna, 'tbl_credenciamento', $id_credenciamento);

        if(!$retorno){
            $msg_erro = "erro";
        }
    }else{
        $msg_erro = "erro";
    }

    if(strlen($msg_erro)>0){
        $resS = pg_query($con,"ROLLBACK TRANSACTION");
        echo json_encode(array('retorno' => 'erro'));
    }else{
        $resS = pg_query($con,"COMMIT TRANSACTION");
        echo json_encode(array('retorno' => 'ok'));
    }

    exit;
}

if (isset($_REQUEST['ajax_estados'])) {
    $paisAjax = $_REQUEST['pais'];
    $array_estados = $array_estados($paisAjax);
    $retorno = (count($array_estados) > 0) ? $array_estados : ["error" => "true"];
    exit(json_encode($retorno));
}

if (isset($_POST['ajax_get_cidade_pela_provincia'])) {
    $provincia = $_POST['provincia'];
    $pais_cadastro = $_POST['pais'];
    $provincias = getCidadesDoEstado($pais_cadastro, $provincia);
    echo json_encode($provincias);
    exit;
}

if($login_fabrica == 1){
    include_once '../class/communicator.class.php';
    include_once "../class/tdocs.class.php";
}

// if ($_POST['aprovacao_pre_cadastro'] == true) {
//     $posto_id           = $_POST["posto"];
//     $codigo_posto       = $_POST['codigo_posto'];
//
//     $resS = pg_query($con,"BEGIN TRANSACTION");
//
//     $sql_credenciamento = "INSERT INTO tbl_credenciamento (fabrica, status, confirmacao_admin, posto, texto) values ($login_fabrica, 'Pre Cadastro em apr', $login_admin, $posto, 'Enviado para aprovação')";
//     $res_credenciamento = pg_query($con, $sql_credenciamento);
//
//
//     $sql_posto_fabrica = "UPDATE tbl_posto_fabrica SET credenciamento = 'Pre Cadastro em apr' WHERE posto = $posto and fabrica = $login_fabrica ";
//     $res_posto_fabrica = pg_query($con, $sql_posto_fabrica);
//
//     if(strlen(pg_last_error($con))==0){
//         $resS = pg_query($con,"COMMIT TRANSACTION");
//
//         $assunto = " Aprovação do Pré-Cadastro do Posto $codigo_posto Pendente ";
//         $mensagem = "O Pré-cadastro do posto $codigo_posto está aguardando aprovação";
//
//         $sql_admin = "SELECT email from tbl_admin where fabrica = $login_fabrica and responsavel_ti is true and ativo is true ";
//         $res_admin = pg_query($con, $sql_admin);
//         if(pg_num_rows($res_admin)>0){
//             $email = 'william.brandino@telecontrol.com.br';
// //             $email = pg_fetch_result($res_admin, 0, email);
//         }
//
//         $mailTc = new TcComm($externalId);
//         $res = $mailTc->sendMail(
//             $email,
//             $assunto,
//             $mensagem,
//             'no-reply@telecontrol.com.br'
//         );
//
//         echo "enviado";
//
//     }else{
//         $resS = pg_query($con,"ROLLBACK TRANSACTION");
//     }
//
// exit;
// }


// if($_POST["prestacao_servico"] and $login_fabrica == 1){
//     $posto = $_POST["prestacao_servico"];
//     $anexaTdocs = true;
//     $caminho = "../";
//
//     $login_posto = $posto;
//     include "../gera_contrato_posto.php";
//
//     if($anexaTdocs == true){
//         $arquivo = "/tmp/contrato_servico_$posto.pdf";
//
//         $tDocs = new TDocs($con, $login_fabrica);
//         $tDocs->setContext("posto", "contrato");
//
//         $info = $tDocs->getDocumentsByName("contrato_servico_$posto.pdf");
//         $anexo = reset($info->attachListInfo)['tdocs_id'];
//
//         if($tDocs->hasAttachment){
//             $tDocs->uploadFileS3($arquivo, $anexo, false, "posto", "contrato");
//         }else{
//             $tDocs->uploadFileS3($arquivo, $posto, false, "posto", "contrato");
//         }
//     }
//
//     header("location: posto_cadastro.php?posto={$posto}");
//     exit;
// }

function validaHora($hora){
    $t = explode(":",$hora);
    if ($t == "")
        return false;

    $h = $t[0];
    $m = $t[1];

    if (!is_numeric($h) || !is_numeric($m) )
        return false;

    if ($h < 0 || $h > 24)
        return false;

    if ($m < 0 || $m > 59)
        return false;

    return true;
}

function retornaLinkContratoGestao() {
    global $login_fabrica, $posto;

    include_once S3CLASS;
    $amazonTC = new AmazonTC('pa_co', $login_fabrica); //Anexo contrato Posto

    $amazonTC->getObjectList("contrato_posto_{$login_fabrica}_{$posto}");
    $files_anexo_posto = $amazonTC->files;

    if(count($files_anexo_posto) > 0){

        $ret = [];
        $camposPronto = "";
        $arr_docs = array("pdf", 'doc', 'docx');

        foreach ($files_anexo_posto as $key => $path) {

            $basename = basename($path);
            $thumb = $amazonTC->getLink("thumb_".$basename, false, "", "");
            $full  = $amazonTC->getLink($basename, false, "", "");
            $pathinfo = pathinfo($full);
            list($ext,$params) = explode("?", $pathinfo["extension"]);

            $tag_abre = '<a href="' . $full . '">';
            $tag_fecha = '</a>';

            $camposPronto = $tag_abre;

            if ($ext == "pdf") {
                $camposPronto .= "<img alt='".traduz("Baixar Anexo")."' src='imagens/adobe.JPG' title='".traduz("Clique para ver a imagem em uma escala maior")."' style='width: 100px; height: 90px;' />";
            } else {
                $camposPronto .= "<img alt='".traduz("Baixar Anexo")."' src='".$thumb."' title='".traduz("Clique para ver a imagem em uma escala maior")."' style='width: 100px; height: 90px;' />";
            }

            $camposPronto .= $tag_fecha;
        
            $ret[$key] = $camposPronto;
            $camposPronto = "";
        }
        return json_encode($ret);
    }
    return 'semLink';
}

if ($contrato_posto && isset($_GET["excluir_contrato"])) {

    $id    = $_GET["id"];
    $posto = $_GET["posto"];

    if(strlen($id) > 0 && strlen($posto) > 0){

        $tDocs = new TDocs($con, $login_fabrica);

        $tDocs->setContext("posto", "contrato")->removeDocumentById($id);

    }

    header("location: posto_cadastro.php?posto={$posto}");
    exit;
}

if (in_array($login_fabrica, [177])) {
    if (isset($_POST['ajax']) && $_POST['ajax'] == 'sim' && isset($_POST['acao']) && isset($_POST['posto'])) {
        $acao     = addslashes($_POST['acao']);
        $posto    = addslashes($_POST['posto']);
       
        if ($acao == 'credenciar') {
            $statusCredenciamento = traduz("EM APROVAÇÃO");
            $sql_update_credenciamento = "UPDATE tbl_posto_fabrica SET
                                            credenciamento = 'EM APROVAÇÃO'
                                        WHERE posto   = {$posto}
                                        AND   fabrica = {$login_fabrica}";
            $res_update_credencimaneto = pg_query($con, $sql_update_credenciamento);

            $sql_credenciamento = "INSERT INTO tbl_credenciamento (fabrica, status, confirmacao_admin, posto, texto) values ($login_fabrica, 'EM APROVAÇÃO', $login_admin, $posto, '')";
            $res_credenciamento = pg_query($con, $sql_credenciamento);       

            $res_msg_erro              = pg_last_error($con);
        } else if ($acao == 'descredenciar') {
            $statusCredenciamento = "DESCREDENCIADO";
            $sql_update_credenciamento = "UPDATE tbl_posto_fabrica SET
                                            credenciamento = 'DESCREDENCIADO'
                                        WHERE posto   = {$posto}
                                        AND   fabrica = {$login_fabrica}";
            $res_update_credencimaneto = pg_query($con, $sql_update_credenciamento);

            $sql_credenciamento = "INSERT INTO tbl_credenciamento (fabrica, status, confirmacao_admin, posto, texto) values ($login_fabrica, 'DESCREDENCIADO', $login_admin, $posto, '')";
            $res_credenciamento = pg_query($con, $sql_credenciamento);

            $sql_get_parametros = "SELECT parametros_adicionais 
                                   FROM tbl_posto_fabrica 
                                   WHERE posto = $posto 
                                   AND fabrica = $login_fabrica";

            $res_get_parametros = pg_query($sql_get_parametros); 

            $res_get_parametros = json_decode(pg_fetch_result($res_get_parametros, 0, 'parametros_adicionais'), True);

            $res_get_parametros['contrato'] = 'f';

            $res_get_parametros = json_encode($res_get_parametros);

            $sql_set_parametros = "UPDATE tbl_posto_fabrica 
                                   SET parametros_adicionais = '$res_get_parametros' 
                                   WHERE posto = $posto 
                                   AND fabrica = $login_fabrica";

            $res_set_parametros = pg_query($sql_set_parametros); 

            $res_msg_erro              = pg_last_error($con);
        } else if ($acao == 'em_descredencimento') {
    
            $sql_update_credenciamento = "UPDATE tbl_posto_fabrica 
                                          SET    credenciamento  = 'EM DESCREDENCIAMENTO', 
                                               digita_os         = 'f', 
                                               pedido_faturado   = 'f' 
                                          WHERE  fabrica         = $login_fabrica 
                                          AND    posto           = $posto";

            $res_update_credencimaneto = pg_query($con, $sql_update_credenciamento);

            $nome_admin = $login_login; 

            $sql_credenciamento = "INSERT INTO tbl_credenciamento (posto ,fabrica ,data ,status ,confirmacao_admin ,confirmacao ,texto ,dias) VALUES ($posto ,$login_fabrica ,current_timestamp ,'EM DESCREDENCIAMENTO' ,$login_admin ,current_timestamp ,'Encerramento de contrato por $nome_admin', null)";

            $res_credenciamento = pg_query($con, $sql_credenciamento);

            
            $sql_get_parametros = "SELECT parametros_adicionais 
                                   FROM tbl_posto_fabrica 
                                   WHERE posto = $posto 
                                   AND fabrica = $login_fabrica";

            $res_get_parametros = pg_query($sql_get_parametros); 

            $res_get_parametros = json_decode(pg_fetch_result($res_get_parametros, 0, 'parametros_adicionais'), True);

            $res_get_parametros['contrato'] = 'f';

            $res_get_parametros = json_encode($res_get_parametros);

            $sql_set_parametros = "UPDATE tbl_posto_fabrica 
                                   SET parametros_adicionais = '$res_get_parametros' 
                                   WHERE posto = $posto 
                                   AND fabrica = $login_fabrica";

            $res_set_parametros = pg_query($sql_set_parametros); 

            $res_msg_erro = pg_last_error($con);
        }

        if ($acao != 'em_descredencimento') {

            $sqlTblCredenciamento = "INSERT INTO tbl_credenciamento (posto, fabrica, status, confirmacao, confirmacao_admin) VALUES ({$posto}, {$login_fabrica}, '{$statusCredenciamento}', now(), {$login_admin}) ";
            
            $resTblCredenciamento = pg_query($con, $sqlTblCredenciamento);
        }

        if (strlen($res_msg_erro) > 0) {
            exit(json_encode(array("error" => $res_msg_erro)));
        } else {
            exit(json_encode(array("ok" => "sucesso")));
        }
    }
}


if ($S3_sdk_OK) {
    $anexo_posto = sha1(date("Ymdhi")."{$login_fabrica}_{$login_posto}");

    include_once S3CLASS;
    $amazonTC = new AmazonTC('pa_co', $login_fabrica); //Anexo contrato Posto
}

if (isset($_POST['posto_imagem'])) {
    $posto      = $_POST['posto_imagem'];
    $tipo_anexo = $_POST['tipo_anexo'];

    if(is_array($_POST['exclui'])){
        foreach($_POST['exclui'] as $apagar){
            unlink($apagar);
        }
    }

    $caminho_imagem = '../autocredenciamento/fotos/';
    $caminho_path   = '../autocredenciamento/fotos/';

    //$debug = ($_COOKIE['debug'][0] == 't');
    $msg_erro = array();

    $nome_foto_cnpj = preg_replace('/\D/','',$_POST['cnpj_imagem']);

    $config["tamanho"] = 2*1024*1024;

        if ($tipo_anexo == "fachada") {
            $i = 1;
        } else if ($tipo_anexo == "recepcao") {
            $i = 2;
        } else {
            $i = 3;
        }

            $arquivo    = $_FILES["anexo_posto_upload"];

            // if ($debug) {echo "<p>Imagem para o posto $posto, Erros: ".count($msg_erro)."<br><pre>".var_dump($arquivo)."</pre></p>";}

            // Formulário postado... executa as ações
            if (strlen($arquivo["tmp_name"]) > 0 && $arquivo["tmp_name"] != "none"){

                // Verifica o MIME-TYPE do arquivo
                if (!preg_match("/\/(pjpeg|jpeg|png|gif|bmp)$/", $arquivo["type"])){
                    $msg_erro = traduz("Arquivo em formato inválido!");
                }

                // Verifica tamanho do arquivo
                if ($arquivo["size"] > $config["tamanho"]) {
                    $msg_erro = traduz("Arquivo em tamanho muito grande! Deve ser de no máximo 2MB. Envie outro arquivo.");
                }

                if (empty($msg_erro)) {

                    // Pega extensão do arquivo
                    preg_match("/\.(gif|bmp|png|jpg|jpeg){1}$/i", $arquivo["name"], $ext);
                    $aux_extensao = "." . $ext[1];
                    $aux_extensao = strtolower($aux_extensao);

                    // Gera um nome único para a imagem
                    $nome_anexo = $nome_foto_cnpj . "_" .$i . $aux_extensao;

    //                     if ($debug) echo "<p>Imagem $i gravada como $nome_anexo...</p>";

                    // Exclui anteriores, qualque extensao
                    #@unlink($nome_foto__cnpj . "_" .$i);
                    array_map('unlink',glob($caminho_imagem.$nome_foto_cnpj . "_" .$i.".*"));

                    // Faz o upload da imagem
                    if (empty($msg_erro)) {
                        $thumbail = new resize("anexo_posto_upload", 600, 400 );
                        $thumbail->saveTo($nome_anexo,$caminho_imagem);
                    }

                }
            } else {
               $msg_erro = traduz("Falha ao anexar imagem");
            }

    if (empty($msg_erro)) {
        $retorno = array('success' => 't', 'link' => $caminho_imagem.$nome_anexo, 'tipo_anexo' => $i);
    } else {
        $retorno = array('error' => utf8_encode($msg_erro));
    }

    exit(json_encode($retorno));
}

$msg_erro = "";
$msg_debug = "";
$msg = $_GET['msg'];

if($_POST['ajax_zera_estoque'] == "true"){

    $posto  = $_POST['posto'];
    $motivo = $_POST['motivo'];
    $obs = traduz("Saída Manual")." - ".$motivo;

    $sqlTipoPosto = "
        SELECT tp.posto_interno, tp.tecnico_proprio
        FROM tbl_posto_fabrica pf
        INNER JOIN tbl_tipo_posto tp ON tp.tipo_posto = pf.tipo_posto AND tp.fabrica = {$login_fabrica}
        WHERE pf.fabrica = {$login_fabrica}
        AND pf.posto = {$posto}
    ";
    $resTipoPosto = pg_query($con, $sqlTipoPosto);

    $posto_interno   = pg_fetch_result($resTipoPosto, 0, "posto_interno");
    $tecnico_proprio = pg_fetch_result($resTipoPosto, 0, "tecnico_proprio");

    if ($posto_interno != "t" && $tecnico_proprio != "t") {
        $sqlOs = "
            SELECT COUNT(o.os) AS qtde_os
            FROM tbl_os o
            INNER JOIN tbl_os_extra oe ON oe.os = o.os
            INNER JOIN tbl_tipo_atendimento ta ON ta.tipo_atendimento = o.tipo_atendimento AND ta.fabrica = {$login_fabrica}
            WHERE o.fabrica = {$login_fabrica}
            AND o.excluida IS NOT TRUE
            AND o.posto = {$posto}
            AND oe.extrato IS NULL
            AND ta.fora_garantia IS TRUE
        ";
        $resOs = pg_query($con, $sqlOs);

        $qtde_os = pg_fetch_result($resOs, 0, "qtde_os");

        if ($qtde_os > 0) {
            echo traduz("Erro ao zerar estoque, ainda há OSs fora de garantia que não constam em extrato");
            exit;
        }
    }

    $sql = "
        SELECT peca,qtde FROM tbl_estoque_posto WHERE fabrica = {$login_fabrica} AND posto = {$posto};
    ";
    $res = pg_query($con,$sql);

    if(pg_num_rows($res) > 0){

        $resS = pg_query($con,"BEGIN TRANSACTION");

        for ($i=0; $i < pg_num_rows($res); $i++) {

            $peca = pg_fetch_result($res, $i, "peca");
            $qtde = pg_fetch_result($res, $i, "qtde");

            if($qtde > 0){

                $sql = "
                    INSERT INTO tbl_estoque_posto_movimento (
                        fabrica,
                        posto,
                        peca,
                        data,
                        qtde_saida,
                        admin,
                        obs
                    ) VALUES (
                        {$login_fabrica},
                        {$posto},
                        {$peca},
                        CURRENT_DATE,
                        {$qtde},
                        {$login_admin},
                        '{$obs}'
                    );
                ";
                $resI = pg_query($con,$sql);

                if(strlen(pg_errormessage($con)) > 0){
                    $erro .= pg_errormessage($con) ."\n";
                }

            }

            $sql = "
                UPDATE tbl_estoque_posto
                SET qtde = 0,
                    estoque_minimo = 0,
                    estoque_maximo = 0
                WHERE fabrica = {$login_fabrica}
                AND posto = {$posto}
                AND peca = {$peca};
            ";

            $resU = pg_query($con,$sql);

            if(strlen(pg_errormessage($con)) > 0){
                $erro .= pg_errormessage($con) ."\n";
            }

            $up_entrada = "
                UPDATE tbl_estoque_posto_movimento
                SET qtde_usada = qtde_entrada, qtde_usada_estoque = qtde_entrada
                WHERE fabrica = {$login_fabrica}
                AND posto = {$posto}
                AND peca = {$peca}
                AND qtde_entrada IS NOT NULL
                AND qtde_saida IS NULL;
            ";

            $res_up = pg_query($con, $up_entrada);

            if(strlen(pg_errormessage($con)) > 0){
                $erro .= pg_errormessage($con) ."\n";
            }

        }

        if(strlen($erro) > 0){
            $res = pg_query($con,"ROLLBACK TRANSACTION");
            echo $erro;
        }else{
            $res = pg_query($con,"COMMIT TRANSACTION");
        }
    }

    exit;
}

if ($_POST["excluiRevendaOSVinculada"] == "true") {
    $revenda = $_POST["revenda"];
    $posto   = $_POST["posto"];
    $sql     = "SELECT parametros_adicionais
                  FROM tbl_posto_fabrica
                 WHERE posto   = {$posto} AND
                       fabrica = {$login_fabrica} ";

    $res = pg_query($con,$sql);
    if(pg_num_rows($res) > 0) {
        $dados= pg_fetch_object($res,0);
        $parametrosAdicionais = json_decode($dados->parametros_adicionais);
        $arrRevendaOSVinculada = $parametrosAdicionais->revendaOSVinculada;
        /* var_dump($dados->parametros_adicionais); */
        $contadorarrRV = count($arrRevendaOSVinculada);
        for($i = 0; $i<$contadorarrRV; $i++){
            if($arrRevendaOSVinculada[$i] == $revenda){
                unset($arrRevendaOSVinculada[$i]);
                break;
            }
        }
        $parametrosAdicionais->revendaOSVinculada = $arrRevendaOSVinculada;
        $parametrosAdicionais = json_encode($parametrosAdicionais);
        $updateParametrosAdicionais = "UPDATE tbl_posto_fabrica set parametros_adicionais ='".$parametrosAdicionais."'
                                        WHERE posto = {$posto}
                                          AND fabrica = {$login_fabrica}";
        $res = pg_query($con,$updateParametrosAdicionais);

        if(!$res){
            echo json_encode(array("success"=>"false"));
        }else{
             echo json_encode(array("success"=>"true", "revenda"=>$revenda));

        }
    }
    exit;
}
if($_POST["osVinculadaConsumidor"] == "true"){
    $cnpj =  $_POST["cnpj"];
    $posto= $login_posto;
    $sql  = "SELECT tbl_revenda.revenda,
                   tbl_revenda.cnpj,
                   CONVERT_TO(tbl_revenda.nome, 'UTF8') AS nome
            FROM tbl_revenda
            WHERE tbl_revenda.cnpj = '{$cnpj}'";

    $res = pg_query($con, $sql);

    if(pg_num_rows($res) > 0){
        $revenda = pg_fetch_object($res, 0);
        $arrRevenda = array("success"=>"true", "revenda"=>$revenda->revenda, "cnpj"=>$revenda->cnpj, "nome"=>$revenda->nome);
        $json = json_encode($arrRevenda);
    }else{

        $json = json_encode(array("success"=>"false"));
    }
    echo $json;
    exit;
}
// Autocomplete ajax
if (isset($_GET["q"])){

    //$q = trim(preg_replace("/\W/", ' ', utf8_decode(mb_strtolower($_GET["q"]))));
    $q = utf8_decode(trim($_GET["q"]));

    if ($_GET["busca"] == "cidade"){
        if (strlen($q)>2){
            $sql = "SELECT cod_ibge, cidade as cidade, estado
            FROM tbl_ibge
            WHERE
            /*cidade ~* E'$q'*/
            cidade ilike  '%$q%'
            ORDER BY length(cidade) ASC
             LIMIT 100";
            $res = pg_query($con,$sql);
            $numrows = pg_num_rows($res);

            if (pg_num_rows ($res) > 0) {

                header('Content-type: text/html; charset=utf-8');

                for ($i=0; $i<pg_num_rows ($res); $i++ ){
                    $codigo_ibge    = pg_fetch_result($res,$i,cod_ibge);
                    $cidade_estado  = utf8_encode(pg_fetch_result($res,$i,cidade));
                    $estado         = utf8_encode(pg_fetch_result($res,$i,estado));
                    echo "$cidade_estado - $estado|$codigo_ibge";
                    echo "\n";
                }
            }
        }
    }
    exit;
}

if($_GET['buscaCidade']){
    $uf = $_GET['estado'];

    if($uf == "BR-CO"){
        $estado = "and contato_estado in('GO','MS','MT','DF')";
    } else if($uf == "BR-NE"){
        $estado = "and contato_estado in('SE','AL','RN','MA','PE','PB','CE','PI','BA')";
    } else if($uf == "BR-N"){
        $estado = "and contato_estado in('TO','PA','AP','RR','AM','AC','RO')";
    } else if($uf == "00"){
        echo "<option value=''>".traduz("Selecione uma cidade")."</option>"; exit;
    } else {
        $estado = "and contato_estado = '$uf'";
    }
    
    $sql = "SELECT DISTINCT UPPER(fn_retira_especiais(TRIM(contato_cidade))) AS contato_cidade, UPPER(fn_retira_especiais(TRIM(contato_estado))) AS contato_estado FROM tbl_posto_fabrica WHERE fabrica = $login_fabrica $estado ORDER BY contato_cidade,contato_estado";

    $res = pg_query($con,$sql);
    $contadorRes = pg_numrows($res);
    if(pg_numrows($res) > 0){
        $retorno = "<option value=''></option>";
        $retorno .= "<option value='t_cidades'>".traduz("Todas cidades")."</option>";
        for($i = 0; $i < $contadorRes; $i++){
            $cidade = pg_result($res,$i,'contato_cidade');
            $estado = pg_result($res,$i,'contato_estado');

            $nome_cidade = in_array($uf,array('BR-CO','BR-NE','BR-N')) ? "$cidade - $estado" : $cidade;

            $retorno .= "<option value='$cidade'>$nome_cidade</option>";
        }
    } else {
        $retorno .= "<option value=''>".traduz("Cidade não encontrada")."</option>";
    }

    echo $retorno;
    exit;
}

if (strlen($_REQUEST['posto'])) {

    $posto = anti_injection($_REQUEST['posto']);

    $sql = "SELECT UPPER(pais) FROM tbl_posto WHERE posto = $posto";

    $res   = pg_query($con, $sql);
    $_pais = pg_fetch_result($res, 0, 0);

}


//contrato positron
if ($login_fabrica == 153) {
    if ($_GET['contrato_positron']) {
        $posto_id = $_GET['posto'];

        $sql = "SELECT tbl_posto_fabrica.contato_email,
                        tbl_posto.nome
                FROM tbl_posto
                JOIN tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
                WHERE posto = $posto_id
                ";
        $res = pg_query($con, $sql);
        if(pg_num_rows($res) > 0){
            $email = pg_fetch_result($res, 0, 'contato_email');
            $posto_nome = pg_fetch_result($res, 0, 'nome');

            if(strlen($email) > 0){
                $file1 = "contratos_positron/contrato_vigente_positron_2015.pdf";
                $file2 = "contratos_positron/termo_adesao_postos_assistencia_tecnica_positron_2015.doc";

                $files = array($file1, $file2);
                $to = $email;
                $from = "rede.autorizada@telecontrol.com.br";
                $subject ="CONTRATO POSITRON";
                $message = "<center><img src='https://posvenda.telecontrol.com.br/assist/helpdesk/documentos/hd-2400939-itens/9410256-topo_positron.jpg' /></center>";
                #$message .= "<center><font face='verdana'>Sr. $posto_nome  segue em anexo os contratos para CREDENCIAMENTO na fabrica POSITRON </font></center>";
                $message .= "<center><div style='width:600px'>
                            <p align='justify'>
                                <font face='Verdana, Arial, Helvetica, sans-serif' size='3'>
                                    Em parceria com a Telecontrol, estamos credenciando novas assistências técnicas para ingressar em nossa rede.
                                    <br /><br />
                                    &bull; A taxa de mão-de-obra varia entre R$19,10 para os modelos mais simples a <br/>R$ 23,30, podendo chegar a R$46,60 conforme a performance do posto.
                                    <br /><br />
                                    &bull; Os contratos e a gestão da rede serão realizados diretamente com a PST Electronics.
                                    <br /><br />
                                    <strong>
                                        <font color='red'>Obs.</font>
                                        Somente o termo de adesão deverá ser preenchido, assinado e devolvido para a Telecontrol.
                                    </strong>
                                </font>
                            </p>
                            <p align='left'>
                                <font face='Verdana, Arial, Helvetica, sans-serif' size='3'>
                                    Envio feito através dos Correios, enviar para o endereço: <br />
                                    Av. Carlos Artêncio, 420-B    CEP: 17.519-255 - Bairro Fragata    Marília, SP - Brasil
                                    <br /><br />
                                    Envio feito através de E-mail, enviar para: <br />
                                    rede.autorizada@telecontrol.com.br
                                </font>
                            </p>
                            <p align='left'>
                                <font face='Verdana, Arial, Helvetica, sans-serif' size='3'>
                                    Duvidas:
                                    <br />SAC Rede Autorizada: 0800-718-7825
                                    <br />E-mail: rede.autorizada@telecontrol.com.br
                                </font>
                            </p>
                            </div></center>";
                $message .= "<center><img src='https://posvenda.telecontrol.com.br/assist/helpdesk/documentos/hd-2400939-itens/9410256-rodape1_positron.jpg' /></center>";

                $headers = "From: $from";

                $semi_rand = md5(time());
                $mime_boundary = "==Multipart_Boundary_x{$semi_rand}x";

                $headers .= "\nMIME-Version: 1.0\n" . "Content-Type: multipart/mixed;\n" . " boundary=\"{$mime_boundary}\"";

                $message = "This is a multi-part message in MIME format.\n\n" . "--{$mime_boundary}\n" . "Content-Type: text/html; charset=\"iso-8859-1\"\n" . "Content-Transfer-Encoding: 7bit\n\n" . $message . "\n\n";
                $message .= "--{$mime_boundary}\n";

                $contadorFiles = count($files);

                for($x=0;$x<$contadorFiles;$x++){
                    $file = fopen($files[$x],"rb");
                    $data = fread($file,filesize($files[$x]));
                    fclose($file);
                    $data = chunk_split(base64_encode($data));
                    $message .= "Content-Type: {\"application/octet-stream\"};\n" . " name=\"$files[$x]\"\n" .
                    "Content-Disposition: attachment;\n" . " filename=\"$files[$x]\"\n" .
                    "Content-Transfer-Encoding: base64\n\n" . $data . "\n\n";
                    $message .= "--{$mime_boundary}\n";
                }
                if(mail($to, $subject, $message, $headers)){
                    ob_clean();
                    echo 'enviado';
                }else{
                    ob_clean();
                    echo 'falha';
                }

            }else{
                ob_clean();
                echo 'falha';
            }
        }
        exit;
    }
}

//exclusão da foto
if (strlen($_GET['posto']) > 0 and strlen($_GET['excluir_foto']) > 0 and strlen($_GET['foto']) > 0) {
    $posto    = trim($_GET['posto']);
    $excluir_foto = trim($_GET['excluir_foto']);
    $foto         = trim($_GET['foto']);
    $sql = "SELECT * FROM tbl_posto_fabrica_foto WHERE posto_fabrica_foto = $excluir_foto";
    $res = pg_query($con, $sql);

    if (pg_num_rows($res) > 0) {
        $aux_fabrica = pg_fetch_result($res, 0, fabrica);

        //valida a fabrica para o caso de ter sido alterado direto na barra de endereços
        if ($aux_fabrica == $login_fabrica) {
            if ($foto == 'foto_posto') {
                $caminho_foto  = pg_fetch_result($res, 0, 'foto_posto');
                $caminho_thumb = pg_fetch_result($res, 0, 'foto_posto_thumb');

                $sql = "UPDATE tbl_posto_fabrica_foto SET
                            foto_posto           = NULL,
                            foto_posto_thumb     = NULL,
                            foto_posto_descricao = NULL
                        WHERE posto_fabrica_foto = $excluir_foto";
                $res = pg_query($con, $sql);

                system("rm $caminho_foto");
                system("rm $caminho_thumb");
            }

            if ($foto == 'foto_contato1') {
                $caminho_foto  = pg_fetch_result($res, 0, foto_contato1);
                $caminho_thumb = pg_fetch_result($res, 0, foto_contato1_thumb);

                $sql = "UPDATE tbl_posto_fabrica_foto SET
                            foto_contato1           = NULL,
                            foto_contato1_thumb     = NULL,
                            foto_contato1_descricao = NULL
                        WHERE posto_fabrica_foto = $excluir_foto";
                $res = pg_query($con, $sql);

                system("rm $caminho_foto");
                system("rm $caminho_thumb");
            }

            if ($foto == 'foto_contato2') {
                $caminho_foto  = pg_fetch_result($res, 0, foto_contato2);
                $caminho_thumb = pg_fetch_result($res, 0, foto_contato2_thumb);

                $sql = "UPDATE tbl_posto_fabrica_foto SET
                            foto_contato2           = NULL,
                            foto_contato2_thumb     = NULL,
                            foto_contato2_descricao = NULL
                        WHERE posto_fabrica_foto = $excluir_foto";
                $res = pg_query($con, $sql);

                system("rm $caminho_foto");
                system("rm $caminho_thumb");
            }
        }
    }
}

if (strlen($_POST['btn_acao']) > 0) $btn_acao = $_POST['btn_acao'];

#-------------------- Descredenciar -----------------
if ($btn_acao == "descredenciar" and strlen($posto) > 0 ) {
    $sql = "DELETE FROM tbl_posto_fabrica
            WHERE  tbl_posto_fabrica.posto   = $posto
            AND    tbl_posto_fabrica.fabrica = $login_fabrica;";
    $res = pg_query ($con,$sql);

    if (strlen (pg_errormessage ($con)) > 0) $msg_erro = pg_errormessage($con);

    if (strlen($msg_erro) == 0) {
        header ("Location: $PHP_SELF");
        exit;
    }
}

if ($btn_acao == "gravar") {

    $cnpj  = trim($_POST['cnpj']);
    $xcnpj = preg_replace("/\W/","",$cnpj);
    $nome  = trim($_POST ['nome']);
    $nome_anterior  = trim($_POST ['nome_anterior']);
    $posto = trim($_POST ['posto']);
    $pais_cadastro  = trim($_POST['pais']);

    if($login_fabrica == 1){
        $credenciamentoObs = $_POST["credenciamentoObs"];
    }

    if (isset($_POST['gera_pedido'])) {
        $gera_pedido = (empty($_POST['gera_pedido'])? "f" : $_POST['gera_pedido']);
    }

    $loja_b2b_tabela  = trim($_POST['loja_b2b_tabela']);

    $usa_cidade_estado_txt = trim($_POST['usa_cidade_estado_txt']);

    if ($usa_cidade_estado_txt == "t") {
        $estado_txt          = $_POST['estado'];
        $cobranca_estado_txt = $_POST['cobranca_estado'];

        unset($_POST['estado']);
        unset($_POST['cobranca_estado']);

    }

    /**
     * HD 6147694
     * Para B&D quando alterar alguns dos seguintes campos:
     *  Tipo, Categoria, Taxa Adm e Reembolso de Peça do Estoque.
     * Vai para aprovação de cadastro e o posto continua como se não sofresse alteração destes valores.
     */
    $atualiza_posto_fabrica = true;

    if (strlen($posto) > 0){
        $sqlVcnpj = "SELECT cnpj
                       FROM tbl_posto
                      WHERE posto = $posto";
        $resVcnpj = pg_query($con,$sqlVcnpj);

        if (pg_num_rows($resVcnpj) > 0){
            if ($xcnpj <> trim((pg_fetch_result ($resVcnpj,0,0)))){
                if (!in_array($login_fabrica,[1,20,180,181,182])) {
                    $msg_erro = traduz("A alteração de CNPJ só é possível mediante abertura de
                chamados para a Telecontrol");
                }
            }
        }
    }
             
        if (strlen($nome) == 0 AND strlen($xcnpj) > 0) {

            // verifica se posto está cadastrado
            $sql = "SELECT posto
                    FROM   tbl_posto
                    WHERE  cnpj = '$xcnpj'";
                    
            $res = pg_query ($con,$sql);

            if (pg_num_rows ($res) > 0) {
                $posto = pg_fetch_result ($res,0,0);
                header ("Location: $PHP_SELF?posto=$posto");
                exit;
            } else if (!in_array($login_fabrica, [180,181,182])) {
                $curl = curl_init();
                curl_setopt_array($curl, array(
                CURLOPT_URL => "https://www.receitaws.com.br/v1/cnpj/".$xcnpj,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "GET",
               ));
                $response = curl_exec($curl);
                $status_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
                $err = curl_error($curl);
                $response = json_decode($response, true);

                if($status_code == '200' && $response['status'] != 'ERROR'){ 
                    $dados = json_decode($response, true);
                    
                    $nome = (strlen(trim($dados['nome']))>0)? "'".$dados['nome']."'" : "null";
                    $nome_fantasia = (strlen(trim($dados['fantasia']))>0)? "'".$dados['fantasia']."'" : "null";
                    $bairro = (strlen(trim($dados['bairro']))>0)? "'".$dados['bairro']."'" : "null";
                    $endereco = (strlen(trim($dados['logradouro']))>0)? "'".$dados['logradouro']."'" : "null";
                    $numero = (strlen(trim($dados['numero']))>0)? "'".$dados['numero']."'" : "null";
                    $complemento = (strlen(trim($dados['complemento']))>0)? "'".$dados['complemento']."'" : "null";
                    $cep = (strlen(trim($dados['cep']))>0)? "'".str_replace(array(".","-"), "", $dados['cep'])."'" : "null";
                    $cidade = (strlen(trim($dados['municipio']))>0)? "'".$dados['municipio']."'" : "null";
                    $estado = (strlen(trim($dados['uf']))>0)? "'".$dados['uf']."'" : "null";
                    $email = (strlen(trim($dados['email']))>0)? "'".$dados['email']."'" : "null";
                    $fone = (strlen(trim($dados['telefone']))>0)? "'".$dados['telefone']."'" : "null";

                    $sql = "INSERT INTO  tbl_posto (cnpj, nome, nome_fantasia, bairro, endereco, numero,complemento, cep, cidade, estado) values('$xcnpj', UPPER($nome), UPPER($nome_fantasia), UPPER($bairro), UPPER($endereco), $numero, UPPER($complemento), $cep, UPPER($cidade), $estado) returning posto";
                    $res = pg_query($con, $sql);
                    $posto = pg_fetch_result($res, 0, 0);
                 
                    $msg_erro = traduz("Posto não cadastrado, favor completar os dados do cadastro.");
                }
            }
        }

       if(strlen(trim($xcnpj)) > 0 and strlen($xcnpj) <> 14 and !in_array($login_fabrica, array(2,5,7,14,30,35,45,49,50,51,52,74,86,85,117,158,173,183)) AND $pais_cadastro == "BR") {
            $msg_erro = traduz("CNPJ inválido, digitar novamente.");
        }
        //exit(var_dump());
       if(strlen(trim($xcnpj)) > 0 and strlen($xcnpj) <> 14 AND strlen($xcnpj) <> 11 and !in_array($login_fabrica, array(2,5,7,35,45,49,50,51,86,85,117))AND $pais_cadastro == "BR"){
 
            //Cadence   07/04/2008 HD 17261   - A Cadence tem postos que são cadastrados pelo CPF
            //Dynacom   06/03/2008 HD 15279   - A Dynacom tem postos que são cadastrados pelo CPF
            //NKS       16/04/2008 HD 17853   - A NKS tem postos que são cadastrados pelo CPF
            //GAMA      23/07/2008 HD 27662   - A GAMA tem postos que são cadastrados pelo CPF
            //FILIZOLA  11/08/2008 HD 27662   - A FILIZOLA tem postos que são cadastrados pelo CPF
            //ESMALTEC  14/05/2009 HD 106125  - A Esmaltec tem postos que são cadastrados pelo CPF
            //Mondial   16/03/2010 HD 208465  - A Mondial tem postos que são cadastrados pelo CPF
            //FAMASTIL  28/05/2010 Fone       - A Famastil precisou cadastrar 2 postos com CPF (MLG)
            //ELGIN     26/04/2012 HD 1108731 - A Elgin tem postos que são cadastrados pelo CPF
            $msg_erro = traduz("CNPJ/CPF inválido, digitar novamente..");
        }

        if($login_fabrica==2){//HD 34921 29/8/2008
            $validar = checa_cnpj($xcnpj);
            if ($validar==1){
                $msg_erro = traduz("Por favor digite um CNPJ válido.");
            }
        }

        if(strlen($xcnpj) == 0){
            $erro = traduz("Preencha os campos obrigatórios.")." <br>";
            $campos[] = "cnpj";
        }else{
            $cnpj = $xcnpj;
        }

    if (strlen($msg_erro) == 0){
        if (strlen($posto) == 0 AND strlen($nome) > 0 AND strlen($xcnpj) > 0) {
            // verifica se posto está cadastrado
            $sql = "SELECT posto
                    FROM   tbl_posto
                    WHERE  cnpj = '$xcnpj'";
            $res = pg_query ($con,$sql);

            if (pg_num_rows ($res) > 0){
                $posto = pg_fetch_result ($res,0,0);
                header ("Location: $PHP_SELF?posto=$posto");
                exit;
            }
        }
    }

        $codigo                                  = trim($_POST ['codigo']);
        $ie                                      = trim($_POST['ie']);
        $im                                      = trim($_POST['im']);
        $endereco                                = trim($_POST['endereco']);
        $numero                                  = trim($_POST['numero']);
        $complemento                             = trim($_POST['complemento']);
        $bairro                                  = trim(filter_input(INPUT_POST,'bairro'));
        $cep                                     = trim($_POST['cep']);
        $cidade                                  = trim($_POST['cidade']);
        $estado                                  = trim($_POST['estado']);
        $email                                   = trim($_POST['email']);
        $mo_triagem                              = trim($_POST['mo_triagem']);
        $digita_os_revenda                       = trim($_POST['digita_os_revenda']);
        if ($login_fabrica == 15) {
            $email2                              = trim($_POST['email2']);
            $fone2                               = trim($_POST['fone2']);
            $fone3                               = trim($_POST['fone3']);
        }
        if($login_fabrica == 74){
            $fone2                               = trim($_POST['fone2']);
            $fone3                               = trim($_POST['fone3']);
        }
        $fone                                    = trim($_POST['fone']);
        if ($login_fabrica == 40) {
            $fone2                               = trim($_POST['fone2']);
        }
        if ($login_fabrica == 151) {
            $celular                             = trim($_POST['celular']);
            $fone2                               = trim($_POST['fone2']);
            $fone3                               = trim($_POST['fone3']);
        }
        if($login_fabrica == 42){
            $pedido_consumo_proprio              = trim($_POST['pedido_consumo_proprio']);
        }
        $fax                                     = trim($_POST['fax']);
        $contato                                 = trim($_POST['contato']);
        $responsavel_social                      = trim($_POST['responsavel_social']);
        $nome_fantasia                           = trim($_POST['nome_fantasia']);
        $obs                                     = trim($_POST['obs']);
        $capital_interior                        = trim($_POST['capital_interior']);
        $posto_empresa                           = trim($_POST['posto_empresa']);
        $tipo_posto                              = $_POST['tipo_posto'];
        $divulgar_consumidor                     = trim($_POST['divulgar_consumidor']);
        $tipo_atende                             = trim($_POST['tipo_atende']);
        $tipo_contribuinte                       = trim($_POST['tipo_contribuinte']); //hd_chamado=2693784
        $escritorio_regional                     = trim($_POST['escritorio_regional']);
        $codigo                                  = trim($_POST['codigo']);
        $senha                                   = trim($_POST['senha']);
        $desconto                                = trim($_POST['desconto']);
        $valor_km                                = trim($_POST['valor_km']);
        $desconto_acessorio                      = trim($_POST['desconto_acessorio']);
        $custo_administrativo                    = trim($_POST['custo_administrativo']);
        $imposto_al                              = trim($_POST['imposto_al']);
        $suframa                                 = trim($_POST['suframa']);
        $item_aparencia                          = trim($_POST['item_aparencia']);
        $pedido_em_garantia_finalidades_diversas = trim($_POST['pedido_em_garantia_finalidades_diversas']);
        $pais_cadastro                           = trim($_POST['pais']);
        $garantia_antecipada                     = trim($_POST['garantia_antecipada']);
        $imprime_os                              = trim($_POST['imprime_os']);
        $qtde_os_item                            = trim($_POST['qtde_os_item']);
        $escolhe_condicao                        = trim($_POST['escolhe_condicao']); #HD 23738
        $condicao_liberada                       = trim($_POST['condicao_liberada']); #HD 23738
        $atende_consumidor                       = trim($_POST['atende_consumidor']);
        $contribuinte_icms                       = trim($_POST['contribuinte_icms']);
        $categoria_posto                         = trim($_POST['categoria_posto']);
        $tipo_frete                              = trim($_POST['tipo_frete']);
        $contato_nome                            = $contato;
	$pais_cadastro = empty($pais_cadastro) ? $_POST['pais_posto'] : $pais_cadastro;

        if ($login_fabrica == 115 && isset($_POST['categoria_manual'])) {
            $categoria_manual = (empty($_POST['categoria_manual'])) ? 'f' : $_POST['categoria_manual'];
        }

        if(!$contribuinte_icms)                  $contribuinte_icms = 'f';

        // MLG  17/7/2009   HD 126810 - Adicionado campo 'atende_consumidor'

    if($login_fabrica == 1) {

        if(strlen($codigo)==0){
	    $codigo = $xcnpj;
            #$erro = "Preencha os campos obrigatórios. <br>";
            #$campos[] = "codigo";
        }

        if(strlen($pais_cadastro)==0) {
            $campos[] = "pais";
            $erro = traduz("Preencha os campos obrigatórios.")." <br>";
        }

        if (empty($categoria_posto)) {
            $campos[] = "categoria_posto";
            $erro = traduz("Preencha os campos obrigatórios.")." <br>";
        }
    } else {
        if(strlen($pais_cadastro)==0) {
            $campos[] = "pais";
            $erro = traduz("Preencha os campos obrigatórios.")." <br>";
        }

        if(strlen(trim($nome))==0){
            $erro = traduz("Preencha os campos obrigatórios.")." <br>";
            $campos[] = "nome";
        }

        if(strlen($codigo)==0){
            $erro = traduz("Preencha os campos obrigatórios.")." <br>";
            $campos[] = "codigo";
        }

        $cep    = preg_replace("/\D/", "", $cep);
        if((strlen(trim($cep))==0 OR strlen(trim($cep)) < 8) AND $pais_cadastro == "BR"){
            $erro = traduz("Preencha os campos obrigatórios.")." <br>";
            $campos[] = "cep";
        }

        if(strlen(trim($endereco))==0 AND $pais_cadastro == "BR" && !in_array($login_fabrica, [180,181,182])) {
            $erro = traduz("Preencha os campos obrigatórios.")." <br>";
            $campos[] = "endereco";
        }

        if(strlen(trim($numero))==0 AND $pais_cadastro == "BR" && !in_array($login_fabrica, [3,180,181,182])){
            $erro = traduz("Preencha os campos obrigatórios.")." <br>";
            $campos[] = "numero";
        }

        if(strlen(trim($cidade))==0 AND $pais_cadastro == "BR" && !in_array($login_fabrica, [180,181,182])){
            $erro = traduz("Preencha os campos obrigatórios.")." <br>";
            $campos[] = "cidade";
        }

        if(strlen(trim($estado))==0 AND $pais_cadastro == "BR" && !in_array($login_fabrica, [180,181,182])){
           $erro = traduz("Preencha os campos obrigatórios.")." <br>";
            $campos[] = "estado";
        }

        if(strlen(trim($bairro))==0 AND $pais_cadastro == "BR" && !in_array($login_fabrica, [180,181,182])){
            $erro = traduz("Preencha os campos obrigatórios.")." <br>";
            $campos[] = "bairro";
        }

        if(strlen(trim($email))==0 AND $pais_cadastro == "BR" && !in_array($login_fabrica, [180,181,182])){
            $erro = traduz("Preencha os campos obrigatórios.")." <br>";
            $campos[] = "email";
        }
        if(strlen(trim($contato))==0 AND $pais_cadastro == "BR" AND !in_array($login_fabrica,[175,180,181,182])){
            $erro = traduz("Preencha os campos obrigatórios.")." <br>";
            $campos[] = "contato";
        }

        if ($login_fabrica == 115) {
            if (empty($categoria_posto)) {
                $campos[] = "categoria_posto";
                $erro = traduz("Preencha os campos obrigatórios.")." <br>";
            }
        }
    }

	if($desconto > 100 or $desconto_acessorio > 100) {
		$erro = traduz("O valor de desconto não pode passar que")." 100%";
		$campos[] = 'desconto';
	}

    if(strlen(trim($responsavel_social))==0 AND $pais_cadastro == "BR" AND in_array($login_fabrica, array(35))){
        $erro = traduz("Preencha os campos obrigatórios.")." <br>";
        $campos[] = "responsavel_social";
    }

	if($pais_cadastro == "BR"){

		if(strtoupper($nome) != strtoupper($nome_anterior) and strlen($posto) >0 and strlen(trim($nome_anterior)) > 0 ){
		    $erro = traduz("NÃO É POSSÍVEL REALIZAR A ALTERAÇÃO NOS ITENS ABAIXO. POR FAVOR UTILIZE A SOLICITAÇÃO DE ALTERAÇÃO DE DADOS CADASTRAIS NO FINAL DA PÁGINA");
		    //$erro = "Favor abrir um chamado para o Suporte Telecontrol caso deseja alterar as seguintes informações do posto: Razão Social, I.E, Cidade/Estado";
		}
		if($ie != $ie_anterior and strlen($posto) >0 and strlen(trim($ie_anterior)) > 0){
		    $erro = traduz("NÃO É POSSÍVEL REALIZAR A ALTERAÇÃO NOS ITENS ABAIXO. POR FAVOR UTILIZE A SOLICITAÇÃO DE ALTERAÇÃO DE DADOS CADASTRAIS NO FINAL DA PÁGINA");
		    //$erro = "Favor abrir um chamado para o Suporte Telecontrol caso deseja alterar as seguintes informações do posto: Razão Social, I.E, Cidade/Estado";
		}

		if(strtoupper($cidade) != strtoupper($cidade_anterior) and strlen($posto) >0 and strlen(trim($cidade_anterior)) > 0 and $categoria_posto != 'Pré Cadastro'){
		    $sql_c = "SELECT cod_ibge FROM tbl_ibge WHERE UPPER(fn_retira_especiais(cidade)) = UPPER(fn_retira_especiais('{$cidade}')) AND UPPER(estado) = UPPER('{$estado}')";

		    $res_c = pg_query($con,$sql_c);

		    if (pg_num_rows($res_c) == 0) {
                $erro = traduz("NÃO É POSSÍVEL REALIZAR A ALTERAÇÃO NOS ITENS ABAIXO. POR FAVOR UTILIZE A SOLICITAÇÃO DE ALTERAÇÃO DE DADOS CADASTRAIS NO FINAL DA PÁGINA");
		    } else {
                $cod_ibge_cidade = pg_fetch_result($res_c, 0, cod_ibge);
		    }
		}

		if($estado != $estado_anterior and strlen($posto) >0 and strlen(trim($estado_anterior)) > 0 and $categoria_posto != 'Pré Cadastro'){
		   $sql_c = "SELECT cod_ibge FROM tbl_ibge WHERE UPPER(fn_retira_especiais(cidade)) = UPPER(fn_retira_especiais('{$cidade}')) AND UPPER(estado) = UPPER('{$estado}')";

		    $res_c = pg_query($con,$sql_c);

		    if (pg_num_rows($res_c) == 0) {
                $erro = traduz("NÃO É POSSÍVEL REALIZAR A ALTERAÇÃO NOS ITENS ABAIXO. POR FAVOR UTILIZE A SOLICITAÇÃO DE ALTERAÇÃO DE DADOS CADASTRAIS NO FINAL DA PÁGINA");
		    }else{
                $cod_ibge_cidade = pg_fetch_result($res_c, 0, cod_ibge);
		    }
		}
	}



    if (strlen(trim($email)) > 0) {

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $msg_erro .= ((strlen($msg_erro) > 0) ? "<br />" : "")."O e-mail \"{$email}\" é inválido!";
        }
    }

    if(strlen(trim($msg_erro))>0){
        $msg_erro .= "<br>".$erro;
    } else {
        $msg_erro = $erro;
    }

    if (strlen($msg_erro) == 0) {
            
        $xie          = (strlen($ie) == 0)         ? "null"           : "'$ie'";
        $xim          = (strlen($im) > 0)          ? "'$im'"          : 'null';
        $xnumero      = (strlen($numero) > 0)      ? "'$numero'"      : 'null';
        $xcomplemento = (strlen($complemento) > 0) ? "'$complemento'" : 'null';
        $xbairro      = (strlen($bairro) > 0)      ? "'".pg_escape_string(substr($bairro,0,40))."'"      : 'null';
        $xcidade      = (strlen($cidade) > 0)      ? "'$cidade'"      : 'null';
        $xestado      = (strlen($estado) > 0)      ? "'$estado'"      : 'null';
        $xcontato     = (strlen($contato) > 0)     ? "'$contato'"     : 'null';

        $xresponsavel_social = (strlen($responsavel_social) > 0)     ? "'$responsavel_social'"     : 'null';

        $xemail       = (strlen($email) > 0)       ? "'$email'"       : 'null';
        $xtipo_atende = (strlen($tipo_atende) > 0) ? "'$tipo_atende'" : "'f'";
        $xtipo_contribuinte = (strlen($tipo_contribuinte) > 0) ? "$tipo_contribuinte" : "f"; //hd_chamado=2693784

        if ($login_fabrica==15) {
             $xemail_latina = $xemail;
        }

        $xfone        = (strlen($fone) > 0)  ? "'". substr($fone,0,30)."'"  : 'null';
        if (in_array($login_fabrica,array(15,74,151))){
            if ($login_fabrica == 151) {
                $xcontato_fone_comercial = $xfone;
            }

            $xfone = "'".pg_parse_array(array($fone,$fone2,$fone3), true)."'";
		}else{
			$xfone2   = (strlen($fone2) > 0) ? "'$fone2'" : 'null';
            $xfone3   = (strlen($fone3) > 0) ? "'$fone3'" : 'null';
        }

        $xfax                 = (strlen($fax) > 0)                ? "'$fax'"                  : 'null';
        $xnome_fantasia       = (strlen($nome_fantasia) > 0)      ? "'".pg_escape_string($con,$nome_fantasia)."'": 'null';
        $xcapital_interior    = (strlen($capital_interior) > 0)   ? "'$capital_interior'"     : 'null';
        $xposto_empresa       = (strlen($posto_empresa) > 0)      ? "'$posto_empresa'"        : 'null';
        $xescritorio_regional = (strlen($escritorio_regional)> 0) ? "'$escritorio_regional'"  : 'null';
        $xcodigo              = (strlen($codigo) > 0)             ? "'$codigo'"               : 'null';
        $xsuframa             = (strlen($suframa) > 0)            ? "'$suframa'"              : "'f'";
        $zgarantia_antecipada = (strlen($garantia_antecipada)> 0) ? "'f'"                     : "'".$garantia_antecipada."'";
        $xescolhe_condicao    = (strlen($escolhe_condicao) > 0)   ? "'t'"                     : "'f'";
        $xatende_consumidor   = (strlen($atende_consumidor) > 0)  ? "'t'"                     : "'f'";
        $xendereco            = (strlen($endereco) > 0)           ? "E'".str_replace("'", "\'", $endereco)."'"         : 'null';
        if (strlen($cep) > 0) {
            $xcep = "'".substr(preg_replace('/\D/', '', $cep), 0,8)."'";
        }else{
            $xcep = 'null';
        }
        
        if (!is_array($tipo_posto)) {
            $xtipo_posto = (strlen($tipo_posto) > 0) ? "'$tipo_posto'" : 'NULL';
            $atipo_posto = null;
        } else {
            if (!count($tipo_posto)) {
                $xtipo_posto = 'NULL';
                $atipo_posto = null;
            } else if (count($tipo_posto) == 1) {
                $xtipo_posto = $tipo_posto[0];
                $atipo_posto = null;
            } else {
                $atipo_posto = array_filter($tipo_posto, 'strlen'); // exclui os elementos vazios que tiver
                $xtipo_posto = $tipo_posto[0];
            }
        }

        if (empty($posto) && (empty($xtipo_posto) || $xtipo_posto == 'NULL')) {
            $msg_erro .= traduz("Informe o tipo do posto")."<br />";
        }

        if ($login_fabrica == 11 or $login_fabrica == 172) {
            $permite_envio_produto = $_POST["permite_envio_produto"];
        }

        if (strlen($pedido_em_garantia_finalidades_diversas) == 0)
            $xpedido_em_garantia_finalidades_diversas = "'f'";
        if($pedido_em_garantia_finalidades_diversas=='t')
            $xpedido_em_garantia_finalidades_diversas = "'$pedido_em_garantia_finalidades_diversas'";

        $sql="SELECT posto FROM tbl_posto where cnpj ='$xcnpj'";
        $res = pg_query($con,$sql);
        $msg_erro .= pg_last_error($con);
        if(pg_num_rows($res) >0){
            $posto=pg_fetch_result($res,0,posto);
        }

        $vCodigo = trim($_POST['codigo']);
        $sqlSenha = "SELECT posto 
                     FROM tbl_posto_fabrica 
                     JOIN tbl_fabrica USING(fabrica) 
                     WHERE codigo_posto = '$vCodigo' 
                     AND senha = '$senha' 
                     AND length(senha) > 3 
                     AND tbl_posto_fabrica.fabrica <> $login_fabrica 
                     AND ativo_fabrica";                     
        $qrySenha = pg_query($con, $sqlSenha);

        if (pg_num_rows($qrySenha) > 0) {
            $msg_erro = traduz('Senha inválida, favor escolher outra.');
        }

        if (in_array($login_fabrica, array(158))) {
            $distribuidores_selected = $_POST['distribuidores_selected'];
            if (count($distribuidores_selected) == 0) {
                $msg_erro = traduz('É necessário selecionar pelo menos uma unidade de negócio');
            }

            $tipos_atendimento = $_POST['selected_atendimentos'];
            if (count($tipos_atendimento) == 0) {
                $msg_erro = traduz('É necessário selecionar tipos de atendimento para o posto');
            }

            $unidade_principal = $_POST['unidade_principal'];
            if (strlen($unidade_principal) == 0) {
                $msg_erro = traduz('É necessário marcar a unidade de negócio principal do posto');
            }

            $grupos_clientes = $_POST['selected_grupos'];
        }

        if (in_array($login_fabrica, [177])) {
            $ferramentas_posto         = $_POST['ferramentas_posto'];
            $marcar_atendidas          = $_POST['marcar_atendidas'];
            $val_parametros_adicionais = array();
            $val_parametros_adicionais['ferramentas_posto'] = $ferramentas_posto;
            $val_parametros_adicionais['marcar_atendidas']  = $marcar_atendidas;
        }

        /**
         * SE HOUVER ALGUM CAMPO QUE PRECISA DE VALIDAÇÃO DE INT, ADICIONAR AQUI
         */
        $campos_int_verificar = array(
            '"Desconto"'             => 'desconto',
            '"Desconto Acessório"'   => 'desconto_acessorio',
            '"Imposto IVA"'          => 'imposto_al',
            '"Custo Administrativo"' => 'custo_administrativo',
            '"Valor KM"'             => 'valor_km',
            '"Acréscimo Tributário"' => 'acrescimo_tributario',
            '"Taxa Administrativa"'  => 'taxa_administrativa'
        );
        $error_inteiros = array();

        foreach ($campos_int_verificar as $text => $value) {

            $$value = str_replace(',', '.', $$value);

            if ( isset($$value) and strlen($$value)>0 and !is_numeric($$value) ) {

                $error_inteiros[] = traduz("Campo $text deve conter apenas números");

            }

        }

        
        if (count($error_inteiros)>0) {
            $msg_erro = implode('<br>', $error_inteiros);
        }
                
	$sqlVerificaEstado = "SELECT estado FROM tbl_estado WHERE estado = '{$estado}' AND pais = '{$pais_cadastro}' UNION SELECT estado FROM tbl_estado_exterior WHERE estado = '{$estado}' AND pais = '{$pais_cadastro}'";
	$resVerificaEstado = pg_query($con, $sqlVerificaEstado);
        if (pg_num_rows($resVerificaEstado) == 0) { 
            $xestado = "'EX'";
        }else{
	    $xestado = "'".pg_fetch_result($resVerificaEstado,0,0)."'";
	}

        if (empty($xcidade) OR $xcidade == "null") {
            $xcidade = "'".trim($_REQUEST['cidade_txt'])."'";
        }

        if (is_numeric($xcidade)) {
            $query = "SELECT nome FROM tbl_cidade WHERE cidade = $xcidade";
            $xcidade = pg_query($con,$query);
            $xcidade = "'".pg_fetch_result($xcidade, 0, nome)."'";
        }
   
        if (strlen($msg_erro) == 0) {
            $res = pg_query ($con,"BEGIN TRANSACTION");

            #----------------------------- Alteração de Dados ---------------------
            if (strlen ($posto) == 0) {

                #-------------- INSERT ---------------
                $sql = "INSERT INTO tbl_posto (
                            nome            ,
                            cnpj            ,
                            ie              ,";
                if($login_fabrica == 15 || $login_fabrica == 151){
                    $sql  .= "im            ,";
                }
                $sql .= "   endereco        ,
                            numero          ,
                            complemento     ,
                            bairro          ,
                            cep             ,
                            cidade          ,
                            estado          ,
                            email           ,";
                if($login_fabrica == 15 or $login_fabrica == 74 or $login_fabrica == 151){
                    $sql  .= "telefones     ,";
                }else{
                    $sql .= "
                              fone          ,";

                }

                    $sql .="
                            fax             ,
                            nome_fantasia   ,
                            capital_interior,
                            pais            ,
                            suframa";
                    $sql .= ") VALUES (
                            '$nome'                  ,
                            '$xcnpj'                 ,
                            $xie                     ,";
                if($login_fabrica == 15 || $login_fabrica == 151){
                    $sql  .= "$xim            ,";
                }
                    $sql  .= "$xendereco                 ,
                            $xnumero                 ,
                            $xcomplemento            ,
                            $xbairro                 ,
                            $xcep                    ,
                            {$xcidade}             ,
                            {$xestado}             ,
                            $xemail                  ,
                            $xfone                   ,
                            $xfax                    ,
                            $xnome_fantasia          ,
                            upper($xcapital_interior),
                            '$pais_cadastro'         ,
                            $xsuframa";

                    $sql .= ") RETURNING posto";

                $res = pg_query($con,$sql);

                $posto = pg_fetch_result($res, 0, posto);        

                if (!is_resource($res)) {   // Se usar o pg_last_error/pg_errormessage não vai devolver o erro do CNPJ e sim o do 'current transaction aborted'
                    $erro_cnpj = explode('.',pg_last_error($con));
                    $msg_erro   = preg_replace('/ERROR: /','',$erro_cnpj[0]);
                    unset($erro_cnpj);
                }

                if (strlen($msg_erro) == 0){
                    $sql = "SELECT CURRVAL ('seq_posto')";
                    $res = pg_query ($con,$sql);
                    $posto = pg_fetch_result ($res,0,0);
                    $msg_erro = pg_errormessage ($con);
                    $novo_posto = $posto;
                }

                if($login_fabrica == 1){
                    if (empty($_POST['recebeTaxaAdm'])) {
                        $msg_erro .= traduz("Recebe Taxa Administrativa Obrigatória")." <Br>";
                    } else {
                        $xtaxa_administrativa = (strlen(trim($xtaxa_administrativa)) == 0) ? 1.1 : $xtaxa_administrativa;
                        $sql = "INSERT INTO tbl_excecao_mobra(posto,fabrica,tx_administrativa) VALUES ($posto,$login_fabrica,$xtaxa_administrativa)";
                        $res = pg_query($con,$sql);
                    }
                }
            } else {

                if (!in_array($login_fabrica, [180,181,182])) { 
                    $sql = "UPDATE tbl_posto SET
                            suframa = $xsuframa,
                            capital_interior = $xcapital_interior,
                            ie = $xie
                        WHERE posto = $posto ";
                    $res = pg_query($con,$sql);
                }
                
            }
                    
            if(in_array($login_fabrica, array(81,114,122,123,125,153))){

                $sql_posto_extra = "UPDATE tbl_posto_extra SET atende_pedido_faturado_parcial = false WHERE posto = {$posto}";
                $res_posto_extra = pg_query($con, $sql_posto_extra);

            }

            // grava posto_fabrica
            if (strlen($msg_erro) == 0 and strlen($posto) > 0) {
                $parametros_adicionais = null ;

                $sqlPostoAdd = "SELECT parametros_adicionais FROM tbl_posto_fabrica WHERE posto = $posto AND fabrica=$login_fabrica";
                $resPostoAdd = pg_query($con, $sqlPostoAdd);
                if (pg_num_rows($resPostoAdd) > 0) {
                    $parametros_adicionais = pg_fetch_result($resPostoAdd, 0, "parametros_adicionais");
                }

                // HD 110541
                if($login_fabrica==11 or $login_fabrica == 172){
                    $atendimento_lenoxx  = trim($_POST['atendimento_lenoxx']);
                }
                $codigo_posto  = trim($_POST['codigo']);
                $senha         = trim($_POST['senha']);
                $posto_empresa = trim($_POST['posto_empresa']);

                if (in_array($login_fabrica, array(120,201))) {
                    $sqlPostoAdd = "SELECT parametros_adicionais FROM tbl_posto_fabrica WHERE codigo_posto = '$vCodigo' AND fabrica=$login_fabrica";
                    $resPostoAdd = pg_query($con, $sqlPostoAdd);
                    if (pg_num_rows($resPostoAdd) > 0) {
                        $adicionais = json_decode(pg_fetch_result($resPostoAdd, 0, "parametros_adicionais"),1);
                        $parametros_adicionais = json_encode(array(
                            "geracao_extrato" => $adicionais['geracao_extrato'],
                            "km_apartir"      => $km_apartir
                            )
                        );
                    } else {
                        $parametros_adicionais = json_encode(array("km_apartir" => $km_apartir));

                    }
                }

                if($login_fabrica == 42){
                    if(empty($pedido_faturado)){
                        $pedido_faturado = 'f';
                    }
                    if(empty($pedido_consumo_proprio)){
                        $pedido_consumo_proprio = 'f';
                    }
                    $parametros_adicionais = json_encode(array(
                        "pedido_venda" => $pedido_faturado,
                        "pedido_consumo" => $pedido_consumo_proprio,
                        )
                    );
                }

                if ($login_fabrica == 74) {
					if($tipo_posto == 437) {
						$qtde_revenda_os_vinculada = $_POST["qtde_revenda_os_vinculada"];
						if(!empty($qtde_revenda_os_vinculada)){

							/* monta json para gravar em parametros adicionais */
							$arrParametrosAdicionais = array();
							for($i = 0; $i < $qtde_revenda_os_vinculada; $i++){
								$revenda = $_POST["hidden_revenda_".$i];
								$arrParametrosAdicionais["revendaOSVinculada"][] = $revenda;
							}

							$parametros_adicionais = json_encode($arrParametrosAdicionais);
						}else{
							$msg_erro = traduz("Selecione pelo menos uma revenda para o tipo de posto 'OS - Consumidor Vinculada'");
						}
					}elseif(!empty($posto)){
					    $sqlPostoAdd = "SELECT parametros_adicionais FROM tbl_posto_fabrica WHERE posto = $posto AND fabrica=$login_fabrica";
						$resPostoAdd = pg_query($con, $sqlPostoAdd);

                        $parametros_posto = json_decode(pg_fetch_result($resPostoAdd, 0, "parametros_adicionais"),1);
					}
					if(!is_array($parametros_posto)) {
						$parametros_posto = array();
					}
                }

                if (in_array($login_fabrica, array(141,144))) {
                    $posto_troca = trim($_POST["posto_troca"]);

                    if ($posto_troca != "t") {
                        $posto_troca = "f";
                    }

                    $parametros_adicionais = json_encode(array("posto_troca" => $posto_troca));
                }

                if (in_array($login_fabrica, array(15))) {
                    $email2 = $_POST["email2"];
                    $nf_obrigatorio = $_POST['nf_obrigatorio'];
                    $loja_b2b = $_POST['loja_b2b'];

                    if (empty($nf_obrigatorio)) {
                        $nf_obrigatorio = 'f';
                    } else{
                        $nf_obrigatorio = 't';
                    }

                    if (empty($loja_b2b)) {
                        $loja_b2b = 'f';
                    } else{
                        $loja_b2b = 't';
                    }

                    $parametros_adicionais = json_encode(array(
                        "email2"=> $email2,
                        "nf_obrigatorio"  => $nf_obrigatorio,
                        "loja_b2b" => $loja_b2b
                        ));
                }

                if($login_fabrica == 6){
                    $meses_extrato = $_POST['meses_extrato'];

                    //$meses_extrato = (int)$mes_extrato;

                    if($meses_extrato < 3 OR $meses_extrato > 16){
                        $msg_erro = traduz("Campo Meses Extrato tem que estar entre 3 e 16 Meses");
                    }

                    if(empty($msg_erro)){
                        $parametros_adicionais = json_encode(array("meses_extrato"=> $meses_extrato));
                    }
                }


                if($login_fabrica == 1){
                    $obs_posto_cadastrado     = filter_input(INPUT_POST,'obs_posto_cadastrado');
                    $recebeTaxaAdm            = filter_input(INPUT_POST,'recebeTaxaAdm');
                    $pedido_faturado_locadora = filter_input(INPUT_POST,'pedido_faturado_locadora');
                    if (empty($msg_erro)) {
                        //hd_chamado=2693784
                        $obs_posto_cadastrado = utf8_encode($obs_posto_cadastrado);
                        $parametros_adicionais = array(
                            "obs_posto_cadastrado"     => $obs_posto_cadastrado,
                            "tipo_contribuinte"        => $xtipo_contribuinte,
                            "recebeTaxaAdm"            => $recebeTaxaAdm,
                            "pedido_faturado_locadora" => $pedido_faturado_locadora
                        );

                        $parametros_adicionais = json_encode($parametros_adicionais);
                    }
                }


                if (in_array($login_fabrica, array(86))) {
                    $contrato = (!empty($_POST["contrato"])) ? "t" : "f";
                    $site     = (!empty($_POST["site"])) ? "t" : "f";
                    $kit_credenciamento = (!empty($_POST["kit_credenciamento"])) ? "t" : "f";

                    $parametros_adicionais = json_encode(array(
                        "contrato" => $contrato,
                        "site" => $site,
                        "kit_credenciamento" => $kit_credenciamento
                    ));
                }

                if (in_array($login_fabrica, array(151))) {
                    $parametros_adicionais = json_encode(array(
                        "qtde_os_item"   => $_POST['qtde_os_item'],
                        "valor_extrato"  => $_POST['valor_extrato'],
                        "valor_mao_obra" => $_POST['valor_mao_obra'],
                        "digito_agencia" => $_POST['digito_agencia'],
                        "digito_conta"   => $_POST['digito_conta']
                        )
                    );
                }

                if (in_array($login_fabrica, array(156))) {

                    foreach ($atestadoCapacitacao as $key => $value) {
                        if(strlen($_POST["capacitacao_$value"])>0){
                            $capacitacao_valores["data_capacitacao_$value"] = $_POST["data_capacitacao_$value"];
                        }
                    }
                    $parametros_adicionais = json_encode($capacitacao_valores);
                }

                if($login_fabrica == 35){

                    $parametros_adicionais = json_encode(array(
                        "obs_cadence" => pg_escape_string($con, utf8_encode($_POST["obs_cadence"])),
                        "obs_oster"   => pg_escape_string($con, utf8_encode($_POST["obs_oster"]))
                    ));

                    $parametros_adicionais = str_replace("\\", "\\\\\\", $parametros_adicionais);

                }

                if (!empty($gera_pedido)) {
                    $parametros_adicionais = json_decode($parametros_adicionais, true);
                    $parametros_adicionais['gera_pedido'] = $gera_pedido;
                    $parametros_adicionais = json_encode($parametros_adicionais);
                } 

                if ($login_fabrica == 115) {
                    $parametros_adicionais = json_decode($parametros_adicionais, true);
                    $parametros_adicionais['categoria_manual'] = $categoria_manual;
                    $parametros_adicionais = json_encode($parametros_adicionais);                    
                }

                if ($usa_cidade_estado_txt == "t") {
                    if (!empty($estado_txt)) {
                        if (is_array($parametros_adicionais)) {
                            $parametros_adicionais['estado'] = trim($estado_txt);
                        } else if (!empty($parametros_adicionais)) {
                            $parametros_adicionais = json_decode($parametros_adicionais, true);
                            $parametros_adicionais['estado'] = trim($estado_txt);
                        }
                        $parametros_adicionais = json_encode($parametros_adicionais);
                    }

                    if (!empty(trim($cobranca_estado_txt))) {
                        if (is_array($parametros_adicionais)) {
                            $parametros_adicionais['cobranca_estado'] = trim($cobranca_estado_txt);
                        } else if (!empty($parametros_adicionais)) {
                            $parametros_adicionais = json_decode($parametros_adicionais, true);
                            $parametros_adicionais['cobranca_estado'] = trim($cobranca_estado_txt);
                        }
                        $parametros_adicionais = json_encode($parametros_adicionais);
                    }
                }

                $escritorio_regional    = trim ($_POST['escritorio_regional']);
                $obs                    = trim ($_POST['obs']);
                $obs                    = str_replace(";",",",$obs);
                $obs                    = str_replace("'","\'",$obs);
                $transportadora         = trim ($_POST['transportadora']);

                //HD-808142
                if($login_fabrica == 52){
                    $tabela_servico          = trim ($_POST['tabela_servico']);
                    if(empty($tabela_servico)){
                        $msg_erro = traduz("Escolha uma tabela de serviço.");
                    }
                }
                $cobranca_endereco       = trim ($_POST['cobranca_endereco']);
                $cobranca_numero         = trim ($_POST['cobranca_numero']);
                $cobranca_complemento    = trim ($_POST['cobranca_complemento']);
                $cobranca_bairro         = substr(trim($_POST['cobranca_bairro']), 0, 30);
                $cobranca_cep            = trim ($_POST['cobranca_cep']);
                $cobranca_cidade         = substr(trim ($_POST['cobranca_cidade']),0,40);
                $cobranca_estado         = trim ($_POST['cobranca_estado']);
                $desconto                = trim ($_POST['desconto']);
                $valor_km                = trim ($_POST['valor_km']);
                $desconto_acessorio      = trim ($_POST['desconto_acessorio']);
                $custo_administrativo    = trim ($_POST['custo_administrativo']);
                $imposto_al              = trim ($_POST['imposto_al']);
                $pedido_em_garantia      = trim($_POST['pedido_em_garantia']);
                $coleta_peca             = trim($_POST['coleta_peca']);
                $reembolso_peca_estoque  = trim($_POST['reembolso_peca_estoque']);
                $pedido_faturado         = trim($_POST['pedido_faturado']);
                $tipo_atende             = trim($_POST['tipo_atende']);

                if($login_fabrica == 74){
					$digita_os_fogo               = trim($_POST['digita_os_fogo']);
					$digita_os_portateis          = trim($_POST['digita_os_portateis']);

					$divulgar_consumidor_callcenter_fogo = trim($_POST["divulgar_consumidor_callcenter_fogo"
						]);
					$divulgar_consumidor_callcenter_portateis = trim($_POST["divulgar_consumidor_callcenter_portateis"]);


					$divulgar_consumidor_mapa_fogo = trim($_POST["divulgar_consumidor_mapa_fogo"]);
					$divulgar_consumidor_mapa_portateis = trim($_POST["divulgar_consumidor_mapa_portateis"]);

					if($divulgar_consumidor_mapa_fogo == 't' OR
						$divulgar_consumidor_mapa_portateis == 't' OR
						$divulgar_consumidor_callcenter_fogo == 't' OR
						$divulgar_consumidor_callcenter_portateis == 't'){
							$divulgar_consumidor = 't';
						}else{
							$divulgar_consumidor = 'f';
						}

					if($digita_os_portateis == 't' OR $digita_os_fogo == 't'){
						$digita_os = 't';
					}else{
						$digita_os = 'f';
					}

					$parametros_posto["divulgar_consumidor_mapa_fogo"] = ($divulgar_consumidor_mapa_fogo == 't') ? 't': 'f';

					$parametros_posto["divulgar_consumidor_mapa_portateis"] = ($divulgar_consumidor_mapa_portateis == 't') ? 't': 'f';

					$parametros_posto["divulgar_consumidor_callcenter_fogo"] = ($divulgar_consumidor_callcenter_fogo == 't') ? 't': 'f';

					$parametros_posto["divulgar_consumidor_callcenter_portateis"] = ($divulgar_consumidor_callcenter_portateis == 't') ? 't': 'f';
					$parametros_posto["digita_os_fogo"] = ($digita_os_fogo == 't') ? 't': 'f';
					$parametros_posto["digita_os_portateis"] = ($digita_os_portateis == 't') ? 't': 'f';

                }else{
                    $digita_os               = trim($_POST['digita_os']);
                }

                if($login_fabrica == 3){
                    $parametros_posto["bloqueado_pagamento"] = ($_POST["bloqueado_pagamento"] == "t") ? "t" : "f";
                }

        		if (in_array($login_fabrica, array(3))) {
        		    $parametros_posto["chat_online"] = ($_POST["chat_online"] == "t") ? "t" : "f";
        		}

                $controla_estoque        = trim($_POST['controla_estoque']);
                $km_apartir              = trim($_POST['km_apartir']);
                if ($login_fabrica == 15){
                    $tipo_controle_estoque = trim($_POST ['tipo_controle_estoque']);
                    $contato_cel = trim($_POST['contato_cel']);

                }
                if($login_fabrica == 151){
                    $contato_cel = trim($_POST['contato_cel']);
                }
                if($login_fabrica == 35 ){
                    $contato_cel = trim($_POST['celular_contato']);
                }

                $prestacao_servico       = trim($_POST['prestacao_servico']);
                $prestacao_servico_sem_mo= trim($_POST['prestacao_servico_sem_mo']);
                $pedido_bonificacao      = trim($_POST['pedido_bonificacao']);
                $banco                   = trim($_POST['banco']);
                $agencia                 = trim($_POST['agencia']);
                $conta                   = trim($_POST['conta']);
                $favorecido_conta        = pg_escape_string(trim($_POST['favorecido_conta']));
                $conta_operacao          = trim($_POST['conta_operacao']);//HD 8190 5/12/2007 Gustavo
                $cpf_conta               = trim($_POST['cpf_conta']);
                $tipo_conta              = trim($_POST['tipo_conta']);
                $obs_conta               = trim($_POST['obs_conta']);
                $obs_conta               = str_replace(";",",",$obs_conta);
                $obs_conta               = str_replace("'","\'",$obs_conta);
                $pedido_via_distribuidor = trim($_POST['pedido_via_distribuidor']);
                $pais_cadastro           = trim($_POST['pais']);
                $pais_cadastro 		 = (empty($pais_cadastro)) ? trim($_POST['pais_posto']) : $pais_cadastro;
                $garantia_antecipada     = trim($_POST['garantia_antecipada']);
                // HD 12104
                $imprime_os              = trim($_POST['imprime_os']);
                #HD 407694
                $acrescimo_tributario    = trim($_POST['acrescimo_tributario']);
                if($login_fabrica == 1){
                    if (empty($recebeTaxaAdm)) {
                        $msg_erro .= traduz("Recebe Taxa Administrativa Obrigatória")." <Br>";
                    } else {
                        $taxa_administrativa = (strlen(trim($_POST['taxa_administrativa'])) == 0) ? "" : trim($_POST['taxa_administrativa']);
                    }
                }else{
                    $taxa_administrativa     = (empty(trim($_POST['taxa_administrativa']))) ? 0 : trim($_POST['taxa_administrativa']);
                }
                // HD 17601
                $qtde_os_item            = trim($_POST['qtde_os_item']);
                $escolhe_condicao        = trim($_POST['escolhe_condicao']);
                // ! HD 121248 (augusto) - Atendente de callcenter para o posto
                $admin_sap               = (int) $_POST['admin_sap'];
                $admin_sap               = (empty($admin_sap)) ? 'null' : $admin_sap ;

                if ($login_fabrica == 3) {
                    $admin_sap_especifico = $_POST["admin_sap_especifico"];
                    $admin_sap_especifico = (empty($admin_sap_especifico)) ? 'null' : $admin_sap_especifico;
                }

                // HD 126810
                $atende_consumidor       = (strlen(trim($_POST ['atende_consumidor']))>0) ? "'t'" : "'f'";
                // hd 21496 - Francisco - campo Data da Nomeação para Dynacom
            if ($login_fabrica==2) { $data_nomeacao           = trim ($_POST['data_nomeacao']);
                $data_nomeacao = (strlen($data_nomeacao) >0) ? "$data_nomeacao" : "0001-01-01";
            }

            if($login_fabrica == 30){ //HD 356653 Inicio
                $conta_contabil = trim($_POST['conta_contabil']);
                $centro_custo   = trim($_POST['centro_custo']);
        		$local_entrega  = trim($_POST['local_entrega']);

        		if($_POST['digita_os_consumidor']){
					$digita_os_consumidor  = trim($_POST['digita_os_consumidor']);
        			$xdigita_os_consumidor   = (strlen($digita_os_consumidor) > 0) ? $digita_os_consumidor : "f";
        			$parametros_posto['digita_os_consumidor']     = $xdigita_os_consumidor;
				}



                $fixo_km_valor  = str_replace(",",".",$_POST ['fixo_km_valor']);
                $qtde_km_posto  = filter_input(INPUT_POST,'qtde_km_posto',FILTER_SANITIZE_NUMBER_INT);

                $parametros_posto["valor_km_fixo"] = $fixo_km_valor;
                $parametros_posto["qtde_km_posto"] = $qtde_km_posto;
            } //HD 356653 Fim

            if ($login_fabrica == 20 and isset($_POST['foto_serie_produto'] )) {
                $parametros_posto['foto_serie_produto'] = 't';
            }

            //HD 1855233
            if($login_fabrica == 74){ //HD 356653 Inicio
                $fixo_km_valor = str_replace(".","",$_POST ['fixo_km_valor']);
	            $fixo_km_valor = str_replace(",",".",$fixo_km_valor);
	            $fixo_km_valor = (float) $fixo_km_valor;
                //echo $fixo_km_valor;exit;
                if(!empty($fixo_km_valor)){

                    $aux_fixo_km = '{"valor_km_fixo":"'.$fixo_km_valor.'"}';
                    $parametros_posto['valor_km_fixo'] = $fixo_km_valor;
                }
            }
            //HD 1855233 Fim

            if($login_fabrica == 151){
                $extrato_mais_3_meses = $_POST["extrato_mais_3_meses"];
            }

            if($login_fabrica == 104){ //HD 2303024
                $posto_auditado = $_POST['posto_auditado'];
                $aux_posto_auditado = '{"posto_auditado":"'.$posto_auditado.'"}';
            }

            if ($login_fabrica == 42){#HD 401553 INICIO
                $posto_filial                            = trim($_POST ['posto_filial']);
                $posto_filial = (empty($posto_filial)) ? 'f' : $posto_filial;
            }#HD 401553 FIM

            //HD 672836 - inicio
            if ($login_fabrica == 1 and empty($msg_erro)) {
                if ($admin_sap){
                    $sql = "SELECT admin from tbl_admin_atendente_estado where estado=$xestado and fabrica = $login_fabrica";

                    $res = pg_query($con,$sql);

                    $admin_atendente_estado = (pg_num_rows($res)>0) ? pg_result($res,0,0) : "" ;

                    if ($admin_sap <> $admin_atendente_estado){

                        $admin_sap_especifico = $admin_sap;
                        $admin_sap            = $admin_sap;

                    }else{

                        $admin_sap_especifico = "null";
                        $admin_sap            = $admin_atendente_estado;

                    }

                }

            }

            //HD 672836 - fim

            if (strlen($pais_cadastro) == 0) {
                $msg_erro = traduz("Selecione o país do Posto")."<br />";
            }

                if($login_fabrica==19){
                    $atende_comgas = trim($_POST ['atende_comgas']);
                    $atende_comgas = (strlen($atende_comgas)==0) ? "'f'" : "'t'";
                }
                $xcodigo_posto         = (strlen($codigo_posto) > 0)         ? "'" . strtoupper ($codigo_posto) . "'" : 'null';
                $xsenha                = (strlen($senha) > 0)                ? "'".$senha."'"                         : "'*'";
                $xdesconto             = (strlen($desconto) > 0)             ? "'".$desconto."'"                      : 'null';
                $xdesconto             = str_replace (",",".",$xdesconto);
                $valor_km              = str_replace (",",".",$valor_km);
                $desconto_acessorio    = str_replace (",",".",$desconto_acessorio);
                $imposto_al            = str_replace (",",".",$imposto_al);
                $custo_administrativo  = str_replace (",",".",$custo_administrativo);
                $xvalor_km             = (strlen($valor_km) > 0)             ? $valor_km                              : '0';
                $xdesconto_acessorio   = (strlen($desconto_acessorio) > 0)   ? "'".$desconto_acessorio."'"            : 'null';
                $xcusto_administrativo = (strlen($custo_administrativo) > 0) ? "'".$custo_administrativo."'"          : 0;
                $ximposto_al           = (strlen($imposto_al) > 0)           ? "'".$imposto_al."'"                    : 'null';
                $xposto_empresa        = (strlen($posto_empresa) > 0)        ? "'".$posto_empresa."'"                 : 'null';
                $xescritorio_regional  = (strlen($escritorio_regional) > 0)  ? "'".$escritorio_regional."'"           : 'null';
                $xobs                  = (strlen($obs) > 0)                  ? "'".$obs."'"                           : 'null';
                $xtransportadora       = (strlen($transportadora) > 0)       ? "'".$transportadora."'"                : 'null';
                $xcobranca_endereco    = (strlen($cobranca_endereco) > 0)    ? "E'".str_replace("'", "\'", $cobranca_endereco)."'"             : 'null';
                $xcobranca_numero      = (strlen($cobranca_numero) > 0)      ? "'".$cobranca_numero."'"               : 'null';
                $xcobranca_complemento = (strlen($cobranca_complemento) > 0) ? "'".$cobranca_complemento."'"          : 'null';
                $xcobranca_bairro      = (strlen($cobranca_bairro) > 0)      ? "'".$cobranca_bairro."'"               : 'null';
                $xacrescimo_tributario = (strlen($acrescimo_tributario) > 0) ? $acrescimo_tributario                  : '0';

                if ($login_fabrica == 1) {
                    $xtaxa_administrativa  = (strlen(trim($taxa_administrativa))== 0)  ? 10 : $taxa_administrativa;
                } else {
                    $xtaxa_administrativa  = (strlen($taxa_administrativa) > 0)  ? $taxa_administrativa                   : '0';
                }

                if ($xacrescimo_tributario > 0 || strpos($xacrescimo_tributario,',') > 0) {
                    $xacrescimo_tributario = str_replace('.','',$xacrescimo_tributario);
                    $xacrescimo_tributario = str_replace(',','.',$xacrescimo_tributario);
                }

                $xtaxa_administrativa = str_replace('.','',$xtaxa_administrativa);
                $xtaxa_administrativa = str_replace(',','.',$xtaxa_administrativa);

                if($xtaxa_administrativa > 0 || strpos($xtaxa_administrativa,',') > 0){
                    $xtaxa_administrativa = ($xtaxa_administrativa / 100) + 1;
                }

                if (strlen($cobranca_cep) > 0) {
                    $xcobranca_cep =   "'".preg_replace('/\D/', '', $cobranca_cep)."'";
                }else{
                    $xcobranca_cep = 'null';
                }

                $xcobranca_cidade        = (strlen($cobranca_cidade)        > 0) ? "E'".str_replace("'", "\'", $cobranca_cidade)."'"        : 'null';
                $xcobranca_estado        = (strlen($cobranca_estado)        > 0) ? "'".$cobranca_estado."'"        : 'null';
                $xobs                    = (strlen($obs)                    > 0) ? "'".pg_escape_string($con,$obs)."'"                    : 'null';
                $xpedido_em_garantia     = (strlen($pedido_em_garantia)     > 0) ? "'".$pedido_em_garantia."'"     : "'f'";
                $xcoleta_peca            = (strlen($coleta_peca)            > 0) ? "'".$coleta_peca."'"            : "'f'";
                $xreembolso_peca_estoque = (strlen($reembolso_peca_estoque) > 0) ? "'".$reembolso_peca_estoque."'" : "'f'";
                $xpedido_faturado        = (strlen($pedido_faturado)        > 0) ? "'".$pedido_faturado."'"        : "'f'";
                $xtipo_atende            = (strlen($tipo_atende)            > 0) ? "'".$tipo_atende."'"            : "'f'";
                $xtipo_contribuinte      = (strlen($tipo_contribuinte)      > 0) ? "'".$tipo_contribuinte."'"      : "'f'"; //hd_chamado=2693784
                $xdigita_os              = (strlen($digita_os)              > 0) ? "'".$digita_os."'"              : "'f'";
                $xcontrola_estoque       = (strlen($controla_estoque)       > 0) ? "'".$controla_estoque."'"       : "'f'";

                if ($login_fabrica == 42) {
					$entrega_tecnica = $_POST["entrega_tecnica"];

					if ($entrega_tecnica <> "t") {
						$entrega_tecnica = "f";
					}
					if(!empty($pedido_consumo_proprio)){
						$xpedido_faturado = "'t'";
					}                                                         

                }

                if ($login_fabrica == 158) {
                    $sqlTipoPosto = "
                        SELECT tipo_posto, tecnico_proprio
                        FROM tbl_tipo_posto
                        WHERE fabrica = $login_fabrica
                        AND tipo_posto = $tipo_posto
                        AND posto_interno IS NOT TRUE
                    ";
                    $resTipoPosto = pg_query($con, $sqlTipoPosto);

                    $inicio_trabalho  = $_POST["inicio_trabalho"];
                    $fim_trabalho     = $_POST["fim_trabalho"];
                    $qtde_atendimento = $_POST["qtde_atendimento"];

                    if (pg_num_rows($resTipoPosto) > 0) {
                        $tipo_posto_tecnico_proprio = pg_fetch_result($resTipoPosto, 0, "tecnico_proprio");

                        $inicio_valido = validaHora($inicio_trabalho);
                        $fim_valido = validaHora($fim_trabalho);

                        if (!$inicio_valido || !$fim_valido) {
                            $msg_erro .= traduz("Hora de Trabalho inicial/final inválida")."<br />";
                        } else {
                            $xinicio_trabalho = "'$inicio_trabalho:00'";
                            $xfim_trabalho    = "'$fim_trabalho:00'";
                        }

                        if ($tipo_posto_tecnico_proprio == "t" && !strlen($qtde_atendimento)) {
                            $msg_erro .= traduz("Informe a capacidade diária de atendimento do técnico")."<br />";
                        } else if ($tipo_posto_tecnico_proprio != "t" && !strlen($qtde_atendimento)) {
                            $xqtde_atendimento = "null";
                        } else {
                            $xqtde_atendimento = $qtde_atendimento;
                        }

                        if (empty($msg_erro)) {
                            $sel_tbl_tecnico = "SELECT * FROM tbl_tecnico WHERE posto = $posto AND fabrica = $login_fabrica LIMIT 1";
                            $res_tbl_tecnico = pg_query($con, $sel_tbl_tecnico);

                            if (pg_num_rows($res_tbl_tecnico) == 0) {
                                $sql_tbl_tecnico = "INSERT INTO tbl_tecnico (
                                    nome,
                                    posto,
                                    fabrica,
                                    qtde_atendimento,
                                    inicio_trabalho,
                                    cep,
                                    estado,
                                    cidade,
                                    bairro,
                                    endereco,
                                    numero,
                                    complemento,
                                    fim_trabalho
                                ) VALUES (
                                    (SELECT nome FROM tbl_posto WHERE posto = $posto),
                                    $posto,
                                    $login_fabrica,
                                    $xqtde_atendimento,
                                    $xinicio_trabalho,
                                    $xcep,
                                    $xestado,
                                    $xcidade,
                                    $xbairro,
                                    $xendereco,
                                    $xnumero,
                                    $xcomplemento,
                                    $xfim_trabalho
                                )";
                            } else {
                                $sql_tbl_tecnico = "
                                    UPDATE tbl_tecnico SET
                                        cep = $xcep,
                                        estado = $xestado,
                                        cidade = $xcidade,
                                        bairro = $xbairro,
                                        endereco = $xendereco,
                                        numero = $xnumero,
                                        complemento = $xcomplemento,
                                        qtde_atendimento = $xqtde_atendimento,
                                        inicio_trabalho = $xinicio_trabalho,
                                        fim_trabalho = $xfim_trabalho
                                    WHERE posto = $posto
                                    AND fabrica = $login_fabrica";
                            }

                            $qry_tbl_tecnico = pg_query($con, $sql_tbl_tecnico);
                        }
                    } else {
                        $xqtde_atendimento = "null";
                        $xinicio_trabalho  = "null";
                        $xfim_trabalho     = "null";
                    }
                }

                if ($login_fabrica == 15) { //HD 755863

                    if ($tipo_controle_estoque == 'nenhum'){

                        $xcontrola_estoque = 'false';
                        $xcontrole_estoque_novo = 'false';
                        $xcontrole_estoque_manual = 'false';

                    }elseif ($tipo_controle_estoque == 'estoque_normal'){

                        $xcontrola_estoque = 'true';
                        $xcontrole_estoque_novo = 'false';
                        $xcontrole_estoque_manual = 'false';

                    }elseif($tipo_controle_estoque == 'estoque_novo'){

                        $xcontrola_estoque = 'false';
                        $xcontrole_estoque_novo = 'true';
                        $xcontrole_estoque_manual = 'false';

                    }elseif($tipo_controle_estoque == 'estoque_manual'){

                        $xcontrola_estoque = 'false';
                        $xcontrole_estoque_novo = 'false';
                        $xcontrole_estoque_manual = 'true';

                    }

                    $sql = "SELECT controla_estoque, controle_estoque_novo, controle_estoque_manual
                            FROM  tbl_posto_fabrica
                            WHERE fabrica = $login_fabrica
                            AND   posto = $posto";
                    $res = pg_query($con,$sql);

                    if (pg_num_rows($res)>0){

                        list($controla_estoque_cad,$controle_estoque_novo_cad,$controle_estoque_manual_cad) = pg_fetch_row($res,0);

                        if ( ( ($controla_estoque == 'f' and $controle_estoque_novo_cad == 'f' and $controle_estoque_manual_cad == 'f') or $controla_estoque_cad == 't' ) and $tipo_controle_estoque == 'estoque_novo' ){

                            // SEGUNDO SOLICITAÇÃO DO CLIENTE, NO MOMENTO QUE O ADMIN MUDAR O TIPO DE CONTROLE DE
                            // ESTOQUE DE UM DETERMINADO POSTO, SERÁ ZERADO O ESTOQUE DE TODAS AS PEÇAS EXISTENTES...

                            $sql_update_estoque = "
                                UPDATE tbl_estoque_posto
                                SET qtde = 0
                                WHERE posto= $posto
                                and fabrica = $login_fabrica
                            ";

                            $res_update_estoque = pg_query($con,$sql_update_estoque);

                        }
                    }

                }

                $xprestacao_servico        = (strlen($prestacao_servico) > 0)    ? "'".$prestacao_servico."'"        : "'f'";
                $xprestacao_servico_sem_mo = (!empty($prestacao_servico_sem_mo)) ? "'".$prestacao_servico_sem_mo."'" : "'f'";
                $xgarantia_antecipada      = (strlen($garantia_antecipada) > 0)  ? "'".$garantia_antecipada."'"      : "'f'";
                $xpedido_bonificacao       = (strlen($pedido_bonificacao) > 0)   ? "'".$pedido_bonificacao."'"       : "'f'";

                if (strlen($banco) > 0) {
                    $xbanco = "'".$banco."'";
                    $sqlB = "SELECT nome FROM tbl_banco WHERE codigo = '$banco'";
                    $resB = pg_query($con,$sqlB);
                    if (pg_num_rows($resB) == 1) {
                        $xnomebanco = "'" . trim(pg_fetch_result($resB,0,0)) . "'";
                    }else{
                        $xnomebanco = "null";
                    }
                }else{
                    $xbanco     = "null";
                    $xnomebanco = "null";
                }

                $xagencia          = (strlen($agencia)          > 0) ? "'".$agencia."'"          : 'null';
                $xconta            = (strlen($conta)            > 0) ? "'".$conta."'"            : 'null';
                $xfavorecido_conta = (strlen($favorecido_conta) > 0) ? "'".$favorecido_conta."'" : 'null';
                $xconta_operacao   = (strlen($conta_operacao)   > 0) ? "'".$conta_operacao."'"   : 'null';
                $xtipo_conta       = (strlen($tipo_conta)       > 0) ? "'".$tipo_conta."'"       : 'null';

                $cpf_conta = str_replace (".","",$cpf_conta);
                $cpf_conta = str_replace ("-","",$cpf_conta);
                $cpf_conta = str_replace ("/","",$cpf_conta);
                $cpf_conta = str_replace (" ","",$cpf_conta);

                if (strlen($cpf_conta) <> 14 AND $tipo_conta == 'Conta jurídica'){
                    $msg_erro = traduz("CNPJ da Conta jurídica inválida");
                }

                $xcpf_conta               = (strlen($cpf_conta) > 0) ? "'".$cpf_conta."'" : 'null';
                $xobs_conta               = (strlen($obs_conta) > 0) ? "'".$obs_conta."'" : 'null';
                $xpedido_via_distribuidor = (strlen($pedido_via_distribuidor) > 0) ? "'".$pedido_via_distribuidor."'" : "'f'";

                    // HD 17601
                    if(strlen($qtde_os_item)==0){
                        if($login_fabrica==45){
                            $msg_erro=traduz("Por favor, preencher a quantidade de itens na OS que o posto pode lançar");
                        }else{
                            $qtde_os_item="0";
                        }
                    }

                if ($login_fabrica == 3 and (empty($admin_sap) or $admin_sap == 'null')) {
                    $msg_erro.= traduz('Por favor, selecione o inspetor para esse posto.');
                }

                if (strlen($msg_erro) == 0 AND strlen($posto) > 0) {
                    $sql = "SELECT  tbl_posto_fabrica.*
                            FROM    tbl_posto_fabrica
                            WHERE   tbl_posto_fabrica.posto   = $posto
                            AND     tbl_posto_fabrica.fabrica = $login_fabrica ";
                    $res = pg_query($con,$sql);
                    if (strlen (pg_errormessage ($con)) > 0) $msg_erro = pg_errormessage($con);
                }

                //Lenoxx não pode repetir código do posto, pois nº da OS é gerado pelo codigo
                if ( strlen($msg_erro) == 0 AND strlen($posto) > 0 AND strlen($xcodigo_posto) > 0 ) {
                    $sqlx = "SELECT  tbl_posto_fabrica.*
                            FROM    tbl_posto_fabrica
                            WHERE   tbl_posto_fabrica.posto       <> $posto
                            AND     tbl_posto_fabrica.fabrica      = $login_fabrica
                            AND     tbl_posto_fabrica.codigo_posto = $xcodigo_posto";
                    $resx = pg_query($con,$sqlx);
                    if (pg_num_rows($resx) > 0) $msg_erro = traduz("Já existe um posto cadastrado com o código $xcodigo_posto");
                }

                if (strlen($msg_erro) == 0){
                    $total_rows = pg_num_rows($res);
                    //HD 15225
                    if ($login_fabrica == 3) {
                        $xpedido_via_distribuidor = "'t'";
                        $xpedido_em_garantia      = "'f'";
                        $xreembolso_peca_estoque  = "'f'";
                    }

                    //HD 12104

                    if($login_fabrica == 14){
                        $imprime_os = (strlen($imprime_os) > 0) ? 't' : 'f';
                    } else {
                        $imprime_os='f';
                    }

                    if($login_fabrica==7 AND $xposto_empresa<>'null'){
                        $sqlp = "SELECT posto
                                FROM tbl_posto_fabrica
                                WHERE codigo_posto = $xposto_empresa
                                AND   fabrica      = $login_fabrica";
                        $resp = @pg_query ($con,$sqlp);
                        $msg_erro = pg_errormessage($con);
                        if (pg_num_rows ($resp) > 0) {
                            $xposto_empresa = pg_fetch_result($resp, 0, posto);
                        }else{
                            $msg_erro = traduz("Código posto empresa não encontrado.");
                        }
                    }

                    if ($login_fabrica == 20 and strlen($posto) > 0) {
                        // HD 31884
                        $sql_t="SELECT  tbl_posto_fabrica.* ,
                                nome                ,
                                cnpj                ,
                                ie                  ,
                                capital_interior    ,
                                suframa             ,
                                pais                ,
                                contato
                            INTO  TEMP tmp_posto_$login_admin
                            FROM  tbl_posto_fabrica
                            JOIN  tbl_posto USING (posto)
                            WHERE tbl_posto_fabrica.posto  = $posto
                            AND   tbl_posto_fabrica.fabrica= $login_fabrica;

                        CREATE INDEX tmp_posto_posto_$login_admin ON tmp_posto_$login_admin(posto); ";

                        $res_t=pg_query($con,$sql_t);
                    }
                        $upd_valor_km = "valor_km                = $xvalor_km               ,";


                    if ( isset($_POST['controla_estoque']) )
                        $x_controla_estoque = $_POST['controla_estoque'];
                    else
                        $x_controla_estoque = 'f';

                    if ($login_fabrica == 15 && $_POST['posto_vip'] && $_POST['posto_vip']=='vip') {
                        $posto_vip = $_POST['posto_vip'];
                    }else if($login_fabrica == 15 && $_POST['posto_vip'] != 'vip'){
                        $posto_vip = "";
                    }

                    if ($login_fabrica == 1) {
                        if ($_POST['categoria_posto']) {
                            $categoria_posto = $_POST['categoria_posto'];
                            if ($categoria_posto == "Compra Peca") {
                                $divulgar_consumidor = "f";
                                $digita_os = "";
                            }
                        } else {
                            $categoria_posto = "";
                        }

                        if (!empty($posto) && (!empty($tipo_posto) || !($categoria_posto) || !empty($xreembolso_peca_estoque) || !empty($taxa_administrativa) || !empty($recebeTaxaAdm))) {

                            $sqlVerDiff = "
                                SELECT  tbl_posto_fabrica.tipo_posto,
                                        tbl_tipo_posto.descricao AS tipo_posto_descricao,
                                        tbl_posto_fabrica.categoria,
                                        tbl_posto_fabrica.reembolso_peca_estoque,
                                        CASE WHEN tbl_posto_fabrica.parametros_adicionais~'\\\\\\\'
                                        THEN tbl_posto_fabrica.parametros_adicionais::JSONB->>'recebeTaxaAdm' ELSE json_field('recebeTaxaAdm' , tbl_posto_fabrica.parametros_adicionais) END AS recebe_taxa,
                                        tbl_posto_fabrica.credenciamento,
                                        tbl_excecao_mobra.tx_administrativa
                                FROM    tbl_posto_fabrica
                                JOIN    tbl_tipo_posto    USING(tipo_posto,fabrica)
                                JOIN    tbl_excecao_mobra USING (posto,fabrica)
                                WHERE   fabrica = $login_fabrica
                                AND     posto   = $posto
                            ";

                            $resVerDiff = pg_query($con,$sqlVerDiff);

                            $comparaTaxa = (!empty($xtaxa_administrativa) && $xtaxa_administrativa != '0') ? number_format((($xtaxa_administrativa * 100) - 100),2,'.','') : '0.00';

                            while ($diff = pg_fetch_object($resVerDiff)) {
                                $taxaAtual = ($diff->tx_administrativa == 0) ? "0.00" : number_format((($diff->tx_administrativa * 100) - 100),2,'.','');
								if(empty($diff->recebe_taxa)) $diff->recebe_taxa = 'nao';

                                if ($tipo_posto != $diff->tipo_posto || $categoria_posto != $diff->categoria || $comparaTaxa != $taxaAtual || $reembolso_peca_estoque != "'".$diff->reembolso_peca_estoque."'" || $recebeTaxaAdm != $diff->recebe_taxa) {

                                    if (!empty($tipo_posto) && $tipo_posto != $diff->tipo_posto) {
                                        $sqlTipoNovo = "SELECT descricao FROM tbl_tipo_posto WHERE fabrica = $login_fabrica AND tipo_posto = $tipo_posto";
                                        $resTipoNovo = pg_query($con,$sqlTipoNovo);
                                        $tipo_novo = pg_fetch_result($resTipoNovo,0,descricao);
                                        $mensagemModificacao .= "<b>".traduz("Antes:").":</b> ".traduz("Tipo Posto")." = ".$diff->tipo_posto_descricao. "\n <b>".traduz("Depois").":</b> ".traduz("Tipo Posto")." = ".$tipo_novo."\n";

                                        $dadosAnteriores['tipo_posto'] = $diff->tipo_posto;
                                    }

                                    if (!empty($categoria_posto) && $categoria_posto != $diff->categoria) {


                                        if($categoria_posto == 'mega projeto'){
                                            $categoria_posto = "Industria/Mega Projeto";
                                        }

                                        if ($diff->categoria == 'mega projeto') {
                                            $diff_categoria_posto = "Industria/Mega Projeto";
                                        } else {
                                            $diff_categoria_posto = $diff->categoria;
                                        }

                                        $mensagemModificacao .= "<b>".traduz("Antes").":</b> ".traduz("Categoria")." = ".$diff->categoria. "\n <b>".traduz("Depois").":</b> ".traduz("Categoria")." = ".$categoria_posto."\n";
                                        $dadosAnteriores['categoria'] = $diff_categoria_posto;

                                    }

                                    if (isset($xtaxa_administrativa) && $comparaTaxa != $taxaAtual) {
                                        $mensagemModificacao .= "<b>".traduz("Antes").":</b> Taxa = ".$taxaAtual. "\n <b>".traduz("Depois").":</b> ".traduz("Taxa")." = ".($xtaxa_administrativa == 0 ? "0,00" : number_format((($xtaxa_administrativa * 100) - 100),2,'.','') )."\n";
                                        $dadosAnteriores['taxaAdm'] = $diff->tx_administrativa;
                                    }
                                    if (!empty($xreembolso_peca_estoque) && $xreembolso_peca_estoque != "'".$diff->reembolso_peca_estoque."'") {
                                        $mensagemModificacao .= "<b>".traduz("Antes").":</b> ".traduz("Peças em Garantia")." = ".(($diff->reembolso_peca_estoque == 't') ? traduz("SIM") : traduz("NÃO")). "\n <b>".traduz("Depois").":</b> ".traduz("Peças em Garantia")." = ".(($xreembolso_peca_estoque == "'t'") ? traduz("SIM") : traduz("NÃO"))."\n";
                                        $dadosAnteriores['reembolso'] = $diff->reembolso_peca_estoque;
                                    }
                                    if (!empty($recebeTaxaAdm) && $recebeTaxaAdm != $diff->recebe_taxa) {
                                        $mensagemModificacao .= "<b>".traduz("Antes").":</b> ".traduz("Recebe Taxa Administrativa")." = ".(($diff->recebe_taxa == "sim") ? traduz("SIM") : traduz("NÃO"))."\n <b>".traduz("Depois").":</b> ".traduz("Recebe Taxa Administrativa")." = ".(($recebeTaxaAdm == "sim") ? traduz("SIM") : traduz("NÃO"))."\n";
                                        $dadosAnteriores['recebeTaxa'] = $diff->recebe_taxa;
                                    }

                                    if (!empty($mensagemModificacao)) {

                                        $mensagemModificacao = utf8_encode($mensagemModificacao);

                                        $sql_credenciamento = "
                                        INSERT INTO tbl_credenciamento (
                                            fabrica,
                                            status,
                                            confirmacao_admin,
                                            posto,
                                            texto
                                            ) VALUES (
                                                $login_fabrica,
                                                'Pre Cadastro em apr',
                                                $login_admin,
                                                $posto,
                                                '<b>".traduz('Valores Modificados').":</b><br> $mensagemModificacao'
                                            )";
                                        $res_credenciamento = pg_query($con, $sql_credenciamento);

                                        $posto_pre = true;


                                        $dadosAnteriores['credenciamento'] = $diff->credenciamento;

                                        $parametros_adicionais = json_decode($parametros_adicionais,TRUE);
                                        $parametros_adicionais['dadosAnteriores'] = $dadosAnteriores;
                                        $parametros_adicionais = json_encode($parametros_adicionais);
                                    }
                                }
                            }
                        }
                    } else if ($login_fabrica == 115 and $categoria_manual == 't') {
                        /*
                         * - A mudança MANUAL da categoria do posto (NORDTECH)
                         * pode ser feita apenas 1 vez por MÊS, num limite máximo de
                         * TRÊS modificações.
                         * - Acima disso, apenas as modificações AUTOMÁTICAS, feitas todo
                         * dia PRIMEIRO de cada mês, serão permitidas.
                         */
                        $sqlCat = "
                            SELECT  categoria,
                                    parametros_adicionais::JSON->'manual'                       AS manual,
                                    parametros_adicionais::JSON->'ultima_categoria'             AS atual,
                                    COALESCE(parametros_adicionais::JSON->>'manual_mes','0')      AS manual_mes,
									COALESCE(parametros_adicionais::JSON->>'manual_total','0')    AS manual_total,
									case when to_char(data_alteracao,'MM') = to_char(current_date,'MM') then true else false end as mes_atual

                            FROM    tbl_posto_fabrica
                            WHERE   fabrica = $login_fabrica
                            AND     posto   = $posto
                        ";
                        
                        $resCat = pg_query($con,$sqlCat);

                        $atual          = pg_fetch_result($resCat,0,atual);
                        $manual         = pg_fetch_result($resCat,0,manual);
                        $manual_mes     = pg_fetch_result($resCat,0,manual_mes);
                        $manual_total   = pg_fetch_result($resCat,0,manual_total);
                        $mes_atual      = pg_fetch_result($resCat,0, 'mes_atual');

                        if ($categoria_posto != $atual) {
                            if (($manual_mes == 0 && $manual_total <= 3) or ($manual_mes > 0 and $mes_atual !='t')) {
                                $manual_mes = 1;
                                $manual_total++;

                                $sqlUpdCat = "
                                    UPDATE  tbl_posto_fabrica
                                    SET     parametros_adicionais = JSONB_SET(
                                                                        JSONB_SET(
                                                                            JSONB_SET(
                                                                                JSONB_SET(
                                                                                    JSONB_SET(
                                                                                        JSONB_SET(parametros_adicionais::JSONB,'{manual}','\"TRUE\"')::JSONB,'{manual_mes}','".(int)$manual_mes."'
                                                                                    )::JSONB,'{manual_total}','".(int)$manual_total."'
                                                                                )::JSONB,'{anterior_categoria}',parametros_adicionais::JSONB->'ultima_categoria'
                                                                            )::JSONB,'{ultima_categoria}','\"".$categoria_posto."\"'
                                                                        )::JSONB,'{tempo}','0'
                                                                    )
                                    WHERE   fabrica = $login_fabrica
                                    AND     posto   = $posto
                                ";
                                $resUpdCat = pg_query($con,$sqlUpdCat);
                            } else if ($manual_total > 3) {
                                $msg_erro .= traduz("Este posto não pode mais sofrer alterações manuais de sua categoria.");
                            } else if ($manual_mes > 0 and $mes_atual == 't') {
                                $msg_erro .= traduz("Este posto não pode mais sofrer alterações manuais de sua categoria este mês.");
                            }
                        }

                        if ($categoria_posto == "premium" && $tipo_posto != "371") {
                            $msg_erro .= traduz("Apenas postos do tipo ASSISTÊNCIA TÉCNICA pode ser PREMIUM.");
                        }
                    }
                    // HD 220549 - disponibiliza chamado apenas para esse flag como 'n'
                    if (isset($_POST['tela_os_nova'])) {

                        $sql_atendimento = "atendimento = 'n',"; // @todo colocar no insert e update

                    } else if ($login_fabrica == 20) {

                        $sql_atendimento = " atendimento = null, ";

                    }

                    //HD-1855233
                    if($login_fabrica == 74){
                        if (strpos($aux_fixo_km, "{") === false) {
                            $aux_fixo_km = (float) $aux_fixo_km;
                        }

                        if(!empty($aux_fixo_km)){
                            $upd_valor_km = "valor_km = 0,";
                        }else{
                            $parametros_posto['valor_km_fixo'] = '';
                            $upd_valor_km = "valor_km                = $xvalor_km               ,";
                        }
                    }

                    // Implantação Imbera - HD 2586842 (Latitude e Longitude)
                    if (in_array($login_fabrica, array(158))) {
                        $latitude = (strlen($_POST['latitude']) == 0) ? "null" : $_POST['latitude'];
                        $longitude = (strlen($_POST['longitude']) == 0) ? "null" : $_POST['longitude'];
                        $centro_custo   = trim($_POST['centro_custo']);
                        $conta_contabil = trim($_POST['conta_contabil']);

                        if ($latitude == "null" || $longitude == "null") {

                            $msg_erro .= traduz("É necessário a latitude e longitude do posto para cadastrar");
                        }
                    }

                    $campo_permite_envio_produto = '';
                    $valor_permite_envio_produto = '';

                    if ($login_fabrica == 178){
                        $centro_custo   = trim($_POST['centro_custo']);
                        $conta_contabil = trim($_POST['conta_contabil']);
                    }

                    if (in_array($login_fabrica, array(169,170))) {
                        $centro_custo   = trim($_POST['centro_custo']);
                        $conta_contabil = trim($_POST['conta_contabil']);
                        $e_ticket       = $_POST['e_ticket'];
                        $campo_permite_envio_produto = 'permite_envio_produto';
                        $valor_permite_envio_produto = (empty($e_ticket)) ? 'false' : 'true';

                        if (!empty($tipo_posto)){
                            $sql_tipo_revenda = "SELECT tipo_revenda FROM tbl_tipo_posto WHERE fabrica = {$login_fabrica} AND tipo_posto = {$tipo_posto} AND tipo_revenda = 't' ";
                            $res_tipo_revenda = pg_query($con,$sql_tipo_revenda);

                            if (pg_num_rows($res_tipo_revenda) > 0){
                                $x_abre_os_dealer = $_POST['abre_os_dealer'];
                                $x_abre_os_dealer = (empty($x_abre_os_dealer)) ? 'f' : 't';
                            }else{
                                $x_abre_os_dealer = "f";
                            }
                        }

                        if (!empty($posto)) {
                            $sqlParametrosAdicionais = "SELECT parametros_adicionais FROM tbl_posto_fabrica WHERE posto = {$posto} AND fabrica = {$login_fabrica};";
                            $resParametrosAdicionais = pg_query($con, $sqlParametrosAdicionais);
                            $parametros_adicionais = json_decode(pg_fetch_result($resParametrosAdicionais, 0,"parametros_adicionais"), true);
                        }

                        if (empty($parametros_adicionais)) {
                            $parametros_adicionais = array();
                        }

                        if (!empty($_POST["atendimento_chat"])) {
                            $parametros_adicionais["atendimento_chat"] = "t";
                        } else {
                            if (array_key_exists("atendimento_chat", $parametros_adicionais)) {
                                unset($parametros_adicionais["atendimento_chat"]);
                            }
                        }

                        if (!empty($_POST["matriz"])) {
                            $parametros_adicionais["matriz"] = "t";
                        } else {
                            if (array_key_exists("matriz", $parametros_adicionais)) {
                                unset($parametros_adicionais["matriz"]);
                            }
                        }

                        if (!empty($_POST['qtde_atendimento'])) {
                            $parametros_adicionais['qtde_atendimento'] = $_POST['qtde_atendimento'];
                        } else {
                            unset($parametros_adicionais['qtde_atendimento']);
                        }

                        if (!empty($x_abre_os_dealer)){
                            $parametros_adicionais['abre_os_dealer'] = $x_abre_os_dealer;
                        }

                        if (!empty($_POST['codigo_cliente'])) {
                            $parametros_adicionais['codigo_cliente'] = $_POST['codigo_cliente'];
                        }

                        unset($parametros_adicionais['escritorio_venda']);
                        unset($parametros_adicionais['equipe_venda']);
                        
                        if(!empty($_POST['escritorio_venda'])){
                            $parametros_adicionais['escritorio_venda'] = $_POST['escritorio_venda'];
                        }
                        if(!empty($_POST['equipe_venda'])){
                            $parametros_adicionais['equipe_venda'] = $_POST['equipe_venda'];
                        }                        

                        $horario_funcionamento_inicio = trim($_POST['horario_funcionamento_inicio']);
                        $horario_funcionamento_fim    = trim($_POST['horario_funcionamento_fim']);

                        if (!empty($horario_funcionamento_inicio) || !empty($horario_funcionamento_fim)) {

                            if (!validaHora($horario_funcionamento_inicio) || !validaHora($horario_funcionamento_fim)) {
                                $msg_erro .= "Horário de Funcionamento do posto informado inválido. Informe um horário válido. <br />";
                            }

                            $parametros_adicionais["inicio_horario_funcionamento"] = (string) $horario_funcionamento_inicio;
                            $parametros_adicionais["fim_horario_funcionamento"]    = (string) $horario_funcionamento_fim;
                        }


                        if (count($parametros_adicionais) == 0) {
                            $parametros_adicionais = '';
                        } else {
                            $parametros_adicionais = json_encode($parametros_adicionais);
                        }
                    }

                    if (in_array($login_fabrica, [177])) {
                        $ferramentas_posto  = array_map("utf8_encode", $_POST['ferramentas_posto']);
                        $marcar_atendidas   = array_map("utf8_encode", $_POST['marcar_atendidas']);

                        if (!empty($posto) && empty($parametros_adicionais)) {
                            $sqlParametrosAdicionais = "SELECT parametros_adicionais FROM tbl_posto_fabrica WHERE posto = {$posto} AND fabrica = {$login_fabrica};";
                            $resParametrosAdicionais = pg_query($con, $sqlParametrosAdicionais);
                            $parametros_adicionais = json_decode(pg_fetch_result($resParametrosAdicionais, 0,"parametros_adicionais"), true);
                            
                            if (empty($parametros_adicionais)) {
                                $parametros_adicionais = array();
                            }
                        }

                        if (!is_array($parametros_adicionais)) {
                            $parametros_adicionais = json_decode($parametros_adicionais, true);
                        }
                        
                        $parametros_adicionais['ferramentas_posto'] = $ferramentas_posto;
                        $parametros_adicionais['marcar_atendidas']  = $marcar_atendidas;
                        $parametros_adicionais                      = json_encode($parametros_adicionais);

		            }

                    if (in_array($login_fabrica, [183])) {
                        $encontro_de_contas  = filter_input(INPUT_POST,'encontro_de_contas',FILTER_SANITIZE_NUMBER_INT);
                    
                        if ($encontro_de_contas >= 100) {
                            $msg_erro .= "Saldo do pedido não pode ser igual ou superior a 100 %";
                        }

                        $sqlParametrosAdicionais = "SELECT parametros_adicionais FROM tbl_posto_fabrica WHERE posto = {$posto} AND fabrica = {$login_fabrica};";
                        $resParametrosAdicionais = pg_query($con, $sqlParametrosAdicionais);
                        $parametros_adicionais = json_decode(pg_fetch_result($resParametrosAdicionais, 0,"parametros_adicionais"), true);
                        
                        if (empty($parametros_adicionais)) {
                            $parametros_adicionais = array();
                        }
            
                        $parametros_adicionais['encontro_de_contas'] = $encontro_de_contas;
                        $parametros_adicionais = json_encode($parametros_adicionais);
                    }

                    if ($login_fabrica == 1) {
                        $tipo_posto_alterar = $_POST['tipo_posto'];
                        $categoria_posto_alterar = $_POST['categoria_posto'];
                        // Pra que mudar o padrão dos nomes dos campos???
                        $recebeTaxaAdm_alterar = $_POST['recebeTaxaAdm'];
                        $reembolso_peca_estoque_alterar = $_POST['reembolso_peca_estoque'];

                        if (empty($recebeTaxaAdm_alterar)) {
                            $recebeTaxaAdm_alterar = 'nao';
                        }

                        $sql_atual = "SELECT tipo_posto, categoria, reembolso_peca_estoque, parametros_adicionais
                                        FROM tbl_posto_fabrica WHERE posto = $posto AND fabrica = $login_fabrica";
                        $res_atual = pg_query($con, $sql_atual);

                        $tipo_posto_atual = pg_fetch_result($res_atual, 0, 'tipo_posto');
                        $categoria_posto_atual = pg_fetch_result($res_atual, 0, 'categoria');
                        $reembolso_peca_estoque_atual = pg_fetch_result($res_atual, 0, 'reembolso_peca_estoque');
                        $parametros_adicionais_atual = json_decode(pg_fetch_result($res_atual, 0, 'parametros_adicionais'), true);
                        $recebeTaxaAdm_atual = 'nao';

                        if (empty($reembolso_peca_estoque_alterar)) {
                            $reembolso_peca_estoque_alterar = 'f';
                        }

                        if (array_key_exists('recebeTaxaAdm', $parametros_adicionais_atual)) {
                            $recebeTaxaAdm_atual = $parametros_adicionais_atual['recebeTaxaAdm'];

                            if ($recebeTaxaAdm_atual == 'f') {
                                $recebeTaxaAdm_atual = 'nao';
                            }
                        }

                        $alteraveis = ['tipo_posto', 'categoria_posto', 'recebeTaxaAdm', 'reembolso_peca_estoque'];

                        foreach ($alteraveis as $alt) {
                            $new = $alt . '_alterar';
                            $old = $alt . '_atual';

                            if ($$new <> $$old) {
                                $campo = ($alt == 'categoria_posto') ? 'categoria' : $alt;
                                $ins_pend = "INSERT INTO tbl_posto_pendencia_alteracao(
                                    posto, fabrica, campo, valor
                                ) VALUES (
                                    $posto, $login_fabrica, '$campo', '{$$new}'
                                )";
                                //echo $ins_pend . '<br>' . $$old;
                                $res_pend = pg_query($con, $ins_pend);

                                $atualiza_posto_fabrica = false;
                            }
                        }
                    }

                    //HD-1855233 FIM
                    $AuditorLog = new AuditorLog;
                    $AuditorLog->RetornaDadosTabela('tbl_posto_fabrica',array("posto" => $posto,"fabrica" => $login_fabrica),'data_alteracao');

                    //Depois do insert - LOG
                    if ($login_fabrica == 115) {
                        if (isset($categoria_manual) && !empty($categoria_manual)) {
                            unset($xyparametros_adicionais);
                            $sql_parametros_add = " SELECT parametros_adicionais FROM tbl_posto_fabrica WHERE posto = $posto AND fabrica = $login_fabrica";
                            $res_parametros_add = pg_query($con, $sql_parametros_add);
                            if (pg_num_rows($res_parametros_add) > 0) {
                                $xyparametros_adicionais = json_decode(pg_fetch_result($res_parametros_add, 0, 'parametros_adicionais'), true);
                                $xyparametros_adicionais['categoria_manual'] = $categoria_manual;       
                            } else {
                                $xyparametros_adicionais['categoria_manual'] = $categoria_manual;
                            }
                            $xyparametros_adicionais = json_encode($xyparametros_adicionais);

                            $sql_upd = " UPDATE  tbl_posto_fabrica
                                         SET     parametros_adicionais = '$xyparametros_adicionais'
                                         WHERE   fabrica = $login_fabrica
                                         AND     posto   = $posto ";
                            $res_upd = pg_query($con, $sql_upd);  
                        }
                    }

                    if (empty($msg_erro)) {
                        
                        if (pg_num_rows ($res) > 0) {
                            #print_r($_POST);exit;

                            // ! Atualizar POSTO FABRICA
                            //UPDATE POSTO FABRICA
                            if (false === $atualiza_posto_fabrica) {
                                $xtipo_posto = $tipo_posto_atual;
                                $categoria_posto = $categoria_posto_atual;
                                $xreembolso_peca_estoque = "'{$reembolso_peca_estoque_atual}'";

                                $parametros_adicionais = json_decode($parametros_adicionais, true);
                                $parametros_adicionais['recebeTaxaAdm'] = $recebeTaxaAdm_atual;

                                $parametros_adicionais = json_encode($parametros_adicionais);
                            }

                            if (!empty($gera_pedido)) {
                                $parametros_posto['gera_pedido'] = $gera_pedido;
                            }

                            if($login_fabrica == 1){
                                $observacao_credenciamento =  pg_escape_string(utf8_encode($_POST["observacao_credenciamento"]));
                                $campos_observacao_credenciamento = "observacao_credenciamento = '$observacao_credenciamento' ,";
                            }

                            if ($login_fabrica == 171){
                                $codigo_fn = $_POST["codigo_fn"];
                            }

                            if($login_fabrica == 151 && !empty($tipo_frete)){
                                $parametros_adicionais = json_decode($parametros_adicionais,true);
                                $parametros_adicionais["frete"] = $tipo_frete;
                                $parametros_adicionais = json_encode($parametros_adicionais);
                            }

                        if ($login_fabrica == 189){
                            $parametros_adicionais = json_decode($parametros_adicionais, true);
                            $parametros_adicionais['tipo_cliente'] = $_POST["tipo_cliente"];
                            $parametros_adicionais['codigo_representante'] = $_POST["codigo_representante"];
                            $parametros_adicionais = json_encode($parametros_adicionais);
                        }
                        if ($login_fabrica == 190){
                            $parametros_adicionais = json_decode($parametros_adicionais, true);
                            if (!empty($_POST['qtde_atendimento'])) {
                                $parametros_adicionais['qtde_atendimento'] = $_POST['qtde_atendimento'];
                            } 
                            $parametros_adicionais = json_encode($parametros_adicionais);
                        }

                        if ($usa_cidade_estado_txt == "t") {
                            if (!empty($estado_txt)) {
                                if (is_array($parametros_adicionais)) {
                                    $parametros_adicionais['estado'] = trim($estado_txt);
                                } else if (!empty($parametros_adicionais)) {
                                    $parametros_adicionais = json_decode($parametros_adicionais, true);
                                    $parametros_adicionais['estado'] = trim($estado_txt);
                                }
                                $parametros_adicionais = json_encode($parametros_adicionais);
                            }
                        }


                        if (in_array($login_fabrica, [169, 170])) {
                            $parametros_adicionais = json_decode($parametros_adicionais, true);
                            $parametros_adicionais['mo_triagem'] = str_replace(",",".",$mo_triagem);
                            $parametros_adicionais['digita_os_revenda'] = $digita_os_revenda;
                            $parametros_adicionais = json_encode($parametros_adicionais);

                            if (!empty(trim($cobranca_estado_txt))) {
                                if (is_array($parametros_adicionais)) {
                                    $parametros_adicionais['cobranca_estado'] = trim($cobranca_estado_txt);
                                } else if (!empty($parametros_adicionais)) {
                                    $parametros_adicionais = json_decode($parametros_adicionais, true);
                                    $parametros_adicionais['cobranca_estado'] = trim($cobranca_estado_txt);
                                }
                                $parametros_adicionais = json_encode($parametros_adicionais);
                            }

                        }

                            $sql = "UPDATE tbl_posto_fabrica SET
                                        codigo_posto = $xcodigo_posto,";
                                        if($login_fabrica == 52){
                                            $sql .="tabela_mao_obra = $tabela_servico,";
                                        }

                                        if ($login_fabrica == 11 || $login_fabrica == 172) {
                                            $sql .= "permite_envio_produto = '$permite_envio_produto',";
                                        }
                            $sql .= "
                                        senha                   = $xsenha                  ,
                                        posto_empresa           = $xposto_empresa          ,
                                        tipo_posto              = $xtipo_posto             ,
                                        obs                     = $xobs                    ,
                                        tipo_atende             = $xtipo_atende            ,
                                        contato_nome            = $xcontato                ,
                                        contato_atendentes      = $xresponsavel_social     ,
                                        $sql_atendimento
                                        contato_endereco        = $xendereco               ,
                                        contato_numero          = $xnumero                 ,
                                        contato_complemento     = $xcomplemento            ,
                                        contato_bairro          = $xbairro                 ,
                                        contato_cidade          = $xcidade                 ,
                                        contato_cep             = $xcep                    ,
                                        contato_estado          = $xestado                 ,";
                                        if (!empty($cod_ibge_cidade)) {
                                            $sql .= "cod_ibge = $cod_ibge_cidade , ";
                                        }

                            if (in_array($login_fabrica, array(15,74,151))) {
                                if ($login_fabrica == 151) {
                                    $sql .= " contato_fone_comercial = $xcontato_fone_comercial, ";
                                }
                                $sql .= "contato_telefones = $xfone,";
                                $sql .= "contato_cel = '$contato_cel',";
                            } else {
                                $sql .= "
                                        contato_fone_comercial  = $xfone                   ,
                                        contato_fone_residencial = $xfone2                 ,";
                            }

                            if($login_fabrica == 1 and !empty($observacao_credenciamento)){
                                $sql .= " $campos_observacao_credenciamento ";
                            }

                            if ($login_fabrica == 171 AND !empty($codigo_fn)){
                                $sql .= "conta_contabil = '$codigo_fn' ,";
                            }

                            if ($login_fabrica == 30) {
                                $sql .= " contato_cel = $xfone3 ,
                                ";
                            }

                            if ($login_fabrica == 35) {
                                $sql .= " contato_cel = '$contato_cel' , ";
                            }

                            if (in_array($login_fabrica, array(3,20,30,74))) {
                                $sql .= " parametros_adicionais = '".
                                    json_encode($parametros_posto)."',
                                ";
                            }

                            if($login_fabrica == 42){   
                                if(empty($pedido_faturado)){
                                    $pedido_faturado = 'f';
                                }
                                if(empty($pedido_consumo_proprio)){
                                    $pedido_consumo_proprio = 'f';
                                }                                                         
                                $parametros_adicionais = json_encode(array(
                                    "pedido_venda" => $pedido_faturado,
                                    "pedido_consumo" => $pedido_consumo_proprio
                                    )
                                );                                
                                $sql .= " parametros_adicionais = '{$parametros_adicionais}', ";

                                $parametros_adicionais = null;

                            }

                            if($login_fabrica == 104){ //HD 2303024
                                $sql .= "parametros_adicionais = '$aux_posto_auditado',";
                            }

                            /*HD - 4276928*/
                            if ($login_fabrica == 91) {
                                if (empty($_POST["informar_cpf_cnpj"])) {
                                    $informar_cpf_cnpj = "true";
                                } else {
                                    $informar_cpf_cnpj = "false";
                                }

                                if (!empty($parametros_adicionais)) {
                                    $parametros_adicionais = json_decode($parametros_adicionais, true);
                                    $parametros_adicionais["informar_cpf_cnpj"] = $informar_cpf_cnpj;
                                    if (empty($parametros_adicionais['gera_pedido']) || !isset($_POST['gera_pedido'])) {
                                        unset($parametros_adicionais['gera_pedido']);
                                    }
                                } else {
                                    $parametros_adicionais = array(
                                        "informar_cpf_cnpj" => $informar_cpf_cnpj
                                    );
                                }
                                $parametros_adicionais = json_encode($parametros_adicionais);
                            }

                            //retirado a fabrica 74 desse if HD-1855233
                            if (!in_array($login_fabrica, array(3,20,30,74,104))) {
                                if(in_array($login_fabrica, array(1,6,15,35,86,91,120,201,201,141,144,156,158,169,170,177,183,189,190)) || !empty($parametros_adicionais)) {

                                    $sql .= " parametros_adicionais = '".$parametros_adicionais."' , ";
                                }
                            }

                            $sql .= "
                                        contato_fax             = $xfax                    ,
                                        nome_fantasia           = $xnome_fantasia          ,";

                            if ($login_fabrica==15) {
                                $sql .= "contato_email           = $xemail_latina          ,";
                            }else{
                                $sql .= "contato_email           = $xemail                 ,";
                            }

                            if (in_array($login_fabrica, array(169,170)) && !empty($campo_permite_envio_produto) && !empty($valor_permite_envio_produto)) {
                                $sql .= "{$campo_permite_envio_produto} = {$valor_permite_envio_produto},";
                            }

                            $sql .= "

                                        transportadora          = $xtransportadora         ,
                                        cobranca_endereco       = $xcobranca_endereco      ,
                                        cobranca_numero         = $xcobranca_numero        ,
                                        cobranca_complemento    = $xcobranca_complemento   ,
                                        cobranca_bairro         = $xcobranca_bairro        ,
                                        cobranca_cep            = $xcobranca_cep           ,
                                        cobranca_cidade         = $xcobranca_cidade        ,
                                        cobranca_estado         = $xcobranca_estado        ,
                                        desconto                = $xdesconto               ,
                                        $upd_valor_km
                                        desconto_acessorio      = $xdesconto_acessorio     ,
                                        custo_administrativo    = $xcusto_administrativo   ,
                                        imposto_al              = $ximposto_al             ,
                                        pedido_em_garantia      = $xpedido_em_garantia     ,
                                        coleta_peca             = $xcoleta_peca            ,
                                        reembolso_peca_estoque  = $xreembolso_peca_estoque ,
                                        pedido_faturado         = $xpedido_faturado        ,
                                        digita_os               = $xdigita_os              ,";


                                        //if($login_fabrica != 35){
                                            $sql .= " controla_estoque  = $xcontrola_estoque       , ";
                                        //}



                            if ($login_fabrica == 15){
                                $sql .= " controle_estoque_novo  = $xcontrole_estoque_novo,
                                        controle_estoque_manual  = $xcontrole_estoque_manual,
                                        categoria               = '$posto_vip',";
                            }
                            if (in_array($login_fabrica,array(1,104,115))) {
                                $sql .= " categoria               = '$categoria_posto',";
                            }
                                $sql .= "
                                        prestacao_servico       = $xprestacao_servico      ,
                                        prestacao_servico_sem_mo=$xprestacao_servico_sem_mo,
                                        pedido_bonificacao      = $xpedido_bonificacao     ,
                                        admin_sap               = $admin_sap               ,";

                                //HD 672836 Gabriel
                                $sql .= ($login_fabrica == 1 or $login_fabrica == 3) ? "admin_sap_especifico  = $admin_sap_especifico ," : " " ;

                                $sql .= "
                                        banco                   = $xbanco                  ,
                                        agencia                 = $xagencia                ,
                                        acrescimo_tributario    = $xacrescimo_tributario   ,
                                        conta                   = $xconta                  ,";
        if($login_fabrica==11 or $login_fabrica == 172){ $sql .= "atendimento            = '$atendimento_lenoxx'    , ";}//HD 110541
                                $sql .= "nomebanco              = $xnomebanco              ,
                                        favorecido_conta        = $xfavorecido_conta       ,
                                        escritorio_regional     = $xescritorio_regional    ,";
        //HD 8190 5/12/2007 Gustavo
        if($login_fabrica==45){ $sql .= "conta_operacao         = $xconta_operacao         , ";}
                                $sql .= "cpf_conta              = $xcpf_conta              ,
                                        tipo_conta              = $xtipo_conta             ,
                                        obs_conta               = $xobs_conta              , ";
        if($login_fabrica==19){ $sql .= " atende_comgas         = $atende_comgas           , ";}
                                $sql .= " pedido_via_distribuidor = $xpedido_via_distribuidor,
                                        item_aparencia          = '$item_aparencia'        ,
                                        data_alteracao          = CURRENT_TIMESTAMP        ,
                                        admin                   = $login_admin             ,
                                        garantia_antecipada     = $xgarantia_antecipada    ,
                                        atende_consumidor       = $xatende_consumidor      ,
                                        imprime_os              = '$imprime_os'            ,
                                        divulgar_consumidor     = '$divulgar_consumidor'   ,
                                        qtde_os_item            = '$qtde_os_item'           ,
                                        contribuinte_icms       = '$contribuinte_icms'     ";
                                        // hd 21496 - Francisco - campo Data da Nomeação para Dynacom
                                        if($login_fabrica==2) {
                                            $sql .= ",data_nomeacao         = '$data_nomeacao'";
                                        }
                                        //HD 356653
                                        if($login_fabrica == 30) {
                                            $sql .= ", centro_custo        = '$centro_custo',
                                                conta_contabil         = '$conta_contabil',
                                                local_entrega          = '$local_entrega'";
                                        }
                                        // Implantação Imbera - HD 2586842
                                        if (in_array($login_fabrica, array(158))) {
                                            $sql .= ", latitude = $latitude
                                                    , longitude = $longitude
                                                    , centro_custo = '$centro_custo'
                                                    , conta_contabil = '$conta_contabil'";
                                        }
                                        // Implantação Midea/Carrier - HD 3590690
                                        if (in_array($login_fabrica, array(169,170,178))) {
                                            $sql .= ", centro_custo = '$centro_custo'
                                                    , conta_contabil = '$conta_contabil'";
                                        }
                                        // HD 401553
                                        if($login_fabrica==42) {
                                            $sql .= ", filial               = '$posto_filial',
                                                entrega_tecnica      = '$entrega_tecnica'";
                                        }

                                $sql .="WHERE tbl_posto_fabrica.posto   = $posto
                                        AND   tbl_posto_fabrica.fabrica = $login_fabrica ";

                                if(in_array($login_fabrica,array(24,50))){
                                    if(isset($_POST['posto_isento'])){
                                        if($_POST['posto_isento'] == 'f'){

                                            $posto_isento = 'f';
                                        }else{

                                            $posto_isento = 't';
                                        }
                                    }
                                    if(isset($_POST['devolver_pecas'])){
                                        if($_POST['devolver_pecas'] == 'f'){

                                            $devolver_pecas = 'f';
                                        }else{

                                            $devolver_pecas = 't';
                                        }
                                    }

                                    $sqlParametrosAdicionais = "SELECT parametros_adicionais FROM tbl_posto_fabrica where posto = {$posto} and fabrica = {$login_fabrica}";
                                    $resParametrosAdicionais = pg_query($con,$sqlParametrosAdicionais);
                                    $posto_parametros_adicionais = pg_fetch_result($resParametrosAdicionais, 0,"parametros_adicionais");

                                    if(strlen($posto_parametros_adicionais) > 0 ){

                                        $posto_parametros_adicionais = json_decode($posto_parametros_adicionais, true);
                                        $posto_parametros_adicionais['posto_isento'] = $posto_isento;
                                        $posto_parametros_adicionais['devolver_pecas'] = $devolver_pecas;
                                        $posto_parametros_adicionais = json_encode($posto_parametros_adicionais);
                                    }else{
                                        $posto_parametros_adicionais = array(
                                            'posto_isento' => $posto_isento,
                                            'devolver_pecas' => $devolver_pecas
                                        );

                                        $posto_parametros_adicionais = json_encode($posto_parametros_adicionais);
                                    }
                                    $updateParametrosAdicionais = "UPDATE tbl_posto_fabrica
                                                                set parametros_adicionais ='". $posto_parametros_adicionais . "'
                                                                where posto = $posto and fabrica =$login_fabrica";
                                    pg_query($con, $updateParametrosAdicionais);
                                }

                                if($login_fabrica == 151){
                                    $sqlParametrosAdicionais = "SELECT parametros_adicionais FROM tbl_posto_fabrica where posto = {$posto} and fabrica = {$login_fabrica}";
                                    $resParametrosAdicionais = pg_query($con,$sqlParametrosAdicionais);
                                    $parametros_adicionais = pg_fetch_result($resParametrosAdicionais, 0,"parametros_adicionais");

                                    $valor_mao_obra = str_replace(",",".",$_POST["valor_mao_obra"]);

                                    if(strlen($parametros_adicionais) > 0) {
                                        $parametros_adicionais                         = json_decode($parametros_adicionais, true);
                                        $parametros_adicionais['qtde_os_item']         = $_POST['qtde_os_item'];
                                        $parametros_adicionais['valor_extrato']        = $_POST['valor_extrato'];
                                        $parametros_adicionais['valor_mao_obra']       = $valor_mao_obra;
                                        $parametros_adicionais['digito_agencia']       = $_POST['digito_agencia'];
                                        $parametros_adicionais['digito_conta']         = $_POST['digito_conta'];
                                        $parametros_adicionais['extrato_mais_3_meses'] = $extrato_mais_3_meses;
                                        $parametros_adicionais = json_encode($parametros_adicionais);
                                    }else{
                                        $parametros_adicionais = json_encode(array(
                                            "qtde_os_item"         => $_POST['qtde_os_item'],
                                            "valor_extrato"        => $_POST['valor_extrato'],
                                            "valor_mao_obra"       => $valor_mao_obra,
                                            "extrato_mais_3_meses" => $extrato_mais_3_meses,
                                            "digito_agencia"       => $_POST['digito_agencia'],
                                            "digito_conta"         => $_POST['digito_conta']
                                        ));
                                    }

                                    $sqlParametrosAdicionais = "";
                                    $sqlParametrosAdicionais = "UPDATE tbl_posto_fabrica SET parametros_adicionais = '".$parametros_adicionais."' WHERE posto = {$posto} and fabrica = {$login_fabrica}";
                                    pg_query($con,$sqlParametrosAdicionais);

                                    $sqlFamilia = "DELETE FROM tbl_excecao_mobra WHERE fabrica = {$login_fabrica} AND posto = {$posto} AND familia IS NOT NULL ";
                                    pg_query($con, $sqlFamilia);

                                    if(strlen($valor_mao_obra) > 0) {
                                        $sqlFamilia = "SELECT familia FROM tbl_familia WHERE fabrica = {$login_fabrica} AND mao_de_obra_familia < {$valor_mao_obra}";
                                        $resFamilia = pg_query($con,$sqlFamilia);

                                        if(pg_num_rows($resFamilia) > 0){

                                            while($objeto_familia = pg_fetch_object($resFamilia)){
                                                $sqlFamilia = "INSERT INTO tbl_excecao_mobra (fabrica, posto, familia, mao_de_obra) VALUES
                                                    ($login_fabrica,$posto,".$objeto_familia->familia.",$valor_mao_obra)";

                                                pg_query($con,$sqlFamilia);
                                            }
                                        }
                                    }
                                }


                                if (in_array($login_fabrica, array(184,200))) {

                                    $sqlPA = "SELECT parametros_adicionais FROM tbl_posto_fabrica where posto = {$posto} and fabrica = {$login_fabrica}";
                                    $resPA = pg_query($con,$sqlPA);
                                    $parametros_adicionais = json_decode(pg_fetch_result($resPA, 0,"parametros_adicionais"),1);
                                    $parametros_adicionais["garantia_com_deslocamento"] = $_POST["garantia_com_deslocamento"];

                                    $upPA = "UPDATE tbl_posto_fabrica
                                                SET parametros_adicionais ='". json_encode($parametros_adicionais) . "'
                                              WHERE posto = $posto 
                                                AND fabrica =$login_fabrica";
                                    pg_query($con, $upPA);

                                }

                        } else {
                            //INSERT POSTO FABRICA
                            $insert = 't';
                            if ($login_fabrica == 20) {
                                $novo_posto = $posto;
                                if(isset($_POST['tela_os_nova']))  $atendimento_lenoxx = 'n';
                            }

                            if ($login_fabrica == 153) {
                                $extrato_programado = date("Y")."-".date("m")."-01";
                            }

                            if ($login_fabrica == 115) {
                                if (isset($categoria_manual) && !empty($categoria_manual)) {
                                    $parametros_adicionais = json_encode([
                                        "ultima_categoria" => $categoria_posto, 
                                        "tempo" => 0,
                                        "categoria_manual" => "$categoria_manual"
                                    ]);                                    
                                } else {
                                    $parametros_adicionais = json_encode([
                                        "ultima_categoria" => $categoria_posto, 
                                        "tempo" => 0
                                    ]);                                    
                                }
                            }
                            if ($login_fabrica == 189){
                                $parametros_adicionais = json_decode($parametros_adicionais, true);
                                $parametros_adicionais['tipo_cliente'] = $_POST["tipo_cliente"];
                                $parametros_adicionais['codigo_representante'] = $_POST["codigo_representante"];
                            }
                            if (in_array($login_fabrica, array(184,200))) {
                                $parametros_adicionais = json_decode($parametros_adicionais,1);
                                $parametros_adicionais["garantia_com_deslocamento"] = $_POST["garantia_com_deslocamento"];
                                $parametros_adicionais = json_encode($parametros_adicionais);
                            }

                            if($login_fabrica == 151 && !empty($tipo_frete)){
                                $parametros_adicionais = json_decode($parametros_adicionais,true);
                                $parametros_adicionais["frete"] = $tipo_frete;
                                $parametros_adicionais = json_encode($parametros_adicionais);
                            }
                            
                            if ($login_fabrica == 190){
                                $parametros_adicionais = json_decode($parametros_adicionais, true);
                                if (!empty($_POST['qtde_atendimento'])) {
                                    $parametros_adicionais['qtde_atendimento'] = $_POST['qtde_atendimento'];
                                } 
                                $parametros_adicionais = json_encode($parametros_adicionais);
                            }

                            // ! Inserir POSTO FABRICA
                            //HD-808142

                            $sql = "INSERT INTO tbl_posto_fabrica (
                                        posto                  ,";
                                    if($login_fabrica == 52){
                                        $sql .= "tabela_mao_obra        ,";
                                    }

                                    if (in_array($login_fabrica, array(11,172))) {
                                        $sql .= "permite_envio_produto ,";
                                    }
                                    if($login_fabrica == 1){
                                        $sql .= " divulgar_consumidor ,";
                                    }
                                    $sql .= "
                                        fabrica                ,
                                        codigo_posto           ,
                                        senha                  ,
                                        desconto               ,
                                        valor_km               ,
                                        desconto_acessorio     ,
                                        custo_administrativo   ,
                                        imposto_al             ,
                                        posto_empresa          ,
                                        tipo_posto             ,
                                        obs                    ,
                                        tipo_atende           ,
                                        contato_nome           ,
                                        contato_atendentes     ,
                                        contato_endereco       ,
                                        contato_numero         ,
                                        contato_complemento    ,
                                        contato_bairro         ,
                                        contato_cidade         ,
                                        contato_cep            ,
                                        contato_estado         ,";
                                    if (!empty($cod_ibge_cidade)) {
                                        $sql .= "cod_ibge , ";
                                    }
                            if (!in_array($login_fabrica, array(15,74,151))) {
                                $sql .= "
                                        contato_fone_comercial ,
                                        contato_fone_residencial , ";
                            }else{
                                if ($login_fabrica == 151) {
                                    $sql .= ' contato_fone_comercial, ';
                                }
                                $sql .= "contato_telefones,";
                                $sql .= "contato_cel,";
                            }

                            if (in_array($login_fabrica, array(169,170)) && !empty($campo_permite_envio_produto) && !empty($valor_permite_envio_produto)) {
                                $sql .= "{$campo_permite_envio_produto},";
                            }

                            if (in_array($login_fabrica, array(30, 35))) {
                                $sql .= " contato_cel ,
                                ";
                            }

                            if (in_array($login_fabrica, array(1,167,203))) {
                                $sql .= " credenciamento ,";
                            }

                            if(in_array($login_fabrica, array(1,6,15,20,30,35,42,74,86,91,115,120,201,141,144,151,156,158,169,170,177,184,189,200)) || !empty($parametros_adicionais)){
                                $sql .=" parametros_adicionais, ";
                            }

                            $sql .= "
                                        contato_fax            ,
                                        nome_fantasia          ,
                                        contato_email          ,
                                        transportadora         ,
                                        cobranca_endereco      ,
                                        cobranca_numero        ,
                                        cobranca_complemento   ,
                                        cobranca_bairro        ,
                                        cobranca_cep           ,
                                        cobranca_cidade        ,
                                        cobranca_estado        ,
                                        pedido_em_garantia     ,
                                        reembolso_peca_estoque ,
                                        coleta_peca            ,
                                        pedido_faturado        ,
                                        digita_os              ,
                                        controla_estoque       ,
                                        prestacao_servico      ,
                                        prestacao_servico_sem_mo,
                                        pedido_bonificacao     ,
                                        admin_sap               ,";

                                //HD 755863
                                $sql .= ($login_fabrica == 15) ? "controle_estoque_novo, controle_estoque_manual, categoria, " : " ";

                                //HD 672836 Gabriel
                                $sql .= ($login_fabrica == 1) ? "admin_sap_especifico, categoria, " : " " ;
                                $sql .= (in_array($login_fabrica,[104,115])) ? "categoria, " : " " ;
                                $sql .= ($login_fabrica == 3) ? "admin_sap_especifico, " : " " ;

                                $sql .= "
                                        banco                  ,
                                        agencia                ,
                                        conta                  ,
                                        nomebanco              ,
                                        favorecido_conta       ,
                                        atende_consumidor      ,
                                        cpf_conta              ,
                                        tipo_conta             ,
                                        acrescimo_tributario   ,
                                        obs_conta              ,
                                        pedido_via_distribuidor,
                                        item_aparencia         ,
                                        data_alteracao         ,
                                        admin                  ,
                                        garantia_antecipada    ,
                                        escritorio_regional    ,
                                        imprime_os             ,
                                        qtde_os_item           ,
                                        contribuinte_icms      ";
        if ($login_fabrica==153) { $sql .= ",extrato_programado "; } // hd-2820608
        if ($login_fabrica==2) { $sql .= ",data_nomeacao        "; } // hd 21496
        if (in_array($login_fabrica, array(11,20,172))) { $sql .= ",atendimento          "; } // HD 110541
        if ($login_fabrica==19){ $sql .= ",atende_comgas        ";}
        if ($login_fabrica==45){ $sql .= ",conta_operacao       ";} //HD 8190
        if ($login_fabrica ==30) { $sql .=",centro_custo,
                                        conta_contabil,
                                        local_entrega"; } //HD 356653
                                        if (in_array($login_fabrica, array(158))) {
                                            $sql .= ",latitude, longitude, centro_custo, conta_contabil";
                                        }
                                        if (in_array($login_fabrica, array(169,170,178))) {
                                            $sql .= ",centro_custo, conta_contabil";
                                        }

                                        if ($login_fabrica == 171 AND !empty($codigo_fn)){
                                            $sql .= ", conta_contabil";
                                        }
        // HD 401553 -  Gabriel
        if($login_fabrica==42) { $sql .= ", filial, entrega_tecnica"; }
	if (in_array($login_fabrica, array(177,186))) { $sql .= ", credenciamento"; }
                                $sql .="
                                    ) VALUES (
                                        $posto                   ,";
                                        if($login_fabrica == 52){
                                            $sql.=" $tabela_servico          ,";
                                        }
                                        if (in_array($login_fabrica, array(11,172))) {
                                            $sql .= "'$permite_envio_produto' ,";
                                        }
                                        if($login_fabrica == 1){
                                            $sql .= " '$divulgar_consumidor' ,";
                                        }
                                        $sql .= "
                                        $login_fabrica           ,
                                        $xcodigo                 ,
                                        $xsenha                  ,
                                        $xdesconto               ,
                                        $xvalor_km               ,
                                        $xdesconto_acessorio     ,
                                        $xcusto_administrativo   ,
                                        $ximposto_al             ,
                                        $xposto_empresa          ,
                                        $xtipo_posto             ,
                                        $xobs                    ,
                                        $xtipo_atende             ,
                                        $xcontato                ,
                                        $xresponsavel_social     ,
                                        $xendereco               ,
                                        $xnumero                 ,
                                        $xcomplemento            ,
                                        $xbairro                 ,
                                        $xcidade                 ,
                                        $xcep                    ,
                                        $xestado                 ,";

                                    if (!empty($cod_ibge_cidade)) {
                                        $sql .= " $cod_ibge_cidade , ";
                                    }

                                if ($login_fabrica != 15 and $login_fabrica != 74 and $login_fabrica != 151) {
                                    $sql .= "
                                        $xfone                   ,
                                        $xfone2                  , ";
                                }else{
                                    if ($login_fabrica == 151) {
                                        $sql .= " $xcontato_fone_comercial, ";
                                    }
                                    $sql .= "$xfone,";
                                    $sql .= "'$contato_cel',";
                                }

                                if ($login_fabrica == 30) {
                                    $sql .= " $xfone3       ,
                                    ";
                                }

                                if ($login_fabrica == 35) {
                                    $sql .= " '$contato_cel' , ";
                                }

                                if(in_array($login_fabrica, array(1))){
                                    $sql .= " 'Pre Cadastro em apr' ,";
                                }

                                if(in_array($login_fabrica, array(167,203))){
                                    $sql .= " 'Em Credenciamento' ,";
                                }

                                if (isFabrica(3,20,30) && !empty($parametros_posto)) {
                                    $sql .= "'".json_encode($parametros_posto)."'    ,
                                    ";
                                }

                                if ($login_fabrica == 74) {
                                    $sql .= "'$aux_fixo_km',
                                    ";
                                }

                                if($login_fabrica == 104){ //HD 2303024
                                    $sql .="'$aux_posto_auditado',";
                                }

                                if (in_array($login_fabrica, array(169,170)) && !empty($campo_permite_envio_produto) && !empty($valor_permite_envio_produto)) {
                                    $sql .= "{$valor_permite_envio_produto},";
                                }


                                //retirado a fabrica 74 desse if HD-1855233
                                if(in_array($login_fabrica, array(1,6,15,35,42,115,120,201,141,144,86,151,156,158,169,170,177,184,189,200)) || (!in_array($login_fabrica, [3,20,30]) && !empty($parametros_adicionais)) || (in_array($login_fabrica, [3,20,30]) && !empty($parametros_adicionais) && empty($parametros_posto))) {
                                    $sql .= " '$parametros_adicionais', ";
                                }

                                /*HD - 4276928*/
                                if ($login_fabrica == 91) {
                                    if (empty($_POST["informar_cpf_cnpj"])) {
                                        $informar_cpf_cnpj = "true";
                                    } else {
                                        $informar_cpf_cnpj = "false";
                                    }

                                        if (!empty($parametros_adicionais)) {
                                            $parametros_adicionais = json_decode($parametros_adicionais, true);
                                            $parametros_adicionais["obrigado_informar_cpf_cnpj"] = $informar_cpf_cnpj;
                                            $parametros_adicionais = json_encode($parametros_adicionais);
                                        } else {
                                            $parametros_adicionais = array(
                                                "obrigado_informar_cpf_cnpj" => $informar_cpf_cnpj
                                            );
                                            $parametros_adicionais = json_decode($parametros_adicionais);
                                        }

                                        $sql .= " '$parametros_adicionais', ";
                                }

                                $sql .= "
                                        $xfax                    ,
                                        $xnome_fantasia          ,";

                                if ($login_fabrica == 15){
                                    $sql .= "$xemail_latina      ,";
                                }else{

                                    $sql .= "$xemail             ,";
                                }

                                $sql .= "
                                        $xtransportadora         ,
                                        $xcobranca_endereco      ,
                                        $xcobranca_numero        ,
                                        $xcobranca_complemento   ,
                                        $xcobranca_bairro        ,
                                        $xcobranca_cep           ,
                                        $xcobranca_cidade        ,
                                        $xcobranca_estado        ,
                                        $xpedido_em_garantia     ,
                                        $xreembolso_peca_estoque ,
                                        $xcoleta_peca            ,
                                        $xpedido_faturado        ,
                                        $xdigita_os              ,
                                        $xcontrola_estoque       ,
                                        $xprestacao_servico      ,
                                        $xprestacao_servico_sem_mo,
                                        $xpedido_bonificacao     ,
                                        $admin_sap               ,";

                                //HD 755863
                                $sql .= ($login_fabrica == 15) ? "$xcontrole_estoque_novo, $xcontrole_estoque_manual, '$posto_vip',": " ";

                                //HD 672836 Gabriel
                                $sql .= ($login_fabrica == 1) ? "$admin_sap_especifico, '$categoria_posto', " : " " ;
                                $sql .= (in_array($login_fabrica,[104,115])) ? "'$categoria_posto', " : " " ;
                                $sql .= ($login_fabrica == 3) ? "$admin_sap_especifico, " : " " ;

                                $sql .= "
                                        $xbanco                  ,
                                        $xagencia                ,
                                        $xconta                  ,
                                        $xnomebanco              ,
                                        $xfavorecido_conta       ,
                                        $xatende_consumidor      ,
                                        $xcpf_conta              ,
                                        $xtipo_conta             ,
                                        $xacrescimo_tributario   ,
                                        $xobs_conta              ,
                                        $xpedido_via_distribuidor,
                                        '$item_aparencia'        ,
                                        current_timestamp        ,
                                        $login_admin             ,
                                        $xgarantia_antecipada    ,
                                        $xescritorio_regional    ,
                                        '$imprime_os'            ,
                                        '$qtde_os_item'          ,
                                        '$contribuinte_icms'     ";
        if($login_fabrica==153) { $sql .=",'$extrato_programado' "; } // hd-2820608
        if($login_fabrica==2) { $sql .=",'$data_nomeacao'        "; } // hd 21496
        if($login_fabrica==11 || $login_fabrica == 20 or $login_fabrica == 172){ $sql .=",'$atendimento_lenoxx'   "; } // HD 110541
        if($login_fabrica==19){ $sql .=",$atende_comgas          "; }
        if($login_fabrica==45){ $sql .=",$xconta_operacao        "; } //HD 8190
        if($login_fabrica==92 || $login_fabrica ==30){ $sql .=", '$centro_custo',
                                        '$conta_contabil',
                                        '$local_entrega'"; } //HD 356653
                                        if (in_array($login_fabrica, array(158))) {
                                            $sql .= ", $latitude, $longitude, '$centro_custo', '$conta_contabil'";
                                        }
                                        if (in_array($login_fabrica, array(169,170,178))) {
                                            $sql .= ", '$centro_custo', '$conta_contabil'";
                                        }

                                        if ($login_fabrica == 171 AND !empty($codigo_fn)){
                                            $sql .= ", '$codigo_fn'";
                                        }
        //HD 401553
        if($login_fabrica==42) { $sql .= ", '$posto_filial', '$entrega_tecnica' ";}
    if($login_fabrica==186) { $sql .= ", 'EM CREDENCIAMENTO' ";}
	if($login_fabrica==177) { $sql .= ", 'EM APROVAÇÃO' ";}
                                $sql .="
                                    )";
                        }
                    }
            $action = strtolower(substr($sql, 0, strpos($sql, ' ')));
            $res = pg_query ($con,$sql);
            if (pg_last_error($con)) {
                $msg_erro .= traduz("Erro ao gravar dados do posto");
            }

            if ($login_fabrica == 158) {

                if (empty($posto)) {
                    
                    $posto = $_REQUEST["posto"];
                }
                
                $sql = "SELECT posto, parametros_adicionais 
                        FROM tbl_posto_fabrica 
                        WHERE posto = $posto";
                
                $res = pg_query($con,$sql);
            
                $json_parametros = pg_fetch_result($res, 0, parametros_adicionais);

                $parametros = json_decode($json_parametros);

                $parametros->zera_km = $_REQUEST["zera_km"];

                $json_parametros = json_encode($parametros);
                
                $sql = "UPDATE tbl_posto_fabrica 
                        SET parametros_adicionais = '$json_parametros'
                        WHERE posto = $posto";

                $res = pg_query($con, $sql);

                if (pg_last_error($con)) {

                    $msg_erro .= "Erro ao gravar dados do posto 'zera_km'";
                }
            }

            if ($login_fabrica == 1 && ($insert == 't')) {
                $texto = utf8_encode("Enviado para aprovação");
                $sql_credenciamento = "INSERT INTO tbl_credenciamento (fabrica, status, confirmacao_admin, posto, texto) values ($login_fabrica, 'Pre Cadastro em apr', $login_admin, $posto, '$texto')";
                $res_credenciamento = pg_query($con, $sql_credenciamento);

                $posto_pre = true;
            }

            // Depois de inserir ou atualizar na tbl_posto_fabrica, se tiver vários tipos,
            // gravar a informação na tbl_posto_tipo_posto
                if(empty($msg_erro)) {
                    if (!empty($telecontrol_distrib) and $telecontrol_distrib == 't') {
                         $sql_fab_distrib = "SELECT fabrica FROM tbl_fabrica
                            WHERE JSON_FIELD('telecontrol_distrib', parametros_adicionais) = 't'
                            AND fabrica <> $login_fabrica
                            AND ativo_fabrica";
                         $res_fab_distrib = pg_query($con, $sql_fab_distrib);

                         $update_cod_ibge = '';

                         if (!empty($cod_ibge_cidade)) {
                            $update_cod_ibge .= "cod_ibge = $cod_ibge_cidade , ";
                         }

                         $update_contatos = 'UPDATE tbl_posto_fabrica SET
                                contato_endereco         = ' . $xendereco    . ',
                                contato_numero           = ' . $xnumero      . ',
                                contato_complemento      = ' . $xcomplemento . ',
                                contato_bairro           = ' . $xbairro      . ',
                                contato_cidade           = ' . $xcidade      . ',
                                ' . $update_cod_ibge . '
                                contato_cep              = ' . $xcep         . ',
                                contato_estado           = ' . $xestado      . ',
                                contato_fone_comercial   = ' . $xfone        . ',
                                contato_fone_residencial = ' . $xfone2       . ',
                                contato_email            = ' . $xemail . '
                            WHERE posto = ' . $posto . '
                            AND fabrica = $1';
                         $prep = pg_prepare($con, "update_contatos", $update_contatos);

                         while ($fetch = pg_fetch_assoc($res_fab_distrib)) {
                             $exec = pg_execute($con, "update_contatos", [$fetch["fabrica"]]);
                         }
                     }

                    if ($tipo_posto_multiplo and count($atipo_posto) > 1) {
                        /**
                         * Confere se já existem registros e se são os mesmos
                         * ARRAY_AGG() devolve os valores num pg_array ( '{val1,val2,...}' )
                         * e o operador && confere se dois arrays têm valores comuns. Sendo que
                         * deve ter count($atipo_tipo) valores, só será true se tiver exatamente
                         * os mesmos valores, não importa a ordem (ARRAY[] = ARRAY[] só é true
                         * se os valores estiverem na mesma ordem, por isso não é possível
                         * usar ARRAY_AGG(tipo_posto) = '{$array_tipos}'
                         **/
                        $count_tipos = count($atipo_posto);
                        $array_tipos = implode(',', $atipo_posto);
                        $sqlT = "SELECT ARRAY_AGG(DISTINCT tipo_posto) AS tipos
                                   FROM tbl_posto_tipo_posto
                                  WHERE fabrica = $login_fabrica
                                    AND posto   = $posto
                                 HAVING COUNT(*) = $count_tipos
                                    AND ARRAY_AGG(tipo_posto) && ARRAY[$array_tipos]";

                        $resT = pg_query($con, $sqlT);

                        if (!pg_num_rows($resT)) { // Se não tem os mesmos valores, exclui tudo
                            // exclui os registros desse posto/fábrica
                            $sqlDelT = "DELETE FROM tbl_posto_tipo_posto WHERE fabrica = $login_fabrica AND posto = $posto";
                            $resDelT = pg_query($con, $sqlDelT);

                            // tira o TRUE despois de fazer os TESTES!!!!
                            if (!pg_last_error($con)) {
                                $sqlInsT = "INSERT INTO tbl_posto_tipo_posto (fabrica, posto, tipo_posto)\nVALUES\n";

                                sort($atipo_posto);

                                foreach($atipo_posto as $tp)
                                    $sqlInsT .= "($login_fabrica, $posto, $tp),";

                                $sqlInsT = substr($sqlInsT, 0, -1);
                                $resInsT = pg_query($con, $sqlInsT);

                                if (pg_affected_rows($resInsT) != $count_tipos)
                                    $msg_erro = traduz('Erro ao cadastrar os tipos para o posto');
                            } else {
                                $msg_erro .= pg_last_error($con);
                            }
                        } // não precisa alterar, já são esses tipos, mesmo...

                    }else if($tipo_posto_multiplo == true && count($tipo_posto) == 1){

                        $tipo_posto_unico = $tipo_posto[0];

                        $sql = "DELETE FROM tbl_posto_tipo_posto WHERE fabrica = {$login_fabrica} AND posto = {$posto}";
                        $res = pg_query($con, $sql);

                        $sql = "INSERT INTO tbl_posto_tipo_posto (fabrica, posto, tipo_posto) VALUES ({$login_fabrica}, {$posto}, {$tipo_posto_unico})";
                        $res = pg_query($con, $sql);

                    }

                    if ($login_fabrica == 1 and $insert == 't') {

                        /**
                         * hd-3596988 - Todo posto novo da Black&Decker
                         * terá como padrão a gravação de intervalo
                         * de extrato mensal, de acorco com o estado de cadastro,
                         * para futuro encaixe na nova regra de intervalos de extrato
                         */

                        $sqlIntervalo = "
                            SELECT  intervalo_extrato
                            FROM    tbl_intervalo_extrato
                            WHERE   fabrica = $login_fabrica
                            AND     estado = $xestado
                            AND     descricao = 'Mensal'
                        ";
                        $resIntervalo = pg_query($con,$sqlIntervalo);
                        $intervalo_extrato = pg_fetch_result($resIntervalo,0,intervalo_extrato);

                        if(pg_num_rows($resIntervalo) > 0) {
                            $sql = "INSERT INTO tbl_tipo_gera_extrato (
                                descricao,
                                fabrica,
                                posto,
                                intervalo_extrato,
                                admin
                            ) VALUES (
                                'Opcao Extrato',
                                $login_fabrica,
                                $posto,
                                $intervalo_extrato,
                                $login_admin
                            )
                            ";
                            $res = pg_query($con,$sql);
                        }
                    }

                    $AuditorLog->RetornaDadosTabela()->EnviarLog($action, 'tbl_posto_fabrica',"$login_fabrica*$posto");
                    if (!$AuditorLog->OK) {
                        $msg_erro .= traduz('Erro ao tentar gravar o log de registro!');
                    }

                }

                if ($login_fabrica == 1) {
                    unset($log_acao);
                    $AuditorLog2 = new AuditorLog;
                    $AuditorLog2->RetornaDadosTabela('tbl_excecao_mobra',array("posto" => $posto,"fabrica" => $login_fabrica));

                    $sql = "SELECT excecao_mobra FROM tbl_excecao_mobra WHERE posto = $posto AND fabrica = $login_fabrica AND tx_administrativa NOTNULL";
                    $res = pg_query($con,$sql);

                    if(pg_num_rows($res) > 0){
                        $excecao_mobra = pg_fetch_result($res, 0, 'excecao_mobra');
                        $sql = "UPDATE tbl_excecao_mobra SET tx_administrativa = $xtaxa_administrativa WHERE excecao_mobra = $excecao_mobra AND posto = $posto AND fabrica = $login_fabrica";

                        $res = pg_query($con,$sql);
                        $log_acao = 'update';
                    }else{
                        $xtaxa_administrativa = (strlen(trim($xtaxa_administrativa)) == 0) ? 1.1 : $xtaxa_administrativa;
                        $sql = "INSERT INTO tbl_excecao_mobra(posto,fabrica,tx_administrativa) VALUES($posto,$login_fabrica,$xtaxa_administrativa)";
                        $res = pg_query($con,$sql);
                        $log_acao = 'insert';
                    }

                    if (isset($auditor_acao)) {
                        $AuditorLog2->retornaDadosTabela()->enviarLog($auditor_acao, 'tbl_excecao_mobra', "$login_fabrica*$posto");
                    }

                }
                $sql_posto_fabrica = "SELECT posto_fabrica FROM tbl_posto_fabrica WHERE posto = $posto AND fabrica = $login_fabrica";
                $res_posto_fabrica = pg_query($con, $sql_posto_fabrica);
                $posto_fabrica = pg_fetch_result($res_posto_fabrica, 'posto_fabrica');
                $data = new DateTime();
                $data_solicitacao = $data->format('Y-m-d H:i:s.u');
				if(!empty($posto_fabrica)) {
					$ip_solicitante = $_SERVER['HTTP_X_FORWARDED_FOR'] ? : $_SERVER['REMOTE_ADDR'];
					$ip_solicitante = explode(",",$ip_solicitante);
					$ip_solicitante = $ip_solicitante[0];
					$sql_tabela_alteracao_senha = "INSERT INTO tbl_alteracao_posto_senha (posto_fabrica, token, data_solicitacao,tipo_alteracao, ip) VALUES ($posto_fabrica, '', '$data_solicitacao', 'cadastro_posto', '$ip_solicitante')";
					pg_query($con, $sql_tabela_alteracao_senha);
					if (strlen (pg_errormessage ($con)) > 0) $msg_erro = pg_errormessage($con);
				}
                }
            }

            //HD 15526
            // grava posto_linha
            if (strlen($msg_erro) == 0){
                if ($login_fabrica >= 175 AND $login_fabrica != 183 OR ($login_fabrica == 152)){
                    $tabela_linha  = $_POST["tabela_linha"];
                }
                if (!in_array($login_fabrica,array(14, 117))) {
                    unset($AuditorLog);
                    $insert = 0;
                    $AuditorLog = new AuditorLog;
                    

                    $sqlAuditor = "SELECT DISTINCT
                                tbl_linha.linha AS codigo,
                                fn_retira_especiais(tbl_linha.nome) AS Linha,
                                fn_retira_especiais(tbl_tabela.descricao) AS tabela,
                                tbl_posto_linha.desconto,
                                tbl_posto_linha.distribuidor,
                                tbl_categoria_posto.nome as categoria_posto
                            FROM    tbl_linha JOIN tbl_posto_linha ON(tbl_posto_linha.linha = tbl_linha.linha AND tbl_posto_linha.posto = {$posto}) LEFT JOIN tbl_tabela ON(tbl_tabela.tabela = tbl_posto_linha.tabela)
                            LEFT JOIN tbl_categoria_posto ON tbl_categoria_posto.categoria_posto = tbl_posto_linha.categoria_posto
                            WHERE   tbl_linha.ativo IS TRUE
                            AND     tbl_linha.fabrica = {$login_fabrica};";
                    
                    $AuditorLog->RetornaDadosSelect($sqlAuditor);
                    
                    $sql = "SELECT  tbl_linha.linha
                            FROM    tbl_linha
                            WHERE   ativo IS TRUE
                            AND     fabrica = $login_fabrica";
                    $res = pg_query ($con,$sql);
       
                    for ($i = 0 ; $i < pg_num_rows ($res) ; $i++) {

                        $linha = pg_fetch_result ($res,$i,linha);

                        $atende             = $_POST ['atende_'             . $linha];
                        $tabela             = $_POST ['tabela_'             . $linha];
                       
                        if ($login_fabrica >= 175 OR $login_fabrica == 152){
                            if (in_array($linha, $_POST["linhas_produto"])){
                                $atende = $linha;
                                $tabela = $tabela_linha;
                            }
                        }
                        
                        $desconto           = $_POST ['desconto_'           . $linha];
                        if ($login_fabrica >= 175 AND $login_fabrica != 183 OR ($login_fabrica == 152)){
                            $tabela_posto       = $_POST ['tabela_posto'];
                        }else{
                            $tabela_posto       = $_POST ['tabela_posto_'       . $linha];
                        }

                        $tabela_bonificacao = $_POST ['tabela_bonificacao_' . $linha];
                        $distribuidor       = $_POST ['distribuidor_'       . $linha];

                        if (isset($tabelaPrecoUnica) && in_array($login_fabrica, array(156))) {
                            $tabela = $tabela_posto;
                        }
                        
                        if ( $login_fabrica == 50 ){
                            $auditar_os     = $_POST ['auditar_os_'         . $linha];
                        }
                        if ($login_fabrica == 24){ #HD 383050 - Adicionando campo novo - SUGGAR
                            $divulga_consumidor_linha = $_POST['divulga_consumidor_linha_'.$linha];
                        }

                        $sql = "SELECT tbl_tabela.tabela FROM tbl_tabela WHERE fabrica = $login_fabrica AND ativa IS TRUE";
                        $resX = pg_query($con,$sql);
                        if (pg_num_rows($resX) == 1) {
                            $tabela = pg_fetch_result ($resX,0,tabela);
                        }

                        if (strlen ($atende) == 0) {
                            $flag = 'delete';
                            $sql = "DELETE FROM tbl_posto_linha WHERE posto = $posto AND linha = $linha";                            
                            $resX = pg_query ($con,$sql);                            

                        }else{
                            if (strlen($tabela)             == 0 and !in_array($login_fabrica,array(1,104,163))) $msg_erro = traduz("Informe a tabela para esta linha");
                            if (strlen($desconto)           == 0) $desconto = "0";
                            if (strlen($distribuidor)       == 0) $distribuidor = "null";
                            if (strlen($tabela_posto)       == 0) $tabela_posto = "null";
                            if (strlen($tabela_bonificacao) == 0) $tabela_bonificacao = "null";
                            
                            $categoria_posto_linha = (empty($_POST['categoria_' . $linha])) ? "null" : "'".$_POST['categoria_' . $linha]."'";

                            if (strlen ($tabela) == 0){
                                    $tabela = 'null';
                                }

                            if ($login_fabrica == 24){ #HD 383050 - Adicionando campo novo - SUGGAR
                                if ( empty($divulga_consumidor_linha) ) {
                                    $divulga_consumidor_linha = "f";
                                }else{
                                    $divulga_consumidor_linha = "t";
                                }
                            }
                            if ($login_fabrica == 50){
                                if ( empty($auditar_os) ) {
                                    $auditar_os = "f";
                                }else{
                                    $auditar_os = "t";
                                }
                            }

                            if (strlen ($msg_erro) == 0) {

                                $sql = "SELECT * FROM tbl_posto_linha WHERE posto = $posto AND linha = $linha";
                                $resX = pg_query ($con,$sql);
                                if (pg_num_rows ($resX) > 0) {                                    
                                    $flag = 'update';
                                    $sql = "UPDATE tbl_posto_linha SET
                                                tabela              = $tabela  ,
                                                desconto            = $desconto,
                                                tabela_posto        = $tabela_posto,
                                                tabela_bonificacao  = $tabela_bonificacao,
                                                distribuidor        = $distribuidor,
                                                categoria_posto     = $categoria_posto_linha";
                                    if ( $login_fabrica == 24 ){ #HD 383050 - Adicionando campo novo - SUGGAR
                                        $sql .= ",divulgar_consumidor = '$divulga_consumidor_linha'";
                                    }
                                    if ( $login_fabrica == 50 ){
                                        $sql .= ",auditar_os = '$auditar_os'";
                                    }

                                    $sql .= "
                                            WHERE tbl_posto_linha.posto = $posto
                                            AND   tbl_posto_linha.linha = $linha";
                                    $resX = pg_query ($con,$sql);
                                }else{                                     
                                    $flag = 'insert';
                                    
                                    $sql = "INSERT INTO tbl_posto_linha (
                                                posto   ,
                                                linha   ,
                                                tabela  ,
                                                desconto,
                                                distribuidor,
                                                tabela_posto,
                                                tabela_bonificacao,
                                                categoria_posto";
                                    if ( $login_fabrica == 24 ){ #HD 383050 - Adicionando campo novo - SUGGAR
                                        $sql .= "
                                                ,divulgar_consumidor
                                        ";
                                    }
                                    if ( $login_fabrica == 50 ){
                                        $sql .= "
                                                ,auditar_os
                                        ";
                                    }

                                    $sql .="
                                            ) VALUES (
                                                $posto   ,
                                                $linha   ,
                                                $tabela  ,
                                                $desconto,
                                                $distribuidor,
                                                $tabela_posto,
                                                $tabela_bonificacao,
                                                $categoria_posto_linha";
                                    if ( $login_fabrica == 24 ){ #HD 383050 - Adicionando campo novo - SUGGAR
                                        $sql .= "
                                                ,'$divulga_consumidor_linha'
                                        ";
                                    }
                                    if ( $login_fabrica == 50 ){ #HD 383050 - Adicionando campo novo - SUGGAR
                                        $sql .= "
                                                ,'$auditar_os'
                                        ";
                                    }

                                    $sql .="
                                            )";
                                    $resX = pg_query ($con,$sql);
                                   
                                }
                            }
                                      
                        }
                    }                   

                    if ($flag == 'insert') {                         
                        $AuditorLog->RetornaDadosSelect()->EnviarLog('INSERT', 'tbl_posto_linha',"$login_fabrica*$posto");
                    }else if ($flag == 'update'){  
                        $AuditorLog->RetornaDadosSelect()->EnviarLog('UPDATE', 'tbl_posto_linha',"$login_fabrica*$posto");
                    }
                    if($flag == 'delete'){
                        $AuditorLog->RetornaDadosSelect()->EnviarLog('DELETE', 'tbl_posto_linha',"$login_fabrica*$posto");
                    }

                    if (!$AuditorLog->OK) {
                        $msg_erro .= traduz("Erro ao tentar gravar o log de alteração!");
                    }
                    #exit;
                }elseif(in_array($login_fabrica, array(117))) {
                    $sqlMacroLinha = "SELECT distinct   tbl_macro_linha.descricao,
                                            tbl_macro_linha.macro_linha
                                    FROM tbl_macro_linha
                                        JOIN tbl_macro_linha_fabrica ON tbl_macro_linha.macro_linha = tbl_macro_linha_fabrica.macro_linha
                                    WHERE tbl_macro_linha.ativo IS TRUE
                                        AND fabrica = {$login_fabrica}
                                        AND ativo = 't'
                                    ORDER BY tbl_macro_linha.descricao";

                    $resMacroLinha = pg_query($con,$sqlMacroLinha);
                    for ($y=0; $y < pg_num_rows($resMacroLinha); $y++) {
                        $macro_linha = pg_fetch_result($resMacroLinha, $y, 'macro_linha');
                        $atende             = $_POST ['linhas_'             . $macro_linha];
                        $tabela             = $_POST ['tabela_'             . $macro_linha];

                        $sqlLinha = "SELECT tbl_linha.linha,
                                        tbl_linha.nome
                                    FROM tbl_macro_linha_fabrica
                                        JOIN tbl_linha USING(linha)
                                    WHERE tbl_linha.fabrica = {$login_fabrica}
                                        AND tbl_linha.ativo IS TRUE
                                        AND tbl_macro_linha_fabrica.macro_linha = {$macro_linha}";
                        $resLinha = pg_query($con,$sqlLinha);
                        for ($i = 0 ; $i < pg_num_rows ($resLinha) ; $i++) {
                            $linha = pg_fetch_result ($resLinha,$i,linha);

                            if (!in_array($linha, $atende)) {
                                $sql = "DELETE FROM tbl_posto_linha WHERE posto = $posto AND linha = $linha";
                                $resX = pg_query ($con,$sql);
                            }else{
                                if (strlen($tabela)             == 0) $msg_erro = "Informe a tabela para esta linha";
                                if (strlen($desconto)           == 0) $desconto = "0";
                                if (strlen($distribuidor)       == 0) $distribuidor = "null";
                                if (strlen($tabela_posto)       == 0) $tabela_posto = "null";
                                if (strlen($tabela_bonificacao) == 0) $tabela_bonificacao = "null";

                                if (strlen ($tabela) == 0){
                                    $tabela = 'null';
                                }

                                if (strlen ($msg_erro) == 0) {
                                 
                                    $sql = "SELECT * FROM tbl_posto_linha WHERE posto = $posto AND linha = $linha";
                                    $resX = pg_query ($con,$sql);
                                    if (pg_num_rows ($resX) > 0) {
                                       
                                        $sql = "UPDATE tbl_posto_linha SET
                                                    tabela              = $tabela  ,
                                                    desconto            = $desconto,
                                                    tabela_posto        = $tabela_posto,
                                                    tabela_bonificacao  = $tabela_bonificacao,
                                                    distribuidor        = $distribuidor
                                                WHERE tbl_posto_linha.posto = $posto
                                                AND   tbl_posto_linha.linha = $linha";

                                        $resX = pg_query ($con,$sql);
                                    }else{
                                                                            
                                        $sql = "INSERT INTO tbl_posto_linha (
                                                posto   ,
                                                linha   ,
                                                tabela  ,
                                                desconto,
                                                distribuidor,
                                                tabela_posto,
                                                tabela_bonificacao
                                            ) VALUES (
                                                $posto   ,
                                                $linha   ,
                                                $tabela  ,
                                                $desconto,
                                                $distribuidor,
                                                $tabela_posto,
                                                $tabela_bonificacao
                                            )";
                                        $resX = pg_query ($con,$sql);
                                    }
                                }
                            }
                        }
                    }

                    //exit;
                }else{
                    $sql = "SELECT * FROM tbl_familia WHERE fabrica = $login_fabrica ORDER BY tbl_familia.descricao;";
                    $res = pg_query ($con,$sql);

                    for ($i = 0 ; $i < pg_num_rows ($res) ; $i++) {
                        $familia = pg_fetch_result ($res,$i,familia);

                        $atende       = $_POST ['atende_'       . $familia];
                        $tabela       = $_POST ['tabela_'       . $familia];
                        $desconto     = $_POST ['desconto_'     . $familia];
                        $distribuidor = $_POST ['distribuidor_' . $familia];

                        if (strlen ($atende) == 0) {
                            $sql = "DELETE FROM tbl_posto_linha
                                    WHERE  tbl_posto_linha.posto   = $posto
                                    AND    tbl_posto_linha.familia = $familia";
                            $resX = pg_query ($con,$sql);
                        }else{
                            if (strlen ($tabela) == 0)       $msg_erro = traduz("Informa a tabela para esta familia");
                            if (strlen ($desconto) == 0)     $desconto = "0";
                            if (strlen ($distribuidor) == 0) $distribuidor = "null";

                            if (strlen ($msg_erro) == 0) {
                                if($login_fabrica == 20){
                                    $sql = "SELECT tbl_tabela.tabela FROM tbl_tabela
                                            WHERE  tbl_tabela.fabrica = $login_fabrica
                                            AND    tbl_tabela.tabela  = $tabela
                                            AND    tbl_tabela.ativa IS TRUE";
                                    $resX = pg_query($con,$sql);

                                    if (pg_num_rows($resX) == 1) {
                                        $tabela = pg_fetch_result ($resX,0,tabela);
                                    }

                                    $sql = "UPDATE tbl_posto_linha SET
                                                tabela       = $tabela
                                            WHERE posto   = $posto
                                            AND   familia = $familia";
                                    $resX = pg_query ($con,$sql);

                                }
                                $sql = "SELECT * FROM tbl_posto_linha WHERE posto = $posto AND familia = $familia";
                                $resX = pg_query ($con,$sql);

                                if (pg_num_rows ($resX) > 0) {
                                    $sql = "SELECT tbl_tabela.tabela FROM tbl_tabela
                                            WHERE  tbl_tabela.fabrica = $login_fabrica
                                            AND    tbl_tabela.tabela  = $tabela
                                            AND    tbl_tabela.ativa IS TRUE";
                                    $resX = pg_query($con,$sql);

                                    if (pg_num_rows($resX) == 1) {
                                        $tabela = pg_fetch_result ($resX,0,tabela);
                                    }

                                    $sql = "UPDATE tbl_posto_linha SET
                                                tabela       = $tabela  ,
                                                desconto     = $desconto,
                                                distribuidor = $distribuidor
                                            WHERE tbl_posto_linha.posto   = $posto
                                            AND   tbl_posto_linha.familia = $familia";
                                    $resX = pg_query ($con,$sql);

                                }else{
                                    $sql = "INSERT INTO tbl_posto_linha (
                                                posto   ,
                                                familia ,
                                                tabela  ,
                                                desconto,
                                                distribuidor
                                            ) VALUES (
                                                $posto   ,
                                                $familia ,
                                                $tabela  ,
                                                $desconto,
                                                $distribuidor
                                            )";
                                    $resX = pg_query ($con,$sql);
                                    //echo nl2br($sql);
                                }
                            }
                        }
                    }
                }
            }
        }

        if($login_fabrica == 20 AND strlen($msg_erro)==0){
            $tabela = $_POST["tabela_unica"];
            if(strlen($tabela) > 0){
                $sql = "SELECT tbl_tabela.tabela FROM tbl_tabela
                        WHERE  tbl_tabela.fabrica = $login_fabrica
                        AND    tbl_tabela.tabela  = $tabela
                        AND    tbl_tabela.ativa IS TRUE";
                $resX = pg_query($con,$sql);
                if (pg_num_rows($resX) == 1) $tabela = pg_fetch_result ($resX,0,tabela);

                $sql = "UPDATE tbl_posto_fabrica SET
                            tabela     = $tabela
                        WHERE posto    = $posto
                        AND   fabrica  = $login_fabrica";
                $resX = pg_query ($con,$sql);
            }

            $sql= " SELECT  CASE WHEN tbl_posto_fabrica.codigo_posto             <> tmp_posto_$login_admin.codigo_posto              THEN tbl_posto_fabrica.codigo_posto            ELSE null END AS codigo_posto_alterado               ,
                            CASE WHEN tbl_posto_fabrica.credenciamento           <> tmp_posto_$login_admin.credenciamento            THEN tbl_posto_fabrica.credenciamento          ELSE null END AS credenciamento_alterado             ,
                            CASE WHEN tbl_posto_fabrica.senha                    <> tmp_posto_$login_admin.senha                     THEN tbl_posto_fabrica.senha                   ELSE null END AS senha_alterado                      ,
                            CASE WHEN tbl_posto_fabrica.desconto                 <> tmp_posto_$login_admin.desconto                  THEN tbl_posto_fabrica.desconto                ELSE null END AS desconto_alterado                   ,
                            CASE WHEN tbl_posto_fabrica.desconto_acessorio       <> tmp_posto_$login_admin.desconto_acessorio        THEN tbl_posto_fabrica.desconto_acessorio      ELSE null END AS desconto_acessorio_alterado         ,
                            CASE WHEN tbl_posto_fabrica.custo_administrativo     <> tmp_posto_$login_admin.custo_administrativo      THEN tbl_posto_fabrica.custo_administrativo    ELSE null END AS custo_administrativo_alterado       ,
                            CASE WHEN tbl_posto_fabrica.imposto_al               <> tmp_posto_$login_admin.imposto_al                THEN tbl_posto_fabrica.imposto_al              ELSE null END AS imposto_al_alterado                 ,
                            CASE WHEN tbl_posto_fabrica.tipo_posto               <> tmp_posto_$login_admin.tipo_posto                THEN tbl_posto_fabrica.tipo_posto              ELSE null END AS tipo_posto_alterado                 ,
                            CASE WHEN tbl_posto_fabrica.obs                      <> tmp_posto_$login_admin.obs                       THEN tbl_posto_fabrica.obs                     ELSE null END AS obs_alterado                        ,
                            CASE WHEN tbl_posto_fabrica.contato_endereco         <> tmp_posto_$login_admin.contato_endereco          THEN tbl_posto_fabrica.contato_endereco        ELSE null END AS contato_endereco_alterado           ,
                            CASE WHEN tbl_posto_fabrica.contato_numero           <> tmp_posto_$login_admin.contato_numero            THEN tbl_posto_fabrica.contato_numero          ELSE null END AS contato_numero_alterado             ,
                            CASE WHEN tbl_posto_fabrica.contato_complemento      <> tmp_posto_$login_admin.contato_complemento       THEN tbl_posto_fabrica.contato_complemento     ELSE null END AS contato_complemento_alterado        ,
                            CASE WHEN tbl_posto_fabrica.contato_bairro           <> tmp_posto_$login_admin.contato_bairro            THEN tbl_posto_fabrica.contato_bairro          ELSE null END AS contato_bairro_alterado             ,
                            CASE WHEN tbl_posto_fabrica.contato_cidade           <> tmp_posto_$login_admin.contato_cidade            THEN tbl_posto_fabrica.contato_cidade          ELSE null END AS contato_cidade_alterado             ,
                            CASE WHEN tbl_posto_fabrica.contato_cep              <> tmp_posto_$login_admin.contato_cep               THEN tbl_posto_fabrica.contato_cep             ELSE null END AS contato_cep_alterado                ,
                            CASE WHEN tbl_posto_fabrica.contato_estado           <> tmp_posto_$login_admin.contato_estado            THEN tbl_posto_fabrica.contato_estado          ELSE null END AS contato_estado_alterado             ,
                            CASE WHEN tbl_posto_fabrica.contato_fone_comercial   <> tmp_posto_$login_admin.contato_fone_comercial    THEN tbl_posto_fabrica.contato_fone_comercial  ELSE null END AS contato_fone_comercial_alterado     ,
                            CASE WHEN tbl_posto_fabrica.contato_fax              <> tmp_posto_$login_admin.contato_fax               THEN tbl_posto_fabrica.contato_fax             ELSE null END AS contato_fax_alterado                ,
                            CASE WHEN tbl_posto_fabrica.nome_fantasia            <> tmp_posto_$login_admin.nome_fantasia             THEN tbl_posto_fabrica.nome_fantasia           ELSE null END AS nome_fantasia_alterado              ,
                            CASE WHEN tbl_posto_fabrica.contato_email            <> tmp_posto_$login_admin.contato_email             THEN tbl_posto_fabrica.contato_email           ELSE null END AS contato_email_alterado              ,
                            CASE WHEN tbl_posto_fabrica.transportadora           <> tmp_posto_$login_admin.transportadora            THEN tbl_posto_fabrica.transportadora          ELSE null END AS transportadora_alterado             ,
                            CASE WHEN tbl_posto_fabrica.cobranca_endereco        <> tmp_posto_$login_admin.cobranca_endereco         THEN tbl_posto_fabrica.cobranca_endereco       ELSE null END AS cobranca_endereco_alterado          ,
                            CASE WHEN tbl_posto_fabrica.cobranca_numero          <> tmp_posto_$login_admin.cobranca_numero           THEN tbl_posto_fabrica.cobranca_numero         ELSE null END AS cobranca_numero_alterado            ,
                            CASE WHEN tbl_posto_fabrica.cobranca_complemento     <> tmp_posto_$login_admin.cobranca_complemento      THEN tbl_posto_fabrica.cobranca_complemento    ELSE null END AS cobranca_complemento_alterado       ,
                            CASE WHEN tbl_posto_fabrica.cobranca_bairro          <> tmp_posto_$login_admin.cobranca_bairro           THEN tbl_posto_fabrica.cobranca_bairro         ELSE null END AS cobranca_bairro_alterado            ,
                            CASE WHEN tbl_posto_fabrica.cobranca_cep             <> tmp_posto_$login_admin.cobranca_cep              THEN tbl_posto_fabrica.cobranca_cep            ELSE null END AS cobranca_cep_alterado               ,
                            CASE WHEN tbl_posto_fabrica.cobranca_cidade          <> tmp_posto_$login_admin.cobranca_cidade           THEN tbl_posto_fabrica.cobranca_cidade         ELSE null END AS cobranca_cidade_alterado            ,
                            CASE WHEN tbl_posto_fabrica.cobranca_estado          <> tmp_posto_$login_admin.cobranca_estado           THEN tbl_posto_fabrica.cobranca_estado         ELSE null END AS cobranca_estado_alterado            ,
                            CASE WHEN tbl_posto_fabrica.pedido_em_garantia       <> tmp_posto_$login_admin.pedido_em_garantia        THEN tbl_posto_fabrica.pedido_em_garantia      ELSE null END AS pedido_em_garantia_alterado         ,
                            CASE WHEN tbl_posto_fabrica.pedido_faturado          <> tmp_posto_$login_admin.pedido_faturado           THEN tbl_posto_fabrica.pedido_faturado         ELSE null END AS pedido_faturado_alterado            ,
                            CASE WHEN tbl_posto_fabrica.digita_os                <> tmp_posto_$login_admin.digita_os                 THEN tbl_posto_fabrica.digita_os               ELSE null END AS digita_os_alterado                  ,
                            CASE WHEN tbl_posto_fabrica.prestacao_servico        <> tmp_posto_$login_admin.prestacao_servico         THEN tbl_posto_fabrica.prestacao_servico       ELSE null END AS prestacao_servico_alterado          ,
                            CASE WHEN tbl_posto_fabrica.banco                    <> tmp_posto_$login_admin.banco                     THEN tbl_posto_fabrica.banco                   ELSE null END AS banco_alterado                      ,
                            CASE WHEN tbl_posto_fabrica.agencia                  <> tmp_posto_$login_admin.agencia                   THEN tbl_posto_fabrica.agencia                 ELSE null END AS agencia_alterado                    ,
                            CASE WHEN tbl_posto_fabrica.conta                    <> tmp_posto_$login_admin.conta                     THEN tbl_posto_fabrica.conta                   ELSE null END AS conta_alterado                      ,
                            CASE WHEN tbl_posto_fabrica.nomebanco                <> tmp_posto_$login_admin.nomebanco                 THEN tbl_posto_fabrica.nomebanco               ELSE null END AS nomebanco_alterado                  ,
                            CASE WHEN tbl_posto_fabrica.favorecido_conta         <> tmp_posto_$login_admin.favorecido_conta          THEN tbl_posto_fabrica.favorecido_conta        ELSE null END AS favorecido_conta_alterado           ,
                            CASE WHEN tbl_posto_fabrica.cpf_conta                <> tmp_posto_$login_admin.cpf_conta                 THEN tbl_posto_fabrica.cpf_conta               ELSE null END AS cpf_conta_alterado                  ,
                            CASE WHEN tbl_posto_fabrica.tipo_conta               <> tmp_posto_$login_admin.tipo_conta                THEN tbl_posto_fabrica.tipo_conta              ELSE null END AS tipo_conta_alterado                 ,
                            CASE WHEN tbl_posto_fabrica.obs_conta                <> tmp_posto_$login_admin.obs_conta                 THEN tbl_posto_fabrica.obs_conta               ELSE null END AS obs_conta_alterado                  ,
                            CASE WHEN tbl_posto_fabrica.pedido_via_distribuidor  <> tmp_posto_$login_admin.pedido_via_distribuidor   THEN tbl_posto_fabrica.pedido_via_distribuidor ELSE null END AS pedido_via_distribuidor_alterado    ,
                            CASE WHEN tbl_posto_fabrica.item_aparencia           <> tmp_posto_$login_admin.item_aparencia            THEN tbl_posto_fabrica.item_aparencia          ELSE null END AS item_aparencia_alterado             ,
                            CASE WHEN tbl_posto_fabrica.garantia_antecipada      <> tmp_posto_$login_admin.garantia_antecipada       THEN tbl_posto_fabrica.garantia_antecipada     ELSE null END AS garantia_antecipada_alterado        ,
                            CASE WHEN tbl_posto_fabrica.escritorio_regional      <> tmp_posto_$login_admin.escritorio_regional       THEN tbl_posto_fabrica.escritorio_regional     ELSE null END AS escritorio_regional_alterado        ,
                            CASE WHEN tbl_posto_fabrica.tabela                   <> tmp_posto_$login_admin.tabela                    THEN tbl_posto_fabrica.tabela                  ELSE null END AS tabela_alterado                     ,
                            CASE WHEN tbl_posto.nome                             <> tmp_posto_$login_admin.nome                      THEN tbl_posto.nome                            ELSE null END AS nome_alterado                       ,
                            CASE WHEN tbl_posto.cnpj                             <> tmp_posto_$login_admin.cnpj                      THEN tbl_posto.cnpj                            ELSE null END AS cnpj_alterado                       ,
                            CASE WHEN tbl_posto.ie                               <> tmp_posto_$login_admin.ie                        THEN tbl_posto.ie                              ELSE null END AS ie_alterado                         ,
                            /* HD 52864 20/11/2008
                            CASE WHEN tbl_posto.fone                             <> tmp_posto_$login_admin.fone                      THEN tbl_posto.fone                            ELSE null END AS fone_alterado                       ,
                            CASE WHEN tbl_posto.fax                              <> tmp_posto_$login_admin.fax                       THEN tbl_posto.fax                             ELSE null END AS fax_alterado                        ,
                            */
                            CASE WHEN tbl_posto.capital_interior                 <> tmp_posto_$login_admin.capital_interior          THEN tbl_posto.capital_interior                ELSE null END AS capital_interior_alterado           ,
                            CASE WHEN tbl_posto.contato                          <> tmp_posto_$login_admin.contato                   THEN tbl_posto.contato                         ELSE null END AS contato_alterado                    ,
                            tmp_posto_$login_admin.*
                    FROM   tbl_posto_fabrica
                    JOIN   tbl_posto USING (posto)
                    JOIN   tmp_posto_$login_admin USING (posto)
                    WHERE  tbl_posto_fabrica.fabrica = $login_fabrica
                    AND    tbl_posto_fabrica.posto   = $posto";
            $res=pg_query($con,$sql);

            $remetente    = "Telecontrol <telecontrol@telecontrol.com.br>";
            $destinatario = "Robson.Gastao@br.bosch.com";
            $assunto      = "Alteração no posto $xcodigo_posto";

            if(strlen(pg_fetch_result($res,0,codigo_posto_alterado           ))        >0 ) $mensagem ="Foi alterado o código do posto, de -" .pg_fetch_result($res,0,codigo_posto). "para - ".pg_fetch_result($res,0,codigo_posto_alterado) ."<br>";
            if(strlen(pg_fetch_result($res,0,credenciamento_alterado         ))        >0 ) $mensagem ="Foi alterado o credenciamento, de -" .pg_fetch_result($res,0,credenciamento). "para - ".pg_fetch_result($res,0,credenciamento_alterado) ."<br>";
            if(strlen(pg_fetch_result($res,0,senha_alterado                  ))        >0 ) $mensagem.="Foi alterada a Senha, de - " .pg_fetch_result($res,0,senha). " para - " .pg_fetch_result($res,0,senha_alterado) . "<br>";
            if(strlen(pg_fetch_result($res,0,desconto_alterado               ))        >0 ) $mensagem.="Foi alterado o Desconto, de - " .pg_fetch_result($res,0,desconto               ). " para - " .pg_fetch_result($res,0,desconto_alterado               ) . "<br>";
            if(strlen(pg_fetch_result($res,0,desconto_acessorio_alterado     ))        >0 ) $mensagem.="Foi alterada o Desconto Acessório, de - " .pg_fetch_result($res,0,desconto_acessorio     ). " para - " .pg_fetch_result($res,0,desconto_acessorio_alterado     ) . "<br>";
            if(strlen(pg_fetch_result($res,0,custo_administrativo_alterado   ))        >0 ) $mensagem.="Foi alterada o Custo Administrativo, de - " .pg_fetch_result($res,0,custo_administrativo   ). " para - " .pg_fetch_result($res,0,custo_administrativo_alterado   ) . "<br>";
            if(strlen(pg_fetch_result($res,0,imposto_al_alterado             ))        >0 ) $mensagem.="Foi alterada o Imposto IVA, de - " .pg_fetch_result($res,0,imposto_al             ). " para - " .pg_fetch_result($res,0,imposto_al_alterado             ) . "<br>";
            if(strlen(pg_fetch_result($res,0,tipo_posto_alterado             ))        >0 ) $mensagem.="Foi alterada o Tipo do posto, de - " .pg_fetch_result($res,0,tipo_posto             ). " para - " .pg_fetch_result($res,0,tipo_posto_alterado             ) . "<br>";
            if(strlen(pg_fetch_result($res,0,obs_alterado                    ))        >0 ) $mensagem.="Foi alterada a observação, de - " .pg_fetch_result($res,0,obs                    ). " para - " .pg_fetch_result($res,0,obs_alterado                    ) . "<br>";
            if(strlen(pg_fetch_result($res,0,contato_endereco_alterado       ))        >0 ) $mensagem.="Foi alterada o endereço, de - " .pg_fetch_result($res,0,contato_endereco       ). " para - " .pg_fetch_result($res,0,contato_endereco_alterado       ) . "<br>";
            if(strlen(pg_fetch_result($res,0,contato_numero_alterado         ))        >0 ) $mensagem.="Foi alterada o número, de - " .pg_fetch_result($res,0,contato_numero         ). " para - " .pg_fetch_result($res,0,contato_numero_alterado         ) . "<br>";
            if(strlen(pg_fetch_result($res,0,contato_complemento_alterado    ))        >0 ) $mensagem.="Foi alterada o complemento, de - " .pg_fetch_result($res,0,contato_complemento    ). " para - " .pg_fetch_result($res,0,contato_complemento_alterado    ) . "<br>";
            if(strlen(pg_fetch_result($res,0,contato_bairro_alterado         ))        >0 ) $mensagem.="Foi alterada o bairro, de - " .pg_fetch_result($res,0,contato_bairro         ). " para - " .pg_fetch_result($res,0,contato_bairro_alterado         ) . "<br>";
            if(strlen(pg_fetch_result($res,0,contato_cidade_alterado         ))        >0 ) $mensagem.="Foi alterada a cidade, de - " .pg_fetch_result($res,0,contato_cidade         ). " para - " .pg_fetch_result($res,0,contato_cidade_alterado         ) . "<br>";
            if(strlen(pg_fetch_result($res,0,contato_cep_alterado            ))        >0 ) $mensagem.="Foi alterada o cep, de - " .pg_fetch_result($res,0,contato_cep            ). " para - " .pg_fetch_result($res,0,contato_cep_alterado            ) . "<br>";
            if(strlen(pg_fetch_result($res,0,contato_estado_alterado         ))        >0 ) $mensagem.="Foi alterada o estado, de - " .pg_fetch_result($res,0,contato_estado         ). " para - " .pg_fetch_result($res,0,contato_estado_alterado         ) . "<br>";
            if(strlen(pg_fetch_result($res,0,transportadora_alterado         ))        >0 ) $mensagem.="Foi alterada a transportadora, de - " .pg_fetch_result($res,0,transportadora         ). " para - " .pg_fetch_result($res,0,transportadora_alterado         ) . "<br>";
            if(strlen(pg_fetch_result($res,0,cobranca_endereco_alterado      ))        >0 ) $mensagem.="Foi alterada o endereço da cobrança, de - " .pg_fetch_result($res,0,cobranca_endereco      ). " para - " .pg_fetch_result($res,0,cobranca_endereco_alterado      ) . "<br>";
            if(strlen(pg_fetch_result($res,0,cobranca_numero_alterado        ))        >0 ) $mensagem.="Foi alterada o número da cobrança, de - " .pg_fetch_result($res,0,cobranca_numero        ). " para - " .pg_fetch_result($res,0,cobranca_numero_alterado        ) . "<br>";
            if(strlen(pg_fetch_result($res,0,cobranca_complemento_alterado   ))        >0 ) $mensagem.="Foi alterada o complemento da cobrança, de - " .pg_fetch_result($res,0,cobranca_complemento   ). " para - " .pg_fetch_result($res,0,cobranca_complemento_alterado   ) . "<br>";
            if(strlen(pg_fetch_result($res,0,cobranca_bairro_alterado        ))        >0 ) $mensagem.="Foi alterada o bairro da cobrança, de - " .pg_fetch_result($res,0,cobranca_bairro        ). " para - " .pg_fetch_result($res,0,cobranca_bairro_alterado        ) . "<br>";
            if(strlen(pg_fetch_result($res,0,cobranca_cep_alterado           ))        >0 ) $mensagem.="Foi alterada o cep da cobrança, de - " .pg_fetch_result($res,0,cobranca_cep           ). " para - " .pg_fetch_result($res,0,cobranca_cep_alterado           ) . "<br>";
            if(strlen(pg_fetch_result($res,0,cobranca_cidade_alterado        ))        >0 ) $mensagem.="Foi alterada a cidade da cobrança, de - " .pg_fetch_result($res,0,cobranca_cidade        ). " para - " .pg_fetch_result($res,0,cobranca_cidade_alterado        ) . "<br>";
            if(strlen(pg_fetch_result($res,0,cobranca_estado_alterado        ))        >0 ) $mensagem.="Foi alterada o estado da cobrança, de - " .pg_fetch_result($res,0,cobranca_estado        ). " para - " .pg_fetch_result($res,0,cobranca_estado_alterado        ) . "<br>";
            if(strlen(pg_fetch_result($res,0,agencia_alterado                ))        >0 ) $mensagem.="Foi alterada a agencia, de - " .pg_fetch_result($res,0,agencia                ). " para - " .pg_fetch_result($res,0,agencia_alterado                ) . "<br>";
            if(strlen(pg_fetch_result($res,0,conta_alterado                  ))        >0 ) $mensagem.="Foi alterada a conta, de - " .pg_fetch_result($res,0,conta                  ). " para - " .pg_fetch_result($res,0,conta_alterado                  ) . "<br>";
            if(strlen(pg_fetch_result($res,0,nomebanco_alterado              ))        >0 ) $mensagem.="Foi alterada o banco , de - " .pg_fetch_result($res,0,nomebanco              ). " para - " .pg_fetch_result($res,0,nomebanco_alterado              ) . "<br>";
            if(strlen(pg_fetch_result($res,0,favorecido_conta_alterado       ))        >0 ) $mensagem.="Foi alterada o Nome favorecido, de - " .pg_fetch_result($res,0,favorecido_conta       ). " para - " .pg_fetch_result($res,0,favorecido_conta_alterado       ) . "<br>";
            if(strlen(pg_fetch_result($res,0,cpf_conta_alterado              ))        >0 ) $mensagem.="Foi alterada o CPF do favorecido, de - " .pg_fetch_result($res,0,cpf_conta              ). " para - " .pg_fetch_result($res,0,cpf_conta_alterado              ) . "<br>";
            if(strlen(pg_fetch_result($res,0,tipo_conta_alterado             ))        >0 ) $mensagem.="Foi alterada o tipo de conta, de - " .pg_fetch_result($res,0,tipo_conta             ). " para - " .pg_fetch_result($res,0,tipo_conta_alterado             ) . "<br>";
            if(strlen(pg_fetch_result($res,0,obs_conta_alterado              ))        >0 ) $mensagem.="Foi alterada a observação da conta, de - " .pg_fetch_result($res,0,obs_conta              ). " para - " .pg_fetch_result($res,0,obs_conta_alterado              ) . "<br>";
            if(strlen(pg_fetch_result($res,0,escritorio_regional_alterado    ))        >0 ) $mensagem.="Foi alterada O escritório regional, de - " .pg_fetch_result($res,0,escritorio_regional    ). " para - " .pg_fetch_result($res,0,escritorio_regional_alterado    ) . "<br>";
            if(strlen(pg_fetch_result($res,0,tabela_alterado                 ))        >0 ) $mensagem.="Foi alterada a tabela, de - " .pg_fetch_result($res,0,tabela                 ). " para - " .pg_fetch_result($res,0,tabela_alterado                 ) . "<br>";
            if(strlen(pg_fetch_result($res,0,nome_alterado                   ))        >0 ) $mensagem.="Foi alterada o Nome do posto, de - " .pg_fetch_result($res,0,nome                   ). " para - " .pg_fetch_result($res,0,nome_alterado                   ) . "<br>";
            if(strlen(pg_fetch_result($res,0,cnpj_alterado                   ))        >0 ) $mensagem.="Foi alterada o CNPJ do posto, de - " .pg_fetch_result($res,0,cnpj                   ). " para - " .pg_fetch_result($res,0,cnpj_alterado                   ) . "<br>";
            if(strlen(pg_fetch_result($res,0,ie_alterado                     ))        >0 ) $mensagem.="Foi alterada o Inscrição Estadual, de - " .pg_fetch_result($res,0,ie                     ). " para - " .pg_fetch_result($res,0,ie_alterado                     ) . "<br>";
            if(strlen(pg_fetch_result($res,0,capital_interior_alterado       ))        >0 ) $mensagem.="Foi alterada o Capital/Interior, de - " .pg_fetch_result($res,0,capital_interior       ). " para - " .pg_fetch_result($res,0,capital_interior_alterado       ) . "<br>";
            if(strlen(pg_fetch_result($res,0,contato_alterado                ))        >0 ) $mensagem.="Foi alterada o contato, de - " .pg_fetch_result($res,0,contato                ). " para - " .pg_fetch_result($res,0,contato_alterado                ) . "<br>";
            if(pg_fetch_result($res,0,pedido_em_garantia_alterado)          =='t') $mensagem.="Este posto não fazia PEDIDO EM GARANTIA (Manual), foi alerado para poder fazer<br>";
            elseif(pg_fetch_result($res,0,pedido_em_garantia_alterado)      =='f')                                                                       $mensagem.="Este posto fazia PEDIDO EM GARANTIA (Manual), foi alerado para não poder fazer<br>";
            if(pg_fetch_result($res,0,pedido_faturado_alterado)             =='t') $mensagem.="Este posto não fazia PEDIDO FATURADO (Manual), foi alerado para poder fazer<br>";
            elseif(pg_fetch_result($res,0,pedido_faturado_alterado)         =='f')                                                                       $mensagem.="Este posto fazia DIGITA OS, foi alerado para não poder fazer<br>";
            if(pg_fetch_result($res,0,digita_os_alterado)                   =='t') $mensagem.="Este posto não fazia DIGITA OS, foi alerado para poder fazer<br>";
            elseif(pg_fetch_result($res,0,digita_os_alterado)               =='f')                                                                      $mensagem.="Este posto fazia PEDIDO FATURADO (Manual), foi alerado para não poder fazer<br>";
            if(pg_fetch_result($res,0,prestacao_servico_alterado)           =='t') $mensagem.="Este posto não fazia PRESTAÇÃO DE SERVIÇO, foi alerado para poder fazer<br>";
            elseif(pg_fetch_result($res,0,prestacao_servico_alterado)       =='f')                                                                       $mensagem.="Este posto fazia PRESTAÇÃO DE SERVIÇO, foi alerado para não poder fazer<br>";
            if(pg_fetch_result($res,0,pedido_via_distribuidor_alterado)     =='t') $mensagem.="Este posto não fazia PEDIDO VIA DISTRIBUIDOR, foi alerado para poder fazer<br>";
            elseif(pg_fetch_result($res,0,pedido_via_distribuidor_alterado) =='f')                                                                       $mensagem.="Este posto fazia PEDIDO VIA DISTRIBUIDOR, foi alerado para não poder fazer<br>";
            if(pg_fetch_result($res,0,item_aparencia_alterado)              =='t') $mensagem.="Este posto não podia pedir peças com item de aparência, foi alerado para poder fazer<br>";
            elseif(pg_fetch_result($res,0,item_aparencia_alterado)          =='f')                                                                       $mensagem.="Este posto podia pedir peças com item de aparência, foi alerado para não poder fazer<br>";

            $headers="Return-Path: <telecontrol@telecontrol.com.br>\nFrom:".$remetente."\nContent-type: text/html\n";
            if(strlen(trim($mensagem))>0) {
                $conteudo="Foi alterado os dados do posto $xcodigo_posto:<br>".$mensagem;
                if(mail($destinatario, utf8_encode($assunto), utf8_encode($conteudo), $headers)){
                    #echo "enviado com sucesso"; exit;
                };
            }
        }

        if (strlen($msg_erro) == 0 && $login_fabrica == 1) {
            $sql =  "SELECT DISTINCT tbl_condicao.condicao , tbl_posto_condicao.visivel
                    FROM tbl_condicao
                    JOIN tbl_posto_condicao USING (condicao)
                    JOIN tbl_posto_fabrica USING (posto)
                    WHERE tbl_condicao.fabrica = $login_fabrica
                    AND   tbl_condicao.tabela  = 31
                    AND   tbl_condicao.visivel IS TRUE
                    AND   tbl_posto_fabrica.tipo_posto = $xtipo_posto
                    ORDER BY tbl_condicao.condicao ASC";
            $res1 = pg_query($con,$sql);
            for ($i = 0 ; $i < pg_num_rows($res1) ; $i++) {
                $condicao = pg_fetch_result($res1,$i,condicao);
                $visivel  = pg_fetch_result($res1,$i,visivel);

                $tabela = ($condicao == 62) ? 47 : 31;

                $sql =  "SELECT condicao
                        FROM tbl_posto_condicao
                        WHERE posto  = $posto
                        AND condicao = $condicao;";
                $res2 = pg_query($con,$sql);
                if (pg_num_rows($res2) > 0) {
                    $sql =  "UPDATE tbl_posto_condicao SET
                                tabela  = $tabela,
                                visivel = '$visivel'
                            WHERE tbl_posto_condicao.condicao = $condicao
                            AND   tbl_posto_condicao.posto    = $posto;";
                }else{
                    $sql = "INSERT INTO tbl_posto_condicao (
                                posto    ,
                                condicao ,
                                tabela   ,
                                visivel
                            ) VALUES (
                                $posto    ,
                                $condicao ,
                                $tabela   ,
                                '$visivel'
                            );";
                }
                $res = @pg_query($con,$sql);
                $msg_erro = pg_errormessage($con);
                if (strlen($msg_erro) > 0) {
                    $msg_erro = traduz("Não foi possível cadastrar a condição de pagamento p/ este posto.");
                    break;
                }
            }

            if($login_fabrica==1){
                $sql = "UPDATE tbl_posto_condicao SET
                                visivel = $xpedido_em_garantia_finalidades_diversas
                        WHERE posto  = $posto
                        AND condicao = 62";
                $res = @pg_query($con,$sql);
            }

            /* HD 23738 */
            if($login_fabrica==1){
                $sql = "SELECT  tbl_posto_fabrica.escolhe_condicao,
                                tbl_posto_fabrica.condicao_escolhida,
                                tbl_posto.nome,
                                tbl_posto_fabrica.contato_email
                        FROM tbl_posto_fabrica
                        JOIN tbl_posto USING(posto)
                        WHERE tbl_posto_fabrica.posto   = $posto
                        AND   tbl_posto_fabrica.fabrica = $login_fabrica";
                $res = @pg_query($con,$sql);
                if (pg_num_rows($res) > 0) {
                    $escolhe_condicao_ant = pg_fetch_result($res,0,escolhe_condicao);
                    $condicao_escolhida   = pg_fetch_result($res,0,condicao_escolhida);
                    $posto_nome           = pg_fetch_result($res,0,nome);
                    $posto_email          = pg_fetch_result($res,0,contato_email);

                    $sql = "UPDATE tbl_posto_fabrica SET
                                    escolhe_condicao = $xescolhe_condicao
                            WHERE posto   = $posto
                            AND   fabrica = $login_fabrica";
                    $res = @pg_query($con,$sql);

                    if ($condicao_escolhida == '' AND $escolhe_condicao_ant <> 't'){
                        if ($escolhe_condicao == 't'){

                            /* Dispara um email para o PA */
                            $assunto = "Definir condição de pagamento: Posto $codigo_posto";
                            $mensagem  = "<b>Nota: Este e-mail é gerado automaticamente. <br>**** POR FAVOR NÃO RESPONDA ESTA MENSAGEM </b>****.<BR><BR><BR>";
                            $mensagem .= "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
                            $mensagem .= "<font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>\n";
                            $mensagem .= "<b>Prezado Cliente,</b> ";
                            $mensagem .= "<br><br>\n";
                            $mensagem .= "Informamos que o seu posto foi nomeado para adquirir peças direto com a fábrica.";
                            $mensagem .= "<br>\n";
                            $mensagem .= "<br>\n";
                            $mensagem .= "Acesse a tela de digitação no site através do caminho <b>PEDIDOS/CADASTRO DE PEDIDOS DE PEÇAS</b> para ler o procedimento sobre definição da condição de pagamento.";
                            $mensagem .= "<br><br>\n";
                            $mensagem .= "Obrigada.";
                            $mensagem .= "<br><br>\n";
                            $mensagem .= "Black & Decker do Brasil.";
                            $mensagem .= "</font>";

                            $cabecalho .= "MIME-Version: 1.0\n";
                            $cabecalho .= "Content-type: text/html; charset=iso-8859-1\n";
                            $sqlemail = "SELECT email
                                    FROM tbl_admin
                                    WHERE fabrica = $login_fabrica
                                    and tbl_admin.admin = $login_admin";
                            $resemail = pg_query($con,$sqlemail);
                            $email_admin = pg_fetch_result($resemail,0,email);

                            $cabecalho .= "From: Black & Decker <$email_admin>\n";
                            $cabecalho .= "To: $posto_nome <$posto_email>, Blackedecker <$email_admin>\n";

                            $cabecalho .= "Subject: $assunto\n";
                            $cabecalho .= "X-Priority: 1\n";
                            $cabecalho .= "X-MSMail-Priority: High\n";
                            $cabecalho .= "X-Mailer: PHP/" . phpversion();

                            if ( !mail("", $assunto, $mensagem, $cabecalho) ) {
                                $msg_erro = traduz(" Não foi possível enviar o email. Tente novamente.");
                            }
                        }
                    }

                    if ($escolhe_condicao == 't' AND $condicao_escolhida == 'f' AND strlen($condicao_liberada)>0){
                        $sql = "UPDATE tbl_posto_fabrica SET
                                        condicao_escolhida = 't',
                                        escolhe_condicao   = 'f'
                                WHERE posto   = $posto
                                AND   fabrica = $login_fabrica";
                        $res = @pg_query($con,$sql);

                        /* Dispara um email para o PA */
                        $assunto = "Definir condição de pagamento: Posto $codigo_posto";
                        $mensagem  = "<b>Nota: Este e-mail é gerado automaticamente. <br>**** POR FAVOR NÃO RESPONDA ESTA MENSAGEM </b>****.<BR><BR><BR>";
                        $mensagem .= "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
                        $mensagem .= "<font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>\n";
                        $mensagem .= "<b>Prezado Cliente,</b> ";
                        $mensagem .= "<br><br><br>\n";
                        $mensagem .= "Informamos que tela de digitacao de pedido foi liberada com a condição de pagamento que você escolheu.";
                        $mensagem .= "<br><br>\n";
                        $mensagem .= "Acesse a tela de digitação no site através do caminho <b>PEDIDOS/CADASTRO DE PEDIDOS DE PEÇAS</b>.";
                        $mensagem .= "<br><br>\n";
                        $mensagem .= "Obrigada.";
                        $mensagem .= "<br><br><br>\n";
                        $mensagem .= "Black & Decker do Brasil.";
                        $mensagem .= "</font>";

                        $sqlemail = "SELECT email
                                    FROM tbl_admin
                                    WHERE fabrica = $login_fabrica
                                    and tbl_admin.admin = $login_admin";
                        $resemail = pg_query($con,$sqlemail);
                        $email_admin = pg_fetch_result($resemail,0,email);
                        $cabecalho .= "MIME-Version: 1.0\n";
                        $cabecalho .= "Content-type: text/html; charset=iso-8859-1\n";
                        $cabecalho .= "From: Black & Decker <$email_admin>\n";
                        $cabecalho .= "To: $posto_nome <$posto_email>, Blackedecker <$email_admin>\n";

                        #$cabecalho .= "To: Rúbia Fernandes <rfernandes@blackedecker.com.br> \n";
                        $cabecalho .= "Subject: $assunto\n";
                        $cabecalho .= "X-Priority: 1\n";
                        $cabecalho .= "X-MSMail-Priority: High\n";
                        $cabecalho .= "X-Mailer: PHP/" . phpversion();

                        if ( !mail("", $assunto, $mensagem, $cabecalho) ) {
                            $msg_erro = traduz("Não foi possível enviar o email. Tente novamente.");
                        }
                    }
                }
            }
        }

        //hd 49412 - o código abaixo se repete para cada foto, pois o admin pode cadastrar fotos com extensões diferentes.
        if (strlen($msg_erro)==0 and $login_fabrica==50) {
            if (isset($_FILES['foto_posto'])) {
                $Destino  = "/www/assist/www/foto_posto/";
                $DestinoT = "/www/assist/www/foto_posto/";

                $Fotos    = $_FILES['foto_posto'];
                $Nome     = $Fotos['name'];
                $Tamanho  = $Fotos['size'];
                $Tipo     = $Fotos['type'];
                $Tmpname  = $Fotos['tmp_name'];
                $Extensao = $Nome;

                if(strlen($Extensao)>0){
                    if(preg_match('/^image\/(pjpeg|jpeg|png|gif|jpg)$/', $Tipo)){
                        if(!is_uploaded_file($Tmpname)){
                            $msg_erro .= traduz("Não foi possível efetuar o upload.");
                        }

                        $tmp = explode(".",$Nome);
                        $ext = $tmp[count($tmp)-1];

                        if (strlen($Extensao)==0){
                            $ext = $Extensao;
                        }

                        $ext = strtolower($ext);

                        $sql = "SELECT posto_fabrica_foto
                                FROM tbl_posto_fabrica_foto
                                WHERE posto = $posto
                                AND fabrica = $login_fabrica";
                        $res = pg_query ($con,$sql);

                        if (pg_num_rows($res) == 0) {
                            #insere um registro
                            $sql = "INSERT INTO tbl_posto_fabrica_foto
                                        (posto, fabrica)
                                        VALUES ($posto,$login_fabrica)";

                            $res = pg_query ($con,$sql);
                            $msg_erro .= pg_errormessage($con);

                            $sql                = "SELECT CURRVAL ('seq_posto_fabrica_foto')";
                            $res                = pg_query ($con,$sql);
                            $posto_fabrica_foto = pg_fetch_result($res,0,0);
                        } else {
                            $posto_fabrica_foto = pg_fetch_result($res,0,0);
                        }

                        $nome_foto  = "$posto_fabrica_foto"."_posto.$ext";
                        $nome_thumb = "$posto_fabrica_foto"."_posto_thumb.$ext";

                        $Caminho_foto  = "../foto_posto/$nome_foto";
                        $Caminho_thumb = "../foto_posto/$nome_thumb";

                        $descricao_foto_posto = str_replace("\'","",$_POST['descricao_foto_posto']);
                        $descricao_foto_posto = str_replace("\"","",$descricao_foto_posto);

                        #Atualiza o nome do arquivo na tabela
                        if (strlen($posto_fabrica_foto)>0){
                            $sql = "UPDATE tbl_posto_fabrica_foto SET
                                        foto_posto           = '$Caminho_foto',
                                        foto_posto_thumb     = '$Caminho_thumb',
                                        foto_posto_Descricao = '$descricao_foto_posto'
                                    WHERE posto_fabrica_foto = $posto_fabrica_foto";
                            $res = pg_query ($con,$sql);
                        }

                        reduz_imagem($Tmpname, 400, 300, $Caminho_foto);
                        reduz_imagem($Tmpname, 100, 90,  $Caminho_thumb);
                    }else{
                        $msg_erro .= ("O formato da foto $Nome não é permitido!")."<br>";
                    }
                }
            }

            if (isset($_FILES['foto_contato1'])) {
                $Destino  = "/www/assist/www/foto_posto/";
                $DestinoT = "/www/assist/www/foto_posto/";

                $Fotos    = $_FILES['foto_contato1'];
                $Nome     = $Fotos['name'];
                $Tamanho  = $Fotos['size'];
                $Tipo     = $Fotos['type'];
                $Tmpname  = $Fotos['tmp_name'];
                $Extensao = $Nome;

                if(strlen($Extensao)>0){
                    if(preg_match('/^image\/(pjpeg|jpeg|png|gif|jpg)$/', $Tipo)){
                        if(!is_uploaded_file($Tmpname)){
                            $msg_erro .= traduz("Não foi possível efetuar o upload.");
                        }

                        $tmp = explode(".",$Nome);
                        $ext = $tmp[count($tmp)-1];

                        if (strlen($Extensao)==0){
                            $ext = $Extensao;
                        }

                        $ext = strtolower($ext);

                        $sql = "SELECT posto_fabrica_foto
                                FROM tbl_posto_fabrica_foto
                                WHERE posto = $posto
                                AND fabrica = $login_fabrica";
                        $res = pg_query ($con,$sql);

                        if (pg_num_rows($res) == 0) {
                            #insere um registro
                            $sql = "INSERT INTO tbl_posto_fabrica_foto
                                        (posto, fabrica)
                                        VALUES ($posto,$login_fabrica)";

                            $res = pg_query ($con,$sql);
                            $msg_erro .= pg_errormessage($con);

                            $sql                = "SELECT CURRVAL ('seq_posto_fabrica_foto')";
                            $res                = pg_query ($con,$sql);
                            $posto_fabrica_foto = pg_fetch_result($res,0,0);
                        } else {
                            $posto_fabrica_foto = pg_fetch_result($res,0,0);
                        }

                        $nome_foto  = "$posto_fabrica_foto"."_contato1.$ext";
                        $nome_thumb = "$posto_fabrica_foto"."_contato1_thumb.$ext";

                        $Caminho_foto  = "../foto_posto/$nome_foto";
                        $Caminho_thumb = "../foto_posto/$nome_thumb";

                        $descricao_foto_contato1 = str_replace("\'","",$_POST['descricao_foto_contato1']);
                        $descricao_foto_contato1 = str_replace("\"","",$descricao_foto_contato1);

                        #Atualiza o nome do arquivo na tabela
                        if (strlen($posto_fabrica_foto)>0){
                            $sql = "UPDATE tbl_posto_fabrica_foto SET
                                        foto_contato1           = '$Caminho_foto',
                                        foto_contato1_thumb     = '$Caminho_thumb',
                                        foto_contato1_descricao = '$descricao_foto_contato1'
                                    WHERE posto_fabrica_foto = $posto_fabrica_foto";
                            $res = pg_query ($con,$sql);
                        }

                        reduz_imagem($Tmpname, 400, 300, $Caminho_foto);
                        reduz_imagem($Tmpname, 100, 90,  $Caminho_thumb);
                    }else{
                        $msg_erro .= traduz("O formato da foto $Nome não é permitido!")."<br>";
                    }
                }
            }

            if (isset($_FILES['foto_contato2'])) {
                $Destino  = "/www/assist/www/foto_posto/";
                $DestinoT = "/www/assist/www/foto_posto/";

                $Fotos    = $_FILES['foto_contato2'];
                $Nome     = $Fotos['name'];
                $Tamanho  = $Fotos['size'];
                $Tipo     = $Fotos['type'];
                $Tmpname  = $Fotos['tmp_name'];
                $Extensao = $Nome;

                if(strlen($Extensao)>0){
                    if(preg_match('/^image\/(pjpeg|jpeg|png|gif|jpg)$/', $Tipo)){
                        if(!is_uploaded_file($Tmpname)){
                            $msg_erro .= traduz("Não foi possível efetuar o upload.");
                        }

                        $tmp = explode(".",$Nome);
                        $ext = $tmp[count($tmp)-1];

                        if (strlen($Extensao)==0){
                            $ext = $Extensao;
                        }

                        $ext = strtolower($ext);

                        $sql = "SELECT posto_fabrica_foto
                                FROM tbl_posto_fabrica_foto
                                WHERE posto = $posto
                                AND fabrica = $login_fabrica";
                        $res = pg_query ($con,$sql);

                        if (pg_num_rows($res) == 0) {
                            #insere um registro
                            $sql = "INSERT INTO tbl_posto_fabrica_foto
                                        (posto, fabrica)
                                        VALUES ($posto,$login_fabrica)";

                            $res = pg_query ($con,$sql);
                            $msg_erro .= pg_errormessage($con);

                            $sql                = "SELECT CURRVAL ('seq_posto_fabrica_foto')";
                            $res                = pg_query ($con,$sql);
                            $posto_fabrica_foto = pg_fetch_result($res,0,0);
                        } else {
                            $posto_fabrica_foto = pg_fetch_result($res,0,0);
                        }

                        $nome_foto  = "$posto_fabrica_foto"."_contato2.$ext";
                        $nome_thumb = "$posto_fabrica_foto"."_contato2_thumb.$ext";

                        $Caminho_foto  = "../foto_posto/$nome_foto";
                        $Caminho_thumb = "../foto_posto/$nome_thumb";

                        $descricao_foto_contato2 = str_replace("\'","",$_POST['descricao_foto_contato2']);
                        $descricao_foto_contato2 = str_replace("\"","",$descricao_foto_contato2);

                        #Atualiza o nome do arquivo na tabela
                        if (strlen($posto_fabrica_foto)>0){
                            $sql = "UPDATE tbl_posto_fabrica_foto SET
                                        foto_contato2       = '$Caminho_foto',
                                        foto_contato2_thumb = '$Caminho_thumb',
                                        foto_contato2_descricao = '$descricao_foto_contato2'
                                    WHERE posto_fabrica_foto = $posto_fabrica_foto";
                            $res = pg_query ($con,$sql);
                        }

                        reduz_imagem($Tmpname, 400, 300, $Caminho_foto);
                        reduz_imagem($Tmpname, 100, 90,  $Caminho_thumb);
                    }else{
                        $msg_erro .= traduz("O formato da foto $Nome não é permitido!")."<br>";
                    }
                }
            }
        }

        #HD 401553 INICIO
        if (strlen($msg_erro)==0 && $login_fabrica == 42) {

            $posto_edicao = $posto;
            /* DELETA TODOS OS REGISTROS DA TABELA tbl_posto_filial
                RELACIONADOS AO POSTO QUE ESTÁ SENDO EDITADO */
                $sql_del_posto_filial = "
                    DELETE from tbl_posto_filial
                    WHERE tbl_posto_filial.posto = $posto_edicao;
                ";

                $res_del_posto_filial = pg_query($con,$sql_del_posto_filial);
                $msg_erro .= pg_errormessage($con);

            /* FAZ A CONTA DE QUANTOS POSTOS ESTÃO COMO DISTRIBUIDOR */
            $sql_distribuidores = "
                SELECT  COUNT(tbl_posto_fabrica.nome_fantasia)
                FROM    tbl_posto_fabrica
                JOIN    tbl_tipo_posto USING (tipo_posto)
                WHERE   tbl_tipo_posto.fabrica = $login_fabrica
                AND     tbl_posto_fabrica.filial IS TRUE;
            ";

            $res_distribuidores = pg_query($con,$sql_distribuidores);

            for ($x = 0; $x < $res_distribuidores;$x++) {

                $posto_filial       = $_POST['posto_distrib_'.$x];
                $filial_garantia    = $_POST['filial_garantia_'.$x];
                $filial_faturado    = $_POST['filial_faturado_'.$x];
                $filial_retira    = $_POST['filial_retira_'.$x];
                $filial_sedex_cobrar = $_POST['filial_sedex_cobrar_'.$x];

                if (!$msg_erro
                    && (
                        !empty($filial_garantia)
                        || !empty($filial_faturado)
                        || !empty($filial_retira)
                        || !empty($filial_sedex_cobrar)
                    )
                ) {
                    /* INSERE NA TABELA tbl_posto_filial OS POSTOS QUE FORAM SELECIONADOS
                    NA EDIÇÃO DA TABELA "Filiais" DO FORMULÁRIO */

                    $filial_garantia = (!empty($filial_garantia)) ? 'TRUE' : 'FALSE';
                    $filial_faturado = (!empty($filial_faturado)) ? 'TRUE' : 'FALSE';
                    $filial_retira = (!empty($filial_retira)) ? 'TRUE' : 'FALSE';
                    $filial_sedex_cobrar = (!empty($filial_sedex_cobrar)) ? 'TRUE' : 'FALSE';

                    $parametros_filial = ['retira' => $filial_retira, 'sedex' => $filial_sedex_cobrar];
                    $parametros_filial = json_encode($parametros_filial);

                    $sql_insere_posto_filial = "
                        INSERT INTO tbl_posto_filial (
                            posto,
                            filial_posto,
                            fabrica,
                            garantia,
                            faturado,
                            parametros_adicionais
                        ) VALUES (
                            $posto_edicao,
                            $posto_filial,
                            $login_fabrica,
                            $filial_garantia,
                            $filial_faturado,
                            '{$parametros_filial}'
                        )
                    ";
                    $res_insere_posto_filial = pg_query($con,$sql_insere_posto_filial);
                    $msg_erro .= pg_errormessage($con);

                }
            }
        }
        #HD 401553 FIM

        // Implantação Imbera - HD 2586842 (Grava Técnico Padrão, adiciona o Centro Distribuidor, agrega tipos de atendimento ao posto e grava as amarrações de grupos de clientes que o posto atende)
        if (strlen($msg_erro) == 0 && in_array($login_fabrica, array(158))) {



            $sql = "SELECT * FROM tbl_distribuidor_sla_posto WHERE posto = {$posto} AND fabrica = {$login_fabrica};";
            $res = pg_query($con, $sql);

            if (pg_num_rows($res) > 0) {
                $sqlDelDistribuidores = "DELETE FROM tbl_distribuidor_sla_posto WHERE posto = {$posto} AND fabrica = {$login_fabrica};";
                $resDelDistribuidores = pg_query($con, $sqlDelDistribuidores);
            }

            if (strlen(pg_last_error()) == 0) {
                foreach ($distribuidores_selected as $id_distribuidor) {
                    $sqlInsDistrib = "
                        INSERT INTO tbl_distribuidor_sla_posto (fabrica, distribuidor_sla, posto)
                        VALUES ({$login_fabrica}, {$id_distribuidor}, {$posto});
                    ";
                    $resInsDistrib = pg_query($con, $sqlInsDistrib);
                }
            } else {
                $msg_erro = traduz("Ocorreu um erro gravando dados da Unidade de negócio");
            }

            //HD-6955553                
            $AuditorLog2 = new AuditorLog;
            $AuditorLog2->RetornaDadosTabela('tbl_posto_distribuidor_sla_default',array("posto" => $posto,"fabrica" => $login_fabrica));

            $sql = "SELECT * FROM tbl_posto_distribuidor_sla_default WHERE posto = {$posto} AND fabrica = {$login_fabrica};";
            $res = pg_query($con, $sql);

            if (pg_num_rows($res) > 0) {
                $sqlDelDistribPrincipal = "DELETE FROM tbl_posto_distribuidor_sla_default WHERE posto = {$posto} AND fabrica = {$login_fabrica};";
                $resDelDistribPrincipal = pg_query($con, $sqlDelDistribPrincipal);
            }

           if (strlen(pg_last_error()) == 0) {

                    $sqlInsDistribPrincipal = "
                    INSERT INTO tbl_posto_distribuidor_sla_default (fabrica, distribuidor_sla, posto)
                    VALUES ({$login_fabrica}, {$unidade_principal}, {$posto});
                ";
                $resInsDistribPrincipal = pg_query($con, $sqlInsDistribPrincipal);
                                                           
                
            } else {
                $msg_erro = traduz("Ocorreu um erro gravando a Unidade de negócio principal");
            }

            $AuditorLog2->retornaDadosTabela()->enviarLog('UPDATE', 'tbl_posto_distribuidor_sla_default', "$login_fabrica*$posto");


            $sqlDelTipAtendimento = "DELETE FROM tbl_posto_tipo_atendimento WHERE posto = {$posto} AND fabrica = {$login_fabrica};";
            $resDelTipAtendimento = pg_query($con, $sqlDelTipAtendimento);

            if (strlen(pg_last_error()) == 0) {
                foreach ($tipos_atendimento as $tipo_atendimento) {
                    $sqlInsTiposAtend = "
                        INSERT INTO tbl_posto_tipo_atendimento
                            (fabrica, posto, tipo_atendimento)
                        VALUES
                            ({$login_fabrica}, {$posto}, {$tipo_atendimento});
                    ";
                    $resInsTiposAtend = pg_query($con, $sqlInsTiposAtend);
                }
            }

            $sqlDelGrupoCliente = "DELETE FROM tbl_posto_grupo_cliente WHERE posto = {$posto} AND fabrica = {$login_fabrica};";
            $resDelGrupoCliente = pg_query($con, $sqlDelGrupoCliente);

            if (strlen(pg_last_error()) == 0) {
                foreach ($grupos_clientes as $grupo_clientes) {
                    $sqlInsGrupCli = "
                        INSERT INTO tbl_posto_grupo_cliente
                            (fabrica, posto, grupo_cliente)
                        VALUES
                            ({$login_fabrica}, {$posto}, {$grupo_clientes});
                    ";
                    $resInsGrupCli = pg_query($con, $sqlInsGrupCli);
                }
            }
        }

     /*==============Cidades Atendidas 781457  ================*/

    if (isset($_POST['btn_acao']) && $posto > 0) {

            
           if ($login_fabrica == 1) {                                        
                      $tipo_posto                 = $_POST['tipo_posto'];
                      $reembolso_peca_estoque     = trim($_POST['reembolso_peca_estoque']);
                          
                      $sqlPosto = "SELECT  parametros_adicionais ,tipo_posto , reembolso_peca_estoque FROM tbl_posto_fabrica WHERE posto = $posto AND fabrica=$login_fabrica";
                   
                      $res = pg_query($con, $sqlPosto);

                      $resultado  = pg_fetch_result($res,0,"tipo_posto");
                      $resultado2  = pg_fetch_result($res,0,"reembolso_peca_estoque");
                   

                    if (pg_num_rows($res) > 0) {
                            
                           $adicionais = json_decode(pg_fetch_result($res, 0, "parametros_adicionais"),1);

                           if ($tipo_posto != $resultado) {

                             $adicionais['tipo_posto_data_alteracao'] = date('Y-m-d H:i:s');
                           }

                           if ($reembolso_peca_estoque != $resultado2) {                                                               
                                $adicionais['reembolso_peca_estoque_data_alteracao'] = date('Y-m-d H:i:s');
                           }
                           $adicionais = json_encode($adicionais);

                           $updateParametrosAdicionais = "UPDATE tbl_posto_fabrica set parametros_adicionais ='$adicionais'
                                                                WHERE posto          = {$posto}
                                                                AND   fabrica        = {$login_fabrica}";
                           $res = pg_query($con,$updateParametrosAdicionais);
                    }
           }           



        if ($login_fabrica == 1 and empty($msg_erro) and strlen($posto) > 0 and $posto <> "6359") {
            include_once '../class/email/mailer/class.phpmailer.php';

            //$mailer = new PHPMailer();

            $sqlRP = "SELECT email FROM tbl_admin WHERE fabrica = $login_fabrica AND responsavel_postos IS TRUE";
            $resRP = pg_query($con,$sqlRP);

            if (pg_num_rows($resRP) > 0)
            {
                $emailRP = Array();
                for ($i = 0; $i < pg_num_rows($resRP); $i++)
                {
                    $emailRP[] = pg_fetch_result($resRP,$i,"email");
                }
            }
            /*$assunto   = "Alterações no cadastro do Posto";
            $mensagem  = "Houve alteração no cadastro do posto $codigo_posto - $nome";
            $mailer->IsSMTP();
            $mailer->IsHTML();
            foreach ($emailRP as $mail){
                $mailer->AddAddress($mail);
            }
            $mailer->Subject = $assunto;
            $mailer->Body = $mensagem;
            if (!$mailer->Send()){
                $new_email  = implode(",", $emailRP);
                $cabecalho  = "MIME-Version: 1.0 \r";
                $cabecalho .= "Content-type: text/html; charset=iso-8859-1 \r";
                $cabecalho .= "From: helpdesk@telecontrol.com.br";
                mail($new_email, utf8_encode($assunto), utf8_encode($mensagem), $cabecalho);
            }*/

            if ($posto_pre) {
                $assunto = " Aprovação do Pré-Cadastro do Posto $codigo_posto Pendente ";
                $mensagem = "O Pré-cadastro do posto $codigo_posto está aguardando aprovação";

                $sql_admin = "SELECT email from tbl_admin where fabrica = $login_fabrica and responsavel_ti is true and ativo is true ";
                $res_admin = pg_query($con, $sql_admin);
                if(pg_num_rows($res_admin)>0){
                    $email = pg_fetch_result($res_admin, 0, email);

					$mailTc = new TcComm($externalId);
					$res = $mailTc->sendMail(
						$email,
						$assunto,
						$mensagem,
						'no-reply@telecontrol.com.br'
					);
                }
            }
        }

    }

    if($login_fabrica == 151){
        /*=====================================*/

        /* Mondial - 151 */
        /* Enviar informações do posto via API | alteração e inserção */

        include_once "./os_cadastro_unico/fabricas/{$login_fabrica}/classes/Participante.php";

        $Participante = new Participante();

        /* Teste de inclusão do arquivo */
        // $Participante->run();

        $dados_posto = array();

        if(strlen($fone) > 0){
            $fone = str_replace(array("(", ")", " ", "-", "."), "", $fone);
            $ddd = substr($fone, 0, 2);
            $telefone = substr($fone, 2, strlen($fone) - 1);
        }else{
            $ddd = "";
            $telefone = "";
        }

        if(strlen($cep) > 0){
            $cep = str_replace(array(".", "-"), "", $cep);
        }

        $dados_posto["SdEntParticipante"] = array(
            "RelacionamentoCodigo"                  => "AssistTecnica", /* AssistTecnica - Assistência Técnica | ConsumidorFinal - Consumidor Final */
            "ParticipanteTipoPessoa"                => "J", /* F- Física | J - Jurídica | E - Estrangeira */
            "ParticipanteFilialCPFCNPJ"             => $xcnpj,
            "ParticipanteRazaoSocial"               => utf8_encode($nome),
            "ParticipanteFilialNomeFantasia"        => utf8_encode($nome_fantasia),
            // "ParticipanteFilialRegimeTributario"     => "", /* Microempresa | SimplesNacional | LucroPresumido | LucroReal */
            "ParticipanteStatus"                    => "A", /* A - Ativo | I - Inativo */

            /** Endereço **/
            "Enderecos"                             => array(
                array(
                    "ParticipanteFilialEnderecoSequencia"   => 1, /* Campo númerico */
                    "ParticipanteFilialEnderecoTipo"        => "Cobranca", /* Cobranca | Entrega */
                    "ParticipanteFilialEnderecoCep"         => $cep,
                    "ParticipanteFilialEnderecoLogradouro"  => utf8_encode($endereco),
                    "ParticipanteFilialEnderecoNumero"      => $numero,
                    "ParticipanteFilialEnderecoComplemento" => utf8_encode($complemento),
                    "ParticipanteFilialEnderecoBairro"      => utf8_encode($bairro),
                    "PaisCodigo"                            => 1058, /* 1058 - Brasil */
                    "PaisNome"                              => "Brasil",
                    "UnidadeFederativaCodigo"               => "",
                    "UnidadeFederativaNome"                 => utf8_encode($estado),
                    // "MunicipioCodigo"                        => "",
                    "MunicipioNome"                         => utf8_encode($cidade),
                    // "InscricaoEstadual"                  => "123456987",
                    "ParticipanteFilialEnderecoStatus"      => "A", /* A - Ativo | I - Inativo */
                    "InscricaoEstadual" => $ie
                )
            ),
            /** Contatos **/
            "Contatos"                              => array(
                array(
                    "ParticipanteFilialEnderecoContatoNome"         => utf8_encode($contato),
                    "ParticipanteFilialEnderecoContatoEmail"        => utf8_encode($email),
                    "ParticipanteFilialEnderecoContatoTelefoneDDI"  => 55, /* Default Brasil */
                    "ParticipanteFilialEnderecoContatoTelefoneDDD"  => $ddd,
                    "ParticipanteFilialEnderecoContatoTelefone"     => $telefone
                )
            )

        );

        $status_posto = $Participante->gravaParticipante($dados_posto);

        if(!is_bool($status_posto) && $$status_posto != true){
            $msg_erro .= $status_posto;
        }
    }

    if (in_array($login_fabrica, array(169, 170))) {
        // Usuários do chat
        $sql_posto_chat = "SELECT tbl_posto.posto,
                tbl_posto.nome,
                tbl_posto_fabrica.contato_email,
                tbl_posto_fabrica.codigo_posto,
                tbl_posto_fabrica.contato_estado,
                tbl_posto_fabrica.credenciamento,
                tbl_posto_fabrica.parametros_adicionais
            FROM tbl_posto_fabrica
            JOIN tbl_posto USING(posto)
            WHERE tbl_posto_fabrica.posto = $posto
            AND tbl_posto_fabrica.fabrica = $login_fabrica";
        $qry_posto_chat = pg_query($con, $sql_posto_chat);

        $posto_chat_externalId = pg_fetch_result($qry_posto_chat, 0, 'posto');
        $posto_chat_nome = pg_fetch_result($qry_posto_chat, 0, 'nome');
        $posto_chat_email = pg_fetch_result($qry_posto_chat, 0, 'contato_email');
        $posto_chat_estado = pg_fetch_result($qry_posto_chat, 0, 'contato_estado');
        $posto_chat_codigo_posto = pg_fetch_result($qry_posto_chat, 0, 'codigo_posto');
        $posto_chat_credenciamento = pg_fetch_result($qry_posto_chat, 0, 'credenciamento');
        $posto_chat_parametros_adicionais = json_decode(pg_fetch_result($qry_posto_chat, 0, 'parametros_adicionais'), true);

        if (null === $posto_chat_parametros_adicionais or !array_key_exists("atendimento_chat", $posto_chat_parametros_adicionais)) {
            $posto_chat_parametros_adicionais = array(
                "atendimento_chat" => "f"
            );
        }

        $headers = array(
            "Access-Application-Key" => "084f77e7ff357414d5fe4a25314886fa312b2cff",
            "Access-Env" => "PRODUCTION",
            "Content-Type" => "application/json",
        );

        $client = new Posvenda\Rest\Client(
            "http://api2.telecontrol.com.br/tcchat",
            $headers
        );

        $response = $client->get("/usuario/usuario/{$posto_chat_codigo_posto}/fabrica/{$login_fabrica}/active/f");
        $posto_chat_usuario = null;
        $posto_chat_ativo = null;

        if (!empty($response) and $response["status_code"] == 200) {
            $arr_response = json_decode($response["response"], true);
            $posto_chat_usuario = $arr_response["usuario"]["id"];
            $posto_chat_ativo = $arr_response["usuario"]["active"];
        }

        if (in_array($posto_chat_credenciamento, array("CREDENCIADO", "EM DESCREDENCIAMENTO")) and $posto_chat_parametros_adicionais["atendimento_chat"] == "t") {
            if (empty($posto_chat_usuario)) {
                $data = array(
                    "nome" => $posto_chat_codigo_posto,
                    "sobrenome" => $posto_chat_nome,
                    "usuario" => $posto_chat_codigo_posto,
                    "email" => $posto_chat_email,
                    "externalId" => $posto_chat_externalId,
                    "fabrica" => $login_fabrica,
                    "estado" => $posto_chat_estado,
                    "aplicacao" => "POSVENDAPOSTO",
                    "tipoUsuario" => "POSTO"
                );

                $client->setJson(true);
                $post = $client->post("/usuario", $data);

                if (empty($post) or $post["status_code"] <> 201) {
                    $msg_erro .= traduz("Erro ao cadastrar Atendimento CHAT");
                }
            } elseif (false === $posto_chat_ativo) {
                $uri = "/usuario/id/{$posto_chat_usuario}/fabrica/{$login_fabrica}";

                $data = array(
                    "active" => true
                );

                $client->setJson(true);
                $put = $client->put($uri, $data);

                if (empty($put) or $put["status_code"] <> 200) {
                    $msg_erro .= traduz("Erro ao cadastrar Atendimento CHAT");
                }
            }
        } elseif (!empty($posto_chat_usuario) and $posto_chat_parametros_adicionais["atendimento_chat"] == "f") {
            $uri = "/usuario/id/{$posto_chat_usuario}/fabrica/{$login_fabrica}";

            $data = array(
                "active" => false
            );

            $client->setJson(true);
            $put = $client->put($uri, $data);
        }
    }

    if (strlen ($msg_erro) == 0) {

		$res = pg_query ($con,"COMMIT TRANSACTION");

        if ( gestao_interna( $login_fabrica ) == true ) {
			if (!$cod_ibge_cidade) {
				$sql_cod_ibge = "SELECT cod_ibge FROM tbl_posto_fabrica WHERE posto = $posto AND fabrica = $login_fabrica";
				$res_cod_ibge = pg_query($con, $sql_cod_ibge);
				$cod_ibge_cidade = pg_fetch_result($res_cod_ibge, 0, 'cod_ibge');
			}
            $ret = sige_sync_pessoa();
        }

        // Anexo contratos B&D
        if($login_fabrica  == 1){

            $qtde_contratos = $_POST["qtde_contratos"];

            $tDocs = new TDocs($con, $login_fabrica);
    	    $tDocs->setContext("posto", "contrato");

            $info = $tDocs->getdocumentsByRef($posto, "posto", "contrato");

            $qtde_contratos_uploads = count($info->attachListInfo);

            if($qtde_contratos_uploads > 5){

                $msg_erro .= traduz('A quantidade de uploads de contratos não pode ser superior a 5!');

            }else{

                $envia_email_admin = false;

                for($c = 1; $c <= $qtde_contratos; $c++){

                    $contrato_file = $_FILES["contrato_{$c}"];

                    if($contrato_file["size"] > 0){

                        /* HD-3980490 Retirado o limite do anexo*/
                        $anexoID = $tDocs->uploadFileS3($contrato_file, $posto, false);

                        if (!$anexoID) {
                            $msg_erro["msg"][] = traduz('Erro ao salvar o contato!');
                            break;
                        }else{
                            $envia_email_admin = true;
                        }
                    }
                }
            }
        }


        if (strlen($loja_b2b_tabela) > 0) {

            $retornoTabela = $obTabelaLoja->relacionaClienteTabela($loja_b2b_tabela, $posto, $login_admin);
            if ($retornoTabela["erro"] == true) {
                $msg_erro .= $retornoTabela["msn"]."<br />";
            }

        }
		/**
		 * Imbera - 158
		 * Gravação do Técnico no eProdutiva
		 *
		 * @author Ronald Santos 01/11/2016
		 *
		 */
		if($login_fabrica == 158){
			if ($tipo_posto_tecnico_proprio == "t") {
				try {
					$cockpit = new Cockpit($login_fabrica);
					if (empty($tecnico_codigo_externo)) {
						$tecnico_codigo_externo = $cockpit->gravaTecnicoMobile($nome, $nome_fantasia, $cnpj, $ie, $email, $codigo_posto);

						$sql = "UPDATE tbl_tecnico SET codigo_externo = '{$tecnico_codigo_externo}' WHERE fabrica = {$login_fabrica} AND posto =       {$posto}";
						$res = pg_query($con, $sql);

						if (strlen(pg_last_error()) > 0) {
							throw new Exception(traduz("Erro ao gravar código externo do técnico"));
						}
					} else {
						$cockpit->atualizaTecnicoMobile($tecnico_codigo_externo, $nome, $nome_fantasia, $cnpj, $ie, $email, $codigo_posto);
					}
				} catch (Exception $e) {
					$msg_erro .= $e->getMessage()."<br />";
				}
			}
		}

            /**
             * Bosch - 20
             * Criação de execução da rotina de criação do arquivo excel de atualização de postos.
             * Após criado, irá ser enviado por email para:
             * Warranty.EWQAS@de.bosch.com ; suporte@telecontrol.com.br
             *
             * @author Gabriel Silveira - 19/09/2012
             *
             */


            if ($login_fabrica == 20 and $novo_posto) {

                include_once '../class/email/mailer/class.phpmailer.php';
                /**
                 * instancia a classe PHPMailer no objeto $mailer
                 */
                $mailer = new PHPMailer();

                $cadastro = "novo";
                $status = "C";

                $comando = "php /www/assist/www/rotinas/bosch/atualizacao-posto.php $novo_posto $status $cadastro";

                #$comando = "php /home/monteiro/public_html/posvenda/rotinas/bosch/atualizacao-posto.php $novo_posto $status $cadastro";
                $link_arquivo = system($comando);

                if($error_code === 0) {
                    $link_arquivo = $resposta[0];

                    if ($link_arquivo == traduz('Sem resultados')) {
                        $msg = $link_arquivo;
                        unset($link_arquivo);
                    }

                }else{
                    #$msg_erro = 'Erro ao processar o arquivo de atualização de posto. Tente novamente.';
                }

            }

            //HD 732838 - Salva os relacionamentos de posto x area de atuacao.. originalmente só para a latina
            
            if (in_array($login_fabrica, array(35,167,203))) {
            //if ($login_fabrica == 35) { /*HD - 4203773*/
                $aux_sql = "SELECT parametros_adicionais FROM tbl_posto_fabrica WHERE fabrica = $login_fabrica AND posto = $posto LIMIT 1";
                $aux_res = pg_query($con, $aux_sql);

                $aux_par_ad = (array) json_decode(pg_fetch_result($aux_res, 0, 'parametros_adicionais'));

                if($login_fabrica == 35){
                    if (!empty($_POST["obrigado_anexar_nf"])) {
                        $aux_par_ad["anexar_nf_os"] = "nao";
                    } else {
                        $aux_par_ad["anexar_nf_os"] = "";
                    }
                }

                if(in_array($login_fabrica, [167, 203])){
                    $responsaveis_cobranca = array("nome_responsavel" => $nome_responsavel, "cpf_responsavel" => $cpf_responsavel, "rg_responsavel" => $rg_responsavel);            
                    $responsaveis_cobranca = json_encode($responsaveis_cobranca);
                    $aux_sql = "UPDATE tbl_posto_fabrica SET parametros_adicionais = '{$responsaveis_cobranca}' WHERE fabrica = {$login_fabrica} AND posto = {$posto}";
                    $aux_res = pg_query($con, $aux_sql);                   
                }
            }
            
            if ($cep != $cep_anterior) {
                $rotina_lat_lon = "php ".__DIR__."/../rotinas/telecontrol/latitude_longitude.php $login_fabrica $posto";
                system($rotina_lat_lon); 
            }

            header ("Location: $PHP_SELF?posto=$posto&msg=Gravado com sucesso");
            exit;
        }else{
            $res = pg_query ($con,"ROLLBACK TRANSACTION");
        }
    }//fim if msg_erro 

}

// Imagens do Posto Autorizado
if($_POST['ajax'] == 'excluir'){
    $imagem = $_POST['imagem'];
    if(file_exists($imagem)) {
        unlink($imagem);
    }
    exit(traduz('Imagem excluída'));
}

// Imagens do Posto Autorizado
if ($_POST['gravarimagem']) {
    $posto = $_POST['posto_imagem'];
    if(is_array($_POST['exclui'])){
        foreach($_POST['exclui'] as $apagar){
            unlink($apagar);
        }
    }

    $caminho_imagem = '../autocredenciamento/fotos/';
    $caminho_path   = '../autocredenciamento/fotos';
    //$debug = ($_COOKIE['debug'][0] == 't');
    $msg_erro = array();

    $nome_foto__cnpj = preg_replace('/\D/','',utf8_decode($cnpj_imagem));

    $config["tamanho"] = 2*1024*1024;

    if(count($msg_erro) == 0){
        for($i = 1; $i < 4; $i++){

            if ($_FILES["arquivo$i"]['name']=='') continue; //  Próxima iteração se não há arquivo definido

            $arquivo    = $_FILES["arquivo$i"];

            // if ($debug) {echo "<p>Imagem para o posto $posto, Erros: ".count($msg_erro)."<br><pre>".var_dump($arquivo)."</pre></p>";}

            // Formulário postado... executa as ações
            if (strlen($arquivo["tmp_name"]) > 0 && $arquivo["tmp_name"] != "none"){

                // Verifica o MIME-TYPE do arquivo
                if (!preg_match("/\/(pjpeg|jpeg|png|gif|bmp)$/", $arquivo["type"])){
                    $msg_erro[] = traduz("Arquivo em formato inválido!");
                }

                // Verifica tamanho do arquivo
                if ($arquivo["size"] > $config["tamanho"])
                    $msg_erro[] = traduz("Arquivo em tamanho muito grande! Deve ser de no máximo 2MB. Envie outro arquivo.");

                if (count($msg_erro) == 0) {

                    // Pega extensão do arquivo
                    preg_match("/\.(gif|bmp|png|jpg|jpeg){1}$/i", $arquivo["name"], $ext);
                    $aux_extensao = "." . $ext[1];
                    $aux_extensao = strtolower($aux_extensao);

                    // Gera um nome único para a imagem
                    $nome_anexo = $nome_foto__cnpj . "_" .$i . $aux_extensao;

    //                     if ($debug) echo "<p>Imagem $i gravada como $nome_anexo...</p>";

                    // Exclui anteriores, qualque extensao
                    #@unlink($nome_foto__cnpj . "_" .$i);
                    array_map('unlink',glob($caminho_imagem.$nome_foto__cnpj . "_" .$i.".*"));

                    // Faz o upload da imagem
                    if (count($msg_erro) == 0) {
                        $thumbail = new resize( "arquivo$i", 600, 400 );
                        $thumbail -> saveTo($nome_anexo,$caminho_imagem);
                    }

                }
            }
        }
    }
    if(empty($msg_erro)) {
        header ("Location: $PHP_SELF?posto=$posto&msg=Gravado com sucesso");
        exit;
    }
}

if ($_POST['anexa_contrato']) {
    $contrato = $_FILES['contrato'];
    $posto_contrato = $_POST['posto_contrato'];
    $ext = explode('/',$contrato['type']);

    list($nome_doc, $tipo_doc) = explode(".", $contrato["name"]);

    if(!in_array(strtolower($tipo_doc), array("pdf", "doc", "zip", "rar"))){
        $msg_erro_contrato = traduz("Tipo de arquivo inválido. Por favor enviar arquivo PDF");
    }else{
        if ($arquivo["size"] > 2048000){
            $msg_erro_contrato = traduz("Arquivo tem tamanho muito grande! Deve ser de no máximo 2MB. Envie outro arquivo.");
        }else{

            if($fabricaEnviaContrato || in_array($login_fabrica, array(147, 160)) || $replica_einhell || $telecontrol_distrib){

                $type = $ext[1];

                $nome_anexo = "contrato_posto_{$login_fabrica}_{$posto_contrato}";

                $amazonTC->upload($nome_anexo, $contrato, "", "");
                $link = $amazonTC->getLink("$nome_anexo.{$type}", false, "", "");

            }else{

                $nome_anexo = "anexos/contrato_$posto_contrato.pdf";

                if(!move_uploaded_file($contrato['tmp_name'], $nome_anexo)){
                    $msg_erro_contrato = traduz("Falha ao anexar arquivo");
                }else{
                    $msg = traduz("Contrato anexado com sucesso");
                }
            }

        }
    }

}

#-------------------- Pesquisa Posto -----------------
if (strlen($_GET['posto']) > 0)  $posto = trim($_GET['posto']);
if (strlen($_POST['posto']) > 0) $posto = trim($_POST['posto']);

if (strlen($posto) > 0 && strlen ($msg_erro) == 0) {

    if ($login_fabrica == 158) {
        $colunasTecnico = "
            , tbl_tecnico.qtde_atendimento,
            tbl_tecnico.inicio_trabalho,
            tbl_tecnico.fim_trabalho
        ";
        $leftJoinTecnico = "
            LEFT JOIN tbl_tecnico ON tbl_tecnico.posto = tbl_posto_fabrica.posto AND tbl_tecnico.fabrica = {$login_fabrica}
        ";
    }

    $sql = "SELECT  tbl_posto_fabrica.posto       ,
            tbl_posto_fabrica.credenciamento      ,
            tbl_posto_fabrica.codigo_posto        ,
            tbl_posto_fabrica.posto_empresa       ,
            tbl_posto_fabrica.tipo_posto          ,
            tbl_posto_fabrica.transportadora_nome ,
            tbl_posto_fabrica.transportadora      ,
            tbl_posto_fabrica.cobranca_endereco   ,
            tbl_posto_fabrica.cobranca_numero     ,
            tbl_posto_fabrica.cobranca_complemento,
            tbl_posto_fabrica.cobranca_bairro     ,
            tbl_posto_fabrica.cobranca_cep        ,
            tbl_posto_fabrica.cobranca_cidade     ,
            tbl_posto_fabrica.cobranca_estado     ,
            tbl_posto_fabrica.obs                 ,
            tbl_posto_fabrica.banco               ,
            tbl_posto_fabrica.agencia             ,
            tbl_posto_fabrica.conta               ,
            tbl_posto_fabrica.filial              ,
            tbl_posto_fabrica.nomebanco           ,
            tbl_posto_fabrica.favorecido_conta    ,
            tbl_posto_fabrica.conta_operacao      ,
            tbl_posto_fabrica.cpf_conta           ,
            tbl_posto_fabrica.atendimento         ,
            tbl_posto_fabrica.tipo_conta          ,
            tbl_posto_fabrica.obs_conta           ,
            tbl_posto_fabrica.acrescimo_tributario,
            tbl_posto.nome                        ,
            tbl_posto.cnpj                        ,
            tbl_posto.ie                          ,
            tbl_posto.im                          ,
            tbl_posto_fabrica.contato_endereco         AS endereco,
            tbl_posto_fabrica.contato_numero           AS numero,
            tbl_posto_fabrica.contato_complemento      AS complemento,
            tbl_posto_fabrica.contato_bairro           AS bairro,
            tbl_posto_fabrica.contato_cep              AS cep,
            tbl_posto_fabrica.contato_cidade           AS cidade,
            tbl_posto_fabrica.contato_estado           AS estado,
            tbl_posto_fabrica.contato_email            AS email,
            tbl_posto_fabrica.contato_fone_comercial   AS fone,
            tbl_posto_fabrica.contato_fone_residencial AS fone2,
            tbl_posto_fabrica.contato_cel              AS fone3,
            tbl_posto_fabrica.contato_fax              AS fax,
            tbl_posto_fabrica.contato_nome             AS contato_nome,
            tbl_posto_fabrica.contato_atendentes       AS responsavel_social,
            /* HD 52864 19/11/2008
            tbl_posto.fone                        ,
            tbl_posto.fax                         ,*/
            tbl_posto.suframa                     ,
            tbl_posto.contato                     ,
            tbl_posto.capital_interior            ,
            tbl_posto_fabrica.nome_fantasia       ,
            tbl_posto.pais                        ,
            tbl_posto_fabrica.item_aparencia      ,
            tbl_posto_fabrica.senha               ,
            tbl_posto_fabrica.desconto            ,
            CASE WHEN tbl_posto_fabrica.fabrica <> 74 AND tbl_posto_fabrica.valor_km = 0     THEN tbl_fabrica.valor_km
                 WHEN tbl_posto_fabrica.fabrica =  74 AND tbl_posto_fabrica.valor_km is null THEN tbl_fabrica.valor_km
            ELSE tbl_posto_fabrica.valor_km END as valor_km  ,
            tbl_posto_fabrica.desconto_acessorio  ,
            tbl_posto_fabrica.custo_administrativo,
            tbl_posto_fabrica.imposto_al          ,
            tbl_posto_fabrica.pedido_em_garantia  ,
            tbl_posto_fabrica.reembolso_peca_estoque,
            tbl_posto_fabrica.coleta_peca         ,
            tbl_posto_fabrica.pedido_faturado     ,
            tbl_posto_fabrica.tipo_atende     ,
            tbl_posto_fabrica.digita_os           ,
            tbl_posto_fabrica.controla_estoque    ,
            tbl_posto_fabrica.prestacao_servico   ,
            tbl_posto_fabrica.prestacao_servico_sem_mo,
            tbl_posto_fabrica.atende_comgas       ,
            tbl_posto_fabrica.pedido_bonificacao  ,
            tbl_posto_fabrica.senha_financeiro            ,
            tbl_posto.senha_tabela_preco          ,
            tbl_posto_fabrica.admin               ,
            TO_CHAR(tbl_posto_fabrica.data_alteracao,'DD/MM/YYYY') AS data_alteracao,
            tbl_posto_fabrica.pedido_via_distribuidor,
            tbl_posto_fabrica.garantia_antecipada,
            tbl_posto_fabrica.escritorio_regional,
            tbl_posto_fabrica.imprime_os         ,
            TO_CHAR(tbl_posto_fabrica.data_nomeacao,'DD/MM/YYYY') AS data_nomeacao,
            tbl_posto_fabrica.qtde_os_item,
            tbl_posto_fabrica.escolhe_condicao,
            tbl_posto_fabrica.condicao_escolhida,
            tbl_posto_fabrica.atende_consumidor,
            tbl_posto_fabrica.admin_sap,
            tbl_posto_fabrica.divulgar_consumidor,
            tbl_posto_fabrica.centro_custo,
            tbl_posto_fabrica.conta_contabil,
            tbl_posto_fabrica.local_entrega,
            tbl_posto_fabrica.contribuinte_icms,
            tbl_posto_fabrica.controle_estoque_novo,
            tbl_posto_fabrica.controle_estoque_manual,
            tbl_posto_fabrica.contato_telefones,
            tbl_posto_fabrica.entrega_tecnica,
            tbl_posto_fabrica.categoria,
            tbl_posto_fabrica.credito,
            tbl_posto_fabrica.latitude,
            tbl_posto_fabrica.longitude,
            tbl_posto_fabrica.tipo_atende,
            tbl_excecao_mobra.tx_administrativa,
            tbl_posto_fabrica.permite_envio_produto,
            tbl_posto_fabrica.parametros_adicionais
            {$colunasTecnico}
            " . (($login_fabrica == 3) ? ", tbl_posto_fabrica.admin_sap_especifico" : "") . "
        FROM      tbl_posto
        LEFT JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
        JOIN tbl_fabrica ON tbl_fabrica.fabrica = tbl_posto_fabrica.fabrica AND tbl_fabrica.fabrica = $login_fabrica
        LEFT JOIN tbl_excecao_mobra ON tbl_posto.posto = tbl_excecao_mobra.posto AND tbl_excecao_mobra.fabrica = $login_fabrica AND tbl_excecao_mobra.tx_administrativa notnull
        {$leftJoinTecnico}
        WHERE     tbl_posto_fabrica.fabrica = $login_fabrica
        AND       tbl_posto_fabrica.posto   = $posto ";
    $res = pg_query ($con,$sql);

    if (pg_num_rows ($res) > 0) {
        $posto          = trim(pg_fetch_result($res,0,'posto'));
        $credenciamento = trim(pg_fetch_result($res,0,'credenciamento'));
        $codigo         = trim(pg_fetch_result($res,0,'codigo_posto'));
        $nome           = trim(pg_fetch_result($res,0,'nome'));
        $nome_anterior  = trim(pg_fetch_result($res,0,'nome'));
        $cnpj           = trim(pg_fetch_result($res,0,'cnpj'));
        $ie             = trim(pg_fetch_result($res,0,'ie'));
        $ie_anterior    = trim(pg_fetch_result($res,0,'ie'));
        $im             = trim(pg_fetch_result($res,0,'im'));
        $endereco       = trim(pg_fetch_result($res,0,'endereco'));
        $endereco       = str_replace("\"","",$endereco);
        $numero         = trim(pg_fetch_result($res,0,'numero'));
        $complemento    = trim(pg_fetch_result($res,0,'complemento'));
        $bairro         = trim(pg_fetch_result($res,0,'bairro'));
        $cep            = trim(pg_fetch_result($res,0,'cep'));
        $cep_anterior   = trim(pg_fetch_result($res,0,'cep'));
        $cidade         = trim(pg_fetch_result($res,0,'cidade'));
		$cidade = mb_detect_encoding($cidade, 'UTF-8', true) ? utf8_decode($cidade) : $cidade;
		$cidade = str_replace (array('"',"'","/","\\"),array('','','',''),$cidade);
        $cidade_anterior = trim(pg_fetch_result($res,0,'cidade'));
        $estado         = trim(pg_fetch_result($res,0,'estado'));
        $estado_anterior = trim(pg_fetch_result($res,0,'estado'));
        $email          = trim(pg_fetch_result($res,0,'email'));
        $fone           = trim(pg_fetch_result($res,0,'fone'));
        $fone2          = trim(pg_fetch_result($res,0,'fone2'));
        $fone3          = trim(pg_fetch_result($res,0,'fone3'));

        if (in_array($login_fabrica, array(15, 74,151))) {
            $contato_telefones = trim(pg_fetch_result($res,0,'contato_telefones'));

            if ($login_fabrica <> 151) {
                unset($fone);
            }
        }

        // Busca o cod_ibge da cidade
        if (!empty($cidade)) {
            $sql_ibge = "SELECT cod_ibge FROM tbl_ibge WHERE UPPER(fn_retira_especiais(cidade)) = UPPER(fn_retira_especiais('{$cidade}')) AND UPPER(estado) = UPPER('{$estado}')";
            $res_ibge = pg_query($con, $sql_ibge);
            if (pg_num_rows($res_ibge) > 0) {
                $addressIbge = pg_fetch_result($res_ibge, 0, cod_ibge);
            }

        }

        if ($login_fabrica == 158) {
            $qtde_atendimento = pg_fetch_result($res, 0, "qtde_atendimento");
            $inicio_trabalho  = pg_fetch_result($res, 0, "inicio_trabalho");
            $fim_trabalho     = pg_fetch_result($res, 0, "fim_trabalho");
        }

        // formata CNPJ/CPF
        if (!in_array($login_fabrica, [158,183])) {
            if (strlen($cnpj) == 14 and is_numeric($cnpj))
                $cnpj = preg_replace('/^(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})$/', '$1.$2.$3/$4-$5', $cnpj);
            if (strlen($cnpj) == 11 and is_numeric($cnpj))
                $cnpj = preg_replace('/^(\d{3})(\d{3})(\d{3})(\d{2})$/', '$1.$2.$3-$4', $cnpj);
        }

        if (in_array($login_fabrica, array(15,35,74,151))) {
            $contato_cel = trim(pg_fetch_result($res,0,'fone3'));

            $chars_replace = array('{','}','"');
            $contato_telefones = str_replace($chars_replace, "", trim(pg_fetch_result($res,0,'contato_telefones')));

            $fones_latina = array();
            $fones_latina = explode(',', $contato_telefones);

            if(strlen($fone)==0 and strlen($fones_latina[0])>0 ){
                $fone  = $fones_latina[0];
            }
            $fone2 = $fones_latina[1];
            $fone3 = $fones_latina[2];

        }
        if($login_fabrica == 151 && $fone == null){
            $fone = trim(pg_fetch_result($res,0,'fone'));
        }

        $fax                      = trim(pg_fetch_result($res,0, 'fax'));
        $contato                  = trim(pg_fetch_result($res,0, 'contato'));
        $suframa                  = trim(pg_fetch_result($res,0, 'suframa'));
        $item_aparencia           = trim(pg_fetch_result($res,0, 'item_aparencia'));
        $obs                      = trim(pg_fetch_result($res,0, 'obs'));
        $capital_interior         = trim(pg_fetch_result($res,0, 'capital_interior'));
        $posto_empresa            = trim(pg_fetch_result($res,0, 'posto_empresa'));
        $tipo_posto               = trim(pg_fetch_result($res,0, 'tipo_posto'));
        $senha                    = trim(pg_fetch_result($res,0, 'senha'));
        $pais_cadastro            = trim(pg_fetch_result($res,0, 'pais'));
        $desconto                 = trim(pg_fetch_result($res,0, 'desconto'));
        $valor_km                 = trim(pg_fetch_result($res,0, 'valor_km'));
        $desconto_acessorio       = trim(pg_fetch_result($res,0, 'desconto_acessorio'));
        $custo_administrativo     = trim(pg_fetch_result($res,0, 'custo_administrativo'));
        $imposto_al               = trim(pg_fetch_result($res,0, 'imposto_al'));
        $nome_fantasia            = trim(pg_fetch_result($res,0, 'nome_fantasia'));
        $transportadora           = trim(pg_fetch_result($res,0, 'transportadora'));
        $escritorio_regional      = trim(pg_fetch_result($res,0, 'escritorio_regional'));
        $posto_filial             = trim(pg_fetch_result($res,0, 'filial')); #HD 401553
        $acrescimo_tributario     = trim(pg_fetch_result($res,0, 'acrescimo_tributario'));
        $cobranca_endereco        = trim(pg_fetch_result($res,0, 'cobranca_endereco'));
        $cobranca_numero          = trim(pg_fetch_result($res,0, 'cobranca_numero'));
        $cobranca_complemento     = trim(pg_fetch_result($res,0, 'cobranca_complemento'));
        $cobranca_bairro          = trim(pg_fetch_result($res,0, 'cobranca_bairro'));
        $cobranca_cep             = trim(pg_fetch_result($res,0, 'cobranca_cep'));
        $cobranca_cidade          = trim(pg_fetch_result($res,0, 'cobranca_cidade'));
        $cobranca_estado          = trim(pg_fetch_result($res,0, 'cobranca_estado'));
        $pedido_em_garantia       = trim(pg_fetch_result($res,0, 'pedido_em_garantia'));
        $reembolso_peca_estoque   = trim(pg_fetch_result($res,0, 'reembolso_peca_estoque'));
        $coleta_peca              = trim(pg_fetch_result($res,0, 'coleta_peca'));
        $pedido_faturado          = trim(pg_fetch_result($res,0, 'pedido_faturado'));
        $tipo_atende              = trim(pg_fetch_result($res,0, 'tipo_atende'));
        $digita_os                = trim(pg_fetch_result($res,0, 'digita_os'));
        $controla_estoque         = trim(pg_fetch_result($res,0, 'controla_estoque'));
        $controle_estoque_novo    = trim(pg_fetch_result($res,0, 'controle_estoque_novo'));
        $controle_estoque_manual  = trim(pg_fetch_result($res,0, 'controle_estoque_manual'));
        $prestacao_servico        = trim(pg_fetch_result($res,0, 'prestacao_servico'));
        $prestacao_servico_sem_mo = trim(pg_fetch_result($res,0, 'prestacao_servico_sem_mo'));
        $pedido_bonificacao       = trim(pg_fetch_result($res,0,'pedido_bonificacao'));
        $banco                    = trim(pg_fetch_result($res,0, 'banco'));
        $agencia                  = trim(pg_fetch_result($res,0, 'agencia'));
        $conta                    = trim(pg_fetch_result($res,0, 'conta'));
        $nomebanco                = trim(pg_fetch_result($res,0, 'nomebanco'));
        $favorecido_conta         = trim(pg_fetch_result($res,0, 'favorecido_conta'));
        $conta_operacao           = trim(pg_fetch_result($res,0, 'conta_operacao'));//HD 8190 5/12/2007 Gustavo
        $cpf_conta                = trim(pg_fetch_result($res,0, 'cpf_conta'));
        $tipo_conta               = trim(pg_fetch_result($res,0, 'tipo_conta'));
        $obs_conta                = trim(pg_fetch_result($res,0, 'obs_conta'));
        $senha_financeiro         = trim(pg_fetch_result($res,0, 'senha_financeiro'));
        $senha_tabela_preco       = trim(pg_fetch_result($res,0, 'senha_tabela_preco'));
        $pedido_via_distribuidor  = trim(pg_fetch_result($res,0, 'pedido_via_distribuidor'));
        $atende_comgas            = trim(pg_fetch_result($res,0, 'atende_comgas'));
        $atendimento_lenoxx       = trim(pg_fetch_result($res,0, 'atendimento'));//HD 110541
        $divulgar_consumidor      = pg_fetch_result($res,0, 'divulgar_consumidor');
        $tipo_atende              = pg_fetch_result($res,0, 'tipo_atende');
        $atendimento              = $atendimento_lenoxx;
        $contato_nome             = trim(pg_fetch_result($res,0, 'contato_nome'));//HD 110541
        $responsavel_social       = trim(pg_fetch_result($res,0, 'responsavel_social'));
        $credito             = trim(pg_fetch_result($res,0, 'credito'));//HD 110541
        $admin                    = trim(pg_fetch_result($res,0, 'admin'));
        $data_alteracao           = trim(pg_fetch_result($res,0, 'data_alteracao'));
        $garantia_antecipada      = trim(pg_fetch_result($res,0, 'garantia_antecipada'));
        $imprime_os               = pg_fetch_result($res,0, 'imprime_os'); // HD12104
        $qtde_os_item             = pg_fetch_result($res,0, 'qtde_os_item'); // HD 17601
        $escolhe_condicao         = pg_fetch_result($res,0, 'escolhe_condicao');
        $condicao_escolhida       = pg_fetch_result($res,0, 'condicao_escolhida');
        $atende_consumidor        = pg_fetch_result($res,0, 'atende_consumidor'); // HD 126810 -    Adicionado campo 'atende_consumidor'
        $data_nomeacao            = pg_fetch_result($res,0, 'data_nomeacao'); // hd 21496 - Francisco - campo Data da Nomeação para Dynacom
        $admin_sap                = pg_fetch_result($res,0,'admin_sap'); // ! HD 121248 (augusto) - Buscar atendente de posto cadastrado para este posto

        $parametros_adicionais = pg_fetch_result($res, 0, parametros_adicionais);
        $parametros_adicionais = json_decode($parametros_adicionais, true);
	extract($parametros_adicionais);
        
        if (isset($parametros_adicionais['gera_pedido']) && !empty($parametros_adicionais['gera_pedido'])) {
            $gera_pedido = $parametros_adicionais['gera_pedido'];
        }

        if(isset($parametros_adicionais['frete']) && !empty($parametros_adicionais['frete'])){
            $tipo_frete = $parametros_adicionais['frete'];
        }

        if (in_array($login_fabrica, [169,170])) {
            $mo_triagem            = $parametros_adicionais["mo_triagem"];
            $digita_os_revenda = $parametros_adicionais["digita_os_revenda"];
        }

        if (in_array($login_fabrica, array(184,200))) {
            $garantia_com_deslocamento = $parametros_adicionais['garantia_com_deslocamento'];
        }

        if ($login_fabrica == 115 && isset($parametros_adicionais['categoria_manual'])) {
            $categoria_manual = $parametros_adicionais['categoria_manual'];
        }

        if ($login_fabrica == 189) {
            $tipo_cliente = $parametros_adicionais['tipo_cliente'];
            $codigo_representante = $parametros_adicionais['codigo_representante'];
        }
        if ($login_fabrica == 171){
            $codigo_fn = pg_fetch_result($res, 0, 'conta_contabil');
        }

        if ($login_fabrica == 3) {
            $admin_sap_especifico = pg_fetch_result($res, 0, "admin_sap_especifico");
        }

        $estado_txt             = $parametros_adicionais["estado"];
        $cobranca_estado_txt    = $parametros_adicionais["cobranca_estado"];

        $retorno = (count($array_estados) > 0) ? $array_estados : ["error" => "true"];

        $usa_cidade_estado_txt = count($array_estados($pais_cadastro)) == 0 ? "t" : "f";

        # HD 110541

        $centro_custo             = trim(pg_fetch_result($res,0, 'centro_custo'));//HD 356653
        $conta_contabil           = trim(pg_fetch_result($res,0, 'conta_contabil'));//HD 356653
        $local_entrega            = trim(pg_fetch_result($res,0, 'local_entrega'));//HD 356653
        $contribuinte_icms        = pg_fetch_result($res,0, 'contribuinte_icms');
        $categoria_posto = pg_fetch_result($res,0,'categoria');

        if ($login_fabrica == 42) {
            $entrega_tecnica = pg_fetch_result($res, 0, "entrega_tecnica");
        }

        if ($login_fabrica == 1) {
            $taxa_administrativa = pg_fetch_result($res,0,'tx_administrativa');
            $taxa_administrativa = (strlen(trim($taxa_administrativa)) == 0) ? 1.1 : $taxa_administrativa;
        }


        if (in_array($login_fabrica, array(169,170))) {
            $e_ticket = pg_fetch_result($res, 0, "permite_envio_produto");
            $parametros_adicionais = pg_fetch_result($res, 0, "parametros_adicionais");

            if (!empty($parametros_adicionais)) {
                $parametros_adicionais = json_decode($parametros_adicionais, true);
                $qtde_atendimento = $parametros_adicionais['qtde_atendimento'];
                $matriz = $parametros_adicionais['matriz'];
                $abre_os_dealer = $parametros_adicionais['abre_os_dealer'];
                $codigo_cliente = $parametros_adicionais['codigo_cliente'];
            }
        }


        if (in_array($login_fabrica, array(169,170,190))) {
            $parametros_adicionais = pg_fetch_result($res, 0, "parametros_adicionais");

            if (!empty($parametros_adicionais)) {
                $parametros_adicionais = json_decode($parametros_adicionais, true);
                $qtde_atendimento = $parametros_adicionais['qtde_atendimento'];
		        $codigo_cliente = $parametros_adicionais['codigo_cliente'];

                $escritorio_venda_ad = $parametros_adicionais['escritorio_venda'];
                $equipe_venda_ad = $parametros_adicionais['equipe_venda'];
                $horario_funcionamento_inicio = $parametros_adicionais['inicio_horario_funcionamento'];
                $horario_funcionamento_fim    = $parametros_adicionais['fim_horario_funcionamento'];
            }
        }

        if ($login_fabrica == 183){
            $sql_tp = "SELECT codigo FROM tbl_tipo_posto WHERE fabrica = {$login_fabrica} AND tipo_posto = {$tipo_posto}";
            $res_tp = pg_query($con, $sql_tp);

            if (pg_num_rows($res_tp) > 0){
                $codigo_tipo_posto = pg_fetch_result($res_tp, 0, "codigo");
            }
        }

        if($login_fabrica==11 or $login_fabrica == 172){
            $permite_envio_produto = pg_fetch_result($res, 0, "permite_envio_produto");

            $sql_X = "SELECT TO_CHAR(tbl_credenciamento.data,'DD/MM/YYYY') AS dataa
                        FROM tbl_credenciamento
                       WHERE fabrica = $login_fabrica
                         AND posto   = $posto
                    ORDER BY data DESC
                       LIMIT 1";
                $res_X = pg_query ($con,$sql_X);
                if (pg_num_rows ($res_X) > 0) {
                        $data_credenciamento   = trim(pg_fetch_result($res_X,0,'dataa'));
                }
        }

        if ($login_fabrica == 158) {
            $latitude =pg_fetch_result($res,0,latitude);
            $longitude =pg_fetch_result($res,0,longitude);
        }
    } else {
        $sql = "SELECT  tbl_posto.nome                        ,
                        tbl_posto.cnpj                        ,
                        tbl_posto.ie                          ,
                        tbl_posto.im                          ,
                        tbl_posto.endereco                    ,
                        tbl_posto.numero                      ,
                        tbl_posto.complemento                 ,
                        tbl_posto.bairro                      ,
                        tbl_posto.cep                         ,
                        tbl_posto.cidade                      ,
                        tbl_posto.estado                      ,
                        tbl_posto.email                       ,
                        tbl_posto.fone                        ,
                        tbl_posto.fax                         ,
                        tbl_posto.contato                     ,
                        tbl_posto.suframa                     ,
                        tbl_posto.capital_interior            ,
                        tbl_posto.senha_financeiro            ,
                        tbl_posto.senha_tabela_preco          ,
                        tbl_posto.pais                        ,
                        tbl_posto.nome_fantasia
                FROM    tbl_posto
                WHERE   tbl_posto.posto   = $posto ";
        $res = pg_query ($con,$sql);

        if (pg_num_rows ($res) > 0) {
            $nome               = trim(pg_fetch_result($res,0, 'nome'));
            $cnpj               = trim(pg_fetch_result($res,0, 'cnpj'));
            $ie                 = trim(pg_fetch_result($res,0, 'ie'));
            $im                 = trim(pg_fetch_result($res,0, 'im'));
            $endereco           = trim(pg_fetch_result($res,0, 'endereco'));
            $endereco           = str_replace("\"","",$endereco);
            $numero             = trim(pg_fetch_result($res,0, 'numero'));
            $complemento        = trim(pg_fetch_result($res,0, 'complemento'));
            $bairro             = trim(pg_fetch_result($res,0, 'bairro'));
            $cep                = trim(pg_fetch_result($res,0, 'cep'));
            $cidade             = trim(pg_fetch_result($res,0, 'cidade'));
            $estado             = trim(pg_fetch_result($res,0, 'estado'));
            $email              = trim(pg_fetch_result($res,0, 'email'));
            $fone               = trim(pg_fetch_result($res,0, 'fone'));
            $fax                = trim(pg_fetch_result($res,0, 'fax'));
            $contato            = trim(pg_fetch_result($res,0, 'contato'));
            $suframa            = trim(pg_fetch_result($res,0, 'suframa'));
            $capital_interior   = trim(pg_fetch_result($res,0, 'capital_interior'));
            $senha_financeiro   = trim(pg_fetch_result($res,0, 'senha_financeiro'));
            $senha_tabela_preco = trim(pg_fetch_result($res,0, 'senha_tabela_preco'));
            $nome_fantasia      = trim(pg_fetch_result($res,0, 'nome_fantasia'));
            $pais_cadastro      = trim(pg_fetch_result($res,0, 'pais'));

            // formata CNPJ/CPF
            if (strlen($cnpj) == 14 and is_numeric($cnpj))
                $cnpj = preg_replace('/^(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})$/', '$1.$2.$3/$4-$5', $cnpj);
            if (strlen($cnpj) == 11 and is_numeric($cnpj))
                $cnpj = preg_replace('/^(\d{3})(\d{3})(\d{3})(\d{2})$/', '$1.$2.$3-$5', $cnpj);
        }
    }    

    $cidade = utf8_decode(retira_acentos($cidade));
    $cidade = str_replace("-"," ",$cidade);
    if ($tipo_posto_multiplo) {
        $sqlT = "SELECT ARRAY_AGG(tipo_posto) AS tipos
                   FROM tbl_posto_tipo_posto
                  WHERE fabrica = $login_fabrica
                    AND posto   = $posto\n";
        $resT = pg_query($con, $sqlT);

        $tipos_posto = pg_parse_array(pg_fetch_result($resT, 0, 0));

        if (count($tipos_posto) > 1) // se for = 1, deixa como está, já está gravado na tbl_posto_fabrica.tipo_posto
            $tipo_posto = $tipos_posto;
    }

    if (in_array($login_fabrica, array(158))) {

        /**
         * Carrega os distribuidores atribuidos ao posto
         */

        if (count($distribuidores_selected) > 0) {
            $orDistribuidor = "OR tbl_distribuidor_sla.distribuidor_sla IN (".implode(",", $distribuidores_selected).")";
        }

        $sqlUnidadeNegocio = "
            SELECT DISTINCT
                tbl_distribuidor_sla.unidade_negocio,
                MAX(tbl_distribuidor_sla.distribuidor_sla) AS distribuidor_sla,                
                tbl_distribuidor_sla.unidade_negocio||' - '||tbl_unidade_negocio.nome AS cidade
                FROM tbl_distribuidor_sla_posto
                LEFT JOIN tbl_distribuidor_sla USING(distribuidor_sla, fabrica)
                RIGHT JOIN tbl_unidade_negocio ON tbl_unidade_negocio.codigo = tbl_distribuidor_sla.unidade_negocio
                WHERE tbl_distribuidor_sla_posto.fabrica = {$login_fabrica}
                AND (tbl_distribuidor_sla_posto.posto = {$posto}
                {$orDistribuidor})
                GROUP BY tbl_distribuidor_sla.unidade_negocio, tbl_unidade_negocio.nome;
        ";

        $resUnidadeNegocio = pg_query($con, $sqlUnidadeNegocio);
        $distribuidores_posto = pg_fetch_all($resUnidadeNegocio);
        $id_distribuidores = array();

        if (count($distribuidores_posto) > 0) {
            foreach ($distribuidores_posto as $distribuidor_sla) {
                $id_distribuidores[] = $distribuidor_sla['distribuidor_sla'];
            }
        }

        if (count($distribuidores_selected) > 0 && count($id_distribuidores) > 0) {
            $distribuidores_selected = implode(",", array_merge($id_distribuidores, $distribuidores_selected));
        } else if (count($distribuidores_selected) == 0) {
            $distribuidores_selected = $id_distribuidores;
        }

        $sqlUnidadePrincipal = "
            SELECT distribuidor_sla FROM tbl_posto_distribuidor_sla_default WHERE fabrica = {$login_fabrica} AND posto = {$posto};
        ";
        $resUnidadePrincipal = pg_query($con, $sqlUnidadePrincipal);
        $unidade_principal = pg_fetch_result($resUnidadePrincipal, 0, distribuidor_sla);

        /**
         * Carrega os tipos de atendimento atribuidos ao posto
         */
        $sqlTiposAtendimento = "
            SELECT DISTINCT
                tbl_posto_tipo_atendimento.tipo_atendimento,
                tbl_tipo_atendimento.descricao AS tipo_atendimento_desc
            FROM tbl_posto_tipo_atendimento
            LEFT JOIN tbl_tipo_atendimento USING(tipo_atendimento)
            WHERE tbl_posto_tipo_atendimento.fabrica = {$login_fabrica}
            AND tbl_posto_tipo_atendimento.posto = {$posto};
        ";

        $resTiposAtendimento = pg_query($con, $sqlTiposAtendimento);
        $posto_tipos_atendimento = pg_fetch_all($resTiposAtendimento);

        $tipos_atendimento = array();

        if (count($posto_tipos_atendimento) > 0) {
            foreach ($posto_tipos_atendimento as $posto_tipo_atendimento) {
                $tipos_atendimento[] = $posto_tipo_atendimento['tipo_atendimento'];
            }
        }
        $json_tipos_atendimento = json_encode($tipos_atendimento);

        /**
         * Carrega os grupos de cliente atribuidos ao posto
         */
        $sqlGruposClientes = "
            SELECT DISTINCT
                tbl_posto_grupo_cliente.grupo_cliente,
                tbl_grupo_cliente.descricao AS grupo_cliente_desc
            FROM tbl_posto_grupo_cliente
            LEFT JOIN tbl_grupo_cliente USING(grupo_cliente)
            WHERE tbl_posto_grupo_cliente.fabrica = {$login_fabrica}
            AND tbl_posto_grupo_cliente.posto = {$posto};
        ";

        $resGruposClientes = pg_query($con, $sqlGruposClientes);
        $posto_grupos_clientes = pg_fetch_all($resGruposClientes);

        $grupos_clientes = array();

        if (count($posto_grupos_clientes) > 0) {
            foreach ($posto_grupos_clientes as $posto_grupo_clientes) {
                $grupos_clientes[] = $posto_grupo_clientes['grupo_cliente'];
            }
        }
        $json_grupos_clientes = json_encode($grupos_clientes);

    }

    if (in_array($login_fabrica,array(1))) {
        $sql = "SELECT  status,
                        texto
                FROM    tbl_credenciamento
                WHERE   posto = $posto
                AND     fabrica = $login_fabrica
                AND  status NOT ILIKE 'descredenciamento%'
          ORDER BY      credenciamento DESC
                LIMIT   1";
        $resStatus = pg_query($con,$sql);

        if (pg_num_rows($resStatus) > 0) {
            $statusPosto = pg_fetch_result($resStatus,0,'status');

            if ($statusPosto == 'Pre Cadastro em apr') {
                $cadastro_bloqueado = true;
                $textoModificado = utf8_decode(pg_fetch_result($resStatus,0,'texto'));
            }
        }
    }
}

if ($_GET["ajax_busca_posto_mapa"] == true) {

    $retorno          = array();
    $estado           = $_POST["estado"];
    $tipo_contato     = $_POST["tipo_contato"];
    $tipo             = $_POST["tipo"];
    $dataf            = date('Y-m-d');
    $datai6           = date('Y-m-d', strtotime('-6 months'));
    $datai12          = date($datai6, strtotime('-12 months'));

    if ($tipo_contato == "C") {
        $cond_doze      = " AND tbl_cliente.cpf NOT IN(SELECT codigo FROM tbl_roteiro_posto JOIN tbl_roteiro ON tbl_roteiro.roteiro = tbl_roteiro_posto.roteiro AND tbl_roteiro.fabrica = {$login_fabrica} WHERE tbl_roteiro.status_roteiro <> 3 AND codigo IS NOT NULL AND data_inicio BETWEEN '$datai12' AND '$dataf')"; 
        $cond_seis_doze = " AND tbl_roteiro.status_roteiro = 3 AND data_inicio::date INTERVAL '-6 months' "; 
        $cond_seis      = " AND tbl_roteiro.status_roteiro = 3 AND data_inicio BETWEEN '$datai6' AND '$dataf'"; 
        $cond_agendada  = " AND tbl_roteiro.status_roteiro = 2"; 
        $joinConsumidor .= " JOIN tbl_roteiro_posto ON tbl_cliente.cpf = tbl_roteiro_posto.codigo
                             JOIN tbl_roteiro ON tbl_roteiro.roteiro = tbl_roteiro_posto.roteiro"; 
            //$joinRoteiroVisita .= " JOIN tbl_roteiro_visita ON tbl_roteiro_posto.roteiro_posto = tbl_roteiro_visita.roteiro_posto"; 

        if ($tipo == "consumidor") {
            
            $retorno["doze"]        = getConsumidorMapa($joinConsumidor, $cond_doze, $estado);
            $retorno["seis_doze"]   = getConsumidorMapa($joinConsumidor, $cond_seis_doze, $estado);
            $retorno["seis"]        = getConsumidorMapa($joinConsumidor, $cond_seis, $estado);
            $retorno["agendada"]    = getConsumidorMapa($joinConsumidor, $cond_agendada, $estado);
            if (empty($retorno)) {
                exit(json_encode(array("erro" => true, "msg" => "Nenhum consumidor encontrado")));
            } else {
                exit(json_encode(array("erro" => false, "result" => $retorno)));
            }

        } elseif ($tipo == "doze_nao_visitado") {

            $cond = " AND tbl_cliente.cpf NOT IN(SELECT codigo FROM tbl_roteiro_posto JOIN tbl_roteiro ON tbl_roteiro.roteiro = tbl_roteiro_posto.roteiro AND tbl_roteiro.fabrica = {$login_fabrica} WHERE tbl_roteiro.status_roteiro <> 3 AND codigo IS NOT NULL AND data_inicio BETWEEN '$datai12' AND '$dataf')"; 

            $retorno["doze"]    = getConsumidorMapa($joinConsumidor, $cond, $estado);

            if (empty($retorno)) {
                exit(json_encode(array("erro" => true, "msg" => "Nenhum posto encontrado")));
            } else {
                exit(json_encode(array("erro" => false, "result" => $retorno)));
            }

        } elseif ($tipo == "seis_doze_visitado") {


            $setaData = new DateTime($datai6);
            $data612 = new DateTime('-12 month');

            $cond = " AND tbl_roteiro.status_roteiro = 3  AND data_inicio BETWEEN '$datai6' AND '".$data612->format('Y-m-d')."'"; 

            $retorno["seis_doze"]    = getConsumidorMapa($joinConsumidor, $cond, $estado);

            if (empty($retorno)) {
                exit(json_encode(array("erro" => true, "msg" => traduz("Nenhum posto encontrado"))));
            } else {
                exit(json_encode(array("erro" => false, "result" => $retorno)));
            }

        } elseif ($tipo == "seis_visitado") {

            $cond  = " AND tbl_roteiro.status_roteiro = 3 AND data_inicio::date INTERVAL '-6 months'"; 

            $retorno["seis"] = getConsumidorMapa($joinConsumidor, $cond, $estado);

            if (empty($retorno)) {
                exit(json_encode(array("erro" => true, "msg" => traduz("Nenhum posto encontrado"))));
            } else {
                exit(json_encode(array("erro" => false, "result" => $retorno)));
            }

        } elseif ($tipo == "visita_agendada") {
            $joinRoteiroVisita .= " JOIN tbl_roteiro_posto ON tbl_cliente.cpf = tbl_roteiro_posto.codigo AND status not in('OK', 'CC')
                                    JOIN tbl_roteiro ON tbl_roteiro.roteiro = tbl_roteiro_posto.roteiro"; 

            $cond  = " AND tbl_roteiro.status_roteiro = 2"; 

            $retorno["agendada"] = getConsumidorMapa($joinRoteiroVisita, $cond, $estado);

            if (empty($retorno)) {
                exit(json_encode(array("erro" => true, "msg" => traduz("Nenhum posto encontrado"))));
            } else {
                exit(json_encode(array("erro" => false, "result" => $retorno)));
            }

        } 

    } 

    if ($tipo_contato == "P") {

        if ($tipo == "doze_nao_visitado") {

            $cond = " AND tbl_posto.cnpj NOT IN(SELECT codigo FROM tbl_roteiro_posto JOIN tbl_roteiro ON tbl_roteiro.roteiro = tbl_roteiro_posto.roteiro AND tbl_roteiro.fabrica = {$login_fabrica} WHERE tbl_roteiro.status_roteiro <> 3 AND codigo IS NOT NULL AND data_inicio BETWEEN '$datai12' AND '$dataf')"; 

            $retorno["doze"]    = getPostoMapa($leftJoinRoteiro, $cond);

            if (empty($retorno)) {
                exit(json_encode(array("erro" => true, "msg" => traduz("Nenhum posto encontrado"))));
            } else {
                exit(json_encode(array("erro" => false, "result" => $retorno)));
            }

        } elseif ($tipo == "seis_doze_visitado") {
            $setaData = new DateTime($datai6);
            $data612 = new DateTime('-12 month');

            $cond = " AND tbl_roteiro.status_roteiro = 3  AND data_inicio BETWEEN '$datai6' AND '".$data612->format('Y-m-d')."'"; 

            $joinRoteiroVisita .= " JOIN tbl_roteiro_posto ON tbl_posto.cnpj = tbl_roteiro_posto.codigo AND status IN('OK')
                                    JOIN tbl_roteiro ON tbl_roteiro.roteiro = tbl_roteiro_posto.roteiro"; 
            $joinRoteiroVisita .= " JOIN tbl_roteiro_visita ON tbl_roteiro_posto.roteiro_posto = tbl_roteiro_visita.roteiro_posto"; 

            $retorno["seis_doze"]    = getPostoMapa($joinRoteiroVisita, $cond);

            if (empty($retorno)) {
                exit(json_encode(array("erro" => true, "msg" => traduz("Nenhum posto encontrado"))));
            } else {
                exit(json_encode(array("erro" => false, "result" => $retorno)));
            }

        } elseif ($tipo == "seis_visitado") {

            $cond  = " AND tbl_roteiro.status_roteiro = 3 AND data_inicio::date BETWEEN '$datai6' AND '$dataf'"; 

            $joinRoteiroVisita .= " JOIN tbl_roteiro_posto ON tbl_posto.cnpj = tbl_roteiro_posto.codigo AND status IN('OK')
                                    JOIN tbl_roteiro ON tbl_roteiro.roteiro = tbl_roteiro_posto.roteiro"; 

            $joinRoteiroVisita .= " JOIN tbl_roteiro_visita ON tbl_roteiro_posto.roteiro_posto = tbl_roteiro_visita.roteiro_posto"; 

            $retorno["seis"] = getPostoMapa($joinRoteiroVisita, $cond);

            if (empty($retorno)) {
                exit(json_encode(array("erro" => true, "msg" => traduz("Nenhum posto encontrado"))));
            } else {
                exit(json_encode(array("erro" => false, "result" => $retorno)));
            }

        } elseif ($tipo == "visita_agendada") {

            $cond  = " AND tbl_roteiro.status_roteiro = 2"; 
            $joinRoteiro     .= " JOIN tbl_roteiro_posto ON tbl_posto.cnpj = tbl_roteiro_posto.codigo AND status NOT IN('CC', 'OK')
                                  JOIN tbl_roteiro ON tbl_roteiro.roteiro = tbl_roteiro_posto.roteiro"; 

            $retorno["agendada"] = getPostoMapa($joinRoteiro, $cond);
            if (empty($retorno)) {
                exit(json_encode(array("erro" => true, "msg" => traduz("Nenhum posto encontrado"))));
            } else {
                exit(json_encode(array("erro" => false, "result" => $retorno)));
            }

        } elseif ($tipo == "posto") {

            $cond_doze              = " AND tbl_posto.cnpj NOT IN(SELECT codigo FROM tbl_roteiro_posto JOIN tbl_roteiro ON tbl_roteiro.roteiro = tbl_roteiro_posto.roteiro AND tbl_roteiro.fabrica = {$login_fabrica} WHERE tbl_roteiro.status_roteiro <> 3 AND codigo IS NOT NULL AND data_inicio::DATE BETWEEN '$datai12' AND '$dataf')"; 
            $cond_seis_doze         = " AND tbl_roteiro.status_roteiro = 3 AND data_inicio::date INTERVAL '-6 months' "; 
            $cond_seis              = " AND tbl_roteiro.status_roteiro = 3 AND data_inicio BETWEEN '$datai6' AND '$dataf'"; 
            $cond_agendada          = " AND tbl_roteiro.status_roteiro = 2"; 

            $retorno["doze"]        = getPostoMapa($joinRoteiro, $cond_doze, false, $estado);

            $retorno["seis_doze"]   = getPostoMapa($joinRoteiro, $cond_seis_doze, false, $estado);
            $retorno["seis"]        = [];
            $retorno["agendada"]    = getPostoMapa($joinRoteiro, $cond_agendada, false, $estado);

            if (empty($retorno)) {
                exit(json_encode(array("erro" => true, "msg" => traduz("Nenhum posto encontrado"))));
            } else {
                exit(json_encode(array("erro" => false, "result" => $retorno)));
            }

        } else {
            $retorno = getPostoMapa(null, null, true, $estado);
            if (empty($retorno)) {
                exit(json_encode(array("erro" => true, "msg" => traduz("Nenhum posto encontrado"))));
            } else {
                exit(json_encode(array("erro" => false, "result" => $retorno)));
            }
        }
    }

}

function getConsumidorMapa($joins = '', $where = '', $estado_postos = '') {
    global $login_fabrica, $con;

    $data_hoje     = date('Y-m-d');
    $data_seis_ant = date('Y-m-d', strtotime('-6 months'));
    $data_vinte_quatro_seis = date('Y-m-d', strtotime('-24 months'));
    $condEstado = "";

    if (strlen($estado_postos) > 0) {
        $condEstado  = " AND tbl_cidade.estado = '{$estado_postos}'";
    }

    if (!empty($joins)) {
        $cond = ",
                       (
                           SELECT (COUNT(os)/6)  
                             FROM tbl_os
                            WHERE fabrica = {$login_fabrica} 
                              AND status_checkpoint = 9
                              AND consumidor_cpf = tbl_cliente.cpf
                              AND data_digitacao::date BETWEEN '$data_seis_ant' AND '$data_hoje'
                       ) AS media_os";
    } else {
        $cond = "";
    }

    $sql = "SELECT tbl_cliente.*,
                   tbl_cidade.nome AS nome_cidade,
                   tbl_cidade.estado AS nome_estado
                   {$cond}
                 FROM tbl_cliente
            LEFT JOIN tbl_cidade USING(cidade)
                      {$joins}
                WHERE tbl_cliente.fabrica={$login_fabrica}
                      {$condEstado}
                      {$where}";

    $res = pg_query($con, $sql);
    if (pg_last_error() || pg_num_rows($res) == 0) {
        return array();
    }

    $key = 0;
    while ($rows = pg_fetch_assoc($res)) {

        $retorno[$key]["cliente"]                   = $rows["cliente"];
        $retorno[$key]["latitude"]                = $rows["latitude"];
        $retorno[$key]["longitude"]               = $rows["longitude"];
        $retorno[$key]["nome"]                      = empty($rows["nome"]) ? "" : utf8_decode($rows["nome"]);
        $retorno[$key]["contato_endereco"]          = empty($rows["endereco"]) ? "" : utf8_decode($rows["endereco"]);
        $retorno[$key]["contato_numero"]            = empty($rows["numero"]) ? "" : utf8_decode($rows["numero"]);
        $retorno[$key]["contato_email"]             = empty($rows["email"]) ? "" : utf8_decode($rows["email"]);
        $retorno[$key]["contato_bairro"]            = empty($rows["bairro"]) ? "" : utf8_decode($rows["bairro"]);
        $retorno[$key]["contato_nome"]              = empty($rows["contato_nome"]) ? "" : utf8_decode($rows["contato_nome"]);
        $retorno[$key]["nome_fantasia"]             = empty($rows["nome_fantasia"]) ? "" : utf8_decode($rows["nome_fantasia"]);
        $retorno[$key]["contato_fone_comercial"]    = empty($rows["fone"]) ? "" : $rows["fone"];
        $retorno[$key]["contato_cidade"]            = empty($rows["nome_cidade"]) ? "" : utf8_decode($rows["nome_cidade"]);
        $retorno[$key]["contato_estado"]            = empty($rows["nome_estado"]) ? "" : utf8_decode($rows["nome_estado"]);
        $retorno[$key]["estado"]            = empty($rows["nome_estado"]) ? "" : utf8_decode($rows["nome_estado"]);
        $retorno[$key]["media_os_seis_meses"]       = empty($rows["media_os"]) ? 0 : $rows["media_os"];
        $retorno[$key]["treinamento_dois_anos"]     = 0;
        $retorno[$key]["media_compra_seis_meses"]   = 0;
        $key++;
    }

    return $retorno;

}


function altera_cadastro_posto() 
{
    global $login_admin, $login_fabrica, $con;

    $sql_altera_categoria_posto = " SELECT parametros_adicionais::jsonb->>'altera_categoria_posto' AS altera_categoria_posto
                                    FROM tbl_admin 
                                    WHERE admin = $login_admin 
                                    AND fabrica = $login_fabrica";
    $res_altera_categoria_posto = pg_query($con, $sql_altera_categoria_posto);
            
    $altera_categoria_posto = 'f';
    if (pg_num_rows($res_altera_categoria_posto)) {
        $altera_categoria_posto = pg_fetch_result($res_altera_categoria_posto, 0, 'altera_categoria_posto');
        if (empty($altera_categoria_posto)) {
            $altera_categoria_posto = 'f';
        }
    }
    return $altera_categoria_posto;
}

function getPostoMapa($joins = '', $where = '', $todos_postos = false, $estado_postos = '') {
    global $login_fabrica, $con;

    $data_hoje     = date('Y-m-d');
    $data_seis_ant = date('Y-m-d', strtotime('-6 months'));
    $data_vinte_quatro_seis = date('Y-m-d', strtotime('-24 months'));
    $cond  = "";
    if (strlen($estado_postos) > 0) {
        $cond  = " AND tbl_posto_fabrica.contato_estado = '{$estado_postos}'";
    }
    if ($todos_postos) {
        $sql = "SELECT UPPER(tbl_posto.nome) AS nome, 
                   tbl_posto.posto, 
                   tbl_posto_fabrica.contato_nome, 
                   tbl_posto_fabrica.contato_endereco, 
                   tbl_posto_fabrica.contato_numero,
                   tbl_posto_fabrica.contato_email,
                   tbl_posto_fabrica.nome_fantasia, 
                   tbl_posto_fabrica.contato_bairro, 
                   tbl_posto_fabrica.contato_fone_comercial, 
                   tbl_posto_fabrica.contato_cidade, 
                   tbl_posto_fabrica.latitude,
                   tbl_posto_fabrica.longitude,
                       0 AS media_os,
                       0 AS media_compra,
                       0 AS media_treinamento
                 FROM tbl_posto_fabrica
                 JOIN tbl_posto ON tbl_posto_fabrica.posto = tbl_posto.posto AND tbl_posto_fabrica.fabrica = {$login_fabrica}
                WHERE tbl_posto_fabrica.credenciamento = 'CREDENCIADO' AND tbl_posto.estado = '{$estado_postos}'";
    } else {


        $sql = "SELECT UPPER(tbl_posto.nome) AS nome, 
                       tbl_posto.posto, 
                       tbl_posto_fabrica.contato_nome, 
                       tbl_posto_fabrica.contato_endereco, 
                       tbl_posto_fabrica.contato_numero,
                       tbl_posto_fabrica.contato_email,
                       tbl_posto_fabrica.nome_fantasia, 
                       tbl_posto_fabrica.contato_bairro, 
                       tbl_posto_fabrica.contato_fone_comercial, 
                       tbl_posto_fabrica.contato_cidade, 
                       tbl_posto_fabrica.contato_estado, 
                       tbl_posto_fabrica.latitude,
                       tbl_posto_fabrica.longitude
                     FROM tbl_posto_fabrica
                     JOIN tbl_posto ON tbl_posto_fabrica.posto = tbl_posto.posto AND tbl_posto_fabrica.fabrica = {$login_fabrica}
                    {$joins}
                    WHERE tbl_posto_fabrica.credenciamento = 'CREDENCIADO'
                          {$cond}
                          {$where}";
    }
    $res = pg_query($con, $sql);
    if (pg_last_error() || pg_num_rows($res) == 0) {
        return array();
    }

    foreach (pg_fetch_all($res) as $key => $rows) {

        $retorno[$key]["posto"]                     = $rows["posto"];
        $retorno[$key]["latitude"]                  = $rows["latitude"];
        $retorno[$key]["longitude"]                 = $rows["longitude"];
        $retorno[$key]["nome"]                      = empty($rows["nome"]) ? "" : utf8_decode($rows["nome"]);
        $retorno[$key]["contato_endereco"]          = empty($rows["contato_endereco"]) ? "" : utf8_decode($rows["contato_endereco"]);
        $retorno[$key]["contato_numero"]            = empty($rows["contato_numero"]) ? "" : utf8_decode($rows["contato_numero"]);
        $retorno[$key]["contato_email"]             = empty($rows["contato_email"]) ? "" : utf8_decode($rows["contato_email"]);
        $retorno[$key]["contato_bairro"]            = empty($rows["contato_bairro"]) ? "" : utf8_decode($rows["contato_bairro"]);
        $retorno[$key]["contato_nome"]              = empty($rows["contato_nome"]) ? "" : utf8_decode($rows["contato_nome"]);
        $retorno[$key]["nome_fantasia"]             = empty($rows["nome_fantasia"]) ? "" : utf8_decode($rows["nome_fantasia"]);
        $retorno[$key]["contato_fone_comercial"]    = empty($rows["contato_fone_comercial"]) ? "" : $rows["contato_fone_comercial"];
        $retorno[$key]["contato_cidade"]            = empty($rows["contato_cidade"]) ? "" : utf8_decode($rows["contato_cidade"]);
        $retorno[$key]["contato_estado"]            = empty($rows["contato_estado"]) ? "" : utf8_decode($rows["contato_estado"]);
        $retorno[$key]["media_os_seis_meses"]       = "";//getMediaOs($rows["posto"], $data_seis_ant, $data_hoje);
        $retorno[$key]["media_compra_seis_meses"]   = "";//getMediaCompra($rows["posto"], $data_seis_ant, $data_hoje);
        $retorno[$key]["treinamento_dois_anos"]     = "";//getMediaTreinamento($rows["posto"], $data_vinte_quatro_seis, $data_hoje);
        
    }

    return $retorno;

}


function getMediaOs($posto, $data_seis_ant, $data_hoje) {

    global $login_fabrica, $con;

    $sql = "SELECT (COUNT(os)/6)  AS media_os
              FROM tbl_os
             WHERE fabrica = {$login_fabrica} 
               AND status_checkpoint = 9
               AND posto = {$posto}
               AND data_digitacao::date BETWEEN '$data_seis_ant' AND '$data_hoje'";
   $res = pg_query($con, $sql);

    if (pg_last_error() || pg_num_rows($res) == 0) {
        return 0;
    }
    $retorno =  pg_fetch_assoc($res);
    return $retorno["media_os"];

}


function getMediaCompra($posto, $data_seis_ant, $data_hoje) {

    global $login_fabrica, $con;

    $sql = "SELECT (COUNT(total)/6) AS media_compra
             FROM tbl_pedido
            WHERE fabrica = {$login_fabrica} 
              AND status_pedido IN(4,5)
              AND posto = {$posto}
              AND data::date BETWEEN '$data_seis_ant' AND '$data_hoje'";
   $res = pg_query($con, $sql);

    if (pg_last_error() || pg_num_rows($res) == 0) {
        return 0;
    }
    $retorno =  pg_fetch_assoc($res);
    return "R$ " . number_format($retorno["media_compra"], 2, ",", ".");

}

function getMediaTreinamento($posto, $data_vinte_quatro_seis, $data_hoje) {

    global $login_fabrica, $con;

    $sql = "SELECT COUNT(tbl_treinamento_posto.posto) AS media_treinamento
             FROM tbl_treinamento_posto 
             JOIN tbl_treinamento USING(treinamento) 
            WHERE tbl_treinamento.fabrica = {$login_fabrica} 
              AND tbl_treinamento_posto.posto = {$posto}
              AND tbl_treinamento_posto.data_inscricao::date BETWEEN '$data_vinte_quatro_seis' AND '$data_hoje'";
   $res = pg_query($con, $sql);

    if (pg_last_error() || pg_num_rows($res) == 0) {
        return 0;
    }
    $retorno =  pg_fetch_assoc($res);
    return $retorno["media_treinamento"];
}

function formata_cpf_cnpj($campo) {

    if (strlen($campo) == 14 and is_numeric($campo)) {

        $campo_formatado = preg_replace('/^(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})$/', '$1.$2.$3/$4-$5', $campo);

    } elseif (strlen($campo) == 11 and is_numeric($campo)) {

        $campo_formatado = preg_replace('/^(\d{3})(\d{3})(\d{3})(\d{2})$/', '$1.$2.$3-$4', $campo);

    } else {
        $campo_formatado = $campo;
    }
    return $campo_formatado;
}

function buscarAtendentePorPosto($posto,$cod_ibge,$estado) {
    global $con, $login_fabrica;

    $sql = "SELECT tbl_posto_fabrica.admin_sap AS admin, tbl_admin.nome_completo AS admin_nome
            FROM tbl_posto_fabrica
            JOIN tbl_admin ON tbl_admin.admin = tbl_posto_fabrica.admin_sap
            WHERE tbl_posto_fabrica.fabrica = {$login_fabrica}
            AND tbl_posto_fabrica.posto = {$posto}
            ";

    $res = pg_query($con, $sql);

    if (pg_num_rows($res) > 0) {
        $atendente['admin'] = pg_fetch_result($res, 0, "admin");
        $atendente['admin_nome'] = pg_fetch_result($res, 0, "admin_nome");
        return $atendente;
    }

}

$visual_black = "manutencao-admin";

$title       = traduz("CADASTRO  DE POSTOS AUTORIZADOS");
$cabecalho   = traduz("CADASTRO DE POSTOS AUTORIZADOS");
$layout_menu = "cadastro";
include_once 'cabecalho.php';

if(!in_array($login_fabrica,[180,181,182])) {
	$fone = preg_replace('/^\(?0?x*(\d{2})\)?/', '($1)', $fone);
}

if ($login_fabrica == 175){
    $fax = preg_replace('/^\(?0?x*(\d{2})\)?/', '($1)', $fax);
}
?>
<?php
include_once '../js/js_css.php'; /* Todas libs js, jquery e css usadas no Assist - HD 969678 */

if ($login_fabrica >= 175){
    $plugins = array(
        "jquery_multiselect",
        "quicksearch-master"
    );

    include("plugin_loader.php");
}
?>
<script type="text/javascript" src="js/jquery.mask.js"></script>
<script type="text/javascript" src="js/jquery.maskMoney.min.js"></script>
<script src="plugins/jquery.form.js"></script>
<link rel="stylesheet" href="css/multiple-select.css" />
<script src="js/jquery.multiple.select.js"></script>
<link rel="stylesheet" href="plugins/leaflet/leaflet.css" />
<script src="plugins/leaflet/leaflet.js"></script>
<script src="plugins/leaflet/map.js"></script>

<?php  
/****** INCLUDE ******/
if (in_array($login_fabrica, [177])) {
    $plugins = array(
        "select2"
    );
    include("plugin_loader.php");
}

?>
<script type='text/javascript'>

<?php if($login_fabrica == 1){ ?>

        $(function() { 

            $('#categoria_posto').change(function() {
                
                let categoria = this.value;

                if (categoria == "Locadora") {

                    $('.faturado_locadora').show();

                } else {

                    $('.faturado_locadora').hide();   
                }
            });

           $('#categoria_posto').trigger('change');

        });

        function verificaAlteracao(){

            var recebeTaxaAdm = "";
            var recebeTaxaAdm_anterior = "";
            var obs = "";

            var categoria_posto_anterior = $( "input[name^='categoria_posto_anterior']" ).val();
            var categoria_posto = $("#categoria_posto option:selected" ).val();

            if($("#reembolso_peca_estoque").is(":checked")){
                var reembolso_peca_estoque =  't';
            }else{
                var reembolso_peca_estoque = 'f';
            }

            var reembolso_peca_estoque_anterior = $("#reembolso_peca_estoque_anterior").val();

            if($("#id_TaxaAdmS").is(":checked")){
                recebeTaxaAdm = $("#id_TaxaAdmS").val();
            }
            if($("#id_TaxaAdmN").is(":checked")){
                recebeTaxaAdm = $("#id_TaxaAdmN").val();
            }

            recebeTaxaAdm_anterior = $("#recebeTaxaAdm_anterior").val();

			if(recebeTaxaAdm_anterior.length == 0) recebeTaxaAdm_anterior = 'nao';
            var tipo_posto_anterior = $("#tipo_posto_anterior").val();
            var tipo_posto = $("#tipo_posto option:selected").text();

            if((categoria_posto != categoria_posto_anterior) || recebeTaxaAdm_anterior != recebeTaxaAdm || 
                tipo_posto_anterior != tipo_posto ||
                reembolso_peca_estoque_anterior != reembolso_peca_estoque ) {
                obs = prompt('<?=traduz("Informe a observação de credenciamento")?>');

                if(obs == null){
                    alert('<?=traduz("O campo observação é obrigatório.")?>');
                    return false;
                }
            }
            $('#observacao_credenciamento').val(obs); 
            return true;           
        }
    
<?php }?>    

    <?php if (strlen($posto) > 0 && in_array($login_fabrica, array(3))) { ?>
        $(function(){
            $("input[name=cnpj]").attr("readonly", true);
            $("input[name=ie]").attr("readonly", true);
            $("input[name=nome]").attr("readonly", true);
            $("input[name=cep]").attr("readonly", true);

            $("select[name=estado] option:not(:selected)").prop('disabled', true);
            $("input[name=estado]").val($(".addressState option:selected").val());
            $("select[name=cidade] option:not(:selected)").prop('disabled', true);
            $("input[name=cidade]").val($(".addressCity option:selected").val());
            $("input[name=bairro]").attr("readonly", true);
            $("input[name=endereco]").attr("readonly", true);
            $("input[name=numero]").attr("readonly", true);
            $("input[name=complemento]").attr("readonly", true);
            $("input[name=nome_fantasia]").attr("readonly", true);

            $("select[name=banco]").attr("disabled", true);
            $("input[name=banco]").val($(".xbanco option:selected").val());

            $("input[name=agencia]").attr("readonly", true);
            $("input[name=conta]").attr("readonly", true);
            $(".btn-lupa-cnpj, .btn-lupa-codigo, .btn-lupa-razao").hide();
        });
    <?php }?>

    <?if (in_array($login_fabrica, array(151,158))){ ?>
        $(function(){
            $("input[name=valor_extrato]").maskMoney({symbol:"", decimal:",", thousands:'.', precision:2, maxlength: 15});
            $("input[name=valor_mao_obra]").maskMoney({symbol:"", decimal:",", thousands:'.', precision:2, maxlength: 15});
        });
    <?}

    if (in_array($login_fabrica, [169,170])) { ?>
        $(function(){
            $("#mo_triagem").maskMoney({symbol:"", decimal:",", thousands:'.', precision:2, maxlength: 15});
        });
    <?php
    }

    ?>
    <?if ($login_fabrica == 117 ){ ?>
        $(function(){
            $("select[id^=linhas_]").change(function() {
            }).multipleSelect({
                width: '190px',
                minimumCountSelected: 1,
                selectAllText: '<?= traduz('Selecionar tudo'); ?>',
                allSelected: '<?= traduz('Todos os registros selecionados'); ?>',
                countSelected: '<?= traduz('Selecionado(s) # de %');?>'
            });
        });
    <?}?>

    <?php if ($login_fabrica == 1) { ?>
            $(document).ready(function txAdm() {
                if($("#id_TaxaAdmN").attr("checked")=="checked") {
                    $("#id_taxaAdm").prop("readonly",true);
                }
            });

            $(function() {
                $("#enviar_aprovacao").click(function(){
                    var posto           = $("input[name=posto]").val();
                    var codigo_posto    = $("input[name=codigo]").val();
                    var textoAlteracao = "";

                    var categoria_posto_anterior = $( "input[name^='categoria_posto_anterior']" ).val();
                    var categoria_posto = $("#categoria_posto option:selected" ).val();

                    if($("#reembolso_peca_estoque").is(":checked")){
                        var reembolso_peca_estoque =  't';
                    }else{
                        var reembolso_peca_estoque = 'f';
                    }

                    var reembolso_peca_estoque_anterior = $("#reembolso_peca_estoque_anterior").val();
                    
                    if($("#id_TaxaAdmS").is(":checked")){
                        var recebeTaxaAdm = $("#id_TaxaAdmS").val();
                    }
                    if($("#id_TaxaAdmN").is(":checked")){
                        var recebeTaxaAdm = $("#id_TaxaAdmN").val();
                    }

                    var recebeTaxaAdm_anterior = $("#recebeTaxaAdm_anterior").val();

                    var tipo_posto_anterior = $("#tipo_posto_anterior").val();
                    var tipo_posto          = $("#tipo_posto option:selected").text();

                    var observacao_interna = prompt('<?=traduz("Por favor, informe o motivo(Obrigatório).")?>');

                    if(categoria_posto != categoria_posto_anterior){
                        if(categoria_posto == 'mega projeto'){
                            categoria_posto = 'Industria/Mega Projeto';
                        }
                        textoAlteracao += "\n Antes: Categoria "+categoria_posto_anterior + "\n Depois: Categoria "+ categoria_posto ;
                    }

                    if(recebeTaxaAdm_anterior != recebeTaxaAdm){
                        textoAlteracao += "\n Antes: Recebe Taxa Adminitrativa "+recebeTaxaAdm_anterior + "\n Depois: Recebe Taxa Adminitrativa "+ recebeTaxaAdm ;
                    }

                    if(tipo_posto_anterior != tipo_posto){
                        textoAlteracao += "\n Antes: Tipo Posto "+tipo_posto_anterior + "\n Depois: Tipo Posto "+ tipo_posto ;       
                    }

                    if(reembolso_peca_estoque_anterior != reembolso_peca_estoque ){
                        textoAlteracao += "\n Antes: Peças em Garantia "+reembolso_peca_estoque_anterior + "\n Depois: Peças em Garantia "+ reembolso_peca_estoque ;  
                    }                   

                    if(observacao_interna.length > 0){
                        $.ajax({
                            url: "<?php echo $_SERVER['PHP_SELF']; ?>",
                            data:{"enviar_aprovacao": true, posto:posto, codigo_posto:codigo_posto, observacao_interna:observacao_interna, msg_alteracao: textoAlteracao},
                            type: 'POST',
                            complete: function(data) {
                                data = $.parseJSON(data.responseText);
                                if(data.retorno == 'ok'){
                                    alert('<?=traduz("Posto enviado para aprovação")?>');
                                    location.reload();
                                }else{                                    
                                    alert('<?=traduz("Falha ao enviar posto para aprovação.")?>');
                                }
                                
                            }
                        });
                    }else{
                        alert('<?=traduz("O campo motivo deve ser preenchido.")?>');
                    }
                });

                $("#id_TaxaAdmS").click(function() {
                    if($(this).is(':checked')) {
                        $("#id_taxaAdm").prop("readonly",false);
                    }
                });
            });
    <?php } ?>
    $(function(){
        Shadowbox.init();
        
        $(".btn-avaliacao-posto").click(function() {

            let posto    = $(this).data("posto");
            let pesquisa = $(this).data("pesquisa");

            Shadowbox.open({
                content:    "avaliacao_tecnica_makita.php?posto="+posto+"&pesquisa="+pesquisa,
                player: "iframe",
                title:      "Avaliação Técnica Makita",
                width:  1500,
                height: 800
            });
        });

        $(".aprovacao_pre_cadastro").click(function(){

            var posto = $("input[name='posto']").val();
            var codigo_posto = $("input[name='codigo']").val();

            $.ajax({
                url: "<?php echo $_SERVER['PHP_SELF']; ?>",
                data:{"aprovacao_pre_cadastro": true, posto:posto, codigo_posto:codigo_posto},
                type: 'POST',
                beforeSend: function () {
                    $("#loading_pre_cadastro").show();
                    $(".aprovacao_pre_cadastro").hide();
                },
                complete: function(data) {
                data = data.responseText;
                    if(data == 'enviado'){
                        alert('<?=traduz("Posto enviado para aprovação")?>');
                    }else{
                        alert('<?=traduz("Falha ao enviar posto para aprovação.")?>');
                    }
                    $("#loading_pre_cadastro").hide();
                }
            });
        });


        $(".btn_consulta_receita").click(function(){
            var posto = $("input[name=posto]").val();

            Shadowbox.open({
              content:    "consulta_dados_receita_posto.php?posto="+posto,
                player: "iframe",
                title:      '<?=traduz("Dados Receita")?>',
                width:  900,
                height: 500
            });        
        });



        $(".btn_arquivo_posto").click(function() {
            var tipo_anexo = $(this).data("tipo-anexo");

            $('input[name=tipo_anexo]').val(tipo_anexo);

            $("input[name=anexo_posto_upload]").trigger("click");
        });

        $("input[name=anexo_posto_upload]").change(function() {
            $("form[name=form_anexo_posto]").submit();
        });

        $("form[name=form_anexo_posto]").ajaxForm({
            complete: function(data) {
               var data = $.parseJSON(data.responseText);

               if (data.tipo_anexo == 1) {
                    var tipo = $("#fachada");
               } else if (data.tipo_anexo == 2) {
                    var tipo = $("#recepcao");
               } else {
                    var tipo = $("#bancada");
               }

               $(tipo).html('<img src="'+data.link+'" style="width:125px; height:125px;">');
            }
        });

        <?php 
        if($login_fabrica >= 175 and $login_fabrica != 183){ 
            if ($login_fabrica == 175) {
                $labelLinhaNaoAtendida = traduz("Produtos não atendidos");
                $labelLinhaAtendida = traduz("Produtos atendidos");
            } elseif (in_array($login_fabrica, [177,193])) {
                $labelLinhaNaoAtendida = traduz("Linhas não atendidas");
                $labelLinhaAtendida = traduz("Linhas atendidas");
            } else {
                $labelLinhaNaoAtendida = traduz("Linhas não selecionadas");
                $labelLinhaAtendida = traduz("Linhas selecionadas");
            }
            ?>
            $('.searchable').multiSelect({
                selectableHeader: "<div class='custom-header'><?=$labelLinhaNaoAtendida?></div><input type='text' class='search-input' autocomplete='off' placeholder='pesquisar'>",
                selectionHeader: "<div class='custom-header'><?=$labelLinhaAtendida?></div><input type='text' class='search-input' autocomplete='off' placeholder='pesquisar'>",
                afterInit: function(ms){
                    var that = this,
                        $selectableSearch = that.$selectableUl.prev(),
                        $selectionSearch = that.$selectionUl.prev(),
                        selectableSearchString = '#'+that.$container.attr('id')+' .ms-elem-selectable:not(.ms-selected)',
                        selectionSearchString = '#'+that.$container.attr('id')+' .ms-elem-selection.ms-selected';

                    that.qs1 = $selectableSearch.quicksearch(selectableSearchString)
                    .on('keydown', function(e){
                        if (e.which === 40){
                            that.$selectableUl.focus();
                            return false;
                        }
                    });

                    that.qs2 = $selectionSearch.quicksearch(selectionSearchString)
                    .on('keydown', function(e){
                        if (e.which == 40){
                            that.$selectionUl.focus();
                            return false;
                        }
                    });
                },
                afterSelect: function(){
                    this.qs1.cache();
                    this.qs2.cache();
                },
                afterDeselect: function(){
                    this.qs1.cache();
                    this.qs2.cache();
                }
            });
        <?php } ?>
    });

    // HD-2400939 POSITRON
    function EnviaContratosPositron(posto){

        if (confirm('<?=traduz("Deseja Enviar Contratos para o Posto ?")?>')) {
            $.ajax({
                url: "<?php echo $_SERVER['PHP_SELF']; ?>?contrato_positron=1&posto="+posto,
                type: 'GET',
                complete: function(data) {
                data = data.responseText;

                    if(data == 'enviado'){
                        alert('<?traduz("Email enviado com Sucesso")?>');
                    }else{
                        alert('<?=traduz("Posto autorizado sem email cadastrado")?>');
                    }
                }
            });
        }
    }
    // FIM HD-2400939 POSITRON

    function EnviaContratos(posto,fabrica){

        <?php
        $url_envia_contrato = (in_array($login_fabrica, array(147, 160)) or $replica_einhell) ? '"../credenciamento/gera_contrato.php?posto="+posto+"&fabrica="+fabrica+"&envia_contrato=true&cnpj='.$cnpj.'"' : '"../credenciamento/"+fabrica+"/gera_contrato.php?posto="+posto+"&fabrica="+fabrica+"&envia_contrato=true"';
        ?>

        if (confirm('<?=traduz("Deseja Enviar Contrato para o Posto ?")?>')) {
            $.ajax({
                url: <?php echo $url_envia_contrato; ?>,
                type: 'GET',
                timeout: 10000
            }).done(function(data){
                data = JSON.parse(data);
                if (data.ok !== undefined) {
                    alert(data.ok);
                }else{
                    alert("Erro: "+data.erro);
                }
            }).fail(function(){
                alert('<?=traduz("Falha ao tentar enviar o contrato!")?>');
            });
        }
    }
    // FIM HD-2400939 POSITRON

    function excluirRevendaOSVinculada(revenda){
        <? if(strlen($_GET["posto"]) > 0){ ?>
               var posto = <?=$_GET["posto"]?>;

        <? }else{ ?>
                  var posto = 0;
        <? }?>

        if(posto == 0){

            var trRevenda = $("#revendas_os_consumidor_vinculada").find("tr[rel="+revenda+"]");
            trRevenda.remove();

        }else{
            $.ajax({
              url:"<?=$PHP_SELF?>",
                type:"POST",
                data:{
                    revenda: revenda,
                    excluiRevendaOSVinculada: "true",
                    posto:posto

                 },
                 complete:function(response){
                    var dados = $.parseJSON(response.responseText);

                    if(dados.success == "true"){
                       var trRevenda = $("#revendas_os_consumidor_vinculada").find("tr[rel="+dados.revenda+"]");
                       trRevenda.remove();
                       alert('<?=traduz("Revenda removida")?>');
                    }else{
                        alert('<?=traduz("Erro ao remover a revenda")?>');
                    }
                }
            });
        }

    }

    function getCidade(consumidor_cobranca) {

		var id = "#cidade";
		var id_txt = "#cidade_txt";
		var provincia = $("#estado").val();
		var pais = $("#pais option:selected").val();

		<?php if (isset($_REQUEST['posto']) && !empty($_REQUEST['posto'])) { ?> 
		pais = $("#pais_posto").val();
		<?php } ?>

		if (consumidor_cobranca == "cobranca") {
			id = "#cobranca_cidade";
			id_txt = "#cobranca_cidade_txt";
			provincia = $("#cobranca_estado").val();
		}

		$.ajax({ 
		url : "posto_cadastro.php",
			type: "POST",
			data: {
			ajax_get_cidade_pela_provincia : true, 
				provincia: provincia,
				pais : pais
		},
			complete: function(data) {
				data = $.parseJSON(data.responseText);
				if (data.error) {
					alert("<?=traduz('Não foram encontradas cidade para esse estado')?>");
					$(id).css("display", "none");
					$(id_txt).css("display", "block");
				} else {
					$(id + " option").remove();

                    $(id).append($("<option>", {selected: true}));

					$.each(data, function(key, value){
						var prov = "<option value='"+value['cidade']+"'>"+value['cidade']+"</option>";
						$(id).append(prov);
					});
					$(id).css("display", "block");
					$(id_txt).css("display", "none");
				}
			}
		});
    }

    function adicionarRevenda(){

        var cnpjRevenda = $("#revenda_cnpj").val();
        $.ajax({
            url:"<?=$PHP_SELF?>",
            type:"POST",
            data:{
              cnpj: cnpjRevenda,
              osVinculadaConsumidor:"true"
            },
            complete: function(response){
                var dados = $.parseJSON(response.responseText);

                if(dados.success == "true"){
                    var tabela = $("#revendas_os_consumidor_vinculada");
                    var tbody = tabela.find("tbody");

                    var i = tbody.find("tr").length;
                    $("input[name=qtde_revenda_os_vinculada]").val(i+1);
                    var trRevenda = $("<tr>");
                    trRevenda.attr("rel",dados.revenda );

                    var hiddenRevenda = $("<input>");
                    hiddenRevenda.attr({
                      value:dados.revenda,
                        type:"hidden",
                        name:"hidden_revenda_"+i
                    })

                    var tdCnpj = $("<td>");
                    tdCnpj.attr("align","left");
                    tdCnpj.html(dados.cnpj);
                    tdCnpj.append(hiddenRevenda);
                    var tdNome = $("<td>");
                    tdNome.attr("align","left");
                    tdNome.html(dados.nome);

                    var tdAcao = $("<td>");
                    tdAcao.attr("align","center");
                    var btnExcluir = $("<button>");
                    btnExcluir.attr({
                      type:"button",
                    });
                  btnExcluir.click(function(){excluirRevendaOSVinculada(dados.revenda)});
                    btnExcluir.html('<?=traduz("Excluir")?>');
                    tdAcao.append(btnExcluir);
                    trRevenda.append(tdCnpj);
                    trRevenda.append(tdNome);
                    trRevenda.append(tdAcao);
                    tbody.append(trRevenda);

                }
            }
        })
    }
    function verifyToEnableOsVinculada(selectTpoPosto){

        if($(selectTpoPosto).val() == 437){
            $("#revenda_os_consumidor_vinculada").show();
        }else{
            $("#revenda_os_consumidor_vinculada").hide();
        }
    }
    function retorna_revenda(nomeRevenda, cnpj){
        $("#revenda_nome").val(nomeRevenda);
        $("#revenda_cnpj").val(cnpj);

    }
    function pesquisaRevenda(campo,tipo,tipo_revenda){
        var campo = campo

        if (jQuery.trim(campo).length > 2){
            Shadowbox.open({
              content:    "pesquisa_revenda_nv.php?"+tipo+"="+campo+"&tipo="+tipo+"&tipo_revenda="+tipo_revenda,
                player: "iframe",
                title:      "Pesquisa Revenda",
                width:  800,
                height: 500
                });
        }else
            alert('<?=traduz("Informar toda ou parte da informação para realizar a pesquisa!")?>');
    }

    function showModal() {

        Shadowbox.open({
			content:"../verifica_forma_extrato.php?posto=<?=$posto?>&admin=<?=$login_admin?>",
            player: "iframe",
            title:  '<?=traduz("Geração de Extrato")?>',
            width:  800,
            <?=$botao_fechar_modal?>
            height: 600
        });

    }

    function showImage(caminho) {

        Shadowbox.open({
            content:caminho,
            player: "iframe",
            title:  '<?=traduz("Imagem do Posto")?>',
            width:  700,
            <?=$botao_fechar_modal?>
            height: 500
        });

    }

    function deleteImage(caminho,imagem, tipo_anexo) {

        if (confirm('<?=traduz("Excluir a imagem do posto?")?>"\n"<?=traduz("ATENÇÃO: Se você fez alguma alteração no cadastro do posto, não será salva. Se for o caso, grave primeiro e exclua a imagem após gravar.")?>')) {
            $.post(
                window.location.pathname,
                {ajax: 'excluir', 'imagem': caminho},
                function(data) {
                    alert('<?=traduz("Imagem Excluída")?>');
                    $('#'+imagem+"> img").hide();

                    if (tipo_anexo == "fachada") {
                        $("#fachada").html("");
                    } else if (tipo_anexo == "recepcao") {
                        $("#recepcao").html("");
                    } else {
                        $("#bancada").html("");
                    }
                }
            )
        }
    }

    window.onload = function(){

        Shadowbox.init({
            modal: true,
        });

    };

    $(document).ready(function() {

        $("input[name='cep']").blur(function() {
            $("input[name='numero']").focus();
        });

        $("#mostra_opt_extrato").click(function(e) {
                        showModal();
                        e.preventDefault();
        });

        $('#cobranca_cidade').alpha();
        $(".msk_valor").numeric({allow: ',' });
        $("#km_apartir").numeric({allow:".,"});
        $('#tipo_controle_estoque').change(function()
        {
            var value = $(this).val();

            if (value == 'estoque_novo'){
                alert('<?=traduz("Alterando para Estoque Novo, se existir, será zerado todo e qualquer saldo de peças do estoque deste posto")?>');
            }
        });
    });

    //função p/ digitar só numero
    function checarNumero(campo){
        var num = campo.value.replace(",",".");
        campo.value = parseInt(num);
        if (campo.value=='NaN') {
            campo.value='';
        }
    }

    function fnc_pesquisa_codigo_posto (codigo, nome) {
        var url = "";
        if (codigo != "" && nome == "") {
            url = "pesquisa_posto.php?codigo=" + codigo;
            janela = window.open(url,"janela","toolbar=no,location=no,status=no,scrollbars=yes,directories=no,width=600,height=400,top=18,left=0");
            janela.focus();
        }
        else{
            alert('<?=traduz("Informar toda ou parte da informação para realizar a pesquisa")?>');
        }
    }

    function fnc_pesquisa_nome_posto (codigo, nome) {
        var url = "";
        if (codigo == "" && nome != "") {
            url = "pesquisa_posto.php?nome=" + nome;
            janela = window.open(url,"janela","toolbar=no,location=no,status=no,scrollbars=yes,directories=no,width=600,height=400,top=18,left=0");
            janela.focus();
        }
        else{
            alert('<?=traduz("Informar toda ou parte da informação para realizar a pesquisa")?>');
        }
    }

    function fnc_pesquisa_posto (campo, campo2, campo3, tipo) {
        if (tipo == "nome" ) {
            var xcampo = campo;
        }

        if (tipo == "cnpj" ) {
            var xcampo = campo2;
        }

        if (tipo == "codigo" ) {
            var xcampo = campo3;
        }

        if (xcampo.value != "") {
            var url = "";
            url = "posto_pesquisa.php?forma=reload&campo=" + xcampo.value + "&tipo=" + tipo ;
            janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=650, height=400, top=0, left=0");
            janela.retorno = "<? echo $_SERVER['PHP_SELF']; ?>";
            janela.nome = campo;
            janela.cnpj = campo2;
            janela.focus();
        }
        else{
            alert('<?=traduz("Informar toda ou parte da informação para realizar a pesquisa")?>');
        }
    }

    //HD 5595 Listar posto revenda para Tectoy
    function posto_revenda(fabrica){
        janela = window.open("posto_revenda.php?fabrica=" + fabrica ,"fabrica",'resizable=1,scrollbars=yes,width=650,height=450,top=0,left=0');
        janela.focus();
    }

    /* ============= Função FORMATA CNPJ =============================
    Nome da Função : formata_cnpj (cnpj, form)
            Formata o Campo de CNPJ a medida que ocorre a digitação
            Parâm.: cnpj (numero), form (nome do form)
    =================================================================*/
    function formata_cnpj(campo){
        var cnpj = campo.value.length;
        if (cnpj == 2 || cnpj == 6) campo.value += '.';
        if (cnpj == 10) campo.value += '/';
        if (cnpj == 15) campo.value += '-';
    }

    <?php if($contrato_posto){ ?>

        function excluir_contrato(id){

            var r = confirm('<?=traduz("Você deseja realmente excluir esse contrato?")?>');

            if (r == true) {

                location.href = "posto_cadastro.php?excluir_contrato=true&posto=<?php echo $posto; ?>&id="+id;

            }

        }

    <?php } ?>

    <?php if (in_array($login_fabrica, [177])) { ?>
            $(function() {
                // clicando no botão credenciar
                $(document).on("click", ".btn-credenciar", function() {
                    var posto = $(this).data('posto');
                    $.ajax({
                        type: 'POST',
                        url: "<?php echo $_SERVER['PHP_SELF']; ?>",
                        data: {ajax: "sim", acao: 'credenciar', posto: posto}
                    }).done(function(data){
                        data = JSON.parse(data);
                        if (data.ok !== undefined) {
                            window.setTimeout('location.reload()', 100); 
                        } else {
                            alert(data.error);
                        }
                    });     
                });

                // clicando no botão descredenciar
                $(document).on("click", ".btn-descredenciar", function() {

                    if (confirm("O posto descredenciado não poderá mais acessar a plataforma. Deseja mesmo descredenciar este posto?")) {

                        var posto = $(this).data('posto');
                        $.ajax({
                            type: 'POST',
                            url: "<?php echo $_SERVER['PHP_SELF']; ?>",
                            data: {ajax: "sim", acao: 'descredenciar', posto: posto}
                        }).done(function(data){
                            data = JSON.parse(data);
                            if (data.ok !== undefined) {
                                window.setTimeout('location.reload()', 100); 
                            } else {
                                alert(data.error);
                            }
                        });
                    }  
                });

                // clicando no botão Em Descredenciamento
                $(document).on("click", ".btn-emdescredenciamento", function() {

                    if (confirm("O posto em descredenciamento poderá apenas encerrar as OS's que estão abertas. Deseja mesmo iniciar o processo de descredencimento para este posto?")) {

                        var posto = $(this).data('posto');
                        $.ajax({
                            type: 'POST',
                            url: "<?php echo $_SERVER['PHP_SELF']; ?>",
                            data: {ajax: "sim", acao: 'em_descredencimento', posto: posto}
                        }).done(function(data){
                            data = JSON.parse(data);
                            if (data.ok !== undefined) {
                                window.setTimeout('location.reload()', 100); 
                            } else {
                                alert(data.error);
                            }
                        });
                    } 
                });
            });
    <?php } ?>

    var map = {"â":"a","Â":"A","à":"a","À":"A","á":"a","Á":"A","ã":"a","Ã":"A","ê":"e","Ê":"E","è":"e","È":"E","é":"e","É":"E","î":"i","Î":"I","ì":"i","Ì":"I","í":"i","Í":"I","õ":"o","Õ":"O","ô":"o","Ô":"O","ò":"o","Ò":"O","ó":"o","Ó":"O","ü":"u","Ü":"U","û":"u","Û":"U","ú":"u","Ú":"U","ù":"u","Ù":"U","ç":"c","Ç":"C","ñ":"n"};

    function removerAcentos(string) { 
        return string.replace(/[\W\[\] ]/g,function(a) {
            return map[a]||a}) 
    };

    $(function() {

        $("#cobranca_estado").change(function() {
            getCidade("cobranca");
        });

        $("#estado").change(function() {
      	    getCidade("consumidor");
        });

        $("#pais").change(function() {

			var pais = this.value;

			$("#estado option, #cidade option").remove();
			$("#estado optgroup, #cidade optgroup").remove();

			$("#cobranca_estado option, #cobranca_cidade option").remove();
			$("#cobranca_estado optgroup, #cobranca_cidade optgroup").remove();

			$.ajax({
			url : "posto_cadastro.php",
				type: "POST",
				data: {
				ajax_estados : true,
					pais : pais
			},
				complete: function(data) {
					data = $.parseJSON(data.responseText);
					if (data.error) {
						alert("<?=traduz('Não existe base de estados para esse país')?>");
						$("#estado, #cidade").hide().prop({disabled: true});
						$("#estado_txt, #cidade_txt").show().prop({disabled: false});
						$("#cobranca_estado, #cobranca_cidade").hide().prop({disabled: true});
						$("#cobranca_estado_txt, #cobranca_cidade_txt").show().prop({disabled: false});
                        $("#usa_cidade_estado_txt").val("t");
					} else {
						$("#estado").append('<option value=""></option>');
						$("#cobranca_estado").append('<option value=""></option>');
						$.each(data, function(key, value){
							var prov = "<option value='"+ key +"'>"+ value +"</option>";
							$("#estado").append(prov);
							$("#cobranca_estado").append(prov);
						});

						$("#estado, #cidade").show().prop({disabled: false});
						$("#estado_txt, #cidade_txt").hide().prop({disabled: true});
						$("#cobranca_estado, #cobranca_cidade").show().prop({disabled: false});
						$("#cobranca_estado_txt, #cobranca_cidade_txt").hide().prop({disabled: true});
                        $("#usa_cidade_estado_txt").val("f");
					}
				}
			});
		});

		<?php if(empty($posto) and !empty($pais)) { ?>
			//$("#pais").val('<?=$pais?>').trigger('change');
		<?php } ?>
    });

</script>

<!-- JavaScript Mapa da Rede-->
<script type="text/javascript">
$(document).ready(function()
{
    $("input.celular").mask("(99)99999-9999");
    <?php
    if (in_array($login_fabrica, [158,169,170])) {
    ?>
        $("input.mascara_tempo").mask("99:99");
        $("input.numerico").numeric();
    <?php
    }

    if($login_fabrica <> 14){
    ?>
/*      $("input[@name=fone]").maskedinput("(99) 9999-9999");
        $("input[@name=fone2]").maskedinput("(99) 9999-9999");
        $("input[@name=fone3]").maskedinput("(99) 9999-9999");
        $("input[@name=contato_cel]").maskedinput("(99) 9999-9999");*/
    <?
    }
    ?>
    /*$("input[@name=fax]").maskedinput("(99) 9999-9999");*/

    <?php if (!in_array($login_fabrica,[180, 181, 182])) { ?>
        $("#cep").mask("99.999-999");
    <?php } ?>

        /**
     * Função que busca as cidades do estado e popula o select cidade
     */
    function busca_cidade(estado, cidade, elem) {
        $(elem).find("option").first().nextAll().remove();

        var paisPosto = $("#pais").val();

        if (paisPosto == "" || paisPosto == undefined) {
            paisPosto = $("#pais_posto").val();
        }

        if (estado.length > 0) {
            $.ajax({
                async: false,
                url: window.location,
                type: "POST",
                data: { ajax: true, ajax_busca_cidade: true, estado: estado, pais: paisPosto },
                complete: function(data) {
                    data = $.parseJSON(data.responseText);

                    if (data.error) {
                        alert(data.error);
                        $(elem).prop('disabled', false);
                    } else {

                        let selected;

                        $.each(data.cidades, function(key, value) {

                            if (value == cidade) {
                                selected = true;
                            } else {
                                selected = false;
                            }

                            var option = $("<option></option>", { value: value, text: value, selected: selected });
                            $(elem).append(option);
                        });

                    }

                }
            });
        }

    }

    $("#cobranca_cep").mask("99.999-999");
    
    var callback_cidades_cobranca = function (result) {

        busca_cidade(result[4], result[3], $("form[name=frm_posto] select[name=cobranca_cidade]"));

    };

    var callback_cidades = function (result) {

        busca_cidade(result[4], result[3], $("form[name=frm_posto] select[name=cidade]"));

    };

    $("#cobranca_cep, #cep").blur(function(){

        let tipo_campo = $(this).attr("id") == "cobranca_cep" ? "cobranca_" : "";
        let frm_posto  = $("form[name=frm_posto]");

        if ($("#pais").val() == "BR" || $("#pais_posto").val() == "BR") {

            let cep = $(this).val();

            let endereco = $(frm_posto).find("input[name="+tipo_campo+"endereco]")[0];
            let bairro   = $(frm_posto).find("input[name="+tipo_campo+"bairro]")[0];
            var cidade   = $(frm_posto).find("select[name="+tipo_campo+"cidade]:visible")[0];
            var estado   = $(frm_posto).find("select[name="+tipo_campo+"estado]:visible")[0];

            if (tipo_campo == "") {
                callback = callback_cidades;
            } else {
                callback = callback_cidades_cobranca;
            }

            if (cidade === undefined) {
                cidade = $(frm_posto).find("input[name="+tipo_campo+"cidade]")[0];
                callback = null;
            }

            if (estado === undefined) {
                estado = $(frm_posto).find("input[name="+tipo_campo+"estado]")[0];
            }

            buscaCEP(cep, endereco, bairro, cidade, estado, null, callback);

        }

    });

    <?php if (!in_array($login_fabrica,[30,52,74,158,173,180,181,182,183])) { ?>
        $("#cnpj").mask('00.000.000/0000-00');
    <?php }

    if (($login_fabrica == 158 || $login_fabrica == 183) && !empty($posto)) { ?>
        var cnpj  = $("#cnpj").val().replace(/\D/g, '');
        var masks = ['000.000.000-00', '00.000.000/0000-00'];
        mask      = (cnpj.length > 11) ? masks[1] : masks[0];

        $('#cnpj').mask(mask);
    <?php
    }
    ?>
    $("input[name=cidade]").alpha({allow:" "});
    $("input[name=im]").numeric({allow:" "});
    <?php
        if($login_fabrica == 20){
            if($_pais ==  "BR"){
                echo '$("input[name=cnpj]").numeric();';
            }
        }else{
            echo '$("input[name=cnpj]").numeric();';
        }
    ?>
    $("input[name=desconto]").numeric({allow:".,"});
    $("input[name=meses_extrato]").numeric({allow:".,"});
<?
        if($login_fabrica == 30){
?>
    $("#fixo_km_valor").numeric({allow:".,"});
    $("#fixo_km_valor").css("text-align","right");
<?
        }
?>
//  Adiciona um evento onClick para cada 'area' que vai alterar o valor do SELECT 'estado'
    $('#mapabr map area').click(function() {
        $('#mapa_estado').val($(this).attr('name'));
        $('#mapa_estado').change();
    });
    $('#mapa_makita map area').click(function() {
        $('#estado_posto_makita').val($(this).attr('name'));
    });
    $('#sel_cidade').hide('fast');
    $('#abre_mapa_br').click(function() {
        $("#mapa_pesquisa").slideToggle("slow",function() {
            if ($("#mapa_pesquisa").is(":hidden")) {
                $("#abre_mapa_br").html('<b><?=traduz("Consulte o Mapa da Rede")?></b> <br /> <img src="imagens/icon_mapa_e.gif" id="img_ab_e" style="width: 28px; height: 28px; margin: 0 auto;" /> <img src="imagens/icon_mapa.gif" id="img_ab" style="width: 28px; height: 28px; margin: 0 auto; display: none;" />');
            } else {
                $("#abre_mapa_br").html('<b><?=traduz("Esconder o Mapa da Rede")?></b> <br /> <img src="imagens/icon_mapa_e.gif" id="img_ab_e" style="width: 28px; height: 28px; margin: 0 auto;" /> <img src="imagens/icon_mapa.gif" id="img_ab" style="width: 28px; height: 28px; margin: 0 auto; display: none;" />');
            }
        });
    });

    $('#abre_mapa_makita_br').click(function() {
  
        $("#mapa_makita").slideToggle("slow",function() {
            if ($("#mapa_makita").is(":hidden")) {
                $("#abre_mapa_makita_br").html('<b><?=traduz("Consulte o Mapa da Rede")?></b> <br /> <img src="imagens/icon_mapa_e.gif" id="img_ab_e" style="width: 28px; height: 28px; margin: 0 auto;" /> <img src="imagens/icon_mapa.gif" id="img_ab" style="width: 28px; height: 28px; margin: 0 auto; display: none;" />');
            } else {
                $("#abre_mapa_makita_br").html('<b><?=traduz("Esconder o Mapa da Rede")?></b> <br /> <img src="imagens/icon_mapa_e.gif" id="img_ab_e" style="width: 28px; height: 28px; margin: 0 auto;" /> <img src="imagens/icon_mapa.gif" id="img_ab" style="width: 28px; height: 28px; margin: 0 auto; display: none;" />');
            }
        });
    });

    $("#abre_mapa_br").mouseover(function () {
        $("#abre_mapa_br > #img_ab_e").hide();
        $("#abre_mapa_br > #img_ab").show();
        $("#abre_mapa_br").removeClass("abre_mapa_br_e");
        $("#abre_mapa_br").addClass("abre_mapa_br");
    });

    $("#abre_mapa_br").mouseout(function () {
        $("#abre_mapa_br > #img_ab").hide();
        $("#abre_mapa_br > #img_ab_e").show();
        $("#abre_mapa_br").removeClass("abre_mapa_br");
        $("#abre_mapa_br").addClass("abre_mapa_br_e");
    });

//  Quando muda o valor do select 'estado' requisita as cidades onde tem postos autorizados e os
//  insere no select 'cidades'
    $('#mapa_estado').change(function() {
        var estado = $('#mapa_estado').val();
        if (estado == '') {
            $('#sel_cidade').hide();
            return;
        }

        $('#sel_cidade').show();
        $.get("cidade_mapa_rede.php", {'action': 'cidades','estado': estado,'fabrica':<?=$login_fabrica?>},
          function(data){
            $('#sel_cidade').show(500);
            if (data.indexOf('Sem resultados') < 0) {
                $('#mapa_cidades').html(data).val('').removeAttr('disabled');
                if ($('#mapa_cidades option').length == 2) {
                    $('#mapa_cidades option:last').attr('selected','selected');
                    $('#mapa_cidades').change();
                }
            } else {
                $('#mapa_cidades').html(data).val('Sem resultados').attr('disabled','disabled');
            }
          });
    });

    $('#mapa_cidades').change(function() {
        $('select[name=cidade]').val($('#mapa_cidades').val());
        $('select[name=linha_elgin]').val('');
        $('[name=btn_mapa]').click();
    });
//  });

    $('#btn_mapa').on('click',function(){
        var urlMap = null;
        if($('select[name=estado]').val() !== "00"){
            urlMap = '?estado='+$('select[name=estado]').val()+'&pais='+$('select[name=pais]').val();
            if($('select[name=cidade]').val() !== ""){
                urlMap += '&cidade='+$('select[name=cidade]').val();
            }

            if ($('select[name=linha_elgin]').val() !== "" && typeof($('select[name=linha_elgin]').val()) !== "undefined" ) {
                urlMap += '&mapa_linha='+$('select[name=linha_elgin]').val();
            }else{
                if ($('select[name=mapa_linha]').val() !== "" && typeof($('select[name=mapa_linha]').val()) !== "undefined" ) {
                    urlMap += '&mapa_linha='+$('select[name=mapa_linha]').val();
                }
            }
            window.open('mapa_rede_new.php'+urlMap);
		}else{
			if($('select[name=mapa_estado]').val() !== "") {
            urlMap = '?estado='+$('select[name=mapa_estado]').val()+'&pais='+$('select[name=pais]').val();
            if($('select[name=mapa_cidades]').val() !== ""){
                urlMap += '&cidade='+$('select[name=mapa_cidades]').val();
            }
            if ($('select[name=mapa_linha]').val() !== "" && typeof($('select[name=mapa_linha]').val()) !== "undefined" ) {
                urlMap += '&mapa_linha='+$('select[name=mapa_linha]').val();
            }else{
                if ($('select[name=linha_elgin]').val() !== "" && typeof($('select[name=linha_elgin]').val()) !== "undefined" ) {
                    urlMap += '&mapa_linha='+$('select[name=linha_elgin]').val();
                }
            }
            window.open('mapa_rede_new.php'+urlMap);
			}else{
				alert('<?=traduz("Selecione um Estado para realizar a pesquisa!")?>');
			}
        }
    });

    <?php
    if ($login_fabrica == 50) {
    ?>
    $('#tipo_posto').change(function(){
        $('input[name=divulgar_consumidor]').each(function(){
            if ($(this).val() == 't') {
                if ($('#tipo_posto').val() == 263) {
                    $(this).prop('checked', false);
                }else{
                    $(this).prop('checked', true);
                }
            }else{
                if ($('#tipo_posto').val() == 263) {
                    $(this).prop('checked', true);
                }else{
                    $(this).prop('checked', false);
                }
            }
        });
    });
    <?php
    }
    ?>

    <?php if (in_array($login_fabrica, array(169,170))){ ?>
        $('#tipo_posto').change(function(){
            var posto_tipo_revenda = $("#tipo_posto > option:selected").attr("rel");;

            if (posto_tipo_revenda == 't'){
                $("#tr_dealer").show();
            }else{
                $("#tr_dealer").hide();
            }
        });
    <?php } ?>

    <?php if ($login_fabrica == 183){ ?>
        $('#tipo_posto').change(function(){
            let posto_tipo_revenda = $("#tipo_posto > option:selected").attr("rel");
            let radios = $("input[name='divulgar_consumidor']");
            
            if (posto_tipo_revenda == 't'){
                radios.filter('[value=f]').prop('checked', true);
            }else{
                radios.filter('[value=t]').prop('checked', true);
            }
        });

        $("input[name='divulgar_consumidor']").click(function(){
            let posto_tipo_revenda = $("#tipo_posto > option:selected").attr("rel");
            let radios = $("input[name='divulgar_consumidor']");

            if (posto_tipo_revenda == 't'){
                radios.filter('[value=f]').prop('checked', true);
            }
        });
        
    <?php } ?>
}); // FIM do jQuery

function montaComboCidade(estado){
    
    $.ajax({
            url: "<?php echo $_SERVER['PHP_SELF']; ?>?buscaCidade=1&estado="+estado,
            cache: false,
            success: function(data) {
                $('#cidade_mapa').html(data);
            }

        });

}

function montaComboCidade2(){

    var estado = $('#mapa_estado').val();

    $.ajax({
        url: "<?= $_SERVER['PHP_SELF']; ?>?buscaCidade=1&estado="+estado,
        cache: false,
        complete: function(data) {
            $('#mapa_cidades').html(data.responseText);
        }

    });

}

function entrega_tecnica_check(valor) {
    if (valor == "t") {
        //$("#entrega_tecnica").attr("checked", "checked").attr("readonly", "readonly");
        $("#entrega_tecnica").attr("checked", "checked");        
        $("#pedido_faturado").attr('checked', false);
        $("#pedido_faturado").attr('disabled', true);
        $("#pedido_consumo_proprio").attr('checked', false);
        $("#pedido_consumo_proprio").attr('disabled', true);
        $("#digita_os").attr('checked', false);
        $("#digita_os").attr('disabled', true);
        $("#prestacao_servico").attr('checked', false);
        $("#prestacao_servico").attr('disabled', true);
    } else {
        $("#entrega_tecnica").removeAttr("readonly");        
        $("#pedido_faturado").attr('disabled', false);
        $("#pedido_consumo_proprio").attr('disabled', false);
        $("#digita_os").attr('disabled', false);
        $("#prestacao_servico").attr('disabled', false);
    }    
}

<?php
if (in_array($login_fabrica, [158,189])) {
?>
    function mostra_cep_atendido(select) {
        var posto_interno = $("#tipo_posto > option:selected").data("posto-interno");

        if (posto_interno != "t") {
            $("#posto_cep_atendido").show();
            $("tr.hora_qtde_atendimento").show();
        } else {
            $("#posto_cep_atendido").hide();
            $("tr.hora_qtde_atendimento").hide();
            $("tr.hora_qtde_atendimento input").val("");
        }
    }

    $(function() {

        $("#adicionar_novo_cep_input").mask("99999-999");

        $("#adicionar_novo_cep_button").click(function() {
            var cep = String($("#adicionar_novo_cep_input").val());

            if (cep.length == 9) {
                if (valida_cep_cadastrado(cep) == false) {
                    var option = $("<option></option>", {
                        value: cep.replace(/\D/, ""),
                        text: cep,
                        selected: true
                    });

                    var div = $("<div></div>", {
                        html: cep+" <button cep='"+String(cep.replace(/\D/, ""))+"' type='button' class='remover_cep_atendido' title='<?=traduz("Remover CEP")?>' >X</button>",
                        css: {
                            width: "100%;",
                            height: "auto",
                            "padding-bottom": "2px",
                            "margin-bottom": "5px",
                            "border-bottom": "1px solid #D6D6D6"
                        }
                    });

                    $("#cep_atendido").prepend(div);
                    $("#cep_atendido_select").append(option);

                    $("button.remover_cep_atendido").unbind("click");
                    $("button.remover_cep_atendido").click(function() {
                        var cep = String($(this).attr("cep"));

                        $(this).parent().remove();
                        $("#cep_atendido_select > option[value='"+cep+"']").remove();
                    });
                } else {
                    alert('<?=traduz("CEP já cadastrado")?>');
                }
            }
        });

        $("#cep_atendido").change(function() {
            $(this).find("option").prop({ selected: true });
        });

        $("button.remover_cep_atendido").click(function() {
            var cep = String($(this).attr("cep"));

            $(this).parent().remove();
            $("#cep_atendido_select > option[value='"+cep+"']").remove();
        });

        $("#zera_estoque").click(function(){
            var posto = $("input[name=posto]").val();

            if(confirm('<?=traduz("Deseja realmente ZERAR o estoque do Posto?")?>')) { 

                var motivo = prompt('<?=traduz("Por favor, informe o motivo")?>');

                if (motivo != "") {
                    $.ajax({
                        url:"posto_cadastro.php",
                        type: "POST",
                        data: {ajax_zera_estoque: "true", posto:posto, motivo:motivo},
                        complete: function(data){

                            if(data.responseText != ""){
                                alert(data.responseText);
                            }else{
                                alert('<?=traduz("Estoque zerado com sucesso")?>');
                            }
                        }
                    });
                }else{
                    alert('<?=traduz("Motivo é obrigatório")?>');
                }

            }
        });

        function valida_cep_cadastrado(cep) {
            cep = cep.replace(/\D/, "");

            var option = $("#cep_atendido_select > option[value='"+cep+"']").length;

            if (option == 0) {
                return false;
            } else {
                return true;
            }
        }
    });
<?php
}
if ($login_fabrica == 1) {
?>
    $(function() {
        $("#categoria_posto").change(function(){
            var categoria_posto = $(this);
            if (categoria_posto.val() == "Compra Peca") {

                $("input:radio[name=divulgar_consumidor][value=f]").click();
                $("input:radio[name=divulgar_consumidor]").prop("disabled","true");

                $("input:checkbox[name=digita_os]").prop("disabled","true");
            } else {
                $("input:radio[name=divulgar_consumidor]").prop("disabled","");
                $("input:checkbox[name=digita_os]").prop("disabled","");
            }
        });
    });
<?php
}
?>
</script>

<style type="text/css">
<?php if ($login_fabrica >= 175){ ?>
.custom-header{
    text-align: center;
    padding: 3px;
    font: bold 12px "Arial" !important;
    background: #596d9b;
    color: #fff;
}
.search-input{
    height: 22px;
    margin-top: 5px;
    margin-bottom: 3px;
    margin-left: 5px;
    width: 296px;
    border-radius: 4px;
    border-style: groove;
}
.ms-container{
    text-align: left !important;
    width: 690px !important;
    height: 480px;
}
.ms-list{
    height: 450px !important;
}
<?php } ?>

.border_visita_tecnica {
    border: 1px solid #dddddd;
    border-collapse: separate;
}

.remover_cep_atendido, .remover_unidade_negocio {
    height: 15px;
    width: 42px;
    font-size: 10px;
    font-weight: bold;
    line-height: 4px;
    display: inline-block;
    text-align: center;
    padding: 0px;
    margin-left: 10px;
    cursor: pointer;
    color: rgb(255, 255, 255);
    background-color: rgb(195, 0, 0);
    border-color: rgb(195, 0, 0);
    padding-top: 5px;
}

.frm_obrigatorio{
    background-color: #FCC;
    border: #888 1px solid;
    font:bold 8pt Verdana;
}

.text_curto {
    text-align: center;
    font-weight: bold;
    color: #000;
    background-color: #FF6666;
}

.menu_top {
    text-align: center;
    font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
    font-size: 10px;
    font-weight: bold;
    border: 1px solid;
    color:#596d9b;
    background-color: #d9e2ef
}

.border {
    border: 1px solid #ced7e7;
}

.table_line {
    text-align: center;
    font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
    font-size: 10px;
    font-weight: normal;
    border: 0px solid;
    background-color: #ffffff
}

input {
    font-size: 10px;
}

.top_list {
    text-align: center;
    font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
    font-size: 10px;
    font-weight: bold;
    color:#596d9b;
    background-color: #d9e2ef
}

.line_list {
    text-align: left;
    font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
    font-size: x-small;
    font-weight: normal;
    color:#596d9b;
    background-color: #ffffff
}

.Titulo {
    text-align: center;
    font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
    font-size: 10px;
    font-weight: bold;
    color: #FFFFFF;
    background-color: #596D9B;
}

.Conteudo {
    font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
    font-size: 10px;
    font-weight: normal;
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

.msg_erro{
background-color:#FF0000;
font: bold 16px "Arial";
color:#FFFFFF;
text-align:center;
}

.formulario{
background-color:#D9E2EF;
font:11px Arial;
}

.subtitulo{

background-color: #7092BE;
    font:bold 11px Arial;
    color: #FFFFFF;
}

table.tabela tr td{
font-family: verdana;
font-size: 11px;
border-collapse: collapse;
border:1px solid #596d9b;
}

.msg_sucesso{
background-color: green;
font: bold 16px "Arial";
color: #FFFFFF;
text-align:center;
}

.texto_avulso{
    font: 14px Arial; color: rgb(89, 109, 155);
    background-color: #d9e2ef;
    text-align: center;
    width:700px;
    margin: 0 auto;
    border:1px solid #596d9b;
}
.btn-solicita,
.btn-avaliacao-posto,
.btn-visualiza-pesquisas {
    background: #596d9b;
    color: #fff;
    padding: 6px 18px;
    font-size: 17px;
    border-color: #596d9b;
}
#sb-container{
    z-index: 11111111111111;
}

.btn_consulta_receita{
    background: #596d9b;
    color: #fff;
    padding: 6px 18px;
    font-size: 17px;
    border-color: #596d9b;
}

</style>

<!-- CSS Mapa BR -->
<style type="text/css">
<!--
div#mapa_pesquisa {
    font-family: sans-serif, Verdana, Geneva, Arial, Helvetica;
    font-size: 11px;
    line-height: 1.2em;
    color:#88A;
    background: white;
    top: 0;
    left: 0;
    padding: 30px 10px 15px 10px;
    display: none;
}

#frmdiv {
    margin: 10px;
    text-align: left;
    width: 552px;
}

#mapabr {height:340px;position:relative;float: left}
    #mapabr label, #mapabr select {margin-left: 1em;z-index:10}
    #mapabr span {
        padding: 2px 4px;
        color: white;
        background-color: #A10F15;
        text-shadow: 0 0 0 transparent;
        font: inherit
    }
    #mapabr h2 {margin-top: 1.5em}
    #mapabr area {cursor: pointer}
    #mapabr fieldset {
        border-radius: 5px;
        -moz-border-radius: 5px;
        -webkit-border-radius: 5px;
        height: 365px;
        width: 500px;
}

.cinza {#667}
.bold {
    font-weight: bold;
}

#abre_mapa_br
{
    display: block;
    cursor: pointer;
    font-size: 12px;
    height: 30px;
    width: 155px;
}
.abre_mapa_br
{
    color: #0183C9;
}
.abre_mapa_br_e
{
    color: #365093;
}
.geolocalizacao a{
    font-size: 14px;
    color: #365093;
}
.geolocalizacao a:hover{
    font-size: 14px;
    color: #0183C9;
}

button, input[type=submit], input[type=button]
{
    cursor: pointer;
}
//-->
</style>

<?
function reduz_imagem($img, $max_x, $max_y, $nome_foto) {
    //pega o tamanho da imagem ($original_x, $original_y)
    list($width, $height) = getimagesize($img);
    $original_x = $width;
    $original_y = $height;
    // se a largura for maior que altura
    if($original_x > $original_y) {
       $porcentagem = (100 * $max_x) / $original_x;
    }
    else {
       $porcentagem = (100 * $max_y) / $original_y;
    }

    $tamanho_x = $original_x * ($porcentagem / 100);
    $tamanho_y = $original_y * ($porcentagem / 100);

    $image_p = imagecreatetruecolor($tamanho_x, $tamanho_y);
    $image   = imagecreatefromjpeg($img);
    imagecopyresampled($image_p, $image, 0, 0, 0, 0, $tamanho_x, $tamanho_y, $width, $height);
    imagejpeg($image_p, $nome_foto, 65);
}

if ($login_login == "fabricio" && $login_fabrica == 3) {
    $sql =  "SELECT DISTINCT
                    tbl_posto.posto                                               ,
                    tbl_posto_fabrica.codigo_posto                AS posto_codigo ,
                    tbl_posto.nome                                AS posto_nome   ,
                    TO_CHAR(tbl_credenciamento.data,'DD/MM/YYYY') AS data         ,
                    tbl_credenciamento.dias                                       ,
                    TO_CHAR((tbl_credenciamento.data::date + tbl_credenciamento.dias),'DD/MM/YYYY') AS data_prevista
            FROM tbl_credenciamento
            JOIN tbl_posto          ON  tbl_posto.posto           = tbl_credenciamento.posto
            JOIN tbl_posto_fabrica  ON  tbl_posto_fabrica.posto   = tbl_posto.posto
                                    AND tbl_posto_fabrica.fabrica = $login_fabrica
            WHERE tbl_credenciamento.fabrica = $login_fabrica
            AND   UPPER(tbl_credenciamento.status) = 'EM DESCREDENCIAMENTO'
            AND   UPPER(tbl_posto_fabrica.credenciamento) = 'EM DESCREDENCIAMENTO'
            AND   (tbl_credenciamento.data::date + tbl_credenciamento.dias) < current_date
            ORDER BY tbl_posto_fabrica.codigo_posto;";
    $resC = pg_query($con,$sql);
    if (pg_num_rows($resC) > 0) {
        echo "<br>";
        echo "<div id='mainCol'>";
        echo "<div class='contentBlockLeft' style='background-color: #FFCC00; width: 500;'>";
        echo "<br>";
        echo "<b>".traduz("Postos com Status")." \"".traduz("Em Descredenciamento")."\"</b>";
        echo "<br><br>";
        echo "<table class='formulario'>";
            echo "<tr class='titulo_coluna'>";
            echo "<td>".traduz("Posto")."</td>";
            echo "<td>".traduz("Data")."</td>";
            echo "<td>".traduz("Dias")."</td>";
            echo "<td>".traduz("Data Prevista")."</td>";
            echo "</tr>";
        for ($k = 0 ; $k < pg_num_rows($resC) ; $k++) {
            $cor = ($k % 2 == 0) ? "#F7F5F0" : "#F1F4FA";

            echo "<tr class='Conteudo' bgcolor='$cor'>";
            echo "<td align='left'><a href='$PHP_SELF?posto=" . trim(pg_fetch_result($resC,$k,posto)) . "'>" . trim(pg_fetch_result($resC,$k,posto_codigo)) . " - " . trim(pg_fetch_result($resC,$k,posto_nome)) . "</a></td>";
            echo "<td>" . trim(pg_fetch_result($resC,$k,data)) . "</td>";
            echo "<td>" . trim(pg_fetch_result($resC,$k,dias)) . "</td>";
            echo "<td>" . trim(pg_fetch_result($resC,$k,data_prevista)) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        echo "<br>";
        echo "</div>";
        echo "</div>";
        echo "<br>";
    }
}

if ($login_fabrica == 1 AND $cadastro_bloqueado) {
    $status_posto = "";
    $sql_status_posto = "SELECT credenciamento FROM tbl_posto_fabrica WHERE posto = $posto AND fabrica = $login_fabrica";
    $res_status_posto = pg_query($con, $sql_status_posto);
    $status_posto = pg_fetch_result($res_status_posto, 0, 'credenciamento');
    if ($status_posto == 'CREDENCIADO') {
?>
        <table style="margin: 0 auto; width: 700px; border: 0;" class='formulario' cellspacing="1" cellpadding="0">
            <tr align='center'>
                <td class='msg_erro'>
                    <?=traduz('Posto aguardando aprovação na alteração do Cadastro.')?> <br /><?=nl2br($textoModificado)?>
                </td>
            </tr>
        </table>
<?php     
    } else {        

?>
    <table style="margin: 0 auto; width: 700px; border: 0;" class='formulario' cellspacing="1" cellpadding="0">
        <tr align='center'>
            <td class='msg_erro'>
                <?=traduz('Posto aguardando aprovação de Cadastros.')?> <br /><?=nl2br($textoModificado)?>
            </td>
        </tr>
    </table>
<?php
    }
}

if (strlen($msg_erro) > 0 or !empty($msg) or !empty($msg_erro_contrato)) {
	if(!empty($msg_erro_contrato)) $msg_erro = $msg_erro_contrato;
    if (strpos($msg_erro,"ERROR:") !== false) {
        $x = explode('ERROR: ',$msg_erro);
        $msg_erro = $x[1];
    }

    $classe = (!empty($msg_erro)) ? "error":"msg_sucesso";
    $mensagem = (!empty($msg_erro)) ? $msg_erro:$msg;
    if (strpos($msg_erro, "tbl_posto_cnpj") ) $msg_erro = traduz("CNPJ do posto já cadastrado.");
?>
<table style="margin: 0 auto; width: 700px; border: 0;" class='formulario' cellspacing="1" cellpadding="0">
<tr align='center'>
<td class='<? echo $classe;?>'>
        <? echo $mensagem; ?>
    </td>
</tr>
</table>
<? } ?>
<p>
<?php if (!in_array($login_fabrica, [180,181,182])) { ?>
    <form name='frm_mapa' method='post' action='mapa_rede_new.php' target='_blank'>
    <table style="margin: 0 auto; width: 700px; border: 0;" cellpadding="1" cellspacing="3" class='formulario'>
        <tr class="titulo_tabela">
            <td>
                <?=traduz('Mapa da Rede')?>
            </td>
        </tr>
        <tr>
            <td align='center' style='color: #596D9B; font: Arial'>
                <?=traduz('Para incluir um novo posto, preencha somente seu CNPJ e clique em gravar.')?>
                <br>
                <?=traduz('Faremos uma pesquisa para verificar se o posto já está cadastrado em nosso banco de dados.')?>
            </td>
        </tr>
        <tr>
            <td align='center'>
            <span id='abre_mapa_br' class="abre_mapa_br_e">
                    <b><?=traduz('Consulte o Mapa da Rede')?></b>
                    <br />
                    <img src="imagens/icon_mapa_e.gif" id="img_ab_e" style="width: 28px; height: 28px; margin: 0 auto;" />
                    <img src="imagens/icon_mapa.gif" id="img_ab" style="width: 28px; height: 28px; margin: 0 auto; display: none;" />
            </span>
            <br />
            <br />

            <div id='mapa_pesquisa'>
                <div id='frmdiv'>
                    <fieldset for="frm_mapa_rede_gama" style="width: 550px;">
                        <legend><?=traduz('Pesquisa de Postos Autorizados')?></legend>
                        <div id='mapabr'>
                            <map name="Map2">
                                <area shape="poly" name="RS" coords="122,238,142,221,164,232,148,262">
                                <area shape="poly" name="SC" coords="143,214,172,215,169,235,143,219">
                                <area shape="poly" name="PR" coords="138,202,148,191,166,192,175,207,171,214,139,213">
                                <area shape="poly" name="SP" coords="152,187,162,173,182,174,186,187,188,194,197,190,197,198,177,206,168,190">

                                <area shape="poly" name="MS" coords="136,195,156,171,138,159,124,159,117,182">
                                <area shape="poly" name="MT" coords="117,151,143,151,160,127,160,106,120,105,111,101,98,102,107,117,100,131,102,142">
                                <area shape="poly" name="RO" coords="93,126,98,118,94,113,86,105,86,100,80,93,73,102,67,108,67,116,77,121">
                                <area shape="poly" name="AC" coords="50,106,10,91,13,101,23,104,29,104,30,112,44,113">
                                <area shape="poly" name="AM" coords="11,87,53,101,74,88,105,91,117,55,103,43,89,50,76,43,77,30,62,37,43,30,40,38,33,75,21,75,13,82">
                                <area shape="poly" name="RR" coords="74,13,74,18,82,25,84,41,93,40,102,31,96,21,97,9,90,11">
                                <area shape="poly" name="PA" coords="112,33,114,40,127,50,117,82,121,95,162,99,174,77,173,68,193,48,172,54,158,55,145,45,133,25">
                                <area shape="poly" name="AP" coords="145,25,153,23,157,13,164,29,153,41">
                                <area shape="poly" name="MA" coords="196,50,185,72,194,90,212,82,215,59">

                                <area shape="poly" name="TO" coords="179,83,165,120,189,128,185,101">
                                <area shape="poly" name="GO" coords="159,166,148,157,165,131,188,136,170,151">
                                <area shape="poly" name="PI" coords="201,92,216,86,223,64,228,85,219,98,207,99,206,107,199,107">
                                <area shape="poly" name="RJ" coords="206,201,202,190,214,189,218,181,226,187">
                                <area shape="poly" name="MG" coords="171,164,190,162,192,145,205,140,217,146,224,154,217,169,212,183,193,183,185,170">
                                <area shape="poly" name="ES" coords="236,167,228,162,221,177,226,183">
                                <area shape="poly" name="BA" coords="198,113,196,134,213,133,230,139,235,146,231,157,235,160,240,142,241,127,249,124,243,113,243,105,234,106,225,107,215,107,207,115">
                                <area shape="poly" name="CE" coords="230,59,235,86,241,86,252,70,239,61">
                                <area shape="poly" name="SE" coords="250,108,248,113,251,118,257,113,252,109">

                                <area shape="poly" name="AL" coords="266,102,258,104,251,102,260,110,266,104">
                                <area shape="poly" name="PE" coords="269,94,269,99,262,99,256,101,251,98,246,98,239,96,234,100,231,95,234,92,243,93,251,94,255,96">
                                <area shape="poly" name="PB" coords="269,85,262,85,257,88,253,85,248,87,257,90,263,91,268,89">
                                <area shape="poly" name="RN" coords="256,73,249,81,256,80,257,83,270,82,265,76">
                                <area shape="poly" name="DF" coords="168,162,171,153,183,149,182,161">
                            </map>
                            <p style='textalign: right; font-weight: bold;'><?=traduz('Selecione o Estado')?>:</p>
                            <img src="../externos/mapa_rede/imagens/mapa_azul.gif" usemap="#Map2" border="0">
                        </div>
                        <?php
                        if ($login_fabrica == 117) {
                        ?>
                        <div id='sel_linha'>
                            <label for='mapa_linha'><?=traduz('Selecione uma Macro-Família')?></label><br>
                            <select title='Selecione uma linha' name='mapa_linha' id='mapa_linha'>
                                <?php
                                $sql_linha = "SELECT DISTINCT tl.linha,
                                        tl.nome,
                                        tl.ativo as ativo
                                    FROM tbl_macro_linha_fabrica AS tmlf
                                        JOIN tbl_macro_linha AS tml ON tmlf.macro_linha = tml.macro_linha
                                        JOIN tbl_linha AS tl ON tmlf.linha = tl.linha
                                    WHERE tmlf.fabrica = $login_fabrica
                                        AND tml.ativo IS TRUE
                                        AND tl.ativo IS TRUE
                                    ORDER BY nome;";
                                $res_linha = pg_query($con,$sql_linha);
                                if (pg_num_rows($res_linha) > 0) {
                                    echo "<option value='' selected>".traduz("Todas")."</option>";
                                    for ($x=0; $x < pg_num_rows($res_linha); $x++) {
                                        $linha_s = pg_fetch_result($res_linha, $x, linha);
                                        $linha_s_nome = pg_fetch_result($res_linha, $x, nome);
                                        echo "<option value='$linha_s'>";
                                        echo $linha_s_nome;
                                        echo "</option>";
                                    }
                                }
                                ?>

                            </select>
                        </div>
                        <?php
                        }
                        ?>

                        <label for='estado'><?=traduz('Selecione o Estado')?></label><br>
                        <select title='<?=traduz("Selecione o Estado")?>' name='mapa_estado' id='mapa_estado' onchange="montaComboCidade2();">
                            <option></option>
        <?              foreach ($estados as $sigla=>$estado_nome) {// a variavel $estado está em ../helpdesk/mlg_funciones.php
                            echo "\t\t\t\t<option value='$sigla'>$estado_nome</option>\n";
                        }
        ?>              </select>
                        <div id='sel_cidade'>
                            <label for='mapa_cidades'><?=traduz('Selecione uma cidade')?></label><br>
                            <select title='<?=traduz("Selecione uma cidade")?>' name='mapa_cidades' id='mapa_cidades'>
                                <option></option>
                            </select>
                        </div>
                    </fieldset>
                </div>
            </div>

        <? if($login_fabrica == 59) { ?>
            País
            <select class='frm' name='pais'>
            <?  $sql = "SELECT DISTINCT UPPER(tbl_posto_fabrica.contato_pais) AS contato_pais
                FROM tbl_posto_fabrica
                WHERE /* tbl_posto_fabrica.credenciamento = 'CREDENCIADO'AND  */
                tbl_posto_fabrica.fabrica = $login_fabrica
                ORDER BY contato_pais";
                $res = pg_query($con, $sql);
                $contadorres = pg_num_rows($res);
                if(pg_num_rows($res)>0){
                    echo "<option value='' selected>".traduz("Todos")."</option>";
                    for($x=0; $x<$contadorres; $x++){
                        $nome_pais = pg_fetch_result($res, $x, contato_pais);
                        echo "<option value='$nome_pais'>";
                        echo $nome_pais;
                        echo "</option>";
                    }
		}
                ?>
                </select>
            <? }else{ ?>
                <?=(in_array($login_fabrica, array(117))) ? '' : 'País';?>
                <select class='frm' name='pais' style="<?=(in_array($login_fabrica, array(117))) ? 'display:none;' : '';?>">
                    <option value='BR' selected>Brasil</option>
                </select>
            <? } ?>

        <? if($login_fabrica == 59) { ?>
            Estado
            <select class='frm' name='estado'>
            <?  $sql = "SELECT DISTINCT UPPER(tbl_posto_fabrica.contato_estado) AS contato_estado
                FROM tbl_posto_fabrica
                WHERE /* tbl_posto_fabrica.credenciamento = 'CREDENCIADO' AND */
                tbl_posto_fabrica.fabrica = $login_fabrica
                ORDER BY contato_estado";
                $res = pg_query($con, $sql);
                $contadorres = pg_num_rows($res);
                if(pg_num_rows($res)>0){
                    echo "<option value='' selected>".traduz('Todos')."</option>";
                    for($x=0; $x<$contadorres; $x++){
                        $nome_estado = pg_fetch_result($res, $x, contato_estado);
                        echo "<option value='$nome_estado'>$nome_estado</option>";
                    }
		}
                ?>
                </select>
            <? }else{ ?>
                    <?=(in_array($login_fabrica, array(117))) ? '' : 'Estado';?>
                    <select class='frm' name='estado' onchange="montaComboCidade(this.value)" style="<?=(in_array($login_fabrica, array(117))) ? 'display:none;' : '';?>">
                        <option value='00' selected>Todos</option>
                        <option value='SP'         >São Paulo</option>
                        <option value='RJ'         >Rio de Janeiro</option>
                        <option value='PR'         >Paraná</option>
                        <option value='SC'         >Santa Catarina</option>
                        <option value='RS'         >Rio Grande do Sul</option>
                        <option value='MG'         >Minas Gerais</option>
                        <option value='ES'         >Espírito Santo</option>
                        <option value='BR-CO'      >Centro-Oeste</option>
                        <option value='BR-NE'      >Nordeste</option>
                        <option value='BR-N'       >Norte</option>
                    </select>
            <? } ?>
                    <?=(in_array($login_fabrica, array(117))) ? '' : 'Cidade';?>
                    <select class='frm' name='cidade' id='cidade_mapa' style="<?=(in_array($login_fabrica, array(117))) ? 'display:none;' : '';?>">
                        <option value='' selected>Selecione uma cidade</option>
                    </select>
                    <?php
                    if ($login_fabrica == 117) {?>
                        <select class="frm" name="linha_elgin" id="linha_elgin" style="display: none;">
                        <?php
                        /*$sql_linha = "SELECT distinct tbl_macro_linha.descricao,
                                             tbl_macro_linha.macro_linha
                                        FROM tbl_macro_linha
                                        JOIN tbl_macro_linha_fabrica ON tbl_macro_linha.macro_linha = tbl_macro_linha_fabrica.macro_linha
                                        WHERE tbl_macro_linha.ativo IS TRUE
                                        AND fabrica = {$login_fabrica}
                                        ORDER BY tbl_macro_linha.descricao;";*/
                        $sql_linha = "SELECT DISTINCT tl.linha as macro_linha,
                                        tl.nome as descricao,
                                        tl.ativo as ativo
                                    FROM tbl_macro_linha_fabrica AS tmlf
                                        JOIN tbl_macro_linha AS tml ON tmlf.macro_linha = tml.macro_linha
                                        JOIN tbl_linha AS tl ON tmlf.linha = tl.linha
                                    WHERE tmlf.fabrica = $login_fabrica
                                        AND tml.ativo IS TRUE
                                        AND tl.ativo IS TRUE
                                    ORDER BY descricao;";
                        $res_linha = pg_query($con,$sql_linha);
                        if (pg_num_rows($res_linha) > 0) {
                            echo "<option value='' selected>Todas</option>";
                            for ($x=0; $x < pg_num_rows($res_linha); $x++) {
                                $linha_s = pg_fetch_result($res_linha, $x, macro_linha);
                                $linha_s_nome = pg_fetch_result($res_linha, $x, descricao);
                                echo "<option value='$linha_s'>";
                                echo $linha_s_nome;
                                echo "</option>";
                            }
                        }
                        ?>
                        </select>
                    <?php
                    }
                    ?>

                    <input class='frm' type='button' name='btn_mapa' id='btn_mapa' value='mapa' style="<?=(in_array($login_fabrica, array(117))) ? 'display:none;' : '';?>">
                    </font>
                </td>
            </tr>
        </table>
    </form>
<?php } ?>

<br />
<?php
if ($login_fabrica == 115 && !empty($posto)) {
    /**
     * - Mostra a classificação do posto e
     * quanto tempo ele permanece nesse estado
     */
    $sqlTipoRank = "
        SELECT  tbl_posto_fabrica.parametros_adicionais::JSONB->>'ultima_categoria'   AS categoria_lvl,
                tbl_posto_fabrica.parametros_adicionais::JSONB->'tempo'               AS tempo_lvl
        FROM    tbl_posto_fabrica
        WHERE   fabrica = $login_fabrica
        AND     posto   = $posto
    ";
    $resTipoRank = pg_query($con,$sqlTipoRank);

    $categoriaLvl   = pg_fetch_result($resTipoRank,0,categoria_lvl);
    $tempoLvl       = pg_fetch_result($resTipoRank,0,tempo_lvl);

    switch($categoriaLvl) {
        case "standard":
            $corFundo = "#C60";
            break;
        case "master":
            $corFundo = "#C0C0C0";
            break;
        case "premium":
            $corFundo = "#FF3";
            break;
    }
?>
<span style="background-color:<?=$corFundo?>;">Este posto é <?=strtoupper($categoriaLvl)?> há <?=$tempoLvl?> mes(es)</span>
<?php
}

if (strlen($posto) > 0 AND $login_fabrica == 42) {
    $sql = "SELECT  tbl_roteiro.roteiro,
            tbl_roteiro.tipo_roteiro,
            to_char(tbl_roteiro.data_inicio::date, 'dd/mm/yyyy') AS data_inicio,
            tbl_roteiro_visita.checkin,
            tbl_roteiro.solicitante,
            tbl_roteiro_posto.roteiro_posto,
            tbl_tecnico.nome AS tecnico
        FROM tbl_roteiro
            JOIN tbl_roteiro_posto ON tbl_roteiro_posto.roteiro = tbl_roteiro.roteiro
            JOIN tbl_roteiro_tecnico ON tbl_roteiro_tecnico.roteiro = tbl_roteiro.roteiro
            JOIN tbl_posto ON tbl_posto.cnpj = tbl_roteiro_posto.codigo
            JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto 
                AND tbl_posto_fabrica.fabrica = $login_fabrica 
            JOIN tbl_roteiro_visita ON tbl_roteiro_visita.roteiro_posto = tbl_roteiro_posto.roteiro_posto
            JOIN tbl_tecnico ON tbl_tecnico.tecnico = tbl_roteiro_tecnico.tecnico 
                AND tbl_tecnico.fabrica = tbl_roteiro.fabrica
        WHERE tbl_roteiro.fabrica = $login_fabrica 
            AND tbl_posto_fabrica.posto = $posto
        ORDER BY tbl_roteiro.data_inicio";
    $resVisitaTecnica = pg_query($con,$sql);

    if(pg_num_rows($resVisitaTecnica) > 0){
        ?>
        <table id="table_visita_tecnica" class='table table-striped table-bordered table-hover border_visita_tecnica' align="center">
            <thead>
                <tr class='titulo_coluna' >
                    <td colspan="7" style="text-align: center;">VISITA TÉCNICA</td>
                </tr>
                <tr class='titulo_coluna' >
                    <th nowrap>Roteiro</th>
                    <th nowrap>Tipo Roteiro</th>
                    <th nowrap>Solicitante</th>
                    <th nowrap>Data Início</th>
                    <th nowrap>Responsável pelo roteiro</th>
                    <th nowrap>Checkin</th>
                    <th nowrap>Ação</th>
                </tr>
            </thead>
            <tbody>
            <?php
            for($i=0; $i < pg_num_rows($resVisitaTecnica); $i++){
                $roteiro       = pg_fetch_result($resVisitaTecnica, $i, "roteiro");
                $tipo_roteiro  = pg_fetch_result($resVisitaTecnica, $i, "tipo_roteiro");
                $data_inicio   = pg_fetch_result($resVisitaTecnica, $i, "data_inicio");
                $solicitante   = pg_fetch_result($resVisitaTecnica, $i, "solicitante");
                $tecnico       = pg_fetch_result($resVisitaTecnica, $i, "tecnico");
                $checkin       = pg_fetch_result($resVisitaTecnica, $i, "checkin");
                $roteiro_posto = pg_fetch_result($resVisitaTecnica, $i, "roteiro_posto");

                $datacheckin = new DateTime($checkin);
                ?>
                <tr >
                    <td class="tac border_visita_tecnica" nowrap><?=$roteiro?></td>
                    <td class="tac border_visita_tecnica" nowrap><?=$tipo_roteiro?></td>
                    <td class="tal border_visita_tecnica" nowrap><?=$solicitante?></td>
                    <td class="tac border_visita_tecnica" nowrap><?=$data_inicio?></td>
                    <td class="tal border_visita_tecnica" nowrap><?=$tecnico?></td>
                    <td class="tac border_visita_tecnica" nowrap><?=$datacheckin->format('d/m/Y H:i:s')?></td>
                    <td class="tal border_visita_tecnica" nowrap>
                        <button type="button" class="btn-solicita btnVisitaTecnica" data-posto="<?=$posto?>" id="btnVisitaTecnica<?=$roteiro_posto?>" title="Ver dados da visita">Visualizar</button>
                    </td>
                </tr>
                <?php
            }
            ?>
            </tbody>
        </table>
        <br/>
        <?php
    }

    $sqlAvaliacao = "SELECT tbl_pesquisa.pesquisa FROM tbl_pesquisa
        WHERE tbl_pesquisa.ativo
            AND tbl_pesquisa.categoria = 'questionario_avaliacao'
            AND tbl_pesquisa.fabrica = {$login_fabrica}";
    $resAvaliacao = pg_query($con, $sqlAvaliacao);

    if (pg_num_rows($resAvaliacao) > 0) {

        $pesquisaAvaliacao = pg_fetch_result($resAvaliacao, 0, 'pesquisa');

        $sqlResposta = "SELECT campos_adicionais
                        FROM tbl_resposta
                        WHERE pesquisa = {$pesquisaAvaliacao}
                        AND posto = {$posto}
                        ORDER BY resposta DESC
                        LIMIT 1";
        $resResposta = pg_query($con, $sqlResposta);

        $bloqueiaAvaliacao = false;
        if (pg_num_rows($resResposta) > 0) {

            $arrCamposAdicionais  = json_decode(pg_fetch_result($resResposta, 0, "campos_adicionais"), true);
            $dataProximaAvaliacao = $arrCamposAdicionais["dataProximaAvaliacao"];

        }

        ?>
        <button data-pesquisa="<?= $pesquisaAvaliacao ?>" data-posto="<?= $posto ?>" class="btn-avaliacao-posto" title="Responder Pesquisa" >
            Realizar Avaliação Técnica
        </button>
        <br /><br />
        <form method="POST" action="relatorio_respostas_pesquisa.php" target="_blank">
            <input type="hidden" name="btn_acao" value="Pesquisar" />
            <input type="hidden" name="posto" value="<?= $posto ?>" />
            <input type="hidden" name="pesquisa" value="<?= $pesquisaAvaliacao ?>" />
            <input type="hidden" name="descricao_posto" value="<?= $nome ?>" />
            <input type="hidden" name="codigo_posto" value="<?= $codigo ?>" />
            <input type="hidden" />
            <button class="btn-visualiza-pesquisas" title="Responder Pesquisa" >
                Consultar Pesquisas Realizadas
            </button>
        </form>
        <br><br>

        <?php
        if (!empty($dataProximaAvaliacao)) { ?>
             <span style="color: darkred">Próxima Visita:</span> <strong><?= mostra_data($dataProximaAvaliacao) ?></strong><br /><br />
        <?php
        }

    } 
}
?>
<form name="frm_posto" method="post" action="<?=$PHP_SELF?>" <?=(in_array($login_fabrica, array(1,50))) ? "enctype='multipart/form-data'" : ""?>>

<?php
if (!empty($msg_erro) and !empty($novo_posto)) {
    unset($posto);
}
?>
<input type="hidden" name="posto" value="<?=$posto?>">

<?    
if($login_fabrica == 1){
    echo "<input type='hidden' name='credenciamentoObs' value='$credenciamento'>";
}

    echo "<TABLE class='formulario' style='margin: 0 auto; width: 700px; border: 0;' >";
    echo "<TR>";
    echo "<TD align='left'><font size='2' face='verdana' ";
    if ($credenciamento == 'CREDENCIADO') {
        $colors = "color:#3300CC";
        if ($login_fabrica == 177) {
            $button_credenciamento = "<button type='button' data-posto='".$posto."' class='btn btn-danger btn-descredenciar' style='font-weight: bold; float: right; background: #596d9b; color: #fff; border-color: #596d9b;'>".traduz("Descredenciar")."</button>";    
            $button_em_descredenciamento = "<button type='button' data-posto='".$posto."' class='btn btn-danger btn-emdescredenciamento' style='font-weight: bold; float: right; background: #596d9b; color: #fff; border-color: #596d9b;'>".traduz('Em Descredenciamento')."</button>";  
        }
    } else if ($credenciamento == 'DESCREDENCIADO') {
        $colors = "color:#F3274B";
        if ($login_fabrica == 177) {
            $button_credenciamento = "<button type='button' data-posto='".$posto."' class='btn btn-success btn-credenciar' style='font-weight: bold; float: right; background: #596d9b; color: #fff; border-color: #596d9b;'>".traduz('Credenciar')."</button>";
        }
    } else if ($credenciamento == 'EM DESCREDENCIAMENTO') { 
        $colors = "color:#FF9900";
        if ($login_fabrica == 177) {
            $button_credenciamento = "<button type='button' data-posto='".$posto."' class='btn btn-success btn-credenciar' style='font-weight: bold; float: right; background: #596d9b; color: #fff; border-color: #596d9b;'>".traduz('Credenciar')."</button>";
        }
    } else if ($credenciamento == 'EM CREDENCIAMENTO') {
        $colors = "color:#006633";
    } else if ($credenciamento == "EM APROVAÇÃO" AND $login_fabrica == 177) {
        //$colors = "color:#FFFF66;";
        $colors = "color:#3300CC";
    }

    # HD 110541
    if(($login_fabrica==11 or $login_fabrica == 172) AND strlen($data_credenciamento)>0){
        if ($credenciamento == 'CREDENCIADO')
            $show_date_credenciamento = "EM: $data_credenciamento";
        else if ($credenciamento == 'DESCREDENCIADO'){
            $sql_X2 = "select TO_CHAR(tbl_credenciamento.data,'DD/MM/YYYY') AS data from tbl_credenciamento where fabrica= $login_fabrica and posto=$posto and status='CREDENCIADO'";
            $res_X2 = pg_query ($con,$sql_X2);
            if (pg_num_rows ($res_X2) > 0) {
                    $data_credenciamento_2   = trim(pg_fetch_result($res_X2,0,data));
                    $show_date_credenciamento .= "CREDENCIADO EM: $data_credenciamento_2 E DESCREDENCIADO EM $data_credenciamento";
            }else{
                $show_date_credenciamento .= "DESCREDENCIADO EM $data_credenciamento";;
            }
        }
        else if ($credenciamento == 'EM DESCREDENCIAMENTO')
            $show_date_credenciamento = "DESDE: $data_credenciamento";
        else if ($credenciamento == 'EM CREDENCIAMENTO')
            $show_date_credenciamento = "DESDE: $data_credenciamento";
        else if ($credenciamento == 'REPROVADO') {
            $show_date_credenciamento = "EM: $data_credenciamento";
        }
    }

    if (strtolower($credenciamento) == 'pre cadastro em apr' AND $login_fabrica == 1) {
       $credenciamento = "PRÉ CADASTRO - EM APROVAÇÃO";
    }
    elseif ($credenciamento == 'pre_cadastro' AND $login_fabrica == 1) {
       $credenciamento = "PRÉ CADASTRO";
    }
    elseif ($credenciamento == 'Pr&eacute; Cad apr' AND $login_fabrica == 1) {
       $credenciamento = "PRÉ CADASTRO - APROVADO";
    }elseif ($credenciamento == 'Pr&eacute; Cad rpr' AND $login_fabrica == 1) {
       $credenciamento = "PRÉ CADASTRO - REPROVADO";
    }elseif ($credenciamento == 'Descredenciamento' AND $login_fabrica == 1) {
       $credenciamento = "DESCREDENCIAMENTO - EM APROVAÇÃO";
    }elseif ($credenciamento == 'Descred rep' AND $login_fabrica == 1) {
       $credenciamento = "DESCREDENCIAMENTO - REPROVADO";
    }
    elseif ($credenciamento == 'Descred apr' AND $login_fabrica == 1) {
       $credenciamento = "DESCREDENCIAMENTO - APROVADO";
    }

    echo "><B>  ";
    if (in_array($login_fabrica, [177])) {
        if ($credenciamento == "EM APROVAÇÃO") {
            echo "<a style='$colors'>";
        }
        echo $button_credenciamento;
    } else {
        echo "<a href='credenciamento.php?codigo=$codigo&posto=$posto&listar=1' style='$colors'>";
    }

    # HD 110541
    if(($login_fabrica==11 or $login_fabrica == 172) AND $credenciamento == 'DESCREDENCIADO'){
        echo $show_date_credenciamento;
    }else{
        if($login_fabrica == 151){
            echo $credenciamento;
        }else{
            echo traduz($credenciamento)."  ".$show_date_credenciamento;
        }

    }
    echo "</B></font></TD>";
    if($login_fabrica == 151){
        $sql_X = "SELECT TO_CHAR(tbl_credenciamento.data,'DD/MM/YYYY') AS data_geracao,
                tbl_admin.nome_completo
                FROM tbl_credenciamento
                LEFT JOIN tbl_admin on tbl_admin.admin = tbl_credenciamento.confirmacao_admin
               WHERE tbl_credenciamento.fabrica = $login_fabrica
                 AND tbl_credenciamento.posto   = $posto
            ORDER BY tbl_credenciamento.data DESC
               LIMIT 1";
        $res_X = pg_query ($con,$sql_X);

        if (pg_num_rows ($res_X) > 0) {
            $data_geracao   = trim(pg_fetch_result($res_X,0,'data_geracao'));
            $nome_completo  = trim(pg_fetch_result($res_X,0,'nome_completo'));

            if(strlen($nome_completo)==0){
                $nome_completo = "AUTOMÁTICO";
            }

            echo "<td>".traduz("Data de Geração").": <b>$data_geracao</b></td>";
            echo "<td>".traduz("Usuário").": <b>$nome_completo</b></td>";
        }


    }

    if ($login_fabrica == 177) {
        echo "<td align='right' nowrap>";
        echo $button_em_descredenciamento;
        echo "</td>";
    }

    if (!in_array($login_fabrica, [193])) {

    echo "<td align='right' nowrap>";
//  if (strlen ($posto) > 0 and $login_fabrica <> 3) {
//  HD 148558 pediu para colocar também para Britânia

    if (strlen ($posto) > 0 ){
        $resX = pg_query ("SELECT tbl_posto.nome, tbl_posto_fabrica.codigo_posto FROM tbl_posto_fabrica JOIN tbl_posto ON tbl_posto_fabrica.distribuidor = tbl_posto.posto AND tbl_posto_fabrica.fabrica = $login_fabrica WHERE tbl_posto_fabrica.posto = $posto");

        if (pg_num_rows ($resX) > 0) {
            echo traduz("Distribuidor").": " . pg_fetch_result ($resX,0,codigo_posto) . " - " . pg_fetch_result ($resX,0,nome) ;
        }else{
            echo traduz("Atendimento direto 2");
        }
    }
    echo "</td>";
    
    }

    echo "</TR>";
    echo "</TABLE>";
?>
<? if($login_fabrica == 91 or $login_fabrica == 160 or $replica_einhell){
        $sql = "SELECT valor_km FROM tbl_fabrica WHERE fabrica = $login_fabrica;";
        $res = pg_query($con,$sql);
        $valor_padrao = pg_result($res,0,0);
        if(!empty($valor_padrao) AND $valor_padrao > 0){
?>
            <div class='texto_avulso'>
                <?=traduz('Caso não seja preenchido o campo "Valor/KM" o sistema assumirá o valor padrão')?> R$ <? echo number_format($valor_padrao,2,',','.'); ?>. <br><?=traduz('Para alterar o valor  padrão, entre em contato com a Telecontrol.')?>
            </div>
            <br />
    <? } ?>
<? } ?>

<?php if (isset($_GET['posto']) && !isset($_POST['endereco']) && in_array($login_fabrica, [180,181,182])) { 

    $query = "SELECT contato_endereco AS endereco,
            contato_numero AS numero,
            contato_complemento AS complemento,
            contato_bairro AS bairro,
            contato_cep AS cep,
            contato,
            fone 
        FROM tbl_posto_fabrica 
            JOIN tbl_posto USING(posto)
        WHERE posto = {$posto} AND fabrica = {$login_fabrica}";
    $result = pg_query($con, $query);

    $endereco     = pg_fetch_result($result, 0, endereco);
    $numero       = pg_fetch_result($result, 0, numero);
    $complemento  = pg_fetch_result($result, 0, complemento);
    $bairro       = pg_fetch_result($result, 0, bairro);
    $cep          = pg_fetch_result($result, 0, cep);
    $contato_nome = pg_fetch_result($result, 0, contato);
    $fone         = pg_fetch_result($result, 0, fone);

} ?>

<table class="formulario" style="margin: 0 auto; width: 700px; border: 0;"cellpadding="1" cellspacing="3">
    <tr>
        <td colspan="5" class='titulo_tabela'>
            <?=traduz('Informações Cadastrais')?>
        </td>
    </tr>

    <?
    //HD 11308 11/1/2008
    if($login_fabrica == 15){?>
    <tr align='left'>
        <td style="color: rgb(168, 0, 0);"><?=traduz('CNPJ')?></td>
        <td><?=traduz('I.E.')?></td>
        <td><?=traduz('I.M.')?></td>
    </tr>
    <tr align='left'>
        <td><input class='<?php echo (in_array('nome', $campos))? 'frm_obrigatorio' : "frm" ?>'
onfocus="this.className='frm';"  type="text" name="cnpj" id="cnpj" style="float: left; width: 143px;" maxlength="18" value="<? echo $cnpj ?>"><a href="#"><img src='imagens/lupa.png' style="cursor:pointer" border='0' align='left' onclick="javascript: fnc_pesquisa_posto (document.frm_posto.nome,document.frm_posto.cnpj,document.frm_posto.codigo,'cnpj')"></a></td>
        <td><input class='frm' type="text" name="ie" style="float: left; width: 143px;" maxlength="20" value="<? echo $ie ?>" >
        <input type="hidden" name="ie_anterior" value="<?=$ie_anterior?>">
        </td>
        <td><input class='frm' type='text' name='im' style="float: left; width: 173px;" maxlength='40' value="<? echo $im ?>"></td>
    </tr>

    <tr align='left'>
        <td><?=traduz('Telefone')?></td>
        <td align="left"><?=traduz('Telefone 2')?></td>
        <td align="left"><?=traduz('Telefone 3')?></td>
        <td align="left"><?=traduz('Celular')?></td>

    </tr>

    <tr align='left'>
        <td align="left"><input class='frm telefone' type="text" name="fone" style="float: left; width: 106px;" maxlength="20" value="<? echo $fone ?>"></td>
        <td align="left">
            <input type="text" class='frm telefone' name="fone2" id="fone2" style="float: left; width: 106px;" maxlength="20" value="<?echo $fone2?>" />
        </td>
        <td align="left">
            <input type="text" class='frm telefone' name="fone3" id="fone3" style="float: left; width: 106px;" maxlength="20" value="<?echo $fone3?>" />
        </td>
        <td align="left">
            <input type="text" class='frm telefone' name="contato_cel" id="contato_cel" style="float: left; width: 106px;" maxlength="20" value="<?echo $contato_cel?>" />
        </td>
    </tr>

    <tr>

        <td align="left">Fax</td>
        <td style="color: rgb(168, 0, 0);"><?=traduz('Contato')?></td>
    </tr>

    <tr>

        <td align="left"><input class='frm telefone' type="text" name="fax" style="float: left; width: 106px;" maxlength="20" value="<? echo $fax ?>"></td>
        <td align="left"><input type="text" name="contato"  style="float: left; width: 143px;" maxlength="30" value="<? echo $contato_nome ?>" class='<?php echo (in_array('contato', $campos))? 'frm_obrigatorio' : "frm" ?>'
onfocus="this.className='frm';" ></td>
    </tr>

    <?}else{?>
        <?php 
            if (in_array($login_fabrica, [195])) {
                $sqlCategoria = "SELECT * FROM tbl_categoria_posto WHERE categoria_posto AND fabrica = {$login_fabrica}";
                $resCategoria = pg_query($con,$sqlCategoria);
                $descricaoCategoria = pg_fetch_result($resCategoria, 0, 'nome');
                $imgMedalha = "";
                if ($descricaoCategoria == "Ouro") {
                    $imgMedalha = "<img width='100' src='imagens/medalha_ouro.png'>";
                }
                if ($descricaoCategoria == "Prata") {
                    $imgMedalha = "<img width='100' src='imagens/medalha_prata.png'>";
                }
                if ($descricaoCategoria == "Bronze") {
                    $imgMedalha = "<img width='100' src='imagens/medalha_bronze.png'>";
                }
                if (strlen($imgMedalha) > 0) {
                echo '
                    <tr align="center">
                        <td colspan="100%">'.$imgMedalha.'</td>
                    </tr>';
                }
            }
        ?>
    <tr align='left'>
        <?php if (in_array($login_fabrica, [30,52,74,85,158,173,183])) {?>
            <td><?=traduz('CNPJ / CPF')?></td>
        <?php }else{?>
            <td style="color: rgb(168, 0, 0);"><?=traduz('CNPJ')?></td>
        <?php }?>
        <td>I.E.</td>
    <?php
    if(in_array($login_fabrica, array(81, 114))){
        ?>
        <td><?=traduz('Fone')?></td>
        <td><?=traduz('Fone 2')?></td>
    </tr>
    <?php
    }
    else if ($login_fabrica == 40)
    {
    ?>
        <td><?=traduz('Fone')?></td>
        <td><?=traduz('Fone 2')?></td>
    <?
    }
    elseif (in_array($login_fabrica, array(88, 176))) {
    ?>
        <td><?=traduz('Fone')?></td>
        <td><?=traduz('Celular')?></td>
    <?
    }
    else{
        if (in_array($login_fabrica, [175,177,178,193])){
            $label_fax = "Celular";
        } else if (in_array($login_fabrica, array(11,172))) {
	    $label_fax = "FONE 2";
	} else {
            $label_fax = "Fax";
        }

    ?>
        <td><?=traduz('Fone')?></td>
        <td><?=$label_fax?></td>
    <?php
    }
    ?>
    </tr>
    <?php
        if(in_array($login_fabrica, array(81, 114,154))){
        ?>
    <tr align='left'>
        <td nowrap><input type="text" name="cnpj" class='<?php echo (in_array('cnpj', $campos))? 'frm_obrigatorio' : "frm" ?>' onfocus="this.className='frm';"  id="cnpj" style="float: left; width: 143px" maxlength="18" value="<? echo $cnpj ?>"><a href="#"><img src='imagens/lupa.png' style="cursor:pointer" border='0' align='left' onclick="javascript: fnc_pesquisa_posto (document.frm_posto.nome,document.frm_posto.cnpj,document.frm_posto.codigo,'cnpj')"></a></td>
        <td><input class='frm' type="text" name="ie" style="float: left; width: 143px" maxlength="15" value="<? echo $ie ?>" >
            <input type="hidden" name="ie_anterior" value="<?=$ie_anterior?>">
        </td>
        <td><input class='frm telefone' type="text" name="fone" style="float: left; width: 106px;" maxlength="12" value="<? echo $fone ?>"></td>
        <td><input class='frm telefone' type="text" name="fone2" style="float: left; width: 106px;" maxlength="12" value="<? echo $fone2 ?>"></td>
    </tr>
    <tr align='left'>
        <td><?=traduz('Fax')?></td>
        <td style="color: rgb(168, 0, 0);"><?=traduz('Contato')?></td>
    </tr>
    <tr align='left'>
        <td><input class='frm telefone' type="text" name="fax" style="float: left; width: 106px;" maxlength="15" value="<? echo $fax ?>"></td>
        <td><input  class='frm'type="text" name="contato" size="15" maxlength="15" value="<? echo $contato_nome ?>" style="width:100px"></td>
    </tr>
        <?php
        }
        else if ($login_fabrica == 40)
        {
        ?>
            <td nowrap>
            <input class='<?php echo (in_array('cnpj', $campos))? 'frm_obrigatorio' : "frm" ?>' onfocus="this.className='frm';" type="text" name="cnpj" id="cnpj" style="float: left; width: 143px;" maxlength="18" value="<? echo $cnpj ?>"><a href="#"><img src='imagens/lupa.png' style="cursor:pointer" border='0' align='left' onclick="javascript: fnc_pesquisa_posto (document.frm_posto.nome,document.frm_posto.cnpj,document.frm_posto.codigo,'cnpj')"></a></td>
            <td><input class='frm' type="text" name="ie" style="float: left; width: 143px;" maxlength="20" value="<? echo $ie ?>" >
            <input type="hidden" name="ie_anterior" value="<?=$ie_anterior?>">
            </td>
            <td><input class='frm telefone' type="text" name="fone" style="float: left; width: 106px;" maxlength="15" value="<? echo $fone ?>"></td>
            <td><input class='frm telefone' type='text' name='fone2' style="float: left; width: 106px;" maxlength='15' value='<?=$fone2?>'></td>
        </tr>
        <tr align='left'>
            <td><?=traduz('Fax')?></td>
            <td><?=traduz('Contato')?></td>
        </tr>
        <tr align='left'>
            <td><input class='frm telefone' type="text" name="fax" size="15" maxlength="15" value="<? echo $fax ?>"></td>
            <td><input  class='frm' type="text" name="contato" style="float: left; width: 106px;" maxlength="15" value="<? echo $contato_nome ?>" style="width:100px"></td>
        </tr>
        <?
        }
        else{
        ?>
        <tr>
            <td nowrap>
                <input class='<?php echo (in_array('cnpj', $campos))? 'frm_obrigatorio' : "frm" ?>' onfocus="this.className='frm';" type="text" name="cnpj" id="cnpj" style="float: left; width: 143px;" maxlength="18" value="<? echo $cnpj ?>"><a href="#"  class='btn-lupa-cnpj'><img src='imagens/lupa.png' style="cursor:pointer" border='0' align='left' onclick="javascript: fnc_pesquisa_posto (document.frm_posto.nome,document.frm_posto.cnpj,document.frm_posto.codigo,'cnpj')"></a>
            </td>
            <td><input class='frm' type="text" name="ie" style="float: left; width: 143px;" maxlength="20" value="<? echo $ie ?>" >
            <input type="hidden" name="ie_anterior" value="<?=$ie_anterior?>">
            </td>
            <td><input class='frm telefone' type="text" name="fone" style="float: left; width: 106px;" maxlength="15" value="<? echo $fone ?>"></td>
            <td><input class='frm telefone' type="text" name="fax" style="float: left; width: 106px;" maxlength="15" value="<? echo $fax ?>"></td>
        </tr>

        <?php
        if(!empty($fone) AND strlen(preg_replace('/(\D)/i', '', $fone)) < 10){?>
        <tr>
            <td>&nbsp;</td>
            <td>&nbsp;</td>
            <td colspan='2' style='color: #F00; text-align: left; font-size: 11px;'>
            <?php if (!in_array($login_fabrica, [180,181,182])) { ?>
                <b><?=traduz('Telefone Inválido')?>!</b> <br /><?=traduz('O formato do telefone deve ser: (14) 3402 6588')?>
            <?php } ?>
            </td>
            <td>&nbsp;</td>
        </tr>
        <?php }?>
        <?php
        }
    }
    $colspan = ($login_fabrica == 15) ? 1 : 3;

    ?>
    <?php if ($login_fabrica == 30): ?>
    <tr>
        <td align="left"><?=traduz('Fone Celular 1')?></td>
        <td align="left" colspan="3"><?=traduz('Fone Celular 2')?></td>
    </tr>
    <tr>
        <td align="left">
            <input class='frm telefone' type="text" name="fone2" id="fone2" style="float: left; width: 106px;" maxlength="15" value="<? echo $fone2 ?>">
        </td>
        <td align="left" colspan="3">
            <input class='frm telefone' type="text" name="fone3" id="fone3" style="float: left; width: 106px;" maxlength="15" value="<? echo $fone3 ?>">
        </td>
    </tr>
    <?php endif ?>

    <tr style="text-align: left;">
        <?
        if (!in_array($login_fabrica, Array(15, 40, 81, 114)))
        {
            if(!in_array($login_fabrica, Array(175))){
        ?>
                <td style="color: rgb(168, 0, 0);"><?=traduz('Contato')?></td>
        <?
            }else{
        ?>
                <td><?=traduz('Contato')?></td>
        <?
            }
        }

        if ($login_fabrica == 171){
        ?>
            <td><?=traduz('Código Ferragens Negrão')?></td>
        <?php 
        }

        ?>
        <td style="color: rgb(168, 0, 0);">
        <?php
            echo ($login_fabrica <> 20) ? traduz("Código") : traduz("Código Cliente");
        ?>

        </td>
    <?php if($login_fabrica != 74){?>
        <td colspan="2" style="color: rgb(168, 0, 0);"><?=traduz('Razão Social')?></td>
    <?php }else{ ?>
        <td><?=traduz('Fone 2')?></td>
        <td><?=traduz('Fone 3')?></td>
    <?php } ?>
    </tr>

    <tr>
        <?
        if (!in_array($login_fabrica, Array(15, 40, 81, 114)))
        {
        ?>
            <td>
            <input  type="text" name="contato" size="15" style="float: left;" maxlength="15" value="<? echo $contato_nome ?>" class='<?php echo (in_array('contato', $campos))? 'frm_obrigatorio' : "frm" ?>' onfocus="this.className='frm';"  style="width:100px">
        </td>
        <?
        }
        if ($login_fabrica == 171){
        ?>
            <td>
                <input type="text" name="codigo_fn" size="15" style="float: left;" value="<? echo $codigo_fn ?>" class='frm' onfocus="this.className='frm'">
            </td>
        <?php
        }
        ?>
        <td align='left'>
            <input class='<?php echo (in_array('codigo', $campos))? 'frm_obrigatorio' : "frm" ?>'
onfocus="this.className='frm';"  type="text" name="codigo" size="14" style="float: left;" maxlength="14" value="<? echo $codigo ?>" style="width:150px"<?if(strlen($posto) > 0 and $login_fabrica == 45 AND strlen(trim($codigo)) > 0)  echo " readonly='readonly' ";?>><a href="#" class="btn-lupa-codigo"><img src='imagens/lupa.png' style="cursor:pointer" border='0' align='left' onclick="javascript: fnc_pesquisa_posto (document.frm_posto.nome,document.frm_posto.cnpj,document.frm_posto.codigo,'codigo')"></a>
        </td>
        <?php if($login_fabrica != 74){
            if ($login_fabrica == 171){
                $pixel = "195px;";
            }else{
                $pixel = "300px;";
            }
        ?>
        <td colspan="2" align='left'>
            <input
            class='<?php echo (in_array('nome', $campos))? 'frm_obrigatorio' : "frm" ?>' onfocus="this.className='frm';"

            type="text" name="nome" style="float: left; width:<?=$pixel?>" maxlength="60" value="<? if ($login_fabrica == 50) { echo strtoupper($nome); } else { echo $nome; } ?>"><a href="#"  class="btn-lupa-razao"><img src='imagens/lupa.png' style="cursor:pointer" border='0' align='left' onclick="javascript: fnc_pesquisa_posto (document.frm_posto.nome,document.frm_posto.cnpj,document.frm_posto.codigo,'nome')"></a>
                <input type="hidden" name="nome_anterior" value="<?=$nome_anterior?>">
        </td>
        <?php }else{ ?>
        <td align="left">
            <input class='frm telefone' type="text" name="fone2" id="fone2" style="float: left; width: 106px;" maxlength="15" value="<? echo $fone2 ?>">
        </td>
        <td align="left" colspan="3">
            <input class='frm telefone' type="text" name="fone3" id="fone3" style="float: left; width: 106px;" maxlength="15" value="<? echo $fone3 ?>">
        </td>
        <?php } ?>
    </tr>
    <?
        // HD4181184 - 03/04/2018
        if ($login_fabrica == 151) {?>
        <tr align='left'>
            <td align="left"><?=traduz('Telefone 2')?></td>
            <td align="left"><?=traduz('Telefone 3')?></td>
            <td align="left"><?=traduz('Celular')?></td>
        </tr>

        <tr align='left'>

            <td align="left">
                <input type="text" class='frm telefone' name="fone2" id="fone2" style="float: left; width: 106px;" maxlength="20" value="<?echo $fone2?>" />
            </td>
            <td align="left">
                <input type="text" class='frm telefone' name="fone3" id="fone3" style="float: left; width: 106px;" maxlength="20" value="<?echo $fone3?>" />
            </td>
            <td align="left">
                <input type="text" class='frm telefone' name="contato_cel" id="contato_cel" style="float: left; width: 106px;" maxlength="20" value="<?echo $contato_cel?>" />
            </td>
        </tr>
    <?}?>
        <?php if($login_fabrica == 35){ ?>
    <tr>
        <td align="left"><?=traduz('Celular Contato')?></td>
    </tr>
    <tr>
        <td>
            <input class='frm celular' type="text" name="celular_contato" id="celular_contato" style="float: left; width: 106px;" maxlength="15" value="<? echo $contato_cel ?>">
        </td>
    </tr>
    <?php } ?>
     <?php if($login_fabrica == 74){?>
        <tr>
             <td colspan="2" align="left"><?=traduz('Razão Social')?></td>
        </tr>
        <tr>
            <td colspan="2" align='left'>
                <input class="frm" type="text" name="nome" style="float: left; width:300px;" maxlength="60" value="<? if ($login_fabrica == 50) { echo strtoupper($nome); } else { echo $nome; } ?>"><a href="#"><img src='imagens/lupa.png' style="cursor:pointer" border='0' align='left' onclick="javascript: fnc_pesquisa_posto (document.frm_posto.nome,document.frm_posto.cnpj,document.frm_posto.codigo,'nome')"></a>
                <input type="hidden" name="nome_anterior" value="<?=$nome_anterior?>">
            </td>
        </tr>
    <?php }

    if($login_fabrica == 35){?>
        <tr>
             <td colspan="2" align="left" style="color: rgb(168, 0, 0);"><?=traduz('Representante Legal')?></td>
        </tr>
        <tr>
            <td colspan="2" align='left'>
                <input  type="text" name="responsavel_social" size="50" style="float: left;" maxlength="150" value="<? echo $responsavel_social ?>" class='<?php echo (in_array('responsavel_social', $campos))? 'frm_obrigatorio' : "frm" ?>' onfocus="this.className='frm';"  style="width:100px">
            </td>
        </tr>
    <?php } ?>

    <tr><td>&nbsp;</td></tr>

    <tr>
        <td align="center" colspan="5">
            <button type="button" onclick="location.href='<? echo $PHP_SELF ?>?listar=todos#postos'"><?=traduz('Listar Todos os Postos Cadastrados')?></button>
        </td>
    </tr>
<?php
    if ($posto and $login_fabrica == 1) {
?>
        <tr>
            <td colspan="5" align="center"><button id="mostra_opt_extrato"><?=traduz('Alterar geração de extrato do posto')?></button></td>
        </tr>
<?php
    }
?>
</table>
<?
//17/7/2009 MLG
    $colspan = 3;   // Calcula o 'colspan' da tD do "país"
    if ($login_fabrica==2) $colspan--;    //  Um a menos, porque tem 'data nomeação'
    if ($login_fabrica==2) $colspan--;    //  Um a menos, porque tem 'atende consumidor'
?>

<? $array_fabrica_inspetores = array(3,30,169,170,178,183); ?>

<?php if ( hdPermitePostoAbrirChamado() or in_array($login_fabrica, $array_fabrica_inspetores) ): ?>

<table class="formulario" style="margin: 0 auto; width: 700px; border: 0;"cellpadding="1" cellspacing="3">
    <tr>
        <td>&nbsp;</td>
    </tr>
    <tr>

        <td class='titulo_coluna'>
            <? echo ($login_fabrica == 1) ? traduz("Atendente de Callcenter Para este Posto") : traduz("Inspetor para esse posto");?>
        </td>
    </tr>
    <tr>
        <td align="center"> <em>
        <?php
            if ($login_fabrica == 30) {
                $tituloInspetor = traduz("Inspetor para esse posto");
            } else {
                $tituloInspetor = traduz("Selecione o inspetor para esse posto");
            }

            echo ($login_fabrica == 1) ? traduz("Selecione o atendente para quem serão gerados os chamados abertos por este posto de atendimento") : $tituloInspetor;
        ?></em> </td>
    </tr>
    <tr>
        <td>
            <?php
                if ($login_fabrica == 3) {
                    $sqlInspetor = "SELECT admin, login, nome_completo FROM tbl_admin WHERE admin_sap = 't' AND ativo = 't' AND fabrica = $login_fabrica ORDER BY nome_completo";
                    $qryInspetor = pg_query($con, $sqlInspetor);

                    if (is_resource($qryInspetor)) {
                        $aAtendentes = array();
                        while ($fetch = pg_fetch_assoc($qryInspetor)) {
                            $aAtendentes[] = $fetch;
                        }
                    }
                } elseif ($login_fabrica == 30) {
                    if (strlen(trim($posto)) > 0){
                        $aAtendentes = buscarAtendentePorPosto($posto,$addressIbge,$estado);
                    }
                } else {
                    // ! Buscar atendentes  de posto
                    // HD 121248 (augusto)
                    $aAtendentes = hdBuscarAtendentes();
                }
            ?>
            <?php if ($login_fabrica != 30){ ?>
            <select class='frm' name="admin_sap" id="admin_sap">
                <option value=""></option>
                <?php foreach($aAtendentes as $aAtendente): ?>
                    <?php if($login_fabrica == 42){ ?>
                        <option value="<?php echo $aAtendente['admin']; ?>" <?php echo ($aAtendente['admin'] == $admin_sap) ? 'selected="selected"' : '' ; ?>><?php echo strtoupper($aAtendente['login']); ?></option>
                    <?php }else{ ?>
                        <option value="<?php echo $aAtendente['admin']; ?>" <?php echo ($aAtendente['admin'] == $admin_sap) ? 'selected="selected"' : '' ; ?>><?php echo empty($aAtendente['nome_completo']) ? $aAtendente['login'] : $aAtendente['nome_completo'] ; ?></option>
                    <?php } ?>
                <?php endforeach; ?>
            </select>
            <?php
                } else {
                    if (strlen($aAtendentes['admin']) == 0 || strlen($aAtendentes['admin_nome']) == 0) {
                        echo '<a href="cadastro_atendente_posto.php">Cadastrar atendente para o posto<a/>';
                    } else {

                        echo '<input type="text" class="frm" value="'.$aAtendentes['admin_nome'].'" readonly name="xadmin_sap" id="xadmin_sap">';
                        echo '<input type="hidden" class="frm" value="'.$aAtendentes['admin'].'" name="admin_sap" id="admin_sap">';
                    }
                }
            ?>
        </td>
    </tr>
    <? if ($login_fabrica == 3) { ?>
    <tr>
        <td>&nbsp;</td>
    </tr>
    <tr>
        <td align="center">
            <em>
                <?=traduz('Selecione o atendente para quem serão gerados os chamados abertos por este posto de atendimento')?>
            </em>
        </td>
    </tr>
    <tr>
        <td>
            <?php
                $bAtendentes = hdBuscarAtendentes();
            ?>
            <select class='frm' name="admin_sap_especifico" id="admin_sap_especifico">
                <option value=""></option>
                <?php foreach($bAtendentes as $bAtendente): ?>
                        <option value="<?php echo $bAtendente['admin']; ?>" <?php echo ($bAtendente['admin'] == $admin_sap_especifico) ? 'selected="selected"' : '' ; ?>><?php echo empty($bAtendente['nome_completo']) ? $bAtendente['login'] : $bAtendente['nome_completo'] ; ?></option>
                <?php endforeach; ?>
            </select>
        </td>
    </tr>
    <? } ?>
    <tr>
        <td>&nbsp;</td>
    </tr>
</table>
<?php endif; ?>

<table class="formulario" style="margin: 0 auto; width: 700px; border: 0;"cellpadding="1" cellspacing="3">
    <tr>
        <? if($login_fabrica==20) { ?>
        <td colspan="1"><?=traduz('Desconto Acessório')?></td>
        <td colspan="1"><?=traduz('Imposto IVA')?></td>
        <td colspan="1"><?=traduz('Custo Administrativo')?></td>
        <? } ?>
        <td colspan="<?=$colspan?>" style="color: rgb(168, 0, 0);" ><?=traduz('País')?></td>
        <? if ($login_fabrica==2) {
        /*  hd 21496 - Francisco - campo Data da Nomeação para Dynacom
            HD 167192- MLG - A Dynacom pode fazer com que o posto não apareça na pesquisa de postos,
                             tanto no Call-Center quanto na web (telecontrol / mapa_rede ...)
            PAra as fábricas que querem controlar se aparecem os postos na pesquisa
       */?>
        <td><?=traduz('Data Nomeação')?></td>
        <td><?=traduz('Atende Consumidor')?></td>
        <? } ?>
    </tr>

    <tr>
        <?
        if (empty($pais_cadastro)) {
            $pais_cadastro = "BR";
        }

        if($login_fabrica==20){ ?>
        <td><input class='frm' type="text" name="desconto_acessorio" size="5" maxlength="5" value="<? echo $desconto_acessorio ?>" >%</td>
        <td><input class='frm' type="text" name="imposto_al" size="5" maxlength="5" value="<? echo $imposto_al ?>" >%</td>
        <td><input class='frm' type="text" name="custo_administrativo" size="5" maxlength="5" value="<? echo $custo_administrativo ?>" >%</td>

        <? } ?>

        <td colspan="<?=$colspan?>">
        <?php if (!empty($posto)) {
            $sql = "SELECT pais, nome
                    FROM tbl_pais
                    WHERE pais = '$pais_cadastro'
                    ORDER BY nome";

            $res = pg_query($con, $sql);

            $nome_pais = pg_fetch_result($res, 0, 'nome');
            $pais_cadastro = pg_fetch_result($res, 0, 'pais');
            ?>
            <input type="hidden" name="pais_posto" id="pais_posto" value="<?=$pais_cadastro?>">
            <?
            echo "<b>$nome_pais</b>";
        } else {

         ?>
        <select name='pais' id='pais' class='<?= (in_array('pais', $campos))? 'frm_obrigatorio' : "frm" ?>' onfocus="this.className='frm';" >
        <?  $sql = "SELECT pais, nome
                    FROM tbl_pais
                    ORDER BY nome";
            $res = pg_query($con, $sql);
            $contadorres = pg_num_rows($res);

            if(pg_num_rows($res)>0){
                echo "<option value=''></option>";
                for($x=0; $x<$contadorres; $x++){
                    $aux_pais = pg_fetch_result($res, $x, pais);
                    $nome_pais= pg_fetch_result($res, $x, nome);

                    $selected_pais = "";
                    if ($pais_cadastro == $aux_pais) $selected_pais = " selected ";

                    echo "<option value='$aux_pais' $selected_pais>";
                    echo $nome_pais;
                    echo "</option>";
                } 
            }
        ?>
        </select>
        <?}?>
        </td>
        <?if ($login_fabrica==2){ ?>
        <td>
        <!-- hd 21496 - Francisco - campo Data da Nomeação para Dynacom -->
        <? include_once "javascript_calendario.php"; ?>
        <script type="text/javascript" charset="utf-8">
            $(function()
            {
                $("input[rel='data_mask']").mask("99/99/9999");
                $("input[name=data_nomeacao]").datepick({startDate : "01/01/2000"});
            });
        </script>
        <? if($data_nomeacao=='01/01/0001' OR $data_nomeacao=='0001-01-01') {
            $data_nomeacao ="";
        }
        $atende_consumidor_checked = ($atende_consumidor<>'f')? "CHECKED" :"";
        ?>
            <input class='frm' type="text" name="data_nomeacao" rel='data_mask' size="12" maxlength="16"
              value="<? echo $data_nomeacao ?>" ></td>
            <td><input class='frm' type="checkbox" name="atende_consumidor" <?=$atende_consumidor_checked?>
                      title='<?=traduz("Se desmarcar, o posto não irá a aparecer na pesquisa da rede de postos autorizados")?>'>
        </td>
        <?}?>
    </tr>
<!--
<tr>
<td>
<center>

<input type='hidden' name='btn_acao' value=''>
<input type="button" style="background:url(imagens_admin/btn_gravar.gif); width:75px; cursor:pointer;" value="&nbsp;" onclick="javascript: if (document.frm_posto.btn_acao.value == '' ) { document.frm_posto.btn_acao.value='gravar' ; document.frm_posto.submit() } else { alert ('Aguarde submissão') }" ALT="Gravar formulário" border='0'>
<input type="button" style="background:url(imagens_admin/btn_limpar.gif); width:75px; cursor:pointer;" value="&nbsp;" onclick="javascript: if (document.frm_posto.btn_acao.value == '' ) { document.frm_posto.btn_acao.value='reset' ; window.location='<? echo $PHP_SELF ?>' } else { alert ('Aguarde submissão') }" ALT="Limpar campos" border='0'>
</center>
</td></tr> -->
</table>

<table class="formulario" style="margin: 0 auto; width: 700px; border: 0;" cellpadding="1" cellspacing="3">
    <tr align='left'>
        <td style="color: rgb(168, 0, 0);"><?=traduz('CEP')?></td>
        <td style="color: rgb(168, 0, 0);"><?=traduz('Estado')?></td>
        <td style="color: rgb(168, 0, 0);"><?=traduz('Cidade')?></td>
        <td style="color: rgb(168, 0, 0);"><?=traduz('Bairro')?></td>
    </tr>
    <tr align='left'>
        <td>
            <input class='<?php echo (in_array('cep', $campos))? 'frm_obrigatorio' : "frm" ?>' type="text" name="cep" id="cep" size="10" maxlength="10" value="<? echo $cep ?>">
            <input type="hidden" name="cep_anterior" value="<?=$cep_anterior?>">
            <input id="usa_cidade_estado_txt" name="usa_cidade_estado_txt" type="hidden" value="<?= $usa_cidade_estado_txt ?>" />
        </td>
        <td>
        <?php if (!empty($pais_cadastro)) {
            $listaDeEstadosDoPais = $array_estados($pais_cadastro);
            //$cobranca_estado;
        }

        $displaySelCobUf = 'style="display:none;" disabled';
        $displayInpCobUf = 'style="display:block;max-width: 120px;"';
        if (count($listaDeEstadosDoPais) > 0) {         
            $optionHtml = '<option value=""></option>';
            foreach($listaDeEstadosDoPais as $siglaEstado => $descEstado) {
            $isSelected = ($estado == $siglaEstado) ? 'selected' : '';
            $optionHtml .= "<option value='{$siglaEstado}' {$isSelected}>".strtoupper($descEstado)."</option>";
            }
        $displaySelCobUf = 'style="display:block;max-width: 120px;"';
        $displayInpCobUf = 'style="display:none;" disabled';
        } ?>

        <select name="estado" id="estado" class="frm addressState" <?=$displaySelCobUf;?>>
            <?= $optionHtml ?>      
        </select>
        <input name="estado" id="estado_txt" class="frm" value="<?= $estado_txt ?>" <?=$displayInpCobUf;?> />
        </td>
        <td>
        <?php 

        if (!empty($pais_cadastro) && !empty($estado)) {
                $listaDeCidadesDoEstado = getCidadesDoEstado($pais_cadastro, $estado);
            }

            $displaySelecCidade = 'style="display: none;max-width:120px;" disabled';
            $displayInputCidade = 'style="display: block;max-width:120px;"';

            $optionHtml = '';
            if (count($listaDeCidadesDoEstado) > 0) {

                foreach($listaDeCidadesDoEstado as $resCidade){
                    $isSelected = (strtoupper($resCidade['cidade']) == strtoupper($cidade)) ? 'selected' : '';
                    $optionHtml .= "<option value='{$resCidade['cidade']}' {$isSelected}> {$resCidade['cidade']}  </option>";
                }
                $displaySelecCidade = 'style="display: block;max-width:120px;"';
                $displayInputCidade = 'style="display: none;max-width:120px;"';
            } 

            if ($pais_cadastro == "BR" || count($array_estados($pais_cadastro)) > 0) {
                $displaySelecCidade = 'style="display: block;max-width:120px;"';
                $displayInputCidade = 'style="display: none" disabled';
            }

            ?>
            <select name="cidade" id="cidade" class="frm addressCity" <?=$displaySelecCidade;?>>
                <?= $optionHtml ?>
            </select>
            <input class='frm' type="text" name="cidade"  id="cidade_txt" size="20" maxlength="30" value="<?= $cidade ?>" <?=$displayInputCidade;?> />
            <input type="hidden" name="cidade_anterior" value="<?=$cidade_anterior?>">
            <input type="hidden" name="addressIbge" class="addressIbge" value="<?=$addressIbge?>">

        </td>
        <td><input class='<?= (in_array('bairro', $campos))? 'frm_obrigatorio' : "frm" ?>' type="text" name="bairro" size="20" maxlength="40" value="<?= $bairro ?>"></td>
    </tr>
    <tr align='left'>
        <td style="color: rgb(168, 0, 0);"><?=traduz('Endereço')?></td>
        <td <?php echo ($login_fabrica <> 3) ? 'style="color: rgb(168, 0, 0);"' : '';?>><?=traduz('Número')?></td>
        <td ><?=traduz('Complemento')?></td>
    </tr>
    <tr align='left'>
        <td><input class='<?php echo (in_array('endereco', $campos))? 'frm_obrigatorio' : "frm" ?>' type="text" name="endereco" size="30" maxlength="50" value="<?=$endereco?>"></td>
        <td><input class='<?php echo (in_array('numero', $campos))? 'frm_obrigatorio' : "frm" ?>' type="text" name="numero" size="10" maxlength="10" value="<? echo $numero ?>"></td>
        <td><input class='frm' type="text" name="complemento" size="5" maxlength="20" value="<? echo $complemento ?>"></td>
    </tr>
</table>

<table class="formulario" style="margin: 0 auto; width: 700px; border: 0;" cellpadding="1" cellspacing="3">
<?php 
    if (isset($_GET['posto']) && !in_array($login_fabrica, [180,181,182])) {
?>
        <tr>
            <td>
                <span class="geolocalizacao">
                    <a rel="shadowbox" href="atualiza_localizacao_posto.php?posto=<?=$posto; ?>" name="btnAtualizaMapa"><?=traduz('Geolocalização do Posto')?><img src="imagens/Google_Maps_Marker_Red.gif" style="width: 28px; height: 28px; margin: 0 auto;"/></a>
                </span>
            </td>
        </tr>
<?php 
    } 
?>
</table>
<table class="formulario" style="margin: 0 auto; width: 700px; border: 0;" cellpadding="1" cellspacing="3">
    <tr align='left'>
        <td style="width: 232px; color: rgb(168, 0, 0);"><?=traduz('E-mail')?></td>
        <td><?=traduz('Capital/Interior')?></td>
        <?if($login_fabrica == 7){?><td><?=traduz('Posto Empresa')?></td><?}?>
    <?php
    if (!in_array($login_fabrica, [148,189]) and !isset($tipo_posto_multiplo)) {
    ?>
            <td><?echo traduz("Tipo do Posto");?></td>
    <?php
    }
    ?>
    <?php
    if (in_array($login_fabrica, [189])) {
    ?>
            <td><?echo "Código do Representante";?></td>
    <?php
    }
    ?>
        <? if(in_array($login_fabrica,array(1,104,115))){ ?>
        <td><?=traduz('Categoria Posto')?></td>
        <? } ?>

        <?  if(in_array($login_fabrica,array(115))) { 
                $altera_categoria_posto = altera_cadastro_posto();
                if ($altera_categoria_posto == 't') { ?>
                    <td></td>
                    <td></td>
            <?  }
            } ?>

        <?php if($login_fabrica == 6){ ?>
            <td><?=traduz('Meses Extrato')?></td>
        <?php } ?>
        <!-- <td>PEDIDO EM GARANTIA</td> -->
        <?if($login_fabrica == 20){?><td>ER</td><?}
        if (!in_array($login_fabrica,array(86,94,122,81,114,124,123,124,125,128,136,184,200))) {//HD 387824?>
            <td><?=traduz('Desconto')?></td><?php
        }
        if ($login_fabrica == 183){
        ?>
            <td>Encontro de contas</td>
        <?php   
        }
        if (in_array($login_fabrica, array(11,172))) {// HD 110541?>
            <td width='34%'><?=traduz('Atendimento')?></td>
        <? } ?>
        <?if (!in_array($login_fabrica, array(173,174,175,176,203))) {
            if(in_array($login_fabrica,array(35,50,52,72,24,91,94,74,15,120,201,131,138,140,141,142,144)) || isset($novaTelaOs)){?>
                <td><?=traduz('Valor/km')?></td>
            <?
            }
        } // HD 12104
        if($login_fabrica == 14){ ?>
        <td><?=traduz('Liberar')?> 10%</td>
        <? } ?>
        <? // HD 17601
        if(in_array($login_fabrica, array(45,151))){ ?>
        <td>Qtde <?php echo (in_array($login_fabrica, array(151))) ? "OS" : "Itens" ?></td>
        <? } ?>
        <? if($login_fabrica == 74){ // HD 384120?>
        <td><?=traduz('Contribuinte de ICMS')?></td>
        <? } ?>

    </tr>
    <tr align='left'>
        <td>
            <?php if (empty($email)) {  
                $query = "SELECT email 
                          FROM tbl_posto 
                          WHERE posto = $posto"; 

                $email = pg_query($con, $query); 
                $email = pg_fetch_result($email, 0, email);
            } ?>
            <input type="text" name="email" style="width: 200px;" maxlength="50" value="<?= $email ?>" class='<?= (in_array('email', $campos))? 'frm_obrigatorio' : "frm" ?>'
onfocus="this.className='frm';" >

        </td>
        <td>

    <select class='frm' name='capital_interior' size='1'>
        <option selected></option>
        <option value='CAPITAL' <? if ($capital_interior == 'CAPITAL') echo ' selected ' ?> >Capital</option>
        <option value='INTERIOR' <? if ($capital_interior == 'INTERIOR') echo ' selected ' ?> >Interior</option>
    </select>

        </td>
        <? if($login_fabrica==7){
            if(strlen($posto_empresa)>0){
            $sqlx = "SELECT codigo_posto
                    FROM tbl_posto_fabrica
                    WHERE posto   = $posto_empresa
                    AND   fabrica = $login_fabrica";
            $resx = pg_query($con, $sqlx);
            if(pg_num_rows($resx)>0){
                $posto_empresa = pg_fetch_result($resx, 0, codigo_posto);
            }
           }
            ?>
            <td><input class='frm' type="text" name="posto_empresa" size="10" maxlength="15" value="<? echo $posto_empresa ?>"></td>
        <?}

        // Select tipo posto normal: apenas pode selecionar um
        $sql = "SELECT *
            FROM   tbl_tipo_posto
            WHERE  tbl_tipo_posto.fabrica = $login_fabrica
            AND tbl_tipo_posto.ativo = 't'
            ORDER BY tbl_tipo_posto.descricao";
        if ($login_fabrica == 94)
            $sql .= ' DESC';

        $res = pg_query($con, $sql);
        $tipos_de_posto = pg_fetch_all($res);

        $extra_attr = ' class="frm" size="1"';
        if ($login_fabrica == 42)
            $extra_attr .= ' onchange="entrega_tecnica_check($(this).find(\'option:selected\').attr(\'rel\'));"';

        if ($login_fabrica == 74)
            $extra_attr .= ' onchange="verifyToEnableOsVinculada(this);"';

        if ($login_fabrica == 158) {
            $extra_attr .= ' onchange="mostra_cep_atendido();"';
        }

        foreach ($tipos_de_posto as $idx => $info_tipo) {
        $sel = (in_array($info_tipo['tipo_posto'], (array)$tipo_posto)) ? 'selected' : ''; // valida tanto se $tipo_posto é um escalar como um array

            if ($login_fabrica == 158) {
                if ($sel == "selected") {
                    $posto_interno = $info_tipo["posto_interno"];
                }

                $sel_tipo_posto .= sprintf(
                    "\t<option rel='%s' value='%s' %s data-posto-interno='%s' >%s</option>\n",
                    $info_tipo['tipo_revenda'],
                    $info_tipo['tipo_posto'],
                    $sel,
                    $info_tipo["posto_interno"],
                    $info_tipo['descricao']
                );
            } else {
                if($sel == "selected"){
                    $descricao_tipo_posto = $info_tipo['descricao'];                    
                }

                $sel_tipo_posto .= sprintf("\t<option rel='%s' value='%s'%s>%s</option>\n", $info_tipo['tipo_revenda'], $info_tipo['tipo_posto'], $sel, $info_tipo['descricao']);
            }
        }

        // Para fábricas que têm posto com vários tipos de posto ao mesmo tempo (Yanmar), o SELECT está abaixo,
        // após o Ítem de Aparência
        if (!isset($tipo_posto_multiplo)) { ?>
        <td>

            <select id="tipo_posto" name='tipo_posto' <?=$extra_attr?> > <!-- fecha tag select -->
            <?=$sel_tipo_posto?>
            </select>
            <input type="hidden" name="tipo_posto_anterior" id="tipo_posto_anterior" value="<?=$descricao_tipo_posto?>">
        </td>
<?php
        }
?>
        
        <?php if ($login_fabrica == 1) {

            $checkedA  = (strtolower($categoria_posto) == 'autorizada')          ? "SELECTED" : "";
            $checkedCP = (strtolower($categoria_posto) == 'compra peca')          ? "SELECTED" : "";
            $checkedL  = (strtolower($categoria_posto) == 'locadora')            ? "SELECTED" : "";
            $checkedAL = (strtolower($categoria_posto) == 'locadora autorizada') ? "SELECTED" : "";
            $checkedMP = (strtolower($categoria_posto) == "mega projeto")        ? "SELECTED" : "";
?> 
                <td>
                    <select name="categoria_posto" id="categoria_posto" class="frm">
                        <option value=""></option>
                        <option value="Autorizada" <?=$checkedA?>><?=traduz('Autorizada')?></option>
                        <option value="Compra Peca" <?=$checkedCP?>><?=traduz('Compra de Peças')?></option>
                        <option value="Locadora" <?=$checkedL?>><?=traduz('Locadora')?></option>
                        <option value="Locadora Autorizada" <?=$checkedAL?>><?=traduz('Locadora Autorizada')?></option>
                        <option value="mega projeto" <?=$checkedMP?>><?=traduz('Industria/Mega Projeto')?></option>
                    </select>
                    <input type="hidden" name="categoria_posto_anterior" value="<?=$categoria_posto?>">
                </td>
<?php
        } else if ($login_fabrica == 115) {

?>
                <td>
                    <select name="categoria_posto" id="categoria_posto" class="frm">
                        <option value=""></option>
                        <option value="premium" <?=($categoria_posto == "premium") ? "selected" : ""?>><?=traduz('PREMIUM')?></option>
                        <option value="master" <?=($categoria_posto == "master") ? "selected" : ""?>><?=traduz('MASTER')?></option>
                        <option value="standard" <?=($categoria_posto == "standard") ? "selected" : ""?>><?=traduz('STANDARD')?></option>
                    </select>
                </td>
<?php
            if ($altera_categoria_posto == 't') { 
?>
                <td>
                    <input type="checkbox" name="categoria_manual" value='t' <? if ($categoria_manual == 't') echo ' checked ' ?>>
                </td>
                <td>
                    <?=traduz('Categoria')?> <br> <?=traduz('Manual')?>
                </td>
                
<?php
            }
		}elseif($login_fabrica == 104) {
?>
			<td>
                    <select name="categoria_posto" id="categoria_posto" class="frm">
                        <option value=""></option>
					<?php
						foreach($categoriaPosto as $categoria) {
							echo "<option value='$categoria'";
							echo ($categoria == $categoria_posto) ? " selected " : "";
							echo ">$categoria</option>";
						}
					?>
                    </select>
                    <input type="hidden" name="categoria_posto_anterior" value="<?=$categoria_posto?>">
                </td>
<?php
		}

        if ($login_fabrica == 20) {
?>
        <td>
            <select name='escritorio_regional' size='1'>
                <?
                    $sql = "SELECT *
                            FROM   tbl_escritorio_regional
                            WHERE  fabrica = $login_fabrica
                            ORDER BY descricao";
                    $res = pg_query ($con,$sql);
                        echo "<option value=''></option>";
                        for ($i = 0 ; $i < pg_num_rows ($res) ; $i++) {
                            echo "<option value='" . pg_fetch_result ($res,$i,escritorio_regional) . "' ";
                                if ($escritorio_regional == pg_fetch_result ($res,$i,escritorio_regional)) echo " selected ";
                            echo ">";
                            echo pg_fetch_result ($res,$i,descricao);
                    echo "</option>";
                    }
                ?>
            </select>
        </td>
		<?}
            if($login_fabrica == 6){
		if (strlen($posto) > 0) {
                    $sqlParametrosAdicionais = "SELECT parametros_adicionais FROM tbl_posto_fabrica WHERE posto = {$posto} AND fabrica = {$login_fabrica}";
                    $resParametrosAdicionais = pg_query($con, $sqlParametrosAdicionais);
                    if (pg_num_rows($resParametrosAdicionais) > 0) {
                        $parametrosAdicionais = json_decode(pg_fetch_result($resParametrosAdicionais, 0, "parametros_adicionais"), true);
                        extract($parametrosAdicionais);
                    }
                }

                if(strlen($meses_extrato) > 0){
                    $meses_extrato = $meses_extrato;
                }else{
                    $meses_extrato = 16;
                }
        ?>
            <td> 
               <input class='frm' type="text" name="meses_extrato" size="5" maxlength="5" value="<?php echo $meses_extrato ?>">
            </td>
        <?php }?>
    <?php if ($login_fabrica == 189) {?>
       
        <td>
            <input type="text" class='frm' name="codigo_representante" id="codigo_representante" value="<?=$codigo_representante?>">
        </td>
    <?php }?>

        <?php 
        if (!in_array($login_fabrica,array(86,94,122,81,114,124,123,124,125,128,136,184,200))) {//HD 387824?>
            <td nowrap><input class='frm' type="text" name="desconto" size="5" maxlength="5" value="<?=$desconto?>">%</td><?php
        }
        
        if ($login_fabrica == 183) {
            if (!empty($msg_erro)){
                $encontro_de_contas = $_POST['encontro_de_contas'];
            }
        ?>
            <td nowrap><input class='frm' type="text" name="encontro_de_contas" size="5" maxlength="5" value="<?=$encontro_de_contas?>">%</td>
        <?php
        }
        // HD 110541
        if($login_fabrica==11 or $login_fabrica == 172){?>
        <td>
            <select class="frm" name='atendimento_lenoxx'
            <?php
                if (isset($readonly) and strlen($atendimento_lenoxx)>0){
                    echo " DISABLED";
                } ?>>
                <option selected></option>
                <option value='b' <?= ($atendimento_lenoxx == 'b') ? "selected" : "" ?>><?=traduz('Balcão')?></option>
                <option value='r' <?= ($atendimento_lenoxx == 'r') ? "selected" : "" ?>><?=traduz('Revenda')?></option>
                <option value='t' <?= ($atendimento_lenoxx == 't') ? "selected" : "" ?>><?=traduz('Balcão/Revenda')?></option>
                <option value='e' <?= ($atendimento_lenoxx == 'e') ? "selected" : "" ?>><?=traduz('Exclusivo')?></option>
            </select>
		<? } ?>
        <?if (!in_array($login_fabrica, array(173,174,175,176,203))) {
            if(in_array($login_fabrica,array(35,50,52,72,24,91,94,74,15,120,201,131,138,140,141,142,144)) || isset($novaTelaOs)){?>
                <td><input class='frm' type="text" name="valor_km" size="5" maxlength="5" value="<? echo $valor_km?>" ></td>
            <?
            }
        }

         // HD 17601
        if(in_array($login_fabrica, array(45,151))){
            if($login_fabrica == 151 AND strlen($posto) > 0){
                $sqlParametrosAdicionais = "SELECT parametros_adicionais FROM tbl_posto_fabrica where posto = {$posto} and fabrica = {$login_fabrica}";
                $resParametrosAdicionais = pg_query($con,$sqlParametrosAdicionais);
                $parametros_adicionais = pg_fetch_result($resParametrosAdicionais, 0,"parametros_adicionais");

                if(strlen($parametros_adicionais) > 0) {
                    $parametros_adicionais = json_decode($parametros_adicionais, true);
                    $qtde_os_item          = $parametros_adicionais['qtde_os_item'];
                    $valor_extrato         = $parametros_adicionais['valor_extrato'];
                    $valor_mao_obra        = $parametros_adicionais['valor_mao_obra'];
                    $digito_agencia        = $parametros_adicionais['digito_agencia'];
                    $digito_conta          = $parametros_adicionais['digito_conta'];
                    $extrato_mais_3_meses          = $parametros_adicionais['extrato_mais_3_meses'];
                }
            }
            ?>
        <td>
        <input type='text' class='frm' name='qtde_os_item' size='2' maxlength='3' value='<? echo "$qtde_os_item";?>'>
        </td>
        <? } ?>
        <? if($login_fabrica == 74){ # HD 384120?>
        <td>
        <input type='radio' class='frm' name='contribuinte_icms'  value='t' <?if ($contribuinte_icms == 't') echo "checked";?>>Sim
        <input type='radio' class='frm' name='contribuinte_icms'  value='f' <?if ($contribuinte_icms == 'f') echo "checked";?>>Não
        </td>
        <? } ?>
    </tr>

   <?
            if (in_array($login_fabrica,array(15))) {
                if (strlen($posto) > 0) {
                    $sqlParametrosAdicionais = "SELECT parametros_adicionais FROM tbl_posto_fabrica WHERE posto = {$posto} AND fabrica = {$login_fabrica}";
                     $resParametrosAdicionais = pg_query($con, $sqlParametrosAdicionais);

                     if (pg_num_rows($resParametrosAdicionais) > 0) {
                         $parametrosAdicionais = json_decode(pg_fetch_result($resParametrosAdicionais, 0, "parametros_adicionais"), true);
                         extract($parametrosAdicionais);
                     }
                }

    ?>
        <tr>
            <td align="left">
                <?=traduz('Email adicional')?>
            </td>
        </tr>
        <tr>
            <td align="left">
                <input type="text" name="email2" id="email2" size="30" maxlength="50" class="frm" value="<?=$email2?>" >
            </td>
        </tr>
    <?php
    }
    if($login_fabrica == 42){
    ?>
        <tr>
            <td colspan="4" align="left"><?=traduz('Taxa Administrativa')?></td>
        </tr>
        <tr>
            <td colspan="4" align="left">
                <?php
                    if(strlen($custo_administrativo)>0){
                        $custo_administrativo = number_format($custo_administrativo,2,',','');
                    }
                ?>
                <input type="text" maxlength="8" size="12" value="<?php echo $custo_administrativo;?>" name="custo_administrativo" class="frm msk_valor">%
            </td>
        </tr>
    <?php
    }
    /* HD 407694 */
    if($login_fabrica == 1){
        ?> 
        <tr>
            <td align="left"><?=traduz('Acréscimo Tributário')?></td>
            <td align="left" nowrap><?=traduz('Taxa Administrativa Fixa')?></td>
            <td colspan="2" align="left"><?=traduz('Recebe Taxa Administrativa')?></td>
        </tr>
        <tr>
            <td align="left">
                <input type="text" maxlength="8" size="12" value="<?=number_format($acrescimo_tributario,3,',','')?>" name="acrescimo_tributario" class="frm msk_valor">
            </td>
            <td align="left" nowrap>
<?php
                if($taxa_administrativa > 0 && strlen($msg_erro) == 0){
                    $taxa_administrativa = ($taxa_administrativa - 1) * 100;
                }

                if(strlen($taxa_administrativa)>0){
                    $taxa_administrativa = number_format($taxa_administrativa,2,',','');
                }

?>
                <input type="text" id="id_taxaAdm" maxlength="8" size="12" value="<?=$taxa_administrativa?>" name="taxa_administrativa" class="frm msk_valor">%&nbsp;&nbsp;&nbsp;
                <input type="hidden" name="taxa_administrativa_anterior" id="taxa_administrativa_anterior" value="<?=$taxa_administrativa?>">
            </td>
            <td  colspan="2" align="left">
                <input type="radio" id="id_TaxaAdmS" name="recebeTaxaAdm" value="sim" <?=($recebeTaxaAdm == "sim") ? "checked" : ""?> /><?=traduz('Sim')?>
                <input type="radio" id="id_TaxaAdmN" onload="txAdm()" name="recebeTaxaAdm" value="nao" <?=($recebeTaxaAdm == "nao" OR empty($recebeTaxaAdm)) ? "checked" : ""?> /><?=traduz('Não')?>

                <input type="hidden" name="recebeTaxaAdm_anterior" id="recebeTaxaAdm_anterior" value="<?=$recebeTaxaAdm?>">
            </td>
        </tr>
<?php
    }
?>
</table>
<table class="formulario" style="margin: 0 auto; width: 700px; border: 0;" cellpadding="1" cellspacing="3">
    <tr align='left'>
        <td style="width: 232px;"><?=traduz('Nome Fantasia')?></td>
        <td style="width:120px"><?=traduz('Senha')?></td>
        <? if (!in_array($login_fabrica, array(86,94,152,163,164,167,171,173,174,175,176,178,180,181,182,184,193,200,203))) { //HD 387824 ?>
            <td><?=traduz('Transportadora')?></td>
        <? }
        if (in_array($login_fabrica, array(151))){?>
            <td><?=traduz('Valor Extrato')?></td>
        <? } ?>
        <?php
            if (in_array($login_fabrica, array(120,201))) {?>
            <td><?=traduz('KM a partir de')?>:</td>
        <?php } ?>
    </tr>
    <tr align='left'>
        <td>
            <input class='frm' type="text" name="nome_fantasia" size="30" maxlength="50" value="<?= $nome_fantasia ?>" >
        </td>
        <td style="width: 100px;" align="left">
            <input class='frm' type="text" name="senha" size="10" maxlength="10" value="<?= $senha ?>">
        </td>

        <? if (!in_array($login_fabrica, array(86,94,152,163,164,167,171,173,174,175,176,178,180,181,182,184,193,200,203))) { //HD 387824 ?>
            <td align='left' <?= ($login_fabrica == 151) ? "style='width:100px;'" : ""; ?>>
                <select class='frm' name="transportadora" style="width:210px;">
                    <option selected></option>
                    <? if (in_array($login_fabrica, array(11,169,170,172,177))) {
                        $sql = "SELECT  tbl_transportadora.transportadora        ,
                                        tbl_transportadora.nome                  ,
                                        tbl_transportadora.cnpj
                                FROM    tbl_transportadora
                                JOIN    tbl_transportadora_fabrica USING(transportadora)
                                WHERE   tbl_transportadora_fabrica.fabrica = $login_fabrica
                                AND     tbl_transportadora_fabrica.ativo  = 't' ";
                        $res = pg_query ($con,$sql);
                        if (pg_num_rows ($res) > 0) {
                            for ($i = 0 ; $i < pg_num_rows ($res) ; $i++) {
                                echo "<option value='".pg_fetch_result($res,$i,transportadora)."' ";
                                if ($transportadora == pg_fetch_result($res,$i,transportadora) ) echo " selected ";
                                echo ">";
                                echo pg_fetch_result($res,$i,cnpj) ." - ".substr (pg_fetch_result($res,$i,nome),0,25);
                                echo "</option>\n";
                            }
                        }
                    }else{
                        $sql = "SELECT  tbl_transportadora.transportadora        ,
                                        tbl_transportadora.nome                  ,
                                        tbl_transportadora_fabrica.codigo_interno
                                FROM    tbl_transportadora
                                JOIN    tbl_transportadora_padrao USING(transportadora)
                                JOIN    tbl_transportadora_fabrica USING(transportadora)
                                WHERE   tbl_transportadora_padrao.fabrica = $login_fabrica
                                AND     tbl_transportadora_fabrica.ativo  = 't'
                                ORDER BY tbl_transportadora.nome";
                        $res = pg_query ($con,$sql);
                        if (pg_num_rows ($res) > 0) {
                            for ($i = 0 ; $i < pg_num_rows ($res) ; $i++) {
                                echo "<option value='".pg_fetch_result($res,$i,transportadora)."' ";
                                if ($transportadora == pg_fetch_result($res,$i,transportadora) ) echo " selected ";
                                echo ">";
                                echo pg_fetch_result($res,$i,codigo_interno) ." - ".pg_fetch_result($res,$i,nome);
                                echo "</option>\n";
                            }
                        }
                        else
                        {
                            $sql = "SELECT  tbl_transportadora.transportadora        ,
                                        tbl_transportadora.nome                  ,
                                        tbl_transportadora_fabrica.codigo_interno
                                FROM    tbl_transportadora
                                JOIN    tbl_transportadora_fabrica USING(transportadora)
                                WHERE   tbl_transportadora_fabrica.fabrica = $login_fabrica
                                AND     tbl_transportadora_fabrica.ativo  = 't'
                                ORDER BY tbl_transportadora.nome";
                            $res = pg_query ($con,$sql);
                            if (pg_num_rows ($res) > 0) {
                                for ($i = 0 ; $i < pg_num_rows ($res) ; $i++) {
                                    echo "<option value='".pg_fetch_result($res,$i,transportadora)."' ";
                                    if ($transportadora == pg_fetch_result($res,$i,transportadora) ) echo " selected ";
                                    echo ">";
                                    echo pg_fetch_result($res,$i,codigo_interno) ." - ".pg_fetch_result($res,$i,nome);
                                    echo "</option>\n";
                                }
                            }
                        }
                    }?>
                </select>
            </td>
        <? }
        if (in_array($login_fabrica, array(151))) { ?>
            <td>
            <input type='text' price="true" class='frm' name='valor_extrato' size='6' value='<?= "$valor_extrato";?>'>
            </td>
        <? } ?>
            <?php
                if (in_array($login_fabrica, array(120,201))) {

                    if (strlen($posto) > 0 && strlen($km_apartir) == 0) {
                        $sqlPA = "SELECT parametros_adicionais FROM tbl_posto_fabrica WHERE posto = {$posto} AND fabrica = {$login_fabrica}";
                        $resPA = pg_query($con, $sqlPA);
                        if (pg_num_rows($resPA) > 0) {
                            $rowPA = json_decode(pg_fetch_result($resPA, 0, "parametros_adicionais"), true);
                            extract($rowPA);
                            $km_apartir = $rowPA['km_apartir'];
                        }
                    }

            ?>
            <td>
            <input type='text' class='frm' size='10' name='km_apartir' id='km_apartir' value='<?php echo $km_apartir;?>'>
            </td>
        <?php } ?>
    </tr>
    <tr align='left'>
        <td><?=traduz('Região Suframa')?></td>
        <td><?=traduz('Item Aparência')?></td>

        <? if(in_array($login_fabrica,array(20))) { ?>
            <td><?=traduz('Foto de Nº de Série')?></td>
        <? } ?>

        <? if(in_array($login_fabrica,array(30,74))) { ?>
            <td>Valor Fixo KM</td>
            <? if($login_fabrica == 30){ ?>
                <td><?=traduz('Qtde KM')?></td>
            <? }
        }

        if($login_fabrica == 151){
            echo "<td>Ver extratos gerados a mais de 3 meses</td>";
            echo "<td>Valor Mão de Obra</td>";
        }

        if (in_array($login_fabrica, array(158,169,170,178))) { ?>
            <?php if ($login_fabrica == 178){ ?>
            <td><?=traduz("Código de cliente")?></td>
            <?php }else{ ?>
            <td><?=traduz("Código Depósito")?></td>
            <?php } ?>
            <td><?=traduz("Código Fornecedor")?></td>
        <? } ?>
    
        <?php if ($login_fabrica == 183){ ?>
            <td>Código Fornecedor</td>
            <?php if (in_array($codigo_tipo_posto, array("Rev", "Rep"))){ ?>
                <td>Representantes</td>
            <?php } ?>
        <?php } ?>

        <?php if ($tipo_posto_multiplo) { ?>
            <td><?=traduz("Tipo(s) do Posto")?></td>
        <? } ?>
                <?php
        if (in_array($login_fabrica, [189])) {
        ?>
                <td><?echo "Tipo de Cliente";?></td>
        <?php
        }
        ?>
    </tr>
    <tr align='left'>
        <td>
            <?=traduz("Sim")?><INPUT TYPE="radio" NAME="suframa" VALUE = 't' <? if ($suframa == 't') echo "checked"; ?>>
            <?=traduz("Não")?><INPUT TYPE="radio" NAME="suframa" VALUE = 'f' <? if ($suframa == 'f' || strlen($suframa) == 0) echo "checked"; ?>>
        </td>
        <td>
            <acronym title='<?=traduz("Esta informação trabalha em conjunto com a informação item de aparência no cadastro de peças. Deixando setado como SIM, este posto vai conseguir lançar peças de item de aparência nas Ordens de Serviço de Revenda.")?>'>
                <?=traduz("SIM")?><INPUT TYPE="radio" NAME="item_aparencia" VALUE = 't' <? if ($item_aparencia == 't') echo "checked"; ?>>
                <?=traduz("NÃO")?><INPUT TYPE="radio" NAME="item_aparencia" VALUE = 'f' <? if ($item_aparencia <> 't') echo "checked"; ?>>
            </acronym>
            <? //5595 link para mostrar os postos que atendem revenda para Tectoy
            if ($login_fabrica == 6) { ?>
                <br />
                <a href="javascript: posto_revenda('<?= $login_fabrica; ?>')" rel='ajuda' title='Clique aqui para ver os postos de revenda'>
                    <font size=1><?=traduz('Listar postos')?></font>
                </a>
            <? } ?>
        </td>
        <?
        if (in_array($login_fabrica, array(158,169,170,178))) { ?>
            <td>
                <input type="text" id="centro_custo" name="centro_custo" value="<?=$centro_custo?>" class="frm" maxlength="10" size="10" />
            </td>
            <td>
                <input type="text" id="conta_contabil" name="conta_contabil" value="<?=$conta_contabil?>" class="frm" maxlength="25" size="10" />
            </td>
        <? } ?>

        <?php if ($login_fabrica == 183){ ?>
            <td>
                <input type="text" id="conta_contabil" name="conta_contabil" value="<?=$conta_contabil?>" class="frm" maxlength="25" size="10" />
            </td>
            <?php
                if (in_array($codigo_tipo_posto, array("Rev", "Rep"))){
                    echo "<td><select class='frm' name='cliente_representante' style='width:146px;'>";
                    if ($codigo_tipo_posto == "Rep"){
                        $sql = "
                            SELECT 
                                tbl_posto_fabrica.codigo_posto AS codigo,
                                tbl_posto.nome AS nome
                            FROM tbl_representante
                            JOIN tbl_posto_fabrica_representante ON tbl_posto_fabrica_representante.representante = tbl_representante.representante AND tbl_posto_fabrica_representante.fabrica = {$login_fabrica}
                            JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto_fabrica_representante.posto AND tbl_posto_fabrica.fabrica = {$login_fabrica}
                            JOIN tbl_posto ON tbl_posto.posto = tbl_posto_fabrica.posto
                            WHERE tbl_representante.fabrica = {$login_fabrica}
                            AND tbl_representante.codigo = '{$codigo}'";
                        $res = pg_query($con, $sql);
                    } else {
                        $sql = "
                            SELECT 
                                tbl_representante.codigo AS codigo,
                                tbl_representante.nome AS nome
                            FROM tbl_posto_fabrica_representante
                            JOIN tbl_representante ON tbl_representante.representante = tbl_posto_fabrica_representante.representante AND tbl_representante.fabrica = {$login_fabrica}
                            WHERE tbl_posto_fabrica_representante.fabrica = {$login_fabrica}
                            AND tbl_posto_fabrica_representante.posto = $posto";
                        $res = pg_query($con, $sql);
                    }
                    if (pg_num_rows($res) > 0){
                        for ($p=0; $p < pg_num_rows($res); $p++) { 
                            $info_codigo = pg_fetch_result($res, $p, "codigo");
                            $info_nome = pg_fetch_result($res, $p, "nome");
                            echo "<option>$info_codigo - $info_nome</option>";
                        }    
                    }
                    echo "</select></td>";
                }
            ?>
        <?php } ?>

        <?php if (in_array($login_fabrica, array(20))) {
            $sqlPA = "  SELECT  parametros_adicionais
                        FROM    tbl_posto_fabrica
                        WHERE   fabrica = $login_fabrica
                        AND     posto = $posto
            ";
            $resPA = pg_query($con,$sqlPA);
            $parametros_adicionais = pg_fetch_result($resPA,0,'parametros_adicionais');
            if(!empty($parametros_adicionais)){
                $adicionais = json_decode($parametros_adicionais,true);
                $foto_serie_produto = $adicionais['foto_serie_produto']=='t' ? ' checked' : '';
            } ?>
        <td title="Quando o posto digita '999' como número de série">
            <input type="checkbox" id="foto_serie_produto" name="foto_serie_produto" <?=$foto_serie_produto?> class="frm" maxlength="10" size="10" />
            <label for="foto_serie_produto"><?=traduz('Exige foto do núm. de série')?></label>
        </td>
        <? }
        if(in_array($login_fabrica, array(30,74)) && strlen($posto) > 0){
            $sqlKm = "  SELECT  parametros_adicionais
                        FROM    tbl_posto_fabrica
                        WHERE   fabrica = $login_fabrica
                        AND     posto = $posto
            ";
            $resKm = pg_query($con,$sqlKm);
            $parametros_adicionais = pg_fetch_result($resKm,0,parametros_adicionais);
            if(!empty($parametros_adicionais)){
                $adicionais = json_decode($parametros_adicionais,true);
                $fixo_km_valor = $adicionais['valor_km_fixo'];
                $qtde_km_posto = $adicionais['qtde_km_posto'];
                $fixo_km_valor = number_format($fixo_km_valor,2,',','.');
            }
?>
        <td>
            <input type="text" id="fixo_km_valor" name="fixo_km_valor" value="<?=$fixo_km_valor?>" class="frm" maxlength="10" size="10" />
        </td>
<?
            if($login_fabrica == 30){
?>
        <td>
            <input type="text" id="qtde_km_posto" name="qtde_km_posto" value="<?=$qtde_km_posto?>" class="frm" maxlength="10" size="10" />
        </td>
<?
            }
        }
?>

    <?php

    if($login_fabrica == 151){
        ?>
        <td>
            SIM <input type="radio" name="extrato_mais_3_meses" value="t" <?php echo ($extrato_mais_3_meses == "t") ? "checked" : ""; ?> >
            NÃO <input type="radio" name="extrato_mais_3_meses" value="f" <?php echo ($extrato_mais_3_meses == "f") ? "checked" : ""; ?> >
        </td>
        <td>
            <input type="input" id="valor_mao_obra" style="width: 45px;" class="frm" name="valor_mao_obra" value="<?=$valor_mao_obra?>">
        </td>
        <?php
    }

    if ($tipo_posto_multiplo) {
        $extra_attr .= ' multiple';
        $extra_attr = str_replace('1', '5', $extra_attr); // Múltiplo com altura de 5 linhas/ítens... ?>
        <td rowspan="4" valign="top">
            <select style="float:left" name='tipo_posto[]' <?=$extra_attr?>>
            <?=$sel_tipo_posto?>
            </select>
            <span style="display: inline-block; width: 40%;float:left; margin-left: 1em;color:#666"> <?=traduz('Utilize a tecla &lt;Ctrl&gt; e clique nos tipos para selecionar mais de um')?>.</span>
        </td>
<?  } ?>
    <?php if ($login_fabrica == 189) {?>
        <td>
            <select id="tipo_cliente" multiple  name='tipo_cliente[]'> 
                <option <?= (in_array("Cliente Final", $tipo_cliente)) ? "selected" : ""?> value="Cliente Final">Cliente Final</option>
                <option <?= (in_array("Varejo", $tipo_cliente)) ? "selected" : ""?> value="Varejo">Varejo</option>
                <option <?= (in_array("Industria", $tipo_cliente)) ? "selected" : ""?> value="Industria">Indústria</option>
            <option <?= (in_array("Tecnico", $tipo_cliente)) ? "selected" : ""?> value="Tecnico">Técnico</option>
            <option <?= (in_array("Exportacao", $tipo_cliente)) ? "selected" : ""?> value="Exportacao">Exportação</option>
            </select>
        </td>
    <?php }?>
    </tr>
<?php
    if(in_array($login_fabrica,array(24,50))){
?>
<tr align='left'>
<?php
    if ($login_fabrica == 50) {
?>
    <td width="50px"><?=traduz('Posto Isento')?></td>
<?php
    }
    if($login_fabrica != 50){
?>
    <td width="50px"><?=traduz('Devolver Peças')?></td>
    <?php } ?>
</tr>
<?
    $posto_isento = 'f';
    $devolver_pecas = 'f';
    if(isset($posto)){
        $sqlParametrosAdicionais = "SELECT parametros_adicionais FROM tbl_posto_fabrica WHERE posto={$posto} and fabrica = {$login_fabrica}";
        $res = pg_query($con, $sqlParametrosAdicionais);
        $posto_parametros_adicionais = pg_fetch_result($res, 0, 'parametros_adicionais');
        $posto_parametros_adicionais = json_decode($posto_parametros_adicionais,true);
        if(array_key_exists('posto_isento',$posto_parametros_adicionais) && $posto_parametros_adicionais['posto_isento'] == 't'){
            $posto_isento = $posto_parametros_adicionais['posto_isento'];
        }
        if(array_key_exists('devolver_pecas',$posto_parametros_adicionais) && $posto_parametros_adicionais['devolver_pecas'] == 't'){
            $devolver_pecas = $posto_parametros_adicionais['devolver_pecas'];

        }
    }
?>
    <tr align='left'>
<?php
    if ($login_fabrica == 50) {
?>
        <td>
            <?=traduz('Sim')?> <input type="radio" name="posto_isento" value="t" <?= ($posto_isento == 't') ? 'checked' : '' ?> />
            <?=traduz('Não')?> <input type="radio" name="posto_isento" value="f" <?= ($posto_isento == 'f') ? 'checked' : '' ?> />
        </td>
<?php
    }
    if($login_fabrica != 50){
?>
        <td>
            <?=traduz('Sim')?> <input type="radio" name="devolver_pecas" value="t" <?= ($devolver_pecas == 't') ? 'checked' : '' ?> />
            <?=traduz('Não')?> <input type="radio" name="devolver_pecas" value="f" <?= ($devolver_pecas == 'f') ? 'checked' : '' ?> />
        </td>
    <?php } ?>
    </tr>
<?php
}
?>
    <tr align='left'>

        <td><?=traduz('Senha da Tabela de Preço')?> <br />

        <?  echo "<span class='frm' style='margin-top: 8px;padding: 2px 1ex;display: inline-block;";
        echo ($senha_tabela_preco <> null) ? "'>$senha_tabela_preco" : 'color:red\'>Não Cadastrada';
        echo "</span>";
        ?>
       </td>

        <td><?=traduz('Senha do Financeiro')?> <br />
        <?  echo "<span class='frm' style='margin-top: 8px;padding: 2px 1ex;display: inline-block;";
        echo ($senha_financeiro <> null) ? "'>$senha_financeiro": 'color:red\'>Não Cadastrada';
        echo "</span>";
        ?>
         </td>
        <?php if (in_array($login_fabrica, array(184,200))) {?>
        <td>Garantia com Deslocamento<br />
            <input type="checkbox" id="garantia_com_deslocamento" class="frm" name="garantia_com_deslocamento" <?= ($garantia_com_deslocamento == 't') ? 'checked' : '';?> value="t">
        </td>
        <?php }
        if (in_array($login_fabrica, [169,170]) && !empty($posto)) { ?>
            <td>M.O. Triagem<br />
                <input type="text" style="width: 75px !important;" id="mo_triagem" name="mo_triagem" class="frm" value="<?= $mo_triagem ?>" />
            </td>
            <td align="center">
                <label style="position: relative;left: -75px;">
                    Triagem/Reoperação<br />
                    <input type="checkbox" name="digita_os_revenda" id="digita_os_revenda" value="t" <?= ($digita_os_revenda == 't') ? 'checked' : ""  ?> />
                </label>
            </td>
        <?php
        }
        ?>
        <?php /*if ($login_fabrica == 158) {?>
        <td>Preço Fixo Extrato<br />
            <input type="input" id="valor_mao_obra" class="frm" name="valor_mao_obra" value="<?=$valor_mao_obra?>">
        </td>
        <?php }*/?>

        <?
        if($login_fabrica == 147){
        echo "<td>".traduz("Limite de crédito")." <br />";
        echo "<span class='frm' style='margin-top: 8px;padding: 2px 1ex;display: inline-block; '>";
        echo ($credito <> null) ? str_replace(".", ",",$credito) : '0,00';
        echo "</span></td>";
        }
        ?>


    </tr>

    <tr align='left'>
        <?php if($login_fabrica != 74){ ?><td><?=traduz('Divulgar posto para o consumidor')?>?</td><?php } ?>
        <? if($login_fabrica == 1) { ?>
        <td colspan="2"><?=traduz('Atende somente revenda')?>?</td>
        <!-- hd_chamado=2693784 -->
        <td style='padding-right:100px;'><?=traduz('Contribuinte')?>?</td>
        <? } ?>
        <?php
        #HD 171607
        if(((in_array($login_fabrica,array(3))) && $login_privilegios == '*') or in_array($login_fabrica, array(30,35,50,72,74,134,151,153, 162))|| isset($usaEstoquePosto)) {
            ?>
            <td align='left' nowrap><?=traduz('Posto Controla Estoque')?>?</td>
            <?php
        }

        if($login_fabrica == 151){ ?>
            <td style="padding-left:60px" align='left' nowrap><?=traduz('Frete')?>?</td>
       <?}

        if($login_fabrica == 74){ ?>
            <td align='left' nowrap><?=traduz('Divulgar posto para o Call-Center')?>?</td>
            <td align='left' nowrap><?=traduz('Divulgar posto para o consumidor (mapa site)')?>?</td>
        <? }
        if (in_array($login_fabrica, array(169,170))) {
        ?>
        <td><?=traduz('E-Ticket')?></td>
        <td><?=traduz('Matriz')?></td>
        <?php
        }
        ?>
        <?php if ($login_fabrica == 158) { ?>
            <td>Zerar KM ?</td>
        <?php } ?>
    </tr>
    <tr align='left'>
<?php
        if($login_fabrica != 74){
?>
        <td>

<?php
            $disabled = " ";
            if (($divulgar_consumidor != 't') && ($divulgar_consumidor != 'f')){
                $divulgar_consumidor = 't';
                if (in_array($login_fabrica, array(50))) {
                    $divulgar_consumidor = 'f';
                }
            }
            if ($login_fabrica == 1 && $categoria_posto == "Compra Peca") {
                $divulgar_consumidor = 'f';
                $disabled = " disabled='disabled'";
            }
?>
            SIM<INPUT class='divulgar_consumidor' TYPE="radio" NAME="divulgar_consumidor" VALUE = 't' <?=($divulgar_consumidor == 't') ? "CHECKED" : ""?><?=$disabled?>>
            NÃO<INPUT class='divulgar_consumidor' TYPE="radio" NAME="divulgar_consumidor" VALUE = 'f' <?=($divulgar_consumidor == 'f') ? "CHECKED" : ""?><?=$disabled?>>
        </td>
<?php
        }

        if (($tipo_atende != 't') && ($tipo_atende != 'f')) $tipo_atende = 'f';
        if ($login_fabrica == 1) {
            if (strlen($posto) > 0) { //hd_chamado=2693784
                $sqlP_adicionais = "SELECT parametros_adicionais FROM tbl_posto_fabrica WHERE posto = {$posto} AND fabrica = {$login_fabrica}";
                $resP_adicionais = pg_query($con, $sqlP_adicionais);
                if (pg_num_rows($resP_adicionais) > 0) {
                    $parametrosAdicionais = json_decode(pg_fetch_result($resP_adicionais, 0, "parametros_adicionais"), true);
                    extract($parametrosAdicionais);
                    $tipo_contribuinte = utf8_decode($tipo_contribuinte);
                    if (strlen(trim($tipo_contribuinte)) > 0 AND $tipo_contribuinte <> 't') {
                        $tipo_contribuinte = "f";
                    } else {
                        $tipo_contribuinte = "t";
                    }
                } else {
                    $tipo_contribuinte = "t"; //hd_chamado=2693784
                }
            } else {
                $tipo_contribuinte = "t"; //hd_chamado=2693784
            }
?>
        <td colspan="2">

            <?=traduz('SIM')?><INPUT TYPE="radio" NAME="tipo_atende" VALUE = 't' <?if ($tipo_atende == 't') echo "CHECKED";?>>
            <?=traduz('NÃO')?><INPUT TYPE="radio" NAME="tipo_atende" VALUE = 'f' <?if ($tipo_atende == 'f') echo "CHECKED";?>>
        </td>

        <!-- //hd_chamado=2693784 -->
        <td colspan="2">
            <?=traduz('SIM')?><INPUT TYPE="radio" NAME="tipo_contribuinte" VALUE = 't' <?if ($tipo_contribuinte == 't') echo "CHECKED";?>>
            <?=traduz('NÃO')?><INPUT TYPE="radio" NAME="tipo_contribuinte" VALUE = 'f' <?if ($tipo_contribuinte == 'f') echo "CHECKED";?>>
        </td>

    <? } ?>
        <?php
        #HD 171607

        if(((in_array($login_fabrica,array(3))) && $login_privilegios == '*') or in_array($login_fabrica, array(30,35,50,72,74,134,151,153,162)) || isset($usaEstoquePosto)) {
            ?>
                <td>
                    <label><?=traduz('SIM')?> <INPUT TYPE="radio" NAME="controla_estoque" ID="controla_estoque_t" VALUE = 't' <?if ($controla_estoque == 't') echo "CHECKED";?>></label>
                    <label><?=traduz('NÃO')?> <INPUT TYPE="radio" NAME="controla_estoque" ID="controla_estoque_f" VALUE = 'f' <?if ($controla_estoque == 'f') echo "CHECKED";?>></label>
                </td>
            <?php
            }
            if($login_fabrica == 151){?>

                <td style="padding-left:60px">
                    <label><?=traduz('CIF')?> <INPUT TYPE="radio" NAME="tipo_frete" ID="tipo_frete_cif" VALUE = 'CIF' <?if ($tipo_frete == 'CIF') echo "CHECKED";?>></label>
                    <label><?=traduz('FOB')?> <INPUT TYPE="radio" NAME="tipo_frete" ID="tipo_frete_fob" VALUE = 'FOB' <?if ($tipo_frete == 'FOB') echo "CHECKED";?>></label>
                </td>

           <? }

        if($login_fabrica == 74 ){ ?>
            <td>
                <label><?=traduz('Fogo')?> <INPUT TYPE="checkbox" NAME="divulgar_consumidor_callcenter_fogo" ID="divulgar_consumidor_callcenter_fogo" VALUE = 't' <?if ($adicionais['divulgar_consumidor_callcenter_fogo'] == 't') echo "CHECKED";?>></label>
                <label><?=traduz('Portáteis')?> <INPUT TYPE="checkbox" NAME="divulgar_consumidor_callcenter_portateis" ID="divulgar_consumidor_callcenter_portateis" VALUE = 't' <?if ($adicionais['divulgar_consumidor_callcenter_portateis'] == 't') echo "CHECKED";?>></label>
            </td>
            <td>
                <label><?=traduz('Fogo')?> <INPUT TYPE="checkbox" NAME="divulgar_consumidor_mapa_fogo" ID="divulgar_consumidor_mapa_fogo" VALUE = 't' <?if ($adicionais['divulgar_consumidor_mapa_fogo'] == 't') echo "CHECKED";?>></label>
                <label><?=traduz('Portáteis')?> <INPUT TYPE="checkbox" NAME="divulgar_consumidor_mapa_portateis" ID="divulgar_consumidor_mapa_portateis" VALUE = 't' <?if ($adicionais['divulgar_consumidor_mapa_portateis'] == 't') echo "CHECKED";?>></label>
            </td>
        <?php }
        if (in_array($login_fabrica, array(169,170))) {
            if (isset($_POST['e_ticket'])) {
                $e_ticket = $_POST['e_ticket'];
            }
        ?>
            <td><input type="checkbox" name="e_ticket" value="t" <?=($e_ticket == 't') ? 'checked' : ''?>></td>
            <td><input type="checkbox" name="matriz" value="t" <?=($matriz == 't') ? 'checked' : ''?>></td>
        <? } ?>
        
        <?php 
            // Gravar o valor da FLAG "Nao Zera KM" 
            if ($login_fabrica == 158) { ?>
                <td>
                    <acronym title='Esta informação trabalha em conjunto com a informação item de aparência no cadastro de peças. Deixando setado como SIM, este posto vai conseguir lançar peças de item de aparência nas Ordens de Serviço de Revenda.'>
                        SIM<INPUT TYPE="radio" NAME="zera_km" VALUE = 't' <? if ($zera_km == 't') echo "checked"; ?>>
                        NÃO<INPUT TYPE="radio" NAME="zera_km" VALUE = 'f' <? if ($zera_km <> 't') echo "checked"; ?>>
                    </acronym>
                </td>
            <?php } ?>
    </tr>
    <? if ($login_fabrica == 158) {
        if ($posto_interno == "t" || !isset($posto_interno)) {
            $display_hora_qtde_atendimento = "display: none;";
        } ?>
        <tr class="hora_qtde_atendimento" align='left' <?=$display_hora_qtde_atendimento?> >
            <td style="color: rgb(168, 0, 0);" ><?=traduz('Capacidade diária de atendimentos')?></td>
            <td style="color: rgb(168, 0, 0);" ><?=traduz('Início do Trabalho')?></td>
            <td style="color: rgb(168, 0, 0);" ><?=traduz('Fim do Trabalho')?></td>
        </tr>
        <tr class="hora_qtde_atendimento" align='left' <?=$display_hora_qtde_atendimento?> >
            <td><input type="text" class="numerico frm" style="width: 40px;" name="qtde_atendimento" value="<?=$qtde_atendimento?>" /></td>
            <td><input type="text" class="mascara_tempo frm" style="width: 60px;" name="inicio_trabalho" value="<?=$inicio_trabalho?>" /></td>
            <td><input type="text" class="mascara_tempo frm" style="width: 60px;" name="fim_trabalho" value="<?=$fim_trabalho?>" /></td>
        </tr>
    <? }

    if ($login_fabrica == 158) {
        $aux_latitude  = ($latitude == "null")  ? "" : $latitude;
        $aux_longitude = ($longitude == "null") ? "" : $longitude; 
    ?>
        <tr align='left'>
            <td><?=traduz('Latitude')?></td>
            <td><?=traduz('Longitude')?></td>
        </tr>
        <tr align='left'>
            <td><input type="text" name="latitude" value="<?=$aux_latitude;?>"></td>
            <td><input type="text" name="longitude" value="<?=$aux_longitude;?>"></td>
        </tr>
    <?php }

    if (in_array($login_fabrica, array(169,170,190))) { ?>
        <tr class="qtde_atendimento" align='left'>
            <td><?=traduz('Quantidade de atendimentos')?></td>
            <?php if (in_array($login_fabrica, array(169,170))) { ?>
    	    <td><?=traduz('Código do Cliente')?></td>
            <td colspan="2">&nbsp;&nbsp;&nbsp;&nbsp;<?=traduz('Horário de Funcionamento');?></td>
            <?php } ?>
        </tr>
        <tr class="qtde_atendimento" align='left'>
            <td><input type="text" class="numerico frm" style="width: 40px;" name="qtde_atendimento" value="<?=$qtde_atendimento?>" /></td>
            <?php if (in_array($login_fabrica, array(169,170))) { ?>
	           <td><input type="text" class="frm" name="codigo_cliente" value="<?=$codigo_cliente?>" /></td>
                <td colspan="2">&nbsp;&nbsp;&nbsp;&nbsp; Das <input style="width: 40px;" type="text" class="frm mascara_tempo" name="horario_funcionamento_inicio" value="<?= $horario_funcionamento_inicio ?>" /> 
                ás  <input style="width: 40px;" type="text" class="frm mascara_tempo" name="horario_funcionamento_fim" value="<?= $horario_funcionamento_fim ?>" /></td>
            <?php } ?>
        </tr>
    <? }

    if (in_array($login_fabrica, array(86)) AND strlen($posto) > 0) {
        $sqlParametrosAdicionais = "SELECT parametros_adicionais FROM tbl_posto_fabrica WHERE posto = {$posto} AND fabrica = {$login_fabrica}";
        $resParametrosAdicionais = pg_query($con, $sqlParametrosAdicionais);

        if (pg_num_rows($resParametrosAdicionais) > 0) {
            $parametrosAdicionais = json_decode(pg_fetch_result($resParametrosAdicionais, 0, "parametros_adicionais"), true);
            extract($parametrosAdicionais);
        }
    }

    if($login_fabrica == 104 && strlen($posto) > 0){ //HD 2303024
            $sqlAuditado = "  SELECT  parametros_adicionais
                        FROM    tbl_posto_fabrica
                        WHERE   fabrica = $login_fabrica
                        AND     posto = $posto";
            $resAuditado = pg_query($con,$sqlAuditado);
            $parametros_adicionais = pg_fetch_result($resAuditado,0,parametros_adicionais);

            if(!empty($parametros_adicionais)){
                $adicionais = json_decode($parametros_adicionais,true);
                $posto_auditado = $adicionais['posto_auditado'];
            }
    ?>
        <tr align="left">
            <td>
                <?=traduz('Posto Auditado')?><br/>
                <input type="checkbox" name="posto_auditado" value="t" <? if ($posto_auditado == "t") echo "checked"; ?> />
            </td>
        </tr>
    <?php
    }

    if (in_array($login_fabrica, array(86))) {
    ?>
        <tr>
            <td>
                <table border="0" style="table-layout: fixed;">
                    <tr>
                        <td>
                            <input type="checkbox" name="contrato" value="t" <? if ($contrato == "t") echo "checked"; ?> /> <?=traduz('Contrato')?>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <input type="checkbox" name="site" value="t" <? if ($site == "t") echo "checked"; ?> /> <?=traduz('Site')?>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <input type="checkbox" name="kit_credenciamento" value="t" <? if ($kit_credenciamento == "t") echo "checked"; ?> /> <?=traduz('Kit Credenciamento')?>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    <?php
    }
    ?>
    <?php if ($login_fabrica == 15){ //755863 estoque novo latinatec
        if (strlen($posto) > 0){
            $sql = "select categoria from tbl_posto_fabrica where fabrica=$login_fabrica and posto=$posto";
            $res = pg_query($con,$sql);
            $categoria = strtolower(pg_fetch_result($res, 0, 'categoria'));
            $CHECKED =  (pg_num_rows($res)>0 and  $categoria == 'vip' ) ? 'CHECKED' : '';
            $CHECKED_NO =  (pg_num_rows($res)==0 or $categoria == '') ? 'CHECKED' : '';
        }
    ?>
        <tr>
            <td align="left"><?=traduz('Posto VIP')?>?</td>
        </tr>
        <tr>
            <td align="left">
                SIM <input type="radio" name="posto_vip" id="posto_vip" value="vip" <?php echo $CHECKED ?> >
                &nbsp;
                NÃO<input type="radio" name="posto_vip" id="posto_vip2" value='false' <?php echo $CHECKED_NO ?> >
            </td>
        </tr>
        <tr align="left">
            <td><?=traduz('Tipo de Controle de Estoque')?></td>
            <td>&nbsp;</td>
        </tr>
        <tr align="left">
            <td>
                <select name="tipo_controle_estoque" id="tipo_controle_estoque" class='frm'>
                    <?php
                    if ($controla_estoque == 'f' and $controle_estoque_novo == 'f' and $controle_estoque_manual == 'f'){
                        $select_nenhum = "SELECTED";
                    }elseif ($controla_estoque == 't' and $controle_estoque_novo == 'f' and $controle_estoque_manual == 'f') {
                        $select_normal = "SELECTED";
                    }elseif ($controla_estoque == 'f' and $controle_estoque_novo == 't' and $controle_estoque_manual == 'f') {
                        $select_novo = "SELECTED";
                    }elseif ($controla_estoque == 'f' and $controle_estoque_novo == 'f' and $controle_estoque_manual == 't') {
                        $select_manual = "SELECTED";
                    } ?>
                    <option value="nenhum" <?=$select_nenhum?> > <?=traduz('Nenhum')?> </option>
                    <option value="estoque_normal" <?=$select_normal?> > <?=traduz('Estoque Normal')?> </option>
                    <option value="estoque_novo" <?=$select_novo?> > <?=traduz('Estoque Novo')?> </option>
                    <option value="estoque_manual" <?=$select_manual?> > <?=traduz('Estoque Manual')?> </option>
                </select>
            </td>
            <td>&nbsp;</td>
        </tr>
    <?php }?>

    <?if ($login_fabrica == 42){  #HD 401553?>
         <tr align='left'>
            <td>
                <?$posto_filial_check = ($posto_filial ==  't') ? "CHECKED" : null;?>
                <input type="checkbox" name='posto_filial' id='posto_filial' value='t' <?echo $posto_filial_check?>/>
                <?=traduz('Posto é Filial')?>
            </td>
        </tr>
        <tr style='text-align: left;' >
            <td>
                <?
                $checked  = ($entrega_tecnica == 't') ? "checked" : "" ;
                if (strlen($tipo_posto) > 0){
                    $sql = "select tipo_revenda from tbl_tipo_posto where fabrica = $login_fabrica and tipo_posto = $tipo_posto";
                    $res = pg_query($con, $sql);

                    $tipo_revenda = pg_fetch_result($res, 0, tipo_revenda);
                    $readonly = ($tipo_revenda == "t") ? "readonly='readonly'" : "";
                }
                ?>
                <input type='checkbox' name='entrega_tecnica' id='entrega_tecnica' value='t' <?=$checked?> <?=$readonly?> />
                <?=traduz('Realiza Entrega Técnica')?>
            </td>
        </tr>

    <?php
    }

    if ($login_fabrica == 11 or $login_fabrica == 172) {
    ?>
        <tr align='left'>
            <td>
                <?=traduz('Permitir abrir OS para produto que estão marcados como Não abrir OS no cadastro')?> ?
            </td>
        </tr>
        <tr align='left'>
            <td>
                Sim <input type='radio' name='permite_envio_produto' <?=($permite_envio_produto == 't') ? 'CHECKED' : ''?> value='t' />
                Não <input type='radio' name='permite_envio_produto' <?=($permite_envio_produto == 'f' || !strlen($permite_envio_produto)) ? 'CHECKED' : ''?> value='f' />
            </td>
        </tr>
    <?php
    }
    $cols = ($login_fabrica == 35) ? "style='width: 610px;'" : "cols='75'";
    $rows = ($login_fabrica == 35) ? "4" : "2";
    ?>

    <tr >
        <td colspan='5'><?=traduz('Observações')?> <?php echo ($login_fabrica == 35) ? traduz("Internas") : ""; ?></td>
    </tr>
    <?php if($login_fabrica  != 1){
        echo "<tr style='color:#ff0000'>";
            echo "<td colspan='5'><b>".traduz("As informações gravadas neste campo ficarão visíveis para o Posto Autorizado.")."</b></td>";
        echo "</tr>";

    }?>
    <tr>
        <td colspan='5'>
            <textarea class='frm' name="obs" id="obs" <?=$cols?> rows="<?=$rows?>"><? echo $obs ?></textarea>
        </td>
    </tr>



    <?php

    if($login_fabrica == 35){

    ?>

    <?php

    if(strlen($posto) > 0){

        $sql = "SELECT parametros_adicionais FROM tbl_posto_fabrica WHERE posto = {$posto} AND fabrica = {$login_fabrica}";
        $res = pg_query($con, $sql);

        $parametros_adicionais = pg_fetch_result($res, 0, "parametros_adicionais");

        if(strlen($parametros_adicionais) > 0){

            $obs = json_decode($parametros_adicionais, true);

            $obs_cadence = utf8_decode($obs["obs_cadence"]);
            $obs_cadence = str_replace("\\", "", $obs_cadence);

            $obs_oster = utf8_decode($obs["obs_oster"]);
            $obs_oster = str_replace("\\", "", $obs_oster);

        }else{
            $obs_cadence = "";
            $obs_oster = "";
        }

    }

    ?>

    <tr>
        <td colspan="5">
            <table style="width: 100%;">
                <tr>
                    <td style="text-align: center;">

                        <?=traduz('Observações')?> (Cadence)<br />
                        <textarea class="frm" name="obs_cadence" cols="30" rows="4"><?=$obs_cadence?></textarea>

                        <br />

                    </td>

                    <td style="text-align: center;">

                        <?=traduz('Observações')?> (Oster) <br />
                        <textarea class="frm" name="obs_oster" cols="30" rows="4"><?=$obs_oster?></textarea>

                    </td>
                </tr>
            </table>
        </td>
    </tr>

    <?php

    }

    ?>

</table>

<br />
    <!--Revendas para OS-Consumidor vinculada -->
 <?php

 if (in_array($login_fabrica, array(141,144)) AND strlen($posto) > 0) {
     $sqlParametrosAdicionais = "SELECT parametros_adicionais FROM tbl_posto_fabrica WHERE posto = {$posto} AND fabrica = {$login_fabrica}";
     $resParametrosAdicionais = pg_query($con, $sqlParametrosAdicionais);

     if (pg_num_rows($resParametrosAdicionais) > 0) {
         $parametrosAdicionais = json_decode(pg_fetch_result($resParametrosAdicionais, 0, "parametros_adicionais"), true);
         extract($parametrosAdicionais);
     }
 }

  if($login_fabrica == 74){
      if($tipo_posto == 437){
          $displayNone = "";
      }else{
          $displayNone = "style='display:none'";
      }
?>
<?
      if(!empty($posto)) {

          $sqlParametrosAdicionais = "SELECT parametros_adicionais FROM tbl_posto_fabrica where posto = {$posto} and fabrica = {$login_fabrica}";
          $resParametrosAdicionais = pg_query($con,$sqlParametrosAdicionais);
          $parametros_adicionais = pg_fetch_result($resParametrosAdicionais, 0,"parametros_adicionais");

          if(strlen($parametros_adicionais) > 0) {
              $parametros_adicionais = json_decode($parametros_adicionais);
              $arrRevendaOSVinculada = $parametros_adicionais->revendaOSVinculada;
              $qtde_revenda_os_vinculada = count($arrRevendaOSVinculada);
          }
      }
?>
      <div id="revenda_os_consumidor_vinculada" <?=$displayNone?>>
        <input type="hidden" name="qtde_revenda_os_vinculada" value="<?=$qtde_revenda_os_vinculada?>"/>
        <table id="table_revenda" class="formulario" style="margin:0 auto; width: 700px; border:0;">
            <tr>
                <td class="titulo_tabela" colspan="3"> <?=traduz('OS-Consumidor Vinculada')?> </td>
            </tr>
            <tr>
                <td  align="left"> <?=traduz('Nome Revenda')?> </td>
                <td align="left"> <?=traduz('CNPJ Revenda')?> </td>
                <td  align="center"></td>

            <tr width="100%">
                <td  align="left" >
                    <input class="frm" type="text" name="revenda_nome" id="revenda_nome" size="30" maxlength="50" value="" />
                    <img src='imagens/lupa.png' border='0' align='absmiddle' onclick="javascript: pesquisaRevenda ($('#revenda_nome').val(), 'nome');" style='cursor: pointer'>
                </td>
                <td  align="left">
                    <input class="frm" type="text" name="revenda_cnpj" size="20" maxlength="18" id="revenda_cnpj" value="" >
                    <img src='imagens/lupa.png' border='0' align='absmiddle' onclick='javascript: pesquisaRevenda ($("#revenda_cnpj").val(), "cnpj");' style='cursor: pointer'>
                </td>
                                 <td>
                                 <button type="button" onclick="adicionarRevenda()" > <?=traduz('Adicionar')?></button>
                                 </td>
            </tr>

        </table>

            <table id="revendas_os_consumidor_vinculada" class="formulario">
                <thead>
                    <tr class="titulo_tabela">
                        <th><?=traduz('CNPJ')?></th>
                        <th><?=traduz('Nome Revenda')?></th>
                        <th><?=traduz('Ações')?></th>
                    </tr>
                </thead>
                <tbody>

<?
                  foreach($arrRevendaOSVinculada as $revenda) {
                      $sqlRevenda = "SELECT revenda, cnpj, nome from tbl_revenda where revenda = {$revenda}";
                      $resSqlRevenda = pg_query( $con,$sqlRevenda);
                      $dadosRevenda = pg_fetch_object($resSqlRevenda,0); ?>

                      <tr rel="<?=$dadosRevenda->revenda?>">
                         <td align="left">
                            <?=$dadosRevenda->cnpj?>
                            <input type="hidden" value="<?=$dadosRevenda->revenda?>" name="hidden_revenda_0">
                        </td>
                        <td align="left"><?=$dadosRevenda->nome?></td>
                        <td align="center"><button type="button" onclick="excluirRevendaOSVinculada(<?=$dadosRevenda->revenda?>)"><?=traduz('Excluir')?></button></td>
                      </tr>
                      <?
                  }
?>

                </tbody>
            </table>
        </div>
<?
}


if (in_array($login_fabrica, array(189))) {

   
    ?>
    <table id="posto_cep_atendido" class="formulario" style="margin: 0 auto; width: 700px; border: 0; " >
        <tr>
            <td class="titulo_tabela" >
                CEPs Atendidos
            </td>
        </tr>
        <tr>
            <td>
                <input type="text" id="adicionar_novo_cep_input" maxlength="9" placeholder="99999-999" /> <button type="button" id="adicionar_novo_cep_button" >Adicionar CEP</button>
            </td>
        </tr>
        <tr>
            <td>
                <hr style="width: 100%;" />
                <p style="text-align: center;" >
                    CEPs Cadastrados
                </p>

                <div id="cep_atendido" style="height: 200px; margin: 0 auto; background-color: #FFFFFF; border: 1px solid #D6D6D6; overflow-y: auto; width: 140px; padding: 10px 0px 10px 0px;" >
                    <?php
                    if ($_POST["cep_atendido"]) {
                        foreach ($_POST["cep_atendido"] as $cep) {
                            echo "
                                <div style='width: 100%; height: auto; padding-bottom: 2px; margin-bottom: 5px; border-bottom: 1px solid #D6D6D6;' >
                                    ".substr($cep, 0, 5)."-".substr($cep, 5, 3)."
                                    &nbsp;
                                    <button cep='$cep' type='button' class='remover_cep_atendido' title='Remover CEP' >X</button>
                                </div>
                            ";
                        }
                    } else if (!empty($posto)) {
                        $sql = "
                            SELECT
                                cep_inicial
                            FROM tbl_posto_cep_atendimento
                            WHERE fabrica = $login_fabrica
                            AND posto = $posto
                        ";
                        $res = pg_query($con, $sql);

                        $ceps_atendidos = pg_fetch_all($res);

                        foreach ($ceps_atendidos as $cep_array) {
                            $cep = $cep_array["cep_inicial"];

                            echo "
                                <div style='width: 100%; height: auto; padding-bottom: 2px; margin-bottom: 5px; border-bottom: 1px solid #D6D6D6;' >
                                    ".substr($cep, 0, 5)."-".substr($cep, 5, 3)."
                                    &nbsp;
                                    <button cep='$cep' type='button' class='remover_cep_atendido' title='Remover CEP' >X</button>
                                </div>
                            ";
                        }
                    }
                    ?>
                </div>
                <select id="cep_atendido_select" name="cep_atendido[]" multiple="multiple" style="display: none;" >
                    <?php
                    if ($_POST["cep_atendido"]) {
                        foreach ($_POST["cep_atendido"] as $cep) {
                            echo "<option value='$cep' selected >$cep</option>";
                        }
                    } else if (!empty($posto)) {
                        foreach ($ceps_atendidos as $cep_array) {
                            $cep = $cep_array["cep_inicial"];

                            echo "<option value='$cep' selected >$cep</option>";
                        }
                    }
                    ?>
                </select>
            </td>
        </tr>
    </table>
    <br />
<?
}


















if (in_array($login_fabrica, array(158))) {

    /*if ($posto_interno == "t" || !isset($posto_interno)) {
        $display_cep_atendido = "display: none;";
    }*/
    ?>
    <!--<table id="posto_cep_atendido" class="formulario" style="margin: 0 auto; width: 700px; border: 0; <?=$display_cep_atendido?>" >
        <tr>
            <td class="titulo_tabela" >
                CEPs Atendidos
            </td>
        </tr>
        <tr>
            <td>
                <input type="text" id="adicionar_novo_cep_input" maxlength="9" placeholder="99999-999" /> <button type="button" id="adicionar_novo_cep_button" >Adicionar CEP</button>
            </td>
        </tr>
        <tr>
            <td>
                <hr style="width: 100%;" />
                <p style="text-align: center;" >
                    CEPs Cadastrados
                </p>

                <div id="cep_atendido" style="height: 200px; margin: 0 auto; background-color: #FFFFFF; border: 1px solid #D6D6D6; overflow-y: auto; width: 140px; padding: 10px 0px 10px 0px;" >
                    <?php
                    if ($_POST["cep_atendido"]) {
                        foreach ($_POST["cep_atendido"] as $cep) {
                            echo "
                                <div style='width: 100%; height: auto; padding-bottom: 2px; margin-bottom: 5px; border-bottom: 1px solid #D6D6D6;' >
                                    ".substr($cep, 0, 5)."-".substr($cep, 5, 3)."
                                    &nbsp;
                                    <button cep='$cep' type='button' class='remover_cep_atendido' title='Remover CEP' >X</button>
                                </div>
                            ";
                        }
                    } else if (!empty($posto)) {
                        $sql = "
                            SELECT
                                cep_inicial
                            FROM tbl_posto_cep_atendimento
                            WHERE fabrica = $login_fabrica
                            AND posto = $posto
                        ";
                        $res = pg_query($con, $sql);

                        $ceps_atendidos = pg_fetch_all($res);

                        foreach ($ceps_atendidos as $cep_array) {
                            $cep = $cep_array["cep_inicial"];

                            echo "
                                <div style='width: 100%; height: auto; padding-bottom: 2px; margin-bottom: 5px; border-bottom: 1px solid #D6D6D6;' >
                                    ".substr($cep, 0, 5)."-".substr($cep, 5, 3)."
                                    &nbsp;
                                    <button cep='$cep' type='button' class='remover_cep_atendido' title='Remover CEP' >X</button>
                                </div>
                            ";
                        }
                    }
                    ?>
                </div>
                <select id="cep_atendido_select" name="cep_atendido[]" multiple="multiple" style="display: none;" >
                    <?php
                    if ($_POST["cep_atendido"]) {
                        foreach ($_POST["cep_atendido"] as $cep) {
                            echo "<option value='$cep' selected >$cep</option>";
                        }
                    } else if (!empty($posto)) {
                        foreach ($ceps_atendidos as $cep_array) {
                            $cep = $cep_array["cep_inicial"];

                            echo "<option value='$cep' selected >$cep</option>";
                        }
                    }
                    ?>
                </select>
            </td>
        </tr>
    </table>

    <br />-->

    <table class="formulario" style="margin: 0 auto; width: 700px; border: 0;"cellpadding="1" cellspacing="3">
        <tr>
            <td class='titulo_tabela'>
                <?=traduz('Unidades de Negócio vinculadas ao posto')?>
            </td>
        </tr>
        <tr>
            <td >
                <?

                if (count($distribuidores_selected) > 0) {
                    $implode_distribuidores_selected = implode(",", $distribuidores_selected);
                }

                $oDistribuidorSLA = new DistribuidorSLA();
                $oDistribuidorSLA->setFabrica($login_fabrica);
                $distribuidores_disponiveis = $oDistribuidorSLA->SelectUnidadeNegocioNotIn($implode_distribuidores_selected); ?>
                <span style="text-align:left;display:inline-block;">
                    Disponíveis:<br />
                    <select id="distribuidores" style="min-width:150px;margin:5px;" size="6">
                        <? foreach ($distribuidores_disponiveis as $unidadeNegocio) { ?>
                            <option value="<?= $unidadeNegocio['distribuidor_sla']; ?>"><?= $unidadeNegocio['cidade']; ?></option>
                        <? } ?>
                    </select>
                </span>
                <span style="text-align:left;display:inline-block;">
                    <?=traduz('Selecionados')?>: <span style="color:#FF0000">(<?=traduz('marque a Unidade principal')?>)</span><br />
                    <div id="distribuidores_selected_display" style="min-width:150px;height:100px;margin:5px;background-color:#FFFFFF;border:1px solid #D6D6D6;overflow-y:auto;">
                        <?
                        if (count($distribuidores_posto) == 0 && count($_POST["distribuidores_selected"]) > 0) { /*HD - 6224590*/
                                 
                            if (!empty($posto) && count($distribuidores_selected) > 0) {
                                $PostoOrDistribuidor = " AND (tbl_distribuidor_sla_posto.posto = {$posto} OR tbl_distribuidor_sla.distribuidor_sla IN (".implode(",", $distribuidores_selected).")";
                                  
                            } else if (!empty($posto)) {
                                $PostoOrDistribuidor = " AND tbl_distribuidor_sla_posto.posto = {$posto} ";
                                  
                            } else if (count($distribuidores_selected) > 0) {
                                $PostoOrDistribuidor = "AND tbl_distribuidor_sla.distribuidor_sla IN (".implode(",", $distribuidores_selected).")";
                                  
                            }
                                 
                            $sqlUnidadeNegocio = "
                               SELECT DISTINCT
                                tbl_distribuidor_sla.unidade_negocio,
                                MAX(tbl_distribuidor_sla.distribuidor_sla) AS distribuidor_sla,
                                tbl_distribuidor_sla.unidade_negocio||' - '||tbl_unidade_negocio.nome as cidade
                                FROM tbl_distribuidor_sla_posto
                                LEFT JOIN tbl_distribuidor_sla USING(distribuidor_sla, fabrica)
                                JOIN tbl_unidade_negocio ON tbl_unidade_negocio.codigo = tbl_distribuidor_sla.unidade_negocio
                                RIGHT JOIN tbl_cidade USING(cidade)
                                WHERE tbl_distribuidor_sla_posto.fabrica = {$login_fabrica}
                                GROUP BY tbl_distribuidor_sla.unidade_negocio, tbl_cidade.nome, tbl_unidade_negocio.nome;
                            ";

                            $resUnidadeNegocio = pg_query($con, $sqlUnidadeNegocio);
                            $distribuidores_posto = pg_fetch_all($resUnidadeNegocio);
                        }
                           
                        foreach($distribuidores_posto as $value) {
                            $checkedUnidade = ($unidade_principal == $value['distribuidor_sla']) ? "checked" : ""; ?>
                            <div id="unidade_selecionada_<?= $value['distribuidor_sla']; ?>" style='width:100%;padding-bottom:2px;'>
                                <input type="radio" name="unidade_principal" value="<?= $value['distribuidor_sla']; ?>" <?= $checkedUnidade; ?> />
                                <?= $value['cidade']; ?>
                                <button type='button' class='remover_unidade_negocio' title='<?traduz("Remover Unidade de Negócio")?>' rel="<?= $value['distribuidor_sla']; ?>" style="float:right">X</button>
                            </div>
                        <? } ?>
                    </div>
                </span>
                <select multiple="multiple" id="distribuidores_selected" name="distribuidores_selected[]" style="display:none;">
                    <? foreach($distribuidores_posto as $value) { ?>
                        <option value="<?= $value['distribuidor_sla']; ?>" class="option-selectable" selected><?= $value['cidade']; ?></option>
                    <? } ?>
                </select>
            </td>
            <script>
                $(function() {
                    $(document).on("click", "#distribuidores", function() {
                        var distribuidor = $(this).val();
                        var option_clone = $(this).find("option:selected");
                        var unidade_negocio = $(this).find("option:selected").text();

                        $("#distribuidores_selected_display").append(
                            "<div id='unidade_selecionada_"+distribuidor+"' style='width:100%;padding-bottom:2px;'>\
                                <input type='radio' name='unidade_principal' value='"+distribuidor+"' />\
                                "+unidade_negocio+"\
                                <button type='button' class='remover_unidade_negocio' title='<?=traduz("Remover Unidade de Negócio")?>' rel='"+distribuidor+"' style='float:right'>X</button>\
                            </div>"
                        );

                        $(option_clone).prop({ selected: true }).addClass("option-selectable");
                        $("#distribuidores_selected").append(option_clone);

                        $(this).find("option:selected").remove();
                    });

                    $(document).on("click", ".remover_unidade_negocio", function() {
                        var distribuidor = $(this).attr('rel');
                        var principal = $("input[name=unidade_principal]:checked").val();
                        var option_clone = $("#distribuidores_selected option[value="+distribuidor+"]").clone();

                        if (distribuidor == principal) {
                            alert('<?=traduz("Unidade de negócio principal, desmarque para remover")?>');
                            return false;
                        }

                        $("#distribuidores").append(option_clone);
                        $("#unidade_selecionada_"+distribuidor).remove();
                        $("#distribuidores_selected option[value="+distribuidor+"]").remove();
                    });
                });
            </script>
        </tr>
    </table>
    <br />
    <table class="formulario" style="margin:0 auto;width:700px;border:0;" cellpadding="1" cellspacing="3">
        <tr>
            <td class='titulo_tabela'>
                <?=traduz('Tipos de atendimento do posto')?>
            </td>
        </tr>
        <tr>
            <td>
            <?
            $sqlTipoAtend = "SELECT * FROM tbl_tipo_atendimento WHERE fabrica = {$login_fabrica} AND ativo IS TRUE;";
            $resTipoAtend = pg_query($con, $sqlTipoAtend);
            $tiposAtendimento = pg_fetch_all($resTipoAtend);

            if ($_POST["selected_atendimentos"]) {
                $json_tipos_atendimento = json_encode($_POST["selected_atendimentos"]);
            }

            if (count($tiposAtendimento) > 0) { ?>
                <span style="text-align:  left; display: inline-block;">
                    <?=traduz('Disponíveis')?>:<br />
                    <select multiple="multiple" id="tipos_atendimento" style="min-width: 150px; height: 100px; margin:5px;" >
                        <? foreach ($tiposAtendimento as $tipoAtendimento) { ?>
                            <option value="<?= $tipoAtendimento['tipo_atendimento']; ?>"><?= $tipoAtendimento['descricao']; ?></option>
                        <? } ?>
                    </select>
                </span>
                <script>
                    $(function() {
                        var multiselect = {
                            g: null,
                            h: null,
                            options_selected: '<?=$json_tipos_atendimento?>',
                            init: function(g, options_selected) {
                                this.g = $(g);

                                $(this.g).find("option").addClass("option-selectable");

                                var h = this.g.clone();

                                this.h = h;

                                $(this.h).attr({ id: "multiselect-selected_atend" });
                                $(this.h).attr({ name: "selected_atendimentos[]" });
                                $(this.h).find("option").remove();
                                $(this.g).parent().after($("<span></span>", { css: { "text-align": "left", display: "inline-block" }, html: "Selecionados:<br />" }).append(h));
                                $(this.g).attr({ id: "multiselect-selectable_atend", name: "multiselect-selectable_atend" });

                                if (multiselect.options_selected != undefined && multiselect.options_selected != '' && multiselect.options_selected != null) {
                                    multiselect.options_selected = JSON.parse(multiselect.options_selected);

                                    $(this.g).find("option").each(function(){
                                        if ($.inArray($(this).val(), multiselect.options_selected) != -1) {
                                            var option_clone = $(this).clone();
                                            $(option_clone).prop({ selected: true }).addClass("option-selectable");
                                            $(multiselect.h).append(option_clone);
                                            $(this).remove();
                                        }
                                    });
                                }

                                this.trigger();
                            },
                            trigger: function() {
                                $(document).delegate("#multiselect-selectable_atend option.option-selectable", "click", function() {
                                    var o = $(this).clone();
                                    $(o).prop({ selected: true });
                                    $(multiselect.h).append(o);
                                    $(this).remove();
                                });

                                $(document).delegate("#multiselect-selected_atend option.option-selectable", "click", function() {
                                    var o = $(this).clone();
                                    $(o).prop({ selected: false });
                                    $(multiselect.g).append(o);
                                    $(this).remove();
                                    $("#multiselect-selected_atend").find("option").prop({ selected: true });
                                });
                            }
                        }

                        multiselect.init("#tipos_atendimento");
                    });
                </script>
            <? } ?>
            </td>
        </tr>
    </table>
    <br />
    <table class="formulario" style="margin: 0 auto; width: 700px; border: 0;"cellpadding="1" cellspacing="3">
        <tr>
            <td class='titulo_tabela'>
                <?=traduz('Grupos de Clientes atendidos pelo Posto')?>
            </td>
        </tr>
        <tr>
            <td>
            <?
            $sqlGrupCli = "SELECT * FROM tbl_grupo_cliente WHERE fabrica = {$login_fabrica} AND ativo IS TRUE;";
            $resGrupCli = pg_query($con, $sqlGrupCli);
            $gruposClientes = pg_fetch_all($resGrupCli);

            if ($_POST["selected_grupos"]) {
                $json_grupos_clientes = json_encode($_POST["selected_grupos"]);
            }

            if (count($gruposClientes) > 0) { ?>
                <span style="text-align:left;display:inline-block;">
                    Disponíveis:<br />
                    <select multiple="multiple" id="grupos_clientes" style="min-width:150px;height:100px;margin:5px;" >
                        <? foreach ($gruposClientes as $grupoClientes) { ?>
                            <option value="<?= $grupoClientes['grupo_cliente']; ?>"><?= $grupoClientes['descricao']; ?></option>
                        <? } ?>
                    </select>
                </span>
                <script>
                    $(function() {
                        var multiselect = {
                            j: null,
                            k: null,
                            options_selected: '<?=$json_grupos_clientes?>',
                            init: function(j, options_selected) {
                                this.j = $(j);

                                $(this.j).find("option").addClass("option-selectable");

                                var k = this.j.clone();

                                this.k = k;

                                $(this.k).attr({ id: "multiselect-selected_grupos" });
                                $(this.k).attr({ name: "selected_grupos[]" });
                                $(this.k).find("option").remove();
                                $(this.j).parent().after($("<span></span>", { css: { "text-align": "left", display: "inline-block" }, html: "Selecionados:<br />" }).append(k));
                                $(this.j).attr({ id: "multiselect-selectable_grupos", name: "multiselect-selectable_grupos" });

                                if (multiselect.options_selected != undefined && multiselect.options_selected != '' && multiselect.options_selected != null) {
                                    multiselect.options_selected = JSON.parse(multiselect.options_selected);

                                    $(this.j).find("option").each(function(){
                                        if ($.inArray($(this).val(), multiselect.options_selected) != -1) {
                                            var option_clone = $(this).clone();
                                            $(option_clone).prop({ selected: true }).addClass("option-selectable");
                                            $(multiselect.k).append(option_clone);
                                            $(this).remove();
                                        }
                                    });
                                }

                                this.trigger();
                            },
                            trigger: function() {
                                $(document).delegate("#multiselect-selectable_grupos option.option-selectable", "click", function() {
                                    var o = $(this).clone();
                                    $(o).prop({ selected: true });
                                    $(multiselect.k).append(o);
                                    $(this).remove();
                                });

                                $(document).delegate("#multiselect-selected_grupos option.option-selectable", "click", function() {
                                    var o = $(this).clone();
                                    $(o).prop({ selected: false });
                                    $(multiselect.j).append(o);
                                    $(this).remove();
                                    $("#multiselect-selected_atend").find("option").prop({ selected: true });
                                });
                            }
                        }

                        multiselect.init("#grupos_clientes");
                    });
                </script>
            <? } ?>
            </td>
        </tr>
    </table>
    <br />
<?
}
?>
<!-- Responsável Legal -->
<? if(in_array($login_fabrica, array(167,203))) { ?>
    <table class="formulario" style="margin: 0 auto; width: 700px; border: 0;"cellpadding="1" cellspacing="3">
        <tr>
            <td colspan="4" class='titulo_tabela'>
                Responsável Legal
            </td>
        </tr>
        <tr  align='left'>
            <td>Nome</td>
            <td>CPF</td>
            <td>RG</td>        
        </tr>
        <tr align='left'>
            <td>
                <input class='frm nome_responsavel' type="text" name="nome_responsavel" value="<? echo $nome_responsavel ?>">
            </td>
            <td>
                <input class='frm cpf_responsavel' type="text" name="cpf_responsavel" value="<? echo $cpf_responsavel ?>">
            </td>   
            <td>
                <input class='frm rg_responsavel' type="text" name="rg_responsavel" value="<? echo $rg_responsavel ?>">
            </td>     
        </tr>
    </table>
    <br/>
    <table class="formulario" style="margin: 0 auto; width: 700px; border: 0;"cellpadding="1" cellspacing="3">
        <tr>
            <td colspan="4" class='titulo_tabela'>
                Aceite Contrato
            </td>
        </tr>
        <tr align='left'>
            <td><b>Número Contrato</b></td>
            <td><b>Nome</b></td>
            <td><b>Data/Hora</b></td>
        </tr>        
            <? 
                $sqlContrato = "SELECT DISTINCT
                                    tbl_posto_contrato.data_input,
                                    tbl_contrato.numero_contrato,
                                    tbl_posto_contrato.campos_adicionais::json->>'nome_aceite' AS nome_aceite
                                FROM tbl_posto_contrato
                                JOIN tbl_contrato ON tbl_contrato.fabrica = tbl_posto_contrato.fabrica 
                                AND tbl_contrato.contrato = tbl_posto_contrato.contrato
                                JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto_contrato.posto
                                AND tbl_posto_fabrica.fabrica = tbl_posto_contrato.fabrica                 
                                WHERE tbl_posto_contrato.fabrica = {$login_fabrica}
                                AND tbl_posto_fabrica.posto = {$posto}
                                AND  tbl_posto_contrato.confirmacao = 't'                                
                                ORDER BY tbl_posto_contrato.data_input ASC";

                $resContrato = pg_query($con, $sqlContrato);

                $contadorContrato = pg_num_rows($resContrato);

                for($i=0; $i<$contadorContrato; $i++){                    
                    $numero_contrato = pg_fetch_result($resContrato, $i, "numero_contrato");
                    $data_input      = pg_fetch_result($resContrato, $i, "data_input");
                    $nome_aceite     = pg_fetch_result($resContrato, $i, "nome_aceite");

                    $data_input = date("d/m/Y H:i:s", strtotime($data_input));
                ?>
                    <tr align='left'>
                        <td><?=$numero_contrato ?></td>
                        <td><?=utf8_encode($nome_aceite) ?></td>
                        <td><?=$data_input ?></td>
                    </tr>
                <?
                }
            ?>        
    </table>
    <br/>    
<? } ?>
<!--   Cobranca  -->
<table class="formulario" style="margin: 0 auto; width: 700px; border: 0;"cellpadding="1" cellspacing="3">
    <tr>
        <td colspan="4" class='titulo_tabela'>
            <?=traduz('Informações para cobrança')?>
        </td>
    </tr>
    <!-- Sem a linha abaixo, aparece errado no IE.. ??? -->
    <tr  align='left'>
        <td><?=traduz('CEP')?></td>
        <td><?=traduz('Endereço')?></td>
        <td><?=traduz('Número')?></td>
        <td><?=traduz('Complemento')?></td>
    </tr>
    <tr align='left'>
        <td>
            <input class='frm' type="text" name="cobranca_cep" id="cobranca_cep" size="10" maxlength="10" value="<? echo $cobranca_cep ?>" />
        </td>
        <td>
            <input class='frm' type="text" name="cobranca_endereco" size="30" maxlength="50" value="<? echo $cobranca_endereco ?>">
        </td>
        <td>
            <input class='frm' type="text" name="cobranca_numero" size="10" maxlength="10" value="<? echo $cobranca_numero ?>">
        </td>
        <td>
            <input class='frm' type="text" name="cobranca_complemento" size="10" maxlength="20" value="<? echo $cobranca_complemento ?>">
        </td>
    </tr>
    <tr  align='left'>
        <td><?=traduz('Bairro')?></td>
        <td><?=traduz('Estado')?></td>
        <td colspan="2"><?=traduz('Cidade')?></td>
    </tr>
    <tr align='left'>
        <td><input class='frm' type="text" name="cobranca_bairro" size="20" maxlength="30" value="<?= $cobranca_bairro ?>"></td>
        <td>
            <?php if (!empty($pais_cadastro)) {
                $listaDeEstadosDoPais = $array_estados($pais_cadastro);
                //$cobranca_estado;
            }

            $displaySelCobUf = 'style="display:none;" disabled';
            $displayInpCobUf = 'style="display:block;"';
            if (count($listaDeEstadosDoPais) > 0) {         
                $optionHtml = '<option value=""></option>';
                foreach($listaDeEstadosDoPais as $siglaEstado => $descEstado) {
                $isSelected = ($cobranca_estado == $siglaEstado) ? 'selected' : '';
                $optionHtml .= "<option value='{$siglaEstado}' {$isSelected}>".strtoupper($descEstado)."</option>";
                }
            $displaySelCobUf = 'style="display:block;"';
            $displayInpCobUf = 'style="display:none;" disabled';
            } ?>

                <select name="cobranca_estado" id="cobranca_estado" class="frm addressState" <?=$displaySelCobUf;?>>
                    <?= $optionHtml ?>      
            </select>
            <?php
            
            ?>
            <input name="cobranca_estado" id="cobranca_estado_txt" class="frm" value="<?= $cobranca_estado_txt ?>" <?=$displayInpCobUf;?> />
        </td>
        <td colspan="2">
	    <?php 

        if (!empty($pais_cadastro) && !empty($cobranca_estado)) {
                        $listaDeCidadesDoEstado = getCidadesDoEstado($pais_cadastro, $cobranca_estado);
            }

            $displaySelecCidade = 'style="display: none" disabled';
            $displayInputCidade = 'style="display: block"';

            $optionHtml = '';
            if (count($listaDeCidadesDoEstado) > 0) {

                foreach($listaDeCidadesDoEstado as $resCidade){
                    $isSelected = (strtoupper($resCidade['cidade']) == strtoupper($cobranca_cidade)) ? 'selected' : '';
                    $optionHtml .= "<option value='{$resCidade['cidade']}' {$isSelected}> {$resCidade['cidade']}  </option>";
                }
                $displaySelecCidade = 'style="display: block"';
                $displayInputCidade = 'style="display: none"';
            } 

            if ($pais_cadastro == "BR" || count($array_estados($pais_cadastro)) > 0) {
                $displaySelecCidade = 'style="display: block"';
                $displayInputCidade = 'style="display: none" disabled';
            }

            ?>
            <select name="cobranca_cidade" id="cobranca_cidade" class="frm addressCity" <?=$displaySelecCidade;?>>
                <?= $optionHtml ?>
            </select>
            <input class='frm' type="text" name="cobranca_cidade"  id="cobranca_cidade_txt" size="20" maxlength="30" value="<?= $cobranca_cidade ?>" <?=$displayInputCidade;?> />
        </td>
    </tr>
</table>

<?php if ($login_fabrica==156) { ?>
<table class="formulario" style="margin: 0 auto; width: 700px; border: 0;"cellpadding="1" cellspacing="3">
    <tr>
        <td colspan="4" class='titulo_tabela'>
            <?=traduz('Atestado de Capacitação')?>
        </td>
    </tr>
    <!-- Sem a linha abaixo, aparece errado no IE.. ??? -->

    <tr>
        <td align='left'>
            <b><?=traduz('Ativo')?></b>
        </td>
    <td align='left'>
            <b><?=traduz('Capacitação')?></b>
        </td>
                <td align='left'>
          <b>  <?=traduz('Validade')?></b>
        </td>
    </tr>
    <script type="text/javascript" charset="utf-8">
            $(function()
            {
                $("input[rel='data_mask']").mask("99/99/9999");
                $("input[rel='data_mask']").datepick({startDate : "01/01/2000"});

                $("input[rel='check_cap']").click(function(){
                    var ide = $(this).val();
                    if( this.checked == true){
                        $("input[name='data_capacitacao_"+ide+"']").parents('td').show();
                    }else{
                        $("input[name='data_capacitacao_"+ide+"']").parents('td').hide();
                    }
                });

            });
        </script>
        <?

        $sql = "SELECT parametros_adicionais FROM tbl_posto_fabrica WHERE posto = {$posto} AND fabrica = {$login_fabrica}";
        $res = pg_query($con, $sql);

        if (pg_num_rows($res) > 0) {
            $parametrosAdicionais = json_decode(pg_fetch_result($res, 0, "parametros_adicionais"), true);
        }
        foreach ($atestadoCapacitacao as $key => $value) {
            $label = $value;
            if($label == "Balanca"){
                $label = 'Balança';
            }

            if(strlen($parametrosAdicionais["data_capacitacao_$value"]) > 0 and empty($msg_erro)) {
                $_POST["data_capacitacao_$value"] = $parametrosAdicionais["data_capacitacao_$value"];
            }
            if(strlen($parametrosAdicionais["capacitacao_$value"]) and empty($msg_erro)){
                $_POST["data_capacitacao_$value"] = $parametrosAdicionais["data_capacitacao_$value"];
            }

            echo "<tr  align='left'>";
            echo "<td><input type='checkbox' name='capacitacao_{$value}' rel='check_cap' value='{$value}' ".((strlen($_POST["data_capacitacao_$value"]) >0 || strlen($_POST["data_capacitacao_$value"])>0)? "CHECKED":"")." ></td>";
            echo "<td>".$label."</td>";

            if(strlen($_POST["data_capacitacao_$value"])>0 || strlen($_POST["data_capacitacao_$value"])>0){
                $display = "block";
            }else{
                $display = "none";
            }

            echo "<td style='display:$display;'>
                            <input class='frm'
                                        type='text'
                                        name='data_capacitacao_{$value}'
                                        rel='data_mask' size='12' maxlength='16'
                                        value='".((strlen($_POST["data_capacitacao_$value"])>0)? $_POST["data_capacitacao_$value"]  :"")."'

                            >
                    </td>";
            echo "</tr>";
        }
        ?>
    </tr>
</table>
<?
}


# HD 55187
if ($login_fabrica == 45){
    $sqlPriv = "SELECT admin FROM tbl_admin WHERE admin = $login_admin
                AND (privilegios LIKE '%financeiro%' OR privilegios LIKE '*')";
    $resPriv = pg_query($con,$sqlPriv);
    if (pg_num_rows($resPriv) == 0){
        $readonly = " READONLY";
    }
}
?>
<br />

<table class="formulario" style="margin: 0 auto; width: 700px; border: 0;" cellpadding="1" cellspacing="3">
    <tr><td colspan='4' class='titulo_tabela'><?=traduz('Informações Bancárias')?></td></tr>
    <tr  align='left'>
        <td width = '33%'><?=traduz('CPF/CNPJ Favorecido')?></td>
        <td colspan=3><?=traduz('Nome Favorecido')?></td>
    </tr>
    <tr align='left'>
        <td width = '33%'>
        <input class='frm' type="text" name="cpf_conta" size="14" maxlength="19" value="<? echo $cpf_conta ?>"
        <?php
        if (strlen($cpf_conta)>0){
            echo $readonly;
        }
        ?>></td>
        <td colspan=3>
        <input class='frm' type="text" name="favorecido_conta" size="50" maxlength="50" value="<? echo $favorecido_conta ?>"
        <?php
        if (strlen($favorecido_conta)>0){
            echo $readonly;
        }
        ?>></td>
    </tr>
    <tr  align='left'>
        <td colspan='4' width = '100%'><?=traduz('Banco')?></td>
    </tr>
    <tr align='left'>
        <td colspan='4'>
            <?
            $sqlB = "SELECT codigo, nome
                    FROM tbl_banco
                    ORDER BY codigo";
            $resB = pg_query($con,$sqlB);
            if (pg_num_rows($resB) > 0) {
                echo "<select class='frm xbanco' name='banco' size='1'";
                if (isset($readonly) and strlen($banco)>0){ // HD 85519
                    echo " onfocus='defaultValue=this.value' onchange='this.value=defaultValue' ";
                }
                echo ">";
                echo "<option value=''></option>";
                for ($x = 0 ; $x < pg_num_rows($resB) ; $x++) {
                    $aux_banco     = trim(pg_fetch_result($resB,$x,codigo));
                    $aux_banconome = pg_fetch_result($resB,$x,nome);
                    echo "<option value='" . $aux_banco . "'";
                    if ($banco == $aux_banco) echo " selected";
                    echo ">" . $aux_banco . " - " . $aux_banconome . "</option>";
                }
                echo "</select>";
            }
            if (strlen($posto) > 0 && $login_fabrica == 3) {
                echo "<input name='banco' type='hidden' id='banco'>";
            }
            ?>
        </td>
    </tr>
    <tr  align='left'>
        <td width = '33%'><?=traduz('Tipo de Conta')?></td>
        <td width = '33%'><?=traduz('Agência')?></td>
        <td width = '34%'><?=traduz('Conta')?></td>
        <? if($login_fabrica == 45 ){?>
        <td width = '34%'><?=traduz('Operação')?></td>
        <?}?>
    </tr>
    <tr align='left'>
        <td width = '33%'>
            <select class='frm' name='tipo_conta'
            <?php
                if (isset($readonly) and strlen($tipo_conta)>0){
                    echo " DISABLED";
                } ?>>
                <option selected></option>
                <option value='Conta conjunta'   <? if ($tipo_conta == 'Conta conjunta')   echo "selected"; ?>><?=traduz('Conta conjunta')?></option>
                <option value='Conta corrente'   <? if ($tipo_conta == 'Conta corrente')   echo "selected"; ?>><?=traduz('Conta corrente')?></option>
                <option value='Conta individual' <? if ($tipo_conta == 'Conta individual') echo "selected"; ?>><?=traduz('Conta individual')?></option>
                <option value='Conta jurídica'   <? if ($tipo_conta == 'Conta jurídica')   echo "selected"; ?>><?=traduz('Conta jurídica')?></option>
                <option value='Conta poupança'   <? if ($tipo_conta == 'Conta poupança')   echo "selected"; ?>><?=traduz('Conta poupança')?></option>
            </select>
        </td>
        <td width = '33%'>
        <input  class='frm' type="text" name="agencia" size="10" maxlength="10" value="<? echo $agencia ?>"
        <?php
        if (strlen($agencia)>0){
            echo $readonly;
        }
        ?>>

        <?php
        if($login_fabrica == 151){
            ?>
            <input type="text" name="digito_agencia" value="<?=$digito_agencia?>" size="3" maxlenght="3" />
            <?php
        }
        ?>

        </td>
        <td width = '34%'>
        <input class='frm' type="text" name="conta" size="15" maxlength="15" value="<? echo $conta ?>"
        <?php
        if (strlen($conta)>0){
            echo $readonly;
        }
        ?>>

        <?php
        if($login_fabrica == 151){
            ?>
            <input type="text" name="digito_conta" value="<?=$digito_conta?>" size="3" maxlenght="3" />
            <?php
        }
        ?>

        </td>
        <? if($login_fabrica == 45 ){?>
        <td width = '34%'>
        <input class='frm' type="text" name="conta_operacao" size="5" maxlength="3" value="<? echo $conta_operacao ?>"
        <?php
        if (strlen($conta_operacao)>0){
            echo $readonly;
        }
        ?>></td>
        <?}?>
    </tr>
    <tr >
        <td colspan="4"><?=traduz('Observações')?></td>
    </tr>
    <?php if($login_fabrica  != 1){
        echo "<tr style='color:#ff0000'>";
            echo "<td colspan='5'><b>As informações gravadas neste campo ficarão visíveis apenas para os usuários administradores do sistema (fábrica).</b></td>";
        echo "</tr>";

    }?>
    <tr>
        <td colspan="4">
            <textarea class='frm' name="obs_conta" cols="75" rows="2"
            <?php
            if (strlen($obs_conta)>0){
                echo $readonly;
            }?>><? echo $obs_conta; ?></textarea>
        </td>
    </tr>
</table>

<br />
    <? if($login_fabrica == 30){ //HD 356653 Início?>
        <table class='formulario' style="margin: 0 auto; width: 700px;">
            <tr class='titulo_tabela'><td colspan='3'><?=traduz('Dados Contábeis')?></td></tr>
            <tr>
                <td align='left'>
                    <?=traduz('Conta Contábil')?> <br />
                    <input type='text' name='conta_contabil' id='conta_contabil' size='17' maxlength='25' value='<? echo $conta_contabil; ?>' class='frm'>
                </td>
                <td align='left'>
                    <?=traduz('Centro Custo')?> <br />
                    <input type='text' name='centro_custo' id='centro_custo' size='17' maxlength='25' value='<? echo $centro_custo; ?>' class='frm'>
                </td>
                <td align='left'>
                    <?=traduz('Local Entrega')?> <br />
                    <input type='text' name='local_entrega' id='local_entrega' size='40' maxlength='50' value='<? echo $local_entrega; ?>' class='frm'>
                </td>
            </tr>
        </table>

        <br />
    <? } //HD 356653 Fim ?>

<!--   linhas, tabelas Distribuidores  -->
<?php if ($login_fabrica < 175 || in_array($login_fabrica, [180,181,182,183])) { ?>
<table class="formulario" style="margin: 0 auto; width: 700px; border: 0;" cellpadding="1" cellspacing="3">
<tr>

<td class='titulo_tabela'>
<!-- criar imagem com texto referente a linha e tabela -->
<?=traduz('Linhas e Tabelas')?>
</td>

</tr>
<tr>

<td>

<?
if($login_fabrica == 20 AND strlen($posto)>0) {
    $sql = "SELECT tabela FROM tbl_posto_fabrica WHERE fabrica = $login_fabrica AND posto = $posto";
    $resX = @pg_query ($con,$sql);
    $tabela =  @pg_fetch_result($resX,0,tabela);

    $sql = "SELECT * FROM tbl_tabela WHERE fabrica = $login_fabrica AND ativa ORDER BY sigla_tabela";
    $resX = pg_query ($con,$sql);

    echo "<select class='frm' name='tabela_unica'>\n";
    echo "<option selected></option>\n";

    $contadorresX = pg_num_rows($resX); 

    for($x=0; $x < $contadorresX; $x++){
        $check = "";
        if ($tabela == pg_fetch_result($resX,$x,tabela)) $check = " selected ";
        echo "<option value='".pg_fetch_result($resX,$x,tabela)."' $check>".pg_fetch_result($resX,$x,sigla_tabela)." - ". pg_fetch_result($resX,$x,descricao)."</option>";
    }

    echo "</select>\n";
}
if ($login_fabrica <> 20) {?>
    <TABLE  class='formulario' style="margin: 0 auto; width: 100%; border: 0;" cellpadding='1' cellspacing='3'>
        <tr align='left'>
             <?php
            if (in_array($login_fabrica, array(117))) {
            ?>
                <TD><?=traduz('Linha')?></TD>
                <TD><?=traduz('Macro-Família')?></TD>
            <?php
            }
            if (!in_array($login_fabrica,array(14,117))) {
            ?>
                <TD><?=traduz('Linha')?></TD>
            <?php
            } else if (!in_array($login_fabrica,array(117))) {
            ?>
                <TD><?=traduz('Família')?></TD>
            <?php
            }
            if (!in_array($login_fabrica, array(117))) {
            ?>
                <td style="text-align: center;"><?=traduz('Atende')?></td>
            <?php
            } 

            if (in_array($login_fabrica, [148])) { ?>
                <td>Categoria</td>
            <?php
            }

        if($login_fabrica == 50){ ?>
            <TD><?=traduz('Auditar OS 24hrs')?></TD>
        <?php
        }

        if((!in_array($login_fabrica,array(30,35,40,74,98,101,104,105,115,116,121,122,123,124,125,128,129,131,136,140)) && !isset($novaTelaOs)) || (in_array($login_fabrica,array(152,180,181,182)  ||  isset($tabelaPrecoUnica)))) { ?>
            <TD>Tabela</TD>
        <?php
        } elseif(in_array($login_fabrica,array(30,98))) { ?>
            <TD><?=traduz('Tabela Faturada')?></TD>
            <TD><?=traduz('Tabela Garantia ')?></TD>
        <?php
        } elseif($login_fabrica == 35 or $login_fabrica == 74 or $login_fabrica == 104 or $login_fabrica == 105 ) { ?>
            <TD><?=traduz('Tabela Venda')?></TD>
            <?php
            if($login_fabrica == 74){ ?>
                <TD><?=traduz('Tabela Recompra')?></TD>
            <?php
            }
            if ($login_fabrica != 35) { ?>
                <TD><?=traduz('Tabela Garantia')?></TD>
            <?php
            }
            if($login_fabrica == 104){ //HD-3022970 ?>
                <TD><?=traduz('Tabela de Acessório')?></TD>
            <?php
            }

        } else { ?>
            <TD><?=traduz('Tabela Garantia')?></TD>
            <?php
            if($login_fabrica == 138){
                echo "<TD>Tabela Revenda</TD>";
                echo "<TD>Tabela Consumo</TD>";
            }elseif ($login_fabrica < 175 || $login_fabrica == 183){ ?>
            <TD><?=traduz('Tabela Faturada')?></TD>
            <?php
            }
        }

        if (!in_array($login_fabrica, array(94,98,101,117,122,81,114)) AND $login_fabrica < 120 && $login_fabrica) {//HD 677353 ?>
            <TD><?=traduz('Desconto')?></TD>
        <?php
        }

        if(in_array($login_fabrica, array(172))){
            ?> <td> <?=traduz('Desconto </td> ')?><?php
        }

        if (!in_array($login_fabrica, array(74,86,94,101,104,115,116,117,81)) AND $login_fabrica < 120 || in_array($login_fabrica, array(139)))  {//HD 387824, 677353?>

            <TD><?=traduz('Distribuidor')?></TD>
        <?php
        }

        if(in_array($login_fabrica, array(172))){
            ?> <td> <?=traduz('Distribuidor </td> ')?><?php
        }

        if ($login_fabrica == 24){ 
?>
            <td><?=traduz('Divulgar linha ao consumidor')?></td>
<?php
        } 
?>
    </tr>
<?php
    if (!in_array($login_fabrica,array(14, 117))) {
        $sql = "SELECT  tbl_linha.linha,
                        tbl_linha.nome
                FROM    tbl_linha
                WHERE   ativo IS TRUE
                AND     tbl_linha.fabrica = $login_fabrica
                ORDER BY nome,codigo_linha ";
        $res = pg_query ($con,$sql);

        for ($i = 0; $i < pg_num_rows($res); $i++) {

            $linha        = pg_fetch_result($res, $i, 'linha');
            $check        = "";
            $auditar_os   = "";
            $tabela       = "";
            $desconto     = "";
            $distribuidor = "";
	
			if(!empty($posto)) {
				$sql = "SELECT  tbl_posto_linha.tabela              ,
					tbl_posto_linha.desconto            ,
					tbl_posto_linha.distribuidor        ,
					tbl_posto_linha.tabela_posto        ,
					tbl_posto_linha.tabela_bonificacao  ,
					tbl_posto_linha.auditar_os          ,
					tbl_posto_linha.divulgar_consumidor ,
                    tbl_posto_linha.categoria_posto
					FROM    tbl_posto_linha
					WHERE   posto = $posto
					AND     linha = $linha
";
						if ($login_fabrica == 2) {
							$sql = "SELECT  DISTINCT
								tbl_posto_linha.tabela              ,
								tbl_posto_linha.desconto            ,
								tbl_posto_linha.distribuidor        ,
								tbl_posto_linha.tabela_posto        ,
								tbl_posto_linha.tabela_bonificacao  ,
								tbl_posto_linha.divulgar_consumidor
								FROM    tbl_posto_linha
								WHERE   posto = $posto
								AND     linha = $linha
";
						}

						$resX = pg_query ($con,$sql);

						if (pg_num_rows($resX) == 1) {
							$check                      = " CHECKED ";
							$tabela                     = pg_fetch_result($resX, 0, 'tabela');
							$desconto                   = pg_fetch_result($resX, 0, 'desconto');
							$distribuidor               = pg_fetch_result($resX, 0, 'distribuidor');
							$tabela_posto               = pg_fetch_result($resX, 0, 'tabela_posto');
							$tabela_bonificacao         = pg_fetch_result($resX, 0, 'tabela_bonificacao');
							$auditar_os                 = pg_fetch_result($resX, 0, 'auditar_os');
							$divulga_consumidor_linha   = pg_fetch_result($resX, 0, 'divulgar_consumidor');
                            $categoria_posto_linha      = pg_fetch_result($resX, 0, 'categoria_posto');
						} else {
							$tabela_posto = "";
						}

						if (pg_num_rows ($resX) > 1) {
?>
				<h1> <?=traduz('ERRO NAS LINHAS, AVISE TELECONTROL')?> </h1>
<?
							exit;
						}
			}
            if (strlen ($msg_erro) > 0) {
                $atende             = $_POST ['atende_'             . $linha];
                $auditar_os         = $_POST ['auditar_os_'         . $linha];
                $tabela             = $_POST ['tabela_'             . $linha];

                // if ($login_fabrica >= 175){
                //     $tabela_posto       = $_POST ['tabela_posto'];
                // }else{
                //     $tabela_posto       = $_POST ['tabela_posto_'       . $linha];
                // }
                $tabela_posto           = $_POST ['tabela_posto_'       . $linha];
                $tabela_bonificacao     = $_POST ['tabela_bonificacao_'  . $linha];
                $desconto               = $_POST ['desconto_'           . $linha];
                $distribuidor           = $_POST ['distribuidor_'       . $linha];
                $categoria_posto_linha  = $_POST['categoria_'.$linha];

                if ($login_fabrica == 24 ){
                    $divulga_consumidor_linha = $_POST [ 'divulga_consumidor_' . $linha ];
                }
                if ( strlen ($atende) > 0 ) $check = " CHECKED ";

                if($login_fabrica == 50){
                    if ( strlen ($auditar_os) > 0 ) {
                        $check_os = " CHECKED ";
                    }
                }
            }

            if ($login_fabrica == 24 ){
                $check_divulga = ($divulga_consumidor_linha == 't') ? "CHECKED" : null; #HD 383050
            }
            if ($login_fabrica == 50 ){
                $check_os = ($auditar_os == 't') ? "CHECKED" : null;
            }
?>
            <tr align='left'>

                <td nowrap><? echo pg_fetch_result ($res,$i,nome); ?></td>
                <td align='center'><input type='checkbox' name='atende_<?=$linha?>' value='<?=$linha?>' <?=$check?>></td>
<?
            if (in_array($login_fabrica, [148])) { ?>
                <td>
                    <select name="categoria_<?= $linha ?>" class="frm">
                        <option value=""></option>
                        <?php

                        $sqlCategoria = "SELECT DISTINCT ON (tbl_categoria_posto.categoria_posto)
                                                tbl_categoria_posto.categoria_posto,
                                                tbl_categoria_posto.nome,
                                                tbl_categoria_posto.ativo
                                         FROM tbl_categoria_posto
                                         JOIN tbl_diagnostico ON tbl_categoria_posto.categoria_posto = tbl_diagnostico.categoria_posto
                                         AND tbl_diagnostico.ativo
                                         AND tbl_diagnostico.linha = {$linha}
                                         WHERE tbl_categoria_posto.fabrica = {$login_fabrica}
                                         AND tbl_categoria_posto.ativo IS TRUE";
                        $resCategoria = pg_query($con, $sqlCategoria);

                        while ($dadosCat = pg_fetch_object($resCategoria)) { 

                            $selectedCat = ($categoria_posto_linha == $dadosCat->categoria_posto) ? "selected" : "";

                            ?>
                            <option value="<?= $dadosCat->categoria_posto ?>" <?= $selectedCat ?>><?= $dadosCat->nome ?></option>
                        <?php
                        } ?>
                    </select>
                </td>
            <?php
            }

            if($login_fabrica == 50){
?>
                <td align='center'><input type='checkbox' name='auditar_os_<?=$linha?>' value='<?=$linha?>' <?=$check_os?>></td>
<?
            }
?>
                <td align='left'>
<?
            if ($login_fabrica == 6) {

                $sql  = "SELECT * FROM tbl_tabela WHERE fabrica = $login_fabrica and ativa ORDER BY sigla_tabela";
                $resX = pg_query($con,$sql);

                echo "<select class='frm' name='tabela_$linha'>\n";
                echo "<option selected></option>\n";

                for ($x = 0; $x < pg_num_rows($resX); $x++) {
                    $check = "";
                    if ($tabela == pg_fetch_result($resX, $x, 'tabela')) $check = " selected ";
                    echo "<option value='".pg_fetch_result($resX, $x, 'tabela')."' $check>".pg_fetch_result($resX, $x, 'sigla_tabela')." - ". pg_fetch_result($resX, $x, 'descricao')."</option>";
                }

            }

            if ($login_fabrica == 104 and !empty($posto)) {
                //HD 845757 - item 1 análise
                //seleciona a tabela que o posto hoje está cadastrado para mostrar na frente. mesmo que a tabela esteja inativa... deverá mostrar

                $sql = "SELECT tbl_tabela.tabela, tbl_tabela.descricao, tbl_tabela.sigla_tabela
                          FROM tbl_posto_linha
                          JOIN tbl_tabela USING (tabela)
                         WHERE tbl_tabela.fabrica = $login_fabrica
                           AND tbl_posto_linha.posto=$posto
                           AND tbl_posto_linha.linha=$linha
                UNION
                      SELECT tbl_tabela.tabela, tbl_tabela.descricao, tbl_tabela.sigla_tabela
                        FROM tbl_tabela
                       WHERE fabrica = $login_fabrica
                         AND ativa
                         /*AND tabela_garantia*/
                         AND SUBSTR(sigla_tabela, 1, 2) = '$estado'
                    ORDER BY sigla_tabela";

                $res_tabela_1 = pg_query($con,$sql);

                if ($debug AND pg_last_error($con))
                    pre_echo(nl2br($sql) . "\n<br />" . pg_last_error($con), "Tabela selecionada: $tabela");

                if (pg_num_rows($res_tabela_1)>0) {

                    unset($tabelas_linha, $combo_tabelas);
                    $tabelas_linha = pg_fetch_all($res_tabela_1);

                    if ($debug == 2)
                        echo array2table($tabelas_linha, count($tabelas_linha) . " Tabelas para a linha $linha");

                    foreach ($tabelas_linha as $item_tabelas_linha) {
                        $combo_tabelas[$item_tabelas_linha['tabela']] = $item_tabelas_linha['sigla_tabela'] . ' - ' .
                                                                        strtoupper(trim($item_tabelas_linha['descricao']));
                    }

                    echo array2select("tabela_$linha", null, $combo_tabelas, $tabela, " class='frm'", ' ', true);

                }

            } else if(!isset($tabelaPrecoUnica)){

                $condgar = '';

                if(in_array($login_fabrica,array(40,101,115,116,121,122,123,124,125,128,131,136,140)) || (isset($novaTelaOs) && !in_array($login_fabrica, array(35,145,150,175,180,181,182)))) {
                    $condgar = ' AND tabela_garantia ';
                }

                if (in_array($login_fabrica , array(147,150))){
                    unset($condgar);
                    if ($estado != 'SP' AND $login_fabrica == 147){
                        $cond_estado = " AND tabela_principal = true ";
                    }
                }

                if (in_array($login_fabrica, array(35))) {
                    $cond = " AND tbl_tabela.tabela_garantia IS NOT TRUE";
                }
                $sql  = "   SELECT  tbl_tabela.tabela       ,
                                    tbl_tabela.descricao    ,
                                    tbl_tabela.sigla_tabela
                            FROM    tbl_tabela
                            WHERE   fabrica = $login_fabrica
                            AND     ativa IS TRUE $condgar
                                    {$cond_estado} {$cond}
                      ORDER BY      sigla_tabela";
                $resX = pg_query($con,$sql);
?>
                    <select class='frm' name='tabela_<?=$linha?>' style='width: 170px'>
                        <option selected></option>
<?
                for ($x = 0; $x < pg_num_rows($resX); $x++) {

                    $q_tabela = pg_fetch_result($resX, $x, 'tabela');
                    $q_descricao = pg_fetch_result($resX, $x, 'descricao');
                    $q_sigla = pg_fetch_result($resX, $x, 'sigla_tabela');

                    $check = "";
                    if ($tabela == $q_tabela) $check = " selected ";

                    //HD 677353 - Para a Delonghi aparecer apenas estas tabelas quando for garantia
                    $delonghi = ($login_fabrica == 101 && in_array($q_tabela, array(575)));

                    if ($delonghi || $login_fabrica != 101) {
?>
                        <option value='<?=$q_tabela?>' <?=$check?>><?=$q_sigla." - ".$q_descricao?></option>
<?
                    }

                }

            }

?> 
                    </select>
                </td>
<?
            if (in_array($login_fabrica, array(30,40,74,98,101,105,115,116,121,122,123,124,125,128,129,131,136,140,183)) || (isset($novaTelaOs) && !in_array($login_fabrica, array(152,180,181,182)) AND $login_fabrica < 175) || isset($tabelaPrecoUnica)){

                if(in_array($login_fabrica,array(40,101,115,116,121,122,123,124,125,128,131,136,140)) || (isset($novaTelaOs) && !in_array($login_fabrica, array(145,150,156,162)))) {
                    $condfat = ' AND tabela_garantia IS NOT TRUE ';
                }

                if($login_fabrica == 147 AND $estado != 'SP'){
                    $cond_estado = " AND tabela_principal = true ";
                }
                echo "<td align='left'>";
                $sql  = "SELECT * FROM tbl_tabela WHERE fabrica = $login_fabrica AND ativa {$condfat} {$cond_estado} ORDER BY sigla_tabela";
                $resX = pg_query ($con,$sql);

                echo "<select class='frm' name='tabela_posto_$linha' style='width: 170px;'>\n";
                echo "<option selected></option>\n";

                for ($x = 0; $x < pg_num_rows($resX); $x++) {

                    //HD 677353 - Para a Delonghi aparecer apenas estas tabelas quando for faturado
                    $delonghi = ($login_fabrica == 101 && in_array(pg_fetch_result($resX, $x, 'tabela'), array(576,577)));

                    if ($delonghi || in_array($login_fabrica, array(30,40,74,98,104,105,115,116,121,122,123,124,125,128,129,131,136,140)) || isset($novaTelaOs) || isset($tabelaPrecoUnica)) {
                        $check = "";
                        if ($tabela_posto == pg_fetch_result($resX, $x, 'tabela')) $check = " selected ";
                        echo "<option value='".pg_fetch_result($resX, $x, 'tabela')."' $check>".pg_fetch_result($resX, $x, 'sigla_tabela')." - ". pg_fetch_result($resX, $x, 'descricao')."</option>";

                    }

                }

                echo "</select>\n";
                echo "</td>";

                if(in_array($login_fabrica,array(138))){
                    echo "<td align='left'>";
                    $sql  = "SELECT * FROM tbl_tabela WHERE fabrica = $login_fabrica $condfat AND ativa ORDER BY sigla_tabela";
                    $resX = pg_query ($con,$sql);
                    echo "<select class='frm' name='tabela_bonificacao_$linha' style='width: 170px;'>\n";
                    echo "<option selected></option>\n";

                    for ($x = 0; $x < pg_num_rows($resX); $x++) {
                        $check = "";
                        if ($tabela_bonificacao == pg_fetch_result($resX, $x, 'tabela')) $check = " selected ";
                        echo "<option value='".pg_fetch_result($resX, $x, 'tabela')."' $check>".pg_fetch_result($resX, $x, 'sigla_tabela')." - ". pg_fetch_result($resX, $x, 'descricao')."</option>";
                    }
                    echo "</select>\n";
                    echo "</td>";
                }

            }else if ($login_fabrica == 104) {
                //HD 845757?>
                <td align="left">
                <?php
                //seleciona a tabela que o posto hoje está cadastrado para mostrar na frente. mesmo que a tabela esteja inativa... deverá mostrar
				if(!empty($posto)) {
					$sql_tabela_posto1 = "SELECT tbl_tabela.*
						FROM tbl_tabela
						JOIN tbl_posto_linha ON tbl_tabela.tabela     = tbl_posto_linha.tabela_posto
						AND tbl_posto_linha.posto = $posto
						AND tbl_posto_linha.linha = $linha
						WHERE fabrica = $login_fabrica
						UNION
						SELECT *
						FROM tbl_tabela
						WHERE fabrica = $login_fabrica
						AND ativa
						/*AND tabela_garantia*/
						AND SUBSTR(sigla_tabela, 1, 2) = '$estado'
						ORDER BY sigla_tabela";

						$res_tabela_posto_1 = pg_query($con,$sql_tabela_posto1);
						if (pg_num_rows($res_tabela_posto_1)>0) {

							$tabelas_posto = pg_fetch_all($res_tabela_posto_1);

							if ($debug == 2)
								echo array2table($tabelas_posto, count($tabelas_posto) . " Tabelas para a linha $linha, Tabela selecionada: $tabela_posto");

							foreach ($tabelas_posto as $item_tabelas_posto) {
								$combo_tabelas[$item_tabelas_posto['tabela']] = $item_tabelas_posto['sigla_tabela'] . ' - ' .
									strtoupper(trim($item_tabelas_posto['descricao']));
							}

							echo array2select("tabela_posto_$linha", null, $combo_tabelas, $tabela_posto, " class='frm'", ' ', true);
							unset($tabela_posto, $tabelas_posto, $combo_tabelas);

						}
				}
                echo "</td>";

                //HD-3022970
                $sql_tabela_posto2 = "SELECT tbl_tabela.*
                                        FROM tbl_tabela
                                        JOIN tbl_posto_linha ON tbl_tabela.tabela     = tbl_posto_linha.tabela_posto
                                                            AND tbl_posto_linha.posto = $posto
                                                            AND tbl_posto_linha.linha = $linha
                                       WHERE fabrica = $login_fabrica
                                       AND tbl_tabela.sigla_tabela ILIKE '%ACESS%'
                                UNION
                                      SELECT *
                                        FROM tbl_tabela
                                       WHERE fabrica = $login_fabrica
                                         AND ativa
                                         /*AND tabela_garantia*/
                                         AND SUBSTR(sigla_tabela, 1, 2) = '$estado'
                                         AND tbl_tabela.sigla_tabela ILIKE '%ACESS%'
                                    ORDER BY sigla_tabela";

                $res_tabela_posto_2 = pg_query($con,$sql_tabela_posto2);

                echo "<td align='left'>";
                if (pg_num_rows($res_tabela_posto_2)>0) {

                    $tabelas_bonificacao = pg_fetch_all($res_tabela_posto_2);

                    if ($debug == 2)
                        echo array2table($tabelas_bonificacao, count($tabelas_bonificacao) . " Tabelas para a linha $linha, Tabela selecionada: $tabela_posto");

                    foreach ($tabelas_bonificacao as $item_tabelas_posto_acess) {
                        $combo_tabelas[$item_tabelas_posto_acess['tabela']] = $item_tabelas_posto_acess['sigla_tabela'] . ' - ' .
                                                                        strtoupper(trim($item_tabelas_posto_acess['descricao']));
                    }

                    echo array2select("tabela_bonificacao_$linha", null, $combo_tabelas, $tabela_bonificacao, " class='frm'", ' ', true);
                    unset($tabelas_bonificacao, $tabela_bonificacao, $combo_tabelas);

                }
                echo "</td>";
                // FIM //HD-3022970

            }
            if($login_fabrica == 74){
                echo "<td align='left'>";
                $sql  = "SELECT * FROM tbl_tabela WHERE fabrica = $login_fabrica AND ativa ORDER BY sigla_tabela";
                $resX = pg_query ($con,$sql);
                echo "<select class='frm' name='tabela_bonificacao_$linha' style='width: 170px;'>\n";
                echo "<option selected></option>\n";

                for ($x = 0; $x < pg_num_rows($resX); $x++) {
                    $check = "";
                    if ($tabela_bonificacao == pg_fetch_result($resX, $x, 'tabela')) $check = " selected ";
                    echo "<option value='".pg_fetch_result($resX, $x, 'tabela')."' $check>".pg_fetch_result($resX, $x, 'sigla_tabela')." - ". pg_fetch_result($resX, $x, 'descricao')."</option>";
                }
                echo "</select>\n";
                echo "</td>";
            }

            if (!in_array($login_fabrica, array(94,98,101,117))  AND $login_fabrica < 120) {//HD 677353
                echo "<td align='center'><input class='frm' type='text' size='3' maxlength='2' name='desconto_$linha' value='$desconto'>%</td>";
            }

            if (in_array($login_fabrica, array(172))) {
                echo "<td align='center'><input class='frm' type='text' size='3' maxlength='2' name='desconto_$linha' value='$desconto'>%</td>";
            }

            if (!in_array($login_fabrica, array(74,86,94,101,104,115,116,117))  AND $login_fabrica < 120 || in_array($login_fabrica, array(139, 172))) {//HD 677353

                echo "<td align='left'>";

                $sql = "SELECT  tbl_posto.posto   ,
                                tbl_posto.nome_fantasia,
                                tbl_posto.nome
                        FROM    tbl_posto
                        JOIN    tbl_posto_fabrica    ON tbl_posto.posto           = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
                        JOIN    tbl_tipo_posto       ON tbl_tipo_posto.tipo_posto = tbl_posto_fabrica.tipo_posto
                        WHERE   tbl_posto_fabrica.fabrica   = $login_fabrica
                        AND     tbl_tipo_posto.distribuidor is true
                        ORDER BY tbl_posto.nome_fantasia";

                $resX = pg_query($con,$sql);

                echo "<select class='frm' name='distribuidor_$linha' style='width: 170px;'>\n";
                echo "<option ></option>\n";

                for ($x = 0; $x < pg_num_rows($resX); $x++) {
                    $check = "";
                    if ($distribuidor == pg_fetch_result($resX,$x,posto)) $check = " selected ";
                    $fantasia = pg_fetch_result ($resX,$x,nome_fantasia) ;
                    if (strlen (trim ($fantasia)) == 0) $fantasia = pg_fetch_result ($resX,$x,nome) ;
                    echo "<option value='".pg_fetch_result($resX,$x,posto)."' $check>$fantasia</option>";
                }

                echo "</select>\n";
                echo "</td>";

                if ($login_fabrica == 24){ #HD 383050
                    echo "<td>";
                        echo "<input type='checkbox' $check_divulga value='t' name='divulga_consumidor_linha_$linha' id='divulga_consumidor_linha'  >";
                    echo "</td>";
                }

            }
            echo "</tr>";
        }
    } else if (in_array($login_fabrica, array(117))) {
        $sqlMacroLinha = "SELECT distinct   tbl_macro_linha.descricao,
                                            tbl_macro_linha.macro_linha
                                    FROM tbl_macro_linha
                                        JOIN tbl_macro_linha_fabrica ON tbl_macro_linha.macro_linha = tbl_macro_linha_fabrica.macro_linha
                                    WHERE tbl_macro_linha.ativo IS TRUE
                                        AND fabrica = {$login_fabrica}
                                    ORDER BY tbl_macro_linha.descricao";
        $resMacroLinha = pg_query($con,$sqlMacroLinha);

        for ($i=0; $i < pg_num_rows($resMacroLinha); $i++) {
            $tabela = "";
            $macro_linha = pg_fetch_result($resMacroLinha, $i, 'macro_linha');
            $macro_linha_desc = pg_fetch_result($resMacroLinha, $i, 'descricao');

            $sqlLinha = "SELECT tbl_linha.linha,
                                tbl_linha.nome
                            FROM tbl_macro_linha_fabrica
                                JOIN tbl_linha USING(linha)
                            WHERE tbl_linha.fabrica = {$login_fabrica}
                                AND tbl_linha.ativo IS TRUE
                                AND tbl_macro_linha_fabrica.macro_linha = {$macro_linha}";
            $resLinha = pg_query($con,$sqlLinha);

            echo "<tr>";
            echo "<td align='left'><b>".$macro_linha_desc."</b></td>";
            echo "<td align='left'>";
            echo "<select name='linhas_".$macro_linha."[]' id='linhas_$macro_linha' style='width:180px;' multiple='multiple' >";
            for ($j=0; $j < pg_num_rows($resLinha); $j++) {
                $linha_id = pg_fetch_result($resLinha, $j, 'linha');
                $linha_desc = pg_fetch_result($resLinha, $j, 'nome');

                $sql_s = "SELECT  tbl_posto_linha.tabela              ,
                            tbl_posto_linha.desconto            ,
                            tbl_posto_linha.distribuidor        ,
                            tbl_posto_linha.tabela_posto        ,
                            tbl_posto_linha.tabela_bonificacao  ,
                            tbl_posto_linha.auditar_os          ,
                            tbl_posto_linha.divulgar_consumidor
                    FROM    tbl_posto_linha
                    WHERE   posto = $posto
                    AND     linha = $linha_id";
                $res_s = pg_query($con,$sql_s);

                if (pg_num_rows($res_s) > 0) {
                    $check = " selected ";
                    $tabela                     = pg_fetch_result($res_s, 0, 'tabela');
                    $desconto                   = pg_fetch_result($res_s, 0, 'desconto');
                    $distribuidor               = pg_fetch_result($res_s, 0, 'distribuidor');
                    $tabela_posto               = pg_fetch_result($res_s, 0, 'tabela_posto');
                    $tabela_bonificacao         = pg_fetch_result($res_s, 0, 'tabela_bonificacao');
                    $auditar_os                 = pg_fetch_result($res_s, 0, 'auditar_os');
                    $divulga_consumidor_linha   = pg_fetch_result($res_s, 0, 'divulgar_consumidor');
                }else{
                    $tabela_posto = "";
                    $check = "";
                }
                if (strlen ($msg_erro) > 0) {
                    $macro_linhas = $_POST['linhas_' . $macro_linha];
                    $check = "";
                    if ( in_array($linha_id, $macro_linhas) ) {
                        $check = " selected ";
                    }
                }
                echo "<option value='{$linha_id}' $check>{$linha_desc}</option>";
            }
            echo "</select>";
            echo "</td>";
            if (strlen ($msg_erro) > 0) {
                $atende             = $_POST ['atende_'             . $macro_linha];
                $auditar_os         = $_POST ['auditar_os_'         . $macro_linha];
                $tabela             = $_POST ['tabela_'             . $macro_linha];
                if ($login_fabrica >= 175){
                    $tabela_posto       = $_POST ['tabela_posto'];
                }else{
                    $tabela_posto       = $_POST ['tabela_posto_'       . $macro_linha];
                }
                $tabela_bonificacao = $_POST ['tabela_bonificacao'  . $macro_linha];
                $desconto           = $_POST ['desconto_'           . $macro_linha];
                $distribuidor       = $_POST ['distribuidor_'       . $macro_linha];
            }
            //Fim atende
            //atende
            echo "<td align='left'>";
                    $sql  = "SELECT tbl_tabela.tabela       ,
                                    tbl_tabela.descricao    ,
                                    tbl_tabela.sigla_tabela
                                FROM    tbl_tabela
                                WHERE   fabrica = $login_fabrica
                                    AND ativa IS TRUE
                                ORDER BY      sigla_tabela";
                    $resX = pg_query($con,$sql);
                    ?>
                    <select class='frm' name='tabela_<?=$macro_linha?>' style='width: 170px'>
                        <option selected></option>
                    <?
                    for ($x = 0; $x < pg_num_rows($resX); $x++) {

                        $q_tabela = pg_fetch_result($resX, $x, 'tabela');
                        $q_descricao = pg_fetch_result($resX, $x, 'descricao');
                        $q_sigla = pg_fetch_result($resX, $x, 'sigla_tabela');

                        $check = "";
                        if ($tabela == $q_tabela) $check = " selected ";
                        ?>
                            <option value='<?=$q_tabela?>' <?=$check?>><?=$q_sigla." - ".$q_descricao?></option>
                        <?
                        }
                    }
                    //fim atende
                 ?>
                </select>
                </td>
            </tr>
<?php
    } else {

        $sql = "SELECT  tbl_familia.familia,
                        tbl_familia.descricao
                FROM    tbl_familia
                WHERE   tbl_familia.fabrica = $login_fabrica
                ORDER BY tbl_familia.descricao;";

        $res = pg_query ($con,$sql);

        for ($i = 0; $i < pg_num_rows($res); $i++) {
            $familia       = pg_fetch_result($res,$i,familia);
            $check         = "";
            $tabela        = "" ;
            $desconto      = "";
            $distribuidor  = "";

            $sql = "SELECT * FROM tbl_posto_linha WHERE posto = $posto AND familia = $familia";
            $resX = pg_query ($con,$sql);

            if (pg_num_rows ($resX) == 1) {
                $check        = " CHECKED ";
                $tabela       = pg_fetch_result ($resX,0,'tabela');
                $desconto     = pg_fetch_result ($resX,0,'desconto');
                $distribuidor = pg_fetch_result ($resX,0,'distribuidor');
            }

            if (pg_num_rows ($resX) > 1) {
                echo "<h1> ".traduz("ERRO NAS FAMÍLIAS, AVISE TELECONTROL")." </h1>";
                exit;
            }

            if (strlen ($msg_erro) > 0) {
                $atende       = $_POST['atende_'       . $familia] ;
                $tabela       = $_POST['tabela_'       . $familia] ;
                $desconto     = $_POST['desconto_'     . $familia] ;
                $distribuidor = $_POST['distribuidor_' . $familia] ;
                if (strlen ($atende) > 0) $check = " CHECKED ";
            }

            echo "<tr>";

            echo "<td nowrap>" . pg_fetch_result ($res, $i, 'descricao') . "</td>";
            echo "<td align='center'><input type='checkbox' name='atende_$familia' value='$familia' $check></td>";

            echo "<td align='left'>";

            $sql  = "SELECT * FROM tbl_tabela WHERE fabrica = $login_fabrica AND ativa ORDER BY sigla_tabela";
            $resX = pg_query ($con,$sql);

            echo "<select class='frm' name='tabela_$familia'>\n";
            echo "<option selected></option>\n";

            for ($x = 0; $x < pg_num_rows($resX); $x++) {

                $check = "";
                if ($tabela == pg_fetch_result($resX, $x, 'tabela')) $check = " selected ";
                echo "<option value='".pg_fetch_result($resX, $x, 'tabela')."' $check>".pg_fetch_result($resX, $x, 'sigla_tabela')." - ". pg_fetch_result($resX, $x, 'descricao')."</option>";

            }

            echo "</select>\n";
            echo "</td>";
            echo "<td align='center'><input class='frm' type='text' size='3' maxlength='2' name='desconto_$linha' value='$desconto'>%</td>";
            echo "<td align='left'>";

            $sql = "SELECT  tbl_posto.posto   ,
                            tbl_posto.nome_fantasia,
                            tbl_posto.nome
                    FROM    tbl_posto
                    JOIN    tbl_posto_fabrica    ON tbl_posto.posto           = tbl_posto_fabrica.posto
                                                AND tbl_posto_fabrica.fabrica = $login_fabrica
                    JOIN    tbl_tipo_posto       ON tbl_tipo_posto.tipo_posto = tbl_posto_fabrica.tipo_posto
                    WHERE   tbl_posto_fabrica.fabrica   = $login_fabrica
                    AND     tbl_posto_fabrica.posto    <> 7214
                    AND     tbl_tipo_posto.distribuidor is true
                    ORDER BY tbl_posto.nome_fantasia";

            $resX = pg_query ($con,$sql);

            echo "<select class='frm' name='distribuidor_$familia' disabled>";
            echo "<option > </option>\n";

            for ($x = 0; $x < pg_num_rows($resX); $x++) {

                $check = "";
                if ($distribuidor == pg_fetch_result($resX, $x, 'posto')) $check = " selected ";

                $fantasia = pg_fetch_result ($resX, $x, 'nome_fantasia') ;

                if (strlen(trim($fantasia)) == 0) $fantasia = pg_fetch_result ($resX, $x, 'nome');

                echo "<option value='".pg_fetch_result($resX,$x,posto)."' $check>$fantasia</option>";

            }

            echo "</select>\n";
            echo "</td>";

            echo "</tr>";
        }
    }
}
?>
    </td>
    </tr>
    <?php
    if($login_fabrica == 1){
        if (strlen($posto) > 0) {
             $sql_p_adicionais = "SELECT parametros_adicionais FROM tbl_posto_fabrica WHERE posto = {$posto} AND fabrica = {$login_fabrica}";
            $res_p_adicionais = pg_query($con, $sql_p_adicionais);
            if (pg_num_rows($res_p_adicionais) > 0) {
                $parametrosAdicionais = json_decode(pg_fetch_result($res_p_adicionais, 0, "parametros_adicionais"), true);
                extract($parametrosAdicionais);

                $obs_posto_cadastrado = utf8_decode($obs_posto_cadastrado);
            }
        }

    ?>
        <tr>
            <td><?=traduz('Observação')?>:</td>
        </tr>
        <tr>
            <td colspan="12">
                <textarea style="width:645px;" name='obs_posto_cadastrado'><?=$obs_posto_cadastrado?></textarea>
            </td>
        </tr>
    <?php } ?>
    </table>
</td>
</tr>
</table>
<?php } ?>

<?php if ($login_fabrica >= 175 and !in_array($login_fabrica, [180,181,182,183])){ 
    if($login_fabrica == 175){
       $condProdutoAtivo = " JOIN tbl_produto ON tbl_linha.linha = tbl_produto.linha AND tbl_produto.ativo IS TRUE";
    }
    
    $sql_linhas_produto = "SELECT  tbl_linha.linha,
                    tbl_linha.nome
	    FROM    tbl_linha
	    $condProdutoAtivo
            WHERE   tbl_linha.ativo IS TRUE
            AND     tbl_linha.fabrica = $login_fabrica
            ORDER BY codigo_linha ";
    $res_linhas_produto = pg_query ($con,$sql_linhas_produto);
    if (pg_num_rows($res_linhas_produto) > 0){
        $linha_posto = array();
        for ($i=0; $i < pg_num_rows($res_linhas_produto); $i++) { 
            $linha = pg_fetch_result($res_linhas_produto, $i, 'linha');
            $nome = pg_fetch_result($res_linhas_produto, $i, 'nome');

			if(!empty($posto)) {
				$sql = "
					SELECT  
						tbl_posto_linha.tabela              ,
						tbl_posto_linha.desconto            ,
						tbl_posto_linha.distribuidor        ,
						tbl_posto_linha.tabela_posto        ,
						tbl_posto_linha.tabela_bonificacao  ,
						tbl_posto_linha.auditar_os          ,
						tbl_posto_linha.divulgar_consumidor ,
						tbl_posto_linha.linha AS linha_posto
					FROM tbl_posto_linha
					WHERE posto = $posto
					AND linha = $linha ";
				$resX = pg_query($con, $sql);

			   if (pg_num_rows($resX) == 1) {
					$check                      = " CHECKED ";
					$tabela                     = pg_fetch_result($resX, 0, 'tabela');
					$desconto                   = pg_fetch_result($resX, 0, 'desconto');
					$distribuidor               = pg_fetch_result($resX, 0, 'distribuidor');
					$tabela_posto               = pg_fetch_result($resX, 0, 'tabela_posto');
					$tabela_bonificacao         = pg_fetch_result($resX, 0, 'tabela_bonificacao');
					$auditar_os                 = pg_fetch_result($resX, 0, 'auditar_os');
					$divulga_consumidor_linha   = pg_fetch_result($resX, 0, 'divulgar_consumidor');
					$linha_posto[]              = pg_fetch_result($resX, 0, 'linha_posto');

			   }
			}
        }
    }
?>
<table class="formulario" style="margin: 0 auto; width: 700px; border: 0;" cellpadding="1" cellspacing="3">
    <tr>
        <td class='titulo_tabela'>
            <?=(in_array($login_fabrica, [175])) ? traduz('Produtos') : traduz('Linhas')?>
        </td>
    </tr>
    <tr>
        <td>
            <select id='custom-headers' class='searchable' name='linhas_produto[]' multiple='multiple'>
                <?php
                    if (pg_num_rows($res_linhas_produto) > 0){

                        if (strlen($msg_erro) > 0 AND !empty($_POST['linhas_produto'])){
                            $linha_posto = array_merge($linha_posto, $_POST['linhas_produto']);
                        }

                        for ($i=0; $i < pg_num_rows($res_linhas_produto); $i++) { 
                            $linha = pg_fetch_result($res_linhas_produto, $i, 'linha');
                            $nome = pg_fetch_result($res_linhas_produto, $i, 'nome');

                            if (in_array($linha, $linha_posto)){
                                $selectedLinha = "selected";
                            }else{
                                $selectedLinha = "";
                            }
                    ?>
                            <option value='<?=$linha?>' <?=$selectedLinha?> ><?=$nome?></option>
                    <?php
                        }
                    }
                ?>
            </select>
        </td>
    </tr>
</table>
<?php } ?>
<br />
<?php //HD-808142
if($login_fabrica == 52){
    if (strlen($posto) > 0){
        $sqlTabelaServico = "select tabela_mao_obra from tbl_posto_fabrica where posto = ".$posto." and fabrica = ".$login_fabrica.";";
        $res = pg_query($con,$sqlTabelaServico);
        $servicoMo = trim(pg_fetch_result($res,0,tabela_mao_obra));
    }
?>
<!--   Tabela de serviços  -->
<table class="formulario" style="margin: 0 auto; width: 700px; border: 0;"cellpadding="1" cellspacing="3">
    <tr>
        <td colspan="4" class='titulo_tabela'>
            <?=traduz('Tabela de serviços')?>
        </td>
    </tr>
    <tr>
        <td style="height:70px;">
                <select class="frm" name="tabela_servico" style="width:350px;">
                    <?php

                    //HD-808142

                    $sql = "SELECT
                                tabela_mao_obra,
                                sigla_tabela,
                                descricao
                            FROM tbl_tabela_mao_obra
                            WHERE tbl_tabela_mao_obra.fabrica = ".$login_fabrica." AND tbl_tabela_mao_obra.ativo is TRUE
                            ORDER BY tbl_tabela_mao_obra.sigla_tabela;";

                    $res = pg_query($con,$sql);

                    if (pg_num_rows($res) > 0) {

                            echo "<option value=''  selected='selected' >Selecione</option>";

                            for ($x = 0 ; $x < pg_num_rows($res) ; $x++){
                                $tabela_mao_obra = trim(pg_fetch_result($res,$x,tabela_mao_obra));
                                $sigla_tabela  = trim(pg_fetch_result($res,$x,sigla_tabela));
                                $descricao  = trim(pg_fetch_result($res,$x,descricao));
                                $selected = ($tabela_mao_obra == $servicoMo) ? " selected='selected' " : "" ;

                                echo  "<option value='".$tabela_mao_obra."' ".$selected.">".$sigla_tabela." - ".$descricao."</option>";
                            }

                    }

                    ?>
                </select>
            </td>
    </tr>
</table>
<?php } ?>
    <?php
        if ((strlen($obTabelaLoja->_loja) > 0 && in_array($login_fabrica, [15,198])) ||  in_array($login_fabrica,  array(3,91, 157))) {

    ?>
    <table class='formulario linha_cidades' style='margin: 0 auto; width: 700px; border: 0;' cellpadding='1' cellspacing='3' >
        <tr>
            <td class='titulo_tabela'><?=traduz('Tabela de Preço - Loja Virtual')?></td>
        </tr>
        <tr>
            <td>
                <?=traduz('Tabela de Preço')?><br />
                <select id="loja_b2b_tabela" name="loja_b2b_tabela" >
                    <option value=""><?=traduz('Escolha uma Tabela')?> ...</option>
                    <?php
                        foreach ($obTabelaLoja->get() as $rows) {
                            $tabelaCliente  = $obTabelaLoja->getTabelaByCliente(null, $posto);
                            $selectCliente  = ($rows["loja_b2b_tabela"] == $tabelaCliente["loja_b2b_tabela"]) ? "selected" : "";
                            echo "<option {$selectCliente} value='".$rows["loja_b2b_tabela"]."' >".$rows["descricao"]."</option>";
                        }
                    ?>
                </select>
            </td>
        </tr>
    </table><br />
    <?php }?>
<!-- Esmaltec Cidades  atendidas +=====================-->

<?php
if(in_array($login_fabrica,array(15,30,52,91,120,201,85,74,117)) && strlen($_GET["posto"]) > 0) {
    $ArrayEstados = array('AC','AL','AM','AP','BA','CE','DF','ES','GO','MA','MG','MS','MT','PA','PB','PE','PI','PR','RJ','RN','RO','RR','RS','SC','SE','SP','TO');
?>
    <script src="js/jquery.alphanumeric.js"></script>
    <script>
        $(function () {
            $(".km").numeric();

            var login_fabrica = "<?=$login_fabrica?>";

            $("#estado_cadastra").change(function () {
                if ($(this).val().length > 0) {
                    $("#cidade_cadastra").removeAttr("readonly");
                } else {
                    $("#cidade_cadastra").attr({ readonly: "readonly" });
                }
            });

            var extraParamEstado = {
                estado: function () {
                    return $("#estado_cadastra").val()
                }
            };

            $("#cidade_cadastra").autocomplete("autocomplete_cidade_new.php", {
                minChars: 3,
                max: 50,
                delay: 150,
                width: 350,
                matchContains: true,
                extraParams: extraParamEstado,
                formatItem: function (row) { return row[0]; },
                formatResult: function (row) { return row[0]; }
            });

            $("#cidade").result(function(event, data, formatted) {
                $("#cidade").val(data[0].toUpperCase());
            });

            $("#adicionar_cidade").click(function () {
                var estado = $("#estado_cadastra").val();
                var cidade = $.trim($("#cidade_cadastra").val());

                if (login_fabrica != 74 && login_fabrica != 117) {
                    var tipo = $("#tipo_cadastra").val();
                }

                if (login_fabrica == 52) {
                    var km = $("#km_cadastra").val();
                }

                var campo_erro = [];

                if (estado.length == 0) {
                    campo_erro.push("estado");
                }

                /*if (cidade.length == 0) {
                    campo_erro.push("cidade");
                }*/

                if ((login_fabrica != 74 && login_fabrica != 91 && login_fabrica != 117) && tipo.length == 0) {
                    campo_erro.push("tipo");
                }

                if (campo_erro.length == 0) {
                    if (login_fabrica == 52) {
                        var data_ajax = { adicionar_cidade: true, posto: "<?=$posto?>", estado: estado, cidade: cidade, tipo: tipo, km: km };
                    } else if (login_fabrica == 74 || login_fabrica == 91 || login_fabrica == 117) {
                        var data_ajax = { adicionar_cidade: true, posto: "<?=$posto?>", estado: estado, cidade: cidade };
                    } else {
                        var data_ajax = { adicionar_cidade: true, posto: "<?=$posto?>", estado: estado, cidade: cidade, tipo: tipo };
                    }

                    $.ajax({
                        url: "cadastra_cidade_atendida.php",
                        type: "POST",
                        data: data_ajax,
                        beforeSend: function () {
                            $("#adicionar_cidade").hide();
                            $("#loading_adicionar_cidade").show();
                        },
                        complete: function (data) {
                            data = $.parseJSON(data.responseText);

                            if (data.erro) {
                                alert(data.erro);
                            } else {
                                if (login_fabrica == 52) {
                                    var tr_cidade = $("<tr></tr>");

                                    var button_cidade_excluir = $("<button></button>", {
                                        type: "button",
                                        name: "excluir_cidade",
                                        rel: data.id,
                                        text: "Excluir Cidade"
                                    });

                                    var button_cidade_salvar = $("<button></button>", {
                                        type: "button",
                                        name: "salvar_cidade",
                                        rel: data.id,
                                        text: "Salvar Alteração"
                                    });

                                    var cidade_nome = data.cidade + " - " + data.estado;

                                    $(tr_cidade).append($("<td></td>", { valign: "top", css: { "border-bottom": "1px solid #000" } }).html($("<input />", { type: "text", readonly: "readonly", value: cidade_nome })));
                                    $(tr_cidade).append($("<td></td>", { valign: "top", css: { "border-bottom": "1px solid #000" } }).html($("<input />", { type: "text", readonly: "readonly", value: data.tipo })));
                                    $(tr_cidade).append($("<td></td>", { valign: "top", css: { "border-bottom": "1px solid #000" } }).html($("<input />", { type: "text", css: { width: "50px" }, class: "km", value: data.km })));
                                    $(tr_cidade).append($("<td></td>", { valign: "top", css: { "border-bottom": "1px solid #000" } }).append(button_cidade_salvar).append(button_cidade_excluir));

                                    $("#cidades_atendidas_cadastradas").append(tr_cidade);

                                    $(".km").numeric();
                                } else if (login_fabrica == 74 || login_fabrica == 91 || login_fabrica == 117) {
                                    var tr_cidade = $("<tr></tr>");

                                    var button_cidade_excluir = $("<button></button>", {
                                        type: "button",
                                        name: "excluir_cidade",
                                        rel: data.id,
                                        text: "Excluir Cidade"
                                    });

                                    var span = $("<span></span>");

                                    var input_bairro = $("<input />", {
                                        name: "bairro_cadastra",
                                        type: "text"
                                    });

                                    var button_bairro_adicionar = $("<button></button>", {
                                        type: "button",
                                        name: "adicionar_bairro",
                                        rel: data.id,
                                        text: "Adicionar Bairro"
                                    });

                                    $(span).text("Bairros");
                                    $(span).append(input_bairro);
                                    $(span).append(button_bairro_adicionar);

                                    var cidade_nome = data.cidade + " - " + data.estado;

                                    $(tr_cidade).append($("<td></td>", { valign: "top", css: { "border-bottom": "1px solid #000" } }).html($("<input />", { type: "text", readonly: "readonly", value: cidade_nome })));
                                    $(tr_cidade).append($("<td></td>", { valign: "top", css: { "border-bottom": "1px solid #000" }, name: "bairros" }).html(span));
                                    $(tr_cidade).append($("<td></td>", { valign: "top", css: { "border-bottom": "1px solid #000" } }).html(button_cidade_excluir));

                                    $("#cidades_atendidas_cadastradas").append(tr_cidade);
                                } else {
                                    var tr_cidade = $("<tr></tr>");

                                    var button_cidade_excluir = $("<button></button>", {
                                        type: "button",
                                        name: "excluir_cidade",
                                        rel: data.id,
                                        text: "Excluir Cidade"
                                    });

                                    var cidade_nome = data.cidade + " - " + data.estado;

                                    $(tr_cidade).append($("<td></td>", { valign: "top", css: { "border-bottom": "1px solid #000" } }).html($("<input />", { type: "text", readonly: "readonly", value: cidade_nome })));
                                    $(tr_cidade).append($("<td></td>", { valign: "top", css: { "border-bottom": "1px solid #000" } }).html($("<input />", { type: "text", readonly: "readonly", value: data.tipo })));
                                    $(tr_cidade).append($("<td></td>", { valign: "top", css: { "border-bottom": "1px solid #000" } }).html(button_cidade_excluir));

                                    $("#cidades_atendidas_cadastradas").append(tr_cidade);
                                }
                            }

                            $("#adicionar_cidade").show();
                            $("#loading_adicionar_cidade").hide();
                        }
                    });
                } else {
                    alert("Os seguintes campos são obrigatórios para adicionar uma nova cidade atendida: "+campo_erro.join(", "));
                }
            });

            $(document).on("click", "button[name=adicionar_bairro]", function () {
                var id     = $(this).attr("rel");
                var bairro = $.trim($(this).prev().val());
                var td_bairro = $(this).parents("td");

                if (bairro.length > 0) {
                    $.ajax({
                        url: "cadastra_cidade_atendida.php",
                        type: "POST",
                        data: { adicionar_bairro: true, posto: "<?=$posto?>", id: id, bairro: bairro },
                        complete: function (data) {
                            data = $.parseJSON(data.responseText);

                            if (data.erro) {
                                alert(data.erro);
                            } else {
                                var div = $("<div></div>");

                                var bairro_nome = $("<input />", {
                                    type: "text",
                                    readonly: "readonly",
                                    value: bairro
                                });

                                var button_bairro_excluir = $("<button></button>", {
                                    rel: id,
                                    type: "button",
                                    name: "excluir_bairro",
                                    text: "Excluir Bairro"
                                });

                                $(div).append(bairro_nome);
                                $(div).append(button_bairro_excluir);

                                $(td_bairro).append(div);
                            }
                        }
                    });
                } else {
                    alert("Digite um bairro");
                }
            });

            $(document).on("click", "button[name=excluir_cidade]", function () {
                if (confirm("Deseja realmente excluir a cidade ?")) {
                    var id = $(this).attr("rel");
                    var tr = $(this).parents("tr");

                    $.ajax({
                        url: "cadastra_cidade_atendida.php",
                        type: "POST",
                        data: { excluir_cidade: true, posto: "<?=$posto?>", id: id },
                        complete: function (data) {
                            data = $.parseJSON(data.responseText);

                            if (data.erro) {
                                alert(data.erro);
                            } else {
                                alert("Cidade Excluida");

                                $(tr).remove();
                            }
                        }
                    });
                }
            });

            $(document).on("click", "button[name=excluir_bairro]", function () {
                if (confirm("Deseja realmente excluir o bairro ?")) {
                    var id     = $(this).attr("rel");
                    var bairro = $(this).prev().val();
                    var div    = $(this).parent("div");

                    $.ajax({
                        url: "cadastra_cidade_atendida.php",
                        type: "POST",
                        data: { excluir_bairro: true, posto: "<?=$posto?>", id: id, bairro: bairro },
                        complete: function (data) {
                            data = $.parseJSON(data.responseText);

                            if (data.erro) {
                                alert(data.erro);
                            } else {
                                alert("Bairro Excluido");

                                $(div).remove();
                            }
                        }
                    });
                }
            });

            $(document).on("click", "button[name=salvar_cidade]", function () {
                var id = $(this).attr("rel");
                var km = $(this).parents("tr").find("input.km").val();

                if (km == undefined || km.length == 0) {
                    km = 0;
                }

                $.ajax({
                    url: "cadastra_cidade_atendida.php",
                    type: "POST",
                    data: { salvar_cidade: true, posto: "<?=$posto?>", id: id, km: km },
                    complete: function (data) {
                        data = $.parseJSON(data.responseText);

                        if (data.erro) {
                            alert(data.erro);
                        } else {
                            alert("Alteração de KM gravada com sucesso");
                        }
                    }
                });
            });
        });
    </script>

    <table class='formulario linha_cidades' style='margin: 0 auto; width: 700px; border: 0;' cellpadding='1' cellspacing='3' >
        <tr>
            <td colspan="<?=($login_fabrica == 52) ? 5 : 4?>" class='titulo_tabela'>Cidades Atendidas</td>
        </tr>
        <tr>
            <td>
                Estado<br />
                <select id="estado_cadastra" >
                    <option value=""></option>
                    <?php
                    foreach ($ArrayEstados as $estado_cadastra) {
                        echo "<option value='{$estado_cadastra}' >{$estado_cadastra}</option>";
                    }
                    ?>
                </select>
            </td>
            <td>
                Cidade<br />
                <input type="text" id="cidade_cadastra" readonly="readonly" title="Selecione um estado para digitar a cidade" />
            </td>
            <?php
            if (!in_array($login_fabrica,array(74,91,117))) {
                $sql = "SELECT posto_fabrica_ibge_tipo, nome
                        FROM tbl_posto_fabrica_ibge_tipo
                        WHERE fabrica = {$login_fabrica}";
                $res = pg_query($con, $sql);
                $rows = pg_num_rows($res);
            ?>
                <td>
                    Tipo<br />
                    <select id="tipo_cadastra" >
                        <option value="" ></option>
                        <?php
                            if ($rows > 0) {
                                for ($i = 0; $i < $rows; $i++) {
                                    $posto_fabrica_ibge_tipo = pg_fetch_result($res, $i, "posto_fabrica_ibge_tipo");
                                    $nome = pg_fetch_result($res, $i, "nome");

                                    echo "<option value='{$posto_fabrica_ibge_tipo}' >{$nome}</option>";
                                }
                            }
                        ?>
                    </select>
                </td>
            <?php
            }

            if ($login_fabrica == 52) {
            ?>
                <td>
                    KM distância<br />
                    <input type="text" class="km" id="km_cadastra" style="width: 50px;" />
                </td>
            <?php
            }
            ?>
            <td>
                <button type="button" id="adicionar_cidade" >Adicionar</button>
                <img src="imagens/loading_img.gif" style="display: none; height: 20px; width: 20px;" id="loading_adicionar_cidade" />
            </td>
        </tr>
    </table>

    <table class='formulario linha_cidades' style='margin: 0 auto; width: 700px; border: 0; border-collapse: collapse;' cellpadding='1' cellspacing='3' >
        <tbody id="cidades_atendidas_cadastradas" >
            <tr>
                <?php
                $colspan = 3;

                if ($login_fabrica == 52) {
                    $colspan = 4;
                }
                ?>
                <td colspan="<?=$colspan?>" class='titulo_tabela'>Cidades cadastradas</td>
            </tr>
            <?php

            $inner = (in_array($login_fabrica,array(74,91,117))) ? "LEFT" : "INNER";

            $sql = "SELECT
                        tbl_posto_fabrica_ibge.posto_fabrica_ibge,
                        tbl_cidade.nome AS cidade,
                        tbl_cidade.estado,
                        tbl_posto_fabrica_ibge.posto_fabrica_ibge_tipo,
                        tbl_posto_fabrica_ibge_tipo.nome AS tipo_nome,
                        tbl_posto_fabrica_ibge.km,
                        tbl_posto_fabrica_ibge.bairro
                    FROM tbl_posto_fabrica_ibge
                    INNER JOIN tbl_cidade ON tbl_cidade.cidade = tbl_posto_fabrica_ibge.cidade
                    $inner JOIN tbl_posto_fabrica_ibge_tipo ON tbl_posto_fabrica_ibge_tipo.posto_fabrica_ibge_tipo = tbl_posto_fabrica_ibge.posto_fabrica_ibge_tipo AND tbl_posto_fabrica_ibge_tipo.fabrica = {$login_fabrica}
                    WHERE tbl_posto_fabrica_ibge.fabrica = {$login_fabrica}
                    AND tbl_posto_fabrica_ibge.posto = {$posto}";
            $res = pg_query($con, $sql);
            $rows = pg_num_rows($res);

            if ($rows > 0) {
                for ($i = 0; $i < $rows; $i++) {
                    $posto_fabrica_ibge      = pg_fetch_result($res, $i, "posto_fabrica_ibge");
                    $cidade                  = pg_fetch_result($res, $i, "cidade");
                    $atende_estado                  = pg_fetch_result($res, $i, "estado");
                    $posto_fabrica_ibge_tipo = pg_fetch_result($res, $i, "posto_fabrica_ibge_tipo");
                    $tipo_nome               = pg_fetch_result($res, $i, "tipo_nome");
                    $km                      = pg_fetch_result($res, $i, "km");
                    $bairros                 = json_decode(pg_fetch_result($res, $i, "bairro"), true);
                    ?>

                    <tr>
                        <td style='border-bottom: 1px solid #000;' valign='top' >
                            <input type='text' readonly='readonly' value='<?=$cidade?> - <?=$atende_estado?>' />
                        </td>
                        <?php
                        if (in_array($login_fabrica,array(74,91,117))) {
                        ?>
                            <td name='bairros' style='border-bottom: 1px solid #000;' valign='top' >
                                <span>
                                    Bairros
                                    <input type='text' name='bairro_cadastra' />
                                    <button type='button' name='adicionar_bairro' rel='<?=$posto_fabrica_ibge?>' >Adicionar Bairro</button>
                                </span>

                                <?php
                                if (count($bairros) > 0) {
                                    foreach ($bairros as $bairro) {
                                        if (!strlen($bairro)) {
                                            continue;
                                        }

                                        $bairro = strtoupper(retira_acentos(utf8_decode($bairro)));
                                        echo "<div><input type='text' readonly='readonly' value='{$bairro}' /> <button type='button' name='excluir_bairro' rel='{$posto_fabrica_ibge}' >Excluir Bairro</button></div>";
                                    }
                                }
                                ?>
                            </td>
                        <?php
                        }

                        if (!in_array($login_fabrica,array(74,91,117))) {
                        ?>
                            <td style='border-bottom: 1px solid #000;' valign='top' >
                                <input type='text' readonly='readonly' value='<?=$tipo_nome?>' />
                            </td>
                        <?php
                        }

                        if ($login_fabrica == 52) {
                        ?>
                            <td style='border-bottom: 1px solid #000;' valign='top' >
                                <input type='text' class="km" style="width: 50px;" value='<?=$km?>' />
                            </td>
                        <?php
                        }
                        ?>
                        <td style='border-bottom: 1px solid #000;' valign='top' >
                            <?php
                            if ($login_fabrica == 52) {
                            ?>
                                <button type='button' name='salvar_cidade' rel='<?=$posto_fabrica_ibge?>' >Salvar Alteração</button>
                            <?php
                            }
                            ?>
                            <button type='button' name='excluir_cidade' rel='<?=$posto_fabrica_ibge?>' >Excluir Cidade</button>
                        </td>
                    </tr>
                <?php
                }
            }
            ?>
        </tbody>
    </table>

<?php
}
?>

<script language='javascript'>

$(document).ready(function(){
    var currentTime = new Date().getTime();
    function formatItem(row) {
        return row[0];
    }

     $('.km_distancia').keypress(function(event) {
    var tecla = (window.event) ? event.keyCode : event.which;
        if ((tecla > 47 && tecla < 58)) return true;
        else {
        if (tecla != 8) return false;
            else return true;
        }
    });

    $("button.btnVisitaTecnica").on("click", function(){
        var roteiro_posto = this.id.replace("btnVisitaTecnica","");
        var posto         = $(this).data("posto");

        Shadowbox.open({
            content: "listagem_visita.php?posto=" + posto + "&roteiro_posto=" + roteiro_posto,
            player:  "iframe",
            title:   "Visitas",
            width:   1000,
            height:  600
        });
    });
});
</script>

<br />

<?php
if ($login_fabrica == 74 && !empty($posto)) {
?>
    <table class="formulario" style="margin: 0 auto; width: 700px;" cellpadding="3" cellspacing="2">
        <tr>
            <TR>
                <TD colspan='3' class='titulo_tabela' ><?=traduz('Bonificação')?></TD>
            </TR>
            <?php
            $sqlParametrosAdicionais = "SELECT parametros_adicionais FROM tbl_posto_fabrica WHERE posto = {$posto} AND fabrica = {$login_fabrica}";
            $resParametrosAdicionais = pg_query($con, $sqlParametrosAdicionais);

            $parametros_adicionais = pg_fetch_result($resParametrosAdicionais, 0, "parametros_adicionais");
            $parametros_adicionais = json_decode($parametros_adicionais, true);

            if (count($parametros_adicionais["bonificacoes"]) > 0) {
                foreach ($parametros_adicionais["bonificacoes"] as $i => $b) {
                    echo "
                        <TR>
                            <TD align='left'>De: {$b['de']}</TD>
                            <TD align='left'>Até: {$b['ate']}</TD>
                            <TD align='left'>Valor: ".number_format($b['valor'], 2, ",", ".")."</TD>
                        </TR>
                    ";
                }
            } else {
                echo "
                    <TR>
                        <TD align='center' colspan='3'>".traduz("Sem Bonificação")."</TD>
                    </TR>
                ";
            }
            ?>
            <TR>
                <TD colspan='3' >
                    <button type="button" onclick="window.open('cadastro_bonificacao_posto.php?posto=<?=$posto?>')" ><?=traduz('Alterar')?></button>
                </TD>
            </TR>
        </tr>
    </table>

    <br />
<?php
}
?>

<?php 
if ($login_fabrica >= 175 and !in_array($login_fabrica, [180,181,182,183])){
?>
    <table class="formulario" style="margin: 0 auto; width: 700px; border: 0;" cellpadding="1" cellspacing="3">
        <tr>
            <td class='titulo_tabela'><?=traduz('Tabela de Garantia')?></td>
        </tr>
        <tr>
            <td style="text-align: left;">
            <?php 
                $sql= "
                    SELECT
                        tbl_tabela.tabela       ,
                        tbl_tabela.descricao    ,
                        tbl_tabela.sigla_tabela
                    FROM    tbl_tabela
                    WHERE   fabrica = $login_fabrica
                    AND     ativa IS TRUE $condgar
                    ORDER BY sigla_tabela";
                $resX = pg_query($con,$sql);
            ?>
                <select class='frm' name='tabela_linha' style='width: 170px'>
                        <option value=""></option>
                    <?php
                        for ($z = 0; $z < pg_num_rows($resX); $z++) {
                            $q_tabela = pg_fetch_result($resX, $z, 'tabela');
                            $q_descricao = pg_fetch_result($resX, $z, 'descricao');
                            $q_sigla = pg_fetch_result($resX, $z, 'sigla_tabela');
                            $check = "";

                            if (!empty($posto) && empty($_POST['tabela_linha'])) {

                                $sqlTblLinhaUnica = "   SELECT tbl_posto_linha.tabela 
                                                        FROM tbl_posto_linha 
                                                        JOIN tbl_linha ON tbl_linha.fabrica = {$login_fabrica} 
                                                        AND tbl_posto_linha.linha = tbl_linha.linha
                                                        WHERE tbl_posto_linha.posto = {$posto}
                                                        AND tbl_posto_linha.tabela IS NOT NULL
                                                        LIMIT 1";
                                $resTblLinhaUnica = pg_query($con, $sqlTblLinhaUnica);
                                
                                if (pg_num_rows($resTblLinhaUnica) > 0) {

                                    $tbl_linha_atual = pg_fetch_result($resTblLinhaUnica, 0, "tabela");

                                    $check = ($tbl_linha_atual == $q_tabela) ? "selected" : "";

                                }

                            } else {
                                if ($_POST['tabela_linha'] == $q_tabela){ $check = " selected ";}
                            }

                    ?>
                        <option value='<?=$q_tabela?>' <?=$check?>><?=$q_sigla." - ".$q_descricao?></option>
                    <?php
                        }
                    ?>
                </select>
            </td>
        </tr>
    </table> 
    <br/>
    <table class="formulario" style="margin: 0 auto; width: 700px; border: 0;" cellpadding="1" cellspacing="3">
        <tr>
            <td class='titulo_tabela'><?=traduz('Tabela de Vendas')?></td>
        </tr>
        <?php
            //if (!empty($posto)){
                // echo "<tr>
                //         <td align='left'>Tabela Faturada</td>
                //     </tr>";
                if (in_array($login_fabrica, array(30,40,74,98,101,105,115,116,121,122,123,124,125,128,129,131,136,140)) || (isset($novaTelaOs) && !in_array($login_fabrica, array(152,180,181,182))) || isset($tabelaPrecoUnica)){

                    if(in_array($login_fabrica,array(40,101,115,116,121,122,123,124,125,128,131,136,140)) || (isset($novaTelaOs) && !in_array($login_fabrica, array(145,150,156,162,175)))) {
                        $condfat = ' AND tabela_garantia IS NOT TRUE ';
                    }

                    if($login_fabrica == 147 AND $estado != 'SP'){
                        $cond_estado = " AND tabela_principal = true ";
                    }
                    echo "<td align='left'>";
                    $sql  = "SELECT * FROM tbl_tabela WHERE fabrica = $login_fabrica AND ativa {$condfat} {$cond_estado} ORDER BY sigla_tabela";
                    $resX = pg_query ($con,$sql);

                    echo "<select class='frm' name='tabela_posto' style='width: 200px;'>\n";
                    echo "<option selected></option>\n";

                    for ($x = 0; $x < pg_num_rows($resX); $x++) {

                        //HD 677353 - Para a Delonghi aparecer apenas estas tabelas quando for faturado
                        $delonghi = ($login_fabrica == 101 && in_array(pg_fetch_result($resX, $x, 'tabela'), array(576,577)));

                        if ($delonghi || in_array($login_fabrica, array(30,40,74,98,104,105,115,116,121,122,123,124,125,128,129,131,136,140)) || isset($novaTelaOs) || isset($tabelaPrecoUnica)) {
                            $check = "";
                            if ($tabela_posto == pg_fetch_result($resX, $x, 'tabela')) $check = " selected ";
                            echo "<option value='".pg_fetch_result($resX, $x, 'tabela')."' $check>".pg_fetch_result($resX, $x, 'sigla_tabela')." - ". pg_fetch_result($resX, $x, 'descricao')."</option>";

                        }

                    }
                    echo "</select>\n";
                    echo "</td>";

                    if(in_array($login_fabrica,array(138))){
                        echo "<td align='left'>";
                        $sql  = "SELECT * FROM tbl_tabela WHERE fabrica = $login_fabrica $condfat AND ativa ORDER BY sigla_tabela";
                        $resX = pg_query ($con,$sql);
                        echo "<select class='frm' name='tabela_bonificacao_$linha' style='width: 170px;'>\n";
                        echo "<option selected></option>\n";

                        for ($x = 0; $x < pg_num_rows($resX); $x++) {
                            $check = "";
                            if ($tabela_bonificacao == pg_fetch_result($resX, $x, 'tabela')) $check = " selected ";
                            echo "<option value='".pg_fetch_result($resX, $x, 'tabela')."' $check>".pg_fetch_result($resX, $x, 'sigla_tabela')." - ". pg_fetch_result($resX, $x, 'descricao')."</option>";
                        }
                        echo "</select>\n";
                        echo "</td>";
                    }

                }
            //}
        ?>
    </table> 
    <br />
<?php 
}
?>

<script>
    $(function(){
        $("#entrega_tecnica").change(function() {
            var tipo_posto = $("select[name=tipo_posto]").find("option:selected").attr("rel");

            if (tipo_posto == "t") {
                if (!$("#entrega_tecnica").is(":checked")) {
                    $("#entrega_tecnica").attr("checked", "checked");
                }
            }
        });
    });
</script>

<?php if (in_array($login_fabrica, [177])) { ?>
    <script type="text/javascript">
        $(function(){
            $(".select-tag").select2({
                tags: true,
                tokenSeparators: [',']
            });  
        });
    </script>
    <table class="formulario" style="margin: 0 auto; width: 700px;" cellpadding="3" cellspacing="2">
        <TR>
            <TD colspan='2' class='titulo_tabela' ><?=traduz('Ferramentas do Posto')?></TD>
        </TR>
        <TR>
            <td align="left">
                <!-- SELECT DE FERRAMENTAS-->
                <select multiple=multiple class='select-tag' id='ferramentas_posto' name='ferramentas_posto[]' style="width: 100% !important;">
                    <?php
                        $sql_ferramentas = "SELECT
                                              pf.parametros_adicionais
                                        FROM  tbl_posto_fabrica pf
                                        WHERE pf.posto   = {$posto}
                                        AND   pf.fabrica = {$login_fabrica}";
                        $res_ferramentas = pg_query($con, $sql_ferramentas);
                        if (pg_num_rows($res_ferramentas) > 0) {
                            for ($i_ferramentas=0; $i_ferramentas<pg_num_rows($res_ferramentas); $i_ferramentas ++) {
                                $param_add         = json_decode(pg_fetch_result($res_ferramentas, $i_ferramentas, 'parametros_adicionais'), true);
                                $array_ferramentas = $param_add['ferramentas_posto'];
                                if (!empty($array_ferramentas)) {
                                    foreach ($array_ferramentas AS $ferramenta) {
                                        echo "<option value='".utf8_decode($ferramenta)."' selected=selected>".utf8_decode($ferramenta)."</option>";
                                    }
                                }
                            }
                        } else if (!empty($_POST['ferramentas_posto'])) {
                            foreach ($_POST['ferramentas_posto'] as $valFerramentas) {
                                echo "<option value='".utf8_decode($valFerramentas)."' selected=selected>".utf8_decode($valFerramentas)."</option>";
                            }
                        }
                    ?>
                </select>
            </td>
        </TR> 
    </table> <br />
    <table class="formulario" style="margin: 0 auto; width: 700px;" cellpadding="3" cellspacing="2">
        <TR>
            <TD colspan='2' class='titulo_tabela' ><?=traduz('Marcas Atendidas')?></TD>
        </TR>
        <TR>  
            <TD align='center'>
                <!-- SELECT DE MARCAR -->
                <select multiple=multiple class='select-tag' id='marcar_atendidas' name='marcar_atendidas[]' style="width: 100% !important;">
                    <?php
                        $sql_marcar = "SELECT
                                              pf.parametros_adicionais
                                        FROM  tbl_posto_fabrica pf
                                        WHERE pf.posto   = {$posto}
                                        AND   pf.fabrica = {$login_fabrica}";
                        $res_marcar = pg_query($con, $sql_marcar);
                        if (pg_num_rows($res_marcar) > 0) {
                            for ($i_marcar=0; $i_marcar<pg_num_rows($res_marcar); $i_marcar ++) {
                                $param_add         = json_decode(pg_fetch_result($res_marcar, $i_marcar, 'parametros_adicionais'), true);
                                $array_marcar = $param_add['marcar_atendidas'];
                                if (!empty($array_marcar)) {
                                    foreach ($array_marcar AS $marcar) {
                                        echo "<option value='".utf8_decode($marcar)."' selected=selected>".utf8_decode($marcar)."</option>";
                                    }
                                }
                            }
                        } else if (!empty($_POST['marcar_atendidas'])) {
                            foreach ($_POST['marcar_atendidas'] as $valMarcas) {
                                echo "<option value='".utf8_decode($valMarcas)."' selected=selected>".utf8_decode($valMarcas)."</option>";
                            }
                        }
                    ?>
                </select>
            </TD>
        </TR> 
    </table> <br />
<?php }?> 

<?php if(in_array($login_fabrica, [169,170]) and $posto > 0){ ?>
    <table class='formulario' style="margin: 0 auto; width: 700px;" cellpadding="3" cellspacing="2">
        <tr>
            <TD colspan='2' class='titulo_tabela' >Escritórios e Equipes de Vendas</TD>
        </tr>
        <tbody>
        <tr>
            <td>
                Escritório de Vendas <br>
                <select name='escritorio_venda'  class='frm' style='width: 250px; '>
                    <option value=''>Selecione um Escritório</option>
                    <?php 
                    $sql = "SELECT * FROM tbl_escritorio_venda WHERE ativo = 't' ";
                    $res = pg_query($con, $sql);

                    for($i=0; $i<pg_num_rows($res); $i++){
                        $escritorio_venda   = pg_fetch_result($res, $i, 'escritorio_venda');
                        $codigo         = pg_fetch_result($res, $i, 'codigo');
                        $descricao      = pg_fetch_result($res, $i, 'descricao');

                        if($escritorio_venda_ad == $codigo){
                            $selected = " selected ";
                        }else{
                            $selected = "  ";
                        }

                        echo "<option value='$codigo' $selected >$descricao</option>";
                    }
                    ?>


                </select>
            </td>
            <td>
                Equipe de Vendas <br>
                <select  name='equipe_venda' class='frm' style='width: 250px; '>
                    <option value=''>Selecione uma Equipe</option>
                    <?php 
                    $sql = "SELECT * FROM tbl_equipe_venda WHERE ativo = 't' ";
                    $res = pg_query($con, $sql);

                    for($i=0; $i<pg_num_rows($res); $i++){
                        $equipe_venda   = pg_fetch_result($res, $i, 'equipe_venda');
                        $codigo         = pg_fetch_result($res, $i, 'codigo');
                        $descricao      = pg_fetch_result($res, $i, 'descricao');

                        if($equipe_venda_ad == $codigo){
                            $selected = " selected ";
                        }else{
                            $selected = "  ";
                        }

                        echo "<option value='$codigo' $selected >$descricao</option>";
                    }
                    ?>
                </select>
            </td>
        </tr>
        </tbody>
    </table>
    <br>

<?php } ?>

<?php if (in_array($login_fabrica, [87])) { ?>
     <script type="text/javascript">
        $(function(){
            $(".select-tag").select2({
                tags: true,
                tokenSeparators: [',']
            });  
        });
    </script> 

    <?php 

        $sql_add = "SELECT
                          parametros_adicionais
                    FROM  tbl_posto_fabrica
                    WHERE posto   = {$posto}
                    AND   fabrica = {$login_fabrica}";
        $res_add = pg_query($con, $sql_add);
        if (pg_num_rows($res_add) > 0) {
    ?>
            <table class="formulario" style="margin: 0 auto; width: 700px;" cellpadding="3" cellspacing="2">
                <TR>
                    <TD colspan='2' class='titulo_tabela' >Fábricas Atendidas</TD>
                </TR>
                <TR>
                    <td align="left">
                        <select multiple=multiple class='select-tag' id='ferramentas_posto' name='ferramentas_posto[]' style="width: 100% !important;">
                            <?php
                                
                                for ($i_add=0; $i_add < pg_num_rows($res_add); $i_add++) {
                                    $param_add         = json_decode(pg_fetch_result($res_add, $i_add, 'parametros_adicionais'), true);
                                    $array_emp         = $param_add['empresas'];
                                    $array_emp         = explode(";", $array_emp);
                                    if (count($array_emp) > 0) {
                                        foreach ($array_emp AS $emp) {
                                            $sql_emp = "SELECT descricao FROM tbl_empresa WHERE empresa = $emp AND fabrica = $login_fabrica";
                                            $res_emp = pg_query($con, $sql_emp);
                                            if (pg_num_rows($res_emp) > 0) {
                                                $desc_emp = (mb_check_encoding(pg_fetch_result($res_emp, 0, 'descricao'), "UTF-8")) ? utf8_decode(pg_fetch_result($res_emp, 0, 'descricao')) : pg_fetch_result($res_emp, 0, 'descricao');
                                                echo "<option value='".$desc_emp."' selected=selected>".$desc_emp."</option>";
                                            }
                                        }
                                    }
                                }
                            ?>
                        </select>
                    </td>
                </TR> 
            </table> <br />
<?php 
        }
    }
?> 


<table class="formulario" style="margin: 0 auto; width: 700px;" cellpadding="3" cellspacing="2">
<TR>
    <TD colspan='2' class='titulo_tabela' ><?=traduz('Posto pode Digitar')?></TD>
</TR>
<?php
if (in_array($login_fabrica, array(141,144))) {
?>
    <TR>
        <TD align='center'><INPUT TYPE="checkbox" NAME="posto_troca" VALUE='t' <? if ($posto_troca == 't') echo ' checked ' ?>></TD>
        <TD align='left'>Posto Troca</TD>
    </TR>
<?php
}

 if(!in_array($login_fabrica,array(152,180,181,182))) 
    if (in_array($login_fabrica, [177])) { $disabledPedidoVenda = "disabled=disabled"; $opacity = "style='opacity: 0.3;'"; } {
?>
<TR>
    <TD align='center'><INPUT TYPE="checkbox" <?=$disabledPedidoVenda;?> NAME="pedido_faturado" VALUE='t' ID="pedido_faturado"
        <? 
            if($login_fabrica == 42 && ($parametros_adicionais['pedido_venda'] == 't' or ($pedido_faturado == 't' and empty($parametros_adicionais['pedido_consumo'])))){
                echo ' checked ';
            } else if ($pedido_faturado == 't' and $login_fabrica != 42) {
                echo ' checked ';
            } else {
                echo '';
            }
        ?>>
    </TD>
    <TD align='left' <?=$opacity;?>>
        <?
            if(in_array($login_fabrica, array(175,178,184,198,200))){
                echo traduz("Compra de Peças");
            } else if($login_fabrica == 42){
                echo traduz("Pedido Venda");
            } else {
                echo traduz("Pedido Faturado (Manual)");
            }        
        ?>        
    </TD>
</TR>
<?php
}
if($login_fabrica == 42){
?>
    <TR>
        <TD align='center'><INPUT TYPE="checkbox" NAME="pedido_consumo_proprio" VALUE='t' ID='pedido_consumo_proprio' <? if ($parametros_adicionais['pedido_consumo'] == 't') echo ' checked ' ?>></TD>
        <TD align='left'>Pedido Consumo Próprio</TD>
    </TR>
<?
}

if ($login_fabrica == 1) { ?>
    <TR class="faturado_locadora" style="display: none;">
        <TD align='center'><INPUT TYPE="checkbox" NAME="pedido_faturado_locadora" VALUE='t' ID='pedido_faturado_locadora' 
            <? if ($parametros_adicionais['pedido_faturado_locadora'] == 't') echo ' checked ' ?>></TD>
        <TD align='left'>Pedido Faturado Locadora (Exceção)</TD>
    </TR>   
<?php }

if($login_fabrica == 30){ ?>
     <TR>
        <TD align='center'><INPUT TYPE="checkbox" NAME="tipo_atende" VALUE='t' <? if ($tipo_atende == 't') echo ' checked ' ?>></TD>
        <TD align='left'><?=traduz('Atendimento CD')?></TD>
     </TR>

<? } ?>
<?php if ($login_fabrica == 20) : ?>

    <tr>
        <td align="center">
            <input type="checkbox" name="tela_os_nova" id="tela_os_nova" value="t" <?=($atendimento == 'n') ? 'checked' : ''?>>
        </td>
        <td align='left'>
            <label for="tela_os_nova"><?=traduz('Usa tela nova de OS e novo Upload de OS')?></label>
        </td>
    </tr>

<?php endif;

if((!in_array($login_fabrica,array(42,147,152,171,173,176,180,181,182)) AND $login_fabrica < 175) OR $login_fabrica == 183) { ?>

    <TR>
        <TD align='center'><INPUT TYPE="checkbox" NAME="pedido_em_garantia" VALUE='t' <? if ($pedido_em_garantia == 't') echo ' checked ' ?> <? if ($login_fabrica == 3) echo " disabled " ?> ></TD>
        <TD align='left'><?=traduz('Pedido em Garantia (Manual)')?></TD>
    </TR>

<? }
    if ($login_fabrica == 1) {
        if($posto){
            $sql = "SELECT visivel FROM tbl_posto_condicao WHERE condicao = 62 AND posto = $posto";
            $res = pg_query ($con,$sql);
            if (pg_num_rows($res) > 0){
                $pedido_em_garantia_finalidades_diversas = pg_fetch_result ($res,0,visivel);
            }
        }

?>
<TR>
    <TD align='center'><INPUT TYPE="checkbox" NAME="pedido_em_garantia_finalidades_diversas" VALUE='t' <? if ($pedido_em_garantia_finalidades_diversas == 't') echo ' checked ' ?> <? if ($login_fabrica == 3) echo " disabled " ?> ></TD>
    <TD align='left'><?=traduz('Pedido de Garantia ( Finalidades Diversas )')?></TD>
</TR>

<TR>
    <TD align='center'><INPUT TYPE="checkbox" NAME="coleta_peca" VALUE='t' <? if ($coleta_peca == 't') echo 'checked' ?>></TD>
    <TD align='left'><?=traduz('Coleta de Peças')?></TD>
</TR>
<TR>
    <TD align='center'><INPUT TYPE="checkbox" id="reembolso_peca_estoque" NAME="reembolso_peca_estoque" VALUE='t' <? if ($reembolso_peca_estoque == 't') echo 'checked' ?> <? if ($login_fabrica == 3) echo " disabled " ?> ></TD>
    <?php if($login_fabrica == 1){?>
        <input type="hidden" name="reembolso_peca_estoque_anterior" id="reembolso_peca_estoque_anterior" value="<?=$reembolso_peca_estoque?>">
    <?php } ?>
    <TD align='left'><?=traduz('Reembolso de Peça do Estoque ( Garantia Automática )')?></TD>
</TR>
<? } ?>
<? if (in_array($login_fabrica, array(6, 24, 81, 114))){ ?>
<TR>
    <TD align='center'><INPUT TYPE="checkbox" NAME="garantia_antecipada" VALUE='t' <? if ($garantia_antecipada == 't'){ echo ' checked ';} ?> ></TD>
    <TD align='left'><?=traduz('Pedido em Garantia Antecipada')?></TD>
</TR>
<? } ?>
<? if (in_array($login_fabrica, array(6))){ ?>
<TR>
    <TD align='center'><INPUT TYPE="checkbox" NAME="coleta_peca" VALUE='t' <? if ($coleta_peca == 't') echo 'checked' ?>></TD>
    <TD align='left'><?=traduz('Coleta de Peças')?></TD>
</TR>
<? } ?>
<TR>
    <?php
    if ($login_fabrica != 74) {
        $disabledOs = "";
        if ($login_fabrica == 1 && $categoria_posto == "Compra Peca") {
            $disabledOs = "disabled='disabled'";
            $digita_os = '';
        }
?>
    <TD align='center'><INPUT TYPE="checkbox" NAME="digita_os" ID="digita_os" VALUE='t' <? if ($digita_os == 't') echo ' checked ' ?> <?=$disabledOs?>></TD>
    <TD align='left'>
	<?php
		echo ($login_fabrica  ==  30) ? "Digita OS Revenda" : "Digita OS";
	?>
    <? }else{ ?>

        <TD align='center'><INPUT TYPE="checkbox" NAME="digita_os_fogo" VALUE='t' <? if ($adicionais['digita_os_fogo'] == 't') echo ' checked ' ?> ></TD>
        <TD align='left'><?=traduz('Digita OS Fogo')?>
    <? }
    if(($login_fabrica==11 or $login_fabrica == 172) and strlen($posto)>0){
        if($digita_os<>"t"){
            echo "<font color='red'><b>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;".traduz("Posto Bloqueado Para digitar OS.")."</b></font>";
        }
    }
    ?>
    </TD>
    <?php
    if(in_array($login_fabrica, array(3))){

        $sql_parametros_posto = "SELECT parametros_adicionais FROM tbl_posto_fabrica WHERE posto = {$posto} AND fabrica = {$login_fabrica}";
        $res_parametros_posto = pg_query($con, $sql_parametros_posto);

        $parametros_posto = json_decode(pg_fetch_result($res_parametros_posto, 0, "parametros_adicionais"), true);
    }

    if ($login_fabrica == 3) {
        $bloqueado_pagamento = (isset($parametros_posto["bloqueado_pagamento"])) ? $parametros_posto["bloqueado_pagamento"] : "";

    ?>
    <tr>
        <td align="center"><input type="checkbox" name="bloqueado_pagamento" value="t" <?php if ($bloqueado_pagamento == 't') echo "checked " ?> ></td>
        <td align='left'><?=traduz('Bloqueado para Pagamento')?></td>
    </tr>
    <?php } ?>
</TR>

<?
    /*HD - 4203773*/
    if ($login_fabrica == 35) {
            $aux_sql = "SELECT parametros_adicionais FROM tbl_posto_fabrica WHERE fabrica = $login_fabrica AND posto = $posto LIMIT 1";
            $aux_res = pg_query($con, $aux_sql);
            $aux_par_ad = (array) json_decode(pg_fetch_result($aux_res, 0, 'parametros_adicionais'));

            if (!empty($aux_par_ad["anexar_nf_os"]) && $aux_par_ad["anexar_nf_os"] == "nao") {
                $obrigado_anexar_nf = "t";
            }
        ?>
            <TR>
                <TD align='center'><INPUT TYPE="checkbox" NAME="obrigado_anexar_nf" VALUE='t' <? if ($obrigado_anexar_nf == 't') echo ' checked ' ?> ></TD>
                <TD align='left'><?=traduz('Não Obrigado a Anexar NF na OS')?></TD>
            </TR>
        <?
    }
	if(!empty($posto)) {
		$sql = "
			SELECT  tbl_posto_fabrica.contato_estado, tbl_posto_fabrica.parametros_adicionais
			FROM    tbl_posto_fabrica
			WHERE   tbl_posto_fabrica.posto     = $posto
			AND     tbl_posto_fabrica.fabrica   = $login_fabrica
			";

		$res = pg_query($con,$sql);
		$resultContatoEstado = pg_fetch_result($res,0,contato_estado);

		$json_parametros_adicionais = pg_fetch_result($res,0,parametros_adicionais);
		$array_parametros_adicionais = json_decode($json_parametros_adicionais);

		$posto_digita_os_consumidor = $array_parametros_adicionais->digita_os_consumidor;
	}
if ($login_fabrica == 30) { ?>
<tr>
    <TD align='center'>
        <INPUT TYPE="checkbox" NAME="digita_os_consumidor" VALUE='t' <? if ($posto_digita_os_consumidor == 't') echo ' checked ' ?> />
    </TD>
    <TD align='left'><?=traduz('Digita OS Consumidor')?></TD>
</tr>
<? } ?>

<?php if($login_fabrica == 74){?>
<tr>
    <TD align='center'><INPUT TYPE="checkbox" NAME="digita_os_portateis" VALUE='t' <? if ($adicionais['digita_os_portateis'] == 't') echo ' checked ' ?> ></TD>
        <TD align='left'><?=traduz('Digita OS Portáteis')?>
</tr>
<?php } ?>

<?php
    if(in_array($login_fabrica, array(1,3,10,11,15,20,24,40,42,45,46,50,59,72,80,85,88,90,91,94,96,99,101,104,115,117,121,129,131,138,141,172))) { ?>
    <TR>
        <TD align='center'><INPUT TYPE="checkbox" NAME="prestacao_servico" ID="prestacao_servico" VALUE='t' <? if ($prestacao_servico == 't') echo ' checked ' ?>  <? if ($login_fabrica == 3) echo " disabled " ?>  ></TD>
        <TD align='left'><?=traduz('Prestação de Serviço')?><br><font size='-2'>&nbsp;<?=traduz('Posto só recebe mão-de-obra. Peças são enviadas sem custo.')?></font></TD>
    </TR>
    <? if($login_fabrica != 42) { ?>
    <TR>
        <TD align='center'>
            <INPUT TYPE="checkbox" disabled NAME="pedido_via_distribuidor" VALUE='t'<?php
            if (strlen($posto) > 0) {
                if ($pedido_via_distribuidor == 't') echo ' checked '; else echo '';
                $sql = "SELECT      tbl_tipo_posto.distribuidor
                        FROM        tbl_tipo_posto
                        LEFT JOIN   tbl_posto_fabrica USING (tipo_posto)
                        WHERE       tbl_posto_fabrica.fabrica = $login_fabrica
                        AND         tbl_posto_fabrica.posto = $posto;";
                $res = pg_query ($con,$sql);

                if (@pg_fetch_result($res,0,0) == 't') echo ''; else echo 'disabled';
            }
            if ($login_fabrica == 3) echo " disabled " ?> />
        </TD>
        <TD align='left'><?=traduz('PEDIDO VIA DISTRIBUIDOR')?></TD>
    </TR><?php
    }
}

if (in_array($login_fabrica, array(3))) {
	if (!empty($_POST['chat_online'])) {
		$chat_online = ($_POST["chat_online"] == 't') ? 't' : "";
	} else {
		$chat_online = ($parametros_posto["chat_online"] == 't') ? 't' : "";
	}
?>
    <TR>  
        <TD align='center'><INPUT TYPE="checkbox" NAME="chat_online" VALUE='t' <? if ($chat_online == 't') echo ' checked ' ?> ></TD>
	<TD align='left'><?=traduz('Atendimento CHAT')?></TD>
    </TR> 
<?php
}

if ($login_fabrica == 15) {

    ?>
    <tr>
        <td align="center">
            <input type="checkbox" name="nf_obrigatorio" value="t" <? if ($nf_obrigatorio == 't') echo ' checked ' ?> ></input>
        </td>
        <td align="left">
            <?=traduz('Anexo NF obrigatório')?>
        </td>
    </tr>
    <tr>
        <td align="center">
            <input type="checkbox" class="loja_b2b" name="loja_b2b" value="t" <? if ($loja_b2b == 't') echo ' checked ' ?> ></input>
        </td>
        <td align="left">
            <?=traduz('Liberar Loja B2B')?>
        </td>
    </tr>

<?php
}

if ($login_fabrica == 91) {

    if (!empty($posto)) {
        $aux_sql = "SELECT parametros_adicionais FROM tbl_posto_fabrica WHERE fabrica = $login_fabrica AND posto = $posto";
        $aux_res = pg_query($con, $aux_sql);
        $aux_par = json_decode(pg_fetch_result($aux_res, 0, 'parametros_adicionais'), true);

        if (!empty($aux_par["informar_cpf_cnpj"])) {
            $informar_cpf_cnpj = $aux_par["informar_cpf_cnpj"];
        }
    }?>
    <tr>
        <td align="center">
            <input type="checkbox" name="informar_cpf_cnpj" value="t" <? if ($informar_cpf_cnpj == 'false') echo ' checked ' ?> ></input>
        </td>
        <td align="left">
            <?=traduz('Não Obrigado à Informar CPF / CNPJ na Abertura de O.S.')?>
        </td>
    </tr>

<?php
}

if ($login_fabrica == 1){ ?>
<TR>
    <TD align='center'>
    <?
    #HD 23738
    if ($condicao_escolhida == 'f'){
        $msg_bloqueio_condicao =" onClick='this.checked = !this.checked; alert(\"Posto já selecionou a condição de pagamento.\")' ";
    }
    if ($condicao_escolhida == 't'){
        $msg_bloqueio_condicao      = " disabled  ";
        $msg_bloqueio_condicao_desc = " <br><font size='-2'>Posto já escolheu a Condição de Pagamento</font>  ";
    }

    ?>
    <INPUT TYPE="checkbox" NAME="escolhe_condicao" VALUE='t' <? if ($escolhe_condicao == 't'){ echo ' checked ';} ?> <?=$msg_bloqueio_condicao?>>
    </TD>
    <TD align='left'><?=traduz('ESCOLHE CONDIÇÃO DE PAGAMENTO')?>
        <?
        echo $msg_bloqueio_condicao_desc;

        if ($escolhe_condicao == 't'){
            if ($condicao_escolhida == ''){
                echo "<br><font size='-2'>Posto não escolheu a condição de pagamento</b></font>";
            }else{
                $sql = "SELECT tbl_black_posto_condicao.condicao
                        FROM tbl_black_posto_condicao
                        JOIN tbl_condicao ON tbl_condicao.condicao  = tbl_black_posto_condicao.id_condicao
                        WHERE tbl_black_posto_condicao.posto = $posto
                        AND   tbl_condicao.fabrica           = $login_fabrica
                        AND   tbl_condicao.promocao          IS NOT TRUE ";
                $res = pg_query($con,$sql);
                if(pg_num_rows($res)>0){
                    $nome_condicao_escolhida = pg_fetch_result($res,0,0);
                    if ($condicao_escolhida == 'f'){
                        echo "<br><font size='-2'>".traduz("Condição de Pagamento escolhida").": <b>$nome_condicao_escolhida</b></font>";
                        echo "&nbsp;&nbsp;&nbsp;".traduz("Liberar")." ";
                        echo "<INPUT TYPE='checkbox' NAME='condicao_liberada' VALUE='t'>";
                    }else{
                        echo "<br><font size='-2'>".traduz("Condição de Pagamento escolhida").": <b>$nome_condicao_escolhida</b></font>";
                    }
                }
            }
        }
        ?>
    </TD>
</TR>
<? } ?>

<? if ($login_fabrica == 19){ ?>
<TR>
    <TD align='center'><INPUT TYPE="checkbox" NAME="atende_comgas" VALUE='t' <? if ($atende_comgas == 't'){ echo ' checked ';} ?> ></TD>
    <TD align='left'>Atend.Comgás<br><font size='-2'>&nbsp;Posto pode digitar OS Comgás.</font></TD>
</TR>
<? }
if (in_array($login_fabrica, array(169,170))) {

    if (!empty($tipo_posto)){
        $sql = "SELECT tipo_revenda FROM tbl_tipo_posto WHERE fabrica = {$login_fabrica} AND tipo_posto = {$tipo_posto};";
        $res = pg_query($con,$sql);
        $tipo_revenda = pg_fetch_result($res, 0, "tipo_revenda");

        if ($tipo_revenda == 't') { ?>
            <TR>
                <TD align='center'><input type="checkbox" name="prestacao_servico_sem_mo" value='t' <? if ($prestacao_servico_sem_mo == 't'){ echo ' checked ';} ?> ></TD>
                <TD align='left'><?=traduz('Prestação de serviço isenta de MO')?></TD>
            </TR>
        <? }
    }

    if ($tipo_revenda == "t"){
        $style_dealer = "";
    }else{
       $style_dealer ="style='display: none;'";
    }
?>
    <tr id='tr_dealer' <?=$style_dealer?> >
        <TD align='center'><INPUT TYPE="checkbox" NAME="abre_os_dealer" VALUE='t' <? if ($abre_os_dealer == 't') echo ' checked ' ?> ></TD>
        <TD align='left'><?=traduz('Abertura de OS pelo Dealer')?> </TD>
    </tr>
<?php
}
if ($login_fabrica == 20){ # HD 85632?>
<TR>
    <TD align='center'><INPUT TYPE="checkbox" NAME="prestacao_servico_sem_mo" VALUE='t' <? if ($prestacao_servico_sem_mo == 't'){ echo ' checked ';} ?> ></TD>
    <TD align='left'><?=traduz('PRESTAÇÃO DE SERVIÇO ISENTA DE MO')?>'<br><font size='-2'>&nbsp;<?=traduz('Posto só recebe valor das peças. Mão-de-obra não será cobrada.')?></font></TD>
</TR>
<? } ?>
<? if ($login_fabrica == 74){ # HD 384558?>
<TR>
    <TD align='center'><INPUT TYPE="checkbox" NAME="pedido_bonificacao" VALUE='t' <? if ($pedido_bonificacao == 't'){ echo ' checked ';} ?> ></TD>
    <TD align='left'><?=traduz('Pedido Bonificação')?></TD>
</TR>
<? } ?>

<?
if ($login_fabrica == 50 and strlen($posto) > 0) {
    $sql = "SELECT * FROM tbl_posto_fabrica_foto WHERE posto = $posto and fabrica = $login_fabrica";
    $res = pg_query($con, $sql);

    if (pg_num_rows($res) > 0) {
        $posto_fabrica_foto     = pg_fetch_result($res,0,posto_fabrica_foto);

        $caminho_foto_posto     = pg_fetch_result($res,0,foto_posto);
        $caminho_thumb_posto    = pg_fetch_result($res,0,foto_posto_thumb);
        $descricao_foto_posto   = pg_fetch_result($res,0,foto_posto_descricao);

        $caminho_foto_contato1   = pg_fetch_result($res,0,foto_contato1);
        $caminho_thumb_contato1  = pg_fetch_result($res,0,foto_contato1_thumb);
        $descricao_foto_contato1 = pg_fetch_result($res,0,foto_contato1_descricao);

        $caminho_foto_contato2   = pg_fetch_result($res,0,foto_contato2);
        $caminho_thumb_contato2  = pg_fetch_result($res,0,foto_contato2_thumb);
        $descricao_foto_contato2 = pg_fetch_result($res,0,foto_contato2_descricao);
    }

    echo "<table class='border' style='margin: 0 auto; width: 700px; border: 0;' cellpadding='1' cellspacing='3'>";
        echo "<tr>";
            echo "<td colspan='5'><img src='imagens/cab_fotosposto.gif'></td>";
        echo "</tr>";

        echo "<tr>";
            echo "<td width='216'>".traduz("Posto")."</td>";
            echo "<td width='216'>".traduz("Contato")." 1</td>";
            echo "<td width='216'>".traduz("Contato")." 2</td>";
        echo "</tr>";

        echo "<tr>";
            echo "<td>";
                if (strlen($caminho_foto_posto) > 0) {
                    $image = $caminho_foto_posto;
                    $size = getimagesize("$image");
                    $height = $size[1];
                    $width  = $size[0];
                    echo "<IMG SRC='$caminho_thumb_posto' WIDTH='100' HEIGHT='100' onclick=\"javascript:window.open('$caminho_foto_posto','Posto','status=no,scrollbars=no,width=$width,height=$height');\">";
                    echo "<BR>$descricao_foto_posto";
                    echo "<BR><a href=\"javascript: if(confirm('Deseja excluir esta foto?')) window.location = '$PHP_SELF?posto=$posto&excluir_foto=$posto_fabrica_foto&foto=foto_posto'\">";
                    echo "<img src='imagens/btn_x.gif' WIDTH='10' HEIGTH='10'><font size='1'>Excluir</font></a>";
                } else {
                    echo "<B>Selecione a imagem (jpg,gif,png):</B><BR><input class='frm' type='file' value='Procurar foto' name='foto_posto' class='multi {accept:\'jpg|gif|png\', max:1, STRING: {remove:\'Remover\',selected:\'Selecionado: $file\',denied:\'Tipo de arquivo inválido: $ext!\'}}' />";
                    echo "<BR><INPUT class='frm' TYPE='text' size='30' value='$descricao_foto_posto' maxlength='100' name='descricao_foto_posto'>";
                }
            echo "</td>";
            echo "<td>";
                if (strlen($caminho_foto_contato1) > 0) {
                    $image = $caminho_foto_contato1;
                    $size = getimagesize("$image");
                    $height = $size[1];
                    $width  = $size[0];
                    echo "<IMG SRC='$caminho_thumb_contato1' WIDTH='100' HEIGHT='100' onclick=\"javascript:window.open('$caminho_foto_contato1','Contato','status=yes,scrollbars=no,width=$width,height=$height');\">";
                    echo "<BR>$descricao_foto_contato1";
                    echo "<BR><a href=\"javascript: if(confirm('Deseja excluir esta foto?')) window.location = '$PHP_SELF?posto=$posto&excluir_foto=$posto_fabrica_foto&foto=foto_contato1'\">";
                    echo "<img src='imagens/btn_x.gif' WIDTH='10' HEIGTH='10'><font size='1'>Excluir</font></a>";
                } else {
                    echo "<B>".traduz("Selecione a imagem")." (jpg,gif,png):</B><BR><input  class='frm' type='file' value='".traduz("Procurar foto")."' name='foto_contato1' class='multi {accept:\'jpg|gif|png\', max:1, STRING: {remove:\'Remover\',selected:\'Selecionado: $file\',denied:\'Tipo de arquivo inválido: $ext!\'}}' />";
                    echo "<BR><INPUT class='frm' TYPE='text' size='30' value='$descricao_foto_contato1' maxlength='100' name='descricao_foto_contato1'>";
                }
            echo "</td>";
            echo "<td>";
                if (strlen($caminho_foto_contato2) > 0) {
                    $image = $caminho_foto_contato2;
                    $size = getimagesize("$image");
                    $height = $size[1];
                    $width  = $size[0];
                    echo "<IMG SRC='$caminho_thumb_contato2' WIDTH='100' HEIGHT='100' onclick=\"javascript:window.open('$caminho_foto_contato2','Contato','status=yes,scrollbars=no,width=$width,height=$height');\">";
                    echo "<BR>$descricao_foto_contato2";
                    echo "<BR><a href=\"javascript: if(confirm('Deseja excluir esta foto?')) window.location = '$PHP_SELF?posto=$posto&excluir_foto=$posto_fabrica_foto&foto=foto_contato2'\">";
                    echo "<img src='imagens/btn_x.gif' WIDTH='10' HEIGTH='10'><font size='1'>Excluir</font></a>";
                } else {
                    echo "<B>".traduz("Selecione a imagem")." (jpg,gif,png):</B><BR><input class='frm' type='file' value='Procurar foto' name='foto_contato2' class='multi {accept:\'jpg|gif|png\', max:1, STRING: {remove:\'Remover\',selected:\'Selecionado: $file\',denied:\'Tipo de arquivo inválido: $ext!\'}}' />";
                    echo "<BR><INPUT class='frm' TYPE='text' size='30' value='$descricao_foto_contato2' maxlength='100' name='descricao_foto_contato2'>";
                }
            echo "</td>";
        echo "</tr>";
        echo "<tr>";
            echo "<td colspan='3'><FONT color='#B1B1B1' size='1'>".traduz("Clique sobre a imagem para ampliar")."</font></td>";
        echo "</tr>";
    echo "</table>";
}
?>

<?
if (strlen($data_alteracao) > 0 AND strlen($admin) > 0){
?>

<?php

$AuditorLog = new AuditorLog;
$res = $AuditorLog->getUltimoLog(array('tbl_posto_fabrica','tbl_posto_linha'), $login_fabrica.'*'.$posto,array('data_alteracao', 'admin'));

$data_alteracao_api = is_date($res['created'], 'U', 'EUR');
$admin_api = $res['user'];
if (!isset($admin_api)) {
    $admin_api = $admin;
}

?>

<table class="formulario" style="margin: 0 auto; width: 700px; border: 0; font-weight: bold;" cellpadding="3" cellspacing="2">
<tr>
    <td>&nbsp;</td>
    <td>&nbsp;</td>
    <td>&nbsp;</td>
</tr>
<? if(!empty($admin) or !empty($admin_api)) { ?>
<tr>
    <td ><?=traduz('Última alteração')?>: <? echo ($data_alteracao_api) ? $data_alteracao_api : ''?></td>
    <td>Usuário:  <?
    $sql = "SELECT login,fabrica FROM tbl_admin WHERE (fabrica = $login_fabrica OR fabrica=10) AND admin = $admin_api";
    $res = pg_query($con,$sql);

    echo pg_fetch_result($res,0,login);
    if(pg_fetch_result($res,0,fabrica)==10)echo " <font size='1'>(Telecontrol)</font>";


    ?></td>
    <!-- <td><input style="background-color: #596d9b; color: white; border-color: #596d9b" type="button" name="btnAuditorLog" onclick="javascript: window.open('relatorio_log_alteracao.php?parametro=tbl_posto_fabrica&id=<?php echo $posto; ?>')" value="Visualizar Log Auditor"></td> -->
    <td><a rel='shadowbox' href='relatorio_log_alteracao_new.php?parametro=tbl_posto_fabrica&id=<?php echo $posto; ?>' name="btnAuditorLog"><?=traduz('Visualizar Log Auditor')?></a></td>
</tr>
<? } ?>
</table>

<?
}

if($login_fabrica == 87){
    $posto_matriz = true;

    if($_GET["posto"]){
        $sql_matriz = "SELECT posto FROM tbl_posto_fabrica
            INNER JOIN tbl_tipo_posto ON tbl_tipo_posto.tipo_posto = tbl_posto_fabrica.tipo_posto
                AND tbl_tipo_posto.fabrica = $login_fabrica
                AND tbl_tipo_posto.codigo  = 'REVENDA'
            WHERE tbl_posto_fabrica.fabrica = $login_fabrica
                AND tbl_posto_fabrica.posto = ".$_GET["posto"];
        $res_matriz = pg_query($con,$sql_matriz);

        if(pg_num_rows($res_matriz) == 0){
            $posto_matriz = false;
        }
    }
}

if ($login_fabrica == 42 || ($login_fabrica == 87 && $posto_matriz)) { #HD 401553 INICIO
?>
<tr>
    <td colspan='2' >
        <table class='formulario' cellpadding='0' cellspacing='0' style="margin: 0 auto; width: 700px; border: 0;">

            <tr>
                <td class='titulo_coluna'><?=traduz('Filiais')?>('</td')>
            </tr>

            <tr>
                <td align='center'>
                    <div style='width:700px;'>
                        <style type="text/css">

                            .lista_filial {
                                list-style:none;
                            }

                            .lista_filial li{
                                display:block;
                                float:left;
                                border: 1px solid #fff;
                                margin: 2px;
                                padding: 2px 15px 2px 15px;
                            }

                        </style>

<?php
    if ($login_fabrica == 42) {
        $sql_distribuidores = "
            SELECT  tbl_posto_fabrica.nome_fantasia,
                    tbl_posto_fabrica.posto
            FROM    tbl_posto_fabrica
            WHERE   tbl_posto_fabrica.fabrica=$login_fabrica
            AND     tbl_posto_fabrica.filial IS TRUE
            AND     tbl_posto_fabrica.posto <> 6359
      ORDER BY      posto

        ";
    } else if ($login_fabrica == 87 and !empty($posto)) {
        $sql_distribuidores = "
            SELECT  tbl_posto.nome,
                    tbl_posto.cidade,
                    tbl_posto.estado,
                    tbl_posto_fabrica.codigo_posto,
                    tbl_posto_fabrica.nome_fantasia,
                    tbl_posto_fabrica.posto
            FROM    tbl_posto_filial
            JOIN    tbl_posto ON tbl_posto.posto = tbl_posto_filial.filial_posto
            JOIN    tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto
            WHERE   tbl_posto_fabrica.fabrica = $login_fabrica
            AND     tbl_posto_filial.posto = $posto
      ORDER BY      tbl_posto_fabrica.nome_fantasia";
    }

    $res_distribuidores = pg_query($con,$sql_distribuidores);
    $count_posto_filial = pg_num_rows($res_distribuidores);

    if ($login_fabrica != 87) {
?>
                                    <input type="hidden" id="qtde_posto_filial" name="qtde_posto_filial" value="<?=$count_posto_filial?>" />
                                    <table>
                                        <thead>
                                            <tr>
                                                <th><?=traduz('FILIAL')?></th>
                                                <th><?=traduz('GARANTIA')?></th>
                                                <th><?=traduz('FATURADO')?></th>
                                            <?php if ($login_fabrica == 42) { ?>
                                                <th><?=traduz('RETIRA')?></th>
                                                <th><?=traduz('SEDEX A COBRAR')?></th>
                                            <?php } ?>
                                            </tr>
                                        </thead>
                                        <tbody>

<?php
    } else {
?>
                                    <ul class='lista_filial'>
<?php
    }

    for ($x = 0; $x < $count_posto_filial; $x++) {

        $nome_fantasia_distrib = pg_fetch_result($res_distribuidores, $x, 'nome_fantasia');
        $posto_distrib         = pg_fetch_result($res_distribuidores, $x, 'posto');

        if ($login_fabrica == 87 && (empty($nome_fantasia_distrib) || strlen($nome_fantasia_distrib) == 0)) {
            $nome_fantasia_distrib = pg_fetch_result($res_distribuidores, $x, 'nome');
        }

        if ($login_fabrica == 87) {
            $filial_codigo = pg_fetch_result($res_distribuidores, $x, 'codigo_posto');
            $filial_cidade = pg_fetch_result($res_distribuidores, $x, 'cidade');
            $filial_cidade .= ', ' .  pg_fetch_result($res_distribuidores, $x, 'estado');
        }

        if ($_GET['posto']) {
            $posto_edicao = $_GET['posto'];

            $sql_posto_filial = "
                SELECT  tbl_posto_filial.posto,
                        tbl_posto_filial.filial_posto,
                        tbl_posto_filial.garantia,
                        tbl_posto_filial.faturado,
                        tbl_posto_filial.parametros_adicionais
                FROM    tbl_posto_filial
                WHERE   posto           = $posto_edicao
                AND     filial_posto    = $posto_distrib
            ";

            $res_posto_filial = pg_query($con,$sql_posto_filial);

            $checked_distrib    = (pg_num_rows($res_posto_filial) > 0) ? "CHECKED" : null;
            $checked_garantia   = (pg_fetch_result($res_posto_filial,0,garantia) == 't') ? "CHECKED" : null;
            $checked_faturado   = (pg_fetch_result($res_posto_filial,0,faturado) == 't') ? "CHECKED" : null;

            if ($login_fabrica == 42) {
                $parametros_filial = pg_fetch_result($res_posto_filial, 0, 'parametros_adicionais');
                $parametros_filial = json_decode($parametros_filial, true);

                if (!is_null($parametros_filial) && is_array($parametros_filial)) {
                    $checked_retira = ($parametros_filial["retira"] == "TRUE") ? 'checked' : null;
                    $checked_sedex_cobrar = ($parametros_filial['sedex'] == "TRUE") ? 'checked' : null;
                } else {
                    $checked_retira = null;
                    $checked_sedex_cobrar = null;
                }
            }
        } else {
            $checked_distrib    = null;
            $checked_garantia   = null;
            $checked_faturado   = null;
            $checked_retira     = null;
            $checked_sedex_cobrar = null;
        }

        if ($login_fabrica != 87) {
            if (in_array($login_fabrica, [42])) {
                $style_center = "style='text-align: center;'";
            }
?>
                                <tr>
                                    <td>
                                        <input type="hidden" name="posto_distrib_<?=$x?>" value='<?=$posto_distrib?>' />
                                        <?=$nome_fantasia_distrib?>
                                    </td>
                                    <td class="tac" <?=$style_center?>>
                                        <input type="checkbox" name="filial_garantia_<?=$x?>" id="filial_garantia_<?=$x?>" value='t' style='margin-top: 2px;' <?=$checked_garantia?> />
                                    </td>
                                    <td class="tac" <?=$style_center?>>
                                        <input type="checkbox" name="filial_faturado_<?=$x?>" id="filial_faturado_<?=$x?>" value='t' style='margin-top: 2px;' <?=$checked_faturado?> />
                                    </td>
                                <?php if ($login_fabrica == 42) { ?>
                                    <td class="tac" <?=$style_center?>>
                                        <input type="checkbox" <?= $checked_retira ?> name="filial_retira_<?=$x?>" id="filial_retira_<?=$x?>" value='t' style='margin-top: 2px;' />
                                    </td>
                                    <td class="tac" <?=$style_center?>>
                                        <input type="checkbox" <?= $checked_sedex_cobrar ?> name="filial_sedex_cobrar_<?=$x?>" id="filial_sedex_cobrar<?=$x?>" value='t' style='margin-top: 2px;' />
                                    </td>
                                <?php } ?>
                                </tr>
<?php
        } else {
?>
                                <li>
                                    <?=$filial_codigo . ' - ' . $nome_fantasia_distrib . ' - ' . $filial_cidade?>
                                </li>
<?php
        }
    }
    if ($login_fabrica != 87) {
?>
                            </tbody>
                        </table>
<?php
    } else {
?>
                            </ul>
<?php
    }
?>
                        <div style='clear:both'></div>
                    </div>

                </td>
            </tr>

            <tr><td>&nbsp;</td></tr>

        </table>
    </td>
</tr>
<?php
}  #HD 401553 FIM
?>
<tr><td>&nbsp;</td></tr>

<?php

if($contrato_posto && strlen($posto) > 0){

    $tDocs = new TDocs($con, $login_fabrica);

    $info = $tDocs->getdocumentsByRef($posto, "posto", "contrato");

    if(count($info->attachListInfo) > 0){
        $qtde_contratos = 5 - count($info->attachListInfo);
    }else{
        $qtde_contratos = 5;
    }

    ?>

    <style>
        .box-contrato{
            float: left;
            height: 130px;
            width: 110px;
            padding: 14px;
            text-align: center;
        }
        .box-contrato img{
            width: 73px;
        }
        .box-contrato button{
            margin-top: 10px;
        }
    </style>

    <?php

    echo "<br />";

    echo "<table class='formulario' style='margin: 0 auto; width: 700px;' cellpadding='1' cellspacing='2' >";

        echo "
        <tr>
            <td class='titulo_tabela'>".traduz("Contratos")."</td>
        </tr>
        ";

        echo "<tr>";

            echo "<td>";

                if(count($info->attachListInfo) > 0){

                    foreach ($info->attachListInfo as $anexo) {

                        $tdocs_id = $anexo["tdocs_id"];
                        $link_arq = $anexo["link"];
                        $icon_pdf = "imagens/pdf_icone.png";

                        echo "
                        <div class='box-contrato'>
                            <a href='{$link_arq}' target='_blank'>
                                <img src='{$icon_pdf}' />
                            </a>
                            <br />
                            <button type='button' onclick='excluir_contrato(\"{$tdocs_id}\")'>
                                ".traduz("Excluir")."
                            </button>
                        </div>
                        ";
                    }
                    
                    echo "<div style='clear: both;'></div>";
                }else{
                    echo "<p style='text-align: center; text-transform: uppercase;'> ".traduz("Sem contratos anexados pelo posto autorizado")." </p>";
                }

                if($credenciamento == "CREDENCIADO" 
                    OR $credenciamento == "DESCREDENCIAMENTO" 
                    OR $credenciamento == 'DESCREDENCIAMENTO - REPROVADO' 
                    OR $credenciamento == "PRÉ CADASTRO - APROVADO"
                    OR $credenciamento == "PRÉ CADASTRO - REPROVADO"
                    OR $credenciamento == "EM DESCREDENCIAMENTO" ){  


                    if($categoria_posto == "Locadora Autorizada"){
                        echo "<button type='button' onclick=\"location.href='download_contrato_atualizado_black.php?posto_id=$posto&categoria=Autorizada'\">".traduz("Baixar Contrato Atualizado(Autorizada)")."</button>";
                        echo "<button type='button' onclick=\"location.href='download_contrato_atualizado_black.php?posto_id=$posto&categoria=Locadora'\">".traduz("Baixar Contrato Atualizado(Locadora)")."</button>";
                    }else{
                        echo "<button type='button' onclick=\"location.href='download_contrato_atualizado_black.php?posto_id=$posto'\">".traduz("Baixar Contrato Atualizado")."</button>";
                    }
                    
                    echo "<button type='button' id='enviar_aprovacao'> ".traduz("Enviar para Aprovação (Contrato Atualizado)")." </button>";
                }


            echo "</td>";

        echo "</tr>";

    echo "</table>";

    if($login_fabrica == 1 and $qtde_contratos > 0){
        echo "<br><table class='formulario' style='margin: 0 auto; width: 700px;' cellpadding='1' cellspacing='2' >";
        echo "<tr>
                <td colspan='3' class='titulo_tabela'>".traduz("Anexos")."</td>
            </tr>";
        for($a = 1; $a <= $qtde_contratos; $a++){
        echo "<tr>
            <td width=234></td>
            <td align='left' width='234'>
                <label class='control-label' for='senha'>".traduz("Contrato")." {$a}</label>
                <br>
                <input type='file' name='contrato_$a' value=''>
                <br><Br>
            </td>
            <td width=234></td>
        </tr>";
        }

        echo "</table>";
        echo "<input type='hidden' name='qtde_contratos' value='$qtde_contratos'>";
    }
}

if($login_fabrica == 1 AND $cadastro_bloqueado){
	$displayGravar = "style=display:none;";
}
?>

<tr>
    <td colspan='4'>
<a name="postos">
<br>
<center>

<input type="hidden" name="btn_acao" />

<?php if($login_fabrica == 1){?>
    <input type="hidden" name="observacao_credenciamento" id="observacao_credenciamento" value="">
<?php } ?>

<input type="button" value='<?=traduz("Gravar")?>'
    ALT='<?=traduz("Gravar formulário")?>' border='0'
    onclick="javascript:
    <?php if($login_fabrica == 1 and (strlen(trim($credenciamento))>0  OR  strlen(trim($credenciamentoObs))>0)  ) { ?>
        if(verificaAlteracao()){
            if (document.frm_posto.btn_acao.value == '' ) {
                document.frm_posto.btn_acao.value='gravar';        
                document.frm_posto.submit()
            } else {
                alert ('<?=traduz("Aguarde submissão")?>')
            }
        }
    <?php }else{  ?>
        if (document.frm_posto.btn_acao.value == '' ) {
            document.frm_posto.btn_acao.value='gravar';        
            document.frm_posto.submit()
        } else {
            alert ('<?=traduz("Aguarde submissão")?>')
        }
    <?php } ?>
"
 <?=$displayGravar?>>

<input type="button"
    ALT="Limpar campos" border='0'
    value="Limpar"
    onclick="javascript:
    if (document.frm_posto.btn_acao.value == '' ) {
        document.frm_posto.btn_acao.value='reset';
        window.location='<?= $PHP_SELF ?>'
    } else {
        alert ('<?=traduz("Aguarde submissão")?>')
    }"
>

<?php if ($login_fabrica == 158) { ?>
    <input type="button"  id="zera_estoque" value='<?=traduz("Zerar Estoque")?>' ALT='<?=traduz("Zerar o estoque do Posto")?>' border='0'>
<?php } ?>

</center>
</a>
<br>
</td></tr>
</TABLE>
<!-- ============================ Botoes de Acao ========================= -->
</form>

<?
# HD - Monteiro
if(in_array($login_fabrica,array(81,114,122,123,125,128,136,146,147,160)) or $replica_einhell){
    //Se for enviado
?>

    <form method="post" action="<? echo $PHP_SELF ?>" enctype="multipart/form-data">
        <p><?=traduz('Obs* Gravar fotos no formato')?> JPEG|JPG|PNG</p>
        <table class="formulario" style="margin: 0 auto; width: 700px; border: 0;"cellpadding="1" cellspacing="3">
            <tr>
                <td colspan="4" class="titulo_tabela">
                    <?=traduz('Imagens do Posto')?>
                </td>
            </tr>
            <tr>
                <td id='fachada'>
                    <?
                    $caminho_imagem = '../autocredenciamento/fotos/';
                    $caminho_path   = '../autocredenciamento/fotos/';
                    $cnpj_img = preg_replace('/\D/','',utf8_decode($cnpj));
                    $img_path = $caminho_path.$cnpj_img;
                    $img_caminho = $caminho_imagem.$cnpj_img;

                    if (is_numeric($posto)) {
						$files = glob($img_caminho."_1.*");
						$img_ext = explode($img_caminho."_1.",$files[0]);
						$img_ext = $img_ext[1];
					}
                    if ($img_ext) {
                        $img_src1 = $img_path."_1.$img_ext";
                    ?>
            <img id="imagem_fachada" src="<?php echo $img_src1;?>" onclick="showImage('<?php echo $img_src1; ?>')" style="width:125px; height:125px;" />
                        <br />

                    <?}
                    unset($img_ext);
                    ?>

                </td>
                <td id='recepcao'>
                    <?
                    if (is_numeric($posto)) {
						$files = glob($img_caminho."_2.*");
						$img_ext = explode($img_caminho."_2.",$files[0]);
						$img_ext = $img_ext[1];
					}
                    if ($img_ext) {
                        $img_src2 = $img_path."_2.$img_ext";
                    ?>
                            <img src="<?php echo $img_src2;?>" style="width:125px; height:125px;" onclick="showImage('<?php echo $img_src2; ?>')"/>
                        <br />


                    <?}
                    unset($img_ext);
                    ?>

                </td>
                <td id='bancada'>
                    <?
                    if (is_numeric($posto)) {
						$files = glob($img_caminho."_3.*");
						$img_ext = explode($img_caminho."_3.",$files[0]);
						$img_ext = $img_ext[1];
						
					}
                    if ($img_ext) {
                        $img_src3 = $img_path."_3.$img_ext";
                    ?>
                            <img src="<?php echo $img_src3;?>" style="width:125px; height:125px;" onclick="showImage('<?php echo $img_src3; ?>')"/>
                        <br />

                    <?}
                    unset($img_ext);
                    ?>

                </td>
            </tr>
            <tr>
                <td>Fachada</td>
                <td>Recepção</td>
                <td>Bancada</td>
            </tr>
            <tr>
                <td>
                    <input type='button' name='arquivo1' id='arquivo1' data-tipo-anexo="fachada" class="btn_arquivo_posto" value='<?=traduz("Anexar")?>' accept="jpeg|jpg" size='1' style="width:125px;" />
                    <input onclick="deleteImage('<?php echo $img_src1; ?>','excluir_1', 'fachada')" type='button' value='<?=traduz("Excluir")?>' style="height: 20px;border-radius: 5px;width:125px;color: white;background-color: darkred;" />
                </td>
                <td>
                    <input type='button' name='arquivo2' id='arquivo2' data-tipo-anexo="recepcao" class="btn_arquivo_posto" value="Anexar" accept="jpeg|jpg" size='1' style="width:125px;" />
                    <input onclick="deleteImage('<?php echo $img_src2; ?>','excluir_2', 'recepcao')" type='button' value='<?=traduz("Excluir")?>' style="height: 20px;border-radius: 5px;width:125px;color: white;background-color: darkred;" />
                </td>
                <td>
                    <input type='button' name='arquivo3' id='arquivo3' data-tipo-anexo="bancada" class="btn_arquivo_posto" value="Anexar" accept="jpeg|jpg" size='1' style="width:125px;" />
                    <input onclick="deleteImage('<?php echo $img_src3; ?>','excluir_3', 'bancada')" type='button' value='<?=traduz("Excluir")?>' style="height: 20px;border-radius: 5px;width:125px;color: white;background-color: darkred;" />
                </td>
            </tr>
            <tr align="center" colspan="3">
                <td align="center" colspan="3">
                    <p>
                    <input type="hidden" name="cnpj_imagem" value="<? echo $cnpj ?>">
                    <input type="hidden" name="posto_imagem" value="<? echo $posto ?>">
                    </p>
                </td>
            </tr>

        </table>
    </form>

<?
}
?>

<table style="margin: 0 auto; width: 700px;">
<? if (strlen($posto) > 0) {
        if($login_fabrica == 153){
            echo "<tr>
                    <td>
                        <a href='contratos_positron/contrato_vigente_positron_2015.pdf' target='_blank'>Contrato Positron</a>
                        &nbsp;&nbsp;&nbsp;&nbsp;
                        <a href='contratos_positron/termo_adesao_postos_assistencia_tecnica_positron_2015.doc' target='_blank'>Termo de Adesão Positron</a>
                        <br /><br />
                        <input class='input' type='button' name='btn' id='envia_contrato' value='Enviar Contratos' style='width:120px' onclick='javascript:EnviaContratosPositron(\"$posto\")'>
                    </td>
                  </tr>";
        }

        if($fabricaEnviaContrato == true || in_array($login_fabrica, array(147, 160)) || $replica_einhell){
            if (!in_array($login_fabrica, [167, 203])) {
            ?>
                <tr>
                    <td>
                        <br />
                        <input class='input' type='button' name='btn' id='envia_contrato' value='<?=traduz("Enviar Contratos")?>' style='width:120px' onclick='javascript:EnviaContratos("<?=$posto?>","<?=$login_fabrica?>")'>
                    </td>
                </tr>
                <tr><td>&nbsp;</td></tr>
            <?php
            }
            ?>
            <tr>
                <td>
                    <form name='frm_contrato' method='post' enctype='multipart/form-data'>
                        <input type='file' name='contrato'>
                        <input type='hidden' name='posto_contrato' value='<?=$posto?>'>
                        <input type='submit' value='<?=traduz("Anexar Contrato")?>' name='anexa_contrato'> <br><br>
                        <span style='font-size:10px;color:#000'>
                            <?=traduz('Tipos de arquivos suportados')?> (PDF).<br><?=traduz('Tamanho máximo do arquivo de')?> 2MB
                        </span>
                    </form>
                </td>
            </tr>
            <tr><td>&nbsp;</td></tr>
            <tr>
                <td>
                    <?php
                        if (in_array($login_fabrica, [167, 203])) {
                            $sql_contrato = "SELECT DISTINCT contrato FROM tbl_posto_contrato WHERE posto = $posto AND fabrica = $login_fabrica AND confirmacao";
                            $res_contrato = pg_query($con, $sql_contrato);

                            if (pg_num_rows($res_contrato) > 0) {

                                include_once __DIR__.'/plugins/fileuploader/TdocsMirror.php';

                                $contratos_id = pg_fetch_all($res_contrato);

                                foreach ($contratos_id as $key => $value) {
                                    $sql_id_tdocs = "SELECT tdocs_id FROM tbl_tdocs WHERE referencia_id = ".$value['contrato'].$posto." AND fabrica = $login_fabrica AND referencia = 'posto_contrato' AND situacao = 'ativo' ORDER BY data_input DESC  LIMIT 1";                                    
                                    $res_id_tdocs = pg_query($con, $sql_id_tdocs);
                                    if (pg_num_rows($res_id_tdocs) > 0) {
                                        $unique_id        = pg_fetch_result($res_id_tdocs, 0, 'tdocs_id');
                                        $tdocsMirror      = new TdocsMirror();
                                        $resposta_link    = $tdocsMirror->get($unique_id);
                                        $link_contrato    = $resposta_link["link"];    
                                        
                                        echo '<a href="'.$link_contrato.'" target="_blank" style="font-size: 10px; color: blue; font-weight: bold; cursor: pointer;"><img alt="Baixar Anexo" src="../imagens/pdf_transparente.jpg" title="Clique para ver a imagem em uma escala maior" style="width: 100px; height: 90px;" /></a>';
                                    }
                                }
                            }
                        } else {
                            //verifica se tem contrato
                            $amazonTC->getObjectList("contrato_posto_{$login_fabrica}_{$posto}");
                            $files_anexo_posto = $amazonTC->files;

                            if(count($files_anexo_posto) > 0){

                                $arr_docs = array("pdf", 'doc', 'docx');

                                foreach ($files_anexo_posto as $key => $path) {

                                    $basename = basename($path);
                                    $thumb = $amazonTC->getLink("thumb_".$basename, false, "", "");
                                    $full  = $amazonTC->getLink($basename, false, "", "");
                                    $pathinfo = pathinfo($full);
                                    list($ext,$params) = explode("?", $pathinfo["extension"]);

                                    $tag_abre = '<a href="' . $full . '">';
                                    $tag_fecha = '</a>';

                                    echo $tag_abre;

                                if($ext == "pdf"){
                                ?>
                                    <img alt='<?=traduz("Baixar Anexo")?>' src="imagens/adobe.JPG" title='<?=traduz("Clique para ver a imagem em uma escala maior")?>' style="width: 100px; height: 90px;" />
                                <?php
                                }else{
                                ?>
                                    <img alt='<?=traduz("Baixar Anexo")?>' src="<?=$thumb?>" title='<?=traduz("Clique para ver a imagem em uma escala maior")?>' style="width: 100px; height: 90px;" />
                                <?php
                                }

                                    echo $tag_fecha;
                                }
                            }
                        }

                    ?>
                </td>
            </tr>
            <tr><td>&nbsp;</td></tr>
    <?php
        }
    ?>
    <tr>
        <td>
            <?php 
                $sqlCodPosto = "SELECT codigo_posto, senha FROM tbl_posto_fabrica WHERE fabrica = $login_fabrica AND posto = $posto";
                $resCodPosto = pg_query($con, $sqlCodPosto);
                if (pg_num_rows($resCodPosto) > 0) {
                    $codPosto   = pg_fetch_result($resCodPosto, 0, 'codigo_posto');
                    $senhaPosto = pg_fetch_result($resCodPosto, 0, 'senha');
                }
            ?>

            <button type="button" onclick="abreAreaPosto('<?=$codPosto?>', '<?=$senhaPosto?>')"><?=traduz('Clique Aqui para acessar como se fosse este Posto')?></button>
        </td>
    </tr>
    <tr>
        <td>
            <br />
            <button type="button" class="btn-solicita"  title='<?=traduz("Ao clicar nessa opção, será direcionado para a tela de abertura de chamado Help-Desk.")?>'  onclick="javascript: window.open('../helpdesk/chamado_detalhe.php?tipo=8&posto=<?=$posto?>')" ><?=traduz('Solicitar Alteração de Dados Cadastrais')?></button>
            <button type="button" class="btn_consulta_receita"  title='<?=traduz("Ao clicar nessa opção para comprar os dados com a receita")?>' ><?=traduz('Consultar Dados Receita')?></button>
        </td>
    </tr>
    <? if (in_array($login_fabrica,array(35,81,114,122,123,125,128,147,160,198)) or $replica_einhell) { ?>
    <tr><td>&nbsp;</td></tr>
    <tr>
        <td>
            <input type="button" value="<?=traduz('Gerar contrato');?>" onclick="javascript: window.open('../credenciamento/gera_contrato.php?fabrica=<?=$login_fabrica?>&cnpj=<?=$cnpj?>&tipo_arquivo=pdf&btn_acao=1')">            

            <? if(is_file("anexos/contrato_$posto.pdf") OR $telecontrol_distrib ){
                    if($telecontrol_distrib) { 
                        $link = retornaLinkContratoGestao();
                        if ($link != 'semLink') {
                            $link = json_decode($link, true);
                            echo '<br><br>';
                            foreach ($link as $key => $value) {
                                echo $value;
                            }
                        }
                    } else { ?>    
                        <input type='button' value='<?=traduz('Abrir contrato');?>' onclick="window.open('anexos/contrato_<?=$posto?>.pdf')">
            <?      }
                } ?>
        </td>
    </tr>
    <tr><td>&nbsp;</td></tr>
    <tr>
        <td>
            <form name='frm_contrato' method='post' enctype='multipart/form-data'>
                <input type='file' name='contrato'>
                <input type='hidden' name='posto_contrato' value='<?=$posto?>'>
                <input type='submit' value='<?=traduz("Anexar Contrato")?>' name='anexa_contrato'> <br><br>
                <span style='font-size:10px;color:#000'>
                    <?=traduz('Tipos de arquivos suportados')?> (PDF,DOC,ZIP e RAR).<br><?=traduz('Tamanho máximo do arquivo de')?> 2MB
                </span>
            </form>
        </td>
    </tr>

<? } ?>
<? if (in_array($login_fabrica,array(156, 160))) {


    $sql = "SELECT    tbl_posto.nome,
                             tbl_posto.nome_fantasia,
                             tbl_posto_fabrica.codigo_posto,
                             tbl_posto.cnpj,
                             tbl_posto.ie,
                             tbl_posto_fabrica.contato_fone_comercial,
                             tbl_posto_fabrica.contato_fax,
                             tbl_posto_fabrica.contato_email,
                             tbl_posto_fabrica.contato_nome,
                             tbl_posto_fabrica.contato_pais,
                             tbl_posto_fabrica.contato_cep,
                             tbl_posto_fabrica.contato_estado,
                             tbl_posto_fabrica.contato_cidade,
                             tbl_posto_fabrica.contato_bairro,
                             tbl_posto_fabrica.contato_endereco,
                             tbl_posto_fabrica.contato_numero,
                             tbl_posto_fabrica.contato_complemento,
                             tbl_posto.capital_interior,
                             tbl_tipo_posto.descricao,
                             tbl_posto_fabrica.desconto,
                             tbl_posto_fabrica.valor_km,
                             tbl_transportadora.nome AS trans_nome,
                             tbl_posto.suframa,
                             tbl_posto_fabrica.item_aparencia,
                             tbl_posto_fabrica.divulgar_consumidor,
                             tbl_posto_fabrica.controla_estoque,
                             tbl_posto_fabrica.obs,
                             tbl_posto_fabrica.cobranca_cep,
                             tbl_posto_fabrica.cobranca_endereco,
                             tbl_posto_fabrica.cobranca_cidade,
                             tbl_posto_fabrica.cobranca_bairro,
                             tbl_posto_fabrica.cobranca_endereco,
                             tbl_posto_fabrica.cobranca_numero,
                             tbl_posto_fabrica.cobranca_complemento,
                             tbl_posto_fabrica.favorecido_conta,
                             tbl_posto_fabrica.cpf_conta,
                             (tbl_posto_fabrica.banco || ' - ' || tbl_posto_fabrica.nomebanco) as banco_nome,
                             tbl_posto_fabrica.tipo_conta,
                             tbl_posto_fabrica.agencia,
                             tbl_posto_fabrica.conta,
                             tbl_posto_fabrica.obs_conta,
                             tbl_posto_fabrica.pedido_faturado,
                             tbl_posto_fabrica.pedido_em_garantia,
                             tbl_posto_fabrica.digita_os
           FROM tbl_posto
            LEFT JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
            LEFT JOIN tbl_tipo_posto ON tbl_posto_fabrica.tipo_posto = tbl_tipo_posto.tipo_posto and tbl_tipo_posto.fabrica = $login_fabrica
            LEFT JOIN tbl_transportadora ON tbl_transportadora.transportadora = tbl_posto_fabrica.transportadora
          WHERE tbl_posto_fabrica.posto = $posto AND tbl_posto_fabrica.fabrica = $login_fabrica";
            $res = pg_query($con,$sql);

        if (pg_num_rows($res) > 0) {
            $nome                   = pg_fetch_result($res,0, 'nome');
            $nome_fantasia          = pg_fetch_result($res,0, 'nome_fantasia');
            $codigo_posto           = pg_fetch_result($res,0, 'codigo_posto');
            $cnpj                   = pg_fetch_result($res,0, 'cnpj');
            $ie                     = pg_fetch_result($res,0, 'ie');
            $contato_fone_comercial = pg_fetch_result($res,0, 'contato_fone_comercial');
            $contato_fax            = pg_fetch_result($res,0, 'contato_fax');
            $contato_email          = pg_fetch_result($res,0, 'contato_email');
            $contato_nome          = pg_fetch_result($res,0, 'contato_nome');
            $contato_pais           = pg_fetch_result($res,0, 'contato_pais');
            $contato_cep            = pg_fetch_result($res,0, 'contato_cep');
            $contato_estado         = pg_fetch_result($res,0, 'contato_estado');
            $contato_cidade         = pg_fetch_result($res,0, 'contato_cidade');
            $contato_bairro         = pg_fetch_result($res,0, 'contato_bairro');
            $contato_endereco       = pg_fetch_result($res,0, 'contato_endereco');
            $contato_numero         = pg_fetch_result($res,0, 'contato_numero');
            $contato_complemento    = pg_fetch_result($res,0, 'contato_complemento');
            $capital_interior       = pg_fetch_result($res,0, 'capital_interior');
            $descricao              = pg_fetch_result($res,0, 'descricao');
            $desconto               = pg_fetch_result($res,0, 'desconto');
            $valor_km               = pg_fetch_result($res,0, 'valor_km');
            $trans_nome                   = pg_fetch_result($res,0, 'trans_nome');
            $suframa                = pg_fetch_result($res,0, 'suframa');
            $item_aparencia         = pg_fetch_result($res,0, 'item_aparencia');
            $divulgar_consumidor    = pg_fetch_result($res,0, 'divulgar_consumidor');
            $controla_estoque       = pg_fetch_result($res,0, 'controla_estoque');
            $obs                    = pg_fetch_result($res,0, 'obs');
            $cobranca_cep           = pg_fetch_result($res,0, 'cobranca_cep');
            $cobranca_endereco      = pg_fetch_result($res,0, 'cobranca_endereco');
            $cobranca_cidade        = pg_fetch_result($res,0, 'cobranca_cidade');
            $cobranca_bairro        = pg_fetch_result($res,0, 'cobranca_bairro');
            $cobranca_endereco      = pg_fetch_result($res,0, 'cobranca_endereco');
            $cobranca_numero        = pg_fetch_result($res,0, 'cobranca_numero');
            $cobranca_complemento   = pg_fetch_result($res,0, 'cobranca_complemento');
            $favorecido_conta       = pg_fetch_result($res,0, 'favorecido_conta');
            $cpf_conta              = pg_fetch_result($res,0, 'cpf_conta');
            $banco_nome             = pg_fetch_result($res,0, 'banco_nome');
            $tipo_conta             = pg_fetch_result($res,0, 'tipo_conta');
            $agencia                = pg_fetch_result($res,0, 'agencia');
            $conta                  = pg_fetch_result($res,0, 'conta');
            $obs_conta              = pg_fetch_result($res,0, 'obs_conta');
            $pedido_faturado        = pg_fetch_result($res,0, 'pedido_faturado');
            $pedido_em_garantia     = pg_fetch_result($res,0, 'pedido_em_garantia');
            $digita_os              = pg_fetch_result($res,0, 'digita_os');

            if($contato_complemento=='f' || strlen($contato_complemento)==0){
                $contato_complemento = "Não" ;
            }else if ($contato_complemento == 't'){
                $contato_complemento = "Sim";
            }
            if($capital_interior=='f' || strlen($capital_interior)==0){
                $capital_interior = "Não" ;
            }else if ($capital_interior == 't'){
                $capital_interior = "Sim";
            }
            if($descricao=='f' || strlen($descricao)==0){
                $descricao = "Não" ;
            }else if ($descricao == 't'){
                $descricao = "Sim";
            }
            if($desconto=='f' || strlen($desconto)==0){
                $desconto = "Não" ;
            }else if ($desconto == 't'){
                $desconto = "Sim";
            }
            if($valor_km=='f' || strlen($valor_km)==0){
                $valor_km = "Não" ;
            }else if ($valor_km == 't'){
                $valor_km = "Sim";
            }
            if($trans_nome=='f' || strlen($trans_nome)==0){
                $trans_nome = "Não" ;
            }else if ($trans_nome == 't'){
                $trans_nome = "Sim";
            }
            if($suframa=='f' || strlen($suframa)==0){
                $suframa = "Não" ;
            }else if ($suframa == 't'){
                $suframa = "Sim";
            }
            if($item_aparencia=='f' || strlen($item_aparencia)==0){
                $item_aparencia = "Não" ;
            }else if ($item_aparencia == 't'){
                $item_aparencia = "Sim";
            }
            if($divulgar_consumidor=='f' || strlen($divulgar_consumidor)==0){
                $divulgar_consumidor = "Não" ;
            }else if ($divulgar_consumidor == 't'){
                $divulgar_consumidor = "Sim";
            }
            if($controla_estoque=='f' || strlen($controla_estoque)==0){
                $controla_estoque = "Não" ;
            }else if ($controla_estoque == 't'){
                $controla_estoque = "Sim";
            }
            if($obs=='f' || strlen($obs)==0){
                $obs = "Não" ;
            }else if ($obs == 't'){
                $obs = "Sim";
            }
            if($cobranca_cep=='f' || strlen($cobranca_cep)==0){
                $cobranca_cep = "Não" ;
            }else if ($cobranca_cep == 't'){
                $cobranca_cep = "Sim";
            }
            if($cobranca_endereco=='f' || strlen($cobranca_endereco)==0){
                $cobranca_endereco = "Não" ;
            }else if ($cobranca_endereco == 't'){
                $cobranca_endereco = "Sim";
            }
            if($cobranca_cidade=='f' || strlen($cobranca_cidade)==0){
                $cobranca_cidade = "Não" ;
            }else if ($cobranca_cidade == 't'){
                $cobranca_cidade = "Sim";
            }
            if($cobranca_bairro=='f' || strlen($cobranca_bairro)==0){
                $cobranca_bairro = "Não" ;
            }else if ($cobranca_bairro == 't'){
                $cobranca_bairro = "Sim";
            }
            if($cobranca_endereco=='f' || strlen($cobranca_endereco)==0){
                $cobranca_endereco = "Não" ;
            }else if ($cobranca_endereco == 't'){
                $cobranca_endereco = "Sim";
            }
            if($cobranca_numero=='f' || strlen($cobranca_numero)==0){
                $cobranca_numero = "Não" ;
            }else if ($cobranca_numero == 't'){
                $cobranca_numero = "Sim";
            }
            if($cobranca_complemento=='f' || strlen($cobranca_complemento)==0){
                $cobranca_complemento = "Não" ;
            }else if ($cobranca_complemento == 't'){
                $cobranca_complemento = "Sim";
            }
            if($favorecido_conta=='f' || strlen($favorecido_conta)==0){
                $favorecido_conta = "Não" ;
            }else if ($favorecido_conta == 't'){
                $favorecido_conta = "Sim";
            }
            if($cpf_conta=='f' || strlen($cpf_conta)==0){
                $cpf_conta = "Não" ;
            }else if ($cpf_conta == 't'){
                $cpf_conta = "Sim";
            }
            if($banco_nome=='f' || strlen($banco_nome)==0){
                $banco_nome = "Não" ;
            }else if ($banco_nome == 't'){
                $banco_nome = "Sim";
            }
            if($tipo_conta=='f' || strlen($tipo_conta)==0){
                $tipo_conta = "Não" ;
            }else if ($tipo_conta == 't'){
                $tipo_conta = "Sim";
            }
            if($agencia=='f' || strlen($agencia)==0){
                $agencia = "Não" ;
            }else if ($agencia == 't'){
                $agencia = "Sim";
            }
            if($conta=='f' || strlen($conta)==0){
                $conta = "Não" ;
            }else if ($conta == 't'){
                $conta = "Sim";
            }
            if($obs_conta=='f' || strlen($obs_conta)==0){
                $obs_conta = "Não" ;
            }else if ($obs_conta == 't'){
                $obs_conta = "Sim";
            }
            if($pedido_faturado=='f' || strlen($pedido_faturado)==0){
                $pedido_faturado = "Não" ;
            }else if ($pedido_faturado == 't'){
                $pedido_faturado = "Sim";
            }
            if($pedido_em_garantia=='f' || strlen($pedido_em_garantia)==0){

                $pedido_em_garantia = "Não" ;
            }else if ($pedido_em_garantia == 't'){
                $pedido_em_garantia = "Sim";
            }
            if($digita_os=='f' || strlen($digita_os)==0){
                $digita_os = "Não" ;
            }else if ($digita_os == 't'){
                $digita_os = "Sim";
            }

            $data = date ("d-m-Y-H-i");

            $arquivo_nome = "relatorio_do_posto_$cnpj-$data.xls";
            $path         = "/var/www/assist/www/admin/xls/";
            #$path         = "/home/ronald/public_html/posvenda/admin/xls/";
            $path_tmp     = "/tmp/assist/";

            $arquivo_completo     = $path.$arquivo_nome;
            $arquivo_completo_tmp = $path_tmp.$arquivo_nome;

            echo `rm $arquivo_completo_tmp `;
            echo `rm $arquivo_completo `;

            $fp = fopen ($arquivo_completo_tmp,"w");

            fputs ($fp, utf8_encode(" Dados Cadastrais do Posto Autorizado \t \t \t \t \t \t \t \t \t \t \t \t \t \t \t \t \t \t \t \t \t \t \t \t \t \t \t Endereço para Cobrança \t \t \t \t \t \t \t \t \t \t \t \t \t \t Posto pode Digitar \t \t \r\n"));

            fputs ($fp, utf8_encode(" Razão Social  \t Nome Fantasia \t Codigo do Posto \t CNPJ \t Inscrição Estadual \t Telefone Comercial \t FAX \t Email \t Contato Nome \t País \t CEP \t Estado \t Cidade \t Bairro \t Endereço \t Número \t Complemento \t capital/interior \t Tipo do Posto \t Desconto \t Valor KM \t Transportadora \t Região Suframa  \t Pedido de item de aparência \t Divulgar para o consumidor (mapa da rede)  \t Controla Estoque \t Observações \t CEP \t Cobranca endereco \t Cobranca  cidade \t Cobranca bairro \t Cobranca endereco \t Cobranca numero \t Cobranca complemento \t Nome do Favorecido  \t CPF/CNPJ do Favorecido  \t Banco \t Tipo de Conta \t Agência \t Número da Conta  \t Observações \t Pedido Faturado \t Pedido em Garantia \t Digita OS \r\n"));

            fputs ($fp, utf8_encode(" $nome \t $nome_fantasia \t $codigo_posto \t $cnpj \t $ie \t $contato_fone_comercial \t $contato_fax \t $contato_email \t $contato_nome \t $contato_pais \t $contato_cep \t $contato_estado \t $contato_cidade \t $contato_bairro \t $contato_endereco \t $contato_numero \t $contato_complemento \t $capital_interior \t $descricao \t $desconto \t $valor_km \t $trans_nome \t $suframa \t $item_aparencia \t $divulgar_consumidor \t $controla_estoque \t $obs \t $cobranca_cep \t $cobranca_endereco \t $cobranca_cidade \t $cobranca_bairro \t $cobranca_endereco \t $cobranca_numero \t $cobranca_complemento \t $favorecido_conta \t $cpf_conta \t $banco_nome \t $tipo_conta \t $agencia \t $conta \t $obs_conta \t $pedido_faturado \t $pedido_em_garantia \t $digita_os \r\n") );



            $sql = "SELECT tbl_linha.nome, tbl_tabela.sigla_tabela
                FROM tbl_posto_linha
                INNER JOIN tbl_linha ON tbl_linha.linha = tbl_posto_linha.linha AND tbl_linha.fabrica = $login_fabrica
                INNER JOIN tbl_tabela ON tbl_tabela.tabela = tbl_posto_linha.tabela AND tbl_tabela.fabrica = $login_fabrica
                WHERE tbl_posto_linha.posto = $posto
                ORDER BY tbl_linha.nome,tbl_tabela.sigla_tabela";
                $resw= pg_query($con,$sql);

                $contadorresW = pg_num_rows($resw);

                if(pg_num_rows($res)>0){
                        fputs($fp , utf8_encode(" \r\n  Linhas "));
                        fputs($fp , utf8_encode(" \r\n  Linha \t Sigla da tabela \r\n "));

                        for($w=0; $w < $contadorresW; $w++){
                            $linha = pg_fetch_result($resw, $w,"nome" );
                            $sigla_tabela = pg_fetch_result($resw, $w,"sigla_tabela" );
                            fputs($fp , utf8_encode("  $linha \t $sigla_tabela \r\n "));
                        }

                }


            $sql = "SELECT parametros_adicionais FROM tbl_posto_fabrica WHERE posto = {$posto} AND fabrica = {$login_fabrica}";
            $res = pg_query($con, $sql);

            if (pg_num_rows($res) > 0) {
                $parametrosAdicionais = json_decode(pg_fetch_result($res, 0, "parametros_adicionais"), true);
                fputs($fp , utf8_encode(" \r\n  Atestado de Capacitação"));
                fputs($fp , utf8_encode(" \r\n  Capacitação \t Validade \r\n "));

                foreach ($parametrosAdicionais as $key => $value) {
                    $key = preg_replace("/data_capacitacao_/", "", $key);
                    fputs($fp , utf8_encode("  $key \t $value \r\n "));
                }
            }


    ?>
    <tr><td>&nbsp;</td></tr>
    <tr>
        <td>
            <?
            fclose ($fp);
            flush();

            echo `cd $path_tmp; rm -rf $arquivo_nome.zip; zip -o $arquivo_nome.zip $arquivo_nome > /dev/null ; mv  $arquivo_nome.zip $path `;
            echo "<br><p id='id_download2'><a href='xls/$arquivo_nome.zip'><img src='../imagens/excel.gif'><br><font color='#3300CC'>".traduz("Fazer download do relatório do posto")."</font></a></p><br>";
            ?>

        </td>
    </tr>
    <tr><td>&nbsp;</td></tr>

</div>
<?      }
     }
} ?>
</table>

<? // <form name="frm_login" method="post" target="_blank" action="../index.php"> ?>
<form name='frm_login' method='post' target='_blank' action='../index.php?ajax=sim&acao=validar&redir=sim'>
<input type="hidden" name="login">
<input type="hidden" name="senha">
<input type="hidden" name="btnAcao" value='<?=traduz("Enviar")?>'>
</form>
<p><?php

if ($_GET ['listar'] == 'todos') {

      // gera nome xls
      if (in_array($login_fabrica,[1,3,11,160,187,188,189,194,203]) || $telecontrol_distrib) {

        $data = date ("d-m-Y-H-i");

        $arquivo_nome = "relatorio_todos_postos-$data.csv";

        if (strtolower($_serverEnvironment) == 'development') {
            $path         = "/home/bicalleto/public_html/PosVenda/admin/xls/";
            $path_tmp     = "/home/bicalleto/public_html/PosVenda/arquivosXLSTMP/";
        } else {
            $path         = "/var/www/assist/www/admin/xls/";
            $path_tmp     = "/tmp/assist/";            
        }


        //Para Gerar o CSV no devel, crie uma pasta local para os files tmp e 
        //dê o chmod 777 NA NOVA PASTA DE TMP para permissao de R/W 
        
        //ex:
            #$path         = "/home/williamcastro/public_html/PosVendaNovo/admin/xls/";
            #$path_tmp     = "/home/williamcastro/public_html/PosVendaNovo/testeWilliam/";
        
        $arquivo_completo     = $path.$arquivo_nome;
        $arquivo_completo_tmp = $path_tmp.$arquivo_nome;

        echo `rm $arquivo_completo_tmp `;
        echo `rm $arquivo_completo_tmp.zip `;
        echo `rm $arquivo_completo.zip `;
        echo `rm $arquivo_completo `;

        $fp = fopen ($arquivo_completo_tmp,"w");
        
        if($login_fabrica == 3){
            fputs ($fp, utf8_encode("NOME ; CÓDIGO ; CNPJ  I.E. ; FONE ; FAX ; CONTATO ; ENDEREÇO ; NÚMERO ; COMPLEMENTO ; BAIRRO ; CEP ; CIDADE ; ESTADO ; E-MAIL ; TIPO ; CREDENCIAMENTO ; PEDIDO FATURADO ; PEDIDO EM GARANTIA ; DIGITA OS ; PRESTAÇÃO DE SERVIÇO ; PEDIDO VIA DISTRIBUIDOR ; \r\n"));
        }else{

            fputs ($fp, "NOME ;");

            if (in_array($login_fabrica, [11,160,187,188,189]) || $telecontrol_distrib) {

                fputs ($fp, "FANTASIA ;");
            } 

            //HD - 6857791
            if($login_fabrica == 1){
                $tipo_posto_alteracao   = 'DATA ALTERACAO TIPO;';
                $reembolso_alteracao    = 'DATA ALTERACAO REEMBOLSO;';
                $data_credenciamento    = 'DATA CREDENCIAMENTO;';
                $data_descredenciamento =  'DATA DESCREDENCIAMENTO;';
                $data_emdrescredenciamento = 'DATA EM DESCREDENCIAMENTO;';
                $qtd_os_seis_meses        = 'OS ÚLTIMOS 6 MESES;';
                $valor_pedidos_seis_meses = 'PEDIDOS ÚLTIMOS 6 MESES;';
            }

            fputs ($fp, "CÓDIGO ; CNPJ ; FONE ; FAX ; CONTATO ; ENDEREÇO ; NÚMERO ; COMPLEMENTO ; BAIRRO ; CEP ; CIDADE ; ESTADO ; E-MAIL ; CATEGORIA POSTO ; TAXA ADMINISTRATIVA ; ATENDENTE DE CALL CENTER ; TIPO DE POSTO ; $tipo_posto_alteracao STATUS DO POSTO ; PEDIDO FATURADO ; PEDIDO EM GARANTIA ; COLETA DE PEÇAS ;");
            
            if ($login_fabrica == 1) {

                fputs ($fp, " RECEBE TAXA ADM ;"); 
            }

            fputs ($fp, " REEMBOLSO DE PEÇAS DO ESTOQUE ; $reembolso_alteracao  DIGITA OS ; PRESTAÇÃO DE SERVIÇO ; PEDIDO VIA DISTRIBUIDOR ; DIVULGA CONSUMIDOR ; ATENDE SOMENTE REVENDA ; CONTRIBUINTE ; OPÇÃO DE EXTRATO ; DATA AUTORIZAÇÃO ; RESPONSAVEL ; TIPO DE ENVIO DE NF ; ENDEREÇO COBRANÇA ; NUMERO COBRANÇA ; COMPLEMENTO COBRANÇA ; BAIRRO COBRANÇA ; CEP COBRANÇA ; CIDADE COBRANÇA ; ESTADO COBRANÇA ; CPF/CNPJ FAVORECIDO ; NOME FAVORECIDO ; BANCO ; TIPO CONTA ; AGENCIA ; CONTA ; OBS CONTA ; OBS POSTO ; OBS LINHAS/TABELAS ; ");

            if ($login_fabrica == 1) {
                 fputs($fp, "$data_credenciamento $data_descredenciamento $data_emdrescredenciamento $qtd_os_seis_meses $valor_pedidos_seis_meses");
            }

            if (in_array($login_fabrica, [11,160,187,188,189]) || $telecontrol_distrib) {
                
                fputs ($fp, " REGIAO SUFRAMA ; ITEM DE APARÊNCIA ; TRANSPORTADORA ; CAPITAL/INTERIOR ; ENDEREÇO COMPLETO ; PRESTACAO SERVICO ; REEMBOLSO PECA ESTOQUE ; COLETA PECA ; DESCONTO ACESSÓRIO ; ATENDE COMGAS ; GARANTIA ANTECIPADA ; IMPRIME OS ; ESCOLHE CONDICAO ; CONDICAO ESCOLHIDA ; PRESTACAO SERVICO SEM MO ; ATENDE CONSUMIDOR ; ATENDE PEDIDO FATURADO PARCIAL ; PEDIDO BONIFICACAO ; FILIAL ; CONTROLA ESTOQUE ; CONTRUIBUINTE ICMS ; ACRESCIMO TRIBUTARIO ; ENTREGA TÉCNICA ; CONTROLE ESTOQUE MANUAL ; PERMITE ENVIO PRODUTO ; TIPO ATENDE; DATA DO CREDENCIAMENTO; LINHA "); 
                    
            }

            fputs ($fp, "\r\n");
        }

    }
    // fim gera nome xls tbl_posto_fabrica.cobranca_endereco   ,
    $varLinha  = "";
    $joinLinha = "";
                            #and tbl_posto_linha.ativo is true
                            #
    if (in_array($login_fabrica, [11,160,187,188,189]) || $telecontrol_distrib) {
        $varLinha  = "tbl_linha.nome AS linha_posto,";
        $joinLinha = " INNER JOIN    tbl_posto_linha   
                            ON tbl_posto_linha.posto = tbl_posto.posto 
                            and tbl_posto_linha.ativo is true
                       INNER JOIN    tbl_linha
                            ON tbl_linha.linha = tbl_posto_linha.linha
                            AND tbl_linha.fabrica = $login_fabrica "; 
    }

    $sql = "SELECT  DISTINCT
                    $varLinha
                    tbl_posto.posto                           ,
                    tbl_posto.cnpj                            ,
                    regexp_replace(tbl_posto.contato, E'[\\r\\n\\t]+', ' ', 'g' ) as contato                               ,
                    tbl_posto.ie ,                    
                    (SELECT  TO_CHAR(data, 'DD/MM/YYYY HH24:MI') FROM  tbl_credenciamento 
                     WHERE  posto   = tbl_posto.posto 
                     AND    fabrica = tbl_posto_fabrica.fabrica 
                     AND    status  = 'CREDENCIADO'   order by credenciamento desc limit 1) AS credenciado,
                    (SELECT  TO_CHAR(data, 'DD/MM/YYYY HH24:MI') FROM  tbl_credenciamento 
                     WHERE  posto   = tbl_posto.posto  
                     AND    fabrica = tbl_posto_fabrica.fabrica
                     AND    status  = 'DESCREDENCIADO'  order by credenciamento desc limit 1) AS descredenciado,
                     (SELECT TO_CHAR(data, 'DD/MM/YYYY HH24:MI') FROM  tbl_credenciamento 
                     WHERE  posto   = tbl_posto.posto  
                     AND    fabrica = tbl_posto_fabrica.fabrica
                     AND    status  = 'EM DESCREDENCIAMENTO'  order by credenciamento desc limit 1) AS emdescrendenciamento,
                    tbl_posto_fabrica.contato_cidade  AS cidade,
                    tbl_posto_fabrica.contato_estado  AS estado,
                    tbl_posto_fabrica.contato_endereco       AS endereco,
                    tbl_posto_fabrica.contato_numero         AS numero,
                    tbl_posto_fabrica.contato_complemento    AS complemento,
                    tbl_posto_fabrica.contato_bairro         AS bairro,
                    tbl_posto_fabrica.contato_cep            AS cep,
                    tbl_posto_fabrica.contato_email          AS email,
                    tbl_posto_fabrica.contato_fone_comercial AS fone,
                    tbl_posto_fabrica.contato_fone_residencial AS fone2,
                    tbl_posto_fabrica.contato_fax            AS fax,
                    tbl_posto_fabrica.contato_nome           AS contato_nome,
                    tbl_posto.nome                            ,
                    tbl_posto.nome_fantasia                   ,
                    tbl_posto.pais                            ,
                    tbl_posto_fabrica.codigo_posto            ,
                    tbl_tipo_posto.descricao                  ,
                    tbl_posto.capital_interior                ,
                    tbl_posto_fabrica.pedido_faturado         ,
                    tbl_posto_fabrica.pedido_em_garantia      ,
                    tbl_posto_fabrica.coleta_peca             ,
                    tbl_posto_fabrica.reembolso_peca_estoque  ,
                    tbl_posto_fabrica.digita_os               ,
                    tbl_posto_fabrica.controla_estoque        ,
                    tbl_posto_fabrica.prestacao_servico       ,
                    tbl_posto_fabrica.divulgar_consumidor     ,
                    tbl_posto_fabrica.tipo_atende             ,
                    tbl_posto_fabrica.prestacao_servico_sem_mo,
                    tbl_posto_fabrica.pedido_via_distribuidor ,
                    tbl_posto_fabrica.pedido_bonificacao      ,
                    tbl_posto_fabrica.credenciamento          ,
                    tbl_posto_fabrica.categoria               ,
                    tbl_posto_fabrica.admin_sap,
                    tbl_posto_fabrica.cpf_conta,
                    tbl_posto_fabrica.favorecido_conta    ,
                    tbl_posto_fabrica.tipo_conta          ,
                    tbl_posto_fabrica.agencia             ,
                    tbl_posto_fabrica.conta               ,
                    regexp_replace(tbl_posto_fabrica.obs_conta, E'[\\n\\r]+', ' ', 'g' ) as obs_conta,
                    fn_retira_especiais(regexp_replace(tbl_posto_fabrica.obs, E'[\\n\\r]+', ' ', 'g' )) as obs,
                    tbl_posto_fabrica.banco as codigo_banco,
                    tbl_posto_fabrica.nomebanco AS nome_banco               ,
                    tbl_posto_fabrica.cobranca_endereco   ,
                    tbl_posto_fabrica.cobranca_numero     ,
                    tbl_posto_fabrica.cobranca_complemento,
                    tbl_posto_fabrica.cobranca_bairro     ,
                    tbl_posto_fabrica.cobranca_cep        ,
                    tbl_posto_fabrica.cobranca_cidade     ,
                    tbl_posto_fabrica.cobranca_estado     ,
                    TO_CHAR(tbl_posto_fabrica.contrato,'DD/MM/YYYY HH24:MI')    as contrato,
                    TO_CHAR(tbl_posto_fabrica.atualizacao,'DD/MM/YYYY HH24:MI') as atualizacao,
                    tbl_tipo_gera_extrato.responsavel,
                    tbl_tipo_gera_extrato.tipo_envio_nf,
                    tbl_intervalo_extrato.descricao as intervalo_extrato,
                    TO_CHAR(tbl_tipo_gera_extrato.data_atualizacao, 'dd/mm/YYYY hh24:ii:ss') AS data_atualizacao,
                    tbl_tipo_gera_extrato.intervalo_extrato    ,
                    tbl_excecao_mobra.tx_administrativa        ,
                    tbl_posto.xxxsuframa                       ,
                    tbl_posto.suframa                          ,
                    tbl_posto.item_aparencia                   ,               
                    tbl_posto_fabrica.prestacao_servico        ,
                    tbl_posto_fabrica.desconto_acessorio       ,
                    tbl_posto_fabrica.atende_comgas            ,
                    tbl_posto_fabrica.garantia_antecipada      ,
                    tbl_posto_fabrica.imprime_os               ,
                    tbl_posto_fabrica.escolhe_condicao         ,
                    tbl_posto_fabrica.condicao_escolhida       ,
                    tbl_posto_fabrica.atende_consumidor        ,
                    tbl_posto_fabrica.atende_pedido_faturado_parcial ,
                    tbl_posto_fabrica.filial                   ,
                    tbl_posto_fabrica.contribuinte_icms        ,
                    tbl_posto_fabrica.acrescimo_tributario     ,
                    tbl_posto_fabrica.entrega_tecnica          ,
                    tbl_posto_fabrica.controle_estoque_manual  ,
                    tbl_posto_fabrica.permite_envio_produto    ,
                    regexp_replace(tbl_posto_fabrica.parametros_adicionais, E'[\\r\\n]+', ' ', 'g' ) as parametros_adicionais
            FROM    tbl_posto
            JOIN    tbl_posto_fabrica       USING (posto)
            $joinLinha
       LEFT JOIN    tbl_tipo_posto          ON  tbl_posto_fabrica.tipo_posto            = tbl_tipo_posto.tipo_posto
       LEFT JOIN    tbl_tipo_gera_extrato   ON  tbl_posto_fabrica.fabrica               = tbl_tipo_gera_extrato.fabrica
                                            AND tbl_posto_fabrica.posto                 = tbl_tipo_gera_extrato.posto
       LEFT JOIN    tbl_intervalo_extrato   ON  tbl_intervalo_extrato.fabrica           = tbl_posto_fabrica.fabrica
                                            AND tbl_intervalo_extrato.intervalo_extrato = tbl_tipo_gera_extrato.intervalo_extrato
       LEFT JOIN    tbl_empresa_cliente     ON  tbl_posto.posto                         = tbl_empresa_cliente.posto
                                            AND tbl_empresa_cliente.fabrica             = tbl_posto_fabrica.fabrica
       LEFT JOIN    tbl_excecao_mobra       ON  tbl_excecao_mobra.posto                 = tbl_posto_fabrica.posto
                                            AND tbl_excecao_mobra.fabrica               = tbl_posto_fabrica.fabrica
            WHERE   tbl_posto_fabrica.fabrica = $login_fabrica"; 

    if ($login_fabrica == 20) {
        if ($login_admin == (590) OR $login_admin == (364) OR $login_admin == (588)) $sql .= " AND 1 = 1 ";
        else $sql .= "AND tbl_posto.pais = 'BR'";
        $sql .=" ORDER BY tbl_posto.pais,tbl_posto_fabrica.credenciamento, tbl_posto.nome";
    } else {
        $sql .=" ORDER BY tbl_posto_fabrica.credenciamento, tbl_posto.nome";
    }

    $res = pg_query ($con,$sql);

    if (pg_num_rows($res) > 0) {
        if (in_array($login_fabrica,array(1, 3,11,123,160,187,188,189,194,203)) || $telecontrol_distrib) {

            //if ($login_fabrica == 1) {

                for ($i = 0;$i<pg_num_rows($res);$i++) {

                    if (pg_fetch_result($res,$i,'admin_sap') != "") {
                        $idAdmin = pg_fetch_result($res,$i,'admin_sap');
                        $sqlAtendente = "SELECT nome_completo FROM tbl_admin WHERE fabrica = $login_fabrica AND admin = $idAdmin;";
                        $resAtendente = pg_query($con,$sqlAtendente);
                        $atendente = pg_fetch_result($resAtendente, 0, 'nome_completo');

                    } else {
                        $atendente = "";
                    }
                    $posto                   =  pg_fetch_result($res, $i, 'posto');
                    $pedido_faturado         = (pg_fetch_result($res, $i, 'pedido_faturado') =='t')         ? "Sim" : "Nao";
                    $pedido_em_garantia      = (pg_fetch_result($res, $i, 'pedido_em_garantia') =='t')      ? "Sim" : "Nao";
                    $digita_os               = (pg_fetch_result($res, $i, 'digita_os') =='t')               ? "Sim" : "Nao";
                    $controla_estoque        = (pg_fetch_result($res, $i, 'controla_estoque') =='t')        ? "Sim" : "Nao";
                    $prestacao_servico       = (pg_fetch_result($res, $i, 'prestacao_servico') =='t')       ? "Sim" : "Nao";
                    $pedido_via_distribuidor = (pg_fetch_result($res, $i, 'pedido_via_distribuidor') =='t') ? "Sim" : "Nao";
                    
                    $divulga_consumidor = pg_fetch_result($res, $i, 'divulgar_consumidor');
                    $divulga_consumidor = ($divulga_consumidor =='t') ? "Sim" : "Nao";

                    $atende_somente_revenda = (pg_fetch_result($res, $i, 'tipo_atende') =='t') ? "Sim" : "Nao";
   

                    $pedido_bonificacao = (pg_fetch_result($res, $i, 'pedido_bonificacao') =='t') ? "Sim" : "Não";
                    $reembolso_peca_estoque = (pg_fetch_result($res, $i, 'reembolso_peca_estoque') =='t') ? "Sim" : "Nao";
                    $coleta_peca = (pg_fetch_result($res, $i, 'coleta_peca') =='t') ? "Sim" : "Nao";

                    $categoria_posto = pg_fetch_result($res,$i,'categoria');
                    if ($login_fabrica == 1 && $categoria_posto == 'mega projeto') {
                        $categoria_posto = 'Industria/Mega Projeto';
                    }
                    $complemento = pg_fetch_result($res,$i,'complemento');
                    $bairro      = pg_fetch_result($res,$i,'bairro');
                    $cep         = pg_fetch_result($res,$i,'cep');
                    $cidade      = pg_fetch_result($res,$i,'cidade');
                    $estado      = pg_fetch_result($res,$i,'estado');
                    $email       = pg_fetch_result($res,$i,'email');
                    $descricao   = pg_fetch_result($res,$i,'descricao');
                    $credenciamento = pg_fetch_result($res,$i,'credenciamento');

                    $dt_credenciamento = pg_fetch_result($res,$i,'credenciado');
                    $dt_descredenciamento = pg_fetch_result($res,$i,'descredenciado');
                    $dt_em_descredenciamento  = pg_fetch_result($res,$i,'emdescrendenciamento');

                    if($login_fabrica == 1){

                        if($credenciamento == 'Descred apr'){
                            $credenciamento = "DESCREDENCIAMENTO - APROVADO";
                        }
                        elseif($credenciamento == 'Descred rep'){
                            $credenciamento = "DESCREDENCIAMENTO - REPROVADO";
                        }
                        elseif($credenciamento == 'Pr&eacute; Cad apr'){
                            $credenciamento = "PRÉ CADASTRO - APROVADO";
                        }
                        elseif($credenciamento == 'Pr&eacute; Cad rpr'){
                            $credenciamento = "PRÉ CADASTRO - REPROVADO";
                        }
                    }

                    $contato     = utf8_encode(pg_fetch_result($res,$i,'contato_nome'));
                    $endereco    = pg_fetch_result($res,$i,'endereco');
                    $numero      = pg_fetch_result($res,$i,'numero');
                    $nome        = pg_fetch_result($res,$i,'nome');
                    $codigo_posto= pg_fetch_result($res,$i,'codigo_posto');
                    $tipo_envio_nfe = pg_fetch_result($res,$i,'tipo_envio_nf');
                    $cpf_conta   = pg_fetch_result($res,$i,'cpf_conta');
                    $favorecido_conta = pg_fetch_result($res,$i,'favorecido_conta');
                    $codigo_banco= pg_fetch_result($res,$i,'codigo_banco');
                    $nome_banco  = pg_fetch_result($res,$i,'nome_banco');
                    $tipo_conta  = pg_fetch_result($res,$i,'tipo_conta');
                    $agencia     = pg_fetch_result($res,$i,'agencia');
                    $conta       = pg_fetch_result($res,$i,'conta');
                    $obs_conta   = pg_fetch_result($res,$i,'obs_conta');
                    $obs         = pg_fetch_result($res,$i,'obs');
                    $cobranca_endereco        = trim(pg_fetch_result($res,$i, 'cobranca_endereco'));
                    $cobranca_numero          = trim(pg_fetch_result($res,$i, 'cobranca_numero'));
                    $cobranca_complemento     = trim(pg_fetch_result($res,$i, 'cobranca_complemento'));
                    $cobranca_bairro          = trim(pg_fetch_result($res,$i, 'cobranca_bairro'));
                    $cobranca_cep             = trim(pg_fetch_result($res,$i, 'cobranca_cep'));
                    $cobranca_cidade          = trim(pg_fetch_result($res,$i, 'cobranca_cidade'));
                    $cobranca_estado          = trim(pg_fetch_result($res,$i, 'cobranca_estado'));

                    $parametros_adicionais_obs= json_decode(pg_fetch_result($res,$i, 'parametros_adicionais'));
                    $taxa_adm_black = $parametros_adicionais_obs->recebeTaxaAdm;
                    
                    $tipo_contribuinte = $parametros_adicionais_obs->tipo_contribuinte;
                    
                    if ($login_fabrica == 1) {

                        $pedido_faturado_locadora = $parametros_adicionais->pedido_faturado_locadora;
                        $pedido_faturado_locadora = ($pedido_faturado_locadora =='t') ? "Sim" : "Nao";
                    }

                    //HD 6857791
                    if ($login_fabrica == 1) {
                        $tipo_posto_data_alteracao  = mostra_data($parametros_adicionais_obs->tipo_posto_data_alteracao);
                        $reembolso_peca_estoque_data_alteracao = mostra_data($parametros_adicionais_obs->reembolso_peca_estoque_data_alteracao);  
                    }    

                    $tipo_contribuinte = ($tipo_contribuinte =='t') ? "Sim" : "Nao";                   
                    $obs_posto_cadastrado = str_replace("\r\n",' ',$parametros_adicionais_obs->obs_posto_cadastrado);
                    
                    $taxa_adm = pg_fetch_result($res,$i,'tx_administrativa');
                    $taxa_adm = (strlen($taxa_adm) > 0) ? $taxa_adm : 0;
                    $taxa_adm = ($taxa_adm > 0) ? ($taxa_adm - 1) * 100 : 0;

                    $nome = str_replace(";","",$nome);
                    $nome = str_replace(",","-",$nome);

                    fputs($fp,str_replace(";","",$nome)." ;");
                    
                    if (in_array($login_fabrica,[11,160,187,188,189]) || $telecontrol_distrib) {

                        $nome_fantasia = pg_fetch_result($res,$i,'nome_fantasia');
                        $nome_fantasia = str_replace(";","",$nome_fantasia);
                        
                        fputs($fp, $nome_fantasia . " ;");
                    }

                    fputs($fp,str_replace(";","",$codigo_posto)." ;");
                    fputs($fp,pg_fetch_result($res,$i,'cnpj')."\0 ;");
                    fputs($fp,pg_fetch_result($res,$i,'fone')." ;");
                    
                    $fax = pg_fetch_result($res,$i,'fax');
                    $fax = str_replace(";"," ",$fax);
                    $fax = str_replace(","," ",$fax); 
                    
                    fputs($fp, $fax . " ;");

                    $contato = str_replace(";"," ",$contato);
                    $contato = str_replace(","," ",$contato); 
                    
                    fputs($fp,str_replace(";","",$contato)." ;");

                    $endereco = str_replace(";","",$endereco);
                    $endereco = str_replace(",","",$endereco);

                    fputs($fp,$endereco ." ;");
                    fputs($fp,str_replace(";","",$numero)." ;");
                    
                    $complemento = str_replace(";","",$complemento);
                    $complemento = str_replace(",","",$complemento);

                    fputs($fp,str_replace(";","",$complemento)." ;");
                    fputs($fp,str_replace(";","",$bairro)." ;");
                    fputs($fp,str_replace(";","",$cep)." ;");
                    fputs($fp,str_replace(";","",$cidade)." ;");
                    fputs($fp,str_replace(";","",$estado)." ;");
                    fputs($fp,str_replace(";","",$email)." ;");
					if(!in_array($login_fabrica,[3])) {
						fputs($fp,str_replace(";","",html_entity_decode($categoria_posto))." ;");
						fputs($fp,number_format($taxa_adm,2,',','.') ." ;");
						fputs($fp,$atendente ." ;");
					}
                    fputs($fp,str_replace(";","",$descricao)." ;");
                    if ($login_fabrica == 1 ){
                         
                          fputs($fp,str_replace(";","",$tipo_posto_data_alteracao)." ;");
                     }
                    fputs($fp,$credenciamento ." ;");
                    fputs($fp,$pedido_faturado ." ;");
                    fputs($fp,$pedido_em_garantia ." ;");
					if(!in_array($login_fabrica,[3])) {
						fputs($fp,$coleta_peca ." ;");
					}
                    if ($login_fabrica == 1){
                        fputs($fp,$taxa_adm_black ." ;"); // RECEBE TAXA ADM

                    }

					if(!in_array($login_fabrica,[3])) {
						fputs($fp,$reembolso_peca_estoque." ;");
					}

                    if ($login_fabrica == 1) {
                        
                         fputs($fp,str_replace(";","",$reembolso_peca_estoque_data_alteracao)." ;");
                    }

                    fputs($fp,$digita_os." ;");
                    fputs($fp,$prestacao_servico." ;");
                    fputs($fp,$pedido_via_distribuidor." ;");
                
					if(!in_array($login_fabrica,[3])) {
						fputs($fp, $divulga_consumidor ." ;"); 

						$atende_somente_revenda = "";

						fputs($fp,$atende_somente_revenda ." ;");
						fputs($fp,$tipo_contribuinte." ;");
						fputs($fp,$intervalo_extrato." ;");

						fputs($fp,pg_result($res,$i,'data_atualizacao')." ;");

						fputs($fp,pg_result($res,$i,'responsavel')." ;");

						fputs($fp,$tipo_envio_nfe." ;");

						$cobranca_endereco = str_replace(";","",$cobranca_endereco);
						$cobranca_endereco = str_replace(",","",$cobranca_endereco);

						fputs($fp,str_replace(";","",$cobranca_endereco)." ;");

						fputs($fp,str_replace(";","",$cobranca_numero)." ;");

						$cobranca_complemento = str_replace(";","",$cobranca_complemento);
						$cobranca_complemento = str_replace(",","",$cobranca_complemento);

						fputs($fp,str_replace(";","",$cobranca_complemento)." ;");
						fputs($fp,str_replace(";","",$cobranca_bairro)." ;");
						fputs($fp,str_replace(";","",$cobranca_cep)." ;");
						fputs($fp,str_replace(";","",$cobranca_cidade)." ;");

						$cobranca_estado = str_replace(","," ",$cobranca_estado);
						$cobranca_estado = str_replace(";"," ",$cobranca_estado);
						$cobranca_estado = str_replace(":"," ",$cobranca_estado);

						fputs($fp,$cobranca_estado."\t" );

						fputs($fp, "\t");

						fputs($fp, $cobranca_estado . " ;");

						fputs($fp,str_replace(";","",$cpf_conta)." ;");
						fputs($fp,str_replace(";","",$favorecido_conta)." ;");
						fputs($fp,str_replace(";","",$nome_banco)." ;");
						fputs($fp,str_replace(";","",$tipo_conta)." ;");
						fputs($fp,str_replace(";","",$agencia)." ;");

						$conta = str_replace(":",'',$conta);
						$conta = str_replace(";",'',$conta);
						$conta = str_replace(",",'',$conta);

						fputs($fp,str_replace(";","",$conta)." ;");

						$obs_conta = str_replace(array(":"),'',$obs_conta);
						$obs_conta = str_replace(array(";"),'',$obs_conta);
						$obs_conta = str_replace(array(","),'',$obs_conta);
						$obs_conta = str_replace(array("\t"),'',$obs_conta);
						$obs_conta = str_replace(array("\r\n"),'',$obs_conta);
						$obs_conta = str_replace(array(""),'',$obs_conta);

						fputs($fp, $obs_conta ." ;");

						$obs = str_replace(array(":"),'',$obs);
						$obs = str_replace(array(";"),'',$obs);
						$obs = str_replace(array(","),'',$obs);
						$obs = str_replace(array("\t"),'',$obs);
						$obs = str_replace(array("\r\n"),'',$obs);
						$obs = str_replace(array(""),'',$obs);

						fputs($fp, $obs ." ;");

						$obs_posto_cadastrado = str_replace(array(":"),'',$obs_posto_cadastrado);
						$obs_posto_cadastrado = str_replace(array(";"),'',$obs_posto_cadastrado);
						$obs_posto_cadastrado = str_replace(array(","),'',$obs_posto_cadastrado);
						$obs_posto_cadastrado = str_replace(array("\t"),'',$obs_posto_cadastrado);
						$obs_posto_cadastrado = str_replace(array("\r\n"),'',$obs_posto_cadastrado);
						$obs_posto_cadastrado = str_replace(array(""),'', $obs_posto_cadastrado);

						fputs($fp, $obs_posto_cadastrado ." ;");
					}
                    
                    if ($login_fabrica == 1 ) {

                        // Busca a quantidade de OS dos últimos 6 meses
                        $sql_os = "SELECT COUNT(tbl_os_extra.os) as qtde_os
                                    FROM tbl_os_extra
                                    JOIN tbl_extrato USING(extrato)
                                    WHERE tbl_extrato.fabrica = {$login_fabrica}
                                    AND tbl_extrato.posto = {$posto}
                                    AND tbl_extrato.data_geracao::date >= CURRENT_DATE - INTERVAL '6 MONTHS'";

                        $res_os = pg_query($con, $sql_os);
                        $qtd_os = pg_fetch_result($res_os, 0, 'qtde_os');


                        // Busca o valor total dos pedidos dos últimos 6 meses
                        $sql_pedidos = "SELECT SUM(tbl_pedido.total) as valor_total_pedidos
                                        FROM tbl_pedido
                                        WHERE fabrica = {$login_fabrica}
                                        AND tbl_pedido.posto = {$posto}
                                        AND tbl_pedido.finalizado::date >= CURRENT_DATE - INTERVAL '6 MONTHS'
                                        AND tbl_pedido.status_pedido NOT IN(1,14)";

                        $res_pedidos = pg_query($con, $sql_pedidos);
                        $valor_pedidos = pg_fetch_result($res_pedidos, 0, 'valor_total_pedidos');

                        if($valor_pedidos){
                            $valor_pedidos = number_format($valor_pedidos,2,',','.');
                        }

                        fputs($fp,str_replace(";","",$dt_credenciamento)." ;");
                        fputs($fp,str_replace(";","",$dt_descredenciamento)." ;");
                        fputs($fp,str_replace(";","",$dt_em_descredenciamento)." ;");
                        fputs($fp,str_replace(";","",$qtd_os)." ;");
                        fputs($fp,str_replace(";","",$valor_pedidos)." ;");

                     }

                    if (in_array($login_fabrica,[11,160,187,188,189]) || $telecontrol_distrib) {

                        //$regiao_suframa = utf8_encode(pg_fetch_result($res,$i,'xxxsuframa'));

                        $regiao_suframa = utf8_encode(pg_fetch_result($res,$i,'suframa'));
                        $regiao_suframa = ($regiao_suframa =='t') ? "Sim" : "Nao";

                        $item_aparencia = utf8_encode(pg_fetch_result($res,$i,'item_aparencia'));
                        $item_aparencia = ($item_aparencia =='t') ? "Sim" : "Nao";

                        $transportadora = pg_fetch_result($res,$i,'trans_nome');
                            
                        $capital_interior = utf8_encode(pg_fetch_result($res,$i,'capital_interior'));

                        $endereco_completo = $cobranca_endereco    . " " . 
                                             $cobranca_numero      . " " . 
                                             $cobranca_complemento . " " . 
                                             $cobranca_bairro      . " " . 
                                             $cobranca_cep;

                        $endereco_completo = str_replace(";"," ",$endereco_completo);
                        $endereco_completo = str_replace(","," ",$endereco_completo);

                        $prestacao_servico =  
                            utf8_encode(pg_fetch_result($res,$i,'prestacao_servico'));
                        $prestacao_servico = ($prestacao_servico =='t') ? "Sim" : "Nao";

                        $reembolso_peca_estoque = 
                            utf8_encode(pg_fetch_result($res,$i,'reembolso_peca_estoque'));
                        $reembolso_peca_estoque = ($reembolso_peca_estoque =='t') ? "Sim" : "Nao";

                        $coleta_peca = 
                            utf8_encode(pg_fetch_result($res,$i,'coleta_peca'));
                        $coleta_peca = ($coleta_peca =='t') ? "Sim" : "Nao";

                        $desconto_acessorio = 
                            utf8_encode(pg_fetch_result($res,$i,'desconto_acessorio'));
                        $desconto_acessorio = ($desconto_acessorio =='t') ? "Sim" : "Nao";

                        $atende_comgas = 
                            utf8_encode(pg_fetch_result($res,$i,'atende_comgas'));
                        $atende_comgas = ($atende_comgas =='t') ? "Sim" : "Nao";

                        $garantia_antecipada = 
                            utf8_encode(pg_fetch_result($res,$i,'garantia_antecipada'));
                        $garantia_antecipada = ($garantia_antecipada =='t') ? "Sim" : "Nao";

                        $imprime_os = 
                            utf8_encode(pg_fetch_result($res,$i,'imprime_os'));
                        $imprime_os = ($imprime_os =='t') ? "Sim" : "Nao";

                        $escolhe_condicao = 
                            utf8_encode(pg_fetch_result($res,$i,'escolhe_condicao'));
                        $escolhe_condicao = ($escolhe_condicao =='t') ? "Sim" : "Nao";

                        $condicao_escolhida = pg_fetch_result($res,$i,'condicao_escolhida');
                        $condicao_escolhida = ($condicao_escolhida == 't') ? "Sim" : "Nao";

                        $prestacao_servico_sem_mo = 
                            utf8_encode(pg_fetch_result($res,$i,'prestacao_servico_sem_mo')); 
                        $coleta_peca = ($coleta_peca =='t') ? "Sim" : "Nao"; 

                        $atende_consumidor = 
                            utf8_encode(pg_fetch_result($res,$i,'atende_consumidor'));
                        $atende_consumidor = ($atende_consumidor =='t') ? "Sim" : "Nao";

                        $atende_pedido_faturado_parcial = 
                            utf8_encode(pg_fetch_result($res,$i,'atende_pedido_faturado_parcial'));
                        $atende_pedido_faturado_parcial = ($atende_pedido_faturado_parcial =='t') ? "Sim" : "Nao";

                        $pedido_bonificacao = 
                            utf8_encode(pg_fetch_result($res,$i,'pedido_bonificacao'));
                        $pedido_bonificacao = ($pedido_bonificacao =='t') ? "Sim" : "Nao";            
                        $filial = 
                            utf8_encode(pg_fetch_result($res,$i,'filial'));
                        $filial = ($filial =='t') ? "Sim" : "Nao"; 

                        $controla_estoque = 
                            utf8_encode(pg_fetch_result($res,$i,'controla_estoque')); 
                        $controla_estoque = ($controla_estoque =='t') ? "Sim" : "Nao"
                        ;  

                        $contribuinte_icms = 
                            utf8_encode(pg_fetch_result($res,$i,'contribuinte_icms'));  
                        $contribuinte_icms = ($contribuinte_icms =='t') ? "Sim" : "Nao";

                        $acrescimo_tributario = 
                            utf8_encode(pg_fetch_result($res,$i,'acrescimo_tributario'));
                        
                        $entrega_tecnica = 
                            utf8_encode(pg_fetch_result($res,$i,'entrega_tecnica'));
                        $entrega_tecnica = ($entrega_tecnica =='t') ? "Sim" : "Nao";

                        $controle_estoque_manual = 
                            utf8_encode(pg_fetch_result($res,$i,'controle_estoque_manual'));
                        $controle_estoque_manual = ($controle_estoque_manual =='t') ? "Sim" : "Nao";

                        $permite_envio_produto = 
                            utf8_encode(pg_fetch_result($res,$i,'permite_envio_produto')); 
                        $permite_envio_produto = ($permite_envio_produto =='t') ? "Sim" : "Nao";

                        $tipo_atende = 
                            utf8_encode(pg_fetch_result($res,$i,'tipo_atende '));           
                        $tipo_atende = ($tipo_atende =='t') ? "Sim" : "Nao";

                        $divulgar_consumidor = pg_fetch_result($res,$i,'divulgar_consumidor');
                        $divulgar_consumidor = ($divulgar_consumidor =='t') ? "Sim" : "Nao";

                        $linha_posto = pg_fetch_result($res,$i,'linha_posto');

                        $queryCredenciamento = "SELECT data AS data_credenciamento 
                                                FROM tbl_credenciamento      
                                                WHERE posto = $posto
                                                AND fabrica = $login_fabrica
                                                ORDER BY credenciamento DESC LIMIT 1";

                        $resCredenciamento = pg_query($con, $queryCredenciamento);

                        $data_credenciamento = pg_fetch_result($resCredenciamento, 0, data_credenciamento);

                        $data_credenciamento = date("d/m/Y", strtotime($data_credenciamento));
                        

                        fputs($fp, $regiao_suframa                ." ;");
                        fputs($fp, $item_aparencia                ." ;");
                        fputs($fp, $transportadora                ." ;");
                        fputs($fp, $capital_interior              ." ;");
                        fputs($fp, $endereco_completo             ." ;");
                        fputs($fp,$prestacao_servico              ." ;");
                        fputs($fp,$reembolso_peca_estoque         ." ;");
                        fputs($fp,$coleta_peca                    ." ;");
                        fputs($fp,$desconto_acessorio             ." ;");
                        fputs($fp,$atende_comgas                  ." ;");
                        fputs($fp,$garantia_antecipada            ." ;");
                        fputs($fp,$imprime_os                     ." ;");
                        fputs($fp,$escolhe_condicao               ." ;");
                        fputs($fp,$condicao_escolhida             ." ;");
                        fputs($fp,$prestacao_servico_sem_mo       ." ;");
                        fputs($fp,$atende_consumidor              ." ;");
                        fputs($fp,$atende_pedido_faturado_parcial ." ;");
                        fputs($fp,$pedido_bonificacao             ." ;");
                        fputs($fp,$filial                         ." ;");
                        fputs($fp,$controla_estoque               ." ;");
                        fputs($fp,$contribuinte_icms              ." ;");
                        fputs($fp,$acrescimo_tributario           ." ;");
                        fputs($fp,$entrega_tecnica                ." ;");
                        fputs($fp,$controle_estoque_manual        ." ;");
                        fputs($fp,$permite_envio_produto          ." ;");
                        fputs($fp,$tipo_atende                    ." ;");
                        fputs($fp, $data_credenciamento           ." ;");
                        fputs($fp, $linha_posto                   ." ;");
 
                    }

                    fputs($fp,"\r\n");
                }

            fclose($fp);

            echo "<br><p id='id_download2'><a href='xls/$arquivo_nome.zip' target='_blank'><img src='../imagens/excel.gif'><br><font color='#3300CC'>".traduz('Fazer download do relatório de todos os postos')."</font></a></p><br>";
            echo `cd $path_tmp; rm -rf $arquivo_nome.zip; zip -o $arquivo_nome.zip $arquivo_nome > /dev/null ; mv  $arquivo_nome.zip $path `;
        }
       
        if ($login_fabrica != 1) {

        echo "<table style='border: 0;' cellpadding='3' cellspacing='0' class='formulario'>";
                echo "<tr class='titulo_coluna'>";
                if ($login_fabrica == 20) {
                    echo "<td nowrap rowspan='2'>".traduz("País")."</td>";
                }
                echo "<td nowrap rowspan='2'>".traduz("Cidade")."</td>";
                echo "<td nowrap rowspan='2'>".traduz("Estado")."</td>";
                echo "<td nowrap rowspan='2'>".traduz("Nome")."</td>";
                if ($login_fabrica == 158) {
                    echo "<td nowrap rowspan='2'>".traduz("CNPJ")."</td>";
                }
                echo "<td nowrap rowspan='2'>".traduz("Código")."</td>";

                if ($login_fabrica == 1) {
                    echo "<td nowrap rowspan='2'>Email</td>";
                    echo "<td nowrap rowspan='2'>".traduz("Categoria Posto")."</td>";
                    echo "<td nowrap rowspan='2'>".traduz("Atendente de Call Center")."</td>";
                }

                if ($login_fabrica == 5) {
                    echo "<td nowrap rowspan='2'>".traduz("Telefone")."</td>";
                    echo "<td nowrap rowspan='2'>Email</td>";
                    echo "<td nowrap rowspan='2'>".traduz("Endereço")."</td>";
                    echo "<td nowrap rowspan='2'>".traduz("Bairro")."</td>";
                }
                if ($login_fabrica == 15) {
                    echo "<td nowrap rowspan='2'>".traduz("I.E.")."</td>";
                }
                echo "<td nowrap rowspan='2'>".traduz("Tipo")."</td>";
                if ($login_fabrica == 115) {
                    echo "<td nowrap rowspan='2'>".traduz("Categoria")."</td>";
                    echo "<td nowrap rowspan='2'>".traduz("Categoria Manual")."</td>";
                }
                echo "<td nowrap rowspan='2'>".traduz("Credenciamento")."</td>";

                if($login_fabrica == 151){
                    echo "<td rowspan='2'>".traduz("Valor Mão de Obra")."</td>";
                    echo "<td nowrap rowspan='2'>".traduz("Divulgar posto para o consumidor?")."</td>";
                }

                if ($login_fabrica == 15) {
                    echo "<td nowrap rowspan='2'>".traduz("Data Atualização")."</td>";
                }

                /*if ($login_fabrica == 158) {
                    echo "<td nowrap rowspan='2'>Preço Fixo</td>";
                }*/

                if (in_array($login_fabrica, array(25,47,81,114,123,124,125,128,136))) {
                    echo "<td nowrap rowspan='2'>".traduz("Data Contrato")."</td>";
                }
                if($login_fabrica == 35){
                    echo "<td rowspan='2'>".traduz('Controla Estoque')."</td>";
                }
                echo "<td nowrap colspan='3'>".traduz("Posto pode Digitar")."</td>";
                if ($login_fabrica == 1) {
                    echo '<td colspan="4">'.traduz("Opções de Extrato").'</td>';
                }
                echo "</tr>";
                echo "<tr class='Titulo'>";
                echo "<td>".traduz("Pedido Faturado")."</td>";
                echo "<td>".traduz("Pedido em Garantia")."</td>";
                if ($login_fabrica == 1) {
                    echo "<td>".traduz("Coleta de Peças")."</td>";
                    echo "<td>".traduz("Reembolso de Peça do Estoque")."</td>";
                    echo "<td>".traduz("Contribuinte")."</td>";
                }
                echo "<td>".traduz("Digita OS")."</td>";
                if(in_array($login_fabrica, array(1,3,10,11,15,20,24,40,42,45,46,50,59,72,80,85,88,90,91,94,96,99,101,104,115,117,121,129,131,138,141,172))) {
                    echo "<td>".traduz("Prestação de Serviço")."</td>";
                    if($login_fabrica == 20) echo "<td>".traduz("Prestação de Serviço Isenta de MO")."</td>";
                    echo "<td>".traduz("Pedido via Distribuidor")."</td>";
                }else{
                    echo "<td></td>";
                    echo "<td></td>";
                }
                if ($login_fabrica == 1) {
                    echo "<td>".traduz("Opção de Extrato")."</td>";
                    echo "<td>".traduz("Data Atualização")."</td>";
                    echo "<td>".traduz("Responsável")."</td>";
                    echo "<td>".traduz("Tipo de envio de NF")."</td>";
                }
                if($login_fabrica == 74){       echo "<td>".traduz("Pedido Bonificação")."</td>";
                    if ($cook_admin == 5973 )    echo "<td>".traduz("Data de Descredenciamento")."</td>";
                }
                echo "</tr>";
        for ($i = 0 ; $i < pg_num_rows ($res) ; $i++) {

            $posto = pg_fetch_result($res,$i,posto);

            // conteudo excel
            if ($login_fabrica == 3) {

                $pedido_faturado         = (pg_fetch_result($res, $i, 'pedido_faturado') =='t')         ? traduz("Sim") : traduz("Não");
                $pedido_faturado = utf8_encode($pedido_faturado);
                $pedido_em_garantia      = (pg_fetch_result($res, $i, 'pedido_em_garantia') =='t')      ? traduz("Sim") : traduz("Não");
                $pedido_em_garantia = utf8_encode($pedido_em_garantia);
                $digita_os               = (pg_fetch_result($res, $i, 'digita_os') =='t')               ? traduz("Sim") : traduz("Não");
                $digita_os = utf8_encode($digita_os);
                $controla_estoque        = (pg_fetch_result($res, $i, 'controla_estoque') =='t')        ? traduz("Sim") : traduz("Não");
                $controla_estoque = utf8_encode($controla_estoque);
                $prestacao_servico       = (pg_fetch_result($res, $i, 'prestacao_servico') =='t')       ? traduz("Sim") : traduz("Não");
                $prestacao_servico = utf8_encode($prestacao_servico);
                $pedido_via_distribuidor = (pg_fetch_result($res, $i, 'pedido_via_distribuidor') =='t') ? traduz("Sim") : traduz("Não");
                $pedido_via_distribuidor = utf8_encode($pedido_via_distribuidor);
                $pedido_bonificacao = (pg_fetch_result($res, $i, 'pedido_bonificacao') =='t') ? traduz("Sim") : traduz("Não");
                $pedido_bonificacao = utf8_encode($pedido_bonificacao);
                $reembolso_peca_estoque = (pg_fetch_result($res, $i, 'reembolso_peca_estoque') =='t') ? traduz("Sim") : traduz("Não");
                $reembolso_peca_estoque = utf8_encode($reembolso_peca_estoque);
                $coleta_peca = (pg_fetch_result($res, $i, 'coleta_peca') =='t') ? traduz("Sim") : traduz("Não");
                $coleta_peca = utf8_encode($coleta_peca);
                $intervalo_extrato = utf8_encode(pg_fetch_result($res,$i, 'intervalo_extrato'));
                $nome = utf8_encode(str_replace(",", "", pg_fetch_result($res,$i,'nome')));
                $descricao = utf8_encode(pg_fetch_result($res,$i,'descricao'));
                $categoria = utf8_encode(urldecode(str_replace("&eacute", "é", str_replace(array(",", ";"), "", pg_fetch_result($res,$i,'categoria')))));
                $contato = utf8_encode(str_replace(",", "", pg_fetch_result($res,$i,'contato_nome')));
                $endereco = utf8_encode(str_replace(",", "", pg_fetch_result($res,$i,'endereco')));
                $numero = utf8_encode(pg_fetch_result($res,$i,'numero'));
                $cidade = utf8_encode(str_replace(",", "", pg_fetch_result($res,$i,'cidade')));
                $bairro = utf8_encode(str_replace(",", "", pg_fetch_result($res,$i,'bairro')));
                $complemento = urldecode(utf8_encode(str_replace(",", "", pg_fetch_result($res,$i,'complemento'))));
                $parametros_adicionais = json_decode(pg_fetch_result($res,$i,'parametros_adicionais'),true);

                if($login_fabrica == 3){
                    fputs($fp,$nome."\t");
                    fputs($fp,pg_fetch_result($res,$i,'codigo_posto')."\t");
                    fputs($fp,$descricao."\t");
                    fputs($fp,pg_fetch_result($res,$i,'credenciamento')."\t");
                    fputs($fp,$pedido_faturado."\t");
                    fputs($fp,$pedido_em_garantia."\t");
                    fputs($fp,$digita_os."\t");
                    fputs($fp,$prestacao_servico."\t");
                    fputs($fp,$pedido_via_distribuidor."\t");
                    fputs($fp,pg_fetch_result($res,$i,'cnpj')."\t");
                    fputs($fp,pg_fetch_result($res,$i,'ie')."\t");
                    fputs($fp,pg_fetch_result($res,$i,'fone')."\t");
                    fputs($fp,pg_fetch_result($res,$i,'fax')."\t");
                    fputs($fp,$contato."\t");
                    fputs($fp,$endereco."\t");
                    fputs($fp,$numero."\t");
                    fputs($fp, $complemento."\t");
                    fputs($fp, $bairro."\t");
                    fputs($fp,pg_fetch_result($res,$i,'cep')."\t");
                    fputs($fp,$cidade."\t");
                    fputs($fp,pg_fetch_result($res,$i,'estado')."\t");
                    fputs($fp,pg_fetch_result($res,$i,'email')."\t");
                    fputs($fp,"\r\n");
                }
            }
            // fim  conteudo excel

            /*Retira todos usuários do TIME*/
            $sql = "SELECT *
                    FROM  tbl_empresa_cliente
                    WHERE posto   = $posto
                    AND   fabrica = $login_fabrica";
            $res2 = pg_query ($con,$sql);
            if (pg_num_rows($res2) > 0) continue;
            $sql = "SELECT *
                    FROM  tbl_empresa_fornecedor
                    WHERE posto   = $posto
                    AND   fabrica = $login_fabrica";
            $res2 = pg_query ($con,$sql);
            if (pg_num_rows($res2) > 0) continue;

            $sql = "SELECT *
                    FROM  tbl_erp_login
                    WHERE posto   = $posto
                    AND   fabrica = $login_fabrica";
            $res2 = pg_query ($con,$sql);
            if (pg_num_rows($res2) > 0) continue;

            $x = ($login_fabrica==3) ? $i : $i % 20;

            $cor = ($i % 2 == 0) ? "#F1F4FA" : "#F7F5F0" ;

            echo "<tr class='Conteudo' bgcolor='$cor'>";

            if ($login_fabrica == 20) {
                echo "<td nowrap align='left'>" . pg_fetch_result($res,$i,'pais') . "</td>";
            }
                   
            $cidade = pg_fetch_result($res,$i,'cidade');
            
            echo "<td nowrap>" . $cidade . "</td>";

            $param =  pg_fetch_result($res,$i,'parametros_adicionais');
            $param = json_decode($param);
            
            $estado = pg_fetch_result($res,$i,'estado');
            
            if (!empty($estado)) {
                echo "<td nowrap>" .  $estado . "</td>";
            } else { 
                echo "<td nowrap>" . $param->estado . "</td>";
            }
            echo "<td nowrap align='left'><a href='$PHP_SELF?posto=" . pg_fetch_result($res,$i,'posto') . "'>" . pg_fetch_result($res,$i,'nome') . "</a></td>";
            if ($login_fabrica == 158) {
                echo "<td nowrap>" . formata_cpf_cnpj(trim(pg_fetch_result($res,$i,'cnpj'))) . "</td>";
            }
            echo "<td nowrap>" . pg_fetch_result($res,$i,'codigo_posto') . "</td>";

            if ($login_fabrica == 1) {
                echo "<td nowrap align='left'>" . pg_fetch_result($res,$i,'email') . "</td>";
                echo "<td nowrap align='left'>" . pg_fetch_result($res,$i,'categoria') . "</td>";
                echo "<td nowrap align='left'>".$atendente."</td>";
            }

            if ($login_fabrica == 5) {
                echo "<td nowrap align='left'>" . pg_fetch_result($res,$i,'fone') . "</td>";
                echo "<td nowrap align='left'>" . pg_fetch_result($res,$i,'email') . "</td>";
                echo "<td nowrap align='left'>" . pg_fetch_result($res,$i,'endereco') . "</td>";
                echo "<td nowrap align='left'>" . pg_fetch_result($res,$i,'bairro') . "</td>";
            }
            if ($login_fabrica == 15) {
                echo "<td nowrap align='left'>" . pg_fetch_result($res,$i,'ie') . "</td>";
            }
            echo "<td nowrap align='left'>" . pg_fetch_result($res,$i,'descricao') . "</td>";
            if ($login_fabrica == 115) {
                echo "<td nowrap align='left'>" . strtoupper(pg_fetch_result($res,$i,'categoria')) . "</td>";
                $paramentro_add = json_decode(pg_fetch_result($res,$i,'parametros_adicionais'),true);
                $categoria_manual_tabela = 'Não';
                if (isset($paramentro_add["categoria_manual"]) && $paramentro_add["categoria_manual"] == 't') {
                    $categoria_manual_tabela = 'Sim';
                }
                echo "<td nowrap align='center'>" . $categoria_manual_tabela . "</td>";
            }
            echo "<td nowrap align='left'>" . pg_fetch_result($res,$i,'credenciamento') . "</td>";

            if($login_fabrica == 151){
                echo "<td>";
                $valor_mao_obra = json_decode(pg_fetch_result($res,$i,'parametros_adicionais'),true);
                echo number_format($valor_mao_obra["valor_mao_obra"],2,",",".");
                echo "</td>";
                echo "<td>";
                if (pg_fetch_result($res,$i,'divulgar_consumidor') == "t") echo "<img border='0' src='imagens/img_ok.gif'>";
                echo "</td>";
            }

            if ($login_fabrica == 15) {
                echo "<td nowrap align='left'>" . pg_fetch_result($res,$i,'atualizacao') . "</td>";
            }
                if (in_array($login_fabrica, array(25, 47, 81, 114, 123,124,125,136))) {
                echo "<td nowrap align='left'>" . pg_fetch_result($res,$i,'contrato') . "</td>";
            }

            if ($login_fabrica == 42){
                echo "<td>";
                if (pg_fetch_result($res,$i,'pedido_faturado') == "t") echo "*";
                echo "</td>";
                echo "<td>";
                if (pg_fetch_result($res,$i,'pedido_em_garantia') == "t") echo "*";
                echo "</td>";
                echo "<td>";
                if (pg_fetch_result($res,$i,'digita_os') == "t") echo "*";
                echo "</td>";
                echo "<td>";
                if (pg_fetch_result($res,$i,'prestacao_servico') == "t") echo "*";
                echo "</td>";
            }else{

                if($login_fabrica == 35){
                    echo "<td>";
                        if (pg_fetch_result($res,$i,'controla_estoque') == "t") echo "<img border='0' src='imagens/img_ok.gif'>";
                    echo "</td>";
                }

                echo "<td>";
                if (pg_fetch_result($res,$i,'pedido_faturado') == "t") echo "<img border='0' src='imagens/img_ok.gif'>";
                echo "</td>";
                echo "<td>";
                if (pg_fetch_result($res,$i,'pedido_em_garantia') == "t") echo "<img border='0' src='imagens/img_ok.gif'>";
                echo "</td>";
                echo "<td>";
                if (pg_fetch_result($res,$i,'digita_os') == "t") echo "<img border='0' src='imagens/img_ok.gif'>";
                echo "</td>";
                echo "<td>";
                if (pg_fetch_result($res,$i,'prestacao_servico') == "t") echo "<img border='0' src='imagens/img_ok.gif'>";
                echo "</td>";
            }

            if (in_array($login_fabrica, array(20,169,170))) { #HD 85632
                echo "<td>";
                if (pg_fetch_result($res,$i,'prestacao_servico_sem_mo') == "t") echo "<img border='0' src='imagens/img_ok.gif'>";
                echo "</td>";
            }
            echo "<td>";
            if (pg_fetch_result($res,$i,'pedido_via_distribuidor') == "t") echo "<img border='0' src='imagens/img_ok.gif'>";
            echo "</td>";

            if($login_fabrica == 74) { #HD 384458
                echo "<td>";
                if (pg_fetch_result($res,$i,'pedido_bonificacao') == "t") echo "<img border='0' src='imagens/img_ok.gif'>";
                echo "</td>";
                 echo "<td>";
                if ($cook_admin == 5973){
                    $sql_data = "SELECT  tbl_credenciamento.dias  ,
                                    to_char(tbl_credenciamento.data,'YYYY-MM-DD') as data ,
                                    current_date as data_atual ,
                                    tbl_credenciamento.data as date
                            FROM    tbl_credenciamento
                            WHERE   tbl_credenciamento.fabrica = $login_fabrica
                            AND     tbl_credenciamento.posto   = $posto
                            ORDER BY date DESC LIMIT 1 ";
                    $res_data = pg_query($con,$sql_data);
                    if (pg_num_rows($res_data))  {
                        unset($date);
                        $data = pg_fetch_result($res_data, 0, 'data');
                        $dias = pg_fetch_result($res_data, 0, 'dias');
                        $data_atual = pg_fetch_result($res_data, 0, 'data_atual');
                        $status_data = pg_fetch_result($res,$i,'credenciamento');
                        if (($dias > 0) AND ($status_data == "EM DESCREDENCIAMENTO")) {
                            $sql_teste = "SELECT to_char('{$data}'::date+interval'{$dias} days','YYYY-MM-DD') as intervalo";
                            $res_teste = pg_query($con,$sql_teste);
                            $intervalo = pg_fetch_result($res_teste, 0, 'intervalo');
                            if  (strtotime($intervalo) > strtotime($data_atual) ){
                                $cont_dias = strtotime($intervalo) - strtotime($data_atual);
                                $dias = floor($cont_dias / (60 * 60 * 24));
                                echo $dias;
                            }else{
                                echo "0";
                            }
                        }
                    }
                }
                echo "</td>";
            }
            echo "</tr>";
        }
		if ($login_fabrica==3 ){
			fclose ($fp);
			flush();
			echo "</table>";
			echo `cd $path_tmp; rm -rf $arquivo_nome.zip; zip -o $arquivo_nome.zip $arquivo_nome > /dev/null ; mv  $arquivo_nome.zip $path `;
			echo "<br><p id='id_download2'><a href='xls/$arquivo_nome.zip'><img src='../imagens/excel.gif'><br><font color='#3300CC'>".traduz("Fazer download do relatório de todos os postos")."</font></a></p><br>";
		}
        }
    }
    //final gera relatorio excel

    //fim final gera relatorio excel

}
if ($login_fabrica <> 183){
?>
<form name="form_anexo_posto" method="post" action="<?= $_SERVER['PHP_SELF'] ?>" enctype="multipart/form-data" style="display: none;" >
        <input type="file" name="anexo_posto_upload" value="" />

        <input type="hidden" name="cnpj_imagem" value="<? echo $cnpj ?>">
        <input type="hidden" name="posto_imagem" value="<? echo $posto ?>">
        <input type="hidden" name="tipo_anexo" value="" />
</form>
<?php } ?>

<script>
    function abreAreaPosto(codPosto, senhaPosto) {
        $("form[name=frm_login] input[name=login]").val(codPosto);
        $("form[name=frm_login] input[name=senha]").val(senhaPosto);
        alert("<?=traduz('Atenção, irá abrir uma nova janela para que se trabalhe como se fosse este posto ! ')?>" + codPosto);
        $("form[name=frm_login]").submit();
    }
</script>

<?php 
include_once "rodape.php"; ?>
