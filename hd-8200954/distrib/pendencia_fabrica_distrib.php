<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$title='Pendência da Fábrica com o Distribuidor';
$gera_automatico = trim($_GET["gera_automatico"]);

if ($gera_automatico != 'automatico'){
	include 'autentica_usuario.php';
}

include "gera_relatorio_pararelo_include.php";

include "cabecalho.php";


//PERIODO DE ATENDIMENTO DO DISTRIB
if (strlen(trim($_POST["data_inicial"])) > 0) $data_inicial = trim($_POST["data_inicial"]);
if (strlen(trim($_GET["data_inicial"])) > 0)  $data_inicial = trim($_GET["data_inicial"]);

if (strlen(trim($_POST["data_final"])) > 0) $data_final = trim($_POST["data_final"]);
if (strlen(trim($_GET["data_final"])) > 0)  $data_final = trim($_GET["data_final"]);

if (strlen(trim($_POST["btn_acao"])) > 0) $btn_acao = trim($_POST["btn_acao"]);
if (strlen(trim($_GET["btn_acao"])) > 0)  $btn_acao = trim($_GET["btn_acao"]);

?>

<html>
<head>
<title>Itens da NF de Entrada</title>
</head>

<body>

<? include 'menu.php' ?>

<style type="text/css">

.menu_top {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: bold;
	border: 0px solid;
	color:#ffffff;
	background-color: #596D9B
}

.link{
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 12px;
	font-weight: normal;
}

</style>

<center><h1>Pendência da Fábrica com o Distrib</h1></center>

<form name='frm_per' method='POST' action='<?=$PHP_SELF?>'>

<table width='250' border='1' cellspacing='1' cellpadding='3' align='center' style='border-collapse:collapse'>
<caption>Pesquisa por Data do Pedido</caption>
<tr>
	<td align='right'>
		Data Inicial
	</td>
	<td>
		<input type='text' name='data_inicial' id='data_inicial' size='12' maxlength='11' value='<?=$data_inicial?>'>
	</td>
</tr>
<tr>
	<td align='right'>
		Data Final
	</td>
	<td>
		<input type='text' name='data_final' id='data_final' size='12' maxlength='10' value='<?=$data_final?>'>
	</td>
</tr>
<tr>
	<td colspan='2'  align='center'>
		<INPUT TYPE='submit' name='btn_acao' id='btn_acao' value='Clique aqui para Listar / Atualizar'>
	</td>
</tr>
</table>
</form>

<?

if (strlen($data_inicial) > 0) {
	$data_inicial = substr($data_inicial,6,4)."-".substr($data_inicial,3,2)."-".substr($data_inicial,0,2);
}
if (strlen($data_final) > 0) {
	$data_final = substr($data_final,6,4)."-".substr($data_final,3,2)."-".substr($data_final,0,2);
}


if (strlen($btn_acao) > 0 && strlen($msg_erro) == 0) {
	include "gera_relatorio_pararelo.php";
}

if ($gera_automatico != 'automatico' and strlen($msg_erro)==0){
	include "gera_relatorio_pararelo_verifica.php";
}

if (strlen($msg_erro) > 0) { ?>
	<table width="600" border="0" cellspacing="0" cellpadding="2" align="center" class="Error">
		<tr>
			<td><?echo $msg_erro?></td>
		</tr>
	</table>
	<br>
	<? 
} 

if (strlen($btn_acao)>0) {
	$sql = "
			SELECT
					tbl_pedido.pedido,
					TO_CHAR(tbl_pedido.data,'DD/MM/YYYY') AS data,
					tbl_posto.nome,
					tbl_posto_fabrica.codigo_posto,
					tbl_pedido_item.peca,
					tbl_peca.referencia,
					tbl_peca.descricao,
					tbl_pedido_item.qtde,
					tbl_pedido_item.qtde_faturada,
					CASE WHEN tbl_pedido_item.qtde_faturada_distribuidor > tbl_pedido_item.qtde THEN tbl_pedido_item.qtde ELSE tbl_pedido_item.qtde_faturada_distribuidor END AS qtde_faturada_distribuidor,
					tbl_pedido_item.qtde_cancelada,
					tbl_tabela_item.preco AS preco,
					tbl_tabela_item.preco * (tbl_pedido_item.qtde_faturada_distribuidor - tbl_pedido_item.qtde_faturada ) AS total
			FROM tbl_pedido 
			JOIN tbl_pedido_item      USING(pedido)
			JOIN tbl_peca             USING(peca)
			JOIN tbl_posto            USING(posto)
			JOIN tbl_posto_fabrica    ON tbl_posto_fabrica.posto = tbl_posto.posto AND tbl_posto_fabrica.fabrica IN (".implode(",", $fabricas).")
			JOIN tbl_posto_linha ON tbl_posto_linha.posto = tbl_pedido.distribuidor AND tbl_posto_linha.linha = tbl_pedido.linha
			JOIN tbl_linha ON tbl_linha.linha = tbl_posto_linha.linha AND tbl_linha.fabrica = tbl_pedido.fabrica
			JOIN tbl_tabela_item ON tbl_tabela_item.tabela = tbl_posto_linha.tabela AND tbl_tabela_item.peca = tbl_pedido_item.peca
			WHERE tbl_pedido.fabrica      IN (".implode(",", $fabricas).")
			AND   tbl_pedido.distribuidor = $login_posto
			AND ( ( TO_CHAR(CURRENT_DATE - tbl_pedido.data,'DD')::integer > 90 AND qtde_faturada_distribuidor > 0  AND qtde_cancelada = 0 AND qtde_faturada < (CASE WHEN tbl_pedido_item.qtde_faturada_distribuidor > tbl_pedido_item.qtde THEN tbl_pedido_item.qtde ELSE tbl_pedido_item.qtde_faturada_distribuidor END) )
					OR 
				( qtde_faturada < (CASE WHEN tbl_pedido_item.qtde_faturada_distribuidor > tbl_pedido_item.qtde THEN tbl_pedido_item.qtde ELSE tbl_pedido_item.qtde_faturada_distribuidor END) AND qtde_cancelada > 0 ))
			AND   tbl_pedido.condicao     = 7
			";
	if (strlen($data_inicial)>0 AND strlen($data_final)>0){
		$sql .= " AND tbl_pedido.data BETWEEN '$data_inicial 00:00:01' AND '$data_final 23:59:59' ";
	}
	$sql .= " ORDER BY tbl_pedido.data ASC";
	#echo nl2br($sql);
	#exit;
	$res = pg_exec ($con,$sql);
	$numero_registros = pg_numrows($res);

	if ($numero_registros > 0) {

		echo "<br><p>Reltório gerado em ".date("d/m/Y")." as ".date("H:i")."</p><br>";

		echo "<table width='650' border='1' cellspacing='1' cellpadding='3' align='center'>";
		echo "<tr>\n";
		echo "<td class='menu_top'>Pedido</td>\n";
		echo "<td class='menu_top'>Data Pedido</td>\n";
		echo "<td class='menu_top'>Código Posto</td>\n";
		echo "<td class='menu_top'>Nome</td>\n";
		echo "<td class='menu_top'>Peça</td>\n";
		echo "<td class='menu_top'>Descrição</td>\n";
		echo "<td class='menu_top'>Qtde Solicitada</td>\n";
		echo "<td class='menu_top'>Qtde Fat. Fabrica</td>\n";
		echo "<td class='menu_top'>Qtde Fat. Distrib</td>\n";
		echo "<td class='menu_top'>Qtde Cancelada</td>\n";
		echo "<td class='menu_top'>Qtde Pendente</td>\n";
		echo "<td class='menu_top'>Preço Unitário</td>\n";
		echo "<td class='menu_top'>Preço Total</td>\n";
		echo "</tr>\n";

		$total_geral = 0;

		for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
			$pedido							= trim(pg_result($res,$i,pedido)) ;
			$data							= trim(pg_result($res,$i,data)) ;
			$nome							= trim(pg_result($res,$i,nome)) ;
			$codigo_posto					= trim(pg_result($res,$i,codigo_posto)) ;
			$peca							= trim(pg_result($res,$i,peca));
			$referencia						= trim(pg_result($res,$i,referencia));
			$descricao						= trim(pg_result($res,$i,descricao));
			$qtde							= trim(pg_result($res,$i,qtde));
			$qtde_faturada					= trim(pg_result($res,$i,qtde_faturada));
			$qtde_faturada_distribuidor		= trim(pg_result($res,$i,qtde_faturada_distribuidor)) ;
			$qtde_cancelada					= trim(pg_result($res,$i,qtde_cancelada)) ;
			$preco							= trim(pg_result($res,$i,preco)) ;
			$total							= trim(pg_result($res,$i,total)) ;

			$total_geral += $total;

			$cor = "#ffffff";
			if ($i % 2 == 0) {
				$cor = "#DDDDEE";
			}

			#$qtde_divergente = $qtde_faturada_distribuidor - $qtde_faturada;
			$qtde_pendente = $qtde_faturada_distribuidor - $qtde_faturada;

			echo "<tr style='font-size: 10px' bgcolor='$cor'>\n";
			echo "<td align='center' nowrap><a href='../pedido_finalizado.php?pedido=".$pedido."' target='_new'>".$pedido."</a></td>\n";
			echo "<td align='center' nowrap>".$data."</td>\n";
			echo "<td align='center' nowrap>".$codigo_posto."</td>\n";
			echo "<td align='left' nowrap>".$nome."</td>\n";
			echo "<td align='center' nowrap><acronym title='Preço da Peça:R$ $preco'>".$referencia."</acronym></td>\n";
			echo "<td align='left' nowrap>".$descricao."</td>\n";
			echo "<td align='center' nowrap>".$qtde."</td>\n";
			echo "<td align='center' nowrap>".$qtde_faturada."</td>\n";
			echo "<td align='center' nowrap>".$qtde_faturada_distribuidor."</td>\n";
			echo "<td align='center' nowrap>".$qtde_cancelada."</td>\n";
			echo "<td align='center' nowrap bgcolor='#FFE6B7'><acronym title='Qtde pendente é a quantidade que a fábrica falta atender o distribuidor'>".$qtde_pendente."</acronym></td>\n";
			echo "<td align='right' nowrap>".number_format($preco,2,","," ")."</td>\n";
			echo "<td align='right' nowrap bgcolor='#D9FFB7'>".number_format($total,2,","," ")."</td>\n";
			echo "</tr>\n";
		}
		echo "<tr>";
		echo "<td colspan='12' align='right'><b>Total</b></td>";
		echo "<td align='right' nowrap>".number_format($total_geral,2,","," ")."</td>";
		echo "</tr>";
		echo "</table>\n";
		echo "<br>";
		echo "<p>Total: $numero_registros registros encontrados</p>";
	}else{
		echo "<p>Nenhum resultado encontrado</p>";
	}
	
}
?>
</body>
<p>
<? include "rodape.php"; ?>