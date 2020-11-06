<?
header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
header("Pragma: no-cache"); // HTTP/1.0

include "dbconfig.php";
include "includes/dbconnect-inc.php";
include "autentica_usuario.php";

$msg_erro="";
$msg="";

$numero_nota=0;
$item_nota=0;

$extrato = trim($_GET['extrato']);
if (strlen($extrato)==0)
	$extrato = trim($_POST['extrato']);

if (strlen($extrato)==0){
	header("Location: new_extrato_posto.php");
}

$btn_acao = trim($_POST['botao_acao']);
if (strlen($btn_acao) > 0 AND $btn_acao=="digitou_qtde") {

	$qtde_pecas = trim($_POST['qtde_pecas']);
	$numero_linhas = trim($_POST['qtde_linha']);

	$resX = pg_exec ($con,"BEGIN TRANSACTION");

	for($i=1;$i<=$qtde_pecas;$i++){

		$extrato_lgr = trim($_POST["item_$i"]);
		$peca = trim($_POST["peca_$i"]);
		$qtde_pecas_devolvidas = trim($_POST["$extrato_lgr"]);

		if (strlen($qtde_pecas_devolvidas)>0){
			$sql_update = "UPDATE tbl_extrato_lgr 
					SET qtde_nf = $qtde_pecas_devolvidas
					WHERE extrato=$extrato
					AND peca=$peca";
			$res_update = pg_exec ($con,$sql_update);
			$msg_erro .= pg_errormessage($con);
		}
		else{
			$msg_erro="Informe a quantidade de peças que vai ser devolvida!";
		}
		if (strlen($msg_erro)>0) break;
	}

	if (strlen($msg_erro) == 0) {
		$resX = pg_exec ($con,"COMMIT TRANSACTION");
		echo $lista_pecas;
	}else{
		$resX = pg_exec ($con,"ROLLBACK TRANSACTION");
	}

}

if (strlen($btn_acao) > 0 AND $btn_acao=="digitou_as_notas") {

	$qtde_pecas = trim($_POST['qtde_pecas']);
	$numero_linhas = trim($_POST['qtde_linha']);
	$numero_de_notas = trim($_POST['numero_de_notas']);
	$data_preenchimento = date("Y-m-d");

	$sql = "SELECT posto,distribuidor,extrato_devolucao
			FROM tbl_faturamento
			WHERE distribuidor=$login_posto
			AND posto=13996
			AND extrato_devolucao=$extrato";
	$res = pg_exec ($con,$sql);
	if (pg_numrows($res)>0){
		header("Location: new_extrato_posto.php");
		exit();
	}
	
	$resX = pg_exec ($con,"BEGIN TRANSACTION");

	for($i=0;$i<$numero_de_notas;$i++){

			
			$nota_fiscal = trim($_POST["nota_fiscal_$i"]);
				
			$nota_fiscal = str_replace(".","",$nota_fiscal);
			$nota_fiscal = str_replace(",","",$nota_fiscal);
			$nota_fiscal = str_replace("-","",$nota_fiscal);


			$total_nota = trim($_POST["id_nota_$i-total_nota"]);
			$base_icms = trim($_POST["id_nota_$i-base_icms"]);
			$valor_icms = trim($_POST["id_nota_$i-valor_icms"]);
			$base_ipi = trim($_POST["id_nota_$i-base_ipi"]);
			$valor_ipi = trim($_POST["id_nota_$i-valor_ipi"]);

			$linha_nota = trim($_POST["id_nota_$i-linha"]);

			$qtde_peca_na_nota = trim($_POST["id_nota_$i-qtde_itens"]);

			$sql = "INSERT INTO tbl_faturamento		  
					(fabrica, emissao,saida, posto, distribuidor, total_nota, nota_fiscal, serie, linha, natureza, base_icms, valor_icms, base_ipi, valor_ipi, extrato_devolucao, obs)
					VALUES ($login_fabrica,'$data_preenchimento','$data_preenchimento',13996,$login_posto,$total_nota,'$nota_fiscal','UN',$linha_nota,'Devolução de peças em garantia',$base_icms,$valor_icms,$base_ipi,$valor_ipi,$extrato,'Devolução de peças do posto para à Fábrica')";
			$res = pg_exec ($con,$sql);

			$sql = "SELECT CURRVAL ('seq_faturamento')";
			$resZ = pg_exec ($con,$sql);
			$faturamento_codigo = pg_result ($resZ,0,0);


			for($x=1;$x<=$qtde_peca_na_nota;$x++){


				$lgr = trim($_POST["id_item_LGR_$x-$i"]);
				$peca = trim($_POST["id_item_peca_$x-$i"]);
				$peca_preco = trim($_POST["id_item_preco_$x-$i"]);
				$peca_qtde_total_nf = trim($_POST["id_item_qtde_$x-$i"]);
				$peca_aliq_icms = trim($_POST["id_item_icms_$x-$i"]);
				$peca_aliq_ipi = trim($_POST["id_item_ipi_$x-$i"]);
				$peca_total_item = trim($_POST["id_item_total_$x-$i"]);


				$peca_base_icms=0;
				$peca_valor_icms=0;

				$peca_base_ipi=0;
				$peca_valor_ipi=0;

				if ($peca_aliq_icms>0){
					$peca_base_icms = $peca_qtde_total_nf*$peca_preco;
					$peca_valor_icms= $peca_qtde_total_nf*$peca_preco*$peca_aliq_icms/100;
				}

				if ($peca_aliq_ipi>0){
					$peca_base_ipi = $peca_qtde_total_nf*$peca_preco;
					$peca_valor_ipi= $peca_qtde_total_nf*$peca_preco*$peca_aliq_ipi/100;
				}


				$sql = "INSERT INTO tbl_faturamento_item		  
					(faturamento,peca,qtde,preco,aliq_icms,aliq_ipi,base_icms,valor_icms,linha,base_ipi,valor_ipi)
					VALUES ($faturamento_codigo,$peca,$peca_qtde_total_nf,$peca_preco,$peca_aliq_icms,$peca_aliq_ipi,$peca_base_icms,$peca_valor_icms,$linha_nota,$peca_base_ipi,$peca_valor_ipi)";
				$res = pg_exec ($con,$sql);


			}


	}

	if (strlen($msg_erro) == 0) {
		$resX = pg_exec ($con,"COMMIT TRANSACTION");
		//$resX = pg_exec ($con,"ROLLBACK TRANSACTION");
	}else{
		$resX = pg_exec ($con,"ROLLBACK TRANSACTION");
	}
}


// para redirecionar para a pagina antiga se a nota já foi digitada. Novas notas irão para esta pagina
if (strlen($extrato)>0){
	$sql = "SELECT	*
		FROM tbl_faturamento 
		JOIN tbl_faturamento_item USING(faturamento)
		WHERE tbl_faturamento.extrato_devolucao = $extrato
		AND tbl_faturamento.fabrica= $login_fabrica
		AND tbl_faturamento.posto=$login_posto
		AND tbl_faturamento_item.extrato_devolucao is not null";
	$res = pg_exec ($con,$sql);
	$qntos_digitou = pg_numrows($res);

	if ($qntos_digitou==0){
		$sql = "SELECT	*
			FROM tbl_extrato_devolucao 
			WHERE extrato = $extrato";
		$res = pg_exec ($con,$sql);
		$qntos_tem = pg_numrows($res);
		
		$sql = "SELECT	*
			FROM tbl_extrato_devolucao 
			WHERE extrato = $extrato
			AND nota_fiscal is not null";
		$res = pg_exec ($con,$sql);
		$qntos_falta = pg_numrows($res);
		
		if ($qntos_falta == $qntos_tem AND $qntos_tem>0) {
			header("Location: new_extrato_posto_devolucao.php?extrato=$extrato");
			exit();
		}
	}
}

$msg = "";



$layout_menu = "os";
$title = "Peças Retornáveis do Extrato";

include "cabecalho.php";
?>

<style type="text/css">
.Tabela{
	border:1px solid #596D9B;
	background-color:#596D9B;
}
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
	font-size: 12px;
	font-weight: normal;
	border: 0px solid;
	background-color: #D9E2EF
}

.table_line2 {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
}

</style>
<script language='javascript'>



</script>
<div id='loading'></div>

<? if (strlen($msg) > 0) { ?>
<table width="650" border="0" align="center" class="error">
	<tr>
		<td><?echo $msg?></td>
	</tr>
</table>
<? } ?>

<center>
<br>
<table width='550' align='center'>
<tr><td>
<b>Conforme determina a legislação local</b><p>
Para toda nota fiscal de peças enviadas em garantia deve haver nota fiscal de devolução de todas as peças nos mesmos valores, quantidades e com os mesmos destaques de impostos obrigatoriamente.
<br>
O valor da mão-de-obra será exibido somente após confirmação da Nota Fiscal de Devolução.
<br>
TODAS as peças de Áudio e Vídeo devem retornar junto com esta Nota fiscal.
<br>
As peças das linhas de eletroportáteis e branca devem ficar no posto por 90 dias para inspeção ou de acordo com os procedimentos definidos por seu DISTRIBUIDOR.
<br>
</td></tr></table>
<br>

<?

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



<?

$sql = "UPDATE tbl_faturamento_item SET linha = (SELECT tbl_produto.linha FROM tbl_produto 
				JOIN tbl_lista_basica ON tbl_produto.produto = tbl_lista_basica.produto AND tbl_faturamento_item.peca = tbl_lista_basica.peca LIMIT 1)
		FROM tbl_faturamento
		WHERE tbl_faturamento_item.faturamento = tbl_faturamento.faturamento
		AND tbl_faturamento.fabrica = $login_fabrica 
		AND tbl_faturamento.extrato_devolucao = $extrato";
$res = pg_exec ($con,$sql);

if ($login_fabrica == 3) {
	$sql = "UPDATE tbl_faturamento_item SET linha = 2
			FROM tbl_faturamento
			WHERE tbl_faturamento_item.faturamento = tbl_faturamento.faturamento
			AND tbl_faturamento.fabrica = $login_fabrica 
			AND tbl_faturamento.extrato_devolucao = $extrato
			AND tbl_faturamento_item.linha IS NULL";
	$res = pg_exec ($con,$sql);
}

$sql = "SELECT * FROM tbl_posto WHERE posto = $login_posto";
$resX = pg_exec ($con,$sql);
$estado_origem = pg_result ($resX,0,estado);


$sql = "SELECT  faturamento,
				extrato_devolucao,
				serie,
				linha,
				nota_fiscal,
				distribuidor,
				NULL as produto_acabado
		FROM tbl_faturamento
		WHERE posto=13996
		AND distribuidor=$login_posto
		AND fabrica=$login_fabrica
		AND extrato_devolucao=$extrato
		ORDER BY faturamento ASC";
$res = pg_exec ($con,$sql);
$jah_digitado=pg_numrows ($res);

if ($jah_digitado==0){
	$sql = "SELECT  DISTINCT tbl_faturamento.extrato_devolucao,
			tbl_faturamento.distribuidor,
			tbl_faturamento_item.linha,
			CASE WHEN produto_acabado IS TRUE THEN 'TRUE' ELSE 'NOT TRUE' END AS produto_acabado,
			tbl_faturamento.serie
		FROM    tbl_faturamento 
		JOIN    tbl_faturamento_item USING (faturamento) 
		JOIN    tbl_peca             USING (peca)
		WHERE   tbl_faturamento.extrato_devolucao = $extrato
		AND     tbl_faturamento.posto             = $login_posto
		AND     (tbl_faturamento.cfop ILIKE '59%' OR tbl_faturamento.cfop ILIKE '69%')
		ORDER BY produto_acabado,linha,serie,distribuidor,extrato_devolucao";
	$res = pg_exec ($con,$sql);
	//		AND     tbl_faturamento_item.aliq_icms > 0 
}




if (strlen($numero_linhas)==0 AND $jah_digitado==0){ ?>
<br>
<TABLE width="650" align="center" border="0" cellspacing="0" cellpadding="2">
<TR>
	<TD colspan="10" class="menu_top"><div align="center"><b>Mudanças no Preenchimento de Notas Fiscais de Devolução</b></div></TD>
</TR>
<TR>
	<TD colspan='8' class="table_line" style='padding:10px'>
	Para o preenchimento das notas de devolução:<br><br>
	<b>-></b> Verifique a quantidade de peças que devem ser devolvidas.<br>
	<b>-></b> Preencha a quantidade de peças que vão ser devolvidas. <br>
	<b>-></b> Informe no fim da página a quantidade de itens que a sua nota fiscal possui.<br>
	<b>-></b> Depois de preenchida, clique em GRAVAR.<br>
	<br> Aparecerá a confirmação das notas já separadas<br>
	</TD>
</TR>
</table>
<br>
<TABLE width="650" align="center" border="0" cellspacing="0" cellpadding="2">
<TR>
	<td style='padding-left:280px;padding-right:60px'>
	<IMG SRC="imagens/setona.gif" WIDTH="31" HEIGHT="52" BORDER="0" ALT="" align='right'>
	Preencha esta coluna com as quantidades de peças que vão ser devolvidas
	</TD>

</TR>
</table><br>
<? } 


if (pg_numrows ($res) > 0) {

	echo "<form method='post' action='$PHP_SELF' name='frm_devolucao'>";
	echo "<input type='hidden' name='extrato' value='$extrato'>";
	echo "<input type='hidden' id='botao_acao' name='botao_acao' value=''>\n";

	$contador=0;
	$qtde_for = pg_numrows ($res);
	for ($i=0; $i < $qtde_for; $i++) {

		$distribuidor     = trim (pg_result ($res,$i,distribuidor));
		$produto_acabado  = trim (pg_result ($res,$i,produto_acabado));
		$serie            = trim (pg_result ($res,$i,serie));
		$linha		  = trim (pg_result ($res,$i,linha));
		$extrato_devolucao= trim (pg_result ($res,$i,extrato_devolucao));

		if ($jah_digitado>0){
			echo $faturamento_nota = trim (pg_result ($res,$i,faturamento));
			$nota_fiscal      = trim (pg_result ($res,$i,nota_fiscal));
			$distribuidor     = "";
			$produto_acabado  = "";
			$numero_linhas=500;
		}


		$sql = "SELECT * FROM tbl_linha WHERE linha = $linha" ;
		$resZ = pg_exec ($con,$sql);
		$linha_nome = pg_result ($resZ,0,nome);

	
		$pecas_produtos = "PEÇAS";
		if ($produto_acabado == "TRUE") $pecas_produtos = "PRODUTOS";
		

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

		$cabecalho ="<br><br><br>\n";
		$cabecalho .= "<table border='1' cellspacing='0' cellpadding='3' border-color='#000' style='border-collapse:collapse;font-size:12px' width='650' >\n";

		$cabecalho .= "<tr align='center'>\n";
		$cabecalho .= "<td bgcolor='#E3E4E6' colspan='3'>\n";
		$cabecalho .= "Nota de Devolução de <b>$pecas_produtos</b> Linha: $linha_nome<br>\n";
		$cabecalho .= "</td>\n";
		$cabecalho .= "</tr>\n";

		$cabecalho .= "<tr>\n";
		$cabecalho .= "<td>Natureza <br> <b>Devolução de Garantia</b> </td>\n";
		$cabecalho .= "<td>CFOP <br> <b>$cfop</b> </td>\n";
		$cabecalho .= "<td>Emissao <br> <b>$data</b> </td>\n";
		$cabecalho .= "</tr>\n";
		$cabecalho .= "</table>\n";

		$cnpj = substr ($cnpj,0,2) . "." . substr ($cnpj,2,3) . "." . substr ($cnpj,5,3) . "/" . substr ($cnpj,8,4) . "-" . substr ($cnpj,12,2);
		$cabecalho .= "<table border='1' cellspacing='0' cellpadding='3' border-color='#000' style='border-collapse:collapse;font-size:12px' width='650' >\n";
		$cabecalho .= "<tr>\n";
		$cabecalho .= "<td>Razão Social <br> <b>$razao</b> </td>\n";
		$cabecalho .= "<td>CNPJ <br> <b>$cnpj</b> </td>\n";
		$cabecalho .= "<td>Inscrição Estadual <br> <b>$ie</b> </td>\n";
		$cabecalho .= "</tr>\n";
		$cabecalho .= "</table>\n";

		$cep = substr ($cep,0,2) . "." . substr ($cep,2,3) . "-" . substr ($cep,5,3) ;
		$cabecalho .= "<table border='1' cellspacing='0' cellpadding='3' border-color='#000' style='border-collapse:collapse;font-size:12px' width='650' >\n";
		$cabecalho .= "<tr>\n";
		$cabecalho .= "<td>Endereço <br> <b>$endereco </b> </td>\n";
		$cabecalho .= "<td>Cidade <br> <b>$cidade</b> </td>\n";
		$cabecalho .= "<td>Estado <br> <b>$estado</b> </td>\n";
		$cabecalho .= "<td>CEP <br> <b>$cep</b> </td>\n";
		$cabecalho .= "</tr>\n";
		$cabecalho .= "</table>\n";

		if (strlen($numero_linhas)>0){
			echo $cabecalho;
		}

		$topo ="";
		$topo .= "<table border='1' cellspacing='0' cellpadding='3' border-color='#000' style='border-collapse:collapse;font-size:12px' width='650' id='tbl_pecas_$i'>\n";
		$topo .=  "<thead>\n";
		if (strlen($numero_linhas)==0){
			$topo .=  "<tr align='center'>\n";
			$topo .=  "<td bgcolor='#E3E4E6' colspan='4'>\n";
			$topo .=  "<b>$pecas_produtos</b> Linha: $linha_nome<br>\n";
			$topo .=  "</td>\n";
			$topo .=  "</tr>\n";
		}
		$topo .=  "<tr align='center'>\n";
		$topo .=  "<td><b>Código</b></td>\n";
		$topo .=  "<td><b>Descrição</b></td>\n";
		$topo .=  "<td><b>Qtde.</b></td>\n";

		if (strlen($numero_linhas)==0){
			$topo .=  "<td><b>Qtde. Devolver</b></td>\n";
		}
		else{
			$topo .=  "<td><b>Preço</b></td>\n";
			$topo .=  "<td><b>Total</b></td>\n";
			$topo .=  "<td><b>% ICMS</b></td>\n";
			$topo .=  "<td><b>% IPI</b></td>\n";
		}
		$topo .=  "</tr>\n";
		$topo .=  "</thead>\n";

		echo $topo;


		if (strlen($numero_linhas)>0){
			$sql_adicional_peca=" AND tbl_extrato_lgr.qtde_nf>0";
		}
		else{
			$sql_adicional_peca="";
		}


		if ($jah_digitado>0){
			$sql = "SELECT  
					tbl_peca.peca, 
					tbl_peca.referencia, 
					tbl_peca.descricao, 
					tbl_peca.ipi, 
					tbl_faturamento_item.aliq_icms,
					tbl_faturamento_item.aliq_ipi,
					tbl_faturamento_item.preco,
					tbl_extrato_lgr.qtde AS qtde_total_item,
					tbl_extrato_lgr.qtde_nf AS qtde_total_item_nf,
					tbl_extrato_lgr.extrato_lgr AS extrato_lgr,
					(tbl_extrato_lgr.qtde * tbl_faturamento_item.preco) AS total_item,
					SUM (tbl_faturamento_item.base_icms) AS base_icms, 
					SUM (tbl_faturamento_item.valor_icms) AS valor_icms,
					SUM (tbl_faturamento_item.base_ipi) AS base_ipi,
					SUM (tbl_faturamento_item.valor_ipi) AS valor_ipi
					FROM tbl_peca
					JOIN tbl_faturamento_item USING (peca)
					JOIN tbl_faturamento      USING (faturamento)
					JOIN tbl_extrato_lgr ON tbl_extrato_lgr.extrato=tbl_faturamento.extrato_devolucao AND tbl_extrato_lgr.peca=tbl_faturamento_item.peca
					WHERE tbl_faturamento.fabrica = $login_fabrica
					AND   tbl_faturamento.extrato_devolucao = $extrato
					AND   tbl_faturamento.faturamento=$faturamento_nota 
					GROUP BY tbl_peca.peca, tbl_peca.referencia, tbl_peca.descricao, tbl_peca.ipi, tbl_faturamento_item.aliq_icms,tbl_faturamento_item.aliq_ipi,tbl_faturamento_item.preco,tbl_extrato_lgr.qtde,total_item,qtde_total_item_nf,extrato_lgr
					ORDER BY tbl_peca.referencia";
			//AND   tbl_faturamento_item.extrato_devolucao IS NOT NULL		
		}
		else{

			$sql = "SELECT  
					tbl_peca.peca, 
					tbl_peca.referencia, 
					tbl_peca.descricao, 
					tbl_peca.ipi, 
					tbl_faturamento_item.aliq_icms,
					tbl_faturamento_item.aliq_ipi,
					tbl_faturamento_item.preco,
					tbl_extrato_lgr.qtde AS qtde_total_item,
					tbl_extrato_lgr.qtde_nf AS qtde_total_item_nf,
					tbl_extrato_lgr.extrato_lgr AS extrato_lgr,
					(tbl_extrato_lgr.qtde * tbl_faturamento_item.preco) AS total_item,
					SUM (tbl_faturamento_item.base_icms) AS base_icms, 
					SUM (tbl_faturamento_item.valor_icms) AS valor_icms,
					SUM (tbl_faturamento_item.base_ipi) AS base_ipi,
					SUM (tbl_faturamento_item.valor_ipi) AS valor_ipi
					FROM tbl_peca
					JOIN tbl_faturamento_item USING (peca)
					JOIN tbl_faturamento      USING (faturamento)
					JOIN tbl_extrato_lgr ON tbl_extrato_lgr.extrato=tbl_faturamento.extrato_devolucao AND tbl_extrato_lgr.peca=tbl_faturamento_item.peca
					WHERE tbl_faturamento.fabrica = $login_fabrica
					AND   tbl_faturamento.posto   = $login_posto
					AND   tbl_faturamento_item.linha = $linha
					AND   tbl_faturamento.extrato_devolucao = $extrato
					AND   (tbl_faturamento.cfop ILIKE '59%' OR tbl_faturamento.cfop ILIKE '69%')
					AND   $condicao_1
					AND   $condicao_2
					$sql_adicional_peca
					AND   tbl_faturamento.emissao > '2005-10-01'
					AND   tbl_faturamento.serie = '$serie'
					GROUP BY tbl_peca.peca, tbl_peca.referencia, tbl_peca.descricao, tbl_peca.ipi, tbl_faturamento_item.aliq_icms,tbl_faturamento_item.aliq_ipi,tbl_faturamento_item.preco,tbl_extrato_lgr.qtde,total_item,qtde_total_item_nf,extrato_lgr
					ORDER BY tbl_peca.referencia";
			//AND   tbl_faturamento_item.extrato_devolucao IS NOT NULL		
			//							AND   tbl_faturamento_item.aliq_icms > 0

			$sql_nf = "SELECT DISTINCT tbl_faturamento.nota_fiscal
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
					AND   tbl_faturamento.emissao > '2005-10-01'
					AND   tbl_faturamento.serie = '$serie'
					ORDER BY tbl_faturamento.nota_fiscal ";
//					echo $sql;
			$resNF = pg_exec ($con,$sql_nf);
			$notas_fiscais    = "";
			for ($y = 0 ; $y < pg_numrows ($resNF) ; $y++) {
				$notas_fiscais .= pg_result ($resNF,$y,nota_fiscal) . ", ";
			}
			
		}

		//echo "<br><br>$sql";
		//exit();

		$resX = pg_exec ($con,$sql);
		$total_base_icms  = 0;
		$total_valor_icms = 0;
		$total_base_ipi   = 0;
		$total_valor_ipi  = 0;
		$total_nota       = 0;
		$aliq_final       = 0;

		for ($x = 0 ; $x < pg_numrows ($resX) ; $x++) {

			if (strlen($numero_linhas)>0){
				if ($x%$numero_linhas==0 AND $x>0){

					//if ($aliq_final > 0) $total_valor_icms = $total_base_icms * $aliq_final / 100;

					$total_geral=$total_nota+$total_valor_ipi;

					echo "</table>\n";
					echo "<table border='1' cellspacing='0' cellpadding='3' border-color='#000' style='border-collapse:collapse;font-size:12px' width='650' >\n";
					echo "<tr>\n";
					echo "<td>Base ICMS <br> <b> " . number_format ($total_base_icms,2,",",".") . " </b> </td>\n";
					echo "<td>Valor ICMS <br> <b> " . number_format ($total_valor_icms,2,",",".") . " </b> </td>\n";
					echo "<td>Base IPI <br> <b> " . number_format ($total_base_ipi,2,",",".") . " </b> </td>\n";
					echo "<td>Valor IPI <br> <b> " . number_format ($total_valor_ipi,2,",",".") . " </b> </td>\n";
					echo "<td>Total da Nota <br> <b> " . number_format ($total_geral,2,",",".") . " </b> </td>\n";
					echo "</tr>\n";
					if (strlen($numero_linhas)>0 AND strlen($notas_fiscais)>0){
						echo "<tfoot>";
						echo "<tr>";
						echo "<td colspan='8'> Referente a suas NFs. " . $notas_fiscais . " da Série $serie</td>";
						echo "</tr>";
						echo "</tfoot>";
					}
					echo "</table>\n";
					if (strlen ($nota_fiscal)==0) {
						echo "\n<br>";
						echo "<input type='hidden' name='id_nota_$numero_nota-linha' value='$linha'>\n";
						echo "<input type='hidden' name='id_nota_$numero_nota-qtde_itens' value='$item_nota'>\n";
						echo "<input type='hidden' name='id_nota_$numero_nota-total_nota' value='$total_geral'>\n";
						echo "<input type='hidden' name='id_nota_$numero_nota-base_icms' value='$total_base_icms'>\n";
						echo "<input type='hidden' name='id_nota_$numero_nota-valor_icms' value='$total_valor_icms'>\n";
						echo "<input type='hidden' name='id_nota_$numero_nota-base_ipi' value='$total_base_ipi'>\n";
						echo "<input type='hidden' name='id_nota_$numero_nota-valor_ipi' value='$total_valor_ipi'>\n";
						echo "<center>";
						echo "<b>Confirme a emissão da sua Nota de Devolução</b><br>Este número não poderá ser alterado<br>";
						echo "<br><IMG SRC='imagens/setona_h.gif' WIDTH='53' HEIGHT='29' BORDER='0' align='absmiddle'>Número da Nota: <input type='text' name='nota_fiscal_$numero_nota' size='10' maxlength='6' value='$nota_fiscal'>";
						echo "<br><br>";
						$numero_nota++;
					}else{
						if (strlen ($nota_fiscal) >0)
							echo "<h1><center>Nota de Devolução $nota_fiscal</center></h1>\n";
					}

					echo $cabecalho;
					echo $topo;
					$total_base_icms  = 0;
					$total_valor_icms = 0;
					$total_base_ipi   = 0;
					$total_valor_ipi  = 0;
					$total_nota       = 0;

					$item_nota=0;
				}
			}
			$contador++;
			$item_nota++;

			$peca        = pg_result ($resX,$x,peca);
			$peca_referencia = pg_result ($resX,$x,referencia);
			$peca_descricao = pg_result ($resX,$x,descricao);

			$peca_preco = pg_result ($resX,$x,preco);

			$qtde_total_item     = pg_result ($resX,$x,qtde_total_item);
			$qtde_total_item_nf  = pg_result ($resX,$x,qtde_total_item_nf);

			$extrato_lgr  = pg_result ($resX,$x,extrato_lgr);
			
			$total_item  = pg_result ($resX,$x,total_item);
			$base_icms   = pg_result ($resX,$x,base_icms);
			$valor_icms  = pg_result ($resX,$x,valor_icms);
			$aliq_icms   = pg_result ($resX,$x,aliq_icms);
			$base_ipi   = pg_result ($resX,$x,base_ipi);
			$aliq_ipi   = pg_result ($resX,$x,aliq_ipi);
			$valor_ipi   = pg_result ($resX,$x,valor_ipi);

			
			$ipi = pg_result ($resX,$x,ipi);


			if (strlen($qtde_total_item_nf)==0)
				$qtde_total_item_nf=$qtde_total_item;

			if ($qtde_total_item_nf==0)
				$preco       =  $total_item;
			else
				$preco       =  $total_item / $qtde_total_item_nf;
			
			$total_item  = $peca_preco * $qtde_total_item_nf;

//			$nota_fiscal_item = pg_result ($resX,$x,nota_fiscal);
//			$faturamento = pg_result ($resX,$x,faturamento);

			if (strlen ($base_icms)  == 0) $base_icms = $total_item ;
			if (strlen ($valor_icms) == 0) $valor_icms = $total_item * $aliq_icms / 100;


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


			echo "<tr bgcolor='#ffffff' style='font-color:#000000 ; align:left ; font-size:10px ' >\n";
			echo "<td align='left'>";
			echo "$peca_referencia";
			echo "<input type='hidden' name='id_item_LGR_$item_nota-$numero_nota' value='$extrato_lgr'>\n";
			echo "<input type='hidden' name='id_item_peca_$item_nota-$numero_nota' value='$peca'>\n";
			echo "<input type='hidden' name='id_item_preco_$item_nota-$numero_nota' value='$preco'>\n";
			echo "<input type='hidden' name='id_item_qtde_$item_nota-$numero_nota' value='$qtde_total_item_nf'>\n";
			echo "<input type='hidden' name='id_item_icms_$item_nota-$numero_nota' value='$aliq_icms'>\n";
			echo "<input type='hidden' name='id_item_ipi_$item_nota-$numero_nota' value='$aliq_ipi'>\n";
			echo "<input type='hidden' name='id_item_total_$item_nota-$numero_nota' value='$total_item'>\n";
			echo "</td>\n";
			echo "<td align='left'>$peca_descricao</td>\n";

			if (strlen($numero_linhas)==0){
				echo "<td align='center'>$qtde_total_item</td>\n";
				echo "<td align='center' bgcolor='#FAE7A5'>\n
						<input type='hidden' name='item_$contador' value='$extrato_lgr'>\n
						<input type='hidden' name='peca_$contador' value='$peca'>\n
						<input style='text-align:right' type='text' size='4' maxlength='4' name='$extrato_lgr' value='$qtde_total_item_nf' onblur='javascript:if (this.value > $qtde_total_item || this.value==\"\" ) {alert(\"Quantidade superior!\");this.value=\"$qtde_total_item\"}'>\n
						</td>\n";
			}
			else{
				echo "<td align='center'>$qtde_total_item_nf</td>\n";
				echo "<td align='right' nowrap>" . number_format ($preco,2,",",".") . "</td>\n";
				echo "<td align='right' nowrap>" . number_format ($total_item,2,",",".") . "</td>\n";
				echo "<td align='right'>$aliq_icms</td>\n";
				echo "<td align='right'>$aliq_ipi</td>\n";
			}
			echo "</tr>\n";

			$total_base_icms  += $base_icms;
			$total_valor_icms += $valor_icms;
			$total_base_ipi  += $base_ipi;
			$total_valor_ipi += $valor_ipi;
			$total_nota       += $total_item;
		}
		if (strlen($numero_linhas)>0 AND strlen($notas_fiscais)>0){
			echo "<tfoot>";
			echo "<tr>";
			echo "<td colspan='8'> Referente a suas NFs. " . $notas_fiscais . " da Série $serie</td>";
			echo "</tr>";
			echo "</tfoot>";
		}

		echo "</table>\n";

//		if ($aliq_final > 0) $total_valor_icms = $total_base_icms * $aliq_final / 100;

		if (strlen($numero_linhas)>0) {
			echo "<table border='1' cellspacing='0' cellpadding='3' border-color='#000' style='border-collapse:collapse;font-size:12px' width='650' >";
			echo "<tr>";
			echo "<td>Base ICMS <br> <b> " . number_format ($total_base_icms,2,",",".") . " </b> </td>";
			echo "<td>Valor ICMS <br> <b> " . number_format ($total_valor_icms,2,",",".") . " </b> </td>";
			echo "<td>Base IPI <br> <b> " . number_format ($total_base_ipi,2,",",".") . " </b> </td>";
			echo "<td>Valor IPI <br> <b> " . number_format ($total_valor_ipi,2,",",".") . " </b> </td>";
			echo "<td>Total da Nota <br> <b> " . number_format ($total_nota+$total_valor_ipi,2,",",".") . " </b> </td>";
			echo "</tr>";
			echo "</table>";

		}
	
		if (strlen($numero_linhas)>0 AND strlen ($nota_fiscal) == 0) {

			$total_geral=$total_nota+$total_valor_ipi;

			echo "\n<br>";
			echo "<input type='hidden' name='id_nota_$numero_nota-linha' value='$linha'>\n";
			echo "<input type='hidden' name='id_nota_$numero_nota-qtde_itens' value='$item_nota'>\n";
			echo "<input type='hidden' name='id_nota_$numero_nota-total_nota' value='$total_geral'>\n";
			echo "<input type='hidden' name='id_nota_$numero_nota-base_icms' value='$total_base_icms'>\n";
			echo "<input type='hidden' name='id_nota_$numero_nota-valor_icms' value='$total_valor_icms'>\n";
			echo "<input type='hidden' name='id_nota_$numero_nota-base_ipi' value='$total_base_ipi'>\n";
			echo "<input type='hidden' name='id_nota_$numero_nota-valor_ipi' value='$total_valor_ipi'>\n";
			echo "<center>";
			echo "<b>Confirme a emissão da sua Nota de Devolução</b><br>Este número não poderá ser alterado<br>";
			echo "<br><IMG SRC='imagens/setona_h.gif' WIDTH='53' HEIGHT='29' BORDER='0' align='absmiddle'>Número da Nota: <input type='text' name='nota_fiscal_$numero_nota' size='10' maxlength='6' value='$nota_fiscal'>";
			echo "<br><br>";
			$item_nota=0;
			$numero_nota++;
		}else{
			if (strlen ($nota_fiscal) >0)
				echo "<h1><center>Nota de Devolução $nota_fiscal</center></h1>";
		}

	}

	if (strlen($numero_linhas)==0){
		echo "<br>
				<input type='hidden' name='qtde_pecas' value='$contador'>
				<IMG SRC='imagens/setona_h.gif' WIDTH='53' HEIGHT='29' BORDER='0' align='absmiddle'> 
				Quantidade de itens que cabe no preenchimento de cada Nota Fiscal sua:
				<input type='text' size='5' maxlength='3' value='' name='qtde_linha'>
				<br><br>
				<input type='button' id='fechar' value='Gravar' name='gravar' onclick=\"javascript:
				if(document.frm_devolucao.qtde_linha.value=='' || document.frm_devolucao.qtde_linha.value=='0')
						alert('Informe a quantidade de linhas!!');
				else{
					document.frm_devolucao.botao_acao.value='digitou_qtde';
					this.form.submit();
					}
					\"><br><br>
			  ";
	}
	else{
		if ($jah_digitado==0){
			echo "<br><br><br>
				<input type='hidden' name='qtde_linha' value='$numero_linhas'>
				<input type='hidden' name='numero_de_notas' value='$numero_nota'>
				
				Preencha as notas acima e clique no botão abaixo para confirmar!
				<br><br>
				<input type='button' value='Confirmar notas de devolução' name='gravar' onclick=\"javascript:
					document.frm_devolucao.botao_acao.value='digitou_as_notas';
					this.form.submit();
					\"><br>";
			echo "<br><br><input type='button' value='Voltar a Tela Anterior' name='gravar' onclick=\"javascript:
				if(confirm('Deseja voltar?')) window.location='$PHP_SELF?extrato=$extrato';\">";
		}
	}
	echo "</form>";

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
	if(pg_numrows($resX)>0 AND strlen($nota_fiscal)>0){

		echo "<br><table border='1' cellspacing='0' cellpadding='3' border-color='#000' style='border-collapse:collapse;font-size:12px' width='650' >";
		echo "<tr align='center'>\n";
		echo "<td bgcolor='#E3E4E6' colspan='3'>\n";
		echo "<b>Nota de Devolução de <b>Produtos Ressarcidos</b>" ;
		echo "</td>\n";
		echo "</tr>\n";
		echo "<tr>";
		echo "<td>Natureza <br> <b>Simples Remessa</b> </td>";
		echo "<td>CFOP <br> <b>$cfop</b> </td>";
		echo "<td>Emissao <br> <b>$data</b> </td>";
		echo "</tr>";
		echo "</table>";
	
		echo "<table border='1' cellspacing='0' cellpadding='3' border-color='#000' style='border-collapse:collapse;font-size:12px' width='650' >";
		echo "<tr>";
		echo "<td>Razão Social <br> <b>$razao</b> </td>";
		echo "<td>CNPJ <br> <b>$cnpj</b> </td>";
		echo "<td>Inscrição Estadual <br> <b>$ie</b> </td>";
		echo "</tr>";
		echo "</table>";
	
	
		echo "<table border='1' cellspacing='0' cellpadding='3' border-color='#000' style='border-collapse:collapse;font-size:12px' width='650' >";
		echo "<tr>";
		echo "<td>Endereço <br> <b>$endereco </b> </td>";
		echo "<td>Cidade <br> <b>$cidade</b> </td>";
		echo "<td>Estado <br> <b>$estado</b> </td>";
		echo "<td>CEP <br> <b>$cep</b> </td>";
		echo "</tr>";
		echo "</table>";

		echo "<table border='1' cellspacing='0' cellpadding='3' border-color='#000' style='border-collapse:collapse;font-size:12px' width='650' >";
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
	//$res = pg_exec ($con,$sql);

}
?>

<p><p>

<? include "rodape.php"; ?>
