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

<? include "menu.php"; ?>

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


echo "<br/>";
echo "<form name='filtrar' method='POST' ACTION='$PHP_SELF'>";
echo "<table width = '400' align = 'center' cellpadding='0' cellspacing='0' border='0'>";
echo "<tr>";
	echo "<td background='/assist/helpdesk/imagem/fundo_tabela_top_esquerdo_azul_claro.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";
	echo "<td background='/assist/helpdesk/imagem/fundo_tabela_top_centro_azul_claro.gif'   align = 'center' width='100%' style='font-family: arial ; color:#666666' ><b>SELECIONE O MÊS PARA FAZER A PESQUISA</b></td>";
	echo "<td background='/assist/helpdesk/imagem/fundo_tabela_top_direito_azul_claro.gif'  ><img src='/assist/imagens/pixel.gif' width='9'></td>";
echo "</tr>";

echo "<tr style='font-family: verdana ; font-size:12px; color: #666666'>";
	echo "<td background='/assist/helpdesk/imagem/fundo_tabela_centro_esquerdo.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";
	echo "<td align='center'><br/>";

	$sql = "SELECT  DISTINCT mes, ano
		FROM     tbl_hd_franquia
		WHERE    fabrica = $login_fabrica
		AND      NOT (periodo_fim IS NULL)
		ORDER BY ano DESC,mes DESC";
	$res = pg_query ($con,$sql);

	echo "<select class='frm' style='width: 90px;font-size:15px' name='mesano'>\n";
	echo "<option value=''></option>\n";
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
	echo "	<td background='/assist/helpdesk/imagem/fundo_tabela_centro_direito.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";
echo "</tr>";

echo "<tr>";
	echo "<td background='/assist/helpdesk/imagem/fundo_tabela_centro_esquerdo.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";
	echo "<td nowrap >&nbsp;</td>";
	echo "<td background='/assist/helpdesk/imagem/fundo_tabela_centro_direito.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";
echo "</tr>";

echo "<tr>";
	echo "<td background='/assist/helpdesk/imagem/fundo_tabela_centro_esquerdo.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";
	echo "<td nowrap ><br><CENTER><INPUT TYPE=\"submit\" name='btn_acao' value=\"Pesquisar\"></CENTER></td>";
	echo "<td background='/assist/helpdesk/imagem/fundo_tabela_centro_direito.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";
echo "</tr>";

echo "<tr>";
	echo "<td background='/assist/helpdesk/imagem/fundo_tabela_baixo_esquerdo.gif'><img src='/assist/imagens/pixel.gif' width='9'></td>";
	echo "<td background='/assist/helpdesk/imagem/fundo_tabela_baixo_centro.gif' align = 'center' width='100%'></td>";
	echo "<td background='/assist/helpdesk/imagem/fundo_tabela_baixo_direito.gif'><img src='/assist/imagens/pixel.gif' width='9'></td>";
echo "</tr>";

echo "</table>";
echo "</FORM>";

if(isset($mesano) and empty($msg_erro)){
		list($ano,$mes) = explode("-",$mesano);
		$sql = "SELECT periodo_inicio,periodo_fim
				FROM tbl_hd_franquia
				WHERE fabrica = $login_fabrica
				AND   mes=$mes
				AND   ano = $ano";
		$res=pg_query($con,$sql);
		if(pg_num_rows($res)>0){
			$periodo_inicio= pg_fetch_result($res,0,periodo_inicio);
			$periodo_fim= pg_fetch_result($res,0,periodo_fim);
		}

		$query_add2 = "AND tbl_hd_chamado.fabrica = $login_fabrica";
		
		$data_de = $periodo_inicio;
		$data_ate= $periodo_fim;


		$sql = "SELECT  tbl_hd_chamado.hd_chamado                ,
			to_char(tbl_hd_chamado.data,'DD/MM/YYYY') as data,
			tbl_hd_chamado.titulo                            ,
			tbl_hd_chamado.cobrar                            ,

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
			WHERE  data_inicio between '$data_de' and '$data_ate'
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
		$res = pg_query ($con,$sql);

		if (pg_num_rows($res) > 0) {

			$fabrica_nome = trim(pg_fetch_result($res,0,fabrica_nome));
			$fabrica_nome = str_replace(" ","_",$fabrica_nome);
			echo "<table width = '750' align = 'center' cellpadding='2' cellspacing='1' border='0' name='relatorio' id='relatorio' class='tablesorter' >";
			echo "<tr style='font-size:11px' bgcolor='#D9E8FF'>";
			echo "<td><b>Relatório de Chamados de $mes/$ano</b></td>";

			echo "</tr>";

			echo "<table width = '750' align = 'center' cellpadding='2' cellspacing='1' border='0' name='relatorio' id='relatorio' class='tablesorter' >";
			echo "<thead>";
			echo "<tr style='font-size:11px' bgcolor='#D9E8FF'>";
			echo "<td align='center'><b>#</b></td>";
			echo "<td align='center'><b>Titulo</b></td>";
			echo "<td align='center'><b>Data</b></td>";
			echo "<td align='center'><b>Status</b></td>";
			echo "<td align='center'><b>Solicitante</b></td>";
			echo "<td align='center'><b>Hora<BR>Cobrada</b></td>";
			echo "<td align='center'><b>Data<BR>Aprovação</b></td>";
			echo "</tr>";
			echo "</thead>";
			echo "<tbody>";


			$total_chamados_geral=0;
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

				$a="";
				if($exigir_resposta=='t') $a = $imagem;
				if($status=="Novo" or $status =="Análise")$cor_status="#000099";
				if($status=="Execução")                   $cor_status="#FF0000";
				if($status=="Aguard.Execução")            $cor_status="#FF9900";
				if($status=="Resolvido")$cor_status="#009900";

				$h_cobrada       = number_format($h_cobrada,1,',','.');
				echo "<tr class='Conteudo' style='background-color: $cor;'>";
				echo "<td align='left' height='15'>$hd_chamado</td>";


				echo "<td align='left'>$titulo</td>";
				echo "<td align='left'>$data</td>";
				echo "<td nowrap'><font color='$cor_status' size='1'><B>$status </B></font></td>";
				echo "<td nowrap'>$nome_completo</td>";
				echo "<td align='right'>";
				echo "$h_cobrada</td>";
				echo "<td align='right'>$data_aprovacao</td>";
				echo "</tr>";
			}
			echo "</tbody>";
			echo "<tfoot>";

			$sql2 = "SELECT DISTINCT tbl_hd_chamado.hd_chamado,
				tbl_hd_chamado.hora_desenvolvimento as t_cobrada
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

			echo "<tr style='font-size:11px' bgcolor='#D9E8FF'>";
			echo "<td align='right' colspan='5'><b>TOTAL CHAMADOS = $x</b></td>";
			echo "<td align='right' colspan='1'><b>TOTAL HORAS</b></td>";
			echo "<td align='right' title='Desconsiderando horas repetidas'><b>$t_cobrada</b></td>";

			$sqlf = "SELECT hora_utilizada, hora_franqueada, hora_faturada, saldo_hora FROM tbl_hd_franquia where fabrica = $login_fabrica and mes =$mes and ano =$ano;";
			$resf = pg_query ($con,$sqlf);
			$hora_utilizada = pg_fetch_result($resf,0,hora_utilizada);
			$hora_franqueada = pg_fetch_result($resf,0,hora_franqueada);
			$hora_faturada = pg_fetch_result($resf,0,hora_faturada);
			$saldo_hora = pg_fetch_result($resf,0,saldo_hora);
			echo "<tr style='font-size:11px' bgcolor='#D9E8FF'>";
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
			echo "<td align='right' colspan='6'><b>Saldo de Horas</b></td>";
			$h = ($hora_franqueada + $saldo_hora) - ($t_cobrada ) + ($hora_faturada);
			echo "<td align='right' ><b>$h</b></td>";
			echo "<tr style='font-size:11px' bgcolor='#D9E8FF'>";
			echo "<td align='right' colspan='6'></td>";
			echo "<td align='right' ><b></b></td>";

			echo "</tr>";
			echo "</tfoot>";
			echo "</table>";
			
		}
}
include "rodape.php";
 ?>
</body>
</html>
