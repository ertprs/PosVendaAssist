<?php

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios = "cadastros";

include 'autentica_admin.php';
include 'funcoes.php';

echo '<link rel="stylesheet" type="text/css" href="../css/ebano.css" media="screen">';
$msg_erro = "";
//  HD 96953 - Fotos de produtos para a Britânia
if ($login_fabrica == 3 or $login_fabrica > 80) {
		$imgType = '';
	function open_image ($file) {
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
        if($original_x > $original_y) {
           $porcentagem = (100 * $max_x) / $original_x;
        }
        else {
           $porcentagem = (100 * $max_y) / $original_y;
        }

        $tamanho_x	= $original_x * ($porcentagem / 100);
        $tamanho_y	= $original_y * ($porcentagem / 100);
        $image_p	= imagecreatetruecolor($tamanho_x, $tamanho_y);
        $image		= open_image($img);

        imagecopyresampled($image_p, $image, 0, 0, 0, 0, $tamanho_x, $tamanho_y, $original_x, $original_y);
        return save_image($image_p, $nome_foto, $formato);
    }

    if (isset($_FILES['arquivo'])) {

        $a_foto = $_FILES["arquivo"];

        if ($a_foto['tmp_name'] <> '' and
            $a_foto['error'] == UPLOAD_ERR_OK and
            $a_foto['size'] < 153600) {
            $Destino  = "/www/assist/www/imagens_produtos/$login_fabrica/media/";
            $DestinoP = "/www/assist/www/imagens_produtos/$login_fabrica/pequena/";
            $Nome     = $a_foto['name'];
            $Tamanho  = $a_foto['size'];
            $Tipo     = $a_foto['type'];
            $Tmpname  = $a_foto['tmp_name'];

            if (preg_match('/^image\/(x-ms-bmp|bmp|x-bmp|pjpeg|jpeg|png|x-png|gif|jpg)$/', $Tipo)) {
                if (!is_uploaded_file($Tmpname)) {
                    $msg_erro .= "Não foi possível efetuar o upload.";
                } else {
                    $ext = substr($Nome, strrpos($Nome, ".")+1);
                    if (strlen($Nome) == 0 and $ext <> "") {
                        $ext = $Nome;
                    }

					list($void, $tipo_MIME) = explode('/', $Tipo);
                    $Caminho_foto	= $Destino . "$produto.jpg";
                    $Caminho_thumb	= $DestinoP. "$produto.jpg";

					// Salvar o arquivo de imagem se já existir
					$arq_ant = glob("$Destino$produto.{gif,GIF,png,PNG,jpg,JPG}", GLOB_BRACE);
					//echo '<code>' . print_r($arq_ant) . '</code>';
					if (count($arq_ant)) {
						$img_anterior = $Destino . 'temp_' . basename($arq_ant[0]);
						$thumb_anterior = $DestinoP . 'temp_' . basename($arq_ant[0]);
						rename($arq_ant[0], $img_anterior);
						rename(str_replace('media', 'pequena', $arq_ant[0]), $thumb_anterior);
						$excl_ant = true;
						//echo "<p>Imagem anterior: $img_anterior</p>";
					}

					/* Estas linhas são para ajustar a extensão do arquivo ao formato,
					 * mas, por enquanto, o formato destino vai ser sempre JPG... 
					 * Comentando código.
					$path_info	= pathinfo($Caminho_foto);
					$nome		= $path_info['dirname'] . '/' . $path_info['filename'];
					$ext		= $path_info['extension'];

					if ($ext != $tipo_MIME) { // Muda a extensão do arquivo de acordo com o conteúdo
						$ext = ($tipo_MIME  == 'jpeg' or $tipo_MIME == 'bmp') ? 'jpg' : $tipo_MIME;
					}
					echo "\nExtensão: $ext\n<br />\n";
					echo $Caminho_foto = $nome . '.' . $ext;
					*/
                    reduz_imagem($Tmpname, 400, 300, $Caminho_foto);
					if (!file_exists($Caminho_foto)) {
						$msg_erro.= 'Não foi possível adicionar a imagem, formato não reconhecido.';
						if ($excl_ant) {
							rename($img_anterior, $arq_ant[0]); //Voltar o arquivo de imagem que já existia
							rename($thumb_anterior, str_replace('media', 'pequena', $arq_ant[0]));
						}
					} else {
						reduz_imagem($Tmpname,  80,  60, $Caminho_thumb);
						if ($excl_ant) {
							unlink($img_anterior); //Excluir definitivamente o arquivo anterior, se existia
							unlink($thumb_anterior); //Excluir definitivamente o arquivo anterior, se existia
						}
					}
                }
            } else {
                $msg_erro .= "O formato do arquivo $Nome não é permitido!<br>";
            }

        } else {
			$msg_erro .= "O tamanho do arquivo é maior do que o permitido (150Kb)!<br>";
		}
		/*if ($msg_erro) exit("<p>$msg_erro</p></body></html>");*/
    }
}

if (strlen($_GET['msg']) > 10) { //HD 406404 - Obrigar ao navegador a carregar de novo a imagem.
	header("Cache-Control: no-cache, must-revalidate");
	header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");
}

$acao    = trim($_REQUEST["acao"]);
$btnacao = trim($_REQUEST["btnacao"]);

if ($acao == "a" and strlen($produto) > 0) {
    $pais = $_GET["pais"];
    if (strlen($pais)) {
        $sql = "SELECT produto,pais FROM tbl_produto_pais WHERE produto=$produto AND pais = '$pais'";
        $res = @pg_query ($con,$sql);
        if(pg_num_rows($res)>0){
            $sql = "DELETE FROM tbl_produto_pais WHERE produto=$produto AND pais = '$pais'";
            $res = @pg_query ($con,$sql);
        }
    }
    header ("Location: $PHP_SELF?produto=$produto");
}


if ($acao == "atribui" and strlen($produto) > 0) {
    $pais            = $_GET["pais"];
    $garantia_pais    = $_GET["garantia_pais"];

    if (strlen($garantia_pais) == 0) {
        $garantia_pais="null";
    }

    if (strlen($pais) > 0) {
        $sql = "SELECT produto,pais FROM tbl_produto_pais WHERE produto=$produto AND pais = '$pais'";
        $res = @pg_query ($con,$sql);
        if (pg_num_rows($res) == 0) {
            $sql = "INSERT INTO tbl_produto_pais (produto,pais, garantia) VALUES ($produto,'$pais', $garantia_pais)";
            $res = pg_query ($con,$sql);
        }
    }
    header ("Location: $PHP_SELF?produto=$produto");
}

if ($btnacao == "deletar" and strlen($produto) > 0) {

    $res = pg_query ($con,"BEGIN TRANSACTION");

    $sql = "DELETE FROM tbl_lista_basica
            WHERE  tbl_lista_basica.produto = tbl_produto.produto
            AND    tbl_produto.linha   = tbl_linha.linha
            AND    tbl_linha.fabrica   = $login_fabrica
            AND    tbl_produto.produto = $produto;";
    $res = @pg_query ($con,$sql);
    $msg_erro = pg_errormessage($con);

    if (strlen($msg_erro) > 0) {
        if(strpos($msg_erro,'update or delete on "tbl_produto" violates foreign key constraint "$1" on "tbl_lista_basica"'))
            $msg_erro = "Este produto está presente na Lista Básica, e não pode ser apagado.";
    }
    
    if (strlen($msg_erro) == 0) {
        $sql = "DELETE FROM tbl_produto
                WHERE  tbl_produto.linha   = tbl_linha.linha
                AND    tbl_linha.fabrica   = $login_fabrica
                AND    tbl_produto.produto = $produto;";
        $res = @pg_query ($con,$sql);
        $msg_erro = pg_errormessage($con);
        
        if(strpos($msg_erro,'update or delete on "tbl_produto" violates foreign key constraint "$1" on "tbl_subproduto"'))
            $msg_erro = "Não foi possível apagar o produto, pois o mesmo encontra-se cadastrado na tabela de subproduto.";
        
        if(strpos($msg_erro,'update or delete on "tbl_produto" violates foreign key constraint "$2" on "tbl_subproduto"'))
            $msg_erro = "Não foi possível apagar o produto, pois o mesmo encontra-se cadastrado na tabela de subproduto.";

        if(strpos($msg_erro,'"$1" on "tbl_produto_tipo_posto"'))
            $msg_erro = "Não foi possível apagar o produto, pois o mesmo encontra-se cadastrado na tabela de tipo posto mão-de-obra.";

        if(strpos($msg_erro,'constraint "produto_fk" on "tbl_os"'))
            $msg_erro = "Não foi possível apagar o produto, pois o mesmo encontra-se cadastrado em uma Ordem de Serviço.";

    }
    
    if (strlen($msg_erro) == 0) {
        ###CONCLUI OPERAÇÃO DE INCLUSÃO/EXLUSÃO/ALTERAÇÃO E SUBMETE
        $res = pg_query ($con,"COMMIT TRANSACTION");
        header ("Location: $PHP_SELF");
        exit;
    }else{
        ###ABORTA OPERAÇÃO DE INCLUSÃO/EXLUSÃO/ALTERAÇÃO E RECARREGA CAMPOS
        $marca                    = $_POST["marca"];
        $produto                  = $_POST["produto"];
        $linha                    = $_POST["linha"];
        $familia                  = $_POST["familia"];
        $descricao                = $_POST["descricao"];
        $referencia               = $_POST["referencia"];
        $voltagem                 = $_POST["voltagem"];
        $garantia                 = $_POST["garantia"];
        $preco                    = $_POST["preco"];
        $mao_de_obra              = $_POST["mao_de_obra"];
        $mao_de_obra_admin        = $_POST["mao_de_obra_admin"];
        $mao_de_obra_troca        = $_POST["mao_de_obra_troca"];
        $valor_troca_gas          = $_POST["valor_troca_gas"];
        $nome_comercial           = $_POST["nome_comercial"];
        $classificacao_fiscal     = $_POST["classificacao_fiscal"];
        $ipi                      = $_POST["ipi"];
        $radical_serie            = $_POST["radical_serie"];
        $radical_serie2           = $_POST["radical_serie2"];
        $radical_serie3           = $_POST["radical_serie3"];
        $radical_serie4           = $_POST["radical_serie4"];
        $radical_serie5           = $_POST["radical_serie5"];
        $radical_serie6           = $_POST["radical_serie6"];
        $numero_serie_obrigatorio = $_POST["numero_serie_obrigatorio"];
        $produto_principal        = $_POST["produto_principal"];
        $locador                  = $_POST["locador"];
        $ativo                    = $_POST["ativo"];
        $uso_interno_ativo        = $_POST["uso_interno_ativo"];
        $referencia_fabrica       = trim($_POST["referencia_fabrica"]);
        $code_convention          = $_POST["code_convention"];
        $abre_os                  = $_POST["abre_os"];
        $aviso_email              = $_POST["aviso_email"];
        $troca_obrigatoria        = $_POST["troca_obrigatoria"];
        $produto_critico          = $_POST["produto_critico"];
        $intervencao_tecnica      = $_POST["intervencao_tecnica"];
        $origem                   = $_POST["origem"];
        $qtd_etiqueta_os          = $_POST["qtd_etiqueta_os"];
        $lista_troca              = $_POST["lista_troca"];
        $produto_fornecedor       = $_POST["produto_fornecedor"];
        $valor_troca              = $_POST["valor_troca"];
        $troca_garantia           = $_POST["troca_garantia"];
        $troca_faturada           = $_POST["troca_faturada"];
        $observacao               = $_POST["observacao"];
		$serie_in                 = $_POST["serie_in"];
		$serie_out                = $_POST["serie_out"];
		$apagar_serie             = $_POST["apagar_serie"];
		
        $res = pg_query ($con,"ROLLBACK TRANSACTION");
    }
}

if ($btnacao == "gravar") {
    
    if (strlen($_POST["linha"]) > 0)                $aux_linha = "'". trim($_POST["linha"]) ."'";
    else                                            $msg_erro = "Selecione a linha do produto.";

    $aux_familia = (strlen($_POST["familia"]) > 0) ? "'". trim($_POST["familia"]) ."'" : "null";

    if (strlen($_POST["mao_de_obra"]) > 0)            $aux_mao_de_obra = "'". trim($_POST["mao_de_obra"]) ."'";
    else{
		if($login_fabrica != 96){
			$msg_erro = "Digite o valor da mão-de-obra.";
		}
		else{
			$aux_mao_de_obra = "'0'";
		}
	}

    if (strlen($_POST["mao_de_obra_admin"]) > 0)    $aux_mao_de_obra_admin = "'". trim($_POST["mao_de_obra_admin"]) ."'";
    else                                            $aux_mao_de_obra_admin = "null";

    if (strlen($_POST["mao_de_obra_troca"]) > 0)    $aux_mao_de_obra_troca = "'". trim($_POST["mao_de_obra_troca"]) ."'";
    else                                            $aux_mao_de_obra_troca = "null";

    if (strlen($_POST["origem"]) > 0)                $aux_origem = "'". trim($_POST["origem"]) ."'";
    else                                            $aux_origem = "null";

    if (strlen($_POST["mao_de_obra_admin"]) > 0) {
        $aux_mao_de_obra_admin = "'". trim($_POST["mao_de_obra_admin"]) ."'";
    } else {
        if ($login_fabrica == 1) {
            #hd 230831 a fabiola não quer que seja igual e não tem quem explique para ela....
            $aux_mao_de_obra_admin = "'"."0"."'";
        } else {
            $aux_mao_de_obra_admin = $aux_mao_de_obra;
        }
    }

    if (strlen($_POST["valor_troca_gas"]) > 0)        $aux_valor_troca_gas= "'". trim($_POST["valor_troca_gas"]) ."'";
    else                                            $aux_valor_troca_gas= "'0'";

    if (strlen($_POST["preco"]) > 0)                $aux_preco = "'". trim($_POST["preco"]) ."'";
    else                                            $aux_preco = "null";

    if (strlen($_POST["garantia"]) > 0)                $aux_garantia = "'". trim($_POST["garantia"]) ."'";
    else                                            $msg_erro = "Digite a garantia do produto.";
    
    if (strlen($_POST["descricao"]) > 0)            $aux_descricao = "'". trim($_POST["descricao"]) ."'";
    else                                            $msg_erro = "Digite a descrição do produto.";
    
    if (strlen($_POST["referencia"]) > 0)            $aux_referencia = "'". trim($_POST["referencia"]) ."'";
    else                                            $msg_erro = "Digite o referência do produto.";
    
    if (in_array($login_fabrica,array(1,20,24,80,96))) {
        if (strlen($_POST["referencia_fabrica"]) > 0)    $aux_referencia_fabrica = "'". trim($_POST["referencia_fabrica"]) ."'";
        else{
			if($login_fabrica == 96){
				$msg_erro = "Digite o nome comercial do produto.";
			}
			else{
				$msg_erro = "Digite a referência interna do produto.";
			}
		}
    }else{
        $aux_referencia_fabrica = "'". trim($_POST["referencia"]) ."'";
    }
    
    if (strlen($_POST["code_convention"]) > 0)        $aux_code_convention = "'". trim($_POST["code_convention"]) ."'";
    else                                            $aux_code_convention = "null";

    $aux_voltagem = (strlen($_POST["voltagem"]) > 0) ? "'". trim($_POST["voltagem"]) ."'" : "null";

    #HD 22873
    if (strlen($_POST["capacidade"]) > 0)            $aux_capacidade = trim($_POST["capacidade"]);
    else                                            $aux_capacidade = "null";

    #HD 22873
    if (strlen($_POST["divisao"]) > 0)                $aux_divisao = "'". trim($_POST["divisao"]) ."'";
    else                                            $aux_divisao = "null";

    if (strlen($_POST["ativo"]) > 0){
        $aux_ativo = "'". trim($_POST["ativo"]) ."'";
    }else{
        $msg_erro = "Por favor! Escolha o Status Rede para Ativo ou Inativo";
    }

    if (strlen($_POST["uso_interno_ativo"]) > 0)                $aux_uso_interno_ativo = "'". trim($_POST["uso_interno_ativo"]) ."'";
    else                                            $msg_erro = "Por favor! Escolha o Status Uso Interno Ativo ou Inativo";

    if (strlen($_POST["produto_fornecedor"]) > 0)    $aux_produto_fornecedor = "'". trim($_POST["produto_fornecedor"]) ."'";
    else                                            $aux_produto_fornecedor = "null";

    if (strlen($_POST["nome_comercial"]) > 0)        $aux_nome_comercial = trim($_POST["nome_comercial"]) ;
    else                                            $aux_nome_comercial = "null";

    if (strlen($_POST["classificacao_fiscal"]) > 0)    $aux_classificacao_fiscal = trim($_POST["classificacao_fiscal"]) ;
    else                                            $aux_classificacao_fiscal = "null";

    if (strlen($_POST["ipi"]) > 0)                    $aux_ipi = trim($_POST["ipi"]) ;
    else                                            $aux_ipi = "null";
    
    if (strlen($_POST["radical_serie"]) > 0)        $aux_radical_serie = "'".trim($_POST["radical_serie"])."'";
    else                                            $aux_radical_serie = 'null';

    if (strlen($_POST["radical_serie2"]) > 0)        $aux_radical_serie2 = "'".trim($_POST["radical_serie2"])."'";
    else                                            $aux_radical_serie2 = 'null';

    if (strlen($_POST["radical_serie3"]) > 0)        $aux_radical_serie3 = "'".trim($_POST["radical_serie3"])."'";
    else                                            $aux_radical_serie3 = 'null';

    if (strlen($_POST["radical_serie4"]) > 0)        $aux_radical_serie4 = "'".trim($_POST["radical_serie4"])."'";
    else                                            $aux_radical_serie4 = 'null';

    if (strlen($_POST["radical_serie5"]) > 0)        $aux_radical_serie5 = "'".trim($_POST["radical_serie5"])."'";
    else                                            $aux_radical_serie5 = 'null';

    if (strlen($_POST["radical_serie6"]) > 0)        $aux_radical_serie6 = "'".trim($_POST["radical_serie6"])."'";
    else                                            $aux_radical_serie6= 'null';

    if (strlen($_POST["qtd_etiqueta_os"]) > 0)        $aux_qtd_etiqueta_os = trim($_POST["qtd_etiqueta_os"]) ;
    else                                            $aux_qtd_etiqueta_os = "null";
    
    if (strlen($_POST["marca"]) > 0)                $aux_marca = trim($_POST["marca"]) ;
    else                                            $aux_marca = "null";
    //$radical_serie = trim ($_POST ['radical_serie']);
    
    if (strlen($_POST["valor_troca"]) > 0)    $aux_valor_troca = trim($_POST["valor_troca"]) ; //hd 7474 TAKASHI 23/11/07 
    else                                    $aux_valor_troca = "null";

    if (strlen($_POST["observacao"]) > 0)    $observacao = trim($_POST["observacao"]) ; //113180
    else                                    $observacao = "null";

    $sistema_operacional = (strlen($_POST["sistema_operacional"]) > 0) ? trim($_POST["sistema_operacional"]) : "null";

    $serie_inicial = strtoupper($_POST["serie_inicial"]);
    $serie_final   = strtoupper($_POST["serie_final"]);
    $validar_serie = isset($_POST["validar_serie"]) ? 't' : 'f';//HD 256659

    $troca_garantia = $_POST["troca_garantia"];
    $aux_troca_garantia = $troca_garantia;

    if (strlen($troca_garantia) == 0) {
        $aux_troca_garantia = "f";
    }

    $troca_faturada = $_POST["troca_faturada"];
    $aux_troca_faturada = $troca_faturada;

    if (strlen($troca_faturada) == 0) {
        $aux_troca_faturada = "f";
    }

    if (strlen($_POST['link_img']) > 0) {
        $aux_link_img   = ($link_img=$_POST['link_img']);
    }

    $produto_critico         = $_POST['produto_critico'];
    $aux_produto_critico     = $produto_critico;

    $troca_obrigatoria       = $_POST['troca_obrigatoria'];
    $aux_troca_obrigatoria   = $troca_obrigatoria;

    $intervencao_tecnica     = $_POST['intervencao_tecnica'];
    $aux_intervencao_tecnica = $intervencao_tecnica;

    $numero_serie_obrigatorio     = $_POST['numero_serie_obrigatorio'];
    $aux_numero_serie_obrigatorio = $numero_serie_obrigatorio;
    
    if (strlen($numero_serie_obrigatorio) == 0) $aux_numero_serie_obrigatorio = "f";
    if (strlen($troca_obrigatoria) == 0)        $aux_troca_obrigatoria = "f";
    if (strlen($produto_critico) == 0)          $aux_produto_critico = "f";
    if (strlen($intervencao_tecnica) == 0)      $aux_intervencao_tecnica = "f";

    $produto_principal = $_POST['produto_principal'];
    $aux_produto_principal = $produto_principal;
    if (strlen($produto_principal) == 0) $aux_produto_principal = "f";
    
    $aux_locador = trim($_POST["locador"]);
    if (strlen($aux_locador) == 0) $aux_locador = "f";

    $lista_troca = trim($_POST["lista_troca"]);
    $aux_lista_troca = (strlen($lista_troca) == 0) ? "FALSE" : "TRUE";

    if ($login_fabrica == 14 or $login_fabrica == 66) {
        $aux_abre_os = (strlen(trim($_POST["abre_os"])) > 0) ?  trim($_POST["abre_os"]) : 'f';

        $aux_aviso_email = trim($_POST["aviso_email"]);
        $retorno_produto = ($aux_aviso_email == 'f')  ? "'" . date("Y-m-d H:i:s") . "'" : "null";
    }else{
        $aux_abre_os     = 't';
        $aux_aviso_email = 't';
        $retorno_produto = "null";
    }

    # HD 50627
    $link_img = $_POST["link_img"];

    #HD 16207
    if ($login_fabrica<>1 and $login_fabrica <> 96){
        if (strlen($produto) > 0) {
            $sql_referencia = " AND tbl_produto.produto <> $produto ";
        }
		
      $sql = "SELECT produto,referencia,descricao 
                FROM tbl_produto
                JOIN tbl_linha USING(linha)
                WHERE tbl_linha.fabrica      = $login_fabrica 
                AND   tbl_produto.referencia = $aux_referencia
                $sql_referencia
                ";
        $res = pg_query ($con,$sql);
        if (@pg_num_rows($res) > 0){
            $msg_erro .= "Já existe um produto cadastrado com esta referência.";
        }
    }

    if ($troca_obrigatoria == 't' and $produto_critico =='t') {
        $msg_erro .= "Troca obrigatoria e produto critico não podem estar selecionados para o mesmo produto";
    }

    if (strlen($msg_erro) == 0) {
        $res = pg_query ($con,"BEGIN TRANSACTION");
		// echo $produto;
		// exit;
        if (strlen($produto) == 0) {
            ###INSERE NOVO REGISTRO
            $sql = "INSERT INTO tbl_produto (
                        linha                    ,
                        familia                  ,
                        descricao                ,
                        voltagem                 ,
                        referencia               ,
                        garantia                 ,
                        preco                    ,
                        mao_de_obra              ,
                        mao_de_obra_admin        ,
                        mao_de_obra_troca        ,
                        valor_troca_gas          ,
                        ativo                    ,
                        uso_interno_ativo        ,
                        /*off_line                 ,*/
                        nome_comercial           ,
                        classificacao_fiscal     ,
                        ipi                      ,
                        radical_serie            ,
                        radical_serie2           ,
                        radical_serie3           ,
                        radical_serie4           ,
                        radical_serie5           ,
                        radical_serie6           ,
                        numero_serie_obrigatorio ,
                        produto_principal        ,
                        locador                  ,
                        referencia_fabrica       ,
                        code_convention          ,
                        abre_os                  ,
                        aviso_email              ,
                        admin                    ,
                        data_atualizacao         ,
                        troca_obrigatoria        ,
                        produto_critico          ,
                        intervencao_tecnica      ,
                        origem                   ,
                        retorno_produto          ,
                        lista_troca              ,
                        qtd_etiqueta_os          ,
                        valor_troca              ,
                        troca_garantia           ,
                        troca_faturada           ,
                        marca                    ,
                        produto_fornecedor       ,
                        capacidade               ,
                        divisao                  ,
                        imagem                   ,
                        observacao               ,
                        sistema_operacional      ,
                        serie_inicial            ,
                        serie_final              ,
                        validar_serie
                    ) VALUES (
                        $aux_linha                       ,
                        $aux_familia                     ,
                        $aux_descricao                   ,
                        $aux_voltagem                    ,
                        $aux_referencia                  ,
                        $aux_garantia                    ,
                        fnc_limpa_moeda($aux_preco)      ,
                        fnc_limpa_moeda($aux_mao_de_obra),
                        fnc_limpa_moeda($aux_mao_de_obra_admin),
                        fnc_limpa_moeda($aux_mao_de_obra_troca),
                        fnc_limpa_moeda($aux_valor_troca_gas),
                        $aux_ativo                       ,
                        $aux_uso_interno_ativo          ,
                        /*$aux_off_line                    ,*/
                        '$nome_comercial'                ,
                        '$classificacao_fiscal'          ,
                        $aux_ipi                         ,
                        $aux_radical_serie               ,
                        $aux_radical_serie2              ,
                        $aux_radical_serie3              ,
                        $aux_radical_serie4              ,
                        $aux_radical_serie5              ,
                        $aux_radical_serie6              ,
                        '$aux_numero_serie_obrigatorio'  ,
                        '$aux_produto_principal'         ,
                        '$aux_locador'                   ,
                        $aux_referencia_fabrica          ,
                        $aux_code_convention             ,
                        '$aux_abre_os'                   ,
                        '$aux_aviso_email'               ,
                        $login_admin                     ,
                        current_timestamp                ,
                        '$aux_troca_obrigatoria'         ,
                        '$aux_produto_critico'           ,
                        '$aux_intervencao_tecnica'       ,
                        $aux_origem                      ,
                        $retorno_produto                 ,
                        $aux_lista_troca                 ,
                        $aux_qtd_etiqueta_os             ,
                        $aux_valor_troca                 ,
                        '$aux_troca_garantia'            ,
                        '$aux_troca_faturada'            ,
                        $aux_marca                       ,
                        $aux_produto_fornecedor          ,
                        $aux_capacidade                  ,
                        $aux_divisao                     ,
                        '$link_img'                      ,
                        '$observacao'                    ,
                        $sistema_operacional             ,
                        '$serie_inicial'                 ,
                        '$serie_final'                   ,
                        '$validar_serie'
                    );";
					
            $res = pg_query ($con,$sql);
			
			#HD 335150 INICIO
			if($login_fabrica == 15){

				$sql = "select currval('seq_produto') AS produto;";
				$res = pg_query($con,$sql);
				
				$produto_aux = trim(pg_fetch_result($res,0,produto));
				$totalSerie = count($_POST['produto_serie_in_out']);

				for($i=0;$i<$totalSerie;$i++){

					$serie_in = $_POST['serie_in'][$i];
					$serie_out = $_POST['serie_out'][$i];
					$produto_serie_in_out = $_POST['produto_serie_in_out'][$i];

					if(strlen($produto_serie_in_out) == 0){
						
						$sql_serie_in_out = "
							INSERT INTO tbl_produto_serie
							(
								fabrica      ,
								produto      ,
								serie_inicial,
								serie_final
							) VALUES (
								$login_fabrica,
								$produto_aux  ,
								'$serie_in'     ,
								'$serie_out'    
							);
						";
						
						$res_serie = pg_query ($con, $sql_serie_in_out);
					}
					
				}
					
			}
						
            $msg_erro = pg_errormessage($con);

            /* ----------------------- INICIO - IGOR HD 2846 -------------------------*/

			// Atribuir o país como sendo o Brasil quando for 515 | Andre Ribeiro, 514 | Mara, 516 | Daniel
             if($login_fabrica == 20 and ($login_admin == 514 or $login_admin == 515 or $login_admin == 516 or $login_admin == 1550 or $login_admin== 3128)) {
				$sql = "select currval('seq_produto') as produto;";
//                    $res = pg_query ($con,$sql);

				$produto = trim(pg_fetch_result($res,0,produto));
				//$sql = "SELECT produto,pais FROM tbl_produto_pais WHERE produto=$produto AND pais = '$pais'";
				//$res = @pg_query ($con,$sql);
				if(strlen($produto)>0){
					$sql = "INSERT INTO tbl_produto_pais (produto,pais) VALUES ($produto,'BR')";
//                        $res = pg_query ($con,$sql);
				}

            }
            /* ----------------------- FIM - IGOR HD 2846 -------------------------*/

		}else{
            ###ALTERA REGISTRO
            $sql = "UPDATE tbl_produto SET
                            linha                    = $aux_linha                             ,
                            familia                  = $aux_familia                           ,
                            descricao                = $aux_descricao                         ,
                            voltagem                 = $aux_voltagem                          ,
                            referencia               = $aux_referencia                        ,
                            garantia                 = $aux_garantia                          ,
                            preco                    = fnc_limpa_moeda($aux_preco)            ,
                            mao_de_obra              = fnc_limpa_moeda($aux_mao_de_obra)      ,
                            mao_de_obra_admin        = fnc_limpa_moeda($aux_mao_de_obra_admin),
                            mao_de_obra_troca        = fnc_limpa_moeda($aux_mao_de_obra_troca),
                            valor_troca_gas          = fnc_limpa_moeda($aux_valor_troca_gas)  ,
                            ativo                    = $aux_ativo                             ,
                            uso_interno_ativo        = $aux_uso_interno_ativo                 ,
                            /*off_line                 = $aux_off_line                        ,*/
                            nome_comercial           = '$nome_comercial'                      ,
                            classificacao_fiscal     = '$classificacao_fiscal'                ,
                            ipi                      = $aux_ipi                               ,
                            radical_serie            = $aux_radical_serie                     ,
                            radical_serie2           = $aux_radical_serie2                    ,
                            radical_serie3           = $aux_radical_serie3                    ,
                            radical_serie4           = $aux_radical_serie4                    ,
                            radical_serie5           = $aux_radical_serie5                    ,
                            radical_serie6           = $aux_radical_serie6                    ,
                            numero_serie_obrigatorio = '$aux_numero_serie_obrigatorio'        ,
                            produto_principal        = '$aux_produto_principal'               ,
                            locador                  = '$aux_locador'                         ,
                            referencia_fabrica       = $aux_referencia_fabrica                ,
                            code_convention          = $aux_code_convention                   ,
                            abre_os                  = '$aux_abre_os'                         ,
                            aviso_email              = '$aux_aviso_email'                     ,
                            admin                    = $login_admin                           ,
                            data_atualizacao         = current_timestamp                      ,
                            troca_obrigatoria        = '$aux_troca_obrigatoria'               ,
                            produto_critico          = '$aux_produto_critico'                 ,
                            intervencao_tecnica      = '$aux_intervencao_tecnica'             ,
                            origem                   = $aux_origem                            ,
                            retorno_produto          = $retorno_produto                       ,
                            lista_troca              = $aux_lista_troca                       ,
                            qtd_etiqueta_os          = $aux_qtd_etiqueta_os                   ,
                            valor_troca              = $aux_valor_troca                       ,
                            troca_garantia           = '$aux_troca_garantia'                  ,
                            troca_faturada           = '$aux_troca_faturada'                  ,
                            marca                    = $aux_marca                             ,
                            produto_fornecedor       = $aux_produto_fornecedor                ,
                            capacidade               = $aux_capacidade                        ,
                            divisao                  = $aux_divisao                           ,
                            imagem                   = '$link_img'                            ,
                            observacao               = '$observacao'                          ,
                            sistema_operacional      = $sistema_operacional                   ,
                            serie_inicial            = '$serie_inicial'                       ,
                            serie_final              = '$serie_final'                         ,
                            validar_serie            = '$validar_serie'
                    FROM tbl_linha
                    WHERE  tbl_produto.linha         = tbl_linha.linha
                    AND    tbl_linha.fabrica         = $login_fabrica
                    AND    tbl_produto.produto       = $produto;";

            $res = @pg_query ($con,$sql);
			
			#HD 335150 INICIO
			if($login_fabrica == 15){
					
				$linhas = count($_POST['produto_serie_in_out']);

				for($i=0;$i<$linhas;$i++){
				
					$serie_in = trim($_POST['serie_in'][$i]);
					$serie_out = trim($_POST['serie_out'][$i]);
					$produto_serie_in_out = $_POST['produto_serie_in_out'][$i];
					$excluir = $_POST['apagar_serie'][$produto_serie_in_out];
					
					if ($excluir == 'excluir'){
						$sql_delete = " DELETE FROM tbl_produto_serie 
										WHERE tbl_produto_serie.produto_serie = $produto_serie_in_out
										and tbl_produto_serie.produto = $produto
						";
						$res = pg_query($con,$sql_delete);
					}
					
					if (strlen($produto_serie_in_out) > 0 && strlen($excluir) == 0){
						$sql_serie_in_out = "							
							UPDATE tbl_produto_serie SET 
								fabrica       = $login_fabrica  ,
								produto       = $produto        ,
								serie_inicial = '$serie_in'     ,
								serie_final   = '$serie_out'    
							WHERE	tbl_produto_serie.produto = $produto
							AND		tbl_produto_serie.produto_serie = $produto_serie_in_out";
						$res_serie = pg_query ($con, $sql_serie_in_out);
					}
					
					if(strlen($produto_serie_in_out) == 0 && strlen($msg_erro)==0 && strlen($serie_in) > 0){
						
						$sql_serie_in_out = "
							INSERT INTO tbl_produto_serie
							(
								fabrica      ,
								produto      ,
								serie_inicial,
								serie_final
							) VALUES (
								$login_fabrica,
								$produto ,
								'$serie_in'     ,
								'$serie_out'    
							);
						";
						
						$res_serie = pg_query ($con, $sql_serie_in_out);
					}
				}
					
			}

        }

        $msg_erro = pg_errormessage($con);

        //--=== TRADUÇÂO DOS PRODUTOS =========================================
        $idioma           = $_POST["idioma"];
        $idioma_novo      = $_POST["idioma_novo"];
        $descricao_idioma = $_POST["descricao_idioma"];

        if (strlen($idioma) == 2 AND strlen($descricao_idioma) > 0 AND strlen($produto) > 0) {

            if (strlen($idioma_novo) == 2) {

                $sql = "INSERT INTO tbl_produto_idioma (produto,
                                                        descricao,
                                                        idioma
                                              ) VALUES ($produto,
                                                        '$descricao_idioma',
                                                        '$idioma')";

            } else {

                $sql = "UPDATE tbl_produto_idioma SET descricao = '$descricao_idioma'
                         WHERE produto = $produto 
                           AND idioma  = '$idioma'";

            }

            $res      = @pg_query ($con,$sql);
            $msg_erro = pg_errormessage($con);

        }

        $qtde_item = $_POST['qtde_item'];

        for ($i = 0; $i < $qtde_item; $i++) {

            $referencia_opcao    = $_POST["referencia_opcao_".$i];
            $descricao_opcao     = $_POST["descricao_opcao_".$i];
            $produto_opcao       = $_POST["produto_opcao_".$i];
            $produto_troca_opcao = $_POST["produto_troca_opcao_".$i];
            $voltagem_opcao      = $_POST["voltagem_opcao_".$i];
            $kit_opcao           = $_POST["kit_opcao_".$i];

            if (strlen($voltagem_opcao) == 0 and strlen($referencia_opcao) > 0) {
                $msg_erro = "Informe a voltagem para o produto de opção troca $referencia_opcao. Clique na lupa para pesquisar.";
                $erro_linha = "erro_linha" . $i;
                $$erro_linha = 1 ;
                break;
            }

            if (strlen($msg_erro) == 0 and strlen($referencia_opcao)) {

                $sql = "SELECT produto
                          FROM tbl_produto
                          JOIN tbl_linha using(linha)
                         WHERE referencia = '$referencia_opcao'
                           AND voltagem   = '$voltagem_opcao'
                           AND fabrica    = $login_fabrica
                         LIMIT 1";

                $res = pg_query($con, $sql);

                if (pg_num_rows($res) == 0) {
                    $msg_erro = "Produto com referência $referencia_opcao e voltagem $voltagem_opcao não encontrado.";
                } else {
                    $produto_opcao = pg_fetch_result($res,0,0);
                }

            }

            if (strlen($msg_erro) == 0) {

                if (strlen($produto_troca_opcao) > 0) {

                    if (strlen($referencia_opcao) == 0) {

                        $sql = "DELETE FROM tbl_produto_troca_opcao
                                WHERE produto_troca_opcao = $produto_troca_opcao";

                        $res = pg_query($con, $sql);

                        if (strlen( pg_errormessage($con) ) > 0) {
                            $msg_erro = pg_errormessage($con) ;
                        }

                    } else {

                        $sql = "UPDATE tbl_produto_troca_opcao SET
                                    produto_opcao = $produto_opcao,
                                    kit = $kit_opcao
                                WHERE produto_troca_opcao = $produto_troca_opcao";

                        $res = pg_query($con, $sql);

                        if (strlen(pg_errormessage($con) ) > 0) {
                            $msg_erro = pg_errormessage($con);
                        }

                    }

                } else {

                    if (strlen($referencia_opcao)>0) {
                        $sql = "INSERT INTO tbl_produto_troca_opcao (produto, produto_opcao, kit)
                                VALUES ($produto, $produto_opcao, $kit_opcao)";
                        $res = pg_query($con, $sql);
                        if (strlen( pg_errormessage($con) ) > 0) {
                            $msg_erro = pg_errormessage($con) ;
                        }
                    }

                }

            }

        }
	
        if (strlen($msg_erro) == 0) {
            $res = pg_query ($con,"COMMIT TRANSACTION");
            header ('Location: '.$PHP_SELF.'?produto='.$produto.'&msg=Gravado com Sucesso!');
            exit;
        } else {

            ###ABORTA OPERAÇÃO DE INCLUSÃO/EXLUSÃO/ALTERAÇÃO E RECARREGA CAMPOS
            $produto                    = $_POST["produto"];
            $marca                      = $_POST["marca"];
            $linha                      = $_POST["linha"];
            $familia                    = $_POST["familia"];
            $descricao                  = $_POST["descricao"];
            $referencia                 = $_POST["referencia"];
            $voltagem                   = $_POST["voltagem"];
            $garantia                   = $_POST["garantia"];
            $preco                      = $_POST["preco"];
            $mao_de_obra                = $_POST["mao_de_obra"];
            $mao_de_obra_admin          = $_POST["mao_de_obra_admin"];
            $mao_de_obra_troca          = $_POST["mao_de_obra_troca"];
            $valor_troca_gas            = $_POST["valor_troca_gas"];
            $ativo                      = $_POST["ativo"];
            $uso_interno_ativo          = $_POST["uso_interno_ativo"];
            $nome_comercial             = $_POST["nome_comercial"];
            $classificacao_fiscal       = $_POST["classificacao_fiscal"];
            $ipi                        = $_POST["ipi"];
            $radical_serie              = $_POST["radical_serie"];
            $radical_serie2             = $_POST["radical_serie2"];
            $radical_serie3             = $_POST["radical_serie3"];
            $radical_serie4             = $_POST["radical_serie4"];
            $radical_serie5             = $_POST["radical_serie5"];
            $radical_serie6             = $_POST["radical_serie6"];
            $numero_serie_obrigatorio   = $_POST["numero_serie_obrigatorio"];
            $troca_obrigatoria          = $_POST["troca_obrigatoria"];
            $produto_critico            = $_POST["produto_critico"];
            $intervencao_tecnica        = $_POST["intervencao_tecnica"];
            $produto_principal          = $_POST["produto_principal"];
            $locador                    = $_POST["locador"];
            $referencia_fabrica         = trim($_POST["referencia_fabrica"]);
            $code_convention            = $_POST["code_convention"];
            $abre_os                    = $_POST["abre_os"];
            $origem                     = $_POST["origem"];
            $produto_fornecedor         = $_POST["produto_fornecedor"];
            $aviso_email                = $_POST["aviso_email"];
            $qtd_etiqueta_os            = $_POST["qtd_etiqueta_os"];
            $lista_troca                = $_POST["lista_troca"];
            $valor_troca                = $_POST["valor_troca"]; 
            $troca_garantia             = $_POST["troca_garantia"]; 
            $troca_faturada             = $_POST["troca_faturada"]; 
            $sistema_operacional        = $_POST['sistema_operacional'];
            $serie_inicial              = $_POST['serie_inicial'];
            $serie_final                = $_POST['serie_final'];

            if (strpos ($msg_erro,"duplicate key violates unique constraint \"tbl_produto_referencia_pesquisa\"") > 0)
            $msg_erro = "Referência para esta linha de produtos já existe e não pode ser duplicada.";
            
            if (strpos ($msg_erro,"duplicate key violates unique constraint \"tbl_produto_unico\"") > 0)
            $msg_erro = "Referência para esta linha de produtos já existe e não pode ser duplicada.";

            $res = pg_query ($con,"ROLLBACK TRANSACTION");

        }

    }//fim if msg erro

}

###CARREGA REGISTRO
$produto = $_GET['produto'];

if (strlen($produto) > 0) {

    $sql = "SELECT  tbl_produto.produto                            ,
                    tbl_produto.linha                              ,
                    tbl_produto.familia                            ,
                    tbl_produto.descricao                          ,
                    tbl_produto.voltagem                           ,
                    tbl_produto.referencia                         ,
                    tbl_produto.garantia                           ,
                    tbl_produto.preco                              ,
                    tbl_produto.mao_de_obra                        ,
                    tbl_produto.mao_de_obra_admin                  ,
                    tbl_produto.mao_de_obra_troca                  ,
                    tbl_produto.valor_troca_gas                    ,
                    tbl_produto.ativo                              ,
                    tbl_produto.uso_interno_ativo                  ,
                    tbl_produto.nome_comercial                     ,
                    tbl_produto.classificacao_fiscal               ,
                    tbl_produto.ipi                                ,
                    tbl_produto.origem                             ,
                    tbl_produto.radical_serie                      ,
                    tbl_produto.radical_serie2                     ,
                    tbl_produto.radical_serie3                     ,
                    tbl_produto.radical_serie4                     ,
                    tbl_produto.radical_serie5                     ,
                    tbl_produto.radical_serie6                     ,
                    tbl_produto.numero_serie_obrigatorio           ,
                    tbl_produto.produto_principal                  ,
                    tbl_produto.locador                            ,
                    tbl_produto.referencia_fabrica                 ,
                    tbl_produto.code_convention                    ,
                    tbl_produto.abre_os                            ,
                    tbl_produto.aviso_email                        ,
                    tbl_produto.troca_obrigatoria                  ,
                    tbl_produto.produto_critico                    ,
                    tbl_produto.intervencao_tecnica                ,
                    tbl_produto.qtd_etiqueta_os                    ,
                    tbl_produto.lista_troca                        ,
                    tbl_admin.login                                ,
                    tbl_marca.marca                                ,
                    tbl_produto.valor_troca                        ,
                    tbl_produto.troca_garantia                     ,
                    tbl_produto.troca_faturada                     ,
                    tbl_produto.produto_fornecedor                 ,
                    to_char(tbl_produto.data_atualizacao, 'DD/MM/YYYY HH24:MI') AS data_atualizacao,
                    tbl_produto.capacidade                         ,
                    tbl_produto.divisao                            ,
                    tbl_produto.observacao                         ,
                    tbl_produto.imagem                             ,
                    tbl_produto.sistema_operacional                ,
                    tbl_produto.serie_inicial                      ,
                    tbl_produto.serie_final                        ,
                    tbl_produto.validar_serie                      
					
            FROM    tbl_produto
            JOIN    tbl_linha ON tbl_linha.linha = tbl_produto.linha
			
            LEFT JOIN tbl_admin ON tbl_admin.admin = tbl_produto.admin
            LEFT JOIN tbl_marca ON tbl_marca.marca = tbl_produto.marca
			
            WHERE   tbl_linha.fabrica   = $login_fabrica
            AND     tbl_produto.produto = $produto;";

    $res = pg_query ($con,$sql);
    
    if (pg_num_rows($res) > 0) {

        $produto                  = trim(pg_fetch_result($res,0,'produto'));
        $linha                    = trim(pg_fetch_result($res,0,'linha'));
        $familia                  = trim(pg_fetch_result($res,0,'familia'));
        $descricao                = trim(pg_fetch_result($res,0,'descricao'));
        $referencia               = trim(pg_fetch_result($res,0,'referencia'));
        $voltagem                 = trim(pg_fetch_result($res,0,'voltagem'));
        $garantia                 = trim(pg_fetch_result($res,0,'garantia'));
        $preco                    = trim(pg_fetch_result($res,0,'preco'));
        $mao_de_obra              = trim(pg_fetch_result($res,0,'mao_de_obra'));
        $mao_de_obra_admin        = trim(pg_fetch_result($res,0,'mao_de_obra_admin'));
        $mao_de_obra_troca        = trim(pg_fetch_result($res,0,'mao_de_obra_troca'));
        $valor_troca_gas          = trim(pg_fetch_result($res,0,'valor_troca_gas'));
        $ativo                    = trim(pg_fetch_result($res,0,'ativo'));
        $uso_interno_ativo        = trim(pg_fetch_result($res,0,'uso_interno_ativo'));
        $nome_comercial           = trim(pg_fetch_result($res,0,'nome_comercial'));
        $classificacao_fiscal     = trim(pg_fetch_result($res,0,'classificacao_fiscal'));
        $ipi                      = trim(pg_fetch_result($res,0,'ipi'));
        $radical_serie            = trim(pg_fetch_result($res,0,'radical_serie'));
        $radical_serie2           = trim(pg_fetch_result($res,0,'radical_serie2'));
        $radical_serie3           = trim(pg_fetch_result($res,0,'radical_serie3'));
        $radical_serie4           = trim(pg_fetch_result($res,0,'radical_serie4'));
        $radical_serie5           = trim(pg_fetch_result($res,0,'radical_serie5'));
        $radical_serie6           = trim(pg_fetch_result($res,0,'radical_serie6'));
        $numero_serie_obrigatorio = trim(pg_fetch_result($res,0,'numero_serie_obrigatorio'));
        $produto_principal        = trim(pg_fetch_result($res,0,'produto_principal'));
        $locador                  = trim(pg_fetch_result($res,0,'locador'));
        $referencia_fabrica       = trim(pg_fetch_result($res,0,'referencia_fabrica'));
        $code_convention          = trim(pg_fetch_result($res,0,'code_convention'));
        $abre_os                  = trim(pg_fetch_result($res,0,'abre_os'));
        $aviso_email              = trim(pg_fetch_result($res,0,'aviso_email'));
        $origem                   = trim(pg_fetch_result($res,0,'origem'));
        $produto_fornecedor       = trim(pg_fetch_result($res,0,'produto_fornecedor'));
        $admin                    = trim(pg_fetch_result($res,0,'login'));
        $data_atualizacao         = trim(pg_fetch_result($res,0,'data_atualizacao'));
        $troca_obrigatoria        = trim(pg_fetch_result($res,0,'troca_obrigatoria'));
        $produto_critico          = trim(pg_fetch_result($res,0,'produto_critico'));
        $intervencao_tecnica      = trim(pg_fetch_result($res,0,'intervencao_tecnica'));
        $qtd_etiqueta_os          = trim(pg_fetch_result($res,0,'qtd_etiqueta_os'));
        $lista_troca              = trim(pg_fetch_result($res,0,'lista_troca'));
        $marca                    = trim(pg_fetch_result($res,0,'marca'));
        $valor_troca              = trim(pg_fetch_result($res,0,'valor_troca'));
        $troca_garantia           = trim(pg_fetch_result($res,0,'troca_garantia'));
        $troca_faturada           = trim(pg_fetch_result($res,0,'troca_faturada'));
        $capacidade               = trim(pg_fetch_result($res,0,'capacidade'));
        $divisao                  = trim(pg_fetch_result($res,0,'divisao'));
        $observacao               = trim(pg_fetch_result($res,0,'observacao'));
        $link_img                 = trim(pg_fetch_result($res,0,'imagem'));
        $sistema_operacional      = trim(pg_fetch_result($res,0,'sistema_operacional'));
        $serie_inicial            = trim(pg_fetch_result($res,0,'serie_inicial'));
        $serie_final              = trim(pg_fetch_result($res,0,'serie_final'));
        $validar_serie            = trim(pg_fetch_result($res,0,'validar_serie'));
		// $serie_in                 = trim(pg_fetch_result($res,0,'serie_in'));
		// $serie_out                = trim(pg_fetch_result($res,0,'serie_out'));
    }

}

$msg = $_GET['msg'];
$layout_menu = "cadastro";
$title = "Cadastramento De Produtos";
echo "<center>";
include 'cabecalho.php';
?>

<link rel="stylesheet" type="text/css" href="js/thickbox.css" media="screen">
<script type="text/javascript"    src="js/jquery-1.2.1.pack.js"></script>
<script type="text/javascript"    src="js/thickbox.js"></script>
<script type="text/javascript"    src="js/jquery.alphanumeric.js"></script>

<script language="JavaScript">

	$( document ).ready( function(){
			$('input[@name=garantia]').numeric();
			$('input[@name=mao_de_obra_admin]').numeric({ allow : ',.' });
			$('input[@name=mao_de_obra]').numeric({ allow : ',.' });
			$('input[@name=qtd_etiqueta_os]').numeric();
		
		} )

    function checarNumero(campo) {
        var num = campo.value.replace(",",".");
        campo.value = parseFloat(num).toFixed(2);
        if (campo.value=='NaN') {
            campo.value='';
        }
    }

    function checarNumeroInteiro(campo){
        campo.value = parseInt(campo.value);
        if (campo.value=='NaN') {
            campo.value='';
        }
    }

	

    function fnc_pesquisa_produto (campo, tipo) {
		
        if (campo.value != "") {
            var url = "";
            url = "produto_pesquisa.php?retorno=<? echo $PHP_SELF ?>&forma=reload&campo=" + campo.value + "&tipo=" + tipo ;
            janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=600, height=400, top=0, left=0");
            janela.retorno = "<? echo $PHP_SELF ?>";
            janela.referencia= document.frm_produto.referencia;
            janela.descricao = document.frm_produto.descricao;
            janela.linha     = document.frm_produto.linha;
            janela.familia   = document.frm_produto.familia;
            janela.focus();
        }

		else
			alert("Informe toda ou parte da informação para realizar a pesquisa!");
    }

    function qtdeLinhas(campo) {
        var linha = 0;
        if (campo.value > 0){
            $(".tabela_item tr").each( function (){
                linha = parseInt( $(this).attr("rel") );
                if (linha  +1 > campo.value) {
                    $(this).css('display','none');
                }else{
                    $(this).css('display','');
                }
            });
        }
    }

    function fnc_pesquisa_produto_opcao (campo, campo2, campo3, campo4, tipo) {
        if (tipo == "referencia" ) {
            var xcampo = campo;
        }

        if (tipo == "descricao" ) {
            var xcampo = campo2;
        }

        if (xcampo.value != "") {
            var url = "";
            url = "produto_pesquisa_2.php?campo=" + xcampo.value + "&tipo=" + tipo + "&lbm=sim" ;
            janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=500, height=400, top=18, left=0");
            janela.referencia    = campo;
            janela.descricao    = campo2;
            janela.produto        = campo3;
            janela.voltagem        = campo4;
            janela.focus();
        }

		else
			alert("Informe toda ou parte da informação para realizar a pesquisa!");
    }
	
	function validaForm(){
		<?php if($login_fabrica == 15):?>
			if(validaSerie()){
				document.frm_produto.btnacao.value='gravar' ; 
				document.frm_produto.submit();
			}
		<?php else:?>
			document.frm_produto.btnacao.value='gravar' ; 
			document.frm_produto.submit();
		<?php endif;?>
	}
	
	<?php if($login_fabrica == 15):?>
		var totals = 0;
		function adiciona(){
			totals++;
			tbl = document.getElementById("table_serie_in_out");
	 
			var novaLinha = tbl.insertRow(-1);
			
			var novaCelula;
			if(totals%2==0) cl = "#F7F5F0";
			else cl = "#F1F4FA";
			
			novaCelula = novaLinha.insertCell(0);
			novaCelula.align = "center";
			novaCelula.height= "25px";
			novaCelula.style.backgroundColor = cl
			novaCelula.innerHTML = "<input type='hidden' name='produto_serie_in_out[]'><input class='frm serie' rel='"+totals+"' id='serie_in_"+totals+"' type='text' name='serie_in[]' size='15' maxlength='30'>";

			novaCelula = novaLinha.insertCell(1);
			novaCelula.align = "center";
			novaCelula.height= "25px";
			novaCelula.style.backgroundColor = cl;
			novaCelula.innerHTML = "<input class='frm serie' rel='"+totals+"' id='serie_out_"+totals+"' type='text' name='serie_out[]' size='15' maxlength='30'>";
	 
			novaCelula = novaLinha.insertCell(2);
			novaCelula.align = "center";
			novaCelula.height= "25px";
			novaCelula.style.backgroundColor = cl;
			novaCelula.innerHTML = "&nbsp;";

		}
		
		function indiceAlfabeto(letra){
			var str = "AB";//CDEFGHIJKLMNOPQRSTUVWXYZ";
			var total = str.length;
			for(var i=0; i<total; i++){
				if(str.charAt(i) == letra){
					return i;
					break;
				}
			}
		}
		
		//Verifica se o numero de série corresponde
		//A Expressão Regular para o número de Série da Latinatec corresponde aos dados
		//1 => Fábricante: pode ser 1, 4 ou 9
		//2 => Versão: letra
		//3 => Mês: letra: A = JAN; B = FEV; C = MAR...
		//4 => Ano: letra: A = 1995; B = 1995; ... Q = 2011
		//5 ao 8 => Sequencial numérico, sempre maior que 1000
		function validaSerie(){

			//Retorno já verdadeiro, caso não tenha uma série correta, retorna falso
			var retorno = true;
			var sa_in = ""; //Serie anterior de entrada
			var sa_out = ""; //Serie anterior de saída
			var s_in = ""; //Serie de entrada
			var s_out = ""; //Serie de saída
			var ident; //id da linha
			
			var serie_inicial = $("#serie_inicial").val();
			var serie_final = $("#serie_final").val();
			
			//Recebe em formato de RegEx o filtro de series
			var serie = new RegExp('[14]['+serie_inicial+'-'+serie_final+'][A-L][A-Z][1-9][0-9]{2,5}');
			var serie_9 = new RegExp('[9]['+serie_inicial+'-'+serie_final+'][A-L][N-Z][1-9][0-9]{2,5}');
			
			//Percorre todas as séries pra ver se estão válidas
			$('.serie').each(function(){
				
				ident = $(this).attr('rel');
				s_in = $('#serie_in_'+ident).val();
				s_out = $('#serie_out_'+ident).val();

				if(s_in.length > 0 && !$('#apagar_serie_'+ident).is(':checked')){
				
					if(s_out.length == 0){
				
						if (!$('#serie_in_'+ident).val().match(serie)){
							retorno = false;
							return false;
						}
					
					}else if(s_in.substr(1,1) == s_out.substr(1,1)){
						
						if (!$(this).val().match(serie)){
							retorno = false;
							return false;
						}

					}else{
						retorno = false;
						return false;
					}
				}

				sa_in = s_in;
				sa_out = s_out;
			
			});
			
			if(retorno == false){
				$('#msg_erro').css('display','');
				$('.sucesso').css('display','none');
				$('#msg_erro').html('<td colspan="6">Número de série inválido</td>');
				$('body').animate({scrollTop:0});
			}
			
			return retorno;
			
		}
	<?php endif;?>
</script>

<style type='text/css'>
.Div{
	border:              #6699cc 1px solid;
	font:             normal normal 10pt arial ;
	color:            #000;
	background-color: #ffffff;
}
.titulo_tabela{
	background-color:#596d9b;
	font: bold 14px "Arial";
	color:#FFFFFF;
	text-align:center;
}
.titulo_coluna{
	background-color:#596d9b;
	font: bold 11px "Arial";
	color:#FFFFFF;
	text-align:center;
}
.msg_erro{
	background-color:#FF0000;
	font: bold 16px "Arial";
	color:#FFFFFF;
	text-align:center;
}
.sucesso{
	background-color:green;
	font: bold 16px "Arial";
	color:#FFFFFF;
	text-align:center;
}
.formulario{
	background-color:#D9E2EF;
	font:11px Arial;
}
.subtitulo {
    background-color: #7092BE;
	color: #FFFFFF;
    font: bold 14px Arial;
    text-align: center;
}
table.tabela tr td{
	font-family: verdana;
	font-size: 11px;
	border-collapse: collapse;
	border:1px solid #596d9b;
}
</style>
<?php $onsubmit = ($login_fabrica == 15) ? 'onsubmit="return validaSerie()"' : null;?>


<form name="frm_produto" method="post" enctype='multipart/form-data' action="<? $PHP_SELF ?>" <?php echo $onsubmit;?>>
	<input type="hidden" name="produto" value="<? echo $produto; ?>">
	<table width="700" border="0" cellpadding="2" cellspacing="1" class="formulario" align='center'>
		<? if (strlen($msg_erro) > 0) { ?>
			<tr class="msg_erro">
				<td colspan="100%"><? echo $msg_erro; ?></td>
			</tr>
		<? } ?>
		<tr class="msg_erro" id="msg_erro" style="display:none;">
			<td colspan="100%"></td>
		</tr>

		<? if (strlen($msg) > 0 AND strlen($msg_erro)==0) { ?>
			<tr class="sucesso">
				<td colspan="100%"><? echo $msg; ?></td>
			</tr>
		<? } ?>

		<tr class="titulo_tabela"><td colspan="100%">Cadastrar Produtos</td></tr>
			<tr><td colspan="4">&nbsp;</td>
		</tr>
		<tr>
			<td width="20">&nbsp;</td>
			<td width="210">Referência *</td>
			<td colspan=4>Descrição *</td><?php
			if ($login_fabrica == 19 or $login_fabrica == 96) {
				echo "<td>Preço</td>";
			}?>
		</tr>
		<tr>
			<td width="20">&nbsp;</td>
			<td nowrap><input type="text" class="frm" name="referencia" value="<? echo $referencia ?>" size="14" maxlength="20" <? if ($login_fabrica == 5) { ?> onblur="javascript: fnc_pesquisa_produto (document.frm_produto.referencia, 'referencia')" <? } ?>><a href='#'><img src="imagens/lupa.png" onclick="javascript: fnc_pesquisa_produto(document.frm_produto.referencia, 'referencia')"></a></td>
			<td colspan=4 nowrap><input type="text" class="frm" size="50" name="descricao" value="<? echo $descricao ?>" maxlength="70" <? if ($login_fabrica == 5) { ?> onblur="javascript: fnc_pesquisa_produto (document.frm_produto.descricao, 'descricao')" <? } ?>><a href='#'><img src="imagens/lupa.png" onclick="javascript: fnc_pesquisa_produto(document.frm_produto.descricao, 'descricao')"></a></td><?php 
			if ($login_fabrica == 19 or $login_fabrica == 96) {
				echo "<td>R$ <input type='text' class='frm' name='preco' value='$preco' size='7' maxlength='20'></td>";
			}?>
		</tr>
		<tr><td colspan="4">&nbsp;</td></tr>
	</table>

	<table width="700" border="0" cellpadding="0" cellspacing="1" class="formulario" align='center'>
		<tr>
			<td width="20">&nbsp;</td>
			<td width="*">Garantia *</td>
			<td width="*"><acronym title="Valor da mão-de-obra de OS digitada pelo posto autorizado">M.Obra <?if ($login_fabrica == 14 or $login_fabrica == 66) echo "ASTEC"; else echo "Posto"; ?> <? if($login_fabrica != 96) echo "*"; ?></acronym></td><?php
			if ($login_fabrica != 86) {//HD 387824?>
				<td><acronym title="Valor da mão-de-obra de OS digitada pelo administrador/fábrica">M.Obra  <?if ($login_fabrica == 14 or $login_fabrica == 66) echo "LAI"; else echo "Admin"; ?> </acronym></td><?php
			}
			if ($login_fabrica == 3) {?> 
				<td><acronym title="Valor adicional referente à recarga de gás.">Valor Recarga de Gás</acronym></td><?php
			}
			if ($login_fabrica == 35) {?> 
				<td><acronym title="Valor da mão-de-obra de OS para Troca">M.Obra Troca</acronym></td><?php
			}?>
		</tr>
		<tr>
			<td width="20">&nbsp;</td>
			<td><input type="text" class="frm" name="garantia" value="<? echo $garantia ?>" size="5" maxlength="20"> meses</td>
			<td>R$ <input type="text" class="frm" name="mao_de_obra" value="<? echo $mao_de_obra ?>" size="7" maxlength="20"></td><?php
			if ($login_fabrica != 86) {//HD 387824?>
				<td>R$ <input type="text" class="frm" name="mao_de_obra_admin" value="<? echo $mao_de_obra_admin ?>" size="7" maxlength="20"></td><?php
			}
			if ($login_fabrica == 3) {?>
				<td>R$ <input type="text" class="frm" name="valor_troca_gas" value="<? echo $valor_troca_gas ?>" size="7" maxlength="20"></td><?php
			}
			if ($login_fabrica == 35) {?>
				<td>R$ <input type="text" class="frm" name="mao_de_obra_troca" value="<?=$mao_de_obra_troca?>" size="7" maxlength="20"></td><?php
			}?>
		</tr><?php
		if ($login_fabrica == 5) {
			echo "<tr><td colspan='4'>&nbsp;</td></tr>";
			echo "<tr>";
			echo "<td width='20'>&nbsp;</td>";
			echo "<td nowrap colspan='3'>Link para imagem:&nbsp;";
			echo "<input acronym title='O caminho deve ser digitado por completo' type='text' ";
			echo "class='frm' name='link_img' value='$link_img' size='43'></td>";
			echo "</tr>";
		}?>
		<tr><td colspan="4">&nbsp;</td></tr>
	</table>

	<?
	if($login_fabrica == 20 AND  strlen($produto)>0){
		echo "<table width='700'align='center'><tr><td><div class='Div'>";
		echo "<table width='100%' border='0' class='formulario'><tr><td valign='top'>";
		
		$sql = "SELECT * FROM tbl_produto_idioma WHERE produto = $produto";
		$res2 = pg_query ($con,$sql);

		if (pg_num_rows($res2) > 0) {
			$produto                  = trim(pg_fetch_result($res2,0,produto));
			$idioma                   = trim(pg_fetch_result($res2,0,idioma));
			$descricao_idioma         = trim(pg_fetch_result($res2,0,descricao));
		}else{
			echo "Não existe descrição para esse produto em outro idioma, preencha o campo abaixo para inserir uma.<br>";
			echo "<input type='hidden' name='idioma_novo' value='ES'>";
		}
		echo "<b>Espanhol</b><br>";
		echo "Descrição: <input  type='text' class='frm' name='descricao_idioma' value='$descricao_idioma' size='30' maxlength='50'><br><br>";
		echo "<input type='hidden' name='idioma' value='ES'>";
		
		echo "</td><td width:1px;></td>
		<td valign='top' >";

		$sql = "SELECT pais, garantia FROM tbl_produto_pais WHERE produto = $produto";
		$res2 = pg_query ($con,$sql);
		if (pg_num_rows($res2) > 0) {
			echo "<TABLE class='formulario' width='100%'>";
			echo "<TR>";
			echo "<TD colspan='3' class='titulo_tabela'><b>Máquina Liberada para</b>";
			echo "</TD>";
			echo "</TR>";
			echo "<TR>";
			echo "<TD align='center'><b>País </b></TD>";
			echo "<TD align='center'><b>Garantia</b></TD>";
			echo "<TD><b>Ação</b></TD>";
			echo "</TD>";
			echo "</TR>";

			for($i = 0 ; $i < pg_num_rows($res2) ; $i++ ){
				$pais                  = trim(pg_fetch_result($res2,$i,pais));
				$garantia_pais         = trim(pg_fetch_result($res2,$i,garantia));
				echo "<TR>";
				echo "<TD align='center'>$pais </TD>";
				echo "<TD align='center'>$garantia_pais</TD>";
				echo "<TD><a href=\"javascript: if (confirm('Deseja excluir?')) {window.location='$PHP_SELF?produto=$produto&acao=a&pais=$pais'}; \">Excluir</a></TD>";
				echo "</TD>";
				echo "</TR>";
			}
			echo "</TABLE>";
		}
		
		if($login_admin == 513 OR $login_admin == 516 OR $login_admin == 514 OR $login_admin == 515 or $login_admin == 3128){
			echo "</td><td width='1px' bgcolor='#6699CC'></td><td valign='top'><b>Escolha o País</b><br>";
			echo "<select name='produto_pais' id='produto_pais'size='1'>";
			echo "<option value='BR' >Brasil</option>";
			echo "</select>";
			echo "<br>";
			echo "<b>Garantia do País</b><br> <input  type='text' class='frm' name='garantia_pais' value='' size='15' maxlength='50' id='garantia_pais'><br>";
			echo "<input type='button' name='btn_produto_pais' value = 'Atribui Pais'onclick=\"javascript:window.location='$PHP_SELF?produto=$produto&acao=atribui&pais='+document.getElementById('produto_pais').value+'&garantia_pais='+document.getElementById('garantia_pais').value\">";
			echo "</td>";
		}
		if($login_admin == (590) OR $login_admin == (364) or $login_admin == 1550 or $login_admin == 1762 or $login_admin == 3128){
			echo "</td><td width='1px' bgcolor='#6699CC'></td>
			<td valign='top'><b>Escolha o País</b><br>";

			echo "<select name='produto_pais' id='produto_pais'size='1'>";
			$sql = "SELECT pais, nome
						FROM tbl_pais
						ORDER BY nome";
				$res = pg_query($con, $sql);
				if(pg_num_rows($res)>0){

					for($x=0; $x<pg_num_rows($res); $x++){
						$aux_pais = pg_fetch_result($res, $x, pais);
						$nome_pais= pg_fetch_result($res, $x, nome);

						echo "<option value='$aux_pais' >";
						echo $nome_pais;
						echo "</option>";
					}
				}
			echo "</select>";

			echo "<br>";
			echo "<b>Garantia do País</b><br> <input  type='text' class='frm' name='garantia_pais' value='' size='15' maxlength='50'><br>";
			echo "<input type='button' name='btn_produto_pais' value = 'Atribui Pais'onclick=\"javascript:window.location='$PHP_SELF?produto=$produto&acao=atribui&pais='+document.getElementById('produto_pais').value+'&garantia_pais='+document.getElementById('garantia_pais').value\">";
			echo "</td>";
		}
		echo "</tr></table>";
		echo "</div></td></tr></table>";
	}

	?>

	<table width="700" border="0" cellpadding="2" cellspacing="1" class="formulario" align='center'>
		
		<tr>
			<td width="20">&nbsp;</td>
			<td width="210">Linha *</td>
			<td width="290">Família</td>
			<td>Origem</td>
		</tr>
		<tr>
			<td width="20">&nbsp;</td>
			<td>
			<?
			##### INÍCIO LINHA #####
			$sql = "SELECT  *
					FROM    tbl_linha
					WHERE   tbl_linha.fabrica = $login_fabrica
					AND     tbl_linha.ativo = TRUE
					ORDER BY tbl_linha.nome;";
			$res = pg_query ($con,$sql);

			if (pg_num_rows($res) > 0) {
				echo "<select class='frm' style='width: 150px;' name='linha'>\n";
				echo "<option value=''>ESCOLHA</option>\n";

				for ($x = 0 ; $x < pg_num_rows($res) ; $x++){
					$aux_linha = trim(pg_fetch_result($res,$x,linha));
					$aux_nome  = trim(pg_fetch_result($res,$x,nome));

					echo "<option value='$aux_linha'"; if ($linha == $aux_linha) echo " SELECTED "; echo ">$aux_nome</option>\n";
				}
				echo "</select>\n";
			}
			##### FIM LINHA #####
			?>
			</td>
			<td>
			<?
			##### INÍCIO FAMÍLIA #####
			$sql = "SELECT  *
					FROM    tbl_familia
					WHERE   tbl_familia.fabrica = $login_fabrica
					AND     tbl_familia.ativo = TRUE
					ORDER BY tbl_familia.descricao;";
			$res = pg_query ($con,$sql);

			if (pg_num_rows($res) > 0) {
				echo "<select class='frm' style='width: 150px;' name='familia'>\n";
				echo "<option value=''>ESCOLHA</option>\n";

				for ($x = 0 ; $x < pg_num_rows($res) ; $x++){
					$aux_familia = trim(pg_fetch_result($res,$x,familia));
					$aux_descricao  = trim(pg_fetch_result($res,$x,descricao));

					echo "<option value='$aux_familia'"; if ($familia == $aux_familia) echo " SELECTED "; echo ">$aux_descricao</option>\n";
				}
				echo "</select>\n";
			}
			##### FIM FAMÍLIA #####
			?>
			</td>
			<td>
			<select name="origem" class="frm" style="width:150;">
				<option value="">ESCOLHA</option>
				<option value="Nac" <? if ($origem == "Nac") echo " SELECTED "; ?>>Nacional</option>
				<option value="Imp" <? if ($origem == "Imp") echo " SELECTED "; ?>>Importado</option>
				<option value="USA" <? if ($origem == "USA") echo " SELECTED "; ?>>Importado USA</option>
				<option value="Asi" <? if ($origem == "Asi") echo " SELECTED "; ?>>Importado Asia</option>

			</select>
			</td>
			<?
			if($login_fabrica==3){
				echo "</tr><tr><td width='20'>&nbsp;</td><td colspan='3'  bgcolor='#D9E2EF'><br>Fornecedor do Produto</td>";

				$sql = "SELECT  *
						FROM    tbl_produto_fornecedor
						WHERE   tbl_produto_fornecedor.fabrica = $login_fabrica
						ORDER BY tbl_produto_fornecedor.nome;";
				$res = pg_query ($con,$sql);
			
				if (pg_num_rows($res) > 0) {
					echo "</tr><tr><td width='20'>&nbsp;</td><td colspan='3'><select class='frm' style='width:400px'  name='produto_fornecedor'>\n";
					echo "<option value=''>ESCOLHA</option>\n";

					for ($x = 0 ; $x < pg_num_rows($res) ; $x++){
						$aux_produto_fornecedor = trim(pg_fetch_result($res,$x,produto_fornecedor));
						$aux_nome               = trim(pg_fetch_result($res,$x,nome));

						echo "<option value='$aux_produto_fornecedor'"; if ($produto_fornecedor == $aux_produto_fornecedor) echo " SELECTED "; echo ">$aux_nome</option>\n";
					}
					echo "</select></td>\n";
				}
			}?>

		</tr>
	</table>

	<?if($login_fabrica==3 and strlen($produto)>0){
		echo "<table width='700'align='center'><tr><td><div class='Div'>";
		echo "<table width='100%'><tr><td valign='top'>";
		
		$sql = "SELECT * FROM tbl_produto_idioma WHERE produto = $produto";
		$res2 = pg_query ($con,$sql);

		if (pg_num_rows($res2) > 0) {
			$produto                  = trim(pg_fetch_result($res2,0,produto));
			$idioma                   = trim(pg_fetch_result($res2,0,idioma));
			$descricao_idioma         = trim(pg_fetch_result($res2,0,descricao));
		}else{
			echo "Não existe descrição para esse produto do fornecedor, preencha o campo abaixo para inserir uma.<br>";
			echo "<input type='hidden' name='idioma_novo' value='EN'>";
		}
		echo "<b>Descrição do Fornecedor:</b> <input  type='text' class='frm' name='descricao_idioma' value='$descricao_idioma' size='30' maxlength='50'><br><br>";
		echo "<input type='hidden' name='idioma' value='EN'>";
		echo "</td>";
		echo "</table>";
		echo "</td>";
		echo "</tr>";
		echo "</table>";

	}?>

	<table width="700" border="0" cellpadding="2" cellspacing="1" class="formulario" align='center'>
		<tr><td colspan="4">&nbsp;</td></tr>
		<tr>
			<td width="20">&nbsp;</td>
			<td width="210">Voltagem</td>
			<?
			if ($login_fabrica ==7 )  {
				echo "<td>Capacidade</td>";
				echo "<td>Divisão</td>";
			}
			?>
			<td width="290">Status Rede</td>
			<td><acronym title="Produto visível somente pelo Posto Interno, apesar de inativo para a rede!">Status uso Interno</acronym></td>
			<!--<td>Off-line (*)</td>-->
		</tr>
		
		<tr>
			<td width="20">&nbsp;</td>
			<td>
			<select name="voltagem" class="frm " style="width: 150px;">
				<option value="">ESCOLHA</option>
				<option value="12 V" <? if ($voltagem == "12 V") echo " SELECTED "; ?>>12 V</option>
				<? if ($login_fabrica <> 1) { ?>
				<option value="110 V" <? if ($voltagem == "110 V") echo " SELECTED "; ?>>110 V</option>
				<?}?>
				<option value="127 V" <? if ($voltagem == "127 V") echo " SELECTED "; ?>>127 V</option>
				<option value="220 V" <? if ($voltagem == "220 V") echo " SELECTED "; ?>>220 V</option>
				<option value="Bivolt" <? if ($voltagem == "Bivolt") echo " SELECTED "; ?>>Bivolt</option>
				<option value="Bivolt Aut" <? if ($voltagem == "Bivolt Aut") echo " SELECTED "; ?>>Bivolt Aut</option>
	<!--            <option value="Trivolt" <? if ($voltagem == "Trivolt") echo " SELECTED "; ?>>Trivolt</option> - -->
				<option value="Bateria" <? if ($voltagem == "Bateria") echo " SELECTED "; ?>>Bateria</option>
				<option value="Pilha" <? if ($voltagem == "Pilha") echo " SELECTED "; ?>>Pilha</option>
				<?if($login_fabrica == 15) { // HD 75711?>
				<option value="Full Range" <? if ($voltagem == "Full Range") echo " SELECTED "; ?>>Full Range</option>
				<?}?>
				<?if($login_fabrica == 1) { // HD 75711?>
				<option value="SEM" <? if ($voltagem == "SEM") echo " SELECTED "; ?>>SEM</option>
				<?}?>
			</select>
			</td>
			<? if ($login_fabrica == 7) { ?>
				<td>
				<input type="text" class="frm" name="capacidade" value="<? echo $capacidade ?>" size="8" maxlength="8">Kg
				</td>
				<td>
				<input type="text" class="frm" name="divisao" value="<? echo $divisao ?>" size="6" maxlength="8">
				</td>
			<?}?>
			<td>
			<select name="ativo" class="frm" style="width: 150px;">
				<option value="">ESCOLHA</option>
				<option value="t" <? if ($ativo == "t") echo " SELECTED "; ?>>Ativo</option>
				<option value="f" <? if ($ativo == "f") echo " SELECTED "; ?>>Inativo</option>
			</select>
			</td>
			<td>
			<select name="uso_interno_ativo" class="frm" style="width: 150px;">
				<option value="">ESCOLHA</option>
				<option value="t" <? if ($uso_interno_ativo == "t") echo " SELECTED "; ?>>Ativo</option>
				<option value="f" <? if ($uso_interno_ativo == "f") echo " SELECTED "; ?>>Inativo</option>
			</select>
			</td>
		</tr>
		<tr><td colspan="4">&nbsp;</td></tr>
		<tr>
			<td width="20">&nbsp;</td>
			<td><acronym title="Quantidade de etiqueta a ser imprimida se for maior que 5 etiquetas">Qtd Etiqueta</acronym></td>
			<?
			if ($login_fabrica ==96 )  echo "<td>Nome Comercial *</td>";
			if ($login_fabrica == 14 or $login_fabrica == 66) {
				echo "<td>Permitido abrir OS (*)</td>";
				if($login_fabrica == 14 or $login_fabrica == 66) {
					echo "<td>Receber E-mail</td>";
				}
			}
			if ($login_fabrica == 1 or $login_fabrica == 80 ) {
				echo "<td>Referência Interna (*)</td>";
				if ($login_fabrica == 1 ) {
					echo "<td>Code Convention</td>";
				}
				echo "<td>Locador</td>";
			}
			if ($login_fabrica ==20 )  echo "<td>Bar Tool (*)</td>";
			if ($login_fabrica ==24 )  echo "<td>Referência Única (*)</td>";
			if ($login_fabrica ==3 or $login_fabrica == 30 or $login_fabrica >80 )   echo "<td>Marca</td>";
			if ($login_fabrica ==43 )  echo "<td>Sistema Operacional</td>";
			
			?>
		</tr>

		<tr>
			<td width="20">&nbsp;</td>
			<td><input type="text" class="frm" name="qtd_etiqueta_os" value="<? echo $qtd_etiqueta_os ?>" size="3" maxlength="10"></td>
			<? if ($login_fabrica == 14 or $login_fabrica == 66) { ?>
			<td>
			<input type="radio" class="frm" name="abre_os" <? if ($abre_os == 't') echo "checked"; ?> value="t"> Sim
			<input type="radio" class="frm" name="abre_os" <? if ($abre_os == 'f' OR strlen($abre_os) == 0) echo "checked"; ?> value="f"> Não
			</td>
			<? if($login_fabrica == 14 or $login_fabrica == 66) { ?>
			<td>
			<input type="radio" class="frm" name="aviso_email" <? if ($aviso_email == 't' OR strlen($aviso_email) == 0) echo "checked"; ?> value="t"> Sim
			<input type="radio" class="frm" name="aviso_email" <? if ($aviso_email == 'f') echo "checked"; ?> value="f"> Não
			</td>
			<? } } ?>
			<? if (in_array($login_fabrica,array(1,20,24,80,96))) { ?>
				<td>
				<input type="text" class="frm" name="referencia_fabrica" value="<? echo $referencia_fabrica ?>" size="16" maxlength="20">
				</td>
			<?}?>
			<?if ($login_fabrica==1){ //HD 14624?>
				<td><input type="text" class="frm" name="code_convention" value="<? echo $code_convention?>" size="15" maxlength="20"></td>
			<? } ?>
			<?if ($login_fabrica==1){ //HD 14624?>
				<td><input type="checkbox" class="frm" name="locador" <? if ($locador == 't' ) echo " checked " ?> value='t'></td>
			<? } ?>
			<? if ($login_fabrica == 3 or $login_fabrica == 30 or $login_fabrica >80) { ?>
			<td>
			<select name="marca" class="frm" style="width: 150px;">
				<option value="">ESCOLHA</option>
				<?
					$sql = "SELECT marca, nome from tbl_marca where fabrica = $login_fabrica and visivel is true order by nome";
					$res = pg_query($con,$sql);
					if(pg_num_rows($res)>0){
						for($i=0;pg_num_rows($res)>$i;$i++){
							$xmarca = pg_fetch_result($res,$i,marca);
							$xnome  = pg_fetch_result($res,$i,nome);
						?>
						<option value="<?echo $xmarca;?>" <? if ($xmarca == $marca) echo " SELECTED "; ?>><?echo $xnome;?></option>

						<?}
					}
				?>
			</select>
			</td>
			<? } ?>
			<? if ($login_fabrica == 43) { ?>
			<td>
			<select name="sistema_operacional" class="frm" style="width: 110px;">
				<option value="">ESCOLHA</option>
				<option value="1" <? if($sistema_operacional == 1) echo " SELECTED ";?>>Windows</option>
				<option value="2" <? if($sistema_operacional == 2) echo " SELECTED ";?>>Linux</option>
				<option value="3" <? if($sistema_operacional == 3) echo " SELECTED ";?>>Apple</option>
			</select>
			</td>
			<? } ?>
		</tr>

		<? if($login_fabrica == 15) {
		
			?>
			<tr><td><br></td></tr>
			<tr>
				<td colspan=5>
					<table class="formulario" width="250px" align='center' border="0">
						
						
						<tr align='left'>
							<td width="17px">&nbsp;</td>
							<td>Série Inicial</td>
							<td colspan='2'>Série Final</td>
							
						</tr>
						
						<tr align='left'>
							<td width="17px">&nbsp;</td>
							<td>
								<input type='text' class='frm' id='serie_inicial' name='serie_inicial' value='<?echo $serie_inicial ?>' size='10' maxlength='20'><br>&nbsp;
							</td>

							<td colspan='2'>
								<input type='text' class='frm' id='serie_final' name='serie_final' value='<? echo $serie_final ?>' size='10' maxlength='20'><br>&nbsp;
							</td>
						</tr>
						
					</table>
				</td>
			</tr>
		<?
		}?>
		
	</table>

	<table width="700" border="0" cellpadding="2" cellspacing="1" class="formulario" align='center'>
		<tr class="subtitulo">
			<td>Nome Comercial</td>
			<td>Classificação Fiscal</td>
			<td>I.P.I.</td><?php
			if ($login_fabrica != 3 && $login_fabrica != 86) {//HD 387824?>
				<td>Radical No. Série </td><?php
			}?>
			<td title='Esta opção serve para que este produto apareça na lista de TROCA DE PRODUTOS'>Lista de Troca</td>
			<?if($login_fabrica == 3 or $login_fabrica==11 or $login_fabrica==14 or $login_fabrica==86){?>
				<td>Intervenção Técnica</td>
			<?}?>
			<td>No. Série Obrigatório</td><?php
			if ($login_fabrica != 86) {//HD 387824?>
				<td>Produto Principal</td><?php
			}
			echo "<td>Troca Obrigatória</td>";
			if ($login_fabrica == 3) {
				echo "<td>Validar Série</td>";
			}
			if ($login_fabrica == 35) {
				echo "<td>Produto Crítico</td>";
			}
			if ($login_fabrica == 1) {
				echo "<td>Troca Faturada(valor)</td>";
				echo "<td>Troca Garantia</td>";
				echo "<td>Troca Faturada</td>";
			}?>
		</tr>
		<tr align="center">
			<td><input type="text" class="frm" name="nome_comercial" value="<? echo $nome_comercial ?>" size="15" maxlength="20"></td>
			<td><input type="text" class="frm" name="classificacao_fiscal" value="<? echo $classificacao_fiscal ?>" size="15" maxlength="20"></td>
			<td><input type="text" class="frm" name="ipi" value="<? echo $ipi ?>" size="5" maxlength="10"></td><?php
			if ($login_fabrica != 3 && $login_fabrica != 86) {//HD 387824?>
				<td><input type="text" class="frm" name="radical_serie" value="<? echo $radical_serie ?>" size="12" maxlength="10"></td><?php
			}?>
			<td><input type="checkbox" class="frm" name="lista_troca" <? if ($lista_troca == 't' ) echo " checked " ?> value='t' ></td>

			<?if($login_fabrica==3 or $login_fabrica==11 or $login_fabrica == 14 or $login_fabrica == 86){?>
				<td><input type="checkbox" class="frm" name="intervencao_tecnica" <? if ($intervencao_tecnica == 't' or $login_fabrica == 86 ) echo " checked " ?> value='t' ></td>
			<?}?>

			<td><input type="checkbox" class="frm" name="numero_serie_obrigatorio" <? if ($numero_serie_obrigatorio == 't' ) echo " checked " ?> value='t' ></td><?php
			if ($login_fabrica != 86) {//HD 387824?>
				<td><input type="checkbox" class="frm" name="produto_principal" <? if ($produto_principal == 't' ) {echo " checked ";} if(($login_fabrica == 46 OR $login_fabrica > 73) AND strlen($produto) == 0){ echo "checked"; } ?> value='t' ></td><?php
			}?>
			<td><input type="checkbox" class="frm" name="troca_obrigatoria" <? if ($troca_obrigatoria == 't' ) echo " checked " ?> value='t' ></td>

			<? if ($login_fabrica == 3) { ?>
				<td><input type="checkbox" class="frm" name="validar_serie" <? if ($validar_serie == 't' ) echo " checked " ?> value='t' ></td>
			<? }?>

			<? if ($login_fabrica == 35) { ?>
				<td><input type="checkbox" class="frm" name="produto_critico" <? if ($produto_critico == 't' ) echo " checked " ?> value='t' ></td>
			<? }?>

			<? if ($login_fabrica == 1) { //hd 7474 TAKASHI 23/11/07  //hd 7474 Fabio 30/11/07 ?>
				<td><input type="text" class="frm" name="valor_troca" value="<? echo $valor_troca ?>" size="6" maxlength="6" onBlur='checarNumero(this)'></td>
				<td><input type="checkbox" class="frm" name="troca_garantia" <? if ($troca_garantia == 't' ) echo " checked " ?> value='t' ></td>
				<td><input type="checkbox" class="frm" name="troca_faturada" <? if ($troca_faturada == 't' ) echo " checked " ?> value='t' ></td>
			<? } ?>
		</tr>
        <?
        if($login_fabrica==20){?>
			<tr align='center'>
				<td colspan='8'>
					Comentário
				</td>
			</tr>
			<tr>
				<td colspan='8' align='center'>
					<textarea name='observacao' cols='90' rows='4' class='frm'>
					<?
						if(strlen($observacao)>0){
							echo nl2br($observacao);
						}
					?>
					</textarea>
				</td>
			</tr>
		<? } ?>
	</table>

	<? #RADICAL DE NÚMERO DE SÉRIE - BRITÂNIA HD 259784
	if($login_fabrica==3){
	?>

		<table width="700" border="0" cellpadding="2" cellspacing="1" class="formulario" align='center'>
			<tr><td colspan="3">&nbsp;</td></tr>
			<tr>
				<td>
					<span>Radical N. Série 1</span>
				</td>
				<td>
					<span>Radical N. Série 2</span>
				</td>
				<td>
					<span>Radical N. Série 3</span>
				</td>
			</tr>
			<tr>
				<td>
					<input type="text" class="frm" name="radical_serie" value="<? echo $radical_serie ?>" size="5" maxlength="10" title="Digite o nº do radical série">
				</td>
				<td>
					<input type="text" class="frm" name="radical_serie2" value="<? echo $radical_serie2 ?>" size="5" maxlength="10" title="Digite o nº do radical série(2)">
				</td>
				<td>
					<input type="text" class="frm" name="radical_serie3" value="<? echo $radical_serie3 ?>" size="5" maxlength="10" title="Digite o nº do radical série(3)">
				</td>
			</tr>
			<tr>
				<td>
					<span>Radical N. Série 4</span>
					<br>
				</td>
				<td>
					<span>Radical N. Série 5</span>
				</td>
				<td>
					<span>Radical N. Série 6</span>
				</td>
			</tr>

			<tr>
				<td>
					<input type="text" class="frm" name="radical_serie4" value="<? echo $radical_serie4 ?>" size="5" maxlength="10" title="Digite o nº do radical série(4)">
				</td>
				<td>
					<input type="text" class="frm" name="radical_serie5" value="<? echo $radical_serie5 ?>" size="5" maxlength="10" title="Digite o nº do radical série(5)">
				</td>
				<td>
					<input type="text" class="frm" name="radical_serie6" value="<? echo $radical_serie6 ?>" size="5" maxlength="10" title="Digite o nº do radical série(6)">
				</td>
			</tr>
		</table>
	<?
	} #FIM - RADICAL DE NÚMERO DE SÉRIE - BRITÂNIA
	?>

	<?  // HD 96953 - Adicionar imagem do produto para a Britânia
    if ($login_fabrica==3 or $login_fabrica>80) {

        $imagem_produto = $produto.'.jpg';
        if (strlen($imagem_produto)>4) {
            $imagem     = "imagens_produtos/$login_fabrica/pequena/$imagem_produto";
            $msg_imagem = "Anexar imagem:";
            if (file_exists("../$imagem")) {
                $tag_imagem = "<a href='../".str_replace("pequena", "media", $imagem)."' class='thickbox' ><img src='../$imagem?bypass=" . md5(mt_rand(100,999)) . "' title='Clique para ver a imagem' valign='middle' class='thickbox' height='60'></a>\n";
                $msg_imagem = "Mudar imagem:";
            }
?>
<table width='700' class='formulario'>
	<caption class='subtitulo'>Imagem do produto <?=$descricao?><?=(strlen($referencia)>0) ? " (ref. $referencia)":""?></caption>
	<tr>
		<td nowrap='nowrap' width='20%'><?=$msg_imagem?></td>
		<td align='center'>
			<input type='hidden' name='MAX_FILE_SIZE' value='153600'>
			<?=$tag_imagem?>
		</td>
		<td width='60%' align='left'>
			<input title='Selecione o arquivo com a foto do produto'
			        type='file' name='arquivo' size='18'
					class="multi {accept:'jpg|gif|png', max:'1', STRING: {remove:'Remover',selected:'Selecionado: <?=$file?>', denied:'Tipo de arquivo inválido: <?=$ext?>!'}}">
		</td>
	</tr>
</table>
<?		}
    }

   //hd 21461
	if ($login_fabrica == 1) {
		$qtde_item = 100;
		$qtde_item_visiveis = 5;
		$qtde_linhas = 0;

		
		if (strlen($produto) > 0) {
			$sql = "SELECT  tbl_produto.referencia                     ,
							tbl_produto.descricao                      ,
							tbl_produto.produto                        ,
							tbl_produto_troca_opcao.produto_troca_opcao,
							tbl_produto.voltagem                       ,
							tbl_produto_troca_opcao.kit
					FROM tbl_produto
					JOIN tbl_produto_troca_opcao ON tbl_produto.produto = tbl_produto_troca_opcao.produto_opcao
					AND  tbl_produto_troca_opcao.produto = $produto
					ORDER by tbl_produto_troca_opcao.kit, tbl_produto.descricao, tbl_produto.voltagem";
			$res = pg_query($con, $sql);

			if (pg_num_rows($res) > 0) {
				$qtde_linhas = pg_num_rows($res);
			}
		}

		echo "<input class='frm' type='hidden' name='qtde_item' value='$qtde_item'>\n";

		echo "<br><br><center>";
		echo "<table class='tabela_item' bgcolor='#D9E2EF' width='820'>";
		   
			echo "<tr class='titulo_tabela'>";
				echo "<td colspan='5' align='center'>";
				echo "<b>Pode ser trocado por:</b>";
				echo "</td>";
			echo "</tr>";
			
			//HD #145639 - INSTRUÇÕES
			echo "
			<tr class='subtitulo'>
				<td colspan=5 style='padding: 3px;'>
				<b>KITs:</b> podem ser criados KITs para trocar um produto por vários. Para isto, selecione na coluna KIT o mesmo número para agrupar vários produtos. No momento da troca os produtos com o mesmo número de KIT serão agrupados para seleção.
				</td>
			</tr>
			";
			 echo "<tr>\n";
			echo "<td colspan='3'></td>\n";
			echo "<td align='right' colspan='2' style='font-size:11px;'>
					Qtde linhas
					<select onChange='qtdeLinhas(this)' class='frm'>
					<option value='5'>5 Linhas</option>
					<option value='10'>10 Linhas</option>
					<option value='15'>15 Linhas</option>
					<option value='30'>30 Linhas</option>
					<option value='50'>50 Linhas</option>
					<option value='100'>100 Linhas</option>
					</select>
			</td>\n";
			echo "</tr>\n";
			//HD #145639 - NOME DAS COLUNAS
			echo "
			<tr class='titulo_coluna'>
				<td></td>
				<td>Código Produto</td>
				<td>Nome Produto</td>
				<td>Voltagem</td>
				<td>KIT</td>
			</tr>
			";

			for ($i=0; $i<$qtde_item; $i++) {
				$referencia_opcao    = "";
				$descricao_opcao     = "";
				$produto_opcao       = "";
				$produto_troca_opcao = "";
				$voltagem_opcao = "";

				if ($i<$qtde_linhas){
					$referencia_opcao    = pg_fetch_result($res,$i,referencia);
					$descricao_opcao     = pg_fetch_result($res,$i,descricao);
					$produto_opcao       = pg_fetch_result($res,$i,produto);
					$produto_troca_opcao = pg_fetch_result($res,$i,produto_troca_opcao);
					$voltagem_opcao      = pg_fetch_result($res,$i,voltagem);
					$kit_opcao             = pg_fetch_result($res,$i,kit);

					$erro_linha = "erro_linha" . $i;
					$erro_linha = $$erro_linha;
					$cor_erro = "#FFFFFF";
					if ($erro_linha == 1) $cor_erro = "#FF9999";
				}
				else $kit_opcao = "";

				$ocultar_linha = "";
				if ($i+1 > $qtde_item_visiveis and $i+1 > $qtde_linhas){
					$ocultar_linha = " style='display:none' ";
				}

				echo "\n<tr ".$ocultar_linha." rel='$i' bgcolor='$cor_erro' style='font-size:11px;'>";
					echo "<td align='center'><input class='frm' type='hidden' name='produto_opcao_$i'    ; rel='produtos'                          value='$produto_opcao'>
											 <input class='frm' type='hidden' name='produto_troca_opcao_$i'    ; rel='produtos' value='$produto_troca_opcao'>
											 <input class='frm' type='hidden' name='voltagem_troca_opcao_$i'    ; rel='produtos' value=''>".($i+1)."</td>";
					echo "<td align='center'><input class='frm' type='text'   name='referencia_opcao_$i' ; rel='produtos' size='20' maxlength='30' value='$referencia_opcao' onchange=\"javascript: document.frm_produto.voltagem_opcao_$i.value = ''\">
						  <img src='imagens/lupa.png' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_produto_opcao (document.frm_produto.referencia_opcao_$i,document.frm_produto.descricao_opcao_$i,document.frm_produto.produto_opcao_$i,document.frm_produto.voltagem_opcao_$i,\"referencia\")' style='cursor:pointer;'></td>";
					echo "<td align='center'><input class='frm' type='text'   name='descricao_opcao_$i'   ; rel='produtos' size='50' maxlength='50' value='$descricao_opcao' onchange=\"javascript: document.frm_produto.voltagem_opcao_$i.value = ''\">
						  <img src='imagens/lupa.png' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_produto_opcao (document.frm_produto.referencia_opcao_$i,document.frm_produto.descricao_opcao_$i,document.frm_produto.produto_opcao_$i,document.frm_produto.voltagem_opcao_$i,\"descricao\")' style='cursor:pointer;'></td>";
					echo "<td align='left'><input class='frm' type='text' name='voltagem_opcao_$i'; rel='produtos' value='$voltagem_opcao' readonly></td>";
					
					//HD #145639 - COLUNA DE KITS
					$n_kit_opcoes = 20;

					echo "
					<td align='center'>
						<select name='kit_opcao_$i' class='frm'>
						<option value='0'>--</option>";

					for($k = 1; $k <=$n_kit_opcoes; $k++) {
						if ($kit_opcao == $k) $selected = "selected";
						else $selected = "";

						echo "
						<option $selected value='$k'>$k</option>";
					}
					
					echo "
						</select>
					</td>
					";
				echo "</tr>";
			}
		echo "</table>";
    } ?>
	
	<!-- HD 335150 INICIO -->
	<?php

	if ($login_fabrica==15){
		?>
		<table class="formulario" align="center" width="700px" cellpadding="0" cellspacing="0">
			<tr>
				<td>
					&nbsp;
				</td>
			</tr>
			<tr>
				<td colspan="1" class='subtitulo'>
					Intervalo de Série
				</td>
			</tr>
			<tr>
				<td>
					&nbsp;
				</td>
			</tr>
		</table>
		
		<table  id="table_serie_in_out" class="tabela" width="700px" cellpadding="1" cellspacing="1" align="center">
			
			<tr class="titulo_coluna">
				<th>Série Inicial</th>
				<th>Série Final</th>
				<th width="25%">Ações</th>
			</tr>
			<?
			if (strlen($produto) > 0){
				$sql = "
					SELECT
						produto_serie,
						serie_inicial ,
						serie_final   
						
					FROM
						tbl_produto_serie
					where
						tbl_produto_serie.produto = $produto
					order by produto_serie
				";
			
				$res = pg_query ($con,$sql);
				$linha = pg_num_rows($res);
			
				for ($i = 0; $i < $linha; $i++){
					$produto_serie = pg_result($res,$i,produto_serie);
					$serie_in = pg_result($res,$i,serie_inicial);
					$serie_out = pg_result($res,$i,serie_final);
					
					?>
					<tr height="25px" style="background-color:#F7F5F0">
						
						<td align="center">
							<input type="hidden" name="produto_serie_in_out[]" value="<? echo $produto_serie;?>">
							
							<input class='frm serie' rel="<?php echo $produto_serie;?>" type="text" id="serie_in_<?echo $produto_serie?>" name="serie_in[]" size="15" maxlength="30" value="<? echo $serie_in;?>">
						</td>
						
						<td align="center">
							<input  class='frm serie' rel="<?php echo $produto_serie;?>" type="text" id="serie_out_<?echo $produto_serie?>" name="serie_out[]" size="15" maxlength="30" value="<?echo $serie_out;?>">
						</td>
						
						<td align="center">
							<input type="checkbox" id="apagar_serie_<?echo $produto_serie?>" name="apagar_serie[<?echo $produto_serie?>]" value="excluir" class="frm">
							Excluir
						</td>
						
					</tr>
					<?php
				}
			}
		?>
		</table>
		<table class="formulario" align="center" width="700px" cellpadding="0" cellspacing="0">
			<tr>
				<td>
					&nbsp;
				</td>
			</tr>
			<tr>
				<td align="center">
					<input type='button' id='add_line' value='Acrescentar Intervalo de Série' onclick='adiciona()'>
				</td>
			</tr>
		</table>
		<?php
	}
	
	?>
	<!-- HD 335150 FIM -->

		<input type='hidden' name='btnacao' value=''>
		  <? 
			if($login_fabrica==1 )
				echo '<table class="formulario" align="center" width="820" border="0">';
			else
				echo '<table class="formulario" align="center" width="700" border="0">';
		  ?>

		<tr><td>&nbsp;</td></tr>

		<tr>
			<td align="center">
			
			<? if (strlen($produto) > 0){
				$onclick = "onclick=\"if (confirm('Você irá atualizar um produto! Confirma esta ação? Caso deseje apenas inserir um novo, cancele a operação, limpe as informações da tela e insira o produto!')) { validaForm(); }\" ";
			}else{
				$onclick = "onclick=\"if (document.frm_produto.btnacao.value == '' ) { validaForm(); } else { alert ('Aguarde submissão'); } return false;\" ";
			}?>
			
			<input type="button" value="Gravar" alt="Gravar formulário" border='0' style="cursor:pointer;" <?php echo $onclick;?> >

		<? if ($login_fabrica == 1 OR $login_fabrica == 14) {
			?>
			<input type="button" value="Apagar" onclick="javascript: if (document.frm_produto.btnacao.value == '' ) { document.frm_produto.btnacao.value='deletar' ; document.frm_produto.submit() } else { alert ('Aguarde submissão') } return false;" ALT="Apagar produto" border='0' style="cursor:pointer;">
			<? } ?>
			<input type="button" value="Limpar" onclick="javascript:  window.location='<? echo $PHP_SELF ?>'; return false;" ALT="Limpar campos" border='0' style="cursor:pointer;">
			</td>
		</tr>
	</table>
</form>

<br />
<?php
if (strlen($produto) > 0) {
    if (strlen($admin) > 0 and strlen($data_atualizacao) > 0) { ?>
        <div id="wrapper">
            <!-- <a href='PROVISORIO_produto_garantia.php' TARGET='_blank'></a> -->
            ÚLTIMA ATUALIZAÇÃO
        
            <!-- <a href='PROVISORIO_produto_garantia.php' TARGET='_blank'></a> -->
            <?echo $admin ." - ". $data_atualizacao;?>
        </div><?php
    }
}?>

</div>
</div><?php
// SE FOR INTELBRÁS OU MAXCOM HD 40530
if (($login_fabrica == 14 or $login_fabrica == 66) and strlen($produto) > 0) {
    $sql = "SELECT  tbl_produto.produto   ,
                    tbl_produto.referencia,
                    tbl_produto.descricao
            FROM    tbl_subproduto
            JOIN    tbl_produto ON tbl_produto.produto = tbl_subproduto.produto_filho
            JOIN    tbl_linha   ON tbl_linha.linha     = tbl_produto.linha
            WHERE   tbl_linha.fabrica          = $login_fabrica
            AND     tbl_subproduto.produto_pai = $produto
            ORDER BY tbl_produto.descricao;";

    $res0 = @pg_query ($con,$sql);

    if (@pg_num_rows($res0) > 0) {

        echo "<table width='400' border='0' class='titulo' cellpadding='2' cellspacing='1' align='center'>\n";
        echo "<tr align='center'>\n";
        echo "<td colspan='3' bgcolor='#D9E2EF' nowrap>PRODUTOS / SUBPRODUTOS RELACIONADOS</td>\n";
        echo "</tr>\n";
        echo "<tr align='center'>\n";
        echo "<td bgcolor='#D9E2EF' nowrap>REFERÊNCIA</td>\n";
        echo "<td bgcolor='#D9E2EF' nowrap>DESCRIÇÃO</td>\n";
        echo "<td bgcolor='#D9E2EF' nowrap>LISTA BÁSICA</td>\n";
        echo "</tr>\n";

        for ($y = 0; $y < @pg_num_rows($res0); $y++) {

            $produto    = trim(pg_fetch_result($res0,$y,produto));
            $referencia = trim(pg_fetch_result($res0,$y,referencia));
            $descricao  = trim(pg_fetch_result($res0,$y,descricao));

            $cor = ($y % 2 == 0) ? "#F1F4FA" : "#F7F5F0";

            echo "<tr bgcolor='$cor'>\n";
            echo "<td width='15%' align='center' nowrap><a href='$PHP_SELF?produto=$produto'>$referencia</a></td>";
            echo "<td width='85%' align='left' nowrap><a href='$PHP_SELF?produto=$produto'>$descricao</a></td>\n";
            echo "<td width='85%' align='center' nowrap><a href='lbm_cadastro.php?produto=$produto' target='_blank'><img src='imagens/btn_lista.gif' border=0/></a></td>\n";
            echo "</tr>\n";
        }
        echo "</table>\n\n";
        echo "<br>\n\n";
    }
}

if (strlen($produto) > 0 AND $ip == '201.0.9.216') {
    echo "<font size='1'><a href='preco_cadastro_produto_lbm.php?produto=$produto&btn_acao=listar'>CLIQUE AQUI PARA LISTAR A TABELA DE PREÇOS</a></font>\n<br>\n<br>\n";
}

if ($login_fabrica == 3) {?>
    <div id="wrapper">
        <a href='produto_consulta_parametro.php' target='_blank'>CLIQUE AQUI PARA CONSULTAR TODOS OS PRODUTOS, FILTRANDO DE ACORDO COM O TIPO (EX.: TROCA OBRIGATÓRIA)</a>
    </div><?php
}?>

<div id="wrapper">
   <button onclick="window.location='<?echo $PHP_SELF;?>?listartudo=1'">Listar Todos os Produtos</button>
</div><?php

$listartudo = $_GET['listartudo'];

if ($listartudo == 1) {

    $sql = "SELECT  tbl_produto.referencia,
                    tbl_produto.voltagem  ,
                    tbl_produto.produto   ,
                    tbl_produto.descricao ,
                    tbl_produto.ativo     ,
                    tbl_produto.uso_interno_ativo     ,
                    tbl_produto.locador   ,
                    tbl_produto.referencia_fabrica,
                    tbl_familia.descricao AS familia,
                    tbl_linha.nome        AS linha
            FROM    tbl_produto
            JOIN    tbl_linha     USING (linha)
            LEFT JOIN tbl_familia USING (familia)
            WHERE   tbl_linha.fabrica = $login_fabrica ";

    if ($login_fabrica == 35 or $login_fabrica == 81) {
                $sql .= " ORDER BY    
                tbl_produto.referencia ASC,
                tbl_linha.nome        ASC,
                tbl_produto.voltagem  ASC,
                tbl_familia.descricao ASC; ";
    } else {
        $sql .=    " ORDER BY    tbl_linha.nome ASC,
                    tbl_produto.voltagem  ASC,
                    tbl_produto.descricao ASC,
                    tbl_familia.descricao ASC; ";
    }

    $res = @pg_query ($con,$sql);

    for ($i = 0; $i < pg_num_rows($res); $i++) {
		if ($i % 2 == 0) $cor = "#F7F5F0"; else $cor = "#F1F4FA";
        if ($i % 20 == 0) {
            if ($i > 0) echo "</table>";
            flush();

            echo "<br />\n";
            echo "<table width='820' align='center' border='0' class='tabela' cellpadding='2' cellspacing='1'>";
            echo "<tr class='titulo_coluna'>";

            echo "<td align='center' width='50'>";
            echo "<b>Status Rede</b>";
            echo "</td>";

            echo "<td align='center' width='50'>";
            echo "<b>Status uso Interno</b>";
            echo "</td>";

            echo "<td align='center' width='200'>";
            echo "<b>Referência</b>";
            echo "</td>";

            echo "<td align='center'>";
            echo "<b>Descrição</b>";
            echo "</td>";

            if ($login_fabrica == 1 or $login_fabrica == 80) { // HD  90109

                echo "<td align='center'>";
                echo "<b>Voltagem</b>";
                echo "</td>";

                echo "<td align='center'>";
                echo "<b>Referência Interna</b>";
                echo "</td>";

                if ($login_fabrica == 1) {
                    echo "<td align='center'>";
                    echo "<b>Code Convention</b>";
                    echo "</td>";
                }

            }
            echo "<td align='center'>";
            echo "<b>Família</b>";
            echo "</td>";

            echo "<td align='center'>";
            echo "<b>Linha</b>";
            echo "</td>";

            if ($login_fabrica == 1) { // HD 90109
                echo "<td align='center'>";
                echo "<b>Locador</b>";
                echo "</td>";
            }

            echo "</tr>";
        }

        echo "<tr bgcolor = '$cor'>";

        echo "<td align='center'>";
        if (pg_fetch_result($res,$i,ativo) <> 't') echo "<img src='imagens_admin/status_vermelho.gif' border='0' alt='Inativo'>";
        else                                  echo "<img src='imagens_admin/status_verde.gif' border='0' alt='Ativo'>";
        echo "&nbsp;</td>";
        echo "<td align='center'>";
        if (pg_fetch_result($res,$i,uso_interno_ativo) <> 't') echo "<img src='imagens_admin/status_vermelho.gif' border='0' alt='Inativo'>";
        else                                  echo "<img src='imagens_admin/status_verde.gif' border='0' alt='Ativo'>";
        echo "&nbsp;</td>";

        echo "<td align='left' nowrap>";
        echo pg_fetch_result($res,$i,referencia);
        if ($login_fabrica <> 1 and $login_fabrica <> 80) {
            if ($login_fabrica <> 81){
                if (strlen(pg_fetch_result($res,$i,voltagem)) > 0) echo " / ". pg_fetch_result($res,$i,voltagem);
            }
        }

        echo "&nbsp;</td>";

        echo "<td align='left' nowrap>";
        echo "<a href='$PHP_SELF?produto=" . pg_fetch_result($res,$i,produto) . "'>";
        echo pg_fetch_result($res,$i,descricao);
        echo "</a>";
        echo "&nbsp;</td>";

        if ($login_fabrica == 1 or $login_fabrica == 80) { // HD 90109
            echo "<td align='left' nowrap>";
            echo pg_fetch_result($res,$i,voltagem);
            echo "&nbsp;</td>";

            echo "<td align='left' nowrap>";
            echo pg_fetch_result($res,$i,referencia_fabrica);
            echo "&nbsp;</td>";
        }

        echo "<td align='left' nowrap>";
        echo pg_fetch_result($res,$i,familia);
        echo "&nbsp;</td>";

        echo "<td align='left' nowrap>";
        echo pg_fetch_result($res,$i,linha);
        echo "&nbsp;</td>";

        if ($login_fabrica == 1) { // HD 90109
            echo "<td align='center'>";
            if (pg_fetch_result($res,$i,locador) <> 't') echo "Não";
            else                                  echo "Sim";
            echo "&nbsp;</td>";
        }
        echo "</tr>";
    }
    echo "</table>";
}?>
</center>

<?php include "rodape.php"; ?>
