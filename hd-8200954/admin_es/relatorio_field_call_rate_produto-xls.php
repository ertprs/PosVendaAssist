<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'includes/funcoes.php';

$admin_privilegios="gerencia";
include 'autentica_admin.php';

$data_inicial = trim($_GET["data_inicial"]);
$data_final   = trim($_GET["data_final"]);
$linha        = trim($_GET["linha"]);
$estado       = trim($_GET["estado"]);
$criterio     = trim($_GET["criterio"]);

if (strlen($data_inicial) == 0) $erro .= "Favor informar a data inicial para pesquisa<br>";

if (strlen($erro) == 0) {
	$fnc            = @pg_exec($con,"SELECT fnc_formata_data('$data_inicial')");
	
	if (strlen ( pg_errormessage ($con) ) > 0) $erro = pg_errormessage ($con) ;
	
	if (strlen($erro) == 0) $aux_data_inicial = @pg_result ($fnc,0,0);
}

if (strlen($erro) == 0) {
	if (strlen($data_final) == 0) $erro .= "Favor informar a data final para pesquisa<br>";
	
	if (strlen($erro) == 0) {
		$fnc            = @pg_exec($con,"SELECT fnc_formata_data('$data_final')");
		
		if (strlen ( pg_errormessage ($con) ) > 0) $erro = pg_errormessage ($con) ;
		
		if (strlen($erro) == 0) $aux_data_final = @pg_result ($fnc,0,0);
	}
}

$layout_menu = "gerencia";
$title = "REPORTE - FIELD CALL-RATE : LÍNEA DE PRODUCTO";

include "cabecalho.php";

?>

<style type="text/css">

.menu_top {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: x-small;
	font-weight: bold;
	border: 1px solid;
	color:#ffffff;
	background-color: #596D9B
}

.table_line {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	border: 0px solid;
	background-color: #D9E2EF
}

.table_line2 {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
}

</style>

<?
//relatorio acertado para bosch
if($login_fabrica == 20 OR $login_fabrica == 15){
	$cond_1 = "1=1";
	$cond_2 = "1=1";
	$cond_3 = "1=1";
	if (strlen ($linha)  > 0) $cond_1 = " tbl_produto.linha = $linha ";
	if (strlen ($estado) > 0) $cond_2 = " tbl_posto.estado  = '$estado' ";
	if (strlen ($posto)  > 0) $cond_3 = " tbl_posto.posto   = $posto ";



	$sql = "SELECT 	tbl_produto.produto, 
					tbl_produto.ativo, 
					tbl_produto.referencia,  
					tbl_produto.descricao AS produto_descricao, 
					tbl_produto_idioma.descricao, 
					fcr1.qtde AS ocorrencia, 
					tbl_produto.familia, 
					tbl_produto.linha
			FROM tbl_produto
			LEFT JOIN tbl_produto_idioma using(produto)
			JOIN (
					SELECT tbl_os.produto, COUNT(*) AS qtde
					FROM tbl_os
					JOIN (
							SELECT tbl_os_extra.os , 
								(SELECT status_os FROM tbl_os_status WHERE tbl_os_status.os = tbl_os_extra.os ORDER BY data DESC LIMIT 1) AS status
							FROM tbl_os_extra
							JOIN tbl_extrato       USING (extrato)
							JOIN tbl_extrato_extra USING (extrato)
							JOIN tbl_posto         USING (posto)
							WHERE tbl_extrato.fabrica = $login_fabrica
							AND   tbl_posto.pais      = '$login_pais'
							AND   tbl_extrato.data_geracao BETWEEN '$aux_data_inicial' AND '$aux_data_final' 
						) fcr ON tbl_os.os = fcr.os
					JOIN tbl_posto ON tbl_os.posto = tbl_posto.posto 
					WHERE (fcr.status NOT IN (13,15) OR fcr.status IS NULL)
					AND tbl_os.excluida IS NOT TRUE
					AND tbl_posto.pais = '$login_pais'
					AND $cond_2
					AND $cond_3
					GROUP BY tbl_os.produto
				) fcr1 ON tbl_produto.produto = fcr1.produto
			WHERE $cond_1
			ORDER BY fcr1.qtde DESC " ;
//if ($ip == "201.71.54.144") { echo nl2br($sql); exit;}
}

	$res = pg_exec ($con,$sql);

	if (pg_numrows($res) > 0) {
		flush();
		
		echo "<br><br>";
		echo"<table width='600' border='0' cellspacing='2' cellpadding='2' align='center'>";
		echo"<tr>";
		echo "<td align='center'><font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>Aguarde, procesando archivo PostScript</font></td>";
		echo "</tr>";
		echo "</table>";
		
		flush();
		
		$data = date ("d/m/Y H:i:s");

		echo `rm /tmp/assist/field-call-rate-serie-$login_fabrica.xls`;

		$fp = fopen ("/tmp/assist/field-call-rate-serie-$login_fabrica.html","w");

		fputs ($fp,"<html>");
		fputs ($fp,"<head>");
		fputs ($fp,"<title>FIELD CALL-RATE - $data");
		fputs ($fp,"</title>");
		fputs ($fp,"<meta name='Author' content='TELECONTROL NETWORKING LTDA'>");
		fputs ($fp,"</head>");
		fputs ($fp,"<body>");
		
		fputs ($fp,"<table width='100%' align='left' border='1' cellpadding='2' cellspacing='2'>");
		fputs ($fp,"<tr>");
		fputs ($fp,"<td bgcolor='#E9F3F3' align='center'><b>REFERENCIA PRODUCTO</b></td>");
		fputs ($fp,"<td bgcolor='#E9F3F3' align='center'><b>DESCRIPCIÓN PRODUCTO</b></td>");
		fputs ($fp,"<td bgcolor='#E9F3F3' align='center'><b>REFERENCIA PIEZA</b></td>");
		fputs ($fp,"<td bgcolor='#E9F3F3' align='center'><b>DESCRIPCIÓN PIEZA</b></td>");
		fputs ($fp,"<td bgcolor='#E9F3F3' align='center'><b>ORDEN SERVICIO</b></td>");
		fputs ($fp,"<td bgcolor='#E9F3F3' align='center'><b>SÉRIE</b></td>");
		fputs ($fp,"<td bgcolor='#E9F3F3' align='center'><b>LÍNEA</b></td>");
		fputs ($fp,"<td bgcolor='#E9F3F3' align='center'><b>DEFECTO CONSTATADO</b></td>");
		fputs ($fp,"</tr>");
		
		for ($i=0; $i<pg_numrows($res); $i++){
			$referencia = trim(pg_result($res,$i,referencia));
			$produto_descricao = trim(pg_result($res,$i,produto_descricao));
			if (strlen($referencia) == 12) $referencia = MW_MascaraString($referencia,'999.999.999.999');
			if (strlen($referencia) == 9)  $referencia = MW_MascaraString($referencia,'999.999.999');
			
			$descricao  = trim(pg_result($res,$i,descricao));
			$produto    = trim(pg_result($res,$i,produto));
			if (strlen($linha) > 0) $linha = trim(pg_result($res,$i,linha));
			$ocorrencia = trim(pg_result($res,$i,ocorrencia));


			if (strlen($descricao)==0){
				$descricao = $produto_descricao;
			}

			// SQL RETIRADO PARA MELHORAR PERFORMANCE
			$sql = "SELECT  tbl_produto.produto                                             ,
							tbl_produto.referencia          AS produto_referencia           ,
							tbl_produto.descricao           AS produto_descricao            ,
							tbl_peca.peca                                                   ,
							tbl_peca.referencia             AS peca_referencia              ,
							tbl_peca.descricao              AS peca_descricao               ,
							tbl_os.sua_os                                                   ,
							tbl_os.os                                                       ,
							tbl_os.serie                                                    ,
							tbl_os_item.pedido                                              ,
							tbl_linha.nome                  AS linha_nome                   ,
							tbl_os.consumidor_estado                                        ,
							tbl_posto.estado                AS posto_estado                 ,
							tbl_defeito_constatado_idioma.descricao AS defeito_constatado_descricao 
					FROM    tbl_os
					JOIN tbl_os_extra using(os)
					JOIN tbl_extrato       USING (extrato)
					JOIN tbl_extrato_extra USING (extrato)
					JOIN    tbl_posto           ON tbl_os.posto              = tbl_posto.posto
					LEFT JOIN    tbl_os_produto ON tbl_os.os                 = tbl_os_produto.os
					LEFT JOIN    tbl_os_item    ON tbl_os_produto.os_produto = tbl_os_item.os_produto
					LEFT JOIN    tbl_peca       ON tbl_os_item.peca          = tbl_peca.peca
					LEFT JOIN    tbl_produto    ON tbl_os.produto            = tbl_produto.produto
					LEFT JOIN    tbl_linha      ON tbl_linha.linha           = tbl_produto.linha
					LEFT JOIN    tbl_defeito_constatado_idioma ON tbl_defeito_constatado_idioma.defeito_constatado = tbl_os.defeito_constatado
					WHERE tbl_extrato.fabrica = $login_fabrica
					AND   tbl_posto.pais      = '$login_pais'
					AND   tbl_extrato.data_geracao BETWEEN '$aux_data_inicial' AND '$aux_data_final' 
					ORDER BY tbl_produto.referencia, tbl_os.os ";

			$res = pg_exec ($con,$sql);
//if ($ip == "201.43.11.131") echo $sql; exit;
			for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {

				$produto           = pg_result ($res,$i,produto)          ;
				$produto_descricao = pg_result ($res,$i,produto_descricao);
				$peca              = pg_result ($res,$i,peca)             ;
				$descricao         = pg_result ($res,$i,peca_descricao)   ;


				$sql2 = "SELECT descricao FROM tbl_produto_idioma WHERE idioma = 'ES' and produto = $produto";
				$res2 = @pg_exec ($con,$sql2);
				if (@pg_numrows($res2) > 0) $produto_descricao = @pg_result ($res2,0,descricao);


				$sql2 = "SELECT descricao FROM tbl_peca_idioma WHERE idioma = 'ES' and peca = $peca";
				$res2 = @pg_exec ($con,$sql2);
				if (@pg_numrows($res2) > 0) $descricao = @pg_result ($res2,0,descricao);

				$os = pg_result ($res,$i,sua_os) ;
				if (strlen ($os) == 0) $os = pg_result ($res,$i,os) ;

				$estado = pg_result ($res,$i,consumidor_estado) ;
				if (strlen ($estado) == 0) $estado = pg_result ($res,$i,posto_estado) ;

				fputs ($fp,"<tr>");
				fputs ($fp,"<td bgcolor='$cor' align='center'>&nbsp;" . pg_result ($res,$i,produto_referencia) . "&nbsp;</td>");
				fputs ($fp,"<td bgcolor='$cor' align='left' nowrap>" . $produto_descricao . "</td>");
				fputs ($fp,"<td bgcolor='$cor' align='center'>&nbsp;" . pg_result ($res,$i,peca_referencia) . "&nbsp;</td>");
				fputs ($fp,"<td bgcolor='$cor' align='left' nowrap>" . $descricao . "</td>");
				fputs ($fp,"<td bgcolor='$cor' align='center'>&nbsp;".$os."&nbsp;</td>");
				fputs ($fp,"<td bgcolor='$cor' align='center'>&nbsp;" . pg_result ($res,$i,serie) . "&nbsp;</td>");
				fputs ($fp,"<td bgcolor='$cor' align='center'>" . pg_result ($res,$i,linha_nome) . "</td>");
				fputs ($fp,"<td bgcolor='$cor' align='center'>" . pg_result ($res,$i,defeito_constatado_descricao) . "</td>");
				fputs ($fp,"</tr>");
			}
			
			fputs ($fp,"</table>");
			
			fputs ($fp,"</body>");
			fputs ($fp,"</html>");
			fclose ($fp);
		}
	}

	$data = date("Y-m-d").".".date("H-i-s");

	echo `htmldoc --webpage --size 297x210mm --fontsize 11 --left 8mm -f /www/assist/www/admin_es/xls/field-call-rate-serie-$login_fabrica.$data.xls /tmp/assist/field-call-rate-serie-$login_fabrica.html`;
	
	echo"<table width='600' border='0' cellspacing='2' cellpadding='2' align='center'>";
	echo"<tr>";
	echo "<td align='center'<font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>Click aquí para hacer el </font><a href='xls/field-call-rate-serie-$login_fabrica.$data.xls'><font face='Arial, Verdana, Times, Sans' size='2' color='#0000FF'>download del archivo en EXCEL</font></a>.<br><font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>Usted puede ver, imprimir y guardar la tabla para consultas off-line.</font></td>";
	echo "</tr>";
	echo "</table>";


?>

<p>

<? include "rodape.php" ?>
