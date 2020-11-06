<?php 

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';
include '../funcoes.php';

$posto   = $_REQUEST['posto'];
$fabrica = $_REQUEST['fabrica'];

if (!empty($posto) && !empty($fabrica)) {
	$sql = "SELECT tbl_posto.fone AS posto_fone,
				   tbl_posto.fax AS posto_fax,
				   tbl_posto.telefones AS posto_tel,
				   tbl_posto_fabrica.contato_fone_residencial,
				   tbl_posto_fabrica.contato_fone_comercial,
				   tbl_posto_fabrica.contato_cel,
				   tbl_posto_fabrica.contato_fax,
				   tbl_posto_fabrica.contato_telefones,
				   tbl_posto.cnpj,
				   tbl_posto.nome
			FROM tbl_posto
			JOIN tbl_posto_fabrica USING(posto)
			WHERE tbl_posto.posto = {$posto}
			AND tbl_posto_fabrica.fabrica = {$fabrica}";
	$res = pg_query($con, $sql);
	if (pg_num_rows($res) > 0) {
		$fones = pg_fetch_all($res);
		$fonesPuro = [];
		$fonesFax = [];
		$postoName = pg_fetch_result($res, 0, 'cnpj') . " - " . pg_fetch_result($res, 0, 'nome');
		if (strlen($postoName) > 47) {
			$postoName = substr($postoName, 0, 47)."...";
		}

		foreach ($fones as $key => $value) {
			foreach ($value as $k => $v) {
				if ($k == 'nome' || $k == 'cnpj') {
					continue;
				}

				if (empty(trim($v)) || trim($v) == "" || strtoupper($v) == 'NULL') {
					continue;
				}

				$fn = preg_replace("/[^0-9]/", "", $v);

				if ($k == 'posto_fax' || $k == 'contato_fax') {
					$fonesFax[] = $fn;
					continue;
				}

				$fonesPuro[] = $fn;
			}
		}
		
		$fonesPuro = array_unique($fonesPuro);
		$fonesPuro = array_filter($fonesPuro);
		$fonesFax  = array_unique($fonesFax);
		$fonesFax  = array_filter($fonesFax);
?>
		
		<!DOCTYPE html>
		<html lang="en">
		  <head>
		    <meta charset="utf-8">
		    <meta http-equiv="X-UA-Compatible" content="IE=edge">
		    <meta name="viewport" content="width=device-width, initial-scale=1">
		    

		    <!-- Bootstrap -->
		    <link type="text/css" rel="stylesheet" media="screen" href="../admin/bootstrap/css/bootstrap.css" />

	        <style>
	            .tac {
	                text-align: center !important;
	            }

	            .title {
	            	background-color: #596d9b; 
	            	color: #ffffff;
	            }

		    hr {
			border-color : #000;	
		    }
	            
	        </style>
	        <script type="text/javascript" src="../plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
	        <script type="text/javascript" src="../plugins/posvenda_jquery_ui/js/jquery-ui-1.10.3.custom.js"></script>
	        <script type="text/javascript">
	            $(function() {
	                
	            });
	        </script>
		    </head>
		    <?php if (count($fonesPuro) > 0 || count($fonesFax) > 0) { ?>
			    <body>
			        <br />
			        <div class='container-fluid'>
			            <table class="table table-hover">
						    <thead>
							    <tr>
							      <th nowrap scope="col" colspan="2" class="title tac"><?=$postoName?></th>
							    </tr>
							 </thead>
						   <tbody>
<?php
								foreach ($fonesPuro as $key => $value) {
								 	$label = 'Celular';

								 	$validaCel = '';
								 	$validaCel = valida_celular($value);

								 	if (!empty($validaCel)) {
								 		$label = "Telefone";
								 	}
?>
								    <tr>
								      <th class="tac"><?=$label?></th>
								      <td class="tac"><?=phone_format($value)?></td>
								    </tr>
<?php
						 		}

			  				    if (count($fonesFax) > 0) {
								 	foreach ($fonesFax as $key => $value) {
								 		$label = 'Celular';

									 	$validaCel = '';
									 	$validaCel = valida_celular($value);

									 	if (!empty($validaCel)) {
									 		$label = "Fax";
									 	}

?>
								 		<tr>
									      <th class="tac"><?=$label?></th>
									      <td class="tac"><?=phone_format($value)?></td>
									    </tr>
<?php
						 			}
						 		}
?>
					  	 	</tbody>
						</table>
		        	</div>
		    	</body>
		    <?php } else { ?>
		    		<div style="background-color: #fcf8e3; border:1px solid #fbeed5;">
						<h4 style="color: #c09853; text-align: center;">Nenhum Resultado Encontrado</h4>
					</div>
		    <?php } ?>
		</html>

<?php
	} else {
?>
		<div style="background-color: #fcf8e3; border:1px solid #fbeed5;">
			<h4 style="color: #c09853; text-align: center;">Nenhum Resultado Encontrado</h4>
		</div>
<?php
	}
} else {
?>
	<div style="background-color: #fcf8e3; border:1px solid #fbeed5;">
		<h4 style="color: #c09853; text-align: center;">Nenhum Resultado Encontrado</h4>
	</div>
<?php
}