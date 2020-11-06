<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

if(strlen($_GET['extrato']) == 0){
	header("Location: os_extrato.php");
	exit;
}

$extrato = trim($_POST['extrato']);
if(strlen($_GET['extrato']) > 0) $extrato = trim($_GET['extrato']);

$posto = trim($_POST['posto']);
if(strlen($_GET['posto']) > 0) $posto = trim($_GET['posto']);

$msg_erro = "";

$layout_menu = "os";
$title = "Extrato - Detalhado";
if($sistema_lingua == "ES") $title = "Extracto - Detallado";

include "cabecalho.php";

?>

<style type="text/css">

.menu_top {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: x-small;
	font-weight: bold;
	border: 1px solid;
	color:#000000;
	background-color: #d9e2ef
}

.table_line {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	border: 0px solid;
	background-color: #D9E2EF;
}

.table_line2 {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
}

</style>

<p>
<?
if(strlen($msg_erro) > 0){
	echo "<TABLE width=\"600\" align='center' border=0>";
	echo "<TR>";
	echo "<TD align='center'>$msg_erro</TD>";
	echo "</TR>";
	echo "</TABLE>";
}

# seleciona dados de OS pagamento
$sql = "SELECT	tbl_extrato_pagamento.valor_total       ,
		tbl_extrato_pagamento.acrescimo         ,
		tbl_extrato_pagamento.desconto          ,
		tbl_extrato_pagamento.valor_liquido     ,
		tbl_extrato_pagamento.autorizacao_pagto ,
		tbl_extrato_pagamento.nf_autorizacao    ,
		to_char(tbl_extrato_pagamento.data_vencimento,'DD/MM/YYYY') AS data_vencimento,
		to_char(tbl_extrato_pagamento.data_pagamento,'DD/MM/YYYY') AS data_pagamento  ,
		tbl_extrato_pagamento.obs               ,
		tbl_extrato_pagamento.baixa_extrato     
		FROM	tbl_extrato_pagamento
		JOIN	tbl_extrato ON tbl_extrato.extrato = tbl_extrato_pagamento.extrato
		WHERE	tbl_extrato_pagamento.extrato = $extrato
		AND		tbl_extrato.posto   = $login_posto
		AND		tbl_extrato.fabrica = $login_fabrica
		ORDER BY tbl_extrato_pagamento.extrato_pagamento ASC";
$res = @pg_exec ($con,$sql);

if (@pg_numrows($res) > 0){
	echo "<TABLE width=\"700\" align='center' border=0>";
	echo "	<TR class='menu_top'>";
	echo "		<TD align='center' colspan='5'>DADOS REFERENTES AO PAGAMENTO DO EXTRATO</TD>";
	echo "	</TR>";
	for($i=0; $i<pg_numrows($res); $i++){
		$ord = $i + 1;
		echo "	<TR class='menu_top'>";
		echo "		<TD align='center' rowspan='6'>$ord</TD>";
		echo "		<TD align='center'>Autorização nº</TD>";
		echo "		<TD align='center'>NF autorização</TD>";
		echo "		<TD align='center'>Data de autorização</TD>";
		echo "		<TD align='center'>Data de pagamento</TD>";
		echo "	</TR>";
		echo "	<TR class='table_line'>";
		echo "		<TD style='background-color: #F1F4FA' align='center'>".pg_result($res,$i,autorizacao_pagto)."</TD>";
		echo "		<TD style='background-color: #F1F4FA' align='center'>".pg_result($res,$i,nf_autorizacao)."</TD>";
		echo "		<TD style='background-color: #F1F4FA' align='center'>".pg_result($res,$i,data_vencimento)."</TD>";
		echo "		<TD style='background-color: #F1F4FA' align='center'>".pg_result($res,$i,data_pagamento)."</TD>";
		echo "	</TR>";
		echo "	<TR class='menu_top'>";
		echo "		<TD align='center'>Valor total</TD>";
		echo "		<TD align='center'>Acréscimo</TD>";
		echo "		<TD align='center'>Desconto</TD>";
		echo "		<TD align='center'>Valor total líquido</TD>";
		echo "	</TR>";
		echo "	<TR class='table_line'>";
		echo "		<TD style='background-color: #F1F4FA' align='center'>".number_format(pg_result($res,$i,valor_total),2,',','.')."</TD>";
		echo "		<TD style='background-color: #F1F4FA' align='center'>".number_format(pg_result($res,$i,acrescimo),2,',','.')."</TD>";
		echo "		<TD style='background-color: #F1F4FA' align='center'>".number_format(pg_result($res,$i,desconto),2,',','.')."</TD>";
		echo "		<TD style='background-color: #F1F4FA' align='center'>".number_format(pg_result($res,$i,valor_liquido),2,',','.')."</TD>";
		echo "	</TR>";
		echo "	<TR class='menu_top'>";
		echo "		<TD align='center' colspan='4'>Observações</TD>";
		echo "	</TR>";
		echo "	<TR class='table_line'>";
		echo "		<TD style='background-color: #F1F4FA' align='left' colspan='4'>&nbsp;".pg_result($res,$i,obs)."</TD>";
		echo "	</TR>";
	}
	echo "</TABLE>";
	echo "<br>";
}

if ($login_fabrica <> 14 and $login_fabrica <> 6) {
	$sql = "SELECT  count(*) as qtde,
					tbl_linha.nome
			FROM   tbl_os
			JOIN   tbl_os_extra  ON tbl_os_extra.os     = tbl_os.os
			JOIN   tbl_produto   ON tbl_produto.produto = tbl_os.produto
			JOIN   tbl_linha     ON tbl_linha.linha     = tbl_produto.linha
								AND tbl_linha.fabrica   = $login_fabrica
			WHERE  tbl_os_extra.extrato = $extrato
			GROUP BY tbl_linha.nome
			ORDER BY count(*)";
	$resx = pg_exec($con,$sql);
	
	if (pg_numrows($resx) > 0) {
		echo "<TABLE width='50%' border='0' cellspacing='1' cellpadding='0' align='center'>";
		echo "<TR class='menu_top'>";
		
		echo "<TD align='left'>";
		if($sistema_lingua == "ES") echo "LÍNEA";
		else                        echo "LINHA";
		echo "</TD>";
		echo "<TD align='center'>";
		if($sistema_lingua == "ES") echo "CTD OS";
		else                        echo "QTDE OS";
		echo "</TD>";
		
		echo "</TR>";
		
		for ($i = 0 ; $i < pg_numrows($resx) ; $i++) {
			$cor = "#d9e2ef";
			
			if ($i % 2 == 0){
				$cor = '#F1F4FA';
			}
			
			$linha = trim(pg_result($resx,$i,nome));
			$qtde  = trim(pg_result($resx,$i,qtde));
			
			echo "<TR class='table_line' style='background-color: $cor;'>";
			
			echo "<TD align='left' style='padding-right:5px'>$linha</TD>";
			echo "<TD align='center' style='padding-right:5px'>$qtde</TD>";
			
			echo "</TR>";
		}
		
		echo "</TABLE>";
		echo "<br>";
	}
}


# ----------------------------------------- #
# -- VERIFICA SE É POSTO OU DISTRIBUIDOR -- #
# ----------------------------------------- #
$sql = "SELECT  DISTINCT
				tbl_tipo_posto.tipo_posto     ,
				tbl_posto.estado
		FROM    tbl_tipo_posto
		JOIN    tbl_posto_fabrica    ON tbl_posto_fabrica.tipo_posto = tbl_tipo_posto.tipo_posto
									AND tbl_posto_fabrica.posto      = $login_posto
									AND tbl_posto_fabrica.fabrica    = $login_fabrica
		JOIN    tbl_posto            ON tbl_posto.posto = tbl_posto_fabrica.posto
		WHERE   tbl_tipo_posto.distribuidor IS TRUE
		AND     tbl_posto_fabrica.fabrica = $login_fabrica
		AND     tbl_tipo_posto.fabrica    = $login_fabrica
		AND     tbl_posto_fabrica.posto   = $login_posto ";
$res = pg_exec ($con,$sql);
if (pg_numrows($res) == 0) $tipo_posto = "P"; else $tipo_posto = "D";

$sql = "SELECT  tbl_os.posto                                                     ,
		tbl_os.sua_os                                                    ,
		tbl_os.os                                                        ,
		tbl_os.mao_de_obra                                               , ";

if (strlen($posto) == 0) $sql .= "tbl_os.mao_de_obra_distribuidor, ";
else					 $sql .= "(tbl_os.mao_de_obra + tbl_familia.mao_de_obra_adicional_distribuidor) AS mao_de_obra_distribuidor, ";

//takashi colocou 020207 HD 1049  tbl_os.tipo_os                                                   ,
$sql .=	"			tbl_os.consumidor_revenda                                        ,
					tbl_os.tipo_os                                                   ,
					tbl_os.pecas                                                     ,
					tbl_os.consumidor_nome                                           ,
					tbl_os.revenda_nome                                              ,
					tbl_os.data_abertura                                             ,
					tbl_os.data_fechamento                                           ,
					tbl_os.tipo_atendimento                                          ,
					to_char(tbl_extrato.data_geracao, 'DD/MM/YYYY')  AS data_geracao ,
					to_char(tbl_extrato.liberado,'DD/MM/YYYY')       AS liberado     ,
					tbl_extrato.data_geracao                         AS geracao      ,
					to_char(tbl_extrato_extra.baixado,'DD/MM/YYYY')  AS baixado      ,
					tbl_os_extra.os_reincidente                                      ,
					tbl_extrato.protocolo                                            ,
					tbl_extrato_extra.obs                                            ,
					tbl_os_extra.mao_de_obra                         AS extra_mo     ,
					tbl_os_extra.custo_pecas                         AS extra_pecas  ,
					tbl_os_extra.taxa_visita                         AS extra_instalacao     ,
					tbl_os_extra.deslocamento_km                     AS extra_deslocamento
		FROM        tbl_os_extra
		JOIN        tbl_os            ON tbl_os.os               = tbl_os_extra.os
		JOIN        tbl_produto       ON tbl_produto.produto     = tbl_os.produto
		JOIN        tbl_extrato       ON tbl_extrato.extrato     = tbl_os_extra.extrato
		JOIN        tbl_extrato_extra ON tbl_extrato.extrato     = tbl_extrato_extra.extrato
		LEFT JOIN   tbl_familia       ON tbl_familia.familia     = tbl_produto.familia
		WHERE       tbl_os_extra.extrato = $extrato and excluida IS NOT TRUE ";
/*WHERE		tbl_os_extra.extrato = $extrato AND
			       ( (SELECT tbl_os_status.status_os FROM tbl_os_status WHERE tbl_os_status.os = tbl_os.os 
					 ORDER BY tbl_os_status.data DESC LIMIT 1) IS NULL
						OR (SELECT tbl_os_status.status_os FROM tbl_os_status WHERE tbl_os_status.os = tbl_os.os ORDER BY tbl_os_status.data DESC LIMIT 1) NOT IN (15))";*/
/* 
$sql .=	"			tbl_os.consumidor_revenda                                        ,
				tbl_os.pecas                                                     ,
				tbl_os.consumidor_nome                                           ,
				tbl_os.data_abertura                                             ,
				tbl_os.data_fechamento                                           ,
				tbl_os.tipo_atendimento                                          ,
				to_char(tbl_extrato.data_geracao, 'DD/MM/YYYY')  AS data_geracao ,
				to_char(tbl_extrato_extra.baixado,'DD/MM/YYYY')  AS baixado      ,
				tbl_os_extra.os_reincidente                                      ,
				tbl_extrato.protocolo                                            ,
				tbl_extrato_extra.obs                                            ,
				tbl_os_extra.mao_de_obra                         AS extra_mo     ,
				tbl_os_extra.custo_pecas                         AS extra_pecas  ,
				tbl_os_extra.taxa_visita                         AS extra_instalacao     ,
				tbl_os_extra.deslocamento_km                     AS extra_deslocamento
		FROM        tbl_os_extra
		JOIN        tbl_os            ON tbl_os.os               = tbl_os_extra.os
		JOIN        tbl_produto       ON tbl_produto.produto     = tbl_os.produto
		JOIN        tbl_extrato       ON tbl_extrato.extrato     = tbl_os_extra.extrato
		JOIN        tbl_extrato_extra ON tbl_extrato.extrato     = tbl_extrato_extra.extrato
		LEFT JOIN   tbl_familia       ON tbl_familia.familia     = tbl_produto.familia
		WHERE       tbl_os_extra.extrato = $extrato AND
              		(tbl_os_status.status_os NOT IN (13,15) OR tbl_os_status.status_os IS NULL)        ";
 */
if ($login_fabrica == 6 and 1 == 2) {
	$sql .= "AND tbl_os_extra.os_reincidente IS NULL ";
}

if (strlen($posto) == 0) $sql .= "AND tbl_os.posto = $login_posto "; // DISTRIBUIDOR
else					 $sql .= "AND tbl_os.posto = $posto ";       // POSTO
$sql .= "ORDER BY   lpad(substr(tbl_os.sua_os,0,strpos(tbl_os.sua_os,'-')),20,0)               ASC,
					replace(lpad(substr(tbl_os.sua_os,strpos(tbl_os.sua_os,'-')),20,0),'-','') ASC";
//if($ip=="201.68.13.36"){ echo $sql; exit; }

echo "sql: $sql";

$res = pg_exec ($con,$sql);

$totalRegistros = pg_numrows($res);

if ($totalRegistros > 0){
	$ja_baixado = false ;
	$posto        = pg_result($res,0,posto);
	$data_geracao = pg_result($res,0,data_geracao);
	$liberado     = pg_result($res,0,liberado);
	$protocolo    = pg_result($res,0,protocolo);
	$geracao      = pg_result($res,0,geracao);

	$sql = "SELECT  tbl_posto.nome,
					tbl_posto_fabrica.codigo_posto
			FROM    tbl_posto_fabrica
			JOIN    tbl_posto   ON tbl_posto.posto     = tbl_posto_fabrica.posto
			JOIN    tbl_fabrica ON tbl_fabrica.fabrica = tbl_posto_fabrica.fabrica
			WHERE   tbl_posto_fabrica.posto   = $login_posto
			AND     tbl_posto_fabrica.fabrica = $login_fabrica;";
	$resx = pg_exec ($con,$sql);
	
	if (pg_numrows($resx) > 0) {
		$posto_codigo = trim(pg_result($resx,0,codigo_posto));
		$posto_nome   = trim(pg_result($resx,0,nome));
	}

	
	if ($login_fabrica == 11) {
		$mes_liberado = substr($liberado,3,2);
		$ano_liberado = substr($liberado,6,4);
		echo "<TABLE align='center'>";
		echo "<tr class='menu_top'>\n";
		$Mes = array("Janeiro","Fevereiro","Março","Abril","Maio","Junho","Julho","Agosto","Setembro","Outubro","Novembro","Dezembro");
		echo "<td align=\"center\"><B>Descrição da Nota Fiscal:</b><BR>	*Serviços prestados em aparelhos de sua comercialização, sob garantia durante o mês de ".$Mes[$mes_liberado-1]." de ".$ano_liberado.".*";
		echo "</tr>\n";

		if ($mes_liberado == '02' and $ano_liberado == '2007') {
			echo "<tr class='menu_top'>\n"; 
			echo "<td align=\"center\"><B>Informações:</b><BR>A Nota Fiscal de Serviços deverá estar na Empresa até o dia 01/03/2007; pagamento dia 07/03/2007.";
			echo "</tr>\n";
		}
		
		if ($mes_liberado == '03' and $ano_liberado == '2007') {
			echo "<tr class='menu_top'>\n"; 
			echo "<td align=\"center\"><B>Informações:</b><BR>A Nota Fiscal de Serviço deverá estar na Empresa até o dia 29/03/2007; pagamento dia 05/04/2007.";
			echo "</tr>\n";
		}
		
		echo "</TABLE>";
	}

if($login_fabrica==6){
		echo "<BR><BR><table width='300' border='0' align='center'>";
			echo "<TR>";
			echo "<td bgcolor='#e5af8a' width='35'>&nbsp;</td>";
			echo "<td><font size='1'>OS com 'PCI enviada para Tectoy'</font></td>";
			echo "</TR>";
		echo "</table><BR><BR>";

}

	echo "<center><font size='1'><a href='os_extrato_detalhe_rejeitadas.php?extrato=$extrato&posto=$login_posto'><B>Clique aqui para verificar a(s) OS(s) que não entraram no Extrato</B></a></font></center>";
	echo "<table width='700' align='center' border='0' cellspacing='1' cellpadding='1'>\n";
	echo "<tr class='menu_top'>";
	
	echo "<td colspan='9' align='center'>";
	if ($sistema_lingua == "ES") echo "EXTRACTO ";
	else                         echo "EXTRATO ";
	if ($login_fabrica == 1) echo $protocolo;
	else                     echo $extrato;
	if ($login_fabrica==11) echo " LIBERADO EM $liberado <br> $posto_codigo - $posto_nome";
	else {
		if($sistema_lingua == "ES") echo " GENERADO EN ";
		else                        echo " GERADO EM ";
		echo "$data_geracao <br> $posto_codigo - $posto_nome";
	}
	echo "</td>";
	
	echo "</tr>";

	
	echo "<tr class='menu_top'>\n";

	//Igor incluiu 28/03/2007 - HD: 1683
	if ($login_fabrica == 19) echo "<td align='center'>#</td>";

	echo "<td align='center' width='17%'>OS</td>\n";
	if ($login_fabrica == 20){
		echo "<td align='center'>";
		if($sistema_lingua == "ES") echo "TIPO DE ATENDIMIENTO";
		else                        echo "TIPO ATENDIMENTO";
		echo "</td>\n";
	}
	echo "<td align='center'>";
	if($sistema_lingua == "ES") echo "CONSUMIDOR";
	else                        echo "CLIENTE";
	echo "</td>\n";
	if ($login_fabrica == 6){
		echo "<td align='center'>MO</td>\n";
		echo "<td align='center'>MO REVENDA</td>\n";
		echo "<td align='center'>PEÇAS</td>\n";
		echo "<td align='center'>PEÇAS REVENDA</td>\n";
	}elseif ($login_fabrica == 19){
		echo "<td align='center'>MO</td>\n";
		echo "<td align='center'>PEÇAS</td>\n";
	    	echo "<td align='center'>INSTALAÇÃO</td>\n";
	    	echo "<td align='center'>DESLOCAMENTO</td>\n";
	    	echo "<td align='center'>TOTAL</td>\n";
	}else{
		echo "<td colspan=2 align='center'>MO</td>\n";
		echo "<td colspan=2 align='center'>";
		if($sistema_lingua == "ES") echo "PIEZAS";
		else                        echo "PEÇAS";
		echo "</td>\n";
	}
	
	echo "</tr>\n";
	
	$total                     = 0;
	$total_mao_de_obra         = 0;
	$total_mao_de_obra_revenda = 0;
	$total_pecas               = 0;
	$total_pecas_revenda       = 0;
	
	$total_extra_mo		   = 0;
	$total_extra_pecas         = 0;
	$total_extra_instalacao    = 0;
	$total_extra_deslocamento  = 0;
	$total_extra_total         = 0;
	
	for ($i = 0 ; $i < $totalRegistros; $i++){
		$os                       = trim(pg_result ($res,$i,os));
		$sua_os                   = trim(pg_result ($res,$i,sua_os));
		$mo                       = trim(pg_result ($res,$i,mao_de_obra));
		$mao_de_obra_distribuidor = trim(pg_result ($res,$i,mao_de_obra_distribuidor));
		$pecas                    = trim(pg_result ($res,$i,pecas));
		$consumidor_nome          = strtoupper(trim(pg_result ($res,$i,consumidor_nome)));
		$consumidor_str           = substr($consumidor_nome,0,23);
		$data_abertura            = trim (pg_result ($res,$i,data_abertura));
		$data_fechamento          = trim (pg_result ($res,$i,data_fechamento));
		$baixado                  = pg_result ($res,0,baixado) ;
		$obs                      = pg_result ($res,0,obs) ;
		$consumidor_revenda       = trim(pg_result ($res,$i,consumidor_revenda));
		$tipo_atendimento         = trim(pg_result ($res,$i,tipo_atendimento));
		$revenda_nome             = trim(pg_result ($res,$i,revenda_nome));
		$tipo_os                  = trim(pg_result ($res,$i,tipo_os));//takashi colocou 020207 HD 1049
		if ($login_fabrica == 19) {
    		$extra_mo  		  = trim(pg_result ($res,$i,extra_mo));
		    $extra_pecas 	  = trim(pg_result ($res,$i,extra_pecas));
		    $extra_instalacao	  = trim(pg_result ($res,$i,extra_instalacao));
		    $extra_deslocamento	  = trim(pg_result ($res,$i,extra_deslocamento));
		    $extra_total 	  = $extra_mo + $extra_pecas + $extra_instalacao + $extra_deslocamento;

		    $total_extra_mo	   	+= $extra_mo;
		    $total_extra_pecas         	+= $extra_pecas;
		    $total_extra_instalacao    	+= $extra_instalacao;
		    $total_extra_deslocamento  	+= $extra_deslocamento;
		    $total_extra_total         	+= $extra_total;
	
		}

if($consumidor_revenda=="R" and $login_fabrica==6) { $consumidor_str = $revenda_nome;}

		if (strlen($baixado) > 0) $ja_baixado = true ;
		
		# soma valores
		if ($login_fabrica == 6){
			if ($consumidor_revenda == 'R'){
				$mao_de_obra         = '0,00';
				$mao_de_obra_revenda = $mo;
				$pecas_posto         = '0,00';
				$pecas_revenda       = $pecas;
				
				if ($tipo_posto == "P") $total_mao_de_obra_revenda += $mao_de_obra_revenda ;
				else					$total_mao_de_obra_revenda += $mao_de_obra_distribuidor ;
			}else{
				$mao_de_obra         = $mo;
				$mao_de_obra_revenda = '0,00';
				$pecas_posto         = $pecas;
				$pecas_revenda       = '0,00';
				
				if ($tipo_posto == "P") $total_mao_de_obra += $mo;
				else					$total_mao_de_obra += $mao_de_obra_distribuidor ;
			}
			
			$total_pecas         += $pecas_posto;
			$total_pecas_revenda += $pecas_revenda;
		}else{
			//if ($tipo_posto == "P") {
				$total_mao_de_obra += $mo;
			//}else{
			//	$total_mao_de_obra += $mao_de_obra_distribuidor ;
			//}
			$mao_de_obra         = $mo;
			$pecas_posto         = $pecas;
			$total_pecas        += $pecas ;
		}
		
		$cor = "#d9e2ef";
		$btn = 'amarelo';

		if ($i % 2 == 0){
			$cor = '#F1F4FA';
			$btn = 'azul';
		}
		
		if (strstr($matriz, ";" . $i . ";")) {
			$cor = '#E49494';
		}
		//takashi colocou 020207 HD 1049
		if($login_fabrica==6 and $tipo_os=='8'){
			$cor='#e5af8a';
		}
		
		echo "<tr class='table_line' style='background-color: $cor;'>\n";

		//Igor incluiu 28/03/2007 - HD: 1683
		if ($login_fabrica == 19) echo "<td align='center'>".($i+1)."</td>";
		
		echo "<td align='center'><acronym title=\"Abertura: $data_abertura | Fechamento: $data_fechamento \"><a href=\"os_press.php?os=$os\" target='_blank'><font color='#000000'>";
		if($login_fabrica == 1) echo $posto_codigo;

		echo "$sua_os</font></a></acronym></td>\n";
		


		if ($login_fabrica == 20){
			if($tipo_atendimento>0){
				$sql2 = "   SELECT descricao 
							FROM tbl_tipo_atendimento 
							WHERE tipo_atendimento = $tipo_atendimento
							AND fabrica = $login_fabrica;";
				

				if($sistema_lingua == "ES"){
					$sql2 = "SELECT descricao FROM tbl_tipo_atendimento_idioma WHERE idioma = 'ES' AND tipo_atendimento = $tipo_atendimento";
				}
				$res2 = pg_exec ($con,$sql2);

				if(pg_numrows($res2)==1){
					$descricao        = pg_result($res2,0,descricao);
				}else{ 
					$descricao = "Não Consta";
				}
			}
			echo "<td align='left'>$descricao</td>\n";
		}
		echo "<td align='left' nowrap><acronym title=\"$consumidor\">$consumidor_str</acronym></td>\n";
		
		if ($login_fabrica == 6){
			if ($tipo_posto == "P") {
				echo "<td align='right' style='padding-right:5px'> " . number_format ($mao_de_obra,2,",",".") . "</td>\n";
				echo "<td align='right' style='padding-right:5px'> " . number_format ($mao_de_obra_revenda,2,",",".") . "</td>\n";
			}else{
				echo "<td align='right' style='padding-right:5px'> " . number_format ($mao_de_obra_distribuidor,2,",",".") . "</td>\n";
				echo "<td align='right' style='padding-right:5px'> " . number_format ($mao_de_obra_revenda,2,",",".") . "</td>\n";
			}
			
			echo "<td align='right' style='padding-right:5px'> " . number_format ($pecas_posto,2,",",".") . "</td>\n";
			echo "<td align='right' style='padding-right:5px'> " . number_format ($pecas_revenda,2,",",".") . "</td>\n";

		}elseif ($login_fabrica == 19){
			echo "<td align='right' style='padding-right:5px'> " . number_format ($extra_mo,2,",",".") . "</td>\n";
			echo "<td align='right' style='padding-right:5px'> " . number_format ($extra_pecas,2,",",".") . "</td>\n";
			echo "<td align='right' style='padding-right:5px'> " . number_format ($extra_instalacao,2,",",".") . "</td>\n";
			echo "<td align='right' style='padding-right:5px'> " . number_format ($extra_deslocamento,2,",",".") . "</td>\n";
			echo "<td align='right' style='padding-right:5px'> " . number_format ($extra_total,2,",",".") . "</td>\n";

		}else{
			//if ($tipo_posto == "P") {
				echo "<td colspan=2 align='right' style='padding-right:5px'> " . number_format ($mao_de_obra,2,",",".") . "</td>\n";
			//}else{
			//	echo "<td colspan=2 align='right' style='padding-right:5px'> R$ " . number_format ($mao_de_obra_distribuidor,2,",",".") . "</td>\n";
			//}
			
			echo "<td colspan=2 align='right' style='padding-right:5px'> " . number_format ($pecas_posto,2,",",".") . "</td>\n";
		}
		echo "</tr>\n";
	}
	//takashi colocou 020207 HD 1049
	if($login_fabrica==6 and $tipo_os=='8'){
		$cor='#d9e2ef';
	}
	echo "<tr class='table_line'>\n";
	//Igor incluiu 28/03/2007 - HD: 1683 (or $login_fabrica == 19)
	if(($login_fabrica==20) or ($login_fabrica == 19)) echo "<td colspan=\"3\"></td>\n";
	else echo "<td colspan=\"2\"></td>\n";
	if ($login_fabrica == 6){
		echo "<td align='right' bgcolor='$cor' style='padding-right:5px'><b> " . number_format ($total_mao_de_obra,2,",",".") . "</b></td>\n";
		echo "<td align='right' bgcolor='$cor' style='padding-right:5px'><b> " . number_format ($total_mao_de_obra_revenda,2,",",".") . "</b></td>\n";
		echo "<td align='right' bgcolor='$cor' style='padding-right:5px'><b> " . number_format ($total_pecas,2,",",".") . "</b></td>\n";
		echo "<td align='right' bgcolor='$cor' style='padding-right:5px'><b> " . number_format ($total_pecas_revenda,2,",",".") . "</b></td>\n";
	}elseif ($login_fabrica == 19){
		echo "<td align='right' bgcolor='$cor' style='padding-right:5px'><b> " . number_format ($total_extra_mo,2,",",".") . "</b></td>\n";
		echo "<td align='right' bgcolor='$cor' style='padding-right:5px'><b> " . number_format ($total_extra_pecas,2,",",".") . "</b></td>\n";
		echo "<td align='right' bgcolor='$cor' style='padding-right:5px'><b> " . number_format ($total_extra_instalacao,2,",",".") . "</b></td>\n";
		echo "<td align='right' bgcolor='$cor' style='padding-right:5px'><b> " . number_format ($total_extra_deslocamento,2,",",".") . "</b></td>\n";
		echo "<td align='right' bgcolor='$cor' style='padding-right:5px'><b> " . number_format ($total_extra_total,2,",",".") . "</b></td>\n";
	}else{
		echo "<td colspan=2 align='right' bgcolor='$cor' style='padding-right:5px'><b> " . number_format ($total_mao_de_obra,2,",",".") . "</b></td>\n";
		echo "<td colspan=2 align='right' bgcolor='$cor' style='padding-right:5px'><b> " . number_format ($total_pecas,2,",",".") . "</b></td>\n";
	}
	echo "</tr>\n";
	if ($login_fabrica == 19) {
	    echo "<tr class='table_line'>\n";

		//Igor incluiu 28/03/2007 - HD: 1683 
		echo "<td colspan='1' align='center' style='padding-right:10px'><b>".($i) ."</b></td>\n";
	    echo "<td colspan=\"1\" align=\"center\" style='padding-right:10px'><b>&lt;=TOTAL DE OS</b></td>\n";

	    echo "<td colspan=\"1\" align=\"center\" style='padding-right:10px'><b>TOTAL (MO + Peças)</b></td>\n";
	    echo "<td colspan=\"5\" bgcolor='$cor' align='center'><b> " . number_format ($total_extra_total,2,",",".") . "</b></td>\n";
	}else{
	    echo "<tr class='table_line'>\n";
		if($login_fabrica==20){
			echo "<td colspan=\"3\" align=\"center\" style='padding-right:10px'><b>";
			if($sistema_lingua == "ES") echo "TOTAL (MO + PIEZAS)";
			else                        echo "TOTAL (MO + Peças)";
			echo "</b></td>\n";
		}else echo "<td colspan=\"2\" align=\"center\" style='padding-right:10px'><b>TOTAL (MO + Peças)</b></td>\n";
	    echo "<td colspan=\"4\" bgcolor='$cor' align='center'><b> " . number_format ($total_mao_de_obra + $total_mao_de_obra_revenda + $total_pecas + $total_pecas_revenda,2,",",".") . "</b></td>\n";
	}
	echo "</tr>\n";
}
echo "</TABLE>\n";
echo "<br>";

	##### LANÇAMENTO DE EXTRATO AVULSO - INÍCIO #####
	$sql =	"SELECT tbl_lancamento.descricao         ,
					tbl_extrato.total,
					tbl_extrato_lancamento.historico ,
					tbl_extrato_lancamento.valor     
			FROM tbl_extrato_lancamento
			JOIN tbl_lancamento USING (lancamento)
			JOIN tbl_extrato USING (extrato)
			WHERE tbl_extrato_lancamento.extrato = $extrato
			AND   tbl_lancamento.fabrica = $login_fabrica";
	$res_avulso = pg_exec($con,$sql);

	if (pg_numrows($res_avulso) > 0) {
		echo "<table width='700' align='center' border='0' cellspacing='1' cellpadding='1'>\n";
		echo "<tr class='menu_top'>\n";
		echo "<td colspan='3'>LANÇAMENTO DE EXTRATO AVULSO</td>\n";
		echo "</tr>\n";
		echo "<tr class='menu_top'>\n";
		echo "<td>DESCRIÇÃO</td>\n";
		echo "<td>HISTÓRICO</td>\n";
		echo "<td>VALOR</td>\n";
		echo "</tr>\n";
		for ($j = 0 ; $j < pg_numrows($res_avulso) ; $j++) {
			$cor = ($i % 2 == 0) ? "#F7F5F0" : "#F1F4FA";

			echo "<tr class='table_line' style='background-color: $cor;'>\n";
			echo "<td width='45%'>" . pg_result($res_avulso, $j, descricao) . "</td>";
			echo "<td width='45%'>" . pg_result($res_avulso, $j, historico) . "</td>";
			echo "<td width='10%' align='right'> " . number_format( pg_result($res_avulso, $j, valor), 2, ',', '.') . "</td>";
			echo "</tr>";
		}
		echo "</table>\n";
		echo "<br>\n";


		echo "<table width='700' align='center' border='0' cellspacing='1' cellpadding='1'>\n";
		echo "<tr class='menu_top'>\n";
		echo "<td colspan='3'>TOTAL GERAL</td>\n";
		echo "</tr>\n";
		echo "<tr class='menu_top' 'table_line' style='background-color: #F1F4FA'>\n";
		echo "<td>	". pg_result($res_avulso,0,total)."</td>\n";
		echo "</tr>\n";
		echo "</table>";
		//echo "<br>\n";
	}
	##### LANÇAMENTO DE EXTRATO AVULSO - FIM #####
?>



<br>

<? if ($ja_baixado == true) { ?>
<table width='600' border='0' cellspacing='1' cellpadding='0' align='center'>
<tr>
	<td height='20' class="table_line" colspan='4'>PAGAMENTO</td>
</tr>
<tr>
	<td align='left' class="table_line" width='20%'>EXTRATO PAGO EM: </td>
	<td class="table_line" width='15%'><? echo $baixado; ?></td>
	<td align='left' class="table_line" width='15%'><center>OBSERVAÇÃO:</center></td>
	<td class="table_line" width='50%'><? echo $obs;?>
	</td>
</tr>
</table>
<? } 

if($login_fabrica==20){
	echo "<TABLE WIDTH='500' border='0' align='center'>";
	echo "<FORM name='frm_atendimento' METHOD=POST ACTION='$PHP_SELF?extrato=$extrato&posto=$posto'>";
	echo "<TR>";
	echo "<td class='table_line'> &nbsp; OS por tipo atedimento:</td>";
	echo "<td class='table_line' ALIGN='left'>";
	echo "<select class='frm' size='1' name='tipo_atendimento'>";
	echo "<option selected></option>";

	$sql = "SELECT *
			FROM   tbl_tipo_atendimento
			WHERE  fabrica = $login_fabrica;";
	$res = pg_exec ($con,$sql) ;
	
	for ($x = 0 ; $x < pg_numrows ($res) ; $x++ ) {
			echo "<option ";
			if ($tipo_atendimento == pg_result ($res,$x,tipo_atendimento)) echo " SELECTED ";
			echo " value='" . pg_result ($res,$x,tipo_atendimento) . "'>" ;
			echo pg_result ($res,$x,descricao) ;
			echo "</option>";
	}
	echo "</select>";
	echo "</td>";
	echo "<td class='table_line' WIDTH='93'>";
	echo "<input type='hidden' name='btn_acao' value=''>";
	echo "<img src='imagens/btn_filtrar.gif' onclick=\"javascript: if (document.frm_atendimento.btn_acao.value == '' ) { document.frm_atendimento.btn_acao.value='filtrar' ; document.frm_atendimento.submit() } else { alert ('Aguarde submissão') }\" ALT='Confirmar filtro por Tipo de Atendimento' border='0' style='cursor:pointer;'>";
	echo "</td>";
	echo "</tr>";
	echo "</FORM>";
	echo "</TABLE>";
}else{
?>

<br>

<TABLE WIDTH='600'  align='center'>
<FORM name='frm_servico' METHOD=POST ACTION="<? echo $PHP_SELF."?extrato=".$extrato."&posto=".$posto."#servicos"; ?>">
<TR>
	<td class='table_line'> &nbsp; OS por serviço realizado:</td>
	<td ALIGN='CENTER'>
<?
			echo "<select class='frm' size='1' name='servico_realziado'>";
			echo "<option selected></option>";
			
			$sql = "SELECT *
					FROM   tbl_servico_realizado
					WHERE  fabrica = $login_fabrica;";
			$res = pg_exec ($con,$sql) ;
			
			for ($x = 0 ; $x < pg_numrows ($res) ; $x++ ) {
				if ($login_fabrica == 3 AND $linha <> 3 AND pg_result ($res,$x,servico_realizado) == 20) {
				}else{
					echo "<option ";
					if ($servico_realizado == pg_result ($res,$x,servico_realizado)) echo " selected ";
					echo " value='" . pg_result ($res,$x,servico_realizado) . "'>" ;
					echo pg_result ($res,$x,descricao) ;
					echo "</option>";
				}
			}

			echo "</select>";
?>
	</td>
	<td WIDTH='93'>
		<input type="hidden" name="btn_acao" value="">
		<img src='imagens/btn_filtrar.gif' onclick="javascript: if (document.frm_servico.btn_acao.value == '' ) { document.frm_servico.btn_acao.value='filtrar' ; document.frm_servico.submit() } else { alert ('Aguarde submissão') }" ALT="Confirmar filtro por serviço realizado" border='0' style="cursor:pointer;">
	</td>
</tr>
</FORM>
</TABLE>

<?
}
$tipo_atendimento  = $_POST['tipo_atendimento'];
$servico_realizado = $_POST['servico_realizado'];
$btn_acao          = $_POST['btn_acao'];
if ($btn_acao == 'filtrar' AND (strlen($servico_realizado) > 0 OR strlen($tipo_atendimento)>0)) {

	$sql = "SELECT	 tbl_peca.referencia          ,
					 tbl_peca.descricao           ,
					 COUNT(tbl_os_item.peca) AS qtde
			FROM	 tbl_os
			JOIN	 tbl_os_produto ON tbl_os_produto.os = tbl_os.os
			JOIN	 tbl_os_item    ON tbl_os_item.os_produto = tbl_os_produto.os_produto
			JOIN	 tbl_peca       ON tbl_peca.peca = tbl_os_item.peca
			JOIN	 tbl_os_extra   ON tbl_os_extra.os = tbl_os.os
			JOIN	 tbl_extrato    USING (extrato)
			WHERE	 tbl_os.fabrica = $login_fabrica";
			if(strlen ($tipo_atendimento)>0)  $sql .= " AND tbl_os.tipo_atendimento = $tipo_atendimento";
			if(strlen ($servico_realizado)>0) $sql .= " AND tbl_os_item.servico_realizado = $servico_realizado ";
if (strlen($posto) == 0)  $sql .= "AND tbl_os.posto = $login_posto ";
else  $sql .= " AND tbl_os.posto = $posto ";
$sql .= "	
			AND		 tbl_extrato.extrato = $extrato
			GROUP BY tbl_peca.referencia,
					 tbl_peca.descricao
			ORDER BY tbl_peca.descricao";
	$res = pg_exec($con,$sql);
	$registros = pg_numrows($res);
	if ($registros > 0){
?>
<BR>
<a name='servicos'>
<table>
<tr class='menu_top'>
	<TD>Referência</td>
	<TD>Descricação da Peça</td>
	<TD>Qtde</td>
</tr>
<?
		for($i=0; $i < $registros; $i++){
			$referencia = pg_result($res,$i,referencia);
			$descricao  = pg_result($res,$i,descricao);
			$qtde       = pg_result($res,$i,qtde);
			
			echo "<tr class='table_line'>\n";
			echo "<td>$referencia</td>\n";
			echo "<td>$descricao</td>\n";
			echo "<td>$qtde</td>\n";
			echo "</tr>\n";
		}
	}
}
?>
</table>

<br>

<table align='center' >
<tr>
	<td>
		<br>
		<img src="imagens/btn_voltar.gif" onclick="javascript: window.location='os_extrato.php';" ALT="Voltar" border='0' style="cursor:pointer;">
		&nbsp;&nbsp;
<? 
	if ($login_fabrica == 1) $url = "os_extrato_detalhe_print_blackedecker.php";
	else                     $url = "os_extrato_detalhe_print.php";
?>
		<img src="imagens/btn_imprimir.gif" onclick="javascript: janela=window.open('<? echo $url; ?>?extrato=<? echo $extrato; ?>','extrato');" ALT="Imprimir" border='0' style="cursor:pointer;">


	</td>
</tr>
</table>

<p>
<p>

<? include "rodape.php"; ?>
