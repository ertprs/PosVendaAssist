<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios = "gerencia,auditoria";
include 'autentica_admin.php';
$consumidor_revenda = $_GET['consumidor_revenda'];
#$cond_1 = "1=1";
#$cond_2 = "1=1";
#$cond_3 = "1=1";
#$cond_4 = "1=1";
#$cond_5 = "1=1";
if (strlen ($consumidor_revenda)  > 0) $cond_5 = " tbl_os.consumidor_revenda = '$consumidor_revenda' ";
//////////////////////////////////////////
if (strlen ($pais)   > 0) $cond_6 = " tbl_posto.pais     = '$pais' ";
if (1 == 1) {

	include ("../jpgraph/jpgraph.php");
	include ("../jpgraph/jpgraph_pie.php");
	
	// nome da imagem
	$img = time();
	$image_graph = "png/3_$img.png";
	
	// seleciona os dados das m�dias
	setlocale (LC_ALL, 'et_EE.ISO-8859-1');

	$peca_descricao = " tbl_peca.descricao ";
	if($login_fabrica == 20 AND $pais <> "BR"){
		$peca_descricao   = " tbl_peca_idioma.descricao ";
		$join_peca_idioma = " LEFT JOIN tbl_peca_idioma on tbl_peca.peca = tbl_peca_idioma.peca AND tbl_peca_idioma.idioma = 'ES' ";
	}
	
	$sql = "SELECT tbl_peca.referencia, $peca_descricao, tbl_peca.peca, pecas.qtde AS ocorrencia
		FROM tbl_peca
		$join_peca_idioma
		JOIN   (SELECT tbl_os_item.peca, COUNT(*) AS qtde
				FROM tbl_os_item
				JOIN tbl_os_produto USING (os_produto)
				JOIN   (SELECT tbl_os.os , 
						      (SELECT status_os FROM tbl_os_status WHERE tbl_os_status.os = tbl_os_extra.os ORDER BY data DESC LIMIT 1) AS status
						FROM tbl_os_extra
						JOIN tbl_extrato USING (extrato)
						JOIN tbl_os ON tbl_os_extra.os = tbl_os.os
						JOIN tbl_posto ON tbl_os.posto = tbl_posto.posto
						JOIN tbl_produto ON tbl_os.produto = tbl_produto.produto
						WHERE tbl_extrato.fabrica = $login_fabrica
						AND   tbl_extrato.data_geracao BETWEEN '$data_inicial' AND '$data_final' ";
if ($login_fabrica == 14) $sql .= "AND   tbl_extrato.liberado IS NOT NULL ";
$sql .= "AND   tbl_os.excluida IS NOT TRUE
						AND   tbl_os.produto = $produto
						AND   $cond_1
						AND   $cond_2
						AND   $cond_3
						AND   $cond_4
						AND   $cond_5
				) fcr ON tbl_os_produto.os = fcr.os
				WHERE (fcr.status NOT IN (13,15) OR fcr.status IS NULL)
				GROUP BY tbl_os_item.peca
		) pecas ON tbl_peca.peca = pecas.peca
		ORDER BY pecas.qtde DESC " ;

$sql = "SELECT tbl_peca.referencia, $peca_descricao , tbl_peca.peca, pecas.qtde AS ocorrencia
		FROM tbl_peca
		$join_peca_idioma 
		JOIN   (SELECT tbl_os_item.peca, COUNT(*) AS qtde
				FROM tbl_os_item
				JOIN tbl_os_produto USING (os_produto)
				JOIN   (SELECT tbl_os.os
						FROM tbl_os_extra
						JOIN tbl_extrato USING (extrato)
						JOIN tbl_extrato_extra USING (extrato)
						JOIN tbl_os ON tbl_os_extra.os = tbl_os.os
						JOIN tbl_posto ON tbl_os.posto = tbl_posto.posto
						JOIN tbl_produto ON tbl_os.produto = tbl_produto.produto
						WHERE tbl_extrato.fabrica = $login_fabrica
						AND   ";
if($login_fabrica == 20){
	if($pais== "BR"){
		$sql .=	" tbl_extrato_extra.exportado BETWEEN '$data_inicial' AND '$data_final' ";
	}else{
		$sql .=	" tbl_extrato.data_geracao BETWEEN '$data_inicial' AND '$data_final' ";
	}
}
else                      $sql .=	" tbl_extrato.data_geracao BETWEEN '$data_inicial' AND '$data_final' ";
if ($login_fabrica == 14) $sql .= "AND   tbl_extrato.liberado IS NOT NULL ";
$sql .= "AND   tbl_os.excluida IS NOT TRUE
						AND   tbl_os.produto = $produto
						AND   $cond_1
						AND   $cond_2
						AND   $cond_3
						AND   $cond_4
						AND   $cond_5
						AND   $cond_6
				) fcr ON tbl_os_produto.os = fcr.os
				GROUP BY tbl_os_item.peca
		) pecas ON tbl_peca.peca = pecas.peca
		ORDER BY pecas.qtde DESC " ;
//takashi retirou 17/09 conforme conversa com t�lio
// , (SELECT status_os FROM tbl_os_status WHERE tbl_os_status.os = tbl_os_extra.os ORDER BY data DESC LIMIT 1) AS status WHERE (fcr.status NOT IN (13,15) OR fcr.status IS NULL)

if($login_fabrica==24){

$sql = "SELECT tbl_peca.referencia, tbl_peca.descricao, tbl_peca.peca, pecas.qtde AS ocorrencia
		FROM tbl_peca
		JOIN   (SELECT tbl_os_item.peca, COUNT(*) AS qtde
				FROM tbl_os_item
				JOIN tbl_os_produto USING (os_produto)
				JOIN   (SELECT tbl_os.os 
						FROM tbl_os_extra
						JOIN tbl_extrato USING (extrato)
						JOIN tbl_extrato_extra USING (extrato)
						JOIN tbl_os ON tbl_os_extra.os = tbl_os.os
						JOIN tbl_posto ON tbl_os.posto = tbl_posto.posto
						JOIN tbl_produto ON tbl_os.produto = tbl_produto.produto
						WHERE tbl_extrato.fabrica = $login_fabrica
						AND  tbl_extrato.data_geracao BETWEEN '$data_inicial 00:00:00' AND '$data_final 23:59:59' 
						AND   tbl_os.excluida IS NOT TRUE
						AND   tbl_os.produto = $produto
						AND   $cond_1
						AND   $cond_2
						AND   $cond_3
						AND   $cond_4
						AND   $cond_5
				) fcr ON tbl_os_produto.os = fcr.os
				GROUP BY tbl_os_item.peca
		) pecas ON tbl_peca.peca = pecas.peca
		ORDER BY pecas.qtde DESC " ;

}
if($login_fabrica==6){
		$sql = "SELECT tbl_peca.referencia, 
						tbl_peca.descricao, 
						tbl_peca.peca, 
						pecas.qtde AS ocorrencia
				FROM tbl_peca 
				JOIN (SELECT tbl_os_item.peca, COUNT(*) AS qtde
				FROM tbl_os_item
				JOIN tbl_os_produto USING (os_produto)
				JOIN (
						SELECT tbl_os.os 
						FROM tbl_os_extra
						JOIN tbl_extrato USING (extrato)
						JOIN tbl_extrato_extra USING (extrato)
						JOIN tbl_os ON tbl_os_extra.os = tbl_os.os
						JOIN tbl_posto ON tbl_os.posto = tbl_posto.posto
						JOIN tbl_produto ON tbl_os.produto = tbl_produto.produto
						WHERE tbl_extrato.fabrica = $login_fabrica
						AND tbl_extrato.data_geracao BETWEEN '$data_inicial 00:00:00' AND '$data_final 23:59:59' 
						AND tbl_os.excluida IS NOT TRUE
						AND tbl_os.produto = $produto
						AND   $cond_1
						AND   $cond_2
						AND   $cond_3
						AND   $cond_4
						AND   $cond_5
				) fcr ON tbl_os_produto.os = fcr.os
				GROUP BY tbl_os_item.peca
				) pecas ON tbl_peca.peca = pecas.peca
				ORDER BY pecas.qtde DESC ";
	
	if($tipo_pesquisa=="data_abertura"){
		$sql = "SELECT tbl_peca.referencia, 
						tbl_peca.descricao, 
						tbl_peca.peca, 
						pecas.qtde AS ocorrencia
				FROM tbl_peca 
				JOIN (
					SELECT tbl_os_item.peca, COUNT(*) AS qtde
					FROM tbl_os_item
					JOIN tbl_os_produto USING (os_produto)
					JOIN (
							SELECT tbl_os.os 
							FROM tbl_os 
							JOIN tbl_posto ON tbl_os.posto = tbl_posto.posto
							JOIN tbl_produto ON tbl_os.produto = tbl_produto.produto
							WHERE tbl_os.fabrica = $login_fabrica
							AND tbl_os.data_abertura BETWEEN '$data_inicial' AND '$data_final' 
							AND tbl_os.excluida IS NOT TRUE
							AND tbl_os.produto = $produto
							AND   $cond_1
							AND   $cond_2
							AND   $cond_3
							AND   $cond_4
							AND   $cond_5
					) fcr ON tbl_os_produto.os = fcr.os
					GROUP BY tbl_os_item.peca
				) pecas ON tbl_peca.peca = pecas.peca
				ORDER BY pecas.qtde DESC ";
	
	}
	
	}

//if($ip=="201.26.23.85")echo nl2br($sql);
//if($ip=="201.92.1.225") echo $sql;
	
	$res = pg_exec($con,$sql);
	$fim          = pg_numrows($res) -1;
	for ($x = 0; $x < pg_numrows($res); $x++) {
			$total = $total + pg_result($res,$x,ocorrencia);
		}
	$n_ocorrencia_anterior =0;
	for ($x = 0; $x < pg_numrows($res); $x++) {

		$y = pg_result($res,$x,ocorrencia);
		$p_ocorrencia = ( $y/ $total ) * 100;

		if ($x==0) {
			$ocorrencia = pg_result($res,$x,ocorrencia);
			
			$descricao  = substr(pg_result($res,$x,descricao), 0, 35);
			$porc_ocorrencia = $p_ocorrencia;
		}
		elseif ($x>=9){
			$fim          = pg_numrows($res) -1;
			$n_ocorrencia = pg_result($res,$x,ocorrencia);

			$n_ocorrencia = $n_ocorrencia + $n_ocorrencia_anterior;
//echo "$x Atual: $n_ocorrencia - Anterior: $n_ocorrencia_anterior <br>";
			$n_ocorrencia_anterior = $n_ocorrencia;
			
			
			if($x ==$fim){
				$p_ocorrencia = ( $n_ocorrencia/ $total ) * 100;
				$porc_ocorrencia = $porc_ocorrencia .','.$p_ocorrencia;
				$descricao       = $descricao.', Outros';
//echo "<br><br>Total".$n_ocorrencia;
			}
		}
		else {

			$n_descricao  = substr(pg_result($res,$x,descricao), 0, 35);
			$n_descricao  = str_replace(",","",$n_descricao); 
//echo "<BR>$x =>desc: $n_descricao";
			$ocorrencia   = $ocorrencia.','.$n_ocorrencia;

			$descricao  = $descricao.','.$n_descricao;

			$porc_ocorrencia = $porc_ocorrencia .','.$p_ocorrencia;
//echo "<BR>$x descricao: $descricao - ocorrencia: $ocorrencia <br>";
		}
	}
//if ($ip="200.158.65.19") {echo $ocorrencia;} 
	if ($total > 0){

		$data = explode(",",$porc_ocorrencia);

		// Create the graph. These two calls are always required
		$graph = new PieGraph(700,500,"auto");
		$graph->SetShadow();

		// Set a title for the plot
		$graph->title->Set("Relat�rio de Field Call Rate ");
		$graph->title->SetFont(FF_FONT1,FS_BOLD);

		// Create
		$p1 = new PiePlot($data);
		$p1->SetCenter(0.35,0.50);
		$p1->SetLegends(explode(",",$descricao));

		$graph->Add($p1);

		$graph->Stroke($image_graph);
		echo "\n\n<img src='$image_graph'>\n\n";

	}

//////////////////////////////////////////
}
?>