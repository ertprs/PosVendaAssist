<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';
$data_de2  = @converte_data($data_de);
$data_ate2 = @converte_data($data_ate);


$fechar_mes  =$_GET['fechar_mes'];
$data_inicial=$_GET['data_inicial'];
$data_final=$_GET['data_final'];
$data_inicial = '2009-04-27';
$data_final = '2009-05-25';
if(strlen($fechar_mes) > 0) {

$sql = " SELECT fabrica FROM tbl_hd_franquia where periodo_fim IS NULL 
		AND   mes in  (SELECT to_char(current_date,'MM')::numeric )
		AND    ano in (SELECT to_char(current_date,'YYYY')::numeric )";
$res = pg_exec($con,$sql);
for($i =0;$i<pg_numrows($res);$i++) {
	$fecha_fabrica = pg_result($res,$i,fabrica);
	if(strlen($fecha_fabrica) >0){
		$sql="SELECT sum(hora_desenvolvimento) AS total_desenvolvimento,
				hora_utilizada
			FROM tbl_hd_chamado
			JOIN tbl_hd_franquia USING(fabrica)
			WHERE tbl_hd_franquia.fabrica=$fecha_fabrica
			AND   mes in  (SELECT to_char(current_date,'MM')::numeric )
			AND    ano in (SELECT to_char(current_date,'YYYY')::numeric )
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
				AND    ano in (SELECT to_char(current_date,'YYYY')::numeric )
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
				AND    mes in (SELECT to_char(current_date,'MM')::numeric ) 
				AND    ano in (SELECT to_char(current_date,'YYYY')::numeric )";
		$res=pg_exec($con,$sql);
		$msg_erro.=pg_errormessage($con);

		$sql = " UPDATE tbl_hd_franquia set saldo_hora = hora_franqueada*2  where mes=5 and ano=2009 and saldo_hora > hora_franqueada*2 and fabrica in (6,1,5,8,15,2,14,43,47,19,24,75,20,78,11);";
		$res = pg_exec($con,$sql);
		$msg_erro.=pg_errormessage($con);
		
		$sql = " UPDATE tbl_hd_franquia set saldo_hora = hora_franqueada  where mes=5 and ano=2009 and saldo_hora > hora_franqueada and fabrica in (51,45,7,50,30,3,77,35);";
		$res = pg_exec($con,$sql);
				
		// socinter não pode usar mais que 12 horas
		// precision não pode usar mais que 35 horas.
		$msg_erro.=pg_errormessage($con);

		if(strlen($msg_erro) > 0){
			$res = @pg_exec ($con,"ROLLBACK TRANSACTION");
			$msg_erro .= 'Houve um erro na hora de fechar o mês.';
		}else{
			$res = @pg_exec($con,"COMMIT");
		}
	}
}
}
$TITULO = "Suporte";

?>
<script type="text/javascript" src="js/jquery-1.3.2.min.js"></script>
<link rel="stylesheet" type="text/css" href="js/datePicker.v1.css" title="default" media="screen" />
<script type="text/javascript" src="js/datePicker.v1.js"></script>
<script type="text/javascript" src="js/jquery.maskedinput.js"></script>


<script type="text/javascript" charset="utf-8">
	$(document).ready(function(){
		$('#data_de').maskedinput("99/99/9999");
		$('#data_ate').maskedinput("99/99/9999");
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

	$data_de2 = "2009-04-27";
	$data_ate2 = "2009-05-25";
if( strlen($data_de2)>0 AND strlen($data_ate2)>0){

	$data_de  = @converte_data(trim($_GET['data_de']));
	$data_ate = @converte_data(trim($_GET['data_ate']));
	$data_de = "2009-04-27";
	$data_ate = "2009-05-25";
		$fabrica_busca = 78;
			if(strlen($fabrica_busca) > 0){
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
			$query_add2  
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
		$res = pg_exec ($con,$sql);

		if (pg_numrows($res) > 0) {

			$fabrica_nome = trim(pg_result($res,0,fabrica_nome));
			$fabrica_nome = str_replace(" ","_",$fabrica_nome);
			$de           = trim($_GET['data_de']);
			$ate          = trim($_GET['data_ate']);
			$conteudo  = "";
			$conteudo .="<div class=Section1>
			<p class=MsoNormal align=center style='text-align:center;mso-line-height-alt:10.0pt'><b>Relatório de Horas Franqueadas de $de até $ate - $fabrica_nome<o:p></o:p></b></p>";


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

			$conteudo .= "<tbody>";

			$total_chamados_geral=0;
			for ($x = 0 ; $x < pg_numrows($res) ; $x++){
				$hd_chamado      = trim(pg_result($res,$x,hd_chamado));
				$titulo          = trim(pg_result($res,$x,titulo));
				$cobrar          = trim(pg_result($res,$x,cobrar));
				$exigir_resposta = trim(pg_result($res,$x,exigir_resposta));
				$status          = trim(pg_result($res,$x,status));
				$fabrica_nome    = trim(pg_result($res,$x,fabrica_nome));
				$fabrica_nome = str_replace(" ","_",$fabrica_nome);
				$nome_completo   = trim(pg_result($res,$x,nome_completo));
				$total_horas     = trim(pg_result($res,$x,total_horas));
				$total_horas_cobrado     = trim(pg_result($res,$x,total_horas_cobrado));
				$data_aprovacao  = trim(pg_result($res,$x,data_aprovacao));
				$data            = trim(pg_result($res,$x,data));
				$h_cobrada       = trim(pg_result($res,$x,hora_desenvolvimento));

				$a="";
				$imagem = "<img src='/assist/admin/imagens_admin/status_vermelho.gif' align='absmiddle' width='8' title='Aguardando a resposta do Solicitante do chamado'>";
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
							$query_add2  
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
					$query_add2 ;

				SELECT  sum(tmp_hd_chamadox.t_cobrada) as t_cobrada
				FROM tmp_hd_chamadox;";
			//echo $sql2;
			$res2 = pg_exec ($con,$sql2);
			$t_cobrada = pg_result($res2,0,t_cobrada);
			//final da totalização de horas cobradas

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




}
include "rodape.php";
 ?>
</body>
</html>
