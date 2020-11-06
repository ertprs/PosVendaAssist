<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="cadastros";
include 'autentica_admin.php';
include 'funcoes.php';
include_once '../class/AuditorLog.php';

	if($_POST['gerar_excel']){
		$file     = "xls/peca_nao_cadastrada-{$login_fabrica}.xls";
		$fileTemp = "/tmp/peca_nao_cadastrada-{$login_fabrica}.xls" ;
		$fp       = fopen($fileTemp,'w');

		$head = "".utf8_encode('código negrão').";".utf8_encode('referência de fábrica').";". utf8_encode('descrição').";total letras;". utf8_encode('apresentação').";total letras;".utf8_encode('descrição detalhada').";marca;un.;emb. 01;categoria;ncm;ipi %;II %;alt (cm);larg (cm);comp (cm);peso (kg);cod. barras 01;custo cip porto;\n\r";

		fwrite($fp, $head);

	}
	if (in_array($login_fabrica, array(171)))
	{
		$where_ref = 'AND p.referencia_fabrica IS NULL';
		$campos    = ', p.parametros_adicionais';
		$group_ref = ', p.parametros_adicionais';
	}else{
		$where_ref = '';
		$campos    = '';
		$group_ref = '';
	}

	$sql = "SELECT
				p.referencia,
				p.descricao,
				SUM(oi.qtde) AS qtde_pendente,
				MIN(o.data_digitacao) AS data_os_mais_antiga,
				p.unidade,
				p.ipi,
				p.peso
				{$campos}
			FROM tbl_os o
			INNER JOIN tbl_os_produto op ON op.os = o.os
			INNER JOIN tbl_os_item oi ON oi.os_produto = op.os_produto
			INNER JOIN tbl_servico_realizado sr ON sr.servico_realizado = oi.servico_realizado AND sr.fabrica = $login_fabrica
			INNER JOIN tbl_peca p ON p.peca = oi.peca AND p.fabrica = $login_fabrica
			WHERE o.fabrica = $login_fabrica
			AND sr.gera_pedido IS TRUE
			AND oi.pedido_item IS NULL
			AND p.pre_selecionada IS TRUE
			{$where_ref}
			GROUP BY p.referencia, p.descricao, p.unidade, p.ipi, p.peso
			{$group_ref}
			ORDER BY data_os_mais_antiga ASC";	
			
	$res = pg_query($con, $sql);
	$count = pg_num_rows($res);	

	$conteudo = "";
	for($i = 0; $i < $count; $i++){

		//$peca_id    			= pg_fetch_result($res, $i, "peca");
		$referencia 			= pg_fetch_result($res, $i, "referencia");
		$descricao  			= pg_fetch_result($res, $i, "descricao");
		$qtde_pendente      	= pg_fetch_result($res, $i, "qtde_pendente");
		$data_os_mais_antiga    = pg_fetch_result($res, $i, "data_os_mais_antiga");
		$unidade    			= pg_fetch_result($res, $i, "unidade");
		$ipi    				= pg_fetch_result($res, $i, "ipi");
		$peso    				= pg_fetch_result($res, $i, "peso");
		$parametros_adicionais  = pg_fetch_result($res, $i, "parametros_adicionais");
		$parametros_adicionais  = json_decode($parametros_adicionais);
		
		$conteudo .= "<tr>";
			$conteudo .=  "<td>$referencia - $descricao</td>";
			$conteudo .=  "<td class='tac'>{$qtde_pendente}</td>";
			$conteudo .=  "<td class='tac'>". mostra_data( substr($data_os_mais_antiga, 0, 16)) ." </td>";
		$conteudo .=  "</tr>";

		if($_POST['gerar_excel']){
			
			$tbody = ";$referencia;$descricao;".strlen($descricao).";{$parametros_adicionais->apresentacao};".strlen($parametros_adicionais->apresentacao).";$descricao;{$parametros_adicionais->marca};$unidade;{$parametros_adicionais->emb};{$parametros_adicionais->categoria};{$parametros_adicionais->ncm};$ipi %;{$parametros_adicionais->ii} %;{$parametros_adicionais->alt} (cm);{$parametros_adicionais->larg} (cm);{$parametros_adicionais->comp} (cm);{$parametros_adicionais->peso} (Kg);{$parametros_adicionais->cod_barra};{$parametros_adicionais->custo_cip};\n\r";

			fwrite($fp, $tbody);
		}
	}

	if($_POST['gerar_excel']){

        fclose($fp);

        if(file_exists($fileTemp)){
            system("mv $fileTemp $file");

            if(file_exists($file)){
                echo $file;
            }
        }
		exit;
	}

$layout_menu = "callcenter";
$title = "RELATÓRIO DE PEÇAS NÃO CADASTRADAS";
include 'cabecalho_new.php';

?>
<style>
	.desc_peca{
		text-transform: uppercase;
	}
</style>

<div class="row">
	<b class="obrigatorio pull-right">  * Campos obrigatórios </b>
</div>
<?php
		if(pg_num_rows($res) > 0){

			?>

			<table class="table table-bordered" id="listagem" style="width: 100%;">
				<thead>
					<tr class="titulo_tabela">
						<th colspan="4">Lista de Peças</th>
					</tr>
					<tr class="titulo_coluna">
						<th>Peça</th>
						<th>Qtde Pendente</th>
						<th>Data da OS mais antiga</th>
					</tr>
				</thead>
				<tbody>
					<?php
						echo $conteudo;
					?>
				</tbody>
			</table>

			<br />

			<?php

            $arr_excel = array(
				"acao" => "pesquisar_todos",
				"tipo" => $_POST["tipo"]
            );

            ?>

            <div id='gerar_excel' class="btn_excel">
		        <input type="hidden" id="jsonPOST" value='<?=json_encode($arr_excel)?>' />
		        <span><img src='imagens/excel.png' /></span>
		        <span class="txt">Gerar Arquivo Excel</span>
		    </div>

		    <br />

			<?php

			if(pg_num_rows($res_pecas) > 50){
				?>

				<script>
	                $.dataTableLoad({
	                    table : "#listagem"
	                });
	            </script>

				<?php
			}

		}else{
			?>
			<div class="alert alert-warning">
				<h4>Nenhum registro encontrado</h4>
			</div>
			<br />
			<?php
		}
?>

<?php
echo "</div>";
include "rodape.php";
?>
