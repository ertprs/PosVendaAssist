<?php

header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
header("Pragma: no-cache"); // HTTP/1.0

include_once "dbconfig.php";
include_once "includes/dbconnect-inc.php";
include_once "autentica_usuario.php";

if (!empty($_GET['extrato'])) {
	$extrato = trim($_GET['extrato']) ;
}
else if (!empty($_POST['extrato']) ){
	$extrato = trim($_POST['extrato']);
}

if (empty($extrato)){
	header("Location: os_extrato.php");
	exit;
}

$msg_erro   = "";
$botao_acao = $_POST['botao_acao'];

$numero_linhas = 0;

if (!empty($botao_acao) and $botao_acao == 'nf_servico') {
	$nf_extrato = trim($_POST['nf_extrato']);
	$data_nf    = trim($_POST['data_nf']);
	$nf_serie   = trim($_POST['nf_serie']);

	if(empty($nf_extrato) or empty($data_nf) ) {
		$msg_erro = "Preencha todos os campos de NF de serviço";
	}else{
		if(!empty($data_nf)) {
			list($di, $mi, $yi) = explode("/", $data_nf);
			if(!checkdate($mi,$di,$yi)) {
				$msg_erro = "Data NF Inválida";
			}else{
				$aux_data_nf = "$yi-$mi-$di";
			}
		}
	}

	if(empty($msg_erro) AND !empty($extrato)) {
		$sql = " SELECT extrato
				FROM tbl_extrato_extra
				WHERE extrato = $extrato";
		$res = pg_query($con,$sql);
		if(pg_num_rows($res) > 0){
			$res = pg_query ($con,"BEGIN TRANSACTION");

			$sql = " UPDATE tbl_extrato_extra SET
						nota_fiscal_mao_de_obra = '$nf_extrato',
						emissao_mao_de_obra     = '$aux_data_nf',
						nota_fiscal_serie_mao_de_obra = '$nf_serie'
					WHERE extrato = $extrato";
			$res = pg_query($con,$sql);
			$msg_erro = pg_last_error($con);

			if(empty($msg_erro)) {
				$res = pg_query ($con,"COMMIT TRANSACTION");
				$msg = "Gravado com sucesso";
			}else{
				$res = pg_query ($con,"ROLLBACK TRANSACTION");
				$msg_erro = "Erro ao gravar, tente novamente";
			}
		}
	}
}

if (!empty($botao_acao) and $botao_acao == 'nf_peca') {
	
	if (isset($_POST['qtde_notas'])) {
		$qtde_notas    = $_POST['qtde_notas'];
		$numero_linhas = $_POST['qtde_linha'];
		$aPecas_notas = explode(" # ", $_POST['pecas_notas']);
		$optante_simples_nacional = trim($_POST["optante_simples_nacional"]);
		$valor_icms = $_POST['aliq_icms'];
		$valor_icms = str_replace(",", ".", $valor_icms);


		$insere_numero_linhas = 0;

		if (empty($aPecas_notas[(count($aPecas_notas) - 1)])) {
			array_pop($aPecas_notas);
		}

		$notas = array();

		$sqlPf = "SELECT posto_fabrica FROM tbl_fabrica WHERE fabrica = $login_fabrica";
		$resPf = pg_query($con, $sqlPf);

		if (pg_num_rows($resPf) == 0) {
			$msg = "Erro ao gravar!";
		} else {
			$distribuidor = pg_fetch_result($resPf, 0, 'posto_fabrica');
		}

		for ($i = 0; $i < $qtde_notas; $i++) {
			$nf_peca       = trim($_POST["nf_peca_$i"]);
			$data_nf_peca  = trim($_POST["data_nf_peca_$i"]);
			$nf_peca_serie = trim($_POST["nf_peca_serie_$i"]);
			$total_nf_peca = $_POST["total_nf_peca_$i"];

			
			$nf_obrigatoria = "";
		    if ($login_fabrica == 74) {
				$nf_obrigatoria = (empty($nf_peca) or empty($data_nf_peca));
			}else {
				$nf_obrigatoria = (empty($nf_peca) or empty($data_nf_peca) or empty($nf_peca_serie));
			}


			if($nf_obrigatoria) {
				if ($login_fabrica == 74) {
					$msg_erro = "Preencha os campos da NF de peça";
				}else {
					$msg_erro = "Preencha todos os campos de NF de peça";
				}
				break;
			} else {
				if(!empty($data_nf_peca)) {
					list($di, $mi, $yi) = explode("/", $data_nf_peca);
					if (!checkdate($mi,$di,$yi)) {
						$msg_erro = "Data NF Inválida";
						break;
					} else {
						$data_nf_peca = "$yi-$mi-$di";
					}
				}

				$notas[] = array(
								"nf_peca" => "$nf_peca",
								"data_nf_peca" => "$data_nf_peca",
								"nf_peca_serie" => "$nf_peca_serie",
								"total_nf_peca" => $total_nf_peca
							);
			}
		}
		
		if($login_fabrica == 74 AND $optante_simples_nacional == "t"){
			if(strlen($valor_icms)==0){
				$msg_erro = "Informe o valor do ICMS";
			}else{

				$campo_aliq_icms = ", aliq_icms";
				$valor_aliq_icms = ", ".$valor_icms;
			}
		}
		
		if (empty($msg_erro)) {

			$sqlExtrato = "SELECT extrato FROM tbl_extrato_devolucao WHERE extrato = $extrato";
			$resExtrato = pg_query($con, $sqlExtrato);
			$action = "insert";

			if (pg_num_rows($resExtrato) > 0) {
				$action = "update";
			}

			$begin = pg_query ($con,"BEGIN TRANSACTION");

			if ($action == "update") {
				$sqlEd = "UPDATE tbl_extrato_devolucao SET optante_simples_nacional = '$optante_simples_nacional'
							WHERE extrato = $extrato";
			} else {
				$sqlEd = "INSERT INTO tbl_extrato_devolucao (
											extrato,
											linha,
											optante_simples_nacional
										) VALUES (
											$extrato,
											(SELECT linha FROM tbl_linha WHERE fabrica = $login_fabrica LIMIT 1),
											'$optante_simples_nacional'
										)";
			}

			$resEd = pg_query($con, $sqlEd);
			
			if (pg_last_error($con)) {
				$rollback = pg_query ($con, "ROLLBACK TRANSACTION");
				$msg_erro = "Erro ao gravar, tente novamente";
			} else {

				foreach ($notas as $key => $value) {
					$emissao = $value['data_nf_peca'];
					$total_nota = $value['total_nf_peca'];
					$nota_fiscal = $value['nf_peca'];
					$serie = $value['nf_peca_serie'];

					$sql = "INSERT INTO tbl_faturamento (
												fabrica,
												emissao,
												saida,
												posto,
												total_nota,
												nota_fiscal,
												serie,
												distribuidor,
												extrato_devolucao
											) VALUES (
												$login_fabrica,
												'$emissao',
												current_date,
												$login_posto,
												$total_nota,
												'$nota_fiscal',
												'$serie',
												$distribuidor,
												$extrato
											)";
					$res = pg_query($con, $sql);

					if (pg_last_error($con)) {
						$rollback = pg_query ($con, "ROLLBACK TRANSACTION");
						$msg_erro = "Erro ao gravar, tente novamente";
						break;
					}

					$currval = pg_query($con, "SELECT currval('seq_faturamento')");
					$faturamento = pg_fetch_result($currval, 0, 0);

					$faturamentos[$key] = array (
											"faturamento" => $faturamento,
											"emissao"     => "$emissao",
											"nota_fiscal" => $nota_fiscal,
											"serie"       => $serie
										);

					$faturamento_item = explode(";", $aPecas_notas[$key]);
					array_pop($faturamento_item);

					foreach ($faturamento_item as $fat_item) {
						/**
						 *
						 * @var array
						 *   0 => peca
						 *   1 => preco
						 *   2 => qtde
						 *
						 */
						$single = explode(":", $fat_item);

						if (empty($single[1])) {
							$single[1] = 0;
						}

						$sqlFi = "INSERT INTO tbl_faturamento_item (
											faturamento,
											peca,
											preco,
											qtde
											$campo_aliq_icms
										) VALUES (
											$faturamento,
											$single[0],
											$single[1],
											$single[2]
											$valor_aliq_icms
										)";
						$resFi = pg_query($con, $sqlFi);

						if (pg_last_error($con)) {
							$rollback = pg_query ($con, "ROLLBACK TRANSACTION");
							$msg_erro = "Erro ao gravar, tente novamente";
							break 2;
						}

						unset($single);

					}

					unset($faturamento_item);

				}
			}

			$commit = pg_query ($con,"COMMIT TRANSACTION");
			$msg = "Gravado com sucesso!";

		}

	} else {
		$nf_peca       = trim($_POST['nf_peca']);
		$data_nf_peca  = trim($_POST['data_nf_peca']);
		$nf_peca_serie = trim($_POST['nf_peca_serie']);
		$optante_simples_nacional = trim($_POST['optante_simples_nacional']);
	    
		$nf_obrigatoria = "";
		$nf_obrigatoria = "";
		if ($login_fabrica == 74) {
			$nf_obrigatoria = (empty($nf_peca) or empty($data_nf_peca));
		}else {
			$nf_obrigatoria = (empty($nf_peca) or empty($data_nf_peca) or empty($nf_peca_serie));
		}


		if($nf_obrigatoria) {
			if ($login_fabrica == 74) {
				$msg_erro = "A Preencha os campos da NF de peça";
			}else {
				$msg_erro = "Preencha todos os campos de NF de peça";
			}
		}else{
			if(!empty($data_nf_peca)) {
				list($di, $mi, $yi) = explode("/", $data_nf_peca);
				if(!checkdate($mi,$di,$yi)) {
					$msg_erro = "Data NF Inválida";
				}else{
					$aux_data_nf_peca = "$yi-$mi-$di";
				}
			}
		}

		if(empty($msg_erro) AND !empty($extrato)) {
			$res = pg_query ($con,"BEGIN TRANSACTION");

			$sql = "SELECT	SUM(tbl_os_item.custo_peca * tbl_os_item.qtde) as total
						FROM    tbl_os_extra
						JOIN    tbl_extrato     USING(extrato)
						JOIN    tbl_os_produto  USING(os)
						JOIN    tbl_os_item     USING(os_produto)
						JOIN    tbl_peca        USING(peca)
						JOIN    tbl_servico_realizado USING(servico_realizado)
						WHERE   tbl_os_extra.extrato = $extrato
						AND     tbl_peca.fabrica     = $login_fabrica
						AND     tbl_extrato.fabrica  = $login_fabrica
						AND     tbl_os_item.custo_peca > 0 ";
			$res = pg_query($con,$sql);
			$total = (pg_num_rows($res) > 0) ?pg_fetch_result($res,0,'total') : 0;

			$sql = " SELECT extrato
						FROM tbl_extrato_devolucao
						WHERE extrato = $extrato";
			$res = pg_query($con,$sql);

			if (pg_num_rows($res) > 0) {
				$sql = " UPDATE tbl_extrato_devolucao SET
								nota_fiscal   = '$nf_peca'         ,
								data_nf_envio = '$aux_data_nf_peca',
								total_nota    = $total             ,
								serie         = '$nf_peca_serie'   ,
								optante_simples_nacional = '$optante_simples_nacional'
							WHERE extrato = $extrato";
			} else {
				$sql = " INSERT INTO tbl_extrato_devolucao (
										extrato,
										linha,
										nota_fiscal,
										data_nf_envio,
										serie,
										optante_simples_nacional,
										total_nota
									) VALUES (
										$extrato,
										(SELECT linha FROM tbl_linha WHERE fabrica = $login_fabrica LIMIT 1),
										'$nf_peca',
										'$aux_data_nf_peca',
										'$nf_peca_serie',
										'$optante_simples_nacional',
										$total
									) ";
			}

			$res = pg_query($con,$sql);
			$msg_erro = pg_last_error($con);

			if (empty($msg_erro)) {
				$res = pg_query ($con,"COMMIT TRANSACTION");
				$msg = "Gravado com sucesso";
			} else {
				$res = pg_query ($con,"ROLLBACK TRANSACTION");
				$msg_erro = "Erro ao gravar, tente novamente";
			}
		}
	}
}

if ($botao_acao == "digitou_qtde") {
	$numero_linhas   = $_POST['qtde_linha'];
	$qtde_pecas      = $_POST['qtde_pecas'];
	$optante_simples_nacional = $_POST['optante_simples_nacional'];
	$valor_icms 	 = $_POST['aliq_icms'];
	$valor_icms = str_replace(",", ".", $valor_icms);
}

$layout_menu = "os";
$title = "Nota Fiscal de peça e serviço ";

include_once "cabecalho.php";

?>

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

.menu_top2 {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: x-small;
	font-weight: bold;
	border: 1px solid;
}

.sucesso{
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: x-small;
	font-weight: bold;
	border: 1px solid;
	color:#000000;
	background-color: #00cc00
}


.table_line {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 12px;
	font-weight: normal;
	border: 0px solid;
}


</style>

<? include "javascript_calendario_new.php"; ?>
<script type="text/javascript" src="js/jquery.alphanumeric.js"></script>

<script type="text/javascript">
function verificaNumero(e) {
	if (e.which != 8 && e.which != 0 && (e.which < 48 || e.which > 57)) {
		return false;
	}
}

$(document).ready(function() {
	$("input[rel='numero']").keypress(verificaNumero);

	if($("input[name=optante_simples_nacional]:checked").val() == "t"){
		$("#valor_icms").show();
	}else{
		$("#valor_icms").hide();
	}

	$("input[name=optante_simples_nacional]").click(function() {
		if($(this).val() == "t"){
			$("#valor_icms").show();
		}else{
			$("#valor_icms").hide();
			$("input[name=aliq_icms]").val("");
		}
	});
});


$(function()
{
	$("input[rel='data']").maskedinput("99/99/9999");
	$("#valor_icms").numeric({allow:".,"});
});

function enviaForm(){
	var optante = $("input[name=optante_simples_nacional]:checked").val();
	var icms = $("input[name=aliq_icms]").val();
	if( optante == "t"){
		if(icms == "") {
			$("input[name=botao_acao]").val("");
			alert("Informe valor do ICMS");			
		}else{
			document.frm_devolucao.submit();
		}
	}else{
		$("input[name=aliq_icms]").val("");
		document.frm_devolucao.submit();
	}
}
</script>

<br>

<?php

if ($login_fabrica == 74 and $numero_linhas == 0){
	$sql = "SELECT faturamento, emissao, nota_fiscal, serie FROM tbl_faturamento WHERE extrato_devolucao = $extrato AND fabrica = $login_fabrica";
	$res = pg_query($con, $sql);

	if (pg_num_rows($res) > 0) {
		$insere_numero_linhas = 0;
		$tem_faturamento = 1;

		$qtde_notas = pg_num_rows($res);
		$faturamentos = pg_fetch_all($res);
	} else {
		$tem_faturamento = 0;
		$sql = "SELECT	tbl_peca.referencia,
						tbl_peca.descricao,
						tbl_os_item.custo_peca as preco,
						SUM(tbl_os_item.qtde) AS qtde,
						SUM(custo_peca * qtde) AS total
					FROM    tbl_os_extra
					JOIN    tbl_extrato     USING(extrato)
					JOIN    tbl_os_produto  USING(os)
					JOIN    tbl_os_item     USING(os_produto)
					JOIN    tbl_peca        USING(peca)
					JOIN    tbl_servico_realizado USING(servico_realizado)
					WHERE   tbl_os_extra.extrato = $extrato
					AND     tbl_peca.fabrica     = $login_fabrica
					AND     tbl_extrato.fabrica  = $login_fabrica
					AND     tbl_extrato.posto = $login_posto
					AND	tbl_os_item.custo_peca > 0 
					AND     tbl_peca.controla_saldo IS TRUE
					GROUP BY
					tbl_peca.referencia,
					tbl_peca.descricao,
					tbl_os_item.custo_peca
					ORDER BY tbl_peca.referencia";
		//echo nl2br($sql);
		$res = pg_query($con,$sql);

		if (pg_num_rows($res) > 0 and !isset($insere_numero_linhas) and 1==2) {
			$sqlN = "select nota_fiscal from tbl_extrato_devolucao where extrato = $extrato and nota_fiscal is not null";
			$resN = pg_query($con, $sqlN);

			if (pg_num_rows($resN) == 0) {
				$insere_numero_linhas = 1;
			} else {
				$insere_numero_linhas = 0;
			}
		} else {
			$insere_numero_linhas = 0;
		}

	}

}

if ($insere_numero_linhas == 1) {
	$sql = "SELECT to_char (data_geracao,'DD/MM/YYYY') AS data,
				to_char (data_geracao,'YYYY-MM-DD') AS periodo,
				tbl_posto.nome,
				tbl_posto_fabrica.codigo_posto
			FROM tbl_extrato
			JOIN tbl_posto ON tbl_extrato.posto = tbl_posto.posto
			JOIN tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
			WHERE tbl_extrato.extrato = $extrato ";
	//echo nl2br($sql);
	$res = pg_query ($con, $sql);

	if (pg_num_rows($res) == 0) {
		echo '<meta http-equiv="Refresh" content=" 0 ; url=os_extrato.php" />';
		exit;
	}

	$data    = pg_fetch_result ($res, 0, 'data');
	$periodo = pg_fetch_result ($res, 0, 'periodo');
	$nome    = pg_fetch_result ($res, 0, 'nome');
	$codigo  = pg_fetch_result ($res, 0, 'codigo_posto');

	echo "<font size='+1' face='arial'>Data do Extrato $data </font>";
	echo "<br>";
	echo "<font size='-1' face='arial'>$codigo - $nome</font>";

	?>

	<p>
		<table width='550' align='center' border='0' style='font-size:12px'>
		<tr>
			<td align='center' width='33%'><a href='os_extrato.php'>Ver outro extrato</a></td>
		</tr>
		</table>

		<div id='loading'></div>

		<?php if (strlen($msg_erro) > 0): ?>
		<br>
		<table width="650" border="0" align="center" class="error">
			<tr>
				<td><?php echo $msg_erro; ?></td>
			</tr>
		</table>
		<?php endif; ?>

		<?php

		echo "<form method='post' action='$PHP_SELF' name='frm_devolucao' id='frm_devolucao'>";
		echo "<input type='hidden' name='notas_d' value=''>";
		echo "<input type='hidden' name='extrato' value='$extrato'>";
		echo "<input type='hidden' id='botao_acao' name='botao_acao' value=''>\n";

		$contador=0;

		echo "<br>
			<input type='hidden' name='qtde_pecas' value='$contador'>
			<IMG SRC='imagens/setona_h.gif' WIDTH='53' HEIGHT='29' BORDER='0' align='absmiddle'>
			<b style='font-size:12px'>


			<b>Informar a quantidade de linhas no formulário de Nota Fiscal de Peças do Posto Autorizado:</b>
			<input type='text' size='5' maxlength='3' value='' name='qtde_linha'><br>
			Essa informação definirá a quantidade de NFs que o posto autorizado deverá emitir e enviar
			<br><br>";

			if($login_fabrica == 74){
				echo "Optante pelo simples nacional:<br/>";
				echo "<input type='radio' name='optante_simples_nacional' value='t'>Sim";
				echo "<input type='radio' name='optante_simples_nacional' value='f' checked>Não";
				echo " <span id='valor_icms' style='display:none;font-size:12px;color:#FF0000;'><br />ICMS %<input type='text' name='aliq_icms' size='5'>&nbsp; 
				<img src='admin/imagens/help.png' title='Campo refere-se ao percentual de ICMS sobre o faturamento no Simples Nacional, caso não saiba favor contatar seu contador.' onclick='javascript: alert(\"Campo refere-se ao percentual de ICMS sobre o faturamento no Simples Nacional, caso não saiba favor contatar seu contador.\")'> <br>
					<font color='#FF0000'>Campo refere-se ao percentual de ICMS sobre o faturamento no Simples Nacional, caso não saiba favor contatar seu contador.</font>
				</span> <br><br>
				";

			}

		echo "<input type='button' id='fechar' value='Gerar Nota Fiscal' name='gravar' onclick=\"javascript:
				if(document.frm_devolucao.qtde_linha.value=='' || document.frm_devolucao.qtde_linha.value=='0'){
					alert('Informe a quantidade de itens!!');
				} else{
					if (document.frm_devolucao.botao_acao.value=='digitou_qtde'){
						alert('Aguarde submissão');
					} else{ 
						document.frm_devolucao.botao_acao.value='digitou_qtde';";

				if($login_fabrica == 74){
						echo "enviaForm();";
				}else{
						echo "this.form.submit();";
					}
				echo "} } \">
			<br><br> ";			

		echo "</form>";

} else {
	$sql = "SELECT  to_char (data_geracao,'DD/MM/YYYY') AS data ,
					to_char (data_geracao,'YYYY-MM-DD') AS periodo,
					total       ,
					mao_de_obra ,
					deslocamento,
					avulso      ,
					valor_adicional
				FROM tbl_extrato
				WHERE tbl_extrato.extrato = $extrato
				AND   tbl_extrato.fabrica = $login_fabrica
				AND tbl_extrato.posto = $login_posto";
	$res = pg_exec ($con,$sql);

	if(pg_num_rows($res) > 0){
		$data               = pg_fetch_result ($res,0,'data');
		$periodo            = pg_fetch_result ($res,0,'periodo');
		$total              = pg_fetch_result ($res,0,'total');
		$mao_de_obra        = pg_fetch_result ($res,0,'mao_de_obra');
		$avulso             = pg_fetch_result ($res,0,'avulso');
		$deslocamento       = pg_fetch_result ($res,0,'deslocamento');
		$valor_adicional    = pg_fetch_result ($res,0,'valor_adicional');
	}else{
		echo '<meta http-equiv="Refresh" content=" 0 ; url=os_extrato.php" />';
		exit;
	}

	echo "<font size='+1' face='arial'>Data do Extrato $data </font>";

	if (strlen($msg) > 0) { ?>
		<br>
		<table width="650" border="0" align="center" class='sucesso'>
			<tr>
				<td><?echo $msg ?></td>
			</tr>
		</table>
	<?php }

	if (strlen($msg_erro) > 0) { ?>
		<br>
		<table width="650" border="0" align="center" class="error">
			<tr>
				<td><?echo $msg_erro ?></td>
			</tr>
		</table>
	<?php }

	echo "<center>";

	$sql = " SELECT nota_fiscal_mao_de_obra      ,
				to_char(emissao_mao_de_obra,'DD/MM/YYYY') as data_nf   ,
				nota_fiscal_serie_mao_de_obra
			FROM    tbl_extrato_extra
			WHERE   extrato = $extrato";
	//echo nl2br($sql);
	$res = pg_query($con,$sql);
	if(pg_num_rows($res) > 0){
		$nf_extrato = pg_fetch_result($res,0,'nota_fiscal_mao_de_obra');
		$data_nf    = pg_fetch_result($res,0,'data_nf');
		$nf_serie   = pg_fetch_result($res,0,'nota_fiscal_serie_mao_de_obra');

		if(!empty($nf_extrato)) {
			$disabled = " disabled='disabled' ";
		}
	}

	$sqlFabrica = "SELECT * from tbl_fabrica where fabrica = $login_fabrica";
	$resFabrica = pg_query($con,$sqlFabrica);

	$razao    = pg_fetch_result($resFabrica,0,'razao_social');
	$endereco = pg_fetch_result($resFabrica,0,'endereco');
	$cidade   = pg_fetch_result($resFabrica,0,'cidade');
	$estado   = pg_fetch_result($resFabrica,0,'estado');
	$cep      = pg_fetch_result($resFabrica,0,'cep');
	$fone     = pg_fetch_result($resFabrica,0,'fone');
	$cnpj     = pg_fetch_result($resFabrica,0,'cnpj');
	$ie       = pg_fetch_result($resFabrica,0,'ie');

	$cfop = "6933";
	$sqlp = " SELECT contato_estado FROM tbl_posto_fabrica WHERE fabrica = $login_fabrica AND posto = $login_posto ";
	$resp = pg_query($con,$sqlp);
	if(pg_num_rows($resp) > 0){
		if(pg_fetch_result($resp,0,'contato_estado')=='PR') {
			$cfop = "5933";
		}
	}


	$cabecalho .= "<table border='1' cellspacing='0' cellpadding='3' border-color='#000' style='border-collapse:collapse;font-size:12px' width='650' >\n";
	$cabecalho .= "<tr align='left'  height='16' class='menu_top'>\n";
	$cabecalho .= "<td colspan='3' style='font-size:18px'>\n";
	$cabecalho .= "<b>&nbsp;<b>(TITULO)</b><br>\n";
	$cabecalho .= "</td>\n";
	$cabecalho .= "</tr>\n";
	$cabecalho .= "</table>\n";

	$cnpj = substr ($cnpj,0,2) . "." . substr ($cnpj,2,3) . "." . substr ($cnpj,5,3) . "/" . substr ($cnpj,8,4) . "-" . substr ($cnpj,12,2);
	$cabecalho .= "<table border='1' cellspacing='0' cellpadding='3' border-color='#000' style='border-collapse:collapse;font-size:12px' width='650' >\n";
	$cabecalho .= "<tr>\n";
	$cabecalho .= "<td>Razão Social <br> <b>$razao</b> </td>\n";
	$cabecalho .= "<td>CNPJ <br> <b>$cnpj</b> </td>\n";
	$cabecalho .= "<td>Inscrição Estadual <br> <b>$ie</b> </td>\n";
	$cabecalho .= "<td>CFOP <br> <b> (CFOP) </b> </td>\n";
	$cabecalho .= "</tr>\n";
	$cabecalho .= "</table>\n";

	$cep = substr ($cep,0,2) . "." . substr ($cep,2,3) . "-" . substr ($cep,5,3) ;
	$cabecalho .= "<table border='1' cellspacing='0' cellpadding='3' border-color='#000' style='border-collapse:collapse;font-size:12px' width='650' >\n";
	$cabecalho .= "<tr>\n";
	$cabecalho .= "<td>Endereço <br> <b>$endereco </b> </td>\n";
	$cabecalho .= "<td>Cidade <br> <b>$cidade</b> </td>\n";
	$cabecalho .= "<td>Estado <br> <b>$estado</b> </td>\n";
	$cabecalho .= "<td>CEP <br> <b>$cep</b> </td>\n";
	$cabecalho .= "</tr>\n";
	$cabecalho .= "</table>\n";

	echo "<form method='post' action='$PHP_SELF' name='frm_nf_servico' id='frm_nf_servico'>";
	echo "<input type='hidden' name='extrato' value='$extrato'>";
	echo "<input type='hidden' id='botao_acao' name='botao_acao' value=''>";
	echo "<table border='1' cellspacing='0' cellpadding='3' border-color='#000' style='border-collapse:collapse;font-size:12px' width='650' >";
	echo "<tr align='left'  height='16'>";
	echo "<td bgcolor='#E3E4E6' colspan='3' style='font-size:18px'>";
	echo "&nbsp;<b>Por favor, preenche os dados da nota do extrato $extrato para ser enviado ao fabricante.</b>";
	echo "</td>";
	echo "</tr>";
	echo "</table>";
	echo "<br/>";

	$x_cabecalho = str_replace("(CFOP)",substr($cfop,0,4),$cabecalho);
	$x_cabecalho = str_replace("(TITULO)","NF DE SERVIÇO",$x_cabecalho);

	echo $x_cabecalho;
	echo "<table border='1' cellspacing='0' cellpadding='3' style='border-collapse:collapse;font-size:12px' width='650' >";
	echo "<tr align='left' class='table_line'>";
	if($inf_valores_adicionais){
		echo "<td>Valor Total:<br/>",number_format($deslocamento+$mao_de_obra+$avulso+$valor_adicional,2,",","."),"</td>";
		echo "<td>Valor Adicional:<br/>",number_format($valor_adicional,2,",","."),"</td>";
	}else{
		echo "<td>Valor Total:<br/>",number_format($deslocamento+$mao_de_obra+$avulso,2,",","."),"</td>";
	}
	echo "<td>Valor KM:<br/>",number_format($deslocamento,2,",","."),"</td>";
	echo "<td>Valor MO:<br/>",number_format($mao_de_obra,2,",","."),"</td>";
	$colspan = 2;
	$colspan_btn = 4;
    if($avulso > 0){
        echo "<td>Valor Avulso:<br/>",number_format($avulso,2,",","."),"</td>";
        $colspan++;
        $colspan_btn++;
    }
	
	echo "</tr>";
	echo "<tr align='center'>";
	echo "<td>Nota Fiscal:<br/><input type='text' name='nf_extrato' value='$nf_extrato' maxlength='10' size='12' rel='numero' $disabled></td>";
	echo "<td>Data NF:<br/><input type='text' name='data_nf' value='$data_nf'  rel='data' maxlength='10' size='12' $disabled></td>";
	echo "<td colspan='$colspan'>Série:<br/><input type='text' name='nf_serie' value='$nf_serie' maxlength='3' size='5' ></td>";
	echo "</tr>";
	echo "<tr align='center' >";
	echo "<td colspan='$colspan_btn'><input type='button' name='btn_nf_servico' value='Gravar' onclick=\"javascript:
		if (document.frm_nf_servico.botao_acao.value=='nf_servico') {
			alert('Aguarde Submissão');
					}else{
						if(confirm('Deseja continuar? A nota de serviço não poderá ser alterada!')){
							document.frm_nf_servico.botao_acao.value='nf_servico';
							document.frm_nf_servico.submit();
						}
					}
					\"$disabled></td>";
	echo "</tr>";
	echo "</table>";

	echo "<br/><br/>";

	if($login_fabrica == 74){
		$cond_saldo = " AND tbl_peca.controla_saldo IS TRUE ";
		if($extrato > 1890082) {
			$cond_saldo .= "  and tbl_posto_fabrica.controla_estoque ";
		}
	}
	$sql = "SELECT	tbl_peca.referencia,
					tbl_peca.descricao,
					tbl_peca.peca,
					tbl_os_item.custo_peca as preco,
					SUM(tbl_os_item.qtde) AS qtde,
					SUM(custo_peca * qtde) AS total
				FROM tbl_os_extra
				JOIN tbl_extrato     USING(extrato)
				JOIN tbl_posto_fabrica USING(posto,fabrica)
					JOIN tbl_os_produto  USING(os)
					JOIN tbl_os_item     USING(os_produto)
					JOIN tbl_peca        USING(peca)
					JOIN tbl_servico_realizado USING(servico_realizado)
				WHERE   tbl_os_extra.extrato = $extrato
					AND tbl_peca.fabrica     = $login_fabrica
					AND tbl_extrato.fabrica  = $login_fabrica
					AND tbl_extrato.posto = $login_posto
					AND tbl_os_item.custo_peca > 0
					$cond_saldo
				GROUP BY
					tbl_peca.referencia,
					tbl_peca.descricao,
					tbl_peca.peca,
					tbl_os_item.custo_peca
				ORDER BY tbl_peca.referencia";
	$res = pg_query($con,$sql);

	if(pg_num_rows($res) > 0 and 1==2){

		$quebra = 0;

		if ($login_fabrica == 74) {
			if ($tem_faturamento == 0) {
				$resultado = pg_fetch_all($res);
			} else {
				$fetchPecas = array();

				if ($extrato == 1241167) {
					$extra_group_by = ', tbl_faturamento_item.faturamento_item';
					$order_by = 'tbl_faturamento_item.faturamento_item';
				} else {
					$extra_group_by = '';
					$order_by = 'tbl_peca.referencia';
				}

				foreach ($faturamentos as $arrFaturamento) {
					$faturamento = $arrFaturamento['faturamento'];
					$sqlPecas = "SELECT tbl_peca.referencia,
										tbl_peca.descricao,
										tbl_peca.peca,
										tbl_faturamento_item.qtde,
										tbl_faturamento_item.preco,
										SUM(tbl_faturamento_item.preco * tbl_faturamento_item.qtde) AS total,
										tbl_faturamento_item.aliq_icms
									FROM tbl_faturamento_item
										JOIN tbl_faturamento USING(faturamento)
										JOIN tbl_peca USING(peca)
									WHERE tbl_faturamento.faturamento = $faturamento
										AND tbl_faturamento.posto = $login_posto
									GROUP BY
										tbl_peca.referencia,
										tbl_peca.descricao,
										tbl_peca.peca,
										tbl_faturamento_item.qtde,
										tbl_faturamento_item.preco,
										tbl_faturamento_item.aliq_icms
									$extra_group_by
									ORDER BY $order_by;";
					//echo nl2br($sqlPecas);
					$resPecas = pg_query($con, $sqlPecas);

					if (pg_num_rows($resPecas) > 0) {
						$fetchPecas[] = pg_fetch_all($resPecas);
					}

				}
				$numero_linhas = count($fetchPecas[0]);

			}

			$quebra = 1;
		}

		$disabled = "";
		$cfop = "6102";
		$sqlp = " SELECT contato_estado FROM tbl_posto_fabrica WHERE fabrica = $login_fabrica AND posto = $login_posto ";
		$resp = pg_query($con,$sqlp);
		if(pg_num_rows($resp) > 0){
			if(pg_fetch_result($resp,0,'contato_estado')=='PR') {
				$cfop = "5102";
			}
		}
		$x_cabecalho = str_replace("(CFOP)",substr($cfop,0,4),$cabecalho);
		$x_cabecalho = str_replace("(TITULO)","NF DE PEÇA",$x_cabecalho);

		$tbl_cabecalho = "<table border='1' cellspacing='0' cellpadding='3' style='border-collapse:collapse;font-size:12px' width='650' >";
		$tbl_cabecalho .= "<thead><tr align='left' class='menu_top2'>";
		$tbl_cabecalho .= "<td colspan='2'>Peça</td>";
		$tbl_cabecalho .= "<td>Preço</td>";
		$tbl_cabecalho .= "<td>Qtde</td>";
		$tbl_cabecalho .= "<td>Total</td>";
		$tbl_cabecalho .= "</tr></thead>";

		$tbl_rodape = "<table border='1' cellspacing='0' cellpadding='3' style='border-collapse:collapse;font-size:12px' width='650' >";

		$sqlNF = " SELECT nota_fiscal,
						to_char(data_nf_envio,'DD/MM/YYYY') as data_nf_envio,
						serie,
						optante_simples_nacional
					FROM   tbl_extrato_devolucao
					WHERE  extrato = $extrato ";
		$resNF = pg_query($con,$sqlNF);

		if(pg_num_rows($resNF) > 0){
			$nf_peca                  = pg_fetch_result($resNF,0,'nota_fiscal');
			$data_nf_peca             = pg_fetch_result($resNF,0,'data_nf_envio');
			$nf_peca_serie            = pg_fetch_result($resNF,0,'serie');
			$optante_simples_nacional = pg_fetch_result($resNF,0,'optante_simples_nacional');
			$optante_simples_nacional = ($login_fabrica == 74 AND $_POST['optante_simples_nacional']) ? $_POST['optante_simples_nacional'] : $optante_simples_nacional;
			if(!empty($nf_peca)) {
				$disabled = " disabled='disabled' ";
				$quebra = 0;
			}
		}

		if ($quebra == 0){
			$tbl_rodape .= "<tr align='center'>";
			$tbl_rodape .= "<td>Optante pelo simples nacional:<br/><input type='radio' name='optante_simples_nacional' value='t' ";
			$tbl_rodape .= ($optante_simples_nacional == 't' or empty($optante_simples_nacional)) ? " CHECKED ":" ";
			$tbl_rodape .= ">Sim";
			$tbl_rodape .= "<input type='radio' name='optante_simples_nacional' value='f' ";
			$tbl_rodape .= ($optante_simples_nacional == 'f') ? " CHECKED ":" ";
			$tbl_rodape .= ">Não</td>";
			$tbl_rodape .= "<td>Nota Fiscal:<br/><input type='text' name='nf_peca' value='$nf_peca' maxlength='10' size='12' rel='numero' $disabled></td>";
			$tbl_rodape .= "<td>Data NF:<br/><input type='text' name='data_nf_peca' value='$data_nf_peca'  rel='data' maxlength='10' size='12' $disabled></td>";
			$tbl_rodape .= "<td>Série:<br/><input type='text' name='nf_peca_serie' value='$nf_peca_serie' maxlength='3' size='5' ></td>";
			$tbl_rodape .= "</tr>";
			$tbl_rodape .= "<tr align='center' >";
			$tbl_rodape .= "<td colspan='4'><input type='button' name='btn_nf_peca' value='Gravar' $disabled onclick=\"javascript:
								if (document.frm_nf_servico.botao_acao.value=='nf_peca') {
									alert('Aguarde Submissão');
								}else{
									if(confirm('Deseja continuar? A nota de peça não poderá ser alterada!')){
										document.frm_nf_servico.botao_acao.value='nf_peca';
										document.frm_nf_servico.submit();
									}
								}
							\" ></td>";
			$tbl_rodape .= "</tr>";
			$tbl_rodape .= "</table>";
		}

	    $nf_pecas_itens = "";

		if ($quebra == 0) {
			$total_peca = "0";
			$sub_total  = "0";
			for($i =0;$i<pg_num_rows($res);$i++) {
				$nf_pecas_itens .= "<tr align='left' class='table_line'>";
				$nf_pecas_itens .= "<td colspan='2'>".pg_fetch_result($res,$i,'referencia')." - ". pg_fetch_result($res,$i,'descricao')."</td>";
				$nf_pecas_itens .= "<td align='right'>".number_format(pg_fetch_result($res,$i,'preco'),2,",",".")."</td>";
				$nf_pecas_itens .= "<td align='right'>".pg_fetch_result($res,$i,'qtde')."</td>";
				$nf_pecas_itens .= "<td align='right'>".number_format(pg_fetch_result($res,$i,'total'),2,",",".")."</td>";
				$nf_pecas_itens .= "</tr>";

				$ext_preco		= pg_fetch_result($res,$i,'preco');
				$ext_quantidade = pg_fetch_result($res,$i,'qtde');
				$total_peca     =  $ext_preco * $ext_quantidade;
				$sub_total      = $sub_total + $total_peca;
			}
			$nf_pecas_itens .= "</table>";

			if($login_fabrica == 74){
				$nf_pecas_itens .= "<table border='1' cellspacing='0' cellpadding='3' style='border-collapse:collapse;font-size:12px' width='650' >";
				$nf_pecas_itens .= "<tr>";
				$nf_pecas_itens .= "<td align='right'><B>TOTAL :</B>&nbsp;".number_format($sub_total,2,",",".")."</td>";
				$nf_pecas_itens .= "</tr>";
				$nf_pecas_itens .= "</table>";
			}

			echo $x_cabecalho;
			echo $tbl_cabecalho;
			echo $nf_pecas_itens;
			echo $tbl_rodape;

		} else {

			$sqlDis = "SELECT tbl_faturamento.faturamento FROM tbl_faturamento
						JOIN tbl_extrato_devolucao ON tbl_extrato_devolucao.extrato = tbl_faturamento.extrato_devolucao
						WHERE tbl_faturamento.extrato_devolucao = $extrato 
					       AND tbl_faturamento.fabrica = $login_fabrica	LIMIT 1";
			$resDis = pg_query($con, $sqlDis);

			if (pg_num_rows($resDis) > 0) {
				$disabledPecas = " disabled='disabled' ";
			} else {
				$disabledPecas = "";
			}

			if (isset($resultado)) {
				@$chuncked   = array_chunk($resultado, $numero_linhas, true);
				$qtde_notas = count($chuncked);
			} else {
				$chuncked = $fetchPecas;
			}

			$pecas_notas = '';
			
			if(count($chuncked) > 0) {
				$countX = 0;
				foreach ($chuncked as $idx => $itens) {
					$nf_pecas_itens   = "";
					$tbl_rodape_itens = $tbl_rodape;

					$nf_peca_value = $faturamentos[$idx]['nota_fiscal'];
					list($yy, $mm, $dd) = explode("-", $faturamentos[$idx]['emissao']);
					$data_peca_value = "$dd/$mm/$yy";
					$nf_peca_serie_value = $faturamentos[$idx]['serie'];

					$nf_peca_value = (empty($nf_peca_value)) ? $_POST["nf_peca_{$countX}"]: $nf_peca_value;
					$data_peca_value = ($data_peca_value == "//") ? $_POST["data_nf_peca_{$countX}"] : $data_peca_value;
					$nf_peca_serie_value = (empty($nf_peca_serie_value)) ? $_POST["nf_peca_serie_{$countX}"]: $nf_peca_serie_value;


					$tbl_rodape_itens .= "<tr align='center'>";
					$tbl_rodape_itens .= "<td>Nota Fiscal:<br/><input type='text' name='nf_peca_$idx' value='$nf_peca_value' maxlength='10' size='12' rel='numero' $disabledPecas ></td>";
					$tbl_rodape_itens .= "<td>Data NF:<br/><input type='text' name='data_nf_peca_$idx' value='$data_peca_value'  rel='data' maxlength='10' size='12' $disabledPecas ></td>";
					$tbl_rodape_itens .= "<td>Série:<br/><input type='text' name='nf_peca_serie_$idx' value='$nf_peca_serie_value' maxlength='3' size='5' $disabledPecas ></td>";
					$tbl_rodape_itens .= "</tr>";
					$tbl_rodape_itens .= "<tr align='center' >";
					$tbl_rodape_itens .= "</tr>";
					$tbl_rodape_itens .= "</table><br/>";

					$total_peca = "0";
					$sub_total  = "0";

					$pecas = '';
					$countX ++;
					foreach($itens as $item) {
						$nf_pecas_itens .= "<tr align='left' class='table_line'>";
						$nf_pecas_itens .= "<td colspan='2'>" . $item['referencia'] . " - " . $item['descricao'] . "</td>";
						$nf_pecas_itens .= "<td align='right'>" . number_format($item['preco'], 2, ',', '.') . "</td>";
						$nf_pecas_itens .= "<td align='right'>" . $item['qtde'] . "</td>";
						$nf_pecas_itens .= "<td align='right'>" . number_format($item['total'],2,",",".") . "</td>";
						$nf_pecas_itens .= "</tr>";

						$ext_preco		= $item['preco'];
						$ext_quantidade = $item['qtde'];
						$total_peca     = $ext_preco * $ext_quantidade;
						$sub_total      = $sub_total + $total_peca;

						$peca_item  = $item['peca'];
						$preco_item = $item['preco'];
						$qtde_item  = $item['qtde'];

						$pecas .= "$peca_item:$preco_item:$qtde_item;";
					}
					$nf_pecas_itens .= "</table>";

					if($login_fabrica == 74){
						$nf_pecas_itens .= "<table border='1' cellspacing='0' cellpadding='3' style='border-collapse:collapse;font-size:12px' width='650' >";
						$nf_pecas_itens .= "<tr>";
						$nf_pecas_itens .= "<td align='right'><input type='hidden' name='total_nf_peca_$idx' value='$sub_total' /><B>TOTAL :</B>&nbsp;".number_format($sub_total,2,",",".")."</td>";
						$nf_pecas_itens .= "</tr>";
						$nf_pecas_itens .= "</table>";
					}

					$pecas_notas .= "$pecas # ";

					echo $x_cabecalho;
					echo $tbl_cabecalho;
					echo $nf_pecas_itens;
					echo $tbl_rodape_itens;
					$valor_icms = (strlen($valor_icms)==0) ? $fetchPecas[0][0]['aliq_icms'] : $valor_icms;

					if( !empty($valor_icms) AND $valor_icms > 0 AND $login_fabrica == 74) {

						$valor_calculado = ($sub_total * $valor_icms) / 100;
						echo "<font style='font-size:12px; font-weight:bold;'>Permite o aproveitamento do crédito de ICMS no valor de R$".number_format($valor_calculado,2,",",".").", correspondente à alíquota de $valor_icms %, nos termos do Art. 23 da LC 123.</font> <br><br>";
					}

				}
				
				

				echo "<center>
							<input type='text' name='qtde_notas' value='$qtde_notas' $disabledPecas />
							<input type='hidden' name='pecas_notas' value='$pecas_notas' $disabledPecas />
							<input type='hidden' name='qtde_linha' value='$numero_linhas' $disabledPecas />";

				if($login_fabrica != 74){

					echo "Optante pelo simples nacional:<br/> ";				

					echo "<input type='radio' name='optante_simples_nacional' value='t'  $disabledPecas ";
					echo ($optante_simples_nacional == 't' or empty($optante_simples_nacional)) ? " CHECKED ":" ";

					echo ">Sim";
					echo "<input type='radio' name='optante_simples_nacional' value='f' $disabledPecas ";
			
					echo ($optante_simples_nacional == 'f') ? " CHECKED ":" ";

					echo ">Não";
				}else{
					echo "<input type='hidden' name='optante_simples_nacional' value='$optante_simples_nacional' />";
					echo "<input type='hidden' name='aliq_icms' value='$valor_icms' />";
				}

			$link = ($faturamento) ? "os_extrato.php" : "extrato_posto_nf.php?extrato=$extrato";
			
			echo "<br/><br/>

						<input type='button' name='btn_nf_peca' value='Gravar' $disabledPecas onclick=\"javascript:
								if (document.frm_nf_servico.botao_acao.value=='nf_peca') {
									alert('Aguarde Submissão');
								}else{
									if(confirm('Deseja continuar? A nota de peça não poderá ser alterada!')){
										document.frm_nf_servico.botao_acao.value='nf_peca';
										document.frm_nf_servico.submit();
									}
								}
							\" >
						<input type='button' value='Voltar' onclick='javascript:window.location=\"$link\";'>
					</center>";
		  	}
		}

	}
	
	echo "</form>";

}

include_once "rodape.php";

?>
