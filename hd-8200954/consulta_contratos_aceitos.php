<?php
include_once 'dbconfig.php';
include_once 'includes/dbconnect-inc.php';
include_once 'autentica_usuario.php';
include "classes/mpdf61/mpdf.php";
include "plugins/fileuploader/TdocsMirror.php";

$title = "Lista de contratos aceitos";

include 'cabecalho_new.php';

$PHP_SELF = $_SERVER['PHP_SELF'];

$plugins = array(
    "shadowbox",
    "price_format",
    "mask",
    "ckeditor",
    "autocomplete",
    "ajaxform",
    "fancyzoom"    
);

include("plugin_loader.php");

$sqlContratos = "SELECT DISTINCT
					tbl_contrato.contrato,				
					tbl_posto_contrato.fabrica,
					tbl_contrato.numero_contrato,					
					tbl_posto_contrato.data_input,
					tbl_posto_contrato.campos_adicionais::json->>'nome_aceite' AS nome_aceite
				FROM tbl_posto_contrato				
				JOIN tbl_contrato ON tbl_contrato.fabrica = tbl_posto_contrato.fabrica
				AND tbl_contrato.contrato = tbl_posto_contrato.contrato						
				WHERE tbl_posto_contrato.fabrica = {$login_fabrica}
				AND tbl_posto_contrato.posto = {$login_posto}
				ORDER BY tbl_posto_contrato.data_input DESC";

$resContratos = pg_query($con, $sqlContratos);
?>

<form name="frm_contratos_aceitos" method="post" action="" align="center" class='form-search form-inline tc_formulario' >
	<div class="titulo_tabela">Contratos Aceitos</div>
	<br/>
	<div class="row-fluid">
		<div class="span2"></div>
	    <div class="span8">
	    	<table id="contrato_aceito" class="table table-striped table-bordered table-hover table-fixed">
			    <thead>
			        <tr class="titulo_tabela" >
			            <th colspan="100">
			                Relação de Contratos
			            </th>
			        </tr>
			        <tr class='titulo_coluna'>
			        	<th>Número Contrato</th>
			        	<th>Responsável Aceite</th>
			        	<th>Data Aceite</th>
						<th>Ações</th>
					</tr>
				</thead>
				<tbody>
	    	<?
	    		for($x=0; $x<pg_num_rows($resContratos); $x++){
	    			$cor = ($x % 2 == 0) ? "#F7F5F0": '#F1F4FA'; 

					$codigo_fabrica  = pg_fetch_result($resContratos, $x, 'fabrica');
					$numero_contrato = pg_fetch_result($resContratos, $x, 'numero_contrato');
					$descricao		 = pg_fetch_result($resContratos, $x, 'descricao');
					$data_cadastro   = pg_fetch_result($resContratos, $x, 'data_input');
					$nome_aceite	 = pg_fetch_result($resContratos, $x, 'nome_aceite');	
					$cod_contrato	 = pg_fetch_result($resContratos, $x, 'contrato');				

					$contratos_id = pg_fetch_all($res_contrato);

					$sql_id_tdocs = "SELECT tdocs_id FROM tbl_tdocs WHERE referencia_id = ".$cod_contrato.$login_posto." AND fabrica = $login_fabrica AND referencia = 'posto_contrato' AND situacao = 'ativo' ORDER BY data_input DESC  LIMIT 1";                                    

					$res_id_tdocs = pg_query($con, $sql_id_tdocs);
					if (pg_num_rows($res_id_tdocs) > 0) {
						$unique_id        = pg_fetch_result($res_id_tdocs, 0, 'tdocs_id');
						$tdocsMirror      = new TdocsMirror();
						$resposta_link    = $tdocsMirror->get($unique_id);
						$arquivo_contrato    = $resposta_link["link"];    
						
					}
                            			?>
		            <tr style="background-color:<?=$cor?>">
		            	<td class="tac">
		            		<?=$numero_contrato ?>
		            	</td>
		            	<td class="tac">
		            		<?=$nome_aceite ?>
		            	</td>     
		            	<td class="tac">
		            		<?=$data_cadastro ?>
		            	</td>
		                <td class="tac">
		                	<a href="<?=$arquivo_contrato?>" type="button" class="btn btn-success btn-xs" title="Ler Contrato">Ler Contrato</a>		                    
		                </td>
		            </tr>
            <?
				}
	    	?>
	    		</tbody>
	    	</table>
	    </div>
	    <div class="span2"></div>
	</div>
</form>

<?php
    include "rodape.php";
?>
