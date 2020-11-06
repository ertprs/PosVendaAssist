<?php
/**
 * s3glob($bucket, $path)
 * @param	$bucket		Bucket S3 onde procurar
 * @param	$path		Parte do arquivo para reduzir a pesquisa (tipo $path*)
 * @return  mixed 		Retorna um array com os nomes e tipo dos arquivos que batem com o $path
 * 						dentro do $bucket, vazio se não tiver. FALSE se houver erro nos parâmetros.
 **/
function s3glob($bucket, $path) {
	global $s3NF;

	if (!$path or !$bucket) return false;

	$opts['prefix'] = S3TEST . $path; // No ambiente de testes, os arquivos estarão sempre dentro do "diretório" testes/

	try {
		$a = $s3NF->get_object_list($bucket, $opts);
	} catch (Exception $e) {
		return array();
	}

	if (count($a)>1)
		usort($a, 'cmpFileName'); // Reordena os nomes dos arquivos em sequência "natural"

	return $a;
}

/**
 *	dirNF($os)
 *
 *  Devolve a URI onde está ou deverá estar a imagem. É formada da seguinte forma:
 *  https://sa-east.telecontrol.com.br/br.com.telecontrol.nf/0042/2011/02/15746561.jpg
 *  [--------------------------------+[--------------------+[---+[---+[-+[-------+
 *  |                                 |                     |    |    |  +--> Numero da OS
 *  |                                 |                     |    |    +----> Mes da digitacao
 *  |                                 |                     |    +---> Ano da digitacao
 *  |                                 |                     +-----> Fabrica com 4 casas
 *  |                                 +---------> Nome do bucket
 *  +---------> Dominio
 * Esta função devolve apenas o caminho (no exemplo: '0042/2011/02') ou URL, sem o nome do arquivo
 *
 *  @param $num_os  o nº da OS
 *  @param $devolve 'path' para devolver o path no S3 (padrão), ou
 *                  'url' para devolver o link, também no S3
 *  O diretório é o fabricante (4 dígitos) mais o ano (4 dígitos) e o mês (2 dígitos) da data de DIGITAÇÃO da OS.
 *  Exemplo: OS 12029473, com DATA DIGITACAO 2010-07-12 21:04:19...
 *  O 'path' seria 0050/2010/07 ('0050' da Colormaq, e 2010/07 pela data de abertura)
 *
*/
function dirNF($num_os, $devolver='path') {
	global $con, $login_fabrica, $a_tiposAnexo;

	if (!$num_os or $num_os == 'r_') return false;
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

	$sql_os = "SELECT fabrica,
				      TO_CHAR($campoData, 'YYYY/MM') AS data_os
                 FROM tbl_$tbl_r
                WHERE $tbl_r      = $num_os
                  AND fabrica = $login_fabrica";
	//echo "$sql_os\n";
	$res	= pg_query($con, $sql_os);

	if (!is_resource($res))		 return 8;
	if (@pg_num_rows($res) == 0) return 4;

	extract(pg_fetch_assoc($res, 0));

	$dir_nf_os = ($devolver=='path') ? '' : S3_BASE_URL . '/';
	$dir_nf_os.= str_pad($fabrica, 4, '0', STR_PAD_LEFT) .
				 '/' . $data_os;

	return $dir_nf_os;
}

/*  Funções */
/**
 *	temNF($num_os, $retorno, $num)
 *
 *	@param  $num_os  string required  Núm de OS/Rev/Sedex/Extrato
 *	@param  $retorno string optional  Ver abaixo
 *	@param  $tipo    string optional  Tipo de objeto para o anexo: OS Revenda, SEDEX, Extrato (ver abaixo)
 *	@param  $retorno bool   optional  Retorna TRUE ou FALSE, se há ou não imagem da NF
 *	                        'count'   Retorna a quantidade de arquivos em anexo
 *	                        'url'     Retorna a URL (S3_BASE_URL . dirNF . $imagem)
 *	                        'img'     Retorna o HTML da só das imagens (<img src='$imagem' ... />)
 *	                        'link'    Devolve o HTML (<a href='$imagem'><img src='$imagem' />...) da NF (padrão)
 *	                        'linkEx'  Como 'link', mas adiciona uma segunda TR com 'X' para excluir
 *	@param  $tipo    int     nº da imagem (1 .. 4) para as fábricas que aceitam várias imagens por OS
 *	@return mixed    Retorna NULL se não tiver imagem para a OS $num_os,
 *	                 TRUE ou FALSE se o $retorno for 'bool' ou
 *	                 URL/link/img da imagem, se tiver para o tipo de retorno solicitado.
 **/
function temNF($num_os, $retorno = 'link', $tipo = '', $admin = false, $chklst = false) {
	global $s3NF, $con, $msgs_erro, $fabricas_anexam_NF, $login_fabrica, $a_tiposAnexo, $attTblAttrs;
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


	// HD 712148 - O campo tbl_os.os_numero contém o campo tbl_os_revenda.sua_os quando é uma os_rev explodida,
	//			   não serve par determinar o campo os_revenda... Agora, consulta direto no banco de dados.

	if (!array_key_exists($login_fabrica, $fabricas_anexam_NF)) {
		extract($fabricas_anexam_NF['padrao']);
	} else {
		extract($fabricas_anexam_NF[$login_fabrica]);
	}

	//  Determina o path e a URL base da imagem
	$dir = dirNF($tipo_anexo . $num_os);        // Devolve '0042/2011/02'
	$url = dirNF($tipo_anexo . $num_os, 'url'); // Devolve S3_BASE_DIR . $dir

	if (is_numeric($dir))
		return $dir;

	$temImagem    = false;
	$temThumbnail = false;

	$searchString = "$dir/$tipo_anexo$num_os";

	$temImagem = (count($arquivos = s3glob(NF_S3_BUCKET, $searchString)));

	// Para estes tipos de retorno, já temos informação suficiente
	if ($retorno == 'bool')  return ($temImagem > 0);
	if ($retorno == 'count') return $temImagem;

    if (count($arquivos) > 4) {
        $max_width_link = '100';
    } else {
        $max_width_link = '150';
    }

	$base_link	= "<a href='%s' target='_blank'>".
				  "<img src='%s' title='Para ver a imagem completa, clique com o botão direito e selecione \"Abrir link em uma nova janela\" ou \"Mostrar Imagem\"' ".
				  "style='_display:block;_zoom:1;max-height:150px;max-width:{$max_width_link}px;_height:150px;*height:150px;' /></a>\n";

	$base_img   = "<img src='%s' />\n";

	if ($criar_thumbnail) {
		$temThumbnail =  count($thumbs = s3glob(NF_S3_BUCKET, $searchString . "_thumb"));
	}

	if (!$temThumbnail) $thumbs = $arquivos;
	usort($thumbs, 'cmpFileName');
	usort($arquivos, 'cmpFileName');

    $arquivos_tmp = array();

    if (false === $admin) {
        foreach ($arquivos as $arq) {
            $partials = explode('/', $arq);
            $last1 = count($partials) - 1;
            $x = explode('.', $partials[$last1]);
            $d = explode('_', $x[0]);

            if (count($d) > 1) {
                $last2 = count($d) - 1;
                if ($d[$last2] <> 'admin') {
                    $arquivos_tmp[] = $arq;
                }
            } else {
                $arquivos_tmp[] = $arq;
            }
        }

    } else {
        foreach ($arquivos as $arq) {
            $partials = explode('/', $arq);
            $last1 = count($partials) - 1;
            $x = explode('.', $partials[$last1]);
            $d = explode('_', $x[0]);

            if (count($d) > 1) {
                $last2 = count($d) - 1;
                if ($d[$last2] == 'admin') {
                    $arquivos_tmp[] = $arq;
                }
            } else {
                if (false == $chklst) {
                    $arquivos_tmp[] = $arq;
                }
            }
        }

    }

    $arquivos = $arquivos_tmp;

	$ret = false;

	if ($temImagem) {

		$pathImgIco = adminBACK . BI_BACK . 'imagens';
        $idx_anexo_i = 1;

		try {
			foreach($arquivos as $idx => $anexo) {
				$imgUrl = $url.'/'.$anexo;
				if (!fopen($imgUrl, 'r'))
					$imgUrl  = $s3NF->get_object_url(NF_S3_BUCKET, $anexo, S3_IMG_AUTH_TIME,array('https'=>true));

				$link = getAttachLink($anexo, '', true);
				$link['acao'] = str_replace('img/',     '', $link['acao']);
				$link['acao'] = str_replace('imagens/', '', $link['acao']);
				$link['ico']  = str_replace('imagens/', '', $link['ico']);

				if ($login_fabrica == 1) {
				// Last Modified Date
						$objectMetadata = $s3NF->get_object_metadata(NF_S3_BUCKET, $anexo, S3_IMG_AUTH_TIME);

					$time = str_replace('000Z',"", $objectMetadata["LastModified"]);
					$time = str_replace('T'," ", $time);
					$links[2][$idx] = "Modificado em ".date("d/m/Y H:i:s",strtotime($time."-3 hour"));
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
						$idx_anexo_0 = strtoupper('Carta de reclamação/Notificação');
						$idx_anexo_1 = 'carta_de_reclamação/notificação';
					} else {
						$idx_anexo_0 = strtoupper($idx_pref . ' ' . $idx_anexo_i);
						$idx_anexo_1 = $idx_pref . '_' . $idx_anexo_i;
						$idx_anexo_i++;
					}
				}

				if (strpos($link['ico'], 'image')!==false) {
					$imgs[]  = sprintf($base_img,  $imgUrl);
					$thumb = ($temThumbnail) ? $thumbs[$idx] : $imgUrl;
					$links[0][$idx_anexo_0] = sprintf($base_link, $imgUrl, $thumb);
				} else {
					$links[0][$idx_anexo_0] = str_replace('150px', '96px',
													sprintf(nl2br($link['acao']), $imgUrl, 'Clique para visualizar', $imgUrl, "$pathImgIco/{$link['ico']}"));
				}

				if ($retorno == 'linkEx') {
					if( !in_array($login_fabrica, array(99,137))){
						$links[1][$idx_anexo_1] = "<span><img src='$pathImgIco/cross.png' name='$anexo' alt='Excluir' title='Excluir Arquivo' class='excluir_NF' /></span>";
					}
				}

				$paths[] = $imgUrl;
			}
		} catch (Exception $e) {
			return false;
		}

		$links['attrs'] = $attTblAttrs;
	//	print_r($links);
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

function temNFMakita($num_os, $retorno = 'link', $tipo = '', $admin = false, $chklst = false) {
	global $s3NF, $con, $msgs_erro, $fabricas_anexam_NF, $login_fabrica, $a_tiposAnexo, $attTblAttrs;
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


	// HD 712148 - O campo tbl_os.os_numero contém o campo tbl_os_revenda.sua_os quando é uma os_rev explodida,
	//			   não serve par determinar o campo os_revenda... Agora, consulta direto no banco de dados.

	if (!array_key_exists($login_fabrica, $fabricas_anexam_NF)) {
		extract($fabricas_anexam_NF['padrao']);
	} else {
		extract($fabricas_anexam_NF[$login_fabrica]);
	}

	//  Determina o path e a URL base da imagem
	$dir = dirNF($tipo_anexo . $num_os);        // Devolve '0042/2011/02'
	$url = dirNF($tipo_anexo . $num_os, 'url'); // Devolve S3_BASE_DIR . $dir

	if (is_numeric($dir))
		return $dir;

	$temImagem    = false;
	$temThumbnail = false;

	$searchString = "$dir/$tipo_anexo$num_os";

	$temImagem = (count($arquivos = s3glob(NF_S3_BUCKET, $searchString)));

	// Para estes tipos de retorno, já temos informação suficiente
	if ($retorno == 'bool')  return ($temImagem > 0);
	if ($retorno == 'count') return $temImagem;

    if (count($arquivos) > 4) {
        $max_width_link = '100';
    } else {
        $max_width_link = '150';
    }

	$base_link	= "<a href='%s' target='_blank'>".
				  "<img src='%s' title='Para ver a imagem completa, clique com o botão direito e selecione \"Abrir link em uma nova janela\" ou \"Mostrar Imagem\"' ".
				  "style='_display:block;_zoom:1;max-height:150px;max-width:{$max_width_link}px;_height:150px;*height:150px;' /></a>\n";

	$base_img   = "<img src='%s' />\n";

	if ($criar_thumbnail) {
		$temThumbnail =  count($thumbs = s3glob(NF_S3_BUCKET, $searchString . "_thumb"));
	}

	if (!$temThumbnail) $thumbs = $arquivos;
	usort($thumbs, 'cmpFileName');
	usort($arquivos, 'cmpFileName');

    $arquivos_tmp = array();

    if (false === $admin) {
        foreach ($arquivos as $arq) {
            $partials = explode('/', $arq);
            $last1 = count($partials) - 1;
            $x = explode('.', $partials[$last1]);
            $d = explode('_', $x[0]);

            if (count($d) > 1) {
                $last2 = count($d) - 1;
                if ($d[$last2] <> 'admin') {
                    $arquivos_tmp[] = $arq;
                }
            } else {
                $arquivos_tmp[] = $arq;
            }
        }

    } else {
        foreach ($arquivos as $arq) {
            $partials = explode('/', $arq);
            $last1 = count($partials) - 1;
            $x = explode('.', $partials[$last1]);
            $d = explode('_', $x[0]);

            if (count($d) > 1) {
                $last2 = count($d) - 1;
                if ($d[$last2] == 'admin') {
                    $arquivos_tmp[] = $arq;
                }
            } else {
                if (false == $chklst) {
                    $arquivos_tmp[] = $arq;
                }
            }
        }

    }

    $arquivos = $arquivos_tmp;

	$ret = false;

	if ($temImagem) {

		$pathImgIco = adminBACK . BI_BACK . 'imagens';
        $idx_anexo_i = 1;

		try {
			foreach($arquivos as $idx => $anexo) {
				$imgUrl = $url.'/'.$anexo;
				if (!fopen($imgUrl, 'r'))
					$imgUrl  = $s3NF->get_object_url(NF_S3_BUCKET, $anexo, S3_IMG_AUTH_TIME,array('https'=>true));

				$link = getAttachLink($anexo, '', true);
				$link['acao'] = str_replace('img/',     '', $link['acao']);
				$link['acao'] = str_replace('imagens/', '', $link['acao']);
				$link['ico']  = str_replace('imagens/', '', $link['ico']);

				// if ($login_fabrica == 1) {
				// 	// Last Modified Date
				// 	$objectMetadata = $s3NF->get_object_metadata(NF_S3_BUCKET, $anexo, S3_IMG_AUTH_TIME);

				// 	$time = str_replace('000Z',"", $objectMetadata["LastModified"]);
				// 	$time = str_replace('T'," ", $time);
				// 	$links[2][$idx] = "Modificado em ".date("d/m/Y H:i:s",strtotime($time."-3 hour"));
				// }

				$arr1 = explode('/', $anexo);
				// echo $num_os;
				//echo $arr1[count($arr1) - 1];
				// exit;
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
						$idx_anexo_0 = strtoupper('Carta de reclamação/Notificação');
						$idx_anexo_1 = 'carta_de_reclamação/notificação';
					} else {
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
						//$idx_anexo_0 = strtoupper($idx_pref . ' ' . $idx_anexo_i);
						$idx_anexo_1 = $idx_pref . '_' . $idx_anexo_i;
						$idx_anexo_i++;
					}
				}

				if (strpos($link['ico'], 'image')!==false) {
					$imgs[]  = sprintf($base_img,  $imgUrl);
					$thumb = ($temThumbnail) ? $thumbs[$idx] : $imgUrl;
					$links[0][$idx_anexo_0] = sprintf($base_link, $imgUrl, $thumb);
				} else {
					$links[0][$idx_anexo_0] = str_replace('150px', '96px',
													sprintf(nl2br($link['acao']), $imgUrl, 'Clique para visualizar', $imgUrl, "$pathImgIco/{$link['ico']}"));
				}

				if ($retorno == 'linkEx') {
					if( !in_array($login_fabrica, array(99,137))){
						$links[1][$idx_anexo_1] = "<span><img src='$pathImgIco/cross.png' name='$anexo' alt='Excluir' title='Excluir Arquivo' class='excluir_NF' /></span>";
					}
				}

				$paths[] = $imgUrl;
			}
		} catch (Exception $e) {
			return false;
		}

		$links['attrs'] = $attTblAttrs;
		// print_r($links);
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


function temNF2($num_os, $num_os_revenda, $retorno = 'link', $tipo = '') {
	global $s3NF, $con, $msgs_erro, $fabricas_anexam_NF, $login_fabrica, $a_tiposAnexo, $attTblAttrs;
	$os = $num_os;
	$os_revenda = $num_os_revenda;

	if (!$num_os && !$num_os_revenda) return ($retorno == 'bool')? false : '';

	if ($tipo) {
		if (strpos(TIPOS_ANEXO, $tipo)===false) return false;

		extract($a_tiposAnexo[$tipo]);
		$tipo_anexo = $tipo . '_';

	} else {
		$os_revenda = e_OS_revenda($num_os_revenda);

		if ($os_revenda !== false) {
			extract($a_tiposAnexo['r']);
			$tipo_anexo = 'r_';
			$num_os_revenda = $os_revenda;
		} else {
			extract($a_tiposAnexo['o']);
		}
	}

	$num_os	    = preg_replace('/\D/', '', $num_os);
	$num_os_revenda	    = preg_replace('/\D/', '', $num_os_revenda);

	$base_link	= "<a href='%s' target='_blank'>".
				  "<img src='%s' title='Para ver a imagem completa, clique com o botão direito e selecione \"Abrir link em uma nova janela\" ou \"Mostrar Imagem\"' ".
				  "style='_display:block;_zoom:1;max-height:150px;max-width:150px;_height:150px;*height:150px;' /></a>\n";

	$base_img   = "<img src='%s' />\n";

	// HD 712148 - O campo tbl_os.os_numero contém o campo tbl_os_revenda.sua_os quando é uma os_rev explodida,
	//			   não serve par determinar o campo os_revenda... Agora, consulta direto no banco de dados.

	if (!array_key_exists($login_fabrica, $fabricas_anexam_NF)) {
		extract($fabricas_anexam_NF['padrao']);
	} else {
		extract($fabricas_anexam_NF[$login_fabrica]);
	}

	//  Determina o path e a URL base da imagem
	$dir = dirNF($num_os);        // Devolve '0042/2011/02'
	$url = dirNF($num_os, 'url'); // Devolve S3_BASE_DIR . $dir

	$dir_revenda = dirNF($tipo_anexo . $num_os_revenda);        // Devolve '0042/2011/02'
	$url_revenda = dirNF($tipo_anexo . $num_os_revenda, 'url'); // Devolve S3_BASE_DIR . $dir


	$temImagem    = false;
	$temThumbnail = false;

	$searchString = "$dir/$num_os";
	$searchStringRevenda = "$dir_revenda/$tipo_anexo$num_os_revenda";

	$arquivos_os         = array();
	$arquivos_os_revenda = array();

	$arquivos_os = s3glob(NF_S3_BUCKET, $searchString);
	$arquivos_os_revenda = s3glob(NF_S3_BUCKET, $searchStringRevenda);

	$arquivos = array_merge($arquivos_os, $arquivos_os_revenda);

	$temImagem = (count($arquivos));

	// Para estes tipos de retorno, já temos informação suficiente
	if ($retorno == 'bool')  return ($temImagem > 0);
	if ($retorno == 'count') return $temImagem;

	if ($criar_thumbnail) {
		$thumbs_os         = array();
		$thumbs_os_revenda = array();

		$thumbs_os = s3glob(NF_S3_BUCKET, $searchString . "_thumb");
		$thumbs_os_revenda = s3glob(NF_S3_BUCKET, $searchStringRevenda . "_thumb");

		$thumbs = array_merge($thumbs_os, $thumbs_os_revenda);

		$temThumbnail =  count($thumbs);
	}

	if (!$temThumbnail) $thumbs = $arquivos;
	usort($thumbs, 'cmpFileName');
	usort($arquivos, 'cmpFileName');

	//print_r($arquivos);

	$ret = false;

	if ($temImagem) {

		$pathImgIco = adminBACK . BI_BACK . 'imagens';

		try {
			foreach($arquivos as $idx => $anexo) {
				$imgUrl = $url.'/'.$anexo;
				if (!fopen($imgUrl, 'r'))
					$imgUrl  = $s3NF->get_object_url(NF_S3_BUCKET, $anexo, S3_IMG_AUTH_TIME,array('https'=>true));



				$link = getAttachLink($anexo, '', true);
				$link['acao'] = str_replace('img/',     '', $link['acao']);
				$link['acao'] = str_replace('imagens/', '', $link['acao']);
				$link['ico']  = str_replace('imagens/', '', $link['ico']);

				if($login_fabrica == 1){
				// Last Modified Date
					$objectMetadata = $s3NF->get_object_metadata(NF_S3_BUCKET, $anexo, S3_IMG_AUTH_TIME);

					$time = str_replace('000Z',"", $objectMetadata["LastModified"]);
					$time = str_replace('T'," ", $time);
					$links[2][$idx] = "Modificado em ".date("d/m/Y H:i:s",strtotime($time."-3 hour"));
				}

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
		} catch (Exception $e) {
			return false;
		}

		$links['attrs'] = $attTblAttrs;
		//print_r($links);
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

function anexaArquivo($anexo, $nome_anexo) {
	global $s3NF, $s3Ready, $mimeTypes;

	if (!file_exists($anexo) or $nome_anexo == '')
		return 8;

	$c = 0;
	while (!$s3Ready and $c++ < 10) {
		// Not yet? Sleep for 1 second, then check again
		sleep(1);
		$s3Ready = $s3NF->if_bucket_exists(NF_S3_BUCKET);
	}

	if (!$s3Ready) return 13; // Erro pontual de acesso

    $retry = true;
    $maxretry = 10;

	$tipoMIME = mime_content_type($anexo);

	/*
	p_echo("Tentando subir a anexo $nome_anexo para o S3 de NFs da Telecontrol");
	pre_echo($meu_anexo, S3TEST . $nome_anexo);
	p_echo("Anexando arquivo " . S3TEST . "$nome_anexo, formato " . $tipoMIME);
	die;
	 */

	try {
		while ($retry and $i++ < $maxretry) {
			$response = $s3NF->create_object(
				NF_S3_BUCKET,
				S3TEST . $nome_anexo,
				array (
					'fileUpload'  => $anexo,
					//'contentType' => $tipoMIME,
					'acl'         => AmazonS3::ACL_PUBLIC,
					'storage'     => AmazonS3::STORAGE_REDUCED
				)
			);
			$retry = false;
		}
	} catch (Exception $e) {
		return false;
	}

	//Deu certo?
    if (is_object($response) and $response->isOK()) {
		return true;
    } else {
    	return $erroS3;
    }
}

/**
 *  anexaNF
 *  Recebe a imagem, trata ela e move para o S3 (próximamente, para a fila de processamento)
 *
 *  $num_os 		:   nº da OS
 *  $nf_upload		:   array da imagem anexada ($_FILES['img_nf'], por exemplo)
 *  $nf_obrigatoria	: (opcional) TRUE para devolver erro se não há, independente da config. da fábrica.
 */
function anexaNF($num_os, $nf_upload, $nf_obrigatoria = null, $tag = '') { // BEGIN function anexaNF
	global $con, $fabricas_anexam_NF, $nf_config, $msgs_erro, $login_fabrica, $login_posto;

	// Exclui a variável se não foi passado o valor. Se a variável existir, não é 'pisada' pelo valor do array
	//echo nl2br("===1-".$num_os);
	if (is_null($nf_obrigatoria))
		unset($nf_obrigatoria);

	if (!array_key_exists($login_fabrica, $fabricas_anexam_NF)) {
		extract($fabricas_anexam_NF['padrao'], EXTR_SKIP); // Extrai os valores, mas não altera o valor da $nf_obrigatoria se foi passado algum valor
	} else {
		extract($fabricas_anexam_NF[$login_fabrica], EXTR_SKIP); // Extrai os valores, mas não altera o valor da $nf_obrigatoria se foi passado algum valor
	}

	$prefixo_anexo = null;
	if ($nf_upload == null) $nf_upload = $_FILES['foto_nf'];

	if (!$login_posto) {
		if($num_os == "r_".$num_os) {
			$tbl_r = "os_revenda";
		}else{
			$tbl_r = "os_revenda";
		}

		$num_os	= preg_replace('/\D/', '', $num_os);
		$sql = " SELECT posto
                   FROM tbl_$tbl_r
                  WHERE $tbl_r      = $num_os
                    AND fabrica = $login_fabrica";
		$res = pg_query($con,$sql);

		$posto = @pg_result($res,0,0);
	} else {
		$posto = $login_posto;
	}

	include_once 'regras/envioObrigatorioNF.php'; //para testes adicionar: /var/www/assist/www/

	// Corrigindo para quando nao for obrigatoria, retornar true
	if ( !$nf_obrigatoria && $nf_upload['size'] == '0' && !EnvioObrigatorioNF($login_fabrica, $login_posto) ) {
		return 0;
	}

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

	// Validando arquivo, tamanho, tipo, data, OS...
	if (!is_numeric($num_os))  return $msgs_erro[4];// Sem OS não pode gravar
	if (!is_array($nf_upload)) return $msgs_erro[1];// Os dados do arquivo tem que vir num array

	if (($nf_upload['name'] != '' and
		 $nf_upload['type'] == '' and
		 $nf_upload['size'] == 0) or
	    ($nf_upload['size'] > $nf_config['max_filesize'])) return $msgs_erro[2];// Arquivo grande demais

	$formato_OK = preg_match("/{$nf_config['mime_type']}/", $nf_upload['type']);

	if (!$formato_OK) return sprintf($msgs_erro[3], $nf_upload['name']);    // Não é um formato aceito

	if (strpos($nf_upload['type'], 'image')) { // Caso seja uma imagem...
		$e_imagem = true;
		list($width, $height) = getimagesize($nf_upload['tmp_name']);
		if (($width * $height) > $nf_config['max_img_size'])
			return sprintf($msgs_erro[9], $width, $height);    // Imagem muito grande
	}

	$target_dir = dirNF($prefixo_anexo . $num_os);
	//echo $target_dir;exit;

	if (is_int($target_dir))
		return $msgs_erro[$target_dir];

	$extArquivo = strtolower(pathinfo($nf_upload['name'], PATHINFO_EXTENSION));
	//p_echo("Arquivo para anexar, nombre original: " . $nf_upload['name'] . "Extensão: " . $extArquivo);

	try {
		$temNFs = count($anxs = s3glob(NF_S3_BUCKET, "$target_dir/$prefixo_anexo$num_os"));
	} catch (Exception $e) {
		return false;
	}

	if ($prefixo_anexo != 'e_' and $temNFs >= LIMITE_ANEXOS)
		return sprintf($msgs_erro[10], LIMITE_ANEXOS); // Ultrapassou o limite de anexos

    if ($tag)
        $sufixo = "-$tag";

    if (!$tag and $temNFs) {
        unset($imagem_nota);

        foreach($anxs as $idx=>$anx) {
            $anxs[$idx] = "$target_dir/" . pathinfo($anx, PATHINFO_FILENAME);
        }

        for ($n = 1; $n <= LIMITE_ANEXOS; $n++) {
            $sufixo = ($n>1) ? "-$n" : '';
            if ($tag)
                $sufixo = "-$tag";
            if (!in_array("$target_dir/$prefixo_anexo$num_os$sufixo", $anxs)) {
                $imagem_nota = "$target_dir/$prefixo_anexo$num_os$sufixo.$extArquivo";
                break;
            }
        }
        if (!$imagem_nota and $prefixo_anexo == 'e_')
            $imagem_nota = "$target_dir/$prefixo_anexo$num_os" . ($temNFs + 1) . ".$extArquivo";
    } else {
        $imagem_nota  = "$target_dir/$prefixo_anexo$num_os{$sufixo}.$extArquivo";
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

		// Finalmente... subir imagem para o S3
		$anexou = anexaArquivo($temp_img, $imagem_nota);

		if ($anexou === true and $criar_thumbnail and file_exists($temp_thumb))
			$anexou = anexaArquivo($temp_thumb, $imagem_thumb);

		if ($anexou === true) { // Se teve êxito ao subir a(s) imagem(ns)...
			if (!$prefixo_anexo) { // Por enquanto, a os_revenda não tem essa coluna
				$sql = "UPDATE tbl_os SET nf_os = TRUE WHERE os = $num_os";
				$res = @pg_query($con,$sql);
			}
			return 0;
		} else {
			if (!$prefixo_anexo and !$temNFs) { // Por enquanto, a os_revenda não tem essa coluna
				$sql = " UPDATE tbl_os SET nf_os = FALSE WHERE os = $num_os";
				$res = @pg_query($con,$sql);
			}
			$img_msg_erro = $anexou;

			//Clean up...
			if (file_exists($temp_img))   unlink($temp_img);
			if (file_exists($temp_thumb)) unlink($temp_thumb);
		}
	}
	return $img_msg_erro;
} // END function anexaNF

function excluirNF($arquivoNF) { // BEGIN function excluirNF. Exclui o arquivo $arquivoNF. Tem que vir a URI ou URL
	global $s3NF, $login_fabrica;

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
} // END function excluirNF

