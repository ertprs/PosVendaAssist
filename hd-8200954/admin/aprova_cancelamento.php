<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="call_center";
include 'autentica_admin.php';
include 'funcoes.php';
include_once '../class/communicator.class.php';
include_once "../class/tdocs_obs.class.php";

if ($login_fabrica == 1) {
    include "../class/email/PHPMailer/PHPMailerAutoload.php";
}

if($_POST['tipo_acao']){
    $postos_id = $_POST["postos"];
    $acao = $_POST["acao"];
    $observacao = $_POST['observacao'];    

    $retorno_final = array();
    foreach($postos_id as $posto){
        $posto_id       = $posto['id_posto'];
        $codigo_posto   = $posto['codigo_posto'];

        $motivointerno  = $posto["motivointerno"];

        if($acao == "Aprovar"){
            $retorno = aprovacao_pre_cadastro($posto_id, $codigo_posto, $motivointerno);
            if($retorno['erro'] == true){
                $retorno_final['erro'][] = $retorno['posto'];
            }else{
                $retorno_final['sucesso'][] = $retorno['posto'];
            }
        }else{
            $retorno = reprovacao_pre_cadastro($posto_id, $codigo_posto, $observacao );
            if($retorno['erro'] == true){
                $retorno_final['erro'][] = $retorno['posto'];
            }else{
                $retorno_final['sucesso'][] = $retorno['posto'];
            }
        }
    }
    
    echo json_encode($retorno_final);

    exit;
}

function aprovacao_pre_cadastro($posto, $codigo_posto, $motivointerno){
    global $con, $login_fabrica, $login_admin, $externalId; 

    $resS = pg_query($con,"BEGIN TRANSACTION");

    $sql_credenciamento = "INSERT INTO tbl_credenciamento (fabrica, status, confirmacao_admin, posto, texto, confirmacao) VALUES ($login_fabrica, 'Descredenciamento - Aprovado', $login_admin, $posto, '$motivointerno', now() )";
    $res_credenciamento = pg_query($con, $sql_credenciamento);

    $sql_posto_fabrica = "UPDATE tbl_posto_fabrica SET credenciamento = 'Descred apr' WHERE posto = $posto and fabrica = $login_fabrica";
    $res_posto_fabrica = pg_query($con, $sql_posto_fabrica);

    $assinado = true;
    
    include "gera_cancelamento_prestacao_servico.php";
    
    $arquivo = "/tmp/contrato_cancelamento_servico_$posto.pdf";

    if(file_exists($arquivo)){
        $tDocs = new TDocs($con, $login_fabrica);
        $tDocs->setContext("posto", "contrato");
        $info = $tDocs->getDocumentsByName("contrato_cancelamento_servico_$posto.pdf",'posto',$posto);
            $anexou = $tDocs->uploadFileS3($arquivo, $posto, false, "posto", "contrato");
 
        if(!$anexou){
            $msg_erro = "erro";
        }

    }else{
        $msg_erro = "erro";
    }

    if(strlen(pg_last_error($con))==0){
        $resS = pg_query($con,"COMMIT TRANSACTION");

        $assunto = " Descredenciamento do Posto $codigo_posto Aprovado ";
        $mensagem = "O Descredenciamento do posto $codigo_posto foi aprovado. ";
        
        if ($login_fabrica == 1) { 

            $email = 'cadastro@sbdbrasil.com.br';
            $mailer = new PHPMailer();
            $mailer->IsHTML(true);
            $mailer->SetFrom("noreply@telecontrol.com.br", "BlackeDecker");
            $mailer->AddAddress($email);
            $mailer->AddAttachment($arquivo,"contrato_cancelamento_servico_$posto.pdf");
            $mailer->Subject = $assunto;
            $mailer->Body = $mensagem;
            $mailer->Send();
            unset($mailer);
            
  
        } else {
            $sql_admin = "SELECT email from tbl_admin where fabrica = $login_fabrica and responsavel_postos is true and ativo is true ";
            $res_admin = pg_query($con, $sql_admin);
            for($a = 0; $a < pg_num_rows($res_admin); $a++){
                $email = pg_fetch_result($res_admin, $a, email);

                $mailTc = new TcComm($externalId);
                $res = $mailTc->sendMail(
                    $email,
                    $assunto,
                    $mensagem,
                    $externalEmail
                );        
            }       
        } 
        $retorno = array('erro' => false, 'posto'=>$posto);
        
    }else{
        $resS = pg_query($con,"ROLLBACK TRANSACTION");
        $retorno = array('erro' => true, 'posto'=>$codigo_posto);
    }    

    return $retorno;      
}


function reprovacao_pre_cadastro($posto, $codigo_posto, $observacao){
    global $con, $login_fabrica, $login_admin, $externalId; 

    $resS = pg_query($con,"BEGIN TRANSACTION");

    $sql_credenciamento = "INSERT INTO tbl_credenciamento (fabrica, status, confirmacao_admin, posto, texto, confirmacao) VALUES ($login_fabrica, 'Descredenciamento-Reprovado', $login_admin, $posto, '$observacao', now() )";
    $res_credenciamento = pg_query($con, $sql_credenciamento);

    $sql_posto_fabrica = "UPDATE tbl_posto_fabrica SET credenciamento = 'Descred rep' WHERE posto = $posto and fabrica = $login_fabrica";
    $res_posto_fabrica = pg_query($con, $sql_posto_fabrica);

    if(strlen(pg_last_error($con))==0){
        $resS = pg_query($con,"COMMIT TRANSACTION");

        $assunto = " Descredenciamento do Posto $codigo_posto Reprovado ";
        $mensagem = "O descredenciamento do posto $codigo_posto foi reprovado. ";

        $sql_admin = "SELECT email from tbl_admin where fabrica = $login_fabrica and responsavel_postos is true and ativo is true ";
        $res_admin = pg_query($con, $sql_admin);
        for($a = 0; $a< pg_num_rows($res_admin); $a++){
            $email = pg_fetch_result($res_admin, $a, email);

            $mailTc = new TcComm($externalId);
            $res = $mailTc->sendMail(
                $email,
                $assunto,
                $mensagem,
                $externalEmail
            );        
        }        
        $retorno = array('erro' => false, 'posto'=>$posto);
    }else{
        $resS = pg_query($con,"ROLLBACK TRANSACTION");
        $retorno = array('erro' => true, 'posto'=>$codigo_posto);
    } 
    return $retorno;  
}


if ($_POST["btn_acao"] == "submit") {
    $data_inicial       = $_POST['data_inicial'];
    $data_final         = $_POST['data_final'];
    $nome_posto         = $_POST['descricao_posto'];
    $codigo_posto       = $_POST['codigo_posto'];
    $posto_id           = $_POST['posto_id'];

    $posto_status       = $_POST['posto_status'];


    if(empty($data_inicial) and empty($data_final) and $posto_status != "descredenciamento em aprovação"){
        $msg_erro["msg"][]    ="Informe um período";
        $msg_erro["campos"][] = "data";
    }

    
    if(strlen($data_inicial)>0 and $data_inicial <> "dd/mm/aaaa" or !empty($posto_id)){
        $xdata_inicial =  fnc_formata_data_pg(trim($data_inicial));
        $xdata_inicial = str_replace("'","",$xdata_inicial);
    }

    if(strlen($data_final)>0 and $data_final <> "dd/mm/aaaa" or !empty($posto_id)){
        $xdata_final =  fnc_formata_data_pg(trim($data_final));
        $xdata_final = str_replace("'","",$xdata_final);
    }

    if(!count($msg_erro["msg"])){
        $dat = explode ("/", $data_inicial );//tira a barra
        $d = $dat[0];
        $m = $dat[1];
        $y = $dat[2];

        if(!checkdate($m,$d,$y) AND strlen($data_inicial)>0){
            $msg_erro["msg"][]    ="Data Inválida";
            $msg_erro["campos"][] = "data";
        }
    }
    if(!count($msg_erro["msg"])){
        $dat = explode ("/", $data_final );//tira a barra
        $d = $dat[0];
        $m = $dat[1];
        $y = $dat[2];
        if(!checkdate($m,$d,$y) AND strlen($data_final)>0){
            $msg_erro["msg"][]    ="Data Inválida";
            $msg_erro["campos"][] = "data";
        }
    }

    if($xdata_inicial > $xdata_final) {
        $msg_erro["msg"][]    ="Data Inicial maior que final";
        $msg_erro["campos"][] = "data";
    }

    if (strlen(trim($xdata_final)) > 0 AND strlen(trim($xdata_inicial)) > 0){
        $sql = "SELECT '$xdata_final'::date - '$xdata_inicial'::date";
        $res = pg_query($con,$sql);

        if(pg_fetch_result($res,0,0) > 186) {
            $msg_erro["msg"][] = "O intervalo não pode ser maior que 6 meses" ;
            $msg_erro["campos"][] = "data";
        }
    }

    if (strlen(trim($data_final)) > 0 AND strlen(trim($data_inicial)) > 0 and count($msg_erro)==0){
        $sql_data = " AND data between '$xdata_inicial 00:00:00' and '$xdata_final 23:59:59' ";
    }

    if(strlen(trim($nome_posto))>0 AND strlen(trim($codigo_posto))>0){
        $sql_posto = " AND  tbl_posto_fabrica.posto = $posto_id ";
    }

    if (!empty($posto_status) and $posto_status != "todos") {
        $sql_status = " and   status = '$posto_status'"; 
    } 
   
    if($posto_status == "todos"){
        $sql_status = " and   (status = 'descredenciamento em aprovação' 
                            OR status = 'Descredenciamento - Aprovado' 
                            OR status = 'Descredenciamento-Reprovado' ) "; 
    }

    if(count($msg_erro)==0){
        $sql_temp_credenciamento = "SELECT 
                                    tbl_posto.posto as id_posto, 
                                    tbl_posto.nome, 
                                    tbl_posto.cnpj, 
                                    tbl_credenciamento.posto,                                 
                                    tbl_posto_fabrica.fabrica,
                                    tbl_posto_fabrica.codigo_posto,
                                    tbl_posto_fabrica.contato_estado,
                                    tbl_posto_fabrica.contato_cidade, 
                                    tbl_posto_fabrica.contato_endereco,
                                    tbl_posto_fabrica.contato_numero,
                                    tbl_posto_fabrica.contato_bairro,
                                    tbl_posto_fabrica.contato_fone_comercial,
                                    tbl_tipo_posto.descricao as tipo_posto_descricao,

                                    (select credenciamento from tbl_credenciamento where tbl_posto_fabrica.fabrica = tbl_credenciamento.fabrica and tbl_posto.posto = tbl_credenciamento.posto order by credenciamento desc limit 1) as credenciamento,
                                    
                                    (select status from tbl_credenciamento where tbl_posto_fabrica.fabrica = tbl_credenciamento.fabrica and tbl_posto.posto = tbl_credenciamento.posto order by credenciamento desc limit 1) as status

                                    into temp apr_cancelamento

                                FROM tbl_credenciamento 
                                JOIN tbl_posto on tbl_posto.posto = tbl_credenciamento.posto
                                JOIN tbl_posto_fabrica on tbl_posto_fabrica.posto = tbl_posto.posto and tbl_posto_fabrica.fabrica = $login_fabrica
                                join tbl_tipo_posto on tbl_posto_fabrica.tipo_posto = tbl_tipo_posto.tipo_posto and tbl_tipo_posto.fabrica = $login_fabrica
                                WHERE tbl_credenciamento.fabrica = $login_fabrica 
                                $sql_data 
                                $sql_posto                                
                                GROUP BY id_posto, 
                                    tbl_posto.nome, 
                                    tbl_posto.cnpj, 
                                    tbl_credenciamento.posto,
                                    
                                    tbl_posto_fabrica.fabrica,
                                    tbl_posto_fabrica.codigo_posto,
                                    tbl_posto_fabrica.contato_estado,
                                    tbl_posto_fabrica.contato_cidade, 
                                    tbl_posto_fabrica.contato_endereco,
                                    tbl_posto_fabrica.contato_numero,
                                    tbl_posto_fabrica.contato_bairro,
                                    tbl_posto_fabrica.contato_fone_comercial,
                                    tbl_tipo_posto.descricao 
                                ";
        $res_temp_credenciamento = pg_query($con, $sql_temp_credenciamento);

        $sql_credenciamento= "SELECT * FROM apr_cancelamento where 1 = 1  $sql_status ";
        $res_credenciamento = pg_query($con, $sql_credenciamento);

    }
}

$array_estado = array("AC"=>"AC - Acre","AL"=>"AL - Alagoas","AM"=>"AM - Amazonas",
  "AP"=>"AP - Amapá", "BA"=>"BA - Bahia", "CE"=>"CE - Ceará","DF"=>"DF - Distrito Federal",
  "ES"=>"ES - Espírito Santo", "GO"=>"GO - Goiás","MA"=>"MA - Maranhão","MG"=>"MG - Minas Gerais",
  "MS"=>"MS - Mato Grosso do Sul","MT"=>"MT - Mato Grosso", "PA"=>"PA - Pará","PB"=>"PB - Paraíba",
  "PE"=>"PE - Pernambuco","PI"=>"PI - Piauí","PR"=>"PR - Paraná","RJ"=>"RJ - Rio de Janeiro",
  "RN"=>"RN - Rio Grande do Norte","RO"=>"RO - Rondônia","RR"=>"RR - Roraima",
  "RS"=>"RS - Rio Grande do Sul", "SC"=>"SC - Santa Catarina","SE"=>"SE - Sergipe",
  "SP"=>"SP - São Paulo","TO"=>"TO - Tocantins");

$layout_menu = "gerencia";
$title = "DESCREDENCIAMENTO DE POSTO AUTORIZADO";

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
//        $.autocompleteLoad(Array("produto", "posto"));
        Shadowbox.init();

        $("span[rel=lupa]").click(function () {
            $.lupa($(this));
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

        

        $("#gravartodos").click(function(){

            var acao    = $("#select_acao").val();
            var motivo  = $("#observacao").val();
            var motivointerno = $(".motivointerno").data('motivointerno');

            if(acao == 'Aprovar'){
                var tipo_acao_descricao = "aprovado";
            }else{
                var tipo_acao_descricao = "reprovado";
            }

            var postos  = [];

            $("input[name^=posto_check_]:checked").each(function(){
                postos.push({"id_posto": $(this).attr("value"), "codigo_posto": $(this).data("codigo_posto"), "motivointerno" : $(this).data("motivointerno")});                
            });

            if (acao == "") {
                alert("Selecione a ação desejada.");

            } else if (postos.length == 0) {
                alert("Selecione os postos a passarem por aprovação / reprovação.");
            } else if (acao == "Reprovar" && motivo == "") {
                alert("Escreva o motivo da reprova dos cadastros dos postos.");
            } else {
               $.ajax({
                    url: "<?php echo $_SERVER['PHP_SELF']; ?>",
                    data:{"tipo_acao": true, acao:acao, postos:postos, observacao: motivo },
                    type: 'POST',
                    beforeSend: function () {
                        $("#loading_pre_cadastro").show();
                        $("#gravartodos").attr("disabled","disabled").text("Aguarde...");
                    },
                    complete: function(data) {
                        data = $.parseJSON(data.responseText);
                        console.log(data);
                        console.log(data.sucesso);

                        if(data.sucesso){
                            $(data.sucesso).each(function(key, value){
                                $(".linhaposto_"+value).hide();
                            });
                        } 
                        if(data.erro){
                            $(data.erro).each(function(key, value){
                                alert("Falha ao "+acao+" posto  "+value);
                            });
                        }

                        alert("Descredenciamento "+ tipo_acao_descricao +" com sucesso");

                        $("#loading_pre_cadastro").hide();
                        $("#gravartodos").attr("disabled",false).text("Gravar");

                    }
                });
            }
        });

        $(".aprovar").click(function(){
            var posto_id        = $(this).data("posto");
            var codigo_posto    = $(this).data("codigo-posto");

            $.ajax({
                url: "<?php echo $_SERVER['PHP_SELF']; ?>",
                data:{"aprovacao_pre_cadastro": true, posto:posto_id, codigo_posto:codigo_posto},
                type: 'POST',
                beforeSend: function () {
                    $("#loading_pre_cadastro_"+posto_id).show();
                    $(".aprovar_reprova_"+codigo_posto+" .aprovar").hide();
                    $(".aprovar_reprova_"+codigo_posto+" .reprovar").hide();
                },
                complete: function(data) {
                data = data.responseText;
                data = data.trim();
                    if(data == 'aprovado'){
                        $(".aprovar_reprova_"+codigo_posto).text("Aprovado");
                        alert('Posto aprovado com sucesso.');
                        window.location.reload();
                        
                    }else{
                        alert('Falha ao aprovar.');
                        $(".aprovar_reprova_"+codigo_posto).text("Falha ao aprovar.");
                    }
                }
            }); 
        });

        $(".reprovar").click(function(){
            var posto_id        = $(this).data("posto");
            var codigo_posto    = $(this).data("codigo-posto");

            $.ajax({
                url: "<?php echo $_SERVER['PHP_SELF']; ?>",
                data:{"reprovacao_pre_cadastro": true, posto:posto_id, codigo_posto:codigo_posto},
                type: 'POST',
                beforeSend: function () {
                    $("#loading_pre_cadastro_"+posto_id).show();
                    $(".aprovar_reprova_"+codigo_posto+" .aprovar").hide();
                    $(".aprovar_reprova_"+codigo_posto+" .reprovar").hide();
                },
                complete: function(data) {
                data = data.responseText;
                    if(data == 'reprovado'){
                        $(".aprovar_reprova_"+codigo_posto).text("Reprovado");
                        alert('Posto reprovado com sucesso.');
                    }else{
                        alert('Falha ao reprovar.');
                         $(".aprovar_reprova_"+codigo_posto).text("Falha ao reprovar.");
                    }
                }
            }); 
        });


    });

    function checkaTodos() {
        
        var value = "";
        if($("#todas").is(":checked")){
            value = 'true';
        }else{
            value = 'false';
        }

        $("input[name^=posto_check_]").each(function(){
            if(value == 'true'){
                $(this).prop("checked", true);
            }else{
                $(this).prop("checked", false);
            }
        });
    }

    function retorna_posto(retorno){
        console.log(retorno);
        $("#codigo_posto").val(retorno.codigo);
        $("#descricao_posto").val(retorno.nome);
        $("#posto_id").val(retorno.posto);
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

<div class="row">
    <b class="obrigatorio pull-right">  * Campos obrigatórios </b>
</div>
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
                                <h5 class='asteristico'>*</h5>
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
                            <h5 class='asteristico'>*</h5>
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
                <label class='control-label' for='status'>Status:</label>
                <div class='controls controls-row'>
                    <div class='span12'>
                        <input type="radio" name="posto_status" value="descredenciamento em aprovação"  <?=($posto_status == "descredenciamento em aprovação" OR $posto_status == "")  ? "checked" : ""?>/>&nbsp;Em Aprovação &nbsp;&nbsp;&nbsp;
                        <input type="radio" name="posto_status" value="Descredenciamento - Aprovado" <?=($posto_status == "Descredenciamento - Aprovado") ? "checked" : ""?>/>&nbsp;Aprovado &nbsp;&nbsp;&nbsp;
                        <input type="radio" name="posto_status" value="Descredenciamento-Reprovado" <?=($posto_status == "Descredenciamento-Reprovado") ? "checked" : ""?>/>&nbsp;Reprovado &nbsp;&nbsp;&nbsp;
                        <input type="radio" name="posto_status" value="todos" <?=($posto_status == "todos") ? "checked" : ""?>/>&nbsp;Todos &nbsp;&nbsp;&nbsp;
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
            <?php if($posto_status == 'descredenciamento em aprovação'){ ?>
            <th class='tac' width="50"  style="cursor: pointer;">Todas
                <input type="checkbox" id="todas" onchange='checkaTodos()' name="todas" value="true">
            </th>
            <?php } ?>
            <th class='tac'>CNPJ</th>
            <th class='tac'>Código Posto</th>
            <th class='tac'>Nome Posto</th>
            <th class='tac'>Cidade</th>
            <th class='tac'>Estado</th>
            <th class='tac'>Bairro</th>
            <?php if($posto_status == "todos"){  ?>
                <th class='tac'>Status</th>
            <?php } ?>
            <th class='tac'>Observação</th>
            <th class='tac'>Tipo</th>
        </TR >
    </thead>
    <tbody>
            <?php 
            $tdocs_obs = new TDocs_obs($con, $login_fabrica, 'credenciamento');

            for($i=0; $i<pg_num_rows($res_credenciamento); $i++){
                $cnpj           = pg_fetch_result($res_credenciamento, $i, 'cnpj');
                $codigo_posto   = pg_fetch_result($res_credenciamento, $i, 'codigo_posto');
                $posto          = pg_fetch_result($res_credenciamento, $i, 'posto');
                $estado         = pg_fetch_result($res_credenciamento, $i, 'contato_estado');
                $cidade         = pg_fetch_result($res_credenciamento, $i, 'contato_cidade');
                $id_posto       = pg_fetch_result($res_credenciamento, $i, 'posto');
                $credenciamento = pg_fetch_result($res_credenciamento, $i, 'credenciamento');
                $status         = pg_fetch_result($res_credenciamento, $i, 'status');
                

                $contato_numero      = pg_fetch_result($res_credenciamento, $i, 'contato_numero');
                $contato_endereco    = pg_fetch_result($res_credenciamento, $i, 'contato_endereco');
                $contato_bairro      = pg_fetch_result($res_credenciamento, $i, 'contato_bairro');
                $contato_fone_comercial = pg_fetch_result($res_credenciamento, $i, 'contato_fone_comercial');
                $tipo_posto_descricao = pg_fetch_result($res_credenciamento, $i, 'tipo_posto_descricao');
                $nome_posto     = pg_fetch_result($res_credenciamento, $i, 'nome');

                $sql_texto_credenciamento = "SELECT texto FROM tbl_credenciamento WHERE posto = $posto and credenciamento = $credenciamento and fabrica = $login_fabrica";
                $res_texto_credenciamento = pg_query($con, $sql_texto_credenciamento);
                if(pg_num_rows($res_texto_credenciamento)>0){
                    $texto = pg_fetch_result($res_texto_credenciamento, 0, 'texto');    
                }

            ?>
                <TR class='linhaposto_<?=$id_posto?>'> 
                    <?php if($posto_status == 'descredenciamento em aprovação'){ ?>
                    <td class='tac' width="50"> <input type="checkbox" data-codigo_posto='<?=$codigo_posto?>' data-motivointerno="<?=utf8_decode($texto) ?>" name="posto_check_<?=$i?>" value="<?=$id_posto?>"> </td>
                    <?php } ?>
                    <TD class='tac'> <a href='posto_cadastro.php?posto=<?=$posto?>' target="_blank"> <?=$cnpj?></a></TD>
                    <TD class='tac'><?=$codigo_posto?></TD>
                    <TD class='tac'><?=$nome_posto?></TD>
                    <TD class='tac'><?=$cidade?></TD>
                    <TD class='tac'><?=$estado?></TD>
                    <TD class='tac'><?=$contato_bairro?></TD>
                    <?php if($posto_status == "todos"){ 
                        echo "<td class='tac'>$status</td>";
                     } ?>
                    <?php 
                        if($posto_status == "descredenciamento em aprovação"){
                            $dadosOBS = $tdocs_obs->getObservacao($credenciamento);
                        }
                    ?>
                    <TD class='tac'> 

                        <b>Motivo:</b> <?=utf8_decode($texto) ?> 
                        <?php if($posto_status == "descredenciamento em aprovação"){ ?>
                         <br> <b>Observação Interna: </b> <?= utf8_decode($dadosOBS['observacao'])?>
                        <?php } ?>
                    </TD>
                    <TD class='tac'><?=$tipo_posto_descricao?></TD>
                </TR >
        <? } ?>
    </tbody>
    <?php if($posto_status == 'descredenciamento em aprovação'){?>
    <tfoot>
        <tr class='titulo_coluna'>
            <td height='20' colspan='11' align='left'>
<?php
            //if ($posto_status == "em apr") {
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
                <img src="imagens/loading_img.gif" style="display: none; height: 20px; width: 20px;" id="loading_pre_cadastro" />
<?php
            //}
?>
            </td>
        </tr>
    </tfoot>    
    <?php } ?>
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
