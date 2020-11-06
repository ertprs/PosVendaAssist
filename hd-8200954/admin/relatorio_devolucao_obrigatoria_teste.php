<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include "funcoes.php";
$admin_privilegios="gerencia";
include 'autentica_admin.php';

$msg_erro = "";


# Pesquisa pelo AutoComplete AJAX
$q = strtolower($_GET["q"]);
if (isset($_GET["q"])){
	$busca      = $_GET["busca"];
	$tipo_busca = $_GET["tipo_busca"];

	if (strlen($q)>2){

		if ($tipo_busca=="posto"){
			$sql = "SELECT tbl_posto.cnpj, tbl_posto.nome, tbl_posto_fabrica.codigo_posto
					FROM tbl_posto
					JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
					WHERE tbl_posto_fabrica.fabrica = $login_fabrica ";

			if ($busca == "codigo"){
				$sql .= " AND tbl_posto_fabrica.codigo_posto = '$q' ";
			}else{
				$sql .= " AND UPPER(tbl_posto.nome) like UPPER('%$q%') ";
			}

			$res = pg_exec($con,$sql);
			if (pg_numrows ($res) > 0) {
				for ($i=0; $i<pg_numrows ($res); $i++ ){
					$cnpj = trim(pg_result($res,$i,cnpj));
					$nome = trim(pg_result($res,$i,nome));
					$codigo_posto = trim(pg_result($res,$i,codigo_posto));
					echo "$cnpj|$nome|$codigo_posto";
					echo "\n";
				}
			}
		}
	}
	exit;
}

$confirma_devolucao=$_GET['confirma_devolucao'];
$extrato=$_GET['extrato'];
if($confirma_devolucao=='sim' and strlen($extrato) > 0){
	$sql=" UPDATE tbl_extrato_extra set pecas_devolvidas='t'
			FROM  tbl_extrato
			WHERE tbl_extrato_extra.extrato=$extrato";
	$res=pg_exec($con,$sql);
	$msg_erro = pg_errormessage ($con);
}


if (strlen($_POST['btn_acao']) > 0) $btn_acao = $_POST['btn_acao'];

$codigo_posto = $_POST['codigo_posto'];
if (strlen ($codigo_posto) == 0) $codigo_posto = $_GET['codigo_posto'];

$posto_nome   = $_POST['posto_nome'];
if (strlen ($posto_nome) == 0) $posto_nome = $_GET['posto_nome'];

$posto = $_GET['posto'];

if (strlen ($posto) > 0) {
	$sql = "SELECT	tbl_posto_fabrica.codigo_posto,
					tbl_posto.nome
			FROM tbl_posto_fabrica
			JOIN tbl_posto USING (posto)
			WHERE tbl_posto_fabrica.fabrica    = $login_fabrica
			AND tbl_posto_fabrica.posto = '$posto'";
	$res = pg_exec ($con,$sql);
	$codigo_posto = pg_result ($res,0,0);
	$posto_nome   = pg_result ($res,0,1	);
}

if (strlen ($codigo_posto) > 0 AND strlen($posto) == 0) {
	$sql = "SELECT	tbl_posto_fabrica.posto
			FROM tbl_posto_fabrica
			WHERE tbl_posto_fabrica.fabrica    = $login_fabrica
			AND tbl_posto_fabrica.codigo_posto = '$codigo_posto'";
	$res = pg_exec ($con,$sql);
	$posto = pg_result ($res,0,0);
}

$layout_menu = "gerencia";
$title = "Relatório de Devolução de Peças Obrigatória";
include "cabecalho.php";

?>
<p>
<style type="text/css">

.menu_top {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: x-small;
	font-weight: bold;
	border: 1px solid;
	color:#ffffff;
	background-color: #596D9B
}

a:link.top{
	color:#ffffff;
}
a:visited.top{
	color:#ffffff;
}
a:hover.top{
	color:#ffffff;
}

.table_line {
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	border: 0px solid;
}
.cor_devolucao{
	color:#0066FF;
}
</style>

<? include "javascript_pesquisas.php" ;?>

<? include "javascript_calendario.php"; ?>
<script type='text/javascript' src='js/jquery.autocomplete.js'></script>
<link rel="stylesheet" type="text/css" href="js/jquery.autocomplete.css" />
<script type='text/javascript' src='js/jquery.bgiframe.min.js'></script>
<script type='text/javascript' src='js/dimensions.js'></script>

<script language="JavaScript">
$().ready(function() {

	function formatItem(row) {
		return row[2] + " - " + row[1];
	}

	/* Busca pelo Código */
	$("#codigo_posto").autocomplete("<?echo $PHP_SELF.'?tipo_busca=posto&busca=codigo'; ?>", {
		minChars: 3,
		delay: 150,
		width: 350,
		matchContains: true,
		formatItem: formatItem,
		formatResult: function(row) {return row[2];}
	});

	$("#codigo_posto").result(function(event, data, formatted) {
		$("#posto_nome").val(data[1]) ;
	});

	/* Busca pelo Nome */
	$("#posto_nome").autocomplete("<?echo $PHP_SELF.'?tipo_busca=posto&busca=nome'; ?>", {
		minChars: 3,
		delay: 150,
		width: 350,
		matchContains: true,
		formatItem: formatItem,
		formatResult: function(row) {return row[1];}
	});

	$("#posto_nome").result(function(event, data, formatted) {
		$("#codigo_posto").val(data[2]) ;
		//alert(data[2]);
	});



});
</script>


<?

if($msg_erro){
	echo "<table width='700px' align='center' border='0' bgcolor='#FFFFFF' cellspacing='1' cellpadding='0'>";
	echo "<tr align='center'>";
	echo "<td class='error'>";
	echo $msg_erro;
	echo "</td>";
	echo "</tr>";
	echo "</table>";
}
?>

<p>


<form name="frm_posto" method="post" action="<? echo $PHP_SELF ?>">

<TABLE width="450" align="center" border="0" cellspacing='0' cellpadding='0' class='PesquisaTabela'>
	<caption>Pesquisa</caption>
	<TR>
	<td nowrap>
		Código do Posto
		<br>
		<input class="frm" type="text" name="codigo_posto" id="codigo_posto" size="15" value="<? echo $codigo_posto ?>">&nbsp;<img src='imagens/lupa.png' border='0' align='absmiddle' style="cursor:pointer" onclick="javascript: fnc_pesquisa_posto (document.frm_posto.codigo_posto,document.frm_posto.posto_nome,'codigo')"></A>
	</td>

	<td nowrap>
		Nome do Posto
		<br>
		<input class="frm" type="text" name="posto_nome" id="posto_nome" size="30" value="<? echo $posto_nome ?>" >&nbsp;<img src='imagens/lupa.png' style="cursor:pointer" border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_posto (document.frm_posto.codigo_posto,document.frm_posto.posto_nome,'nome')" style="cursor:pointer;"></A>
	</td>
</tr>
<tr>
	<td colspan='2'><br><br><a href = 'peca_consulta.php' target='_blank'>Clique Aqui</a> para ver as Peças em Devolução Obrigatória<br>&nbsp;</td>
</tr>
<tr>
	<td colspan='2'>
		<center><input type='hidden' name='btn_finalizar' value='0'>
			<IMG src="imagens_admin/btn_pesquisar_400.gif" onclick="javascript: if ( document.frm_posto.btn_finalizar.value == '0' ) { document.frm_posto.btn_finalizar.value='1'; document.frm_posto.submit() ; } else { alert ('Aguarde submiss?o da OS...'); }" style="cursor:pointer " alt='Clique AQUI para pesquisar'></center>
	</td>
</tr>


</table>
</form>
<br>


<?
flush();

if (strlen ($codigo_posto) > 0 OR strlen ($posto) > 0 ) {

	$sql = "SELECT	extrato,
			TO_CHAR (data_geracao,'DD/MM/YYYY') AS data_geracao,
			TO_CHAR (aprovado,'DD/MM/YYYY')     AS aprovado,
			total,
			pecas_devolvidas
		FROM tbl_extrato
		JOIN tbl_extrato_extra using(extrato)
		WHERE posto   = $posto
		AND   fabrica = $login_fabrica
		ORDER BY extrato DESC
		LIMIT 30";
	$res = pg_exec ($con,$sql);

	if (pg_numrows($res) == 0){
		echo "<table align='center'><tr><td><font color = '#FF3300'size='3' face='verdana'><b>Este posto não possui extratos</b></font></td></tr></table>";
	}
	if (pg_numrows($res) > 0){
		#hd 15606
		if($login_fabrica==6){
			echo "<div align='center' style='position: relative; center: 0'>";
			echo "<table border='0' cellspacing='0' cellpadding='0'>";
			echo "<tr height='18'>";
			echo "<td width='18' bgcolor='#33CCFF'>&nbsp;</td>";
			echo "<td align='left'><font size='1'><b>&nbsp; Devolução Confirmada</b></font></td>";
			echo "</tr>";
			echo "</table>";
			echo "</div><br>";
		}
		echo "<table width='600' align='center' border='0' cellspacing='2' cellpadding='2'>";
		echo "<tr class='menu_top'>";
		echo "<td align='center' width = '25%' class='menu_top'>EXTRATO</td>";
		echo "<td align='center' width = '25%' class='menu_top'>DATA GERAÇÃO</td>";
		echo "<td align='center' width = '25%' class='menu_top'>DATA APROVAÇÃO</td>";
		echo "<td align='center' width = '25%' class='menu_top'>TOTAL</td>";
		echo "</tr>";

		if($login_fabrica==35){
			echo pg_numrows ($res);
			for ($i = 0 ; $i < pg_numrows ($res) ; $i++ ) {
				$extrato          = pg_result($res,$i,extrato);
				$data_geracao     = pg_result ($res,$i,data_geracao);
				$aprovacao        = pg_result ($res,$i,aprovado);
				$total            = pg_result ($res,$i,total);
				$total            = number_format($total,2,",",".");
				$pecas_devolvidas = pg_result ($res,$i,pecas_devolvidas);
				$cor = "#F7F7F7";
				if ($i % 2 == 0) $cor = '#F1F4FA';
				//HD 15606
				if($login_fabrica==6 and $pecas_devolvidas=='t'){
					$cor= "#33CCFF";
				}

				echo "<tr class='table_line' bgcolor='$cor'>";
				echo "<td align='center'>";
				echo "<a href='../os_extrato_pecas_retornaveis_teste.php?extrato=$extrato'>$extrato</a></td>";
				echo "<td align='center'>$data_geracao</td>";
				echo "<td align='center'>$aprovacao</td>";
				echo "<td align='right'>$total &nbsp;</td>";
				echo "</tr>";
			}		
		}else{

			for ($i = 0 ; $i < pg_numrows ($res) ; $i++ ) {
				$extrato          = pg_result($res,$i,extrato);
				$data_geracao     = pg_result ($res,$i,data_geracao);
				$aprovacao        = pg_result ($res,$i,aprovado);
				$total            = pg_result ($res,$i,total);
				$total            = number_format($total,2,",",".");
				$pecas_devolvidas = pg_result ($res,$i,pecas_devolvidas);
				$cor = "#F7F7F7";
				if ($i % 2 == 0) $cor = '#F1F4FA';
				//HD 15606
				if($login_fabrica==6 and $pecas_devolvidas=='t'){
					$cor= "#33CCFF";
				}

				echo "<tr class='table_line' bgcolor='$cor'>";
				echo "<td align='center'>";
				echo "<a href='$PHP_SELF?extrato=$extrato&posto=$posto'>$extrato</a></td>";
				echo "<td align='center'>$data_geracao</td>";
				echo "<td align='center'>$aprovacao</td>";
				echo "<td align='right'>$total &nbsp;</td>";
				echo "</tr>";
			}
		}
		echo "</table><br><br>";
	}//fim if
}


#----------------------- Lista Pecas de um extrato -----------------
$extrato = $_GET['extrato'];

if (strlen ($extrato) > 0) {

	###########################################################################
	## ESTAVA PEGANDO CONFORME A TELA EM QUE O POSTO VISUALIZA
	$sql = "SELECT	tbl_peca.referencia,
					tbl_peca.descricao ,
					tbl_extrato_extra.pecas_devolvidas,
					SUM (tbl_os_item.qtde) AS qtde
			FROM    tbl_os
			JOIN    tbl_os_extra          ON tbl_os.os                               = tbl_os_extra.os
			JOIN    tbl_produto           ON tbl_os.produto                          = tbl_produto.produto
			JOIN    tbl_os_produto        ON tbl_os.os                               = tbl_os_produto.os
			JOIN    tbl_os_item           ON tbl_os_produto.os_produto               = tbl_os_item.os_produto
			JOIN    tbl_servico_realizado ON tbl_servico_realizado.servico_realizado = tbl_os_item.servico_realizado
			JOIN    tbl_peca              ON tbl_os_item.peca                        = tbl_peca.peca
			JOIN    tbl_extrato           ON tbl_extrato.extrato                     = tbl_os_extra.extrato
			JOIN    tbl_extrato_extra     ON tbl_os_extra.extrato                    = tbl_extrato_extra.extrato
			WHERE   tbl_os_extra.extrato = $extrato
			AND     tbl_extrato.fabrica  = $login_fabrica
			AND     tbl_os_item.liberacao_pedido        IS TRUE ";
			if (($posto <> 17674 and $login_fabrica == 6) or $login_fabrica <> 6) {
				$sql .=" AND     tbl_peca.devolucao_obrigatoria      IS TRUE ";
			}
			$sql .= " AND     tbl_servico_realizado.gera_pedido   IS TRUE
			AND     tbl_servico_realizado.troca_de_peca IS TRUE
			GROUP BY tbl_peca.referencia, tbl_peca.descricao,tbl_extrato_extra.pecas_devolvidas
			ORDER BY SUM (tbl_os_item.qtde);";

	#HD 17436
	if ($login_fabrica==11){
		$sql = "SELECT	tbl_peca.referencia,
						tbl_peca.descricao ,
						'' AS pecas_devolvidas,
						SUM (tbl_faturamento_item.qtde) AS qtde
				FROM    tbl_faturamento
				JOIN    tbl_faturamento_item USING(faturamento)
				JOIN    tbl_peca             USING(peca)
				WHERE   tbl_faturamento.extrato_devolucao = $extrato
				AND     tbl_faturamento.fabrica           = $login_fabrica
				AND     tbl_faturamento.distribuidor IS NULL
				AND     tbl_peca.devolucao_obrigatoria      IS TRUE
				GROUP BY tbl_peca.referencia, tbl_peca.descricao
				ORDER BY SUM (tbl_faturamento_item.qtde) DESC;";
	}
	if ($login_fabrica==51){
		$sql = "SELECT	tbl_peca.referencia,
						tbl_peca.descricao ,
						'' AS pecas_devolvidas,
						SUM (tbl_faturamento_item.qtde) AS qtde
				FROM    tbl_faturamento
				JOIN    tbl_faturamento_item USING(faturamento)
				JOIN    tbl_peca             USING(peca)
				WHERE   tbl_faturamento.extrato_devolucao = $extrato
				AND     tbl_faturamento.fabrica           = $login_fabrica
				AND     tbl_faturamento.distribuidor = 4311
				AND     tbl_peca.devolucao_obrigatoria      IS TRUE
				GROUP BY tbl_peca.referencia, tbl_peca.descricao
				ORDER BY SUM (tbl_faturamento_item.qtde) DESC;";
	}

	$res = pg_exec($con,$sql);

	if (pg_numrows($res) == 0) {?>
		<table width='500' align='center' border='0' cellspacing='2' cellpadding='2'>
			<tr class='table_line'>
				<td align='center'>
					<font size='2'>
					<?
						if ($login_fabrica == 11 OR $login_fabrica == 51){
							echo "Não existe peças com devolução obrigatória neste extrato.";
						}else{
							echo "Não existe peças com devolução obrigatória lançadas em suas Ordens de Serviço";
						}
					?>
					</font>
				</td>
			</tr>
		</table>
	<?
	}else{
		echo "<table width='600' align='center' border='0' cellspacing='2' cellpadding='2'>";
		echo "<tr class='menu_top'><td align='center' colspan='3'>PEÇAS DE DEVOLUÇÃO OBRIGATÓRIA <br>DO POSTO \"".$posto_nome."\" REFERENTE AO EXTRATO \"".$extrato."\" </td></tr>";
		echo "<tr class='menu_top'>";

		echo "<td align='center' class='menu_top' style='cursor: hand;' onclick='javascript:document.location=\"$PHP_SELF?extrato=$extrato&posto=$posto&orderx=referencia&type=$typeRetorno\"' title='Ordena pelo CODIGO'>";
		echo "COD. PEÇA ";
		if ($orderx =='referencia' and $type =='DESC'){
			echo"<img src='imagens/ordena_asc.gif'>";
		}else if ($orderx == 'referencia' and $type == 'ASC'){
			echo"<img src='imagens/ordena_desc.gif'>";
		}
		echo "</td>";

		echo "<td align='center' class='menu_top' style='cursor: hand;' onclick='javascript:document.location=\"$PHP_SELF?extrato=$extrato&posto=$posto&orderx=descricao&type=$typeRetorno\"' title='Ordena pela DESCRIÇÃO'>";
		echo "DESCRIÇÃO ";
		if ($orderx =='descricao' and $type =='DESC'){
			echo"<img src='imagens/ordena_asc.gif' alt=''>";
		}else if ($orderx =='descricao' and $type == 'ASC'){
			echo"<img src='imagens/ordena_desc.gif' alt=''>";
		}
		echo "</td>";

		echo "<td align='center'>QTDE</td>";
		echo "</tr>";

		for ($i=0; $i < pg_numrows($res); $i++){
			$referencia_peca  = trim(pg_result ($res,$i,referencia));
			$descricao_peca   = trim(pg_result ($res,$i,descricao));
			$qtde             = trim(pg_result ($res,$i,qtde));
			$pecas_devolvidas = trim(pg_result ($res,$i,pecas_devolvidas));

			$cor = "#F7F7F7";
			if ($i % 2 == 0) $cor = '#F1F4FA';
			echo "<tr class='table_line' bgcolor='$cor' >";
			echo "<td align='center'>$referencia_peca</td>";
			echo "<td align='left'>$descricao_peca</td>";
			echo "<td align='center'>$qtde</td>";
			echo "</tr>";

		}
		//HD 15606
		if($login_fabrica==6 and $pecas_devolvidas=='f'){
			echo "<tr>";
			echo "<td colspan='100%' align='center' nowrap><a href='$PHP_SELF?posto=$posto&extrato=$extrato&confirma_devolucao=sim'><font size=4>Confirmar Devolução</font></a></td>";
			echo "</tr>";
		}

		echo "</table>";

	}
}

if (1==2) {

	$sql = "SELECT  tbl_peca.referencia,
					tbl_peca.descricao ,
					tbl_os_item.qtde   ,
					tbl_os.sua_os
			FROM    tbl_os
			JOIN    tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_os.posto
			JOIN    tbl_os_produto    USING (os)
			JOIN    tbl_os_item       USING (os_produto)
			JOIN    tbl_peca          USING (peca)
			WHERE   tbl_os.fabrica                 = $login_fabrica
			AND     tbl_os.finalizada NOTNULL
			AND     tbl_peca.devolucao_obrigatoria is true
			AND     tbl_posto_fabrica.codigo_posto = $codigo_posto
			ORDER BY tbl_os_item.os_item DESC
			LIMIT 10 ";
	$res = pg_exec($con,$sql);
	if (pg_numrows($res) == 0) {?>
		<table width='700' align='center' border='0' cellspacing='2' cellpadding='2'>
			<tr class='table_line'>
				<td align='center'><font size='2'>Não existe peças com devolução obrigatória lançadas em suas Ordens de Serviço</font></t>
			</tr>
		</table>
	<?}else{

		echo "<table width='700' align='center' border='0' cellspacing='2' cellpadding='2'>";
		echo "<tr class='menu_top'>";
		echo "<td align='center'><font size='2'>COD. PEÇA</font></td>";
		echo "<td align='center'><font size='2'>DESCRIÇÃO</font></td>";
		echo "<td align='center'><font size='2'>QTDE</font></td>";
		echo "</tr>";

		for ($i=0; $i < pg_numrows($res); $i++){
			$referencia_peca = trim(pg_result ($res,$i,referencia));
			$descricao_peca  = trim(pg_result ($res,$i,descricao));
			$qtde            = trim(pg_result ($res,$i,qtde));

			$cor = "#F7F7F7";
			if ($i % 2 == 0) $cor = '#F1F4FA';

			echo "<tr class='table_line' bgcolor='$cor'>";
			echo "<td align='center'>$referencia_peca</td>";
			echo "<td align='left'>$descricao_peca</td>";
			echo "<td align='center'>$qtde</td>";
			echo "</tr>";

		}
	}
}

// ORDENA??O DO SQL (VEM VIA GET)
/*switch ($_GET['order']){
	case 'referencia':	$order = "tbl_peca.referencia";				break;
	case 'descricao' :	$order = "tbl_peca.descricao";				break;
	case 'qtde'		 :	$order = "tbl_os_item.qtde";				break;
	case 'os'		 :	$order = "LPAD(tbl_os.sua_os,20,'0')";		break;
	case 'posto'	 :	$order = "tbl_posto.nome";					break;
	default			 :	$order = "tbl_peca.referencia";				break;
}

if ($_GET['type'] == 'ASC')
	$typeRetorno = 'DESC';
else
	$typeRetorno = 'ASC';

$sql = "SELECT  tbl_peca.referencia,
				tbl_peca.descricao ,
				tbl_os_item.qtde   ,
				tbl_os.sua_os      ,
				tbl_posto.nome
		FROM    tbl_os
		JOIN    tbl_os_produto    USING (os)
		JOIN    tbl_os_item       USING (os_produto)
		JOIN    tbl_peca          USING (peca)
		JOIN    tbl_posto         USING (posto)
		WHERE   tbl_os.fabrica = $login_fabrica
		AND     tbl_os.finalizada    NOTNULL
		AND     tbl_peca.devolucao_obrigatoria is true
		ORDER BY $order $type";
$res = pg_exec($con,$sql);

if (pg_numrows($res) == 0) {
	echo "<table width='700' align='center' border='0' cellspacing='2' cellpadding='2'>";
	echo "<tr class='table_line'>";
	echo "<td align='center'><font size='2'>N?o existe pe?as com devolu??o obrigat?ria lan?adas em suas Ordens de Servi?o</font></t>";
	echo "</tr>";
	echo "</table>";
}else{
	echo "<table width='700' align='center' border='0' cellspacing='2' cellpadding='2'>";
	echo "<tr class='menu_top'>";
	echo "<td align='center' style='cursor:hand;' onclick='javascript:document.location=\"$PHP_SELF?order=referencia&type=$typeRetorno\"' title='Ordena pelo C?DIGO DO PRODUTO'><font size='2'>C?digo</font></td>";
	echo "<td align='center' style='cursor: hand;' onclick='javascript:document.location=\"$PHP_SELF?order=descricao&type=$typeRetorno\"' title='Ordena pela DESCRI??O DO PRODUTO'><font size='2'>Descri??o</font></td>";
	echo "<td align='center' style='cursor:hand;' onclick='javascript:document.location=\"$PHP_SELF?order=qtde&type=$typeRetorno\"' title='Ordena pela QUANTIDADE'><font size='2'>Qtde</font></td>";
	echo "<td align='center' style='cursor: hand;' onclick='javascript:document.location=\"$PHP_SELF?order=os&type=$typeRetorno\"' title='Ordena pelo N?MERO DA OS'><font size='2'>OS</font></td>";
	echo "<td align='center' style='cursor: hand;' onclick='javascript:document.location=\"$PHP_SELF?order=posto&type=$typeRetorno\"' title='Ordena pelo NOME DO POSTO'><font size='2'>POSTO</font></td>";
	echo "</tr>";

	for ($i = 0 ; $i < pg_numrows ($res) ; $i++){
		$referencia = trim(pg_result($res,$i,referencia));
		$descricao  = trim(pg_result($res,$i,descricao));
		$qtde       = trim(pg_result($res,$i,qtde));
		$sua_os     = trim(pg_result($res,$i,sua_os));
		$posto      = trim(pg_result($res,$i,nome));

		$cor = "#F7F7F7";
		if ($i % 2 == 0) $cor = '#F1F4FA';

		echo "<tr class='table_line' bgcolor='$cor'>";
		echo "<td align='center'>$referencia</td>";
		echo "<td align='left'>$descricao</td>";
		echo "<td align='center'>$qtde</td>";
		echo "<td align='center'>$sua_os</td>";
		echo "<td align='left' nowrap title='$posto'>".substr($posto,0,20)."</td>";
		echo "</tr>";
	}

	echo "</table>";
}

echo "<br><br>";*/

echo "<br>";
echo "<br>";
include "rodape.php";

?>
