<?php 

include "dbconfig.php";
include "includes/dbconnect-inc.php";
include 'autentica_admin.php';
include 'funcoes.php';
include "../class/log/log.class.php";

include_once "../class/tdocs.class.php";

$produto_referencia = $_GET["produto_referencia"];


if(isset($_GET['comunicado'])){
	$comunicado = $_GET["comunicado"];

	$sql = "SELECT * FROM tbl_comunicado WHERE comunicado = $comunicado AND fabrica = $login_fabrica";
	$res = pg_query($con, $sql);

	if(pg_num_rows($res)>0){
		$mensagem = pg_fetch_result($res, 0, 'mensagem');
		$link = pg_fetch_result($res, 0, 'link_externo');
		$titulo = pg_fetch_result($res, 0, 'descricao');			
		$comunicado = pg_fetch_result($res, 0, 'comunicado');

		$tDocs = new TDocs($con, $login_fabrica);
		$tDocs->setContext("comunicados");
		$info = $tDocs->getdocumentsByRef($comunicado, "comunicados");
	}

?>
	<table id="roteiros-list" style="width: 100%" class='table table-striped table-bordered table-hover'>
        <thead>
            <tr class='titulo_coluna' >
                <td colspan="100%" class="tac">Informações Técnicas</td>
            </tr>
            <tr >
                <th style="width: 25%" class='titulo_coluna' nowrap>Titulo</th>
                <td><?=$titulo?></td>
            </tr>
            <tr >
                <th class='titulo_coluna' nowrap>Mensagem</th>
                <td><?=$mensagem?></td>
            </tr>
            <tr>
                <th class='titulo_coluna' nowrap>Link</th>
                <td><?=$link?></td>
            </tr>
            <?php if(!empty($info->url)){ ?>
            <tr>
                <th class='titulo_coluna' nowrap>Download</th>
                <td><?php echo "<a target='_blank' href='".$info->url."'> Download </a>";?></td>
            </tr>
            <? } ?>
        </thead>
    </table>
    <div style="text-align: center;">
    	<button id="voltar" type="button" class="btn btn-primary">Voltar</button>
    </div>
<?php	
}
?>

<!DOCTYPE>
<html>
	<head>
		<title>Consulta Informações Técnica</title>
		<script src="../js/jquery-1.7.2.js" ></script>

		<style type="text/css">
			h1{
				font: 20px arial; 
				margin: 20px;
			}
		</style>

		<link type="text/css" rel="stylesheet" media="screen" href="bootstrap/css/bootstrap.css" />
		<link type="text/css" rel="stylesheet" media="screen" href="bootstrap/css/extra.css" />
		<link type="text/css" rel="stylesheet" media="screen" href="css/tc_css.css" />
		<link type="text/css" rel="stylesheet" media="screen" href="css/tooltips.css" />
		<link type="text/css" rel="stylesheet" media="screen" href="plugins/posvenda_jquery_ui/css/posvenda_ui/jquery-ui-1.10.3.custom.css">
		<link type="text/css" rel="stylesheet" media="screen" href="bootstrap/css/ajuste.css" />

		<script src="plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
		<script src="plugins/posvenda_jquery_ui/js/jquery-ui-1.10.3.custom.js"></script>
		<script src="plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.core.min.js"></script>
		<script src="plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.widget.min.js"></script>
		<script src="plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.effect.min.js"></script>
		<script src="plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.tabs.min.js"></script>
		<script src="bootstrap/js/bootstrap.js"></script>
		<style>
		    #btn-call-fileuploader, #div_anexos .titulo_tabela{display: none;}
		    .tc_formulario {
		        background-color: transparent;
		        text-align: center;
		    }
		</style>
		<?php 

		$plugins = array(
		    "shadowbox",
		    "dataTable",
		);

		include("plugin_loader.php");
		?>

		<script type="text/javascript">
			
			$(function(){
				$(".comunicado").click(function(){

					var comunicado = $(this).data("comunicado");
					$( "#dados" ).load( "consulta_informacoes_tecnica.php?comunicado="+comunicado, function() {					  
						$("#comunicados").hide();
						$("#dados").show();
					});
				});

				$("#voltar").on( "click", function() {
					$("#comunicados").show();
					$("#dados").hide();
				});
			});

		</script>
				

	</head>
	<body>
		<div id="comunicados" style="display: <?=(isset($_GET['comunicado'])) ? "none": "block"; ?>">
			<?php 

			if(isset($_GET['produto_referencia'])){
	    	$sql = "SELECT
	    			tbl_comunicado.comunicado, 
					CASE
					WHEN tbl_comunicado.produto IS NULL THEN
					pcp.referencia
					ELSE pc.referencia
					END AS referencia,
					CASE
					WHEN pc.descricao IS NULL THEN
					pcp.descricao
					ELSE pc.descricao
					END AS descricao,
					tbl_comunicado.tipo,
					to_char(tbl_comunicado.data,'DD/MM/YYYY') AS data
					FROM tbl_comunicado
					LEFT JOIN tbl_comunicado_produto ON tbl_comunicado.comunicado = tbl_comunicado_produto.comunicado
					LEFT JOIN tbl_produto pc ON tbl_comunicado.produto = pc.produto
					LEFT JOIN tbl_produto pcp ON tbl_comunicado_produto.produto = pcp.produto
					WHERE tbl_comunicado.fabrica = $login_fabrica
					AND tbl_comunicado.ativo IS TRUE
					AND (tbl_comunicado.produto = pc.produto OR tbl_comunicado_produto.produto = pcp.produto)

					and (pcp.referencia = '$produto_referencia' OR pc.referencia = '$produto_referencia' )";
			$res = pg_query($con ,$sql);
		}
			if(pg_num_rows($res)>0){ ?>

			<table id="roteiros-list"  style="width: 100%" class='table table-striped table-bordered table-hover ' >
	            <thead>
	                <tr class='titulo_coluna' >
	                    <td colspan="100%" class="tac">Informações Técnicas</td>
	                </tr>
	                <tr class='titulo_coluna' >
	                    <th nowrap>Referência Produto</th>
	                    <th nowrap>Descrição Produto</th>
	                    <th nowrap>Tipo informativo</th>
	                    <th nowrap>Data informativo</th>
	                    <th nowrap>Arquivo</th>
	                </tr>
	            </thead>
	            <tbody>
	            	<?php 
	            	for($i=0; $i<pg_num_rows($res); $i++){
	            		$comunicado = pg_fetch_result($res, $i, 'comunicado');
	            		$referencia = pg_fetch_result($res, $i, 'referencia');
	            		$descricao = pg_fetch_result($res, $i, 'descricao');
	            		$tipo = pg_fetch_result($res, $i, 'tipo');
	            		$data = pg_fetch_result($res, $i, 'data');

	            		$tDocs = new TDocs($con, $login_fabrica);
						$tDocs->setContext("comunicados");

						$info = $tDocs->getdocumentsByRef($comunicado, "comunicados");
						echo "<tr>";
	            		echo "<td>$referencia</td>";
	            		echo "<td>$descricao</td>";
	            		echo "<td class='comunicado' data-comunicado='$comunicado' style='cursor: pointer; color:#0088cc'>$tipo</td>";
	            		echo "<td>$data</td>";
	            		if(!empty($info->url)){
	            			echo "<td><a target='_blank' href='".$info->url."'> Download </a></td>";
	            		}else{
	            			echo "<td></td>";
	            		}            		
	            		echo "</tr>";
	            	}

	            	?>

	            </tbody>
	        </table>
	        <?php }else{  ?>
	        		<div class="alert alert_shadowbox">
						    <h4>Nenhum resultado encontrado</h4>
						</div>'
	        <?php } ?>

    	</div>
    	<div id="dados">
    		
    	</div>
	</body>
</html>
