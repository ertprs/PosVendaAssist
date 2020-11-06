<!-- RELATÓRIO CRIADO EM 22/01/2010 (ATENDENDO HD 188352) (EDUARDO)-->
<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="auditoria,gerencia";
include 'autentica_admin.php';
include 'funcoes.php';

$cachebypass=md5(time());

$btn_acao 	= $_POST['acao'];
$data_inicial 	= $_POST['data_inicial_01'];
$data_final 	= $_POST['data_final_01'];

$msg_erro = array();
$msgErrorPattern01 = traduz("Preencha os campos obrigatórios.");
$msgErrorPattern02 = traduz("A data de consulta deve ser no máximo de 6 meses.");

# Pesquisa pelo AutoComplete AJAX
$q = strtolower($_GET["q"]);

if (isset($_GET["q"])) {
	$tipo_busca = $_GET["busca"];

	if (strlen($q) > 3) {
		$sql = "SELECT tbl_posto.cnpj, tbl_posto.nome, tbl_posto_fabrica.codigo_posto
				FROM tbl_posto
				JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
				WHERE tbl_posto_fabrica.fabrica = $login_fabrica ";

		if ($tipo_busca == "codigo"){
			$sql .= " AND tbl_posto_fabrica.codigo_posto = '$q' ";
		}else{
			$sql .= " AND UPPER(tbl_posto.nome) like UPPER('%$q%') ";
		}

		$sql .= " LIMIT 50 ";
		$res = pg_query($con,$sql);
		if (pg_num_rows($res) > 0) {
			for ($i = 0; $i < pg_num_rows($res); $i++ ) {
				$cnpj = trim(pg_result($res,$i,cnpj));
				$nome = trim(pg_result($res,$i,nome));
				$codigo_posto = trim(pg_result($res,$i,codigo_posto));
				echo "$codigo_posto|$nome|$cnpj";
				echo "\n";
			}
		}
	}
	exit;
}

if($login_fabrica == 156){
    $statusOs = array();
    $sqlStatusOs = 'SELECT status_checkpoint, descricao as status_descricao FROM tbl_status_checkpoint';
    $resStatusOs = pg_query($con, $sqlStatusOs);

    $statusOs = pg_fetch_all($resStatusOs);
}

##INCIO DA VALIDACAO DE DATAS
if(strlen($btn_acao)>0) {
	if(!$data_inicial OR !$data_final) {
		$msg_erro["msg"][]    = $msgErrorPattern01;
		$msg_erro["campos"][] = traduz("data");
	}

	##TIRA A BARRA
	if(count($msg_erro["msg"]) == 0) {
		$dat = explode ("/", $data_inicial );
		$d = $dat[0];
		$m = $dat[1];
		$y = $dat[2];
		if(!checkdate($m,$d,$y)) {
			$msg_erro["msg"][]    = $msgErrorPattern01;
			$msg_erro["campos"][] = traduz("data");
		}
	}

	if(count($msg_erro["msg"]) == 0) {
		$dat = explode ("/", $data_final );//tira a barra
		$d = $dat[0];
		$m = $dat[1];
		$y = $dat[2];
		if(!checkdate($m,$d,$y)) {
			$msg_erro["msg"][]    = $msgErrorPattern01;
			$msg_erro["campos"][] = traduz("data");
		}
	}

	if(count($msg_erro["msg"]) == 0) {
		$d_ini = explode ("/", $data_inicial);//tira a barra
		$nova_data_inicial = "$d_ini[2]-$d_ini[1]-$d_ini[0]";//separa as datas $d[2] = ano $d[1] = mes etc...

		$d_fim = explode ("/", $data_final);//tira a barra
		$nova_data_final = "$d_fim[2]-$d_fim[1]-$d_fim[0]";//separa as datas $d[2] = ano $d[1] = mes etc...

		if($nova_data_final < $nova_data_inicial) {
			$msg_erro["msg"][]    = $msgErrorPattern01;
			$msg_erro["campos"][] = "data";
		}

		##Fim Validação de Datas
		if(count($msg_erro["msg"]) == 0) {
			$sql = "SELECT '$nova_data_final'::date - INTERVAL '6 MONTHS' > '$nova_data_inicial'::date ";
			$res = pg_query ($con,$sql);
			if (pg_fetch_result($res,0,0) == 't') {
				$msg_erro["msg"][]    = $msgErrorPattern02;
				$msg_erro["campos"][] = traduz("data");;
			}
		}
	}
}

$layout_menu = "gerencia";
$title = traduz("RELATÓRIO DE OS LANÇADAS SEM PEDIDO GERADO");
include 'cabecalho_new.php';

$plugins = array(
	"autocomplete",
	"datepicker",
	"shadowbox",
	"mask",
	"dataTable"
	);

include("plugin_loader.php");

?>

<script type="text/javascript" charset="utf-8">

	$(function() {
		$.datepickerLoad(Array("data_final", "data_inicial"));
		$.autocompleteLoad(Array("produto", "peca", "posto"));
		Shadowbox.init();

		$("span[rel=lupa]").click(function () {	$.lupa($(this));});

		function formatItem(row) { return row[0] + " - " + row[1];}

		function formatResult(row) { return row[0]; }
	});

	function retorna_posto(retorno){
		$("#codigo_posto").val(retorno.codigo);
		$("#descricao_posto").val(retorno.nome);
	}

	function chamaAjax(linha,data_inicial,data_final,posto,produto,cache) {
		if (document.getElementById('div_sinal_' + linha).innerHTML == '+') {
			$.ajax({
				url: "mostra_os_peca_sem_pedido_ajax.php",
				type: "GET",
				data: {linha:linha,data_inicial:data_inicial,data_final:data_final,posto:posto,produto:produto},
				beforeSend: function(){
					$("#div_detalhe_"+linha).html("<img src='a_imagens/ajax-loader.gif'>");
				},
				complete: function(data){
					var dados = data.responseText;
					dados = dados.split("|");
					$("#div_detalhe_"+linha).html(dados[1]);
					$("#div_sinal_"+linha).html("-");
				}
			});
		} else {
			document.getElementById('div_detalhe_' + linha).innerHTML = "";
			document.getElementById('div_sinal_' + linha).innerHTML = '+';
		}
	}



</script>

<?php if (count($msg_erro["msg"]) > 0) { ?>
    <div class="alert alert-error"> <h4><?php echo $msg_erro["msg"][0]; ?></h4> </div>
<?php } ?>

<div class="row"> <b class="obrigatorio pull-right"> * <?=traduz('Campos obrigatórios') ?> </b> </div>
<form class="form-search form-inline tc_formulario" name="frm_pesquisa" method="POST" action="<?echo $PHP_SELF?>">
	<div class="titulo_tabela"> <?php echo traduz("Parâmetros de Pesquisa") ?></div>
	<br />
	<div class="container tc_container">
		<div class='row-fluid'>
			<div class='span2'></div>
			<div class='span4'>
				<div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='data_inicial'><?php echo traduz("Data Inicial") ?></label>
					<div class='controls controls-row'>
						<div class='span4'>
							<h5 class='asteristico'>*</h5>
							<input type="text" name="data_inicial_01" id="data_inicial" size="12" maxlength="10" class='span12' value= "<?=$data_inicial?>">
						</div>
					</div>
				</div>
			</div>
			<div class='span4'>
				<div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='data_inicial'><?php echo traduz("Data Final")?></label>
					<div class='controls controls-row'>
						<div class='span4'>
							<h5 class='asteristico'><?php echo traduz("*")?></h5>
							<input type="text" name="data_final_01" id="data_final" size="12" maxlength="10" class='span12' value="<?=$data_final?>" >
						</div>
					</div>
				</div>
			</div>
			<div class='span2'></div>
		</div>
	</div>

	<div class='row-fluid'>
		<div class='span2'></div>
		<div class='span4'>
			<div class='control-group'>
				<label class='control-label' for='codigo_posto'><?php echo traduz("Cod. Posto")?></label>
				<div class='controls controls-row'>
					<div class='span10 input-append'>
						<input type="text" id="codigo_posto" name="codigo_posto" class='span8' maxlength="20" value="<? echo $codigo_posto ?>" >
						<span class='add-on' rel="lupa" ><i class='icon-search'></i></span>
						<input type="hidden" name="lupa_config" tipo="posto" parametro="referencia" />
						<input type="hidden" name="posto" value="<?=$posto?>" >
					</div>
				</div>
			</div>
		</div>
		<div class='span4'>
			<div class='control-group'>
				<label class='control-label' for='descricao_posto'><?php echo traduz("Nome Posto")?></label>
				<div class='controls controls-row'>
					<div class='span10 input-append'>
						<input type="text" id="descricao_posto" name="posto_nome" class='span12' value="<? echo $posto_nome; ?>" >
						<span class='add-on' rel="lupa" ><i class='icon-search' ></i></span>
						<input type="hidden" name="lupa_config" tipo="posto" parametro="nome" />
					</div>
				</div>
			</div>
		</div>
		<div class='span2'></div>
	</div>

	<?php if ($login_fabrica == 156){ ?>
		<div class='row-fluid'>
			<div class='span2'></div>
			<div class='span4'>
				<div class='control-group'>
					<label class='control-label' for='status_os'><?php echo traduz("Status da OS")?></label>
					<div class='controls controls-row'>
						<div class='span4'>
							<select name="status_os" id="status_os">
								<option value=""></option>
								<?php foreach ($statusOs as $status) { ?>
									<option <?=($_POST['status_os'] == $status['status_checkpoint']) ? 'selected' : '' ?> value="<?=$status['status_checkpoint']?>"><?=$status['status_descricao']?></option>
								<?php } ?>
							</select>
						</div>
					</div>
				</div>
			</div>

			<div class='span2'></div>
		</div>

		<?php } ?>
	<br />
	<center>
		<input type="button" class='btn' value="Pesquisar" onclick="document.frm_pesquisa.acao.value='PESQUISAR'; document.frm_pesquisa.submit();" alt="P<?php echo traduz("Preencha as opções e clique aqui para pesquisar") ?>">
		<input type="hidden" name="acao">
	</center>
	<br />
</form>

<?php
if((strlen($btn_acao) > 0) && (count($msg_erro["msg"]) == 0)) {
	flush();
	$referencia		= $_POST['posto_referencia'];
	$descricao		= $_POST['posto_nome'];

	if (count($msg_erro["msg"]) == 0) {
		if (strlen($_POST["data_inicial_01"]) == 0 or $_POST["data_inicial_01"]=='dd/mm/aaaa') {
			$msg_erro["msg"][]    = $msgErrorPattern01;
			$msg_erro["campos"][] = traduz("data");
		}

		if (count($msg_erro["msg"]) == 0) {
			$data_inicial   = trim($_POST["data_inicial_01"]);
			$fnc            = pg_query($con,"SELECT fnc_formata_data('$data_inicial')");

			if (strlen(pg_last_error($con) ) > 0) {
				$msg_erro = traduz("Ocorreu um erro na busca dos Dados! _01");
			}

			if (count($msg_erro["msg"]) == 0) $aux_data_inicial = pg_fetch_result($fnc,0,0);
		}
	}

	if (count($msg_erro["msg"]) == 0) {
		if (strlen($_POST["data_final_01"]) == 0 or $_POST["data_final_01"] == 'dd/mm/aaaa') {
			$msg_erro["msg"][]    = $msgErrorPattern01;
			$msg_erro["campos"][] = traduz("data");
		}

		if (count($msg_erro["msg"]) == 0) {
			$data_final = trim($_POST["data_final_01"]);
			$fnc        = pg_query($con,"SELECT fnc_formata_data('$data_final')");

			if (strlen(pg_last_error($con)) > 0) {
				$msg_erro = traduz("Ocorreu um erro na busca dos Dados! _02");
			}

			if (count($msg_erro["msg"]) == 0) {
				$aux_data_final = pg_fetch_result($fnc,0,0);
			}
		}
	}

	if(empty($msg_erro)) {
		$sql = "SELECT '$aux_data_final'::date - INTERVAL '6 MONTHS' > '$aux_data_inicial' ";
		$res = pg_query($con,$sql);

		if (pg_fetch_result($res,0,0) == 't') {
			$msg_erro["msg"][]    = $msgErrorPattern02;
			$msg_erro["campos"][] = traduz("data");
		}
	}

	$codigo_posto = $_POST['codigo_posto'];
	$posto_nome   = $_POST['posto_nome'];

	$cond_1 = " 1=1 ";
	if(strlen($codigo_posto) > 0) {
		$sql = "SELECT posto from tbl_posto_fabrica where codigo_posto = '$codigo_posto' and fabrica = $login_fabrica";
		$res = pg_query($con,$sql);

		if(pg_num_rows($res)>0) {
			$posto = pg_fetch_result($res,0,posto);
			$cond_1 = "tbl_os.posto = $posto ";
		}
	}

	$cond_2 = " 1=1 ";
	if(strlen($referencia) > 0) {
		$sql = "SELECT produto FROM tbl_produto JOIN tbl_linha USING (linha) WHERE tbl_produto.referencia = '$referencia' AND tbl_linha.fabrica = $login_fabrica";
		$res = pg_query($con,$sql);

		if(pg_num_rows($res) > 0) {
			$produto = pg_fetch_result($res, 0, 0);
			if (in_array($login_fabrica, array(138)))	$cond_2 = "tbl_os_produto.produto = $produto ";
			else $cond_2 = "tbl_os.produto = $produto ";
		}
	}

	if (count($msg_erro["msg"]) == 0) {
		if (in_array($login_fabrica, array(138))) {
			$sql = "SELECT DISTINCT (tbl_os_produto.os),
		                        tbl_os.posto,
		                        tbl_os_produto.produto
		           		INTO TEMP tmp_os_qtde_$login_admin
				FROM tbl_os_produto
				JOIN tbl_os USING (os)
				WHERE tbl_os.fabrica = $login_fabrica
				AND $cond_1
				AND $cond_2
				AND tbl_os.data_abertura > '2009-01-01'
				AND tbl_os.data_fechamento IS NULL
				AND tbl_os.excluida is not true
				AND tbl_os.data_abertura BETWEEN '$aux_data_inicial 00:00:00' AND '$aux_data_final 23:59:59' ";
		} else {

			if($login_fabrica == 156 && (isset($_POST['status_os']) && strlen($_POST['status_os']) != 0 )){
				$status_os = $_POST['status_os'];
				$statusCheckpointDescricao = 'tbl_os.status_checkpoint, tbl_status_checkpoint.descricao AS status_descricao,';
				$joinStatusCheckpoint = "JOIN tbl_status_checkpoint ON tbl_status_checkpoint.status_checkpoint = tbl_os.status_checkpoint";
				$whereStatusCheckpoint = "AND tbl_os.status_checkpoint = $status_os";
			}else{
				$statusCheckpointDescricao = '';
				$joinStatusCheckpoint = '';
				$whereStatusCheckpoint = '';
			}

			if($login_fabrica == 163){
				$joinStatusCheckpoint = " JOIN tbl_tipo_atendimento ON tbl_tipo_atendimento.tipo_atendimento = tbl_os.tipo_atendimento ";
				$whereStatusCheckpoint = " AND tbl_tipo_atendimento.fora_garantia IS NOT TRUE ";
			}

			$sql = "SELECT  tbl_os.os,
		                        tbl_os.posto,
		                        {$statusCheckpointDescricao}
		                        tbl_os.produto
		           		INTO TEMP tmp_os_qtde_$login_admin
				FROM tbl_os
				{$joinStatusCheckpoint}
				WHERE tbl_os.fabrica = $login_fabrica
				AND $cond_1
				AND $cond_2
				{$whereStatusCheckpoint}
				AND tbl_os.data_abertura > '2009-01-01'
				AND tbl_os.data_fechamento IS NULL
				AND tbl_os.excluida is not true
				AND tbl_os.data_abertura BETWEEN '$aux_data_inicial 00:00:00' AND '$aux_data_final 23:59:59' ";
		}
	if($login_fabrica == 74) {
		$sql .= "
		AND tbl_os.os NOT IN (
			SELECT tbl_os_excluida.os
			FROM tbl_os_excluida
			WHERE tbl_os_excluida.fabrica = $login_fabrica
			AND tbl_os_excluida.data_abertura BETWEEN '$aux_data_inicial 00:00:00' AND '$aux_data_final 23:59:59'
		)";

	}

	if(in_array($login_fabrica, array(148))){
		$join_tipo_atendimento = " JOIN tbl_os ON tbl_os.os = tmp_os_qtde_$login_admin.os
			JOIN tbl_tipo_atendimento ON tbl_tipo_atendimento.tipo_atendimento = tbl_os.tipo_atendimento
				AND tbl_tipo_atendimento.entrega_tecnica IS NOT TRUE";
	}else{
		$join_tipo_atendimento = "";
	}

	$sql .= ";
		SELECT  tbl_posto_fabrica.codigo_posto,
			tbl_posto.nome as nome_posto,
			COUNT(DISTINCT tmp_os_qtde_$login_admin.os) as qtde
		FROM tmp_os_qtde_$login_admin
		JOIN tbl_os_produto USING (os)
		JOIN tbl_os_item USING (os_produto)
		JOIN tbl_produto on tmp_os_qtde_$login_admin.produto = tbl_produto.produto AND tbl_produto.fabrica_i = $login_fabrica
		JOIN tbl_peca on tbl_os_item.peca = tbl_peca.peca AND tbl_peca.fabrica = $login_fabrica
		JOIN tbl_posto on tmp_os_qtde_$login_admin.posto = tbl_posto.posto
		JOIN tbl_servico_realizado on tbl_servico_realizado.servico_realizado = tbl_os_item.servico_realizado AND tbl_servico_realizado.troca_de_peca IS TRUE AND tbl_servico_realizado.gera_pedido IS TRUE
		JOIN tbl_posto_fabrica on tbl_posto_fabrica.posto = tbl_posto.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
		{$join_tipo_atendimento}
		WHERE tbl_os_item.pedido IS NULL
		AND tbl_os_item.fabrica_i = $login_fabrica
		GROUP BY tbl_posto_fabrica.codigo_posto, tbl_posto.nome
		ORDER BY qtde DESC;";

		$res = pg_query($con,$sql);

		if (pg_num_rows($res) > 0) {
			echo "	<table class='table table-striped table-bordered table-fixed'>
					<thead>
					<tr class='titulo_tabela'>
						<td colspan='4'>
							<center>";
							if (strlen($codigo_posto) > 0 && strlen($posto_nome) > 0) {
								echo "<h4>".$codigo_posto." - ".$posto_nome." ".$_POST['data_inicial_01']. " " . traduz("até") . " " . $_POST['data_final_01']."</h4>";
							} else {
								echo "<h4>". traduz("Período de") .$_POST['data_inicial_01']." ".traduz("até")." " .$_POST['data_final_01']."</h4>";
							}

						echo "	</center>
						</td>
					</tr>
					<tr class='titulo_coluna'>
						<th>" . traduz("Ver OS")  ."</th>
						<th>" . traduz("Código")  ."</th>
						<th>" . traduz("Posto")   ."</th>
						<th>" . traduz("Qtde OS") ."</th>
					</tr>
					</thead>
					<tbody>";

			$total = pg_num_rows($res);
			$total_os = 0;
			if ($produto == '') {
				$produto = "0";
			}

			for ($i = 0; $i < pg_num_rows($res); $i++) {
				$nome 			= trim(pg_fetch_result($res, $i, nome_posto));
				$codigo_posto 		= trim(pg_fetch_result($res, $i, codigo_posto));
				$qtde			= trim(pg_fetch_result($res, $i, qtde));


				$total_os += $qtde;
				echo "	<tr>
					<td onmouseover='this.style.cursor=\"pointer\";' onclick=\"chamaAjax($i,'$aux_data_inicial','$aux_data_final','$codigo_posto',$produto,'$cachebypass')\">
						<div id=div_sinal_$i>+</div>
					</td>
					<td>$codigo_posto&nbsp;</td>
					<td>$nome&nbsp;</td>
					<td>$qtde&nbsp;</td>
				</tr>
				<tr>
					<td colspan='4'><div id='div_detalhe_$i'></div></td>
				</tr>";
			}
			echo "	</tbody>
					<tfoot>
					<tr class='titulo_coluna'>
						<td colspan='3' style='text-align:right;'>Total OS:</td>
						<td>$total_os&nbsp;</td>
					</tr>
					</tfoot>
					</table>";
		} else {
			echo "	<div class='alert'><h4>". traduz("Nenhum resultado encontrado") . "</h4></div>";
		}
	}
}

include "rodape.php" ;

?>
