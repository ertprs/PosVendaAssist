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
<meta http-equiv="refresh" content="500">
<?
$sql="select * from tbl_hd_chamado where status is null or status=''";

$res = pg_exec ($con,$sql);
$chamados_nao_atendidos = pg_numrows($res);
if (@pg_numrows($res) > 0) {
	$msg='Existem '.$chamados_nao_atendidos.' chamada(s) não atendida(s)';
}

$status_busca = $_POST['status'];
//echo "$status_busca";
$atendente_busca = $_POST['atendente'];
//echo "$atendente_busca";


$sql = "SELECT 
			hd_chamado              ,
			tbl_hd_chamado.admin    ,
			tbl_admin.nome_completo ,
			tbl_admin.login         ,
			to_char (tbl_hd_chamado.data,'DD/MM HH24:MI') AS data,
			titulo                  ,
			status                  ,
			atendente               ,
			tbl_fabrica.nome AS fabrica_nome
		FROM tbl_hd_chamado
		JOIN tbl_admin ON tbl_hd_chamado.admin = tbl_admin.admin
		JOIN tbl_fabrica ON tbl_admin.fabrica  = tbl_fabrica.fabrica
		WHERE  tbl_hd_chamado.fabrica_responsavel = $login_fabrica";
if ($atendente_busca=="t"){
	$sql .= " AND tbl_hd_chamado.atendente=$login_admin ";
}
if ($status_busca=="p"){
	$sql .= " AND tbl_hd_chamado.status ILIKE 'aprovação' ";
}
if ($status_busca=="a"){
	$sql .= " AND tbl_hd_chamado.status ILIKE 'análise' ";
}
if ($status_busca=="r"){
	$sql .= " AND tbl_hd_chamado.status ILIKE 'resolvido' ";
}

if ($status_busca=="e"){
	$sql .= " AND tbl_hd_chamado.status ILIKE 'execução' ";
}


if ($status_busca=="n"){
	$sql .= " AND tbl_hd_chamado.status ILIKE'novo' or tbl_hd_chamado.status is null or tbl_hd_chamado.status='' ";
}

$sql .= " ORDER BY tbl_hd_chamado.data DESC ";
//echo nl2br($sql);
$res = pg_exec ($con,$sql);
//		AND   (tbl_hd_chamado.atendente = $login_admin OR tbl_hd_chamado.atendente IS NULL)
if (@pg_numrows($res) >= 0) {
//	$nome  = trim(pg_result ($res,0,nome));
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
	echo "<br>";

/*================================TABELA DE ESCOLHA DE STATUS============================*/


	echo "<FORM METHOD='POST' ACTION='$PHP_SELF'>";
	echo "<table width = '600' align = 'center' cellpadding='0' cellspacing='0' border='0'>";
	echo "<tr>";
	echo "	<td background='/assist/helpdesk/imagem/fundo_tabela_top_esquerdo_azul_claro.gif' rowspan='2'><img src='/assist/imagens/pixel.gif' width='9'></td>";
	echo "	<td background='/assist/helpdesk/imagem/fundo_tabela_top_centro_azul_claro.gif'   colspan='6' align = 'center' width='100%' style='font-family: arial ; color:#666666'>&nbsp;</td>";
	echo "	<td background='/assist/helpdesk/imagem/fundo_tabela_top_direito_azul_claro.gif'  rowspan='2'><img src='/assist/imagens/pixel.gif' width='9'></td>";
	echo "</tr>";
	echo "<tr bgcolor='#D9E8FF' style='font-family: arial ; color: #666666'>";
	echo "	<td colspan='6'><b><CENTER>Pesquisa Chamados</CENTER></b></td>";
	echo "</tr>";
	echo "<tr >";
	echo "	<td background='/assist/helpdesk/imagem/fundo_tabela_centro_esquerdo.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";
	echo "	<td ><INPUT TYPE=\"radio\" NAME=\"status\" value='n'";
	if ($status_busca=='n') echo "CHECKED";
	echo ">Novo</td>";
	echo "	<td ><INPUT TYPE=\"radio\" NAME=\"status\" value='a'";
	if ($status_busca=='a') echo "CHECKED";
	echo ">Analise</td>";
	echo "	<td ><INPUT TYPE=\"radio\" NAME=\"status\" value='p'";
	if ($status_busca=='p') echo "CHECKED";
	echo ">Aprovação</td>";
	echo "	<td><INPUT TYPE=\"radio\" NAME=\"status\" value='r'";
	if ($status_busca=='r') echo "CHECKED";
	echo ">Resolvido</td>";

	echo "	<td><INPUT TYPE=\"radio\" NAME=\"status\" value='e'";
	if ($status_busca=='e') echo "CHECKED";
	echo ">Execução</td>";
	echo "<td></td>";

	echo "	<td background='/assist/helpdesk/imagem/fundo_tabela_centro_direito.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";
	echo "</tr>";
	echo "<tr bgcolor='#D9E8FF' style='font-family: arial ; color: #666666'>";
	echo "	<td background='/assist/helpdesk/imagem/fundo_tabela_centro_esquerdo.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";
	echo "	<td colspan='6'><CENTER><B>Status Atendente</B></CENTER></td>";
	echo "	<td background='/assist/helpdesk/imagem/fundo_tabela_centro_direito.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";
	echo "</tr>";
	echo "<tr>";
	echo "	<td background='/assist/helpdesk/imagem/fundo_tabela_centro_esquerdo.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";
	echo "	<td></td>";
	echo "	<td colspan='3'><INPUT TYPE=\"radio\" NAME=\"atendente\" value='f'";
	if ($atendente_busca=='f' OR $atendente_busca=='')echo "CHECKED";
	echo ">Todos</td>";
	echo "	<td ><INPUT TYPE=\"radio\" NAME=\"atendente\" value='t'";
	if ($atendente_busca=='t') echo "CHECKED";
	echo ">Meus Chamados</td>";

	echo "	<td></td>";
	echo "	<td background='/assist/helpdesk/imagem/fundo_tabela_centro_direito.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";
	echo "</tr>";

#botao submit	
	echo "<tr>";
	echo "	<td background='/assist/helpdesk/imagem/fundo_tabela_centro_esquerdo.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";
//	echo "	<td></td>";
	echo "	<td colspan='6'><CENTER><INPUT TYPE=\"submit\" value=\"Pesquisar\"></CENTER></td>";
//	echo "	<td></td>";
	echo "	<td background='/assist/helpdesk/imagem/fundo_tabela_centro_direito.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";
	echo "</tr>";
#===========================

	echo "<tr>";
	echo "	<td background='/assist/helpdesk/imagem/fundo_tabela_baixo_esquerdo.gif'><img src='/assist/imagens/pixel.gif' width='9'></td>";
	echo "	<td background='/assist/helpdesk/imagem/fundo_tabela_baixo_centro.gif' colspan='6' align = 'center' width='100%'></td>";
	echo "	<td background='/assist/helpdesk/imagem/fundo_tabela_baixo_direito.gif'><img src='/assist/imagens/pixel.gif' width='9'></td>";
	echo "</tr>";
	echo "</table>";
	echo "</FORM>";


/*--===============================TABELA DE CHAMADOS========================--*/

	echo "<table width = '600' align = 'center' cellpadding='0' cellspacing='0'>";
	echo "<tr>";
	echo "	<td background='/assist/helpdesk/imagem/fundo_tabela_top_esquerdo_azul_claro.gif' rowspan='2'><img src='/assist/imagens/pixel.gif' width='9'></td>";
	echo "	<td background='/assist/helpdesk/imagem/fundo_tabela_top_centro_azul_claro.gif'   colspan='6' align = 'center' width='100%' style='font-family: arial ; color:#666666'><b>Lista de Chamados</b></td>";
	echo "	<td background='/assist/helpdesk/imagem/fundo_tabela_top_direito_azul_claro.gif'  rowspan='2'><img src='/assist/imagens/pixel.gif' width='9'></td>";
	echo "</tr>";
	echo "<tr bgcolor='#D9E8FF' style='font-family: arial ; color: #666666'>";
	echo "	<td ><strong>Nº </strong></td>";
	echo "	<td nowrap><strong>Título</strong><img src='/assist/imagens/pixel.gif' width='5'></td>";
	echo "	<td ><strong>Status</strong></td>";
	echo "	<td ><strong>Data</strong></td>";
	echo "	<td nowrap><img src='/assist/imagens/pixel.gif' width='5'><strong>Autor </strong></td>";
	echo "	<td nowrap><img src='/assist/imagens/pixel.gif' width='5'><strong>Fábrica </strong></td>";
	echo "</tr>";

	for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
		$hd_chamado           = pg_result($res,$i,hd_chamado);
		$admin                = pg_result($res,$i,admin);
		$login                = pg_result($res,$i,login);
//		$posto                = pg_result($res,$i,posto);
		$data                 = pg_result($res,$i,data);
		$titulo               = pg_result($res,$i,titulo);
		$status               = pg_result($res,$i,status);
		$atendente            = pg_result($res,$i,atendente);
		$nome_completo        = trim(pg_result($res,$i,nome_completo));
		$fabrica_nome         = trim(pg_result($res,$i,fabrica_nome));


		$cor='#F2F7FF';
		if ($i % 2 == 0) $cor = '#FFFFFF';

		echo "<tr  style='font-family: arial ; font-size: 12px ; cursor: hand; ' height='25' bgcolor='$cor'  onmouseover=\"this.bgColor='#D9E8FF'\" onmouseout=\"this.bgColor='$cor'\" ><a href='adm_chamado_detalhe.php?hd_chamado=$hd_chamado'>";
		echo "<td background='/assist/helpdesk/imagem/fundo_tabela_centro_esquerdo.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";

		echo "<td>$hd_chamado</td>";
		echo "<td><a href='adm_chamado_detalhe.php?hd_chamado=$hd_chamado'>$titulo</a></td>";
		echo "<td nowrap>$status </td>";
		echo "<td nowrap>&nbsp;$data </td>";
		echo "<td>";
		if (strlen ($nome_completo) > 0) {
			echo $nome_completo;
		}else{
			echo $login;
		}
		echo "</td>";
		echo "<td>$fabrica_nome</td>";

		echo "<td background='/assist/helpdesk/imagem/fundo_tabela_centro_direito.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";
		echo "</a></tr>"; 
		
	}
	echo "<tr>";
	echo "<td background='/assist/helpdesk/imagem/fundo_tabela_baixo_esquerdo.gif'><img src='/assist/imagens/pixel.gif' width='9'></td>";
	echo "<td background='/assist/helpdesk/imagem/fundo_tabela_baixo_centro.gif' colspan='6' align = 'center' width='100%'></td>";
	echo "<td background='/assist/helpdesk/imagem/fundo_tabela_baixo_direito.gif'><img src='/assist/imagens/pixel.gif' width='9'></td>";
	echo "</tr>";

	echo "</table>"; 
}
?>

<? include "rodape.php" ?>

<?
if ($msg) {
echo "<script language='JavaScript'>alert('$msg');</script>";
}
?>