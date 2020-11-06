<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';

if($login_fabrica<>10){
	header ("Location: index.php");
}

$TITULO = "ADM - Lista de Chamadas";

include "menu.php";
?>

<meta http-equiv="refresh" content="300">

<?

###busca###

$sql = "SELECT 
			hd_chamado              ,
			tbl_hd_chamado.admin    ,
			tbl_admin.nome_completo ,
			tbl_admin.login         ,
			to_char (tbl_hd_chamado.data,'DD/MM HH24:MI') AS data,
			titulo                  ,
			status                  ,
			atendente               ,
			exigir_resposta         ,
			tbl_fabrica.nome AS fabrica_nome
		FROM tbl_hd_chamado
		JOIN tbl_admin   ON tbl_hd_chamado.admin    = tbl_admin.admin
		JOIN tbl_fabrica ON tbl_hd_chamado.fabrica  = tbl_fabrica.fabrica
		WHERE atendente = $login_admin
		AND status NOT IN ('Resolvido','Cancelado')";

$sql .= " ORDER BY tbl_hd_chamado.data ";

//echo nl2br($sql);
$res = pg_exec ($con,$sql);

if (@pg_numrows($res) >= 0) {


/*--=========================LEGENDA DE CORES=======================================-*/

	echo "<!-- ";
	echo "<br>";
	echo "<table  align='center' cellpadding='0' cellspacing='0' border='0' bordercolor='DDDDDD'>";
	echo "<tr align = 'center' >";
	echo "<td width='30' bgcolor='eeeeee'></td>";
	echo "<td width='2'></td>";
	echo "<td >Em Dia</td>";
	echo "<td width='10'></td>";
	echo "<td width='30' bgcolor='FF3300'></td>";
	echo "<td width='2'></td>";
	echo "<td> Atrasado</td>";
	echo "<td width='10'></td>";
	echo "<td width='30' bgcolor='00CCFF'></td>";
	echo "<td width='2'></td>";
	echo "<td> Interno Em Dia</td>";
	echo "<td width='10'></td>";
	echo "<td width='30' bgcolor='FF6600'></td>";
	echo "<td width='2'></td>";
	echo "<td> Interno Atrasado</td>";
	echo "</tr>";
	echo "</table>";//fim da tabela de legenda
	echo " -->";

/*================================TABELA DE ESCOLHA DE STATUS============================*/


	
	/*--===============================TABELA DE CHAMADOS========================--*/

	$sqlmeuchamado =	"SELECT count(*) AS total_meuchamado
						FROM tbl_hd_chamado
						WHERE (status NOT ILIKE 'Resolvido' 
						AND status NOT ILIKE 'Cancelado' 
						AND status NOT ILIKE 'Aprovação' 
						OR status IS NULL) 
						AND atendente = $login_admin";
	$resmeuchamado     = pg_exec ($con,$sqlmeuchamado);
	$xtotal_meuchamado = pg_result($resmeuchamado,0,total_meuchamado);


		
	if (@pg_numrows($res) > 0) {
		echo "<table width = '700' align = 'center' cellpadding='0' cellspacing='0' border='0'>";
		echo "<tr>";
		echo "	<td background='/assist/helpdesk/imagem/fundo_tabela_top_esquerdo_azul_claro.gif' rowspan='2'><img src='/assist/imagens/pixel.gif' width='9'></td>";
		echo "	<td background='/assist/helpdesk/imagem/fundo_tabela_top_centro_azul_claro.gif'   colspan='7' align = 'center' width='100%' style='font-family: arial ; color:#666666'><b>Lista de Chamados</b></td>";
		echo "	<td background='/assist/helpdesk/imagem/fundo_tabela_top_direito_azul_claro.gif'  rowspan='2'><img src='/assist/imagens/pixel.gif' width='9'></td>";
		echo "</tr>";
		echo "<tr bgcolor='#D9E8FF' style='font-family: arial ; color: #666666'>";
		echo "	<td ><strong>Nº </strong></td>";
		echo "	<td nowrap><strong>Título</strong><img src='/assist/imagens/pixel.gif' width='5'></td>";
		echo "	<td ><strong>Status</strong></td>";
		echo "	<td ><strong>Data</strong></td>";
		echo "	<td nowrap><img src='/assist/imagens/pixel.gif' width='5'><strong>Autor </strong></td>";
		echo "	<td nowrap><img src='/assist/imagens/pixel.gif' width='5'><strong>Fábrica </strong></td>";
		echo "	<td nowrap><img src='/assist/imagens/pixel.gif' width='5'><strong>Atendente </strong></td>";
		echo "</tr>";	
			
	//inicio imprime chamados
		for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
			$hd_chamado           = pg_result($res,$i,hd_chamado);
			$admin                = pg_result($res,$i,admin);
			$login                = pg_result($res,$i,login);
	//		$posto                = pg_result($res,$i,posto);
			$data                 = pg_result($res,$i,data);
			$titulo               = pg_result($res,$i,titulo);
			$status               = pg_result($res,$i,status);
			$atendente            = pg_result($res,$i,atendente);
			$exigir_resposta      = pg_result($res,$i,exigir_resposta);
			$nome_completo        = trim(pg_result($res,$i,nome_completo));
			$fabrica_nome         = trim(pg_result($res,$i,fabrica_nome));
			
		
			$sql2 = "SELECT nome_completo, admin
				FROM	tbl_admin
				WHERE	admin='$atendente'";

			$res2 = pg_exec ($con,$sql2);	
			$xatendente            = pg_result($res2,0,nome_completo);
			$xxatendente = explode(" ", $xatendente);
			
			$cor='#F2F7FF';
			if ($i % 2 == 0) $cor = '#FFFFFF';

		$interno='';
		for($r = 0 ; $r < count($chamado_interno); $r++){
			if($hd_chamado == $chamado_interno[$r])$interno="<img src='../admin/imagens_admin/star_on.gif' title='Contém chamado interno' border='0'>";
		}


			echo "<tr  style='font-family: arial ; font-size: 12px ; cursor: hand; ' height='25' bgcolor='$cor'  onmouseover=\"this.bgColor='#D9E8FF'\" onmouseout=\"this.bgColor='$cor'\" >";
			if($status == "Novo") {echo "<a href='adm_atendimento.php?hd_chamado=$hd_chamado'>";}
			else echo "<a href='adm_chamado_detalhe.php?hd_chamado=$hd_chamado'>";
			echo "<td background='/assist/helpdesk/imagem/fundo_tabela_centro_esquerdo.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";


			echo "<td>";
			if($status =="Análise" AND $exigir_resposta <> "t"){
				echo "<img src='/assist/admin/imagens_admin/status_azul.gif' align='absmiddle'> ";
			}elseif($exigir_resposta == "t" AND $status<>'Cancelado'OR ($status == "Resolvido" AND strlen($resolvido)==0 )) {
				echo "<img src='/assist/admin/imagens_admin/status_vermelho.gif' align='absmiddle' alt='Aguardando resposta do cliente'> ";
			}elseif (($status == "Resolvido" AND strlen($resolvido)>0) OR $status == "Cancelado") {
					echo "<img src='/assist/admin/imagens_admin/status_verde.gif' align='absmiddle'> ";
				}elseif ($status == "Aprovação") {
					echo "<img src='/assist/admin/imagens_admin/status_amarelo.gif' align='absmiddle'> ";
				}else{
					echo "<img src='/assist/admin/imagens_admin/status_azul.gif' align='absmiddle'> ";
				}
			echo " $hd_chamado&nbsp;</td>";

			echo "<td><acronym title='$titulo'>";
			if($status == "Novo") echo "<a href='adm_atendimento.php?hd_chamado=$hd_chamado'>";
			else                  echo "<a href='adm_chamado_detalhe.php?hd_chamado=$hd_chamado'>";
			echo"$interno ".substr($titulo,0,20)."...</a></acronym></td>";
			if (($status != 'Resolvido') and ($status != 'Cancelado')) {
				echo "<td nowrap><font color=#FF0000><B>$status </B></font></td>";
			}else{
				echo "<td nowrap>$status </td>";
			}
			echo "<td nowrap><font size='1'>&nbsp;$data &nbsp;</font></td>";
			echo "<td><font size='1'>";
			if (strlen ($nome_completo) > 0) {
				echo $nome_completo;
				
			}else{
				echo $login;
			}
			echo "</font></td>";
			echo "<td><font size='1'>&nbsp;$fabrica_nome&nbsp;</font></td>";
			echo "<td><font size='1'>&nbsp;$xxatendente[0]&nbsp;</font></td>";
			
			echo "<td background='/assist/helpdesk/imagem/fundo_tabela_centro_direito.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";
			echo "</a></tr>"; 
			
		}
		
	//fim imprime chamados
		
		echo "<tr>";
		echo "<td background='/assist/helpdesk/imagem/fundo_tabela_baixo_esquerdo.gif'><img src='/assist/imagens/pixel.gif' width='9'></td>";
		echo "<td background='/assist/helpdesk/imagem/fundo_tabela_baixo_centro.gif' colspan='7' align = 'center' width='100%'></td>";
		echo "<td background='/assist/helpdesk/imagem/fundo_tabela_baixo_direito.gif'><img src='/assist/imagens/pixel.gif' width='9'></td>";
		echo "</tr>";

		echo "</table>"; 
		
	}else echo "<center><font color='#006600' size='3'><b>VOCÊ NÃO TEM NENHUM CHAMADO PENDENTE!</b></font></center>";
	
	echo "</td>";
	echo "</tr>";

	echo "</table>";
	
}
?>

<? include "rodape.php" ?>

