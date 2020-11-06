<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';
include_once "class/tdocs.class.php";
$tDocs = new TDocs($con, $login_fabrica);

$ajax = $_GET['ajax'];
if(strlen($ajax)>0){
	$peca   = $_GET['peca'];
	$idpeca = $_GET['idpeca'];

	$xpecas  = $tDocs->getDocumentsByRef($idpeca, "peca");
	if (!empty($xpecas->attachListInfo)) {

		$a = 1;
		foreach ($xpecas->attachListInfo as $kFoto => $vFoto) {
		    $fotoPeca = $vFoto["link"];
		    if ($a == 1){break;}
		}
		echo"<center>
			<img src='$fotoPeca' border='0'>
			</center>";
	} else {
		if ($dh = opendir('imagens_pecas/media/')) {
			echo"<center>
				<img src='imagens_pecas/media/$peca' border='0'>
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
?>

<DIV style='border: 1px solid #D3BE96; background-color: #FCF0D8;font-size:9pt;
			position:absolute;top:0;right:5px;clear:none;opacity:.9;font-weight:bold;
			overflow:auto;z-index:1' id='mensagem'>
	Carregando dados...
</DIV>

<script language='javascript'>
	function checarNumero(campo){
		var num = campo.value.replace(",",".");
		campo.value = parseInt(num);
		if (campo.value=='NaN') {
			campo.value='';
		}
	}
</script>

<!--30/01/2009   MLG     Otimizando código, deixa a página mais leve -->
<script type="text/javascript" language="JavaScript">
	function checaItem(obj,qi_disp,qi_min,qi_max) {
	// qi_disp: quantidade disponível
	// qi_min:  quantidade mínima
	// qi_max:  quantidade máxima
		checarNumero(obj);
		if (obj.value == '') return;
		var qtde = obj.value;
		if (qi_disp != 0 && qi_max != 0){
			if (parseInt(qtde) < parseInt(qi_min)) {
				alert('Quantidade abaixo da mínima permitida. A quantidade mínima para compra desta peça é de '+qi_min+'!');
				obj.value=qi_min;
			}
			if (parseInt(qtde) > parseInt(qi_max) ) {
				alert('Quantidade acima da máxima permitida. A quantidade máxima para compra desta peça é de '+qi_max+'!');
				obj.value=qi_max;
			}
		}
	}
</script>

<?
$sql="SELECT posto, capital_interior
		FROM tbl_posto WHERE posto=$login_posto";

$res = pg_exec ($con,$sql);
if(pg_numrows($res)>0){
	$posto            = trim(pg_result ($res,0,posto));
	$capital_interior = trim(pg_result ($res,0,capital_interior));
}

$sql = "SELECT valor_pedido_minimo, valor_pedido_minimo_capital
			FROM tbl_fabrica
			WHERE fabrica = $login_fabrica";
$res = pg_exec ($con,$sql);
$valor_pedido_minimo         = trim(pg_result($res,valor_pedido_minimo));
$valor_pedido_minimo_capital = trim(pg_result($res,valor_pedido_minimo_capital));

$valor_pedido_minimo         = number_format($valor_pedido_minimo,2,".",",");
$valor_pedido_minimo_capital = number_format($valor_pedido_minimo_capital,2,".",",");

if($capital_interior=="CAPITAL"){
	$msg="O valor mínimo de faturamento é de R$ $valor_pedido_minimo_capital..";
}

if($capital_interior=="INTERIOR"){
	$msg="O valor mínimo de faturamento é de R$ $valor_pedido_minimo.";
}


include 'lv_menu.php';
# AVISO
echo "<BR>";
# BUSCA POR PEÇA
echo "<table width='98%' border='0' align='center' cellpadding='2' cellspacing='2'>";
echo "<tr>";
echo "<td width='180' valign='top'>";
/*MENU*/
include "lv_menu_lateral.php";

echo "</td>";
echo "<td valign='top' align='right'>";
//echo "	<center><img src='imagens/liquidacao2.png' border='0'></center>";
echo "<form  name='frmcarrinho' align='center' method='post' action='lv_carrinho.php?acao=adicionar&tipo=liquidacao'>";
echo "<table width='90%' border='0' align='center' cellpadding='0' cellspacing='0'>";

if ($login_fabrica == 3) { // HD 60180
	$sql = "SELECT distinct peca
			INTO TEMP peca_linha_$login_posto
			FROM tbl_lista_basica
			JOIN tbl_produto USING(produto)
			WHERE fabrica = $login_fabrica
			AND   tbl_produto.ativo IS TRUE
			AND   linha  IN (SELECT linha FROM tbl_posto_fabrica JOIN tbl_posto_linha USING (posto) WHERE tbl_posto_linha.posto = $login_posto AND fabrica = $login_fabrica);

			CREATE INDEX peca_linha_peca_$login_posto ON peca_linha_$login_posto(peca);";
	$res = pg_exec($con,$sql);
	$sql_join = " JOIN peca_linha_$login_posto USING(peca) ";
}

$sql = "SELECT tbl_peca.peca,
					tbl_peca.referencia    ,
					tbl_peca.descricao     ,
					tbl_peca.preco_sugerido,
					tbl_peca.ipi  ,
					tbl_peca.promocao_site ,
					tbl_peca.informacoes          ,
					tbl_peca.qtde_max_site,
					tbl_peca.qtde_minima_site,
					tbl_peca.qtde_disponivel_site,
					tbl_peca.multiplo_site
		FROM tbl_peca
		$sql_join
		WHERE tbl_peca.fabrica = $login_fabrica
		AND   tbl_peca.liquidacao IS TRUE
		AND tbl_peca.ativo is not false
		AND tbl_peca.peca NOT IN (SELECT peca FROM tbl_peca_fora_linha WHERE fabrica = $login_fabrica)
		AND (qtde_disponivel_site notnull or qtde_disponivel_site <> 0)
		AND tbl_peca.peca NOT IN (SELECT DISTINCT peca_de FROM tbl_depara WHERE fabrica = $login_fabrica)
		ORDER BY posicao_site, referencia";
//		echo $sql;
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
		echo "<table width='98%' border='0' align='center' cellpadding='5' cellspacing='0'  style='font-size:10px;'><BR>\n";
		echo "<tr style='height: 1.1em;background-color:#6DA5E2;color:white;font-weight:bold;text-align:center;padding:0 0.5ex'>\n";
		echo "<td>\n\tFoto\n</td>\n";
		echo "<td>\n\tPeça\n</td>\n";
		echo "<td>\n\tQtde. Disponível\n</td>\n";
		echo "<td>\n\tValor Unitário\n</td>\n";
		echo "<td>\n\tQtde. Compra\n</td>\n";
		if ($login_fabrica==3) {
			echo "<td>\n\t&nbsp;\n</td>\n"; //MLG Coluna para o botão comprar
		}
		echo "</tr>";   // 29/1/2009 MLG Estava faltando este /tr, que fecha o cabeçalho
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
			$qtde_max_site        = trim(pg_result ($res,$i,qtde_max_site));
			$qtde_minima_site     = trim(pg_result ($res,$i,qtde_minima_site));
			$multiplo_site        = trim(pg_result ($res,$i,multiplo_site));
			if(strlen($multiplo_site)==0){$multiplo_site = 1;}
			//if($i%4==0){echo "</td></tr>\n<tr><td align='center'>";}
			if (strlen($qtde_max_site)==0){
				$qtde_max_site = 500;
			}
			if (strlen($qtde_minima_site)==0){
				$qtde_minima_site = 1;
			}
			if (strlen($qtde_disponivel_site)==0){
				$qtde_disponivel_site = 0;
			}

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
						AND   tbl_posto_linha.linha IN (
							SELECT DISTINCT tbl_produto.linha
							FROM tbl_produto
							JOIN tbl_lista_basica USING(produto)
							JOIN tbl_peca USING(peca)
							WHERE peca = $peca
						)
					)";
			$res2 = pg_exec ($con,$sql2);
			if(pg_numrows($res2)<1) {
				$preco       = 0;
///				continue;
			}else{
				$preco       = trim(pg_result ($res2,0,preco));
			}
			$preco_formatado = number_format($preco, 2, ',', '');
			$preco_formatado = str_replace(".",",",$preco_formatado);

			if($i%2==0) $cor='#DAE3E4';
			else        $cor='#EFEFEF';

			echo "<tr>\n";
			echo "<td bgcolor='$cor' align='center' width='60'>\n";

			$xpecas  = $tDocs->getDocumentsByRef($peca, "peca");
			if (!empty($xpecas->attachListInfo)) {

				$a = 1;
				foreach ($xpecas->attachListInfo as $kFoto => $vFoto) {
				    $fotoPeca = $vFoto["link"];
				    if ($a == 1){break;}
				}
				echo "<a href='$PHP_SELF?ajax=true&idpeca=$peca&peca=$fotoPeca&keepThis=trueTB_iframe=true&height=340&width=420' title='$referencia' class='thickbox'>".
						"<center>".
						"	<img src='$fotoPeca' border='0' width='50'>".
						"</center></a>";
				echo "<input type='hidden' name='peca_imagem' value='$fotoPeca'>";
			} else {

				$saida == "";
				if ($dh = opendir('imagens_pecas/pequena/')) {
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
					echo "<a href='$PHP_SELF?ajax=true&idpeca=$peca&peca=$filename_final&keepThis=trueTB_iframe=true&height=340&width=420' title='$referencia' class='thickbox'>".
							"<center>".
							"	<img src='imagens_pecas/pequena/$filename_final' border='0' width='50'>".
							"</center></a>";
					echo "<input type='hidden' name='peca_imagem' value='$filename_final'>";
				}else{
					echo "<center><img src='imagens_pecas/semimagem.jpg' border='0'></center>";
					echo "<input type='hidden' name='peca_imagem' value='semimagem.jpg' width='50'>";
				}
			}
			echo "</td>\n";
			echo "<td  bgcolor='$cor' valign='top'>";
			echo "<font color='#FF0000' size='1'><b>EM LIQUIDAÇÃO</b></font><BR>\n";
			echo "<font color='#000000' size='1'><b>$referencia - $descricao</b><BR>$informacoes</font><BR>\n";
			echo "</td>\n";
			echo "<td bgcolor='$cor' valign='top' align='center'>$qtde_disponivel_site";
			echo "</td>\n";
			echo "<td bgcolor='$cor' valign='top' align='center'>R$ $preco_formatado";
			echo "</td>\n";

			echo "<td bgcolor='$cor' valign='top' align='center'>\n";
			echo "<input type='hidden' name='liquida_peca_$i'	value='$peca' width='3'>\n";
			echo "<input type='hidden' name='qtde_maxi_$i'		value='$qtde_max_site'>\n";
			echo "<input type='hidden' name='qtde_disp_$i'		value='$qtde_disponivel_site'>\n";
			echo "<input type='hidden' name='qtde_min_$i'		value='$qtde_minima_site'>\n";
			echo "<input type='hidden' name='liquida_preco_$i'	value='$preco_formatado' width='3'>\n";

			if($multiplo_site > 1){

					echo "\n<select name='liquida_qtde_$i' class='Caixa'";
					if ($login_fabrica==3) {
						echo " \n\t\tonChange='document.frm_liquida_$i.liquida_qtde_0.value=this.value;'";
					}
					echo ">\n\t\t<option value=''></option>\n";
					for($h=1;$h<=20;$h++){
						$aux = $h * $multiplo_site;
						if(($aux>=$qtde_minima_site) AND (strlen($qtde_max_site)>0 AND $aux<=$qtde_max_site))echo "\t\t<option value='$aux'>$aux</option>\n";
					}
					echo "</select>\n";
			}else{
				echo "<input type='text' name='liquida_qtde_$i' value='' size='3' maxlength='4'";
				echo " \n\t\tonChange='javascript:checaItem(this,$qtde_disponivel_site,$qtde_minima_site,$qtde_max_site);";
				if ($login_fabrica==3) {
					echo " \n\t\t\tdocument.frm_liquida_$i.liquida_qtde_0.value=this.value;'";
				}
				echo ">\n";
			}
			if ($login_fabrica==3) {    // Para evitar o problema dos FORM aninhados...
				echo "<FORM name='void' method='post' action=''><INPUT type='hidden' value='' name='void'></FORM>";
			}
		echo "</td>\n";
		if ($login_fabrica==3) {
			echo "\t<TD valign='top' align='center' bgcolor='$cor'>\n";
			echo "\t\t<FORM name='frm_liquida_$i' method='post' ".
				"action='lv_carrinho.php?acao=adicionar&tipo=liquidacao'>\n";
			echo "\t\t\t<INPUT type='hidden' name='btn_comprar' id='btn_comprar_item_$i' value='' class='botao'>\n";
			echo "\t\t\t<INPUT type='hidden' name='liquida_qtde_0'	value='' id='qtde_item_$i'>\n";
			echo "\t\t\t<INPUT type='hidden' name='liquida_peca_0'	value='$peca'>\n";
			echo "\t\t\t<INPUT type='hidden' name='liquida_preco_0'	value='$preco_formatado'>\n";
			echo "\t\t\t<INPUT type='hidden' name='qtde_maxi_0'		value='$qtde_max_site'>\n";
			echo "\t\t\t<INPUT type='hidden' name='qtde_disp_0'		value='$qtde_disponivel_site'>\n";
			echo "\t\t\t<INPUT type='hidden' name='qtde_linha'		value='1'>\n";
?>		<IMG onclick="if (document.getElementById('btn_comprar_item_<?=$i?>').value == '') {
							document.getElementById('btn_comprar_item_<?=$i?>').value='Comprar';
							document.frm_liquida_<?=$i?>.submit();
						} else {
						alert ('Aguarde submissão');}"
						 src='imagens/bt_comprar_pq2.gif'
					   style='cursor: pointer;'>
			</FORM>
			</TD>
		<?}
		echo "</tr>\n";
		}
		echo "<tr>\n";
		echo "<td align='center' colspan='4'>\n";
		echo "<input type='hidden' name='qtde_linha' value='$i'>\n";
		echo "<input type='submit' name='btn_comprar' value='Comprar' class='botao'>\n";
		echo "</td>\n";
		echo "<tr>\n";
	}
	echo "<script>document.getElementById('mensagem').style.visibility = 'hidden';</script>\n";

echo "</table>\n";
echo "</form>\n";
### P? PAGINACAO###

echo "</td>\n";
echo "</tr>\n";
echo "</table>\n";
echo "</td>\n";
echo "</tr>\n";
echo "</table>\n";

include 'rodape.php';
?>