<style>
#relatorio_informativo_icone {
    margin:5px 0;
}

#relatorio_informativo_icone:hover {
    cursor:pointer;
}

#relatorio_informativo_icone img {
    width: 45px;
    border: 0px;
}

#relatorio_informativo_icone .rounder {
    background-color: #e82412;
    display:block;
    width:20px;
    height:20px;
    border-radius:100%;
    position:absolute;
    right:11px;
}

#relatorio_informativo_icone .countml {
    position:absolute;
    right:14px;
    font-size:14px;
    font-family: "Arial", "sans-serif";
    font-weight:bold;
    color:#FFF;
}

#pendencia_atendimentos_ri_refresh {
    float: left;
    margin-left: 3px;
    margin-top: 3px;
    cursor: pointer;
}

#pendencia_atendimentos_ri_close {
    float: right;
    margin-right: 3px;
    margin-top: 3px;
    cursor: pointer;
}

#pendencia_atendimentos_ri_list table {
    margin: 0 auto;
    width: 100%;
    border-collapse: collapse;
}

#pendencia_atendimentos_ri_list table tr th {
    background-color: #596D9B;
    color: #FFF;
}

#pendencia_atendimentos_ri_list table tr.sem_pendencias th {
    background-color: #FFF;
    color: #F00;
    display: none;
}

#pendencia_atendimentos_ri_list table tr.loading {
    display: none;
}

#pendencia_atendimentos_ri_list table tr.loading th {
    background-color: #FFF;
}

#pendencia_atendimentos_ri_list table tr.loading th img {
    width: 32px;
    height: 32px;
}

#pendencia_atendimentos_ri_list table tr td {
    border-bottom: 1px solid #A9A9A9;
}

#pendencia_atendimentos_ri_list h6 {
    margin-top: 5px;
    font-size: 11.9px !important;
}

.atendimento_alert_ml img {
    width: 12px;
    height: 12px;
}

.atendimento_link_ml a {
    text-decoration: none;
    color: #596D9B;
}

#pendencia_atendimentos_ri_list table tr:hover {
    background-color: #D0D0D0;
}

#pendencia_atendimentos_ri_list {
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
    position: absolute;
}
</style>
<div id="relatorio_informativo_icone">
    <span class="rounder"></span>
    <span class="countml"></span>
    <center><img src="imagens/botoes/analytics.png"/></center>
</div>
<div id="pendencia_atendimentos_ri_list">
    <span id="pendencia_atendimentos_ri_refresh" title="Atualizar lista de atendimentos pendentes" >
        <img src="imagens/icon/refresh.png" />
    </span>
    <span id="pendencia_atendimentos_ri_close" title="Fechar" >
        <img src="imagens/icon/close.png" />
    </span>
    <h6>Relatórios Informativos vinculados ao seu grupo de follow-up</h6>
    <div style="width:100%;max-height:232px;overflow-y:auto;" >
        <table border="0">
            <tbody>
                <tr class="sem_pendencias_ml">
                    <th colspan="3" >RI's</th>
                </tr>
                <tr class="loading_ml">
                    <th colspan="3" ><img src="imagens/loading_img.gif" /></th>
                </tr>
            </tbody>
        </table>
    </div>
</div>
<script>
Object.size = function(obj) {
    var size = 0, key;
    for (key in obj) {
        if (obj.hasOwnProperty(key)) size++;
    }
    return size;
};

function atendimentosRiRefresh() {
    var list = $("#pendencia_atendimentos_ri_list").find("table > tbody");

    $.ajax({
        url: "relatorio_informativo/relatorio_informativo_ajax.php",
        type: "POST",
        data: { icone_atendimentos_ri: true },
        beforeSend: function () {
            $(list).find("tr.loading_ml").show();
            $(list).find("tr[class!=sem_pendencias_ml][class!=loading]").remove();
        },
        complete: function (data) {
            data = $.parseJSON(data.responseText);

            if (Object.size(data) > 9) {
                $("#relatorio_informativo_icone .countml").text("9+");
            } else {
                $("#relatorio_informativo_icone .countml").text(Object.size(data));
                $("#relatorio_informativo_icone .countml").css('right', '17px');
                $("#relatorio_informativo_icone .countml").css('font-size', '15px');
            }

            $(list).append("<tr style='text-align:center'>\
                <td class='atendimento_link_ml'><strong>RI</strong></td>\
                <td><strong>Título</strong></td>\
                <td><strong>Data Transferencia</strong></td>\
            </tr>");

            if (Object.size(data) > 0) {
                $.each(data, function (ri, dados) {

                    $(list).append("<tr style='text-align:center'>\
                        <td class='atendimento_link_ml'><a href='cadastro_relatorio_informativo.php?ri_id=" + dados.ri + "' target='_blank' >" + dados.ri + "</a></td>\
                        <td><a href='cadastro_relatorio_informativo.php?ri_id=" + dados.ri + "' target='_blank' >" + dados.titulo + "</a></td>\
                        <td>" + dados.data_transferencia + "</td>\
                    </tr>");

                });
            }

            $(list).find("tr.loading_ml").hide();
        }
    });
}

$(function () {
    atendimentosRiRefresh();
    
    $("#relatorio_informativo_icone").click(function () {
        $("#pendencia_atendimentos_ri_list").show();
        $("#menu_sidebar").css("z-index", "666");
    });

    $("html").on('click','#pendencia_atendimentos_ri_close', function () {        
        $("#pendencia_atendimentos_ri_list").hide();
        $("#menu_sidebar").css("z-index", "40000");
    });

    $("#pendencia_atendimentos_ri_refresh").click(function () {
        atendimentosRiRefresh();
    });
});
</script>