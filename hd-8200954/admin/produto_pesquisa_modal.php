
<div style="float:left;width:97%;height:40px;background: url('css/table/imagem/ui-bg_highlight-soft_75_cccccc_1x100.png');">
</div>
<div style="float:right;width:3%;background: url('css/table/imagem/ui-bg_highlight-soft_75_cccccc_1x100.png');height:40px;">
	<a href="#" onclick="closeMessage()" width="50px" height="30px" alt="Fechar" title="Fechar"><img src="css/modal/excluir.png"/></a>
</div>
<div style="background:transparent;position: relative; height: 460px;width:100%;overflow:auto">
<?php
$contador_ver ="0";
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include 'autentica_admin.php';


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


<body topmargin='0' leftmargin='0' marginwidth='0' marginheight='0'>
<?php




$tipo = trim(strtolower($_GET['tipo']));
?>
<div style="float:left;color:#596d9b;width:100%;background:;height:27px;background: url('css/table/imagem/ui-bg_highlight-soft_75_cccccc_1x100.png');">
<?php
if ($tipo == "descricao") {

	$descricao = trim(strtoupper($_GET["campo"]));
	?>
	<?php
		echo "<h4>Pesquisando por <b>descrição do produto</b>:";


	echo "<i>$descricao</i></h4>";

	echo "<p>";
	$descricao = strtoupper($descricao);

	if ($login_pais <> 'BR') {
		$cond1 = "";
	}

	$cond_ativo = "tbl_produto.ativo";

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

	if ($login_fabrica == 14 or $login_fabrica == 66) $sql .= " AND tbl_produto.abre_os IS TRUE ";
	if ($login_fabrica == 20) $sql .= " AND tbl_produto_pais.pais = '$login_pais' ";
	
	if ($login_fabrica == 14 and $login_pais=='BR') $sql .= " and upper(tbl_produto.origem) <> 'IMP' and upper(tbl_produto.origem) <> 'USA' and upper(tbl_produto.origem) <> 'ASI' ";

	$sql .= " ORDER BY tbl_produto.descricao;";

	$res = pg_exec($con,$sql);


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

	if ($login_fabrica == 14 and $login_pais=='BR') $sql .= " and upper(tbl_produto.origem) <> 'IMP' and upper(tbl_produto.origem) <> 'USA' and upper(tbl_produto.origem) <> 'ASI' ";
	
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
        <table width='99%' cellpadding="0" cellspacing="0" border="0" class="display" id="modal_produto">
        <thead>
        	<tr style="text-align: left;background-color:#596d9b;font: bold 14px Arial;color:#FFFFFF;">
				<th width="20%">Código do Produto</th>
				<th width="40%">Modelo do Produto</th>
				<th width="10%">Nome Comercial</th>
				<th width="10%">Voltagem</th>
				<th width="10%">Status</th>
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
		$ativo              = trim(pg_result($res, $i, 'ativo'));
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
			<a href='#' onclick="retorna_dados_produto('<?php echo $referencia;?>','<?php echo $descricao;?>');"><font face='Arial, Verdana, Times, Sans' size='-1' color='#6A6A6A'><?php echo $referencia;?></font></a>
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
		

			echo "<td>";

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

				?>
					<a href="#" onclick="retorna_dados_produto('<?php echo $referencia;?>','<?php echo $descricao;?>');">
						<font face='Arial, Verdana, Times, Sans' size='-1' color='#0000FF'><?php echo $descricao;?></font>
					</a>
				<?php
			}
				echo "</td>";
		
		echo "<td>";

		echo "<font face='Arial, Verdana, Times, Sans' size='-1' color='#0000FF'>$nome_comercial</font>";
		echo "</td>";
		
		echo "<td>";
		echo "<font face='Arial, Verdana, Times, Sans' size='-1' color='#000000'>$voltagem</font>";
		echo "</td>";
		
		echo "<td>";
		echo "<font face='Arial, Verdana, Times, Sans' size='-1' color='#000000'>$mativo</font>";
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