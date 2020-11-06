
var camposObrigatoriosDefault = function() {

    $.each(obrigatorios, function(section, campos){

        $.each(campos, function(nome, parametros) {
            
            let elemento = $("[name='"+section+"["+nome+"]"+"']");
            
            elemento.prop({
                required: parametros.obrigatorio
            });
            if (parametros.obrigatorio == true) {
                elemento.closest('.form-group').addClass('has-error')
            }
        });

    });

};

var changeTipoAtendimentoDefault = function() {

    if ($(this).val() == "obrig_produto") {

        obrigatorios.produto.referencia.obrigatorio = true;
        obrigatorios.produto.descricao.obrigatorio = true;

        obrigatorios.consumidor.nome.obrigatorios = false;
        obrigatorios.consumidor.cpf.obrigatorios = false;

    }

    if ($(this).val() == "obrig_consumidor") {

        obrigatorios.produto.referencia.obrigatorio = false;
        obrigatorios.produto.descricao.obrigatorio = false;

        obrigatorios.consumidor.nome.obrigatorios = true;
        obrigatorios.consumidor.cpf.obrigatorios = true;

    }

};


var changeVerificaValidacao = function (obrigatorios,elemento_pai) {

    $.each(obrigatorios, function(section, campos){

        $.each(campos, function(nome, parametros) {
            
            let elemento = $("[name='"+section+"["+nome+"]"+"']");

            if (parametros.obrigatorio == true) {

                if (parametros.rule == 'empty' && elemento.val() != '') {

                    elemento.closest('.form-group').removeClass('has-error');
                   
                } else if (parametros.rule == 'validaCpfCnpj' && elemento.val() != '' && validaCpfCnpj(elemento.val())) {

                    elemento.closest('.form-group').removeClass('has-error');

                } else if (parametros.rule == 'validaEmail' && elemento.val() != '' && validaEmail(elemento.val())) {

                    elemento.closest('.form-group').removeClass('has-error');

                }
            } 

        });

    });

    var teste = elemento_pai.find('[required]').filter(function(){
        return $(this).val() == '';
    })
    if (teste.length == 0) {
        $(elemento_pai).next('.container_bc').find(':input').prop('disabled',false);
    }
}

var configs = {
    atualizaObrigatorios: camposObrigatoriosDefault,
    tipoAtendimentoChange: changeTipoAtendimentoDefault
};

function validaEmail(email){
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
function validaCpfCnpj(cnpj){
    return true;
    
}
function retiraAcentos(palavra){
    if (!palavra) {
        return "";
    }

    var com_acento = 'áàãâäéèêëíìîïóòõôöúùûüçÁÀÃÂÄÉÈÊËÍÌÎÏÓÒÕÖÔÚÙÛÜÇ';
    var sem_acento = 'aaaaaeeeeiiiiooooouuuucAAAAAEEEEIIIIOOOOOUUUUC';
    var newPalavra = "";

    for(i = 0; i < palavra.length; i++) {
        if (com_acento.search(palavra.substr(i, 1)) >= 0) {
            newPalavra += sem_acento.substr(com_acento.search(palavra.substr(i, 1)), 1);
        } else {
            newPalavra += palavra.substr(i, 1);
        }
    }

    return newPalavra.toUpperCase();
}


function busca_cep(cep, method) {


    if (cep.length > 0) {
        var img = $("<img />", { src: "imagens/loading_img.gif", css: { display: "block", width: "15px", height: "15px" } });

        if (typeof method == "undefined" || method.length == 0) {
            method = "webservice";

            $.ajaxSetup({
                timeout: 3000
            });
        } else {
            $.ajaxSetup({
                timeout: 5000
            });
        }

        $.ajax({
            async: true,
            url: "ajax_cep.php",
            type: "GET",
            data: { ajax: true, cep: cep, method: method },
            beforeSend: function() {
                $("[name='consumidor[uf]']").next("img").remove();
                $("[name='consumidor[cidade]']").next("img").remove();
                $("[name='consumidor[bairro]']").next("img").remove();
                $("[name='consumidor[endereco]']").next("img").remove();
                $("[name='consumidor[uf]']").hide().after(img.clone());
                $("[name='consumidor[cidade]']").hide().after(img.clone());
                $("[name='consumidor[bairro]']").hide().after(img.clone());
                $("[name='consumidor[endereco]']").hide().after(img.clone());
            },
            error: function(xhr, status, error) {
                busca_cep(cep, "database");
            },
            success: function(data) {
                results = data.split(";");

                if (results[0] != "ok") {
                    alert(results[0]);
                    $("[name='consumidor[cidade]']").show().next().remove();
                } else {
                    $("[name='consumidor[uf]']").val(results[4]);

                    results[3] = results[3].replace(/[()]/g, '');

                    $("[name='consumidor[cidade]']").val(retiraAcentos(results[3]).toUpperCase());

                    if (results[2].length > 0) {
                        $("[name='consumidor[bairro]']").val(results[2]);
                    }

                    if (results[1].length > 0) {
                        $("[name='consumidor[endereco]']").val(results[1]);
                    }
                }

                $("[name='consumidor[uf]']").show().next().remove();
                $("[name='consumidor[bairro]']").show().next().remove();
                $("[name='consumidor[endereco]']").show().next().remove();
                $("[name='consumidor[cidade]']").show().next().remove();

                if ($("[name='consumidor[bairro]']").val().length == 0) {
                    $("[name='consumidor[bairro]']").focus();
                } else if ($("[name='consumidor[endereco]']").val().length == 0) {
                    $("[name='consumidor[endereco]']").focus();
                } else if ($("[name='consumidor[numero]']").val().length == 0) {
                    $("[name='consumidor[numero]']").focus();
                }

                $.ajaxSetup({
                    timeout: 0
                });
            }
        });
    }
}


function geraProtocolo() {

    $.ajax({
        url: "gera_protocolo.php",
        type: "GET",
        data: {},
        beforeSend: function() {
            $('.a_atendimento').html("<img src='imagens/ajax-loader.gif' width='20' height='20'>");
            
        },
        error: function(xhr, status, error) {
            alert('Erro ao gerar Protocolo');
            return false;
        },
        success: function(data) {
             var campos_array = data.split('|');
            if (campos_array[0]=='sim') {
                $('.mostra_protocolo').show();
                $('.oculta_protocolo').hide();
                $('.n_atendimento').html(campos_array[1]);
            } else {
                alert('Erro ao gerar Protocolo');
                return false;
            }
        }
    });

}

$(function(){
    $(document).on('focus', ".cpf_cnpj", function(){
       $(this).unmask();
       $(this).mask("99999999999999",{placeholder:""});
    });
    $(document).on('change', ".campoCEP", function(){
        busca_cep($(this).val())
    });
    $(document).on('blur', ".cpf_cnpj", function(){ 
       var el = $(this);
       el.unmask();
       
       if(el.val().length > 11){
           el.mask("99.999.999/9999-99",{placeholder:""});
       }

       if(el.val().length <= 11){
           el.mask("999.999.999-99",{placeholder:""});
       }
   });
   
   CKEDITOR.replace("reclamacao[descricao]", { enterMode: CKEDITOR.ENTER_BR});
   
    $(document).on('click', ".btn-lupa-pesquisa", function(){
        var filtro     = $(this).data('filtro');
        var callcenter = $(this).data('callcenter');

        if (filtro == 'n_protocolo') {
            var campo = $("[name='pesquisa[n_protocolo]']");
        } else if (filtro == 'cpf_cnpj') {
            var campo = $("[name='pesquisa[cpf_cnpj]']");
        } else if (filtro == 'nome') {
            var campo = $("[name='pesquisa[nome]']");
        } else if (filtro == 'telefone') {
            var campo = $("[name='pesquisa[telefone]']");
        } else if (filtro == 'n_ordem_servico') {
            var campo = $("[name='pesquisa[n_ordem_servico]']");
        } else if (filtro == 'n_serie') {
            var campo = $("[name='pesquisa[n_serie]']");
        } else if (filtro == 'cep') {
            var campo = $("[name='pesquisa[cep]']");
        }
        if (campo.val() == '' || campo.val().length < 3) {
            alert("Digite ao menos 3 caracteres no campo de pesquisa");
            campo.focus();
            return false;
        }
        
        Shadowbox.open({
            content: "callcenter/componentes/lupas/pesquisa_atendimento.php?tipo="+filtro+"&callcenter&"+callcenter+"&campo="+campo.val(),
            player: "iframe",
            width: 850,
            height: 500,
            options: {onFinish: function(){
                    $("#sb-nav-close").hide();
                },
                overlayColor:'#333' }
        });

    });

    $(document).on('click', ".btn-abre-mapa", function(){

        var login_fabrica         = $(this).data('fabrica');
        var linha                 = $("[name='mapa_rede[linha]']").val();
        var nome_cliente          = $("[name='mapa_rede[linha]']").val();
        var endereco_completo     = [];
        var endereco_rota         = [];
        var hd_chamado            = $("[name='callcenter']").val();
        var estado,
            tipo_cliente,
            tipo_posto,
            cidade,
            cep,
            endereco,
            numero,
            bairro,
            local_cidade,
            local_estado,
            mapa_endereco,
            mapa_rota,
            produto;
        if (linha == "") {
            alert("Favor selecionar a Linha do Posto!");
            $("[name='mapa_rede[linha]']").focus();
        } else {
            
            if (($("#mapa_cidade").val() !== '' && $("#mapa_cidade").val() !== undefined)
                && ($("#mapa_estado").val() !== '' && $("#mapa_estado").val() !== undefined)
                && ($("#mapa_cidade").val() !== $('#consumidor_cidade').val()
                || $("#mapa_estado").val() !== $('#consumidor_estado').val())) {
                endereco        = '';
                numero          = '';
                bairro          = '';
                cep             = '';
                local_cidade    = $("#mapa_cidade").val();
                local_estado    = $("#mapa_estado").val();
            }else{
                endereco        = $("#consumidor_endereco").val();
                numero          = $("#consumidor_numero").val();
                bairro          = $("#consumidor_bairro").val();
                cep             = $("#consumidor_cep").val();
                local_cidade    = $('#consumidor_cidade').val();
                local_estado    = $('#consumidor_estado').val();
            }

            if (local_cidade == '' || local_estado == '') {
                alert('Preencha a cidade e Estado do cliente antes de buscar um posto autorizado');
                return;
            }

            endereco_completo.push(endereco);
            endereco_completo.push(numero);
            endereco_completo.push(bairro);
            endereco_completo.push(local_cidade);
            endereco_completo.push(local_estado);
            endereco_completo.push("Brasil");
            mapa_completo = endereco_completo.join();

            endereco_rota.push(endereco);
            endereco_rota.push(numero);
            endereco_rota.push(local_cidade);
            endereco_rota.push(local_estado);
            endereco_rota.push("Brasil");
            mapa_rota = endereco_rota.join();

            var get = [
                "callcenter=true",
                "linha="+linha,
                "nome="+nome_cliente,
                "cep="+cep,
                "pais=BR",
                "consumidor_estado="+local_estado,
                "estado="+local_estado,
                "consumidor_cidade="+local_cidade,
                "cidade="+local_cidade,
                "bairro="+bairro,
                "numero="+numero,
                "endereco="+endereco,
                "endereco_rota="+endereco_rota,
                "consumidor="+endereco_completo,
                "hd_chamado="+hd_chamado,
                "tipo_cliente="+tipo_cliente,
                "tipo_posto="+tipo_posto,
                "produto="+produto
            ];

            Shadowbox.open({
                content: "mapa_rede_new.php?"+get.join("&"),
                player: "iframe",
                width: '1400px',
                height: '900px'
            });
            $("#sb-nav").css({ display: "none" });
        }
    });


    $(document).on('change', ".tipo_consumidor", function(){

        var tipo = $(".tipo_consumidor option:selected").val();

        $(".cpf_cnpj").unmask();
        if(tipo == 'J'){
            $(".label_cpf").text("CNPJ");
            $(".label_rg").text("I.E.");
            $(".cpf_cnpj").mask("99.999.999/9999-99",{placeholder:""});
        }else{
            $(".label_cpf").text("CPF");
            $(".label_rg").text("RG");
            $(".cpf_cnpj").mask("999.999.999-99",{placeholder:""});
        }
    });

    //buscaCEP(cep, endereco, bairro, cidade, estado, method, callback) //
    $(".campoData").datepicker({ maxDate: 0, minDate: "-2d", dateFormat: "dd/mm/yy" }).mask("99/99/9999");
    $(".campoNumerico").numeric();
    $(".campoFone").mask("(99) 9999-9999",{placeholder:""});
    $(".campoCelular").mask("(99) 99999-9999",{placeholder:""});
    $(".campoCEP").mask("99999-999",{placeholder:""});
    $(".campoPreco").priceFormat({
            prefix: '',
            thousandsSeparator: '',
            centsSeparator: '.',
            centsLimit: 2
        });

    configs.atualizaObrigatorios();

    $("#tipo_atendimento").change(function(){

        configs.tipoAtendimentoChange();

        configs.atualizaObrigatorios();

    });

    $("#form_callcenter :input").prop( "disabled", true );
    $("#box_consumidor :input").prop( "disabled", false );
    
    $(document).on('change', '.box_campos :input', function(){
        var elemento_pai = $(this).closest('.container_bc');
        changeVerificaValidacao(obrigatorios,elemento_pai)
    });
     var contador_produto = 0;
    $(document).on('click', '.add_produto', function(){

        $("#boxcall").clone().appendTo(".result_produto_callcenter").addClass('ajuste_box_produto_'+contador_produto);
        $(".ajuste_box_produto_"+contador_produto).find(".remove_pd").show().addClass('remove_pd_'+contador_produto);
        $(".ajuste_box_produto_"+contador_produto).find(".del_produto").attr("data-posicao",contador_produto);
        contador_produto++;

    });
    $(document).on('click', '.del_produto', function(){
        var posicao = $(this).data("posicao");
        if (confirm("Tem certeza que deseja remover esse registro?")) {
            $('.ajuste_box_produto_'+posicao).remove();
        }

    });
});