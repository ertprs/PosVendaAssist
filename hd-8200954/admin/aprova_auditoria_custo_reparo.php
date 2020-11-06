<?php

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

/* Área do Admin    */
//Opções: 'auditoria', 'cadastros', 'call_center', 'financeiro', 'gerencia'  'info_tecnica'
$admin_privilegios = "auditoria";
include 'autentica_admin.php';

/*------------------*/
include 'funcoes.php';
// Opcional
include '../helpdesk/mlg_funciones.php'; //Admin

include_once '../class/communicator.class.php';
include_once "../helpdesk.inc.php";

$title = "Auditoria de Custo Reparo X Troca Produto";
//Opções: 'cadastro', 'callcenter', 'financeiro', 'gerencia', 'tecnica'
$layout_menu = 'auditoria';

if(isset($_GET['excluir'])){
    $os_item    = (int)$_GET["os_item"];
    $os         = (int)$_GET["os"];

    $sql = "BEGIN TRANSACTION";
    $resx = pg_query($con, $sql);

    $sql_os_item = "SELECT tbl_os.os, tbl_os.produto, tbl_os.posto, tbl_os_item.os_produto
        from tbl_os_item
        inner join tbl_os_produto on tbl_os_produto.os_produto = tbl_os_item.os_produto
        inner join tbl_os on tbl_os.os = tbl_os_produto.os
        where os_item = $os_item ";
    $res_os_item = pg_query($con, $sql_os_item);
        $posto = pg_fetch_result($res_os_item, 0, posto);
        $produto = pg_fetch_result($res_os_item, 0, produto);
        $os_produto = pg_fetch_result($res_os_item, 0, os_produto);

    $msg_erro .= pg_last_error($con);

    $sql_del = "DELETE FROM tbl_os_item where os_item = $os_item";
    $res_del = pg_query($con, $sql_del);
    $msg_erro .= pg_last_error($con);

    $sql_os_produto = "DELETE FROM tbl_os_produto where os_produto = $os_produto";
    $res_os_produto = pg_query($con, $sql_os_produto);
    $msg_erro .= pg_last_error($con);

    require_once "../classes/Posvenda/Fabricas/_1/CustoPeca.php";
    $custoPeca = new CustoPeca($os, $login_fabrica, $posto, $con);
    $dadosCusto = $custoPeca->getCustoPeca();

    $dadosMobraTroca = $custoPeca->getMObra($os, true);
    $Mobra = $dadosMobraTroca['mao_de_obra'];

    if($dadosCusto['linha'] == 199){
        $valorProduto['total_produto'] = ($dadosCusto['medioCr'] * 1.15) + $Mobra;
        $valorProduto['multiplicador'] = '15';
    }else{
        $valorProduto['total_produto'] = ($dadosCusto['medioCr'] * 1.10) + $Mobra;
        $valorProduto['multiplicador'] = '10';
    }
    $valorProduto['Mobra_produto'] = $Mobra;

    if($dadosCusto['custo_pecas'] >= ($valorProduto['total_produto'] * 1.2) ){
        if($dadosCusto['reembolso_peca_estoque'] == 't'){
            $msg_erro .= $custoPeca->GravarAuditoria($os, $dadosCusto['custo_pecas']);
        }
    }else{
        $msg_erro .= $custoPeca->RetiraAuditoria($os);
    }
    $msg_erro .= $custoPeca->GravarCampoExtra($dadosCusto, $valorProduto);

    if(strlen($msg_erro) > 0){
        $sql = "ROLLBACK TRANSACTION";
        $resx = pg_query($con, $sql);
        echo "erro";
    }else{
        $sql = "COMMIT TRANSACTION";
        $resx = pg_query($con, $sql);
        echo "OK";
    }
    exit;
}

if(isset($_GET["aprovar"])){
    $os = (int)$_GET['os'];
    $sql = "SELECT auditoria_os FROM tbl_auditoria_os WHERE os = $os and auditoria_status = 4 and cancelada isnull and liberada isnull and reprovada isnull ORDER BY auditoria_os desc limit 1";
    $res = pg_query($con, $sql);
    if(pg_num_rows($res)>0){
        $auditoria_os   = pg_fetch_result($res, 0, auditoria_os);
        $sql = "UPDATE tbl_auditoria_os set liberada = now(), admin = $login_admin, bloqueio_pedido = false where auditoria_os = $auditoria_os";
        $res = pg_query($con, $sql);
        if(strlen(pg_last_error($res))> 0){
            echo "Falha ao aprovar O.S $os";
        }else{
            echo "OS aprovada com sucesso. ";
        }
    }
    exit;
}

if(isset($_GET["recusar"])){
    $os = (int)$_GET['os'];
    $motivo = $_GET['motivo'];

    $sql = "SELECT auditoria_os FROM tbl_auditoria_os WHERE os = $os AND auditoria_status = 4 and cancelada isnull and liberada isnull and reprovada isnull ORDER BY auditoria_os desc limit 1";
    $res = pg_query($con, $sql);
    if(pg_num_rows($res)>0){
        $auditoria_os   = pg_fetch_result($res, 0, auditoria_os);

        $sql = "UPDATE tbl_auditoria_os set reprovada = now(), justificativa = '$motivo', admin = $login_admin where auditoria_os = $auditoria_os";
        $res = pg_query($con, $sql);
        if(strlen(pg_last_error($res))> 0){
            echo "Falha ao reprovar a O.S $os";
        }else{
            echo "OS reprovada com sucesso. ";
        }
    }
    exit;
}


include "cabecalho.php";
extract(array_filter($_POST, 'anti_injection')); // cria as variáveis com os campos do formulário


$regioes = array('NORTE'  => 'Região Norte',
                 'NORDESTE' => 'Região Nordeste',
                 'CENTRO-OESTE' => 'Região Centro-Oeste',
                 'SUDESTE' => 'Região Sudeste',
                 'SUL'  => 'Região Sul');

if($_GET['buscaRegiao']){
    $reg = $_GET['regiao'];
    if($reg == "CENTRO-OESTE"){
        $estados = array('GO'=>'Goiás',
                        'MS'=>'Mato Grosso do Sul',
                        'MT'=>'Mato Grosso',
                        'DF'=>'Distrito Federal');
    } else if($reg == "NORDESTE"){

        $estados = array('SE'=>'Sergipe',
                        'AL'=>'Alagoas',
                        'RN'=>'Rio Grande do Norte',
                        'MA'=>'Maranhão',
                        'PE'=>'Pernambuco',
                        'PB'=>'Paraíba',
                        'CE'=>'Ceará',
                        'PI'=>'Piauí',
                        'BA'=>'Bahia');

    } else if($reg == "NORTE"){
        $estados = array('TO'=>'Tocantins',
                        'PA'=>'Pará',
                        'AP'=>'Amapa',
                        'RR'=>'Roraima',
                        'AM'=>'Amazonas',
                        'AC'=>'Acre',
                        'RO'=>'Rondônia');
    } else if($reg == "SUDESTE"){
        $estados = array('ES'=>'Espírito Santos',
                        'MG'=>'Minas Gerais',
                        'RJ'=>'Rio de Janeiro',
                        'SP'=>'São Paulo');
    } else if($reg == "SUL"){
        $estados = array('PR'=>'Paraná',
                        'RS'=>'Rio Grande do Sul',
                        'SC'=>'Santa Catarina');
    }

        $retorno = "<option value=''>Selecione um Estado</option>";
        foreach ($estados as $sigla_estado=>$nome_estado) {
            $nome_estado = utf8_encode($nome_estado);
            $retorno .= "<option value='$sigla_estado'>$nome_estado</option>";
        }

    echo $retorno;
    exit;
}


if(isset($_POST['btn_acao'])){

    $codigo_posto = $_POST['posto_codigo'];
    $data_inicial = $_POST['data_inicial'];
    $data_final   = $_POST['data_final'];
    $regiao       = $_POST['regiao'];
    $estado       = $_POST['estado'];
    $os           = trim($_POST['os']);
    $situacao     = $_POST['situacao'];

    //validar Data
    if (!empty($_POST["data_inicial"]) && !empty($_POST["data_final"])) {

        $data_inicial=$_POST["data_inicial"];
        $data_fim=$_POST["data_final"];

        if(strlen($msg_erro)==0){
            $dat = explode ("/", $data_inicial );//tira a barra
                $d = $dat[0];
                $m = $dat[1];
                $y = $dat[2];
                if(!checkdate($m,$d,$y)) $msg_erro = "Data Inválida";
        }
        if(strlen($msg_erro)==0){
            $dat = explode ("/", $data_fim );//tira a barra
                $d = $dat[0];
                $m = $dat[1];
                $y = $dat[2];
                if(!checkdate($m,$d,$y)) $msg_erro = "Data Inválida";
        }

        if(strlen($msg_erro)==0){
            $d_ini = explode ("/", $data_inicial);//tira a barra
            $xdata_inicial = "$d_ini[2]-$d_ini[1]-$d_ini[0]";//separa as datas $d[2] = ano $d[1] = mes etc...


            $d_fim = explode ("/", $data_fim);//tira a barra
            $xdata_fim = "$d_fim[2]-$d_fim[1]-$d_fim[0]";//separa as datas $d[2] = ano $d[1] = mes etc...

            if($xdata_fim < $xdata_inicial){
                $msg_erro = "A Data Fim deve ser maior do que a Data Início.";
            }
        }
        if(strlen($msg_erro)==0){
            $cond_data = "AND tbl_auditoria_os.data_input BETWEEN '$xdata_inicial 00:00:00' and '$xdata_fim 23:59:59'";
        }
    } else if ((empty($_POST["data_inicial"]) && empty($_POST["data_final"])) && empty($os) && ($login_fabrica == 1 && $situacao != "em_aprovacao")){
        $msg_erro = "Por favor, preencha as datas";
    }

    if(strlen($codigo_posto) > 0  ){
        $cond_posto = " AND tbl_posto_fabrica.codigo_posto = '$codigo_posto' ";
    }

    if($regiao == "NORTE"){
        $cond_regiao = " AND tbl_posto.estado in (TO, PA, AP, RR, AM, AC, RO) ";
    }elseif($regiao == "SUL"){
        $cond_regiao = " AND tbl_posto.estado in (SC,RS, PR) ";
    }elseif($regiao == "SUDESTE"){
        $cond_regiao = " AND tbl_posto.estado in ('SP', 'RJ', 'ES', 'MG') ";
    }elseif($regiao == "CENTRO-OESTE"){
        $cond_regiao = " AND tbl_posto.estado in (GO, MS, MT, DF) ";
    }elseif($regiao == "NORDESTE"){
        $cond_regiao = " AND tbl_posto.estado in (SE, AL, RN, MA, PE, PB, CE, PI, BA) ";
    }

    if(strlen($estado)>0){
        $cond_estado = " AND tbl_posto.estado = '$estado' ";
    }

    if (strlen($os)> 0) {

        $numos = strpos($os, '-');

        if($numos === false){
            $sua_os = substr($os, -7);
        }else{
            $oos = explode("-", $os);
            $sua_os = substr($oos[0], -7);
            $sua_os = $sua_os."-".$oos[1];
        }

        $cond_os = " AND tbl_os.sua_os = '$sua_os' ";
    }

    if(strlen($situacao)> 0 ){
        if($situacao == "aprovadas"){
            $cond_situacao = " AND tbl_auditoria_os.liberada is not null  AND justificativa is null";
        }elseif($situacao == "reprovadas"){
            $cond_situacao = " AND tbl_auditoria_os.reprovada is not null ";
        }elseif($situacao == "em_aprovacao"){
            $cond_situacao = " AND tbl_auditoria_os.reprovada is null AND tbl_auditoria_os.liberada is null ";
        }elseif($situacao == "trocadas"){
            $cond_situacao = " AND tbl_auditoria_os.liberada is not null AND justificativa = 'Gerando troca de produto'";
        }

    }

    if(strlen($msg_erro) == 0 ){

        $sql = "
            SELECT  tbl_os.os,
                    tbl_os.sua_os,
                    tbl_posto_fabrica.codigo_posto,
                    tbl_posto.nome,
                    tbl_auditoria_os.justificativa,
                    tbl_auditoria_os.reprovada,
                    tbl_auditoria_os.liberada,
                    tbl_posto.nome,
                    tbl_tipo_posto.descricao as tipo_posto_descricao,
                    (
                        SELECT  SUM(tbl_os_item.qtde)
                        FROM    tbl_os_produto
                        JOIN    tbl_os_item ON tbl_os_produto.os_produto =  tbl_os_item.os_produto
                        WHERE   tbl_os_produto.os =  tbl_os.os
                    ) AS qtde_pecas ,
                    tbl_os_campo_extra.campos_adicionais

            FROM    tbl_auditoria_os
            JOIN    tbl_os              ON  tbl_auditoria_os.os         = tbl_os.os
            JOIN    tbl_posto           ON  tbl_posto.posto             = tbl_os.posto
            JOIN    tbl_posto_fabrica   ON  tbl_posto_fabrica.posto     = tbl_posto.posto
                                        AND tbl_posto_fabrica.fabrica   =  $login_fabrica
            JOIN    tbl_tipo_posto      ON  tbl_tipo_posto.tipo_posto   = tbl_posto_fabrica.tipo_posto
            JOIN    tbl_os_campo_extra  ON  tbl_os_campo_extra.os       = tbl_os.os

            WHERE   tbl_os.fabrica = $login_fabrica
            AND     auditoria_status = 4
            AND     tbl_auditoria_os.observacao = 'OS em auditoria de peça Valor Reparo X Troca'
            $cond_regiao
            $cond_posto
            $cond_estado
            $cond_data
            $cond_os
            $cond_situacao
      ORDER BY      tbl_os.data_abertura
            ";
        $res = pg_query($con, $sql);
    }
}



include 'javascript_pesquisas_novo.php'; //Admin
?>
<link rel="stylesheet" type="text/css" href="../plugins/jquery/datepick/telecontrol.datepick.css" />
<link rel="stylesheet" type="text/css" href="bootstrap/css/extra.css" />
<script type="text/javascript" src="../plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
<script type="text/javascript" src="../js/jquery.maskedinput2.js"></script>
<script type="text/javascript" src="../plugins/jquery/datepick/jquery.datepick.js"></script>
<script type="text/javascript" src="../plugins/jquery/datepick/jquery.datepick-pt-BR.js"></script>

<script type="text/javascript" src="plugins/jquery.form.js"></script>
<script type="text/javascript" src="../plugins/shadowbox/shadowbox.js"></script>
<link rel="stylesheet" type="text/css" href="../plugins/shadowbox/shadowbox.css" />
<script type='text/javascript' src='../js/FancyZoom.js'></script>
<script type='text/javascript' src='../js/FancyZoomHTML.js'></script>

<script type="text/javascript" charset="utf-8">
$(function(){
    $("#data_inicial").datepick({startDate:"01/01/2000"});
    $("#data_inicial").maskedinput("99/99/9999");

    $("#data_final").datepick({startDate:"01/01/2000"});
    $("#data_final").maskedinput("99/99/9999");

    $('#motivo').attr('readonly', true);

    $("#acao_tudo").change(function(){
        var opcao = $("#acao_tudo").val();
        if(opcao == 'rp'){
            $('#motivo').attr('readonly', false);
        }else{
            $('#motivo').attr('readonly', true);
        }
    });

    $(".checkTodos").change(function(){
        if( $(".checkTodos").is(":checked") ){
            $('[class^="check"]').prop("checked", true);
        }else{
            $('[class^="check"]').prop("checked", false);
        }
    });

    Shadowbox.init();
    setupZoom();

    $("td[class^='plus_']").click(function(){
        var posicao = $(this).data('num');
        if($(".linha_"+posicao).is(':visible')){
            $(".linha_"+posicao).css('display', 'none');
        }else{
            $("tr[class^='linha_']").hide();
            $(".linha_"+posicao).show();
        }
    });


    $("button[class^='excluir_item_'").click(function(){
        var os_item = $(this).data('os_item');
        var numos = $(this).data('os');

        $.ajax({
            url: "<?php echo $_SERVER['PHP_SELF']; ?>?excluir=1&os_item="+os_item+"&os="+numos,
            cache: false,
            success: function(data){
                if(data == 'erro'){
                    alert('Falha ao excluir peça.');
                }else{
                    alert('Peça excluída com sucesso.');
                    $(".excluir_item_"+os_item).remove();
                }

            }
        });
    });

    $("#gravar_checked").click(function(){
        $( ".check" ).each(function() {
            var opcao = $("#acao_tudo").val();

            if( $(this).is(":checked") ){
                var obj = this;
                var num_os = $(this).val();
                if(opcao == 'ap'){
                    console.log('sim ap '+ num_os);
                    AprovarOS(this, num_os);
                }else if(opcao == 'rp'){
                    console.log('sim rp '+ num_os);
                    ReprovarOS(this, num_os);
                }else{
                    alert("Escolha uma ação.");
                }
            }else{
                console.log('nao');
            }

          //$( this ).addClass( "foo" );
        });
    });


    $(".aprovar").click(function(){
        AprovarOS(this, '');
    });

    $(".recusar").click(function(){
        ReprovarOS(this, '');
    });

    $(".gerar_troca").click(function(){
        var numos = $(this).data('os');
        window.open("os_cadastro_troca_black.php?os="+numos+'&valor_peca=true');
    });

});


function AprovarOS(obj, osnum){
    if(osnum.length > 0){
        var numos = osnum;
    }else{
        var numos = $(obj).data('os');
    }
    $('[data-os='+numos+']').hide();
    $("#linha_"+numos).html("Aguarde...");
    $.ajax({
        url: "<?php echo $_SERVER['PHP_SELF']; ?>?aprovar=1&os="+numos,
        cache: false,
        success: function(data) {
            alert(data);
            $("#linha_"+numos).html("Aprovada");
        }
    });
}

function ReprovarOS(obj , osnum){
    if(osnum.length > 0){
        var numos = osnum;
        var motivo = $('#motivo').val();
    }else{
        var numos = $(obj).data('os');
        var motivo = prompt("Informe o Motivo:");
    }
    $('[data-os='+numos+']').hide();
    $("#linha_"+numos).html("Aguarde...");
    console.log("motivo "+motivo);
    if(motivo.length > 0){
        $.ajax({
            url: "<?php echo $_SERVER['PHP_SELF']; ?>?recusar=1&os="+numos+"&motivo="+motivo,
            cache: false,
            success: function(data) {
                alert(data);
                $("#linha_"+numos).html("Reprovada");
            }
        });
    }
}

function retorna_posto(codigo_posto, posto,nome, cnpj, cidade, estado, credenciamento, num_posto, cep, endereco, numero, bairro){
    gravaDados('posto_codigo',codigo_posto);
    gravaDados('posto_nome',nome);
}




 </script>

<style type="text/css">
.menu_top{text-align:center;font:normal bold 10px Verdana,Geneva,Arial,Helvetica,sans-serif;border:1px solid;color:#596d9b;background-color:#d9e2ef;}
.border{border:1px solid #ced7e7;}
.table_line{text-align:center;font:normal normal 10px Verdana,Geneva,Arial,Helvetica,sans-serif;font-family:Verdana,Geneva,Arial,Helvetica,sans-serif;border:0px solid;background-color:white;}
input{font-size:10px;}
.top_list{text-align:center;font:normal bold 10px Verdana,Geneva,Arial,Helvetica,sans-serif;color:#596d9b;background-color:#d9e2ef;}
.line_list{text-align:left;font-family:Verdana,Geneva,Arial,Helvetica,sans-serif;font-size:x-small;font-weight:normal;color:#596d9b;background-color:white;}
caption, .titulo_tabela {background-color:#596d9b;font:bold 14px "Arial";color:white;text-align:center;}
thead, .titulo_coluna {background-color:#596d9b;font:bold 11px "Arial";color:white;text-align:center;}
.formulario{background-color:#D9E2EF;font:normal normal 11px Arial;width:700px;margin:auto;text-align:left;}
.msg, .msg_erro{background-color:#FF0000;font:bold 16px "Arial";color:white;text-align:center;}
.formulario caption{padding: 3px;}
.msg{background-color:#51AE51;color:white;}
table.tabela tr td{font-family:verdana;font-size:11px;border-collapse:collapse;border:1px solid #596d9b;}
.texto_avulso{font:14px Arial;color:rgb(89,109,155);background-color:#d9e2ef;text-align:center;width:700px;margin:0 auto;border-collapse:collapse;border:1px solid #596d9b;}
.btn_excel {
  -pie-background: linear-gradient(top, #559435 0%, #63AE3D 72%);
  behavior: url(plugins/PIE/PIE.htc);
}
.btn_excel, .btn_excel span, .btn_excel span img, .btn_excel span.txt {
  background: #FFFFFF !important;
  background-color: #FFFFFF !important;
  background-image: #FFFFFF !important;
  border: 0px;
}
.btn_excel span.txt {
        color: #0088cc;
}
</style>


<table id = "erro" width='700' align='center' border='0' bgcolor='#d9e2ef'>
<? if (strlen ($msg_erro) > 0) { ?>
    <tr class="msg_erro">
        <td> <? echo $msg_erro; ?></td>
    </tr>
<? } ?>

<? if (strlen ($msg) > 0) { ?>
    <tr class="msg">
        <td> <? echo $msg; ?></td>
    </tr>
<? } ?>
</table>

<form method="post" name="frm_pesquisa" action="aprova_auditoria_custo_reparo.php">
    <table class="formulario" border='0' cellpadding='5' cellspacing='2'>
        <caption>PARÂMETROS DA PESQUISA</caption>
        <tr><td colspan='3'>&nbsp;</td></tr>
        <tr>
            <td style='width:135px'>&nbsp;</td>
            <td style='width:165px'>
                <label for="data_inicial">Código Posto</label><br>
                <input type="text" name="posto_codigo" size="12" value="<?echo $posto_codigo?>" class="frm">
                <img border="0" src="imagens/lupa.png" style="cursor: pointer;" align="absmiddle" alt="Clique aqui para pesquisar postos pelo código" onclick="javascript: fnc_pesquisa_posto ('', document.frm_pesquisa.posto_codigo,'')">
            </td>
            <td>
                <label for="data_final">Descrição Posto</label><br>
                <input type="text" name="posto_nome" size="30" value="<?echo $posto_nome?>" class="frm">
                <img border="0" src="imagens/lupa.png" style="cursor: pointer;" align="absmiddle" alt="Clique aqui para pesquisar postos pelo código" onclick="javascript: fnc_pesquisa_posto ('', '', document.frm_pesquisa.posto_nome)">
            </td>
        </tr>
        <tr>
            <td style='width:135px'>&nbsp;</td>
            <td style='width:165px'>
                <label for="data_inicial">Data Inicial </label><br>
                <input id="data_inicial" maxlength="10" name="data_inicial" size='12' type="text" class="frm" value="<?=$data_inicial?>">
            </td>
            <td>
                <label for="data_final">Data Final </label><br>
                <input id="data_final" maxlength="10" name="data_final" size='12' type="text" class="frm" value="<?=$data_final?>">
            </td>
        </tr>

        <tr>
            <td style='width:135px'>&nbsp;</td>
            <td>
                <label >Região</label><br>
                <select title='Selecione a Região' style='width:200px;' name='regiao' id='regiao' onchange="montaComboEstado();" >
                    <option></option>
                    <? foreach ($regioes as $sigla=>$regiao_nome) {
                            echo "<option value='$sigla'";
                                    if($sigla == $regiao){
                                        print "selected";
                                    }
                            echo ">$regiao_nome</option>\n";
                        }
                    ?>
                </select>
            </td>
            <td>
                <label >Estado</label><br>
                <select title='Selecione o Estado' style='width:200px;' name='estado' id='estado'>
                    <option></option>
                    <? foreach ($estados as $sigla=>$estado_nome) {// a variavel $estados esta definida em ../helpdesk/mlg_funciones
                            echo "<option value='$sigla'";
                                    if($sigla == $estado){
                                        print "selected";
                                    }
                            echo ">$estado_nome</option>\n";
                        }
                    ?>
                </select>
            </td>

        </tr>
        <tr>
            <td style='width:135px'>&nbsp;</td>
            <td>
                 <label >OS</label><br>
                 <input class="frm" type="text" name="os" value="<?=$os?>" >
            </td>
        </tr>
        <tr>
            <td style='width:135px'>&nbsp;</td>
            <td colspan="2">
                <table>
                    <tr>
                        <td><input type="radio" name="situacao" value="em_aprovacao" <?=($situacao == 'em_aprovacao' || empty($situacao)) ? ' checked ': '' ?>> Em aprovação</td>
                        <td> <input type="radio" name="situacao" value="aprovadas" <?= ($situacao == 'aprovadas')? ' checked ': '' ?>> Aprovada</td>
                        <td> <input type="radio" name="situacao" value="reprovadas" <?= ($situacao == 'reprovadas')? ' checked ': '' ?>> Reprovada</td>
                        <td> <input type="radio" name="situacao" value="trocadas" <?= ($situacao == 'trocadas')? ' checked ': '' ?>> Trocada</td>
                    </tr>
                </table>
            </td>
        </tr>

        <tr style='text-align:center!important; margin: 30px !important;'>
            <td colspan="3"><br />
                <input name="btn_acao" type="hidden" value='t' />
                <!-- <button value="" type='button' onclick="geraRelatorio()">Filtrar</button> -->
                <button value="1" name="gravar" type="submit">Pesquisar</button>
            </td>
        </tr>
    </table>
</form> <br/>
<? if(pg_num_rows($res)>0 AND (isset($_POST['btn_acao'])) AND strlen($msg_erro)==0 ){ ?>

<table class="tabela"  cellspacing="0" cellpadding="2" border="0" align="center">
    <thead>
        <th width="25"><input type='checkbox' name='checkTodos' class='checkTodos' value='sim'></th>
        <th width="25">Todas</th>
        <th>Posto</th>
        <th>Tipo Posto</th>
        <th>OS</th>
        <th>Qtde Peças</th>
        <th>Valor Peças</th>
        <th>Valor Produto</th>
        <th>Peças X Produto</th>
        <th>Ações</th>
    </thead>
    <tbody>
    <?php
        for($i= 0; $i<pg_num_rows($res); $i++){
            $os = pg_fetch_result($res, $i, os);
            $codigo_posto = pg_fetch_result($res, $i, codigo_posto);
            $liberada = pg_fetch_result($res, $i, liberada);
            $reprovada = pg_fetch_result($res, $i, reprovada);
            $justificativa = pg_fetch_result($res, $i, justificativa);
            $nome_posto = pg_fetch_result($res, $i, nome);
            $tipo_posto_descricao = pg_fetch_result($res, $i, tipo_posto_descricao);
            $os = pg_fetch_result($res, $i, os);
            $sua_os = pg_fetch_result($res, $i, sua_os);
            $qtde_pecas = pg_fetch_result($res, $i, qtde_pecas);
            $campos_adicionais = json_decode(pg_fetch_result($res, $i, 'campos_adicionais'), true);

            $valor_pecas = $campos_adicionais['total_custo_peca'];
            $valor_produto = $campos_adicionais['total_produto'];

            $valor_interrogacao = "Total de Peças: R$". $campos_adicionais['valor_pecas']." \nTaxa Administrativa: 10%\nMão de Obra Peças: R$".$campos_adicionais['Mobra'];

            $valor_interrogacao_produto = "Valor do Produto: R$".$campos_adicionais['medioCr']. "\nPorcentagem: ". $campos_adicionais['multiplicador']."%".  " \nMão de Obra: R$". $campos_adicionais['Mobra_produto'];

            echo "<tr>";
                echo "<td><input type='checkbox' name='check_$i' class='check' value='$os'> </td>";
                echo "<td class='plus_$i' data-num='$i' > + </td>";
                echo "<td>$codigo_posto - $nome_posto</td>";
                echo "<td>$tipo_posto_descricao</td>";
                echo "<td><a target='_blank' href='os_press.php?os=$os' target='_blank'>".$codigo_posto.$sua_os."</a></td>";
                echo "<td>$qtde_pecas</td>";
                echo "<td>R$ $valor_pecas <img src='imagens/help.png' title='$valor_interrogacao'></td>";
                echo "<td>R$ $valor_produto <img src='imagens/help.png' title='$valor_interrogacao_produto'></td>";
                echo "<td>". number_format(($valor_pecas *100)/$valor_produto, 2, '.', '') ."% </td>";

                echo "<td id='linha_$os'>";
                if(strlen($liberada) == 0 AND strlen($reprovada)==0){
                    echo "<button type='button' class='aprovar' data-os='$os' >Aprovar</button>";
                    echo "<button type='button' class='recusar' data-os='$os' >Reprovar</button>";
                    echo "<button type='button' class='gerar_troca' data-os='$os' >Gerar Troca</button>";
                }elseif(strlen($liberada)>0 and strlen($justificativa)==0){
                    echo "<font color='green'>Aprovada</font>";
                }
                elseif(strlen($reprovada)>0){
                    echo "<font color='#ff0000'> Reprovada</font> ";
                }elseif(strlen($liberada)>0 and strlen($justificativa)>0){
                    echo "<font color='blue'>Gerado Troca</font>";
                }
                echo "</td>";
            echo "</tr>";

            $sql_peca = "SELECT tbl_os_item.os_item, tbl_os_item.qtde, tbl_peca.peca, tbl_tabela_item.preco as custo_peca, tbl_peca.referencia, tbl_peca.descricao
                        FROM tbl_os_item
                        INNER JOIN tbl_os_produto ON tbl_os_produto.os_produto = tbl_os_item.os_produto
                        INNER JOIN tbl_peca on tbl_peca.peca = tbl_os_item.peca AND tbl_peca.fabrica = $login_fabrica

                        INNER JOIN tbl_tabela_item ON tbl_tabela_item.peca = tbl_peca.peca AND tbl_tabela_item.tabela = 1053

                        WHERE tbl_os_produto.os = $os";
            $res_peca = pg_query($con, $sql_peca);
            for($a=0; $a<pg_num_rows($res_peca); $a++ ){
                $os_item = pg_fetch_result($res_peca, $a, os_item);
                $qtde = pg_fetch_result($res_peca, $a, qtde);
                $referencia = pg_fetch_result($res_peca, $a, referencia);
                $descricao = pg_fetch_result($res_peca, $a, descricao);
                $custo_peca = pg_fetch_result($res_peca, $a, custo_peca);

                $total_custo_item = $custo_peca * $qtde;

                echo "<tr style='display:none' class='linha_$i'>";
                    echo "<td colspan='5'> $referencia - $descricao  </td>";
                    echo "<td>".$qtde."</td>";
                    echo "<td>R$ ".number_format($total_custo_item, 2, '.', '')." </td>";
                    echo "<td colspan='2'></td>";

                    if(strlen($liberada)==0 and strlen($justificativa)==0){
                        echo "<td> <button type='button' class='excluir_item_$os_item' data-os_item='$os_item' data-os='$os' >Excluir</button> </td>";
                    }else{
                         echo "<td> </td>";
                    }
                echo "</tr>";
            }
        }
    ?>
    <tr>
        <td colspan='2'></td>
        <td colspan='8' align="left">
            <select name="acao_tudo" id="acao_tudo">
                <option value=''>Seleciona um ação</option>
                <option value='ap'>Aprovar</option>
                <option value='rp'>Reprovar</option>
            </select>
            <input type="text" size='70' name="motivo" id="motivo" value="">
            <button type='button' id="gravar_checked">Gravar</button>
        </td>

    </tr>
    </tbody>
</table>

<? }

    if(pg_num_rows($res)==0 AND isset($_POST['btn_acao'])){
        echo "Nenhum registro encontrado. ";
} ?>

<? include 'rodape.php'; ?>

