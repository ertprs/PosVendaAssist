<?php

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

$login_fabrica = "(10,51,81)";
$login_posto = 4311;

include "menu.php";

?>

<style>
.Pesquisa{
    font-family: Verdana, Arial, Helvetica, sans-serif;
    font-size: 10px;
    font-weight: none;
    color: #333333;
    border:#485989 1px solid;
    background-color: #EFF4FA;
}

.Pesquisa caption {
    font-size:14px;
    font-weight:bold;
    color: #FFFFFF;
    background-color: #596D9B;
    text-align:'left';
    text-transform:uppercase;
    padding:0px 5px;
}

.Pesquisa thead td{
    text-align: center;
    font-size: 12px;
    font-weight: bold;
    color: #FFFFFF;
    background-color: #596D9B;
}

.Pesquisa tbody th{
    font-size: 12px;
    font-weight: none;
    text-align:'left';
    color: #333333;
}
.Pesquisa tbody td{
    font-size: 10px;
    font-weight: none;
    text-align:'left';
    color: #333333;
}

.Pesquisa tfoot td{
    font-size:10px;
    font-weight:bold;
    color: #000000;
    text-align:'left';
    text-transform:uppercase;
    padding:0px 5px;
}

</style>
<p>

<?php

$sql = "SELECT tbl_peca.peca,
               tbl_peca.descricao,
               tbl_peca.referencia,
               tbl_marca.nome as marca,
               tbl_peca.qtde_disponivel_site,
               tbl_peca.qtde_minima_estoque
          FROM tbl_peca
          JOIN tbl_marca ON tbl_marca.marca = tbl_peca.marca
         WHERE qtde_disponivel_site <= qtde_minima_estoque
           AND tbl_peca.fabrica = 10";

$res = pg_query ($con,$sql);

if (@pg_num_rows($res) > 0) {

    echo "<table width='670' border='1' cellspacing='1' cellpadding='5' align='center' rules='all'>";
        echo "<tr height='20' bgcolor='#999999'>";
            echo "<td align='center'><font size='2' face='Geneva, Arial, Helvetica, san-serif'><b>Código</b></font></td>";
            echo "<td align='center'><font size='2' face='Geneva, Arial, Helvetica, san-serif'><b>Refêrencia</b></font></td>";
            echo "<td align='center'><font size='2' face='Geneva, Arial, Helvetica, san-serif'><b>Descrição</b></font></td>";
            echo "<td align='center'><font size='2' face='Geneva, Arial, Helvetica, san-serif'><b>Marca</b></font></td>";
            echo "<td align='center'><font size='2' face='Geneva, Arial, Helvetica, san-serif'><b>Qtd Mínima</b></font></td>";
            echo "<td align='center'><font size='2' face='Geneva, Arial, Helvetica, san-serif'><b>Qtd Disponível</b></font></td>";
        echo "</tr>";

        for ($i = 0; $i < pg_num_rows($res); $i++) {
            
            $cor = ($i % 2 == 0) ? '#F1F4FA' : '#FFFFFF';

            $peca           = trim(pg_fetch_result($res,$i,peca));
            $referencia     = trim(pg_fetch_result($res,$i,referencia));
            $descricao      = trim(pg_fetch_result($res,$i,descricao));
            $marca          = trim(pg_fetch_result($res,$i,marca));
            $qtd_minima     = trim(pg_fetch_result($res,$i,qtde_minima_estoque));
			$qtd_disponivel = trim(pg_fetch_result($res,$i,qtde_disponivel_site));

            echo "<tr bgcolor='$cor'>";
                echo "<td><font size='1' face='Geneva, Arial, Helvetica, san-serif'>$peca</font></td>";
                echo "<td><font size='1' face='Geneva, Arial, Helvetica, san-serif'>$referencia</font></td>";
				echo "<td><font size='1' face='Geneva, Arial, Helvetica, san-serif'>$descricao</font></td>";
                echo "<td><font size='1' face='Geneva, Arial, Helvetica, san-serif'>$marca</font></td>";
                echo "<td><font size='1' face='Geneva, Arial, Helvetica, san-serif'>$qtd_minima</font></td>";
                echo "<td><font size='1' face='Geneva, Arial, Helvetica, san-serif'>$qtd_disponivel</font></td>";
            echo "</tr>";

        }

    echo "</table>";

} else {

    echo "<table width='600' border='0' cellpadding='2' cellspacing='2' align='center'>";
        echo "<tr>";
            echo "<td valign='top' align='center'>";
                echo "<h4>Nenhum pedido encontrado</h4>";
            echo "</td>";
        echo "</tr>";
    echo "</table>";

}

?>

<p>

<? include "rodape.php"; ?>
