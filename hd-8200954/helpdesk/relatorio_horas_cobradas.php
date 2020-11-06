<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';

$TITULO = "Relatório de Chamados";

?>

<style>

.error{
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 12px;
	font-weight: normal;
	background-color: #FF0000;
}

</style>



<link rel="stylesheet" href="js/blue/style.css" type="text/css" id="" media="print, projection, screen" />

<!--[if lt IE 8]>
<style>
table.tabela{
	empty-cells:show;
    border-collapse:collapse;
	border-spacing: 2px;
}
</style>
<![endif]-->

<?php include "menu.php";
?>

<table width="700" align="center" bgcolor="#FFFFFF" border='0'>
	<tr>
		<td >
			<table width='100%' border='0'>
				<tr>
					<td valign='middle'>

<table width="700" align="center"><tr><td style='font-family: arial ; color: #666666; font-size:10px' align="justify">
<?
echo "<tr style='font-family: arial ; color: #666666' align='center'>";
	echo "<td nowrap align='left'>";
		echo "<img src='/assist/admin/imagens_admin/status_amarelo.gif' valign='absmiddle'> ";
		if($sistema_lingua == 'ES') echo "&nbsp;Aguardando aprobación&nbsp;";
		else                        echo "&nbsp;<a href='chamado_lista.php?status=Aprovação'>Aguarda Aprovação</a>&nbsp;";
	echo "</td>";
	echo "<td width='50%' nowrap align='left'>";
		echo "<img src='/assist/admin/imagens_admin/status_cinza.gif' valign='absmiddle'> ";
		if($sistema_lingua == 'ES') echo "&nbsp;Meus Chamados&nbsp;";
		else                        echo "&nbsp;<a href='chamado_lista.php?admin=admin'>Meus Chamados</a>&nbsp;";
	echo "</td>";
	echo "<td nowrap align='left'>";
		echo "<img src='/assist/admin/imagens_admin/status_azul.gif' align='absmiddle'> ";
		if($sistema_lingua == 'ES') echo "&nbsp;Pendiente&nbsp;";
		else                        echo "&nbsp;<a href='chamado_lista.php?status=Análise&exigir_resposta=f'>Pendentes Telecontrol</a>&nbsp;";
	echo "</td>";
	echo "<td nowrap align='left'>";
		echo "<img src='/assist/admin/imagens_admin/status_vermelho.gif' valign='absmiddle'> ";
		if($sistema_lingua == 'ES') echo "&nbsp;Aguardando su respuesta&nbsp;";
		else                        echo "&nbsp;<a href='chamado_lista.php?status=Análise&exigir_resposta=t'>Aguarda sua resposta</a>&nbsp;";
	echo "</td>";
	echo "<td nowrap align='left'>";
		echo "<img src='/assist/admin/imagens_admin/status_verde.gif' valign='absmiddle'> ";
		if($sistema_lingua == 'ES') echo "&nbsp;Resolvido&nbsp;";
		else                        echo "&nbsp;<a href='chamado_lista.php?status=Resolvido&filtro=1'>Meus Resolvidos</a>&nbsp;";
	echo "</td>";
	echo "<td nowrap align='left'>";
		echo "<img src='/assist/admin/imagens_admin/status_azul_bb.gif' valign='absmiddle'> ";
		if($sistema_lingua == 'ES') echo "&nbsp;Todos Chamados&nbsp;";
		else                        echo "&nbsp;<a href='chamado_lista.php?todos=todos&filtro=1'>Todos Chamados</a>&nbsp;";
	echo "</td>";
		echo "<td nowrap align='left'>";
		echo "<img src='/assist/admin/imagens_admin/status_rosa.gif' valign='absmiddle'> ";
		echo "&nbsp;<a href='relatorio_horas_cobradas.php'>Relatório Mensal</a>&nbsp;";
	echo "</td>";
	echo "</tr>";
echo "</table>";

?>

		</td>
	</tr>
	<tr>
		<td colspan="2" bgcolor="<?=$menu_cor_linha?>" width="1" height="1"></td>
	</tr>
</table>
<?
$btn_acao = $_POST['btn_acao'];
if(isset($_POST['btn_acao'])) {
	$mesano = $_POST['mesano'];

	if(empty($mesano)) {
		$msg_erro = "SELECIONE O MÊS/ANO PARA FAZER A PESQUISA";
	}

}

if(strlen($msg_erro)>0){
	echo "<DIV class='error'>".$msg_erro."</DIV>";
}

//Inicio do FORM de Pesquisa
echo "<br/>";
echo "<form name='filtrar' method='POST' ACTION='$PHP_SELF'>";
echo "<table width='700' align='center' cellpadding='2' cellspacing='0' border='0' class='formulario'>";
//HD 213585 INICIO -----
echo "<tr>";
	echo "<td class='titulo_tabela' colspan='2'><b>Parâmetros de Pesquisa</b></td>";
echo "</tr>";
//HD 213585 FIM -----
echo "<tr style='font-family: verdana ; font-size:12px; color: #000000'>";
	echo"<td align='right' width='44%'> Mês:";
	echo"</td>";

	echo "<td align='left'>";

	$sql = "SELECT  DISTINCT mes, ano
		FROM     tbl_hd_franquia
		WHERE    fabrica = $login_fabrica
		AND      NOT (periodo_fim IS NULL)
		ORDER BY ano DESC,mes DESC";
	$res = pg_query ($con,$sql);
//HD 213585 INICIO -----
	echo "<select class='frm' style='width: 137px;font-size:15px' name='mesano'>\n";
	echo "<option value=''></option>\n";
//HD 213585 FIM-----
	for ($x = 0 ; $x < pg_num_rows($res) ; $x++){
		$ano   = trim(pg_fetch_result($res,$x,ano));
		$mes   = trim(pg_fetch_result($res,$x,mes));
		if(strlen($mes) == 1) {
			$xmes = "0".$mes;
		}else{
			$xmes = $mes;
		}
		echo "<option value='$ano-$mes'>$xmes/$ano</option>\n";
	}
	echo "</select>\n";
	echo "</td>";

echo "</tr>";



echo "<tr>";
	echo "<td nowrap align='center' colspan='2'><br><INPUT TYPE=\"submit\" name='btn_acao' value=\"Pesquisar\"></td>";
echo "</tr>";


echo "</table>";
echo "</FORM>";
//end FORM de Pesquisa


if(isset($mesano) and empty($msg_erro)){
		list($ano,$mes) = explode("-",$mesano);
		$sql = "SELECT 
				to_char(periodo_inicio,'DD/MM/YYYY') as periodo_inicio , 
				to_char(periodo_fim,'DD/MM/YYYY')    as periodo_fim
				FROM tbl_hd_franquia
				WHERE fabrica = $login_fabrica
				AND   mes=$mes
				AND   ano=$ano";
		$res=pg_query($con,$sql);
		if(pg_num_rows($res)>0){
			$periodo_inicio= pg_fetch_result($res,0,'periodo_inicio');
			$periodo_fim= pg_fetch_result($res,0,'periodo_fim');
		}

		$query_add2 = "AND tbl_hd_chamado.fabrica = $login_fabrica";
		
		list($di,$mi,$yi) = explode("/",$periodo_inicio);
		list($df,$mf,$yf) = explode("/",$periodo_fim);

		$data_de  = $yi."-".$mi."-".$di;
		$data_ate = $yf."-".$mf."-".$df;

		$sql = "SELECT  tbl_hd_chamado.hd_chamado            ,
			to_char(tbl_hd_chamado.data,'DD/MM/YYYY') as data,
			tbl_hd_chamado.titulo                            ,
			tbl_hd_chamado.cobrar                            ,
			tbl_hd_chamado.hora_faturada                     ,
			CASE WHEN data_aprovacao between '$data_de' and '$data_ate' THEN
				tbl_hd_chamado.hora_desenvolvimento
			ELSE
				0
			END as hora_desenvolvimento,
			tbl_hd_chamado.status                            ,
			tbl_hd_chamado.exigir_resposta                   ,
			tbl_fabrica.nome                AS fabrica_nome  ,
			tbl_admin.nome_completo         as nome_completo ,
			to_char(tbl_hd_chamado.data_aprovacao,'DD/MM/YYYY hh24:mi') as data_aprovacao
			FROM   tbl_hd_chamado_atendente
			JOIN   tbl_hd_chamado USING(hd_chamado)
			JOIN tbl_admin   ON tbl_hd_chamado.admin    = tbl_admin.admin
			JOIN tbl_fabrica ON tbl_hd_chamado.fabrica  = tbl_fabrica.fabrica
			WHERE  data_inicio between '$data_de 00:00' and '$data_ate 23:59'
			AND data_aprovacao between '$data_de 00:00' and '$data_ate 23:59'
			$query_add2  $query_add3
			GROUP BY tbl_hd_chamado.hd_chamado     ,
			tbl_hd_chamado.titulo          ,
			tbl_hd_chamado.cobrar          ,
			tbl_hd_chamado.hora_desenvolvimento,
			tbl_hd_chamado.hora_faturada   ,
			tbl_hd_chamado.status          ,
			tbl_hd_chamado.exigir_resposta ,
			tbl_fabrica.nome               ,
			tbl_admin.nome_completo,
			data_aprovacao,
			data
			ORDER BY hd_chamado;";
		$res = pg_query ($con,$sql);

		$num_rows = pg_num_rows($res);
		
		if (pg_num_rows($res) > 0) {

			$fabrica_nome = trim(pg_fetch_result($res,0,fabrica_nome));
			$fabrica_nome = str_replace(" ","_",$fabrica_nome);
//Texto avulso
			echo "<table width = '700' align = 'center' cellpadding='3' cellspacing='1' border='0' name='relatorio' id='relatorio' class='texto_avulso' >";
				
				echo "<tr class='texto_avulso'>";
				echo"<td >Este relatório mostra todos os chamados que tiveram interação de nossos atendentes no período especificado, bem como os chamados aprovados dentro do período especificado </td>";
				echo "</tr>";
			
			echo "</table>";
			echo "<br>";

//Relatorios de Chamados ...

			echo "<table width = '700' align = 'center' cellpadding='3' cellspacing='0' border='0' class='texto_avulso' >";

				echo "<tr>";
				//HD 213585 INICIO -----
				if(strlen($mes)==1){
					$ymes = "0".$mes;
				}else{
					$ymes = $mes;
				}
				echo "<td align='right' width='45%'><b>Relatório de Chamados de $ymes/$ano</b></td>";
				//HD 213585 FIM -----

				echo "<td align='left' width='50%'>&nbsp;Periodo de $periodo_inicio até $periodo_fim</td>";
				echo "</tr>";
			
			echo "</table>";
//end Relatorios de Chamados...

			
			echo "<br><br>";
			
//Tabela do Relatório
			echo "<table width = '100%' align = 'center' cellpadding='3' cellspacing='1' border='0' name='relatorio' id='relatorio' class='tabela'>";
			


			echo "<tr class='titulo_coluna'>";
			//HD 213585 INICIO -----
				echo "<td align='center'><b>Nº HD</b></td>";
			//HD 213585 FIM -----
				echo "<td align='left' width='30%'><b>Título</b></td>";
				echo "<td align='left'><b>Data Abertura</b></td>";
				echo "<td align='left'><b>Status</b></td>";
				echo "<td align='left'><b>Solicitante</b></td>";
				//HD 213585 INICIO -----
				echo "<td align='center'><b>Horas<BR>Franquia</b></td>";
				//HD 213585 FIM -----
				echo "<td align='center'><b>Horas<br>Faturadas</b></td>";
				echo "<td align='center'><b>Data<BR>Aprovação</b></td>";
			echo "</tr>";
			echo "<tbody>";


			$total_chamados_geral=0;
			//HD 213585 INICIO -----
			$hora_franqueada = 0;
			$hora_faturada = 0;
			//HD 213585 FIM -----
			
			for ($x = 0 ; $x < pg_num_rows($res) ; $x++){
				$hd_chamado      = trim(pg_fetch_result($res,$x,hd_chamado));
				$titulo          = trim(pg_fetch_result($res,$x,titulo));
				$cobrar          = trim(pg_fetch_result($res,$x,cobrar));
				$exigir_resposta = trim(pg_fetch_result($res,$x,exigir_resposta));
				$status          = trim(pg_fetch_result($res,$x,status));
				$nome_completo   = trim(pg_fetch_result($res,$x,nome_completo));
				$data_aprovacao  = trim(pg_fetch_result($res,$x,data_aprovacao));
				$data            = trim(pg_fetch_result($res,$x,data));
				$h_cobrada       = trim(pg_fetch_result($res,$x,hora_desenvolvimento));
				$h_faturada      = trim(pg_fetch_result($res,$x,hora_faturada));
				$h_cobrada = ($h_faturada > 0)?$h_cobrada-$h_faturada:$h_cobrada;
				$a="";
				if($exigir_resposta=='t') $a = $imagem;
				
				$cor_status = "#000000";
				if($status=="Aguard.Admin") $cor_status="#FF0000";
				if($status=="Resolvido")	$cor_status="#009900";

				$h_cobrada       = number_format($h_cobrada,1,',','.');


				$cor = ($x % 2) ? "#F7F5F0" : "#F1F4FA";


				echo "<tr class='Conteudo' style='background-color: $cor;' height='34px'>";
				echo "<td align='left' height='15'><a href='chamado_detalhe.php?hd_chamado=$hd_chamado'>$hd_chamado</a></td>";
				echo "<td align='left'>$titulo</td>";
				echo "<td align='left'>$data</td>";
				echo "<td nowrap'><font color='$cor_status' size='1'><B>$status </B></font></td>";
				echo "<td nowrap'>$nome_completo</td>";
				echo "<td align='right'>";
				echo "$h_cobrada</td>";
				if ($h_faturada == 0){
					echo "<td align='right'>0,0</td>";
				}else{
				echo "<td align='right'>$h_faturada</td>";
				}
				echo "<td align='right'>$data_aprovacao</td>";
				echo "</tr>";
				$total_hora_cobrada += $h_cobrada;
				//HD 213585 INICIO -----
				$hora_franqueada += $h_cobrada;
				$hora_faturada	+= $h_faturada;
				//HD 213585 FIM -----
			}
			
			$sql2 = "SELECT DISTINCT tbl_hd_chamado.hd_chamado,
			case when hora_faturada > 0 then hora_desenvolvimento - hora_faturada else tbl_hd_chamado.hora_desenvolvimento end as t_cobrada          ,
			tbl_hd_chamado.hora_faturada as h_faturada
			INTO TEMP TABLE tmp_hd_chamadox
			FROM   tbl_hd_chamado
			JOIN   tbl_hd_chamado_atendente USING(hd_chamado)
			WHERE  data_inicio between '$data_de' and '$data_ate'
			AND    data_aprovacao between '$data_de' and '$data_ate'
				$query_add2 $query_add3;

			SELECT  sum(tmp_hd_chamadox.t_cobrada) as t_cobrada
			FROM tmp_hd_chamadox;";
			$res2 = pg_query ($con,$sql2);
			$t_cobrada = pg_fetch_result($res2,0,t_cobrada);

			$sqlf = "SELECT hora_utilizada, hora_franqueada, hora_faturada, saldo_hora FROM tbl_hd_franquia where fabrica = $login_fabrica and mes =$mes and ano =$ano;";
			$resf = pg_query ($con,$sqlf);
			$hora_utilizada = pg_fetch_result($resf,0,hora_utilizada);
			//HD 213585 INICIO -----
			//$hora_franqueada = pg_fetch_result($resf,0,hora_franqueada);
			//$hora_faturada = pg_fetch_result($resf,0,hora_faturada);
			//HD 213585 FIM -----
			$saldo_hora = pg_fetch_result($resf,0,saldo_hora);
		//HD 213585 INICIO -----
			echo "<tr class='titulo_coluna'>";
				echo "<td colspan='2' align='left'>TOTAL: $num_rows Chamados</td>";
				echo "<td></td>";
				echo "<td></td>";
				echo "<td></td>";
				if ($t_cobrada == 0){
					echo "<td  align='right'>0,0</td>";
				}else{
				echo "<td align='right'>$t_cobrada</td>";
				}
				echo "<td align='right'>$hora_faturada</td>";
				echo "<td></td>";
		//HD 213585 FIM -----
			echo "</table>";
//end Tabela do Relatório

			echo "<br>";

//tabela HORAS De FRANQUIA
			$hora_franqueada = pg_fetch_result($resf,0,hora_franqueada);
			echo "<table align='center' width='700px' class='tabela' cellpadding='3' cellspacing='1' border='0'>";

//titulo_tabela
			echo"<tr>";
			
				echo "<td colspan='2' class='titulo_tabela' align='center'>Horas de Franquia";
				echo "</td>";

			echo"</tr>";
			
//end titulo_tabela

			echo"<tr >";
				echo "<td width='70%' style='background-color:#F7F5F0;'><b>Saldo de Horas do Mês Anterior</b>";
				echo "</td>";

				echo "<td width='30%' align='right' style='background-color:#F7F5F0;'><b>$saldo_hora</b>";
				echo "</td>";
			echo"</tr>";

			echo"<tr>";
				echo "<td width='70%' style='background-color:#F1F4FA;'><b>Horas Franqueadas do Mês</b>";
				echo "</td>";

				echo "<td width='30%' align='right' style='background-color:#F1F4FA;'><b>$hora_franqueada</b>";
				echo "</td>";
			echo"</tr>";

			echo"<tr>";
				echo "<td width='70%' style='background-color:#F7F5F0;'><b>Total de Horas de Franquia Disponíveis para o Período</b>";
				echo "</td>";

				$total_horas_disponiveis_periodo = $saldo_hora + $hora_franqueada;
				echo "<td width='30%' align='right' style='background-color:#F7F5F0;'><b>$total_horas_disponiveis_periodo</b>";
				echo "</td>";
			echo"</tr>";

			echo"<tr>";
				echo "<td width='70%' style='background-color:#F1F4FA;'><b>Total de Horas de Franquia Utilizadas no Período</b>";
				echo "</td>";
				echo "<td width='30%' align='right' style='background-color:#F1F4FA;'><b>$t_cobrada</b>";
				echo "</td>";
			echo"</tr>";

			echo"<tr>";
				echo "<td width='70%' style='background-color:#F7F5F0;'><b>Saldo de Horas ao Final do Período</b>";
				echo "</td>";
				$h = $total_horas_disponiveis_periodo - $t_cobrada;
				echo "<td align='right' style='background-color:#F7F5F0;'><b>$h</b></td>";
			echo"</tr>";

			echo "</table>";
//end HORAS De FRANQUIA
		}
}
include "rodape.php";
 ?>
</body>
</html>
