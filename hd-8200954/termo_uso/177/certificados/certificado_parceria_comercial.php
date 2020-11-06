<?php 
	
	require __DIR__ . '/../../../dbconfig.php';
	require __DIR__ . '/../../../includes/dbconnect-inc.php';

	$query = "SELECT tbl_posto.nome, 
					 tbl_posto_fabrica.contato_cidade AS cidade, 
					 tbl_posto.cnpj, 
					 tbl_posto_fabrica.contato_estado AS estado, 
					 tbl_fabrica.logo, 
					 tbl_posto_fabrica.codigo_posto
			  FROM tbl_posto_fabrica 
			  JOIN tbl_posto ON tbl_posto.posto = tbl_posto_fabrica.posto
			  JOIN tbl_fabrica ON tbl_fabrica.fabrica = tbl_posto_fabrica.fabrica
			  WHERE tbl_posto_fabrica.posto = $login_posto
			  AND tbl_posto_fabrica.fabrica = $login_fabrica";

	$postoInfo = pg_query($con, $query);
	$postoInfo = pg_fetch_object($postoInfo);
	
	$contentCertificado = '<!DOCTYPE html>
        <head>
        	<style type="text/css">
        		
        		.marca_dagua {
					width: 200px;
					height: 200px;
					z-index: -1; 
        		}

        		.marca_dagua {
					background-repeat : no-repeat;
					background-position: center;
					opacity: 0.15;
					top: 325px;
					bottom: 0;
					right: 0;
					position: relative;

        		}

        		.lucida_font {

        			font-family : "Trebuchet MS", Helvetica, sans-serif;
        			margin-top: -220px;
        		}

        		.center {
        			text-align: center;
        		}

        		.titulo {
        			color : #D93333;

        		}

        		.validade {
        			color : #FB7C7C;
        		}

        		.regular-text {
        			color : #515151;
        		}

        		.posto-info {
        			color : #808080;
        		}

        		.assinatura {
        			padding-left: 10%;
        		}

        	</style>
        </head>
        <body>
        	<br><br>
        	<div class="row center">
				<img src="https://posvenda.telecontrol.com.br/assist/logos/logo_anauger.png" width="220" height="140">
        	</div>
        	<div class="titulo center">
        		<strong><h1>	
        			POSTO DE SERVI�O <br>
        			AUTORIZADO
        		</h1></strong>
        	</div>
			<div class="center" style="z-index: -1; ">
				<img class="marca_dagua" src="https://posvenda.telecontrol.com.br/assist/logos/logo_anauger.png">
			</div>
        	<div class="lucida_font"  style="z-index: 1;">
        		<div style="position: relative;"> 

		        	<div class="validade center">
		        		<strong><h3>
		        			V�LIDO PARA O ANO ' . date("Y") . '
		        		</h3></strong>
		    		</div>
		        	<div class="regular-text center">
		        		<strong><h2>
		        			A IND�STRIA DE MOTORES ANAUGER S.A. <br> 
		        			CERTIFICA � EMPRESA
		        		</h2></strong>
		        	</div>
		        	<div class="posto-info center">
		        		<strong>
			        		<div class=""> CNPJ ' . $postoInfo->cnpj . '</div>
			        		<div class=""> ' . $postoInfo->nome . '</div>
			        		<div class=""> ' . $postoInfo->cidade . ' - ' . $postoInfo->estado . '</div>
		        		</strong>
			        </div>
		        </div>
	        	<div class="regular-text center">
	        		<strong><h2>
	        			PARA PRESTA��O DE SERVI�OS DE       <br> 
	        			ASSIST�NCIA T�CNICA, RECONHECENDO-A <br>
	        			APTA PARA MANUTEN��O DOS PRODUTOS   <br>
	        			DE SUA LINHA DE FABRICA��O.
	        		</h2></strong>
	        	</div>
	        	<div class="row center">
					<img src="https://posvenda.telecontrol.com.br/assist/imagens/produtos_anauger.png" width="620" height="140">
        		</div>
	        	<br><br><br>
	        	<div class="assinatura regular-text">
	        		<img src="https://posvenda.telecontrol.com.br/assist/imagens/assinatura_anauger.png" width="220" height="120">
	        		<br>
	        		Supervisor de Assist�ncia T�cnica <br>
	        		Departamento de Assist�ncia T�cnica
	        	</div>
	        </div>
        </body>
	<br>';	
?>
