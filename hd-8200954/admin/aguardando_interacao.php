<style>
#aguardando_interacao_icon {
    text-align: center;
    background-image: url(imagens/pendencia_atendimento3.jpg);
    background-position: center;
    height: 45px;
    background-repeat: no-repeat;
    cursor: pointer;
}

#aguardando_interacao_icon {
    background-image: url(imagens/pendencia_atendimento_verde.jpg) !important;
}

#aguardando_interacao_icon > span {
    display: inline-block;
    font-weight: bold;
    color: #FFF;
    font-size: 14px;
    margin-top: 1px;
    margin-left: 28px;
}

#aguardando_interacao_refresh {
    float: left;
    margin-left: 3px;
    margin-top: 3px;
    cursor: pointer;
}

#aguardando_interacao_close {
    float: right;
    margin-right: 3px;
    margin-top: 3px;
    cursor: pointer;
}

#aguardando_interacao_list table {
    margin: 0 auto;
    width: 100%;
    border-collapse: collapse;
}

#aguardando_interacao_list table tr th {
    background-color: #596D9B;
    color: #FFF;
}

#aguardando_interacao_list table tr.sem_pendencias th {
    background-color: #FFF;
    color: #F00;
    display: none;
}

#aguardando_interacao_list table tr.loading {
    display: none;
}

#aguardando_interacao_list table tr.loading th {
    background-color: #FFF;
}

#aguardando_interacao_list table tr.loading th img {
    width: 32px;
    height: 32px;
}

#aguardando_interacao_list table tr td {
    border-bottom: 1px solid #A9A9A9;
}

#aguardando_interacao_list h6 {
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

#aguardando_interacao_list table tr:hover {
    background-color: #D0D0D0;
}

#aguardando_interacao_list {
    display: none;
    text-align: center;
    width: 300px;
    max-height: 288px;
    border: 1px solid #000;
    border-radius: 8px;
    background-color: #FFF;
    z-index: 9999;
    padding: 5px;
    margin-left: -250px;
    margin-top: -23px;
    position: relative;
}

#interacao_os {
    text-align: center;
    background-position: center;
    height: 45px;
    color: #3366FF;
    font-weight: bold;
    font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
    font-size: 9px;
}
</style>
<div id="aguardando_interacao_icon">
    <span></span><br>
</div>
<!-- <div id="interacao_os">
 -->    <span>Interações Pendentes</span>
    <div id="aguardando_interacao_list">
        <span id="aguardando_interacao_refresh" title="Atualizar lista de atendimentos pendentes" >
            <img src="imagens/icon/refresh.png" />
        </span>

        <span id="aguardando_interacao_close" title="Fechar" >
            <img src="imagens/icon/close.png" />
        </span>
   
        <h6>Interações pendentes</h6>

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
<!-- </div> -->

<script>
function atendimentosRefresh2 (params) {
    var list = $("#aguardando_interacao_list").find("table > tbody");

    if(params == 'manual'){
        var condLimpaCache = true
    }

    $.ajax({
        url: "os_aguardando_interacao_ajax.php",
        type: "POST",
        data: { busca_os_ag_interacao: true, limpaCache : condLimpaCache },
        beforeSend: function () {
            $(list).find("tr.loading").show();
            $(list).find("tr[class!=sem_pendencias][class!=loading]").remove();
        },
        complete: function (data) {
            data = $.parseJSON(data.responseText);

            $("#aguardando_interacao_icon > span").text(data.qtde);

            if (data.qtde > 9) {
                $("#aguardando_interacao_icon > span").css({ left: "41px" });
            } else {
                $("#aguardando_interacao_icon > span").css({ left: "43px" });
            }

            $(list).find("tr[class!=sem_pendencias][class!=loading]").remove();

            $(list).append("<tr>\
                              <td class='atendimento_alert'></td>\
                                <td class='atendimento_link'>OS</td>\
                                <td class='atendimento_link'>Pedido</td>\
                                <td>Data Interação</td>\
                            </tr>");

            if (data.qtde > 0) {
                $.each(data.oss, function (key, atendimento) {
                    var data = [];

                    //data = atendimento.data_programada.split("-");

                    var data_a = new Date(data[0], (data[1] - 1), data[2]);
                    data_a = data_a.getTime();

                    var data_b = new Date();
                    data_b = data_b.getTime();

                    if (data_a <= data_b) {
                        var img_alert = "<img src='imagens/icon/alert.png' title='Atendimento com a data de resolução atrasada' />";
                    } else {
                        var img_alert = "&nbsp;";
                    } 

                    //atendimento.data_programada = data[2]+"/"+data[1]+"/"+data[0];

                    $(list).append("<tr>\
                        <td class='atendimento_alert'>"+img_alert+"</td>\
                        <td class='atendimento_link'><a href='os_press.php?os="+atendimento.os+"' target='_blank' >"+atendimento.os+"</a></td>\
                        <td class='atendimento_link'><a target='_blank' href='pedido_admin_consulta.php?pedido="+atendimento.pedido+"'>"+atendimento.pedido+"</a></td>\
                        <td>"+atendimento.data_programada+"</td>\
                    </tr>");
                });
            } else {
                $(list).find("tr.sem_pendencias").show();
            }

            $(list).find("tr.loading").hide();
        }
    });
}

$(function () {
    atendimentosRefresh2();

    $("#aguardando_interacao_icon").click(function () {
        $("#aguardando_interacao_list").show();
    });

    $("#aguardando_interacao_close").click(function () {
        $("#aguardando_interacao_list").hide();
    });

    $("#aguardando_interacao_refresh").click(function () {
        atendimentosRefresh2("manual");
    });
});
</script>
