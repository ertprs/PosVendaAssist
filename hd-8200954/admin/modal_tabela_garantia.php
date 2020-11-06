<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';
include_once '../helpdesk/mlg_funciones.php';

if(isset($_POST["ajax_verifica_garantia"])){
	$defeito_reclamado = $_POST["defeito_reclamado"];
	$data_nota         = $_POST["data_nota"];
	$cliente_admin     = $_POST["cliente_admin"];

    list($dia, $mes, $ano) = explode("/", $data_nota);
    $data_nota = $ano."-".$mes."-".$dia;

    $result = array();

    $sql = "SELECT mao_de_obra FROM tbl_tabela_garantia 
        WHERE fabrica             = {$login_fabrica}
            AND cliente_admin     = {$cliente_admin} 
            AND defeito_reclamado = {$defeito_reclamado}";
    $res = pg_query($con, $sql);

    if(pg_num_rows($res) > 0){
        $mao_de_obra = pg_fetch_result($res, 0, mao_de_obra);
        $data_nota   = date('Y-m-d', strtotime("{$data_nota} +{$mao_de_obra} months"));

        if(strtotime($data_nota) < strtotime(date("Y-m-d"))){
            $result["garantia"] = false;
        } else {
        	$result["garantia"] = true;
        }

        $result["success"] = true;
    } else {
    	$result["success"] = false;
    }
 
    echo json_encode($result);
    exit;
}

include("plugin_loader.php");
?>

<link href="bootstrap/css/bootstrap.css" type="text/css" rel="stylesheet" media="screen" />
<link href="bootstrap/css/extra.css" type="text/css" rel="stylesheet" media="screen" />
<link href="css/tc_css.css" type="text/css" rel="stylesheet" media="screen" />
<link href="css/tooltips.css" type="text/css" rel="stylesheet" />
<link href="plugins/posvenda_jquery_ui/css/posvenda_ui/jquery-ui-1.10.3.custom.css" type="text/css" rel="stylesheet" media="screen">
<link href="bootstrap/css/ajuste.css" type="text/css" rel="stylesheet" media="screen" />
<script src="plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
<script src="plugins/posvenda_jquery_ui/js/jquery-ui-1.10.3.custom.js"></script>


<script>
	$(document).ready(function(){
		$(".btnFechar").on("click", function(){
			window.parent.Shadowbox.close();
		});
	});
</script>

<body class="container" style="background-color: #FFFFFF; overflow: hidden; padding: 10px 20px; width: 94%;" >
	<form method="post" >
		<div class="control-group-inline" >
		    <div class="controls" >
				<button type="button" class="btn btn-danger btn-small btnFechar" style="float: right;" >Fechar</button>
	  	  </div>
	 	</div>
		<div class="row-fluid" >
			<div class='span12' >
				<div class='control-group' >
					<div class='controls controls-row' >
						<div class='span12' >
						<?php 
						$defeito_reclamado = $_GET["defeito_reclamado"];
						$garantia          = $_GET["garantia"];
						if($garantia == "false"){
							?>
							<label class="alert alert-warning"><h4>Produto fora de garantia</h4></label>
							<?php 
						}
						$sql = "SELECT 
								tbl_tabela_garantia.defeito_reclamado,
								tbl_tabela_garantia.ano_fabricacao,
								tbl_tabela_garantia.mao_de_obra,
								tbl_tabela_garantia.pecas,
								tbl_defeito_reclamado.descricao
							FROM tbl_tabela_garantia 
								INNER JOIN tbl_defeito_reclamado ON tbl_defeito_reclamado.defeito_reclamado = tbl_tabela_garantia.defeito_reclamado
									AND tbl_defeito_reclamado.fabrica = {$login_fabrica}
									AND tbl_defeito_reclamado.defeito_reclamado = {$defeito_reclamado}
								WHERE tbl_tabela_garantia.fabrica = {$login_fabrica}
								ORDER BY defeito_reclamado, ano_fabricacao";
						$res = pg_query($con, $sql);

						$row = pg_num_rows($res);

						if ($row > 0) {
						?>
						<table id="resultado_tabela_garantia" class='table table-striped table-bordered table-fixed'>
							<tr class='titulo_coluna'>
								<th>Defeito</th>
								<th class="th_result">Ano de Fabricação</th>
								<th class="th_result">Mão de obra</th>
								<th class="th_result">Peças</th>
							</tr>
								<?php 

								for ($i = 0; $i < $row; $i++) { 
									$defeito_reclamado  = pg_fetch_result($res, $i, 'defeito_reclamado');
									$ano_fabricacao     = pg_fetch_result($res, $i, 'ano_fabricacao');
									$mao_de_obra        = pg_fetch_result($res, $i, 'mao_de_obra');
									$pecas              = pg_fetch_result($res, $i, 'pecas');
									$descricao          = pg_fetch_result($res, $i, 'descricao');
									?>
									<tr>
										<td>
											<?=$descricao;?>
										</td>
										<td data-ano_fabricacao="<?=$ano_fabricacao?>" class="tac"><?=$ano_fabricacao;?></td>
										<td data-mao_de_obra="<?=$mao_de_obra?>" class="tac"><?=$mao_de_obra;?> (meses)</td>
										<td data-pecas="<?=$pecas?>" class="tac"><?=$pecas;?> (meses)</td>
									</tr>
								<?php 
								} ?>
						</table>
						<br>
						<?php }
						?>
						</div>
					</div>
				</div>
			</div>
		</div>
 	</form>
</body>
