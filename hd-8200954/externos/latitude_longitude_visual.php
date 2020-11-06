<!DOCTYPE html />

<html>

	<?php

	/*
	Rotina: Atualiza localização dos Postos - Latitude e Longitude
	*/

	include dirname(__FILE__) . '/../dbconfig.php';
	include dirname(__FILE__) . '/../includes/dbconnect-inc.php';

	if(isset($_POST['fabrica'])){

		$fabrica     = $_POST['fabrica'];

		/* Não atualizar os postos da Saint-Gobain, pois foi atualizado manualmente */
		if($fabrica == 125){
			echo "<h1 style='color: red;'>Esta Fábrica foi atualizada manualmente...</h1>";
			exit;
		}

		if(!file_exists("localizacao/$fabrica.txt")){

			system("php latitude_longitude_visual2.php $fabrica", $ret);

			echo "<a href='localizacao/$fabrica.txt' target='_blank'>Arquivo Download</a>";

		}else{

			echo "<em style='color: red;'>Processo de atualização já realizado para está Fábrica!</em> <br /> <br />";

		}

		exit;

	}

	?>

	<head>
		<title>Localização de Postos</title>

		<script src="js/jquery.min.js"></script>

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

			$(document).ready(function(){
				
				$('#btn_acao').click(function(){

					var fabrica = $('select[name=fabrica]').val();
					
					$.ajax({
						url: "<?php echo $_SERVER['PHP_SELF']; ?>",
						type: "POST",
						data: {
							fabrica : fabrica	
						},
						beforeSend: function(){
							$('.result').html('<img src="imagens/loading.gif" /> <em>buscando postos! por favor aguarde...</em>');
						},
						complete: function(data){
							$('.result').html('');
							data = data.responseText;
							$('.result').html(data);
						}
					});

				});
				
			});
			
		</script>

	</head>

	<body>

		<h1>Localização de Postos</h1>

		<div>

			<strong>Fábrica</strong> <br />
			<select name="fabrica">
				<option value=""></option>
				<?php
					$sql = "SELECT fabrica, nome FROM tbl_fabrica WHERE ativo_fabrica IS TRUE ORDER BY nome";
					$res = pg_query($con, $sql);

					if(pg_num_rows($res) > 0){
						while($fabrica = pg_fetch_object($res)){
							$dados .= $fabrica->nome." - ".$fabrica->fabrica."<br />";
							$selected = (isset($_POST['fabrica']) && $_POST['fabrica'] == $fabrica->fabrica) ? "selected" : "";
							echo "<option value='".$fabrica->fabrica."' ".$selected." > ".$fabrica->nome." - ".$fabrica->fabrica."</option>";
						}
					}
				?>
			</select>

			<br /> <br />

			<input type="submit" value="Localizar Postos" id="btn_acao" />

		</div>

		<br />

		<div class="result"> </div>

	</body>

</html>
