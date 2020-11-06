<?php
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include 'autentica_admin.php';
include 'autentica_usuario.php';


  // echo "<pre>";
  // print_r($_GET);
  // echo "</pre>";

if (!empty($_GET["tecnico"])) {
	$tecnico = $_GET["tecnico"];
	//echo $tecnico;
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


		<script>
			// $(function () {
			// 	$.dataTableLupa();
			// });
		</script>
	</head>

	<body>
		<div id="container_lupa" style="overflow-y:auto;">
			<div id="topo">
				<img class="espaco" src="imagens/logo_new_telecontrol.png">

			</div>
			<br /><hr />

			<?
		    $sql = "SELECT DISTINCT tbl_treinamento.titulo                                                      ,
		    						tbl_tecnico.nome						,
		    						tbl_tecnico.cpf						,
		                			tbl_treinamento_posto.tecnico_cpf                                           ,
		                			tbl_treinamento_posto.ativo                                                 ,
		                			tbl_treinamento.titulo                                                      ,
		                			tbl_treinamento.local                                                      ,
		                			tbl_treinamento.cidade                                                      ,
		                			TO_CHAR(tbl_treinamento_posto.data_inscricao,'DD/MM/YYYY') AS data_inscricao,
		                			TO_CHAR(tbl_treinamento_posto.data_inscricao,'HH24:MI:SS') AS hora_inscricao

		            FROM tbl_treinamento_posto
		            JOIN tbl_treinamento using(treinamento)
		            JOIN      tbl_posto USING(posto)
		            JOIN      tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
		            JOIN tbl_tecnico ON tbl_treinamento_posto.tecnico = tbl_tecnico.tecnico
		            WHERE tbl_treinamento_posto.posto = $login_posto
		            AND  tbl_treinamento.fabrica = $login_fabrica
		            AND  tbl_treinamento_posto.ativo = 't'
		            AND  tbl_tecnico.tecnico = $tecnico

		            ORDER BY tbl_treinamento.titulo    " ;
		    //echo '{"sql": "' .  $sql . '"}';

		    $res = pg_query($con,$sql);

if(pg_num_rows($res) > 0){

?>
<form name="frm_tab" method="GET" class="form-search form-inline" enctype="multipart/form-data" >
	<table class='table table-striped table-bordered table-hover table-fixed'>
		<thead>
		<tr class='titulo_coluna'>
		<td>Título</td>
		<td>Nome do Técnico</td>
		<td>CPF</td>
		<td>Data de Inscrição</td>
		<td>Hora de Inscrição</td>
		<td>Local</td>
		<td>Ativo</td>
		</tr>
		</thead>
		<tbody>
	<?
	for ($i = 0 ; $i < pg_num_rows($res) ; $i++) {

		//$tecnico	= pg_fetch_result($res_func, $i, 'tecnico');
		$titulo			= pg_fetch_result($res, $i, 'titulo');
		$nome  			= pg_fetch_result($res, $i, 'nome');
		$cpf  			= pg_fetch_result($res, $i, 'cpf');
		$data_inscricao = pg_fetch_result($res, $i, 'data_inscricao');
		$hora_inscricao = pg_fetch_result($res, $i, 'hora_inscricao');
		$local    		= pg_fetch_result($res, $i, 'local');
		$ativo    		= pg_fetch_result($res, $i, 'ativo');
		$cidade		    = pg_fetch_result($res, $i, 'cidade');

	?>
		<tr id="<?php echo $tecnico?>">
		<td><?echo $titulo?></td>
		<td><?echo $nome?></td>
		<td><?echo $cpf?></td>
		<td><?echo $data_inscricao?></td>
		<td><?echo $hora_inscricao?></td>
		<td><? if (strlen($cidade) > 0 AND strlen($local) > 0) {
			echo $cidade."-".$local;
		}elseif (strlen($cidade) > 0 AND strlen($local) == 0) {
			echo $cidade;
		}elseif (strlen($cidade) == 0 AND strlen($local) > 0) {
			echo $local;
		}else{
			echo " ";
		}
		?>
		</td>
		<td><?if ($ativo === 't') {
			echo "Ativo";
		}else{
			echo "Desativado";
		}
		?>
		</td>
		</tr>
	<?
	}
	?>
		</tbody>
	</table>
</form>
<br />
<?
}else{
	?>
	<div class="alert alert_shadobox">

	  	<?php if($login_pais == "BR"){ ?>
		    <h4>Não foi encontrado treinamento para o técnico.</h4>
			<?php }else{?>
				<h4>Se encontró formación para el técnico.</h4>
			<?php } ?>
	</div>
<?
}
?>

	</div>
	</body>
</html>
