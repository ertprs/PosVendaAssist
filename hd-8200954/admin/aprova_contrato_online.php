<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="call_center";
include 'autentica_admin.php';
include 'funcoes.php';
include_once '../class/communicator.class.php';
include_once '../class/AuditorLog.php';
include_once "../gera_contrato_posto.php";
include_once "../class/tdocs_obs.class.php";

/**
 * - Faz a aprovação do cadastro
 * dos postos
 */
function aprovaPreCadastro($con,$login_fabrica,$posto,$codigo_posto,$login_admin,$externalId)
{
    /*$sqlVerPosto = "
        SELECT  COUNT(1) AS com_contrato
        FROM    tbl_credenciamento
        WHERE   fabrica = $login_fabrica
        AND     posto = $posto
        AND     texto ILIKE '%Valores Modificados%'
  GROUP BY      tbl_credenciamento.credenciamento
    ";

    $resVerPosto = pg_query($con,$sqlVerPosto);
    $contaPosto = pg_fetch_result($resVerPosto,0,com_contrato);*/

    pg_query($con,"BEGIN TRANSACTION");

    $sqlPendenciaAlteracao = "SELECT distinct campo, valor FROM tbl_posto_pendencia_alteracao WHERE posto = $posto AND fabrica = $login_fabrica";
    $resPendenciaAlteracao = pg_query($con, $sqlPendenciaAlteracao);

    $arr_alt = [];
    while ($fetch = pg_fetch_assoc($resPendenciaAlteracao)) {
        if ($fetch['campo'] == 'recebeTaxaAdm') {
            $arr_alt[] = 'parametros_adicionais = JSONB_SET(regexp_replace(parametros_adicionais,\'(\w)\\\\u\',\'\\1\\\\\\\\u\',\'g\')::JSONB,\'{recebeTaxaAdm}\',\'"' . $fetch['valor'] . '"\')';
        } else {
            $arr_alt[] = "{$fetch['campo']} = '{$fetch['valor']}'";
        }
    }

    if (!empty($arr_alt)) {
        $sqlPostoFabrica = "UPDATE tbl_posto_fabrica SET " . implode(', ', $arr_alt) . " WHERE posto = $posto AND fabrica = $login_fabrica";
        $resPostoFabrica = pg_query($con, $sqlPostoFabrica);

        if (pg_affected_rows($resPostoFabrica) == 1) {
            pg_query($con, "DELETE FROM tbl_posto_pendencia_alteracao WHERE posto = $posto AND fabrica = $login_fabrica");
        } else {
            pg_query($con,"ROLLBACK TRANSACTION");

            return ['erro' => 'Erro ao aprovar cadastro'];
        }
    }

    $AuditorLog = new AuditorLog;
    $AuditorLog->RetornaDadosTabela('tbl_posto_fabrica',array("posto" => $posto,"fabrica" => $login_fabrica),'data_alteracao');

    $sql_credenciamento = "INSERT INTO tbl_credenciamento (fabrica, status, confirmacao_admin, posto, texto, confirmacao) VALUES ($login_fabrica, 'Pr&eacute; Cadastro - Aprovado', $login_admin, $posto, 'Aprovado', now() )";
    $res_credenciamento = pg_query($con, $sql_credenciamento);

    if ($login_fabrica == 1) {
        $status_posto = "";
        $sql_status_posto = "SELECT credenciamento FROM tbl_posto_fabrica WHERE posto = $posto AND fabrica = $login_fabrica";
        $res_status_posto = pg_query($con, $sql_status_posto);
        $status_posto = pg_fetch_result($res_status_posto, 0, 'credenciamento');
        //if ($status_posto == 'Pre Cadastro em apr') {
            $sql_posto_fabrica = "UPDATE tbl_posto_fabrica SET credenciamento = 'Pr&eacute; Cad apr' WHERE posto = $posto and fabrica = $login_fabrica";
            $res_posto_fabrica = pg_query($con, $sql_posto_fabrica);
        //}        
    } else {
        $sql_posto_fabrica = "UPDATE tbl_posto_fabrica SET credenciamento = 'Pr&eacute; Cad apr' WHERE posto = $posto and fabrica = $login_fabrica";
        $res_posto_fabrica = pg_query($con, $sql_posto_fabrica);
    }

    $AuditorLog->RetornaDadosTabela()->EnviarLog("update", 'tbl_posto_fabrica',"$login_fabrica*$posto");

    if (!pg_last_error($con)) {
        //if ($contaPosto == 0) {
            $login_posto = $posto;

            $sql_dados_posto = "
                SELECT  tbl_posto_fabrica.codigo_posto      AS posto_codigo,
                        tbl_posto.nome                      AS posto_nome,
                        tbl_posto.cnpj                      AS posto_cnpj,
                        tbl_posto_fabrica.categoria         AS posto_categoria,
                        tbl_posto_fabrica.contato_endereco  AS posto_endereco,
                        tbl_posto_fabrica.contato_numero    AS posto_numero,
                        tbl_posto_fabrica.contato_cep       AS posto_cep,
                        tbl_posto_fabrica.contato_bairro    AS posto_bairro,
                        tbl_posto_fabrica.contato_cidade    AS posto_cidade,
                        tbl_posto_fabrica.contato_estado    AS posto_estado,
                        tbl_posto_fabrica.contato_email     AS posto_email
                FROM    tbl_posto
                JOIN    tbl_posto_fabrica ON tbl_posto_fabrica.fabrica = {$login_fabrica} AND tbl_posto_fabrica.posto = tbl_posto.posto
                WHERE   tbl_posto.posto = {$login_posto}";
            $res_dados_posto = pg_query($con, $sql_dados_posto);

            $posto_codigo       = pg_fetch_result($res_dados_posto, 0, "posto_codigo");
            $posto_nome         = pg_fetch_result($res_dados_posto, 0, "posto_nome");
            $posto_cnpj         = pg_fetch_result($res_dados_posto, 0, "posto_cnpj");
            $posto_endereco     = pg_fetch_result($res_dados_posto, 0, "posto_endereco");
            $posto_numero       = pg_fetch_result($res_dados_posto, 0, "posto_numero");
            $posto_cep          = pg_fetch_result($res_dados_posto, 0, "posto_cep");
            $posto_bairro       = pg_fetch_result($res_dados_posto, 0, "posto_bairro");
            $posto_cidade       = pg_fetch_result($res_dados_posto, 0, "posto_cidade");
            $posto_estado       = pg_fetch_result($res_dados_posto, 0, "posto_estado");
            $posto_categoria    = pg_fetch_result($res_dados_posto, 0, "posto_categoria");
            $posto_email        = pg_fetch_result($res_dados_posto, 0, "posto_email");

            $posto_endereco_completo = "";

            if(strlen($posto_endereco) > 0){
                $posto_endereco_completo .= $posto_endereco;
            }

            if(strlen($posto_numero) > 0){
                $posto_endereco_completo .= ", ".$posto_numero;
            }

            if(strlen($posto_cep) > 0){
                $posto_endereco_completo .= ", ".$posto_cep;
            }

            if(strlen($posto_bairro) > 0){
                $posto_endereco_completo .= ", ".$posto_bairro;
            }

            if(strlen($posto_cidade) > 0){
                $posto_endereco_completo .= ", ".$posto_cidade;
            }

            if(strlen($posto_estado) > 0){
                $posto_endereco_completo .= ", ".$posto_estado;
            }

            switch ($posto_categoria) {
                case "Locadora":
                case "Autorizada":
                case "Compra Peca":
                case "mega projeto":
                    imprimir($con,$login_fabrica,$login_posto,$posto_codigo,$posto_nome,$posto_categoria,$posto_endereco_completo,$posto_cnpj,$posto_email);
                    break;
                case "Locadora Autorizada":
                    imprimir($con,$login_fabrica,$login_posto,$posto_codigo,$posto_nome,"Locadora",$posto_endereco_completo,$posto_cnpj,$posto_email);
                    imprimir($con,$login_fabrica,$login_posto,$posto_codigo,$posto_nome,"Autorizada",$posto_endereco_completo,$posto_cnpj,$posto_email);
                    break;
            }
        //}

        pg_query($con,"COMMIT TRANSACTION");
        if ($login_fabrica == 1) {
            $texto_valores = '';
            $sql_texto = "SELECT texto FROM tbl_credenciamento WHERE posto = $posto AND fabrica = $login_fabrica AND texto ILIKE '%valores%' ORDER BY data DESC LIMIT 1";
            $res_texto = pg_query($con, $sql_texto);
            $texto_valores = utf8_decode(pg_fetch_result($res_texto, 0, 'texto'));
            if (strpos($texto_valores,"Valores Modificados") !== false) {
                $assunto = " Alteração dos dados do Posto $codigo_posto Aprovado ";
                $mensagem = "A solicitação de alteração referente ao Posto $codigo_posto foi aprovada.<br><br>".nl2br($texto_valores);
            
            } else {
                $assunto = " Pré-cadastro do Posto $codigo_posto Aprovado ";
                $mensagem = "O Pré-cadastro do posto $codigo_posto foi aprovado. ";    
            }
        } else {
            $assunto = " Pré-cadastro do Posto $codigo_posto Aprovado "; 
            $mensagem = "O Pré-cadastro do posto $codigo_posto foi aprovado. ";
        }
        $sql_admin = "SELECT admin,email from tbl_admin where fabrica = $login_fabrica and responsavel_postos is true and ativo is true ";
        $res_admin = pg_query($con, $sql_admin);
        for($a = 0; $a < pg_num_rows($res_admin); $a++){
            $email = pg_fetch_result($res_admin, $a, email);
            
            $mailTc = new TcComm('smtp@posvenda');

            $res = $mailTc->sendMail(
                $email,
                utf8_encode($assunto),
                utf8_encode($mensagem),
                'noreply@telecontrol.com.br'
            );
        }
        $retorno = json_encode(array("msg"=>"aprovado"));
    } else {
        pg_query($con,"ROLLBACK TRANSACTION");
    }

    return $retorno;
}

/**
 * - Faz a reprova
 * dos postos cadastrados
 */
function reprovaPreCadastro($con,$login_fabrica,$posto,$codigo_posto,$login_admin,$externalId,$motivo)
{
    $resS = pg_query($con,"BEGIN TRANSACTION");

    if ($login_fabrica == 1) {
        $status_posto = "";
        $sql_status_posto = "SELECT credenciamento FROM tbl_posto_fabrica WHERE posto = $posto AND fabrica = $login_fabrica";
        $res_status_posto = pg_query($con, $sql_status_posto);
        $status_posto = pg_fetch_result($res_status_posto, 0, 'credenciamento');
        if ($status_posto != 'CREDENCIADO') {
            $sql_credenciamento = "INSERT INTO tbl_credenciamento (fabrica, status, confirmacao_admin, posto, texto, confirmacao) VALUES ($login_fabrica, 'Pr&eacute; Cadastro-Reprovado', $login_admin, $posto, '".pg_escape_string($motivo)."', now() )";
            $res_credenciamento = pg_query($con, $sql_credenciamento);      
        }
    } else {        
        $sql_credenciamento = "INSERT INTO tbl_credenciamento (fabrica, status, confirmacao_admin, posto, texto, confirmacao) VALUES ($login_fabrica, 'Pr&eacute; Cadastro-Reprovado', $login_admin, $posto, '".pg_escape_string($motivo)."', now() )";
        $res_credenciamento = pg_query($con, $sql_credenciamento);
    }
    $sqlVerDadosAnteriores = "
        SELECT  regexp_replace(parametros_adicionais,'(\w)\\\\u','\\1\\\\\\\\u','g')::JSON->'dadosAnteriores' AS dados_anteriores
        FROM    tbl_posto_fabrica
        WHERE   fabrica = $login_fabrica
        AND     posto = $posto
    ";
    $resVerDadosAnteriores = pg_query($con,$sqlVerDadosAnteriores);
    $dados_anteriores = pg_fetch_result($resVerDadosAnteriores,0,dados_anteriores);
    if (strlen($dados_anteriores) > 0) {
        $dados_anteriores = json_decode($dados_anteriores,TRUE);
        extract($dados_anteriores);

        if (isset($tipo_posto)) {
            $sqlTipo = "
                UPDATE  tbl_posto_fabrica
                SET     tipo_posto = $tipo_posto
                WHERE   posto = $posto
                AND     fabrica = $login_fabrica
            ";
            $resTipo = pg_query($con,$sqlTipo);
        }

        if (isset($categoria)) {
            $sqlCategoria = "
                UPDATE  tbl_posto_fabrica
                SET     categoria = '$categoria'
                WHERE   posto = $posto
                AND     fabrica = $login_fabrica
            ";
            $rescategoria = pg_query($con,$sqlCategoria);
        }

        if (isset($taxaAdm)) {
            $sqlTaxa = "
                UPDATE  tbl_excecao_mobra
                SET     tx_administrativa = $taxaAdm
                WHERE   posto = $posto
                AND     fabrica = $login_fabrica
            ";
            $resTaxa = pg_query($con,$sqlTaxa);
        }

        if (isset($reembolso)) {
            $sqlReembolso = "
                UPDATE  tbl_posto_fabrica
                SET     reembolso_peca_estoque = '$reembolso'
                WHERE   posto = $posto
                AND     fabrica = $login_fabrica
            ";
            $resReembolso = pg_query($con,$sqlReembolso);
        }

        if (isset($recebeTaxa)) {
            $sqlRecebeTaxa = "
                UPDATE  tbl_posto_fabrica
                SET     parametros_adicionais = JSONB_SET(regexp_replace(parametros_adicionais,'(\w)\\\\u','\\1\\\\\\\\u','g')::JSONB,'{recebeTaxaAdm}','\"".$recebeTaxa."\"')
                WHERE   posto = $posto
                AND     fabrica = $login_fabrica
            ";
            $resRecebeTaxa = pg_query($con,$sqlRecebeTaxa);
        }

        if (!pg_last_error($con)) {
            $sql_posto_fabrica = "
                UPDATE  tbl_posto_fabrica
                SET     parametros_adicionais = JSONB_SET(regexp_replace(parametros_adicionais,'(\w)\\\\u','\\1\\\\\\\\u','g')::JSONB, '{dadosAnteriores}','\"NULL\"'),
                        credenciamento = 'Pr&eacute; Cad rpr'
                WHERE   posto = $posto
                AND     fabrica = $login_fabrica";
            $res_posto_fabrica = pg_query($con, $sql_posto_fabrica);
        }
    } else {
        $sql_posto_fabrica = "UPDATE tbl_posto_fabrica SET credenciamento = 'Pr&eacute; Cad rpr' WHERE posto = $posto and fabrica = $login_fabrica";
        $res_posto_fabrica = pg_query($con, $sql_posto_fabrica);
    }

    if (!pg_last_error($con)) {
        //deletar dados da tabela de pendencia para não dar erro nas futuras aprovações ou . 
        pg_query($con, "DELETE FROM tbl_posto_pendencia_alteracao WHERE posto = $posto AND fabrica = $login_fabrica");

        $resS = pg_query($con,"COMMIT TRANSACTION");
        if ($login_fabrica == 1) {
            $texto_valores = '';
            $sql_texto = "SELECT texto FROM tbl_credenciamento WHERE posto = $posto AND fabrica = $login_fabrica AND texto ILIKE '%valores%' ORDER BY data DESC LIMIT 1";
            $res_texto = pg_query($con, $sql_texto);
            $texto_valores = pg_fetch_result($res_texto, 0, 'texto');
            if (strpos($texto_valores,"Valores Modificados") !== false) {
                $assunto = " Alteração dos dados do Posto $codigo_posto Reprovado ";
                $mensagem = "A solicitação de alteração referente ao Posto $codigo_posto foi Reprovada.<br><br>".nl2br($texto_valores);
            
            } else {
                $assunto = " Pré-cadastro do Posto $codigo_posto Reprovado ";
                $mensagem = "Pré-cadastro do Posto $codigo_posto Reprovado. ";    
            }
        } else { 
            $assunto = " Pré-cadastro do Posto $codigo_posto Reprovado ";
            $mensagem = "Pré-cadastro do Posto $codigo_posto Reprovado. ";
        }

        $sql_admin = "SELECT email from tbl_admin where fabrica = $login_fabrica and responsavel_postos is true and ativo is true ";
        $res_admin = pg_query($con, $sql_admin);
        for ($a = 0; $a< pg_num_rows($res_admin); $a++) {
            $email = pg_fetch_result($res_admin, $a, email);

            $mailTc = new TcComm('smtp@posvenda');
            $mailTc->sendMail(
                $email,
                $assunto,
                $mensagem,
                'noreply@telecontrol.com.br'
            );
        }
        $retorno = json_encode(array("msg"=>"reprovado"));
    } else {
        $resS = pg_query($con,"ROLLBACK TRANSACTION");
    }

    return $retorno;
}

/**
 * - Aprova / reprova em Lote
 */
if (filter_input(INPUT_POST,'ajax') == "postos_em_lote") {
    $tipo   = filter_input(INPUT_POST,'tipo');
    $motivo = filter_input(INPUT_POST,'motivo');
    $postos = filter_input(INPUT_POST,'postos',FILTER_UNSAFE_RAW,FILTER_REQUIRE_ARRAY);

    foreach ($postos as $posto) {
        $sql = "SELECT codigo_posto FROM tbl_posto_fabrica WHERE posto = $posto AND fabrica = $login_fabrica";
        $res = pg_query($con,$sql);

        $codigo_posto = pg_fetch_result($res,0,codigo_posto);

        if ($tipo == "Aprovar") {
            $retorno[$posto] = aprovaPreCadastro($con,$login_fabrica,$posto,$codigo_posto,$login_admin,$externalId);
        } else if ($tipo == "Reprovar") {
            $retorno[$posto] = reprovaPreCadastro($con,$login_fabrica,$posto,$codigo_posto,$login_admin,$externalId,$motivo);
        }
    }

    echo json_encode(array("retorno"=>"ok","msg"=>$tipo,"postos"=>$retorno));
    exit;
}

if ($_POST["btn_acao"] == "submit") {
    $data_inicial       = $_POST['data_inicial'];
    $data_final         = $_POST['data_final'];
    $nome_posto         = $_POST['descricao_posto'];
    $codigo_posto       = $_POST['codigo_posto'];
    $posto_id           = $_POST['posto_id'];
    $posto_status       = $_POST['posto_status'];

    if(strlen($data_inicial)>0 and $data_inicial <> "dd/mm/aaaa" OR strlen($data_final)>0 and $data_final <> "dd/mm/aaaa" or !empty($codigo_posto)){
        $xdata_inicial =  fnc_formata_data_pg(trim($data_inicial));
        $xdata_inicial = str_replace("'","",$xdata_inicial);

        $xdata_final =  fnc_formata_data_pg(trim($data_final));
        $xdata_final = str_replace("'","",$xdata_final);
    }


    if(!count($msg_erro["msg"])){
        $dat = explode ("/", $data_inicial );//tira a barra
        $d = $dat[0];
        $m = $dat[1];
        $y = $dat[2];
        if(!checkdate($m,$d,$y) and strlen($data_inicial)>0){
            $msg_erro["msg"][]    ="Data Inválida";
            $msg_erro["campos"][] = "data";
        }
    }
    if(!count($msg_erro["msg"])){
        $dat = explode ("/", $data_final );//tira a barra
        $d = $dat[0];
        $m = $dat[1];
        $y = $dat[2];
        if(!checkdate($m,$d,$y) and strlen($data_final)>0){
            $msg_erro["msg"][]    ="Data Inválida";
            $msg_erro["campos"][] = "data";
        }
    }

    if($xdata_inicial > $xdata_final) {
        $msg_erro["msg"][]    ="Data Inicial maior que final";
        $msg_erro["campos"][] = "data";
    }

    if (strlen(trim($data_final)) > 0 AND strlen(trim($data_inicial)) > 0 and count($msg_erro)==0){
        $sql_data = " AND tbl_credenciamento.data between '$xdata_inicial 00:00:00' and '$xdata_final 23:59:59' ";
    }

    if(strlen(trim($nome_posto))>0 AND strlen(trim($codigo_posto))>0){
        $sql_posto = " AND  tbl_posto_fabrica.posto = $posto_id ";
    }

    if (!empty($posto_status)) {
        $sql_status = " AND tbl_credenciamento.status ILIKE '%$posto_status'";
    } else {
        $sql_status = " AND     (
                        tbl_credenciamento.status = 'Pre Cadastro em apr'
                    )";
    }

    if(count($msg_erro)==0){
        $sql_credenciamento = "
            SELECT  tbl_posto.nome,
                    tbl_posto.cnpj,
                    tbl_credenciamento.credenciamento,
                    tbl_credenciamento.posto,
                    tbl_credenciamento.texto,
                    tbl_credenciamento.status,
                    tbl_posto_fabrica.categoria,
                    tbl_posto_fabrica.observacao_credenciamento, 
                    tbl_posto_fabrica.codigo_posto,
                    tbl_posto_fabrica.contato_estado,
                    tbl_posto_fabrica.contato_cidade,
                    tbl_posto_fabrica.contato_endereco,
                    tbl_posto_fabrica.contato_numero,
                    tbl_posto_fabrica.contato_bairro,
                    tbl_posto_fabrica.contato_fone_comercial,
                    tbl_posto_fabrica.reembolso_peca_estoque,
                    JSON_FIELD('recebeTaxaAdm',tbl_posto_fabrica.parametros_adicionais)    AS recebe_taxa_administrativa   ,
                    tbl_excecao_mobra.tx_administrativa                                 AS taxa_administrativa,
                    tbl_tipo_posto.descricao as tipo_posto_descricao
            FROM    tbl_credenciamento
            JOIN    tbl_posto           ON  tbl_posto.posto                 = tbl_credenciamento.posto
            JOIN    tbl_posto_fabrica   ON  tbl_posto_fabrica.posto         = tbl_posto.posto
                                        AND tbl_posto_fabrica.fabrica       = tbl_credenciamento.fabrica
                                        AND tbl_posto_fabrica.fabrica       = $login_fabrica
            JOIN    tbl_tipo_posto      ON  tbl_posto_fabrica.tipo_posto    = tbl_tipo_posto.tipo_posto
                                        AND tbl_tipo_posto.fabrica          = $login_fabrica
            JOIN    tbl_excecao_mobra   ON  tbl_excecao_mobra.posto         = tbl_posto_fabrica.posto
                                        AND tbl_excecao_mobra.fabrica       = tbl_posto_fabrica.fabrica
                                        AND tbl_excecao_mobra.fabrica       = $login_fabrica
            WHERE   tbl_credenciamento.fabrica  = $login_fabrica

            $sql_data
            $sql_posto
            $sql_status
            AND     tbl_credenciamento.credenciamento IN (

                    SELECT  MAX(tbl_credenciamento.credenciamento) AS credenciamento
                    FROM    tbl_credenciamento
                    WHERE   tbl_credenciamento.fabrica = $login_fabrica
                    AND     tbl_credenciamento.texto IS NOT NULL
                    AND status NOT ILIKE 'descredenciamento%'
              GROUP BY      tbl_credenciamento.posto
                )
        ";
        $res_credenciamento = pg_query($con, $sql_credenciamento);
    }
}

$layout_menu = "gerencia";
$title = "CADASTRO DE POSTO AUTORIZADO";

include "cabecalho_new.php";

$plugins = array(
    "autocomplete",
    "datepicker",
    "shadowbox",
    "mask",
    "dataTable",
    "multiselect"
);

include("plugin_loader.php");
?>
<style type="text/css">
    .motivo {
        display:none;
    }
</style>
<script type="text/javascript">


    $(function() {
        $.datepickerLoad(Array("data_final", "data_inicial"));
        Shadowbox.init();

        $("span[rel=lupa]").click(function () {
            $.lupa($(this));
        });

        $(".aprovar").click(function(){
            var posto_id        = $(this).data("posto");
            var codigo_posto    = $(this).data("codigo-posto");

            $.ajax({
                url: "<?=$PHP_SELF?>",
                type: 'POST',
                dataType:"JSON",
                data:{
                    "aprovacao_pre_cadastro": true,
                    posto:posto_id,
                    codigo_posto:codigo_posto
                    },
                beforeSend: function () {
                    $("#loading_pre_cadastro_"+posto_id).show();
                    $(".aprovar_reprova_"+codigo_posto+" .aprovar").hide();
                    $(".aprovar_reprova_"+codigo_posto+" .reprovar").hide();
                }
            })
            .done(function(data) {
                msg = data.msg;
                if (msg == 'aprovado') {
                    $(".aprovar_reprova_"+codigo_posto).text("Aprovado");
                    $("input[name^=check_][value="+posto_id+"]").css("display","none");
                    alert('Posto aprovado com sucesso.');
                }
            })
            .fail(function(){
                alert('Falha ao aprovar.');
                $(".aprovar_reprova_"+codigo_posto).text("Falha ao aprovar.");
            });
        });

        $(".reprovar").click(function(){
            var posto_id        = $(this).data("posto");
            var codigo_posto    = $(this).data("codigo-posto");

            $.ajax({
                url: "<?=$PHP_SELF?>",
                data:{"reprovacao_pre_cadastro": true, posto:posto_id, codigo_posto:codigo_posto},
                type: 'POST',
                dataType:"JSON",
                beforeSend: function () {
                    $("#loading_pre_cadastro_"+posto_id).show();
                    $(".aprovar_reprova_"+codigo_posto+" .aprovar").hide();
                    $(".aprovar_reprova_"+codigo_posto+" .reprovar").hide();
                }
            })
            .done(function(data) {
                data = data.msg;
                if(data == 'reprovado'){
                    $(".aprovar_reprova_"+codigo_posto).text("Reprovado");
                    $("input[name^=check_][value="+posto_id+"]").css("display","none");
                    alert('Posto reprovado com sucesso.');
                }
            })
            .fail(function(){
                alert('Falha ao reprovar.');
                $(".aprovar_reprova_"+codigo_posto).text("Falha ao reprovar.");
            });
        });

        $("#gravartodos").click(function(){
            var acao    = $("#select_acao").val();
            var motivo  = $("#observacao").val();
            var postos  = [];
            $("input[id^=check_]:checked").each(function(){
                postos.push($(this).attr("value"));
            });

            if (acao == "") {
                alert("Selecione a ação desejada.");

            } else if (postos.length == 0) {
                alert("Selecione os postos a passarem por aprovação / reprovação.");
            } else if (acao == "Reprovar" && motivo == "") {
                alert("Escreva o motivo da reprova dos cadastros dos postos.");
            } else {

                $.ajax({
                    url: "<?=$PHP_SELF?>",
                    type: 'POST',
                    dataType:"JSON",
                    data:{
                        tipo:acao,
                        ajax:"postos_em_lote",
                        motivo:motivo,
                        postos:postos
                    },
                    beforeSend: function () {
                        $("img[id^=loading_pre_cadastro_]").show();
                        $("button[class*=aprovar]").hide();
                        $("button[class*=reprovar]").hide();
                        $("#gravartodos").attr("disabled","disabled").text("Aguarde...");
                    }
                })
                .done(function(data) {
                    if (data.msg == 'Aprovar') {
                        alert('Postos aprovados com sucesso.');
                    } else {
                        alert('Postos reprovados com sucesso.');
                    }
                    $("#btn_acao").trigger("click");

                });
            }
        });

        $("#select_acao").change(function(){
            var acao = $(this).val();

            if (acao == "Reprovar") {
                $(".motivo").css("display","inline");
            } else {
                $(".motivo").css("display","none");
                $("#observacao").val("");
            }
        });
    });

    function retorna_posto(retorno){
        console.log(retorno);
        $("#codigo_posto").val(retorno.codigo);
        $("#descricao_posto").val(retorno.nome);
        $("#posto_id").val(retorno.posto);
    }

    var ok = false;
    var cont=0;

    function setCheck(theCheckbox,mudarcor,cor) {

        if ($("#"+theCheckbox).prop("checked")) {
            $("#"+mudarcor+" > td").css({"background-color" : "#F5ECCE !important"});
        } else {
            $("#"+mudarcor+" > td").css({"background-color" : "#fff !important"});
        }

    }

    function checkaTodos() {
        $("input[id^='check_']").each(function(){

            var linha = [];
            var id = $(this).attr("id");
            var linha_id = "";

            linha = id.split("_");
            linha_id = linha[1];

            if($(this).prop("checked")){
                $(this).prop("checked", false);
                $("#linha_"+linha_id+" > td").css({"background-color" : "#fff !important"});
            }else{
                $(this).prop("checked", true);
                $("#linha_"+linha_id+" > td").css({"background-color" : "#F5ECCE !important"});
            }
        });
    }


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

<!-- <script type="text/javascript" src="js/grafico/highcharts.js"></script> -->
<script type="text/javascript" src="js/novo_highcharts.js"></script>
<script type="text/javascript" src="js/modules/exporting.js"></script>


<form name="frm_relatorio" METHOD="POST" ACTION="<? echo $PHP_SELF ?>" align='center' class='form-search form-inline tc_formulario'>
    <div class='titulo_tabela '>Parâmetros de Pesquisa</div>
    <br/>

    <div class='row-fluid'>
        <div class='span2'></div>
            <div class='span4'>
                <div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
                    <label class='control-label' for='data_inicial'>Data Inicial</label>
                    <div class='controls controls-row'>
                        <div class='span4'>
                                <input type="text" name="data_inicial" id="data_inicial" size="12" maxlength="10" class='span12' value= "<?=$data_inicial?>">
                        </div>
                    </div>
                </div>
            </div>
        <div class='span4'>
            <div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
                <label class='control-label' for='data_final'>Data Final</label>
                <div class='controls controls-row'>
                    <div class='span4'>
                            <input type="text" name="data_final" id="data_final" size="12" maxlength="10" class='span12' value="<?=$data_final?>" >
                    </div>
                </div>
            </div>
        </div>
        <div class='span2'></div>
    </div>

    <div class='row-fluid'>
        <div class='span2'></div>
        <div class='span4'>
            <div class='control-group <?=(in_array("posto", $msg_erro["campos"])) ? "error" : ""?>'>
                <label class='control-label' for='codigo_posto'>Código Posto</label>
                <div class='controls controls-row'>
                    <div class='span7 input-append'>
                        <input type="text" name="codigo_posto" id="codigo_posto" class='span12' value="<? echo $codigo_posto ?>" >
                        <span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
                        <input type="hidden" name="lupa_config" tipo="posto" parametro="codigo" />
                    </div>
                </div>
            </div>
        </div>
        <div class='span4'>
            <div class='control-group <?=(in_array("posto", $msg_erro["campos"])) ? "error" : ""?>'>
                <label class='control-label' for='descricao_posto'>Nome Posto</label>
                <div class='controls controls-row'>
                    <div class='span12 input-append'>
                        <input type="text" name="descricao_posto" id="descricao_posto" class='span12' value="<? echo $descricao_posto ?>" >&nbsp;
                        <span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
                        <input type="hidden" name="lupa_config" tipo="posto" parametro="nome" />
                    </div>
                </div>
            </div>
        </div>
        <div class='span2'></div>
    </div>
    <div class='row-fluid'>
        <div class='span2'></div>
        <div class='span8'>
            <div class='control-group'>
                <label class='control-label' for='status'>Status</label>
                <div class='controls controls-row'>
                    <div class='span12'>
                        <input type="radio" name="posto_status" value="em apr"  <?=($posto_status == "em apr" || empty($posto_status))  ? "checked" : ""?>/>Em Aprovação
                        <input type="radio" name="posto_status" value="Aprovado" <?=($posto_status == "Aprovado") ? "checked" : ""?>/>Aprovado
                        <input type="radio" name="posto_status" value="Reprovado" <?=($posto_status == "Reprovado") ? "checked" : ""?>/>Reprovado
                    </div>
                </div>
            </div>
        </div>
        <div class='span2'></div>
    </div>
    <p><br/>
        <button class='btn' id="btn_acao" type="button"  onclick="submitForm($(this).parents('form'));">Pesquisar</button>
        <input type='hidden' id="btn_click" name='btn_acao' value='' />
        <input type='hidden' id="posto_id" name='posto_id' value='<?=$posto_id?>' />
    </p><br/>
</FORM>
</div>
<?php
if (isset($res_credenciamento)) {
    if (pg_num_rows($res_credenciamento) > 0) {
        echo "<br />";
        $count = pg_num_rows($res_credenciamento);
?>
<table id="relatorio_aprovacao_posto" class='table table-striped table-bordered table-hover table-fixed' >
    <thead>
        <TR class='titulo_coluna'>
<?php
        if ($posto_status == "em apr") {
?>
            <th><span onclick='checkaTodos()' style="cursor: pointer;">Todas</span></th>
<?php
        }
?>
            <th class='tac'>CNPJ</th>
            <th class='tac'>Código Posto</th>
            <th class='tac'>Nome Posto</th>
            <th class='tac'>Cidade</th>
            <th class='tac'>Estado</th>
            <th class='tac'>Bairro</th>            
            <th class='tac'>Tipo</th>
            <th class='tac'>Categoria</th>
            <th class='tac'><acronym title='Reembolso de Peça do Estoque (Garantia Automática)' style='cursor:help;'>Peças em Garantia</a></th>
            <th class='tac'>Taxa Administrativa</th>
            <th class='tac'>Recebe Taxa ADM</th>
            <th class='tac'> <?php if ($posto_status == "Reprovado"){ 
                                        echo "Motivo Reprova";
                                }elseif ($posto_status == "Aprovado"){
                                    echo "Situação";
                                }  else {
                                    echo "Valores Modificados";
                                } ?></th>
            <th class='tac'>Observação</th>
        </TR >
    </thead>
    <tbody>
<?php
            $tdocs_obs = new TDocs_obs($con, $login_fabrica, 'credenciamento');

            for ($i=0; $i < pg_num_rows($res_credenciamento); $i++) {

                $cnpj                       = pg_fetch_result($res_credenciamento, $i, cnpj);
                $codigo_posto               = pg_fetch_result($res_credenciamento, $i, codigo_posto);
                $posto                      = pg_fetch_result($res_credenciamento, $i, posto);
                $texto                      = pg_fetch_result($res_credenciamento, $i, texto);
                $estado                     = pg_fetch_result($res_credenciamento, $i, contato_estado);
                $cidade                     = pg_fetch_result($res_credenciamento, $i, contato_cidade);

                $contato_numero             = pg_fetch_result($res_credenciamento, $i, contato_numero);
                $contato_endereco           = pg_fetch_result($res_credenciamento, $i, contato_endereco);
                $contato_bairro             = pg_fetch_result($res_credenciamento, $i, contato_bairro);
                $contato_fone_comercial     = pg_fetch_result($res_credenciamento, $i, contato_fone_comercial);
                $tipo_posto_descricao       = pg_fetch_result($res_credenciamento, $i, tipo_posto_descricao);
                $reembolso_peca_estoque     = pg_fetch_result($res_credenciamento, $i, reembolso_peca_estoque);
                $recebe_taxa_administrativa = pg_fetch_result($res_credenciamento, $i, recebe_taxa_administrativa);
                $taxa_administrativa        = pg_fetch_result($res_credenciamento, $i, taxa_administrativa);
                $nome_posto                 = pg_fetch_result($res_credenciamento, $i, nome);
                $categoria                  = pg_fetch_result($res_credenciamento, $i, categoria);

                if($categoria == "mega projeto"){
                    $categoria = "Industria/Mega Projeto";
                }

                $observacao_credenciamento  = utf8_decode(pg_fetch_result($res_credenciamento, $i, observacao_credenciamento));

                $credenciamento = pg_fetch_result($res_credenciamento, $i, "credenciamento");
            

                
?>
                <tr id="linha_<?=$i?>">
<?php
                if ($posto_status == "em apr") {
?>
                    <td class='tac' align='center' width='0'>
                        <input type='checkbox' name='check_<?=$i?>' id='check_<?=$i?>' value='<?=$posto?>' onclick="setCheck('check_<?=$i?>','linha_<?=$i?>');" <?(strlen($_POST["check_".$i])>0) ? " CHECKED " : ""?>>
                    </td>
<?php
                }
?>
                    <TD class='tac'> <a href='posto_cadastro.php?posto=<?=$posto?>' target="_blank"> <?=$cnpj?></a></TD>
                    <TD class='tac'><?=$codigo_posto?></TD>
                    <TD class='tac'><?=$nome_posto?></TD>
                    <TD class='tac'><?=$cidade?></TD>
                    <TD class='tac'><?=$estado?></TD>
                    <TD class='tac'><?=$contato_bairro?></TD>
                    <TD class='tac'><?=$tipo_posto_descricao?></TD>
                    <TD width='230' class='tac'>
                        <?=$categoria?>
                    </td>
                    <TD width='230' class='tac'>
                        <?=($reembolso_peca_estoque == 't') ? "SIM" : "NÃO"?>
                    </td>
                    <TD class='tac'><?=($taxa_administrativa == 0) ? 'GRADATIVA' : number_format(($taxa_administrativa - 1) * 100,2,',','.')."%" ?></TD>
                    <TD class='tac'><?=($recebe_taxa_administrativa == 'sim') ? "SIM" : "NÃO"?></TD>
                    <TD class='tac'><?=(strpos($texto,"Valores Modificados") !== false) ? nl2br(utf8_decode($texto)) : utf8_decode($texto)?></TD>
                    <td class="tac"><?=$observacao_credenciamento?></td>
                </TR >

<?php
            }
?>
    </tbody>
    <tfoot>
        <tr class='titulo_coluna'>
            <td height='20' colspan='100%' align='left'>
<?php
            if ($posto_status == "em apr") {
?>
                &nbsp;&nbsp;&nbsp;&nbsp;<img border='0' src='imagens/seta_checkbox.gif' align='absmiddle'> &nbsp; COM MARCADOS:&nbsp;
                <select name='select_acao' id='select_acao' size='1' class='frm' >
                    <option value=''></option>
                    <option value='Aprovar'   >Aprovar</option>
                    <option value='Reprovar' >Reprovar</option>
                </select>
                <div class="motivo">
                &nbsp;&nbsp;Motivo: <input class='frm' type='text' name='observacao' id='observacao' size='50' maxlength='900' value=''>
                &nbsp;&nbsp;
                </div>
                <button type='button' class='btn' value='Gravar' border='0' id='gravartodos'>Gravar</button>
<?php
            }
?>
            </td>
        </tr>
    </tfoot>
</table>

<?php
            if ($count > 10) {
?>
                <script>
                    $.dataTableLoad({ table: "#relatorio_aprovacao_posto" });
                </script>
            <?php
            }
            ?>
        <br />

            <?php
            echo $grafico_topo.$grafico_conteudo.$grafico_rodape;

        }else{
            echo "<div class='container'>
            <div class='alert'>
                    <h4>Nenhum resultado encontrado</h4>
            </div>
            </div>";
        }
    }
?>
<? include "rodape.php" ?>
