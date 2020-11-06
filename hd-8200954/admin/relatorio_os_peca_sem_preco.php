<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="gerencia";
include 'autentica_admin.php';
include 'funcoes.php';

$layout_menu = "gerencia";
$title = "RELATÓRIO PEÇAS SEM PREÇO";
include 'cabecalho_new.php';

$plugins = array(
	"dataTable"
);

include("plugin_loader.php");
?>
<script type="text/javascript">

	$(function(){
		$(document).on("click", ".ver_os", function() {
	        var modal_reprova_os = $("#modal-dados-os");
	        var dados_os = $(this).data("dados_os");
	      	var result = dados_os.split(',');
	      	var value_result = "";
	      	var tr = "";
	      	$(result).each(function(key, value){
	      		value_result = value.split('-');
	      		tr += "<tr><td><a href='os_press.php?os="+value_result[0]+"' target='_blank'>"+value_result[1]+"</a></td><td>"+value_result[2]+"</td></tr>";
			});
			$("#tdbody-modal").html(tr);
	        $(modal_reprova_os).modal("show");
	    });

		$("#btn-close-modal-dados-os").click(function() {
            var modal_reprova_os = $("#modal-dados-os");
            var btn_fechar = $("#btn-close-modal-dados-os");
            $(modal_reprova_os).modal("hide");
        });

	});
	
</script>

<style type="text/css">
	#modal-dados-os {
       width: 80%;
       margin-left: -40%;
    }
</style>

<?php
$sql = "SELECT 
			ARRAY_TO_STRING(array_agg(DISTINCT(tmp_os_peca_sem_preco.os || '-' || tmp_os_peca_sem_preco.sua_os || '-' || tbl_os.consumidor_nome)), ', ', null) AS sua_os,
			ARRAY_TO_STRING(array_agg(DISTINCT(tmp_os_peca_sem_preco.sua_os || '-' || tbl_os.consumidor_nome)), ', ', null) AS sua_os_csv,
			tbl_peca.peca,
			tbl_peca.referencia,
			tbl_peca.descricao
		FROM tmp_os_peca_sem_preco 
		JOIN tbl_peca ON tbl_peca.peca = tmp_os_peca_sem_preco.peca AND tbl_peca.fabrica = {$login_fabrica}
		JOIN tbl_os ON tbl_os.os = tmp_os_peca_sem_preco.os AND tbl_os.fabrica = {$login_fabrica}
		WHERE tmp_os_peca_sem_preco.fabrica = {$login_fabrica}
		GROUP BY tbl_peca.peca";
$res = pg_query($con, $sql);

if (pg_num_rows($res) > 0){
	$data = date("d-m-Y-H:i");
	$file = "xls/relatorio_os_peca_sem_preco-$login_fabrica-$data.csv";
	$fp = fopen($file,"w");
	fwrite($fp, utf8_encode("OS;REF. PEÇA;DESCR. PEÇA\n"));
?>
	<div id="modal-dados-os" class="modal hide fade" data-backdrop="static" data-keyboard="false" >
		<div class="modal-body">
	       	<table id="dados_os" class='table table-striped table-bordered table-hover table-fixed' >
				<thead>
					<tr class='titulo_coluna' >
						<th>OS</th>
						<th>Consumidor</th>
					</tr>
				</thead>
				<tbody id="tdbody-modal">
				
				</tbody>
			</table>
	    </div>
	    <div class="modal-footer">
	        <button type="button" id="btn-close-modal-dados-os" class="btn">Fechar</button>
	    </div>
	</div>

	<table id="result_os_sem_preco" class='table table-striped table-bordered table-hover table-fixed' >
		<thead>
			<tr>
				<th colspan="3" class='titulo_tabela'>RELATÓRIO PEÇAS SEM PREÇO</th>
			</tr>
			<tr class='titulo_coluna' >
				<th>OS</th>
				<th>Ref. Peça</th>
				<th>Desc. Peça</th>
			</tr>
		</thead>
		<tbody>
			<?php
			
			for ($i = 0; $i < pg_num_rows($res); $i++) {
				$sua_os     = pg_fetch_result($res, $i, 'sua_os');
				$referencia = pg_fetch_result($res, $i, 'referencia');
				$descricao  = pg_fetch_result($res, $i, 'descricao');
				$peca       = pg_fetch_result($res, $i, 'peca');
				$sua_os_csv = pg_fetch_result($res, $i, 'sua_os_csv');

				$linha_csv = "{$sua_os_csv};{$referencia};{$descricao} \n";
				fwrite($fp, utf8_encode($linha_csv));
			?>
				<tr>
					<td class="tac">
						<button class='btn btn-small btn-primary ver_os' data-dados_os='<?=$sua_os?>' >Ver OS</button>
					</td>
					<td class="tac"><a href="peca_cadastro.php?peca=<?=$peca?>" target="_blank"><?=$referencia?></a></td>
					<td class="tac"><?=$descricao?></td>
				</tr>
			<?php
			}
			fclose($fp);
			?>
		</tbody>
	</table>
	<script>
		$.dataTableLoad({ table: "#result_os_sem_preco" });
	</script>
	<br />
		<div class="btn_excel" onclick="javascript: window.location='<?=$file?>';">		    
		    <span class="txt" style="background: #5e9c76;">Gerar Arquivo CSV</span>
		    <span><img style="width:40px ; height:40px;" src='imagens/icon_csv.png' /></span>
		</div>
	<?php
}else{
	echo '
	<div class="container">
	<div class="alert">
		    <h4>Nenhum resultado encontrado</h4>
	</div>
	</div>';
}
	



include 'rodape.php';?>
