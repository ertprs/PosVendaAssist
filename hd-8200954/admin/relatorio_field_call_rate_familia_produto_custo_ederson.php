<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="financeiro";
include 'autentica_admin.php';

$data_inicial = $_REQUEST['data_inicial'];
$data_final   = $_REQUEST['data_final'];
$produto      = $_REQUEST['produto'];
$estado       = $_REQUEST['estado'];
$familia      = $_REQUEST['familia'];
$posto        = $_REQUEST['posto'];

$sql = "SELECT descricao FROM tbl_familia WHERE familia = $familia";

$res = pg_query($con,$sql);
$descricao_produto = pg_fetch_result($res,0,descricao);

$aux_data_inicial = substr($data_inicial,8,2) . "/" . substr($data_inicial,5,2) . "/" . substr($data_inicial,0,4);
$aux_data_final   = substr($data_final,8,2)   . "/" . substr($data_final,5,2)   . "/" . substr($data_final,0,4);

$title = "RELATÓRIO DE QUEBRA DE PRODUTO x CUSTO";
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">
<HTML>
<HEAD>
<TITLE><? echo $title; ?></TITLE>
<META NAME="Generator" CONTENT="EditPlus">
<META NAME="Author" CONTENT="">
<META NAME="Keywords" CONTENT="">
<META NAME="Description" CONTENT="">

<style>
.Titulo {
	text-align: center;
	font-family: Arial;
	font-size: 10px;
	font-weight: bold;
	color: #FFFFFF;
	background-color: #485989;
}
.Conteudo {
	font-family: Arial;
	font-size: 10px;
	font-weight: normal;
}
.ConteudoBranco {
	font-family: Arial;
	font-size: 9px;
	color:#FFFFFF;
	font-weight: normal;
}
.Mes{
	font-size: 9px;
}
.Caixa{
	BORDER-RIGHT: #6699CC 1px solid; 
	BORDER-TOP: #6699CC 1px solid; 
	FONT: 8pt Arial ;
	BORDER-LEFT: #6699CC 1px solid; 
	BORDER-BOTTOM: #6699CC 1px solid; 
	BACKGROUND-COLOR: #FFFFFF;
}
.Exibe{
	font-family: Arial, Helvetica, sans-serif;
	font-size:11px;
	font-weight: none;
	color: #000000;
	text-align: center;
}
.Erro{
	BORDER-RIGHT: #990000 1px solid; 
	BORDER-TOP: #990000 1px solid; 
	FONT: 10pt Arial ;
	COLOR: #ffffff;
	BORDER-LEFT: #990000 1px solid; 
	BORDER-BOTTOM: #990000 1px solid; 
	BACKGROUND-COLOR: #FF0000;
}
</style>


</HEAD>

<BODY>
<?

echo "<div align='center'><b>$title </b>";
echo "<span class='Conteudo'><br>De $aux_data_inicial até $aux_data_final</B>";
echo " Família: <b>$descricao_produto </b></span></div><br />";

$cond_1 = " 1=1 ";
$cond_2 = " 1=1 ";
$cond_3 = " 1=1 ";
$cond_4 = " 1=1 ";

if (strlen ($estado)  > 0) $cond_2    = " tbl_posto.estado = '$estado' ";
if (strlen ($posto)   > 0) $cond_3    = " tbl_posto.posto  = $posto ";

$sql = "SELECT 
            tbl_produto.referencia, 
            tbl_produto.descricao, 
            tbl_produto.familia,  
            COUNT(tbl_os.*) AS qtde, 
            SUM (tbl_os.mao_de_obra) AS mao_de_obra
            INTO TEMP tmp_os_familia_{$familia}
        FROM tbl_os
            JOIN (
                SELECT tbl_os_extra.os ,
                (SELECT status_os FROM tbl_os_status WHERE tbl_os_status.os = tbl_os_extra.os ORDER BY data DESC LIMIT 1) AS status
                FROM tbl_os_extra
                    JOIN tbl_extrato ON tbl_os_extra.extrato = tbl_extrato.extrato
                    JOIN tbl_extrato_extra ON tbl_extrato_extra.extrato = tbl_extrato.extrato
                    JOIN tbl_posto ON tbl_posto.posto = tbl_extrato.posto
                WHERE tbl_extrato.fabrica = 24
                    AND tbl_extrato.data_geracao BETWEEN '{$data_inicial} 00:00:00' AND '{$data_final} 23:59:59'
                    AND tbl_posto.pais='BR'
        ) fcr ON tbl_os.os = fcr.os
        JOIN tbl_produto ON tbl_os.produto = tbl_produto.produto /*AND tbl_produto.familia = 578*/ AND tbl_produto.fabrica_i = 24
        JOIN tbl_familia ON tbl_produto.familia = tbl_familia.familia
        JOIN tbl_posto ON tbl_posto.posto = tbl_os.posto
        WHERE (fcr.status NOT IN (13,15) OR fcr.status IS NULL)
        AND tbl_os.excluida IS NOT TRUE
        /*AND tbl_familia.familia = 578*/
        AND $cond_1
        AND $cond_2
        AND $cond_3
        AND $cond_4
        GROUP BY tbl_produto.referencia, tbl_produto.descricao,  tbl_produto.familia;
        
        SELECT * FROM tmp_os_familia_{$familia} WHERE familia = {$familia};";
$res = pg_query($con, $sql);
//echo nl2br($sql)."<br>";


$sql = "SELECT SUM(mao_de_obra) AS total_mao_de_obra FROM tmp_os_familia_{$familia} WHERE familia = {$familia};";
$res_mao_de_obra = pg_query($con, $sql);
if(pg_num_rows($res_mao_de_obra) > 0){
	$total_mao_de_obra = pg_fetch_result($res_mao_de_obra,0,'total_mao_de_obra');
	
	echo  "<table border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#d2e4fc'  align='center' width='500'>";
        echo  "<tr class='Titulo' height='25'>";
            echo  "<td colspan='2' ><b>CUSTO TOTAL COM A FAMÍLIA: $descricao_produto</b></td>";
        echo "</tr>";
	echo "<tr bgcolor='#F1F4FA' class='Exibe'>
            <td align='center'>TOTAL DE MÃO DE OBRA</td>
            <td align='right'><b>R$ ".number_format($total_mao_de_obra,2,",",".")."</td>
          </tr>";
}

if(pg_num_rows($res) > 0){
    $total_mao_de_obra = null;
    $ocorrencia        = null;

	for ($x = 0; $x < pg_num_rows($res); $x++) {
		$referencia     = pg_fetch_result($res,$x,'referencia');
        $descricao      = pg_fetch_result($res,$x,'descricao');
        $qtde           = pg_fetch_result($res,$x,'qtde');
        $mao_de_obra    = pg_fetch_result($res,$x,'mao_de_obra');

		$ocorrencia += $qtde;
        $total_mao_de_obra += $mao_de_obra;
	}
    
    $total_final = $total_pecas + $total_mao_de_obra;
	echo "<tr bgcolor='#F1F4FA' class='Exibe'><td align='center'>TOTAL DE CUSTO DE PEÇAS</td> <td align='right'><b>R$ ".number_format($total_pecas,2,",",".")."</td></tr>";
	echo "<tr bgcolor='#d9e2ef' class='Exibe'><td align='center'>TOTAL</td><td align='right'><b>R$ ".number_format($total_final,2,",",".")."</td></tr>";
	echo "</table><br>";

	echo  "<table border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#d2e4fc'  align='center' width='600'>";
        echo  "<tr class='Titulo'  height='25'>";
            echo  "<td>Referência</td>";
            echo  "<td>Produto</TD>";
            echo  "<td>Ocorrência</td>";
            echo  "<td>R$ M.Obra</td>";
            echo  "<td>%</td>";
            //echo  "<td>Série</td>";
        echo  "</tr>";


	
        for($i=0; $i < pg_num_rows($res); $i++){
            $referencia     = pg_fetch_result($res,$i,'referencia');
            $descricao      = pg_fetch_result($res,$i,'descricao');
            //$ocorrencia     = pg_fetch_result($res,$i,'qtde');
            $qtde           = pg_fetch_result($res,$i,'qtde');
            $mao_de_obra    = pg_fetch_result($res,$i,'mao_de_obra');

            if ($total_mao_de_obra > 0) {
                $porcentagem = (($mao_de_obra * 100) / $total_mao_de_obra);
            }

            $cor = ($cor == "#F1F4FA") ? "#F7F5F0" : "#F1F4FA";
            echo "<tr class='Conteudo' bgcolor='$cor'>";
                //echo "<td><a href='javascript:AbreDefeito(\"$peca\",\"$data_inicial\",\"$data_final\",\"$linha\",\"$estado\")'>$referencia</a></td>";
                echo "<td><a href='relatorio_field_call_rate_familia_pecas_custo.php?familia=$familia&data_inicial=$aux_data_inicial&data_final=$aux_data_final&estado={$estado}&produto={$produto}' target='_blank' style='text-decoration: none; font-weight: bold; color: #000;'>$referencia</a></td>";
                echo "<td align='left'>$descricao</td>";
                echo "<td align='left'>$qtde</td>";
                echo "<td align='right' width='75'>R$ ". number_format($mao_de_obra,2,",",".") ."</td>";
                echo "<td align='right'>". number_format($porcentagem,2,",",".") ." %</td>";
                //echo "<td><a href='javascript:AbreSerie(\"$peca\",\"$data_inicial\",\"$data_final\",\"$linha\",\"$estado\")'>#série</a></td>";
            echo "</tr>";
            $total = $total + $pecas;
        }
        echo "<tr class='Conteudo' bgcolor='#d9e2ef'>";
            echo "<td colspan='2'><b><CENTER>VALOR TOTAL DE CUSTO PEÇA </b></td>";
            echo "<td align='left'><b>{$ocorrencia}</b></td>";
            echo "<td align='right'><b>R$ ". number_format($total_mao_de_obra,2,",",".") ." </b></td>";
            echo "<td>&nbsp;</td>";
        echo "</tr>";
	echo "</table>";
} ?>
</body>
</html>
