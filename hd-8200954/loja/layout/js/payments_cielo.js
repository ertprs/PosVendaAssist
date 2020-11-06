$(function(){
    $('[data-toggle="tooltip"]').tooltip();

    $('#cpf_cartao').on('keyup', function() {

        $(this).unmask("99.999.999/9999-99");
        $(this).unmask("999.999.999-99");
        $(this).attr('maxlength', "");
        if($(this).val().length == 11) {
            $(this).mask("999.999.999-99");

        } else if ($(this).val().length > 17) {
            $(this).mask("99.999.999/9999-99");
            $(this).attr('maxlength', 14);
        } 

    });

    $('#bx__cartao  input, select').on('change', function(e) {
        var total = $("#bx__cartao").find('.obrigatorio2').length;
        var novo_total = 0;
        $("#bx__cartao").find('.obrigatorio2').each(function(index, campos){
           if ($(campos).val() == '') {
                $(campos).focus();
                return false;
            } 
            total = total-1; 
        });
        if (total <= 0) {
            $(".btn-finaliza-compra ").removeAttr('disabled');
        } else {
           $(".btn-finaliza-compra ").attr('disabled', true);
        }
    });

    $(document).on("click", ".icone-bandeiras", function(){
        var bandeira = ($(this).data("id"))

        $(".icone-bandeiras").removeClass('active_bandeira');
        $(this).addClass('active_bandeira');
        $("#brand_cartao").html("<img style='height: 30px;' title='"+bandeira+"'alt='"+bandeira+"' src='loja/layout/img/bandeiras/"+bandeira.toLowerCase()+".png'>");

        $("input[name=bandeira]").val(bandeira);
        $.ajax({
            type: "POST",
            url:  "loja/ajax/ajax_carrega_bandeira.php?carrega_trava_ccv=true",
            data: {bandeira:bandeira},
            complete: function(data) {
                $("input[name=cvv]").attr("maxlength", data.responseText);
                getParcelas();
            }
        });
    });

    $(".meios-pagamentos").on("click", function(){
        var nome = $(this).data('nome');
        var valor = $(this).data('valor');
        var integrador = $(this).data('integrador');
        $(".meio_pagamento").removeClass('active_meio_pagamento');
        $(".meio_pagamento_"+valor).addClass('active_meio_pagamento');

        if (nome == 'BOLETO') {
            $("input[name=integrador]").val(integrador);
            $(".meio_boleto").show();
            $("input[name=tipo_pagamento_nome]").val(nome);
            $("input[name=tipo_pagamento_valor]").val(valor);
            $(".btn-finaliza-compra ").removeAttr('disabled');
        } else {
           $(".btn-finaliza-compra ").attr('disabled', true);
           $(".meio_boleto").hide();
            $("formas_cartao").html("");
        }
        if (nome == 'CREDIT_CARD') {
            $("input[name=integrador]").val(integrador);
            $(".meio_cartao").show();
            $("input[name=tipo_pagamento_nome]").val(nome);
            $("input[name=tipo_pagamento_valor]").val(valor);
            $('[data-toggle="tooltip"]').tooltip();

            $.ajax({
                type: "POST",
                url:  "loja/ajax/ajax_carrega_bandeira.php?carrega_bandeiras=true",
                beforeSend: function() {
                    $("#formas_cartao").html('<div class="span12 tac"><img src="imagens/loading.gif" style="z-index:11" /></div>');
                },
                complete: function(data) {
                    $("#formas_cartao").html('');
                    $("#formas_cartao").html(data.responseText);
                }
            });

        } else {
            $(".meio_cartao").hide();
        }

    });

    $(".btn-finaliza-compra").on("click", function(){
        var nome = $("input[name=tipo_pagamento_nome]").val();
        var posto = $("input[name=posto]").val();
        var tipo_pagamento_nome = $("input[name=tipo_pagamento_nome]").val();
        var bandeira = $("input[name=bandeira]").val();

        if (tipo_pagamento_nome != 'BOLETO') {
            if (bandeira == '') {
                alert("Escolha uma Bandeira");
                $(".btn-finaliza-compra ").attr('disabled', true);
                return false;
            } else {
                $(".btn-finaliza-compra ").removeAttr('disabled');
            }
        }

        if ($("input[name=usaEnvio]").val() == 'true') {
            checado = $("input[name=formaEnvio]").is(':checked')
            if ($("input[name=tipoEnvio]").val() == '' || !checado) {
                alert("Selecione um tipo de frete");
                return false;
            }
        }

        setTimeout(function(){ 
            var form_dados = $("#form_finaliza_compra").serialize();
            $.ajax({
                type: "POST",
                dataType: "json",
                url:  "loja/ajax/ajax_finaliza_com_checkout.php?method_pagamento="+nome+"&posto="+posto,
                data : form_dados,
                beforeSend: function() {
                    $("#loading-loja").show();
                    $("#loading-block-loja").show();
                },
                success: function(data)
                {
                    if (data.erro == true) {
                        $('#txt-status-pedido-loja').css("color", "red");
                        $('#txt-status-pedido-loja').html(data.msg);
                        setTimeout(function(){ 
                            $("#loading-loja").hide();
                            $("#loading-block-loja").hide();
                            $('#txt-status-pedido-loja').css("color", "black");
                            $('#txt-status-pedido-loja').html("Aguarde estamos gerando seu pedido");
                            return false;
                        }, 4000);
                        
                    } else {
                        $('#txt-status-pedido-loja').css("color", "green");
                        $('#txt-status-pedido-loja').html(data.msg);
                        setTimeout(function(){ 
                            $("#loading-loja").hide();
                            $("#loading-block-loja").hide();
                            if (tipo_pagamento_nome == 'BOLETO') {
                                var resultado = [
                                    {
                                        "dados_frete" : [
                                            {
                                                "codigoEnvio" : data.codigoEnvio,
                                                "servicoEnvio" : data.servicoEnvio,
                                                "diasEnvio" : data.diasEnvio,
                                                "valorEnvio" : data.valorEnvio
                                            }
                                        ],
                                        "status_cartao" : data.status_cartao,
                                        "pedido" : data.pedido,
                                        "bandeira" : data.bandeira,
                                        "tipo_pagamento_escolhido" : tipo_pagamento_nome,
                                        "link_boleto" : data.link_boleto,
                                        "status_boleto" : data.status_boleto
                                    }
                                ];

                                var result = btoa(JSON.stringify(resultado));
                                window.location.href="loja_new.php?pg=finalizado&result="+result;
                                
                            } else {
                                var resultado = [
                                    {
                                        "dados_frete" : [
                                            {
                                                "codigoEnvio" : data.codigoEnvio,
                                                "servicoEnvio" : data.servicoEnvio,
                                                "diasEnvio" : data.diasEnvio,
                                                "valorEnvio" : data.valorEnvio
                                            }
                                        ],
                                        "status_cartao" : data.status_cartao,
                                        "pedido" : data.pedido,
                                        "bandeira" : data.bandeira,
                                        "tipo_pagamento_escolhido" : tipo_pagamento_nome,
                                        "code_auto" : data.code_auto
                                    }
                                ];

                                var result = btoa(JSON.stringify(resultado));
                                window.location.href="loja_new.php?pg=finalizado&result="+result;
                            }
                        }, 4000); 
                    }
                }
            });
        }, 1500);
    });
    
});



//FUNCOES DE AJUDAS

function isEmailAddress(email){
    var s = email;
    var filter=/^[A-Za-z][A-Za-z0-9_.-]*@[A-Za-z0-9_.-]+\.[A-Za-z0-9_.]+[A-za-z]$/;

    if (s.length == 0 ){
        return false;
    }

    if (filter.test(s)){
        return true;
    }else{
        return false;
    }
}

function getParcelas() {

    $('#qtde_parcelas').html('');
    var bandeira = $("input#bandeira").val();
    var total = $("input#carrinhosubtotal").val();
    $.ajax({
            type: "POST",
            dataType: "json",
            url:  "loja/ajax/ajax_carrega_parcelas.php?integrador=cielo",
            data : {total:total, bandeira:bandeira},
            success: function(response)
            {

                if (response.erro == true) {
                    $('#qtde_parcelas').append("<option value=''>Erro ao gerar as parcelas</option>");
                } else {
                    $.each(response.parcelas, function( indexs, values ) {
                        $('#qtde_parcelas').append("<option value='"+values.parcela+'|'+values.valor_parcela+"'>"+values.parcela+" x R$ "+values.valor_parcela_formatado+" (sem juros)</option>");
                    });
                }
            }
     });
}