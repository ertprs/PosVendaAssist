<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';

if($login_fabrica<>10) header ("Location: index.php");

function converte_data($date)
{
	$date = explode("-", preg_replace('/\//', '-', $date));
	$date2 = ''.$date[2].'/'.$date[1].'/'.$date[0];
	if (sizeof($date)==3)
		return $date2;
	else return false;
}

$TITULO = "Relatórios Atendimentos";

include "menu.php";

$atendente 	= trim($_GET['atendente_busca']);
$data_de		= @converte_data(trim($_GET['data_de']));
$data_ate 		= @converte_data(trim($_GET['data_ate']));
$sql="";

if (strlen($atendente)>0 OR (strlen($data_de)>0 AND strlen($data_ate)>0)){
	if (strlen($atendente)>0)
		$sql_extra="  tbl_hd_chamado_item.admin = $atendente AND ";
	if (strlen($data_de)>0){
		$sql = " SELECT DISTINCT
					tbl_hd_chamado.hd_chamado AS hd_chamado,
					to_char (tbl_hd_chamado.data,'YYYY-MM-DD HH24:MI') AS data,
					to_char (tbl_hd_chamado.resolvido,'DD/MM/YYYY HH24:MI') AS resolvido,
					tbl_hd_chamado.status AS status,
					tbl_hd_chamado.titulo AS titulo,
					tbl_hd_chamado.duracao AS duracao,
					tbl_hd_chamado.categoria AS categoria,
					tbl_hd_chamado.admin AS admin,
					tbl_hd_chamado.atendente AS atendente,
					tbl_fabrica.nome AS fabrica_nome,
					tbl_hd_chamado.exigir_resposta AS exigir_resposta,
					tbl_admin.login AS nome_admin
				FROM tbl_hd_chamado
				JOIN tbl_hd_chamado_item USING(hd_chamado)
				JOIN tbl_admin ON tbl_admin.admin = tbl_hd_chamado.admin
				JOIN tbl_fabrica ON tbl_admin.fabrica  = tbl_fabrica.fabrica
				WHERE $sql_extra
					tbl_hd_chamado_item.data BETWEEN '$data_de 00:00:01' AND '$data_ate 23:59:59'
				";
		$res_result = pg_exec ($con,$sql);
	} // entra qui no caso do user soh clicar em pesquisar mas sem selecionar nada
	else{
		$msg_erro=' ';
	}
}
/*================================TABELA DE ESCOLHA DE STATUS============================*/


	echo "<form name='filtrar' method='GET' ACTION='$PHP_SELF'>";
	echo "<table width = '700' align = 'center' cellpadding='0' cellspacing='0' border='0'>";
	echo "<tr>";
	echo "	<td background='/assist/helpdesk/imagem/fundo_tabela_top_esquerdo_azul_claro.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";
	echo "	<td background='/assist/helpdesk/imagem/fundo_tabela_top_centro_azul_claro.gif'  colspan='2' align = 'center' width='100%' style='font-family: arial ; color:#666666'>&nbsp;<b>Relatório de Posição de Atendimento</b></td>";
	echo "	<td background='/assist/helpdesk/imagem/fundo_tabela_top_direito_azul_claro.gif'  ><img src='/assist/imagens/pixel.gif' width='9'></td>";
	echo "</tr>";

	echo "<tr style='font-family: verdana ; font-size:12px; color: #666666'>";
	echo "	<td background='/assist/helpdesk/imagem/fundo_tabela_centro_esquerdo.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";
	echo "	<td td colspan = '2' align='center'> <br>Atendente: ";

		$sqlatendente = "SELECT  nome_completo,
					admin
				FROM    tbl_admin
				WHERE   tbl_admin.fabrica = 10
				ORDER BY tbl_admin.nome_completo;";

		$resatendente = pg_exec ($con,$sqlatendente);

		$atendente_busca = $_POST['atendente_busca'];

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

			echo "</select><BR>";
		}

	echo "<br></td>";



	echo "	<td background='/assist/helpdesk/imagem/fundo_tabela_centro_direito.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";
	echo "</tr>";

	echo "<tr bgcolor='#D9E8FF' style='font-family: arial ; color: #666666'>";
	echo "	<td background='/assist/helpdesk/imagem/fundo_tabela_centro_esquerdo.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";
	echo "	<td align='center' colspan='2'><b>Período</b></td>";
	echo "	<td background='/assist/helpdesk/imagem/fundo_tabela_centro_direito.gif' align='rigth'><img src='/assist/imagens/pixel.gif' width='9'></td>";
	echo "</tr>";

	$data_de2		= @converte_data($data_de);
	$data_ate2 		= @converte_data($data_ate);

	echo "<tr style='font-family: verdana ; font-size:12px; color: #666666'>";
	echo "	<td background='/assist/helpdesk/imagem/fundo_tabela_centro_esquerdo.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";
	echo "	<td align='right'><br>De: <input type='text' size='15' maxlength='10' name='data_de' value='$data_de2' class='caixa'> </td>";
	echo "	<td align='left'><br> Até:: <input type='text' size='15' maxlength='10' name='data_ate' value='$data_ate2' class='caixa'></td>";
	echo "	<td background='/assist/helpdesk/imagem/fundo_tabela_centro_direito.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";
	echo "</td>";


#botao submit

	echo "<tr>";
	echo "	<td background='/assist/helpdesk/imagem/fundo_tabela_centro_esquerdo.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";
	echo "<td nowrap colspan='2'><br><CENTER><INPUT TYPE=\"submit\" value=\"Pesquisar\"></CENTER></td>";
//	echo "	<td></td>";
	echo "	<td background='/assist/helpdesk/imagem/fundo_tabela_centro_direito.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";
	echo "</tr>";

#===========================

	echo "<tr>";
	echo "	<td background='/assist/helpdesk/imagem/fundo_tabela_baixo_esquerdo.gif'><img src='/assist/imagens/pixel.gif' width='9'></td>";
	echo "	<td background='/assist/helpdesk/imagem/fundo_tabela_baixo_centro.gif' colspan='2' align = 'center' width='100%'></td>";
	echo "	<td background='/assist/helpdesk/imagem/fundo_tabela_baixo_direito.gif'><img src='/assist/imagens/pixel.gif' width='9'></td>";
	echo "</tr>";
	echo "</table>";
	echo "</FORM>";


if (@pg_numrows($res_result) > 0){


	echo "<table width = '760' align = 'center' cellpadding='0' cellspacing='0' border='0'>";
	echo "<tr>";
	echo "	<td background='/assist/helpdesk/imagem/fundo_tabela_top_esquerdo_azul_claro.gif' rowspan='2'><img src='/assist/imagens/pixel.gif' width='9'></td>";
	echo "	<td background='/assist/helpdesk/imagem/fundo_tabela_top_centro_azul_claro.gif'   colspan='10' align = 'center' width='100%' style='font-family: arial ; color:#666666'><b>Lista de Chamados</b></td>";
	echo "	<td background='/assist/helpdesk/imagem/fundo_tabela_top_direito_azul_claro.gif'  rowspan='2'><img src='/assist/imagens/pixel.gif' width='9'></td>";
	echo "</tr>";

	echo "<tr bgcolor='#D9E8FF' style='font-family: arial ; font-size:10px;color: #666666'>";
	echo "	<td ></td>";
	echo "	<td ><strong>N°</strong></td>";
	echo "	<td ><strong>Título</strong></td>";
	echo "	<td ><strong>Status</strong></td>";
	echo "	<td align='center'><strong>Autor </strong></td>";
	echo "	<td ><strong>Fábrica </strong></td>";
	echo "	<td ><strong>Data Início</strong></td>";
	echo "	<td ><strong>Data Fim</strong></td>";
	echo "	<td ><strong>Duração</strong></td>";
	echo "	<td align='right'><strong>Última Int.</strong></td>";
	echo "</tr>";


	if (@pg_numrows($res_result) > 0) {

//inicio imprime chamados
		for ($i = 0 ; $i < pg_numrows ($res_result) ; $i++) {
			$hd_chamado           = pg_result($res_result,$i,hd_chamado);
			$admin                = pg_result($res_result,$i,nome_admin);
			$data                 = pg_result($res_result,$i,data);
			$titulo               = pg_result($res_result,$i,titulo);
			$status               = pg_result($res_result,$i,status);
			$atendente            = pg_result($res_result,$i,atendente);
			$resolvido            = pg_result($res_result,$i,resolvido);
			$fabrica_nome            = pg_result($res_result,$i,fabrica_nome);
			$exigir_resposta         = pg_result($res_result,$i,exigir_resposta);

			$sql3 = "SELECT to_char (data,'DD/MM HH24:MI') AS data,
						to_char (data,'YYYY-MM-DD HH24:MI') AS data2
						FROM tbl_hd_chamado_item
						WHERE hd_chamado=$hd_chamado
						ORDER BY hd_chamado_item DESC  LIMIT 1";
			$res_result_3 = pg_exec ($con,$sql3);
			if (pg_numrows($res_result_3)>0){
				$ultima_interacao	=	pg_result($res_result_3,0,data);
				$ultima_interacao2	=	pg_result($res_result_3,0,data2);
			}
			if(strlen($ultima_interacao)==0)
				$ultima_interacao2=$ultima_interacao="<font color='FF0000'>» não houve</font>";

			$sql2 = "SELECT nome_completo, admin
				FROM tbl_admin
				WHERE	admin='$atendente'";

			$res2 = pg_exec ($con,$sql2);

			$xatendente            = pg_result($res2,0,nome_completo);
			$xxatendente = explode(" ", $xatendente);

			if ($status=='Resolvido')	$cor='#D7FFE1';
			else					$cor='#FFFF99';
			//$cor= '#FF99CC';
			//if ($i % 2 == 0) $cor = '#D7FFE1';


			echo "<tr style='font-family: arial ; font-size: 10px ; cursor: hand; border:solid 2px #cecece;' height='25' bgcolor='$cor'  onmouseover=\"this.bgColor='#D9E8FF'\" onmouseout=\"this.bgColor='$cor'\" >";

			echo "<td  nowrap background='/assist/helpdesk/imagem/fundo_tabela_centro_esquerdo.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";


			echo "<td  nowrap >";
			if($status =="Análise" AND $exigir_resposta <> "t"){
				echo "<img src='/assist/admin/imagens_admin/status_azul.gif' align='absmiddle'> ";
			}elseif($exigir_resposta == "t" AND $status<>'Cancelado' OR ($status == "Resolvido" AND strlen($resolvido)==0 )) {
				echo "<img src='/assist/admin/imagens_admin/status_vermelho.gif' align='absmiddle' alt='Aguardando resposta do cliente'> ";
			}elseif (($status == "Resolvido" AND strlen($resolvido)>0) OR $status == "Cancelado") {
					echo "<img src='/assist/admin/imagens_admin/status_verde.gif' align='absmiddle'> ";
				}elseif ($status == "Aprovação") {
					echo "<img src='/assist/admin/imagens_admin/status_amarelo.gif' align='absmiddle'> ";
				}else{
					echo "<img src='/assist/admin/imagens_admin/status_azul.gif' align='absmiddle'> ";
				}
			echo "&nbsp;</td>";

			echo "<td   nowrap >";
			if($status == "Novo")	echo "<a href='adm_atendimento.php?hd_chamado=$hd_chamado'>$hd_chamado</a>";
			else				echo "<a href='adm_chamado_detalhe.php?hd_chamado=$hd_chamado'>$hd_chamado</a>";
			echo "&nbsp;&nbsp;</td>";


			echo "<td  nowrap  title='$titulo'> ";
			/*if($status == "Novo" OR $login_admin==435 )
					echo "<a href='adm_atendimento.php?hd_chamado=$hd_chamado'>$hd_chamado</a>";
			else 		*/
			echo "<a href='adm_chamado_detalhe.php?hd_chamado=$hd_chamado'><acronym title='$titulo'>";
			echo substr($titulo,0,25)."...</acronym></a></td>";

			echo "<td nowrap>";
			if (($status != 'Resolvido') and ($status != 'Cancelado')) {
				echo "<font color=#FF0000><B>$status </B></font>";
			}else{
				echo "$status";
			}
			echo "</td>";


			$data1 = strtotime($data);
			$data2 = time();
			if ($status=='Resolvido'){
				$data2 = strtotime($ultima_interacao2);
			}
			//echo "<hr> ".date("d/m/Y H:i",$data1)." <br> ".date("d/m/Y H:i",$data2)." <br>".date("d/m/Y H:i",$resolvido)." -";
			$tota_horas = 0;
			//$calcular=date("d/m/Y H:i",$data1)." - ".date("d/m/Y H:i",$data2);
			if (date("d",$data2)==date("d",$data1)){ // no  caso se chamado for aberto e fechado no mesmo dias ou aberto e ainda esta no dia da abertura
				//$data_tmp1=mktime(17, 35, 0, date("m",$data1), date("d",$data1), date("Y",$data1));
				$tota_horas =  $data2 - $data1;
			}
			else {
				$data_tmp1=mktime(16, 15, 0, date("m",$data1), date("d",$data1), date("Y",$data1));
				$tota_horas =  $data_tmp1 - $data1;

				if ($tota_horas/60/60>8) $tota_horas=8*60*60;

				for ($j = 0 ; $j < 330 ; $j++) {
					$data1 += 24*60*60;
					$dia_semana  = strtoupper(date("l",$data1));
					if ($data1>$data2)
						break;
					if ($dia_semana!="SATURDAY" && $dia_semana!="SUNDAY"){
						if (date("d/m/Y",$data1)==date("d/m/Y",$data2)){
							$data_tmp1=mktime(7, 0, 0, date("m",$data2), date("d",$data2), date("Y",$data2));
							$tota_horas +=  $data2-$data_tmp1;
						}else{
							$tota_horas +=8*60*60;
						}
					}
				}
				//if (date("d/m/Y",$data1)==date("d/m/Y",$data2)){
					$data_tmp1=mktime(7, 0, 0, date("m",$data2), date("d",$data2), date("Y",$data2));
					$tota_horas +=  $data2-$data_tmp1;
				//}

			}
			$tota_horas = $tota_horas/60/60;
			if ($tota_horas<1){
				$tota_horas = ($tota_horas*60)." minutos ";
			}
			else{
				$tota_horas = round($tota_horas)." horas";
			}

			echo "<td align='center'><font size='1'>";
			echo $admin;
			echo "</font></td>";
			$data = date("d/m/Y H:i",strtotime($data));
			if ($status=='Resolvido' AND strlen($resolvido)==0){
				$resolvido = date("d/m/Y H:i",strtotime($ultima_interacao2));
			}
			else {
				//$resolvido = date("d/m/Y H:i",strtotime($resolvido));
			}

			echo "<td>&nbsp;$fabrica_nome&nbsp;</td>\n";
			echo "<td>$data &nbsp;</td>\n";
			echo "<td>$resolvido&nbsp;</td>\n";
			echo "<td align='center'><b>$tota_horas</b></td>\n";
			echo "<td align='right'>&nbsp;$ultima_interacao</td>\n";
			echo "<td background='/assist/helpdesk/imagem/fundo_tabela_centro_direito.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>\n";
			echo "</tr>\n";

			echo "<tr>\n";
			echo "<td  nowrap background='/assist/helpdesk/imagem/fundo_tabela_centro_esquerdo.gif' \n><img src='/assist/imagens/pixel.gif' width='9'></td>\n";
			echo "<td colspan='10' align='right'>\n";
			echo "<table width = '600' align = 'center' cellpadding='2' cellspacing='2' border='0'>\n";


			$sql = "SELECT tbl_arquivo.descricao AS arquivo,
						to_char (tbl_controle_acesso_arquivo.data_inicio,'DD/MM') AS data_inicio,
						to_char (tbl_controle_acesso_arquivo.hora_inicio,'HH24:MI') AS hora_inicio,
						to_char (tbl_controle_acesso_arquivo.data_fim,'DD/MM') AS data_fim,
						to_char (tbl_controle_acesso_arquivo.hora_fim,'HH24:MI') AS hora_fim
					FROM tbl_arquivo JOIN tbl_controle_acesso_arquivo USING(arquivo)
					WHERE hd_chamado=$hd_chamado
					ORDER BY tbl_controle_acesso_arquivo.data_inicio";
			$res_arquivos = pg_exec ($con,$sql);
			if (@pg_numrows($res_arquivos) > 0) {
				echo "<tr style='font-family: arial ; font-size: 10px ;'>\n";
				echo "<td nowrap style='border-bottom:1px solid #cecece'><b>Início</b></td>\n";
				echo "<td nowrap style='border-bottom:1px solid #cecece'align='center'><b>Histórico dos Arquivos Utilizados</b></td>\n";
				echo "<td nowrap style='border-bottom:1px solid #cecece'><b>Fim</b></td>\n";
				echo "</tr>\n";
				$arquivo = "";
				$data_inicio = "";
				$data_fim = "";
				for ($k = 0 ; $k < pg_numrows ($res_arquivos) ; $k++) {
					$arquivo	.= str_replace ("/var/www/assist/www/","",pg_result($res_arquivos,$k,arquivo))."<br>";
					$data_inicio.= pg_result($res_arquivos,$k,data_inicio)."  ".pg_result($res_arquivos,$k,hora_inicio)."<br>";
					$data_fim.= pg_result($res_arquivos,$k,data_fim)."  ".pg_result($res_arquivos,$k,hora_fim)."<br>";
				}
				echo "<tr style='font-family: arial ; font-size: 10px ;' height='25'>\n";
				echo "<td nowrap>$data_inicio</td>\n";
				echo "<td align='left' style='padding-left:10px'>$arquivo</td>\n";
				echo "<td nowrap>$data_fim</td>\n";
				echo "</tr>\n";
			}
			$sql = "SELECT
						tbl_hd_chamado_item.hd_chamado_item AS hd_chamado_item,
						to_char (tbl_hd_chamado_item.data,'DD/MM HH24:MI') AS data,
						tbl_hd_chamado_item.comentario AS comentario,
						tbl_hd_chamado_item.admin AS admin,
						tbl_hd_chamado_item.interno AS interno,
						tbl_hd_chamado_item.status_item as status_item,
						tbl_admin.login AS login
					FROM tbl_hd_chamado_item
					JOIN tbl_admin USING(admin)
					WHERE hd_chamado = $hd_chamado
					AND tbl_hd_chamado_item.data BETWEEN '$data_de 00:00:01' AND '$data_ate 23:59:59'
					ORDER BY tbl_hd_chamado_item.data";
			$res_comentarios = pg_exec ($con,$sql);
			if (@pg_numrows($res_comentarios) > 0) {
				echo "<tr style='font-family: arial ; font-size: 10px ;'>\n";
				echo "<td nowrap style='border-bottom:1px solid #cecece'><b>Data</b></td>\n";
				echo "<td nowrap style='border-bottom:1px solid #cecece' align='center'><b>Interação no período</b></td>\n";
				echo "<td nowrap style='border-bottom:1px solid #cecece'><b>Usuário</b></td>\n";
				echo "</tr>\n";
				for ($k = 0 ; $k < pg_numrows ($res_comentarios) ; $k++) {
					$hd_chamado_item	= pg_result($res_comentarios,$k,hd_chamado_item);
					$data		= pg_result($res_comentarios,$k,data);
					$comentario	= strip_tags(pg_result($res_comentarios,$k,comentario),"<a><b><i><u>");
					$admin		= pg_result($res_comentarios,$k,admin);
					$interno		= pg_result($res_comentarios,$k,interno);
					$status_item	= pg_result($res_comentarios,$k,status_item);
					$login		= pg_result($res_comentarios,$k,login);

					$cor='#F2F7FF';
					if ($k % 2 == 0) $cor = '#FFFFFF';
					echo "<tr style='font-family: arial ; font-size: 10px ;' height='25' bgcolor='$cor'>\n";
						echo "<td nowrap>$data</td>\n";
						echo "<td>$comentario</td>\n";
						echo "<td nowrap>$login</td>\n";
					echo "</tr>\n";

				}

			}
			echo "</table>";
			echo "</td>";
			echo "<td background='/assist/helpdesk/imagem/fundo_tabela_centro_direito.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";
			echo "</tr>";
		}

	//fim imprime chamados

		echo "<tr>";
		echo "<td background='/assist/helpdesk/imagem/fundo_tabela_baixo_esquerdo.gif'><img src='/assist/imagens/pixel.gif' width='9'></td>";
		echo "<td background='/assist/helpdesk/imagem/fundo_tabela_baixo_centro.gif' colspan='10' align = 'center' width='100%'></td>";
		echo "<td background='/assist/helpdesk/imagem/fundo_tabela_baixo_direito.gif'><img src='/assist/imagens/pixel.gif' width='9'></td>";
		echo "</tr>";

		echo "</table>";


	}

	echo "</td>";
	echo "</tr>";
	echo "</table>";

}


?>

<? include "rodape.php" ?>

<?
if ($msg) {
	if ($login_admin == '399') {
		echo "<script language='JavaScript'>alert('$msg');</script>";
	}
}
?>