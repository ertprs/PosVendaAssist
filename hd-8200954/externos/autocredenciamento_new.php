<?php

$script_php = $_SERVER["SCRIPT_NAME"];
$pos = strpos($_SERVER["REQUEST_URI"], $script_php . '/');

if ($pos !== false) {
    header('HTTP/1.1 404 Not Found');
    include __DIR__ . '/../not_found.html';
    exit;
}

$verifica_cnpj = '';

include('autocredenciamento_validate.php');

$pagetitle = "Reenviar Email de Validação";

include('site_estatico/header.php');

?>

<script>$('body').addClass('pg log-page')</script>


<!-- <script src="../js/jquery.js" type="text/javascript"></script> -->
<script src="../js/jquery.autocomplete.js" type="text/javascript"></script>
<script src="../js/file/jquery.MultiFile_novo.js" type="text/javascript"></script>
<!-- <script type='text/javascript' src='../js/jquery.maskedinput.js'></script>-->
<script type="text/javascript" src="../js/jquery.numeric.js"></script>

<script type="text/javascript" src="../admin/js/jquery.mask.js"></script>


<script type="text/javascript">

    function verifica_submit() {
        var verifica = 0;
        var nome = $(".nome").val();
        var nome_fantasia = $(".nome_fantasia").val();
        var cpnj = $("#cnpj").val();
        var ie = $("#ie").val();
        var contato = $(".contato").val();
        var telefone = $(".telefone").val();
        var email = $(".email").val();
        var cep = $(".cep").val();
        var endereco = $(".endereco").val();
        var numero = $(".numero").val();
        var bairro = $(".bairro").val();
        var cidade = $(".cidade").val();
        var estado = $(".estado").val();
        var funcionarios = $(".funcionarios").val();
        var fabricantes = $("#fabricantes").val();
        var atende_cidade_proxima = $("#atende_cidade_proxima").val();

        var linha_1 = $("#linha_1").attr("checked");
        var linha_2 = $("#linha_2").attr("checked");
        var linha_3 = $("#linha_3").attr("checked");
        var linha_4 = $("#linha_4").attr("checked");
        var linha_5 = $("#linha_5").attr("checked");
        var linha_6 = $("#linha_6").attr("checked");
        var linha_7 = $("#linha_7").attr("checked");

        var condicao_1 = $("#condicao_1").attr("checked");
        var condicao_2 = $("#condicao_2").attr("checked");
        var condicao_3 = $("#condicao_3").attr("checked");

        var arquivo1 = $("#arquivo1").val();
        var arquivo2 = $("#arquivo2").val();
        var arquivo3 = $("#arquivo3").val();

        var total_fab = $("#total_fab").val();
        var outras_fabricas = $("#outras_fabricas").attr("checked");
        var outras_fabricas_txt = $("#opcao_outras_fabricas").val();

        var s_outro_sis = $("#s_outro_sis").attr("checked");

        var melhor_sistema = $("#melhor_sistema_txt").val();

        var erro_login = "";
        if (!nome) {
            $(".nome").css('border-color','#C6322B');
            $(".nome").css('border-width','1px');
            $(".razaosocial").css('color','#C6322B');
            verifica ='1';
            erro_login = "Preencha razão social";
        } else {
            $(".nome").css('border-color','#CCC');
            $(".nome").css('border-width','1px');
            $(".razaosocial").css('color','#535252');
        }

        if (!nome_fantasia) {
            $(".nome_fantasia").css('border-color','#C6322B');
            $(".nome_fantasia").css('border-width','1px');
            $(".lnome_fantasia").css('color','#C6322B');
            verifica ='1';
            erro_login = "Preencha nome fantasia";
        } else {
            $(".nome_fantasia").css('border-color','#CCC');
            $(".nome_fantasia").css('border-width','1px');
            $(".lnome_fantasia").css('color','#535252');
        }

        if (!cpnj) {
            $("#cnpj").css('border-color','#C6322B');
            $("#cnpj").css('border-width','1px');
            $(".lcnpj").css('color','#C6322B');
            verifica ='1';
            erro_login = "Preencha CNPJ";
        } else {
            $("#cnpj").css('border-color','#CCC');
            $("#cnpj").css('border-width','1px');
            $(".lcnpj").css('color','#535252');
        }

        if (!contato) {
            $(".contato").css('border-color','#C6322B');
            $(".contato").css('border-width','1px');
            $(".lcontato").css('color','#C6322B');
            verifica ='1';
            erro_login = "Preencha contato.";
        } else {
            $(".contato").css('border-color','#CCC');
            $(".contato").css('border-width','1px');
            $(".lcontato").css('color','#535252');
        }

        if (!telefone) {
            $(".telefone").css('border-color','#C6322B');
            $(".telefone").css('border-width','1px');
            $(".ltelefone").css('color','#C6322B');
            verifica ='1';
            erro_login = "Preencha telefone";
        } else {
            $(".telefone").css('border-color','#CCC');
            $(".telefone").css('border-width','1px');
            $(".ltelefone").css('color','#535252');
        }

        if (!email) {
            $(".email").css('border-color','#C6322B');
            $(".email").css('border-width','1px');
            $(".lemail").css('color','#C6322B');
            verifica ='1';
            erro_login = "Preencha email";
        } else {
            $(".email").css('border-color','#CCC');
            $(".email").css('border-width','1px');
            $(".lemail").css('color','#535252');
        }

        if (!cep) {
            $(".cep").css('border-color','#C6322B');
            $(".cep").css('border-width','1px');
            $(".lcep").css('color','#C6322B');
            verifica ='1';
            erro_login = "Preencha CEP.";
        } else {
            $(".cep").css('border-color','#CCC');
            $(".cep").css('border-width','1px');
            $(".lcep").css('color','#535252');
        }

        if (!endereco) {
            $(".endereco").css('border-color','#C6322B');
            $(".endereco").css('border-width','1px');
            $(".lendereco").css('color','#C6322B');
            verifica ='1';
            erro_login = "Preencha endereço";
        } else {
            $(".endereco").css('border-color','#CCC');
            $(".endereco").css('border-width','1px');
            $(".lendereco").css('color','#535252');
        }

        if (!numero) {
            $(".numero").css('border-color','#C6322B');
            $(".numero").css('border-width','1px');
            $(".lnumero").css('color','#C6322B');
            verifica ='1';
            erro_login = "Preencha número.";
        } else {
            $(".numero").css('border-color','#CCC');
            $(".numero").css('border-width','1px');
            $(".lnumero").css('color','#535252');
        }

        if (!bairro) {
            $(".bairro").css('border-color','#C6322B');
            $(".bairro").css('border-width','1px');
            $(".lbairro").css('color','#C6322B');
            verifica ='1';
            erro_login = "Preencha bairro";
        } else {
            $(".bairro").css('border-color','#CCC');
            $(".bairro").css('border-width','1px');
            $(".lbairro").css('color','#535252');
        }

        if (!cidade) {
            $(".cidade").css('border-color','#C6322B');
            $(".cidade").css('border-width','1px');
            $(".lcidade").css('color','#C6322B');
            verifica ='1';
            erro_login = "Preencha cidade";
        } else {
            $(".cidade").css('border-color','#CCC');
            $(".cidade").css('border-width','1px');
            $(".lcidade").css('color','#535252');
        }

        if (!estado) {
            $(".estado").css('border-color','#C6322B');
            $(".estado").css('border-width','1px');
            $(".lestado").css('color','#C6322B');
            verifica ='1';
            erro_login = "Preencha estado";
        } else {
            $(".estado").css('border-color','#CCC');
            $(".estado").css('border-width','1px');
            $(".lestado").css('color','#535252');
        }

        if (!funcionarios) {
            $(".funcionarios").css('border-color','#C6322B');
            $(".funcionarios").css('border-width','1px');
            $(".lfuncionarios").css('color','#C6322B');
            verifica ='1';
            erro_login = "Preencha funcionários";
        } else {
            $(".funcionarios").css('border-color','#CCC');
            $(".funcionarios").css('border-width','1px');
            $(".lfuncionarios").css('color','#535252');
        }

        if (!fabricantes) {
            $("#fabricantes").css('border-color','#C6322B');
            $("#fabricantes").css('border-width','1px');
            $(".lfabricantes").css('color','#C6322B');
            verifica ='1';
            erro_login = "Preencha fabricantes";
        } else {
            $("#fabricantes").css('border-color','#CCC');
            $("#fabricantes").css('border-width','1px');
            $(".lfabricantes").css('color','#535252');
        }

        if (!atende_cidade_proxima) {
            $("#atende_cidade_proxima").css('border-color','#C6322B');
            $("#atende_cidade_proxima").css('border-width','1px');
            $(".latende_cidade_proxima").css('color','#C6322B');
            verifica ='1';
            erro_login = "Preencha cidades proximas";
        } else {
            $("#atende_cidade_proxima").css('border-color','#CCC');
            $("#atende_cidade_proxima").css('border-width','1px');
            $(".latende_cidade_proxima").css('color','#535252');
        }

        if (linha_1 == false && linha_2 == false && linha_3 == false && linha_4 == false && linha_5 == false && linha_6 == false && linha_7 == false) {
            $(".llinhas").css("color", "#C6322B");
            $("#info_ad_linhas").css("border-color", "#C6322B");
            verifica ='1';
            erro_login = "Preencha linha.";
        } else {
            $(".llinhas").css("color", "#535252");
            $("#info_ad_linhas").css("border-color", "#CCCCCC");
        }

        if (condicao_1 == false && condicao_2 == false && condicao_3 == false) {
            $(".latender").css("color", "#C6322B");
            $("#info_atender").css("border-color", "#C6322B");
            verifica ='1';
            erro_login = "Preencha condicao.";
        } else {
            $(".latender").css("color", "#535252");
            $("#info_atender").css("border-color", "#CCCCCC");
        }

        var testVal = '';
        var totErr = 0;
        var fabrica_ok = 1;



        for (var i = 0; i < total_fab; i++) {
            testVal = $("input[name=fabrica_" + i + "]").prop("checked");

            <? // HD-1867132

            if($_GET['fabrica'] == 114 AND $_GET['linha'] == 811 OR $_GET['fabrica'] == 114 AND $_GET['linha'] == 710){
            ?>
                    testVal = $("input[name='fabrica_8']").val();
            <?
                }
                // FIM HD-1867132
            ?>
            if (!testVal) {
                totErr++;
            }
        }


        if (totErr < total_fab) {
            fabrica_ok = 0;
        } else {
            if (outras_fabricas && outras_fabricas_txt) {
                fabrica_ok = 0;
            }
        }


        if (fabrica_ok == 1) {
            $("#fabrica_marcas_label_topo").css("color", "#C6322B");
            $("#info_ad_fabricas").css("border-color", "#C6322B");
            verifica ='1';
            erro_login = "Preencha fabricas ok.";
        } else {
            $("#fabrica_marcas_label_topo").css("color", "#535252");
            $("#info_ad_fabricas").css("border-color", "#CCCCCC");
        }

        if (!arquivo1) {
            var old_foto1 = $("#old_foto1").val();

            if (old_foto1 != "1") {
                $("#arquivo1").css('border-color','#C6322B');
                $("#arquivo1").css('border-width','1px');
                $(".lfachada").css('color','#C6322B');
                verifica ='1';
                erro_login = "Preencha foto1.";
            }
        } else {
            $("#arquivo1").css('border-color','#CCC');
            $("#arquivo1").css('border-width','1px');
            $(".lfachada").css('color','#535252');
        }

        if (!arquivo2) {
            var old_foto2 = $("#old_foto2").val();

            if (old_foto2 != "1") {
                $("#arquivo2").css('border-color','#C6322B');
                $("#arquivo2").css('border-width','1px');
                $(".lrecepcao").css('color','#C6322B');
                verifica ='1';
                erro_login = "Preencha foto2.";
            }
        } else {
            $("#arquivo2").css('border-color','#CCC');
            $("#arquivo2").css('border-width','1px');
            $(".lrecepcao").css('color','#535252');
        }

        if (!arquivo3) {
            var old_foto3 = $("#old_foto3").val();

            if (old_foto3 != "1") {
                $("#arquivo3").css('border-color','#C6322B');
                $("#arquivo3").css('border-width','1px');
                $(".loficina").css('color','#C6322B');
                verifica ='1';
                erro_login = "Preencha foto3.";
            }
        } else {
            $("#arquivo3").css('border-color','#CCC');
            $("#arquivo3").css('border-width','1px');
            $(".loficina").css('color','#535252');
        }

        if (s_outro_sis) {
            var inf = 0;

            var if1 = $('#inf_sistema1').val();
            var im1 = $('#inf_marca1').val();
            var iv1 = $('#inf_vantagem1').val();

            if (if1 && im1 && iv1) {
                inf = 0;
            } else {
                inf++;
            }

            var if2 = $('#inf_sistema2').val();
            var im2 = $('#inf_marca2').val();
            var iv2 = $('#inf_vantagem2').val();

            if (if2 && im2 && iv2) {
                inf = 0;
            } else {
                inf++;
            }

            var if3 = $('#inf_sistema3').val();
            var im3 = $('#inf_marca3').val();
            var iv3 = $('#inf_vantagem3').val();

            if (if3 && im3 && iv3) {
                inf = 0;
            } else {
                inf++;
            }

            if (inf == 3) {
                $("#inf_sistema1").css('border-color','#C6322B');
                $("#inf_sistema1").css('border-width','1px');

                $("#inf_marca1").css('border-color','#C6322B');
                $("#inf_marca1").css('border-width','#1px');

                $("#inf_vantagem1").css('border-color','#C6322B');
                $("#inf_vantagem1").css('border-width','1px');

                $("#label_informacoes_sistem_extra").css('color','#C6322B');
                $("#label_sistema").css('color','#C6322B');
                $("#label_marca").css('color','#C6322B');
                $("#label_vantagem").css('color','#C6322B');

                verifica ='1';
                erro_login = "Preencha info.";
            } else {
                $("#inf_sistema1").css('border-color','#CCC');
                $("#inf_sistema1").css('border-width','1px');

                $("#inf_marca1").css('border-color','#CCC');
                $("#inf_marca1").css('border-width','1px');

                $("#inf_vantagem1").css('border-color','#CCC');
                $("#inf_vantagem1").css('border-width','1px');

                $("#label_informacoes_sistem_extra").css('color','#535252');
                $("#label_sistema").css('color','#535252');
                $("#label_marca").css('color','#535252');
                $("#label_vantagem").css('color','#535252');
            }
        } else {
            $("#inf_sistema1").css('border-color','#CCC');
            $("#inf_sistema1").css('border-width','1px');

            $("#inf_marca1").css('border-color','#CCC');
            $("#inf_marca1").css('border-width','1px');

            $("#inf_vantagem1").css('border-color','#CCC');
            $("#inf_vantagem1").css('border-width','1px');

            $("#label_informacoes_sistem_extra").css('color','#535252');
            $("#label_sistema").css('color','#535252');
            $("#label_marca").css('color','#535252');
            $("#label_vantagem").css('color','#535252');
        }

        if (!melhor_sistema) {
            $("#melhor_sistema_txt").css('border-color','#C6322B');
            $("#melhor_sistema_txt").css('border-width','1px');
            $("#melhor_sis").css('color','#C6322B');
            verifica ='1';
            erro_login = "Preencha melhor sistema.";
        } else {
            $("#melhor_sistema_txt").css('border-color','#CCC');
            $("#melhor_sistema_txt").css('border-width','1px');
            $("#melhor_sis").css('color','#535252');
        }

        if (verifica =='1') {
            $("#msg_error").show();

             $('html, body').animate({scrollTop:0}, '');

            setTimeout(function(){
                $("#msg_error").hide();
            },3500);
            //alert("ERRO");
            // $("#mensagem_envio").html('');
            // $("#mensagem_envio").show();
            // $("#mensagem_envio").css('display','block');
            // $("#mensagem_envio").html('<label class="erro_campos_obrigatorios">* Por favor, verifique os campos marcados em vermelho.</label>');
            // window.location.hash = '#mensagem_envio';
            return false;
        } else {
            //alert("SUCESSO");
            $("#mensagem_envio").html('');
            $('#frm_posto').submit();//EXECUTA O SUBMIT
            return true;
        }

    }

    function vericaSubmitCNPJ() {
        var cnpj = $("#cnpj").val();
        if (!cnpj) {

            $("#msg_erro").show();
            setTimeout(function(){
                $("#msg_erro").hide();
            },3000);
            // $("#cnpj").css('border-color','#C6322B');
            // $("#cnpj").css('border-width','1px');
            // $(".informe_cnpj").css('color', '#C6322B');
            // $("#mensagem_envio").html('');
            // $("#mensagem_envio").show();
            // $("#mensagem_envio").css('display','block');
            // $("#mensagem_envio").html('<label class="erro_campos_obrigatorios">* Por favor, informe seu CNPJ</label>');

        } else {
            $("#mensagem_envio").html('');
            $('#verificaa').submit();
            return true;
        }
    }
    function mostraOutrosSis(p) {
        var el = document.getElementById(p + '_outro_sis');
        if (el.value == "S") {
            document.getElementById('outros_sistemas').style.display = "block";
        } else {
            document.getElementById('outros_sistemas').style.display = "none";
        }
    }
    function txtBoxFormat(objeto, sMask, evtKeyPress) {
        var i, nCount, sValue, fldLen, mskLen,bolMask, sCod, nTecla;
        if(document.all) { // Internet Explorer
            nTecla = evtKeyPress.keyCode;
        } else if(document.layers) { // Nestcape
            nTecla = evtKeyPress.which;
        } else {
            nTecla = evtKeyPress.which;
            if (nTecla == 8) {
                return true;
            }
        }
        sValue = objeto.value;
        // Limpa todos os caracteres de formatação que
        // já estiverem no campo.
        sValue = sValue.toString().replace( /[\-\.\/:\(\)\s]/g, "");
        fldLen = sValue.length;
        mskLen = sMask.length;
        i = 0;
        nCount = 0;
        sCod = "";
        mskLen = fldLen;
        while (i <= mskLen) {
            bolMask = ((sMask.charAt(i) == "-") || (sMask.charAt(i) == ".") || (sMask.charAt(i) == "/") || (sMask.charAt(i) == ":"))
            bolMask = bolMask || ((sMask.charAt(i) == "(") || (sMask.charAt(i) == ")") || (sMask.charAt(i) == " "))
            if (bolMask) {
                sCod += sMask.charAt(i);
                mskLen++; }
            else {
                sCod += sValue.charAt(nCount);
                nCount++;
            }
            i++;
        }
        objeto.value = sCod;
        if (nTecla != 8) { // backspace
            if (sMask.charAt(i-1) == "9") { // apenas números...
                return ((nTecla > 47) && (nTecla < 58)); }
            else { // qualquer caracter...
                return true;
            }
        }
        else {
            return true;
        }
    }
    function verificaNumero(e) {
        if (e.which != 8 && e.which != 0 && (e.which < 48 || e.which > 57)) {
            return false;
        }
    }
    function vretirar_caracter(e) {
        if (e.which == 124) {
            return false;
        }
    }
    $(document).ready(function() {
        $(".funcionarios").keypress(verificaNumero);
    });
    $(document).ready(function() {
        $(".inf_sistemas").keypress(vretirar_caracter);
    });
    $(document).ready(function() {
        $("input[type=text][name=cnpj]").mask("99.999.999/9999-99");
        $("input[type=text][name=telefone]").mask("(99) 9999-9999");
        $("input[type=text][name=fax]").mask("(99) 9999-9999");
        $("input[type=text][name=cep]").mask("99999-999");
        //$("input[@name=cnpj]").mask("99.999.999/9999-99");
        //$("input[@name=telefone]").maskedinput("(99) 9999-9999");
        //$("input[@name=fax]").maskedinput("(99) 9999-9999");
        //$("input[@name=cep]").maskedinput("99999-999");
        $("#ie").numeric();
        $("#numero").numeric();
        $(".funcionarios").numeric();
        $(".oss").numeric();
    });

    $(document).ready(function() {
        //  Busca CEP
        $('form input[name=cep]').blur(function() {
            var cep     = escape(jQuery(this).val());
            var endereco= jQuery('form input[name=endereco]');
            var bairro  = jQuery('form input[name=bairro]');
            var cidade  = jQuery('form input[name=cidade]');
            var estado  = jQuery('form select[name=estado]');
            $('#loading-cep').show();
            if (cep.length >= 8) {
                jQuery.get('ajax_cep.php',
                            {'cep':cep},
                            function(data) {
                    results = data.split(";");
                    if (results[0] != 'ok'){
                        jQuery('#endereco').val('');
                        jQuery('#bairro').val('');
                        jQuery('#cidade').val('');
                        jQuery('#estado').val('');
                        jQuery('#numero').val('');
                        jQuery('#complemento').val('');
                        return false;
                    }

                    if (typeof (results[1]) != 'undefined') endereco.val(results[1]);
                    if (typeof (results[2]) != 'undefined') bairro.val(results[2]);
                    if (typeof (results[3]) != 'undefined') cidade.val(results[3]);
                    if (typeof (results[4]) != 'undefined') estado.val(results[4]);

                    if (data.length <= 2) {
                        jQuery('#endereco').focus();
                    } else {
                        jQuery('#numero').val('');
                        jQuery('#complemento').val('');
                        jQuery('#numero').focus();
                    }
                    $('#loading-cep').hide();
                });
            } else {
                jQuery('#endereco').val('');
                jQuery('#bairro').val('');
                jQuery('#cidade').val('');
                jQuery('#estado').val('');
                jQuery('#numero').val('');
                alert('Cep inválido!');
                $('#loading-cep').hide();
                return false;
            }
        });

        <?php
            /*  Define o array para o autocomplete dos fabricantes cadastrados na Telecontrol. Talvez seja possível adicionar
            também marcas de outros cadastros... Veremos    */

            $temp_fabricas = pg_fetch_all(pg_query($con, "SELECT nome FROM tbl_fabrica WHERE ativo_fabrica IS TRUE and fabrica NOT IN ($not_in_fabricas)"));
            foreach ($temp_fabricas as $fabrica_temp) {
                $fabricas_tc[] = '"'.$fabrica_temp['nome'].'"';
            }
            echo "  var fabricas = [".implode(",",$fabricas_tc)."];\n";
        ?>

        $('#linha_6').click(function(){
            if($('#linha_6').is(':checked')){
                $('#linha_6_obs').attr('disabled',false);
            }else{
                $('#linha_6_obs').attr('disabled',true);
            }
        });

        $('#marca_todas_fabricas').click(function(){ //2780042
            if($('#marca_todas_fabricas').is(':checked')){
                $('.todas_fabricas').prop('checked',true);
            }else{
                $('.todas_fabricas').each(function(){
                    var checked = $(this).data('fabrica-checked');

                    if(checked.length == 0){
                        $(this).prop('checked',false);
                    }
                });
            }
        });

        $('#outras_fabricas').click(function(){
            if($('#outras_fabricas').is(':checked')){
                $('#opcao_outras_fabricas').attr('disabled',false);
            }else{
                $('#opcao_outras_fabricas').attr('disabled',true);
            }
        });
    });
</script>

<style type="text/css">
    .input_gravar{
        cursor: pointer;
        border-bottom: none;
        background: #434390;
        color: #FFF;
        padding: 10px 60px;
        display: inline-block;
        width: auto;
        margin-top: 12px;
        display: block;
        width: 100%;
        text-align: center;
    }
    .contato{
        background: none;
    }
</style>

<section class="table h-img">
    <?php include('site_estatico/menu-pgi.php'); ?>
    <div class="cell">
        <div class="title"><h2>Auto-Credenciamento</h2></div>
    </div>
</section>

<section class="pad-1 login cad-autocred">
    <div class="main">

        <?php
            if(strlen($msg_ok) > 0){
                $display="style='display:block;'";
        ?>
                <script language="JavaScript" type="text/javascript">
                    var contador = 5;
                    function conta() {
                        if(contador == 0) {
                            window.location = "http://www.telecontrol.com.br";
                        }
                        if (contador != 0){
                            contador = contador-1;
                            setTimeout("conta()", 1000);
                        }
                    }
                </script>
                <div class="alerts">
                    <div class="alert success" <?=$display?>><i class="fa fa-check-circle"></i>
                        A Telecontrol agradece o seu cadastro!
                    </div>
                </div>
                <div>
                <h3>
                    <strong>Razão Social: <?=$nome?></strong><br/>
                    <strong>CNPJ: <?=$cnpj?> </strong><strong>IE: <?=$ie?></strong><br/>
                    <strong>Cidade: <?=$cidade?></strong> <strong>Estado: <?=$estado?></strong><br/>
                    <strong>Email: <?=$email?></strong><br/>
                </h3>
                </div><br/>
                <?php
                    $cnpj = str_replace("'", "", $aux_cnpj);

                    $img_path = $caminho_path.$cnpj;
                    $img_caminho = $caminho_imagem.$cnpj;

                    if (file_exists($img_caminho."_1.jpg")) $img_ext = "jpg";
                    if (file_exists($img_caminho."_1.png")) $img_ext = "png";
                    if (file_exists($img_caminho."_1.gif")) $img_ext = "gif";
                ?>
                <ul class="three-col">
                    <?php
                        if ($img_ext) {
                            $img_src = $img_path . '_1.' . $img_ext;
                            echo '<li style="float: left; padding-left:0px;">';
                                echo '<img width="260" height="163" src="' , $img_src , '" />';
                            echo '</li>';
                        }

                        if (file_exists($img_caminho."_2.jpg")) $img_ext = "jpg";
                        if (file_exists($img_caminho."_2.png")) $img_ext = "png";
                        if (file_exists($img_caminho."_2.gif")) $img_ext = "gif";

                        if ($img_ext) {
                            $img_src = $img_path . '_2.' . $img_ext;
                            echo '<li style="float: left;">';
                                echo '<img width="260" height="163" src="' , $img_src , '" />';
                            echo '</li>';
                        }

                        if (file_exists($img_caminho."_3.jpg")) $img_ext = "jpg";
                        if (file_exists($img_caminho."_3.png")) $img_ext = "png";
                        if (file_exists($img_caminho."_3.gif")) $img_ext = "gif";

                        if ($img_ext) {
                            $img_src = $img_path . '_3.' . $img_ext;
                            echo '<li style="float: left;">';
                                echo '<img width="260" height="163" src="' , $img_src , '" />';
                            echo '</li>';
                        }
                    ?>
                </ul>

                <script>window.onload = conta();</script>

        <?php
            exit;
            }
        ?>

        <?php if(strlen($msg_erro) > 0){ $display="style='display:block;'"; ?>
            <div class="alert error" <?=$display?>><i class="fa fa-exclamation-circle"></i><?=$msg_erro?></div>
        <?php } ?>

        <?php if(strlen($btn_acao) == 0 or $aux_cnpj == ""): ?>

        <div class="alerts">
            <div class="alert error" id='msg_erro'><i class="fa fa-exclamation-circle"></i>Por favor, informe seu CNPJ</div>
        </div>

        <div class="desc">
            <h3>
            <?=ttext($a_labels, "Informe_CNPJ", $cook_idioma)?>
            </h3>
        </div>
        <div class="sep"></div>
        <form method='post' id='verificaa' name='frm_verifica' action="<?$PHP_SELF?>">
            <input type="text" name="cnpj" id='cnpj' maxlength="18" value="<?=trim($verifica_cnpj)?>" placeholder="CNPJ">
            <input type="hidden" name="btn_acao" value="Cadastrar" />
            <?php if ($_GET['wurth'] == 's'): ?>
                <input type="hidden" name="wurth" value="ok">
            <?php endif ?>
            <?php if ($_GET['cobimex'] == 's'): ?>
                <input type="hidden" name="cobimex" value="ok">
            <?php endif ?>
            <?php if ($_GET['positec'] == 's'): ?>
                <input type="hidden" name="positec" value="ok">
            <?php endif ?>
            <?php if (!empty($_GET['fabrica'])): ?>
            <input type="hidden" name="<?=$fabrica_nome?>" value="ok">
            <?php endif ?>
            <input type="button"  id='btn_acao' class='input_gravar' style="cursor: default;" value='<?=ttext ($a_labels, "Cadastrar", $cook_idioma)?>' onClick="vericaSubmitCNPJ()" />

            </form>
		<br><br>
		<? if($_GET['fabrica'] != '146') { ?>
        <div class="desc">
        <h3>O que é e para que serve o Auto-Credenciamento Telecontrol?</h3>
        <br>
        <h4>
        É um novo recurso que desenvolvemos para auxiliar nossos parceiros. Tem por finalidade, contribuir para que as indústrias ampliem sua Rede Autorizada e, por outro lado, possibilitar ao seu Posto Autorizado acesso a um canal rápido e eficaz para oferecer seus serviços.
        </h4>
            <br>
            <ul class="no-li-style">
            <li>
            <strong>1º Passo</strong> - O Posto Autorizado faz o cadastro no site da Telecontrol detalhando as informações importantes para que as Industrias possam analisar seu perfil (linhas de atendimento, cidades que atende, fotos da sua empresa, preferência por marcas, recursos a disposição - carro, estacionamento, etc).</li>
            <li>
            <strong>2º Passo</strong> - A Telecontrol disponibilizará estas informações para as indústrias que utilizam nosso Sistema.
            </li>
            <li>
            <strong>3º Passo</strong> - A indústria interessada entrará em contato para iniciar o processo de credenciamento do Posto Autorizado.
            </li>
            <li>
            <br>
            <strong>Não perca tempo, cadastre-se já!</strong>
            </li>
            </ul>
        </div>
	<? } ?>
    <?php else: ?>

        <div class="desc">
            <h3>
                Preencha os dados abaixo para efetuar o Credenciamento (*Campos obrigatórios).
            </h3>
        </div>
         <div class="alerts" id='scrollToTop'>
            <div class="alert error" id='msg_error'><i class="fa fa-exclamation-circle"></i>Por favor, verifique os campos marcados obrigatórios.</div>
        </div>
        <div class="sep"></div>
        <form name="frm_posto" id="frm_posto" method="post" action="<?=$PHP_SELF?>" enctype="multipart/form-data" class="form">
            <input type="hidden" name="posto" value="<?=$posto?>" />
            <input type="hidden" name="fabrica" value="<?=$_GET['fabrica']?>" />

            <div class="title mb">
                <h1>Informações Cadastrais</h1>
            </div>
            <ul class="half">
                <li>
                    <span>Razão Social<i>*</i></span>
                    <input value="<?=$nome?>" type="text" name="nome" class="nome" rel="<?php echo $nome;?>" size="35" maxlength="150">
                </li>
                <li>
                    <span>Nome Fantasia<i>*</i></span>
                    <input value="<?=$nome_fantasia?>" type="text" name="nome_fantasia" size="35" maxlength="50" rel="<?=$nome_fantasia?>" class="nome_fantasia">
                </li>
            </ul>
            <ul class="half">
                <li>
                    <span>CNPJ/CPF<i>*</i></span>
                    <input value="<?=$cnpj?>" type="text" align="right" name="cnpj" id="cnpj" size="16" maxlength="14" readonly />
                </li>
                <li>
                    <span>I.E.</span>
                    <input value="<?=$ie?>" type="text" name="ie" id="ie" size="20" maxlength="14" rel="<?=$ie?>" class="ie"/>
                </li>
            </ul>
            <div class="sep"></div>

            <div class="title mb">
                <h1>Informações de Contato</h1>
            </div>
            <ul class="half">
                <li>
                    <span>Contato<i>*</i></span>
                    <input value="<?=$contato?>" type="text" class='contato' name="contato" size="35" maxlength="30" rel="<?php echo $contato;?>">
                </li>
                <li>
                    <span>Telefone<i>*</i></span>
                    <input value="<?=$telefone?>" type="text" name="telefone" class="telefone" rel="<?php echo $telefone;?>" size="15" maxlength="14" />
                </li>
            </ul>
            <ul class="half">
                <li>
                    <span>E-mail<i>*</i></span>
                    <input value="<?=$email?>" type="text" name="email" id="email" class="email" size="35" maxlength="50" rel="<?php echo $email;?>" <? echo $readonly;//Adicionada regra para o posto alterar o e-mail caso esteja errado na tbl_posto ?> />
                    <input type="hidden" name="email_antigo" id="email_antigo" value="<?=$email?>">
                </li>
                <li>
                    <span>Fax</span>
                    <input value="<?=$fax?>" type="text" name="fax" id="fax" class="fax" size="15" maxlength="14" rel="<?php echo $fax;?>" />
                </li>
            </ul>
            <div class="sep"></div>
            <div class="title mb">
                <h1>Endereço</h1>
            </div>
            <ul class="half">
                <li>
                    <span>CEP<i>*</i></span>
                    <input value="<?=$cep?>" type="text" name="cep" id="cep" class="cep" size="15" maxlength="10" rel="<?php echo $cep;?>" /><div class="loading tac" id="loading-cep" style="display: none;" ><img style="width:18px;margin-left: 364px;margin-top: 1px;" src="../admin/imagens/ajax-loader.gif" /></div>
                </li>
                <li>
                    <span>Logradouro<i>*</i></span>
                    <input value="<?=$endereco?>" type="text" name="endereco" id="endereco" class="endereco" size="35" maxlength="50" rel="<?php echo $endereco;?>" />
                </li>
                <li>
                    <span>Número<i>*</i></span>
                    <input value="<?=$numero?>" type="text" name="numero" id="numero" class="numero" size="35" maxlength="10" rel="<?php echo $numero;?>" align="right" <?=$readonly?> />
                </li>
                <li>
                    <span>Complemento</span>
                    <input value="<?=$complemento?>" type="text" name="complemento" id="complemento" class="complemento" size="35" maxlength="20" rel="<?php echo $complemento;?>" />
                </li>
            </ul>
            <ul class="half">
                <li>
                    <span>Bairro<i>*</i></span>
                    <input value="<?=$bairro?>" type="text" name="bairro" id="bairro" class="bairro" size="35" maxlength="40" rel="<?php echo $bairro;?>" />
                </li>
                <li>
                    <span>Cidade<i>*</i></span>
                    <input value="<?=$cidade?>" type="text" name="cidade" id="cidade" class="cidade" size="25" maxlength="30" rel="<?php echo $cidade;?>" readonly="readonly" />
                    <input type="hidden" name="cidade_antigo" id="cidade_antigo" value="<?=$cidade?>">
                </li>
                <li>
                    <span>Estado<i>*</i></span>
                        <select name="estado" id="estado" class="estado" rel="<?php echo $estado;?>">
                        <option value=""></option>
                        <?php
                            foreach ($estados as $sigla=>$nome_estado) {
                                echo "\t\t\t\t\t<option value='$sigla'";
                                if ($sigla == $estado) echo " selected";
                                echo ">$nome_estado</option>\n";
                            }
                        ?>
                    </select>
                </li>
            </ul>
            <div class="sep"></div>

            <?php
                $outros_sistema = 'N';
                if (!empty($info_sistema_1) && !empty($info_marca_1) && !empty($info_vantagem_1)) {
                    $outros_sistema = 'S';
                }
                if (!empty($info_sistema_2) && !empty($info_marca_2) && !empty($info_vantagem_2)) {
                    $outros_sistema = 'S';
                }
                if (!empty($info_sistema_3) && !empty($info_marca_3) && !empty($info_vantagem_3)) {
                    $outros_sistema = 'S';
                }
            ?>
            <div class="title mb">
                <h1>Informações</h1>
            </div>
            <ul>
                <li class="sis_telecontrol">
                    <span>Você usa outros sistemas além do Telecontrol?</span>
                    <ul class="r-btns">
                        <li>
                            <input type="radio" id="s_outro_sis" name="outros_sistema" value="S" onChange="mostraOutrosSis('s')"
                            <?php
                                $display = 'none';
                                if ($outros_sistema == "S") {
                                    echo ' checked="checked" ';
                                    $display = 'block';
                                }
                            ?>
                            /> Sim
                        </li>
                        <li>
                            <input type="radio" id="n_outro_sis" name="outros_sistema" value="N" onChange="mostraOutrosSis('n')"
                            <?php
                                if ($outros_sistema == "N") {
                                    echo ' checked="checked" ';
                                    $display = 'none';
                                }
                            ?>
                            /> Não
                        </li>
                    </ul>
                </li>
                <li id="outros_sistemas" style="display: <?php echo $display ?>;">
                    <span>Para quais marcas? Quais são as vantagens?</span>
                    <ul class="three-col">
                        <li>
                            <span>Nome do Sistema</span>
                            <input type="text" name="inf_sistema1" id="inf_sistema1" size="35" value="<?php echo $info_sistema_1;?>"  class="inf_sistemas">
                        </li>
                        <li>
                            <span>Marca</span>
                            <input type="text" name="inf_marca1" id="inf_marca1" size="35" value="<?php echo $info_marca_1;?>" class="inf_sistemas">
                        </li>
                        <li>
                            <span>Vantagens</span>
                            <input type="text" name="inf_vantagem1" id="inf_vantagem1" size="35" maxlength="250" value="<?php echo $info_vantagem_1;?>" class="inf_sistemas">
                        </li>

                        <li>
                            <span>Nome do Sistema</span>
                            <input type="text" name="inf_sistema2" id="inf_sistema2" size="35" value="<?php echo $info_sistema_2;?>" class="inf_sistemas">
                        </li>
                        <li>
                            <span>Marca</span>
                            <input type="text" name="inf_marca2" id="inf_marca2" size="35" value="<?php echo $info_marca_2;?>" class="inf_sistemas">
                        </li>
                        <li>
                            <span>Vantagens</span>
                            <input type="text" name="inf_vantagem2" id="inf_vantagem2" size="35" maxlength="250" value="<?php echo $info_vantagem_2;?>" class="inf_sistemas">
                        </li>

                        <li>
                            <span>Nome do Sistema</span>
                            <input type="text" name="inf_sistema3" id="inf_sistema3" size="35" value="<?php echo $info_sistema_3;?>" class="inf_sistemas">
                        </li>
                        <li>
                            <span>Marca</span>
                            <input type="text" name="inf_marca3" id="inf_marca3" size="35" value="<?php echo $info_marca_3;?>" class="inf_sistemas">
                        </li>
                        <li>
                            <span>Vantagens</span>
                            <input type="text" name="inf_vantagem3" id="inf_vantagem3" size="35" maxlength="250" value="<?php echo $info_vantagem_3;?>" class="inf_sistemas">
                        </li>
                    </ul>
                </li>
                <li class="op_no">
                    <span>Na sua opinião, qual o melhor sistema informatizado de ordens de serviço? Por quê?<i>*</i></span>
                    <input type="text" name="melhor_sistema" id="melhor_sistema_txt" value="<?=$melhor_sistema?>">
                </li>
            </ul>

            <script>
                // $('.sis_telecontrol input[type=radio]').on('change', function() {
                //  var op = $(this).val();
                //  console.log(op);
                //  if(op=='yes') {
                //      $('.op_no').css('display','none');
                //      $('.op_yes').css('display','block');
                //  } else {
                //      $('.op_yes').css('display','none');
                //      $('.op_no').css('display','block');
                //  }
                // });
            </script>
            <?php
                if(strpos($linhas, 'OUTRAS') !== false){
                    $linha_6_obs = substr($linhas, strrpos($linhas,',')+1);
                    $bloqueia_campo_6 = "";
                }/*else{
                    $linha_6_obs = "";
                    $bloqueia_campo_6 = "readonly='true'";
                }*/
                // HD-1867132
                if($_GET['fabrica'] == 114 AND $_GET['linha'] == 811){
                    $bloqueia_checkbox = " onclick='return false;'";
                    if(strlen($linha_6_obs) > 0){
                        $linha_6 .= "checado";
                        $linha_6_obs .= ", MASTER CHEF";
                    }else{
                        $linha_6 .= "checado";
                        $linha_6_obs .= "MASTER CHEF";
                    }
                }

                if($_GET['fabrica'] == 114 AND $_GET['linha'] == 710){
                    $bloqueia_checkbox = " onclick='return false;'";
                    if(strlen($linha_6_obs) > 0){
                        $linha_6 .= "checado";
                        $linha_6_obs .= ", COMPRESSORES MICHELIN";
                    }else{
                        $linha_6 .= "checado";
                        $linha_6_obs .= "COMPRESSORES MICHELIN";
                    }
                }
                // fim HD-1867132
            ?>
            <div class="sep"></div>
            <div class="title mb">
                <h1>Linhas que trabalha *</h1>
            </div>
            <ul class="checklist">
                <li>
                    <input type="checkbox" class="concordo" name="linha_1" id="linha_1" value='BRANCA' <?if(strlen($linha_1) > 0 or strpos($linhas, 'BRANCA') !== false) echo "checked";?> />
                    <span>BRANCA - adega, refrigeração, ar-condicionado (split, janela,..)</span>
                </li>
                <li>
                    <input type="checkbox" class="concordo"  name="linha_2" id="linha_2" value='MARROM' <?if(strlen($linha_2) > 0 or strpos($linhas, 'MARROM') !== false) echo "checked";?> />
                    <span>MARROM - áudio e video (TV, DVD, MP3, MP4, ...)</span>
                </li>
                <li>
                    <input type="checkbox" class="concordo"  name="linha_3" id="linha_3" value='ELETROPORTATEIS' <?if(strlen($linha_3) > 0 or strpos($linhas, 'ELETRO') !== false) echo "checked";?> />
                    <span>ELETROPORTÁTEIS - liquidificadores, ventiladores, ...</span>
                </li>
                <li>
                    <input type="checkbox" class="concordo"  name="linha_4" id="linha_4" value='INFORMATICA' <?if(strlen($linha_4) > 0 or strpos($linhas, 'INFORM') !== false) echo "checked";?> />
                    <span>INFORMÁTICA - notebook, monitores, ....</span>
                </li>
                <li>
                    <input type="checkbox" class="concordo"  name="linha_5" id="linha_5" value='FERRAMENTAS' <?if(strlen($linha_5) > 0 or strpos($linhas, 'FERRAM') !== false) echo "checked";?> />
                    <span>FERRAMENTAS - furadeiras, serras, motosserras ...</span>
                </li>
                <li>
                    <input type="checkbox" class="concordo"  name="linha_7" id="linha_7" value="LAVADORAS DE ALTA PRESSAO"
                    <?php
                        if (strlen($linha_7) > 0 or strpos($linhas, "LAVADORAS DE ALTA PRESSAO") !== false) {
                            echo ' checked ';
                        }
                    ?>
                    />
                    <span>LAVADORAS DE ALTA PRESSÃO</span>
                </li>

                <li>
                    <input type="checkbox" class="concordo"  name="linha_6" class="linha_6" id="linha_6" value='OUTRAS' <?if(strlen($linha_6) > 0 or strpos($linhas, 'OUTRAS') !== false) echo "checked".$bloqueia_checkbox; ?>/>
                    <span>Outras - Quais?</span>
                    <input type="text" name="linha_6_obs" class="linha_6_obs" id="linha_6_obs" <?php echo $bloqueia_campo_6;?> size='70' value='<?php echo $linha_6_obs;?>'>
                </li>
            </ul>
            <div class="sep"></div>
            <div class="title mb">
                <h1>Informações</h1>
            </div>
            <ul class="half">
                <li>
                    <span>Quantidade de funcionários<i>*</i><br><br></span>
                    <input value="<?=$funcionarios?>" align="right" type="text" name="funcionarios" class="funcionarios" size='10' maxlength="3" style="margin-left: 7px;" />
                </li>
                <li>
                    <span>Quantidade de ordens de serviço mensal<i>*</i></span>
                    <input value="<?=$oss?>" align="right" type="text" name="oss" size='10' class="oss" maxlength="5" style="margin-left: 7px;" />
                </li>
            </ul>
            <ul class="half">
                <li>
                    <span>Quais marcas sua empresa é autorizada atualmente?<i>*</i></span>
                    <input type="text" name="fabricantes" size='50' class='marcas' id="fabricantes" value='<?=$fabricantes?>'>
                </li>
                <li>
                    <span>Sua empresa atende cidades próximas? Quais?<i>*</i></span>
                    <input type="text" name="atende_cidade_proxima" size='50' id="atende_cidade_proxima" value='<?=$atende_cidade_proxima?>'>
                </li>
            </ul>
            <div class="sep"></div>
            <div class="title mb">
                <h1>Fábricas de Interesse *</h1>
            </div>

            <div style="margin-bottom: 13px; color: red;">
                <span>Fábricas já credenciadas</span>
            </div>

            <ul class="checklist four-col">

                <li>
                    <input type="checkbox" class="marca_todas_fabricas" id="marca_todas_fabricas" name="marca_todas_fabricas" style="border: none;">
                    <span>Marcar todas</span>

                </li>
                <?php
                    //BUSCA AS FABRICAS ATIVAS
                    //$sql_fabrica = "SELECT fabrica,nome,ativo_fabrica FROM tbl_fabrica where ativo_fabrica = 't' order by nome";
                    //$res_fabrica = pg_query($con,$sql_fabrica);

                    $sql = "
                        SELECT
                        fabrica,
                        ativo_fabrica,
                        nome,
                        'f' AS fabrica_marca
                        FROM tbl_fabrica
                        where ativo_fabrica = 't'
                        AND fabrica NOT IN ($not_in_fabricas)

                        /*UNION

                        SELECT
                        tbl_marca.marca,
                        tbl_marca.ativo,
                        tbl_marca.nome,
                        'm' AS fabrica_marca
                        FROM tbl_fabrica
                        JOIN tbl_marca
                        ON tbl_fabrica.fabrica = tbl_marca.fabrica
                        AND tbl_marca.ativo = 't'
                        where tbl_fabrica.ativo_fabrica = 't'
                        AND marca NOT IN ($not_in_marcas)
                        */
                        ORDER BY nome
                        ";
                    $res = pg_query($con, $sql);
                    $rows = pg_num_rows($res);
                    if ($rows > 0){
                ?>
                        <input type="hidden" name="total_fab" id="total_fab" value="<?php echo $rows; ?>" />
                <?php
                        for ($a=0; $a < $rows; $a++){
                            $id_fabrica   = pg_fetch_result($res, $a, 'fabrica');
                            $nome_fabrica = ucwords(strtolower(trim(pg_fetch_result($res, $a, 'nome'))));
                            $fabrica_marca = pg_fetch_result($res, $a, 'fabrica_marca');
                            $$fabrica_nome = false;
                            if ($id_fabrica == 114){
                                 if (isset($_POST['cobimex']) and $_POST['cobimex'] == 'ok'){
                                    #$checked_cobimex = "CHECKED";
                                    $cobimex = true;
                                }
                            }elseif ($id_fabrica == 122){
                                if (isset($_POST['wurth']) and $_POST['wurth'] == 'ok'){
                                    #$checked_wurth = "CHECKED";
                                    $wurth = true;
                                }
                            }elseif ($id_fabrica == 123){
                                if (isset($_POST['positec']) and $_POST['positec'] == 'ok'){
                                    #$checked_positec = "CHECKED";
                                    $positec = true;
                                }
                            }elseif ($id_fabrica == $_GET['fabrica']){
                                if (isset($_POST[$fabrica_nome]) and $_POST[$fabrica_nome] == 'ok'){
                                    #$checked_saintgobain = "CHECKED";
                                    $$fabrica_nome = true;
                                }
                            }else{
                                $checked_cobimex = '';
                                $cobimex = false;
                            }
                            $literals = array (
                                                "Delonghi" => "DeLonghi",
                                                "Dwt" => "DWT",
                                                "Ibbl" => "IBBL",
                                                "Nks" => "NKS"
                                            );
                            if (array_key_exists($nome_fabrica, $literals)) {
                                $nome_fabrica = $literals["$nome_fabrica"];
                            }
                            $fabrica_credenciadas = str_replace('{','',$fabrica_credenciadas);
                            $fabrica_credenciadas = str_replace('}','',$fabrica_credenciadas);
                            $cod_fabrica = explode(",",$fabrica_credenciadas);
                            foreach($cod_fabrica as $variavel_fabrica) {
                                if($variavel_fabrica == $id_fabrica){
                                    #$check_fabrica = "checked onclick='return false;'"; //hd_chamado=2813100 retirado a pedido do ronaldo
                                    $check_fabrica = "checked";
                                }
                            }
                            $color ="";
                            foreach ($arrayCred as $key => $value) { ////2780042
                                if($value == $id_fabrica){
                                    $check_fabrica = "checked onclick='return false;'";
                                    $color = "style='color:red;'";
                                }
                            }


                            ?>
                            <li>
                                <?php if (($cobimex and $id_fabrica == 114) or ($wurth and $id_fabrica == 122) or ($positec and $id_fabrica == 123) or ($$fabrica_nome and !empty($_GET['fabrica']))): ?>
                                    <input type="checkbox" class="todas_fabricas" checked onclick='return false;' >
                                    <input type="hidden" name="fabrica_<?php echo $a;?>" value="<?php echo $fabrica_marca . ':' . $id_fabrica;?>" >
                                <?php else: ?>
                                    <input type="checkbox" class="todas_fabricas" <?php echo $check_fabrica; ?> data-fabrica-checked='<?=$check_fabrica?>' name="fabrica_<?php echo $a;?>" id="todas_fabricas" value="<?php echo $fabrica_marca . ':' . $id_fabrica;?>">
                                <?php endif ?>
                                <span <?=$color?> ><?php echo  $nome_fabrica; ?></span>
                            </li>
                            <?php
                            $check_fabrica = '';
                            $id_fabrica ="";
                        }
                    }

                    //BUSCA AS MARCAS ATIVAS
                    $sql_marcas = "SELECT
                                        tbl_marca.marca,
                                        tbl_marca.ativo,
                                        tbl_marca.nome
                                    FROM tbl_fabrica
                                    JOIN tbl_marca
                                    ON tbl_fabrica.fabrica = tbl_marca.fabrica
                                    AND tbl_marca.ativo = 't'
                                    where tbl_fabrica.ativo_fabrica = 't'
                                    order by tbl_marca.nome";
                    //$res_marca = pg_query($con,$sql_marcas);
                    $ttl_fabrica = pg_num_rows($res_marca);
                    if(pg_num_rows($res_marca) > 0){
                        for($a=0;$a < $ttl_fabrica;$a++){
                            $id_marca    = pg_fetch_result($res_marca,$a,'marca');
                            $nome_marca  = pg_fetch_result($res_marca,$a,'nome');

                            $marcas_credenciadas = str_replace('{','',$marcas_credenciadas);
                            $marcas_credenciadas = str_replace('}','',$marcas_credenciadas);
                            $cod_marca = explode(",",$marcas_credenciadas);
                            foreach($cod_marca as $variavel_marca) {
                                if($variavel_marca == $id_marca){
                                    #$check_marca = "checked";
                                }
                            }
                            ?>
                            <li>
                                <input type="checkbox" class="todas_fabricas" <?php echo $check_marca;?> name="fabrica_<?php echo $id_marca;?>" id="todas_marcas" value="<?php echo $id_marca;?>">

                                <span><?=$nome_marca?></span>
                            </li>
                            <?php
                            $check_marca    = '';
                            $id_marca       ="";
                        }
                    }

                    $check_outras_fabricas = '';
                    $disabled_outras_fabricas = '';
                    if(strlen($marca_ser_autorizada) > 0){
                        $check_outras_fabricas = "checked";
                        $disabled_outras_fabricas = '';
                    }else{
                        $disabled_outras_fabricas = 'disabled="true"';
                    }

                ?>

                <li class="full">
                    <input type="checkbox" name="outras_fabricas" <?php echo $check_outras_fabricas;?> id="outras_fabricas" value="outras">
                    <span>Outras</span>
                    <input type="text" name="opcao_outras_fabricas" id="opcao_outras_fabricas" <?php echo $disabled_outras_fabricas;?> value='<?=$marca_ser_autorizada?>'>
                </li>
            </ul>

            <div class="sep"></div>
            <div class="title mb">
                <h1>Posto tem condições de atender *</h1>
            </div>
            <ul class="checklist">
                <li>
                    <input type="checkbox" class="atender" name="condicao_1" id="condicao_1" value='VISITA TECNICA' <?if($visita_tecnica == 't') echo "checked";?> />
                    <span>Visita Técnica</span>
                </li>
                <li>
                    <input type="checkbox" class="atender"  name="condicao_2" id="condicao_2" value='ATENDE CONSUMIDOR - BALCÃO' <?if($atende_consumidor_balcao == 't') echo "checked";?> />
                    <span>Atende Consumidor - balcão</span>
                </li>
                <li>
                    <input type="checkbox" class="atender"  name="condicao_3" id="condicao_3" value='ATENDE REVENDAS' <?if($atende_revendas == 't') echo "checked";?> />
                    <span>Atende revendas</span>
                </li>
            </ul>
            <div class="sep"></div>
            <div class="title mb">
                <h1>Fotos / Descrição</h1>
            </div>
            <ul>
                <li><span>Três fotos da sua loja (fachada, recepção e laboratório) com as extensões JPG e tamanho máximo de 2MB.</span></li>
                <li>
                <ul class="three-col">
                    <li>
                        <span>Fachada<i>*</i></span>
                        <input type='file' name='arquivo1' id='arquivo1' class="arquivo1" accept="jpeg|jpg" size='1' />
                        <!-- <div class="img bg-contain" style="background-image:url(images/no-img.png)"></div> -->
                        <?php
                            if (is_numeric($posto))
                            $img_path = $caminho_path.$cnpj;
                            $img_caminho = $caminho_imagem.$cnpj;
                            //echo  dirname(preg_replace("&admin(_cliente)?/&", '', $_SERVER['PHP_SELF'])) ."/nf_digitalizada");
                            if (file_exists($img_caminho."_1.jpg")) $img_ext = "jpg";
                            if (file_exists($img_caminho."_1.png")) $img_ext = "png";
                            if (file_exists($img_caminho."_1.gif")) $img_ext = "gif";
                            if ($img_ext) {
                                $img_src = $img_path."_1.$img_ext";
                                echo '<input type="hidden" id="old_foto1" value="1" />';
                            ?>
                                <img src="<?php echo $img_src;?>" style='width: 256px;'/>
                            <?}
                            unset($img_ext);
                        ?>
                    </li>
                    <li>
                        <span>Recepção<i>*</i></span>
                        <input type='file' name='arquivo2' id='arquivo2' class="arquivo2" accept="jpeg|jpg" size='1' />
                        <!--<div class="img bg-contain" style="background-image:url(images/no-img.png)"></div>-->
                        <?
                            if (file_exists($img_caminho."_2.jpg")) $img_ext = "jpg";
                            if (file_exists($img_caminho."_2.png")) $img_ext = "png";
                            if (file_exists($img_caminho."_2.gif")) $img_ext = "gif";
                            if ($img_ext) {
                                $img_src = $img_path."_2.$img_ext";
                                echo '<input type="hidden" id="old_foto2" value="1" />';
                            ?>
                                <img src="<?php echo $img_src;?>" style='width: 256px;' />
                            <?}
                            unset($img_ext);
                        ?>
                    </li>
                    <li>
                        <span>Laboratório<i>*</i></span>
                        <input type='file' name='arquivo3' id='arquivo3' class="arquivo3" size='1' accept="jpeg|jpg" />
                        <!-- <div class="img bg-contain" style="background-image:url(images/no-img.png)"></div> -->
                        <?php
                            if (file_exists($img_caminho."_3.jpg")) $img_ext = "jpg";
                            if (file_exists($img_caminho."_3.png")) $img_ext = "png";
                            if (file_exists($img_caminho."_3.gif")) $img_ext = "gif";
                            if ($img_ext) {
                                $img_src = $img_path."_3.$img_ext";
                                echo '<input type="hidden" id="old_foto3" value="1" />';
                            ?>
                                <img src="<?php echo $img_src;?>" style='width: 256px;' />
                            <?php
                            }
                        ?>
                    </li>
                </ul>
                </li>
                <li>
                    <span>Descrição de sua Autorizada</span>
                    <textarea name="descricao" id="campo_descricao"><?=$observacao?></textarea>
                </li>
            </ul>
            <input type="hidden" name='btn_acao' value='gravar'>
            <input type="button" class="input_gravar" value="<?=ttext ($a_labels, "gravar", $cook_idioma)?>" onClick="verifica_submit();" />

            <!-- <button type="submit" class="input_gravar" value="<?=ttext ($a_labels, "gravar", $cook_idioma)?>" onClick="verifica_submit();"><i class="fa fa-check" ></i>Credenciar</button>
         -->
        </form>


    <?php endif; ?>


    </div>
</section>

<?php include('site_estatico/footer.php') ?>
