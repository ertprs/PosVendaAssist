<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'includes/funcoes.php';


include "autentica_admin.php";

include "funcoes.php";
include "cabecalho.php";

$layout_menu = "gerencia";
$title = "RELATÓRIO - NÚMERO DE SÉRIE";

?>

<style type="text/css">
.Titulo {
	text-align: center;
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 12px;
	font-weight: bold;
	color: #FFFFFF;
	background-color: #596D9B;
}
.Conteudo {
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	background-color: #f1f6f4;
}

table.tabela tr td{
    font-family: verdana;
    font-size: 11px;
    border-collapse: collapse;
    border:1px solid #596d9b;
}


</style>

<? include "javascript_pesquisas.php"; ?>

<script language="javascript" src="js/cal2.js"></script>
<script language="javascript" src="js/cal_conf2.js"></script>
<script language="javascript">
function MostraEsconde(dados){
//alert("takashi + "+dados);
	if (document.getElementById){
		var style2 = document.getElementById(dados);
		if (style2==false) return;
		if (style2.style.display=="block"){
			style2.style.display = "none";
		}else{
			style2.style.display = "block";
			retornaLinha(dados);
		}
	}
}
function AbreOS(produto,nserie,constatado,solucao){
//alert("relatorio_defeito_serie_fabricacao_os2.php?produto=" + produto + "&nserie=" + nserie + "&constatado=" + constatado + "&solucao=" + solucao);
	janela = window.open("relatorio_defeito_serie_fabricacao_os2.php?produto=" + produto + "&nserie=" + nserie + "&constatado=" + constatado + "&solucao=" + solucao + "&tipo_os=<?echo $tipo_os;?>","os",'resizable=1,scrollbars=yes,width=720,height=450,top=50,left=50');
	janela.focus();
}
function AbrePeca(produto,peca,nserie){

	//alert("relatorio_defeito_serie_fabricacao_os2.php?produto=" + produto + "&nserie=" + nserie + "&constatado=" + constatado + "&solucao=" + solucao);
	janela = window.open("relatorio_defeito_serie_fabricacao_peca.php?produto=" + produto + "&nserie=" + nserie + "&peca=" + peca + "&tipo_os=<?echo $tipo_os;?>","peca",'resizable=1,scrollbars=yes,width=720,height=450,top=50,left=50');
	janela.focus();
}
</script>
<br>

<?


$tipo_os = $_GET['tipo_os'];
$cond_5 = "1=1";
if (strlen ($tipo_os)  > 0) $cond_5 = " tbl_os.consumidor_revenda = '$tipo_os' ";
$produto = $_GET['produto'];
$nserie = $_GET['nserie'];
if (strlen($nserie) > 0 && strlen($produto) > 0) {
	$sql = "SELECT 	count(distinct tbl_os.os) as qtde_total
			FROM tbl_os
			JOIN tbl_os_extra using(os)
			JOIN tbl_produto using(produto)
			JOIN tbl_os_produto on tbl_os_produto.os = tbl_os.os
			join tbl_os_item on tbl_os_item.os_produto = tbl_os_produto.os_produto
			WHERE tbl_os.fabrica= $login_fabrica
			AND tbl_os.produto = $produto
			AND tbl_os.serie like '$nserie%'
			AND tbl_os.solucao_os <>127
			and $cond_5
			AND tbl_os_extra.extrato NOTNULL
			GROUP BY tbl_os.produto";
	$res = pg_exec($con,$sql);
	$total_os = pg_result($res,0,0);
	//echo nl2br($sql);

//echo $sql;
	$sql = "SELECT 	count(distinct tbl_os.os) as qtde
			FROM tbl_os
			JOIN tbl_os_extra using(os)
			JOIN tbl_produto using(produto)
			JOIN tbl_defeito_constatado on tbl_os.defeito_constatado = tbl_defeito_constatado.defeito_constatado
			JOIN tbl_solucao on tbl_solucao.solucao = tbl_os.solucao_os
			JOIN tbl_os_produto on tbl_os.os = tbl_os_produto.os
			JOIN tbl_os_item on tbl_os_produto.os_produto = tbl_os_item.os_produto
			JOIN tbl_servico_realizado on tbl_servico_realizado.servico_realizado = tbl_os_item.servico_realizado and tbl_servico_realizado.troca_de_peca IS FALSE
			WHERE tbl_os.fabrica= $login_fabrica
			AND tbl_os.produto = $produto
			AND tbl_os.serie like '$nserie%'
			and $cond_5
			AND tbl_os_extra.extrato NOTNULL";
//	echo nl2br($sql);
	$res = pg_exec($con,$sql);
	$total_sem = pg_result($res,0,0);
	$total_com = $total_os - $total_sem;

	$cor = ($x % 2) ? "#F7F5F0" : "#F1F4FA";
?>


<table width='700' border='0' cellpadding='2' cellspacing='1' class='tabela'  align='center'>
	<tr class='Titulo' height='15'>
		<td colspan='5' align='center'>Ordem de Serviço</td>
	</tr>
	<tr class='Titulo'>
		<td><font size='1'>Quantidade OS sem Peça</font></td>
		<td><font size='1'>Quantidade OS com Peça</font></td>
		<td><font size='1'>Quantidade total de OS</font></td>
	</tr>
	<tr class='Conteudo' height='15'  bgcolor='$cor'>
		<td nowrap align='right'><font size='1'><?php echo $total_sem;?></font></td>
		<td nowrap align='right'><font size='1'><?php echo $total_com;?></font></td>
		<td nowrap align='right'><font size='1'><?php echo $total_os;?></font></td>
	</tr>
</table><BR><BR>

<?php
	/*OS SEM peca*/
	$sql = "SELECT  tbl_os.produto,
					tbl_produto.referencia as produto_referencia,
					tbl_produto.descricao as produto_descricao,
					tbl_defeito_constatado.descricao as defeito_constatado,
					tbl_defeito_constatado.defeito_constatado as defeito_constatado_id,
					tbl_solucao.descricao as solucao,
					tbl_solucao.solucao as solucao_id,
				count(distinct tbl_os.os) as qtde
			FROM tbl_os
			JOIN tbl_os_extra using(os)
			JOIN tbl_produto using(produto)
			JOIN tbl_defeito_constatado on tbl_os.defeito_constatado = tbl_defeito_constatado.defeito_constatado
			JOIN tbl_solucao on tbl_solucao.solucao = tbl_os.solucao_os
			JOIN tbl_os_produto on tbl_os.os = tbl_os_produto.os
			JOIN tbl_os_item on tbl_os_produto.os_produto = tbl_os_item.os_produto
			JOIN tbl_servico_realizado on tbl_servico_realizado.servico_realizado = tbl_os_item.servico_realizado and tbl_servico_realizado.troca_de_peca IS FALSE
			WHERE tbl_os.fabrica= $login_fabrica
			AND tbl_os.produto = $produto
			AND tbl_os.serie like '$nserie%'
			and $cond_5
			AND tbl_os_extra.extrato NOTNULL
			GROUP BY tbl_os.produto,
					tbl_produto.referencia,
					tbl_produto.descricao,
					tbl_defeito_constatado.descricao,
					tbl_defeito_constatado.defeito_constatado,
					tbl_solucao.descricao,
					tbl_solucao.solucao
					ORDER BY qtde desc";
	$res = pg_exec($con,$sql);

//	echo nl2br($sql);

	if (pg_numrows($res) > 0) {
	?>
		<table width='700' border='0' cellpadding='2' cellspacing='1' class='tabela'  align='center'>
			<tr class='Titulo' height='15'>
				<td colspan='5' align='center'>OS sem utilização de peça</td>
			</tr>
			<tr class='Titulo' height='15'>
				<td>Produto</td>
				<td>Defeito Constatado</td>
				<td>Solução</td>
				<td>Qtde</td>
			</tr>
		<?php

		$total = pg_numrows($res);
		for($x=0; pg_numrows($res) > $x;$x++){
			$cor ="";
			$qtde               = pg_result($res,$x,qtde);
			$produto            = pg_result($res,$x,produto);
			$produto_referencia = pg_result($res,$x,produto_referencia);
			$produto_descricao  = pg_result($res,$x,produto_descricao);
			$defeito_constatado = pg_result($res,$x,defeito_constatado);
			$solucao            = pg_result($res,$x,solucao);
			$defeito_constatado_id = pg_result($res,$x,defeito_constatado_id);
			$solucao_id = pg_result($res,$x,solucao_id);


			$cor = ($x % 2) ? "#F7F5F0" : "#F1F4FA";
			?>
			<tr class='Conteudo' height='15' bgcolor='$cor'>
				<td nowrap align='left'><font size='1'><?php echo $produto_descricao;?></font></td>
				<td nowrap align='left'><font size='1'><a href='javascript: AbreOS("<?php echo $produto;?>","<?php echo $nserie;?>","<?php echo $defeito_constatado_id;?>","<?php echo $solucao_id;?>")'><?php echo $defeito_constatado;?></a></font></td>
				<td nowrap align='left'><font size='1'><?php echo $solucao;?></font></td>
				<td nowrap align='right'><font size='1'><?php echo $qtde;?></font></td>
			</tr>
			<?php
			/*echo "<tr>";
			echo "<td colspan='5'>";
			echo "<div id='dados_$x' style='display:none; border: 1px solid 	#949494;background-color: #f4f4f4;'>";
			echo "<script language='javascript'>exibirDados('dados_$x','','','');</script>";
			/*$xsql = "SELECT  tbl_os.os,
							to_char(tbl_os.data_abertura,'DD/MM/YYYY') AS data_abertura,
							to_char(tbl_os.data_fechamento,'DD/MM/YYYY') AS data_fechamento,
							tbl_produto.descricao as produto_descricao,
							tbl_defeito_constatado.descricao as defeito_constatado,
							tbl_defeito_reclamado.descricao as defeito_reclamado,
							tbl_os.serie,
tbl_defeito_constatado.defeito_constatado,
tbl_solucao.solucao as solucao_id,
							tbl_solucao.descricao as solucao
					FROM tbl_os
					JOIN tbl_os_extra using(os)
					JOIN tbl_produto using(produto)
					JOIN tbl_defeito_constatado on tbl_os.defeito_constatado = tbl_defeito_constatado.defeito_constatado
					JOIN tbl_solucao on tbl_solucao.solucao = tbl_os.solucao_os
					JOIN tbl_os_produto on tbl_os.os = tbl_os_produto.os
					JOIN tbl_os_item on tbl_os_produto.os_produto = tbl_os_item.os_produto
					JOIN tbl_servico_realizado on tbl_servico_realizado.servico_realizado = tbl_os_item.servico_realizado and tbl_servico_realizado.troca_de_peca IS FALSE
					WHERE tbl_os.fabrica= $login_fabrica
					AND tbl_os.produto = $produto
					AND tbl_os.serie like '$nserie%'
					AND tbl_os_extra.extrato NOTNULL
					AND tbl_defeito_constatado.defeito_constatado = $defeito_constatado_id
					AND tbl_solucao.solucao = $solucao_id limit 5";
			//$xres = pg_exec($con,$xsql);
echo $xsql;
			if(pg_exec($xres)>0){
			echo "<table width='700' border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#000000' align='center'>";
					echo "<tr class='Titulo' height='15'>";
					echo "<td>OS</td>";
					echo "<td>Produto</td>";
					echo "<td>Data Abertura</td>";
					echo "<td>Data Fechamento</td>";
					echo "<td>Defeito Reclamado</td>";
					echo "<td>Defeito Constatado</td>";
					echo "<td>Solução</td>";
					echo "</tr>";
				for($i=0;pg_numrows($xres)>$i; $i++){
					$os = pg_result($xres,$i,os);
					$data_abertura = pg_result($xres,$i,data_abertura);
					$data_fechamento = pg_result($xres,$i,data_fechamento);
					$produto_descricao = pg_result($xres,$i,produto_descricao);
					$defeito_constatado = pg_result($xres,$i,defeito_constatado);
					$defeito_reclamado = pg_result($xres,$i,defeito_reclamado);
					$solucao = pg_result($xres,$i,solucao);
					echo "<tr class='Titulo' height='15'>";
					echo "<td>$os</td>";
					echo "<td>$produto_descricao</td>";
					echo "<td>$data_abertura</td>";
					echo "<td>$data_fechamento</td>";
					echo "<td>$defeito_reclamado</td>";
					echo "<td>$defeito_constatado</td>";
					echo "<td>$solucao</td>";
					echo "</tr>";
				}
			echo "</table>";
			}

			echo "</div> ";
			echo "</td>";
			echo "</tr>";*/

		}
		?>
		</table>
		<?php
	}else{
		?>
		<table width='700' border='0' cellpadding='2' cellspacing='1' class='tabela'  align='center'>
			<tr class='Titulo' height='15'>
				<td align='center'>Nenhuma OS sem peça</td>
			</tr>
		</table>
		<?php
	}
echo "<br><br><br>";
/*OS COM PEÇAS*/
	/*OS SEM peca*/
$sql =  "select tbl_peca.peca,
				tbl_peca.referencia,
				tbl_peca.descricao,
				count(tbl_os_item.peca) as qtde
		FROM tbl_os
		JOIN tbl_os_extra using(os)
		JOIN tbl_os_produto using(os)
		JOIN tbl_os_item using(os_produto)
		JOIN tbl_peca using(peca)
		JOIN tbl_defeito on tbl_defeito.defeito = tbl_os_item.defeito
		JOIN tbl_servico_realizado ON tbl_os_item.servico_realizado=tbl_servico_realizado.servico_realizado
		WHERE tbl_os.fabrica =$login_fabrica
		AND tbl_os.produto = $produto
		AND tbl_os.serie like '$nserie%'
		and $cond_5
		AND tbl_os_extra.extrato NOTNULL
		AND tbl_servico_realizado.gera_pedido IS TRUE
		GROUP BY tbl_peca.peca, tbl_peca.referencia, tbl_peca.descricao
		ORDER BY qtde desc";
	$res = pg_exec($con,$sql);

//echo nl2br($sql);

	if (pg_numrows($res) > 0) {
		?>
		<table width='700' border='0' cellpadding='2' cellspacing='1' class='tabela'  align='center'>
			<tr class='Titulo' height='15'>
				<td colspan='5' align='center'>Peças utilizadas</td>
			</tr>
			<tr class='Titulo' height='15'>
				<td>Referência</td>
				<td>Peça</td>
				<td>Qtde</td>
			</tr>
		<?php
		$total = pg_numrows($res);
		for($x=0; pg_numrows($res) > $x;$x++){
			$cor = "";
			$qtde               = pg_result($res,$x,qtde);
			$peca_referencia            = pg_result($res,$x,referencia);
			$peca_descricao  = pg_result($res,$x,descricao);
			$peca   = pg_result($res,$x,peca);
			$cor = ($x % 2) ? "#F7F5F0" : "#F1F4FA";
			?>
			<tr class='Conteudo' height='15'  bgcolor='$cor'>
				<td nowrap align='left'><font size='1'><?php echo $peca_referencia;?></font></td>
				<td nowrap align='left'><font size='1'><a href='javascript: AbrePeca(<?php echo $produto;?>,<?php echo $peca;?>,"<?php echo $nserie;?>")'><?php echo $peca_descricao;?></font></td>
				<td nowrap align='right'><font size='1'><?php echo $qtde;?></font></td>
			</tr>
			<?php
		}
			?>
		</table>
		<?php
	}else{
		?>
		<table width='700' border='0' cellpadding='2' cellspacing='1' class='tabela'  align='center'>
			<tr class='Titulo' height='15'>
				<td align='center'>Nenhuma OS sem peça</td>
			</tr>
		</table>
		<?php
	}


}

include "rodape.php";
?>
