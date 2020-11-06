<?
	include 'dbconfig.php';

	$dbhost = 'homer.telecontrol.com.br';
	$dbnome = 'telecontrol_testes';

	include 'includes/dbconnect-inc.php';
	include 'autentica_usuario.php';
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
	<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
	<script src="admin/js/jquery-1.8.3.min.js"></script>
		<script type="text/javascript">

			/*
			$('#triangle').hover(
			    function() {
				$('#marcador').css({'visibility' : 'visible'});
			},
			function() {
				$('#marcador').css({'visibility' : 'hidden'});
			});
			

			function mostraPosicao(posicao, top, left, bottom, right, tamanho){
				//alert("Posicao: "+posicao+"\nLeftTop: "+left+","+top);

				$("#marcador").css("display","block");
				$("#marcador").css("top",top+"px");
				$("#marcador").css("left",left+"px");
				//$("#marcador").css("width",tamanho+"%");
			}
			*/

			function mostraPosicao(posicao, t, l, b, r, tamanho){
				if(posicao){
					// showing div in position
					$("#marcador").css({top:t+'px', left:l+'px', opacity:1}).show().click(function (){
						//window.open("Teste");
						window.alert(posicao);
					});
				} else {
					// hiding this div.
					$("#marcador").hide().unbind('click');
				}
			}
		</script>
		<style type="text/css">
			body {
				font: 80% Verdana,Arial,sans-serif;
				background: #FFF;
				margin: 0;
			}

			#content{
				margin: 0 auto;
				width: 1024px;
				border: 1px solid #000;
				position: relative;
			}

			#desenho{
				width: 200px;
				margin-left: 20px;
			}
			
			area{
				border: 1px solid #F00;
			}

			map{
				margin: 0;
				padding: 0;
			}

			#desenho_mapeado{
				position: absolute;
				top: 0;
				left: 0;
			}

			#marcador{
				position: absolute;
				top: 0;
				left: 0;
				display: none;
			}
		</style>
	</head>

	<body>

		<?php
			$sql = "SELECT DISTINCT desenho FROM tmp_jacto_peca_mapa LIMIT 100;";
			$res = pg_exec ($con,$sql);

			if($_POST['tamanho'])
				$tamanho = $_POST['tamanho'];
			else
				$tamanho = 100;

			if (@pg_numrows ($res) > 0) {
				echo "<form action='' method='POST' name='desenho_mapa'>";
					echo "Desenho: ";
					echo "<select id='desenho' name='desenho'>";
						for ( $i = 0 ; $i < pg_numrows ($res) ; $i++ ) {
							$desenho       = trim(pg_result($res,$i,desenho));
							 $selected = $desenho == $_POST['desenho'] ? 'selected' : '';
							echo "<option value='$desenho'  $selected>$desenho</option>";
							
						}
					echo "</select>";
					echo "<input type='text' name='tamanho' value='$tamanho' />";
					echo "<input type='submit' name='btnacao' value=' Ver ' />";
				echo "</form>";
			}

			if(!isset($_POST['desenho']))
				$desenho = "CD-363";
			else{
				$desenho = $_POST['desenho'];

			}

			$url_desenho = "jacto/imagens/$desenho.JPG";
			list($width, $height, $type, $attr) = getimagesize($url_desenho);
			$width = round(($width*$tamanho)/100);

		?>

		<div id='content'>
			
			<div id="container">
				<img src="<?php echo $url_desenho;?>" usemap="#map_desenho" id='desenho_mapeado' width='<?echo $width?>px'>

				<?php
					if(isset($desenho)){
						echo $sql = "SELECT 
									posicao, 
									(($tamanho/100::float)*REPLACE(p_left,'.','')::float) as p_left, 
									(($tamanho/100::float)*REPLACE(p_top,'.','')::float) as p_top, 
									(($tamanho/100::float)*REPLACE(p_right,'.','')::float) as p_right, 
									(($tamanho/100::float)*REPLACE(p_bottom,'.','')::float) as p_bottom
								FROM 
									tmp_jacto_peca_mapa 
								WHERE desenho = '$desenho';";
						$res = pg_exec ($con,$sql);

						if (@pg_numrows ($res) > 0) {
							echo "<map name='map_desenho'>";
								for ( $i = 0 ; $i < pg_numrows ($res) ; $i++ ) {
									$left		= trim(pg_result($res,$i,p_left));
									$top		= trim(pg_result($res,$i,p_top));
									$right		= trim(pg_result($res,$i,p_right));
									$bottom	= trim(pg_result($res,$i,p_bottom));
									$posicao	= trim(pg_result($res,$i,posicao));

									echo "<area shape='rect' href='javascript: void(0);' coords='$left, $top,$right, $bottom' class='$posicao' onmouseover='javascript: mostraPosicao($posicao, $top, $left, $bottom, $right, $tamanho);' />";
									
									if($i == 1)
										$width_marcador = $right - $left;
								}

							echo "</map>";
						}
					}
					
					
					$img_w = ($imnfo[1]/100)*$tamanho;
				?>
		
				<img src='imagens/bg_red_mapa_imagem_jacto.png' id='marcador' width='<?echo $width_marcador?>px' />
				<div style='clear: both; padding-top: 1600px;'>&nbsp; <?php echo $sql;?></div>
			</div>
			
		</div>
	</body>


