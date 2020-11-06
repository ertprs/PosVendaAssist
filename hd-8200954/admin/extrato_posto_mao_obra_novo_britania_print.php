<?php

include_once "dbconfig.php";
include_once "includes/dbconnect-inc.php";

$admin_privilegios="financeiro";

include_once "autentica_admin.php";

$extrato = trim($_GET['extrato']);
$posto   = trim($_GET['posto']);
$codigo_agrupado   = trim($_GET['codigo_agrupado']);
$imprimir = $_GET['imprimir'];

$title = "Extrato $extrato";

include_once "cabecalho_extrato_print_britania.php";?>

<style type="text/css">
.Titulo {
	text-align: center;
	font-family: Arial;
	font-size: 10px;
	font-weight: bold;
	color: #FFFFFF;
	background-color: #485989;
	background: url('imagens_admin/azul.gif');
}
.Conteudo {
	font-family: Arial;
	font-size: 9px;
	font-weight: normal;
}
.ConteudoBranco {
	font-family: Arial;
	font-size: 9px;
	color:#FFFFFF;
	font-weight: normal;
}
.Mes{
	font-size: 9px;
}
.menu_top2 {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: x-small;
	font-weight: bold;
	color:#ffffff;
	background-color: #596D9B
}

.table_line2 {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
}

.table_obs2 {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
}

.table_line3 {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	background-color: #FFFFBB;
}

.error{
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 12px;
	font-weight: normal;
	background-color: #FF0000;
}

#imagem{
	float: left;
	position: relative;
	left: 65px;
}

</style>

<script language="JavaScript">


</script>
<p>
<center>
<?
if(strlen($msg_erro)>0){
	echo "<DIV class='error'>".$msg_erro."</DIV>";
}

?>
<font size='+1' face='arial'>Data do Extrato</font>
<?

$sql = "SELECT	tbl_linha.nome AS linha_nome         ,
				tbl_linha.linha                      ,
				tbl_os_extra.mao_de_obra AS unitario ,
				COUNT(CASE WHEN tbl_os_extra.mao_de_obra_desconto IS NULL     THEN 1 ELSE NULL END) AS qtde                      ,
				COUNT(CASE WHEN tbl_os_extra.mao_de_obra_desconto IS NOT NULL THEN 1 ELSE NULL END) AS qtde_recusada             ,
				SUM  (CASE WHEN tbl_os_extra.mao_de_obra_desconto IS NULL THEN tbl_os_extra.mao_de_obra           ELSE 0 END) AS mao_de_obra_posto     ,
				tbl_os_extra.mao_de_obra           AS mao_de_obra_posto_unitaria     ,
				SUM  (CASE WHEN tbl_os_extra.mao_de_obra_desconto IS NULL THEN tbl_os_extra.mao_de_obra_adicional ELSE 0 END) AS mao_de_obra_adicional ,
				SUM  (CASE WHEN tbl_os_extra.mao_de_obra_desconto IS NULL THEN tbl_os_extra.adicional_pecas       ELSE 0 END) AS adicional_pecas       ,
				to_char (tbl_extrato.data_geracao, 'DD/MM/YYYY') AS data_geracao  ,
				distrib.nome_fantasia AS distrib_nome                                  ,
				distrib.posto    AS distrib_posto                                 
		FROM
			(SELECT tbl_os_extra.os 
			FROM tbl_os_extra 
			JOIN tbl_os      ON tbl_os_extra.os = tbl_os.os
			WHERE tbl_os_extra.extrato = $extrato
			) os 
		JOIN tbl_os_extra ON os.os = tbl_os_extra.os
		JOIN tbl_extrato  ON tbl_os_extra.extrato = tbl_extrato.extrato
		JOIN tbl_linha    ON tbl_os_extra.linha = tbl_linha.linha
		LEFT JOIN tbl_posto distrib ON tbl_os_extra.distribuidor = distrib.posto
		GROUP BY tbl_linha.linha, tbl_linha.nome, tbl_os_extra.mao_de_obra, tbl_extrato.data_geracao, distrib.nome_fantasia, distrib.posto, tbl_os_extra.mao_de_obra
		ORDER BY tbl_linha.nome";
$res = pg_query ($con,$sql);

echo $data_geracao = @pg_fetch_result ($res,0,data_geracao);

$data_geracao_extrato = $data_geracao;
echo "<br>";

$sql = "SELECT tbl_posto_fabrica.codigo_posto, tbl_posto.nome
		FROM tbl_posto_fabrica
		JOIN tbl_posto USING (posto)
		JOIN tbl_extrato ON tbl_extrato.fabrica = tbl_posto_fabrica.fabrica AND tbl_extrato.posto = tbl_posto_fabrica.posto
		WHERE tbl_extrato.extrato = $extrato";
$resX = pg_query ($con,$sql);

echo @pg_fetch_result ($resX,0,codigo_posto) . " - " . @pg_fetch_result ($resX,0,nome);

$codigo_posto2 = pg_fetch_result ($resX,0,codigo_posto);
$nome_posto2 = pg_fetch_result ($resX,0,nome);

if(strlen($codigo_posto2)>0){

	# 51985 - Francisco Ambrozio
	#   Alterei para pesquisar a partir do dia 1º do mês e incluir o mês atual
	#$data_serv  = mktime (date("H"), date("i"), date("s"),date("m")-12  , date("d"), date("Y"));
	$data_serv  = mktime (date("H"), date("i"), date("s"),date("m")+1 , 1, date("Y")-1);
	$data_inicial = strftime ("%Y-%m-%d", $data_serv);
	
	$xdata_inicial = $data_inicial .' 00:00:00';
	$xdata_final = date("Y-m-d 23:59:59");

	$sql = "SELECT   SUM(coalesce(pecas,0)+ (SELECT SUM(coalesce(mao_de_obra,0)) FROM tbl_extrato_conferencia JOIN tbl_extrato_conferencia_item ON tbl_extrato_conferencia.extrato_conferencia = tbl_extrato_conferencia_item.extrato_conferencia WHERE tbl_extrato.extrato = tbl_extrato_conferencia.extrato AND tbl_extrato_conferencia.cancelada IS NOT TRUE) +coalesce(e.valor,0))        AS total        ,
			 to_char(data_geracao,'YYYY-MM')      AS data_geracao ,
			 tbl_posto.posto                                      ,
			 tbl_posto.nome                       AS posto_nome   ,
			 tbl_posto_fabrica.codigo_posto,
			 tbl_extrato.extrato
		FROM tbl_extrato 
		JOIN tbl_posto_fabrica  ON  tbl_extrato.posto         = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica 
		JOIN tbl_posto          ON tbl_extrato.posto          = tbl_posto.posto
		LEFT JOIN (SELECT sum(valor) as valor,extrato from tbl_extrato_lancamento where fabrica = $login_fabrica and (admin notnull or lancamento in (103,104)) group by extrato) e ON e.extrato = tbl_extrato.extrato
		WHERE tbl_extrato.fabrica          = $login_fabrica 
		AND tbl_posto_fabrica.codigo_posto = '$codigo_posto2'
		AND tbl_extrato.aprovado IS NOT NULL
			AND to_char(data_geracao,'YYYY-MM') IN (
			SELECT DISTINCT to_char(data_geracao,'YYYY-MM') 
			FROM tbl_extrato 
			JOIN tbl_posto_fabrica using(posto) 
			WHERE codigo_posto     ='$codigo_posto2'
			AND tbl_extrato.fabrica = $login_fabrica
			AND tbl_extrato.data_geracao BETWEEN '$xdata_inicial' AND '$xdata_final'
		)
		AND   tbl_extrato.data_geracao BETWEEN '$xdata_inicial' AND '$xdata_final'
		GROUP BY to_char(data_geracao,'YYYY-MM'),
			tbl_posto.posto                 ,
			tbl_posto.nome                  ,
			tbl_posto_fabrica.codigo_posto ,
			tbl_extrato.extrato
		ORDER BY to_char(data_geracao,'YYYY-MM');";
	# echo nl2br($sql);exit;
	$res = pg_query($con,$sql);
	
	if (pg_num_rows($res) > 0) {
		$posto           = trim(pg_fetch_result($res,0,posto))       ;
		$posto_nome      = trim(pg_fetch_result($res,0,posto_nome))  ;
		$codigo_posto    = trim(pg_fetch_result($res,0,codigo_posto));

		echo "<br><table border='1' width='840' cellpadding='2' cellspacing='0'  bordercolor='#d2e4fc'  align='center'>";
		echo "<tr class='Titulo'>";
		echo "<td width='90' rowspan='2' >Valor Pago/Mes</td>";
		echo "<td colspan='12'>Meses</td>";
		echo "<td rowspan='2' class='Mes'>Total Ano</td>";
		echo "</tr><tr class='Titulo'>";
		
		# HD 68843
		$mes_atual = date("m");
		$ano_atual = date("Y");
		$ano_atual--;

		for($x=0;$x<12;$x++){
			
			if ($mes_atual < 12){
				$mes_atual++;
			}else{
				$mes_atual = 01;
				$ano_atual++;
			}
			
			$mes_atual = sprintf("%02d",$mes_atual);
			
			$mes[$x] = "$mes_atual/$ano_atual";
			echo "<td class='Mes'>$mes[$x]</td>";
		}

		echo "</tr>";

		$x=0;
		$y=0;
		//zerando todos arrays
		$posto_total=0;
		$qtde_mes =  array();
	
		$total_mes = 0;
		$total_ano = 0;
	

		$qtde_mes[$posto_total][0]  = 0;
		$qtde_mes[$posto_total][1]  = 0;
		$qtde_mes[$posto_total][2]  = 0;
		$qtde_mes[$posto_total][3]  = 0;
		$qtde_mes[$posto_total][4]  = 0;
		$qtde_mes[$posto_total][5]  = 0;
		$qtde_mes[$posto_total][6]  = 0;
		$qtde_mes[$posto_total][7]  = 0;
		$qtde_mes[$posto_total][8]  = 0;
		$qtde_mes[$posto_total][9]  = 0;
		$qtde_mes[$posto_total][10] = 0;
		$qtde_mes[$posto_total][11] = 0;
		$qtde_mes[$posto_total][12] = $posto_nome;
		$x=0;
		for ($i=0; $i<pg_num_rows($res); $i++){
	
			$posto           = trim(pg_fetch_result($res,$i,posto));
			$data_geracao    = trim(pg_fetch_result($res,$i,data_geracao));
			$total           = trim(pg_fetch_result($res,$i,total));

	
			
			$xdata_geracao = explode('-',$data_geracao);
			$data_geracao = $xdata_geracao[1].'/'.$xdata_geracao[0];

			if($posto_anterior<>$posto){

	//IMPRIME O VETOR COM OS DOZE MESES E LOGO APÓS PULA LINHA E ESCREVE O NOVO PRODUTO
				if($i<>0 ){
	
					for($a=0;$a<12;$a++){			//imprime os doze meses
						echo "<td bgcolor='$cor' title='".$mes[$a]."'>";
						if ($qtde_mes[$y][$a]>0)
							echo "<font color='#000000'><b>R$ ".number_format($qtde_mes[$y][$a],2,',','.');
						else echo "<font color='#999999'> ";
	
						echo "</td>";
						$total_ano = $total_ano + $qtde_mes[$y][$a];
						if($a==11) {
							$total_ano = number_format($total_ano,2,',','.');
							echo "<td bgcolor='$cor' >R$ $total_ano</td>";
							echo "</tr>";
						}	// se for o ultimo mes quebra a linha
					}
	
					$y=$y+1;						// usado para indicação de produto
				}
	
				if($cor=="#F1F4FA")$cor = '#F7F5F0';
				else               $cor = '#F1F4FA';
	
				echo "<tr class='Conteudo'align='center'>";
				echo "<td bgcolor='$cor' width='150'  height = '40'><b>$posto_nome</b></td>";
	
	
				$total_ano = 0;
				$x=0; //ZERA OS MESES
				
			}
			
			while($data_geracao<>$mes[$x]){ //repete o lup até que o mes seja igual e anda um mes.
//				echo "$data_geracao<>".$mes[$x];
				$x=$x+1;
			};
	
			
			if($data_geracao == $mes[$x]){
				$qtde_mes[$y][$x] = $total;
			}
	
			$x=$x+1; //após armazenar o valor no mes correspondente anda 1 mes no vetor
			
			if($i==(pg_num_rows($res)-1)){
				for($a=0;$a<12;$a++){			//imprime os doze meses
					echo "<td bgcolor='$cor' title='".$mes[$a]."'>";
					if ($qtde_mes[$y][$a]>0)
						echo "<font color='#000000'>R$ ".number_format($qtde_mes[$y][$a],2,',','.');
					else echo "<font color='#999999'> ";
	
					echo "</td>";
					$total_ano = $total_ano + $qtde_mes[$y][$a];
					if($a==11) {
						$total_ano = number_format($total_ano,2,',','.');
						echo "<td bgcolor='$cor' >R$ $total_ano</td>";
						echo "</tr>";
					}	// se for o ultimo mes quebra a linha
				}
			
			}
			$posto_anterior=$posto;
		}

		flush();

		$data_serv  = mktime (date("H"), date("i"), date("s"),date("m")+1 , 1, date("Y")-1);
		$data_inicial = strftime ("%Y-%m-%d", $data_serv);
		
		$xdata_inicial = $data_inicial .' 00:00:00';
		$xdata_final = date("Y-m-d 23:59:59");
	
		$sql = "SELECT SUM(coalesce(pecas,0)+ (SELECT SUM(coalesce(mao_de_obra,0)) FROM tbl_extrato_conferencia JOIN tbl_extrato_conferencia_item ON tbl_extrato_conferencia.extrato_conferencia = tbl_extrato_conferencia_item.extrato_conferencia WHERE tbl_extrato.extrato = tbl_extrato_conferencia.extrato AND tbl_extrato_conferencia.cancelada IS NOT TRUE) +coalesce(e.valor,0)) AS total ,
			to_char(data_geracao,'YYYY-MM')      AS data_geracao, tbl_extrato.extrato
		       INTO TEMP tmp_total_extrato	
		FROM tbl_extrato 
		LEFT JOIN (SELECT sum(valor) as valor,extrato from tbl_extrato_lancamento where fabrica = $login_fabrica and (admin notnull or  lancamento in (103,104)) group by extrato) e ON e.extrato = tbl_extrato.extrato
		WHERE tbl_extrato.fabrica          = $login_fabrica 
		AND tbl_extrato.aprovado IS NOT NULL
		AND to_char(data_geracao,'YYYY-MM') IN (
			SELECT DISTINCT to_char(data_geracao,'YYYY-MM') 
			FROM tbl_extrato
			WHERE tbl_extrato.fabrica = $login_fabrica
			AND tbl_extrato.data_geracao BETWEEN '$xdata_inicial' AND '$xdata_final'
		)
		AND   tbl_extrato.data_geracao BETWEEN '$xdata_inicial' AND '$xdata_final'
		GROUP BY to_char(data_geracao,'YYYY-MM'), tbl_extrato.extrato
		ORDER BY to_char(data_geracao,'YYYY-MM');

		SELECT data_geracao,SUM(total)AS total FROM tmp_total_extrato GROUP BY data_geracao ORDER BY data_geracao";
		#echo nl2br($sql); exit;
		$res = pg_query($con,$sql);
		if (pg_num_rows($res) > 0) {
	
			$x=0;
			$y=0;
			//zerando todos arrays
	
			$posto_total2 = 0;
			$qtde_mes2   =  array();
			$qtde_posto2 =  array();
	
			$total_mes2 = 0;
			$total_ano2 = 0;
	
			$qtde_mes2[$posto_total2][0]  = 0;
			$qtde_mes2[$posto_total2][1]  = 0;
			$qtde_mes2[$posto_total2][2]  = 0;
			$qtde_mes2[$posto_total2][3]  = 0;
			$qtde_mes2[$posto_total2][4]  = 0;
			$qtde_mes2[$posto_total2][5]  = 0;
			$qtde_mes2[$posto_total2][6]  = 0;
			$qtde_mes2[$posto_total2][7]  = 0;
			$qtde_mes2[$posto_total2][8]  = 0;
			$qtde_mes2[$posto_total2][9]  = 0;
			$qtde_mes2[$posto_total2][10] = 0;
			$qtde_mes2[$posto_total2][11] = 0;
			#$qtde_mes2[$posto_total2][12] = "Média";
	
			$qtde_posto2[$posto_total2][0]  = 0;
			$qtde_posto2[$posto_total2][1]  = 0;
			$qtde_posto2[$posto_total2][2]  = 0;
			$qtde_posto2[$posto_total2][3]  = 0;
			$qtde_posto2[$posto_total2][4]  = 0;
			$qtde_posto2[$posto_total2][5]  = 0;
			$qtde_posto2[$posto_total2][6]  = 0;
			$qtde_posto2[$posto_total2][7]  = 0;
			$qtde_posto2[$posto_total2][8]  = 0;
			$qtde_posto2[$posto_total2][9]  = 0;
			$qtde_posto2[$posto_total2][10] = 0;
			$qtde_posto2[$posto_total2][11] = 0;
			#$qtde_posto2[$posto_total2][12] = "Média";
			
			$x = 0;

			for ($i=0; $i<pg_num_rows($res); $i++){
		
				$data_geracao    = trim(pg_fetch_result($res,$i,data_geracao));
				$total           = trim(pg_fetch_result($res,$i,total));
	
				$sql2 = "SELECT  count(*) ,
						posto 
					FROM tbl_extrato 
					WHERE fabrica = $login_fabrica 
					AND  to_char(data_geracao,'YYYY-MM') ='$data_geracao'
					GROUP BY posto;";
				$xdata_geracao = explode('-',$data_geracao);
				$data_geracao = $xdata_geracao[1].'/'.$xdata_geracao[0];
	
				$res2 = pg_query($con,$sql2);
				if (pg_num_rows($res2) > 0) {
					$postos_digitaram[$i] = pg_num_rows($res2);
					$media_mes[$i] = $total / $postos_digitaram[$i];
				}

				$cor = '#F7F5F0';
	
				if($i==0){
					#echo "<tr class='Conteudo'align='center'>";
					#echo "<td bgcolor='$cor' width='150'  height = '40'><b>Média</b></td>";
				}
		
				$total_ano2 = 0;
				$x = 0; //ZERA OS MESES

				while($data_geracao<>$mes[$x]){ //repete o lup até que o mes seja igual e anda um mes.
					//echo "$data_geracao<>".$mes[$x];
					$x=$x+1;
				};
		
				if($data_geracao == $mes[$x]){
					$qtde_mes2[$y][$x]   = $media_mes[$i];
					$qtde_posto2[$y][$x] = $postos_digitaram[$i];
				}
		
				$x=$x+1; //após armazenar o valor no mes correspondente anda 1 mes no vetor
				
				if($i==(pg_num_rows($res)-1)){
					for($a=0;$a<12;$a++){			//imprime os doze meses
						/*
						echo "<td bgcolor='$cor' title='".$mes[$a]."'>";
						if ($qtde_mes2[$y][$a]>0)
							echo "<font color='#000000'>R$ ".number_format($qtde_mes2[$y][$a],2,',','.');
						else echo "<font color='#999999'> ";
		
						echo "</td>";
						 */
						$total_ano2 = $total_ano2 + $qtde_mes2[$y][$a];
						if($a==11) {
							$total_ano2 = number_format($total_ano2,2,',','.');
							#echo "<td bgcolor='$cor' >R$ $total_ano2</td>";
							#echo "</tr>";
	
							//TOTAL DE POSTOS
							echo "<tr class='Conteudo'align='center'>";
							for($a=0;$a<12;$a++){
								if($a==0) echo "<td bgcolor='$cor'><b>Total de Postos</b></td>";
								echo "<td bgcolor='$cor'>";
								if ($qtde_mes2[$y][$a]>0)
									echo "<font color='#000000'>".$qtde_posto2[$y][$a];
								else    echo " ";
								echo "</td>";
							}
							echo "<td bgcolor='$cor'> - </td></tr>";
						}	// se for o ultimo mes quebra a linha
					}
				}
			}
		}
		echo "</table><br>";
	}
}

echo "<div>";

if(strlen($codigo_posto2)>0){

	$data_serv  = mktime (date("H"), date("i"), date("s"),date("m")+1 , 1, date("Y")-1);
	$data_inicial = strftime ("%Y-%m-%d", $data_serv);

	$xdata_inicial = $data_inicial .' 00:00:00';
	$xdata_final = date("Y-m-d 23:59:59");

	$sql = "SELECT   SUM(coalesce(pecas,0)+coalesce(mao_de_obra,0)+coalesce(e.valor,0))        AS total        ,
			 to_char(data_geracao,'YYYY-MM')      AS data_geracao ,
			 tbl_posto.posto                                      ,
			 tbl_posto.nome                       AS posto_nome   ,
			 tbl_posto_fabrica.codigo_posto
		FROM tbl_extrato 
		LEFT JOIN (SELECT sum(valor) as valor,extrato from tbl_extrato_lancamento where fabrica = $login_fabrica and (admin notnull or lancamento in (103,104)) group by extrato) e ON e.extrato = tbl_extrato.extrato
		JOIN tbl_posto_fabrica  ON  tbl_extrato.posto         = tbl_posto_fabrica.posto 
					AND tbl_posto_fabrica.fabrica = $login_fabrica 
		JOIN tbl_posto          ON tbl_extrato.posto          = tbl_posto.posto 
		WHERE tbl_extrato.fabrica          = $login_fabrica 
		AND tbl_posto_fabrica.codigo_posto = '$codigo_posto2'
		AND tbl_extrato.aprovado IS NOT NULL
			AND to_char(data_geracao,'YYYY-MM') IN (
			SELECT DISTINCT to_char(data_geracao,'YYYY-MM') 
			FROM tbl_extrato 
			JOIN tbl_posto_fabrica using(posto) 
			WHERE codigo_posto     ='$codigo_posto2'
			AND tbl_extrato.fabrica = $login_fabrica
			AND tbl_extrato.data_geracao BETWEEN '$xdata_inicial' AND '$xdata_final'
		)
		AND   tbl_extrato.data_geracao BETWEEN '$xdata_inicial' AND '$xdata_final'
		GROUP BY to_char(data_geracao,'YYYY-MM'),
			tbl_posto.posto                 ,
			tbl_posto.nome                  ,
			tbl_posto_fabrica.codigo_posto  
		ORDER BY to_char(data_geracao,'YYYY-MM');";
	$resgrafico = pg_query($con,$sql);
	
	if (pg_num_rows($resgrafico) > 0) {
		$posto           = trim(pg_fetch_result($resgrafico,0,posto))       ;
		$posto_nome      = trim(pg_fetch_result($resgrafico,0,posto_nome))  ;
		$codigo_posto2   = trim(pg_fetch_result($resgrafico,0,codigo_posto));

		$mes_atual = date("m");
		$ano_atual = date("Y");
		$ano_atual--;

		for($x=0;$x<12;$x++){
			if ($mes_atual < 12){
				$mes_atual++;
			}else{
				$mes_atual = 01;
				$ano_atual++;
			}
			$mes_atual = sprintf("%02d",$mes_atual);
			$mes[$x] = "$mes_atual/$ano_atual";
		}
		$x=0;
		$y=0;

		$posto_total=0;
		$qtde_mes =  array();
	
		$total_mes = 0;
		$total_ano = 0;

		$qtde_mes[$posto_total][0]  = 0;
		$qtde_mes[$posto_total][1]  = 0;
		$qtde_mes[$posto_total][2]  = 0;
		$qtde_mes[$posto_total][3]  = 0;
		$qtde_mes[$posto_total][4]  = 0;
		$qtde_mes[$posto_total][5]  = 0;
		$qtde_mes[$posto_total][6]  = 0;
		$qtde_mes[$posto_total][7]  = 0;
		$qtde_mes[$posto_total][8]  = 0;
		$qtde_mes[$posto_total][9]  = 0;
		$qtde_mes[$posto_total][10] = 0;
		$qtde_mes[$posto_total][11] = 0;
		$qtde_mes[$posto_total][12] = $posto_nome;
		$x=0;

		for ($i=0; $i<pg_num_rows($resgrafico); $i++){
			$posto           = trim(pg_fetch_result($resgrafico,$i,posto));
			$data_geracao    = trim(pg_fetch_result($resgrafico,$i,data_geracao));
			$total           = trim(pg_fetch_result($resgrafico,$i,total));

			$xdata_geracao = explode('-',$data_geracao);
			$data_geracao = $xdata_geracao[1].'/'.$xdata_geracao[0];

			if($posto_anterior<>$posto){
				if($i<>0 ){
					for($a=0;$a<12;$a++){			//imprime os doze meses
						echo ($qtde_mes[$y][$a]>0) ? "<font color='#000000'><b>R$ ".number_format($qtde_mes[$y][$a],2,',','.') : "<font color='#999999'> ";

						$total_ano = $total_ano + $qtde_mes[$y][$a];
						if($a==11) {
							$total_ano = number_format($total_ano,2,',','.');
						}	// se for o ultimo mes quebra a linha
					}
					$y=$y+1;						// usado para indicação de produto
				}

				$cor = ($cor=="#F1F4FA") ? '#F7F5F0' : '#F1F4FA';

				$total_ano = 0;
				$x=0; //ZERA OS MESES
			}

			while($data_geracao<>$mes[$x]){ 
				$x=$x+1;
			};

			if($data_geracao == $mes[$x]){
				$qtde_mes[$y][$x] = $total;
			}

			$x=$x+1; //após armazenar o valor no mes correspondente anda 1 mes no vetor

			if($i==(pg_num_rows($resgrafico)-1)){
				for($a=0;$a<12;$a++){			//imprime os doze meses
					echo ($qtde_mes[$y][$a]>0) ? "<font color='#000000'>" : "<font color='#999999'> ";
					$total_ano = $total_ano + $qtde_mes[$y][$a];
					if($a==11) {
						$total_ano = number_format($total_ano,2,',','.');
					}	// se for o ultimo mes quebra a linha
				}
			}
			$posto_anterior=$posto;
		}

		flush();

		$data_serv  = mktime (date("H"), date("i"), date("s"),date("m")+1 , 1, date("Y")-1);
		$data_inicial = strftime ("%Y-%m-%d", $data_serv);

		$xdata_inicial = $data_inicial .' 00:00:00';
		$xdata_final = date("Y-m-d 23:59:59");
	
		$sql = "SELECT	SUM(coalesce(pecas,0)+coalesce(mao_de_obra,0)+coalesce(e.valor,0))  AS total        ,
						to_char(data_geracao,'YYYY-MM')      AS data_geracao 
					FROM tbl_extrato 
					LEFT JOIN (SELECT sum(valor) as valor,extrato from tbl_extrato_lancamento where fabrica = $login_fabrica and (admin notnull or  lancamento in (103,104)) group by extrato) e ON e.extrato = tbl_extrato.extrato
					WHERE tbl_extrato.fabrica          = $login_fabrica 
					AND tbl_extrato.aprovado IS NOT NULL
					AND to_char(data_geracao,'YYYY-MM') IN (
						SELECT DISTINCT to_char(data_geracao,'YYYY-MM') 
						FROM tbl_extrato
						WHERE tbl_extrato.fabrica = $login_fabrica
						AND tbl_extrato.data_geracao BETWEEN '$xdata_inicial' AND '$xdata_final'
					)
					AND   tbl_extrato.data_geracao BETWEEN '$xdata_inicial' AND '$xdata_final'
					GROUP BY to_char(data_geracao,'YYYY-MM')
					ORDER BY to_char(data_geracao,'YYYY-MM');";
		$resgrafico = pg_query($con,$sql);
		if (pg_num_rows($resgrafico) > 0) {
			$x=0;
			$y=0;
	
			$posto_total2 = 0;
			$qtde_mes2   =  array();
			$qtde_posto2 =  array();
	
			$total_mes2 = 0;
			$total_ano2 = 0;
	
			$qtde_mes2[$posto_total2][0]  = 0;
			$qtde_mes2[$posto_total2][1]  = 0;
			$qtde_mes2[$posto_total2][2]  = 0;
			$qtde_mes2[$posto_total2][3]  = 0;
			$qtde_mes2[$posto_total2][4]  = 0;
			$qtde_mes2[$posto_total2][5]  = 0;
			$qtde_mes2[$posto_total2][6]  = 0;
			$qtde_mes2[$posto_total2][7]  = 0;
			$qtde_mes2[$posto_total2][8]  = 0;
			$qtde_mes2[$posto_total2][9]  = 0;
			$qtde_mes2[$posto_total2][10] = 0;
			$qtde_mes2[$posto_total2][11] = 0;
			#$qtde_mes2[$posto_total2][12] = "Média";

			$qtde_posto2[$posto_total2][0]  = 0;
			$qtde_posto2[$posto_total2][1]  = 0;
			$qtde_posto2[$posto_total2][2]  = 0;
			$qtde_posto2[$posto_total2][3]  = 0;
			$qtde_posto2[$posto_total2][4]  = 0;
			$qtde_posto2[$posto_total2][5]  = 0;
			$qtde_posto2[$posto_total2][6]  = 0;
			$qtde_posto2[$posto_total2][7]  = 0;
			$qtde_posto2[$posto_total2][8]  = 0;
			$qtde_posto2[$posto_total2][9]  = 0;
			$qtde_posto2[$posto_total2][10] = 0;
			$qtde_posto2[$posto_total2][11] = 0;
			#$qtde_posto2[$posto_total2][12] = "Média";

			$x = 0;

			for ($i=0; $i<pg_num_rows($resgrafico); $i++){

				$data_geracao    = trim(pg_fetch_result($resgrafico,$i,data_geracao));
				$total           = trim(pg_fetch_result($resgrafico,$i,total));

				$databarx[$i] = $data_geracao;

				$sql2 = "SELECT	count(*),
								posto
						FROM tbl_extrato
						WHERE fabrica = $login_fabrica 
						AND  to_char(data_geracao,'YYYY-MM') ='$data_geracao'
						GROUP BY posto;";
				$resgrafico2 = pg_query($con,$sql2);

				$xdata_geracao = explode('-',$data_geracao);
				$data_geracao = $xdata_geracao[1].'/'.$xdata_geracao[0];

				if (pg_num_rows($resgrafico2) > 0) {
					$postos_digitaram[$i] = pg_num_rows($resgrafico2);
					$media_mes[$i] = $total / $postos_digitaram[$i];
				}

				$cor = '#F7F5F0';
				$total_ano2 = 0;
				$x = 0; //ZERA OS MESES

				while($data_geracao<>$mes[$x]){
					$x=$x+1;
				};

				if($data_geracao == $mes[$x]){
					$qtde_mes2[$y][$x]   = $media_mes[$i];
					$qtde_posto2[$y][$x] = $postos_digitaram[$i];
				}

				$x=$x+1; //após armazenar o valor no mes correspondente anda 1 mes no vetor

				if($i==(pg_num_rows($resgrafico)-1)){
					for($a=0;$a<12;$a++){			//imprime os doze meses
						echo ($qtde_mes2[$y][$a]>0) ? "<font color='#000000'>" : "<font color='#999999'> ";
						$total_ano2 = $total_ano2 + $qtde_mes2[$y][$a];
						if($a==11) {
							$total_ano2 = number_format($total_ano2,2,',','.');
							//TOTAL DE POSTOS
		
							for($a=0;$a<12;$a++){
								if($a==0) 
								if ($qtde_mes2[$y][$a]>0)
									echo "";
								else    echo " ";
							}
						}
					}
				}
			}
		}
		echo "</table><br>";
        
		include("jpgraph2/jpgraph.php");
		include("jpgraph2/jpgraph_line.php");

		$img = $extrato."_".$login_fabrica."_".$login_admin;
		$image_graph = "png/1_$img.png";

		// seleciona os dados das médias
		setlocale (LC_ALL, 'et_EE.ISO-8859-1');

		// Joga os meses no eixo X

		$data2y = array(
					$valor2y[0]  = 0,
					$valor2y[1]  = 0,
					$valor2y[2]  = 0,
					$valor2y[3]  = 0,
					$valor2y[4]  = 0,
					$valor2y[5]  = 0,
					$valor2y[6]  = 0,
					$valor2y[7]  = 0,
					$valor2y[8]  = 0,
					$valor2y[9]  = 0,
					$valor2y[10] = 0,
					$valor2y[11] = 0
				);

		// A nice graph with anti-aliasing
		$graph = new Graph(600,400,"auto");
		$graph->img->SetMargin(40,90,27,75);
		$graph->img->SetAntiAliasing("white");
		$graph->SetScale("textlin");
		$graph->SetShadow();
		$graph->title->Set("Relatório Anual de Extrato - ".$qtde_mes[0][12]);

		$graph->yaxis->HideZeroLabel();
		$graph->legend->Pos(0.02,0.9,"right","bottom");
		$graph->ygrid->SetFill(true,'#EFEFEF@0.5','#BBCCFF@0.5');
		$graph->xgrid->Show();

		$graph->xaxis->SetLabelAngle(90);

		$graph->xaxis->SetTickLabels($databarx);
		$graph->yaxis->title->Set("");
		$graph->xaxis->title->Set("");
		$graph->title->SetFont(FF_FONT1,FS_BOLD);

		$i=0;
		for($j=0; $j<13; $j++){
			if ($j==12){
				$titulo = "Posto";
			}
			else $valory[$j] = $qtde_mes[$i][$j];
		}

		$data1y  = array(
					$valory[0],
					$valory[1],
					$valory[2],
					$valory[3],
					$valory[4],
					$valory[5],
					$valory[6],
					$valory[7],
					$valory[8],
					$valory[9],
					$valory[10],
					$valory[11]
				);

		$p1 = new LinePlot($data1y);
		$p1->mark->SetType(MARK_UTRIANGLE);

		$p1->mark->SetFillColor("blue");
		$p1->mark->SetWidth(2);

		$p1->value->SetFont(FF_FONT1,FS_BOLD);
		
		$p1->SetColor("blue");
		$p1->SetCenter();
		$p1->SetLegend($titulo);
		$p1->value->SetFormat('%0.0f');

		$graph->Add($p1);

		$i=0;
		for($j=0; $j<13; $j++){
			if ($j==12){
				$titulo = $qtde_mes2[$i][$j];
			}
			else $valory[$j] = $qtde_mes2[$i][$j];
		}

		$data1y  = array(
					$valory[0],
					$valory[1],
					$valory[2],
					$valory[3],
					$valory[4],
					$valory[5],
					$valory[6],
					$valory[7],
					$valory[8],
					$valory[9],
					$valory[10],
					$valory[11]
				);

		$p2 = new LinePlot($data1y);
		$p2->mark->SetType(MARK_FILLEDCIRCLE);
		$p2->mark->SetFillColor("orange");
		$p2->mark->SetWidth(2);
		$p2->value->SetFont(FF_FONT1,FS_BOLD);
		$p2->SetColor("orange");
		$p2->SetCenter();
		$p2->SetLegend($titulo);
		$p2->value->SetFormat('%0.0f');
	#	$graph->Add($p2);

		$graph->Stroke($image_graph);
		echo "<center><img src='$image_graph' height='300' width='500' id='imagem'></center>";
	}

}


$sql = "SELECT a.os
	into TEMP tmp_reinc_90_$login_admin
	from
	(SELECT distinct tbl_os_auditar.os
	FROM tbl_os_auditar
	JOIN tbl_os_extra USING(os)
	WHERE  (tbl_os_auditar.descricao !~* '.*Reincidente.*' AND tbl_os_extra.mao_de_obra_desconto NOTNULL)
	AND tbl_os_extra.extrato = $extrato
	UNION
	select tbl_os_status.os
	FROM tbl_os_status
	JOIN tbl_os_extra USING(os)
	WHERE tbl_os_extra.extrato = $extrato
	AND tbl_os_status.status_os in (67,70) and tbl_os_status.observacao like '% MAIS 90 DIAS)' ) a	;

	SELECT	tbl_linha.nome AS linha_nome         ,
				tbl_linha.linha                      ,
				tbl_os_extra.mao_de_obra AS unitario ,
				COUNT(CASE WHEN (tbl_os_extra.mao_de_obra_desconto is null or (tbl_os_extra.mao_de_obra_desconto is not null AND tbl_os_extra.os IN (select os from tmp_reinc_90_$login_admin where 1=1) )) THEN 1 else null END ) AS qtde                      ,
				SUM  (CASE WHEN (tbl_os_extra.mao_de_obra_desconto is null or (tbl_os_extra.mao_de_obra_desconto is not null AND tbl_os_extra.os IN (select os from tmp_reinc_90_$login_admin where 1=1) )) THEN tbl_os_extra.mao_de_obra           ELSE 0 END) AS mao_de_obra_posto
		FROM
			(SELECT tbl_os_extra.os 
			FROM tbl_os_extra 
			JOIN tbl_os      ON tbl_os_extra.os = tbl_os.os
			WHERE tbl_os_extra.extrato = $extrato
			) os 
		JOIN tbl_os_extra ON os.os = tbl_os_extra.os
		JOIN tbl_extrato  ON tbl_os_extra.extrato = tbl_extrato.extrato
		JOIN tbl_linha    ON tbl_os_extra.linha = tbl_linha.linha
		GROUP BY tbl_linha.linha, tbl_linha.nome ,tbl_os_extra.mao_de_obra
		ORDER BY tbl_linha.nome";
$res = pg_query ($con,$sql);

echo @pg_fetch_result ($res,0,data_geracao);

echo "<br/><br/>";
echo "<table width='150' align='center' border='1' bordercolor='#C0C0C0' cellpadding='2' cellspacing='0' id='tabela2'>";
echo "<tr class='menu_top2'>";
echo "<td align='center' nowrap colspan='4'>Resumo Por Linha</td>";
echo "</tr>";
echo "<tr class='menu_top2'>";
echo "<td align='center' nowrap >Linha</td>";
echo "<td align='center' nowrap >MO.</td>";
echo "<td align='center' nowrap >Qtde</td>";
echo "<td align='center' nowrap >Valor</td>";
echo "</tr>";

$total_qtde            = 0 ;
$total_mo_posto        = 0 ;
for($i=0; $i<pg_num_rows($res); $i++){
	$linha_nome        = pg_fetch_result ($res,$i,linha_nome);
	$qtde              = number_format(pg_fetch_result ($res,$i,qtde),0,',','.');
	$mao_de_obra_posto = number_format(pg_fetch_result ($res,$i,mao_de_obra_posto),2,',','.');
	$unitario          = number_format(pg_fetch_result ($res,$i,unitario),2,',','.');

	$cor = ($i%2) ? "#FFFFFF" : "#CCCCFF";

	echo "<tr style='font-size: 10px' bgcolor='$cor'>";
	
	echo "<td nowrap >";
	echo $linha_nome;
	echo "</td>";

	echo "<td  nowrap align='right'>";
	echo $unitario;
	echo "</td>";

	echo "<td  nowrap align='right'>";
	echo $qtde;
	echo "</td>";

	echo "<td  nowrap align='right'>";
	echo $mao_de_obra_posto;
	echo "</td>";

	echo "</tr>";

	$total_qtde            += pg_fetch_result ($res,$i,qtde) ;
	$total_mo_posto        += pg_fetch_result ($res,$i,mao_de_obra_posto) ;
}

echo "<tr bgcolor='#FFFFFF' style='font-size:10px;  font-weight:bold;  ' >";
echo "<td align='center' colspan='2'>TOTAIS</td>";
echo "<td align='right'>" . number_format ($total_qtde           ,0,",",".") . "</td>";
echo "<td align='right'>" . number_format ($total_mo_posto       ,2,",",".") . "</td>";
echo "</tr>";
echo "<tr bgcolor='#FFFFFF' >";
echo "<td align='center' bgcolor='#ffffff' colspan='4'>&nbsp;</td>";
echo "</tr>";
echo "<tr class='menu_top2' border='0'>";
echo "<td align='center' nowrap colspan='4'>Resumo de Os's</td>";
echo "</tr>";
	 $sqls = "SELECT 
					COUNT(distinct os.os) AS qtde                      
				FROM 
					(SELECT tbl_os_extra.os 
					FROM tbl_os_extra 
					JOIN tbl_os      ON tbl_os_extra.os = tbl_os.os
                    LEFT JOIN tbl_os_status ON tbl_os.os = tbl_os_status.os AND tbl_os_status.os_status NOT IN(126,156, 143)
					WHERE finalizada IS NULL
					AND   excluida IS NOT TRUE
                    AND   status_os_ultimo isnull
					AND   fabrica = $login_fabrica
					AND   posto in (
							SELECT posto FROM tbl_extrato where extrato = $extrato
						)
					) os 
				JOIN tbl_os_extra ON os.os = tbl_os_extra.os ";
        //echo nl2br($sqls);
		$ress = pg_query($con,$sqls);
		echo "<tr bgcolor='#CCCCFF' style='font-size:9px;' >";
		echo "<td nowrap colspan='3'>QTDE OS ABERTA</td>";
		$qtde_aberta = (pg_num_rows($ress) > 0) ? pg_fetch_result($ress,0,qtde): 0;
		echo "<td  nowrap align='right'>$qtde_aberta</td>";
		echo "</tr>";
		echo "<tr bgcolor='#FFFFFF' style='font-size:9px; ' >";

		$sqls = "SELECT 
					COUNT(distinct os.os) AS qtde                      
				FROM 
					(SELECT tbl_os_extra.os 
					FROM tbl_os_extra 
					JOIN tbl_os      ON tbl_os_extra.os = tbl_os.os
                    LEFT JOIN tbl_os_status ON tbl_os.os = tbl_os_status.os AND tbl_os_status.os_status NOT IN(126,156, 143)
					WHERE  data_digitacao < CURRENT_DATE - interval '30 days'
					AND   excluida IS NOT TRUE
                    AND   status_os_ultimo isnull
					AND   finalizada is null
					AND   fabrica = $login_fabrica
					AND   posto in (
							SELECT posto FROM tbl_extrato where extrato = $extrato
						)
					) os 
				JOIN tbl_os_extra ON os.os = tbl_os_extra.os";
        //echo nl2br($sqls);
		$ress = pg_query($con,$sqls);

	echo "<td nowrap colspan='3'>QTDE > 30 ABERTA</td>";
	$qtde_aberta_30 = (pg_num_rows($ress) > 0) ? pg_fetch_result($ress,0,qtde): 0;
	echo "<td  nowrap align='right'>$qtde_aberta_30</td>";
	$total_qtde_os          =  $qtde_aberta+$qtde_aberta_30;
	echo "</tr>";
	echo "<tr bgcolor='#FFFFFF' style='font-size:10px;font-weight:bold ' >";
	echo "<td align='center' colspan='3'>TOTAIS</td>";
	echo "<td align='right'>" . $total_qtde_os . "</td>";
	echo "</tr>";
	echo "</table>";
echo "</div>";
echo "<br/><br/><br/><br/><br/>";
echo "<center>";
echo "<TABLE width='850' border='1' bordercolor='#C0C0C0' align='center' cellspacing='1' cellpadding='4'>";

$sql = "SELECT  tbl_extrato_conferencia.extrato_conferencia                        AS extrato_conferencia,
					tbl_extrato_conferencia.data_conferencia                           AS data_conferencia,
					to_char(tbl_extrato_conferencia.data_conferencia,'DD/MM/YYYY')     AS data,
					tbl_extrato_conferencia.nota_fiscal                                AS nota_fiscal,
					to_char(tbl_extrato_conferencia.data_nf,'DD/MM/YYYY')              AS data_nf,
					tbl_extrato_conferencia.valor_nf                                   AS valor_nf,
					tbl_extrato_conferencia.valor_nf_a_pagar                           AS valor_nf_a_pagar,
					tbl_extrato_conferencia.caixa                                      AS caixa,
					to_char(tbl_extrato_conferencia.previsao_pagamento,'DD/MM/YYYY')   AS previsao_pagamento,
					tbl_admin.login                                                    AS login             ,
					tbl_extrato_agrupado.codigo                                                                ,
					tbl_extrato_conferencia.obs_fabricante
			FROM tbl_extrato_conferencia
			JOIN tbl_admin   USING(admin)
			LEFT JOIN tbl_extrato_agrupado USING(extrato)
			WHERE tbl_extrato_conferencia.extrato = $extrato
			AND   cancelada IS NOT TRUE
			ORDER BY tbl_extrato_conferencia.data_conferencia";
$res = pg_query($con,$sql);
for ($i=0; $i<pg_num_rows($res); $i++) {
	$extrato_conferencia= pg_fetch_result($res,$i,extrato_conferencia);
	$data               = pg_fetch_result($res,$i,data);
	$nota_fiscal_posto  = pg_fetch_result($res,$i,nota_fiscal);
	$data_nf            = pg_fetch_result($res,$i,data_nf);
	$valor_nf           = pg_fetch_result($res,$i,valor_nf);
	$valor_nf_a_pagar   = pg_fetch_result($res,$i,valor_nf_a_pagar);
	$caixa              = pg_fetch_result($res,$i,caixa);
	$previsao_pagamento = pg_fetch_result($res,$i,previsao_pagamento);
	$admin              = pg_fetch_result($res,$i,login);
	$codigo_agrupado    = pg_fetch_result($res,$i,codigo);
	$obs_fabricante     = pg_fetch_result($res,$i,obs_fabricante);
	$valor_nf         = number_format($valor_nf,2,",",".");
	$valor_nf_a_pagar = number_format($valor_nf_a_pagar,2,",",".");
}
?>

	<TR class='menu_top2'>
		<TD colspan="6">RESUMO DE CONFERÊNCIA PARA PGTO</TD>
	</TR>
	<TR class='menu_top2'>
		<TD>Cod. Posto</TD>
		<TD>Posto</TD>
		<TD>Caixa Arq.</TD>
		<TD>Data Conferência</TD>
		<TD>Admin</TD>
	</TR>
	<TR style='font-size: 10px; text-align:center'>
		<TD><? echo $codigo_posto2; ?></TD>
		<TD><? echo $nome_posto2; ?></TD>
		<TD><? echo $caixa; ?></TD>
		<TD><? echo $data; ?></TD>
		<TD><? echo $admin; ?></TD>
	</TR>
	<TR class='menu_top2'>
		<TD>Nota Fiscal</TD>
		<TD>Data NF</TD>
		<TD>Valor NF</TD>
		<TD>Previsão de Pagamento</TD>
		<TD>Valor Total Agrupado</TD>
	</TR>
<?	
	$sql4 = "SELECT DISTINCT tbl_extrato.valor_agrupado,
			tbl_extrato.total
			from tbl_extrato
			join tbl_extrato_agrupado using(extrato)
			where tbl_extrato_agrupado.extrato= $extrato ";
	$res4 = @pg_query ($con,$sql4);
	if(@pg_num_rows($res4) > 0){
		$valor_agrupado   = trim(pg_fetch_result($res4,0,valor_agrupado));
		$total_extrato   = trim(pg_fetch_result($res4,0,total));
	}
	
	$sqlt = " SELECT sum(tbl_os_extra.mao_de_obra)  as total
			FROM tbl_extrato
			JOIN tbl_os_extra ON tbl_os_extra.extrato = tbl_extrato.extrato
			JOIN tbl_os USING(os)
			WHERE tbl_extrato.extrato = $extrato
			AND   tbl_extrato.fabrica = $login_fabrica
			AND   tbl_os.sinalizador = 1";
	$rest = pg_query($con,$sqlt);
	$total = pg_fetch_result($rest,0,total);

	$sql_av = " SELECT
			extrato,
			historico,
			valor,
			tbl_extrato_lancamento.admin,
			debito_credito,
			lancamento
		FROM tbl_extrato_lancamento
		JOIN tbl_extrato_agrupado USING(extrato)
		WHERE tbl_extrato_lancamento.extrato = $extrato
		AND   tbl_extrato_agrupado.extrato = $extrato
		AND fabrica = $login_fabrica
		AND (tbl_extrato_lancamento.admin IS NOT NULL OR lancamento in (103,104))";

	$res_av = pg_query ($con,$sql_av);

	if(pg_num_rows($res_av) > 0){
		for($i=0; $i < pg_num_rows($res_av); $i++){
			$extrato         = trim(pg_fetch_result($res_av, $i, extrato));
			$historico       = trim(pg_fetch_result($res_av, $i, historico));
			$valor           = trim(pg_fetch_result($res_av, $i, valor));
			$debito_credito  = trim(pg_fetch_result($res_av, $i, debito_credito));
			$lancamento      = trim(pg_fetch_result($res_av, $i, lancamento));
			
			if($debito_credito == 'D'){ 
				if ($lancamento == 78 AND $valor>0){
					$valor = $valor * -1;
				}
			}

			$total_avulso = $valor + $total_avulso;
		}
	}else{
		$total_avulso = 0 ;
	}
	
	$total += $total_avulso;

	if($total < 0) {
		$total = 0 ;
	}
 ?>

	<TR style='font-size: 10px; text-align:center'>
		<TD><? echo $nota_fiscal_posto; ?></TD>
		<TD><? echo $data_nf; ?></TD>
		<TD><? echo number_format ($valor_agrupado,2,",","."); ?></TD>
		<TD><? echo $previsao_pagamento; ?></TD>
		<TD><? echo number_format ($valor_agrupado,2,",","."); ?></TD>
	</TR>

	<TR class='menu_top2'>
		<TD>Código Agrupamento</TD>
		<TD>Valor Total Extrato</TD>
		<TD colspan='3'>Observação</TD>
	</TR>
	<TR style='font-size: 10px; text-align:center'>
		<TD align='center'><? echo $codigo_agrupado; ?></TD>
		<TD><? echo number_format ($total,2,",","."); ?></TD>
		<TD nowrap colspan='3'><?=$obs_fabricante?></TD>
	</TR>
</TABLE> 
<?


	
		$sql = " SELECT  
					extrato_nota_avulsa,
					nota_fiscal   ,
					valor_original,
					to_char(data_lancamento,'DD/MM/YYYY') as data_lancamento,
					to_char(data_emissao,'DD/MM/YYYY') as data_emissao,
					to_char(previsao_pagamento,'DD/MM/YYYY') as previsao_pagamento,
					login   ,
					observacao
				FROM tbl_extrato_nota_avulsa
				JOIN tbl_admin USING(admin)
				WHERE extrato = $extrato
				AND   tbl_extrato_nota_avulsa.fabrica = $login_fabrica ";
		$res = pg_query($con,$sql);
		if(pg_num_rows($res) > 0){
			echo "<br/>";
			echo "<TABLE width='850' border='1' bordercolor='#C0C0C0' align='center' cellspacing='1' cellpadding='4'>";
			echo "<TR class='menu_top2'>";
			echo "<TD colspan='6'>NOTA AVULSA</TD>";
			echo "</TR>";
			echo "<TR class='menu_top2'>";
				echo "<td>Data Lançamento</td>";
				echo "<td>Admin</td>";
				echo "<td>Nota Fiscal</td>";
				echo "<td>Data Emissão</td>";
				echo "<td>Valor Original</td>";
				echo "<td>Previsão Pagamento</td>";
			echo "</TR>";
			for($i =0;$i<pg_num_rows($res);$i++) {
				$extrato_nota_avulsa= pg_fetch_result($res,$i,extrato_nota_avulsa);
				$data_lancamento   = pg_fetch_result($res,$i,data_lancamento);
				$nota_fiscal       = pg_fetch_result($res,$i,nota_fiscal);
				$login             = pg_fetch_result($res,$i,login);
				$data_emissao      = pg_fetch_result($res,$i,data_emissao);
				$observacao        = pg_fetch_result($res,$i,observacao);
				$valor_original    = number_format(pg_fetch_result($res,$i,valor_original),2,",","."); 
				$previsao_pagamento= pg_fetch_result($res,$i,previsao_pagamento);

				echo "<tr style='font-size: 10px;text-align:center'>";
				echo "<td>$data_lancamento</td>";
				echo "<td>$login</td>";
				echo "<td>$nota_fiscal</td>";
				echo "<td>$data_emissao</td>";
				echo "<td>$valor_original</td>";
				echo "<td nowrap>$previsao_pagamento</td>";
				echo "</tr>";
				echo "<tr><td colspan='2' style='text-align:center; font-size: x-small;font-weight: bold;color:#ffffff; background-color: #596D9B'>Observação</td><td colspan='4' style='text-align:center; font-size: x-small;' nowrap>$observacao</td></tr>";
			}
			
		}
	echo "</table>";

/****************************resumo****************************/
/*echo "<br><br>";
$sql = "SELECT	tbl_linha.nome AS linha_nome         ,
				tbl_linha.linha                      ,
				tbl_os_extra.mao_de_obra AS unitario ,
				tbl_sinalizador_os.acao                  ,
				COUNT(CASE WHEN os.sinalizador =1     THEN 1 ELSE NULL END) AS qtde                      ,
				SUM  (CASE WHEN os.sinalizador =1 THEN tbl_os_extra.mao_de_obra           ELSE 0 END) AS mao_de_obra_posto     ,
				tbl_os_extra.mao_de_obra           AS mao_de_obra_posto_unitaria     ,
				SUM  (CASE WHEN tbl_os_extra.mao_de_obra_desconto IS NULL THEN tbl_os_extra.mao_de_obra_adicional ELSE 0 END) AS mao_de_obra_adicional ,
				SUM  (CASE WHEN tbl_os_extra.mao_de_obra_desconto IS NULL THEN tbl_os_extra.adicional_pecas       ELSE 0 END) AS adicional_pecas       ,
				to_char (tbl_extrato.data_geracao, 'DD/MM/YYYY') AS data_geracao  ,
				distrib.nome_fantasia AS distrib_nome                                  ,
				distrib.posto    AS distrib_posto                                 
		FROM
			(SELECT tbl_os_extra.os,tbl_os.sinalizador
			FROM tbl_os_extra 
			JOIN tbl_os      ON tbl_os_extra.os = tbl_os.os
			WHERE tbl_os_extra.extrato = $extrato
			) os 
		JOIN tbl_os_extra ON os.os = tbl_os_extra.os
		JOIN tbl_extrato  ON tbl_os_extra.extrato = tbl_extrato.extrato
		JOIN tbl_linha    ON tbl_os_extra.linha = tbl_linha.linha
		JOIN tbl_sinalizador_os on os.sinalizador = tbl_sinalizador_os.sinalizador
		LEFT JOIN tbl_posto distrib ON tbl_os_extra.distribuidor = distrib.posto
		WHERE os.sinalizador = 1
		GROUP BY tbl_linha.linha, tbl_linha.nome, tbl_os_extra.mao_de_obra, tbl_sinalizador_os.acao,tbl_extrato.data_geracao, distrib.nome_fantasia, distrib.posto, tbl_os_extra.mao_de_obra
		ORDER BY tbl_linha.nome";
$res = pg_query ($con,$sql);

echo "<form style='MARGIN: 0px; WORD-SPACING: 0px' name='frm_conferencias' method='post' action='$PHP_SELF?posto=$posto'>";
echo "<table width='850' align='center' border='1' bordercolor='#C0C0C0' cellpadding='2' cellspacing='0'><TR class='menu_top2'><TD colspan='6'>RESUMO DE CONFERÊNCIA</TD></TR>";
echo "<tr class='menu_top2'>";
echo "<td align='center' nowrap >Sinalizador</td>";
echo "<td align='center' nowrap >Linha</td>";
echo "<td align='center' nowrap >M.O.Unit.</td>";
echo "<td align='center' nowrap >Qtde</td>";
echo "<td align='center' nowrap >Recusada</td>";
echo "<td align='center' nowrap >Mão-de-Obra</td>";
echo "</tr>";

$total_qtde            = 0 ;
$total_mo_posto        = 0 ;
$total_qtde_recusada            = 0 ;
$total_mo_adicional    = 0 ;
$total_adicional_pecas = 0 ;

$qtde_item_enviada = pg_num_rows($res);

for($i=0; $i<pg_num_rows($res); $i++){
	$acao_sinalizador             = pg_fetch_result ($res,$i,acao);
	$linha_nome        = pg_fetch_result ($res,$i,linha_nome);
	$linha             = pg_fetch_result ($res,$i,linha);
	$unitario          = number_format(pg_fetch_result ($res,$i,unitario),2,',','.');
	$qtde              = number_format(pg_fetch_result ($res,$i,qtde),0,',','.');
	$mao_de_obra_a_pagar += number_format(pg_fetch_result ($res,$i,mao_de_obra_posto),2,',','.');
	$mao_de_obra_posto = number_format(pg_fetch_result ($res,$i,mao_de_obra_posto),2,',','.');
	$mao_de_obra_posto_unitaria = number_format(pg_fetch_result ($res,$i,mao_de_obra_posto_unitaria),2,',','.');

	$sqll = " SELECT count(*)
			from tbl_os_extra
			join tbl_os using(os)
			WHERE extrato = $extrato
			and tbl_os_extra.linha = $linha
			and tbl_os_extra.mao_de_obra= ".pg_fetch_result ($res,$i,unitario)."
			and tbl_os_extra.mao_de_obra_desconto is not null";
	$resl = pg_query($con,$sqll);

	$qtde_recusada = (pg_num_rows($resl) > 0) ? pg_fetch_result($resl,0,0) : 0;

	$cor = "#FFFFFF";
	if ($i % 2 == 0) $cor = "#FEF2C2";

	echo "<tr bgcolor='$cor' style='font-size: 10px'>";

	echo "<td nowrap >";
	echo $acao_sinalizador;
	echo "</td>";

	echo "<td nowrap >";
	echo $linha_nome;
	echo "</td>";

	echo "<td  nowrap align='right'>";
	echo $unitario;
	echo "</td>";

	echo "<td  nowrap align='right'>";
	echo $qtde;
	echo "</td>";

	echo "<td  nowrap align='right'>";
	echo $qtde_recusada;
	echo "</td>";
	

	echo "<td  nowrap align='right'>";
	echo $mao_de_obra_posto;
	echo "</td>";

	echo "</tr>";
	$total_qtde            += pg_fetch_result ($res,$i,qtde) ;
	$total_qtde_recusada   += $qtde_recusada;
	$total_mo_posto        += pg_fetch_result ($res,$i,mao_de_obra_posto) ;

}*/
$sql = " SELECT
		extrato,
		historico,
		valor,
		admin,
		debito_credito,
		lancamento
	FROM tbl_extrato_lancamento
	WHERE extrato = $extrato
	AND fabrica = $login_fabrica
	AND (admin IS NOT NULL OR lancamento in (103,104))";

$res = pg_query ($con,$sql);

$total_avulso = 0;

if(pg_num_rows($res) > 0){
echo "<table width='850' align='center' border='1' bordercolor='#C0C0C0' cellpadding='2' cellspacing='0'><TR class='menu_top2'><TD colspan='5'>AVULSOS</TD></TR>";

	for($i=0; $i < pg_num_rows($res); $i++){
		$extrato         = trim(pg_fetch_result($res, $i, extrato));
		$historico       = trim(pg_fetch_result($res, $i, historico));
		$valor           = trim(pg_fetch_result($res, $i, valor));
		$debito_credito  = trim(pg_fetch_result($res, $i, debito_credito));
		$lancamento      = trim(pg_fetch_result($res, $i, lancamento));
		
		if($debito_credito == 'D'){ 
			$bgcolor= "bgcolor='#FF0000'"; 
			$color = " color: #000000; ";
			if ($lancamento == 78 AND $valor>0){
				$valor = $valor * -1;
			}
		}else{ 
			$bgcolor= "bgcolor='#0000FF'";
			$color = " color: #FFFFFF; ";
		}

		//hd 22096 - lançamentos e Valores de ajuste de Extrato
		if ($lancamento==103 or $lancamento==104) {
			$bgcolor= "bgcolor='#339900'";
		}

		echo "<tr style='font-size: 10px; $color' $bgcolor>";
		echo "<TD><b>Avulso</b></TD>";
		echo "<TD colspan='3'><b>$historico</b></TD>";
		echo "<TD align='right'><b>".number_format ($valor ,2,",",".")."</b>
		</TD>";
		echo "</tr>";
		$total_avulso = $valor + $total_avulso;
	}
}

$total_nota	= ($mao_de_obra_a_pagar+$total_avulso);

echo "<TR class='menu_top2'><TD colspan='3'>TOTAL</TD><td style='text-align:right'></td><TD align='right'>".number_format ($total_nota,2,",",".")."</TD></TR></table>";

//------------------------------------resumo irregulares
echo "<br>";
$sql = "SELECT	tbl_linha.nome AS linha_nome         ,
				tbl_linha.linha                      ,
				tbl_os_extra.mao_de_obra AS unitario ,
				tbl_sinalizador_os.acao              ,
				COUNT(tbl_os.os) AS qtde                      ,
				SUM  (tbl_os_extra.mao_de_obra ) AS mao_de_obra_posto     ,
				to_char (tbl_extrato.data_geracao, 'DD/MM/YYYY') AS data_geracao  
		FROM
			(SELECT tbl_os_extra.os 
			FROM tbl_os_extra 
			JOIN tbl_os      ON tbl_os_extra.os = tbl_os.os
			WHERE tbl_os_extra.extrato = $extrato
			) os 
		JOIN tbl_os_extra ON os.os = tbl_os_extra.os
		JOIN tbl_extrato  ON tbl_os_extra.extrato = tbl_extrato.extrato
		JOIN tbl_linha    ON tbl_os_extra.linha = tbl_linha.linha
		JOIN tbl_os      ON tbl_os_extra.os = tbl_os.os
		JOIN tbl_sinalizador_os    ON tbl_sinalizador_os.sinalizador = tbl_os.sinalizador 
		WHERE tbl_sinalizador_os.debito='S' and tbl_os.sinalizador <> 3
		GROUP BY tbl_linha.linha, tbl_linha.nome, tbl_os_extra.mao_de_obra, tbl_extrato.data_geracao, tbl_os_extra.mao_de_obra, tbl_sinalizador_os.acao 
		ORDER BY tbl_linha.nome";

$res = @pg_query ($con,$sql);
if(@pg_num_rows($res) > 0){
	

echo "<form style='MARGIN: 0px; WORD-SPACING: 0px' name='frm_conferencias' method='post' action='$PHP_SELF?posto=$posto'>";
echo "<table width='850' align='center' border='1' bordercolor='#C0C0C0' cellpadding='2' cellspacing='0'><TR class='menu_top2'><TD colspan='5'>RESUMO DE CONFERÊNCIA COM IRREGULARIDADE</TD></TR>";
echo "<tr class='menu_top2'>";
echo "<td align='center' nowrap >Sinalizador</td>";
echo "<td align='center' nowrap >Linha</td>";
echo "<td align='center' nowrap >M.O.Unit.</td>";
echo "<td align='center' nowrap >Qtde</td>";
echo "<td align='center' nowrap >Mão-de-Obra</td>";
echo "</tr>";

$total_qtde            = 0 ;
$total_mo_posto        = 0 ;
$total_mo_adicional    = 0 ;
$total_adicional_pecas = 0 ;

$qtde_item_enviada = pg_num_rows($res);

for($i=0; $i<pg_num_rows($res); $i++){
	$acao_sinalizador  = pg_fetch_result ($res,$i,acao);
	$linha             = pg_fetch_result ($res,$i,linha);
	$linha_nome        = pg_fetch_result ($res,$i,linha_nome);
	$unitario          = number_format(pg_fetch_result ($res,$i,unitario),2,',','.');
	$qtde              = number_format(pg_fetch_result ($res,$i,qtde),0,',','.');
	$mao_de_obra_posto = number_format(pg_fetch_result ($res,$i,mao_de_obra_posto),2,',','.');
	$cor = "#FFFFFF";
	if ($i % 2 == 0) $cor = "#FEF2C2";

	echo "<tr bgcolor='$cor' style='font-size: 10px'>";

	echo "<td nowrap >";
	echo $acao_sinalizador;
	echo "</td>";

	echo "<td nowrap >";
	echo $linha_nome;
	echo "</td>";

	echo "<td  nowrap align='right'>";
	echo $unitario;
	echo "</td>";

	echo "<td  nowrap align='right'>";
	echo $qtde;
	echo "</td>";

	echo "<td  nowrap align='right'>";
	echo $mao_de_obra_posto;
	echo "</td>";
	echo "</tr>";

	$total_qtde            += pg_fetch_result ($res,$i,qtde) ;
	$total_mo_posto        += pg_fetch_result ($res,$i,mao_de_obra_posto) ;

}

echo "<TR class='menu_top2'><TD colspan='3'>TOTAL SEM PAGAR</TD><td style='text-align:right'>$total_qtde</td><TD align='right'>".number_format ($total_mo_posto,2,",",".")."</TD></TR></table>";

echo "</table>";
echo "</center>";
}else{
	echo "<center><h1 style='font-weight:bold'>Extrato sem irregularidades</h1></center>";
}
//------------------------------------------ pendentes de conferencia

echo "<p style='text-align:center; font-size:14px'><center >$codigo_posto2 - $nome_posto2 - $data_geracao_extrato</center></p>";
?>


<? include_once "rodape_print.php"; ?>
<?php 
	if($imprimir == 'sim'){
			echo "<script language='JavaScript'>
					window.print();
			</script>";
	}
 ?>
