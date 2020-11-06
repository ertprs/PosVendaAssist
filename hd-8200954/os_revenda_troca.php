<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'helpdesk/mlg_funciones.php';
include 'autentica_usuario.php';
include 'funcoes.php';

if ($login_fabrica == '1') {
    $limite_anexos_nf = 5;
}

include 'anexaNF_inc.php';
if($login_fabrica == 1){
    require "classes/ParametrosAdicionaisFabrica.php";
    $parametrosAdicionaisObject = new ParametrosAdicionaisFabrica($login_fabrica);

    require "classes/form/GeraComboType.php";
}
$msg_erro = "";

if (strlen($_POST['qtde_item']) > 0) $qtde_item = $_POST['qtde_item'];

if (strlen($_POST['qtde_linhas']) > 0) $qtde_item = $_POST['qtde_linhas'];
if (strlen($_POST['tipo_atendimento']) > 0) $tipo_atendimento = $_POST['tipo_atendimento'];

$btn_acao = trim(strtolower($_POST['btn_acao']));

if (strlen($_GET['os_revenda']) > 0)  $os_revenda = trim($_GET['os_revenda']);
if (strlen($_POST['os_revenda']) > 0) $os_revenda = trim($_POST['os_revenda']);

if(strlen($os_revenda)>0){
	$sql="SELECT count(*) as qtde_item from tbl_os_revenda_item where os_revenda=$os_revenda";
	$res=pg_exec($con,$sql);
	$qtde_item=pg_result($res,0,qtde_item);
}

if(strlen($qtde_item) > 0 && strlen($tipo_atendimento) == 0){
    $msg_erro = "Favor selecionar o tipo de atendimento";
    $qtde_item = "";
}
/* ====================  APAGAR  =================== */
if ($btn_acao == "apagar") {
	if(strlen($os_revenda) > 0){
		$sql = "DELETE FROM tbl_os_revenda
				WHERE  tbl_os_revenda.os_revenda = $os_revenda
				AND    tbl_os_revenda.fabrica    = $login_fabrica
				AND    tbl_os_revenda.posto      = $login_posto";
		$res = pg_exec ($con,$sql);

		$msg_erro = pg_errormessage($con);
		$msg_erro = substr($msg_erro,6);

		if (strlen ($msg_erro) == 0) {
			header("Location: $PHP_SELF");
			exit;
		}
	}
}

if ($btn_acao == "gravar"){
	if (strlen($_POST['sua_os']) > 0){
		$xsua_os = $_POST['sua_os'] ;
		$xsua_os = "00000" . trim ($xsua_os);
		$xsua_os = substr ($xsua_os, strlen ($xsua_os) - 5 , 5) ;
		$xsua_os = "'". $xsua_os ."'";
	}else{
		$xsua_os = "null";
	}

	$xdata_abertura = fnc_formata_data_pg($_POST['data_abertura']);
	$xdata_nf       = fnc_formata_data_pg($_POST['data_nf']);

	$nota_fiscal = $_POST["nota_fiscal"];
	if (strlen($nota_fiscal) == 0) {
		$xnota_fiscal = 'null';
	}else{
		$nota_fiscal = trim ($nota_fiscal);
		$nota_fiscal = str_replace (".","",$nota_fiscal);
		$nota_fiscal = str_replace (" ","",$nota_fiscal);
		$nota_fiscal = str_replace ("-","",$nota_fiscal);
		// $nota_fiscal = "000000" . $nota_fiscal;
		#$nota_fiscal = substr ($nota_fiscal,strlen($nota_fiscal)-14,14);
		$xnota_fiscal = "'" . $nota_fiscal . "'" ;
	}

	if (strlen($_POST['revenda_cnpj']) > 0) {
		$revenda_cnpj  = $_POST['revenda_cnpj'];
		$revenda_cnpj  = str_replace (".","",$revenda_cnpj);
		$revenda_cnpj  = str_replace ("-","",$revenda_cnpj);
		$revenda_cnpj  = str_replace ("/","",$revenda_cnpj);
		$revenda_cnpj  = str_replace (" ","",$revenda_cnpj);
		$xrevenda_cnpj = "'". $revenda_cnpj ."'";
	}else{
		$xrevenda_cnpj = "null";
	}

	$tipo_atendimento = $_POST['tipo_atendimento'];
	if (strlen (trim ($tipo_atendimento)) == 0) $msg_erro .= " Escolha o Tipo de Atendimento<BR>";
	if($tipo_atendimento == 18){
		/* CHAMADO 18127 BLACK E DECKER - EBANO */
		$motivo = $_POST["motivo"];
		if (($motivo == "") && ($login_fabrica == 1))
		{
			$msg_erro = "Para troca <FONT color='blue'><u>FATURADA</u></font> de <font color='blue'><u>REVENDA</u></font>, é necessário informar o motivo da troca.";
		}
		if($xnota_fiscal <> 'null'){
			$msg_erro = "Para troca faturada não é necessário digitar a Nota Fiscal.";
		}else{
			$xnota_fiscal = 'null';
		}
		if(strlen ($_POST['data_nf']) > 0 ){
			$msg_erro = "Para troca faturada não é necessário digitar a Data da Nota Fiscal.";
		}else{
			$xdata_nf = 'null';
		}
	}

	if ($xrevenda_cnpj <> "null") {
		$sql =	"SELECT *
				FROM    tbl_revenda
				WHERE   cnpj = $xrevenda_cnpj";
		$res = pg_exec($con,$sql);

		if (pg_numrows($res) == 0){
			$msg_erro = "CNPJ da revenda não cadastrado";
		}else{
			$revenda		= trim(pg_result($res,0,revenda));
			$nome			= trim(pg_result($res,0,nome));
			$endereco		= trim(pg_result($res,0,endereco));
			$numero			= trim(pg_result($res,0,numero));
			$complemento	= trim(pg_result($res,0,complemento));
			$bairro			= trim(pg_result($res,0,bairro));
			$cep			= trim(pg_result($res,0,cep));
			$cidade			= trim(pg_result($res,0,cidade));
			$fone			= trim(pg_result($res,0,fone));
			$cnpj			= trim(pg_result($res,0,cnpj));

			if (strlen($revenda) > 0)
				$xrevenda = "'". $revenda ."'";
			else
				$xrevenda = "null";

			if (strlen($nome) > 0)
				$xnome = "'". $nome ."'";
			else
				$xnome = "null";

			if (strlen($endereco) > 0)
				$xendereco = "'". $endereco ."'";
			else
				$xendereco = "null";

			if (strlen($numero) > 0)
				$xnumero = "'". $numero ."'";
			else
				$xnumero = "null";

			if (strlen($complemento) > 0)
				$xcomplemento = "'". $complemento ."'";
			else
				$xcomplemento = "null";

			if (strlen($bairro) > 0)
				$xbairro = "'". $bairro ."'";
			else
				$xbairro = "null";

			if (strlen($cidade) > 0)
				$xcidade = "'". $cidade ."'";
			else
				$xcidade = "null";

			if (strlen($cep) > 0)
				$xcep = "'". $cep ."'";
			else
				$xcep = "null";

			if (strlen($fone) > 0)
				$xfone = "'". $fone ."'";
			else
				$xfone = "null";
			if (strlen($cnpj) > 0)
				$xcnpj = "'". $cnpj ."'";
			else
				$xcnpj = "null";

		}
	}else{
		$msg_erro = "CNPJ não informado";
	}

	if (strlen($_POST['revenda_fone']) > 0) {
		$xrevenda_fone = "'". $_POST['revenda_fone'] ."'";
	}else{
		$xrevenda_fone = "null";
	}
	if($xrevenda_fone == "null"){$msg_erro .="Insira o telefone da revenda.<BR>";}

  	//3334608
	if (!$login_fabrica==1) {
		if (strlen($_POST['revenda_email']) > 0) {
			$xrevenda_email = "'". $_POST['revenda_email'] ."'";
		}else{
			// HD 20281
			$msg_erro .="Digite o email da Revenda. <br>";
		}
	}else{
		if (strlen($_POST['revenda_email']) > 0) {
			/*if (!filter_var($_POST['revenda_email'], FILTER_VALIDATE_EMAIL)) {*/
			if (!is_email($_POST['revenda_email'])){
				$msg_erro .='E-mail de contato da revenda obrigatório.<br />
							Caso não possuir endereço eletrônico, deverá ser informado o e-mail: "nt@nt.com.br".  <br>';
			}else{
				$xrevenda_email = "'". $_POST['revenda_email'] ."'";
			}
		}else{
			// HD 20281
			$msg_erro .='E-mail de contato da revenda obrigatório .<br />
						Caso não possuir endereço eletrônico, deverá ser informado o e-mail: "nt@nt.com.br".  <br>';
		}
	}


	if(strlen($xrevenda_email) >0 and strlen($revenda) >0){
		$sql="UPDATE tbl_revenda set email=$xrevenda_email where revenda=$revenda";
		$res=pg_exec($con,$sql);
	}

	if (strlen($_POST['obs']) > 0) {
		$xobs = "'". str_replace("'","''",$_POST['obs']) ."'";
	}else{
		$xobs = "null";
	}

	if (strlen($_POST['motivo']) > 0) {
		$xmotivo = "'". str_replace("'","''",$_POST['motivo']) ."'";
	}else{
		$xmotivo = "null";
	}

	if (strlen($_POST['contrato']) > 0) {
		$xcontrato = "'". $_POST['contrato'] ."'";
	}else{
		$xcontrato = "'f'";
	}

	// HD 18051 20281
	/*if(strlen($consumidor_email) ==0 ){
		$msg_erro .="Digite o email de contato. <br>";
	}else{
		$consumidor_email = trim($_POST['consumidor_email']);
	}*/

    if ($login_fabrica == '1') {
        foreach (range(0, 4) as $idx) {
            if (!empty($_FILES["foto_nf"]["size"][$idx][0])) {
                break;
            }

            $tmp_erro = "Anexo de NF obrigatório.<br/>";
        }

        if (!empty($tmp_erro)) {
            $msg_erro .= $tmp_erro;
        }
    }

	if (strlen ($msg_erro) == 0) {

		$res = pg_exec ($con,"BEGIN TRANSACTION");

		if (strlen ($os_revenda) == 0) {
			#-------------- insere ------------
			$sql = "INSERT INTO tbl_os_revenda (
						fabrica                           ,
						sua_os                            ,
						data_abertura                     ,
						data_nf                           ,
						nota_fiscal                       ,
						revenda                           ,
						obs                               ,
						motivo                            ,
						digitacao                         ,
						posto                             ,
						contrato
					) VALUES (
						$login_fabrica                    ,
						$xsua_os                          ,
						$xdata_abertura                   ,
						$xdata_nf                         ,
						$xnota_fiscal                     ,
						$revenda                          ,
						$xobs                             ,
						$xmotivo                          ,
						current_timestamp                 ,
						$login_posto                      ,
						$xcontrato
					)";
		}else{
			$sql = "UPDATE tbl_os_revenda SET
						fabrica       = $login_fabrica                   ,
						sua_os           = $xsua_os                         ,
						data_abertura    = $xdata_abertura                  ,
						data_nf          = $xdata_nf                        ,
						nota_fiscal      = $xnota_fiscal                    ,
						revenda          = $revenda                         ,
						obs              = $xobs                            ,
						motivo           = $xmotivo                         ,
						posto            = $login_posto                     ,
						contrato         = $xcontrato
					WHERE os_revenda  = $os_revenda
					AND	 posto        = $login_posto
					AND	 fabrica      = $login_fabrica ";
		}



$msg_debug = $sql."<br>";
		$res = @pg_exec ($con,$sql);
		$msg_erro = pg_errormessage($con);

# echo $sql."<br><br>";

		if (strlen($msg_erro) == 0 and strlen($os_revenda) == 0) {
			$res        = pg_exec ($con,"SELECT CURRVAL ('seq_os_revenda')");
			$os_revenda = pg_result ($res,0,0);
			$msg_erro   = pg_errormessage($con);


			if (strlen ($msg_erro) > 0) {
				$sql = "UPDATE tbl_cliente SET contrato = $xcontrato
						WHERE  tbl_cliente.cliente  = $revenda";
				$res = pg_exec ($con,$sql);
				$msg_erro = pg_errormessage($con);
			}
		}


			$objectId = $_POST['objectid'];

		    if ($login_fabrica == 1) {
		        $filesByImageUploader = 0;

		        $sqlDocs = "SELECT tdocs, tdocs_id, referencia, obs 
		                      FROM tbl_tdocs 
		                     WHERE referencia_id = 0 
		                       AND referencia = '$objectId' 
		                       AND contexto = 'os'";
		        $resDocs = pg_query($con,$sqlDocs);
		        $filesByImageUploader = pg_num_rows($resDocs);
		    }

	    	if ($login_fabrica == 1 && !$filesByImageUploader) {
	            foreach (range(0, 4) as $idx) {
	                if ($anexaNotaFiscal and $_FILES["foto_nf"]['tmp_name'][$idx][0] != '') {
	                    $file = array(
	                        "name" => $_FILES["foto_nf"]["name"][$idx][0],
	                        "type" => $_FILES["foto_nf"]["type"][$idx][0],
	                        "tmp_name" => $_FILES["foto_nf"]["tmp_name"][$idx][0],
	                        "error" => $_FILES["foto_nf"]["error"][$idx][0],
	                        "size" => $_FILES["foto_nf"]["size"][$idx][0]
	                    );

	                    $anexou = anexaNF("r".$os_revenda, $file);
	                    if ($anexou !== 0) $msg_erro .= (is_numeric($anexou)) ? $msgs_erro[$anexou] : $anexou; // '0' é que executou OK
	                }
	            }
	        } else {
	            if ($anexaNotaFiscal and $_FILES["foto_nf"]['tmp_name'] != '') { # HD 174117
	                $anexou = anexaNF("r".$os_revenda, $_FILES['foto_nf']);
	                if ($anexou !== 0) $msg_erro .= (is_numeric($anexou)) ? $msgs_erro[$anexou] : $anexou; // '0' é que executou OK
	            }
	        }

		$filesByImageUploader = 0;
		if ($login_fabrica == 1) {

			$sqlDocs = "SELECT tdocs, tdocs_id, referencia, obs 
			              FROM tbl_tdocs 
			             WHERE referencia_id = 0 
			               AND referencia = '$objectId' 
			               AND contexto = 'os'";
			$resDocs = pg_query($con,$sqlDocs);
			$resDocs = pg_fetch_all($resDocs);

			if(count($resDocs)>0 && $resDocs != false){

				foreach ($resDocs as $key => $value) {

					$sqlUpate = "UPDATE tbl_tdocs 
					                SET fabrica = $login_fabrica, 
					                    referencia = 'revenda', 
					                    referencia_id = $os_revenda 
					              WHERE tdocs = ".$value['tdocs'];
					$res = pg_query($con, $sqlUpate);

					if(pg_last_error($con)){
						$msg_erro .= "<br>".pg_last_error($con);
					}

					$filesByImageUploader += 1;

				}
			}
		}

		//HD 9013 21517 56662
		if(strlen($os_revenda)>0 AND strlen($msg_erro) == 0 ){
			$sql="SELECT tbl_os_revenda.sua_os,
				 tbl_posto_fabrica.codigo_posto
				 FROM tbl_os_revenda
				 	JOIN tbl_os_revenda_item ON tbl_os_revenda_item.os_revenda = tbl_os_revenda.os_revenda
					JOIN tbl_posto_fabrica on tbl_os_revenda.posto= tbl_posto_fabrica.posto and tbl_posto_fabrica.fabrica=$login_fabrica
					WHERE tbl_os_revenda.nota_fiscal::float = (
								SELECT nota_fiscal::float
								FROM   tbl_os_revenda
								WHERE  os_revenda = $os_revenda
								and posto      = $login_posto
								and fabrica    = $login_fabrica
								)
					and   revenda       = (
								SELECT revenda
								FROM   tbl_os_revenda
								WHERE  os_revenda = $os_revenda
								and posto      = $login_posto
								and fabrica    = $login_fabrica
								)
					AND tbl_os_revenda.os_revenda <>$os_revenda
					AND tbl_os_revenda_item.tipo_atendimento IS NOT NULL
					AND tbl_posto_fabrica.posto   = $login_posto
					AND tbl_os_revenda.fabrica    = $login_fabrica
					AND tbl_os_revenda.excluida != 't'";
			$res=pg_exec($con,$sql);
			if(pg_numrows($res)>0){
				$sua_os       = pg_result($res,0,sua_os);
				$codigo_posto = pg_result($res,0,codigo_posto);
				$msg_erro="Nota fiscal já foi informada na OS $codigo_posto$sua_os. O sistema permite a digitação de apenas uma OS de revenda para cada nota fiscal, pois é possível incluir na mesma OS a quantidade total de produtos que serão atendidos em garantia.";
			}
		}

		if (strlen($msg_erro) == 0) {
			//$qtde_item = $_POST['qtde_item'];
			$sql = "DELETE FROM tbl_os_revenda_item WHERE  os_revenda = $os_revenda";
			$res = @pg_exec($con,$sql);
			$msg_erro = pg_errormessage($con);

			for ($i = 0 ; $i < $qtde_item ; $i++) {
				$referencia                    = trim($_POST["produto_referencia_".$i]);
				$codigo_fabricacao             = trim($_POST["codigo_fabricacao_".$i]);
				$serie                         = trim($_POST["produto_serie_".$i]);
				$capacidade                    = trim($_POST["produto_capacidade_".$i]);
				$voltagem                      = trim($_POST["produto_voltagem_".$i]);
				$type                          = trim($_POST["type_".$i]);
				$embalagem_original            = trim($_POST["embalagem_original_".$i]);
				$sinal_de_uso                  = trim($_POST["sinal_de_uso_".$i]);
                $defeito_constatado_descricao  = trim($_POST["defeito_constatado_descricao_".$i]);
				$tipo_atendimento_item         = trim($_POST["tipo_atendimento_item_".$i]);
				//HD 244476
				$produto_troca				   = trim($_POST["produto_troca_".$i]);
				if (strlen(trim($_POST['produto_referencia_troca_'.$i])) == 0) {
					$msg_erro = 'Informe o produto para troca.';
					$linha_erro = $i;
				}else{
					$referencia_troca = "'".trim($_POST['produto_referencia_troca_'.$i])."'";
				}
				if (strlen(trim($_POST['produto_voltagem_troca_'.$i])) == 0) {
					$msg_erro = 'Informe a voltagem do produto para troca. Caso esteja em branco clique na lupa para pesquisar o produto a ser trocado.';
				$linha_erro = $i;
				}else{
					$voltagem_troca = trim($_POST['produto_voltagem_troca_'.$i]);
				}

				if (strlen($tipo_atendimento_item) == 0) $msg_erro = "Favor, escolher o tipo de atendimento da OS";
			//	if (strlen($sinal_de_uso) == 0)       $sinal_de_uso = "f";

				if (strlen($type) == 0)
					$type = "null";
				else
					$type = "'". $type ."'";

				if (strlen($voltagem) == 0)
					$voltagem = "null";
				else
					$voltagem = "'". $voltagem ."'";


				if (strlen($voltagem_troca) == 0)
					$voltagem_troca = "null";
				else
					$voltagem_troca = "'". $voltagem_troca ."'";

				if (strlen($msg_erro) == 0) {
					if (strlen ($referencia) > 0) {
						$referencia = strtoupper ($referencia);
						$referencia = str_replace ("-","",$referencia);
						$referencia = str_replace (".","",$referencia);
						$referencia = str_replace ("/","",$referencia);
						$referencia = str_replace (" ","",$referencia);
						$referencia = "'". $referencia ."'";

						$sql =	"SELECT tbl_produto.produto, tbl_produto.numero_serie_obrigatorio, tbl_linha.linha
								FROM    tbl_produto
								JOIN    tbl_linha USING (linha)
								WHERE   UPPER(tbl_produto.referencia_pesquisa) = UPPER($referencia)
								AND     UPPER(tbl_produto.voltagem) = UPPER($voltagem)
								AND     tbl_linha.fabrica = $login_fabrica;";
						$res = pg_exec($con,$sql);

						if (pg_numrows($res) == 0) {
							$msg_erro = " Produto $referencia não cadastrado. <BR>";
							$linha_erro = $i;
						}else{
							$produto                  = pg_result($res,0,produto);
							$numero_serie_obrigatorio = pg_result($res,0,numero_serie_obrigatorio);
							$linha                    = pg_result($res,0,linha);
						}


						if (strlen($defeito_constatado_descricao) == 0) {
							$msg_erro="Por favor, Preencher o campo defeito constatado.";
							$linha_erro = $i;
						}


						if($tipo_atendimento_item == 18 AND strlen($produto) > 0){
						//pega o valor da troca
							$sql = "select valor_troca,
										troca_garantia,
										troca_faturada,
										troca_obrigatoria
									from tbl_produto
									join tbl_familia using(familia)
									where fabrica = $login_fabrica
										and produto=$produto";
							$res = pg_exec($con,$sql);
							if(pg_numrows($res)>0){
								$valor_troca       = pg_result($res,0,valor_troca);
								$troca_garantia    = pg_result($res,0,troca_garantia);
								$troca_faturada    = pg_result($res,0,troca_faturada);
								$troca_obrigatoria = pg_result($res,0,troca_obrigatoria);
								if($troca_faturada == 'f' and $troca_garantia == 'f' and $troca_obrigatoria =='t'){
									$msg_erro = "Há incorreções no cadastro do produto que impossibilitam a troca. Favor entrar em contato com o fabricante.";
									$linha_erro = $i;
								}elseif($troca_faturada == 'f' and $troca_garantia == 'f' and $troca_obrigatoria =='f'){
									$msg_erro = "Este produto não é troca. Solicitar peças e realizar o reparo normalmente. Em caso de dúvidas entre em contato com o suporte da sua região";
									$linha_erro = $i;
								}else{
									if($troca_faturada == 'f' and $troca_garantia == 't'){
										$msg_erro = "Este produto não é atendido em troca faturada, apenas troca em garantia.";
										$linha_erro = $i;
									}
								}
							}
						}

					if($tipo_atendimento_item == 17 AND strlen($produto) > 0){ //troca garantia
						$valor_troca = "0";

						$sqlT = "SELECT valor_troca as valor_faturada,
										troca_garantia ,
										troca_faturada ,
										troca_obrigatoria
								FROM tbl_produto
								JOIN tbl_familia USING(familia)
								WHERE fabrica      = $login_fabrica
								AND produto        = $produto";
						$resT = pg_exec($con,$sqlT);

						$troca_garantia    = pg_result($resT,0,troca_garantia);
						$troca_faturada    = pg_result($resT,0,troca_faturada);
						$valor_faturada    = pg_result($resT,0,valor_faturada);
						$troca_obrigatoria = pg_result($resT,0,troca_obrigatoria);
						if($troca_faturada == 'f' and $troca_garantia == 'f' and $troca_obrigatoria =='t'){
							$msg_erro = "Há incorreções no cadastro do produto que impossibilitam a troca. Favor entrar em contato com o fabricante.";
							$linha_erro = $i;
						}elseif($troca_faturada == 'f' and $troca_garantia == 'f' and $troca_obrigatoria =='f'){
							$msg_erro = "Este produto não é troca. Solicitar peças e realizar o reparo normalmente. Em caso de dúvidas entre em contato com o suporte da sua região";
							$linha_erro = $i;
						}else{
							if($troca_faturada == 't' and $troca_garantia == 'f'){
								$msg_erro = "Este produto não é atendido em troca em garantia, apenas troca faturada.";
								$linha_erro = $i;
							}
						}
					}

						if (strlen($serie) == 0) {
							if ($linha == 198) {
								$msg_erro .= " Número de série do produto $referencia é obrigatório. <BR>";
								$linha_erro = $i;
							}else{
								$serie = 'null';
							}
						}else{
							if ($linha == 199 OR $linha == 200) {
								$msg_erro .= " Número de série do produto $referencia não pode ser preenchido. <BR>";
								$linha_erro = $i;
							}else{
								$serie = "'". $serie ."'";
							}
						}

						if (strlen($capacidade) == 0) {
							$xcapacidade = 'null';
						}else{
							$xcapacidade = "'".$capacidade."'";
						}

						if (strlen($codigo_fabricacao) == 0) {
							$msg_erro = "Digite o Código de fabricação.<BR>";
						}else{
							$codigo_fabricacao = "'". $codigo_fabricacao ."'";
						}

						if (strlen($embalagem_original) == 0){
								$msg_erro .= "Gentileza marcar opção sim ou não para os campos embalagem original e/ou sinal de uso.<BR>";
						}
						if (strlen($sinal_de_uso) == 0)  {
								$msg_erro .= "Gentileza marcar opção sim ou não para os campos embalagem original e/ou sinal de uso.<BR>";
						}
						// HD 46146
						$referencia_troca = strtoupper ($referencia_troca);
						$referencia_troca = str_replace ("-","",$referencia_troca);
						$referencia_troca = str_replace (".","",$referencia_troca);
						$referencia_troca = str_replace ("/","",$referencia_troca);
						$referencia_troca = str_replace (" ","",$referencia_troca);

						//HD 244476: Troca de um para muitos (KIT) para a Black
						if (strlen($msg_erro) == 0 && $login_fabrica == 1 && $referencia_troca == "'KIT'") {
							$kit = intval($produto_troca);

							$sql = "
							SELECT
							produto_troca_opcao

							FROM
							tbl_produto_troca_opcao

							WHERE
							produto = $produto
							AND kit = $kit
							";
							$res = pg_query($con, $sql);

							if (pg_num_rows($res) == 0) {
								$msg_erro = "KIT $kit não encontrado como opção de troca para o produto $referencia";
							}
							else {
								$produto_troca = $produto;
							}
						}
						//HD 244476: FIM
						elseif (strlen($msg_erro) == 0) {
							//HD 244476: Troca de um para muitos (KIT) para a Black
							$kit = "null";

							$sql = "SELECT tbl_produto.produto, tbl_produto.linha
									FROM   tbl_produto
									JOIN   tbl_linha USING (linha)
									WHERE  UPPER(tbl_produto.referencia_pesquisa) = UPPER($referencia_troca)
									AND    tbl_linha.fabrica      = $login_fabrica
									AND    tbl_produto.ativo IS TRUE";
							if(strlen($voltagem_troca) >0 and $voltagem_troca <> 'null'){
								$sql .=" AND     UPPER(tbl_produto.voltagem) = UPPER($voltagem_troca::text)";
							}
							$res = pg_exec ($con,$sql);

							if (pg_numrows ($res) == 0) {
								$msg_erro = " Produto $referencia_troca não cadastrado";
								$linha_erro = $i;
							}else{
								$produto_troca = pg_result ($res,0,produto);
							}

							if (strlen($msg_erro) == 0) {
								$sql = "SELECT produto_opcao as produto
										FROM   tbl_produto_troca_opcao
										WHERE  produto = $produto
										AND    produto_opcao = $produto_troca";
								$res = pg_exec ($con,$sql);

								if (pg_numrows($res)==0) {
									$sql = "SELECT $produto as produto
											FROM tbl_produto_troca_opcao
											WHERE $produto = $produto_troca
											AND (select count(*) from tbl_produto_troca_opcao where produto = $produto) = 0
											LIMIT 1";
									$res = pg_exec ($con,$sql);
								}

								if (pg_numrows ($res) == 0) {
									$msg_erro = " Produto $referencia_troca não encontrado como opção de troca para o produto $referencia";
									$linha_erro = $i;
								}else{
									$produto_troca = pg_result ($res,0,produto);
								}
							}
						}

						if($login_fabrica == 1 and $tipo_atendimento_item == 18){
							$data_ab = str_replace("'", "", formata_data($_POST['data_abertura']));
							$data_notafiscal = str_replace("'", "", formata_data($_POST['data_nf']));
							$limite_dias = 1095; // 3 anos convertidos para dias hd-6330221 

							$dt_abertura 	= new DateTime($data_ab);
							$dt_compra 		= new DateTime($data_notafiscal);
							$diferenca 		= $dt_abertura->diff($dt_compra);

							$diferenca_dias = $diferenca->days;

							if($diferenca_dias > $limite_dias ){
								$sql = "SELECT parametros_adicionais 
										from tbl_produto 
										WHERE produto = $produto
										and fabrica_i = $login_fabrica "; 
								$res = pg_query($con, $sql);
								if(strlen(pg_last_error($con)>0)){
									$msg_erro .= "Falha ao consultar data de descontinuado.";
								}
								if(pg_num_rows($res)>0){
									$parametros_adicionais 	= json_decode(pg_fetch_result($res, 0, 'parametros_adicionais'),true);
									$data_descontinuado 	= formata_data($parametros_adicionais['data_descontinuado']); 

									$dt_descontinuado 	= new DateTime($data_descontinuado);
									$dt_abertura 		= new DateTime(formata_data($data_abertura));
									$diferenca 			= $dt_abertura->diff($dt_descontinuado);

									$diferenca_dias = $diferenca->days;

									if($diferenca_dias > $limite_dias){
										$msg_erro .= "Esse produto saiu de linha há mais de 3 anos. Dessa forma, já cumprimos um prazo considerável para manter peças para reposição ou troca do produto, não sendo mais possível a troca desse modelo. Qualquer dúvida, por favor, entre em contato com o seu suporte";
									}
								}
							}
						}

						if (strlen ($msg_erro) == 0) {
							$sql = "INSERT INTO tbl_os_revenda_item (
										os_revenda                     ,
										produto                        ,
										serie                          ,
										codigo_fabricacao              ,
										nota_fiscal                    ,
										data_nf                        ,
										capacidade                     ,
										type                           ,
										embalagem_original             ,
										sinal_de_uso                   ,
										defeito_constatado_descricao   ,
										produto_troca				   ,
										kit                             ,
										tipo_atendimento
									) VALUES (
										$os_revenda                    ,
										$produto                       ,
										$serie                         ,
										$codigo_fabricacao             ,
										$xnota_fiscal                  ,
										$xdata_nf                      ,
										$xcapacidade                   ,
										$type                          ,
										'$embalagem_original'          ,
										'$sinal_de_uso'                ,
										'$defeito_constatado_descricao',
										$produto_troca				   ,
										$kit                            ,
										$tipo_atendimento_item
									)";
							$res = pg_exec ($con,$sql);
							$msg_erro = pg_errormessage($con);


							if (strlen($msg_erro) == 0) {
								$res        = pg_exec ($con,"SELECT CURRVAL ('seq_os_revenda_item')");
								$os_revenda_item = pg_result ($res,0,0);
								$msg_erro   = pg_errormessage($con);

								$conta_qtde++;

							}

							if (strlen ($msg_erro) == 0) {
								$sql = "SELECT fn_valida_os_item_revenda_black($os_revenda,$login_fabrica,$produto,$os_revenda_item)";
								$res = @pg_exec ($con,$sql);
								$msg_erro = pg_errormessage($con);

							}

							if (strlen ($msg_erro) > 0) {
								break ;
							}
						}
					}
				}
			}

			if($qtde_item!=$conta_qtde and strlen($msg_erro) ==0){
				$msg_erro="FOI INFORMADO NO CAMPO QUANTIDADE DE PRODUTOS B&D/DW NESSA NOTA FISCAL $qtde_item E DETECTAMOS A DIGITAÇÃO DE QUANTIDADE INFERIOR À INFORMADA. GENTILEZA VERIFICAR.";
			}

			if (strlen ($msg_erro) == 0) {
				$sql = "SELECT fn_valida_os_revenda($os_revenda,$login_posto,$login_fabrica)";
				$res = @pg_exec ($con,$sql);
				$msg_erro = pg_errormessage($con);
			}
		}
	}


	if (strlen ($msg_erro) == 0) {
		$res = pg_exec ($con,"COMMIT TRANSACTION");
		header("Location: os_revenda_finalizada.php?os_revenda=$os_revenda");
		exit;
	}else{
		if (strpos ($msg_erro,"tbl_os_revenda_unico") > 0) $msg_erro = " O Número da Ordem de Serviço do fabricante já está cadastrado.";
		if (strpos ($msg_erro,"null value in column \"data_abertura\" violates not-null constraint") > 0) $msg_erro = "Data da abertura deve ser informada.";

		if (strpos ($msg_erro,'date/time field value out of range') > 0) $msg_erro ="O formato da data incorreto, por favor, verificar.";
		$os_revenda = trim($_POST['os_revenda']);

		$res = pg_exec ($con,"ROLLBACK TRANSACTION");
	}
}

if ((strlen($msg_erro) == 0) AND (strlen($os_revenda) > 0)){
	// seleciona do banco de dados
	$sql = "SELECT  tbl_os_revenda.sua_os                                                ,
					tbl_os_revenda.obs                                                   ,
					tbl_os_revenda.motivo                                                ,
					tbl_os_revenda.contrato                                              ,
					to_char(tbl_os_revenda.data_abertura,'DD/MM/YYYY') AS data_abertura  ,
					to_char(tbl_os_revenda.data_nf      ,'DD/MM/YYYY') AS data_nf        ,
					tbl_os_revenda.nota_fiscal                                           ,
					tbl_revenda.nome  AS revenda_nome                                    ,
					tbl_revenda.cnpj  AS revenda_cnpj                                    ,
					tbl_revenda.fone  AS revenda_fone                                    ,
					tbl_revenda.email AS revenda_email                                   ,
					tbl_os_revenda.explodida
			FROM	tbl_os_revenda
			JOIN	tbl_revenda ON tbl_os_revenda.revenda = tbl_revenda.revenda
			JOIN	tbl_fabrica USING (fabrica)
			JOIN    tbl_posto USING (posto)
			JOIN    tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
					AND tbl_posto_fabrica.fabrica = tbl_fabrica.fabrica
			WHERE	tbl_os_revenda.os_revenda = $os_revenda
			AND		tbl_os_revenda.posto      = $login_posto
			AND		tbl_os_revenda.fabrica    = $login_fabrica ";
	$res = pg_exec($con, $sql);

	if (pg_numrows($res) > 0){
		$sua_os           = pg_result($res,0,sua_os);
		$data_abertura    = pg_result($res,0,data_abertura);
		$data_nf          = pg_result($res,0,data_nf);
		$nota_fiscal      = pg_result($res,0,nota_fiscal);
		$revenda_nome     = pg_result($res,0,revenda_nome);
		$revenda_cnpj     = pg_result($res,0,revenda_cnpj);
		$revenda_fone     = pg_result($res,0,revenda_fone);
		$revenda_email    = pg_result($res,0,revenda_email);
		$obs              = pg_result($res,0,obs);
		$motivo           = pg_result($res,0,motivo);
		$contrato         = pg_result($res,0,contrato);
		$explodida        = pg_result($res,0,explodida);
// 		$tipo_atendimento = pg_result($res,0,tipo_atendimento);

		if (strlen($explodida) > 0){
			header("Location:os_revenda_parametros.php");
			exit;
		}

		$sql = "SELECT *
				FROM   tbl_os
				WHERE  sua_os ILIKE '$sua_os-%'
				AND    posto   = $login_posto
				AND    fabrica = $login_fabrica";
		$resX = pg_exec($con, $sql);

		if (pg_numrows($resX) == 0) $exclui = 1;

		$sql = "SELECT  tbl_os_revenda_item.nota_fiscal,
						to_char(tbl_os_revenda_item.data_nf, 'DD/MM/YYYY') AS data_nf
				FROM	tbl_os_revenda_item
				JOIN	tbl_os_revenda ON tbl_os_revenda.os_revenda = tbl_os_revenda_item.os_revenda
				WHERE	tbl_os_revenda.os_revenda = $os_revenda
				AND		tbl_os_revenda.posto      = $login_posto
				AND		tbl_os_revenda.fabrica    = $login_fabrica
				AND		tbl_os_revenda_item.nota_fiscal NOTNULL
				AND		tbl_os_revenda_item.data_nf     NOTNULL LIMIT 1";
		$res = pg_exec($con, $sql);

		if (pg_numrows($res) > 0){
			$nota_fiscal = pg_result($res,0,nota_fiscal);
			$data_nf     = pg_result($res,0,data_nf);
		}
	}else{
		#header('Location: os_revenda_troca.php');
		#exit;
	}
}


$title			= "Cadastro de Ordem de Serviço - Revenda";
$layout_menu	= 'os';
include "cabecalho.php";
include "javascript_pesquisas.php";
?>
<!-- <script language='javascript' src='js/jquery-1.2.1.pack.js'></script>
 -->
 <script language='javascript' src='js/jquery-1.8.3.min.js'></script>

<script type="text/javascript" src="js/jquery.maskedinput.js"></script>
<script type="text/javascript" src="plugins/jquery/datepick/jquery.datepick.js"></script>
<script type="text/javascript" src="plugins/jquery/datepick/jquery.datepick-pt-BR.js"></script>
<link rel="stylesheet" type="text/css" href="plugins/shadowbox/shadowbox.css" media="all">
<script src="plugins/shadowbox/shadowbox.js" type="text/javascript"></script>


<script language="JavaScript">

$(document).ready(function(){
	$("input[name='data_abertura']").datepick({startdate:'01/01/2000'});
	$("input[name='data_nf']").datepick({startdate:'01/01/2000'});

	<? if($login_fabrica == 1) { ?>
		$(":input").click(function(){
			var alerta = $("#alerta").val();
			if(alerta=='0'){ /* HD 117212 */
				alerta_revenda_bed();
			}
		});
		/*verifyObjectId($("#objectid").val()); HD - 6225921 */
	<? } ?>

	<?php if ($login_fabrica == 1) { ?>
	$('input').focus(function(){
        var cnpj = $('#revenda_cnpj').val();
        var lista_cnpj = [
            '53.296.273/0001-91',
            '53.296.273/0032-98',
            '03.997.959/0002-12',
            '03.997.959/0003-01'
        ];

        if ($.inArray(cnpj, lista_cnpj) >= 0 && $('#alerta').val() == '0') {
        	$('#alerta').val(1);
            janela=window.open("os_info_black2.php", "janela", "toolbar=no, location=no, status=no, scrollbars=no, directories=no, width=501, height=400, top=18, left=0");
            janela.focus();
        }
	});
	<?php } ?>

	$("input[rel='fone']").maskedinput("(99) 9999-9999");
	$("#data_nf").maskedinput("99/99/9999");
	$("#data_abertura").maskedinput("99/99/9999");

});
  function verifyObjectId(objectId){

    $.ajax("controllers/TDocs.php",{
            method: "POST",
            data:{
              "ajax": "verifyObjectId",
              "objectId": objectId,
              "context": "os"
            }
          }).done(function(response){
            response = JSON.parse(response);

            if(response.exception == undefined){
              $(response).each(function(idx,elem){

                if($("#"+elem.tdocs_id).length == 0){
                  //var img = $("<div class='env-img'><img id='"+elem.tdocs_id+"' style='width: 150px; border: 2px solid #e2e2e2; margin-left: 5px;margin-right: 5px;'><button data-tdocs='"+elem.tdocs_id+"'>Excluir</button></div>");
                  //##var img = $("<div class='env-img'><a href='http://api2.telecontrol.com.br/tdocs/document/id/"+elem.tdocs_id+"/file/imagem.jpg' target='_BLANK' ><img id='"+elem.tdocs_id+"' style='width: 90px; border: 2px solid #e2e2e2; margin-left: 5px;margin-right: 5px;'></a><br/><button data-tdocs='"+elem.tdocs_id+"'>Excluir</button></div>");
                  //$(img).find("img").attr("src","http://api2.telecontrol.com.br/tdocs/document/id/"+elem.tdocs_id);


                  var img = $("<div class='env-img'><a href='http://api2.telecontrol.com.br/tdocs/document/id/"+elem.tdocs_id+"/file/imagem.jpg' target='_BLANK' ><img id='"+elem.tdocs_id+"' style='width: 90px; border: 2px solid #e2e2e2; margin-left: 5px;margin-right: 5px;'></a><br/><button class='btn-danger' data-tdocs='"+elem.tdocs_id+"'>Excluir</button></div>");

                  $(img).find("img").attr("src","http://api2.telecontrol.com.br/tdocs/document/id/"+elem.tdocs_id+"/file/imagem.jpg");
                  $(img).find("button").click(function(){
                      $.ajax("controllers/TDocs.php",{
                        method: "POST",
                        data: {
                          "ajax": "removeImage",
                          "objectId": elem.tdocs_id,
                          "context": "os"
                        }
                      }).done(function(response){
                          response = JSON.parse(response);
                          console.log(response);
                          if(response.res == 'ok'){
                            $("#"+elem.tdocs_id).parents(".env-img").fadeOut(1000);
                          }else{
                            alert("Não foi possível excluir o anexo, por favor tente novamente");
                          }
                      });
                  });

                  $("#env-images").append(img);
                  setupZoom();
                  console.log(elem.tdocs_id);
                }
              });
            }
          });
  }
  setIntervalRunning = false;
  setIntervalHandler = null;

  function getQrCode(){
    $("#btn-qrcode-request").fadeOut(1000);
    $("#btn-google-play").fadeOut(1000);
    $.ajax("controllers/QrCode.php",{
      method: "POST",
      data: {
        "ajax": "requireQrCode",
        "options": [
          "notafiscal"
        ],
        "title": "Upload de Nota Fiscal",
        "objectId": $("#objectid").val()
      }
   }).done(function(response){

      response = JSON.parse(response);
      console.log(response);

      $("#env-qrcode").find("img").attr("src",response.qrcode)
      $("#env-qrcode").fadeIn(1000);

      if(setIntervalRunning==false){
        setIntervalHandler = setInterval(function(){
          console.log("buscando...");


          verifyObjectId($("#objectid").val());
        },5000);
      }
   });
  }
function addAnexoUpload()
{
    var tpl = $("#anexoTpl").html();
    var id = $("#qtde_anexos").val();

    if (id == "5") {
        return;
    }

    var tr = '<tr>' + tpl.replace('@ID@', id) + '</tr>';
    $("#qtde_anexos").val(parseInt(id) + 1);

    $("#input_anexos").append(tr);
}

function fnc_pesquisa_revenda (campo, tipo) {
	var url = "";
	if (tipo == "nome") {
		url = "pesquisa_revenda.php?nome=" + campo.value + "&tipo=nome";
	}
	if (tipo == "cnpj") {
		url = "pesquisa_revenda.php?cnpj=" + campo.value + "&tipo=cnpj";
	}
	janela = window.open(url,"janela","toolbar=no,location=yes,status=yes,scrollbars=yes,directories=no,width=500,height=400,top=18,left=0");
	janela.nome			= document.frm_os.revenda_nome;
	janela.cnpj			= document.frm_os.revenda_cnpj;
	janela.fone			= document.frm_os.revenda_fone;
	janela.cidade		= document.frm_os.revenda_cidade;
	janela.estado		= document.frm_os.revenda_estado;
	janela.endereco		= document.frm_os.revenda_endereco;
	janela.numero		= document.frm_os.revenda_numero;
	janela.complemento	= document.frm_os.revenda_complemento;
	janela.bairro		= document.frm_os.revenda_bairro;
	janela.cep			= document.frm_os.revenda_cep;
	janela.email		= document.frm_os.revenda_email;
	janela.focus();
	<?php if ($login_fabrica == 1) { ?>
	$('#alerta').val(0);
	<?php } ?>
}

/* ============= Função PESQUISA DE PRODUTOS ====================
Nome da Função : fnc_pesquisa_produto (codigo,descricao)
		Abre janela com resultado da pesquisa de Produtos pela
		referência (código) ou descrição (mesmo parcial).
=================================================================*/

function verifica_produtos_troca(referencia){

    $.ajax({
        type: 'POST',
        dataType:"JSON",
        url: 'ajax_verifica_troca.php',
        data: {
            ajax_verifica_troca : true,
            produto : referencia
        },
    }).done(function(data) {
        if (data.mostra_shadowbox) {
        	informa_produtos_troca(data.produto);
        }
    });

}

function informa_produtos_troca(produto) {
	Shadowbox.init();

	Shadowbox.open({
		content :   "produtos_disponiveis_troca.php?produto="+produto,
		player  :   "iframe",
		title   :   "Produtos disponíveis para troca",
		width   :   800,
		height  :   500
	});
}

function fnc_pesquisa_produto (campo, campo2, campo3, tipo, linha = null) {
	if (tipo == "referencia" ) {
		var xcampo = campo;
	}

	if (tipo == "descricao" ) {
		var xcampo = campo2;
	}

	if (xcampo.value != "") {
		var url = "";
		url = "produto_pesquisa_2.php?campo=" + xcampo.value + "&tipo=" + tipo + "&limpa=TRUE&linha="+linha+"&exibe=<? echo $_SERVER['REQUEST_URI']; ?>";
		janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=500, height=400, top=18, left=0");
		janela.referencia	= campo;
		janela.descricao	= campo2;
		janela.voltagem		= campo3;
		janela.focus();
	}
}

function limpa_troca(linha){
	$('input[name^="produto_referencia_troca_'+linha+'"]').val('');
	$('input[name^="produto_descricao_troca_'+linha+'"]').val('');
	$('input[name^="produto_troca_'+linha+'"]').val('');
	$('input[name^="produto_os_troca_'+linha+'"]').val('');
	$('input[name^="produto_voltagem_troca_'+linha+'"]').val('');
	$('input[name^="produto_observacao_troca_'+linha+'"]').val('');
}

function fnc_pesquisa_produto_serie (campo,campo2,campo3,linha=null) {
	if (campo3.value != "") {
		var url = "";
		url = "produto_serie_pesquisa2.php?campo=" + campo3.value ;
		janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=600, height=400, top=18, left=0");
		janela.referencia	= campo;
		janela.descricao	= campo2;
		janela.serie	    = campo3;
		janela.focus();
		limpa_troca(linha);
	}
}

	//HD 244476: Acrescentei um parâmetro para alimentar o ID do hidden produto_troca_$i
	function fnc_pesquisa_produto_troca (referencia, descricao, voltagem, referencia_produto, voltagem_produto, tipo, id_produto_troca) {
		var url = "";

		url = "pesquisa_produto_troca.php?referencia=" + referencia.value + "&descricao=" + descricao.value + "&voltagem=" + voltagem.value + "&referencia_produto=" + referencia_produto.value + "&voltagem_produto=" + voltagem_produto.value + "&tipo=" + tipo;
		if (referencia_produto.value.length > 0) {
			janela = window.open(url, "janela", "toolbar=no, location=yes, status=yes, scrollbars=yes, directories=no, width=501, height=400, top=18, left=0");
			//HD 244476
			janela.produto      = id_produto_troca;
			janela.descricao    = descricao;
			janela.referencia   = referencia;
			janela.voltagem     = voltagem;
		}else{
			alert("Antes de escolher o produto para troca, informe o produto a ser trocado.");
		}
	}

function char(nota_fiscal){
	try{var element = nota_fiscal.which	}catch(er){};
	try{var element = event.keyCode	}catch(er){};
	if (String.fromCharCode(element).search(/[0-9]/gi) == -1)
	return false
}
window.onload = function(){
	document.getElementById('nota_fiscal').onkeypress = char;
}

<? if($login_fabrica == 1) { ?>
function alerta_revenda_bed() {
	var cons_rev = $("input[name='consumidor_revenda']:checked").val();
	var nome_rev = $("#revenda_nome").val().toLowerCase();
	var black = nome_rev.indexOf("black");
	var decker = nome_rev.indexOf("decker");
	var becker = nome_rev.indexOf("becker");

	if((black >= 0 && decker >= 0) || (black >= 0 && becker >= 0)){
		document.getElementById('alerta').value = '1';
		janela=window.open("os_info_black2.php", "janela", "toolbar=no, location=no, status=no, scrollbars=no, directories=no, width=501, height=400, top=18, left=0");
		janela.focus();
	}
}
<? } ?>
</script>


<style type="text/css">
	@import "plugins/jquery/datepick/telecontrol.datepick.css";
.mobile:hover {
  background: #5b5c8d;
}
.mobile:active{
  background: #373865;
}
.mobile{
  display: inline-flex;
  height: 45px;
  width: 190px;
  background: #373865;
  padding: 5px;
  border-radius: 10px;
  cursor: pointer;
}
.google_play{
  margin-left: 10%;
  display: inline-flex;
  height: 45px;
  padding: 5px;
  cursor: pointer;

}
.google_play > a >span{
  color: #373865;
}
.google_play:hover{
  background: #f3f3f3;
}
.mobile > span{
  font-size: 14px;
  float: right;
  margin-top: 14px;
  margin-right: 14px;
  color: #fac814;
}

.env-code{
  width: 100%;
  border: solid 3px;
  border-color: #373866;
  width: 205px;
  border-radius: 7px;
  margin-top: 10px;
}

.env-img {
 /*   float: left;*/
    max-width: 150px;
    margin-left: 10px;
    margin-top: 10px;
    display: inline-block;
}

.content {
    background:#CDDBF1;
    width: 600px;
    text-align: center;
    padding: 5px 30px; /* padding greater than corner height|width */
    margin: 1em 0.25em;
    color:#000000;
    text-align:center;
}
.content h1 {
    color:black;
    font-size: 120%;
}
.menu_top {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 9px;
	font-weight: bold;
	border: 1px solid;
	color:#ffffff;
	background-color: #596D9B
}
.menu_top2 {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 9px;
	font-weight: bold;
	border: 1px solid;
	color:#000000;
}
.table {
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: x-small;
	text-align: center;
	border: 1px solid #d9e2ef;
}

.table_line {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	border: 0px solid;
}

.table_line2 {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	background-color: #CED7e7;
}

</style>

<!-- ============= <HTML> COMEÇA FORMATAÇÃO ===================== -->

<?
if (strlen ($msg_erro) > 0) {
?>
<table border="0" cellpadding="0" cellspacing="0" width='700' align="center" bgcolor="#ffffff">
<tr>
	<td height="27" valign="middle" width='100%' align="center"
         style="padding:4px 8px;background:red;color: white;font-weight:bold">
<?
	if ( strpos($msg_erro,"É necessário informar o type para o produto") !== false OR strpos($msg_erro,"Type informado para o produto não é válido") !== false  ) {
		$sqlT =	"SELECT tbl_lista_basica.type, tbl_produto.referencia
				FROM tbl_produto
				JOIN tbl_lista_basica USING (produto)
				WHERE tbl_produto.produto = $produto
				AND   tbl_lista_basica.fabrica = $login_fabrica
				AND   tbl_produto.ativo IS TRUE
				GROUP BY tbl_lista_basica.type, tbl_produto.referencia
				ORDER BY tbl_lista_basica.type;";
		$resT = pg_exec ($con,$sqlT);
		if (pg_numrows($resT) > 0) {
			$s = pg_numrows($resT) - 1;
			for ($t = 0 ; $t < pg_numrows($resT) ; $t++) {
				$typeT = pg_result($resT,$t,type);
				$result_type = $result_type.$typeT;

				if ($t == $s) $result_type = $result_type.".";
				else          $result_type = $result_type.",";
			}
			if (strpos($msg_erro,"É necessário informar o type para o produto") !== false) $msg_erro = "É necessário informar o type para o produto ".pg_result($resT,0,referencia).".<br>";
			if (strpos($msg_erro,"Type informado para o produto não é válido") !== false) $msg_erro = "Type informado para o produto ".pg_result($resT,0,referencia)." não é válido.<br>";
			$msg_erro .= "Selecione o Type: $result_type";
		}
	}

	// retira palavra ERROR:
	if (strpos($msg_erro,"ERROR: ") ) {
		$erro = "Foi detectado o seguinte erro:<br>";
		$msg_erro = substr($msg_erro, 6);
	}

	// retira CONTEXT:
	if (strpos($msg_erro,"CONTEXT:")) {
		$x = explode('CONTEXT:',$msg_erro);
		$msg_erro = $x[0];
	}
	echo $erro . $msg_erro;
?>
	</td>
</tr>
</table>
<?
}

?>


<br>

<table width="650" border="0" cellpadding="0" cellspacing="0" align="center" bgcolor="#ffffff" class="table">
	<tr class="menu_top">
		<td nowrap><font size="1" face="Geneva, Arial, Helvetica, san-serif">ATENÇÃO: <br><br> AS ORDENS DE SERVIÇO DIGITADAS NESTE MÓDULO SÓ SERÃO VÁLIDAS APÓS O CLIQUE EM GRAVAR E DEPOIS EM EXPLODIR.</font></td>
	</tr>
</table>

<br>

<table width="650" border="0" cellpadding="0" cellspacing="0" align="center" bgcolor="#ffffff" class="table">
<caption><input type='button' name='voltar' value='Clique aqui para voltar na tela da OS de Troca de Consumidor' onclick="window.location='os_cadastro_troca.php'">
</table>

<br>
<?php if ($login_fabrica == 1) { ?>
<input type="hidden" name="alerta" id="alerta" value="0">
<?php } ?>
<table width="650" border="0" cellpadding="0" cellspacing="0" align="center" bgcolor="#ffffff" class="table">
	<tr >
		<td><img height="1" width="20" src="imagens/spacer.gif"></td>
		<td valign="top" align="left">

			<!--Formulário -->
			<form name="frm_os" method="post" action="<? echo $PHP_SELF ?>" enctype="multipart/form-data">
			<table width="100%" border="0" cellspacing="3" cellpadding="2">
			<input type='hidden' name='os_revenda' value='<? echo $os_revenda; ?>'>
			<input name="sua_os" type="hidden" value="<? echo $sua_os ?>">
				<tr class="menu_top">
					<td nowrap>Data Abertura</td>
					<td nowrap>Nota Fiscal</td>
					<td nowrap>Data Nota</td>
					<td   valign='top'>Tipo de Atendimento</td>
				</tr>
				<tr>
					<td nowrap align='center'>
						<input name="data_abertura" id='data_abertura' size="12" maxlength="10" value="<? echo $data_abertura; ?>" type="text" class="frm" tabindex="0"> <font face='arial' size='1'> Ex.: <? echo date("d/m/Y"); ?></font>
					</td>
					<td nowrap align='center'>
						<input name="nota_fiscal" size="6" maxlength="14" id="nota_fiscal" value="<? echo $nota_fiscal ?>" type="text" class="frm" tabindex="0" >
					</td>
					<td nowrap align='center'>
						<input name="data_nf" size="12" id='data_nf' maxlength="10"value="<? echo $data_nf ?>" type="text" class="frm" tabindex="0" > <font face='arial' size='1'> Ex.: 03/06/2005</font>
					</td>
					<td>
						<select class='frm' name="tipo_atendimento" size="1" style='width:200px; height=18px;'>
							<option selected></option>
							<?
							// hd 15197

							if($login_fabrica==1) $sql_add1 = "AND   tipo_atendimento IN (17,204)";

							$sql = "SELECT *
									FROM tbl_tipo_atendimento
									WHERE fabrica = $login_fabrica
									$sql_add1
									ORDER BY tipo_atendimento";

							$res = pg_exec ($con,$sql) ;
							for ($i = 0 ; $i < pg_numrows ($res) ; $i++ ) {

								echo "<option ";
								if ($tipo_atendimento == pg_result ($res,$i,tipo_atendimento) ) echo " selected ";
								echo " value='" . pg_result ($res,$i,tipo_atendimento) . "'>" ;
								echo pg_result ($res,$i,descricao) ;
								echo "</option>";
							}
							?>
						</select>
					</td>
				</tr>
				<tr>
					<td colspan='4' class="table_line2" height='20'></td>
				</tr>
			</table>

			<table width="100%" border="0" cellspacing="3" cellpadding="2">
				<tr class="menu_top">
					<td>Nome Revenda</td>
					<td>CNPJ Revenda</td>
					<td>Fone Revenda</td>
					<td>e-Mail Revenda</td>
				</tr>
				<tr>
					<td align='center'>
						<input class="frm" type="text" name="revenda_nome" id="revenda_nome" size="28" maxlength="50" value="<? echo $revenda_nome ?>" onkeyup="somenteMaiusculaSemAcento(this)">&nbsp;<img src='imagens/btn_buscar5.gif' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_revenda (document.frm_os.revenda_nome, "nome")' style='cursor:pointer;'>

						<input type="hidden" name="alerta" id="alerta" value="0">
					</td>
					<td align='center'>
						<input class="frm" type="text" name="revenda_cnpj" id="revenda_cnpj" size="20" maxlength="14" value="<? echo $revenda_cnpj ?>">&nbsp;<img src='imagens/btn_buscar5.gif' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_revenda (document.frm_os.revenda_cnpj, "cnpj")' style='cursor:pointer;'>
					</td>
					<td align='center'>
						<input class="frm" type="text" name="revenda_fone" id="revenda_fone" rel='fone' size="11"  maxlength="20"  value="<? echo $revenda_fone ?>" >
					</td>
					<td align='center'>
						<input class="frm" type="text" name="revenda_email" id="revenda_email" size="11" maxlength="50" value="<? echo $revenda_email ?>" tabindex="0">
					</td>
				</tr>
			</table>

<input type="hidden" name="revenda_cidade"      id="revenda_cidade"         value="" />
<input type="hidden" name="revenda_estado"      id="revenda_estado"         value="" />
<input type="hidden" name="revenda_endereco"    id="revenda_endereco"       value="" />
<input type="hidden" name="revenda_cep"         id="revenda_cep"            value="" />
<input type="hidden" name="revenda_numero"      id="revenda_numero"         value="" />
<input type="hidden" name="revenda_complemento" id="revenda_complemento"    value="" />
<input type="hidden" name="revenda_bairro"      id="revenda_bairro"         value="" />
<?
if ($login_fabrica == 1)
{
	echo "
			<table width=\"100%\" border=\"0\" cellspacing=\"3\" cellpadding=\"2\">
				<tr class=\"menu_top\">
					<td>Motivo da Troca</td>
				</tr>
				<tr>
					<td align='center'>
						<input class=\"frm\" type=\"text\" name=\"motivo\" id=\"motivo\" size=\"68\" value=\"" .  $motivo . "\">
					</td>
				</tr>
			</table>
	";
}
?>
			<table width="100%" border="0" cellspacing="3" cellpadding="2">
				<tr class="menu_top">
					<td>Observações</td>
				</tr>
				<tr>
					<td align='center'>
						<input class="frm" type="text" name="obs" size="68" value="<? echo $obs ?>">
					</td>
				</tr>
			</table>

			<? $display = (!empty($qtde_item)) ? '' : 'none' ?>

			<table width="100%" border="0" cellspacing="5" cellpadding="0" style="display:<?=$display?>;" id="input_anexos">
				<?php if ($login_fabrica == 1) {?>
				<tr>
					<td align="center">
				    	<br>
						  <div id="env-qrcode" style="display:none;">
						    <div class='env-code'>
						      <img style="width: 200px;" src="">
						    </div>
						  </div>
			  			  <!-- <img id="btn-qrcode-request" src="imagens/btn_imageuploader.gif" onclick="getQrCode()" alt="Fazer Upload via Image Uploader" border="0" style="cursor: pointer;border: 1px solid #888;">-->
						  <div style="width:920px;text-align:center">
						    <span class="mobile" id="btn-qrcode-request" onclick="getQrCode()">
						    <img style="width: 45px; float: left" alt="Fazer Upload via Mobile" src="imagens/icone_mobile.png">
						    <span>Anexar via Mobile</span>
						    </span>
						    <span class="google_play" id="btn-google-play">
						      <a class="g_play" target="_BLANK" href="https://play.google.com/store/apps/details?id=br.com.telecontrol.imageuploader">
						        <img style="width: 45px; float: left" alt="Fazer Upload via Mobile" src="imagens/icone_google_play.png">
						        <span style="margin-top: 17px;float: left;font-size: 12px; color: #373865;">Baixar Aplicativo Image Uploader</span>
						      </a>
						    </span>
						  </div>
						  <div id="env-images"></div>
						<?php
						  #color: #373865
						  echo $include_imgZoom;
						?>
						<br>
					</td>
				</tr>
				<?php }?>
				<tr>
					<td height="27" valign="middle" align="center" bgcolor="#FFFFFF">
						<?php
							if ($anexaNotaFiscal) {
								if ($os_revenda)
									$temAnexos = temNF("r_$os_revenda", 'count');

								if ($temAnexos)
									echo temNF('r' . $os_revenda, 'link') . $include_imgZoom;

                                if (($anexa_duas_fotos and $temAnexos < LIMITE_ANEXOS) or $temAnexos == 0) {
                                    if ($login_fabrica == '1') {
                                        $inputNotaFiscalTpl = str_replace('foto_nf', 'foto_nf[@ID@]', $inputNotaFiscal);
                                        echo str_replace('@ID@', '0', $inputNotaFiscalTpl);

                                        $anexoTpl = '
                                                <tr id="anexoTpl" style="display: none">
                                                    <td height="27" valign="middle" align="center" bgcolor="#FFFFFF">
                                                      ' . $inputNotaFiscalTpl . '
                                                    </td>
                                                </tr>
                                            ';

                                        echo '<input type="hidden" id="qtde_anexos" name="qtde_anexos" value="1" />';
                                    } else {
                                        echo  $inputNotaFiscal;
                                    }
                                }
							}
						?>
					</td>
				</tr>
			</table>
                <?php
                if ($login_fabrica == '1' and !empty($qtde_item)) {
                    echo '<div align="center"><input value="Adicionar novo arquivo" onclick="addAnexoUpload()" type="button"></div>';
                    echo $anexoTpl;
                }
                ?>

		</td>
		<td><img height="1" width="16" src="imagens/spacer.gif"></td>
	</tr>
</table>

<?
if (strlen($os_revenda) > 0) {
	$sql = "SELECT      tbl_produto.produto
			FROM        tbl_os_revenda_item
			JOIN        tbl_produto   USING (produto)
			JOIN        tbl_os_revenda USING (os_revenda)
			WHERE       tbl_os_revenda_item.os_revenda = $os_revenda
			ORDER BY    tbl_os_revenda_item.os_revenda_item";
	$res_os = pg_exec ($con,$sql);
}

// monta o FOR
echo "<input class='frm' type='hidden' name='qtde_item' value='$qtde_item'>";
echo "<input type='hidden' name='btn_acao' value=''>";

//HD 9013 21517
if(($qtde_item==0 or strlen($qtde_item)==0) and strlen($os_revenda)==0){
	echo "<br>";
	echo "<table width='650' border='0' cellpadding='0' cellspacing='0' align='center' bgcolor='#ffffff' class='table'>";
	echo "<caption class='menu_top'>ATENÇÃO:</caption>";
	echo "<td><P align='justify'><FONT COLOR='#000009'><font size='2' face='Geneva, Arial, Helvetica, san-serif'><br>
		A digitação de NF de Revenda tem que ser na totalidade, ou seja, todos os produtos referentes a mesma nota fiscal devem ser digitados na mesma OS. Favor informar a quantidade de produtos existentes para que o programa insira a quantidade correta.<br><br>
	NOTA: Não será possível digitar novamente a mesma Nota Fiscal!
	</font></td></P>";
	echo "</tr>";
	echo "<tr><td>&nbsp;</td><tr>";
	echo "<tr>";
	echo "<td><font size='1' face='Geneva, Arial, Helvetica, san-serif'><b>QUANTIDADE DE PRODUTOS B&D/DW</b></font>&nbsp;&nbsp; <input type=text size=10 maxlength=10 name='qtde_linhas' >&nbsp;<input type='button' name='Listar' value='Listar quantidade digitada' onclick=\"javascript: document.frm_os.submit(); \">";
	echo "</td></tr>";
	echo "</table>";
}else{

	for ($i = 0 ; $i < $qtde_item ; $i++) {

		$novo                         = 't';
		$os_revenda_item              = "";
		$referencia_produto           = "";
		$serie                        = "";
		$produto_descricao            = "";
		$capacidade                   = "";
		$type                         = "";
		$embalagem_original           = "";
		$sinal_de_uso                 = "";
		$codigo_fabricacao            = "";
		$produto_voltagem             = "";
		$defeito_constatado_descricao = "";
		$referencia_produto_troca     = "";
		$produto_descricao_troca      = "";
		$produto_voltagem_troca       = "";
		//HD 244476
		$produto_troca				  = "";
		$kit          				  = "";

		if($i%2==0)$bgcor = '#C0C0C0';
		else       $bgcor = '#F1F4FA';

		if ($i % 20 == 0) {


			echo "<table width='98%' border='0' cellpadding='0' cellspacing='2' align='center' bgcolor='#ffffff'>";
			echo "<tr class='menu_top'>";
			echo "<td align='center'>Cod. Fabricação</td>\n";
			echo "<td align='center'>Número de série</td>";
			echo "<td align='center'>Produto</td>";
			echo "<td align='center'>Descrição do produto</td>";
			echo "<td align='center'>Voltagem </td>";
			echo "<td align='center'>Type</td>\n";
			echo "<td align='center'>Embalagem Original</td>\n";
			echo "</tr>";
		}

		if (strlen($os_revenda) > 0 && strlen($msg_erro) == 0){
			if (@pg_numrows($res_os) > 0) {
				$produto = trim(@pg_result($res_os,$i,produto));
			}

			if(strlen($produto) > 0){
				// seleciona do banco de dados
				$sql = "SELECT   tbl_os_revenda_item.os_revenda_item    ,
								 tbl_os_revenda_item.serie              ,
								 tbl_os_revenda_item.capacidade         ,
								 tbl_os_revenda_item.codigo_fabricacao  ,
								 tbl_os_revenda_item.type               ,
								 tbl_os_revenda_item.embalagem_original ,
								 tbl_os_revenda_item.sinal_de_uso       ,
								 /* HD 244476 */
                                 tbl_os_revenda_item.produto_troca      ,
								 tbl_os_revenda_item.tipo_atendimento   ,
								 tbl_os_revenda_item.kit          		,
								 tbl_produto.referencia                 ,
								 tbl_produto.descricao                  ,
								 tbl_produto.voltagem                   ,
								 tbl_os_revenda_item.defeito_constatado_descricao,
								 produto_troca.referencia as referencia_troca,
								 produto_troca.descricao as descricao_troca,
								 produto_troca.voltagem as  voltagem_troca
						FROM	 tbl_os_revenda
						JOIN	 tbl_os_revenda_item ON tbl_os_revenda.os_revenda = tbl_os_revenda_item.os_revenda
						JOIN	 tbl_produto ON tbl_produto.produto = tbl_os_revenda_item.produto
						LEFT JOIN	 tbl_produto produto_troca ON tbl_os_revenda_item.produto_troca = produto_troca.produto
						WHERE	 tbl_os_revenda_item.os_revenda = $os_revenda";
//echo $sql;
				$res = pg_exec($con,$sql);

				if (@pg_numrows($res) == 0) {
					$novo                        = 't';
					$os_revenda_item             = $_POST["item_".$i];
					$referencia_produto          = $_POST["produto_referencia_".$i];
					$serie                       = $_POST["produto_serie_".$i];
					$produto_descricao           = $_POST["produto_descricao_".$i];
					$capacidade                  = $_POST["produto_capacidade_".$i];
					$type                        = $_POST["type_".$i];
					$embalagem_original          = $_POST["embalagem_original_".$i];
					$sinal_de_uso                = $_POST["sinal_de_uso_".$i];
					$codigo_fabricacao           = $_POST["codigo_fabricacao_".$i];
					$produto_voltagem            = $_POST["produto_voltagem_".$i];
					$defeito_constatado_descricao= $_POST["defeito_constatado_descricao_".$i];
					$referencia_produto_troca    = $_POST["produto_referencia_troca_".$i];
					$produto_descricao_troca     = $_POST["produto_descricao_troca_".$i];
                    $produto_voltagem_troca      = $_POST["produto_voltagem_troca_".$i];
					$tipo_atendimento_item       = $_POST["tipo_atendimento_item_".$i];
					//HD 244476
					$produto_troca               = $_POST["produto_troca_".$i];
					if ($referencia_produto_troca == "KIT") {
						$kit                         = $_POST["produto_troca_".$i];
					}
				}else{
					$novo                        = 'f';
					$os_revenda_item             = pg_result($res,$i,os_revenda_item);
					$referencia_produto          = pg_result($res,$i,referencia);
					$produto_descricao           = pg_result($res,$i,descricao);
					$serie                       = pg_result($res,$i,serie);
					$capacidade                  = pg_result($res,$i,capacidade);
					$type                        = pg_result($res,$i,type);
					$embalagem_original          = pg_result($res,$i,embalagem_original);
					$sinal_de_uso                = pg_result($res,$i,sinal_de_uso);
					$codigo_fabricacao           = pg_result($res,$i,codigo_fabricacao);
					$produto_voltagem            = pg_result($res,$i,voltagem);
					$defeito_constatado_descricao= pg_result($res,$i,defeito_constatado_descricao);
					$referencia_produto_troca    = pg_result($res,$i,referencia_troca);
					$produto_descricao_troca     = pg_result($res,$i,descricao_troca);
                    $produto_voltagem_troca      = pg_result($res,$i,voltagem_troca);
					$tipo_atendimento_item       = pg_result($res,$i,tipo_atendimento);
					//HD 244476
					$produto_troca               = pg_result($res,$i,produto_troca);
					$kit                         = pg_result($res,$i,kit);

					if (strlen($kit) > 0) {
						$referencia_produto_troca = "KIT";
						$produto_descricao_troca = "KIT $kit";
						$produto_voltagem_troca = "KIT $kit";
						$produto_troca = $kit;
					}
				}
			}else{
				$novo               = 't';
			}
		}else{
			$novo                        = 't';
			$os_revenda_item             = $_POST["item_".$i];
			$referencia_produto          = $_POST["produto_referencia_".$i];
			$serie                       = $_POST["produto_serie_".$i];
			$produto_descricao           = $_POST["produto_descricao_".$i];
			$capacidade                  = $_POST["produto_capacidade_".$i];
			$type                        = $_POST["type_".$i];
			$embalagem_original          = $_POST["embalagem_original_".$i];
			$sinal_de_uso                = $_POST["sinal_de_uso_".$i];
			$codigo_fabricacao           = $_POST["codigo_fabricacao_".$i];
			$produto_voltagem            = $_POST["produto_voltagem_".$i];
			$defeito_constatado_descricao= $_POST["defeito_constatado_descricao_".$i];
			$referencia_produto_troca    = $_POST["produto_referencia_troca_".$i];
			$produto_descricao_troca     = $_POST["produto_descricao_troca_".$i];
            $produto_voltagem_troca      = $_POST["produto_voltagem_troca_".$i];
			$tipo_atendimento_item       = $_POST["tipo_atendimento_item_".$i];
			//HD 244476
			$produto_troca               = $_POST["produto_troca_".$i];
			if ($referencia_produto_troca == "KIT") {
				$kit                         = $_POST["produto_troca_".$i];
			}
		}

		echo "<input type='hidden' name='novo_$i' value='$novo'>\n";
		echo "<input type='hidden' name='item_$i' value='$os_revenda_item'>\n";

		echo "<tr ";
		if ($linha_erro == $i AND strlen ($msg_erro) > 0) echo "bgcolor='#ffcccc'";
		else echo "bgcolor='$bgcor'";
		echo ">\n";
		echo "<td align='center'><input class='frm' type='text' name='codigo_fabricacao_$i' size='9' maxlength='20' value='$codigo_fabricacao'></td>\n";
		echo "<td align='center'><input class='frm' type='text' name='produto_serie_$i'  size='10'  maxlength='20'  value='$serie'>&nbsp;<img src='imagens/btn_buscar5.gif' border='0' align='absmiddle' onclick=\"javascript: fnc_pesquisa_produto_serie (document.frm_os.produto_referencia_$i,document.frm_os.produto_descricao_$i,document.frm_os.produto_serie_$i,$i)\" style='cursor:pointer;'></td>\n";
		echo "<td align='center'><input class='frm' type='text' name='produto_referencia_$i' size='15' maxlength='50' value='$referencia_produto'>&nbsp;<img src='imagens/btn_buscar5.gif' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_produto (document.frm_os.produto_referencia_$i,document.frm_os.produto_descricao_$i,document.frm_os.produto_voltagem_$i,\"referencia\",$i)' style='cursor:pointer;'></td>\n";
		echo "<td align='center'><input class='frm' type='text' name='produto_descricao_$i' size='30' maxlength='50' value='$produto_descricao'>&nbsp;<img src='imagens/btn_buscar5.gif' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_produto (document.frm_os.produto_referencia_$i,document.frm_os.produto_descricao_$i,document.frm_os.produto_voltagem_$i,\"descricao\",$i)' style='cursor:pointer;'></td>\n";
		echo "<td align='center'><input class='frm' type='text' name='produto_voltagem_$i' size='5' value='$produto_voltagem'></td>\n";

		?>
		<td align='center' nowrap>
		&nbsp;
		    <?
		     GeraComboType::makeComboType($parametrosAdicionaisObject, $type, null, array("class"=>"frm","index"=>$i));
      		     echo GeraComboType::getElement();
		    ?>

		&nbsp;
		</td>
		<td align='center' nowrap>
			&nbsp;
			<input class='frm' type="radio" name="embalagem_original_<? echo $i ?>" value="t" <? if ($embalagem_original == 't'/* OR strlen($embalagem_original) == 0*/) echo "checked"; ?>>
			<font size='1' face='Verdana, Tahoma, Geneva, Arial, Helvetica, san-serif'><b>Sim</b></font>
			<input class='frm' type="radio" name="embalagem_original_<? echo $i ?>" value="f" <? if ($embalagem_original == 'f') echo "checked"; ?>>
			<font size='1' face='Verdana, Tahoma, Geneva, Arial, Helvetica, san-serif'><b>Não</b></font>
			&nbsp;
		</td>
		<?

		echo "</tr>\n";
echo "<tr class='menu_top2'";
		if ($linha_erro == $i AND strlen ($msg_erro) > 0) echo "bgcolor='#ffcccc'";
		echo " >";
		echo "<td align='center'>Sinal de Uso</td>\n";
		echo "<td align='center'>Defeito constatado</td>\n";
		echo "<td align='center'>Trocar Por</td>\n";
		echo "<td align='center'>Produto</td>\n";
		echo "<td align='center'>Voltagem</td>\n";
		echo "<td align='center'>Tipo Atendimento</td>";
		echo "</tr>";
		echo "<tr ";
		if ($linha_erro == $i AND strlen ($msg_erro) > 0) echo "bgcolor='#ffcccc'";
		else echo " bgcolor = '$bgcor' ";
		echo ">\n";
		echo "<td align='center' nowrap>";
		echo "<input class='frm' type='radio' name='sinal_de_uso_$i' value='t'";
		if ($sinal_de_uso == 't') echo " checked ";
		echo " ><font size='1'><b>Sim</b></font>";
		echo "<input class='frm' type='radio' name='sinal_de_uso_$i' value='f'";
		if ($sinal_de_uso == 'f') echo " checked ";
		echo " ><font size='1'><b>Não</b></font></td>";
		echo "<td align='center' nowrap>";
		echo "<input class='frm' type='text' name='defeito_constatado_descricao_$i' size ='20' maxlength='150' value='$defeito_constatado_descricao'></td>";
		//HD 244476: Este hidden não estava sendo usado para nada, alterei para que grave o ID do produto selecionado ou o número do KIT se for o caso
		//HD 244476: A chamada da função de pesquisa foi alterada para levar o novo parâmetro
		echo "<td align='center' nowrap><input class='frm' type='hidden' name='produto_troca_$i' value='$produto_troca'><input class='frm' type='text' name='produto_referencia_troca_$i' size='15' maxlength='50' value='$referencia_produto_troca'>&nbsp;<img src='imagens/btn_buscar5.gif' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_produto_troca (document.frm_os.produto_referencia_troca_$i,document.frm_os.produto_descricao_troca_$i,document.frm_os.produto_voltagem_troca_$i,document.frm_os.produto_referencia_$i,document.frm_os.produto_voltagem_$i,\"referencia\", document.frm_os.produto_troca_$i)' style='cursor:pointer;'></td>\n";
		//HD 244476: A chamada da função de pesquisa foi alterada para levar o novo parâmetro
		echo "<td align='center' nowrap><input class='frm' type='text' name='produto_descricao_troca_$i' size='30' maxlength='50' value='$produto_descricao_troca'>&nbsp;<img src='imagens/btn_buscar5.gif' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_produto_troca (document.frm_os.produto_referencia_troca_$i,document.frm_os.produto_descricao_troca_$i,document.frm_os.produto_voltagem_troca_$i,document.frm_os.produto_referencia_$i,document.frm_os.produto_voltagem_$i,\"descricao\", document.frm_os.produto_troca_$i)' style='cursor:pointer;'></td>\n";
		echo "<td align='center' nowrap><input class='frm' type='text' name='produto_voltagem_troca_$i' size='5' value='$produto_voltagem_troca'>";
		echo "<td align='center'>";
		if($tipo_atendimento == 204){
            echo "<input class='frm' type='radio' name='tipo_atendimento_item_$i' value='17'";
            if ($tipo_atendimento_item == '17') echo " checked ";
            echo " ><font size='1'><b>GAR</b></font>";
            echo "<input class='frm' type='radio' name='tipo_atendimento_item_$i' value='18'";
            if ($tipo_atendimento_item == '18') echo " checked ";
            echo " ><font size='1'><b>FAT</b></font></td>";
		}else{
            echo "<input type='hidden' id='tipo_atendimento_item_$i' name='tipo_atendimento_item_$i' value='$tipo_atendimento' />";
            if($tipo_atendimento == 17){
                echo "Troca Garantia";
            }else if($tipo_atendimento == 18){
                echo "Troca Faturada";
            }
		}
        echo "</td>";
		echo "</tr>";
		echo "<tr><td colspan='100%'><h1></h1><br></td></tr>";
		// limpa as variaveis
		$novo               = '';
		$os_revenda_item    = '';
		$referencia_produto = '';
		$serie              = '';
		$produto_descricao  = '';
		$capacidade         = '';

	}
}
echo "<tr>";
echo "<td colspan='7' align='center'>";
echo "<br>";
//echo "<input type='hidden' name='btn_acao' value=''>";
if($qtde_item != 0 ){
	echo "<img src='imagens/btn_gravar.gif' onclick=\"javascript: if (document.frm_os.btn_acao.value == '' ) { document.frm_os.btn_acao.value='gravar' ; document.frm_os.submit() } else { alert ('Aguarde submissão') }\" ALT='Gravar' border='0' style='cursor:pointer;'>";
}

if (strlen($os_revenda) > 0 AND strlen($exclui) > 0) {
	echo "&nbsp;&nbsp;<img src='imagens/btn_apagar.gif' style='cursor:pointer' onclick=\"javascript: if (document.frm_os.btn_acao.value == '' ) { if(confirm('Deseja realmente apagar esta OS?') == true) { document.frm_os.btn_acao.value='apagar'; document.frm_os.submit(); }else{ return; }; } else { alert ('Aguarde submissão') }\" ALT='Apagar a Ordem de Serviço' border='0'>";
}

echo "</td>";
echo "</tr>";
echo "</table>";

  if ($_POST['objectid'] == "") {
      $objectId = $login_fabrica.$login_posto.date('dmyhis').rand(1,10000);
  }else{
      $objectId = $_POST['objectid'];
  }

?>
  <input type="hidden" id="objectid"  name="objectid" value="<?php echo $objectId; ?>">

</form>

<br>

<? include "rodape.php";?>
