<?php
/**
 * Agosto 2016
 * Refatoração das funções de anexo de NF (antiga `anexaNF()`) para usar a classe
 * TDocs, seguindo o padrão da classe `AnexaS3`: primeiro confere se existe arquivo
 * no TDocs, e se não existe, usa o sistema anterior.
 * O funcionamento deveria ser o mesmo, mudando apenas as chamdas ao S3 por chamadas
 * aos métodos da classe TDocs; confiugurações de fábrica, parametrização e retorno
 * das funções devem se manter iguais, para assim poder fazer a transição transparente
 * ao resto do sistema.
 */

if (DEV_ENV === true)
	error_reporting(E_ERROR); //Mostrar erros no ambiente de testes

// Constantes
define('S3_BASE_URL',  'http://sa-east.telecontrol.com.br');
//define('NF_S3_BUCKET', 'br.com.telecontrol.nf');
define('NF_S3_BUCKET', 'br.com.telecontrol.os-anexo');
//define('NF_S3_TEMP',   'br.com.telecontrol.os-anexo-temp');

// Define o tempo que a imagem protegida estará disponível
define('S3_IMG_AUTH_TIME', '+5 minutes');

// Ativar logs para debug
// define('DEBUG', true);

$s3NF  = new AmazonS3();
$tDocs = new TDocs($con, $login_fabrica, 'os');

/**
 * @function s3glob()
 * @param   string $bucket     repositório do S3 onde procurar o arquivo
 * @param   string $path       'rota' e nome do arquivo a procurar
 * @desc
 * Usando o TDocs, o 'bucket' é ignorado, e não é usado como contexto, porque
 * a AnexaNF trata apenas com 'os'.
 * O path pode ser repassado completo, mas será usado apenas o nome do arquivo,
 * e chamado o método `TDocs::getDocumentsByName()` para recuperar os nomes.
 * Se não retorna valores, será usada a versão anterior.
 */
function s3glob($bucket, $path) {
	$arquivo = pathinfo($path, PATHINFO_BASENAME);
	global $tDocs, $s3NF;

	$getThumbs = substr($arquivo, -6) == '_thumb' ? '/size/thumb' : '';

	if (!$path or !$bucket) return false;
	$opts['prefix'] = S3TEST . $path; // No ambiente de testes, os arquivos estarão sempre dentro do "diretório" testes/

	try {
		$a = $s3NF->get_object_list($bucket, $opts);
	} catch (Exception $e) {
		return array();
	}

	if (count($a)>1) {
		usort($a, 'cmpFileName'); // Reordena os nomes dos arquivos em sequência "natural"
	}

	return $a;
}

/**
 * @function dirNF()
 * @param    int     $id    ID do objeto (os, os_iem, comunicado...)
 * @param    string  $ret   Tipo de retorno:
 *                          path: devolve o 'diretório' do S3
 *                          url: devolve a URL do arquivo
 * Devolve a URI onde está ou deverá estar a imagem. É formada da seguinte forma:
 * https://sa-east.telecontrol.com.br/br.com.telecontrol.nf/0042/2011/02/15746561.jpg
 * [--------------------------------+[--------------------+[---+[---+[-+[-------+
 * |                                 |                     |    |    |  +--> Numero da OS
 * |                                 |                     |    |    +----> Mes da digitacao
 * |                                 |                     |    +---> Ano da digitacao
 * |                                 |                     +-----> Fabrica com 4 casas
 * |                                 +---------> Nome do bucket
 * +---------> Dominio
 * Esta função devolve apenas o caminho (no exemplo: '0042/2011/02') ou URL, sem o nome do arquivo
 */
function dirNF($num_os, $devolver='path') {
	global $con, $login_fabrica, $a_tiposAnexo, $tDocs;

	if (!$num_os or strlen($num_os) == 2)
		return false;

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
	$num_os = preg_replace('/\D/', '', $num_os);

	if (DEBUG===true)
		pecho("OS ID: '$num_os'" . '  '.$tipo.'<br/>');

	if(strlen($num_os) < 11) {
		$sql_os = "SELECT fabrica, TO_CHAR($campoData, 'YYYY/MM') AS data_os
			FROM tbl_$tbl_r
			WHERE $tbl_r      = $num_os
			AND fabrica = $login_fabrica";
		$res = pg_query($con, $sql_os);

		if (!is_resource($res))      return 8;
		if (@pg_num_rows($res) == 0) return 4;
	}else{
		return 4;
	}

	extract(pg_fetch_assoc($res, 0));

	$dir_nf_os = ($devolver=='path') ? '' : S3_BASE_URL . '/';
	$dir_nf_os.= str_pad($fabrica, 4, '0', STR_PAD_LEFT) .
		'/' . $data_os;

	return $dir_nf_os;
}

/**
 * @function temNF() mixed
 * @param    $num_os    ID da OS/OS revenda, com o 'prefixo' do tipo (r, s, e)
 * @param    $retorno   informa o tipo de retorno (1)
 * @param    $tipo      tipo de anexo ('r'evenda, 's'edex, 'e'xtrato)
 * @param    $admin     informa se é admin, para algumas validações
 * @param    $chklst    informa se é uma checklist, para mais validações extra
 * @return   mixed      Retorna '' se não tem anexos **e se o retorno é diferente**
 *                      de `bool`, se o retorno solicitado é `bool`, e **não** tem
 *                      anexos, retorna FALSE.
 *
 * (1) Tipos de retorno da função:
 * - bool    TRUE | FALSE
 * - count   INT (quantidade de arquivos encontrada)
 * - array   array com quandidade de arquivos, de thumbnails, e dois arrays
 *           contendo paths ou links aos arquivos, para manipular de forma
 *           diferenciada (sem usaar a função `anexosHTML()`)
 * - link    HTML com a tabela e os links
 * - linkEx  como o anterior, mais um 'X' para excluir os arquivos
 */
function temNF($num_os, $retorno = 'link', $tipo = '', $admin = false, $chklst = false) {
	global $s3NF, $con, $tDocs, $msgs_erro, $fabricas_anexam_NF,
		$login_fabrica, $a_tiposAnexo, $attTblAttrs;

	$fabrica_anexo = $login_fabrica;


	if (!$num_os)
		return ($retorno == 'bool')  ? false :
			   ($retorno == 'count') ? 0 : '';

	$fn = $num_os;
	if (!is_numeric($num_os)) {
		$t = array(
			$num_os,
			$num_os[0],
			preg_replace('/\D/', '', $num_os)
		);
		$tipo = $num_os[0];
		$num_os = $t[2];

		switch ($t[1]) {
			case 's': $tipoTDocs = 'ossedex';   break;
			case 'r': $tipoTDocs = 'osrevenda'; break;
			case 'e': $tipoTDocs = 'osextrato'; break;
			case 'o': $tipoTDocs = 'os';        break;
			default:  $tipoTDocs = 'os'; $tipo = '';
		}
	} else {
		$os_revenda = e_OS_revenda($num_os);

		if ($os_revenda) {
			if ($login_fabrica != 160) {
				$tipo = 'r';
				$tipoTDocs  = 'osrevenda';
				$num_os = $os_revenda;
			} else {
				$tipo = 'r';
				$tipoTDocs  = 'os_revenda';
			}
		} else {
			$tipo = '';
			$tipoTDocs = 'os';
		}
	}

	if(in_array($login_fabrica, array(11,172))){
		if($tipo == 'r') {
			$tbl_os = "os_revenda";
		}else{
			$tbl_os = "os";
		}

		$sql_os_fabrica = "SELECT fabrica FROM tbl_$tbl_os WHERE $tbl_os = '{$num_os}'";
		$res_os_fabrica = pg_query($con, $sql_os_fabrica);
		if(pg_num_rows($res_os_fabrica) > 0){
			$fabrica_anexo = pg_fetch_result($res_os_fabrica, 0, "fabrica");
		}

	}

	if ($tipo) {
		if (strpos(TIPOS_ANEXO, $tipo)===false) return false;


		extract($a_tiposAnexo[$tipo]);
		$tipo_anexo = $tipo . '_';

	} else {
		extract($a_tiposAnexo['o']);
	}

	if(in_array($fabrica_anexo, array(11,172))){

		$fabricas_anexam_NF_arr = array();

		foreach ($fabricas_anexam_NF as $c => $v) {

			$k = (is_numeric($c)) ? $fabrica_anexo : $c;
			
			$fabricas_anexam_NF_arr[$k] = $v;

		}

		$fabricas_anexam_NF = $fabricas_anexam_NF_arr;

	}

	if (!array_key_exists($fabrica_anexo, $fabricas_anexam_NF)) {
		extract($fabricas_anexam_NF['padrao']);
	} else {
		extract($fabricas_anexam_NF[$fabrica_anexo]);
	}

	// Init vars...
	$temImagem    = false;
	$temThumbnail = false;
	$tDocsFiles   = array();
	$thumbsTDocs  = array();
	$tDocsCount   = 0;
	$imgS3count   = 0;

	// TDocs...
	$tDocs->setContext($tipoTDocs);
	$tDocs->getDocumentsByRef($num_os);
	$att = array();

	if ($tDocs->hasAttachment) {
		$att = $tDocs->attachListInfo;
		$temAnexoTDocs = true;
		$tDocsCount = $tDocs->attachCount;
	}

	if($tipoTDocs == "os"){
		$tDocs->setContext("osserie")->getDocumentsByRef($num_os);
		$att2 = $tDocs->attachListInfo;
		$temAnexoTDocs = true;
		$tDocsCount += $tDocs->attachCount;
	}

	if(count($att2)){
		$att = array_merge($att,$att2);
	}

	$att3 = array();

	if ($fabrica_anexo == 1) {

		$query = "SELECT consumidor_revenda, sua_os 
		 	      FROM tbl_os 
		 	      WHERE os = '{$num_os}'
		 	      AND fabrica = {$fabrica_anexo}";

		$res = pg_query($con, $query);

		$consumidor_revenda = pg_fetch_result($res, 0, 'consumidor_revenda');
		$sua_os 			= pg_fetch_result($res, 0, 'sua_os');

		if ($consumidor_revenda == 'R') {

			$sua_os = explode('-', $sua_os);
			$sua_os = $sua_os[0];

			$query = "SELECT distinct os_revenda 
					  FROM tbl_os_revenda 
					  JOIN tbl_os_revenda_item USING(os_revenda)
					  WHERE os_lote = $num_os
					  AND fabrica = {$fabrica_anexo}";

			$res = pg_query($con, $query);
		
			$num_revenda = pg_fetch_result($res, 0, 'os_revenda');

			if (!empty($num_revenda)) {
				
				$query = "SELECT tdocs, tdocs_id, referencia_id, obs
						  FROM tbl_tdocs 
						  WHERE referencia_id = '{$num_revenda}'
						  AND referencia = 'revenda'";

				$res   = pg_query($con, $query);

				for ($i = 0; $i < pg_num_rows($res); $i++) { 

					$tdocs    = pg_fetch_result($res, $i, 'tdocs');
					$tdocs_id = pg_fetch_result($res, $i, 'tdocs_id');
					$obs      = json_decode(pg_fetch_result($res, $i, 'obs'), True);

					$link = 'https://api2.telecontrol.com.br/tdocs/document/id/' . $tdocs_id . '/file/' . $obs[0]['filename'];

					$att3[] = [
								"link"  => $link,
								"id"    => $tdocs,
								"thumb" => str_replace('/file/', '/size/thumb/file/', $link)
							  ];
				}
			}
		}
	}

	foreach ($att as $idx=>$_row) {
		$tDocsFiles[$idx] = $_row['link'];
		$thumbsTDocs[$idx] = str_replace('/file/', '/size/thumb/file/', $_row['link']);
	}

	if (count($att3)) {

		foreach ($att3 as $anexo) {
			
			$tDocsFiles[$anexo['id']]  = $anexo['link'];
			$thumbsTDocs[$anexo['id']] = $anexo['thumb']; 
		}
	}
	
	//  Determina o path e a URL base da imagem
	$dir = dirNF($tipo_anexo . $num_os);        // Devolve '0042/2011/02'

	if (is_numeric($dir) && !in_array($fabrica_anexo, array(1,11,160,172))){
		return $dir;
	}

	$searchString = "$dir/$tipo_anexo$num_os";

	$imgS3count = (count($s3files = s3glob(NF_S3_BUCKET, $searchString)));

	if (!$imgS3count) {
		$imgS3count = 0;
		$s3files = array();
	}

	$temImagem = $tDocsCount + $imgS3count;
	$arquivos  = array_merge($s3files, $tDocsFiles);

	// Para estes tipos de retorno, já temos informação suficiente
	if ($retorno == 'bool')  return ($temImagem > 0);
	if ($retorno == 'count') return $temImagem;

	// Prepara os arrays
	$thumbs = s3glob(NF_S3_BUCKET, $searchString . "_thumb");
	if (!count($thumbs)) $thumbs = $s3files;

	$temThumbnail = count($thumbs);

	$thumbs = array_merge($thumbs, $thumbsTDocs);

	usort($arquivos, 'cmpFileName');
	usort($thumbs, 'cmpFileName');

	foreach ($arquivos as $idx => $fn) {
		if (is_numeric(pathinfo($fn, PATHINFO_FILENAME))) {

			if (!$admin or ($admin and !$chklst))
				continue;

			$arquivos[$idx] = null;
			continue;
		}

		$temAdminNoNome = (bool)strpos($fn, '_admin.');

		if ($temAdminNoNome !== $admin)
			$arquivos[$idx] = null;
	}

	$arquivos = array_filter($arquivos);
	$thumbs   = array_filter($thumbs);

	if ($retorno == 'array') {
		return array(
			'temThumbnail'  => $temThumbnail,
			'temAnexoTDocs' => $temAnexoTDocs,
			'arquivos'      => $arquivos,
			'thumbs'        => $thumbs
		);
	}

	return anexosHTML($arquivos, $thumbs, $retorno);

} // End function temNF()

/**
 * Refatorado:
 * Tem apenas um `switch{}` de diferença e usa em uma tela (2, posto e admin).
 */
function temNFMakita($num_os, $ret='link', $tipo='', $admin=false, $chklst=false) {
	return temNF($os, $ret, $tipo, $admin, $chklst);
}

/**
 * Refatorado, consulta os e os_revenda, junta e cria o retorno
 */
function temNF2($num_os, $num_os_revenda, $retorno = 'link', $tipo = '') {
	global $s3NF, $con, $msgs_erro, $fabricas_anexam_NF, $login_fabrica, $a_tiposAnexo, $attTblAttrs;

	if (!$num_os && !$num_os_revenda) return ($retorno == 'bool')? false : '';

	$retOS = temNF($num_os, 'array', 'o');
	$retRV = temNF($num_os_revenda, 'array', 'r');

	if ($retorno == 'bool')
	   return (count($retOS)>0 or count($retRV)>0);

	$arquivos = array_unique(array_merge($retOS['arquivos'], $retRV['arquivos']));
	$thumbs   = array_unique(array_merge($retOS['thumbs'], $retRV['thumbs']));

	$temThumbs     = $retOS['temThumbnail']  || $retRV['temThumbnail'];
	$temAnexoTDocs = $retOS['temAnexoTDocs'] || $retRV['temAnexoTDocs'];

	return anexosHTML($arquivos, $thumbs, $retorno, $admin, $chcklst);
}

/**
 * Refatoração:
 * Cria o retorno de acordo com a solicitação. Retirado da `temNF()` para poder
 * reutilizar na `temNF2()`
 */
function anexosHTML($arquivos, $thumbs=false, $retorno='link', $admin=false, $chklst=false) {
	global $login_fabrica, $s3NF, $tDocs, $attTblAttrs, $a_tiposAnexo;

	if (!count($arquivos)) return '';

	if (count($arquivos) > 4) {
		$max_width_link = '100';
	} else {
		$max_width_link = '150';
	}

	if (!$temThumbnail = is_array($thumbs)) {
		$thumbs = $arquivos;
	}

	$base_link = "<a hash='%s' href='%s' target='_blank'>".
		"<img src='%s' title='Para ver a imagem completa, clique com o botão direito e selecione \"Abrir link em uma nova janela\" ou \"Mostrar Imagem\"' ".
		"style='_display:block;_zoom:1;max-height:150px;max-width:{$max_width_link}px;_height:150px;*height:150px;' />".
		"</a>\n";

	$base_img = "<img src='%s' />\n";

	$pathImgIco = adminBACK . BI_BACK . 'imagens';
	$idx_anexo_i = 1;

	$links['attrs'] = $attTblAttrs;
	$context = '';

	if (!empty($tDocs)) {
		$context_arr = explode(';', $tDocs->getContext());
		$context = $context_arr[0];
	}

	try {
		foreach($arquivos as $idx => $anexo) {
			$t = substr(pathinfo($anexo, PATHINFO_FILENAME), 0, 1);
			$t = is_numeric($t) ? 'o' : $t;

			if (preg_match('#[0-9a-f]{64}#', $anexo, $id)) {
				$context = (empty($context)) ? $a_tiposAnexo[$t]['tipoTDocs'] : $context;
				$tDocs->setContext($context);

				$tDocsData     = $tDocs->getDocumentInfo($id[0]);
                $anexoID       = $id[0];
				$anexo         = $tDocsData['fileName'];
				$imgUrl        = $tDocsData['link'];
				$temAnexoTDocs = true;
			} else {
				$imgUrl = S3_BASE_DIR.'/'.$anexo;
                $anexoID = $anexo;
				if (!fopen($imgUrl, 'r'))
                    $imgUrl  = $s3NF->get_object_url(
                        NF_S3_BUCKET, $anexo,
                        S3_IMG_AUTH_TIME,
                        array('https'=>true)
                    );
				$temAnexoTDocs = false;
				unset($tDocsData);
			}

			$link = getAttachLink($imgUrl, '', true);

			$link['acao'] = str_replace('img/',     '', $link['acao']);
			$link['acao'] = str_replace('imagens/', '', $link['acao']);
			$link['ico']  = str_replace('imagens/', '', $link['ico']);

			if ($login_fabrica == 1) {
				// Last Modified Date
				$modified_date = ($tDocsData['lastModified']) ? $tDocsData['lastModified'] : $tDocsData['insertDate'];

				if ($temAnexoTDocs) {
					$links[2][$idx] = "Modificado em " .
						date("d/m/Y H:i:s", strtotime($modified_date));
				} else {
					$objectMetadata = $s3NF->get_object_metadata(NF_S3_BUCKET, $anexo, S3_IMG_AUTH_TIME);

					$time = str_replace('000Z',"", $objectMetadata["LastModified"]);
					$time = str_replace('T'," ", $time);
					$links[2][$idx] = "Modificado em ".date("d/m/Y H:i:s",strtotime($time."-3 hour"));
				}
			}

			$arr1 = explode('/', $anexo);
			$l1 = count($arr1) - 1;
			$arr2 = explode('.', $arr1[$l1]);
			$arr3 = explode('_', $arr2[0]);

			$idx_pref = (true === $chklst) ? 'check_list' : 'anexo';

			if (count($arr3) == 1) {
				$idx_anexo_0 = strtoupper($idx_pref . ' ' . $idx_anexo_i);
				$idx_anexo_1 = $idx_pref . '_' . $idx_anexo_i;
				$idx_anexo_i++;
			} else {
				if ($arr3[1] == 'PROCON') {
					$idx_anexo_0 = mb_strtoupper('Carta de reclamação/Notificação');
					$idx_anexo_1 = 'carta_de_reclamação/notificação';
				} else {
					$idx_anexo_0 = mb_strtoupper($idx_pref . ' ' . $idx_anexo_i);
					$idx_anexo_1 = $idx_pref . '_' . $idx_anexo_i;
					$idx_anexo_i++;

					if ($login_fabrica == 42) {
						$anexo_nome = $arr1[count($arr1) - 1];
						$anexo_nome = explode('.', $anexo_nome);

						switch ($anexo_nome[0]) {
						case 'e_'.$num_os:
							$idx_anexo_0 = strtoupper('Nota Fiscal de Serviço');
							break;
						case 'e_'.$num_os.'-2':
							$idx_anexo_0 = strtoupper('XML da Nota Fiscal de Serviço');
							break;
						case 'e_'.$num_os.'-3':
							$idx_anexo_0 = strtoupper('Nota Fiscal de Peças (DANFE)');
							break;
						case 'e_'.$num_os.'-4':
							$idx_anexo_0 = strtoupper('XML Nota Fiscal de Peças');
							break;
						default:
							$idx_anexo_0 = strtoupper($idx_pref . ' ' . $idx_anexo_i);
							break;
						}
					}
				}
			}

			if (strpos($tDocsData['context'], 'osserie'))
				$idx_anexo_0 = mb_strtoupper(traduz('etiqueta.serie'));

			if (strpos($link['ico'], 'image')!==false) {
				$imgs[]  = sprintf($base_img,  $imgUrl);

				$thumb = ($temAnexoTDocs or strpos($thumbs[$idx], 'thumb')) ?
					$thumbs[$idx] : $imgUrl;

				$links[0][$idx_anexo_0] = sprintf($base_link, $anexoID, $imgUrl, $thumb);
			} else {
				$links[0][$idx_anexo_0] = str_replace('150px', '96px',
					sprintf(nl2br($link['acao']), $imgUrl, traduz('click.ver.doc.online'), $imgUrl, "$pathImgIco/{$link['ico']}"));
			}

			if ($retorno == 'linkEx') {
				// pre_echo($tDocsData, 'ARQUIVO');
				if (isset($tDocsData) and strpos($tDocsData['context'], 'osserie'))
					continue;
				if( !in_array($login_fabrica, array(99,137))){
					$links[1][$idx_anexo_1] = "<span><img src='$pathImgIco/cross.png' data-id='$anexoID' data-name='$anexo' alt='Excluir' title='Excluir Arquivo' class='excluir_NF' /></span>";
				}
			}

			$paths[] = $imgUrl;
		}

	} catch (Exception $e) {
		return false;
	}

	switch ($retorno) {
		case 'url':    $ret = array_filter($paths);     break;
		case 'path':   $ret = array_filter($arquivos);  break;
		case 'img':    $ret = implode('<br />', $imgs); break;
		case 'link':   $ret = array2table($links);      break;
		case 'linkEx': $ret = array2table($links);      break;
	}
	return $ret;
}

/**
 * @function anexaArquivo()
 * Não é mais necessária, pois o destino dos arquivos é o TDocs, e não vai mais
 * gravar no S3 como até antes da mudança
 */

function anexaNF($num_os, $nf_upload, $nf_obrigatoria=null, $tag='') {
	global $con, $tDocs, $fabricas_anexam_NF, $nf_config,
		$img_msg_erro, $msgs_erro, $login_fabrica, $login_posto;
	$e_imagem = false;

	if (DEBUG === true) pre_echo(func_get_args(), 'anexaNF() params:');

	if (is_null($nf_obrigatoria)) unset($nf_obrigatoria);

	$tipo = 'os';
	$tipoTDocs = 'os';

	$fanfidx = array_key_exists($login_fabrica, $fabricas_anexam_NF) ?
		$login_fabrica : 'padrao';

	// Extrai os valores, mas não altera o valor da $nf_obrigatoria se foi passado algum valor
	extract($fabricas_anexam_NF[$fanfidx], EXTR_SKIP);

	$prefixo_anexo = null;
	if ($nf_upload == null) $nf_upload = $_FILES['foto_nf'];

	// Mesmo não marcada como os_revenda, confere no banco de dados... "vai que..."
	if (is_numeric($num_os) and $os_revenda = e_OS_revenda($num_os)) {
		$prefixo_anexo = 'r_';
		$tipoTDocs = 'osrevenda';
		$tipo = 'r';
		if (!in_array($login_fabrica, [160]) || (!isset($nf_upload['termo_entrega']) && $nf_upload['termo_entrega'] != 'ok' && !isset($nf_upload['termo_devolucao']) && $nf_upload['termo_devolucao'] != 'ok')) {
			$num_os = $os_revenda;
		} 
	} else {
		$t = array(
			$num_os,
			$num_os[0],
			preg_replace('/\D/', '', $num_os)
		);
		$tipo = $num_os[0];
		$num_os = $t[2];

		switch ($t[1]) {
			case 's': $tipoTDocs = 'ossedex';   break;
			case 'r': $tipoTDocs = 'osrevenda'; break;
			case 'e': $tipoTDocs = 'osextrato'; break;
			case 'o': $tipoTDocs = 'os';        break;
			default:  $tipoTDocs = 'os'; $tipo = '';
		}
	}

	if (!$login_posto) {
		$tbl_r = ($tipoTDocs == 'osrevenda') ? 'os_revenda' : 'os';
		$num_os = preg_replace('/\D/', '', $num_os);

		$sql = " SELECT posto
				   FROM tbl_$tbl_r
				  WHERE $tbl_r      = $num_os
					AND fabrica = $login_fabrica";
		$res = pg_query($con,$sql);

		if (DEBUG === true) pre_echo(pg_fetch_all($res) . pg_last_error($con), $sql);
		$posto = @pg_result($res,0,0);
	} else {
		$posto = $login_posto;
	}

	include_once 'regras/envioObrigatorioNF.php'; //para testes adicionar: /var/www/assist/www/

	// Corrigindo para quando nao for obrigatoria, retornar OK
	if ( !$nf_obrigatoria && $nf_upload['size'] == '0' && !EnvioObrigatorioNF($login_fabrica, $login_posto) ) {
		return 0;
	}

	$prefixo_anexo = ($tipo and $tipo != 'o') ? $tipo . '_' : '';
	$num_os = preg_replace('/\D/', '', $num_os);

	// Validando arquivo, tamanho, tipo, data, OS...
	if (!is_numeric($num_os))  return $msgs_erro[4];// Sem OS não pode gravar
	if (!is_array($nf_upload)) return $msgs_erro[1];// Os dados do arquivo tem que vir num array

	if ($nf_upload['name'] != '' and
		 $nf_upload['type'] == '' and
		 $nf_upload['size'] == 0 ) return $msgs_erro[2];// Arquivo grande demais

	$formato_OK = preg_match("/{$nf_config['mime_type']}/", $nf_upload['type']);

	if (!$formato_OK) return sprintf($msgs_erro[3], $nf_upload['name']);    // Não é um formato aceito

	if (strpos($nf_upload['type'], 'image')) { // Caso seja uma imagem...
		$e_imagem = true;
		list($width, $height) = getimagesize($nf_upload['tmp_name']);
		if (($width * $height) > $nf_config['max_img_size'])
			return sprintf($msgs_erro[9], $width, $height);    // Imagem muito grande
	}

	$target_dir = '';
	$extArquivo = strtolower(pathinfo($nf_upload['name'], PATHINFO_EXTENSION));
	//p_echo("Arquivo para anexar, nombre original: " . $nf_upload['name'] . "Extensão: " . $extArquivo);

	try {
		$anxs = temNF($prefixo_anexo.$num_os, 'url');
		$temNFs = count($anxs);

	} catch (Exception $e) {
		return $msgs_erro[13];
	}

	if ($prefixo_anexo != 'e_' and $temNFs >= LIMITE_ANEXOS)
		return sprintf($msgs_erro[10], LIMITE_ANEXOS); // Ultrapassou o limite de anexos

	if ($tag)
		$sufixo = "-$tag";

	if (!$tag and $temNFs) {
		unset($imagem_nota);

		for ($n = 1; $n <= LIMITE_ANEXOS; $n++) {
			$fname = pathinfo($anxs[$n-1], PATHINFO_FILENAME);
			$sufixo = ($n>1) ? "-$n" : '';
			// echo '$fname' ==  '$prefixo_anexo$num_os$sufixo'? <br />";
			if ($fname == "$prefixo_anexo$num_os$sufixo") {
				continue;
			}
			$imagem_nota = "$prefixo_anexo$num_os$sufixo.$extArquivo";
			break;
		}
		if (!$imagem_nota and $prefixo_anexo == 'e_')
			$imagem_nota = $prefixo_anexo . $num_os . ($temNFs + 1) . ".$extArquivo";
	} else {
		$imagem_nota  = "$prefixo_anexo$num_os{$sufixo}.$extArquivo";
	}

	//  Feitas as validações...
	//  Finaliza a inicialização das variáveis
	$arquivo = $nf_upload['tmp_name'];

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
			//move_uploaded_file($arquivo, $temp_img);
			$temp_img = $arquivo;
		}

		//p_echo("Anexar imagem $temp_img, OS $num_os tem 1ª imagem? $tem1. OS $num_os tem 2ª imagem? $tem2");
		//p_echo("Tentando anexar imagem com o nome: $imagem_nota");die;
		// Criar imagem de amostra, se está habilitado
		if ($criar_thumbnail and $e_imagem) {
			$temp_thumb   = time() . '.jpg';
			$imagem_thumb = "$target_dir/$prefixo_anexo$num_os"."_thumb$sufixo.$extArquivo";
			$reDimThumbOK = reduz_imagem($temp_img, 150, 135, $temp_thumb);
			if (!$reDimThumbOK) {
				unlink($temp_img); // Clean the house... ;)
				return 6;
			}
		}
		if (!file_exists($temp_img)) {
			$imagem_nota = '';
			return 6;
		}

		$checkFile = preg_match('/([xers]_)?\d+(?:-\d)?\.\w{3,4}$/', basename($imagem_nota), $t);
		switch ($t[1]) {
			case 's_': $tipoTDocs = 'ossedex';   break;
			case 'r_': $tipoTDocs = 'osrevenda'; break;
			case 'e_': $tipoTDocs = 'osextrato'; break;
			case 'o_': $tipoTDocs = 'os'; $tipo = 'o'; break;
			default:   $tipoTDocs = 'os'; $tipo = 'o';
		}

		if (in_array($login_fabrica, [123, 160,188]) && $nf_upload['termo_entrega'] == 'ok') {
			$arquivo_imagem = array(
				'tmp_name' => $temp_img,
				'name' => basename($imagem_nota),
				'size' => filesize($temp_img),
				'type' => mime_content_type($temp_img),
				'error' => null,
				'termo_entrega' => 'ok'
			);
		} else if (in_array($login_fabrica, [123, 160,188]) && $nf_upload['termo_devolucao'] == 'ok'){
			$arquivo_imagem = array(
				'tmp_name' => $temp_img,
				'name' => basename($imagem_nota),
				'size' => filesize($temp_img),
				'type' => mime_content_type($temp_img),
				'error' => null,
				'termo_devolucao' => 'ok'
			);
		} else {
			$arquivo_imagem = array(
				'tmp_name' => $temp_img,
				'name' => basename($imagem_nota),
				'size' => filesize($temp_img),
				'type' => mime_content_type($temp_img),
				'error' => null
			);
		}
		// pre_echo($arquivo_imagem, "SUBIR ARQUIVO $tipoTDocs:");

		$anexou = $tDocs->uploadFileS3($arquivo_imagem, $num_os, false, $tipoTDocs);

		if ($anexou) {
			$id = key($tDocs->attachListInfo);
			// $tDocs->setDocumentFileName($id, $imagem_nota); // novo nome do arquivo

			if ($tipo != 'os') {
				pg_query(
					$con,
					"UPDATE tbl_os SET nf_os = TRUE WHERE os = $num_os"
				);
			}

			//Clean up...
			if (file_exists($temp_img)) unlink($temp_img);

			return 0;
		}
		return $tDocs->error;
	}
	return $img_msg_erro;
}

function excluirNF($arquivoNF) {
	global $s3NF, $tDocs, $con, $login_fabrica;
	// Se veio um _hash_, procurar no TDocs

	if (strlen(trim($arquivoNF)) == 64) {
		$tDocs->getDocumentsByRef($arquivoNF);

		return $tDocs->removeDocumentById($arquivoNF);
	}


	// Segue o processo anterior
	$arquivoNF = str_replace(S3_BASE_URL, '', $arquivoNF); //Tira o http e o servidor, se vier


	//Se por acaso passou apenas o nº da OS (nome do arquivo), tentar "reconstruir" o path S3.
	if (preg_match("/^\d{6,8}\./", $arquivoNF, $os))
		$arquivoNF = S3TEST . dirNF($os[0]) . "/$arquivoNF";

	if (strpos($arquivoNF, '?'))
		$arquivoNF = substr($arquivoNF, 0, strpos($arquivoNF, '?'));

	try {
		if ($s3NF->if_object_exists(NF_S3_BUCKET, $arquivoNF)) {

			$excluiu = $s3NF->delete_object(NF_S3_BUCKET, $arquivoNF);

			//echo "excluindo...\n";

			$retornoS3 = $excluiu->isOK() && !isset($response->body->Error);
			$para = preg_match("/\/([" . TIPOS_ANEXO ."]_)?(\d{4,})(-\d\d?)?\.*(\w{3,4})$/", $arquivoNF, $a_para);

			if ($retornoS3 and $a_para[1] == '' and !temNF($a_para[2], 'bool')) {
				$sql = " UPDATE tbl_os SET nf_os = FALSE WHERE os = $num_os";
				$res = @pg_query($con,$sql);
			}
			//var_dump($retornoS3);
			return $retornoS3;
		}
	} catch (Exception $e) {
		return null;
	}

	return null;
}

