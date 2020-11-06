

<?php
include '../../dbconfig.php';
include '../../includes/dbconnect-inc.php';
include '../../helpdesk/mlg_funciones.php';

$fabrica = 24;

$sql = "SELECT DISTINCT tbl_familia.familia, tbl_familia.descricao, tbl_produto.linha
			FROM tbl_produto
			JOIN tbl_linha USING(linha)
			JOIN tbl_familia ON tbl_familia.familia = tbl_produto.familia
		WHERE tbl_produto.ativo IS TRUE
		  AND tbl_linha.fabrica = $fabrica
		  AND tbl_produto.familia IS NOT NULL
		ORDER BY tbl_familia.descricao";
$res = pg_query($con, $sql);
$num_fams = pg_num_rows($res);
$linhas	  = array();
$familias = array();

for ($i=0; $i < $num_fams; $i++) {
	list($familia, $nome_familia, $linha) = pg_fetch_row($res, $i);
    $familias[$familia] = $nome_familia;
	if (!in_array($linha, $linhas)) $linhas[] = $linha;
	$linha_familia[$familia] = $linha;
}

//	AJAX
if ($_GET['action']=='cidades') {
	$estado = $_GET['estado'];
	$familia= $_GET['familia'];
	if ($estado == "") exit("<option SELECTED>Sem resultados</option>");

	if(strlen($estado) > 0) {
		$linha  = $linha_familia[$familia];
		$tot_i = false;
		$debug = $_REQUEST['debug'];
		$sql_cidades =	"SELECT  LOWER(mlg_cidade)||'#('||count(mlg_cidade)||')' AS cidade
							FROM (SELECT tbl_posto_fabrica.posto,tipo_posto,UPPER(TRIM(TRANSLATE(contato_cidade,'·‚‡„‰ÈÍËÎÌÓÏÔÛÙÚı˙˘¸Á¡¬¿√ƒ… »ÀÕŒÃœ”‘“’⁄Ÿ‹«',	'aaaaaeeeeiiiIIIIOOOOUUUC')))
														AS mlg_cidade,
										contato_estado	AS mlg_estado
							FROM tbl_posto_fabrica
							JOIN tbl_posto_linha ON tbl_posto_linha.posto = tbl_posto_fabrica.posto AND tbl_posto_linha.linha = $linha
								WHERE credenciamento<>'DESCREDENCIADO'
									AND tbl_posto_fabrica.posto NOT IN(6359,20462)
									AND tbl_posto_fabrica.credenciamento <> 'DESCREDENCIADO'
									AND tbl_posto_fabrica.tipo_posto <> 163
									AND tbl_posto_fabrica.divulgar_consumidor IS TRUE
									AND contato_estado='$estado' AND fabrica=$fabrica) mlg_posto ";
			
			if ($login_fabrica==24){
					$sql_cidades .= " AND tbl_posto_linha.divulgar_consumidor IS TRUE ";
				
			}
		$sql_cidades .="GROUP BY mlg_posto.mlg_cidade ORDER BY cidade ASC";
		$res_cidades = pg_query($con,$sql_cidades);

		$tot_i  = pg_num_rows($res_cidades);
		if ($debug) pre_echo($sql_cidades, "Resultado: $tot_i registro(s)");
		if ($tot_i) echo "<option></option>";
		if ($debug) pre_echo($cidades, "$tot_i postos");
		$cidades = pg_fetch_all($res_cidades);

		foreach($cidades as $info_cidade) {
			list($cidade_i,$cidade_c) = preg_split('/#/',htmlentities($info_cidade['cidade']));
			$sel = (strtoupper($cidade) == strtoupper($cidade_i))?" SELECTED":"";
			echo "\t\t\t<option value='$cidade_i'$sel>".ucwords($cidade_i." ".$cidade_c)."</option>\n";
		}

		die();
		if ($tot_i==0) echo "<option SELECTED>Sem resultados</option>";
	}
	exit;
}

if ($_GET['action']=='postos') {

	$estado = $_GET['estado'];
	$familia= $_GET['familia'];
	if (isset($_GET['cidade'])) $cidade=strtoupper(utf8_decode($_GET['cidade']));
	$linha  = $linha_familia[$familia];
	$sql = "SELECT
				tbl_posto.posto,
				tbl_posto_fabrica.codigo_posto,
				TRIM(tbl_posto_fabrica.contato_endereco)	AS endereco,
				tbl_posto_fabrica.contato_numero			AS numero,
                TRIM(tbl_posto.nome)						AS nome,
				LOWER(TRIM(TRANSLATE(tbl_posto_fabrica.contato_cidade,'¡¬¿√ƒ… »ÀÕŒÃœ”‘“’⁄Ÿ‹«',
																'·‚‡„‰ÈÍËÎÌÓÏÔÛÙÚı˙˘¸Á')))
															AS cidade,
				tbl_posto_fabrica.contato_estado			AS estado,
				tbl_posto_fabrica.contato_bairro			AS bairro,
				tbl_posto_fabrica.contato_cep				AS cep,
				tbl_posto_fabrica.nome_fantasia,
				CASE WHEN NOT (tbl_posto.latitude IS NULL)
					THEN tbl_posto.longitude ||','|| tbl_posto.latitude
					ELSE NULL END							AS latlong,
                TRIM(tbl_posto_fabrica.contato_email)		AS email,
				tbl_posto_fabrica.contato_fone_comercial	AS fone,
				ARRAY(SELECT DISTINCT tbl_familia.familia
							FROM tbl_produto
							RIGHT JOIN tbl_posto_linha ON tbl_produto.linha = tbl_posto_linha.linha
							JOIN tbl_linha		 ON tbl_linha.linha = tbl_produto.linha
							JOIN tbl_familia	 ON tbl_familia.familia = tbl_produto.familia
						WHERE tbl_produto.ativo IS TRUE
						 AND tbl_posto_linha.posto = tbl_posto.posto
						 AND tbl_linha.fabrica = $fabrica)	AS familias_posto
			FROM   tbl_posto
			JOIN    tbl_posto_fabrica USING (posto)
			JOIN    tbl_fabrica       USING (fabrica)
			JOIN	tbl_posto_linha ON tbl_posto_linha.posto = tbl_posto_fabrica.posto AND tbl_posto_linha.linha = $linha
			WHERE   tbl_posto_fabrica.fabrica = $fabrica
			AND tbl_posto_fabrica.contato_estado ILIKE '$estado' ";
			
		if ($login_fabrica==24){
				$sql .= "AND tbl_posto_linha.divulgar_consumidor IS TRUE ";	
		}

		$sql .="
			AND UPPER(TRIM(TRANSLATE(contato_cidade,'·‚‡„‰ÈÍËÎÌÓÏÔÛÙÚı˙˘¸Á¡¬¿√ƒ… »ÀÕŒÃœ”‘“’⁄Ÿ‹«',
													'aaaaaeeeeiiiioooouuucAAAAAEEEEIIIIOOOOUUUC')))
						ILIKE '".tira_acentos($cidade)."%'
			AND tbl_posto.posto not in(6359,20462)
			AND tbl_posto_fabrica.credenciamento <> 'DESCREDENCIADO'
			AND tbl_posto_fabrica.tipo_posto <> 163
			AND tbl_posto_fabrica.divulgar_consumidor IS TRUE
			ORDER BY tbl_posto_fabrica.contato_bairro, tbl_posto.nome";
		$res = pg_query ($con,$sql);
		$total_postos = ($tem_mapa=pg_num_rows($res));
		$cidade = pg_fetch_result($res, $total_postos-1, 'cidade');

		if($total_postos > 0){

			for ($i = 0 ; $i < $total_postos ; $i++) {
                $row = pg_fetch_array($res, $i);
                foreach ($row as $campo => $valor) {
                    $$campo = trim($valor);
                }
				$familias_posto = preg_split('/,/',preg_replace('/^\{|\}$/', '', $familias_posto));
				$posto_nome = iif((strlen($nome_fantasia)>0),$nome_fantasia,$nome);
				$end_completo = $bairro  . " - " . $endereco . ", " . $numero; 
				$end_mapa     = "q=$endereco, $numero, $cep, $cidade, $estado, Brasil";
				$link_mapa = "https://maps.google.com/maps?f=q&source=s_q&hl=pt-BR&$end_mapa&ie=windows-1252";
	
				?>
				
			 <div class="container container-info">
			    <div class="row contact-assist">
			        <div class="col-md-12 info-col">
			          <h5><?php echo $posto_nome ?></h4>
			          <span class="assist-info">
			          	<a target="_blank" href="<?php echo $link_mapa ?>">
			            	<span title="Abrir Mapa">
			            		<i class="fa fa-map-marker icon-map" aria-hidden="true"></i>
			            	</span>
			            </a>
			            <?php echo $end_completo ?>
			          </span> 
			          <? if($email !== '') : ?>
				          <span class="assist-info">
				          	<a href= <?php echo "mailto:".strtolower($email) ?>>
				            	<span title="Abrir Email">
				            		<i class="fa fa-envelope icon-mail" aria-hidden="true"></i>
				            	</span> 
				            </a> 
				            <?php echo $email ?>
				          </span> 
				      <? endif ?>
				      <? if($fone !== '') : ?>
				          <span class="assist-info">
				            <span><i class="fa fa-phone icon-tel" aria-hidden="true"></i></span>  
				            <?php echo $fone ?>
				          </span>   
			          <? endif ?> 
	          			<div class="form-group select-produto">
			              <label id="labelProduto" for="selectProduto">AssistÍncia de:</label>
			              <select class="form-control" id="selectProduto">
			              	<?foreach ($familias_posto as $familia) {
			                	echo "\t\t\t\t\t<option>".$familias[$familia]."</option>\n";
			                }?>
			                <option>1</option>
			              </select>
		            	</div> 
			        </div>
			    </div>
			</div>
		<?} 
	}
	
	exit;
}
//  FIM AJAX
$base_url = 'https://' . $_SERVER['SERVER_NAME'] . dirname($_SERVER['PHP_SELF']).'/';
$sqlInsertLog = "INSERT INTO tbl_log_conexao(programa) VALUES ('$PHP_SELF')";
$resInsertLog = pg_query($con, $sqlInsertLog); ?>

<!doctype html>
<html lang="pt-br">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="stylesheet" href="../bootstrap4/css/bootstrap.min.css">
 	<link rel="stylesheet" href="../font_awesome_4/css/font-awesome.min.css">
 	<link rel="stylesheet" href="css/estilo_map_sugar.css">
 	<script src="../../js/jquery-1.4.2.js" type="text/javascript"></script>
	<script type="text/javascript" src="../callcenter/suggar.js"></script>
</head>
<section class="container-map">
  <div class="container container-filters" id="containerFilters">
  	<div class="row">
      <div class="col-md-6 col-map">
       <?php include_once("mapa_brasil_svg.php"); ?> 
    </div>
    <div class="col-md-6 form-map">
        <form>
            <div class="form-group">
              <label for="selectFamilia" class="label-assist">Selecione a famÌlia de produtos:</label>
              <select class="form-control select-assist" id="selectFamilia">
                <?foreach ($familias as $familia=>$nome) {
					echo str_repeat("\t", 8)."<option value='$familia'>$nome</option>\n";
    			}?>
              </select>
            </div>
            <div class="form-group">
              <label for="selectEstado" class="label-assist">Selecione o Estado:</label>
              <select class="form-control select-assist" id="selectEstado">
              	<option></option>
              	<? foreach ($estados as $uf=>$nome) {
					echo str_repeat("\t", 8)."<option value='$uf'>$nome</option>\n";
    			}?>
              </select>
            </div>
             <div class="form-group" id="groupCidade" style="display:none">
              <label for="selectCidade" class="label-assist">Selecione a Cidade:</label>
              <select class="form-control select-assist" id="selectCidade">
                <option>1</option>    
              </select>
            </div>
      </form>
    </div>
  </div>
  </div>
  <div class="container-posto"></div>
</section>
<script>

	$(document).ready(function(){

		var php_self = window.location.pathname;
		var estados = $('.estado');
		var selectEstado = $('#selectEstado');
		var selectFamilia = $("#selectFamilia");
		var groupCidade = $("#groupCidade");
		var selectCidade = $('#selectCidade');
		var containerFilters = $('#containerFilters');
		var containerPosto = $('.container-posto');
		var containerMap = $('.container-map');

		estados.each(function(index, item) {
			$(item).bind( "click", function() {
			 	selectEstado.attr('value', $(this).attr('uf'))
			 	selectEstado.change();
			});
		});

		selectEstado.change(function() {

			containerPosto.empty();

		    var estado = selectEstado.val();
			var familia= selectFamilia.val();
		    if (estado == '') {
				groupCidade.fadeOut(100);
				return false;
			}
			$.get(php_self, {'action': 'cidades','estado': estado, 'familia': familia},
			  function(data){
				if (data.indexOf('Sem resultados') < 0) {
					groupCidade.fadeIn(100);
				    selectCidade.html(data).val('').removeAttr('disabled');
				} else {
				    selectCidade.html(data).val('Sem resultados').attr('disabled','disabled');
				}
			});
		});

		selectCidade.change(function(){

			containerPosto.empty();
	
		 	var estado = selectEstado.val();
			var cidade = selectCidade.val();
			var familia= selectFamilia.val();
			$.get(php_self, {'action': 'postos','estado': estado,'cidade': cidade, 'familia': familia},function(data){
				containerPosto.append(data);
			  });
		})
	})
</script>