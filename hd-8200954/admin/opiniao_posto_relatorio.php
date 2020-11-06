<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";
$admin_privilegios="gerencia";
include 'autentica_admin.php';

if (isset($_REQUEST['linha'])) {
    $linha = $_REQUEST['linha'];
}

if (isset($_REQUEST['distribuidor_posto'])) {
    $distribuidor_posto = $_REQUEST['distribuidor_posto'];
}

if (isset($_REQUEST['distribuidor'])) {
    $distribuidor = $_REQUEST['distribuidor'];
}

if (isset($_REQUEST['estado'])) {
    $estado = $_REQUEST['estado'];
}

if (isset($_REQUEST['xpesquisa'])) {
    $xpesquisa = $_REQUEST['xpesquisa'];
}

if (isset($_GET["acao"]) && $_GET["acao"] == "excluir") {
    $msg_erro       = array();
    $msg_sucesso    = array();
    $opiniao_posto  = $_GET["opiniao_posto"];
    $listartudo     = $_GET["listartudo"];
    $listardetalhes = $_GET["listardetalhes"];
    $codigo_posto   = $_GET["codigo_posto"];
    $linha          = $_GET["linha"];
    $estado             = $_GET["estado"];
    $distribuidor_posto = $_GET["distribuidor_posto"];

    $sql = "SELECT 
                    tbl_opiniao_posto_resposta.opiniao_posto_resposta
              FROM tbl_opiniao_posto_resposta
              JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_opiniao_posto_resposta.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
              JOIN tbl_opiniao_posto_pergunta ON tbl_opiniao_posto_pergunta.opiniao_posto_pergunta = tbl_opiniao_posto_resposta.opiniao_posto_pergunta
              JOIN tbl_opiniao_posto ON tbl_opiniao_posto.opiniao_posto = tbl_opiniao_posto_pergunta.opiniao_posto
             WHERE tbl_opiniao_posto.fabrica= $login_fabrica
               AND tbl_opiniao_posto.opiniao_posto = $opiniao_posto;";
    $res = pg_query($con,$sql);

    if (pg_num_rows($res) > 0) {
        $rows = pg_fetch_all($res);
        foreach ($rows as $key => $row) {
            $respostas[] = $row["opiniao_posto_resposta"];
        }
        $sql = "DELETE  FROM tbl_opiniao_posto_resposta
                       WHERE opiniao_posto_resposta IN (".implode(",", $respostas).");";
        $res = pg_query($con,$sql);
        if (pg_last_error($con)) {
            $msg_erro["msg"] =  "Erro ao excluir a(s) resposta(s)";
            echo "<meta http-equiv=refresh content=\"2;URL=opiniao_posto_relatorio.php?listartudo=$listartudo&listardetalhes=$listardetalhes&codigo_posto=$codigo_posto&linha=$linha&estado=$estado&distribuidor_posto=$distribuidor_posto\">";
        }
        if (strlen($msg_erro["msg"]) == 0) {
            $msg_sucesso["msg"] =  "Resposta(s) excluida(s) com sucesso";
            echo "<meta http-equiv=refresh content=\"2;URL=opiniao_posto_relatorio.php?listartudo=$listartudo&listardetalhes=$listardetalhes&codigo_posto=$codigo_posto&linha=$linha&estado=$estado&distribuidor_posto=$distribuidor_posto\">";
        }

    } else {
        $msg_erro["msg"] =  "Erro ao excluir a(s) resposta(s)";
        echo "<meta http-equiv=refresh content=\"2;URL=opiniao_posto_relatorio.php?listartudo=$listartudo&listardetalhes=$listardetalhes&codigo_posto=$codigo_posto&linha=$linha&estado=$estado&distribuidor_posto=$distribuidor_posto\">";
    }

}

$xpesquisa = $_REQUEST['xpesquisa'];

$title       = "RELATÓRIO OPINIÃO POSTO";
$cabecalho   = "RELATÓRIO OPINIAO POSTO";
$layout_menu = "gerencia";
include 'cabecalho_new.php';

$plugins = array(
    "multiselect",
    "autocomplete",
    "select2"
);

include("plugin_loader.php");
?>
<!-- <script type='text/javascript' src='js/jquery.js'></script>
 -->

<script type='text/javascript'>
//Rotina para deixar selecionar o selectbox Somente postos via distribuidor.

$(document).ready(function(){
    $( "#sel_distrib" ).attr("disabled","disabled");
    $( "input:radio" ).change( function(){

        if( this.value == 'VIA-DISTRIB' )
        {
            $( "#sel_distrib" ).removeAttr("disabled"); 
        }
        else
        {
            $( "#sel_distrib" ).attr( "disabled","disabled" );
        }
    })
    $(".select2").select2({width : "100%"});

})


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
    font: bold 16px "Arial";
    color:#FFFFFF;
    text-align:center;
    }

    .formulario{
    background-color:#D9E2EF;
    font:11px Arial;
    }

    .subtitulo{

    color: #7092BE
    }

    table.tabela tr td{
    font-family: verdana;
    font-size: 11px;
    border-collapse: collapse;
    border:1px solid #596d9b;
    }

    .espaco{padding-left:220px;}

</style>
<?php 
    if (strlen($msg_erro["msg"]) > 0) {
        echo '<div class="alert alert-danger"><h4>'.$msg_erro["msg"].'</h4></div>';
    }
    if (strlen($msg_sucesso["msg"]) > 0) {
        echo '<div class="alert alert-success"><h4>'.$msg_sucesso["msg"].'</h4></div>';
    }
?>
<form name="frm_opiniao_posto" method="post" action="opiniao_posto_relatorio.php" align='center' class='form-search form-inline tc_formulario'>
    <div class='titulo_tabela '>Parâmetros de Pesquisa</div>
    <br/>
    <div class='row-fluid'>
        <div class='span2'></div>
        <div class='span4'>
            <div class='control-group'>
                <label class='control-label' for='linha'>Linha do Posto</label>
                <div class='controls controls-row'>
                    <div class='span4'>
                        <?php
                            $sql = "SELECT linha, nome
                                      FROM tbl_linha
                                     WHERE tbl_linha.fabrica = $login_fabrica
                                  ORDER BY tbl_linha.nome;";
                            $res = pg_query($con, $sql);

                            if (pg_num_rows($res) > 0) {
                        ?>
                        <select name="linha" id="linha">
                            <option value="">TODAS</option>
                            <?php
                                for ($x = 0 ; $x < pg_num_rows($res) ; $x++){
                                    $aux_linha = trim(pg_fetch_result($res,$x,linha));
                                    $aux_nome  = trim(pg_fetch_result($res,$x,nome));
                                    echo "<option value='$aux_linha'"; if ($linha == $aux_linha) echo " SELECTED "; echo ">$aux_nome</option>";
                                }
                            ?>
                        </select>
                        <?php }?>
                    </div>
                </div>
            </div>
        </div>
        <div class='span4'>
            <div class='control-group'>
                <label class='control-label' for='estado'>Estado do Posto</label>
                <div class='controls controls-row'>
                    <div class='span4'>
                        <?php
                            $sql = "SELECT estado
                                      FROM tbl_estado
                                     WHERE pais = 'BR' 
                                       AND estado <> 'EX'
                                  ORDER BY estado;";
                            $res = pg_query($con, $sql);

                            if (pg_num_rows($res) > 0) {
                        ?>
                        <select name="estado" id="estado">
                            <option value="">TODOS</option>
                            <?php
                                for ($x = 0 ; $x < pg_num_rows($res) ; $x++) {
                                    $aux_estado = trim(pg_fetch_result($res,$x,estado));
                                    $aux_nome  = trim(pg_fetch_result($res,$x,nome));
                                    echo "<option value='$aux_estado'"; if ($estado == $aux_estado) echo " SELECTED "; echo ">$aux_estado</option>";
                                }
                            ?>
                        </select>
                        <?php }?>
                    </div>
                </div>
            </div>
        </div>  
        <div class="span2"></div>
    </div>
    <?php if ($login_fabrica == 151) {?>
    <div class="row-fluid">
        <div class='span2'></div>
        <div class='span8'>
            <div class='control-group'>
                <label class='control-label' for='pesquisa'>Pesquisa</label>
                <div class='controls controls-row'>
                    <div class='span12'>
                        <?php
                            $sql = "SELECT opiniao_posto, cabecalho
                                      FROM tbl_opiniao_posto
                                     WHERE fabrica = $login_fabrica
                                  ORDER BY cabecalho;";
                            $res = pg_query($con,$sql);

                            if (pg_num_rows($res) > 0) {
                        ?>
                        <select name="xpesquisa" class="select2" id="xpesquisa">
                            <option value="TODOS">TODAS</option>
                            <?php
                                for ($x = 0 ; $x < pg_num_rows($res) ; $x++){
                                    $aux_opiniao_posto = trim(pg_fetch_result($res,$x,opiniao_posto));
                                    $aux_cabecalho     = trim(pg_fetch_result($res,$x,cabecalho));
                                    $selected = ($xpesquisa == $aux_opiniao_posto) ? " SELECTED " : "";
                                    echo "<option value='$aux_opiniao_posto' {$selected}>$aux_cabecalho</option>";
                                }
                            ?>
                        </select>
                    <?php }?>
                    </div>
                </div>
            </div>
        </div>
        <div class='span2'></div>
    </div>
    <?php }?>
    <div class='row-fluid'>
        <div class="container">
            <div class='span2'></div>
            <div class='span4'>
                 <label class="radio">
                    <input type="radio" name="distribuidor_posto" value="TODOS" <? if (strlen($distribuidor_posto) == 0 OR $distribuidor_posto == 'TODOS') echo " checked"; ?>>
                    Exibir todos os Postos
                </label>
            </div>
            <div class='span4'>
                <label class="radio">
                    <input type="radio" name="distribuidor_posto" value="DISTRIB" <? if ($distribuidor_posto == 'DISTRIB') echo " checked"; ?>>
                    Exibir somente distribuidores
                </label>
            </div>
            <div class='span2'></div>
        </div>
        <div class="container">
            <div class='span2'></div>
            <div class='span4'>
                 <label class="radio">
                    <input type="radio" name="distribuidor_posto" value="DIRETO" <? if ($distribuidor_posto == 'DIRETO') echo " checked"; ?>>
                    Somente postos com pedidos diretos
                </label>
            </div>
            <div class='span4'>
                <label class="radio">
                    <input type="radio" name="distribuidor_posto" value="VIA-DISTRIB" <? if ($distribuidor_posto == 'VIA-DISTRIB') echo " checked"; ?> >
                    Somente postos via distribuidor
                </label>
            </div>
            <div class='span2'></div>
        </div>
        <?php if ($login_fabrica == 151) {?>
        <div class="container">
            <div class='span2'></div>
            <div class='span4'>
                 <label class="radio">
                    <input type="radio" name="distribuidor_posto" value="QUESTIONARIO-RESPONDIDO" <? if ($distribuidor_posto == 'QUESTIONARIO-RESPONDIDO') echo " checked"; ?>>
                    Exibir todos os Posto com questionário respondido
                </label>
            </div>
            <div class='span4'>
                <label class="radio">
                    <input type="radio" name="distribuidor_posto" value="QUESTIONARIO-PENDENTE" <? if ($distribuidor_posto == 'QUESTIONARIO-PENDENTE') echo " checked"; ?> >
                    Exibir todos os Posto com questionário pendente
                </label>
            </div>
            <div class='span2'></div>
        </div>
        <?php }?>
    </div>

    <?php
    $sql = "SELECT  tbl_posto.posto,
                    tbl_posto.nome 
            FROM    tbl_posto
            JOIN    tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
            JOIN    tbl_tipo_posto USING (tipo_posto)
            WHERE   tbl_tipo_posto.distribuidor IS TRUE
            AND     tbl_posto_fabrica.credenciamento = 'CREDENCIADO'
            ORDER BY tbl_posto.nome;";
    $res = pg_exec ($con,$sql);
    if(pg_numrows($res) > 0){
    ?>
    <div class="row-fluid"> 
        <div class="span2"></div>
        <div class='span4'>
            <div class='control-group'>
                <label class='control-label' for='distribuidor'>Estado do Posto</label>
                <div class='controls controls-row'>
                    <div class='span4'>
                        <select name="distribuidor" id="sel_distrib">
                            <option value="" selected>TODAS</option>
                            <?php
                                for ($x = 0 ; $x < pg_numrows($res) ; $x++){
                                    $aux_posto = trim(pg_result($res,$x,posto));
                                    $aux_nome  = trim(pg_result($res,$x,nome));
                                    echo "<option value='$aux_posto'"; if ($_POST['distribuidor'] == $aux_posto) echo " SELECTED "; echo ">$aux_nome</option>\n";
                                }
                            ?>
                        </select>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php
    }
    ?>
    <p><br />
        <input type='hidden' name='btn_acao' value=''>
        <input type="button" class="btn" onclick="javascript: document.frm_opiniao_posto.btn_acao.value='relatorio' ; document.frm_opiniao_posto.submit() ; " value="Pesquisar">
    </p><br />
</form>

<?php

flush();
if (strlen($linha) == 0) {
    $linha_selecao = " AND 1=1 " ;
} else {
    $linha_selecao = " AND tbl_posto.posto IN (SELECT tbl_posto_linha.posto FROM tbl_posto_linha WHERE tbl_posto_linha.linha = $linha) " ;
}
if (strlen($estado) == 0) {
    $estado_selecao = " AND 1=1 " ;
} else {
    $estado_selecao = " AND tbl_posto.estado = '$estado' ";
}


if ($xpesquisa == 'TODOS' ) {
    $xpesquisa_selecao = " AND 1=1" ;
} elseif (strlen($estado) == 0 && strlen($xpesquisa) > 0) {
    $xpesquisa_selecao = " AND  tbl_opiniao_posto.opiniao_posto = {$xpesquisa}" ;
    $xpesquisa_selecao2 = " AND  tbl_opiniao_posto_pergunta.opiniao_posto = {$xpesquisa}" ;
}

if (in_array($distribuidor_posto, array('TODOS','QUESTIONARIO-RESPONDIDO','QUESTIONARIO-PENDENTE'))) {
    $distribuidor_selecao = " AND 1=1" ;
}

if ($distribuidor_posto == 'DIRETO'){
    $distribuidor_selecao = " AND tbl_posto_fabrica.distribuidor IS NULL " ;
}

if ($distribuidor_posto == 'VIA-DISTRIB'){
    if (strlen ($distribuidor) == 0) {
        $distribuidor_selecao = " AND tbl_posto_fabrica.distribuidor NOTNULL " ;
    }else{
        $distribuidor_selecao = " AND tbl_posto_fabrica.distribuidor = '$distribuidor'";
    }
}

$link_get = "";

if (strlen($distribuidor) == 0){
    $link_get = "linha=$linha&estado=$estado&distribuidor_posto=$distribuidor_posto";
} else if (strlen($distribuidor) > 0){ 
    $link_get = "linha=$linha&estado=$estado&distribuidor_posto=$distribuidor_posto&distribuidor=$distribuidor";
}
if ($xpesquisa > 0) {
    $link_get .= "&xpesquisa=$xpesquisa";

}
/*

$res = pg_exec ($con,"SELECT opiniao_posto FROM tbl_opiniao_posto WHERE fabrica = $login_fabrica AND ativo IS TRUE ");
if (pg_numrows ($res) == 0) {
    echo " <div class='alert alert-block'>
    <h4>Não existem pesquisas ativas.</h4>
    </div><h2></h2></center>";
    exit;
}
*/
$btn_acao = $_POST['btn_acao'];

if($btn_acao == "relatorio") {

    if ($xpesquisa == 'TODOS' ) {
        $xCondOp = " 1=1" ;

        if (strlen($estado) > 0) {
            $xCondOp .= " AND tbl_opiniao_posto.estado = '$estado'" ;
        }

    } else {
        $xCondOp = " tbl_opiniao_posto.opiniao_posto = $xpesquisa" ;

        if (strlen($estado) > 0) {
            $xCondOp .= " AND tbl_opiniao_posto.estado = '$estado'" ;
        }

    }

    $sql = "SELECT DISTINCT tbl_posto.posto, tbl_posto_fabrica.codigo_posto, tbl_posto.nome, tbl_posto.estado
            INTO TEMP TABLE TMP_posto 
            FROM tbl_posto 
            JOIN tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
            JOIN tbl_posto_linha   ON tbl_posto.posto = tbl_posto_linha.posto
            JOIN tbl_linha ON tbl_posto_linha.linha = tbl_linha.linha AND tbl_linha.fabrica = $login_fabrica
            JOIN tbl_tipo_posto    ON tbl_posto_fabrica.tipo_posto = tbl_tipo_posto.tipo_posto 
            WHERE tbl_posto_fabrica.credenciamento = 'CREDENCIADO' ";

    if (strlen ($linha) > 0) $sql .= " AND tbl_posto_linha.linha = $linha " ;
    if ($distribuidor_posto == 'DIRETO') $sql .= " AND tbl_posto_fabrica.distribuidor IS NULL ";
    if ($distribuidor_posto == 'DISTRIB') $sql .= " AND tbl_tipo_posto.distribuidor IS TRUE ";
    if ($distribuidor_posto == 'VIA-DISTRIB') $sql .= " AND tbl_posto_fabrica.distribuidor = $distribuidor ";

    #Se a opção "TODAS" for selecionado no campo "postos via distribuidor" entao retirar o where do select, para trazer todos os postos via distribuidor

    if ($distribuidor_posto == 'VIA-DISTRIB'  && $distribuidor == '' )
    {
        $novaSql = str_replace( " AND tbl_posto_fabrica.distribuidor = $distribuiror " , '' , $sql );
        $sql = $novaSql;
    }

    if (strlen ($estado) > 0) $sql .= " AND tbl_posto.estado = '$estado' ";
    $res = pg_query($con,$sql);
    $sql = "SELECT COUNT(tbl_posto_fabrica.posto) AS qtde 
              FROM tbl_posto_fabrica 
             WHERE tbl_posto_fabrica.fabrica = $login_fabrica 
               AND tbl_posto_fabrica.credenciamento in ('CREDENCIADO','EM DESCREDENCIAMENTO')";
    $res = pg_query($con,$sql);
    $qtde_posto = pg_result ($res,0,0);

    $sql = "SELECT count(tbl_posto_fabrica.posto) 
              FROM tbl_posto_fabrica 
              JOIN tbl_posto ON tbl_posto_fabrica.posto = tbl_posto.posto 
              JOIN (
                    SELECT DISTINCT posto 
                      FROM tbl_opiniao_posto_resposta 
                      JOIN tbl_opiniao_posto_pergunta USING (opiniao_posto_pergunta) 
                      JOIN tbl_opiniao_posto ON tbl_opiniao_posto_pergunta.opiniao_posto=tbl_opiniao_posto.opiniao_posto AND tbl_opiniao_posto.fabrica = {$login_fabrica} 
                     WHERE {$xCondOp}
                ) resp ON resp.posto = tbl_posto_fabrica.posto
             WHERE tbl_posto_fabrica.fabrica = $login_fabrica 
               AND tbl_posto_fabrica.credenciamento = 'CREDENCIADO';";
    $res4 = pg_query($con,$sql);
    $qtde_resposta = pg_result($res4,0,0);

    $sql = "SELECT  tbl_posto_fabrica.posto       ,
                    tbl_posto_fabrica.codigo_posto,
                    tbl_posto.nome                ,
                    tbl_posto.fone                ,
                    resp.posto AS resp_posto      
            FROM tbl_posto_fabrica 
            JOIN tbl_posto ON tbl_posto_fabrica.posto = tbl_posto.posto 
            LEFT JOIN (
                SELECT DISTINCT posto FROM tbl_opiniao_posto_resposta 
                JOIN tbl_opiniao_posto_pergunta USING (opiniao_posto_pergunta) 
                JOIN tbl_opiniao_posto USING (opiniao_posto) 
                WHERE {$xCondOp}
            ) resp ON resp.posto = tbl_posto_fabrica.posto
            WHERE tbl_posto_fabrica.fabrica = $login_fabrica 
            AND   tbl_posto_fabrica.credenciamento = 'CREDENCIADO'
            AND resp.posto is null
            $linha_selecao
            $estado_selecao";
            if( $distribuidor_selecao != NULL )
            {
                $sql .= " $distribuidor_selecao ";
            }
            
            $sql .= "ORDER BY tbl_posto.nome";
    
    $res3 = pg_query($con,$sql);

    $qtde_sem_resposta = pg_num_rows($res3);

    $sql = "SELECT  tbl_posto_fabrica.posto       ,
                    tbl_posto_fabrica.codigo_posto,
                    tbl_posto.nome                ,
                    tbl_posto.fone                ,
                    resp.posto AS resp_posto      
            FROM tbl_posto_fabrica 
            JOIN tbl_posto ON tbl_posto_fabrica.posto = tbl_posto.posto 
            LEFT JOIN (
                SELECT DISTINCT posto FROM tbl_opiniao_posto_resposta 
                JOIN tbl_opiniao_posto_pergunta USING (opiniao_posto_pergunta) 
                JOIN tbl_opiniao_posto USING (opiniao_posto) 
                WHERE {$xCondOp}
            ) resp ON resp.posto = tbl_posto_fabrica.posto
            WHERE tbl_posto_fabrica.fabrica = $login_fabrica 
            AND   tbl_posto_fabrica.credenciamento = 'CREDENCIADO'
            $linha_selecao
            $estado_selecao 
            ";
            if( $distribuidor_selecao != NULL )
            {
                $sql .= " $distribuidor_selecao ";
            }
            
            $sql .= "  ORDER BY tbl_posto.nome";
    $res6 = pg_query($con,$sql);

    $qtde_postos = pg_num_rows($res6);

    if ( $qtde_postos > 0 ) {

        echo "<div class='row-fluid'>
                        <div class='span12'>
                            <p class='tac'>
                                <input type='button' class='btn' style='cursor: pointer;' onclick=\"javascript: window.open('opiniao_posto_relatorio_print.php?linha=$linha&distribuidor_posto=$distribuidor_posto&distribuidor=$distribuidor&estado=$estado','print','resizable=1,toolbar=1,scrollbars=1,width=640,height=480,top=0,left=0')\" value='Imprimir' />
                            </p>
                        </div>
                    </div>";

        echo "<table class='table table-striped table-bordered table-hover table-fixed'>";
        echo "<thead>";
        echo "<tr>";
        echo "<th colspan='3' class='titulo_tabela'>";
        echo "QUANTIDADE DE POSTOS CREDENCIADOS: " .$qtde_posto;
        echo "</th>";
        echo "</tr>";
        echo "</thead>";
        echo "<tbody>";

        if ($login_fabrica == 151 && $distribuidor_posto == 'QUESTIONARIO-RESPONDIDO') {
            echo "<tr>";
            echo "<td class='pesquisa'>POSTOS QUE RESPONDERAM</td>";
            echo "<td CLASS='menu_top tac'>$qtde_resposta</td>";
            echo "<td CLASS='menu_top tac'><a href=$PHP_SELF?listartudo=2&$link_get>Clique Aqui para Consultar</a></td>";
            echo "</tr>";
        } elseif ($login_fabrica == 151 && $distribuidor_posto == 'QUESTIONARIO-PENDENTE') {
            echo "<tr>";
            echo "<td CLASS='pesquisa'>POSTOS QUE NÃO RESPONDERAM</td>";
            echo "<td CLASS='menu_top tac'>$qtde_sem_resposta </td>";
            echo "<td CLASS='menu_top tac'><a href=$PHP_SELF?listartudo=3&$link_get>Clique Aqui para Consultar</a></td>";
            echo "</tr>";
        } else {
            echo "<tr>";
            echo "<td class='pesquisa'>POSTOS QUE RESPONDERAM</td>";
            echo "<td CLASS='menu_top tac'>$qtde_resposta</td>";
            echo "<td CLASS='menu_top tac'><a href=$PHP_SELF?listartudo=2&$link_get>Clique Aqui para Consultar</a></td>";
            echo "</tr>";
            echo "<tr>";
            echo "<td CLASS='pesquisa'>POSTOS QUE NÃO RESPONDERAM</td>";
            echo "<td CLASS='menu_top tac'>$qtde_sem_resposta </td>";
            echo "<td CLASS='menu_top tac'><a href=$PHP_SELF?listartudo=3&$link_get>Clique Aqui para Consultar</a></td>";
            echo "</tr>";
        }

        echo "</tbody>";
        echo "</table>";

        $sql = "SELECT  tbl_opiniao_posto_pergunta.pergunta              ,
                        tbl_opiniao_posto_pergunta.tipo_resposta         ,
                        tbl_opiniao_posto_pergunta.opiniao_posto_pergunta
                FROM    tbl_opiniao_posto_pergunta
                JOIN tbl_opiniao_posto ON tbl_opiniao_posto.opiniao_posto = tbl_opiniao_posto_pergunta.opiniao_posto 
                WHERE tbl_opiniao_posto.fabrica = $login_fabrica  
                $xpesquisa_selecao
                $cond_pesquisa
                ORDER BY tbl_opiniao_posto_pergunta.tipo_resposta        , 
                        tbl_opiniao_posto_pergunta.ordem;";
        $res = pg_query($con,$sql);
        //echo "<table width='700px' align='center' class='tabela'>\n";
    //      echo "<table class='table table-striped table-bordered table-hover table-fixed'>";

        $conteudo = "";
        if(pg_num_rows($res) > 0){
            for ($i=0; $i < pg_num_rows($res); $i++){

                $opiniao_posto_pergunta = pg_result($res,$i,opiniao_posto_pergunta);
                $pergunta               = pg_result($res,$i,pergunta);
                $tipo_resposta          = pg_result($res,$i,tipo_resposta);
                if ($tipo_resposta == 'T') {continue;}

                $conteudo .= "<table class='table table-striped table-bordered table-hover table-fixed'>";
                //$cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";
                
                $sql = "SELECT
                        (
                            SELECT  count(tbl_opiniao_posto_resposta.resposta) AS ocorrencia_muito_satisfeito
                            FROM    tbl_opiniao_posto_resposta
                            JOIN    tbl_opiniao_posto_pergunta USING (opiniao_posto_pergunta)
                            JOIN    tbl_posto ON tbl_opiniao_posto_resposta.posto = tbl_posto.posto
                            JOIN    tbl_posto_fabrica ON tbl_posto_fabrica.posto  = tbl_posto.posto
                            WHERE   tbl_opiniao_posto_resposta.opiniao_posto_pergunta = $opiniao_posto_pergunta
                            AND     tbl_opiniao_posto_resposta.resposta               = 'muito satisfeito'
                            AND     tbl_opiniao_posto_pergunta.tipo_resposta in ('F')
                            AND     tbl_posto_fabrica.fabrica = $login_fabrica
                            $linha_selecao
                            $estado_selecao
                            $distribuidor_selecao
                            $xpesquisa_selecao2
                        ) AS muito_satisfeito,
                        ( 
                            SELECT  count(tbl_opiniao_posto_resposta.resposta) AS ocorrencia_satisfeito
                            FROM    tbl_opiniao_posto_resposta
                            JOIN    tbl_opiniao_posto_pergunta USING (opiniao_posto_pergunta)
                            JOIN    tbl_posto ON tbl_opiniao_posto_resposta.posto = tbl_posto.posto
                            JOIN    tbl_posto_fabrica ON tbl_posto_fabrica.posto  = tbl_posto.posto
                            WHERE   tbl_opiniao_posto_resposta.opiniao_posto_pergunta = $opiniao_posto_pergunta
                            AND     tbl_opiniao_posto_resposta.resposta               = 'satisfeito'
                            AND     tbl_opiniao_posto_pergunta.tipo_resposta in ('F')
                            AND     tbl_posto_fabrica.fabrica = $login_fabrica
                            $linha_selecao
                            $estado_selecao
                            $distribuidor_selecao
                            $xpesquisa_selecao2
                        ) AS satisfeito,
                        (
                            SELECT  count(tbl_opiniao_posto_resposta.resposta) AS ocorrencia_nem_nem
                            FROM    tbl_opiniao_posto_resposta
                            JOIN    tbl_opiniao_posto_pergunta USING (opiniao_posto_pergunta)
                            JOIN    tbl_posto ON tbl_opiniao_posto_resposta.posto = tbl_posto.posto
                            JOIN    tbl_posto_fabrica ON tbl_posto_fabrica.posto  = tbl_posto.posto
                            WHERE   tbl_opiniao_posto_resposta.opiniao_posto_pergunta = $opiniao_posto_pergunta
                            AND     tbl_opiniao_posto_resposta.resposta               = 'nem satisfeito nem insatisfeito'
                            AND     tbl_opiniao_posto_pergunta.tipo_resposta in ('F')
                            AND     tbl_posto_fabrica.fabrica = $login_fabrica
                            $linha_selecao
                            $estado_selecao
                            $distribuidor_selecao
                            $xpesquisa_selecao2
                        ) AS nem_satisfeito_nem_insatisfeito,
                        ( 
                            SELECT  count(tbl_opiniao_posto_resposta.resposta) AS ocorrencia_muito_insatisfeito
                            FROM    tbl_opiniao_posto_resposta
                            JOIN    tbl_opiniao_posto_pergunta USING (opiniao_posto_pergunta)
                            JOIN    tbl_posto ON tbl_opiniao_posto_resposta.posto = tbl_posto.posto
                            JOIN    tbl_posto_fabrica ON tbl_posto_fabrica.posto  = tbl_posto.posto
                            WHERE   tbl_opiniao_posto_resposta.opiniao_posto_pergunta = $opiniao_posto_pergunta
                            AND     tbl_opiniao_posto_resposta.resposta               = 'muito insatisfeito'
                            AND     tbl_opiniao_posto_pergunta.tipo_resposta in ('F')
                            AND     tbl_posto_fabrica.fabrica = $login_fabrica
                            $linha_selecao
                            $estado_selecao
                            $distribuidor_selecao
                            $xpesquisa_selecao2
                        ) AS muito_insatisfeito,
                        ( 
                            SELECT  count(tbl_opiniao_posto_resposta.resposta) AS ocorrencia_insatisfeito
                            FROM    tbl_opiniao_posto_resposta
                            JOIN    tbl_opiniao_posto_pergunta USING (opiniao_posto_pergunta)
                            JOIN    tbl_posto ON tbl_opiniao_posto_resposta.posto = tbl_posto.posto
                            JOIN    tbl_posto_fabrica ON tbl_posto_fabrica.posto  = tbl_posto.posto
                            WHERE   tbl_opiniao_posto_resposta.opiniao_posto_pergunta = $opiniao_posto_pergunta
                            AND     tbl_opiniao_posto_resposta.resposta               = 'insatisfeito'
                            AND     tbl_opiniao_posto_pergunta.tipo_resposta in ('F')
                            AND     tbl_posto_fabrica.fabrica = $login_fabrica
                            $linha_selecao
                            $estado_selecao
                            $distribuidor_selecao
                            $xpesquisa_selecao2
                        ) AS insatisfeito,
                        ( 
                            SELECT  count(tbl_opiniao_posto_resposta.resposta) AS ocorrencia_sim
                            FROM    tbl_opiniao_posto_resposta
                            JOIN    tbl_opiniao_posto_pergunta USING (opiniao_posto_pergunta)
                            JOIN    tbl_posto ON tbl_opiniao_posto_resposta.posto = tbl_posto.posto
                            JOIN    tbl_posto_fabrica ON tbl_posto_fabrica.posto  = tbl_posto.posto
                            WHERE   tbl_opiniao_posto_resposta.opiniao_posto_pergunta = $opiniao_posto_pergunta
                            AND     tbl_opiniao_posto_resposta.resposta               = 't'
                            AND     tbl_opiniao_posto_pergunta.tipo_resposta in ('S')
                            AND     tbl_posto_fabrica.fabrica = $login_fabrica
                            $linha_selecao
                            $estado_selecao
                            $distribuidor_selecao
                            $xpesquisa_selecao2
                        ) AS sim,
                        ( 
                            SELECT  count(tbl_opiniao_posto_resposta.resposta) AS ocorrencia_nao
                            FROM    tbl_opiniao_posto_resposta
                            JOIN    tbl_opiniao_posto_pergunta USING (opiniao_posto_pergunta)
                            JOIN    tbl_posto ON tbl_opiniao_posto_resposta.posto = tbl_posto.posto
                            JOIN    tbl_posto_fabrica ON tbl_posto_fabrica.posto  = tbl_posto.posto
                            WHERE   tbl_opiniao_posto_resposta.opiniao_posto_pergunta = $opiniao_posto_pergunta
                            AND     tbl_opiniao_posto_resposta.resposta               = 'f'
                            AND     tbl_opiniao_posto_pergunta.tipo_resposta in ('S')
                            AND     tbl_posto_fabrica.fabrica = $login_fabrica
                            $linha_selecao
                            $estado_selecao
                            $distribuidor_selecao
                            $xpesquisa_selecao2
                        ) AS nao,
                        ( 
                            SELECT  count(tbl_opiniao_posto_resposta.resposta) AS ocorrencia_muito_progresso
                            FROM    tbl_opiniao_posto_resposta
                            JOIN    tbl_opiniao_posto_pergunta USING (opiniao_posto_pergunta)
                            JOIN    tbl_posto ON tbl_opiniao_posto_resposta.posto = tbl_posto.posto
                            JOIN    tbl_posto_fabrica ON tbl_posto_fabrica.posto  = tbl_posto.posto
                            WHERE   tbl_opiniao_posto_resposta.opiniao_posto_pergunta = $opiniao_posto_pergunta
                            AND     tbl_opiniao_posto_resposta.resposta               = 'muito progresso'
                            AND     tbl_opiniao_posto_pergunta.tipo_resposta in ('P')
                            AND     tbl_posto_fabrica.fabrica = $login_fabrica
                            $linha_selecao
                            $estado_selecao
                            $distribuidor_selecao
                            $xpesquisa_selecao2
                        ) AS muito_progresso,
                        ( 
                            SELECT  count(tbl_opiniao_posto_resposta.resposta) AS ocorrencia_melhorou
                            FROM    tbl_opiniao_posto_resposta
                            JOIN    tbl_opiniao_posto_pergunta USING (opiniao_posto_pergunta)
                            JOIN    tbl_posto ON tbl_opiniao_posto_resposta.posto = tbl_posto.posto
                            JOIN    tbl_posto_fabrica ON tbl_posto_fabrica.posto  = tbl_posto.posto
                            WHERE   tbl_opiniao_posto_resposta.opiniao_posto_pergunta = $opiniao_posto_pergunta
                            AND     tbl_opiniao_posto_resposta.resposta               = 'melhorou'
                            AND     tbl_opiniao_posto_pergunta.tipo_resposta in ('P')
                            AND     tbl_posto_fabrica.fabrica = $login_fabrica
                            $linha_selecao
                            $estado_selecao
                            $distribuidor_selecao
                            $xpesquisa_selecao2
                        ) AS melhorou,
                        ( 
                            SELECT  count(tbl_opiniao_posto_resposta.resposta) AS ocorrencia_permaneceu_igual
                            FROM    tbl_opiniao_posto_resposta
                            JOIN    tbl_opiniao_posto_pergunta USING (opiniao_posto_pergunta)
                            JOIN    tbl_posto ON tbl_opiniao_posto_resposta.posto = tbl_posto.posto
                            JOIN    tbl_posto_fabrica ON tbl_posto_fabrica.posto  = tbl_posto.posto
                            WHERE   tbl_opiniao_posto_resposta.opiniao_posto_pergunta = $opiniao_posto_pergunta
                            AND     tbl_opiniao_posto_resposta.resposta               = 'permaneceu igual'
                            AND     tbl_opiniao_posto_pergunta.tipo_resposta in ('P')
                            AND     tbl_posto_fabrica.fabrica = $login_fabrica
                            $linha_selecao
                            $estado_selecao
                            $distribuidor_selecao
                            $xpesquisa_selecao2
                        ) AS permaneceu_igual,
                        ( 
                            SELECT  count(tbl_opiniao_posto_resposta.resposta) AS ocorrencia_piorou
                            FROM    tbl_opiniao_posto_resposta
                            JOIN    tbl_opiniao_posto_pergunta USING (opiniao_posto_pergunta)
                            JOIN    tbl_posto ON tbl_opiniao_posto_resposta.posto = tbl_posto.posto
                            JOIN    tbl_posto_fabrica ON tbl_posto_fabrica.posto  = tbl_posto.posto
                            WHERE   tbl_opiniao_posto_resposta.opiniao_posto_pergunta = $opiniao_posto_pergunta
                            AND     tbl_opiniao_posto_resposta.resposta               = 'piorou'
                            AND     tbl_opiniao_posto_pergunta.tipo_resposta in ('P')
                            AND     tbl_posto_fabrica.fabrica = $login_fabrica
                            $linha_selecao
                            $estado_selecao
                            $distribuidor_selecao
                            $xpesquisa_selecao2
                        ) AS piorou;";
                $res2 = pg_query($con,$sql);//echo "&middot; ".nl2br($sql)." <br><br>"; exit;
            if($ip == '192.168.0.66'){ echo "&middot; ".nl2br($sql)." <br><br>"; exit; }
            

            if ($tipo_resposta == 'S'){
                $conteudo .= "<thead><tr><th class='titulo_tabela' colspan='2'>$pergunta</th></tr>";
            }else   if ($tipo_resposta == 'F'){
                $conteudo .= "<thead><tr><th class='titulo_tabela' colspan='5'>$pergunta</th></tr>";
            }else   if ($tipo_resposta == 'P'){
                $conteudo .= "<thead><tr><th class='titulo_tabela' colspan='4'>$pergunta</th></tr>";
            }
            $stotal   = 0;
            $perc_sim = 0;
            $perc_nao = 0; 
            if ($tipo_resposta == 'S'){
                $sim = pg_result ($res2,0, sim);
                $nao = pg_result ($res2, 0, nao);
                $stotal = $stotal + $sim + $nao;
                if ($stotal > 0) $perc_sim  = ($sim/$stotal)*100;
                $perc_sim  = number_format ($perc_sim,0);
                if ($stotal > 0) $perc_nao  = ($nao/$stotal)*100;
                $perc_nao  = number_format ($perc_nao,0);
                $conteudo .= "<tr class='titulo_coluna'>\n";
                $conteudo .= "<th>SIM</th>\n";
                $conteudo .= "<th>NÃO</th>\n";
                $conteudo .= "</tr>\n";
                $conteudo .= "</thead>";
                $conteudo .= "<tbody>";
                $conteudo .= "<tr>\n";
                $conteudo .= "<td class='menu_top tac'><a href='$PHP_SELF?listartudo=4&pergunta=$opiniao_posto_pergunta&resposta=t' ";
                if ($sim > $nao) $conteudo .= "style=color:#CC0000";
                $conteudo .= ">$sim ($perc_sim %)</a></td>\n";
                $conteudo .= "<td class='menu_top tac'><a href='$PHP_SELF?listartudo=4&pergunta=$opiniao_posto_pergunta&resposta=f' ";
                if ($nao > $sim) $conteudo .= "style=color:#CC0000";
                $conteudo .= ">$nao ($perc_nao %)</a></td>\n";
                $conteudo .= "</tr>\n";
                $conteudo .= "</tbody>";
                //echo "<tr><td colspan='2' >&nbsp;</td></tr>\n";
            }

            $total = 0;
            $perc_muito_satisfeito = 0;
            $perc_satisfeito = 0;
            $perc_nem_satisfeito_nem_insatisfeito = 0;
            $perc_insatisfeito = 0;
            $perc_muito_insatisfeito = 0;
            if($tipo_resposta == 'F'){
                $campo[0]    = pg_result ($res2,0, muito_satisfeito);
                $campo[1]    = pg_result ($res2,0, satisfeito);
                $campo[2]    = pg_result ($res2,0, nem_satisfeito_nem_insatisfeito);
                $campo[3]    = pg_result ($res2,0, insatisfeito);
                $campo[4]    = pg_result ($res2,0, muito_insatisfeito);
                $total = $total + $campo[0] + $campo[1] + $campo[2] + $campo[3] + $campo[4];
                if ($total > 0) $perc_muito_satisfeito= ($campo[0]/$total)*100;
                $perc_muito_satisfeito                = number_format ($perc_muito_satisfeito,0);
                
                if ($total > 0) $perc_satisfeito      = ($campo[1]/$total)*100;
                $perc_satisfeito                      = number_format ($perc_satisfeito,0);
                
                if ($total > 0) $perc_nem_satisfeito_nem_insatisfeito = ($campo[2]/$total)*100;
                $perc_nem_satisfeito_nem_insatisfeito = number_format ($perc_nem_satisfeito_nem_insatisfeito,0);
                
                if ($total > 0) $perc_insatisfeito                    = ($campo[3]/$total)*100;
                $perc_insatisfeito                    = number_format ($perc_insatisfeito,0);
                
                if ($total > 0) $perc_muito_insatisfeito              = ($campo[4]/$total)*100;
                $perc_muito_insatisfeito              = number_format ($perc_muito_insatisfeito,0);
                
                for($j=0; $j<4; $j++){
                    $posMaior[$j] = 0;
                }

                $respMaior = 0;

                for ($r=0; $r<4; $r++){
                    if ($respMaior < $campo[$r]){
                        $respMaior = $campo[$r];
                        $posMaior[$r] = 1;
                        for($j=0; $j<$r; $j++){
                            $posMaior[$j] = 0;
                        }
                        
                    }
                }

                $conteudo .= "<tr class='titulo_coluna'>\n";
                $conteudo .= "<th class='pesquisa'>Muito Satisfeito</th>\n";
                $conteudo .= "<th class='pesquisa'>Satisfeito</th>\n";
                $conteudo .= "<th class='pesquisa'>Nem Satisfeito/ Nem Insatisfeito</th>\n";
                $conteudo .= "<th class='pesquisa'>Insatisfeito</th>\n";
                $conteudo .= "<th class='pesquisa'>Muito Insatisfeito</th>\n";
                $conteudo .= "</tr>\n";
                $conteudo .= "</thead>";
                $conteudo .= "<tbody>";
                $conteudo .= "<tr  bgcolor='$cor'>\n";
            
                $conteudo .= "<td class='tac'><a href='$PHP_SELF?listartudo=4&pergunta=$opiniao_posto_pergunta&resposta=muito satisfeito' ";
                if ($posMaior[0] == 1) ;
                $conteudo .= ">$campo[0] ($perc_muito_satisfeito %)</a></td>\n";

                $conteudo .= "<td class='menu_top tac'><a href='$PHP_SELF?listartudo=4&pergunta=$opiniao_posto_pergunta&resposta=satisfeito' ";
                if ($posMaior[1] == 1) ;
                $conteudo .= ">$campo[1]  ($perc_satisfeito %)</a></td>\n";

                $conteudo .= "<td class='menu_top tac'><a href='$PHP_SELF?listartudo=4&pergunta=$opiniao_posto_pergunta&resposta=nem satisfeito nem insatisfeito'";
                if ($posMaior[2] == 1) ;
                $conteudo .= ">$campo[2]  ($perc_nem_satisfeito_nem_insatisfeito %)</a></td>\n";

                $conteudo .= "<td class='menu_top tac'><a href='$PHP_SELF?listartudo=4&pergunta=$opiniao_posto_pergunta&resposta=insatisfeito'";
                if ($posMaior[3] == 1) ;
                $conteudo .= ">$campo[3]  ($perc_insatisfeito %)</a></td>\n";

                $conteudo .= "<td class='menu_top tac'><a href='$PHP_SELF?listartudo=4&pergunta=$opiniao_posto_pergunta&resposta=muito insatisfeito'";
                if ($posMaior[4] == 1) ;
                $conteudo .= ">$campo[4]  ($perc_muito_insatisfeito %)</a></td>\n";
                
                $conteudo .= "</tr>\n";
                $conteudo .= "</tbody>";
            }

            $total = 0;
            $perc_muito_progresso = 0;
            $perc_melhorou = 0;
            $perc_permaneceu_igual = 0;
            $perc_piorou = 0;
            if ($tipo_resposta == 'P'){
            
                $campo[5]               = pg_result ($res2,0, muito_progresso);
                $campo[6]               = pg_result ($res2,0, melhorou);
                $campo[7]               = pg_result ($res2,0, permaneceu_igual);
                $campo[8]               = pg_result ($res2,0, piorou);

                $total = $total + $campo[5] + $campo[6] + $campo[7] + $campo[8];

                $perc_muito_progresso   = ($campo[5]/$total)*100;
                $perc_muito_progresso   = number_format ($perc_muito_progresso,0);
                $perc_melhorou          = ($campo[6]/$total)*100;
                $perc_melhorou          = number_format ($perc_melhorou,0);
                $perc_permaneceu_igual  = ($campo[7]/$total)*100;
                $perc_permaneceu_igual  = number_format ($perc_permaneceu_igual,0);
                $perc_piorou            = ($campo[8]/$total)*100;
                $perc_piorou            = number_format ($perc_piorou,0);

                if ($perc_muito_progresso <= 0 || $perc_muito_progresso == "NAN") {
                    $perc_muito_progresso = 0;
                }
                if ($perc_permaneceu_igual <= 0 || $perc_permaneceu_igual == "NAN") {
                    $perc_permaneceu_igual = 0;
                }
                if ($perc_melhorou <= 0 || $perc_melhorou == "NAN") {
                    $perc_melhorou = 0;
                }
                if ($perc_piorou <= 0 || $perc_piorou == "NAN") {
                    $perc_piorou = 0;
                }

                for($j=5; $j<8; $j++){
                    $posMaior[$j] = 0;
                }

                $respMaior = 0;

                for ($r=5; $r<8; $r++){
                    if ($respMaior < $campo[$r]){
                        $respMaior = $campo[$r];
                        $posMaior[$r] = 1;
                        for($j=5; $j<$r; $j++){
                            $posMaior[$j] = 0;
                        }
                        
                    }
                }

                $conteudo .= "<tr>\n";
                $conteudo .= "<th class='titulo_coluna'>MUITO PROGRESSO</th>\n";
                $conteudo .= "<th class='titulo_coluna'>MELHOROU</th>\n";
                $conteudo .= "<th class='titulo_coluna'>PERMANECEU IGUAL</th>\n";
                $conteudo .= "<th class='titulo_coluna'>PIOROU</th>\n";
                $conteudo .= "</tr>\n";
                $conteudo .= "</thead>\n";
                $conteudo .= "<tbody>\n";
                $conteudo .= "<tr bgcolor='$cor'>\n";
                $conteudo .= "<td class='menu_top tac'><a href='$PHP_SELF?listartudo=4&pergunta=$opiniao_posto_pergunta&resposta=muito progresso'";
                if ($posMaior[5] == 1) $conteudo .= "style=color:#CC0000";
                $conteudo .= ">$campo[5] ($perc_muito_progresso %)</a></td>\n";

                $conteudo .= "<td class='menu_top tac'><a href='$PHP_SELF?listartudo=4&pergunta=$opiniao_posto_pergunta&resposta=melhorou'";
                if ($posMaior[6] == 1) $conteudo .= "style=color:#CC0000";
                $conteudo .= ">$campo[6] ($perc_melhorou %)</a></td>\n";

                $conteudo .= "<td class='menu_top tac'><a href='$PHP_SELF?listartudo=4&pergunta=$opiniao_posto_pergunta&resposta=permaneceu igual'";
                if ($posMaior[7] == 1) $conteudo .= "style=color:#CC0000";
                $conteudo .= ">$campo[7] ($perc_permaneceu_igual %)</a></td>\n";

                $conteudo .= "<td class='menu_top tac'><a href='$PHP_SELF?listartudo=4&pergunta=$opiniao_posto_pergunta&resposta=piorou'";
                if ($posMaior[8] == 1) $conteudo .= "style=color:#CC0000";
                $conteudo .= ">$campo[8] ($perc_piorou %)</a></td>\n";

                $conteudo .= "</tr>\n";
                $conteudo .= "</tbody>\n";
                //echo "<tr><td>&nbsp;</td></tr>\n";
            }
        $conteudo .= "</table><br />";
        }//fim for

    }//if
    
    echo $conteudo;

    $xls  = "relatorio-opiniao-posto-{$login_fabrica}-{$login_admin}-".date("YmdHis").".xls";
    $fileXLS = fopen('/tmp/'.$xls, "w");
    fwrite($fileXLS, $conteudo);
    fclose($fileXLS);
    system("mv /tmp/{$xls} xls/{$xls}"); 
    echo "<br />
    <p class='tac'>
        <button type='button' class='btn btn-success download-xls' data-xls='".$xls."'>
            <i class='icon-download-alt icon-white'></i> Download XLS</button>
    </p>";


    }else{
        echo "<center>Nenhum Resultado Encontrado</center>";
    }
}

$listartudo = $_GET['listartudo'];
if($listartudo == 2){

    if ($xpesquisa == 'TODOS'|| strlen($xpesquisa) == 0) {
        $xCondOp .= " 1=1" ;

        if (strlen($estado) > 0) {
            $xCondOp .= " AND tbl_opiniao_posto.estado = '$estado'" ;
        }

    } else {
        $xCondOp .= " tbl_opiniao_posto.opiniao_posto = $xpesquisa" ;
        if (strlen($estado) > 0) {
            $xCondOp .= " AND tbl_opiniao_posto.estado = '$estado'" ;
        }
    }

    $sql = "SELECT tbl_posto_fabrica.posto, 
                   tbl_posto_fabrica.codigo_posto, 
                   tbl_posto.nome, 
                   tbl_posto.email
        FROM tbl_posto_fabrica 
        JOIN tbl_posto ON tbl_posto_fabrica.posto = tbl_posto.posto 
        JOIN (
            SELECT tbl_opiniao_posto_resposta.posto
            FROM tbl_opiniao_posto_resposta 
            JOIN tbl_opiniao_posto_pergunta USING (opiniao_posto_pergunta) 
            JOIN tbl_opiniao_posto ON tbl_opiniao_posto_pergunta.opiniao_posto=tbl_opiniao_posto.opiniao_posto AND tbl_opiniao_posto.fabrica = {$login_fabrica} 
            WHERE {$xCondOp} group by tbl_opiniao_posto_resposta.posto
        ) resp ON resp.posto = tbl_posto_fabrica.posto
        WHERE tbl_posto_fabrica.fabrica = $login_fabrica 
        AND   tbl_posto_fabrica.credenciamento = 'CREDENCIADO'
        $linha_selecao
        $estado_selecao
        $distribuidor_selecao
        ORDER BY tbl_posto.nome;";
    $res4 = pg_query($con,$sql);
    $qtde_resposta = pg_num_rows($res4);

    echo "<table align='center' class='table table-striped table-bordered table-hover table-fixed'>";
    echo "<thead>";
    $colspan = 3;
    if ($login_fabrica == 151) {
        $colspan = 5;
    }
    echo "<tr class='titulo_tabela'>
    <th colspan='$colspan'>RELAÇÃO DOS POSTOS QUE RESPONDERAM</th>
    </tr>";
    echo "</thead>";
    echo "<tbody>";
    for ($i = 0 ; $i < pg_num_rows($res4) ; $i++) {

        if( $i %2 == 0 )
        {
            $cor = '#F7F5F0';
        }
        else
        {
            $cor = '#F1F4FA';
        }
        $codigo_posto  = pg_result ($res4,$i,codigo_posto);
        $nome_posto    = pg_result ($res4,$i,nome);
        $email_posto   = pg_result ($res4,$i,email);
        
        echo "<tr class='menu_top' bgcolor='$cor'>";
        echo "<td align='left'>&nbsp; $codigo_posto</td>";
        echo "<td align='left'><a href='$PHP_SELF?listartudo=2&listardetalhes=4&codigo_posto=$codigo_posto&$link_get'>&nbsp; $nome_posto</a></td>";
        echo "<td align='left'>&nbsp; <a href='mailto:$email_posto'>$email_posto</a></td>";
        echo "</tr>";
    }
    echo "<tbody>";
    echo "</table>";
}

if($listartudo == 3){
    if ($xpesquisa == 'TODOS'|| strlen($xpesquisa) == 0) {
        $xCondOp .= " 1=1" ;

        if (strlen($estado) > 0) {
            $xCondOp .= " AND tbl_opiniao_posto.estado = '$estado'" ;
        }

    } else {
        $xCondOp .= " tbl_opiniao_posto.opiniao_posto = $xpesquisa" ;
        if (strlen($estado) > 0) {
            $xCondOp .= " AND tbl_opiniao_posto.estado = '$estado'" ;
        }
    }
    $sql = "SELECT  tbl_posto_fabrica.posto        ,
                    tbl_posto_fabrica.codigo_posto ,
                    tbl_posto.nome                 ,
                    tbl_posto.fone                 ,
                    resp.posto AS resp_posto
            FROM tbl_posto_fabrica 
            JOIN tbl_posto ON tbl_posto_fabrica.posto = tbl_posto.posto 
            LEFT JOIN (
                SELECT DISTINCT posto FROM tbl_opiniao_posto_resposta 
                JOIN tbl_opiniao_posto_pergunta USING (opiniao_posto_pergunta) 
                JOIN tbl_opiniao_posto USING (opiniao_posto) 
                WHERE {$xCondOp}
            ) resp ON resp.posto = tbl_posto_fabrica.posto
            WHERE tbl_posto_fabrica.fabrica = $login_fabrica 
            AND   tbl_posto_fabrica.credenciamento = 'CREDENCIADO'
            AND   resp.posto IS NULL
            $linha_selecao
            $estado_selecao
            $distribuidor_selecao
            ORDER BY tbl_posto.nome";

    $res3 = pg_query($con,$sql);
    $qtde_sem_resposta = pg_num_rows($res3);
    echo "<table class='table table-striped table-bordered table-hover table-fixed'>";
    echo "<thead>";
    echo "<tr class='titulo_tabela'><th colspan='3'>RELAÇÃO DOS POSTOS QUE NÃO RESPONDERAM</th></tr>";
    echo "</thead>";
    echo "<tbody>";
    for ($i = 0 ; $i < pg_num_rows($res3) ; $i++) {

        if( $i %2 == 0 )
        {
            $cor = '#F7F5F0';
        }
        else
        {
            $cor = '#F1F4FA';
        }
        $codigo_posto = pg_result ($res3,$i,codigo_posto);
        $nome_posto   = pg_result ($res3,$i,nome);
        $fone_posto   = pg_result ($res3,$i,fone);
        echo "<tr bgcolor='$cor'>\n";
        echo "<td align='left'>&nbsp; $codigo_posto</td>";
        echo "<td align='left'>&nbsp; $nome_posto</td>";
        echo "<td align='left'>&nbsp; $fone_posto</td>";
        echo "</tr>";
    }
    echo "</tbody>";
    echo "</table>";
    echo "<br>";
}

if($listartudo == 4){
    $pergunta = $_GET['pergunta'];
    $resposta = $_GET['resposta'];

    $sql = "SELECT  tbl_posto_fabrica.codigo_posto, 
                    tbl_posto.nome ,
                    tbl_posto.email
            FROM tbl_posto_fabrica 
            JOIN tbl_posto ON tbl_posto_fabrica.posto = tbl_posto.posto 
            JOIN tbl_opiniao_posto_resposta ON tbl_opiniao_posto_resposta.posto = tbl_posto.posto
            WHERE tbl_posto_fabrica.fabrica                         = $login_fabrica 
            AND  tbl_opiniao_posto_resposta.opiniao_posto_pergunta = $pergunta
            AND     tbl_opiniao_posto_resposta.resposta               = '$resposta'
            $xpesquisa_selecao
            ORDER BY tbl_posto.nome";
    $resX = pg_exec ($con,$sql);

    $sql = "SELECT  pergunta
            FROM tbl_opiniao_posto_pergunta
            WHERE   opiniao_posto_pergunta = $pergunta";
    $resY = pg_exec($con,$sql);

    $desc_pergunta = pg_result($resY,0,pergunta);

    echo "<table class='table table-striped table-bordered table-hover table-fixed'>\n";
    if ($resposta == 'f') $resposta = 'não';
    if ($resposta == 't') $resposta = 'sim';
    echo "<thead>";
    echo "<tr class=titulo_coluna><th colspan='3'>RELAÇÃO DOS POSTOS QUE RESPONDERAM \"".ucfirst($resposta)."\" A QUESTÃO \"".$desc_pergunta."\"</th></tr>\n";
    echo "</thead>";
    echo "<tbody>";
    for ($i = 0 ; $i < pg_numrows ($resX) ; $i++) {
        $codigo_posto = pg_result ($resX,$i,codigo_posto);
        $nome_posto   = pg_result ($resX,$i,nome);
        $email_posto  = pg_result ($resX,$i,email);
        echo "<tr class='menu_top' bgcolor='$cor'>\n";
        echo "<td align='left'>&nbsp; $codigo_posto</td>\n";
        echo "<td align='left'>&nbsp; $nome_posto</td>\n";
        echo "<td align='left'>&nbsp; <a href='mailto:$email_posto'>$email_posto</a></td>\n";
        echo "</tr>\n";
    }
    echo "</tdoby>";
    echo "</table>\n";
    echo "<br>\n";
}

if ($listardetalhes == 4){
    if (strlen($_GET['codigo_posto']) > 0) $codigo_posto = $_GET['codigo_posto'];
    echo "<br>";
    $sql = "SELECT
                 tbl_opiniao_posto.opiniao_posto,
                 tbl_opiniao_posto.cabecalho   
            FROM tbl_opiniao_posto_resposta
            JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_opiniao_posto_resposta.posto 
             AND tbl_posto_fabrica.fabrica = $login_fabrica
             AND tbl_posto_fabrica.codigo_posto = '$codigo_posto'
            JOIN tbl_opiniao_posto_pergunta ON tbl_opiniao_posto_pergunta.opiniao_posto_pergunta = tbl_opiniao_posto_resposta.opiniao_posto_pergunta
            JOIN tbl_opiniao_posto ON tbl_opiniao_posto.opiniao_posto = tbl_opiniao_posto_pergunta.opiniao_posto
           WHERE tbl_opiniao_posto.fabrica = $login_fabrica 
           $xpesquisa_selecao
        GROUP BY tbl_opiniao_posto.opiniao_posto,tbl_opiniao_posto.cabecalho;";
    $res = pg_query($con,$sql);


    $sqlPosto = "SELECT tbl_posto.nome
                   FROM tbl_posto
                   JOIN tbl_posto_fabrica USING (posto)
                  WHERE tbl_posto_fabrica.codigo_posto = '$codigo_posto'";
    $resPosto = pg_query($con ,$sqlPosto);

    $posto_nome = strtoupper(pg_fetch_result($resPosto,0,nome));

    echo "<h2 class='titulo_tabela' style='padding:10px;'>$posto_nome</h2>";

    for ($i=0; $i < pg_num_rows($res); $i++) { 

    $cabecalho = strtoupper(pg_fetch_result($res,$i,cabecalho));
    $opiniao_posto = strtoupper(pg_fetch_result($res,$i,opiniao_posto));

?>
    <table class='table table-striped table-bordered table-hover table-fixed'>
        <thead>
        <tr class='titulo_tabela' >
            <th align='center' colspan='3'><?php echo $cabecalho;?></th>
        </tr>
        <tr>
            <th class='titulo_coluna'>PERGUNTAS</th>
            <th class='titulo_coluna'>RESPOSTAS</th>
            <th class='titulo_coluna'>DATA RESPOSTA</th>
        </tr>
        </thead>
        <tbody>
        <?php
        $sqlPerguntas = "SELECT  DISTINCT 
                                TO_CHAR(tbl_opiniao_posto_resposta.data_resposta, 'DD/MM/YYYY') AS data_resposta,
                                tbl_opiniao_posto_pergunta.pergunta,
                                tbl_opiniao_posto_resposta.resposta,
                                tbl_opiniao_posto_pergunta.ordem   
                           FROM tbl_opiniao_posto_resposta
                           JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_opiniao_posto_resposta.posto 
                            AND tbl_posto_fabrica.fabrica = $login_fabrica
                            AND tbl_posto_fabrica.codigo_posto = '$codigo_posto'
                           JOIN tbl_opiniao_posto_pergunta ON tbl_opiniao_posto_pergunta.opiniao_posto_pergunta = tbl_opiniao_posto_resposta.opiniao_posto_pergunta
                           JOIN tbl_opiniao_posto ON tbl_opiniao_posto.opiniao_posto = tbl_opiniao_posto_pergunta.opiniao_posto
                          WHERE tbl_opiniao_posto.fabrica= $login_fabrica
                            AND tbl_opiniao_posto.opiniao_posto= $opiniao_posto
                       ORDER BY tbl_opiniao_posto_pergunta.ordem;";
        $resPerguntas = pg_query($con,$sqlPerguntas);
        $qtde_perguntas = pg_num_rows($resPerguntas);
        if ($qtde_perguntas > 0) {
            $contador  = 1;
            for ($x=0; $x < $qtde_perguntas; $x++) {
                $pergunta = pg_fetch_result($resPerguntas,$x,pergunta);
                $resposta = pg_fetch_result($resPerguntas,$x,resposta);
                $data_resposta = pg_fetch_result($resPerguntas,$x,data_resposta);
                if ($resposta == 't') $resposta = 'Sim';
                if ($resposta == 'f') $resposta = 'Não';

                echo "<TR>\n";
                echo "  <TD class='menu_top'><div align='left'>&nbsp;$pergunta</div></TD>\n";
                echo "  <TD class='menu_top'><div align='left'>&nbsp;$resposta</div></TD>\n";
                echo "  <TD class='menu_top tac'>&nbsp;$data_resposta</TD>\n";
                echo "</TR>\n";
                if ($login_fabrica == 151 && $qtde_perguntas == $contador) {
                    echo "<TR>\n";
                        echo "<td class='tac' colspan='3'>&nbsp; <a href='opiniao_posto_relatorio.php?acao=excluir&opiniao_posto=$opiniao_posto&listartudo=$listartudo&listardetalhes=$listardetalhes&codigo_posto=$codigo_posto&linha=$linha&estado=$estado&distribuidor_posto=$distribuidor_posto' class='btn btn-danger'>Excluir Respostas</a></td>";
                    echo "</TR>\n";
                }
                $contador++;
            }//for
        }//if
        ?>
        </tbody>
    </TABLE>
<?php
    }//fecha for
}

?>
<script>


$("button.download-xls").on("click", function() {
    var xls = $(this).data("xls");

    window.open("xls/"+xls);
});

</script>
<?include "rodape.php"; ?>
