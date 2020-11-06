<?php
/**
  *  @description Relatorio Perfil do Consumidor - HD 816610
  *  @author Francisco Ambrozio.
  *  @version 2012.03
  *
  **/

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include "autentica_admin.php";
$layout_menu = "callcenter";
$title = "RELATÓRIO DE PERFIL DO CONSUMIDOR";
include "cabecalho.php";

?>

<link rel="stylesheet" type="text/css" href="../plugins/shadowbox/shadowbox.css" media="all">

<style type="text/css">
	.titulo_tabela{
		background-color:#596d9b;
		font: bold 14px "Arial";
		color:#FFFFFF;
		text-align:center;
	}
	.titulo_coluna{
		background-color:#596d9b;
		font: bold 11px "Arial";
		color:#FFFFFF;
		text-align:center;
	}
	.msg_erro{
		background-color:#FF0000;
		font: bold 14px "Arial";
		color:#FFFFFF;
		text-align:center;
	}
	.formulario{
		background-color:#D9E2EF;
		font:11px Arial;
		text-align:left;
	}
	button.download { margin-top : 15px; }
	table.form tr td{
		padding:10px 30px 0 0;
	}
	table.tabela tr td{
		font-family: verdana;
		font-size: 11px;
		border-collapse: collapse;
		border:1px solid #596d9b;
		padding: 0 10px;
	}
	.texto_avulso{
	    font: 14px Arial; color: rgb(89, 109, 155);
	    background-color: #d9e2ef;
	    text-align: center;
	    width:700px;
	    margin: 10px auto;
	    border-collapse: collapse;
	    border:1px solid #596d9b;
	}
	div.formulario table.form{
		padding:10px 0 10px 60px;
		text-align:left;
	}
	.subtitulo{
		background-color: #7092BE;
		font:bold 14px Arial;
		color: #FFFFFF;
		text-align:center;
	}
	tr th a {color:white !important;}
	tr th a:hover {color:blue !important;}

	div.formulario form p{ margin:0; padding:0; }
</style>

<script type="text/javascript" src="js/jquery-1.4.2.js"></script>
<script src="../plugins/shadowbox/shadowbox.js"	type="text/javascript"></script>
<script type="text/javascript" src="js/jquery.maskedinput.js"></script>
<script type="text/javascript" src="js/datePicker.v1.js"></script>

<script type="text/javascript">
	$().ready(function(){

		Shadowbox.init();

		$( "#data_hd_inicial" ).maskedinput("99/99/9999");
		$( "#data_hd_inicial" ).datePicker({startDate : "01/01/2000"});
		$( "#data_hd_final" ).maskedinput("99/99/9999");
		$( "#data_hd_final" ).datePicker({startDate : "01/01/2000"});

		$( "#data_prod_inicial" ).maskedinput("99/99/9999");
		$( "#data_prod_inicial" ).datePicker({startDate : "01/01/2000"});
		$( "#data_prod_final" ).maskedinput("99/99/9999");
		$( "#data_prod_final" ).datePicker({startDate : "01/01/2000"});

	});

	function pesquisaProduto(produto,tipo){

		if (jQuery.trim(produto.value).length > 2){
			Shadowbox.open({
				content:	"produto_pesquisa_nv.php?"+tipo+"="+produto.value,
				player:	"iframe",
				title:		"Produto",
				width:	800,
				height:	500
			});
		}else{
			alert("Informe toda ou parte da informação para realizar a pesquisa!");
			produto.focus();
		}
	}

	function retorna_produto(produto,referencia,descricao, numero_serie, posicao){
		gravaDados("produto_referencia_"+posicao,referencia);
		gravaDados("produto_descricao_"+posicao,descricao);
		gravaDados("produto_serie_"+posicao,numero_serie);
	}

	function retorna_dados_produto(referencia,descricao,produto,linha,nome_comercial,voltagem,referencia_fabrica,garantia,ativo,valor_troca,troca_garantia,troca_faturada){
		gravaDados('produto_referencia',referencia);
		gravaDados('produto_descricao',descricao);
	}

	function gravaDados(name, valor){
		try {
			$("input[name="+name+"]").val(valor);
		} catch(err){
			return false;
		}
	}

</script>

<?php include "javascript_calendario.php";?>

<?php

$produto_referencia = '';
$produto_descricao = '';

if ( isset($_POST['gerar']) ) {

	$vCond = '';
	$aJoin = array();
	$aCond = array();

	if($_POST["data_hd_inicial"]) $data_hd_inicial = trim ($_POST["data_hd_inicial"]);
	if($_POST["data_hd_final"]) $data_hd_final = trim($_POST["data_hd_final"]);

	if( empty($data_hd_inicial) OR empty($data_hd_final) )
		$msg_erro = "Data (Abertura do Atendimento) Inválida.<br/>";

	if(strlen($msg_erro)==0) {
		list($di, $mi, $yi) = explode("/", $data_hd_inicial);
		list($df, $mf, $yf) = explode("/", $data_hd_final);
		if(!checkdate($mi,$di,$yi) OR !checkdate($mf,$df,$yf))
			$msg_erro = "Data (Abertura do Atendimento) Inválida.<br/>";
	}

	$so_excel = 0;

	if(strlen($msg_erro)==0) {
		$aux_data_inicial = "$yi-$mi-$di";
		$aux_data_final = "$yf-$mf-$df";

		if(strtotime($aux_data_final) < strtotime($aux_data_inicial))
			$msg_erro = "Data (Abertura do Atendimento) Inválida.";
		if(strlen($msg_erro)==0)
			if (strtotime("$aux_data_inicial + 1 year" ) < strtotime($aux_data_final)) {
				$msg_erro = 'O intervalo entre as datas (Abertura do Atendimento) não pode ser maior que 1 ano.<br/>';
			} else {
				if (strtotime("$aux_data_inicial + 90 days" ) < strtotime($aux_data_final)) {
					$so_excel = 1;
				}
			}
		if(empty($msg_erro)) {
			$vCond.= " WHERE tbl_hd_chamado.data BETWEEN '$aux_data_inicial 00:00:00' AND '$aux_data_final 23:59:59' ";
		}
	}

	$produto_referencia = trim ($_POST['produto_referencia']);
	$produto_descricao = trim($_POST['produto_descricao']);

	if ( !empty($produto_referencia) ) {

		$sql = "SELECT produto
				FROM tbl_produto
				JOIN tbl_linha ON tbl_linha.linha = tbl_produto.linha AND tbl_linha.fabrica = $login_fabrica
				WHERE referencia = '$produto_referencia'
				AND descricao = '$produto_descricao'";

		$res = pg_query($con, $sql);

		if (pg_num_rows($res)) {
			$produto = pg_result($res,0,0);
			$vCond.= " AND tbl_hd_chamado_extra.produto = $produto ";
		}
		else
			$msg_erro.= 'Produto '.$referencia.' não Encontrado.<br/>';

	}

	if (!empty($_POST['faixa_etaria'])) {
		$faixa_etaria = $_POST['faixa_etaria'];
	}

	$pesquisa_data_compra = 0;
	if (!empty($_POST['data_prod_inicial'])) {
		if (empty($_POST['data_prod_final'])) {
			$msg_erro.= 'Informe a data final da compra do produto.';
		} else {
			$data_prod_inicial = $_POST['data_prod_inicial'];
			$data_prod_final = $_POST['data_prod_final'];

			list($di, $mi, $yi) = explode("/", $data_prod_inicial);
			list($df, $mf, $yf) = explode("/", $data_prod_final);
			if(!checkdate($mi,$di,$yi) OR !checkdate($mf,$df,$yf)) {
				$msg_erro.= "Data (Compra do Produto) Inválida.<br/>";
			} else {
				$pesquisa_data_compra = 1;
			}
		}
	}

	if (!empty($_POST['tentativas'])) {
		$tentativas = $_POST['tentativas'];
	}

	if (empty($msg_erro) and !empty($vCond)) {
		$sql = "SELECT DISTINCT tbl_hd_chamado_extra.hd_chamado,
					tbl_produto.referencia AS referencia_produto,
					tbl_produto.descricao AS descricao_produto,
					tbl_cidade.nome AS cidade,
					tbl_cidade.estado AS estado
					INTO TEMP tmp_rpsc_$login_admin
				FROM tbl_hd_chamado_extra
				JOIN tbl_hd_chamado ON tbl_hd_chamado.hd_chamado = tbl_hd_chamado_extra.hd_chamado
				JOIN tbl_produto USING(produto)
				JOIN tbl_cidade USING(cidade)
				JOIN tbl_resposta ON tbl_resposta.hd_chamado = tbl_hd_chamado_extra.hd_chamado
				$vCond
				AND tbl_hd_chamado.fabrica = $login_fabrica";

		$query = pg_query($con, $sql);

		if (!pg_last_error($con)) {

			$idx = pg_query($con, "CREATE INDEX 	idx_hd_chamado_tmp_rpsc_$login_admin ON tmp_rpsc_$login_admin(hd_chamado)");

			$qry_total = pg_query($con, "SELECT count(*) AS total FROM tmp_rpsc_$login_admin");
			$total = pg_fetch_result($qry_total, 0, 'total');

			$resultado = '';

			if ($total > 0) {
				$transaction = pg_query($con, "BEGIN");
				$rollback = 0;

				$alter = pg_query($con, "ALTER TABLE tmp_rpsc_$login_admin ADD id_sexo integer");
				$alter = pg_query($con, "ALTER TABLE tmp_rpsc_$login_admin ADD txt_sexo varchar(100)");

				$alter = pg_query($con, "ALTER TABLE tmp_rpsc_$login_admin ADD id_faixa_etaria integer");
				$alter = pg_query($con, "ALTER TABLE tmp_rpsc_$login_admin ADD txt_faixa_etaria varchar(100)");

				$alter = pg_query($con, "ALTER TABLE tmp_rpsc_$login_admin ADD id_tentativas integer");
				$alter = pg_query($con, "ALTER TABLE tmp_rpsc_$login_admin ADD txt_tentativas varchar(50)");

				$alter = pg_query($con, "ALTER TABLE tmp_rpsc_$login_admin ADD data_compra varchar(20)");

				$idxss = "CREATE INDEX idx_tmp_faixa_etaria ON tmp_rpsc_$login_admin (id_faixa_etaria);
							CREATE INDEX idx_tmp_sexo ON tmp_rpsc_$login_admin (id_sexo);
							CREATE INDEX idx_tmp_tentativas ON tmp_rpsc_$login_admin (id_tentativas);
							CREATE INDEX idx_tmp_data_compra ON tmp_rpsc_$login_admin (data_compra);";
				$cr_idx = pg_query($con, $idxss);

				/**
				 * Algumas coisas deste relatório estão fixas com o cadastro da pesquisa no banco de dados:
				 *  tbl_pergunta.pergunta = 64  -  data da compra do produto
				 *  tbl_pergunta.pergunta = 65  -  sexo
				 *  tbl_pergunta.pergunta = 66  -  faixa etária
				 *  tbl_pergunta.pergunta = 67  -  tentativas de contato
				 *
				 * Se for alterado no banco precisa alterar aqui também.
				 */

				$update1 = "UPDATE tmp_rpsc_$login_admin SET
								id_faixa_etaria = tbl_tipo_resposta_item.tipo_resposta_item,
								txt_faixa_etaria = tbl_tipo_resposta_item.descricao
							FROM tbl_tipo_resposta_item
							JOIN tbl_resposta ON tbl_resposta.tipo_resposta_item = tbl_tipo_resposta_item.tipo_resposta_item
							JOIN tbl_pergunta ON tbl_pergunta.pergunta = tbl_resposta.pergunta AND tbl_resposta.pergunta = 66
							WHERE tmp_rpsc_$login_admin.hd_chamado = tbl_resposta.hd_chamado";
				$up = pg_query($con, $update1);

				if (pg_last_error($con) || pg_affected_rows($up) > $total) {
					$rollback = 1;
				}

				$update2 = "UPDATE tmp_rpsc_$login_admin SET
								id_tentativas = tbl_tipo_resposta_item.tipo_resposta_item,
								txt_tentativas = tbl_tipo_resposta_item.descricao
							FROM tbl_tipo_resposta_item
							JOIN tbl_resposta ON tbl_resposta.tipo_resposta_item = tbl_tipo_resposta_item.tipo_resposta_item
							JOIN tbl_pergunta ON tbl_pergunta.pergunta = tbl_resposta.pergunta AND tbl_resposta.pergunta = 67
							WHERE tmp_rpsc_$login_admin.hd_chamado = tbl_resposta.hd_chamado";
				$up = pg_query($con, $update2);

				if (pg_last_error($con) || pg_affected_rows($up) > $total) {
					$rollback = 1;
				}

				$update3 = "UPDATE tmp_rpsc_$login_admin SET data_compra = to_char(tbl_resposta.txt_resposta::date, 'DD/MM/YYYY')
							FROM tbl_resposta
							JOIN tbl_pergunta ON tbl_pergunta.pergunta = tbl_resposta.pergunta AND tbl_resposta.pergunta = 64
							WHERE tmp_rpsc_$login_admin.hd_chamado = tbl_resposta.hd_chamado";
				$up = pg_query($con, $update3);

				if (pg_last_error($con) || pg_affected_rows($up) > $total) {
					$rollback = 1;
				}

				$update4 = "UPDATE tmp_rpsc_$login_admin SET
								id_sexo = tbl_tipo_resposta_item.tipo_resposta_item,
								txt_sexo = tbl_tipo_resposta_item.descricao
							FROM tbl_tipo_resposta_item
							JOIN tbl_resposta ON tbl_resposta.tipo_resposta_item = tbl_tipo_resposta_item.tipo_resposta_item
							JOIN tbl_pergunta ON tbl_pergunta.pergunta = tbl_resposta.pergunta AND tbl_resposta.pergunta = 65
							WHERE tmp_rpsc_$login_admin.hd_chamado = tbl_resposta.hd_chamado";
				$up = pg_query($con, $update4);

				if (pg_last_error($con) || pg_affected_rows($up) > $total) {
					$rollback = 1;
				}

				if ($rollback == 0) {
					$transaction = pg_query($con, "COMMIT");

					if ($pesquisa_data_compra == 1) {
						$cond.= " AND tmp_rpsc_$login_admin.data_compra::date BETWEEN '$data_prod_inicial' AND '$data_prod_final' ";
					}

					if (!empty($faixa_etaria)) {
						$cond.= " AND tmp_rpsc_$login_admin.id_faixa_etaria = $faixa_etaria ";
					}

					if (!empty($tentativas)) {
						$cond.= " AND tmp_rpsc_$login_admin.id_tentativas = $tentativas ";
					}

					$fetch_all = pg_query($con, "SELECT * FROM tmp_rpsc_$login_admin WHERE 1=1 $cond ORDER BY data_compra::date DESC");

					if (pg_num_rows($fetch_all) > 0) {
						$resultado.= '<table class="tabela" cellspacing="1" align="center">
										<tr class="titulo_coluna">
											<th>Sexo</th>
											<th>Faixa Etária</th>
											<th>Data de Compra do Produto</th>
											<th>Referência do Produto</th>
											<th>Descrição do Produto</th>
											<th>Tentativas de Contato</th>
											<th>Cidade</th>
											<th>Estado</th>
										</tr>';

						while ($res = pg_fetch_array($fetch_all)) {
							$hd_chamado = $res['hd_chamado'];
							$sexo = $res['txt_sexo'];
							$referencia_produto = $res['referencia_produto'];
							$descricao_produto = $res['descricao_produto'];
							$cidade = $res['cidade'];
							$estado = $res['estado'];
							$resp_faixa_etaria = $res['txt_faixa_etaria'];
							$resp_tentativas = $res['txt_tentativas'];
							$data_compra = $res['data_compra'];

							$resultado.= '<tr>';
							$resultado.= '<td>' . $sexo . '</td>';
							$resultado.= '<td>' . $resp_faixa_etaria . '</td>';
							$resultado.= '<td>' . $data_compra . '</td>';
							$resultado.= '<td>' . $referencia_produto . '</td>';
							$resultado.= '<td>' . $descricao_produto . '</td>';
							$resultado.= '<td>' . $resp_tentativas . '</td>';
							$resultado.= '<td>' . $cidade . '</td>';
							$resultado.= '<td>' . $estado . '</td>';
							$resultado.= '</tr>';
						}

						$resultado.= '</table>';
						$resultado.= '<span style="font-family: verdana; font-size: 11px;">Total de registros: <strong>' . pg_num_rows($fetch_all) . '</strong> atendimentos</span><br/>';
					} else {
						$resultado = 'Nenhum resultado encontrado para os parâmetros pesquisados.';
					}

				} else {
					$transaction = pg_query($con, "ROLLBACK");
					$msg_erro = 'Erro ao processar resultado.';
				}

			} else {
				$resultado = 'Nenhum resultado encontrado para os parâmetros pesquisados.';
			}

		} else {
			$msg_erro = 'Erro ao processar resultado.';
		}

	}

}
?>

<div class="formulario" style="width:700px; margin:auto;">
	<div id="msg"></div>
	<div class="titulo_tabela">Parâmetros de Pesquisa</div>
	<form action="<?=$_SERVER['PHP_SELF'];?>" method="POST" name="frm">
		<table cellspacing="1" align="center" class="form">
			<tr>
				<td style="min-width:120px;">
					<label for="data_hd_inicial">Data Inicial (Abertura do Atendimento)</label><br />
					<input type="text" name="data_hd_inicial" id="data_hd_inicial" class="frm" size="13" value="<?=isset($_POST['data_hd_inicial'])?$_POST['data_hd_inicial'] : '' ?>" />
				</td>
				<td style="min-width:120px;">
					<label for="data_hd_final">Data Final (Abertura do Atendimento)</label><br />
					<input type="text" name="data_hd_final" id="data_hd_final" class="frm" size="13" value="<?=isset($_POST['data_hd_final'])?$_POST['data_hd_final'] : ''?>"/>
				</td>
			</tr>

			<tr>
				<td>
					<label for="faixa_etaria">Faixa etária</label><br />
					<?php
					/**
					 * Aqui também está fixo:
					 *   - tbl_tipo_resposta_item.tipo_resposta = 10 - faixa etária
					 *   - tbl_tipo_resposta_item.tipo_resposta = 11 - tentativas
					 */
					$qry = pg_query($con, "SELECT tipo_resposta_item, descricao from tbl_tipo_resposta_item where tipo_resposta = 10 order by ordem");
					if (pg_num_rows($qry) > 0) {
						while ($fetch = pg_fetch_array($qry)) {
							echo '<input type="radio" name="faixa_etaria" value="' , $fetch['tipo_resposta_item'] , '"';
							if ($faixa_etaria == $fetch['tipo_resposta_item']) {
								echo ' checked="checked"';
							}
							echo ' />' , $fetch['descricao'] , '<br/>';
						}
					}
					?>
				</td>
			</tr>

			<tr>
				<td>
					Referência Produto<br><input type="text" name="produto_referencia" id="produto_referencia" value="<?php echo $produto_referencia;?>" size="15" maxlength="20" class='frm'>&nbsp;<img src='../imagens/lupa.png' border='0' align='absmiddle' onclick="javascript: pesquisaProduto(document.frm.produto_referencia,'referencia')" alt='Clique aqui para pesquisar pela referência do produto' style='cursor:pointer;'>
					&nbsp;&nbsp;&nbsp;
				</td>

				<td>
					Descrição Produto&nbsp;<br><input type="text" name="produto_descricao" id="produto_descricao" value="<?php echo $produto_descricao;?>" size="30" maxlength="50" class='frm'>&nbsp;<img src='../imagens/lupa.png' border='0' align='absmiddle' onclick="javascript: pesquisaProduto(document.frm.produto_descricao,'descricao')" alt='Clique aqui para pesquisar pela descrição do produto' style='cursor:pointer;'>
				</td>
			</tr>



			<tr>
				<td style="min-width:120px;">
					<label for="data_prod_inicial">Data Inicial (Compra do Produto)</label><br />
					<input type="text" name="data_prod_inicial" id="data_prod_inicial" class="frm" size="13" value="<?=isset($_POST['data_prod_inicial'])?$_POST['data_prod_inicial'] : '' ?>" />
				</td>
				<td style="min-width:120px;">
					<label for="data_prod_final">Data Final (Compra do Produto)</label><br />
					<input type="text" name="data_prod_final" id="data_prod_final" class="frm" size="13" value="<?=isset($_POST['data_prod_final'])?$_POST['data_prod_final'] : ''?>"/>
				</td>
			</tr>


			<tr>
				<td>
					<label for="tentativas">Tentativas de contato</label><br />
					<?php
					$qry = pg_query($con, "SELECT tipo_resposta_item, descricao from tbl_tipo_resposta_item where tipo_resposta = 11 order by ordem");
					if (pg_num_rows($qry) > 0) {
						while ($fetch = pg_fetch_array($qry)) {
							echo '<input type="radio" name="tentativas" value="' , $fetch['tipo_resposta_item'] , '"';
							if ($tentativas == $fetch['tipo_resposta_item']) {
								echo ' checked="checked"';
							}
							echo ' />' , $fetch['descricao'] , '<br/>';
						}
					}
					?>
				</td>
			</tr>

			<tr>
				<td colspan="2" style="padding-top:15px;" align="center">
					<input type="submit" name="gerar" value="Consultar" />
					<a href="<?php echo $_SERVER['PHP_SELF']; ?>" ><input type="button" value="Limpar Dados" /></a>
				</td>
			</tr>
		</table>
	</form>
</div><br/>

<?php
if (!empty($resultado)) {
	if ($so_excel == 0) {
		echo $resultado;
	}

	if ($resultado != 'Nenhum resultado encontrado para os parâmetros pesquisados.') {
		date_default_timezone_set('America/Sao_Paulo');
		$link = 'xls/relatorio_perfil_consumidor_' . $login_fabrica . $login_admin . date('ymd') . '.xls';

		$xls = fopen($link, 'w');
		fwrite($xls, $resultado);
		fclose($xls);

		echo "<button class='download' onclick=\"window.open('$link') \">Download XLS</button>";

	}

}
?>

<?php echo '<div id="erro" class="msg_erro" style="display:none;">'.$msg_erro.'</div>'; ?>

<script type="text/javascript">
	<?php if ( !empty($msg_erro) ){ ?>
			$("#erro").appendTo("#msg").fadeIn("slow");
	<?php } ?>
</script>
<?php include 'rodape.php'; ?>
