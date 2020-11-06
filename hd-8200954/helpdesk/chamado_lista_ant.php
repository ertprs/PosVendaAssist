<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';

$TITULO = "Lista de Chamadas - Telecontrol Hekp-Desk";

include "menu.php";
?>

<?
$sql = "SELECT  tbl_admin.nome_completo      AS admin_nome   ,
				tbl_hd_chamado.hd_chamado          ,
				tbl_hd_chamado.admin               ,
				to_char (tbl_hd_chamado.data,'DD/MM HH24:MI') AS data,
				tbl_hd_chamado.titulo              ,
				tbl_hd_chamado.status              ,
				tbl_hd_chamado.atendente           ,
				at.nome_completo AS atendente_nome ,
				(SELECT admin FROM tbl_hd_chamado_item WHERE hd_chamado = tbl_hd_chamado.hd_chamado ORDER BY data DESC LIMIT 1) AS admin_item ,
				(SELECT tbl_admin.nome_completo FROM tbl_hd_chamado_item JOIN tbl_admin USING (admin) WHERE hd_chamado = tbl_hd_chamado.hd_chamado ORDER BY data DESC LIMIT 1) AS nome_item ,
				(SELECT data  FROM tbl_hd_chamado_item WHERE hd_chamado = tbl_hd_chamado.hd_chamado ORDER BY data DESC LIMIT 1) AS data_item
		FROM tbl_hd_chamado
		JOIN tbl_admin ON tbl_admin.admin = tbl_hd_chamado.admin
		LEFT JOIN tbl_admin at ON tbl_hd_chamado.atendente = at.admin
		WHERE   tbl_hd_chamado.admin = $login_admin
		ORDER BY data_item DESC";

$res = @pg_exec ($con,$sql);
if (@pg_numrows($res) > 0) {

/*--=========================LEGENDA DE CORES=======================================-*/

	echo "<br>";
	echo "<table width='500' align='center' cellpadding='0' cellspacing='0' border='0'>";
	echo "<tr style='font-family: arial ; color: #666666' align='center'>";

	echo "<td width='33%' nowrap>";
	echo "<img src='/assist/admin/imagens_admin/status_vermelho.gif' valign='absmiddle'> Aguardando sua resposta";
	echo "</td>";

	echo "<td width='33%' nowrap>";
	echo "<img src='/assist/admin/imagens_admin/status_amarelo.gif' valign='absmiddle'> Pendente";
	echo "</td>";

	echo "<td width='33%' nowrap>";
	echo "<img src='/assist/admin/imagens_admin/status_verde.gif' valign='absmiddle'> Resolvido";
	echo "</td>";

	echo "</tr>";
	echo "</table>";
	echo "<br>";


/*--===============================TABELA DE CHAMADOS========================--*/
	echo "<table width = '500' align = 'center' cellpadding='0' cellspacing='0'>";
	echo "<tr>";
	echo "<td background='/assist/helpdesk/imagem/fundo_tabela_top_esquerdo_azul_claro.gif' rowspan='2'><img src='/assist/imagens/pixel.gif' width='9'></td>";
	echo "<td background='/assist/helpdesk/imagem/fundo_tabela_top_centro_azul_claro.gif' colspan='5' align = 'center' width='100%' style='font-family: arial ; color:#666666'><b>Lista de Chamados</b></td>";
	echo "<td background='/assist/helpdesk/imagem/fundo_tabela_top_direito_azul_claro.gif' rowspan='2'><img src='/assist/imagens/pixel.gif' width='9'></td>";
	echo "</tr>";
	echo "<tr bgcolor='#D9E8FF' style='font-family: arial ; color: #666666'>";
	echo "<td ><strong>Nº </strong></td>";
	echo "<td nowrap><strong>Título</strong><img src='/assist/imagens/pixel.gif' width='5'></td>";
	echo "<td ><strong>Status</strong></td>";
	echo "<td ><strong>Data</strong></td>";
	echo "<td nowrap><img src='/assist/imagens/pixel.gif' width='5'><strong>Último Autor </strong></td>";
	echo "</tr>";

	for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
		$hd_chamado           = pg_result($res,$i,hd_chamado);
		$admin                = pg_result($res,$i,admin);
		$admin_nome           = pg_result($res,$i,admin_nome);
		$data                 = pg_result($res,$i,data);
		$titulo               = pg_result($res,$i,titulo);
		$status               = pg_result($res,$i,status);
		$atendente            = pg_result($res,$i,atendente);
		$atendente_nome       = pg_result($res,$i,atendente_nome);
		$admin_item           = pg_result($res,$i,admin_item);
		$nome_item            = pg_result($res,$i,nome_item);

		$cor='#F2F7FF';
		if ($i % 2 == 0) $cor = '#FFFFFF';

		echo "<tr  style='font-family: arial ; font-size: 12px ; cursor: hand; ' height='25' bgcolor='$cor'  onmouseover=\"this.bgColor='#D9E8FF'\" onmouseout=\"this.bgColor='$cor'\" ><a href='chamado_detalhe.php?hd_chamado=$hd_chamado'>";
		echo "<td background='/assist/helpdesk/imagem/fundo_tabela_centro_esquerdo.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";
		echo "<td nowrap>";
		if ($admin_item <> $login_admin) {
			echo "<img src='/assist/admin/imagens_admin/status_vermelho.gif' align='absmiddle'> ";
		}else{
			if ($status == "Resolvido") {
				echo "<img src='/assist/admin/imagens_admin/status_verde.gif' align='absmiddle'> ";
			}else{
				echo "<img src='/assist/admin/imagens_admin/status_amarelo.gif' align='absmiddle'> ";
			}
		}
		echo $hd_chamado;
		echo "</td>";
		echo "<td><a href='chamado_detalhe.php?hd_chamado=$hd_chamado'>$titulo</a></td>";
		echo "<td>$status</td>";
		echo "<td>$data</td>";
		echo "<td nowrap>$nome_item</td>";
		echo "<td background='/assist/helpdesk/imagem/fundo_tabela_centro_direito.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";
		echo "</a></tr>"; 
	}

	echo "<tr>";
	echo "<td background='/assist/helpdesk/imagem/fundo_tabela_baixo_esquerdo.gif'><img src='/assist/imagens/pixel.gif' width='9'></td>";
	echo "<td background='/assist/helpdesk/imagem/fundo_tabela_baixo_centro.gif' colspan='5' align = 'center' width='100%'></td>";
	echo "<td background='/assist/helpdesk/imagem/fundo_tabela_baixo_direito.gif'><img src='/assist/imagens/pixel.gif' width='9'></td>";
	echo "</tr>";
	echo "</table>"; //fim da tabela de chamadas
}else{
	echo "<center><h3>NENHUM CHAMADO ABERTO</h3></center>";
}
?>


<? include "rodape.php" ?>