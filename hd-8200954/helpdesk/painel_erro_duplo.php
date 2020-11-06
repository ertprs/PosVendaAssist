<?php
	include 'dbconfig.php';
	include 'includes/dbconnect-inc.php';

	if($consulta_dados == "galeria"){
		if(strlen($_POST['pesquisa']) == 0)
			$pesquisa = "ChuckNorris";
		else
			$pesquisa = $_POST['pesquisa'];

		$url = "http://api.flickr.com/services/rest/?method=flickr.photos.search&api_key=949751b331dc1243b750eeb6e8bea493&tags=$pesquisa&per_page=5&page=1&format=";

		$rsp = file_get_contents($url);
		$rsp_obj = simplexml_load_string($rsp);
		$attr = $rsp_obj->attributes();

		if ($attr['stat'] == 'ok'){

			//sort($rsp_obj->photos->photo);

			/*
				http://farm{farm-id}.static.flickr.com/{server-id}/{id}_{secret}.jpg
				http://farm{farm-id}.static.flickr.com/{server-id}/{id}_{secret}_[mstzb].jpg
				http://farm{farm-id}.static.flickr.com/{server-id}/{id}_{o-secret}_o.(jpg|gif|png)

				s	quadrado pequeno 75x75
				t	miniatura, 100 no lado mais longo
				m	pequeno, 240 no lado mais longo
				-	médio, 500 no lado mais longo
				z	Médio 640, 640 no lado mais longo
				b	grande, 1024 no lado mais longo*
				o	imagem original, jpg, gif ou png, dependendo do formato de origem
			*/

			foreach($rsp_obj->photos->photo as $photo){ 
				$attr = $photo->attributes();
				$url = "http://farm{$attr['farm']}.static.flickr.com/{$attr['server']}/{$attr['id']}_{$attr['secret']}_z.jpg";
				echo "<li style='text-align: center; width: 100%; height: 100%'><img src='$url' style='display: block; margin: auto;' /></li>";
			}
		
		}else{
			echo "Falha na chamada!";
		}
		exit;
	}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<title>HD - Painel de Erros</title>
		<script src="http://code.jquery.com/jquery-latest.js"></script>
		<script type="text/javascript" src="../plugins/jquery/slideshow/jquery.cycle.all.js"></script>
		<script type="text/javascript">
			var pag;
			var posicao = 0;
			var galeria = 0;

			$(document).ready(function(){ 
				atualizaDados();
			});

			function atualizaDados(){
			//	console.log(pag);
				if (pag == 'painel_erro_geral.php') {

					pag = 'painel_erro_semanal2.php';
		
				} else if (pag == 'painel_erro_semanal2.php') {

					pag = 'painel_erro_geral.php';

				} else {
					pag = 'painel_erro_geral.php';
				}


				console.log(pag);

				$.ajax({
					url: pag,
					success: function(retorno){
						$('#content').html(retorno);


							setTimeout("atualizaDados()",18000);

						}
				});
			}
		</script>
		<style type="text/css">
			*{
				font-family: Verdana,Arial,sans-serif;
			}

			body, html{
				padding: 0;
				margin: 0;
				background: #FCFCFC;
			}

			#galeria{
				z-index: 100;
				width: 100%;
				height: 100%;
			//	background: #000 url('http://i1.peperonity.info/c/4DD967/538119/ssc3/home/084/aawarapan1/178_1848_tv_chuvisco.gif_320_320_256_9223372036854775000_0_1_0.gif');
				text-align:center;
				position: absolute;
				margin: 0;
				padding: 0;
				display: none;
			}

			#galeria ul, #galeria li{
				margin: 0 auto;
				padding: 0;
				text-align: center !important;
				list-style: none;
			}

			#galeria li img{
				margin: auto;
			}
		</style>
	</head>

	<body>
		<div id='galeria'>
			<ul id='slideshow'>
			
			</ul>
		</div>
		<div id='content'></div>
	</body>
</html>
