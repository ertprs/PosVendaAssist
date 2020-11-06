<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="auditoria,gerencia";
include 'autentica_admin.php';

if($login_fabrica <> 59){
	header("Location: pedido_parametros.php");
	exit;
}

$title = "RELATÓRIO DE PEÇAS - PEDIDO NÃO EXPORTADO";
$layout_menu = "callcenter";

include 'cabecalho.php';
?>

<style type="text/css">
.Titulo {
	text-align: center;
	font-family: Arial;
	font-size: 10px;
	font-weight: bold;
	color: #FFFFFF;
	background-color: #485989;
}
.Conteudo {
	font-family: Arial;
	font-size: 11px;
	font-weight: normal;
}
.ConteudoBranco {
	font-family: Arial;
	font-size: 11px;
	color:#FFFFFF;
	font-weight: normal;
}
.Mes{
	font-size: 8px;
}
</style>
<script language='javascript'>

function fnc_pesquisa_posto(campo, campo2, tipo) {
	if (tipo == "codigo" ) {
		var xcampo = campo;
	}

	if (tipo == "nome" ) {
		var xcampo = campo2;
	}

	if (xcampo.value != "") {
		var url = "";
		url = "posto_pesquisa_2.php?campo=" + xcampo.value + "&tipo=" + tipo ;
		janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=600, height=400, top=18, left=0");
		janela.codigo  = campo;
		janela.nome    = campo2;
		janela.focus();
	}
}
</script>
<?

$btn_acao = $_POST['btn_acao'];
$msg_erro = "";

if ($btn_acao == "gravar") {
	$qtde_pedido   = $_POST['qtde_pedido'];
	for ($i = 0 ; $i <= $qtde_pedido ; $i++) {
		$pedido = $_POST['exportar'.$i];
		if (strlen ($pedido) > 0) {
			$sql2 = "UPDATE tbl_pedido SET
						exportado        = current_timestamp,
						exportado_manual = 't'              ,
						status_pedido    = 2
					WHERE pedido = $pedido 
					AND   fabrica = $login_fabrica";
			$res2 = pg_exec($con,$sql2);
			if (pg_errormessage ($con) > 0) $msg_erro .= pg_errormessage ($con);
		}
	}
	if (strlen ($msg_erro) == 0) {
		echo "<script language='JavaScript'>
				alert('Gravado com sucesso!');
			</script>";
	}
}

$btn_consultar = $_POST['acao'];
if ($btn_consultar == "consultar") {
	$codigo_posto = $_POST['codigo_posto'];
	$sql= "SELECT tbl_posto_fabrica.codigo_posto,
		tbl_pedido.pedido,
		tbl_os.sua_os,
		tbl_produto.referencia AS produto_referencia,
		tbl_produto.descricao  AS produto_descricao,
		tbl_peca.referencia    AS peca_referencia,
		tbl_peca.descricao     AS peca_descricao,
		tbl_pedido_item.qtde   AS qtde
		FROM tbl_pedido_item
		JOIN tbl_pedido  on tbl_pedido.pedido = tbl_pedido_item.pedido
		JOIN tbl_os_item on tbl_os_item.pedido = tbl_pedido_item.pedido and tbl_os_item.peca = tbl_pedido_item.peca
		AND tbl_os_item.pedido_item = tbl_pedido_item.pedido_item
		JOIN tbl_os_produto on tbl_os_produto.os_produto = tbl_os_item.os_produto
		JOIN tbl_os         on tbl_os.os                 = tbl_os_produto.os
		JOIN tbl_posto_fabrica on tbl_posto_fabrica.posto = tbl_os.posto and tbl_posto_fabrica.fabrica = 59 ";
		if(strlen($codigo_posto)>0){
			$sql .= " AND tbl_posto_fabrica.codigo_posto = '$codigo_posto'";
		}
		$sql .= " JOIN tbl_peca       on tbl_pedido_item.peca = tbl_peca.peca
		JOIN tbl_produto    on tbl_os.produto = tbl_produto.produto
		WHERE tbl_pedido.fabrica = $login_fabrica AND tbl_pedido.exportado IS NULL
		ORDER BY tbl_posto_fabrica.codigo_posto, tbl_pedido.pedido
		";

	$res = pg_exec($con,$sql);
	if (pg_numrows($res)>0){
		$total_pecas=0;
		echo"<form name='frm_exportar' method='post' action='$PHP_SELF'>";
		echo "<br><br><br>\n";
		echo "<table border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#d2e4fc' align='center' width='500'>\n";
		echo "<tr class='Titulo'>\n";
		echo "	<td >POSTO</td>\n";
		echo "	<td >PEDIDO</td>\n";
		echo "	<td >EXPORTAR</td>\n";
		echo "	<td >OS</td>\n";
		echo "	<td >MODELO PRODUTO</td>\n";
		echo "	<td >PECA</td>\n";
		echo "	<td >QTDE</td>\n";
		echo "</tr>\n";

		$pedido_anterior = "";
		$qtde_pedido = 0;
		for ($i=0;$i<pg_numrows($res);$i++){

			$codigo_posto       = pg_result($res,$i,codigo_posto);
			$pedido             = pg_result($res,$i,pedido);
			$sua_os             = pg_result($res,$i,sua_os);
			$produto_referencia = pg_result($res,$i,produto_referencia);
			$produto_descricao  = pg_result($res,$i,produto_descricao);
			$peca_referencia    = pg_result($res,$i,peca_referencia);
			$peca_descricao     = pg_result($res,$i,peca_descricao);
			$qtde               = pg_result($res,$i,qtde);
			$total_pecas       += $qtde;

			if($cor=="#F1F4FA")$cor = '#F7F5F0';
			else               $cor = '#F1F4FA';

			echo "<tr class='Conteudo'align='center'>\n";
			echo "	<td bgcolor='$cor' align='center' nowrap>$codigo_posto</td>\n";
			echo "	<td bgcolor='$cor' align='center' nowrap>$pedido</td>\n";
			echo "<td bgcolor='$cor' align='center' nowrap>";
			if($pedido_anterior<>$pedido){
				echo "<input type='checkbox' name='exportar$qtde_pedido' value='$pedido'";
				$qtde_pedido++;
			}
			echo "	</td>\n";
			echo "	<td bgcolor='$cor' align='left' nowrap>$sua_os</td>\n";
			echo "	<td bgcolor='$cor' align='left' nowrap>$produto_referencia - $produto_descricao</td>\n";
			echo "	<td bgcolor='$cor' align='left' nowrap>$peca_referencia - $peca_descricao</td>\n";
			echo "	<td bgcolor='$cor' nowrap>$qtde&nbsp;</td>\n";
			echo "</tr>\n";
		}
		echo "<tr class='Conteudo'>\n";
		echo "	<td colspan='5'><B>Total</b></td>\n";
		echo "	<td >$total_pecas</td>\n";
		echo "</tr>\n";
		echo "</table>\n";
		echo "<p>";
		echo "<input type='hidden' name='qtde_pedido' value='$qtde_pedido'>";
		echo "<input type='hidden' name='btn_acao' value=''>";
		echo "<img src='imagens_admin/btn_gravar.gif' style='cursor: pointer;' onclick=\"javascript: if (document.frm_exportar.btn_acao.value == '' ) { document.frm_exportar.btn_acao.value='gravar' ; document.frm_exportar.submit() } else { alert ('Aguarde submissão') }\" ALT='Gravar formulário' border='0'>";
		echo "NOTA! Não é possível exportar um item de um pedido! Uma item selecionado quer dizer que todo o pedido será exportado!";
		echo "<p>";
		echo "</form>";
	}else{
		echo "<br><center>Nenhum resultado encontrado</center>";
	}
}
?>

<form name="frm_relatorio_pecas_nao_exportado" method="POST" action="<?echo $PHP_SELF?>">

<table width="450" align="center" border="0" cellspacing="0" cellpadding="2">
	<tr class="Titulo">
		<td colspan="4">Escolha o posto para realizar a pesquisa.</td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF">
		<td>Código Posto</td>
		<td>Nome Posto</td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF">
		<td>
			<input type='text' name='codigo_posto' size='15' value='<? echo $codigo_posto ?>' class='frm'>
			<img border="0" src="imagens_admin/btn_lupa.gif" style="cursor: hand;" align="absmiddle" alt="Clique aqui para pesquisar postos pelo código" onclick="javascript: fnc_pesquisa_posto (document.frm_relatorio_pecas_nao_exportado.codigo_posto, document.frm_relatorio_pecas_nao_exportado.posto_nome, 'codigo')">
		</td>
		<td>
			 <input type='text' name='posto_nome' size='30' value='<? echo $posto_nome ?>' class='frm'>
			<img border="0" src="imagens_admin/btn_lupa.gif" style="cursor: hand;" align="absmiddle" alt="Clique aqui para pesquisar postos pelo código" onclick="javascript: fnc_pesquisa_posto (document.frm_relatorio_pecas_nao_exportado.codigo_posto, document.frm_relatorio_pecas_nao_exportado.posto_nome, 'nome')">
		</td>
	</tr>
	<tr bgcolor="#D9E2EF">
		<td colspan="2" align="center">
		<input type="hidden" name="acao" id="acao">
			<img border="0" src="imagens/btn_pesquisar_400.gif" onclick="document.frm_relatorio_pecas_nao_exportado.acao.value='consultar'; document.frm_relatorio_pecas_nao_exportado.submit();" style="cursor: hand;" alt="Preencha as opções e clique aqui para pesquisar">
		</td>
	</tr>
</table>
</form>

<?
include "rodape.php" ;
?>