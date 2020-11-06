<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';
include_once('../helpdesk/mlg_funciones.php');

$btnacao            = getPost('btnacao');
$data_inicial       = getPost('data_inicial');
$data_final         = getPost('data_final');
$produto            = getPost('produto');
$peca               = getPost('peca');
$preco_peca         = getPost('preco_peca');
$linha              = getPost('linha');
$estado             = getPost('estado');
$familia            = getPost('familia');
$tipo               = getPost('tipo');
$consumidor_revenda = getPost('consumidor_revenda');
$tipo_pesquisa      = getPost('tipo_pesquisa');
$pais               = getPost('pais');
$origem             = getPost('origem');
$posto              = getPost('posto');
$defeito_constatado = getPost('defeito_constatado');

if($login_fabrica == 24){
    $matriz_filial = $_REQUEST['matriz_filial'];
    if(strlen($matriz_filial)>0){
        $cond_matriz_filial = " AND substr(tbl_os.serie,length(tbl_os.serie) - 1, 2) = '$matriz_filial' ";
    }
}


$sql = "SELECT descricao FROM tbl_produto WHERE produto = $produto";
$res = @pg_query($con,$sql);

$descricao_produto = @pg_fetch_result($res,0,descricao);
if(strlen($defeito_constatado)> 0) {
	$sql_defeito = "SELECT descricao FROM tbl_defeito_constatado WHERE defeito_constatado = $defeito_constatado";
	$res_defeito = @pg_exec($con,$sql_defeito);
	$descricao_defeito_constatado = @pg_result($res_defeito,0,descricao);
}

if($login_fabrica == 20 and $pais <> "BR"){
	$sql = "SELECT referencia,
				   tbl_peca_idioma.descricao
			  FROM tbl_peca
		 LEFT JOIN tbl_peca_idioma ON tbl_peca.peca          = tbl_peca_idioma.peca
								  AND tbl_peca_idioma.idioma = 'ES'
			 WHERE tbl_peca.peca = $peca";
}else{
	$sql = "SELECT referencia,descricao FROM tbl_peca WHERE peca = $peca";
}

$res = pg_exec($con,$sql);

$referencia_peca = pg_result($res,0,referencia);
$descricao_peca  = pg_result($res,0,descricao);

if($btnacao=='filtrar'){
	$aux_data_inicial = $data_inicial;
	$aux_data_final   = $data_final;

	$fnc = @pg_exec($con,"SELECT fnc_formata_data('$data_inicial')");
	if (strlen ( pg_errormessage ($con) ) > 0) $msg_erro = pg_errormessage ($con) ;	
	$data_inicial = @pg_result ($fnc,0,0);

	$fnc = @pg_exec($con,"SELECT fnc_formata_data('$data_final')");
	if (strlen ( pg_errormessage ($con) ) > 0) $msg_erro = pg_errormessage ($con) ;	
	$data_final = @pg_result ($fnc,0,0);

}else{
	$aux_data_inicial = substr($data_inicial,8,2)."/".substr($data_inicial,5,2)."/".substr($data_inicial,0,4);
	$aux_data_final   = substr($data_final,8,2)."/".substr($data_final,5,2)."/".substr($data_final,0,4);
}
$title = "RELATÓRIO DE QUEBRA - DEFEITOS";

?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">
<HTML>
<HEAD>
<TITLE><? echo $title; ?></TITLE>
<META NAME="Generator" CONTENT="EditPlus">
<META NAME="Author" CONTENT="">
<META NAME="Keywords" CONTENT="">
<META NAME="Description" CONTENT="">

<style type="text/css">

.titPreto14 {
	color: #000000;
	text-align: center;
	font:12px Tahoma, Verdana, Arial, Helvetica, Sans-Serif
}

.titPreto12 {
	color: #000000;
	text-align: left;
	font:12px Tahoma, Verdana, Arial, Helvetica, Sans-Serif
}

.titDatas12 {
	color: #000000;
	text-align: center;
	font:12px Tahoma, Verdana, Arial, Helvetica, Sans-Serif
}

.titChamada10{
	background-color: #596D9B;
	color: #ffffff;
	text-align: center;
	font:11px Tahoma, Verdana, Arial, Helvetica, Sans-Serif
}

.bgTRConteudo1{
	background-color: #FEFEFF;
}

.bgTRConteudo2{
	background-color: #F9FCFF;
}

</style>
</HEAD>
<?
$cond_1 = " AND 1 = 1 ";
$cond_2 = " AND 1 = 1 ";
$cond_3 = " AND 1 = 1 ";
$cond_4 = " AND 1 = 1 ";
$cond_5 = " AND 1 = 1 ";
$cond_6 = " AND 1 = 1 ";

if (strlen ($produto)             > 0) $cond_1 = "AND tbl_os.produto            = $produto ";
if (strlen ($peca)                > 0) $cond_2 = "tbl_os_item.peca          = $peca ";
if (strlen ($posto)               > 0) $cond_3 = "AND tbl_posto.posto           = $posto ";
if (strlen ($estado)              > 0) $cond_4 = "AND tbl_posto.estado          = '$estado' ";
if (strlen ($consumidor_revenda)  > 0) $cond_5 = "AND tbl_os.consumidor_revenda = '$consumidor_revenda' ";
if (strlen ($defeito_constatado)  > 0) $cond_6 = "AND tbl_os.defeito_constatado = $defeito_constatado ";

$tipo_data = " tbl_extrato.data_geracao ";

if($login_fabrica == 20 and $pais <> "BR")
	$tipo_data = " tbl_extrato_extra.exportado ";

flush();

if($login_fabrica <> 20){

	if($login_fabrica==1){
		$xsolucao     = " tbl_solucao.descricao as solucao ";
		$join_solucao = " LEFT JOIN tbl_solucao ON tbl_solucao.solucao = tbl_os.solucao_os ";
	}else{
		$xsolucao     = " tbl_servico_realizado.descricao as solucao ";
		$join_solucao = " LEFT JOIN tbl_servico_realizado ON tbl_servico_realizado.servico_realizado=tbl_os.solucao_os ";
	}
	if ($login_fabrica == 14) $sql_14 .= "AND   tbl_extrato.liberado IS NOT NULL ";

	// HD 892365 - Ao mostrar o valor unitário das peças, na hora de mostrar a quebra deve separar também por preço}
	if ($login_fabrica == 74) {
		$sql_preco_peca = ', tbl_os_item.preco';
		$tmp_preco_peca = ', preco';
		if (strlen($preco_peca) > 0) {
			$cond_7 = "AND tbl_os_item.preco         ";
			$cond_7.= ($preco_peca == '0.00') ? 'IS NULL ':'= '.$preco_peca;

			$mostra_preco = ($preco_peca == '0.00') ? ' (sem preço)' : " (preço: R$ ". number_format($preco_peca, 2, ',', '.') .")";
		}

	}

	$sql = "SELECT tbl_os.os 
			INTO TEMP tmp_fcrd_$login_admin
			FROM tbl_os_extra
			JOIN tbl_extrato       USING (extrato)
			JOIN tbl_extrato_extra ON tbl_extrato_extra.extrato = tbl_extrato.extrato
			JOIN tbl_os            ON tbl_os_extra.os           = tbl_os.os
			JOIN tbl_posto         ON tbl_os.posto              = tbl_posto.posto
			JOIN tbl_produto       ON tbl_os.produto            = tbl_produto.produto
			WHERE tbl_extrato.fabrica = $login_fabrica
			AND   $tipo_data BETWEEN '$data_inicial 00:00:00' AND '$data_final 23:59:59' 
			AND   tbl_os.excluida IS NOT TRUE
			$cond_1
			$cond_3
			$cond_4
			$cond_5 
			$cond_6
			$sql_14
			$cond_matriz_filial;

			CREATE INDEX tmp_fcrd_os_$login_admin ON tmp_fcrd_$login_admin(os);

			SELECT tbl_os_item.defeito$sql_preco_peca, COUNT(*) AS ocorrencia
			INTO TEMP tmp_fcrd2_$login_admin
			FROM tbl_os_item
			JOIN tbl_os_produto USING (os_produto)
			JOIN   tmp_fcrd_$login_admin fcr ON tbl_os_produto.os = fcr.os
			WHERE  $cond_2 $cond_7
			GROUP BY tbl_os_item.defeito$sql_preco_peca;

			CREATE INDEX tmp_fcrd2_defeito_$login_admin ON tmp_fcrd2_$login_admin(defeito);

			SELECT tbl_defeito.descricao AS defeito_descricao, defeitos.ocorrencia$tmp_preco_peca
			FROM tbl_defeito
			JOIN   tmp_fcrd2_$login_admin defeitos ON defeitos.defeito = tbl_defeito.defeito
			ORDER BY defeitos.ocorrencia DESC " ;

	if($login_fabrica==24){
		$sql = "
			SELECT tbl_os.os 
			INTO TEMP tmp_fcrd_$login_admin
			FROM tbl_os_extra
			JOIN tbl_extrato USING (extrato)
			JOIN tbl_os ON tbl_os_extra.os = tbl_os.os
			JOIN tbl_posto ON tbl_os.posto = tbl_posto.posto
			JOIN tbl_produto ON tbl_os.produto = tbl_produto.produto
			WHERE tbl_extrato.fabrica = $login_fabrica
			AND   $tipo_data BETWEEN '$data_inicial 00:00:00' AND '$data_final 23:59:59' 
			AND   tbl_os.excluida IS NOT TRUE
			$cond_1
			$cond_3
			$cond_4
			$cond_5;

			CREATE INDEX tmp_fcrd_os_$login_admin ON tmp_fcrd_$login_admin(os);

			SELECT tbl_os_item.defeito, COUNT(*) AS ocorrencia
			INTO TEMP tmp_fcrd2_$login_admin
			FROM tbl_os_item
			JOIN tbl_os_produto USING (os_produto)
			JOIN tmp_fcrd_$login_admin fcr ON tbl_os_produto.os = fcr.os
			WHERE    $cond_2
			GROUP BY tbl_os_item.defeito;

			CREATE INDEX tmp_fcrd2_defeito_$login_admin ON tmp_fcrd2_$login_admin(defeito);

			SELECT tbl_defeito.descricao AS defeito_descricao, defeitos.ocorrencia
			FROM tbl_defeito
			JOIN   tmp_fcrd2_$login_admin defeitos ON defeitos.defeito = tbl_defeito.defeito
			ORDER BY defeitos.ocorrencia DESC " ;
	}
	if($login_fabrica==6){
		$sql = "
				SELECT tbl_os.os
				INTO TEMP tmp_fcrd_$login_admin
				FROM tbl_os_extra
				JOIN tbl_extrato USING (extrato)
				JOIN tbl_os      ON tbl_os_extra.os = tbl_os.os
				JOIN tbl_posto   ON tbl_os.posto = tbl_posto.posto
				JOIN tbl_produto ON tbl_os.produto = tbl_produto.produto
				WHERE tbl_extrato.fabrica = $login_fabrica
				AND   tbl_extrato.data_geracao BETWEEN '$data_inicial 00:00:00' AND '$data_final 23:59:59' 
				AND   tbl_os.excluida IS NOT TRUE
				AND   tbl_os.produto = $produto
				$cond_3
				$cond_4
				$cond_5;

				CREATE INDEX tmp_fcrd_os_$login_admin ON tmp_fcrd_$login_admin(os);

				SELECT tbl_os_item.defeito, COUNT(*) AS ocorrencia
				INTO TEMP tmp_fcrd2_$login_admin
				FROM tbl_os_item
				JOIN tbl_os_produto USING (os_produto)
				JOIN  tmp_fcrd_$login_admin fcr ON tbl_os_produto.os = fcr.os
				WHERE  $cond_2
				GROUP BY tbl_os_item.defeito;

				CREATE INDEX tmp_fcrd2_defeito_$login_admin ON tmp_fcrd2_$login_admin(defeito);

				SELECT tbl_defeito.descricao AS defeito_descricao, defeitos.ocorrencia
				FROM tbl_defeito
				JOIN tmp_fcrd2_$login_admin defeitos ON defeitos.defeito = tbl_defeito.defeito
				ORDER BY defeitos.ocorrencia DESC ";

		if($tipo_pesquisa=="data_abertura"){
			$sql = "
					SELECT tbl_os.os 
					INTO TEMP tmp_fcrd_$login_admin
					FROM tbl_os 
					JOIN tbl_posto ON tbl_os.posto = tbl_posto.posto
					JOIN tbl_produto ON tbl_os.produto = tbl_produto.produto
					WHERE tbl_os.fabrica = $login_fabrica
					AND tbl_os.data_abertura BETWEEN '$data_inicial' AND '$data_final' 
					AND tbl_os.excluida IS NOT TRUE
					AND   tbl_os.produto = $produto
					$cond_3
					$cond_4
					$cond_5;

					CREATE INDEX tmp_fcrd_os_$login_admin ON tmp_fcrd_$login_admin(os);

					SELECT tbl_os_item.defeito, COUNT(*) AS ocorrencia
					INTO TEMP tmp_fcrd2_$login_admin
					FROM tbl_os_item
					JOIN tbl_os_produto USING (os_produto)
					JOIN tmp_fcrd_$login_admin fcr ON tbl_os_produto.os = fcr.os
					WHERE  $cond_2
					GROUP BY tbl_os_item.defeito;

					CREATE INDEX tmp_fcrd2_defeito_$login_admin ON tmp_fcrd2_$login_admin(defeito);

					SELECT tbl_defeito.descricao AS defeito_descricao, defeitos.ocorrencia
					FROM tbl_defeito
					JOIN tmp_fcrd2_$login_admin defeitos ON defeitos.defeito = tbl_defeito.defeito
					ORDER BY defeitos.ocorrencia DESC ";
		}
	}
	//echo nl2br($sql);
	$res = pg_exec($con, $sql);
?>
<BODY>
<TABLE WIDTH = '600' align = 'center'>
	<TR>
		<TD class='titPreto14'><B><? echo $title; ?></B></TD>
	</TR>
	<TR>
		<TD class='titDatas12'><? echo $aux_data_inicial." até ".$aux_data_final ?></TD>
	</TR>
	<TR>
		<TD class='titPreto14'>&nbsp;</TD>
	</TR>
	<table align = 'center'>
		<?if($btnacao<>'filtrar'){?>
		<TR>
<?if(strlen($produto) > 0) {?>
			<TD HEIGHT='25' class='titPreto12' align='center'>PRODUTO: <b><? echo $referencia_produto ." - ". $descricao_produto; ?></b></TD>
<? } elseif(strlen($defeito_constatado)>0) { ?>
			<TD HEIGHT='25' class='titPreto12' align='center'>DEFEITO CONSTATADO: <b><? echo $descricao_defeito_constatado; ?></b></TD>
<? } ?>
		<?}?>
		<TR>
			<TD HEIGHT='25' class='titPreto12' align = 'center'>PEÇA: <b><? echo "$referencia_peca - $descricao_peca</b>" . $mostra_preco; ?></TD>
		</TR>
	</table>
</TABLE>
<BR>
<?
	echo "<TABLE width='600' cellspacing='0' cellpadding='2' border='0' align = 'center'>";
	echo "<TR>";
	echo "<TD class='titChamada10'>DEFEITO</TD>";
	echo "<TD class='titChamada10'>OCORRÊNCIAS</TD>";
	echo "<TD class='titChamada10'>%</TD>";
	echo "</TR>";
	if(pg_numrows($res) > 0){
	
		for ($x = 0; $x < pg_numrows($res); $x++) {
			$total_ocorrencia = $total_ocorrencia + pg_result($res,$x,ocorrencia);
			//$total_mobra      = $total_mobra + pg_result($res,$x,soma_mobra);
			//$total_peca       = $total_peca + pg_result($res,$x,soma_peca);
			//$total_geral      = $total_geral + pg_result($res,$x,soma_total);
		}
	
		for($i=0; $i<pg_numrows($res); $i++){
			$defeito    = pg_result($res,$i,defeito_descricao);
			$ocorrencia = pg_result($res,$i,ocorrencia);
	
			if ($total_ocorrencia > 0) {
				$porcentagem = (($ocorrencia * 100) / $total_ocorrencia);
			}
	
			$cor = '#ffffee';
			if ($i % 2 == 0) $cor = '#eeffff';
	
			echo "<TR bgcolor='$cor' style='font-size: 10px ; font-face: verdana'>";
			echo "	<TD align='left'>$defeito </TD>";
			echo "	<TD align='center'>$ocorrencia</TD>";
			echo "	<TD align='center'>". number_format($porcentagem,2,",",".") ."%</TD>";
			echo "</TR>";
		}
	}
}
if($login_fabrica == 20){

	if (strlen ($tipo_atendimento)> 0 ) $cond1 = " AND tbl_os.tipo_atendimento = $tipo_atendimento";
	if (strlen ($familia)         > 0 ) $cond2 = " AND tbl_produto.familia     = $familia ";
	if (strlen ($origem)          > 0 ) $cond3 = " AND tbl_produto.origem      = '$origem' ";
	if (strlen ($aux)             > 0 ) $cond4 = " AND substr(tbl_os.serie,0,4) IN ($aux)";

	if (strlen ($pais)            > 0 ) $cond_6 = " tbl_posto.pais     = '$pais' ";

	if($login_fabrica==1){
		$xsolucao     = " tbl_solucao.descricao as s_descricao ";
		$join_solucao = " LEFT JOIN tbl_solucao ON tbl_solucao.solucao = tbl_os.solucao_os ";
	}else{
		$xsolucao     = " tbl_servico_realizado.descricao as s_descricao ";
		$join_solucao = " LEFT JOIN tbl_servico_realizado ON tbl_servico_realizado.servico_realizado=tbl_os.solucao_os ";
	}

	$sql = "SELECT  
		$xsolucao,
		tbl_causa_defeito.codigo           AS c_codigo   ,
		tbl_causa_defeito.descricao        AS c_descricao,
		count(*)                           AS ocorrencia ,
		sum (tbl_os_item.preco * tbl_os_item.qtde) AS total
	FROM tbl_os 
	JOIN tbl_os_produto USING (os)
	JOIN tbl_os_item    USING (os_produto)
	$join_solucao 
	LEFT JOIN tbl_causa_defeito     ON tbl_os.causa_defeito     = tbl_causa_defeito.causa_defeito
	JOIN   (
		SELECT tbl_os.os
		FROM tbl_os_extra
		JOIN tbl_extrato USING (extrato)
		JOIN tbl_extrato_extra ON tbl_extrato_extra.extrato = tbl_extrato.extrato
		JOIN tbl_os ON tbl_os_extra.os = tbl_os.os
		JOIN tbl_posto ON tbl_os.posto = tbl_posto.posto
		JOIN tbl_produto ON tbl_os.produto = tbl_produto.produto
		WHERE tbl_extrato.fabrica = $login_fabrica
		AND   $tipo_data BETWEEN '$data_inicial 00:00:00' AND '$data_final 23:59:59'
		AND   tbl_os.excluida IS NOT TRUE ";

	if($btnacao<>'filtrar')$sql .= " AND   tbl_os.produto = $produto ";
	$sql .= "				AND   $cond_1 
						AND   $cond_3
						AND   $cond_4
						AND   $cond_5
						AND   $cond_6
						$cond1 $cond2 $cond3 $cond4
	)fcr ON tbl_os_produto.os = fcr.os
	WHERE   $cond_2
	group by 
		tbl_servico_realizado.descricao    ,
		tbl_causa_defeito.codigo           ,
		tbl_causa_defeito.descricao
	ORDER BY ocorrencia DESC,total DESC" ;

if($btnacao=='filtrar'){
	$sql = "SELECT
			$xsolucao,
			tbl_causa_defeito.codigo    AS c_codigo   , 
			tbl_causa_defeito.descricao AS c_descricao, 
			SUM(tbl_os_item.qtde)       AS ocorrencia , 
			SUM(tbl_os_item.preco)      AS total
		FROM tbl_os
		JOIN tbl_produto    USING (produto)
		JOIN tbl_os_produto USING (os)
		JOIN tbl_os_item    USING (os_produto)
		JOIN tbl_posto      USING (posto)
		JOIN tbl_os_extra   USING (os)
		JOIN tbl_extrato    USING (extrato)
		JOIN tbl_extrato_extra      ON tbl_extrato_extra.extrato = tbl_extrato.extrato
		$join_solucao 
		LEFT JOIN tbl_causa_defeito ON tbl_os.causa_defeito      = tbl_causa_defeito.causa_defeito
		WHERE tbl_extrato_extra.exportado BETWEEN '$data_inicial 00:00:00' AND '$data_final 23:59:59'
		AND $cond_2
		$cond1 $cond2 $cond3 $cond4
		GROUP BY
			tbl_servico_realizado.descricao ,
			tbl_causa_defeito.codigo ,
			tbl_causa_defeito.descricao
		ORDER BY ocorrencia DESC,total DESC";
}
	$res = pg_exec($con, $sql);
	if(pg_numrows($res) > 0){
		echo "<TABLE width='600' cellspacing='0' cellpadding='2' border='0' align = 'center'>";
		echo "<TR>";
		echo "<TD class='titChamada10'>IDENTIFICAÇÃO</TD>";
		echo "<TD class='titChamada10'>DEFEITO</TD>";
		echo "<TD class='titChamada10'>OCORRÊNCIAS</TD>";
		echo "<TD class='titChamada10'>%</TD>";
		echo "<TD class='titChamada10'>VALOR</TD>";
		echo "<TD class='titChamada10'>%</TD>";
		echo "</TR>";
		for ($x = 0; $x < pg_numrows($res); $x++) {
			$total_ocorrencia = $total_ocorrencia + pg_result($res,$x,ocorrencia);
			$total_final = $total_final + pg_result($res,$x,total);

		}
	
		for($i=0; $i<pg_num_rows($res); $i++){
			$s_defeito  = pg_fetch_result($res, $i, s_descricao);
			$c_codigo   = pg_fetch_result($res, $i, c_codigo);
			$c_defeito  = pg_fetch_result($res, $i, c_descricao);
			$ocorrencia = pg_fetch_result($res, $i, ocorrencia);
			$total      = pg_fetch_result($res, $i, total);

			if ($total_ocorrencia > 0) {
				$porcentagem = (($ocorrencia * 100) / $total_ocorrencia);
			}
			if ($total_ocorrencia > 0) {
				$porcentagem2 = (($total * 100) / $total_final);
			}
	
			$cor = '#ffffee';
			if ($i % 2 == 0) $cor = '#eeffff';
	
			echo "<TR bgcolor='$cor' style='font-size: 10px ; font-face: verdana'>";
			echo "	<TD align='left'>$s_defeito </TD>";
			echo "	<TD align='left'>$c_codigo $c_defeito </TD>";
			echo "	<TD align='center'>$ocorrencia</TD>";
			echo "	<TD align='center'>". number_format($porcentagem,2,",",".") ."%</TD>";
			echo "	<TD align='center'>". number_format($total,2,",",".")."</TD>";
			echo "	<TD align='center'>". number_format($porcentagem2,2,",",".") ."%</TD>";
			echo "</TR>";
		}
	}else{
		echo "<div>Nenhum resultado encontrado</div>";
	}
}
?>
	</TABLE>
</BODY>
</HTML>
