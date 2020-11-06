
<?php

	include '../admin/dbconfig.php';
	include '../admin/includes/dbconnect-inc.php';
	include '../admin/funcoes.php';

	function latLonPrecision($l){
		if(strlen($l) > 14){
			$l = substr($l, 0, strlen($l) - 5);
		}
		return $l;
	}

	if(isset($_POST['posto'])){

		$posto 		= $_POST['posto'];
		$fabrica 	= $_POST['fabrica'];
		$lat 	= latLonPrecision($_POST['lat']);
		$lon 	= latLonPrecision($_POST['lon']);

		$sql = "UPDATE tbl_posto SET longitude = '{$lat}', latitude = '{$lon}' WHERE posto = {$posto}";
		$res = pg_query($con, $sql);

		$sql = "UPDATE tbl_posto_fabrica SET latitude = '{$lat}', longitude = '{$lon}' WHERE posto = {$posto} AND fabrica = {$fabrica}";
		$res = pg_query($con, $sql);

		if(pg_affected_rows($res)){
			echo "Atualizado com Sucesso";
		}else{
			echo pg_last_error();
			echo "Erro ao atualizar";
		}

		exit;

	}
$sqlInsertLog = "INSERT INTO tbl_log_conexao(programa) VALUES ('$PHP_SELF')";
$resInsertLog = pg_query($con, $sqlInsertLog);
?>

<!DOCTYPE html />

	<head>
		<title>Localização de Postos</title>

		<script src="../admin/plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>

		<style>
			html{
				font: 15px arial;
				color: #333;
			}
			table{
				font: 12px arial;
				border: 1px solid #CCC;
				width: 100%;
			}
			table tr th{
				background-color: #cecece;
			}
		</style>

		<script>

			function atualizaLatLonPosto(posto){

				var lat = $('input[name=latitude_'+posto+']').val();
				var lon = $('input[name=longitude_'+posto+']').val();

				$.ajax({
					url: "<?php  echo $_SERVER['PHP_SELF']; ?>",
					type: "POST",
					data: {
						posto   : posto,
						fabrica  : <?php echo $_POST["fabrica"]; ?>,
						lat 	: lat,
						lon     : lon
					},
					complete: function(data){
						data = data.responseText;
						alert(data);
					}
				});

			}

		</script>

	</head>

	<body>

		<h1>Localização de Postos</h1>

		<form method="POST" action="<?php echo $_SERVER['PHP_SELF']; ?>">

			<strong>Fábrica</strong> <br />
			<select name="fabrica">
				<option value=""></option>
				<?php
					$sql = "SELECT fabrica, nome FROM tbl_fabrica WHERE ativo_fabrica IS TRUE ORDER BY nome";
					$res = pg_query($con, $sql);

					if(pg_num_rows($res) > 0){
						while($fabrica = pg_fetch_object($res)){
							$selected = (isset($_POST['fabrica']) && $_POST['fabrica'] == $fabrica->fabrica) ? "selected" : "";
							echo "<option value='".$fabrica->fabrica."' ".$selected." > ".$fabrica->nome." - ".$fabrica->fabrica."</option>";
						}
					}
				?>
			</select>

			<br /> <br />

			<input type="submit" value="Listar Postos" />

		</form>

		<?php

		if(isset($_POST['fabrica'])){
			
			$fabrica = $_POST['fabrica'];

			if(strlen($fabrica) == 0){

				echo "<p style='color: #ff0000; text-align: center; background-color: #F8E0E0; padding-top: 10px; padding-bottom: 10px;'>Informe a Fábrica!</p>";

			}else{

				echo "<hr />";

				echo "
				<p>
					Site para comparação de endereços <a href='http://www.procriativo.com.br/include/app/google-longitude-latitude.php' target='_blank'> Latitude / Longitude</a>
				</p>
				";

				$sql = "
					SELECT
						tbl_posto.posto AS posto                              ,
						tbl_posto_fabrica.codigo_posto AS codigo              ,
						tbl_posto.nome                                        ,
						tbl_posto.nome_fantasia                               ,
						tbl_posto.cep                               		  ,
						tbl_posto_fabrica.longitude                ,
						tbl_posto_fabrica.latitude                ,
						tbl_posto_fabrica.contato_fone_comercial AS telefone  ,
						tbl_posto_fabrica.contato_email 		 AS email     ,
						tbl_posto_fabrica.contato_endereco       AS endereco  ,
						tbl_posto_fabrica.contato_numero         AS numero    ,
						tbl_posto_fabrica.contato_cidade      	 AS cidade    ,
						tbl_posto_fabrica.contato_estado      	 AS estado    ,
						tbl_posto_fabrica.contato_bairro      	 AS bairro
					FROM tbl_posto
					JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto AND tbl_posto_fabrica.fabrica = $fabrica 
					WHERE 
						tbl_posto_fabrica.credenciamento = 'CREDENCIADO'
						/*AND tbl_posto_fabrica.posto <> 6359*/
						AND tbl_posto_fabrica.divulgar_consumidor IS TRUE
					ORDER BY tbl_posto_fabrica.contato_cidade";

				$res = pg_query($con, $sql);

				echo pg_last_error();

				if(pg_num_rows($res) > 0){

					echo "<strong>Qtde Postos: </strong>".pg_num_rows($res)."<br /> <br />";

					echo "
					<table>
						<tr>
							<th>Nº</th>
							<th>ID Posto</th>
							<th>Código Posto</th>
							<th>Posto</th>
							<th>Endereço</th>
							<th>Lat / Long</th>
							<th>Ações</th>
						</tr>
					";

					$cont = 1;

					while($posto = pg_fetch_object($res)){

						$cod_posto = $posto->posto;

						$i = $cont++;

						$endereco = $posto->endereco." ".$posto->numero.", ".$posto->cidade.", ".$posto->estado;
						$cor = ($i%2 == 0) ? "#e6e6e6" : "#fff";

						echo "
						<tr bgcolor='$cor'>
							<td>$i</td>
							<td>$cod_posto</td>
							<td>".$posto->codigo."</td>
							<td>".$posto->nome." (".$posto->nome_fantasia.")</td>
							<td>".$endereco."</td>
							<td width='200px'><b>Latitude</b> <input type='text' name='latitude_$cod_posto' value='".$posto->latitude."' /> <b>Longitude</b> <input type='text' name='longitude_$cod_posto' value='".$posto->longitude."' /> <button type='button' onclick='atualizaLatLonPosto(\"$cod_posto\")'>Atualizar</button></td>
							<td width='120px'><a href='https://www.google.com/maps/place/".$posto->latitude."+".$posto->longitude."' target='_blank'>Ver no Mapa</a></td>
						</tr>
						";

					}
					// <td width='120px'><a href='https://maps.google.com/maps?z=15&q=".$posto->latitude."+".$posto->longitude."' target='_blank'>Ver no Mapa</a></td>

					echo "</table>";

				}

			}

		}

		?>

	</body>

</html>
