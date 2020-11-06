<?
$areaAdmin = preg_match('/\/admin\//',$_SERVER['PHP_SELF']) > 0 ? true : false;

include "dbconfig.php";
include "includes/dbconnect-inc.php";

/*ini_set("display_errors", 1);
error_reporting(E_ALL);*/

if ($areaAdmin === true) {
    $admin_privilegios = "call_center";
	$directory_back = "../";
    include __DIR__.'/admin/autentica_admin.php';
} else {
    include __DIR__.'/autentica_usuario.php';
}

include_once 'helpdesk/mlg_funciones.php';
include __DIR__.'/funcoes.php';

$array_estados = $array_estados();
$array_estados = array_map(function($e) {
    return utf8_decode($e);
}, $array_estados);

include "os_cadastro_unico/fabricas/os_revenda.php";

if ($_POST["ajax_cancela_os"]) {
    try {
        
        $os            = $_POST["os"];
        $justificativa = $_POST["justificativa"];
        $tipo_os       = $_POST["tipo_os"];

        if (empty($os)){
            throw new Exception("Ordem de serviço não encontrada");
        }

        if (empty($justificativa)){
            throw new Exception("Informe o motivo para o cancelamento da OS");
        }

        if (empty($tipo_os)){
            throw new Exception("Tipo de OS não encontrado");
        }

        pg_query($con, "BEGIN");
        
        if (file_exists($directory_back."classes/Posvenda/Fabricas/_{$login_fabrica}/Os.php")) {
            include_once $directory_back."classes/Posvenda/Fabricas/_{$login_fabrica}/Os.php";
            $className = '\\Posvenda\\Fabricas\\_' . $login_fabrica . '\\Os';
            $classOs = new $className($login_fabrica, $os);

            $classOs->cancelaOs($con, $os, $justificativa);
        }

        exit(json_encode(array("success" => utf8_encode("OS Cancelada com sucesso"))));
    } catch(Exception $e) {
        if ($begin == true) {
            pg_query($con, "ROLLBACK");
        }

        exit(json_encode(array("erro" => utf8_encode($e->getMessage()))));
    }
    exit;
}

$sqlAuditoria = "
    SELECT 
        tbl_auditoria_status.descricao,
        tbl_auditoria_os_revenda.admin,
        tbl_auditoria_os_revenda.observacao,
        tbl_auditoria_os_revenda.auditoria_os,
        to_char(tbl_auditoria_os_revenda.data_input,'DD/MM/YYYY') AS data_input,
        to_char(tbl_auditoria_os_revenda.liberada,'DD/MM/YYYY') AS liberada,
        to_char(tbl_auditoria_os_revenda.cancelada,'DD/MM/YYYY') AS cancelada,
        to_char(tbl_auditoria_os_revenda.reprovada,'DD/MM/YYYY') AS reprovada,
        tbl_auditoria_os_revenda.justificativa
    FROM tbl_auditoria_os_revenda
    JOIN tbl_auditoria_status ON tbl_auditoria_status.auditoria_status = tbl_auditoria_os_revenda.auditoria_status
    WHERE tbl_auditoria_os_revenda.os_revenda = $os_revenda
    ORDER BY data_input DESC ";
$resAuditoria = pg_query($con, $sqlAuditoria);
if (pg_num_rows($resAuditoria) > 0){
    $dadosAuditoria = pg_fetch_all($resAuditoria);
    
    $aud_liberada  = $dadosAuditoria[0]["liberada"];
    $aud_cancelada = $dadosAuditoria[0]["cancelada"];
    $aud_reprovada = $dadosAuditoria[0]["reprovada"];
}

$layout_menu = ($areaAdmin) ? 'callcenter' : 'os';
$title = ($login_fabrica == 178) ? traduz("CONFIRMAÇÃO DE ORDEM DE SERVIÇO") : traduz("CONFIRMAÇÃO DE ORDEM DE SERVIÇO DE REVENDA") ;

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
   "leaflet"
);

include __DIR__.'/admin/plugin_loader.php'; ?>

<? if (count($msg_erro["msg"]) > 0) { ?>
    <br />
    <div class="alert alert-error"><h4><?= implode("<br />", $msg_erro["msg"]); ?></h4></div>
    <? if ($erro_carrega_os_revenda) {
        include "rodape.php";
        exit;
    }
} else { ?>
    <br />
<? } ?>
<script type="text/javascript">
$(function() {
    Shadowbox.init();
    $('.imprimir_etiqueta').on('click', function() {
        os = $(this).data('os');
        if (window.location.href.match('admin') != null) {
            content_url = "imprimir_etiqueta.php?sua_os=" + os;
        } else {
            content_url = "admin/imprimir_etiqueta.php?sua_os=" + os;
        }
        Shadowbox.open({
            content :   content_url,
            player  :   "iframe",
            title   :   "Etiqueta",
            width   :   800,
            height  :   600
        });
    });

    <?php if ($login_fabrica == 178){ ?>
        function converte_data (data){
            let d = data.split('/');
            return d[2]+'/'+d[1]+'/'+d[0];
        }

        $(".dc_agendamento").datepicker({startDate:'01/01/2000'});
        $("input[name^=data_agendamento_novo]").datepicker({minDate: "-5d", dateFormat: "dd/mm/yy" }).mask("99/99/9999");

        $("#data_agendamento_novo").change(function(){
            let data_agendamento = $(this).val();
            let data_abertura = $("#input_data_abertura").val();

            data_abertura = new Date(converte_data(data_abertura));
            data_agendamento = new Date(converte_data(data_agendamento));
            
            if (data_agendamento < data_abertura){
                alert("Data de Agendamento não pode ser menor que a data de abertura da Ordem de serviço");
                $("#data_agendamento_novo").val("");
                return;
            }
        });

        $(".cancelar_agendamento").click(function() {
            var that = $(this);
            var posto = $('#posto').val();
            var motivo_cancelamento = $(that).prev().val();
            var tecnico_agenda = $(that).data('tecnico_agenda');
            var os_revenda = $(that).data('os_revenda');
            var data_agendada = $(that).data('data_agendada');
            var login_fabrica = <?= $login_fabrica; ?>;

            if (motivo_cancelamento == '' || motivo_cancelamento == undefined){
                alert('<?= traduz("e.necessario.informar.o.motivo.do.cancelamento") ?>');
                return false;
            }

            if(confirm('<?= traduz("tem.certeza.que.deseja.cancelar.visita") ?>')) {
                $.ajax({
                    type: "POST",
                    url: "agendamentos_pendentes.php",
                    dataType:"JSON",
                    data: {
                        ajax_cancelar_visita: true,
                        posto: posto,
                        os_revenda: os_revenda,
                        tecnico_agenda: tecnico_agenda,
                        motivo_cancelamento: motivo_cancelamento,
                        data_agendada: data_agendada,
                        login_fabrica: login_fabrica
                    },
                    beforeSend: function() {
                        $(that).text("Cancelando...").prop({ disabled: true });
                    },
                }).done(function (retorno) {
                    if (retorno.sucesso == 1) {
                        // $(that).parents("tr").css("background", "#ff6159");
                        // $(that).parents("tr").find('.td_motivo_cancelamento').html(motivo_cancelamento);
                        // $(that).parent().append().html("Visita cancelada");
                        window.location.href = "os_revenda_press.php?os_revenda="+os_revenda;
                    } else {
                        alert(retorno.msg);
                        $(that).text("Cancelar Visita").prop({ disabled: false });
                    }
                });
            } else {
                return false;
            }
        });

        $(".agendamento_realizado").click(function() {
            var that = $(this);
            var posto = $('#posto').val();
            var data_confirmacao = $(that).prev().val();
            var tecnico_agenda = $(that).data('tecnico_agenda');
            var os_revenda = $(that).data('os_revenda');
            var data_agendada = $(that).data('data_agendada');
            var login_fabrica = <?= $login_fabrica; ?>;
            
            if (data_confirmacao == '' || data_confirmacao == undefined){
                alert('<?= traduz("e.necessario.selecionar.a.data.confirmacao") ?>');
                return false;
            }

            if(confirm('<?= traduz("tem.certeza.que.deseja.confirmar.visita") ?>')) {
                $.ajax({
                    type: "POST",
                    url: "agendamentos_pendentes.php",
                    dataType:"JSON",
                    data: {
                        ajax_confirmar_visita: true,
                        posto: posto,
                        os_revenda: os_revenda,
                        tecnico_agenda: tecnico_agenda,
                        data_confirmacao: data_confirmacao,
                        data_agendada: data_agendada,
                        login_fabrica: login_fabrica
                    },
                    beforeSend: function() {
                        $(that).text("Confirmando...").prop({ disabled: true });
                    },
                }).done(function (retorno) {
                    if (retorno.sucesso == 1) {
                        //$(that).parent().append().html(data_confirmacao);
                        window.location.href = "os_revenda_press.php?os_revenda="+os_revenda;
                    } else {
                        alert(retorno.msg);
                        $(that).text("Confirmar Visita").prop({ disabled: false });
                    }
                });
            } else {
                return false;
            }
        });

        $("#reagendar_os").click(function(){
            $("#rel_agenda").show();
        });

        $(".btn_confirmar").click(function() {
            var that = $(this);
            var posto = $('#posto').val();
            var tecnico_agenda = $(that).data('tecnico-agenda');
            var os_revenda = $(that).data('os_revenda');
            var tecnico = $('#tecnico').val();
            var linha = $(".linha_agenda");
            var data_agendamento = $("#data_agendamento").val();
            var data_agendamento_novo = $("#data_agendamento_novo").val();
            var login_fabrica = <?= $login_fabrica; ?>;
            var periodo = $("#periodo").val();
            var obs_motivo_agendamento = $("#obs_motivo_agendamento").val();
            var justificativa = $("#justificativa").val();

            if (data_agendamento_novo == ''){
                alert('<?= traduz("e.necessario.selecionar.a.data.de.agendamento") ?>');
                return false;
            }

            if (periodo == ''){
                alert('<?= traduz("selecione.um.periodo.para.efetuar.o.agendamento") ?>');
                return false;
            }

            if (obs_motivo_agendamento == ''){
                alert('<?= traduz("descreva.o.motivo.do.reagendamento") ?>');
                return false;
            }

            if(confirm('<?= traduz("tem.certeza.que.deseja.confirmar.o.agendamento") ?>')) {
                $.ajax({
                    type: "POST",
                    url: "agendamentos_pendentes.php",
                    dataType:"JSON",
                    data: {
                        ajax_reagendar_os: true,
                        posto: posto,
                        tecnico: tecnico,
                        tecnico_agenda: tecnico_agenda,
                        data_agendamento: data_agendamento,
                        data_agendamento_novo: data_agendamento_novo,
                        login_fabrica: login_fabrica,
                        periodo: periodo,
                        obs_motivo_agendamento: obs_motivo_agendamento,
                        justificativa:justificativa,
                        os_revenda: os_revenda
                    },
                    beforeSend: function() {
                        $(that).text("Confirmando...").prop({ disabled: true });
                    },
                }).done(function (retorno) {
                    if (retorno.sucesso == 1) {
                        $("#rel_agenda").hide();
                        $("#data_agendamento_novo").val('');
                        
                        if(retorno.dados["acao"] == "insert"){
                            if (login_fabrica == 178 && retorno.auditoria_visita == "true"){
                                alert("Reagendamento realizado com sucesso. Ordem de serviço em auditoria de visita.");
                            }else{
                                alert("<?= traduz('reagendamento.realizado.com.sucesso') ?>");
                            }
                            window.location.href = "os_revenda_press.php?os_revenda="+os_revenda;
                        } else {
                            alert("<?= traduz('agendamento.atualizado.com.sucesso') ?>");
                            window.location.href = "os_revenda_press.php?os_revenda="+os_revenda;
                        }
                    } else {
                        $(that).text("<?= traduz('confirmar') ?>").prop({ disabled: false });
                        $("#rel_agenda").show();
                        alert(retorno.msg);
                    }
                });
            } else {
                return false;
            }
        });

        /* 
            SCRIPT CANCELAMENTO OS 
        */
            var modal_cancela_os;
            var modal_cancela_os_os;
            $("button.cancelar-os").on("click", function() {
                modal_cancela_os    = $("#modal-cancela-os");
                modal_cancela_os_os = $(this).data("os");
                modal_cancela_os_tipo_os = $(this).data("tipo_os");

                $(modal_cancela_os).find("div.modal-body > div.alert-danger, div.modal-body > div.alert-success").remove();
                $(modal_cancela_os).find("div.modal-header").html("<h4>OS "+modal_cancela_os_os+"</h4>");
                $(modal_cancela_os).find("input[type=text]").val("");
                $("#btn-cancelar-os").prop({ disabled: false }).text("Cancelar");
                $("#input_cancelar_tipo_os").val(modal_cancela_os_tipo_os);

                $(modal_cancela_os).modal("show");
            });

            $("#btn-cancelar-os").on("click", function() {
                var justificativa = String($("#input-cancelar-os-justificativa").val()).trim();
                var tipo_os = $("#input_cancelar_tipo_os").val();

                if (tipo_os == undefined){
                    tipo_os = "";
                }
                if (justificativa == "undefined" || justificativa.length < 8) {
                    alert("Para cancelar a OS é necessário digitar pelo menos 8 caracteres no motivo");
                    return false;
                }

                var btn        = $(this);
                var btn_fechar = $("#btn-close-modal-cancela-os");

                var data_ajax = {
                    ajax_cancela_os: true,
                    os: modal_cancela_os_os,
                    justificativa: justificativa,
                    tipo_os: tipo_os
                };

                $.ajax({
                    url: "os_revenda_press.php",
                    type: "post",
                    data: data_ajax,
                    beforeSend: function() {
                        $(modal_cancela_os).find("div.modal-body > div.alert-danger, div.modal-body > div.alert-success").remove();
                        $(btn).prop({ disabled: true }).text("Cancelando...");
                        $(btn_fechar).prop({ disabled: true });
                    },
                    async: false,
                    timeout: 10000
                }).fail(function(res) {
                    $(modal_cancela_os).find("div.modal-body").prepend("<div class='alert alert-danger' >Ocorreu um erro ao cancelar a OS</div>");
                    $(btn).prop({ disabled: false }).text("Cancelar");
                    $(btn_fechar).prop({ disabled: false });
                }).done(function(res) {
                    res = JSON.parse(res);
                    
                    if (res.erro) {
                        $(modal_cancela_os).find("div.modal-body").prepend("<div class='alert alert-danger' >"+res.erro+"</div>");
                        $(btn).prop({ disabled: false }).text("Cancelar");
                    } else {
                        $(".alterar-os").hide();
                        $(".cancelar-os").hide();
                        $(".os-cancelada").show();
                        $(btn).text("Cancelado");
                        $("#iframe_interacao").hide();
                        $("#box-uploader-app").hide();
                        $(".td_acoes").html('<span class="label label-important">OS CANCELADA</span>');
                    }
                    $(btn_fechar).prop({ disabled: false });
                });
            });

            $("#btn-close-modal-cancela-os").on("click", function() {
                $(modal_cancela_os).modal("hide");
            });
        /* 
            FIM SCRIPT CANCELAMENTO OS 
        */
    <?php } ?>
});
</script>
<style type="text/css">
.table td {text-align:center;padding:5px;vertical-align:middle;}
.td_coluna{
    background-color: #596d9b !important;
    font: bold 11px "Arial" !important;
    color: #FFFFFF !important;
    text-align: left !important;
    padding: 5px !important;
    width: 20% !important;
}
.btn_agendamento_danger {
    padding: 2px 10px;
    font-size: 11.9px;
    border-radius: 3px;
    background-color: #da4f49;
    color: white;
    background-image: linear-gradient(to bottom, #ee5f5b, #bd362f);
    background-repeat:  repeat-x;
    border-color: rgba(0, 0, 0, 0.1) rgba(0, 0, 0, 0.1) rgba(0, 0, 0, 0.25);
    text-shadow: 0 -1px 0 rgba(0, 0, 0, 0.25);
}
</style>
<?php if (in_array($login_fabrica, [173]) ) { ?>
<center>
     <button class="btn btn-success imprimir_etiqueta" data-os="<?= getValue('sua_os'); ?>">Imprimir Etiqueta</button>
</center>
</br>
<?php } ?>

<?php 
    if ($login_fabrica == 178){ 
        if (getValue('os_excluida') == "t"){
            $style_cancelada = '';
        }else{
            $style_cancelada = 'style="display: none;"';
        }

        if (strlen(trim(getValue('data_fechamento_os_revenda'))) > 0){
            $style_fechada = '';
        }else{
            $style_fechada = 'style="display: none;"';
        }
?>
    <div class="row-fluid tac div_acoes" style="min-height: 40px !important;" >
        <div class="alert alert-error os-cancelada" <?=$style_cancelada?> >Ordem de Serviço Cancelada</div>
        <div class="alert alert-success" <?=$style_fechada?> >Ordem de Serviço Fechada</div>
        <?php if (getValue('os_excluida') != "t" AND strlen(trim(getValue('data_fechamento_os_revenda'))) == 0){ ?>
            <a class='alterar-os' href="cadastro_os_revenda.php?os_revenda=<?=getValue('os_revenda')?>">
                <button class="btn btn-primary">Alterar Ordem de Serviço</button>
            </a>
            <button style="margin-left: 50px;" type="button" class="btn btn-danger cancelar-os" data-tipo_os="OSR" data-os="<?=getValue('os_revenda')?>">Cancelar Ordem de Serviço</button>
        <?php } ?>
    </div>

    <?php if (count($dadosAuditoria) AND empty($aud_liberada) AND empty($aud_cancelada) AND empty($aud_reprovada)){ ?>
    <div class="alert alert-error">
        <h4>Essa OS está em Auditoria</h4>
    </div>
    <?php } ?>
    
    <?php if ($areaAdmin){ ?>
    <table class="table table-bordered">
        <thead>
            <tr>
                <th colspan="2" class="titulo_tabela">Informações do Posto</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><strong><?=getValue("posto_codigo")?> - <?=getValue("posto_nome")?></strong></td>
            </tr>
        </tbody>
    </table>
    <?php } ?>
    <table class="table table-bordered">
        <thead>
            <tr>
                <th class="titulo_coluna" scope="col" rowspan="2" class="sua_os_revenda">
                    <p>ORDEM DE SERVIÇO</p>
                    <br />
                    <p style="font-size:40px;">
                        <?=getValue('os_revenda')?>
                    </p>
                </th>
                <th scope="col" class="titulo_coluna"><strong><?= traduz('Data Abertura'); ?></strong></th>
                <th scope="col" class="titulo_coluna"><strong><?= traduz('Data Digitação'); ?></strong></th>
                <th scope="col" class="titulo_coluna"><strong><?= traduz('Tipo de OS'); ?></strong></th>
                <?php if (!empty(getValue('qtde_km'))){ ?>
                <th scope="col" class="titulo_coluna"><strong><?= traduz('Qtde KM'); ?></strong></th>
                <?php } ?>
                <?php if (!empty(getValue('hd_chamado'))){ ?>
                <th scope="col" class="titulo_coluna"><strong><?= traduz('Atendimento Call-Center'); ?></strong></th>
                <?php } ?>
            </tr>
            <tr>
                <td>
                    <small><?= getValue('data_abertura'); ?></small>
                    <input type="hidden" id="input_data_abertura" name="input_data_abertura" value="<?=getValue('data_abertura')?>">
                </td>
                <td><small><?= getValue('orev_digitacao'); ?></small></td>
                <td>
                    <small>
                    <?php
                        if (getValue('consumidor_revenda') == "C"){
                            echo "Consumidor";
                        } else if (getValue('consumidor_revenda') == "R"){
                            echo "Revenda";
                        } else if (getValue('consumidor_revenda') == "S"){ 
                            echo "Construtora";
                        }
                    ?>
                    </small>
                </td>
                <?php if (!empty(getValue('qtde_km'))){ ?>
                <td><small><?= getValue('qtde_km'); ?></small></td>
                <?php } ?>
                <?php 
                if (!empty(getValue('hd_chamado')) && !$areaAdmin){ ?>
                    <td><small><?= getValue('hd_chamado'); ?></small></td>
                <?php 
                } else if (!empty(getValue('hd_chamado'))) { ?>
                    <td><small><a target="_blank" href="callcenter_interativo_new.php?callcenter=<?= getValue('hd_chamado'); ?>"><?= getValue('hd_chamado'); ?></a></small></td>
                <?php
                } ?>
            </tr>
        </thead>
    </table>
    <table class="table table-striped table-bordered">
        <thead>
            <tr class="titulo_tabela">
                <th colspan="6">
                    <?php 
                        if (getValue('consumidor_revenda') == "C" OR getValue('consumidor_revenda') == "S"){
                            echo traduz('Informações do Cliente');
                        } else {
                            echo traduz('Informações da Revenda');
                        }
                    ?>
                </th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="td_coluna"><?=traduz("Telefone Residencial")?></td> <td class="tal"><?=getValue("revenda_fone")?></td>
                <td class="td_coluna"><?=traduz("Nome")?></td> <td colspan="4" class="tal"><?=getValue("revenda_nome")?></td>
            </tr>
            <tr>    
                <td class="td_coluna"><?=traduz("Celular")?></td> <td class="tal"><?=getValue("revenda_celular")?></td>
                <td class="td_coluna"><?=traduz("CPF/CNPJ")?></td> <td class="tal"><?=getValue("revenda_cnpj")?></td>
                <?php if ($login_fabrica == 178 AND strlen(trim(getValue('inscricao_estadual')) > 0 )){ ?>
                    <td class="td_coluna"><?=traduz("Inscr. Estadual")?></td> <td class="tal"><?=getValue("inscricao_estadual")?></td>
                <?php } ?>
            </tr>
            <tr>
                <td class="td_coluna"><?=traduz("Cep")?></td> <td class="tal"><?=getValue("revenda_cep")?></td>
                <td class="td_coluna"><?=traduz("Endereço")?></td> <td colspan="4" class="tal"><?=getValue("revenda_endereco")?></td>
            </tr>
            <tr>
                <td class="td_coluna"><?=traduz("Número")?></td> <td class="tal"><?=getValue("revenda_numero")?></td>
                <td class="td_coluna"><?=traduz("Complemento")?></td> <td colspan="4" class="tal"><?=getValue("revenda_complemento")?></td>
            </tr>
            <tr>
                <td class="td_coluna"><?=traduz("Bairro")?></td> <td class="tal"><?=getValue("revenda_bairro")?></td>
                <td class="td_coluna"><?=traduz("Cidade")?></td> <td colspan="4" class="tal"><?=getValue("revenda_cidade")?></td>
            </tr>
            <tr>
                <td class="td_coluna"><?=traduz("Estado")?></td> <td class="tal"><?=getValue("revenda_estado")?></td>
                <td class="td_coluna"><?=traduz("E-mail")?></td> <td colspan="4" class="tal"><?=getValue("revenda_email")?></td>
            </tr>
        </tbody>
    </table>

    <?php 
        if ($login_fabrica == 178){ 
            if (getValue("consumidor_revenda") == "C" OR getValue("consumidor_revenda") == "S"){
    ?>
                <table class="table table-striped table-bordered">
                    <thead>
                        <tr class="titulo_tabela">
                            <th colspan="4"><?=traduz('Informações da Revenda')?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="td_coluna"><?=traduz("Nome")?></td> <td class="tal"><?=getValue("dados_revenda_consumidor[revenda_nome]")?></td>
                            <td class="td_coluna"><?=traduz("CNPJ")?></td> <td class="tal"><?=getValue("dados_revenda_consumidor[revenda_cnpj]")?></td>
                        </tr>
                        <tr>    
                            <td class="td_coluna"><?=traduz("Telefone")?></td> <td class="tal"><?=getValue("dados_revenda_consumidor[revenda_fone]")?></td>
                            <td class="td_coluna"><?=traduz("Cep")?></td> <td class="tal"><?=getValue("dados_revenda_consumidor[revenda_cep]")?></td>
                        </tr>
                        <tr>
                            <td class="td_coluna"><?=traduz("Endereço")?></td> <td class="tal"><?=getValue("dados_revenda_consumidor[revenda_endereco]")?></td>
                            <td class="td_coluna"><?=traduz("Bairro")?></td> <td class="tal"><?=getValue("dados_revenda_consumidor[revenda_bairro]")?></td>
                        </tr>
                        <tr>
                            <td class="td_coluna"><?=traduz("Cidade")?></td> <td class="tal"><?=getValue("dados_revenda_consumidor[revenda_cidade]")?></td>
                            <td class="td_coluna"><?=traduz("Estado")?></td> <td class="tal"><?=getValue("dados_revenda_consumidor[revenda_estado]")?></td>
                        </tr>
                    </tbody>
                </table>
      <?php } ?>
    <?php } else {?>
        <table class="table table-striped table-bordered">
            <thead>
                <tr class="titulo_tabela">
                    <th colspan="4"><?=traduz('Informações da Revenda')?></th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="td_coluna"><?=traduz("Nome")?></td> <td class="tal"><?=getValue("dados_revenda_consumidor[revenda_nome]")?></td>
                    <td class="td_coluna">
                        <?= (in_array($login_fabrica, [186])) ? traduz("CPF/CNPJ") : traduz("CNPJ") ?>
                    </td>
                    <td class="tal"><?=getValue("dados_revenda_consumidor[revenda_cnpj]")?></td>
                </tr>
                <tr>    
                    <td class="td_coluna"><?=traduz("Telefone")?></td> <td class="tal"><?=getValue("dados_revenda_consumidor[revenda_fone]")?></td>
                    <td class="td_coluna"><?=traduz("Cep")?></td> <td class="tal"><?=getValue("dados_revenda_consumidor[revenda_cep]")?></td>
                </tr>
                <tr>
                    <td class="td_coluna"><?=traduz("Endereço")?></td> <td class="tal"><?=getValue("dados_revenda_consumidor[revenda_endereco]")?></td>
                    <td class="td_coluna"><?=traduz("Bairro")?></td> <td class="tal"><?=getValue("dados_revenda_consumidor[revenda_bairro]")?></td>
                </tr>
                <tr>
                    <td class="td_coluna"><?=traduz("Cidade")?></td> <td class="tal"><?=getValue("dados_revenda_consumidor[revenda_cidade]")?></td>
                    <td class="td_coluna"><?=traduz("Estado")?></td> <td class="tal"><?=getValue("dados_revenda_consumidor[revenda_estado]")?></td>
                </tr>
            </tbody>
        </table>
    <?php } ?>
    
<?php } else {?>
    <table class="table table-striped table-bordered">
        <thead>
            <tr>
                <th class="titulo_coluna" scope="col" rowspan="2" class="sua_os_revenda">
                    <p>OS REVENDA</p>
                    <br />
                    <p style="font-size:40px;">
                        <?= (empty(getValue('sua_os'))) ? getValue('os_revenda') : getValue('sua_os') ?>
                    </p>
                </th>
                <th scope="col" class="titulo_coluna"><strong><?= traduz('Data Abertura'); ?></strong></th>
                <th scope="col" class="titulo_coluna"><strong><?= traduz('Nome Revenda'); ?></strong></th>
                <th scope="col" class="titulo_coluna"><strong><?= traduz('Contato'); ?></strong></th>
                <th scope="col" class="titulo_coluna"><strong><?= (in_array($login_fabrica, [186])) ? traduz("CPF/CNPJ") : traduz("CNPJ") ?></strong></th>
                <th scope="col" class="titulo_coluna"><strong><?= traduz('Telefone'); ?></strong></th>
            </tr>
            <tr>
                <td><small><?= getValue('data_abertura'); ?></small></td>
                <td><small><?= getValue('revenda_nome'); ?></small></td>
                <td><small><?= getValue('revenda_contato'); ?></small></td>
                <td><small><?= getValue('revenda_cnpj'); ?></small></td>
                <td><small><?= getValue('revenda_fone'); ?></small></td>
            </tr>
        </thead>
    </table>
    <?php
    if (in_array($login_fabrica, [169,170])) {
    ?>
    <table class="table table-striped table-bordered">
        <thead>
            <tr class="titulo_tabela">
                <th colspan="4"><?=traduz('Informações da Revenda')?></th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="td_coluna"><?=traduz("Nome")?></td> <td class="tal"><?=getValue("dados_revenda_consumidor[revenda_nome]")?></td>
                <td class="td_coluna">
                    <?= (in_array($login_fabrica, [186])) ? traduz("CPF/CNPJ") : traduz("CNPJ") ?>
                </td>
                <td class="tal"><?=getValue("dados_revenda_consumidor[revenda_cnpj]")?></td>
            </tr>
            <tr>    
                <td class="td_coluna"><?=traduz("Telefone")?></td> <td class="tal"><?=getValue("dados_revenda_consumidor[revenda_fone]")?></td>
                <td class="td_coluna"><?=traduz("Cep")?></td> <td class="tal"><?=getValue("dados_revenda_consumidor[revenda_cep]")?></td>
            </tr>
            <tr>
                <td class="td_coluna"><?=traduz("Endereço")?></td> <td class="tal"><?=getValue("dados_revenda_consumidor[revenda_endereco]")?></td>
                <td class="td_coluna"><?=traduz("Bairro")?></td> <td class="tal"><?=getValue("dados_revenda_consumidor[revenda_bairro]")?></td>
            </tr>
            <tr>
                <td class="td_coluna"><?=traduz("Cidade")?></td> <td class="tal"><?=getValue("dados_revenda_consumidor[revenda_cidade]")?></td>
                <td class="td_coluna"><?=traduz("Estado")?></td> <td class="tal"><?=getValue("dados_revenda_consumidor[revenda_estado]")?></td>
            </tr>
            <tr>
                <td class="td_coluna"><?=traduz("Email")?></td> <td class="tal"><?=getValue("dados_revenda_consumidor[revenda_email]")?></td>
                <td class="td_coluna"><?=traduz("Contato")?></td> <td class="tal"><?=getValue("dados_revenda_consumidor[revenda_contato]")?></td>
            </tr>
        </tbody>
    </table>
<?php 
    }

} ?>

<?php if (pg_num_rows($resAuditoria) > 0){ ?>
<table class="table table-striped table-bordered">
    <thead>
        <tr>
            <th class='titulo_tabela' colspan="5">Histórico de intervenção</th>
        </tr>
        <tr>
            <th class="titulo_coluna">Data</th>
            <th class="titulo_coluna">Descrição</th>
            <th class="titulo_coluna">Status</th>
            <th class="titulo_coluna">Justificativa</th>
            <th class="titulo_coluna">Admin</th>
        </tr>
    </thead>
    <tbody>
    <?php  
        foreach ($dadosAuditoria as $key => $value) {
            $sql_admin = "SELECT nome_completo FROM tbl_admin WHERE fabrica = $login_fabrica AND admin = {$value['admin']}";
            $res_admin = pg_query($con, $sql_admin);
            if (pg_num_rows($res_admin) > 0){
                $nome_completo = pg_fetch_result($res_admin, 0, "nome_completo");
            }

            if (!empty($value["liberada"])){
                $status = "Liberada em ".$value["liberada"];
            }else if (!empty($value["reprovada"])){
                $status = "Reprovada em ".$value["reprovada"];
            }else if (!empty($value["cancelada"])){
                $status = "Cancelada em ".$value["cancelada"];
            }else{
                $status = "Aguardando Admin";
            }
    ?>
        <tr>
            <td><?=$value["data_input"]?></td>
            <td><?=$value["descricao"]?> - <?=$value["observacao"]?></td>
            <td><?=$status?></td>
            <td><?=$value["justificativa"]?></td>
            <td><?=$nome_completo?></td>
        </tr>
    <?php
        } 
    ?>
    </tbody>
</table>

<?php } 

$produtosAdicionados = getValue('produtos_print');

if (count($produtosAdicionados) > 0) {
?>
    <table class="table table-striped table-bordered">
        <thead>
            <?php if ($login_fabrica == 178){ ?>
            <tr>
                <th colspan="8" class="titulo_tabela">Informações da Ordem de Serviço</th>
            </tr>
            <?php } ?>
            <tr>
                <th class="titulo_coluna">OS</th>
                <th class="titulo_coluna">Tipo de Atendimento</th>
                <th class="titulo_coluna">Serie</th>
                <th class="titulo_coluna">Produto</th>
                <?php if ($login_fabrica == 178){ ?>
                <th class="titulo_coluna">Marca</th>
                <?php } 
                if (in_array($login_fabrica, [169,170])) { ?>
                    <td class="titulo_coluna">Qtde</td>
                <?php
                }
                ?>
                <th class="titulo_coluna">Nota Fiscal</th>
                <th class="titulo_coluna">Data da NF</th>
                <?php if ($login_fabrica == 178){ ?>
                <th class="titulo_coluna">Ações</th>
                <?php } ?>
            </tr>
        </thead>
        <tbody>
            <? 
            foreach($produtosAdicionados as $array_produto) { ?>
                <tr>
                    <td><small><a href="os_press.php?os=<?= $array_produto['os']; ?>" target="_blank"><?= $array_produto['sua_os']; ?></small></td>
                    <td><small><?= (!empty($array_produto['tipo_atendimento_descricao_os'])) ? $array_produto['tipo_atendimento_descricao_os'] : $array_produto['tipo_atendimento_descricao']; ?></small></td>
                    <td><small><?= $array_produto['serie']; ?></small></td>
                    <td><small><?= $array_produto['referencia'].' - '.$array_produto['descricao']; ?></small></td>
                    <?php 
                    if (in_array($login_fabrica, [169,170])) { ?>
                        <td><small><?= (!empty($array_produto["os"])) ? 1 : $array_produto["qtde"] ?></small></td>
                    <?php
                    }

                    if ($login_fabrica == 178){ ?>
                    <td><?=$array_produto['marca_nome']?></td>
                    <?php } ?>
                    <td><small><?= $array_produto['nota_fiscal']; ?></small></td>
                    <td><small><?= $array_produto['data_nf']; ?></small></td>
                    <?php 
                        if ($login_fabrica == 178){
                            if (empty($array_produto['data_fechamento'])){
                    ?>
                                <td class='td_acoes'>
                                    <?php if ($array_produto['os_excluida'] == 't'){ ?>
                                        <span class="label label-important">OS CANCELADA</span>
                                    <?php }else{ ?>
                                        <a href="cadastro_os.php?os_id=<?=$array_produto["os"]?>" target='_blank'><button type='button' class='btn btn-small btn-primary'>Lançar itens</button></a>
                                    <?php } ?>
                                </td>
                    <?php 
                            }else{
                    ?>
                                <td><span class="label label-success">OS FECHADA - <?=$array_produto['data_fechamento']?></span></td>
                    <?php
                            }
                        } 
                    ?>
                </tr>
            <? } ?>
        </tbody>
    </table>

<?php
} 
    if ($login_fabrica == 178){
        $observacao_os_revenda = getValue("observacao_os_revenda");
        $observacao_callcenter = getValue("observacao_callcenter");

        if (!empty($observacao_callcenter)){
?>
            <table class="table table-striped table-bordered">
                <thead>
                    <tr>
                        <th colspan="8" class="titulo_tabela">Observação Callcenter</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class='tal'><?=$observacao_callcenter?></td>
                    </tr>
                </tbody>
            </table>
<?php
        }

        if (!empty($observacao_os_revenda)){
?>
            <table class="table table-striped table-bordered">
                <thead>
                    <tr>
                        <th colspan="8" class="titulo_tabela">Observação Ordem Serviço</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class='tal'><?=$observacao_os_revenda?></td>
                    </tr>
                </tbody>
            </table>
<?php            
        }
		if(!empty($login_posto)) {
			$sqlTecnico = "SELECT * FROM tbl_tecnico WHERE fabrica = {$login_fabrica} AND posto = {$login_posto} AND ativo IS TRUE;";
			$resTecnico = pg_query($con,$sqlTecnico);
			$countTecnico = pg_num_rows($resTecnico);
		}

        $sql_agendamento = "
            SELECT  TO_CHAR(tbl_tecnico_agenda.data_agendamento, 'DD/MM/YYYY') AS data_agendamento,
                    TO_CHAR(tbl_tecnico_agenda.confirmado, 'DD/MM/YYYY')       AS data_confirmacao,
                    TO_CHAR(tbl_tecnico_agenda.data_cancelado, 'DD/MM/YYYY')   AS data_cancelado,
                    tbl_tecnico.nome AS nome_tecnico,
                    tbl_tecnico_agenda.tecnico_agenda,
                    tbl_tecnico_agenda.periodo,
                    tbl_tecnico_agenda.obs,
                    tbl_tecnico_agenda.justificativa_cancelado AS motivo_cancelamento
            FROM    tbl_tecnico_agenda
            LEFT JOIN tbl_tecnico ON tbl_tecnico.tecnico = tbl_tecnico_agenda.tecnico AND tbl_tecnico.fabrica = {$login_fabrica}
            WHERE tbl_tecnico_agenda.fabrica = {$login_fabrica}
            AND tbl_tecnico_agenda.os_revenda = $os_revenda
            ORDER BY tbl_tecnico_agenda.tecnico_agenda ASC";
        $res_agendamento = pg_query($con, $sql_agendamento);

        $count_agendamento = pg_num_rows($res_agendamento);
        $xdata_agendamento = pg_fetch_result($res_agendamento, 0, 'data_agendamento');
        $confirmado_cancelado = pg_fetch_all($res_agendamento);

        if ($count_agendamento > 0) {
?>
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th colspan="8" class="titulo_tabela">Visitas</th>
                    </tr>
                    <tr>
                        <th class="titulo_coluna">#</th>
                        <th class="titulo_coluna"><?=traduz("data.agendamento")?></th>
                        <th class="titulo_coluna"><?=traduz("periodo")?></th>
                        <th class="titulo_coluna"><?=traduz("data.confirmacao")?></th>
                        <th class="titulo_coluna"><?=traduz("nome.tecnico")?></th>
                        <th class="titulo_coluna"><?=traduz("motivo")?></th>
                        <th class="titulo_coluna"><?=traduz("data.cancelamento")?></th>
                        <th class="titulo_coluna"><?=traduz("motivo.cancelamento")?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                        $agendamento_confirmado = false;
                        $reagendamento = false;
                        for ($x = ($count_agendamento - 1); $x >= 0; $x--) {
                            $data_agendamento    = pg_fetch_result($res_agendamento, $x, 'data_agendamento');
                            $data_confirmacao    = pg_fetch_result($res_agendamento, $x, 'data_confirmacao');
                            $nome_tecnico        = pg_fetch_result($res_agendamento, $x, 'nome_tecnico');
                            $periodo             = pg_fetch_result($res_agendamento, $x, 'periodo');
                            $obs                 = pg_fetch_result($res_agendamento, $x, 'obs');
                            $justificativa       = pg_fetch_result($res_agendamento, $x, 'justificativa');
                            $xtecnico_agenda     = pg_fetch_result($res_agendamento, $x, 'tecnico_agenda');
                            $motivo_cancelamento = pg_fetch_result($res_agendamento, $x, 'motivo_cancelamento');
                            $data_cancelado      = pg_fetch_result($res_agendamento, $x, 'data_cancelado');

                            if ($periodo == "manha"){
                                $txt_periodo = "Manhã";
                            } else if ($periodo == "tarde") {
                                $txt_periodo = "Tarde";
                            } else {
                                $txt_periodo = "";
                            }

                            if (!empty($motivo_cancelamento)){
                                $tr_color = "style='background-color: #ff6159;'";
                            }else{
                                $tr_color = "";
                            }

                            if ($agendamento_confirmado) {
                                if (!empty($data_confirmacao)) {
                                    $confirmacao = $data_confirmacao;
                                } else {
                                    $confirmacao = "Agendamento Alterado";
                                }
                            } else {
                                if (strlen(trim($data_confirmacao)) > 0) {
                                    $confirmacao = $data_confirmacao;
                                    $agendamento_confirmado = true;
                                }else{
                                    $confirmacao = "";
                                    $reagendamento = true;
                                }
                            }
                    ?>
                            <tr <?=$tr_color?>>
                                <td><?=$x + 1?></td>
                                <td><?=$data_agendamento?></td>
                                <td><?=$txt_periodo?></td>
                                <?php if ((empty($data_confirmacao)) OR (in_array($login_fabrica, [178]) AND !empty($motivo_cancelamento)) ){ ?>
                                <td>
                                    <?php 
                                        if ($areaAdmin === false){
                                            if (empty($motivo_cancelamento) AND getValue('os_excluida') != 't'){
                                    ?>
                                        <div style="width: 235px !important">
                                            <input class="dc_agendamento" style="width: 120px; text-align: center; margin-top: 10px;" type="text" name="dc_agendamento_<?=$x?>" value="">
                                            <button class="btn btn-primary btn-small agendamento_realizado" type="button" data-os_revenda="<?=$os_revenda?>" data-data_agendada='<?=$data_agendamento?>' data-tecnico_agenda='<?=$xtecnico_agenda?>' > 
                                                <?=traduz("confirmar.visita")?>
                                            </button>
                                            <br/>
                                            <input placeholder="Motivo do cancelamento" class="motivo_cancelamento" style="width: 125px; text-align: center; margin-top: 10px;" type="text" name="motivo_cancelamento<?=$x?>" value="">
                                            <button class="btn btn-danger btn-small cancelar_agendamento" type="button" data-os_revenda="<?=$os_revenda?>" data-data_agendada='<?=$data_agendamento?>' data-tecnico_agenda='<?=$xtecnico_agenda?>' > 
                                                <?=traduz("cancelar.visita")?>
                                            </button>
                                        </div>
                                    <?php 
                                            }else{
                                                echo "Visita cancelada";
                                            }
                                        } 
                                    ?>
                                </td>
                                <?php }else{ ?>
                                <td><?=$confirmacao?></td>
                                <?php } ?>
                                <td><?=$nome_tecnico?></td>
                                
                                <?php if (in_array($login_fabrica, [178]) AND strlen($data_confirmacao) > 0 AND empty($motivo_cancelamento)) { ?>
                                    <td colspan="3">
                                        <input placeholder="Motivo do cancelamento" class="motivo_cancelamento" style="width: 170px; text-align: left; margin-top: 10px;" type="text" name="motivo_cancelamento<?=$x?>" value="">
                                        <button class="cancelar_agendamento btn_agendamento_danger" type="button" data-os_revenda="<?=$os_revenda?>" data-data_agendada='<?=$data_agendamento?>' data-tecnico_agenda='<?=$xtecnico_agenda?>' > 
                                            <?=traduz("Cancelar.visita")?>
                                        </button> 
                                    </td>
                                <?php } else { ?>
                                    <td><?=utf8_decode($obs)?></td>
                                    <td><?=$data_cancelado?></td>
                                    <td class='td_motivo_cancelamento'><?=utf8_decode($motivo_cancelamento)?></td>
                                <?php } ?>
                            </tr>
                    <?php
                        }
                    ?>    
                </tbody>
                <?php if (empty($data_fechamento) AND $areaAdmin === false) { 
                    $confirmado_cancelado = end($confirmado_cancelado);
                    if (empty($confirmado_cancelado["data_confirmacao"]) AND empty($confirmado_cancelado["data_cancelado"])){
                        $mostra_reagendamento = "style='display:none;'";
                    }else if (getValue('os_excluida') == 't' OR strlen(trim(getValue('data_fechamento_os_revenda'))) > 0){
                        $mostra_reagendamento = "style='display:none;'";
                    }else{
                        $mostra_reagendamento = "";
                    }
                    
                ?>
                <tfoot <?=$mostra_reagendamento?>>
                    <tr>
                        <td class="titulo_coluna" colspan="8" align="center">
                            <button id="reagendar_os" class="btn btn-primary" type="button"> <?=traduz("Nova Visita")?></button>
                        </td>
                    </tr>
                </tfoot>
                <?php } ?>
            </table>
<?php
        } 
?>
            <table class="table table-bordered" style="display: none;" id="rel_agenda">
                <input type="hidden" id="posto" name="posto" value="<?= $login_posto; ?>" />
                <thead>
                    <tr class="titulo_tabela">
                        <th colspan="6">Agenda de Visitas</th>
                    </tr>
                    <tr class="titulo_coluna">
                        <th><?=traduz("data.agendamento")?></th>
                        <th><?=traduz("periodo")?></th>
                        <th><?=traduz("tecnico")?></th>
                        <th><?=traduz("motivo.reagendamento")?></th>
                        <th><?=traduz("opcoes")?></th>
                    </tr>
                </thead>
                <tbody>
                    <tr class="texto_avulso linha_agenda_<?=$xtecnico_agenda?>">
                        <td>
                            <input style="width: 130px; text-align: center; background-color: #ffffff; cursor: auto;" readonly type="text" id="data_agendamento_novo" name="data_agendamento_novo"/>
                        </td>
                        <td>
                            <select id="periodo" name='periodo' style="width: 120px;">
                                <option value=""><?= traduz("selecione") ?></option>
                                <option value="manha">Manhã</option>
                                <option value="tarde">Tarde</option>
                            </select>
                        </td>
                        <td style="text-align: center;">
                            <select id="tecnico" name="tecnico" class="frm">
                                <option value=""><?= traduz("selecione") ?></option>
                                <? for ($t = 0; $t < $countTecnico; $t++) {
                                    $resIdTecnico = pg_fetch_result($resTecnico, $t, tecnico);
                                    $resNome = pg_fetch_result($resTecnico, $t, nome);
                                    $select = ($tecnico == $resIdTecnico) ? "SELECTED" : ""; ?>
                                    <option value="<?= $resIdTecnico; ?>"><?= $resNome; ?></option>
                                <? } ?>
                            </select>
                        </td>
                        <td style="text-align: center;">
                            <textarea id='obs_motivo_agendamento' name='obs_motivo_agendamento'></textarea>
                        </td>
                        <td style="text-align: center;">
                            <input type="hidden" id="hd_chamado" name="hd_chamado" value="<?=$hd_chamado?>" />
                            <input type="hidden" id="data_agendamento" name="data_agendamento" value="<?=$xdata_agendamento?>" />
                            <button type="button" class="btn btn-primary btn_confirmar" data-tecnico-agenda="<?=$xtecnico_agenda?>" data-os_revenda="<?=$os_revenda?>" data-os="<?=$os?>"><?=traduz("confirmar")?></button>
                        </td>
                    </tr>
                </tbody>
            </table>
<?php
    } 
?>
<?php if ($fabricaFileUploadOS AND getValue('os_excluida') != 't') {
        echo "<br>";
        if (in_array($login_fabrica, [178]) AND strlen(trim(getValue('data_fechamento_os_revenda'))) > 0){
            $hidden_button = true;
        }else{
            $hidden_button = false;
        }

        $tempUniqueId = $os_revenda;
        $boxUploader = array(
            "div_id" => "div_anexos",
            "prepend" => $anexo_prepend,
            "context" => "revenda",
            "unique_id" => $tempUniqueId,
            "hash_temp" => $anexoNoHash,
            "bootstrap" => true,
            "hidden_button" => $hidden_button
        );
        include "box_uploader.php";
}
?>
<?php if ($login_fabrica == 178 AND getValue('os_excluida') != 't' AND strlen(getValue('data_fechamento_os_revenda')) == 0){ ?>
<br/>
<div class="row-fluid">
    <iframe id="iframe_interacao" style="width: 850px;" src="interacoes.php?tipo=OSREVENDA&reference_id=<?=$os_revenda?>&posto="<?=$login_posto?>" frameborder="0" scrolling="no"></iframe>
</div>
<br/>
<div class="row tac" style="min-height: 40px !important;" >
    <a href="cadastro_os_revenda.php?os_revenda=<?=getValue('os_revenda')?>">
        <button class="btn btn-primary alterar-os">Alterar Ordem de Serviço</button>
    </a>
</div>

<div id="modal-cancela-os" class="modal hide fade" data-backdrop="static" data-keyboard="false" >
    <div class="modal-header">
    </div>
    <div class="modal-body">
        <div class="alert alert-info"><strong>Para cancelar a OS é obrigatório informar o motivo</strong></div>
        <form>
            <fieldset>
                <label>Motivo</label>
                <input type="text" id="input-cancelar-os-justificativa" maxlength="200" style="width: 98%;" value="" />
                <input type="hidden" name="input_cancelar_tipo_os" id="input_cancelar_tipo_os" value="" />
            </fieldset>
        </form>
    </div>
    <div class="modal-footer">
        <button type="button" id="btn-close-modal-cancela-os" class="btn">Fechar</button>
        <button type="button" id="btn-cancelar-os" class="btn btn-danger">Cancelar</button>
    </div>
</div>
<?php } ?>
<br/>
<div class="row tac"><a href="os_revenda_print.php?os_revenda=<?= $os_revenda; ?>" target="_blank" class="btn">IMPRIMIR</a></div>
<? include "rodape.php"; ?>
