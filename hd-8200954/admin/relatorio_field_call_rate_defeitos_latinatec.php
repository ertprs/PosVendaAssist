<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';

$btnacao            = $_GET['btnacao'];
$data_inicial       = $_GET['data_inicial'];
$data_final         = $_GET['data_final'];
$produto            = $_GET['produto'];
$peca               = $_GET['peca'];
$linha              = $_GET['linha'];
$estado             = $_GET['estado'];
$familia            = $_GET['familia'];
$tipo               = $_GET['tipo'];
$consumidor_revenda = $_GET['consumidor_revenda'];

$sql = "SELECT descricao FROM tbl_produto WHERE produto = $produto";
$res = @pg_exec($con,$sql);
$descricao_produto = @pg_result($res,0,descricao);

$sql = "SELECT referencia,descricao FROM tbl_peca WHERE peca = $peca";
$res = pg_exec($con,$sql);
$referencia_peca = pg_result($res,0,referencia);
$descricao_peca = pg_result($res,0,descricao);
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
			<TD HEIGHT='25' class='titPreto12' align = 'center'>PRODUTO: <b><? echo $descricao_produto; ?></b></TD>
		</TR>
		<?}?>
		<TR>
			<TD HEIGHT='25' class='titPreto12' align = 'center'>PEÇA: <b><? echo "$referencia_peca - $descricao_peca"; ?></b></TD>
		</TR>
	</table>
</TABLE>
<BR>



<?
$sql = "SELECT  vw_quebra_defeito.fabrica   ,
				vw_quebra_defeito.produto   ,
				vw_quebra_defeito.peca      ,
				vw_quebra_defeito.referencia,
				vw_quebra_defeito.descricao ,
				vw_quebra_defeito.status_os , ";

if (strlen($linha) > 0)  $sql .= "vw_quebra_defeito.linha, ";
if (strlen($estado) > 0) $sql .= "vw_quebra_defeito.estado, ";

$sql .= "		vw_quebra_defeito.defeito_descricao,
				sum(vw_quebra_defeito.ocorrencia) AS ocorrencia
		FROM (
				SELECT	tbl_os.fabrica     ,
						tbl_produto.produto, ";

if (strlen($linha) > 0)  $sql .= "tbl_linha.linha, ";
if (strlen($estado) > 0) $sql .= "tbl_posto.estado, ";



$sql .= "				tbl_peca.peca                                         ,
						tbl_peca.referencia                                   ,";

	if($login_fabrica == 20 and $pais !='BR'){
		$sql .=" tbl_peca_idioma.descricao AS descricao_espanhol, ";
		$join_pc_idioma="LEFT JOIN tbl_peca_idioma on tbl_peca_idioma.peca = tbl_peca.peca";
	}else{
		$sql .=" tbl_peca.descricao, ";
		$join_pc_idioma="";
	}



	$sql .="
						tbl_peca.descricao                                    ,
						tbl_os_status.status_os                               ,
						count(tbl_os_item.peca)                  AS ocorrencia,
						date_trunc('day', tbl_os.data_digitacao) AS digitada  ,
						date_trunc('day', tbl_os.finalizada)     AS finalizada,
						tbl_defeito.descricao                    AS defeito_descricao
				FROM    tbl_os
				JOIN    tbl_os_produto   ON tbl_os_produto.os      = tbl_os.os
				JOIN    tbl_os_item      ON tbl_os_item.os_produto = tbl_os_produto.os_produto
				JOIN    tbl_defeito      ON tbl_defeito.defeito    = tbl_os_item.defeito
				JOIN    tbl_posto        ON tbl_posto.posto        = tbl_os.posto
				JOIN    tbl_produto      ON tbl_produto.produto    = tbl_os_produto.produto
										AND tbl_os_produto.os      = tbl_os.os
				JOIN    tbl_linha        ON tbl_linha.linha        = tbl_produto.linha 
				JOIN    tbl_peca         ON tbl_os_item.peca       = tbl_peca.peca 
				$join_pc_idioma
				LEFT JOIN tbl_os_status  ON tbl_os_status.os       = tbl_os.os
				GROUP BY    tbl_os.fabrica     ,
							tbl_os_item.defeito,
							tbl_produto.produto, ";

if (strlen($linha) > 0)  $sql .= "	tbl_linha.linha, ";
if (strlen($estado) > 0) $sql .= "	tbl_posto.estado, ";

$sql .= "					tbl_peca.peca                           ,
							tbl_peca.referencia                     ,
							tbl_peca.descricao                      ,
							tbl_os_status.status_os                 ,
							date_trunc('day', tbl_os.data_digitacao),
							date_trunc('day', tbl_os.finalizada)    ,
							tbl_defeito.descricao
		) AS vw_quebra_defeito
		WHERE vw_quebra_defeito.digitada BETWEEN '$data_inicial' AND '$data_final' 
		AND  (vw_quebra_defeito.status_os NOT IN (13,15) OR vw_quebra_defeito.status_os IS NULL)
		AND   vw_quebra_defeito.fabrica = $login_fabrica ";

if (strlen($linha) > 0)  $sql .= "AND vw_quebra_defeito.linha   = '$linha' ";
if (strlen($estado) > 0) $sql .= "AND vw_quebra_defeito.estado  = '$estado' ";

$sql .= "	AND vw_quebra_defeito.peca = $peca
			AND vw_quebra_defeito.produto = $produto
		GROUP BY    vw_quebra_defeito.fabrica   ,
					vw_quebra_defeito.produto   ,
					vw_quebra_defeito.peca      ,
					vw_quebra_defeito.referencia,
					vw_quebra_defeito.descricao ,
					vw_quebra_defeito.status_os , ";

if (strlen($linha) > 0)  $sql .= "vw_quebra_defeito.linha, ";
if (strlen($estado) > 0) $sql .= "vw_quebra_defeito.estado, ";

$sql .= "vw_quebra_defeito.defeito_descricao
		ORDER BY sum(vw_quebra_defeito.ocorrencia) DESC;";


$cond_1 = "1=1";
$cond_2 = "1=1";
$cond_3 = "1=1";
$cond_4 = "1=1";
$cond_5 = "1=1";
$cond_6 = "1=1";
if (strlen ($produto) > 0) $cond_1 = " tbl_os.produto      = $produto ";
if (strlen ($peca)    > 0) $cond_2 = " tbl_os_item.peca    = $peca ";
if (strlen ($posto)   > 0) $cond_3 = " tbl_posto.posto     = $posto ";
if (strlen ($estado)  > 0) $cond_4 = " tbl_posto.estado    = '$estado' ";
if (strlen ($consumidor_revenda)  > 0) $cond_5 = " tbl_os.consumidor_revenda = '$consumidor_revenda' ";
if (strlen ($pais)   > 0) $cond_6 = " tbl_posto.pais     = '$pais' ";

if($login_fabrica == 20)$tipo_data = " tbl_extrato_extra.exportado ";
else                    $tipo_data = " tbl_extrato.data_geracao ";

if($login_fabrica <> 20){
echo "<TABLE width='600' cellspacing='0' cellpadding='2' border='0' align = 'center'>";
echo "<TR>";
echo "<TD class='titChamada10'>DEFEITO</TD>";
echo "<TD class='titChamada10'>OCORRÊNCIAS</TD>";
echo "<TD class='titChamada10'>%</TD>";
echo "</TR>";



	$sql = "SELECT tbl_defeito.descricao AS defeito_descricao, defeitos.ocorrencia
			FROM tbl_defeito
			JOIN   (SELECT tbl_os_item.defeito, COUNT(*) AS ocorrencia
					FROM tbl_os_item
					JOIN tbl_os_produto USING (os_produto)
					JOIN   (SELECT tbl_os.os , 
							(SELECT status_os FROM tbl_os_status WHERE tbl_os_status.os = tbl_os_extra.os ORDER BY data DESC LIMIT 1) AS status
							FROM tbl_os_extra
							JOIN tbl_extrato USING (extrato)
							JOIN tbl_extrato_extra ON tbl_extrato_extra.extrato = tbl_extrato.extrato
							JOIN tbl_os ON tbl_os_extra.os = tbl_os.os
							JOIN tbl_posto ON tbl_os.posto = tbl_posto.posto
							JOIN tbl_produto ON tbl_os.produto = tbl_produto.produto
							WHERE tbl_extrato.fabrica = $login_fabrica
							AND   $tipo_data BETWEEN '$data_inicial' AND '$data_final' ";
	if ($login_fabrica == 14) $sql .= "AND   tbl_extrato.liberado IS NOT NULL ";
	$sql .="AND   tbl_os.excluida IS NOT TRUE
							AND   tbl_os.produto = $produto
							AND   $cond_1
							AND   $cond_3
							AND   $cond_4
							AND $cond_5
					) fcr ON tbl_os_produto.os = fcr.os
					WHERE (fcr.status NOT IN (13,15) OR fcr.status IS NULL)
					AND   $cond_2
					GROUP BY tbl_os_item.defeito
			) defeitos ON defeitos.defeito = tbl_defeito.defeito
			ORDER BY defeitos.ocorrencia DESC " ;
	
	//if($ip=="201.68.13.116") echo $sql; exit;
	//if ($ip == "201.0.9.216") { echo nl2br($sql); }
	
	if($login_fabrica==24){
		$sql = "SELECT tbl_defeito.descricao AS defeito_descricao, defeitos.ocorrencia
			FROM tbl_defeito
			JOIN   (SELECT tbl_os_item.defeito, COUNT(*) AS ocorrencia
					FROM tbl_os_item
					JOIN tbl_os_produto USING (os_produto)
					JOIN   (SELECT tbl_os.os 
							FROM tbl_os_extra
							JOIN tbl_extrato USING (extrato)
							JOIN tbl_extrato_extra ON tbl_extrato_extra.extrato = tbl_extrato.extrato
							JOIN tbl_os ON tbl_os_extra.os = tbl_os.os
							JOIN tbl_posto ON tbl_os.posto = tbl_posto.posto
							JOIN tbl_produto ON tbl_os.produto = tbl_produto.produto
							WHERE tbl_extrato.fabrica = $login_fabrica
							AND   $tipo_data BETWEEN '$data_inicial 00:00:00' AND '$data_final 23:59:59' 
							AND   tbl_os.excluida IS NOT TRUE
							AND   tbl_os.produto = $produto
							AND   $cond_1
							AND   $cond_3
							AND   $cond_4
							AND   $cond_5
					) fcr ON tbl_os_produto.os = fcr.os
					WHERE    $cond_2
					GROUP BY tbl_os_item.defeito
			) defeitos ON defeitos.defeito = tbl_defeito.defeito
			ORDER BY defeitos.ocorrencia DESC " ;
	
	}


	$res = pg_exec($con, $sql);
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
IF($login_fabrica == 20){

	if (strlen ($tipo_atendimento)> 0 ) $cond1 = " AND tbl_os.tipo_atendimento = $tipo_atendimento";
	if (strlen ($familia)         > 0 ) $cond2 = " AND tbl_produto.familia     = $familia ";
	if (strlen ($origem)          > 0 ) $cond3 = " AND tbl_produto.origem      = '$origem' ";
	if (strlen ($aux)             > 0 ) $cond4 = " AND substr(tbl_os.serie,0,4) IN ($aux)";

	$sql = "SELECT  
		tbl_servico_realizado.descricao    AS s_descricao,
		tbl_causa_defeito.codigo           AS c_codigo   ,
		tbl_causa_defeito.descricao        AS c_descricao,
		count(*)                           AS ocorrencia ,
		sum (tbl_os_item.preco * tbl_os_item.qtde) AS total
	FROM tbl_os 
	JOIN tbl_os_produto USING (os)
	JOIN tbl_os_item    USING (os_produto)
	LEFT JOIN tbl_servico_realizado ON tbl_os.solucao_os        = tbl_servico_realizado.servico_realizado
	LEFT JOIN tbl_causa_defeito     ON tbl_os.causa_defeito     = tbl_causa_defeito.causa_defeito
	JOIN   (
		SELECT tbl_os.os , 
		      (SELECT status_os FROM tbl_os_status WHERE tbl_os_status.os = tbl_os_extra.os ORDER BY data DESC LIMIT 1) AS status
		FROM tbl_os_extra
		JOIN tbl_extrato USING (extrato)
		JOIN tbl_extrato_extra ON tbl_extrato_extra.extrato = tbl_extrato.extrato
		JOIN tbl_os ON tbl_os_extra.os = tbl_os.os
		JOIN tbl_posto ON tbl_os.posto = tbl_posto.posto
		JOIN tbl_produto ON tbl_os.produto = tbl_produto.produto
		WHERE tbl_extrato.fabrica = $login_fabrica
		AND   $tipo_data BETWEEN '$data_inicial' AND '$data_final'
		AND   tbl_os.excluida IS NOT TRUE ";

	if($btnacao<>'filtrar')$sql .= " AND   tbl_os.produto = $produto ";
	$sql .= "				AND   $cond_1 
						AND   $cond_3
						AND   $cond_4
						AND   $cond_5
						AND   $cond_6
						$cond1 $cond2 $cond3 $cond4
	)fcr ON tbl_os_produto.os = fcr.os
	WHERE (fcr.status NOT IN (13,15) OR fcr.status IS NULL)
	AND   $cond_2
	group by 
		tbl_servico_realizado.descricao    ,
		tbl_causa_defeito.codigo           ,
		tbl_causa_defeito.descricao
	ORDER BY ocorrencia DESC,total DESC" ;
//if($ip=="189.18.85.78")echo nl2br($sql);

if($btnacao=='filtrar'){
	$sql = "SELECT
		tbl_servico_realizado.descricao AS s_descricao,
		tbl_causa_defeito.codigo AS c_codigo ,
		tbl_causa_defeito.descricao AS c_descricao,
		sum(tbl_os_item.qtde) AS ocorrencia ,
		sum (tbl_os_item.preco ) AS total
	FROM tbl_os
	JOIN tbl_produto  USING(produto)
	JOIN tbl_os_produto using(os)
	JOIN tbl_os_item  USING (os_produto)
	JOIN tbl_posto    USING(posto)
	JOIN tbl_os_extra USING(os)
	JOIN tbl_extrato  USING (extrato)
	JOIN tbl_extrato_extra          ON tbl_extrato_extra.extrato = tbl_extrato.extrato
	LEFT JOIN tbl_servico_realizado ON tbl_os.solucao_os         = tbl_servico_realizado.servico_realizado
	LEFT JOIN tbl_causa_defeito     ON tbl_os.causa_defeito      = tbl_causa_defeito.causa_defeito
	WHERE tbl_extrato_extra.exportado BETWEEN '2007-03-01' AND '2007-03-31'
	AND $cond_2
	AND $cond_6
	$cond1 $cond2 $cond3 $cond4
	GROUP BY
		tbl_servico_realizado.descricao ,
		tbl_causa_defeito.codigo ,
		tbl_causa_defeito.descricao
	ORDER BY ocorrencia DESC,total DESC";
}
	$res = pg_exec($con, $sql);
//echo "sql $sql";

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
	
		for($i=0; $i<pg_numrows($res); $i++){
			$s_defeito    = pg_result($res,$i,s_descricao);
			$c_codigo     = pg_result($res,$i,c_codigo);
			$c_defeito    = pg_result($res,$i,c_descricao);
			$ocorrencia = pg_result($res,$i,ocorrencia);
			$total = pg_result($res,$i,total);

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
	}
}




?>

</TABLE>
</BODY>
</HTML>
