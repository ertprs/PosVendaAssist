<?
//arquivo alterado por takashi em 17/08/2006. Aparecia com a somatoria errada. Qdo agrupava por peça o valor total ficava diferente e o valor das pecas tambem. Arquivo anterior renomeado para os_extrato_pecas_retornaveis_ant.php

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

$totalRegistros = 1;
$extrato = trim($_GET['extrato']);
if (strlen($extrato) == 0) $extrato = trim($_POST['extrato']);
$agrupar = trim($_GET['agrupar']);
if (strlen($agrupar)==0){
$agrupar ="false";
}

$servico_realizado = trim($_GET['servico_realizado']);
if (strlen($servico_realizado) == 0) $servico_realizado = trim($_POST['servico_realizado']);

if(strlen($extrato) == 0){
	header("Location: os_extrato.php");
	exit;
}

if($login_posto==17674){
	header("Location: os_extrato_pecas_retornaveis_tectoy_17674.php?extrato=$extrato");
	exit;
}



$msg_erro = "";

$layout_menu = "os";
$title = "Relação de Peças Retornáveis ";
if ($login_fabrica == 3) { 
		$title .= "/ do Estoque ";
}
$title .= "no Extrato";

include "cabecalho.php";

?>

<style type="text/css">

.menu_top {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: x-small;
	font-weight: bold;
	border: 1px solid;
	color:#000000;
	background-color: #d9e2ef
}

.table_line {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	border: 0px solid;
	background-color: #D9E2EF;
}

.table_line2 {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
}

</style>

<p>
<?
if(strlen($msg_erro) > 0){
	echo "<TABLE width=\"650\" align='center' border=0>";
	echo "<TR>";
	
	echo "<TD align='center' class='error'>$msg_erro</TD>";
	
	echo "</TR>";
	echo "</TABLE>";
}


if ($login_fabrica == 2){
	echo "<table width='650' align='center' border='0' cellspacing='1' cellpadding='1'>\n";
	echo "<tr class='menu_top'>\n";
	echo "<td colspan=7 align='center'><font color='#FF0000'<b>AGUARDAR UM BOM VOLUME DE PEÇAS COM DEFEITOS PARA O ENVIO DAS MESMAS, ENTRAR EM CONTATO POR EMAIL PARA VERIFICAR O MEIO DE TRANSPORTE A SER ENVIADO.</b></font></td>\n";
	echo "</tr>\n";
	echo "</table>\n";
}

echo "<TABLE width=\"650\" align='center' border=0>";
echo "<TR class='menu_top'>\n";

echo "<TD align='center'><a href='$PHP_SELF?agrupar=true&extrato=$extrato'><font color='#000000'>Agrupar por peça</font></a></TD>\n";
echo "<TD align='center'><a href='$PHP_SELF?agrupar=false&extrato=$extrato'><font color='#000000'>Não agrupar</font></a></TD>\n";

echo "</TR>";
echo "</TABLE>";

echo "<p>";

if ($login_fabrica == 6){
	if ($agrupar == "false"){
		$join_preco = "tbl_os_item.preco, ";
		
		/*
		if ($login_fabrica == 6) {
			$join_preco = "(SELECT preco FROM tbl_tabela_item JOIN tbl_tabela USING (tabela) WHERE tbl_tabela.fabrica = $login_fabrica AND tbl_tabela_item.peca = tbl_os_item.peca AND tbl_tabela.ativa ORDER BY preco DESC LIMIT 1) AS preco, " ;
		}
		*/

		/* hd chamado 930 */
		/* caso peça não tenha sido faturada, exibir preço da tabela (Angélica) */
		if ($login_fabrica == 6) {
			$join_preco = "CASE WHEN
								tbl_faturamento_item.preco > 0 THEN
									tbl_faturamento_item.preco
								ELSE 
									(SELECT preco FROM tbl_tabela_item JOIN tbl_tabela USING (tabela) WHERE tbl_tabela.fabrica = $login_fabrica AND tbl_tabela_item.peca = tbl_os_item.peca AND tbl_tabela.ativa ORDER BY preco DESC LIMIT 1)
							END AS preco," ;
		}

		/* hd chamado 930 incluido join na tbl_faturamento_item */
		/* hd chamado 6977 nao listar defeito = 97 - falta */
		$sql = "SELECT 	distinct 
						tbl_produto.descricao, 
						tbl_os.os, 
						tbl_os.sua_os, 
						tbl_os.consumidor_nome, 
						tbl_os_produto.os_produto, 
						tbl_os_item.os_item, 
						tbl_peca.referencia as peca_referencia,
						tbl_peca.descricao as peca_descricao, 
						tbl_os_item.qtde AS qtde,
						$join_preco                                                    
						to_char(tbl_extrato.data_geracao, 'DD/MM/YYYY') AS data_geracao 
				FROM tbl_os 
				JOIN tbl_os_extra using(os) 
				JOIN tbl_os_produto using(os) 
				JOIN tbl_produto ON tbl_os.produto = tbl_produto.produto  
				JOIN tbl_os_item using(os_produto) 
				JOIN tbl_peca using(peca)  
				JOIN    tbl_extrato ON tbl_extrato.extrato = tbl_os_extra.extrato
				LEFT JOIN tbl_faturamento_item on tbl_os_item.pedido = tbl_faturamento_item.pedido
										and tbl_os_item.peca = tbl_faturamento_item.peca
				WHERE tbl_os_extra.extrato = $extrato 
				AND tbl_os_item.liberacao_pedido IS NOT NULL 
				AND tbl_os_item.defeito <> 79
				AND tbl_os_item.pedido IS NOT NULL";
	if($login_posto<>17674)$sql .= " AND tbl_peca.devolucao_obrigatoria='t' ";
				if (strlen($servico_realizado) > 0) $sql .= " AND tbl_servico_realizado.servico_realizado = $servico_realizado ";
				$sql .=" ORDER BY tbl_peca.referencia;";
		//echo nl2br($sql);
		$res = pg_exec ($con,$sql);
		$totalRegistros = pg_numrows($res);
		
	
		if($totalRegistros==0){

			$sql = "SELECT 	tbl_produto.descricao, 
						tbl_os.os, 
						tbl_os.sua_os, 
						tbl_os.consumidor_nome, 
						tbl_os_produto.os_produto, 
						tbl_os_item.os_item, 
						tbl_peca.referencia as peca_referencia,
						tbl_peca.descricao as peca_descricao, 
						tbl_os_item.qtde AS qtde,
						$join_preco                                                    
						to_char(tbl_extrato.data_geracao, 'DD/MM/YYYY') AS data_geracao 
				FROM tbl_os 
				JOIN tbl_os_extra using(os) 
				JOIN tbl_os_produto using(os) 
				JOIN tbl_produto ON tbl_os.produto = tbl_produto.produto  
				JOIN tbl_os_item using(os_produto) 
				JOIN tbl_peca using(peca)  
				JOIN    tbl_extrato              ON tbl_extrato.extrato                     = tbl_os_extra.extrato
				LEFT JOIN tbl_faturamento_item on tbl_os_item.pedido = tbl_faturamento_item.pedido
										and tbl_os_item.peca = tbl_faturamento_item.peca
				WHERE tbl_os_extra.extrato=$extrato 
				AND tbl_os_item.liberacao_pedido IS NOT NULL 
				AND   tbl_os_item.pedido IS NOT NULL
				AND tbl_os_item.defeito <> 79";
	if($login_posto<>17674)$sql .= " AND tbl_peca.devolucao_obrigatoria='t'";
				if (strlen($servico_realizado) > 0) $sql .= " AND tbl_servico_realizado.servico_realizado = $servico_realizado ";
				$sql .=" ORDER BY tbl_peca.referencia;";
		$res = pg_exec ($con,$sql);
//echo $sql."<<<<-----<BR><BR>";
			$totalRegistros = pg_numrows($res);
			}

		if ($totalRegistros>0){
// 		echo "<BR>NAO AGRUPADOO";
		echo "<TABLE width='650' align='center' border='0' cellspacing='1' cellpadding='1'>";
		echo "<TR class='menu_top'>";
		echo "<TD colspan='5' align = 'center'>EXTRATO $extrato GERADO EM ". pg_result ($res,0,data_geracao);
		echo "</TD>";
		echo "</TR>\n";
		echo "<TR class='menu_top'>\n";
		echo "<TD colspan='5' align = 'center'>Peças que não geram Crédito e que devem <br> ser
devolvidas fisicamente para a Fábrica.</TD>";
//frase alterada dia 16/10 conforme contato com Leandro / tectoy "Peças do estoque que geram
//Crédito mas não<br> precisam ser devolvidas fisicamente para a Fábrica." (Takashi)
		echo "</TR>\n";
		
		echo "<TR class='menu_top'>\n";
		echo "<TD align='center' >OS</TD>\n";
		echo "<TD align='center' >CLIENTE</TD>\n";
		echo "<TD align='center' >PEÇA</TD>\n";
		echo "<TD align='center' >QTDE</TD>\n";
		echo "<TD align='center' >VALOR</TD>\n";
		echo "</TR>";
				
		$soma_preco = 0;
		for ($i = 0 ; $i < pg_numrows ($res) ; $i++){
			$os	= trim(pg_result ($res,$i,os));
			$sua_os	= trim(pg_result ($res,$i,sua_os));
			$peca_descricao	= trim(pg_result ($res,$i,peca_descricao));
			$peca_referencia	= trim(pg_result ($res,$i,peca_referencia));
			$consumidor	= trim(pg_result ($res,$i,consumidor_nome));
			
			$preco				= trim(pg_result ($res,$i,preco));
			$qtde				= trim(pg_result ($res,$i,qtde));
			if($qtde>1){$preco = $preco*$qtde;}
			$soma_preco			= $soma_preco + $preco;
			$consumidor			= strtoupper($consumidor);
			$preco				= number_format($preco,2,",",".");
			
			$cor = "#d9e2ef";
			$btn = 'amarelo';
			
			if ($i % 2 == 0){ $cor = '#F1F4FA'; $btn = 'azul';}
					
			if (strstr($matriz, ";" . $i . ";")) {$cor = '#E49494';}
					
			if (strlen ($sua_os) == 0) $sua_os = $os;
	
			echo "<TR class='table_line' style='background-color: $cor;'>\n";
			echo "<TD align='center' nowrap><a href='os_press.php?os=$os' target='_blank'><font color='#000000'>$sua_os</font></a></TD>\n";
			echo "<TD align='left' nowrap>$consumidor</TD>\n";
			echo "<TD align='left' nowrap>$peca_referencia - $peca_descricao</TD>\n";
			echo "<TD align='center' nowrap>$qtde</TD>\n";
			echo "<TD align='right' nowrap style='padding-right:3px'>$preco</TD>\n";
			echo "</TR>\n";
		}
		echo "<TR class='table_line' style='background-color: $cor;'>\n";
		echo "<TD align='center' nowrap colspan='4'><b>TOTAL</b></TD>\n";
		echo "<TD align='right' nowrap style='padding-right:3px'>". number_format($soma_preco,2,",",".");
		echo "</TD>\n";
		echo "</TR>\n";
		echo "</TABLE>\n";
		}else{ echo "Nenhuma peça encontrada para devolução";}
		echo "<br>";
	
	}else{

		if ($login_fabrica == 6) {
			$join_preco = "CASE WHEN
								tbl_faturamento_item.preco > 0 THEN
									tbl_faturamento_item.preco
								ELSE 
									(SELECT preco FROM tbl_tabela_item JOIN tbl_tabela USING (tabela) WHERE tbl_tabela.fabrica = $login_fabrica AND tbl_tabela_item.peca = tbl_os_item.peca AND tbl_tabela.ativa ORDER BY preco DESC LIMIT 1)
							END AS preco," ;
		}

		/* hd chamado 930 incluido join na tbl_faturamento_item */
		$sql = "select peca, peca_referencia, sum (preco*qtde) as preco,
					peca_descricao, sum(qtde) as qtde
				from (
				SELECT 	distinct 
						tbl_produto.descricao, 
						tbl_os.os, 
						tbl_os.sua_os, 
						tbl_os.consumidor_nome, 
						tbl_os_produto.os_produto, 
						tbl_os_item.os_item, 
						tbl_peca.peca,
						tbl_peca.referencia as peca_referencia,
						tbl_peca.descricao as peca_descricao, 
						tbl_os_item.qtde AS qtde,
						$join_preco                                                    
						to_char(tbl_extrato.data_geracao, 'DD/MM/YYYY') AS data_geracao 
				FROM tbl_os 
				JOIN tbl_os_extra using(os) 
				JOIN tbl_os_produto using(os) 
				JOIN tbl_produto ON tbl_os.produto = tbl_produto.produto  
				JOIN tbl_os_item using(os_produto) 
				JOIN tbl_peca using(peca)  
				JOIN    tbl_extrato ON tbl_extrato.extrato = tbl_os_extra.extrato
				LEFT JOIN tbl_faturamento_item on tbl_os_item.pedido = tbl_faturamento_item.pedido
				and tbl_os_item.peca = tbl_faturamento_item.peca
				WHERE tbl_os_extra.extrato = $extrato 
				AND tbl_os_item.liberacao_pedido IS NOT NULL 
				AND   tbl_os_item.pedido IS NOT NULL
				AND tbl_os_item.defeito <> 79";
	if($login_posto<>17674)$sql .= " AND tbl_peca.devolucao_obrigatoria='t'  ";
				if (strlen($servico_realizado) > 0) $sql .= " AND tbl_servico_realizado.servico_realizado = $servico_realizado ";
				$sql .= " ) as x group by peca, peca_referencia,peca_descricao order by peca_referencia";
				//echo $sql; exit;
	$res = pg_exec ($con,$sql);
	//echo nl2br($sql);
		$totalRegistros = pg_numrows($res);

		if($totalRegistros==0){

			$sql = "select peca, peca_referencia, sum (preco*qtde) as preco ,
					peca_descricao, sum(qtde) as qtde
					
					from (
						SELECT 	tbl_produto.descricao, 
						tbl_os.os, 
						tbl_os.sua_os, 
						tbl_os.consumidor_nome, 
						tbl_os_produto.os_produto, 
						tbl_os_item.os_item, 
						tbl_peca.peca,
						tbl_peca.referencia as peca_referencia,
						tbl_peca.descricao as peca_descricao, 
						tbl_os_item.qtde AS qtde,
						$join_preco                                                    
						to_char(tbl_extrato.data_geracao, 'DD/MM/YYYY') AS data_geracao 
				FROM tbl_os 
				JOIN tbl_os_extra using(os) 
				JOIN tbl_os_produto using(os) 
				JOIN tbl_produto ON tbl_os.produto = tbl_produto.produto  
				JOIN tbl_os_item using(os_produto) 
				JOIN tbl_peca using(peca)  
				JOIN    tbl_extrato              ON tbl_extrato.extrato                     = tbl_os_extra.extrato
				LEFT JOIN tbl_faturamento_item on tbl_os_item.pedido = tbl_faturamento_item.pedido
				and tbl_os_item.peca = tbl_faturamento_item.peca
				WHERE tbl_os_extra.extrato = $extrato
				AND tbl_os_item.liberacao_pedido IS NOT NULL 
				AND   tbl_os_item.pedido IS NOT NULL
				AND tbl_os_item.defeito <> 79";
	if($login_posto<>17674)$sql .= " AND tbl_peca.devolucao_obrigatoria='t'";
				if (strlen($servico_realizado) > 0) $sql .= " AND tbl_servico_realizado.servico_realizado = $servico_realizado ";
				$sql .=" ) as x group by peca, peca_referencia,peca_descricao order by peca_referencia";
			$res = pg_exec ($con,$sql);
//echo $sql."<7--<BR><BR>";
			$totalRegistros = pg_numrows($res);
		}	
		

 		
		if($totalRegistros>0){
			
			// AGRUPADO
// 			echo "AGRUPADOOOOOOOOOO";
			echo "<TABLE width='650' align='center' border='0' cellspacing='1' cellpadding='1'>\n";
			echo "<TR class='menu_top'>\n";
			echo "<TD colspan='3' align = 'center'>";
			echo "EXTRATO $extrato ";

			echo "</TD>";
			echo "</TR>\n";
			
			echo "<TR class='menu_top'>\n";
			echo "<TD colspan='3' align = 'center'>Peças que não geram Crédito e que devem <br> ser devolvidas fisicamente para a Fábrica.</TD>";
			echo "</TR>\n";
			
			echo "<TR class='menu_top'>\n";
			echo "<TD align='center' >PEÇA</TD>\n";
			echo "<TD align='center' >QTDE</TD>\n";
			echo "<TD align='center' >VALOR</TD>\n";
			echo "</TR>\n";
			
			$soma_preco = 0;
			for ($i = 0 ; $i < pg_numrows ($res) ; $i++){
				
				$peca_referencia		= trim(pg_result ($res,$i,peca_referencia));
				$peca_nome			= trim(pg_result ($res,$i,peca_descricao));
				$preco				= trim(pg_result ($res,$i,preco));
				$qtde				= trim(pg_result ($res,$i,qtde));
			//	if($qtde>1){$preco = $preco*$qtde;}
				$soma_preco			= $soma_preco + $preco;
				$consumidor			= strtoupper($consumidor);
				$preco				= number_format($preco,2,",",".");
				
				$cor = "#d9e2ef";
				$btn = 'amarelo';
				
				if ($i % 2 == 0){
					$cor = '#F1F4FA';
					$btn = 'azul';
				}
				
				if (strstr($matriz, ";" . $i . ";")) {
					$cor = '#E49494';
				}
				
				if (strlen ($sua_os) == 0) $sua_os = $os;
			
			echo "<TR class='table_line' style='background-color: $cor;'>\n";
			echo "<TD align='left' nowrap>$peca_referencia - $peca_nome</TD>\n";
			echo "<TD align='center' nowrap>$qtde</TD>\n";
			echo "<TD align='right' nowrap style='padding-right:3px'>$preco</TD>\n";
			echo "</TR>\n";
			}
			echo "<TR class='table_line' style='background-color: $cor;'>\n";
			echo "<TD align='center' nowrap colspan='2'><b>TOTAL</b></TD>\n";
			echo "<TD align='right' nowrap style='padding-right:3px'>". number_format($soma_preco,2,",",".")."</TD>\n";
			echo "</TR>\n";
			
			echo "</TABLE>\n";
		}else{echo "Não foi encontrado nenhuma peça";}
	}
}
?>


<TABLE align='center'>
<TR>
	<TD>
		<br>
		<img src="imagens/btn_voltar.gif" onclick="javascript: window.location='os_extrato.php';" ALT="Voltar" border='0' style="cursor:pointer;">
	</TD>
</TR>
</TABLE>

<p>
<p>

<? include "rodape.php"; ?>