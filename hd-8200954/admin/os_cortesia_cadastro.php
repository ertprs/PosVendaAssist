<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="call_center";
include 'autentica_admin.php';
include 'funcoes.php';
/* error_reporting(E_ALL);
ini_set('display_errors', 1); */
$qtde_itens = 17;
if($login_fabrica == 1){
    require "../classes/ParametrosAdicionaisFabrica.php";
    $parametrosAdicionaisObject = new ParametrosAdicionaisFabrica($login_fabrica);

    require "../classes/form/GeraComboType.php";
}
if (strlen($_POST['os']) > 0) $os = trim($_POST['os']);
else $os = trim($_GET['os']);

$btn_acao = $_POST['btn_acao'];

$os_produto = $_POST['os_produto'];

$msg_erro = "";
$limite_anexos_nf = 5;
include_once('../anexaNF_inc.php');


if ($btn_acao == "gravar") {

	# HD 30250 - Francisco Ambrozio (31/7/08) - o Paulo conversou com a Fabíola
	#   e foi solicitado que desabitasse consumidor_revenda.
	# Defini-o com o valor "C".
	$xconsumidor_revenda = $_POST["consumidor_revenda"];
	#$xconsumidor_revenda = "C";

	$posto_codigo = trim($_POST['posto_codigo']);
	if (strlen($posto_codigo) == 0) {
		$msg_erro .= " Digite o Código do Posto.";
	}else{
		$posto_codigo = str_replace("-","",$posto_codigo);
		$posto_codigo = str_replace(".","",$posto_codigo);
		$posto_codigo = str_replace("/","",$posto_codigo);
		$posto_codigo = substr($posto_codigo,0,14);
	}

	$data_abertura      = fnc_formata_data_pg(trim($_POST['data_abertura']));
	if ($data_abertura == "null") $msg_erro = " Digite a Data de Abertura da OS.";

	$produto_referencia = trim($_POST['produto_referencia']);
	$produto_referencia = str_replace("-","",$produto_referencia);
	$produto_referencia = str_replace(" ","",$produto_referencia);
	$produto_referencia = str_replace("/","",$produto_referencia);
	$produto_referencia = str_replace(".","",$produto_referencia);
	if (strlen($produto_referencia) == 0) $msg_erro .= " Digite a Referência do produto.";

	$produto_voltagem   = trim($_POST['produto_voltagem']);

	$produto_type       = trim($_POST['produto_type']);
#	if (strlen($produto_type) == 0) $msg_erro .= " Selecione o Tipo do produto.";

	# HD 29241 - Francisco Ambrozio (31/7/08) - É necessário conferir se o
	#   tipo de produto selecionado corresponde a um tipo válido.
	$sqltype="SELECT tbl_lista_basica.type
			FROM tbl_lista_basica
			JOIN tbl_produto USING (produto)
			WHERE tbl_lista_basica.fabrica = $login_fabrica
			AND tbl_produto.referencia_pesquisa = '$produto_referencia'
			GROUP BY tbl_lista_basica.type
			ORDER BY tbl_lista_basica.type; ";

	$restype = pg_exec($con,$sqltype);

	if (pg_numrows($restype) > 0) {
		for ($t = 0 ; $t < pg_numrows($restype) ; $t++){
			$canbtype = trim(pg_result($restype,$t,type));
			if ($produto_type == $canbtype){
				$msg_erro_type = "";
				break;
			}else{
				/*if ($canbtype == ""){
					$msg_erro_type = "<br>Para este produto não é necessário selecionar Tipo.<br>";
					continue;
				}else{
					$msg_erro_type = "<br>O tipo possível para este produto é: $canbtypeshow<br>";
				}*/
				$msg_erro_type = "<br>O Tipo selecionado para este produto não é válido.<br>";
				continue;
			}
		}
	}else{
		if (strlen($produto_type) > 0){
			$msg_erro_type = "<br>Para este produto não é necessário selecionar o Tipo.<br>";
		}
	}

	$produto_serie      = trim($_POST['produto_serie']);
	if (strlen($produto_serie) == 0) $xproduto_serie = 'null';
	else                             $xproduto_serie = "'".$produto_serie."'";

		#HD 231110
	$locacao_serie = $_POST['locacao_serie'];
	if($locacao_serie == 'sim') {

	}

	if(strlen($msg_erro)==0){
		$tipo_os_cortesia   = $_POST['tipo_os_cortesia'];
		if (strlen($tipo_os_cortesia) == 0) $msg_erro = " Selecione o Tipo da OS Cortesia.";
	//takashi 10/10
		# HD 30250 - desabilitei os ifs
		//if ($xconsumidor_revenda == "R") {
			if (strlen(trim($_POST['consumidor_fone'])) == 0) $xconsumidor_fone =" 'null'";
			else        $xconsumidor_fone = "'".trim($_POST['consumidor_fone'])."'";
		//}
	}

	if(strlen($msg_erro)==0){
		if ($xconsumidor_revenda == "C") {
		$consumidor_nome    = trim($_POST['consumidor_nome']);

		if($login_fabrica==1){
			if (strlen(trim($_POST['fisica_juridica'])) == 0) $msg_erro = "Escolha o Tipo Consumidor.<BR> ";
			else $xfisica_juridica = "'".($_POST['fisica_juridica'])."'";
		}else{
			$xfisica_juridica = "null";
		}

		$consumidor_cpf     = trim($_POST['consumidor_cpf']);
		$consumidor_cpf     = str_replace("-","",$consumidor_cpf);
		$consumidor_cpf     = str_replace(" ","",$consumidor_cpf);
		$consumidor_cpf     = str_replace("/","",$consumidor_cpf);
		$consumidor_cpf     = str_replace(".","",$consumidor_cpf);
		if (strlen($consumidor_cpf) > 14) $msg_erro = " Tamanho do CPF/CNPJ do cliente inválido.";

		$consumidor_cidade = strtoupper(trim($_POST['consumidor_cidade']));
		$consumidor_estado = strtoupper(trim($_POST['consumidor_estado']));

		if (($consumidor_estado == "UF" or (strlen($consumidor_estado) == 0))){
			$msg_erro = "Digite o estado do consumidor.<br>";
		}
	}

	if (strlen(trim($_POST['consumidor_fone'])) == 0) $xconsumidor_fone = "'null'";
	else        $xconsumidor_fone = "'".trim($_POST['consumidor_fone'])."'";
	} //takashi

	$consumidor_email       = trim ($_POST['consumidor_email']) ;

	// HD 18051
	if(strlen($consumidor_email) ==0 ){
		$msg_erro ="Digite o email de contato. <br>";
	}else{
		$consumidor_email = trim($_POST['consumidor_email']);
	}

	if (strlen ($_POST['nota_fiscal']) == 0) $xnota_fiscal = 'null';
	else        $xnota_fiscal = "'".trim($_POST['nota_fiscal'])."'";

	if(strlen($msg_erro)==0){
		$data_nf = $_POST['data_nf'];
		if(strlen($data_nf) > 0){
			list($di, $mi, $yi) = explode("/", $data_nf);
			$data_nf = $yi."-".$mi."-".$di;
			if(!checkdate($mi,$di,$yi))
				$msg_erro = "Data da Compra Inválida";
		} else {
			$data_nf = 'null';
		}
	}

	if(strlen($msg_erro)==0){
		if (strlen ($_POST['troca_garantia']) == 0) $xtroca_garantia = 'null';
		else        $xtroca_garantia = "'".trim($_POST['troca_garantia'])."'";

		if (strlen ($_POST['troca_faturada']) == 0) $xtroca_faturada = 'null';
		else        $xtroca_faturada = "'".trim($_POST['troca_faturada'])."'";

		if ($data_nf == 'null'){
			//if ($xtroca_faturada <> 't')         $msg_erro = " Digite a data de compra.";
			if ($tipo_os_cortesia == 'Garantia') $msg_erro = " Digite a data de compra.";
		}
	}

	$cdata_abertura = str_replace("'","",$data_abertura);

	if(strlen($msg_erro)==0){
		if ($tipo_os_cortesia == 'Garantia') {
			if (strlen($nota_fiscal) == 0) $msg_erro = " Digite a Nota Fiscal.";
			if ($data_nf == "null")        $msg_erro = " Digite a Data da Compra.";
		}
	}

	//adicionado por Sono 26/07/2007 HD 3251
	if (($tipo_os_cortesia == 'Sem Nota Fiscal' OR $tipo_os_cortesia == 'Fora de Garantia' OR $tipo_os_cortesia == 'Promotor') AND (strlen($nota_fiscal)>0 OR (strlen($data_nf)>0 AND $data_nf<>"null"))) {
		$msg_erro = " Os dados da nota fiscal não devem ser informados para este tipo de Cortesia.";
	}
	if(strlen($msg_erro)==0){
		$xrevenda_nome = trim($_POST["revenda_nome"]);

		$xrevenda_cnpj = trim($_POST["revenda_cnpj"]);
		$xrevenda_cnpj = str_replace("-","",$xrevenda_cnpj);
		$xrevenda_cnpj = str_replace(".","",$xrevenda_cnpj);
		$xrevenda_cnpj = str_replace("/","",$xrevenda_cnpj);
		$xrevenda_cnpj = substr($xrevenda_cnpj,0,14);

		# HD 30250 - Francisco Ambrozio - os dados da revenda são obrigatórios
		#  caso o tipo de cortesia seja "Mau uso", "Devolução de valor" ou "Garantia".
		if (($tipo_os_cortesia == 'Garantia' OR $tipo_os_cortesia == 'Mau uso' OR $tipo_os_cortesia == 'Devolução de valor') AND ((strlen($nota_fiscal) == 0 OR (strlen($data_nf) == 0 AND $data_nf == "null")) OR ((strlen($xrevenda_nome) == 0) OR (strlen($xrevenda_cnpj) == 0)))) {
			$msg_erro = " Os dados da revenda devem ser informados para este tipo de Cortesia.";
		}
	}

	if (strlen($xrevenda_cnpj) > 0) {
		$sqlR = "SELECT revenda FROM tbl_revenda WHERE cnpj = '$xrevenda_cnpj';";
		$resR = pg_exec($con,$sqlR);
		if (pg_numrows($resR) == 1) {
			$revenda = "'" . pg_result($resR,0,0) . "'";
		}else{
			$revenda = "null";
		}
	}else{
		$revenda = "null";
	}

	if (1 == 2) {
		if (strlen($consumidor_cpf) == 0) $msg_erro = " Digite o CPF/CNPJ do Consumidor.";
	}

	if (strlen($_POST['obs']) == 0) $xobs = 'null';
	else                            $xobs = "'".trim($_POST['obs'])."'";

	if ((strlen($msg_erro) == 0) and (strlen($msg_erro_type) == 0)) {

		if (strlen($produto_referencia) > 0) {
			if(strlen($produto_voltagem) > 0) $cond_voltagem = " AND UPPER(trim(tbl_produto.voltagem)) = UPPER(trim('$produto_voltagem')) ";

			$sql =	"SELECT tbl_produto.produto
					FROM tbl_produto
					JOIN tbl_linha USING (linha)
					WHERE UPPER(trim(tbl_produto.referencia_pesquisa)) = UPPER(trim('$produto_referencia'))
					$cond_voltagem
					AND tbl_linha.fabrica = $login_fabrica;";
			$res      = pg_exec($con,$sql);

			if (pg_numrows($res) > 0) {
				$produto = pg_result($res,0,produto);
			}else{
				$msg_erro = " Produto $produto_referencia não cadastrado.";
			}
		}

		if ($xtroca_faturada <> "'t'" and $tipo_os_cortesia == "Garantia") { // verifica troca faturada para a Black

			$sql = "SELECT garantia FROM tbl_produto WHERE tbl_produto.produto = $produto";
			$res = @pg_exec ($con,$sql);

			if (@pg_numrows($res) == 0) {
				$msg_erro = " Produto $produto_referencia sem garantia";
			}

			if (strlen($msg_erro) == 0) {
				$garantia = trim(@pg_result($res,0,garantia));

				$sql = "SELECT ('$data_nf'::date + (($garantia || ' months')::interval))::date;";
				$res = @pg_exec ($con,$sql);
				$msg_erro = pg_errormessage($con);

				if (strlen($msg_erro) > 0) $msg_erro =  "Data da NF inválida.";

				if (strlen($msg_erro) == 0) {
					if (pg_numrows ($res) > 0) {
						$data_final_garantia = trim(pg_result($res,0,0));
					}

#######################
					if ($tipo_os_cortesia <> 'Fora da Garantia' AND $data_final_garantia < $cdata_abertura) {
						$msg_erro = " Produto $produto_referencia fora da Garantia, vencida em ". substr($data_final_garantia,8,2) ."/". substr($data_final_garantia,5,2) ."/". substr($data_final_garantia,0,4);
					}
				}
			}
		}

		if (strlen($posto_codigo) > 0) {
			$sql =	"SELECT tbl_posto.posto
					FROM tbl_posto
					JOIN tbl_posto_fabrica	ON tbl_posto.posto = tbl_posto_fabrica.posto
											AND tbl_posto_fabrica.fabrica = $login_fabrica
					WHERE tbl_posto_fabrica.codigo_posto = '$posto_codigo';";
			$res = pg_exec ($con,$sql);
			if (pg_numrows($res) > 0) {
				$posto = pg_result ($res,0,0);
			}else{
				$msg_erro = " Posto $posto_codigo não cadastrado.";
			}

            $sqlPostoCred = "SELECT * FROM tbl_posto_fabrica
                                WHERE posto = $posto
                                AND fabrica = $login_fabrica
                                AND credenciamento = 'DESCREDENCIADO'";
            $qryPostoCred = pg_query($con, $sqlPostoCred);

            if (pg_num_rows($qryPostoCred) > 0) {
                $msg_erro = 'Posto informado encontra-se DESCREDENCIADO';
            }
		}

	if ($login_fabrica == 1) {
		$sql =	"SELECT tbl_familia.familia, tbl_familia.descricao
				FROM tbl_produto
				JOIN tbl_familia USING (familia)
				WHERE tbl_familia.fabrica = $login_fabrica
				AND   tbl_familia.familia = 347
				AND   tbl_produto.linha   = 198
				AND   tbl_produto.produto = $produto;";
		$res = @pg_exec($con,$sql);
		if (pg_numrows($res) > 0) {
			$xtipo_os_compressor = "10";
		}else{
			$xtipo_os_compressor = 'null';
		}
	}else{
		$xtipo_os_compressor = 'null';
	}

		$codigo_fabricacao = trim($_POST['codigo_fabricacao']);
		if ($login_fabrica == 1 AND strlen($codigo_fabricacao) == 0){
			if(strlen($os)>0){
				$sqlT = "SELECT tipo_atendimento FROM tbl_os WHERE os = $os;";
				$resT = pg_exec($con,$sqlT);

				if(pg_numrows($resT)>0){
					$tipo_atendimento = pg_result($resT,0,tipo_atendimento);

				}
			}
		}

		if($tipo_atendimento==64 or $tipo_atendimento==65 or $tipo_atendimento==69){
			if(strlen($codigo_fabricacao)==0) $xcodigo_fabricacao = "NULL";
			else                              $xcodigo_fabricacao = "'" . $codigo_fabricacao . "'";
		}else{
			if(strlen($codigo_fabricacao)==0) $msg_erro = "Digite o Código de fabricação do produto.";
			else                              $xcodigo_fabricacao = "'" . $codigo_fabricacao . "'";
		}

		if ($login_fabrica <> 1) $xcodigo_fabricacao = "NULL";

		$res = pg_exec ($con,"BEGIN TRANSACTION");

		if ((strlen($msg_erro) == 0) and (strlen($msg_erro_type) == 0)) {
		//takashi 10-10
		if ($xconsumidor_revenda == "C") {
			if (strlen($consumidor_cpf) > 0 AND strlen($consumidor_cidade) > 0 AND strlen($consumidor_estado) > 0 ) {

				$sql = "SELECT fnc_qual_cidade ('$consumidor_cidade','$consumidor_estado')";
				$res = pg_exec ($con,$sql);
				$cidade = pg_result ($res,0,0);

				$sql  = "SELECT cliente FROM tbl_cliente WHERE cpf = '$consumidor_cpf'";
				$res1 = pg_exec($con,$sql);

				if (pg_numrows($res1) > 0) {
					$cliente = pg_result ($res1,0,cliente);
					$sql = "UPDATE tbl_cliente SET
								nome		= '$consumidor_nome' ,
								cpf			= '$consumidor_cpf'  ,
								cidade		= $cidade
							WHERE tbl_cliente.cliente = $cliente";
					$res3 = @pg_exec ($con,$sql);
					if (strlen (pg_errormessage($con)) > 0) {
						$msg_erro = pg_errormessage ($con);
					}
				}else{
					$sql = "INSERT INTO tbl_cliente (
								nome   ,
								cpf    ,
								cidade
							) VALUES (
								'$consumidor_nome' ,
								'$consumidor_cpf'  ,
								$cidade
							)";
					$res3 = @pg_exec ($con,$sql);
					if (strlen (pg_errormessage($con)) > 0) {
						$msg_erro = pg_errormessage ($con);
					}
				}
		}
		}

			if (strlen($os) == 0) {
				########## I N S E R E   D A D O S ##########
				$sql = "INSERT INTO tbl_os (
								sua_os            ,
								posto             ,
								data_abertura     ,
								fabrica           ,
								admin             ,
								produto           ,
								serie             ,
								consumidor_nome   ,
								consumidor_cpf    ,
								consumidor_cidade ,
								consumidor_estado ,
								consumidor_fone   ,
								revenda_cnpj      ,
								revenda_nome      ,
								nota_fiscal       ,";
						if($data_nf<>'null'){
							$sql .="data_nf       ,";
						}
						$sql .=" codigo_fabricacao ,
								tipo_os_cortesia  ,
								type              ,
								cortesia          ,
								troca_garantia    ,
								troca_faturada    ,
								consumidor_revenda,
								revenda           ,
								tipo_os           ,
								obs               ,
								consumidor_email  ,
								fisica_juridica
							) VALUES (
								null                 ,
								$posto               ,
								$data_abertura       ,
								$login_fabrica       ,
								$login_admin         ,
								$produto             ,
								$xproduto_serie      ,
								'$consumidor_nome'   ,
								'$consumidor_cpf'    ,
								'$consumidor_cidade' ,
								'$consumidor_estado' ,
								$xconsumidor_fone    ,
								'$xrevenda_cnpj'     ,
								'$revenda_nome'      ,
								'$nota_fiscal'       ,";
							if($data_nf<>'null'){
								$sql .="'$data_nf'       ,";
							}
							$sql .="$xcodigo_fabricacao  ,
								'$tipo_os_cortesia'  ,
								'$produto_type'      ,
								't'                  ,
								$xtroca_garantia     ,
								$xtroca_faturada     ,
								'$xconsumidor_revenda',
								$revenda             ,
								$xtipo_os_compressor ,
								$xobs                ,
								'$consumidor_email'  ,
								$xfisica_juridica
								);";
#				echo nl2br($sql);
#				exit;
				$res = pg_exec($con,$sql);
				$msg_erro = pg_errormessage($con);
				$msg_erro = substr($msg_erro,6);

				if ((strlen($msg_erro) == 0) and (strlen($msg_erro_type) == 0)) {
					if (strlen($os) == 0) {
						$res = @pg_exec ($con,"SELECT CURRVAL ('seq_os')");
						$os  = pg_result ($res,0,0);
					}
					$res      = @pg_exec ($con,"SELECT fn_valida_os($os, $login_fabrica)");
					$msg_erro = @pg_errormessage($con);
					$msg_erro = substr($msg_erro,6);
				}

				if ((strlen ($msg_erro) == 0) and (strlen($msg_erro_type) == 0)) {
					if (strlen($os) > 0) {
						$sql =	"INSERT INTO tbl_os_produto (
										os      ,
										produto ,
										serie   ,
										versao
									) VALUES (
										$os              ,
										$produto         ,
										'$produto_serie' ,
										'$produto_type'
									);";
						$res      = @pg_exec($con,$sql);
						$msg_erro = @pg_errormessage($con);
						$msg_erro = substr($msg_erro,6);
					}
				}

				if ((strlen($msg_erro) == 0) and (strlen($msg_erro_type) == 0)) {
					if (strlen($os_produto) == 0) {
						$res = @pg_exec ($con,"SELECT CURRVAL ('seq_os_produto')");
						$os_produto  = pg_result ($res,0,0);
					}
					$res      = @pg_exec ($con,"SELECT fn_valida_os($os, $login_fabrica)");
					$msg_erro = @pg_errormessage($con);
					$msg_erro = substr($msg_erro,6);
				}

			}else{
				if($data_nf <> 'null'){
					$data_nf = "'$data_nf'";
				}
				########## A L T E R A   D A D O S ##########
				$sql =	"UPDATE tbl_os SET
							posto              = $posto              ,
							data_abertura      = $data_abertura      ,
							produto            = $produto            ,
							serie              = $xproduto_serie     ,
							consumidor_nome    = '$consumidor_nome'  ,
							consumidor_cpf     = '$consumidor_cpf'   ,
							consumidor_fone    = $xconsumidor_fone   ,
							nota_fiscal        = '$nota_fiscal'      ,";
							if($data_nf<>'null'){
								$sql .= "data_nf  =  $data_nf        ,";
							}
							$sql .=" codigo_fabricacao  = $xcodigo_fabricacao ,
							tipo_os_cortesia   = '$tipo_os_cortesia' ,
							troca_garantia     = $xtroca_garantia    ,
							troca_faturada     = $xtroca_faturada     ,
							consumidor_revenda = '$xconsumidor_revenda',
							revenda            = $revenda             ,
							tipo_os            = $xtipo_os_compressor ,
							obs                = $xobs                ,
							fisica_juridica    = $xfisica_juridica    ,
							type               = '$produto_type'
						WHERE os      = $os
						AND   fabrica = $login_fabrica";
				$res = pg_exec($con,$sql);
				$msg_erro = pg_errormessage($con);
				$msg_erro = substr($msg_erro,6);

				if ((strlen ($msg_erro) == 0) and (strlen($msg_erro_type) == 0)) {
					$sql =	"UPDATE tbl_os_produto SET
									produto = $produto         ,
									serie   = '$produto_serie' ,
									versao  = '$produto_type'
							WHERE os         = $os
							AND   os_produto = $os_produto";
					$res = pg_exec($con,$sql);
					$msg_erro = pg_errormessage($con);
					$msg_erro = substr($msg_erro,6);
				}
			}
		}

		if ((strlen ($msg_erro) == 0) and (strlen($msg_erro_type) == 0)) {
			if (strlen($os) == 0) {
				$res = pg_exec ($con,"SELECT CURRVAL ('seq_os')");
				$os  = pg_result ($res,0,0);
			}
			$res      = @pg_exec ($con,"SELECT fn_valida_os($os, $login_fabrica)");
			$msg_erro = pg_errormessage($con);
			$msg_erro = substr($msg_erro,6);
		}

		if ( strlen($msg_erro) == 0 && $login_fabrica == 1 ) {
            foreach (range(0, 4) as $idx) {
                if ($_FILES["foto_nf"]['tmp_name'][$idx][0] != '') {
                    $file = array(
                        "name" => $_FILES["foto_nf"]["name"][$idx],
                        "type" => $_FILES["foto_nf"]["type"][$idx],
                        "tmp_name" => $_FILES["foto_nf"]["tmp_name"][$idx],
                        "error" => $_FILES["foto_nf"]["error"][$idx],
                        "size" => $_FILES["foto_nf"]["size"][$idx]
                    );

                    $anexou = anexaNF($os, $file);
                    if ($anexou !== 0) $msg_erro .= (is_numeric($anexou)) ? $msgs_erro[$anexou] : $anexou; // '0' é que executou OK
                }
            }
		}

		if ((strlen ($msg_erro) == 0) and (strlen($msg_erro_type) == 0)) {
			$res = pg_query($con,"COMMIT");
			header ("Location: os_cortesia_item.php?os=$os");
			exit;
		}

		if((strlen ($msg_erro) > 0) or (strlen($msg_erro_type) > 0)) {
			$res = pg_exec($con,"ROLLBACK TRANSACTION");
		}

	}
}

if (strlen($_GET['os']) > 0) {
	$sql =	"SELECT tbl_os.os                                                   ,
					tbl_os.sua_os                                               ,
					tbl_os.posto                                                ,
					to_char(tbl_os.data_abertura,'DD/MM/YYYY') AS data_abertura ,
					tbl_os.fabrica                                              ,
					tbl_os.admin                                                ,
					tbl_os.produto                                              ,
					tbl_os.serie                                                ,
					tbl_os.codigo_fabricacao                                    ,
					tbl_os.consumidor_nome                                      ,
					tbl_os.consumidor_cpf                                       ,
					tbl_os.consumidor_cidade                                    ,
					tbl_os.consumidor_estado                                    ,
					tbl_os.consumidor_fone                                      ,
					tbl_os.nota_fiscal                                          ,
					to_char(tbl_os.data_nf,'DD/MM/YYYY')       AS data_nf       ,
					tbl_os.tipo_os_cortesia                                     ,
					tbl_os.troca_garantia                                       ,
					tbl_os.troca_faturada                                       ,
					tbl_os.tipo_os_cortesia                                     ,
					tbl_os.consumidor_revenda                                   ,
					tbl_os.revenda                                              ,
					tbl_os_produto.os_produto                                   ,
					tbl_os_produto.versao                                       ,
					tbl_produto.referencia                                      ,
					tbl_produto.voltagem                                        ,
					tbl_posto_fabrica.codigo_posto                              ,
					tbl_os.consumidor_email                                     ,
					tbl_os.fisica_juridica
			FROM	tbl_os
			JOIN	tbl_os_produto USING (os)
			JOIN	tbl_produto ON tbl_os.produto  = tbl_produto.produto
			JOIN	tbl_posto   ON tbl_posto.posto = tbl_os.posto
			JOIN	tbl_posto_fabrica	ON  tbl_posto.posto           = tbl_posto_fabrica.posto
										AND tbl_posto_fabrica.fabrica = $login_fabrica
			WHERE	tbl_os.os      = $os
			AND		tbl_os.fabrica = $login_fabrica";
	$res = pg_exec($con,$sql);

	if (pg_numrows($res) > 0) {
		$os                 = pg_result($res,0,os);
		$sua_os             = pg_result($res,0,sua_os);

		if($login_fabrica != '1'){
			$sua_os         = substr($sua_os, strlen($sua_os)-5, strlen($sua_os));
		}
		$posto              = pg_result($res,0,posto);
		$data_abertura      = pg_result($res,0,data_abertura);
		$fabrica            = pg_result($res,0,fabrica);
		$admin              = pg_result($res,0,admin);
		$produto            = pg_result($res,0,produto);
		$produto_serie      = pg_result($res,0,serie);
		$codigo_fabricacao  = pg_result($res,0,codigo_fabricacao);
		$consumidor_nome    = pg_result($res,0,consumidor_nome);
		$consumidor_cpf     = pg_result($res,0,consumidor_cpf);
		$consumidor_cidade  = pg_result($res,0,consumidor_cidade);
		$consumidor_estado  = pg_result($res,0,consumidor_estado);
		$consumidor_fone    = pg_result($res,0,consumidor_fone);
		$consumidor_email   = pg_result($res,0,consumidor_email);
		$fisica_juridica    = pg_result($res,0,fisica_juridica);
		$nota_fiscal        = pg_result($res,0,nota_fiscal);
		$data_nf            = pg_result($res,0,data_nf);
		$os_produto         = pg_result($res,0,os_produto);
		$tipo_os_cortesia   = pg_result($res,0,tipo_os_cortesia);
		$troca_garantia     = pg_result($res,0,troca_garantia);
		$troca_faturada     = pg_result($res,0,troca_faturada);
		$produto_referencia = pg_result($res,0,referencia);
		$produto_voltagem   = pg_result($res,0,voltagem);
		$posto_codigo       = pg_result($res,0,codigo_posto);
		$produto_type       = pg_result($res,0,versao);
		$consumidor_revenda = pg_result($res,0,consumidor_revenda);
		$revenda            = pg_result($res,0,revenda);
		$tipo_os_cortesia	= pg_result ($res,0,tipo_os_cortesia);

		if (strlen($revenda) > 0) {
			$sqlR = "SELECT nome, cnpj FROM tbl_revenda WHERE revenda = $revenda;";
			$resR = pg_exec($con,$sqlR);
			if (pg_numrows($resR) == 1) {
				$revenda_cnpj = trim(pg_result($resR,0,cnpj));
				$revenda_nome = trim(pg_result($resR,0,nome));
			}
		}
	}
}

if ((strlen($msg_erro) > 0) or (strlen($msg_erro_type) > 0)) {
	$os                 = $_POST['os'];
	$sua_os             = $_POST['sua_os'];
	$posto              = $_POST['posto'];
	$posto_codigo       = trim($_POST['posto_codigo']);
	$data_abertura      = trim($_POST['data_abertura']);
	$os_produto         = $_POST['os_produto'];
	$produto            = $_POST['produto'];
	$produto_referencia = trim($_POST['produto_referencia']);
	$produto_descricao  = trim($_POST['produto_nome']);
	$produto_voltagem   = trim($_POST['produto_voltagem']);
	$produto_type       = trim($_POST['produto_type']);
	$produto_serie      = trim($_POST['produto_serie']);
	$tipo_os_cortesia   = $_POST['tipo_os_cortesia'];
	$troca_garantia     = $_POST['troca_garantia'];
	$troca_faturada     = $_POST['troca_faturada'];
	$codigo_fabricacao  = trim($_POST['codigo_fabricacao']);
	$consumidor_nome    = trim($_POST['consumidor_nome']);
	$consumidor_cpf     = trim($_POST['consumidor_cpf']);
	$consumidor_cidade  = trim($_POST['consumidor_cidade']);
	$consumidor_estado  = trim($_POST['consumidor_estado']);
	$consumidor_fone    = trim($_POST['consumidor_fone']);
	$consumidor_email   = trim($_POST['consumidor_email']);
	$nota_fiscal        = trim($_POST['nota_fiscal']);
	$data_nf            = trim($_POST['data_nf']);
	$consumidor_revenda = $_POST['consumidor_revenda'];
	$revenda_nome       = trim($_POST['revenda_nome']);
	$revenda_cnpj       = trim($_POST['revenda_cnpj']);
	$fisica_juridica    = trim($_POST['fisica_juridica']);
}

$title = "CADASTRO DE OS DO TIPO CORTESIA - ADMIN";

$layout_menu = 'callcenter';


include "cabecalho.php";
include "javascript_pesquisas_novo.php";
include "javascript_calendario.php" ;

if ($login_fabrica <> 1)
{
	echo "<script type=\"text/javascript\" src=\"js/jquery.blockUI_2.39.js\"></script>
	<script type=\"text/javascript\" src=\"js/plugin_verifica_servidor.js\"></script>";
}


?>

<script type="text/javascript" src="js/jquery.alphanumeric.js"></script>
<script type="text/javascript" src="js/jquery.maskedinput.js"></script>
<script src="../plugins/shadowbox/shadowbox.js" type="text/javascript"></script>
<link rel="stylesheet" type="text/css" href="../plugins/shadowbox/shadowbox.css" media="all">

<script>

$(document).ready(function(){
	Shadowbox.init();
	$("input[name=nota_fiscal]").numeric({allow:"-"});
	$("input[name=consumidor_fone]").maskedinput("(99) 9999-9999");
	$("input[name=data_nf]").maskedinput("99/99/9999");
	$("input[name=data_abertura]").maskedinput("99/99/9999");
	$("#consumidor_cpf").numeric({allow:".- "});
	$("#consumidor_cidade").alpha({allow:" "});
	$("#revenda_cnpj").numeric({allow:"./- "});

});

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

function verificaSerie(){

    <?php if ($login_fabrica == '1'): ?>
    var confirmouPosto = $("#confirmouPosto").val();
    var confirmacao = false;

    switch (confirmouPosto) {
        case "0":
        case "1":
            confirmacao = confirm('Deseja realmente gravar esta OS?');
            break;
        case "2":
            alert('Posto informado encontra-se DESCREDENCIADO');
            break;
        default:
            confirmacao = verificaPostoCredenciamento();
    }

    <?php else: ?>
    confirmacao = confirm('Deseja realmente gravar esta OS?');
    <?php endif ?>

    if (false === confirmacao) {
        return;
    }

	if ($('#produto_referencia').length == 0 && $('#prdouto_serie').length == 0) {
		document.frm_os.btn_acao.value='gravar';
		document.frm_os.submit();
	}else{
		$.ajax({
			url:'ajax_verifica_serie.php',
			data:'produto_referencia='+$('#produto_referencia').val()+'&produto_serie='+$('#produto_serie').val(),
			complete: function(respostas){
				if (respostas.responseText == 'erro'){
					if (confirm('Esse número de série e produto foi identificado em nosso arquivo de vendas para locadoras. As locadoras têm acesso à pedido em garantia através da Telecontrol. Esse atendimento poderá ser gravado, e irá para um relatório gerencial. Deseja prosseguir?') == true){
						$('#locacao_serie').val('sim');
						document.frm_os.btn_acao.value='gravar';
						document.frm_os.submit();
					}else{
						return;
					}
				}else{
					document.frm_os.btn_acao.value='gravar';
					document.frm_os.submit();
				}
			}
		})
	}
}

function formata_cnpj(cnpj, form){
	var mycnpj = '';
		mycnpj = mycnpj + cnpj;
		myrecord = "revenda_cnpj";
		myform = form;

		if (mycnpj.length == 2){
			mycnpj = mycnpj + '.';
			window.document.forms["" + myform + ""].elements[myrecord].value = mycnpj;
		}
		if (mycnpj.length == 6){
			mycnpj = mycnpj + '.';
			window.document.forms["" + myform + ""].elements[myrecord].value = mycnpj;
		}
		if (mycnpj.length == 10){
			mycnpj = mycnpj + '/';
			window.document.forms["" + myform + ""].elements[myrecord].value = mycnpj;
		}
		if (mycnpj.length == 15){
			mycnpj = mycnpj + '-';
			window.document.forms["" + myform + ""].elements[myrecord].value = mycnpj;
		}
}

function retorna_revenda(nome,cnpj,nome_cidade,fone,endereco,numero,complemento,bairro,cep,estado,email)
{
	gravaDados('revenda_nome',nome);
	gravaDados('revenda_cnpj',cnpj);
}

function retorna_dados_produto(produto,linha,nome_comercial,voltagem,referencia,descricao,referencia_fabrica,garantia,ativo,valor_troca,troca_garantia,troca_faturada,mobra,off_line,capacidade,ipi,troca_obrigatoria,posicao)
{
	gravaDados('produto_nome',descricao);
	gravaDados('produto_referencia',referencia);
	gravaDados('produto_voltagem',voltagem);
}


function retorna_posto(codigo_posto,posto,nome,cnpj,cidade,estado,credenciamento,num_posto)
{
	gravaDados('posto',posto);
	gravaDados('posto_codigo',codigo_posto);
	gravaDados('posto_nome',nome);

    <?php
    if ($login_fabrica == '1') {
        echo 'verificaPostoCredenciamento();';
    }
    ?>
}

function gravaDados(name, valor){
    try{
        $("input[name="+name+"]").val(valor);
    } catch(err){
        return false;
    }
}
</script>

<script type="text/javascript" src="js/verifica_posto_credenciamento.js"></script>

<style type="text/css">
.titulo_tabela{
	background-color:#596d9b;
	font: bold 14px "Arial";
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
    background-color:#008000;
    font: bold 14px "Arial";
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
	text-align:center;
}

.espaco{
	padding:0 0 0 100px;
}
</style>
<? if ((strlen ($msg_erro) > 0) or (strlen($msg_erro_type) > 0)) { ?>
<br>
<table border="0" cellpadding="0" cellspacing="0" align="center" width = '700'>
<tr>
	<td valign="middle" align="center" class='error'>
<?
	if ($login_fabrica == 1 AND ( strpos($msg_erro,"É necessário informar o type para o produto") !== false OR strpos($msg_erro,"Type informado para o produto não é válido") !== false ) ) {
		$produto_referencia = trim($_POST["produto_referencia"]);
		$produto_voltagem   = trim($_POST["produto_voltagem"]);
		$sqlT =	"SELECT tbl_lista_basica.type
				FROM tbl_produto
				JOIN tbl_lista_basica USING (produto)
				WHERE UPPER(tbl_produto.referencia_pesquisa) = UPPER('$produto_referencia')
				AND   tbl_produto.voltagem = '$produto_voltagem'
				AND   tbl_lista_basica.fabrica = $login_fabrica
				AND   tbl_produto.ativo IS TRUE
				GROUP BY tbl_lista_basica.type
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
			$msg_erro = "<br>Selecione o Type: $result_type";
		}
	}

	// Retira palavra ERROR:
	if (strpos($msg_erro,"ERROR: ") !== false) {
		$msg_erro = substr($msg_erro, 6);
	}
	// retira CONTEXT:
	if (strpos($msg_erro,"CONTEXT:")) {
		$x = explode('CONTEXT:',$msg_erro);
		$msg_erro = $x[0];
	}
	echo $msg_erro;
	echo $msg_erro_type;
?>
	</td>
</tr>
</table>
<? } ?>

<form name="frm_os" id="frm_os" method="post" action="<? echo $PHP_SELF ?>" enctype="multipart/form-data">

<input type="hidden" name="os" value="<? echo $os; ?>">

<table border="0" cellpadding="2" cellspacing="0" align="center" width="700" CLASS='formulario'>
	<tr class='titulo_tabela'><td colspan='2'>OS Cortesia</td></tr>
	<tr class='subtitulo'><td colspan='2'>Dados do Posto</td></tr>
	<tr valign="top" align="left">
<? if (strlen($os) > 0) { ?>
		<td>
			<input type="hidden" name="sua_os" value="<? echo $sua_os; ?>">
			OS Fabricante
			<br>
			<input class="frm" type="text" name="sua_os" size="15" value="<? echo $posto_codigo.$sua_os; ?>" disabled>
		</td>
		</tr>
<? } ?>
		<tr valign="top" align="left">
		<td class='espaco' width='150'>
			Cod. Posto
			<br>
            <?php
            if ($login_fabrica == '1') {
                echo '<input type="hidden" id="confirmouPosto" name="confirmouPosto" value="f" />';
            }
            ?>
                <input type="hidden" name="posto" value="<?=$posto?>" />
                <input class="frm" type="text" name="posto_codigo" size="13" value="<? echo $posto_codigo ?>" > &nbsp; <img src="../imagens/lupa.png" border="0" style="cursor:pointer" align="absmiddle" onclick="javascript: fnc_pesquisa_posto ('',document.frm_os.posto_codigo,'')">
		</td>

		<td>
			Nome Posto
			<br>
			<input class="frm" type="text" name="posto_nome" size="40" value="<? echo $posto_nome ?>">&nbsp; <img src="../imagens/lupa.png" border="0" style="cursor:pointer" align="absmiddle" onclick="javascript: fnc_pesquisa_posto ('','',document.frm_os.posto_nome)">
		</td>
	</tr>
	<tr>
		<td class='espaco'>
			Data de Abertura
			<br>
			<input class="frm" type="text" name="data_abertura" size="15" value="<? if (strlen($data_abertura) == 0) $data_abertura = date("d/m/Y"); echo $data_abertura; ?>" <?if($login_fabrica<>1)echo"readonly";?>><br>
		</td>
		<td>
			Tipo OS Cortesia
			<br>
			<select name='tipo_os_cortesia' class="frm">
				<? if(strlen($tipo_os_cortesia) == 0) echo "<option value=''></option>"; ?>
				<option value='Garantia' <? if($tipo_os_cortesia == 'Garantia') echo "selected"; ?>>Garantia</option>
				<option value='Sem Nota Fiscal' <? if($tipo_os_cortesia == 'Sem Nota Fiscal') echo "selected"; ?>>Sem Nota Fiscal</option>
				<option value='Fora da Garantia' <? if($tipo_os_cortesia == 'Fora da Garantia') echo "selected"; ?>>Fora da Garantia</option>
<? if($login_admin == 155) { ?><option value='Transformação' <? if($tipo_os_cortesia == 'Transformação') echo "selected"; ?>>Transformação</option><? } ?>
<? if(in_array($login_admin,array(155,756,5087))) { ?><option value='Promotor' <? if($tipo_os_cortesia == 'Promotor') echo "selected"; ?>>Promotor</option><? } ?>
				<option value='Mau uso' <? if($tipo_os_cortesia == 'Mau uso') echo "selected"; ?>>Mau uso</option>
				<option value='Devolução de valor' <? if($tipo_os_cortesia == 'Devolução de valor') echo "selected"; ?>>Devolução de valor</option>
			</select>
			<br>
		</td>
		<input type="hidden" name="consumidor_revenda" value="C">
	</tr>
	<tr><td colspan='2'>&nbsp;</td></tr>
</table>

<table border="0" cellpadding="2" cellspacing="0" align="center" width="700" class='formulario'>
	<tr class='subtitulo'><td colspan='2'>Dados do Produto</td></tr>
	<tr valign="bottom" align="left">
		<td class='espaco'>
			<input type="hidden" name="os_produto" value="<? echo $os_produto; ?>">
			<input type="hidden" name="produto_descricao">
			Referência do Produto
			<br>
			<input class="frm" type="text" name="produto_referencia" size="15" maxlength="20" value="<? echo $produto_referencia ?>" id='produto_referencia'>
			&nbsp;
			<img src='imagens/lupa.png' style="cursor:pointer" border='0' alt="Clique para pesquisar pela referência do produto" align='absmiddle' onclick="javascript: fnc_pesquisa_produto ('',document.frm_os.produto_referencia,'',document.frm_os.posto)">
		</td>
		<td colspan='3'>
			Descrição Produto
			<br>
			<input class="frm" type="text" name="produto_nome"  id="produto_nome" size="40" maxlength="20" value="<? echo $produto_nome ?>" >
			&nbsp;
			<img src='imagens/lupa.png' style="cursor:pointer" border='0' alt="Clique para pesquisar pela referência do produto" align='absmiddle' onclick="javascript: fnc_pesquisa_produto (document.frm_os.produto_nome,'','',document.frm_os.posto)">
		</td>
	</tr>

	<tr>
		<td width='150' class='espaco'>
			Voltagem
			<br>
			<input class="frm" type="text" name="produto_voltagem" size="14" value="<? echo $produto_voltagem ?>">
		</td>
		<td>
			Tipo
			<br>
			<?
			 try{
			    GeraComboType::makeComboType($parametrosAdicionaisObject, $produto_type, "produto_type", array("class"=>"frm"));
			    echo GeraComboType::getElement();
			}catch(Exception $ex){

			    echo $ex->getMessage();
			}
			?>

		</td>
	</tr>
	<tr>
		<td width='150' class='espaco'>
			Nº de Série
			<br>
			<input class="frm" type="text" name="produto_serie" size="20" maxlength="20" value="<? echo $produto_serie ?>" id="produto_serie">
			<input class="frm" type="hidden" name="locacao_serie" value="" id="locacao_serie">
		</td>
		<td>
			Código fabricação
			<br>
			<input class="frm" type="text" name="codigo_fabricacao" size="20" maxlength="20" value="<? echo $codigo_fabricacao ?>" >
		</td>
	</tr>
	<tr><td colspan='2'>&nbsp;</td></tr>
</table>

<table border="0" cellpadding="2" cellspacing="0" align="center" width="700" class='formulario'>
	<tr class='subtitulo'><td colspan='3'>Dados do Consumidor</td></tr>
	<tr valign="top" align="left">
		<td width='150' class='espaco'>
			CPF/CNPJ Consumidor
			<br>
			<input class="frm" type="text" name="consumidor_cpf" id="consumidor_cpf" size="17" maxlength="18" value="<? echo $consumidor_cpf ?>">
		</td>
		<td colspan='2'>
			Nome Consumidor
			<br>
			<input class="frm" type="text" name="consumidor_nome" size="40" maxlength="50" value="<? echo $consumidor_nome ?>" onkeyup="somenteMaiusculaSemAcento(this)">
		</td>

	</tr>
	<tr>
		<td width='150' class='espaco'>
			Fone
			<br>
			<input class="frm" type="text" name="consumidor_fone" rel='fone' size="15" maxlength="20" value="<? echo $consumidor_fone ?>">
		</td>
		<td>
			Estado
			<br>
			<select id="consumidor_estado" name="consumidor_estado" class="frm addressState">
                <option value="" >Selecione</option>
                <?php
                #O $array_estados() está no arquivo funcoes.php
                foreach ($array_estados() as $sigla => $nome_estado) {
                    $selected = ($sigla == $consumidor_estado) ? "selected" : "";

                    echo "<option value='{$sigla}' {$selected} >{$nome_estado}</option>";
                }
                ?>
            </select>
			<!--
			<select name="consumidor_estado" size="1" class="frm">
				<option value=''>UF</option> -->
				<?

				/*
				$estados = array("AC","AL","AM","AP","BA","CE","DF","ES","GO","MA","MG","MS","MT","PA","PB","PE","PI","PR","RJ","RN","RO","RR","RS","SC","SE","SP","TO");
				for ($i = 0 ; $i < count($estados) ; $i++) {
					echo "<option value='".$estados[$i]."'";
					if ($consumidor_estado == $estados[$i]) echo " selected";
					echo ">";
					echo $estados[$i];
					echo "</option>";
				}*/
				?>
			<!-- </select> -->
		</td>
		<td align="left">
			Cidade
			<br>
			<select id="consumidor_cidade" name="consumidor_cidade" class="frm addressCity" style="width:200px">
                <option value="" >Selecione</option>
                <?php
                    if (strlen($consumidor_estado) > 0) {
                        $sql = "SELECT DISTINCT * FROM (
                                SELECT UPPER(fn_retira_especiais(nome)) AS cidade FROM tbl_cidade WHERE UPPER(estado) = UPPER('".$consumidor_estado."')
                                    UNION (
                                        SELECT UPPER(fn_retira_especiais(cidade)) AS cidade FROM tbl_ibge WHERE UPPER(estado) = UPPER('".$consumidor_estado."')
                                    )
                                ) AS cidade
                                ORDER BY cidade ASC";
                        $res = pg_query($con, $sql);

                        if (pg_num_rows($res) > 0) {
                            while ($result = pg_fetch_object($res)) {
                                $selected  = (trim($result->cidade) == $consumidor_cidade) ? "SELECTED" : "";

                                echo "<option value='{$result->cidade}' {$selected} >{$result->cidade} </option>";
                            }
                        }
                    }
                ?>
            </select>

			<!--
			<input class="frm" type="text" name="consumidor_cidade" id="consumidor_cidade"  size="25" maxlength="50" value="<? echo $consumidor_cidade ?>"> -->
		</td>
	</tr>
	<tr>

	<? if($login_fabrica == 1){?>
	<td width='150' class='espaco'>
		Tipo Consumidor
		<br>
		<SELECT NAME="fisica_juridica" class='frm'>
			<OPTION></OPTION>
			<OPTION VALUE="F" <? if($fisica_juridica=="F") echo "selected"; ?>>Pessoa Física</OPTION>
			<OPTION VALUE="J" <? if($fisica_juridica=="J") echo "selected"; ?>>Pessoa Jurídica</OPTION>
		</SELECT>
	</td>
	<?}?>

	<?php if($login_fabrica == 1){?>
			<td valign='top' align='left' colspan='2'>
	<?php }
		  else{ ?>
			<td valign='top' align='left' colspan='3' class='espaco'>
	<?php } ?>
		E-mail
		<br>
		<INPUT TYPE='text' name='consumidor_email' class='frm' value="<? echo "$consumidor_email"; ?>" size='40' maxlength='50'>
	</td>
	</tr>
	<tr><td colspan='3'>&nbsp;</td></tr>
</table>

<table border="0" cellpadding="2" cellspacing="0" align="center" width="700" class='formulario'>
	<tr class='subtitulo'><td colspan='2'>Dados da Revenda</td></tr>
	<tr valign="top" align="left">
		<td width='150' class='espaco'>
			CNPJ Revenda
			<br>
			<input class="frm" type="text" name="revenda_cnpj" id="revenda_cnpj" size="17" maxlength="18" value="<? echo $revenda_cnpj; ?>" onKeyUp="formata_cnpj(this.value,'frm_os');">&nbsp;<IMG src="imagens/lupa.png" style="cursor:pointer " align='absmiddle' alt="Clique aqui para pesquisar revendas pelo código" onclick="fnc_pesquisa_revenda ($('input[name=revenda_cnpj]').val(),'cnpj')" >
		</td>
		<td>
			Nome Revenda
			<br>
			<input class="frm" type="text" name="revenda_nome" size="40" maxlength="50" value="<? echo $revenda_nome; ?>" onkeyup="somenteMaiusculaSemAcento(this)">&nbsp;
			<IMG src="imagens/lupa.png" style="cursor:pointer " align='absmiddle' alt="Clique aqui para pesquisar pelo nome da revenda." onclick="fnc_pesquisa_revenda ($('input[name=revenda_nome]').val(),'nome')">

		</td>

	</tr>
</table>

<? # HD 30250 - os campos acima, estavam iniciando como desabilitados
	#<!--<?if(strlen($os)>0) echo "disabled"
	#<!--<?if(strlen($os)>0) echo "disabled" >-->
?>

<table border="0" cellpadding="2" cellspacing="0" align="center" width="700" class='formulario'>
	<tr valign="top" align="left">
		<td width="150" class='espaco'>
			Nota Fiscal
			<br>
			<input class="frm" type="text" id="nota_fiscal" name="nota_fiscal" size="15" maxlength="20" value="<? echo $nota_fiscal ?>">
		</td>
		<td>
			Data Compra
			<br>
			<input class="frm" type="text" name="data_nf" size="12" maxlength="10" value="<? echo $data_nf ?>"><br><font face='arial' size='1'>
		</td>
		<? if ($login_fabrica <> 1) {# B&D : troca somente no cadastro de TROCA?>
		<td>
			Troca faturada
			<br>
			<input class="frm" type="checkbox" name="troca_faturada" value="t" <? if ($troca_faturada == 't') echo "checked";?>>
		</td>
		<td>
			Troca garantia
			<br>
			<input class="frm" type="checkbox" name="troca_garantia" value="t" <? if ($troca_garantia == 't') echo "checked";?>>
		</td>
		<? } ?>
	</tr>
	<tr><td colspan='2'>&nbsp;</td></tr>
</table>

<table border="0" cellpadding="2" cellspacing="0" align="center" width="700" class='formulario' id="input_anexos">

	<tr valign="top" align="left">
		<td class='espaco'>
			Observações
			<br>
			<textarea name="obs" rows="5" cols="60" class="frm"><? echo $obs; ?></textarea>
		</td>
	</tr>
	<?php if ($login_fabrica == 1) : ?>
		<tr>
			<td align='center' style="padding-left:15px;">
				<label for="foto_nf">Anexar NF</label>
                <input type="file" name="foto_nf[0]" id="foto_nf" />
                <input type="hidden" id="qtde_anexos" name="qtde_anexos" value="1" />
			</td>
		</tr>
        <tr id="anexoTpl" style="display: none;">
            <td align='center' style="padding-left:15px;">
				<label for="foto_nf">Anexar NF</label>
				<input type="file" name="foto_nf[@ID@]" />
			</td>
        </tr>
</table>

<div class="formulario" style="text-align: center;"><input value="Adicionar novo arquivo" onclick="addAnexoUpload()" type="button"></div>

<table border="0" cellpadding="2" cellspacing="0" align="center" width="700" class='formulario'>
	<?php endif; ?>
	<tr>
		<td style='padding:20px 0 20px 0;' align='center'>
			<input type="hidden" name="btn_acao" value="">
			<input type='button'
                   value='Gravar'
                   border="0"
                   rel='sem_submit'
                   class='verifica_servidor'
                   onclick="if (document.frm_os.btn_acao.value == '') { verificaSerie(); } else { alert('Aguarde submissão'); }"
                   id='btn_img'
                   ALT="Gravar"
                   style="cursor:pointer;">
		</td>
	</tr>


</table>

</form>
<script language='javascript' src='address_components.js'></script>

<? include "rodape.php";?>
