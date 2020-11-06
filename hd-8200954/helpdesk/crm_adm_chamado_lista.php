<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';

$suporte = 432;

if($login_fabrica<>10) header ("Location: index.php");
//if($login_admin<>$suporte)  header ("Location: adm_atendimento_lista.php");

$TITULO = "Lista de Chamados";

include "menu.php";
?>

<meta http-equiv="refresh" content="300">

<?



$status_busca = $_POST['status'];
if (strlen($status_busca) == 0) $status_busca = $_GET['status'];

$atendente_busca = $_POST['atendente_busca'];
if (strlen($atendente_busca) == 0) $atendente_busca = $_GET['atendente_busca'];

$valor_chamado = $_POST['valor_chamado'];
if (strlen($valor_chamado) == 0)  $valor_chamado = $_GET['valor_chamado'];

$fabrica_busca = $_POST['fabrica_busca'];
if (strlen($fabrica_busca) == 0)  $fabrica_busca = $_GET['fabrica_busca'];


###INICIO ESTATITICAS###

#NOVO#
$sqlnovo = "SELECT count(*) AS total_novo
			FROM tbl_hd_chamado
			WHERE (status ILIKE 'novo' OR status ILIKE '$status'  OR status IS NULL) ";

if($atendente_busca <> '') $sqlnovo .= " AND atendente        = '$atendente_busca'";
if($fabrica_busca<>'')     $sql     .= " AND tbl_fabrica.nome = '$fabrica_busca'"  ;

$resnovo = @pg_exec ($con,$sqlnovo);

if (@pg_numrows($resnovo) > 0) $xtotal_novo = pg_result($resnovo,0,total_novo);


#ANALISE#
$sqlanalise = "SELECT count(*) AS total_analise
				FROM tbl_hd_chamado
				WHERE status ILIKE 'análise' ";

if($atendente_busca <> '') $sqlanalise .= " AND atendente        = '$atendente_busca'";
if($fabrica_busca<>'')     $sql        .= " AND tbl_fabrica.nome = '$fabrica_busca'"  ;

$resanalise = @pg_exec ($con,$sqlanalise);

if (@pg_numrows($resanalise) > 0) $xtotal_analise = pg_result($resanalise,0,total_analise);



#APROVACAO#
$sqlaprovacao = "SELECT count(*) AS total_aprovacao
	FROM tbl_hd_chamado
	WHERE status ILIKE 'aprovação' ";
if($atendente_busca <> '')$sqlaprovacao .= " AND atendente        = '$atendente_busca'";
if($fabrica_busca<>'')    $sql          .= " AND tbl_fabrica.nome = '$fabrica_busca'"  ;

$resaprovacao = @pg_exec ($con,$sqlaprovacao);

if (@pg_numrows($resnovo) > 0) $xtotal_aprovacao = pg_result($resaprovacao,0,total_aprovacao);



#RESOLVIDO#
$sqlresolvido = "SELECT count(*) AS total_resolvido
					FROM tbl_hd_chamado
					WHERE status ILIKE 'resolvido' ";
if($atendente_busca <> '') $sqlresolvido .= " AND atendente        = '$atendente_busca'";
if($fabrica_busca<>'')     $sql          .= " AND tbl_fabrica.nome = '$fabrica_busca'"  ;

$resresolvido = @pg_exec ($con,$sqlresolvido);

if (@pg_numrows($resresolvido) > 0) $xtotal_resolvido = pg_result($resresolvido,0,total_resolvido);


#EXECUCAO#
$sqlexecucao = "SELECT count(*) AS total_execucao
					FROM tbl_hd_chamado
					WHERE status ILIKE 'execução' ";
if($atendente_busca <> '') $sqlexecucao .= " AND atendente        = '$atendente_busca'";
if($fabrica_busca<>'')     $sql         .= " AND tbl_fabrica.nome = '$fabrica_busca'"  ;

$resexecucao = @pg_exec ($con,$sqlexecucao);

if (@pg_numrows($resexecucao) > 0) $xtotal_execucao = pg_result($resexecucao,0,total_execucao);



#CANCELADO#
$sqlcancelado = "SELECT count(*) AS total_cancelado
					FROM tbl_hd_chamado
					WHERE status ILIKE 'cancelado' ";
if($atendente_busca <> '') $sqlcancelado .= " AND atendente        = '$atendente_busca'";
if($fabrica_busca<>'')     $sql          .= " AND tbl_fabrica.nome = '$fabrica_busca'"  ;

$rescancelado = @pg_exec ($con,$sqlcancelado);

if (@pg_numrows($rescancelado) > 0) $xtotal_cancelado = pg_result($rescancelado,0,total_cancelado);


#TODOS#
$sqltodos = "SELECT count(*) AS total_todos
				FROM tbl_hd_chamado
				WHERE 1=1 ";

if($atendente_busca <> '') $sqltodos .= " AND atendente        = '$atendente_busca'";
if($fabrica_busca<>'')     $sql      .= " AND tbl_fabrica.nome = '$fabrica_busca'"  ;

$restodos = @pg_exec ($con,$sqltodos);

if (@pg_numrows($restodos) > 0) {
	$xtotal_todos         = pg_result($restodos,0,total_todos);
	$xtotal_aberto        = $xtotal_todos - $xtotal_cancelado - $xtotal_resolvido  - $xtotal_aprovacao;
}

###FIM ESTATITICAS###





###busca###

$sql = "SELECT
			hd_chamado              ,
			tbl_hd_chamado.admin    ,
			tbl_empregado.empregado ,
			tbl_admin.login         ,
			to_char (tbl_hd_chamado.data,'DD/MM HH24:MI') AS data,
			titulo                  ,
			status                  ,
			atendente               ,
			exigir_resposta         ,

		FROM tbl_hd_chamado
		JOIN tbl_empregado USING (empregado)";


if($valor_chamado <> ''){
	$sql .= " WHERE  tbl_hd_chamado.hd_chamado = '$valor_chamado'";
}else{
	$sql .= " WHERE  tbl_hd_chamado.fabrica_responsavel = $login_fabrica";

	if ($atendente_busca <> '' ){
		$sql .= " AND tbl_hd_chamado.atendente = $atendente_busca";
	}


	if ($status_busca=="p"){
		$sql .= " AND tbl_hd_chamado.status ILIKE 'Aprovação' ";
	}

	if ($status_busca=="a"){
		$sql .= " AND tbl_hd_chamado.status ILIKE 'Análise' ";
	}

	if ($status_busca=="r"){
		$sql .= " AND tbl_hd_chamado.status ILIKE 'Resolvido' ";
	}

	if ($status_busca=="e"){
		$sql .= " AND tbl_hd_chamado.status ILIKE 'Execução' ";
	}

	if ($status_busca=="c"){
		$sql .= " AND tbl_hd_chamado.status ILIKE 'Cancelado' ";
	}

	if ($status_busca=="n"){
		$sql .= " AND ((tbl_hd_chamado.status ILIKE 'Novo') OR (tbl_hd_chamado.status IS NULL))";
	}

	if ($status_busca==''){
		$sql .= " AND ((tbl_hd_chamado.status <> 'Cancelado' AND tbl_hd_chamado.status <> 'Resolvido')) ";
	}
}



$sql .= " ORDER BY tbl_hd_chamado.data DESC";

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


	echo "<FORM METHOD='GET' ACTION='$PHP_SELF'>";
	echo "<table width = '700' align = 'center' cellpadding='0' cellspacing='0' border='0'>";
	echo "<tr>";
	echo "	<td background='/assist/helpdesk/imagem/fundo_tabela_top_esquerdo_azul_claro.gif' rowspan='2'><img src='/assist/imagens/pixel.gif' width='9'></td>";
	echo "	<td background='/assist/helpdesk/imagem/fundo_tabela_top_centro_azul_claro.gif'   colspan='8' align = 'center' width='100%' style='font-family: arial ; color:#666666'>&nbsp;</td>";
	echo "	<td background='/assist/helpdesk/imagem/fundo_tabela_top_direito_azul_claro.gif'  rowspan='2'><img src='/assist/imagens/pixel.gif' width='9'></td>";
	echo "</tr>";
	echo "<tr bgcolor='#D9E8FF' style='font-family: arial ; color: #666666'>";
	echo "	<td colspan='9'><b><CENTER>Pesquisa Chamados</CENTER></b></td>";
	echo "</tr>";
	echo "<tr align='left'  height ='70'>";
	echo "	<td background='/assist/helpdesk/imagem/fundo_tabela_centro_esquerdo.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";

	echo "<td align='center'><INPUT TYPE=\"radio\" NAME=\"status\" value='n'";
	if ($status_busca=='n') echo "CHECKED";
	echo "><BR><font size=2>Novo<BR>(</font><font size=2 ";
	if($xtotal_novo>0)echo"color='ff0000'";echo"><b> $xtotal_novo  </B></font><font size=2>)</font></td>";

	echo "<td align='center' ><INPUT TYPE=\"radio\" NAME=\"status\" value='a'";
	if ($status_busca=='a') echo "CHECKED";
	echo "><BR><font size=2>Analise<BR>(</font><font size=2 ";
	if($xtotal_analise>0)echo "color='ff0000'";echo "><b> $xtotal_analise </B></font><font size=2>)</font></td>";

	echo "<td align='center'><INPUT TYPE=\"radio\" NAME=\"status\" value='e'";
	if ($status_busca=='e') echo "CHECKED";
	echo "><BR><font size=2>Execução<BR>(</font><font size=2 ";
	if($xtotal_execucao>0)echo "color='ff0000'";echo "><b> $xtotal_execucao </B></font><font size=2>)</font></td>";

	echo "<td align='center'><INPUT TYPE=\"radio\" NAME=\"status\" value=''";
	if ($status_busca=='') echo "CHECKED";
	echo "><BR><font size=2>Em Aberto<BR>(<b> $xtotal_aberto </B>)</font></td>";


	echo "<td>|<br>|</td>";


	echo "<td align='center'><INPUT TYPE=\"radio\" NAME=\"status\" value='r'";
	if ($status_busca=='r') echo "CHECKED";
	echo "><BR><font size=2>Resolvido<BR>(</font><font size=2><b> $xtotal_resolvido </B></font><font size=2>)</font></td>";

	echo "<td align='center' ><INPUT TYPE=\"radio\" NAME=\"status\" value='p'";
	if ($status_busca=='p') echo "CHECKED";
	echo "><BR><font size=2>Aprovação<BR>(</font><font size=2><b> $xtotal_aprovacao </B></font><font size=2>)</font></center></td>";

	echo "	<td align='center'><INPUT TYPE=\"radio\" NAME=\"status\" value='c'";
	if ($status_busca=='c') echo "CHECKED";
	echo "><BR><center><font size=2>Cancelado<BR>(<b> $xtotal_cancelado </B>)</font></center></td>";



	echo "	<td background='/assist/helpdesk/imagem/fundo_tabela_centro_direito.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";
	echo "</tr>";

	echo "<tr bgcolor='#D9E8FF' style='font-family: verdana ; color: #666666'>";
	echo "	<td background='/assist/helpdesk/imagem/fundo_tabela_centro_esquerdo.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";
	echo "	<td colspan='3'><CENTER><B>Atendente</B></CENTER></td>";
	echo "	<td></td>";
	echo "	<td colspan='4'><CENTER><B>Fábrica</B></CENTER></td>";
	echo "	<td background='/assist/helpdesk/imagem/fundo_tabela_centro_direito.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";
	echo "</tr>";

	echo "<tr>";
	echo "	<td background='/assist/helpdesk/imagem/fundo_tabela_centro_esquerdo.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";
	echo "	<td td colspan='3'>";


		##### INÍCIO FAMÍLIA #####
	$sqlatendente = "SELECT empregado,
				nome
			FROM tbl_empregado
			JOIN tbl_posto ON tbl_posto.posto = tbl_empregado.posto_empregado
			WHERE tbl_empregado.empresa = $login_empresa;
";

		$resatendente = pg_exec ($con,$sqlatendente);

		$atendente_busca = $_POST['atendente_busca'];

		if (pg_numrows($resatendente) > 0) {
			echo "<BR><center><select class='frm' style='width: 200px;' name='atendente_busca'>\n";
			echo "<option value='' ";
			if (strlen ($atendente_busca) == 0 ) echo " SELECTED ";
			echo ">- TODOS -</option>\n";

			for ($x = 0 ; $x < pg_numrows($resatendente) ; $x++){
				$n_empregado = trim(pg_result($resatendente,$x,empregado));
				$nome_atendente  = trim(pg_result($resatendente,$x,nome));

				echo "<option value='$n_admin'";
				if ($login_empregado == $n_empregado AND strlen ($atendente_busca) > 0 ) echo " SELECTED ";
				if ($atendente_busca == $n_empregado ) echo " SELECTED ";
				echo "> $nome_atendente</option>\n";
			}

			echo "</select></center><BR>";
		}
		##### FIM FAMÍLIA #####




	echo "</td>";
	echo "	<td></td>";

	echo "	<td colspan='4'><CENTER>";




echo "</CENTER></td>";




	echo "	<td background='/assist/helpdesk/imagem/fundo_tabela_centro_direito.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";
	echo "</tr>";

	echo "<tr bgcolor='#D9E8FF' style='font-family: arial ; color: #666666'>";
	echo "	<td background='/assist/helpdesk/imagem/fundo_tabela_centro_esquerdo.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";
	echo "	<td colspan='8'><CENTER><B>Busca pelo Número do Chamado</B></CENTER></td>";
	echo "	<td background='/assist/helpdesk/imagem/fundo_tabela_centro_direito.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";
	echo "</tr>";

		echo "<tr>";
	echo "	<td background='/assist/helpdesk/imagem/fundo_tabela_centro_esquerdo.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";
	echo "	<td td colspan='2'>&nbsp;</td>";
	echo "<td align='center' colspan='4'><BR><font size=2>Número Chamado:</font> &nbsp; <input type='text' size='5' maxlength='5' name='valor_chamado' value=''> <BR><BR>";

	echo "</td>";
	echo "	<td td colspan='2'>&nbsp;</td>";
	echo "	<td background='/assist/helpdesk/imagem/fundo_tabela_centro_direito.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";
	echo "</tr>";






#botao submit

	echo "<tr>";
	echo "	<td background='/assist/helpdesk/imagem/fundo_tabela_centro_esquerdo.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";
	echo "<td colspan='8' nowrap><CENTER><INPUT TYPE=\"submit\" value=\"Pesquisar\"></CENTER></td>";
//	echo "	<td></td>";
	echo "	<td background='/assist/helpdesk/imagem/fundo_tabela_centro_direito.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";
	echo "</tr>";

#===========================

	echo "<tr>";
	echo "	<td background='/assist/helpdesk/imagem/fundo_tabela_baixo_esquerdo.gif'><img src='/assist/imagens/pixel.gif' width='9'></td>";
	echo "	<td background='/assist/helpdesk/imagem/fundo_tabela_baixo_centro.gif' colspan='8' align = 'center' width='100%'></td>";
	echo "	<td background='/assist/helpdesk/imagem/fundo_tabela_baixo_direito.gif'><img src='/assist/imagens/pixel.gif' width='9'></td>";
	echo "</tr>";
	echo "</table>";
	echo "</FORM>";


	/*--===============================TABELA DE CHAMADOS========================--*/

	echo "<table width='630' align='center' cellpadding='0' cellspacing='0' border='0'>";
	echo "<tr style='font-family: arial ; color: #666666' align='center'>";

	echo "<td width='50%' nowrap align='left'>";
	echo "<img src='/assist/admin/imagens_admin/status_vermelho.gif' valign='absmiddle'> Aguardando resposta do cliente";
	echo "</td>";

	echo "<td width='50%' nowrap align='left'>";
	echo "<img src='/assist/admin/imagens_admin/status_azul.gif' valign='absmiddle'> Pendente Telecontrol";
	echo "</td>";

	echo "</tr>";

	echo "<tr style='font-family: arial ; color: #666666' align='center'>";

	echo "<td width='50%' nowrap align='left'>";
	echo "<img src='/assist/admin/imagens_admin/status_amarelo.gif' valign='absmiddle'> Aguardando aprovação";
	echo "</td>";

	echo "<td width='50%' nowrap align='left'>";
	echo "<img src='/assist/admin/imagens_admin/status_verde.gif' valign='absmiddle'> Resolvido";
	echo "</td>";


	echo "</tr>";

	echo "</table>";
	echo "<br>";





	echo "<table width = '700' align = 'center' cellpadding='0' cellspacing='0' border='0'>";
	echo "<tr>";
	echo "	<td background='/assist/helpdesk/imagem/fundo_tabela_top_esquerdo_azul_claro.gif' rowspan='2'><img src='/assist/imagens/pixel.gif' width='9'></td>";
	echo "	<td background='/assist/helpdesk/imagem/fundo_tabela_top_centro_azul_claro.gif'   colspan='8' align = 'center' width='100%' style='font-family: arial ; color:#666666'><b>Lista de Chamados</b></td>";
	echo "	<td background='/assist/helpdesk/imagem/fundo_tabela_top_direito_azul_claro.gif'  rowspan='2'><img src='/assist/imagens/pixel.gif' width='9'></td>";
	echo "</tr>";
	echo "<tr bgcolor='#D9E8FF' style='font-family: arial ; color: #666666'>";
	echo "	<td ><strong> </strong></td>";
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
	$max_res   = 100;				// máximo de resultados à serem exibidos por tela ou pagina
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
		$exigir_resposta            = pg_result($res,$i,exigir_resposta);
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

		for($r = 0 ; $r < count($chamado_interno); $r++){
			if($hd_chamado == $chamado_interno[$r])$interno="<img src='../admin/imagens_admin/star_on.gif' title='Contém chamado interno' border='0'>";
		}

		echo "<tr  style='font-family: arial ; font-size: 12px ; cursor: hand; ' height='25' bgcolor='$cor'  onmouseover=\"this.bgColor='#D9E8FF'\" onmouseout=\"this.bgColor='$cor'\" >";
		if($status == "Novo") {echo "<a href='adm_atendimento.php?hd_chamado=$hd_chamado'>";}
		else echo "<a href='adm_chamado_detalhe.php?hd_chamado=$hd_chamado'>";
		echo "<td background='/assist/helpdesk/imagem/fundo_tabela_centro_esquerdo.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";

		echo "<td>";
		echo "<td nowrap>";
		if($status =="Análise" AND $exigir_resposta <> "t"){
			echo "<img src='/assist/admin/imagens_admin/status_azul.gif' align='absmiddle'> ";
		}elseif($exigir_resposta == "t" AND $status<>'Cancelado'OR ($status == "Resolvido" AND strlen($resolvido)==0 )) {
			echo "<img src='/assist/admin/imagens_admin/status_vermelho.gif' align='absmiddle'> ";
		}elseif (($status == "Resolvido" AND strlen($resolvido)>0) OR $status == "Cancelado") {
				echo "<img src='/assist/admin/imagens_admin/status_verde.gif' align='absmiddle'> ";
			}elseif ($status == "Aprovação") {
				echo "<img src='/assist/admin/imagens_admin/status_amarelo.gif' align='absmiddle' > ";
			}else{
				echo "<img src='/assist/admin/imagens_admin/status_azul.gif' align='absmiddle'> ";
			}
		echo " $hd_chamado&nbsp;</td>";

		echo "<td>";

		if($status == "Novo" OR $login_admin==$suporte) {echo "<a href='adm_atendimento.php?hd_chamado=$hd_chamado'>";}
		else echo "<a href='adm_chamado_detalhe.php?hd_chamado=$hd_chamado'>";

		echo "<acronym title='$titulo'>$interno ";
		echo substr($titulo,0,20)."...</acronym></a></td>";

		if (($status != 'Resolvido') and ($status != 'Cancelado')) {
			echo "<td nowrap><font color=#FF0000><B>$status </B></font></td>";
		}else{
			echo "<td nowrap>$status </td>";
		}
		echo "<td nowrap><font size='1'>&nbsp;$data &nbsp;</font></td>";
		echo "<td><font size='1'>";
		if (strlen ($nome_completo) > 0) {
			$nome_completo2 = explode (' ',$nome_completo);
			$nome_completo2 = $nome_completo2[0];
			echo $nome_completo2;

		}else{
			echo $login;
		}
		echo "</font></td>";
		echo "<td><font size='1'>&nbsp;$fabrica_nome&nbsp;</font></td>";
		echo "<td><font size='1'>&nbsp;$xxatendente[0]&nbsp;</font></td>";

		echo "<td background='/assist/helpdesk/imagem/fundo_tabela_centro_direito.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";
		echo "</a></tr>";
		$interno='';
	}

//fim imprime chamados

	echo "<tr>";
	echo "<td background='/assist/helpdesk/imagem/fundo_tabela_baixo_esquerdo.gif'><img src='/assist/imagens/pixel.gif' width='9'></td>";
	echo "<td background='/assist/helpdesk/imagem/fundo_tabela_baixo_centro.gif' colspan='8' align = 'center' width='100%'></td>";
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

