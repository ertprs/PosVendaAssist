<?php 

require __DIR__ . '/../dbconfig.php';
require __DIR__ . '/../includes/dbconnect-inc.php';
	
$produto = $_GET['produto'];
$loginFabrica = $_GET['fabrica'];
$meses = $_GET['meses'];

$query = "SELECT osp.os, 
				 TO_CHAR(os.data_abertura, 'DD/MM/YYYY') as data, 
				 prod.produto, 
				 osi.peca, 
				 peca.descricao AS peca_descricao, 
				 osi.qtde 
		  FROM tbl_produto prod
		  JOIN tbl_os_produto osp ON (osp.produto = prod.produto) 
		  JOIN tbl_os os ON (os.os = osp.os) 
		  JOIN tbl_os_item osi ON (osi.os_produto = osp.os_produto)
		  JOIN tbl_peca peca ON (peca.peca = osi.peca)
		  WHERE prod.referencia = '$produto' 
		  AND prod.fabrica_i = $loginFabrica 
		  AND os.data_abertura BETWEEN (CURRENT_DATE - INTERVAL '$meses months') AND CURRENT_DATE";

$res = pg_query($con, $query);

$res = pg_fetch_all($res);

$relatorio = [];

foreach ($res as $ocorrencia) {
	$data = $ocorrencia['data'];
	$data = explode("/", $data);
	$data = intval($data[1]);

	$relatorio[$data][] = $ocorrencia;	
}

$estados = [ 1 => "Janeiro", 2 => "Fevereiro", 3 => "Março", 4 => "Abril", 5 => "Maio", 6 => "Junho",
			 7 => "Julho", 8 => "Agosto", 9 => "Setembro", 10 => "Outubro", 11 => "Novembro", 12 => "Dezembro"];
?>

<html>
	<head>
		<title>Detalhes peça por ocorrência</title>
		<link type="text/css" rel="stylesheet" media="screen" href="../bootstrap/css/bootstrap.css" />
		<link type="text/css" rel="stylesheet" media="screen" href="../bootstrap/css/extra.css" />
		<link type="text/css" rel="stylesheet" media="screen" href="../css/tc_css.css" />
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
		<script type="text/javascript" src="plugins/shadowbox/shadowbox.js"></script>
		<link type="text/css" href="../plugins/shadowbox/shadowbox.css" rel="stylesheet" media="all">

		<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/css/bootstrap.min.css" integrity="sha384-MCw98/SFnGE8fJT3GXwEOngsV7Zt27NXFoaoApmYm81iuXoPkFOJwJ8ERdknLPMO" crossorigin="anonymous">
		<script src="https://code.jquery.com/jquery-3.3.1.slim.min.js" integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo" crossorigin="anonymous"></script>
		<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.3/umd/popper.min.js" integrity="sha384-ZMP7rVo3mIykV+2+9J3UJ46jBk0WLaUAdn689aCwoqbBJiSnjAK/l8WvCWPIPm49" crossorigin="anonymous"></script>
		<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/js/bootstrap.min.js" integrity="sha384-ChfqqxuZUCnJSK3+MXmPNIyE6ZbWh2IMqE241rYiqJxyMiZ6OW/JmZQ5stwEULTy" crossorigin="anonymous"></script>
	</head>
	<body>
		<div class="row text-white text-justify" style="background: #596d9b">
			<div class="col mt-2 text-center">
				<h3>Ocorrência de Peças</h3>
			</div>
		</div>
		<div class="container-fluid mt-4">
			<div class="row">
			<?php foreach ($relatorio as $mes => $ocorrencias) { ?>
				<div class="col-2 mt-4 text-center">
					<button class="btn btn-primary btn_conteudo" type="button" data-toggle="collapse" data-target="#collapse<?=$mes?>" aria-expanded="false" aria-controls="collapse<?=$mes?>" data-id="#box-<?=$mes?>"><?=$estados[$mes]?></button>
					<br>
					<div class="mt-2 text-center">
				  		<span class="badge badge-warning"> 
			  				<?php echo count($ocorrencias) . ' ocorrências'?>	
		  				</span>
	  				</div>
				</div>
			<? } ?>
			</div>
			<div id="conteudo">
			<?php foreach ($relatorio as $mes => $ocorrencias) { ?>
  				<div id="collapse<?=$mes?>" class="collapse mt-2">
  					<div class="bg-light text-center mt-6">
						<h2><?=$estados[$mes]?></h2>	
  					</div>
					<div class="card card-body mt-4">
						<table class="table table-striped">
					  		<thead>
							    <tr>
							      <th scope="col">OS</th>
							      <th scope="col">Peça</th>
							      <th scope="col">Quantidade</th>
							    </tr>
						  	</thead>
						  	<tbody>
						  		<?php foreach ($ocorrencias as $ocorrencia) { ?>
						  			<tr>
						      			<th scope="row"><?=$ocorrencia['os']?></th>
										<th scope="row"><?=$ocorrencia['peca_descricao']?></th>
										<th scope="row"><?=$ocorrencia['qtde']?></th>
							    	</tr>
						  		<?php } ?>	
						  	</tbody>
						</table>
					</div>
				</div>
			<? } ?>
			</div>
		</div>
	</body>
</html>

<script type="text/javascript">
	$(function () { 
		$(".btn_conteudo").on('click', function() {

			let tabelas = $('#conteudo').find(".in");

			tabelas.each(function(index) {
				$(this).collapse('hide');
			});
		});
	});
</script>