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

$atendente 	    = trim($_GET['atendente_busca']);
$data_de		= @converte_data(trim($_GET['data_de']));
$data_ate 		= @converte_data(trim($_GET['data_ate']));
$x_status         = trim($_GET['x_status']);
//echo $status;
echo "<form name='filtrar' method='GET' ACTION='$PHP_SELF'>";
echo "<table width = '450' align = 'center' cellpadding='0' cellspacing='0' border='0'>";
echo "<tr>";
	echo "<td background='/assist/helpdesk/imagem/fundo_tabela_top_esquerdo_azul_claro.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";	
	echo "<td background='/assist/helpdesk/imagem/fundo_tabela_top_centro_azul_claro.gif'   align = 'center' width='100%' style='font-family: arial ; color:#666666' ><b>Relatório de Horas Trabalhadas</b></td>";
	echo "<td background='/assist/helpdesk/imagem/fundo_tabela_top_direito_azul_claro.gif'  ><img src='/assist/imagens/pixel.gif' width='9'></td>";
echo "</tr>";

$data_de2  = @converte_data($data_de);
$data_ate2 = @converte_data($data_ate);

echo "<tr style='font-family: verdana ; font-size:12px; color: #666666'>";
	echo "<td background='/assist/helpdesk/imagem/fundo_tabela_centro_esquerdo.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";
	echo "<td align='center'> <br>(Deixar em branco o atendente para gerar resumo)<br>Atendente: ";
	$sqlatendente = "SELECT  nome_completo, admin
						FROM    tbl_admin
						WHERE   tbl_admin.fabrica = 10
						AND tbl_admin.responsabilidade IN ('Analista de Help-Desk', 'Programador')
						ORDER BY tbl_admin.nome_completo;";
	$resatendente = pg_exec ($con,$sqlatendente);
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
	echo "<td background='/assist/helpdesk/imagem/fundo_tabela_centro_direito.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";
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

echo "<tr style='font-family: verdana ; font-size:12px; color: #666666'>";
	echo "<td background='/assist/helpdesk/imagem/fundo_tabela_centro_esquerdo.gif'><img src='/assist/imagens/pixel.gif' width='9'></td>";
	echo "<td align='center' nowrap >";
	echo "<table><tr style='font-family: verdana ; font-size:12px; color: #666666'><td >Somente Resolvido:</td><td><input type='radio' name='x_status' id='x_status' value='Resolvido' class='caixa'></td>";
	echo "</tr></table>";
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

	if(strlen($atendente_busca > 0)){
		if(strlen($atendente_busca > 0)) $query_add2 = "AND tbl_hd_chamado_atendente.admin = $atendente";
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
				tbl_hd_chamado_atendente.admin  AS atendente     ,
				tbl_admin.nome_completo         as nome_completo ,
				sum(data_termino - data_inicio) as total_horas   ,
				
				to_char(tbl_hd_chamado.data_aprovacao,'DD/MM/YYYY hh24:mi') as data_aprovacao,
				tbl_tipo_chamado.descricao as tipo_chamado
				FROM   tbl_hd_chamado_atendente
				JOIN   tbl_hd_chamado USING(hd_chamado)
				JOIN tbl_admin   ON tbl_hd_chamado_atendente.admin    = tbl_admin.admin
				JOIN tbl_fabrica ON tbl_hd_chamado.fabrica  = tbl_fabrica.fabrica
				JOIN tbl_tipo_chamado on tbl_hd_chamado.tipo_chamado = tbl_tipo_chamado.tipo_chamado
				WHERE  data_inicio between '$data_de 00:00:00' and '$data_ate 23:59:59'
				AND   tbl_hd_chamado.status <> 'Cancelado' ";
		if($x_status == 'Resolvido'){
			$sql .= "AND  tbl_hd_chamado.status = 'Resolvido' ";
		}
		$sql .= "$query_add2 $query_add3
				GROUP BY tbl_hd_chamado.hd_chamado     ,
				tbl_hd_chamado.titulo          ,
				tbl_hd_chamado.cobrar          ,
				tbl_hd_chamado.status          ,
				tbl_hd_chamado.exigir_resposta ,
				tbl_fabrica.nome               ,
				tbl_hd_chamado_atendente.admin ,
				tbl_admin.nome_completo,
				data_aprovacao,
				data,
				tbl_tipo_chamado.descricao
				ORDER BY hd_chamado;";
		//echo $sql;
		$res = pg_exec ($con,$sql);

		if (pg_numrows($res) > 0) {

			echo "<table width = '450' align = 'center' cellpadding='2' cellspacing='1' border='0' name='relatorio' id='relatorio' class='tablesorter' >";
			echo "<thead>";
			echo "<tr style='font-size:11px' bgcolor='#D9E8FF'>";
			echo "<td align='center'><b>#</b></td>";
			echo "<td align='center'><b>Titulo</b></td>";
			echo "<td align='center'><b>Data</b></td>";
			echo "<td align='center'><b>Tipo</b></td>";
			echo "<td align='center'><b>Status</b></td>";
			echo "<td align='center'><b>Solicitante</b></td>";
			echo "<td align='center'><b>Atendente</b></td>";
			echo "<td align='center'><b>Fabricante</b></td>";
			//echo "<td align='center'><b>Horas trabalhadas<br>meses anteriores</b></td>";
			echo "<td align='center'><b>Horas trabalhadas<br>mês atual</b></td>";
			echo "<td align='center'><b>Horas trabalhadas<br>mês atual<br>(cobrado)</b></td>";
			echo "</tr>";
			echo "</thead>";
			echo "<tbody>";
			$total_chamados_geral=0;
			$t_total_prazo_horas = 0;
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
				$atendente       = trim(pg_result($res,$x,atendente));

				$a="";
				if($exigir_resposta=='t') $a = $imagem;
				if($status=="Novo" or $status =="Análise")$cor_status="#000099";
				if($status=="Execução")                   $cor_status="#FF0000";
				if($status=="Aguard.Execução")            $cor_status="#FF9900";
				if($status=="Resolvido")$cor_status="#009900";

				$sql2 = "SELECT nome_completo FROM tbl_admin WHERE admin = $atendente";
				$res2 = pg_exec ($con,$sql2);
				$atendente = pg_result($res2,0,0);

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
					JOIN tbl_admin   ON tbl_hd_chamado_atendente.admin    = tbl_admin.admin
					JOIN tbl_fabrica ON tbl_hd_chamado.fabrica  = tbl_fabrica.fabrica
					WHERE  hd_chamado = $hd_chamado 
					AND   tbl_hd_chamado.status <> 'Cancelado' ";
				if($x_status == 'Resolvido'){
					$sql_uti .="AND  tbl_hd_chamado.status = 'Resolvido' ";
				}
				$sql_uti .= "and data_inicio < '$data_de 00:00:00'
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
				//totalizando horas prevista no mes em chamados cobrados
				$sqlp = "SELECT prazo_horas  as total_prazo_horas
						FROM   tbl_hd_chamado
						WHERE  tbl_hd_chamado.hd_chamado = $hd_chamado";
				$resp = pg_exec ($con,$sqlp);
				$total_prazo_horas = pg_result($resp,0,total_prazo_horas);
				//final da totalização de horas trabalhadas no mês
				$t_total_prazo_horas = $t_total_prazo_horas + $total_prazo_horas;
				echo "<tr class='Conteudo' style='background-color: $cor;'>";
				echo "<td align='left' height='15'><a href='adm_chamado_detalhe.php?hd_chamado=$hd_chamado'>$hd_chamado</a> $a</td>";
				echo "<td align='left'>$titulo</td>";
				echo "<td align='left'>$data</td>";
				echo "<td align='left'>$tipo_chamado</td>";
				echo "<td nowrap'><font color='$cor_status' size='1'><B>$status </B></font></td>";
				echo "<td nowrap'>$nome_completo</td>";
				echo "<td nowrap'>$atendente</td>";
				echo "<td nowrap'>$fabrica_nome</td>";
				//echo "<td align='right'>$total_horas_gastas</td>";
				echo "<td align='right'>$total_horas</td>";
				echo "<td align='right'>$total_horas_cobrado</td>";
				echo "<td align='right'>$total_prazo_horas</td>";
				echo "</tr>";
			}
			echo "</tbody>";
			echo "<tfoot>";

			//totalizando horas dos meses anteriores
			$sql22 = "SELECT sum(data_termino - data_inicio)  as total_horas
						FROM   tbl_hd_chamado_atendente
						JOIN   tbl_hd_chamado USING(hd_chamado)
						WHERE  data_inicio < '$data_de 00:00:00'
						AND hd_chamado in (
						SELECT distinct hd_chamado
						FROM   tbl_hd_chamado_atendente
						JOIN   tbl_hd_chamado USING(hd_chamado)
						WHERE  data_inicio between '$data_de 00:00:00' and '$data_ate 23:59:59' 
						AND   tbl_hd_chamado.status <> 'Cancelado'
						$query_add2  $query_add3
						)
						$query_add2  $query_add3";

			$sql22 = "SELECT  sum(data_termino - data_inicio) as total_horas 
					FROM   tbl_hd_chamado_atendente
					JOIN   tbl_hd_chamado USING(hd_chamado)
					JOIN tbl_admin   ON tbl_hd_chamado_atendente.admin    = tbl_admin.admin
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
						AND   tbl_hd_chamado.status <> 'Cancelado'";
					if($x_status == 'Resolvido'){
						$sql22 .= "AND  tbl_hd_chamado.status = 'Resolvido' ";
					}
					$sql22 .= "
						 $query_add3
						GROUP BY tbl_hd_chamado.hd_chamado
						ORDER BY hd_chamado
					)
					and data_inicio < '$data_de 00:00:00'
					$query_add2  $query_add3;";
			//echo "$sql22 - $status";
			$res = pg_exec ($con,$sql22);
			$total_horas_meses_ant = pg_result($res,0,total_horas);
			//final da totalização dos meses anteriores

			//totalizando horas trabalhadas no mes
			$sql2 = "SELECT sum(data_termino - data_inicio)  as total_horas
					FROM   tbl_hd_chamado_atendente
					JOIN   tbl_hd_chamado USING(hd_chamado)
					WHERE  data_inicio between '$data_de 00:00:00' and '$data_ate 23:59:59' 
					AND   tbl_hd_chamado.status <> 'Cancelado' ";
			if($x_status == 'Resolvido'){
				$sql2 .= "AND  tbl_hd_chamado.status = 'Resolvido' ";
			}
			$sql2 .= "
					$query_add2  $query_add3";
			//echo $sql2;
			$res = pg_exec ($con,$sql2);
			$total_horas = pg_result($res,0,total_horas);
			//final da totalização de horas trabalhadas no mês

			//totalizando horas trabalhadas no mes em chamados cobrados
			$sql2 = "SELECT sum(data_termino - data_inicio)  as total_horas
					FROM   tbl_hd_chamado_atendente
					JOIN   tbl_hd_chamado USING(hd_chamado)
					WHERE  data_inicio between '$data_de 00:00:00' and '$data_ate 23:59:59' 
					AND    data_aprovacao IS NOT NULL
					AND    cobrar IS TRUE
					AND   tbl_hd_chamado.status <> 'Cancelado' ";
			if($x_status == 'Resolvido'){
				$sql2 .= "AND  tbl_hd_chamado.status = 'Resolvido' ";
			}
			$sql2 .= "
					$query_add2  $query_add3";
			//echo $sql2;
			$res = pg_exec ($con,$sql2);
			$total_horas_cobrado = pg_result($res,0,total_horas);
			//final da totalização de horas trabalhadas no mês


			echo "<tr style='font-size:11px' bgcolor='#D9E8FF'>";
			echo "<td align='right' colspan='7'><b>Total chamados $x</b></td>";
			echo "<td align='right' colspan='1'><b>Total hora</b></td>";
			//echo "<td align='right'><b>$total_horas_meses_ant</b></td>";
			echo "<td align='right'><b>$total_horas</b></td>";
			echo "<td align='right'><b>$total_horas_cobrado</b></td>";
			echo "<td align='right'><b>$t_total_prazo_horas</b></td>";
			echo "</tr>";
			echo "</foot>";
			echo "</table>";
		}
	}else{
		$sqlf = "SELECT  nome_completo, admin
				FROM    tbl_admin
				WHERE   tbl_admin.fabrica = 10 
				AND tbl_admin.responsabilidade IN ('Analista de Help-Desk', 'Programador')
				ORDER BY tbl_admin.nome_completo;";
		//echo $sqlf;
		$resf = pg_exec($con,$sqlf);
		if (pg_numrows($resf) > 0) {
			echo "<table width = '450'  align = 'center' cellpadding='2' cellspacing='1' border='0' name='relatorio' >";
			echo "<tr  style='font-size:11px' bgcolor='#D9E8FF'>";
				echo "<td align='center'><b>Atendente(total chamados)</b></td>";
			//	echo "<td align='center'><b>Hora trabalhada<br>meses anteriores</b></td>";
				echo "<td align='center'><b>Hora trabalhada<br>mês atual</b></td>";
				echo "<td align='center'><b>Hora trabalhada<br>mês atual<br>(cobrado)</b></td>";
				echo "<td align='center'><b>Hora prevista<br>mês atual<br>(cobrado)</b></td>";
			echo "</tr>";
			$geral_total_chamado         = 0;
			
			for ($x = 0 ; $x < pg_numrows($resf) ; $x++){
				$nome_completo   = pg_result($resf,$x,nome_completo);
				$admin           = pg_result($resf,$x,admin);
				$query_add2 = "AND tbl_hd_chamado_atendente.admin = $admin";
				//totalizando
				//totalizando horas dos meses anteriores
				$sql22 = "SELECT sum(data_termino - data_inicio)  as total_horas
						FROM   tbl_hd_chamado_atendente
						JOIN   tbl_hd_chamado USING(hd_chamado)
						WHERE  data_inicio < '$data_de 00:00:00'
						AND hd_chamado in (
							SELECT  tbl_hd_chamado.hd_chamado
							FROM   tbl_hd_chamado_atendente
							JOIN   tbl_hd_chamado USING(hd_chamado)
							JOIN tbl_admin   ON tbl_hd_chamado.admin    = tbl_admin.admin
							JOIN tbl_fabrica ON tbl_hd_chamado.fabrica  = tbl_fabrica.fabrica
							JOIN tbl_tipo_chamado on tbl_hd_chamado.tipo_chamado = tbl_tipo_chamado.tipo_chamado
							WHERE  data_inicio between '$data_de 00:00:00' and '$data_ate 23:59:59'
							AND   tbl_hd_chamado.status <> 'Cancelado' ";
					if($x_status == 'Resolvido'){
						$sql22 .= "AND  tbl_hd_chamado.status = 'Resolvido' ";
					}
					$sql22 .= " 
							$query_add2  $query_add3
							GROUP BY tbl_hd_chamado.hd_chamado
							ORDER BY tbl_hd_chamado.hd_chamado
						)
						$query_add2  $query_add3";
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
						AND   tbl_hd_chamado.status <> 'Cancelado' ";
					if($x_status == 'Resolvido'){
						$sql22 .= "AND  tbl_hd_chamado.status = 'Resolvido' ";
					}
					$sql22 .= " 
						$query_add3
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
					AND   tbl_hd_chamado.status <> 'Cancelado' ";
					if($x_status == 'Resolvido'){
						$sql2 .= "AND  tbl_hd_chamado.status = 'Resolvido' ";
					}
					$sql2 .= " 
					$query_add2  $query_add3";
				//echo $sql2;
				$res = pg_exec ($con,$sql2);
				$total_horas = pg_result($res,0,total_horas);
				//final da totalização de horas trabalhadas no mês

				//totalizando horas trabalhadas no mes em chamados cobrados
				$sql2 = "SELECT sum(data_termino - data_inicio)  as total_horas
					FROM   tbl_hd_chamado_atendente
					JOIN   tbl_hd_chamado USING(hd_chamado)
					WHERE  data_inicio between '$data_de 00:00:00' and '$data_ate 23:59:59' 
					AND    data_aprovacao IS NOT NULL
					AND    cobrar IS TRUE
					AND   tbl_hd_chamado.status <> 'Cancelado' ";
					if($x_status == 'Resolvido'){
						$sql2 .= "AND  tbl_hd_chamado.status = 'Resolvido' ";
					}
					$sql2 .= " 
					$query_add2  $query_add3";
				//echo $sql2;
				$res = pg_exec ($con,$sql2);
				$total_horas_cobrado = pg_result($res,0,total_horas);
				//final da totalização de horas trabalhadas no mês

				// final da totalização

				//calcula qtd chamados
				$sqlq = "SELECT count(tbl_hd_chamado.hd_chamado) as total_chamado 
						FROM   tbl_hd_chamado_atendente
						JOIN   tbl_hd_chamado USING(hd_chamado)
						JOIN tbl_admin   ON tbl_hd_chamado.admin    = tbl_admin.admin
						JOIN tbl_tipo_chamado on tbl_hd_chamado.tipo_chamado = tbl_tipo_chamado.tipo_chamado
						WHERE  data_inicio between '$data_de 00:00:00' and '$data_ate 23:59:59'
						AND   tbl_hd_chamado.status <> 'Cancelado' ";
					if($x_status == 'Resolvido'){
						$sqlq .= "AND  tbl_hd_chamado.status = 'Resolvido' ";
					}
					$sqlq .= " 
						$query_add2  $query_add3
						GROUP BY tbl_hd_chamado.hd_chamado;";
				//echo $sqlq;
				$resq = pg_exec ($con,$sqlq);
				$total_chamado = pg_numrows($resq);

				//calcula qtd prevista
				$sqlq = "DROP TABLE tmp_prazo_horas";
				$resq = @pg_exec ($con,$sqlq);

				$sqlq = "SELECT distinct tbl_hd_chamado.hd_chamado, tbl_hd_chamado.prazo_horas
						INTO TEMP TABLE tmp_prazo_horas
						FROM   tbl_hd_chamado
						JOIN   tbl_hd_chamado_atendente USING(hd_chamado)
						JOIN tbl_admin   ON tbl_hd_chamado.admin    = tbl_admin.admin
						JOIN tbl_tipo_chamado on tbl_hd_chamado.tipo_chamado = tbl_tipo_chamado.tipo_chamado
						WHERE  data_inicio between '$data_de 00:00:00' and '$data_ate 23:59:59'
						AND   tbl_hd_chamado.status <> 'Cancelado' ";
					if($x_status == 'Resolvido'){
						$sqlq .= "AND  tbl_hd_chamado.status = 'Resolvido' ";
					}
					$sqlq .= " 
						$query_add2  $query_add3;

						SELECT sum(prazo_horas) AS prazo_horas FROM tmp_prazo_horas;
						
						;";
				//echo $sqlq;
				$resq = pg_exec ($con,$sqlq);
				$total_prazo_horas = pg_result($resq,0,prazo_horas);

				//final calcula qtd chamados

				//final da totalização de horas cobradas
				if(strlen($total_horas_meses_ant)!=0 or 
					strlen($total_horas)!= 0 or
					strlen($total_horas_cobrado)!=0) {
					echo "<tr style='font-size:11px' bgcolor='#D9E8FF'>";
					echo "<td align='right' nowrap>$admin -$nome_completo($total_chamado)</td>";
			//		echo "<td align='center'>$total_horas_meses_ant</td>";
					echo "<td align='right' >$total_horas</td>";
					echo "<td align='right' >$total_horas_cobrado</td>";
					echo "<td align='right' >$total_prazo_horas</td>";
					echo "</tr>";
					$geral_total_chamado         = $geral_total_chamado         + $total_chamado;
				}
			}
			/////////////////////////////////////////////
			//totalizando horas dos meses anteriores
			$sql22 = "SELECT sum(data_termino - data_inicio)  as total_horas
						FROM   tbl_hd_chamado_atendente
						JOIN   tbl_hd_chamado USING(hd_chamado)
						WHERE  data_inicio < '$data_de 00:00:00'
						AND hd_chamado in (
						SELECT distinct hd_chamado
						FROM   tbl_hd_chamado_atendente
						JOIN   tbl_hd_chamado USING(hd_chamado)
						WHERE  data_inicio between '$data_de 00:00:00' and '$data_ate 23:59:59' 
						AND   tbl_hd_chamado.status <> 'Cancelado' ";
					if($x_status == 'Resolvido'){
						$sql22 .= "AND  tbl_hd_chamado.status = 'Resolvido' ";
					}
					$sql22 .= " 
						$query_add3
						)
						$query_add3";
			//echo $sql22;
			$res = pg_exec ($con,$sql22);
			$total_horas_meses_ant = pg_result($res,0,total_horas);
			//final da totalização dos meses anteriores

			//totalizando horas trabalhadas no mes
			$sql2 = "SELECT sum(data_termino - data_inicio)  as total_horas
					FROM   tbl_hd_chamado_atendente
					JOIN   tbl_hd_chamado USING(hd_chamado)
					WHERE  data_inicio between '$data_de 00:00:00' and '$data_ate 23:59:59' 
					AND   tbl_hd_chamado.status <> 'Cancelado' ";
					if($x_status == 'Resolvido'){
						$sql2 .= "AND  tbl_hd_chamado.status = 'Resolvido' ";
					}
					$sql2 .= " 
					$query_add3";
			//echo $sql2;
			$res = pg_exec ($con,$sql2);
			$total_horas = pg_result($res,0,total_horas);
			//final da totalização de horas trabalhadas no mês

			//totalizando horas trabalhadas no mes em chamados cobrados
			$sql2 = "SELECT sum(data_termino - data_inicio)  as total_horas
					FROM   tbl_hd_chamado_atendente
					JOIN   tbl_hd_chamado USING(hd_chamado)
					WHERE  data_inicio between '$data_de 00:00:00' and '$data_ate 23:59:59' 
					AND    data_aprovacao IS NOT NULL
					AND    cobrar IS TRUE
					AND   tbl_hd_chamado.status <> 'Cancelado' ";
					if($x_status == 'Resolvido'){
						$sql2 .= "AND  tbl_hd_chamado.status = 'Resolvido' ";
					}
					$sql2 .= " 
					$query_add3";
			//echo $sql2;
			$res = pg_exec ($con,$sql2);
			$total_horas_cobrado = pg_result($res,0,total_horas);
			//final da totalização de horas trabalhadas no mês
			//totalizando horas previstas no mes em chamados cobrados
			$sql2 = "SELECT sum(prazo_horas)  as total_prazo_horas
					FROM   tbl_hd_chamado_atendente
					JOIN   tbl_hd_chamado USING(hd_chamado)
					WHERE  data_inicio between '$data_de 00:00:00' and '$data_ate 23:59:59' 
					AND    data_aprovacao IS NOT NULL
					AND    cobrar IS TRUE
					AND   tbl_hd_chamado.status <> 'Cancelado' ";
					if($x_status == 'Resolvido'){
						$sql2 .= "AND  tbl_hd_chamado.status = 'Resolvido' ";
					}
					$sql2 .= " 
					$query_add3";
			//echo $sql2;
			$res = pg_exec ($con,$sql2);
			$total_prazo_horas = pg_result($res,0,total_prazo_horas);
			$total_prazo_horas = 0; //Não funcionou....
			//final da totalização de horas trabalhadas no mês
			/////////////////////////////////////////////
			echo "<tr style='font-size:11px' bgcolor='#D9E8FF'>";
			echo "<td align='right' >Total($geral_total_chamado)(*)</td>";
			//echo "<td align='center'>$total_horas_meses_ant</td>";
			echo "<td align='right' >$total_horas</td>";
			echo "<td align='right' >$total_horas_cobrado</td>";
			echo "<td align='right' >$total_prazo_horas</td>";
			echo "</tr>";
			echo "<tr style='font-size:11px' bgcolor='#D9E8FF'><td align='center' colspan='3'>(*)Este total de chamados pode <b>não bater</b> com o total de chamados do fabricante. O motivo é que um chamado pode ser trabalhado por mais de um atendente!</td></tr>";
			echo "</foot>";
			echo "</table>";
		}
	}
}
include "rodape.php";
 ?>
</body>
</html>
