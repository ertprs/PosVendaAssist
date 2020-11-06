<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="financeiro,gerencia,call_center";
include 'autentica_admin.php';
include 'funcoes.php';

function cort($string, $num)
{
	if(strlen($string) > $num){
		$string = substr($string, 0, $num)."...";
	}
	return $string;
}

/* ---------------------- */

if ($_POST["btn_acao"] == "submit") {

	$data_inicial       	= $_POST['data_inicial'];
	$data_final         	= $_POST['data_final'];
	$codigo_posto       	= $_POST['codigo_posto'];
	$descricao_posto  		= $_POST['descricao_posto'];
	$referencia     		= $_POST['referencia'];
	$descricao      		= $_POST['descricao'];
	$estado             	= $_POST['estado'];
	$pedidos_faturados_no_periodo = $_POST['pedidos_faturados_no_periodo'][0];
	$pedido  				= $_POST['pedido'];
    $pedido                 = preg_replace('/\D/','',$pedido);

	if (strlen($codigo_posto) > 0 or strlen($descricao_posto) > 0){
		$sql = "SELECT tbl_posto_fabrica.posto
				FROM tbl_posto
				JOIN tbl_posto_fabrica USING(posto)
				WHERE tbl_posto_fabrica.fabrica = {$login_fabrica}
				AND (
					(UPPER(tbl_posto_fabrica.codigo_posto) = UPPER('{$codigo_posto}'))
					OR
					(TO_ASCII(UPPER(tbl_posto.nome), 'LATIN-9') = TO_ASCII(UPPER('{$descricao_posto}'), 'LATIN-9'))
				)";
		$res = pg_query($con ,$sql);

		if (!pg_num_rows($res)) {
			$msg_erro["msg"][]    = "Posto não encontrado";
			$msg_erro["campos"][] = "posto";
		} else {
			$posto = pg_fetch_result($res, 0, "posto");
		}
	}

	if (strlen($referencia) > 0){
		$sql = "SELECT tbl_peca.peca
				FROM tbl_peca
				WHERE tbl_peca.fabrica = {$login_fabrica}
				AND (UPPER(tbl_peca.referencia) = UPPER('{$referencia}')) ";
		$res = pg_query($con ,$sql);

		if (!pg_num_rows($res)) {
			$msg_erro["msg"][]    ="Peça não encontrada";
			$msg_erro["campos"][] = "peca";
		} else {
			$peca = pg_fetch_result($res, 0, "peca");
		}
	}

	if ((!strlen($data_inicial) or !strlen($data_final)) and empty($pedido)) {
		$msg_erro["msg"][]    = "Preencha os campos obrigatórios";
		$msg_erro["campos"][] = "data_inicial";
		$msg_erro["campos"][] = "data_final";
	} elseif(!empty($data_inicial) and !empty($data_final) ) {
		list($di, $mi, $yi) = explode("/", $data_inicial);
		list($df, $mf, $yf) = explode("/", $data_final);

		if (!checkdate($mi, $di, $yi) or !checkdate($mf, $df, $yf)) {
			$msg_erro["msg"][]    = "Data Inválida";
			$msg_erro["campos"][] = "data";
		} else {
			$aux_data_inicial = "{$yi}-{$mi}-{$di}";
			$aux_data_final   = "{$yf}-{$mf}-{$df}";

			if (strtotime($aux_data_final) < strtotime($aux_data_inicial)) {
				$msg_erro["msg"][]    = "Data Final não pode ser menor que a Data Inicial";
				$msg_erro["campos"][] = "data";
			}
		}
		if($pedidos_faturados_no_periodo == 't') {
				$cond_data = " AND tbl_faturamento.emissao BETWEEN '{$aux_data_inicial}' AND '{$aux_data_final}' ";
		}else{
				$cond_data = " AND tbl_pedido.data BETWEEN '{$aux_data_inicial}' AND '{$aux_data_final}' ";
		}
		if(empty($posto)){
			$sqlX = "SELECT '$aux_data_inicial'::date + interval '12 months' > '$aux_data_final'";
			$resSubmitX = pg_query($con,$sqlX);
			$periodo_6meses = pg_fetch_result($resSubmitX,0,0);
			if($periodo_6meses == 'f'){
				$msg_erro['msg'][] = "O intervalo entre as datas deve ser de no máximo 1 ano";
			}
		}else{
			$sqlX = "SELECT '$aux_data_inicial'::date + interval '12 months' > '$aux_data_final'";
			$resSubmitX = pg_query($con,$sqlX);
			$periodo_6meses = pg_fetch_result($resSubmitX,0,0);
			if($periodo_6meses == 'f'){
				$msg_erro['msg'][] = "O intervalo entre as datas deve ser de no máximo 12 meses";
			}
		}
	}

	if (!count($msg_erro["msg"])) {

		if (!empty($peca)) {
			$cond_peca = " AND tbl_peca.peca = $peca";
		}

		if($pedidos_faturados_no_periodo !='t') {
			if (!empty($posto)) {
				$cond_posto = " AND tbl_pedido.posto = {$posto} ";
			}else{
				$cond_posto = " AND COALESCE(tbl_faturamento.posto, 0) <> 6359 ";
			}

			if ($estado) {
				$cond_estado = " AND tbl_posto_fabrica.contato_estado = '$estado' ";
			}


			if (!empty($pedido)) {
				$cond_pedido = " AND tbl_pedido.pedido = $pedido";
			}
		}else{
			if (!empty($posto)) {
				$cond_posto = " AND tbl_faturamento.posto = {$posto} ";
			}else{
				$cond_posto = " AND COALESCE(tbl_faturamento.posto, 0) <> 6359 ";
			}

			if ($estado) {
				$cond_estado = " AND tbl_posto_fabrica.contato_estado = '$estado' ";
			}

			if (!empty($pedido)) {
				$cond_pedido = " AND tbl_faturamento_item.pedido = $pedido";
			}	
		}


		if(!isset($_POST['gerar_excel'])){
			$limit = " LIMIT 501 ";
		}

		$display = "display:none";
		if($telecontrol_distrib AND $pedidos_faturados_no_periodo == 't'){
			$display = "display:block";

			$sql = "SELECT DISTINCT tbl_peca.peca,
                  tbl_peca.referencia,
				  tbl_peca.descricao,
				  tbl_faturamento_item.qtde as qtde_faturada_distribuidor,
				  tbl_faturamento_item.pedido, 
				  tbl_posto.nome as nome_posto,
				  tbl_faturamento.nota_fiscal,
				  to_char(tbl_pedido.data,'DD/MM/YYYY') as data_pedido,
				  tbl_faturamento.emissao as data_nota_fiscal,
				case when tbl_faturamento.cfop ~* '949' then 'Garantia' else 'Faturado' end as condicao,
				  tbl_faturamento_item.preco,
				  tbl_pedido_item.ipi,
				  coalesce(tbl_pedido.desconto,0) AS desconto,
				  tbl_faturamento_item.os
				into temp tmp_gf_$login_admin
				FROM tbl_faturamento
				JOIN tbl_faturamento_item USING(faturamento)
				LEFT JOIN tbl_posto USING(posto)
				LEFT JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto and tbl_posto_fabrica.fabrica = $login_fabrica
                		JOIN tbl_peca ON tbl_faturamento_item.peca = tbl_peca.peca
                		JOIN tbl_pedido ON tbl_faturamento_item.pedido = tbl_pedido.pedido
				JOIN tbl_pedido_item ON tbl_faturamento_item.pedido = tbl_pedido_item.pedido AND (tbl_faturamento_item.peca = tbl_pedido_item.peca OR tbl_faturamento_item.peca = tbl_pedido_item.peca_alternativa)
				WHERE tbl_peca.fabrica = $login_fabrica
				and tbl_faturamento.fabrica = 10 
				and tbl_faturamento.nota_fiscal <> '000000'
				and tbl_faturamento_item.pedido notnull 
				and (tbl_faturamento.posto <> 4311 or tbl_faturamento.posto isnull)
                $cond_data
                $cond_posto
                $cond_estado
                $cond_peca
                $cond_pedido;

				SELECT distinct peca,referencia, descricao, pedido, nome_posto,  condicao,  nota_fiscal, data_pedido, data_nota_fiscal,preco,ipi,desconto,os FROM tmp_gf_$login_admin order by referencia
";
#echo nl2br($sql); exit;
			$resSubmit = pg_query($con, $sql);

		}else{
			$display = "display:block";
			$sql = "SELECT distinct 
							tbl_pedido.pedido,
							to_char(tbl_pedido.data, 'DD/MM/YYYY') as data,
							tbl_posto_fabrica.codigo_posto,
							tbl_posto.nome,
							tbl_posto_fabrica.contato_estado,
							tbl_peca.referencia,
							tbl_peca.peca,
							tbl_peca.descricao,
							tbl_os_item.os_item,
							tbl_pedido_item.qtde,
							tbl_pedido_item.preco,
							tbl_pedido_item.ipi,
							coalesce(tbl_pedido.desconto,0) AS desconto,
							case when tbl_condicao.descricao ~* 'garantia' then 'Garantia' else 'Faturado' end as condicao
					FROM tbl_pedido
					JOIN tbl_posto_fabrica USING(posto,fabrica)
					JOIN tbl_posto USING(posto)
					JOIN tbl_condicao ON tbl_pedido.condicao = tbl_condicao.condicao
					JOIN tbl_pedido_item ON tbl_pedido_item.pedido = tbl_pedido.pedido
					JOIN tbl_peca ON tbl_pedido_item.peca = tbl_peca.peca
					$joinPeriodo
					LEFT JOIN tbl_os_item ON tbl_pedido_item.pedido = tbl_os_item.pedido and tbl_pedido_item.peca = tbl_os_item.peca
					WHERE tbl_pedido.fabrica = $login_fabrica
					AND  tbl_pedido.distribuidor notnull
					$cond_data
					$cond_estado
					$cond_peca
					$cond_pedido
					$condFaturamento
					{$limit}";
			$resSubmit = pg_query($con, $sql);
		}

	}

	if(isset($_POST['gerar_excel'])){

		$th_pecas = "<th nowrap>PEÇAS</th>";
		$th_pecas2 = "<th bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;'>Peças</th>";

		$data = date("d-m-Y-H:i");

		$filename = "relatorio-os-{$data}-$login_admin.xls";

		$file = fopen("/tmp/{$filename}", "w");

		if($pedidos_faturados_no_periodo == 't'){
			fwrite($file, "
			<table border='1'>
				<thead>
					<tr>
						<th>Referência</th>
						<th>Descrição</th>
						<th>Nome Posto</th>
						<th>Pedido</th>
						<th>OS</th>
						<th>Data Pedido</th>
						<th>Nota Fiscal</th>
						<th>Data Nota Fiscal</th>
						<th>Condição</th>
						<th>Preço</th>
						<th>IPI (%)</th>
						<th>Desconto (%)</th>
						<th>Quantidade Garantia</th>
						<th>Quantidade Faturado</th>
						<th>Total Enviado</th>
					</tr>
				</thead>
				<tbody>
		");
		}else{
			fwrite($file, "
			<table border='1'>
				<thead>
					<tr>
						<th>Pedido</th>
						<th>OS</th>
						<th>Condição</th>
						<th>Posto</th>
						<th>Razão Social</th>
						<th>Estado</th>
						<th>Data</th>
						<th>Referência</th>
						<th>Descrição</th>
						<th>Máquina</th>
						<th>Qtde</th>
						<th>Preço</th>
						<th>IPI (%)</th>
						<th>Desconto (%)</th>
					</tr>
				</thead>
				<tbody>
		");
		}

		for ($i = 0; $i < pg_num_rows($resSubmit); $i++) {

				if($pedidos_faturados_no_periodo == 't'){

					$peca = pg_fetch_result($resSubmit, $i, 'peca');
					$referencia = pg_fetch_result($resSubmit,$i,'referencia');
					$descricao = pg_fetch_result($resSubmit,$i,'descricao');
					$osFaturamento = pg_fetch_result($resSubmit, $i, 'os');

					$pedido = pg_fetch_result($resSubmit,$i,'pedido');
					$nome_posto = pg_fetch_result($resSubmit,$i,'nome_posto');
					$desc_condicao = pg_fetch_result($resSubmit,$i,'condicao');
					$data_pedido = pg_fetch_result($resSubmit,$i,'data_pedido');
					$data_nota_fiscal = mostra_data(pg_fetch_result($resSubmit,$i,'data_nota_fiscal'));
					$nota_fiscal = pg_fetch_result($resSubmit,$i,'nota_fiscal');
					$preco = pg_fetch_result($resSubmit,$i,'preco');
					$ipi = pg_fetch_result($resSubmit,$i,'ipi');
					$desconto = pg_fetch_result($resSubmit,$i,'desconto');


					$sqlFaturada = "SELECT sum(coalesce(qtde_faturada_distribuidor,0)) as quantidade,
											case when condicao ~* 'garantia' then 'Garantia' else 'Faturado' end as cond
									FROM tmp_gf_$login_admin
									WHERE peca = $peca
									AND pedido = $pedido
									AND preco  = '$preco'
									AND nota_fiscal='$nota_fiscal'
									GROUP BY cond,pedido
													";
					$resFaturada = pg_query($con,$sqlFaturada);
					$total_enviado = 0 ;
					$total_garantia = 0 ;
					$total_faturado = 0 ;
					for ($x=0; $x < pg_num_rows($resFaturada); $x++) {


						$quantidade = pg_fetch_result($resFaturada, $x, 'quantidade');
						$condicao 	= pg_fetch_result($resFaturada, $x, 'cond');

						if($condicao == "Garantia"){
								$total_garantia = $quantidade;
								break;
						}else{
								$total_faturado = $quantidade;
								break;
						}
					}

					$total_enviado = $total_garantia + $total_faturado;
				}else{
					$pedido = pg_fetch_result($resSubmit,$i,'pedido');
					$data = pg_fetch_result($resSubmit,$i,'data');
					$codigo_posto = pg_fetch_result($resSubmit,$i,'codigo_posto');
					$nome = pg_fetch_result($resSubmit,$i,'nome');
					$contato_estado = pg_fetch_result($resSubmit,$i,'contato_estado');
					$referencia = pg_fetch_result($resSubmit,$i,'referencia');
					$descricao = pg_fetch_result($resSubmit,$i,'descricao');
					$os_item = pg_fetch_result($resSubmit,$i,'os_item');
					$qtde = pg_fetch_result($resSubmit,$i,'qtde');
					$condicao = pg_fetch_result($resSubmit,$i,'condicao');
					$preco = pg_fetch_result($resSubmit,$i,'preco');
					$ipi = pg_fetch_result($resSubmit,$i,'ipi');
					$desconto = pg_fetch_result($resSubmit,$i,'desconto');
					$sua_os =  "";
					$referencia_produto = "";
					$descricao_produto = "";
				}
				if(!empty($os_item)) {

						$sql2 = "SELECT tbl_os.sua_os,
										tbl_produto.referencia,
										tbl_produto.descricao
								FROM tbl_os_item
								JOIN tbl_os_produto USING(os_produto)
								JOIN tbl_os USING(os)
								JOIN tbl_produto ON tbl_os.produto = tbl_produto.produto
								WHERE os_item = $os_item";
						$resSubmit2 = pg_query($con, $sql2);
						if(pg_num_rows($resSubmit2) >0 ) {
								$sua_os = pg_fetch_result($resSubmit2,0,'sua_os');
								$referencia_produto = pg_fetch_result($resSubmit2,0,'referencia');
								$descricao_produto = pg_fetch_result($resSubmit2,0,'descricao');
						}
				}

				if($pedidos_faturados_no_periodo == 't'){
					fwrite($file, "
					<tr class='tac' style='text-align:center'>
							<td>$referencia</td>
							<td>$descricao</td>
							<td>$nome_posto</td>
							<td>$pedido</td>
							<td>$osFaturamento</td>
							<td>$data_pedido</td>
							<td>$nota_fiscal</td>
							<td>$data_nota_fiscal</td>
							<td>$desc_condicao</td>
							<td>".number_format($preco,2,',','.')."</td>
							<td>$ipi</td>
							<td>$desconto</td>
							<td>$total_garantia</td>
							<td>$total_faturado</td>
							<td>$total_enviado</td>
					</tr>"
				);


				}else{
					fwrite($file, "
					<tr class='tac' style='text-align:center'>
							<td>$pedido</td>
							<td>$sua_os</td>
							<td>$condicao</td>
							<td>$codigo_posto</td>
							<td>$nome</td>
							<td>$contato_estado</td>
							<td>$data</td>
							<td>$referencia</td>
							<td>$descricao</td>
							<td>$referencia_produto - $descricao_produto</td>
							<td>$qtde</td>
							<td>".number_format($preco,2,',','.')."</td>
							<td>$ipi</td>
							<td>$desconto</td>

					</tr>"
				);
				}

		}

		fwrite($file, "
					<tr>
						<th colspan='$rows' bgcolor='#596D9B' color='#FFFFFF' style='color: #FFFFFF !important;' >Total de ".pg_num_rows($resSubmit)." registros</th>
					</tr>
				</tbody>
			</table>
		");

		// fwrite($file, $conteudo);

		fclose($file);

		if (file_exists("/tmp/{$filename}")) {
			system("mv /tmp/{$filename} xls/{$filename}");

			echo "xls/{$filename}";
		}

		exit;
	}

}

/* ---------------------- */

$layout_menu = "gerencia";
$title = "RELATÓRIO DE PEÇA POR GARANTIA/FATURADO";
include 'cabecalho_new.php';

$plugins = array(
	"autocomplete",
	"datepicker",
	"shadowbox",
	"mask",
	"dataTable",
	"informacaoCompleta"
);

include("plugin_loader.php");

$form = array(
	"data_inicial" => array(
		"span"      => 4,
		"label"     => "Data Início",
		"type"      => "input/text",
		"width"     => 4,
		"required"  => true,
		"maxlength" => 10
	),
	"data_final" => array(
		"span"      => 4,
		"label"     => "Data Final",
		"type"      => "input/text",
		"width"     => 4,
		"required"  => true,
		"maxlength" => 10
	),
	"codigo_posto" => array(
		"span"      => 4,
		"label"     => "Código do Posto",
		"type"      => "input/text",
		"width"     => 10,
		"lupa"      => array(
			"name" => "lupa_posto",
			"tipo" => "posto",
			"parametro" => "codigo"
		)
	),

	"descricao_posto" => array(
		"span"      => 4,
		"label"     => "Nome do Posto",
		"type"      => "input/text",
		"width"     => 10,
		"lupa"      => array(
			"name" => "lupa_posto",
			"tipo" => "posto",
			"parametro" => "nome"
		)
	),
	"referencia" => array(
		"span"      => 4,
		"label"     => "Referência",
		"type"      => "input/text",
		"width"     => 8,
		"lupa"      => array(
			"name" => "lupa_peca",
			"tipo" => "peca",
			"parametro" => "referencia"
		)
	),

	"descricao" => array(
		"span"      => 4,
		"label"     => "Descrição Peça",
		"type"      => "input/text",
		"width"     => 12,
		"lupa"      => array(
			"name" => "lupa_peca",
			"tipo" => "peca",
			"parametro" => "descricao"
		)
	),
	"pedido" => array(
		"span"      => 4,
		"label"     => "Pedido",
		"type"      => "pedido",
		"width"     => "8",
		"type"      => "input/text",
	),


	"estado" => array(
		"span"      => 4,
		"label"     => "Estado",
		"type"      => "select",
		"width"     => 8,
		"options"=>array('AC' => 'Acre',
				 'AL' => 'Alagoas',
				 'AP' => 'Amapá',
				 'AM' => 'Amazonas' ,
				 'BA' => 'Bahia',
				 'CE' => 'Ceará',
				 'DF' => 'Distrito Federal' ,
				 'GO' => 'Goiás' ,
				 'ES' => 'Espirito Santo',
				 'MA' => 'Maranhão',
				 'MT' => 'Mato Grosso',
				 'MS' => 'Mato Grosso do Sul',
				 'MG' => 'Minas Gerais',
				 'PA' => 'Pará',
				 'PB' => 'Paraíba',
				 'PR' => 'Paraná',
				 'PE' => 'Pernambuco',
				 'PI' => 'Piaui',
				 'RJ' => 'Rio de Janeiro',
				 'RN' => 'Rio Grande do Norte',
				 'RS' => 'Rio Grande do Sul',
				 'RO' => 'Rondônia',
				 'RR' => 'Roraima',
				 'SC' => 'Santa Catarina',
				 'SE' => 'Sergipe',
				 'SP' => 'São Paulo',
				 'TO' => 'Tocantins',
			 )
  ),
);

if($telecontrol_distrib){
	$form["pedidos_faturados_no_periodo"] = array(
    "type" => "checkbox",
    "span" => 4,
    "title" =>"Pedidos faturados no período",
    "checks" => array(
        "t" => "Pedidos faturados no período"
    )
	);
}
?>

<script type="text/javascript">
	$(function() {
		$.datepickerLoad(Array("data_final", "data_inicial"));
		$.autocompleteLoad(Array("posto","peca"));
		Shadowbox.init();

		$("span[rel=lupa_posto]").click(function () {
			$.lupa($(this));
		});

		$("span[rel=lupa_peca]").click(function () {
			$.lupa($(this));
		});


	});

	function retorna_posto(retorno){
        $("#codigo_posto").val(retorno.codigo);
		$("#descricao_posto").val(retorno.nome);
    }

	function retorna_peca(retorno){
        $("#referencia").val(retorno.referencia);
		$("#descricao").val(retorno.descricao);
    }

    function abreBoxPecas(box){
	if($('#boxpecas'+box).is(':visible')){
		$('#boxpecas'+box).hide();
	}else{
		$('#boxpecas'+box).show();
	}
    }
</script>

<?php
if (count($msg_erro["msg"]) > 0) {
?>
    <div class="alert alert-error">
		<h4><?=implode("<br />", $msg_erro["msg"])?></h4>
    </div>
<?php
}
?>

<style>
	table #resultado{
		margin-top: 5px !important;
	}
</style>

<div class="row">
	<b class="obrigatorio pull-right">  * Campos obrigatórios </b>
</div>

<form name='frm_relatorio' METHOD='POST' ACTION='<?=$PHP_SELF?>' align='center' class='form-search form-inline tc_formulario' >
		<div class='titulo_tabela '>Parâmetros de Pesquisa</div>
		<br/>
		<? echo montaForm($form,null);?>

		<p><br/>
			<button class='btn' id="btn_acao" type="button"  onclick="submitForm($(this).parents('form'));">Pesquisar</button>
			<input type='hidden' id="btn_click" name='btn_acao' value='' />
		</p><br/>
</form>
</div>

<?php

if(isset($resSubmit)){

	if (pg_num_rows($resSubmit) > 0) {

		if (pg_num_rows($resSubmit) > 500) {
			$count = 500;
		?>
			<div id='registro_max'>
				<h6>Em tela serão mostrados no máximo 500 registros, para visualizar todos os registros baixe o arquivo Excel no final da tela.</h6>
			</div>
		<?php
		} else {
			$count = pg_num_rows($resSubmit);
		}

		echo "<div class='tal' style='padding-rigth: 5px !important; $display'>";

			echo "


				<table id='resultado' class=\"table table-striped table-bordered table-hover table-large\" style='margin: 0 auto;'  >
					<thead>
						<tr class='titulo_coluna'>
					";

					if($pedidos_faturados_no_periodo == 't'){
						echo"
							<th>Referência</th>
							<th>Descrição</th>
							<th>Nome Posto</th>
							<th>Pedido</th>
							<th>OS</th>
							<th>Data Pedido</th>
							<th>Nota Fiscal</th>
							<th>Data Nota Fiscal</th>
							<th>Condição</th>
							<th>Preço</th>
							<th>IPI (%)</th>
							<th>Desconto (%)</th>
							<th>Quantidade Garantia</th>
							<th>Quantidade Faturado</th>
							<th>Total Enviado</th>
						";
					}else{
						echo "
							<th>Pedido</th>
							<th>OS</th>
							<th>Condição</th>
							<th>Posto</th>
							<th>Razão Social</th>
							<th>Estado</th>
							<th>Data</th>
							<th>Referência</th>
							<th>Descrição</th>
							<th>Máquina</th>
							<th>Qtde</th>
							<th>Preço</th>
							<th>IPI (%)</th>
							<th>Desconto (%)</th>

						";
					}
				echo"
					</tr>
					</thead>
					<tbody>
			";

			for ($i = 0; $i < $count; $i++) {

				if($pedidos_faturados_no_periodo == 't'){
					$referencia 		= pg_fetch_result($resSubmit,$i,'referencia');
					$descricao 			= pg_fetch_result($resSubmit,$i,'descricao');
					$peca 				= pg_fetch_result($resSubmit,$i,'peca');
					$osFaturamento      = pg_fetch_result($resSubmit,$i, 'os');

					$pedido = pg_fetch_result($resSubmit,$i,'pedido');
					$nome_posto = pg_fetch_result($resSubmit,$i,'nome_posto');
					$desc_condicao = pg_fetch_result($resSubmit,$i,'condicao');
					$data_pedido = pg_fetch_result($resSubmit,$i,'data_pedido');
					$data_nota_fiscal = mostra_data(pg_fetch_result($resSubmit,$i,'data_nota_fiscal'));
					$nota_fiscal = pg_fetch_result($resSubmit,$i,'nota_fiscal');
					$preco       = pg_fetch_result($resSubmit,$i,'preco');
					$ipi	     = pg_fetch_result($resSubmit,$i,'ipi');
					$desconto    = pg_fetch_result($resSubmit,$i,'desconto');

					$total = array();

					$sqlFaturada = "SELECT sum(coalesce(qtde_faturada_distribuidor,0)) as quantidade,
											case when condicao ~* 'garantia' then 'Garantia' else 'Faturado' end as cond
									FROM tmp_gf_$login_admin
									WHERE peca = $peca
									AND pedido = $pedido
									AND preco  = '$preco'
									AND nota_fiscal='$nota_fiscal'
									GROUP BY cond,pedido
														";
					$resFaturada = pg_query($con,$sqlFaturada);

					for ($x=0; $x < pg_num_rows($resFaturada); $x++) {

						$quantidade = pg_fetch_result($resFaturada, $x, 'quantidade');
						$condicao 	= pg_fetch_result($resFaturada, $x, 'cond');

						if($condicao == "Garantia"){
							$total['garantia'] = $quantidade;
							break;
						}else{
							$total['faturado'] = $quantidade;
							break;
						}

					}

					if(isset($total['faturado'])){
						$total_faturado = $total['faturado'];
					}else{
						$total_faturado = 0;
					}

					if(isset($total['garantia'])){
						$total_garantia = $total['garantia'];
					}else{
						$total_garantia = 0;
					}


					$total_enviado =  $total_faturado+$total_garantia;
					unset($total);
				}else{
					$pedido = pg_fetch_result($resSubmit,$i,'pedido');
					$data = pg_fetch_result($resSubmit,$i,'data');
					$data_nota_fiscal = pg_fetch_result($resSubmit,$i,'data_nf');
					$nota_fiscal = pg_fetch_result($resSubmit,$i,'nota_fiscal');
					$codigo_posto = pg_fetch_result($resSubmit,$i,'codigo_posto');
					$nome = pg_fetch_result($resSubmit,$i,'nome');
					$contato_estado = pg_fetch_result($resSubmit,$i,'contato_estado');
					$referencia = pg_fetch_result($resSubmit,$i,'referencia');
					$descricao = pg_fetch_result($resSubmit,$i,'descricao');
					$os_item = pg_fetch_result($resSubmit,$i,'os_item');
					$qtde = pg_fetch_result($resSubmit,$i,'qtde');
					$condicao = pg_fetch_result($resSubmit,$i,'condicao');
					$preco       = pg_fetch_result($resSubmit,$i,'preco');
					$ipi	     = pg_fetch_result($resSubmit,$i,'ipi');
					$desconto    = pg_fetch_result($resSubmit,$i,'desconto');
					$sua_os =  "";
					$referencia_produto = "";
					$descricao_produto = "";
				}
				if(!empty($os_item)) {

						$sql2 = "SELECT tbl_os.sua_os,
										tbl_os.os,
										tbl_produto.referencia,
										tbl_produto.descricao
								FROM tbl_os_item
								JOIN tbl_os_produto USING(os_produto)
								JOIN tbl_os USING(os)
								JOIN tbl_produto ON tbl_os.produto = tbl_produto.produto
								WHERE os_item = $os_item";
						$resSubmit2 = pg_query($con, $sql2);
						if(pg_num_rows($resSubmit2) >0 ) {
								$sua_os = pg_fetch_result($resSubmit2,0,'sua_os');
								$os = pg_fetch_result($resSubmit2,0,'os');
								$referencia_produto = pg_fetch_result($resSubmit2,0,'referencia');
								$descricao_produto = pg_fetch_result($resSubmit2,0,'descricao');
						}
				}
				echo "<tr style='text-align:center'>";
						if($pedidos_faturados_no_periodo == 't'){

							echo "
								<td><a href='peca_pedido_faturado_detalhe.php?peca=$peca&data_inicial=$aux_data_inicial&data_final=$aux_data_final&pedidos_faturados_no_periodo=$pedidos_faturados_no_periodo' target='_blank'> $referencia</td>
								<td>$descricao</td>
								<td>$nome_posto</td>
								<td> <a href='pedido_admin_consulta.php?pedido=$pedido' target='blank'>$pedido</a></td>
								<td> <a href='os_press.php?os={$osFaturamento}' target='_blank'>$osFaturamento</a></td>
								<td>$data_pedido</td>
								<td>$nota_fiscal</td>
								<td>$data_nota_fiscal</td>
								<td>$desc_condicao</td>
								<td class='tar'>".number_format($preco,2,',','.')."</td>
								<td class='tac'>$ipi</td>
								<td class='tac'>$desconto</td>
								<td class='tac'>{$total_garantia}</td>
								<td class='tac'>{$total_faturado}</td>
								<td class='tac'>{$total_enviado}</td>
							";
						}else{
						echo"
							<td><a href='pedido_admin_consulta.php?pedido=$pedido' target='_blank'>$pedido</a></td>
							<td><a href='os_press.php?os=$os' target='_blank'>$sua_os</a></td>
							<td>$condicao</td>
							<td>$codigo_posto</td>
							<td>$nome</td>
							<td align='center'>$contato_estado</td>
							<td>$data</td>
							<td>$referencia</td>
							<td>$descricao</td>
							<td>$referencia_produto  $descricao_produto</td>
							<td class='tac'>$qtde</td>
							<td class='tar'>".number_format($preco,2,',','.')."</td>
							<td class='tac'>$ipi</td>
							<td class='tac'>$desconto</td>

						";
						}
				echo"</tr>";

			}

			echo "
					</tbody>

				</table>
			";

			echo "<br />";

			if ($count > 50) {
			?>
				<script>
					$.dataTableLoad({ table: "#resultado" });
				</script>
			<?php
			}

			$jsonPOST = excelPostToJson($_POST);

			?>

			<br />

			<div id='gerar_excel' class="btn_excel">
				<input type="hidden" id="jsonPOST" value='<?=$jsonPOST?>' />
				<span><img src='imagens/excel.png' /></span>
				<span class="txt">Gerar Arquivo Excel</span>
			</div>


			<?php

		echo "</div>";

	}else{
		echo '
		<div class="container">
			<div class="alert">
				    <h4>Nenhum resultado encontrado</h4>
			</div>
		</div>';
	}

}

include 'rodape.php';?>
