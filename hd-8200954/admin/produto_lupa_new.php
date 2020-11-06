<?php
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include "funcoes.php";

if (preg_match("/\/admin\//", $_SERVER["PHP_SELF"])) {		
	include 'autentica_admin.php';
	$areaAdmin = true;

	include_once '../class/aws/s3_config.php';

    include_once S3CLASS;

    $s3 = new AmazonTC("produto", $login_fabrica);

} elseif (preg_match("/\/admin_es\//", $_SERVER["PHP_SELF"])) {
	include "../fn_traducao.php";
	include 'autentica_admin.php';
	include_once '../class/aws/s3_config.php';

    include_once S3CLASS;

    $s3 = new AmazonTC("produto", $login_fabrica);

	$areaAdmin = true;
} else {
	include 'autentica_usuario.php';
	include_once 'class/aws/s3_config.php';

    include_once S3CLASS;

    $s3 = new AmazonTC("produto", $login_fabrica);

	$areaAdmin = false;
}


function retira_acentos( $texto ){
    $array1 = array("á", "à", "â", "ã", "ä", "é", "è", "ê", "ë", "í", "ì", "î", "ï", "ó", "ò", "ô", "õ", "ö", "ú", "ù", "û", "ü", "ç" , "Á", "À", "Â", "Ã", "Ä", "É", "È", "Ê", "Ë", "Í", "Ì", "Î", "Ï", "Ó", "Ò", "Ô", "Õ", "Ö", "Ú", "Ù", "Û", "Ü", "Ç","º","&","%","$","?","@", "'" );
    $array2 = array("a", "a", "a", "a", "a", "e", "e", "e", "e", "i", "i", "i", "i", "o", "o", "o", "o", "o", "u", "u", "u", "u", "c" , "A", "A", "A", "A", "A", "E", "E", "E", "E", "I", "I", "I", "I", "O", "O", "O", "O", "O", "U", "U", "U", "U", "C","_","_","_","_","_","_","" );
    return str_replace( $array1, $array2, $texto );
}

$contador_ver = "0";

if ($_REQUEST["posicao"]) {
	$posicao   = $_REQUEST["posicao"];
}

if ($_REQUEST["ativo"]) {
	$ativo   = $_REQUEST["ativo"];
}
if ($_REQUEST["acessorio"]) {
	$acessorio   = $_REQUEST["acessorio"];
}

if ($_REQUEST["entrega-tecnica"]) {
	$entrega_tecnica = $_REQUEST["entrega-tecnica"];
}

if ($_REQUEST["entrega"]) {
	$entrega = true ;
}

$parametro = $_REQUEST["parametro"];
$valor     = trim($_REQUEST["valor"]);

if($_REQUEST['tela_cadastro_os']){ //hd_chamado=2717074
	$cadastro_os = $_REQUEST["tela_cadastro_os"];
}

#Usado para filtrar somente produtos de linhas atendidas pelo posto
if ($_REQUEST["posto"]) {
	$posto   = $_REQUEST["posto"];
}

#Usado para retornar o subproduto do produto selecionado
if ($_REQUEST["subproduto"]) {
	$subproduto   = $_REQUEST["subproduto"];
}

if ($_REQUEST["valores-adicionais"]) {
	$valores_adicionais = $_REQUEST["valores-adicionais"];
}

if ($_REQUEST['produtoAcao']) {
	$produtoAcao = $_REQUEST['produtoAcao'];
}

#Usado na tela cadastro_rpi (midea)
if ($_REQUEST['codigo_validacao_serie']){
	$codigo_validacao_serie = $_REQUEST['codigo_validacao_serie'];
}

if ($_REQUEST["listaTroca"]) {
	$listaTroca = true;
}

if ($_REQUEST['retornaIndice']) {
	$retornaIndice = $_REQUEST['retornaIndice'];
}

if ($_REQUEST["mascara"]) {
	$mascara = $_REQUEST["mascara"];
}

if ($_REQUEST["produto-generico"]) {
	$produto_generico = $_REQUEST["produto-generico"];
}

if ($_REQUEST["grupo-atendimento"]) {
	$grupo_atendimento = $_REQUEST["grupo-atendimento"];
}

if ($_REQUEST["fora-garantia"]) {
	$fora_garantia = $_REQUEST["fora-garantia"];
}

if ($_REQUEST["km-google"]) {
	$km_google = $_REQUEST["km-google"];
}

if ($_REQUEST["marca"]) {
	$marca = $_REQUEST['marca'];
}

if($_REQUEST['familia']){
	$familia = $_REQUEST['familia'];
}

if(!empty($posto)) {
	$sql = "SELECT tbl_tipo_posto.posto_interno
			FROM tbl_posto_fabrica
			INNER JOIN tbl_tipo_posto ON tbl_tipo_posto.tipo_posto = tbl_posto_fabrica.tipo_posto AND tbl_tipo_posto.fabrica = {$login_fabrica}
			WHERE tbl_posto_fabrica.fabrica = {$login_fabrica}
			AND tbl_posto_fabrica.posto = {$posto}";
	$res = pg_query($con, $sql);
}
if (pg_num_rows($res) > 0) {
	$postoInterno =  (pg_fetch_result($res, 0, 0) == "t") ? true : false;
} else {
	$postoInterno = false;
}
?>

<!DOCTYPE html />
<html>
	<head>
		<meta http-equiv=pragma content=no-cache>
		<link href="bootstrap/css/bootstrap.css" type="text/css" rel="stylesheet" media="screen" />
    	<link href="bootstrap/css/extra.css" type="text/css" rel="stylesheet" media="screen" />
    	<link href="css/tc_css.css" type="text/css" rel="stylesheet" media="screen" />
    	<link href="bootstrap/css/ajuste.css" type="text/css" rel="stylesheet" media="screen" />
		<link href="plugins/dataTable.css" type="text/css" rel="stylesheet" />

		<script src="plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
		<script src="bootstrap/js/bootstrap.js"></script>
		<script src="plugins/dataTable.js"></script>
		<script src="plugins/resize.js"></script>
		<script src="plugins/shadowbox_lupa/lupa.js"></script>

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
				<img class="espaco" src="imagens/logo_new_telecontrol.png">
				<img class="lupa_img pull-right" src="imagens/lupa_new.png">
			</div>
			<br /><hr />
			<div class="row-fluid">
				<form action="<?=$_SERVER['PHP_SELF']?>" method='POST' >

				<div class="span1"></div>
					<div class="span4">
						<input type="hidden" name="posicao" value='<?=$posicao?>' />

						<?
							if(isset($produtoAcao)){
								echo "<input type='hidden' name='produtoAcao' value='$produtoAcao' />";
							}

							if(isset($ativo)){
								echo "<input type='hidden' name='ativo' value='$ativo' />";
							}

							if (isset($codigo_validacao_serie)){
								echo "<input type='hidden' name='codigo_validacao_serie' value='$codigo_validacao_serie' />";
							}

							if (isset($posto)) {
								echo "<input type='hidden' name='posto' value='$posto' />";
							}

							if (isset($subproduto)) {
								echo "<input type='hidden' name='subproduto' value='$subproduto' />";
							}

							if (isset($entrega_tecnica)) {
								echo "<input type='hidden' name='entrega-tecnica' value='$entrega_tecnica' />";
							}

							if (isset($entrega)) {
								echo "<input type='hidden' name='entrega' value='$entrega' />";
							}

							if (isset($valores_adicionais)) {
								echo "<input type='hidden' name='valores-adicionais' value='$valores_adicionais' />";
							}

							if (isset($retornaIndice)) {
								echo "<input type='hidden' name='retornaIndice' value='$retornaIndice' />";
							}

							if (isset($listaTroca)) {
								echo "<input type='hidden' name='listaTroca' value='$listaTroca' />";
							}

							if (isset($mascara)) {
								echo "<input type='hidden' name='mascara' value='$mascara' />";
							}

							if (isset($produto_generico)) {
								echo "<input type='hidden' name='produto-generico' value='$produto_generico' />";
							}

							if (isset($grupo_atendimento)) {
								echo "<input type='hidden' name='grupo-atendimento' value='$grupo_atendimento' />";
							}

							if (isset($fora_garantia)) {
								echo "<input type='hidden' name='fora-garantia' value='$fora_garantia' />";
							}

							if (isset($km_google)) {
								echo "<input type='hidden' name='km-google' value='$km_google' />";
							}
						?>
						<select name="parametro"  >
							<? if (in_array($login_fabrica, array(152,180,181,182))) { ?>
								<option value="referencia">Referência</option>
								<option value="descricao" SELECTED>Descrição</option>
							<? } else if (in_array($login_fabrica, array(52,158,161,165,169,170,175)) AND $parametro == "numero_serie") { ?>
								<option value="numero_serie"  <?=($parametro == "numero_serie")  ? "SELECTED" : ""?> ><?= traduz('Número de série') ?></option>
							<? } else { ?>
								<option value="referencia" <?=($parametro == "referencia") ? "SELECTED" : ""?> ><?= traduz('Referência') ?></option>
								<option value="descricao"  <?=($parametro == "descricao")  ? "SELECTED" : ""?> ><?= traduz('Descrição') ?></option>

								<? if (in_array($login_fabrica, [167, 203])) { ?>
									<option value="referencia_descricao" <?=($parametro == "referencia_descricao") ? "SELECTED" : ""?> ><?= traduz('Referência/Descrição') ?></option>
								<? } ?>

							<? } ?>
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
		$msg_confirma = "0";

		if($login_fabrica == 153 AND $cadastro_os == 't'){ //hd_chamado=2717074

			$sqlReparo = "SELECT parametros_adicionais FROM tbl_produto WHERE fabrica_i = $login_fabrica AND (referencia = '$valor' OR descricao = '$valor') ";
			$resReparo = pg_query($con, $sqlReparo);
			$reparo_na_fabrica = trim(pg_fetch_result($resReparo, 0, 'parametros_adicionais'));
			$param_adicionais = json_decode($reparo_na_fabrica,true);
            $reparo_na_fabrica = $param_adicionais['reparo_na_fabrica'];
            if($reparo_na_fabrica == 't'){
            ?>
            	<div class="row-fluid">
            		<div class="span1"></div>
        			<div class="span10">
	            		<div class="alert alert-error">
							<h4>Posto de assistência não tem autorização para realizar reparo ou <br/> descaracterizar
							(trocar peça, ressoldar e outros) este produto.<br/> Cliente deverá ser orientado a ligar para 0800-775-1400.</h4>
					    </div>
					</div>
				    <div class="span1"></div>
			    </div>
            <?
            	exit;
            }
		}

		if ($login_fabrica == 30 && strlen($valor) >= 3) {
			switch ($parametro) {
				case 'referencia':
					$valor = str_replace(array(".", ",", "-", "/"), "", $valor);
					$whereAdc = "UPPER(tbl_produto.referencia_pesquisa) LIKE UPPER('%{$valor}%')";
					break;

				case 'descricao':
					$whereAdc = "(UPPER(tbl_produto.descricao) LIKE UPPER('%{$valor}%') OR UPPER(tbl_produto.nome_comercial) LIKE UPPER('%{$valor}%') )";
					break;
			}


			if (isset($whereAdc)) {
				$sql = "
					SELECT
						CASE WHEN tbl_produto.marca = 164 THEN 't' ELSE 'f' END AS itatiaia
					FROM tbl_produto
					JOIN tbl_linha ON tbl_produto.linha = tbl_linha.linha
					WHERE
					{$whereAdc}
					AND tbl_linha.fabrica = {$login_fabrica};
				";
				$res = pg_query($con, $sql);

				if (pg_num_rows($res) > 0) {
					$itatiaia = pg_fetch_result($res, 0, 'itatiaia');

					if ($itatiaia == 't') {
						$contador_ver ="1";

						echo "<script>";
							echo "alert('Este produto é ITATIAIA não pode ser aberto Ordem de Serviço pelo Posto, somente o CALLCENTER poderá abrir. Favor entrar em contato com o CALLCENTER!');";
							echo "window.parent.Shadowbox.close();";
						echo "</script>";
					}
				}
			}
		}

		if ((in_array($login_fabrica, array(152,180,181,182))) || ($login_fabrica == 165 && isset($listaTroca)) || strlen($valor) >= 3) {
			switch ($parametro) {

				case 'referencia':

					if (in_array($login_fabrica, array(169,170))) {
						$valor_real = $valor;
						$valor = str_replace("-", "YY", $valor);
					} else {
						$valor = str_replace(array(".", ",", "-", "/", " "), "", $valor);
					}

					if ($login_fabrica == 20) {
						$whereAdc = "(UPPER(tbl_produto.referencia_pesquisa) LIKE UPPER('%{$valor}%') OR UPPER(tbl_produto.referencia_fabrica) LIKE UPPER('%{$valor}%') OR UPPER(tbl_produto.referencia) LIKE UPPER('%{$valor}%'))";
					} else if (in_array($login_fabrica, array(169,170))) {
						$whereAdc = "((UPPER(REPLACE(tbl_produto.referencia_pesquisa, '-', 'YY')) LIKE UPPER('%{$valor}%') OR UPPER(REPLACE(tbl_produto.referencia_fabrica, '-', 'YY')) LIKE UPPER('%{$valor}%') OR UPPER(REPLACE(tbl_produto.referencia, '-', 'YY')) LIKE UPPER('%{$valor}%')) OR (UPPER(tbl_produto.referencia_pesquisa) LIKE UPPER('%{$valor_real}%') OR UPPER(tbl_produto.referencia_fabrica) LIKE UPPER('%{$valor_real}%') OR UPPER(tbl_produto.referencia) LIKE UPPER('%{$valor_real}%')))";
					} elseif ($login_fabrica == 171) {
						$whereAdc = "( UPPER(fn_retira_especiais(tbl_produto.referencia_pesquisa)) LIKE UPPER('%'||fn_retira_especiais('{$valor}')||'%') OR UPPER(fn_retira_especiais(tbl_produto.referencia_fabrica)) LIKE UPPER('%'||fn_retira_especiais('{$valor}')||'%') OR tbl_produto.referencia ~*'{$valor}')";
					} else {
						$whereAdc = "(UPPER(fn_retira_especiais(tbl_produto.referencia_pesquisa)) LIKE UPPER('%'||fn_retira_especiais('{$valor}')||'%') or tbl_produto.referencia ~*'{$valor}')";
					}
					break;

				case 'descricao':
					if ($login_fabrica != 20) {
                        $whereAdc = "( UPPER(fn_retira_especiais(tbl_produto.descricao)) LIKE UPPER('%' || fn_retira_especiais('$valor') || '%') OR
                                       UPPER(fn_retira_especiais(tbl_produto.nome_comercial)) LIKE UPPER('%' || fn_retira_especiais('{$valor}') || '%') OR (UPPER(fn_retira_especiais(tbl_produto_idioma.descricao)) LIKE UPPER('%' || fn_retira_especiais('{$valor}') || '%') AND tbl_produto_idioma.idioma = '{$sistema_lingua}') )";
					} else {
						$whereAdc = "( UPPER(fn_retira_especiais(tbl_produto.descricao)) LIKE UPPER('%{$valor}%') OR UPPER(fn_retira_especiais(tbl_produto.nome_comercial)) LIKE UPPER('%{$valor}%') )";
					}
					break;
				case 'numero_serie': //hd_chamado=2891049
						$valor = strtoupper($valor);
						if (in_array($login_fabrica, array(169,170))) {
							$whereAdc = " (tbl_numero_serie.serie = UPPER('{$valor}') OR tbl_numero_serie.serie = UPPER('S{$valor}')) AND tbl_numero_serie.fabrica = {$login_fabrica} ";
						} else {
							$whereAdc = " tbl_numero_serie.serie = '{$valor}' AND tbl_numero_serie.fabrica = $login_fabrica ";
						}
						$joinAdc .= " JOIN tbl_numero_serie ON tbl_produto.produto = tbl_numero_serie.produto ";
						if($login_fabrica == 161){
							$joinAdc .= " JOIN tbl_revenda ON tbl_numero_serie.cnpj = tbl_revenda.cnpj ";
							$campos_extra =",
									tbl_revenda.nome AS nome_revenda,
									tbl_revenda.cnpj AS cnpj_revenda,
									tbl_numero_serie.serie AS serie_produto";
						}
					break;
				case 'referencia_descricao':
						$whereAdc = "(UPPER(fn_retira_especiais(tbl_produto.referencia)) LIKE UPPER('%'||fn_retira_especiais('{$valor}')||'%') OR UPPER(fn_retira_especiais(tbl_produto.descricao)) LIKE UPPER('%'||fn_retira_especiais('{$valor}')||'%')) ";
					break;
			}

			if (in_array($login_fabrica, array(14,169,170))) {
				$joinAdc .= " LEFT JOIN tbl_familia ON tbl_familia.familia = tbl_produto.familia ";
			}

			if ($login_fabrica != 20) {
				$joinAdc .= " LEFT JOIN tbl_produto_idioma ON tbl_produto_idioma.produto = tbl_produto.produto
							LEFT JOIN tbl_produto_pais ON tbl_produto_pais.produto = tbl_produto.produto ";
			}

			if (in_array($login_fabrica, array(14,66))) {
				$whereAdc .= " AND tbl_produto.abre_os IS TRUE ";
			}

			if ($login_fabrica == 14 && $login_pais == 'BR') {
				$whereAdc .= " AND UPPER(tbl_produto.origem) <> 'IMP' AND UPPER(tbl_produto.origem) <> 'USA' AND UPPER(tbl_produto.origem) <> 'ASI' ";
			}

			if ($login_fabrica != 165 AND $listaTroca) {
				if($areaAdmin) {
					$cond_ativo = " or uso_interno_ativo "; 
				}
				$whereAdc .=" AND tbl_produto.lista_troca IS TRUE AND ( tbl_produto.ativo IS TRUE $cond_ativo ) ";

				if ($usaProdutoGenerico) {
					$whereAdc .= " AND tbl_produto.produto_principal IS TRUE ";
				}
			}

			if ($produto_generico) {
				$whereAdc .= " AND tbl_produto.produto_principal IS TRUE ";
			}

			if (in_array($login_fabrica, [186]) && !$areaAdmin && !$postoInterno) {

				$whereAdc .= " AND tbl_produto.ativo IS TRUE ";
				
			}

			if (($ativo or !$areaAdmin) && !in_array($login_fabrica, [186])) {
				$whereAdc .= " AND tbl_produto.ativo IS TRUE ";
			}

			if ($entrega) {
				$whereAdc .= " AND tbl_produto.entrega_tecnica IS TRUE ";
			}

			// if (isset($posto) && !(in_array($login_fabrica, array(169, 170)) && isset($posto) && $parametro == "numero_serie")) {
			// 	$joinAdc .= " JOIN tbl_posto_linha ON tbl_posto_linha.linha = tbl_linha.linha AND tbl_posto_linha.posto = {$posto} ";
			// }

			if (isset($posto) && !in_array($login_fabrica, array(20, 169, 170))) {
				$joinAdc .= " JOIN tbl_posto_linha ON tbl_posto_linha.linha = tbl_linha.linha AND tbl_posto_linha.posto = {$posto} ";
			}

			if (!$areaAdmin && in_array($login_fabrica, [167,203]) && !isset($posto)) {
				$joinAdc .= " JOIN tbl_posto_linha ON tbl_posto_linha.linha = tbl_linha.linha AND tbl_posto_linha.posto = {$login_posto} ";
			}

			if($acessorio == true || $acessorio == "t"){
				$whereAdc .=" AND descricao ilike UPPER('%acessorio%')";
			}

			if (in_array($login_fabrica,array(158,165,169,170)) && $parametro == "numero_serie") {
				$campos = "
					tbl_produto.*,
					tbl_linha.*,
					tbl_numero_serie.serie AS serie_produto,
					TO_CHAR(tbl_numero_serie.data_venda, 'DD/MM/YYYY') AS data_venda,
					TO_CHAR(tbl_numero_serie.data_fabricacao, 'DD/MM/YYYY') AS data_fabricacao
				";
			} else if ($login_fabrica == 161) {
				$campos = "
					tbl_produto.* $campos_extra\n";
			} else {
				$campos = " tbl_produto.* , tbl_linha.*, (SELECT replace(tbl_familia.descricao,E'\'','') FROM tbl_familia WHERE tbl_familia.familia = tbl_produto.familia AND tbl_familia.fabrica = {$login_fabrica}) AS descricao_familia ";
				
				if (in_array($login_fabrica, [11,172])) {
					$campos = " tbl_produto.* , tbl_linha.*, (SELECT tbl_familia.descricao FROM tbl_familia WHERE tbl_familia.familia = tbl_produto.familia AND tbl_familia.fabrica in (11,172)) AS descricao_familia ";
				}
			}

			if ($login_fabrica == 190) {
				$campos .= ", JSON_FIELD('limite_horas_trabalhadas',tbl_familia.bosch_cfa) AS limite_horas_trabalhadas";
				$joinAdc .= " LEFT JOIN tbl_familia ON tbl_familia.familia = tbl_produto.familia ";

			}


			if ($login_fabrica == 165 && empty($listaTroca) && $retornaIndice) {
				$whereBaseTroca = "AND COALESCE(tbl_produto.valor_troca,0) > 0";
			} else {
				$whereBaseTroca = "";
			}

			if ($mascara) {
				$campos .= ", (SELECT ARRAY_TO_STRING(ARRAY(SELECT mascara FROM tbl_produto_valida_serie WHERE fabrica = {$login_fabrica} AND produto = tbl_produto.produto), ';')) AS mascaras";
			}

			if($login_fabrica == 178){
				if ($listaTroca && $marca && $familia){
					$whereAdc .= " 	AND tbl_produto.familia = {$familia}
						AND tbl_produto.parametros_adicionais::jsonb->'marcas' ? '{$marca}'";
				}
				$campos .= ", JSON_FIELD('marca',parametros_adicionais) AS marcas_produto";
			}

			if (in_array($login_fabrica, [3,169,170]) && !empty($familia)) {
				$whereAdc .= "AND tbl_produto.familia = {$familia}";
			}

			if (in_array($login_fabrica, array(169,170))) {
				if ($cadastro_os == 't'){
					if ($fora_garantia != 't' && empty($grupo_atendimento)) {
						$whereAdc .= " AND tbl_linha.deslocamento IS NOT TRUE";
					} else if ($fora_garantia != 't' && $grupo_atendimento == 'P' && $km_google == 't') {
						$whereAdc .= " AND tbl_linha.deslocamento IS TRUE";
					} else if ($fora_garantia != 't' && $grupo_atendimento == "I") {
						$whereAdc .= " AND tbl_familia.setor_atividade IN ('I', 'C', 'IC')";
						$campos .= ", tbl_familia.setor_atividade";
					}
				}

				if ($codigo_validacao_serie == "true"){
					$whereAdc .= " AND tbl_familia.codigo_validacao_serie = 'true'";
				}
			}

			if ($login_fabrica == 176)
			{
				$campos  .= " , tbl_marca.visivel ";
				$joinAdc .= " LEFT JOIN tbl_marca ON tbl_marca.marca = tbl_produto.marca ";
			}

			if ($login_fabrica == 175 AND $parametro == "numero_serie"){
				$campos .= ", tbl_numero_serie.serie AS serie_produto, tbl_numero_serie.data_venda AS serie_data_venda";
			}

			if (in_array($login_fabrica, [193])) {
				$campos .= ", tbl_produto.garantia_horas AS hora_tecnica
							, JSON_FIELD('lancamento',parametros_adicionais) AS lancamento";
			}

			$conds = "	AND tbl_linha.fabrica = {$login_fabrica}
						AND tbl_produto.fabrica_i = {$login_fabrica}";

			if (in_array($login_fabrica, [11,172])) {
				$conds = "	AND tbl_linha.fabrica in (11,172)
							AND tbl_produto.fabrica_i in (11,172)";
				$order_by = " ORDER BY tbl_produto.referencia desc";
			}

			$sql = "
				SELECT
					{$campos}
				FROM tbl_produto
				JOIN tbl_linha ON tbl_produto.linha = tbl_linha.linha
				{$joinAdc}
				WHERE
				{$whereAdc}
				{$conds}
				{$whereBaseTroca}
				{$order_by};
			";
			if ($login_fabrica == 165 && $listaTroca == true && $retornaIndice) {

                /*
                 * - Aparece as opções
                 * de produtos cadastrados
                 * para troca
                 */
                $sql = "
                    SELECT
                    	{$campos}
                    FROM tbl_produto_troca_opcao
                    JOIN tbl_os_produto USING(produto)
                    JOIN tbl_produto ON tbl_produto.produto = tbl_produto_troca_opcao.produto_opcao
                    JOIN tbl_linha ON tbl_produto.linha = tbl_linha.linha
                    {$joinAdc}
                    WHERE
                    {$whereAdc}
                    AND tbl_produto.fabrica_i = {$login_fabrica}
                    AND tbl_os_produto.os_produto = {$retornaIndice}
                    ORDER BY tbl_produto_troca_opcao.kit;
                ";
            }
            $res = pg_query($con, $sql);
			$rows = pg_num_rows($res);
			if ($rows > 0) {
				if (in_array($login_fabrica, array(169, 170)) && isset($posto) && ($parametro == "numero_serie" OR $parametro == "referencia" OR $referencia == "descricao") ) {
					$linha_produto = pg_fetch_result($res, 0, 'linha');
					$sqlPostoLinha = "
						SELECT linha FROM tbl_posto_linha WHERE posto = {$posto} AND linha = {$linha_produto}
					";
					$resPostoLinha = pg_query($con, $sqlPostoLinha);

					if (!pg_num_rows($resPostoLinha)) {
						echo '
							<div class="alert alert_shadobox"><h4>'.traduz('Posto não atende a linha do produto selecionado').'</h4></div>
							</div></div></body></html>
						';
						exit;
					}
				}
			?>
			<div id="border_table">
				<table id="resultados" class="table table-striped table-bordered table-hover table-lupa" >
					<thead>
						<? if($login_fabrica == 161 && $parametro == "numero_serie") { ?>
							<tr class='titulo_coluna'>
								<th><?= traduz('Série') ?></th>
								<th><?= traduz('Ref. Produto') ?></th>
								<th><?= traduz('Desc. Produto') ?></th>
								<th><?= traduz('CNPJ Revenda') ?></th>
								<th><?= traduz('Nome Revenda') ?></th>
							</tr>
						<? } else { ?>
							<tr class='titulo_coluna'>
								<?php if (in_array($login_fabrica, array(195))) { ?>
									<th><?= traduz('Foto') ?></th>
								<?php } ?>
								<?php if (in_array($login_fabrica, array(171))) { ?>
									<th><?= traduz('Referência Fábrica') ?></th>
								<?php } ?>
								<?php if (in_array($login_fabrica, [11,172])) { ?>
									<th><?= traduz('Fábrica') ?></th>
								<?php } ?>
								<? if (in_array($login_fabrica, array(152,180,181,182))) { ?>
									<th><?= traduz('Nome') ?></th>
									<th><?= traduz('Código') ?></th>
								<? } else { ?>
									<th><?= (in_array($login_fabrica, array(169,170))) ? "Referência" : "Código"; ?></th>
									<th><?= traduz('Nome') ?></th>
								<? }
								if (in_array($login_fabrica, array(169,170)) && $parametro == "numero_serie") { ?>
									<th><?= traduz('Data Fabricação') ?></th>
									<!--<th>Data Venda</th>-->
								<? } ?>
								<th><?= traduz('Voltagem') ?></th>
								<th>Status</th>
							</tr>
						<? } ?>
					</thead>
					<tbody>
						<? for ($i = 0 ; $i < $rows; $i++) {

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
							$limite_horas_trabalhadas = pg_fetch_result($res, $i, 'limite_horas_trabalhadas');

							if ($mascara) {
								$mascaras = pg_fetch_result($res, $i, "mascaras");
							}

							if ($login_fabrica == 178){
								$marcas_produto = pg_fetch_result($res, $i, "marcas_produto");
							}

							if($login_fabrica == 35){
								$produto_critico = pg_fetch_result($res, 0, produto_critico);
							}

							if(in_array($login_fabrica, [167, 203])){ //HD-3428328
								$parametros_adicionais = pg_fetch_result($res, $i, 'parametros_adicionais');
								$parametros_adicionais = json_decode($parametros_adicionais,true);
								$suprimento            = $parametros_adicionais["suprimento"];
								$linha_nome            = pg_fetch_result($res, $i, "nome");
								$descricao_familia     = pg_fetch_result($res, $i, "descricao_familia");
							}

							if ($login_fabrica == 177){
								$parametros_adicionais = pg_fetch_result($res, $i, 'parametros_adicionais');
								$parametros_adicionais = json_decode($parametros_adicionais,true);
								$lote            	   = $parametros_adicionais["lote"];
							}

							if (in_array($login_fabrica, [193])) {
								$hora_tecnica = pg_fetch_result($res, $i, 'hora_tecnica');
								$lancamento   = pg_fetch_result($res, $i, 'lancamento');
							}

							if($login_fabrica == 161 && $parametro == "numero_serie"){
								$nome_revenda       = pg_fetch_result($res, $i, 'nome_revenda');
								$cnpj_revenda       = pg_fetch_result($res, $i, 'cnpj_revenda');
								$serie_produto      = pg_fetch_result($res, $i, 'serie_produto');
							}

							if (in_array($login_fabrica,array(158,165,169,170)) && $parametro == "numero_serie") {
								$data_venda      = pg_fetch_result($res, $i, "data_venda");
								$data_fabricacao = pg_fetch_result($res, $i, "data_fabricacao");
								if (in_array($login_fabrica, array(165,169,170))) {
									$serie_produto = pg_fetch_result($res, $i, 'serie_produto');
								}
							}

							if ($login_fabrica == 175 AND $parametro == "numero_serie"){
								$serie_produto = pg_fetch_result($res, $i, 'serie_produto');
								$serie_data_venda = pg_fetch_result($res, $i, 'serie_data_venda');
							}

							if (in_array($login_fabrica, array(169,170))) {
								$referencia = str_replace("YY", "-", $referencia);
								$setor_atividade = pg_fetch_result($res, $i, 'setor_atividade');
							}

							$sql_idioma = "SELECT descricao FROM tbl_produto_idioma WHERE produto = $produto AND upper(idioma) = '$sistema_lingua'";
							$res_idioma = pg_query($con, $sql_idioma);

							if(in_array($login_fabrica,array(152,180,181,182))){
								$tipo_entrega = pg_fetch_result($res, $i, 'code_convention');
							}

							if(in_array($login_fabrica,array(35,157,169,170))){
								$deslocamento_km = pg_fetch_result($res, $i, 'deslocamento');
							}

							if (pg_num_rows($res_idioma) > 0) {
								$descricao = pg_fetch_result($res_idioma, 0, 'descricao');
							}

							$descricao = str_replace('"', '', $descricao);
							$descricao = str_replace("'", "", $descricao);
							$descricao = str_replace("''", "", $descricao);
							$descricao = retira_acentos($descricao);

							$mativo = ($ativo == 't') ?  " ATIVO " : " INATIVO ";

							if ($login_fabrica != 165 && (strlen($ipi) > 0 && $ipi != "0")) {
								$valor_troca = $valor_troca * (1 + ($ipi /100));
							}

							$produto_pode_trocar = 1;

							if ($troca_produto == 't' || $revenda_troca == 't') {
								if ($troca_faturada != 't' && $troca_garantia != 't') {
									$produto_pode_trocar = 0;
								}
							}

							$produto_so_troca = 1;

							if ($troca_obrigatoria_consumidor == 't' || $troca_obrigatoria_revenda == 't') {
								if ($troca_obrigatoria == 't') {
									$produto_so_troca = 0;
								}
							}

							if (in_array($login_fabrica, [11,172])) {
								$fabrica_codigo = pg_fetch_result($res, $i, 'fabrica');
								$fabrica_nome = ($fabrica_codigo == 11) ? "AULIK" : "PACIFIC";
							}

							$mascaras_versoes = array();

							if($login_fabrica == 151){

								$sql_mascara = "SELECT mascara, posicao_versao FROM tbl_produto_valida_serie WHERE fabrica = {$login_fabrica} AND produto = {$produto}";
								$res_mascara = pg_query($con, $sql_mascara);

								if(pg_num_rows($res_mascara) > 0){
									while ($mascara = pg_fetch_object($res_mascara)) {
										$mascaras_versoes[] = array("mascara" => $mascara->mascara, "versao" => $mascara->posicao_versao);
									}
								}
							}

							if (!empty($familia)) {

								$sqlDadosFamilia = "SELECT TRIM(codigo_familia) AS codigo_familia,
														   replace(descricao,E'\'','') as descricao_familia
													FROM tbl_familia
													WHERE familia = {$familia}
													AND fabrica = {$login_fabrica}";
								$resDadosFamilia = pg_query($con, $sqlDadosFamilia);

								$codigo_familia    = pg_fetch_result($resDadosFamilia, 0, 'codigo_familia');
								$descricao_familia = pg_fetch_result($resDadosFamilia, 0, 'descricao_familia');

							}

							if (!empty($linha)) {
								$sqlDadosLinha = "SELECT TRIM(codigo_linha) AS codigo_linha
													FROM tbl_linha
													WHERE linha = {$linha}
													AND fabrica = {$login_fabrica}";
								$resDadosLinha = pg_query($con, $sqlDadosLinha);

								$codigo_linha = pg_fetch_result($resDadosLinha, 0, 'codigo_linha');
							}


							$r = array(
								"produto"                  => $produto,
								"descricao"                => utf8_encode($descricao),
								"referencia_fabrica"       => $referencia_fabrica,
								"referencia"               => $referencia,
								"voltagem"                 => utf8_encode($voltagem),
								"tipo_produto"             => $tipo_produto,
								"numero_serie_obrigatorio" => $numero_serie_obrigatorio,
								"troca_obrigatoria"        => ($troca_obrigatoria == "t") ? true : false,
								"valores_adicionais"       => (!empty($valores_adicionais)) ? "t" : "f",
								"mascaras_versoes"         => $mascaras_versoes,
								"linha"                    => $linha,
								"familia"                  => $familia,
								"origem"                   => $origem,
								"marca_indice"             => $indice,
								"limite_horas_trabalhadas" => $limite_horas_trabalhadas,
								"codigo_familia" 		   => utf8_encode($codigo_familia),
								"codigo_linha" 			   => utf8_encode($codigo_linha),
								"descricao_familia"        => utf8_encode($descricao_familia)
							);

							if ($mascara) {
								$r["mascaras"] = $mascaras;
							}

							if (in_array($login_fabrica, [193])) {
								$r["hora_tecnica"] = $hora_tecnica;
								$r["lancamento"]   = $lancamento;
							}

							if ($login_fabrica == 178 AND !empty($marcas_produto)){
								$fora_linha = json_decode($marcas_produto, true);
								$fora_linha = $fora_linha["fora_linha"];
								$r["marcas_produto"] = $marcas_produto;
								$r["fora_linha"] = $fora_linha;
							}

							if($login_fabrica == 35 and !empty($produto_critico)){
								$r['produto_critico'] = $produto_critico;
							}

							if(in_array($login_fabrica,array(152,180,181,182))){
								$r['entrega_tecnica'] = $tipo_entrega;
							}

							if(in_array($login_fabrica, array(167,203))){ //HD-3428328
								$r['suprimento']        = $suprimento;
								$r['linha_nome']        = $linha_nome;
								$r['descricao_familia'] = $descricao_familia;
							}

							if ($login_fabrica == 177){
								$r['lote'] = $lote;
							}

							if ($login_fabrica == 175){
								$r['capacidade']        = $capacidade;
							}

							if($login_fabrica == 162){
								$r['informatica'] = $informatica;
							}

							if($login_fabrica == 165){
								$r['valor_troca'] = $valor_troca;
							}

							if (in_array($login_fabrica,array(158,165,169,170)) && $parametro == "numero_serie") {
								$r["data_venda"]      = $data_venda;
								$r["data_fabricacao"] = $data_fabricacao;
								if (in_array($login_fabrica, array(165,169,170))) {
									$r['serie_produto'] = $serie_produto;
								}
							}

							if ($login_fabrica == 175 AND $parametro == "numero_serie"){
								$r['serie_produto'] = $serie_produto;
								$r['serie_data_venda'] = $serie_data_venda;
							}
							
							if ($login_fabrica == 175) {
								$r['garantia'] = $garantia;
							}

							if($login_fabrica == 153 AND $cadastro_os == 't'){ //hd_chamado=2717074
								$sqlReparo = "SELECT parametros_adicionais FROM tbl_produto WHERE fabrica_i = $login_fabrica AND (referencia = '$referencia' OR descricao = '$descricao') ";
								$resReparo = pg_query($con, $sqlReparo);
								$reparo_na_fabrica = trim(pg_fetch_result($resReparo, 0, 'parametros_adicionais'));
								$param_adicionais = json_decode($reparo_na_fabrica,true);
					            $reparo_na_fabrica = $param_adicionais['reparo_na_fabrica'];
					            $style_reparo = "";
					            if($reparo_na_fabrica == 't'){
					            	$r['reparo_na_fabrica'] = $reparo_na_fabrica;
					            	$style_reparo = "style='color:red;'";
					            }
					        }

							if ($retornaIndice) {
								$r['retornaIndice'] = $retornaIndice;
							}

							if (isset($entrega_tecnica)) {
								$r['entrega_tecnica'] = pg_fetch_result($res, $i, 'entrega_tecnica');

								if ($r['entrega_tecnica'] == 't') {
									$valores_adicionais = json_decode(pg_fetch_result($res, $i, 'valores_adicionais'));

									if ($valores_adicionais->deslocamento_km == 't') {
										$r['deslocamento_km'] = 't';
									} else {
										$r['deslocamento_km'] = 'f';
									}
								}
							}
							if (in_array($login_fabrica, array(35,157,169,170))) {
								$r['deslocamento_km'] = $deslocamento_km;
							}

							if (isset($valores_adicionais)) {
								$r['valores_adicionais'] = $valores_adicionais;
							}

							if(isset($produtoAcao)){
								$r['produtoAcao'] = $produtoAcao;
							}

							if (strlen($posicao) > 0) {
								$r['posicao'] = $posicao;
							}

							if (strlen($setor_atividade) > 0) {
								$r['setor_atividade'] = $setor_atividade;
							}

							if (isset($subproduto)) {
								$sql_subproduto = "SELECT * FROM tbl_subproduto WHERE produto_pai = {$produto} OR produto_filho = {$produto}";
								$res_subproduto = pg_query($con, $sql_subproduto);
								$r['subproduto'] = (pg_num_rows($res_subproduto) > 0);
							}

							if ($login_fabrica == 52) { /*HD - 6201206*/
								$marca = pg_fetch_result($res, $i, 'marca');
								
								if (strlen($marca) == 0) {
									$r["marca"] = "";
								} else {
									$r["marca"] = $marca;
								}
							}

							$r = array_map('utf8_encode',$r);

							if ($login_fabrica == 161) {

								$r['cnpj_revenda'] = ($parametro == "numero_serie") ? $cnpj_revenda : "";

								echo "
								<tr class='produto-item' data-produto='".json_encode($r)."'>
									<td class='cursor_lupa'>$serie_produto</td>
									<td class='cursor_lupa'>$referencia</td>
									<td class='cursor_lupa'>$descricao</td>
									".(
										($parametro == "numero_serie") ? "<td class='cursor_lupa'>$nome_revenda</td><td class='cursor_lupa'>$cnpj_revenda</td>" : "<td></td>"
									)."
								</tr>
								";

							} else {
								echo "
								<tr class='produto-item' data-produto='".json_encode($r)."' $style_reparo>";
									if (in_array($login_fabrica, array(195))) {
										    $imagem_produto = $s3->getObjectList($produto);
										    $imagem_produto = basename($imagem_produto[0]);
										    $imagem_produto = $s3->getLink($imagem_produto);

										$caminhoNotFound = (preg_match("/\/admin\//", $_SERVER["PHP_SELF"])) ? "../imagens/not_image.png" : "imagens/not_image.png";

										$tag_imagem.= "<img src='$imagem_produto' valign='middle' alt='$referencia' style='width:70px;border: 2px solid #FFCC00' class='thickbox' onerror=\"this.onerror=null;this.src='$caminhoNotFound';\" />\n";



										echo "<td width='10%' class='cursor_lupa'>{$tag_imagem}</td>";
									}
									if (in_array($login_fabrica, array(171))) {
										echo "<td class='cursor_lupa'>{$referencia_fabrica}</td>";
									}
									if (in_array($login_fabrica, [11,172])) {
										echo "<td class='cursor_lupa'>{$fabrica_nome}</td>";
									}
									if (in_array($login_fabrica, array(152,180,181,182))) {
										echo "
											<td class='cursor_lupa'>{$descricao}</td>
										 	<td class='cursor_lupa'>{$referencia}</td>
									 	";
									} else {
										echo "
											<td class='cursor_lupa'>{$referencia}</td>
										 	<td class='cursor_lupa'>{$descricao}</td>
									 	";
									}
									if (in_array($login_fabrica, array(169,170)) && $parametro == "numero_serie") {
										echo "
											<td class='cursor_lupa'>{$data_fabricacao}</td>
										";
										//<td class='cursor_lupa'>{$data_venda}</td>
									}

									echo "
										<td class='cursor_lupa'>{$voltagem}</td>
										<td class='cursor_lupa'>{$mativo}</td>
										</tr>
									";

							}
						}
						echo "
					</tbody>";
						echo "
				</table>";

			} else {
				if ($login_fabrica == 165 && $parametro == "numero_serie" && !$areaAdmin && !$postoInterno) {
					echo '<div class="alert alert-danger alert_shadobox"><h3><b>'.traduz('PRODUTO FORA DE GARANTIA').'</b></h3>
					 <h5 style="color:#000">'.traduz('Favor anexar a NF').'</h5></div>';
				} else {

					$rowsProd = 0;

					if ($login_fabrica == 35 and !$areaAdmin) {
						$sqlProd = "SELECT * FROM tbl_produto LEFT JOIN tbl_produto_idioma ON tbl_produto_idioma.produto = tbl_produto.produto WHERE fabrica_i = $login_fabrica AND $whereAdc";
						$qryProd = pg_query($con, $sqlProd);

						$rowsProd = pg_num_rows($qryProd);
					}

					if ($rowsProd > 0) {
						echo '<div class="alert alert_shadobox"><h4>'.traduz('Linha de Produto não atendida por seu PA, por gentileza entrar em contato com o consultor de sua região através do Help Desk').'</h4></div>';
					} else {
						echo '<div class="alert alert_shadobox"><h4>'.traduz('Nenhum resultado encontrado').'</h4></div>';
					}
				}
			}
		} else {
			echo '<div class="alert alert_shadobox"><h4>'.traduz('Informar toda ou parte da informação para pesquisar!').'</h4></div>';
		}
		?>
			</div>
		</div>
	</body>
</html>
