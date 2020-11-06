<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';


$layout_menu = 'pedido';
$title="Seja bem-vindo à loja da Britania !";

include "cabecalho.php";

include 'loja_menu.php';

echo "<table width='700' border='0' align='center' cellpadding='0' cellspacing='0' style=' border:#B5CDE8 1px solid;  bordercolor='#d2e4fc'>";
echo "<tr height='40'>";

	echo "<td height='40' colspan='3' align='left'  align='center' class='Titulo' bgcolor='#e6eef7' >&nbsp;&nbsp;<font size='2'>Produtos em Destaque</font><BR>
	<font size='1'>&nbsp;&nbsp;O valor mínimo de faturamento é de R$100,00 para capitais e R$50,00 para as demais cidades.</font>";
	echo "</td>";
	echo "<td align='right' class='Titulo' bgcolor='#e6eef7'></td>";

echo "</tr>";
//produtos linha a cima

//pega produtos
$sql = "SELECT  random()  ,
				peca      ,
				referencia,
				descricao ,
				preco_sugerido,
				qtde_disponivel_site
		FROM tbl_peca 
		WHERE fabrica = '$login_fabrica'
		AND   promocao_site IS TRUE
		LIMIT 6";
$res = pg_exec ($con,$sql);
//		echo "$sql";

for ($i = 0 ; $i < pg_numrows($res); $i++){
	$peca                 = trim(pg_result ($res,$i,peca));
	$referencia           = trim(pg_result ($res,$i,referencia));
	$preco_sugerido       = trim(pg_result ($res,$i,preco_sugerido));
	$descricao            = trim(pg_result ($res,$i,descricao));
	$qtde_disponivel_site = trim(pg_result ($res,$i,qtde_disponivel_site));

	$sql2 = "SELECT preco
			 FROM tbl_tabela_item
			 WHERE peca  = $peca
			 AND   tabela IN (
				SELECT tbl_tabela.tabela
				 FROM tbl_posto_linha 
				 JOIN tbl_tabela       USING(tabela)
				 JOIN tbl_posto        USING(posto) 
				 JOIN tbl_linha        USING(linha)
				 WHERE tbl_posto.posto       = $login_posto
				 AND   tbl_linha.fabrica     = $login_fabrica
				 AND   tbl_posto_linha.linha in (
					SELECT DISTINCT tbl_produto.linha
					FROM tbl_produto 
					JOIN tbl_lista_basica USING(produto)
					JOIN tbl_peca USING(peca)
					WHERE peca = $peca LIMIT 1
				 )
			)";
	$res2 = pg_exec ($con,$sql2);
	
	if(pg_numrows($res2)<1) continue;
	$preco      = trim(pg_result ($res2,0,preco));
	$preco_formatado = number_format($preco,2,'.',',');

	if($i%3==0) $cor='#F4EBD7';
	else        $cor='#EFEFEF';
	if($i==4)   $cor='#F4EBD7';
	if($i==0 )echo "<tr class='Conteudo'>";
	if($i%2==0 AND $i<>0) {
		echo "</tr><tr class='Conteudo'>";
	}
	/*#########################################################################
	########################### TESTA DISPONIBILIDADE #########################*/
	echo "<td align='center' bgcolor='$cor'><BR><div class='contenedorfoto'>";
	if(($qtde_disponivel_site) > 0 && ($preco) > 0){
		echo "<a href='loja_detalhe.php?cod_produto=$peca'>";
		$saida == "";
		if ($dh = opendir('imagens_pecas/pequena/')) {
			$contador=0;
			while (false !== ($filename = readdir($dh))) {
				if($contador == 1) break;
				if (strpos($filename,$referencia) !== false){
					$contador++;
					//$peca_referencia = ntval($peca_referencia);
					$po = strlen($referencia);
					if(substr($filename, 0,$po)==$referencia){?>
						<img src='imagens_pecas/pequena/<?echo $filename; ?>' border='0'>
						<input type='hidden' name='peca_imagem' value='<?echo $filename; ?>'>
		<?			}
				}
			}
		}
		// trabalhando no HD 3780 - GUSTAVO
		echo "</a>";
	}else{
		$saida == "";
		if ($dh = opendir('imagens_pecas/pequena/')) {
			$contador=0;
			while (false !== ($filename = readdir($dh))) {
				if($contador == 1) break;
				if (strpos($filename,$referencia) !== false){
					$contador++;
					//$peca_referencia = ntval($peca_referencia);
					$po = strlen($referencia);
					if(substr($filename, 0,$po)==$referencia){?>
						<img src='imagens_pecas/pequena/<?echo $filename; ?>' border='0'>
						<input type='hidden' name='peca_imagem' value='<?echo $filename; ?>'>
		<?			}
				}
			}
		}
	}
	echo "</div>";
	if(strlen($saida)>0)echo "$saida";
	echo "</td>";
	if(($qtde_disponivel_site) > 0 && ($preco) > 0){
		echo "<td  bgcolor='$cor'><BR><a href='loja_detalhe.php?cod_produto=$peca'>$descricao";
		echo "<br><font color='#333333'>Referencia: </b>$referencia</b></font>";
		echo "<br><font color='#333333'>Qtde Disponível: </b>$qtde_disponivel_site</b></font>";
		echo "<br><font color='#FF0000' size='+1'><b>R$ $preco_formatado</b></font></a></td>";
	}else{
		echo "<td  bgcolor='$cor'><BR>$descricao";
		echo "<br><font color='#333333'>Referencia: </b>$referencia</b></font>";
		echo "<br><font color='#333333'><b>Indisponivel</b></font>";
		echo "<br><font color='#FF0000' size='+1'><b>R$ $preco_formatado</b></font></td>";
	}

	/*#########################################################################
	######################## FIM TESTA DISPONIBILIDADE ########################*/
}
echo "<tr>";
echo "<td align='right' bgcolor='#e6eef7' colspan='5' class='Titulo'><a href='javascript:history.back()'>Voltar</a></td>";
echo "</tr>";

echo "</table>";
?>