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

$layout_menu = 'pedido';
$title="Detalhes do produto!";
include "cabecalho.php";
?>
	<style type="text/css">


		ul#intro,ul#intro li{list-style-type:none;margin:0;padding:0}
		ul#intro{width:100%;overflow:hidden;margin-bottom:10px}
		ul#intro li{float:left;width:98%;margin-right:10px;padding: 5px 5;}
		li#produto{background: #CEDFF0}
		ul#intro li#more{margin-right:0;background: #7D63A9}
		ul#intro p,ul#intro h3{margin:0;padding: 0 10px}
		
		ul#intro2,ul#intro2 li{list-style-type:none;margin:0;padding:0}
		ul#intro2{width:100%;overflow:hidden;margin-bottom:10px}
		ul#intro2 li{float:left;width:98%;margin-right:10px;padding: 5px 5;}
		li#infor{background: #B4B6B6}
		ul#intro2 li#more{margin-right:0;background: #7D63A9}
		ul#intro p,ul#intro2 h3{margin:0;padding: 0 10px}

		ul#intro3,ul#intro3 li{list-style-type:none;margin:0;padding:0}
		ul#intro3{width:100%;overflow:hidden;margin-bottom:10px}
		ul#intro3 li{float:left;width:98%;margin-right:10px;padding: 5px 5;}
		li#maisprod{background: #FFBA75}
		ul#intro3 li#more{margin-right:0;background: #7D63A9}
		ul#intro p,ul#intro3 h3{margin:0;padding: 0 10px}

	</style>
<!--border: 2px solid Black; -->
<script type="text/javascript" src="js/jquery-1.1.2.pack.js"></script>

<script src="../js/jquery.MultiFile.js" type="text/javascript" language="javascript"></script>
<script src="../js/jquery.MetaData.js" type="text/javascript" language="javascript"></script>


<script language='javascript'>
function checarNumero(campo){
	var num = campo.value.replace(",",".");
	campo.value = parseInt(num);
	if (campo.value=='NaN') {
		campo.value='';
	}
}
</script>

<script type="text/javascript" src="../js/niftycube.js"></script>
<script type="text/javascript" src="../js/niftyLayout.js"></script>


<?

$cod_produto = $_GET['cod_produto'];

	$sql = "SELECT  
				tbl_peca.peca            ,
				referencia              ,
				tbl_peca.ipi            ,
				descricao               ,
				estoque                 ,
				garantia_diferenciada   , 
				informacoes             ,
				linha_peca              ,
				multiplo_site           ,
				qtde_minima_site        ,
				qtde_max_site           ,
				qtde_disponivel_site
			FROM tbl_peca 
			WHERE tbl_peca.peca='$cod_produto'";

	$res = pg_exec ($con,$sql);

	if(pg_numrows($res)>0){
		$peca						= trim(pg_result ($res,0,peca));
		$referencia					= trim(pg_result ($res,0,referencia));
		$ipi						= trim(pg_result ($res,0,ipi));
		$descricao					= trim(pg_result ($res,0,descricao));
		$estoque					= trim(pg_result ($res,0,estoque));
		$garantia_diferenciada		= trim(pg_result ($res,0,garantia_diferenciada));
		$informacoes				= trim(pg_result ($res,0,informacoes));
		$linha						= trim(pg_result ($res,0,linha_peca));
		$multiplo_site 				= trim(pg_result ($res,0,multiplo_site));
		$qtde_minima_site			= trim(pg_result ($res,0,qtde_minima_site));
		$qtde_max_site				= trim(pg_result ($res,0,qtde_max_site));
		$qtde_disponivel_site		= trim(pg_result ($res,0,qtde_disponivel_site));

	
		$sql3 = "SELECT distinct tbl_tabela_item.preco
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
		$res3 = pg_exec ($con,$sql3);
		$preco = trim(pg_result ($res3,0,preco));
		$preco_formatado = number_format($preco,2,'.',',');

		$parcelas = ($preco/2);
		$parcelas = number_format($parcelas, 2, ',', '');
		$preco_formatado    = number_format($preco, 2, ',', '');

		$sql4 = "SELECT distinct tbl_linha.nome as linha_descricao,
						tbl_linha.linha ,
						tbl_familia.descricao as familia_descricao,
						tbl_familia.familia
					from tbl_peca
					join tbl_lista_basica on tbl_peca.peca = tbl_lista_basica.peca
					join tbl_produto on tbl_lista_basica.produto = tbl_produto.produto
					join tbl_linha on tbl_produto.linha = tbl_linha.linha
					join tbl_familia on tbl_familia.familia = tbl_produto.familia
					where tbl_peca.peca = $peca 
					and tbl_peca.fabrica = $login_fabrica
					limit 1";
		$res4 = pg_exec ($con,$sql4);
		$linha_peca	    = trim(pg_result ($res4,0,linha));
		$familia_peca	= trim(pg_result ($res4,0,familia));
		$linha_peca_desc    = ucfirst(trim(pg_result ($res4,0,linha_descricao)));
		$familia_peca_desc	= ucfirst(trim(pg_result ($res4,0,familia_descricao)));
		$cabeca = " <a href='loja_completa.php'><font color='#4A4A4A'>Loja Virtual</font></a> 
		<font color='#4A4A4A'>></font> <a href='loja_completa.php?categoria=$linha_peca&categoria_tipo=linha'><font color='#4A4A4A'>$linha_peca_desc</font></a> <font color='#4A4A4A'>></font> <a href='loja_completa.php?categoria=$familia_peca&categoria_tipo=familia'><font color='#4A4A4A'>$familia_peca_desc</font></a> </a>";

	}

//corpo do produto
include 'loja_menu.php';
echo "<BR>";
echo "<form action='loja_carrinho_teste.php?acao=adicionar' method='post' name='frmcarrinho' align='center'>";
echo "<table width='98%' border='0' align='center' cellpadding='3' cellspacing='0'>";

echo "<tr>";
echo "<td width='180' valign='top' align='left'>";
include "loja_menu_lateral.php";
echo "</td>";

echo "<td align='left' valign='top' class='Conteudo'>";
echo "<table width='95%' border='0' align='center' cellpadding='5' cellspacing='5' style = 'font-size:11px'>";

echo "<tr>";
echo "<td colspan='2'>";
echo " $cabeca";
echo "</td>";
echo "<tr>";
echo "<td colspan='2' height='1px' bgcolor='#4A4A4A'>";
echo "</td>";
echo "<tr>";
echo "<td width='130' align='center' valign='top'><BR>";
	$xpecas = $tDocs->getDocumentsByRef($peca, "peca");
	if (!empty($xpecas->attachListInfo)) {

		$a = 1;
		foreach ($xpecas->attachListInfo as $kFoto => $vFoto) {
		    $fotoPeca = $vFoto["link"];
		    if ($a == 1){break;}
		}

		echo "<div class='contenedorfoto'>";
			echo "<a href='$PHP_SELF?ajax=true&idpeca=$peca&peca=$fotoPeca&keepThis=trueTB_iframe=true&height=340&width=420' title='$referencia' class='thickbox'>";
			echo "<img src='$fotoPeca' width=100 border='0'>";
			echo "<input type='hidden' name='peca_imagem' value='$fotoPeca'>";
			echo "<br><font size='-4' color='#999999'>clique na foto para visualizar</font>";
			echo "</a>";
		echo "</div>";

	} else {

		if ($dh = opendir('../imagens_pecas/pequena/')) {
			$contador=0;
			while (false !== ($filename = readdir($dh))) {
				if($contador == 1) break;
				if (strpos($filename,$referencia) !== false){
					$contador++;
					//$peca_referencia = ntval($peca_referencia);
					$po = strlen($referencia);
					if(substr($filename, 0,$po)==$referencia){
						$file_final = $filename;
					}
				}
			}
		}
		echo "<div class='contenedorfoto'>";
		if(strlen($file_final)>0){
			echo "<a href='$PHP_SELF?ajax=true&peca=$file_final&keepThis=trueTB_iframe=true&height=340&width=420' title='$referencia' class='thickbox'>";
			echo "<img src='../imagens_pecas/pequena/$file_final' border='0'>";
			echo "<input type='hidden' name='peca_imagem' value='$file_final'>";
			echo "<br><font size='-4' color='#999999'>clique na foto para visualizar</font>";
			echo "</a>";
		}else{
			echo "<img src='../imagens_pecas/semimagem.jpg' border='0'>";
			echo "<input type='hidden' name='peca_imagem' value='$filename'>";
		}
	echo "</div>";
	}

echo "</td>";
echo "<td valign='top' width='80%'><BR>";
	echo "<input type='hidden' name='cod_produto' value='$cod_produto'>";

	echo "<input type='hidden' name='valor'       value='$preco'>";
	echo "<input type='hidden' name='ipi'         value='$ipi'>";
	echo "<input type='hidden' name='descricao'   value='$descricao'>";
	echo "<input type='hidden' name='linha'       value='$linha'>";
	echo "<input type='hidden' name='qtde_maxi'   value='$qtde_max_site'>";
	echo "<input type='hidden' name='qtde_disp'   value='$qtde_disponivel_site'>";

echo "<ul id=\"intro\">";
	echo "<li id=\"produto\">";
	echo "<font color='#0B84DD' size='3'><strong>$descricao</strong></font>";
	echo "</li></ul>";
	echo "<font color='#444751' size='1'>Referência: $referencia<BR>";
	echo " Qtde Disponível: $qtde_disponivel_site <BR>";
	echo " Qtde Múltipla: $multiplo_site <BR>";
	echo " Qtde Máxima: $qtde_max_site </font><BR>";
	echo "<font color='#E82F00' size='3'><B>Valor: R$ $preco_formatado</B></font><BR>";
	echo "<font color='#5C6872' size='1'>* Valor + IPI: $ipi %</font><BR>";
if(1==2){
	echo "Qtde: ";
	
if (strlen($qtde_max_site)==0){
			$qtde_max_site = 500;
		}
	if($multiplo_site > 1){
		
		echo "<select name='qtde' class='Caixa' >";
		for($i=1;$i<=20;$i++){
			$aux = $i * $multiplo_site;
			if(($aux>=$qtde_minima_site) AND (strlen($qtde_max_site)>0 AND $aux<=$qtde_max_site))echo "<option value='$aux'>$aux</option>";
		}
		echo "</select>";
	}else{
		echo "<input type='text' size='2' maxlength='3' name='qtde' value='";
		if(strlen($qtde_minima_site)==0){ $qtde_minima_site= "1"; echo $qtde_minima_site;}
		else                             $qtde_minima_site= "$qtde_minima_site";
		echo "'";
		if (strlen($qtde_disponivel_site)>0){
			echo "onblur='javascript:
			checarNumero(this);
			if (this.value < $qtde_minima_site || this.value==\"\" ) {
				alert(\"Quantidade abaixo da mínima permitida. A quantidade mínima para compra desta peça é de $qtde_minima_site!\");
				this.value=\"$qtde_minima_site\";
			}
			if (this.value > $qtde_max_site || this.value==\"\" ) {
				alert(\"Quantidade acima da máxima permitida. A quantidade máxima para compra desta peça é de $qtde_max_site!\");
				this.value=\"$qtde_minima_site\";
			}'";
		}else{
			echo "onblur='javascript: checarNumero(this);'";
		}
		echo ">";
	}
//	echo "&nbsp;&nbsp;&nbsp;<input type='submit' name='btn_comprar' value='Comprar' class='botao'>";
}
echo "</td>";
echo "</tr>";
echo "<tr>";
echo "<td align='top' class='Conteudo' colspan='2'><br>";
	if(strlen($informacoes)>0){
		echo "<ul id=\"intro2\">";
		echo "<li id=\"infor\">";
		echo "<font size='4'><B>+ Informações</b></font>";
		echo "</li></ul>";
		echo "&nbsp;$informacoes";
		echo "<BR><BR>";
		
	}
	echo "</td>";
echo "</tr>";



echo "<tr>";
echo "<td align='top'  class='Conteudo' colspan='2'><br>";
	echo "<ul id=\"intro3\">";
	echo "<li id=\"maisprod\">";
	echo "<font size='4'><B>+ Produtos</b></font>";
	echo "</li></ul>";
$sql = "SELECT * FROM (
		SELECT DISTINCT tbl_peca.peca          ,
						tbl_peca.referencia    ,
						tbl_peca.descricao     ,
						tbl_peca.preco_sugerido,
						tbl_peca.ipi           ,
						tbl_peca.promocao_site ,
						tbl_peca.qtde_disponivel_site,
						tbl_peca.posicao_site

		FROM tbl_peca
		JOIN tbl_lista_basica on tbl_peca.peca = tbl_lista_basica.peca
		WHERE tbl_lista_basica.produto IN( 
			SELECT tbl_lista_basica.produto 
			FROM tbl_lista_basica 
			WHERE tbl_lista_basica.peca = $peca
		)
		AND tbl_peca.fabrica = $login_fabrica
		AND tbl_peca.peca  <> $peca
		) AS X
		ORDER BY  X.posicao_site limit 3;


";
//		AND tbl_peca.promocao_site is true


	$res = pg_exec($con,$sql);
/*echo nl2br($sql);
echo "<BR>".pg_numrows($res);*/
		for ($i = 0 ; $i < pg_numrows($res); $i++){
			$peca                 = trim(pg_result ($res,$i,peca));
			$referencia           = trim(pg_result ($res,$i,referencia));
			$preco_sugerido       = trim(pg_result ($res,$i,preco_sugerido));
			$ipi                  = trim(pg_result ($res,$i,ipi));
			$descricao            = trim(pg_result ($res,$i,descricao));
			$descricao            = substr($descricao,0,25)."...";
			$promocao_site        = trim(pg_result ($res,$i,promocao_site));
			$qtde_disponivel_site = trim(pg_result ($res,$i,qtde_disponivel_site));

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
				continue;
			}else{
				$preco       = trim(pg_result ($res2,0,preco));
			}
			$preco_formatado = number_format($preco, 2, ',', '');
			$preco_formatado = str_replace(".",",",$preco_formatado);


			echo "\n<div rel='box_content' class=\"content_box\">";
				if( $preco > 0){
					echo "<a href='loja_detalhe.php?cod_produto=$peca'>";
				}
				$saida == "";
				if ($dh = opendir('../imagens_pecas/pequena/')) {
					$contador=0;
					$filename_final = "";
					while (false !== ($filename = readdir($dh))) {
						if($contador == 1) break;
						if (strpos($filename,$referencia) !== false){
							$contador++;
							$po = strlen($referencia);
							if(substr($filename, 0,$po)==$referencia){ 
								$filename_final = $filename;
							}
						}
					}
				}
				if(strlen($filename_final)>0){
					echo "<center><img src='../imagens_pecas/pequena/$filename_final' border='0'></center>";
					echo "<input type='hidden' name='peca_imagem' value='$filename_final'>";
				}else{
					echo "<center><img src='../imagens_pecas/semimagem.jpg' border='0'></center>";
					echo "<input type='hidden' name='peca_imagem' value='semimagem.jpg'>";
				}
				if( $preco > 0){
					echo "</a>";
				}

	
				if ($promocao_site == 't' OR $qtde_disponivel_site > 0) {
					echo "<font color='#FF0000'  size='1'><b>EM PROMOÇÃO</b></font><BR>\n";
				}
				if( $preco > 0 ){
					echo "<a href='loja_detalhe.php?cod_produto=$peca' ><font size='1' color='#363636'> <b>$referencia</b> - $descricao</font>";
				}else{
					echo "<font size='1' color='#363636'> <b>$referencia</b> - $descricao</font>";
					echo "<br><font color='#333333' size='1'><b>Indisponível</b></font>\n";
				}
				echo "<br><font color='#FC6625' size='1'><b>R$ $preco_formatado</b></font>";
				if( $preco > 0 ){
					echo "</a>\n";
				}
				echo "</div>\n";
		}

echo "</td>";
echo "</tr>";


echo "</table>";
echo "</td>";
echo "</tr>";
echo "</table>";
echo "</form>";

include "rodape.php";
?>