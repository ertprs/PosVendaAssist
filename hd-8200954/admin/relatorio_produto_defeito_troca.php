<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="financeiro,gerencia,call_center";
include 'autentica_admin.php';
include 'includes/funcoes.php';
include 'funcoes.php';
include "monitora.php";
$cachebypass = md5(time());

$layout_menu = "Gerencia";
$title = "RELATÓRIO DE DEFEITOS POR PRODUTOS ";
$msg_erro = '';

# Pesquisa pelo AutoComplete AJAX
$q = strtolower($_GET["q"]);
if (isset($_GET["q"])){
	$tipo_busca = $_GET["busca"];
	if (strlen($q)>2){
		$sql = "SELECT
					tbl_posto.cnpj,
					tbl_posto.nome,
					tbl_posto_fabrica.codigo_posto
				FROM tbl_posto
					JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
				WHERE tbl_posto_fabrica.fabrica = $login_fabrica ";
		$sql .= ($tipo_busca == "codigo") ? " AND tbl_posto_fabrica.codigo_posto = '$q' " :  " AND UPPER(tbl_posto.nome) like UPPER('%$q%') ";
		$res = pg_exec($con,$sql);
		if (pg_numrows ($res) > 0) {
			for ($i=0; $i<pg_numrows ($res); $i++ ){
				$cnpj         = trim(pg_result($res,$i,cnpj));
				$nome         = trim(pg_result($res,$i,nome));
				$codigo_posto = trim(pg_result($res,$i,codigo_posto));
				echo "$codigo_posto|$nome|$cnpj";
				echo "\n";
			}
		}
	}
	exit;
}

include "cabecalho.php";
include "javascript_calendario.php";?>

<script type="text/javascript" charset="utf-8">
	$(function(){
		$('#data_inicial').datePicker({startDate:'01/01/2000'});
		$('#data_final').datePicker({startDate:'01/01/2000'});
		$("#data_inicial").maskedinput("99/99/9999");
		$("#data_final").maskedinput("99/99/9999");
	});
</script>

<script language="JavaScript" src="js/qTip.js" type="text/JavaScript"></script>
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
		width: 250px;
	}
</style>

<script language="JavaScript" type="text/javascript">
	window.onload = function(){
		tooltip.init();
	}
</script>

<script type="text/javascript" src="js/bibliotecaAJAX.js"></script>
<script language='javascript' src='../ajax.js'></script>
<script language="javascript">
	
	function chamaAjax(linha, data_inicial, data_final, codigo_linha, produto, cache){
		if (document.getElementById('div_sinal_' + linha).innerHTML == '+'){
			requisicaoHTTP('GET','mostra_produto_defeito_troca_defeito_ajax.php?linha='+linha+'&data_inicial='+data_inicial+'&data_final='+data_final+'&codigo_linha='+codigo_linha+'&produto='+produto+'&cachebypass='+cache, true , 'div_detalhe_carrega');
		}else{
			document.getElementById('div_detalhe_' + linha).innerHTML = "";
			document.getElementById('div_sinal_' + linha).innerHTML = '+';
		}
	}
	function load(linha){
		document.getElementById('div_detalhe_' + linha).innerHTML = "<img src='a_imagens/ajax-loader.gif'>";
	}
	function div_detalhe_carrega (campos){
		campos_array = campos.split("|");
		linha = campos_array [0];
		document.getElementById('div_detalhe_' + linha).innerHTML = campos_array[1];
		document.getElementById('div_sinal_' + linha).innerHTML = '-';
	}

</script>

<link rel="stylesheet" type="text/css" href="js/jquery.autocomplete.css" />
<script type="text/javascript" src="js/jquery.bgiframe.min.js"></script>
<script type="text/javascript" src="js/jquery.autocomplete.js"></script>
<script type="text/javascript" src="js/jquery.dimensions.js"></script>

<?
flush();
if (strlen($_GET['data_inicial']) > 0)
	$data_inicial = $_GET['data_inicial'];
else
	$data_inicial = $_POST['data_inicial'];

if (strlen($_GET['data_final']) > 0)
	$data_final   = $_GET['data_final'];
else
	$data_final   = $_POST['data_final'];

if (strlen($_GET['aux_linha']) > 0)
	$aux_linha   = $_GET['aux_linha'];
else
	$aux_linha   = $_POST['aux_linha'];

if($btn_acao=="Consultar"){
	if((strlen($data_inicial) > 0 AND $data_inicial!="dd/mm/aaaa") AND (strlen($data_final)>0 AND $data_final!="dd/mm/aaaa")){
		$ver_data = "Select case when '$data_inicial' < '$data_final' then true else false end";
		$res = @pg_exec($con,$ver_data);
		$resposta = pg_result($res,0,0);
		if ($resposta == 'f'){
			$msg_erro = "A DATA INICIAL NÃO PODE SER SUPERIOR A DATA FINAL";
		}
		if (strlen($msg_erro) == 0) {
			$fnc = @pg_exec($con,"SELECT fnc_formata_data('$data_inicial')");
			if (strlen ( pg_errormessage ($con) ) > 0) {
				$msg_erro = pg_errormessage ($con) ;
			}
			if (strlen($msg_erro) == 0)
				$aux_data_inicial = @pg_result ($fnc,0,0);
		}

		if (strlen($erro) == 0) {
			if (strlen($msg_erro) == 0) {
				$fnc = @pg_exec($con,"SELECT fnc_formata_data('$data_final')");
					if (strlen ( pg_errormessage ($con) ) > 0) {
					$erro = pg_errormessage ($con) ;
				}
				if (strlen($msg_erro) == 0)
					$aux_data_final = @pg_result ($fnc,0,0);
			}
		}
	}else{
		$msg_erro = "ENTRE COM O PERÍODO PARA FILTRAGEM";
	}
}
?>
<br>
<br>
<?
if(strlen($msg_erro)>0){
	echo "<table width='700' border='0' cellpadding='5' cellspacing='1' align='center'>";
		echo "<tr>";
			echo "<td class='Erro'>$msg_erro</td>";
		echo "</tr>";
	echo "</table>";
}
?>
<br>
<!-- *** Processo de formatação de LAY-OUT *** -->
<form name='frm_relatorio' METHOD='POST' ACTION='<?=$PHP_SELF?>' align='center'>
	<br>
	<table width='450' class='Conteudo' style='background-color: #485989' border='0' cellpadding='5' cellspacing='1' align='center'>
		<tr>
			<td class='Titulo' background='imagens_admin/azul.gif'>
				Relatório de defeitos por produto
			</td>
		</tr>
		<tr>
			<td bgcolor='#DBE5F5'>
				<table width='90%' border='0' cellspacing='1' cellpadding='2' class='Conteudo' align='center'>
					<tr>
						<td>
							<table width='100%' align='left'>
								<tr class="Conteudo" bgcolor="#D9E2EF">
									<td width='32%' align='left' nowrap>Data Inicial (abertura)</td>
									<td width='32%' align='left' nowrap>Data Final (abertura)</td>
									<td width='64%' align='left' nowrap>Linha do Produto</td>
								</tr>
								<tr bgcolor="#D9E2EF">
									<td width='32%' align='left' nowrap>
										<input type="text" style="width: 80px" name="data_inicial" id="data_inicial" size="12" maxlength="10" class='Caixa' value= "<?=$data_inicial?>" title="Preencha aqui a data inicial (pesquisa por data de abertura da Ordem de Serviço)">
									</td>
									<td width='32%' align='left' nowrap>
										<input type="text" style="width: 80px" name="data_final" id="data_final" size="12" maxlength="10" class='Caixa' value="<?=$data_final?>" title="Preencha aqui a data final (pesquisa por data de abertura da Ordem de Serviço)">
									</td>
									<td width='64%' align='left' nowrap>
										<select name="linha" style="width: 130px" class="Caixa" title="Escolha a Linha de Produto">
											<?
												$sql = "SELECT  *
													FROM    tbl_linha
													WHERE   tbl_linha.fabrica = $login_fabrica
													ORDER BY tbl_linha.nome";
												$res = pg_exec ($con,$sql);
												for ($x = 0 ; $x < pg_numrows($res) ; $x++){
													$aux_linha = trim(pg_result($res,$x,linha));
													$aux_nome  = trim(pg_result($res,$x,nome));
													echo "<option value='$aux_linha'";
														if ($linha == $aux_linha){
															echo " SELECTED ";
															$mostraMsgLinha = "<br> da LINHA $aux_nome";
														}
													echo ">$aux_nome</option>\n";
												}
											?>
										</select>
									</td>
								</tr>
							</table>
						</td>
					</tr>
				</table>
				<center>
					<br>
					<img border="0" src="imagens/btn_pesquisar_400.gif" onclick="document.frm_relatorio.btn_acao.value='Consultar'; document.frm_relatorio.submit();" style="cursor: hand;" alt="Preencha as opções e clique aqui para pesquisar">
					<input type='hidden' name='btn_acao' value='<?=$acao?>'>
				</center>
			</td>
		</tr>
	</table>
</form>
<p>

<? if($btn_acao=="Consultar" AND strlen($msg_erro) == 0){
	// VERIFICAR O NOME DAS VARIÁVEIS
	$sql = "SELECT
				tbl_produto.descricao                                   AS descricao,
				tbl_os.produto                                          AS produto,
				tbl_produto.referencia                                  AS referencia,
				tbl_os.serie                                            AS serie,
				to_char(tbl_numero_serie.data_fabricacao,'DD/MM/YYYY')  AS data_fabricacao,
				COUNT(os)                                               AS qtde
			FROM tbl_os
				JOIN tbl_produto            ON tbl_produto.produto     = tbl_os.produto
				LEFT JOIN tbl_numero_serie  ON tbl_numero_serie.serie  = tbl_os.serie
			WHERE tbl_os.fabrica       = $login_fabrica
				AND tbl_produto.linha  = $linha
				AND tbl_os.data_abertura between '$aux_data_inicial 00:00:00' and '$aux_data_final 23:59:59' 
				AND tbl_os.defeito_constatado IS NOT NULL
			GROUP BY tbl_produto.descricao, tbl_os.produto, tbl_os.serie, tbl_numero_serie.data_fabricacao, tbl_produto.referencia
			ORDER BY data_fabricacao, qtde desc";
	$res = pg_exec ($con,$sql);
	
	if (pg_numrows($res) > 0){
		// INÍCIO DA PRIMEIRA LOCALIZAÇÃO DE DADOS (PRODUTO)
		echo "</font>";
			echo "<br>";
			echo "<table border='1' cellpadding='1' cellspacing='0' style='border-collapse: collapse' bordercolor='#d2e4fc' align='center' width='700'>";
				echo "<tr class='Titulo'>";
					echo "<td ></td>";
					echo "<td >Código</td>";
					echo "<td >Descrição do Produto</td>";
					echo "<td >Série</td>";
					echo "<td >Data de Fabricação</td>";
					echo "<td >Qtde</td>";
				echo "</tr>";
				$total = pg_numrows($res);
				$total_produtos = 0;
				if ($produto == ''){
					$produto = "0";
				}
				for ($i=0; $i<pg_numrows($res); $i++){
					$produto          = trim(pg_result($res,$i,produto));
					$descricao        = trim(pg_result($res,$i,descricao));
					$referencia       = trim(pg_result($res,$i,referencia));
					$serie            = trim(pg_result($res,$i,serie));
					$data_fabricacao  = trim(pg_result($res,$i,data_fabricacao));
					$qtde             = trim(pg_result($res,$i,qtde));
					if($cor=="#F1F4FA")
						$cor = '#F7F5F0';
					else
						$cor = '#F1F4FA';
					$total_produto = $total_produto + $qtde;
					echo "<tr>";
						echo "<td onMouseOver='this.style.cursor=\"pointer\" ; this.style.background=\"#cccccc\"'  onMouseOut='this.style.backgroundColor=\"#ffffff\" ' onClick=\"load($i);chamaAjax($i,'$aux_data_inicial','$aux_data_final','$linha','$produto','$cachebypass')\"><div id=div_sinal_$i>+</div></td>";
						echo "<td bgcolor='$cor' align='center' nowrap>$referencia</td>";
						echo "<td bgcolor='$cor' align='left' nowrap>$descricao</td>";
						echo "<td bgcolor='$cor' align='left' nowrap>$serie</td>";
						echo "<td bgcolor='$cor' align='left' nowrap>$data_fabricacao</td>";
						echo "<td bgcolor='$cor' nowrap>$qtde&nbsp;</td>";
					echo "</tr>";
					echo "<tr>";
						echo "<td colspan='6'>";
							echo "<div id='div_detalhe_$i'></div>";
						echo "</td>";
					echo "</tr>";
				}
				echo "<tr class='Conteudo'>";
					echo "<td align='right'colspan='5'>";
						echo "<b>TOTAL&nbsp;&nbsp;</b>";
					echo "</td>";
					echo "<td>$total_produto</td>";
				echo "</tr>";
			echo "</table>";
		echo "</form>";
	}
}
include "rodape.php" ?>