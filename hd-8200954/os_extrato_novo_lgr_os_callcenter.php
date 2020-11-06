<?php

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';
include 'admin/funcoes.php';

if (!in_array($login_fabrica, array(1, 20))) include "autentica_usuario_financeiro.php";

if ($login_fabrica == 3) {

    if ($login_e_distribuidor == 't') {
        header ("Location: new_extrato_distribuidor.php");
        exit;
    } else {
        header ("Location: extrato_posto_novo.php");
        exit;
    }

}

if ($login_fabrica == 1) {
    header ("Location: os_extrato_blackedecker.php");
    exit;
}

$meses = array(1 => traduz("janeiro",$con,$cook_idioma), traduz("fevereiro",$con,$cook_idioma), traduz("marco",$con,$cook_idioma), traduz("abril",$con,$cook_idioma), traduz("maio",$con,$cook_idioma), traduz("junho",$con,$cook_idioma), traduz("julho",$con,$cook_idioma), traduz("agosto",$con,$cook_idioma), traduz("setembro",$con,$cook_idioma), traduz("outubro",$con,$cook_idioma), traduz("novembro",$con,$cook_idioma), traduz("dezembro",$con,$cook_idioma));
$ajax = $_GET['ajax'];

if ($ajax == "true") {

    $extrato = $_GET['extrato'];
    $status  = $_GET['status'];
    $nf      = $_GET['nf'];

    if ($login_fabrica == 11){
        $contato_fabricante = "Taiz TEL:071 3379-1997";
    }

    if (in_array($login_fabrica, array(25, 51))) {
        $contato_fabricante = "Sr. Ronaldo TEL: 014 3413-6588";
    }

    if (in_array($login_fabrica, array(91,50,114,7,43,125)) || empty($contato_fabricante)) {
        $contato_fabricante = "Fabricante";
    }
    if (strlen($extrato) > 0) {

        if ($status == "anterior") {

            fecho("prezada.autorizada", $con, $cook_idoma);
        echo "<br/><br/>";
        if($login_fabrica == 50){
        echo "A nota fiscal de RETORNO DE REMESSA das peÁas em garantia do extrato $extrato n„o foram preenchidas. Por favor, acesse o link de peÁas retorn·veis para o preenchimento.";
        }else{
            fecho ("a.nota.fiscal.de.devolucao.das.pecas.em.garantia.do.extrato.%.nao.foram.preenchidas.por.favor.acesse.o.link.de.pecas.retornaveis.para.o.preenchimento",$con,$cook_idioma,$extrato);
        }
            echo ".<br/><br/>";

            if($login_fabrica <> 91) {
                echo "<a href='extrato_posto_devolucao_lgr_novo_lgr.php?extrato=$extrato' title='";
                fecho("clique.aqui.para.preencher.a.nota.fiscal.de.devolucao..apos.a.devolucao.da.nf,.podera.ser.visualizado.a.mao.de.obra",$con,$cook_idioma);
                echo "'>";
                fecho("clique.aqui.para.preencher.a.nf",$con,$cook_idioma);
                echo ".</a>";
            }
        }

        if ($status == "parcial") {

            if ($login_fabrica == 11) {

                fecho("prezada.autorizada", $con, $cook_idoma);
                echo "<br/><br/>";
                fecho("a.nf.de.devolucao.%.foram.recebidas.parcialmente.pela.fabrica", $con, $cook_idioma,$nf);
                fecho ("favor.entrar.em.contato.urgente.com.a.taiz.tel.071.3379.1997.para.sua.regularizacao",$con,$cook_idioma);

            }

        }

        $nf = str_replace(',','.',$nf);
        if ($status == "confirmada") {
            fecho("prezada.autorizada", $con, $cook_idioma);
            echo "<br/><br/>";
            fecho ("a.nf.de.devolucao.%.nao.foram.recebida.pela.fabrica", $con, $cook_idioma,$nf);
            echo "<br>";
            fecho ("favor.entrar.em.contato.urgente.com.o.%.para.sua.regularizacao",$con,$cook_idioma,$contato_fabricante);
        }

    }

    exit;

}

if (isset($_POST['gravanota']) and isset($_POST['extrato'])) {

    $extrato      = $_POST['extrato'];
    $nota_fiscal  = $_POST['nota_fiscal'];
    $data_emissao = $_POST['data_emissao'];
    $valor_nf     = $_POST['valor_nf'];
    $valor_nf     = str_replace(",",".",$valor_nf);
    $aux_ano      = substr($data_emissao,6,4);
    $aux_mes      = substr($data_emissao,3,2);
    $aux_dia      = substr($data_emissao,0,2);
    $data_emissao = "'". $aux_ano."-".$aux_mes."-".$aux_dia."'";

    $sql = " UPDATE tbl_extrato_extra set
                    nota_fiscal_mao_de_obra = $nota_fiscal,
                    emissao_mao_de_obra = $data_emissao,
                    valor_total_extrato = $valor_nf
                WHERE extrato = $extrato";

    $res = pg_query($con,$sql);
    $msg_erro = pg_last_error($con);

    echo (strlen($msg_erro) > 0) ?'erro|$msg_erro':'ok|ok';

    exit;

}

if ($login_fabrica == 6) {
    //hd 3477 - Para obrigar o PA a fechar a OS ate 90 dias, obrigamos os postos a pelo menos informar o motivo ou fechar a OS, inicialmente para somente estes postos a partir de 01/09, depois liberaremos para tds os postos. Takashi 29/08
    $postos_liberado = array('033939','032244','013851','032857','033523','012269','006844','005576','031548','018392','033602','033203','032830','011998','031376','031194','032605','033550','012825','012665','006553','017153','033524','020685','030466','015354','034472','033948','032263','032385','033953','034601','031676','033644','009455','033155','011175','019876','TESTE');

    if (in_array($login_codigo_posto, $postos_liberado)) {

        $wsql = "SELECT CURRENT_DATE - INTERVAL '60 days' as inicio, CURRENT_DATE - INTERVAL '30 days' as fim";
        $wres = pg_query($con,$wsql);

        $data_inicio = pg_fetch_result($wres, 0, 'inicio');
        $data_fim    = pg_fetch_result($wres, 0, 'fim');

        $wwsql = "SELECT tbl_os.os ,
                        tbl_os.sua_os ,
                        to_char(tbl_os.data_abertura,'DD/MM/YYYY') as data_abertura ,
                        to_char(tbl_os.data_digitacao,'DD/MM/YYYY') as data_digitacao ,
                        to_char(tbl_os.data_fechamento,'DD/MM/YYYY') as data_fechamento ,
                        current_date - tbl_os.data_abertura as tempo_em_aberto ,
                        tbl_produto.referencia as produto_referencia ,
                        tbl_produto.descricao as produto_descricao ,
                        tbl_os.consumidor_nome ,
                        tbl_os.motivo_atraso,
                        tbl_os_extra.motivo_atraso2
                FROM tbl_os
                JOIN tbl_produto on tbl_os.produto = tbl_produto.produto
                join tbl_os_extra on tbl_os.os = tbl_os_extra.os
                WHERE tbl_os.fabrica = $login_fabrica
                and tbl_os.posto = $login_posto
                AND tbl_os.data_fechamento IS NULL
                AND tbl_os.excluida IS NOT TRUE
                AND ((tbl_os.data_abertura between '$data_inicio'  and '$data_fim' and tbl_os.motivo_atraso is null)
                or (tbl_os.data_abertura <'$data_inicio' and tbl_os_extra.motivo_atraso2 is null))
                ORDER BY tbl_os.data_abertura ";

        $wwres = pg_query($con,$wwsql);

        if (pg_num_rows($wwres) > 0) {
            include "os_aberta.php";
        }

    }

}

$msg_erro = "";
$layout_menu = "os";
$title = 'Extrato - OSs abertas pelo Call-Center';

include "cabecalho.php";
include "javascript_calendario_new.php"; //adicionado por Fabio 27-09-2007 ?>

<script type="text/javascript" src="js/thickbox.js"></script>
<link rel="stylesheet" href="js/thickbox.css" type="text/css" media="screen" />

<script src="plugins/shadowbox/shadowbox.js" type="text/javascript"></script>
<link rel="stylesheet" type="text/css" href="plugins/shadowbox/shadowbox.css" media="all">

<script language="JavaScript">

Shadowbox.init();

$(function()
{
    $("input[rel='emissao_mao_de_obra']").maskedinput("99/99/9999");

    if($('#preencha')){
        var campo = $('#preencha').attr('rel');
        var extrato = $('#extrato_sem_nota').val();
        campo -= 1;
        for(i = campo; i >=0; i--){

            $('#bloqueado_'+i).attr('href','os_extrato_novo_lgr.php?ajax=true&extrato='+extrato+'&status=anterior&height=240&width=320');

        }
    }
});

window.onload = function(){
    $("input[rel='nota_fiscal_mao_de_obra']").keypress(function(e) {
        var c = String.fromCharCode(e.which);
        var allowed = '1234567890';
        if (e.which != 8 && allowed.indexOf(c) < 0) return false;
    });

    $("input[rel='valor_total_extrato']").keypress(function(e) {
        var c = String.fromCharCode(e.which);
        var allowed = '1234567890,.';
        if (e.which != 8 && allowed.indexOf(c) < 0) return false;
    });
}

function gravaNota(nota,data,valor,mostrar,extrato){
    var nota = document.getElementById(nota).value;
    var data = document.getElementById(data).value;
    var valor = document.getElementById(valor).value;
    var mostrar = document.getElementById(mostrar);

    if(nota.length == 0 || data.length==0 || valor.length==0 ) {
        alert('Preenche todos os dados para gravar')
    }else{
        if(confirm('VocÍ tem certeza das informaÁıes digitadas? Elas n„o poder„o ser alteradas, somente a F·brica poder· alterar?') == true){
            $.post(
                '<?=$PHP_SELF?>',
                {
                    nota_fiscal: nota,
                    data_emissao: data,
                    valor_nf: valor,
                    extrato:extrato,
                    gravanota: 'sim'
                },
                function(resposta){
                    var resultado= resposta.split("|");
                    if(resultado[0] =='ok'){
                        $(mostrar).html('Gravado com sucesso');
                    }else{
                        alert(resultado[1]);
                    }
                }
            )
        }
    }
}

function date_onkeydown() {
  if (window.event.srcElement.readOnly) return;
  var key_code = window.event.keyCode;
  var oElement = window.event.srcElement;
  if (window.event.shiftKey && String.fromCharCode(key_code) == "T") {
        var d = new Date();
        oElement.value = String(d.getMonth() + 1).padL(2, "0") + "/" +
                         String(d.getDate()).padL(2, "0") + "/" +
                         d.getFullYear();
        window.event.returnValue = 0;
    }
    if (!window.event.shiftKey && !window.event.ctrlKey && !window.event.altKey) {
        if ((key_code > 47 && key_code < 58) ||
          (key_code > 95 && key_code < 106)) {
            if (key_code > 95) key_code -= (95-47);
            oElement.value =
                oElement.value.replace(/[dma]/, String.fromCharCode(key_code));
        }
        if (key_code == 8) {
            if (!oElement.value.match(/^[dma0-9]{2}\/[dma0-9]{2}\/[dma0-9]{4}$/))
                oElement.value = "dd/mm/aaaa";
            oElement.value = oElement.value.replace(/([dma\/]*)[0-9]([dma\/]*)$/,
                function ($0, $1, $2) {
                    var idx = oElement.value.search(/([dma\/]*)[0-9]([dma\/]*)$/);
                    if (idx >= 5) {
                        return $1 + "a" + $2;
                    } else if (idx >= 2) {
                        return $1 + "m" + $2;
                    } else {
                        return $1 + "d" + $2;
                    }
                } );
            window.event.returnValue = 0;
        }
    }
    if (key_code != 9) {
        event.returnValue = false;
    }
}

<? if ($login_fabrica == 151) { ?>

function insere_nf_servico(nf_servico, extrato){
    $(".nf-servico-"+extrato).html("<a href='nota_servico_extrato.php?extrato="+extrato+"' rel='shadowbox; width= 400; height= 250;'>"+nf_servico+"</a>");
    Shadowbox.setup();
}

<? } ?>

</script>

<style type="text/css">

.menu_top {
    text-align: center;
    font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
    font-size: 10px;
    font-weight: bold;
    border: 1px solid;
    color:#ffffff;
    background-color: #596D9B
}

.table_line {
    text-align: left;
    font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
    font-size: 10px;
    font-weight: normal;
    border: 0px solid;
    background-color: #D9E2EF
}

.table_line2 {
    text-align: left;
    font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
    font-size: 10px;
    font-weight: normal;
}
.table_line3 {
    text-align: left;
    font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
    font-size: 12px;
    font-weight: normal;
    border: 0px solid;
    background-color: #FE918D
}
.Mensagem{
    font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
    font-size: 12px;
    color:#7192C4;
    font-weight: bold;
}
.Tabela{
    border:1px solid #596D9B;
    background-color:#596D9B;
    }

.menu_top4 {
    text-align: center;
    font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
    font-size: x-small;
    font-weight: bold;
    border: 1px solid;
    color:#ffffff;
    background-color: #CC3333;
}

</style>

<?

if($login_fabrica<>20){ //hd 2565 takashi 30-05-07
    if(strlen($msg)>0){
        echo "<br><table class='Tabela' width='700' cellspacing='0'  cellpadding='0' align='center'>";
        echo "<tr >";
        echo "<td bgcolor='FFFFFF' width='60' align='top'><img src='imagens/cadeado1.jpg' align='absmiddle'></td><td  class='Mensagem' bgcolor='FFFFFF' align='left'>$msg<br>";
        fecho ("para.restringir.o.acesso.novamente,.sair.do.sistema..caso.contrario.o.extrato.ficara.com.o.acesso.liberado",$con,$cook_idioma);
        echo "<br><center><a href='os_extrato_senha.php?acao=alterar'>";
        fecho("alterar.senha",$con,$cook_idioma);
        echo "</a> &nbsp; - &nbsp; <a href='os_extrato_senha.php?acao=libera'>";
        fecho("liberar.tela",$con,$cook_idioma);
        echo "</a></center>";
        echo "</td>";
        echo "</tr>";
        echo "</table><br>";
    }else{
        echo "<table class='Tabela' width='700' cellspacing='0'  cellpadding='0' align='center'>";
        echo "<tr >";
        echo "<td bgcolor='FFFFFF' width='60'><img src='imagens/cadeado2.jpg' align='absmiddle'></td><td  class='Mensagem' bgcolor='FFFFFF' align='left'><a href='os_extrato_senha.php?acao=inserir' >";
        fecho ("esta.area.nao.esta.protegida.por.senha",$con,$cook_idioma);
        echo "<br/>";
        fecho ("para.inserir.senha.para.restricao.do.extrato.clique.aqui.e.saiba.mais",$con,$cook_idioma);
        echo "</a></td>";
        echo "</tr>";
        echo "</table><br>";
    }
}

if ($login_fabrica==20) {
    echo "<table class='Tabela' width='700' cellspacing='0'  cellpadding='0' align='center'>";
    echo "<tr >";
    echo "<td  class='Mensagem' bgcolor='FFFFFF' align='left'>(*)";
    fecho ("previsao.de.pagamento.e.uma.data.de.referencia.para.o.credito.do.valor.de.garantia..o.pagamento.pode.ocorrer.em.ate.10.dias.uteis.apos.esta.data..se.nao.houver.uma.data.neste.campos,.o.lote.foi.aprovado.pela.auditoria.mas.ainda.nao.foi.enviado.para.pagamento",$con,$cook_idioma);
    echo ".</td>";
    echo "</tr>";
    echo "</table><br>";
}

$periodo = trim($_POST['periodo']);
if (strlen($_GET['periodo']) > 0) $periodo = trim($_GET['periodo']);

# -- VERIFICA SE … POSTO OU DISTRIBUIDOR -- #
$sql = "SELECT  DISTINCT
            tbl_tipo_posto.tipo_posto ,
            tbl_posto.estado
    FROM    tbl_tipo_posto
    JOIN    tbl_posto_fabrica    ON tbl_posto_fabrica.tipo_posto = tbl_tipo_posto.tipo_posto
                                AND tbl_posto_fabrica.posto      = $login_posto
                                AND tbl_posto_fabrica.fabrica    = $login_fabrica
    JOIN    tbl_posto            ON tbl_posto.posto = tbl_posto_fabrica.posto
    WHERE   tbl_tipo_posto.distribuidor IS TRUE
    AND     tbl_posto_fabrica.fabrica = $login_fabrica
    AND     tbl_tipo_posto.fabrica    = $login_fabrica
    AND     tbl_posto_fabrica.posto   = $login_posto ";
$res = pg_query ($con,$sql);
$tipo_posto = (pg_num_rows($res) == 0) ? "P" : "D";

//  Confere se tem extratos liberados sem NF, ent„o procura o comunicado para esse extratoe mostra
if (in_array($login_fabrica,array(11,24,25,50))) {
//  Verifica se tem comunicados sem ler sobre liberaÁ„o de extrato...
    $sql_extratos   = "SELECT extrato
                        FROM tbl_extrato
                        LEFT JOIN tbl_extrato_pagamento USING (extrato)
                        WHERE posto=$login_posto
                        AND fabrica=$login_fabrica
                        AND nf_autorizacao IS NULL
                        ORDER BY liberado";
    $res_ext        = pg_query($con,$sql_extratos);
    if (!is_bool($res_ext)) {   // Se n„o deu erro na consulta...
        $extratos       = pg_fetch_all($res_ext);
        if (!$extratos === false) { // Se tem extrato sem NF recebida...
            echo "<table style='table-layout: fixed;font-family: verdana; font-size: 10px'".
                 " align='center' width='50%'>\n";
            foreach ($extratos as $extrato_num) {
                $extrato = $extrato_num['extrato'];
                $sql_com = "SELECT comunicado,descricao,mensagem,ativo,
                                to_char(data::date,'DD/MM/YYYY') AS data
                                FROM tbl_comunicado
                                WHERE posto   = $login_posto
                                AND descricao ILIKE '%$extrato%'";
                $res_com = pg_query($con, $sql_com);
                if (pg_num_rows($res_com)==1 and pg_fetch_result($res_com, 0, ativo)=='t') {
                    echo "\t<tr 'style='background-color: #66a;color:white'>\n".
                         "\t\t<td style='width:33%;padding: 10px' valign='top'>Comunicado <b>".pg_fetch_result($res_com, 0, comunicado).
                         "</b><br><br><p>".pg_fetch_result($res_com, 0, descricao)."</p></td>\n".
                         "\t\t<td style='width:66%;padding: 10px'><p>".pg_fetch_result($res_com, 0, mensagem)."</p></td>\n";
                    "\t</tr>\n";
                }
            }
            echo "</table>\n";
        }
    }
}

if ($login_fabrica==11) {
    echo "<TABLE style='font-family: verdana; font-size: 12px' align='center' width='50%'>";
    echo "<tr class='table_line3'>\n";
    echo "<td align=\"center\"><B>EMITIR NOTA FISCAL:</B><BR>
    Aulik Industria e Comercio Ltda.<BR>
    Rua Carlos Alberto Santos, 187 - Galp„o 03/04/05 QD.BO/C-Lote Miragem - Buraquinho<BR>
    Lauro de Freitas / BA. CEP 42700-000<BR>
    CNPJ: 05.256.426/0001-24 <BR>
    INSCR.EST. : 62.942.325</td>\n";
    echo "</tr>\n";
    echo "<tr class='table_line3'>\n";
    echo "<td align=\"center\"><B>ENVIAR PARA:</b><BR>
    Aulik Industria e Comercio Ltda.<BR>
    Rua Bela Cintra, 986 - 3 Andar - BELA VISTA<BR>
    S„o Paulo / SP.  CEP 01415-000</td>\n";
    echo "</tr>\n";
    echo "</table>";
}
if ($login_fabrica==25 or $login_fabrica==51) { ?>
    <TABLE width="650" align="center" border="0" cellspacing="0" cellpadding="2">
    <? $class = ($login_fabrica==25 or $login_fabrica==51) ? "menu_top4" : "menu_top"; ?>
    <TR>
        <TD colspan="10" class="<? echo $class; ?>" ><div align="center" style='font-size:16px'>
        <b>
        <?
            if ($pecas_pendentes=="sim"){
                echo "DEVOLU«√O PENDENTE";
            }else{
                if($login_fabrica != '51'){
                    fecho("atencao",$con,$cook_idioma);
                }
            }
        ?>
        </b></div></TD>
    </TR>
    </table>
<?
    echo "<TABLE width='650' align='center' border='0' cellspacing='0' cellpadding='2'>";
    echo "<tr class='table_line3'>\n";
    if ($login_fabrica == 51){
        /*
        echo "<td align=\"center\"><B>CUSTO DE ENVIO:</B><BR>";
            echo "<FONT SIZE='2'>";
            /* HD 40226 11/9/2008
            Custo de envio das OS's e das pe√ßas de devolu√ß√£o ser√£o ressarcidos no pr√≥ximo extrato. Favor encaminhar as OS's via SEDEX junto com o comprovante de postagem.*/
            /* HD 79440 Ronaldo enviou por email
            echo "Custo de envio das OS's e das pe√ßas de devolu√ß√£o ser√£o ressarcidos no pr√≥ximo extrato. Favor encaminhar as OS's via SEDEX junto com o comprovante de postagem. E as pe√ßas dever√£o ser devolvidas atrav√©s de encomenda PAC Varejo (Correio).";
            echo "</FONT><br>";
        echo "</td>\n";
        */
    }else{
        echo "<td align=\"center\"><B>CUSTO DE ENVIO:</B><BR>
        <FONT SIZE='2'>Custo de envio das OS's e dos aparelhos de devoluÁ„o ser„o ressarcidos no prÛximo extrato. Favor encaminhar junto com as OS's o comprovante de postagem.</FONT><br>
        </td>\n";
    }
    echo "</tr>\n";
    /*echo "<tr class='table_line3'>\n";
    echo "<td align=\"center\"><B>EMITIR NOTA FISCAL DE M√ÉO DE OBRA E ENVIAR OS's PARA CONFER√äNCIA:</B><BR>
    HB ASSIST√äNCIA T√âCNICA LTDA.<br>
    Av. Yojiro Takaoka, 4.384 - Conj. 2156 - Loja 17<br>
    Alphaville<br>
    Santana de Parna√≠ba, SP, CEP 06.541-038<br>
    CNPJ: 08.326.458/0001-47 </td>\n";
    echo "</tr>\n";
    echo "<tr class='table_line3'>\n";
    echo "<td align=\"center\"><B>ENVIAR OS's PARA CONFER√äNCIA E LIBERA«√ÉO DO EXTRATO DE M√ÉO DE OBRA:</b><BR>
    HBFLEX S.A.<br>
    Av. Marqu√™s de S√£o Vicente, 121 - Bl. B - Conj 401<br>
    Barra Funda<br>
    S√£o Paulo, SP, CEP 01139-001 </td>\n";
    echo "</tr>\n";*/
    echo "</table>";
    echo "<BR>";
    $razao    = "TELECONTROL NETWORKING LTDA.";
    $endereco = "";
    $cidade   = "";
    $estado   = "SP";
    $cep      = "";
    $fone     = "(14) 3413-6588";
    $cnpj     = "438.200.748-116";
    $ie       = "438.200.748-116";
    /*echo "<TABLE style='font-family: verdana; font-size: 12px' align='center' width='50%'>";
    echo "<tr class='table_line3'>\n";
    echo "<td align=\"center\"><B>EMITIR NOTA FISCAL:</B><BR>
    TELECONTROL NETWORKING LTDA.<br>
    AV.CARLOS ARTENCIO, 420 B - FRAGATA C<br>
    MAR√çLIA, SP, CEP 17519-255 <br>
    CNPJ: 04.716.427/0001-41 </td>\n";
    echo "</tr>\n";
    echo "<tr class='table_line3'>\n";
    echo "<td align=\"center\"><B>ENVIAR PARA:</b><BR>
    TELECONTROL NETWORKING LTDA.<br>
    AV.CARLOS ARTENCIO, 420 B - FRAGATA C<br>
    MAR√çLIA, SP, CEP 17519-255 </td>\n";
    echo "</tr>\n";
    echo "</table>";*/
}

if($login_fabrica == 30) { // HD 60266
        echo "<TABLE width='650' align='center' border='0' cellspacing='0' cellpadding='2'>";
        echo "<tr>\n";
        echo "<td align=\"center\" style='color:#FF0000'>Emitir NF somente apÛs e-mail enviado pela F·brica, Extrato liberado somente para consulta de ServiÁo e KM. Quando o nosso Extrato estiver completo enviaremos um comunicado aos SAE's
        </td>\n";
        echo "</tr></table>";
}
if ($login_fabrica == 3 or $login_fabrica == 19) {
    if($fabrica==3) {
        # --------------------------------------- #
        # -- MONTA COMBO COM DATAS DE EXTRATOS -- #
        # --------------------------------------- #
        $sql = "SELECT      DISTINCT
                            tbl_extrato.protocolo                                          ,
                            date_trunc('day',tbl_extrato.data_geracao)      AS data_extrato,
                            to_char(tbl_extrato.data_geracao, 'DD/MM/YYYY') AS data        ,
                            to_char(tbl_extrato.data_geracao, 'YYYY-MM-DD') AS periodo
                FROM        tbl_extrato
                JOIN        tbl_posto_fabrica    ON tbl_posto_fabrica.posto   = tbl_extrato.posto
                                                AND tbl_posto_fabrica.fabrica = $login_fabrica ";

        if ($tipo_posto == "D") $sql .= " WHERE (tbl_posto_fabrica.posto = $login_posto OR tbl_posto_fabrica.distribuidor = $login_posto) ";
        else                    $sql .= " WHERE tbl_posto_fabrica.posto  = $login_posto ";

        $sql .="AND      tbl_extrato.fabrica = $login_fabrica
                AND      tbl_extrato.aprovado IS NOT NULL
                ORDER BY to_char(tbl_extrato.data_geracao, 'YYYY-MM-DD') DESC";
        $res = pg_query ($con,$sql);

        if (pg_num_rows($res) > 0) {
            echo "<form name=\"frm_periodo\" method=\"get\" action=\"$PHP_SELF\">";
            echo "<input type=\"hidden\" name=\"exibir\" value=\"acumulado\">";
            echo "<table width='80%' border='0' cellpadding='2' cellspacing='2' align='center'>";
            echo "<tr>";
            echo "<td bgcolor='#FFFFFF' align='center'>";
            echo "<select name='periodo' onchange='javascript:frm_periodo.submit()'>\n";
            echo "<option value=''>";
            fecho("informe.o.periodo.para.consulta",$con,$cook_idioma);
            echo "</option>\n";

            for ($x = 0 ; $x < pg_num_rows($res) ; $x++){
                $protocolo   = trim(pg_fetch_result($res,$x,protocolo));
                $aux_data  = trim(pg_fetch_result($res,$x,data));
                $aux_extr  = trim(pg_fetch_result($res,$x,data_extrato));
                $aux_peri  = trim(pg_fetch_result($res,$x,periodo));
                echo "<option value='$aux_peri'"; if ($periodo == $aux_peri) echo " SELECTED "; echo ">$aux_data</option>\n";
            }
            echo "</select>\n";
            echo "</td>";
            echo "</tr>";
            echo "</table>";
            echo "</form>";
        }
    }else{?>
        <form name="frm_mes" method="get" action="<? echo $PHP_SELF; ?>">
        <input type="hidden" name="exibir" value="detalhado">
        <table>
            <tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
                <td> <? fecho("ano",$con,$cook_idioma);?></td>
                <td> <? fecho("mes",$con,$cook_idioma);?></td>
            </tr>
            <tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
                <td>
                    <select name="ano" size="1" class="frm" >
                    <option value=''></option>
                    <?
                    for($i = date("Y"); $i > 2003; $i--){
                        echo "<option value='$i'";
                        if ($ano == $i) echo " selected";
                        echo ">$i</option>";
                    }
                    ?>
                    </select>
                </td>
                <td>
                    <select name="mes" size="1" class="frm" onchange='javascript:frm_mes.submit()'>
                    <option value=''></option>
                    <?
                    for ($i = 1 ; $i <= count($meses) ; $i++) {
                        echo "<option value='$i'";
                        if ($mes == $i) echo " selected";
                        echo ">" . $meses[$i] . "</option>";
                    }
                    ?>
                    </select>
                </td>
            </tr>
        </table>
        </form>
<?  }

    //LEGENDA
    echo "<table width='700' height=16 border='0' cellspacing='0' cellpadding='5' align='center'>";
    echo "<tr>";
    echo "<td align='center' width='16' bgcolor='#FFE1E1'>&nbsp;</td>";
    echo "<td align='left'><font size=1><b>&nbsp; ";
    fecho("extrato.avulso",$con,$cook_idioma);
    echo "</b></font></td>";
    echo "</tr>";
    echo "</table>";
    echo "<br>";

    # -- SE FOI SELECIONADO PERÕODO NO COMBO -- #
    $mes = trim (strtoupper ($_GET['mes']));
    $ano = trim (strtoupper ($_GET['ano']));

    if (strlen($periodo) > 0 or $login_fabrica==19) {
        $exibir = $_POST['exibir'];
        if (strlen($_GET['exibir']) > 0) $exibir = $_GET['exibir'];

        if ($exibir == 'acumulado') {
            # -- EXIBE VALORES ACUMULADOS DOS EXTRATOS -- #
            # -- SELECIONA EXTRATOS DOS POSTOS -- #
            $sql = "SELECT      tbl_linha.linha                                                    ,
                                tbl_linha.nome                                       AS linha_nome ,
                                count(tbl_os.os)                                     AS qtde_os    ,
                                tbl_os.mao_de_obra                                   AS mo_unit    ,
                                sum (tbl_os.mao_de_obra)                             AS mo_posto   ,
                                sum (tbl_familia.mao_de_obra_adicional_distribuidor) AS mo_adicional
                    FROM        tbl_os
                    JOIN        tbl_os_extra         ON tbl_os_extra.os           = tbl_os.os
                                                    AND tbl_os.fabrica            = $login_fabrica
                    JOIN        tbl_extrato          ON tbl_extrato.extrato       = tbl_os_extra.extrato
                                                    AND tbl_extrato.fabrica       = $login_fabrica
                    JOIN        tbl_produto          ON tbl_produto.produto       = tbl_os.produto
                    JOIN        tbl_linha            ON tbl_produto.linha         = tbl_linha.linha
                                                    AND tbl_linha.fabrica         = $login_fabrica
                    LEFT JOIN   tbl_familia          ON tbl_produto.familia       = tbl_familia.familia
                    JOIN        tbl_posto_fabrica    ON tbl_os.posto              = tbl_posto_fabrica.posto
                                                    AND tbl_posto_fabrica.fabrica = tbl_os.fabrica
                    WHERE       tbl_posto_fabrica.fabrica = $login_fabrica ";

            if ($tipo_posto == "D") $sql .= "AND (tbl_posto_fabrica.distribuidor = $login_posto OR tbl_posto_fabrica.posto = $login_posto) ";
            else                    $sql .= "AND tbl_os.posto = $login_posto ";

            $sql .="AND         tbl_extrato.data_geracao BETWEEN '$periodo 00:00:00' AND '$periodo 23:59:59'
                    GROUP BY    tbl_linha.linha    ,
                                tbl_linha.nome     ,
                                tbl_os.mao_de_obra
                    ORDER BY    linha_nome         ,
                                tbl_os.mao_de_obra ";
            $res = pg_query($con,$sql);

            echo "<table width='700' align='center' border='0' cellspacing='1' cellpadding='1'>\n";

            if (pg_num_rows($res) > 0) {
                $qtde_linhas     = pg_num_rows($res);
                $qtde_os         = 0;
                $mo_posto        = 0;
                $mo_adicional    = 0;
                $pecas_total     = 0;
                $adicional_pecas = 0;
                $total           = 0;

                echo "<form method=post name=frm_extrato action=\"$PHP_SELF\">";
                echo "<tr class='table_line2' style='background-color: #D9E2EF;'>";
                echo "<td nowrap align='center'><b>";
                fecho("linha",$con,$cook_idioma);
                echo "</b></td>";
                echo "<td nowrap align='center'><b>M.O.<br>UNIT.</b></td>";
                echo "<td nowrap align='center'><b>";
                fecho("qtd",$con,$cook_idioma);
                echo "</b></td>";
                echo "<td nowrap align='center'><b>M.O.<br>POSTOS</b></td>";
                if ($tipo_posto == "D") echo "<td nowrap align='center'><b>M.O.<br>ADICIONAL</b></td>";
                if ($tipo_posto == "D") echo "<td nowrap align='center'><b>PE«AS<br>TOTAL</b></td>";
                if ($tipo_posto == "D") echo "<td nowrap align='center'><b>ADICIONAL<br>PE«AS</b></td>";
                if ($tipo_posto == "D") echo "<td nowrap align='center'><b>N.F.<br>SERVI«O</b></td>";
                echo "<td nowrap align='center'>&nbsp;</td>";
                echo "</tr>";

                for ($y=0; $y < pg_num_rows($res); $y++) {
                    $linha        = trim(pg_fetch_result($res,$y,linha));
                    $nome_linha   = trim(pg_fetch_result($res,$y,linha_nome));
                    $mo_unit      = trim(pg_fetch_result($res,$y,mo_unit));
                    $qtde_os      = trim(pg_fetch_result($res,$y,qtde_os));
                    $mo_posto     = trim(pg_fetch_result($res,$y,mo_posto));
                    $mo_adicional = trim(pg_fetch_result($res,$y,mo_adicional));

                    //////////////////////////////////////////////
                    $btn = 'azul';
                    $cor = ($y % 2 == 0) ? '#F1F4FA' : "#F7F5F0";

                    echo "<tr class='table_line2' style='background-color: $cor;'>\n";
                    echo "<td align='left'>$nome_linha</td>\n";
                    echo "<td align='right'>". number_format($mo_unit,2,",",".") ."</td>\n";
                    echo "<td align='right'>$qtde_os</td>\n";
                    echo "<td align='right'>". number_format($mo_posto,2,",",".") ."</td>\n";

                    if ($tipo_posto == "D") {
                        echo "<td align='right'>". number_format($mo_adicional,2,",",".") ."</td>\n";

                        $sql = "SELECT ROUND (SUM (tbl_os_item.qtde * tbl_tabela_item.preco)::numeric, 2) AS preco
                                FROM    tbl_os
                                JOIN    tbl_os_produto       ON tbl_os.os                 = tbl_os_produto.os
                                JOIN    tbl_os_item          ON tbl_os_produto.os_produto = tbl_os_item.os_produto
                                JOIN    tbl_os_extra         ON tbl_os.os                 = tbl_os_extra.os
                                JOIN    tbl_extrato          ON tbl_extrato.extrato       = tbl_os_extra.extrato
                                JOIN    tbl_produto          ON tbl_os.produto            = tbl_produto.produto
                                JOIN    tbl_linha            ON tbl_produto.linha         = tbl_linha.linha
                                JOIN    tbl_familia          ON tbl_produto.familia       = tbl_familia.familia
                                JOIN    tbl_posto_fabrica    ON tbl_os.posto              = tbl_posto_fabrica.posto
                                                            AND tbl_posto_fabrica.fabrica = tbl_os.fabrica
                                JOIN    tbl_posto_linha      ON tbl_posto_linha.posto     = $login_posto
                                                            AND tbl_posto_linha.linha     = $linha
                                JOIN    tbl_tabela_item      ON tbl_tabela_item.tabela    = tbl_posto_linha.tabela
                                                            AND tbl_tabela_item.peca      = tbl_os_item.peca
                                WHERE   (tbl_posto_fabrica.distribuidor = $login_posto OR tbl_os.posto = $login_posto)
                                AND     tbl_extrato.data_geracao BETWEEN '$periodo 00:00:00' AND '$periodo 23:59:59'
                                AND     tbl_os.fabrica     = $login_fabrica
                                AND     tbl_linha.linha    = $linha
                                AND     tbl_os.mao_de_obra = $mo_unit ";
                        $resX = pg_query ($con,$sql);

                        if (pg_num_rows($resX) > 0) {
                            $pecas_preco    = pg_fetch_result ($resX,0,preco);
                            $adicional      = $pecas_preco * 0.5385;
                            $nf_servico     = $mo_posto + $mo_adicional + $adicional;
                            $t_pecas_total += $pecas_preco;

                            echo "<td align='right'>". number_format($adicional,2,",",".")    ."</td>\n";
                            echo "<td align='right'>". number_format($mo_adicional,2,",",".") ."</td>\n";
                            echo "<td align='right'>". number_format($nf_servico,2,",",".")   ."</td>\n";
                        }
                    }

                    if ($y == 0) {
                        echo "<td width='85' rowspan='$qtde_linhas' valign='center'><a href='$PHP_SELF?periodo=$periodo&exibir=detalhado'>";
                        echo ($sistema_lingua == "ES") ? "<img src='imagens/btn_detallar_".$btn.".gif'>" : "<img src='imagens/btn_detalhar_".$btn.".gif'>";
                        echo "</a></td>\n";
                    }

                    $t_qtde_os         += $qtde_os;
                    $t_mo_posto        += $mo_posto;
                    $t_mo_adicional    += $mo_adicional;
                    $t_adicional_pecas += $adicional;
                    $total             += $nf_servico;

                    echo "</tr>\n";
                }

                echo "<tr class='table_line2' style='background-color: #D9E2EF;'>\n";
                echo "<td align='center' colspan='2' nowrap><b>";
                fecho("totais",$con,$cook_idioma);
                echo "</b></td>\n";
                echo "<td nowrap align='right'><b>$t_qtde_os</b></td>";
                echo "<td nowrap align='right'><b>" . number_format ($t_mo_posto,2,",",".") . "</b></td>";
                if ($tipo_posto == "D") echo "<td nowrap align='right'><b>" . number_format ($t_mo_adicional,2,",",".")    . "</b></td>";
                if ($tipo_posto == "D") echo "<td nowrap align='right'><b>" . number_format ($t_pecas_total,2,",",".")     . "</b></td>";
                if ($tipo_posto == "D") echo "<td nowrap align='right'><b>" . number_format ($t_adicional_pecas,2,",",".") . "</b></td>";
                if ($tipo_posto == "D") echo "<td nowrap align='right'><b>" . number_format ($total,2,",",".") . "</b></td>";
                echo "<td align='right' colspan='2' nowrap>&nbsp;</td>\n";
                echo "</tr>\n";
                echo "</form>";
            }else{
                echo "<tr class='table_line'>\n";
                echo "<td align=\"center\">";
                fecho("nenhum.extrato.foi.encontrado",$con,$cook_idioma);
                echo "</td>\n";
                echo "</tr>\n";
                echo "<tr>\n";
                echo "<td align=\"center\">\n";
                echo "<br><a href='menu_os.php'><img src='imagens/btn_voltar.gif'></a>";
                echo "</td>\n";
                echo "</tr>\n";
            }
            echo "</table>\n";
        }else{

            $condicao1 = "";
            $condicao2 = "";
            $condicao3 = "";

            if ($login_fabrica == 19){
                if (strlen($mes) > 0) {
                    $data_inicial = date("Y-m-01 00:00:00", mktime(0, 0, 0, $mes, 1, $ano));
                    $data_final   = date("Y-m-t 23:59:59", mktime(0, 0, 0, $mes, 1, $ano));
                }

                if(strlen($data_inicial)==0 and strlen($data_final)==0){
                    $temp1  = "INTO TEMP temp_extrato_lorenzetti ";
                    $temp2 = "INTO TEMP temp_extrato_lorenzetti2 ";
                    $condicao1 = " AND  tbl_extrato_financeiro.pagamento > current_date";
                    $condicao2 = " AND   tbl_extrato_financeiro.pagamento IS NULL";
                    $condicao3 = " AND tbl_extrato.nf_recebida IS NOT NULL AND tbl_extrato.nf_recebida != 'f'";
                }
                # -- EXIBE VALORES DETALHADOS DOS EXTRATOS -- #
                $sql = "SELECT  tbl_posto_fabrica.codigo_posto                                                                 ,
                        tbl_posto.nome                                                                                 ,
                        tbl_extrato.posto                                                                              ,
                        tbl_extrato.extrato                                                                            ,
                        to_char(tbl_extrato.data_geracao, 'YYYY-MM-DD')                                 AS data_extrato,
                        to_char(tbl_extrato.data_geracao, 'DD/MM/YYYY')                                 AS data_geracao,
                        tbl_extrato.mao_de_obra                                                                        ,
                        tbl_extrato.mao_de_obra_postos                                                                 ,
                        SUM (tbl_os_extra.mao_de_obra + tbl_os_extra.taxa_visita + tbl_os_extra.deslocamento_km) AS extra_mo  ,
                        sum(tbl_familia.mao_de_obra_adicional_distribuidor)                             AS adicional   ,
                        tbl_extrato.pecas                                                                              ,
                        SUM (tbl_os_extra.custo_pecas)                                                  AS extra_pecas ,
                        SUM (tbl_os_extra.taxa_visita)                                                  AS extra_instalacao,
                        tbl_extrato.protocolo                                                                          ,
                        tbl_extrato.avulso                                                                            ,
                        TO_CHAR (tbl_extrato_financeiro.previsao,'DD/MM/YYYY') AS previsao                         ,
                        TO_CHAR (tbl_extrato_financeiro.pagamento,'DD/MM/YYYY')  AS pagamento                      ,
                        tbl_posto.estado                                                                               ,
                        tbl_posto_fabrica.pedido_via_distribuidor
                    $temp1
                    FROM        tbl_extrato
                    JOIN        tbl_extrato_financeiro ON tbl_extrato_financeiro.extrato = tbl_extrato.extrato
                    JOIN        tbl_posto_fabrica    ON tbl_extrato.posto         = tbl_posto_fabrica.posto
                    AND tbl_posto_fabrica.fabrica = $login_fabrica AND tbl_posto_fabrica.posto = $login_posto
                    JOIN        tbl_posto            ON tbl_extrato.posto         = tbl_posto.posto
                    JOIN        tbl_os_extra         ON tbl_os_extra.extrato      = tbl_extrato.extrato
                    JOIN        tbl_os               ON tbl_os.os                 = tbl_os_extra.os
                    JOIN        tbl_produto          ON tbl_produto.produto       = tbl_os.produto
                    LEFT JOIN   tbl_familia          ON tbl_familia.familia       = tbl_produto.familia
                    WHERE       tbl_extrato.fabrica = $login_fabrica ";

                if ($tipo_posto == "D") $sql .= "AND (tbl_posto_fabrica.distribuidor = $login_posto OR tbl_posto_fabrica.posto = $login_posto) ";
                else                    $sql .= "AND tbl_extrato.posto   = $login_posto ";
                if(strlen($data_inicial)>0 and strlen($data_final)>0){
                    $sql .="AND tbl_extrato.data_geracao BETWEEN '$data_inicial' AND '$data_final'";
                }else{
                    $sql .= $condicao1;
                }
                $sql .=" AND  tbl_extrato.aprovado IS NOT NULL
                        $condicao3
                        GROUP BY    tbl_posto_fabrica.codigo_posto            ,
                                    tbl_posto.nome                            ,
                                    tbl_extrato.posto                         ,
                                    tbl_extrato.extrato                       ,
                                    tbl_extrato.data_geracao                  ,
                                    tbl_posto_fabrica.pedido_via_distribuidor ,
                                    tbl_extrato.mao_de_obra                   ,
                                    tbl_extrato.mao_de_obra_postos            ,
                                    tbl_extrato.protocolo                     ,
                                    tbl_extrato.avulso                        ,
                                    tbl_extrato.pecas                         ,
                                    tbl_extrato_financeiro.previsao           ,
                                    tbl_extrato_financeiro.pagamento          ,
                                    tbl_posto.estado
                        ORDER BY tbl_extrato.data_geracao DESC; ";

                if(strlen($data_inicial)==0 and strlen($data_final)==0){
                    $sql .= "SELECT  tbl_posto_fabrica.codigo_posto                                                                 ,
                        tbl_posto.nome                                                                                 ,
                        tbl_extrato.posto                                                                              ,
                        tbl_extrato.extrato                                                                            ,
                        to_char(tbl_extrato.data_geracao, 'YYYY-MM-DD')                                 AS data_extrato,
                        to_char(tbl_extrato.data_geracao, 'DD/MM/YYYY')                                 AS data_geracao,
                        tbl_extrato.mao_de_obra                                                                        ,
                        tbl_extrato.mao_de_obra_postos                                                                 ,
                        SUM (tbl_os_extra.mao_de_obra + tbl_os_extra.taxa_visita + tbl_os_extra.deslocamento_km) AS extra_mo  ,
                        sum(tbl_familia.mao_de_obra_adicional_distribuidor)                             AS adicional   ,
                        tbl_extrato.pecas                                                                              ,
                        SUM (tbl_os_extra.custo_pecas)                                                  AS extra_pecas ,
                        SUM (tbl_os_extra.taxa_visita)                                                  AS extra_instalacao,
                        tbl_extrato.protocolo                                                                          ,
                        tbl_extrato.avulso                                                                            ,
                        TO_CHAR (tbl_extrato_financeiro.previsao,'DD/MM/YYYY') AS previsao                         ,
                        TO_CHAR (tbl_extrato_financeiro.pagamento,'DD/MM/YYYY')  AS pagamento                      ,
                        tbl_posto.estado                                                                               ,
                        tbl_posto_fabrica.pedido_via_distribuidor
                    $temp2
                    FROM        tbl_extrato
                    JOIN        tbl_extrato_financeiro ON tbl_extrato_financeiro.extrato = tbl_extrato.extrato
                    JOIN        tbl_posto_fabrica    ON tbl_extrato.posto         = tbl_posto_fabrica.posto
                    AND tbl_posto_fabrica.fabrica = $login_fabrica AND tbl_posto_fabrica.posto = $login_posto
                    JOIN        tbl_posto            ON tbl_extrato.posto         = tbl_posto.posto
                    JOIN        tbl_os_extra         ON tbl_os_extra.extrato      = tbl_extrato.extrato
                    JOIN        tbl_os               ON tbl_os.os                 = tbl_os_extra.os
                    JOIN        tbl_produto          ON tbl_produto.produto       = tbl_os.produto
                    LEFT JOIN   tbl_familia          ON tbl_familia.familia       = tbl_produto.familia
                    WHERE       tbl_extrato.fabrica = $login_fabrica ";

                    if ($tipo_posto == "D") $sql .= "AND (tbl_posto_fabrica.distribuidor = $login_posto OR tbl_posto_fabrica.posto = $login_posto) ";
                    else                    $sql .= "AND tbl_extrato.posto   = $login_posto ";
                    if(strlen($data_inicial)>0 and strlen($data_final)>0){
                        $sql .="AND tbl_extrato.data_geracao BETWEEN '$data_inicial' AND '$data_final'";
                    }else{
                        $sql .= $condicao2;
                    }
                    $sql .=" AND  tbl_extrato.aprovado IS NOT NULL
                            $condicao3
                            GROUP BY    tbl_posto_fabrica.codigo_posto            ,
                                        tbl_posto.nome                            ,
                                        tbl_extrato.posto                         ,
                                        tbl_extrato.extrato                       ,
                                        tbl_extrato.data_geracao                  ,
                                        tbl_posto_fabrica.pedido_via_distribuidor ,
                                        tbl_extrato.mao_de_obra                   ,
                                        tbl_extrato.mao_de_obra_postos            ,
                                        tbl_extrato.protocolo                     ,
                                        tbl_extrato.avulso                        ,
                                        tbl_extrato.pecas                         ,
                                        tbl_extrato_financeiro.previsao           ,
                                        tbl_extrato_financeiro.pagamento          ,
                                        tbl_posto.estado
                            ORDER BY tbl_extrato.data_geracao DESC; ";

                    $sql .= " SELECT * from temp_extrato_lorenzetti
                            UNION
                             SELECT * from temp_extrato_lorenzetti2";
                }//periodo
            }else{//OUTRAS FABRICAS
                # -- EXIBE VALORES DETALHADOS DOS EXTRATOS -- #
                $sql = "SELECT  tbl_posto_fabrica.codigo_posto                                                                 ,
                        tbl_posto.nome                                                                                 ,
                        tbl_extrato.posto                                                                              ,
                        tbl_extrato.extrato                                                                            ,
                        to_char(tbl_extrato.data_geracao, 'YYYY-MM-DD')                                 AS data_extrato,
                        to_char(tbl_extrato.data_geracao, 'DD/MM/YYYY')                                 AS data_geracao,
                        tbl_extrato.mao_de_obra                                                                        ,
                        tbl_extrato.mao_de_obra_postos                                                                 ,
                        SUM (tbl_os_extra.mao_de_obra + tbl_os_extra.taxa_visita + tbl_os_extra.deslocamento_km) AS extra_mo  ,
                        sum(tbl_familia.mao_de_obra_adicional_distribuidor)                             AS adicional   ,
                        tbl_extrato.pecas                                                                              ,
                        SUM (tbl_os_extra.custo_pecas)                                                  AS extra_pecas ,
                        SUM (tbl_os_extra.taxa_visita)                                                  AS extra_instalacao,
                        tbl_extrato.protocolo                                                                          ,
                        tbl_extrato.avulso                                                                            ,
                        TO_CHAR (tbl_extrato_financeiro.previsao,'DD/MM/YYYY') AS previsao                         ,
                        TO_CHAR (tbl_extrato_financeiro.pagamento,'DD/MM/YYYY')  AS pagamento                      ,
                        tbl_posto.estado                                                                               ,
                        tbl_posto_fabrica.pedido_via_distribuidor
                    FROM        tbl_extrato
                    JOIN        tbl_extrato_financeiro ON tbl_extrato_financeiro.extrato = tbl_extrato.extrato
                    JOIN        tbl_posto_fabrica    ON tbl_extrato.posto         = tbl_posto_fabrica.posto
                        AND tbl_posto_fabrica.fabrica = $login_fabrica
                    JOIN        tbl_posto            ON tbl_extrato.posto         = tbl_posto.posto
                    JOIN        tbl_os_extra         ON tbl_os_extra.extrato      = tbl_extrato.extrato
                    JOIN        tbl_os               ON tbl_os.os                 = tbl_os_extra.os
                    JOIN        tbl_produto          ON tbl_produto.produto       = tbl_os.produto
                    LEFT JOIN   tbl_familia          ON tbl_familia.familia       = tbl_produto.familia
                    WHERE       tbl_extrato.fabrica = $login_fabrica ";

                if ($tipo_posto == "D") $sql .= "AND (tbl_posto_fabrica.distribuidor = $login_posto OR tbl_posto_fabrica.posto = $login_posto) ";
                else                    $sql .= "AND tbl_extrato.posto   = $login_posto ";
                if(strlen($periodo)>0){
                    $sql .="AND         tbl_extrato.data_geracao BETWEEN '$periodo 00:00:00' AND '$periodo 23:59:59'";
                }
                $sql .=" AND  tbl_extrato.aprovado IS NOT NULL
                        GROUP BY    tbl_posto_fabrica.codigo_posto            ,
                                    tbl_posto.nome                            ,
                                    tbl_extrato.posto                         ,
                                    tbl_extrato.extrato                       ,
                                    tbl_extrato.data_geracao                  ,
                                    tbl_posto_fabrica.pedido_via_distribuidor ,
                                    tbl_extrato.mao_de_obra                   ,
                                    tbl_extrato.mao_de_obra_postos            ,
                                    tbl_extrato.protocolo                     ,
                                    tbl_extrato.avulso                        ,
                                    tbl_extrato.pecas                         ,
                                    tbl_extrato_financeiro.previsao           ,
                                    tbl_extrato_financeiro.pagamento          ,
                                    tbl_posto.estado
                        ORDER BY tbl_extrato.data_geracao DESC; ";
            }
            $res = pg_query ($con,$sql);

            echo "<table width='700' align='center' border='0' cellspacing='1' cellpadding='1'>\n";
            if (pg_num_rows($res) > 0) {
                echo "<tr class='table_line'>";

                $colspan = ($login_fabrica==19) ? 13 : 8;

                echo "<td colspan=$colspan align='center'>\n";
                echo "&nbsp;";
                echo "</td>\n";
                echo "</tr>\n";

                echo "<form method=post name=frm_extrato action=\"$PHP_SELF\">";
                echo "<tr class='menu_top'>\n";

                echo "<td align=\"center\">";
                fecho("extrato",$con,$cook_idioma);
                echo "</td>\n";
                if($login_fabrica==19)echo "<td align=\"center\">RELATORIO</td>\n";
                echo "<td align=\"center\">";
                fecho("posto",$con,$cook_idioma);
                echo "</td>\n";
                echo "<td align=\"center\">";
                fecho("geracao",$con,$cook_idioma);
                echo "</td>\n";

                echo "<td align=\"center\">";
                fecho("mao.de.obra",$con,$cook_idioma);
                echo "</td>\n";

                if(!in_array($login_fabrica,array(121,123,124,125,126,127,128,129,131,134,136,137,138,139,140,141,144,145,146,147,150,152,153,157,160,180,181,182)) and !$replica_einhell){

                    echo "<td align=\"center\">";
                    fecho("pecas",$con,$cook_idioma);
                    echo "</td>\n";
                }
                if($login_fabrica==19) {
                    echo "<td align=\"center\">";
                    fecho("instalacao",$con,$cook_idioma);
                    echo "</td>\n";
                }
                echo "<td align=\"center\">";
                fecho("total",$con,$cook_idioma);
                echo "</td>\n";
                if($login_fabrica==19){
                echo "<td align=\"center\" nowrap>+ AVULSO</td>\n";
                    echo "<td align=\"center\" nowrap>(*)";
                    fecho("previsao",$con,$cook_idioma);
                    echo "</td>\n";
                    echo "<td align=\"center\" nowrap>PAGAMENTO </td>\n";
                }
                echo "<td align=\"center\">&nbsp;</td>\n";
                echo "<td align=\"center\">&nbsp;</td>\n";

                echo "</tr>\n";

                for ($i = 0 ; $i < pg_num_rows ($res) ; $i++){
                    $xmao_de_obra            = 0;
                    $posto                   = trim(pg_fetch_result($res,$i,posto));
                    $posto_codigo            = trim(pg_fetch_result($res,$i,codigo_posto));
                    $posto_nome              = trim(substr(pg_fetch_result($res,$i,nome),0,25));
                    $extrato                 = trim(pg_fetch_result($res,$i,extrato));
                    $data_geracao            = trim(pg_fetch_result($res,$i,data_geracao));
                    $pedido_via_distribuidor = trim(pg_fetch_result($res,$i,pedido_via_distribuidor));
                    $data_extrato            = trim(pg_fetch_result($res,$i,data_extrato));
                    $mao_de_obra             = trim(pg_fetch_result($res,$i,mao_de_obra));
                    $mao_de_obra_postos      = trim(pg_fetch_result($res,$i,mao_de_obra_postos));
                    $extra_mo                = trim(pg_fetch_result($res,$i,extra_mo));
                    $adicional               = trim(pg_fetch_result($res,$i,adicional));
                    $pecas                   = trim(pg_fetch_result($res,$i,pecas));
                    $extra_pecas             = trim(pg_fetch_result($res,$i,extra_pecas));
                    $extra_instalacao        = trim(pg_fetch_result($res,$i,extra_instalacao));
                    $extrato                 = trim(pg_fetch_result($res,$i,extrato));
                    $estado                  = trim(pg_fetch_result($res,$i,estado));
                    $protocolo               = trim(pg_fetch_result($res,$i,protocolo));
                    $avulso                  = trim(pg_fetch_result($res,$i,avulso));
                    $previsao                = trim(pg_fetch_result($res,$i,previsao));
                    $pagamento               = trim(pg_fetch_result($res,$i,pagamento));

                    if (strlen($adicional) == 0) $adicional = 0;

                    # soma valores
                    if ($tipo_posto == "P") {
                        $xmao_de_obra += $mao_de_obra_postos;
                        $xvrmao_obra   = $mao_de_obra_postos;
                    }else{
                        $xmao_de_obra += $mao_de_obra;
                        $xvrmao_obra   = $mao_de_obra;
                    }

                    if ($xvrmao_obra == 0)  $xvrmao_obra   = $mao_de_obra;
                    if ($xmao_de_obra == 0) $xmao_de_obra += $mao_de_obra;

                    $total = $xmao_de_obra + $pecas;
                    $data_geracao;

                    $cor = ($i % 2 == 0) ? '#F1F4FA' : '#F7F5F0';
                    $btn = ($i % 2 == 0) ? 'azul' : 'amarelo';

                    ##### LAN«AMENTO DE EXTRATO AVULSO - IN√çCIO #####
                    if (strlen($extrato) > 0) {
                        $sql = "SELECT count(*) as existe
                                FROM   tbl_extrato_lancamento
                                WHERE  extrato = $extrato
                                and    posto   = $login_posto
                                and    fabrica = $login_fabrica";
                        $res_avulso = pg_query($con,$sql);

                        if (@pg_num_rows($res_avulso) > 0) {
                            if (@pg_fetch_result($res_avulso, 0, existe) > 0) $cor = "#FFE1E1";
                        }
                    }
                    ##### LAN«AMENTO DE EXTRATO AVULSO - FIM  HD 5630#####

                    echo "<tr class='table_line' style='background-color: $cor;'>\n";
                    echo "<td align='left' style='padding-left:7px;'>";
                    echo ($login_fabrica == 1) ? $protocolo : (($login_fabrica <> 19) ? $extrato : "");
                    echo "</td>\n";
                    echo ($login_fabrica == 19) ? "<td align='left' style='padding-left:7px;'>$protocolo</td>\n" : "";
                    echo "<td align='left' nowrap>$posto_codigo - $posto_nome</td>\n";
                    echo ($tipo_posto == "D") ? "<td align='center'><a href='extrato_distribuidor.php?data=$data_extrato'>$data_geracao</a></td>\n" : "<td align='center'>$data_geracao</td>\n";

                    if ($login_fabrica == 19) {
                        $xvrmao_obra = pg_fetch_result ($res,$i,extra_mo) ;
                        $pecas       = pg_fetch_result ($res,$i,extra_pecas) ;
                        $instalacao  = pg_fetch_result ($res,$i,extra_instalacao) ;
                        $total       = $pecas + $xvrmao_obra ;
                    }

                    echo "<td align='right'  style='padding-right:3px;' nowrap>". number_format($xvrmao_obra,2,",",".") ."</td>\n";

                    if(!in_array($login_fabrica,array(121,123,124,125,126,127,128,129,131,134,136,137,138,139,140,141,142,144,145,146,147,152,153,156,157,160,180,181,182)) or !$replica_einhell){

                        echo "<td align='right'  style='padding-right:3px;' nowrap>". number_format($pecas,2,",",".") ."</td>\n";
                    }
                    if($login_fabrica==19){
                        echo "<td align='right'  style='padding-right:3px;' nowrap>". number_format($instalacao,2,",",".") ."</td>\n";
                    }
                    if($login_fabrica <> 147){
                        echo "<td align='right'  style='padding-right:3px;' nowrap>". number_format($total,2,",",".") ."</td>\n";
                    }
                    if($login_fabrica==19){
                        echo "<td align='right'  style='padding-right:3px;' nowrap>".number_format($avulso,2,",",".")."</td>\n";
                        echo "<td align='right'  style='padding-right:3px;' nowrap>$previsao</td>\n";
                        echo "<td align='right'  style='padding-right:3px;' nowrap>$pagamento</td>\n";
                    }
                    if($sistema_lingua == 'ES'){
                        echo "<td><a href='os_extrato_detalhe.php?extrato=$extrato&posto=$posto'><img src='imagens/btn_detallar_".$btn.".gif'></a></td>\n";
                    }else{
                        echo "<td><a href='os_extrato_detalhe.php?extrato=$extrato&posto=$posto'><img src='imagens/btn_detalhar_".$btn.".gif'></a></td>\n";
                        if($login_fabrica<>15){
                            if ($login_fabrica==11){
                                echo "<td><a href='extrato_posto_devolucao_lenoxx_itens.php?extrato=$extrato'><img src='imagens/btn_pecasretornaveis_".$btn.".gif'></a></TD>\n";
                            }else{
                                if ($login_fabrica==25){
                                        echo "<td><a href='extrato_posto_devolucao_hbtech_itens.php?extrato=$extrato'><img src='imagens/btn_pecasretornaveis_".$btn.".gif'></a></TD>\n";
                                    }else{
                                        if ($login_fabrica==7){ #LGR Genericos
                                            echo "<td><a href='extrato_posto_lgr.php?extrato=$extrato'><img src='imagens/btn_pecasretornaveis_".$btn.".gif'></a></TD>\n";
                                        }elseif ($login_fabrica==51){
                                            echo "<td><a href='extrato_posto_devolucao_gama_itens.php?extrato=$extrato'><img src='imagens/btn_pecasretornaveis_".$btn.".gif'></a></TD>\n";
                                        }else{
                                            echo "<td><a href='os_extrato_pecas_retornaveis.php?extrato=$extrato'><img src='imagens/btn_pecasretornaveis_".$btn.".gif'></a></TD>\n";
                                        }
                                    }
                                }
                        } else { # HD 81361 Latinatec
                            echo "<td><a href='os_extrato_pecas_latina.php?extrato=$extrato'><img src='imagens/btn_pecasretornaveis_".$btn.".gif'></a></TD>\n";
                        }
                    }
                    echo "</tr>\n";
                }
                echo "<input type='hidden' name='total' value='$i'>";
                echo "</form>";
            }else{
                echo "<tr class='table_line'>\n";
                echo "<td align=\"center\">";
                fecho("nenhum.extrato.foi.encontrado",$con,$cook_idioma);
                echo "</td>\n";
                echo "</tr>\n";
                echo "<tr>\n";
                echo "<td align=\"center\">\n";
                echo "<br><a href='menu_os.php'><img src='imagens/btn_voltar.gif'></a>";
                echo "</td>\n";
                echo "</tr>\n";
            }
            echo "</table>\n";
        }
    }
} else { // OUTROS FABRICANTES
    # -- EXIBE VALORES DETALHADOS DOS EXTRATOS -- #

    #HD 14263 - 124413
    # Desabilitei o SUM, pois a intelbras / lenoxx armazena os valores no extrato
    if (in_array($login_fabrica,array(7,11,14,25,51,80,47))) {
        if($login_fabrica == 51) {# HD 349773
            $sql = "  SELECT
                        to_char(current_date,'YYYY') as ano_atual,
                        to_char(current_date - 60,'YYYY') as ano_anterior,
                        to_char(current_date,'MM') as mes_atual,
                        to_char(current_date - 60,'MM') as mes_anterior ";
            $res = pg_query($con,$sql);
            if(pg_num_rows($res) > 0){
                $ano_atual    = pg_fetch_result($res,0,'ano_atual');
                $ano_anterior = pg_fetch_result($res,0,'ano_anterior');
                $mes_atual    = pg_fetch_result($res,0,'mes_atual');
                $mes_anterior = pg_fetch_result($res,0,'mes_anterior');
                $data_inicial = date("Y-m-01 00:00:00", mktime(0, 0, 0, $mes_anterior, 1, $ano_anterior));
                $data_final   = date("Y-m-t 23:59:59", mktime(0, 0, 0, $mes_atual, 1, $ano_atual));
                $cond = " AND tbl_extrato.data_geracao BETWEEN '$data_inicial' AND '$data_final' ";
            }
        }
        $sql = "SELECT
                tbl_posto_fabrica.codigo_posto                                                   ,
                tbl_posto.nome                                                                   ,
                tbl_extrato.posto                                                                ,
                tbl_extrato.extrato                                                              ,
                to_char(tbl_extrato.data_geracao, 'YYYY-MM-DD')                AS data_extrato   ,
                to_char(tbl_extrato.data_geracao, 'DD/MM/YYYY')                AS data_geracao   ,
                to_char(tbl_extrato.liberado, 'DD/MM/YYYY')                    AS liberado       ,
                to_char(tbl_extrato.previsao_pagamento,'DD/MM/YYYY')           AS previsao_pagamento,
                to_char(tbl_extrato_pagamento.data_pagamento,'DD/MM/YYYY')     AS data_pagamento ,
                tbl_extrato_pagamento.nf_autorizacao                                             ,
                tbl_extrato.mao_de_obra                                                          ,
                tbl_extrato.mao_de_obra_postos                                                   ,
                /* SUM (tbl_os_extra.mao_de_obra + tbl_os_extra.taxa_visita + tbl_os_extra.deslocamento_km) AS extra_mo  ,*/
                tbl_extrato.mao_de_obra                                        AS extra_mo       ,
                tbl_extrato.protocolo                                                            ,
                0                                                              AS adicional      ,
                tbl_extrato.pecas                                                                ,
                tbl_extrato.avulso                                                               ,
                tbl_extrato.admin_lgr                                                            ,
                /*SUM (tbl_os_extra.custo_pecas)                               AS extra_pecas  ,*/
                tbl_extrato.pecas                                              AS extra_pecas    ,
                tbl_posto.estado                                                                 ,
                tbl_posto_fabrica.pedido_via_distribuidor                                        ,
                tbl_extrato.total                                                                ,
                tbl_extrato_extra.nota_fiscal_devolucao                                          ,
                tbl_extrato_extra.nota_fiscal_mao_de_obra                                        ,
                to_char(tbl_extrato_extra.data_entrega_transportadora,'dd/mm/yyyy') as data_entrega_transportadora                                    ,
                to_char(tbl_extrato_extra.exportado, 'DD/MM/YYYY') As exportado,
                to_char(tbl_extrato_extra.emissao_mao_de_obra, 'DD/MM/YYYY') As emissao_mao_de_obra,
                tbl_extrato_extra.valor_total_extrato
            INTO TEMP tmp_os_extrato /* hd 39502 */
            FROM        tbl_extrato
            JOIN        tbl_posto_fabrica    ON tbl_extrato.posto         = tbl_posto_fabrica.posto
                        AND tbl_posto_fabrica.fabrica = $login_fabrica
            JOIN        tbl_posto            ON tbl_extrato.posto         = tbl_posto.posto
            LEFT JOIN   tbl_extrato_pagamento ON tbl_extrato.extrato = tbl_extrato_pagamento.extrato
    /*      LEFT JOIN   tbl_os_extra         ON tbl_os_extra.extrato      = tbl_extrato.extrato
            LEFT JOIN   tbl_os               ON tbl_os.os                 = tbl_os_extra.os
            LEFT JOIN   tbl_produto          ON tbl_produto.produto       = tbl_os.produto
            LEFT JOIN   tbl_familia          ON tbl_familia.familia       = tbl_produto.familia*/
            LEFT JOIN   tbl_extrato_extra    ON tbl_extrato.extrato       = tbl_extrato_extra.extrato
            WHERE       tbl_extrato.fabrica = $login_fabrica ";

        if ($tipo_posto == "P") $sql .= "AND tbl_extrato.posto   = $login_posto ";
        else                    $sql .= "AND (tbl_posto_fabrica.distribuidor = $login_posto OR tbl_posto_fabrica.posto = $login_posto) ";

        // HD 121178 - samuel 10/11/2009
        if($login_fabrica == 51){
            $sql .="AND   tbl_extrato.data_geracao > current_date-interval '12 month'
                    $cond ";
        }
        $sql .="AND         tbl_extrato.posto   = $login_posto
                AND         tbl_extrato.aprovado IS NOT NULL
                AND         tbl_extrato.liberado IS NOT NULL ";

        $sql .= "ORDER BY tbl_extrato.data_geracao DESC";
    }else{
        if($login_fabrica==6){
            $sql_peca=" ,tbl_extrato_extra.pecas_devolvidas ";
        }

        if($login_fabrica == 24){
            $sql_data = " AND tbl_extrato.data_geracao > '2013-09-30 00:00:00' ";
        }

        if($login_fabrica == 6){

            $sqlParametrosAdicionais = "SELECT parametros_adicionais FROM tbl_posto_fabrica WHERE posto = $login_posto AND fabrica = 6";
            $resParametrosAdicionais = pg_query($con, $sqlParametrosAdicionais);
            if (pg_num_rows($resParametrosAdicionais) > 0) {
                $parametrosAdicionais = json_decode(pg_fetch_result($resParametrosAdicionais, 0, "parametros_adicionais"), true);
                extract($parametrosAdicionais);
            }
            $parametrosAdicionais = json_encode(array("meses_extrato"=> $meses_extrato));
            $sql_data= "AND ( ( tbl_extrato.data_geracao < '2013-12-31 00:00:00'
                                    AND tbl_extrato.aprovado NOTNULL
                                    AND tbl_extrato_pagamento.data_pagamento IS NOT NULL)
                                OR (tbl_extrato.data_geracao > '2013-12-31 00:00:00') )
                        AND tbl_extrato.data_geracao > current_date-interval '$meses_extrato month'";
        }

        $sql = "SELECT  tbl_posto_fabrica.codigo_posto                                                           ,
                tbl_posto.nome                                                                                   ,
                tbl_extrato.posto                                                                                ,
                tbl_extrato.extrato                                                                              ,
                to_char(tbl_extrato.data_geracao, 'YYYY-MM-DD')                                 AS data_extrato  ,
                to_char(tbl_extrato.data_geracao, 'DD/MM/YYYY')                                 AS data_geracao  ,
                to_char(tbl_extrato.liberado, 'DD/MM/YYYY')                                     AS liberado      ,
                to_char(tbl_extrato_pagamento.data_pagamento,'DD/MM/YYYY')                      AS data_pagamento,
                to_char(tbl_extrato.previsao_pagamento,'DD/MM/YYYY')                      AS previsao_pagamento,
                tbl_extrato_pagamento.nf_autorizacao                                                             ,
                tbl_extrato.mao_de_obra                                                                          ,
                tbl_extrato.mao_de_obra_postos                                                                   ,
                /* SUM (tbl_os_extra.mao_de_obra + tbl_os_extra.taxa_visita + tbl_os_extra.deslocamento_km) AS extra_mo  ,*/
                tbl_extrato.mao_de_obra                                                         AS extra_mo      ,
                tbl_extrato.protocolo                                                                            ,
                0                                                                               AS adicional     ,
                tbl_extrato.pecas                                                                                ,
                tbl_extrato.avulso                                                                               ,
                tbl_extrato.admin_lgr                                                                            ,
                /*SUM (tbl_os_extra.custo_pecas)                                                  AS extra_pecas ,*/
                tbl_extrato.pecas                                                                AS extra_pecas  ,
                tbl_extrato.deslocamento                                                     AS deslocamento_km  ,
                tbl_posto.estado                                                                                 ,
                tbl_posto_fabrica.pedido_via_distribuidor                                                        ,
                tbl_extrato.total                                                                                ,
                tbl_extrato.valor_adicional                                                                      ,
                tbl_extrato_extra.nota_fiscal_devolucao                                                          ,
                tbl_extrato_extra.nota_fiscal_mao_de_obra                                                        ,
                to_char(tbl_extrato_extra.data_entrega_transportadora,'dd/mm/yyyy') as data_entrega_transportadora,
                to_char(tbl_extrato_extra.exportado, 'DD/MM/YYYY') As exportado,
                to_char(tbl_extrato_extra.emissao_mao_de_obra, 'DD/MM/YYYY') As emissao_mao_de_obra
                /*tbl_extrato_extra.valor_total_extrato*/
                $sql_peca
            INTO TEMP tmp_os_extrato /* hd 39502 */
            FROM        tbl_extrato
            JOIN        tbl_posto_fabrica    ON tbl_extrato.posto         = tbl_posto_fabrica.posto
                        AND tbl_posto_fabrica.fabrica = $login_fabrica
            JOIN        tbl_posto            ON tbl_extrato.posto         = tbl_posto.posto
            LEFT JOIN   tbl_extrato_pagamento ON tbl_extrato.extrato = tbl_extrato_pagamento.extrato
            LEFT JOIN   tbl_extrato_extra    ON tbl_extrato.extrato       = tbl_extrato_extra.extrato
            WHERE       tbl_extrato.fabrica = $login_fabrica
            $sql_data";

        if ($tipo_posto == "P") $sql .= "AND tbl_extrato.posto   = $login_posto ";
        else                    $sql .= "AND (tbl_posto_fabrica.distribuidor = $login_posto OR tbl_posto_fabrica.posto = $login_posto) ";


        $sql .="
            AND         tbl_extrato.posto   = $login_posto
            AND         tbl_extrato.aprovado IS NOT NULL
            /*
            AND         tbl_os.os NOT IN (SELECT tbl_os_status.os FROM tbl_os_status WHERE tbl_os.os = tbl_os_status.os AND tbl_os_status.status_os IN (13,15) AND tbl_os_status.extrato=tbl_extrato.extrato)
            */ ";

        if ( $login_fabrica == 15 ) {
            $sql .= " AND tbl_extrato.liberado IS NOT NULL ";
        }

        if($login_fabrica == 151){

            $sql_extrato_3_meses = "SELECT parametros_adicionais FROM tbl_posto_fabrica WHERE fabrica = {$login_fabrica} AND posto = {$login_posto}";
            $res_extrato_3_meses = pg_query($con, $sql_extrato_3_meses);

            if(pg_num_rows($res_extrato_3_meses) > 0){

                $parametros_adicionais = pg_fetch_result($res_extrato_3_meses, 0, "parametros_adicionais");
                if(strlen($parametros_adicionais) > 0){

                    $parametros_adicionais = json_decode($parametros_adicionais, true);

                    if(isset($parametros_adicionais["extrato_mais_3_meses"])){

                        if($parametros_adicionais["extrato_mais_3_meses"] == "t"){

                            $sql .= "AND tbl_extrato.data_geracao > current_date-interval '3 month' ";

                        }

                    }

                }

            }

        }

       if ($login_fabrica == 24){
            $sql .= " ORDER BY tbl_extrato_pagamento.data_pagamento DESC, tbl_extrato.data_geracao DESC";
        }else{
            $sql .= " ORDER BY tbl_extrato.data_geracao DESC";
        }
        if($login_fabrica ==15 ){
            $sql .= " limit 3";
        }
    }
//     echo nl2br($sql);
    $res = pg_query ($con,$sql);

    /* hd 39502 */
    if ($login_fabrica==20) {
        $sql = "ALTER table tmp_os_extrato add column total_cortesia double precision";
        $res = pg_query ($con,$sql);

        $sql = "UPDATE tmp_os_extrato SET
                    total_cortesia = (
                        SELECT sum(tbl_os.mao_de_obra) + sum(tbl_os.pecas)
                        FROM tbl_os
                        JOIN tbl_os_extra USING(os)
                        WHERE extrato = tmp_os_extrato.extrato
                        AND   tbl_os.tipo_atendimento = 16
                    )";
        $res = pg_query ($con,$sql);
    }

    /* hd 39502 */
    if (in_array($login_fabrica,array(91,125,131,138,140))) {
        $sql = "ALTER table tmp_os_extrato add column valor_km double precision";
        $res = pg_query ($con,$sql);

        $sql = "UPDATE tmp_os_extrato SET
                    valor_km = (
                        SELECT sum(qtde_km_calculada)
                        FROM tbl_os
                        JOIN tbl_os_extra USING(os)
                        WHERE extrato = tmp_os_extrato.extrato
                    )";
        $res = pg_query ($con,$sql);
    }

    //hd 39502
    $sql = "SELECT * FROM tmp_os_extrato order by data_extrato desc ";

    if ($login_fabrica == 156) {
        $sql = "SELECT * FROM tmp_os_extrato WHERE extrato IN (
            SELECT DISTINCT extrato FROM tmp_os_extrato
            JOIN tbl_os_extra USING(extrato)
            JOIN tbl_os using(os)
            WHERE hd_chamado IS NOT NULL
        ) OR extrato IN (
            SELECT DISTINCT tmp_os_extrato.extrato
            FROM tmp_os_extrato
            JOIN tbl_os_extra ON tbl_os_extra.extrato = tmp_os_extrato.extrato
              AND tbl_os_extra.i_fabrica = $login_fabrica
            JOIN tbl_hd_chamado_extra USING(os)
        ) ORDER BY data_extrato DESC";
    }

    $res = pg_query ($con,$sql);
//echo $sql;exit;
    if($login_fabrica==19){
        echo "<table width='700' height=16 border='0' cellspacing='0' cellpadding='5' align='center'>";
        echo "<tr>";
        echo "<td align='center'><font size=1>* ";
        fecho("nf.s.e.recibos.que.chegarem.entre.os.dias.20.a.05,.serao.pagos.dia.10", $con, $cook_idioma);
        echo "<br/>";
        fecho ("nf.s.e.recibos.que.chegarem.entre.os.dias.06.a.20,.serao.pagos.dia.25", $con, $cook_idioma);
        echo "<br/>";
        fecho ("as.data.de.pagamentos.10.e.25.poderao.ser.alteradas.nos.meses", $con, $cook_idioma);
        echo "<br/>";
        fecho ("em.que.estas.data.forem.feriados.ou.finais.de.semana",$con,$cook_idioma);
        echo ".<BR></font></td>";
        echo "</tr>";
        echo "</table>";
        echo "<br>";
    }

    echo "<table width='700' height=16 border='0' cellspacing='0' cellpadding='5' align='center'>";
    echo "<tr>";
    echo "<td align='center' width='16' bgcolor='#FFE1E1'>&nbsp;</td>";
    echo "<td align='left'><font size=1><b>&nbsp;";
    fecho ("extrato.avulso",$con,$cook_idioma);
    echo "</b></font></td>";

    if(in_array($login_fabrica,array(94,104,105,106,115,116,117,123,125,127,128,129,134,141,142,144,149,151,157,160)) or $replica_einhell){


        echo "<td align='center' width='16'><input type ='button' value='PeÁas para InspeÁ„o' onclick=\"window.open('lgr_vistoria_itens.php')\"></td>";
    }

    if($login_fabrica == 140){
        echo "<td align='center' width='16'><input type ='button' value='PeÁas para InspeÁ„o' onclick=\"window.open('new_extrato_distribuidor_retornaveis.php')\"></td>";
    }
    echo "</tr>";

    # HD15606
    if($login_fabrica == 6){
        echo "<tr height='3'><td colspan='2'></td></tr>";
        echo "<tr>";
        echo "<td align='center' width='16' bgcolor='#33CCFF'>&nbsp;</td>";
        echo "<td align='left'><font size='1'><b>&nbsp;";
        fecho("devolucao.confirmada",$con,$cook_idioma);
        echo "</b></font></td>";
        echo "</tr>";
    }
    echo "</table>";
    echo "<br>";
    if($login_fabrica == 24){
        echo "<center><font size=1 face=verdana>* ";
        fecho("nao.emitir.nota.fiscal.no.caso.dos.extratos.avulso",$con,$cook_idioma);
        echo "</font></center>";
    }
    echo "<table width='700' align='center' border='0' cellspacing='1' cellpadding='5'>\n";
    if (pg_num_rows($res) > 0) {
        if ($login_fabrica == 2){
            echo "<tr class='table_line'>";
            echo "<td colspan='9' align='center'>\n";
            echo "<br><b>";
            fecho("enviar.para.a.dynacom.a.nota.fiscal.de.prestacao.de.servico.e.as.ordens.de.servico.referente.ao.abaixo",$con,$cook_idioma);
            echo ". <br><font color='#FF0000'>";
            fecho("e.obrigatorio.o.envio.das.o.s",$con,$cook_idioma);
            echo ".</font></b><br><br>(";
            fecho("clique.no.numero.do.extrato.para.abrir.os.dados.da.nota.fiscal.de.devolucao",$con,$cook_idioma);
            echo ")<br><br>\n";
            echo "</td>\n";
            echo "</tr>\n";
        }

        echo "<form method=post name=frm_extrato action=\"$PHP_SELF\">";
        echo "<tr class='menu_top'>\n";
        echo "<td align='center'>";
        fecho ("extrato",$con,$cook_idioma);
        echo "</td>\n";
        if ($login_fabrica == 19) {
            echo "<td align='center'>";
            fecho("relatorio",$con,$cook_idioma);
            echo "</td>\n";
        }

        if($login_fabrica<>11){
            echo "<td align='center'>";
            fecho ("posto",$con,$cook_idioma);
            echo "</td>\n";
        }

        echo "<td align='center'>";
        if (in_array($login_fabrica,array(7,11,25,51,45))) {
            fecho("liberacao",$con,$cook_idioma);
        } else {
            fecho("geracao",$con,$cook_idioma);
        }
        echo "</td>\n";

        if (in_array($login_fabrica,array(129))) {
            echo "<td align='center'>";
                fecho("liberado",$con,$cook_idioma);
            echo "</td>\n";
        }

        if($login_fabrica == 104){
            echo "<td align=\"center\">";
            fecho("marca",$con,$cook_idioma);
            echo "</td>\n";
        }

        echo "<td align='center' style='min-width: 50px;'>";
        fecho("mao.de.obra",$con,$cook_idioma);
        echo "</td>\n";

        if(!in_array($login_fabrica,array(94,99,106,121,123,124,125,126,127,128,129,131,134,136,137,138,140,139,142,146,147,149,150,152,153,156,157,160,180,181,182)) and !$replica_einhell){
            echo "<td align='center'>";
            fecho("pecas",$con,$cook_idioma);
            echo "</td>\n";
        }

        if(in_array($login_fabrica,array(140))){
            echo "<td align='center'>";
            fecho("entrega.tecnica",$con,$cook_idioma);
            echo "</td>\n";
        }
        if(!in_array($login_fabrica, array(152,156,157,180,181,182))){
            if($inf_valores_adicionais or in_array($login_fabrica, array(142,145))){
                echo "<td align='center'>";
                    fecho("valor.adicional",$con,$cook_idioma);
                    echo "</td>\n";
            }
        }

        if(in_array($login_fabrica, array(35,90,142))){
            echo "<td align='center'>";
                fecho("valor.km",$con,$cook_idioma);
                echo "</td>\n";
        }

        if(in_array($login_fabrica, array(90))){
            echo "<td align='center'>";
            fecho("taxa_visita",$con,$cook_idioma);
            echo "</td>\n";
        }


        //hd 39502
        if($login_fabrica <> 51 AND $login_fabrica <> 128){

            if($login_fabrica == 129){

                echo "<td align='center'> +KM </td>";
                echo "<td align='center'> +Avulso </td>";
                echo "<td align='center'> Total Geral </td>";

            }else{

                if ($login_fabrica==20) {
                    echo "<td align='center'>";
                    fecho("total.cortesia",$con,$cook_idioma);
                    echo "</td>\n";
                    echo "<td align='center'>";
                    fecho("total.geral",$con,$cook_idioma);
                    echo "</td>\n";
                } else {

                    if ($login_fabrica <> 45 && !isset($novaTelaOs)) {
                        echo ($login_fabrica == 11) ? "<td align='center' nowrap>+ AVULSO</td>\n" : "<td align='center'>TOTAL</td>\n";
                    }

                }

                if(!isset($novaTelaOs)){
                    echo ($login_fabrica==11) ? "<td align='center'>TOTAL</td>\n" : "<td align='center' nowrap>+ AVULSO</td>\n";
                    echo (in_array($login_fabrica,array(50,30,85,91,94,120,201,125,129,131,138,140,157))) ? "<td align='center' nowrap>+ KM</td>\n" : "";
                } else {
                    if (isset($fabrica_usa_valor_adicional)) {
                        echo "<td align='center'>Valor Adicional</td>";
                    }

                    if (!in_array($login_fabrica, array(147,150,151,153))) {
                        echo "<td align='center'>KM</td>";
                    }
                    if($login_fabrica == 160 or $replica_einhell){
                        echo "<td align='center'>PeÁas</td>";
                    }
                    echo ($login_fabrica != 151) ?  "<td align='center'>Avulso</td>" : "";

                    echo "<td align='center'>Total</td>";
                }

            }

        } else {
            if($login_fabrica == 128){
            echo "<td align='center' nowrap>Visita <br /> TÈcnica</td>";
            echo "<td align='center' nowrap>Valor KM</td>";
            echo "<td align='center'>TOTAL</td>";
            }else{
                echo "<td align='center' nowrap>AVULSO</td>\n";
                echo "<td align='center'>TOTAL NF</td>\n";
            }
        }

        if ($login_fabrica == 45) {
            echo "<td align='center' nowrap>TOTAL GERAL</td>\n";
        }

        if (!in_array($login_fabrica, array(99, 142, 145))) {

            echo "<td align='center'";

            echo (!in_array($login_fabrica,array(11,15,45,50,81,114,115,116,117,120,201,121,123,124,125,126,127,128,129,131,134,136,137,138,139,140,141,142,144,151,152,153,157,160,180,181,182)) and !$replica_einhell) ? (($login_fabrica == 146) ? "nowrap>" : "nowrap>(*)") : ">Data de Pagamento";

            if ($login_fabrica == 20 or $login_fabrica == 5) {
                fecho("previsao",$con,$cook_idioma);
                echo "<br/>";
                fecho ("de.pagamento", $con, $cook_idioma);
            } else {

                if(!in_array($login_fabrica,array(11,15,45,50,81,114,115,116,117,120,201,121,123,124,125,126,127,128,129,131,134,136,137,138,139,140,141,142,144,151,152,153,157,160,180,181,182)) and !$replica_einhell){

                    if(in_array($login_fabrica, array(104,146))){
                        echo "Data Baixa";
                    }else{
                        fecho("previsao",$con,$cook_idioma);
                    }
                }
            }
            echo "</td>\n";

        }

        if ($login_fabrica == 5) {
            echo "<td align='center' nowrap>";
            fecho("nf.autorizacao",$con,$cook_idioma);
            echo "</td>\n";
        }

        if ($login_fabrica == 45 or $login_fabrica ==50 or $login_fabrica ==15) {
            echo "<td align='center' nowrap>";
            fecho("previsao",$con,$cook_idioma);
            echo "<br/>";
            fecho ("de.pagamento", $con, $cook_idioma);
            echo "</td>\n";
        }

        if ($login_fabrica==20) {
            echo "<td align='center'>";
            fecho("n.f..m..de.obra",$con,$cook_idioma);
            echo "</td>\n";
            echo "<td align='center'>";
            fecho("n.f..remessa",$con,$cook_idioma);
            echo "</td>\n";
            echo "<td align='center'>";
            fecho("entrega.transportadora",$con,$cook_idioma);
            echo "</td>\n";
        }

        if ($login_fabrica == 19) echo "<td align='center' nowrap>PAGAMENTO</td>\n";
        if ($login_fabrica == 120 or $login_fabrica == 201) echo "<td align='center' nowrap>NF do Extrato</td>\n";
        if ($login_fabrica == 151) echo "<td align='center' nowrap>NF de ServiÁo</td>\n";
        echo "<td align='center' colspan='4'>";
        fecho("acoes",$con,$cook_idioma);
        echo "</td>\n";
        echo "</tr>\n";

        $total_pendencia = 0;

        if ($login_fabrica == 51) {
             if ($extrato > 640885) {
                $campo_extrato_dev = "fi.extrato_devolucao ";
                $join_extrato = " JOIN tbl_extrato ON fi.extrato_devolucao = tbl_extrato.extrato AND tbl_extrato.fabrica = $login_fabrica ";
             } else {
                $campo_extrato_dev = "fi.extrato_devolucao ";
                $join_extrato = " JOIN tbl_extrato ON f.extrato_devolucao = tbl_extrato.extrato AND tbl_extrato.fabrica = $login_fabrica ";
             }
        } else {
            $campo_extrato_dev = "fi.extrato_devolucao ";
            $join_extrato = " JOIN tbl_extrato ON fi.extrato_devolucao = tbl_extrato.extrato AND tbl_extrato.fabrica = $login_fabrica ";
            if($login_fabrica == 24) {
                $join_extrato .= " AND tbl_extrato.data_geracao::date >='2013-09-30' ";
            }

            if ($login_fabrica == 114 or $login_fabrica == 125) {
                $join_extrato_sub_select = "JOIN tbl_extrato ON tbl_extrato.extrato = ff.extrato_devolucao AND tbl_extrato.fabrica = $login_fabrica";
            }
        }

        if (in_array($login_fabrica,array(6,24,35,50,81,85,90,91,94,99,106,114,115,116,117,120,201,121,123,124,125,126,127,128,129,131,134,136,138,139,140,141,142,144,145,146,147,151,152,153,157,160,180,181,182)) or ($login_fabrica  == 51 and $extrato < '839297') || isset($usaLGR) || $replica_einhell) {

            if($login_fabrica == 90){
                $peca_obri_cond = " AND ((tbl_extrato.data_geracao::date < '2013-02-01' AND tbl_peca.devolucao_obrigatoria is true) OR tbl_extrato.data_geracao::date >= '2013-02-01') ";
        }else{
        if($login_fabrica == 6){
                $devolucaoEstoqueFabrica = "\"devolucao_estoque_fabrica\":\"t\"";
                $peca_obri_cond = " AND (tbl_peca.produto_acabado IS TRUE  OR (tbl_peca.devolucao_obrigatoria = 't' AND (tbl_peca.parametros_adicionais is null or parametros_adicionais !~* '$devolucaoEstoqueFabrica')) OR (tbl_peca.parametros_adicionais like '%".$devolucaoEstoqueFabrica."%' AND tbl_peca.devolucao_obrigatoria = 'f'))";
        } else if (in_array($login_fabrica, array(35,114,126,129,131,134,138,141,144,145,146,147,151,152,157,180,181,182))  || isset($usaLGR)) {
                    $peca_obri_cond = " AND (fi.devolucao_obrig is true or tbl_peca.produto_acabado)";
                } else {
                    $peca_obri_cond = " AND tbl_peca.devolucao_obrigatoria is true ";
                }
            }

            if ($login_fabrica == 146) {
                $verifica_lgr_ressarcimento = "OR ((
                                SELECT COUNT(tbl_os_troca.os)
                                FROM tbl_os
                                INNER JOIN tbl_os_extra ON tbl_os_extra.os = tbl_os.os
                                INNER JOIN tbl_os_troca ON tbl_os_troca.os = tbl_os.os
                                WHERE tbl_os.fabrica = 146
                                AND tbl_os.posto = 6359
                                AND tbl_os.nota_fiscal = fi.nota_fiscal_origem
                                AND tbl_os_extra.extrato = fi.extrato_devolucao
                                AND tbl_os_troca.ressarcimento IS TRUE
                                AND tbl_os_troca.peca = fi.peca
                            ) > 0 AND fd.faturamento IS NULL)";
            }

            $sqlDev = "SELECT DISTINCT $campo_extrato_dev,
                            fc.nota_fiscal,
                            fc.faturamento
                      FROM tbl_faturamento_item fi
                      JOIN tbl_faturamento f USING(faturamento)
                      JOIN tbl_peca USING(peca)
                      $join_extrato
                      LEFT JOIN tbl_faturamento_item fid
                            ON fi.peca = fid.peca
                            AND fi.extrato_devolucao = fid.extrato_devolucao
                            AND fi.faturamento <> fid.faturamento
                            AND fid.nota_fiscal_origem = f.nota_fiscal
                      LEFT JOIN tbl_faturamento fd
                            ON fd.faturamento = fid.faturamento
                            AND fd.distribuidor = f.posto
                            AND fd.fabrica ".(($telecontrol_distrib) ? "IN (10, $login_fabrica)" : " = $login_fabrica")."
                      LEFT JOIN (
                            SELECT
                                DISTINCT devolucao_concluida,
                                ff.extrato_devolucao,
                                nota_fiscal,
                                ff.faturamento
                            FROM tbl_faturamento
                            JOIN tbl_faturamento_item ff USING(faturamento)
                            $join_extrato_sub_select
                            WHERE tbl_faturamento.fabrica ".(($telecontrol_distrib) ? "IN (10, $login_fabrica)" : " = $login_fabrica")."
                            AND tbl_faturamento.distribuidor = $login_posto
                      ) fc ON fi.extrato_devolucao = fc.extrato_devolucao
                      WHERE f.fabrica ".(($telecontrol_distrib) ? "IN (10, $login_fabrica)" : " = $login_fabrica")."
                      AND f.posto = $login_posto
                      AND (
                            (
                                fd.distribuidor = $login_posto
                                AND tbl_extrato.admin_lgr IS NULL
                                AND fd.devolucao_concluida IS NOT TRUE
                            )
                            OR
                            fc.faturamento IS NULL
                            $verifica_lgr_ressarcimento
                      )
                      AND fi.extrato_devolucao IS NOT NULL
                      AND tbl_peca.fabrica = $login_fabrica
                      AND fc.devolucao_concluida IS NOT TRUE
                      AND (
                            f.cfop LIKE '59%'
                            OR f.cfop LIKE '69%'
                            OR fi.cfop ILIKE '59%'
                            OR fi.cfop ILIKE '69%'
                      )
                      $peca_obri_cond
              ORDER BY 1";
        #echo nl2br($sqlDev);
            $resDev = pg_query($con,$sqlDev);
            if(pg_num_rows($resDev) > 0){
                $extrato_dev = pg_result($resDev,0,extrato_devolucao);
                $nf_dev = pg_result($resDev,0,nota_fiscal);
            }
        }


        for ($i = 0 ; $i < pg_num_rows ($res); $i++){
        $total_extrato = pg_fetch_result($res,$i,total);
            $xmao_de_obra            = 0;
            $posto                   = trim(pg_fetch_result($res,$i,posto));
            $posto_codigo            = trim(pg_fetch_result($res,$i,codigo_posto));
            $posto_nome              = trim(pg_fetch_result($res,$i,nome));
            $extrato                 = trim(pg_fetch_result($res,$i,extrato));
            $data_geracao            = trim(pg_fetch_result($res,$i,data_geracao));
            $liberado                = trim(pg_fetch_result($res,$i,liberado));
            $pedido_via_distribuidor = trim(pg_fetch_result($res,$i,pedido_via_distribuidor));
            $data_extrato            = trim(pg_fetch_result($res,$i,data_extrato));
            $mao_de_obra             = trim(pg_fetch_result($res,$i,mao_de_obra));
            $mao_de_obra_postos      = trim(pg_fetch_result($res,$i,mao_de_obra_postos));
            $extra_mo                = trim(pg_fetch_result($res,$i,extra_mo));
            $adicional               = trim(pg_fetch_result($res,$i,adicional));
            $pecas                   = trim(pg_fetch_result($res,$i,pecas));

            if(in_array($login_fabrica,array(30,35,50,90,94,120,201,128,131,138,140,142,146,145,149,152,157,160,180,181,182)) or $replica_einhell){
                $deslocamento_km         = trim(pg_fetch_result($res,$i,deslocamento_km));
            }
            if(in_array($login_fabrica,array(91,125))){
                $valor_km         = trim(pg_fetch_result($res,$i,valor_km));
            }
            $extra_pecas             = trim(pg_fetch_result($res,$i,extra_pecas));
            $estado                  = trim(pg_fetch_result($res,$i,estado));
            $avulso                  = trim(pg_fetch_result($res,$i,avulso));
            $protocolo               = trim(pg_fetch_result($res,$i,protocolo));
            $admin_lgr               = trim(pg_fetch_result($res,$i,admin_lgr)); // HD 6000
            $data_pagamento          = trim(pg_fetch_result($res,$i,data_pagamento));
            $nf_autorizacao          = trim(pg_fetch_result($res,$i,nf_autorizacao));//HD 9982
            $nota_fiscal_devolucao   = trim(pg_fetch_result($res,$i,nota_fiscal_devolucao))   ;
            $nota_fiscal_mao_de_obra = trim(pg_fetch_result($res,$i,nota_fiscal_mao_de_obra)) ;
            $data_entrega_transportadora = trim(pg_fetch_result($res,$i,data_entrega_transportadora)) ;
            $previsao_pagamento      = trim(pg_fetch_result($res,$i,exportado)) ;
            $previsao_pagamento_extrato= trim(pg_fetch_result($res,$i,previsao_pagamento)) ;
            $emissao_mao_de_obra     = trim(pg_fetch_result($res,$i,emissao_mao_de_obra)) ;
            $valor_adicional        = trim(pg_fetch_result($res,$i,valor_adicional)) ;
            $msg_mes_anterior= "";
            $qtde_devolucao  = "";
            $devolveu_pecas = "";
            if($login_fabrica==6){
                $pecas_devolvidas        = trim(pg_fetch_result($res,$i,pecas_devolvidas)) ;
            }
            if($login_fabrica ==45) { // HD 50104
                $total_geral = trim(pg_fetch_result($res,$i,total)) ;
            }
            //hd 39502
            if ($login_fabrica==20) {
                $total_cortesia      = trim(pg_fetch_result($res,$i,total_cortesia)) ;
            }

            if(strtotime($data_extrato) < strtotime('2013-02-01')){
                $data_corte_extrato = 1;
            }else{
                $data_corte_extrato = "";
            }

            if(in_array($login_fabrica, array(142))){
                $pecas = 0;
            }

            if($extrato_anterior == $extrato) continue;

            $nao_conta_total = '';

            $sqlX = "SELECT TO_CHAR (tbl_extrato_financeiro.previsao,'DD/MM/YYYY') FROM tbl_extrato_financeiro WHERE extrato = $extrato";
            $resX = pg_query ($con,$sqlX);
            $previsao = trim(@pg_fetch_result($resX,0,0));

            $sqlX = "SELECT TO_CHAR (tbl_extrato_financeiro.pagamento,'DD/MM/YYYY') FROM tbl_extrato_financeiro WHERE extrato = $extrato";
            $resX = pg_query ($con,$sqlX);
            $pagamento = trim(@pg_fetch_result($resX,0,0));

            if ($login_fabrica == 11){
                $sql = "SELECT  CASE WHEN data_geracao > '2008-08-01'::date THEN '1' ELSE '0' END
                        FROM tbl_extrato
                        WHERE extrato = $extrato ";
                $resX = pg_query ($con,$sql);
                $verificacao = pg_fetch_result ($resX,0,0);
            }
            if ($login_fabrica == 51){
                $sql = "SELECT  CASE WHEN data_geracao > '2008-10-30'::date THEN '1' ELSE '0' END
                        FROM tbl_extrato
                        WHERE extrato = $extrato ";
                $resX = pg_query ($con,$sql);
                $verificacao = pg_fetch_result ($resX,0,0);
            }

            //aqui comeca a o lgr

            if ((in_array($login_fabrica,array(6,24,35,50,81,85,90,91,94,99,106,114,115,116,117,120,201,121,123,124,125,126,127,128,129,131,134,136,138,139,140,141,142,144,145,146,147,151,152,153,157,160,180,181,182)) or ($login_fabrica  == 51 and $extrato < '839297') || isset($usaLGR) || $replica_einhell) AND ($extrato >= $extrato_dev and !empty($extrato_dev))) {

                $msg_notas = "";
                $msg_mes_anterior = "";

                ## VerificÁ„o do MÍs Anterior
                ## Verifica se tem extrato no mÍs anterior e se foi digitado as notas de devoluÁ„o
                    ## V·lido apartir de data_geracao > '2007-12-01'

                    $novo_processo_lgr= "";
                    if (!empty($extrato) AND $login_fabrica == 24) {
                        $sql = "SELECT CASE WHEN data_geracao::date >= '2013-09-30'::date THEN 1 ELSE 0 END AS sim_nao
                                FROM   tbl_extrato
                                WHERE  extrato = $extrato
                                and    posto   = $login_posto
                                and    fabrica = $login_fabrica";
                        $res_lgr = pg_exec($con,$sql);
                        if (@pg_numrows($res_lgr) > 0) {
                            $novo_processo_lgr = pg_result($res_lgr, 0, sim_nao);
                        }
                    }

                $data_geracao_extrato = ($novo_processo_lgr == 1) ? '2013-09-30' : '2009-10-01';
                $sqlConf = "
                            SELECT extrato,admin_lgr
                            FROM tbl_extrato
                            WHERE fabrica    = $login_fabrica
                            AND posto        = $login_posto
                            AND extrato      < $extrato
                            AND data_geracao > '$data_geracao_extrato'
                            ORDER BY data_geracao DESC
                            LIMIT 1";

                $resConf = pg_query ($con,$sqlConf);

                 //echo nl2br($sqlConf)."<br><br>";exit;

                if (pg_num_rows($resConf)>0){
                    $admin_lgr   = trim(pg_fetch_result($resConf,0,admin_lgr));
                    $lgr_extrato = trim(pg_fetch_result($resConf,0,extrato));
                    # Verifica se as notas de devolu√ß√£o do Mes anterior foi recebido pela Fabrica
                    if(!empty($extrato) and !empty($extrato_dev)){
                        if ($extrato <> $extrato_dev) {
                            $lgr_extrato = $extrato_dev;
                        }
                    }
                    //definir corte para extrato anterior, pois a nova regra verifica outro campo 09/12/2009 waldir

                    if ($login_fabrica == 51) {
                         if ($extrato > 640885) {
                            $sql_verifica = "AND tbl_faturamento_item.extrato_devolucao = $lgr_extrato ";
                         } else {
                            $sql_verifica = "AND tbl_faturamento.extrato_devolucao = $lgr_extrato ";
                         }
                    } else {
                        $sql_verifica = "AND tbl_faturamento_item.extrato_devolucao = $lgr_extrato ";
                    }



                  $sqlConf = "SELECT  DISTINCT faturamento,
                                            nota_fiscal,
                                            emissao - CURRENT_DATE AS dias_emitido,
                                            conferencia,
                                            movimento,
                                            devolucao_concluida
                                    FROM tbl_faturamento_item
                                    JOIN tbl_faturamento USING(faturamento)
                                    WHERE fabrica = $login_fabrica
                                    AND distribuidor = $login_posto
                                    $sql_verifica
                                    AND posto IS NOT NULL";
                    $resConf = pg_query ($con,$sqlConf);

                    $notas_array = array();
                    $msg_notas = "";


                    if (pg_num_rows($resConf)>0){
                        for ( $w=0; $w < pg_num_rows($resConf); $w++ ){
                            $fat_faturamento  = trim(pg_fetch_result($resConf,$w,faturamento));
                            $fat_nota_fiscal  = trim(pg_fetch_result($resConf,$w,nota_fiscal));
                            $fat_dias_emitido = trim(pg_fetch_result($resConf,$w,dias_emitido));
                            $fat_conferencia  = trim(pg_fetch_result($resConf,$w,conferencia));
                            $fat_movimento    = trim(pg_fetch_result($resConf,$w,movimento));
                            $fat_concluido    = trim(pg_fetch_result($resConf,$w,devolucao_concluida));

                            // $admin_lgr -> se a F√°brica liberou o mes anterior, deixa digitar este mes
                            // $fat_movimento != 'NAO_RETOR.' -> nao exige conferencia caso nao for conferida NF de pe√ßas nao retornaveis - HD 13450
                            if (strlen($admin_lgr)==0 AND $fat_concluido!='t' AND $fat_movimento != 'NAO_RETOR.'){
                                array_push($notas_array,$fat_nota_fiscal);
                            }
                        }
                    }

                    if (count($notas_array)>0 OR pg_num_rows($resConf)==0){

                        if (count($notas_array)>0 AND $login_fabrica <> 99){

                            #HD 174349
                            $nao_conta_total = 'sim';
                            $msg_mes_anterior = "<a href=\"$PHP_SELF?ajax=true&extrato=$lgr_extrato&status=confirmada&nf=".implode(",",$notas_array)."&height=240&width=320\"  title=\"NF n„o confirmada\" class=\"thickbox\">".traduz("extrato.bloqueado",$con,$cook_idioma)."</a><br>";

                        } else {

                            //echo "Extrato 1 - ".$lgr_extrato."<br>------------<br> ";

                            if ($login_fabrica == 51) {
                                 if ($extrato > 640885) {
                                    $sql_verifica = "AND tbl_faturamento_item.extrato_devolucao = $lgr_extrato ";
                                 } else {
                                    $sql_verifica = "AND tbl_faturamento.extrato_devolucao = $lgr_extrato ";
                                 }
                            } else {
                                $sql_verifica = "AND tbl_faturamento_item.extrato_devolucao = $lgr_extrato";
                            }

                            $sqlConf = "SELECT  faturamento
                                    FROM tbl_faturamento
                                    JOIN tbl_faturamento_item USING(faturamento)
                                    JOIN tbl_peca             USING(peca)
                                    WHERE tbl_faturamento.fabrica  ".(($telecontrol_distrib) ? "IN (10, $login_fabrica)" : " = $login_fabrica")."
                                    ".((!$telecontrol_distrib) ? " AND tbl_faturamento.distribuidor IS NULL " : "")."
                                    AND   tbl_faturamento.posto   = $login_posto
                                    $sql_verifica";

                if ($verificacao=='1' OR in_array($login_fabrica,array(6,24,35,50,81,85,91,94,99,106,114,115,116,117,120,201,121,123,124,125,126,127,128,129,131,134,136,138,140,141,142,144,145,139,146,147,151,152,153,157,160,180,181,182)) OR ($login_fabrica == 90 AND $data_corte_extrato) || isset($usaLGR) || $replica_einhell) {
                    if($login_fabrica == 6){
                             $devolucaoEstoqueFabrica = "\"devolucao_estoque_fabrica\":\"t\"";
                    $sqlConf .= " AND (tbl_peca.produto_acabado IS TRUE  OR (tbl_peca.devolucao_obrigatoria = 't' AND (tbl_peca.parametros_adicionais is null or parametros_adicionais !~* '$devolucaoEstoqueFabrica')) OR (tbl_peca.parametros_adicionais like '%$devolucaoEstoqueFabrica%' AND tbl_peca.devolucao_obrigatoria = 'f'))";

                    } else if (in_array($login_fabrica, array(35,114,125,129,131,134,136,138,141,144,145,146,147,151,152,157,180,181,182)) || isset($usaLGR) ) {

                                    $sqlConf .= "AND    (tbl_faturamento_item.devolucao_obrig IS TRUE OR tbl_peca.produto_acabado IS TRUE) ";
                                    } else {
                                        $sqlConf .= "AND    (tbl_peca.devolucao_obrigatoria IS TRUE OR tbl_peca.produto_acabado IS TRUE) ";
                                    }
                            }

                    // echo nl2br($sqlConf); echo "<br>";exit;

                            $resConf = pg_query ($con,$sqlConf);
                            if (pg_num_rows($resConf)> 0 AND $login_fabrica <> 99){
                                $nao_conta_total = 'sim';
                                $msg_mes_anterior = "<a href=\"$PHP_SELF?ajax=true&extrato=$lgr_extrato&status=anterior&height=240&width=320\"  title=\"NF n„o confirmada\" class=\"thickbox\" id='bloqueado_$i'>".traduz("extrato.bloqueado",$con,$cook_idioma)."</a>";

                            }

                        }

                    }

                }

            #Regra nova do LGR pegando no faturamento_item inicialmente para suggar Waldir 24/12/2009
                    if (in_array($login_fabrica,array(6,35,50,81,51,85,90,91,24,94,99,106,114,115,116,117,120,201,121,123,124,125,126,128,129,131,134,136,138,139,140,141,142,144,145,146,147,151,152,153,157,160,180,181,182)) || isset($usaLGR) or $replica_einhell) {


                        if ($login_fabrica == 51) {
                             if ($extrato > 640885) {
                                $sql_verifica = "AND tbl_faturamento_item.extrato_devolucao = $extrato ";
                             } else {
                                $sql_verifica = "AND tbl_faturamento.extrato_devolucao = $extrato ";
                             }
                        } else {
                            $sql_verifica = "AND tbl_faturamento_item.extrato_devolucao = $extrato ";
                        }

                        if($login_fabrica == 90){
                            if($data_corte_extrato){
                                $peca_obri_cond = " AND tbl_peca.devolucao_obrigatoria is true ";
                            }else{
                                $peca_obri_cond = "";
                            }
                        }else if($login_fabrica == 6){
                            $devolucaoEstoqueFabrica = "\"devolucao_estoque_fabrica\":\"t\"";

                $sqlConf .= " AND (tbl_peca.produto_acabado IS TRUE  OR (tbl_peca.devolucao_obrigatoria = 't' AND (tbl_peca.parametros_adicionais is null or parametros_adicionais !~* '$devolucaoEstoqueFabrica')) OR (tbl_peca.parametros_adicionais like '%$devolucaoEstoqueFabrica%' AND tbl_peca.devolucao_obrigatoria = 'f'))";
            }else if (in_array($login_fabrica, array(35,114,129,131,134,136,138,141,144,145,146,147,151,152,157,180,181,182)) || isset($usaLGR) ) {

                            $peca_obri_cond = " AND (tbl_faturamento_item.devolucao_obrig is true or tbl_peca.produto_acabado) ";
                        } else {
                            $peca_obri_cond = " AND tbl_peca.devolucao_obrigatoria is true ";
                        }

                        $sqlLgr = "SELECT count(*)
                            FROM tbl_faturamento_item
                            JOIN tbl_faturamento USING(faturamento)
                            JOIN tbl_peca             USING(peca)
                            WHERE  (tbl_faturamento.fabrica           = $login_fabrica or tbl_faturamento.fabrica = 10)
                            AND tbl_peca.fabrica = $login_fabrica
                            $sql_verifica
                            AND    (tbl_faturamento.distribuidor IS NULL or tbl_faturamento.distribuidor = 4311 or tbl_faturamento.distribuidor = 376542)
                            AND     (tbl_faturamento.cfop LIKE '59%' OR tbl_faturamento.cfop LIKE '69%'  or tbl_faturamento_item.cfop ilike '59%' or tbl_faturamento_item.cfop ilike '69%')
                            $peca_obri_cond";
                    }else {
                        $sqlLgr = "SELECT count(*)
                            FROM tbl_faturamento_item
                            JOIN tbl_faturamento USING(faturamento)
                            JOIN tbl_peca             USING(peca)
                            WHERE  tbl_faturamento.fabrica           = $login_fabrica
                            AND    tbl_faturamento_item.extrato_devolucao = $extrato
                            AND    (tbl_faturamento.distribuidor IS NULL)
                            AND     (tbl_faturamento.cfop LIKE '59%' OR tbl_faturamento.cfop LIKE '69%')
                            AND tbl_peca.devolucao_obrigatoria is true";
                    }
                $resLGR = pg_query ($con,$sqlLgr);

                $qtde_devolucao = trim(@pg_fetch_result($resLGR,0,0));
                $devolveu_pecas = "nao";

                $sqlPosto = "SELECT posto_fabrica from tbl_fabrica where fabrica = $login_fabrica";
                $resPosto = pg_query($con,$sqlPosto);

                $aux_posto_fabrica = pg_fetch_result($resPosto,0,0);

                if (strlen($aux_posto_fabrica)>0) {
                    $posto_da_fabrica = $aux_posto_fabrica;
                }

                if(!isset($telecontrol_distrib)) {
                     $sql_distrib = "AND distribuidor = $login_posto";
                }

                $sqlLgr = "SELECT   tbl_faturamento_item.extrato_devolucao,
                                emissao,
                                nota_fiscal
                    FROM tbl_faturamento_item
                    JOIN tbl_faturamento USING(faturamento)
                    WHERE posto             in ($posto_da_fabrica)
                    $sql_distrib
                    $sql_verifica
                    AND   fabrica           = $login_fabrica
                    AND   (tbl_faturamento.obs <> 'DevoluÁ„o de Ressarcimento' or tbl_faturamento.obs isnull)
                    AND  cancelada          IS NULL";
                $resLGR = pg_query ($con,$sqlLgr);

                if (pg_num_rows($resLGR)>0){
                    $devolveu_pecas = "sim";
                }

                /* Trecho retirado, pois estava bloqueando extratos sem peÁas de devoluÁ„o - HD 841679

                $sqlProduto = "SELECT  DISTINCT
                        tbl_produto.referencia                       AS produto_referencia
                FROM tbl_os
                JOIN tbl_os_troca USING(os)
                JOIN tbl_os_extra   USING(os)
                JOIN tbl_extrato    ON tbl_extrato.fabrica = tbl_os.fabrica and tbl_extrato.posto = tbl_os.posto and tbl_extrato.extrato = tbl_os_extra.extrato
                LEFT JOIN tbl_admin            ON tbl_os.troca_garantia_admin = tbl_admin.admin
                LEFT JOIN tbl_produto          ON tbl_os.produto              = tbl_produto.produto
                WHERE tbl_extrato.extrato   = $extrato
                AND  tbl_os.fabrica        = $login_fabrica
                AND  tbl_os.posto          = $login_posto
                AND  tbl_os_troca.ressarcimento   IS TRUE
                AND  tbl_os.troca_garantia  IS TRUE";
                $resProduto = pg_query ($con,$sqlProduto);
                if(pg_num_rows($resProduto) > 0){
                    $produto_referencia = pg_fetch_result($resProduto,0,'produto_referencia');
                    $sql_peca = "SELECT peca FROM tbl_peca where fabrica = $login_fabrica and referencia = '$produto_referencia' and produto_acabado is true";

                    $res_peca = pg_query($con,$sql_peca);
                    if (pg_num_rows($res_peca)>0) {
                        $peca = pg_fetch_result($res_peca,0,0);

                        $sqlLgr = "SELECT   tbl_faturamento_item.extrato_devolucao,
                                    emissao,
                                    nota_fiscal
                        FROM tbl_faturamento_item
                        JOIN tbl_faturamento USING(faturamento)
                        WHERE distribuidor      = $login_posto
                        AND   posto             in ($posto_da_fabrica)
                        $sql_verifica
                        AND   fabrica           = $login_fabrica
                        AND   tbl_faturamento.obs ='DevoluÁ„o de Ressarcimento'
                        AND   tbl_faturamento_item.peca = $peca
                        AND  cancelada          IS NULL";

                        $resLGR = pg_query($con,$sqlLgr);
                        if (pg_num_rows($resLGR) == 0){
                            if($login_fabrica == 24) { # HD 390021
                                $devolveu_pecas = "nao";
                                $qtde_devolucao = 1;
                            }
                        }
                    }
                }
                */

                # Verifica tem nota recebida PARCIAL

                $sqlConf = "SELECT  faturamento,
                                emissao,
                                nota_fiscal
                    FROM tbl_faturamento_item
                    JOIN tbl_faturamento USING(faturamento)
                    WHERE distribuidor      = $login_posto
                    AND   posto             in ($posto_da_fabrica)
                    $sql_verifica
                    AND   fabrica           = $login_fabrica
                    AND   devolucao_concluida IS NOT TRUE
                    AND   conferencia       IS NOT NULL
                    AND   cancelada         IS NULL";

                //echo nl2br($sqlConf);
                $resConf = pg_query ($con,$sqlConf);
                $notas_array_parcial = array();
                if (pg_num_rows($resConf)>0){
                    for ( $w=0; $w < pg_num_rows($resConf); $w++ ){
                        $nf_tmp = trim(pg_fetch_result($resConf,$w,nota_fiscal));
                        array_push($notas_array_parcial,$nf_tmp);
                    }
                }

                # Verifica se foi recebida pela F·brica

                    $sqlConf = "SELECT  faturamento,
                                    emissao,
                                    nota_fiscal
                        FROM tbl_faturamento_item
                        JOIN tbl_faturamento USING(faturamento)
                        WHERE distribuidor      = $login_posto
                        AND   posto             IN ($posto_da_fabrica)
                        $sql_verifica
                        AND   fabrica           = $login_fabrica
                        AND   devolucao_concluida IS NOT TRUE
                        AND   cancelada         IS NULL
                        AND   emissao - CURRENT_DATE >15";

                $resConf = pg_query ($con,$sqlConf);
                $notas_array = array();
                if (pg_num_rows($resConf)>0){
                    for ( $w=0; $w < pg_num_rows($resConf); $w++ ){
                        $nf_tmp = trim(pg_fetch_result($resConf,$w,nota_fiscal));
                        array_push($notas_array,$nf_tmp);
                    }
                }
            }

            if (strlen($adicional) == 0) $adicional = 0;

            # soma valores
            if ($tipo_posto == "P") {
                $xmao_de_obra += $mao_de_obra_postos;
                $xvrmao_obra   = $mao_de_obra_postos;
            }else{
                $xmao_de_obra += $mao_de_obra;
                $xvrmao_obra   = $mao_de_obra;
            }

            if ($xvrmao_obra == 0)  $xvrmao_obra   = $mao_de_obra;
            if ($xmao_de_obra == 0) $xmao_de_obra += $mao_de_obra;

            $total = 0 ;

            $total += ($xmao_de_obra + $pecas);

            if ($login_fabrica == 94 OR $login_fabrica == 128) {

                $total += $deslocamento_km;

        }

        if($login_fabrica == 128 OR $login_fabrica == 140){
        $total += $valor_adicional;
        }

            if($login_fabrica == 90) {
                $sqld = "SELECT  total
                        FROM tbl_extrato
                        WHERE extrato = $extrato";
                $resd = pg_query($con,$sqld);
                if(pg_num_rows($res) > 0){
                    $total = pg_fetch_result($resd,0,0);
                }
            }

            if (strlen($data_pagamento)==0 and empty($nao_conta_total)){
                if($qtde_devolucao == 0 or $devolveu_pecas != "nao" ){
                    $total_pendencia += $total;
                }
            }

            $data_geracao;

            $cor = ($i % 2 == 0) ? '#F1F4FA' : "#F7F5F0";
            $btn = ($i % 2 == 0) ? 'azul' : 'amarelo';

            ##### LAN«AMENTO DE EXTRATO AVULSO - INÕCIO #####
            if (strlen($extrato) > 0) {
                $sql = "SELECT count(*) as existe
                        FROM   tbl_extrato_lancamento
                        WHERE  extrato = $extrato
                        and    posto   = $login_posto
                        and    fabrica = $login_fabrica";
                $res_avulso = pg_query($con,$sql);

                if (@pg_num_rows($res_avulso) > 0) {
                    if (@pg_fetch_result($res_avulso, 0, existe) > 0) $cor = "#FFE1E1";
                }

                #### HD-2163607 INICIO / MONTEIRO ####
                if($login_fabrica == 85){
                    $sqlComentarios = "SELECT
                            tbl_os_extra.obs_adicionais
                            FROM tbl_os_extra
                            JOIN tbl_os ON tbl_os_extra.os = tbl_os.os
                            WHERE tbl_os_extra.extrato = $extrato
                            AND tbl_os.posto = $login_posto
                            AND tbl_os.fabrica = $login_fabrica";
                    $resComentarios = pg_query($con, $sqlComentarios);

                    if (@pg_num_rows($resComentarios) > 0) {
                        $comentarios = pg_fetch_result($resComentarios, 0, 'obs_adicionais');

                        if(strlen($comentarios) > 0 ){
                            $cor = "#FFE1E1";
                            $btn = 'amarelo';
                        }
                    }
                }
                #### HD-2163607 FIM / MONTEIRO ####

            }

            ##### LAN«AMENTO DE EXTRATO AVULSO - FIM #####





            //HD 15606
            if($login_fabrica == 6 and $pecas_devolvidas=='t'){
                $cor = '#33CCFF';
            }
            #HD 22758
            if ($login_fabrica==24 AND $total_pendencia>0 AND strlen($data_pagamento)>0 AND $imprimir_total_pendencia<>'1'){
                $imprimir_total_pendencia = '1';
                echo "<tr class='table_line'>\n";
                echo "<td align='right' colspan='5'>Total Aguardando Pagamento:</td>\n";
                echo "<td align='left' colspan='5'><b>".number_format($total_pendencia,2,",",".")."</b></td>\n";
                echo "</tr>\n";
            }

            echo "<tr class='table_line' style='background-color: $cor;'>\n";

            if ($login_fabrica == 2){
                echo "<td align='left' style='padding-left:7px;'>\n";
                echo "<a href='nf_dynacom_consulta.php?extrato=$extrato' target='_blank'>$extrato</a>\n";
                echo " - <a href='nf_servico_dynacom_consulta.php?extrato=$extrato' target='_blank'>NF</a>\n";
                echo "</td>\n";
            }else{
                echo "<td align='left' style='padding-left:7px;padding-right:7px;'>";
                echo ($login_fabrica == 1) ? $protocolo : (($login_fabrica <> 19 ) ?$extrato : "");
                echo "</td>\n";
            }
            if ($login_fabrica == 19) {
                echo "<td align='left' style='padding-left:7px;padding-right:7px;'>";
                echo $protocolo;
                echo "</td>\n";
            }

            if ($login_fabrica <> 11) {
                echo "<td align='left' nowrap><acronym title='$posto_codigo - $posto_nome'>$posto_codigo - " . substr($posto_nome,0,20) . "</acronym></td>\n";
            }

            if ($login_fabrica == 3 AND $tipo_posto == "D") {
                echo "<td align='center'><a href='extrato_distribuidor.php?data=$data_extrato'>$data_geracao</a></td>\n";
            } else {
                echo (in_array($login_fabrica,array(7,11,25,51,45))) ? "<td align='center'>$liberado</td>\n" : "<td align='center'>$data_geracao</td>\n";
            }

            if($login_fabrica == 129){
                 echo "<td align='center'>$liberado</td>";
            }

            if($login_fabrica == 104){
                echo "<td align='center'>";
                echo mostraMarcaExtrato($extrato);
                echo    "</td>";
            }

            if ($login_fabrica == 51) {

                $sql3 = "SELECT SUM(valor) as total_os_sedex FROM tbl_extrato_lancamento WHERE fabrica = $login_fabrica AND lancamento in (96) AND extrato = $extrato;";
                $res3 = pg_query($con,$sql3);
                if (@pg_num_rows($res3) > 0) {
                    $total_os_sedex = pg_fetch_result($res3, 0, total_os_sedex);
                }

                #O PA n„o pode ver o crÈdito de OSs recusadas que s„o lanÁados para acertar o extrato conforme nota feita pelo PA
                #Na confer√™ncia de Lote √© feito a recusa e √© lan√ßado um d√©btio para o pr√≥ximo extrato e um cr√©dito no atual para manter o mesmo valor da nota emitida.
                $sql3 = "SELECT SUM(valor) as total_os_recusada FROM tbl_extrato_lancamento WHERE fabrica = $login_fabrica AND lancamento in (121) AND extrato = $extrato;";
                $res3 = pg_query($con,$sql3);
                if (@pg_num_rows($res3) > 0) {
                    $total_os_recusada = pg_fetch_result($res3, 0, total_os_recusada);

                    $xvrmao_obra = $xvrmao_obra + $total_os_recusada;
                    $total       = $total + $total_os_recusada;
                    $avulso      = $avulso - $total_os_recusada - $total_os_sedex;
                    $total       = $total + $avulso;
                }

            }

            # HD 119143 adicionei fabrica 43 'nova computadores' ‡ essa regra
            #echo "<br />qtde_devolucao ".$qtde_devolucao."<br />";
            #echo "devolveu_pecas ".$devolveu_pecas."<br />";
            #echo "msg_mes_anterior ".strlen($msg_mes_anterior);

            #if $qtde_devolucao > 0 && $devolveu_pecas == "nao" && !strlen($msg_mes_anterior)

            if (isset($fabrica_usa_lgr_ressarcimento) && !($qtde_devolucao > 0 && $devolveu_pecas == "nao" && !strlen($msg_mes_anterior))) {
                $sqlOsRessarcimento = "SELECT tbl_os.os, tbl_os.nota_fiscal
                                       FROM tbl_extrato
                                       INNER JOIN tbl_os_extra ON tbl_os_extra.extrato = tbl_extrato.extrato
                                       INNER JOIN tbl_os ON tbl_os.os = tbl_os_extra.os AND tbl_os.fabrica = {$login_fabrica} AND tbl_os.posto = {$login_posto}
                                       INNER JOIN tbl_os_troca ON tbl_os_troca.os = tbl_os.os
                                       WHERE tbl_extrato.extrato = {$extrato}
                                       AND tbl_os_troca.ressarcimento IS TRUE";
                $resOsRessarcimento = pg_query($con, $sqlOsRessarcimento);

                $lgrRessarcimento = false;

                if (pg_num_rows($resOsRessarcimento) > 0) {
                    $nota_fiscal_os_ressarcimento = pg_fetch_result($resOsRessarcimento, 0, "nota_fiscal");

                    $sqlLgrRessarcimento = "SELECT tbl_faturamento.faturamento
                                            FROM tbl_faturamento_item
                                            INNER JOIN tbl_faturamento ON tbl_faturamento.faturamento = tbl_faturamento_item.faturamento AND tbl_faturamento.fabrica = {$login_fabrica}
                                            WHERE tbl_faturamento_item.extrato_devolucao = {$extrato}
                                            AND tbl_faturamento_item.nota_fiscal_origem = '{$nota_fiscal_os_ressarcimento}'";
                    $resLgrRessarcimento = pg_query($con, $sqlLgrRessarcimento);

                    if (!pg_num_rows($resLgrRessarcimento)) {
                        $lgrRessarcimento = true;
                    }
                }
            }

            if (((in_array($login_fabrica,array(6,35,50,81,85,90,91,94,99,106,114,115,116,117,120,201,121,123,124,125,126,127,128,129,131,134,136,138,139,140,142,141,144,145,146,147,151,152,153,157,160,180,181,182)) or ($login_fabrica == 51 and $extrato < '839297')
                or ($login_fabrica == 24 and $novo_processo_lgr == 1) || isset($usaLGR)) AND $qtde_devolucao > 0 AND $devolveu_pecas == "nao"
                AND strlen($msg_mes_anterior) == 0) || (isset($fabrica_usa_lgr_ressarcimento) && $lgrRessarcimento === true) or $replica_einhell) {

                echo "<td></td>";
                echo "<td></td>";
                echo "<td></td>";
                echo "<td></td>";
                if($inf_valores_adicionais){
                    echo "<td></td>";
                }
                if($login_fabrica == 120 or $login_fabrica == 201) {
                    echo "<td></td>";
                    echo "<td></td>";
                    $sqlServico = "SELECT * FROM tbl_extrato_extra WHERE extrato = $extrato AND nota_fiscal_mao_de_obra notnull";
                    $resServico = pg_query($con,$sqlServico);
                    if(pg_num_rows($resServico) == 0){
                        echo "<td align='center'><a href='extrato_posto_nf.php?extrato=$extrato'>Preencha a NF<br> do Extrato <br>Clique Aqui</a></TD>";
                    }else{
                        echo "<td align='center'><a href='extrato_posto_nf.php?extrato=$extrato'>NF do Extrato <br>Preenchida</a></TD>";
                    }
                }
                echo "<td><input type='hidden' id='extrato_sem_nota' value='$extrato'></td>";

                $texto_nf = ($login_fabrica == 120 or $login_fabrica == 201) ? "Preencha a NF<br> do LGR<br> Clique Aqui": "Preencha a NF<br>Clique Aqui";
                $colspan = (in_array($login_fabrica, array(142,145,146,157))) ? "colspan='4'" : "";
                echo "<td align='center' $colspan>
                <a href='extrato_posto_devolucao_lgr_novo_lgr.php?extrato=$extrato' title='Clique aqui para preencher a nota fiscal de devoluÁ„o. ApÛs a devoluÁ„o da NF, poder„o ser visualizado a M„o de Obra' id='preencha' rel='$i'>$texto_nf</a></td>\n";
            } else if ((in_array($login_fabrica,array(2,6,7,11,25,50,81,85,90,91,94,99,106,114,115,116,117,120,201,121,125,153, 160)) or ($login_fabrica == 51 and $extrato < '839297') or ($login_fabrica == 24 and $novo_processo_lgr == 1) || $replica_einhell) AND count($notas_array) > 0 AND strlen($admin_lgr) == 0) {
                echo "<td align='center' colspan='7' bgcolor='#FF9E5E'>\n";
                echo "<a href=\"$PHP_SELF?ajax=true&extrato=$extrato&status=confirmada&nf=".implode(",",$notas_array)."&height=240&width=320\"  title=\"Nota Fiscal n„o confirmada\" class=\"thickbox\">ATEN«√O</a>";
                echo "</td>";
            } else if( (in_array($login_fabrica,array(2,7,11,25))) AND count($notas_array_parcial)>0 AND strlen($admin_lgr)==0 ) {
                echo "<td align='center' colspan='7' bgcolor='#FF9E5E'>\n";
                echo "<a href=\"$PHP_SELF?ajax=true&extrato=$extrato&status=parcial&nf=".implode(",",$notas_array_parcial)."&height=240&width=320\"  title=\"Nota Fiscal Parcial\" class=\"thickbox\">ATEN«√O</a>";
                echo "</td>";

            } else if ((in_array($login_fabrica,array(2,6,7,11,25,35,43,50,51,81,85,90,91,94,99,106,114,115,116,117,120,201,121,125,131,138,139,141,142,144,145,146,147,151,152,153,157,160,180,181,182)) || isset($usaLGR) or ($login_fabrica == 24 and $novo_processo_lgr == 1) or $replica_einhell) AND strlen($msg_mes_anterior)>0){
                echo "<td align='center' colspan='7' bgcolor='#FF9E5E'>\n";
                echo $msg_mes_anterior;
                echo "</td>";

            } else {

                //HD 221727: Acrescentado a pedido do Samuel
                if ($login_fabrica == 51 and $i > 2) {
                    break;
                }

                $aguardando_nf = "";

                if ($login_fabrica == 19) {

                    $xvrmao_obra = pg_fetch_result($res, $i, 'extra_mo');
                    $pecas       = pg_fetch_result($res, $i, 'extra_pecas');
                    $total       = $pecas + $xvrmao_obra ;

                    $sql = "SELECT tbl_extrato.extrato
                              FROM tbl_extrato
                             WHERE nf_recebida IS NOT TRUE
                               AND extrato < $extrato
                               AND posto = $login_posto
                               AND fabrica = $login_fabrica
                               AND aprovado IS NOT NULL
                             ORDER BY extrato ";

                    $resX = pg_query ($con,$sql);

                }

                if ($aguardando_nf == 't') {

                    echo "<td align='left' bgcolor='#9999aa' colspan='4' nowrap style='color: #ffffff'>$mensagem_aguardando_nf</td>";

                } else {

                    echo "<td align='right'  style='padding-right:3px;' nowrap> ";
                        if ($login_fabrica == 6) {

                            if (strlen($liberado) > 0) {
                                echo number_format($xvrmao_obra,2,",",".");
                            }

                        } else {

                            echo number_format($xvrmao_obra,2,",",".");

                        }
                    echo "</td>\n";

                    if ((!in_array($login_fabrica,array(94,99,106,121,125,126,128,129,131,134,136,137,138,140,139,142,146,147,149,150,151,152,153,157,160,180,181,182)) and !$replica_einhell) || isset($usaLGR)) {

                        // Fabricas que n„o pagam a peÁa
                        if(!in_array($login_fabrica,array(156))){

                        echo "<td align='right'  style='padding-right:3px;' nowrap> ";
                            if ($login_fabrica == 6) {
                                if (strlen($liberado) > 0) {
                                    echo number_format($pecas,2,",",".");
                                }
                            } else {
                                    echo number_format($pecas,2,",",".");
                            }
                        echo "</td>\n";
                        }

                    }

                    if(in_array($login_fabrica, array(140,142,145)) or isset($fabrica_usa_valor_adicional)){
                        $valor_adicional = ($valor_adicional) ? $valor_adicional : 0;
                        echo "<td align='right'  style='padding-right:3px;' nowrap> ";
                            echo number_format($valor_adicional,2,",",".");
                        echo "</td>\n";
                        $total += $valor_adicional;
                    }

                    if(isset($novaTelaOs) and !in_array($login_fabrica ,array(147,150,151))){
                        $deslocamento_km = ($deslocamento_km) ? $deslocamento_km : 0;
                        echo "<td align='right'  style='padding-right:3px;' nowrap> ";
                            echo number_format($deslocamento_km,2,",",".");
                        echo "</td>\n";
                        $total += $deslocamento_km;
                    }

                    if($login_fabrica == 90){
                        $deslocamento_km = ($deslocamento_km) ? $deslocamento_km : 0;
                        echo "<td align='right'  style='padding-right:3px;' nowrap> ";
                        echo number_format($deslocamento_km,2,",",".");
                        echo "</td>\n";

                        $sqlVisita = "SELECT SUM(tbl_os_extra.taxa_visita) AS taxa_visita FROM tbl_os_extra WHERE extrato = $extrato";
                        $resVisita = pg_query($con,$sqlVisita);
                        $taxa_visita = pg_fetch_result($resVisita,0,'taxa_visita');

                        echo "<td align='right'  style='padding-right:3px;' nowrap> ";
                        echo number_format($taxa_visita,2,",",".");
                        echo "</td>\n";
                    }

                    if($inf_valores_adicionais and !in_array($login_fabrica,array(152,156,180,181,182))){
                        $valor_adicional = ($valor_adicional) ? $valor_adicional : 0;
                        $total += $valor_adicional;
                        echo "<td align='right'  style='padding-right:3px;' nowrap>";
                            echo number_format($valor_adicional,2,",",".");
                        echo "</td>\n";
                    }

                    if($login_fabrica == 35){
                        $total += $deslocamento_km;
                        echo "<td align='right'  style='padding-right:3px;' nowrap> ";
                        echo number_format($deslocamento_km,2,",",".");
                        echo "</td>\n";
                    }
                    //hd 39502
                    if ($login_fabrica == 20) {
                        echo "<td align='right'  style='padding-right:3px;' nowrap> ";
                            echo number_format($total_cortesia,2,",",".");
                        echo "</td>\n";
                    }

                    if($login_fabrica == 129){
                        echo "<td>".number_format($deslocamento_km,2,",",".")."</td>";
                    }

                    if ($login_fabrica <> 51 AND $login_fabrica <> 128) {

                        if($login_fabrica == 129){

                            echo "<td>".number_format($avulso,2,",",".")."</td>";
                            echo "<td><strong>".number_format($total + $avulso,2,",",".")."</strong></td>";

                        }else{

                            if ($login_fabrica <> 45 && !isset($novaTelaOs)) {

                                echo "<td align='right'  style='padding-right:3px;' nowrap> ";
                                if ($login_fabrica == 6) {

                                    if (strlen($liberado) > 0) {
                                        echo number_format($total,2,",",".");
                                    }

                                } else {

                                    echo ($login_fabrica==11) ? number_format($avulso,2,",",".") : number_format($total,2,",",".") ;

                                }
                                echo "</td>\n";

                            }

                            if($login_fabrica == 160 or $replica_einhell){
                                echo "<td align='right' style='padding-right:3px;' nowrap>".number_format($pecas,2,",",".")."</td>";
                            }

                            echo "<td align='right' style='padding-right:3px;' nowrap>";

                            if ($login_fabrica == 6) {
                                if (strlen($liberado) > 0) {
                                    echo number_format($avulso,2,",",".");
                                }
                            } else {
                                echo ($login_fabrica==11) ? number_format($total+$avulso,2,",",".") : number_format($avulso,2,",",".");
                            }

                        }

                    } else {
                        if($login_fabrica == 128){
                            echo "<td align='right' style='padding-right:3px;' nowrap>".number_format($valor_adicional,2,",",".")."</td>";
                            echo "<td align='right' style='padding-right:3px;' nowrap>".number_format($deslocamento_km,2,",",".")."</td>";
                            echo "<td align='right' style='padding-right:3px;' nowrap>".number_format($total,2,",",".")."</td>";
                        }else{
                            echo "<td align='right'  style='padding-right:3px;' nowrap> ";
                            echo number_format($avulso,2,",",".");
                            echo "</td>\n";
                            echo "<td align='right'  style='padding-right:3px;' nowrap> ";
                            echo number_format($total,2,",",".");
                        }
                    }
                    echo "</td>\n";

                    if (in_array($login_fabrica, array(30,50,85,91,94,120,201,125,131,140)) && !isset($novaTelaOs)){
                        echo "<td align='right'  style='padding-right:3px;' nowrap> ";
                        if(in_array($login_fabrica,array(91,125))){
                            echo number_format($valor_km,2,",",".");
                        }
                        else{
                            echo number_format($deslocamento_km,2,",",".");
                        }
                        echo "</td>\n";
                    }

                    if (isset($novaTelaOs)) {
                        echo "<td align='right'>".number_format($total_extrato, 2, ",", ".")."</td>";
                    }

                    if($login_fabrica ==45) {
                        echo "<td align='right'  style='padding-right:3px;' nowrap> ";
                        echo number_format($total_geral,2,",",".");
                        echo "</td>\n";
                    }

                    if ($login_fabrica != 99) {//HD 416354
                        echo "<td align='right'  style='padding-right:3px;' nowrap> "; //26/12/2007 HD 9982

                        if(in_array($login_fabrica,array(11,15,24,45,50,81,104,114,115,116,117,120,201,121,123,124,125,126,127,128,129,131,134,136,137,138,139,140,141,144,145,146,151,152,153,157,160,180,181,182)) || $replica_einhell) {
                            echo $data_pagamento;
                        } elseif ($login_fabrica==20) {
                            echo $previsao_pagamento;
                        } else {
                            if($login_fabrica<>11){
                                    echo $previsao;
                            }
                        }
                        echo "</td>\n"; //26/12/2007 HD 9982
                    }

                    echo ($login_fabrica==5) ? "<td align='right'  style='padding-right:3px;' nowrap>$nf_autorizacao</td>\n" : "";
                    echo ($login_fabrica == 19) ? "<td align='right'  style='padding-right:3px;' nowrap> ". $pagamento ."</td>\n" : "";
                    echo ($login_fabrica == 45 or $login_fabrica ==50 or $login_fabrica ==15) ? "<td align='right'  style='padding-right:3px;' nowrap> ". $previsao_pagamento_extrato ."</td>\n" : "";

                }

                if ($login_fabrica == 20) {
                    echo "<td align='left'>$nota_fiscal_mao_de_obra</td>\n";
                    echo "<td align='left'>$nota_fiscal_devolucao</td>\n";
                    echo "<td align='left'>$data_entrega_transportadora</td>\n";
                }

                if($login_fabrica == 120 or $login_fabrica == 201) {
                    $sqlServico = "SELECT * FROM tbl_extrato_extra WHERE extrato = $extrato AND nota_fiscal_mao_de_obra notnull";
                    $resServico = pg_query($con,$sqlServico);
                    if(pg_num_rows($resServico) == 0){
                        echo "<td align='center'><a href='extrato_posto_nf.php?extrato=$extrato'>Preencha a NF<br> do Extrato <br>Clique Aqui</a></TD>";
                    }else{
                        echo "<td align='center'><a href='extrato_posto_nf.php?extrato=$extrato'>NF do Extrato <br>Preenchida</a></TD>";
                    }

                }

               if($login_fabrica == 151){

                    if(strlen($nf_autorizacao) == 0){
                        $nf_autorizacao = "<a href='nota_servico_extrato.php?extrato=$extrato' rel='shadowbox; width= 500; height= 350;'>Informar Nota de ServiÁo</a>";
                    }else{
                        $nf_autorizacao = "<a href='nota_servico_extrato.php?extrato=$extrato' rel='shadowbox; width= 500; height= 350;'>$nf_autorizacao</a>";
                    }

                    echo "<td align='left' class='nf-servico-$extrato' nowrap> $nf_autorizacao </td>\n";
                }

                if ($login_fabrica == 1) {
                    echo "<td><img src='imagens/btn_imprimirdetalhado_15.gif' onclick=\"javascript: janela=window.open('os_extrato_detalhe_print_blackedecker.php?extrato=$extrato','extrato');\" ALT=\"Imprimir detalhado\" border='0' style=\"cursor:pointer;\"></td>\n";
                } else {

                    if ($sistema_lingua == 'ES') {
                        echo "<td><a href='os_extrato_detalhe.php?extrato=$extrato&posto=$posto'><img src='imagens/btn_detallar_".$btn.".gif'></a></td>\n";
                    } else {
                        echo "<td><a href='os_extrato_detalhe.php?extrato=$extrato&posto=$posto'><img src='imagens/btn_detalhar_".$btn.".gif'></a></td>\n";
                    }

                    if ($login_fabrica == 14) {
                        echo "<td nowrap><a href='os_extrato_pecas_retornaveis.php?extrato=$extrato'><font color=\"#0000CC\">PeÁas Trocadas</font></a></TD>\n";
                    } else {

                        if ($sistema_lingua == 'ES') {
                            //echo "<td><a href='os_extrato_pecas_retornaveis.php?extrato=$extrato'><img src='imagens/btn_piezasretornables_".$btn.".gif'></a></TD>\n";
                        } else {

                            if ($login_fabrica <> 15) {

                                if ($login_fabrica == 11) {
                                    echo "<td><a href='extrato_posto_devolucao_lenoxx_itens.php?extrato=$extrato'><img src='imagens/btn_pecasretornaveis_".$btn.".gif'></a></TD>\n";
                                } else {

                                    if (in_array($login_fabrica,array(6,35,50,51,85,90,91,94,99,106,114,115,116,117,120,201,121,123,125,126,127,128,129,131,134,136,137,138,139,140,141,142,144,145,146,147,151,152,153,157,160,180,181,182)) or ($login_fabrica == 24 AND $novo_processo_lgr == 1) || isset($usaLGR) || $replica_einhell) {

                                        if ($login_fabrica == 51) {

                                             if ($extrato > 640885) {

                                                if ($extrato > '839297') {
                                                    $link_extrato = "";
                                                } else {
                                                    $link_extrato = "<a href='extrato_posto_lgr_itens_novo.php?extrato=$extrato'><img src='imagens/btn_pecasretornaveis_".$btn.".gif'></a>";
                                                }

                                             } else {
                                                $link_extrato = "<a href='extrato_posto_devolucao_gama_itens.php?extrato=$extrato'><img src='imagens/btn_pecasretornaveis_".$btn.".gif'></a>";
                                             }

                                        } else {
                                            if($login_fabrica == 35 && $data_extrato < '2014-05-01'){
                                                $link_extrato = "<a href='os_extrato_pecas_retornaveis.php?extrato=$extrato'><img src='imagens/btn_pecasretornaveis_".$btn.".gif'></a>";
                                            }else{
                                                $link_extrato = "<a href='extrato_posto_lgr_itens_novo.php?extrato=$extrato'><img src='imagens/btn_pecasretornaveis_".$btn.".gif'></a>";
                                            }
                                        }

                                        echo "<td>$link_extrato</TD>\n";

                                    } else {

                                        if ($login_fabrica == 24 AND $novo_processo_lgr <> '1') {
                                            echo "<td><a href='os_extrato_pecas_retornaveis_suggar_lgr.php?extrato=$extrato'><img src='imagens/btn_pecasretornaveis_".$btn.".gif'></a></TD>\n";
                                        } else if ($login_fabrica == 2) {

                                            if ($extrato > 302634){ # HD 12684
                                                echo "<td><a href='extrato_posto_lgr.php?extrato=$extrato'><img src='imagens/btn_pecasretornaveis_".$btn.".gif'></a></TD>\n";
                                            } else {
                                                echo "<td><a href='os_extrato_pecas_retornaveis.php?extrato=$extrato'><img src='imagens/btn_pecasretornaveis_".$btn.".gif'></a></TD>\n";
                                            }

                                        } else if ($login_fabrica == 25) {
                                            echo "<td><a href='extrato_posto_devolucao_hbtech_itens.php?extrato=$extrato'><img src='imagens/btn_pecasretornaveis_".$btn.".gif'></a></TD>\n";
                                        } else {

                                            if ($login_fabrica == 7) {
                                                echo "<td><a href='extrato_posto_lgr_itens.php?extrato=$extrato'><img src='imagens/btn_pecasretornaveis_".$btn.".gif'></a></TD>\n";
                                            } else if(!in_array($login_fabrica,array(104,105,124,149,150))){
                                                echo "<td><input type='button' value='Produtos' onclick=\"window.location='os_extrato_pecas_retornaveis.php?extrato=$extrato'\"></TD>\n";
                                            }

                                        }

                                    }

                                }

                            } else { # HD 81361 Latinatec
                                echo "<td><a href='os_extrato_pecas_latina.php?extrato=$extrato'><img src='imagens/btn_pecasretornaveis_".$btn.".gif'></a></TD>\n";
                            }

                        }

                    }

                }

            }

            echo "</tr>\n";

            if ($login_fabrica == 15) { # HD 165932
                echo "<tr class='table_line' style='background-color: $cor;'>\n";
                echo "<td colspan='100%' nowrap align='center' style='font-size:18px'><div id='mostra_$i'>Nota Fiscal:&nbsp;&nbsp;";
                echo (strlen($nota_fiscal_mao_de_obra) > 0) ? $nota_fiscal_mao_de_obra : "<input type='text' name='nota_fiscal_mao_de_obra_$i' id='nota_fiscal_mao_de_obra_$i' class='frm' size='10' maxlength='10' rel='nota_fiscal_mao_de_obra'>&nbsp;&nbsp;";
                echo "&nbsp;&nbsp;Data Emiss„o:&nbsp;&nbsp;";
                echo (strlen($nota_fiscal_mao_de_obra) > 0) ? $emissao_mao_de_obra : "<input type='text' name='emissao_mao_de_obra_$i' id='emissao_mao_de_obra_$i' rel='emissao_mao_de_obra' size='12'>&nbsp;&nbsp;";
                echo "&nbsp;&nbsp;Valor NF: &nbsp;&nbsp;";
                echo (strlen($nota_fiscal_mao_de_obra) > 0) ? number_format($valor_total_extrato,2,",",".") : "<input type='text' name='valor_total_extrato_$i' id='valor_total_extrato_$i' size='10' maxlength='10' rel='valor_total_extrato'>&nbsp;&nbsp;&nbsp;";
                echo (strlen($nota_fiscal_mao_de_obra) > 0) ? "" : "<a href=\"javascript: gravaNota('nota_fiscal_mao_de_obra_$i','emissao_mao_de_obra_$i','valor_total_extrato_$i','mostra_$i',$extrato);\"><img src='imagens/btn_gravar.gif'>";
                echo "</div></td>";
                echo "</tr>";
            }
            $extrato_anterior = $extrato;
        }
        echo "<input type='hidden' name='total' value='$i'>";
        echo "</form>";
    }else{
        echo "<tr class='table_line'>\n";
        echo "<td align=\"center\">";
        fecho("nenhum.extrato.foi.encontrado",$con,$cook_idioma);
        echo "</td>\n";
        echo "</tr>\n";
        echo "<tr>\n";
        echo "<td align=\"center\">\n";
        echo "<br><a href='menu_os.php'><img src='imagens/btn_voltar.gif'></a>";
        echo "</td>\n";
        echo "</tr>\n";
    }
    echo "</table>\n";
}
?>
<p><p>
<? include "rodape.php"; ?>
