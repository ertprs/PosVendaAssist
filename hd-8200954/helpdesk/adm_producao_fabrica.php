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
	echo "<td background='/assist/helpdesk/imagem/fundo_tabela_top_esquerdo_azul_claro.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";	
	echo "<td background='/assist/helpdesk/imagem/fundo_tabela_top_centro_azul_claro.gif'   align = 'center' width='100%' style='font-family: arial ; color:#666666' ><b>Relatório de Horas Trabalhadas</b></td>";
	echo "<td background='/assist/helpdesk/imagem/fundo_tabela_top_direito_azul_claro.gif'  ><img src='/assist/imagens/pixel.gif' width='9'></td>";
echo "</tr>";

$data_de2  = @converte_data($data_de);
$data_ate2 = @converte_data($data_ate);

echo "<tr style='font-family: verdana ; font-size:12px; color: #666666'>";
	echo "<td background='/assist/helpdesk/imagem/fundo_tabela_centro_esquerdo.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";
	echo "<td align='center'> <br>(Deixar em branco a fábrica para gerar resumo)<br>Fabricante: ";

	$sqlfabrica = "SELECT   * 
		FROM     tbl_fabrica 
		WHERE ativo_fabrica IS TRUE
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

echo "<tr>";
	echo "<td background='/assist/helpdesk/imagem/fundo_tabela_centro_esquerdo.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";
	echo "<td nowrap >&nbsp;</td>";
	echo "<td background='/assist/helpdesk/imagem/fundo_tabela_centro_direito.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";
echo "</tr>";

echo "<tr style='font-family: verdana ; font-size:12px; color: #666666'>";
	echo "<td background='/assist/helpdesk/imagem/fundo_tabela_centro_esquerdo.gif'><img src='/assist/imagens/pixel.gif' width='9'></td>";
	echo "<td align='center' nowrap >";
	echo "<table><tr style='font-family: verdana ; font-size:12px; color: #666666'><td >De:</td><td><input type='text' size='15' maxlength='10' name='data_de' id='data_de' value='$data_de2' class='caixa'></td>";
	echo "<td class='label'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Até:</td><td><input type='text' size='15' maxlength='10' name='data_ate' id='data_ate' value='$data_ate2' class='caixa'></td></tr></table>";
	echo "<td background='/assist/helpdesk/imagem/fundo_tabela_centro_direito.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";
echo "</tr>";

echo "<tr>";
	echo "<td background='/assist/helpdesk/imagem/fundo_tabela_centro_esquerdo.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";
	echo "<td nowrap ><br><CENTER><INPUT TYPE=\"submit\" value=\"Pesquisar\"></CENTER></td>";
	echo "<td background='/assist/helpdesk/imagem/fundo_tabela_centro_direito.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";
echo "</tr>";

echo "<tr>";
	echo "<td background='/assist/helpdesk/imagem/fundo_tabela_baixo_esquerdo.gif'><img src='/assist/imagens/pixel.gif' width='9'></td>";
	echo "<td background='/assist/helpdesk/imagem/fundo_tabela_baixo_centro.gif' align = 'center' width='100%'></td>";
	echo "<td background='/assist/helpdesk/imagem/fundo_tabela_baixo_direito.gif'><img src='/assist/imagens/pixel.gif' width='9'></td>";
echo "</tr>";

echo "</table>";
echo "</FORM>";

$imagem = "<img src='/assist/admin/imagens_admin/status_vermelho.gif' align='absmiddle' width='8' title='Aguardando a resposta do Solicitante do chamado'>";


if( strlen($data_de2)>0 AND strlen($data_ate2)>0){

	$data_de  = @converte_data(trim($_GET['data_de']));
	$data_ate = @converte_data(trim($_GET['data_ate']));

	if(strlen($fabrica_busca > 0)){
		if(strlen($fabrica_busca > 0)) $query_add2 = "AND tbl_hd_chamado.fabrica = $fabrica_busca";
		if(strlen($cobrar) > 0 and $cobrar <> 'f'){ $query_add3 = " AND tbl_hd_chamado.cobrar IS TRUE"; }

		$sql = "SELECT  tbl_hd_chamado.hd_chamado                ,
					to_char(tbl_hd_chamado.data,'DD/MM/YYYY') as data,
					tbl_hd_chamado.titulo                            ,
					tbl_hd_chamado.cobrar                            ,

					CASE WHEN data_aprovacao IS NOT NULL THEN
						sum(data_termino - data_inicio) 
					ELSE
						'00:00:00'::interval
					END as total_horas_cobrado,

					tbl_hd_chamado.status                            ,
					tbl_hd_chamado.exigir_resposta                   ,
					tbl_fabrica.nome                AS fabrica_nome  ,
					tbl_admin.nome_completo         as nome_completo ,
					sum(data_termino - data_inicio) as total_horas   ,
					

					to_char(tbl_hd_chamado.data_aprovacao,'DD/MM/YYYY hh24:mi') as data_aprovacao,
					tbl_tipo_chamado.descricao as tipo_chamado
				FROM   tbl_hd_chamado_atendente
				JOIN   tbl_hd_chamado USING(hd_chamado)
				JOIN tbl_admin   ON tbl_hd_chamado.admin    = tbl_admin.admin
				JOIN tbl_fabrica ON tbl_hd_chamado.fabrica  = tbl_fabrica.fabrica
				JOIN tbl_tipo_chamado on tbl_hd_chamado.tipo_chamado = tbl_tipo_chamado.tipo_chamado
				WHERE  data_inicio between '$data_de 00:00:00' and '$data_ate 23:59:59'
					AND   tbl_hd_chamado.status <> 'Cancelado'
					$query_add2  $query_add3
				GROUP BY tbl_hd_chamado.hd_chamado     ,
					tbl_hd_chamado.titulo          ,
					tbl_hd_chamado.cobrar          ,
					tbl_hd_chamado.status          ,
					tbl_hd_chamado.exigir_resposta ,
					tbl_fabrica.nome               ,
					tbl_admin.nome_completo        ,
					data_aprovacao,
					data,
					tbl_tipo_chamado.descricao
				ORDER BY hd_chamado;";
		//echo $sql;
		$res = pg_exec ($con,$sql);

		if (pg_numrows($res) > 0) {

			echo "<table width = '750' align = 'center' cellpadding='2' cellspacing='1' border='0' name='relatorio' id='relatorio' class='tablesorter' >";
			echo "<thead>";
			echo "<tr style='font-size:11px' bgcolor='#D9E8FF'>";
			echo "<td align='center'><b>#</b></td>";
			echo "<td align='center'><b>Titulo</b></td>";
			echo "<td align='center'><b>Data</b></td>";
			echo "<td align='center'><b>Tipo</b></td>";
			echo "<td align='center'><b>Status</b></td>";
			echo "<td align='center'><b>Solicitante</b></td>";
			echo "<td align='center'><b>Fabricante</b></td>";
			echo "<td align='center'><b>Horas trabalhadas<br>meses anteriores</b></td>";
			echo "<td align='center'><b>Horas trabalhadas<br>mês atual</b></td>";
			echo "<td align='center'><b>Horas trabalhadas<br>mês atual<br>(cobrado)</b></td>";
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
				$total_horas     = trim(pg_result($res,$x,total_horas));
				$total_horas_cobrado     = trim(pg_result($res,$x,total_horas_cobrado));
				$data_aprovacao  = trim(pg_result($res,$x,data_aprovacao));
				$data            = trim(pg_result($res,$x,data));
				$tipo_chamado    = trim(pg_result($res,$x,tipo_chamado));

				$a="";
				if($exigir_resposta=='t') $a = $imagem;
				if($status=="Novo" or $status =="Análise")$cor_status="#000099";
				if($status=="Execução")                   $cor_status="#FF0000";
				if($status=="Aguard.Execução")            $cor_status="#FF9900";
				if($status=="Resolvido")$cor_status="#009900";

				// calculo de horas já utilizadas dos meses anteriores
				$sql_uti = "SELECT  tbl_hd_chamado.hd_chamado          ,
						tbl_hd_chamado.titulo                      ,
						tbl_hd_chamado.cobrar                      ,
						tbl_hd_chamado.status                          ,
						tbl_hd_chamado.exigir_resposta                 ,
						tbl_fabrica.nome                AS fabrica_nome,
						tbl_admin.nome_completo         as nome_completo   ,
						sum(data_termino - data_inicio) as total_horas 
					FROM   tbl_hd_chamado_atendente
					JOIN   tbl_hd_chamado USING(hd_chamado)
					JOIN tbl_admin   ON tbl_hd_chamado.admin    = tbl_admin.admin
					JOIN tbl_fabrica ON tbl_hd_chamado.fabrica  = tbl_fabrica.fabrica
					WHERE  hd_chamado = $hd_chamado 
					AND   tbl_hd_chamado.status <> 'Cancelado'
					and data_inicio < '$data_de 00:00:00'
					$query_add2  $query_add3
					GROUP BY tbl_hd_chamado.hd_chamado     ,
						tbl_hd_chamado.titulo          ,
						tbl_hd_chamado.cobrar          ,
						tbl_hd_chamado.status          ,
						tbl_hd_chamado.exigir_resposta ,
						tbl_fabrica.nome               ,
						tbl_admin.nome_completo
					ORDER BY hd_chamado;";
				//echo $sql_uti."<br>";
				$res_uti            = pg_exec ($con,$sql_uti);
				$linha_uti          = pg_num_rows( $res_uti);
				if($linha_uti > 0) {
					$total_horas_gastas = trim(pg_result($res_uti,0,total_horas));
				}else{
					$total_horas_gastas = 0;
				}
				echo "<tr class='Conteudo' style='background-color: $cor;'>";
				echo "<td align='left' height='15'><a href='adm_chamado_detalhe.php?hd_chamado=$hd_chamado'>$hd_chamado</a> $a</td>";
				echo "<td align='left'>$titulo</td>";
				echo "<td align='left'>$data</td>";
				echo "<td align='left'>$tipo_chamado</td>";
				echo "<td nowrap'><font color='$cor_status' size='1'><B>$status </B></font></td>";
				echo "<td nowrap'>$nome_completo</td>";
				echo "<td nowrap'>$fabrica_nome</td>";
				echo "<td align='right'>$total_horas_gastas</td>";
				echo "<td align='right'>$total_horas</td>";
				echo "<td align='right'>$total_horas_cobrado</td>";
				echo "</tr>";
			}
			echo "</tbody>";
			echo "<tfoot>";

			//totalizando horas dos meses anteriores
			$sql22 = "SELECT  sum(data_termino - data_inicio) as total_horas 
					FROM   tbl_hd_chamado_atendente
					JOIN   tbl_hd_chamado USING(hd_chamado)
					JOIN tbl_admin   ON tbl_hd_chamado.admin    = tbl_admin.admin
					JOIN tbl_fabrica ON tbl_hd_chamado.fabrica  = tbl_fabrica.fabrica
					WHERE  tbl_hd_chamado.status <> 'Cancelado'
					AND    tbl_hd_chamado.hd_chamado in (
						SELECT  distinct tbl_hd_chamado.hd_chamado
						FROM   tbl_hd_chamado_atendente
						JOIN   tbl_hd_chamado USING(hd_chamado)
						JOIN tbl_admin   ON tbl_hd_chamado.admin    = tbl_admin.admin
						JOIN tbl_fabrica ON tbl_hd_chamado.fabrica  = tbl_fabrica.fabrica
						JOIN tbl_tipo_chamado on tbl_hd_chamado.tipo_chamado = tbl_tipo_chamado.tipo_chamado
						WHERE  data_inicio between '$data_de 00:00:00' and '$data_ate 23:59:59'
						AND   tbl_hd_chamado.status <> 'Cancelado'
						$query_add2  $query_add3
						GROUP BY tbl_hd_chamado.hd_chamado
						ORDER BY hd_chamado
					)
					and data_inicio < '$data_de 00:00:00'
					$query_add2  $query_add3;";
			//echo $sql22;
			$res = pg_exec ($con,$sql22);
			$total_horas_meses_ant = pg_result($res,0,total_horas);
			//final da totalização dos meses anteriores

			//totalizando horas trabalhadas no mes
			$sql2 = "SELECT sum(data_termino - data_inicio)  as total_horas
					FROM   tbl_hd_chamado_atendente
					JOIN   tbl_hd_chamado USING(hd_chamado)
					WHERE  data_inicio between '$data_de 00:00:00' and '$data_ate 23:59:59' 
					AND   tbl_hd_chamado.status <> 'Cancelado'
					$query_add2  $query_add3";
			//echo $sql2;
			$res = pg_exec ($con,$sql2);
			$total_horas = pg_result($res,0,total_horas);
			//final da totalização de horas trabalhadas no mês

			//totalizando horas trabalhadas no mes em chamados cobrados
			$sql2 = "SELECT 
						CASE WHEN tbl_hd_chamado.data_aprovacao IS NOT NULL THEN
							sum(data_termino - data_inicio) 
						ELSE
							'00:00:00'::interval
						END as total_horas
					into temp hd_chamado_total
					FROM   tbl_hd_chamado_atendente
					JOIN   tbl_hd_chamado USING(hd_chamado)
					WHERE  data_inicio between '$data_de 00:00:00' and '$data_ate 23:59:59' 
					AND    data_aprovacao IS NOT NULL
					AND    cobrar IS TRUE
					AND   tbl_hd_chamado.status <> 'Cancelado'
					$query_add2  $query_add3 
					GROUP BY tbl_hd_chamado.data_aprovacao
					ORDER BY tbl_hd_chamado.data_aprovacao;
					
					SELECT sum(total_horas) as total_horas FROM hd_chamado_total;
					";
			//echo $sql2;
			$res = pg_exec ($con,$sql2);
			$total_horas_cobrado = trim(pg_result($res,0,total_horas));
			//final da totalização de horas trabalhadas no mês

			echo "<tr style='font-size:11px' bgcolor='#D9E8FF'>";
			echo "<td align='right' colspan='6'><b>Total chamados $x</b></td>";
			echo "<td align='right' colspan='1'><b>Total hora</b></td>";
			echo "<td align='right'><b>$total_horas_meses_ant</b></td>";
			echo "<td align='right'><b>$total_horas</b></td>";
			echo "<td align='right'><b>$total_horas_cobrado</b></td>";
			echo "</tr>";
			echo "</foot>";
			echo "</table>";
		}
	}else{
		$sqlf = 'SELECT fabrica, nome from tbl_fabrica ORDER BY fabrica';
		$resf = pg_exec($con,$sqlf);
		if (pg_numrows($resf) > 0) {
			echo "<table  align = 'center' cellpadding='2' cellspacing='1' border='0' name='relatorio' >";
			echo "<tr  style='font-size:11px' bgcolor='#D9E8FF'>";
				echo "<td><b>Fábrica</b></td>";
				echo "<td><b>Hora trabalhada<br>meses anteriores</b></td>";
				echo "<td><b>Hora trabalhada<br>mês atual</b></td>";
				echo "<td><b>Hora trabalhada<br>mês atual<br>(cobrado)</b></td>";
			echo "</tr>";
			$geral_total_horas_meses_ant = 0;
			$geral_total_horas           = 0;
			$geral_total_horas_cobrado   = 0;
			$geral_total_chamado         = 0;
			
			for ($x = 0 ; $x < pg_numrows($resf) ; $x++){
				$fabrica_busca   = pg_result($resf,$x,fabrica);
				$fabrica_nome    = pg_result($resf,$x,nome);
				$query_add2 = "AND tbl_hd_chamado.fabrica = $fabrica_busca";
				//totalizando
				//totalizando horas dos meses anteriores
				$sql22 = "SELECT  sum(data_termino - data_inicio) as total_horas 
					FROM   tbl_hd_chamado_atendente
					JOIN   tbl_hd_chamado USING(hd_chamado)
					JOIN tbl_admin   ON tbl_hd_chamado.admin    = tbl_admin.admin
					JOIN tbl_fabrica ON tbl_hd_chamado.fabrica  = tbl_fabrica.fabrica
					WHERE  tbl_hd_chamado.status <> 'Cancelado'
					AND    tbl_hd_chamado.hd_chamado in (
						SELECT  distinct tbl_hd_chamado.hd_chamado
						FROM   tbl_hd_chamado_atendente
						JOIN   tbl_hd_chamado USING(hd_chamado)
						JOIN tbl_admin   ON tbl_hd_chamado.admin    = tbl_admin.admin
						JOIN tbl_fabrica ON tbl_hd_chamado.fabrica  = tbl_fabrica.fabrica
						JOIN tbl_tipo_chamado on tbl_hd_chamado.tipo_chamado = tbl_tipo_chamado.tipo_chamado
						WHERE  data_inicio between '$data_de 00:00:00' and '$data_ate 23:59:59'
						AND   tbl_hd_chamado.status <> 'Cancelado'
						$query_add2  $query_add3
						GROUP BY tbl_hd_chamado.hd_chamado
						ORDER BY hd_chamado
					)
					and data_inicio < '$data_de 00:00:00'
					$query_add2  $query_add3;";
				//echo $sql22;
				$res = pg_exec ($con,$sql22);
				$total_horas_meses_ant = pg_result($res,0,total_horas);
				//final da totalização dos meses anteriores

				//totalizando horas trabalhadas no mes
				$sql2 = "SELECT sum(data_termino - data_inicio)  as total_horas
					FROM   tbl_hd_chamado_atendente
					JOIN   tbl_hd_chamado USING(hd_chamado)
					WHERE  data_inicio between '$data_de 00:00:00' and '$data_ate 23:59:59' 
					AND   tbl_hd_chamado.status <> 'Cancelado'
					$query_add2  $query_add3";
				//echo $sql2;
				$res = pg_exec ($con,$sql2);
				$total_horas = pg_result($res,0,total_horas);
				//final da totalização de horas trabalhadas no mês

				//totalizando horas trabalhadas no mes em chamados cobrados
				$sql2 = "SELECT 
						CASE WHEN tbl_hd_chamado.data_aprovacao IS NOT NULL THEN
							sum(data_termino - data_inicio) 
						ELSE
							'00:00:00'::interval
						END as total_horas
					INTO TEMP hd_chamado_total2
					FROM   tbl_hd_chamado_atendente
					JOIN   tbl_hd_chamado USING(hd_chamado)
					WHERE  data_inicio between '$data_de 00:00:00' and '$data_ate 23:59:59' 
					AND    data_aprovacao IS NOT NULL
					AND    cobrar IS TRUE
					AND   tbl_hd_chamado.status <> 'Cancelado'
					$query_add2  $query_add3
					GROUP BY tbl_hd_chamado.data_aprovacao
					ORDER BY tbl_hd_chamado.data_aprovacao;

					SELECT sum(total_horas) as total_horas FROM hd_chamado_total2;
					";
				//echo $sql2;
				$res = pg_exec ($con,$sql2);
				$total_horas_cobrado = pg_result($res,0,total_horas);
				$sql2 = "DROP TABLE hd_chamado_total2;";
				$res = pg_exec ($con,$sql2);
				//final da totalização de horas trabalhadas no mês

				// final da totalização

				//calcula qtd chamados
				$sqlq = "SELECT count(tbl_hd_chamado.hd_chamado) as total_chamado 
						FROM   tbl_hd_chamado_atendente
						JOIN   tbl_hd_chamado USING(hd_chamado)
						JOIN tbl_fabrica ON tbl_hd_chamado.fabrica  = tbl_fabrica.fabrica
						JOIN tbl_tipo_chamado on tbl_hd_chamado.tipo_chamado = tbl_tipo_chamado.tipo_chamado
						WHERE  data_inicio between '$data_de 00:00:00' and '$data_ate 23:59:59'
						AND   tbl_hd_chamado.status <> 'Cancelado'
						$query_add2  $query_add3
						GROUP BY tbl_hd_chamado.hd_chamado;";
				//echo $sqlq;
				$resq = pg_exec ($con,$sqlq);
				$total_chamado = pg_numrows($resq);

				//final calcula qtd chamados

				//final da totalização de horas cobradas
				if(strlen($total_horas_meses_ant)!=0 or 
					strlen($total_horas)!= 0 or
					strlen($total_horas_cobrado)!=0) {
					echo "<tr style='font-size:11px' bgcolor='#D9E8FF'>";
					echo "<td align='right' >$x -$fabrica_nome($total_chamado)</td>";
					echo "<td align='center'>$total_horas_meses_ant</td>";
					echo "<td align='right' >$total_horas</td>";
					echo "<td align='right' >$total_horas_cobrado</td>";
					echo "</tr>";
					$geral_total_horas_meses_ant = $geral_total_horas_meses_ant + $total_horas_meses_ant;
					$geral_total_horas           = $geral_total_horas           + $total_horas;
					$geral_total_horas_cobrado   = $geral_total_horas_cobrado   + $total_horas_cobrado;
					$geral_total_chamado         = $geral_total_chamado         + $total_chamado;
				}
			}
			////////////////////////////////////////////
				//totalizando horas dos meses anteriores
				$sql22 = "SELECT  sum(data_termino - data_inicio) as total_horas 
					FROM   tbl_hd_chamado_atendente
					JOIN   tbl_hd_chamado USING(hd_chamado)
					JOIN tbl_admin   ON tbl_hd_chamado.admin    = tbl_admin.admin
					JOIN tbl_fabrica ON tbl_hd_chamado.fabrica  = tbl_fabrica.fabrica
					WHERE  tbl_hd_chamado.status <> 'Cancelado'
					AND    tbl_hd_chamado.hd_chamado in (
						SELECT  distinct tbl_hd_chamado.hd_chamado
						FROM   tbl_hd_chamado_atendente
						JOIN   tbl_hd_chamado USING(hd_chamado)
						JOIN tbl_admin   ON tbl_hd_chamado.admin    = tbl_admin.admin
						JOIN tbl_fabrica ON tbl_hd_chamado.fabrica  = tbl_fabrica.fabrica
						JOIN tbl_tipo_chamado on tbl_hd_chamado.tipo_chamado = tbl_tipo_chamado.tipo_chamado
						WHERE  data_inicio between '$data_de 00:00:00' and '$data_ate 23:59:59'
						AND   tbl_hd_chamado.status <> 'Cancelado'
						$query_add3
						GROUP BY tbl_hd_chamado.hd_chamado
						ORDER BY hd_chamado
					)
					and data_inicio < '$data_de 00:00:00'
					$query_add3;";
				//echo $sql22;
				$res = pg_exec ($con,$sql22);
				$total_horas_meses_ant = pg_result($res,0,total_horas);
				//final da totalização dos meses anteriores

				//totalizando horas trabalhadas no mes
				$sql2 = "SELECT sum(data_termino - data_inicio)  as total_horas
					FROM   tbl_hd_chamado_atendente
					JOIN   tbl_hd_chamado USING(hd_chamado)
					WHERE  data_inicio between '$data_de 00:00:00' and '$data_ate 23:59:59' 
					AND   tbl_hd_chamado.status <> 'Cancelado'
					$query_add3";
				//echo $sql2;
				$res = pg_exec ($con,$sql2);
				$total_horas = pg_result($res,0,total_horas);
				//final da totalização de horas trabalhadas no mês

				//totalizando horas trabalhadas no mes em chamados cobrados
				$sql2 = "SELECT 
						CASE WHEN tbl_hd_chamado.data_aprovacao IS NOT NULL THEN
							sum(data_termino - data_inicio) 
						ELSE
							'00:00:00'::interval
						END as total_horas
					INTO TEMP hd_chamado_total2
					FROM   tbl_hd_chamado_atendente
					JOIN   tbl_hd_chamado USING(hd_chamado)
					WHERE  data_inicio between '$data_de 00:00:00' and '$data_ate 23:59:59' 
					AND    data_aprovacao IS NOT NULL
					AND    cobrar IS TRUE
					AND   tbl_hd_chamado.status <> 'Cancelado'
					$query_add3
					GROUP BY tbl_hd_chamado.data_aprovacao
					ORDER BY tbl_hd_chamado.data_aprovacao;

					SELECT sum(total_horas) as total_horas FROM hd_chamado_total2;
					";
				//echo $sql2;
				$res = pg_exec ($con,$sql2);
				$total_horas_cobrado = pg_result($res,0,total_horas);
				$sql2 = "DROP TABLE hd_chamado_total2;";
				$res = pg_exec ($con,$sql2);
				//final da totalização de horas trabalhadas no mês

				// final da totalização

				//calcula qtd chamados
				$sqlq = "SELECT count(tbl_hd_chamado.hd_chamado) as total_chamado 
						FROM   tbl_hd_chamado_atendente
						JOIN   tbl_hd_chamado USING(hd_chamado)
						JOIN tbl_fabrica ON tbl_hd_chamado.fabrica  = tbl_fabrica.fabrica
						JOIN tbl_tipo_chamado on tbl_hd_chamado.tipo_chamado = tbl_tipo_chamado.tipo_chamado
						WHERE  data_inicio between '$data_de 00:00:00' and '$data_ate 23:59:59'
						AND   tbl_hd_chamado.status <> 'Cancelado'
						$query_add3
						GROUP BY tbl_hd_chamado.hd_chamado;";
				//echo $sqlq;
				$resq = pg_exec ($con,$sqlq);
				$total_chamado = pg_numrows($resq);

				//final calcula qtd chamados

				//final da totalização de horas cobradas
			////////////////////////////////////////////
			echo "<tr style='font-size:11px' bgcolor='#D9E8FF'>";
			echo "<td align='right' >Total ($total_chamado)</td>";
			echo "<td align='center'>$total_horas_meses_ant</td>";
			echo "<td align='right' >$total_horas</td>";
			echo "<td align='right' >$total_horas_cobrado</td>";
			echo "</tr>";
			//echo "<tr style='font-size:11px' bgcolor='#D9E8FF'>";
			//echo "<td align='right' >Total ($geral_total_chamado)</td>";
			//echo "<td align='center'>$geral_total_horas_meses_ant</td>";
			//echo "<td align='right' >$geral_total_horas</td>";
			//echo "<td align='right' >$geral_total_horas_cobrado</td>";
			//echo "</tr>";
			echo "</foot>";
			echo "</table>";
		}
	}
}
include "rodape.php";
 ?>
</body>
</html>

