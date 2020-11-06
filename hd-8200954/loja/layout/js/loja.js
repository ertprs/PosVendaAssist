$(function() {

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

    var tamanhoDiv = new Array;
    $.each($("#vitrini_produtos .eco_vitrine_nome_produto"), function(key, dados) { 
        tamanhoDiv[key] = $(dados).outerHeight();
    });

    var tamanhoMaior = Math.max.apply(null,tamanhoDiv)
    $.each($("#vitrini_produtos .eco_vitrine_nome_produto"), function(key, dados) { 
        $(dados).css( "height", tamanhoMaior+"px" );
    });

    var tamanhoDivQuadro = new Array;
    $.each($("#vitrini_produtos .quadroDiv"), function(key, dados) { 
        tamanhoDivQuadro[key] = $(dados).outerHeight();
    });

    var tamanhoMaiorQuadro = Math.max.apply(null,tamanhoDivQuadro)
    $.each($("#vitrini_produtos .quadroDiv"), function(key, dados) { 
        $(dados).css( "height", tamanhoMaiorQuadro+"px" );
    });

    var tamanhoDivGrade = new Array;
    $.each($("#vitrini_produtos .grade"), function(key, dados) { 
        tamanhoDivGrade[key] = $(dados).outerHeight();
    });

    var tamanhoMaiorGrade = Math.max.apply(null,tamanhoDivGrade)
    $.each($("#vitrini_produtos .grade"), function(key, dados) { 
        $(dados).css( "height", tamanhoMaiorGrade+"px" );
    });

    $("input[price=true]").each(function () {
        $(this).priceFormat({
            prefix: '',
            thousandsSeparator: '',
            centsSeparator: '',
            centsLimit: parseInt(0)
        });
    });


    $(document).on("click", ".btn_add_grade", function(){
        var codigo_produto = $("input[name=codigo_produto]").val();
        var item_grade = $("input[name=item_grade_"+codigo_produto+"]").val();

        if (item_grade == "") {
            alert("Selecione um Tamanho");
            return false;
        }
        $("form").submit();

    });

    $(document).on("click", ".item_grade", function(){
        var grade_selecionada = $(this).data("tamanho");
        var produto = $(this).data("produto");
        $(".item_grade").removeClass('ativo');
        $(this).addClass('ativo');
        $("input[name=item_grade_"+produto+"]").val('')
        $("input[name=item_grade_"+produto+"]").val(grade_selecionada)

    });


    /* JS REFERENTE A HOME DA LOJA */

    //EXIBE E ESCONDE MENU TELECONTROL
    $('.btn-show-hide').click(function(){
        var targ = $(this).data('target');
        if (targ == 'show') {
            $('#alltop').collapse('show');
            $(this).data('target', 'hide');
            $(".label_exb_menu").text('OCULTAR MENU TELECONTROL');
            $(".sidebar").css( "margin-top", "280px" ); 
        } else {
            $('#alltop').collapse('hide');
            $(this).data('target', 'show')
            $(".label_exb_menu").text('EXIBIR MENU TELECONTROL');
            $(".sidebar").css( "margin-top", "150px" ); 

        }
    });
    $('.btn-pagar').click(function(){
        $(this).button('loading');
     });
    /* JS REFERENTE AO DETALHE DO PRODUTO */



    //AVISE ME
    $('html').on("click", ".btn-avise-me", function(){
        var id = $(this).data("id");

        if (id == '') {
            return false;
        }

        $.ajax({
            url: 'loja/ajax/ajax_insere_avise_me.php',
            type: "POST",
            dataType:"JSON",
            data: { 
                ajax_insere_avise_me: true,
                id: id
            }
        }).done(function(data) {

            if (data.erro == true) {
                alert(data.msg);
                return false;
            } else {
                alert(data.msg);
            }
        });

    });


    //TROCA IMAGENS NA GALERIA DE FOTOS - DETALHE DO PRODUTO
    $('.eco_detalhe_thumb').click(function(){
        var imagem = $(this).attr("src");
        $('#grande').attr("src", imagem);
        $('.zoomImg').attr("src", imagem);
    }); 

    //CALCULA FRETE
    $('.btn-detalhe-calcula-frete').click(function(){
        var simula_cep = $("#simula_cep");

        if (simula_cep.val() == "") {
            alert("Digite um CEP!");
            simula_cep.focus();
            return false;
        }
        $("#modal_calcula_frete").modal("show");

        $.ajax({
            url: 'loja/calcula_frete.php',
            type: "POST",
            dataType:"JSON",
            data: {
                cep: simula_cep.val()
            },
            beforeSend: function(){
                $('.loading').html("<div align='center'><img src='imagens/loading_img.gif' style='margin: 0 auto;' /></div>");
            }
        }).done(function(data) {

            $('.loading').html("");
            if (data.erro == true) {
                alert(data.msg);
            } else {

                if (data.formas.length > 0) {

                    var iniTabela = "<table class='table table-bordered table-hover'>\
                                        <thead>\
                                        <tr bgcolor='#474883' style='color:#fac81a;'>\
                                            <th>Forma de Envio</th>\
                                            <th>Custo de Envio</th>\
                                            <th>Prazo de Entrega</th>\
                                        </tr></thead><tbody>";
                    var contTabela = "";
                    $.each(data.formas, function(key, dados) {
                        contTabela += "<tr>\
                                         <td>"+dados.Forma+"</td>\
                                         <td>R$ "+dados.Valor+"</td>\
                                         <td>"+dados.PrazoEntrega+" dia(s)</td>\
                                       </tr>"; 
                  
                    });
                    var fimTabela = "</tbody></table>";


                    $("#resultado").html(iniTabela+""+contTabela+""+fimTabela);
                }
            }
        });
    });

    /* JS REFERENTE AO CARRINHO DE COMPRAS */

    //CALCULO DE FRETE
    $('.btn-calcula-frete-carrinho').click(function(){
        $(this).button('loading');
        if ($("#cep_carrinho").val() == "") {
            $("#eco_calculo_frete").addClass("error");
            $("#eco_msn_cep_vazio").show("slow");
            $("#eco_msn_cep_vazio").html("Digite um CEP!");
            $('.btn-calcula-frete-carrinho').button('reset');
            $("#cep_carrinho").focus();
            return false;
        }
        $("#eco_carrinho_formas_envio").html('<img src="imagens/loading_indicator_big.gif"> Aguarde...');
        setTimeout(function(){ 
            $('.btn-calcula-frete-carrinho').button('reset');
            $("#eco_carrinho_formas_envio").html('<li>'+
                                                '<label class="radio">'+
                                                    '<input type="radio" name="formaEnvio" value="2" data-valor="10.274">'+
                                                    '<b class="eco_carrinho_prazo_correio">10 dias úteis</b> '+
                                                    '<span class="eco_carrinho_preco_correio">R$ 10,27</span> '+
                                                    '<span class="eco_carrinho_tipo_correio">PAC</span>'+
                                                '</label>'+
                                            '</li>'+
                                            '<li>'+
                                                '<label class="radio">'+
                                                    '<input type="radio" name="formaEnvio" value="3" data-valor="11.6265">'+
                                                    '<b class="eco_carrinho_prazo_correio">4 dias úteis</b> '+
                                                    '<span class="eco_carrinho_preco_correio">R$ 11,63</span> '+
                                                    '<span class="eco_carrinho_tipo_correio">e-SEDEX</span>'+
                                                '</label>'+
                                            '</li>'+
                                            '<li>'+
                                                '<label class="radio">'+
                                                    '<input type="radio" name="formaEnvio" value="1" data-valor="15.641">'+
                                                    '<b class="eco_carrinho_prazo_correio">4 dias úteis</b> '+
                                                    '<span class="eco_carrinho_preco_correio">R$ 15,64</span> '+
                                                    '<span class="eco_carrinho_tipo_correio">SEDEX</span>'+
                                                '</label>'+
                                            '</li>'); 
            
        }, 3000);
                                
    });
    
    //LIMPA CLASS ERRO NO CAMPO DE CEP (QUANDO VAZIO)
    $('#cep_carrinho').change(function(){
        $("#eco_msn_cep_vazio").html("");
        $("#eco_calculo_frete").removeClass("error");
        $("#eco_msn_cep_vazio").hide("slow");
    });
    
    //REMOVE ITEM DO CARRINHO DE COMPRA
    $('.btn-remove-item-carrinho').click(function(){
        confirma  = confirm("Deseja remover esse registro");
        if (confirma) {
            var id = $(this).data("id");
            var kit = $(this).data("kit");
            $.ajax({
                url: 'loja/ajax/ajax_carrinho.php',
                type: "POST",
                dataType:"JSON",
                data: { 
                    ajax_remove_item: true,
                    id: id,
                    kit : kit
                }
            }).done(function(data) {

                $('#eco_carrinho_formas_envio').html("");
                if (data.erro == true) {
                    $(".mensagens").html('<div id="msg_erro_item" class="alert alert-danger alert-carrinho"><h5>'+data.msg+'</h5></div>');
                } else {
                    if (kit == true) {
                        $("#eco_item_carrinho_tr_kit_"+id).remove();
                        $(".eco_item_carrinho_tr_kit_"+id).remove();
                    } else {
                        $("#eco_item_carrinho_"+id).remove();
                    }
                    $(".mensagens").html('<div id="msg_erro_item" class="alert alert-success alert-carrinho"><h5>'+data.msg+'</h5></div>');
                    location.href="loja_new.php?pg=carrinho";

                }
            });
        } else {
            return false;
        }

        
    });

    //ATUALIZA QUANTIDADE DE ITEM NO CARRINHO
    $('html').on("click", ".btn-atualiza-item-carrinho", function(){
        var id   = $(this).data("id");
        var kitpeca   = $(this).data("kitpeca");
        var idcarrinho   = $(this).data("idcarrinho");
        var qtde = $(".qtde_carrinho_"+id).val();

        if (id == '') {
            return false;
        }
        
        $.ajax({
            url: 'loja/ajax/ajax_carrinho.php',
            type: "POST",
            dataType:"JSON",
            data: { 
                ajax_atualiza_item: true,
                id: id,
                kitpeca: kitpeca,
                idcarrinho: idcarrinho,
                qtde: qtde
            }
        }).done(function(data) {
            console.log(data)
            if (data.erro == true) {
                $(".mensagens").html('<div id="msg_erro_item" class="alert alert-danger alert-carrinho"><h5>'+data.msg+'</h5></div>');
            } else {
                location.href="loja_new.php?pg=carrinho";
            }
        });

    });

    //CALCULA TOTAL PEDIDO NO CARRINHO DE COMPRA TOTALPEDIDO+FRETE
    $('html').on("click", ".formaEnvio", function(){
        var totalreal   = parseFloat($("#carrinhosubtotal").data("totalreal"));
        var valorFrete  = parseFloat($(this).data("valor"));
        var totalPedido = parseFloat($("#carrinhosubtotal").val());
        var tipoEnvio   = $(this).data("forma") + ' - ' + $(this).closest('.radio').find('.eco_carrinho_prazo_correio').text();
        var carrinho    = $("input[name=loja_b2b_carrinho]").val();
        
        $("#total_pedido_frete").html("");
        $("#total_pedido_frete").html("R$ " + parseFloat(totalreal+valorFrete).toFixed(2).replace(".",","));
        $("#carrinhosubtotal").val(totalreal+valorFrete);

        $.ajax({
            url: 'loja/ajax/grava_frete.php',
            type: "POST",
            dataType:"JSON",
            data: { 
                carrinho : carrinho,
                tipoEnvio : tipoEnvio,
                totalFrete : valorFrete
            }
        }).done(function(data){
            if (data == "erro") {
                alert("erro ao gravar frete");
            }
        });

    });


    //AJAX RESPONSAVEL POR FINALIZAR A COMPRA SEM CHECKOUT
    $('.btn-finaliza-sem-checkout').click(function(){
        var id = $(this).data("id");
        $.ajax({
            url: 'loja/ajax/ajax_finaliza_sem_checkout.php',
            type: "POST",
            dataType:"JSON",
            data: { 
                ajax_insere_pedido_sem_checkout: true,
                id: id
            },
            beforeSend: function () {
                $("#loading-loja").show();
                $("#loading-block-loja").show();
            }
        }).done(function(data) {
           
            if (data.erro == true) {
                $('#txt-status-pedido-loja').css("color", "red");
                $('#txt-status-pedido-loja').html(data.msg);

            } else {
                setTimeout(function(){
                    $('#txt-status-pedido-loja').css("color", "green");
                    $('#txt-status-pedido-loja').html(data.msg);
                }, 2000);
                 setTimeout(function(){ 
                    $("#loading-loja").hide();
                    $("#loading-block-loja").hide();
                    location.href="loja_new.php?pg=finalizado&pedido="+data.pedido;
                }, 6000);

            }
        });
        
    });

    setTimeout(function(){ 
        $('.alert-carrinho').hide("");
        $('#msg_erro_item').hide("");
    }, 5000);

    $(document).on("click", ".formaEnvio", function(){
        $("#btn_fecha_pedido").prop("disabled", false);
    });

}); 

function calculaFreteCarrinho(cep, metodo, carrinho = "", id_fornecedor = "") {

    if (cep == "") {
        alert("CEP não encontrado! Por favor, verifique se os seus dados cadastrais estão corretos e tente novamente, ou entre em contato com o fabricante");
        return false;
    }

    $.ajax({
        url: 'loja/ajax/calcula_frete.php?metodo='+metodo,
        type: "POST",
        dataType:"JSON",
        data: {
            cep: cep,
            id_fornecedor: id_fornecedor,
            carrinho: carrinho
        },
        beforeSend: function(){
            $("#eco_carrinho_formas_envio").html('<img src="imagens/loading_indicator_big.gif"> Aguarde...');
            $("#btn_fecha_pedido").prop("disabled", true);
        }
    }).done(function(data) {
        $('#eco_carrinho_formas_envio').html("");
        
        if (data.erro == true) {
            alert(data.msg);
        } else {
            var formaEnvioSelecionado = $("input[name=forma_envio]").val();

            dadosLi = "";
                $.each(data.formas, function(key, dados) {
                    dadosLi += '<li>'+
                                '<label class="radio">'+
                                    '<input type="radio" class="formaEnvio" name="formaEnvio" value="'+dados.nome+'|'+dados.prazoEntrega+'|'+dados.valor+'|'+dados.codigo+'" data-valor="'+dados.valor+'" data-forma="'+dados.nome+'" >'+
                                    '<input type="hidden" class="tipoEnvio" name="tipoEnvio" value="correios">'+
                                    '<span class="eco_carrinho_tipo_correio">'+dados.nome+'</span> - '+
                                    '<span class="eco_carrinho_prazo_correio" style="font-weight: bolder;">'+dados.prazoEntrega+' dia(s)</span> '+
                                    '<span class="eco_carrinho_preco_correio">R$ '+dados.valor+'</span> '+
                                '</label>'+
                            '</li>'; 
              
                });
                $("input[name=tipoEnvio]").val(metodo);

                $("#eco_carrinho_formas_envio").html(dadosLi);
        }
    });

}

    
    function add_carrinho_com_grade(produto, tamanho) {
        if (tamanho == "" || tamanho == undefined) {
            alert("Selecione um Tamanho");
            return false;
        }
        window.location.href='loja_new.php?pg=carrinho&codigo_produto='+produto+'&item_grade_'+produto+'='+tamanho;
    } 

function atualizaTotalItens() {

    $.ajax({
        url: 'loja/ajax/ajax_carrinho.php?ajax_atualiza_total_item=true',
        type: "POST",
        dataType:"JSON",
        beforeSend: function(){
            $(".totalItens").html('atualizando...');
        }
    }).done(function(data) {
        $(".totalItens").html(data.total+" un");
    });

}

function setCookie(cname, cvalue, exHours) {

  var d = new Date();
  d.setTime(d.getTime() + exHours * 3600 * 1000);
  var expires = "expires="+ d.toUTCString();
  document.cookie = cname + "=" + cvalue + ";" + expires + ";path=/";
}

function getCookie(cname) {

  var name = cname + "=";
  var decodedCookie = decodeURIComponent(document.cookie);
  var ca = decodedCookie.split(';');
  for(var i = 0; i <ca.length; i++) {
    var c = ca[i];
    while (c.charAt(0) == ' ') {
      c = c.substring(1);
    }
    if (c.indexOf(name) == 0) {
      return c.substring(name.length, c.length);
    }
  }
  return "";
}

// Verifica se existe itens no carrinho adicionados a mais de 48h e os remove
function removeItensExpiradosCarrinho(){

    /* Salva o cookie para verificar se os itens já foram removidos ao entrar na 
     loja e evitar que seja removido enquando o cliente faz pedidos */
    if(getCookie('itensExpiradosRemovidos') != 'true'){

        //Irá expirar em duas horas
        setCookie('itensExpiradosRemovidos', true, 2);

        $.ajax({
            url: 'loja/ajax/ajax_carrinho.php',
            type: "POST",
            dataType:"JSON",
            data: { 
                ajax_remove_itens_expirados_carrinho: true,
            },
            success:function(response){

                if(response.erro == false && response.removidos == true){
                    location.reload();
                }

               console.log(response);
            },
            error: function(xhr, options, error){
                console.log(error);
            }
        });
    } 
}


/* $(window).scroll(function() {    
    var scroll = $(window).scrollTop();
    if (scroll >= 100) {
        $("#eco_topo").addClass('navbar-fixed-top');
    } else {
        $("#eco_topo").removeClass('navbar-fixed-top');
    }
});*/


$(document).ready(function(){

    removeItensExpiradosCarrinho();

    $('.ex1').zoom();

});
