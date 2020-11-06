<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios = "financeiro";

$gera_automatico = trim($_GET["gera_automatico"]);

if ($gera_automatico != 'automatico'){
    include "autentica_admin.php";
}

function formatCPFCNPJ ($string){
    $output = preg_replace("[' '-./ t]", '', $string);
    $size = (strlen($output) -2);
    if ($size != 9 && $size != 12) return false;
    $mask = ($size == 9)
        ? '###.###.###-##'
        : '##.###.###/####-##';
    $index = -1;
    for ($i=0; $i < strlen($mask); $i++):
        if ($mask[$i]=='#') $mask[$i] = $output[++$index];
    endfor;
    return $mask;
}

# Pesquisa pelo AutoComplete AJAX
$q = strtolower($_GET["q"]);
if (isset($_GET["q"])) {

    $tipo_busca = $_GET["busca"];

    if (strlen($q)>2){
        $sql = "SELECT  tbl_posto.cnpj                  ,
                        tbl_posto.nome                  ,
                        tbl_posto_fabrica.codigo_posto
                FROM    tbl_posto
                JOIN    tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
                WHERE   tbl_posto_fabrica.fabrica = $login_fabrica ";

        $sql .= ($tipo_busca == "codigo") ? " AND tbl_posto_fabrica.codigo_posto = '$q' " : " AND UPPER(tbl_posto.nome) like UPPER('%$q%') ";

        $res = pg_query($con,$sql);
        if (pg_num_rows ($res) > 0) {
            for ($i=0; $i<pg_num_rows ($res); $i++ ){
                $cnpj = trim(pg_fetch_result($res,$i,cnpj));
                $nome = trim(pg_fetch_result($res,$i,nome));
                $codigo_posto = trim(pg_fetch_result($res,$i,codigo_posto));
                echo "$cnpj|$nome|$codigo_posto";
                echo "\n";
            }
        }
    }
    exit;
}

if($_POST['ajax'] == 1){
    $arquivo = utf8_decode($_POST['arquivo']);

    $data = date ("dmY");
    echo `rm xls/relatorio_pagamento_posto_linha-$login_fabrica.xls`;
    $fp = fopen ("xls/relatorio_pagamento_posto_linha-$login_fabrica.html","w");

    fputs($fp,$arquivo);
    fclose($fp);
    rename("xls/relatorio_pagamento_posto_linha-$login_fabrica.html","xls/relatorio_pagamento_posto_linha-$login_fabrica.$data.xls");
    $caminho = "xls/relatorio_pagamento_posto_linha-$login_fabrica.$data.xls";

    if(file_exists($caminho)){
        echo $caminho;
        exit;
    }else{
        echo "erro";
        exit;
    }
}

if (strlen(trim($_POST["btn_acao"])) > 0) $btn_acao = trim($_POST["btn_acao"]);
if (strlen(trim($_GET["btn_acao"])) > 0)  $btn_acao = trim($_GET["btn_acao"]);

if (strlen(trim($_POST["posto_codigo"])) > 0) $posto_codigo = trim($_POST["posto_codigo"]);
if (strlen(trim($_GET["posto_codigo"])) > 0)  $posto_codigo = trim($_GET["posto_codigo"]);

if (strlen(trim($_POST["data_inicial"])) > 0) $data_inicial = trim($_POST["data_inicial"]);
if (strlen(trim($_GET["data_inicial"])) > 0)  $data_inicial = trim($_GET["data_inicial"]);

if (strlen(trim($_POST["data_final"])) > 0) $data_final = trim($_POST["data_final"]);
if (strlen(trim($_GET["data_final"])) > 0)  $data_final = trim($_GET["data_final"]);

if (strlen(trim($_POST["agrupar"])) > 0) $agrupar = trim($_POST["agrupar"]);
if (strlen(trim($_GET["agrupar"])) > 0)  $agrupar = trim($_GET["agrupar"]);

if (strlen(trim($_POST["nota_sem_baixa"])) > 0) $nota_sem_baixa = trim($_POST["nota_sem_baixa"]);
if (strlen(trim($_GET["nota_sem_baixa"])) > 0)  $nota_sem_baixa = trim($_GET["nota_sem_baixa"]);

if (strlen(trim($_POST["nota_com_baixa"])) > 0) $nota_com_baixa = trim($_POST["nota_com_baixa"]);
if (strlen(trim($_GET["nota_com_baixa"])) > 0)  $nota_com_baixa = trim($_GET["nota_com_baixa"]);


if (strlen($btn_acao) > 0) {

    if (strlen($posto_codigo) > 0) {
        $cond1 = " AND tbl_posto_fabrica.codigo_posto = '$posto_codigo'";
    }

    if($data_inicial){
        $dat = explode ("/", $data_inicial);
        $d = $dat[0];
        $m = $dat[1];
        $y = $dat[2];
        if( (!checkdate($m,$d,$y)) ){
            $msg_erro ="Data Inválida.";
        }
    }

    if($data_final){
        $dat = explode ("/", $data_final);
        $d = $dat[0];
        $m = $dat[1];
        $y = $dat[2];
        if( (!checkdate($m,$d,$y)) ){
            $msg_erro ="Data Inválida.";
        }
    }

    else{
        $msg_erro="Data Inválida.";
    }

    if(strlen($msg_erro)==0){
        $data_inicial = str_replace (" " , "" , $data_inicial);
        $data_inicial = str_replace ("-" , "" , $data_inicial);
        $data_inicial = str_replace ("/" , "" , $data_inicial);
        $data_inicial = str_replace ("." , "" , $data_inicial);

        $data_final   = str_replace (" " , "" , $data_final)  ;
        $data_final   = str_replace ("-" , "" , $data_final)  ;
        $data_final   = str_replace ("/" , "" , $data_final)  ;
        $data_final   = str_replace ("." , "" , $data_final)  ;

        if (strlen ($data_inicial) == 6) $data_inicial = substr ($data_inicial,0,4) . "20" . substr ($data_inicial,4,2);
        if (strlen ($data_final)   == 6) $data_final   = substr ($data_final  ,0,4) . "20" . substr ($data_final  ,4,2);

        if (strlen ($data_inicial) > 0)  $data_inicial = substr ($data_inicial,0,2) . "/" . substr ($data_inicial,2,2) . "/" . substr ($data_inicial,4,4);
        if (strlen ($data_final)   > 0)  $data_final   = substr ($data_final,0,2)   . "/" . substr ($data_final,2,2)   . "/" . substr ($data_final,4,4);
    }
}


$layout_menu = "financeiro";
$title = "RELATÓRIO DE VALORES DE EXTRATOS";

include 'cabecalho.php';
?>

<style type="text/css">
.Titulo {
    text-align: center;
    font-family: Arial;
    font-size: 9px;
    font-weight: bold;
    color: #FFFFFF;
    background-color: #485989;
}
.Titulo2 {
    text-align: center;
    font-family: Arial;
    font-size: 11px;
    font-weight: bold;
    color: #FFFFFF;
    background-color: #CC0033;
}
.Conteudo {
    font-family: Arial;
    font-size: 9px;
    font-weight: normal;
}
.Mes{
    font-size: 8px;
}
.Caixa{
    BORDER-RIGHT: #639CC 1px solid;
    BORDER-TOP: #639CC 1px solid;
    FONT: 8pt Arial ;
    BORDER-LEFT: #639CC 1px solid;
    BORDER-BOTTOM: #639CC 1px solid;
    BACKGROUND-COLOR: #FFFFFF
}

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
    font: bold 16px "Arial";
    color:#FFFFFF;
    text-align:center;
}

.formulario{
    background-color:#D9E2EF;
    font:11px Arial;
}
</style>

<script type='text/javascript' src='js/jquery.autocomplete.js'></script>
<link rel="stylesheet" type="text/css" href="js/jquery.autocomplete.css" />
<script type='text/javascript' src='js/jquery.bgiframe.min.js'></script>
<script type='text/javascript' src='js/dimensions.js'></script>
<? include "javascript_calendario_new.php";
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


<script type='text/javascript'>
$().ready(function() {
    $('input[id^=obs_]').blur(function(){
        var valor   = this.value;
        var obs     = this.id;
        var arquivo = $('#arquivo').val();

        if(valor.length > 0){
            switch(obs){
                case "obs_costura":
                    $("#arquivo").val(arquivo.replace("<div id='obs_costura'/>","<tr><td colspan='11'>"+valor+"</td></tr>"));
                break;
                case "obs_clima":
                    $("#arquivo").val(arquivo.replace("<div id='obs_clima'/>","<tr><td colspan='11'>"+valor+"</td></tr>"));
                break;
                case "obs_eletro":
                    $("#arquivo").val(arquivo.replace("<div id='obs_eletro'/>","<tr><td colspan='11'>"+valor+"</td></tr>"));
                break;
                case "obs_inf":
                    $("#arquivo").val(arquivo.replace("<div id='obs_inf'/>","<tr><td colspan='11'>"+valor+"</td></tr>"));
                break;
                case "obs_div":
                    $("#arquivo").val(arquivo.replace("<div id='obs_div'/>","<tr><td colspan='11'>"+valor+"</td></tr>"));
                break;
            }
        }
    });
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

function geraExcel(){
    var arquivo = $('#arquivo').val();
    $.ajax({
        url:"<?=$PHP_SELF?>",
        type:"POST",
        data:{
            ajax:1,
            arquivo:arquivo
        },
        cache:"false"
    })
    .done(function(result){
        if(result != "erro"){
            $('#arquivo').val($('#arquivo_intacto').val());
            window.location = result;
        }
    });
}

/* ============= Função PESQUISA DE POSTOS ====================
Nome da Função : fnc_pesquisa_posto (cnpj,nome)
        Abre janela com resultado da pesquisa de Postos pela
        Código ou CNPJ (cnpj) ou Razão Social (nome).
=================================================================*/
function fnc_pesquisa_posto2 (campo, campo2, tipo) {

    if (tipo == "codigo" ) {
        var xcampo = campo;
    }

    if (tipo == "nome" ) {
        var xcampo = campo2;
    }

    if (xcampo.value != "") {
        var url = "";
        url = "posto_pesquisa_2.php?campo=" + xcampo.value + "&tipo=" + tipo;
        janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=600, height=400, top=18, left=0");
        janela.codigo  = campo;
        janela.nome    = campo2;
        janela.focus();
    }
    else{
        alert("Preencha toda ou parte da informação para realizar a pesquisa!");
    }

}

function SomenteNumero(event){

    var tecla = event.keyCode ? event.keyCode : event.which ? event.which : event.charCode;

    if (tecla == 9) {
        $("#posto_nome").focus();
    }

    if((tecla > 47 && tecla < 58)) return true;
    else{
        if (tecla != 8) return false;
        else return true;
    }
}

</script>
<?
if (strlen($msg_erro) > 0) {
?>
<table width="40px" border="0" cellpadding="2" cellspacing="2" align="center" class="error">
    <tr>
        <td><?echo $msg_erro?></td>
    </tr>
</table>

<?
}
?>

<!-- FORMULÁRIO DE PESQUISA -->
<form name='frm_relatorio' method='post' action='<?=$PHP_SELF?>' align='center'>
<table width='40px' class='formulario' border='0' cellpadding='0' cellspacing='1' align='center' style="margin-top:3px;">
    <tr class='titulo_tabela'>
        <td height="20px">Parâmetros de Pesquisa</td>
    </tr>
    <tr><td>&nbsp;</td></tr>
    <tr>
        <td>

            <table width='40px' border='0' cellspacing='1' cellpadding='2' class='formulario'>

                    <tr class='table_line'>
                    <td width="50">&nbsp;</td>
                    <td align='left'>Data Inicial</td>
                    <td align='left' width='130' nowrap>
                        <input type="text" name="data_inicial" id="data_inicial" size="12" maxlength="10" class='frm' value="<? if (strlen($data_inicial) > 0) echo $data_inicial; ?>">
                    </td>
                    <td align='right' nowrap>Data Final</td>
                    <td align='left' nowrap>
                        <input type="text" name="data_final" id="data_final" size="12" maxlength="10" class='frm' value="<? if (strlen($data_final) > 0) echo $data_final;?>" >
                    </td>
                </tr>
                <tr class="table_line">
                    <td width="10">&nbsp;</td>

                    <td align='left' nowrap>Código do Posto</td>
                    <td nowrap><input class="frm" type="text" name="posto_codigo" id="posto_codigo" size="10" value="<? echo $posto_codigo ?>" onkeypress='return SomenteNumero(event);' >&nbsp;<img src="imagens/lupa.png"
                    border='0' align='absmiddle' style="cursor:pointer" onclick="javascript: fnc_pesquisa_posto2
                    (document.frm_relatorio.posto_codigo,document.frm_relatorio.posto_nome,'codigo')"></A>
                    </td>
                    <td align='right' nowrap>Nome do Posto</td>
                    <td align='left' nowrap><input class="frm" type="text" name="posto_nome" id="posto_nome" size="30" value="<? echo $posto_nome ?>">&nbsp;<img src="imagens/lupa.png" style="cursor:pointer" border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_posto2 (document.frm_relatorio.posto_codigo,document.frm_relatorio.posto_nome,'nome')" style="cursor:pointer;"></a>
                    </td>
                </tr>
                <tr class='table_line'>
                <td width="10">&nbsp;</td>
                    <td align='left' nowrap colspan='4'>
                        Aprovados para Pagamento
                        <INPUT TYPE="checkbox" NAME="pago" value='sim' <?if(strlen($pago)>0)echo "CHECKED"?>>

                    </td>
                    <td width="10">&nbsp;</td>
                </tr>
                <tr>
                    <td colspan='5' align='center'>
                    <br />
                        <input type='submit' name='enviar' value='Consultar'>
                        <input type='hidden' name='btn_acao' value='consultar'></center>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>

</form>

<?php
//--=== RESULTADO DA PESQUISA ====================================================--\\
flush();

if (strlen($btn_acao) > 0 AND strlen($msg_erro) == 0) {
    if (strlen($data_inicial) > 0 AND strlen($data_final) > 0) {
        if (strlen ($data_inicial) < 8){
            $data_inicial = date ("d/m/Y");
        }

        $x_data_inicial = substr ($data_inicial,6,4) . "-" . substr ($data_inicial,3,2) . "-" . substr ($data_inicial,0,2);

        if (strlen ($data_final) < 8){
            $data_final = date ("d/m/Y");
        }

        $x_data_final = substr ($data_final,6,4) . "-" . substr ($data_final,3,2) . "-" . substr ($data_final,0,2);

        $sql = "SELECT  tbl_posto.nome                                                  ,
                        tbl_posto_fabrica.codigo_posto                                  ,
                        tbl_posto_fabrica.contato_cidade                                ,
                        tbl_posto_fabrica.contato_estado                                ,
                        tbl_posto.cnpj                                                  ,
                        tbl_posto_fabrica.nomebanco                                     ,
                        tbl_posto_fabrica.agencia                                       ,
                        tbl_posto_fabrica.conta                                         ,
                        TO_CHAR(tbl_extrato.data_geracao,'DD/MM/YYYY') AS data_geracao  ,
                        tbl_extrato.extrato                                             ,
                        tbl_extrato.total
                INTO    TEMP tmp_extrato_pagamento
                FROM    tbl_extrato
                JOIN    tbl_extrato_extra   ON  tbl_extrato_extra.extrato = tbl_extrato.extrato
                JOIN    tbl_posto           ON  tbl_posto.posto           = tbl_extrato.posto
                JOIN    tbl_posto_fabrica   ON  tbl_posto_fabrica.posto   = tbl_extrato.posto
                                            AND tbl_posto_fabrica.fabrica = $login_fabrica
                                            $cond1
                WHERE   tbl_extrato.fabrica = $login_fabrica";
        if (strlen($pago) > 0){
            $sql .= "
                AND     tbl_extrato.aprovado IS NOT NULL
            ";
        }
        if (strlen ($x_data_inicial) > 0 AND strlen ($x_data_final) > 0){
            $sql .= "
                AND     tbl_extrato.data_geracao BETWEEN '$x_data_inicial 00:00:00' AND '$x_data_final 23:59:59'";
        }
        $sql .= "
          ORDER BY      tbl_posto.nome
        ";

        $res = pg_query($con, $sql);

        $sqlV = "SELECT * FROM tmp_extrato_pagamento";
        $resV = pg_query($sqlV);
        if(pg_num_rows($resV) > 0){

            $sql2 = "ALTER TABLE tmp_extrato_pagamento add column linha integer;
                     ALTER TABLE tmp_extrato_pagamento add column total_os integer;
            ";
            $res2 = pg_query($con,$sql2);

            $sql3 = "SELECT tmp_extrato_pagamento.extrato
                     FROM   tmp_extrato_pagamento
            ";
            $res3 = pg_query($con,$sql3);
            $contaExtrato = pg_num_rows($res3);

            for($c=0;$c<$contaExtrato;$c++){
                $extrato = pg_fetch_result($res3,$c,extrato);

                $sqlEx = "  SELECT  COUNT (tbl_os_extra.os) AS total_os,
									tbl_macro_linha_fabrica.macro_linha as linha
                            FROM    tbl_os_extra
							JOIN	tbl_macro_linha_fabrica USING(linha)
                            WHERE   tbl_os_extra.extrato = $extrato
                      GROUP BY      macro_linha
                ";
                $resEx = pg_query($con,$sqlEx);
                $totalOs    = pg_fetch_result($resEx,0,total_os);
                $linha      = pg_fetch_result($resEx,0,linha);

                $sql4 = "   UPDATE  tmp_extrato_pagamento
                            SET     linha       = $linha  ,
                                    total_os    = $totalOs
                            WHERE   extrato = $extrato
                ";
                $res4 = pg_query($con,$sql4);
            }
            $data = date ("dmY");
?>
<!--<p id='id_download' ><img src='imagens/excell.gif'> <a href='xls/relatorio_pagamento_posto_linha-<?=$login_fabrica.".".$data?>.xls' target='_blank'><font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>Clique aqui para fazer o download </font> do arquivo em EXCEL</a></p>-->
<?
$url_inteira = strlen($PHP_SELF);
$ultima_slash = (strlen(strrchr($PHP_SELF, "/")) - 1);
$espaco = $url_inteira - $ultima_slash;
$filename = substr($PHP_SELF,0,$espaco);

?>
<a href='<?=$SERVER_NAME.$filename?>xls/relatorio_pagamento_posto_linha-<?=$login_fabrica.".".$data?>.xls' id='baixa_arquivo' style="display:none" target='_blank'></a>
<p id='id_download' ><img src='imagens/excell.gif'> <a href='#' onclick="javascript:geraExcel();"><font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>Clique aqui para fazer o download </font> do arquivo em EXCEL</a></p>
<br>
<table border='0' cellpadding='2' cellspacing='0' class='formulario' align='center' width='40px'>
    <thead>
        <tr class='titulo_coluna'>
            <th>Assistência Técnica</th>
            <th>Cidade</th>
            <th>UF</th>
            <th>CNPJ</th>
            <th>Banco</th>
            <th>Agência</th>
            <th>Conta</th>
            <th>Data Geração</th>
            <th>Extrato</th>
            <th>QTDE OS</th>
            <th>Total</th>
        </tr>
    </thead>
    <tbody>
<?
            $arquivo = "";
            $arquivo .= "<html>";
            $arquivo .= "<head>";
            $arquivo .= "<title>RELATÓRIO DE VALORES DE EXTRATOS POR LINHA - $data";
            $arquivo .= "</title>";
            $arquivo .= "<meta name='Author' content='TELECONTROL NETWORKING LTDA'>";
            $arquivo .= "</head>";
            $arquivo .= "<body>";

            $arquivo .= "<br><table border='0' cellpadding='2' cellspacing='0'class='formulario' width='40px' align='center'>";
            $arquivo .= "<thead>";
            $arquivo .= "<tr class='titulo_coluna'>";
            $arquivo .= "<th>Assistência Técnica</th>";
            $arquivo .= "<th>Cidade</th>";
            $arquivo .= "<th>UF</th>";
            $arquivo .= "<th>CNPJ</th>";
            $arquivo .= "<th>Banco</th>";
            $arquivo .= "<th>Agência</th>";
            $arquivo .= "<th>Conta</th>";
            $arquivo .= "<th>Data Geração</th>";
            $arquivo .= "<th>Extrato</th>";
            $arquivo .= "<th>QTDE OS</th>";
            $arquivo .= "<th>Total</th>";
            $arquivo .= "</tr>";
            $arquivo .= "</thead>";
            $arquivo .= "<tbody>";
          /*
            $data = date ("dmY");
            echo `rm xls/relatorio_pagamento_posto_linha-$login_fabrica.xls`;
            $fp = fopen ("../xls/relatorio_pagamento_posto_linha-$login_fabrica.html","w");
            #echo `rm /tmp/assist/relatorio_pagamento_posto_linha-$login_fabrica.xls`;
            #$fp = fopen ("/tmp/assist/relatorio_pagamento_posto_linha-$login_fabrica.html","w");

            fputs($fp,"<html>");
            fputs($fp,"<head>");
            fputs($fp,"<title>RELATÓRIO DE VALORES DE EXTRATOS POR LINHA - $data");
            fputs($fp,"</title>");
            fputs($fp,"<meta name='Author' content='TELECONTROL NETWORKING LTDA'>");
            fputs($fp,"</head>");
            fputs($fp,"<body>");

            fputs($fp,"<br><table border='0' cellpadding='2' cellspacing='0'class='formulario' width='40px' align='center'>");
            fputs($fp,"<thead>");
            fputs($fp,"<tr class='titulo_coluna'>");
            fputs($fp,"<th>Assistência Técnica</th>");
            fputs($fp,"<th>Cidade</th>");
            fputs($fp,"<th>UF</th>");
            fputs($fp,"<th>CNPJ</th>");
            fputs($fp,"<th>Banco</th>");
            fputs($fp,"<th>Agência</th>");
            fputs($fp,"<th>Conta</th>");
            fputs($fp,"<th>Data Geração</th>");
            fputs($fp,"<th>Extrato</th>");
            fputs($fp,"<th>QTDE OS</th>");
            fputs($fp,"<th>Total</th>");
            fputs($fp,"</tr>");
            fputs($fp,"</thead>");
            fputs($fp,"<tbody>");
          */

            $sql5 = "   SELECT  tmp_extrato_pagamento.linha
                        FROM    tmp_extrato_pagamento
                  GROUP BY      linha
                  ORDER BY      linha DESC
            ";
            $res5 = pg_query($con,$sql5);
            $contaLinha = pg_num_rows($res5);
            for($l=0;$l<$contaLinha;$l++){
                switch(pg_fetch_result($res5,$l,linha)){
                    case 41:
                        $sqlCase = "SELECT  *
                                    FROM    tmp_extrato_pagamento
                                    WHERE   linha = 41
                        ";
                        $resCase = pg_query($con,$sqlCase);
?>
        <tr class="titulo_coluna">
            <th colspan="11">Extrato da linha Máquina de Costura</th>
        </tr>
        <tr class="titulo_coluna">
            <td colspan="11">
                Obs:
                <input type="text" id="obs_costura" name="obs_costura" value="" style="width:40px;" />
            </td>
        </tr>
<?
                        $contaCase = pg_num_rows($resCase);

                        $arquivo .= "<tr class='titulo_coluna' style='background-color:$cor'>";
                        $arquivo .= "<th colspan='11'>Extrato da linha Máquina de Costura</th>";
                        $arquivo .= "</tr>";
                        $arquivo .= "<div id='obs_costura'/>";

                        /*
                        fputs($fp,"<tr class='titulo_coluna' style='background-color:$cor'>");
                        fputs($fp,"<th colspan='11'>Extrato da linha Máquina de Costura</th>");
                        fputs($fp,"</tr>");
                        fputs($fp,"<div id='obs_costura'/>");
                        */
                        for($i=0;$i<$contaCase;$i++){
                            $posto              = pg_fetch_result($resCase,$i,codigo_posto);
                            $posto_nome         = pg_fetch_result($resCase,$i,nome);
                            $posto_cidade       = pg_fetch_result($resCase,$i,contato_cidade);
                            $posto_estado       = pg_fetch_result($resCase,$i,contato_estado);
                            $posto_cnpj         = pg_fetch_result($resCase,$i,cnpj);
                            $posto_banco        = pg_fetch_result($resCase,$i,nomebanco);
                            $posto_agencia      = pg_fetch_result($resCase,$i,agencia);
                            $posto_conta        = pg_fetch_result($resCase,$i,conta);
                            $extrato_data       = pg_fetch_result($resCase,$i,data_geracao);
                            $extrato            = pg_fetch_result($resCase,$i,extrato);
                            $extrato_total      = pg_fetch_result($resCase,$i,total);
                            $extrato_total_os   = pg_fetch_result($resCase,$i,total_os);

                            $extrato_total = number_format($extrato_total,2,",",".");

                            $cor = ( $i%2 ) ? '#F7F5F0' : '#F1F4FA';
?>
        <tr style="background-color:<?=$cor?>">
            <td nowrap><abbr title="<?=$posto." - ".$posto_nome?>"><? echo substr($posto_nome,0,17); ?></abbr></td>
            <td nowrap><?=$posto_cidade?></td>
            <td><?=$posto_estado?></td>
            <td nowrap><? echo formatCPFCNPJ($posto_cnpj); ?></td>
            <td nowrap><?=$posto_banco?></td>
            <td><?=$posto_agencia?></td>
            <td><?=$posto_conta?></td>
            <td><?=$extrato_data?></td>
            <td><?=$extrato?></td>
            <td style="text-align:right"><?=$extrato_total_os?></td>
            <td style="text-align:right"><?="R$".$extrato_total?></td>
        </tr>
<?

                            $arquivo .= "<tr class='Conteudo' style='background-color:$cor'>";
                            $arquivo .= "<td nowrap>$posto - $posto_nome</td>";
                            $arquivo .= "<td nowrap>$posto_cidade</td>";
                            $arquivo .= "<td>$posto_estado</td>";
                            $arquivo .= "<td>".formatCPFCNPJ($posto_cnpj)."</td>";
                            $arquivo .= "<td nowrap>$posto_banco</td>";
                            $arquivo .= "<td>$posto_agencia</td>";
                            $arquivo .= "<td>$posto_conta</td>";
                            $arquivo .= "<td>$extrato_data</td>";
                            $arquivo .= "<td>$extrato</td>";
                            $arquivo .= "<td style='text-align:right'>$extrato_total_os</td>";
                            $arquivo .= "<td style='text-align:right'>R$ $extrato_total</td>";
                            $arquivo .= "</tr>";
                            /*
                            fputs($fp,"<tr class='Conteudo' style='background-color:$cor'>");
                            fputs($fp,"<td nowrap>$posto - $posto_nome</td>");
                            fputs($fp,"<td nowrap>$posto_cidade</td>");
                            fputs($fp,"<td>$posto_estado</td>");
                            fputs($fp,"<td>".formatCPFCNPJ($posto_cnpj)."</td>");
                            fputs($fp,"<td nowrap>$posto_banco</td>");
                            fputs($fp,"<td>$posto_agencia</td>");
                            fputs($fp,"<td>$posto_conta</td>");
                            fputs($fp,"<td>$extrato_data</td>");
                            fputs($fp,"<td>$extrato</td>");
                            fputs($fp,"<td style='text-align:right'>$extrato_total_os</td>");
                            fputs($fp,"<td style='text-align:right'>R$ $extrato_total</td>");
                            fputs($fp,"</tr>");
                            */
                        }
                    break;
                    case 38:
                        $sqlCase = "SELECT  *
                                    FROM    tmp_extrato_pagamento
                                    WHERE   linha = 38
                        ";
                        $resCase = pg_query($con,$sqlCase);
?>
        <tr class="titulo_coluna">
            <th colspan="11">Extrato da linha Climatização</th>
        </tr>
        <tr class="titulo_coluna">
            <td colspan="11">
                Obs:
                <input type="text" id="obs_clima" name="obs_clima" value="" style="width:40px;" />
            </td>
        </tr>
<?
                        $contaCase = pg_num_rows($resCase);

                        $arquivo .= "<tr class='titulo_coluna' style='background-color:$cor'>";
                        $arquivo .= "<th colspan='11'>Extrato da linha Climatização</th>";
                        $arquivo .= "</tr>";
                        $arquivo .= "<div id='obs_clima'/>";
                        /*
                        fputs($fp,"<tr class='titulo_coluna' style='background-color:$cor'>");
                        fputs($fp,"<th colspan='11'>Extrato da linha Climatização</th>");
                        fputs($fp,"</tr>");
                        fputs($fp,"<div id='obs_clima'/>");
                        */
                        for($i=0;$i<$contaCase;$i++){
                            $posto              = pg_fetch_result($resCase,$i,codigo_posto);
                            $posto_nome         = pg_fetch_result($resCase,$i,nome);
                            $posto_cidade       = pg_fetch_result($resCase,$i,contato_cidade);
                            $posto_estado       = pg_fetch_result($resCase,$i,contato_estado);
                            $posto_cnpj         = pg_fetch_result($resCase,$i,cnpj);
                            $posto_banco        = pg_fetch_result($resCase,$i,nomebanco);
                            $posto_agencia      = pg_fetch_result($resCase,$i,agencia);
                            $posto_conta        = pg_fetch_result($resCase,$i,conta);
                            $extrato_data       = pg_fetch_result($resCase,$i,data_geracao);
                            $extrato            = pg_fetch_result($resCase,$i,extrato);
                            $extrato_total      = pg_fetch_result($resCase,$i,total);
                            $extrato_total_os   = pg_fetch_result($resCase,$i,total_os);

                            $extrato_total = number_format($extrato_total,2,",",".");
                            $cor = ( $i%2 ) ? '#F7F5F0' : '#F1F4FA';
?>
        <tr style="background-color:<?=$cor?>">
            <td nowrap><abbr title="<?=$posto." - ".$posto_nome?>"><? echo substr($posto_nome,0,17); ?></abbr></td>
            <td nowrap><?=$posto_cidade?></td>
            <td><?=$posto_estado?></td>
            <td nowrap><? echo formatCPFCNPJ($posto_cnpj); ?></td>
            <td nowrap><?=$posto_banco?></td>
            <td><?=$posto_agencia?></td>
            <td><?=$posto_conta?></td>
            <td><?=$extrato_data?></td>
            <td><?=$extrato?></td>
            <td style="text-align:right"><?=$extrato_total_os?></td>
            <td style="text-align:right"><?="R$".$extrato_total?></td>
        </tr>
<?
                            $arquivo .= "<tr class='Conteudo' style='background-color:$cor'>";
                            $arquivo .= "<td nowrap>$posto - $posto_nome</td>";
                            $arquivo .= "<td nowrap>$posto_cidade</td>";
                            $arquivo .= "<td>$posto_estado</td>";
                            $arquivo .= "<td>".formatCPFCNPJ($posto_cnpj)."</td>";
                            $arquivo .= "<td nowrap>$posto_banco</td>";
                            $arquivo .= "<td>$posto_agencia</td>";
                            $arquivo .= "<td>$posto_conta</td>";
                            $arquivo .= "<td>$extrato_data</td>";
                            $arquivo .= "<td>$extrato</td>";
                            $arquivo .= "<td style='text-align:right'>$extrato_total_os</td>";
                            $arquivo .= "<td style='text-align:right'>R$ $extrato_total</td>";
                            $arquivo .= "</tr>";
                            /*
                            fputs($fp,"<tr class='Conteudo' style='background-color:$cor'>");
                            fputs($fp,"<td nowrap>$posto - $posto_nome</td>");
                            fputs($fp,"<td nowrap>$posto_cidade</td>");
                            fputs($fp,"<td>$posto_estado</td>");
                            fputs($fp,"<td>".formatCPFCNPJ($posto_cnpj)."</td>");
                            fputs($fp,"<td nowrap>$posto_banco</td>");
                            fputs($fp,"<td>$posto_agencia</td>");
                            fputs($fp,"<td>$posto_conta</td>");
                            fputs($fp,"<td>$extrato_data</td>");
                            fputs($fp,"<td>$extrato</td>");
                            fputs($fp,"<td style='text-align:right'>$extrato_total_os</td>");
                            fputs($fp,"<td style='text-align:right'>R$ $extrato_total</td>");
                            fputs($fp,"</tr>");
                            */
                        }
                    break;
                    case 39:
                        $sqlCase = "SELECT  *
                                    FROM    tmp_extrato_pagamento
                                    WHERE   linha = 39
                        ";
                        $resCase = pg_query($con,$sqlCase);
?>
        <tr class="titulo_coluna">
            <th colspan="11">Extrato da linha Eletroeletrônico</th>
        </tr>
        <tr class="titulo_coluna">
            <td colspan="11">
                Obs:
                <input type="text" id="obs_eletro" name="obs_eletro" value="" style="width:40px;" />
            </td>
        </tr>
<?
                        $contaCase = pg_num_rows($resCase);

                        $arquivo .= "<tr class='titulo_coluna' style='background-color:$cor'>";
                        $arquivo .= "<th colspan='11'>Extrato da linha Eletroeletrônico</th>";
                        $arquivo .= "</tr>";
                        $arquivo .= "<div id='obs_eletro'/>";
                        /*
                        fputs($fp,"<tr class='titulo_coluna' style='background-color:$cor'>");
                        fputs($fp,"<th colspan='11'>Extrato da linha Eletroeletrônico</th>");
                        fputs($fp,"</tr>");
                        fputs($fp,"<div id='obs_eletro'/>");
                        */
                        for($i=0;$i<$contaCase;$i++){
                            $posto              = pg_fetch_result($resCase,$i,codigo_posto);
                            $posto_nome         = pg_fetch_result($resCase,$i,nome);
                            $posto_cidade       = pg_fetch_result($resCase,$i,contato_cidade);
                            $posto_estado       = pg_fetch_result($resCase,$i,contato_estado);
                            $posto_cnpj         = pg_fetch_result($resCase,$i,cnpj);
                            $posto_banco        = pg_fetch_result($resCase,$i,nomebanco);
                            $posto_agencia      = pg_fetch_result($resCase,$i,agencia);
                            $posto_conta        = pg_fetch_result($resCase,$i,conta);
                            $extrato_data       = pg_fetch_result($resCase,$i,data_geracao);
                            $extrato            = pg_fetch_result($resCase,$i,extrato);
                            $extrato_total      = pg_fetch_result($resCase,$i,total);
                            $extrato_total_os   = pg_fetch_result($resCase,$i,total_os);

                            $extrato_total = number_format($extrato_total,2,",",".");
                            $cor = ( $i%2 ) ? '#F7F5F0' : '#F1F4FA';
?>
        <tr style="background-color:<?=$cor?>">
            <td nowrap><abbr title="<?=$posto." - ".$posto_nome?>"><? echo substr($posto_nome,0,17); ?></abbr></td>
            <td nowrap><?=$posto_cidade?></td>
            <td><?=$posto_estado?></td>
            <td nowrap><? echo formatCPFCNPJ($posto_cnpj); ?></td>
            <td nowrap><?=$posto_banco?></td>
            <td><?=$posto_agencia?></td>
            <td><?=$posto_conta?></td>
            <td><?=$extrato_data?></td>
            <td><?=$extrato?></td>
            <td style="text-align:right"><?=$extrato_total_os?></td>
            <td style="text-align:right"><?="R$".$extrato_total?></td>
        </tr>
<?
                            $arquivo .= "<tr class='Conteudo' style='background-color:$cor'>";
                            $arquivo .= "<td nowrap>$posto - $posto_nome</td>";
                            $arquivo .= "<td nowrap>$posto_cidade</td>";
                            $arquivo .= "<td>$posto_estado</td>";
                            $arquivo .= "<td>".formatCPFCNPJ($posto_cnpj)."</td>";
                            $arquivo .= "<td nowrap>$posto_banco</td>";
                            $arquivo .= "<td>$posto_agencia</td>";
                            $arquivo .= "<td>$posto_conta</td>";
                            $arquivo .= "<td>$extrato_data</td>";
                            $arquivo .= "<td>$extrato</td>";
                            $arquivo .= "<td style='text-align:right'>$extrato_total_os</td>";
                            $arquivo .= "<td style='text-align:right'>R$ $extrato_total</td>";
                            $arquivo .= "</tr>";
                            /*
                            fputs($fp,"<tr class='Conteudo' style='background-color:$cor'>");
                            fputs($fp,"<td nowrap>$posto - $posto_nome</td>");
                            fputs($fp,"<td nowrap>$posto_cidade</td>");
                            fputs($fp,"<td>$posto_estado</td>");
                            fputs($fp,"<td>".formatCPFCNPJ($posto_cnpj)."</td>");
                            fputs($fp,"<td nowrap>$posto_banco</td>");
                            fputs($fp,"<td>$posto_agencia</td>");
                            fputs($fp,"<td>$posto_conta</td>");
                            fputs($fp,"<td>$extrato_data</td>");
                            fputs($fp,"<td>$extrato</td>");
                            fputs($fp,"<td style='text-align:right'>$extrato_total_os</td>");
                            fputs($fp,"<td style='text-align:right'>R$ $extrato_total</td>");
                            fputs($fp,"</tr>");
                            */
                        }
                    break;
                    case 40:
                        $sqlCase = "SELECT  *
                                    FROM    tmp_extrato_pagamento
                                    WHERE   linha = 40
                        ";
                        $resCase = pg_query($con,$sqlCase);
?>
        <tr class="titulo_coluna">
            <th colspan="11">Extrato da linha Informática</th>
        </tr>
        <tr class="titulo_coluna">
            <td colspan="11">
                Obs:
                <input type="text" id="obs_inf" name="obs_inf" value="" style="width:40px;" />
            </td>
        </tr>
<?
                        $contaCase = pg_num_rows($resCase);

                        $arquivo .= "<tr class='titulo_coluna' style='background-color:$cor'>";
                        $arquivo .= "<th colspan='11'>Extrato da linha Informática</th>";
                        $arquivo .= "</tr>";
                        $arquivo .= "<div id='obs_inf'/>";
                        /*
                        fputs($fp,"<tr class='titulo_coluna' style='background-color:$cor'>");
                        fputs($fp,"<th colspan='11'>Extrato da linha Informática</th>");
                        fputs($fp,"</tr>");
                        fputs($fp,"<div id='obs_inf'/>");
                        */
                        for($i=0;$i<$contaCase;$i++){
                            $posto              = pg_fetch_result($resCase,$i,codigo_posto);
                            $posto_nome         = pg_fetch_result($resCase,$i,nome);
                            $posto_cidade       = pg_fetch_result($resCase,$i,contato_cidade);
                            $posto_estado       = pg_fetch_result($resCase,$i,contato_estado);
                            $posto_cnpj         = pg_fetch_result($resCase,$i,cnpj);
                            $posto_banco        = pg_fetch_result($resCase,$i,nomebanco);
                            $posto_agencia      = pg_fetch_result($resCase,$i,agencia);
                            $posto_conta        = pg_fetch_result($resCase,$i,conta);
                            $extrato_data       = pg_fetch_result($resCase,$i,data_geracao);
                            $extrato            = pg_fetch_result($resCase,$i,extrato);
                            $extrato_total      = pg_fetch_result($resCase,$i,total);
                            $extrato_total_os   = pg_fetch_result($resCase,$i,total_os);

                            $extrato_total = number_format($extrato_total,2,",",".");
                            $cor = ( $i%2 ) ? '#F7F5F0' : '#F1F4FA';
?>
        <tr style="background-color:<?=$cor?>">
            <td nowrap><abbr title="<?=$posto." - ".$posto_nome?>"><? echo substr($posto_nome,0,17); ?></abbr></td>
            <td nowrap><?=$posto_cidade?></td>
            <td><?=$posto_estado?></td>
            <td nowrap><? echo formatCPFCNPJ($posto_cnpj); ?></td>
            <td nowrap><?=$posto_banco?></td>
            <td><?=$posto_agencia?></td>
            <td><?=$posto_conta?></td>
            <td><?=$extrato_data?></td>
            <td><?=$extrato?></td>
            <td style="text-align:right"><?=$extrato_total_os?></td>
            <td style="text-align:right"><?="R$".$extrato_total?></td>
        </tr>
<?
                            $arquivo .= "<tr class='Conteudo' style='background-color:$cor'>";
                            $arquivo .= "<td nowrap>$posto - $posto_nome</td>";
                            $arquivo .= "<td nowrap>$posto_cidade</td>";
                            $arquivo .= "<td>$posto_estado</td>";
                            $arquivo .= "<td>".formatCPFCNPJ($posto_cnpj)."</td>";
                            $arquivo .= "<td nowrap>$posto_banco</td>";
                            $arquivo .= "<td>$posto_agencia</td>";
                            $arquivo .= "<td>$posto_conta</td>";
                            $arquivo .= "<td>$extrato_data</td>";
                            $arquivo .= "<td>$extrato</td>";
                            $arquivo .= "<td style='text-align:right'>$extrato_total_os</td>";
                            $arquivo .= "<td style='text-align:right'>R$ $extrato_total</td>";
                            $arquivo .= "</tr>";
                            /*
                            fputs($fp,"<tr class='Conteudo' style='background-color:$cor'>");
                            fputs($fp,"<td nowrap>$posto - $posto_nome</td>");
                            fputs($fp,"<td nowrap>$posto_cidade</td>");
                            fputs($fp,"<td>$posto_estado</td>");
                            fputs($fp,"<td>".formatCPFCNPJ($posto_cnpj)."</td>");
                            fputs($fp,"<td nowrap>$posto_banco</td>");
                            fputs($fp,"<td>$posto_agencia</td>");
                            fputs($fp,"<td>$posto_conta</td>");
                            fputs($fp,"<td>$extrato_data</td>");
                            fputs($fp,"<td>$extrato</td>");
                            fputs($fp,"<td style='text-align:right'>$extrato_total_os</td>");
                            fputs($fp,"<td style='text-align:right'>R$ $extrato_total</td>");
                            fputs($fp,"</tr>");
                            */
                        }
                    break;
                    case -1:
                        $sqlCase = "SELECT  *
                                    FROM    tmp_extrato_pagamento
                                    WHERE   linha = -1
                        ";
                        $resCase = pg_query($con,$sqlCase);
?>
        <tr class="titulo_coluna">
            <th colspan="11">Extrato de linhas diversas</th>
        </tr>
        <tr class="titulo_coluna">
            <td colspan="11">
                Obs:
                <input type="text" id="obs_div" name="obs_div" value="" style="width:40px;" />
            </td>
        </tr>
<?
                        $contaCase = pg_num_rows($resCase);

                        $arquivo .= "<tr class='titulo_coluna' style='background-color:$cor'>";
                        $arquivo .= "<th colspan='11'>Extrato de linhas diversas</th>";
                        $arquivo .= "</tr>";
                        $arquivo .= "<div id='obs_div'/>";
                        /*
                        fputs($fp,"<tr class='titulo_coluna' style='background-color:$cor'>");
                        fputs($fp,"<th colspan='11'>Extrato de linhas diversas</th>");
                        fputs($fp,"</tr>");
                        fputs($fp,"<div id='obs_div'/>");
                        */

                        for($i=0;$i<$contaCase;$i++){
                            $posto              = pg_fetch_result($resCase,$i,codigo_posto);
                            $posto_nome         = pg_fetch_result($resCase,$i,nome);
                            $posto_cidade       = pg_fetch_result($resCase,$i,contato_cidade);
                            $posto_estado       = pg_fetch_result($resCase,$i,contato_estado);
                            $posto_cnpj         = pg_fetch_result($resCase,$i,cnpj);
                            $posto_banco        = pg_fetch_result($resCase,$i,nomebanco);
                            $posto_agencia      = pg_fetch_result($resCase,$i,agencia);
                            $posto_conta        = pg_fetch_result($resCase,$i,conta);
                            $extrato_data       = pg_fetch_result($resCase,$i,data_geracao);
                            $extrato            = pg_fetch_result($resCase,$i,extrato);
                            $extrato_total      = pg_fetch_result($resCase,$i,total);
                            $extrato_total_os   = pg_fetch_result($resCase,$i,total_os);

                            $extrato_total = number_format($extrato_total,2,",",".");
                            $cor = ( $i%2 ) ? '#F7F5F0' : '#F1F4FA';
?>
        <tr style="background-color:<?=$cor?>">
            <td nowrap><abbr title="<?=$posto." - ".$posto_nome?>"><? echo substr($posto_nome,0,17); ?></abbr></td>
            <td nowrap><?=$posto_cidade?></td>
            <td><?=$posto_estado?></td>
            <td nowrap><? echo formatCPFCNPJ($posto_cnpj); ?></td>
            <td nowrap><?=$posto_banco?></td>
            <td><?=$posto_agencia?></td>
            <td><?=$posto_conta?></td>
            <td><?=$extrato_data?></td>
            <td><?=$extrato?></td>
            <td style="text-align:right"><?=$extrato_total_os?></td>
            <td style="text-align:right"><?="R$".$extrato_total?></td>
        </tr>
<?
                            $arquivo .= "<tr class='Conteudo' style='background-color:$cor'>";
                            $arquivo .= "<td nowrap>$posto - $posto_nome</td>";
                            $arquivo .= "<td nowrap>$posto_cidade</td>";
                            $arquivo .= "<td>$posto_estado</td>";
                            $arquivo .= "<td>".formatCPFCNPJ($posto_cnpj)."</td>";
                            $arquivo .= "<td nowrap>$posto_banco</td>";
                            $arquivo .= "<td>$posto_agencia</td>";
                            $arquivo .= "<td>$posto_conta</td>";
                            $arquivo .= "<td>$extrato_data</td>";
                            $arquivo .= "<td>$extrato</td>";
                            $arquivo .= "<td style='text-align:right'>$extrato_total_os</td>";
                            $arquivo .= "<td style='text-align:right'>R$ $extrato_total</td>";
                            $arquivo .= "</tr>";
                            /*
                            fputs($fp,"<tr class='Conteudo' style='background-color:$cor'>");
                            fputs($fp,"<td nowrap>$posto - $posto_nome</td>");
                            fputs($fp,"<td nowrap>$posto_cidade</td>");
                            fputs($fp,"<td>$posto_estado</td>");
                            fputs($fp,"<td>".formatCPFCNPJ($posto_cnpj)."</td>");
                            fputs($fp,"<td nowrap>$posto_banco</td>");
                            fputs($fp,"<td>$posto_agencia</td>");
                            fputs($fp,"<td>$posto_conta</td>");
                            fputs($fp,"<td>$extrato_data</td>");
                            fputs($fp,"<td>$extrato</td>");
                            fputs($fp,"<td style='text-align:right'>$extrato_total_os</td>");
                            fputs($fp,"<td style='text-align:right'>R$ $extrato_total</td>");
                            fputs($fp,"</tr>");*/
                        }
                    break;
                }
            }
            $arquivo .= "</tbody>";
            $arquivo .= "</table>";
            $arquivo .= "</body>";
            $arquivo .= "</html>";
            /*
            fputs($fp,"</tbody>");
            fputs($fp,"</table>");
            fputs($fp,"</body>");
            fputs($fp,"</html>");
            fclose ($fp);
            */

            #echo `htmldoc --webpage --size 297x210mm --fontsize 11 --left 8mm -f /xls/relatorio_pagamento_posto-$login_fabrica.$data.xls /xls/relatorio_pagamento_posto-$login_fabrica.html`;
            #rename("../xls/relatorio_pagamento_posto_linha-$login_fabrica.html","xls/relatorio_pagamento_posto_linha-$login_fabrica.$data.xls");
?>
    <input type="hidden" name="arquivo" id="arquivo" value="<?=$arquivo?>" />
    <input type="hidden" name="arquivo_intacto" id="arquivo_intacto" value="<?=$arquivo?>" />
    </tbody>
</table>
<br>
<?
        }else{
?>
        <br><br><p>Nenhum resultado encontrado!</p>
<?
        }
    }else{
        $msg_erro = "Favor colocar período para busca";
    }
}

include 'rodape.php';
?>
