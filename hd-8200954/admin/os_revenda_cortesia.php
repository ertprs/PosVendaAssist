<?php

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="call_center";
include 'autentica_admin.php';
include 'funcoes.php';
include_once('../anexaNF_inc.php');
$msg_erro = "";
if($login_fabrica == 1){
    require "../classes/ParametrosAdicionaisFabrica.php";
    $parametrosAdicionaisObject = new ParametrosAdicionaisFabrica($login_fabrica);

    require "../classes/form/GeraComboType.php";
}
#$qtde_item = 20;
if (strlen($_POST['qtde_item']) > 0)   $qtde_item = $_POST['qtde_item'];
if (strlen($_POST['qtde_linhas']) > 0) $qtde_item = $_POST['qtde_linhas'];

$btn_acao = trim(strtolower($_POST['btn_acao']));

if (strlen($_GET['os_revenda']) > 0)  $os_revenda = trim($_GET['os_revenda']);
if (strlen($_POST['os_revenda']) > 0) $os_revenda = trim($_POST['os_revenda']);

if(strlen($os_revenda)>0){
	$sql = " SELECT os_geo from tbl_os_revenda where os_revenda=$os_revenda ";
	$res = pg_query($con,$sql);
	if(pg_fetch_result($res,0,0) =='t') {
		header("Location: os_cadastro_metais_sanitario_cortesia.php?os_metal=$os_revenda");
		exit;
	}

	$sql="SELECT count(*) as qtde_item from tbl_os_revenda_item where os_revenda=$os_revenda";
	$res=pg_query($con,$sql);
	$qtde_item=pg_fetch_result($res,0,qtde_item);
}

/* ====================  APAGAR  =================== */
if ($btn_acao == "apagar") {
	if(strlen($os_revenda) > 0){
		$sql = "DELETE FROM tbl_os_revenda
				WHERE  tbl_os_revenda.os_revenda = $os_revenda
				AND    tbl_os_revenda.fabrica    = $login_fabrica
				AND    tbl_os_revenda.posto      = $posto";
		$res = pg_query($con,$sql);

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
	$posto_codigo = trim($_POST['posto_codigo']);

	$posto_descricao = trim($_POST['posto_descricao']);

	$xdata_abertura = $_POST["data_abertura"];
	$xdata_nf = $_POST['data_nf'];

	if (strlen($posto_codigo) == 0) {
		$msg_erro .= " Digite o Código do Posto.";
	}else{
		$posto_codigo = str_replace("-","",$posto_codigo);
		$posto_codigo = str_replace(".","",$posto_codigo);
		$posto_codigo = str_replace("/","",$posto_codigo);
		$posto_codigo = substr($posto_codigo,0,14);
	}
	if (strlen ($_POST['nota_fiscal']) == 0) $xnota_fiscal = 'null';
	else        $xnota_fiscal = "'".trim($_POST['nota_fiscal'])."'";

	if(strlen($msg_erro)==0){
        list($di, $mi, $yi) = explode("/", $xdata_abertura);
        if(!checkdate($mi,$di,$yi))
            $msg_erro = "Data Inválida";
		else{
				$xdata_abertura = "$yi-$mi-$di";
		}
	}
	if(strlen($msg_erro)==0 and !empty($xdata_nf)){
        list($di, $mi, $yi) = explode("/", $xdata_nf);
        if(!checkdate($mi,$di,$yi))
            $msg_erro = "Data Inválida";
		else{
				$xdata_nf = "$yi-$mi-$di";
		}
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

	if (strlen($_POST['contrato']) > 0) {
		$xcontrato = "'". $_POST['contrato'] ."'";
	}else{
		$xcontrato = "'f'";
	}

	if(strlen($msg_erro)==0){
		$tipo_os_cortesia   = $_POST['tipo_os_cortesia'];
		if (strlen($tipo_os_cortesia) == 0) $msg_erro = " Selecione o Tipo da OS Cortesia.";
	}

	if(strlen($msg_erro)==0){
		if (($tipo_os_cortesia == 'Sem Nota Fiscal' OR $tipo_os_cortesia == 'Fora de Garantia' OR $tipo_os_cortesia == 'Promotor') AND (strlen($nota_fiscal)>0 OR (strlen($data_nf)>0 AND $data_nf<>"null"))) {
			$msg_erro = " Os dados da nota fiscal não devem ser informados para este tipo de Cortesia.";
		}
	}

	if ($tipo_os_cortesia == 'Garantia') {
		if (strlen($nota_fiscal) == 0) $msg_erro = " Digite a Nota Fiscal.";
		if ($data_nf == "null")        $msg_erro = " Digite a Data da Compra.";
	}

		if (strlen($posto_codigo) > 0) {
			$sql =	"SELECT tbl_posto.posto
					FROM tbl_posto
					JOIN tbl_posto_fabrica	ON tbl_posto.posto = tbl_posto_fabrica.posto
					AND tbl_posto_fabrica.fabrica = $login_fabrica
					WHERE tbl_posto_fabrica.codigo_posto = '$posto_codigo';";
			$res = pg_query($con,$sql);

			if (pg_num_rows($res) > 0) {
				$posto = pg_fetch_result($res,0,0);
			}else{
				$msg_erro = " Posto $posto_codigo não cadastrado.";
			}
		}

	if ($xrevenda_cnpj <> "null") {
		$sql =	"SELECT *
				FROM    tbl_revenda
				WHERE   cnpj = $xrevenda_cnpj";
		$res = pg_query($con,$sql);

		if (pg_num_rows($res) == 0){
			$msg_erro = "CNPJ da revenda não cadastrado";
		}else{
			$revenda		= trim(pg_fetch_result($res,0,revenda));
			$nome			= trim(pg_fetch_result($res,0,nome));
			$endereco		= trim(pg_fetch_result($res,0,endereco));
			$numero			= trim(pg_fetch_result($res,0,numero));
			$complemento	= trim(pg_fetch_result($res,0,complemento));
			$bairro			= trim(pg_fetch_result($res,0,bairro));
			$cep			= trim(pg_fetch_result($res,0,cep));
			$cidade			= trim(pg_fetch_result($res,0,cidade));
			$fone			= trim(pg_fetch_result($res,0,fone));
			$cnpj			= trim(pg_fetch_result($res,0,cnpj));

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

			$sql = "SELECT cliente
					FROM   tbl_cliente
					WHERE  cpf = $xrevenda_cnpj";
			$res = pg_query($con,$sql);

			if (pg_num_rows($res) == 0){
				// insere dados
				$sql = "INSERT INTO tbl_cliente (
							nome       ,
							endereco   ,
							numero     ,
							complemento,
							bairro     ,
							cep        ,
							cidade     ,
							fone       ,
							cpf
						)VALUES(
							$xnome       ,
							$xendereco   ,
							$xnumero     ,
							$xcomplemento,
							$xbairro     ,
							$xcep        ,
							$xcidade     ,
							$xfone       ,
							$xcnpj
						)";
				// pega valor de cliente

				$res     = pg_query($con,$sql);
				$msg_erro = pg_errormessage($con);

				if (strlen($msg_erro) == 0 and strlen($cliente) == 0) {
					$res     = pg_query($con,"SELECT CURRVAL ('seq_cliente')");
					$msg_erro = pg_errormessage($con);
					if (strlen($msg_erro) == 0) $cliente = pg_fetch_result($res,0,0);
				}

			}else{
				$cliente = pg_fetch_result($res,0,cliente);
			}
		}
	}else{
		$msg_erro = "CNPJ não informado";
	}
	$consumidor_email       = trim ($_POST['consumidor_email']) ;
	// HD 18051
	if(strlen($consumidor_email) ==0 ){
		$msg_erro ="Digite o email de contato. <br>";
	}else{
		$consumidor_email = trim($_POST['consumidor_email']);
	}


	if (strlen($_POST['revenda_fone']) > 0) {
		$xrevenda_fone = "'". $_POST['revenda_fone'] ."'";
	}else{
		$xrevenda_fone = "null";
	}
	if($xrevenda_fone == "null"){$msg_erro ="Insira o telefone da revenda.<BR>";}

	if (strlen($_POST['revenda_email']) > 0) {
		$xrevenda_email = "'". $_POST['revenda_email'] ."'";
	}else{
		$xrevenda_email = "null";
	}

	if (strlen($_POST['obs']) > 0) {
		$xobs = "'". $_POST['obs'] ."'";
	}else{
		$xobs = "null";
	}

	if (strlen ($msg_erro) == 0) {

		$res = pg_query($con,"BEGIN TRANSACTION");

		if (strlen ($os_revenda) == 0) {
			if(!empty($xdata_nf)){
				$xdata_nf = "'".$xdata_nf."'";
			}
			else{
				$xdata_nf = 'null';
			}

			#-------------- insere ------------
			$sql = "INSERT INTO tbl_os_revenda (
						fabrica           ,
						sua_os            ,
						data_abertura     ,
						data_nf           ,
						nota_fiscal       ,
						cliente           ,
						contrato          ,
						revenda           ,
						obs               ,
						digitacao         ,
						posto             ,
						cortesia          ,
						tipo_os_cortesia  ,
						consumidor_revenda,
						admin             ,
						consumidor_email
					) VALUES (
						$login_fabrica    ,
						$xsua_os          ,
						'$xdata_abertura'   ,
						$xdata_nf         ,
						$xnota_fiscal     ,
						$cliente          ,
						$xcontrato        ,
						$revenda          ,
						$xobs             ,
						current_timestamp ,
						$posto            ,
						't'               ,
						'$tipo_os_cortesia',
						'R'               ,
						$login_admin       ,
						'$consumidor_email'
					)";
		}else{
			$sql = "UPDATE tbl_os_revenda SET
						fabrica           = $login_fabrica                   ,
						sua_os            = $xsua_os                         ,
						data_abertura     = $xdata_abertura                  ,
						contrato          = $xcontrato                       ,
						data_nf           = $xdata_nf                        ,
						nota_fiscal       = $xnota_fiscal                    ,
						cliente           = $cliente                         ,
						revenda           = $revenda                         ,
						obs               = $xobs                            ,
						posto             = $posto                           ,
						tipo_os_cortesia  = '$tipo_os_cortesia'              ,
						admin             = $login_admin                     ,
						consumidor_email  = '$consumidor_email'
					WHERE os_revenda  = $os_revenda
					AND	 posto        = $posto
					AND	 fabrica      = $login_fabrica ";
		}

		$res = @pg_query($con,$sql);
		$msg_erro = pg_errormessage($con);

		if (strlen($msg_erro) == 0 and strlen($os_revenda) == 0) {
			$res        = pg_query($con,"SELECT CURRVAL ('seq_os_revenda')");
			$os_revenda = pg_fetch_result($res,0,0);
			$msg_erro   = pg_errormessage($con);

			if (strlen ($msg_erro) > 0) {
				$sql = "UPDATE tbl_cliente SET contrato = $xcontrato
						WHERE  tbl_cliente.cliente  = $revenda";
				$res = pg_query($con,$sql);
				$msg_erro = pg_errormessage($con);

			}

// 			if (strlen ($msg_erro) > 0) {
// 				break ;
// 			}
		}

		//HD 9013 21517 HD 23159 24/7/2008 64474
		if(strlen($os_revenda)>0){
			$sql="SELECT tbl_os_revenda.sua_os,
						 tbl_posto_fabrica.codigo_posto
							FROM tbl_os_revenda
							JOIN tbl_posto_fabrica on tbl_os_revenda.posto= tbl_posto_fabrica.posto and tbl_posto_fabrica.fabrica=$login_fabrica
							WHERE regexp_replace(nota_fiscal,'\\\\D','')::bigint = (
										SELECT regexp_replace(nota_fiscal,'\\\\D','')::bigint
										FROM   tbl_os_revenda
										WHERE  os_revenda = $os_revenda
										and posto      = $posto
										and fabrica    = $login_fabrica
										)
							and   revenda       = (
										SELECT revenda
										FROM   tbl_os_revenda
										WHERE  os_revenda = $os_revenda
										and posto      = $posto
										and fabrica    = $login_fabrica
										)
							AND os_revenda <> $os_revenda
							AND tbl_os_revenda.posto <> $posto
							AND tbl_os_revenda.nota_fiscal IS NOT NULL
							AND tbl_os_revenda.fabrica = $login_fabrica";
					$res=pg_query($con,$sql);
					if(pg_num_rows($res)>0){
						$sua_os       = pg_fetch_result($res,0,sua_os);
						$codigo_posto = pg_fetch_result($res,0,codigo_posto);
						$msg_erro="Nota fiscal já foi informada na OS $codigo_posto$sua_os. O sistema permite a digitação de apenas uma OS de revenda para cada nota fiscal, pois é possível incluir na mesma OS a quantidade total de produtos que serão atendidos em garantia.";
			}
		}

		if (strlen($msg_erro) == 0) {

			$sql = "DELETE FROM tbl_os_revenda_item WHERE  os_revenda = $os_revenda";
			$res = pg_query($con,$sql);
			$msg_erro = pg_errormessage($con);
			$conta_qtde=0;

			for ($i = 0 ; $i < $qtde_item ; $i++) {
				$referencia         = trim($_POST["produto_referencia_".$i]);
				$codigo_fabricacao  = trim($_POST["codigo_fabricacao_".$i]);
				$serie              = trim($_POST["produto_serie_".$i]);
				$voltagem           = trim($_POST["produto_voltagem_".$i]);
				$type               = trim($_POST["type_".$i]);

			if (strlen($type) == 0)
					$type = "null";
				else
					$type = "'". $type ."'";

				if (strlen($voltagem) == 0)
					$voltagem = "null";
				else
					$voltagem = "'". $voltagem ."'";


				if (strlen($msg_erro) == 0) {
					if (strlen ($referencia) > 0) {
						$referencia = strtoupper ($referencia);
						$referencia = str_replace ("-","",$referencia);
						$referencia = str_replace (".","",$referencia);
						$referencia = str_replace ("/","",$referencia);
						$referencia = str_replace (" ","",$referencia);
						$referencia = "'". $referencia ."'";

						$sql =	"SELECT tbl_produto.produto,
										tbl_produto.numero_serie_obrigatorio,
										tbl_linha.linha
								FROM    tbl_produto
								JOIN    tbl_linha USING (linha)
								WHERE   UPPER(tbl_produto.referencia_pesquisa) = UPPER($referencia)
								AND     UPPER(tbl_produto.voltagem) = UPPER($voltagem)
								AND     tbl_linha.fabrica = $login_fabrica;";
						$res = pg_query($con,$sql);
						if (pg_num_rows($res) == 0) {
							$msg_erro = " Produto $referencia não cadastrado. <BR>";
							$linha_erro = $i;
						}else{
							$produto                  = pg_fetch_result($res,0,produto);
							$numero_serie_obrigatorio = pg_fetch_result($res,0,numero_serie_obrigatorio);
							$linha                    = pg_fetch_result($res,0,linha);
						}

						if (strlen($serie) == 0) {
							if ($linha == 198) {
								$msg_erro = " Número de série do produto $referencia é obrigatório. <BR>";
								$linha_erro = $i;
							}else{
								$serie = 'null';
							}
						}else{
							if ($linha == 199 OR $linha == 200) {
								$msg_erro = " Número de série do produto $referencia não pode ser preenchido. <BR>";
								$linha_erro = $i;
							}else{
								$serie = "'". $serie ."'";
							}
						}

						if ($tipo_os_cortesia == "Garantia" and strlen($produto) > 0) {
							$sql = "SELECT garantia FROM tbl_produto WHERE tbl_produto.produto = $produto";
							$res = pg_query($con,$sql);

							if (pg_num_rows($res) == 0) {
								$msg_erro = " Produto $produto_referencia sem garantia";
								$linha_erro=$i;
							}

							if (strlen($msg_erro) == 0) {
								$garantia = trim(pg_fetch_result($res,0,garantia));

								$sql = "SELECT ($xdata_nf::date + (($garantia || ' months')::interval))::date;";
								$res = pg_query($con,$sql);
								$msg_erro = pg_errormessage($con);

								if (strlen($msg_erro) > 0) $msg_erro =  "Data da NF inválida.";

								if (strlen($msg_erro) == 0) {
									if (pg_num_rows($res) > 0) {
										$data_final_garantia = trim(pg_fetch_result($res,0,0));
									}

									if ($tipo_os_cortesia <> 'Fora da Garantia' AND $data_final_garantia < $cdata_abertura) {
										$msg_erro = " Produto $produto_referencia fora da Garantia, vencida em ". substr($data_final_garantia,8,2) ."/". substr($data_final_garantia,5,2) ."/". substr($data_final_garantia,0,4);
										$linha_erro=$i;
									}
								}
							}
						}

						if(strlen($produto)>0){
							$sql =	"SELECT distinct tbl_familia.familia
									FROM tbl_produto
									JOIN tbl_familia USING (familia)
									WHERE tbl_familia.fabrica = $login_fabrica
									AND   tbl_familia.familia = 347
									AND   tbl_produto.linha   = 198
									AND   tbl_produto.produto = $produto;";
							$res = pg_query($con,$sql);
							if (pg_num_rows($res) > 0) {
								$xtipo_os_compressor = "10";
							}else{
								$xtipo_os_compressor = 'null';
							}
						}

						if (strlen($codigo_fabricacao) == 0) {
							$msg_erro = "Digite o Código de fabricação.<BR>";
						}else{
							$codigo_fabricacao = "'". $codigo_fabricacao ."'";
						}


						if (strlen ($msg_erro) == 0) {
							$sql = "INSERT INTO tbl_os_revenda_item (
										os_revenda        ,
										produto           ,
										serie             ,
										codigo_fabricacao ,
										nota_fiscal       ,
										data_nf           ,
										type
									) VALUES (
										$os_revenda       ,
										$produto          ,
										$serie            ,
										$codigo_fabricacao,
										$xnota_fiscal     ,
										$xdata_nf         ,
										$type
									)";
							$res = pg_query($con,$sql);
							$msg_erro = pg_errormessage($con);

							if (strlen($msg_erro) == 0) {
								$res        = pg_query($con,"SELECT CURRVAL ('seq_os_revenda_item')");
								$os_revenda_item = pg_fetch_result($res,0,0);
								$msg_erro   = pg_errormessage($con);

								$conta_qtde++;
							}

							if (strlen ($msg_erro) == 0) {
								$sql = "SELECT fn_valida_os_item_revenda_black($os_revenda,$login_fabrica,$produto,$os_revenda_item)";
								$res = @pg_query($con,$sql);
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
				$msg_erro ="FOI INFORMADO NO CAMPO QUANTIDADE DE PRODUTOS B&D/DW NESSA NOTA FISCAL $qtde_item E DETECTAMOS A DIGITAÇÃO DE QUANTIDADE INFERIOR À INFORMADA. GENTILEZA VERIFICAR.";
			}

			if (strlen ($msg_erro) == 0) {
				$sql = "SELECT fn_valida_os_revenda($os_revenda,$posto,$login_fabrica)";
				$res = @pg_query($con,$sql);
				$msg_erro = pg_errormessage($con);
			}
		}
	}

	if ( strlen($msg_erro) == 0 && $login_fabrica == 1 && !empty($_FILES['foto_nf']['name'])) {

			foreach (range(0, 4) as $idx) {
				if ($_FILES["foto_nf"]['tmp_name'][$idx] != '') {
					$file = array(
						"name" => $_FILES["foto_nf"]["name"][$idx],
						"type" => $_FILES["foto_nf"]["type"][$idx],
						"tmp_name" => $_FILES["foto_nf"]["tmp_name"][$idx],
						"error" => $_FILES["foto_nf"]["error"][$idx],
						"size" => $_FILES["foto_nf"]["size"][$idx]
					);
					$anexou = anexaNF("r_".$os_revenda, $file);

					if ($anexou !== 0) $msg_erro .= (is_numeric($anexou)) ? $msgs_erro[$anexou] : $anexou; // '0' é que executou OK
				}
			}
	}

	if (strlen ($msg_erro) == 0) {
		$res = pg_query($con,"COMMIT TRANSACTION");
		header ("Location: os_revenda_finalizada.php?os_revenda=$os_revenda");
		exit;
	}else{
		if (strpos ($msg_erro,"tbl_os_revenda_unico") > 0) $msg_erro = " O Número da Ordem de Serviço do fabricante já está cadastrado.";
		if (strpos ($msg_erro,"null value in column \"data_abertura\" violates not-null constraint") > 0) $msg_erro = "Data da abertura deve ser informada.";

		$os_revenda = trim($_POST['os_revenda']);

		$res = pg_query($con,"ROLLBACK TRANSACTION");
	}
}

if ((strlen($msg_erro) == 0) AND (strlen($os_revenda) > 0)){
	// seleciona do banco de dados
	$sql = "SELECT  tbl_os_revenda.sua_os                                                ,
					tbl_os_revenda.obs                                                   ,
					tbl_os_revenda.contrato                                              ,
					to_char(tbl_os_revenda.data_abertura,'DD/MM/YYYY') AS data_abertura  ,
					to_char(tbl_os_revenda.data_nf      ,'DD/MM/YYYY') AS data_nf        ,
					tbl_os_revenda.nota_fiscal                                           ,
					tbl_revenda.nome  AS revenda_nome                                    ,
					tbl_revenda.cnpj  AS revenda_cnpj                                    ,
					tbl_revenda.fone  AS revenda_fone                                    ,
					tbl_revenda.email AS revenda_email                                   ,
					tbl_os_revenda.explodida                                             ,
					tbl_os_revenda.posto                                                 ,
					tbl_os_revenda.tipo_os_cortesia                                      ,
					tbl_os_revenda.consumidor_email
			FROM	tbl_os_revenda
			JOIN	tbl_revenda ON tbl_os_revenda.revenda = tbl_revenda.revenda
			WHERE	tbl_os_revenda.os_revenda = $os_revenda
			AND		tbl_os_revenda.fabrica    = $login_fabrica ";

	$res = pg_query($con, $sql);

	if (pg_num_rows($res) > 0){
		$sua_os          = pg_fetch_result($res,0,sua_os);
		$data_abertura   = pg_fetch_result($res,0,data_abertura);
		$data_nf         = pg_fetch_result($res,0,data_nf);
		$nota_fiscal     = pg_fetch_result($res,0,nota_fiscal);
		$revenda_nome    = pg_fetch_result($res,0,revenda_nome);
		$revenda_cnpj    = pg_fetch_result($res,0,revenda_cnpj);
		$revenda_fone    = pg_fetch_result($res,0,revenda_fone);
		$revenda_email   = pg_fetch_result($res,0,revenda_email);
		$obs             = pg_fetch_result($res,0,obs);
		$contrato        = pg_fetch_result($res,0,contrato);
		$explodida       = pg_fetch_result($res,0,explodida);
		$posto           = pg_fetch_result($res,0,posto);
		$tipo_os_cortesia= pg_fetch_result($res,0,tipo_os_cortesia);
		$consumidor_email = pg_fetch_result($res,0,consumidor_email);

		if (strlen($explodida) > 0){
			header("Location:os_revenda_parametros.php");
			exit;
		}
		if (strlen($posto) > 0){
			$sql="SELECT codigo_posto from tbl_posto_fabrica
					where posto=$posto
					and fabrica =$login_fabrica";
			$res=pg_query($con,$sql);
			$posto_codigo = pg_fetch_result($res,0,codigo_posto);
	}

		$sql = "SELECT *
				FROM   tbl_os
				WHERE  sua_os ILIKE '$sua_os-%'
				AND    fabrica = $login_fabrica";
		$resX = pg_query($con, $sql);

		if (pg_num_rows($resX) == 0) $exclui = 1;

		$sql = "SELECT  tbl_os_revenda_item.nota_fiscal                               ,
						to_char(tbl_os_revenda_item.data_nf, 'DD/MM/YYYY') AS data_nf
				FROM	tbl_os_revenda_item
				JOIN	tbl_os_revenda ON tbl_os_revenda.os_revenda = tbl_os_revenda_item.os_revenda
				WHERE	tbl_os_revenda.os_revenda = $os_revenda
				AND		tbl_os_revenda.fabrica    = $login_fabrica
				AND		tbl_os_revenda_item.nota_fiscal NOTNULL
				AND		tbl_os_revenda_item.data_nf     NOTNULL LIMIT 1";
		$res = pg_query($con, $sql);

		if (pg_num_rows($res) > 0){
			$nota_fiscal = pg_fetch_result($res,0,nota_fiscal);
			$data_nf     = pg_fetch_result($res,0,data_nf);
		}
	}else{
		header('Location: os_revenda_cortesia.php');
		exit;
	}
}

$title			= "CADASTRO DE OS DE REVENDA";
$layout_menu	= 'callcenter';

include "cabecalho.php";

include "javascript_pesquisas.php";
include "javascript_calendario.php";


?>


<? // HD 31122 ?>
<script type='text/javascript' language='javascript' src='js/jquery.alphanumeric.js'></script>

<script type="text/javascript" src="js/jquery.blockUI_2.39.js"></script>
<script type="text/javascript" src="js/plugin_verifica_servidor.js"></script>

<script language="JavaScript">
$(function(){
	$("#data_nf").datePicker({startDate:'01/01/2010'});
	$("#data_nf").maskedinput("99/99/9999");
});

$(document).ready(function(){
	$("input[rel*='fone']").maskedinput("(99) 9999-9999");
	$("input[name*='revenda_cnpj']").numeric({allow:"./-"});
	$("input[name*='nota_fiscal']").numeric({allow:"-"});
});

function fnc_pesquisa_revenda (campo, tipo) {
	var url = "";
	if (tipo == "nome") {
		url = "pesquisa_revenda.php?nome=" + campo.value + "&tipo=nome";
	}
	if (tipo == "cnpj") {
		url = "pesquisa_revenda.php?cnpj=" + campo.value + "&tipo=cnpj";
	}
	if(campo.value != ""){
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
	}
	else{
		alert("Informe toda ou parte da informação para realizar a pesquisa");
	}
}

/* ============= Função PESQUISA DE PRODUTOS ====================
Nome da Função : fnc_pesquisa_produto (codigo,descricao)
		Abre janela com resultado da pesquisa de Produtos pela
		referência (código) ou descrição (mesmo parcial).
=================================================================*/

function fnc_pesquisa_produto (campo, campo2, campo3,  tipo) {
	if (tipo == "referencia" ) {
		var xcampo = campo;
	}

	if (tipo == "descricao" ) {
		var xcampo = campo2;
	}

	if (xcampo.value != "") {
		var url = "";
		url = "produto_pesquisa_2.php?campo=" + xcampo.value + "&tipo=" + tipo ;
		janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=500, height=400, top=18, left=0");
		janela.referencia	= campo;
		janela.descricao	= campo2;
		janela.voltagem		= campo3;
		janela.focus();
	}
	else{
		alert("Informe toda ou parte da informação para realizar a pesquisa");
	}
}

function fnc_pesquisa_produto_serie (campo,campo2,campo3) {
	if (campo3.value != "") {
		var url = "";
		url = "produto_serie_pesquisa2.php?campo=" + campo3.value ;
		janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=600, height=400, top=18, left=0");
		janela.referencia	= campo;
		janela.descricao	= campo2;
		janela.serie	    = campo3;
		janela.focus();
	}
	else{
		alert("Informe toda ou parte da informação para realizar a pesquisa");
	}
}

function fnc_pesquisa_posto2 (campo, campo2, tipo) {

    if (tipo == "codigo" ) {
        var xcampo = campo;
    }

    if (tipo == "nome" ) {
        var xcampo = campo2;
    }

    if (xcampo.value != "") {
        var url = "";
        url="posto_pesquisa_2.php?campo=" + xcampo.value + "&tipo=" + tipo + "&proximo=t" +"&os=t";
        janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes,directories=no, width=600, height=400, top=18, left=0");
        janela.codigo  = campo;
        janela.nome    = campo2;

        if ("<? echo $pedir_sua_os; ?>" == "t") {
            janela.proximo = document.frm_os.sua_os;
        }else{
            janela.proximo = document.frm_os.data_abertura;
        }
        janela.focus();
    }

    else{
        alert("Informar toda ou parte da informação para realizar a pesquisa!");
    }
   }

function verificaSerie(){
	if ($('#qtde_item').val() > 0 && $('#qtde_item').length > 0) {
		for (var i =0;i <$('#qtde_item').val() ;i++ ){
			if ($('#produto_referencia_'+i).length == 0 && $('#produto_serie_'+i).length == 0) {
				document.frm_os.btn_acao.value='gravar';
				document.frm_os.submit();
			} else {
				var resposta =$.ajax({
					url:'ajax_verifica_serie.php',
					data:'produto_referencia='+$('#produto_referencia_'+i).val()+'&produto_serie='+$('#produto_serie_'+i).val(),
					async:false,
					complete: function(respostas){
					}
				}).responseText;

				if (resposta == 'erro'){
					if (confirm('Esse número de série e produto('+$('#produto_serie_'+i).val()+' - ' +$('#produto_referencia_'+i).val()+') foi identificado em nosso arquivo de vendas para locadoras. As locadoras têm acesso à pedido em garantia através da Telecontrol. Esse atendimento poderá ser gravado, e irá para um relatório gerencial. Deseja prosseguir?') == true){
						$('#locacao_serie').val('sim');
						document.frm_os.btn_acao.value='gravar';
						document.frm_os.submit();
					} else {
						$('#produto_referencia_'+i).val(' ');
						$('#produto_descricao_'+i).val(' ');
						$('#produto_serie_'+i).val(' ');
						$('#codigo_fabricacao_'+i).val(' ');
						$('#produto_voltagem_'+i).val(' ');
						$('#type_'+i).val(' ');
						break;
						return false;
					}
				} else {
					document.frm_os.btn_acao.value='gravar';
					document.frm_os.submit();
				}
			}
		}
	} else {
		document.frm_os.btn_acao.value='gravar' ;
		document.frm_os.submit();
	}
}

</script>


<style type="text/css">

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

.formulario{
	background-color:#D9E2EF;
	font:11px Arial;
	text-align:left;
}

.subtitulo{

	background-color: #7092BE;
	font:bold 11px Arial;
	color: #FFFFFF;
}

table.tabela tr td{
	font-family: verdana;
	font-size: 11px;
	border-collapse: collapse;
	border:1px solid #596d9b;
}

.texto_avulso{
	font: 14px Arial; color: rgb(89, 109, 155);
	background-color: #d9e2ef;
	text-align: center;
	width:700px;
	margin: 0 auto;
	border:1px solid #596d9b;
}
</style>

<!-- ============= <HTML> COMEÇA FORMATAÇÃO ===================== -->

<table width="700" border="0" cellpadding="0" cellspacing="0" align="center" class="texto_avulso">
	<tr class='texto_avulso'>
			<td nowrap>Atenção</td>
		</tr>
	<tr >
		<td nowrap>As OS digitadas neste módulo só serão válidas após clicar em “Gravar” e em seguida “Explodir”</td>
	</tr>
</table>

<br>

<?
if (strlen ($msg_erro) > 0) {
?>
<table border="0" cellpadding="0" cellspacing="0" align="center" class='msg_erro' width='700'>
<tr>
	<td height="27" valign="middle" align="center">

<?
	if ( strpos($msg_erro,"É necessário informar o type para o produto") !== false OR strpos($msg_erro,"Type informado para o produto não é válido") !== false  ) {
		$sqlT =	"SELECT tbl_lista_basica.type, tbl_produto.referencia
				FROM tbl_produto
				JOIN tbl_lista_basica USING (produto)
				WHERE (tbl_produto.produto::text) = UPPER('$produto')
				AND   tbl_lista_basica.fabrica = $login_fabrica
				AND   tbl_produto.ativo IS TRUE
				GROUP BY tbl_lista_basica.type, tbl_produto.referencia
				ORDER BY tbl_lista_basica.type;";
		$resT = pg_query($con,$sqlT);
		if (pg_num_rows($resT) > 0) {
			$s = pg_num_rows($resT) - 1;
			for ($t = 0 ; $t < pg_num_rows($resT) ; $t++) {
				$typeT = pg_fetch_result($resT,$t,type);
				$result_type = $result_type.$typeT;

				if ($t == $s) $result_type = $result_type.".";
				else          $result_type = $result_type.",";
			}
			if (strpos($msg_erro,"É necessário informar o type para o produto") !== false) $msg_erro = "É necessário informar o type para o produto ".pg_fetch_result($resT,0,referencia).".<br>";
			if (strpos($msg_erro,"Type informado para o produto não é válido") !== false) $msg_erro = "Type informado para o produto ".pg_fetch_result($resT,0,referencia)." não é válido.<br>";
			if(strlen($result_type) >0){
				$msg_erro = "Selecione o Type: $result_type";
			}
		}
	}

	// retira palavra ERROR:
	if (strpos($msg_erro,"ERROR: ") !== false) {
		$msg_erro = substr($msg_erro, 6);
	}

	// retira CONTEXT:
	if (strpos($msg_erro,"CONTEXT:")) {
		$x = explode('CONTEXT:',$msg_erro);
		$msg_erro = $x[0];
	}
	echo $msg_erro;
?>
		</font></b>
	</td>
</tr>
</table>
<?
}
?>


<br>

<table width="700" border="0" cellpadding="0" cellspacing="0" align="center">
	<tr class='titulo_tabela'>
			<td nowrap>Cadastro</td>
		</tr>
</table>

<table width="700" border="0" cellpadding="0" cellspacing="0" align="center"  class="formulario">
	<tr >
		<td width="30">&nbsp;</td>
		<td valign="top" align="left">

			<!--------------- Formulário ----------------- -->
			<form name="frm_os" id='frm_os' method="post" action="<? echo $PHP_SELF ?>" enctype="multipart/form-data">
			<table width="100%" border="0" cellspacing="3" cellpadding="2" class="formulario">
			<input type='hidden' name='os_revenda' value='<? echo $os_revenda; ?>'>
			<input name="sua_os" type="hidden" value="<? echo $sua_os ?>">
				<tr>

				</tr>
				<tr>
					<td nowrap colspan='2'>
					Código do Posto
					</td>
					<td nowrap colspan='2'>
						Data Abertura
					</td>
				</tr>
					<td nowrap  colspan='2'>
						<input class="frm" type="text" name="posto_codigo" size="15" value="<?
					echo $posto_codigo ?>">
					</td>
					<td nowrap  colspan='2'>
						<input class="frm" type="text" name="data_abertura" size="15" value="<? if (strlen($data_abertura) == 0) $data_abertura = date("d/m/Y"); echo $data_abertura; ?>" readonly>
					</td>

				</tr>
				<tr >
					<td   valign='top' colspan='2'><font size="1" face="Geneva, Arial, Helvetica, san-serif">Tipo da OS cortesia</font><BR>
					</td>
					<td nowrap >
						Nota Fiscal
					</td>
					<td nowrap >
						Data Nota
					</td>
				</tr>
				<tr >
					<td  colspan='2'>
						<select name='tipo_os_cortesia' class="frm">
						<? if(strlen($tipo_os_cortesia) == 0) echo "<option value=''></option>"; ?>
						<option value='Garantia' <? if($tipo_os_cortesia == 'Garantia') echo "selected"; ?>>Garantia</option>
						<option value='Sem Nota Fiscal' <? if($tipo_os_cortesia == 'Sem Nota Fiscal') echo "selected"; ?>>Sem Nota Fiscal</option>
						<option value='Fora da Garantia' <? if($tipo_os_cortesia == 'Fora da Garantia') echo "selected"; ?>>Fora da Garantia</option>
						<? if($login_admin == 155) { ?><option value='Transformação' <? if($tipo_os_cortesia == 'Transformação') echo "selected"; ?>>Transformação</option><? } ?>
						<? if(in_array($login_admin,array(155,5087))) { ?><option value='Promotor' <? if($tipo_os_cortesia == 'Promotor') echo "selected"; ?>>Promotor</option><? } ?>
						<option value='Mau uso' <? if($tipo_os_cortesia == 'Mau uso') echo "selected"; ?>>Mau uso</option>
						<option value='Devolução de valor' <? if($tipo_os_cortesia == 'Devolução de valor') echo "selected"; ?>>Devolução de valor</option>
					</select>
					</td>
					<td nowrap >
						<input name="nota_fiscal" id="nota_fiscal" size="10" maxlength="20"value="<? echo $nota_fiscal ?>" type="text" class="frm" tabindex="0" >
					</td>
					<td nowrap >
						<input name="data_nf" id="data_nf" size="12" maxlength="10"value="<? echo $data_nf ?>" type="text" class="frm" tabindex="0" >
					</td>
				</tr>
				<tr>
					<td colspan='4'height='20'></td>
				</tr>
			</table>

			<table width="100%" border="0" cellspacing="3" cellpadding="2">
				<tr>
					<td colspan="2">
						Nome Revenda
					</td>
					<td colspan="2">
						CNPJ Revenda
					</td>
				</tr>

				<tr>
					<td colspan="2">
						<input class="frm" type="text" name="revenda_nome" size="28" maxlength="50" value="<? echo $revenda_nome ?>" onkeyup="somenteMaiusculaSemAcento(this)">&nbsp;<img src='imagens/lupa.png' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_revenda (document.frm_os.revenda_nome, "nome")' style='cursor:pointer;'>
					</td>
					<td colspan="2">
						<input class="frm" type="text" name="revenda_cnpj" size="25" maxlength="14" value="<? echo $revenda_cnpj ?>">&nbsp;<img src='imagens/lupa.png' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_revenda (document.frm_os.revenda_cnpj, "cnpj")' style='cursor:pointer;'>
					</td>
				</tr>

				<tr>
					<td colspan="2">
						E-mail Revenda
					</td>
					<td colspan="2">
						Fone Revenda
					</td>
				</tr>

				<tr>
					<td colspan="2">
						<input class="frm" type="text" name="revenda_email" size="60" maxlength="50" value="<? echo $revenda_email ?>" tabindex="0">
					</td>
					<td colspan="2">
						<input class="frm" type="text" name="revenda_fone" rel="fone" size="11"  maxlength="20"  value="<? echo $revenda_fone ?>" >
					</td>

				</tr>
			</table>

<input type="hidden" name="revenda_cidade" value="">
<input type="hidden" name="revenda_estado" value="">
<input type="hidden" name="revenda_endereco" value="">
<input type="hidden" name="revenda_cep" value="">
<input type="hidden" name="revenda_numero" value="">
<input type="hidden" name="revenda_complemento" value="">
<input type="hidden" name="revenda_bairro" value="">

			<table width="100%" border="0" cellspacing="3" cellpadding="2">
				<tr>
					<td>
						Observações
					</td>

				</tr>
				<tr>
					<td>
						<input class="frm" type="text" name="obs" size="60" value="<? echo $obs ?>">
					</td>
				</tr>
				<tr>
					<td>
						Email de Contato
					</td>
				</tr>
				<tr>
					<td>
						<input class="frm" type="text" name="consumidor_email" size="60" value="<? echo $consumidor_email ?>">
					</td>
				</tr>
				<?php if ($login_fabrica == 1) : ?>
					<tr>
						<td align='center' style="padding-left:15px;"><?=$inputNotaFiscal?></td>
					</tr>
					<tr>
						<td>&nbsp;</td>
					</tr>
				<?php endif; ?>

			</table>
		</td>
		<td width="30">&nbsp;</td>
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
	$res_os = pg_query($con,$sql);
}

// monta o FOR
echo "<input class='frm' type='hidden' name='qtde_item' value='$qtde_item'>";
echo "<input type='hidden' name='btn_acao' value=''>";

//HD 23159 24/7/2008
if(($qtde_item==0 or strlen($qtde_item)==0) and strlen($os_revenda)==0){
	echo "<br>";
	echo "<table width='700' border='0' cellpadding='0' cellspacing='0' align='center'  class='formulario'>";
	echo "<caption class='titulo_tabela'>ATENÇÃO:</caption>";
	echo "<tr><td width='20'>&nbsp;</td>";
	echo "<td><P align='justify'><FONT COLOR='#000009'><font size='2' face='Geneva, Arial, Helvetica, san-serif'><br>
		A digitação de NF de Revenda deve ser na totalidade, ou seja, todos os produtos referentes a uma NF devem ser digitados na mesma OS. Favor informar a quantidade correta de produtos existentes!
<br><br>
	NOTA: Não será possível digitar novamente a mesma Nota Fiscal!
	</font></td></P>";
	echo "<td width='20'>&nbsp;</td></tr>";
	echo "<tr><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><tr>";
	echo "<tr><td>&nbsp;</td>";
	echo "<td><font size='1' face='Geneva, Arial, Helvetica, san-serif'><b>QUANTIDADE DE PRODUTOS B&D/DW</b></font>&nbsp;&nbsp; <input type=text size=10 maxlength=10 name='qtde_linhas' class='frm'>&nbsp;<input type='button' name='Listar' value='Listar quantidade digitada' onclick=\"javascript: document.frm_os.submit();\">";
	echo "</td><td>&nbsp;</td></tr>";
	echo "</table>";
}else{
	for ($i = 0 ; $i < $qtde_item ; $i++) {

		$novo               = 't';
		$os_revenda_item    = "";
		$referencia_produto = "";
		$serie              = "";
		$produto_descricao  = "";
		$type               = "";
		$codigo_fabricacao  = "";
		$produto_voltagem   = "";

		if ($i % 20 == 0) {
			echo "<table width='80%' border='0' cellpadding='0' cellspacing='2' align='center' bgcolor='#ffffff'>";
			echo "<tr class='menu_top'>";
			echo "<td><font size='1' face='Geneva, Arial, Helvetica, san-serif'>Cod. Fabricação</font></td>\n";
			echo "<td><font size='1' face='Geneva, Arial, Helvetica, san-serif'>Número de série</font></td>";
			echo "<td><font size='1' face='Geneva, Arial, Helvetica, san-serif'>Produto</font></td>";
			echo "<td><font size='1' face='Geneva, Arial, Helvetica, san-serif'>Descrição do produto</font></td>";
			echo "<td><font size='1' face='Geneva, Arial, Helvetica, san-serif'> Voltagem </font></td>";
			echo "<td><font size='1' face='Geneva, Arial, Helvetica, san-serif'>Type</font></td>\n";
			echo "</tr>";
		}

		if (strlen($os_revenda) > 0){
			if (@pg_num_rows($res_os) > 0) {
				$produto = trim(@pg_fetch_result($res_os,$i,produto));
			}

			if(strlen($produto) > 0){
				// seleciona do banco de dados
				$sql = "SELECT   tbl_os_revenda_item.os_revenda_item    ,
								 tbl_os_revenda_item.serie              ,
								 tbl_os_revenda_item.codigo_fabricacao  ,
								 tbl_os_revenda_item.type               ,
								 tbl_produto.referencia                 ,
								 tbl_produto.descricao                  ,
								 tbl_produto.voltagem
						FROM	 tbl_os_revenda
						JOIN	 tbl_os_revenda_item ON tbl_os_revenda.os_revenda = tbl_os_revenda_item.os_revenda
						JOIN	 tbl_produto ON tbl_produto.produto = tbl_os_revenda_item.produto
						WHERE	 tbl_os_revenda_item.os_revenda = $os_revenda";
	//echo $sql;
				$res = pg_query($con,$sql);

				if (@pg_num_rows($res) == 0) {
					$novo                 = 't';
					$os_revenda_item      = $_POST["item_"               . $i];
					$referencia_produto   = $_POST["produto_referencia_" . $i];
					$serie                = $_POST["produto_serie_"      . $i];
					$produto_descricao    = $_POST["produto_descricao_"  . $i];
					$type                 = $_POST["type_"               . $i];
					$codigo_fabricacao    = $_POST["codigo_fabricacao_"  . $i];
					$produto_voltagem     = $_POST["produto_voltagem_"   . $i];
				}else{
					$novo               = 'f';
					$os_revenda_item    = pg_fetch_result($res,$i,os_revenda_item);
					$referencia_produto = pg_fetch_result($res,$i,referencia);
					$produto_descricao  = pg_fetch_result($res,$i,descricao);
					$serie              = pg_fetch_result($res,$i,serie);
					$type               = pg_fetch_result($res,$i,type);
					$codigo_fabricacao  = pg_fetch_result($res,$i,codigo_fabricacao);
					$produto_voltagem   = pg_fetch_result($res,$i,voltagem);
				}
			}else{
				$novo               = 't';
			}
		}else{
			$novo                = 't';
			$os_revenda_item     = $_POST["item_"               . $i];
			$referencia_produto  = $_POST["produto_referencia_" . $i];
			$serie               = $_POST["produto_serie_"      . $i];
			$produto_descricao   = $_POST["produto_descricao_"  . $i];
			$type                = $_POST["type_"               . $i];
			$codigo_fabricacao   = $_POST["codigo_fabricacao_"  . $i];
			$produto_voltagem    = $_POST["produto_voltagem_"   . $i];
		}

		echo "<input type='hidden' name='novo_$i' value='$novo'>\n";
		echo "<input type='hidden' name='item_$i' value='$os_revenda_item'>\n";

		echo "<tr "; if ($linha_erro == $i AND strlen ($msg_erro) > 0) echo "bgcolor='#ffcccc'"; echo ">\n";
		echo "<td><input class='frm' type='text' name='codigo_fabricacao_$i' size='9' maxlength='20' value='$codigo_fabricacao'></td>\n";
		echo "<td><input class='frm' type='text' name='produto_serie_$i'  size='10'  maxlength='20'  value='$serie'>&nbsp;<img src='imagens/lupa.png' border='0' align='absmiddle' onclick=\"javascript: fnc_pesquisa_produto_serie (document.frm_os.produto_referencia_$i,document.frm_os.produto_descricao_$i,document.frm_os.produto_serie_$i)\" style='cursor:pointer;'></td>\n";
		echo "<td><input class='frm' type='text' name='produto_referencia_$i' size='15' maxlength='50' value='$referencia_produto'>&nbsp;<img src='imagens/lupa.png' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_produto (document.frm_os.produto_referencia_$i,document.frm_os.produto_descricao_$i,document.frm_os.produto_voltagem_$i,\"referencia\")' style='cursor:pointer;'></td>\n";
		echo "<td><input class='frm' type='text' name='produto_descricao_$i' size='30' maxlength='50' value='$produto_descricao'>&nbsp;<img src='imagens/lupa.png' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_produto (document.frm_os.produto_referencia_$i,document.frm_os.produto_descricao_$i,document.frm_os.produto_voltagem_$i,\"descricao\")' style='cursor:pointer;'></td>\n";
		echo "<td><input class='frm' type='text' name='produto_voltagem_$i' size='5' value='$produto_voltagem'></td>\n";


		?>
		<td align='center' nowrap>
		&nbsp;
		    <?
		     GeraComboType::makeComboType($parametrosAdicionaisObject, $type, null, array("class"=>"frm", "index"=>$i));
      		     echo GeraComboType::getElement();
		    ?>

		&nbsp;
		</td>
		<?

		echo "</tr>\n";

		// limpa as variaveis
		$novo               = '';
		$os_revenda_item    = '';
		$referencia_produto = '';
		$serie              = '';
		$produto_descricao  = '';
	}
}

echo "<tr>";
echo "<td colspan='5' align='center'>";
echo "<br>";
if($qtde_item != 0 ){
echo "<input type='button' value='Gravar'  rel='sem_submit' class='verifica_servidor' onclick=\"javascript: if (document.frm_os.btn_acao.value == '' ) { verificaSerie() } else { alert ('Aguarde submissão') }\" ALT='Gravar' border='0' style='cursor:pointer;'>";

}

if (strlen($os_revenda) > 0 AND strlen($exclui) > 0) {
	echo "&nbsp;&nbsp;<img src='imagens/btn_apagar.gif' name='name_frm_os' class='verifica_servidor' style='cursor:pointer' onclick=\"javascript: if (document.frm_os.btn_acao.value == '' ) { if(confirm('Deseja realmente apagar esta OS?') == true) { document.frm_os.btn_acao.value='apagar'; document.frm_os.submit(); } else { return; }; } else { alert ('Aguarde submissão') }\" ALT='Apagar a Ordem de Serviço' border='0'>";
}

echo "</td>";
echo "</tr>";
echo "</table>";
?>
</form>

<br>

<? include "rodape.php";?>
