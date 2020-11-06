<?php
include_once "dbconfig.php";
include_once "includes/dbconnect-inc.php";
include_once "autentica_admin.php";
include_once "funcoes.php";

if ($_POST["btn_acao"] == "submit") {
	$estado	= $_REQUEST["estado"];

	if (!empty($estado)) {
		$cond = " AND tbl_cidade.estado = '$estado' ";
	}

	if($login_fabrica == 74){
        $cond_admin_fale_conosco = " AND tbl_hd_chamado.status IS NOT NULL ";
    }

	$sql = "SELECT tbl_hd_chamado.hd_chamado,tbl_hd_chamado.status, tbl_hd_chamado.data::date as data
			INTO TEMP tmp_atendimento_por_estado
			FROM tbl_hd_chamado 
			JOIN tbl_hd_chamado_extra ON tbl_hd_chamado.hd_chamado = tbl_hd_chamado_extra.hd_chamado
			JOIN tbl_cidade ON tbl_hd_chamado_extra.cidade = tbl_cidade.cidade
			WHERE tbl_hd_chamado.fabrica = $login_fabrica
			AND UPPER(tbl_hd_chamado.status) NOT IN('PROTOCOLO DE INFORMACAO','RESOLVIDO') 
			$cond_admin_fale_conosco 
			$cond";
	$res = pg_query($con,$sql);
	if(pg_last_error($con)){
		$msg_erro['msg'][] = pg_last_error($con);
	}else{
		$sql = "SELECT count(hd_chamado) AS qtde FROM tmp_atendimento_por_estado";
		$res = pg_query($con,$sql);
		$total_atendimento = pg_fetch_result($res, 0, 'qtde');

		$sql = "SELECT status, count(hd_chamado) as qtde
				FROM tmp_atendimento_por_estado
				GROUP BY status
				ORDER BY status";
		$resS = pg_query($con,$sql);
		$count = pg_num_rows($resS);
	}
	
}

$layout_menu = "callcenter";
$title = "RELATÓRIO DE PRODUTIVIDADE";

include_once "cabecalho_new.php";

$plugins = array(
    "autocomplete",
    "shadowbox",
    "datepicker",
);

include("plugin_loader.php");

if ($_RESULT && !$_POST) {
	$valor_input = $_RESULT;
} else {
	$valor_input = $_POST;
}

$inputs = array(
	"estado" => array(
		"span"      => 4,
		"label"     => "Estado",
		"type"      => "select",
		"width"     => 8,
		"options"  => array(),
	),
);

$array_estado = array("AC"=>"AC - Acre","AL"=>"AL - Alagoas","AM"=>"AM - Amazonas",
  "AP"=>"AP - Amapá", "BA"=>"BA - Bahia", "CE"=>"CE - Ceará","DF"=>"DF - Distrito Federal",
  "ES"=>"ES - Espírito Santo", "GO"=>"GO - Goiás","MA"=>"MA - Maranhão","MG"=>"MG - Minas Gerais",
  "MS"=>"MS - Mato Grosso do Sul","MT"=>"MT - Mato Grosso", "PA"=>"PA - Pará","PB"=>"PB - Paraíba",
  "PE"=>"PE - Pernambuco","PI"=>"PI - Piauí","PR"=>"PR - Paraná","RJ"=>"RJ - Rio de Janeiro",
  "RN"=>"RN - Rio Grande do Norte","RO"=>"RO - Rondônia","RR"=>"RR - Roraima",
  "RS"=>"RS - Rio Grande do Sul", "SC"=>"SC - Santa Catarina","SE"=>"SE - Sergipe",
  "SP"=>"SP - São Paulo","TO"=>"TO - Tocantins");

foreach ($array_estado as $key => $value) {
	$inputs['estado']['options'][$key] = $value;
}
?>

<script language="javascript">	
	
	function listarChamados(estado,status,intervalo){
		if (status != undefined && status.length > 0) {
			status = encodeURIComponent(status);
		}

		var url = "listar_registro_processo.php?estado="+estado+"&intervalo="+intervalo+"&status="+status;
		window.open(url);
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

<form name="frm_condicao" method="POST" class="form-search form-inline tc_formulario" >
	<div class='titulo_tabela '>Parâmetros de Pesquisa</div>
	<br/>

	<?php
		echo montaForm($inputs, $hiddens);
	?>

	<p><br/>
		<button class='btn' id="btn_acao" type="button"  onclick="submitForm($(this).parents('form'));">Consultar</button>
		<input type='hidden' id="btn_click" name='btn_acao' value='' />
	</p><br/>
</form>

<?php
if ($_POST["btn_acao"] == "submit" AND count($msg_erro['msg']) == 0) {
	if($count > 0){
?>
		<table id="resultado_atendimento" align='center' class='table table-striped table-bordered table-hover table-large' >
			<thead>
				<tr class='titulo_coluna'>
					<th>Status</th>
					<th>Qtde</th>
					<th>%</th>
				</tr>
			<tbody>
		<?php
			for($i = 0; $i < $count;$i++){

				$status = pg_fetch_result($resS, $i, 'status');
				$qtde   = pg_fetch_result($resS, $i, 'qtde');
				$porcentagem = ($qtde / $total_atendimento) * 100;

				echo "<tr>
						<td class='tac'>{$status}</td>
						<td class='tac'><a href='#' onclick='listarChamados(\"$estado\",\"$status\",\"\")' >{$qtde}</a></td>
						<td class='tac'>".number_format($porcentagem,2,".",",")."</td>
					</tr>";
			}
			?>
			</tbody>
			<tfoot>
				<tr class="titulo_coluna">
				<td class="tac">Total</td>
				<td class="tac"><?=$total_atendimento?></td>
				<td>&nbsp;</td>
			</tr>
			</tfoot>
		</table>
		<br />
		<table id="resultado_atendimento" align='center' class='table table-striped table-bordered table-hover table-large' >
			<thead>
				<tr class='titulo_coluna' id='status' >
					<th>Período</th>
					<th>Qtde</th>
					<th>Porc. Parcial(%)</th>
					<th>Proc. Total(%)</th>
				</tr>
		<?php
		for($i = 0; $i < 60; $i += 5){
			if($i == 0){
				$inicio = $i;
				$fim = $i + 5;
			}else{
				$inicio = $i + 1;
				$fim = $inicio + 4;
			}

			if($i >= 55){
				$fim = "ou mais";
				$dia = 56;
				$sql = "SELECT count(hd_chamado) AS qtdee 
					FROM tmp_atendimento_por_estado
					WHERE data <= CURRENT_DATE - interval '$dia days'";
			}else{
				$sql = "SELECT count(hd_chamado) AS qtdee 
					FROM tmp_atendimento_por_estado
					WHERE data BETWEEN CURRENT_DATE - interval '$fim days' and CURRENT_DATE - interval '$inicio days'";			
			}
			
			#echo nl2br($sql)."<br><br>";
			$resT = pg_query($con,$sql);	
			$quantidade = 0;
			$quantidade   = pg_fetch_result($resT, 0, 'qtdee');
			
			$porcentagem = 0;
			if($quantidade > 0){
				$porcentagem = ($quantidade / $total_atendimento) * 100;
				$total_porcentagem += $porcentagem;
			}

			echo "<tr>
						<td class='tac'>{$inicio} - {$fim}</td>
						<td class='tac'><a href='#' onclick='listarChamados(\"$estado\",\"\",\"$inicio-$fim\")'>".$quantidade."</a></td>
						<td class='tac'>".number_format($porcentagem,2,".",",")."</td>
						<td class='tac'>".number_format($total_porcentagem,2,".",",")."</td>
					</tr>";

		}
		?>
			</tbody>
			<tfoot>
				<tr class="titulo_coluna">
					<td class="tac">Total</td>
					<td class="tac"><?=$total_atendimento?></td>
					<td>&nbsp;</td>
					<td>&nbsp;</td>
				</tr>
			</tfoot>
		</table>
<?php
	}else{
		echo '
			<div class="container">
			<div class="alert">
				    <h4>Nenhum resultado encontrado</h4>
			</div>
			</div>';
	}
}
include "rodape.php";
?>
