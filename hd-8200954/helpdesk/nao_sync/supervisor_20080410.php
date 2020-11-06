<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';
 
//VERIFICA SE O USUÁRIO É SUPERVISOR
$sql="  SELECT * FROM tbl_admin 
		WHERE admin=$login_admin 
		AND help_desk_supervisor='t'";

$res = @pg_exec ($con,$sql);

if (@pg_numrows($res) > 0) {
	$supervisor='t';
	$nome_completo=pg_result($res,0,nome_completo);
}
//PEGA O NOME DA FABRICA
$sql = "SELECT   * 
		FROM     tbl_fabrica
		WHERE    fabrica=$login_fabrica
		ORDER BY nome";
$res = pg_exec ($con,$sql);
$nome      = trim(pg_result($res,0,nome));

$menu_cor_fundo="EEEEEE";
$menu_cor_linha="BBBBBB";


if($_GET['conteudo'])  $conteudo  = $_GET['conteudo']; 
//echo $conteudo."<br>".$ajuda; 


//FIM DO SELECT DA TABELA ESTATISTICAS DE CHAMADAS---------------------------------
$hd_chamado = $_GET['hd_chamado'];
$aprova = $_GET['aprova'];

$data       = date("d/m/Y  h:i");

if($aprova=='sim'){

	$res = @pg_exec($con,"BEGIN TRANSACTION");

	$sql= " SELECT TO_CHAR(data,'DD/MM HH24:MI') AS data
			FROM tbl_hd_chamado
			WHERE fabrica = $login_fabrica
			AND hd_chamado = $hd_chamado";
	$res = pg_exec ($con,$sql);
	$msg_erro .= pg_errormessage($con);
	if (pg_numrows($res) > 0) {
		$data_abertura = pg_result($res,0,data);
	}

	$sql = "UPDATE tbl_hd_chamado 
			SET exigir_resposta = 'f', status = 'Novo', data = CURRENT_TIMESTAMP
			WHERE hd_chamado = $hd_chamado";
	$res = pg_exec ($con,$sql);
	$msg_erro .= pg_errormessage($con);

	$sql = "INSERT into tbl_hd_chamado_item (
				hd_chamado,
				comentario,
				admin
				) VALUES (
				$hd_chamado,
				'MENSAGEM AUTOMÁTICA - ESTE CHAMADO FOI ABERTO EM $data_abertura E FOI APROVADO EM $data PELO USUÁRIO $nome_completo',
				$login_admin
				)";
	$res = pg_exec ($con,$sql);
	$msg_erro .= pg_errormessage($con);

	if(strlen($msg_erro) > 0){
		$res = @pg_exec ($con,"ROLLBACK TRANSACTION");
		$msg_erro .= 'Houve um erro na aprovação do Chamado.';
	}else{
		$res = @pg_exec($con,"COMMIT");
	}
}
if($cancela=='sim'){
	$sql = "UPDATE tbl_hd_chamado SET status = 'Cancelado' WHERE hd_chamado = $hd_chamado";
	$res = pg_exec ($con,$sql);
	if($login_fabrica==6){
	$sql = "INSERT into tbl_hd_chamado_item (
				hd_chamado,
				comentario,
				admin
				) VALUES (
				$hd_chamado,
				'MENSAGEM AUTOMÁTICA-ESTE CHAMADO FOI CANCELADO EM $data PELO USUÁRIO $nome_completo',
				$login_admin
				)";
	$res = pg_exec ($con,$sql);
	}
}
//echo $sql;
?>

<html>
<head>
<title>Telecontrol - Help Desk</title>
<link type="text/css" rel="stylesheet" href="css/css.css">
</head>
<body>
<?
include "menu.php";
?>
<table width="700" align="center" bgcolor="#FFFFFF" border='0'>
	<tr>
		<td >
			<table width='100%' border='0'>
				<tr>
					<td valign='top' class='Conteudo'> 
						<img src="imagem/help.png" width="36" height="36" border='0' align='absmiddle'> SUPERVISOR
					</td>
					<td valign='middle'>
<?
	echo "<table width='400' align='center' cellpadding='0' cellspacing='0' border='0' bgcolor='#FFFFFF'>";
	echo "<tr class='Legenda' align='center' valign='middle'>";

	echo "<td width='50%' nowrap align='left'>";
	echo "<img src='/assist/admin/imagens_admin/status_vermelho.gif' valign='absmiddle'> Aguardando sua resposta";
	echo "</td>";

	echo "<td width='50%' nowrap align='left'>";
	echo "<img src='/assist/admin/imagens_admin/status_azul.gif' valign='absmiddle'> Pendente Telecontrol";
	echo "</td>";

	echo "</tr>";

	
	echo "<tr class='Legenda' align='center'>";

	echo "<td width='50%' nowrap align='left'>";
	echo "<img src='/assist/admin/imagens_admin/status_amarelo.gif' valign='absmiddle'> Aguardando aprovação";
	echo "</td>";

	echo "<td width='50%' nowrap align='left'>";
	echo "<img src='/assist/admin/imagens_admin/status_verde.gif' valign='absmiddle'> Resolvido";
	echo "</td>";


	echo "</tr>";
	
?>

					</td>
				</tr>
			</table>
		</td>
	</tr>
	<tr>
		<td colspan="2" bgcolor="<?=$menu_cor_linha?>" width="1" height="1"></td>
	</tr>
	<tr>
		<td colspan="2" class="Conteudo" align="center"><b>Somente administradores podem aprovar os chamados</font></td>
	</tr>
	

</table>

<!-- ====================INICIO DA TABELA DE ESTATISTICAS DE CHAMADAS=========================================   -->
<?
$sql1 = "SELECT count (*) AS total_novo
	FROM       tbl_hd_chamado
	JOIN tbl_admin ON tbl_hd_chamado.admin = tbl_admin.admin
	JOIN tbl_fabrica ON tbl_admin.fabrica = tbl_fabrica.fabrica 
	WHERE tbl_fabrica.nome = '$nome'
	AND      status ILIKE 'novo'";
//echo $sql1;


$res1 = @pg_exec ($con,$sql1);

if (@pg_numrows($res1) > 0) {//PEGA OS DADOS DE QUEM ESTÁ ABRINDO O CHAMADO
	$total_novo           = pg_result($res1,0,total_novo);
	}


$sql2 = "SELECT	 COUNT (*) AS total_analise
	FROM       tbl_hd_chamado
	JOIN tbl_admin ON tbl_hd_chamado.admin = tbl_admin.admin
	JOIN tbl_fabrica ON tbl_admin.fabrica = tbl_fabrica.fabrica 
	WHERE tbl_fabrica.nome = '$nome'
	AND      status ILIKE 'análise'";
$res2 = @pg_exec ($con,$sql2);

if (@pg_numrows($res2) > 0) {//PEGA OS DADOS DE QUEM ESTÁ ABRINDO O CHAMADO
	$total_analise           = pg_result($res2,0,total_analise);
	}

$sql3 = "SELECT	 COUNT (*) AS total_aprovacao
	FROM       tbl_hd_chamado
	JOIN tbl_admin ON tbl_hd_chamado.admin = tbl_admin.admin
	JOIN tbl_fabrica ON tbl_admin.fabrica = tbl_fabrica.fabrica 
	WHERE tbl_fabrica.nome = '$nome'
	AND      status ILIKE 'aprovação'";
$res3 = @pg_exec ($con,$sql3);

if (@pg_numrows($res3) > 0) {//PEGA OS DADOS DE QUEM ESTÁ ABRINDO O CHAMADO
	$total_aprovacao           = pg_result($res3,0,total_aprovacao);
	}

$sql4 = "SELECT	 COUNT (*) AS total_resolvido
	FROM       tbl_hd_chamado
	JOIN tbl_admin ON tbl_hd_chamado.admin = tbl_admin.admin
	JOIN tbl_fabrica ON tbl_admin.fabrica = tbl_fabrica.fabrica 
	WHERE tbl_fabrica.nome = '$nome'
	AND      status ILIKE 'resolvido'";

$res4 = @pg_exec ($con,$sql4);

if (@pg_numrows($res4) > 0) {//PEGA OS DADOS DE QUEM ESTÁ ABRINDO O CHAMADO
	$total_resolvido           = pg_result($res4,0,total_resolvido);
	}


$sql5 = "SELECT	 COUNT (*) AS total_cancelado
	FROM       tbl_hd_chamado
	JOIN tbl_admin ON tbl_hd_chamado.admin = tbl_admin.admin
	JOIN tbl_fabrica ON tbl_admin.fabrica = tbl_fabrica.fabrica 
	WHERE tbl_fabrica.nome = '$nome'
	AND      status ILIKE 'cancelado'";

$res5 = @pg_exec ($con,$sql5);

if (@pg_numrows($res5) > 0) {//PEGA OS DADOS DE QUEM ESTÁ ABRINDO O CHAMADO
	$total_cancelado           = pg_result($res5,0,total_cancelado);
	}


$sql6 = "SELECT	 COUNT (*) AS total_Execução
	FROM       tbl_hd_chamado
	JOIN tbl_admin ON tbl_hd_chamado.admin = tbl_admin.admin
	JOIN tbl_fabrica ON tbl_admin.fabrica = tbl_fabrica.fabrica 
	WHERE tbl_fabrica.nome = '$nome'
	AND      status ILIKE 'Execução'";;

$res6 = @pg_exec ($con,$sql6);

if (@pg_numrows($res6) > 0) {//PEGA OS DADOS DE QUEM ESTÁ ABRINDO O CHAMADO
	$total_Execução           = pg_result($res6,0,total_Execução);
	}


$sql = "SELECT 
			hd_chamado ,
			tbl_hd_chamado.admin ,
			tbl_admin.nome_completo ,
			tbl_admin.login ,
			to_char (tbl_hd_chamado.data,'DD/MM HH24:MI') AS data,
			titulo ,
			status ,
			atendente ,
			tbl_fabrica.nome AS fabrica_nome
		FROM tbl_hd_chamado
		JOIN tbl_admin ON tbl_hd_chamado.admin = tbl_admin.admin
		JOIN tbl_fabrica ON tbl_admin.fabrica = tbl_fabrica.fabrica 
		WHERE tbl_hd_chamado.fabrica_responsavel = 10 
		AND tbl_fabrica.nome = '$nome'
		AND (tbl_hd_chamado.status ='Aprovação')
		ORDER BY tbl_hd_chamado.status,tbl_hd_chamado.data DESC";
$res = pg_exec ($con,$sql);
   
		
if (@pg_numrows($res) > 0) {
	echo "<table width = '700' align = 'center' cellpadding='0' cellspacing='0' border='0'>";
	echo "<tr>";
	echo "	<td background='/assist/helpdesk/imagem/fundo_tabela_top_esquerdo_azul_claro.gif' rowspan='2'><img src='/assist/imagens/pixel.gif' width='9'></td>";
	echo "	<td background='/assist/helpdesk/imagem/fundo_tabela_top_centro_azul_claro.gif'   colspan='6' align = 'center' width='100%' style='font-family: arial ; color:#666666'><CENTER>Chamados para serem aprovados</CENTER></td>";
	echo "	<td background='/assist/helpdesk/imagem/fundo_tabela_top_direito_azul_claro.gif'  rowspan='2'><img src='/assist/imagens/pixel.gif' width='9'></td>";
	echo "</tr>";
	echo "<tr bgcolor='#D9E8FF' style='font-family: arial ; color: #666666'>";
	echo "	<td>N°</td>";
	echo "	<td>Título</td>";
	echo "	<td>Status</td>";
	echo "	<td>Data</td>";
	echo "	<td>Solicitante</td>";
	echo "	<td>Ação</td>";
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

		echo "<tr  style='font-family: arial ; font-size: 12px ; cursor: hand; ' height='25' bgcolor='$cor'  onmouseover=\"this.bgColor='#D9E8FF'\" onmouseout=\"this.bgColor='$cor'\" ><a href='chamado_detalhe.php?hd_chamado=$hd_chamado'>";
		echo "<td background='/assist/helpdesk/imagem/fundo_tabela_centro_esquerdo.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";

		echo "<td><img src='/assist/admin/imagens_admin/status_amarelo.gif' align='absmiddle'> $hd_chamado&nbsp;</td>";
		echo "<td><a href='chamado_detalhe.php?hd_chamado=$hd_chamado'>$titulo</a></td>";
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
		echo "<td>";
		if ($supervisor=='t' AND $status=='Aprovação'){
			echo "<a href='$PHP_SELF?hd_chamado=$hd_chamado&aprova=sim'><img src='imagem/btn_ok.gif'border='0' align='absmiddle'>APROVA</a><br><a href='$PHP_SELF?hd_chamado=$hd_chamado&cancela=sim'><img src='imagem/btn_deletar.gif'border='0' align='absmiddle'>CANCELA";
		}
		echo"</td>";
		
		echo "<td background='/assist/helpdesk/imagem/fundo_tabela_centro_direito.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";
		echo "</a></tr>"; 
		
	}
	
//fim imprime chamados
	
	echo "<tr>";
	echo "<td background='/assist/helpdesk/imagem/fundo_tabela_baixo_esquerdo.gif'><img src='/assist/imagens/pixel.gif' width='9'></td>";
	echo "<td background='/assist/helpdesk/imagem/fundo_tabela_baixo_centro.gif' colspan='6' align = 'center' width='100%'></td>";
	echo "<td background='/assist/helpdesk/imagem/fundo_tabela_baixo_direito.gif'><img src='/assist/imagens/pixel.gif' width='9'></td>";
	echo "</tr>";

	echo "</table>"; 
}

echo "</td>";
echo "</tr>";

echo "</table>";




$sql = "SELECT 
			hd_chamado ,
			tbl_hd_chamado.admin ,
			tbl_admin.nome_completo ,
			tbl_admin.login ,
			to_char (tbl_hd_chamado.data,'DD/MM HH24:MI') AS data,
			titulo ,
			status ,
			atendente ,
			tbl_fabrica.nome AS fabrica_nome
		FROM tbl_hd_chamado
		JOIN tbl_admin ON tbl_hd_chamado.admin = tbl_admin.admin
		JOIN tbl_fabrica ON tbl_admin.fabrica = tbl_fabrica.fabrica 
		WHERE tbl_hd_chamado.fabrica_responsavel = 10 
		AND tbl_fabrica.nome = '$nome'
		AND (tbl_hd_chamado.status <>'Resolvido')
		AND (tbl_hd_chamado.status <>'Aprovação')
		ORDER BY tbl_hd_chamado.data DESC";
$res = pg_exec ($con,$sql);

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
	echo "<table width = '700' align = 'center' cellpadding='0' cellspacing='0' border='0'>";
	echo "<tr>";
	echo "	<td background='/assist/helpdesk/imagem/fundo_tabela_top_esquerdo_azul_claro.gif' rowspan='2'><img src='/assist/imagens/pixel.gif' width='9'></td>";
	echo "	<td background='/assist/helpdesk/imagem/fundo_tabela_top_centro_azul_claro.gif'   colspan='5' align = 'center' width='100%' style='font-family: arial ; color:#666666'>&nbsp;</td>";
	echo "	<td background='/assist/helpdesk/imagem/fundo_tabela_top_direito_azul_claro.gif'  rowspan='2'><img src='/assist/imagens/pixel.gif' width='9'></td>";
	echo "</tr>";
	echo "<tr bgcolor='#D9E8FF' style='font-family: arial ; color: #666666'>";
	echo "	<td colspan='5'><b><CENTER>Chamados para Acompanhamento</CENTER></b></td>";
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

		echo "<tr  style='font-family: arial ; font-size: 12px ; cursor: hand; ' height='25' bgcolor='$cor'  onmouseover=\"this.bgColor='#D9E8FF'\" onmouseout=\"this.bgColor='$cor'\" ><a href='chamado_detalhe.php?hd_chamado=$hd_chamado'>";
		echo "<td background='/assist/helpdesk/imagem/fundo_tabela_centro_esquerdo.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";

		echo "<td>";
		if($status =="Análise" AND $exigir_resposta <> "t"){
			echo "<img src='/assist/admin/imagens_admin/status_azul.gif' align='absmiddle'> ";
		}elseif($exigir_resposta == "t" AND $status<>'Cancelado'OR ($status == "Resolvido" AND strlen($resolvido)==0 )) {
			echo "<img src='/assist/admin/imagens_admin/status_vermelho.gif' align='absmiddle'> ";
		}elseif (($status == "Resolvido" AND strlen($resolvido)>0) OR $status == "Cancelado") {
				echo "<img src='/assist/admin/imagens_admin/status_verde.gif' align='absmiddle'> ";
			}elseif ($status == "Aprovação") {
				echo "<img src='/assist/admin/imagens_admin/status_amarelo.gif' align='absmiddle'> ";
			}else{
				echo "<img src='/assist/admin/imagens_admin/status_azul.gif' align='absmiddle'> ";
			}
		echo "$hd_chamado&nbsp;</td>";
		echo "<td><a href='chamado_detalhe.php?hd_chamado=$hd_chamado'>$titulo</a></td>";
		if (($status != 'Resolvido') and ($status != 'Cancelado')) {
			echo "<td nowrap><font color=#FF0000><B>$status </B></font></td>";
		}else{
			echo "<td nowrap>$status </td>";
		}
		echo "<td nowrap>&nbsp;$data &nbsp;</td>";
		echo "<td class='Conteudo'>";
		if (strlen ($nome_completo) > 0) {
			echo $nome_completo;
			
		}else{
			echo $login;
		}
		echo "</td>";

		
		echo "<td background='/assist/helpdesk/imagem/fundo_tabela_centro_direito.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";
		echo "</a></tr>"; 
		
	}
	
//fim imprime chamados
	
	echo "<tr>";
	echo "<td background='/assist/helpdesk/imagem/fundo_tabela_baixo_esquerdo.gif'><img src='/assist/imagens/pixel.gif' width='9'></td>";
	echo "<td background='/assist/helpdesk/imagem/fundo_tabela_baixo_centro.gif' colspan='5' align = 'center' width='100%'></td>";
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

?>

<table width = '700' align = 'center' cellpadding='0' cellspacing='0'>
<tr>
	<td background='/assist/helpdesk/imagem/fundo_tabela_top_esquerdo_azul_claro.gif' rowspan='2'><img src='/assist/imagens/pixel.gif' width='9'></td>
	<td background='/assist/helpdesk/imagem/fundo_tabela_top_centro_azul_claro.gif' colspan='8' align = 'center' width='100%' style='font-family: arial ; color:#666666'><b>Estatística de Chamadas</b></td>
	<td background='/assist/helpdesk/imagem/fundo_tabela_top_direito_azul_claro.gif' rowspan='2'><img src='/assist/imagens/pixel.gif' width='9'></td>
</tr>
<tr bgcolor='#D9E8FF' style='font-family: arial ; color: #666666'>
	<td nowrap colspan="9"></td>
	<td nowrap></td>
</tr>
<tr style='font-family: arial ; font-size: 12px ; ' height='25'>
	<td background='/assist/helpdesk/imagem/fundo_tabela_centro_esquerdo.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>
	<td nowrap ><CENTER>Novo: <B><? echo $total_novo ?></B></CENTER></td>
	<td>&nbsp;</td>
	<td nowrap ><CENTER>Análise: <B><? echo $total_analise ?></B></CENTER></td>
	<td>&nbsp;</td>
	<td nowrap><CENTER>Aprovação: <B><? echo $total_aprovacao ?></B></CENTER></td>
	<td nowrap><CENTER>Resolvido: <B><? echo $total_resolvido ?></B></CENTER></td>
	<td nowrap><CENTER>Execução: <B><? echo $total_Execução ?></B></CENTER></td>
	<td nowrap><CENTER>Cancelado: <B><? echo $total_cancelado ?></B></CENTER></td>
	<td background='/assist/helpdesk/imagem/fundo_tabela_centro_direito.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>
</tr>
<tr style='font-family: arial ; font-size: 12px ; ' height='25'>
	<td background='/assist/helpdesk/imagem/fundo_tabela_centro_esquerdo.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>
	<td nowrap colspan="4"><a href='chamado_detalhe.php'><img src="imagem/01.jpg" width="32" height="32"border='0'><B>INSERIR CHAMADO</B></a></td>
	<td nowrap align="right" colspan="4"><a href='chamado_lista.php'><img src="imagem/01.jpg" width="32" height="32"border='0'><B>LISTAR CHAMADO</B></a></td>
	<td background='/assist/helpdesk/imagem/fundo_tabela_centro_direito.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>
</TR>
<tr>
	<td background='/assist/helpdesk/imagem/fundo_tabela_baixo_esquerdo.gif'><img src='/assist/imagens/pixel.gif' width='9'></td>
	<td background='/assist/helpdesk/imagem/fundo_tabela_baixo_centro.gif' colspan='8' align = 'center' width='100%'></td>
	<td background='/assist/helpdesk/imagem/fundo_tabela_baixo_direito.gif'><img src='/assist/imagens/pixel.gif' width='9'></td>
</tr>
</table>

<!-- ====================FIM DA TABELA DE ESTATISTICAS DE CHAMADAS=========================================   -->

<? include "rodape.php" ?>
</body>
</html>