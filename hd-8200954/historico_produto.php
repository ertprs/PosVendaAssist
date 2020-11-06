<?php
include "dbconfig.php";
include "includes/dbconnect-inc.php";

if (preg_match("/\/admin\//", $_SERVER["PHP_SELF"])) {
	include 'autentica_admin.php';
} else {
	include 'autentica_usuario.php';
}

$serie = $_REQUEST["serie"];

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

	<body>
		<div id="container_lupa" style="overflow-y:auto;">
			<div id="topo">
				<img class="espaco" src="imagens/logo_new_telecontrol.png">
				<img class="lupa_img pull-right" src="imagens/lupa_new.png">
			</div>

			<?php

			if(strlen($serie) > 0){

                $sql = "SELECT  tbl_os.os,
                                tbl_posto.nome                  AS posto,
                                tbl_os.data_abertura,
                                tbl_tipo_atendimento.descricao  AS tipo_atendimento,
                                tbl_produto.referencia,
                                tbl_produto.descricao,
                                tbl_os.qtde_hora,
                                tbl_os.hora_tecnica,
                                tbl_defeito_constatado.descricao AS defeito_constatado
                        FROM    tbl_os
                        JOIN    tbl_posto               ON  tbl_posto.posto                             = tbl_os.posto
                        JOIN    tbl_posto_fabrica       ON  tbl_posto_fabrica.posto                     = tbl_posto.posto
                                                        AND tbl_posto_fabrica.fabrica                   = {$login_fabrica}
                        JOIN    tbl_produto             ON  tbl_produto.produto                         = tbl_os.produto
                                                        AND tbl_produto.fabrica_i                       = {$login_fabrica}
                        JOIN    tbl_tipo_atendimento    ON  tbl_tipo_atendimento.tipo_atendimento       = tbl_os.tipo_atendimento
                                                        AND tbl_tipo_atendimento.fabrica                = {$login_fabrica}
                   LEFT JOIN    tbl_defeito_constatado  ON  tbl_defeito_constatado.defeito_constatado   = tbl_os.defeito_constatado
                                                        AND tbl_defeito_constatado.fabrica              = {$login_fabrica}
                        WHERE   tbl_os.serie        = UPPER('{$serie}')
                        AND     tbl_os.fabrica      = {$login_fabrica}
                        AND     tbl_os.excluida     IS NOT TRUE
                        AND     tbl_os.finalizada   IS NOT NULL
                  ORDER BY      tbl_os.data_abertura DESC

				";
// 				echo(nl2br($sql));
				$res = pg_query($con, $sql);

				if(pg_num_rows($res) > 0){

					?>

					<hr style="margin-top: 50px; margin-bottom: 25px;" />

					<div id="border_table">
						<table class="table table-striped table-bordered table-hover table-lupa" >
							<thead>
								<tr class='titulo_coluna'>
									<th>Posto</th>
									<th>Data</th>
									<th>Tipo Atendimento</th>
									<th>Produto</th>
									<th>Horimetro</th>
									<th>Revisão</th>
									<th>Defeito Constatado</th>
									<th>Peça / Serviço Realizado</th>
								</tr>
							</thead>
							<tbody>

					<?php

							for($i = 0; $i < pg_num_rows($res); $i++){
								$os                 = pg_fetch_result($res, $i, "os");
								$posto 				= pg_fetch_result($res, $i, "posto");
								$data_abertura 		= pg_fetch_result($res, $i, "data_abertura");
								$tipo_atendimento 	= utf8_encode(pg_fetch_result($res, $i, "tipo_atendimento"));
								$referencia 		= pg_fetch_result($res, $i, "referencia");
								$descricao 			= pg_fetch_result($res, $i, "descricao");
								$horimetro 			= pg_fetch_result($res, $i, "qtde_hora");
								$revisao 			= pg_fetch_result($res, $i, "hora_tecnica");
								$defeito_constatado = pg_fetch_result($res, $i, "defeito_constatado");

								list($ano, $mes, $dia) = explode("-", $data_abertura);
								$data_abertura = $dia."/".$mes."/".$ano;

								$tipo_atendimento = utf8_decode($tipo_atendimento);

								$pecas = array();

								$sqlPecas = "SELECT tbl_peca.referencia || ' - ' || tbl_peca.descricao AS peca,
													tbl_servico_realizado.descricao AS servico
											 FROM tbl_os_item
											 INNER JOIN tbl_peca ON tbl_peca.peca = tbl_os_item.peca AND tbl_peca.fabrica = {$login_fabrica}
											 INNER JOIN tbl_os_produto ON tbl_os_produto.os_produto = tbl_os_item.os_produto
											 INNER JOIN tbl_servico_realizado ON tbl_servico_realizado.servico_realizado = tbl_os_item.servico_realizado AND tbl_servico_realizado.fabrica = {$login_fabrica}
											 WHERE tbl_os_produto.os = {$os}";
								$resPecas = pg_query($con, $sqlPecas);

								if (pg_num_rows($res) > 0) {
									while ($peca = pg_fetch_object($resPecas)) {
										$pecas[] = $peca->peca." / ".$peca->servico;
									}

									$pecas = implode("<br />", $pecas);
								}

								echo "
								<tr>
									<td>{$posto}</td>
									<td>{$data_abertura}</td>
									<td>{$tipo_atendimento}</td>
									<td>{$referencia} - {$descricao}</td>
									<td>{$horimetro}</td>
									<td>{$revisao}</td>
									<td>{$defeito_constatado}</td>
									<td>".((empty($pecas)) ? "" : $pecas)."</td>
								</tr>
								";
							}
							?>
							</tbody>
						</table>
					</div>
					<?php
				} else {
					echo "<br /><br /><br /><div class='alert alert-danger' >Produto sem Historico</div>";
				}
			}
			?>
		</div>
	</body>
</html>
