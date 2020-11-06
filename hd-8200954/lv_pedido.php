<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

if (strlen($cook_fabrica)==0 AND strlen($cook_login_unico)>0){
	include 'login_unico_autentica_usuario.php';
	$login_fabrica = 10;
}elseif (strlen($cook_fabrica)==0 AND strlen($cook_login_simples)>0){
	include 'login_simples_autentica_usuario.php';
}else{
	include 'autentica_usuario.php';
}
include_once "class/tdocs.class.php";
$tDocs = new TDocs($con, $login_fabrica);
?>


<style type="text/css">
.pedido_table {
       border-style: solid;
       border-width: 2px;
       border-color: #6B7290;
       font-size: 10px;
       font-weight: bold;
       color: white;
       font-weight: bold;
       background-image: url('./imagens/barra_dg_azul_tc_30.jpg');
       background-repeat: repeat-x;
       background-attachment: top, left;
}
.pedido_th{
     text-align:center;
	 background-image: url('imagens/barra_dg_azul_tc_30.jpg');
	 color:#FFFFFF;
}

</style>

<?php
$layout_menu = 'pedido';
$title="BEM-VINDO a loja virtual";


if (strlen($cook_fabrica)==0 AND (strlen($cook_login_unico)>0 OR strlen($cook_login_simples)>0)){
	include "login_unico_cabecalho.php";
}else{
	include "cabecalho.php";
}

include 'lv_menu.php';
$pedido = $_GET["pedido"];
if(strlen($pedido)==0){
	$sql = "SELECT  
			tbl_pedido.pedido,
			to_char(tbl_pedido.finalizado,'DD/MM/YYYY') AS finalizado,
			to_char(tbl_pedido.data,'DD/MM/YYYY')       AS data,
			tbl_pedido.total,
			tbl_condicao.descricao AS condicao
		FROM  tbl_pedido
		LEFT JOIN tbl_condicao USING(condicao)
		WHERE tbl_pedido.posto   = $login_posto
		AND   tbl_pedido.fabrica = $login_fabrica
		AND   tbl_pedido.pedido_loja_virtual IS TRUE
		AND   tbl_pedido.exportado           IS NULL
		ORDER BY tbl_pedido.pedido DESC";
	$res = pg_exec ($con,$sql);
	if (pg_numrows($res) > 0) {
	
		$qtde_produto = 0;
		$total        = 0;
		$pedidos      = "";
	
		echo "<br><TABLE width='90%' border='0' cellspacing='0' cellpadding='10px' align='center' name='relatorio' id='relatorio' class='pedido_table'>";
		echo "<thead class='pedido_th' style='height:18px'>";
		echo "<td>Número do seu Pedido</td>";
		echo "<td>Data da Compra</td>";
		#echo "<td>Nota Fiscal</td>";
		echo "<td>Status da sua Compra</td>";
		echo "<td width='200'>Quantidade de Produtos</td>";
		echo "<td>Valor</td>";
		if (strlen($condicao)>0) echo "<td align='left' class='Titutlo'>Condição de Pagamento</td>";
		echo "</thead>";
	
		echo "<tbody>";
	
		for ($i=0;$i<pg_numrows($res);$i++){
			$pedido      = trim(pg_result($res,$i,pedido));
			$finalizado  = trim(pg_result($res,$i,finalizado));
			$data        = trim(pg_result($res,$i,data));
			$condicao    = trim(pg_result($res,$i,condicao));
			$total       = trim(pg_result($res,$i,total));
	
			$sql = "SELECT  SUM(qtde) as qtde
					FROM  tbl_pedido_item
					WHERE pedido = $pedido";
			$res2 = pg_exec ($con,$sql);
			$qtde_produto  = pg_result($res2,0,qtde);
	
			if (strlen($finalizado)>0) {
				$status_pedido = "<b style='color:green'>Finalizado</n>";
				$data = $finalizado;
			}else{
				$status_pedido = "<b style='color:red'>Não Finalizado</n>";
			}
	
			$total = number_format ( $total,2,'.',',');

			$cor = ($x % 2 == 0) ? "#FFFFFF" : "#F1F4FA";

			echo "<tr>";
			echo "<td align='center' class='Conteudo' STYLE='border-bottom: 1px solid #ccc;' bgcolor='$cor'><a href='$PHP_SELF?pedido=$pedido'>$pedido</a></td>";
			echo "<td align='center' class='Conteudo' STYLE='border-bottom: 1px solid #ccc;' bgcolor='$cor'>&nbsp;$data</td>";
			#echo "<td align='center' class='Conteudo' STYLE='border-bottom: 1px solid #ccc;' bgcolor='$cor'>Teste</td>";
			echo "<td align='center' class='Conteudo' STYLE='border-bottom: 1px solid #ccc;' bgcolor='$cor'>$status_pedido</td>";
			echo "<td align='center' class='Conteudo' STYLE='border-bottom: 1px solid #ccc;' bgcolor='$cor'>$qtde_produto</td>";
			echo "<td align='center' class='Conteudo' STYLE='border-bottom: 1px solid #ccc;' bgcolor='$cor'>R$ $total</td>";
			if (strlen($condicao)>0) echo "<td align='left' class='Conteudo' STYLE='border-bottom: 1px solid #ccc;' bgcolor='$cor'>$condicao</td>";
			echo "</tr>";
		}
		echo "</tbody>";
		echo "</table>";
		echo "<br>";
	}else{ echo "<center><b><font color=red size='4'><br>Nenhum pedido encontrado.</font></b></center><br><br><br><br><br><br><br><br><br>";}
}else{

	$sql = "SELECT  
				tbl_pedido.pedido,
				to_char(tbl_pedido.finalizado,'DD/MM/YYYY') as finalizado,
				to_char(tbl_pedido.data,'DD/MM/YYYY') as data,
				tbl_pedido.total,
				tbl_condicao.descricao AS condicao
		FROM  tbl_pedido
		LEFT JOIN tbl_condicao USING(condicao)
		WHERE tbl_pedido.posto   = $login_posto
		AND   tbl_pedido.fabrica = $login_fabrica
		AND   tbl_pedido.pedido  = $pedido
		AND   tbl_pedido.pedido_loja_virtual IS TRUE
		ORDER BY tbl_pedido.pedido DESC";
	$res = pg_exec ($con,$sql);
	if (pg_numrows($res) > 0) {
		
		$qtde_produto = 0;
		$total        = 0;
		$pedidos      = "";

		for ($i=0;$i<pg_numrows($res);$i++){
			$_pedido     = trim(pg_result($res,$i,pedido));
			$pedidos    .= $_pedido. ", ";
			$finalizado  = trim(pg_result($res,$i,finalizado));
			$data        = trim(pg_result($res,$i,data));
			$condicao    = trim(pg_result($res,$i,condicao));
			$total      += trim(pg_result($res,$i,total));

			$sql = "SELECT  SUM(qtde) as qtde
					FROM  tbl_pedido_item
					WHERE pedido = $_pedido";
			$res2 = pg_exec ($con,$sql);
			$qtde_produto  += pg_result($res2,0,qtde);
		}

		$total = number_format ( $total,2,'.',',');

		$pedidos = trim($pedidos);
		$pedidos = substr ($pedidos,0,strlen ($pedidos)-1);

		if (strlen($finalizado)>0){
			$status_pedido = "Finalizado";
		}else{
			$status_pedido = "Não Finalizado";
		}

		echo "<br>";
		
		echo "<table width='90%' cellpadding='2' cellspacing='1' align='center' STYLE='border-bottom: 1px solid #ccc;'>";

		if ($status=="finalizado"){
			echo "<tr>";
			echo "<td colspan='4' align='center' class='Titulo' bgcolor='#e6eef7' >";
			echo "<font size='3' color='blue'><b>Pedido finalizado com sucesso!</b></font><br>";
			echo "<font size='2' color='black'>Este pedido será enviado para a Fábrica hoje e estará disponível amanhã para consulta.<br> É possível adicionar mais peças antes que seja enviado.</font><br>";
			echo "<font size='1' color='black'>Este pedido poderá ser consultado na <a href='pedido_relacao.php?listar=todas'>Consulta de Pedidos</a></font><br><br>";
			echo "</td>";
			echo "</tr>";
		}

		echo "<tr>";
		echo "<td align='left' class='Titutlo2' >Número do seu Pedido</td>";
		echo "<td colspan='3' align='left' class='Conteudo'>$pedidos</td>";
		echo "</tr>";
		echo "<tr>";
		echo "<td  align='left' class='Titutlo2'>Status da sua Compra</td>";
		if (strlen($finalizado)>0){
			$status_pedido = "<b style='color:blue'>$status_pedido</n>";
		}else{
			$status_pedido = "<b style='color:red'>$status_pedido</n>";
		}
		echo "<td colspan='3' align='left' class='Conteudo'>$status_pedido</td>";
		echo "</tr>";

		echo "<tr>";
		echo "<td align='left' class='Titutlo2' width='200'>Quantidade de Produtos</td>";
		echo "<td align='left' class='Conteudo'>$qtde_produto</td>";
		echo "</tr>";
		echo "<tr>";
		echo "<td align='left' class='Titutlo2'>Valor</td>";
		echo "<td colspan='3' align='left' class='Conteudo'>R$ $total</td>";
		echo "</tr>";

		if (strlen($condicao)>0){
			echo "<tr>";
			echo "<td align='left' class='Titutlo2'>Condição de Pagamento</td>";
			echo "<td align='left' class='Conteudo'>$condicao</td>";
			echo "</tr>";
		}

		if (strlen($finalizado)==0){
			echo "<tr>";
			echo "<td colspan='4' align='left' class='Conteudo' style='color: red;'>O pedido da Loja Virtual não será enviado para a Fábrica até que seja <B>FECHADO</b>. </td>";
			echo "</tr>";
		}

		echo "</table>";
		echo "<br>";		
	}


	echo "<br><TABLE width='90%' border='0' cellspacing='0' cellpadding='2' align='center' name='relatorio' id='relatorio' class='pedido_table'>";

	echo "<thead class='pedido_th'>";
	echo "<td width='20' width='25' align='center'>&nbsp;&nbsp;</td>";
	echo "<td height='30' align='left'>Peça</td>";
	echo "<td height='30' align='center'>Qtde</td>";
	echo "<td height='30' align='right'>Valor Unit.</td>";
	echo "<td height='30' align='right' style='padding-right:10px'>Valor Total </td>";
	echo "</thead>";

	$sql = "SELECT
			tbl_pedido.pedido              ,
			tbl_pedido_item.pedido         ,
			tbl_pedido_item.pedido_item    ,
			tbl_peca.peca                  ,
			tbl_peca.referencia            ,
			tbl_peca.descricao             ,
			tbl_peca.ipi                   ,
			tbl_peca.promocao_site         ,
			tbl_peca.qtde_disponivel_site  ,
			tbl_pedido_item.qtde           ,
			tbl_pedido_item.preco          ,
			tbl_linha.nome as linha_desc
		FROM  tbl_pedido
		JOIN  tbl_pedido_item USING (pedido)
		JOIN  tbl_peca        USING (peca)
		LEFT JOIN tbl_linha USING(linha)
		WHERE tbl_pedido.posto   = $login_posto
		AND   tbl_pedido.fabrica = $login_fabrica
		AND   tbl_pedido.pedido  = $pedido
		AND   tbl_pedido.pedido_loja_virtual IS TRUE
		AND   tbl_pedido.exportado           IS NULL
		ORDER BY tbl_pedido_item.pedido_item ASC";
	$res = pg_exec ($con,$sql);
	$pedido_ant = "";
	if (pg_numrows($res) > 0) {
		for($i=0; $i< pg_numrows($res); $i++) {
			$pedido          = trim(pg_result($res,$i,pedido));
			$pedido_item     = trim(pg_result($res,$i,pedido_item));
			$peca            = trim(pg_result($res,$i,peca));
			$referencia      = trim(pg_result($res,$i,referencia));
			$peca_descricao  = trim(pg_result($res,$i,descricao));
			$qtde_carro      = trim(pg_result($res,$i,qtde));
			$preco           = trim(pg_result($res,$i,preco));
			$ipi             = trim(pg_result($res,$i,ipi));
			$promocao_site   = trim(pg_result($res,$i,promocao_site));
			$qtde_disponivel = trim(pg_result($res,$i,qtde_disponivel_site));
			$linha_desc      = trim(pg_result($res,$i,linha_desc));

			$preco_2         = str_replace(",",".",$preco);

			if (strlen($ipi)>0 AND $ipi>0){
				$valor_total = $preco * $qtde_carro + ($preco*$qtde_carro *$ipi/100);
				$ipi = $ipi." %";
			}else{
				$ipi = "";
				$valor_total = $preco * $qtde_carro;
			}

			$soma       += $valor_total;
			$preco       = number_format($preco, 2, ',', '');
			$preco       = str_replace(".",",",$preco);
			$valor_total = number_format($valor_total, 2, ',', '');

			$a++;
			$cor = "#FFFFFF"; 
			if ($a % 2 == 0)$cor = '#F1F4FA';

			if ($pedido_ant<>$pedido){
				if ($promocao_site=='t' OR strlen($qtde_disponivel)>0){
					$msg_pedido = "Pedido de Promoção";
				}else{
					$msg_pedido = "Pedido de Peças";
				}
			}
			$pedido_ant = $pedido;

			echo "<tr class='Conteudo'>";
			echo "<td bgcolor='$cor' align='l' width='45' style='padding-left:10px'>";
			$xpecas  = $tDocs->getDocumentsByRef($peca, "peca");
			if (!empty($xpecas->attachListInfo)) {

				$a = 1;
				foreach ($xpecas->attachListInfo as $kFoto => $vFoto) {
				    $fotoPeca = $vFoto["link"];
				    if ($a == 1){break;}
				}
				echo "<a href=\"lv_carrinho.php?ajax=true&idpeca=$peca&peca=$fotoPeca&keepThis=trueTB_iframe=true&height=340&width=420\" title='$referencia' class='thickbox'>
								<img src='$fotoPeca' border='0'  >
								<input type='hidden' name='peca_imagem' value='$fotoPeca' ></a>";
			} else {
				if ($dh = opendir('imagens_pecas/pequena/')) {
					$contador=0;
					while (false !== ($filename = readdir($dh))) {
						if($contador == 1) break;
						if (strpos($filename,$referencia) !== false){
							$contador++;
							//$peca_referencia = ntval($peca_referencia);
							$po = strlen($referencia);
							if(substr($filename, 0,$po)==$referencia){?>
								<a href="<? echo "lv_carrinho.php?ajax=true&peca=$filename"; ?>&keepThis=trueTB_iframe=true&height=340&width=420" title="<?echo $referencia;?>" class="thickbox">
								<img src='imagens_pecas/pequena/<?echo $filename; ?>' border='0'  >
								<input type='hidden' name='peca_imagem' value='<?echo $filename; ?>' ></a>
				<?			}
						}
					}
				}
			}
			echo "</td>";
			echo "<td  bgcolor='$cor' align='left'>";
			echo "$referencia - $peca_descricao</td>";
			echo "<td  bgcolor='$cor' align='center'>$qtde_carro</td>";
			echo "<td  bgcolor='$cor' align='right'>R$ $preco</td>";
			echo "<td  bgcolor='$cor' align='right' style='padding-right:10px'>R$ $valor_total</td>";
			echo "</tr>";
		}
		echo "<tr class='pedido_th'>";
		echo "<td colspan='4' align='right'><strong>TOTAL</strong></td>";
		echo "<td align='right' style='padding-right:10px'><strong>R$ ".number_format($soma,2,',','.')."</strong></td>";
		echo "</tr>";
		echo "</table>";
	}
	echo "<br><center><a href='$PHP_SELF'>Ver outros pedidos</a></center>";

}

if (strlen($cook_fabrica)==0 AND ( strlen($cook_login_unico)>0 OR strlen($cook_login_simples)>0)) {
	include "login_unico_rodape.php";
}else{
	include "rodape.php";
}


?>