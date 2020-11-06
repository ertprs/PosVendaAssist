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
			if (strtolower(substr($file, -3)) != 'jpg') continue; // Pula o que não é JPG pela extensão

			$log_header = ++$nitens . ": filename: $file "; // Para não repetir tantas vezes...
			list($width, $height, $imgfmt) = getimagesize($file); // Largura, altura e formato da imagem

			if ($imgfmt != IMAGETYPE_JPEG) { // Pula o que não é realmente um JPG...
				print_log ("$log_header: O arquivo não é uma imagem JPG válida!\n", 'err');
				print("$log_header: O arquivo não é uma imagem JPG válida!\n");
				rename($file, NF_BASE_DIR . "/nf_bkp/$file");
				continue; // Pula tudo que não seja jpg...
			}

			$Mpx = round($width*$height/1e6, 2);
			echo "$log_header: WxH: $width x $height ({$Mpx}Mpx) \n";

			/* Processa o nome do arquivo, se tem prefixo de revenda, separa o nº de sua_os e o sufixo e extensão
			*  (r_)?		- prefixo de revenda
			*  (\d{6,8})		- nº de sua_os
			*  [()0-9]{0,3}		- para arquivos que tem (1) (2) (4) ... procura, mas ignora
			*  ([_]thumb-?\d?|-\d)? - sufixo de thumbnail ou de segunda imagem (ou os dois...)
			*  (\.jpg)$		- extensão do arquivo, têm que ser os últimos caracteres
			*/
			preg_match('/(r_)?(\d{6,8})[()0-9]{0,3}([_]thumb-?\d?|-\d)?(\.jpg)$/', $file, $fileinfo);
			if (count($fileinfo) == 0) {
				print_log("$log_header: Formato do nome do arquivo não confere!", 'err');
				continue;
			}
			list ($match, $rev, $num_os, $suffix, $ext) = $fileinfo;
			$subdir   = dirNF($rev . $num_os);
			$dest_file = "$subdir/$file";

			if ($subdir == 8) { // Erro ao recuperar as informações...
				print_log("$log_header: " . ++$dbAccessError . "º erro $subdir ao tentar descobrir o diretório do arquivo!\n", 'err');
				if ($dbAccessError >= 5) {
					print_log("Este é o $dbAccessErrorº erro de acesso ao banco de dados. Abortando processo.\n", 'err');
					print_log("Este é o $dbAccessErrorº erro de acesso ao banco de dados. Abortando processo.\n");
					closedir($dir);
					exit();
				}
			}

			if ($subdir == 4) { // A não pertence a uma OS
				$err = "$log_header: Esta OS não pertence a nenhuma OS. Movendo arquivo para dir. de backup.\n";
				print_log($err, 'err');
				echo $err;
				rename($file, NF_BASE_DIR . '/nf_bkp/'.$file);
				continue;
			}

			if (substr($subdir, 0, 2) == '00') { // A imagem pertence a uma OS excluída (fábrica 00)
				$err = "$log_header: Esta OS foi excluída (fábrica 0). Movendo arquivo para dir. de backup.\n";
				print_log($err, 'err');
				echo $err;
				rename($file, NF_BASE_DIR . '/nf_bkp/'.$file);
				continue;
			}
/*
			echo <<<InfoArquivo
	Revenda:  $rev
	Nº de OS: $num_os
	Sufixo:   $suffix
	Extensão: $ext
	Destino:  $subdir

InfoArquivo;
*/
			if (!is_dir($subdir)) mkdir($subdir); // Cria o diretório de destino se não existir

			if (file_exists($dest_file) and ($Mpx < 3 or filesize($file) > filesize($dest_file))) { // Se já existe e é menor, move pro backup
				echo "\tO arquivo $file já existe!\n";
				if (rename($file, NF_BASE_DIR . "/nf_bkp/$file")) {
					print_log("$log_header: Imagem já existe no destino ($subdir). Movendo para o backup");
				} else {
					print_log("$log_header: Imagem já existe no destino ($subdir), mas não foi possível mové-la para o backup", 'err');
				}
				continue;
			}

			if ($width <= 1024 and $height <= 1024 /* or ($width * $height) > 5500000*/) {
				rename($file, $dest_file);
				print_log("$log_header: Imagem de {$Mpx}Mpx, apenas mover", 'log');
				echo ("$log_header: Imagem de {$Mpx}Mpx, apenas mover\n");
				continue;     // Pula as imagens menores de 1024*1024
			}

			if ($Mpx > 5.5) { // Warning de imagem muito grande
				echo "$log_header: Imagem de {$Mpx}Mpx ($width x $height)!! \n";
				print_log("$log_header: Imagem de {$Mpx}Mpx!!", 'err');
			}

			if (reduz_imagem($file, 1024, 1024, $dest_file)) {
				print_log("$log_header: ({$Mpx}Mpx): Imagem alterada com sucesso", 'log');
				echo (unlink($file)) ? 'Moveu! - ' : 'Não moveu! - ';
				echo "Imagem redimensionada OK!\n";
			} else {
				print_log("$log_header: ($width x $height), erro ao redimensionar", 'err');
				echo "Erro ao tratar a imagem!\n";
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
