<?
include '../dbconfig.php';
include '../includes/dbconnect-inc.php';
include '../includes/funcoes.php';

$admin_privilegios="gerencia";
include 'autentica_admin.php';
include "../monitora.php";


if(isset($produto))$listar="ok";
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
</script>

<?
if ($listar == "ok") {
	$sql2 = "SELECT referencia,descricao
			FROM tbl_produto 
			JOIN tbl_linha  USING(linha)
			WHERE produto = $produto
			AND   fabrica = $login_fabrica";
	$res2 = pg_exec ($con,$sql2);
	$produto_referencia = pg_result($res2,0,0);
	$produto_descricao  = pg_result($res2,0,1);
	if(strlen($peca)>0){
		$sql2 = "SELECT referencia,descricao
				FROM tbl_peca 
				WHERE peca    = $peca
				AND   fabrica = $login_fabrica";
		$res2 = pg_exec ($con,$sql2);
		$peca_referencia = pg_result($res2,0,0);
		$peca_descricao  = pg_result($res2,0,1);
	}
	echo "<table width='100%' cellspacing='0' cellpadding='0' border='0' id='Menu'>";
	echo "<tr>";
	echo "<td bgcolor='#F5F9FC'>";
	if (strlen($lista_produtos) > 0) {
		$sql2 = "SELECT referencia,descricao
				FROM tbl_produto 
				JOIN tbl_linha  USING(linha)
				WHERE produto in ($lista_produtos)
				AND   fabrica = $login_fabrica
				ORDER BY tbl_produto.referencia";
		$res2 = pg_exec ($con,$sql2);
		echo "<h5>Produto:";
		for($i=0;$i<pg_numrows($res2);$i++){
			$produto_referencia = pg_result($res2,$i,0);
			$produto_descricao  = pg_result($res2,$i,1);
			echo " $produto_referencia - $produto_descricao<br>";
		}
		echo "</h5>";
	}else{
		echo "<h5>Produto: $produto_referencia - $produto_descricao</h5>";
	}
	if(strlen($peca)>0)	echo "<h5>Peça: $peca_referencia - $peca_descricao</h5>";
	else                echo "<h5>&nbsp;</h5>";
	if(strlen($data_inicial)>0)echo "Resultado de pesquisa entre os dias <b>$data_inicial</b> e <b>$data_final</b>";
	echo "$mostraMsgLinha $mostraMsgEstado $mostraMsgPais";
	echo "</td>";
	echo "</tr>";
	echo "</table>";
	if ($login_fabrica == 50) { // HD 41116
		echo "<span id='logo'><img src='../imagens_admin/colormaq_.gif' width='160' height='55'></span>";
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
	if (strlen ($posto) > 0 AND !empty($exceto_posto)) {
		$cond_3 = " AND   NOT (BI.posto   = $posto) ";
	}
	if (strlen ($produto)  > 0) $cond_4 = " AND   BI.produto = $produto "; // HD 2003 TAKASHI
	if (strlen ($pais)     > 0) $cond_6 = " AND   BI.pais    = '$pais' ";
	if (strlen ($marca)    > 0) $cond_7 = " AND   BI.marca   = $marca ";
	if (strlen ($origem)   > 0) $cond_8 = " AND   BI.origem  = '$origem' ";
	if (strlen ($lista_produtos)> 0) {
		$cond_10 = " AND   BI.produto in ($lista_produtos) ";
		$cond_4  = "";
	}

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

	if(strlen($peca)>0){

		$sql = "SELECT  PE.peca                               ,
						PE.ativo                              ,
						PE.referencia                         ,
						PE.descricao                          ,
						PF.codigo_posto        AS posto_codigo,
						PO.nome                AS posto_nome  ,
						BI.os                                 ,
						BI.custo_peca                         ,
						BI.sua_os                             ,
						DR.codigo              AS dr_codigo   ,
						DR.descricao           AS dr_descricao,
						DC.codigo              AS dc_codigo   ,
						DC.descricao           AS dc_descricao,
						DE.codigo_defeito      AS de_codigo   ,
						DE.descricao           AS de_descricao,
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
			 $cond_1 $cond_2 $cond_3 $cond_4 $cond_5 $cond_6 $cond_7 $cond_8 $cond_9 $cond_10";

		$res = pg_exec ($con,$sql);

		if (pg_numrows($res) > 0) {
			$total = 0;

			echo "<center><div style='width:98%;'><TABLE width='600' border='0' cellspacing='2' cellpadding='2' align='center'  style=' border:#485989 1px solid; background-color: #e6eef7' name='relatorio' id='relatorio' class='tablesorter'>";
			echo "<thead>";
			echo "<TR>";
			echo "<TD height='15'><b>OS</b></TD>";
			echo "<TD height='15'><b>Cód. Posto</b></TD>";
			echo "<TD height='15'><b>Posto</b></TD>";
			if ($login_fabrica == 50){ #HD 86811 para Colormaq
				echo "<TD height='15'><b>Nº Série</b></TD>";
				echo "<TD height='15'><b>Data Fabricação</b></TD>";
			}
			echo "<TD height='15'><b>Defeito Reclamado</b></TD>";
			echo "<TD height='15'><b>Defeito Constatado</b></TD>";
			if ($login_fabrica==5 or $login_fabrica==50 or $login_fabrica==51) {#HD 43647
				echo "<TD height='15'><b>Defeito Peça</b></TD>";
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
				if($login_fabrica == 50 or $login_fabrica == 5){ // HD 37460

					if(strlen($dr_codigo) == 0){
						$sqlx="SELECT defeito_reclamado_descricao, serie from tbl_os where os=$os and fabrica= $login_fabrica";
					} else { # HD 86811 para Colormaq
						$sqlx="SELECT serie FROM tbl_os WHERE os = $os AND fabrica = $login_fabrica";
					}
					$resx = pg_exec($con,$sqlx);
					if(strlen($dr_codigo) == 0){
						$dr_descricao = pg_result($resx,0,defeito_reclamado_descricao);
					}
					$serie        = pg_result($resx,0,serie);
					$data_fabricacao = "";
					if(strlen($serie) > 0) {
						$sqld = "SELECT to_char(data_fabricacao,'DD/MM/YYYY') as data_fabricacao
								FROM tbl_numero_serie
								WHERE serie = '$serie'";
						$resd = pg_exec($con,$sqld);
						if(pg_numrows($resd) > 0) {
							$data_fabricacao=pg_result($resd,0,data_fabricacao);
						}
					}
				}

				echo "<TR>";
				echo "<TD align='left' nowrap><a href='../os_press.php?os=$os' target='_blanck'>$sua_os</a></td>";
				echo "<TD align='left' nowrap>$posto_codigo</TD>";
				echo "<TD align='left' nowrap>$posto_nome</TD>";
				if ($login_fabrica == 50){ #HD 86811 para Colormaq 
					echo "<TD align='left' nowrap>$serie</TD>";
					echo "<TD align='left' nowrap>$data_fabricacao</TD>";
				}
				if($login_fabrica == 15 or $login_fabrica == 5){
					$sql_dr       = "SELECT defeito_reclamado_descricao FROM tbl_os WHERE os = $os";
					$res_dr       = pg_exec($con,$sql_dr);
					$dr_descricao = pg_result($res_dr,0,defeito_reclamado_descricao);
				}
				echo "<TD align='left' nowrap>$dr_codigo - $dr_descricao</TD>";
				echo "<TD align='left' nowrap>$dc_codigo - $dc_descricao</TD>";
				if ($login_fabrica==50 or $login_fabrica==5){#HD 43647
					echo "<TD align='left' nowrap>$de_codigo - $de_descricao</TD>";
				}
				if ($login_fabrica==51){#HD 43647
					echo "<TD align='left' nowrap>$de_descricao</TD>";  // Defeito da peça
				}
				echo "<TD align='center' nowrap>$sr_descricao</TD>";
				echo "<TD align='right' nowrap>$custo_peca</TD>";
				echo "</TR>";
			}
			$total_pecas = number_format($total_pecas,2,",",".");
			echo "</tbody>";
			echo "<tr class='table_line'>";
			if ($login_fabrica == 50){
				echo "<td colspan='8'><font size='2'><b><CENTER>TOTAL</b></td>";
			}elseif ($login_fabrica == 5){
				echo "<td colspan='7'><font size='2'><b><CENTER>TOTAL</b></td>";
			}else{
				echo "<td colspan='6'><font size='2'><b><CENTER>TOTAL</b></td>";
			}
			echo "<td><font size='2' color='009900'><b>$total_pecas</b></td>";
			echO "</tr>";
			echo " </TABLE></div>";
			echo "<a href='javascript:history.back()'>[Voltar]</a>";
		}


	}else{
		$sql = "
			SELECT count(*) AS sem_peca
			FROM (
				SELECT distinct os
				FROM bi_os BI 
				WHERE BI.fabrica = $login_fabrica
				AND BI.excluida IS NOT TRUE
				$cond_1 $cond_2 $cond_3 $cond_4 $cond_5 $cond_6 $cond_7 $cond_8 $cond_9 $cond_10 
				EXCEPT
				SELECT distinct os
				FROM bi_os_item BI
				WHERE BI.fabrica = $login_fabrica
				AND   BI.peca    IS NOT NULL
				AND BI.excluida IS NOT TRUE
				$cond_1 $cond_2 $cond_3 $cond_4 $cond_5 $cond_6 $cond_7 $cond_8 $cond_9 $cond_10 
			 )X;
		";
		#echo $sql;
		$res = pg_exec ($con,$sql);
		$os_sem_peca = pg_result($res,0,0);
		$sql = "
			SELECT count( distinct os) AS com_peca
			FROM bi_os_item BI
			WHERE BI.fabrica = $login_fabrica
			AND BI.excluida IS NOT TRUE
			$cond_1 $cond_2 $cond_3 $cond_4 $cond_5 $cond_6 $cond_7 $cond_8 $cond_9 $cond_10
		";
		#echo $sql;
		$res = pg_exec ($con,$sql);
		$os_com_peca = pg_result($res,0,0);
		$total_quebra = $os_sem_peca+$os_com_peca;
		echo "<br><table border='0' style='border:1px #000000 solid;' bgcolor='#F5E8BC' align='center' cellpadding='2'>";
		echo "<tr>";
		echo "<th align='left'>OS sem peça:</th><td> $os_sem_peca</td>";
		echo "</tr>";
		echo "<tr>";
		echo "<th align='left'>OS com peça:</th><td> $os_com_peca</td>";
		echo "</tr>";
		echo "<tr>";
		echo "<th  align='left'>Total:</th><td><b>$total_quebra</b></td>";
		echo "</tr>";
		echo "</table>";

		flush();


		/*  DESATIVADO TULIO 06/04/2008
		$sql = "SELECT  PE.peca                              ,
						PE.ativo                             ,
						PE.referencia                        ,
						PE.descricao                         ,
						FA.descricao           AS f_nome     ,
						LI.nome                AS l_nome     ,
						MA.nome                AS m_nome     ,
						count(BI.os)           AS ocorrencia ,
						SUM(BI.custo_peca)     AS custo_peca ,
						SUM(BI.preco)          AS preco
			FROM      bi_os_item BI
			JOIN      tbl_peca    PE ON PE.peca    = BI.peca
			LEFT JOIN tbl_linha   LI ON LI.linha   = BI.linha
			LEFT JOIN tbl_familia FA ON FA.familia = BI.familia
			LEFT JOIN tbl_marca   MA ON MA.marca   = BI.marca
			WHERE BI.fabrica = $login_fabrica
			 $cond_1 $cond_2 $cond_3 $cond_4 $cond_5 $cond_6 $cond_7 $cond_8 $cond_9
			GROUP BY    PE.peca                              ,
						PE.ativo                             ,
						PE.referencia                        ,
						PE.descricao                         ,
						f_nome                               ,
						l_nome                               ,
						m_nome
			ORDER BY ocorrencia DESC ";

		*/


		$sql = "SELECT	tbl_peca.referencia,
						tbl_peca.descricao,
						tbl_peca.peca,
						tbl_peca.ativo,
						bi.qtde				AS ocorrencia ,
						bi.custo_peca		AS custo_peca
				FROM   (SELECT bi.peca, SUM (bi.qtde) AS qtde, SUM (bi.custo_peca) AS custo_peca
						FROM bi_os_item BI
						WHERE bi.fabrica = $login_fabrica
						AND BI.excluida IS NOT TRUE
						$cond_1 $cond_2 $cond_3 $cond_4 $cond_5 $cond_6 $cond_7 $cond_8 $cond_9 $cond_10 
						GROUP BY bi.peca
				) bi
				JOIN tbl_peca ON bi.peca = tbl_peca.peca
				ORDER BY ocorrencia DESC";

		$res = pg_exec ($con,$sql);

		if (pg_numrows($res) > 0) {
			$total = 0;

			echo "<center><div style='width:98%;'><TABLE width='600' border='0' cellspacing='2' cellpadding='2' align='center'  style=' border:#485989 1px solid; background-color: #e6eef7' name='relatorio' id='relatorio' class='tablesorter'>";
			echo "<thead>";
			echo "<TR>";
			echo "<TD width='100' height='15'><b>Referência</b></TD>";
			echo "<TD height='15'><b>Produto</b></TD>";
			echo "<TD width='120' height='15'><b>Ocorrência</b></TD>";
			echo "<TD width='50' height='15'><b>%</b></TD>";
			echo "<TD width='50' height='15'><b>Custo</b></TD>";
			echo "</TR>";
			echo "</thead>";
			echo "<tbody>";

			for ($x = 0; $x < pg_numrows($res); $x++) {
				$total_ocorrencia = $total_ocorrencia + pg_result($res,$x,ocorrencia);
			}
			for ($i=0; $i<pg_numrows($res); $i++){
				$referencia   = trim(pg_result($res,$i,referencia));
				$ativo        = trim(pg_result($res,$i,ativo));
				$descricao    = trim(pg_result($res,$i,descricao));
				if($login_fabrica == 20 and $pais !='BR' and strlen($descricao)==0){
					$descricao    = "<font color = 'red'>Tradução não cadastrada.</font>";
				}
				$peca         = trim(pg_result($res,$i,peca));
#				$familia      = trim(pg_result($res,$i,f_nome));
#				$linha        = trim(pg_result($res,$i,l_nome));
#				$marca        = trim(pg_result($res,$i,m_nome));
				$ocorrencia   = trim(pg_result($res,$i,ocorrencia));
				$custo_peca   = trim(pg_result($res,$i,custo_peca));
#				$preco        = trim(pg_result($res,$i,preco));

				if($custo_peca==0) $custo_peca = $preco;
				if ($total_ocorrencia > 0) $porcentagem = (($ocorrencia * 100) / $total_ocorrencia);

				if($ativo == 'f'){$ativo = "<B>*</B>"; }else{$ativo= '';} 

				$total_pecas    += $custo_peca;
				$total       += $ocorrencia ;
				$porcentagem_total += $porcentagem;
				$porcentagem = number_format($porcentagem,2,",",".");
				$custo_peca = number_format($custo_peca,2,",",".");

				echo "<TR>";
				echo "<TD align='left' nowrap>";

				echo "<a href='$PHP_SELF?produto=$produto&peca=$peca&data_inicial=$data_inicial&data_final=$data_final&aux_data_inicial=$aux_data_inicial&aux_data_final=$aux_data_final&tipo_data=$tipo_data&posto=$posto&lista_produtos=$lista_produtos&exceto_posto=$exceto_posto'>";
				echo "$referencia</TD>";
				echo "<TD align='left' nowrap>$descricao</TD>";
				echo "<TD align='center' nowrap>$ocorrencia</TD>";
				echo "<TD align='right' nowrap title=''>$porcentagem</TD>";
				echo "<TD align='right' nowrap>$custo_peca</TD>";
				echo "</TR>";
			}
			$total_pecas       = number_format($total_pecas,2,",",".");
			$porcentagem_total = number_format($porcentagem_total,2,",",".");
			echo "</tbody>";
			echo "<tr class='table_line'>";
			echo "<td colspan='2'><font size='2'><b><CENTER>TOTAL</b></td>";
			echo "<td align='center' ><font size='2' color='009900'><b>$total</b></td>";
			echo "<td align='right' ><font size='2' color='009900'><b>$porcentagem_total</b></td>";
			echo "<td align='right' ><font size='2' color='009900'><b>$total_pecas</b></td>";
			echO "</tr>";
			echo " </TABLE></div>";
		}
	}
}

flush();

?>


