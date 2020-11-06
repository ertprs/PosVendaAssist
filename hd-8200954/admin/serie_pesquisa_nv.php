<?php

include "dbconfig.php";
include "includes/dbconnect-inc.php";
include 'autentica_admin.php';

$produto_serie = trim($_REQUEST['produto_serie']);

?>

<!DOCTYPE html>

<html>

	<head>

		<title>Pesquisa Série</title>
		<meta name="Author" content="">
		<meta name="Keywords" content="">
		<meta name="Description" content="">
		<meta http-equiv=pragma content=no-cache>

		<link rel="stylesheet" type="text/css" href="../css/lupas/lupas.css">
		<style>
			body 
			{
				margin: 0;
				font-family: Arial, Verdana, Times, Sans;
				background: #fff;
			}
		</style>

	</head>

	<body>

		<div class="lp_header">
			<img src='css/modal/excluir.png' alt='Fechar' class='lp_btn_fechar'  style="cursor: pointer;" onclick="window.parent.Shadowbox.close();" />
		</div>
		<div class='lp_nova_pesquisa'>
			<form action='<?=$_SERVER["PHP_SELF"]?>' method='POST' name='nova_pesquisa'>
				<table cellspacing='1' cellpadding='2' style="border: 0;">
					<tr>
						<td style='width: 50%;'>
							<span style="float: right;">
								<label>Série</label>
								<input type='text' name='produto_serie' value='<?=$produto_serie?>' style='width: 150px;' />
							</span>
						</td>
						<td colspan='2' class='btn_acao' style="vertical-align: bottom; text-align: left;">
							<input type='submit' name='btn_acao' value='Pesquisar' />
						</td>
					</tr>
				</table>
			</form>
		</div>

		<?php

		if (strlen($produto_serie) > 0)
		{
			echo "<div class='lp_pesquisando_por'>Buscando por número de série: $produto_serie</div>";

			$sql = "SELECT   
						tbl_produto.produto,
						tbl_produto.descricao,
						tbl_produto.referencia,
						tbl_numero_serie.serie
					FROM
						tbl_produto
					JOIN
						tbl_numero_serie 
						ON 
							tbl_produto.referencia = tbl_numero_serie.referencia_produto
					WHERE   
						tbl_numero_serie.serie ILIKE '%$produto_serie%'
						AND
							tbl_numero_serie.fabrica = $login_fabrica
					ORDER BY 
						tbl_produto.descricao;";
			$res = pg_query($con,$sql);

			if (pg_num_rows($res) > 0) 
			{
				echo "<table cellspacing='1' cellspading='0' class='lp_tabela' style='border: 0; width: 100%;'>
							<tr style='cursor: default;'>
								<td>
									Referência
								</td>
								<td>
									Descrição
								</td>
								<td>
									Série
								</td>
							</tr>";

				for ( $i = 0 ; $i < pg_num_rows($res) ; $i++ ) 
				{
					$produto    = trim(pg_result($res,$i,"produto"));
					$serie      = trim(pg_result($res,$i,"serie"));
					$descricao  = trim(pg_result($res,$i,"descricao"));
					$descricao  = str_replace('"','',$descricao);
					$descricao  = str_replace("'","",$descricao);
					$descricao  = str_replace("''","",$descricao);
					$referencia = trim(pg_result($res,$i,"referencia"));

					$cor = ($i % 2 <> 0) ? '#F7F5F0' : '#F1F4FA';

					$onclick = "window.parent.retorna_serie('$produto', '$descricao', '$referencia', '$serie'); window.parent.Shadowbox.close();";

					echo "<tr style='background-color: $cor' onclick=\"$onclick\">
							<td>
								$referencia
							</td>
							<td>
								$descricao
							</td>
							<td>
								$serie
							</td>
						</tr>";
				}

				echo "</table>";
			}
			else
			{
				echo "<div class='lp_msg_erro'>'$produto_serie' não encontrado</div>";
			}
		}
		else
		{
			echo "<div class='lp_msg_erro'>Informar toda ou parte da informação para realizar a pesquisa!</div>";
		}

		?>

	</body>

</html>