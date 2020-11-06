<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="financeiro";
include 'autentica_admin.php';
include 'funcoes.php';

$layout_menu = "financeiro";
$title = "Comparativo anual de promedio de extractos";

include 'cabecalho.php';

?>

<style type="text/css">
.Titulo {
	text-align: center;
	font-family: Arial;
	font-size: 10px;
	font-weight: bold;
	color: #FFFFFF;
	background-color: #485989;
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
.Caixa{
	BORDER-RIGHT: #6699CC 1px solid; 
	BORDER-TOP: #6699CC 1px solid; 
	FONT: 8pt Arial ;
	BORDER-LEFT: #6699CC 1px solid; 
	BORDER-BOTTOM: #6699CC 1px solid; 
	BACKGROUND-COLOR: #FFFFFF
}
.Exibe{
	font-family: Arial, Helvetica, sans-serif;
	font-size: 8 px;
	font-weight: none;
	color: #000000;
	text-align: center;
}
</style>
<script>
function fnc_pesquisa_posto2 (campo, campo2, tipo) {
	if (tipo == "codigo" ) {
		var xcampo = campo;
	}

	if (tipo == "nome" ) {
		var xcampo = campo2;
	}

	if (xcampo.value != "") {
		var url = "";
		url = "posto_pesquisa_2.php?campo=" + xcampo.value + "&tipo=" + tipo ;
		janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=600, height=400, top=18, left=0");
		janela.codigo  = campo;
		janela.nome    = campo2;
		janela.focus();
	}
}

</script>


<FORM name='frm' METHOD='POST' ACTION='<?=$PHP_SELF?>' align='center'>
<table width='500' class='Conteudo' style='background-color: #485989' border='0' cellpadding='5' cellspacing='1' align='center'>
	
	<tr>
		<td class='Titulo' background='imagens_admin/azul.gif'>Comparativo anual de promedio de extractos</td>
	</tr>
	
	<tr>
		<td bgcolor='#DBE5F5'>
	
			<table width='100%' border='0' cellspacing='1' cellpadding='2' class='Conteudo'>

	
				<tr width='100%' >
					<td colspan='2' align='right' height='20'>Código Servicio:&nbsp;</td>
					<td colspan='2' align='left'>
						<input class='Caixa' type='text' name='codigo_posto' size='10' value='<?=$codigo_posto?>'> &nbsp;<img src='imagens_admin/btn_lupa.gif' border='0' align='absmiddle' style="cursor:pointer" onclick="javascript: fnc_pesquisa_posto2 (document.frm.codigo_posto,document.frm.posto_nome,'codigo')"></A>
					</td>	
				</tr>
				<tr>
					<td colspan='2' align='right'>Razón Social:&nbsp;</td>
					<td colspan='2' align='left'><input class="Caixa" type="text" name="posto_nome" size="30" value="<? echo $posto_nome ?>" >&nbsp;<img src='imagens_admin/btn_lupa.gif' style="cursor:pointer" border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_posto2 (document.frm.codigo_posto,document.frm.posto_nome,'nome')" style="cursor:pointer;"></A>
					</td>
				</tr>
			</table>
		</td>
	</tr>
</table>
<center><br><input type='submit' name='btn_acao' value='Buscar'></center>
<?




$btn_acao     = $_POST["btn_acao"];
$codigo_posto = $_POST["codigo_posto"];


if(strlen($btn_acao)>0 AND strlen($codigo_posto)>0){

	$data_serv  = mktime (date("H"), date("i"), date("s"),date("m")-12  , date("d"), date("Y"));
	$data_inicial = strftime ("%Y-%m-%d", $data_serv);
	
	$xdata_inicial = $data_inicial .' 00:00:00';
	$xdata_final = date("Y-m-d 23:59:59");

	$sql = "SELECT posto 
			FROM tbl_posto_fabrica 
			WHERE fabrica    = $login_fabrica 
			AND codigo_posto = '$codigo_posto'";
	$res = pg_exec($con,$sql);
	if (pg_numrows($res) > 0) {
		$posto = trim(pg_result($res,0,posto));
	}else{
		$msg_erro .= "Ninguno Servicio encuentrada";
	}

	if (strlen($msg_erro)==0){

		$sql = "SELECT   
				SUM(pecas + mao_de_obra + avulso)    AS total        ,
				to_char(data_geracao,'YYYY-MM')      AS data_geracao ,
				tbl_posto.posto                                      ,
				tbl_posto.nome                       AS posto_nome   ,
				tbl_posto_fabrica.codigo_posto
			FROM tbl_extrato 
			JOIN (
				SELECT extrato
				FROM tbl_extrato 
				JOIN tbl_posto   USING(posto)
				WHERE tbl_extrato.fabrica = $login_fabrica
				AND   tbl_extrato.posto   = $posto
				AND   tbl_extrato.aprovado IS NOT NULL
				AND   tbl_posto.pais = '$login_pais'
				AND   TO_CHAR(CURRENT_DATE,'YYYY-MM') <> to_char(data_geracao,'YYYY-MM') 
				AND   tbl_extrato.data_geracao BETWEEN '$xdata_inicial' AND '$xdata_final'
			) ext                   ON ext.extrato       = tbl_extrato.extrato
			JOIN tbl_posto          ON tbl_extrato.posto = tbl_posto.posto 
			JOIN tbl_posto_fabrica  ON tbl_posto.posto   = tbl_posto_fabrica.posto 
						AND tbl_posto_fabrica.fabrica = $login_fabrica 
			GROUP BY	TO_CHAR(data_geracao,'YYYY-MM'),
						tbl_posto.posto                 ,
						tbl_posto.nome                  ,
						tbl_posto_fabrica.codigo_posto  
			ORDER BY TO_CHAR(data_geracao,'YYYY-MM');";

		$res = pg_exec($con,$sql);

		if (pg_numrows($res) > 0) {
			$posto           = trim(pg_result($res,0,posto))       ;
			$posto_nome      = trim(pg_result($res,0,posto_nome))  ;
			$codigo_posto    = trim(pg_result($res,0,codigo_posto));

			echo "<br><table border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#d2e4fc'  align='center'>";
			echo "<tr class='Titulo'>";
			echo "<td width='100' rowspan='2' >Valor Pago/Mes</td>";
			echo "<td colspan='12'>Meses</td>";
			echo "<td rowspan='2' class='Mes'>Total Año</td>";
			echo "</tr><tr class='Titulo'>";
			for($x=0;$x<12;$x++){
		
				$data_serv  = mktime (date("H"), date("i"), date("s"),date("m")- 12 + $x , date("d"), date("Y"));
				$mes[$x]    = strftime ("%m/%Y", $data_serv);
				
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


			for ($i=0; $i<pg_numrows($res); $i++){
		
				$posto           = trim(pg_result($res,$i,posto));
				$data_geracao    = trim(pg_result($res,$i,data_geracao));
				$total           = trim(pg_result($res,$i,total));

		
				
				$xdata_geracao = explode('-',$data_geracao);
				$data_geracao = $xdata_geracao[1].'/'.$xdata_geracao[0];

				if($posto_anterior<>$posto){

		//IMPRIME O VETOR COM OS DOZE MESES E LOGO APÓS PULA LINHA E ESCREVE O NOVO PRODUTO
					if($i<>0 ){
		
						for($a=0;$a<12;$a++){			//imprime os doze meses
							echo "<td bgcolor='$cor' title='".$mes[$a]."'>";
							if ($qtde_mes[$y][$a]>0)
								echo "<font color='#000000'><b>$ ".number_format($qtde_mes[$y][$a],2,',','.');
							else echo "<font color='#999999'> ";
		
							echo "</td>";
							$total_ano = $total_ano + $qtde_mes[$y][$a];
							if($a==11) {
								$total_ano = number_format($total_ano,2,',','.');
								echo "<td bgcolor='$cor' >$ $total_ano</td>";
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
				
				if($i==(pg_numrows($res)-1)){
					for($a=0;$a<12;$a++){			//imprime os doze meses
						echo "<td bgcolor='$cor' title='".$mes[$a]."'>";
						if ($qtde_mes[$y][$a]>0)
							echo "<font color='#000000'>$ ".number_format($qtde_mes[$y][$a],2,',','.');
						else echo "<font color='#999999'> ";
		
						echo "</td>";
						$total_ano = $total_ano + $qtde_mes[$y][$a];
						if($a==11) {
							$total_ano = number_format($total_ano,2,',','.');
							echo "<td bgcolor='$cor' >$ $total_ano</td>";
							echo "</tr>";
						}	// se for o ultimo mes quebra a linha
					}
				
				}
				$posto_anterior=$posto;

		
			}

			flush();
		
			$data_serv  = mktime (date("H"), date("i"), date("s"),date("m")-12  , date("d"), date("Y"));
			$data_inicial = strftime ("%Y-%m-%d", $data_serv);
			
			$xdata_inicial = $data_inicial .' 00:00:00';
			$xdata_final = date("Y-m-d 23:59:59");
		
			$sql = "SELECT   SUM(pecas+mao_de_obra+avulso)                           AS total        ,
							to_char(data_geracao,'YYYY-MM')      AS data_geracao 
					FROM tbl_extrato 
					WHERE tbl_extrato.fabrica          = $login_fabrica 
					AND tbl_extrato.aprovado IS NOT NULL
					AND to_char(data_geracao,'YYYY-MM') IN (
						SELECT DISTINCT to_char(data_geracao,'YYYY-MM') 
						FROM tbl_extrato
						WHERE tbl_extrato.fabrica = $login_fabrica
						AND to_char(CURRENT_DATE,'YYYY-MM')<> to_char(data_geracao,'YYYY-MM') 
						AND tbl_extrato.data_geracao BETWEEN '$xdata_inicial' AND '$xdata_final'
					)
					AND   tbl_extrato.data_geracao BETWEEN '$xdata_inicial' AND '$xdata_final'
					GROUP BY to_char(data_geracao,'YYYY-MM')
					ORDER BY to_char(data_geracao,'YYYY-MM');";
			#Novo SQL
			$sql = "SELECT	SUM(pecas + mao_de_obra + avulso) AS total,
							to_char(data_geracao,'YYYY-MM')   AS data_geracao
					FROM tbl_extrato 
					JOIN (
						SELECT extrato
						FROM tbl_extrato 
						JOIN tbl_posto   USING(posto)
						WHERE tbl_extrato.fabrica = $login_fabrica
						AND   tbl_extrato.aprovado IS NOT NULL
						AND   tbl_posto.pais = '$login_pais'
						AND   TO_CHAR(CURRENT_DATE,'YYYY-MM') <> to_char(data_geracao,'YYYY-MM') 
						AND   tbl_extrato.data_geracao BETWEEN '$xdata_inicial' AND '$xdata_final'
					) ext ON ext.extrato       = tbl_extrato.extrato
					GROUP BY TO_CHAR(data_geracao,'YYYY-MM')
					ORDER BY TO_CHAR(data_geracao,'YYYY-MM');";

			$res = pg_exec($con,$sql);
			if (pg_numrows($res) > 0) {
		
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
				$qtde_mes2[$posto_total2][12] = "Promédio";
		
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
				$qtde_posto2[$posto_total2][12] = "Promédio";
				
				$x = 0;
		
		
		
				for ($i=0; $i<pg_numrows($res); $i++){
			
					$data_geracao    = trim(pg_result($res,$i,data_geracao));
					$total           = trim(pg_result($res,$i,total));
		
					$sql2 = "SELECT  count(*) ,
								posto 
							FROM tbl_extrato 
							JOIN tbl_posto USING(posto)
							WHERE fabrica        = $login_fabrica 
							AND   tbl_posto.pais = '$login_pais'
							AND  to_char(data_geracao,'YYYY-MM') ='$data_geracao'
							GROUP BY posto;";
		
					$xdata_geracao = explode('-',$data_geracao);
					$data_geracao = $xdata_geracao[1].'/'.$xdata_geracao[0];
		
					$res2 = pg_exec($con,$sql2);
					if (pg_numrows($res2) > 0) {
						$postos_digitaram[$i] = pg_numrows($res2);
						$media_mes[$i] = $total / $postos_digitaram[$i];
						//echo "data: $data_geracao".$postos_digitaram[$i].' = '.$media_mes[$i].'<br>';
					}
		
			
					$cor = '#F7F5F0';
		
					if($i==0){
						echo "<tr class='Conteudo'align='center'>";
						echo "<td bgcolor='$cor' width='150'  height = '40'><b>Promédio</b></td>";
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
					
					if($i==(pg_numrows($res)-1)){
						for($a=0;$a<12;$a++){			//imprime os doze meses
							echo "<td bgcolor='$cor' title='".$mes[$a]."'>";
							if ($qtde_mes2[$y][$a]>0)
								echo "<font color='#000000'>$ ".number_format($qtde_mes2[$y][$a],2,',','.');
							else echo "<font color='#999999'> ";
			
							echo "</td>";
							$total_ano2 = $total_ano2 + $qtde_mes2[$y][$a];
							if($a==11) {
								$total_ano2 = number_format($total_ano2,2,',','.');
								echo "<td bgcolor='$cor' >$ $total_ano2</td>";
								echo "</tr>";
		
								//TOTAL DE POSTOS
								echo "<tr class='Conteudo'align='center'>";
								for($a=0;$a<12;$a++){
									if($a==0) echo "<td bgcolor='$cor'><b>Total de servicios</b></td>";
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
			include "posto_extrato_ano_grafico.php";
		}else{
			$msg_erro .= "Ninguno extracto durante este período";
		}
	}

	if (strlen($msg_erro)>0){
		echo "<p>".$msg_erro."</p>";
	}
}













include 'rodape.php';
?>
