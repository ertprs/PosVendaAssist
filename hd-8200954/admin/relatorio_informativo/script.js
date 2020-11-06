
var abaAtual, abaJaAtualizada = {};

$(function(){

    $.datepickerLoad(Array("data_abertura","data_chegada", "data_contencao","data_implementacao", "data_eficacia"));

    Shadowbox.init();

    $(document).on("click", "span[rel=lupa]", function(){

        let adicionaisLupa = [];
        let inputConfig = $(this).next("input");

        if ($(inputConfig).attr("tipo") == "produto") {

            adicionaisLupa = ["posicao"];

            let familia = $("#familia").val();

            if (familia != "") {

                adicionaisLupa.push("familia");

                $(inputConfig).attr("familia", familia);

            } else {

                alert("Selecione a família para pesquisar o produto!");
                return;
            }

        }

        $.lupa($(this), adicionaisLupa);

    });

    $(".id_produto").filter(function(){

        return $(this).val() != "";

    }).each(function(){

        carregaConstatados($(this).val(), $(this).closest(".linha-produto"));

    });

    var abaAtiva = $("li[role=presentation].active > a[role=tab]").attr("aria-controls");

    if (abaAtiva.length > 0) {

        refreshBoxUploaderAba(abaAtiva);

    }

    $('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {

        abaAtual = $(e.target).attr("aria-controls");

        refreshBoxUploaderAba(abaAtual);

    });

    $('.money-input').priceFormat({
        prefix: '',
        centsSeparator: ',',
        thousandsSeparator: '.'
    });

    var wordCountConf = {
        showParagraphs: false,
        showWordCount: false,
        showCharCount: false,
        countSpacesAsChars: false,
        countHTML: false,
        maxWordCount: -1,
        maxCharCount: -1,
    }

    CKEDITOR.replace("ri_posvenda[descricao_problema]", { enterMode : CKEDITOR.ENTER_BR, wordcount: wordCountConf, disableNativeSpellChecker: false});
    CKEDITOR.replace("ri_analise[descricao_problema]", { enterMode : CKEDITOR.ENTER_BR, wordcount: wordCountConf, disableNativeSpellChecker: false});
    CKEDITOR.replace("ri_analise[acao_contencao]", { enterMode : CKEDITOR.ENTER_BR, wordcount: wordCountConf, disableNativeSpellChecker: false});
    CKEDITOR.replace("ri_analise[causa_raiz]", { enterMode : CKEDITOR.ENTER_BR, wordcount: wordCountConf, disableNativeSpellChecker: false});
    CKEDITOR.replace("ri_acoes_corretivas[poka_yoke_justificativa]", { enterMode : CKEDITOR.ENTER_BR, wordcount: wordCountConf, disableNativeSpellChecker: false});
    CKEDITOR.replace("ri_acoes_corretivas[implementacao_permanente]", { enterMode : CKEDITOR.ENTER_BR, wordcount: wordCountConf, disableNativeSpellChecker: false});
    CKEDITOR.replace("ri_acoes_corretivas[verificacao_eficacia]", { enterMode : CKEDITOR.ENTER_BR, wordcount: wordCountConf, disableNativeSpellChecker: false});
    CKEDITOR.replace("ri[conclusao]", { enterMode : CKEDITOR.ENTER_BR, wordcount: wordCountConf, disableNativeSpellChecker: false});

    $(".bloquear-campos").each(function(){

        $(this).find("input, textarea, select").attr("disabled", true);
        $(this).find("button").remove();

        let status = $("#status_relatorio_informativo").val();

        let texto = "Você não tem permissão para preenchimento desta ABA";
        let classe = "warning";
        if (status == "Finalizado") {
            texto  = "O RI foi finalizado e não pode mais ser alterado";
            classe = "success";
        }

        let msg = $("<div>", {
            class: "alert alert-"+classe,
            text: texto,
            css: {
                "font-size": "16px"
            }
        });

        $(this).find(".btn-submit").closest("div").append(msg);

        $(this).find("input[type=button]").remove();

    });

    $(".obrigatorio").each(function(){

        let spanObrigatorio = $("<span>", {
            class: "asterisco",
            text: " * ",
            css: {
                color: "red"
            }
        });

        $(this).closest(".form-group").find("label").append( spanObrigatorio );

    });

    $(".obrigatorio").on("blur, change", function(){

        if ( $(this).val() != "" ) {

            $(this).closest(".form-group").removeClass("has-error");

        } else {

            $(this).closest(".form-group").addClass("has-error");

        }

    });

    $(".obrigatorio").change();

    $("#btn_nova_linha").click(function(){

        let divProduto = $(".linha-produto:first").clone();
        let contador   = parseInt($(".linha-produto:last").data("contador")) + 1;

        $( divProduto ).attr("data-contador", contador ).find("input[name=lupa_config]").attr("posicao", contador );
        $( divProduto ).find(".div-btn-excluir").show();
        $( divProduto ).find(".form-group").removeClass("has-error");
        $( divProduto ).find(".asterisco").remove();
        $( divProduto ).find("[type=text], [type=number]").val("");
        $( divProduto ).find("textarea").text("").val("");
        $( divProduto ).find("select").html($("<option>", { value: "", text: "Selecione o defeito constatado" }));

        $("#lista_produtos").append( divProduto );

    });

    $(document).on("click", ".btn-remover-produto", function(){

        $(this).closest(".linha-produto").remove();

    });

    $(document).on("click", ".btn-remover-corretiva", function(){

        $(this).closest("tr").remove();

    });

    $("#btn_nova_linha_corretiva").click(function(){

        let trCorretiva = $(".tabela-identificacao-acoes tbody > tr:first").clone();

        $( trCorretiva ).find("textarea").text("").val("").removeClass("obrigatorio");
        $( trCorretiva ).find(".form-group").removeClass("has-error");
        $( trCorretiva ).find(".div-btn-excluir-corretiva").show();

        $(".tabela-identificacao-acoes tbody").append( trCorretiva );

    });

    if ($(".revisar_docs").val() == "t") {
        $(".documentos-revisar").show("fast");
    }

    $(".revisar_docs").click(function(){

        if ($(this).val() == "t") {

            $(".documentos-revisar").show("fast");

        } else {

            $(".documentos-revisar").hide("fast");

        }

    });

    $(".btn-submit").click(function(){

        let form = $(this).closest("form");

        let inputsVazios = $( form ).find("input.obrigatorio, textarea.obrigatorio, select.obrigatorio").filter(function(){
            return !$(this).val();
        }).length;

        let ckeditor_textarea = $( form ).find(".textarea_ckeditor").attr("name");

        erro_ckeditor = false;

        if (ckeditor_textarea != undefined) { 
            if (ckeditor_textarea.length > 0) {

                ckeditor_valor = $.trim(CKEDITOR.instances[ckeditor_textarea].getData());

                if (ckeditor_valor == "") {
                    erro_ckeditor = true;
                }

            }
        }

        if ( inputsVazios == 0 && !erro_ckeditor) {

            $( form ).submit();

        } else {

            if (erro_ckeditor) {

                $( form ).find("iframe").css({ border: "3px solid red" });

            }

            $(".alert-error").show("fast");
            $("#texto_erro").text("Preencha os campos obrigatórios");

            $("html").scrollTop(0);

            setTimeout(function(){
                $(".alert-error").hide("fast");
            }, 6000);

        }

    });

    $(".box-uploader-anexos").css({
        width: "100%"
    });

    $("#transferir").click(function(){

        let riFollowup      = $("#transferir_para").val();
        let status          = $("#status_ri").val();
        let riTransferencia = $("#posvenda #transferencia_id").val();
        let admin           = $("#transferencia_admin").val();
        let ri              = $("#posvenda input[name=ri_id]").val();

        if (riFollowup == "") {
            alert("Selecione um Grupo para transferir");
            return;
        }

        if (riTransferencia == "") {
            alert("Erro: id transferencia vazio");
            return;
        }

        $("#transferir").attr("disabled", true).text("..aguarde");

        $.ajax({
            url: "relatorio_informativo/relatorio_informativo_ajax.php",
            type: "POST",
            dataType:"JSON",
            data: {
                ri_transferencia: {
                    ri_followup: riFollowup,
                    status: status,
                    id: riTransferencia,
                    admin: admin
                },
                ri_id: ri
            }
        })
        .done(function(data) {
            
            if (data.success) {

                alert("RI Transferido com sucesso!");

                location.reload();

            } else {

                alert("Algo deu errado!");

            }

            $("#transferir").attr("disabled", false).text("Transferir");

        });

    });

    $("#gerar_pdf").click(function(){

        let ri = $(this).data("ri");

        $.ajax({
            url: "relatorio_informativo/relatorio_informativo_ajax.php",
            type: "POST",
            data: {
                gerar_pdf: true,
                ri: ri
            },
            beforeSend: function () {
                loading("show");
            },
            complete: function (data) {
                window.open(data.responseText, "_blank");

                loading("hide");
            }
        });

    });

    $(".valores-custo").change(function(){

        let total = 0;
        $(".valores-custo").map(function(){
            total += formataValor($(this).val());

        });

        $("#total").val(numberToReal(total));

    });

});

function formataValor(valor) {

    if (valor != "") {

        valor = valor.replace(".","");
        valor = valor.replace(",",".");

        valor = parseFloat(valor);

        return valor;

    }

}

function numberToReal(numero) {
    var numero = numero.toFixed(2).split('.');
    numero[0] = numero[0].split(/(?=(?:...)*$)/).join('.');
    return numero.join(',');
}

function refreshBoxUploaderAba(aba) {

    let contexto;

    switch(aba) {
      case "posvenda":

        contexto = "ri_posvenda";

        break;
      case "acao_contencao":

        contexto = "ri_contencao";

        break;
      case "causa_analise":

        contexto = "ri_causa";
        
        break;
      case "identificacao_acoes":

        contexto = "ri_identificacao";
        
        break;
      case "implementacao_acoes":

        contexto = "ri_implementacao";
        
        break;
      case "eficacia_acoes":

        contexto = "ri_eficacia";
        
        break;
      case "conclusao":

        contexto = "ri_conclusao";
        
        break;
      default:

      return;
    }

    var novaConfig = objDadosBoxuploader[contexto];
    
    boxUploader = new BoxUploader(novaConfig);

    boxUploader.init();

    //atualizar aba apenas uma vez
    if (abaJaAtualizada[contexto] != true) {

        boxUploader.showFile();

        abaJaAtualizada[contexto] = true;

    }

    setTimeout(function(){
        $(".box-uploader-carregando").filter(function(){
            return $(this).find(".fa-spinner").length > 0;
        }).find(".fa-spinner").remove();
    }, 1500);

    setTimeout(function(){
        $(".box-uploader-carregando").filter(function(){
            return $(this).find("div").length == 0;
        }).remove();
    }, 5000);

}

function retorna_produto (retorno) {

    let divProduto = $("div.linha-produto[data-contador="+retorno.posicao+"]");

    $(divProduto).find(".id_produto").val(retorno.produto);
    $(divProduto).find(".referencia_produto").val(retorno.referencia).change();
    $(divProduto).find(".descricao_produto").val(retorno.descricao).change();

    let camposObrig = $(divProduto).find(".qtde, .defeito_constatado");

    $(camposObrig).addClass("obrigatorio");
    $(camposObrig).closest(".form-group").find("label").append('<span class="asterisco" style="color: rgb(255, 0, 0);"> * </span>');
    $(camposObrig).closest(".form-group").addClass("has-error");

    carregaConstatados(retorno.produto, divProduto);

}

function carregaConstatados(produto, divProduto) {

    $.ajax({
        url: "cadastro_os.php",
        type: "POST",
        dataType:"JSON",
        data: {
            ajax: true,
            ajax_busca_defeito_constatado: true,
            produto: produto,
            fora_garantia: false
        }
    })
    .done(function(data) {
        
        $(divProduto).find(".defeito_constatado option:not(:first)").remove();

        if (data.defeitos_constatados.length == 0) {
            alert("Nenhum defeito constatado encontrado para o produto selecionado");
        }

        let defeitoConstatadoAnterior = $(divProduto).find(".defeito_constatado_anterior").val();

        $.each(data.defeitos_constatados, function(key, value) {

            var option = $("<option></option>", {
                text: value.descricao,
                value: value.defeito_constatado,
                selected: defeitoConstatadoAnterior == value.defeito_constatado
            });

            $(divProduto).find(".defeito_constatado").append(option);

        });

    });

}


var singleSelect = true;  // Allows an item to be selected once only
var sortSelect = true;  // Only effective if above flag set to true
var sortPick = true;  // Will order the picklist in sort sequence

// Initialise - invoked on load
function initIt() {
  var pickList = document.getElementById("PickList");
  var pickOptions = pickList.options;
  pickOptions[0] = null;  // Remove initial entry from picklist (was only used to set default width)
}

// Selection - invoked on submit
function selIt(btn) {
    var pickList = document.getElementById("PickList");
    if (pickList == null) return true;
    var pickOptions = pickList.options;
    var pickOLength = pickOptions.length;
/*  if (pickOLength < 1) {
        alert("Nenhuma produto selecionado!");
        return false;
    }*/
    for (var i = 0; i < pickOLength; i++) {
        pickOptions[i].selected = true;
    }
/*  return true;*/
}

function delIt() {
  var pickList = document.getElementById("PickList");
  var pickIndex = pickList.selectedIndex;
  var pickOptions = pickList.options;
  while (pickIndex > -1) {
    pickOptions[pickIndex] = null;
    pickIndex = pickList.selectedIndex;
  }
}

// Adds a selected item into the picklist
function addIt() {

    if ($('#admin_analise').val()=='')
        return false;

    var pickList = document.getElementById("PickList");
    var pickOptions = pickList.options;
    var pickOLength = pickOptions.length;

    pickOptions[pickOLength] = new Option($('#admin_analise option:selected').text());
    pickOptions[pickOLength].value = $('#admin_analise').val();

    if (sortPick) {
        var tempText;
        var tempValue;
        // Sort the pick list
        while (pickOLength > 0 && pickOptions[pickOLength].value < pickOptions[pickOLength-1].value) {
            tempText = pickOptions[pickOLength-1].text;
            tempValue = pickOptions[pickOLength-1].value;
            pickOptions[pickOLength-1].text = pickOptions[pickOLength].text;
            pickOptions[pickOLength-1].value = pickOptions[pickOLength].value;
            pickOptions[pickOLength].text = tempText;
            pickOptions[pickOLength].value = tempValue;
            pickOLength = pickOLength - 1;
        }
    }

    pickOLength = pickOptions.length;

    $('#admin_analise option:first').prop("selected", true);

}
