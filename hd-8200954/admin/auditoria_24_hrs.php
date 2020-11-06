<?php
include "dbconfig.php";
include "includes/dbconnect-inc.php";
$admin_privilegios="auditoria";
include "autentica_admin.php";
include 'funcoes.php';

# Pesquisa pelo AutoComplete AJAX
$q = strtolower($_GET["q"]);
if (isset($_GET["q"])){
    $tipo_busca = $_GET["busca"];

    if (strlen($q)>2){
        $sql = "SELECT  tbl_posto.cnpj                  ,
                        tbl_posto.nome                  ,
                        tbl_posto_fabrica.codigo_posto
                FROM    tbl_posto
                JOIN    tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
                WHERE   tbl_posto_fabrica.fabrica = $login_fabrica ";
        if ($tipo_busca == "codigo"){
            $sql .= "
                AND     tbl_posto_fabrica.codigo_posto = '$q' ";
        }else{
            $sql .= "
                AND     UPPER(tbl_posto.nome) like UPPER('%$q%') ";
        }

        $res = pg_exec($con,$sql);
        if (pg_numrows ($res) > 0) {
            for ($i=0; $i<pg_numrows ($res); $i++ ){
                $cnpj         = trim(pg_result($res,$i,cnpj));
                $nome         = utf8_encode(trim(pg_result($res,$i,nome)));
                $codigo_posto = trim(pg_result($res,$i,codigo_posto));
                echo "$cnpj|$nome|$codigo_posto";
                echo "\n";
            }
        }
    }
    exit;
}

$os   = $_GET["os"];
$btn_acao    = trim($_POST['btn_acao']);
$select_acao = trim($_POST['select_acao']);
if(strlen($btn_acao)>0 && strlen($select_acao)>0){

    $qtde_os     = trim($_POST["qtde_os"]);
    $observacao  = trim($_POST["observacao"]);

    if($select_acao == "recusada" && strlen($observacao) == 0){
        $msg_erro .= "Informe o motivo da reprovação da OS.<br>";
    }

    if($select_acao == "liberada" && strlen($observacao) == 0){
        $msg_erro .= "Informe o motivo da aprovação da OS.<br>";
    }

    if(strlen($observacao) > 0){
        $observacao = " Observação: $observacao ";
    }

    if (strlen($qtde_os)==0){
        $qtde_os = 0;
    }

    for ($x=0;$x<$qtde_os;$x++){

        $xxos         = trim($_POST["check_".$x]);

        if (strlen($xxos) > 0 AND strlen($msg_erro) == 0){

            $res_os = pg_exec($con,"BEGIN TRANSACTION");

            if($select_acao == "liberada"){

                $sql = "UPDATE  tbl_os_auditar
                        SET     liberado        = true              ,
                                liberado_data   = CURRENT_TIMESTAMP ,
                                justificativa   = '$observacao'     ,
                                admin           = $login_admin
                        WHERE   os = $xxos
                ";
                $res = pg_exec($con,$sql);
                $msg_erro .= pg_errormessage($con);
            }

            if($select_acao == "recusada"){
                $sql = "UPDATE  tbl_os_auditar
                        SET     cancelada       = true              ,
                                cancelada_data  = CURRENT_TIMESTAMP ,
                                justificativa   = '$observacao'     ,
                                admin           = $login_admin
                        WHERE   os = $xxos
                ";
                $res = pg_exec($con,$sql);
                $msg_erro .= pg_errormessage($con);

                /*
                * - QUANDO A OS É RECUSADA NESSA AUDITORIA
                * A MESMA É EXCLUÍDA
                */
                $sqlOs = "UPDATE    tbl_os
                          SET       excluida = true
                          WHERE     os = $xxos
                ";
                $resOs = pg_exec($con,$sqlOs);
                $msg_erro .= pg_errormessage($con);
            }

            if (strlen($msg_erro)==0){
                $res = pg_exec($con,"COMMIT TRANSACTION");
            }else{
                $res = pg_exec($con,"ROLLBACK TRANSACTION");
            }
        }
    }
}

$layout_menu = "auditoria";
$title = "Auditoria de OSs abertas, para aprovação e/ou reprovação em 24 horas";

include "cabecalho.php";

?>

<style type="text/css">

.Tabela{
    border:1px solid #596D9B;
    background-color:#596D9B;
}
.Erro{
    font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
    font-size: 12px;
    color:#CC3300;
    font-weight: bold;
    background-color:#FFFFFF;
}
.Titulo {
    text-align: center;
    font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
    font-size: 10px;
    font-weight: bold;
    color: #FFFFFF;
    background-color: #596D9B;
}
.Conteudo {
    font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
    font-size: 10px;
    font-weight: normal;
}

.menu_top {
    text-align: center;
    font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
    font-size: x-small;
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

/*****************************
ELEMENTOS DE POSICIONAMENTO
*****************************/

#container {
  border: 0px;
  padding:0px 0px 0px 0px;
  margin:0px 0px 0px 0px;
  background-color: white;
}

body {
    margin: 0px;
}

body {
    margin: 0px;
}

.titulo {
    font-family: Arial;
    font-size: 7pt;
    text-align: right;
    color: #000000;
    background: #ced7e7;
}
.titulo2 {
    font-family: Arial;
    font-size: 7pt;
    text-align: center;
    color: #000000;
    background: #ced7e7;
}



.titulo3 {
    font-family: Arial;
    font-size: 7pt;
    text-align: left;
    color: #000000;
    background: #ced7e7;
}
.inicio {
    font-family: Arial;
    FONT-SIZE: 8pt;
    font-weight: bold;
    text-align: left;
    color: #FFFFFF;
}

.conteudo {
    font-family: Arial;
    FONT-SIZE: 8pt;
    font-weight: bold;
    text-align: left;
    background: #F4F7FB;
}

.conteudo2 {
    font-family: Arial;
    FONT-SIZE: 8pt;
    font-weight: bold;
    text-align: left;
    background: #FFDCDC;
}

.Tabela{
    border:1px solid #d2e4fc;
    background-color:#485989;
    }
.subtitulo {
    font-family: Verdana;
    FONT-SIZE: 9px;
    text-align: left;
    background: #F4F7FB;
    padding-left:5px
}
.justificativa{
    font-family: Arial;
    FONT-SIZE: 10px;
    background: #F4F7FB;
}
.inpu{
    border:1px solid #666;
}
.conteudo_sac {
    font-family: Arial;
    FONT-SIZE: 10pt;
    text-align: left;
    background: #F4F7FB;
}

table.bordasimples {border-collapse: collapse;}

table.bordasimples tr td {border:1px solid #000000;}

</style>

<script type="text/javascript" src='ajax.js'></script>
<script type="text/javascript" src="js/bibliotecaAJAX.js"></script>

<script type='text/javascript' src='js/jquery.autocomplete.js'></script>
<link rel="stylesheet" type="text/css" href="js/jquery.autocomplete.css" />
<script type='text/javascript' src='js/jquery.bgiframe.min.js'></script>
<script type='text/javascript' src='js/dimensions.js'></script>
<? include "javascript_calendario_new.php"; //adicionado por Fabio 27-09-2007
include '../js/js_css.php';
?>

<script type="text/javascript" charset="utf-8">
$(function(){
    $('#data_inicial').datepick({startdate:'01/01/2000'});
    $('#data_final').datepick({startDate:'01/01/2000'});
    $("#data_inicial").mask("99/99/9999");
    $("#data_final").mask("99/99/9999");

});
</script>



<script type="text/javascript">
    $().ready(function() {

        function formatItem(row) {
            return row[2] + " - " + row[1];
        }

        function formatResult(row) {
            return row[2];
        }

        /* Busca pelo Código */
        $("#posto_codigo").autocomplete("<?echo $PHP_SELF.'?busca=codigo'; ?>", {
            minChars: 3,
            delay: 150,
            width: 350,
            matchContains: true,
            formatItem: formatItem,
            formatResult: function(row) {return row[2];}
        });

        $("#posto_codigo").result(function(event, data, formatted) {
            $("#posto_nome").val(data[1]) ;
        });

        /* Busca pelo Nome */
        $("#posto_nome").autocomplete("<?echo $PHP_SELF.'?busca=nome'; ?>", {
            minChars: 3,
            delay: 150,
            width: 350,
            matchContains: true,
            formatItem: formatItem,
            formatResult: function(row) {return row[1];}
        });

        $("#posto_nome").result(function(event, data, formatted) {
            $("#posto_codigo").val(data[2]) ;
            //alert(data[2]);
        });

    });

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

    var ok = false;
    var cont=0;
    function checkaTodos() {
        f = document.frm_pesquisa2;
        if (!ok) {
            for (i=0; i<f.length; i++){
                if (f.elements[i].type == "checkbox"){
                    f.elements[i].checked = true;
                    ok=true;
                    if (document.getElementById('linha_'+cont)) {
                        document.getElementById('linha_'+cont).style.backgroundColor = "#F0F0FF";
                    }
                    cont++;
                }
            }
        }else{
            for (i=0; i<f.length; i++) {
                if (f.elements[i].type == "checkbox"){
                    f.elements[i].checked = false;
                    ok=false;
                    if (document.getElementById('linha_'+cont)) {
                        document.getElementById('linha_'+cont).style.backgroundColor = "#FFFFFF";
                    }
                    cont++;
                }
            }
        }
    }

    function setCheck(theCheckbox,mudarcor,cor){
        if (document.getElementById(theCheckbox)) {
        }
        if (document.getElementById(mudarcor)) {
            document.getElementById(mudarcor).style.backgroundColor  = (document.getElementById(theCheckbox).checked ? "#FFF8D9" : cor);
        }
    }
</script>

<form name="frm_pesquisa" method="post" action="<?echo $PHP_SELF?>">

<table width="700" align="center" border="0" cellspacing='0' cellpadding='0' class='PesquisaTabela'>
    <caption>Auditoria de OS para postos marcados com Auditoria 24hrs</caption>
    <tbody>
        <tr>
            <td colspan="2">
                Número da OS<br />
                <input type="text" name="os" id="os" size="20" maxlength="20" value="<?=$os?>" class="frm" />
            </td>
        </tr>
        <tr>
            <td>
                Data Inicial<br />
                <input type="text" name="data_inicial" id="data_inicial" size="11" maxlength="10" value="<?=$data_inicial?>" class="frm">
            </td>
            <td>
                Data Final<br />
                <input type="text" name="data_final" id="data_final" size="11" maxlength="10" value="<?=$data_final?>" class="frm">
            </td>
        </tr>
        <tr>
            <td>
                Código Posto<br />
                <input type="text" name="posto_codigo" id="posto_codigo" size="15"  value="<?=$posto_codigo ?>" class="frm">
            </td>
            <td>
                Nome do Posto<br />
                <input type="text" name="posto_nome" id="posto_nome" size="40"  value="<?=$posto_nome ?>" class="frm">
            </td>
        </tr>

        <tr>
            <td colspan='2'>
            Linha<br>
                    <select name='linha' class="frm">
                        <option value=''></option>
<?
    $sql = "SELECT  tbl_linha.linha,
                    tbl_linha.nome
            FROM    tbl_linha
            WHERE   ativo IS TRUE
            AND     tbl_linha.fabrica = $login_fabrica ";
    $res = pg_query ($con,$sql);

    for($i=0;$i<pg_num_rows($res);$i++){
?>
                    <option value='<?=pg_fetch_result($res,$i,linha)?>'><?=pg_fetch_result($res,$i,nome)?></option>
<?
    }
?>
                    </select>
            </td>
        </tr>

        <tr>
            <td colspan='2'>
                <b>Mostrar as OS:</b><br>

                    <input type="radio" name="aprova" value='aprovacao' <? if(trim($aprova) == 'aprovacao' or trim($aprova)==0) echo "checked='checked'"; ?>>Em Aprovação &nbsp;&nbsp;&nbsp;
                    <input type="radio" name="aprova" value='aprovadas' <? if(trim($aprova) == 'aprovadas') echo "checked='checked'"; ?>>Aprovadas  &nbsp;&nbsp;&nbsp;
                    <input type="radio" name="aprova" value='reprovadas' <? if(trim($aprova) == 'reprovadas') echo "checked='checked'"; ?>>Reprovadas &nbsp;&nbsp;&nbsp;
            </td>
        </tr>
    </tbody>
<tr>
    <td colspan="2" style="text-align:center">
        <br>
        <input type='hidden' name='btn_acao' value=''>
        <img src="imagens_admin/btn_pesquisar_400.gif" onclick="javascript: if ( document.frm_pesquisa.btn_acao.value == '' ) { document.frm_pesquisa.btn_acao.value='pesquisar'; document.frm_pesquisa.submit() ; } else { alert ('aguarde submissão da os...'); }" style="cursor:pointer " alt='clique aqui para pesquisar'>
    </td>
</tr>
</table>
</form>

<?
if (strlen($btn_acao)  > 0 AND strlen($msg_erro)==0) {
    if($btn_acao == "pesquisar"){
        $os             = $_POST['os'];
        $linha          = $_POST['linha'];
        $aprova         = $_POST['aprova'];
        $posto_codigo   = $_POST['posto_codigo'];
        $data_inicial   = $_POST['data_inicial'];
        $data_final     = $_POST['data_final'];

        if (strlen($data_inicial) > 0) {
            $xdata_inicial = formata_data ($data_inicial);
            $xdata_inicial = $xdata_inicial." 00:00:00";
        }

        if (strlen($data_final) > 0) {
            $xdata_final = formata_data ($data_final);
            $xdata_final = $xdata_final." 23:59:59";
        }

        if(strlen($aprova) == 0){
            $aprova = "aprovacao";
            $aprovacao = "Em Aprovacao";
        }elseif($aprova=="aprovacao"){
            $aprovacao = "Em Aprovacao";
        }elseif($aprova=="aprovadas"){
            $aprovacao = "Aprovadas";
        }elseif($aprova=="reprovadas"){
            $aprovacao = "Reprovadas";
        }

        if (strlen($os)>0){
            $Xos = " AND audit.os = $os ";
        }

        $sql = "SELECT  audit.os
                INTO    TEMP tmp_audit_$login_admin
                FROM    (
                            SELECT  ultima.os,
                                    (
                                        SELECT  CASE WHEN tbl_os_auditar.liberado IS NOT TRUE AND tbl_os_auditar.cancelada IS NOT TRUE
                                                     THEN 'Em Aprovacao'
                                                     WHEN tbl_os_auditar.liberado IS TRUE AND tbl_os_auditar.cancelada IS NOT TRUE
                                                     THEN 'Aprovadas'
                                                     WHEN tbl_os_auditar.liberado IS NOT TRUE AND tbl_os_auditar.cancelada IS TRUE
                                                     THEN 'Reprovadas'
                                                     ELSE 'NADA'
                                                END AS status_auditar
                                        FROM    tbl_os_auditar
                                        WHERE   fabrica = $login_fabrica
                                        AND     descricao = 'AUDITORIA 24hrs'
                                        AND     tbl_os_auditar.os = ultima.os
                                  ORDER BY      data DESC
                                        LIMIT   1
                                    ) AS ultimo_status
                            FROM    (
                                        SELECT  DISTINCT
                                                os
                                        FROM    tbl_os_auditar
                                        WHERE   fabrica = $login_fabrica
                                        AND     descricao = 'AUDITORIA 24hrs'
                                    ) ultima
                        ) audit
                WHERE   audit.ultimo_status = '$aprovacao'
                $Xos
                ;

                CREATE INDEX tmp_audit_OS_$login_admin ON tmp_audit_$login_admin(os);

                SELECT  tbl_os.os                                                           ,
                        tbl_os.sua_os                                                       ,
                        tbl_os.consumidor_nome                                              ,
                        TO_CHAR(tbl_os.data_abertura,'DD/MM/YYYY')  AS data_abertura        ,
                        TO_CHAR(tbl_os.data_digitacao,'DD/MM/YYYY') AS data_digitacao       ,
                        tbl_os.fabrica                                                      ,
                        tbl_posto.nome                              AS posto_nome           ,
                        tbl_posto_fabrica.codigo_posto                                      ,
                        tbl_posto_fabrica.contato_email             AS posto_email          ,
                        tbl_produto.referencia                      AS produto_referencia   ,
                        tbl_produto.descricao                       AS produto_descricao    ,
                        tbl_produto.voltagem                                                ,
                        (
                            SELECT  CASE WHEN tbl_os_auditar.liberado IS NOT TRUE AND tbl_os_auditar.cancelada IS NOT TRUE
                                         THEN 'Em Aprovacao'
                                         WHEN tbl_os_auditar.liberado IS TRUE AND tbl_os_auditar.cancelada IS TRUE
                                         THEN 'Aprovadas'
                                         WHEN tbl_os_auditar.liberado IS NOT TRUE AND tbl_os_auditar.cancelada IS NOT NULL
                                         THEN 'Reprovadas'
                                         ELSE 'NADA'
                                    END AS status_auditar
                            FROM    tbl_os_auditar
                            WHERE   fabrica = $login_fabrica
                            AND     tbl_os.os = tbl_os_auditar.os
                            AND     descricao = 'AUDITORIA 24hrs'
                      ORDER BY      os_auditar DESC
                            LIMIT   1
                        )                                           AS status_auditar       ,
                        (
                            SELECT  justificativa
                            FROM    tbl_os_auditar
                            WHERE   fabrica = $login_fabrica
                            AND     tbl_os.os = tbl_os_auditar.os
                            AND     descricao = 'AUDITORIA 24hrs'
                      ORDER BY      os_auditar DESC
                            LIMIT   1
                        )                                           AS status_observacao

                FROM    tmp_audit_$login_admin X
                JOIN    tbl_os              ON tbl_os.os                    = X.os
                JOIN    tbl_produto         ON tbl_produto.produto          = tbl_os.produto
                JOIN    tbl_posto           ON tbl_os.posto                 = tbl_posto.posto
                JOIN    tbl_posto_fabrica   ON tbl_posto.posto              = tbl_posto_fabrica.posto
                                           AND tbl_posto_fabrica.fabrica    = $login_fabrica
                WHERE   tbl_os.fabrica = $login_fabrica
        ";
        if (strlen($xdata_inicial) > 0 AND strlen($xdata_final) > 0) {
            $sql .= "
                AND     tbl_os.data_digitacao BETWEEN '$xdata_inicial' AND '$xdata_final'
            ";
        }
        $sql .= "
          ORDER BY      tbl_posto_fabrica.codigo_posto,
                        tbl_os.os
        ";

        #echo nl2br($sql);
        #exit;
        $res = pg_query($con,$sql);

        if(pg_numrows($res)>0){

            $xls_rows = pg_num_rows($res);
            if (pg_num_rows($res) <= 500){
                $rows = pg_num_rows($res);
            }else{
                $rows = 500;
            }
?>
<br />
<br />
<form name="frm_pesquisa2" method="POST" ACTION="<?=$PHP_SELF?>">
    <input type="hidden" name="data_inicial"   value="<?=$data_inicial?>" />
    <input type="hidden" name="data_final"     value="<?=$data_final?>" />
    <input type="hidden" name="aprova"         value="<?=$aprova?>" />
<?
            if(in_array($aprova,array("aprovadas","reprovadas"))){
?>
    <center>
        <p style='color: #ff2222;'>
            Serão mostrados em tela no maximo os últimos 500 pedidos, para visualizar todos os pedidos baixe o arquivo xls.
        </p>
    </center>
<?
            }
?>
    <table width="98%" border="0" align="center" cellpadding="3" cellspacing="1" style="font-family: verdana; font-size: 11px" bgcolor="#FFFFFF">
        <thead>
            <tr>
                <th style="background-color:#485989;color:#FFF"><img border='0' src='imagens_admin/selecione_todas.gif' onclick='javascript: checkaTodos()' alt='Selecionar todos' style='cursor: hand;' align='center'></th>
                <th style="background-color:#485989;color:#FFF">OS</th>
                <th style="background-color:#485989;color:#FFF">Data</th>
                <th style="background-color:#485989;color:#FFF">Posto</th>
                <th style="background-color:#485989;color:#FFF">E-mail</th>
                <th style="background-color:#485989;color:#FFF">Produto</th>
                <th style="background-color:#485989;color:#FFF">Descrição</th>
                <th style="background-color:#485989;color:#FFF">Status</th>
            </tr>
        </thead>
        <tbody>
<?
            $cores          = '';
            $qtde_auditoria = 0;
            $total_os       = $rows;

            for ($x=0; $x<$rows;$x++){
                $os                 = pg_result($res, $x, os);
                $sua_os             = pg_result($res, $x, sua_os);
                $codigo_posto       = pg_result($res, $x, codigo_posto);
                $posto_nome         = pg_result($res, $x, posto_nome);
                $posto_email        = pg_result($res, $x, posto_email);
                $produto_referencia = pg_result($res, $x, produto_referencia);
                $produto_descricao  = pg_result($res, $x, produto_descricao);
                $produto_voltagem   = pg_result($res, $x, voltagem);
                $data_digitacao     = pg_result($res, $x, data_digitacao);
                $data_abertura      = pg_result($res, $x, data_abertura);
                $status_auditar     = pg_result($res, $x, status_auditar);
                $status_observacao  = pg_result($res, $x, status_observacao);

                $cores++;
                $cor = ($cores % 2 == 0) ? "#FEFEFE": '#E8EBEE';

?>
            <tr style="background-color:<?=$cor?>" id="linha_<?=$x?>">
                <td style="text-align:center" width="0">
<?
                    if (strlen($msg_erro)>0){
                        if (strlen($_POST["check_".$x])>0){
                            $checked = " CHECKED ";
                        }else{
                            $checked = "";
                        }
                    }
?>
                    <input type="checkbox" name="check_<?=$x?>" id="check_<?=$x?>" value="<?=$os?>" onclick="setCheck('check_<?=$x?>','linha_<?=$x?>','<?=$cor?>');" <?=$checked?>>
                </td>
                <td style="font-size: 9px; font-family: verdana" nowrap >
                    <a href="os_press.php?os=<?=$os?>"  target="_blank"><?=$sua_os?></a>
                </td>
                <td style="font-size: 9px; font-family: verdana"><?=$data_digitacao?></td>
                <td align="left" style="font-size: 9px; font-family: verdana" nowrap title="<?$codigo_posto.' - '.$posto_nome?>"><?=$codigo_posto.' - '.substr($posto_nome,0,20)?>...</td>
                <td align="left" style="font-size: 9px; font-family: verdana" nowrap ><a href="mailto:<?=$posto_email?>"><?=$posto_email?></a></td>
                <td align="left" style="font-size: 9px; font-family: verdana" nowrap><acronym title="Produto: <?=$produto_referencia?>" style="cursor: help"><?=$produto_referencia?></acronym></td>
                <td align="left" style="font-size: 9px; font-family: verdana" nowrap><acronym title="Produto: <?=$produto_referencia - $produto_descricao?>" style="cursor: help"><?=$produto_descricao?></acronym></td>
                <td style="font-size: 9px; font-family: verdana" nowrap><acronym title="Observação: <?=$status_observacao?>"><?=$status_observacao?></acronym></td>
            </tr>
<?
            }
?>
            <input type="hidden" name="qtde_os" value="<?=$x?>">
            <tr>
                <td colspan="100%" style="height:20px; background-color:#485989;text-align:left;">
<?
            if($aprova == "aprovacao"){
?>
                    &nbsp;&nbsp;&nbsp;&nbsp;<img border='0' src='imagens/seta_checkbox.gif' align='absmiddle'> &nbsp; <font color='#FFFFFF'><B>COM MARCADOS:</B></font> &nbsp;
                    <select name="select_acao" size="1" class="frm" >
                        <option value="">&nbsp;</option>
                        <option value="liberada" <? if($_POST['select_acao'] == "liberada"){ echo "selected";}?>>APROVADO</option>
                        <option value="recusada" <? if($_POST['select_acao'] == "recusada"){ echo "selected";}?>>RECUSADO</option>
                    </select>
                    &nbsp;&nbsp; <font color="#FFFFFF"><b>Motivo:<b></font> <input class="frm" type="text" name="observacao" id="observacao" size="30" maxlength="250" value="" >
                    &nbsp;&nbsp;<img src="imagens/btn_gravar.gif" style="cursor:pointer" onclick="javascript: document.frm_pesquisa2.submit()" style="cursor: hand;" border="0">
<?
            }
?>
                </td>
            </tr>
            <input type='hidden' name='btn_acao' value='Pesquisar'>
        </tbody>
    </table>
    <p>TOTAL OS: <?=$total_os?></p>
</form>
<?
            if(in_array($aprova,array("aprovadas","reprovadas"))){
                if ($xls_rows > 0){
                    flush();
                    $xlsdata = date ("d/m/Y H:i:s");
                   # echo `rm /tmp/assist/auditoria-vintequatrohrs-$login_fabrica.xls`;
                    echo `rm /xls/auditoria-vintequatrohrs-$login_fabrica.xls`;
                    #$fp = fopen ("/tmp/assist/auditoria-vintequatrohrs-$login_fabrica.html","w");
                    $fp = fopen ("../xls/auditoria-vintequatrohrs-$login_fabrica.html","w");

                    fputs($fp,"<table width='98%' border='0' align='center' cellpadding='3' cellspacing='1' style='font-family: verdana; font-size: 11px' bgcolor='#FFFFFF'>");
                    fputs($fp,"    <thead>");
                    fputs($fp,"        <tr>");
                    fputs($fp,"            <th style='background-color:#485989;color:#FFF'>OS</th>");
                    fputs($fp,"            <th style='background-color:#485989;color:#FFF'>Data</th>");
                    fputs($fp,"            <th style='background-color:#485989;color:#FFF'>Posto</th>");
                    fputs($fp,"            <th style='background-color:#485989;color:#FFF'>E-mail</th>");
                    fputs($fp,"            <th style='background-color:#485989;color:#FFF'>Produto</th>");
                    fputs($fp,"            <th style='background-color:#485989;color:#FFF'>Descrição</th>");
                    fputs($fp,"            <th style='background-color:#485989;color:#FFF'>Status</th>");
                    fputs($fp,"        </tr>");
                    fputs($fp,"    </thead>");
                    fputs($fp,"    <tbody>");
                    for ($x=0; $x<$xls_rows;$x++){
                        $os                 = pg_result($res, $x, os);
                        $sua_os             = pg_result($res, $x, sua_os);
                        $codigo_posto       = pg_result($res, $x, codigo_posto);
                        $posto_nome         = pg_result($res, $x, posto_nome);
                        $posto_email        = pg_result($res, $x, posto_email);
                        $produto_referencia = pg_result($res, $x, produto_referencia);
                        $produto_descricao  = pg_result($res, $x, produto_descricao);
                        $produto_voltagem   = pg_result($res, $x, voltagem);
                        $data_digitacao     = pg_result($res, $x, data_digitacao);
                        $data_abertura      = pg_result($res, $x, data_abertura);
                        $status_auditar     = pg_result($res, $x, status_auditar);
                        $status_observacao  = pg_result($res, $x, status_observacao);

                        fputs($fp,"<tr>");
                        fputs($fp,"    <td style='text-align:center' width='0'>$sua_os</td>");
                        fputs($fp,"    <td style='text-align:center' width='0'>$data_digitacao</td>");
                        fputs($fp,"    <td style='text-align:center' width='0'>$codigo_posto - $posto_nome</td>");
                        fputs($fp,"    <td style='text-align:center' width='0'>$posto_email</td>");
                        fputs($fp,"    <td style='text-align:center' width='0'>$produto_referencia</td>");
                        fputs($fp,"    <td style='text-align:center' width='0'>$produto_descricao</td>");
                        fputs($fp,"    <td style='text-align:center' width='0'>$status_observacao</td>");
                        fputs($fp,"</tr>");
                    }
                    fputs($fp,"    </tbody>");
                    fputs($fp,"</table>");

                    fclose ($fp);
                    $data = date("Y-m-d").".".date("H-i-s");
                    #rename("/tmp/assist/auditoria-vintequatrohrs-$login_fabrica.html", "xls/auditoria-vintequatrohrs-$login_fabrica.$data.xls");
                    rename("../xls/auditoria-vintequatrohrs-$login_fabrica.html", "xls/auditoria-vintequatrohrs-$login_fabrica.$data.xls");
?>
<table width='200' border='0' cellspacing='2' cellpadding='2' align='center' style='cursor: pointer; font-size: 12px;'>
    <tr>
        <td align='left' valign='absmiddle'><a href='xls/auditoria-vintequatrohrs-<?=$login_fabrica.".".$data?>.xls' target='_blank'><img src='imagens/excel.png' height='20px' width='20px' align='absmiddle'>&nbsp;&nbsp;&nbsp;Gerar Arquivo Excel</a></td>
    </tr>
</table>
<?
                }
            }
        }else{
?>
<center>
    Nenhum OS encontrada.
</center>
<?
        }
    }
    $msg_erro = "";
}
?>
