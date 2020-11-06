<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';


$data_inicial = $_POST['data_inicial'];
$data_final = $_POST['data_final'];
$fabrica = $_POST['fabrica'];
$peca = $_POST['peca'];
$peca_produto = $_POST['peca_produto'];
$tipo_pedido = $_POST['tipo_pedido'];
$tipo_os     = $_POST['tipo_os'];
$btn_acao = $_POST['btn_acao'];

if((empty($data_inicial) or empty($data_final)) and !empty($btn_acao)) {
	$msg_erro = "Favor informar os dias";
}

if(!empty($_POST['ajax'])) {
	$relatorio = $_POST['relatorio'];

	if($tipo_pedido == 'garantia') $cond2 = " AND tbl_tipo_pedido.pedido_em_garantia ";
	if($tipo_pedido == 'faturado') $cond2 = " AND tbl_tipo_pedido.pedido_faturado ";
	if($tipo_pedido == 'garantia') $campoData = " AND tbl_os.data_abertura between '$data_inicial'  and '$data_final'";
	if($tipo_pedido == 'faturado') $campoData = " AND tbl_pedido.data between '$data_inicial'  and '$data_final'";
	if(empty($tipo_pedido)) $campoData = "";

	if(empty($relatorio)) {
		$sql = "SELECT	tbl_pedido.pedido,
				(tbl_pedido_item.qtde - tbl_pedido_item.qtde_cancelada - tbl_pedido_item.qtde_faturada-tbl_pedido_item.qtde_faturada_distribuidor) AS qtde,
				to_char(tbl_pedido.data,'DD/MM/YYYY') as data,
				tbl_tipo_pedido.descricao,
				tbl_os.sua_os,
				tbl_produto.referencia AS ref_produto,
				tbl_produto.descricao AS desc_produto,
				tbl_posto.nome,
				tbl_posto.cnpj,
				tbl_posto_estoque.qtde as qtde_estoque,
				tbl_posto_fabrica.contato_fone_comercial,
				TO_CHAR(tbl_pedido.data,'DD/MM/YYYY') AS data_pedido,
				TO_CHAR(tbl_os.data_digitacao,'DD/MM/YYYY') AS data_os
			FROM	tbl_pedido_item
			JOIN tbl_pedido USING (pedido)
			JOIN tbl_tipo_pedido USING(tipo_pedido)
			JOIN tbl_posto ON tbl_pedido.posto = tbl_posto.posto
			JOIN tbl_posto_fabrica ON tbl_pedido.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = tbl_pedido.fabrica
			LEFT JOIN tbl_posto_estoque ON (tbl_posto_estoque.posto = tbl_posto.posto)
			LEFT JOIN tbl_os_item using(pedido_item, pedido)
			LEFT JOIN tbl_os_produto USING(os_produto)
			LEFT JOIN tbl_os ON tbl_os.os = tbl_os_produto.os and tbl_os.excluida is not true
			LEFT JOIN tbl_produto ON tbl_os.produto = tbl_produto.produto
			WHERE tbl_pedido_item.qtde > tbl_pedido_item.qtde_faturada + tbl_pedido_item.qtde_cancelada+tbl_pedido_item.qtde_faturada_distribuidor
			$campoData
			AND tbl_pedido_item.peca = $peca
			AND	tbl_pedido.fabrica = $fabrica
			and tbl_pedido.distribuidor notnull
			and tbl_pedido.finalizado IS NOT NULL
			AND ( tbl_pedido.posto not in (4311,6359) or (tbl_pedido.posto not in (20682) and tbl_pedido.pedido_cliente != 'REPOSIÇÃO ESTOQUE'))
			$cond2
			order by tbl_pedido.pedido ; 		";

	}elseif ($_POST['tipo_pedido'] == 'faturado') {
			$sql = "SELECT  tbl_peca.peca,
						tbl_peca.referencia,
						tbl_peca.descricao,
						tbl_produto.referencia AS ref_produto,
						tbl_produto.descricao AS desc_produto,
						tbl_os.sua_os,
						to_char(MIN (tbl_pedido.data),'DD/MM/YYYY') AS data,
						SUM(tbl_pedido_item.qtde - tbl_pedido_item.qtde_cancelada - tbl_pedido_item.qtde_faturada - tbl_pedido_item.qtde_faturada_distribuidor) AS qtde,
						tbl_posto_estoque.qtde as qtde_estoque,
						tbl_pedido.pedido,
						tbl_tipo_pedido.descricao as tipo_pedido,
						tbl_posto.nome,
						tbl_posto.cnpj,
						tbl_posto_fabrica.contato_fone_comercial,
						TO_CHAR(tbl_pedido.data,'DD/MM/YYYY') AS data_pedido,
						TO_CHAR(tbl_os.data_digitacao,'DD/MM/YYYY') AS data_os
					FROM tbl_pedido_item
					JOIN tbl_pedido ON tbl_pedido_item.pedido = tbl_pedido.pedido AND tbl_pedido.fabrica = $fabrica
					JOIN tbl_tipo_pedido ON tbl_pedido.tipo_pedido = tbl_tipo_pedido.tipo_pedido
					JOIN tbl_peca ON tbl_pedido_item.peca = tbl_peca.peca
					JOIN tbl_posto ON tbl_pedido.posto = tbl_posto.posto
					JOIN tbl_posto_fabrica ON tbl_pedido.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = tbl_pedido.fabrica
					LEFT JOIN tbl_os_item ON tbl_pedido_item.pedido_item = tbl_os_item.pedido_item
					LEFT JOIN tbl_os_produto USING(os_produto)
					LEFT JOIN tbl_os ON tbl_os_produto.os = tbl_os.os AND tbl_os.fabrica = $fabrica and tbl_os.excluida is not true
					LEFT JOIN tbl_produto ON tbl_os.produto = tbl_produto.produto
					LEFT JOIN tbl_posto_estoque ON tbl_posto_estoque.peca = tbl_peca.peca
					WHERE tbl_pedido.fabrica = $fabrica
					$campoData
					AND tbl_pedido_item.qtde > tbl_pedido_item.qtde_faturada + tbl_pedido_item.qtde_cancelada+tbl_pedido_item.qtde_faturada_distribuidor
					and tbl_pedido.distribuidor notnull
					and tbl_pedido.finalizado IS NOT NULL
					AND ( tbl_pedido.posto not in (4311,6359) or (tbl_pedido.posto not in (20682) and tbl_pedido.pedido_cliente != 'REPOSIÇÃO ESTOQUE'))
					$cond2
					GROUP BY tbl_peca.peca,
						 tbl_peca.referencia,
						 tbl_peca.descricao,
						 tbl_produto.referencia,
						 tbl_produto.descricao,
						 tbl_os.sua_os,
						 tbl_posto_estoque.qtde,
						 tbl_pedido.pedido,
						 tbl_tipo_pedido.descricao,
						 tbl_posto.nome,
						 tbl_posto.cnpj,
						 tbl_posto_fabrica.contato_fone_comercial,
						 tbl_pedido.data,
			 			tbl_os.data_digitacao";

	} else {
		$cond_distrib = ($_POST['tipo_pedido'] == 'distrib') ? " AND tbl_pedido.distribuidor = 4311" : "";

/*		$sql = "SELECT	tbl_pedido.pedido,
				(tbl_pedido_item.qtde - tbl_pedido_item.qtde_cancelada - tbl_pedido_item.qtde_faturada-tbl_pedido_item.qtde_faturada_distribuidor) AS qtde,
				to_char(tbl_pedido.data,'DD/MM/YYYY') as data,
				tbl_tipo_pedido.descricao as tipo_pedido,
				tbl_os.sua_os,
				tbl_posto_estoque.qtde as qtde_estoque,
				tbl_pedido_item.peca,
				tbl_peca.referencia,
				tbl_peca.descricao,
				tbl_produto.referencia AS ref_produto,
				tbl_produto.descricao AS desc_produto,
				tbl_posto.nome,
				tbl_posto.cnpj,
				tbl_posto_fabrica.contato_fone_comercial,
				TO_CHAR(tbl_pedido.data,'DD/MM/YYYY') AS data_pedido,
				TO_CHAR(tbl_os.data_digitacao,'DD/MM/YYYY') AS data_os
			into temp pedido_nao_atendido_$login_posto
			FROM	tbl_pedido_item
			JOIN tbl_peca USING(peca)
			JOIN tbl_pedido USING (pedido)
			JOIN tbl_tipo_pedido USING(tipo_pedido)
			JOIN tbl_posto ON tbl_pedido.posto = tbl_posto.posto
			JOIN tbl_posto_fabrica ON tbl_pedido.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = tbl_pedido.fabrica
			LEFT JOIN tbl_posto_estoque on tbl_posto_estoque.peca = tbl_pedido_item.peca
			LEFT JOIN tbl_os_item using(pedido_item, pedido)
			LEFT JOIN tbl_os_produto USING(os_produto)
			LEFT JOIN tbl_os ON tbl_os.os = tbl_os_produto.os and tbl_os.excluida is not true
			LEFT JOIN tbl_produto ON tbl_os.produto = tbl_produto.produto
			WHERE tbl_pedido_item.qtde > tbl_pedido_item.qtde_faturada + tbl_pedido_item.qtde_cancelada + tbl_pedido_item.qtde_faturada_distribuidor
			$campoData
			AND	tbl_pedido.fabrica = $fabrica
			AND tbl_pedido.finalizado notnull
			and tbl_pedido.distribuidor notnull
			AND ( tbl_pedido.posto not in (4311,6359) or (tbl_pedido.posto not in (20682) and tbl_pedido.pedido_cliente != 'REPOSIÇÃO ESTOQUE'))
			$cond2
			$cond_distrib
			order by tbl_pedido.pedido ; 

			select sum(qtde) as qtde, peca, qtde_estoque into temp estoque_pedido_$login_posto from pedido_nao_atendido_$login_posto group by peca, qtde_estoque ; 

			delete from pedido_nao_atendido_$login_posto using estoque_pedido_$login_posto where estoque_pedido_$login_posto.peca = pedido_nao_atendido_$login_posto.peca and estoque_pedido_$login_posto.qtde - estoque_pedido_$login_posto.qtde_estoque <= 0 ;
			
			select * from pedido_nao_atendido_$login_posto order by referencia; 
			";*/

			$sql = "SELECT  tbl_peca.peca,
			tbl_peca.referencia,
			tbl_peca.descricao,
			tbl_produto.referencia AS ref_produto,
			tbl_produto.descricao AS desc_produto,
			tbl_os.sua_os,
			to_char(MIN (tbl_pedido.data),'DD/MM/YYYY') AS desde,
			SUM(tbl_pedido_item.qtde - tbl_pedido_item.qtde_cancelada - tbl_pedido_item.qtde_faturada - tbl_pedido_item.qtde_faturada_distribuidor) AS qtde,
			tbl_posto_estoque.qtde as qtde_estoque,
			tbl_pedido.pedido,
			tbl_tipo_pedido.descricao as tipo_pedido,
			tbl_posto.nome,
			tbl_posto.cnpj,
			tbl_posto_fabrica.contato_fone_comercial,
			TO_CHAR(tbl_pedido.data,'DD/MM/YYYY') AS data_pedido,
			TO_CHAR(tbl_os.data_digitacao,'DD/MM/YYYY') AS data_os
		FROM tbl_pedido_item
		JOIN tbl_pedido ON tbl_pedido_item.pedido = tbl_pedido.pedido AND tbl_pedido.fabrica = $fabrica
		JOIN tbl_tipo_pedido ON tbl_pedido.tipo_pedido = tbl_tipo_pedido.tipo_pedido
		JOIN tbl_peca ON tbl_pedido_item.peca = tbl_peca.peca
		JOIN tbl_posto ON tbl_pedido.posto = tbl_posto.posto
		JOIN tbl_posto_fabrica ON tbl_pedido.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = tbl_pedido.fabrica
		LEFT JOIN tbl_os_item ON tbl_pedido_item.pedido_item = tbl_os_item.pedido_item
		LEFT JOIN tbl_os_produto USING(os_produto)
		LEFT JOIN tbl_os ON tbl_os_produto.os = tbl_os.os AND tbl_os.fabrica = $fabrica and tbl_os.excluida is not true
		LEFT JOIN tbl_produto ON tbl_os.produto = tbl_produto.produto
		LEFT JOIN tbl_posto_estoque ON tbl_posto_estoque.peca = tbl_peca.peca
		WHERE tbl_pedido.fabrica = $fabrica
		$campoData
		AND tbl_pedido_item.qtde > tbl_pedido_item.qtde_faturada + tbl_pedido_item.qtde_cancelada+tbl_pedido_item.qtde_faturada_distribuidor
		AND tbl_pedido.distribuidor notnull
		and tbl_pedido.finalizado IS NOT NULL
		AND ( tbl_pedido.posto not in (4311,6359) or (tbl_pedido.posto not in (20682) and tbl_pedido.pedido_cliente != 'REPOSIÇÃO ESTOQUE'))
		$cond2
		$campo_tipo_os
		GROUP BY tbl_peca.peca,
			 tbl_peca.referencia,
			 tbl_peca.descricao,
			 tbl_produto.referencia,
			 tbl_produto.descricao,
			 tbl_os.sua_os,
			 tbl_posto_estoque.qtde,
			 tbl_pedido.pedido,
			 tbl_tipo_pedido.descricao,
			 tbl_posto.nome,
			 tbl_posto.cnpj,
			 tbl_posto_fabrica.contato_fone_comercial,
			 tbl_pedido.data,
			 tbl_os.data_digitacao";
	}

	$res = pg_query($con,$sql);
	if(pg_num_rows($res) >0 and empty($relatorio)) {
		$resultado .= "<tr><td colspan='100%'>
				<table align='center' width='600'  border='0' cellspacing='1' cellpadding='1' id='table_$peca'>
				<tr bgcolor='#663366' style='color:#ffffff ; font-weight:bold ; font-size:16px' align='center'>
						<td>CNPJ</td>
						<td>Posto</td>
						<td>Fone Contato</td>
						<td>Pedido</td>
						<td>Data</td>
						<td>Qtde</td>
						<td>Tipo</td>
						<td>OS</td>
						</tr>
						";
		for($x = 0; $x < pg_numrows($res);$x++) {

			$pedido    = pg_fetch_result($res,$x,'pedido');
			$qtde      = pg_fetch_result($res,$x,'qtde');
			$data      = pg_fetch_result($res,$x,'data');
			$descricao = pg_fetch_result($res,$x,'descricao');
			$sua_os    = pg_fetch_result($res,$x,'sua_os');
			$cnpj      = pg_fetch_result($res,$x,'cnpj');
			$posto     = pg_fetch_result($res,$x,'nome');
			$fone      = pg_fetch_result($res,$x,'contato_fone_comercial');
		
			$resultado .= "<tr><td>$cnpj</td>";
			$resultado .= "<td>$posto</td>";
			$resultado .= "<td>$fone</td>";
			$resultado .= "<td align='center'>$pedido</td>";
			$resultado .= "<td align='center'>$data</td>";
			$resultado .= "<td align='right'>$qtde</td>";
			$resultado .= "<td align='center'>$descricao</td>";
			$resultado .= "<td align='center'>$sua_os</td></tr>";
		}
		$resultado.="</table></td></tr>";
		echo $resultado;
	}elseif($relatorio == 'relatorio'){

		if ($fabrica == 122 && ($_POST['tipo_pedido'] == 'faturado' || $_POST['tipo_pedido'] == 'garantia')) {
			$coluna_de_para = 'De para Referência;';
			$colunas = "Qtde Estoque;Qtde Compra;";
		} else if ($_POST['tipo_pedido'] == 'faturado') {
			$colunas = "Qtde Estoque;Qtde Compra;";
		}

		$resultado = "CNPJ;Posto;Fone Contato;Referência Peça;{$coluna_de_para}Descrição Peça;Peças Alternativas;Referência Produto;Descrição Produto;Data AB do Pedido;Pedido;{$colunas}Data;Tipo;OS;Data AB da OS; Desde; Estoque; Qtd Peças Alternativas; Estoque Total; Qtde;\n";

		for($x = 0; $x < pg_numrows($res);$x++) {

			$peca      = pg_fetch_result($res,$x,'peca');
			$pedido    = pg_fetch_result($res,$x,'pedido');
			$qtde      = pg_fetch_result($res,$x,'qtde');
			$qtde_estoque = pg_fetch_result($res,$x,'qtde_estoque');
			//$qtde_estoque = (empty($qtde_estoque)) ? 0 : $qtde_estoque;
			//$qtde_compra  = ($qtde-$qtde_estoque>0) ?$qtde - $qtde_estoque : 0;
			$data      = pg_fetch_result($res,$x,'data');
			$descricao = pg_fetch_result($res,$x,'descricao');
			$referencia= pg_fetch_result($res,$x,'referencia');
			$tipo_pedido= pg_fetch_result($res,$x,'tipo_pedido');
			$sua_os    = pg_fetch_result($res,$x,'sua_os');
			$ref_produto = pg_fetch_result($res,$x,'ref_produto');
			$desc_produto = pg_fetch_result($res,$x,'desc_produto');
			$cnpj      = pg_fetch_result($res,$x,'cnpj');
			$posto     = pg_fetch_result($res,$x,'nome');
			$fone      = pg_fetch_result($res,$x,'contato_fone_comercial');
			$data_pedido = pg_fetch_result($res,$x,'data_pedido');
			$data_os	 = pg_fetch_result($res,$x,'data_os');

			$sql_alternativa = "SELECT peca_para FROM tbl_peca_alternativa WHERE peca_de = $peca AND fabrica = $fabrica";
			$res_alternativa = pg_query($con, $sql_alternativa);
			$array_pecas_alternativas = [];
			$pecas_alternativas = "";
			$qtde_peca_alternativa = "";

			if (pg_num_rows($res_alternativa) > 0) {
				for ($p=0; $p < pg_num_rows($res_alternativa); $p++) { 
					$peca_para_alternativa = pg_fetch_result($res_alternativa, $p, "peca_para");

					$sql_peca_estoque = "SELECT referencia, 
												qtde 
										 FROM tbl_peca 
										 JOIN tbl_posto_estoque USING(peca) 
										 WHERE tbl_peca.peca = $peca_para_alternativa 
										 AND tbl_peca.fabrica = $fabrica";
					$res_peca_estoque = pg_query($con, $sql_peca_estoque);
					if (pg_num_rows($res_peca_estoque) > 0) {
						$array_pecas_alternativas[] = pg_fetch_result($res_peca_estoque, 0, 'referencia');
						$qtde_peca_alternativa += pg_fetch_result($res_peca_estoque, 0, 'qtde');
					}
				}
				$pecas_alternativas = implode(",", $array_pecas_alternativas);	
			}

			if ($_POST['tipo_pedido'] == 'faturado') {
				$valores = "$qtde_estoque;$qtde_compra;";
			}

			$referencia_de_para = "";
			if ($fabrica == 122 && ($_POST['tipo_pedido'] == 'faturado' || $_POST['tipo_pedido'] == 'garantia')) {
					$sql_de_para = "SELECT tbl_peca.referencia as referencia_para
									FROM tbl_depara
									JOIN tbl_peca ON tbl_peca.fabrica = $fabrica 
									AND tbl_peca.peca = tbl_depara.peca_para
									WHERE tbl_depara.peca_de = $peca
									AND (tbl_depara.expira > current_date OR tbl_depara.expira IS NULL)";
					$res_de_para = pg_query($con, $sql_de_para);

					$referencia_de_para = pg_fetch_result($res_de_para, 0, 'referencia_para');
					$referencia_de_para = str_replace($quebra_layout, "", $referencia_de_para) . ";";
					$valores = "$qtde_estoque;$qtde_compra;";
			}
			$quebra_layout      = array('"', "'", "<br>", "<br/>", "<br />", "\n", "\r", ";") ;
			$referencia         = str_replace($quebra_layout, "", $referencia);
			$descricao          = str_replace($quebra_layout, "", $descricao);
			
			$desde   = pg_fetch_result($res, $x, desde);

			if (empty($qtde_estoque)) {
				$qtde_estoque = 0;
			}

			if (empty($qtde_peca_alternativa)) {
				$qtde_peca_alternativa = 0;
			}

			$total = $qtde_peca_alternativa + $qtde_estoque; 
	
			$compra =  0;
			$compra = $qtde - $total;

			if ($compra < 0) {
				$compra = abs($compra);
			} 

			$resultado .= "$cnpj;$posto;$fone;$referencia;{$referencia_de_para}$descricao;$pecas_alternativas;$ref_produto;$desc_produto;{$data_pedido};$pedido;{$valores}$data;$tipo_pedido;$sua_os;$data_os; $desde; $qtde_estoque; $qtde_peca_alternativa; $total; $qtde;\n";

		}

		$data = date('dmYHi');
		$fp = fopen ("xls/relatorio_detalhado_pedido_nao_atendido_$data.csv","w");
		fwrite($fp, $resultado);
		fclose($fp);
		echo "xls/relatorio_detalhado_pedido_nao_atendido_$data.csv";
	}
	exit;
}
?>

<html>
<head>
<title>Pedidos Não Atendidos</title>
<link type="text/css" rel="stylesheet" href="css/css.css">
</head>
<?
include_once '../js/js_css.php'; /* Todas libs js, jquery e css usadas no Assist - HD 969678 */
?>
<script>
$(document).ready(function()
    {
        $('#data_inicial').datepick({startDate:'01/01/2000'});
        $('#data_final').datepick({startDate:'01/01/2000'});

	
			
});

function mostraPedido(peca,data_inicial,data_final,fabrica,tipo_pedido){
	if($('#table_'+peca).length >0) {
		$('#table_'+peca).toggle();
	}else{

		$.ajax({
			url: '<?$PHP_SELF?>',
			cache: false,
			type: "POST",
			data:{
				peca : peca,
				data_inicial : data_inicial,
				data_final: data_final,
				fabrica: fabrica,
				tipo_pedido:tipo_pedido,
				ajax : 'ajax'
			},
			complete: function(retorno){
				$('#'+peca).after(retorno.responseText);
			}
		});
	}
}

function via_distrib(fabrica){
	if (fabrica == 11 || fabrica == 172) {
		$(".via_distrib").show();
	} else {
		$(".via_distrib").hide();
	}
}

function gerarExcel(data_inicial,data_final,fabrica,tipo_pedido){
	$.ajax({
			url: '<?$PHP_SELF?>',
			cache: false,
			type: "POST",
			data:{
				data_inicial : data_inicial,
				data_final: data_final,
				fabrica: fabrica,
				tipo_pedido:tipo_pedido,
				relatorio:'relatorio',
				ajax : 'ajax'
			},
			complete: function(retorno){
				window.open(retorno.responseText);
			}
		});
}
</script>
<body>

<? include 'menu.php' ;

?>
<center><h1>Pedidos Não Atendidos</h1></center>

<p>
<?
		if (strlen($msg_erro) > 0) {
			echo "<div style='border: 1px solid #DD0000; background-color: #FFDDDD; color: #DD0000; font-size: 11pt; margin-bottom: 10px; padding: 5px;'>$msg_erro</div><p>";
		}

?>
	<center>
	<form name='frm_estoque' action='<? echo $PHP_SELF ?>' method='POST'>
	<table>

		<tr>
			<td align='right'>Data Inicial</td>
			<td><input type='text' size='11' name='data_inicial' id='data_inicial' class="frm" value="<? echo $_REQUEST["data_inicial"]; ?>"></td>
			<td align='right'>Data Final</td>
			<td><input type='text' size='11' name='data_final'   id='data_final' class="frm"  value="<? echo $_REQUEST["data_final"]; ?>"></td>
					<td align='right'>Fábrica</td>
			<td align='left'>
			<?
			echo "<select style='width:120px;' name='fabrica' id='fabrica' class='frm' onchange='via_distrib(this.value)'>";
				$sql = "SELECT fabrica,nome FROM tbl_fabrica WHERE fabrica IN ($telecontrol_distrib) ORDER BY nome";
				$res = pg_exec($con,$sql);
				if(pg_numrows($res)>0){
					for($x = 0; $x < pg_numrows($res);$x++) {
						$aux_fabrica = pg_fetch_result($res,$x,fabrica);
						$aux_nome    = pg_fetch_result($res,$x,nome);
						echo "<option value='$aux_fabrica'" ;if($fabrica==$aux_fabrica) echo "selected"; echo ">$aux_nome</option>";
					}
				}
			echo "</select>";
			?>
			</td>
		</tr>
		<?php
			$style_distrib = "style='display: none;'";
			if ($_POST["tipo_pedido"] == 'distrib') {
				$style_distrib = "style='display: blocked;'";
			}
		?>
		<tr>
			<td align='center' colspan='6'>
				<input type='radio' name='tipo_pedido' value='garantia' <?= ($_POST['tipo_pedido'] == 'garantia') ? "checked" : "" ?>>Garantia
				<input type='radio' name='tipo_pedido' value='faturado' <?= ($_POST['tipo_pedido'] == 'faturado') ? "checked" : "" ?>>Faturado
				<input class='via_distrib' type='radio' name='tipo_pedido' value='distrib' <?=$style_distrib?>  <?= ($_POST['tipo_pedido'] == 'distrib') ? "checked" : "" ?>><label class='via_distrib' <?=$style_distrib?>>Via Distrib</label>
			</td>
		</tr>
		<tr>
			<td align='center' colspan='6'>
				<input type='radio' name='peca_produto' value='peca' <?= ($_POST['tipo_pedido'] == 'peca') ? "checked" : "" ?>>Apenas Peça
				<input type='radio' name='peca_produto' value='produto' <?= ($_POST['tipo_pedido'] == 'produto') ? "checked" : "" ?>>Apenas Produto
			</td>
		</tr>
		<tr>
			<td align='center' colspan='6'>
				<input type='radio' name='tipo_os' value='C' <?= ($_POST['tipo_os'] == 'C') ? "checked" : "" ?>>OS's Consumidor 
				<input type='radio' name='tipo_os' value='R' <?= ($_POST['tipo_os'] == 'R') ? "checked" : "" ?>>OS's Revenda
			</td>
		</tr>

		<tr>
			<td align='center' colspan='6'><input type='submit' name='btn_acao' id='btn_acao' value='Pesquisar'></td>
		</tr>
	</table>
	<br/>
<?
if(!empty($btn_acao) and empty($msg_erro) ) {
	if($peca_produto == 'peca') $cond = "AND tbl_peca.produto_acabado is not true ";
	if($peca_produto == 'produto') $cond = "AND tbl_peca.produto_acabado ";

	if($tipo_pedido == 'garantia') $cond2 = " AND tbl_tipo_pedido.pedido_em_garantia ";
	if($tipo_pedido == 'faturado') $cond2 = " AND tbl_tipo_pedido.pedido_faturado ";

	if($tipo_pedido == 'garantia') $campoData = " AND tbl_os.data_abertura between '$data_inicial' and '$data_final'";
	if($tipo_pedido == 'faturado') $campoData = " AND tbl_pedido.data between '$data_inicial' and '$data_final'";

	if($tipo_pedido == 'distrib') $campoData = " AND tbl_pedido.distribuidor = 4311";	

	if(empty($tipo_pedido)) $campoData = "";

	if (!empty($tipo_os)) $campo_tipo_os = " AND tbl_os.consumidor_revenda = '$tipo_os'";

	$sql = "SELECT  tbl_peca.peca,
			tbl_peca.referencia,
			tbl_peca.descricao,
			tbl_produto.referencia AS ref_produto,
			tbl_produto.descricao AS desc_produto,
			tbl_os.sua_os,
			to_char(MIN (tbl_pedido.data),'DD/MM/YYYY') AS desde,
			SUM(tbl_pedido_item.qtde - tbl_pedido_item.qtde_cancelada - tbl_pedido_item.qtde_faturada - tbl_pedido_item.qtde_faturada_distribuidor) AS qtde,
			tbl_posto_estoque.qtde as qtde_estoque,
			tbl_pedido.pedido,
			tbl_tipo_pedido.descricao as tipo_pedido,
			tbl_posto.nome,
			tbl_posto.cnpj,
			tbl_posto_fabrica.contato_fone_comercial,
			TO_CHAR(tbl_pedido.data,'DD/MM/YYYY') AS data_pedido,
			TO_CHAR(tbl_os.data_digitacao,'DD/MM/YYYY') AS data_os
		FROM tbl_pedido_item
		JOIN tbl_pedido ON tbl_pedido_item.pedido = tbl_pedido.pedido AND tbl_pedido.fabrica = $fabrica
		JOIN tbl_tipo_pedido ON tbl_pedido.tipo_pedido = tbl_tipo_pedido.tipo_pedido
		JOIN tbl_peca ON tbl_pedido_item.peca = tbl_peca.peca
		JOIN tbl_posto ON tbl_pedido.posto = tbl_posto.posto
		JOIN tbl_posto_fabrica ON tbl_pedido.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = tbl_pedido.fabrica
		LEFT JOIN tbl_os_item ON tbl_pedido_item.pedido_item = tbl_os_item.pedido_item
		LEFT JOIN tbl_os_produto USING(os_produto)
		LEFT JOIN tbl_os ON tbl_os_produto.os = tbl_os.os AND tbl_os.fabrica = $fabrica and tbl_os.excluida is not true
		LEFT JOIN tbl_produto ON tbl_os.produto = tbl_produto.produto
		LEFT JOIN tbl_posto_estoque ON tbl_posto_estoque.peca = tbl_peca.peca
		WHERE tbl_pedido.fabrica = $fabrica
		$campoData
		AND tbl_pedido_item.qtde > tbl_pedido_item.qtde_faturada + tbl_pedido_item.qtde_cancelada+tbl_pedido_item.qtde_faturada_distribuidor
		AND tbl_pedido.distribuidor notnull
		and tbl_pedido.finalizado IS NOT NULL
		AND ( tbl_pedido.posto not in (4311,6359) or (tbl_pedido.posto not in (20682) and tbl_pedido.pedido_cliente != 'REPOSIÇÃO ESTOQUE'))
		$cond2
		$campo_tipo_os
		GROUP BY tbl_peca.peca,
			 tbl_peca.referencia,
			 tbl_peca.descricao,
			 tbl_produto.referencia,
			 tbl_produto.descricao,
			 tbl_os.sua_os,
			 tbl_posto_estoque.qtde,
			 tbl_pedido.pedido,
			 tbl_tipo_pedido.descricao,
			 tbl_posto.nome,
			 tbl_posto.cnpj,
			 tbl_posto_fabrica.contato_fone_comercial,
			 tbl_pedido.data,
			 tbl_os.data_digitacao";
#echo nl2br($sql); exit;
		$res = pg_exec ($con,$sql);

	echo "<input type='button' value='Relatorio Detalhado' id='relatorio' onclick=\"javascript:gerarExcel('$data_inicial','$data_final','$fabrica','$tipo_pedido')\">";
	echo "<br>";
	echo "<table align='center' border='0' cellspacing='1' cellpadding='1'>";
	echo "<tr bgcolor='#663366' style='color:#ffffff ; font-weight:bold ; font-size:16px' align='center'>";
	echo "<td>CNPJ</td><td>Posto</td><td>Fone Contato</td>";
	echo "<td>Referência Peça</td>";

	if ($fabrica == 122 && ($tipo_pedido == 'faturado' || $tipo_pedido == 'garantia')) {
		echo "<td>De para Referência</td>";
	}

	echo "<td>Descrição Peça</td>";
	echo "<td>Peças Alternativas</td>";
	if ($tipo_pedido != 'faturado') {
		echo "<td>Referência Produto</td>";
		echo "<td>Descrição Produto</td>";
	}

	echo "<td>Data AB do Pedido</td>";
	echo "<td>Pedido</td>";
	echo "<td>OS</td>";
	echo "<td>Data AB da OS</td>";
	echo "<td>Desde</td>";	
	echo "<td>Estoque</td>";	
	echo "<td>Qtd Peças Alternativas</td>";	
	echo "<td>Estoque Total</td>";
	echo "<td>Qtde</td>";	
	//echo "<td>Compra</td>";
	echo "</tr>";

	for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
		$referencia   = pg_fetch_result($res,$i,'referencia');
		$descricao    = pg_fetch_result($res,$i,'descricao');
		$qtde         = pg_fetch_result($res,$i,'qtde');
		$qtde_estoque = pg_fetch_result($res,$i,'qtde_estoque');
		$qtde_estoque = (empty($qtde_estoque)) ? 0 : $qtde_estoque;
		$peca         = pg_fetch_result($res,$i,'peca');

		$sql_alternativa = "SELECT peca_para FROM tbl_peca_alternativa WHERE peca_de = $peca AND fabrica = $fabrica";
		$res_alternativa = pg_query($con, $sql_alternativa);
		$array_pecas_alternativas = [];
		$pecas_alternativas = "";
		$qtde_peca_alternativa = "";

		if (pg_num_rows($res_alternativa) > 0) {
			for ($p=0; $p < pg_num_rows($res_alternativa); $p++) { 
				$peca_para_alternativa = pg_fetch_result($res_alternativa, $p, "peca_para");

				$sql_peca_estoque = "SELECT referencia, 
											qtde 
									 FROM tbl_peca 
									 JOIN tbl_posto_estoque USING(peca) 
									 WHERE tbl_peca.peca = $peca_para_alternativa 
									 AND tbl_peca.fabrica = $fabrica";
				$res_peca_estoque = pg_query($con, $sql_peca_estoque);
				if (pg_num_rows($res_peca_estoque) > 0) {
					$array_pecas_alternativas[] = pg_fetch_result($res_peca_estoque, 0, 'referencia');
					$qtde_peca_alternativa += pg_fetch_result($res_peca_estoque, 0, 'qtde');
				}
			}
			$pecas_alternativas = implode(",", $array_pecas_alternativas);	
		}
		
		$total = $qtde_peca_alternativa + $qtde_estoque; 
	
		$qtde_compra  =  0;

		if ($total < $qtde) {

			$qtde_compra = $qtde - $total;
		} 
		
		//$qtde_compra  = ($qtde + $qtde_peca_alternativa) - $qtde > 0 ?  ($qtde + $qtde_peca_alternativa) - $qtde : 0;

		$ref_produto  = pg_fetch_result($res,$i,'ref_produto');
		$desc_produto = pg_fetch_result($res,$i,'desc_produto');
		$sua_os       = pg_fetch_result($res,$i,'sua_os');
		$pedido       = pg_fetch_result($res,$i, 'pedido');
		$cnpj	      = pg_fetch_result($res,$i,'cnpj');
		$posto	      = pg_fetch_result($res,$i,'nome');
		$fone	      = pg_fetch_result($res,$i,'contato_fone_comercial');
		$data_pedido  = pg_fetch_result($res,$i,'data_pedido');
		$data_os	  = pg_fetch_result($res,$i,'data_os');

		$cor = "cccccc";
		if ($i % 2 == 0) $cor = '#eeeeee';

		echo "<tr bgcolor='$cor' style='font-size:11px' id='$peca'>";
		
		echo "<td>$cnpj</td>";
		echo "<td>$posto</td>";
		echo "<td>$fone</td>";
		echo "<td><a href=\"javascript:mostraPedido('$peca','$data_inicial','$data_final','$fabrica','$tipo_pedido')\">";
		echo $referencia;
		echo "</td>";

		if ($fabrica == 122 && ($tipo_pedido == 'faturado' || $tipo_pedido == 'garantia')) {
			
			$sql_de_para = "SELECT tbl_peca.referencia as referencia_para
							FROM tbl_depara
							JOIN tbl_peca ON tbl_peca.fabrica = $fabrica 
							AND tbl_peca.peca = tbl_depara.peca_para
							WHERE tbl_depara.peca_de = $peca
							AND (tbl_depara.expira > current_date OR tbl_depara.expira IS NULL)";
			$res_de_para = pg_query($con, $sql_de_para);

			$referencia_de_para = pg_fetch_result($res_de_para, 0, 'referencia_para');

			echo "<td><strong>".$referencia_de_para."</strong></td>";
		}

		echo "<td>";
		echo $descricao;
		echo "</td>";

		echo "<td>";
		echo $pecas_alternativas;
		echo "</td>";

		if ($tipo_pedido != 'faturado') {
			echo "<td>";
			echo $ref_produto;
			echo "</td>";

			echo "<td>";
			echo $desc_produto;
			echo "</td>";
		}

		echo "<td>";
		echo $data_pedido;
		echo "</td>";

		echo "<td>";
		echo $pedido;
		echo "</td>";

		echo "<td>";
		echo $sua_os;
		echo "</td>";

		echo "<td>";
		echo $data_os;
		echo "</td>";

		echo "<td>";
		echo $desde;
		echo "</td>";

		echo "<td align='right'>";
		echo $qtde_estoque;
		echo "</td>";
		
		if (empty($qtde_peca_alternativa)) {
			$qtde_peca_alternativa = 0;
		}

		echo "<td align='right'>";
		echo $qtde_peca_alternativa;		
		echo "</td>";

		echo "<td align='right'>";
		echo $total;
		echo "</td>";		

		echo "<td align='right'>";
		echo $qtde;
		echo "</td>";

		/*echo "<td align='right'>";
		echo $qtde_compra;
		echo "</td>";*/

		echo "</tr>";
	}

	echo "</table>";
}
 include "rodape.php"; ?>

</body>
</html>
