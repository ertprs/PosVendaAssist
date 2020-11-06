$(function(){
    $('[data-toggle="tooltip"]').tooltip();

    $(".meios-pagamentos").on("click", function(){
        var nome = $(this).data('nome');
        var integrador = $(this).data('integrador');
        var valor = $(this).data('valor');
        $(".meio_pagamento").removeClass('active_meio_pagamento');
        $(".meio_pagamento_"+valor).addClass('active_meio_pagamento');
        if (nome == 'BOLETO') {
            $(".meio_boleto").show();
            $("input[name=integrador]").val(integrador);
            $("input[name=tipo_pagamento_nome]").val(nome);
            $("input[name=tipo_pagamento_valor]").val(valor);
            $(".btn-finaliza-compra ").removeAttr('disabled');
        } else {
           $(".btn-finaliza-compra ").attr('disabled', true);
           $(".meio_boleto").hide();
            $("formas_cartao").html("");
        }
    });

   
    
});
