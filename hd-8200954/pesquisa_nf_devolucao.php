<?php
include_once 'dbconfig.php';
include_once 'includes/dbconnect-inc.php';
include_once 'autentica_usuario.php';

include_once 'helpdesk/mlg_funciones.php';

$menu_os[]['link'] = 'linha_de_separação';

$layout_menu = "os";
$title = "Consulta de Nota fiscal de devolução";

include 'cabecalho.php';

if(isset($_POST['btnacao'])){

    $data_inicial   = $_POST['data_inicial'];
    $data_final     = $_POST['data_final'];
    $nota_fiscal    = $_POST['nota_fiscal'];
    $status         = $_POST['status'];

    if(strlen(trim($data_inicial) >0) ){
        list($di, $mi, $yi) = explode("/", $data_inicial);
        if(!checkdate($mi,$di,$yi))
            $msg_erro = "Data Inválida";

        list($df, $mf, $yf) = explode("/", $data_final);
        if(!checkdate($mf,$df,$yf))
            $msg_erro = "Data Inválida";

        $aux_data_inicial = "$yi-$mi-$di";
        $aux_data_final = "$yf-$mf-$df";

        if(strlen($msg_erro)==0){
            if(strtotime($aux_data_final) < strtotime($aux_data_inicial)){
                $msg_erro = "Data Inválida.";
            }elseif (strtotime($aux_data_inicial) < strtotime($aux_data_final . ' -12 month')) { //
                    //$msg_erro = traduz("Período não pode ser maior que 90 dias",$con,$cook_idioma);
            }else{
                $cond_data = " AND tbl_faturamento.data_input between '$aux_data_inicial 00:00:00' AND '$aux_data_final 23:59:59' ";
            }
        }

    }
    // else{
    //     $msg_erro = "Data Inválida.";
    // }

    if(strlen(trim($nota_fiscal))>0){
        $cond_nf = " AND tbl_faturamento.nota_fiscal = '$nota_fiscal' and tbl_faturamento.nota_fiscal is not null ";
    }elseif( (strlen(trim($nota_fiscal))==0) AND (strlen(trim($data_inicial) == 0 AND strlen(trim($data_final) == 0) )) ) {
        $msg_erro = "Por favor informar um período ou nota fiscal.";
    }

    if(strlen(trim($status))>0){
        $cond_status = " AND tbl_os_campo_extra.os_bloqueada = '$status' ";
    }
}

?>

<style type="text/css">
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
.sucesso{
    background-color:#008000;
    font: bold 14px "Arial";
    color:#FFFFFF;
    text-align:center;
}
.formulario{
    background-color:#D9E2EF;
    font:11px Arial;
    text-align:left;
}
.subtitulo{

    background-color: #7092BE;
    font:bold 14px Arial;
    color: #FFFFFF;
    text-align:center;
}
table.tabela tr td{
    font-family: verdana;
    font-size: 11px;
    border-collapse: collapse;
    border:1px solid #596d9b;
}
.texto_avulso{
    font: 14px Arial; color: rgb(89, 109, 155);
    background-color: #d9e2ef;
    text-align: center;
    width:700px;
    margin: 0 auto;
    border-collapse: collapse;
    border:1px solid #596d9b;
}
.informacao{
    font: 14px Arial; color:rgb(89, 109, 155);
    background-color: #C7FBB5;
    text-align: center;
    width:700px;
    margin: 0 auto;
    border-collapse: collapse;
    border:1px solid #596d9b;
}
.espaco{
    padding-left:150px;
    width: 220px;
}

.sem_pagamento{
    font-family: bold small Verdana, Arial, Helvetica, sans-serif;
    color: #888888;
    font-weight: bold;

}

</style>
<style type="text/css">
    @import "plugins/jquery/datepick/telecontrol.datepick.css";
</style>
<script type="text/javascript" src="plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
<script language="javascript" src="js/assist.js"></script>
<script language='javascript' src='ajax.js'></script>

<script type="text/javascript" src="plugins/jquery/datepick/jquery.datepick.js"></script>
<script type="text/javascript" src="plugins/jquery/datepick/jquery.datepick-pt-BR.js"></script>
<script type="text/javascript" src="js/jquery.maskedinput2.js"></script>
<script type="text/javascript">
    $(document).ready(function() {
        $('#data_inicial').datepick({startDate:'01/01/2000'});
        $('#data_final').datepick({startDate:'01/01/2000'});
        $('#data_inicial').maskedinput("99/99/9999");
        $('#data_final').maskedinput("99/99/9999");
    });
</script>
<bR><br>
<?php if(strlen(trim($msg_erro))>0){ ?>
<table width="700" border="0">
    <tr>
        <td class="msg_erro"><?=$msg_erro?></td>
    </tr>
</table>
<?php } ?>

<form name="frm_consulta" method="post" action="<?echo $PHP_SELF?>">

    <input type="hidden" name="acao">

    <table align="center" class="formulario" width="700" border="0">
        <tr>
            <td class="titulo_tabela" align="center">Parâmetros de Pesquisa</td>
        </tr>
    </table>
    <table align="center" class="formulario" width="700" border="0">

     <tr align='left'>
        <td class="espaco"><? fecho("data.inicial",$con,$cook_idioma); ?></td>
        <td><? fecho("data.final",$con,$cook_idioma); ?></td>
    </tr>
    <tr align='left'>
        <td class="espaco">
            <input type="text" name="data_inicial" id="data_inicial" size="13" maxlength="10" value="<? echo substr($data_inicial,0,10);?>" class="frm" />
        </td>
        <td>
            <input type="text" name="data_final" id="data_final" size="13" maxlength="10" value="<? echo substr($data_final,0,10);?>" class="frm">
        </td>
    </tr>
    <tr align='left'>
        <td class="espaco"><? fecho("nota.fiscal",$con,$cook_idioma)?> *</td>
        <td >Situação</td>
    </tr>
    <tr align='left'>
        <td class="espaco">
            <input type="text" name="nota_fiscal" size="10" value="<?echo $nota_fiscal?>" class="frm">
        </td>
        <td>
            <select name="status" class="frm">
                <option value=""></option>
                <option value="f" <?php if($status == 'f'){ echo " selected "; } ?> >Liberada</option>
                <option value="t" <?php if($status == 't'){ echo " selected "; } ?> >Bloqueada</option>
            </select>
        </td>
    </tr>
    <tr>
        <td>&nbsp; </td>
    </tr>
    <tr>
        <td colspan="2" align="center">
            <input type="submit" name="btnacao" value="Pesquisar">
        </td>
    </tr>
    <tr>
        <td>&nbsp; </td>
    </tr>
    </table>
</form>

<?php

if (isset($_POST['btnacao']) AND strlen(trim($msg_erro))==0) {

    $sql = "
        SELECT  tbl_faturamento.data_input,
                tbl_faturamento.data_input,
                tbl_os_campo_extra.os_bloqueada,
                tbl_faturamento.nota_fiscal,
                tbl_faturamento_item.qtde,
                tbl_faturamento_item.qtde_inspecionada,
                tbl_faturamento.faturamento,
                tbl_faturamento_item.peca,
                tbl_faturamento_item.os,
                tbl_peca.referencia,
                tbl_peca.descricao
        FROM    tbl_faturamento
        JOIN    tbl_faturamento_item on tbl_faturamento.faturamento = tbl_faturamento_item.faturamento
        JOIN    tbl_peca ON tbl_peca.peca = tbl_faturamento_item.peca
        JOIN    tbl_os_campo_extra ON tbl_os_campo_extra.os = tbl_faturamento_item.os
        WHERE   tbl_faturamento.fabrica = $login_fabrica
        $cond_nf
        $cond_data
        $cond_status
        AND     tbl_faturamento.distribuidor = $login_posto
        AND     tbl_faturamento.nota_fiscal IS NOT NULL
        AND     tbl_faturamento_item.os     IS NOT NULL
        AND     tbl_faturamento_item.pedido IS NULL
  ORDER BY      data_input, os
 ";
    $res = pg_query($con, $sql);

    $fetch = pg_fetch_all($res);

    $contOSCallcenter = pg_num_rows($res);
    if ($contOSCallcenter > 0) {
        echo '<br/>';
        /*echo "<table width='700'>";
            echo "<tr>";
                echo "<td width='60' bgcolor='#98FB98'>&nbsp;</td>";
                echo "<td style='font-size:12px'>Nota já enviada.</td>";
            echo "</tr>";
        echo "</table>";*/
        echo "<form id='frm_os_pendente_pagamento' name='frm_os_pendente_pagamento' method='POST' action='os_posto_lgr.php'>";

        echo '<table border="0" cellspacing="0" cellpadding="2" width="700" class="tabela">';
        echo '<thead>';
            echo '<tr class="titulo_tabela">';
                echo '<th colspan="7">Nota fiscal de Devolução</th>';
            echo '</tr>';
            echo '<tr class="titulo_coluna">';

                echo '<th>OS</th>';
                echo '<th>Peças</th>';
                echo '<th>Qtde Pendente</th>';
                echo '<th>Qtde Conferida</th>';
                echo '<th>Nota Fiscal</th>';
                echo '<th>Emissão</th>';
                echo '<th>Situação</th>';
            echo '</tr>';
        echo '</thead>';

        echo '<tbody>';
        $a=1;
        //while ($fetch = pg_fetch_assoc($res)) {
        foreach($fetch as $linha){
            $faturamento         = $linha['faturamento'];
            $pecas_qtde         = $linha['qtde'];
            $qtde_inspecionada  = $linha['qtde_inspecionada'];
            $nota_fiscal        = $linha['nota_fiscal'];
            $referencia         = $linha['referencia'];
            $descricao          = $linha['descricao'];
            $os                 = $linha['os'];
            $os_bloqueada       = $linha['os_bloqueada'];
            $emissao            = substr(mostra_data($linha['data_input']), 0 , 10);
            $peca = $referencia ." - ". $descricao;

            $value_array = explode("-", $pecas_qtde[0]);

            $situacao = ($os_bloqueada == 't')? 'Bloqueada': 'Liberada';

            $bgcolor = ($lgr == true)? "bgcolor='#98FB98'" : "";

            echo "<tr class='Conteudo' style='text-align:center;' $bgcolor >";

                echo '<td><a target="_blank" href="os_press.php?os='.$os.'">'.$os.'</a></td>';
                echo "<td>$peca</td>";
                echo "<td>$pecas_qtde</td>";
                echo "<td>$qtde_inspecionada</td>";
                echo "<td><a href='espelho_lgr.php?nota_fiscal=$faturamento' target='_blank'>$nota_fiscal</a></td>";
                echo "<td>$emissao</td>";
                echo "<td>$situacao</td>";

            if ($a % 2 == 0) {
                $bgcolor = '#FFFFFF';
            } else {
                $bgcolor = '#EAEAEA';
            }

            echo '</tr>';
        $a++  ;
        }

        echo '</tbody>';
        echo '</table>';
        //echo '<br/>';
        /*echo '<table border="0" cellspacing="0" cellpadding="2" width="700">';
            echo "<tr>";
                echo "<td align='center'>";
                echo " <input type='hidden' name='btnacao' value='gravar'>
                        <input type='hidden' id='qtde_os' name='qtde' value='$contOSCallcenter'>
                <button type='button' id='btn_gravar' name='btnacao'>Gravar Nota Fiscal</button></td>";
            echo "</tr>";
        echo "</table>";*/
        echo "</form>";
    }else{
        echo "<br><br>";
        echo '<table border="0" cellspacing="0" cellpadding="2" width="700">';
        echo "<tr>";
            echo "<td align='center' class='sem_pagamento'>Nenhum registro encontrado.</td>";
        echo "</tr>";
        echo "</table>";
    }
}
?>


<script type="text/javascript">
    $(function() {
        $("#btn_gravar").click(function(){

            if ($("input[class^='os_check_']").is(':checked')) {
                $("#frm_os_pendente_pagamento").submit();
            }else{
                alert("Favor selecionar uma OS");
            }
        });
    });
</script>

<?php
include "rodape.php";

