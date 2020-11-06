<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';
include_once "../class/tdocs.class.php";
$tDocs = new TDocs($con, $login_fabrica);

$ajax = $_GET['ajax'];
if(strlen($ajax)>0){
	$peca   = $_GET['peca'];
	$idpeca = $_GET['idpeca'];
	$xpecas = $tDocs->getDocumentsByRef($idpeca, "peca");
	if (!empty($xpecas->attachListInfo)) {

		$a = 1;
		foreach ($xpecas->attachListInfo as $kFoto => $vFoto) {
		    $fotoPeca = $vFoto["link"];
		    if ($a == 1){break;}
		}
		echo "<center>
				<img src='$fotoPeca' border='0'>
				</center>";
	} else {

		if ($dh = opendir('../imagens_pecas/media/')) {
			echo"<center>
				<img src='../imagens_pecas/media/$peca' border='0'>
				</center>";
		}
	}
	exit;
}


// Liberado para todos os Postos da Britania - HD 5686 - Fabio
if ($login_fabrica <> 3) {
	header ("Location: menu_pedido.php");
}

session_name("carrinho");
session_start();

#$indice = $_SESSION[cesta][numero];

$numero_pedido = $_GET['pedido'];
$status        = $_GET['status'];

$layout_menu = 'pedido';
$title="BEM-VINDO a loja virtual da Britania ";

include "cabecalho.php";
echo "<div style='position: absolute;opacity:.90;z-index:1; overflow: auto;position:absolute;top:0px;right: 5px;'><table id='mensagem' style='border: 1px solid rgb(211, 190, 150); background-color: rgb(252, 240, 216);' ><tbody><tr><td><b>Carregando dados...</b></td></tr></tbody></table></div>";


include 'loja_menu.php';
# AVISO
echo "<BR>"; 
# BUSCA POR PEÇA
echo "<table width='98%' border='0' align='center' cellpadding='2' cellspacing='2'>";
echo "<tr>";
echo "<td width='180' valign='top' align='left'>";
/*MENU*/
include "loja_menu_lateral.php";

echo "</td>";
echo "<td valign='top' align='right'>";
//echo "	<center><img src='imagens/liquidacao2.png' border='0'></center>";
echo "<form action='loja_carrinho_teste.php?acao=adicionar&tipo=liquidacao' method='post' name='frmcarrinho' align='center'>";
echo "<table width='90%' border='0' align='center' cellpadding='0' cellspacing='0'>";

$sql = "SELECT tbl_peca.peca,
					tbl_peca.referencia    ,
					tbl_peca.descricao     ,
					tbl_peca.preco_sugerido,
					tbl_peca.ipi  ,
					tbl_peca.promocao_site ,
					tbl_peca.informacoes          ,
					tbl_peca.qtde_disponivel_site,
					tbl_peca.posicao_site
		FROM tbl_peca 
		WHERE tbl_peca.fabrica = $login_fabrica
		AND   tbl_peca.liquidacao IS TRUE
		AND tbl_peca.ativo is not false
		AND tbl_peca.peca NOT IN (SELECT peca FROM tbl_peca_fora_linha WHERE fabrica = $login_fabrica)
		ORDER BY posicao_site, referencia";
$res = pg_exec($con,$sql);
	if(pg_numrows($res) == 0){
		echo "<tr>\n";
		echo "<td align='center'>\n";
				echo "<FONT SIZE='2' COLOR='#FF0000'><b>Nenhuma peça encontrada!</b></FONT>";
		echo "</td>";
		echo "</tr>";
	}else{
		echo "<tr>\n";
		echo "<td align='center'>\n";
		echo "<table width='98%' border='0' align='center' cellpadding='5' cellspacing='0'  style='font-size:10px'><BR>\n";			
		echo "<tr>\n";
		echo "<td bgcolor='#6DA5E2'  align='center'>\n";
		echo "<font color='#FFFFFF'><B>Foto</B></FONT>";
		echo "</td>\n";
		echo "<td bgcolor='#6DA5E2' align='center'>\n";
		echo "<font color='#FFFFFF'><B>Peça</B></FONT>";
		echo "</td>\n";
		echo "<td bgcolor='#6DA5E2' align='center'>\n";
		echo "<font color='#FFFFFF'><B>Qtde Disponível</B>";
		echo "</td>\n";
		echo "<td bgcolor='#6DA5E2' align='center'>\n";
		echo "<font color='#FFFFFF'><B>Valor Unitário</B>";
		echo "</td>\n";
		echo "<td bgcolor='#6DA5E2' align='center'>\n";
		echo "<font color='#FFFFFF'><B>Prioridade <acronym class='ac' title='Prioridade de exibição da peça na Loja Virtual. (1,2,3...1000)'>[?]</acronym> </B></FONT>";
		echo "</td>\n";
		for ($i = 0 ; $i < pg_numrows($res); $i++){
			$peca                 = trim(pg_result ($res,$i,peca));
			$referencia           = trim(pg_result ($res,$i,referencia));
			$preco_sugerido       = trim(pg_result ($res,$i,preco_sugerido));
			$ipi                  = trim(pg_result ($res,$i,ipi));
			$descricao            = trim(pg_result ($res,$i,descricao));
		//	$descricao            = substr($descricao,0,25)."...";
			$promocao_site        = trim(pg_result ($res,$i,promocao_site));
			$qtde_disponivel_site = trim(pg_result ($res,$i,qtde_disponivel_site));
			$informacoes          = trim(pg_result ($res,$i,informacoes));
			$posicao_site         = trim(pg_result ($res,$i,posicao_site));

				
			//if($i%4==0){echo "</td></tr>\n<tr><td align='center'>";}

			$sql2 = "SELECT distinct tbl_tabela_item.preco
					FROM tbl_tabela
					JOIN tbl_tabela_item USING(tabela)
					WHERE peca  = $peca
					AND tbl_tabela.fabrica = $login_fabrica
					AND   tbl_tabela.tabela IN (
						SELECT tbl_tabela.tabela
						FROM tbl_posto_linha 
						JOIN tbl_tabela       USING(tabela)
						JOIN tbl_posto        USING(posto) 
						JOIN tbl_linha        USING(linha)
						WHERE   tbl_posto_linha.linha in (
							SELECT DISTINCT tbl_produto.linha
							FROM tbl_produto 
							JOIN tbl_lista_basica USING(produto)
							JOIN tbl_peca USING(peca)
							WHERE peca = $peca
							AND tbl_peca.fabrica=$login_fabrica
						 )
					)";
			$res2 = pg_exec ($con,$sql2);
			if(pg_numrows($res2)<1) {
				$preco       = 0;

			}else{
				$preco_formatado = "";
				for ($j = 0 ; $j < pg_numrows($res2); $j++){
					$X_preco = number_format(trim(pg_result ($res2,$j,preco)),2,",",".");
					$preco_formatado .= "<a href='preco_cadastro.php?peca=$peca' target='blank'><strong><font color='#FF0000'>R$ $X_preco</font></strong></a> <BR>";
				}
			}
			
			if($i%2==0) $cor='#DAE3E4';
			else        $cor='#EFEFEF';

			echo "<tr>\n";
			echo "<td bgcolor='$cor' align='center' width='60'>\n";
				$xpecas = $tDocs->getDocumentsByRef($peca, "peca");
				if (!empty($xpecas->attachListInfo)) {

					$a = 1;
					foreach ($xpecas->attachListInfo as $kFoto => $vFoto) {
					    $fotoPeca = $vFoto["link"];
					    if ($a == 1){break;}
					}
					echo "<a href='$PHP_SELF?ajax=true&idpeca=$peca&peca=$filename_final&keepThis=trueTB_iframe=true&height=340&width=420' title='$referencia' class='thickbox'><center><img src='$fotoPeca' border='0' width='50'></center></a>";
					echo "<input type='hidden' name='peca_imagem' value='$fotoPeca'>";
				} else {
					$saida == "";
					if ($dh = opendir('../imagens_pecas/pequena/')) {
						$contador=0;
						$filename_final = "";
						while (false !== ($filename = readdir($dh))) {
							if($contador == 1) break;
							if (strpos($filename,$referencia) !== false){
								$contador++;
								//$peca_referencia = ntval($peca_referencia);
								$po = strlen($referencia);
								if(substr($filename, 0,$po)==$referencia){ 
									$filename_final = $filename;					
								}
							}
						}
					}
					if(strlen($filename_final)>0){
						echo "<a href='$PHP_SELF?ajax=true&idpeca=$peca&peca=$filename_final&keepThis=trueTB_iframe=true&height=340&width=420' title='$referencia' class='thickbox'><center><img src='../imagens_pecas/pequena/$filename_final' border='0' width='50'></center></a>";
						echo "<input type='hidden' name='peca_imagem' value='$filename_final'>";
					}else{
						echo "<center><img src='../imagens_pecas/semimagem.jpg' border='0'></center>";
						echo "<input type='hidden' name='peca_imagem' value='semimagem.jpg' width='50'>";
					}
				}
			echo "</td>";
			echo "<td  bgcolor='$cor' valign='top' align='left'>";
			echo "<font color='#FF0000' size='1'><b>EM LIQUIDAÇÃO</b></font><BR>\n";
			echo "<a href='peca_cadastro.php?peca=$peca' target='blank'>";
			echo "<font color='#000000' size='1'><b>$referencia - $descricao</b><BR>$informacoes</font><BR>\n";
			echo "</a>";
			echo "</td>";
			echo "<td bgcolor='$cor' valign='top' align='center'>$qtde_disponivel_site";
			echo "</td>";
			echo "<td bgcolor='$cor' valign='top' align='center'>$preco_formatado";
			echo "</td>";
			
			echo "<td bgcolor='$cor' valign='top' align='center'>";
			echo "$posicao_site ";
			echo "</td>";
			echo "</tr>";
		}

	}
	echo "<script>document.getElementById('mensagem').style.visibility = 'hidden';</script>";

echo "</table>\n";
echo "</form>";
### P? PAGINACAO###

echo "</td>";
echo "</tr>";
echo "</table>";
echo "</td>";
echo "</tr>";
echo "</table>";

include 'rodape.php';
?>