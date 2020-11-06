<?php

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';
include 'funcoes.php';

if ($_REQUEST['referencia_peca'])
    $referencia_peca = $_REQUEST['referencia_peca'];

if ($_REQUEST['descricao_peca'])
    $descricao_peca = $_REQUEST['descricao_peca'];

ob_start();

include "javascript_pesquisas.php";
?>
<script type="text/javascript">
    var cor = 0;
    var referencias = new Array();

    function qStr(obj) {
        var qArr = [];
        for (i in obj)
            qArr.push(i+"="+obj[i]);
        return (qArr.length == 0) ? '' : '?' + encodeURI(qArr.join('&'));
    }

    function makitaValidaRegras(frm) {
        var peca_referencia = $("#referencia_peca").val();
        var condicao = 1175;
        var linha = 1;
        var posto = <?php echo $login_posto;?>;

        if ((peca_referencia.length) == 0) {
            alert('Informe uma referência!');
            return false;

        } else {

            $('#btn_acao').attr({'value': 'Pesquisando...', "disabled": true});

            params = {
                cache_bypass: '$cache_bypass',
                'btn_acao':   'acao_tabela_preco',
                'condicao':   condicao,
                'linha':      linha,
                'posto':      posto,
                'produto_referencia': peca_referencia
            };

            $.ajax({
                type: 'GET',
                url: 'makita_valida_regras.php',
                data: params,
                success: function(resposta) {
                    if (resposta == 'referencia_invalida') {
                        //dados = "<tr style='background-color: #F00; color: #FFF'><td colspan='3'>Referência: "+produto_referencia+ " Inválida</td></tr>";
                        alert('Peça não Encontrada');
                        $('#btn_acao').attr('value', ' Pesquisar ');
                        $('#btn_acao').attr("disabled", false);

                    } else {

                        resposta = resposta.substring (resposta.indexOf('<preco>')+7,resposta.length);
                        resposta = resposta.substring (0,resposta.indexOf('</preco>'));
                        resposta = resposta.split("|");

                        preco        = resposta[0];
                        linha_form   = resposta[1];
                        descricao    = resposta[2];
                        mudou        = resposta[3];
                        de           = resposta[4];
                        referencia   = resposta[5];
                        ipi          = resposta[6];
                        valor_total  = resposta[7];
                        valor_tabela = resposta[8];
                        fora_linha   = resposta[9];
                        classFiscal  = resposta[10];
                        CST          = resposta[11];
			disponibilidade = resposta[14];
                        // entrega      = resposta[12];

                        var cor      = ($("#conteudo tr").length % 2 == 0) ? "#F1F4FA" : "#F7F5F0";
                        var depara   = $("#ref_de").length !== 0;
                        var refAttr  = depara ? ' style="text-decoration:line-through;color:darkred" title="Mudou para..."' : '';
                        var paraAttr = depara ? ' style="color:darkgreen"' : '';

                        produto_referencia = depara ? $("#ref_de").text()  : referencia;
                        produto_descricao  = depara ? $("#desc_de").text() : descricao;

                        var dados = "<tr style='background: "+cor+"'"
                            + ( depara ? " title='A peça ref.: "+produto_referencia+" foi substituída pela ref.: "+ referencia+"'" :'' ) + ">"
                            + "<td"  + refAttr  + ">" + produto_referencia + "</td>"
                            + "<td>" + produto_descricao  + "</td>"
                            + (depara ? "<td"  + paraAttr + ">" + referencia +  " - " + descricao + "</td>" : '<td>&nbsp;</td>')
                            + "<td>" + disponibilidade + "</td>"
                            + "<td align='center'>"  + classFiscal  + "</td>"
                            + "<td align='center'>"  + CST          + "</td>"
                            + "<td align='right'>R$ "+ valor_tabela + "</td>"
                            + "<td align='right'>R$ "+ preco        + "</td>" 
                            + "<td align='right'>"   + ipi          + "%</td>" 
                            + "<td align='right'>R$ "+ valor_total  + "</td>"
                            + "</tr>";
                            // + "<td>" + entrega + "</td>"
                        $('#btn_acao').attr('value', ' Pesquisar ');
                        $('#btn_acao').attr("disabled", false);

                        if (referencias.indexOf(peca_referencia) != -1) {
                            alert('Referência: '+peca_referencia + ' já foi pesquisada!');
                        } else {
                            referencias.push(peca_referencia);
                            $('#conteudo').append(dados);
                            $('.conteudo_resposta_ajax').css('display','block');
                        }
                    }
                }
            });
        }
    }

    function fnc_pesquisa_peca_2(referencia, descricao) {
        if (referencia.length > 2 || descricao.length > 2) {
            Shadowbox.open({
                content: "peca_pesquisa_nv.php?referencia=" + referencia + "&descricao=" + descricao,
                player:  "iframe",
                title:   "Pesquisa Peça",
                width:   800,
                height:  500
            });
        }
        else{
            alert("Informe toda ou parte da informação para realizar a pesquisa");
        }
    }

    function retorna_dados_peca (peca, referencia, descricao, ipi, origem, para, peca_para, para_descricao, posicao) {
        var msg_de_para = '';
        if (peca_para != "") {
            msg_de_para  = "<dl><dt>A peça</dt><dd><span id='ref_de'>" + referencia + "</span> - <em><span id='desc_de'>" + descricao + "</span></em></dd>";
            msg_de_para += "<dt>foi substituìda pela peça</dt><dd>" + para + " - <em>" + para_descricao + "</em></dd></dl>";
            referencia = para;
            descricao  = para_descricao;
        }

        $("#referencia_peca").val(referencia);
        $("#descricao_peca").val(descricao);
        if (msg_de_para.length > 0) {
             $("#msgDePara").show().html(msg_de_para);
        } else {
             $("#msgDePara").hide().html(msg_de_para);
        }
    }

    $().ready(function() {
        Shadowbox.init();
        $("span[rel=lupa]").click(function () {
            var ref  = $("#referencia_peca").val(),
                desc = $("#descricao_peca").val();
            fnc_pesquisa_peca_2 (ref, desc);
        });

        $("#btn_acao").click(function() {
            makitaValidaRegras();
        });
    });

</script>

<style type="text/css">
.aviso{
    font: 14px Arial;
    color: #FFF;
    background-color: #F00;
    text-align: center;
    width:700px;
    margin: 10px auto;
    border:1px solid #FFF;
    padding: 2px 0;
    font-weight: bold;
}

.texto_avulso{
    font: 14px Arial; color: rgb(89, 109, 155);
    background-color: #d9e2ef;
    text-align: center;
    width:700px;
    margin: 10px auto;
    border:1px solid #596d9b;
    padding: 2px 0;
}

.titulo_coluna{
    background-color:#596d9b;
    font: bold 11px "Arial" !important;
    color:#FFFFFF;
    text-align:center;
}

.subtitulo{
    background-color: #7092BE;
    font:bold 11px Arial;
    color: #FFFFFF;
}

table.tabela{
    padding: 0;
    margin: 0 auto;
    border:0;
    width: 700px;
    background-color: #CCC;
}

table.tabela tr td{
    font-family: verdana;
    font-size: 11px;
    padding: 2px;
    border: 1px solid #FFF;
}
.titulo_tabela{
    background-color:#596d9b;
    font: bold 14px "Arial" !important;
    color:#FFFFFF;
    text-align:center;
}
.msg_erro{
    background-color:#FF0000;
    font: bold 16px "Arial";
    color:#FFFFFF;
    text-align:center;
}
.formulario{
    background-color:#D9E2EF;
    font:11px Arial;
    text-align:left;
    width: 700px;
    margin: 0 auto;
}

.btn_submit{
   text-align: center;
   padding: 15px 0;
}

.espaco{
    padding-left: 140px;
}
</style>
<?php
$title       = "TABELA DE PREÇOS";
$layout_menu = 'preco';
$plugins     = array("shadowbox", "autocomplete");

$headerHTML .= ob_get_clean();
include "cabecalho_new.php";

$formPreco = [
    'referencia_peca' => [
        'id'    => 'referencia_peca',
        'span'  => 3,
        'label' => traduz('referencia'),
        "type"  => "input/text",
        "width" => 6,
        "lupa"  => array(
            "name"      => "lupa",
            "tipo"      => "peca",
            "parametro" => "referencia",
            "extra"     => array(
            "ativo"     => true
            )
        )
    ],
    'descricao_peca' => [
        'id'    => 'descricao_peca',
        'span'  => 5,
        'label' => traduz('descricao'),
        "type"  => "input/text",
        "width" => 10,
        "lupa"  => array(
            "name"      => "lupa",
            "tipo"      => "peca",
            "parametro" => "descricao",
            "extra"     => array(
                "ativo" => true
            )
        )
    ],
];

?>
<form id="tabelapreco" action="javascript:makitaValidaRegras(this)" name="frm_tabela">
    <div class="container">
        <div class="tc_formulario">
            <div class="titulo_tabela">Parâmetros de Pesquisa</div>
            <div id="msgDePara" style="margin:1ex 20%; color:darkred; display:none"></div>
            <?=montaForm($formPreco)?>
            <div class="tac">
                <button id="btn_acao" class="btn btn-default" type="button" name="btn_acao"><?=traduz('pesquisar')?></button>
            </div>
            <div>&nbsp;</div>
        </div>
    </div>
</form>

<table width="850px" border="0" cellpadding="2" cellspacing="1" align="center" class='formulario conteudo_resposta_ajax' style='display: none'>
	<thead>
		<tr class="titulo_coluna">
			<td width='150'>Referência</td>
			<td width='300'>Descrição</td>
			<td width='450'>Alternativo</td>
            <td width="150">Disp. Estoque</td>
            <!-- <td width="150">Data Entrega</td> -->
			<td width='150'>Class. Fiscal</td>
			<td width='150' title="0 - Nacional ; 1 - Estrangeira">Cód. Origem</td>
			<td width='150'>Tabela</td>
			<td width='150'>Valor</td>
			<td width='150'>IPI</td>
			<td width='150'>Valor Total</td>
		</tr>
	</thead>
	<tbody id='conteudo'></tbody>
</table>

<? include "rodape.php";

