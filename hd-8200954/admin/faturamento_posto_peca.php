<?php

include "dbconfig.php";
include "includes/dbconnect-inc.php";
include 'autentica_admin.php';

$title = 'Relatório Faturamento Produto, Família e Data';
$admin_privilegios = "financeiro";

include 'cabecalho.php';
include 'javascript_pesquisas.php';
include 'javascript_calendario.php';

?>
<style>
    body {
        font-family: Verdana,Geneva,Arial,Helvetica,sans-serif;
        font-size: 10px;
        margin:0px;
    }
    .tablesorter {
        font-size: 11px;
        border: 1px solid #CCC;
    }
</style>
<link rel="stylesheet" href="js/blue/style.css" type="text/css" id="" media="print, projection, screen" />
<script type="text/javascript" src="bi/js/jquery.tablesorter.js"></script>
<script type="text/javascript" src="bi/js/jquery.tablesorter.pager.js"></script>
<script type="text/javascript" src="bi/js/chili-1.8b.js"></script>
<script type="text/javascript" src="bi/js/docs.js"></script>
<script type="text/javascript">

    $(function() {
        $('#data_inicial').datePicker({startDate:'01/01/2000'});
        $('#data_final').datePicker({startDate:'01/01/2000'});
        $("#data_inicial").maskedinput("99/99/9999");
        $("#data_final").maskedinput("99/99/9999");

        // add new widget called repeatHeaders
        $.tablesorter.addWidget({
            // give the widget a id
            id: "repeatHeaders",
            // format is called when the on init and when a sorting has finished
            format: function(table) {
                // cache and collect all TH headers
                if(!this.headers) {
                    var h = this.headers = [];
                    $("thead th",table).each(function() {
                        h.push(
                            "<th>" + $(this).text() + "</th>"
                        );
                    });
                }

                // remove appended headers by classname.
                $("tr.repated-header",table).remove();

                // loop all tr elements and insert a copy of the "headers"
                for(var i=0; i < table.tBodies[0].rows.length; i++) {
                    // insert a copy of the table head every 10th row
                    if((i%20) == 0) {
                        if(i!=0){
                        $("tbody tr:eq(" + i + ")",table).before(
                            $("<tr></tr>").addClass("repated-header").html(this.headers.join(""))

                        );
                    }}
                }

            }
        });
        $("table").tablesorter({
            widgets: ['zebra','repeatHeaders']
        });

    });

</script>
<br />
<br />
<form method="POST" name="frm_faturamento" action="<?=$PHP_SELF?>">
    <table width="700" align="center" border="0" cellspacing="3" cellpadding="2">
        <tr>
            <td align='left'>
                <span style="float:left">Data Inicial &nbsp;</span>
                <input size="12" maxlength="10" type="text" name="data_inicial" id="data_inicial" rel="data" value="<?=$data_inicial?>" class='frm' />
            </td>
            <td>
                <span style="float:left;margin-left:40px">Data Final &nbsp;</span>
                <input size="12" maxlength="10" type="text" name="data_final" id="data_final" rel="data" value="<?=$data_final?>" class="frm" />
            </td>
        </tr>
        <tr align='left'>
            <td>
                <span>Ref. Produto</span>
                <input class="frm" type="text" name="produto_referencia" id="produto_referencia" size="15" maxlength="20" value="<?=$produto_referencia?>" />&nbsp;
                <img src='imagens/lupa.png' border='0' align='absmiddle' style="cursor:pointer" onclick="javascript: fnc_pesquisa_produto (document.getElementById('produto_referencia'), document.getElementById('produto_descricao'),'referencia')" />
            </td>
            <td>
                <span>Descrição Produto</span>
                <input class="frm" type="text" name="produto_descricao" id="produto_descricao" size="30" value="<? echo $produto_descricao ?>" />&nbsp;
                <img src='imagens/lupa.png'  style="cursor:pointer" border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_produto (document.getElementById('produto_referencia'), document.getElementById('produto_descricao'),'descricao')">
            </td>
        </tr>
        <tr>
            <td align="center" nowrap="nowrap" colspan="2">
                <span>Familia </span><?php
                $sql = "SELECT *
                          FROM tbl_familia
                         WHERE fabrica = $login_fabrica
                           AND ativo = 't'
                         ORDER BY descricao";

                $res = pg_exec($con,$sql);

                if (pg_numrows($res) > 0) {
                    echo "<select name='familia'>\n";
                        echo "<option value=''>.:: Selecione uma familia ::.</option>\n";
                    for ($x = 0 ; $x < pg_numrows($res); $x++) {
                        $aux_familia   = trim(pg_result($res,$x,familia));
                        $aux_descricao = trim(pg_result($res,$x,descricao));
                        echo '<option value="'.$aux_familia.'"'.($familia == $aux_familia ? ' selected="selected" ' : '').'>'.$aux_descricao.'</option>';
                    }
                    echo "</select>\n";
                }?>
            </td>
        </tr>
        <tr>
            <td colspan="2">
                <img src="imagens_admin/btn_filtrar.gif" onclick="javascript: document.frm_faturamento.submit() " alt="Filtrar" border="0" style="cursor:pointer;" />
            </td>
        </tr>
    </table>
</form><?php

if (!empty($_POST)) {

    $data_inicial       = $_POST['data_inicial'];
    $data_final         = $_POST['data_final'];
    $familia            = $_POST['familia'];
    $produto_referencia = $_POST['produto_referencia'];

    $sql = "SELECT SUM(tbl_faturamento_item.qtde)  as qtd        ,
                   SUM(tbl_faturamento_item.preco) as preco      ,
                   tbl_produto.referencia          as referencia ,
                   tbl_produto.descricao           as produto
              FROM tbl_faturamento_item
              JOIN tbl_faturamento  ON tbl_faturamento_item.faturamento = tbl_faturamento.faturamento
              JOIN tbl_peca         ON tbl_faturamento_item.peca        = tbl_peca.peca
              JOIN tbl_lista_basica ON tbl_lista_basica.peca            = tbl_peca.peca
              JOIN tbl_posto        ON tbl_faturamento.posto            = tbl_posto.posto
              JOIN tbl_produto      ON tbl_produto.produto              = tbl_lista_basica.produto
             WHERE tbl_faturamento.fabrica  = $login_fabrica
               AND tbl_lista_basica.fabrica = $login_fabrica";

    if (!empty($data_inicial) && !empty($data_final)) {
        $sql .= " AND tbl_faturamento.emissao between '$data_inicial' and '$data_final' ";
    }
    if (!empty($familia)) {
        $sql .= " AND tbl_produto.familia = ".$familia;
    }
    if (!empty($produto_referencia)) {
        $sql .= " AND tbl_produto.referencia = '$produto_referencia' ";
    }

    $sql .= "   GROUP BY tbl_faturamento_item.peca ,
                         tbl_faturamento_item.qtde ,
                         tbl_faturamento_item.preco ,
                         tbl_produto.referencia ,
                         tbl_produto.descricao
                ORDER BY tbl_produto.descricao";

    $res = pg_query($con,$sql);

    if (@pg_num_rows($res) > 0) {

        $vet = array();
        $i   = 0;

        while ($row = pg_fetch_assoc($res)) {

            if ($row['referencia'] != $vet[$i]['referencia'] && count($vet) > 0) {
                $i++;
            }

            $vet[$i]['referencia']  = $row['referencia'];
            $vet[$i]['produto']     = $row['produto'];
            $vet[$i]['qtd']        += $row['qtd'];
            $vet[$i]['total']      += $row['preco'] * $row['qtd'];

            $total_geral += $row['preco'] * $row['qtd'];

        }

        if (!empty($vet)) {?>
            <br />
            <br />
            <table cellpadding="2" cellspacing="0" border="0" align="center" rules="all" class="tablesorter">
                <thead>
                    <tr bgcolor="#D9E2EF">
                        <th>Produto</th>
                        <th>Produto</th>
                        <th>Qtde.</th>
                        <th>Total</th>
                        <th>%</th>
                    </tr>
                </thead><?php
                for ($i = 0; $i < count($vet); $i++) {
                    $cor = ($i % 2 == 0) ? '#F7F5F0' : '#F1F4FA';
                    $tot_qtd += $vet[$i]['qtd'];
                    $total   += $vet[$i]['total'];
                    echo '<tbody>';
                        echo '<tr bgcolor="'.$cor.'">';
                            echo '<td>&nbsp;'.$vet[$i]['referencia'].'</td>';
                            echo '<td align="left">&nbsp;'.$vet[$i]['produto'].'</td>';
                            echo '<td>&nbsp;'.$vet[$i]['qtd'].'</td>';
                            echo '<td align="left" nowrap="nowrap">R$ '.number_format($vet[$i]['total'],2,',','.').'</td>';
                            echo '<td align="left" nowrap="nowrap">'.number_format($vet[$i]['total'] / $total_geral * 100,3,',','.').' %</td>';
                        echo '</tr>';
                    echo '</tbody>';
                }
                echo '<tfoot>';
                    echo '<tr bgcolor="#D9E2EF">';
                        echo '<td colspan="2" align="right"><b>Total:</b></td>';
                        echo '<td>'.$tot_qtd.'</td>';
                        echo '<td align="right" nowrap="nowrap"> R$ '.number_format($total,2,',','.').'</td>';
                        echo '<td>100%</td>';
                    echo '</tr>';
                echo '</tfoot>';?>
            </table><?php
        }
    } else {
        echo "<center><h2>Nenhum registro encontrado</h2></center>";
    }
}?>
<br />
<br />
<? include "rodape.php"; ?>