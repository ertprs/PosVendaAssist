<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

include "funcoes.php";

$sql = "SELECT	tbl_tipo_posto.distribuidor                                                      ,
				to_char(tbl_posto_fabrica.inicio_nf_garantia,'DD/MM/YYYY') AS inicio_nf_garantia
		FROM	tbl_tipo_posto
		JOIN	tbl_posto_fabrica USING(tipo_posto)
		WHERE	tbl_posto_fabrica.posto   = $login_posto
		AND		tbl_posto_fabrica.fabrica = $login_fabrica
		AND		tbl_tipo_posto.distribuidor IS true";
$res = pg_exec ($con,$sql);

if (pg_result($res,0,distribuidor) <> 't'){
	header("Location: pedido_relacao.php?entrou=s");
	exit;
}

$inicio_nf_garantia = trim(pg_result($res,0,inicio_nf_garantia));


$title = "Relação de Pedido dos Postos";
$layout_menu = 'pedido';
include "cabecalho.php";

$status       = $_GET['status'];
$codigo_posto = $_GET['codigo_posto'];
$data_emissao = $_GET['data_emissao'];

// USADO NO sql
if($status == 1){
	$tipo_selecionado = 'NOTNULL';
	$titulo_pedido = 'PEDIDOS ATENDIDOS TOTAL';
}else{
	$tipo_selecionado = 'IS NULL';
	$titulo_pedido = 'PEDIDOS EM ABERTO';
}

?>

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
.table_line {
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	border: 0px solid;
}
</style>

<p>
<!-- 
<table width="750" border="0" cellpadding="0" cellspacing="0" align="center" bgcolor="#ffffff">
<tr>
	<td width='50%' height="27" valign="middle" align="center" bgcolor="#FFFFFF">
		<a href="<? echo $PHP_SELF; ?>?tipo=1">Pedidos novos</a>
	</td>
	<td height="27" valign="middle" align="center" bgcolor="#FFFFFF">
		<a href="<? echo $PHP_SELF; ?>?tipo=2">Pedidos já lançados</a>
	</td>
</tr>
</table>
 -->

<table width="700" border="0" cellpadding="0" cellspacing="1" align="center" bgcolor="#ffffff">
<FORM name='frm_pedido' METHOD=GET ACTION="<? echo $PHP_SELF; ?>">
<tr>
	<td height="20" valign="middle" align="center" class="menu_top">CÓDIGO POSTO</td>
<!-- 	<td valign="middle" align="center" class="menu_top">NOME POSTO</td> -->
	<td valign="middle" align="center" class="menu_top">DATA</td>
	<td valign="middle" align="center" class="menu_top">SITUAÇÃO</td>
</tr>
<tr>
	<td valign=top  align="center" class="table_line"><INPUT TYPE="text" NAME="codigo_posto" value='<? echo $codigo_posto; ?>' size='17'></td>
<!-- 	<td valign=top  align="center" class="table_line"><INPUT TYPE="text" NAME="posto_nome" value='<? echo $posto_nome; ?>' size='30'></td> -->
	<td valign=top align="center" class="table_line"><INPUT TYPE="text" NAME="data_emissao" value='<? echo $data_emissao; ?>' size='12'><br><font face='arial' size='1'>Ex.: 25/10/2004</font></td>
	<td valign=top  align="center" class="table_line">
		<select class=frm NAME="status">
			<option SELECTED></option>
			<option value='1' <? IF ($status == 1) echo "selected"; ?>>ATENDIDOS TOTAL</option>
			<option value='2' <? IF ($status == 2) echo "selected"; ?>>EM ABERTO</option>
		</select>
	</td>
</tr>
<tr>
	<td colspan=4 align="center">
		<input type="hidden" name="btn_acao" value="">
		<img src='imagens/btn_continuar.gif' onclick="javascript: if (document.frm_pedido.btn_acao.value == '' ) { document.frm_pedido.btn_acao.value='gravar' ; document.frm_pedido.submit() } else { alert ('Aguarde submissão') }" ALT="Gravar pedido" border='0' style='cursor: pointer'>
	</td>
</tr>
</FORM>
</table>
<br>

<table width="700" border="0" cellpadding="0" cellspacing="0" align="center" bgcolor="#ffffff">
<tr>
	<td height="27" valign="middle" align="center" class="menu_top">
		<? echo $titulo_pedido; ?>
	</td>
</tr>
<tr>
	<td valign="top" align="center">
		<p>

		<table width="100%" border="0" cellspacing="1" cellpadding="1" align='center'>
		<tr height="20">
			<td class='menu_top'>Posto</td>
			<td class='menu_top'>Data</td>
			<td class='menu_top'>Tipo</td>
			<td class='menu_top'>Linha</td>
			<td class='menu_top'>Pedido</td>
<!--
			<td class='menu_top'>Status</td>
			<td class='menu_top'>Tipo Pedido</td>
			<td class='menu_top'>Valor Total</td>
			<td class='menu_top' colspan=2>&nbsp;</td>
-->
		</tr>

<?
	if (strlen($codigo_posto) > 0 OR strlen($status) > 0 OR strlen($data_emissao) > 0){
		$sql = "SELECT  tbl_pedido.*,
						to_char(tbl_pedido.data,'DD/MM/YYYY') as datas,
						tbl_tipo_pedido.descricao AS tipo_pedido_descricao,
						tbl_posto.nome,
						tbl_posto_fabrica.codigo_posto,
						tbl_linha.nome AS linha_nome
				FROM    tbl_pedido
				JOIN    tbl_linha ON tbl_pedido.linha = tbl_linha.linha
				JOIN    tbl_tipo_pedido   USING (tipo_pedido)
				JOIN    tbl_posto         USING (posto)
				JOIN    tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
										AND  tbl_posto_fabrica.fabrica = $login_fabrica
				WHERE   tbl_pedido.distribuidor = $login_posto
				AND     tbl_pedido.fabrica      = $login_fabrica ";
		if (strlen($codigo_posto) > 0) $sql .= " AND tbl_posto_fabrica.codigo_posto = '$codigo_posto' ";
		if (strlen($data_emissao) > 0 and $data_emissao < $inicio_nf_garantia) {
			$sql .= " AND tbl_pedido.data >= '". formata_data($inicio_nf_garantia) ." 00:00:00'";
		}else{
			if (strlen($data_emissao) > 0) {
				$sql .= " AND tbl_pedido.data BETWEEN '". formata_data($data_emissao). " 00:00:00' AND '".formata_data($data_emissao)." 23:59:59'";
			}else{
				if (strlen($inicio_nf_garantia) > 0) {
					$sql .= " AND tbl_pedido.data >= '". formata_data($inicio_nf_garantia) ." 00:00:00'";
				}
			}
		}

		if ($login_posto == 4311) {
			if ($status == "2") {
				$sql .= " AND tbl_pedido.status_pedido_posto <> 13 ";
			}else{
				$sql .= " AND tbl_pedido.status_pedido_posto =  13 ";
			}
		}else{
			if (strlen($status) > 0) $sql .= " AND tbl_pedido.pedido_atendido_total $tipo_selecionado ";
		}

		$sql .= " ORDER BY tbl_pedido.data DESC, tbl_posto.nome ASC ";


		$sqlCount  = "SELECT count(*) FROM (";
		$sqlCount .= $sql;
		$sqlCount .= ") AS count";

		// ##### PAGINACAO ##### //
		require "_class_paginacao.php";

		// definicoes de variaveis
		$max_links = 11;				// máximo de links à serem exibidos
		$max_res   = 50;				// máximo de resultados à serem exibidos por tela ou pagina
		$mult_pag  = new Mult_Pag();	// cria um novo objeto navbar
		$mult_pag->num_pesq_pag = $max_res; // define o número de pesquisas (detalhada ou não) por página

		$res = $mult_pag->executar($sql, $sqlCount, $con, "otimizada", "pgsql");

		// ##### PAGINACAO ##### //

//		$res = pg_exec ($con,$sql);
		
		for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
			$cor = "#FFFFFF";
			if ($i % 2 == 0) $cor = '#F1F4FA';
?>
		
		<tr bgcolor="<? echo $cor ?>" >
			<td class='table_line'><? echo pg_result($res,$i,codigo_posto)." - ".pg_result($res,$i,nome) ?></td>
			<td class='table_line' align='center'><? echo pg_result ($res,$i,datas) ?></td>
			<td class='table_line' align='center'><? echo pg_result ($res,$i,tipo_pedido_descricao) ?></td>
			<td class='table_line' align='center'><? echo pg_result ($res,$i,linha_nome) ?></a></td>
			<td class='table_line' align='center'><a href="pedido_posto_faturamento.php?pedido=<? echo pg_result ($res,$i,pedido) ?>"><? echo pg_result ($res,$i,pedido) ?></a></td>
		</tr>
<?
		}

		echo "		</table>";

		// ##### PAGINACAO ##### //
		// links da paginacao
		echo "<br>";

		echo "<div>";

		if($pagina < $max_links)
			$paginacao = pagina + 1;
		else
			$paginacao = pagina;

		// paginacao com restricao de links da paginacao

		// pega todos os links e define que 'Próxima' e 'Anterior' serão exibidos como texto plano
		$todos_links		= $mult_pag->Construir_Links("strings", "sim");

		// função que limita a quantidade de links no rodape
		$links_limitados	= $mult_pag->Mostrar_Parte($todos_links, $coluna, $max_links);

		for ($n = 0; $n < count($links_limitados); $n++)
			echo "<font color='#DDDDDD'>".$links_limitados[$n]."</font>&nbsp;&nbsp;";

		echo "</div>";

		$resultado_inicial = ($pagina * $max_res) + 1;
		$resultado_final   = $max_res + ( $pagina * $max_res);
		$registros         = $mult_pag->Retorna_Resultado();

		$valor_pagina   = $pagina + 1;
		$numero_paginas = intval(($registros / $max_res) + 1);

		if ($valor_pagina == $numero_paginas) $resultado_final = $registros;

		if ($registros > 0){
			echo "<br>";
			echo "<div>";
			echo "Resultados de <b>$resultado_inicial</b> a <b>$resultado_final</b> do total de <b>$registros</b> registros.";
			echo "<font color='#cccccc' size='1'>";
			echo " (Página <b>$valor_pagina</b> de <b>$numero_paginas</b>)";
			echo "</font>";
			echo "</div>";
		}
		// ##### PAGINACAO ##### //
	}
?>
	</td>
</tr>
</table>

<BR>
<!-- 
<table width="750" border="0" cellpadding="0" cellspacing="0" align="center" bgcolor="#ffffff">
<tr>
	<td width='50%' height="27" valign="middle" align="center" bgcolor="#FFFFFF">
		<font size=2><a href="<? echo $PHP_SELF; ?>?tipo=1">Pedidos Atendidos Total</a></font>
	</td>
	<td height="27" valign="middle" align="center" bgcolor="#FFFFFF">
		<font size=2><a href="<? echo $PHP_SELF; ?>?tipo=2">Pedidos Em Aberto</a></font>
	</td>
</tr>
</table>
 -->
<p>

<? include "rodape.php"; ?>
