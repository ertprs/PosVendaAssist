<?
include '../dbconfig.php';
include '../includes/dbconnect-inc.php';
include '../includes/funcoes.php';

$admin_privilegios="gerencia";
include 'autentica_admin.php';
include "../monitora.php";


if(isset($peca))$listar="ok";
$layout_menu = "gerencia";
$title = "RELATÓRIO - FIELD CALL-RATE : LINHA DE PRODUTO";

//include "cabecalho.php";

?>

<style type="text/css">
body,table{
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: x-small;
	margin: 0px,0px,0px,0px;
	padding:  0px,0px,0px,0px;
}
#Menu{border-bottom:#485989 1px solid;}
#Formulario {
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: x-small;
	font-weight: none;
	border: 1px solid #596D9B;
	color:#000000;
	background-color: #D9E2EF;
}
#Formulario tbody th{
	text-align: left;
	font-weight: bold;
}
#Formulario tbody td{
	text-align: left;
	font-weight: none;
}
#Formulario caption{
	color:#FFFFFF;
	text-align: center;
	font-weight: bold;
	background-image: url("imagens_admin/azul.gif");
}
#logo{
	BORDER-RIGHT: 1px ;
	BORDER-TOP: 1px ;
	BORDER-LEFT: 1px ;
	BORDER-BOTTOM: 1px ;
	position: absolute;
	top: 1px;
	right: 1px;
	z-index: 5;
}

</style>


<?
include "../javascript_pesquisas.php";
include "../javascript_calendario.php";
?>

<link rel="stylesheet" href="../js/blue/style.css" type="text/css" id="" media="print, projection, screen" />


<script type="text/javascript" src="../js/jquery.tablesorter.pack.js"></script>
<script>
$(document).ready(function(){
	$.tablesorter.defaults.widgets = ['zebra'];
	$("#relatorio").tablesorter();

});
$(document).ready(function(){
	$.tablesorter.defaults.widgets = ['zebra'];
	$("#relatorio2").tablesorter();

});
$(document).ready(function(){
	$.tablesorter.defaults.widgets = ['zebra'];
	$("#relatorio3").tablesorter();

});

function camada( sId ) {
	var sDiv = document.getElementById( sId );
	if( sDiv.style.display == "none" ) {
	sDiv.style.display = "block";
	sDiv.style.position = "absolute";
	} else {
	sDiv.style.display = "none";
	}
}

</script>

<?

if ($listar == "ok") {
	$sql2 = "SELECT referencia,descricao
			FROM tbl_peca
			WHERE peca = $peca
			AND   fabrica = $login_fabrica";
	$res2 = pg_exec ($con,$sql2);
	$peca_referencia = pg_result($res2,0,0);
	$peca_descricao  = pg_result($res2,0,1);
/*	if(strlen($peca)>0){
		$sql2 = "SELECT referencia,descricao
				FROM tbl_peca
				WHERE peca    = $peca
				AND   fabrica = $login_fabrica";
		$res2 = pg_exec ($con,$sql2);
		$peca_referencia = pg_result($res2,0,0);
		$peca_descricao  = pg_result($res2,0,1);
	}*/
	echo "<table width='100%' cellspacing='0' cellpadding='0' border='0' id='Menu'>";
	echo "<tr>";
	echo "<td bgcolor='#F5F9FC'>";
	echo "<h5>Peça: $peca_referencia - $peca_descricao</h5>";
	if(strlen($data_inicial)>0)echo "Resultado de pesquisa entre os dias <b>$data_inicial</b> e <b>$data_final</b>";
	echo "$mostraMsgLinha $mostraMsgEstado $mostraMsgPais";
	if ($login_fabrica == 50) { // HD 41116
		echo "<br><br><br>";
	}
	echo "</td>";
	echo "</tr>";
	echo "</table>";
	if ($login_fabrica == 50) { // HD 41116
		echo "<span id='logo'><img src='../imagens_admin/colormaq_.gif' border='0' width='160' height='55'></span>";
	}

	if(strlen($codigo_posto)>0){
		$sql = "SELECT  posto
				FROM    tbl_posto_fabrica
				WHERE   fabrica      = $login_fabrica
				AND     codigo_posto = '$codigo_posto';";
		$res = pg_exec ($con,$sql);
		if (pg_numrows($res) > 0) $posto = trim(pg_result($res,0,posto));
	}

	if (strlen ($linha)    > 0) $cond_1 = " AND   BI.linha   = $linha ";
	if (strlen ($estado)   > 0) $cond_2 = " AND   BI.estado  = '$estado' ";
	if (strlen ($posto)    > 0) $cond_3 = " AND   BI.posto   = $posto ";
	if (strlen ($produto)  > 0) $cond_4 = " AND   BI.produto = $produto "; // HD 2003 TAKASHI
	if (strlen ($pais)     > 0) $cond_6 = " AND   BI.pais    = '$pais' ";
	if (strlen ($marca)    > 0) $cond_7 = " AND   BI.marca   = $marca ";
	if (strlen ($origem)   > 0) $cond_8 = " AND   BI.origem  = '$origem' ";

	if (strlen($tipo_data) == 0 ) $tipo_data = 'data_fechamento';
	if (strlen($aux_data_inicial)>0 AND strlen($aux_data_final)>0){
		$cond_9 = "AND   BI.$tipo_data BETWEEN '$aux_data_inicial' AND '$aux_data_final'";
	}

	if($login_fabrica == 20 and $pais !='BR'){
		$produto_descricao   ="tbl_produto_idioma.descricao ";
		$join_produto_idioma =" LEFT JOIN tbl_produto_idioma ON tbl_produto.produto = tbl_produto_idioma.produto and tbl_produto_idioma.idioma = 'ES' ";
	}else{
		$produto_descricao   ="tbl_produto.descricao ";
		$join_produto_idioma =" ";
	}


	//Relatório por Defeito Reclamado
	$sql = "SELECT  count(distinct BI.os)        AS os,
					SUM(BI.custo_peca * BI.qtde) AS custo_peca  ,
					DC.codigo                    AS dc_codigo   ,
					DC.descricao                 AS dc_descricao,
					to_char(BI.$tipo_data,'mm') as mes into tmp_defeito_reclamado_$login_fabrica
		FROM      bi_os_item             BI
		JOIN      tbl_peca               PE ON PE.peca               = BI.peca
		LEFT JOIN tbl_defeito_constatado DC ON DC.defeito_constatado = BI.defeito_constatado
		WHERE BI.fabrica = $login_fabrica
		AND   BI.peca    = $peca
		 $cond_1 $cond_2 $cond_3 $cond_4 $cond_5 $cond_6 $cond_7 $cond_8 $cond_9
		GROUP BY    dc_codigo   ,
					dc_descricao,mes;
				SELECT SUM(os) as total_os, mes as total_mes 
					FROM tmp_defeito_reclamado_$login_fabrica 
					GROUP BY mes 
					ORDER BY mes";

	/* Alterei para pegar o defeito da Peça - HD 43710 */
	if ($login_fabrica == 50 OR $login_fabrica == 5){
		$sql = "SELECT  count(distinct BI.os)              AS os          ,
						SUM(BI.custo_peca * BI.qtde)       AS custo_peca  ,
						DE.codigo_defeito                  AS dc_codigo   ,
						DE.descricao                       AS dc_descricao,
						to_char(BI.$tipo_data,'mm') as mes
				into tmp_defeito_reclamado_$login_fabrica
				FROM      bi_os_item             BI
				JOIN      tbl_peca               PE ON PE.peca               = BI.peca
				LEFT JOIN tbl_defeito            DE ON DE.defeito            = BI.defeito
				WHERE BI.fabrica = $login_fabrica
				AND   BI.peca    = $peca
				 $cond_1 $cond_2 $cond_3 $cond_4 $cond_5 $cond_6 $cond_7 $cond_8 $cond_9
				GROUP BY    dc_codigo   ,
							dc_descricao,mes;
					SELECT SUM(os) as total_os,mes as total_mes 
					FROM tmp_defeito_reclamado_$login_fabrica 
					GROUP BY mes 
					ORDER BY mes";
	}

	$meses = array(1 => "Janeiro", "Fevereiro", "Março", "Abril", "Maio", "Junho", "Julho", "Agosto", "Setembro", "Outubro", "Novembro", "Dezembro");

	/*Alterado para listar qtde de defeitos por mes - HD 110479*/
	if($ip=='201.76.86.11')echo nl2br($sql);
	$res = pg_exec ($con,$sql);
	
	if ($login_fabrica==11){
		if (pg_numrows($res) > 0) {

					
			for ($i=0; $i<pg_numrows($res); $i++){
				$os             = trim(pg_result($res,$i,total_os));
				$mes             = (int)trim(pg_result($res,$i,total_mes));

				$meses = array(1 => "Janeiro", "Fevereiro", "Março", "Abril", "Maio", "Junho", "Julho", "Agosto", "Setembro", "Outubro", "Novembro", "Dezembro");
				$mes = $meses[$mes];

				if ($mes_antigo==""){
					$cabecalho = "<DIV ID=\"total\" STYLE='POSITION: static; BORDER: 10px solid #FFFFFF; BACKGROUND: #FFFFFF; margin:0px;'><table style='font-family: Verdana, Geneva, Arial, Helvetica, sans-serif; font-size: 11px;' cellspacing='3'  cellpadding='7'><tr align='center'><td width='100' style='background-color:#D9E2EF;'><b>MÊS</b></td><td width='60' style='background-color:#D9E2EF;'><b>$mes</b></td>";
					$linha = "<tr><td align='center' style='background-color:#D9E2EF;'><b>TOTAL</b></td><td align='center'>$os</td>";
				}else{
						$cabecalho = $cabecalho."<td width=60 style='background-color:#D9E2EF;'><b>$mes</b></td>";
						$linha = $linha."<td align='center'>$os</td>";
				}
				$mes_antigo=$mes;
			}
			$div=$cabecalho."</tr>".$linha."</tr></table></div>";

		}
	}
	if (pg_numrows($res) > 0) {
		$total = 0;

			$sql2 = "select		sum(os) as soma_os,
								sum(custo_peca) as soma_custo_peca,
								dc_codigo                    AS dc_codigo   ,
								dc_descricao                 AS dc_descricao 
						from tmp_defeito_reclamado_$login_fabrica 
						GROUP BY   dc_codigo   ,
								dc_descricao 
						order by dc_descricao";
		$res2 = pg_exec ($con,$sql2);

		echo "<center><div style='width:98%;'><TABLE width='600' border='0' cellspacing='2' cellpadding='2' align='center'  style=' border:#485989 1px solid; background-color: #e6eef7' name='relatorio' id='relatorio' class='tablesorter'>";
		echo "<thead>";
		echo "<TR>";
		if ($login_fabrica == 50 or $login_fabrica == 5){
			echo "<TD height='15'><b>Defeito</b></TD>";
		}else{
			echo "<TD height='15'><b>Defeito Constatado</b></TD>";
		}
		echo "<TD width='100' height='15'><b>Qtde OS</b></TD>";
		echo "<TD width='50' height='15'><b>Custo</b></TD>";
		echo "</TR>";
		echo "</thead>";
		echo "<tbody>";

		$total_pecas  ="";
		for ($i=0; $i<pg_numrows($res2); $i++){
			$de_codigo      = trim(pg_result($res2,$i,dc_codigo));
			$de_descricao   = trim(pg_result($res2,$i,dc_descricao));
			$custo_peca     = trim(pg_result($res2,$i,soma_custo_peca));
			$os             = trim(pg_result($res2,$i,soma_os));

			//$mes             = trim(pg_result($res,$i,mes));

			$total_pecas  = $total_pecas + $custo_peca;
			$custo_peca   = number_format($custo_peca,2,",",".");
			echo "<TR>";
			echo "<TD align='left' nowrap>";
				echo "$de_codigo - $de_descricao</TD>";
			echo "<TD align='center' nowrap>$os</TD>";
			echo "<TD align='right' nowrap>$custo_peca</TD>";
			echo "</TR>";
		}
		$total_pecas = number_format($total_pecas,2,",",".");
		echo "</tbody>";
		echo "<tr class='table_line'>";
		echo "<td colspan='2'><font size='2'><b><CENTER>TOTAL</b></td>";
		echo "<td><font size='2' color='009900'><b>$total_pecas</b></td>";
		echO "</tr>";		
		if ($login_fabrica==11){
			echo "<tr class='table_line'>";
			echo "<td colspan='3'>$div</td>";
			echO "</tr>";
		}
		echo " </TABLE>";
		echo "</div>";
		echo "<a href='javascript:history.back()'>[Voltar]</a>";
	}else{echo"vazio";}

	$sql ="drop table tmp_defeito_reclamado_$login_fabrica;";
	$res = pg_exec($con, $sql);

	//Relatório por Defeito Reclamado
	$sql = "SELECT  count( DISTINCT os)          AS os          ,
					SUM(BI.custo_peca * BI.qtde) AS custo_peca  ,
					PR.referencia                               ,
					PR.descricao								,
					to_char(BI.$tipo_data,'mm') as mes into tmp_defeito_reclamado_$login_fabrica
			FROM      bi_os_item             BI
			JOIN      tbl_peca               PE ON PE.peca    = BI.peca
			JOIN      tbl_produto            PR ON PR.produto = BI.produto
			WHERE BI.fabrica = $login_fabrica
			AND   BI.peca    = $peca
			 $cond_1 $cond_2 $cond_3 $cond_4 $cond_5 $cond_6 $cond_7 $cond_8 $cond_9
			GROUP BY    PR.referencia   ,
						PR.descricao,
						mes
			ORDER BY PR.referencia   ,
						PR.descricao;
			SELECT sum(os) as os_total,mes 
			FROM tmp_defeito_reclamado_$login_fabrica group by mes order by mes";
	#echo nl2br($sql);
	$res = pg_exec ($con,$sql);


	if (pg_numrows($res) > 0) {
		$total = 0;
		
		/*Alterado para listar qtde de produtos com defeito por mes - HD 110479*/
		if ($login_fabrica==11){
			for ($i=0; $i<pg_numrows($res); $i++){
				$os             = trim(pg_result($res,$i,os_total));
				$mes             = (int)trim(pg_result($res,$i,mes));
				
				$meses = array(1 => "Janeiro", "Fevereiro", "Março", "Abril", "Maio", "Junho", "Julho", "Agosto", "Setembro", "Outubro", "Novembro", "Dezembro");
				$mes = $meses[$mes];
								if ($mes_antigo==""){
					$cabecalho = "<DIV ID=\"total\" STYLE='POSITION: absolute; BORDER: 10px solid #FFFFFF; BACKGROUND: #FFFFFF; margin:0px;'><table style='font-family: Verdana, Geneva, Arial, Helvetica, sans-serif; font-size: 11px;' cellspacing='3'  cellpadding='7'><tr align='center'><td width='100' style='background-color:#D9E2EF;'><b>MÊS</b></td><td width='60' style='background-color:#D9E2EF;'><b>$mes</b></td>";
					$linha = "<tr><td align='center' style='background-color:#D9E2EF;'><b>TOTAL</b></td><td align='center'>$os</td>";
				}else{
						$cabecalho = $cabecalho."<td width=60 style='background-color:#D9E2EF;'><b>$mes</b></td>";
						$linha = $linha."<td align='center'>$os</td>";
				}
				$mes_antigo=$mes;
			}

			$total= $cabecalho."</tr></thead>".$linha."</tr></table></div>";
		}
		$sql2 = "SELECT SUM(os)                 AS soma_os,
						SUM(custo_peca)         AS soma_custo_peca,
						referencia              AS referencia,
						descricao               AS descricao 
				FROM tmp_defeito_reclamado_$login_fabrica 
				GROUP BY    referencia,
								descricao 
				ORDER BY    referencia,
								descricao";
		$res2 = pg_exec ($con,$sql2);
		echo "<center><div style='width:98%;'><TABLE width='600' border='0' cellspacing='2' cellpadding='2' align='center'  style=' border:#485989 1px solid; background-color: #e6eef7' name='relatorio2' id='relatorio2' class='tablesorter'>";
		echo "<thead>";
		echo "<TR>";
		echo "<TD height='15'><b>Produto</b></TD>";
		echo "<TD width='100' height='15'><b>Qtde OS</b></TD>";
		echo "<TD width='50' height='15'><b>Custo</b></TD>";
		echo "</TR>";
		echo "</thead>";
		echo "<tbody>";

		for ($i=0; $i<pg_numrows($res2); $i++){
			$referencia     = trim(pg_result($res2,$i,referencia));
			$descricao      = trim(pg_result($res2,$i,descricao));
			$custo_peca     = trim(pg_result($res2,$i,soma_custo_peca));
			$os             = trim(pg_result($res2,$i,soma_os));

			$total_pecas += $custo_peca;
			$custo_peca   = number_format($custo_peca,2,",",".");
			$div_e='div'.str_replace('.', '',str_replace(' ', '', $referencia));
			$div_ex=$$div_e;
			echo "<TR>";
			echo "<TD align='left' nowrap>";
			echo"$referencia - $descricao</TD>";
			echo "<TD align='center' nowrap>$os</TD>";
			echo "<TD align='right' nowrap>$custo_peca</TD>";
			echo "</TR>";
			
		}
		$total_pecas = number_format($total_pecas,2,",",".");
		echo "</tbody>";
		echo "<tr class='table_line'><td colspan='2'><font size='2'><b><CENTER>TOTAL</b></td>";
		echo "<td><font size='2' color='009900'><b>$total_pecas</b></td>";
		echO "</tr>";
		if ($login_fabrica==11){
			echo "<tr class='table_line'>";
			echo "<td colspan='3'>$div</td>";
			echO "</tr>";
		}
		echo " </TABLE></div>";
		echo "<a href='javascript:history.back()'>[Voltar]</a>";
	}

	$sql ="drop table tmp_defeito_reclamado_$login_fabrica;";
	$res = pg_exec($con, $sql);

	if(strlen($peca)>0){
		$sql = "SELECT  PE.peca                                ,
						PE.ativo                               ,
						PE.referencia                          ,
						PE.descricao                           ,
						PF.codigo_posto        AS posto_codigo ,
						PO.nome                AS posto_nome   ,
						BI.os                                  ,
						(BI.custo_peca * BI.qtde) AS custo_peca,
						BI.sua_os                              ,
						DR.codigo              AS dr_codigo    ,
						DR.descricao           AS dr_descricao ,
						DC.codigo              AS dc_codigo    ,
						DC.descricao           AS dc_descricao ,
						DE.codigo_defeito      AS de_codigo    ,
						DE.descricao           AS de_descricao ,
						SR.descricao           AS sr_descricao
			FROM      bi_os_item             BI
			JOIN      tbl_peca               PE ON PE.peca               = BI.peca
			JOIN      tbl_posto              PO ON PO.posto              = BI.posto
			JOIN      tbl_posto_fabrica      PF ON PF.posto              = BI.posto
			LEFT JOIN tbl_defeito_reclamado  DR ON DR.defeito_reclamado  = BI.defeito_reclamado
			LEFT JOIN tbl_defeito_constatado DC ON DC.defeito_constatado = BI.defeito_constatado
			LEFT JOIN tbl_servico_realizado  SR ON SR.servico_realizado  = BI.servico_realizado
			LEFT JOIN tbl_defeito            DE ON DE.defeito            = BI.defeito
			WHERE BI.fabrica = $login_fabrica
			AND   PF.fabrica = $login_fabrica
			AND   BI.peca    = $peca
			 $cond_1 $cond_2 $cond_3 $cond_4 $cond_5 $cond_6 $cond_7 $cond_8 $cond_9";
		#echo nl2br($sql);
		$res = pg_exec ($con,$sql);

		if (pg_numrows($res) > 0) {
			$total = 0;

			echo "<center><div style='width:98%;'><TABLE width='600' border='0' cellspacing='2' cellpadding='2' align='center'  style=' border:#485989 1px solid; background-color: #e6eef7' name='relatorio' id='relatorio' class='tablesorter'>";
			echo "<thead>";
			echo "<TR>";
			echo "<TD height='15'><b>OS</b></TD>";
			echo "<TD height='15'><b>Cód. Posto</b></TD>";
			echo "<TD height='15'><b>Posto</b></TD>";
			echo "<TD height='15'><b>Defeito Reclamado</b></TD>";
			echo "<TD height='15'><b>Defeito Constatado</b></TD>";
			if ($login_fabrica==50 OR $login_fabrica==5){#HD 43647
				echo "<TD height='15'><b>Defeito da Peça</b></TD>";
			}
			echo "<TD width='100' height='15'><b>Serviço Realizado</b></TD>";
			echo "<TD width='50' height='15'><b>Custo</b></TD>";
			echo "</TR>";
			echo "</thead>";
			echo "<tbody>";

			for ($i=0; $i<pg_numrows($res); $i++){
				$posto_codigo   = trim(pg_result($res,$i,posto_codigo));
				$posto_nome     = trim(pg_result($res,$i,posto_nome));
				$dr_codigo      = trim(pg_result($res,$i,dr_codigo));
				$dr_descricao   = trim(pg_result($res,$i,dr_descricao));
				$dc_codigo      = trim(pg_result($res,$i,dc_codigo));
				$dc_descricao   = trim(pg_result($res,$i,dc_descricao));
				$de_codigo      = trim(pg_result($res,$i,de_codigo));
				$de_descricao   = trim(pg_result($res,$i,de_descricao));
				$sr_descricao   = trim(pg_result($res,$i,sr_descricao));
				$custo_peca     = trim(pg_result($res,$i,custo_peca));
				$os             = trim(pg_result($res,$i,os));
				$sua_os         = trim(pg_result($res,$i,sua_os));

				$total_pecas += $custo_peca;
				$custo_peca   = number_format($custo_peca,2,",",".");

				echo "<TR>";
				echo "<TD align='left' nowrap><a href='../os_press.php?os=$os' target='_blanck'>$sua_os</a></td>";
				echo "<TD align='left' nowrap>$posto_codigo</TD>";
				echo "<TD align='left' nowrap>$posto_nome</TD>";
				if($login_fabrica == 5){ 
					$sqlx="SELECT defeito_reclamado_descricao from tbl_os where os=$os and fabrica= $login_fabrica";
					$resx = @pg_exec($con,$sqlx);
					$dr_descricao = @pg_result($resx,0,defeito_reclamado_descricao);
				}

				echo "<TD align='left' nowrap>$dr_codigo - $dr_descricao</TD>";
				echo "<TD align='left' nowrap>$dc_codigo - $dc_descricao</TD>";
				if ($login_fabrica==50 or $login_fabrica==5){ #HD 43647
					echo "<TD align='left' nowrap>$de_codigo - $de_descricao</TD>";
				}
				echo "<TD align='center' nowrap>$sr_descricao</TD>";
				echo "<TD align='right' nowrap>$custo_peca</TD>";
				echo "</TR>";
			}
			$total_pecas = number_format($total_pecas,2,",",".");
			echo "</tbody>";
			echo "<tr class='table_line'>";
			if ($login_fabrica == 50){
				echo "<td colspan='7'>";
			}else{
				echo "<td colspan='6'>";
			}
			echo "<font size='2'><b><CENTER>TOTAL</b>";
			echo "</td>";
			echo "<td><font size='2' color='009900'><b>$total_pecas</b></td>";
			echO "</tr>";
			echo " </TABLE></div>";
			echo "<a href='javascript:history.back()'>[Voltar]</a>";
		}
	}
}

flush();

?>


