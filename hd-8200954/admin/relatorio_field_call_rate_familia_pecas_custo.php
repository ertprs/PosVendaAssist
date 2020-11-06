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

if($login_fabrica == 24){
    $matriz_filial = $_REQUEST['matriz_filial'];
    if(strlen($matriz_filial)>0){
        $cond_matriz_filial = " AND substr(tbl_os.serie,length(tbl_os.serie) - 1, 2) = '$matriz_filial' ";
    }
}

$sql = "SELECT descricao FROM tbl_familia WHERE familia = $familia";

$res = pg_query($con,$sql);
$descricao_produto = pg_fetch_result($res,0,descricao);

$aux_data_inicial = substr($data_inicial,8,2) . "/" . substr($data_inicial,5,2) . "/" . substr($data_inicial,0,4);
$aux_data_final   = substr($data_final,8,2)   . "/" . substr($data_final,5,2)   . "/" . substr($data_final,0,4);

$sql_link = "SELECT fabrica FROM tbl_defeito WHERE fabrica = $login_fabrica";
$res_link = pg_query($con, $sql_link);
if(pg_num_rows($res_link) > 0){
    $tem_link = "true";
}

$title = "RELATÓRIO DE QUEBRA DE PEÇAS x CUSTO";

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

<script>
function AbreDefeito(peca,data_inicial,data_final,linha,estado,matriz_filial){
    matriz_filial = matriz_filial || '0';
	janela = window.open("relatorio_field_call_rate_defeitos.php?peca=" + peca + "&data_inicial=" + data_inicial + "&data_final=" + data_final + "&linha=" + linha + "&estado=" + estado + "&matriz_filial="+matriz_filial ,"peca",'scrollbars=yes,width=750,height=200,top=315,left=0');
	janela.focus();
}
function AbreSerie(produto,peca,data_inicial,data_final,linha,estado,familia,matriz_filial){
    matriz_filial = matriz_filial || '0';
	janela = window.open("relatorio_field_call_rate_serie.php?produto="+produto+"&peca=" + peca + "&data_inicial=" + data_inicial + "&data_final=" + data_final + "&linha=" + linha + "&familia=" +familia+ "&estado=" + estado +"&matriz_filial="+matriz_filial ,"peca",'scrollbars=yes,width=750,height=200,top=315,left=0');
	janela.focus();
}
</script>
</HEAD>

<BODY>
<?

echo "<div align='center'><b>$title </b>";
echo "<span class='Conteudo'><br>De $aux_data_inicial até $aux_data_final</B>";
echo " Família: <b>$descricao_produto </b></span></div>";


$cond_1 = " 1 = 1 ";
$cond_2 = " 1 = 1 ";
$cond_3 = " 1 = 1 ";
$cond_4 = " 1 = 1 ";
$cond_5 = " 1 = 1 ";

if (strlen ($estado)  > 0) $cond_2    = " tbl_posto.estado = '$estado' ";
if (strlen ($posto)   > 0) $cond_3    = " tbl_posto.posto = $posto ";
if (strlen ($produto) > 0) $cond_5    = " tbl_os.produto = $produto ";
if (strlen ($familia) > 0) $cond_6    = " AND tbl_produto.familia = $familia ";

if($login_fabrica   == 20)
    $tipo_data = " tbl_extrato_extra.exportado ";
else
    $tipo_data = " tbl_extrato.data_geracao ";

$sql = "SELECT extrato
		INTO TEMP tmp_extrato_relatorio
		FROM tbl_extrato
		JOIN tbl_extrato_extra USING(extrato)
		WHERE tbl_extrato.fabrica = $login_fabrica
        AND   $tipo_data BETWEEN '$data_inicial 00:00:00' AND '$data_final 23:59:59' ";
if ($login_fabrica == 14 OR $login_fabrica == 129) $sql .= "AND   tbl_extrato.liberado IS NOT NULL ";
$res = pg_query($con, $sql);

    $sql = "
            SELECT
                tbl_os.os,
                tbl_os.mao_de_obra,
                tbl_os.produto
                INTO TEMP tmp_familia_peca_os
            FROM tbl_os
                JOIN tbl_os_extra ON tbl_os.os = tbl_os_extra.os and tbl_os_extra.i_fabrica = $login_fabrica
                JOIN tbl_posto ON tbl_os.posto = tbl_posto.posto
                JOIN tbl_extrato ON tbl_extrato.extrato = tbl_os_extra.extrato AND tbl_extrato.fabrica = $login_fabrica
                JOIN tbl_produto    ON tbl_produto.produto = tbl_os.produto AND tbl_produto.fabrica_i = $login_fabrica
            WHERE (tbl_os.status_os_ultimo NOT IN (13,15) OR tbl_os.status_os_ultimo IS NULL)
                AND tbl_os.excluida IS NOT TRUE
                AND tbl_os.fabrica = $login_fabrica
                AND tbl_extrato.data_geracao BETWEEN '$data_inicial 00:00:00' AND '$data_final 23:59:59'
                AND tbl_os_extra.extrato IN ( SELECT * FROM tmp_extrato_relatorio )
                AND tbl_posto.pais='BR'
                AND $cond_2
                AND $cond_3
                AND $cond_4
                AND $cond_5
                $cond_6
                $cond_matriz_filial;

            SELECT SUM(mao_de_obra) AS total_mao_de_obra FROM tmp_familia_peca_os;";
    $res = pg_query($con, $sql);


    if(pg_num_rows($res) > 0){
        $total_mao_de_obra = pg_fetch_result($res,0,'total_mao_de_obra');

        echo "<table border='1' cellpadding='2' cellspacing='0' width='600' style='border-collapse: collapse' bordercolor='#d2e4fc'  align='center' width='500'>";
            echo "<tr class='Titulo' height='25'>";
                echo "<td colspan='2'  background='imagens_admin/azul.gif'><b>CUSTO TOTAL COM A FAMÍLIA: $descricao_produto</b></td>";
            echo "</tr>";
            echo "<tr bgcolor='#F1F4FA' class='Exibe'>
                    <td align='center'>TOTAL DE MÃO DE OBRA</td>
                    <td align='right'><b>R$ ".number_format($total_mao_de_obra,2,",",".")."</td>
                  </tr>";
    }

    $sql = "SELECT
                COUNT(tbl_os.os) AS qtde_os,
                SUM(tbl_os.mao_de_obra) as mao_de_obra,
                SUM(tbl_os_item.preco) as pecas,
                SUM(tbl_os_item.qtde) AS qtde,
                SUM(tbl_os_item.custo_peca) as custo_peca,
                tbl_peca.peca,
                tbl_peca.referencia,
                tbl_peca.descricao
            FROM tbl_os
                JOIN tbl_os_produto ON tbl_os_produto.os = tbl_os.os
                JOIN tbl_os_item    ON tbl_os_item.os_produto = tbl_os_produto.os_produto
                JOIN tbl_peca       ON tbl_peca.peca = tbl_os_item.peca
		        JOIN tmp_familia_peca_os ON tbl_os.os = tmp_familia_peca_os.os
                JOIN tbl_produto    ON tbl_produto.produto = tbl_os.produto AND tbl_produto.fabrica_i = $login_fabrica
            WHERE tbl_os.fabrica = $login_fabrica
		        AND tbl_os_item.fabrica_i = $login_fabrica
                AND tbl_peca.fabrica = $login_fabrica
                $cond_6
            GROUP BY tbl_peca.peca,  tbl_peca.referencia, tbl_peca.descricao
            ORDER BY qtde DESC;";
            // echo nl2br($sql);
            // exit();
    $res = pg_query($con, $sql);

    if(pg_num_rows($res) > 0){

        for ($x = 0; $x < pg_num_rows($res); $x++) {
            $pecas      = pg_fetch_result($res,$x,'pecas');
            $ocorrencia = @pg_fetch_result($res,$x,'qtde');

            if($login_fabrica==1)$pecas = pg_fetch_result($res,$x,custo_peca);
            $total_pecas     = $total_pecas + $pecas ;
            if($login_fabrica == 20) $total_qtde_peca = $total_qtde_peca + $ocorrencia;
        }

        $total_final = $total_pecas + $total_mao_de_obra;
        echo "<tr bgcolor='#F1F4FA' class='Exibe'><td align='center'>TOTAL DE CUSTO DE PEÇAS</td> <td align='right'><b>R$ ".number_format($total_pecas,2,",",".")."</td></tr>";
        echo "<tr bgcolor='#d9e2ef' class='Exibe'><td align='center'>TOTAL</td><td align='right'><b>R$ ".number_format($total_final,2,",",".")."</td></tr>";
        echo "</table><br>";

        echo  "<table border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#d2e4fc'  align='center' width='600'>";
        echo  "<TR class='Titulo'  height='25'>";
        echo  "<TD background='imagens_admin/azul.gif'><b>Referência</b></TD>";
        echo  "<TD background='imagens_admin/azul.gif'><b>Peça</b></TD>";
        echo  "<TD background='imagens_admin/azul.gif' ><b>Ocorrências de Peças</b></TD>";
        if($login_fabrica == 20) echo  "<TD><b>Qtde Lançadas</b></TD>";
        if($login_fabrica == 20) echo  "<TD><b>% peças</b></TD>";
        echo  "<TD background='imagens_admin/azul.gif'><b>Custo</b></TD>";
        echo  "<TD background='imagens_admin/azul.gif'><b>%</b></TD>";
        echo  "<TD background='imagens_admin/azul.gif'><b>Série</b></TD>";
        echo  "</TR>";



        for($i=0; $i<pg_num_rows($res); $i++){
            $peca       = pg_fetch_result($res,$i,'peca');
            $referencia = pg_fetch_result($res,$i,'referencia');
            $descricao  = pg_fetch_result($res,$i,'descricao');
            $ocorrencia = pg_fetch_result($res,$i,'qtde');
            if($login_fabrica == 20) $qtde_os    = pg_fetch_result($res,$i,'qtde_os');
            $pecas      = pg_fetch_result($res,$i,'pecas');
            if($login_fabrica==1)$pecas = pg_fetch_result($res,$i,'custo_peca');

            if ($total_pecas > 0) {
                $porcentagem = (($pecas * 100) / $total_pecas);
            }

            if($login_fabrica == 20 AND $ocorrencia > 0){
                $porcentagem_pecas = (($ocorrencia * 100) / $total_qtde_peca);
            }

            if($cor=="#F1F4FA")$cor = '#F7F5F0';
            else               $cor = '#F1F4FA';

            echo "<TR class='Conteudo' bgcolor='$cor'>";
            if($tem_link == "true"){
                echo "  <TD><a href='javascript:AbreDefeito(\"$peca\",\"$data_inicial\",\"$data_final\",\"$linha\",\"$estado\",\"$matriz_filial\")'>$referencia</a></TD>";            
            }else{
                echo "<td>$referencia</td>";
            }
            echo "	<TD align='left'>$descricao</TD>";
            if($login_fabrica == 20) echo "	<TD align='center'>$qtde_os </TD>";
            echo "	<TD align='center'>$ocorrencia</TD>";
            if($login_fabrica == 20) echo "	<TD >". number_format($porcentagem_pecas,2,",",".") ."%</TD>";
            echo "	<TD align='right' width='75'>R$ ". number_format($pecas,2,",",".") ."</TD>";
            echo "	<TD >". number_format($porcentagem,2,",",".") ."%</TD>";
            echo "	<TD><a href='javascript:AbreSerie(\"$produto\",\"$peca\",\"$data_inicial\",\"$data_final\",\"$linha\",\"$estado\",\"$familia\",\"$matriz_filial\")'>Detalhada</a></TD>";
            echo "</TR>";
            $total = $total + $pecas;
        }
        echo "<tr class='Conteudo' bgcolor='#d9e2ef'>";
            echo "<td colspan='3'><font size='2'><b><CENTER>VALOR TOTAL DE CUSTO PEÇA </b></td>";
            echo "<td colspan='5'><font size='2' color='009900'><b>R$". number_format($total_pecas,2,",",".") ." </b></td>";
            echo "</tr>";
        echo "</table>";
    }

?>


</BODY>
</HTML>
