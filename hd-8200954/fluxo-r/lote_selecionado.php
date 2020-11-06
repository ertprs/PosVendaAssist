<?
include 'cabecalho-ajax.php';

$produto_rg = $_GET['produto_rg'];
$produto_rg = str_replace ("'","",$produto_rg);

$sql = "SELECT tbl_produto_rg_item.*, tbl_produto.referencia, tbl_produto.descricao FROM tbl_produto_rg_item LEFT JOIN tbl_produto USING (produto) WHERE produto_rg = $produto_rg";
$res = pg_exec ($con,$sql);

if (pg_numrows ($res) > 0) {
	$produto_rglote  = pg_result ($res,0,'produto_rg');
	echo "<table border='0' cellpadding='3' cellspacing='3' class='TabelaRevenda' align='center'>";
	echo "<thead>";
	echo "<tr>";
	echo "<td width='80' ><b>Lote</b></td>";
	echo "<td width='80' ><b>Barras</b></td>";
	echo "<td width='80' ><b>RG</b></td>";
	echo "<td width='350'><b>Produto</b></td>";
	echo "</tr>";
	echo "</thead>";

	echo "<input type='hidden' name='produto_rg' id='produto_rg' value='$produto_rglote'>";
	echo "<input type='hidden' name='ja_estou_editando' value='0'>";

	for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
		$produto_rglote  = pg_result ($res,$i,'produto_rg');
		$produto_rg_item = pg_result ($res,$i,'produto_rg_item');
		$produto         = pg_result ($res,$i,'produto');
		$rg              = pg_result ($res,$i,'rg');
		$codigo_barra    = pg_result ($res,$i,'codigo_barra');
		$referencia      = pg_result ($res,$i,'referencia');
		$descricao       = pg_result ($res,$i,'descricao');

		if($cor<>'#FFFFFF') {
			$cor = '#FFFFFF';
		}else{
			$cor = '#e6eef7';
		}

		echo "<tr bgcolor='$cor' onmouseover=\"this.bgColor='#cccccc' ; this.style.cursor = 'hand' ; \" onmouseout=\"this.bgColor='$cor'\">";
		echo "<input type='hidden' name='produto_rg_item_$i' value='$produto_rg_item'>";
		echo "<td>$produto_rg</td>";
		echo "<td>$codigo_barra</td>";
		echo "<td>$rg</td>";
		echo "<td>";

		echo "<div id='linha_descricao_$i' style='display:block;width:350' onmouseover=\"this.style.cursor = 'pointer' \" onclick=\"if (ja_estou_editando.value == '0') {linha_campos_$i.style.display='block' ; linha_descricao_$i.style.display='none' ;  ja_estou_editando.value = '1' ; autocompletar('produto_$i','id_produto_$i') ; produto_$i.focus();; } \">";

		if (strlen($referencia)>0) {
			echo "$referencia - $descricao";
		}else{
			echo "&nbsp;";
		}

		echo "</div>";
		
		if (strlen($referencia)>0) {
			$produto_completo = "$referencia - $descricao";
		}else{
			$produto_completo = " ";
		}

		echo "<div id='linha_campos_$i' style='display:none;' >";
		echo "<input type='hidden' name='id_produto_$i' id='id_produto_$i' value='$produto'>";
		echo "<input type='text' size='35' name='produto_$i' id='produto_$i' rel='produto' value='$produto_completo' 
				onkeypress=\"
					if (event.keyCode == 13) { 
						linha_campos_$i.style.display='none' ; 
						linha_descricao_$i.style.display='block' ; 
						ja_estou_editando.value = '0' ; 
						linha_descricao_$i.innerHTML = produto_$i.value;
						gravar(id_produto_$i.value,produto_rg_item_$i.value,'mostra_gravar_$i',produto_rg.value,'explodir_os');
					} 
				\" >";
		echo "</div>";

		echo "<div id='mostra_gravar_$i' name='mostra_gravar_$i' style='display:inline'></div>";
		echo "</td>";
		echo "</tr>";
	}
	echo "</table>";
	if(strlen($produto_rg)>0){
		$sql = "SELECT produto_rg_item
				FROM   tbl_produto_rg      RG
				JOIN   tbl_produto_rg_item RI USING(produto_rg)
				WHERE  RG.produto_rg = $produto_rg
				AND    produto IS NULL";
		$res = @pg_exec($con,$sql);
		$msg_erro .= pg_errormessage($con);
		if(@pg_numrows($res)>0) {
			$bloqueio = "style='display:none'";
		}
	}
	echo "<div id='explodir_os' $bloqueio ><input type='button' name='btn_acao' id='btn_acao' value='Explodir em OS' onclick=\"window.location='rg_conferencia.php?explodir=$produto_rg'\"></div>";
}
?>