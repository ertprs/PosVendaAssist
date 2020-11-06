<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";
$admin_privilegios="financeiro";
include "autentica_admin.php";


$msg = "";

$btn_acao = $_POST['btn_acao'];
if (strlen($btn_acao) > 0) {
	$extrato = $_POST['extrato'];
	$posto = $_POST['posto'];
	$posto = $_GET['posto'];

	$res = pg_exec ($con,"BEGIN TRANSACTION");

	$sql = "SELECT * FROM tbl_extrato_devolucao WHERE extrato = $extrato";
	$res = pg_exec ($con,$sql);

	for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
		$extrato_devolucao = pg_result ($res,$i,extrato_devolucao);

		$nota_fiscal = trim($_POST['nota_fiscal_' . $extrato_devolucao]);
		$total_nota  = trim($_POST['total_nota_'  . $extrato_devolucao]);
		$base_icms   = trim($_POST['base_icms_'   . $extrato_devolucao]);
		$valor_icms  = trim($_POST['valor_icms_'  . $extrato_devolucao]);
		
		if (strlen($nota_fiscal) == 0) {
			$msg = " Favor informar o número de todas as Notas de Devolução.";
			$res = pg_exec ($con,"ROLLBACK TRANSACTION");
		}
	
		$nota_fiscal = str_replace(".","",$nota_fiscal);
		$nota_fiscal = str_replace(",","",$nota_fiscal);
		$nota_fiscal = str_replace("-","",$nota_fiscal);

		$nota_fiscal = "000000" . $nota_fiscal;
		$nota_fiscal = substr ($nota_fiscal,strlen ($nota_fiscal)-6);

		if (strlen ($msg) == 0) {
			$sql =	"UPDATE tbl_extrato_devolucao SET
					nota_fiscal             = '$nota_fiscal'      ,
					total_nota              = $total_nota         ,
					base_icms               = $base_icms          ,
					valor_icms              = $valor_icms         
				WHERE extrato_devolucao = $extrato_devolucao";
#			echo nl2br($sql);
			$resX = @pg_exec ($con,$sql);
			$msg = pg_errormessage($con);
		}
	}

	if (strlen($msg) == 0) {
		$resX = pg_exec ($con,"COMMIT TRANSACTION");
		header ("Location: $PHP_SELF?extrato=$extrato");
		exit;
	}else{
		$resX = pg_exec ($con,"ROLLBACK TRANSACTION");
	}
}


if (strlen ($extrato) == 0) {
	$extrato = trim($_GET['extrato']);
}

if (strlen ($somente_consulta) == 0) $somente_consulta = trim($_GET['somente_consulta']);


$postos_permitidos = array(0 => 'LIXO', 1 => '1537', 2 => '1773', 3 => '7080', 4 => '5037', 5 => '13951', 6 => '4311', 7 => '564', 8 => '1623', 9 => '1664', 10 => '595');

$postos_permitidos_novo = array(0 => 'LIXO', 1 => '2506', 2 => '6458', 3 => '1511', 4 => '1870', 5 => '1266', 6 => '6591', 7 => '5496', 8 => '14296', 9 => '6140', 10 => '1161', 11 => '1962');

$postos_permitidos_novo_new = array (0 => 'LIXO',1 => '708', 2 => '710 ', 3 => '14119', 4 => '898', 5 => '6379', 6 => '5024', 7 => '388', 8 => '2508', 9 => '1172', 10 => '1261', 11 => '19724', 12 => '1523', 13 => '1567', 14 => '1581', 15 => '1713', 16 => '1740', 17 => '1752', 18 => '1754', 19 => '1766', 20 => '115', 21 => '1799', 22 => '1806', 23 => '1814', 24 => '1891', 25 => '6432', 26 => '6916', 27 => '6917', 28 => '7245', 29 => '7256', 30 => '13850', 31 => '4044', 32 => '14182', 33 => '14297', 34 => '14282', 35 => '14260', 36 => '18941', 37 => '18967', 38 => '5419');

if (strlen ($extrato) > 0) {

	if ($extrato>144000){
		if (array_search($posto, $postos_permitidos)>0){ //verifica se o posto tem permissao
			header ("Location: extrato_posto_devolucao_britania_lgr.php?extrato=$extrato&posto=$posto&somente_consulta=$somente_consulta");
			exit();
		}
	}
	if ($extrato>148811){
		if (array_search($posto, $postos_permitidos_novo)>0){ //verifica se o posto tem permissao
			header ("Location: extrato_posto_devolucao_britania_lgr.php?extrato=$extrato&posto=$posto&somente_consulta=$somente_consulta");
			exit();
		}
	}
	if ($extrato>176484){
		if (array_search($posto, $postos_permitidos_novo_new)>0){ //verifica se o posto tem permissao
			header ("Location: extrato_posto_devolucao_britania_lgr.php?extrato=$extrato&posto=$posto&somente_consulta=$somente_consulta");
			exit();
		}
	}
	if ($extrato>185731){
		header ("Location: extrato_posto_devolucao_britania_lgr.php?extrato=$extrato&somente_consulta=$somente_consulta");
		exit();
	}
}

$msg_erro = "";

$layout_menu = "financeiro";
$title = "Consulta e Manutenção de Extratos do Posto";

include "cabecalho.php";
?>

<br>
<center>
<?
	echo "<table width='550' align='center'>";
	echo "<tr><td>";
	echo "<b>Conforme determina a legislação local</b><p>";

	echo "Para toda nota fiscal de peças enviadas em garantia deve haver nota fiscal de devolução de todas as peças nos mesmos valores, quantidades e com os mesmos destaques de impostos obrigatoriamente.";
	echo "<br>";
	echo "O valor da mão-de-obra será exibido somente após confirmação da Nota Fiscal de Devolução.";
	echo "<br>";
	echo "TODAS as peças de Áudio e Vídeo devem retornar junto com esta Nota fiscal.";
	echo "<br>";
	echo "As peças das linhas de eletroportáteis e branca devem ficar no posto por 90 dias para inspeção ou de acordo com os procedimentos definidos por seu DISTRIBUIDOR.";
	echo "<br>";

	echo "</td></tr></table>";

?>


<? if (strlen($msg) > 0) { 
	echo "<table class='Tabela' width='700' cellspacing='0'  cellpadding='0' align='center'>";
	echo "<tr >";
	echo "<td bgcolor='FFFFFF' width='60'><img src='imagens/proibido2.jpg' align='middle'></td><td  class='Erro' bgcolor='FFFFFF' align='left'> $msg</td>";
	echo "</tr>";
	echo "</table><br>";
} ?>


<?

$sql  = "SELECT COUNT(*) FROM tbl_extrato_devolucao WHERE extrato = $extrato AND nota_fiscal IS NULL";
$resY = pg_exec ($con,$sql);
$qtde = pg_result ($resY,0,0);
if ($qtde > 0) {
	$sql  = "SELECT COUNT(*) FROM tbl_extrato_devolucao WHERE extrato = $extrato AND nota_fiscal IS NOT NULL";
	$resY = pg_exec ($con,$sql);
	$qtde = pg_result ($resY,0,0);
	if ($qtde > 0) {
		$sql = "DELETE FROM tbl_extrato_devolucao WHERE extrato = $extrato";
		$resY = pg_exec ($con,$sql);
	}
}



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

?>

<p>
<table width='550' align='center' border='0' style='font-size:12px'>
<tr>
<?
if(strlen($somente_consulta)> 0){
	echo "<td align='center' width='33%'><a href='extrato_posto_mao_obra_consulta.php?extrato=$extrato&posto=$posto&somente_consulta=$somente_consulta'>Ver Mão-de-Obra</a></td>";
}else{
	echo "<td align='center' width='33%'><a href='extrato_posto_mao_obra.php?extrato=$extrato&posto=$posto&somente_consulta=$somente_consulta'>Ver Mão-de-Obra</a></td>";
}
?>
<td align='center' width='33%'><a href='extrato_posto_britania.php?somente_consulta=$somente_consulta'>Ver outro extrato</a></td>
</tr>
</table>


<p>

<?
$sql = "UPDATE tbl_faturamento_item SET linha = tbl_produto.linha 
		WHERE tbl_faturamento_item.peca = tbl_lista_basica.peca 
		AND tbl_lista_basica.produto = tbl_produto.produto 
		AND tbl_faturamento_item.faturamento = tbl_faturamento.faturamento
		AND tbl_faturamento.fabrica = $login_fabrica 
		AND tbl_faturamento.extrato_devolucao = $extrato";

$sql = "UPDATE tbl_faturamento_item SET linha = (SELECT tbl_produto.linha FROM tbl_produto 
				JOIN tbl_lista_basica ON tbl_produto.produto = tbl_lista_basica.produto AND tbl_faturamento_item.peca = tbl_lista_basica.peca LIMIT 1)
		FROM tbl_faturamento
		WHERE tbl_faturamento_item.faturamento = tbl_faturamento.faturamento
		AND tbl_faturamento.fabrica = $login_fabrica 
		AND tbl_faturamento.extrato_devolucao = $extrato";

$res = pg_exec ($con,$sql);

if ($login_fabrica == 3) {
	$sql = "UPDATE tbl_faturamento_item SET linha = 2
			WHERE tbl_faturamento_item.faturamento = tbl_faturamento.faturamento
			AND tbl_faturamento.fabrica = $login_fabrica 
			AND tbl_faturamento.extrato_devolucao = $extrato
			AND tbl_faturamento_item.linha IS NULL";

	$sql = "UPDATE tbl_faturamento_item SET linha = 2
			FROM tbl_faturamento
			WHERE tbl_faturamento_item.faturamento = tbl_faturamento.faturamento
			AND tbl_faturamento.fabrica = $login_fabrica 
			AND tbl_faturamento.extrato_devolucao = $extrato
			AND tbl_faturamento_item.linha IS NULL";

	$res = pg_exec ($con,$sql);
}

$sql = "SELECT  DISTINCT
				tbl_faturamento.distribuidor,
				tbl_faturamento_item.linha,
				CASE WHEN tbl_peca.produto_acabado IS TRUE THEN 'TRUE' ELSE 'NOT TRUE' END AS produto_acabado,
				tbl_faturamento.serie
		FROM    tbl_faturamento 
		JOIN    tbl_faturamento_item USING (faturamento) 
		JOIN    tbl_peca             USING (peca)
		WHERE   tbl_faturamento.extrato_devolucao = $extrato
		AND     tbl_faturamento.posto             = $posto
		AND     (tbl_faturamento.cfop ILIKE '59%' OR tbl_faturamento.cfop ILIKE '69%')
		AND     tbl_faturamento_item.aliq_icms > 0 ";

//if ($ip == "201.0.9.216") echo $sql;
$res = pg_exec ($con,$sql);
$distribuidor_ant = "*";
$linha_ant        = "*";
$produto_acabado_ant = "*";

if (pg_numrows ($res) > 0) {

	echo "<br>";
	echo "<font face='arial' size='+1' color='#330066'>Você deve emitir uma Nota Fiscal com os dados abaixo.</font>";
	echo "<br>";
	echo "<font face='arial' size='+0' color='#330066'>O valor da mão-de-obra só será exibido <br> depois que você confirmar a emissão da Nota de Devolução.</font>";
	echo "<br>";
	echo "<font face='arial' size='+0' color='#330066'>As peças de Áudio e Vídeo devem <b>todas</b> retornar fisicamente junto com esta Nota fiscal.</font>";
	echo "<br>";
	echo "<font face='arial' size='+0' color='#330066'>As peças de Eletro e linha branca devem ficar no posto por 90 dias para inspeção.</font>";

	echo "<form method='post' action='$PHP_SELF' name='frm_devol'>";
	echo "<input type='hidden' name='extrato' value='$extrato'>";

	$qtde_for = pg_numrows ($res);

	for ($i=0; $i < $qtde_for; $i++) {
		if ($distribuidor_ant <> pg_result ($res,$i,distribuidor) OR $linha_ant <> pg_result ($res,$i,linha) OR $produto_acabado_ant <> pg_result ($res,$i,produto_acabado) ) {
			if ($distribuidor_ant <> "*" AND $linha_ant <> "*" AND $produto_acabado_ant <> "*" ) {
				echo "</table>";
			}

			$sql = "SELECT * FROM tbl_posto WHERE posto = $posto";
			$resX = pg_exec ($con,$sql);
			$estado_origem = pg_result ($resX,0,estado);

			$distribuidor = trim (pg_result ($res,$i,distribuidor));
			$produto_acabado = trim (pg_result ($res,$i,produto_acabado));
			$serie           = trim (pg_result ($res,$i,serie));

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
				$condicao_2 = " tbl_peca.produto_acabado IS $produto_acabado ";

			}else {
				if ($serie == "3") {
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
					$condicao_1 = " tbl_faturamento.distribuidor IS NULL AND tbl_faturamento.serie = '3' ";
					$condicao_2 = " tbl_peca.produto_acabado IS $produto_acabado ";
				}
				else{

					$sql_data_geracao_extrato = "SELECT data_geracao::date FROM tbl_extrato WHERE extrato = {$extrato} AND fabrica = {$login_fabrica}";
					$res_data_geracao_extrato = pg_query($con, $sql_data_geracao_extrato);

					$data_geracao_extrato = pg_fetch_result($res_data_geracao_extrato, 0, "data_geracao");
					
					if(strtotime($data_geracao_extrato) >= strtotime("2017-03-01")){
				
						$razao    = "BRITANIA ELETRONICOS SA";
						$endereco = "Rua Dona Francisca, 12340, Bairro: Pirabeiraba";
						$cidade   = "Joinville";
						$estado   = "SC";
						$cep      = "89239-270";
						$fone     = "(41) 2102-7700";
						$cnpj     = "07019308000128";
						$ie       = "254.861.660";

					}else{
						
						$razao    = "BRITANIA ELETRODOMESTICOS LTDA";
						$endereco = "Rua Dona Francisca, 8300 - Mod.4 e 5 - Bloco A";
						$cidade   = "Joinville";
						$estado   = "SC";
						$cep      = "89239270";
						$fone     = "(41) 2102-7700";
						$cnpj     = "76492701000742";
						$ie       = "254.861.652";

					}

					$distribuidor = "null";
					$condicao_1 = " tbl_faturamento.distribuidor IS NULL AND tbl_faturamento.serie = '2' ";
					$condicao_2 = " tbl_peca.produto_acabado IS $produto_acabado ";
				}
			}

			$cfop = '6949';
			if ($estado_origem == $estado) $cfop = '5949';

			$linha = pg_result ($res,$i,linha);
			$sql = "SELECT * FROM tbl_linha WHERE linha = $linha" ;
			$resZ = pg_exec ($con,$sql);
			$linha_nome = pg_result ($resZ,0,nome);

			$pecas_produtos = "PEÇAS";
			if ($produto_acabado == "TRUE") $pecas_produtos = "PRODUTOS";

			echo "Nota de Devolução de <b>$pecas_produtos</b> da Linha: $linha_nome" ;
			echo "<table border='1' cellspacing='0' cellpadding='3' style='border-collapse:collapse;font-size:12px' width='600' >";
			echo "<tr>";
			echo "<td>Natureza <br> <b>Devolução de Garantia</b> </td>";
			echo "<td>CFOP <br> <b>$cfop</b> </td>";
			echo "<td>Emissao <br> <b>$data</b> </td>";
			echo "</tr>";
			echo "</table>";

			$cnpj = substr ($cnpj,0,2) . "." . substr ($cnpj,2,3) . "." . substr ($cnpj,5,3) . "/" . substr ($cnpj,8,4) . "-" . substr ($cnpj,12,2);
			echo "<table border='1' cellspacing='0' cellpadding='3' style='border-collapse:collapse;font-size:12px' width='600' >";
			echo "<tr>";
			echo "<td>Razão Social <br> <b>$razao</b> </td>";
			echo "<td>CNPJ <br> <b>$cnpj</b> </td>";
			echo "<td>Inscrição Estadual <br> <b>$ie</b> </td>";
			echo "</tr>";
			echo "</table>";

			$cep = substr ($cep,0,2) . "." . substr ($cep,2,3) . "-" . substr ($cep,5,3) ;
			echo "<table border='1' cellspacing='0' cellpadding='3' style='border-collapse:collapse;font-size:12px' width='600' >";
			echo "<tr>";
			echo "<td>Endereço <br> <b>$endereco </b> </td>";
			echo "<td>Cidade <br> <b>$cidade</b> </td>";
			echo "<td>Estado <br> <b>$estado</b> </td>";
			echo "<td>CEP <br> <b>$cep</b> </td>";
			echo "</tr>";
			echo "</table>";

			echo "<table border='1' bgcolor='#dddddd' cellspacing='0' cellpadding='3' style='border-collapse:collapse;font-size:12px' width='600' >";
			echo "<tr align='center'>";
			echo "<td><b>Código</b></td>";
			echo "<td><b>Descrição</b></td>";
			echo "<td><b>Qtde.</b></td>";
			echo "<td><b>Unitário</b></td>";
			echo "<td><b>Total</b></td>";
			echo "<td><b>% ICMS</b></td>";
			echo "<td><b>% IPI</b></td>";
			echo "</tr>";

			$sql = "SELECT DISTINCT tbl_faturamento.nota_fiscal
					FROM tbl_faturamento_item 
					JOIN tbl_faturamento      USING (faturamento)
					JOIN tbl_peca ON tbl_faturamento_item.peca = tbl_peca.peca
					WHERE tbl_faturamento.fabrica = $login_fabrica
					AND   tbl_faturamento.posto   = $posto
					AND   tbl_faturamento_item.linha = $linha
					AND   (tbl_faturamento.cfop ILIKE '59%' OR tbl_faturamento.cfop ILIKE '69%')
					AND   tbl_faturamento.extrato_devolucao = $extrato
					AND   $condicao_1
					AND   $condicao_2
					AND   tbl_faturamento_item.aliq_icms > 0
					AND   tbl_faturamento.emissao > '2005-10-01'
					ORDER BY tbl_faturamento.nota_fiscal ";

	#				AND   tbl_faturamento_item.aliq_icms > 0
			$resX = pg_exec ($con,$sql);
//if ($ip == "201.0.9.216") echo $sql;
			$notas_fiscais    = "";
			for ($x = 0 ; $x < pg_numrows ($resX) ; $x++) {
				$notas_fiscais .= pg_result ($resX,$x,nota_fiscal) . ", ";
			}
			
			$sql = "SELECT tbl_peca.peca,
						tbl_peca.referencia,
						tbl_peca.descricao,
						tbl_peca.ipi,
						tbl_faturamento_item.aliq_icms,
						tbl_faturamento_item.aliq_ipi,
						SUM (tbl_faturamento_item.qtde) AS qtde,
						SUM (tbl_faturamento_item.qtde * tbl_faturamento_item.preco ) AS total_item,
						SUM (tbl_faturamento_item.base_icms) AS base_icms,
						SUM (tbl_faturamento_item.valor_icms) AS valor_icms,
						SUM (tbl_faturamento_item.base_ipi) AS base_ipi,
						SUM (tbl_faturamento_item.valor_ipi) AS valor_ipi
					FROM tbl_peca
					JOIN tbl_faturamento_item USING (peca)
					JOIN tbl_faturamento      USING (faturamento)
					WHERE tbl_faturamento.fabrica = $login_fabrica
					AND   tbl_faturamento.posto   = $posto
					AND   tbl_faturamento_item.linha = $linha
					AND   tbl_faturamento.extrato_devolucao = $extrato
					AND   (tbl_faturamento.cfop ILIKE '59%' OR tbl_faturamento.cfop ILIKE '69%')
					AND   $condicao_1
					AND   $condicao_2
					AND   tbl_faturamento_item.aliq_icms > 0
					AND   tbl_faturamento.emissao > '2005-10-01'
					GROUP BY tbl_peca.peca, tbl_peca.referencia, tbl_peca.descricao, tbl_peca.ipi, tbl_faturamento_item.aliq_icms,tbl_faturamento_item.aliq_ipi
					ORDER BY tbl_peca.referencia ";

	#				AND   tbl_faturamento_item.aliq_icms > 0
			$resX = pg_exec ($con,$sql);
			$total_base_icms  = 0;
			$total_valor_icms = 0;
			$total_base_ipi  = 0;
			$total_valor_ipi = 0;
			$total_nota       = 0;
			$aliq_final       = 0;

			for ($x = 0 ; $x < pg_numrows ($resX) ; $x++) {

				$peca       = pg_result ($resX,$x,peca);
				$qtde       = pg_result ($resX,$x,qtde);
				$total_item = pg_result ($resX,$x,total_item);
				$base_icms  = pg_result ($resX,$x,base_icms);
				$valor_icms = pg_result ($resX,$x,valor_icms);
				$aliq_icms  = pg_result ($resX,$x,aliq_icms);
				$base_ipi   = pg_result ($resX,$x,base_ipi);
				$valor_ipi   = pg_result ($resX,$x,valor_ipi);
				$aliq_ipi   = pg_result ($resX,$x,aliq_ipi);
				$ipi = pg_result ($resX,$x,ipi);
				$preco = round ($total_item / $qtde,2);
				$total_item = $preco * $qtde;

				if (strlen ($base_icms)  == 0) $base_icms = $total_item ;
				if (strlen ($valor_icms) == 0) $valor_icms = round ($total_item * $aliq_icms / 100,2);

				if (strlen($aliq_ipi)==0) $aliq_ipi=0;
				if ($aliq_ipi==0) 	{
					$base_ipi=0;
					$valor_ipi=0;
				}
				else {
					$base_ipi=$total_item;
					$valor_ipi = $total_item*$aliq_ipi/100;
				}
				

				if ($base_icms > $total_item) $base_icms = $total_item;
				if ($aliq_final == 0) $aliq_final = $aliq_icms;
				if ($aliq_final <> $aliq_icms) $aliq_final = -1;

				echo "<tr bgcolor='#ffffff' style='font-color:#000000 ; align:left ; font-size:10px ' >";
				echo "<td align='left'>" . pg_result ($resX,$x,referencia) . "</td>";
				echo "<td align='left'>" . pg_result ($resX,$x,descricao) . "</td>";
				echo "<td align='right'>" . pg_result ($resX,$x,qtde) . "</td>";
				echo "<td align='right' nowrap>" . number_format ($preco,2,",",".") . "</td>";
				echo "<td align='right' nowrap>" . number_format ($total_item,2,",",".") . "</td>";
				echo "<td align='right'>" . $aliq_icms . "</td>";
				echo "<td align='right'>" . $aliq_ipi. "</td>";
				echo "</tr>";

	#			if (strpos ($notas_fiscais , pg_result ($resP,0,nota_fiscal)) === false ) {
	#				$notas_fiscais .= pg_result ($resP,0,nota_fiscal) . ", " ;
	#			}
				$total_base_icms  += $base_icms;
				$total_valor_icms += $valor_icms;
				$total_base_ipi  += $base_ipi;
				$total_valor_ipi += $valor_ipi;
				$total_nota       += $total_item;
			}

			echo "<tr bgcolor='#eeeeee' style='font-color:#000000 ; align:left ; font-size:10px ' >";
			echo "<td colspan='7'> Referente suas NFs. " . $notas_fiscais . "</td>";
			echo "</td>";
			echo "</tr>";

			echo "</table>";

			if ($aliq_final > 0) $total_valor_icms = round ($total_base_icms * $aliq_final / 100,2);

			echo "<table border='1' cellspacing='0' cellpadding='3' style='border-collapse:collapse;font-size:12px' width='600' >";
			echo "<tr>";
			echo "<td>Base ICMS <br> <b> " . number_format ($total_base_icms,2,",",".") . " </b> </td>";
			echo "<td>Valor ICMS <br> <b> " . number_format ($total_valor_icms,2,",",".") . " </b> </td>";
			echo "<td>Base IPI <br> <b> " . number_format ($total_base_ipi,2,",",".") . " </b> </td>";
			echo "<td>Valor IPI <br> <b> " . number_format ($total_valor_ipi,2,",",".") . " </b> </td>";
			echo "<td>Total da Nota <br> <b> " . number_format ($total_nota+$total_valor_ipi,2,",",".") . " </b> </td>";
			echo "</tr>";
			echo "</table>";
			

			if (strlen ($distribuidor) > 0 AND $distribuidor <> "null" ) {
				$condicao_1 = " tbl_extrato_devolucao.distribuidor = $distribuidor ";
			}else{
				$condicao_1 = " tbl_extrato_devolucao.distribuidor IS NULL ";
				$distribuidor = "null";
			}
			$sql = "SELECT * FROM tbl_extrato_devolucao WHERE extrato = $extrato AND $condicao_1 AND linha = $linha";
#			echo nl2br($sql);
			$resNF = pg_exec ($con,$sql);

			if (pg_numrows ($resNF) == 0) {
				$pa = "f";
				if ($produto_acabado == "TRUE") $pa = "t";

				$sql = "INSERT INTO tbl_extrato_devolucao (extrato, linha, distribuidor, produto_acabado) VALUES ($extrato,$linha,$distribuidor,'$pa')";
				$resZ = pg_exec ($con,$sql);
//if ($ip == '201.0.9.216') echo "$sql<br>";

				$sql = "SELECT CURRVAL ('seq_extrato_devolucao')";
				$resZ = pg_exec ($con,$sql);
				$extrato_devolucao = pg_result ($resZ,0,0);
//if ($ip == "201.0.9.216") echo "$sql<br><br>";

				$nota_fiscal = "";

			}else{
				$nota_fiscal = pg_result ($resNF,0,nota_fiscal);
				$extrato_devolucao = pg_result ($resNF,0,extrato_devolucao);
			}

			$extdev = $extrato_devolucao ;
			if (strlen ($nota_fiscal) == 0) {
				echo "\n<input type='hidden' name='total_nota_$extdev' value='$total_nota'>\n";
				echo "<input type='hidden' name='base_icms_$extdev' value='$total_base_icms'>\n";
				echo "<input type='hidden' name='valor_icms_$extdev' value='$total_valor_icms'>\n";
				echo "<center><br>";
				echo "<b>Confirme a emissão da sua Nota de Devolução</b><br>Este número não poderá ser alterado<br>";
				echo "<input type='text' name='nota_fiscal_$extdev' size='6' maxlength='6' value='$nota_fiscal'><br><br>";
				echo "<p>";
				echo "<br>";
				echo "<br>";
				echo "<hr>";
				$botao = 1 ;
			}else{
				echo "<h1><center>Nota de Devolução $nota_fiscal</center></h1>";
				echo "<p>";
				echo "<hr>";
				$botao = 0 ;
			}

			$distribuidor_ant    = @pg_result ($res,$i,distribuidor);
			$linha_ant           = @pg_result ($res,$i,linha);
			$produto_acabado_ant = @pg_result ($res,$i,produto_acabado);

		}

	}

	if ($botao == 1) {
		echo "<p><input type='submit' name='btn_acao' value='Confirmar Notas de Devolução'>";
	}

	$sql = "SELECT  tbl_os.os                                                         ,
			tbl_os.sua_os                                                     ,
			TO_CHAR(tbl_os.data_fechamento,'DD/MM/YYYY') AS data_ressarcimento,
			tbl_produto.referencia                       AS produto_referencia,
			tbl_produto.descricao                        AS produto_descricao ,
			tbl_admin.login
		FROM tbl_os
		JOIN tbl_os_produto USING (os)
		JOIN tbl_os_item    USING(os_produto)
		JOIN tbl_os_extra   USING(os)
		LEFT JOIN tbl_admin      ON tbl_os.troca_garantia_admin   = tbl_admin.admin
		LEFT JOIN tbl_produto    ON tbl_os.produto = tbl_produto.produto
		WHERE tbl_os_extra. extrato = $extrato
		AND  tbl_os.ressarcimento  IS TRUE
		AND  tbl_os.troca_garantia IS TRUE";

	$resX = pg_exec ($con,$sql);
	if(pg_numrows($resX)>0){

		echo "Nota de Devolução de <b>Produtos Ressarcidos</b>" ;
		echo "<table border='1' cellspacing='0' cellpadding='3' border-color='#000' style='border-collapse:collapse;font-size:12px' width='600' >";
		echo "<tr>";
		echo "<td>Natureza <br> <b>Simples Remessa</b> </td>";
		echo "<td>CFOP <br> <b>$cfop</b> </td>";
		echo "<td>Emissao <br> <b>$data</b> </td>";
		echo "</tr>";
		echo "</table>";
	
		echo "<table border='1' cellspacing='0' cellpadding='3' border-color='#000' style='border-collapse:collapse;font-size:12px' width='600' >";
		echo "<tr>";
		echo "<td>Razão Social <br> <b>$razao</b> </td>";
		echo "<td>CNPJ <br> <b>$cnpj</b> </td>";
		echo "<td>Inscrição Estadual <br> <b>$ie</b> </td>";
		echo "</tr>";
		echo "</table>";
	
	
		echo "<table border='1' cellspacing='0' cellpadding='3' border-color='#000' style='border-collapse:collapse;font-size:12px' width='600' >";
		echo "<tr>";
		echo "<td>Endereço <br> <b>$endereco </b> </td>";
		echo "<td>Cidade <br> <b>$cidade</b> </td>";
		echo "<td>Estado <br> <b>$estado</b> </td>";
		echo "<td>CEP <br> <b>$cep</b> </td>";
		echo "</tr>";
		echo "</table>";

		echo "<table border='1' cellspacing='0' cellpadding='3' border-color='#000' style='border-collapse:collapse;font-size:12px' width='600' >";
		echo "<tr align='center'>";
		echo "<td><b>Código</b></td>";
		echo "<td><b>Descrição</b></td>";
		echo "<td><b>Ressarcimento</b></td>";
		echo "<td><b>Responsavel</b></td>";
		echo "<td><b>OS</b></td>";
		echo "</tr>";
	
		for ($x = 0 ; $x < pg_numrows ($resX) ; $x++) {
	
			$sua_os             = pg_result ($resX,$x,sua_os);
			$produto_referencia = pg_result ($resX,$x,produto_referencia);
			$produto_descricao  = pg_result ($resX,$x,produto_descricao);
			$data_ressarcimento = pg_result ($resX,$x,data_ressarcimento);
			$quem_trocou        = pg_result ($resX,$x,login);
	
			echo "<tr bgcolor='#ffffff' style='font-color:#000000 ; align:left ; font-size:10px ' >";
			echo "<td align='left'>$produto_referencia</td>";
			echo "<td align='left'>$produto_descricao</td>";
			echo "<td align='left'>$data_ressarcimento</td>";
			echo "<td align='right'>$quem_trocou</td>";
			echo "<td align='right'>$sua_os</td>";
			echo "</tr>";
		}
		echo "</table>";
	}



	echo "</form>";

}else{

	echo "<h1><center> Extrato de Mão-de-obra Liberado. Recarregue a página. </center></h1>";
	$sql =	"UPDATE tbl_extrato_extra SET
				nota_fiscal_devolucao              = '000000' ,
				valor_total_devolucao              = 0        ,
				base_icms_devolucao                = 0        ,
				valor_icms_devolucao               = 0        ,
				nota_fiscal_devolucao_distribuidor = '000000' ,
				valor_total_devolucao_distribuidor = 0        ,
				base_icms_devolucao_distribuidor   = 0        ,
				valor_icms_devolucao_distribuidor  = 0
			WHERE extrato = $extrato;";
	$res = pg_exec ($con,$sql);

}
?>

<p><p>

<? include "rodape.php"; ?>
