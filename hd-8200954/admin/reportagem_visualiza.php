<?php

require_once 'dbconfig.php';
require_once 'includes/dbconnect-inc.php';

$titulo = "Reportagem";
$msg_erro = array();
$n_imagens = 0;
$campos_telecontrol = array();

$reportagem = strlen($reportagem) == 0 && isset($_GET["reportagem"]) ? $_GET["reportagem"] : $reportagem;
$reportagem = strlen($reportagem) == 0 && isset($_POST["reportagem"]) ? $_POST["reportagem"] : $reportagem;

echo "
<!DOCTYPE html PUBLIC '-//W3C//DTD XHTML 1.0 Transitional//EN' 'http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd'> <html xmlns='http://www.w3.org/1999/xhtml'>
<head>
<link href='reportagem_visualiza.css' rel='stylesheet' type='text/css'>
<link href='../js/adgallery/jquery.ad-gallery.css' rel='stylesheet' type='text/css'>

<script type='text/javascript' src='../js/jquery-1.3.2.js'></script>
<script type='text/javascript' src='../js/adgallery/jquery.ad-gallery.js'></script>
<script type='text/javascript' src='reportagem_visualiza.js'></script>
</head>
<body>
";


if (strlen($reportagem) > 0) {
	try {
		$reportagem = intval($reportagem);
		
		$sql = "
		SELECT
		*
		
		FROM
		tbl_reportagem
		
		WHERE
		reportagem={$reportagem}
		";
		@$res = pg_query($con, $sql);
		
		if (strlen(pg_last_error($con)) > 0) throw new Exception("<erro msg='".pg_last_error($con)."'>", 1);
		
		if (pg_num_rows($res) == 0) {
			unset($_POST["btn_acao"]);
			unset($reportagem);
			throw new Exception("Reportagem não encontrada");
		}
		
		$dados_reportagem = pg_fetch_array($res);
		
		$sql = "
		SELECT
		*
		
		FROM
		tbl_reportagem_foto
		
		WHERE
		reportagem={$reportagem}
		";
		$res = pg_query($con, $sql);
		$n_fotos = pg_num_rows($res);
		
		$galeria = array();
		
		for($i = 0; $i < $n_fotos; $i++) {
			$dados_foto = pg_fetch_array($res);
			$abre_center = "";
			$fecha_center = "";
			
			if ($dados_foto["posicao"] == "galeria") {
				$galeria[$i]["imagem"] = $dados_foto["imagem"];
				$galeria[$i]["legenda"] = $dados_foto["legenda"];
				$abre_center = "<center>";
				$fecha_center = "</center>";
			}
			
			if ($dados_foto["posicao"] == "centro") {
				$abre_center = "<center>";
				$fecha_center = "</center>";
			}
			
			$legenda = strlen($dados_foto["legenda"]) ? "<br><div class='legenda'>{$dados_foto["legenda"]}</div>" : "";
			$html = "{$abre_center}<table class='img_{$dados_foto["posicao"]} imagens'><tr><td><img src='{$dados_foto["imagem"]}' title='{$dados_foto["legenda"]}'>{$legenda}</td></tr></table>{$fecha_center}";
			$dados_reportagem["texto"] = str_replace("[{$dados_foto["reportagem_foto"]}]", $html, $dados_reportagem["texto"]);
		}
		
		if (count($galeria) > 0) {
			$html = "
			<center>
			<div id='gallery' class='ad-gallery'>
				<div class='ad-image-wrapper'></div>
				<div class='ad-nav'>
					<div class='ad-thumbs'>
						<ul class='ad-thumb-list'>";
				
			foreach($galeria as $indice => $foto) {
				$miniatura = explode(".", $foto["imagem"]);
				$miniatura[count($miniatura) -2] .= "_min";
				$miniatura = implode(".", $miniatura);
				$engana = time();
				
				$html .= "
				<li>
				<a href='{$foto["imagem"]}?{$engana}'>
				<img src='{$miniatura}?{$engana}' title='{$foto["legenda"]}'>
				</a>
				</li>
				";
			}

			$html .= "
						</ul>
					</div>
				</div>
			</div>
			</center>";
			
			$dados_reportagem["texto"] = str_ireplace("[galeria]", $html, $dados_reportagem["texto"]);
		}
		
		echo "
		<div class='principal'>
			<div class='titulo'>
			{$dados_reportagem["titulo"]}
			</div>";
			
		/* Exibição da data. O mês aparece em inglês não sei porque.
		setlocale(LC_TIME,"pt_BR");
		$data_completa = strftime("%B de %Y");
		echo $data_completa; */
		
		echo"
			<div class='texto'>
			{$dados_reportagem["texto"]}
			</div>
		</div>
		";
	}
	catch(Exception $e) {
		unset($_POST["btn_acao"]);
		$msg_erro[] = ($e->getCode() == 1 ? "Falha ao localizar informativo" : "") . $e->getMessage();
	}
}

if(is_array($msg_erro) > 0) {
	$msg_erro = implode('<br>', $msg_erro);
}

echo "<div id='msg_erro' name='msg_erro' class='msg_erro'>{$msg_erro}</div>";
 
echo "
</body>
</html>
";

 ?>