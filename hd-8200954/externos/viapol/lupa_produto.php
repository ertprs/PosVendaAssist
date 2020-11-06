<?php
include "../../dbconfig.php";
include "../../includes/dbconnect-inc.php";
include "../../fn_traducao.php";

$login_fabrica 	= 189;
$parametro 		= $_REQUEST["parametro"];
$valor     		= trim($_REQUEST["valor"]);

?>
<!DOCTYPE html />
<html>
	<head>
		<meta http-equiv=pragma content=no-cache>
		<link href="../../bootstrap/css/bootstrap.css" type="text/css" rel="stylesheet" media="screen" />
    	<link href="../../bootstrap/css/extra.css" type="text/css" rel="stylesheet" media="screen" />
    	<link href="../../css/tc_css.css" type="text/css" rel="stylesheet" media="screen" />
    	<link href="../../bootstrap/css/ajuste.css" type="text/css" rel="stylesheet" media="screen" />
		<link href="../../plugins/dataTable.css" type="text/css" rel="stylesheet" />

		<script src="../../plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
		<script src="../../bootstrap/js/bootstrap.js"></script>
		<script src="../../plugins/dataTable.js"></script>
		<script src="../../plugins/resize.js"></script>
		<script src="../../plugins/shadowbox_lupa/lupa.js"></script>

		<script>
			$(function () {
				var option = {
						"sDom": "<'row'<'span6'l><'span6'f>r>t<'row'<'span6'i><'span6'p>>",
						"bInfo": false,
						"bFilter": false,
						"bLengthChange": false,
						"bPaginate": false,
						"aaSorting": [[2, "desc" ]]
					};
				$.dataTableLupa(option);

				$('#resultados').on('click', '.produto-item', function() {
					var info = JSON.parse($(this).attr('data-produto'));
 					if(info.reparo_na_fabrica == 't'){ //hd_chamado=2717074
						alert("<?= traduz('Posto de assistência não tem autorização para realizar reparo ou descaracterizar (trocar peça, ressoldar e outros) este produto.\n \nCliente deverá ser orientado a ligar para 0800-775-1400.') ?>");
					}else{
						if (typeof(info) == 'object') {
							window.parent.retorna_produto(info);
							window.parent.Shadowbox.close();
						}
					}
				});
			});
		</script>
	</head>

	<body>
		<div id="container_lupa" style="overflow-y:auto;z-index:1">
			<div id="topo">
				<img class="espaco" src="../../imagens/logo_new_telecontrol.png">
				<img class="lupa_img pull-right" src="../../imagens/lupa_new.png">
			</div>
			<br /><hr />
			<div class="row-fluid">
				<form action="<?=$_SERVER['PHP_SELF']?>" method='POST' >
				<div class="span1"></div>
					<div class="span4">
						<input type="hidden" name="posicao" value='<?=$posicao?>' />
						<select name="parametro"  >
								<option value="referencia" <?=($parametro == "referencia") ? "SELECTED" : ""?> ><?= traduz('Referência') ?></option>
								<option value="descricao"  <?=($parametro == "descricao")  ? "SELECTED" : ""?> ><?= traduz('Descrição') ?></option>
						</select>
					</div>
					<div class="span4">
						<input type="text" name="valor" class="span12" value="<?=$valor?>" />
					</div>
					<div class="span2">
						<button type="button" class="btn pull-right" onclick="$(this).parents('form').submit();"><?= traduz('Pesquisar') ?></button>
					</div>
					<div class="span1"></div>
				</form>
			</div>
		<?php 
			switch ($parametro) {
				case 'descricao':
                    $whereAdc = "(
                    UPPER(fn_retira_especiais(tbl_produto.descricao)) LIKE UPPER('%' || fn_retira_especiais('$valor') || '%') OR
                    UPPER(fn_retira_especiais(tbl_produto.nome_comercial)) LIKE UPPER('%' || fn_retira_especiais('{$valor}') || '%') OR (UPPER(fn_retira_especiais(tbl_produto_idioma.descricao)) LIKE UPPER('%' || fn_retira_especiais('{$valor}') || '%') AND tbl_produto_idioma.idioma = '{$sistema_lingua}') )";
					break;
				case 'referencia':
					$whereAdc = "(UPPER(fn_retira_especiais(tbl_produto.referencia)) LIKE UPPER('%'||fn_retira_especiais('{$valor}')||'%') OR UPPER(fn_retira_especiais(tbl_produto.descricao)) LIKE UPPER('%'||fn_retira_especiais('{$valor}')||'%')) ";
					break;
			}

		
			$joinAdc .= " LEFT JOIN tbl_produto_idioma ON tbl_produto_idioma.produto = tbl_produto.produto
							LEFT JOIN tbl_produto_pais ON tbl_produto_pais.produto = tbl_produto.produto ";

		

			$campos = " tbl_produto.* , tbl_linha.*, (SELECT tbl_familia.descricao FROM tbl_familia WHERE tbl_familia.familia = tbl_produto.familia AND tbl_familia.fabrica = {$login_fabrica}) AS descricao_familia ";
		
			$conds = "	AND tbl_linha.fabrica = {$login_fabrica}
						AND tbl_produto.fabrica_i = {$login_fabrica}";

		
			$sql = "
				SELECT
					{$campos}
				FROM tbl_produto
				JOIN tbl_linha ON tbl_produto.linha = tbl_linha.linha
				{$joinAdc}
				WHERE
				{$whereAdc}
				{$conds}
				{$order_by};
			";

            $res = pg_query($con, $sql);
			$rows = pg_num_rows($res);
			if ($rows > 0) {
			?>
			<div id="border_table">
				<table id="resultados" class="table table-striped table-bordered table-hover table-lupa" >
					<thead>
						<tr class='titulo_coluna'>
							<th><?= traduz('Referência');?></th>
							<th><?= traduz('Nome');?></th>
							<th><?= traduz('Voltagem');?></th>
							<th><?= traduz('Status');?></th>
						</tr>
					</thead>
					<tbody>
						<?php
						for ($i = 0 ; $i < $rows; $i++) {

							$numero_serie_obrigatorio = pg_fetch_result($res, $i, 'numero_serie_obrigatorio');
							$produto                  = pg_fetch_result($res, $i, 'produto');
							$linha                    = pg_fetch_result($res, $i, 'linha');
							$familia                  = pg_fetch_result($res, $i, 'familia');
							$nome_comercial           = pg_fetch_result($res, $i, 'nome_comercial');
							$voltagem                 = pg_fetch_result($res, $i, 'voltagem');
							$referencia               = pg_fetch_result($res, $i, 'referencia');
							$descricao                = pg_fetch_result($res, $i, 'descricao');
							$referencia_fabrica       = pg_fetch_result($res, $i, 'referencia_fabrica');
							$garantia                 = pg_fetch_result($res, $i, 'garantia');
							$ativo                    = pg_fetch_result($res, $i, 'ativo');
							$valor_troca              = pg_fetch_result($res, $i, 'valor_troca');
							$troca_garantia           = pg_fetch_result($res, $i, 'troca_garantia');
							$troca_faturada           = pg_fetch_result($res, $i, 'troca_faturada');
							$informatica              = pg_fetch_result($res, $i, 'informatica');
							$mobra                    = str_replace(".", ",", pg_fetch_result($res, $i, "mao_de_obra"));
							$off_line                 = pg_fetch_result($res, $i, "off_line");
							$capacidade               = pg_fetch_result($res, $i, 'capacidade');
							$ipi                      = pg_fetch_result($res, $i, "ipi");
							$troca_obrigatoria        = pg_fetch_result($res, $i, 'troca_obrigatoria');
							$tipo_produto             = pg_fetch_result($res, $i, 'fabrica_origem');
							$valores_adicionais       = pg_fetch_result($res, $i, 'valores_adicionais');
							$origem                   = pg_fetch_result($res, $i, 'origem');
							$indice                   = pg_fetch_result($res, $i, 'visivel');

							$descricao = str_replace('"', '', $descricao);
							$descricao = str_replace("'", "", $descricao);
							$descricao = str_replace("''", "", $descricao);

							$mativo = ($ativo == 't') ?  " ATIVO " : " INATIVO ";

							$r = array(
								"produto"                  => $produto,
								"descricao"                => utf8_encode($descricao),
								"voltagem"                 => utf8_encode($voltagem),
							);

							$r = array_map('utf8_encode',$r);

							echo "
								<tr class='produto-item' data-produto='".json_encode($r)."' $style_reparo>
									<td class='cursor_lupa'>{$referencia}</td>
									<td class='cursor_lupa'>{$descricao}</td>
									<td class='cursor_lupa'>{$voltagem}</td>
									<td class='cursor_lupa'>{$mativo}</td>
								</tr>
									";
								} //fechafor
						echo "
					</tbody>
				</table>
			</div>";
			}
		?>
		</div>
	</body>
</html>
