<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';
$data_de2  = @converte_data($data_de);
$data_ate2 = @converte_data($data_ate);

if(strlen($data_de2)==0 OR strlen($data_ate2)==0) $msg_erro = "É obrigatório colocar as datas";

$TITULO = "Suporte";
include "menu.php";
?>
<? include "javascript_calendario.php"; ?>

<script type="text/javascript" charset="utf-8">
	$(function()
	{
		$('#data_de').datePicker({startDate:'01/01/2000'});
		$('#data_ate').datePicker({startDate:'01/01/2000'});
		$("#data_de").maskedinput("99/99/9999");
		$("#data_ate").maskedinput("99/99/9999");
	});
</script>

<link rel="stylesheet" href="js/blue/style.css" type="text/css" id="" media="print, projection, screen" />
<!--
<script language="javascript" src="js/cal2.js"></script>
<script language="javascript" src="js/cal_conf2.js"></script>
-->

<script type="text/javascript" src="js/jquery.tablesorter.pack.js"></script> 
<script>
$(document).ready(function(){
	$.tablesorter.defaults.widgets = ['zebra'];
	$("#relatorio").tablesorter();

});
</script>
<?

function converte_data($date)
{
	$date = explode("-", str_replace('/', '-', $date));
	$date2 = ''.$date[2].'/'.$date[1].'/'.$date[0];
	if (sizeof($date)==3)
		return $date2;
	else return false;
}

$atendente 	= trim($_GET['atendente_busca']);
$data_de		= @converte_data(trim($_GET['data_de']));
$data_ate 		= @converte_data(trim($_GET['data_ate']));

echo "<form name='filtrar' method='GET' ACTION='$PHP_SELF'>";
echo "<table width = '400' align = 'center' cellpadding='0' cellspacing='0' border='0'>";
echo "<tr>";
echo "	<td background='/assist/helpdesk/imagem/fundo_tabela_top_esquerdo_azul_claro.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";
echo "	<td background='/assist/helpdesk/imagem/fundo_tabela_top_centro_azul_claro.gif'  colspan='2' align = 'left' width='100%' style='font-family: arial ; color:#666666'>&nbsp;<b>Relatório de Posição de Atendimento</b></td>";
echo "	<td background='/assist/helpdesk/imagem/fundo_tabela_top_direito_azul_claro.gif'  ><img src='/assist/imagens/pixel.gif' width='9'></td>";
echo "</tr>";

echo "<tr style='font-family: verdana ; font-size:12px; color: #666666'>";
echo "	<td background='/assist/helpdesk/imagem/fundo_tabela_centro_esquerdo.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";
echo "<td td colspan = '2' align='left'> <br>Atendente: ";

$sqlatendente = "SELECT  nome_completo,
			admin
		FROM    tbl_admin
		WHERE   tbl_admin.fabrica = 10
		AND tbl_admin.responsabilidade IN ('Analista de Help-Desk', 'Programador')
		ORDER BY tbl_admin.nome_completo;";

$resatendente = pg_exec ($con,$sqlatendente);

$atendente_busca = $_POST['atendente_busca'];

if (pg_numrows($resatendente) > 0) {
	echo "<select class='frm' style='width: 200px;' name='atendente_busca'>\n";
	echo "<option value=''";
	if (strlen ($atendente_busca) == 0 ) echo " SELECTED "; 
	echo ">- TODOS -</option>\n";

	for ($x = 0 ; $x < pg_numrows($resatendente) ; $x++){
		$n_admin = trim(pg_result($resatendente,$x,admin));
		$nome_atendente  = trim(pg_result($resatendente,$x,nome_completo));

		echo "<option value='$n_admin'"; 
		if ($atendente == $n_admin) echo " SELECTED "; 
		if ($atendente_busca == $n_admin ) echo " SELECTED "; 
		echo "> $nome_atendente</option>\n";
	}
	
	echo "</select>";
}

echo "</td>";

	
$data_de2  = @converte_data($data_de);
$data_ate2 = @converte_data($data_ate);
echo "	<td background='/assist/helpdesk/imagem/fundo_tabela_centro_direito.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";
echo "</tr>";

echo "<tr style='font-family: verdana ; font-size:12px; color: #666666'>";
echo "	<td background='/assist/helpdesk/imagem/fundo_tabela_centro_esquerdo.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";
echo "	<td td colspan = '2' align='left'> <br>Fabricante: ";

$sqlfabrica = "SELECT   * 
	FROM     tbl_fabrica 
	ORDER BY nome";
$resfabrica = pg_exec ($con,$sqlfabrica);
$n_fabricas = pg_numrows($res);


echo "<select class='frm' style='width: 180px;' name='fabrica_busca'>\n";
echo "<option value=''></option>\n";
for ($x = 0 ; $x < pg_numrows($resfabrica) ; $x++){
	$fabrica   = trim(pg_result($resfabrica,$x,fabrica));
	$nome      = trim(pg_result($resfabrica,$x,nome));
	echo "<option value='$fabrica'"; if ($fabrica_busca == $fabrica) echo " SELECTED "; echo ">$nome</option>\n";
}
echo "</select>\n";

echo "</td>";

	
	
echo "	<td background='/assist/helpdesk/imagem/fundo_tabela_centro_direito.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";
echo "</tr>";


echo "<tr bgcolor='#D9E8FF' style='font-family: arial ; color: #666666'>";
echo "<td background='/assist/helpdesk/imagem/fundo_tabela_centro_esquerdo.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";
echo "<td align='left' colspan='2'><b>Período</b></td>";
echo "<td background='/assist/helpdesk/imagem/fundo_tabela_centro_direito.gif' align='rigth'><img src='/assist/imagens/pixel.gif' width='9'></td>";
echo "</tr>";


echo "<tr style='font-family: verdana ; font-size:12px; color: #666666'>";
echo "	<td background='/assist/helpdesk/imagem/fundo_tabela_centro_esquerdo.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";
echo "	<td align='left' colspan='2' nowrap><br>De: <input type='text' size='15' maxlength='10' name='data_de' id='data_de' value='$data_de2' class='caixa'> </td>";
echo "	<td background='/assist/helpdesk/imagem/fundo_tabela_centro_direito.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";
echo "</td>";
echo "</tr>";
echo "<tr style='font-family: verdana ; font-size:12px; color: #666666'>";
echo "	<td background='/assist/helpdesk/imagem/fundo_tabela_centro_esquerdo.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";
echo "	<td align='left' colspan='2' nowrap><br> Até: <input type='text' size='15' maxlength='10' name='data_ate' id='data_ate' value='$data_ate2' class='caixa'></td>";
echo "	<td background='/assist/helpdesk/imagem/fundo_tabela_centro_direito.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";
echo "</td>";
echo "</tr>";


echo "<tr style='font-family: verdana ; font-size:12px; color: #666666'>";
echo "	<td background='/assist/helpdesk/imagem/fundo_tabela_centro_esquerdo.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";
echo "<td colspan='2' align='left'><br><INPUT TYPE=\"checkbox\" name=\"cobrar\" value=\"cobrar\"> Chamados cobrados";
echo "	<td background='/assist/helpdesk/imagem/fundo_tabela_centro_direito.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";
echo "</td>";
echo "</tr>";


echo "<tr>";
echo "<td background='/assist/helpdesk/imagem/fundo_tabela_centro_esquerdo.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";
echo "<td nowrap colspan='2'><br><CENTER><INPUT TYPE=\"submit\" value=\"Pesquisar\"></CENTER></td>";
echo "<td background='/assist/helpdesk/imagem/fundo_tabela_centro_direito.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";
echo "</tr>";

#===========================

echo "<tr>";
echo "<td background='/assist/helpdesk/imagem/fundo_tabela_baixo_esquerdo.gif'><img src='/assist/imagens/pixel.gif' width='9'></td>";
echo "<td background='/assist/helpdesk/imagem/fundo_tabela_baixo_centro.gif' colspan='2' align = 'center' width='100%'></td>";
echo "<td background='/assist/helpdesk/imagem/fundo_tabela_baixo_direito.gif'><img src='/assist/imagens/pixel.gif' width='9'></td>";
echo "</tr>";

echo "</table>";
echo "</FORM>";

$imagem = "<img src='/assist/admin/imagens_admin/status_vermelho.gif' align='absmiddle' width='8' title='Aguardando a resposta do Solicitante do chamado'>";


if( strlen($data_de2)>0 AND strlen($data_ate2)>0){

	$data_de  = @converte_data(trim($_GET['data_de']));
	$data_ate = @converte_data(trim($_GET['data_ate']));

	if(strlen($atendente > 0))     $query_add1 = "AND tbl_hd_chamado_atendente.admin = $atendente ";
	if(strlen($fabrica_busca > 0)) $query_add2 = "AND tbl_hd_chamado.fabrica = $fabrica_busca";
	if(strlen($cobrar) > 0 and $cobrar <> 'f'){ $query_add3 = " AND tbl_hd_chamado.cobrar IS TRUE"; }

	$sql = "SELECT  tbl_hd_chamado.hd_chamado          ,
			tbl_hd_chamado.titulo                      ,
			tbl_hd_chamado.cobrar                      ,
			tbl_hd_chamado.prazo_horas                 ,
			tbl_hd_chamado.hora_desenvolvimento        ,
			tbl_hd_chamado.status                          ,
			tbl_hd_chamado_atendente.admin  as atendente   ,
			tbl_hd_chamado.exigir_resposta                 ,
			tbl_fabrica.nome                AS fabrica_nome,
			tbl_admin.nome_completo         as nome_completo   ,
			sum(data_termino - data_inicio) as total_horas 
		FROM   tbl_hd_chamado_atendente
		JOIN   tbl_hd_chamado USING(hd_chamado)
		JOIN tbl_admin   ON tbl_hd_chamado.admin    = tbl_admin.admin
		JOIN tbl_fabrica ON tbl_hd_chamado.fabrica  = tbl_fabrica.fabrica
		WHERE  data_inicio between '$data_de 00:00:00' and '$data_ate 23:59:59'
		$query_add1 $query_add2  $query_add3
		GROUP BY tbl_hd_chamado.hd_chamado     ,
			tbl_hd_chamado.titulo          ,
			tbl_hd_chamado.cobrar          ,
			tbl_hd_chamado.prazo_horas     ,
			tbl_hd_chamado.hora_desenvolvimento,
			tbl_hd_chamado.status          ,
			tbl_hd_chamado_atendente.admin ,
			tbl_hd_chamado.exigir_resposta ,
			tbl_fabrica.nome               ,
			tbl_admin.nome_completo
		ORDER BY hd_chamado;";
//echo $sql;
	$res = pg_exec ($con,$sql);

	if (pg_numrows($res) > 0) {

		echo "<table width = '750' align = 'center' cellpadding='2' cellspacing='1' border='0' name='relatorio' id='relatorio' class='tablesorter' >";
		echo "<thead>";
		echo "<tr style='font-size:11px' bgcolor='#D9E8FF'>";
		echo "<td align='center'><b>#</b></td>";
		echo "<td align='center'><b>Titulo</b></td>";
		echo "<td align='center'><b>Status</b></td>";
		echo "<td align='center'><b>Solicitante</b></td>";
		echo "<td align='center'><b>Fabricante</b></td>";
		echo "<td align='center'><b>Atendente</b></td>";
		echo "<td align='center'><b>Total</b></td>";
		echo "<td align='center'><b>H.Interna</b></td>";
		echo "<td align='center'><b>H.Cobrada</b></td>";
		echo "</tr>";
		echo "</thead>";
		echo "<tbody>";
		$total_chamados_geral=0;
		for ($x = 0 ; $x < pg_numrows($res) ; $x++){
			$hd_chamado      = trim(pg_result($res,$x,hd_chamado));
			$titulo          = trim(pg_result($res,$x,titulo));
			$cobrar          = trim(pg_result($res,$x,cobrar));
			$exigir_resposta = trim(pg_result($res,$x,exigir_resposta));
			$status          = trim(pg_result($res,$x,status));
			$fabrica_nome    = trim(pg_result($res,$x,fabrica_nome));
			$nome_completo   = trim(pg_result($res,$x,nome_completo));
			$atendente       = trim(pg_result($res,$x,atendente));
			$total_horas     = trim(pg_result($res,$x,total_horas));
      $h_interna       = trim(pg_result($res,$x,prazo_horas));
      $h_cobrada       = trim(pg_result($res,$x,hora_desenvolvimento));

			$a="";
			if($exigir_resposta=='t') $a = $imagem;
			if($status=="Novo" or $status =="Análise")$cor_status="#000099";
			if($status=="Execução")                   $cor_status="#FF0000";
			if($status=="Aguard.Execução")            $cor_status="#FF9900";
			if($status=="Resolvido")$cor_status="#009900";

			$sql2 = "SELECT nome_completo FROM tbl_admin WHERE admin = $atendente";
			$res2 = pg_exec ($con,$sql2);
			$atendente = pg_result($res2,0,0);

	// calculo de horas já utilizadas
	$sql_uti = "SELECT  tbl_hd_chamado.hd_chamado          ,
			tbl_hd_chamado.titulo                      ,
			tbl_hd_chamado.cobrar                      ,
			tbl_hd_chamado.prazo_horas                 ,
			tbl_hd_chamado.hora_desenvolvimento        ,
			tbl_hd_chamado.status                          ,
			tbl_hd_chamado_atendente.admin  as atendente   ,
			tbl_hd_chamado.exigir_resposta                 ,
			tbl_fabrica.nome                AS fabrica_nome,
			tbl_admin.nome_completo         as nome_completo   ,
			sum(data_termino - data_inicio) as total_horas 
		FROM   tbl_hd_chamado_atendente
		JOIN   tbl_hd_chamado USING(hd_chamado)
		JOIN tbl_admin   ON tbl_hd_chamado.admin    = tbl_admin.admin
		JOIN tbl_fabrica ON tbl_hd_chamado.fabrica  = tbl_fabrica.fabrica
		WHERE  hd_chamado = $hd_chamado 
			and data_inicio < '$data_ate 23:59:59'
		$query_add1 $query_add2  $query_add3
		GROUP BY tbl_hd_chamado.hd_chamado     ,
			tbl_hd_chamado.titulo          ,
			tbl_hd_chamado.cobrar          ,
			tbl_hd_chamado.prazo_horas     ,
			tbl_hd_chamado.hora_desenvolvimento,
			tbl_hd_chamado.status          ,
			tbl_hd_chamado_atendente.admin ,
			tbl_hd_chamado.exigir_resposta ,
			tbl_fabrica.nome               ,
			tbl_admin.nome_completo
		ORDER BY hd_chamado;";
	//echo $sql_uti;
	$res_uti            = pg_exec ($con,$sql_uti);
	$total_horas_gastas = trim(pg_result($res_uti,0,total_horas));

			echo "<tr class='Conteudo' style='background-color: $cor;'>";
			echo "<td align='left' height='15'><a href='adm_chamado_detalhe.php?hd_chamado=$hd_chamado'>$hd_chamado</a> $a</td>";
			echo "<td align='left'>$titulo</td>";
			echo "<td nowrap'><font color='$cor_status' size='1'><B>$status </B></font></td>";
			echo "<td nowrap'>$nome_completo</td>";
			echo "<td nowrap'>$fabrica_nome</td>";
			echo "<td nowrap'>$atendente</td>";
			echo "<td align='right'>$total_horas_gastas $total_horas</td>";
			echo "<td align='right'>$h_interna</td>";
			echo "<td align='right'>";
			if($cobrar=='t') echo "*";
			echo "$h_cobrada</td>";
			echo "</tr>";
		}
		echo "</tbody>";
		echo "<tfoot>";
		$sql2 = "SELECT sum(data_termino - data_inicio)  as total_horas,
		        sum(tbl_hd_chamado.prazo_horas)          as t_interna,
		        sum(tbl_hd_chamado.hora_desenvolvimento) as t_cobrada
			FROM   tbl_hd_chamado_atendente
			JOIN   tbl_hd_chamado USING(hd_chamado)
			WHERE  data_inicio between '$data_de 00:00:00' and '$data_ate 23:59:59' 
			$query_add1 $query_add2  $query_add3";
			//echo $sql2;
		$res = pg_exec ($con,$sql2);
		$total_horas = pg_result($res,0,total_horas);
		$sql2 = "SELECT DISTINCT tbl_hd_chamado.hd_chamado, tbl_hd_chamado.prazo_horas as t_interna,
		        tbl_hd_chamado.hora_desenvolvimento as t_cobrada
		        INTO TEMP TABLE tmp_hd_chamado
			FROM   tbl_hd_chamado 
      JOIN   tbl_hd_chamado_atendente USING(hd_chamado)
			WHERE  data_inicio between '$data_de 00:00:00' and '$data_ate 23:59:59' 
			$query_add1 $query_add2 $query_add3;
      
      SELECT sum(tmp_hd_chamado.t_interna) as t_interna, sum(tmp_hd_chamado.t_cobrada) as t_cobrada
      FROM tmp_hd_chamado; 
      ";
			//echo $sql2;
		$res = pg_exec ($con,$sql2);
    $t_interna = pg_result($res,0,t_interna);
		$t_cobrada = pg_result($res,0,t_cobrada);
		echo "<tr style='font-size:11px' bgcolor='#D9E8FF'>";
		echo "<td align='center' colspan='6'><b>TOTAL HORAS</b></td>";
		echo "<td align='right'><b>$total_horas</b></td>";
		echo "<td align='right' title='Desconsiderando horas repetidas'><b>$t_interna</b></td>";
		echo "<td align='right' title='Desconsiderando horas repetidas'><b>$t_cobrada</b></td>";
		echo "</tr>";
		echo "<tr style='font-size:11px' bgcolor='#D9E8FF'>";
		echo "<td align='center' colspan='6'><b>TOTAL CHAMADOS</b></td>";
		echo "<td align='right'><b>$x</b></td>";
		echo "</tr>";
		echo "</foot>";
		echo "</table>";
	
	}

}
include "rodape.php";
 ?>
</body>
</html>
