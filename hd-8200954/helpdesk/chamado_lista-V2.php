<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';
if($sistema_lingua == "ES") $TITULO = "Lista de llamados - Telecontrol Help-Desk";
else                        $TITULO = "Lista de Chamadas - Telecontrol Hekp-Desk";

include "menu.php";

if($_POST['hd_chamado']) $hd_chamado = trim ($_POST['hd_chamado']);
if($_GET ['hd_chamado']) $hd_chamado = trim ($_GET ['hd_chamado']);

$titulo = $_POST['titulo'];
if (strlen($titulo) == 0)  $titulo = $_GET['titulo'];

?>

<table width="700" align="center"><tr><td style='font-family: arial ; color: #666666; font-size:10px' align="justify">
<?
$sql = "SELECT  tbl_admin.nome_completo      AS admin_nome   ,
				tbl_hd_chamado.hd_chamado          ,
				tbl_hd_chamado.admin               ,
				to_char (tbl_hd_chamado.data,'DD/MM HH24:MI') AS data,
				tbl_hd_chamado.titulo              ,
				tbl_hd_chamado.status              ,
				tbl_hd_chamado.atendente           ,
				TO_CHAR(tbl_hd_chamado.resolvido,'dd/mm/YYYY') AS resolvido    ,
				tbl_hd_chamado.exigir_resposta     ,
				to_char(tbl_hd_chamado.previsao_termino,'DD/MM/YYYY HH24:MI')as  previsao_termino,
				at.nome_completo AS atendente_nome ,
				CASE 
				WHEN tbl_hd_chamado.exigir_resposta THEN 0 
				WHEN (tbl_hd_chamado.status = 'Resolvido'                  
					AND tbl_hd_chamado.resolvido is null)                 THEN 1
				WHEN tbl_hd_chamado.status = 'Aprovação'                  THEN 2
				WHEN tbl_hd_chamado.status = 'Cancelado'                  THEN 10 

				WHEN tbl_hd_chamado.resolvido is not null 
					AND tbl_hd_chamado.status = 'Resolvido'           THEN 9
				ELSE 5 
				END AS classificacao ,
				(SELECT admin FROM tbl_hd_chamado_item WHERE hd_chamado = tbl_hd_chamado.hd_chamado ORDER BY data DESC LIMIT 1) AS admin_item ,
				(SELECT tbl_admin.nome_completo FROM tbl_hd_chamado_item JOIN tbl_admin USING (admin) WHERE hd_chamado = tbl_hd_chamado.hd_chamado ORDER BY data DESC LIMIT 1) AS nome_item ,
				(SELECT data  FROM tbl_hd_chamado_item WHERE hd_chamado = tbl_hd_chamado.hd_chamado ORDER BY data DESC LIMIT 1) AS data_item
		FROM       tbl_hd_chamado
		JOIN       tbl_admin ON tbl_admin.admin = tbl_hd_chamado.admin AND tbl_admin.fabrica=$login_fabrica";
		if($titulo <> '') $sql .= " JOIN        tbl_hd_chamado_item using(hd_chamado) ";
		$sql .= "LEFT JOIN  tbl_admin at ON (tbl_hd_chamado.atendente = at.admin AND at.fabrica=$login_fabrica)
		WHERE      tbl_hd_chamado.fabrica=$login_fabrica ";
		if ($login_fabrica<>3){
			$sql .= " AND tbl_hd_chamado.admin = $login_admin ";
		}
		$sql .= " AND tbl_hd_chamado.fabrica_responsavel <> $login_fabrica ";
		if($titulo <> '') $sql .= " AND  tbl_hd_chamado_item.comentario LIKE '%$titulo%'";
		else $sql .= " ORDER BY classificacao, tbl_hd_chamado.data ";
//		echo $sql;

$res = pg_exec ($con,$sql);
if (pg_numrows($res) > 0) {

/*--=========================LEGENDA DE CORES=======================================-*/
	if($titulo){ echo "<center><font size='3' color='333333'>";
		if($sistema_lingua == "ES") echo "Usted estás buscando: ";
		else                        echo "Você está procurando por: ";
		echo "<b>$titulo</b></font></center>";
	}
	echo "<table width='630' align='center' cellpadding='0' cellspacing='0' border='0'>";
	echo "<tr style='font-family: arial ; color: #666666' align='center'>";

	echo "<td width='50%' nowrap align='left'>";
	echo "<img src='/assist/admin/imagens_admin/status_vermelho.gif' valign='absmiddle'> ";
	if($sistema_lingua == 'ES') echo "Aguardando su respuesta";
	else                        echo "Aguardando sua resposta";
	echo "</td>";

	echo "<td width='50%' nowrap align='left'>";
	echo "<img src='/assist/admin/imagens_admin/status_azul.gif' valign='absmiddle'> ";
	if($sistema_lingua == 'ES') echo "Pendiente";
	else                        echo "Pendente Telecontrol";
	echo "</td>";

	echo "</tr>";

	
	echo "<tr style='font-family: arial ; color: #666666' align='center'>";

	echo "<td width='50%' nowrap align='left'>";
	echo "<img src='/assist/admin/imagens_admin/status_amarelo.gif' valign='absmiddle'> ";
	if($sistema_lingua == 'ES') echo "Aguardando aprobación";
	else                        echo "Aguardando aprovação";
	echo "</td>";

	echo "<td width='50%' nowrap align='left'>";
	echo "<img src='/assist/admin/imagens_admin/status_verde.gif' valign='absmiddle'> ";
	if($sistema_lingua == 'ES') echo "Resolvido";
	else                        echo "Resolvido";
	echo "</td>";


	echo "</tr>";
	
	echo "</table>";
	echo "<br>";


/*--===============================TABELA DE CHAMADOS========================--*/
	echo "<table width = '630' align = 'center' cellpadding='0' cellspacing='0'>";
	echo "<tr>";
	echo "<td background='/assist/helpdesk/imagem/fundo_tabela_top_esquerdo_azul_claro.gif' rowspan='2'><img src='/assist/imagens/pixel.gif' width='9'></td>";
	echo "<td background='/assist/helpdesk/imagem/fundo_tabela_top_centro_azul_claro.gif' colspan='7' align = 'center' width='100%' style='font-family: arial ; color:#666666'><b>";
	if($sistema_lingua == "ES") echo "Lista de llamados";
	else                        echo "Lista de Chamados";
	echo "</b></td>";
	echo "<td background='/assist/helpdesk/imagem/fundo_tabela_top_direito_azul_claro.gif' rowspan='2'><img src='/assist/imagens/pixel.gif' width='9'></td>";
	echo "</tr>";
	echo "<tr bgcolor='#D9E8FF' style='font-family: arial ; color: #666666'>";
	echo "<td ><strong>Nº </strong></td>";
	echo "<td nowrap><strong>Título</strong><img src='/assist/imagens/pixel.gif' width='5'></td>";
	echo "<td ><strong>Status</strong></td>";
	echo "<td ><strong>";
	if($sistema_lingua == "ES") echo "Fecha";
	else                        echo "Data";
	echo "</strong></td>";
	echo "<td ><strong>";
	if($sistema_lingua == "ES") echo "Solicitante";
	else                        echo "Solicitante";
	echo "</strong></td>";
	echo "<td nowrap><strong>";
	if($sistema_lingua == "ES") echo "&nbsp;Prev. Término";
	else                        echo "&nbsp;Prev. Término";
	echo "</strong></td>";
	echo "<td ><strong>";
	if($sistema_lingua == "ES") echo "&nbsp;Resolvido";
	else                        echo "&nbsp;Resolvido";
	echo "</strong></td>";

//	echo "<td nowrap><img src='/assist/imagens/pixel.gif' width='5'><strong>Último Autor </strong></td>";
	echo "</tr>";

	for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
		$hd_chamado           = pg_result($res,$i,hd_chamado);
		$admin                = pg_result($res,$i,admin);
		$admin_nome           = pg_result($res,$i,admin_nome);
		$data                 = pg_result($res,$i,data);
		$titulo               = pg_result($res,$i,titulo);
		$resolvido            = pg_result($res,$i,resolvido);
		$status               = pg_result($res,$i,status);
		$atendente            = pg_result($res,$i,atendente);
		$atendente_nome       = pg_result($res,$i,atendente_nome);
		$admin_item           = pg_result($res,$i,admin_item);
		$nome_item            = pg_result($res,$i,nome_item);
		$exigir_resposta      = pg_result($res,$i,exigir_resposta);
		$previsao_termino     = pg_result($res,$i,previsao_termino);
		


		$cor='#F2F7FF';
		if ($i % 2 == 0) $cor = '#FFFFFF';

		echo "<tr  style='font-family: arial ; font-size: 12px ; cursor: hand; ' height='25' bgcolor='$cor'  onmouseover=\"this.bgColor='#D9E8FF'\" onmouseout=\"this.bgColor='$cor'\" ><a href='chamado_detalhe.php?hd_chamado=$hd_chamado'>";
		echo "<td background='/assist/helpdesk/imagem/fundo_tabela_centro_esquerdo.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";
		echo "<td nowrap>";
		if($status =="Análise" AND $exigir_resposta <> "t"){
			echo "<img src='/assist/admin/imagens_admin/status_azul.gif' align='absmiddle'> ";
		}elseif($exigir_resposta == "t" AND $status<>'Cancelado' AND $status<>'Resolvido' OR ($status == "Resolvido" AND strlen($resolvido)==0 )) {
			echo "<img src='/assist/admin/imagens_admin/status_vermelho.gif' align='absmiddle'> ";
		}elseif (($status == "Resolvido" AND strlen($resolvido)>0) OR $status == "Cancelado") {
				echo "<img src='/assist/admin/imagens_admin/status_verde.gif' align='absmiddle'> ";
			}elseif ($status == "Aprovação") {
				echo "<img src='/assist/admin/imagens_admin/status_amarelo.gif' align='absmiddle'> ";
			}else{
				echo "<img src='/assist/admin/imagens_admin/status_azul.gif' align='absmiddle'> ";
			}


		echo $hd_chamado;
		echo "</td>";
		echo "<td nowrap><a href='chamado_detalhe.php?hd_chamado=$hd_chamado'>$titulo</a></td>";
		echo "<td>$status</td>";
		echo "<td nowrap>$data</td>";
		echo "<td nowrap>$admin_nome</td>";
		echo "<td nowrap>$previsao_termino </td>";
		echo "<td nowrap>$resolvido</td>";
		echo "<td background='/assist/helpdesk/imagem/fundo_tabela_centro_direito.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";
		echo "</a></tr>"; 
	}

	echo "<tr>";
	echo "<td background='/assist/helpdesk/imagem/fundo_tabela_baixo_esquerdo.gif'><img src='/assist/imagens/pixel.gif' width='9'></td>";
	echo "<td background='/assist/helpdesk/imagem/fundo_tabela_baixo_centro.gif' colspan='7' align = 'center' width='100%'></td>";
	echo "<td background='/assist/helpdesk/imagem/fundo_tabela_baixo_direito.gif'><img src='/assist/imagens/pixel.gif' width='9'></td>";
	echo "</tr>";
	echo "</table>"; //fim da tabela de chamadas
}else{
	if($sistema_lingua == "ES") echo "<center><h3>NINGÚN LLAMADO ABIERTO</h3></center>";
	else                        echo "<center><h3>NENHUM CHAMADO ABERTO</h3></center>";
}
?>

</td>
</tr>
</table>
<? include "rodape.php" ?>
