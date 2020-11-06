<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';
include '../admin/funcoes.php';

$suporte = 432;

if($login_fabrica<>10) header ("Location: index.php");
//if($login_admin<>$suporte)  header ("Location: adm_atendimento_lista.php");

$TITULO = "Lista de Chamados";

include "menu.php";
?>
<meta http-equiv="refresh" content="300">
<script type="text/javascript" charset="utf-8">
	$(function(){
		$("#data_inicio").maskedinput("99/99/9999");
		$("#data_fim").maskedinput("99/99/9999");
	});
</script>
<link rel="stylesheet" href="js/blue/style.css" type="text/css" id="" media="print, projection, screen" />

<script type="text/javascript" src="js/jquery.tablesorter.pack.js"></script> 
<script>
$(document).ready(function(){
	$.tablesorter.defaults.widgets = ['zebra'];
	$("#relatorio").tablesorter();

});


 $(document).ready(function(){
   $(".relatorio tr").mouseover(function(){$(this).addClass("over");}).mouseout(function(){$(this).removeClass("over");});
   $(".relatorio tr:even").addClass("alt");
   });
   
</script>
<style>
	table.relatorio {
		border-collapse: collapse;
		width: 650px;
		font-size: 1.1em;
	}

	table.relatorio th {
		background: #3e83c9;
		color: #fff;
		font-weight: bold;
		padding: 2px 11px;
		text-align: left;
		border-right: 1px solid #fff;
		line-height: 1.2;
	}

	table.relatorio td {
		padding: 1px 11px;
		border-bottom: 1px solid #95bce2;
	}

/*
	table.sample td * {
		padding: 1px 11px;
	}
*/
	table.relatorio tr.alt td {
		background: #ecf6fc;
	}

	table.relatorio tr.over td {
		background: #bcd4ec;
	}
	table.relatorio tr.clicado td {
		background: #FF9933;
	}
	table.relatorio tr.sem_defeito td {
		background: #FFCC66;
	}
	table.relatorio tr.mais_30 td {
		background: #FF0000;
	}
	table.relatorio tr.erro_post td {
		background: #99FFFF;
	}
	
	</style>
<?
$sql="  SELECT * FROM tbl_hd_chamado
	WHERE (atendente = 399) 
	AND   (status ILIKE 'novo' OR status ILIKE '$status' OR status IS NULL)";

$res = pg_exec ($con,$sql);
$chamados_nao_atendidos = pg_numrows($res);
if (@pg_numrows($res) > 0) $msg = 'Existem '.$chamados_nao_atendidos.' chamada(s) não atendida(s)';

$status_busca    = trim($_POST['status']);
$atendente_busca = trim($_POST['atendente_busca']);
$autor_busca     = trim($_POST['autor_busca']);
$valor_chamado   = trim($_POST['valor_chamado']);
$fabrica_busca   = trim($_POST['fabrica_busca']);
$data_inicio     = trim($_POST['data_inicio']);
$data_fim        = trim($_POST['data_fim']);


if (strlen($status_busca)    == 0) $status_busca    = trim($_GET['status']);
if (strlen($atendente_busca) == 0) $atendente_busca = trim($_GET['atendente_busca']);
if (strlen($autor_busca)     == 0) $autor_busca     = trim($_GET['autor_busca']);
if (strlen($valor_chamado)   == 0) $valor_chamado   = trim($_GET['valor_chamado']);
if (strlen($data_inicio)   == 0)   $data_inicio     = trim($_GET['data_inicio']);
if (strlen($data_fim)   == 0)      $data_fim        = trim($_GET['data_fim']);
if (strlen($fabrica_busca)   == 0) $fabrica_busca   = trim($_GET['fabrica_busca']);


###INICIO ESTATITICAS###
if($atendente_busca <> '') {
	$cond1 = " AND tbl_hd_chamado.atendente        = '$atendente_busca' ";
}
if($autor_busca <> '') {
	$cond3 = " AND tbl_hd_chamado.admin            = $autor_busca ";
}
//if($fabrica_busca<>'')     $cond2 = " AND tbl_fabrica.nome = '$fabrica_busca' "  ;

$sql = "SELECT (
		SELECT count(*) AS total_novo
		FROM tbl_hd_chamado
		JOIN tbl_admin ON tbl_hd_chamado.admin = tbl_admin.admin
		JOIN tbl_fabrica ON tbl_hd_chamado.fabrica = tbl_fabrica.fabrica 
		LEFT JOIN tbl_tipo_chamado on tbl_tipo_chamado.tipo_chamado = tbl_hd_chamado.tipo_chamado
		WHERE (status ILIKE 'novo' OR status ILIKE '$status'  OR status IS NULL) 
		AND   fabrica_responsavel = $login_fabrica 
		$cond1
		$cond2
		$cond3
	) AS total_novo,
	(
		SELECT count(*) AS total_analise
		FROM tbl_hd_chamado
		JOIN tbl_admin ON tbl_hd_chamado.admin = tbl_admin.admin
		JOIN tbl_fabrica ON tbl_hd_chamado.fabrica = tbl_fabrica.fabrica 
		LEFT JOIN tbl_tipo_chamado on tbl_tipo_chamado.tipo_chamado = tbl_hd_chamado.tipo_chamado
		WHERE status ILIKE 'análise'
		AND   fabrica_responsavel = $login_fabrica 
		$cond1
		$cond2
		$cond3
	) AS total_analise,
	(
		SELECT count(*) AS total_aprovacao
		FROM tbl_hd_chamado
		JOIN tbl_admin ON tbl_hd_chamado.admin = tbl_admin.admin
		JOIN tbl_fabrica ON tbl_hd_chamado.fabrica = tbl_fabrica.fabrica 
		LEFT JOIN tbl_tipo_chamado on tbl_tipo_chamado.tipo_chamado = tbl_hd_chamado.tipo_chamado
		WHERE status ILIKE 'aprovação' 
		AND   fabrica_responsavel = $login_fabrica 
		$cond1
		$cond2
		$cond3
	) AS total_aprovacao,
	(
		SELECT count(*) AS total_resolvido
		FROM tbl_hd_chamado
		JOIN tbl_admin ON tbl_hd_chamado.admin = tbl_admin.admin
		JOIN tbl_fabrica ON tbl_hd_chamado.fabrica = tbl_fabrica.fabrica 
		LEFT JOIN tbl_tipo_chamado on tbl_tipo_chamado.tipo_chamado = tbl_hd_chamado.tipo_chamado
		WHERE status ILIKE 'resolvido' 
		AND   fabrica_responsavel = $login_fabrica 
		$cond1
		$cond2
		$cond3
	) AS total_resolvido,
	(	SELECT count(*) AS total_execucao
		FROM tbl_hd_chamado
		JOIN tbl_admin ON tbl_hd_chamado.admin = tbl_admin.admin
		JOIN tbl_fabrica ON tbl_hd_chamado.fabrica = tbl_fabrica.fabrica 
		LEFT JOIN tbl_tipo_chamado on tbl_tipo_chamado.tipo_chamado = tbl_hd_chamado.tipo_chamado
		WHERE status ILIKE 'execução' 
		AND   fabrica_responsavel = $login_fabrica 
		$cond1
		$cond2
		$cond3
	)AS total_execucao,
	(	SELECT count(*) AS total_aguardando_execucao
		FROM tbl_hd_chamado
		JOIN tbl_admin ON tbl_hd_chamado.admin = tbl_admin.admin
		JOIN tbl_fabrica ON tbl_hd_chamado.fabrica = tbl_fabrica.fabrica 
		LEFT JOIN tbl_tipo_chamado on tbl_tipo_chamado.tipo_chamado = tbl_hd_chamado.tipo_chamado
		WHERE status ILIKE 'Aguard.Execução' 
		AND   fabrica_responsavel = $login_fabrica 
		$cond1
		$cond2
		$cond3
	)AS total_aguardando_execucao,
	(
		SELECT count(*) AS total_cancelado
		FROM tbl_hd_chamado
		JOIN tbl_admin ON tbl_hd_chamado.admin = tbl_admin.admin
		JOIN tbl_fabrica ON tbl_hd_chamado.fabrica = tbl_fabrica.fabrica 
		LEFT JOIN tbl_tipo_chamado on tbl_tipo_chamado.tipo_chamado = tbl_hd_chamado.tipo_chamado
		WHERE status ILIKE 'cancelado' 
		AND   fabrica_responsavel = $login_fabrica 
		$cond1
		$cond2
		$cond3
	) as total_cancelado,
	(	SELECT count(*) AS total_todos
		FROM tbl_hd_chamado 
		JOIN tbl_admin ON tbl_hd_chamado.admin = tbl_admin.admin
		JOIN tbl_fabrica ON tbl_hd_chamado.fabrica = tbl_fabrica.fabrica 
		LEFT JOIN tbl_tipo_chamado on tbl_tipo_chamado.tipo_chamado = tbl_hd_chamado.tipo_chamado
		WHERE fabrica_responsavel = $login_fabrica 
		$cond1
		$cond2
		$cond3
	) as total_todos";

$res = @pg_exec ($con,$sql);

$xtotal_novo               = pg_result($res,0,total_novo);
$xtotal_analise            = pg_result($res,0,total_analise);
$xtotal_aprovacao          = pg_result($res,0,total_aprovacao);
$xtotal_resolvido          = pg_result($res,0,total_resolvido);
$xtotal_execucao           = pg_result($res,0,total_execucao);
$xtotal_aguardando_execucao= pg_result($res,0,total_aguardando_execucao);
$xtotal_cancelado          = pg_result($res,0,total_cancelado);
$xtotal_todos              = pg_result($res,0,total_todos);
$xtotal_aberto              = $xtotal_todos - $xtotal_cancelado - $xtotal_resolvido  - $xtotal_aprovacao;
###FIM ESTATITICAS###


###busca###

$sql = "SELECT 
			hd_chamado,
			tbl_hd_chamado.admin    ,
			tbl_admin.nome_completo ,
			tbl_admin.login         ,
			to_char (tbl_hd_chamado.data,'DD/MM HH24:MI') AS data,
			to_char (tbl_hd_chamado.previsao_termino,'DD/MM HH24:MI') AS previsao_termino,
			to_char (tbl_hd_chamado.previsao_termino_interna,'DD/MM HH24:MI') AS previsao_termino_interna,
			tbl_hd_chamado.titulo,
			tbl_hd_chamado.status,
			tbl_hd_chamado.atendente,
			tbl_hd_chamado.prazo_horas                           ,
			tbl_hd_chamado.exigir_resposta,
			tbl_hd_chamado.cobrar,
			tbl_hd_chamado.hora_desenvolvimento,
			tbl_fabrica.nome AS fabrica_nome,
			tbl_tipo_chamado.descricao as tipo_chamado_descricao,
			CASE WHEN current_timestamp > tbl_hd_chamado.previsao_termino 
				THEN 1
				ELSE 0
			END AS atrasou,
			CASE WHEN current_timestamp > tbl_hd_chamado.previsao_termino_interna 
				THEN 1
				ELSE 0
			END AS atrasou_interno
		FROM tbl_hd_chamado
		LEFT JOIN tbl_tipo_chamado on tbl_tipo_chamado.tipo_chamado = tbl_hd_chamado.tipo_chamado
		JOIN tbl_admin ON tbl_hd_chamado.admin = tbl_admin.admin
		JOIN tbl_fabrica ON tbl_hd_chamado.fabrica  = tbl_fabrica.fabrica";

if($valor_chamado <> ''){
	$sql .= " WHERE  tbl_hd_chamado.hd_chamado = '$valor_chamado'";
}else{
	$sql .= " WHERE  tbl_hd_chamado.fabrica_responsavel = $login_fabrica";

	if (strlen($data_inicio)>0 AND strlen($data_fim)>0){
		$status_busca = 'r';

		$aux_data_inicio = formata_data($data_inicio);
		$aux_data_fim    = formata_data($data_fim);

		$sql .= " AND tbl_hd_chamado.hd_chamado IN (
					SELECT chamado.hd_chamado
					FROM (
					SELECT 
					ultima.hd_chamado, 
					(SELECT data 
					FROM tbl_hd_chamado_item 
					WHERE tbl_hd_chamado_item.hd_chamado = ultima.hd_chamado 
					ORDER BY data DESC LIMIT 1) AS ultimo_interacao
					FROM (SELECT DISTINCT hd_chamado FROM tbl_hd_chamado WHERE status='Resolvido') ultima
					) chamado
					WHERE chamado.ultimo_interacao BETWEEN '$aux_data_inicio 00:00:01' AND '$aux_data_fim 23:59:59'
				)";
	}

	if ($atendente_busca <> '' ){
		$sql .= " AND tbl_hd_chamado.atendente = $atendente_busca";
	}

	if ($autor_busca <> '' ){
		$sql .= " AND tbl_hd_chamado.admin = $autor_busca";
	}

	if($fabrica_busca <> '' ){
		$sql .= " AND tbl_fabrica.nome = '$fabrica_busca'";
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

	if ($status_busca=="ae"){
		$sql .= " AND tbl_hd_chamado.status ILIKE 'Aguard.Execução' ";
	}

	if ($status_busca=="c"){
		$sql .= " AND tbl_hd_chamado.status ILIKE 'Cancelado' ";
	}

	if ($status_busca=="n"){
		$sql .= " AND ((tbl_hd_chamado.status ILIKE 'Novo') OR (tbl_hd_chamado.status IS NULL))";
	}

	if ($status_busca==''){
		$sql .= " AND ((tbl_hd_chamado.status <> 'Aprovação' AND tbl_hd_chamado.status <> 'Cancelado' AND tbl_hd_chamado.status <> 'Resolvido')) ";
	}
}

$sql .= " ORDER BY tbl_hd_chamado.previsao_termino_interna ASC,tbl_hd_chamado.previsao_termino ASC,tbl_hd_chamado.data DESC";

//echo nl2br($sql);

$res = pg_exec ($con,$sql);

if (@pg_numrows($res) >= 0) {

/*================================TABELA DE ESCOLHA DE STATUS============================*/


	echo "<FORM METHOD='GET' ACTION='$PHP_SELF'>";
	echo "<table width = '700' align = 'center' cellpadding='0' cellspacing='0' border='0'>";
	echo "<tr>";
	echo "	<td background='/assist/helpdesk/imagem/fundo_tabela_top_esquerdo_azul_claro.gif' rowspan='2'><img src='/assist/imagens/pixel.gif' width='9'></td>";
	echo "	<td background='/assist/helpdesk/imagem/fundo_tabela_top_centro_azul_claro.gif'   colspan='9' align = 'center' width='100%' style='font-family: arial ; color:#666666'>&nbsp;</td>";
	echo "	<td background='/assist/helpdesk/imagem/fundo_tabela_top_direito_azul_claro.gif'  rowspan='2'><img src='/assist/imagens/pixel.gif' width='9'></td>";
	echo "</tr>";
	echo "<tr bgcolor='#D9E8FF' style='font-family: arial ; color: #666666'>";
	echo "	<td colspan='9'><b><CENTER>Pesquisa Chamados</CENTER></b></td>";
	echo "</tr>";
	echo "<tr align='left'  height ='70' valign='top'>";
	echo "	<td background='/assist/helpdesk/imagem/fundo_tabela_centro_esquerdo.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";
	
	echo "<td align='center'><INPUT TYPE=\"radio\" NAME=\"status\" value='n'";
	if ($status_busca=='n') echo "CHECKED";
	echo "><BR><font size=2>Novo<BR>(</font><font size=2 ";
	if($xtotal_novo>0)echo"color='ff0000'";echo"><b> $xtotal_novo  </B></font><font size=2>)</font></td>";
	
	echo "<td align='center' ><INPUT TYPE=\"radio\" NAME=\"status\" value='a'";
	if ($status_busca=='a') echo "CHECKED";
	echo "><BR><font size=2>Analise<BR>(</font><font size=2 "; 
	if($xtotal_analise>0)echo "color='ff0000'";echo "><b> $xtotal_analise </B></font><font size=2>)</font></td>";

	echo "<td align='center'><INPUT TYPE=\"radio\" NAME=\"status\" value='ae'";
	if ($status_busca=='ae') echo "CHECKED";
	echo "><BR><font size=2>Aguardando<br>Execução<BR>(</font><font size=2 "; 
	if($xtotal_aguardando_execucao>0)echo "color='ff0000'";echo "><b> $xtotal_aguardando_execucao </B></font><font size=2>)</font></td>";

	echo "<td align='center'><INPUT TYPE=\"radio\" NAME=\"status\" value='e'";
	if ($status_busca=='e') echo "CHECKED";
	echo "><BR><font size=2>Execução<BR>(</font><font size=2 "; 
	if($xtotal_execucao>0)echo "color='ff0000'";echo "><b> $xtotal_execucao </B></font><font size=2>)</font></td>";

	echo "<td align='center'><INPUT TYPE=\"radio\" NAME=\"status\" value=''";
	if ($status_busca=='') echo "CHECKED";
	echo "><BR><font size=2>Em Aberto<BR>(<b> $xtotal_aberto </B>)</font></td>";


	echo "<td><br></td>";


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
	
	echo "<tr bgcolor='#D9E8FF' style='font-family: arial ; color: #666666'>";
	echo "	<td background='/assist/helpdesk/imagem/fundo_tabela_centro_esquerdo.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";
	echo "	<td colspan='4'><CENTER><B>Atendente</B></CENTER></td>";
	echo "	<td><CENTER><B>Autor</B></CENTER></td>";
	echo "	<td colspan='4'><CENTER><B>Fábrica</B></CENTER></td>";
	echo "	<td background='/assist/helpdesk/imagem/fundo_tabela_centro_direito.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";
	echo "</tr>";
	
	echo "<tr>";
	echo "	<td background='/assist/helpdesk/imagem/fundo_tabela_centro_esquerdo.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";

	

	# Atendente
	echo "	<td td colspan='4'>";
		$sqlatendente = "SELECT  nome_completo,
					admin
				FROM    tbl_admin
				WHERE   tbl_admin.fabrica = 10
				ORDER BY tbl_admin.nome_completo;";
	
		$resatendente = pg_exec ($con,$sqlatendente);
		
		$atendente_busca = trim($_POST['atendente_busca']);
		if (strlen($atendente_busca)==0){
			$atendente_busca = trim($_GET['atendente_busca']);
		}

		if (pg_numrows($resatendente) > 0) {
			echo "<BR><center><select class='frm' style='width: 200px;' name='atendente_busca'>\n";
			echo "<option value='' ";
			if (strlen ($atendente_busca) == 0 ) echo " SELECTED "; 
			echo ">- TODOS -</option>\n";

			for ($x = 0 ; $x < pg_numrows($resatendente) ; $x++){
				$n_admin = trim(pg_result($resatendente,$x,admin));
				$nome_atendente  = trim(pg_result($resatendente,$x,nome_completo));

				echo "<option value='$n_admin'"; 
				//if ($login_admin == $n_admin AND strlen ($atendente_busca) == 0 ) echo " SELECTED "; 
				if ($atendente_busca == $n_admin ) echo " SELECTED "; 
				echo "> $nome_atendente</option>\n";
			}
			
			echo "</select></center><BR>";
		}

	
	echo "</td>";

	# Autor
	echo "<td>";
		$sqlatendente = "SELECT  nome_completo,
							admin
						FROM    tbl_admin
						WHERE   tbl_admin.fabrica = 10
						ORDER BY tbl_admin.nome_completo;";
	
		$resatendente = pg_exec ($con,$sqlatendente);
		
		$autor_busca = trim($_POST['autor_busca']);
		if (strlen($autor_busca)==0){
			$autor_busca = trim($_GET['autor_busca']);
		}

		if (pg_numrows($resatendente) > 0) {
			echo "<BR><center>";
			echo "<select class='frm' style='width: 200px;' name='autor_busca'>\n";
			echo "<option value='' ";
			if (strlen ($atendente_busca) == 0 ) echo " SELECTED "; 
			echo ">- TODOS -</option>\n";

			for ($x = 0 ; $x < pg_numrows($resatendente) ; $x++){
				$n_admin = trim(pg_result($resatendente,$x,admin));
				$nome_atendente  = trim(pg_result($resatendente,$x,nome_completo));

				echo "<option value='$n_admin'"; 
				//if ($login_admin == $n_admin AND strlen ($autor_busca) == 0 ) echo " SELECTED "; 
				if ($autor_busca == $n_admin ) echo " SELECTED "; 
				echo "> $nome_atendente</option>\n";
			}
			
			echo "</select></center><BR>";
		}	
	echo "</td>";
	
	echo "	<td colspan='4'><CENTER>";
	$sqlfabrica = "SELECT   * 
			FROM     tbl_fabrica 
			ORDER BY nome";
	$resfabrica = pg_exec ($con,$sqlfabrica);
	$n_fabricas = pg_numrows($res);


	echo "<center><select class='frm' style='width: 200px;' name='fabrica_busca'></center>\n";
	echo "<option value=''>- FÁBRICA -</option>\n";
	for ($x = 0 ; $x < pg_numrows($resfabrica) ; $x++){
		$fabrica   = trim(pg_result($resfabrica,$x,fabrica));
		$nome      = trim(pg_result($resfabrica,$x,nome));
		echo "<option value='$nome'"; if ($fabrica_busca == $nome) echo " SELECTED "; echo ">$nome</option>\n";
}
	echo "</select>\n";

	echo "</CENTER></td>";

	echo "	<td background='/assist/helpdesk/imagem/fundo_tabela_centro_direito.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";
	echo "</tr>";


	echo "<tr bgcolor='#D9E8FF' style='font-family: arial ; color: #666666'>";
	echo "	<td background='/assist/helpdesk/imagem/fundo_tabela_centro_esquerdo.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";
	echo "	<td colspan='9'><CENTER><B>Busca pelo Número do Chamado</B></CENTER></td>";
	echo "	<td background='/assist/helpdesk/imagem/fundo_tabela_centro_direito.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";
	echo "</tr>";
	
	echo "<tr>";
	echo "	<td background='/assist/helpdesk/imagem/fundo_tabela_centro_esquerdo.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";
	echo "	<td td colspan='3'>&nbsp;</td>";
	echo "<td align='center' colspan='4'><BR><font size=2>Número Chamado:</font> &nbsp; <input type='text' size='5' maxlength='5' name='valor_chamado' value=''> <BR><BR>";
	echo "</td>";
	echo "	<td td colspan='2'>&nbsp;</td>";
	echo "	<td background='/assist/helpdesk/imagem/fundo_tabela_centro_direito.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";
	echo "</tr>";
	
	echo "<tr bgcolor='#D9E8FF' style='font-family: arial ; color: #666666'>";
	echo "	<td background='/assist/helpdesk/imagem/fundo_tabela_centro_esquerdo.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";
	echo "	<td colspan='9'><CENTER><B>Busca por Data</B></CENTER></td>";
	echo "	<td background='/assist/helpdesk/imagem/fundo_tabela_centro_direito.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";
	echo "</tr>";
	
	echo "<tr>";
	echo "<td background='/assist/helpdesk/imagem/fundo_tabela_centro_esquerdo.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";
		echo "<td td colspan='3'>&nbsp;</td>";
		echo "<td align='center' colspan='2'><BR><font size=2>Data Início </font> <input type='text' size='10' name='data_inicio' id='data_inicio' value='$data_inicio'><BR><BR></td>";
		echo "<td align='center' colspan='2'><BR><font size=2>Data Final </font> <input type='text' size='10' name='data_fim' id='data_fim' value='$data_fim'><BR><BR></td>";
		echo "<td td colspan='2'>&nbsp;</td>";
	echo "<td background='/assist/helpdesk/imagem/fundo_tabela_centro_direito.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";
	echo "</tr>";	
	
	
#botao submit

	echo "<tr>";
	echo "	<td background='/assist/helpdesk/imagem/fundo_tabela_centro_esquerdo.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";
	echo "<td colspan='9' nowrap><CENTER><INPUT TYPE=\"submit\" value=\"Pesquisar\"></CENTER></td>";
//	echo "	<td></td>";
	echo "	<td background='/assist/helpdesk/imagem/fundo_tabela_centro_direito.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";
	echo "</tr>";

#===========================

	echo "<tr>";
	echo "	<td background='/assist/helpdesk/imagem/fundo_tabela_baixo_esquerdo.gif'><img src='/assist/imagens/pixel.gif' width='9'></td>";
	echo "	<td background='/assist/helpdesk/imagem/fundo_tabela_baixo_centro.gif' colspan='9' align = 'center' width='100%'></td>";
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





	echo "<table width = '100%' align = 'center' cellpadding='2' cellspacing='1' border='0' name='relatorio' id='relatorio' class='relatorio'>";
echo "<thead>";
	echo "<tr bgcolor='#D9E8FF' style='font-family: arial ;font-size:12px; color: #666666'>";
	echo "	<th ><strong>Nº </strong></th>";
	echo "	<th nowrap><strong>Título</strong><img src='/assist/imagens/pixel.gif' width='5'></th>";
	echo "	<th ><strong>Status</strong></th>";
	echo "	<th ><strong>Tipo</strong></th>";
	echo "	<th ><strong>Data</strong></th>";
	echo "	<th nowrap><strong>Autor </strong></th>";
	echo "	<th nowrap><strong>Fábrica </strong></td>";
	echo "	<th nowrap><strong>Atendente </strong></th>";
	echo "	<th nowrap ><acronym title='Horas Trabalhadas'><strong>Trab.</strong></acronym></th>";
	echo "	<th nowrap ><strong>Prazo</strong></th>";
//	echo "	<th nowrap><img src='/assist/imagens/pixel.gif' width='5'><strong>Prazo Interno</strong></td>";
//	echo "	<th nowrap><img src='/assist/imagens/pixel.gif' width='5'><strong>Prazo</strong></td>";
	echo "</tr>";
	
	echo "</thead>";
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

//	echo $sql;
		
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
		$previsao_termino     = trim(pg_result($res,$i,previsao_termino));
		$previsao_termino_interna = trim(pg_result($res,$i,previsao_termino_interna));
		$atrasou              = trim(pg_result($res,$i,atrasou));
		$atrasou_interno      = trim(pg_result($res,$i,atrasou_interno));
		$cobrar               = trim(pg_result($res,$i,cobrar));
		$hora_desenvolvimento = trim(pg_result($res,$i,hora_desenvolvimento));
		$tipo_chamado_descricao= trim(pg_result($res,$i,tipo_chamado_descricao));
		$prazo_horas          = pg_result($res,$i,prazo_horas);
		
		$wsql =" select sum(case when data_termino is null then current_timestamp else data_termino end - data_inicio ) from tbl_hd_chamado_atendente where hd_chamado = $hd_chamado;";
		$wres = pg_exec($con, $wsql);
		if(pg_numrows($wres)>0)
		$horas= pg_result ($wres,0,0);	
		if(strlen($horas)>0){
			$xhoras = explode(":",$horas);
			$horas = $xhoras[0].":".$xhoras[1];
		}

		if ($status == 'Aprovação' OR $status == 'Resolvido' OR $status == 'Cancelado'){
			$atrasou = 0;
		}

		if ($atrasou == 0 AND $chamados_atrasados==1){
			//break;
		}
	
		$sql2 = "SELECT nome_completo, admin
			FROM	tbl_admin
			WHERE	admin='$atendente'";
//		echo $sql2;
		$res2 = pg_exec ($con,$sql2);	
		$xatendente            = pg_result($res2,0,nome_completo);
		$xxatendente = explode(" ", $xatendente);
		
		$cor='#F2F7FF';
		if ($i % 2 == 0) $cor = '#FFFFFF';

		if ($atrasou_interno == '1'){
			$cor='#F8FBB3';
		}

		if ($atrasou == '1'){
			$chamados_atrasados = 1;
			$cor='#FFE6E1';
		}

		if (strlen($hora_desenvolvimento)>0){
			$hora_desenvolvimento = "(".$hora_desenvolvimento." h)";
		}

		for($r = 0 ; $r < count($chamado_interno); $r++){
			if($hd_chamado == $chamado_interno[$r])$interno="<img src='../admin/imagens_admin/star_on.gif' width='10' title='Contém chamado interno' border='0'>";
		}
		echo "<tbody>";
		echo "<tr  style='font-family: arial ; font-size: 11px ; cursor: hand; ' height='25' bgcolor='$cor'  onmouseover=\"this.bgColor='#D9E8FF'\" onmouseout=\"this.bgColor='$cor'\"  nowrap>";
		 echo "<a href='adm_chamado_detalhe.php?hd_chamado=$hd_chamado'>";

		echo "<td nowrap width='60'>";
		if($status =="Análise" AND $exigir_resposta <> "t"){
			echo "<img src='/assist/admin/imagens_admin/status_azul.gif' align='absmiddle' width='8'> ";
		}elseif($exigir_resposta == "t" AND $status<>'Cancelado' AND $status <> "Resolvido" ) {
			echo "<img src='/assist/admin/imagens_admin/status_vermelho.gif' align='absmiddle' width='8'> ";
		}elseif (($status == "Resolvido" ) OR $status == "Cancelado") {
				echo "<img src='/assist/admin/imagens_admin/status_verde.gif' align='absmiddle' width='8'> ";
			}elseif ($status == "Aprovação") {
				echo "<img src='/assist/admin/imagens_admin/status_amarelo.gif' align='absmiddle'  width='8'> ";
			}else{
				echo "<img src='/assist/admin/imagens_admin/status_azul.gif' align='absmiddle' width='8'> ";
			}
		echo " $hd_chamado&nbsp;</td>";

		echo "<td nowrap  width='200'>";

		echo "<a href='adm_chamado_detalhe.php?hd_chamado=$hd_chamado'>";

		echo "<acronym title='$titulo'>$interno ";
		echo substr($titulo,0,20)."...</acronym></a></td>";
		
		if (($status != 'Resolvido') and ($status != 'Cancelado')) {
			$cor_status="#000000";
			if($status=="Novo" or $status =="Análise")$cor_status="#FF0000";
			if($status=="Execução")$cor_status="#339933";
			if($status=="Aguard.Execução")$cor_status="#FF9900";
			echo "<td nowrap><font color='$cor_status' size='1'><B>$status </B></font><b>$hora_desenvolvimento</b></td>";
		}else{
			echo "<td nowrap>$status <b>$hora_desenvolvimento</b></td>";
		}
		$imagem_erro="";
		
		if($tipo_chamado_descricao=="Erro em programa"){$imagem_erro="<IMG SRC='imagem/ico_erro.png' border='0'>"; }
		echo "<td nowrap width='130' >$imagem_erro <font size='1'><strong>$tipo_chamado_descricao</strong></font></td>";
		
		echo "<td nowrap width='80'><font size='1'>$data</font></td>";
		echo "<td nowrap><font size='1'>";
		if (strlen ($nome_completo) > 0) {
			$nome_completo2 = explode (' ',$nome_completo);
			$nome_completo2 = $nome_completo2[0];
			echo $nome_completo2;
			
		}else{
			echo $login;
		}
		echo "</font></td>";
		echo "<td nowrap width='80'><font size='1'>$fabrica_nome</font></td>";
		echo "<td nowrap><font size='1'>$xxatendente[0]</font></td>";
		echo "<td nowrap align='center'><font size='1'>$horas</font></td>";
		echo "<td nowrap align='center'><font size='1'>$prazo_horas</font></td>";
//echo "<td><font size='1' nowrap>&nbsp;$previsao_termino_interna&nbsp;</font></td>";
	//	echo "<td><font size='1' nowrap>&nbsp;$previsao_termino&nbsp;</font></td>";
		
		echo "</tr>"; 
		$interno='';
	}

//fim imprime chamados
	
		echo "</tbody>";

	echo "</table>"; 
### PÉ PAGINACAO###

	if ($chamados_atrasados == 1){
		echo "<center><h3>Chamados atrasados! Concluir com URGÊNCIA.</h3><center>";
	}


	echo "<table border='0' align='center'>";
	echo "<tr>";
	echo "<td colspan='10' align='center'>";
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
