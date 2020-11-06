<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';

if($login_fabrica<>10){
	header ("Location: index.php");
}
$TITULO = "ADM - Relatório de Digitação de OS's Latina";
?>
<style type="text/css">
.Titulo {
	text-align: center;
	font-family: Arial;
	font-size: 13px;
	font-weight: bold;
	color: #FFFFFF;
	background-color: #485989;
}
.Conteudo {
	font-family: Arial;
	font-size: 12px;
	font-weight: normal;
}
.ConteudoBranco {
	font-family: Arial;
	font-size: 12px;
	color:#FFFFFF;
	font-weight: normal;
}
.Mes{
	font-size: 12px;
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
<?
include "menu.php";

	echo "<FORM METHOD='get' ACTION='$PHP_SELF'>";
	echo "<table width = '700' align = 'center' cellpadding='0' cellspacing='0' border='0'>";
	echo "<tr>";
	echo "	<td background='/assist/helpdesk/imagem/fundo_tabela_top_esquerdo_azul_claro.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";
	echo "	<td background='/assist/helpdesk/imagem/fundo_tabela_top_centro_azul_claro.gif'   colspan='8' align = 'center' width='100%' style='font-family: arial ; color:#666666'>&nbsp;<b>Relatório de digitação de OS's Latina</b></td>";
	echo "	<td background='/assist/helpdesk/imagem/fundo_tabela_top_direito_azul_claro.gif'  ><img src='/assist/imagens/pixel.gif' width='9'></td>";
	echo "</tr>";
	
	echo "<tr style='font-family: verdana ; font-size:12px; color: #666666'>";
	echo "	<td background='/assist/helpdesk/imagem/fundo_tabela_centro_esquerdo.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";
	echo "	<td td colspan = '4' > <br>Mes: ";


		echo "<select class='frm' style='width: 200px;' name='mes'>\n";
			echo "<option value=''> - Escolha o mes - </option>";
			echo "<option value='01-Janeiro'>Janeiro</option>";
			echo "<option value='02-Fevereiro'>Fevereiro</option>";
			echo "<option value='03-Março'>Março</option>";
			echo "<option value='04-Abril'>Abril</option>";
			echo "<option value='05-Maio'>Maio</option>";
			echo "<option value='06-Junho'>Junho</option>";
			echo "<option value='07-Julho'>Julho</option>";
			echo "<option value='08-Agosto'>Agosto</option>";
			echo "<option value='09-Setembro'>Setembro</option>";
			echo "<option value='10-Outubro'>Outubro</option>";
			echo "<option value='11-Novembro'>Novembro</option>";
			echo "<option value='12-Dezembro'>Dezembro</option>";
		echo "</select><BR>";
	
	echo "<br></td>";
	echo "	<td colspan = '4' > <br>Ano: ";
		echo "<select class='frm' style='width: 200px;' name='ano'>\n";
			echo "<option value=''> - Escolha o ano - </option>";
			echo "<option value='2006'>2006</option>";
			echo "<option value='2007'>2007</option>";
		echo "</select><BR>";

	
	echo "<br></td>";

	
	echo "	<td background='/assist/helpdesk/imagem/fundo_tabela_centro_direito.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";
	echo "</tr>";

#botao submit
	echo "<tr>";
	echo "	<td background='/assist/helpdesk/imagem/fundo_tabela_centro_esquerdo.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";
	echo "<td colspan='8'><CENTER><INPUT TYPE=\"submit\" value=\"Pesquisar\" name='acao'></CENTER></td>";
//	echo "	<td></td>";
	echo "	<td background='/assist/helpdesk/imagem/fundo_tabela_centro_direito.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";
	echo "</tr>";

#===========================

	echo "<tr>";
	echo "	<td background='/assist/helpdesk/imagem/fundo_tabela_baixo_esquerdo.gif'><img src='/assist/imagens/pixel.gif' width='9'></td>";
	echo "	<td background='/assist/helpdesk/imagem/fundo_tabela_baixo_centro.gif' colspan='8' align = 'center' width='100%'></td>";
	echo "	<td background='/assist/helpdesk/imagem/fundo_tabela_baixo_direito.gif'><img src='/assist/imagens/pixel.gif' width='9'></td>";
	echo "</tr>";
	echo "</table>";
	echo "</FORM>";








if(strlen($_GET["acao"])>0){
	$mes = $_GET["mes"];

	$ano = $_GET["ano"];

	$mes           = explode('-',$mes);
	$mes_descricao = $mes[1]          ;
	$mes           = $mes[0]          ;


	$data_inicial = $ano . "-" . $mes . "-01 00:00:00";
	$data_final   = pg_result (pg_exec ($con,"SELECT ('$data_inicial'::date + INTERVAL '1 month' - INTERVAL '1 day')::date "),0,0) . " 23:59:59";

	$sql = "SELECT  count(os)                        AS total_digitada,
			tbl_os.admin                                      ,
			login                                             ,
			to_char(data_digitacao,'DD')AS data_digitacao
		FROM tbl_os
		JOIN tbl_admin ON tbl_admin.admin = tbl_os.admin 
			AND tbl_admin.fabrica = 15
		WHERE tbl_os.admin IN 
			(
			SELECT admin 
			FROM  tbl_admin
			WHERE fabrica = 15 
			AND login ILIKE '%latina'
			) 
		AND tbl_os.fabrica = 15
		AND tbl_os.data_digitacao BETWEEN '$data_inicial' AND '$data_final'
		GROUP BY  tbl_os.admin                   ,
			  login                          ,
			  to_char(data_digitacao,'DD') 
		ORDER BY admin,login;";

//	echo "$sql";

	$res = pg_exec ($con,$sql);

	if (pg_numrows($res) > 0) {
	
		echo "<br><table border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#d2e4fc' align='center'>";


		$x_data_final  = explode('-',$data_final);
		$x_data_final  = substr($x_data_final[2],0,2);
		for($x=0 ; $x < $x_data_final ; $x++){
			if($x == 0){
				$y = 0;
				$colunas = $x_data_final + 2;
				echo "<tr class='Titulo'>";
				echo "<td colspan='$colunas'background='../admin/imagens_admin/azul.gif' height='20'><font size='2'>TOTAL DE OS'S DIGITADAS - $mes_descricao/$ano</font></td>";
				echo "</tr>";
		
				echo "<tr class='Titulo'>";
				echo "<td >USUÁRIOS</td>";
			}	
			$y = $x+1;
			if($y<10) $y="0".$y;
			$dia_mes[$x] = $y;
			echo "<td class='Mes'>$dia_mes[$x]</td>";
		}
		echo "<td class='Mes'>TOTAL</td>";
		echo "</tr>";
	

	$x = 0;
	$y = 0;

	
	$qtde_digitada =  array();

	$total_mes = 0;
	$total_ano = 0;

	$admins_total = 0;

	for ($i=0; $i<pg_numrows($res); $i++){

		$admin  = trim(pg_result($res,$i,admin))          ;
		$login  = trim(pg_result($res,$i,login));

		if($admin_anterior<>$admin){
			for($x=0 ; $x < $x_data_final ; $x++){
				$qtde_digitada[$admins_total][$x]  = 0;
			}
			$qtde_digitada[$admins_total][$total_defeito] = $login;
			$admin_anterior = $admin;
			$admins_total   = $admins_total + 1;
		}
	}
//echo "foi";print_r($qtde_digitada);exit;

	for ($i=0; $i<pg_numrows($res); $i++){

		$admin            = trim(pg_result($res,$i,admin))           ;
		$login            = trim(pg_result($res,$i,login))           ;
		$data_digitacao   = trim(pg_result($res,$i,data_digitacao))  ;
		$total_digitada   = trim(pg_result($res,$i,total_digitada))  ;

		if($admin_anterior<>$admin){

//IMPRIME O VETOR COM OS DOZE MESES E LOGO APÓS PULA LINHA E ESCREVE O NOVO PRODUTO
			if($i<>0 AND $admin_anterior<>$admin ){

				for($a = 0 ;$a < $x_data_final ; $a++ ){
					echo "<td bgcolor='$cor' title='Dia ".$dia_mes[$a]." de $mes_descricao de $ano'>";

					if ($qtde_digitada[$y][$a]>0) echo "<font color='#000000'><b>".$qtde_digitada[$y][$a];
					else                         echo "<font color='#999999'> ";

					echo "</td>";
				
					$total_ano = $total_ano + $qtde_digitada[$y][$a];
					if($a==($x_data_final-1)){
						echo "<td bgcolor='$cor' ><b>$total_ano</b></td>";
						echo "</tr>";
					}
				}

				$y=$y+1;// usado para indicação de produto
			}

			if($cor=="#F1F4FA")$cor = '#F7F5F0';
			else               $cor = '#F1F4FA';

			echo "<tr class='Conteudo'align='center'>";
			echo "<td bgcolor='$cor' width='100'  height = '40' align='left'>$admin - $login</td>";

			$x=0; //ZERA OS MESES
			$total_ano = 0;
			$admin_anterior=$admin; 
		}

		while(trim($dia_mes[$x]) <> $data_digitacao){ //repete o lup até que o mes seja igual e anda um mes.
			//echo $dia_mes[$x]." <> $data_digitacao<br>";
			$x=$x+1;
		};

		if(trim($dia_mes[$x]) == $data_digitacao){
			$qtde_digitada[$y][$x] = $total_digitada;
		}

		$x=$x+1; //após armazenar o valor no mes correspondente anda 1 mes no vetor
		if($i==(pg_numrows($res)-1)){
			for( $a = 0 ;$a < $x_data_final ; $a++ ){			//imprime os doze meses
				echo "<td bgcolor='$cor' title='Dia ".$dia_mes[$a]." de $mes_descricao de $ano'>";

				if ($qtde_digitada[$y][$a]>0) echo "<font color='#000000'><b>".$qtde_digitada[$y][$a];
				else                         echo "<font color='#999999'> ";

				echo "</td>";

				$total_ano = $total_ano + $qtde_digitada[$y][$a];
				if($a==($x_data_final-1)){
					echo "<td bgcolor='$cor' ><b>$total_ano</b></td>";
					echo "</tr>";
				}
			}
		
		}

	}
	echo "<tr class='Conteudo' bgcolor='#D7FFE1'>";
	echo "<td height='25' ><b>TOTAL DIA</b></td>";
	$total_dia = 0;
	for( $i=0 ; $i < $x_data_final ; $i++){
		for( $j=0 ;$j < $admins_total ;$j++ ){
			$total_dia += $qtde_digitada[$j][$i];
			
		}
		echo "<td>";
		if($total_dia=='0')echo "-";
		else                 echo "<font color='#FF0000'>$total_dia</font>";
		echo "</td>";
		$total_final +=$total_dia; 
		$total_dia = 0;
	} 
	echo "<td align='center'>";
	if($total_final=='0')echo "";
	else                 echo "<font color='#FF0000' size='3'><b>$total_final</b></font>";
	echo "</td>";
	echo "</tr>";
	echo "</table>";

	}else{
		echo "<br><table style=' border: #D3BE96 1px solid; background-color: #FCF0D8 ' align='center' width='90%'><tr><td class='Exibe'>";
		echo "<b>Nenhuma Unidade tempo Cadastrada";
		echo "</td></tr></table>";
	}
}





include 'rodape.php';
?>