<?
include "../admin/dbconfig.php";
include "../admin/includes/dbconnect-inc.php";

$estado = $_POST['estado'];
$linha  = $_POST['linha'];
?>

<html>

<head>
<title>Consulta de Postos Autorizados</title>
<style type="text/css">
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
</style>
</head>

<body>

<FORM name="frm_consulta" METHOD="POST" ACTION="<? echo $PHP_SELF ?>">

<TABLE width="400" align="center" border="0" cellspacing="0" cellpadding="2">
    <TR class="menu_top">
        <TD><b>Consulta</b></TD>
    </TR>
    <TR class='table_line'>
        <TD align="center">Linha</TD>
    </TR>
    <TR class='table_line'>
        <TD align="center">
            <?
            $sql = "SELECT  *
                    FROM    tbl_linha
                    WHERE   tbl_linha.fabrica = 2
                    ORDER BY tbl_linha.nome;";
            $res = pg_exec ($con,$sql);
            
            if (pg_numrows($res) > 0) {
                echo "<select name='linha' size='1'>\n";
                echo "              <option value=''>ESCOLHA</option>\n";
                
                for ($x = 0 ; $x < pg_numrows($res) ; $x++){
                    $aux_linha = trim(pg_result($res,$x,linha));
                    $aux_nome  = trim(pg_result($res,$x,nome));
                    
                    echo "              <option value='$aux_linha'"; 
                    if ($linha == $aux_linha){
                        echo " SELECTED "; 
                        $mostraMsgLinha = "<br> da LINHA $aux_nome";
                    }
                    echo ">$aux_nome</option>\n";
                }
                echo "          </select>\n";
            }
            ?>
        </TD>
    </TR>
    <TR class='table_line'>
        <TD align="center">Por região</TD>
    </TR>
    <TR class="table_line">
        <td align="center">
            <select name="estado" size="1">
                <option value="" <? if (strlen($estado) == 0) echo "selected"; ?>>TODOS OS ESTADOS</option>
                <option value="AC" <? if ($estado == "AC") echo "selected"; ?>>AC - Acre</option>
                <option value="AL" <? if ($estado == "AL") echo "selected"; ?>>AL - Alagoas</option>
                <option value="AM" <? if ($estado == "AM") echo "selected"; ?>>AM - Amazonas</option>
                <option value="AP" <? if ($estado == "AP") echo "selected"; ?>>AP - Amapá</option>
                <option value="BA" <? if ($estado == "BA") echo "selected"; ?>>BA - Bahia</option>
                <option value="CE" <? if ($estado == "CE") echo "selected"; ?>>CE - Ceará</option>
                <option value="DF" <? if ($estado == "DF") echo "selected"; ?>>DF - Distrito Federal</option>
                <option value="ES" <? if ($estado == "ES") echo "selected"; ?>>ES - Espírito Santo</option>
                <option value="GO" <? if ($estado == "GO") echo "selected"; ?>>GO - Goiás</option>
                <option value="MA" <? if ($estado == "MA") echo "selected"; ?>>MA - Maranhão</option>
                <option value="MG" <? if ($estado == "MG") echo "selected"; ?>>MG - Minas Gerais</option>
                <option value="MS" <? if ($estado == "MS") echo "selected"; ?>>MS - Mato Grosso do Sul</option>
                <option value="MT" <? if ($estado == "MT") echo "selected"; ?>>MT - Mato Grosso</option>
                <option value="PA" <? if ($estado == "PA") echo "selected"; ?>>PA - Pará</option>
                <option value="PB" <? if ($estado == "PB") echo "selected"; ?>>PB - Paraíba</option>
                <option value="PE" <? if ($estado == "PE") echo "selected"; ?>>PE - Pernambuco</option>
                <option value="PI" <? if ($estado == "PI") echo "selected"; ?>>PI - Piauí</option>
                <option value="PR" <? if ($estado == "PR") echo "selected"; ?>>PR - Paraná</option>
                <option value="RJ" <? if ($estado == "RJ") echo "selected"; ?>>RJ - Rio de Janeiro</option>
                <option value="RN" <? if ($estado == "RN") echo "selected"; ?>>RN - Rio Grande do Norte</option>
                <option value="RO" <? if ($estado == "RO") echo "selected"; ?>>RO - Rondônia</option>
                <option value="RR" <? if ($estado == "RR") echo "selected"; ?>>RR - Roraima</option>
                <option value="RS" <? if ($estado == "RS") echo "selected"; ?>>RS - Rio Grande do Sul</option>
                <option value="SC" <? if ($estado == "SC") echo "selected"; ?>>SC - Santa Catarina</option>
                <option value="SE" <? if ($estado == "SE") echo "selected"; ?>>SE - Sergipe</option>
                <option value="SP" <? if ($estado == "SP") echo "selected"; ?>>SP - São Paulo</option>
                <option value="TO" <? if ($estado == "TO") echo "selected"; ?>>TO - Tocantins</option>
            </select>
        </td>
    </TR>
    <TR class="table_line">
        <TD>
            <input type='hidden' name='btn_acao' value=''>
            <IMG src="../admin/imagens_admin/btn_pesquisar_400.gif" onclick="javascript: if ( document.frm_consulta.btn_acao.value == '' ) { document.frm_consulta.btn_acao.value='mostrar'; document.frm_consulta.submit() ; } else { alert ('Aguarde submissão da OS...'); }" style="cursor:pointer " alt='Clique AQUI para pesquisar'>
        </TD>
    </TR>
</TABLE>

</FORM>


<?
if ($btn_acao == 'mostrar') {

    if (strlen($estado) == 0 AND strlen($linha) == 0) {

        echo "<table border='0' cellspacing='2' align='center' bgcolor='#FF0000' width = '600'><tr>";
        echo "<td valign='middle' align='center'><font face='Verdana, Tahoma, Arial' size='2' color='#FFFFFF'><b>Selecione pelo menos um campo.</b></font></td>";
        echo "</tr></table>";

    } else {

        $sql =  "SELECT tbl_posto_fabrica.codigo_posto, tbl_posto.nome AS nome_posto, tbl_posto.cidade, tbl_posto.estado
                    FROM tbl_posto
                    JOIN tbl_posto_fabrica using(posto) ";

        if (strlen($linha) > 0) $sql .= "JOIN tbl_posto_linha using(posto) JOIN tbl_linha using(linha) ";

        $sql .= "WHERE tbl_posto_fabrica.fabrica = 2
                    AND tbl_posto_fabrica.credenciamento = 'CREDENCIADO' ";

        if (strlen($estado) > 0) $sql .= "AND tbl_posto.estado = '$estado' ";
        if (strlen($linha) > 0) $sql .= "AND tbl_posto_linha.linha = $linha ";

        $sql .= "ORDER BY tbl_posto.estado ASC, tbl_posto.cidade ASC, tbl_posto.nome ASC;";
        $res = pg_exec ($con,$sql);
        
        if (pg_numrows($res) == 0) {
            echo "<table border='0' cellspacing='2' align='center' bgcolor='#FF0000' width = '600'><tr>";
            echo "<td valign='middle' align='center'><font face='Verdana, Tahoma, Arial' size='2' color='#FFFFFF'><b>Consulta encontrou ".pg_numrows($res)." posto(s) autorizado(s).</b></font></td>";
            echo "</tr></table>";
        } else {
            echo "<table border='0' width = '600' cellspacing='2' cellpadding='2' align='center'>\n";
            echo "<tr class='menu_top'>\n<td>Posto</td>\n<td>Cidade / Estado</td>\n</tr>\n";
            for ($i=0; $i<pg_numrows($res); $i++) {
                echo "<tr class='table_line'>\n";
                echo "<td nowrap>".pg_result ($res,$i,codigo_posto)." - ".pg_result ($res,$i,nome_posto)."</td>\n";
                echo "<td nowrap>".pg_result ($res,$i,cidade)." / ".pg_result ($res,$i,estado)."</td>\n";
                echo "</tr>\n";
            }
            echo "</table>\n\n<br>\n\n";
            echo "<table border='0' cellspacing='2' align='center' bgcolor='#FF0000' width = '600'>\n<tr>\n";
            echo "<td valign='middle' align='center'><font face='Verdana, Tahoma, Arial' size='2' color='#FFFFFF'><b>Consulta encontrou ".pg_numrows($res)." posto(s) autorizado(s).</b></font></td>";
            echo "</tr>\n</table>\n";
        }
    }
}
?>

</body>

</html>