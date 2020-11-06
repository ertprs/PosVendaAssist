<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

$msg_erro = "";

$btn_acao = $_POST['btn_acao'];
if (strlen ($btn_acao) > 0) {
	$extrato = $_POST['extrato'];

	$nota_fiscal_devolucao = trim ($_POST['nota_fiscal_devolucao']);
	$nota_fiscal_devolucao = "000000" . $nota_fiscal_devolucao;
	$nota_fiscal_devolucao = substr ($nota_fiscal_devolucao, strlen ($nota_fiscal_devolucao)-6 );

	if (intval ($nota_fiscal_devolucao) > 0) {
		$sql = "UPDATE tbl_extrato_extra SET nota_fiscal_devolucao = '$nota_fiscal_devolucao' WHERE extrato = $extrato";
		$res = pg_exec ($con,$sql);
	}else{
		$sql = "UPDATE tbl_extrato_extra SET nota_fiscal_devolucao = null WHERE extrato = $extrato";
		$res = pg_exec ($con,$sql);
	}
}


$layout_menu = "os";
$title = "Peças Retornáveis do Extrato";

include "cabecalho.php";

?>

<p>
<center>
<?
if (strlen ($extrato) == 0) $extrato = trim($_GET['extrato']);

$sql = "SELECT  to_char (data_geracao,'DD/MM/YYYY') AS data ,
				to_char (data_geracao,'YYYY-MM-DD') AS periodo ,
				tbl_posto.nome ,
				tbl_posto_fabrica.codigo_posto
		FROM tbl_extrato
		JOIN tbl_posto ON tbl_extrato.posto = tbl_posto.posto
		JOIN tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
		WHERE tbl_extrato.extrato = $extrato ";
$res = pg_exec ($con,$sql);
$data = pg_result ($res,0,data);
$periodo = pg_result ($res,0,periodo);
$nome = pg_result ($res,0,nome);
$codigo = pg_result ($res,0,codigo_posto);

echo "<font size='+1' face='arial'>Data do Extrato $data </font>";
echo "<br>";
echo "<font size='+0' face='arial'>$codigo - $nome</font>";


$sql = "SELECT nota_fiscal_devolucao FROM tbl_extrato_extra WHERE extrato = $extrato";
$res = pg_exec ($con,$sql);
$nota_fiscal_devolucao = pg_result ($res,0,0);

$link_mo = "#";
if (strlen ($nota_fiscal_devolucao) > 0) $link_mo = "new_extrato_posto_mao_obra.php";
?>

<p>
<table width='550' align='center' border='0' style='font-size:12px'>
<tr>
<td align='center' width='33%'><a href='<? echo $link_mo ?>?extrato=<? echo $extrato ?>'>Ver Mão-de-Obra</a></td>
<td align='center' width='33%'><a href='new_extrato_posto.php'>Ver outro extrato</a></td>
</tr>
</table>


<br>
<font face='arial' size='+1' color='#330066'>Você deve emitir uma Nota Fiscal com os dados abaixo.</font>
<br>
<font face='arial' size='+0' color='#330066'>O valor da mão-de-obra só será exibido <br> depois que você confirmar a emissão da Nota de Devolução.</font>

<p>

<?

$sql = "SELECT DISTINCT distribuidor FROM tbl_faturamento WHERE extrato_devolucao = $extrato AND posto = $login_posto";
$res = pg_exec ($con,$sql);
$distribuidor_ant = "*";


echo "<form method='post' action='$PHP_SELF' name='frm_devol'>";
echo "<input type='hidden' name='extrato' value='$extrato'>";


for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
	if ($distribuidor_ant <> pg_result ($res,$i,distribuidor) ) {
		if (strlen ($distribuidor_ant) > 0) {
			echo "</table>";
			$distribuidor_ant = pg_result ($res,$i,distribuidor);
		}
		
		$sql = "SELECT * FROM tbl_posto WHERE posto = $login_posto";
		$resX = pg_exec ($con,$sql);
		$estado_origem = pg_result ($resX,0,estado);

		$distribuidor = trim (pg_result ($res,$i,distribuidor));
		if (strlen ($distribuidor) > 0) {
			$sql  = "SELECT * FROM tbl_posto WHERE posto = $distribuidor";
			$resX = pg_exec ($con,$sql);

			$estado   = pg_result ($resX,0,estado);
			$razao    = pg_result ($resX,0,nome);
			$endereco = trim (pg_result ($resX,0,endereco)) . " " . trim (pg_result ($resX,0,numero));
			$cidade   = pg_result ($resX,0,cidade);
			$estado   = pg_result ($resX,0,estado);
			$cep      = pg_result ($resX,0,cep);
			$fone     = pg_result ($resX,0,fone);
			$cnpj     = pg_result ($resX,0,cnpj);
			$ie       = pg_result ($resX,0,ie);

			$condicao_1 = " tbl_faturamento.distribuidor = $distribuidor ";

		}else{
			$sql  = "SELECT * FROM tbl_fabrica WHERE fabrica = $login_fabrica";
			$resX = pg_exec ($con,$sql);

			$razao    = pg_result ($resX,0,razao_social);
			$endereco = pg_result ($resX,0,endereco);
			$cidade   = pg_result ($resX,0,cidade);
			$estado   = pg_result ($resX,0,estado);
			$cep      = pg_result ($resX,0,cep);
			$fone     = pg_result ($resX,0,fone);
			$cnpj     = pg_result ($resX,0,cnpj);
			$ie       = pg_result ($resX,0,ie);

			$distribuidor = "null";
			$condicao_1 = " tbl_faturamento.distribuidor IS NULL ";
		}

		$cfop = '6949';
		if ($estado_origem == $estado) $cfop = '5949';

		echo "<table border='1' cellspacing='0' cellpadding='3' style='font-size:12px' width='600' >";
		echo "<tr>";
		echo "<td>Natureza <br> <b>Devolução de Garantia</b> </td>";
		echo "<td>CFOP <br> <b>$cfop</b> </td>";
		echo "<td>Emissao <br> <b>$data</b> </td>";
		echo "</tr>";
		echo "</table>";

		$cnpj = substr ($cnpj,0,2) . "." . substr ($cnpj,2,3) . "." . substr ($cnpj,5,3) . "/" . substr ($cnpj,8,4) . "-" . substr ($cnpj,12,2);
		echo "<table border='1' cellspacing='0' cellpadding='3' style='font-size:12px' width='600' >";
		echo "<tr>";
		echo "<td>Razão Social <br> <b>$razao</b> </td>";
		echo "<td>CNPJ <br> <b>$cnpj</b> </td>";
		echo "<td>Inscrição Estadual <br> <b>$ie</b> </td>";
		echo "</tr>";
		echo "</table>";

		$cep = substr ($cep,0,2) . "." . substr ($cep,2,3) . "-" . substr ($cep,5,3) ;
		echo "<table border='1' cellspacing='0' cellpadding='3' style='font-size:12px' width='600' >";
		echo "<tr>";
		echo "<td>Endereço <br> <b>$endereco </b> </td>";
		echo "<td>Cidade <br> <b>$cidade</b> </td>";
		echo "<td>Estado <br> <b>$estado</b> </td>";
		echo "<td>CEP <br> <b>$cep</b> </td>";
		echo "</tr>";
		echo "</table>";

		echo "<table border='1' bgcolor='#dddddd' cellspacing='0' cellpadding='3' style='font-size:12px' width='600' >";
		echo "<tr align='center'>";
		echo "<td><b>Código</b></td>";
		echo "<td><b>Descrição</b></td>";
		echo "<td><b>Qtde.</b></td>";
		echo "<td><b>Unitário</b></td>";
		echo "<td><b>Total</b></td>";
		echo "<td><b>% ICMS</b></td>";
		echo "</tr>";

		$sql = "SELECT tbl_peca.peca, tbl_peca.referencia, tbl_peca.descricao, tbl_peca.ipi, tbl_faturamento_item.aliq_icms, SUM (tbl_faturamento_item.qtde) AS qtde, SUM (tbl_faturamento_item.qtde * tbl_faturamento_item.preco ) AS total_item, SUM (tbl_faturamento_item.base_icms) AS base_icms, SUM (tbl_faturamento_item.valor_icms) AS valor_icms
				FROM tbl_peca
				JOIN tbl_faturamento_item USING (peca)
				JOIN tbl_faturamento      USING (faturamento)
				WHERE tbl_faturamento.fabrica = $login_fabrica
				AND   tbl_faturamento.posto   = $login_posto
				AND   tbl_faturamento.extrato_devolucao = $extrato
				AND   $condicao_1
				AND   tbl_faturamento.emissao > '2005-10-01'
				AND   tbl_faturamento_item.aliq_icms > 0
				GROUP BY tbl_peca.peca, tbl_peca.referencia, tbl_peca.descricao, tbl_peca.ipi, tbl_faturamento_item.aliq_icms
				ORDER BY tbl_peca.referencia ";

#AND   tbl_faturamento.tipo_pedido = 3

		$resX = pg_exec ($con,$sql);

		$notas_fiscais    = "";
		$total_base_icms  = 0;
		$total_valor_icms = 0;
		$total_nota       = 0;
		$aliq_final       = 0;

		for ($x = 0 ; $x < pg_numrows ($resX) ; $x++) {

			$peca       = pg_result ($resX,$x,peca);
			$qtde       = pg_result ($resX,$x,qtde);
			$total_item = pg_result ($resX,$x,total_item);
			$base_icms  = pg_result ($resX,$x,base_icms);
			$valor_icms = pg_result ($resX,$x,valor_icms);
			$aliq_icms  = pg_result ($resX,$x,aliq_icms);
			$preco = round ($total_item / $qtde,2);
			$total_item = $preco * $qtde;

			if ($base_icms > $total_item) $base_icms = $total_item;
			if ($aliq_final == 0) $aliq_final = $aliq_icms;
			if ($aliq_final <> $aliq_icms) $aliq_final = -1;

			echo "<tr bgcolor='#ffffff' style='font-color:#000000 ; align:left ; font-size:10px ' >";
			echo "<td>" . pg_result ($resX,$x,referencia) . "</td>";
			echo "<td>" . pg_result ($resX,$x,descricao) . "</td>";
			echo "<td align='right'>" . pg_result ($resX,$x,qtde) . "</td>";
			echo "<td align='right' nowrap>" . number_format ($preco,2,",",".") . "</td>";
			echo "<td align='right' nowrap>" . number_format ($total_item,2,",",".") . "</td>";
			echo "<td align='right'>" . $aliq_icms . "</td>";
			echo "</tr>";

#			if (strpos ($notas_fiscais , pg_result ($resP,0,nota_fiscal)) === false ) {
#				$notas_fiscais .= pg_result ($resP,0,nota_fiscal) . ", " ;
#			}
			$total_base_icms  += $base_icms;
			$total_valor_icms += $valor_icms;
			$total_nota       += $total_item;
		}

		echo "<tr bgcolor='#eeeeee' style='font-color:#000000 ; align:left ; font-size:10px ' >";
		echo "<td colspan='6'> Referente suas NFs. " . $notas_fiscais . "</td>";
		echo "</td>";
		echo "</tr>";
		
		echo "</table>";

		if ($aliq_final > 0) $total_valor_icms = round ($total_base_icms * $aliq_final / 100,2);

		echo "<table border='1' cellspacing='0' cellpadding='3' style='font-size:12px' width='600' >";
		echo "<tr>";
		echo "<td>Base ICMS <br> <b> " . number_format ($total_base_icms,2,",",".") . " </b> </td>";
		echo "<td>Valor ICMS <br> <b> " . number_format ($total_valor_icms,2,",",".") . " </b> </td>";
		echo "<td>Total da Nota <br> <b> " . number_format ($total_nota,2,",",".") . " </b> </td>";
		echo "</tr>";
		echo "</table>";

		if (strlen ($nota_fiscal_devolucao) == 0) {
			echo "<center><br>";
			echo "<b>Confirme a emissão da sua Nota de Devolução</b><br>Este número não poderá ser alterado<br>";
			echo "<input type='text' name='nota_fiscal_devolucao' size='6' maxlength='6'><br><br>";

		}else{
			echo "<h1><center>Nota de Devolução $nota_fiscal_devolucao</center></h1>";
		}

	}

}

if (strlen ($nota_fiscal_devolucao) == 0) {
	echo "<p><input type='submit' name='btn_acao' value='Confirmar'>";
}

echo "</form>";

?>

<p><p>

<? include "rodape.php"; ?>
