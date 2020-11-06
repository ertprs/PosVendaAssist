<?php
//define("NF_BASE_URL", "/assist/nf_digitalizada/");
//define("NF_BASE_DIR", "/var/www/assist/www/nf_digitalizada/");
//define("NF_DEST_DIR", "/var/www/assist/www/nf_digitalizada/processadas/");
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

include_once 'anexaNF_inc.php'; //Usa as constantes e a função dirNF(num_os) para obter o destino

if (!function_exists('reduz_imagem')) {
	function reduz_imagem($img, $max_x, $max_y, $nome_foto) {
		list($original_x, $original_y) = getimagesize($img);

		$porcentagem = ($original_x > $original_y) ? (100 * $max_x) / $original_x : (100 * $max_y) / $original_y;

		$tamanho_x = $original_x * ($porcentagem / 100);
		$tamanho_y = $original_y * ($porcentagem / 100);

		if ($original_x < $max_x and $original_y < $max_y) { //Se a imagem é menor que os máximos...
			return;
		} else {
			$image_p = imagecreatetruecolor($tamanho_x, $tamanho_y);
			$image   = imagecreatefromjpeg($img);
			imagecopyresampled($image_p, $image, 0, 0, 0, 0, $tamanho_x, $tamanho_y, $original_x, $original_y);
			return imagejpeg($image_p, $nome_foto, 75);
		}
	}
}
function print_log($texto, $tipo='log') {
	$tipo = substr($tipo, 0, 3);
	if (substr($texto, -1) != "\n") $texto .= "\n";
	return file_put_contents("redim.$tipo", $texto, FILE_APPEND);
}

$curdir = getcwd();
chdir (NF_BASE_DIR); // Vai pra pasta
$nitens = 0;

if (is_dir(NF_BASE_DIR)) {
	if (!is_dir(NF_BASE_DIR . "/nf_bkp")) mkdir(NF_BASE_DIR . "/nf_bkp"); // Cria um diretório de backup para as imagens não processadas

	if ($dir = opendir(NF_BASE_DIR)) {
		// Abre e inicializa os arquivos de log e de erro
		print_log("\n----------\n" . date('Y-m-d'). "  - LOG\n----\n", 'log');
		print_log("\n----------\n" . date('Y-m-d'). "  - ERROR LOG\n----\n", 'err');

		while (($file = readdir($dir)) !== false) {
			if (is_dir($file)) continue; // Pula os diretórios
			if (strtolower(substr($file, -3)) != 'jpg') continue;

			$log_header = ++$nitens . ": filename: $file "; // Para não repetir tantas vezes...
			list($width, $height, $imgfmt) = getimagesize($file); // Largura, altura e formato da imagem

			if ($imgfmt != IMAGETYPE_JPEG) continue; // Pula tudo que não seja jpg...

			$Mpx = round($width*$height/1000000, 2);
			echo "$log_header: WxH: $width x $height ({$Mpx}Mpx) \n";

			preg_match('/(r_)?(\d{6,8})[()0-9]{0,3}([_]thumb-?\d?|-\d)?(\.jpg)$/', $file, $fileinfo);
			if (count($fileinfo) == 0) {
				print_log("$log_header: Formato do nome do arquivo não confere!", 'err');
				continue;
			}
			list ($match, $rev, $num_os, $suffix, $ext) = $fileinfo;
if ($rev != '') {
			$subdir   = dirNF($rev . $num_os);
			$dest_file = NF_DEST_DIR . "/$subdir/$file";

			echo <<<InfoArquivo
	Revenda:  $rev
	Nº de OS: $num_os
	Sufixo:   $suffix
	Extensão: $ext
	Destino:  $subdir

InfoArquivo;
}
			if ($nitens > 500) {
				break;
			}
		}
		closedir($dir);
	}
}

chdir ($curdir);
/* LOG
..
57220: filename: 13543483.jpg : WxH: 462 x 540

real    392m11.716s
user    278m28.632s
sys     4m51.090s

=> SELECT interval '392m11.716s';
interval
--------------
06:32:11.716
*/
?>
