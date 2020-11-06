<?php
/*  Upload de Nota Fiscal
 *  ---------------------
 *  Função: anexar imagem da NF à OS
 *          excluir imagem anexada
 *          redimensionar as imagens
 *          Tratamento de erros
 *  Uso: dar include do arquivo e, no ponto certo, chamar a função anexaNF,
 *       passando como parâmetros o 'array' do INPUT e nº da OS.
 *  Ex.: anexaNF($_FILES['foto'], 11234567, '2010-05-01');
 *       Opcionalmente pode passar a data de abertura da OS. Se não vier, a
 *       função vai pegar do banco, passar a data evita uma nova consulta.
 *       Também pode passar a obrigatoriedade de anexar NF
 *  Retorna:
 *       String com código_de_erro|'Texto do erro'
*/

//define("NF_BASE_URL", "/assist/nf_digitalizada");
//define("NF_BASE_DIR", "/var/www/assist/www/nf_digitalizada");
define("NF_BASE_DIR", LOCAL_PATH . '/' . 'nf_digitalizada');
define("NF_BASE_URL",  'http://' . $_SERVER['HTTP_HOST'] . dirname(preg_replace("&admin(_cliente)?/&", '', $_SERVER['PHP_SELF'])) . '/' . 'nf_digitalizada');

/**
 *	dirNF($os)
 *
 *  Devolve o subdiretório on de está ou deverá estar a imagem.
 *  @params: $num_os, $devolve
 *  $num_os     o nº da OS
 *  $devolve    'dir' para devolver o path no sistema (padrão), ou 'url' para devolver o link
 *  O diretório é o NF_BASE_DIR mais o fabricante mais o mês e ano da data de abertura da OS.
 *  Exemplo: OS 12029473, com data de abertura 12/07/2010... Seria:
 *  O diretório seria 30/2010_07 ('30' da Colormaq, e 2010_07 pela data de abertura)
 *
 *  Atualização 30/01/2012:
 *  	O armazenamento foi para o AWS3, assim, o diretório de escrita será /tmp/nfupload,
 *  	e o de leitura será:
 *  	S3_BASE_URL/[fábrica 4 dígitos]/[ano]/[mês 2 dígitos]/[r]_[fábrica 4 dígitos]_[OS|OS Revenda][_9].jpg
 *
 *  	Com este esquema, a função dirNF será usada apenas para montar o link de leitura.
 *
*/
function dirNF($num_os, $devolver='dir') {
	global $con, $a_tiposAnexo;
	if (!$num_os or $num_os == 'r_') return false;

	//die($num_os);
	//2011-08-22 00:00:01 Usar data de digitação desde esta data!
	if (!is_numeric($num_os[0])) {
		$tipo = $num_os[0];
		if (strpos(TIPOS_ANEXO, $tipo)===false) return false;
		
		extract($a_tiposAnexo[$tipo]);
		$tipo .= '_';
	} else {
			$os_revenda = e_OS_revenda($num_os);

			if ($os_revenda !== false) {
			extract($a_tiposAnexo['r']);
			$tipo = 'r_';
		} else {
			extract($a_tiposAnexo['o']);
			}
	}
	$num_os	= preg_replace('/\D/', '', $num_os);

	$sql_os = "SELECT fabrica,
				      TO_CHAR($campoData, 'YYYY_MM') AS data_os
                 FROM tbl_$tbl_r
                WHERE $tbl_r = $num_os";
	//echo $sql_os."<br>";
	$res	= pg_query($con, $sql_os);

	if (!is_resource($res))		 return 8;
	if (@pg_num_rows($res) == 0) return 4;

	extract(pg_fetch_assoc($res, 0));

	$dir_nf_os = ($devolver  == 'dir') ? NF_BASE_DIR : NF_BASE_URL;
	$dir_nf_os.= '/'. str_pad($fabrica, 2, '0', STR_PAD_LEFT) .
				 '/'. $data_os;

	return $dir_nf_os;
}

/*  Funções */
/**
 *	temNF($num_os, $retorno, $num)
 *
 *	@param	$num_os		string	required	Núm de OS/Rev/Sedex/Extrato
 *	@param	$retorno	string	optional	Ver abaixo
 *	@param	$tipo		string	optional	Tipo de objeto para o anexo: OS Revenda, SEDEX, Extrato (ver abaixo)
 *	@return	mixed		mixed				Retorna NULL se não tiver imagem para a OS $num_os, 
 *											TRUE ou FALSE se o $retorno for 'bool' ou 
 *											URL/link/img da imagem, se tiver para o tipo de retorno solicitado.
 *	$retorno: 'bool'   retorna TRUE ou FALSE, se há ou não imagem da NF
 *			  'count'  retorna a quantidade de rquivos em anexo
 *			  'url'    retorna a URL (S3_BASE_URL . dirNF . $imagem)
 *			  'img'    retorna o HTML da só das imagens (<img src='$imagem' ... />)
 *			  'link'   devolve o HTML (<a href='$imagem'><img src='$imagem' />...) da NF (padrão)
 *			  'linkEx' como 'link', mas adiciona uma segunda TR com 'X' para excluir
 *	$tipo	: nº da imagem (1 .. 4) para as fábricas que aceitam várias imagens por OS
 **/
function temNF($num_os, $retorno = 'link', $tipo = '') {
	global $con, $msgs_erro, $fabricas_anexam_NF, $login_fabrica, $a_tiposAnexo, $attTblAttrs;
	$os = $num_os;

	if (!$num_os) return ($retorno == 'bool')? false : '';

	if (!is_numeric($num_os[0]))
		$tipo = $num_os[0];

	if ($tipo) {
		if (strpos(TIPOS_ANEXO, $tipo)===false) return false;
		
		extract($a_tiposAnexo[$tipo]);
		$tipo_anexo = $tipo . '_';
		
	} else {
		$os_revenda = e_OS_revenda($num_os);

		if ($os_revenda !== false) {
			extract($a_tiposAnexo['r']);
			$tipo_anexo = 'r_';
			$num_os = $os_revenda;
		} else {
			extract($a_tiposAnexo['o']);
		}
	}

	$num_os	    = preg_replace('/\D/', '', $num_os);

	//p_echo ("Teste. OS $num_os, tipo $tipo_anexo");

	$base_link	= "<a href='%s' target='_blank'>".
				  "<img src='%s' title='Para ver a imagem completa, clique com o botão direito e selecione \"Abrir link em uma nova janela\" ou \"Mostrar Imagem\"' ".
				  "style='_display:block;_zoom:1;max-height:150px;max-width:150px;_height:150px;*height:150px;' /></a>\n";

	$base_img   = "<img src='%s' />\n";

	// HD 712148 - O campo tbl_os.os_numero contém o campo tbl_os_revenda.sua_os quando é uma os_rev explodida,
	//				não serve par determinar o campo os_revenda... Agora, consulta direto no banco de dados.

	if (!array_key_exists($login_fabrica, $fabricas_anexam_NF)) {
		extract($fabricas_anexam_NF['padrao']);
	} else {
		extract($fabricas_anexam_NF[$login_fabrica]);
	}

//  Procura a imagem no diretório base/ano_mes
	$dir = dirNF($tipo_anexo . $num_os); //Devolve '/var/www.../nf_digitalizada/81/2012/04'
	$url = dirNF($tipo_anexo . $num_os, 'url');

	if (strpos($url, '192'))
		$url = str_replace('192.168.0.199', 'urano.telecontrol.com.br', $url);

	if (is_numeric($dir))
		return $dir;

	$temImagem    = false;
	$temThumbnail = false;

	$searchString = str_replace(LOCAL_PATH.'/', '', "$dir/$tipo_anexo$num_os");

	$curdir = getcwd();
	chdir(LOCAL_PATH);

	//echo "URL: $url";

	$temImagem = (count($arquivos = glob("$searchString*")));
	usort($arquivos, 'cmpFileName');

	chdir($curdir);

	// Para estes tipos de retorno, já temos informação suficiente
	if ($retorno == 'bool')  return ($temImagem > 0);
	if ($retorno == 'count') return $temImagem;

	if ($criar_thumbnail) {
		$temThumbnail =  count($thumbs = glob($searchString . "*_thumb*"));
		usort($thumbs, 'cmpFileName');
	}

	if (!$temThumbnail) $thumbs = $arquivos;

	//print_r($arquivos);

	$ret = false;

	if ($temImagem) {

		$pathImgIco = adminBACK . BI_BACK . 'imagens';

		foreach($arquivos as $idx => $anexo) {
			$imgUrl = $url . '/' . basename($anexo);

			$link = getAttachLink($imgUrl, '', true);
			$link['acao'] = str_replace('img/',     '', $link['acao']);
			$link['acao'] = str_replace('imagens/', '', $link['acao']);
			$link['ico']  = str_replace('imagens/', '', $link['ico']);

			if (strpos($link['ico'], 'image')!==false) {
				$imgs[]  = sprintf($base_img,  $imgUrl);
				$thumb = ($temThumbnail) ? $thumbs[$idx] : $imgUrl;
				$links[0]["anexo " . ++$idx] = sprintf($base_link, $imgUrl, $thumb);
			} else {
				$links[0]["anexo " . ++$idx] = str_replace('150', '96', 
												sprintf(nl2br($link['acao']), $imgUrl, 'Clique para visualizar', $imgUrl, "$pathImgIco/{$link['ico']}"));
			}

			if ($retorno == 'linkEx') {
				$links[1]["anexo_$idx"] = "<span><img src='$pathImgIco/cross.png' name='$anexo' alt='Excluir' title='Excluir Arquivo' class='excluir_NF' /></span>";
			}

			$paths[] = $imgUrl;
		}

		$links['attrs'] = $attTblAttrs;

		//print_r($links);
		//die();
		switch ($retorno) {
			case 'url':    $ret = array_filter($paths);     break;
			case 'path':   $ret = array_filter($arquivos);  break;
			case 'img':    $ret = implode('<br />', $imgs); break;
			case 'link':   $ret = array2table($links);      break;
			case 'linkEx': $ret = array2table($links);      break;
		}
	}
	//if ($retorno=='link') echo $ret;
	return $ret;
} // End function temNF()

/**
 *  anexaNF
 *  Recebe a imagem, trata ela e move para o destino
 *
 *  $num_os 		:   nº da OS. 'r_$os_revenda' para revenda explodida. 's_$os_sedex' para OS Sedex
 *  $nf_upload		:   array da imagem anexada ($_FILES['img_nf'], por exemplo)
 *  $nf_obrigatoria	: (opcional) TRUE para devolver erro se não há, independente da config. da fábrica.
 */
function anexaNF($num_os, $nf_upload, $nf_obrigatoria = null) { // BEGIN function anexaNF
	global $con, $fabricas_anexam_NF, $nf_config, $msgs_erro, $login_fabrica, $login_posto;

	// Exclui a variável se não foi passado o valor. Se a variável existir, não é 'pisada' pelo valor do array
	//p_echo ("Num. OS - ".$num_os);
	if (is_null($nf_obrigatoria)) 
		unset($nf_obrigatoria);

	if (!array_key_exists($login_fabrica, $fabricas_anexam_NF)) {
		extract($fabricas_anexam_NF['padrao'], EXTR_SKIP); // Extrai os valores, mas não altera o valor da $nf_obrigatoria se foi passado algum valor
	} else {
		extract($fabricas_anexam_NF[$login_fabrica], EXTR_SKIP); // Extrai os valores, mas não altera o valor da $nf_obrigatoria se foi passado algum valor
	}

	$prefixo_anexo = null;
	if ($nf_upload == null) $nf_upload = $_FILES['foto_nf'];

	// Antes era if is_uploaded_file(), mas então os anexos da Bosch Sec. (que vem do chamado) não entravam...
	$tem_anexo = file_exists($nf_upload['tmp_name']) && filesize($nf_upload['tmp_name'])>0;

	if (strpos('_' . TIPOS_ANEXO, $num_os[0]) > 0) {

		$prefixo_anexo = $num_os[0] . '_';
		$num_os = preg_replace('/\D/', '', $num_os);
		
	} else  {

		// Mesmo não marcada como os_revenda, confere no banco de dados... "vai que..."
		if ($os_revenda = e_OS_revenda($num_os)) {
			$prefixo_anexo = 'r_';
			$num_os	= $os_revenda;
		}

	}

	//pre_echo($nf_upload, "Anexo recebido para a OS $num_os");
	if (!$login_posto) {

		switch($prefixo_anexo){ 

			case '':   $tbl = 'tbl_os';         $campo = 'os';         break;
			case 'r_': $tbl = 'tbl_os_revenda'; $campo = 'os_revenda'; break;
			case 's_': $tbl = 'tbl_os_sedex';   $campo = 'os_sedex';   break;

		}

		$sql = "SELECT posto FROM $tbl WHERE $campo = $num_os AND fabrica = $login_fabrica";

		$res = pg_query($con,$sql);
		$posto = @pg_result($res,0,0);
	} else {
		$posto = $login_posto;
	}

	include_once 'regras/envioObrigatorioNF.php';

	// Corrigindo para quando nao for obrigatoria, retornar true
	if (!$tem_anexo) {
		if (!$nf_obrigatoria or !EnvioObrigatorioNF($login_fabrica, $posto,$prefixo_anexo . $num_os))
			return 0;

		return 1;
	}

	//pre_echo($nf_upload, "Anexo recebido para a OS $num_os");

	// Validando arquivo, tamanho, tipo, data, OS...
	if (!is_numeric($num_os))  return $msgs_erro[4];// Sem OS não pode gravar
	if (!is_array($nf_upload)) return $msgs_erro[1];// Os dados do arquivo tem que vir num array

	if (($nf_upload['name'] != '' and
		 $nf_upload['type'] == '' and
		 $nf_upload['size'] == 0)) return $msgs_erro[2]; /*HD-3980490 Retirado o limite do tamanho do arquivo*/

	$formato_OK = preg_match("/{$nf_config['mime_type']}/", $nf_upload['type']);

	if (!$formato_OK) return sprintf($msgs_erro[3], $nf_upload['name'] . "(formato {$nf_upload['type']})");    // Não é um formato aceito

	if (strpos($nf_upload['type'], 'image')) { // Caso seja uma imagem...
		$e_imagem = true;
		list($width, $height) = getimagesize($nf_upload['tmp_name']); /*HD-3980490 Retirado o limite do tamanho da imagem*/
	}

	//Validação diretório destino
	$target_dir = dirNF($prefixo_anexo . $num_os);
	//die($target_dir);

	if (is_int($target_dir))
		return $msgs_erro[$target_dir];

	$curdir = getcwd();
	chdir(LOCAL_PATH);

	$target_dir = str_replace(LOCAL_PATH.'/', '', $target_dir);

	// Cria o diretório se não existe...
	if (!is_dir($target_dir)) {
		mkdir($target_dir, 0777, true); // Cria o diretório...
	}

	if (!is_writable($target_dir)) {
		chdir($curdir);
		return "Permissão negada, contate com o Suporte Telecontrol.";
	}

	$extArquivo = strtolower(pathinfo($nf_upload['name'], PATHINFO_EXTENSION));

	$searchString = "$target_dir/$prefixo_anexo{$num_os}*";
	$temNFs = count($anxs = glob($searchString));

	if ($prefixo_anexo != 'e_' and $temNFs >= LIMITE_ANEXOS)
		return sprintf($msgs_erro[10], LIMITE_ANEXOS); // Ultrapassou o limite de anexos

	if ($temNFs) {
		unset($imagem_nota);

		foreach($anxs as $idx=>$anx) {
			$anxs[$idx] = "$target_dir/" . pathinfo($anx, PATHINFO_FILENAME);
		}

		for ($n = 1; $n <= LIMITE_ANEXOS; $n++) {
			$sufixo = ($n>1) ? "-$n" : '';
			if (!in_array("$target_dir/$prefixo_anexo$num_os$sufixo", $anxs)) {
				$imagem_nota = "$target_dir/$prefixo_anexo$num_os$sufixo.$extArquivo";
				break;
			}
		}
		if (!$imagem_nota and $prefixo_anexo == 'e_')
			$imagem_nota = "$target_dir/$prefixo_anexo$num_os" . ($temNFs + 1) . ".$extArquivo";
	} else {
		$imagem_nota  = "$target_dir/$prefixo_anexo$num_os.$extArquivo";
	}

	// Feitas as validações...
	// Finaliza a inicialização das variáveis
	$arquivo      = $nf_upload['tmp_name'];

	list($nova_largura,$nova_altura) = $max_size;

	if (strlen($img_msg_erro) == 0 or $img_msg_erro == 0) {
		if ($reduz_imagens and $e_imagem) {
			$temp_img = '/tmp/' . time() . $extArquivo;
		//  Redimensiona e copia a imagem, cria a imagem reduzida para pré-visualizar
			$reDimOK = reduz_imagem($arquivo, $nova_largura, $nova_altura, $temp_img);

			if ($reDimOK)
				unlink($arquivo);
			else
				return 6;
		} else {
			$moveu = (is_uploaded_file($arquivo)) ? move_uploaded_file($arquivo, $imagem_nota) : copy($arquivo, $imagem_nota);
		}

		if ($criar_thumbnail and $e_imagem) {
			$imagem_thumb = str_replace($num_os, $num_os.'_thumb', $imagem_nota);
			reduz_imagem($arquivo, 150, 135, $imagem_thumb);
			if (file_exists($imagem_thumb))
				chmod($imagem_thumb, 0666);
		}

		if (!file_exists($imagem_nota)) {
			$img_msg_erro = "Não gravou :( ";
			$imagem_nota  = '';
		} else {
			$img_msg_erro = 0;
			chmod($imagem_nota, 0666);

			if (!$prefixo_anexo and !$temNFs) {
				$sql = " UPDATE tbl_os SET nf_os = 't' WHERE os = $num_os";
				$res = @pg_query($con,$sql);
			}
		}

	}

	return $img_msg_erro;

} // END function anexaNF

function excluirNF($arquivoNF) { // BEGIN function excluiNF. Exclui o arquivo $arquivoNF. Tem que vir a OS, ou a URI ou URL
	global $con, $msgs_erro, $fabricas_anexam_NF, $login_fabrica;

	//Se passar só o nº da OS ou nº de OS, 1|2 (1ª ou 2ª imagem) | 'r' [revenda]
	if (is_numeric($arquivoNF)) 
		$arquivoNF = temNF($arquivoNF, 'path');

//  Pega só o path relativo do nome do arquivo...
	if (!preg_match('/(\d{2,3}\/\d{4}_\d{2}\/)?([' . TIPOS_ANEXO .']_)?\d{4,}(_\d|-\d)?\.[a-z]{3,4}$/', $arquivoNF, $nomeArquivo)){	
		//echo "Não bate... /(\d{2,3}\/\d{4}_\d{2}\/)?([" . TIPOS_ANEXO . "]_)?\d{6,}(_\d|-\d)?\.[a-z]{3}$/'";
		return false; // Só pra garantir que não vai deletar o que não deve...
	}
	
	$curdir = getcwd();
	chdir(LOCAL_PATH);

	//print_r($nomeArquivo);

	//echo getcwd();

	//O preg_match devolve a string que bate com a ER, no caso o path relativo ao NF_BASE_DIR
	$arquivo = NF_BASE_DIR . '/' . $nomeArquivo[0];
	//die($arquivo);

	if ($fabricas_anexam_NF[$login_fabrica]['criar_thumbnail']) {
		$arquivo_thumb = NF_BASE_DIR . '/' . preg_replace( '/(.*\d{6,})(-\d\d?)?\.jpg$/', '$1$2_thumb.jpg', $nomeArquivo[0]);
	}
	
	if (file_exists($arquivo)) {
		//die("Excluíndo $arquivo...");
		$excluiu = unlink($arquivo);
		
		if ($excluiu) {
			if (file_exists($arquivo_thumb) and $arquivo_thumb) 
				$excluiu_thumb = unlink($arquivo_thumb);
			
			$num_os = preg_replace('/^.*\/(\d{5,9})\D.*$/','$1',$arquivoNF);
			if ($prefixo_rev == '' and !temNF($num_os, 'bool')) {
				$sql = " UPDATE tbl_os SET nf_os = 'f' WHERE os = $num_os";
				$res = pg_query($con, $sql);
			}
		} else {
			$excluiu = "Erro ao excluir o arquivo do sistema de arquivos local";
		}
		return $excluiu;
	}
	chdir($curdir);

	return false;
} // END function excluiNF

