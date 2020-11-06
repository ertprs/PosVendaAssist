<style>
    #pendencia_helpdesk_icon {
        width: 70px;
        text-align: center;
        background-image: url(imagens/pendencia_atendimento3.jpg);
        background-position: center;
        height: 45px;
        background-repeat: no-repeat;
        cursor: pointer;
    }

    #regra_sla_icon {
        width: 70px;
        text-align: center;
        background-image: url(imagens/prazo.jpg);
        background-size: 45px; 
        background-position: center;
        height: 45px;
        background-repeat: no-repeat;
        cursor: pointer;
    }

    #pendencia_helpdesk_list {
        display: none;
        text-align: center;
        top: 140px;
        width: 300px;
        max-height: 288px;
        border: 1px solid black;
        border-radius: 8px;
        background-color: #FFF;
        z-index: 9999;
        padding: 5px;
    }

    #pendencia_helpdesk_icon span {
        line-height: 20px;
        font-weight: bold;
        color: #FFF;
        font-size: 11px;
        top: -3px;
        margin-left: 27px !important;
    }

    #regra_sla_icon span {
        line-height: 20px;
        font-weight: bold;
        color: #FFF;
        font-size: 11px;
        top: -3px;
        margin-left: 27px !important;
    }

    #pendencia_helpdesk_refresh {
        float: left;
        margin-left: 3px;
        margin-top: 3px;
        cursor: pointer;
    }

    #pendencia_helpdesk_close {
        float: right;
        margin-right: 3px;
        margin-top: 3px;
        cursor: pointer;
    }

    #pendencia_helpdesk_list table {
        margin: 0 auto;
        width: 100%;
        border-collapse: collapse;
    }

    #pendencia_helpdesk_list table tr th {
        background-color: #596D9B;
        color: #FFF;
    }

    #pendencia_helpdesk_list table tr.sem_pendencias th {
        background-color: #FFF;
        color: #F00;
        display: none;
    }

    #pendencia_helpdesk_list table tr.loading {
        display: none;
    }

    #pendencia_helpdesk_list table tr.loading th {
        background-color: #FFF;
    }

    #pendencia_helpdesk_list table tr.loading th img {
        width: 32px;
        height: 32px;
    }

    #pendencia_helpdesk_list table tr td {
        border-bottom: 1px solid #A9A9A9;
    }

    #pendencia_helpdesk_list h6 {
        margin-top: 5px;
        font-size: 11.9px !important;
    }

    .atendimento_alert img {
        width: 12px;
        height: 12px;
    }

    .atendimento_link a {
        text-decoration: none;
        color: #596D9B;
    }
    .atendimento_link_previsao {
        text-decoration: none;
        color: #d90000;
        font-size: 12px;
    }
    .atendimento_link_previsao_2 {
        text-decoration: none;
        font-size: 12px;
    }
    .atendimento_link_previsao_3 {
        text-decoration: none;
        font-size: 11px;
    }
    .atendimento_link_chamado {
        text-decoration: none;
        font-size: 12px;
    }

    #pendencia_helpdesk_list table tr:hover {
        background-color: #D0D0D0;
    }
    .helpdesk_pendencia {
            
    }
    .titulo-prev{
        color: #ff0000;
        font-size: 18px;
    }
</style>
<link rel="stylesheet" type="text/css" href="../plugins/shadowbox_lupa/shadowbox.css" />
<script src="../plugins/shadowbox_lupa/shadowbox.js"></script>

<script>
function helpdesk_previsao() {
    var list = $("#pendencia_helpdesk_list").find("table > tbody");

    $.ajax({
        url: "ajax_pendencia_helpdesk.php",
        type: "POST",
        dataType:"JSON",
        data: { atualiza_atendimentos: true },
        beforeSend: function () {
            $(list).find("tr.loading").show();
            $(list).find("tr[class!=sem_pendencias][class!=loading]").remove();
        }
    })
    .done(function(data) {
        $("#pendencia_helpdesk_icon > span").html(data.qtde);

        if (data.qtde > 0) {
            $(list).append("<tr>\
                <td class='atendimento_alert'></td>\
                <td class='atendimento_link_chamado'>Chamado</td>\
                <td class='atendimento_link_previsao'>Previsão Cliente</td>\
		<td class='atendimento_link_previsao'>Fabrica</td>\
		<td class='atendimento_link_previsao'>Status</td>\
            </tr>");

            $.each(data.atendimentos, function (key, atendimento) {

                $(list).append("<tr>\
                    <td class='atendimento_alert'>&nbsp;</td>\
                    <td class='atendimento_link'><a href='./adm_chamado_detalhe.php?hd_chamado="+atendimento.atendimento+"' target='_blank' >"+atendimento.atendimento+"</a></td>\
                    <td class='atendimento_link_previsao_2'>"+atendimento.previsao_termino+"</td>\
		    <td class='atendimento_link_previsao_3'>"+atendimento.fabrica+"</td>\
		    <td class='atendimento_link_previsao_3'>"+atendimento.status+"</td>\
                </tr>");
            });

        } else {

            $(list).append("<tr>\
                    <td class='atendimento_link_previsao' colspan='5' align='center'>Sem pendências.</td>\
                </tr>");
        }

        $(list).find("tr.loading").hide();
    });
}

function helpdesk_previsao_sla() {
    var list_base = $("#pendencia_helpdesk_list").find("table > tbody#list");
    var list_sla  = $("#pendencia_helpdesk_list").find("table > tbody#list_sla");
    var list      = $("#pendencia_helpdesk_list").find("table > tbody#list_all");
    var fab       = '<?=$fabrica?>';  

    $.ajax({
        url: "ajax_pendencia_helpdesk.php?analista=<?=$analista_hd;?>",
        type: "POST",
        dataType:"JSON",
        data: { atualiza_atendimentos: true, sla: true },
        beforeSend: function () {
            $(list_base).find("tr.loading").show();
        }
    })
    .done(function(data) {
        
        var total_pendencias = parseInt(data.qtde.length) + parseInt(data.qtde_sla.length);
        
        if (fab != 1 && fab != 159) {
            total_pendencias = parseInt(data.qtde.length);
        }

        $("#pendencia_helpdesk_icon > span").html(total_pendencias);

        if (data.qtde_sla.length > 0) {
            $(list_sla).append("<tr>\
                <td class='atendimento_alert' colspan='5' align='center'>SLA</td>\
            </tr>");

            $(list_sla).append("<tr>\
                <td class='atendimento_alert'></td>\
                <td class='atendimento_link_chamado'>Chamado</td>\
                <td class='atendimento_link_previsao'>Previsão Cliente</td>\
		<td class='atendimento_link_previsao'>Fabrica</td>\
		<td class='atendimento_link_previsao'>Status</td>\
            </tr>");

            $.each(data.atendimentos_sla, function (key, atendimento_sla) {
                $(list_sla).append("<tr>\
                    <td class='atendimento_alert'>&nbsp;</td>\
                    <td class='atendimento_link'><a href='./adm_chamado_detalhe.php?hd_chamado="+atendimento_sla.atendimento+"' target='_blank' >"+atendimento_sla.atendimento+"</a></td>\
                    <td class='atendimento_link_previsao_2'>"+atendimento_sla.previsao_termino+"</td>\
		    <td class='atendimento_link_previsao_3'>"+atendimento_sla.fabrica+"</td>\
		    <td class='atendimento_link_previsao_3'>"+atendimento_sla.status+"</td>\
                </tr>");
            });
        } else {
            $(list_sla).append("<tr>\
                <td class='atendimento_alert' colspan='5' align='center'>SLA</td>\
            </tr>");

            $(list_sla).append("<tr>\
                    <td class='atendimento_link_previsao' colspan='4' align='center'>Sem pendências.</td>\
                </tr>");
        }

        if (data.qtde.length > 0) {

            $(list).append("<tr>\
                <td class='atendimento_alert' colspan='5' align='center'>Previsão Cliente</td>\
            </tr>");

            $(list).append("<tr>\
                <td class='atendimento_alert'></td>\
                <td class='atendimento_link_chamado'>Chamado</td>\
                <td class='atendimento_link_previsao'>Previsão Cliente</td>\
		<td class='atendimento_link_previsao'>Fabrica</td>\
		<td class='atendimento_link_previsao'>Status</td>\
            </tr>");
  

            $.each(data.atendimentos, function (key, atendimento) {
                $(list).append("<tr>\
                    <td class='atendimento_alert'>&nbsp;</td>\
                    <td class='atendimento_link'><a href='./adm_chamado_detalhe.php?hd_chamado="+atendimento.atendimento+"' target='_blank' >"+atendimento.atendimento+"</a></td>\
                    <td class='atendimento_link_previsao_2'>"+atendimento.previsao_termino+"</td>\
		    <td class='atendimento_link_previsao_3'>"+atendimento.fabrica+"</td>\
		    <td class='atendimento_link_previsao_3'>"+atendimento.status+"</td>\
                </tr>");
            });
        } else {

            $(list).append("<tr>\
                <td class='atendimento_alert' colspan='4' align='center'>Previsão Cliente</td>\
            </tr>");

            $(list).append("<tr>\
                    <td class='atendimento_link_previsao' colspan='4' align='center'>Sem pendências.</td>\
                </tr>");
        }

        $(list_base).find("tr.loading").hide();
    });
}


$(function () {
    Shadowbox.init({onClose:function(){

        let xfabrica = <?=$fabrica?>;

        if (xfabrica == 1) {
            $("html #sb-wrapper-inner, #sb-info",window.parent.document).css('width', '390px');
            $("#sb-wrapper-inner",window.parent.document).css('height', '170px');
            $("#sb-wrapper",window.parent.document).css("left", "515px");
            $("#sb-wrapper",window.parent.document).css("top", "95px");
        } else if (xfabrica == 159) {
            $("html #sb-wrapper-inner, #sb-info",window.parent.document).css('width', '350px');
            $("#sb-wrapper-inner",window.parent.document).css('height', '190px');
            $("#sb-wrapper",window.parent.document).css("left", "50%");
            $("#sb-wrapper",window.parent.document).css("top", "30%");
        }

    }});

    <?php if (in_array($login_admin, array(7354,5205,4789,8527)) || ($analista_hd == 'sim' && $login_admin != 586)) {?>
        helpdesk_previsao_sla();       

        $(".tabela_sla").click(function(){
            <?php if($fabrica == 159){ ?>
                var width = 350;
                var height = 190;
            <?php } elseif($fabrica == 1){ ?>
                var width = 390;
                var height = 420;
            <?php } ?>
            Shadowbox.open({
                content: "tabela_sla_<?=$fabrica?>.php",
                player: "iframe",
                width: width,
                height: height                
            });
        });



    <?php } else {?>
        helpdesk_previsao();
    <?php }?>
    $("#pendencia_helpdesk_icon").click(function () {
        $("#pendencia_helpdesk_list").show();
<?php 
        if ($fabrica == 159) { 
?>
            $("div[class=tabela_e_helpdesk]").css("width", "1188px");
<?php   
        } else if ($fabrica == 1) { 
?>
            $("div[class=tabela_e_helpdesk]").css("width", "1188px");
<?php   
        } else {
?>
            $("div[class=helpdesk_pendencia]").css("margin-right", "40%");          
<?php
        }
?>
        
    });

    $("#pendencia_helpdesk_close").click(function () {
        $("#pendencia_helpdesk_list").hide();
<?php 
        if ($fabrica == 159) { 
?>
            $("div[class=tabela_e_helpdesk]").css("width", "970px");
            $("div[class=inicio_trabalho]").css("margin-left", "0px");
            $("div[class=trabalho_iniciado]").css("margin-left", "");
<?php   
        } else if ($fabrica == 1) { 
?>
        $("div[class=tabela_e_helpdesk]").css("width", "970px");
        $("div[class=inicio_trabalho]").css("margin-left", "0px");
        $("div[class=trabalho_iniciado]").css("margin-left", "");
<?php   
        }
?>
    });

    $("#pendencia_helpdesk_refresh").click(function () {
        $("#list_sla").html('');
        $("#list_all").html('');
        <?php if (in_array($login_admin, array(5205,4789,8527)) || ($analista_hd == 'sim' && $login_admin != 586)) {?>
            helpdesk_previsao_sla();
        <?php } else {?>
            helpdesk_previsao();
        <?php }?>
    });
});
</script>
<?php
 if (in_array($grupo_admin, array(6)) ||  $login_admin == 8527 || ($analista_hd == 'sim' && $login_admin != 586) and ( $fabrica == 1 OR $fabrica == 159 ) ) { ?>
<div class="helpdesk_pendencia">
    <div id="pendencia_helpdesk_icon">
        <span></span>
    </div>
    <h3 class="titulo-prev">Equipe SLA</h3>
    <div id="pendencia_helpdesk_list">
        <span id="pendencia_helpdesk_refresh" title="Atualizar Controle de Previsão Cliente" >
            <img src="imagens/icon/refresh.png" />
        </span>

        <span id="pendencia_helpdesk_close" title="Fechar" >
            <img src="imagens/icon/close.png" />
        </span>

        <h6>Controle de Previsão Cliente</h6>

        <div style="width: 100%; max-height: 232px; overflow-y: auto;" >
            <table border="0">
                <tbody id="list_sla"></tbody>
            </table><br />
            <table border="0">
                <tbody id="list_all"></tbody>
            </table>
            <table border="0"><tbody id="list">
                <tr class="loading">
                    <td colspan="4" align="center"><img src="imagens/loading_img.gif" /></td>
                </tr></tbody>
            </table>
        </div>
    </div>
    <br>
    <div id="regra_sla_icon" class="tabela_sla">
        <span></span>
    </div>
    <div>
        <h3 class="titulo-prev tabela_sla">Regra SLA</h3>
    </div>
</div>
<?php } else {?>
<div class="helpdesk_pendencia">
    <div id="pendencia_helpdesk_icon">
        <span></span>
    </div>
    <h3 class="titulo-prev">Previsão Cliente</h3>
    <div id="pendencia_helpdesk_list">
        <span id="pendencia_helpdesk_refresh" title="Atualizar Controle de Previsão Cliente" >
            <img src="imagens/icon/refresh.png" />
        </span>

        <span id="pendencia_helpdesk_close" title="Fechar" >
            <img src="imagens/icon/close.png" />
        </span>

        <h6>Controle de Previsão do Cliente</h6>

        <div style="width: 100%; max-height: 232px; overflow-y: auto;" >
            <table border="0">
                <tbody id="list_all"></tbody>
            </table>
            <table border="0"><tbody id="list">
                <tr class="loading">
                    <td colspan="4" align="center"><img src="imagens/loading_img.gif" /></td>
                </tr></tbody>
            </table>
        </div>
        <div style="width: 100%; max-height: 232px; overflow-y: auto;" >
            <table border="0">
                <tbody>
                    <tr class="sem_pendencias">
                        <th colspan="3" >Sem pendências</th>
                    </tr>
                    <tr class="loading">
                        <th colspan="3" ><img src="imagens/loading_img.gif" /></th>
                    </tr>
                </tbody>
            </table>
        </div> 
    </div>
</div>
<?php }?>
