<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';

if($login_fabrica <> 10) {
		include "rodape.php" ;exit;
}

$data_de2  = @converte_data($data_de);
$data_ate2 = @converte_data($data_ate);
$tipo = $_REQUEST['tipo'];

if(!empty($_GET['pesquisar']) and (strlen($data_de2)==0 OR strlen($data_ate2)==0)) $msg_erro = "É obrigatório colocar as datas";

$TITULO = "Suporte";
include "menu.php";

include_once '../js/js_css.php'; /* Todas libs js, jquery e css usadas no Assist - HD 969678 */
?>
<script type="text/javascript" charset="utf-8">
	$(function()
	{
		$('#data_de').datepick({startDate:'01/01/2000'});
		$('#data_ate').datepick({startDate:'01/01/2000'});
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
	echo "<td background='/assist/helpdesk/imagem/fundo_tabela_top_centro_azul_claro.gif'   align = 'center' width='100%' style='font-family: arial ; color:#666666' ><b>Relatório Gerencial</b></td>";
	echo "<td background='/assist/helpdesk/imagem/fundo_tabela_top_direito_azul_claro.gif'  ><img src='/assist/imagens/pixel.gif' width='9'></td>";
echo "</tr>";

$data_de2  = @converte_data($data_de);
$data_ate2 = @converte_data($data_ate);

echo "<tr style='font-family: verdana ; font-size:12px; color: #666666'>";
	echo "<td background='/assist/helpdesk/imagem/fundo_tabela_centro_esquerdo.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";

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
	echo "<table><tr style='font-family: verdana ; font-size:12px; color: #666666'><td >Tipo: </td>
			<td>
				Reincidentes<input type='radio' name='tipo' value='reincidente' class='caixa' checked>
				Erros com causador<input type='radio' name='tipo' value='erro' class='caixa'>
		</td>";
	echo "</tr></table>";
	echo "<td background='/assist/helpdesk/imagem/fundo_tabela_centro_direito.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";
echo "</tr>";

echo "<tr>";
	echo "<td background='/assist/helpdesk/imagem/fundo_tabela_centro_esquerdo.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";
	echo "<td nowrap ><br><CENTER><INPUT TYPE=\"submit\" value=\"Pesquisar\" name='pesquisar'></CENTER></td>";
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

	if($tipo =='reincidente'){

		$sql = "SELECT  tbl_hd_chamado.hd_chamado                ,
				to_char(tbl_hd_chamado.data,'DD/MM/YYYY') as data,
				tbl_hd_chamado.titulo                            ,
				tbl_hd_chamado.cobrar                            ,
				tbl_hd_chamado.status                            ,
				tbl_hd_chamado.exigir_resposta                   ,
				tbl_fabrica.nome                AS fabrica_nome  ,
				to_char(tbl_hd_chamado.data_aprovacao,'DD/MM/YYYY hh24:mi') as data_aprovacao,
				tbl_tipo_chamado.descricao as tipo_chamado,
				tbl_hd_chamado.atendente AS atendente_hd,
				tbl_hd_chamado.hd_chamado_anterior
				FROM   tbl_hd_chamado
				JOIN tbl_fabrica ON tbl_hd_chamado.fabrica  = tbl_fabrica.fabrica
				JOIN tbl_tipo_chamado on tbl_hd_chamado.tipo_chamado = tbl_tipo_chamado.tipo_chamado
				WHERE  tbl_hd_chamado.data between '$data_de 00:00:00' and '$data_ate 23:59:59'
				AND    hd_chamado_anterior notnull
				ORDER BY hd_chamado;";
		$res = pg_exec ($con,$sql);

		if (pg_numrows($res) > 0) {

			echo "<table width = '450' align = 'center' cellpadding='2' cellspacing='1' border='0' name='relatorio' id='relatorio' class='tablesorter' >";
			echo "<thead>";
			echo "<tr style='font-size:11px' bgcolor='#D9E8FF'>";
			echo "<td align='center'><b>Chamado</b></td>";
			echo "<td align='center'><b>Chamado Anterior </b></td>";
			echo "<td align='center'><b>Titulo</b></td>";
			echo "<td align='center'><b>Data</b></td>";
			echo "<td align='center'><b>Tipo</b></td>";
			echo "<td align='center'><b>Status</b></td>";
			echo "<td align='center'><b>Fábrica</b></td>";
			echo "</tr>";
			echo "</thead>";
			echo "<tbody>";
			$total_chamados_geral=0;
			$t_total_prazo_horas = 0;
			for ($x = 0 ; $x < pg_numrows($res) ; $x++){
				$hd_chamado      = trim(pg_result($res,$x,hd_chamado));
				$hd_chamado_anterior      = trim(pg_result($res,$x,hd_chamado_anterior));
				$titulo          = trim(pg_result($res,$x,titulo));
				$cobrar          = trim(pg_result($res,$x,cobrar));
				$exigir_resposta = trim(pg_result($res,$x,exigir_resposta));
				$status          = trim(pg_result($res,$x,status));
				$fabrica_nome    = trim(pg_result($res,$x,fabrica_nome));
				$nome_completo   = trim(pg_result($res,$x,nome_completo));
				$data_aprovacao  = trim(pg_result($res,$x,data_aprovacao));
				$data            = trim(pg_result($res,$x,data));
				$tipo_chamado    = trim(pg_result($res,$x,tipo_chamado));
				$atendente       = trim(pg_result($res,$x,atendente));
				$atendente_hd    = trim(pg_result($res,$x,atendente_hd));

				$a="";
				if($exigir_resposta=='t') $a = $imagem;
				if($status=="Novo" or $status =="Análise")$cor_status="#000099";
				if($status=="Execução")                   $cor_status="#FF0000";
				if($status=="Aguard.Execução")            $cor_status="#FF9900";
				if($status=="Resolvido")$cor_status="#009900";

				echo "<tr class='Conteudo' style='background-color: $cor;'>";
				echo "<td align='left' height='15'><a href='adm_chamado_detalhe.php?hd_chamado=$hd_chamado'>$hd_chamado</a></td>";
				echo "<td align='left' height='15'><a href='adm_chamado_detalhe.php?hd_chamado=$hd_chamado_anterior'>$hd_chamado_anterior</a></td>";
				echo "<td align='left'>$titulo</td>";
				echo "<td align='left'>$data</td>";
				echo "<td align='left'>$tipo_chamado</td>";
				echo "<td nowrap'><font color='$cor_status' size='1'><B>$status </B></font></td>";
				echo "<td nowrap'>$fabrica_nome</td>";
				echo "</tr>";
			}
			echo "</tbody>";
			echo "</table>";
		}
	}else{
		$sql = "SELECT  tbl_hd_chamado.hd_chamado                ,
				to_char(tbl_hd_chamado.data,'DD/MM/YYYY') as data,
				tbl_hd_chamado.titulo                            ,
				tbl_hd_chamado.status                            ,
				tbl_fabrica.nome                AS fabrica_nome  ,
				tbl_hd_chamado.atendente AS atendente_hd,
				tbl_hd_chamado.admin_erro,
				tbl_hd_chamado.motivo_erro,
				tbl_hd_chamado.tipo_erro
				FROM   tbl_hd_chamado
				JOIN tbl_fabrica ON tbl_hd_chamado.fabrica  = tbl_fabrica.fabrica
				JOIN tbl_tipo_chamado on tbl_hd_chamado.tipo_chamado = tbl_tipo_chamado.tipo_chamado
				WHERE  tbl_hd_chamado.data between '$data_de 00:00:00' and '$data_ate 23:59:59'
				AND    (admin_erro notnull or motivo_erro notnull or tipo_erro notnull )
				ORDER BY hd_chamado;";
		$res = pg_exec ($con,$sql);

		if (pg_numrows($res) > 0) {

			echo "<table width = '450' align = 'center' cellpadding='2' cellspacing='1' border='0' name='relatorio' id='relatorio' class='tablesorter' >";
			echo "<thead>";
			echo "<tr style='font-size:11px' bgcolor='#D9E8FF'>";
			echo "<td align='center'><b>Chamado</b></td>";
			echo "<td align='center'><b>Chamado Anterior </b></td>";
			echo "<td align='center'><b>Titulo</b></td>";
			echo "<td align='center'><b>Data</b></td>";
			echo "<td align='center'><b>Tipo</b></td>";
			echo "<td align='center'><b>Status</b></td>";
			echo "<td align='center'><b>Fábrica</b></td>";
			echo "</tr>";
			echo "</thead>";
			echo "<tbody>";
			$total_chamados_geral=0;
			$t_total_prazo_horas = 0;
			for ($x = 0 ; $x < pg_numrows($res) ; $x++){
				$hd_chamado      = trim(pg_result($res,$x,hd_chamado));
				$hd_chamado_anterior      = trim(pg_result($res,$x,hd_chamado_anterior));
				$titulo          = trim(pg_result($res,$x,titulo));
				$cobrar          = trim(pg_result($res,$x,cobrar));
				$exigir_resposta = trim(pg_result($res,$x,exigir_resposta));
				$status          = trim(pg_result($res,$x,status));
				$fabrica_nome    = trim(pg_result($res,$x,fabrica_nome));
				$nome_completo   = trim(pg_result($res,$x,nome_completo));
				$data_aprovacao  = trim(pg_result($res,$x,data_aprovacao));
				$data            = trim(pg_result($res,$x,data));
				$tipo_chamado    = trim(pg_result($res,$x,tipo_chamado));
				$atendente       = trim(pg_result($res,$x,atendente));
				$atendente_hd    = trim(pg_result($res,$x,atendente_hd));

				$a="";
				if($exigir_resposta=='t') $a = $imagem;
				if($status=="Novo" or $status =="Análise")$cor_status="#000099";
				if($status=="Execução")                   $cor_status="#FF0000";
				if($status=="Aguard.Execução")            $cor_status="#FF9900";
				if($status=="Resolvido")$cor_status="#009900";

				echo "<tr class='Conteudo' style='background-color: $cor;'>";
				echo "<td align='left' height='15'><a href='adm_chamado_detalhe.php?hd_chamado=$hd_chamado'>$hd_chamado</a></td>";
				echo "<td align='left' height='15'><a href='adm_chamado_detalhe.php?hd_chamado=$hd_chamado_anterior'>$hd_chamado_anterior</a></td>";
				echo "<td align='left'>$titulo</td>";
				echo "<td align='left'>$data</td>";
				echo "<td align='left'>$tipo_chamado</td>";
				echo "<td nowrap'><font color='$cor_status' size='1'><B>$status </B></font></td>";
				echo "<td nowrap'>$fabrica_nome</td>";
				echo "</tr>";
			}
			echo "</tbody>";
			echo "</table>";
		}

	}
}
include "rodape.php";
 ?>
</body>
</html>
