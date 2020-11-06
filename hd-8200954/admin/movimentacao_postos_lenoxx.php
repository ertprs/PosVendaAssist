<?php
include "dbconfig.php";
include "includes/dbconnect-inc.php";

$admin_privilegios = "financeiro";
include "autentica_admin.php";

$layout_menu = "financeiro";
$title = "Movimentação dos Postos Autorizados";

############################## EXCELL ##############################
if (isset($_POST['gerar_excel'])) {

    $data_inicialx = $_POST['data_inicial'];
    $data_finalx = $_POST['data_final'];


    $data_inicialx = str_replace(" ", "", $data_inicial);
    $data_inicialx = str_replace("-", "", $data_inicialx);
    $data_inicialx = str_replace("/", "", $data_inicialx);
    $data_inicialx = str_replace(".", "", $data_inicialx);

    $data_finalx = str_replace(" ", "", $data_final);
    $data_finalx = str_replace("-", "", $data_finalx);
    $data_finalx = str_replace("/", "", $data_finalx);
    $data_finalx = str_replace(".", "", $data_finalx);

    if (strlen($data_inicialx) == 6)
        $data_inicialx = substr($data_inicialx, 0, 4) . "20" . substr($data_inicialx, 4, 2);
    if (strlen($data_finalx) == 6)
        $data_finalx = substr($data_finalx, 0, 4) . "20" . substr($data_finalx, 4, 2);

    if (strlen($data_inicialx) > 0)
        $data_inicialx = substr($data_inicialx, 0, 2) . "/" . substr($data_inicialx, 2, 2) . "/" . substr($data_inicialx, 4, 4);
    if (strlen($data_finalx) > 0)
        $data_finalx = substr($data_finalx, 0, 2) . "/" . substr($data_finalx, 2, 2) . "/" . substr($data_finalx, 4, 4);

    if (strlen($data_inicialx) < 8)
        $data_inicialx = date("d/m/Y");
    $data_inicialx = substr($data_inicialx, 6, 4) . "-" . substr($data_inicialx, 3, 2) . "-" . substr($data_inicialx, 0, 2);

    if (strlen($data_finalx) < 10)
        $data_finalx = date("d/m/Y");
    $data_finalx = substr($data_finalx, 6, 4) . "-" . substr($data_finalx, 3, 2) . "-" . substr($data_finalx, 0, 2);


    $data = date("d-m-Y-H:i");
    $fileName = "relatorio_os_atendimento-{$data}.xls";
    $file = fopen("/tmp/{$fileName}", "w");

    fwrite($file, "
	<table border='1'>
	    <thead>
	            <tr>
	                    <th colspan='8' bgcolor='#D9E2EF' color='#333333' style='color: #333333 !important;' >
	                            MOVIMENTAÇÃO DOS POSTOS AUTORIZADOS
	                    </th>
	            </tr>
	            <tr>
	                    <th bgcolor='#b1b4b5' color='#FFFFFF' style='color: #FFFFFF !important;'>CNPJ</th>
	                    <th bgcolor='#b1b4b5' color='#FFFFFF' style='color: #FFFFFF !important;'>Posto</th>
	                    <th bgcolor='#b1b4b5' color='#FFFFFF' style='color: #FFFFFF !important;'>Garantia</th>
	                    <th bgcolor='#b1b4b5' color='#FFFFFF' style='color: #FFFFFF !important;'>Lançamentos</th>
	                    <th bgcolor='#b1b4b5' color='#FFFFFF' style='color: #FFFFFF !important;'>Saldo</th>
	                    <th bgcolor='#b1b4b5' color='#FFFFFF' style='color: #FFFFFF !important;'>Banco</th>
	                    <th bgcolor='#b1b4b5' color='#FFFFFF' style='color: #FFFFFF !important;'>Qtd OS</th>
	                    <th bgcolor='#b1b4b5' color='#FFFFFF' style='color: #FFFFFF !important;'>% OS</th>
	            </tr>
	    </thead>
    <tbody>
");

    $sql = "SELECT COUNT(tbl_os_extra.os) AS qtde_os
			FROM tbl_extrato
			JOIN tbl_os_extra USING(extrato)
			WHERE tbl_extrato.fabrica     = $login_fabrica 
			AND tbl_extrato.data_geracao BETWEEN '$data_inicialx 00:00:00' AND '$data_finalx 23:59:59'
			AND tbl_extrato.liberado NOTNULL
			AND tbl_extrato.posto NOT IN ('6359','14301','20321')";
    $res = pg_exec($con, $sql);
    $qtde_os_total = trim(pg_result($res, 0, qtde_os));

    $sql = "SELECT count(tbl_posto.cnpj||' '||tbl_posto.nome) as posto,
					sum(tbl_extrato.mao_de_obra) as mao_de_obra,
					sum(tbl_extrato.avulso) as avulso,
					sum(tbl_extrato.total) as total
			FROM tbl_extrato
			JOIN tbl_posto USING(posto)
			JOIN tbl_posto_fabrica USING(posto)
			LEFT join tbl_banco on tbl_posto_fabrica.banco = tbl_banco.codigo
			WHERE tbl_extrato.fabrica     = $login_fabrica 
			AND tbl_posto_fabrica.fabrica = $login_fabrica
			AND tbl_extrato.data_geracao  BETWEEN '$data_inicialx 00:00:00' AND '$data_finalx 23:59:59'
			AND tbl_extrato.liberado      NOTNULL
			AND tbl_extrato.posto         NOT IN ('6359','14301','20321')";
    $res = pg_exec($con, $sql);

    $qtde_postos = trim(pg_result($res, 0, posto));
    $total_mao_de_obra = trim(pg_result($res, 0, mao_de_obra));
    $total_avulso = trim(pg_result($res, 0, avulso));
    $total_geral = trim(pg_result($res, 0, total));

    $sql = "SELECT tbl_posto.cnpj,
					tbl_posto.nome as posto,
					tbl_extrato.mao_de_obra,
					tbl_extrato.avulso,
					tbl_extrato.total,
					tbl_banco.nome         AS banco,
					COUNT(tbl_os_extra.os) AS qtde_os
			FROM tbl_extrato
			LEFT JOIN tbl_os_extra USING(extrato)
			JOIN tbl_posto using(posto)
			JOIN tbl_posto_fabrica using(posto)
			LEFT JOIN tbl_banco on tbl_posto_fabrica.banco = tbl_banco.codigo
			WHERE tbl_extrato.fabrica     = $login_fabrica 
			AND tbl_posto_fabrica.fabrica = $login_fabrica
			AND tbl_extrato.data_geracao BETWEEN '$data_inicialx 00:00:00' AND '$data_finalx 23:59:59'
			AND tbl_extrato.liberado NOTNULL
			AND tbl_extrato.posto NOT IN ('6359','14301','20321')
			GROUP BY tbl_posto.cnpj,
					tbl_posto.nome,
					tbl_extrato.mao_de_obra,
					tbl_extrato.avulso,
					tbl_extrato.total,
					tbl_banco.nome
			ORDER BY tbl_posto.nome;";

    $res = pg_exec($con, $sql);





    for ($i = 0; $i < pg_num_rows($res); $i++) {
        $cnpj = pg_fetch_result($res, $i, 'cnpj');
        $posto = pg_fetch_result($res, $i, 'posto');
        $mao_de_obra = pg_fetch_result($res, $i, 'mao_de_obra');
        $avulso = pg_fetch_result($res, $i, 'avulso');
        $total = pg_fetch_result($res, $i, 'total');
        $banco = pg_fetch_result($res, $i, 'banco');
        $qtde_os = pg_fetch_result($res, $i, 'qtde_os');
        $porcentagem = ($qtde_os / $qtde_os_total) * 100;
        $porcentagem = number_format($porcentagem, 2, ',', '.');

        $total = number_format($total,2,',','.');        
        $mao_de_obra = number_format($mao_de_obra,2,',','.');
        $avulso = number_format($avulso,2,',','.');



        fwrite($file, "
                <tr>
                        <td nowrap align='center'>{$cnpj}</td>
                        <td nowrap align='center'>{$posto}</td>
                        <td nowrap align='center'>{$mao_de_obra}</td>
                        <td nowrap align='center'>{$avulso}</td>
                        <td nowrap align='center'>{$total}</td>
                        <td nowrap align='center'>{$banco}</td>
                        <td nowrap align='center'>{$qtde_os}</td>
                        <td nowrap align='center'>{$porcentagem}</td>
                </tr>"
        );
    }

    fwrite($file, '</tbody></table>');

    $total_mao_de_obra = number_format($total_mao_de_obra, 2,',','.');
    $total_avulso = number_format($total_avulso, 2,',','.');
    $total_geral = number_format($total_geral, 2,',','.');
    $qtde_os_total = number_format($qtde_os_total, 0,',','.');

    fwrite($file,  "<table width='700' align='center' border='1' cellspacing='2'>
     <tr class = 'menu_top'>
     	<td nowrap></td>
     	<td nowrap></td>
     	<td nowrap></td>
     	<td nowrap></td>
     	<td nowrap></td>
     	<td nowrap></td>
     	<td nowrap></td>
     	<td nowrap></td>

     </tr>
     <tr class = 'menu_top'>
     <td bgcolor='#b1b4b5' color='#FFFFFF' style='color: #FFFFFF !important;' align='center' nowrap colspan=3 rowspan=2>TOTAL GERAL</td>
     <td bgcolor='#b1b4b5' color='#FFFFFF' style='color: #FFFFFF !important;' align='center' nowrap>Posto</td>
     <td bgcolor='#b1b4b5' color='#FFFFFF' style='color: #FFFFFF !important;' align='center' nowrap>Garantia</td>
     <td bgcolor='#b1b4b5' color='#FFFFFF' style='color: #FFFFFF !important;' align='center' nowrap>Lançamentos</td>
     <td bgcolor='#b1b4b5' color='#FFFFFF' style='color: #FFFFFF !important;' align='center' nowrap>Saldo</td>
     <td bgcolor='#b1b4b5' color='#FFFFFF' style='color: #FFFFFF !important;' align='center' nowrap>Total OS</td>
     </tr>
     <tr class = table_line>
     <td align='right' nowrap>{$qtde_postos}</td>
     <td align='right' nowrap>{$total_mao_de_obra}</td>
     <td align='right' nowrap>{$total_avulso}</td>
     <td align='right' nowrap>{$total_geral}</td>
     <td align='right' nowrap>{$qtde_os_total}</td>
     </tr>
     </table>");
    
    




    if (file_exists("/tmp/{$fileName}")) {
        system("mv /tmp/{$fileName} xls/{$fileName}");
        // devolve para o ajax o nome doa rquivo gerado
        echo "xls/{$fileName}";
        exit;
    }
}
############################## FIM EXCELL ##############################

include "cabecalho_new.php";
$plugins = array("mask", "datepicker");
include "plugin_loader.php";

if (count($_POST) > 0) {
    $jsonPOST = excelPostToJson($_POST);
} else {
    $jsonPOST = "";
}
?>



<?php //include "javascript_calendario.php"; //adicionado por Fabio 27-09-2007  ?>

<script type="text/javascript" charset="utf-8">
    $(function() {
        // $('#data_inicial').datePicker({startDate:'01/01/2000'});
        // $('#data_final').datePicker({startDate:'01/01/2000'});
        $('#data_inicial').datepicker().mask("99/99/9999");
        ;
        $('#data_final').datepicker().mask("99/99/9999");
        ;

    });
</script>


<?

echo "<FORM method = 'POST' action='$PHP_SELF' name='FORMULARIO' class='form-search form-inline tc_formulario'>";
echo "<input type='hidden' name='btnacao' value=''>";
//width='600' align='center' border='0' cellspacing='5' cellpadding='3' border='0'
?>
<div class="titulo_tabela">Busca</div>
<br>
<p>Digite o intervalo de datas para gerar o relatório</p>

<div class="row">
    <div class="span2"></div>
    <div class="span4">
        <div class="control-group ">
            <label class="control-label" for="mao_de_obra_admin">Data Inicial</label>
            <div class="controls controls-row">
                <div class="inptc8  ">
                    <?php echo "<INPUT maxlength='10' TYPE='text' NAME='data_inicial' class='frm tac' id='data_inicial' value='$data_inicial'>"; ?>												
                </div>
            </div>
        </div>

    </div>
    <div class="span4">
        <div class="control-group ">
            <label class="control-label" for="mao_de_obra_admin">Data Final</label>
            <div class="controls controls-row">
                <div class="inptc8  ">
                    <?php echo "<INPUT size='12' maxlength='10' TYPE='text' NAME='data_final' class='frm tac' id='data_final' value='$data_final'>"; ?>												
                </div>
            </div>
        </div>		
    </div>
    <div class="span2"></div>
</div>
<br>
<div class="row">
    <div class="span11 tac">
        <?php echo "<input class=\"btn\" value=\"Continuar\" type=\"button\" onclick=\"javascript: if (document.FORMULARIO.btnacao.value == '' ) { document.FORMULARIO.btnacao.value='pesquisar' ; document.FORMULARIO.submit() } else { alert ('Aguarde submissão') }\">" ?>
    </div>
</div>
<br>
</form>

</div>

<?php

if ($_POST["pesquisar"])
    $btnacao = trim($_POST["btnacao"]);

if ($btnacao == 'pesquisar') {
    $data_inicial = $_POST['data_inicial'];
    if (strlen($_POST['data_inicial']) > 0)
        $data_inicial = $_POST['data_inicial'];
    $data_final = $_POST['data_final'];
    if (strlen($_POST['data_final']) > 0)
        $data_final = $_POST['data_final'];

    $data_inicialx = str_replace(" ", "", $data_inicial);
    $data_inicialx = str_replace("-", "", $data_inicialx);
    $data_inicialx = str_replace("/", "", $data_inicialx);
    $data_inicialx = str_replace(".", "", $data_inicialx);

    $data_finalx = str_replace(" ", "", $data_final);
    $data_finalx = str_replace("-", "", $data_finalx);
    $data_finalx = str_replace("/", "", $data_finalx);
    $data_finalx = str_replace(".", "", $data_finalx);

    if (strlen($data_inicialx) == 6)
        $data_inicialx = substr($data_inicialx, 0, 4) . "20" . substr($data_inicialx, 4, 2);
    if (strlen($data_finalx) == 6)
        $data_finalx = substr($data_finalx, 0, 4) . "20" . substr($data_finalx, 4, 2);

    if (strlen($data_inicialx) > 0)
        $data_inicialx = substr($data_inicialx, 0, 2) . "/" . substr($data_inicialx, 2, 2) . "/" . substr($data_inicialx, 4, 4);
    if (strlen($data_finalx) > 0)
        $data_finalx = substr($data_finalx, 0, 2) . "/" . substr($data_finalx, 2, 2) . "/" . substr($data_finalx, 4, 4);

    if (strlen($data_inicialx) < 8)
        $data_inicialx = date("d/m/Y");
    $data_inicialx = substr($data_inicialx, 6, 4) . "-" . substr($data_inicialx, 3, 2) . "-" . substr($data_inicialx, 0, 2);

    if (strlen($data_finalx) < 10)
        $data_finalx = date("d/m/Y");
    $data_finalx = substr($data_finalx, 6, 4) . "-" . substr($data_finalx, 3, 2) . "-" . substr($data_finalx, 0, 2);

    $sql = "SELECT count(tbl_posto.cnpj||' '||tbl_posto.nome) as posto,
					sum(tbl_extrato.mao_de_obra) as mao_de_obra,
					sum(tbl_extrato.avulso) as avulso,
					sum(tbl_extrato.total) as total
			FROM tbl_extrato
			JOIN tbl_posto USING(posto)
			JOIN tbl_posto_fabrica USING(posto)
			LEFT join tbl_banco on tbl_posto_fabrica.banco = tbl_banco.codigo
			WHERE tbl_extrato.fabrica     = $login_fabrica 
			AND tbl_posto_fabrica.fabrica = $login_fabrica
			AND tbl_extrato.data_geracao  BETWEEN '$data_inicialx 00:00:00' AND '$data_finalx 23:59:59'
			AND tbl_extrato.liberado      NOTNULL
			AND tbl_extrato.posto         NOT IN ('6359','14301','20321')";
    $res = pg_exec($con, $sql);

    $qtde_postos = trim(pg_result($res, 0, posto));
    $total_mao_de_obra = trim(pg_result($res, 0, mao_de_obra));
    $total_avulso = trim(pg_result($res, 0, avulso));
    $total_geral = trim(pg_result($res, 0, total));

    # HD 23195
    $sql = "SELECT COUNT(tbl_os_extra.os) AS qtde_os
			FROM tbl_extrato
			JOIN tbl_os_extra USING(extrato)
			WHERE tbl_extrato.fabrica     = $login_fabrica 
			AND tbl_extrato.data_geracao BETWEEN '$data_inicialx 00:00:00' AND '$data_finalx 23:59:59'
			AND tbl_extrato.liberado NOTNULL
			AND tbl_extrato.posto NOT IN ('6359','14301','20321')";
    $res = pg_exec($con, $sql);
    $qtde_os_total = trim(pg_result($res, 0, qtde_os));

    $sql = "SELECT tbl_posto.cnpj,
					tbl_posto.nome as posto,
					tbl_extrato.mao_de_obra,
					tbl_extrato.avulso,
					tbl_extrato.total,
					tbl_banco.nome         AS banco,
					COUNT(tbl_os_extra.os) AS qtde_os
			FROM tbl_extrato
			LEFT JOIN tbl_os_extra USING(extrato)
			JOIN tbl_posto using(posto)
			JOIN tbl_posto_fabrica using(posto)
			LEFT JOIN tbl_banco on tbl_posto_fabrica.banco = tbl_banco.codigo
			WHERE tbl_extrato.fabrica     = $login_fabrica 
			AND tbl_posto_fabrica.fabrica = $login_fabrica
			AND tbl_extrato.data_geracao BETWEEN '$data_inicialx 00:00:00' AND '$data_finalx 23:59:59'
			AND tbl_extrato.liberado NOTNULL
			AND tbl_extrato.posto NOT IN ('6359','14301','20321')
			GROUP BY tbl_posto.cnpj,
					tbl_posto.nome,
					tbl_extrato.mao_de_obra,
					tbl_extrato.avulso,
					tbl_extrato.total,
					tbl_banco.nome
			ORDER BY tbl_posto.nome;";
    $res = pg_exec($con, $sql);

    if (pg_numrows($res) > 0) {

        $data = date("d-m-Y");
        
        for ($i = 0; $i < pg_numrows($res); $i++) {
            $posto = trim(pg_result($res, $i, cnpj)) . ' - ' . trim(pg_result($res, $i, posto));
            $mao_de_obra = trim(pg_result($res, $i, mao_de_obra));
            $avulso = trim(pg_result($res, $i, avulso));
            $total = trim(pg_result($res, $i, total));
            $banco = trim(pg_result($res, $i, banco));
            $qtde_os = trim(pg_result($res, $i, qtde_os));

            $porcentagem = ($qtde_os / $qtde_os_total) * 100;
            $porcentagem = number_format($porcentagem, 2, ',', '.');

            if ($i == 0) {                
                echo "<table  style='margin: 0 auto' class='table table-striped table-bordered table-large'>";

                echo "<tr class = 'titulo_coluna' >";
                echo "<th align='left' nowrap>Posto</th>";
                echo "<th align='left' nowrap>Garantia</th>";
                echo "<th align='left' nowrap>Lançamentos</th>";
                echo "<th align='left' nowrap>Saldo</th>";
                echo "<th align='left' nowrap>Banco</th>";
                echo "<th align='left' nowrap>Qtde OS</th>"; # HD 23195
                echo "<th align='left' nowrap>% OS</th>";    # HD 23195
                echo "</tr>";
            }

            echo "<tr class = 'table_line'>";

            if (strlen($posto) > 40)
                $posto = substr($posto, 0, 39);


            $mao_de_obra = number_format($mao_de_obra, 2, ',', '.');
            $avulso = number_format($avulso, 2, ',', '.');
            $total = number_format($total, 2, ',', '.');
            
            echo "<td align='left' nowrap>$posto</td>";
            echo "<td align='right' nowrap>$mao_de_obra</td>";
            echo "<td align='right' nowrap>$avulso</td>";
            echo "<td align='right' nowrap>$total</td>";
            echo "<td align='left' nowrap>$banco</td>\n";
            echo "<td align='right' nowrap>$qtde_os</td>\n";
            echo "<td align='right' nowrap>$porcentagem</td>\n";
            echo "</tr>";
        }
        echo "</table>";

        $total_mao_de_obra = number_format($total_mao_de_obra, 2, ',', '.');
        $total_avulso = number_format($total_avulso, 2, ',', '.');
        $total_geral = number_format($total_geral, 2, ',', '.');
        $qtde_os_total = number_format($qtde_os_total, 0, ',', '.');

        echo "<BR><table width='700' align='center' border='1' cellspacing='2'>";
        echo "<tr class = 'menu_top'>";
        echo "<td align='center' nowrap rowspan=2>TOTAL GERAL</td>";
        echo "<td align='center' nowrap>Posto</td>";
        echo "<td align='center' nowrap>Garantia</td>";
        echo "<td align='center' nowrap>Lançamentos</td>";
        echo "<td align='center' nowrap>Saldo</td>";
        echo "<td align='center' nowrap>Total OS</td>";
        echo "</tr>";
        echo "<tr class = table_line>";
        echo "<td align='right' nowrap>$qtde_postos</td>";
        echo "<td align='right' nowrap>$total_mao_de_obra</td>";
        echo "<td align='right' nowrap>$total_avulso</td>";
        echo "<td align='right' nowrap>$total_geral</td>";
        echo "<td align='right' nowrap>$qtde_os_total</td>"; # HD 23195
        echo "</tr>";
        echo "</table>";
        
        ?>
        <BR>
        <div class="container">
            <div class="row tac">
                <div class="span3"></div>
                <div class="span3 tar">
                    <div id='gerar_excel' class="btn_excel" style="margin-top:5px;margin-bottom:20px;">
                        <span><img src='http://posvenda.telecontrol.com.br/assist/admin/imagens/excel.png' /></span>
                        <input type="hidden" id="jsonPOST" name="jsonPOST" value='<?php echo $jsonPOST; ?>'> 
                        <span class="txt">Gerar Arquivo Excel</span>
                    </div>

                </div>
                <div class="span2 tac">
                    <input type="button" class="btn" value="Imprimir" onclick="javascript: window.open('movimentacao_postos_lenoxx_print.php?btnacao=pesquisar&inicio=<?php echo $data_inicial . '&fim=' . $data_final ?>', 'printmov', 'toolbar=no,location=no,directories=no,status=no,scrollbars=yes,menubar=yes,resizable=yes,width=700,height=480')" >					
                </div>
            </div>
        </div>

        <?php
    } else
        echo "<center>NENHUM EXTRATO ENCONTRADO</center>";
}

include "rodape.php";
?>