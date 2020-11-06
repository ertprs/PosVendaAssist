<?php
/**
 *
 * relatorio_extratificacao.php
 *
 * @author  Francisco Ambrozio
 * @version 2015.05
 *
 */

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include "autentica_admin.php";
$layout_menu = "gerencia";
$title = "RELAT”RIO DE ESTRATIFICA«√O";
include "cabecalho.php";
//  error_reporting(E_ALL);
include 'relatorio_extratificacao_devolucao.class.php';
/**
 *
 * REGRAS:
 *
 *   - o relatÛrio È extraÌdo de 24 meses retroativo ao mÍs atual
 *   - o admin seleciona familia, meses e Ìndice
 *   - de acordo com o n˙mero de meses selecionado conta as OSs de cada mÍs
 *   - taxa de falha: total de OSs (meses) / total produÁ„o
 *   - populaÁ„o: soma de N meses anteriores
 *
 *
 *
 */
$relatorio = new relatorioExtratificacaoDevolucao;
$relatorio->run();

$resultado = $relatorio->getResultView();

$data_inicial = $relatorio->getDataInicial();
if (!empty($data_inicial)) {
    $arr_data = explode('-', $data_inicial);
    $ano_pesquisa = $arr_data[0];
    $mes_pesquisa = $arr_data[1];
}

$familia = $relatorio->getFamilia();
$regiao = $relatorio->getRegiao();
$sem_peca = $relatorio->getSemPeca();
$qtde_meses = $relatorio->getMeses();
$irc = $relatorio->getIndexIRC();
$fornecedores = $relatorio->getFornecedores();
$revendas = $relatorio->getRevendas();
$produtos = $relatorio->getProdutos();
$pecas = $relatorio->getPecas();
$postos = $relatorio->getPostos();
$periodo = $relatorio->getPeriodo();

$posto = '';
if (!empty($_POST['posto'])) {
    $posto = $_POST['posto'];
}

if (empty($qtde_meses)) {
    $qtde_meses = 15;
}

$msg_erro = $relatorio->getMsgErro();

?>

<script src="plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
<script type='text/javascript' src='plugins/posvenda_jquery_ui/js/jquery-ui-1.10.3.custom.js'></script>
<script type='text/javascript' src='plugins/posvenda_jquery_ui/development-bundle/ui/jquery.ui.core.js'></script>
<script type='text/javascript' src='plugins/posvenda_jquery_ui/development-bundle/ui/jquery.ui.widget.js'></script>
<script type='text/javascript' src='plugins/posvenda_jquery_ui/development-bundle/ui/jquery.ui.autocomplete.js'></script>
<script type='text/javascript' src='../js/jquery.numeric.js'></script>
<link rel="stylesheet" type="text/css" href="../plugins/shadowbox/shadowbox.css" media="all">
<script src="../plugins/shadowbox/shadowbox.js" type="text/javascript"></script>
<link rel="stylesheet" type="text/css" href="plugins/posvenda_jquery_ui/css/posvenda_ui/jquery-ui-1.10.3.custom.css" />

<script>
    var mes = null;
    function pareto(fabricacao, abertura, meses, familia, matriz_filial, peca01, peca02, fornecedores, datas, revendas, produtos, pecas, posto) {

        if(!matriz_filial){
            matriz_filial = '';
        }

        if (!peca01) {
            peca01 = 0;
        }

        if (!peca02) {
            peca02 = 0;
        }

        if (!fornecedores) {
            fornecedores = '';
        }
        if (!revendas) {
            revendas = '';
        }

        if (!datas) {
            datas = '';
        }

        if (!produtos) {
            produtos = '';
        }

        if (!pecas) {
            pecas = '';
        }

        if (!posto) {
            posto = '';
        }

        var regiao = $('#regiao_pesquisada').val();
        
        var desconsiderar_conversor = $("#desconsidera_conversor").is(":checked");
        
        if( $("#sem_peca").is(":checked") ){
                var sem_peca = $("#sem_peca").val();
                var url="relatorio_extratificacao_pareto_sem_peca_vdnf.php?ab=" + abertura + "&nf=" + fabricacao + "&fm=" + meses + "&f=" + familia + "&defeito=" + sem_peca + "&po=" + posto + "&dcg=" + desconsiderar_conversor;
        }else{
                var url="relatorio_extratificacao_devolucao_pareto_vdnf.php?nf=" + fabricacao + "&ab=" + abertura + "&fm=" + meses + "&fa=" + familia + "&p1=" + peca01 + "&p2=" + peca02 + "&fo=" + fornecedores + "&d=" + datas + "&pd=" + produtos + "&pc=" + pecas + "&po=" + posto + "&dcg=" + desconsiderar_conversor+"&matriz_filial="+matriz_filial;
        }

        window.open (url, "pareto", "height=640,width=1020,scrollbars=1");
    }

    function mostraRelatorio(relatorio) {
        var tx_os = document.getElementById('taxa_falha');
        var tx_os_comp = document.getElementById('tx_falha_comparativo_0');
        var tx_os_comp_15 = document.getElementById('tx_falha_comparativo_1');
        var tx_forn = document.getElementById('taxa_falha_fornecedor');
        var irc = document.getElementById('irc_0');
        var irc_15 = document.getElementById('irc_1');
        var irc_15_mes = document.getElementById('irc_15_mes');
        //var irc_revenda = document.getElementById('irc_revenda');
        var cfe_parq = document.getElementById('gr_cfe_0');
        var cfe_prod = document.getElementById('gr_cfe_1');
        var cfe_fat = document.getElementById('gr_cfe_2');
        var maiores_defeitos = document.getElementById('gr_maiores_defeitos');

        if (relatorio == 'tx_os') {
            tx_os.style.display = "block";
            tx_os_comp.style.display = "none";
            tx_os_comp_15.style.display = "none";
            tx_forn.style.display = "none";
            irc.style.display = "none";
            irc_15.style.display = "none";
            irc_15_mes.style.display = "none";
            //irc_revenda.style.display = "none";
            cfe_parq.style.display = "none";
            cfe_prod.style.display = "none";
            if (cfe_fat) {
                cfe_fat.style.display = "none";
            }
            maiores_defeitos.style.display = "none";
        }
        else if (relatorio == 'tx_os_comp') {
            tx_os.style.display = "none";
            tx_os_comp.style.display = "block";
            tx_os_comp_15.style.display = "none";
            tx_forn.style.display = "none";
            irc.style.display = "none";
            irc_15.style.display = "none";
            irc_15_mes.style.display = "none";
            //irc_revenda.style.display = "none";
            cfe_parq.style.display = "none";
            cfe_prod.style.display = "none";
            if (cfe_fat) {
                cfe_fat.style.display = "none";
            }
            maiores_defeitos.style.display = "none";
        }
        else if (relatorio == 'tx_os_comp_15') {
            tx_os.style.display = "none";
            tx_os_comp.style.display = "none";
            tx_os_comp_15.style.display = "block";
            tx_forn.style.display = "none";
            irc.style.display = "none";
            irc_15.style.display = "none";
            irc_15_mes.style.display = "none";
            //irc_revenda.style.display = "none";
            cfe_parq.style.display = "none";
            cfe_prod.style.display = "none";
            if (cfe_fat) {
                cfe_fat.style.display = "none";
            }
            maiores_defeitos.style.display = "none";
        }
        else if (relatorio == 'tx_forn') {
            tx_os.style.display = "none";
            tx_os_comp.style.display = "none";
            tx_os_comp_15.style.display = "none";
            tx_forn.style.display = "block";
            irc.style.display = "none";
            irc_15.style.display = "none";
            irc_15_mes.style.display = "none";
            //irc_revenda.style.display = "none";
            cfe_parq.style.display = "none";
            cfe_prod.style.display = "none";
            if (cfe_fat) {
                cfe_fat.style.display = "none";
            }
            maiores_defeitos.style.display = "none";
        }
        else if (relatorio == 'irc') {
            tx_os.style.display = "none";
            tx_os_comp.style.display = "none";
            tx_os_comp_15.style.display = "none";
            tx_forn.style.display = "none";
            irc.style.display = "block";
            irc_15.style.display = "none";
            irc_15_mes.style.display = "none";
            //irc_revenda.style.display = "none";
            cfe_parq.style.display = "none";
            cfe_prod.style.display = "none";
            if (cfe_fat) {
                cfe_fat.style.display = "none";
            }
            maiores_defeitos.style.display = "none";
        }
        else if (relatorio == 'irc_15') {
            tx_os.style.display = "none";
            tx_os_comp.style.display = "none";
            tx_os_comp_15.style.display = "none";
            tx_forn.style.display = "none";
            irc.style.display = "none";
            irc_15.style.display = "block";
            irc_15_mes.style.display = "none";
            //irc_revenda.style.display = "none";
            cfe_parq.style.display = "none";
            cfe_prod.style.display = "none";
            if (cfe_fat) {
                cfe_fat.style.display = "none";
            }
            maiores_defeitos.style.display = "none";
        }
        else if (relatorio == 'irc_15_mes') {
            tx_os.style.display = "none";
            tx_os_comp.style.display = "none";
            tx_os_comp_15.style.display = "none";
            tx_forn.style.display = "none";
            irc.style.display = "none";
            irc_15.style.display = "none";
            irc_15_mes.style.display = "block";
            //irc_revenda.style.display = "none";
            cfe_parq.style.display = "none";
            cfe_prod.style.display = "none";
            if (cfe_fat) {
                cfe_fat.style.display = "none";
            }
            maiores_defeitos.style.display = "none";
        }
        // else if (relatorio == 'irc_revenda') {
        //     tx_os.style.display = "none";
        //     tx_os_comp.style.display = "none";
        //     tx_os_comp_15.style.display = "none";
        //     tx_forn.style.display = "none";
        //     irc.style.display = "none";
        //     irc_15.style.display = "none";
        //     irc_15_mes.style.display = "none";
        //     irc_revenda.style.display = "block";
        //     irc_revenda.style.display = "none";
        //     cfe_parq.style.display = "none";
        //     cfe_prod.style.display = "none";
        //     if (cfe_fat) {
        //         cfe_fat.style.display = "none";
        //     }
        //     maiores_defeitos.style.display = "none";
        // }
        else if (relatorio == 'cfe_parq') {
            tx_os.style.display = "none";
            tx_os_comp.style.display = "none";
            tx_os_comp_15.style.display = "none";
            tx_forn.style.display = "none";
            irc.style.display = "none";
            irc_15.style.display = "none";
            irc_15_mes.style.display = "none";
           // irc_revenda.style.display = "none";
            cfe_parq.style.display = "block";
            cfe_prod.style.display = "none";
            if (cfe_fat) {
                cfe_fat.style.display = "none";
            }
            maiores_defeitos.style.display = "none";
        }
        else if (relatorio == 'cfe_prod') {
            tx_os.style.display = "none";
            tx_os_comp.style.display = "none";
            tx_os_comp_15.style.display = "none";
            tx_forn.style.display = "none";
            irc.style.display = "none";
            irc_15.style.display = "none";
            irc_15_mes.style.display = "none";
            //irc_revenda.style.display = "none";
            cfe_parq.style.display = "none";
            cfe_prod.style.display = "block";
            if (cfe_fat) {
                cfe_fat.style.display = "none";
            }
            maiores_defeitos.style.display = "none";
        }
        else if (relatorio == 'cfe_fat') {
            tx_os.style.display = "none";
            tx_os_comp.style.display = "none";
            tx_os_comp_15.style.display = "none";
            tx_forn.style.display = "none";
            irc.style.display = "none";
            irc_15.style.display = "none";
            irc_15_mes.style.display = "none";
            //irc_revenda.style.display = "none";
            cfe_parq.style.display = "none";
            cfe_prod.style.display = "none";
            cfe_fat.style.display = "block";
            maiores_defeitos.style.display = "none";
        }
        else if (relatorio == 'maiores_defeitos') {
            tx_os.style.display = "none";
            tx_os_comp.style.display = "none";
            tx_os_comp_15.style.display = "none";
            tx_forn.style.display = "none";
            irc.style.display = "none";
            irc_15.style.display = "none";
            irc_15_mes.style.display = "none";
            //irc_revenda.style.display = "none";
            cfe_parq.style.display = "none";
            cfe_prod.style.display = "none";
            if (cfe_fat) {
                cfe_fat.style.display = "none";
            }
            maiores_defeitos.style.display = "block";
        }
    }

    function download(link) {
        window.location=link;
    }

    function getPecasByFamilia(familia, apagaVal) {
    $.ajax({
            url: "pecas_lb_familia.php?familia=" + familia,
            dataType: "text",
            success: function(data) {
                if (apagaVal) {
                    $('#peca01').val('');
                    $('#peca02').val('');
                }

                <?php if ($login_fabrica == 50 || $login_fabrica == 24): ?>
                    autocompletePecas(data);
                <?php else: ?>
                    autocomplete(data);
                <?php endif; ?>
            }
        });
    }

    function getProdutosByFamilia(familia, apagaVal) {
        $.ajax({
            url: "produtos_familia.php?familia=" + familia,
            dataType: "text",
            success: function(data) {
                if (apagaVal) {
                    $('#produto_input').val('');
                    $('#produtos').val('');
                }
                autocompleteProduto(data);
            }
        });
    }

    function autocomplete (data) {
        var pecas = $.parseJSON(data);

        if (!pecas) {
            return false;
        }

        $('#peca01').autocomplete({
            source: pecas,
            select: function (event, ui) {
                $("#peca01").val(ui.item['value']);
                return false;
            }
            }).data("uiAutocomplete")._renderItem = function (ul, item) {
                var text = item["value"];
                return $("<li></li>").data("item.autocomplete", item).append("<a>"+text+"</a>").appendTo(ul);
            };

        $('#peca02').autocomplete({
            source: pecas,
            select: function (event, ui) {
                $("#peca02").val(ui.item['value']);
                return false;
            }
            }).data("uiAutocomplete")._renderItem = function (ul, item) {
                var text = item["value"];
                return $("<li></li>").data("item.autocomplete", item).append("<a>"+text+"</a>").appendTo(ul);
            };
    }

    function autocompleteProduto (data) {
        var produto = $.parseJSON(data);
        if (!produto) {
            return false;
        }

        $('#produto_input').autocomplete({
            source: produto,
            select: function (event, ui) {
                $("#produto_input").val(ui.item['value']);
                var data = ui.item['data'];
                $("#produto_input_hidden").val(data);

                return false;
            }
        }).data("uiAutocomplete")._renderItem = function (ul, item) {
            var text = item["value"];

            return $("<li></li>").data("item.autocomplete", item).append("<a>"+text+"</a>").appendTo(ul);
        };
    }

    function autocompletePecas (data) {
        var peca = $.parseJSON(data);
        if (!peca) {
            return false;
        }

        $('#peca_input').autocomplete({
            source: peca,
            select: function (event, ui) {
                $("#peca_input").val(ui.item['value']);
                var data = ui.item['data'];
                $("#peca_input_hidden").val(data);

                return false;
            }
        }).data("uiAutocomplete")._renderItem = function (ul, item) {
            var text = item["value"];

            return $("<li></li>").data("item.autocomplete", item).append("<a>"+text+"</a>").appendTo(ul);
        };
    }

    function autocompletePosto (data) {
        var posto = $.parseJSON(data);
        if (!posto) {
            return false;
        }

        $('#posto_input').autocomplete({
            source: posto,
            select: function (event, ui) {
                $("#posto_input").val(ui.item['value']);
                var data = ui.item['data'];
                $("#posto_input_hidden").val(data);

                return false;
            }
        }).data("uiAutocomplete")._renderItem = function (ul, item) {
            var text = item["value"];

            return $("<li></li>").data("item.autocomplete", item).append("<a>"+text+"</a>").appendTo(ul);
        };
    }

  function removeAcento(strToReplace) {
      str_acento= "·‡„‚‰ÈËÍÎÌÏÓÔÛÚıÙˆ˙˘˚¸Á¡¿√¬ƒ…» ÀÕÃŒœ”“’÷‘⁄Ÿ€‹«";
      str_sem_acento = "aaaaaeeeeiiiiooooouuuucAAAAAEEEEIIIIOOOOOUUUUC";
      var nova="";

      for (var i = 0; i < strToReplace.length; i++) {
          if (str_acento.indexOf(strToReplace.charAt(i)) != -1) {
              nova+=str_sem_acento.substr(str_acento.search(strToReplace.substr(i,1)),1);
          } else {
              nova+=strToReplace.substr(i,1);
          }
      }

      return nova;
  }

    $(function() {
        Shadowbox.init();

        var familia = $('#familia').val();

        if (familia) { getPecasByFamilia(familia, false); getProdutosByFamilia(familia, false); };

        $('#add_fornecedor').click(function(){
            var fornecedor = removeAcento($('#fornecedor_input').val().toUpperCase());

            if (fornecedor) {
                var option = '<option value="' + fornecedor + '">' + fornecedor + '</option>';
                var hidden = '<input type="hidden" name="fornecedor[]" id="' + fornecedor + '" value="' + fornecedor + '" />';
                $('#fornecedores').append(option);
                $('#fornecedores').append(hidden);
                $("#fornecedor_input").val('');
            }
        });

        $('#rm_fornecedor').click(function(){
            $("select[name=fornecedores] option:selected").each(function () {
                var hidden = $(this).val();
                $(this).remove();
                $('#' + hidden).remove();
            });
        });

        $('#add_revenda').click(function(){
            var revenda = removeAcento($('#revenda_input').val().toUpperCase());
            var revendaHidden = revenda.replace(" ","_");
            var cnpj    = $('#revenda_hidden').val();

            if (revenda) {
                var option = '<option value="' + cnpj + '">'+ cnpj +' - ' + revenda + '</option>';
                var hidden = '<input type="hidden" name="revenda[]" id="' + revendaHidden + '" value="' + cnpj + '" />';
                $('#revendas').append(option);
                $('#revendas').append(hidden);
                $("#revenda_input").val('');
            }
        });

        $('#rm_revenda').click(function(){
            $("select[name=revendas] option:selected").each(function () {
                var hidden = $(this).val();
                hidden = hidden.replace(" ","_");
                $(this).remove();
                $('#' + hidden).remove();
            });
        });

        $('#add_produto').click(function(){
            var produto = removeAcento($('#produto_input').val().toUpperCase());
            var produto_id = $("#produto_input_hidden").val();

            if (produto) {
                var option = '<option value="' + produto + '" class="' + produto_id + '">' + produto + '</option>';
                var hidden = '<input type="hidden" name="produto[]" id="' + produto_id + '" value="' + produto_id + '" />';
                $('#produtos').append(option);
                $('#produtos').append(hidden);
                $("#produto_input").val('');
            }
        });

        $('#rm_produto').click(function(){
            $("select[name=produtos] option:selected").each(function () {
                var hidden = $(this).attr("class");
                $(this).remove();
                $('#' + hidden).remove();
            });
        });

        $('#add_peca').click(function(){
            var peca = removeAcento($('#peca_input').val().toUpperCase());
            var peca_id = $("#peca_input_hidden").val();

            if (peca) {
                if ($("input[name='peca[]']").length == 5) {
                    var option = $("input[name='peca[]']")[0].id;
                    $("input[name='peca[]']")[0].remove();
                    $('.' + option).remove();
                }

                var option = '<option value="' + peca + '" class="' + peca_id + '">' + peca + '</option>';
                var hidden = '<input type="hidden" name="peca[]" id="' + peca_id + '" value="' + peca_id + '" />';
                $('#pecas').append(option);
                $('#pecas').append(hidden);
                $("#peca_input").val('');
            }
        });

        $('#rm_peca').click(function(){
            $("select[name=pecas] option:selected").each(function () {
                var hidden = $(this).attr("class");
                $(this).remove();
                $('#' + hidden).remove();
            });
        });
        <?php if ($login_fabrica != 24) { ?>
            $.ajax({
            url: "json_postos.php",
            dataType: "text",
            success: function(data) {
                autocompletePosto(data);
            }
        });
        <?php } ?>
        

        $('#add_posto').click(function(){
            var posto = removeAcento($('#posto_input').val().toUpperCase());
            var posto_id = $("#posto_input_hidden").val();

            if (posto) {
                var option = '<option value="' + posto + '" class="' + posto_id + '">' + posto + '</option>'
                var hidden = '<input type="hidden" name="posto[]" id="' + posto_id + '" value="' + posto_id + '" />';
                $('#postos').append(option);
                $('#postos').append(hidden);
                $("#posto_input").val('');
            }
        });

        $('#rm_posto').click(function(){
            $("select[name=postos] option:selected").each(function () {
                var hidden = $(this).attr("class");
                $(this).remove();
                $('#' + hidden).remove();
            });
        });

    });

  function fnc_pesquisa_fornecedor (fornecedor) {

    if (fornecedor.value.length > 2) {
        Shadowbox.open({
            content:    "fornecedor_ns.php?fornecedor=" + fornecedor.value,
            player: "iframe",
            title:      "Pesquisa Fornecedor",
            width:  800,
            height: 500
        });
    }
    else{
        alert("Informe toda ou parte da informaÁ„o para realizar a pesquisa");
    }
  }

  function retorna_dados_fornecedor (fornecedor){
    gravaDados("fornecedor_input", fornecedor);
  }

  function fnc_pesquisa_revenda (revenda) {

    if (revenda.value.length > 2) {
        Shadowbox.open({
            content:    "revenda_lupa_new.php?parametro=razao_social&valor=" + revenda.value +"&extratifica=true",
            player: "iframe",
            title:      "Pesquisa Revenda",
            width:  800,
            height: 500
        });
    }
    else{
        alert("Informe toda ou parte da informaÁ„o para realizar a pesquisa");
    }
  }

function retorna_revenda (revenda){
    gravaDados("revenda_input", revenda.razao);
    gravaDados("revenda_hidden", revenda.cnpj);
}

  function gravaDados(name, valor){
    try{
        $("input[name="+name+"]").val(valor);
    } catch(err){
        return false;
    }
  }



</script>

<style type="text/css">
    .titulo_tabela{
        background-color:#596d9b;
        font: bold 14px "Arial";
        color:#FFFFFF;
        text-align:center;
    }
    .titulo_coluna{
        background-color:#596d9b;
        font: bold 11px "Arial";
        color:#FFFFFF;
        text-align:center;
    }
    .msg_erro{
        background-color:#FF0000;
        font: bold 14px "Arial";
        color:#FFFFFF;
        text-align:center;
    }
    .formulario{
        background-color:#D9E2EF;
        font:11px Arial;
        text-align:left;
    }
    button.download { margin-top : 15px; }
    table.form tr td{
        padding:10px 30px 0 0;
    }
    table.tabela tr td{
        font-family: verdana;
        font-size: 11px;
        border-collapse: collapse;
        border:1px solid #596d9b;
        padding: 0 10px;
    }
    .texto_avulso{
        font: 14px Arial; color: rgb(89, 109, 155);
        background-color: #d9e2ef;
        text-align: center;
        width:700px;
        margin: 10px auto;
        border-collapse: collapse;
        border:1px solid #596d9b;
    }
    div.formulario table.form{
        padding:10px 0 10px 60px;
        text-align:left;
    }
    .subtitulo{
        background-color: #7092BE;
        font:bold 14px Arial;
        color: #FFFFFF;
        text-align:center;
    }
    tr th a {color:white !important;}
    tr th a:hover {color:blue !important;}

    div.formulario form p{ margin:0; padding:0; }

    .autocomplete-suggestions { text-align: left; border: 1px solid #999; background: #FFF; cursor: default; overflow: auto; -webkit-box-shadow: 1px 4px 3px rgba(50, 50, 50, 0.64); -moz-box-shadow: 1px 4px 3px rgba(50, 50, 50, 0.64); box-shadow: 1px 4px 3px rgba(50, 50, 50, 0.64); }
    .autocomplete-suggestion { padding: 2px 5px; white-space: nowrap; overflow: hidden; }
    .autocomplete-selected { background: #F0F0F0; }

</style>

<div style="width:700px; margin:auto; padding: 0px 10px; font-weight: bold; margin-bottom: 20px;">
    ATEN«√O: <em>Este relatÛrio utiliza BI como base para exibiÁ„o dos resultados. As bases s„o atualizadas 2 vezes por dia, sendo uma pela madrugada e outra no inÌcio da tarde.</em>
    <?php if ($login_fabrica == 24): ?>
    <br />Obs: RelatÛrio IRC Global È atualizado 1 vez por dia na madrugada. </br><br />
    <?php endif ?>
</div>

<div class="formulario" style="width:700px; margin:auto;">

    <div id="msg"></div>
  <div class="titulo_tabela">Par‚metros de Pesquisa</div>
    <form name="frm_consulta" action="<?=$_SERVER['PHP_SELF'];?>" method="POST" name="frm">
        <table cellspacing="1" align="center" class="form">
            <tr>
                <td style="min-width:120px;">
                    <label for="ano">Ano</label>
                    <select name="ano_pesquisa">
                        <?php
                        date_default_timezone_set('America/Sao_Paulo');
                        $curr_year = date('Y')+3;
                        $curr_month = date('m');
                        $anos = range(2006, $curr_year);

                        if (empty($ano_pesquisa)) {
                            $ano_pesquisa = $curr_year;
                        }

                        foreach ($anos as $ano) {
                            echo '<option value="' , $ano , '"';
                            if ($ano == $ano_pesquisa) {
                                echo ' selected="SELECTED"';
                            }
                            echo '>' , $ano , '</option>';
                        }
                        ?>
                    </select>
                </td>
                <td style="min-width:120px;">
                    <label for="mes">MÍs</label>
                    <select name="mes_pesquisa">
                        <?php
                        $meses = array("01" => "Janeiro",
                                        "02" => "Fevereiro",
                                        "03" => "MarÁo",
                                        "04" => "Abril",
                                        "05" => "Maio",
                                        "06" => "Junho",
                                        "07" => "Julho",
                                        "08" => "Agosto",
                                        "09" => "Setembro",
                                        "10" => "Outubro",
                                        "11" => "Novembro",
                                        "12" => "Dezembro"
                                        );

                        if (empty($mes_pesquisa)) {
                            $mes_pesquisa = $curr_month;
                        }

                        foreach ($meses as $idx => $mes) {
                            echo '<option value="' , $idx , '"';
                            if ($idx == $mes_pesquisa) {
                                echo ' selected="SELECTED"';
                            }
                            echo '>' , $mes , '</option>';
                        }

                        ?>
                    </select>
                </td>
            </tr>

            <tr>
                <td>
                    <label for="familia">FamÌlia</label>
                    <select name="familia" id="familia" onChange="getPecasByFamilia(this.value, true); getProdutosByFamilia(this.value, true);">
                        <option value=""></option>
                        <?php
                        $qry_familias = pg_query($con, "SELECT familia, descricao FROM tbl_familia WHERE fabrica = $login_fabrica AND ativo = 't' ORDER BY descricao");
                        if (pg_num_rows($qry_familias) > 0) {
                            while ($fetch = pg_fetch_assoc($qry_familias)) {
                                echo '<option value="' , $fetch['familia'] , '"';
                                if ($familia == $fetch['familia']) {
                                    echo ' SELECTED="SELECTED"';
                                }
                                echo '>' , $fetch['descricao'] , '</option>';
                            }
                        }

            if(in_array($login_admin,array(4683,4884,6577,9497,10730)) OR $login_fabrica == 24){
              echo "<option value='irc_global' > IRC Global </option>";
            }

            ?>

            </select>
                </td>
            </tr>

            <?php if ( in_array($login_fabrica, array(50)) ): ?>
            <tr>
                <td>
                    <label for="regiao">Regi„o</label>
                    <input type="hidden" id="regiao_pesquisada" value="<?php echo $regiao ?>" />
                    <select name="regiao" id="regiao">
                        <option value=""></option>
                        <?php
                        $qry_regiao = pg_query($con, "SELECT regiao, estados_regiao FROM tbl_regiao WHERE fabrica = $login_fabrica AND ativo ORDER BY descricao");
                        if (pg_num_rows($qry_regiao) > 0) {
                            while ($fetch = pg_fetch_assoc($qry_regiao)) {
                                echo '<option value="' , $fetch['regiao'] , '"';
                                if ($regiao == $fetch['regiao']) {
                                    echo ' SELECTED="SELECTED"';
                                }
                                echo '>' , $fetch['estados_regiao'] , '</option>';
                            }
                        }
                        ?>
                    </select>
                </td>
            </tr>
            <?php endif; ?>
            
            <tr>
                <td>
                    <label for="meses">Qtde Meses</label>
                    <input type="text" name="meses" id="meses" value="<?php echo $qtde_meses;?>" class="frm" style="width: 40px;" maxlength="2">
                </td>
                <td>
                    <label for="index_irc">Õndice</label>
                    <input type="text" name="index_irc" id="index_irc" value="<?php echo $irc;?>" class="frm" style="width: 40px;">
                </td>
            </tr>
            <?php if($login_fabrica == 24){ ?>
                <tr>
                    <td> <input type="radio" name="matriz_filial" value="02" <?php if($_POST['matriz_filial'] == 02 OR $_POST['matriz_filial'] == ''){ echo "checked"; }?>> <label for="meses">Matriz - 02</label></td>
                    <td><input type="radio" name="matriz_filial" value="04" <?php if($_POST['matriz_filial'] == 04){ echo "checked"; }?> > <label for="meses">Filial - 04</label></td>
                </tr>
            <?php } ?>
            <tr>
                <td colspan="2">
                    <?php
                        $checked = ($sem_peca) ? "checked" : "";
                    ?>
                    <input type="checkbox" name="sem_peca" id="sem_peca" value="20934" class="frm" <?=$checked?> >
                    <label for="sem_peca">M·quina n„o apresentou problema</label>
                </td>
            </tr>

            <?php if ($login_fabrica == 24): ?>
            <tr>
                <td colspan="2">
                    <?php
                    $checked_desconsidera_conversor = '';

                    if (!empty($_POST["desconsidera_conversor"])) {
                        $checked_desconsidera_conversor = ' checked="checked" ';
                    }
                    ?>
                    <input type="checkbox" name="desconsidera_conversor" id="desconsidera_conversor" <?php echo $checked_desconsidera_conversor ?>>
                    <label for="desconsidera_conversor">Desconsiderar OS de convers„o de g·s</label>
                </td>
            </tr>
            <?php endif ?>

            <?php if ( in_array($login_fabrica, array(24,50)) ): ?>
                 <tr>
                    <td colspan="2">
                        <label for="peca_input">PeÁa</label>
                        <input type="text" name="peca_input" id="peca_input" size="41" class="frm" />
                        <input type="hidden" name="peca_input_hidden" id="peca_input_hidden" size="41" class="frm" />
                        <input type="button" id="add_peca" value="Adicionar" />
                    </td>
                </tr>

                <tr>
                    <td colspan="2">
                        <select name="pecas" id="pecas" multiple style="width: 345px;">
                        <?php
                        if (!empty($pecas)) {
                            $condPecas = implode(', ', $pecas);
                            $sqlPecas = "SELECT peca, referencia, descricao FROM tbl_peca where peca IN ($condPecas)";
                            $qryPecas = pg_query($con, $sqlPecas);

                            if (pg_num_rows($qryPecas) > 0) {
                                while ($fetch = pg_fetch_assoc($qryPecas)) {
                                    echo '<option value="' , $fetch['peca'] , '" class="' , $fetch['peca'] , '">' , $fetch['referencia'] , ' - ', $fetch['descricao'] , '</option>';
                                }
                            }

                            foreach ($pecas as $pe) {
                                echo '<input type="hidden" name="peca[]" id="' , $pe , '" value="' , $pe , '" >';
                            }
                        }
                        ?>
                        </select>
                        <input type="button" id="rm_peca" value="Remover" />
                    </td>
                </tr>
                <?php 
                if ($login_fabrica == 50) { ?>
                    <tr>
                        <td colspan="2">
                            <label for="produto_input">PerÌodo</label>
                            <select name="periodo" id="periodo" style="width: 150px;">
                                <option value=""></option>
                                <?php

                                for ($i=1; $i <= 15 ; $i++) {
                                    $n = ($i == 1) ? "$i mÍs" : "$i meses";
                                    $sel = ($i == $periodo) ? "selected" : "";
                                    echo "<option value='{$i}' {$sel}>{$n}</option>";
                                }

                                ?>
                            </select>
                        </td>
                    </tr>
                <?php
                }
                ?>
                
            <?php else: ?>
                <tr>
                    <td colspan="2">
                        <label for="peca01">PeÁa 1</label>
                        <input type="text" name="peca01" id="peca01" value="<?php echo $peca01;?>" class="frm" style="width: 380px;">
                    </td>
                </tr>

                <tr>
                    <td colspan="2">
                        <label for="peca02">PeÁa 2</label>
                        <input type="text" name="peca02" id="peca02" value="<?php echo $peca02;?>" class="frm" style="width: 380px;">
                    </td>
                </tr>
            <?php endif; ?>

            <tr>
                <td colspan="2">
                    <label for="produto_input">Produto</label>
                    <input type="text" name="produto_input" id="produto_input" size="41" class="frm" />
                    <input type="hidden" name="produto_input_hidden" id="produto_input_hidden" size="41" class="frm" />
                    <input type="button" id="add_produto" value="Adicionar" />
                </td>
            </tr>

            <tr>
                <td colspan="2">
                    <select name="produtos" id="produtos" multiple style="width: 345px;">
                        <?php
                        if (!empty($produtos)) {
                            $condProds = implode(', ', $produtos);
                            $sqlProds = "SELECT produto, referencia, descricao FROM tbl_produto where produto IN ($condProds)";
                            $qryProds = pg_query($con, $sqlProds);

                            if (pg_num_rows($qryProds) > 0) {
                                while ($fetch = pg_fetch_assoc($qryProds)) {
                                    echo '<option value="' , $fetch['produto'] , '" class="' , $fetch['produto'] , '">' , $fetch['referencia'] , ' - ', $fetch['descricao'] , '</option>';
                                }
                            }

                            foreach ($produtos as $prod) {
                                echo '<input type="hidden" name="produto[]" id="' , $prod , '" value="' , $prod , '" >';
                            }
                        }
                        ?>
                    </select>
                    <input type="button" id="rm_produto" value="Remover" />
                </td>
            </tr>

            <?php if ($login_fabrica != 120) { ?>
            <tr>
                <td colspan="2">
                    <label for="fornecedor_input">Fornecedor</label>
                    <input type="text" name="fornecedor_input" id="fornecedor_input" size="38" class="frm" />&nbsp;
                    <img src="imagens/lupa.png" border="0" align="absmiddle" onclick="javascript: fnc_pesquisa_fornecedor(document.frm_consulta.fornecedor_input)" style="cursor:pointer;">
                    <input type="button" id="add_fornecedor" value="Adicionar" />
                </td>
            </tr>

            <tr>
                <td colspan="2">
                    <select name="fornecedores" id="fornecedores" multiple style="width: 345px;">
                        <?php
                            if(!empty($fornecedores)) {
                                foreach ($fornecedores as $fornecedor) {
                                    echo '<option value="' , $fornecedor , '">' , $fornecedor , '</option>';
                                }


                                foreach ($fornecedores as $fornecedor) {
                                    echo '<input type="hidden" name="fornecedor[]" id="' , $fornecedor , '" value="' , $fornecedor , '" >';
                                }
                            }
                        ?>
                    </select>
                    <input type="button" id="rm_fornecedor" value="Remover" />
                </td>
            </tr>

<?php
                        }
                        if( in_array($login_fabrica, array(50)) ){

?>
            <tr>
                <td colspan="2">
                    <label for="revenda_input">Revenda</label>
                    <input type="text" name="revenda_input" id="revenda_input" size="38" class="frm" />&nbsp;
                    <input type="hidden" name="revenda_hidden" id="revenda_hidden" />
                    <img src="imagens/lupa.png" border="0" align="absmiddle" onclick="javascript: fnc_pesquisa_revenda(document.frm_consulta.revenda_input)" style="cursor:pointer;">
                    <input type="button" id="add_revenda" value="Adicionar" />
                </td>
            </tr>
            <tr>
                <td colspan="2">
                    <select name="revendas" id="revendas" multiple style="width: 345px;">
                        <?php
                            if(!empty($revendas)) {
                                $condRevs = implode("', '", $revendas);
                                $sqlRevs = "SELECT cnpj, nome FROM tbl_revenda where cnpj IN ('$condRevs')";
                                $qryRevs = pg_query($con, $sqlRevs);

                                if (pg_num_rows($qryRevs) > 0) {
                                    while ($fetch = pg_fetch_assoc($qryRevs)) {
                                        echo '<option value="' , $fetch['cnpj'] , '">' , $fetch['cnpj'] ,' - ' , $fetch['nome'] , '</option>';
                                    }
                                }


                                foreach ($revendas as $revenda) {
                                    echo '<input type="hidden" name="revenda[]" id="' , $revenda, '" value="' , $revenda , '" >';
                                }
                            }
                        ?>
                    </select>
                    <input type="button" id="rm_revenda" value="Remover" />
                </td>
            </tr>
<?
                        }
?>

            <tr>
                <td colspan="2">
                    <label for="dia_01">Dia</label>
                    <input type="text" name="dia_01" id="dia_01" value="<?php echo $dia_01;?>" class="frm" style="width: 40px;" maxlength="2">

                    <label for="mes_01" style="padding-left: 10px;">MÍs</label>
                    <input type="text" name="mes_01" id="mes_01" value="<?php echo $mes_01;?>" class="frm" style="width: 40px;" maxlength="2">

                    <label for="ano_01" style="padding-left: 10px;">Ano</label>
                    <input type="text" name="ano_01" id="ano_01" value="<?php echo $ano_01;?>" class="frm" style="width: 60px;" maxlength="4">
                </td>
            </tr>

            <tr>
                <td colspan="2">
                    <label for="dia_02">Dia</label>
                    <input type="text" name="dia_02" id="dia_02" value="<?php echo $dia_02;?>" class="frm" style="width: 40px;" maxlength="2">

                    <label for="mes_02" style="padding-left: 10px;">MÍs</label>
                    <input type="text" name="mes_02" id="mes_02" value="<?php echo $mes_02;?>" class="frm" style="width: 40px;" maxlength="2">

                    <label for="ano_02" style="padding-left: 10px;">Ano</label>
                    <input type="text" name="ano_02" id="ano_02" value="<?php echo $ano_02;?>" class="frm" style="width: 60px;" maxlength="4">
                </td>
            </tr>

            <tr>
                <td colspan="2">
                    <label for="dia_03">Dia</label>
                    <input type="text" name="dia_03" id="dia_03" value="<?php echo $dia_03;?>" class="frm" style="width: 40px;" maxlength="2">

                    <label for="mes_03" style="padding-left: 10px;">MÍs</label>
                    <input type="text" name="mes_03" id="mes_03" value="<?php echo $mes_03;?>" class="frm" style="width: 40px;" maxlength="2">

                    <label for="ano_03" style="padding-left: 10px;">Ano</label>
                    <input type="text" name="ano_03" id="ano_03" value="<?php echo $ano_03;?>" class="frm" style="width: 60px;" maxlength="4">
                </td>
            </tr>

            <?php if ( in_array($login_fabrica, array(50)) ): ?>

            <tr>
                <td colspan="2">
                    <label for="posto">Posto</label>
                    <input type="text" name="posto_input" id="posto_input" size="41" class="frm" />
                    <input type="hidden" name="posto_input_hidden" id="posto_input_hidden" size="41" class="frm" />
                     <input type="button" id="add_posto" value="Adicionar" />
                </td>
            </tr>

            <tr>
                <td colspan="2">
                    <select name="postos" id="postos" multiple style="width: 345px;">
                        <?php
                        if (!empty($postos)) {
                            $condPos = implode(', ', $postos);
                            $sqlPos = "SELECT tbl_posto.posto, codigo_posto, nome FROM tbl_posto join tbl_posto_fabrica on tbl_posto.posto = tbl_posto_fabrica.posto and tbl_posto_fabrica.fabrica = $login_fabrica where tbl_posto.posto IN ($condPos)";
                            $qryPos = pg_query($con, $sqlPos);

                            if (pg_num_rows($qryPos) > 0) {
                                while ($fetch = pg_fetch_assoc($qryPos)) {
                                    echo '<option value="' , $fetch['posto'] , '" class="' , $fetch['posto'] , '">' , $fetch['codigo_posto'] , ' - ', $fetch['nome'] , '</option>';
                                }
                            }

                            foreach ($postos as $pos) {
                                echo '<input type="hidden" name="posto[]" id="' , $pos , '" value="' , $pos , '" >';
                            }
                        }
                        ?>

                    </select>
                    <input type="button" id="rm_posto" value="Remover" />
                </td>
            </tr>

            <?php endif; ?>

            <tr>
                <td colspan="2" style="padding-top:15px;" align="center">
                    <input type="submit" name="btn_acao" value="Consultar" />
                </td>
            </tr>

        </table>
    </form>
</div><br/>

<?php
if (!empty($resultado)) {
    echo $resultado;
}
?>

<script type="text/javascript">
    $("#meses").numeric();
    $("#irc").numeric();
    $("input[name^=dia_]").numeric();
    $("input[name^=mes_]").numeric();
    $("input[name^=ano_]").numeric();
</script>

<?php echo '<div id="erro" class="msg_erro" style="display:none;">'.$msg_erro.'</div>'; ?>

<script type="text/javascript">
    <?php if ( !empty($msg_erro) ){ ?>
            $("#erro").appendTo("#msg").fadeIn("slow");
    <?php } ?>
</script>

<?php include 'rodape.php'; ?>
