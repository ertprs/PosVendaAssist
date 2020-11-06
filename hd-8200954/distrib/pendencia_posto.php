<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';



#$title = "DETALHAMENTO DE NOTA FISCAL";
#$layout_menu = 'pedido';

#include "cabecalho.php";
?>

<html>
<head>
<title>Pendência dos Postos</title>
<link type="text/css" rel="stylesheet" href="css/css.css">
</head>

<body>

<? include 'menu.php' ?>


<center><h1>Pendência dos Postos de peças (não contém produtos acabados)</h1></center>

<p>

<?
$apenas_saldo = $_POST['apenas_saldo'];
$codigo_posto = $_POST['codigo_posto'];
$nome         = $_POST['nome'];
$pedido       = $_POST['pedido'];
$nota_fiscal  = $_POST['nota_fiscal'];

?>


<center>
<form name='frm_estoque' action='<? echo $PHP_SELF ?>' method='post'>

Código do Posto <input type='text' class='frm' size='10' name='codigo_posto' value='<? echo $codigo_posto ?>'>
Nome do Posto <input type='text' class='frm' size='25' name='nome' value='<? echo $nome ?>'>
<br>
Pedido <input type='text' class='frm' size='10' name='pedido' value='<? echo $pedido ?>'>
Nota Fiscal <input type='text' class='frm' size='10' name='nota_fiscal' value='<? echo $nota_fiscal ?>'>

<br>

<input type='checkbox' name='apenas_saldo' value='1' <? if ($apenas_saldo == "1") echo " checked " ?> >Incluir pedidos já atendidos

<input type='submit' name='btn_acao' value='Pesquisar'>

</form>
</center>


<?

$codigo_posto = trim ($_POST['codigo_posto']);
$nome         = trim ($_POST['nome']);
$pedido       = trim ($_POST['pedido']);
$nota_fiscal  = trim ($_POST['nota_fiscal']);

if (strlen ($codigo_posto) > 1 OR strlen ($nome) > 2 OR strlen ($pedido) > 3 OR strlen ($nota_fiscal) > 2 ) {
	if (strlen ($codigo_posto) > 1) {
		$condicao = " tbl_posto_fabrica.codigo_posto ILIKE '%$codigo_posto%' ";
	}

	if (strlen ($nome) > 1) {
		$condicao = " tbl_posto.posto IN ( SELECT DISTINCT posto FROM tbl_posto WHERE nome ILIKE '%$nome%' ) ";
		$join_1   = " JOIN ( SELECT DISTINCT posto FROM tbl_posto WHERE nome ILIKE '%$nome%' ) pnome ON tbl_pedido.posto = pnome.posto ";
	}

	if (strlen ($pedido) > 1) {
		$condicao = " tbl_pedido.pedido = $pedido ";
	}

	if (strlen ($nota_fiscal) > 1) {
		$condicao = " tbl_faturamento.nota_fiscal = LPAD ('$nota_fiscal',6,'0') ";
	}

	$condicao_2 = " tbl_pedido_item.qtde > tbl_pedido_item.qtde_faturada_distribuidor + tbl_pedido_item.qtde_cancelada";
	if ($apenas_saldo == "1") {
		$condicao_2 = "true";
	}

	$sql = "SELECT tbl_posto_fabrica.codigo_posto, 
						tbl_posto.cnpj ,
						tbl_posto.fone,
						tbl_posto.posto, 
						tbl_posto.nome, 
						tbl_posto.cidade,
						tbl_posto.estado, 
						tbl_peca.peca, 
						tbl_peca.referencia, 
						tbl_peca.descricao, 
						tbl_pedido_item.qtde, 
						tbl_pedido_item.qtde_cancelada, 
						tbl_pedido_item.qtde_faturada_distribuidor, 
						tbl_pedido.pedido, 
						to_char (tbl_pedido.data,'DD/MM/YYYY') AS pedido_data, tbl_faturamento.nota_fiscal, 
						to_char (tbl_faturamento.emissao,'DD/MM/YYYY') AS nf_emissao , tbl_posto_estoque.qtde AS qtde_estoque, 
						tbl_pedido_item.pedido_item, 
						tbl_pedido.tipo_pedido
		FROM tbl_posto
		JOIN tbl_pedido      USING (posto)
		JOIN tbl_pedido_item USING (pedido)
		JOIN tbl_peca        USINg (peca)
		LEFT JOIN tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = tbl_pedido.fabrica
		$join_1
		LEFT JOIN tbl_posto_estoque ON tbl_pedido.distribuidor = tbl_posto_estoque.posto AND tbl_pedido_item.peca = tbl_posto_estoque.peca
		LEFT JOIN tbl_embarque_item ON tbl_pedido_item.pedido_item  = tbl_embarque_item.pedido_item
		LEFT JOIN tbl_faturamento   ON tbl_embarque_item.embarque = tbl_faturamento.embarque AND tbl_pedido.tipo_pedido = tbl_faturamento.tipo_pedido
		WHERE $condicao
		AND   $condicao_2

		AND   tbl_pedido.distribuidor = $login_posto
		AND   tbl_peca.produto_acabado IS NOT TRUE
		ORDER BY tbl_posto.nome, tbl_peca.referencia, tbl_pedido_item.pedido_item
		";
// HD 13935 - nao aparecer produto acabado
#		AND   (tbl_pedido.posto <> 970 OR tbl_pedido.tipo_pedido = 2)
#echo nl2br($sql);
#exit ;

	$res = pg_exec ($con,$sql);

	echo "<table border='0' cellspacing='1' cellpadding='1'>";
	$posto_ant = "";
	$pedido_item_ant = "";

	$reotnro = "";

	for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
		if ($posto_ant <> pg_result ($res,$i,posto) ) {
			echo "<tr bgcolor='#0099CC' style='color:#ffffff ; align='center' style='font-weight:bold'>";

			echo "<td colspan='13' align='center'>";
			echo pg_result ($res,$i,cnpj)." - ";
			echo pg_result ($res,$i,nome);
			echo " Fone: ";
			echo pg_result ($res,$i,fone);
			echo "</td>";

			echo "</tr>";


			echo "<tr bgcolor='#0099CC'style='color:#ffffff ; align='center' style='font-weight:bold'>";

			echo "<td nowrap>Pedido</td>";
			echo "<td nowrap>Data Pedido</td>";
			echo "<td nowrap>Tipo</td>";
			echo "<td nowrap>Peça</td>";
			echo "<td nowrap>Descrição</td>";
			echo "<td nowrap>Pedida</td>";
			echo "<td nowrap>Cancelada</td>";
			echo "<td nowrap>Atendida</td>";
			echo "<td nowrap>Pendente</td>";
			echo "<td nowrap>Estoque</td>";
			echo "<td nowrap>Fábrica</td>";
			echo "<td nowrap>Transp.</td>";
			echo "<td nowrap>Nota Fiscal</td>";
	
			echo "</tr>";

			$posto_ant = pg_result ($res,$i,posto);
			$pedido_item_ant = "";
		}

		if ($pedido_item_ant <> pg_result ($res,$i,pedido_item) ) {

			$qtde_fabrica   = 0;
			$qtde_pedida    = pg_result ($res,$i,qtde);
			$qtde_cancelada = pg_result ($res,$i,qtde_cancelada);
			$qtde_atendida  = pg_result ($res,$i,qtde_faturada_distribuidor);;
			$saldo          = $qtde_pedida - $qtde_cancelada - $qtde_atendida;

			if ($saldo>0){
				$saldo = "<b style='color:red'>$saldo</b>";
			}else{
				$saldo = "<b style='color:blue'>$saldo</b>";
			}

			$peca           = pg_result ($res,$i,peca);

			$sql_fabrica = "
				/*comentado no chamado HD 49576
				SELECT	peca,
						SUM (qtde - qtde_faturada - qtde_cancelada) AS qtde_fabrica 
				FROM tbl_pedido_item 
				JOIN tbl_peca USING (peca) 
				JOIN tbl_pedido USING (pedido) 
				WHERE   ((tbl_pedido.posto = $login_posto AND tbl_pedido.tipo_pedido = 2)
						OR
						(tbl_pedido.distribuidor = $login_posto	AND tbl_pedido.tipo_pedido = 3 ) )
					AND tbl_pedido.fabrica IN (".implode(",", $fabricas).") 
					AND tbl_peca.peca = $peca
					AND tbl_pedido.status_pedido NOT IN (3,4)
					AND tbl_pedido_item.qtde > (tbl_pedido_item.qtde_faturada + tbl_pedido_item.qtde_cancelada) 
				GROUP BY tbl_pedido_item.peca*/
				
				SELECT peca, SUM (qtde - qtde_faturada - qtde_cancelada) AS qtde_fabrica 
				FROM tbl_pedido_item 
				JOIN tbl_pedido USING (pedido)
				WHERE tbl_pedido.fabrica NOT IN (0,10) /* HD 43268 NOT IN (10) */
				AND (
				(tbl_pedido.posto        = $login_posto /*AND tbl_pedido.tipo_pedido = 2) OR 
				(tbl_pedido.distribuidor = $login_posto AND tbl_pedido.tipo_pedido = 3) OR
				(tbl_pedido.posto        = $login_posto AND tbl_pedido.tipo_pedido = 131) OR 
				(tbl_pedido.distribuidor = $login_posto AND tbl_pedido.tipo_pedido = 132*/) 
				)
				AND     tbl_pedido.fabrica  IN (".implode(",", $fabricas).") 
				AND     tbl_pedido_item.peca = $peca
				AND     (tbl_pedido.status_pedido NOT IN (3,4,6,13) OR tbl_pedido.status_pedido IS NULL)
				AND     tbl_pedido_item.qtde > (tbl_pedido_item.qtde_faturada + tbl_pedido_item.qtde_cancelada)
				GROUP BY tbl_pedido_item.peca;
				";
			$resFabrica = pg_exec ($con,$sql_fabrica);
			if (pg_numrows ($resFabrica)>0) {
				$qtde_fabrica = pg_result ($resFabrica,0,qtde_fabrica);
			}

			$reotnro .= nl2br($sql_fabrica);

			$sql_transp = "
				SELECT	peca,
						SUM (qtde) AS qtde_transp
				FROM tbl_faturamento_item 
				JOIN tbl_faturamento USING (faturamento) 
				JOIN tbl_peca USING (peca) 
				WHERE tbl_faturamento.posto = $login_posto 
				AND tbl_faturamento.fabrica IN (".implode(",", $fabricas).")
				AND tbl_faturamento.conferencia  IS NULL
				AND tbl_faturamento.cancelada    IS NULL 
				AND tbl_faturamento.distribuidor IS NULL 
				AND tbl_peca.peca = $peca
				GROUP BY tbl_faturamento_item.peca";
			$resTransp = pg_exec ($con,$sql_transp);
			if (pg_numrows ($resTransp)>0) {
				$qtde_transp = pg_result ($resTransp,0,qtde_transp);
			}

			$reotnro .= nl2br($resTransp);

			echo "</td>";
			echo "</tr>";
			$cor = "#eeeeee";
			if ($i % 2 == 0)$cor = "#cccccc";

			echo "<tr style='font-size:12px' bgcolor='$cor'> ";

			echo "<td>";
			echo pg_result ($res,$i,pedido);
			echo "</td>";

			echo "<td>";
			echo pg_result ($res,$i,pedido_data);
			echo "</td>";

			echo "<td>";
			if ($login_fabrica == 3 AND $login_posto == 4311) {
				$t_pedido = pg_result ($res,$i,tipo_pedido);
				$sql_tipo = "SELECT codigo 
								FROM tbl_tipo_pedido 
								WHERE tipo_pedido = $t_pedido";
				$resTipo = pg_exec($con,$sql_tipo);
				if (pg_numrows ($resTipo)>0) {
					$tipo_pedido = pg_result ($resTipo,0,codigo);
				}
				echo $tipo_pedido;

			} else {
				if (pg_result ($res,$i,tipo_pedido) == 2) {
					echo "FAT";
				}
				if (pg_result ($res,$i,tipo_pedido) == 3) {
					echo "GAR";
				}

				//GAMA HD 53990
				if (pg_result ($res,$i,tipo_pedido) == 131) {
					echo "FAT";
				}
				if (pg_result ($res,$i,tipo_pedido) == 132) {
					echo "GAR";
				}

				//HBTECH HD 53990
				if (pg_result ($res,$i,tipo_pedido) == 116) {
					echo "FAT";
				}
				if (pg_result ($res,$i,tipo_pedido) == 115) {
					echo "GAR";
				}
			}
			echo "</td>";

			echo "<td nowrap>";
			echo pg_result ($res,$i,referencia);
			echo "</td>";

			echo "<td nowrap>";
			echo pg_result ($res,$i,descricao);
			echo "</td>";

			echo "<td>";
			echo pg_result ($res,$i,qtde);
			echo "</td>";

			echo "<td>";
			if (pg_result ($res,$i,qtde_cancelada) > 0) {
				echo pg_result ($res,$i,qtde_cancelada);
			}else{
				echo "&nbsp;";
			}
			echo "</td>";

			echo "<td>";
			echo pg_result ($res,$i,qtde_faturada_distribuidor);
			echo "</td>";

			echo "<td>";
			echo $saldo;
			echo "</td>";

			echo "<td>";
			echo pg_result ($res,$i,qtde_estoque);
			echo "</td>";

			echo "<td>";
			echo $qtde_fabrica;
			echo "</td>";

			echo "<td>";
			echo $qtde_transp;
			echo "</td>";
			

			echo "<td nowrap>";

			$pedido_item_ant = pg_result ($res,$i,pedido_item);
		}

		echo pg_result ($res,$i,nota_fiscal);
		echo "-";
		echo pg_result ($res,$i,nf_emissao);
		echo "<br>";

	}

	if (pg_numrows ($res)==0){
		echo "<p>Não foi encontrado nenhuma pendência</p>";
	}


	echo "</table>";
include "rodape.php";
exit;

	$sql = "SELECT tbl_peca.referencia, tbl_peca.descricao, tbl_posto_estoque.qtde, fabrica.qtde_fabrica, transp.qtde_transp, para.referencia AS para_referencia, para.descricao AS para_descricao, tbl_posto_estoque_localizacao.localizacao
			FROM   tbl_peca 
			LEFT JOIN tbl_posto_estoque             ON tbl_peca.peca = tbl_posto_estoque.peca AND tbl_posto_estoque.posto = $login_posto
			LEFT JOIN tbl_posto_estoque_localizacao ON tbl_peca.peca = tbl_posto_estoque_localizacao.peca AND tbl_posto_estoque_localizacao.posto = $login_posto
			LEFT JOIN tbl_depara ON tbl_peca.peca = tbl_depara.peca_de
			LEFT JOIN tbl_peca para ON tbl_depara.peca_para = para.peca
			LEFT JOIN (SELECT peca, SUM (qtde - qtde_faturada - qtde_cancelada) AS qtde_fabrica FROM tbl_pedido_item JOIN tbl_pedido USING (pedido) WHERE ((tbl_pedido.posto = $login_posto AND tbl_pedido.tipo_pedido = 2) OR (tbl_pedido.distribuidor = $login_posto AND tbl_pedido.tipo_pedido = 3 ) ) AND tbl_pedido.fabrica IN (".implode(",", $fabricas).") GROUP BY tbl_pedido_item.peca) fabrica ON tbl_peca.peca = fabrica.peca
			LEFT JOIN (SELECT peca, SUM (qtde) AS qtde_transp FROM tbl_faturamento_item JOIN tbl_faturamento USING (faturamento) WHERE tbl_faturamento.posto = $login_posto AND tbl_faturamento.fabrica IN (".implode(",", $fabricas).") AND tbl_faturamento.conferencia IS NULL AND tbl_faturamento.distribuidor IS NULL GROUP BY tbl_faturamento_item.peca) transp ON tbl_peca.peca = transp.peca
			WHERE  (tbl_posto_estoque.posto = $login_posto OR tbl_posto_estoque.posto IS NULL)
			AND    (tbl_peca.referencia ILIKE '%$referencia%' OR para.referencia ILIKE '%$referencia%')
			AND    tbl_peca.fabrica IN (".implode(",", $fabricas).")
			ORDER BY tbl_peca.descricao";
	$res = pg_exec ($con,$sql);
}

if (strlen ($descricao) > 2) {
	$sql = "SELECT tbl_peca.referencia, tbl_peca.descricao, tbl_posto_estoque.qtde, fabrica.qtde_fabrica, transp.qtde_transp, para.referencia AS para_referencia, para.descricao AS para_descricao, tbl_posto_estoque_localizacao.localizacao
			FROM   tbl_peca 
			LEFT JOIN tbl_posto_estoque             ON tbl_peca.peca = tbl_posto_estoque.peca AND tbl_posto_estoque.posto = $login_posto
			LEFT JOIN tbl_posto_estoque_localizacao ON tbl_peca.peca = tbl_posto_estoque_localizacao.peca AND tbl_posto_estoque_localizacao.posto = $login_posto
			LEFT JOIN tbl_depara ON tbl_peca.peca = tbl_depara.peca_de
			LEFT JOIN tbl_peca para ON tbl_depara.peca_para = para.peca
			LEFT JOIN (SELECT peca, SUM (qtde - qtde_faturada - qtde_cancelada) AS qtde_fabrica FROM tbl_pedido_item JOIN tbl_pedido USING (pedido) WHERE ((tbl_pedido.posto = $login_posto AND tbl_pedido.tipo_pedido = 2) OR (tbl_pedido.distribuidor = $login_posto AND tbl_pedido.tipo_pedido = 3 ) ) AND tbl_pedido.fabrica IN (".implode(",", $fabricas).") GROUP BY tbl_pedido_item.peca) fabrica ON tbl_peca.peca = fabrica.peca
			LEFT JOIN (SELECT peca, SUM (qtde) AS qtde_transp FROM tbl_faturamento_item JOIN tbl_faturamento USING (faturamento) WHERE tbl_faturamento.posto = $login_posto AND tbl_faturamento.fabrica IN (".implode(",", $fabricas).") AND tbl_faturamento.conferencia IS NULL AND tbl_faturamento.distribuidor IS NULLGROUP BY tbl_faturamento_item.peca) transp ON tbl_peca.peca = transp.peca
			WHERE  (tbl_posto_estoque.posto = $login_posto OR tbl_posto_estoque.posto IS NULL)
			AND    (tbl_peca.descricao ILIKE '%$descricao%' OR para.descricao ILIKE '%$descricao%')
			AND    tbl_peca.fabrica IN (".implode(",", $fabricas).")
			ORDER BY tbl_peca.descricao";
	$res = pg_exec ($con,$sql);
}

if (strlen ($descricao) > 2 or strlen ($referencia) > 2) {

	echo "<table align='center' border='1' cellspacing='3' cellpaddin='3'>";
	echo "<tr bgcolor='#663366' style='color:#ffffff ; font-weight:bold ; font-size:16px' align='center'>";
	echo "<td>Referência</td>";
	echo "<td>Descrição</td>";
	echo "<td>Estoque</td>";
	echo "<td>Fábrica</td>";
	echo "<td>Transp.</td>";
	echo "<td>Localização</td>";
	echo "</tr>";

	for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {

		$cor = "";
		if (strlen (trim (pg_result ($res,$i,para_referencia))) > 0) $cor = '#dddddd';
		
		echo "<tr bgcolor='$cor'>";

		echo "<td>";
		echo pg_result ($res,$i,referencia);
		if (strlen (trim (pg_result ($res,$i,para_referencia))) > 0) echo "<br>" . pg_result ($res,$i,para_referencia);
		echo "</td>";

		echo "<td>";
		echo pg_result ($res,$i,descricao);
		if (strlen (trim (pg_result ($res,$i,para_referencia))) > 0) echo "<br>" . pg_result ($res,$i,para_descricao);
		echo "</td>";

		echo "<td align='right'>&nbsp;";
		echo pg_result ($res,$i,qtde);
		echo "</td>";

		echo "<td align='right'>&nbsp;";
		echo pg_result ($res,$i,qtde_fabrica);
		echo "</td>";

		echo "<td align='right'>&nbsp;";
		echo pg_result ($res,$i,qtde_transp);
		echo "</td>";

		echo "<td align='left'>&nbsp;";
		echo pg_result ($res,$i,localizacao);
		echo "</td>";

		echo "</tr>";
	}

	echo "</table>";

}

?>

<? #include "rodape.php"; ?>

</body>
</html>
