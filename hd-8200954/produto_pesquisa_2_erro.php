
<div style="float:left;width:97%;height:40px;background: url('css/table/imagem/ui-bg_highlight-soft_75_cccccc_1x100.png');">
</div>
<div style="float:right;width:3%;background: url('css/table/imagem/ui-bg_highlight-soft_75_cccccc_1x100.png');height:40px;">
	<a href="#" onclick="closeMessage('Rodrigo Pedroso')" width="50px" height="30px" alt="Fechar" title="Fechar"><img src="css/modal/excluir.png"/></a>
</div>
<div style="background:transparent;position: relative; height: 460px;width:100%;overflow:auto">
<?php
$contador_ver ="0";
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include 'autentica_usuario.php';

#include 'cabecalho_pop_produtos.php';

header("Expires: 0");
header("Cache-Control: no-cache, public, must-revalidate, post-check=0, pre-check=0");
header("Pragma: no-cache, public");

if ($login_fabrica == 14) {
	$sql_familia = " JOIN tbl_familia on tbl_familia.familia = tbl_produto.familia ";
}
$msg_confirma ="0";
if ($login_fabrica == 30) {

	$tipo = trim(strtolower($_GET['tipo']));

	if ($tipo == "referencia") {

		$referencia = trim(strtoupper($_GET["campo"]));
		$referencia = str_replace(".","",$referencia);
		$referencia = str_replace(",","",$referencia);
		$referencia = str_replace("-","",$referencia);
		$referencia = str_replace("/","",$referencia);

		$sql = "SELECT CASE WHEN tbl_produto.marca = 164 then 't' ELSE 'f' END as itatiaia
				FROM  tbl_produto
				JOIN  tbl_linha   on tbl_produto.linha   = tbl_linha.linha
				WHERE tbl_produto.referencia_pesquisa like '%$referencia%'
				AND   tbl_linha.fabrica = $login_fabrica
				AND   tbl_produto.ativo
				AND   tbl_produto.produto_principal;";

		$res = @pg_exec($con,$sql);

	} else {

		$descricao = trim(strtoupper($_GET["campo"]));

		$sql = "SELECT CASE WHEN tbl_produto.marca = 164 then 't' ELSE 'f' END as itatiaia
				FROM  tbl_produto
				JOIN  tbl_linha   on tbl_produto.linha   = tbl_linha.linha
				WHERE (UPPER(tbl_produto.descricao)      LIKE '%$descricao%' 
				    OR UPPER(tbl_produto.nome_comercial) LIKE '%$descricao%' )
				AND   tbl_linha.fabrica = $login_fabrica
				AND   tbl_produto.ativo
				AND   tbl_produto.produto_principal;";

		$res = @pg_exec($con,$sql);

	}
	
	if (@pg_numrows($res) > 0) {

		$itatiaia = pg_result($res,0,'itatiaia');

		if ($itatiaia == 't') {
			?>
			<div class='demo_jui' onmouseover="alertaItaitaia();">
			<?php
			$contador_ver ="1";
			echo "<script language='javascript'>";
			echo "setTimeout('window.close()',500);";
			echo "</script>";
			exit;
		}
	}
}
?>



<?php
if ($login_fabrica == 1) {

	$programa_troca = $_GET['exibe'];
	
	if (preg_match("os_cadastro_troca.php", $programa_troca)) {
		$troca_produto = 't';
	}

	if (preg_match("os_revenda_troca.php", $programa_troca)) {
		$revenda_troca = 't';
	}

	if (preg_match("os_cadastro.php", $programa_troca)) {
		$troca_obrigatoria_consumidor = 't';
	}

	if (preg_match("os_revenda.php", $programa_troca)) {
		$troca_obrigatoria_revenda = 't';
	}

}

if ($login_fabrica == 66) {

	$programa_troca = $_GET['exibe'];

	if (preg_match("pedido_cadastro.php", $programa_troca)) {
		$subproduto_consulta = 't';
	}

}?>

<!doctype html public "-//W3C//DTD HTML 4.0 Transitional//EN">
<html>
<head><?php
if ($sistema_lingua == 'ES') {?>
	<title> Busca producto... </title><?php
} else {?>
	<title> Pesquisa Produto... </title><?php
}?>
<meta name="Author" content="">
<meta name="Keywords" content="">
<meta name="Description" content="">
<meta http-equiv=pragma content=no-cache>

</head>

<!--
<body onblur="javascript: setTimeout('window.close()',2500);" topmargin='0' leftmargin='0' marginwidth='0' marginheight='0'>
-->

<body topmargin='0' leftmargin='0' marginwidth='0' marginheight='0'><?php
if ($login_fabrica == 1) {?>
	<script language="JavaScript">
		function alertaTroca() {
			alert('ESTE PRODUTO NÃO É TROCA. SOLICITAR PEÇAS E REALIZAR O REPARO NORMALMENTE. EM CASO DE DÚVIDAS ENTRE EM CONTATO COM O SUPORTE DA SUA REGIÃO.');
		}
		function alertaTrocaSomente() {
			alert('Prezado Posto, este produto é somente para troca. Gentileza cadastrar na o.s de troca específica.');
		}
	</script><?php
}



$tipo = trim(strtolower($_GET['tipo']));
?>
<div style="float:left;color:#596d9b;width:100%;background:;height:27px;background: url('css/table/imagem/ui-bg_highlight-soft_75_cccccc_1x100.png');">
<?php
if ($tipo == "descricao") {

	$descricao = trim(strtoupper($_GET["campo"]));
	?>
	<?php
	if ($sistema_lingua == "ES") {
		echo "<h4>Buscando por <B>referencia del producto</b>:";
	} else {
		echo "<h4>Pesquisando por <b>descrição do produto</b>:";
	}

	echo "<i>$descricao</i></h4>";

	echo "<p>";
	$descricao = strtoupper($descricao);

	if ($login_pais <> 'BR') {
		$cond1 = "";
	}

	$cond_ativo = "tbl_produto.ativo";

	if ($login_posto == 7214) {
		$cond_ativo = "tbl_produto.uso_interno_ativo";
	}

	$sql = "SELECT   *
			FROM     tbl_produto
			JOIN     tbl_linha ON tbl_produto.linha = tbl_linha.linha
			LEFT JOIN tbl_produto_idioma using(produto)
			LEFT JOIN tbl_produto_pais   using(produto)
			$sql_familia
			WHERE   (
				   UPPER(tbl_produto.descricao)      LIKE '%$descricao%' 
				OR UPPER(tbl_produto.nome_comercial) LIKE '%$descricao%' 
				OR ( 
					UPPER(tbl_produto_idioma.descricao) LIKE '%$descricao%' 
					AND tbl_produto_idioma.idioma = '$sistema_lingua'
				)
				
			)
			AND      tbl_linha.fabrica = $login_fabrica
			AND      $cond_ativo
			AND      tbl_produto.produto_principal ";

#	if ($login_fabrica == 14) $sql .= " AND tbl_produto.abre_os IS NOT FALSE ";
	if ($login_fabrica == 14 or $login_fabrica == 66) $sql .= " AND tbl_produto.abre_os IS TRUE ";
	if ($login_fabrica == 20) $sql .= " AND tbl_produto_pais.pais = '$login_pais' ";
	
// fabrica intelbras postos BR não exibir produtos importados.
	if ($login_fabrica == 14 and $login_pais=='BR') $sql .= " and upper(tbl_produto.origem) <> 'IMP' and upper(tbl_produto.origem) <> 'USA' and upper(tbl_produto.origem) <> 'ASI' ";

	# HD 96688 - Tulio desabilitou. Nao era bem isto...
#	if($login_fabrica == 14 AND $login_posto <> 7214) {
#		$sql .= " AND tbl_produto.linha <> 560 ";
#	}

	$sql .= " ORDER BY tbl_produto.descricao;";

	$res = pg_exec($con,$sql);

#echo $sql;

	if (@pg_numrows($res) == 0) {
		if ($sistema_lingua=='ES') echo "<h1>Producto '$descricao' no encontrado</h1>";
		else echo "<h1>Produto '$descricao' não encontrado</h1>";
		echo "<script language='javascript'>";
		echo "setTimeout('window.close()',2500);";
		echo "</script>";
		exit;
	}

	if (@pg_numrows($res) == 1 and $login_fabrica == 24) {
			$produto    = trim(pg_result($res,0,'produto'));
			$descricao  = trim(pg_result($res,0,'descricao'));
			$voltagem   = trim(pg_result($res,0,'voltagem'));
			$referencia = trim(pg_result($res,0,'referencia'));
			$descricao = str_replace('"','',$descricao);
			$descricao = str_replace("'","",$descricao);
			echo "<script language='JavaScript'>";
			echo "referencia.value = '$referencia' ;";
			echo "descricao.value = '$descricao' ;";
			echo "voltagem.value = '$voltagem';";
			echo "descricao.focus();";
			echo "this.close();";
			echo "</script>";
	}

}

if ($tipo == "referencia") {

	$referencia = trim(strtoupper($_GET["campo"]));
	$referencia = str_replace(".","",$referencia);
	$referencia = str_replace(",","",$referencia);
	$referencia = str_replace("-","",$referencia);
	$referencia = str_replace("/","",$referencia);

	echo "<font face='Arial, Verdana, Times, Sans' size='2'>";
	echo ($sistema_lingua == "ES") ? "Buscando por <B>referencia del producto</b>:" : "Pesquisando por <b>descrição do produto</b>:";
	echo "<i>$referencia</i></font>";
	echo "<p>";

	$sql = "SELECT   *
			FROM     tbl_produto
			JOIN     tbl_linha ON tbl_produto.linha = tbl_linha.linha
			LEFT JOIN tbl_produto_pais   using(produto)
			$sql_familia
			WHERE    tbl_produto.referencia_pesquisa LIKE '%$referencia%'
			AND      tbl_linha.fabrica = $login_fabrica
			AND      tbl_produto.ativo
			AND      tbl_produto.produto_principal ";

	if ($login_fabrica == 20) {

		$sql = "SELECT   *
			FROM     tbl_produto
			JOIN     tbl_linha ON tbl_produto.linha = tbl_linha.linha
			LEFT JOIN tbl_produto_pais   using(produto)
			WHERE    (tbl_produto.referencia_pesquisa LIKE '%$referencia%' OR tbl_produto.referencia_fabrica LIKE '%$referencia%')
			AND      tbl_linha.fabrica = $login_fabrica
			AND      tbl_produto.ativo
			AND      tbl_produto.produto_principal ";

	}

	if ($login_fabrica == 14 or $login_fabrica == 66) $sql .= " AND tbl_produto.abre_os IS TRUE ";
	if ($login_fabrica == 20) $sql .= " AND tbl_produto_pais.pais = '$login_pais' ";

	// fabrica intelbras postos BR não exibir produtos importados.
	if ($login_fabrica == 14 and $login_pais=='BR') $sql .= " and upper(tbl_produto.origem) <> 'IMP' and upper(tbl_produto.origem) <> 'USA' and upper(tbl_produto.origem) <> 'ASI' ";
	
	# HD 96688
	if ($login_fabrica == 14 AND $login_posto <> 7214) {
		$sql .= " AND tbl_produto.linha <> 560 ";
	}

	$sql .= " ORDER BY";
	if ($login_fabrica == 45) $sql .= " tbl_produto.referencia, ";
	$sql .= " tbl_produto.descricao;";

	$res = pg_exec($con,$sql);

	if (pg_numrows($res) == 0) {
		echo ($sistema_lingua=='ES') ? "<h1>Producto '$referencia' no encontrado</h1>" : "<h1>Produto '$referencia' não encontrado</h1>";
		echo "<script language='javascript'>";
		echo "setTimeout('window.close()',2500);";
		echo "</script>";
		exit;
	}

	if (@pg_numrows($res) == 1 and $login_fabrica == 24) {
		$produto    = trim(pg_result($res,0,'produto'));
		$descricao  = trim(pg_result($res,0,'descricao'));
		$voltagem   = trim(pg_result($res,0,'voltagem'));
		$referencia = trim(pg_result($res,0,'referencia'));
		$descricao  = str_replace('"','',$descricao);
		$descricao  = str_replace("'","",$descricao);
		echo "<script language='JavaScript'>";
		echo "referencia.value = '$referencia' ;";
		echo "descricao.value = '$descricao' ;";
		echo "voltagem.value = '$voltagem';";
		echo "descricao.focus();";
		echo "this.close();";
		echo "</script>";
	}

}
?>
</div>
<?php
	echo "<script language='JavaScript'>";
	echo "<!--";
	echo "this.focus();";
	echo "// -->";
	echo "</script>";
	?>
        <table width='99%' cellpadding="0" cellspacing="0" border="0" class="display" id="modal_2">
        <thead>
        	<tr style="text-align: left;background-color:#596d9b;font: bold 14px Arial;color:#FFFFFF;">
				<th width="20%">Código do Produto</th>
				<th width="40%">Modelo do Produto</th>
				<th width="10%">Nome Comercial</th>
				<th width="10%">Voltagem</th>
				<th width="10%">Status</th>
				<th width="8%">Imagem</th>
			</tr>
        </thead>

		<tbody>	
	<?php
	for ($i = 0; $i < pg_numrows($res); $i++) {

		$produto            = trim(pg_result($res, $i, 'produto'));
		$linha              = trim(pg_result($res, $i, 'linha'));
		$descricao          = trim(pg_result($res, $i, 'descricao'));
		$nome_comercial     = trim(pg_result($res, $i, 'nome_comercial'));
		$voltagem           = trim(pg_result($res, $i, 'voltagem'));
		$referencia         = trim(pg_result($res, $i, 'referencia'));
		$referencia_fabrica = trim(pg_result($res, $i, 'referencia_fabrica'));
		$garantia           = trim(pg_result($res, $i, 'garantia'));
		$mobra              = str_replace(".",",",trim(pg_result($res, $i, 'mao_de_obra')));
		$ativo              = trim(pg_result($res, $i, 'ativo'));
		$off_line           = trim(pg_result($res, $i, 'off_line'));
		$capacidade         = trim(pg_result($res, $i, 'capacidade'));

		$valor_troca        = trim(pg_result($res, $i, 'valor_troca'));
		$troca_garantia     = trim(pg_result($res, $i, 'troca_garantia'));
		$troca_faturada     = trim(pg_result($res, $i, 'troca_faturada'));

		$descricao = str_replace('"','',$descricao);
		$descricao = str_replace("'","",$descricao);
		//hd 14624
		$troca_obrigatoria= trim(pg_result($res, $i, 'troca_obrigatoria'));

		$sql_idioma = "SELECT * FROM tbl_produto_idioma WHERE produto = $produto AND upper(idioma) = '$sistema_lingua'";
	
		$res_idioma = @pg_exec($con,$sql_idioma);
		if (@pg_numrows($res_idioma) >0) {
			$descricao  = trim(@pg_result($res_idioma, 0, 'descricao'));
		}

		$mativo = ($ativo == 't') ?  "ATIVO" : "INATIVO";

		$produto_pode_trocar = 1;

		if ($troca_produto == 't' or $revenda_troca == 't') {

			if ($troca_faturada != 't' AND $troca_garantia != 't') {
				$produto_pode_trocar = 0;
			}

		}

		$produto_so_troca = 1;

		if ($troca_obrigatoria_consumidor == 't' or $troca_obrigatoria_revenda == 't') {

			if ($troca_obrigatoria == 't') {
				$produto_so_troca = 0;
			}

		}

		$cor = ($i % 2 <> 0) ? '#EEEEEE' : '#ffffff';

		echo "<tr bgcolor='$cor'>";
		//$referencia  $descrica $voltagem
		echo "<td>";
			
		//HD 14624 Paulo alterou para verificar se o produto é só de troca
		if ($produto_pode_trocar == 0) {
		?>
			<a href='#' onclick="alertaTroca('');"><font face='Arial, Verdana, Times, Sans' size='-1' color='#6A6A6A'><?php echo $referencia;?></font></a>
		<?php
		} else if($produto_so_troca == 0) {
			?>
			<a href='#' onclick="alertaTrocaSomente('');"><font face='Arial, Verdana, Times, Sans' size='-1' color='#6A6A6A'><?php echo $referencia;?></font></a>
		<?php
		} else {
			// hd 115479
			if ($login_fabrica == 11) {
				$num = pg_numrows($res);
				if ($num>1) {
					$msg_confirma = "1";
				}
			}
			?>
			<a href='#' onclick="retorna_dados_produto('<?php echo $referencia;?>','<?php echo $descricao;?>','<?php echo $voltagem;?>','<?php echo $msg_confirma;?>');"><font face='Arial, Verdana, Times, Sans' size='-1' color='#6A6A6A'><?php echo $referencia;?></font></a>
		<?php
		}

		echo "</td>";

		if ($login_fabrica == 20) {
			echo "<td>";
			if (strlen($referencia_fabrica) > 0) {
				echo "<font size='1' color='#AAAAAA'>Bare Tool</font><br />";
			}
			echo "<font face='Arial, Verdana, Times, Sans' size='-1' color='#000000'> $referencia_fabrica </font>";
			echo "</td>";
		}
		
		if ($login_fabrica == 14 or ($login_fabrica == 66 and $subproduto_consulta)) {
		
				#------------ Pesquisa de Produto Pai para INTELBRÁS -----------
				if ($login_fabrica == 66) {

					$sql = "SELECT DISTINCT tbl_produto.produto, tbl_produto.descricao, tbl_produto.referencia
							   FROM     tbl_produto
							   JOIN     tbl_subproduto ON tbl_subproduto.produto_pai = tbl_produto.produto
							   WHERE    tbl_subproduto.produto_pai = $produto
							   AND      tbl_produto.ativo
							   ORDER BY tbl_produto.descricao";

				} else {

					$sql = "SELECT tbl_produto.produto, tbl_produto.descricao, tbl_produto.referencia
							   FROM     tbl_produto
							   JOIN     tbl_subproduto ON tbl_subproduto.produto_pai = tbl_produto.produto
							   WHERE    tbl_subproduto.produto_filho = $produto
							   AND      tbl_produto.ativo ";
					
					// fabrica intelbras postos BR não exibir produtos importados.
					if ($login_fabrica == 14 and $login_pais=='BR') $sql .= " and upper(tbl_produto.origem) <> 'IMP' and upper(tbl_produto.origem) <> 'USA' and upper(tbl_produto.origem) <> 'ASI' ";

					$sql .= "ORDER BY tbl_produto.descricao";

				}

				$resX = pg_exec($con,$sql);

				if (pg_numrows($resX) == 0) {
				?>
					<td>
						<a href="#" onclick="retorna_dados_produto('<?php echo $referencia;?>','<?php echo $descricao;?>','<?php echo $voltagem;?>');">
						<font face='Arial, Verdana, Times, Sans' size='-1' color='#0000FF'><?php echo $descricao;?></font>
						</a>
				<?php
				} else {

					echo "<td>";

				}

				for ($x = 0; $x < pg_numrows($resX); $x++) {

					$produto_pai    = trim(pg_result($resX, $x, 'produto'));
					$descricao_pai  = trim(pg_result($resX, $x, 'descricao'));
					$referencia_pai = trim(pg_result($resX, $x, 'referencia'));

					$descricao_pai = str_replace('"','',$descricao_pai);
					$sql = "SELECT tbl_produto.produto, tbl_produto.descricao, tbl_produto.referencia
								   FROM     tbl_produto
								   JOIN     tbl_subproduto ON tbl_subproduto.produto_filho = tbl_produto.produto
								   WHERE    tbl_subproduto.produto_pai = $produto_pai
								   AND      tbl_produto.ativo
								   ORDER BY tbl_produto.descricao";
					$resZ = pg_exec($con,$sql);
					if (pg_numrows($resZ) == 0) {
						?>
						<font face='Arial, Verdana, Times, Sans' size='-1' color='#0000FF'></font>
						<a href="#" onclick="retorna_dados_produto('<?php echo $referencia;?>','<?php echo $descricao;?>','<?php echo $voltagem;?>');">
						<font face='Arial, Verdana, Times, Sans' size='-1' color='#0000FF'><?php echo $descricao;?></font>
						</a>";
						<?php
					}else{
						?>
						<a href="#" onclick="retorna_dados_produto('<?php echo $referencia;?>','<?php echo $descricao;?>','<?php echo $voltagem;?>','<?php echo $msg_confirma;?>');">
						<font face='Arial, Verdana, Times, Sans' size='-1' color='#0000FF'><?php echo $descricao;?></font>
						<?php
						break;
					}
					//179336 retirado por reclamacao da rammonna
					if (1==2) {
						for ( $z = 0 ; $z < pg_numrows($resZ) ; $z++ ) {
							$produto_avo    = trim(pg_result($resZ,$z,produto));
							$descricao_avo  = trim(pg_result($resZ,$z,descricao));
							$referencia_avo = trim(pg_result($resZ,$z,referencia));
							
							$descricao_avo = str_replace('"','',$descricao_avo);
							?>
							<font face='Arial, Verdana, Times, Sans' size='-1' color='#0000FF'>&nbsp;</font>
							<a href="#" onclick="retorna_dados_produto('<?php echo $referencia;?>','<?php echo $descricao;?>','<?php echo $voltagem;?>');">
							<font face='Arial, Verdana, Times, Sans' size='-1' color='#0000FF'><?php echo $descricao_avo;?></font>
							</a>
							<?php
						}
					}
				}
				echo "</td>";
		}else{
			echo "<td>";

			# Fabio - 19-12-2007 = Coloquei esta if para nw mostrar link quando o produto nw for de troca / somente na tela de cadastro de OS troca
			if($produto_pode_trocar ==0){
				?>
				<a href="#" onclick="alertaTroca('');">
				<font face='Arial, Verdana, Times, Sans' size='-1' color='#0000FF'><?php echo $descricao;?></font></a>
				<?php
			}elseif($produto_so_troca ==0){
				?>
				<a href="#" onclick="alertaTrocaSomente('');">
				<font face='Arial, Verdana, Times, Sans' size='-1' color='#0000FF'><?php echo $descricao;?></font></a>
				<?php
			}else{
				// hd 115479
				if ($login_fabrica == 11) {
					$num = pg_numrows($res);
					if ($num>1) {
						$msg_confirma = "1";
					}
				}
				?>
					<a href="#" onclick="retorna_dados_produto('<?php echo $referencia;?>','<?php echo $descricao;?>','<?php echo $voltagem;?>','<?php echo $msg_confirma;?>');">
						<font face='Arial, Verdana, Times, Sans' size='-1' color='#0000FF'><?php echo $descricao;?></font>
					</a>
				<?php
			}
				echo "</td>";
			}
		
		echo "<td>";

		echo "<font face='Arial, Verdana, Times, Sans' size='-1' color='#0000FF'>$nome_comercial</font>";
		echo "</td>";
		
		echo "<td>";
		echo "<font face='Arial, Verdana, Times, Sans' size='-1' color='#000000'>$voltagem</font>";
		echo "</td>";
		
		echo "<td>";
		echo "<font face='Arial, Verdana, Times, Sans' size='-1' color='#000000'>$mativo</font>";
		echo "</td>";
		$imagem = "imagens_produtos/$login_fabrica/pequena/$produto.jpg";

		echo "<td title='$imagem' align='center'>&nbsp;";
		if ($login_fabrica==3) {
				
		    if (file_exists("/var/www/assist/www/$imagem")) {
		        $tag_imagem = "<A href='".str_replace("pequena", "media", $imagem)."' class='thickbox'>";
				$tag_imagem.= "<IMG src='$imagem' valign='middle' style='border: 2px solid #FFCC00' class='thickbox' height='40'></A>";
				echo $tag_imagem;
			}
				
		}
		echo "</td>";

		echo "</tr>";

	}
	?>
	</tbody>
	</table>

</body>
</html>
<?php
if($contador_ver == '1'){
?>
</div>
<?php
}
?>
</div>
