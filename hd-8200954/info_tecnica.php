<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';
include_once 'funcoes.php';
include_once 'helpdesk/mlg_funciones.php';

$title = traduz('Menu Assistência Técnica');
$layout_menu = "tecnica";

switch ($login_fabrica) {
	case 148:
		$tipos = [
			"Vista Explodida",
			"Manual de Instruções / Operações",
			"Boletim Técnico",
			"Manual Técnico"
		];
		break;
	default:
		$tipos = [
			"Vista Explodida",
			"Manual de Instruções",
			"Alterações Técnicas",
			"Manual Técnico"
		];
		break;
}

$linha = $_POST['linha'];

$sqlPostoLinha = "
			AND (tbl_comunicado.linha IN
				(
					SELECT tbl_linha.linha
					FROM tbl_posto_linha
					JOIN tbl_linha ON tbl_linha.linha = tbl_posto_linha.linha
					WHERE fabrica =$login_fabrica
						AND tbl_linha.ativo IS TRUE
						AND posto = $login_posto
				)
				OR (
						tbl_comunicado.produto IS NULL AND
						tbl_comunicado.comunicado IN (
							SELECT tbl_comunicado_produto.comunicado
							FROM tbl_comunicado_produto
							JOIN tbl_produto ON tbl_comunicado_produto.produto = tbl_produto.produto
							JOIN tbl_posto_linha on tbl_posto_linha.linha = tbl_produto.linha
							WHERE fabrica_i = $login_fabrica AND
								  tbl_posto_linha.posto = $login_posto

						)

				)
				OR
				    (
					tbl_comunicado.linha IS NULL AND
					tbl_comunicado.produto in
						(
							SELECT tbl_produto.produto
						 	FROM tbl_produto
							JOIN tbl_linha ON tbl_linha.linha = tbl_produto.linha AND tbl_linha.ativo IS TRUE
							JOIN tbl_posto_linha ON tbl_produto.linha = tbl_posto_linha.linha
							WHERE fabrica_i = $login_fabrica AND
						 	posto = $login_posto
						)
					)

				 OR (tbl_comunicado.linha IS NULL AND tbl_comunicado.produto IS NULL AND
				 		tbl_comunicado.comunicado IN (
							SELECT tbl_comunicado_produto.comunicado
							FROM tbl_comunicado_produto
							JOIN tbl_produto ON tbl_comunicado_produto.produto = tbl_produto.produto
							JOIN tbl_linha ON tbl_linha.linha = tbl_produto.linha AND tbl_linha.ativo IS TRUE
							JOIN tbl_posto_linha on tbl_posto_linha.linha = tbl_produto.linha
							WHERE fabrica_i =$login_fabrica AND tbl_posto_linha.posto = $login_posto

							)

					)
			)
		";


if (!empty($linha)) {
	$condLinha = "AND dados_produtos.linha = $linha
				  AND dados_produtos.produto IS NULL";
}

if ($tipo_posto_multiplo) {
	$condPostoTipo = "
		tbl_comunicado.tipo_posto IN (
			SELECT tipo_posto 
			FROM tbl_posto_tipo_posto
			WHERE tbl_posto_tipo_posto.fabrica = {$login_fabrica}
			AND tbl_posto_tipo_posto.posto = {$login_posto}
		) ";
} else {
	$condPostoTipo = " tbl_comunicado.tipo_posto = $login_tipo_posto ";
}

$sql = "SELECT * FROM (
			SELECT	tbl_comunicado.comunicado,
					tbl_comunicado.descricao ,
					tbl_comunicado.mensagem  ,
					tbl_comunicado.linha,
					CASE WHEN tbl_comunicado.produto IS NULL THEN prod.produto ELSE tbl_produto.produto END AS produto,
					CASE WHEN tbl_comunicado.produto IS NULL THEN prod.referencia ELSE tbl_produto.referencia END AS referencia,
					CASE WHEN tbl_comunicado.produto IS NULL THEN prod.descricao ELSE tbl_produto.descricao END AS descricao_produto,
					to_char (tbl_comunicado.data,'dd/mm/yyyy') AS data,
					tbl_comunicado.extensao
			FROM	tbl_comunicado
			LEFT JOIN tbl_comunicado_produto ON tbl_comunicado_produto.comunicado = tbl_comunicado.comunicado
			LEFT JOIN tbl_produto ON tbl_produto.produto = tbl_comunicado.produto
			LEFT JOIN tbl_produto prod ON prod.produto = tbl_comunicado_produto.produto
			WHERE	tbl_comunicado.fabrica = $login_fabrica
			AND    ($condPostoTipo  OR  tbl_comunicado.tipo_posto IS NULL)
			AND     ((tbl_comunicado.posto           = $login_posto) OR (tbl_comunicado.posto           IS NULL))
			AND    tbl_comunicado.ativo IS NOT FALSE
			AND	   tbl_comunicado.tipo = $1
			AND    ($condPostoTipo  OR  tbl_comunicado.tipo_posto IS NULL)
			AND    (tbl_comunicado.posto IS NULL OR tbl_comunicado.posto = $login_posto)
			".$sqlPostoLinha."
			ORDER BY tbl_produto.descricao DESC, tbl_produto.referencia 
		) dados_produtos 
		WHERE 1=1
		{$condLinha}";
$statement = pg_prepare($con, "comunicados", $sql);

include __DIR__.'/cabecalho_new.php';

?>
<style>
	#titulo_linha {
		font-size: 14px;
		font-family: sans-serif;
	}

	#lista_tipos {
		width: 100%;
	}

	.tipos-header {
		background-color: #53a3b9;
		color: white;
		height: 50px;
		font-family: sans-serif;
		margin-bottom: 15px;
		font-size: 17px;
		border-radius: 7px;
		text-align: center;
		padding-top: 12px;
		display: block;
		cursor: pointer;
	}

	table.table {
		min-width: 100% !important;
	}

	.tipos-header:hover {
		background-color: #297083;
		transition: 0.25s ease-in;
	}

	.tipo-body {
		display: none;
	}

	.tipo-body, .tabela-comunicados {
		width: 100% !important;
	}

	#titulo {
		text-align: center;
		font-family: sans-serif;
		font-weight: bold;
		color: #086A87;
	}
</style>
<?php
$plugins = array(
   "datepicker",
   "shadowbox",
   "fancyzoom",
   "dataTable"
);

include __DIR__.'/admin/plugin_loader.php';
?>

<script>
	$(function(){

		Shadowbox.init();

		$("#linha").change(function(){
			if ($("#linha > option:selected").val() != "") {
				$("#formulario_linha").submit();
			}
		});

		$(".tipos-header").click(function(){
			$(this).next(".tipo-body").slideToggle();
		});

		$(".btn-lista-anexos").click(function(){

	    	let comunicado = $(this).attr("comunicado");

	    	Shadowbox.open({
	            content:    "exibe_anexos_boxuploader.php?comunicado="+comunicado,
	            player: "iframe",
	            title:      "Anexos do Comunicado",
	            width:  800,
	            height: 500
	        });
	    });

	});
</script>

	<form class='form-search form-inline tc_formulario' id="formulario_linha" action='<?= $_SERVER['PHP_SELF'] ?>' method='POST'>
		<div class='titulo_tabela'>
			<?=traduz('parametros.de.pesquisa', $con)?>
		</div>
		<br />
		<div class="row-fluid tac">
			<span id="titulo_linha"><?= traduz("Selecione uma linha de produtos") ?></span><br />
				<?php
				$sql_linha = "SELECT  tbl_linha.nome,
									  tbl_linha.linha
								FROM    tbl_linha
								WHERE   tbl_linha.fabrica = $login_fabrica
								AND     tbl_linha.ativo IS TRUE
								AND     tbl_linha.linha IN (
									SELECT tbl_posto_linha.linha
									FROM tbl_posto_linha
									JOIN tbl_linha ON tbl_linha.linha = tbl_posto_linha.linha AND tbl_linha.fabrica = $login_fabrica
									WHERE tbl_posto_linha.posto = $login_posto
									AND tbl_posto_linha.ativo IS TRUE
								)
								ORDER BY tbl_linha.nome;";
				$res_linha = pg_query ($con,$sql_linha);

				if (pg_num_rows($res_linha) > 0) { ?>
					<select class='span4' name='linha' id="linha">
						<option value=''><?= traduz('Selecione') ?></option>

						<?php
						while ($linha_posto = pg_fetch_array($res_linha)) {
							$linha_descricao = $linha_posto['nome'];
							$linha_id  	     = $linha_posto['linha'];

							$selected = "";
							if ($linha == $linha_id) {
								$selected          = "selected";
								$linha_selecionada = $linha_descricao;
							}

						?>
							<option value="<?= $linha_id ?>" <?= $selected ?>> <?= $linha_descricao ?></option>
						<?php	
						} ?>
						
					</select>
				<?php
				}
				?>
		</div>
		<br />
	</form>
</div>
<?php
if (!empty($linha)) { ?>
	<div class="container">
		<h3 id="titulo">Documentos - <?= $linha_selecionada ?></h3>
		<div id="lista_tipos">
			<?php
			foreach ($tipos as $tipo) { 

				$res = pg_execute($con, "comunicados", [
	                (string)$tipo
	            ]);

				?>
				<div class="tipos-header">
					<?= $tipo ?>
				</div>
				<div class="tipo-body">
					<?php
					if (pg_num_rows($res) > 0) { ?>
						<table class="table tabela-comunicados">
			        		<thead>
			        			<tr>
			        				<th>Data</th>
			        				<th>Título</th>
			        				<th>Anexos</th>
			        			</tr>
			        		</thead>
			        		<tbody>
					        <?php

					           	while ($dados = pg_fetch_array($res)) {

					           		$descricao  = $dados['descricao'];
					           		$data       = $dados['data'];
					           		$comunicado = $dados['comunicado'];

					           		?>
						           	<tr>
						           		<td class="tac">
						           			<?= $data ?>
						           		</td>
						           		<td>
						           			<?= $descricao ?>
						           		</td>
						           		<td class="tac">
						           			<button class="btn btn-small btn-primary btn-lista-anexos" comunicado="<?= $comunicado ?>">
						           				Visualizar anexos
						           			</button>
						           		</td>
						           	</tr>
					           	<?php
					           	} ?>
							</tbody>
						</table>
					<?php
					} else { ?>
						<div class="alert alert-warning">
							<h4>Sem resultados</h4>
						</div>
					<?php
					} ?>
				</div>
			<?php
			}
			?>
		</div>
	</div>
<?php
} ?>
	<script>
		$.dataTableLoad({ table: ".tabela-comunicados"});
	</script>
<?php
require_once('rodape');
?>

