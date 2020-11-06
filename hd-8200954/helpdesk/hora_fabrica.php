<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';
$TITULO = "Suporte";
include "menu.php";
/*

function converte_data($date){
	$date = explode("-", ereg_replace('/', '-', $date));
	if (sizeof($date)==3)	return ''.$date[2].'/'.$date[1].'/'.$date[0];
	else			return false;
}

function converte_data_x($date){
	if (strlen($date)!=8) {return false;}
	return $date{4}.$date{5}.$date{6}.$date{7}.'-'.$date{2}.$date{3}.'-'.$date{0}.$date{1};
}


$btn_acao=trim($_POST['btn_acao']);
if ($btn_acao=='buscar_hora_fabricante'){
	$data_inicial	=trim($_POST['data_inicial']);
	$data_final		=trim($_POST['data_final']);
	if (strlen($data_inicial)==10){
		$xdata_inicial=converte_data($data_inicial);
		if ($xdata_inicial){
			$xdata_inicial = " AND tbl_hd_chamado_atendente.data_inicio >= '$xdata_inicial 00:00:00'";
		}else{
			$msg_erro_hora_fabrica .= "Data inválida";
		}
	}else{
		if (strlen($data_inicial)==8){
			$xdata_inicial=converte_data_x($data_inicial);
			if ($xdata_inicial){
				$xdata_inicial = " AND tbl_hd_chamado_atendente.data_inicio >= '$xdata_inicial 00:00:00'";
			}else{
				$msg_erro_hora_fabrica .= "Data inválida";
			}
		}else{
			$msg_erro_hora_fabrica .= "Data inválida";
		}
	}

	if (strlen($data_final)==10){
		$xdata_final=converte_data($data_final);
		if ($xdata_final){
			$xdata_final = " AND tbl_hd_chamado_atendente.data_termino <= '$xdata_final 23:59:59'";
		}else{
			$msg_erro .= "Data inválida";
		}
	}else{
		if (strlen($data_final)==8){
			$xdata_final=converte_data_x($data_final);
			if ($xdata_final){
				$xdata_final = " AND tbl_hd_chamado_atendente.data_termino <= '$xdata_final 23:59:59'";
			}else{
				$msg_erro .= "Data inválida";
			}
		}else{
			$msg_erro_hora_fabrica .= "Data inválida";
		}
	}
}

if (strlen($xdata_inicial)==0 AND strlen($xdata_final)==0){
	#$xdata_inicial = " AND tbl_hd_chamado_atendente.data_inicio >=  '".date("Y-m-01 ")." 00:00:00'";
	#$xdata_final = " AND tbl_hd_chamado_atendente.data_termino >=  '".date("Y-m-30 ")." 23:59:59'";
}

$sql = "SELECT  to_char(data_inicio,'MM')                        AS mes,
		to_char(SUM(data_termino-data_inicio),'HH24:MI') AS total_horas,
		SUM(data_termino-data_inicio)                    AS total_horas_2,
		COUNT(hd_chamado)                                AS total_chamados,
		tbl_fabrica.nome,
		(
		SELECT COUNT(hd_chamado)
		FROM tbl_hd_chamado
		WHERE hd_chamado IN (
			SELECT hd_chamado 
			FROM tbl_hd_chamado_atendente
			WHERE 1=1 
			$xdata_inicial
			$xdata_final 
			AND fabrica = $login_fabrica
			)
		) AS total_chamados
	FROM tbl_hd_chamado_atendente
	JOIN tbl_admin      USING(admin)
	JOIN tbl_hd_chamado USING(hd_chamado)
	JOIN tbl_fabrica    ON tbl_hd_chamado.fabrica = tbl_fabrica.fabrica
	WHERE tbl_fabrica.fabrica = $login_fabrica
	$xdata_inicial
	$xdata_final
	GROUP BY tbl_fabrica.nome,mes
	ORDER BY  mes,tbl_fabrica.nome";
$res = pg_exec ($con,$sql);

#echo nl2br($sql );

if (pg_numrows($res) > 0) {

	$sql2 = "SELECT
			to_char(SUM(data_termino-data_inicio),'HH24:MI') AS total_horas
			FROM tbl_hd_chamado_atendente
			JOIN tbl_admin      USING(admin)
			JOIN tbl_hd_chamado USING(hd_chamado)
			JOIN tbl_fabrica    ON tbl_hd_chamado.fabrica = tbl_fabrica.fabrica
			WHERE tbl_fabrica.fabrica = $login_fabrica
			$xdata_inicial
			$xdata_final
			";
	$res2 = pg_exec ($con,$sql2);
	$tota_horas_geral = trim(pg_result($res2,0,0));

	

	echo "<br>";
	echo "<table width = '400'  cellpadding='0' cellspacing='0' border='0' align='center'>";
	echo "<tr>";
	echo "<td background='/assist/helpdesk/imagem/fundo_tabela_top_esquerdo_azul_claro.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>"; //linha esquerda - 2 linhas
	echo "<td background='/assist/helpdesk/imagem/fundo_tabela_top_centro_azul_claro.gif'   align = 'center' width='100%' style='font-family: arial ; color:#666666'><b>Análise da Horas Trabalhadas</b></td>";//centro
	echo "<td background='/assist/helpdesk/imagem/fundo_tabela_top_direito_azul_claro.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";//linha direita - 2 linhas
	echo "</tr>";
	echo "<tr>";
	echo "<td background='/assist/helpdesk/imagem/fundo_tabela_centro_esquerdo.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";//coluna esquerda
	echo "<td>";
	
	echo "<table width='100%' cellspacing='0' cellpadding='4'>";
	echo "<form name='busca_hora_fabrica' method='POST' action='$PHP_SELF'>";
	echo "<input type='hidden' name='btn_acao' value=''>";
	echo "<tr style='font-size:11px'>";
	echo "<td align='center'><b>Data Inicial</b></td>";
	echo "<td align='center'><b>Data Final</b></td>";
	echo "<td align='center'></td>";
	echo "</tr>";
	echo "<tr style='font-size:11px'>";
	echo "<td align='center'><input type='text' name='data_inicial' size='12' maxlength='10' value='$data_inicial'></td>";
	echo "<td align='center'><input type='text' name='data_final' size='12' maxlength='10' value='$data_final'></td>";
	echo "<td align='center'><input type='button' value='OK' onClick=\"this.form.btn_acao.value='buscar_hora_fabricante';this.form.submit();\"></td>";
	echo "</tr>";
	echo "</form>";

	echo "<tr style='font-size:11px'>";
	echo "<td align='left' colspan='3' style='color:red;font-size:10px'>$msg_erro_hora_fabrica</td>";
	echo "</tr>";

	echo "<tr style='font-size:11px'>";
	echo "<td align='center' colspan='3'><b style='font-size:14px;color:#018AFA'>Horas por Fábrica</b></td>";
	echo "</tr>";

	echo "<tr style='font-size:11px'>";
	echo "<td align='center'><b>Fábrica</b></td>";
	echo "<td align='center'><b>Horas</b></td>";
	echo "<td align='center'><b>Mes</b></td>";
	echo "</tr>";

	$total_chamados_geral=0;
	for ($x = 0 ; $x < pg_numrows($res) ; $x++){
		$mes               = trim(pg_result($res,$x,mes));
		$total_horas       = trim(pg_result($res,$x,total_horas));
		$total_horas_2     = trim(pg_result($res,$x,total_horas_2));
		$total_chamados    = trim(pg_result($res,$x,total_chamados));
//		$total_chamados_geral += $total_chamados;
		$nome              = trim(pg_result($res,$x,nome));
		$cor = ($x%2==0)?'#EAF2FF':'#FFFFFF';
		echo "<tr class='Conteudo' style='background-color: $cor;'>";
		echo "<td align='left' height='15'>$nome</td>";
		echo "<td align='center'>$total_horas</td>";
		echo "<td align='right'>$mes</td>";
		echo "</tr>";
	}
	echo "<tr style='font-size:12px'>";
	echo "<td align='left' height='15'><b>Total</b></td>";
	echo "<td align='center'><b>$tota_horas_geral</b></td>";
	echo "<td align='right'><b></b></td>";
	echo "</tr>";
	echo "</table>";
	
	
	echo "</td>";
	
	echo "<td background='/assist/helpdesk/imagem/fundo_tabela_centro_direito.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";// coluna direita
	
	echo "</tr>";
	
	echo "<tr>";
	echo "<td background='/assist/helpdesk/imagem/fundo_tabela_baixo_esquerdo.gif'><img src='/assist/imagens/pixel.gif' width='9'></td>";
	echo "<td background='/assist/helpdesk/imagem/fundo_tabela_baixo_centro.gif' align = 'center' width='100%'></td>";
	echo "<td background='/assist/helpdesk/imagem/fundo_tabela_baixo_direito.gif'><img src='/assist/imagens/pixel.gif' width='9'></td>";
	echo "</tr>";
	echo "</table>";

}

*/
include "rodape.php";
 ?>
</body>
</html>
