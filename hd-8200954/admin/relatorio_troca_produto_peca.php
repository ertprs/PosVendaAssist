<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'includes/funcoes.php';

$admin_privilegios="gerencia";
include "autentica_admin.php";

include "funcoes.php";

$msg = "";



$meses = array (1 => "Janeiro", "Fevereiro", "Março", "Abril", "Maio", "Junho", "Julho", "Agosto", "Setembro", "Outubro", "Novembro", "Dezembro");

if (strlen($_POST["acao"]) > 0) $acao = strtoupper($_POST["acao"]);

##### GERAR ARQUIVO EXCEL #####
if ($acao == "RELATORIO") {
	$x_data_inicial     = trim($_GET["data_inicial"]);
	$x_data_final       = trim($_GET["data_final"]);
	
	$sql =	"SELECT tbl_posto_fabrica.posto                          AS posto               ,
				tbl_posto_fabrica.codigo_posto                       AS posto_codigo        ,
				tbl_posto.nome                                       AS posto_nome          ,
				tbl_os.sua_os                                                               ,
				tbl_os.os                                                                   ,
				tbl_os.ressarcimento                                                        ,
				tbl_produto.referencia                               AS produto_referencia  ,
				tbl_produto.descricao                                AS produto_descricao   ,
				(
					SELECT referencia 
					FROM tbl_peca 
					JOIN tbl_os_item    USING (peca) 
					JOIN tbl_os_produto USING (os_produto) 
					WHERE tbl_peca.produto_acabado 
					AND tbl_os_produto.os = tbl_os.os LIMIT 1
				)                                                    AS troca_por_referencia ,
				(
					SELECT descricao  
					FROM tbl_peca 
					JOIN tbl_os_item    USING (peca) 
					JOIN tbl_os_produto USING (os_produto) 
					WHERE tbl_peca.produto_acabado 
					AND tbl_os_produto.os = tbl_os.os LIMIT 1
				)                                                    AS troca_por_descricao ,
				(
					SELECT pedido
					FROM tbl_peca 
					JOIN tbl_os_item    USING (peca) 
					JOIN tbl_os_produto USING (os_produto) WHERE tbl_peca.produto_acabado AND tbl_os_produto.os = tbl_os.os LIMIT 1
				)                                                    AS pedido ,
				TO_CHAR(tbl_os.data_abertura,'DD/MM/YYYY')           AS data_abertura        ,
				TO_CHAR(tbl_os.data_fechamento,'DD/MM/YYYY HH:MM')   AS data_fechamento      ,
				tbl_admin.login                                                              ,
				tbl_os_troca.ri                                                              ,
				tbl_os_troca.setor                                                           ,
				tbl_os_troca.situacao_atendimento                                            ,
				tbl_causa_troca.descricao                            AS causa_troca
		FROM tbl_os
		JOIN tbl_admin            ON tbl_admin.admin           = tbl_os.troca_garantia_admin
		JOIN tbl_posto            ON tbl_posto.posto           = tbl_os.posto
		JOIN tbl_posto_fabrica    ON tbl_posto_fabrica.posto   = tbl_posto.posto AND tbl_posto_fabrica.fabrica=$login_fabrica 
		JOIN tbl_produto          ON tbl_produto.produto       = tbl_os.produto AND tbl_produto.fabrica_i=$login_fabrica
		LEFT JOIN tbl_os_troca    ON tbl_os_troca.os           = tbl_os.os AND tbl_os_troca.fabric=$login_fabrica
		LEFT JOIN tbl_causa_troca ON tbl_causa_troca.causa_troca = tbl_os_troca.causa_troca AND tbl_causa_troca.fabrica=$login_fabrica
		WHERE tbl_os.fabrica = $login_fabrica
		AND   ( tbl_os.troca_garantia IS TRUE OR tbl_os.ressarcimento IS TRUE )
		AND   tbl_os.data_fechamento BETWEEN '$x_data_inicial' AND '$x_data_final' ;";
//echo nl2br($sql);
	$res = pg_exec($con,$sql);

	if (pg_numrows($res) > 0) {

		$data = date("Y_m_d-H_i_s");
		$arq = fopen("/tmp/assist/relatorio-troca-produto-$login_fabrica-$data.html","w");

		fputs($arq,"<html>");
		fputs($arq,"<head>");
		fputs($arq,"<title>RELATÓRIO DE TROCA DE PRODUTO - ".date("d/m/Y H:i:s"));
		fputs($arq,"</title>");
		fputs($arq,"</head>");
		fputs($arq,"<body>");


		fputs($arq,"<table border='0' cellspacing='0' cellpadding='0' >");
		fputs($arq,"<tr height='18'>");
		fputs($arq,"<td width='18' bgcolor='#ddf8cc'>&nbsp;</td>");
		fputs($arq,"<td align='left'><font size='1'><b>&nbsp; Ressarcimento Financeiro </b></font></td>");
		fputs($arq,"</tr>");
		fputs($arq,"</table>");

		fputs($arq,"<br>");
		fputs($arq,"<table width='750' border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#596D9B' align='center' style='border-style: solid; border-color: #596D9B; border-width:1px;
font-family: Verdana;
font-size: 10px;'>");
		fputs($arq,"<tr height='15' class='Titulo'>");
		fputs($arq,"<td background='imagens_admin/azul.gif'>OS</td>");
		fputs($arq,"<td background='imagens_admin/azul.gif'>Posto</td>");
		fputs($arq,"<td background='imagens_admin/azul.gif'>Produto</td>");
		fputs($arq,"<td background='imagens_admin/azul.gif'>Produto troca</td>");
		fputs($arq,"<td background='imagens_admin/azul.gif'>Abertura</td>");
		fputs($arq,"<td background='imagens_admin/azul.gif'>Troca</td>");
		fputs($arq,"<td background='imagens_admin/azul.gif'>Pedido</td>");
		fputs($arq,"<td background='imagens_admin/azul.gif'>Responsável</td>");
		fputs($arq,"<td background='imagens_admin/azul.gif' nowrap>Setor responsável</td>");
		fputs($arq,"<td background='imagens_admin/azul.gif' nowrap>Situação do atendimento</td>");
		fputs($arq,"<td background='imagens_admin/azul.gif'>RI</td>");
		fputs($arq,"<td background='imagens_admin/azul.gif'>Causa da Troca</td>");
		fputs($arq,"<td background='imagens_admin/azul.gif'>Ressarcimento</td>");
		fputs($arq,"</tr>");

		for ($i = 0 ; $i < pg_numrows($res) ; $i++) {
			$posto                = trim(pg_result($res,$i,posto));
			$posto_codigo         = trim(pg_result($res,$i,posto_codigo));
			$posto_nome           = trim(pg_result($res,$i,posto_nome));
			$posto_completo       = $posto_codigo . " - " . $posto_nome;
			$sua_os               = trim(pg_result($res,$i,sua_os));
			$os                   = trim(pg_result($res,$i,os));
			$produto_referencia   = trim(pg_result($res,$i,produto_referencia));
			$produto_descricao    = trim(pg_result($res,$i,produto_descricao));
			$produto_completo     = $produto_referencia . " - " . $produto_descricao;
			$troca_por_referencia = trim(pg_result($res,$i,troca_por_referencia));
			$troca_por_descricao  = trim(pg_result($res,$i,troca_por_descricao));
			$troca_por_completo   = $troca_por_referencia . " - " . $troca_por_descricao;
			$data_abertura        = trim(pg_result($res,$i,data_abertura));
			$data_fechamento      = trim(pg_result($res,$i,data_fechamento));
			$pedido               = trim(pg_result($res,$i,pedido));
			$login                = trim(pg_result($res,$i,login));
			$ressarcimento        = trim(pg_result($res,$i,ressarcimento));
			$ri                   = trim(pg_result($res,$i,ri));
			$setor                = trim(pg_result($res,$i,setor));
			$situacao_atendimento = trim(pg_result($res,$i,situacao_atendimento));
			$causa_troca          = trim(pg_result($res,$i,causa_troca));

			if($situacao_atendimento == 0 AND strlen($situacao_atendimento)>0) $situacao_atendimento = "Garantia";
			elseif(strlen($situacao_atendimento)>0)                            $situacao_atendimento .= "%";

			$cor = ($i % 2 == 0) ? "#F1F4FA" : "#F7F5F0";

			if ($ressarcimento == "t") $cor = "#ddf8cc";

			if($ressarcimento == "t") $ressarcimento = "SIM";
			else                      $ressarcimento = "NÃO";
			fputs($arq,"<tr class='Conteudo' height='15' bgcolor='$cor'>");
			fputs($arq,"<td nowrap><a href='os_press.php?os=$os' target='_blank'>$sua_os</a></td>");
			fputs($arq,"<td nowrap align='left'>$posto_codigo - $posto_nome</td>");
			fputs($arq,"<td nowrap align='left'><acronym title='REFERÊNCIA: $produto_referencia\nDESCRIÇÃO: $produto_descricao' style='cursor: hand;'>" . substr($produto_descricao,0,20) . "</acronym></td>");
			fputs($arq,"<td nowrap align='left'><acronym title='REFERÊNCIA: $troca_por_referencia \n DESCRIÇÃO: $troca_por_descricao' style='cursor: hand;'>" . substr($troca_por_descricao,0,20) . "</acronym></td>");
			fputs($arq,"<td nowrap>$data_abertura</td>");
			fputs($arq,"<td nowrap>$data_fechamento</td>");
			fputs($arq,"<td nowrap>$pedido</td>");
			fputs($arq,"<td nowrap align='left'>$login</td>");
			fputs($arq,"<td nowrap align='center'>$setor</td>");
			fputs($arq,"<td nowrap align='center'>$situacao_atendimento</td>");
			fputs($arq,"<td nowrap align='center'>$ri</td>");
			fputs($arq,"<td nowrap align='center'>$causa_troca</td>");
			fputs($arq,"<td nowrap align='center'>$ressarcimento</td>");
			fputs($arq,"</tr>");

			$posto_anterior  = $posto;
			$nota_fiscal     = null;
			$login           = null;
		}
	
		fputs($arq,"</table>");
		fputs($arq,"</body>");
		fputs($arq,"</html>");
		fclose($arq);
		
		echo `htmldoc --webpage --size 297x210mm --fontsize 11 --left 8mm -f /www/assist/www/admin/xls/relatorio-troca-produto-$login_fabrica-$data.xls /tmp/assist/relatorio-troca-produto-$login_fabrica-$data.html`;
		echo "<br>";
		echo "<p align='center'><font face='Verdana, Tahoma, Arial' size='2' color='#000000'><b>Relatório gerado com sucesso!<br><a href='xls/relatorio-troca-produto-$login_fabrica-$data.xls' target='_blank'>Clique aqui</a> para fazer o download do arquivo em EXCEL.<br>Você poderá ver, imprimir e salvar a tabela para consultas off-line.</b></font></p>";
		exit;
	}
}

if (strlen($acao) > 0) {

	$mes = trim (strtoupper ($_POST['mes']));
	$ano = trim (strtoupper ($_POST['ano']));

	if(strlen($ano) == 0){
		$msg = "Escolha o Ano.";
	}

	##### Pesquisa de produto #####
	$produto_referencia = trim($_POST["produto_referencia"]);
	$produto_descricao  = trim($_POST["produto_descricao"]);
}

$layout_menu = "gerencia";
$title = "RELATÓRIO DE TROCA DE PRODUTO";

include "cabecalho.php";
?>

<style type="text/css">
.Titulo {
	text-align: center;
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 12px;
	font-weight: bold;
	color: #FFFFFF;
	background-color: #596D9B;
}
.Conteudo {
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
}
</style>

<? include "javascript_pesquisas.php"; ?>

<script language="javascript" src="js/cal2.js"></script>
<script language="javascript" src="js/cal_conf2.js"></script>

<script language="JavaScript">
function GerarRelatorio (produto_referencia, produto_descricao, data_inicial, data_final) {
	var largura  = 350;
	var tamanho  = 200;
	var lar      = largura / 2;
	var tam      = tamanho / 2;
	var esquerda = (screen.width / 2) - lar;
	var topo     = (screen.height / 2) - tam;
	var link = '<?echo $PHP_SELF?>?acao=RELATORIO&produto_referencia=' + produto_referencia + '&produto_descricao=' + produto_descricao + '&data_inicial=' + data_inicial + '&data_final=' + data_final;
	window.open(link, "janela", "toolbar=no, location=no, status=yes, menubar=no, scrollbars=no, directories=no, resizable=no, width=" + largura + ", height=" + tamanho + ", top=" + topo + ", left=" + esquerda + "");
}
</script>
<br>

<? 
//Variavel escolha serve para selecionar entre 'data_digitacao' ou 'finalizada' na pesquisa.
$escolha = trim($_POST['data_filtro']); 

?>

<? if (strlen($msg) > 0) { ?>
<table width="730" border="0" cellpadding="2" cellspacing="2" align="center" class="error">
	<tr>
		<td><?echo $msg?></td>
	</tr>
</table>
<br>
<? } ?>

<form name="frm_relatorio" method="post" action="<? echo $PHP_SELF ?>">
<input type="hidden" name="acao">
<table width="400" border="0" cellspacing="0" cellpadding="2" align="center" style='border-style: solid; border-color: #6699CC; border-width:1px;
font-family: Verdana;
font-size: 10px;'>
	<tr class="Titulo">
		<td colspan="4" background='imagens_admin/azul.gif' height='25'>PESQUISA</td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF">
		<td colspan="4">&nbsp;</td>
	</tr>
		<tr>
		<td class="Conteudo" bgcolor="#D9E2EF" style="width: 10px">&nbsp;</td>
		<td class="Conteudo" bgcolor="#D9E2EF" colspan='2' style="font-size: 10px"><center>Este relatório considera o mês inteiro de Troca de Produto.</center></td>
		<td class="Conteudo" bgcolor="#D9E2EF" style="width: 10px">&nbsp;</td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF">
		<td colspan="4">&nbsp;</td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF">
		<td width="10">&nbsp;</td>
		<td>Mês</td>
		<td>Ano</td>
		<td width="10">&nbsp;</td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF">
		<td width="10">&nbsp;</td>
		<td>
		<?
		$meses = array(1 => 'Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro');
		if (strlen ($mes) == 0) $mes = date('m');
		?>
		<select name='mes' size='1' class='frm'>
			<?
				echo "<option value='anual'";
				echo ">ANUAL</option>\n";
			for ($i = 1 ; $i <= 12 ; $i++) {
				echo "<option value='$i'";
				if ($mes == $i) echo " selected";
				echo ">$meses[$i]</option>\n";
			}
			?>
		</select>
		</td>
		<td>
			<select name="ano" size="1" class="frm">
			<?
			//for ($i = 2003 ; $i <= date("Y") ; $i++) {
			for($i = date("Y"); $i > 2003; $i--){
				echo "<option value='$i'";
				if ($ano == $i) echo " selected";
				echo ">$i</option>";
			}
			?>
			</select>
		</td>
		<td width="10">&nbsp;</td>
	</tr>

	<tr class="Conteudo" bgcolor="#D9E2EF">
		<td colspan="4">&nbsp;</td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF">
		<td colspan="4"><img src="imagens_admin/btn_pesquisar_400.gif" onclick="javascript: document.frm_relatorio.acao.value='PESQUISAR'; document.frm_relatorio.submit();" style="cursor:pointer " alt="Clique AQUI para pesquisar"></td>
	</tr>
</table>
</form>

<br>

<?
flush();

if (strlen($acao) > 0 && strlen($msg) == 0 AND $mes <> "ANUAL") {
	$x_data_inicial = date("Y-m-01 00:00:00", mktime(0, 0, 0, $mes, 1, $ano));
	$x_data_final   = date("Y-m-t 23:59:59", mktime(0, 0, 0, $mes, 1, $ano));

	$mostra_data_inicial = mostra_data($x_data_inicial);
	$mostra_data_final   = mostra_data($x_data_final);

	$sql =	"SELECT DISTINCT 
				tbl_posto_fabrica.posto                              AS posto               ,
				tbl_posto_fabrica.codigo_posto                       AS posto_codigo        ,
				tbl_posto.nome                                       AS posto_nome          ,
				tbl_os.sua_os                                                               ,
				tbl_os.os                                                                   ,
				tbl_os.ressarcimento                                                        ,
				tbl_produto.referencia                               AS produto_referencia  ,
				tbl_produto.descricao                                AS produto_descricao   ,
				(
					SELECT referencia 
					FROM tbl_peca 
					JOIN tbl_os_item    USING (peca) 
					JOIN tbl_os_produto USING (os_produto) 
					WHERE tbl_peca.produto_acabado 
					AND tbl_os_produto.os = tbl_os.os LIMIT 1
				)                                                    AS troca_por_referencia ,
				(
					SELECT descricao  
					FROM tbl_peca 
					JOIN tbl_os_item    USING (peca) 
					JOIN tbl_os_produto USING (os_produto) 
					WHERE tbl_peca.produto_acabado 
					AND tbl_os_produto.os = tbl_os.os LIMIT 1
				)                                                    AS troca_por_descricao ,
				(
					SELECT pedido
					FROM tbl_peca 
					JOIN tbl_os_item    USING (peca) 
					JOIN tbl_os_produto USING (os_produto) WHERE tbl_peca.produto_acabado AND tbl_os_produto.os = tbl_os.os LIMIT 1
				)                                                    AS pedido ,
				TO_CHAR(tbl_os.data_abertura,'DD/MM/YYYY')           AS data_abertura        ,
				TO_CHAR(tbl_os.data_fechamento,'DD/MM/YYYY HH:MM')   AS data_fechamento      ,
				tbl_admin.login                                                              ,
				tbl_os_troca.ri                                                              ,
				tbl_os_troca.setor                                                           ,
				tbl_os_troca.situacao_atendimento                                            ,
				tbl_causa_troca.descricao                            AS causa_troca          ,
				tbl_peca.referencia                                  AS peca_referencia      ,
				tbl_peca.descricao                                   AS peca_descricao
		FROM tbl_os
		JOIN tbl_admin ON tbl_admin.admin = tbl_os.troca_garantia_admin
		JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_os.posto AND tbl_posto_fabrica.fabrica = tbl_os.fabrica
		JOIN tbl_posto ON tbl_posto.posto = tbl_posto_fabrica.posto
		JOIN tbl_produto ON tbl_produto.produto = tbl_os.produto and tbl_produto.fabrica_i=$login_fabrica
		LEFT JOIN tbl_os_produto USING(os)
		LEFT JOIN tbl_os_item using(os_produto)
		LEFT JOIN tbl_pedido_item using(peca)
		LEFT JOIN tbl_peca ON tbl_os_item.peca =  tbl_peca.peca AND tbl_peca.fabrica=$login_fabrica
		LEFT JOIN tbl_os_troca ON tbl_os_troca.os = tbl_os.os and tbl_os_troca.fabric=$login_fabrica
		LEFT JOIN tbl_causa_troca ON tbl_causa_troca.causa_troca = tbl_os_troca.causa_troca and tbl_causa_troca.fabrica=$login_fabrica
		WHERE tbl_os.fabrica = $login_fabrica
		AND   ( tbl_os.troca_garantia IS TRUE OR tbl_os.ressarcimento IS TRUE )
		AND   tbl_os.data_fechamento BETWEEN '$x_data_inicial' AND '$x_data_final'
		AND   tbl_pedido_item.qtde_cancelada > 0 ";

//echo $sql;
flush();
	// ##### PAGINACAO ##### //
	$sqlCount  = "SELECT count(*) FROM (";
	$sqlCount .= $sql;
	$sqlCount .= ") AS count";

	require "_class_paginacao.php";

	// definicoes de variaveis
	$max_links = 11;				// máximo de links à serem exibidos
	$max_res   = 20;				// máximo de resultados à serem exibidos por tela ou pagina
	$mult_pag  = new Mult_Pag();	// cria um novo objeto navbar
	$mult_pag->num_pesq_pag = $max_res; // define o número de pesquisas (detalhada ou não) por página

	$res = $mult_pag->executar($sql, $sqlCount, $con, "otimizada", "pgsql");

	// ##### PAGINACAO ##### //

	if (pg_numrows($res) > 0) {

		echo "<br><a href=\"javascript: GerarRelatorio ('$produto_referencia', '$produto_descricao', '$x_data_inicial', '$x_data_final');\"><font size='2'>Clique aqui para gerar arquivo do EXCEL</font></a><br>";

		echo "<table border='0' cellspacing='0' cellpadding='0' >";
		echo "<tr height='18'>";
		echo "<td width='18' bgcolor='#ddf8cc'>&nbsp;</td>";
		echo "<td align='left'><font size='1'><b>&nbsp; Ressarcimento Financeiro </b></font></td>";
		echo "</tr>";
		echo "</table>";

		echo "<br>";
		echo "<table width='750' border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#596D9B' align='center' style='border-style: solid; border-color: #596D9B; border-width:1px;
font-family: Verdana;
font-size: 10px;'>";
		echo "<tr height='15' class='Titulo'>";
		echo "<td background='imagens_admin/azul.gif'>OS</td>";
		echo "<td background='imagens_admin/azul.gif'>Posto</td>";
		echo "<td background='imagens_admin/azul.gif'>Produto</td>";
		echo "<td background='imagens_admin/azul.gif'>Produto troca</td>";
		echo "<td background='imagens_admin/azul.gif'>Abertura</td>";
		echo "<td background='imagens_admin/azul.gif'>Troca</td>";
		echo "<td background='imagens_admin/azul.gif'>Pedido</td>";
		echo "<td background='imagens_admin/azul.gif'>Responsável</td>";
		echo "<td background='imagens_admin/azul.gif' nowrap>Setor responsável</td>";
		echo "<td background='imagens_admin/azul.gif' nowrap>Situação do atendimento</td>";
		echo "<td background='imagens_admin/azul.gif'>RI</td>";
		echo "<td background='imagens_admin/azul.gif'>Causa da Troca</td>";
		echo "<td background='imagens_admin/azul.gif'>Peça Referência</td>";
		echo "<td background='imagens_admin/azul.gif'>Peça Descrição</td>";
		echo "</tr>";
		$posto_anterior = "*";

		for ($i = 0 ; $i < pg_numrows($res) ; $i++) {
			$posto                = trim(pg_result($res,$i,posto));
			$posto_codigo         = trim(pg_result($res,$i,posto_codigo));
			$posto_nome           = trim(pg_result($res,$i,posto_nome));
			$posto_completo       = $posto_codigo . " - " . $posto_nome;
			$sua_os               = trim(pg_result($res,$i,sua_os));
			$os                   = trim(pg_result($res,$i,os));
			$produto_referencia   = trim(pg_result($res,$i,produto_referencia));
			$produto_descricao    = trim(pg_result($res,$i,produto_descricao));
			$produto_completo     = $produto_referencia . " - " . $produto_descricao;
			$troca_por_referencia = trim(pg_result($res,$i,troca_por_referencia));
			$troca_por_descricao  = trim(pg_result($res,$i,troca_por_descricao));
			$troca_por_completo   = $troca_por_referencia . " - " . $troca_por_descricao;
			$data_abertura        = trim(pg_result($res,$i,data_abertura));
			$data_fechamento      = trim(pg_result($res,$i,data_fechamento));
			$pedido               = trim(pg_result($res,$i,pedido));
			$login                = trim(pg_result($res,$i,login));
			$ressarcimento        = trim(pg_result($res,$i,ressarcimento));
			$ri                   = trim(pg_result($res,$i,ri));
			$setor                = trim(pg_result($res,$i,setor));
			$situacao_atendimento = trim(pg_result($res,$i,situacao_atendimento));
			$causa_troca          = trim(pg_result($res,$i,causa_troca));
			$peca_referencia      = trim(pg_result($res,$i,peca_referencia));
			$peca_descricao       = trim(pg_result($res,$i,peca_descricao));

			if($situacao_atendimento == 0 AND strlen($situacao_atendimento)>0) $situacao_atendimento = "Garantia";
			elseif(strlen($situacao_atendimento)>0)                            $situacao_atendimento .= "%";

			$cor = ($i % 2 == 0) ? "#F1F4FA" : "#F7F5F0";

			if ($ressarcimento == "t") $cor = "#ddf8cc";

			echo "<tr class='Conteudo' height='15' bgcolor='$cor'>";
			echo "<td nowrap><a href='os_press.php?os=$os' target='_blank'>$sua_os</a></td>";
			echo "<td nowrap align='left'>$posto_codigo - $posto_nome</td>";
			echo "<td nowrap align='left'><acronym title='REFERÊNCIA: $produto_referencia\nDESCRIÇÃO: $produto_descricao' style='cursor: hand;'>" . substr($produto_descricao,0,20) . "</acronym></td>";
			echo "<td nowrap align='left'><acronym title='REFERÊNCIA: $troca_por_referencia \n DESCRIÇÃO: $troca_por_descricao' style='cursor: hand;'>" . substr($troca_por_descricao,0,20) . "</acronym></td>";
			echo "<td nowrap>$data_abertura</td>";
			echo "<td nowrap>$data_fechamento</td>";
			echo "<td nowrap>$pedido</td>";
			echo "<td nowrap align='left'>$login</td>";
			echo "<td nowrap align='center'>$setor</td>";
			echo "<td nowrap align='center'>$situacao_atendimento</td>";
			echo "<td nowrap align='center'>$ri</td>";
			echo "<td nowrap align='left'>$causa_troca</td>";
			echo "<td nowrap align='left'>$peca_referencia</td>";
			echo "<td nowrap align='left'>$peca_descricao</td>";
			echo "</tr>";

			$posto_anterior  = $posto;
			$nota_fiscal     = null;
			$login           = null;
		}
		echo "</table>";

	### PÉ PAGINACAO###

		echo "<table border='0' align='center'>";
		echo "<tr>";
		echo "<td colspan='9' align='center'>";
		// ##### PAGINACAO ##### //

		// links da paginacao
		echo "<br>";

		if($pagina < $max_links) $paginacao = pagina + 1;
		else                     $paginacao = pagina;

		// paginacao com restricao de links da paginacao

		// pega todos os links e define que 'Próxima' e 'Anterior' serão exibidos como texto plano
		$todos_links        = $mult_pag->Construir_Links("strings", "sim");

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

	}else
		echo "<br><FONT size='2' COLOR=\"#FF3333\"><B>Não encontrado!</B></FONT><br><br>";
}

echo "<br>";


include "rodape.php";
?>
