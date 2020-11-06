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
#$sql="select * from tbl_hd_chamado where atendente is null";
$sql="SELECT * FROM tbl_hd_chamado
	  WHERE (atendente = 399) AND (status ILIKE 'novo' OR status ILIKE '$status' OR status IS NULL)";

$res = pg_exec ($con,$sql);
$chamados_nao_atendidos = pg_numrows($res);
if (@pg_numrows($res) > 0) {
	$msg='Existem '.$chamados_nao_atendidos.' chamada(s) não atendida(s)';
}

$status_busca = $_POST['status'];
//echo "$status_busca";
$atendente_busca = $_POST['atendente_busca'];
//echo "$atendente_busca";
$valor_chamado = $_POST['valor_chamado'];

###INICIO ESTATITICAS###

#NOVO#
$sqlnovo = "SELECT count(*) AS total_novo
	FROM tbl_hd_chamado
	WHERE (status ILIKE 'novo' OR status ILIKE '$status'  OR status IS NULL) ";
if($atendente_busca <> ''){

	$sqlnovo .= " AND atendente = '$atendente_busca'";
	}
	$resnovo = @pg_exec ($con,$sqlnovo);
//echo "$sqlnovo<BR>";
if (@pg_numrows($resnovo) > 0) {//PEGA OS DADOS DE QUEM ESTÁ ABRINDO O CHAMADO
	$xtotal_novo           = pg_result($resnovo,0,total_novo);
	}
//echo "$xtotal_novo";
#ANALISE#
$sqlanalise = "SELECT count(*) AS total_analise
	FROM tbl_hd_chamado
	WHERE status ILIKE 'análise' ";
if($atendente_busca <> ''){
	$sqlanalise .= " AND atendente = '$atendente_busca'";
	}
	$resanalise = @pg_exec ($con,$sqlanalise);
//echo "$sqlanalise<BR>";
if (@pg_numrows($resanalise) > 0) {//PEGA OS DADOS DE QUEM ESTÁ ABRINDO O CHAMADO
	$xtotal_analise           = pg_result($resanalise,0,total_analise);
	}
//echo "$xtotal_analise";
#APROVACAO#
$sqlaprovacao = "SELECT count(*) AS total_aprovacao
	FROM tbl_hd_chamado
	WHERE status ILIKE 'aprovação' ";
if($atendente_busca <> ''){
	$sqlaprovacao .= " AND atendente = '$atendente_busca'";
	}
	$resaprovacao = @pg_exec ($con,$sqlaprovacao);
//echo "$sqlaprovacao<BR>";
if (@pg_numrows($resnovo) > 0) {//PEGA OS DADOS DE QUEM ESTÁ ABRINDO O CHAMADO
	$xtotal_aprovacao          = pg_result($resaprovacao,0,total_aprovacao);
	}
//echo "$xtotal_aprovacao";

#RESOLVIDO#
$sqlresolvido = "SELECT count(*) AS total_resolvido
	FROM tbl_hd_chamado
	WHERE status ILIKE 'resolvido' ";
if($atendente_busca <> ''){
	$sqlresolvido .= " AND atendente = '$atendente_busca'";
	}
	$resresolvido = @pg_exec ($con,$sqlresolvido);
//echo "$sqlresolvido<BR>";
if (@pg_numrows($resresolvido) > 0) {//PEGA OS DADOS DE QUEM ESTÁ ABRINDO O CHAMADO
	$xtotal_resolvido         = pg_result($resresolvido,0,total_resolvido);
	}
//echo "$xtotal_resolvido";

#EXECUCAO#
$sqlexecucao = "SELECT count(*) AS total_execucao
	FROM tbl_hd_chamado
	WHERE status ILIKE 'execução' ";
if($atendente_busca <> ''){
	$sqlexecucao .= " AND atendente = '$atendente_busca'";
	}
	$resexecucao = @pg_exec ($con,$sqlexecucao);
//echo "$sqlexecucao<BR>";
if (@pg_numrows($resexecucao) > 0) {//PEGA OS DADOS DE QUEM ESTÁ ABRINDO O CHAMADO
	$xtotal_execucao         = pg_result($resexecucao,0,total_execucao);
	}
//echo "$xtotal_execucao";
#CANCELADO#
$sqlcancelado = "SELECT count(*) AS total_cancelado
	FROM tbl_hd_chamado
	WHERE status ILIKE 'cancelado' ";
if($atendente_busca <> ''){
	$sqlcancelado .= " AND atendente = '$atendente_busca'";
	}
	$rescancelado = @pg_exec ($con,$sqlcancelado);
//echo "$sqlcancelado<BR>";
if (@pg_numrows($rescancelado) > 0) {//PEGA OS DADOS DE QUEM ESTÁ ABRINDO O CHAMADO
	$xtotal_cancelado         = pg_result($rescancelado,0,total_cancelado);
	}
//echo "$xtotal_cancelado";
#TODOS#
$sqltodos = "SELECT count(*) AS total_todos
	FROM tbl_hd_chamado 
	WHERE 1=1 ";
if($atendente_busca <> ''){
	$sqltodos .= " AND atendente = '$atendente_busca'";
	}
	$restodos = @pg_exec ($con,$sqltodos);
//echo "$sqltodos<BR>";
if (@pg_numrows($restodos) > 0) {//PEGA OS DADOS DE QUEM ESTÁ ABRINDO O CHAMADO
	$xtotal_todos         = pg_result($restodos,0,total_todos);
	}
//echo "$xtotal_todos";






###FIM ESTATITICAS###

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
			tbl_fabrica.nome AS fabrica_nome
		FROM tbl_hd_chamado
		JOIN tbl_admin ON tbl_hd_chamado.admin = tbl_admin.admin
		JOIN tbl_fabrica ON tbl_admin.fabrica  = tbl_fabrica.fabrica";

if($valor_chamado <> ''){
	$sql .= " WHERE  tbl_hd_chamado.hd_chamado = '$valor_chamado'";
}else{
	$sql .= " WHERE  tbl_hd_chamado.fabrica_responsavel = $login_fabrica";
}
/*
if(($atendente_busca == '') && ($login_admin<>'399'))
{
	$sql .= " AND tbl_hd_chamado.atendente = $login_admin";

}*/

if ($atendente_busca <> '' ){
	$sql .= " AND tbl_hd_chamado.atendente = $atendente_busca";
}
/*else{
if($login_admin<>'399')
	$sql .= " AND tbl_hd_chamado.atendente = $login_admin";
}*/


if ($status_busca=="p"){
	$sql .= " AND tbl_hd_chamado.status ILIKE 'Aprovação' ";
}

if ($status_busca=="a"){
	$sql .= " AND tbl_hd_chamado.status ILIKE 'análise' ";
}

if ($status_busca=="r"){
	$sql .= " AND tbl_hd_chamado.status ILIKE 'resolvido' ";
}

if ($status_busca=="e"){
	$sql .= " AND tbl_hd_chamado.status ILIKE 'Execução' ";
}

if ($status_busca=="c"){
	$sql .= " AND tbl_hd_chamado.status ILIKE 'Cancelado' ";
}



if ($status_busca=="n"){
	$sql .= " AND ((tbl_hd_chamado.status ILIKE 'novo') OR (tbl_hd_chamado.status ILIKE '$status')  OR (tbl_hd_chamado.status IS NULL))";
}



if ($status_busca==""){
	
}


$sql .= " ORDER BY tbl_hd_chamado.data DESC";
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
	echo "<table width = '700' align = 'center' cellpadding='0' cellspacing='0' border='0'>";
	echo "<tr>";
	echo "	<td background='/assist/helpdesk/imagem/fundo_tabela_top_esquerdo_azul_claro.gif' rowspan='2'><img src='/assist/imagens/pixel.gif' width='9'></td>";
	echo "	<td background='/assist/helpdesk/imagem/fundo_tabela_top_centro_azul_claro.gif'   colspan='7' align = 'center' width='100%' style='font-family: arial ; color:#666666'>&nbsp;</td>";
	echo "	<td background='/assist/helpdesk/imagem/fundo_tabela_top_direito_azul_claro.gif'  rowspan='2'><img src='/assist/imagens/pixel.gif' width='9'></td>";
	echo "</tr>";
	echo "<tr bgcolor='#D9E8FF' style='font-family: arial ; color: #666666'>";
	echo "	<td colspan='8'><b><CENTER>Pesquisa Chamados</CENTER></b></td>";
	echo "</tr>";
	echo "<tr align='left'  height ='70'>";
	echo "	<td background='/assist/helpdesk/imagem/fundo_tabela_centro_esquerdo.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";
	
	echo "<td align='center'><INPUT TYPE=\"radio\" NAME=\"status\" value='n'";
	if ($status_busca=='n') echo "CHECKED";
	echo "><BR><font size=2>Novo<BR>(</font><font size=2 "; if($xtotal_novo>0)echo"color='ff0000'";echo"><b> $xtotal_novo  </B></font><font size=2>)</font></td>";
	
	echo "	<td align='center' ><INPUT TYPE=\"radio\" NAME=\"status\" value='a'";
	if ($status_busca=='a') echo "CHECKED";
	echo "><BR><font size=2>Analise<BR>(</font><font size=2 "; if($xtotal_analise>0)echo "color='ff0000'";echo "><b> $xtotal_analise </B></font><font size=2>)</font></td>";
	
	echo "	<td align='center' ><INPUT TYPE=\"radio\" NAME=\"status\" value='p'";
	if ($status_busca=='p') echo "CHECKED";
	echo "><BR><font size=2>Aprovação<BR>(</font><font size=2 "; if($xtotal_aprovacao>0)echo "color='ff0000'";echo "><b> $xtotal_aprovacao </B></font><font size=2>)</font></center></td>";
	
	echo "<td align='center'><INPUT TYPE=\"radio\" NAME=\"status\" value='r'";
	if ($status_busca=='r') echo "CHECKED";
	echo "><BR><font size=2>Resolvido<BR>(</font><font size=2><b> $xtotal_resolvido </B></font><font size=2>)</font></td>";
	
	echo "<td align='center'><INPUT TYPE=\"radio\" NAME=\"status\" value='e'";
	if ($status_busca=='e') echo "CHECKED";
	echo "><BR><font size=2>Execução<BR>(</font><font size=2 "; if($xtotal_execucao>0)echo "color='ff0000'";echo "><b> $xtotal_execucao </B></font><font size=2>)</font></td>";
	
	echo "	<td align='center'><INPUT TYPE=\"radio\" NAME=\"status\" value='c'";
	if ($status_busca=='c') echo "CHECKED";
	echo "><BR><center><font size=2>Cancelado<BR>(<b> $xtotal_cancelado </B>)</font></center></td>";

	echo "<td align='center'><INPUT TYPE=\"radio\" NAME=\"status\" value=''";
	if ($status_busca=='') echo "CHECKED";
	echo "><BR><font size=2>Todos<BR>(<b> $xtotal_todos </B>)</font></td>";


	echo "	<td background='/assist/helpdesk/imagem/fundo_tabela_centro_direito.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";
	echo "</tr>";
	
	
	echo "<tr bgcolor='#D9E8FF' style='font-family: arial ; color: #666666'>";
	echo "	<td background='/assist/helpdesk/imagem/fundo_tabela_centro_esquerdo.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";
	echo "	<td colspan='7'><CENTER><B>Atendente</B></CENTER></td>";
	echo "	<td background='/assist/helpdesk/imagem/fundo_tabela_centro_direito.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";
	echo "</tr>";
	
	echo "<tr>";
	echo "	<td background='/assist/helpdesk/imagem/fundo_tabela_centro_esquerdo.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";
	echo "	<td td colspan='7'>";
	

		##### INÍCIO FAMÍLIA #####
		$sqlatendente = "SELECT  nome_completo,
					admin
				FROM    tbl_admin
				WHERE   tbl_admin.fabrica = 10
				ORDER BY tbl_admin.nome_completo;";
	
		$resatendente = pg_exec ($con,$sqlatendente);

		if (pg_numrows($resatendente) > 0) {
			echo "<BR><center><select class='frm' style='width: 200px;' name='atendente_busca'>\n";
			echo "<option value=''>- TODOS -</option>\n";

			for ($x = 0 ; $x < pg_numrows($resatendente) ; $x++){
				$n_admin = trim(pg_result($resatendente,$x,admin));
				$nome_atendente  = trim(pg_result($resatendente,$x,nome_completo));

				echo "<option value='$n_admin'"; 
				//if ($login_admin == $n_admin) echo " SELECTED "; 
				echo "> $nome_atendente</option>\n";
			}
			
			echo "</select></center><BR>";
		}
		##### FIM FAMÍLIA #####

		
	
	
	echo "</td>";
	echo "	<td background='/assist/helpdesk/imagem/fundo_tabela_centro_direito.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";
	echo "</tr>";

	echo "<tr bgcolor='#D9E8FF' style='font-family: arial ; color: #666666'>";
	echo "	<td background='/assist/helpdesk/imagem/fundo_tabela_centro_esquerdo.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";
	echo "	<td colspan='7'><CENTER><B>Busca pelo Número do Chamado</B></CENTER></td>";
	echo "	<td background='/assist/helpdesk/imagem/fundo_tabela_centro_direito.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";
	echo "</tr>";
	
		echo "<tr>";
	echo "	<td background='/assist/helpdesk/imagem/fundo_tabela_centro_esquerdo.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";
	echo "	<td td colspan='2'>&nbsp;</td>";
	echo "<td align='center' colspan='3'><BR><font size=2>Número Chamado:</font> &nbsp; <input type='text' size='5' maxlength='5' name='valor_chamado' value=''> <BR><BR>";

	echo "</td>";
	echo "	<td td colspan='2'>&nbsp;</td>";
	echo "	<td background='/assist/helpdesk/imagem/fundo_tabela_centro_direito.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";
	echo "</tr>";
	
	
	
	
	
	
	
#botao submit

	echo "<tr>";
	echo "	<td background='/assist/helpdesk/imagem/fundo_tabela_centro_esquerdo.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";
	echo "<td colspan='7' nowrap><CENTER><INPUT TYPE=\"submit\" value=\"Pesquisar\"></CENTER></td>";
//	echo "	<td></td>";
	echo "	<td background='/assist/helpdesk/imagem/fundo_tabela_centro_direito.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";
	echo "</tr>";

#===========================

	echo "<tr>";
	echo "	<td background='/assist/helpdesk/imagem/fundo_tabela_baixo_esquerdo.gif'><img src='/assist/imagens/pixel.gif' width='9'></td>";
	echo "	<td background='/assist/helpdesk/imagem/fundo_tabela_baixo_centro.gif' colspan='7' align = 'center' width='100%'></td>";
	echo "	<td background='/assist/helpdesk/imagem/fundo_tabela_baixo_direito.gif'><img src='/assist/imagens/pixel.gif' width='9'></td>";
	echo "</tr>";
	echo "</table>";
	echo "</FORM>";

	
	/*--===============================TABELA DE CHAMADOS========================--*/

	$sqlmeuchamado = "SELECT count(*) AS total_meuchamado
					  FROM tbl_hd_chamado
					  WHERE (status not iLIKE 'RESOLVIDO' AND status not ILIKE 'CANCELADO') 
					  AND atendente = $login_admin";
	$resmeuchamado     = pg_exec ($con,$sqlmeuchamado);
	$xtotal_meuchamado = pg_result($resmeuchamado,0,total_meuchamado);

	if ($xtotal_meuchamado > 0) {
		echo "<font color=#FF0000 size='5' face='Arial'><CENTER><B>VOCÊ TEM $xtotal_meuchamado CHAMADOS PENDENTES.</B></CENTER></font>";
	}

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
	
	
	// ##### PAGINACAO ##### //
	$sqlCount  = "SELECT count(*) FROM (";
	$sqlCount .= $sql;
	$sqlCount .= ") AS count";

	
	require "_class_paginacao.php";

	// definicoes de variaveis
	$max_links = 11;				// máximo de links à serem exibidos
	$max_res   = 50;				// máximo de resultados à serem exibidos por tela ou pagina
	$mult_pag  = new Mult_Pag();	// cria um novo objeto navbar
	$mult_pag->num_pesq_pag = $max_res; // define o número de pesquisas (detalhada ou não) por página

	$res = $mult_pag->executar($sql, $sqlCount, $con, "otimizada", "pgsql");

	// ##### PAGINACAO ##### //
		
if (@pg_numrows($res) > 0) {

	
		
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

		echo "<tr  style='font-family: arial ; font-size: 12px ; cursor: hand; ' height='25' bgcolor='$cor'  onmouseover=\"this.bgColor='#D9E8FF'\" onmouseout=\"this.bgColor='$cor'\" ><a href='adm_chamado_detalhe.php?hd_chamado=$hd_chamado'>";
		echo "<td background='/assist/helpdesk/imagem/fundo_tabela_centro_esquerdo.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";

		echo "<td>$hd_chamado&nbsp;</td>";
		echo "<td><a href='adm_chamado_detalhe.php?hd_chamado=$hd_chamado'>$titulo</a></td>";
		if (($status != 'Resolvido') and ($status != 'Cancelado')) {
			echo "<td nowrap><font color=#FF0000><B>$status </B></font></td>";
		}else{
			echo "<td nowrap>$status </td>";
		}
		echo "<td nowrap>&nbsp;$data &nbsp;</td>";
		echo "<td>";
		if (strlen ($nome_completo) > 0) {
			echo $nome_completo;
			
		}else{
			echo $login;
		}
		echo "</td>";
		echo "<td>&nbsp;$fabrica_nome&nbsp;</td>";
		echo "<td>&nbsp;$xxatendente[0]&nbsp;</td>";
		
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
### PÉ PAGINACAO###

	echo "<table border='0' align='center'>";
	echo "<tr>";
	echo "<td colspan='9' align='center'>";
		// ##### PAGINACAO ##### //

	// links da paginacao
	echo "<br>";

	if($pagina < $max_links) {
		$paginacao = pagina + 1;
	}else{
		$paginacao = pagina;
	}

	// paginacao com restricao de links da paginacao

	// pega todos os links e define que 'Próxima' e 'Anterior' serão exibidos como texto plano
	$todos_links		= $mult_pag->Construir_Links("strings", "sim");

	// função que limita a quantidade de links no rodape
	$links_limitados	= $mult_pag->Mostrar_Parte($todos_links, $coluna, $max_links);

	for ($n = 0; $n < count($links_limitados); $n++) {
		echo "<font color='#DDDDDD'>".$links_limitados[$n]."</font>&nbsp;&nbsp;";
	}



	$resultado_inicial = ($pagina * $max_res) + 1;
	$resultado_final   = $max_res + ( $pagina * $max_res);
	$registros         = $mult_pag->Retorna_Resultado();

	$valor_pagina   = $pagina + 1;
	$numero_paginas = intval(($registros / $max_res) + 1);

	if ($valor_pagina == $numero_paginas) $resultado_final = $registros;

	if ($registros > 0){
		echo "<br>";
		echo "<font size='2'>Resultados de <b>$resultado_inicial</b> a <b>$resultado_final</b> do total de <b>$registros</b> registros.</font>";
		echo "<font color='#cccccc' size='1'>";
		echo " (Página <b>$valor_pagina</b> de <b>$numero_paginas</b>)";
		echo "</font>";
		echo "</div>";
	}
	// ##### PAGINACAO ##### //
	
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