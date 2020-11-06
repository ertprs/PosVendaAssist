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
        }
        if (nome == 'CREDIT_CARD') {
            $("input[name=integrador]").val(integrador);
            $(".meio_cartao").show();
            $("input[name=tipo_pagamento_nome]").val(nome);
            $("input[name=tipo_pagamento_valor]").val(valor);
            $('[data-toggle="tooltip"]').tooltip();
        } else {
            $(".meio_cartao").hide();
        }

    });

    $(".btn-finaliza-compra").on("click", function(){
        var nome = $("input[name=tipo_pagamento_nome]").val();
        var posto = $("input[name=posto]").val();
        var tipo_pagamento_nome = $("input[name=tipo_pagamento_nome]").val();
        var bandeira = $("input[name=bandeira]").val();
        if (tipo_pagamento_nome == "CREDIT_CARD") {

            PagSeguroDirectPayment.createCardToken({
                cardNumber: $("#cartao").val(),
                brand: $("#bandeira").val(),
                cvv: $("#cvv").val(),
                expirationMonth: $("#validadeMes").val(),
                expirationYear: $("#validadeAno").val(),
                success: function(response) {
                    $("#cardHashs").val(response.card.token)
                },
                error: function(response) {
                    console.log(response)
                },
                complete: function(response) {
                    //tratamento comum para todas chamadas
                }
            });
        }

        PagSeguroDirectPayment.onSenderHashReady(function(response){
            if(response.status == 'error') {
                return false;
            }
            var hash = response.senderHash; //Hash estará disponível nesta variável.
            $("input[name=sendHarsh]").val(PagSeguroDirectPayment.getSenderHash());
        });


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
                            let url_ret = data.pedido+"|"+bandeira+"|"+tipo_pagamento_nome+"|"+data.link_boleto+"|"+data.status_boleto;
                            window.location.href="loja_new.php?pg=finalizado&tipo=PS&result="+btoa(url_ret);
                        }, 4000); 
                    }
                }
            });
        }, 1500);
    });
 
    PagSeguroDirectPayment.setSessionId(sessao_id);

    $("input[name=cartao]").on("blur", function(){
        PagSeguroDirectPayment.getBrand({
            cardBin: $(this).val(),
            success: function(response) {
                $("#brand_cartao").html("<img title='"+response.brand.name+"'alt='"+response.brand.name+"' src='https://stc.pagseguro.uol.com.br/public/img/payment-methods-flags/68x30/"+response.brand.name+".png'>");
                if (response.brand.cvvSize > 0) {
                    $("input[name=cvv]").attr("maxlength", response.brand.cvvSize);
                }                
                $("input[name=bandeira]").val(response.brand.name);
                getParcelas();
            },
            error: function(response) {
                $("#brand_cartao").html("<img src='loja/layout/img/bandeira-erro.png'>");
            }
        });
    });


    PagSeguroDirectPayment.getPaymentMethods({
        amount: $("input#carrinhosubtotal").val(),
        success: function(response) {
            
            $.each(response.paymentMethods, function( index, value ) {
                if (index != 'CREDIT_CARD') {
                    return;
                }
                $.each(value.options, function( indexs, values ) {
                    if (values.status != "AVAILABLE") {
                        return;
                    }
                    $("#formas_cartao").append("<div class='span2' style='margin-left:7px;'><label for='' class='icone-pagamento'><img style='margin-top:5px' title='"+values.name+"' alt='"+values.name+"' src='https://stc.pagseguro.uol.com.br"+values.images.MEDIUM.path+"'></label></div>");
                });
            });
        },
        error: function(response) {
            $("#formas_cartao").html("<div class='span12'><div class='alert alert-danger' id='erro_bandeira'></div></div>");
            $.each(response.errors, function( index, value ) {
                $("#erro_bandeira").append("<p>Erro ao carregar as bandeiras de cartão.</p>");
                //$("#erro_bandeira").append("<p>"+index+" - "+value+"</p>");
            });
       }
    });

});



//MASCARAS
$(function($) {
    $('.input-mask-date').mask('99/99/9999');
    $('.cep').mask('99999-999');
    $('.cpf').mask('999.999.999-99');
    $('.celular').mask('99999-9999');
    $('.fone').mask('9999-9999');
    $('.dd_fone').mask('99');
    $('.dd_celular').mask('99');
    $('.cnpj').mask('99.999.999/9999-99');
    $('.data_nasc').mask('99/99/9999');
    $('.data').mask('99/99/9999');
    $('.hora_ini').mask('99:99');
    $('.hora_fim').mask('99:99');
    $('.mes').mask('99/9999');
    $('.data').mask('99/99/9999');
});

//FUNCOES DE AJUDAS
function limpa_formulario_cep(alerta) {
    if (alerta !== undefined) {
        alert(alerta);
    }

    inputsCEP.val('');
}

function get(url) {

    $.get(url, function(result) {
        $('.cep').val(result.cep);
        $('.endereco').val(result.logradouro);
        $('.bairro').val(result.bairro);
        $('.uf').val(result.uf);
        $('.cidade').val(result.localidade);
        $('.numero').focus();
    });
}

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
    PagSeguroDirectPayment.getInstallments({
        amount: $("input#carrinhosubtotal").val(),
        brand: $("input#bandeira").val(),
        maxInstallmentNoInterest: 6,
        success: function(response) {
            $.each(response.installments, function( index, value ) {
                $.each(value, function( indexs, values ) {
                    if (values.interestFree) {
                        $('#qtde_parcelas').append("<option value='"+values.quantity+'|'+values.installmentAmount+"'>"+values.quantity+" x R$ "+values.installmentAmount+" (sem juros)</option>");
                    } else {
                        $('#qtde_parcelas').append("<option value='"+values.quantity+'|'+values.installmentAmount+"'>"+values.quantity+" x R$ "+values.installmentAmount+" (com juros)</option>");
                    }
                });
            });
            
        },
        error: function(response) {
            $.each(response.errors, function( index, value ) {
                $('#qtde_parcelas').append("<option value=''>"+value+"</option>");
            });
            console.log(response)
        }
    });

}