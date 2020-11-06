<?php
if (!defined('BI_BACK')) define('BI_BACK', '');
define ('adminBACK', (preg_match("/admin|mlg|helpdesk/", $_SERVER['PHP_SELF'])) ? '../' : '');
define('LOCAL_PATH', __DIR__ . DIRECTORY_SEPARATOR);

//Includes
if (!defined('S3TEST')) {
	include_once LOCAL_PATH . "class/aws/s3_config.php";
}
if (!function_exists('traduz')) {
	include_once LOCAL_PATH . 'fn_traducao.php';
}
include_once LOCAL_PATH . 'helpdesk/mlg_funciones.php';

// Mensagens de erro
$msgs_erro = array(
	0 => 'Arquivo %s anexado com sucesso à OS!',
	1 => 'Arquivo não recebido',  //  (na verdade, o 1º parâmetro é um Array vazio)
	2 => 'Arquivo muito grande.', //  (Apache rejeitou)
	3 => 'O arquivo %s deve ser uma imagem JPG, GIF, PNG ou XML, PDF, ODT ou DOC(x)',
	4 => 'OS não existe', // (ou não é deste Fabricante ou Posto, tanto faz)
	5 => 'OS já tem NF anexada',  // (ou duas, caso aceite 2 imagens por OS)
	6 => 'Erro ao gravar o arquivo no servidor. Por favor, tente novamente.<br>' .
		 'Se persistir o erro, contate com o nosso <a href="mailto:helpdesk@telecontrol.com.br">Suporte Técnico</a>.',
	7 => 'Data de abertura fornecida (%s) não confere com a data que consta na OS.',
	8 => 'Há um problema de acesso ao sistema. Por favor, tente novamente em alguns instantes.',
	9 => 'Tamanho em pixels do arquivo muito grande (%d x %d).<br>'.
		 'Por favor, reduza ou recorte a imagem antes de enviá-la de novo.<br>Obrigado.',
	10=> 'Esta OS já tem %s arquivos anexados.',
	11=> 'Já existe uma imagem para esta OS. Pode anexar mais uma.',
	12=> 'Já existe um anexo para esta OS. Exclua o existente para anexar um novo.',
	13=> 'Erro de conexão com o depósito de arquivos.',
	14=> 'Erro ao copiar a imagem ao depósito de arquivos.'
);

//  Configuração do programa
$nf_config = array(
	"max_filesize" => 999999*999999,
	'max_img_size' => 999999*999999, /*HD-3980490 Retirado o limite de tamanho dos arquivos.*/
	"mime_type"    => 'jpg|jpeg|gif|pjpeg|xml|pdf|png|doc|docx|odt|msword',
	'botao_nf'     => 'imagens/btn_notafiscal.gif',
	'nf_excui_ico' => 'imagens/delete_2.gif',
	'inputTitle_1' => array(
		'pt-br' => 'Inserir a imagem digitalizada da Nota Fiscal, formatos ',
		'es'    => 'Seleccione un archivo con la factura digitalizada, en formato ',
		'en'    => 'Select a file with a scanned version of the Invoice, format '
	),
	'inputTitle_2' => array(
		'pt-br' => ' Formatos válidos JPG, PNG, PDF, XML e DOC.',
		'es'    => ' Formatos válidos JPG, PNG, PDF, XML e DOC.',
		'en'    => ' Accepted formats JPG, PNG, PDF, XML or DOC'
	),
);

// Tipos de arquivos aceitos, e o tipo MIME para validação.
$mimeTypes = array(
	'jpg'  => 'image/jpeg',
	'jpeg' => 'image/jpeg',
	'gif'  => 'image/gif',
	'png'  => 'image/png',
	'pdf'  => 'application/pdf',
	'xml'  => 'application/xml',
	'doc'  => 'application/msword',
	'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
);

//	Define parâmetros para a tabela de anexos, com ou sem linha para exclusão
	$attTblAttrs = array(
		'tableAttrs'  => " id='anexos' class='tabela' align='center'",
		'headerAttrs' => ' class="Tabela inicio" ',
		'rowAttrs'    => ' class="conteudo"',
		'cellAttrs'   => " style='vertical-align:middle;text-align:center'"
	);

/**
 * r	OS Revenda
 * s	OS Sedex
 * e	Extrato
 *
 * Para adicionar um novo tipo, simplesmente "configurar" ele no array a_tiposAnexo
 **/
if (!empty($limite_anexos_nf)) {
    define('LIMITE_ANEXOS', $limite_anexos_nf);
} else {
    define('LIMITE_ANEXOS', 30); // Total de anexos por objeto. Extratos não tem limite, fixo dentro da função anexaNF().
}

$a_tiposAnexo = array(
	'r' => array(
		'tbl_r'     => 'os_revenda',
		'campoData' => 'digitacao',
		'tipoAnexo' => 'OS Revenda',
		'tipoTDocs' => 'osrevenda',
		'campo'     => 'os_revenda',
		'progAnexo' => 'os_revenda_finalizada.php'
	),
	's' => array(
		'tbl_r'     => 'os_sedex',
		'campoData' => 'data_digitacao',
		'tipoAnexo' => 'OS Sedex',
		'tipoTDocs' => 'ossedex',
		'campo'     => 'os_sedex',
		'progAnexo' => 'sedex_consulta.php'
	),
	'e' => array(
		'tbl_r'     => 'extrato',
		'campoData' => 'data_geracao',
		'tipoAnexo' => 'Extrato',
		'tipoTDocs' => 'osextrato',
		'campo'     => 'extrato',
		'progAnexo' => 'os_extrato_detalhe.php'
	),
	'o' => array(
		'tbl_r'     => 'os',
		'campoData' => 'data_digitacao',
		'tipoAnexo' => 'OS Consumidor',
		'tipoTDocs' => 'os',
		'campo'     => 'os',
		'progAnexo' => 'os_press.php'
	),
);
define('TIPOS_ANEXO', implode('',array_keys($a_tiposAnexo)));

/***********************************************************************************************************************
 * Configuração - configuração por fabricante. Tem também uma configuração padrão para poder usar                      *
 * mesmo que não seja definido um fabricante...                                                                        *
 * A configuração feita para cada fabricante deverá ser ser feita via Banco de Dados na tbl_fabrica_parametros_nf      *
 * a coluna max_size do vetor será composta pelas colunas max_size_width e max_size_height do Banco de Dados,          *
 * os resultados da consulta à essa tabela irá alimentar uma nova posição do vetor que irá conter as regras da Fábrica *
 ***********************************************************************************************************************/

// Recupera do banco a configuração do fabricante
$anexaNotaFiscal = false; // Até confirmar, a fábrica não está habilitada...

$sql_anf = "SELECT anexa_duas_fotos, reduz_imagens,
                   criar_thumbnail,
                   max_size_width,
                   max_size_height,
                   nf_obrigatoria,
                   qtde_anexo
              FROM tbl_fabrica_parametros_nf
             WHERE fabrica = $login_fabrica";
$res_anf = pg_query($con, $sql_anf);

if (is_resource($res_anf)) {
	// Se tiver resultados, a fábrica está habilitada para anexar imagens à NF.
	// Extrai então as variáveis do banco de dados.
	//echo "<br />Resource: $res_anf<br />\n";

	if (pg_num_rows($res_anf) > 0) {
		$anexaNotaFiscal = true;

		$max_size_width  = pg_fetch_result($res_anf, 0, 'max_size_width');
		$max_size_height = pg_fetch_result($res_anf, 0, 'max_size_height');
		$qtde_anexo = pg_fetch_result($res_anf, 0, 'qtde_anexo');

		$fabricas_anexam_NF[$login_fabrica] = array(
			'anexa_duas_fotos' => (pg_fetch_result($res_anf, 0, 'anexa_duas_fotos') == 't'),
			'reduz_imagens'    => (pg_fetch_result($res_anf, 0, 'reduz_imagens')    == 't'),
			'criar_thumbnail'  => (pg_fetch_result($res_anf, 0, 'criar_thumbnail')  == 't'),
			'nf_obrigatoria'   => (pg_fetch_result($res_anf, 0, 'nf_obrigatoria')   == 't'),
			'max_size'         => array(999999*999999)
		);
		//$fabricas_anexam_NF[$login_fabrica]['reduz_imagens']   = false;
		$fabricas_anexam_NF[$login_fabrica]['criar_thumbnail'] = false;
	}
} else {
	die("Erro de conexão! ". pg_last_error($con));
}
$fabricas_anexam_NF['padrao'] = array(
	'anexa_duas_fotos' => false,             // Permite anexar duas imagens à OS
	'reduz_imagens'    => true,              // As imagens são reduzidas para ter no máximo a largura/altura max_size
	'criar_thumbnail'  => false,             // Se vai ou não criar uma imagem pequena (150x135 máx)
	'max_size'         => array(999999*999999), // Largura ou altura máxima /*HD-3980490 Retirado o limite de tamanho do arquivo.*/
	'nf_obrigatoria'   => false              // Se é obrigatório anexar a NF.
);

//  Funções auxiliares
if (!function_exists('cmpFileName')) {
	function cmpFileName($a,$b) {
		$pa = pathinfo($a, PATHINFO_FILENAME);
		$pb = pathinfo($b, PATHINFO_FILENAME);
		return ($pa == $pb) ? 0 : ($pa < $pb) ? -1 : 1;
	}
}

if (!function_exists('reduz_imagem')) {
	function open_image($file) {
		global $imgType;
		$img_data = getimagesize($file);
		switch($img_data["mime"]){
			case "image/jpeg":
				$im = imagecreatefromjpeg($file); //jpeg file
				$imgType = 'jpg';
				break;
			case "image/gif":
				$im = imagecreatefromgif($file); //gif file
				$imgType = 'gif';
				break;
			case "image/png":
				$im = imagecreatefrompng($file); //png file
				$imgType = 'png';
				break;
			case "image/bmp":
				$im = imagecreatefromwbmp($file); //png file
				$imgType = 'bmp';
				break;
			default:
				$im=false;
				break;
			}
		return $im;
	}

	function save_image($file, $dest_path, $format='jpeg') {
		// Grava a imagem no $dest_path, no formato $format
		switch($format){
		case "bmp":
		case "jpg":
		case "jpeg":
			$ret = imagejpeg($file, $dest_path, 80); // Salva em formato JPeG
			break;
		case "gif":
			$ret = imagegif($file, $dest_path); // Salva como GIF
			break;
		case "png":
			$ret = imagepng($file, $dest_path, 2); // Salva como PNG, compressão nível 2
			break;
		default:
			$ret = false;
			break;
		}
		return $ret;
	}

	function reduz_imagem($img, $max_x, $max_y, $nome_foto, $formato = 'jpg') {
		list($original_x, $original_y) = getimagesize($img);    //pega o tamanho da imagem

		// se a largura for maior que altura
		$porcentagem = ($original_x > $original_y) ? (100 * $max_x) / $original_x : $porcentagem = (100 * $max_y) / $original_y;

		$tamanho_x    = $original_x * ($porcentagem / 100);
		$tamanho_y    = $original_y * ($porcentagem / 100);
		$image_p    = imagecreatetruecolor($tamanho_x, $tamanho_y);
		$image		= open_image($img);

		imagecopyresampled($image_p, $image, 0, 0, 0, 0, $tamanho_x, $tamanho_y, $original_x, $original_y);
		return save_image($image_p, $nome_foto, $formato);
	}
}

	/**
	 *	e_OS_revenda($num_os) return bool
	 *  @param	$num_os		Número de OS a conferir
	 *  @return	mixed		ID OS Revenda se for, FALSE se é OS normal.
	 *
	 *	devolve o nº da OS Revenda se é OS Revenda e false se não é
	 */
	function e_OS_revenda($num_os) {
		global $con, $login_fabrica;

		// pre_echo(func_get_args(), 'e_OS_revenda params:');

		if (!is_numeric($num_os))
			$num_os = preg_replace('/\D/', $num_os);

		// Confere os_revenda...

		$sql = "SELECT os_revenda FROM tbl_os_revenda WHERE os_revenda = $num_os AND fabrica = $login_fabrica";
		$res = @pg_query($con, $sql);

		if (pg_num_rows($res) > 0) {
			$os_revenda = pg_fetch_result($res, 0, 0);
			return $os_revenda;
		}

		if ($login_fabrica == 1) {
			$cond_explodida = " AND tbl_os_revenda.explodida IS NULL ";
		}

		// Mesmo não marcada como os_revenda, confere no banco de dados... "vai que..."
		$sql = "SELECT os, os_revenda
				FROM tbl_os
				LEFT JOIN tbl_os_revenda ON (tbl_os_revenda.sua_os ~ tbl_os.os_numero::text
										AND tbl_os.fabrica = tbl_os_revenda.fabrica)
				WHERE tbl_os.fabrica = $login_fabrica
				   AND os			  = " . (int) $num_os . "
				   AND tbl_os.posto	  = tbl_os_revenda.posto
				   $cond_explodida";

		$res = @pg_query($con, $sql);

		if (!is_resource($res)) return "8. ".pg_last_error($con);

		if (pg_num_rows($res) > 0) {
			$os_revenda = pg_fetch_result($res, 0, 1);
		}

		return (!is_null($os_revenda)) ? $os_revenda : false;
	}

//  JS para mostrar a NF como 'popup' animado
$include_imgZoom = <<<JSZoom
    <script type='text/javascript' src='js/FancyZoom.js'></script>
    <script type='text/javascript' src='js/FancyZoomHTML.js'></script>
	<script type="text/javascript">
		setupZoom();
	</script>
JSZoom;
//  Elementos HTML para anexar NF (vale para todos os programas menos para a Intelbras)
$tiposAdmitidos = implode(',', $mimeTypes);
$lang = ($cook_idioma) ? $cook_idioma : 'pt-br';

$inputFileTitle = $nf_config['inputTitle_2'][$lang];

$inputAnexoLabel = traduz('Anexar NF legível');

if ($login_fabrica == 19) {

    $sqlLinha = "
        SELECT  linha
        FROM    tbl_os
        JOIN    tbl_produto USING(produto)
        WHERE   fabrica = $login_fabrica
        AND     os      = $os";
    $resLinha = pg_query($con,$sqlLinha);

    $linhaAnexo = pg_fetch_result($resLinha,0,linha);

    $inputAnexoLabel = (strstr($PHP_SELF,"os_item_") && in_array($linhaAnexo,array(265,928)))
        ? "Anexo (Para linhas LOUÇAS e AQUECEDORES a Gás. anexar RVT)"
        : "Anexar NF legível";

    $bold = (strstr($PHP_SELF,"os_item_") && in_array($linhaAnexo,array(265,928)))
        ? ";font-weight:bold;font-size:14px;"
        : "";
}

$inputNotaFiscal = "";

for($qtde = 0; $qtde < $qtde_anexo; $qtde++){
$inputNotaFiscal .= <<<NFInputHTML
        <label style='position:relative;top:-3px;left:-10px;font-size:10px;font-family:verdana,arial,helvetica,sans-serif$bold'
                title="$inputFileTitle">
            $inputAnexoLabel: </label><span style='color:red;font-weight:bold'
                                title='$inputFileTitle'><img src='imagens/help.png' /></span>
        <input type='file' name='foto_nf[]' class='frm' accept='$tiposAdmitidos' /><br>
NFInputHTML;

}

$inputNotaFiscal2 = <<<NFInputHTML
			<br />
			<input type='file' name='foto_nf_2' class='frm' accept='$tiposAdmitidos' title="$inputFileTitle" />
NFInputHTML;
$inputFileTitle = "<br />\n<p>$inputFileTitle</p>";

$anexaNotaFiscal = array_key_exists($login_fabrica, $fabricas_anexam_NF);

$anexa_duas_fotos = $fabricas_anexam_NF[$login_fabrica]['anexa_duas_fotos'];

require_once AWS_SDK;

require_once LOCAL_PATH . 'class/tdocs.class.php';
require_once LOCAL_PATH . 'anexaNF_S3.php';

