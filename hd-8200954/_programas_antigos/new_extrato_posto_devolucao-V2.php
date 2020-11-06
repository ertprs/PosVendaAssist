<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include "autentica_usuario.php";

$msg = "";


$btn_acao = $_POST['btn_acao'];
if (strlen($btn_acao) > 0) {
	$extrato = $_POST['extrato'];

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

			// adicionado por Fabio - somente para ver se deu erro na hora de confirmar as notas de devolucao
			$email_origem  = "fabio@telecontrol.com.br";
			$email_destino = "fabio@telecontrol.com.br";
			$assunto       = "URGENTE - EXTRATO POSTO DEVOLUCAO";
			$corpo ="<br>Extrato: $extrato\n\n Posto não conseguiu confirmar Nota de Devolução. VERIFICAR URGENTE\n\n";
			@mail($email_destino, stripslashes($assunto), $corpo, "From: ".$email_origem); 
			// fim
			
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


$layout_menu = "os";
$title = "Peças Retornáveis do Extrato";

include "cabecalho.php";
?>

<br>

<? if (strlen($msg) > 0) { ?>
<table width="600" border="0" align="center" class="error">
	<tr>
		<td><?echo $msg?></td>
	</tr>
</table>
<? } ?>

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


if (strlen ($extrato) == 0) $extrato = trim($_GET['extrato']);

$sql  = "SELECT COUNT(*) FROM tbl_extrato_devolucao WHERE extrato = $extrato AND nota_fiscal IS NULL";
$resY = pg_exec ($con,$sql);
$qtde = pg_result ($resY,0,0);
if ($qtde > 0) {
#	$sql  = "SELECT COUNT(*) FROM tbl_extrato_devolucao WHERE extrato = $extrato AND nota_fiscal IS NOT NULL";
#	$resY = pg_exec ($con,$sql);
#	$qtde = pg_result ($resY,0,0);
#	if ($qtde > 0) {
#		$sql = "DELETE FROM tbl_extrato_devolucao WHERE extrato = $extrato";
#		$resY = pg_exec ($con,$sql);
#	}
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
<td align='center' width='33%'><a href='new_extrato_posto_mao_obra.php?extrato=<? echo $extrato ?>'>Ver Mão-de-Obra</a></td>
<td align='center' width='33%'><a href='new_extrato_posto.php'>Ver outro extrato</a></td>
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
		AND     tbl_faturamento.posto             = $login_posto
		AND     (tbl_faturamento.cfop ILIKE '59%' OR tbl_faturamento.cfop ILIKE '69%')
		AND     tbl_faturamento_item.aliq_icms > 0 
		ORDER BY produto_acabado, linha";


//if ($ip == "201.0.9.216") echo $sql;
$res = pg_exec ($con,$sql);
$distribuidor_ant    = "*";
$linha_ant           = "*";
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
/* 
foi colocado o  "OR $serie <> pg_result($res,$i,serie)"  pois não estava aparecendo uma nota de devolução
para o posto 80697 - PLAYSOUND RIO SERVIÇOS ELETRONICOS LTDA extrato do dia 06/11/2006 número: 94030
Alterardo por Fernando dia 17/11/2006. Sirlei reclamou.
*/
	for ($i=0; $i < $qtde_for; $i++) {
		if ($distribuidor_ant <> pg_result ($res,$i,distribuidor) OR $linha_ant <> pg_result ($res,$i,linha) OR $produto_acabado_ant <> pg_result ($res,$i,produto_acabado) OR $serie <> pg_result($res,$i,serie)) {
			if ($distribuidor_ant <> "*" AND $linha_ant <> "*" AND $produto_acabado_ant <> "*" ) {
				echo "</table>";
			}

			$sql = "SELECT * FROM tbl_posto WHERE posto = $login_posto";
			$resX = pg_exec ($con,$sql);
			$estado_origem = pg_result ($resX,0,estado);

			$distribuidor    = trim (pg_result ($res,$i,distribuidor));
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

			}else{
				if ($serie == "2") {
					$razao    = "BRITANIA ELETRODOMESTICOS LTDA";
					$endereco = "Rua Dona Francisca, 8300 - Mod.4 e 5 - Bloco A";
					$cidade   = "Joinville";
					$estado   = "SC";
					$cep      = "89239270";
					$fone     = "(41) 2102-7700";
					$cnpj     = "76492701000742";
					$ie       = "254.861.652";

					$distribuidor = "null";
					$condicao_1 = " tbl_faturamento.distribuidor IS NULL AND tbl_faturamento.serie = '2' ";
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
					$condicao_1 = " tbl_faturamento.distribuidor IS NULL AND tbl_faturamento.serie = '$serie' ";
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
			echo "<table border='1' cellspacing='0' cellpadding='3' border-color='#000' style='border-collapse:collapse;font-size:12px' width='600' >";
			echo "<tr>";
			echo "<td>Natureza <br> <b>Devolução de Garantia</b> </td>";
			echo "<td>CFOP <br> <b>$cfop</b> </td>";
			echo "<td>Emissao <br> <b>$data</b> </td>";
			echo "</tr>";
			echo "</table>";

			$cnpj = substr ($cnpj,0,2) . "." . substr ($cnpj,2,3) . "." . substr ($cnpj,5,3) . "/" . substr ($cnpj,8,4) . "-" . substr ($cnpj,12,2);
			echo "<table border='1' cellspacing='0' cellpadding='3' border-color='#000' style='border-collapse:collapse;font-size:12px' width='600' >";
			echo "<tr>";
			echo "<td>Razão Social <br> <b>$razao</b> </td>";
			echo "<td>CNPJ <br> <b>$cnpj</b> </td>";
			echo "<td>Inscrição Estadual <br> <b>$ie</b> </td>";
			echo "</tr>";
			echo "</table>";

			$cep = substr ($cep,0,2) . "." . substr ($cep,2,3) . "-" . substr ($cep,5,3) ;
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
			echo "<td><b>NF Origem</b></td>";
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
					AND   tbl_faturamento.posto   = $login_posto
					AND   tbl_faturamento_item.linha = $linha
					AND   (tbl_faturamento.cfop ILIKE '59%' OR tbl_faturamento.cfop ILIKE '69%')
					AND   tbl_faturamento.extrato_devolucao = $extrato
					AND   $condicao_1
					AND   $condicao_2
					AND   tbl_faturamento_item.aliq_icms > 0
					AND   tbl_faturamento.emissao > '2005-10-01'
					AND   tbl_faturamento.serie = '$serie'
					ORDER BY tbl_faturamento.nota_fiscal ";
//					echo $sql;
			$resX = pg_exec ($con,$sql);
			$notas_fiscais    = "";
			for ($x = 0 ; $x < pg_numrows ($resX) ; $x++) {
				$notas_fiscais .= pg_result ($resX,$x,nota_fiscal) . ", ";
			}
			
			$sql = "SELECT  tbl_faturamento.faturamento, 
							tbl_faturamento.nota_fiscal, 
							tbl_peca.peca, 
							tbl_peca.referencia, 
							tbl_peca.descricao, 
							tbl_peca.ipi, 
							tbl_faturamento_item.aliq_icms,
							tbl_faturamento_item.aliq_ipi,
							SUM (tbl_faturamento_item.qtde) AS qtde, 
							SUM (tbl_faturamento_item.qtde * tbl_faturamento_item.preco ) AS total_item, 
							SUM (tbl_faturamento_item.base_icms) AS base_icms, 
							SUM (tbl_faturamento_item.valor_icms) AS valor_icms,
							SUM (tbl_faturamento_item.valor_ipi) AS valor_ipi,
							SUM (tbl_faturamento_item.base_ipi) AS base_ipi
					FROM tbl_peca
					JOIN tbl_faturamento_item USING (peca)
					JOIN tbl_faturamento      USING (faturamento)
					WHERE tbl_faturamento.fabrica = $login_fabrica
					AND   tbl_faturamento.posto   = $login_posto
					AND   tbl_faturamento_item.linha = $linha
					AND   tbl_faturamento.extrato_devolucao = $extrato
					AND   (tbl_faturamento.cfop ILIKE '59%' OR tbl_faturamento.cfop ILIKE '69%')
					AND   $condicao_1
					AND   $condicao_2
					AND   tbl_faturamento_item.aliq_icms > 0
					AND   tbl_faturamento.emissao > '2005-10-01'
					AND   tbl_faturamento.serie = '$serie'
					GROUP BY tbl_faturamento.faturamento, tbl_peca.peca, tbl_peca.referencia, tbl_peca.descricao, tbl_peca.ipi, tbl_faturamento_item.aliq_icms, tbl_faturamento.nota_fiscal,tbl_faturamento_item.aliq_ipi
					ORDER BY tbl_peca.referencia ";

			$resX = pg_exec ($con,$sql);
			$total_base_icms  = 0;
			$total_valor_icms = 0;
			$total_base_ipi  = 0;
			$total_valor_ipi = 0;
			$total_nota       = 0;
			$aliq_final       = 0;

			for ($x = 0 ; $x < pg_numrows ($resX) ; $x++) {

				$peca        = pg_result ($resX,$x,peca);
				$qtde        = pg_result ($resX,$x,qtde);
				$total_item  = pg_result ($resX,$x,total_item);
				$base_icms   = pg_result ($resX,$x,base_icms);
				$valor_icms  = pg_result ($resX,$x,valor_icms);
				$aliq_icms   = pg_result ($resX,$x,aliq_icms);
				$base_ipi   = pg_result ($resX,$x,base_ipi);
				$aliq_ipi   = pg_result ($resX,$x,aliq_ipi);
				$valor_ipi   = pg_result ($resX,$x,valor_ipi);
				$ipi = pg_result ($resX,$x,ipi);
				$preco       = round ($total_item / $qtde,2);
				$total_item  = $preco * $qtde;
				$nota_fiscal = pg_result ($resX,$x,nota_fiscal);
				$faturamento = pg_result ($resX,$x,faturamento);

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
				echo "<td align='left'>" . pg_result ($resX,$x,nota_fiscal) . "</td>";
				echo "<td align='right'>" . pg_result ($resX,$x,qtde) . "</td>";
				echo "<td align='right' nowrap>" . number_format ($preco,2,",",".") . "</td>";
				echo "<td align='right' nowrap>" . number_format ($total_item,2,",",".") . "</td>";
				echo "<td align='right'>" . $aliq_icms . "</td>";
				echo "<td align='right'>" . $aliq_ipi. "</td>";
				echo "</tr>";

				$total_base_icms  += $base_icms;
				$total_valor_icms += $valor_icms;
				$total_base_ipi  += $base_ipi;
				$total_valor_ipi += $valor_ipi;
				$total_nota       += $total_item;
			}

			echo "<tr bgcolor='#eeeeee' style='font-color:#000000 ; align:left ; font-size:10px ' >";
			echo "<td colspan='8'> Referente suas NFs. " . $notas_fiscais . " Referente a Série $serie</td>";
			echo "</td>";
			echo "</tr>";

			echo "</table>";

			if ($aliq_final > 0) $total_valor_icms = round ($total_base_icms * $aliq_final / 100,2);

			echo "<table border='1' cellspacing='0' cellpadding='3' border-color='#000' style='border-collapse:collapse;font-size:12px' width='600' >";
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

			if ($produto_acabado == "TRUE") {
				$condicao_2 = " tbl_extrato_devolucao.produto_acabado IS TRUE ";
				$pa = "'t'";
			}else{
				$condicao_2 = " tbl_extrato_devolucao.produto_acabado IS NOT TRUE ";
				$pa = "'f'";
			}


			$sql = "SELECT * FROM tbl_extrato_devolucao WHERE extrato = $extrato AND $condicao_1 AND $condicao_2 AND linha = $linha AND (((serie='$serie' OR serie IS NULL) and nota_fiscal IS NULL) OR ( (serie='$serie' OR serie IS NULL) and nota_fiscal IS NOT NULL))";
			$resNF = pg_exec ($con,$sql);
			$numero_linhas=pg_numrows ($resNF);


			if ($numero_linhas == 0) {
	
				# Primeiro extrato da Britania que teve produtos acabados na devolucao 

				if ($extrato >= 30437) {
					$sql = "INSERT INTO tbl_extrato_devolucao (extrato, linha, distribuidor,produto_acabado,serie) VALUES ($extrato,$linha,$distribuidor,$pa,'$serie')";
					$resZ = pg_exec ($con,$sql);

					$sql = "SELECT CURRVAL ('seq_extrato_devolucao')";
					$resZ = pg_exec ($con,$sql);
					$extrato_devolucao = pg_result ($resZ,0,0);

					$nota_fiscal = "";
				}

			}else{
				$nota_fiscal = pg_result ($resNF,0,nota_fiscal);
				$extrato_devolucao = pg_result ($resNF,0,extrato_devolucao);
			}

			$extdev = $extrato_devolucao ;
			if (strlen ($nota_fiscal) == 0) {
				echo "\n";
				echo "<input type='hidden' name='total_nota_$extdev' value='$total_nota'>\n";
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
