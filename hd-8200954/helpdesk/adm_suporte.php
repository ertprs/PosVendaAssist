<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';
$TITULO = "Suporte";
include "menu.php";

echo "<table width = '700'  cellpadding='0' cellspacing='0' border='0' align='center'>";
echo "<tr>";
echo "<td background='imagem/fundo_tabela_top_esquerdo_azul_claro.gif' ><img src='../imagens/pixel.gif' width='9'></td>"; //linha esquerda - 2 linhas
echo "<td background='imagem/fundo_tabela_top_centro_azul_claro.gif'   align = 'center' width='100%' style='font-family: arial ; color:#666666'><b> Ferramentas de Atendimento</b></td>";//centro
echo "<td background='imagem/fundo_tabela_top_direito_azul_claro.gif' ><img src='../imagens/pixel.gif' width='9'></td>";//linha direita - 2 linhas
echo "</tr>";
echo "<tr>";
echo "<td background='imagem/fundo_tabela_centro_esquerdo.gif' ><img src='../imagens/pixel.gif' width='9'></td>";//coluna esquerda
echo "<td>";

echo "<table width='100%'>";

// echo "<tr>";
// echo "<td align='center'>";
// echo "<a href='adm_agenda.php'><img src='imagem/admin.jpg' border='0'></a>";
// echo "</td><td>";
// echo "<a href='adm_agenda.php'>Agenda de Fabricantes e Admins</a>";
// echo "</td>";

echo "<td align='center'>";
//echo "<a href='adm_chamado_relatorio.php'><img src='imagem/02.jpg' border='0'></a>";
echo "<a href='adm_chamado_lista.php'><img src='imagem/02.jpg' border='0'></a>";

echo "</td><td>";
//echo "<a href='adm_chamado_relatorio.php'>Relatório de Atendimentos Pendentes</a>";
echo "<a href='adm_chamado_lista.php'>Relatório de Atendimentos Pendentes</a>";
echo "</td>";

echo "<td align='center'>";
echo "<a href='adm_senhas.php'><img src='imagem/senha.jpg' border='0'></a>";
echo "</td><td>";
echo "<a href='adm_senhas.php'>Senhas de Postos Autorizados</a>";
echo "</td><td>";
echo "</td>";

echo "</tr>";

echo "<tr>";

/*echo "<td align='center'>";
echo "<a href='adm_relatorio_digitacao.php'><img src='imagem/relatorio.jpg' border='0'></a>";
echo "</td><td>";
echo "<a href='adm_relatorio_digitacao.php'>Relatório de Digitaçao de OS's da Latina</a>";
echo "</td>";
*/
echo "<td align='center'>";
echo "<a href='adm_estatistica_new.php' border='0'><img src='imagem/grafico.gif' border='0'></a>";
echo "</td><td>";
echo "<a href='adm_estatistica_new.php' border='0'>Estatísticas</a>";
echo "</td>";

/*echo "<td align='center'>";
echo "<a href='adm_chamado_relatorio_por_periodo.php' border='0'><img src='imagem/grafico.gif' border='0'></a>";
echo "</td><td>";
echo "<a href='adm_chamado_relatorio_por_periodo.php' border='0'>Posição de Atendimento</a>";
echo "</td>";*/

echo "<td align='center'>";
echo "<a href='rotinas_php_fabricas.php' border='0'><img src='imagem/php.png' border='0' style='width: 48px; height: 48px;'></a>";
echo "</td><td>";
echo "<a href='rotinas_php_fabricas.php' border='0'>Rotinas PHP</a>";
echo "</td>";

echo "<tr><td align='center'>";
echo "<a href='altera_dados_posto.php' border='0'><img src='imagem/senha.jpg' border='0' style='width: 48px; height: 48px;'></a>";
echo "</td><td>";
echo "<a href='altera_dados_posto.php' border='0'>Alterar dados Posto</a>";
echo "</td></tr>";

echo "</tr>";

echo "</table>";


echo "</td>";

echo "<td background='imagem/fundo_tabela_centro_direito.gif' ><img src='../imagens/pixel.gif' width='9'></td>";// coluna direita

echo "</tr>";

echo "<tr>";
echo "<td background='imagem/fundo_tabela_baixo_esquerdo.gif'><img src='../imagens/pixel.gif' width='9'></td>";
echo "<td background='imagem/fundo_tabela_baixo_centro.gif' align = 'center' width='100%'></td>";
echo "<td background='imagem/fundo_tabela_baixo_direito.gif'><img src='../imagens/pixel.gif' width='9'></td>";
echo "</tr>";
echo "</table>";

include "rodape.php";
die;

//Exclui todos os quadros que estao no codigo abaixo desta linha, pois estao com informacoes incorretas e ninguem usa pra nada

$sql1 = "SELECT count (*) AS total_novo
	FROM       tbl_hd_chamado
	WHERE      status ILIKE 'novo'";


$res1 = @pg_exec ($con,$sql1);

if (@pg_numrows($res1) > 0) {//PEGA OS DADOS DE QUEM ESTÁ ABRINDO O CHAMADO
	$total_novo           = pg_result($res1,0,total_novo);
	}


$sql2 = "SELECT	 COUNT (*) AS total_analise
	FROM       tbl_hd_chamado
	WHERE      status ILIKE 'análise' ";

$res2 = @pg_exec ($con,$sql2);

if (@pg_numrows($res2) > 0) {//PEGA OS DADOS DE QUEM ESTÁ ABRINDO O CHAMADO
	$total_analise           = pg_result($res2,0,total_analise);
	}

$sql3 = "SELECT	 COUNT (*) AS total_aprovacao
	FROM       tbl_hd_chamado
	WHERE      status ILIKE 'aprovação'";

$res3 = @pg_exec ($con,$sql3);

if (@pg_numrows($res3) > 0) {//PEGA OS DADOS DE QUEM ESTÁ ABRINDO O CHAMADO
	$total_aprovacao           = pg_result($res3,0,total_aprovacao);
	}



$sql4 = "SELECT	 COUNT (*) AS total_resolvido
	FROM       tbl_hd_chamado
	WHERE      status ILIKE 'resolvido'";

$res4 = @pg_exec ($con,$sql4);

if (@pg_numrows($res4) > 0) {//PEGA OS DADOS DE QUEM ESTÁ ABRINDO O CHAMADO
	$total_resolvido           = pg_result($res4,0,total_resolvido);
	}


$sql5 = "SELECT	 COUNT (*) AS total_cancelado
	FROM       tbl_hd_chamado
	WHERE      status ILIKE 'cancelado'";

$res5 = @pg_exec ($con,$sql5);

if (@pg_numrows($res5) > 0) {//PEGA OS DADOS DE QUEM ESTÁ ABRINDO O CHAMADO
	$total_cancelado           = pg_result($res5,0,total_cancelado);
	}


$sql6 = "SELECT	 COUNT (*) AS total_Execução
	FROM       tbl_hd_chamado
	WHERE      status ILIKE 'Execução'";

$res6 = @pg_exec ($con,$sql6);

if (@pg_numrows($res6) > 0) {//PEGA OS DADOS DE QUEM ESTÁ ABRINDO O CHAMADO
	$total_Execução           = pg_result($res6,0,total_Execução);
	}



?>

<table width = '700' align = 'center' cellpadding='0' cellspacing='0'>
<tr>
	<td background='imagem/fundo_tabela_top_esquerdo_azul_claro.gif' rowspan='2'><img src='../imagens/pixel.gif' width='9'></td>
	<td background='imagem/fundo_tabela_top_centro_azul_claro.gif' colspan='8' align = 'center' width='100%' style='font-family: arial ; color:#666666'><b>Estatística de Chamadas</b></td>
	<td background='imagem/fundo_tabela_top_direito_azul_claro.gif' rowspan='2'><img src='../imagens/pixel.gif' width='9'></td>
</tr>
<tr bgcolor='#D9E8FF' style='font-family: arial ; color: #666666'>
	<td nowrap colspan="9"></td>
	<td nowrap></td>
</tr>
<tr style='font-family: arial ; font-size: 12px ; ' height='25'>
	<td background='imagem/fundo_tabela_centro_esquerdo.gif' ><img src='../imagens/pixel.gif' width='9'></td>
	<td nowrap ><CENTER>Novo: <B><? echo $total_novo ?></B></CENTER></td>
	<td>&nbsp;</td>
	<td nowrap ><CENTER>Análise: <B><? echo $total_analise ?></B></CENTER></td>
	<td>&nbsp;</td>
	<td nowrap><CENTER>Aprovação: <B><? echo $total_aprovacao ?></B></CENTER></td>
	<td nowrap><CENTER>Resolvido: <B><? echo $total_resolvido ?></B></CENTER></td>
	<td nowrap><CENTER>Execução: <B><? echo $total_Execução ?></B></CENTER></td>
	<td nowrap><CENTER>Cancelado: <B><? echo $total_cancelado ?></B></CENTER></td>
	<td background='imagem/fundo_tabela_centro_direito.gif' ><img src='../imagens/pixel.gif' width='9'></td>
</tr>
<tr style='font-family: arial ; font-size: 12px ; ' height='25'>
	<td background='imagem/fundo_tabela_centro_esquerdo.gif' ><img src='../imagens/pixel.gif' width='9'></td>
	<td nowrap colspan="4"><a href='chamado_detalhe.php'><img src="imagem/01.jpg" width="32" height="32"border='0'><B>INSERIR CHAMADO</B></a></td>
	<td nowrap align="right" colspan="4"><a href='chamado_lista.php'><img src="imagem/01.jpg" width="32" height="32"border='0'><B>LISTAR MEUS CHAMADO</B></a></td>
	<td background='imagem/fundo_tabela_centro_direito.gif' ><img src='../imagens/pixel.gif' width='9'></td>
</TR>
<tr>
	<td background='imagem/fundo_tabela_baixo_esquerdo.gif'><img src='../imagens/pixel.gif' width='9'></td>
	<td background='imagem/fundo_tabela_baixo_centro.gif' colspan='8' align = 'center' width='100%'></td>
	<td background='imagem/fundo_tabela_baixo_direito.gif'><img src='../imagens/pixel.gif' width='9'></td>
</tr>
</table>

<?
$sql = "select  x.admin                              ,
		x.nome_completo                      ,
		tbl_hd_chamado.hd_chamado            ,
		tbl_hd_chamado.titulo                ,
		tbl_hd_chamado_atendente.data_inicio ,
		tbl_hd_chamado_atendente.data_termino
	FROM tbl_hd_chamado_atendente
	JOIN (
		SELECT DISTINCT
			tbl_admin.admin        ,
			tbl_admin.nome_completo,
			(
			SELECT tbl_hd_chamado_atendente.hd_chamado_atendente 
			FROM tbl_hd_chamado_atendente 
			WHERE tbl_hd_chamado_atendente.admin = tbl_admin.admin
			ORDER by tbl_hd_chamado_atendente.hd_chamado_atendente DESC 
			LIMIT 1
		) AS hd_chamado_atendente
		FROM tbl_hd_chamado_atendente
		JOIN tbl_admin using(admin)
	) x                 ON x.hd_chamado_atendente    = tbl_hd_chamado_atendente.hd_chamado_atendente
	JOIN tbl_hd_chamado ON tbl_hd_chamado.hd_chamado = tbl_hd_chamado_atendente.hd_chamado
  ORDER BY x.nome_completo";
$res = pg_exec ($con,$sql);
	//echo "<div style='position: absolute; top: 124px; left: 300px;opacity:.85;'>";
if (pg_numrows($res) > 0) {

	echo "<table width = '500'  cellpadding='0' cellspacing='0' border='0' align='center'>";
	echo "<tr>";
	echo "<td background='imagem/fundo_tabela_top_esquerdo_azul_claro.gif' ><img src='../imagens/pixel.gif' width='9'></td>"; //linha esquerda - 2 linhas
	echo "<td background='imagem/fundo_tabela_top_centro_azul_claro.gif'   align = 'center' width='100%' style='font-family: arial ; color:#666666'><b> Chamados em Andamento</b></td>";//centro
	echo "<td background='imagem/fundo_tabela_top_direito_azul_claro.gif' ><img src='../imagens/pixel.gif' width='9'></td>";//linha direita - 2 linhas
	echo "</tr>";
	echo "<tr>";
	echo "<td background='imagem/fundo_tabela_centro_esquerdo.gif' ><img src='../imagens/pixel.gif' width='9'></td>";//coluna esquerda
	echo "<td>";
	
	echo "<table width='100%'>";

	for ($x = 0 ; $x < pg_numrows($res) ; $x++){
		$admin          = trim(pg_result($res,$x,admin));
		$nome_completo  = trim(pg_result($res,$x,nome_completo));
		$hd_chamado     = trim(pg_result($res,$x,hd_chamado));
		$titulo         = trim(pg_result($res,$x,titulo));
		$data_inicio    = trim(pg_result($res,$x,data_inicio));
		$data_termino   = trim(pg_result($res,$x,data_termino));



		echo "<tr class='Conteudo'>";
		echo "<td align='left' height='15'>$nome_completo</td><td>";
		echo "<td align='left'>";
		if(strlen($data_termino)==0)echo "<a href='adm_chamado_detalhe.php?hd_chamado=$hd_chamado'>$hd_chamado - $titulo</a>";
		else                        echo "STAND BY";
		echo "</td><td>";

		echo "</td>";
		echo "</tr>";

	}
	echo "</table>";
	
	
	echo "</td>";
	
	echo "<td background='imagem/fundo_tabela_centro_direito.gif' ><img src='../imagens/pixel.gif' width='9'></td>";// coluna direita
	
	echo "</tr>";
	
	echo "<tr>";
	echo "<td background='imagem/fundo_tabela_baixo_esquerdo.gif'><img src='../imagens/pixel.gif' width='9'></td>";
	echo "<td background='imagem/fundo_tabela_baixo_centro.gif' align = 'center' width='100%'></td>";
	echo "<td background='imagem/fundo_tabela_baixo_direito.gif'><img src='../imagens/pixel.gif' width='9'></td>";
	echo "</tr>";
	echo "</table>";
	echo "</div>";
}

$sql = "SELECT  SUM(data_termino-data_inicio) AS total_horas,
		COUNT(hd_chamado)             AS total_chamados,
		tbl_fabrica.nome
	FROM tbl_hd_chamado_atendente
	JOIN tbl_admin      USING(admin)
	JOIN tbl_hd_chamado USING(hd_chamado)
	JOIN tbl_fabrica    ON tbl_hd_chamado.fabrica = tbl_fabrica.fabrica
	WHERE data_inicio > '2007-06-01 00:00:00'
	GROUP BY tbl_fabrica.nome;";
$res = pg_exec ($con,$sql);
if (pg_numrows($res) > 0) {

	echo "<table width = '500'  cellpadding='0' cellspacing='0' border='0' align='center'>";
	echo "<tr>";
	echo "<td background='imagem/fundo_tabela_top_esquerdo_azul_claro.gif' ><img src='../imagens/pixel.gif' width='9'></td>"; //linha esquerda - 2 linhas
	echo "<td background='imagem/fundo_tabela_top_centro_azul_claro.gif'   align = 'center' width='100%' style='font-family: arial ; color:#666666'><b> Hora Fábrica</b></td>";//centro
	echo "<td background='imagem/fundo_tabela_top_direito_azul_claro.gif' ><img src='../imagens/pixel.gif' width='9'></td>";//linha direita - 2 linhas
	echo "</tr>";
	echo "<tr>";
	echo "<td background='imagem/fundo_tabela_centro_esquerdo.gif' ><img src='../imagens/pixel.gif' width='9'></td>";//coluna esquerda
	echo "<td>";
	
	echo "<table width='100%'>";

	for ($x = 0 ; $x < pg_numrows($res) ; $x++){
		$total_horas     = trim(pg_result($res,$x,total_horas));
		$total_chamados  = trim(pg_result($res,$x,total_chamados));
		$nome            = trim(pg_result($res,$x,nome));

		echo "<tr class='Conteudo'>";
		echo "<td align='left' height='15'>$nome</td><td>";
		echo "<td align='left'>$total_horas</td>";
		echo "<td>$total_chamados</td>";
		echo "</tr>";
	}
	echo "</table>";
	
	
	echo "</td>";
	
	echo "<td background='imagem/fundo_tabela_centro_direito.gif' ><img src='../imagens/pixel.gif' width='9'></td>";// coluna direita
	
	echo "</tr>";
	
	echo "<tr>";
	echo "<td background='imagem/fundo_tabela_baixo_esquerdo.gif'><img src='../imagens/pixel.gif' width='9'></td>";
	echo "<td background='imagem/fundo_tabela_baixo_centro.gif' align = 'center' width='100%'></td>";
	echo "<td background='imagem/fundo_tabela_baixo_direito.gif'><img src='../imagens/pixel.gif' width='9'></td>";
	echo "</tr>";
	echo "</table>";
}

include "rodape.php";

 ?>
</body>
</html>
