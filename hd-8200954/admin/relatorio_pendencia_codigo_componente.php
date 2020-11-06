<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'includes/funcoes.php';
include 'autentica_admin.php';
$admin_privilegios="gerencia";
$layout_menu = "gerencia";
$title = "RELATÓRIO DE PENDÊNCIA POR CÓDIGO DO COMPONENTE";
include 'cabecalho.php';
include "javascript_pesquisas.php";

$codigo_peca      = $_POST['codigo_peca'];
$peca_id          = $_POST['cod_peca'];
$descricao_peca   = $_POST['descricao_peca'];
$codigo_posto     = $_POST['codigo_posto'];
$posto_id         = $_POST['cod_posto'];
$descricao_posto  = $_POST['descricao_posto'];

?>
<script language="JavaScript" src="js/qTip.js" type="text/JavaScript"></script>
<link rel="stylesheet" type="text/css" href="js/jquery.autocomplete.css" />
<script type="text/javascript" src="js/jquery.js"></script>
<script type='text/javascript' src='js/jquery.bgiframe.min.js'></script>
<script type='text/javascript' src='js/jquery.autocomplete.js'></script>
<script type="text/javascript" src="js/jquery.dimensions.js"></script>
<script language='javascript' src='ajax.js'></script>
<script type="text/javascript" src="js/bibliotecaAJAX.js"></script>
<style type="text/css">
	.Titulo {
		text-align: center;
		font-family: Arial;
		font-size: 11px;
		font-weight: bold;
		color: #FFFFFF;
		background-color: #485989;
	}
	.Erro {
		text-align: center;
		font-family: Arial;
		font-size: 12px;
		font-weight: bold;
		color: #FFFFFF;
		background-color: #FF0000;
	}
	.Conteudo {
		text-align: left;
		font-family: Arial;
		font-size: 11px;
		font-weight: normal;
	}
	.Conteudo2 {
		text-align: center;
		font-family: Arial;
		font-size: 11px;
		font-weight: normal;
	}
	.Caixa{
		BORDER-RIGHT: #6699CC 1px solid;
		BORDER-TOP: #6699CC 1px solid;
		FONT: 8pt Arial ;
		BORDER-LEFT: #6699CC 1px solid;
		BORDER-BOTTOM: #6699CC 1px solid;
		BACKGROUND-COLOR: #FFFFFF
	}
	#tooltip{
		background: #5D92B1;
		border:2px solid #000;
		display:none;
		padding: 2px 4px;
		color: #FFFFFF;
		text-align: center;
		font-family: Arial;
		font-size: 11px;
		font-weight: normal;
		width: 200px;
	}
	.relcabecalho{
		background-color: #596D9B;
		border: 1px solid #d9e2ef;
		color: #FFFFFF;
		height: 25px;
		font-weight: bold;
	}
	.relerro{
		color: #FF0000;
		font-size: 11pt;
		padding: 20px;
		background-color: #F7F7F7;
		text-align: center;
	}
	.rellinha0{
		background-color: #F1F4FA;
		border: solid 1px #d9e2ef;
	}
	.rellinha1{
		background-color: #F7F5F0;
		border: solid 1px #d9e2ef;
	}
	.relinstrucoes{
		text-align: center;
		font-weight: bold;
		color: #FFFFFF;
		background:url('imagens_admin/azul.gif');
		height:25px;
	}
	.relopcoes{
		text-align: center;
		background-color: #DBE5F5;
		height: 30px;
		text-align: left;
		width: 696px;
	}
	.relprincipal{
		border-collapse: collapse;
		font-size: 8pt;
		font-family: Arial;
		font-weight: normal;
		border: #d9e2ef 1px solid; 
	}
	.reltitulo{
		text-align: center;
		background-color: #596D9B;
		height: 30px;
		font-weight: bold;
		color: #FFFFFF;
		background:url('imagens_admin/azul.gif');
	}
	.rellink{
		text-decoration: none;
		font-weigth: normal;
		color: #000000;
	}
</style>
<script language="JavaScript" type="text/javascript">
	window.onload = function(){
		tooltip.init();
	}
</script>

<script language="JavaScript">
$().ready(function() {
	function formatItem(row) {
		return row[0] + " - " + row[1];
	}
	function formatResult(row) {
		return row[0];
	}
});
function chamaAjax(peca, posto){
	if (document.getElementById('div_sinal_' + linha).innerHTML == '+'){
		requisicaoHTTP('GET','relatorio_pendencia_codigo_componente?linha='+linha+'&data_inicial='+data_inicial+'&data_final='+data_final+'&posto='+posto+'&produto='+produto+'&cachebypass='+cache, true , 'div_detalhe_carrega');
	}else{
		document.getElementById('div_detalhe_' + linha).innerHTML = "";
		document.getElementById('div_sinal_' + linha).innerHTML = '+';
	}
}
</script>
<script>
	function fnc_pesquisa_posto2 (campo, campo2, tipo){
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
$peca_referencia    =   $_POST['peca_referencia'];
$produto_referencia =   $_POST['produto_referencia'];
if(strlen($codigo_posto)>0){
	$sql = "SELECT posto
			FROM tbl_posto_fabrica
			WHERE codigo_posto = '$codigo_posto' AND fabrica = $login_fabrica";
	$res = @pg_exec($con,$sql);
	if(pg_num_rows($res)<1){
		$msg_erro .= " Selecione o Posto! ";
	}else{
		$posto = pg_result($res,0,0);
		if(strlen($posto)==0){
			$msg_erro .= " Selecione o Posto! ";
		}else{
			$cond_3 = " AND tbl_os.posto = $posto";
		}
	}
}
if(strlen($msg_erro)>0){
	echo "<table width='700' border='0' cellpadding='5' cellspacing='1' align='center'>";
		echo "<tr>";
			echo "<td class='Erro'>$msg_erro</td>";
		echo "</tr>";
	echo "</table>";
}
?>
<form name='frm_relatorio' method='post' action='<?=$PHP_SELF?>' align='center'>
	<br>
		<table width='530' class='Conteudo' style='background-color: #485989' border='0' cellpadding='5' cellspacing='1' align='center'>
			<tr>
				<td class='Titulo' background='imagens_admin/azul.gif'>
					RELATÓRIO DE PENDÊNCIA POR CÓDIGO DO COMPONENTE 
				</td>
			</tr>
			<tr>
				<td bgcolor='#DBE5F5'>
					<table width='90%' border='0' cellspacing='1' cellpadding='2' class='Conteudo' align='center'>
						<tr class="Conteudo" bgcolor="#D9E2EF">
							<td>Ref. Peça</td>
							<td>Descrição Peça</td>
						</tr>
						<tr bgcolor="#D9E2EF">
							<td>
								<input id="codigo_peca" name="codigo_peca" type="text" size = "12" class="Caixa" maxlength="20" title="Digite a referência/código da peça."  value="<? echo $codigo_peca ?>">
								<input id="cod_peca" name="cod_peca" type="hidden" >
								<img src='imagens/lupa.png' border='0' align='absmiddle' style="cursor:pointer" onclick="javascript: fnc_pesquisa_peca (document.frm_relatorio.codigo_peca, document.frm_relatorio.descricao_peca,'referencia')"title='Digite parte da referência/código da peça e clique na lupa para encontrar todas as peças com parte da referência/código'>
							</td>
							<td>
								<input id="descricao_peca" name="descricao_peca" type="text" size="70" class="Caixa" value="<? echo $descricao_peca ?>" title="Digite a descrição da peça.">
								<img src='imagens/lupa.png'  style="cursor:pointer" border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_peca (document.frm_relatorio.codigo_peca, document.frm_relatorio.descricao_peca,'descricao')"title='Digite parte da descrição da peça e clique na lupa para encontrar todas as peças com parte da descrição'>
							</td>
						</tr>
						<tr class="Conteudo" bgcolor="#D9E2EF">
							<td align='left' height='20'>
								Código Posto:&nbsp;
							</td>
							<td align='left' height >
								Nome do Posto
							</td>
						</tr>
						<tr>
							<td align='left' nowrap>
								<input class="Caixa" type="text" name="codigo_posto" id="codigo_posto" size="12" value="<? echo $codigo_posto ?>" title='Digite a referência/código do posto'>&nbsp;
								<input type="hidden" id="cod_posto" name="cod_posto" >
								<img src='imagens/lupa.png' border='0' align='absmiddle' style="cursor:pointer" onclick="javascript: fnc_pesquisa_posto2 (document.frm_relatorio.codigo_posto,document.frm_relatorio.descricao_posto,'codigo')"title='Digite código do posto e clique na lupa para buscar todos os postos com parte do código'>
							</td>
							<td  align='left' nowrap>
								<input class="Caixa" type="text" name="descricao_posto" id="descricao_posto" size="70" value="<? echo $descricao_posto ?>" title='Digite a descrição do posto'>&nbsp;
								<img src='imagens/lupa.png' style="cursor:pointer" border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_posto2 (document.frm_relatorio.codigo_posto,document.frm_relatorio.descricao_posto,'nome')" style="cursor:pointer;"title='Digite descrição do posto e clique na lupa para buscar todos os postos com parte da descrição'>
							</td>
						</tr>
						<tr bgcolor="#D9E2EF">
							<td colspan="4" align="center">
								<input type = "hidden" name ="acao">
								<img border="0" src="imagens/btn_pesquisar_400.gif" onclick="document.frm_relatorio.acao.value='PESQUISAR'; document.frm_relatorio.submit();" style="cursor: hand;" alt="Preencha as opções e clique aqui para pesquisar">
							</td>
						</tr>
					</table>
				</td>
			</tr>
		</table>
	<br>
</form>
<?
if ($_POST["acao"]== 'PESQUISAR'){
	if (strlen($acao) > 0 AND strlen($msg) == 0 AND (strlen($peca_id)>0 or strlen($codigo_posto)>0)) {
		if (strlen($peca_id)>0){
			$condpeca = " AND tbl_pedido_item.peca = $peca_id ";
		}
		if (strlen($codigo_posto)>0){
			$condposto = " AND tbl_pedido.posto = $posto_id ";
		}
		// PROCESSO DE SELEÇÃO DE DADOS
		$sql = "SELECT
					tbl_tipo_pedido.descricao,
					tbl_pedido.pedido,
					tbl_peca.referencia,
					tbl_peca.descricao,
					TO_CHAR(tbl_pedido.data, 'DD/MM/YYYY') AS data,
					tbl_pedido_item.qtde + tbl_pedido_item.qtde_faturada + tbl_pedido_item.qtde_cancelada
				FROM tbl_pedido_item
					JOIN tbl_pedido USING(pedido)
					JOIN tbl_tipo_pedido on tbl_pedido.tipo_pedido = tbl_tipo_pedido.tipo_pedido and tbl_tipo_pedido.fabrica=$login_fabrica
					JOIN tbl_peca USING (peca)
				WHERE tbl_pedido.fabrica=$login_fabrica 
				$condpeca
				$condposto ";
		$sql .= " AND tbl_pedido_item.qtde_faturada + tbl_pedido_item.qtde_cancelada < tbl_pedido_item.qtde;";

//		echo nl2br($sql);
//		exit;
		$res = pg_exec($con, $sql);
		// PROCESSO DE FORMAÇÃO DA TELA HTML
		$colunas = 12;
		if (pg_numrows($res) > 0){
			if ($_GET["descricao_peca"]) $title .= ": " . $_GET["descricao_peca"];
				echo "
						<table class=relprincipal align=center>
							<tr>
								<td colspan=$colunas class=reltitulo>
									$title
								</td>
							</tr>";
							for($i = 0; $i < pg_numrows($res); $i++){
								// INÍCIO DO CORPO DO CABEÇALHO
								$linhastela = $i % 20;
								if($linhastela == 0){
									if ($login_fabrica ==2){
										echo "
												<tr>
													<td width=20 class=relcabecalho>
														N
													</td>
													<td width=65 class=relcabecalho>
														TIPO PEDIDO
													</td>
													<td width=55 class=relcabecalho>
														PEDIDO
													</td>
													<td width=55 class=relcabecalho>
														REF. PEÇA
													</td>
													<td width=200 class=relcabecalho>
														DESCRIÇÃO DA PEÇA
													</td>
													<td width=55 class=relcabecalho>
														DATA
													</td>
													<td width=30 class=relcabecalho>
														QTDE
													</td>
													<td width=80 class=relcabecalho>
														OS
													</td>
													<td width=90 class=relcabecalho>
														CÓDIGO POSTO
													</td>
													<td width=250 class=relcabecalho>
														NOME DO POSTO
													</td>
												</tr>";
									}else{
										echo "
												<tr>
													<td width=20 rowspan=2 class=relcabecalho>
														N
													</td>
													<td width=65 rowspan=2 class=relcabecalho>
														TIPO PEDIDO
													</td>
													<td width=55 rowspan=2 class=relcabecalho>
														PEDIDO
													</td>
													<td width=55 rowspan=2 class=relcabecalho>
														REF. PEÇA
													</td>
													<td width=200 rowspan=2 class=relcabecalho>
														DESCRIÇÃO DA PEÇA
													</td>
													<td width=55 rowspan=2 class=relcabecalho>
														DATA
													</td>
													<td width=30 rowspan=2 class=relcabecalho>
														QTDE
													</td>
													<td width=80 rowspan=2 class=relcabecalho>
														OS
													</td>
													<td width=90 rowspan=2 class=relcabecalho>
														CÓDIGO POSTO
													</td>
													<td width=250 rowspan=2 class=relcabecalho>
														NOME DO POSTO
													</td>
													<td colspan=2 width=120 class=relcabecalho>
														CANCELAR ESTE ITEM
													</td>
												</tr>
												<tr>
													<td width=60 class=relcabecalho>
														SIM
													</td>
													<td width=60 class=relcabecalho>
														NÃO
													</td>
												</tr>";
									}
								}
								// FIM DO CORPO DO CABEÇALHO
								$linha = $i % 2;
								echo "
										<tr class=rellinha$linha>
											<td>
												" .($i + 1) . "
											</td>";
											for ($j = 0; $j < pg_num_fields($res); $j++) {
												$valor = pg_result($res, $i, $j);
												if ($j == 1) {//PEDIDO, se mudar a posição das colunas, favor modificar
													echo " <td><a href='pedido_admin_consulta.php?pedido=$valor' target='_blank'>$valor</td>";
												} else {
													echo " <td>$valor</td>";
												}
											}
											// SELEÇÃO DAS POSSÍVEIS OSs PARA CADA PEÇA DENTRO DO PEDIDO 
											$pedido = pg_result($res, $i, pedido);
											$ref_peca = pg_result($res, $i, referencia);
											if (strlen($peca_id)>0){
												$condpeca2 = " AND tbl_os_item.peca = $peca_id ";
											}
											$sql = "SELECT DISTINCT
														tbl_os.os      ,
														tbl_os.sua_os  ,
														tbl_posto.cnpj ,
														tbl_posto.nome
													FROM tbl_os_item
														JOIN tbl_os_produto ON tbl_os_item.os_produto=tbl_os_produto.os_produto
														JOIN tbl_os ON tbl_os_produto.os=tbl_os.os
														JOIN tbl_posto ON tbl_os.posto=tbl_posto.posto
														JOIN tbl_peca ON tbl_os_item.peca=tbl_peca.peca
													WHERE tbl_os_item.pedido = $pedido
														AND tbl_peca.referencia = '$ref_peca'
													$condpeca2";
											$res_os = pg_exec($con, $sql);
//											echo nl2br($sql);
//											exit;
											if(pg_numrows($res_os)==0){
												$sql = "SELECT DISTINCT
															'' as os       ,
															'' as sua_os   ,
															tbl_posto.cnpj ,
															tbl_posto.nome
														FROM tbl_pedido_item
															JOIN tbl_pedido ON tbl_pedido.pedido  = tbl_pedido_item.pedido
															JOIN tbl_posto  ON tbl_pedido.posto   = tbl_posto.posto
															JOIN tbl_peca ON tbl_pedido_item.peca = tbl_peca.peca
														WHERE tbl_pedido_item.pedido = $pedido
															AND tbl_peca.referencia = '$ref_peca'
															$condpeca";
												$res_os = pg_exec($con, $sql);
											}
											echo "<td>";
														for($j = 0; $j < pg_numrows($res_os); $j++){
															$os = pg_result($res_os, $j, os);
															$sua_os = pg_result($res_os, $j, sua_os);
															echo "<a target='_blank' href='os_press.php?os=$os'>$sua_os</a><br>";
														}
														@$cnpj = pg_result($res_os, 0, cnpj);
														@$nome = pg_result($res_os, 0, nome);
											echo "</td>
												  <td>
													$cnpj
												  </td>
												  <td>
													$nome
												  </td>";
											if ($login_fabrica !=2){
												echo "<td>
														[&nbsp;&nbsp;&nbsp;]
													  </td>
													  <td>
														[&nbsp;&nbsp;&nbsp;]
													  </td>";
											}
								echo "
										</tr>";
							}
				echo"
						</table>";
				// PROCESSO DE FORMAÇÃO DA TELA HTML
		}else{
			echo "
					<div class=relerro>
						Nenhuma pendência encontrada!
					</div>";
		}
	}else{
		echo "
			<div class=relerro>
				Por favor, escolha uma PEÇA ou um POSTO.
			</div>";
	}
}
?>
<link rel="stylesheet" type="text/css" href="js/jquery.autocomplete.css" />
<script type="text/javascript" src="js/jquery.js"></script>
<script type="text/javascript" src="js/jquery.bgiframe.min.js"></script>
<script type="text/javascript" src="js/jquery.autocomplete.js"></script>
<script type="text/javascript" src="js/jquery.dimensions.js"></script>
<script language="javascript">
	var hora = new Date();
	var engana = hora.getTime();
	$().ready(function() {
			$('#codigo_peca').autocomplete("autocomplete_peca_ajax.php?engana=" + engana,{
			minChars: 3,
			delay: 150,
			width: 450,
			scroll: true,
			scrollHeight: 200,
			matchContains: false,
			highlightItem: false,
			formatItem: function (row)   {return row[1]+"&nbsp;-&nbsp;"+row[2]},
			formatResult: function(row)  {return row[1];}
		});
		$('#codigo_peca').result(function(event, data, formatted) {
			$("#cod_peca").val(data[0]);
			$("#codigo_peca").val(data[1]);
			$("#descricao_peca").val(data[2]);
		});
		$('#descricao_peca').autocomplete("autocomplete_peca_ajax.php?engana=" + engana,{
			minChars: 3,
			delay: 150,
			width: 450,
			scroll: true,
			scrollHeight: 200,
			matchContains: false,
			highlightItem: false,
			formatItem: function (row)   {return row[1]+"&nbsp;-&nbsp;"+row[2]},
			formatResult: function(row)  {return row[1];}
		});
		$('#descricao_peca').result(function(event, data, formatted) {
			$("#cod_peca").val(data[0]);
			$("#codigo_peca").val(data[1]);
			$("#descricao_peca").val(data[2]);
		});
		$('#codigo_posto').autocomplete("autocomplete_posto_ajax.php?engana=" + engana,{
			minChars: 3,
			delay: 150,
			width: 450,
			scroll: true,
			scrollHeight: 200,
			matchContains: false,
			highlightItem: false,
			formatItem: function (row)   {return row[0]+"&nbsp;-&nbsp;"+row[1]},
			formatResult: function(row)  {return row[0];}
		});
		$('#codigo_posto').result(function(event, data, formatted) {
			$("#cod_posto").val(data[2]);
			$("#codigo_posto").val(data[0]);
			$("#descricao_posto").val(data[1]);
		});
		$('#descricao_posto').autocomplete("autocomplete_posto_ajax.php?engana=" + engana,{
			minChars: 3,
			delay: 150,
			width: 450,
			scroll: true,
			scrollHeight: 200,
			matchContains: false,
			highlightItem: false,
			formatItem: function (row)   {return row[0]+"&nbsp;-&nbsp;"+row[1]},
			formatResult: function(row)  {return row[0];}
		});
		$('#descricao_posto').result(function(event, data, formatted) {
			$("#cod_posto").val(data[2]);
			$("#codigo_posto").val(data[0]);
			$("#descricao_posto").val(data[1]);
		});
	})
</script>
<? include "../rodape.php" ?>
