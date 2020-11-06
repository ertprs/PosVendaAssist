<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include "autentica_admin.php";
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
	</head>

<body style="margin: 0px 0px 0px 0px;" onblur="setTimeout('window.close()',2500);">
	<div id="container_lupa" style="overflow-y:auto;">
		<div id="topo">
			<img class="espaco" src="imagens/logo_new_telecontrol.png">
			<img class="lupa_img pull-right" src="imagens/lupa_new.png">
		</div>
		<br /><hr />
		<?php
			$tipo = trim (strtolower ($_GET['tipo']));
			if($tipo == "nome"){
				$nome = strtoupper (trim ($_GET["campo"]));

				$sql = "SELECT 	tbl_fornecedor.*,
								tbl_cidade.nome as nome_cidade,
								tbl_cidade.estado as nome_estado
						FROM	tbl_fornecedor
						JOIN	tbl_fornecedor_fabrica USING (fornecedor)
						LEFT JOIN   tbl_cidade USING (cidade)
						WHERE	tbl_fornecedor.nome ILIKE '%$nome%'
						AND     tbl_fornecedor_fabrica.fabrica = $login_fabrica
						ORDER BY tbl_fornecedor.nome";
				$res = pg_exec ($con,$sql);

				if (pg_numrows ($res) == 0) {
				?>
					<div class='row-fluid'>
						<div class='span1'></div>
						<div class='span10'>
							<div class='alert alert-error'>
								<h4>Fornecedor <?=$nome?> não encontrado</h4>
							</div>
						</div>
						<div class='span1'></div>
						<script type="text/javascript">
							setTimeout('window.close();',2500);
						</script>
					</div>
				<?php
				}
			}//IF NOME

			if($tipo == "cnpj"){

				$cnpj = strtoupper (trim ($_GET["campo"]));
				$cnpj = str_replace (".","",$cnpj);
				$cnpj = str_replace ("-","",$cnpj);
				$cnpj = str_replace ("/","",$cnpj);
				$cnpj = str_replace (" ","",$cnpj);

				$sql = "SELECT   *
						FROM     tbl_fornecedor
						JOIN     tbl_fornecedor_fabrica USING (fornecedor)
						WHERE    tbl_fornecedor.cnpj ILIKE '%$cnpj%'
						AND      tbl_fornecedor_fabrica.fabrica = $login_fabrica
						ORDER BY tbl_fornecedor.nome";

				$sql = "SELECT      tbl_fornecedor.fornecedor ,
									tbl_fornecedor.nome  ,
									tbl_fornecedor.cnpj  ,
									tbl_cidade.nome as nome_cidade,
									tbl_cidade.estado as nome_estado
						FROM        tbl_fornecedor
						JOIN        tbl_fornecedor_fabrica USING (fornecedor)
						LEFT JOIN   tbl_cidade USING (cidade)
						WHERE       tbl_fornecedor.cnpj ILIKE '%$cnpj%'
						AND         tbl_fornecedor_fabrica.fabrica = $login_fabrica
						GROUP BY	tbl_fornecedor.fornecedor ,
									tbl_fornecedor.nome  ,
									tbl_fornecedor.cnpj  ,
									tbl_cidade.nome      ,
									tbl_cidade.estado
						ORDER BY tbl_fornecedor.nome";
				$res = pg_exec ($con,$sql);

				if (pg_numrows ($res) == 0) {
				?>
					<div class='row-fluid'>
						<div class='span1'></div>
						<div class='span10'>
							<div class='alert alert-error'>
								<h4>CNPJ <?=$cnpj?> não encontrado</h4>
							</div>
						</div>
						<div class='span1'></div>
						<script type="text/javascript">
							setTimeout('window.close();',2500);
						</script>
					</div>
				<?php
				}

			}//IF CNPJ
			echo "<script language='JavaScript'>";
			echo "<!--\n";
			echo "this.focus();\n";
			echo "// -->\n";
			echo "</script>\n";
	?>


	<!-- <div class='row-fluid'>
		<?php
		if($tipo=="nome"){
		?>
			<div class='span1'></div>
				<div class='span10 titulo_coluna'>
					<label>Pesquisando por <b>nome do Fornecedor</b>: <?=$nome?></label>
				</div>
			<div class='span1'></div>
		<?php
		}
		if($tipo=="cnpj"){
		?>
			<div class='span1'></div>
				<div class='span10 titulo_coluna' >
					<label>Pesquisando por <b>CNPJ do Fornecedor</b>: <?=$cnpj?></label>
				</div>
			<div class='span1'></div>
		<?php
		}
		?>
	</div> -->
	<div id="border_table">
		<table id="resultados" class="table table-striped table-bordered table-hover table-lupa" >
			<thead>
			<?php
				if($tipo=="nome"){
				?>
					<tr class='titulo_coluna'>
						<th colspan='2'>Pesquisando por <b>nome do Fornecedor</b>: <?=$nome?></th>
					</tr>
				<?php
				}
				if($tipo=="cnpj"){
				?>
					<tr class='titulo_coluna' colspan='2'>
						<th colspan='2'>Pesquisando por <b>CNPJ do Fornecedor</b>: <?=$cnpj?></th>
					</tr>
				<?php
				}
				?>
				<tr class='titulo_coluna'>
					<th>Nome</th>
					<th>CNPJ</th>
				</tr>
			</thead>
			<tbody>
				<?php
					for ( $i = 0 ; $i < pg_numrows ($res) ; $i++ ) {
						$fornecedor		= pg_result ($res,$i,fornecedor);
						$nome			= pg_result ($res,$i,nome);
						$cnpj           = trim(pg_result($res,$i,cnpj));
						$cidade         = trim(pg_result($res,$i,nome_cidade));

						$nome = str_replace ('"','',$nome);
						$cnpj = substr ($cnpj,0,2) . "." . substr ($cnpj,2,3) . "." . substr ($cnpj,5,3) . "/" . substr ($cnpj,8,4) . "-" . substr ($cnpj,12,2);

						if($i%2==0) $cor = "#F7F5F0"; else $cor = "#F1F4FA";
						echo "<tr bgcolor='$cor'>\n";

						echo "<td class='tac'>\n";
						echo "$cnpj";
						echo "</td>\n";

						echo "<td class='tac'>\n";
						if ($_GET['forma'] == 'reload') {
							echo "<a href=\"javascript: opener.document.location = retorno + '?fornecedor=$fornecedor' ; this.close() ;\" > " ;
						}else{
							echo "<a href=\"javascript: nome.value = '$nome' ; cnpj.value = '$cnpj' ; this.close() ; \" >";
						}
						echo "$nome";
						echo "</a>\n";
						echo "</td>\n";

						//echo "<td>\n";
						//echo "<font face='Arial, Verdana, Times, Sans' size='-1' color='#000000'>$cidade</font>\n";
						//echo "</td>\n";

						echo "</tr>\n";
					}//FOR
		?>
				</tbody>
			</table>
</body>
</html>