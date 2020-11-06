<?php
include_once "dbconfig.php";
include_once "includes/dbconnect-inc.php";
include_once "autentica_admin.php";
include_once "funcoes.php";

$estado 	= $_GET['estado'];
$status 	= utf8_decode($_GET['status']);
$intervalo 	= $_GET['intervalo'];

if(!empty($estado)){
	$cond = " AND tbl_cidade.estado = '$estado' ";
}
if(!empty($status)){
	$cond_2 = " AND tbl_hd_chamado.status = '$status' ";
}
if(!empty($intervalo)){
	list($inicio,$fim) = explode("-", $intervalo);
	if($inicio > 55){
		$cond_3 = " AND data::date <= CURRENT_DATE - interval '$inicio days' ";
	}else{
		$cond_3 = " AND data::date BETWEEN CURRENT_DATE - interval '$fim days' and CURRENT_DATE - interval '$inicio days' ";
	}
}

if ($login_fabrica == 74) {
	$sql_ultima_alteracao = ", tbl_hd_chamado_extra.nome AS nome_consumidor, 
							  TO_CHAR((SELECT data 
							  FROM tbl_hd_chamado_item 
							  WHERE hd_chamado = tbl_hd_chamado.hd_chamado
							  ORDER BY data DESC 
							  LIMIT 1), 'DD/MM/YYYY') AS ultima_alteracao";
}

if($login_fabrica == 74){
    $cond_admin_fale_conosco = " AND tbl_hd_chamado.status IS NOT NULL ";
}

$sql = "SELECT tbl_hd_chamado.hd_chamado,
				tbl_hd_chamado.status,
				to_char(tbl_hd_chamado.data,'DD/MM/YYYY') as data_abertura,
				to_char(tbl_hd_chamado.resolvido,'DD/MM/YYYY') as data_fechamento,
				tbl_hd_chamado_extra.os,
				tbl_cidade.nome AS cidade,
				tbl_cidade.estado,
				tbl_produto.referencia AS ref_produto,
				tbl_produto.descricao AS desc_produto,
				tbl_posto_fabrica.codigo_posto,
				tbl_posto.nome AS nome_posto
				$sql_ultima_alteracao
			FROM tbl_hd_chamado
			JOIN tbl_hd_chamado_extra ON tbl_hd_chamado.hd_chamado = tbl_hd_chamado_extra.hd_chamado
			JOIN tbl_cidade ON tbl_hd_chamado_extra.cidade = tbl_cidade.cidade
			LEFT JOIN tbl_posto ON tbl_hd_chamado_extra.posto = tbl_posto.posto
			LEFT JOIN tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto
			AND tbl_posto_fabrica.fabrica = $login_fabrica
			LEFT JOIN tbl_produto ON tbl_hd_chamado_extra.produto = tbl_produto.produto
			AND tbl_produto.fabrica_i = $login_fabrica
		WHERE tbl_hd_chamado.fabrica = $login_fabrica	
		AND upper(tbl_hd_chamado.status) NOT IN('PROTOCOLO DE INFORMACAO','RESOLVIDO') 
		$cond 
		$cond_2
		$cond_3 
		$cond_admin_fale_conosco";
$resSubmit = pg_query($con,$sql);
$count = pg_num_rows($resSubmit);
#echo nl2br($sql);

$layout_menu = "callcenter";
$title = "RELATÓRIO DE REGISTRO DE PROCESSOS";

include_once "cabecalho_new.php";

$plugins = array(
	"autocomplete",
	"datepicker",
	"shadowbox",
	"mask",
	"dataTable"
);

include("plugin_loader.php");

?>
</div>
<table id="resultado_atendimento" class='table table-striped table-bordered table-hover table-large' >
	<thead>
		<tr class='titulo_coluna' >
			<th>Atendimento</th>
			<th>Data Abertura</th>
			<th>Produto</th>
			<?php
			if ($login_fabrica != 74) {
			?>
				<th>OS</th>
			<?php
			}

			if ($login_fabrica == 74) {
			?>
				<th>Consumidor</th>
			<?php
			}
			?>
			<th>Cidade</th>
			<th>Estado</th>
			<th>Posto</th>
			<?php
			if ($login_fabrica == 74) {
			?>
				<th>Última Alteração</th>
			<?php
			}
			?>
			<th>Status</th>
		</tr>
	</thead>
	<tbody>
		<?php
			for ($i = 0; $i < $count; $i++) {
				$hd_chamado 	 = pg_fetch_result($resSubmit, $i, 'hd_chamado');
				$data_abertura   = pg_fetch_result($resSubmit, $i, 'data_abertura');
				$data_fechamento = pg_fetch_result($resSubmit, $i, 'data_fechamento');
				$ref_produto 	 = pg_fetch_result($resSubmit, $i, 'ref_produto');
				$desc_produto 	 = pg_fetch_result($resSubmit, $i, 'desc_produto');
				$os 			 = pg_fetch_result($resSubmit, $i, 'os');
				$cidade 		 = pg_fetch_result($resSubmit, $i, 'cidade');
				$estado 		 = pg_fetch_result($resSubmit, $i, 'estado');
				$codigo_posto 	 = pg_fetch_result($resSubmit, $i, 'codigo_posto');
				$nome_posto 	 = pg_fetch_result($resSubmit, $i, 'nome_posto');
				$status 		 = pg_fetch_result($resSubmit, $i, 'status');

				if ($login_fabrica == 74) {
					$ultima_alteracao = pg_fetch_result($resSubmit, $i, "ultima_alteracao");
					$nome_consumidor = pg_fetch_result($resSubmit, $i, "nome_consumidor");
				}

				$produto = (!empty($ref_produto)) ? "{$ref_produto} - {$desc_produto}" : "";
				$posto = (!empty($codigo_posto)) ? "{$codigo_posto} - {$nome_posto}" : "";
				echo "	<tr>
							<td class='tac' nowrap><a href='callcenter_interativo_new.php?callcenter={$hd_chamado}' target='_blank' >{$hd_chamado}</a></td>
							<td class='tac' nowrap>{$data_abertura}</td>	
							<td class='tal' nowrap>{$produto}</td>";						
							if ($login_fabrica != 74) {
								echo "<td class='tac' nowrap><a href='os_press.php?os={$os}' target='_blank' >{$os}</a></td>";
							}
							
							if ($login_fabrica == 74) {
								echo "<td class='tal'>{$nome_consumidor}</td>";
							}

							echo "<td class='tal' nowrap>{$cidade}</td>
							<td class='tal' nowrap>{$estado}</td>
							<td class='tal' nowrap>{$posto}</td>";

							if ($login_fabrica == 74) {
								echo "<td class='tal'>{$ultima_alteracao}</td>";
							}
							
							echo "<td class='tal' nowrap>{$status}</td>
						</tr>";
			}
				?>
			</tbody>
		</table>
<?php
	if($count < 50){
		?>
			<script>
					$.dataTableLoad({
						table: "#resultado_atendimento",
						type: "custom",
						config: [ "info" ]
					});	
			</script>
		<?php
		}else{
		?>
			<script>
					$.dataTableLoad({
						table: "#resultado_atendimento"
					});	
			</script>
		<?php
		}
	include "rodape.php";
?>
