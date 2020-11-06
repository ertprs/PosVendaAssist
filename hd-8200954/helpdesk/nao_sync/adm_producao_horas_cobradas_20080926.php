<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';
$data_de2  = @converte_data($data_de);
$data_ate2 = @converte_data($data_ate);


$fecha_fabrica=$_GET['fecha_fabrica'];
$data_inicial=$_GET['data_inicial'];
$data_final=$_GET['data_final'];
if(strlen($fecha_fabrica) >0){

	$sql="SELECT sum(hora_desenvolvimento) AS total_desenvolvimento,
			hora_utilizada
		FROM tbl_hd_chamado
		JOIN tbl_hd_franquia USING(fabrica)
		WHERE tbl_hd_franquia.fabrica=$fecha_fabrica
		AND   mes in  (SELECT to_char(current_date,'MM')::numeric )
		AND data_aprovacao
		BETWEEN '$data_inicial 00:00:00' and '$data_final 23:59:59'
		AND tbl_hd_chamado.status <> 'Cancelado'
		GROUP BY hora_utilizada,tbl_hd_chamado.fabrica,hora_franqueada;
	";

	$res=pg_exec($con,$sql);
	$msg_erro.=pg_errormessage($con);
	if(pg_numrows($res) >0){
		$total_desenvolvimento = pg_result($res,0,total_desenvolvimento);
		$hora_utilizada        = pg_result($res,0,hora_utilizada);
	}
	$res = @pg_exec($con,"BEGIN TRANSACTION");
	$sql="UPDATE tbl_hd_franquia SET periodo_fim='$data_final 23:59:59'
			WHERE fabrica=$fecha_fabrica
			AND mes in (SELECT to_char(current_date,'MM')::numeric )
			AND periodo_fim is null ";
	$res=pg_exec($con,$sql);
	$msg_erro.=pg_errormessage($con);
if(strlen($total_desenvolvimento) ==0 or $total_desenvolvimento ==null) $total_desenvolvimento = 0;


	$sql="INSERT INTO tbl_hd_franquia (
			fabrica               ,
			mes                   ,
			ano                   ,
			hora_franqueada       ,
			valor_hora_franqueada ,
			saldo_hora            ,
			hora_utilizada        ,
			hora_faturada         ,
			periodo_inicio
			)
			SELECT $fecha_fabrica,
			       case when mes =12 then 1 else mes+1 end as mes,
				   case when mes =12 then ano +1 else ano end as ano,
				   hora_franqueada,
				   valor_hora_franqueada,
				   (hora_franqueada+saldo_hora) - ($total_desenvolvimento) + (hora_faturada),
				   0,
				   0,
				   (periodo_fim::date + 1|| ' 00:00:00')::date
			FROM tbl_hd_franquia
			WHERE fabrica=$fecha_fabrica
			AND    mes in (SELECT to_char(current_date,'MM')::numeric ) ";
         	$res=pg_exec($con,$sql);
	$msg_erro.=pg_errormessage($con);
	if(strlen($msg_erro) > 0){
		$res = @pg_exec ($con,"ROLLBACK TRANSACTION");
		$msg_erro .= 'Houve um erro na hora de fechar o mês.';
	}else{
		$res = @pg_exec($con,"COMMIT");
	}
}
$TITULO = "Suporte";

?>
<script type="text/javascript" src="js/jquery-1.2.6.pack.js"></script>
<link rel="stylesheet" type="text/css" href="js/datePicker.v1.css" title="default" media="screen" />
<script type="text/javascript" src="js/datePicker.v1.js"></script>
<script type="text/javascript" src="js/jquery.maskedinput.js"></script>


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

$data_de		= @converte_data(trim($_GET['data_de']));
$data_ate 		= @converte_data(trim($_GET['data_ate']));


if( strlen($data_de2)>0 AND strlen($data_ate2)>0){

	$data_de  = @converte_data(trim($_GET['data_de']));
	$data_ate = @converte_data(trim($_GET['data_ate']));

	if(strlen($fabrica_busca > 0)){
		$query_add2 = "AND tbl_hd_chamado.fabrica = $fabrica_busca";


		$sql = "SELECT  tbl_hd_chamado.hd_chamado                ,
			to_char(tbl_hd_chamado.data,'DD/MM/YYYY') as data,
			tbl_hd_chamado.titulo                            ,
			tbl_hd_chamado.cobrar                            ,

			CASE WHEN data_aprovacao between '$data_de 00:00:00' and '$data_ate 23:59:59' THEN
				tbl_hd_chamado.hora_desenvolvimento
			ELSE
				0
			END as hora_desenvolvimento,

			tbl_hd_chamado.status                            ,
			tbl_hd_chamado.exigir_resposta                   ,
			tbl_fabrica.nome                AS fabrica_nome  ,
			tbl_admin.nome_completo         as nome_completo ,
			sum(data_termino - data_inicio) as total_horas   ,
			sum(data_termino - data_inicio) as total_horas_cobrado,

			to_char(tbl_hd_chamado.data_aprovacao,'DD/MM/YYYY hh24:mi') as data_aprovacao
			FROM   tbl_hd_chamado_atendente
			JOIN   tbl_hd_chamado USING(hd_chamado)
			JOIN tbl_admin   ON tbl_hd_chamado.admin    = tbl_admin.admin
			JOIN tbl_fabrica ON tbl_hd_chamado.fabrica  = tbl_fabrica.fabrica
			WHERE  data_inicio between '$data_de 00:00:00' and '$data_ate 23:59:59'
			$query_add2  $query_add3
			GROUP BY tbl_hd_chamado.hd_chamado     ,
			tbl_hd_chamado.titulo          ,
			tbl_hd_chamado.cobrar          ,
			tbl_hd_chamado.hora_desenvolvimento,
			tbl_hd_chamado.status          ,
			tbl_hd_chamado.exigir_resposta ,
			tbl_fabrica.nome               ,
			tbl_admin.nome_completo,
			data_aprovacao,
			data
			ORDER BY hd_chamado;";
		//echo $sql;
		$res = pg_exec ($con,$sql);

		if (pg_numrows($res) > 0) {

			$fabrica_nome = trim(pg_result($res,0,fabrica_nome));
			$de           = trim($_GET['data_de']);
			$ate          = trim($_GET['data_ate']);
			echo "<table width = '750' align = 'center' cellpadding='2' cellspacing='1' border='0' name='relatorio' id='relatorio' class='tablesorter' >";
			echo "<tr style='font-size:11px' bgcolor='#D9E8FF'>";
			echo "<td><b>Relatório de Horas Franqueadas de $de até $ate - $fabrica_nome</b></td>";

			$conteudo .="<div class=Section1>
			<p class=MsoNormal align=center style='text-align:center;mso-line-height-alt:10.0pt'><b>Relatório de Horas Franqueadas de $de até $ate - $fabrica_nome<o:p></o:p></b></p>";

			echo "</tr>";

			echo "<table width = '750' align = 'center' cellpadding='2' cellspacing='1' border='0' name='relatorio' id='relatorio' class='tablesorter' >";
			echo "<thead>";
			echo "<tr style='font-size:11px' bgcolor='#D9E8FF'>";
			echo "<td align='center'><b>#</b></td>";
			echo "<td align='center'><b>Titulo</b></td>";
			echo "<td align='center'><b>Data</b></td>";
			echo "<td align='center'><b>Status</b></td>";
			echo "<td align='center'><b>Solicitante</b></td>";
			echo "<td align='center'><b>Fabricante</b></td>";
			echo "<td align='center'><b>Hora<BR>Cobrada</b></td>";
			echo "<td align='center'><b>Data<BR>Aprovação</b></td>";
			echo "</tr>";
			echo "</thead>";

				$conteudo .="<p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt'><b
				style='mso-bidi-font-weight:normal'><span style='mso-fareast-language:#00FF;
				mso-bidi-language:#00FF'>";
				$conteudo .= "<table width = '750' align = 'center' cellpadding='2' cellspacing='1' border='0' name='relatorio' id='relatorio' class='tablesorter' >";
				$conteudo .= "<thead>";
				$conteudo .= "<tr style='font-size:11px' bgcolor='#D9E8FF'>";
				$conteudo .= "<td align='center'><b>#</b></td>";
				$conteudo .= "<td align='center'><b>Titulo</b></td>";
				$conteudo .= "<td align='center'><b>Data</b></td>";
				$conteudo .= "<td align='center'><b>Status</b></td>";
				$conteudo .= "<td align='center'><b>Solicitante</b></td>";
				$conteudo .= "<td align='center'><b>Fabricante</b></td>";
				$conteudo .= "<td align='center'><b>Hora<BR>Cobrada</b></td>";
				$conteudo .= "<td align='center'><b>Data<BR>Aprovação</b></td>";
				$conteudo .= "</tr>";
				$conteudo .= "</thead>";

			echo "<tbody>";

			$conteudo .= "<tbody>";

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
				$h_cobrada       = trim(pg_result($res,$x,hora_desenvolvimento));

				$a="";
				if($exigir_resposta=='t') $a = $imagem;
				if($status=="Novo" or $status =="Análise")$cor_status="#000099";
				if($status=="Execução")                   $cor_status="#FF0000";
				if($status=="Aguard.Execução")            $cor_status="#FF9900";
				if($status=="Resolvido")$cor_status="#009900";

				// calculo de horas já utilizadas
				$sql_uti = "SELECT  tbl_hd_chamado.hd_chamado          ,
							tbl_hd_chamado.titulo                      ,
							tbl_hd_chamado.cobrar                      ,
							tbl_hd_chamado.hora_desenvolvimento        ,
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
							and data_inicio < '$data_de 00:00:00'
							$query_add2  $query_add3
							GROUP BY tbl_hd_chamado.hd_chamado     ,
							tbl_hd_chamado.titulo          ,
							tbl_hd_chamado.cobrar          ,
							tbl_hd_chamado.hora_desenvolvimento,
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
				$h_cobrada       = number_format($h_cobrada,1,',','.');
				echo "<tr class='Conteudo' style='background-color: $cor;'>";
				echo "<td align='left' height='15'><a href='adm_chamado_detalhe.php?hd_chamado=$hd_chamado'>$hd_chamado</a> $a</td>";


				echo "<td align='left'>$titulo</td>";
				echo "<td align='left'>$data</td>";
				echo "<td nowrap'><font color='$cor_status' size='1'><B>$status </B></font></td>";
				echo "<td nowrap'>$nome_completo</td>";
				echo "<td nowrap'>$fabrica_nome</td>";
				echo "<td align='right'>";
				echo "$h_cobrada</td>";
				echo "<td align='right'>$data_aprovacao</td>";
				echo "</tr>";


					$conteudo .= "<tr class='Conteudo' style='background-color: $cor;'>";
					$conteudo .= "<td align='left' height='15'><a href='adm_chamado_detalhe.php?hd_chamado=$hd_chamado'>$hd_chamado</a> $a</td>";


					$conteudo .= "<td align='left'>$titulo</td>";
					$conteudo .= "<td align='left'>$data</td>";
					$conteudo .= "<td nowrap'><font color='$cor_status' size='1'><B>$status </B></font></td>";
					$conteudo .= "<td nowrap'>$nome_completo</td>";
					$conteudo .= "<td nowrap'>$fabrica_nome</td>";
					$conteudo .= "<td align='right'>";
					$conteudo .= "$h_cobrada</td>";
					$conteudo .= "<td align='right'>$data_aprovacao</td>";
					$conteudo .= "</tr>";


			}
			echo "</tbody>";
			echo "<tfoot>";

				$conteudo .=  "</tbody>";
				$conteudo .=  "<tfoot>";


			//totalizando horas cobradas
			$sql2 = "SELECT DISTINCT tbl_hd_chamado.hd_chamado,
				tbl_hd_chamado.hora_desenvolvimento as t_cobrada
				INTO TEMP TABLE tmp_hd_chamadox
				FROM   tbl_hd_chamado
				JOIN   tbl_hd_chamado_atendente USING(hd_chamado)
				WHERE  data_inicio between '$data_de 00:00:00' and '$data_ate 23:59:59'
				AND    data_aprovacao between '$data_de 00:00:00' and '$data_ate 23:59:59'
					$query_add2 $query_add3;

				SELECT  sum(tmp_hd_chamadox.t_cobrada) as t_cobrada
				FROM tmp_hd_chamadox;";
			//echo $sql2;
			$res2 = pg_exec ($con,$sql2);
			$t_cobrada = pg_result($res2,0,t_cobrada);
			//final da totalização de horas cobradas

			echo "<tr style='font-size:11px' bgcolor='#D9E8FF'>";
			echo "<td align='right' colspan='5'><b>TOTAL CHAMADOS = $x</b></td>";
			echo "<td align='right' colspan='1'><b>TOTAL HORAS</b></td>";
			echo "<td align='right' title='Desconsiderando horas repetidas'><b>$t_cobrada</b></td>";

				$conteudo .= "<tr style='font-size:11px' bgcolor='#D9E8FF'>";
				$conteudo .= "<td align='right' colspan='5'><b>TOTAL CHAMADOS = $x</b></td>";
				$conteudo .= "<td align='right' colspan='1'><b>TOTAL HORAS</b></td>";
				$conteudo .= "<td align='right' title='Desconsiderando horas repetidas'><b>$t_cobrada</b></td>";

			$sqlf = "SELECT hora_utilizada, hora_franqueada, hora_faturada, saldo_hora FROM tbl_hd_franquia where fabrica = $fabrica_busca and mes in (SELECT to_char(current_date,'MM')::numeric) and ano in (SELECT to_char(current_date,'YYYY')::numeric);";
			$resf = pg_exec ($con,$sqlf);
			$hora_utilizada = pg_result($resf,0,hora_utilizada);
			$hora_franqueada = pg_result($resf,0,hora_franqueada);
			$hora_faturada = pg_result($resf,0,hora_faturada);
			$saldo_hora = pg_result($resf,0,saldo_hora);
			echo "<tr style='font-size:11px' bgcolor='#D9E8FF'>";
			//echo "<td align='right' colspan='6'><b>Sub Total de Horas</b></td>";
			//echo "<td align='right' ><b>$hora_utilizada</b></td>";
			echo "<tr style='font-size:11px' bgcolor='#D9E8FF'>";
			echo "<td align='right' colspan='6'><b>Horas Franqueadas do Mês</b></td>";
			echo "<td align='right' ><b>$hora_franqueada</b></td>";
			echo "<tr style='font-size:11px' bgcolor='#D9E8FF'>";
			echo "<td align='right' colspan='6'><b>Horas Faturadas do Mês</b></td>";
			echo "<td align='right' ><b>$hora_faturada</b></td>";
			echo "<tr style='font-size:11px' bgcolor='#D9E8FF'>";
			echo "<td align='right' colspan='6'><b>Saldo de Horas do Mês Anterior</b></td>";
			echo "<td align='right' ><b>$saldo_hora</b></td>";
			echo "<tr style='font-size:11px' bgcolor='#D9E8FF'>";
			echo "<td align='right' colspan='6'><b>Saldo de Horas (*)</b></td>";
			$h = ($hora_franqueada + $saldo_hora) - ($t_cobrada ) + ($hora_faturada);
			echo "<td align='right' ><b>$h</b></td>";
			echo "<tr style='font-size:11px' bgcolor='#D9E8FF'>";
			echo "<td align='right' colspan='6'>(*) Este total deve ser incluído no saldo do próximo mês.</td>";
			echo "<td align='right' ><b></b></td>";

			echo "</tr>";
			echo "</tfoot>";
			echo "</table>";

				$conteudo .=  "<tr style='font-size:11px' bgcolor='#D9E8FF'>";
				$conteudo .=  "<tr style='font-size:11px' bgcolor='#D9E8FF'>";
				$conteudo .=  "<td align='right' colspan='6'><b>Horas Franqueadas do Mês</b></td>";
				$conteudo .=  "<td align='right' ><b>$hora_franqueada</b></td>";
				$conteudo .=  "<tr style='font-size:11px' bgcolor='#D9E8FF'>";
				$conteudo .=  "<td align='right' colspan='6'><b>Horas Faturadas do Mês</b></td>";
				$conteudo .=  "<td align='right' ><b>$hora_faturada</b></td>";
				$conteudo .=  "<tr style='font-size:11px' bgcolor='#D9E8FF'>";
				$conteudo .=  "<td align='right' colspan='6'><b>Saldo de Horas do Mês Anterior</b></td>";
				$conteudo .=  "<td align='right' ><b>$saldo_hora</b></td>";
				$conteudo .=  "<tr style='font-size:11px' bgcolor='#D9E8FF'>";
				$conteudo .=  "<td align='right' colspan='6'><b>Saldo de Horas (*)</b></td>";
				$conteudo .=  "<td align='right' ><b>$h</b></td>";
				$conteudo .=  "</tfoot>";
				$conteudo .=  "</table>";
				$conteudo .=  "<o:p></o:p></span></b></p></div>";

				echo `rm /tmp/relatorio/relatorio_$fabrica_nome.htm`;
				echo `rm /var/www/assist/www/helpdesk/relatorios/relatorio_$fabrica_nome.pdf`;


				if(strlen($msg_erro) == 0){
					$abrir = fopen("/tmp/relatorio/relatorio_$fabrica_nome.htm", "w");
					if (!fwrite($abrir, $conteudo)) {
						$msg_erro = "Erro escrevendo no arquivo ($filename)";
					}
					fclose($abrir);
				}


				//gera o pdf
				echo `htmldoc --webpage --no-duplex --no-embedfonts --header ... --permissions no-modify,no-copy --fontsize 8.5 --no-title -f /tmp/relatorio/relatorio_$fabrica_nome.pdf /tmp/relatorio/relatorio_$fabrica_nome.htm`;
				echo `mv  /tmp/relatorio/relatorio_$fabrica_nome.pdf /var/www/assist/www/helpdesk/relatorios/relatorio_$fabrica_nome.pdf`;

			?>
			<script type="text/javascript">
				if(confirm("Deseja fechar o mês de franquia de horas e abrir o controle para o próximo mês?") == true) {
					window.location="adm_producao_horas_cobradas.php?fecha_fabrica=<?=$fabrica_busca;?> &data_inicial=<?=$data_de;?>&data_final=<?=$data_ate;?>";
				}
			</script>
	<?
		}
	}else{
		$sqlf = 'SELECT fabrica, nome from tbl_fabrica ORDER BY fabrica';
		$resf = pg_exec($con,$sqlf);
		if (pg_numrows($resf) > 0) {
			echo "<table  align = 'center' cellpadding='2' cellspacing='1' border='0' name='relatorio' >";
			echo "<tr  style='font-size:11px' bgcolor='#D9E8FF'>";
				echo "<td><b>Fábrica</b></td>";
				echo "<td><b>Qtd.Chamados</b></td>";
				echo "<td><b>Total Hora</b></td>";
			echo "</tr>";
			$total_chamado_geral = 0;
			$total_t_cobrada     = 0;

			for ($x = 0 ; $x < pg_numrows($resf) ; $x++){
				$fabrica_busca   = pg_result($resf,$x,fabrica);
				$fabrica_nome    = pg_result($resf,$x,nome);
				$query_add2 = "AND tbl_hd_chamado.fabrica = $fabrica_busca";

				//totalizando horas cobradas
				$sql2 = "SELECT DISTINCT tbl_hd_chamado.hd_chamado,
					tbl_hd_chamado.hora_desenvolvimento as t_cobrada
					INTO TEMP TABLE tmp_hd_chamadox
					FROM   tbl_hd_chamado
					JOIN   tbl_hd_chamado_atendente USING(hd_chamado)
					WHERE  data_inicio between '$data_de 00:00:00' and '$data_ate 23:59:59'
					AND    data_aprovacao between '$data_de 00:00:00' and '$data_ate 23:59:59'
					$query_add2 $query_add3;

					SELECT  sum(tmp_hd_chamadox.t_cobrada) as t_cobrada
					FROM tmp_hd_chamadox;";
				//echo $sql2;
				$res = pg_exec ($con,$sql2);
				$t_cobrada = pg_result($res,0,t_cobrada);

				$sql3 = "DROP TABLE tmp_hd_chamadox;";
				$res = pg_exec ($con,$sql3);

				//calcula qtd chamados
				$sqlq = "SELECT count(tbl_hd_chamado.hd_chamado) as total_chamado
						FROM tbl_hd_chamado_atendente JOIN tbl_hd_chamado USING(hd_chamado)
						JOIN tbl_admin ON tbl_hd_chamado.admin = tbl_admin.admin
						JOIN tbl_fabrica ON tbl_hd_chamado.fabrica = tbl_fabrica.fabrica
						WHERE  data_inicio between '$data_de 00:00:00' and '$data_ate 23:59:59'
						$query_add2  $query_add3
						GROUP BY tbl_hd_chamado.hd_chamado;";
				//echo $sqlq;
				$resq = pg_exec ($con,$sqlq);
				$total_chamado = pg_numrows($resq);

				//final calcula qtd chamados

				//final da totalização de horas cobradas
				if(strlen($t_cobrada)!=0){
					echo "<tr style='font-size:11px' bgcolor='#D9E8FF'>";
					echo "<td align='right' >$x -$fabrica_nome</td>";
					echo "<td align='center'>$total_chamado</td>";
					echo "<td align='right' >$t_cobrada</td>";
					echo "</tr>";
					$total_chamado_geral = $total_chamado_geral + $total_chamado;
					$total_t_cobrada = $total_t_cobrada + $t_cobrada;
				}
			}
			echo "<tr style='font-size:11px' bgcolor='#D9E8FF'>";
			echo "<td align='right' >Total</td>";
			echo "<td align='center'>$total_chamado_geral</td>";
			echo "<td align='right' >$total_t_cobrada</td>";
			echo "</tr>";
			echo "</foot>";
			echo "</table>";
		}
	}
}else{
include "menu.php";

echo "<form name='filtrar' method='GET' ACTION='$PHP_SELF'>";
echo "<table width = '400' align = 'center' cellpadding='0' cellspacing='0' border='0'>";
echo "<tr>";
	echo "<td background='/assist/helpdesk/imagem/fundo_tabela_top_esquerdo_azul_claro.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";
	echo "<td background='/assist/helpdesk/imagem/fundo_tabela_top_centro_azul_claro.gif'   align = 'center' width='100%' style='font-family: arial ; color:#666666' ><b>Relatório de Horas Cobradas das Franquias</b></td>";
	echo "<td background='/assist/helpdesk/imagem/fundo_tabela_top_direito_azul_claro.gif'  ><img src='/assist/imagens/pixel.gif' width='9'></td>";
echo "</tr>";

$data_de2  = @converte_data($data_de);
$data_ate2 = @converte_data($data_ate);

echo "<tr style='font-family: verdana ; font-size:12px; color: #666666'>";
	echo "<td background='/assist/helpdesk/imagem/fundo_tabela_centro_esquerdo.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";
	echo "<td align='center'> <br>(Deixar em branco a fábrica para gerar resumo)<br>Fabricante: ";

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


}
include "rodape.php";
 ?>
</body>
</html>
