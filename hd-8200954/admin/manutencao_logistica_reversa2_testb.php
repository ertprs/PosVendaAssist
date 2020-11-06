<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";
$admin_privilegios="financeiro";
include "autentica_admin.php";


$msg = "";

$btn_acao = $_POST['btn_acao'];
$btn_alterar = $_POST['btn_alterar'];
$btn_deletar = $_POST['btn_deletar'];

if (strlen($btn_deletar) > 0) {
        $extrato = $_POST['extrato'];
        $posto = $_POST['posto'];
        $sql =  "DELETE FROM tbl_extrato_devolucao WHERE extrato = $extrato";
        $res = pg_exec ($con,$sql);
}

if (strlen($btn_alterar) > 0) {
        $extrato = $_POST['extrato'];
        $posto = $_POST['posto'];
        
        $sql = "SELECT * FROM tbl_extrato_devolucao WHERE extrato = $extrato";
        $res = pg_exec ($con,$sql);

        for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
                $extrato_devolucao = pg_result ($res,$i,extrato_devolucao);
                $nota_fiscal_ant = trim($_POST['nota_fiscal_ant_' . $extrato_devolucao]);
                $nota_fiscal_nova  = trim($_POST['nota_fiscal_nova_'  . $extrato_devolucao]);
                if($nota_fiscal_ant<>$nota_fiscal_nova){
                        $sql =  "UPDATE tbl_extrato_devolucao SET
                                        nota_fiscal   = '$nota_fiscal_nova'     
                                WHERE extrato_devolucao = $extrato
                                AND nota_fiscal=$nota_fiscal_ant";
                        
                        $resX = @pg_exec ($con,$sql);
                        $msg = pg_errormessage($con);
                }
                }
}
if (strlen($btn_acao) > 0) {
	$extrato = $_POST['extrato'];
	$posto = $_POST['posto'];
	$posto = $_GET['posto'];

	$res = pg_exec ($con,"BEGIN TRANSACTION");

	$sql = "SELECT * FROM tbl_extrato_devolucao WHERE extrato = $extrato";
	$res = pg_exec ($con,$sql);

	for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
		$extrato_devolucao = pg_result ($res,$i,extrato_devolucao);

		$nota_fiscal = trim($_POST['nota_fiscal_' . $extrato_devolucao ]);
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


$msg_erro = "";

$layout_menu = "financeiro";
$title = "CONSULTA E MANUTENÇÃO DE EXTRATOS DO POSTO";

include "cabecalho.php";
?>
<style type="text/css">
.titulo_tabela{
	background-color:#596d9b;
	font: bold 14px "Arial";
	color:#FFFFFF;
	text-align:center;
}
.formulario{
	background-color:#D9E2EF;
	font:11px Arial;
	text-align:left;
}
.msg_erro{
	background-color:#FF0000;
	font: bold 14px "Arial";
	color:#FFFFFF;
	text-align:center;
}
div.formulario{font-size:12px}
</style>

<? if (strlen($msg) > 0) { 
	echo "<table width='700' cellspacing='1'  cellpadding='0' align='center'>";
	echo "<tr >";
	echo "<td class='msg_erro' align='left'>$msg</td>";
	echo "</tr>";
	echo "</table><br>";
} ?>

<center>
<?

	echo "<table width='700' align='center' class='formulario'>";
	echo "<tr><td class='titulo_tabela'>Conforme Determina a Legislação Local</td></tr><tr><td>";
	
	echo "Para toda nota fiscal de peças enviadas em garantia deve haver nota fiscal de devolução de todas as peças nos mesmos valores, quantidades e com os mesmos destaques de impostos obrigatoriamente.";
	echo "<br>";
	echo "O valor da mão-de-obra será exibido somente após confirmação da Nota Fiscal de Devolução.";
	echo "<br>";
	echo "TODAS as peças de Áudio e Vídeo devem retornar junto com esta Nota fiscal.";
	echo "<br>";
	echo "As peças das linhas de eletroportáteis e branca devem ficar no posto por 90 dias para inspeção ou de acordo com os procedimentos definidos por seu DISTRIBUIDOR.";
	echo "<br>";

	echo "</td></tr></table>";


if (strlen ($extrato) == 0) $extrato = trim($_GET['extrato']);

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

echo "<br /><div class='formulario' style='width:700px;margin:auto;text-align:center;'>",
	 "<font size='+1' face='arial'>Data do Extrato $data </font><br>",
	 "<font size='+0' face='arial'>$codigo - $nome</font>";

?>

<p>
<table width='550' align='center' border='0' style='font-size:12px'>
<tr>
<?
echo "<td align='center' width='33%'><button type=\"button\" onclick=\"window.location='extrato_posto_mao_obra.php?extrato=$extrato&posto=$posto'\">Ver Mão-de-Obra</button></td>";
?>
<td align='center' width='33%'>
	<button type="button" onclick="window.location='extrato_posto_britania.php'">Ver outro extrato</button>
</td>
</tr>
</table>
</div>

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
				tbl_faturamento_item.linha  ,
				CASE WHEN tbl_peca.produto_acabado IS TRUE THEN 'TRUE' ELSE 'NOT TRUE' END AS produto_acabado
		FROM    tbl_faturamento 
		JOIN    tbl_faturamento_item USING (faturamento) 
		JOIN    tbl_peca             USING (peca)
		WHERE   tbl_faturamento.extrato_devolucao = $extrato
		AND     tbl_faturamento.posto             = $posto
		AND     (tbl_faturamento.cfop ILIKE '59%' OR tbl_faturamento.cfop ILIKE '69%')
		AND     tbl_faturamento_item.aliq_icms > 0 ";


//if ($ip == "201.0.9.216") echo $sql;
$res = pg_exec ($con,$sql);
$distribuidor_ant    = "*";
$linha_ant           = "*";
$produto_acabado_ant = "*";

if (pg_numrows ($res) > 0) {

	echo "<div class='formulario' style='width:700px;margin:auto;text-align:center;'>";
	echo "<div class='titulo_tabela'>Você deve emitir uma Nota Fiscal com os dados abaixo. </div><br>";
	echo "O valor da mão-de-obra só será exibido <br> depois que você confirmar a emissão da Nota de Devolução.<br>";
	echo "As peças de Áudio e Vídeo devem <b>todas</b> retornar fisicamente junto com esta Nota fiscal.<br>";
	echo "As peças de Eletro e linha branca devem ficar no posto por 90 dias para inspeção. <br /><br /> </div><br />";

	echo "<form method='post' action='$PHP_SELF' name='frm_devol'>";
	echo "<input type='hidden' name='extrato' value='$extrato'>";
	echo "<input type='hidden' name='posto' value='$posto'>";

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
				$condicao_2 = " tbl_peca.produto_acabado IS $produto_acabado ";
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
			echo "<td><b>IPI</b></td>";
			echo "<td><b>Total</b></td>";
			echo "<td><b>% ICMS</b></td>";
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
			$resX = pg_exec ($con,$sql);
			$notas_fiscais    = "";
			for ($x = 0 ; $x < pg_numrows ($resX) ; $x++) {
				$notas_fiscais .= pg_result ($resX,$x,nota_fiscal) . ", ";
			}
			
			$sql = "SELECT tbl_peca.peca, tbl_peca.referencia, tbl_peca.descricao, tbl_peca.ipi, tbl_faturamento_item.aliq_icms, SUM (tbl_faturamento_item.qtde) AS qtde, SUM (tbl_faturamento_item.qtde * tbl_faturamento_item.preco ) AS total_item, SUM (tbl_faturamento_item.base_icms) AS base_icms, SUM (tbl_faturamento_item.valor_icms) AS valor_icms
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
					GROUP BY tbl_peca.peca, tbl_peca.referencia, tbl_peca.descricao, tbl_peca.ipi, tbl_faturamento_item.aliq_icms
					ORDER BY tbl_peca.referencia ";

	#				AND   tbl_faturamento_item.aliq_icms > 0
			$resX = pg_exec ($con,$sql);
//if ($ip == "201.0.9.216") echo $sql;
			$total_ipi        = 0;
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

				if (strlen ($base_icms)  == 0) $base_icms = $total_item ;
				if (strlen ($valor_icms) == 0) $valor_icms = round ($total_item * $aliq_icms / 100,2);

				if ($base_icms > $total_item) $base_icms = $total_item;
				if ($aliq_final == 0) $aliq_final = $aliq_icms;
				if ($aliq_final <> $aliq_icms) $aliq_final = -1;

				echo "<tr bgcolor='#ffffff' style='font-color:#000000 ; align:left ; font-size:10px ' >";
				echo "<td>" . pg_result ($resX,$x,referencia) . "</td>";
				echo "<td>" . pg_result ($resX,$x,descricao) . "</td>";
				echo "<td align='right'>" . pg_result ($resX,$x,qtde) . "</td>";
				echo "<td align='right' nowrap>" . number_format ($preco,2,",",".") . "</td>";
				if ($pecas_produtos == "PRODUTOS") {$ipi = round ($total_item * 0.25,2) ; $total_item += $ipi; }
				echo "<td align='right' nowrap>" . number_format ($ipi,2,",",".") . "</td>";
				echo "<td align='right' nowrap>" . number_format ($total_item,2,",",".") . "</td>";
				echo "<td align='right'>" . $aliq_icms . "</td>";
				echo "</tr>";

	#			if (strpos ($notas_fiscais , pg_result ($resP,0,nota_fiscal)) === false ) {
	#				$notas_fiscais .= pg_result ($resP,0,nota_fiscal) . ", " ;
	#			}
				$total_base_icms  += $base_icms;
				$total_ipi        += $ipi;
				$ipi               = 0;
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
			echo "<td>Valor IPI <br> <b> " . number_format ($total_ipi,2,",",".") . " </b> </td>";
			echo "<td>Total da Nota <br> <b> " . number_format ($total_nota,2,",",".") . " </b> </td>";
			echo "</tr>";
			echo "</table>";
			

			if (strlen ($distribuidor) > 0 AND $distribuidor <> "null" ) {
				$condicao_1 = " tbl_extrato_devolucao.distribuidor = $distribuidor ";
			}else{
				$condicao_1 = " tbl_extrato_devolucao.distribuidor IS NULL ";
				$distribuidor = "null";
			}

			if ($produto_acabado == "TRUE") {
				$condicao_2 = " tbl_extrato_devolucao.produto_acabado IS TRUE ";
				$pa = "'t'";
			}else{
				$condicao_2 = " tbl_extrato_devolucao.produto_acabado IS NOT TRUE ";
				$pa = "'f'";
			}


			$sql = "SELECT * FROM tbl_extrato_devolucao WHERE extrato = $extrato AND $condicao_1 AND $condicao_2 AND linha = $linha";
			$resNF = pg_exec ($con,$sql);

			if (pg_numrows ($resNF) == 0) {
				$sql = "INSERT INTO tbl_extrato_devolucao (extrato, linha, distribuidor,produto_acabado) VALUES ($extrato,$linha,$distribuidor,$pa)";
				$resZ = pg_exec ($con,$sql);

				$sql = "SELECT CURRVAL ('seq_extrato_devolucao')";
				$resZ = pg_exec ($con,$sql);
				$extrato_devolucao = pg_result ($resZ,0,0);

				$nota_fiscal = "";

			}else{
				$nota_fiscal = pg_result ($resNF,0,nota_fiscal);
				$extrato_devolucao = pg_result ($resNF,0,extrato_devolucao);
			}

			$extdev = $extrato_devolucao ;
			if (strlen ($nota_fiscal) == 0) {
				echo "<center><br>";
				echo "<b>O Posto ainda não informou os números de suas notas fiscais.</b><br><br><br><br>";
				$botao = 0 ;
			}else{
				echo "<input type='hidden' name='nota_fiscal_ant_$extdev' value='$nota_fiscal'>\n";

				echo "<center><FONT SIZE='3' color='#000088'><B>Nota de Devolução</b></font> <input type='text' size='6' maxlength='6' name='nota_fiscal_nova_$extdev' value='$nota_fiscal'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
#				echo "<FONT SIZE='2' color='#880000'><B>Apagar Nota de Devolução</b></font> <input type='checkbox' name='apagar_$extdev' value='$extdev'>";
				echo "<p>";
				echo "<hr>";
				$botao = 1 ;
			}

			$distribuidor_ant = @pg_result ($res,$i,distribuidor);
			$linha_ant        = @pg_result ($res,$i,linha);
			$produto_acabado_ant = @pg_result ($res,$i,produto_acabado);

		}

	}

	if ($botao == 1) {
		echo "<BR><BR><BR>";
		echo "<INPUT TYPE='submit' name='btn_deletar' value='Deletar Nota Fiscal Devolução Selecionada' ></center>";
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
