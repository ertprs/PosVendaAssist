<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';
$TITULO = "Relatório de horas utilizadas de fábricas";
include "menu.php";

$sql="SELECT saldo_hora            ,
			 mes                   ,
			 ano                   ,
			 hora_franqueada       ,
			 hora_faturada         ,
			 hora_utilizada        ,
			 valor_hora_franqueada ,
			 to_char(periodo_inicio,'DD/MM/YYYY') as periodo_inicio,
			 to_char(periodo_fim,'DD/MM/YYYY') as periodo_fim,
			 nome,
			 hora_maxima
		from tbl_hd_franquia
		JOIN tbl_fabrica USING(fabrica)
		where periodo_fim IS NULL
		order by fabrica ";

$res=pg_exec($con,$sql);

if(pg_numrows($res) > 0){
	for($i=0;$i<pg_numrows($res);$i++) {
		$saldo_hora            = pg_result($res,$i,'saldo_hora');
		$hora_franqueada       = pg_result($res,$i,'hora_franqueada');
		$hora_faturada         = pg_result($res,$i,'hora_faturada');
		$hora_utilizada        = pg_result($res,$i,'hora_utilizada');
		$valor_hora_franqueada = pg_result($res,$i,'valor_hora_franqueada');
		$valor_hora_franqueada = number_format($valor_hora_franqueada,2,',','.');
		$periodo_inicio        = pg_result($res,$i,'periodo_inicio');
		$periodo_fim           = pg_result($res,$i,'periodo_fim');
		$mes                   = pg_result($res,$i,'mes');
		$ano                   = pg_result($res,$i,'ano');
		$nome                  = pg_result($res,$i,'nome');
		$nome                  = strtoupper($nome);
		$hora_maxima           = pg_result($res,$i,'hora_maxima');

		$valor_faturado = $hora_faturada * $valor_hora_franqueada;

		echo "<table width = '700' align = 'center' class='tabela' cellpadding='0' cellspacing='0' border='0'>";
		echo "<tr>";
		echo "<td background='/assist/helpdesk/imagem/fundo_tabela_top_esquerdo_azul_claro.gif' rowspan='2'><img src='/assist/imagens/pixel.gif' width='9'></td>";
		echo "<td background='/assist/helpdesk/imagem/fundo_tabela_top_centro_azul_claro.gif'   colspan='2' align = 'center' width='100%' style='font-family: arial ; color:#666666'><CENTER>FRANQUIA DE HORAS - $nome</CENTER></td>";
		echo "<td background='/assist/helpdesk/imagem/fundo_tabela_top_direito_azul_claro.gif'  rowspan='2'><img src='/assist/imagens/pixel.gif' width='9'></td>";
		echo "</tr>";
		echo "<tr bgcolor='#D9E8FF' style='font-family: arial ; color: #666666'>";
		echo "<td align='center' colspan='100%'>$mes/$ano Inicio: $periodo_inicio -";
		echo "</td>";
		echo "</tr>";
		echo "<tr style='font-family: arial ; font-size: 17px ; cursor: hand; ' height='25' bgcolor='#F2F7FF'  onmouseover=\"this.bgColor='#D9E8FF'\" onmouseout=\"this.bgColor='#F2F7FF'\" >";
		echo "<td background='/assist/helpdesk/imagem/fundo_tabela_centro_esquerdo.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";
		echo "<td align='center'>";
		echo "Total de franquia de horas deste mês: ";
		echo "</td>";
		echo "<td align='center'> $hora_franqueada</td>";
		echo "<td background='/assist/helpdesk/imagem/fundo_tabela_centro_direito.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";
		echo "</tr>";
		echo "<tr  style='font-family: arial ; font-size: 17px ; cursor: hand; ' height='25' bgcolor='#FFFFFF'  onmouseover=\"this.bgColor='#D9E8FF'\" onmouseout=\"this.bgColor='#FFFFFF'\" >";
		echo "<td background='/assist/helpdesk/imagem/fundo_tabela_centro_esquerdo.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";
		echo "<td align='center'>";
		echo "Saldo de Hora: ";
		echo "</td>";
		echo "<td align='center'> $saldo_hora</td>";
		echo "<td background='/assist/helpdesk/imagem/fundo_tabela_centro_direito.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";
		echo "</tr>";
		echo "<tr  style='font-family: arial ; font-size: 17px ; cursor: hand; ' height='25' bgcolor='#F2F7FF'  onmouseover=\"this.bgColor='#D9E8FF'\" onmouseout=\"this.bgColor='#F2F7FF'\" >";
		echo "<td background='/assist/helpdesk/imagem/fundo_tabela_centro_esquerdo.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";
		echo "<td align='center'>";
		echo "Total de horas utilizadas: ";
		echo "</td>";
		echo "<td align='center'> $hora_utilizada</td>";
		echo "<td background='/assist/helpdesk/imagem/fundo_tabela_centro_direito.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";
		echo "</tr>";

		echo "<tr  style='font-family: arial ; font-size: 17px ; cursor: hand; ' height='25' bgcolor='#FFFFFF'  onmouseover=\"this.bgColor='#D9E8FF'\" onmouseout=\"this.bgColor='#FFFFFF'\" >";
		echo "<td background='/assist/helpdesk/imagem/fundo_tabela_centro_esquerdo.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";
		echo "<td align='center'>";
		echo "Hora Máxima: ";
		echo "</td>";
		echo "<td align='center'> $hora_maxima</td>";
		echo "<td background='/assist/helpdesk/imagem/fundo_tabela_centro_direito.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";
		echo "</tr>";
		echo "<tr  style='font-family: arial ; font-size: 17px ; cursor: hand; ' height='25' bgcolor='#F2F7FF'  onmouseover=\"this.bgColor='#D9E8FF'\" onmouseout=\"this.bgColor='#F2F7FF'\" >";
		echo "<td background='/assist/helpdesk/imagem/fundo_tabela_centro_esquerdo.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";
		echo "<td align='center'>";
		echo "Valor Hora: ";
		echo "</td>";
		echo "<td align='center'> $valor_hora_franqueada</td>";
		echo "<td background='/assist/helpdesk/imagem/fundo_tabela_centro_direito.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";
		echo "</tr>";
		
		echo "<tr  style='font-family: arial ; font-size: 17px ; cursor: hand; ' height='25' bgcolor='#FFFFFF'  onmouseover=\"this.bgColor='#D9E8FF'\" onmouseout=\"this.bgColor='#FFFFFF'\" >";
		echo "<td background='/assist/helpdesk/imagem/fundo_tabela_centro_esquerdo.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";
		echo "<td align='center'><font color='red'>";
		echo "A fabrica pode liberar este mês, sem cobrar, o total de : ";
		echo "</td>";
		$horas_que_ainda_podem_aprovar = $hora_franqueada + $saldo_hora - $hora_utilizada;
		echo "<td align='center'> $horas_que_ainda_podem_aprovar hora(s)</td>";
		echo "<td background='/assist/helpdesk/imagem/fundo_tabela_centro_direito.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";
		echo "</tr>";

		echo "<tr  style='font-family: arial ; font-size: 17px ; cursor: hand; ' height='25' bgcolor='#F2F7FF'  onmouseover=\"this.bgColor='#D9E8FF'\" onmouseout=\"this.bgColor='#F2F7FF'\" >";
		echo "<td background='/assist/helpdesk/imagem/fundo_tabela_centro_esquerdo.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";
		echo "<td align='center'>";
		echo "Hora faturada: ";
		echo "</td>";
		echo "<td align='center'> $hora_faturada</td>";
		echo "<td background='/assist/helpdesk/imagem/fundo_tabela_centro_direito.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";
		echo "</tr>";

		echo "<tr  style='font-family: arial ; font-size: 17px ; cursor: hand; ' height='25' bgcolor='#FFFFFF'  onmouseover=\"this.bgColor='#D9E8FF'\" onmouseout=\"this.bgColor='#FFFFFF'\" >";
		echo "<td background='/assist/helpdesk/imagem/fundo_tabela_centro_esquerdo.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";
		echo "<td align='center'>";
		echo "Valor faturado:";
		echo "</td>";
		echo "<td align='center'> $valor_faturado</td>";
		echo "<td background='/assist/helpdesk/imagem/fundo_tabela_centro_direito.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";
		echo "</tr>";
		echo "<tr>";
		echo "<td background='/assist/helpdesk/imagem/fundo_tabela_baixo_esquerdo.gif'><img src='/assist/imagens/pixel.gif' width='9'></td>";
		echo "<td background='/assist/helpdesk/imagem/fundo_tabela_baixo_centro.gif' colspan='2' align = 'center' width='100%'></td>";
		echo "<td background='/assist/helpdesk/imagem/fundo_tabela_baixo_direito.gif'><img src='/assist/imagens/pixel.gif' width='9'></td>";
		echo "</tr>";
		echo "</table>";
		echo "<br />";
	}
}

include "rodape.php";