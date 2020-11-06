<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

if ($login_e_distribuidor <> 't') {
	header ("Location: new_extrato_posto.php");
	exit;
}

$msg_erro = "";

$layout_menu = "os";
$title = "Adicional de Peças";

include "cabecalho.php";

?>

<p>
<center>
<?
$data = trim($_GET['data']);
$data_inicio = substr ($data,0,8) . "-01";
$sql = "SELECT '$data_inicio'::date - interval '1 month'";
$res = pg_exec ($con,$sql);
$data_inicio = substr (pg_result ($res,0,0),0,10) . " 00:00:00";

$sql = "SELECT '$data_inicio'::date + interval '1 month' - interval '1 day'";
$res = pg_exec ($con,$sql);
$data_final = substr (pg_result ($res,0,0),0,10) . " 23:59:59";


echo "Processando Notas Fiscais em Garantia de ";
$x_data = substr ($data_inicio,8,2) . "/" . substr ($data_inicio,5,2) . "/" . substr ($data_inicio,0,4);
echo $x_data;
echo " a ";
$x_data = substr ($data_final,8,2) . "/" . substr ($data_final,5,2) . "/" . substr ($data_final,0,4);
echo $x_data;
echo "<p>";

$sql = "SELECT  tbl_faturamento.nota_fiscal , 
				tbl_faturamento.cfop, 
				TO_CHAR (tbl_faturamento.emissao,'DD/MM/YYYY') AS emissao, 
				tbl_posto_fabrica.codigo_posto, 
				tbl_os.sua_os, 
				tbl_posto.nome, 
				tbl_peca.referencia, 
				tbl_peca.descricao, 
				tbl_peca.ipi, 
				(SELECT tbl_faturamento_item.preco FROM tbl_faturamento_item WHERE tbl_faturamento.faturamento = tbl_faturamento_item.faturamento AND tbl_os_item.peca = tbl_faturamento_item.peca LIMIT 1) AS preco
		FROM tbl_faturamento
		JOIN tbl_embarque ON tbl_faturamento.embarque = tbl_embarque.embarque
		JOIN tbl_embarque_item ON tbl_embarque.embarque = tbl_embarque_item.embarque
		JOIN tbl_os_item ON tbl_embarque_item.os_item = tbl_os_item.os_item
		JOIN tbl_peca ON tbl_os_item.peca = tbl_peca.peca
		JOIN tbl_os_produto ON tbl_os_item.os_produto = tbl_os_produto.os_produto
		JOIN tbl_os ON tbl_os_produto.os = tbl_os.os
		JOIN tbl_posto ON tbl_faturamento.posto = tbl_posto.posto
		JOIN tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
		WHERE tbl_faturamento.distribuidor = $login_posto
		AND   tbl_faturamento.fabrica = $login_fabrica
		AND   tbl_os.fabrica = $login_fabrica
		AND   tbl_faturamento.emissao BETWEEN '$data_inicio' AND '$data_final'
		AND   tbl_faturamento.cfop ILIKE '59%'
		AND   tbl_faturamento.tipo_pedido = 3
		ORDER BY tbl_faturamento.nota_fiscal";
//echo $sql ;
//exit;
$res = pg_exec ($con,$sql);

if (pg_numrows($res) > 0) {
	echo "<br><table align='center' border='0' cellspacing='1' cellpadding='1'>";

	echo "<tr bgcolor='#0099CC' style='color:#ffffff ; font-weight:bold ; font-size:16px' align='center'>";
	echo "<td>Nota Fiscal</td>";
	echo "<td>Emissão</td>";
	echo "<td>Posto</td>";
	echo "<td>CFOP</td>";
	echo "<td>OS</td>";
	echo "<td>Peça</td>";
	echo "<td>Preço</td>";
	echo "</tr>";
	
	$ultima_nf=0;
	$cor = "#cccccc";
	$total = 0 ;
	
	for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {

		$muda_cor = false;
		$nf  = pg_result ($res,$i,nota_fiscal);
		if ($ultima_nf <> $nf) $muda_cor = true;
		if ($muda_cor) {
			$ultima_nf=$nf;
			if ($cor = "#cccccc") {
				$cor = "#eeeeee";
			}else{
				$cor = "#cccccc";
			}
		}
		echo "<tr bgcolor='$cor' style='font-size=10px'>";

		echo "<td align='left'>";
		echo pg_result ($res,$i,nota_fiscal);
		echo "</td>";

		echo "<td align='left'>";
		echo pg_result ($res,$i,emissao);
		echo "</td>";

		echo "<td align='left' nowrap>";
		echo pg_result ($res,$i,codigo_posto) . "-" . pg_result ($res,$i,nome);
		echo "</td>";

		echo "<td align='left' nowrap>";
		echo pg_result ($res,$i,cfop);
		echo "</td>";

		echo "<td align='left' nowrap>";
		echo pg_result ($res,$i,sua_os);
		echo "</td>";

		echo "<td align='left' nowrap>";
		echo pg_result ($res,$i,referencia) . "-" . pg_result ($res,$i,descricao);
		echo "</td>";

		echo "<td align='right' nowrap>";
		echo number_format (pg_result ($res,$i,preco),2,",",".");
		echo "</td>";

		echo "</tr>";

		$total += pg_result ($res,$i,preco);
	}
	echo "</table>";

	echo "Total : R$ " . number_format ($total,2,",",".");
}else{
	echo "Não foram encontradas notas fiscais no período";
}














#-------------- INCIO DO RELATORIO ANTIGO

exit;


$data_inicio = $data . " 00:00:00";
$data_final  = $data . "  23:59:59";
$data_extrato = substr ($data,8,2) . "/" . substr ($data,5,2) . "/" . substr ($data,0,4) ;

?>

<p>
<center>
<font size='+1' face='arial'>Data do Extrato <? echo $data_extrato ?></font>

<p>
<table width='400' align='center' border='0'>
<tr>
<td align='center' width='50%'><a href='new_extrato_distribuidor.php?periodo=<? echo $data ?>'>Ver extrato total</a></td>
<td align='center' width='50%'><a href='new_extrato_distribuidor.php'>Ver outro extrato</a></td>
</tr>
</table>


<br>
<font face='arial' size='+1' color='#330066'>Relação das peças trocadas nas O.S. deste extrato.
<br>

<p>

<?

if (strlen ($data) > 0) {
	$sql = "SELECT tbl_posto_fabrica.codigo_posto ,
	               tbl_posto.nome                 ,
				   tbl_peca.referencia            ,
				   tbl_peca.ipi                   ,
				   tbl_os.sua_os                  ,
				   tbl_os.os                      ,
				   tbl_os_extra.extrato           ,
				   tbl_peca.descricao             ,
				   tbl_os_item.qtde               ,
				   tbl_tabela_item.preco
			FROM   tbl_os
			JOIN   tbl_posto ON tbl_os.posto = tbl_posto.posto
			JOIN   tbl_posto_fabrica ON tbl_os.posto = tbl_posto_fabrica.posto AND tbl_os.fabrica = tbl_posto_fabrica.fabrica
			JOIN   tbl_os_extra ON tbl_os.os = tbl_os_extra.os
			JOIN   tbl_extrato  ON tbl_os_extra.extrato = tbl_extrato.extrato
			JOIN   tbl_os_produto ON tbl_os.os = tbl_os_produto.os
			JOIN   tbl_os_item    ON tbl_os_produto.os_produto = tbl_os_item.os_produto
			JOIN   tbl_peca       ON tbl_os_item.peca = tbl_peca.peca
			JOIN   tbl_servico_realizado ON tbl_os_item.servico_realizado = tbl_servico_realizado.servico_realizado
			LEFT JOIN tbl_tabela_item    ON tbl_os_extra.tabela = tbl_tabela_item.tabela AND tbl_os_item.peca = tbl_tabela_item.peca
			WHERE  tbl_extrato.data_geracao BETWEEN '$data_inicio' AND '$data_final' 
			AND    tbl_extrato.aprovado IS NOT NULL 
			AND    tbl_extrato.fabrica = $login_fabrica
			AND    (tbl_extrato.posto = $login_posto OR tbl_extrato.posto IN (SELECT posto FROM tbl_posto_linha WHERE distribuidor = $login_posto) )
			AND    tbl_os_item.servico_realizado = 20
			AND    tbl_os_extra.distribuidor = $login_posto
			ORDER BY tbl_posto.nome , tbl_os.sua_os";
			

	$sql = "SELECT tbl_posto_fabrica.codigo_posto ,
	               tbl_posto.nome                 ,
				   tbl_peca.referencia            ,
				   tbl_peca.ipi                   ,
				   tbl_os.sua_os                  ,
				   tbl_os.os                      ,
				   tbl_os_extra.extrato           ,
				   tbl_peca.descricao             ,
				   tbl_os_item.qtde               ,
				   tbl_os_item.custo_peca AS preco
			FROM   tbl_os
			JOIN   tbl_posto ON tbl_os.posto = tbl_posto.posto
			JOIN   tbl_posto_fabrica ON tbl_os.posto = tbl_posto_fabrica.posto AND tbl_os.fabrica = tbl_posto_fabrica.fabrica
			JOIN   tbl_os_extra ON tbl_os.os = tbl_os_extra.os
			JOIN   tbl_extrato  ON tbl_os_extra.extrato = tbl_extrato.extrato
			JOIN   tbl_os_produto ON tbl_os.os = tbl_os_produto.os
			JOIN   tbl_os_item    ON tbl_os_produto.os_produto = tbl_os_item.os_produto
			JOIN   tbl_peca       ON tbl_os_item.peca = tbl_peca.peca
			JOIN   tbl_servico_realizado ON tbl_os_item.servico_realizado = tbl_servico_realizado.servico_realizado
			WHERE  tbl_extrato.data_geracao BETWEEN '$data_inicio' AND '$data_final' 
			AND    tbl_extrato.aprovado IS NOT NULL 
			AND    tbl_extrato.fabrica = $login_fabrica
			AND    (tbl_extrato.posto = $login_posto OR tbl_extrato.posto IN (SELECT posto FROM tbl_posto_linha WHERE distribuidor = $login_posto) )
			AND    tbl_os_item.servico_realizado = 20
			AND    tbl_os_extra.distribuidor = $login_posto
			ORDER BY tbl_posto.nome , tbl_os.sua_os";
			
#echo $sql;
#exit;

	$res = pg_exec ($con,$sql);

	$posto_ant = "";
	$total = 0 ;
	$total_posto = 0 ;

	for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
		if ($posto_ant <> pg_result ($res,$i,codigo_posto) ) {
			if (strlen ($posto_ant) > 0) {
				echo "<tr bgcolor='#FFCC99'>";
				echo "<td colspan='4' >TOTAL DO POSTO</td>";
				echo "<td></td>";
				echo "<td align='right'>" . number_format ($total_posto,2,",",".") . "</td>";

				echo "</tr>";

				$total_posto = 0 ;

				echo "</table><p>";
				flush();
			}

			echo "<table width='550' align='center' border='0' cellspacing='3'>\n";
			echo "<tr bgcolor='#0066CC'>\n";
			echo "<td colspan='6' align='center'><font size='+1' color='#ffffff'>Posto " . pg_result ($res,$i,codigo_posto) . " - " . pg_result ($res,$i,nome) . "</font>";
			echo "</td>\n";
			echo "</tr>\n";

			echo "<tr bgcolor='#336600' style='color:#ffffff ; text-align:center ' >\n";
			echo "<td>OS</td>\n";
			echo "<td>Extrato</td>\n";
			echo "<td>Peça</td>\n";
			echo "<td>Descrição</td>\n";
			echo "<td>Qtde</td>\n";
			echo "<td>Valor</td>\n";
			echo "</tr>\n";

			$posto_ant = pg_result ($res,$i,codigo_posto);

		}

		$cor = '#ffffff';
		if ($i % 2 == 0) $cor = '#eeeeee';
#		echo "<FORM name='frm_extrato'>\n";
#		echo "<tr bgcolor='$cor' style='font-color:#000000 ; align:left ; font-size:10px ' >\n";
#		echo "<td><a href=\"javascript: fnc_consulta_os ('$data','".pg_result ($res,$i,peca)."')\">" . pg_result ($res,$i,referencia) . "</a></td>\n";
#		echo "<td>" . pg_result ($res,$i,descricao) . "</td>\n";
#		echo "<td align='right'>" . pg_result ($res,$i,qtde) . "</td>\n";
#		echo "</tr>\n";
#		echo "</FORM>\n";

		$ipi = pg_result ($res,$i,ipi);
		if (strlen ($ipi) == 0) $ipi = 0;

		$preco = pg_result ($res,$i,preco);
#		$preco = $preco * (1 + ($ipi / 100));

		echo "<tr bgcolor='$cor' style='font-color:#000000 ; align:left ; font-size:10px ' >\n";
		echo "<td>" . pg_result ($res,$i,sua_os) . "</td>\n";
		echo "<td>" . pg_result ($res,$i,extrato) . "</td>\n";
		echo "<td>" . pg_result ($res,$i,referencia) . "</td>\n";
		echo "<td>" . pg_result ($res,$i,descricao) . "</td>\n";
		echo "<td align='right'>" . pg_result ($res,$i,qtde) . "</td>\n";
		echo "<td align='right'>" . number_format ($preco,2,",",".") . "</td>\n";
		echo "</tr>\n";

		$total += $preco ;
		$total_posto += $preco ;

	}

	echo "<tr bgcolor='#FFCC99'>";
	echo "<td colspan='4' >TOTAL DO POSTO</td>";
	echo "<td></td>";
	echo "<td align='right'>" . number_format ($total_posto,2,",",".") . "</td>";

	echo "</tr>";


	
	echo "<tr bgcolor='#FFCC99'>";
	echo "<td colspan='4' >TOTAL GERAL</td>";
	echo "<td></td>";
	echo "<td align='right'>" . number_format ($total,2,",",".") . "</td>";

	echo "</tr>";

	echo "</table>\n";

}

?>

<p><p>

<? include "rodape.php"; ?>