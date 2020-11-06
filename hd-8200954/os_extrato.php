<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';
include 'funcoes.php';

if($login_fabrica == 114){
         header("Location: menu_inicial.php");
         exit;
}

if ($login_fabrica<>1 and $login_fabrica<>20) include "autentica_usuario_financeiro.php";

//HD 205958: Um extrato pode ser modificado atÈ o momento que for APROVADO pelo admin. ApÛs aprovado
//           n„o poder· mais ser modificado em hipÛtese alguma. Acertos dever„o ser feitos com lanÁamento
//           de extrato avulso. Verifique as regras definidas neste HD antes de fazer exceÁıes para as f·bricas
//           SER√O LIBERADOS AOS POUCOS, POIS OS PROGRAMAS N√O EST√O PARAMETRIZADOS
//           O array abaixo define quais f·bricas est„o enquadradas no processo novo
$fabricas_acerto_extrato = array(42,43,45,88,95,99);

// F·bricas que usam o Extrato LGR
$fabricas_novo_extrato_lgr = array(
    6,24,35,51,81,85,90,91,94,99,101,104,105,106,
    114,115,116,117,120,121,122,123,124,125,126,
    127,128,129,131,134,136,137,138,139,140,
    141,142,144,145,146,147,149,150,151,152,153,157,160,162,180,181,182,183,201
);

if (in_array($login_fabrica,$fabricas_novo_extrato_lgr) || isset($usaLGR)  || $replica_einhell ) {
    header ("Location: os_extrato_novo_lgr.php");
    exit;
}

$sql = "SELECT posto FROM tbl_posto_fabrica WHERE posto = $login_posto AND fabrica = $login_fabrica AND controla_estoque IS TRUE";
$res = pg_query($con,$sql);
if(pg_num_rows($res) > 0 AND $login_fabrica == 50){
    header ("Location: os_extrato_novo_lgr.php");
    exit;
}

if ($login_fabrica == 3) {
    $sql = "SELECT   extrato
                    FROM  tbl_extrato
                    WHERE tbl_extrato.fabrica = $login_fabrica
                    AND   tbl_extrato.posto = $login_posto
                    ORDER BY  tbl_extrato.extrato DESC LIMIT 1";
    $res = pg_query($con,$sql);
    if(pg_num_rows($res) > 0){

        $ultimo_extrato = pg_fetch_result($res,0,'extrato');

        $data_corte_britania = " and tbl_extrato.data_geracao > '2017-10-01 00:00:00' ";
        $join_extrato = " join tbl_extrato on tbl_extrato.extrato = tbl_faturamento.extrato_devolucao "; 

        $sqls = "SELECT  DISTINCT tbl_faturamento.extrato_devolucao
                   FROM  tbl_faturamento
                   JOIN  tbl_faturamento_item USING (faturamento)
                   JOIN  tbl_peca             USING (peca)
                   $join_extrato 
                  WHERE  tbl_faturamento.extrato_devolucao <= $ultimo_extrato
                    $data_corte_britania
                    AND  tbl_faturamento.fabrica            = $login_fabrica
                    AND  tbl_faturamento.posto              = $login_posto
                    AND  tbl_faturamento.distribuidor          IS NULL
                    AND  (tbl_faturamento_item.devolucao_obrig IS TRUE OR tbl_peca.produto_acabado IS TRUE)
                    AND  tbl_faturamento.cfop IN ('694921','694922','694923','594919','594920','594921','594922','594923')
                    AND  tbl_faturamento.extrato_devolucao NOT IN (
                                SELECT DISTINCT
                                       extrato_devolucao
                                  FROM tbl_faturamento
                                 WHERE posto IN (13996,4311)
                                   AND distribuidor=$login_posto
                                   AND fabrica=$login_fabrica
                                   AND extrato_devolucao <= $ultimo_extrato
                            )
                    ORDER BY  tbl_faturamento.extrato_devolucao DESC";
        $ress = pg_query ($con,$sqls);
        $res_qtdes = pg_num_rows ($ress);
        if ($res_qtdes> 0){

            $extrato_aux = pg_fetch_result($ress,0,'extrato_devolucao');

            $sqlD="SELECT extrato_devolucao
                FROM   tbl_faturamento
                WHERE  distribuidor = $login_posto
                AND    extrato_devolucao = $extrato_aux
                AND fabrica = $login_fabrica;";
            $resD = pg_query($con,$sqlD);

            if(pg_num_rows($resD) == 0){
                $sqld = " SELECT tbl_extrato.extrato,to_char(data_geracao,'DD/MM/YYYY') as data_extrato
                            FROM tbl_extrato
                       LEFT JOIN tbl_extrato_agrupado USING(extrato)
                           WHERE extrato = $extrato_aux
                             AND fabrica = $login_fabrica
                             AND posto   = $login_posto
                             AND data_geracao > '2010-01-01 00:00:00'
                             AND tbl_extrato_agrupado.aprovado ISNULL
                        ORDER BY extrato DESC limit 1;";
                $resd = pg_query($con,$sqld);
                if(pg_num_rows($resd) > 0){
                    header("Location:extratos_pendentes_britania.php");
                    exit;
                }
            }
        }
    }

    if ($login_e_distribuidor == 't') {
        header ("Location: new_extrato_distribuidor.php");
        exit;
    } else {
        $sql = "SELECT codigo
                FROM tbl_extrato
                JOIN tbl_extrato_agrupado USING(extrato)
                WHERE fabrica = $login_fabrica
                AND   posto   = $login_posto
                ";
        $res = pg_query($con,$sql);
        if(pg_num_rows($res) > 0){
            header ("Location: extrato_agrupado.php");
        } else {
            $sqln = "SELECT extrato
                FROM tbl_extrato
                JOIN tbl_extrato_nota_avulsa USING(extrato)
                WHERE tbl_extrato.fabrica = $login_fabrica
                AND   tbl_extrato.posto   = $login_posto
                ";
            $resn = pg_query($con,$sqln);
            if (pg_num_rows($resn) > 0) {
                header ("Location: extrato_agrupado.php");
            } else {
                header ("Location: extrato_posto_novo.php");
            }
        }
        exit;
    }
}

$ajax = $_GET['ajax'];

if ($ajax=="true") {
    $extrato = $_GET['extrato'];
    $status  = $_GET['status'];
    $nf      = $_GET['nf'];

    if (in_array($login_fabrica, array(11,172))){
        $contato_fabricante = "Taiz TEL:071 3379-1997";
    }

    #HD 173724 adicionada a fabrica 43
    if ($login_fabrica == 7 or $login_fabrica == 43){
        $contato_fabricante = "fabricante";
    }
    if ($login_fabrica == 25 or $login_fabrica == 51){
        $contato_fabricante = "Sr. Ronaldo TEL:014 3413-6588";
    }

    if (strlen($extrato)>0){
        if ($status=="anterior"){
            fecho("prezada.autorizada", $con, $cook_idoma);
	    echo "<br/><br/>";
	    if($login_fabrica == 50){
		echo traduz("A nota fiscal de RETORNO DE REMESSA das peÁas em garantia do extrato $extrato n„o foram preenchidas. Por favor, acesse o link de peÁas retorn·veis para o preenchimento.");
	    }else{
		    fecho ("a.nota.fiscal.de.devolucao.das.pecas.em.garantia.do.extrato.%.nao.foram.preenchidas.por.favor.acesse.o.link.de.pecas.retornaveis.para.o.preenchimento",$con,$cook_idioma,$extrato);
	    }
            echo ".<br/><br/>";
            echo "<a href='extrato_posto_devolucao.php?extrato=$extrato' title='";
            fecho("clique.aqui.para.preencher.a.nota.fiscal.de.devolucao..apos.a.devolucao.da.nf,.podera.ser.visualizado.a.mao.de.obra",$con,$cook_idioma);
            echo "'>";
            fecho("clique.aqui.para.preencher.a.nf",$con,$cook_idioma);
            echo ".</a>";
        }
        if ($status=="parcial"){
            if ( in_array($login_fabrica, array(11,172)) ){
                fecho("prezada.autorizada", $con, $cook_idoma);
                echo "<br/><br/>";
                fecho("a.nf.de.devolucao.%.foram.recebidas.parcialmente.pela.fabrica", $con, $cook_idioma,$nf);
                fecho ("favor.entrar.em.contato.urgente.com.a.taiz.tel.071.3379.1997.para.sua.regularizacao",$con,$cook_idioma);
            }
        }
	if ($status=="confirmada"){
	    $contato_fabricante = "fabricante";
            fecho("prezada.autorizada", $con, $cook_idioma);
	    echo "<br/><br/>";
	    if($login_fabrica == 50){
		fecho("a.nota.fiscal.de.retorno.de.remessa.%.nao.foi.recebida.pela.fabrica",$con,$cook_idioma,$nf);
	    }else{
		    fecho ("a.nf.de.devolucao.%.nao.foram.recebida.pela.fabrica", $con, $cook_idioma,$nf);
	    }
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
    $aux_ano      = substr ($data_emissao,6,4);
    $aux_mes      = substr ($data_emissao,3,2);
    $aux_dia      = substr ($data_emissao,0,2);
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
        $data_inicio = pg_fetch_result($wres,0,'inicio');
        $data_fim    = pg_fetch_result($wres,0,'fim');

        $wwsql = "SELECT tbl_os.os,
                        tbl_os.sua_os,
                        TO_CHAR(tbl_os.data_abertura,'DD/MM/YYYY')   AS data_abertura,
                        TO_CHAR(tbl_os.data_digitacao,'DD/MM/YYYY')  AS data_digitacao,
                        TO_CHAR(tbl_os.data_fechamento,'DD/MM/YYYY') AS data_fechamento,
                        CURRENT_DATE - tbl_os.data_abertura          AS tempo_em_aberto,
                        tbl_produto.referencia                       AS produto_referencia,
                        tbl_produto.descricao                        AS produto_descricao,
                        tbl_os.consumidor_nome,
                        tbl_os.motivo_atraso,
                        tbl_os_extra.motivo_atraso2
                    FROM tbl_os
                    JOIN tbl_produto  ON tbl_os.produto = tbl_produto.produto
                    JOIN tbl_os_extra ON tbl_os.os      = tbl_os_extra.os
                   WHERE tbl_os.fabrica = $login_fabrica
                     AND tbl_os.posto = $login_posto
                     AND tbl_os.data_fechamento IS NULL
                     AND tbl_os.excluida IS NOT TRUE
                     AND ((tbl_os.data_abertura BETWEEN '$data_inicio'  AND '$data_fim' AND tbl_os.motivo_atraso IS NULL)
                      OR (tbl_os.data_abertura <'$data_inicio' AND tbl_os_extra.motivo_atraso2 IS NULL))
                ORDER BY tbl_os.data_abertura ";
        $wwres = pg_query($con,$wwsql);
        if(pg_num_rows($wwres)>0){
            include "os_aberta.php";
        }
    }
}

if ($login_fabrica == 1) {
    header ("Location: os_extrato_blackedecker.php");
    exit;
}

if ($login_fabrica == 24) {

    $wwsql = "SELECT peca from tbl_estoque_posto_movimento where fabrica = $login_fabrica and posto = $login_posto and obs = 'Invent·rio de PeÁas'";
    $wwres = pg_query($con, $wwsql);

    if (pg_num_rows($wwres) == 0) {
        include "peca_inventario.php";
        exit;
    }

}

$msg_erro    = '';
$layout_menu = 'os';
$title       = traduz("extrato",$con,$cook_idioma);

include "cabecalho.php";
include "javascript_calendario_new.php"; //adicionado por Fabio 27-09-2007

unset($meses);
$meses = array(
    1 =>traduz('janeiro', $con, $cook_idioma),
    traduz('fevereiro',   $con, $cook_idioma),
    traduz('marco',       $con, $cook_idioma),
    traduz('abril',       $con, $cook_idioma),
    traduz('maio',        $con, $cook_idioma),
    traduz('junho',       $con, $cook_idioma),
    traduz('julho',       $con, $cook_idioma),
    traduz('agosto',      $con, $cook_idioma),
    traduz('setembro',    $con, $cook_idioma),
    traduz('outubro',     $con, $cook_idioma),
    traduz('novembro',    $con, $cook_idioma),
    traduz('dezembro',    $con, $cook_idioma)
);

?>

<script src="js/thickbox.js" type="text/javascript"></script>
<script src="plugins/shadowbox/shadowbox.js" type="text/javascript"></script>

<link rel="stylesheet" type="text/css" href="plugins/shadowbox/shadowbox.css" media="all">
<link rel="stylesheet" href="js/thickbox.css" type="text/css" media="screen" />

<script language="JavaScript">

    $(function() {
        $("input[rel='emissao_mao_de_obra']").maskedinput("99/99/9999");
        Shadowbox.init();

        $("input[id^=encontro_contas_]").click(function(){

            var extrato = $(this).attr("rel");
            var posto   = $(this).attr("rel");

            Shadowbox.open({
                content : "admin/detalhe_encontro_contas.php?extrato="+extrato+"&posto="+posto,
                player  : "iframe",
                title   : ('<?=traduz("Detalhe encontro de contas")?>'),
                width   : 800,
                height  : 250
            });
        });
    });

    window.onload = function() {

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

    function mostrar_os_calculadas(obj){
        if($("#os_calculadas").is(":visible")){
            $("#os_calculadas").hide();
            $(obj).removeClass('ocultar');
            $(obj).addClass('expandir');
        }else{
            $("#os_calculadas").show();
            $(obj).addClass('ocultar');
            $(obj).removeClass('expandir');
        }
    }

    function mostrar_os_calculadas_pacific(obj){
        if($("#os_calculadas_pacific").is(":visible")){
            $("#os_calculadas_pacific").hide();
            $(obj).removeClass('ocultar');
            $(obj).addClass('expandir');
        }else{
            $("#os_calculadas_pacific").show();
            $(obj).addClass('ocultar');
            $(obj).removeClass('expandir');
        }
    }

    function gravaNota(nota,data,valor,mostrar,extrato) {
        var nota    = document.getElementById(nota).value;
        var data    = document.getElementById(data).value;
        var valor   = document.getElementById(valor).value;
        var mostrar = document.getElementById(mostrar);

        if (nota.length == 0 || data.length==0 || valor.length == 0) {
            alert('Preenche todos os dados para gravar')
        } else {
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

    <?php if($login_fabrica == 19){ //hd_chamado=2881143?>
        function submit_data(){
            var mes = $('select[name="mes"]').val();
            var mes_final = $('select[name="mes_final"]').val();
            var ano = $('select[name="ano"]').val();
            if(mes.length > 0 && mes_final.length > 0 && ano.length > 0){
                frm_mes.submit();
            }
        }
    <?php } ?>
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

    function previsaoPagamento() {

        Shadowbox.open({
            content: "previsao_pagamento_latinatec.php",
            player:  "iframe",
            title:   "Previs„o de Pagamento",
            width:   800,
            height:  500
        });

    }

    function listarTodosExtratos(){
	window.location = 'os_extrato.php';
    }

</script>

<?php

if(in_array($login_fabrica, array(11,172))){

?>

<script>

    function aviso_detalhar(fabrica, url){

        Shadowbox.init();

        Shadowbox.open({
            content: "aviso_extrato.php?fabrica="+fabrica+"&url="+url,
            player: "iframe",
            title: "AtenÁ„o!!!",
            width: 600,
            height: 240,
            options: {
                modal: true,
                enableKeys: true
            }
        });

    }

    function retorno_pagina_redireciona(url){

        location.href = url;

    }

</script>

<?php

}

?>

<style type="text/css">

table{
    min-width: 760px !important;
}

.expandir{
    background: url(admin/imagens/icon_expand_white.png) no-repeat 16px 2px;
}

.ocultar{
    background: url(admin/imagens/icon_collapse_white.png) no-repeat 16px 2px;
}

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

.error {
    color: white;
    text-align: center;
    font: bold 16px Verdana, Arial, Helvetica, sans-serif;
    background-color: #FF0000;
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
   if($login_pais != 'BR'){
    echo $cook_idioma." A previsao de pagamento e de 15 dias apos a data do fechamento.<br>
            Verifique as datas no calendario de garantia. </td>";
   }else{
    echo "A previs„o de pagamento È de 15 dias apÛs a data do fechamento.<br>
          Verifique as datas no calend·rio de garantia. </td>";
   }
    echo "<td class='Mensagem' align='left' bgcolor='FFFFFF' style='padding-left:7px;'>\n";
    echo    "<a href='calendario_bosch/calendario_garantia_2020.pdf' target='_blank'><font size='4' color='blue'><b><u>Calendario de Garantia</u></b></font></a>";

    echo "</td>";
    echo "</tr>";
    echo "</table><br>";
   // echo "<a href='https://devel.telecontrol.com.br/~diego/PosVendaAssist/documentos/8420376-calendario_de_garantia_2015.pdf>TESTE</a>";
}

$periodo = trim($_POST['periodo']);
if (strlen($_GET['periodo']) > 0) $periodo = trim($_GET['periodo']);

# -- VERIFICA SE √â POSTO OU DISTRIBUIDOR -- #
$sql = "SELECT DISTINCT
               tbl_tipo_posto.tipo_posto ,
               tbl_posto.estado
         FROM  tbl_tipo_posto
         JOIN  tbl_posto_fabrica  ON tbl_posto_fabrica.tipo_posto = tbl_tipo_posto.tipo_posto
                                 AND tbl_posto_fabrica.posto      = $login_posto
                                 AND tbl_posto_fabrica.fabrica    = $login_fabrica
         JOIN  tbl_posto          ON tbl_posto.posto              = tbl_posto_fabrica.posto
         WHERE tbl_tipo_posto.distribuidor IS TRUE
         AND   tbl_posto_fabrica.fabrica = $login_fabrica
         AND   tbl_tipo_posto.fabrica    = $login_fabrica
         AND   tbl_posto_fabrica.posto   = $login_posto ";
$res = pg_query($con,$sql);
$tipo_posto = (pg_num_rows($res) == 0) ? "P" : "D";

//  Confere se tem extratos liberados sem NF, ent„o procura o comunicado para esse extratoe mostra
if (in_array($login_fabrica,array(11,24,25,172))) {
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
        $extratos = pg_fetch_all($res_ext);
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
                if (pg_num_rows($res_com)==1 and pg_fetch_result($res_com, 0, 'ativo')=='t') {
                    echo "\t<tr 'style='background-color: #66a;color:white'>\n".
                         "\t\t<td style='width:33%;padding: 10px' valign='top'>Comunicado <b>".pg_fetch_result($res_com, 0, 'comunicado').
                         "</b><br><br><p>".pg_fetch_result($res_com, 0, 'descricao')."</p></td>\n".
                         "\t\t<td style='width:66%;padding: 10px'><p>".pg_fetch_result($res_com, 0, 'mensagem')."</p></td>\n";
                    "\t</tr>\n";
                }
            }
            echo "</table>\n";
        }
    }
}

if ($login_fabrica==25 or $login_fabrica==51) {?>
    <TABLE width="650" align="center" border="0" cellspacing="0" cellpadding="2">
    <? $class = ($login_fabrica==25 or $login_fabrica==51) ? "menu_top4" : "menu_top"; ?>
    <TR>
        <TD colspan="10" class="<? echo $class; ?>" ><div align="center" style='font-size:16px'>
        <b>
        <?
            if ($pecas_pendentes=="sim"){

                echo traduz("DEVOLU«√O PENDENTE");

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
            Custo de envio das OS's e das peÁas de devoluÁ„o ser√É¬£o ressarcidos no pr√É¬≥ximo extrato. Favor encaminhar as OS's via SEDEX junto com o comprovante de postagem.*/
            /* HD 79440 Ronaldo enviou por email
            echo "Custo de envio das OS's e das peÁas de devoluÁ„o ser√É¬£o ressarcidos no pr√É¬≥ximo extrato. Favor encaminhar as OS's via SEDEX junto com o comprovante de postagem. E as peÁas dever√É¬£o ser devolvidas atravÈs de encomenda PAC Varejo (Correio).";
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
    echo "<td align=\"center\"><B>EMITIR NOTA FISCAL DE M√É∆íO DE OBRA E ENVIAR OS's PARA CONFER√É≈†NCIA:</B><BR>
    HB ASSIST√É≈†NCIA T√É‚Ä∞CNICA LTDA.<br>
    Av. Yojiro Takaoka, 4.384 - Conj. 2156 - Loja 17<br>
    Alphaville<br>
    Santana de Parna√É¬≠ba, SP, CEP 06.541-038<br>
    CNPJ: 08.326.458/0001-47 </td>\n";
    echo "</tr>\n";
    echo "<tr class='table_line3'>\n";
    echo "<td align=\"center\"><B>ENVIAR OS's PARA CONFER√É≈†NCIA E LIBERA√É‚Ä°√É∆íO DO EXTRATO DE M√É∆íO DE OBRA:</b><BR>
    HBFLEX S.A.<br>
    Av. MarquÍs de S„o Vicente, 121 - Bl. B - Conj 401<br>
    Barra Funda<br>
    S„o Paulo, SP, CEP 01139-001 </td>\n";
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
    MAR√É¬çLIA, SP, CEP 17519-255 <br>
    CNPJ: 04.716.427/0001-41 </td>\n";
    echo "</tr>\n";
    echo "<tr class='table_line3'>\n";
    echo "<td align=\"center\"><B>ENVIAR PARA:</b><BR>
    TELECONTROL NETWORKING LTDA.<br>
    AV.CARLOS ARTENCIO, 420 B - FRAGATA C<br>
    MAR√É¬çLIA, SP, CEP 17519-255 </td>\n";
    echo "</tr>\n";
    echo "</table>";*/
}

/*
if($login_fabrica == 30){ // HD 60266
        echo "<TABLE width='650' align='center' border='0' cellspacing='0' cellpadding='2'>";
        echo "<tr>\n";
        echo "<td align=\"center\" style='color:#FF0000'>Emitir NF somente apÛs e-mail enviado pela F·brica, Extrato liberado somente para consulta de ServiÁo e KM. Quando o nosso Extrato estiver completo enviaremos um comunicado aos SAE's
        </td>\n";
        echo "</tr></table>";
}
*/

if ($login_fabrica == 3 or $login_fabrica == 19) {
    if($fabrica==3){
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
        $res = pg_query($con,$sql);

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
                $protocolo = trim(pg_fetch_result($res,$x,'protocolo'));
                $aux_data  = trim(pg_fetch_result($res,$x,'data'));
                $aux_extr  = trim(pg_fetch_result($res,$x,'data_extrato'));
                $aux_peri  = trim(pg_fetch_result($res,$x,'periodo'));
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
                <td>
                    <?
                        if($login_fabrica == 19){
                            echo "MÍs Inicial";
                        }else{
                            fecho("mes",$con,$cook_idioma);
                        }
                    ?>
                </td>

                <?php if($login_fabrica == 19){?>
                    <td>MÍs Final</td>
                <?php } ?>
            </tr>
            <tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
                <?php if($login_fabrica <> 19){ ?>
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
                    foreach($meses as $i_mes=>$i_mes_nome) {
                        $sel = ($mes == $i_mes) ? ' SELECTED' : '';
                        echo "<option$sel value='$i_mes'>$i_mes_nome</option>\n";
                    }
                    ?>
                    </select>
                </td>
                <?php } ?>

                <?php if($login_fabrica == 19){ //hd_chamado=2881143?>
                    <td>
                        <select name="ano" size="1" class="frm" onchange='javascript:submit_data();' >
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
                        <select name="mes" size="1" class="frm" onchange='javascript:submit_data();'>
                        <option value=''></option>
                        <?
                        foreach($meses as $i_mes=>$i_mes_nome) {
                            $sel = ($mes == $i_mes) ? ' SELECTED' : '';
                            echo "<option$sel value='$i_mes'>$i_mes_nome</option>\n";
                        }
                        ?>
                        </select>
                    </td>
                    <td>
                        <!--<select name="mes_final" size="1" class="frm" onchange='javascript:frm_mes.submit()'>-->
                        <select name="mes_final" size="1" class="frm" onchange='javascript:submit_data();'>
                        <option value=''></option>
                        <?
                        foreach($meses as $i_mes_final=>$i_mes_nome_final) {
                            $sel = ($mes_final == $i_mes_final) ? ' SELECTED' : '';
                            echo "<option$sel value='$i_mes_final'>$i_mes_nome_final</option>\n";
                        }
                        ?>
                        </select>
                    </td>
                <?php } ?>
            </tr>
        </table>
        </form>
<?  }
    // LEGENDA ?>
    <table width='700' height=16 border='0' cellspacing='0' cellpadding='5' align='center'>
        <tr>
            <td align='center' width='16' bgcolor='#FFE1E1'>&nbsp;</td>
            <td align='left'><font size=1><b>&nbsp;<?fecho("extrato.avulso",$con,$cook_idioma);?></b></font></td>
        </tr>
    </table>
    <br>

    <br>
    <form method='post' name='frm_extrato' action="<?=$PHP_SELF?>">
        <table width="540" height=16 border="0" cellspacing="0" cellpadding="5" align="center">
            <tr>
                <td align="left"><font size=1 ><b>&nbsp;
                    <?php echo utf8_decode("Buscar por N√∫mero da OS "); ?>
                    </b></font>
                    <input type="text" name="buscarOS" id="buscarOS">
                </td>
                <td>
                    <input type="submit" name="btn_pesquisar" value="Pesquisar">
                </td>
                <td>
                    <input type="button" name="btn_listar_extratos" value="Listar Todos Extratos" onclick="listarTodosExtratos()">
                </td>
            </tr>
        </table>
    </form>
    </br>
    <?php

    # -- SE FOI SELECIONADO PER√çODO NO COMBO -- #
    $mes = trim(strtoupper($_GET['mes']));
    if($login_fabrica == 19){ //hd_chamado=2881143
        $mes_final = trim(strtoupper($_GET['mes_final']));

        if(($mes_final - $mes) >= 3){
            $msg_erro = "O periodo de pesquisa n„o pode ser maior que 3 meses";
        }
        if($mes > $mes_final){
            $msg_erro = "O mÍs inicial n„o pode ser maior que o mÍs final";
        }

    }
    $ano = trim(strtoupper($_GET['ano']));
    if(strlen($msg_erro) == 0){
        if (strlen($periodo) > 0 or $login_fabrica==19) {
            $exibir = $_POST['exibir'];
            if (strlen($_GET['exibir']) > 0) $exibir = $_GET['exibir'];

            if ($exibir == 'acumulado') {
                # -- EXIBE VALORES ACUMULADOS DOS EXTRATOS -- #
                # -- SELECIONA EXTRATOS DOS POSTOS -- #
                $sql = "SELECT tbl_linha.linha                                                    ,
                               tbl_linha.nome                                       AS linha_nome ,
                               count(tbl_os.os)                                     AS qtde_os    ,
                               tbl_os.mao_de_obra                                   AS mo_unit    ,
                               sum (tbl_os.mao_de_obra)                             AS mo_posto   ,
                               sum (tbl_familia.mao_de_obra_adicional_distribuidor) AS mo_adicional
                          FROM tbl_os
                          JOIN tbl_os_extra       ON tbl_os_extra.os           = tbl_os.os
                                                 AND tbl_os.fabrica            = $login_fabrica
                          JOIN tbl_extrato        ON tbl_extrato.extrato       = tbl_os_extra.extrato
                                                 AND tbl_extrato.fabrica       = $login_fabrica
                          JOIN tbl_produto        ON tbl_produto.produto       = tbl_os.produto
                          JOIN tbl_linha          ON tbl_produto.linha         = tbl_linha.linha
                                                 AND tbl_linha.fabrica         = $login_fabrica
                     LEFT JOIN tbl_familia        ON tbl_produto.familia       = tbl_familia.familia
                          JOIN tbl_posto_fabrica  ON tbl_os.posto              = tbl_posto_fabrica.posto
                                                 AND tbl_posto_fabrica.fabrica = tbl_os.fabrica
                         WHERE tbl_posto_fabrica.fabrica = $login_fabrica ";

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
                    if ($tipo_posto == "D") echo "<td nowrap align='center'><b>PE√É‚Ä°AS<br>TOTAL</b></td>";
                    if ($tipo_posto == "D") echo "<td nowrap align='center'><b>ADICIONAL<br>PE√É‚Ä°AS</b></td>";
                    if ($tipo_posto == "D") echo "<td nowrap align='center'><b>N.F.<br>SERVI√É‚Ä°O</b></td>";
                    echo "<td nowrap align='center'>&nbsp;</td>";
                    echo "</tr>";

                    for ($y=0; $y < pg_num_rows($res); $y++) {
                        $linha        = trim(pg_fetch_result($res,$y,'linha'));
                        $nome_linha   = trim(pg_fetch_result($res,$y,'linha_nome'));
                        $mo_unit      = trim(pg_fetch_result($res,$y,'mo_unit'));
                        $qtde_os      = trim(pg_fetch_result($res,$y,'qtde_os'));
                        $mo_posto     = trim(pg_fetch_result($res,$y,'mo_posto'));
                        $mo_adicional = trim(pg_fetch_result($res,$y,'mo_adicional'));

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
                            $resX = pg_query($con,$sql);

                            if (pg_num_rows($resX) > 0) {
                                $pecas_preco    = pg_fetch_result($resX,0,'preco');
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
                        $data_final   = date("Y-m-t 23:59:59", mktime(0, 0, 0, $mes_final, 1, $ano)); //hd_chamado=2881143
                        $condicao3 = " AND tbl_extrato.nf_recebida IS NOT NULL AND tbl_extrato.nf_recebida != 'f'";
                    }

                    if(strlen($data_inicial)==0 and strlen($data_final)==0){
                        $temp1  = "INTO TEMP temp_extrato_lorenzetti ";
                        $temp2 = "INTO TEMP temp_extrato_lorenzetti2 ";
                        $condicao1 = " AND  tbl_extrato_financeiro.pagamento > current_date";
                        $condicao2 = " AND   tbl_extrato_financeiro.pagamento IS NULL";
                        $condicao3 = " AND tbl_extrato.nf_recebida IS NOT NULL AND tbl_extrato.nf_recebida != 'f'";
                    }
                    # -- EXIBE VALORES DETALHADOS DOS EXTRATOS -- #
                    $sql = "SELECT  DISTINCT tbl_posto_fabrica.codigo_posto                                                                 ,
                            tbl_posto.nome                                                                                 ,
                            tbl_extrato.posto                                                                              ,
                            tbl_extrato.extrato                                                                            ,
                            tbl_extrato.data_geracao                                                        AS data_order  ,
                            to_char(tbl_extrato.data_geracao, 'YYYY-MM-DD')                                 AS data_extrato,
                            to_char(tbl_extrato.data_geracao, 'DD/MM/YYYY')                                 AS data_geracao,
                            to_char(tbl_extrato.aprovado, 'DD/MM/YYYY')                                     AS aprovado    ,
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

                    // HD 2219464
                    if(isset($_POST['buscarOS'])){
                        if($_POST['buscarOS'] != ""){
                            $sql .= " AND tbl_os.sua_os = '".$_POST['buscarOS']."'";
                        }
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
                                        tbl_extrato.aprovado                      ,
                                        tbl_extrato_financeiro.previsao           ,
                                        tbl_extrato_financeiro.pagamento          ,
                                        tbl_posto.estado
                            ORDER BY data_order DESC; ";

                    if(strlen($data_inicial)==0 and strlen($data_final)==0){
                        $sql .= "SELECT  DISTINCT tbl_posto_fabrica.codigo_posto                                                                 ,
                            tbl_posto.nome                                                                                 ,
                            tbl_extrato.posto                                                                              ,
                            tbl_extrato.extrato                                                                            ,
							tbl_extrato.data_geracao as data_order                                                         ,   
							to_char(tbl_extrato.data_geracao, 'YYYY-MM-DD')                                 AS data_extrato,
                            to_char(tbl_extrato.data_geracao, 'DD/MM/YYYY')                                 AS data_geracao,
                            to_char(tbl_extrato.aprovado, 'DD/MM/YYYY')                                     AS aprovado    ,
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
                                                        AND tbl_posto_fabrica.fabrica = $login_fabrica
                                                        AND tbl_posto_fabrica.posto   = $login_posto
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

                        // HD 2219464
                        if(isset($_POST['buscarOS'])){
                            if($_POST['buscarOS'] != ""){
                                $sql .= " AND tbl_os.sua_os = '".$_POST['buscarOS']."'";
                            }
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
                                            tbl_extrato.aprovado                      ,
                                            tbl_extrato_financeiro.previsao           ,
                                            tbl_extrato_financeiro.pagamento          ,
                                            tbl_posto.estado
                                ORDER BY tbl_extrato.extrato DESC; ";

                        $sql .= " SELECT * from temp_extrato_lorenzetti
                                UNION
                                 SELECT * from temp_extrato_lorenzetti2";
                    }//periodo

                }else{//OUTRAS FABRICAS
                    # -- EXIBE VALORES DETALHADOS DOS EXTRATOS -- #
                    $sql = "SELECT  DISTINCT tbl_posto_fabrica.codigo_posto,
                            tbl_posto.nome,
                            tbl_extrato.posto,
                            tbl_extrato.extrato,
                            TO_CHAR(tbl_extrato.data_geracao, 'YYYY-MM-DD')         AS data_extrato,
                            TO_CHAR(tbl_extrato.data_geracao, 'DD/MM/YYYY')         AS data_geracao,
                            TO_CHAR(tbl_extrato.aprovado, 'DD/MM/YYYY')             AS aprovado,
                            tbl_extrato.mao_de_obra,
                            tbl_extrato.mao_de_obra_postos,
                            SUM (tbl_os_extra.mao_de_obra + tbl_os_extra.taxa_visita + tbl_os_extra.deslocamento_km) AS extra_mo,
                            SUM(tbl_familia.mao_de_obra_adicional_distribuidor)     AS adicional,
                            tbl_extrato.pecas,
                            SUM (tbl_os_extra.custo_pecas)                          AS extra_pecas,
                            SUM (tbl_os_extra.taxa_visita)                          AS extra_instalacao,
                            tbl_extrato.protocolo,
                            tbl_extrato.avulso,
                            TO_CHAR (tbl_extrato_financeiro.previsao,'DD/MM/YYYY')  AS previsao,
                            TO_CHAR (tbl_extrato_financeiro.pagamento,'DD/MM/YYYY') AS pagamento,
                            tbl_posto.estado,
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

                // HD 2219464
                    if(isset($_POST['buscarOS'])){
                        if($_POST['buscarOS'] != ""){
    						$sql .= " AND tbl_os.sua_os = '".$_POST['buscarOS']."'";
                        }
                    }
                    if ($tipo_posto == "D") $sql .= "AND (tbl_posto_fabrica.distribuidor = $login_posto OR tbl_posto_fabrica.posto = $login_posto) ";
                    else                    $sql .= "AND tbl_extrato.posto   = $login_posto ";
                    if(strlen($periodo)>0){
                        $sql .="AND         tbl_extrato.data_geracao BETWEEN '$periodo 00:00:00' AND '$periodo 23:59:59'";
                    }

                    // HD 2219464
                    // if(isset($_POST['buscarOS'])){
                    //     if($_POST['buscarOS'] != ""){
                    //         $sql .= " AND tbl_os_extra.os = ".$_POST['buscarOS']." ";
                    //     }
                    // }

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
                                        tbl_extrato.aprovado                      ,
                                        tbl_extrato_financeiro.previsao           ,
                                        tbl_extrato_financeiro.pagamento          ,
                                        tbl_posto.estado
                            ORDER BY tbl_extrato.extrato DESC; ";
                }

                $res = pg_query($con,$sql);
                echo "<table width='580' align='center' border='0' cellspacing='1' cellpadding='1'>\n";
                if (pg_num_rows($res) > 0) {
                    echo "<tr class='table_line'>";

                    $colspan = ($login_fabrica==19) ? 13 : 8;

                    echo "<td colspan='$colspan' align='center'>\n";
                    echo "&nbsp;";
                    echo "</td>\n";
                    echo "</tr>\n";

                    echo "<form method=post name=frm_extrato action=\"$PHP_SELF\">";
                    echo "<tr class='menu_top'>\n";

                    echo "<td align=\"center\">";
                    fecho("extrato",$con,$cook_idioma);
                    echo "</td>\n";

                    echo "<td align='center'>";
                    fecho("aprovado",$con,$cook_idioma);
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

                    echo "<td align=\"center\">";
                    fecho("pecas",$con,$cook_idioma);
                    echo "</td>\n";

                    if($login_fabrica==19) {
                        echo "<td align=\"center\">";
                        fecho("instalacao",$con,$cook_idioma);
                        echo "</td>\n";
                    }
                    echo "<td align=\"center\">";
                    fecho("total",$con,$cook_idioma);
                    echo "</td>\n";
                    if($login_fabrica==19){
                        #echo "<td align=\"center\" nowrap>+ AVULSO</td>\n";
                        echo "<td align=\"center\" nowrap>(*)";
                        fecho("previsao",$con,$cook_idioma);
                        echo "</td>\n";
                        echo "<td align=\"center\" nowrap>PAGAMENTO </td>\n";
                    }

                    echo "<td align=\"center\">&nbsp;</td>\n";
                    echo "<td align=\"center\">&nbsp;</td>\n";

                    echo "</tr>\n";
                    for ($i = 0 ; $i < pg_num_rows($res) ; $i++){
                        $xmao_de_obra            = 0;
                        $posto                   = trim(pg_fetch_result($res,$i,'posto'));
                        $posto_codigo            = trim(pg_fetch_result($res,$i,'codigo_posto'));
                        $posto_nome              = trim(substr(pg_fetch_result($res,$i,'nome'),0,25));
                        $extrato                 = trim(pg_fetch_result($res,$i,'extrato'));
                        $data_geracao            = trim(pg_fetch_result($res,$i,'data_geracao'));
                        $pedido_via_distribuidor = trim(pg_fetch_result($res,$i,'pedido_via_distribuidor'));
                        $data_extrato            = trim(pg_fetch_result($res,$i,'data_extrato'));
                        $mao_de_obra             = trim(pg_fetch_result($res,$i,'mao_de_obra'));
                        $mao_de_obra_postos      = trim(pg_fetch_result($res,$i,'mao_de_obra_postos'));
                        $extra_mo                = trim(pg_fetch_result($res,$i,'extra_mo'));
                        $adicional               = trim(pg_fetch_result($res,$i,'adicional'));
                        $pecas                   = trim(pg_fetch_result($res,$i,'pecas'));
                        $extra_pecas             = trim(pg_fetch_result($res,$i,'extra_pecas'));
                        $extra_instalacao        = trim(pg_fetch_result($res,$i,'extra_instalacao'));
                        $extrato                 = trim(pg_fetch_result($res,$i,'extrato'));
                        $estado                  = trim(pg_fetch_result($res,$i,'estado'));
                        $protocolo               = trim(pg_fetch_result($res,$i,'protocolo'));
                        $avulso                  = trim(pg_fetch_result($res,$i,'avulso'));
                        $previsao                = trim(pg_fetch_result($res,$i,'previsao'));
                        $pagamento               = trim(pg_fetch_result($res,$i,'pagamento'));
                        $data_aprovado           = trim(pg_fetch_result($res,$i,'aprovado'));


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


                        $data_geracao;

                        $cor = ($i % 2 == 0) ? '#F1F4FA' : '#F7F5F0';
                        $btn = ($i % 2 == 0) ? 'azul' : 'amarelo';

                        ##### LAN√É‚Ä°AMENTO DE EXTRATO AVULSO - IN√É¬çCIO #####
                        if (strlen($extrato) > 0) {
                            $sql = "SELECT count(*) as existe
                                    FROM   tbl_extrato_lancamento
                                    WHERE  extrato = $extrato
                                    and    posto   = $login_posto
                                    and    fabrica = $login_fabrica";
                            $res_avulso = pg_query($con,$sql);

                            if (@pg_num_rows($res_avulso) > 0) {
                                if (@pg_fetch_result($res_avulso, 0, 'existe') > 0) $cor = "#FFE1E1";
                            }
                        }
                        ##### LAN√É‚Ä°AMENTO DE EXTRATO AVULSO - FIM  HD 5630#####

                        ##### VERIFICA NOVO PROCESSO LGR - IN√É¬çCIO #####
                        $novo_processo_lgr= "";
                        if (strlen($extrato) > 0 AND $login_fabrica == 24) {
                            $sql = "SELECT CASE WHEN data_geracao > '2007-12-31'::date THEN 1 ELSE 0 END AS sim_nao
                                    FROM   tbl_extrato
                                    WHERE  extrato = $extrato
                                    and    posto   = $login_posto
                                    and    fabrica = $login_fabrica";
                            $res_lgr = pg_query($con,$sql);
                            if (@pg_num_rows($res_lgr) > 0) {
                                $novo_processo_lgr = pg_fetch_result($res_lgr, 0, 'sim_nao');
                            }
                        }
                        ##### VERIFICA NOVO PROCESSO LGR  - HD 5630 FIM #####

                        echo "<tr class='table_line' style='background-color: $cor;'>\n";
                        echo "<td align='left' style='padding-left:7px;'>";
                        echo ($login_fabrica == 1) ? $protocolo : (($login_fabrica <> 19) ? $extrato : "");
                        echo "</td>\n";

                        echo "<td align='left' nowrap><acronym title='aprovado_$data_aprovado'>" . $data_aprovado . "</acronym></td>\n";

                        echo ($login_fabrica == 19) ? "<td align='left' style='padding-left:7px;'>$protocolo</td>\n" : "";
                        echo "<td align='left' nowrap>$posto_codigo - $posto_nome</td>\n";
                        echo ($tipo_posto == "D") ? "<td align='center'><a href='extrato_distribuidor.php?data=$data_extrato'>$data_geracao</a></td>\n" : "<td align='center'>$data_geracao</td>\n";


                    if ($login_fabrica == 19) {
                        #$xvrmao_obra = pg_fetch_result($res,$i,'extra_mo') ;
                        #$pecas       = pg_fetch_result($res,$i,'extra_pecas') ;
                        $instalacao  = pg_fetch_result($res,$i,'extra_instalacao') ;
                        $total       = $pecas + $xvrmao_obra ;
                    }

                    echo "<td align='right'  style='padding-right:3px;' nowrap>". number_format($xvrmao_obra,2,",",".") ."</td>\n";
                    echo "<td align='right'  style='padding-right:3px;' nowrap>". number_format($pecas,2,",",".") ."</td>\n";
                    if($login_fabrica==19){
                        echo "<td align='right'  style='padding-right:3px;' nowrap>". number_format($instalacao,2,",",".") ."</td>\n";
                    }
                    echo "<td align='right'  style='padding-right:3px;' nowrap>". number_format($total,2,",",".") ."</td>\n";
                    if($login_fabrica==19){
                        #echo "<td align='right'  style='padding-right:3px;' nowrap>".number_format($avulso,2,",",".")."</td>\n";
                        echo "<td align='right'  style='padding-right:3px;' nowrap>$previsao</td>\n";
                        echo "<td align='right'  style='padding-right:3px;' nowrap>$pagamento</td>\n";
                    }
                    if($sistema_lingua == 'ES'){
                        echo "<td><a href='os_extrato_detalhe.php?extrato=$extrato&posto=$posto'><img src='imagens/btn_detallar_".$btn.".gif'></a></td>\n";
                    }else{
                        echo "<td><a href='os_extrato_detalhe.php?extrato=$extrato&posto=$posto'><img src='imagens/btn_detalhar_".$btn.".gif'></a></td>\n";
                        if($login_fabrica<>15){
                            if ( in_array($login_fabrica, array(11,172)) ){
                                echo "<td><a href='extrato_posto_devolucao_lenoxx_itens.php?extrato=$extrato'><img src='imagens/btn_pecasretornaveis_".$btn.".gif'></a></TD>\n";
                            }else{
                                if ($login_fabrica==24 AND $novo_processo_lgr == '1'){
                                    echo "<td><a href='os_extrato_pecas_retornaveis_suggar_lgr.php?extrato=$extrato'><img src='imagens/btn_pecasretornaveis_".$btn.".gif'></a></TD>\n";
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
    }else{ //hd_chamado=2881143
        echo "<table width='580' align='center' border='0' cellspacing='1' cellpadding='1'>\n";
            echo "<tr class='table_line'>\n";
                echo "<td align=\"center\">";
                echo "<div class='error'>$msg_erro</div>";
                echo "</td>\n";
                echo "</tr>\n";
                echo "<tr>\n";
                echo "<td align=\"center\">\n";
                echo "<br><a href='menu_os.php'><img src='imagens/btn_voltar.gif'></a>";
                echo "</td>\n";
            echo "</tr>\n";
        echo "</table>\n";
    }
} else { // OUTROS FABRICANTES
    # -- EXIBE VALORES DETALHADOS DOS EXTRATOS -- #

	if(strlen($_POST['buscarOS']) > 0){

		$sqlOS = "SELECT os FROM tbl_os WHERE fabrica = $login_fabrica AND sua_os = '{$_POST['buscarOS']}'";
		$resOS = pg_query($con,$sqlOS);
		$buscarOS = pg_fetch_result($resOS,0,'os');

    }
    #HD 14263 - 124413
    # Desabilitei o SUM, pois a intelbras / lenoxx armazena os valores no extrato
    if (in_array($login_fabrica,array(7,11,14,25,51,80,47,172))) {

        $cond_pesquisa_fabrica = (in_array($login_fabrica, array(11,172))) ? " tbl_posto_fabrica.fabrica IN (11,172) " : " tbl_posto_fabrica.fabrica = $login_fabrica ";

        if(in_array($login_fabrica, array(11,172))){
            $joinfabricaextrato .= "JOIN tbl_fabrica ON tbl_fabrica.fabrica = tbl_extrato.fabrica";
            $camponomefabrica = " tbl_fabrica.nome as nome_fabrica_extrato,  ";
        }

        $sql = "SELECT DISTINCT
                tbl_posto_fabrica.codigo_posto                                                   ,
                $camponomefabrica
                tbl_posto.nome                                                                   ,
                tbl_extrato.posto                                                                ,
                tbl_extrato.extrato                                                              ,
                to_char(tbl_extrato.data_geracao, 'YYYY-MM-DD')                AS data_extrato   ,
                to_char(tbl_extrato.data_geracao, 'DD/MM/YYYY')                AS data_geracao   ,
                to_char(tbl_extrato.liberado, 'DD/MM/YYYY')                    AS liberado       ,
                to_char(tbl_extrato.previsao_pagamento,'DD/MM/YYYY')           AS previsao_pagamento,
                to_char(tbl_extrato_pagamento.data_pagamento,'DD/MM/YYYY')     AS data_pagamento ,
                to_char(tbl_extrato.aprovado,'DD/MM/YYYY')                     AS aprovado       ,
                tbl_extrato_pagamento.nf_autorizacao                                             ,
                tbl_extrato.mao_de_obra                                                          ,
                tbl_extrato.mao_de_obra_postos                                                   ,
                /* SUM (tbl_os_extra.mao_de_obra + tbl_os_extra.taxa_visita + tbl_os_extra.deslocamento_km) AS extra_mo  ,*/
                tbl_extrato.mao_de_obra                                        AS extra_mo       ,
                tbl_extrato.protocolo                                                            ,
                0                                                              AS adicional      ,
                tbl_extrato.pecas                                                                ,
                tbl_extrato.deslocamento                                     AS deslocamento_km  ,
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
                        AND {$cond_pesquisa_fabrica}
            JOIN        tbl_posto            ON tbl_extrato.posto         = tbl_posto.posto
	    LEFT JOIN   tbl_extrato_pagamento ON tbl_extrato.extrato = tbl_extrato_pagamento.extrato";
	 if($_POST['buscarOS'] != ""){
	   $sql .= "
            JOIN   tbl_os_extra         ON tbl_os_extra.extrato      = tbl_extrato.extrato
	    JOIN   tbl_os               ON tbl_os.os                 = tbl_os_extra.os";
	 }

     $cond_pesquisa_fabrica_extrato = (in_array($login_fabrica, array(11,172))) ? " tbl_extrato.fabrica IN (11,172) " : " tbl_extrato.fabrica = {$login_fabrica} ";




	    $sql .= " $joinfabricaextrato

    /*      LEFT JOIN   tbl_produto          ON tbl_produto.produto       = tbl_os.produto
            LEFT JOIN   tbl_familia          ON tbl_familia.familia       = tbl_produto.familia*/
            LEFT JOIN   tbl_extrato_extra    ON tbl_extrato.extrato       = tbl_extrato_extra.extrato
            WHERE       {$cond_pesquisa_fabrica_extrato} ";

        if ($tipo_posto == "P") $sql .= "AND tbl_extrato.posto   = $login_posto ";
        else                    $sql .= "AND (tbl_posto_fabrica.distribuidor = $login_posto OR tbl_posto_fabrica.posto = $login_posto) ";

        //HD 205958: O extrato deve ser visualizado pelo pospto quando tiver campo tbl_extrato.liberado NOT NULL
        //           o campo tbl_extrato.aprovado n„o deve influenciar nesta rotina
        if (in_array($login_fabrica, $fabricas_acerto_extrato)) {
            $sql .="AND         tbl_extrato.posto   = $login_posto
                    AND         tbl_extrato.liberado IS NOT NULL ";
        }
        // HD 205958: Rotina antiga
        else {
            $sql .="AND         tbl_extrato.posto   = $login_posto
                    AND         tbl_extrato.aprovado IS NOT NULL
                    AND         tbl_extrato.liberado IS NOT NULL ";
        }

        // HD 2219464
        if(isset($_POST['buscarOS'])){
            if($_POST['buscarOS'] != ""){
					$sql .= " AND tbl_os.sua_os = '".$_POST['buscarOS']."'";
            }
        }

        $sql .= "/*GROUP BY   tbl_posto_fabrica.codigo_posto            ,
                        $camponomefabrica
                        tbl_posto.nome                            ,
                        tbl_extrato.posto                         ,
                        tbl_extrato.extrato                       ,
                        tbl_extrato.data_geracao                  ,
                        tbl_extrato.liberado                      ,
                        tbl_extrato_pagamento.data_pagamento      ,
                        tbl_extrato_pagamento.nf_autorizacao      ,
                        tbl_posto_fabrica.pedido_via_distribuidor ,
                        tbl_extrato.mao_de_obra                   ,
                        tbl_extrato.mao_de_obra_postos            ,
                        tbl_extrato.pecas                         ,
                        tbl_extrato.avulso                        ,
                        tbl_extrato.total                         ,
                        tbl_extrato.protocolo                     ,
                        tbl_extrato.admin_lgr                     ,
                        tbl_posto.estado                          ,
                        tbl_extrato_extra.nota_fiscal_devolucao   ,
                        tbl_extrato_extra.nota_fiscal_mao_de_obra ,
                        tbl_extrato_extra.exportado*/
                ORDER BY tbl_extrato.extrato DESC";
    }else{
        if($login_fabrica==6){
            $sql_peca=" ,tbl_extrato_extra.pecas_devolvidas ";
        }
        if ($login_fabrica == 42) {
            $sql_conf = " (SELECT conferido from tbl_extrato_status where fabrica = {$login_fabrica} order by data desc limit 1) as conferido, ";
            $sql = "SELECT DISTINCT tbl_extrato.extrato,";
        }else{
            $sql = "SELECT tbl_extrato.extrato,";
        }
        $sql .= " tbl_posto_fabrica.codigo_posto,
                tbl_extrato.valor_adicional,
                tbl_posto.nome,
                tbl_extrato.posto,
                TO_CHAR(tbl_extrato.data_recebimento_nf, 'YYYY-MM-DD')     AS data_recebimento_nf,
                $sql_conf
                TO_CHAR(tbl_extrato.data_geracao, 'YYYY-MM-DD')            AS data_extrato,
                TO_CHAR(tbl_extrato.data_geracao, 'DD/MM/YYYY')            AS data_geracao,
                TO_CHAR(tbl_extrato.liberado, 'DD/MM/YYYY')                AS liberado,
                TO_CHAR(tbl_extrato_pagamento.data_pagamento,'DD/MM/YYYY') AS data_pagamento,
                TO_CHAR(tbl_extrato.previsao_pagamento,'DD/MM/YYYY')       AS previsao_pagamento,
                tbl_extrato_pagamento.nf_autorizacao,
		tbl_extrato_pagamento.data_entrega_financeiro,
                tbl_extrato_pagamento.justificativa,
                tbl_extrato.mao_de_obra,
                tbl_extrato.mao_de_obra_postos,
             /* SUM (tbl_os_extra.mao_de_obra + tbl_os_extra.taxa_visita + tbl_os_extra.deslocamento_km) AS extra_mo, */
                TO_CHAR(tbl_extrato.aprovado, 'DD/MM/YYYY')                AS aprovado,
                tbl_extrato.mao_de_obra                                    AS extra_mo,
                tbl_extrato.protocolo,
                0                                                          AS adicional,
                tbl_extrato.pecas,
                tbl_extrato.avulso,
                tbl_extrato.admin_lgr,
             /* SUM (tbl_os_extra.custo_pecas)                             AS extra_pecas, */
                tbl_extrato.pecas                                          AS extra_pecas,
                tbl_extrato.deslocamento                                   AS deslocamento_km,
                tbl_posto.estado,
                tbl_posto_fabrica.pedido_via_distribuidor,
                tbl_extrato.total,
		tbl_extrato.nf_recebida,
                tbl_extrato.bloqueado,
                tbl_extrato_extra.nota_fiscal_devolucao,
                tbl_extrato_extra.nota_fiscal_mao_de_obra,
                TO_CHAR(tbl_extrato_extra.data_entrega_transportadora, 'DD/MM/YYYY') AS data_entrega_transportadora,
                TO_CHAR(tbl_extrato_extra.exportado,                   'DD/MM/YYYY') AS exportado,
                TO_CHAR(tbl_extrato_extra.emissao_mao_de_obra,         'DD/MM/YYYY') AS emissao_mao_de_obra,
                tbl_extrato_extra.valor_total_extrato
                $sql_peca
            INTO TEMP tmp_os_extrato /* hd 39502 */
            FROM        tbl_extrato
            JOIN        tbl_posto_fabrica    ON tbl_extrato.posto         = tbl_posto_fabrica.posto
                                            AND tbl_posto_fabrica.fabrica = $login_fabrica
            JOIN        tbl_posto            ON tbl_extrato.posto         = tbl_posto.posto
	    LEFT JOIN   tbl_extrato_pagamento ON tbl_extrato.extrato = tbl_extrato_pagamento.extrato";

	    if(isset($_POST['buscarOS'])){
		 $sql .= " JOIN   tbl_os_extra         ON tbl_os_extra.extrato      = tbl_extrato.extrato
                   JOIN   tbl_os               ON tbl_os.os                 = tbl_os_extra.os";
	    }

        $cond_pesquisa_fabrica_extrato = (in_array($login_fabrica, array(11,172))) ? " tbl_extrato.fabrica IN (11,172) " : " tbl_extrato.fabrica = {$login_fabrica} ";


	    $sql .= "
    /*      LEFT JOIN   tbl_produto          ON tbl_produto.produto       = tbl_os.produto
            LEFT JOIN   tbl_familia          ON tbl_familia.familia       = tbl_produto.familia*/
            LEFT JOIN   tbl_extrato_extra    ON tbl_extrato.extrato       = tbl_extrato_extra.extrato
            WHERE       {$cond_pesquisa_fabrica_extrato} ";

        if ($tipo_posto == "P") $sql .= "AND tbl_extrato.posto   = $login_posto ";
        else                    $sql .= "AND (tbl_posto_fabrica.distribuidor = $login_posto OR tbl_posto_fabrica.posto = $login_posto) ";

        //HD 205958: O extrato deve ser visualizado pelo pospto quando tiver campo tbl_extrato.liberado NOT NULL
        //           o campo tbl_extrato.aprovado n„o deve influenciar nesta rotina
        if (in_array($login_fabrica, $fabricas_acerto_extrato)) {
            $sql .="
            AND         tbl_extrato.posto   = $login_posto
            AND         tbl_extrato.liberado IS NOT NULL
            ";
        }
        else {
            $sql .="
            AND         tbl_extrato.posto   = $login_posto
            AND         tbl_extrato.aprovado IS NOT NULL
            ";

            if ( $login_fabrica == 15 ) {
                $sql .= " AND tbl_extrato.liberado IS NOT NULL ";
            }
        }

        $sql .= "/*GROUP BY   tbl_posto_fabrica.codigo_posto            ,
                            tbl_posto.nome                            ,
                            tbl_extrato.posto                         ,
                            tbl_extrato.extrato                       ,
                            tbl_extrato.data_geracao                  ,
                            tbl_extrato.liberado                      ,
                            tbl_extrato_pagamento.data_pagamento      ,
                            tbl_extrato_pagamento.nf_autorizacao      ,
			    tbl_extrato_pagamento.data_entrega_financeiro,
                            tbl_posto_fabrica.pedido_via_distribuidor ,
                            tbl_extrato.mao_de_obra                   ,
                            tbl_extrato.mao_de_obra_postos            ,
                            tbl_extrato.pecas                         ,
                            tbl_extrato.avulso                        ,
                            tbl_extrato.total                         ,
                            tbl_extrato.protocolo                     ,
                            tbl_extrato.admin_lgr                     ,
                            tbl_posto.estado                          ,
                            tbl_extrato_extra.nota_fiscal_devolucao   ,
                            tbl_extrato_extra.nota_fiscal_mao_de_obra ,
                            tbl_extrato_extra.exportado*/ ";
        #HD 22758

        // HD 2219464
        if (isset($_POST['buscarOS'])) {
            if ($_POST['buscarOS'] != "") {
				$sql .= " AND tbl_os.sua_os = '".$_POST['buscarOS']."'";
            }
        }

        if($login_fabrica == 42){
            $sql .=" AND tbl_extrato.data_geracao > current_date - interval '6 months' ";
        }

        if ($login_fabrica == 24){
            $sql .= " ORDER BY tbl_extrato_pagamento.data_pagamento DESC, tbl_extrato.data_geracao DESC";
        }else{
            $sql .= " ORDER BY data_extrato DESC";
        }
        if($login_fabrica ==15 ){
            $sql .= " limit 3";
        }

    }

    $res = pg_query($con,$sql);

    /* hd 39502 */
    if ($login_fabrica==20) {
        $sql = "ALTER TABLE tmp_os_extrato ADD COLUMN total_cortesia DOUBLE PRECISION";
        $res = pg_query($con,$sql);
        $sql = "UPDATE tmp_os_extrato
                   SET total_cortesia = (
                        SELECT SUM(tbl_os.mao_de_obra) + SUM(tbl_os.pecas)
                          FROM tbl_os
                          JOIN tbl_os_extra USING(os)
                         WHERE extrato = tmp_os_extrato.extrato
                           AND tbl_os.tipo_atendimento = 16
                    )";
        $res = pg_query($con,$sql);
    }


    //hd 39502
    $sql = "SELECT * FROM tmp_os_extrato";
    $res = pg_query($con,$sql);

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
    if(in_array($login_fabrica, array(94,98,164, 176))){
        echo "<td align='center' width='16'><input type ='button' value='PeÁas para InspeÁ„o' onclick=\"window.open('lgr_vistoria_itens.php')\"></td>";
    }
    echo "</tr>";
    if($login_fabrica == 42){
        echo "<tr>";
        echo "<td align='center' width='16' bgcolor='PaleGreen'>&nbsp;</td>";
        echo "<td align='left'><font size=1><b>&nbsp;";
        echo "Extrato Pago";
        echo "</b></font></td>";
        echo "</tr>";
    }
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

    if( in_array($login_fabrica, array(11,172)) ){

        $cond_pesquisa_fabrica = (in_array($login_fabrica, array(11,172))) ? " tbl_os.fabrica IN (11,172) " : " tbl_os.fabrica = $login_fabrica ";
        $cond_pesquisa_fabrica_os_status = (in_array($login_fabrica, array(11,172))) ? " tbl_os_status.fabrica_status IN (11,172) " : " tbl_os_status.fabrica_status = $login_fabrica ";

        $sql = "SELECT tbl_os.os,tbl_os.sua_os, tbl_os.fabrica,
                TO_CHAR(tbl_os.data_fechamento, 'DD/MM/YYYY')             AS data_fechamento,
				tbl_os.mao_de_obra,    
                tbl_fabrica.nome as nome_fabrica,             
			    (select status_os from tbl_os_status where {$cond_pesquisa_fabrica_os_status} and tbl_os_status.os = tbl_os.os and status_os in (64,67,68,70,19,13,139,155) order by data desc limit 1) as status_os,
                tbl_os.posto
				into temp tmp_os_extrato_$login_posto
                FROM tbl_os
                JOIN tbl_os_extra ON tbl_os_extra.os = tbl_os.os AND tbl_os_extra.extrato IS NULL
                JOIN tbl_fabrica on tbl_os.fabrica = tbl_fabrica.fabrica 
                WHERE {$cond_pesquisa_fabrica} 
                AND tbl_os.excluida IS NOT TRUE
                AND tbl_os.posto = $login_posto
                AND    NOT (tbl_os.data_fechamento  IS NULL)
                AND    NOT (tbl_os.finalizada       IS NULL)
                AND    tbl_os.data_fechamento  > current_date - interval '12 months'
                AND tbl_os.mao_de_obra is not null
				AND    tbl_os.finalizada::date < current_date ;

				select * from tmp_os_extrato_$login_posto where (status_os not in (67,68,70) or status_os isnull)
";
        $resOS = pg_query($con,$sql);

        $os_sem_extrato = array();
        for($i=0; $i<pg_num_rows($resOS); $i++){
            $cor                = ($i % 2 == 0) ? '#F1F4FA' : "#F7F5F0";
            $sua_os             = pg_fetch_result($resOS, $i, 'sua_os');
            $os                 = pg_fetch_result($resOS, $i, 'os');
            $data_fechamento    = pg_fetch_result($resOS, $i, 'data_fechamento');
            $mao_de_obra        = pg_fetch_result($resOS, $i, 'mao_de_obra');
            $fabrica            = pg_fetch_result($resOS, $i, 'fabrica');
            $nome_fabrica       = pg_fetch_result($resOS, $i, 'nome_fabrica');

            $nome_fabrica = ($nome_fabrica == "Lenoxx")? "Aulik" : $nome_fabrica;

            $mao_de_obra2       = number_format($mao_de_obra,2,".",",");
            $mao_de_obra        = number_format($mao_de_obra,2,",",".");            

            $os_sem_extrato[$fabrica][$os]['cor']                = $cor;
            $os_sem_extrato[$fabrica][$os]['nome_fabrica']       = $nome_fabrica;
            $os_sem_extrato[$fabrica][$os]['os']                 = $os;
            $os_sem_extrato[$fabrica][$os]['mao_de_obra']        = $mao_de_obra;
            $os_sem_extrato[$fabrica][$os]['mao_de_obra2']       = $mao_de_obra2;
            $os_sem_extrato[$fabrica][$os]['sua_os']             = $sua_os;
            $os_sem_extrato[$fabrica][$os]['data_fechamento']    = $data_fechamento;
            
        }       

        if(!empty($os_sem_extrato[11])){
        echo "<br>";
        echo "<table width='580' height=16 border='0' cellspacing='0' cellpadding='0' align='center' >";
            echo "<tr class='menu_top' style='font-size:12px; line-height:20px'>";
                echo "<td colspan ='3' onclick='mostrar_os_calculadas($(this))' class='expandir' >Ordens de ServiÁo que ainda n„o entraram em extrato</td>";
            echo "</tr>";
            echo "<tr>";
                echo "<td>";
                    echo "<table id='os_calculadas' style='display:none' width='100%' border='0' cellspacing='0' cellpadding='0'>";
                        echo "<tr class='menu_top'  style='font-size:12px; line-height:20px'>";
                            echo "<td>OS</td>";
                            echo "<td>F·brica</td>";
                            echo "<td>Data do Fechamento</td>";
                            echo "<td>Valor</td>";
                        echo "</tr>";
                        foreach($os_sem_extrato[11] as $chave => $values){
                            echo "<tr class='table_line' style='font-size:11px; line-height:20px'>";
                                echo "<td align='center' style='background-color: $values[cor];'><a href='os_press.php?os=$os' target='_blank'>$values[sua_os]</a></td>";
                                echo "<td align='center' style='background-color: $values[cor];'>$values[nome_fabrica]</td>";
                                echo "<td align='center' style='background-color: $values[cor];'>$values[data_fechamento]</td>";
                                echo "<td align='center' style='background-color: $values[cor];'>R$ $values[mao_de_obra] </td>";
                            echo "</tr>";

                            $total_mao_de_obra += $values['mao_de_obra2'];
                        }
                        
                        $total_mao_de_obra  = number_format($total_mao_de_obra,2,",",".");
                        echo "<tr class='table_line' style='font-size:11px; line-height:20px'>";
                            echo "<td colspan='3' align='right'><b>Total:</b></td>";
                            echo "<td align='center'>R$ $total_mao_de_obra</td>";
                        echo "</tr>";
                        echo "<tr>";
                            echo "<td style='color:red; font-size:10px; text-align:center' colspan='4'>Para gerar extrato a soma dos valores das O.S tem que atingir o mÌnimo de R$100,00</td>";
                        echo "</tr>";
                    echo "</table>";
                echo "</td>";
            echo "</tr>";
        echo "</table>";
        }
       
        if(!empty($os_sem_extrato[172])){
            echo "<br>";
            echo "<table width='580' height=16 border='0' cellspacing='0' cellpadding='0' align='center' >";
                echo "<tr class='menu_top' style='font-size:12px; line-height:20px'>";
                    echo "<td colspan ='3' onclick='mostrar_os_calculadas_pacific($(this))' class='expandir' >Ordens de ServiÁo que ainda n„o entraram em extrato</td>";
                echo "</tr>";
                echo "<tr>";
                    echo "<td>";
                        echo "<table id='os_calculadas_pacific' style='display:none' width='100%' border='0' cellspacing='0' cellpadding='0'>";
                            echo "<tr class='menu_top'  style='font-size:12px; line-height:20px'>";
                                echo "<td>OS</td>";
                                echo "<td>F·brica</td>";
                                echo "<td>Data do Fechamento</td>";
                                echo "<td>Valor</td>";
                            echo "</tr>";

                            foreach($os_sem_extrato[172] as $chave => $values){
                                echo "<tr class='table_line' style='font-size:11px; line-height:20px'>";
                                    echo "<td align='center' style='background-color: $values[cor];'><a href='os_press.php?os=$os' target='_blank'>$values[sua_os]</a></td>";
                                    echo "<td align='center' style='background-color: $values[cor];'>$values[nome_fabrica]</td>";
                                    echo "<td align='center' style='background-color: $values[cor];'>$values[data_fechamento]</td>";
                                    echo "<td align='center' style='background-color: $values[cor];'>R$ $values[mao_de_obra] </td>";
                                echo "</tr>";

                                $total_mao_de_obra_pacific += $values['mao_de_obra2'];
                            }
                            $total_mao_de_obra_pacific = number_format($total_mao_de_obra_pacific,2,",",".");

                            echo "<tr class='table_line' style='font-size:11px; line-height:20px'>";
                                echo "<td colspan='3' align='right'><b>Total:</b></td>";
                                echo "<td align='center'>R$ $total_mao_de_obra_pacific</td>";
                            echo "</tr>";
                            echo "<tr>";
                                echo "<td style='color:red; font-size:10px; text-align:center' colspan='4'>Para gerar extrato a soma dos valores das O.S tem que atingir o mÌnimo de R$50,00</td>";
                            echo "</tr>";
                        echo "</table>";
                    echo "</td>";
                echo "</tr>";
            echo "</table>";
        }
    }

    echo "<br>";
    if($login_fabrica == 24){
        echo "<center><font size=1 face=verdana>* ";
        fecho("nao.emitir.nota.fiscal.no.caso.dos.extratos.avulso",$con,$cook_idioma);
        echo "</font></center>";
    }

    echo "<form method='post' name='frm_extrato' action=\"$PHP_SELF\">";
    ?>
        <table width="540" height=16 border="0" cellspacing="0" cellpadding="5" align="center">
            <tr>
                <td align="left"><font size=1 ><b>&nbsp;
                    <?php echo utf8_decode("Buscar por N√∫mero da OS "); ?>
                    </b></font>
                    <input type="text" name="buscarOS" id="buscarOS">
                </td>
                <td>
                    <input type="submit" name="btn_pesquisar" value="Pesquisar">
                </td>
                <td>
                    <input type="button" name="btn_listar_extratos" value="Listar Todos Extratos" onclick="listarTodosExtratos()">
                </td>
            </tr>
        </table>
    </form>
    </br>
    <?php

    echo "<table width='580' align='center' border='0' cellspacing='1' cellpadding='5'>\n";
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

	if (in_array($login_fabrica, [169,170])) {
            echo "<td align=\"center\">";
            fecho("status",$con,$cook_idioma);
            echo "</td>\n";
        }

        if (in_array($login_fabrica, [11,172])){
            echo "<td align='center'>";
            fecho("F·brica",$con,$cook_idioma);
            echo "</td>\n";
        }

        if (!in_array($login_fabrica, [169,170])){
            echo "<td align='center'>";
            fecho("aprovado",$con,$cook_idioma);
            echo "</td>\n";
        }

        if ($login_fabrica == 19) {
            echo "<td align='center'>";
            fecho("relatorio",$con,$cook_idioma);
            echo "</td>\n";
        }

        if( !in_array($login_fabrica, array(11,172)) ){
            echo "<td align='center'>";
            fecho ("posto",$con,$cook_idioma);
            echo "</td>\n";
        }

        echo "<td align='center'>";
        if (in_array($login_fabrica,array(7,11,25,51,45,169,170,172))) {
            fecho("Data do Extrato",$con,$cook_idioma);
        } else {
            fecho("geracao",$con,$cook_idioma);
        }
        echo "</td>\n";


        if($login_fabrica == 52){
            echo "<td align='center'>PED¡GIO</td>";
        }

        //hd-1059101
        if($login_fabrica == 42){
            echo "<td>Data Pagamento</td>";
        }
        if($login_fabrica == 86){
            echo "<td align=\"center\">";
            fecho("marca",$con,$cook_idioma);
            echo "</td>\n";
            echo "<td>DATA PAGAMENTO</td>";
        }

        if (!in_array($login_fabrica, array(143,169,170))) {
            echo "<td align='center'>";
            fecho("mao.de.obra",$con,$cook_idioma);
            echo "</td>\n";
        }

        if ($login_fabrica == 85){
            echo "<td align=\"center\">";
                echo "KM";
            echo "</td>\n";
            echo "<td align=\"center\">";
                echo "Ped·gio";
            echo "</td>\n";
        }

        if ($login_fabrica == 15){
            echo "<td align='center'>KM</td>\n";
        }

        if (isset($novaTelaOs)) {
            $nao_calcula_peca = \Posvenda\Regras::get("nao_calcula_peca", "mao_de_obra", $login_fabrica);
        }

        if((!in_array($login_fabrica,array(87,104,105,106,108,111,139)) && !isset($novaTelaOs)) || (isset($novaTelaOs) && !$nao_calcula_peca && !in_array($login_fabrica, array(169,170,191)))){
            echo "<td align='center'>";
                fecho("pecas",$con,$cook_idioma);
            echo "</td>\n";
        }

        if (in_array($login_fabrica, array(35,139))) {
            echo "<td align='center' nowrap>Valor <br /> Adicional</td>\n";
        }


        //hd 39502
        if($login_fabrica != 51){
            if ($login_fabrica == 20) {
                echo "<td align='center'>";
                fecho("total.cortesia",$con,$cook_idioma);
                echo "</td>\n";
                echo "<td align='center'>";
                fecho("total.geral",$con,$cook_idioma);
                echo "</td>\n";
            } else {
                if(!in_array($login_fabrica, array(14, 30, 45, 74)) && !isset($novaTelaOs)) {
                    echo ( in_array($login_fabrica, array(11,172)) ) ? "<td align='center' nowrap>+ AVULSO</td>\n" : "<td align='center'>TOTAL</td>\n";
                }
            }

            if (!in_array($login_fabrica, array(169,170)) && isset($novaTelaOs) && $fabrica_usa_valor_adicional) {
                echo "<td style='text-align: center;'>Valores Adicionais</td>";
            }

            if (!in_array($login_fabrica, array(169,170))) {
                echo ( in_array($login_fabrica, array(11,172)) ) ? "<td align='center'>TOTAL</td>\n" : "<td align='center' nowrap>+ AVULSO</td>\n";
            }

            if (isset($novaTelaOs)) {
                $nao_calcula_km = \Posvenda\Regras::get("nao_calcula_km", "mao_de_obra", $login_fabrica);
            }

            if (!in_array($login_fabrica, array(169,170)) && isset($novaTelaOs) && !$nao_calcula_km) {
                echo "<td align='center' nowrap>+ KM</td>\n";
            } else if (in_array($login_fabrica,array(30,50,52,72,74,90))) {
                echo "<td align='center' nowrap>+ KM</td>\n";
            }

            if (in_array($login_fabrica, array(30))) {
                echo "<td align='center'>TAXA ENTREGA</td>\n";
            }

            if(in_array($login_fabrica, array(30,74)) || isset($novaTelaOs)) {
                echo "<td align='center'>TOTAL</td>\n";
            }
            //echo ($login_fabrica==20) ? "<td align='center'>(*)Data do Fechamento</td>\n" : "<td align='center' nowrap>+ (*) Data do Fechamento</td>\n";
            echo ($login_fabrica == 90) ?"<td align='center' nowrap>+ Tx Visita</td>":"";
        }else{
            echo "<td align='center' nowrap>AVULSO</td>\n";
            echo "<td align='center'>TOTAL NF</td>\n";
        }

        if($login_fabrica == 45) {
            echo "<td align='center' nowrap>TOTAL GERAL</td>\n";
        }

	if (in_array($login_fabrica, [5,80,169,170])) {
            echo "<td align='center' nowrap>";
            fecho("nf.autorizacao",$con,$cook_idioma);
            echo "</td>\n";

            if (in_array($login_fabrica, [169,170])) {

                echo "<td align='center' nowrap>";
                fecho("Data AutorizaÁ„o",$con,$cook_idioma);
                echo "</td>\n";

                echo "<td align='center' nowrap>";
                fecho("nf.reprovada",$con,$cook_idioma);
                echo "</td>\n";

                echo "<td align='center' nowrap>";
                fecho("Data Reprova",$con,$cook_idioma);
                echo "</td>\n";

            }
        }

        if ( $login_fabrica != 104 && $login_fabrica != 105 && $login_fabrica != 87 ) {
            echo "<td align='center'";
            echo (!in_array($login_fabrica,array(11,45,50,15,80,172))) ? "nowrap>(*)" : ">Data de Pagamento";

            if ($login_fabrica == 5) {
                fecho("previsao",$con,$cook_idioma);
                echo "<br/>";
                fecho ("de.pagamento", $con, $cook_idioma);
            }
                if($login_fabrica == 20){
                    fecho("Data",$con,$cook_idioma);
                    echo"<br/>";
                    fecho("De.Fechamento", $con, $cook_idioma);

                } else {
                if(!in_array($login_fabrica,array(11,45,50,15,80,139,172))){
                    fecho("previsao",$con,$cook_idioma);
                }
            }
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

      	if(in_array($login_fabrica,[72,169,170])) echo "<td align='center' nowrap>Data de Pagamento</td>\n"; //HD 351922

        if($login_fabrica == 30){
        ?>
            <td align="center">N∫ OC</td>
            <td align="center">Data Pagamento</td>
            <td align="center">Valor Pago</td>
            <td align="center">Nota Fiscal</td>
            <td align="center">Valor descontado do Encontro de Contas</td>
            <!-- <td></td> -->
        <?php
        }

        echo "<td align='center' colspan='2'>";
        fecho("acoes",$con,$cook_idioma);
        echo "</td>\n";

        echo "</tr>\n";

        $total_pendencia = 0;

        $liberar_lgr = 3;

        if (in_array($login_fabrica, array(30))) {
            pg_prepare($con, 'taxa_entrega',"SELECT SUM(JSON_FIELD('taxa_entrega', tbl_os_campo_extra.campos_adicionais)::integer) AS taxa_entrega FROM tbl_os_campo_extra WHERE os IN(SELECT os FROM tbl_os_extra WHERE extrato = $1) AND fabrica = {$login_fabrica}");
        }

        for ($i = 0 ; $i < pg_num_rows($res) ; $i++){
            $xmao_de_obra                = 0;
            $posto                       = trim(pg_fetch_result($res,$i,'posto'));
            $posto_codigo                = trim(pg_fetch_result($res,$i,'codigo_posto'));
            $posto_nome                  = trim(pg_fetch_result($res,$i,'nome'));
            $extrato                     = trim(pg_fetch_result($res,$i,'extrato'));
            $data_geracao                = trim(pg_fetch_result($res,$i,'data_geracao'));
            $liberado                    = trim(pg_fetch_result($res,$i,'liberado'));
            $pedido_via_distribuidor     = trim(pg_fetch_result($res,$i,'pedido_via_distribuidor'));
            $data_extrato                = trim(pg_fetch_result($res,$i,'data_extrato'));
            $mao_de_obra                 = trim(pg_fetch_result($res,$i,'mao_de_obra'));
            $mao_de_obra_postos          = trim(pg_fetch_result($res,$i,'mao_de_obra_postos'));
            $extra_mo                    = trim(pg_fetch_result($res,$i,'extra_mo'));
            $adicional                   = trim(pg_fetch_result($res,$i,'adicional'));
            $pecas                       = ($login_fabrica == 40) ? 0 : trim(pg_fetch_result($res,$i,'pecas'));
            $deslocamento_km             = trim(pg_fetch_result($res,$i,'deslocamento_km'));
            $extra_pecas                 = trim(pg_fetch_result($res,$i,'extra_pecas'));
            $extrato                     = trim(pg_fetch_result($res,$i,'extrato'));
            $estado                      = trim(pg_fetch_result($res,$i,'estado'));
            $avulso                      = trim(pg_fetch_result($res,$i,'avulso'));
            $protocolo                   = trim(pg_fetch_result($res,$i,'protocolo'));
            $admin_lgr                   = trim(pg_fetch_result($res,$i,'admin_lgr')); // HD 6000
            $data_pagamento              = trim(pg_fetch_result($res,$i,'data_pagamento'));
            $nf_autorizacao              = trim(pg_fetch_result($res,$i,'nf_autorizacao'));//HD 9982
            $nota_fiscal_devolucao       = trim(pg_fetch_result($res,$i,'nota_fiscal_devolucao'))   ;
            $nota_fiscal_mao_de_obra     = trim(pg_fetch_result($res,$i,'nota_fiscal_mao_de_obra')) ;
            $data_entrega_transportadora = trim(pg_fetch_result($res,$i,'data_entrega_transportadora')) ;
            $previsao_pagamento          = trim(pg_fetch_result($res,$i,'exportado')) ;
            $previsao_pagamento_extrato  = trim(pg_fetch_result($res,$i,'previsao_pagamento')) ;
            $emissao_mao_de_obra         = trim(pg_fetch_result($res,$i,'emissao_mao_de_obra')) ;
            $valor_total_extrato         = trim(pg_fetch_result($res,$i,'valor_total_extrato')) ;
            $aux_valor_total             = trim(pg_fetch_result($res,$i,'total')) ;
            $valor_adicional             = trim(pg_fetch_result($res,$i,'valor_adicional'));
            $data_aprovado               = trim(pg_fetch_result($res,$i,'aprovado')); // HD 2219464
	        $nf_recebida                 = trim(pg_fetch_result($res,$i,'nf_recebida'));
            $bloqueado                   = trim(pg_fetch_result($res, $i, 'bloqueado'));
            $data_entrega_financeiro     = trim(pg_fetch_result($res, $i, 'data_entrega_financeiro'));
            $justificativa               = trim(pg_fetch_result($res, $i, 'justificativa'));

            if(in_array($login_fabrica, [11,172])){
                $nome_fabrica_extrato    = trim(pg_fetch_result($res,$i,'nome_fabrica_extrato')) ;

                $nome_fabrica_extrato = ($nome_fabrica_extrato == "Lenoxx")? "Aulik" : $nome_fabrica_extrato;
            }

            if($login_fabrica == 52){
                $sql_pega_pedagio = "SELECT sum(tbl_os.pedagio) as pedagio from tbl_os
                                    INNER JOIN tbl_os_extra on tbl_os_extra.os = tbl_os.os
                                    WHERE tbl_os_extra.extrato = $extrato
                                        ";
                $res_pega_pedagio = pg_query($con, $sql_pega_pedagio);
                for($a=0; $a<pg_num_rows($res_pega_pedagio); $a++){
                    $pedagio =  pg_fetch_result($res_pega_pedagio, $a, 'pedagio');
                }
            }

            if ($login_fabrica==85) {

                $sql_km_pedagio = "SELECT   SUM(tbl_os.pedagio) as pedagio,
                                SUM(tbl_os.qtde_km_calculada) as qtde_km_calculada
                        FROM    tbl_os
                        LEFT JOIN tbl_os_extra on tbl_os_extra.os = tbl_os.os
                        WHERE tbl_os_extra.extrato = $extrato";
                $res_km_pedagio    = pg_query($con,$sql_km_pedagio);
                $pedagio           = (pg_num_rows($res_km_pedagio)>0) ? trim(pg_fetch_result($res_km_pedagio,0,'pedagio')) : 0;
                $qtde_km_calculada = (pg_num_rows($res_km_pedagio)>0) ? trim(pg_fetch_result($res_km_pedagio,0,'qtde_km_calculada')) : 0;;

            }

            if ($login_fabrica == 6) {
                $pecas_devolvidas = trim(pg_fetch_result($res,$i,'pecas_devolvidas')) ;
            }
            if ($login_fabrica == 45) { // HD 50104
                $total_geral = trim(pg_fetch_result($res,$i,'total')) ;
            }
            //hd 39502
            if ($login_fabrica == 20) {
                $total_cortesia = trim(pg_fetch_result($res,$i,'total_cortesia')) ;
            }

            $sqlX = "SELECT TO_CHAR (tbl_extrato_financeiro.previsao,'DD/MM/YYYY') ,TO_CHAR (tbl_extrato_financeiro.pagamento,'DD/MM/YYYY') FROM tbl_extrato_financeiro WHERE extrato = $extrato";
            $resX = pg_query($con,$sqlX);
            $previsao = trim(@pg_fetch_result($resX,0,0));
            $pagamento = trim(@pg_fetch_result($resX,0,1));
            $verificacao = '1';

            if ( in_array($login_fabrica, array(11,172)) ) {
                $sql = "SELECT  CASE WHEN data_geracao > '2008-08-01'::date THEN '1' ELSE '0' END
                        FROM tbl_extrato
                        WHERE extrato = $extrato ";
                $resX = pg_query($con,$sql);
                $verificacao = pg_fetch_result($resX,0,0);
            }
            if ($login_fabrica == 51) {
                $sql = "SELECT  CASE WHEN data_geracao > '2008-10-30'::date THEN '1' ELSE '0' END
                        FROM tbl_extrato
                        WHERE extrato = $extrato ";
                $resX = pg_query($con,$sql);
                $verificacao = pg_fetch_result($resX,0,0);
            }

            if ($login_fabrica == '50') {
                $verificacao = '0';
            }

            if ( ($login_fabrica == 2 && $Login_posto==6359) || in_array($login_fabrica, array(7,11,25,43,50,51,80,101,172)) ){
                $msg_notas = "";
                $msg_mes_anterior = "";

                if ($login_fabrica == '50') {
                    /**
                     * @var string $data_corte
                     *  Data da efetivaÁ„o (07/03/2014) do HD 1418436 + 15 dias
                     */
                    $data_corte = '2014-03-22';
                } else {
                    $data_corte = '2007-11-01';
                }

                ## VerificaÁ„o do MÍs Anterior
                ## Verifica se tem extrato no mÍs anterior e se foi digitado as notas de devoluÁ„o
                ## V·lido apartir de data_geracao > '2007-12-01'
                $sqlConf = "
                            SELECT extrato,admin_lgr
                            FROM tbl_extrato
                            WHERE fabrica    = $login_fabrica
                            AND posto        = $login_posto
                            AND extrato      < $extrato
                            AND data_geracao > '$data_corte'
                            AND liberado    IS NOT NULL
                            ORDER BY data_geracao DESC
                            LIMIT 1";
                $resConf = pg_query($con,$sqlConf);
                if (pg_num_rows($resConf)>0){
                    $admin_lgr   = trim(pg_fetch_result($resConf,0,'admin_lgr'));
                    $lgr_extrato = trim(pg_fetch_result($resConf,0,'extrato'));
                    # Verifica se as notas de devoluÁ„o do Mes anterior foi recebido pela Fabrica
                    $sqlConf = "SELECT  faturamento,
                                        nota_fiscal,
                                        emissao - CURRENT_DATE AS dias_emitido,
                                        conferencia,
                                        movimento,
                                        devolucao_concluida
                                FROM tbl_faturamento
                                WHERE fabrica         = $login_fabrica
                                AND distribuidor      = $login_posto
                                AND extrato_devolucao = $lgr_extrato
                                AND posto             IS NOT NULL
                                ";
                    $resConf = pg_query($con,$sqlConf);
                    $notas_array = array();
                    $msg_notas = "";

                        if (pg_num_rows($resConf)>0){
                            for ( $w=0; $w < pg_num_rows($resConf); $w++ ){
                                $fat_faturamento  = trim(pg_fetch_result($resConf,$w,'faturamento'));
                                $fat_nota_fiscal  = trim(pg_fetch_result($resConf,$w,'nota_fiscal'));
                                $fat_dias_emitido = trim(pg_fetch_result($resConf,$w,'dias_emitido'));
                                $fat_conferencia  = trim(pg_fetch_result($resConf,$w,'conferencia'));
                                $fat_movimento    = trim(pg_fetch_result($resConf,$w,'movimento'));
                                $fat_concluido    = trim(pg_fetch_result($resConf,$w,'devolucao_concluida'));

                                // $admin_lgr -> se a F·brica liberou o mes anterior, deixa digitar este mes
                                // $fat_movimento != 'NAO_RETOR.' -> nao exige conferencia caso nao for conferida NF de peÁas nao retornaveis - HD 13450
                                if (strlen($admin_lgr)==0 AND strlen($fat_conferencia)==0 AND $fat_concluido!='t' AND $fat_movimento != 'NAO_RETOR.'){
                                    array_push($notas_array,$fat_nota_fiscal);
                                }
                            }
                        }

                        #Dynacom nao tem conferencia de NF - HD12684
                        #Gmaa nao faz tambÈm!!!!
                        if ($login_fabrica==2 or $login_fabrica == 51){
                            $notas_array = array();
                        }


                        if (count($notas_array)>0 OR pg_num_rows($resConf)==0){
                            if (count($notas_array)>0){
                                #HD 174349
                                if($login_fabrica<>43){
                                    $msg_mes_anterior = "<a href=\"$PHP_SELF?ajax=true&extrato=$lgr_extrato&status=confirmada&nf=".implode(",",$notas_array)."&height=240&width=320\"  title=\"NF n„o confirmada\" class=\"thickbox\">".traduz("extrato.bloqueado",$con,$cook_idioma)."</a>";
                                }
                            }else{
								if($login_fabrica == 50) {
									$joinDev= "JOIN tbl_os_item ON tbl_os_item.pedido = tbl_faturamento_item.pedido and tbl_os_item.peca = tbl_faturamento_item.peca and peca_obrigatoria " ;
								}

                                $sqlConf = "SELECT  faturamento
                                    FROM tbl_faturamento
                                    JOIN tbl_faturamento_item USING(faturamento)
									JOIN tbl_peca             USING(peca)
									JOIN tbl_extrato ON tbl_faturamento.extrato_devolucao = tbl_extrato.extrato
									$joinDev
                                    WHERE tbl_faturamento.fabrica = $login_fabrica
                                    AND   tbl_faturamento.posto   = $login_posto
                                    AND   tbl_faturamento.extrato_devolucao = $lgr_extrato
									";
								if($login_fabrica == 50) {
									$sqlConf .= " and tbl_faturamento.emissao > tbl_extrato.data_geracao - interval '6 months' ";
								}
                                if ($verificacao=='1'){
                                    if( in_array($login_fabrica, array(11,172)) ){
                                        $sqlConf .= "AND    (tbl_peca.devolucao_obrigatoria IS TRUE) "; /*HD 49036*/
                                    }else{
                                        $sqlConf .= "AND    (tbl_peca.devolucao_obrigatoria IS TRUE OR tbl_peca.produto_acabado IS TRUE) ";
                                    }
                                }
                                $resConf = pg_query($con,$sqlConf);
                                if (pg_num_rows($resConf)> 0){
                                    $msg_mes_anterior = "<a href=\"$PHP_SELF?ajax=true&extrato=$lgr_extrato&status=anterior&height=240&width=320\"  title=\"NF n„o confirmada\" class=\"thickbox\">".traduz("extrato.bloqueado",$con,$cook_idioma)."</a>";
                                }

                            }
                        }
                }

                if($login_fabrica == 50 && strlen($msg_mes_anterior) > 0){

                    if($i < $liberar_lgr && strtotime(date("Y-m-d")) >= strtotime("2016-08-01")){
                        $msg_mes_anterior = "";
                    }else{

                        $sql_lgr_provisorio = "SELECT extrato FROM tbl_extrato WHERE extrato = {$extrato} AND admin_libera_pendencia NOTNULL AND data_libera_pendencia NOTNULL";
                        $res_lgr_provisorio = pg_query($con, $sql_lgr_provisorio);

                        if(pg_num_rows($res_lgr_provisorio) > 0){
                            $msg_mes_anterior = "";
                        }
                    }

                }

                /*PARA A HBTECH √É‚Ä∞ FATURADO PELA TELECONTROL - DISTRIB*/
                if($login_fabrica == 25 or $login_fabrica == 51){
                    #verifica se tem peÁas de devoluÁ„o em garantia
                    $sqlLgr = "SELECT count(*)
                            FROM tbl_faturamento
                            JOIN tbl_faturamento_item USING(faturamento)
                            JOIN tbl_peca             USING(peca)
                            WHERE  tbl_faturamento.fabrica           = $login_fabrica
                            AND    tbl_faturamento.extrato_devolucao = $extrato
                            AND    (tbl_faturamento.cfop LIKE '59%' OR tbl_faturamento.cfop LIKE '69%') ";
                    if ($verificacao=='1'){
                        $sqlLgr .= "AND    (tbl_peca.devolucao_obrigatoria IS TRUE OR tbl_peca.produto_acabado IS TRUE) ";
                    }
                    $resLGR = pg_query($con,$sqlLgr);
                    $qtde_devolucao = trim(@pg_fetch_result($resLGR,0,0));
                }else{
                    #verifica se tem peÁas de devoluÁ„o em garantia
                    $sqlLgr = "SELECT count(*)
                            FROM tbl_faturamento
                            JOIN tbl_faturamento_item USING(faturamento)
                            JOIN tbl_peca             USING(peca)
                            WHERE  tbl_faturamento.fabrica           = $login_fabrica
                            AND    tbl_faturamento.extrato_devolucao = $extrato
                            AND    tbl_faturamento.distribuidor IS NULL
                            AND     (tbl_faturamento.cfop LIKE '59%' OR tbl_faturamento.cfop LIKE '69%')
                            ";

                    if ($verificacao=='1'){
                        if( in_array($login_fabrica, array(11,172)) ){
                            $sqlLgr .= "AND    (tbl_peca.devolucao_obrigatoria IS TRUE) "; /*HD 49036*/
                        }else{
                            $sqlLgr .= "AND    (tbl_peca.devolucao_obrigatoria IS TRUE OR tbl_peca.produto_acabado IS TRUE) ";
                        }
                    }
                    $resLGR = pg_query($con,$sqlLgr);
                    $qtde_devolucao = trim(@pg_fetch_result($resLGR,0,0));
                }

                $devolveu_pecas = "nao";

                #Posto Lenoxx
                $posto_da_fabrica = "20321";

                if ($login_fabrica == 2){
                        #HD 31407
                    $posto_da_fabrica = "4365,28408";
                }

                if ($login_fabrica==7){
                    $posto_da_fabrica = "27808"; #Filizola Matriz
                }

                if ( in_array($login_fabrica, array(11,172)) ){
                    $posto_da_fabrica = "20321";
                }

                if ($login_fabrica == 25 or $login_fabrica == 51) {
                    $posto_da_fabrica = "4311";
                }
                if($login_fabrica == 43) {
                    $posto_da_fabrica = "36522";
                }
                if($login_fabrica == 80) {
                    $posto_da_fabrica = "40222";
                }

                # Verifica se j· foi digitada
                $sqlLgr = "SELECT   extrato_devolucao,
                                    emissao,
                                    nota_fiscal
                        FROM tbl_faturamento
                        WHERE distribuidor      = $login_posto
                        AND   posto             in ($posto_da_fabrica)
                        AND   extrato_devolucao = $extrato
                        AND   fabrica           = $login_fabrica
                        AND  cancelada          IS NULL";
                $resLGR = pg_query($con,$sqlLgr);
                if (pg_num_rows($resLGR)>0){
                    $devolveu_pecas = "sim";
                }

                # Verifica tem nota recebida PARCIAL
                $sqlConf = "SELECT  faturamento,
                                    emissao,
                                    nota_fiscal
                        FROM tbl_faturamento
                        WHERE distribuidor      = $login_posto
                        AND   posto             in ($posto_da_fabrica)
                        AND   extrato_devolucao = $extrato
                        AND   fabrica           = $login_fabrica
                        AND   devolucao_concluida IS NOT TRUE
                        AND   conferencia       IS NOT NULL
                        AND   cancelada         IS NULL";
                $resConf = pg_query($con,$sqlConf);
                $notas_array_parcial = array();
                if (pg_num_rows($resConf)>0){
                    for ( $w=0; $w < pg_num_rows($resConf); $w++ ){
                        $nf_tmp = trim(pg_fetch_result($resConf,$w,'nota_fiscal'));
                        array_push($notas_array_parcial,$nf_tmp);
                    }
                }

                # Verifica se foi recebida pela F·brica
                $sqlConf = "SELECT  faturamento,
                                    emissao,
                                    nota_fiscal
                        FROM tbl_faturamento
                        WHERE distribuidor      = $login_posto
                        AND   posto             IN ($posto_da_fabrica)
                        AND   extrato_devolucao = $extrato
                        AND   fabrica           = $login_fabrica
                        AND   devolucao_concluida IS NOT TRUE
                        AND   cancelada         IS NULL
                        AND   emissao - CURRENT_DATE >15";
                $resConf = pg_query($con,$sqlConf);
                $notas_array = array();
                if (pg_num_rows($resConf)>0){
                    for ( $w=0; $w < pg_num_rows($resConf); $w++ ){
                        $nf_tmp = trim(pg_fetch_result($resConf,$w,'nota_fiscal'));
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

            $total = ($login_fabrica == 85) ? $xmao_de_obra + $pecas + $pedagio + $qtde_km_calculada : $xmao_de_obra + $pecas ;

            if (strlen($data_pagamento)==0){
                $total_pendencia += $total;
            }

            $data_geracao;

            $cor = ($i % 2 == 0) ? '#F1F4FA' : "#F7F5F0";
            $btn = ($i % 2 == 0) ? 'azul' : 'amarelo';

            ##### LAN√áAMENTO DE EXTRATO avulso - IN√çCIO #####
            if (strlen($extrato) > 0) {
                $sql = "SELECT count(*) as existe
                        FROM   tbl_extrato_lancamento
                        WHERE  extrato = $extrato
                        and    posto   = $login_posto
                        and    fabrica = $login_fabrica";
                $res_avulso = pg_query($con,$sql);

                if (@pg_num_rows($res_avulso) > 0) {
                    if (@pg_fetch_result($res_avulso, 0, 'existe') > 0) $cor = "#FFE1E1";
                }
            }
            ##### LAN√É‚Ä°AMENTO DE EXTRATO AVULSO - FIM #####

            //HD 15606
            if($login_fabrica == 6 and $pecas_devolvidas=='t'){
                $cor = '#33CCFF';
            }

            ##### VERIFICA NOVO PROCESSO LGR - IN√É¬çCIO #####
            $novo_processo_lgr= "";
            if (strlen($extrato) > 0 AND $login_fabrica == 24) {
                $sql = "SELECT CASE WHEN data_geracao > '2007-12-31'::date THEN 1 ELSE 0 END AS sim_nao
                        FROM   tbl_extrato
                        WHERE  extrato = $extrato
                        and    posto   = $login_posto
                        and    fabrica = $login_fabrica";
                $res_lgr = pg_query($con,$sql);
                if (@pg_num_rows($res_lgr) > 0) {
                    $novo_processo_lgr = pg_fetch_result($res_lgr, 0, 'sim_nao');
                }
            }
            ##### VERIFICA NOVO PROCESSO LGR  - HD 5630 FIM #####

            #HD 22758
            if ($login_fabrica==24 AND $total_pendencia>0 AND strlen($data_pagamento)>0 AND $imprimir_total_pendencia<>'1'){
                $imprimir_total_pendencia = '1';
                echo "<tr class='table_line'>\n";
                echo "<td align='right' colspan='5'>Total Aguardando Pagamento:</td>\n";
                echo "<td align='left' colspan='5'><b>".number_format($total_pendencia,2,",",".")."</b></td>\n";
                echo "</tr>\n";
            }

            if($login_fabrica == 42 || $login_fabrica == 86){
                if(!empty($data_pagamento))
                    echo "<tr class='table_line' style='background-color: PaleGreen;'>\n";
                else{
                    echo "<tr class='table_line' style='background-color: $cor;'>\n";
                }
            }else{
                echo "<tr class='table_line' style='background-color: $cor;'>\n";
            }

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

	    if (in_array($login_fabrica, [169,170])) {
                $statusExtrato = '';
                if (!empty($data_pagamento)) {
                    $statusExtrato = 'Pagamento Efetivado';
                } else if ($bloqueado == 't') {
                    $statusExtrato = 'Pagamento Bloqueado';
                } else if (!empty($data_entrega_financeiro)) {
                    $statusExtrato = 'Nota Aprovada';
                } else if ($nf_recebida == 't') {
                    $statusExtrato = 'Nota Emitida';
                } else {
                    $statusExtrato = 'Liberado';
                }

                echo "<td align='center' nowrap>";
                fecho ($statusExtrato, $con, $cook_idioma);
                echo "</td>\n";
            }

            if(in_array($login_fabrica, [11,172])){
                echo "<td align='left' style='padding-left:7px;padding-right:7px;'>";
                echo $nome_fabrica_extrato;
                echo "</td>\n";
            }

            if (!in_array($login_fabrica, [169,170])) {
                echo "<td align='left' nowrap><acronym title='aprovado_$data_aprovado'>" . $data_aprovado . "</acronym></td>\n";
            }

            if ($login_fabrica == 19) {
                echo "<td align='left' style='padding-left:7px;padding-right:7px;'>";
                echo $protocolo;
                echo "</td>\n";
            }
            if( !in_array($login_fabrica, array(11,172)) ){
                echo "<td align='left' nowrap><acronym title='$posto_codigo - $posto_nome'>$posto_codigo - " . substr($posto_nome,0,20) . "</acronym></td>\n";
            }
            if ($login_fabrica == 3 AND $tipo_posto == "D"){
                echo "<td align='center'><a href='extrato_distribuidor.php?data=$data_extrato'>$data_geracao</a></td>\n";
            }else{
                echo (in_array($login_fabrica,array(7,11,25,51,45,172))) ? "<td align='center'>$liberado</td>\n" : "<td align='center'>$data_geracao</td>\n";
            }

            if($login_fabrica == 52){
                echo "<td align='center'>".number_format($pedagio,2,",",".")."</td>";
            }

            if($login_fabrica == 86){
                echo "<td align='center'>";
                echo mostraMarcaExtrato($extrato);
                echo    "</td>";
            }

            if($login_fabrica == 51){
                $sql3 = "SELECT SUM(valor) as total_os_sedex FROM tbl_extrato_lancamento WHERE fabrica = $login_fabrica AND lancamento in (96) AND extrato = $extrato;";
                $res3 = pg_query($con,$sql3);
                if (@pg_num_rows($res3) > 0) {
                    $total_os_sedex = pg_fetch_result($res3, 0, 'total_os_sedex');
                }

                #O PA n„o pode ver o cr√©dito de OSs recusadas que s„o lanÁados para acertar o extrato conforme nota feita pelo PA
                #Na conferÍncia de Lote È feito a recusa e È lanÁado um dÈbtio para o pr√É¬≥ximo extrato e um crÈdito no atual para manter o mesmo valor da nota emitida.
                $sql3 = "SELECT SUM(valor) as total_os_recusada FROM tbl_extrato_lancamento WHERE fabrica = $login_fabrica AND lancamento in (121) AND extrato = $extrato;";
                $res3 = pg_query($con,$sql3);
                if (@pg_num_rows($res3) > 0) {
                    $total_os_recusada = pg_fetch_result($res3, 0, 'total_os_recusada');

                    $xvrmao_obra = $xvrmao_obra + $total_os_recusada;
                    $total       = $total + $total_os_recusada;
                    $avulso      = $avulso - $total_os_recusada - $total_os_sedex;
                    $total       = $total + $avulso;
                }
            }
            if($login_fabrica == 42  || $login_fabrica == 86) echo "<td>$data_pagamento</td>";

            # HD 119143 adicionei fabrica 43 'nova computadores' √É¬† essa regra
            if ((($login_fabrica == 2 and $login_posto == 6359) or in_array($login_fabrica,array(7,11,25,43,51,80,101,172))) AND $qtde_devolucao >0 AND $devolveu_pecas == "nao" AND strlen($msg_mes_anterior)==0){
                echo "<td></td>";
                echo "<td></td>";
                echo "<td></td>";
                echo "<td></td>";
                echo "<td></td>";
                echo "<td align='center'>
                <a href='extrato_posto_devolucao.php?extrato=$extrato' title='Clique aqui para preencher a nota fiscal de devoluÁ„o. ApÛs a devoluÁ„o da NF, poder„o ser visualizado a M„o de Obra'>Preencha a NF<br>Clique Aqui</a></td>\n";
                if($login_fabrica == 2){
                    echo "<td><a href='extrato_posto_lgr.php?extrato=$extrato'><img src='imagens/btn_pecasretornaveis_".$btn.".gif'></a></TD>\n";
                }elseif( in_array($login_fabrica, array(11,172)) ){
                    echo "<td><a href='extrato_posto_devolucao_lenoxx.php?extrato=$extrato'><img src='imagens/btn_pecasretornaveis_".$btn.".gif'></a></TD>\n";
                }elseif($login_fabrica == 51){
                    echo "<td><a href='extrato_posto_devolucao_gama.php?extrato=$extrato'><img src='imagens/btn_pecasretornaveis_".$btn.".gif'></a></TD>\n";
                }elseif($login_fabrica == 43 or $login_fabrica==80){
                    echo "<td><a href='os_extrato_pecas_retornaveis.php?extrato=$extrato'><img src='imagens/btn_pecasretornaveis_".$btn.".gif'></a></TD>\n";
                }else{
                    echo "<td><a href='extrato_posto_devolucao_hbtech.php?extrato=$extrato'><img src='imagens/btn_pecasretornaveis_".$btn.".gif'></a></TD>\n";
                }
            }elseif( (in_array($login_fabrica,array(2,7,11,25,51,172))) AND count($notas_array)>0 AND strlen($admin_lgr)==0 ) {
                echo "<td align='center' colspan='7' bgcolor='#FF9E5E'>\n";
                echo "<a href=\"$PHP_SELF?ajax=true&extrato=$extrato&status=confirmada&nf=".implode(",",$notas_array)."&height=240&width=320\"  title=\"Nota Fiscal n„o confirmada\" class=\"thickbox\">ATEN√á√ÉO</a>";
                echo "</td>";
            }elseif( (in_array($login_fabrica,array(2,7,11,25,172))) AND count($notas_array_parcial)>0 AND strlen($admin_lgr)==0 ) {
                echo "<td align='center' colspan='7' bgcolor='#FF9E5E'>\n";
                echo "<a href=\"$PHP_SELF?ajax=true&extrato=$extrato&status=parcial&nf=".implode(",",$notas_array_parcial)."&height=240&width=320\"  title=\"Nota Fiscal Parcial\" class=\"thickbox\">ATEN√á√ÉO</a>";
                echo "</td>";
            }elseif((in_array($login_fabrica,array(2,7,11,25,43,51,50,172))) AND strlen($msg_mes_anterior)>0){
                echo "<td align='center' colspan='7' bgcolor='#FF9E5E'>\n";
                echo $msg_mes_anterior;
                echo "</td>";
            }else{
                //HD 221727: Acrescentado a pedido do Samuel
                if($login_fabrica == 51 and $i>2){
                    break;
                }

                $aguardando_nf = "";

                if ($login_fabrica == 19) {
                    $xvrmao_obra = pg_fetch_result($res,$i,'extra_mo');
                    $pecas       = pg_fetch_result($res,$i,'extra_pecas');
                    $total       = $pecas + $xvrmao_obra;

                    $sql = "SELECT tbl_extrato.extrato FROM tbl_extrato WHERE nf_recebida IS NOT TRUE AND extrato < $extrato and posto = $login_posto and fabrica = $login_fabrica AND aprovado IS NOT NULL ORDER BY extrato " ;
                    $resX = pg_query($con,$sql);
    #               if (pg_num_rows($resX) > 0) {
    #                   $extrato_anterior = pg_fetch_result($resX,0,0);
    #                   $aguardando_nf = 't';
    #                   $mensagem_aguardando_nf = "Seu lote ($extrato_anterior) est· com pendÍncia da Nota Fiscal. <br> O lote atual permanecer· bloqueado atÈ regularizaÁ„o. <br> D˙vidas, entrar em contato atravÈs do <b>0800 160212</b>";
    #               }
                }

                if ($aguardando_nf == 't') {
                    echo "<td align='left' bgcolor='#9999aa' colspan='4' nowrap style='color: #ffffff'>$mensagem_aguardando_nf</td>";
                }else{
                    if (!in_array($login_fabrica, array(143,169,170))) {
                        echo "<td align='right'  style='padding-right:3px;' nowrap> ";
                        if($login_fabrica == 6){
                            if(strlen($liberado)>0){
                                echo number_format($xvrmao_obra,2,",",".");
                            }
                        }else{
                            echo number_format($xvrmao_obra,2,",",".");
                        }

                        if ($login_fabrica == 15){
                            echo "<td align='right'  style='padding-right:3px;' nowrap>". number_format($deslocamento_km,2,",",".") ."</td>\n";
                        }

                        if ($login_fabrica == 85){
                            echo "<td align='right'  style='padding-right:3px;' nowrap>". number_format($qtde_km_calculada,2,",",".") ."</td>\n";
                            echo "<td align='right'  style='padding-right:3px;' nowrap>". number_format($pedagio,2,",",".") ."</td>\n";
                        }
                        echo "</td>\n";
                    }

                    if (isset($novaTelaOs)) {
                        $nao_calcula_peca = \Posvenda\Regras::get("nao_calcula_peca", "mao_de_obra", $login_fabrica);
                    }

                    if ((!in_array($login_fabrica,array(87,104,105,106,108,111,139)) && !isset($novaTelaOs)) || (isset($novaTelaOs) && !$nao_calcula_peca && !in_array($login_fabrica, array(169,170,191)))) {

                        echo "<td align='right'  style='padding-right:3px;' nowrap>";
                        if($login_fabrica == 6){
                            if(strlen($liberado)>0){
                                echo number_format($pecas,2,",",".");
                            }
                        }else{
                            echo number_format($pecas,2,",",".");
                        }
                        echo "</td>\n";
                    }

                    // Valor Adicional
                    $valor_adicional = (strlen($valor_adicional) > 0) ? $valor_adicional : "00.00";

                    if (!in_array($login_fabrica, array(169,170)) && ($inf_valores_adicionais || in_array($login_fabrica, array(35,139)))) {
                        echo "<td align='center' nowrap> ".number_format($valor_adicional,2,",",".")." </td>\n";
                    }

                    //hd 39502
                    if ($login_fabrica==20) {
                        echo "<td align='right'  style='padding-right:3px;' nowrap> ";
                        echo number_format($total_cortesia,2,",",".");
                        echo "</td>\n";
                    }

                    if(!in_array($login_fabrica, array(30,51))) {
                        if(!in_array($login_fabrica, array(45, 74)) && !isset($novaTelaOs)) {
                            echo "<td align='right'  style='padding-right:3px;' nowrap> ";
                            if($login_fabrica == 6){
                                if(strlen($liberado)>0){
                                    echo number_format($total,2,",",".");
                                }
                            }else{
                                if(!in_array($login_fabrica, array(11,15,42,172))) {
                                    echo number_format( ($total + $valor_adicional),2,",",".");
                                }
                                else if ($login_fabrica == 15) { // HD 977125
                                    echo number_format( ($total + $deslocamento_km),2,",",".");
				} else if($login_fabrica == 42){
					echo number_format( ($aux_valor_total),2,",",".");
				}else {
                                    echo ( in_array($login_fabrica, array(11,172)) ) ? number_format($avulso,2,",",".") : number_format($total,2,",",".") ;
                                }
                            }
                            echo "</td>\n";
                        }

                        if (!in_array($login_fabrica, array(169,170))) {
                            echo "<td align='right'  style='padding-right:3px;' nowrap> ";
                            if($login_fabrica == 6){
                                if(strlen($liberado)>0){
                                    echo number_format($avulso,2,",",".");
                                }
                            }else{ 
                                echo ( in_array($login_fabrica, array(11,172)) ) ? number_format($total+$avulso,2,",",".") : number_format($avulso,2,",",".");
                            }
                        }
                    }else{
                        echo "<td align='right'  style='padding-right:3px;' nowrap> ";
                        echo number_format($avulso,2,",",".");
                        echo "</td>\n";
                        if (!in_array($login_fabrica, array(30))) {
                            echo "<td align='right'  style='padding-right:3px;' nowrap> ";
                            echo number_format($total,2,",",".");
                        }
                    }
                    echo "</td>\n";

                    if (isset($novaTelaOs)) {
                        $nao_calcula_km = \Posvenda\Regras::get("nao_calcula_km", "mao_de_obra", $login_fabrica);
                    }

                    if((in_array($login_fabrica,array(30,50,52,72,90,74)) && !isset($novaTelaOs)) || (!in_array($login_fabrica, array(169,170)) && isset($novaTelaOs) && !$nao_calcula_km)){
                        echo "<td align='right'  style='padding-right:3px;' nowrap> ";
                        echo number_format($deslocamento_km,2,",",".");
                        echo "</td>\n";
                    }

                    if (in_array($login_fabrica, array(30))) {
                        $res_taxa_entrega = pg_execute($con, 'taxa_entrega', array($extrato));
                        echo "<td align='right'  style='padding-right:3px;' nowrap> ";
                        #echo number_format(pg_fetch_result($res_taxa_entrega, 0, "taxa_entrega"),2,",",".");
						echo '0,00';
                        echo "</td>\n";
                        echo "<td align='right'  style='padding-right:3px;' nowrap> ";
                        $total = $total + $avulso + $deslocamento_km; // + pg_fetch_result($res_taxa_entrega, 0, "taxa_entrega");
                        echo number_format($total,2,",",".");
                        echo "</td>\n";                                                
                    }

                    if($login_fabrica == 90) {
                        echo "<td align='right'  style='padding-right:3px;' nowrap> ";
                        $sqlv = "SELECT sum(taxa_visita) as taxa_visita
                            FROM tbl_os_extra
                            WHERE extrato = $extrato";
                        $resv = pg_query($con,$sqlv);
                        echo number_format(pg_fetch_result($resv,0,'taxa_visita'),2,",",".");
                        echo "</td>\n";
                    }
                    if($login_fabrica ==45) {
                        echo "<td align='right'  style='padding-right:3px;' nowrap> ";
                        echo number_format($total_geral,2,",",".");
                        echo "</td>\n";
                    }

                    if($login_fabrica == 74 || isset($novaTelaOs)) {
                        echo "<td align='right'  style='padding-right:3px;' nowrap> ";
                        echo number_format($aux_valor_total,2,",",".");
                        echo "</td>\n";
                    }

                    if (in_array($login_fabrica, [5,80,169,170])) { ?>
                        <td align='center' style='padding-right:3px;' nowrap><?= $nf_autorizacao; ?></td>
                        <?php if (in_array($login_fabrica, [169,170])) { ?>
                            <td align='center' style='padding-right:3px;'>
                                <?php
                                $sqlAutorizacao = "SELECT data FROM tbl_extrato_status
                                                   WHERE fabrica = {$login_fabrica}
                                                   AND obs = 'Nota Fiscal Aprovada'
                                                   AND extrato = {$extrato}
                                                   ORDER BY data DESC 
                                                   LIMIT 1";
                                $resAutorizacao = pg_query($con, $sqlAutorizacao);

                                echo mostra_data_hora(pg_fetch_result($resAutorizacao, 0, 'data'));

                                $sqlReprovada = "SELECT data, obs FROM tbl_extrato_status
                                                   WHERE fabrica = {$login_fabrica}
                                                   AND pendente IS TRUE
                                                   AND extrato = {$extrato}
                                                   ORDER BY data DESC
                                                   LIMIT 1";
                                $resReprovada = pg_query($con, $sqlReprovada);

                                $justificativa = pg_fetch_result($resReprovada, 0, 'obs');

                                ?>
                            </td>
                            <td align='center' style='padding-right:3px;'>
                                <?php if (!empty($justificativa)) {
                                        $justificativa_x = utf8_decode($justificativa);
                                        $justificativa_x = nl2br($justificativa_x);   
                                        $justificativa_x = str_replace("<br />", "&nbsp;\\", $justificativa_x); ?>
                                    <button type="button" onclick="alert('<?=$justificativa_x ?>');">Justificativa</button>
                                <?php } ?>
                            </td>
                            <td>
                                <?= mostra_data_hora(pg_fetch_result($resReprovada, 0, 'data')) ?>
                            </td>
                        <?php }
                    }

                    //26/12/2007 HD 9982
                    if (!in_array($login_fabrica, [87,104,105,169,170])) {
                        echo "<td align='right'  style='padding-right:3px;' nowrap> ";
                        if(in_array($login_fabrica,array(5,11,24,45,50,15,80,172))){
                            echo $data_pagamento;
                        } elseif ($login_fabrica==20) {
                            echo $previsao_pagamento;
                        } else {
                            if( !in_array($login_fabrica, array(11,172)) ){
                                echo $previsao;
                            }
                        }
                        echo "</td>\n";
                    }
                    echo (in_array($login_fabrica, [15,45,50,169,170])) ? "<td align='right' style='padding-right:3px;' nowrap> ". $previsao_pagamento_extrato ."</td>\n" : "";
                    echo (in_array($login_fabrica, [72,169,170])) ? "<td align='center' nowrap>$data_pagamento</td>\n" : ""; //HD 351922
                    echo ($login_fabrica == 19) ? "<td align='right'  style='padding-right:3px;' nowrap> ". $pagamento ."</td>\n" : "";
                }

                if ($login_fabrica==20) {
                    echo "<td align='left'>$nota_fiscal_mao_de_obra</td>\n";
                    echo "<td align='left'>$nota_fiscal_devolucao</td>\n";
                    echo "<td align='left'>$data_entrega_transportadora</td>\n";
                }

                if($login_fabrica == 30){
                    $sqlEncontro = "SELECT  to_char(posto_data_transacao,'DD/MM/YYYY') AS dt_pagamento,
                                            nf_numero_nf,
                                            nf_valor_do_encontro_contas,
                                            encontro_serie,
                                            encontro_titulo_a_pagar,
                                            encontro_parcela,
                                            encontro_valor_liquido,
                                            posto_valor_do_encontro_contas
                                        FROM tbl_encontro_contas
                                        WHERE fabrica = $login_fabrica
                                        AND extrato = $extrato
                                        LIMIT 1";
                    $resEncontro = pg_query($con,$sqlEncontro);

                    if(pg_num_rows($resEncontro) > 0){
                        $num_oc         = pg_fetch_result($resEncontro, 0, 'encontro_serie');
                        $dt_pagamento   = pg_fetch_result($resEncontro, 0, 'dt_pagamento');
                        $valor_pago     = pg_fetch_result($resEncontro, 0, 'nf_valor_do_encontro_contas');
                        $num_nf         = pg_fetch_result($resEncontro, 0, 'nf_numero_nf');
                        $desconto       = pg_fetch_result($resEncontro, 0, 'posto_valor_do_encontro_contas');
                        $button = "<input type='button' rel='$extrato' pa='$login_posto' value='Encontro Contas' id='encontro_contas_$extrato'>";
                    }else{
                        $num_oc = "";
                        $dt_pagamento = "";
                        $valor_pago = "";
                        $num_nf = "";
                        $desconto = "";
                        $button = "&nbsp;";
                    }
                    ?>
                        <td><?=$num_oc?></td>
                        <td><?=$dt_pagamento?></td>
                        <td><?=number_format($valor_pago,2,',','.')?></td>
                        <td><?=$num_nf?></td>
                        <td><?=number_format($desconto,2,',','.')?></td>
                        <!-- <td><?=$button?></td> -->
                    <?php
                }

                if ($login_fabrica == 1){
                    echo "<td><img src='imagens/btn_imprimirdetalhado_15.gif' onclick=\"javascript: janela=window.open('os_extrato_detalhe_print_blackedecker.php?extrato=$extrato','extrato');\" ALT=\"Imprimir detalhado\" border='0' style=\"cursor:pointer;\"></td>\n";
                }else{

                    if(in_array($login_fabrica, array(11,172))){

                        $sql_fabrica_extrato = "SELECT fabrica FROM tbl_extrato WHERE extrato = {$extrato}";
                        $res_fabrica_extrato = pg_query($con, $sql_fabrica_extrato);

                        $fabrica_extrato = pg_fetch_result($res_fabrica_extrato, 0, "fabrica");

                        echo "<td><a href='javascript: aviso_detalhar({$fabrica_extrato}, \"os_extrato_detalhe.php?extrato=$extrato&posto=$posto\");'><img src='imagens/btn_detalhar_$btn.gif'></a></td>\n";
                    }else{
                        if (in_array($login_fabrica, [169,170])) {

                            $sqlStatus = "SELECT pendente, obs FROM tbl_extrato_status
                                          WHERE fabrica = {$login_fabrica}
                                          AND extrato = {$extrato}
                                          ORDER BY data DESC
                                          LIMIT 1";
                            $resStatus = pg_query($con, $sqlStatus);

                            $pendenteEnvio = pg_fetch_result($resStatus, 0, 'pendente');

                            $liberaEnvio = false;
                            if ($pendenteEnvio == "t" || pg_num_rows($resStatus) == 0) {
                                $liberaEnvio = true;    
                            }

                            echo "<td nowrap>";

                            if ($liberaEnvio) {
                                echo "<button onclick='window.open(\"os_extrato_detalhe.php?extrato=$extrato&posto=$posto\");'>Enviar NF</button>\n";
                            }

                            echo "<button onclick='window.open(\"os_extrato_detalhe.php?extrato=$extrato&posto=$posto\");'>Detalhar</button>\n";

                            echo "</td>";

                        } else {
                            echo "<td><a href='os_extrato_detalhe.php?extrato=$extrato&posto=$posto'><img src='imagens/btn_detalhar_$btn.gif'></a></td>\n";
                        }
                    }

                    if ($login_fabrica == 14) {
                        echo "<td nowrap><a href='os_extrato_pecas_retornaveis.php?extrato=$extrato'><font color=\"#0000CC\">PeÁas Trocadas</font></a></TD>\n";
                    }else{
                        if($login_fabrica<>15){
                            if ( in_array($login_fabrica, array(11,172)) ) {
                                echo "<td><a href='extrato_posto_devolucao_lenoxx_itens.php?extrato=$extrato'><img src='imagens/btn_pecasretornaveis_".$btn.".gif'></a></TD>\n";
                            }else{
                                if ($login_fabrica==24 AND $novo_processo_lgr == '1'){
                                    echo "<td><a href='os_extrato_pecas_retornaveis_suggar_lgr.php?extrato=$extrato'><img src='imagens/btn_pecasretornaveis_".$btn.".gif'></a></TD>\n";
                                }else{
                                    if ($login_fabrica==2){
                                        if ($extrato > 302634){ # HD 12684
                                            echo "<td><a href='extrato_posto_lgr.php?extrato=$extrato'><img src='imagens/btn_pecasretornaveis_".$btn.".gif'></a></TD>\n";
                                        }else{
                                            echo "<td><a href='os_extrato_pecas_retornaveis.php?extrato=$extrato'><img src='imagens/btn_pecasretornaveis_".$btn.".gif'></a></TD>\n";
                                        }
                                    }elseif ($login_fabrica==25){
                                        echo "<td><a href='extrato_posto_devolucao_hbtech_itens.php?extrato=$extrato'><img src='imagens/btn_pecasretornaveis_".$btn.".gif'></a></TD>\n";
                                    }else{
                                        if ($login_fabrica==7){
                                            echo "<td><a href='extrato_posto_lgr_itens.php?extrato=$extrato'><img src='imagens/btn_pecasretornaveis_".$btn.".gif'></a></TD>\n";
                                        }elseif($login_fabrica==51){
                                            echo "<td><a href='extrato_posto_devolucao_gama_itens.php?extrato=$extrato'><img src='imagens/btn_pecasretornaveis_".$btn.".gif'></a></TD>\n";
                                        }elseif($login_fabrica==74){
                                            echo "<td><a href='extrato_posto_nf.php?extrato=$extrato'>Cadastro de NF</a></TD>\n";
                                        }elseif(in_array($login_fabrica, array(85,129,81,120,201,15,1,145,138,90,80,134,59,117,50,104,6,40,122,3,72,131,115,35,127,86,52,20,141,91,24,114,45,99,46,142,140,144,139))){
                                            echo "<td><a href='os_extrato_pecas_retornaveis.php?extrato=$extrato'><img src='imagens/btn_pecasretornaveis_".$btn.".gif'></a></TD>\n";
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

            echo "</tr>\n";

            if ($login_fabrica == 15 || $login_fabrica == 14) { # HD 165932
                echo "<tr class='table_line' style='background-color: $cor;'>\n";
                echo "<td colspan='100%' nowrap align='center' style='font-size:18px'><div id='mostra_$i'><b>".traduz('nota.fiscal', $con, $cook_idioma).":</b>&nbsp;&nbsp;";
                echo (strlen($nota_fiscal_mao_de_obra) > 0) ? $nota_fiscal_mao_de_obra : "<input type='text' name='nota_fiscal_mao_de_obra_$i' id='nota_fiscal_mao_de_obra_$i' class='frm' size='10' maxlength='10' rel='nota_fiscal_mao_de_obra'>&nbsp;&nbsp;";
                echo "&nbsp;&nbsp;<b>".traduz('data.emissao', $con, $cook_idioma).":</b>&nbsp;&nbsp;";
                echo (strlen($nota_fiscal_mao_de_obra) > 0) ? $emissao_mao_de_obra : "<input type='text' name='emissao_mao_de_obra_$i' id='emissao_mao_de_obra_$i' rel='emissao_mao_de_obra' size='12'>&nbsp;&nbsp;";
                echo "&nbsp;&nbsp;<b>Valor NF:</b> &nbsp;&nbsp;";
                echo (strlen($nota_fiscal_mao_de_obra) > 0) ? number_format($valor_total_extrato,2,",",".") : "<input type='text' name='valor_total_extrato_$i' id='valor_total_extrato_$i' size='10' maxlength='10' rel='valor_total_extrato'>&nbsp;&nbsp;&nbsp;";
                echo (strlen($nota_fiscal_mao_de_obra) > 0) ? "" : "<a href=\"javascript: gravaNota('nota_fiscal_mao_de_obra_$i','emissao_mao_de_obra_$i','valor_total_extrato_$i','mostra_$i',$extrato);\"><img src='imagens/btn_gravar.gif'>";
                if (($login_fabrica == 14) && strlen($nota_fiscal_mao_de_obra) > 0) {
                    echo "&nbsp;&nbsp;<b>".traduz(array('data.recebimento','nf'),$con)."</b>&nbsp;&nbsp;" . $data_entrega_transportadora;
                }
                echo "</div></td>";
                echo "</tr>";
            }
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

if ($login_fabrica == 15) {

    echo '<table width="700" align="center" border="0" cellspacing="1" cellpadding="5">';
        echo '<tr>';
	    $label_previsao = 'Previs„o de Pagamento';
            echo "<td align='center'><input type='button' value='$label_previsao' onclick='previsaoPagamento()' /></td>";
        echo '</tr>';
    echo '</table>';

}

include "rodape.php"; ?>
