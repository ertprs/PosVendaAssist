<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="call_center";
include 'autentica_admin.php';
include 'funcoes.php';

if ($login_fabrica == '1') {
    $limite_anexos_nf = 5;
}
include '../anexaNF_inc.php';
if($login_fabrica == 1){
    require "../classes/ParametrosAdicionaisFabrica.php";
    $parametrosAdicionaisObject = new ParametrosAdicionaisFabrica($login_fabrica);

    require "../classes/form/GeraComboType.php";
}
# Pesquisa pelo AutoComplete AJAX
$q = strtolower($_GET["q"]);
if (isset($_GET["q"])){
	$tipo_busca = $_GET["busca"];
	$busca_tipo = $_GET['busca_tipo'];
	if (strlen($q)>2){
		if ($busca_tipo=="posto"){
            $sql = "SELECT  tbl_posto.cnpj,
                            tbl_posto.nome,
                            tbl_posto_fabrica.codigo_posto,
                            tbl_posto.posto
                    FROM    tbl_posto
                    JOIN    tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
                    WHERE   tbl_posto_fabrica.fabrica = $login_fabrica ";

            if ($tipo_busca == "codigo"){
                $sql .= " AND tbl_posto_fabrica.codigo_posto like('%$q%') ";
            }else{
                $sql .= " AND UPPER(tbl_posto.nome) like UPPER('%$q%') ";
            }
 
            $res = pg_query($con,$sql);
            if (pg_num_rows ($res) > 0) {
                for ($i=0; $i<pg_num_rows ($res); $i++ ){
                    $posto          = trim(pg_fetch_result($res,$i,posto));
                    $cnpj           = trim(pg_fetch_result($res,$i,cnpj));
                    $nome           = trim(pg_fetch_result($res,$i,nome));
                    $codigo_posto   = trim(pg_fetch_result($res,$i,codigo_posto));
                    echo "$codigo_posto|$nome|$posto";
                    echo "\n";
                }
            }
		}
	}
	exit;
}	

if ($_POST["verifica_garantia_origem"]) {
	$produto_referencia_origem = $_POST["produto_referencia_origem"];
    $data_nf = formata_data($_POST["data_nf"]);

    $sql = "SELECT tbl_produto.produto
			FROM   tbl_produto
			JOIN   tbl_linha USING (linha)
			WHERE  tbl_linha.fabrica = $login_fabrica 
			AND    (UPPER(tbl_produto.referencia_fabrica) = UPPER('$produto_referencia_origem') OR UPPER(tbl_produto.referencia) = UPPER('$produto_referencia_origem'))
			AND    tbl_produto.ativo IS TRUE";
	$res = pg_query($con,$sql);

	if (pg_num_rows($res) == 0) {
		echo "Produto $produto_referencia_origem não cadastrado";
		exit();
	} else {
		$produto = pg_fetch_result($res,0,'produto');
	
		$sql = "SELECT garantia FROM tbl_produto WHERE tbl_produto.produto = $produto";
		$res = pg_query($con,$sql);

		if (pg_num_rows($res) == 0) {
			echo "Produto $produto_referencia_origem sem garantia";
			exit();
		} else {
			$garantia = trim(pg_fetch_result($res,0,'garantia'));

			$sql = "SELECT ('$data_nf'::date + (($garantia || ' months')::interval))::date;";
			$res = pg_query($con,$sql);
			if (strlen(pg_last_error()) > 0) {
				echo "Data da nota inválida";
				exit();
			}

			if (pg_num_rows($res) > 0) {
				$data_final_garantia = trim(pg_fetch_result($res,0,0));

				$dt_hj = Date('Y-m-d');

				if (strtotime($data_final_garantia) < strtotime($dt_hj)) {
					echo "Produto $produto_referencia_origem está fora do prazo de garantia";
					exit();
				} else {
					echo "ok";
					exit();
				}
			}
		}
	}
	exit();
}

if($_POST['verifica_descontinuado'] == true) {

	$produto_referencia = $_POST['produto_referencia'];
	$data_abertura = formata_data($_POST['data_abertura']);
	$limite_data = 1095;
	
	$sql = "SELECT parametros_adicionais from tbl_produto WHERE referencia = '$produto_referencia' and fabrica_i = $login_fabrica ";
	$res = pg_query($con, $sql);

	if(strlen(pg_last_error($con)>0)){
		echo json_encode(array('erro' => true));
	}

	if(pg_num_rows($res)>0){
		$parametros_adicionais 	= json_decode(pg_fetch_result($res, 0, 'parametros_adicionais'),true);
		$data_descontinuado 	= formata_data($parametros_adicionais['data_descontinuado']);

		$dt_descontinuado 	= new DateTime($data_descontinuado);
		$dt_abertura 		= new DateTime($data_abertura);
		$diferenca 			= $dt_descontinuado->diff($dt_abertura);

		$diferenca_anos = $diferenca->days;

		if($diferenca_anos > $limite_data ){
			echo json_encode(array('motivo' => true ));
		}else{
			echo json_encode(array('gravar_ok' => true ));
		}
	}
	exit;
}


if(isset($_POST['verifica_bo']) && $_POST['verifica_bo'] == "ok"){

	$bo = $_POST['bo'];

	$sql = "SELECT  advertencia
			FROM    tbl_advertencia
			WHERE   fabrica         = $login_fabrica
            AND     advertencia      = '$bo'
            AND     tipo_ocorrencia IS NOT NULL";
	$res = pg_query($con, $sql);

	echo (pg_num_rows($res) > 0) ? "<strong style='color: green;'>Número de B.O. Correto</strong>" : "<strong style='color: #ff0000;'>Número de B.O. inválido</strong>";

	exit;

}

if (strlen($_POST['qtde_item']) > 0) $qtde_item = $_POST['qtde_item'];

if (strlen($_POST['qtde_linhas']) > 0) $qtde_item = $_POST['qtde_linhas'];

$btn_acao = trim(strtolower($_POST['btn_acao']));

if (strlen($_GET['os_revenda']) > 0)  $os_revenda = trim($_GET['os_revenda']);
if (strlen($_POST['os_revenda']) > 0) $os_revenda = trim($_POST['os_revenda']);


if(strlen($os_revenda)>0){
	$sql="SELECT count(*) as qtde_item from tbl_os_revenda_item where os_revenda=$os_revenda";
	$res=pg_query($con,$sql);
	$qtde_item=pg_fetch_result($res,0,qtde_item);
}

/* ====================  APAGAR  =================== */
if ($btn_acao == "apagar") {
	if(strlen($os_revenda) > 0){
		$sql = "DELETE FROM tbl_os_revenda
				WHERE  tbl_os_revenda.os_revenda = $os_revenda
				AND    tbl_os_revenda.fabrica    = $login_fabrica";

		$res = pg_query ($con,$sql);

		$msg_erro = pg_errormessage($con);
		$msg_erro = substr($msg_erro,6);

		if (strlen ($msg_erro) == 0) {
			header("Location: $PHP_SELF");
			exit;
		}
	}
}


if ($btn_acao == "gravar"){

	if ($login_fabrica == 1 && $_POST['reverter_produto'] == "sim") {
		$prod_o_ref = $_POST['produto_origem_referencia_item'];
		$data_nf_g = fnc_formata_data_pg($_POST['data_nf']);

		$sql = "SELECT tbl_produto.produto
			FROM   tbl_produto
			JOIN   tbl_linha USING (linha)
			WHERE  tbl_linha.fabrica = $login_fabrica 
			AND    (UPPER(tbl_produto.referencia_fabrica) = UPPER('$prod_o_ref') OR UPPER(tbl_produto.referencia) = UPPER('$prod_o_ref'))
			AND    tbl_produto.ativo IS TRUE";
		$res = pg_query($con,$sql);

		if (pg_num_rows($res) == 0) {
			$msg_erro .=  "Produto $prod_o_ref não cadastrado <br>";
		} else {
			$produto_g = pg_fetch_result($res,0,'produto');
		
			$sql = "SELECT garantia FROM tbl_produto WHERE tbl_produto.produto = $produto_g";
			$res = pg_query($con,$sql);

			if (pg_num_rows($res) == 0) {
				$msg_erro .= "Produto $prod_o_ref sem garantia <br>";
				
			} else {
				$garantia_g = trim(pg_fetch_result($res,0,'garantia'));

				$sql = "SELECT ($data_nf_g::date + (($garantia_g || ' months')::interval))::date;";				
				$res = pg_query($con,$sql);
				if (strlen(pg_last_error()) > 0) {
					$msg_erro .= "Data da nota inválida <br>";
				}

				if (pg_num_rows($res) > 0) {
					$data_final_garantia_g = trim(pg_fetch_result($res,0,0));

					$dt_hj_g = Date('Y-m-d');

					if (strtotime($data_final_garantia_g) < strtotime($dt_hj_g)) {
						$msg_erro .= "Produto $prod_o_ref está fora do prazo de garantia <br>";
					}
				}
			}
		}
	}

	$motivo_descontinuado = pg_escape_string(utf8_encode($_POST['motivo_descontinuado']));

	$produto_origem 	= (mb_check_encoding($_POST["produto_origem"], "UTF-8")) ? $_POST["produto_origem"] : utf8_encode($_POST["produto_origem"]);
	$reverter_produto 	= $_POST["reverter_produto"];

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

	$posto_codigo=$_POST['posto_codigo'];
	if(strlen($posto_codigo > 0)){
		$sql="SELECT posto
				FROM tbl_posto_fabrica
				WHERE codigo_posto='$posto_codigo'
				AND fabrica=$login_fabrica";
		$res=pg_query($con,$sql);
		if(pg_num_rows($res)>0){
			$posto=pg_fetch_result($res,0,posto);
		}else{
			$msg_erro="Posto $posto_codigo não Encontrado";
		}
	}else{
		$msg_erro="Digite o Código do Posto.";
	}

    if ($login_fabrica == '1') {
        $sqlPostoCred = "SELECT * FROM tbl_posto_fabrica
            WHERE posto = $posto AND fabrica = $login_fabrica AND credenciamento = 'DESCREDENCIADO'";
        $qryPostoCred = pg_query($con, $sqlPostoCred);

        if (pg_num_rows($qryPostoCred) > 0) {
            $msg_erro = 'Posto informado encontra-se DESCREDENCIADO';
        }
    }

	$nota_fiscal = $_POST["nota_fiscal"];
	if (strlen($nota_fiscal) == 0) {
		$xnota_fiscal = 'null';
	}else{
		$nota_fiscal = trim ($nota_fiscal);
		$nota_fiscal = str_replace (".","",$nota_fiscal);
		$nota_fiscal = str_replace (" ","",$nota_fiscal);
		$nota_fiscal = str_replace ("-","",$nota_fiscal);
		// $nota_fiscal = "000000" . $nota_fiscal;
		//$nota_fiscal = substr ($nota_fiscal,strlen($nota_fiscal)-14,14);

		if(strlen($nota_fiscal) > 20){
			$msg_erro = "O número da Nota Fiscal não pode ser maior que 20 caracteres.";
		}else{
			$xnota_fiscal = "'" . $nota_fiscal . "'" ;
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

	$tipo_atendimento = $_POST['tipo_atendimento'];
// 	if (strlen (trim ($tipo_atendimento)) == 0) $msg_erro = " Escolha o Tipo de Atendimento<br />";
	// if($tipo_atendimento <> 17){
	// 	if($xnota_fiscal <> 'null'){
	// 		$msg_erro = "Para troca faturada ou troca em cortesia não é necessário digitar a Nota Fiscal. <br />";
	// 	}else{
	// 		$xnota_fiscal = 'null';
	// 	}
	// 	if(strlen ($_POST['data_nf']) > 0 ){
	// 		$msg_erro = "Para troca faturada ou troca em cortesia não é necessário digitar a Data da Nota Fiscal. <br />";
	// 	}else{
	// 		$xdata_nf = 'null';
	// 	}


	// }


	if ($xrevenda_cnpj <> "null") {
		$sql =	"SELECT *
				FROM    tbl_revenda
				WHERE   cnpj = $xrevenda_cnpj";
		$res = pg_query($con,$sql);

		if (pg_num_rows($res) == 0){
			$msg_erro = "CNPJ da Revenda não Cadastrado";
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

		}
	}else{
		$msg_erro = "CNPJ não informado";
	}

	if (strlen($_POST['revenda_fone']) > 0) {
		$xrevenda_fone = "'". $_POST['revenda_fone'] ."'";
	}else{
		$xrevenda_fone = "null";
	}
	if($xrevenda_fone == "null"){$msg_erro ="Insira o Telefone da Revenda.<BR>";}

	if (strlen($_POST['revenda_email']) > 0) {
		$xrevenda_email = "'". $_POST['revenda_email'] ."'";
	}else{
		// HD 20281
		$msg_erro ="Digite o E-mail da Revenda <br>";
	}



	if(strlen($xrevenda_email) >0 and strlen($revenda) >0){
		$sql="UPDATE tbl_revenda set email=$xrevenda_email where revenda=$revenda";
		$res=pg_query($con,$sql);
	}

	if (strlen($_POST['obs']) > 0) {
		$xobs = "'". $_POST['obs'] ."'";
	}else{
		$xobs = "null";
	}

	if (strlen($_POST['contrato']) > 0) {
		$xcontrato = "'". $_POST['contrato'] ."'";
	}else{
		$xcontrato = "'f'";
	}

	$admin_autoriza= trim ($_POST['admin_autoriza']) ;
	$causa_troca   = trim ($_POST['causa_troca']) ;
	$multi_peca    = ($_POST['multi_peca']) ;
	$obs_causa     = trim ($_POST['obs_causa']) ;
	$falta_cidade  = trim ($_POST['falta_cidade']) ;
	$falta_estado  = trim ($_POST['falta_estado']) ;
	$produto_troca_garantia_faturada  = trim ($_POST['produto_troca_garantia_faturada']) ;
	$produto_origem_referencia_item  = trim ($_POST['produto_origem_referencia_item']) ;

	if($login_fabrica == 1){

		if($causa_troca == 380 and empty($multi_peca)){
        	$msg_erro .= "Por favor digite as Peças <br />";
        }

		if($causa_troca == 316 && strlen($obs_causa) == 0 && $produto_troca_garantia_faturada == "sim"){
			$msg_erro .= "Por favor digite a Justificativa <br />";
		}

		if($causa_troca == 316 && $produto_troca_garantia_faturada <> "sim"){
			$msg_erro .= "Motivo de troca inválido para este produto <br />";
		}

		if($causa_troca == 312 && count($multi_peca) == 0){
			$msg_erro .= "Por favor digite as Peças <br />";
		}

		if($causa_troca == 317 && strlen($obs_causa) == 0){
			$msg_erro .= "Por favor digite a Justificativa <br />";
		}

		if($causa_troca == 237 && strlen($produto_origem_referencia_item) == 0){
			$msg_erro .= "Por favor informar produto de origem <br />";
		}
	}

	if(empty($admin_autoriza)) {
		$msg_erro = "Selecione o Admin que Autoriza";
	}


	if(empty($causa_troca) && $login_fabrica != 1) {
		$msg_erro = "Selecione o Motivo da Troca";
	}else{
        $causa_troca = "null";
	}

	if($login_fabrica != 1){
        if($causa_troca == 124) {
            $xobs_causa = "'".$pecas."'";
        }elseif($causa_troca ==130){
            $xobs_causa="'Cidade: ".$falta_cidade."<br>Estado: $falta_estado '";

            if(empty($falta_cidade) or empty($falta_estado)) {
                $msg_erro ="Por favor, informe a cidade e o Estado que falta<br/>";
            }else{
                $sql = "SELECT cidade FROM tbl_cidade WHERE UPPER(fn_retira_especiais(nome)) = UPPER(fn_retira_especiais('{$falta_cidade}')) AND UPPER(estado) = UPPER('{$falta_estado}')";
                $res = pg_query($con, $sql);

                if (pg_num_rows($res) > 0) {
                    $cod_cidade = pg_fetch_result($res, 0, "cidade");
                } else {
                    $sql = "SELECT cidade, estado FROM tbl_ibge WHERE UPPER(fn_retira_especiais(cidade)) = UPPER(fn_retira_especiais('{$falta_cidade}')) AND UPPER(estado) = UPPER('{$falta_estado}')";
                    $res = pg_query($con, $sql);

                    if (pg_num_rows($res) > 0) {
                        $cidade_ibge        = pg_fetch_result($res, 0, "cidade");
                        $cidade_estado_ibge = pg_fetch_result($res, 0, "estado");

                        $sql = "INSERT INTO tbl_cidade (
                                    nome, estado
                                ) VALUES (
                                    '{$cidade_ibge}', '{$cidade_estado_ibge}'
                                )";
                        $res = pg_query($con, $sql);
                    } else {
                        $msg_erro .= "Cidade não encontrada";
                    }
                }
            }

        }elseif(in_array($causa_troca,array(125,128,131,318,316,317))){
            $xobs_causa = "'".$obs_causa."'";
            if(empty($obs_causa)) {
                $msg_erro ="Por favor, informe a justificativa para esse motivo de troca<br/>";
            }
        }else{
            $xobs_causa = "'".$obs_causa."'";
        }
    }else{
        $xobs_causa = "null";
    }

	if ($login_fabrica == 1 && !empty($_POST['pedido_item'])) {
		$num_pedido = trim($_POST["pedido_item"]);

		$sqlPedido = "SELECT pedido FROM tbl_pedido where fabrica = $login_fabrica and posto = $posto  AND ( substr(tbl_pedido.seu_pedido,4) = '$num_pedido' OR tbl_pedido.seu_pedido = '$num_pedido')";
		$resPedido = pg_query($con, $sqlPedido);
		if(pg_num_rows($resPedido)==0){
			$msg_erro .= "Pedido inválido.<br>";
		}else{
			$id_pedido = pg_fetch_result($resPedido, 0, pedido);
		}

		$campo_pedido = 'pedido_cliente, ';
		$xpedido_item = $id_pedido.',';
		$campo_updade = 'pedido_cliente = '.$xpedido_item;
	} else {
		$campo_pedido = '';
		$xpedido_item = '';
		$campo_updade = '';
	}   

	if(!empty($motivo_descontinuado)){
		$valor_adicional_justificativa = json_encode(array('motivo_descontinuado' =>  $motivo_descontinuado, 'produto_descontinuado' => true));

		$campo_valor_adicional = " valor_adicional_justificativa, ";
		$value_valor_adicional = " '$valor_adicional_justificativa', ";
		$update_valor_adicional = " valor_adicional_justificativa = '$valor_adicional_justificativa',  ";
	}

	if($reverter_produto == 'sim'){
		$campos_extra['produto_origem'] = $_POST['produto_origem_referencia_item'];
		$campos_extra['produto_origem_descricao'] = (mb_check_encoding($_POST['produto_origem_descricao_item'], "UTF-8")) ? $_POST['produto_origem_descricao_item'] : utf8_encode($_POST['produto_origem_descricao_item']);
		$campos_extra['reverter_produto'] = $reverter_produto;
		$campos_extra = json_encode($campos_extra);
		$campos_extra = str_replace("\u", "\\\u", $campos_extra);
	}

	if(strlen(trim($campos_extra))==0){
		$campos_extra = 'null';
	}

	if (strlen ($msg_erro) == 0) {

		$res = pg_query ($con,"BEGIN TRANSACTION");

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
						digitacao                         ,
						posto                             ,
						contrato                          ,
						admin                             ,
						admin_autoriza                    ,
						$campo_pedido
						$campo_valor_adicional
						causa_troca                       ,
						campos_extra 					  ,
						obs_causa
					) VALUES (
						$login_fabrica                    ,
						$xsua_os                          ,
						$xdata_abertura                   ,
						$xdata_nf                         ,
						$xnota_fiscal                     ,
						$revenda                          ,
						$xobs                             ,
						current_timestamp                 ,
						$posto                            ,
						$xcontrato                        ,
						$login_admin                      ,
						$admin_autoriza                   ,
						$xpedido_item
						$value_valor_adicional
						$causa_troca                      ,
						'$campos_extra'					  , 
						$xobs_causa
					)";
		}else{
			$sql = "UPDATE tbl_os_revenda SET
						data_abertura    = $xdata_abertura                  ,
						data_nf          = $xdata_nf                        ,
						nota_fiscal      = $xnota_fiscal                    ,
						revenda          = $revenda                         ,
						obs              = $xobs                            ,
						contrato         = $xcontrato                       ,
						$update_valor_adicional 
						obs_causa 		 = $xobs_causa                		,
						$campo_updade
						admin            = $login_admin
					WHERE os_revenda  = $os_revenda
					AND	 posto        = $posto
					AND	 fabrica      = $login_fabrica ";
		}

		$res = @pg_query ($con,$sql);
		$msg_erro = pg_errormessage($con);

		if (strlen($msg_erro) == 0 and strlen($os_revenda) == 0) {
			$res        = pg_query ($con,"SELECT CURRVAL ('seq_os_revenda')");
			$os_revenda = pg_fetch_result ($res,0,0);
			$msg_erro   = pg_errormessage($con);

			if (strlen ($msg_erro) > 0) {
				$sql = "UPDATE tbl_cliente SET contrato = $xcontrato
						WHERE  tbl_cliente.cliente  = $revenda";
				$res = pg_query ($con,$sql);
				$msg_erro = pg_errormessage($con);
			}
			if (strlen ($msg_erro) > 0) {
				exit ;
				$linha_erro = '';
			}
		}

		//HD 9013 21517 64474
		if(strlen($os_revenda)>0){
			$sql="SELECT tbl_os_revenda.sua_os,
						 tbl_posto_fabrica.codigo_posto
						 FROM tbl_os_revenda
						 	JOIN tbl_os_revenda_item ON tbl_os_revenda_item.os_revenda = tbl_os_revenda.os_revenda
							JOIN tbl_posto_fabrica on tbl_os_revenda.posto= tbl_posto_fabrica.posto and tbl_posto_fabrica.fabrica=$login_fabrica
							WHERE tbl_os_revenda.nota_fiscal::float = (
										SELECT nota_fiscal::float
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
							AND tbl_os_revenda.os_revenda <>$os_revenda
							AND tbl_os_revenda_item.tipo_atendimento IS NOT NULL
							AND tbl_os_revenda.excluida IS NOT TRUE
							AND tbl_posto_fabrica.posto   = $posto
							AND tbl_os_revenda.fabrica    = $login_fabrica";
					$res=pg_query($con,$sql);
					if(pg_num_rows($res)>0){
						$sua_os       = pg_fetch_result($res,0,sua_os);
						$codigo_posto = pg_fetch_result($res,0,codigo_posto);
						$msg_erro="Nota fiscal já foi informada na OS $codigo_posto$sua_os. O sistema permite a digitação de apenas uma OS de revenda para cada nota fiscal, pois é possível incluir na mesma OS a quantidade total de produtos que serão atendidos em garantia.";
			}
		}

		if (strlen($msg_erro) == 0) {
			if(strlen($qtde_item) == 0) {
				$qtde_item = $_POST['total_linhas'];
			}

			$sql = "DELETE FROM tbl_os_revenda_item WHERE  os_revenda = $os_revenda";
			$res = @pg_query($con,$sql);
			$msg_erro = pg_errormessage($con);
			$conta_qtde=0;
			for ($i = 0 ; $i < $qtde_item ; $i++) {
				$referencia                     = trim($_POST["produto_referencia_".$i]);
				$codigo_fabricacao              = trim($_POST["codigo_fabricacao_".$i]);
				$serie                          = trim($_POST["produto_serie_".$i]);
				$capacidade                     = trim($_POST["produto_capacidade_".$i]);
				$voltagem                       = trim($_POST["produto_voltagem_".$i]);
				$type                           = trim($_POST["type_".$i]);
				$embalagem_original             = trim($_POST["embalagem_original_".$i]);
				$sinal_de_uso                   = trim($_POST["sinal_de_uso_".$i]);
                $defeito_constatado_descricao   = trim($_POST["defeito_constatado_descricao_".$i]);
                $causa_troca_item               = trim($_POST["causa_troca_item_".$i]);
                $obs_causa_item                 = $_POST["obs_causa_item_".$i];
				$tipo_atendimento_item          = $_POST["tipo_atendimento_item_".$i];
				$kit_produto          		    = $_POST["kit_produto_".$i];

                $sql_prateleira_box = array(
                    "insert" => array(
                        "campo" => "",
                        "valor" => ""
                    ),
                    "update" => ""
                );

                if ($login_fabrica == '1') {
                    $nao_anexou_nf = false;

                    foreach (range(0, 4) as $idx) {
                        if (!empty($_FILES["foto_nf"]["size"][$idx][0])) {
                            break;
                        }

                        $nao_anexou_nf = true;
                    }

                    if (!empty($_POST["prateleira_box_{$i}"]) and $causa_troca_item == 124) {
                        $prateleira_box = $_POST["prateleira_box_{$i}"];

                        $wlist = array('obsoleto', 'impinat', 'indispl', 'cabo_eletrico','estoque', 'nao_cadastrada');
                        if (in_array($prateleira_box, $wlist)) {
                        	
                        	$prateleira_box = substr($prateleira_box, 0, 10);

                            $sql_prateleira_box["insert"] = array(
                                "campo" => ", rg_produto",
                                "valor" => ", '$prateleira_box'"
                            );
                            $sql_prateleira_box["update"] = ", rg_produto = '$prateleira_box'";
                        }
                    }
                }

				if (!isset($_POST["TDOrdemDeServicoSemNF"]) && (!strlen(trim($_POST["nota_fiscal"])) || $nao_anexou_nf) && $login_fabrica == 1 && $tipo_atendimento_item == 18) {
					$msg_erro .= " Nota fiscal Obrigatoria ";
				}

				//HD 244476
				$produto_troca				   = trim($_POST["produto_troca_".$i]);
				if (strlen(trim($_POST['produto_referencia_troca_'.$i])) == 0) $msg_erro = 'Informe o produto para troca.';
				else             $referencia_troca = "'".trim($_POST['produto_referencia_troca_'.$i])."'";
				if (strlen(trim($_POST['produto_voltagem_troca_'.$i])) == 0) {
                    $msg_erro = 'Informe a voltagem do produto para troca. Caso esteja em branco clique na lupa para pesquisar o produto a ser trocado.';
					$linha_erro = $i;
				}else             $voltagem_troca = trim($_POST['produto_voltagem_troca_'.$i]);

                if (strlen($type) == 0)
                    $type = "null";
                else
                    $type = "'". $type ."'";

				if (strlen($tipo_atendimento_item) == 0){
                    if($login_fabrica == 1){
                        $msg_erro = 'Informe o tipo de atendimento.';
                    }else{
                        $tipo_atendimento_item = "";
                    }
                }

				if (strlen($causa_troca_item) == 0){
					$causa_troca_item = $causa_troca;
						if (strlen($causa_troca_item) == 0){
							$msg_erro = "FAVOR INFORMAR O MOTIVO DA TROCA";
						}
                }

				if (strlen($obs_causa_item) == 0){
					$msg_erro = "Favor informar a justificativa";
				}else{

					if($reverter_produto == 'sim'){
						$produto_origem_arr = explode("|", $produto_origem);

						$sqlProduto = "SELECT produto FROM tbl_produto where fabrica_i = $login_fabrica and referencia = '".trim($produto_origem_arr[0])."' ";	

					}else{
						$sqlProduto = "SELECT produto FROM tbl_produto where fabrica_i = $login_fabrica and referencia = '$referencia' ";
					}					
					$resProduto = pg_query($con, $sqlProduto);
					if(pg_num_rows($resProduto)>0){
						$produto = pg_fetch_result($resProduto, 0, 'produto');
					}					

                    if(in_array($causa_troca_item,array(124,380)) && empty($msg_erro)) {
                        $aux_causa_item = $obs_causa_item;
                        $pecas_referencia = explode("|",$aux_causa_item);
                        $obs_causa_item = "";
                        if(count($pecas_referencia) > 0 and $prateleira_box != 'nao_cadast' and $prateleira_box != 'cabo_eletrico' and $prateleira_box != 'indispl') {
                        	if($reverter_produto == 'sim'){
			        			$produtoValidaListaBasica = $produto_origem_id; 
			        		}else{
			        			$produtoValidaListaBasica = $produto; 	
			        		}
                            for($x =0;$x<count($pecas_referencia);$x++) {

                                $sql = "SELECT  DISTINCT
                                                tbl_peca.referencia,
                                                tbl_peca.parametros_adicionais,
                                                tbl_peca.descricao
                                        FROM    tbl_lista_basica
                                        JOIN    tbl_peca USING(peca)
                                        WHERE   tbl_lista_basica.fabrica    = $login_fabrica
                                        AND 	tbl_lista_basica.produto = $produto
                                        AND     referencia                  = '".$pecas_referencia[$x]."'";
                                $res = pg_query($con,$sql);
                                if(pg_num_rows($res) > 0){
                                	if($prateleira_box == 'estoque'){
                                		$parametros_adicionais = pg_fetch_result($res, 0, 'parametros_adicionais');
                                		$parametros_adicionais = json_decode($parametros_adicionais, true);
                                		$previsao = $parametros_adicionais['previsao'];
                                		$previsao = mostra_data($previsao);
                                		$obs_causa_item .="<br>".pg_fetch_result($res,0,'referencia')." - ". pg_fetch_result($res,0,'descricao'). " Previsão: $previsao";
                                	}else{
                                		$obs_causa_item .="<br>".pg_fetch_result($res,0,referencia)." - ". pg_fetch_result($res,0,descricao);
                                	}                                     
                                }else{
                                    $msg_erro = $pecas_referencia[$x]." não encontrada no sistema ou não está na lista básica de produto <br>";
                                    $obs_causa_item = $aux_causa_item;
                                }
                            }
                            $obs_causa_item = "'".$obs_causa_item."'";
                        }
                        if($prateleira_box == 'indispl'){
                        	$obs_causa_item = $_POST["obs_causa_item_".$i];

                        	$obs_causa_item = explode("|", $obs_causa_item);

							$obs_indispl = "";
							foreach($obs_causa_item as $values){
								if(!empty($obs_indispl)){
									$obs_indispl .= "<br>";
								}
								$obs_indispl .= " Peça: ". $values;
							}
							$obs_causa_item = "'".$obs_indispl."'";
                        }elseif($prateleira_box == "nao_cadast"){
							if(count($pecas_referencia) > 0){
								$xcampos_posicao = $_POST["campos_posicao"];

								$campos_posicao = $xcampos_posicao;
								$campos_posicao = explode("|", $campos_posicao);
								foreach($campos_posicao as $values){
									$valores = explode("-", $values);
									if ($login_fabrica == 1) {
										foreach ($valores as $k => $v) {
											if (empty($v) || $v == " ") {
												unset($valores[$k]);
											}
										}
										$valores = array_values($valores);
										$obs_causa_item .= "Posição: " . $valores[0]. "- Peça: ". $valores[1] . "<Br> ";	
									} else {
										$obs_causa_item .= "Posição: " . $valores[0]. "- Peça: ". $valores[1]. " - ". $valores[2] . "<Br> ";
									}
								}
								$obs_causa_item = "'".$obs_causa_item."'"; 
							}else{
								$msg_erro = "É necessário informar a peça faltante<br/>";	
							}                            
                        }
                    }else if($causa_troca_item == 316 && empty($msg_erro)){
                        if($login_fabrica == 1){
                        	$sql = "SELECT  produto AS produto_troca_direta
	                                FROM    tbl_produto
	                                WHERE   referencia  = '$referencia'
	                                AND     fabrica_i   = $login_fabrica
	                                AND     (
	                                            troca_garantia IS TRUE
	                                        OR  troca_faturada IS TRUE
	                                        )
	                        ";
	                      }else{
	                        $sql = "SELECT  produto AS produto_troca_direta
	                                FROM    tbl_produto
	                                WHERE   referencia  = $referencia_troca
	                                AND     fabrica_i   = $login_fabrica
	                                AND     (
	                                            troca_garantia IS TRUE
	                                        OR  troca_faturada IS TRUE
	                                        )
	                        ";
                        }
                        $res = pg_query($con, $sql);
                        if(pg_num_rows($res) > 0){

                        	if($login_fabrica <> 1 ){

                            $produto_troca_direta = pg_fetch_result($res, 0, produto_troca_direta);

                            $sql = "SELECT  tbl_lista_basica.peca
                                    FROM    tbl_lista_basica
                                    JOIN    tbl_peca    ON  tbl_lista_basica.peca   = tbl_peca.peca
                                                        AND tbl_peca.descricao      <> (
                                                            SELECT  descricao
                                                            FROM    tbl_produto
                                                            WHERE   produto     = $produto_troca_direta
                                                            AND     fabrica_i   = $login_fabrica
                                                        )
                                    WHERE   tbl_lista_basica.fabrica = $login_fabrica
                                    AND     tbl_lista_basica.produto = $produto_troca_direta";
                            $res = pg_query($con, $sql);

                            if(pg_num_rows($res) > 0){
                                $msg_erro = "Motivo de troca inválido para o produto $referencia_troca: Há peças na Lista Básica.";
                            }else{
                                $obs_causa_item = "'". $obs_causa_item ."'";
                            }
                          }else{
                          	$obs_causa_item = "'". $obs_causa_item ."'";
                          }
                        }else{
                            if($login_fabrica == 1){
                            	$msg_erro = "Motivo de troca inválido para o produto $referencia: Produto não habilitado para troca";
                        		}else{
                            	$msg_erro = "Motivo de troca inválido para o produto $referencia_troca: Produto não habilitado para troca";
                        		}
                        }
                    }else if($causa_troca_item == 317 && empty($msg_erro)){
                         $aux_causa_item = $obs_causa_item;
                         $lista_item = str_replace("|",", ",$aux_causa_item);
                         $obs_causa_item = "'Peças faltantes: ".$lista_item."'";
                    }else{
                        $obs_causa_item = "'". nl2br($obs_causa_item) ."'";
                    }
                }
              

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
								AND     tbl_linha.fabrica = $login_fabrica";
						if(strlen($voltagem) >0 and $voltagem <> 'null'){
							$sql .=" AND     UPPER(tbl_produto.voltagem) = UPPER($voltagem::text)";
						}
						$res = pg_query($con,$sql);

						if (pg_num_rows($res) == 0) {
							$msg_erro = " Produto $referencia não cadastrado. <BR>";
						}else{
							$produto                  = pg_fetch_result($res,0,produto);
							$numero_serie_obrigatorio = pg_fetch_result($res,0,numero_serie_obrigatorio);
							$linha                    = pg_fetch_result($res,0,linha);
						}


						if($tipo_atendimento == 18 AND strlen($produto) >0){ //troca faturada
							//pega o valor da troca
							$sql = "SELECT valor_troca
									FROM  tbl_produto
									JOIN  tbl_familia USING(familia)
									WHERE fabrica = $login_fabrica
									AND produto=$produto";

							$res = pg_query($con,$sql);
							if(pg_num_rows($res)>0){
								$valor_troca = pg_fetch_result($res,0,0);
								if(strlen($valor_troca)==0 or $valor_troca == "0"){
									$valor_troca=$_POST['valor_troca_'.$i];
									if(strlen($_GET['valor_troca'])>0) $valor_troca=$_GET['valor_troca_'.$i];
								}
							}
							if(strlen($produto) > 0){
								if(strlen($valor_troca) ==0 or $valor_troca=="0"){
									$msg_erro="Por favor, informar o valor da troca.";
								}
							}
						}

						if($tipo_atendimento <> 18){ //troca garantia qualquer uma diferente de troca
							$valor_troca = "0";
						}

						if (strlen($serie) == 0) {
							$serie = 'null';
						}else{
							if ($linha == 199 OR $linha == 200) {
								$msg_erro = " Número de série do produto $referencia não pode ser preenchido. <BR>";
							}else{
								$serie = "'". $serie ."'";
							}
						}

						if (strlen($codigo_fabricacao) == 0)
						{
							//HD 144808: Não será obrigatório digitar o código de fabricacao para a Bosch
							if ($login_fabrica != 1)
							{
								$msg_erro = "Digite o Código de fabricação.<BR>";
							}
						}

						if (!$msg_erro) {
							$codigo_fabricacao = "'". $codigo_fabricacao ."'";
						}

						if ($embalagem_original <> 't' and $embalagem_original <> 'f'){
								$embalagem_original = 'f';
						}
						if ($sinal_de_uso <>'t' and $sinal_de_uso <>'f')  {
								$sinal_de_uso = 'f';
						}

						$valor_troca = str_replace (",",".",$valor_troca);

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
									AND    (tbl_produto.ativo IS TRUE or uso_interno_ativo) ";
							if(strlen($voltagem_troca) >0 and $voltagem_troca <> 'null'){
								$sql .=" AND     UPPER(tbl_produto.voltagem) = UPPER($voltagem_troca::text)";
							}
							$res = pg_query ($con,$sql);

							if (pg_num_rows ($res) == 0) {
								$msg_erro = " Produto $referencia_troca não cadastrado";
								$linha_erro = $i;
							}else{
								$produto_troca = pg_fetch_result ($res,0,produto);
							}

							if (strlen($msg_erro) == 0) {
								$sql = "SELECT produto_opcao as produto
										FROM   tbl_produto_troca_opcao
										WHERE  produto = $produto
										AND    produto_opcao = $produto_troca";
								$res = pg_query ($con,$sql);

								if (pg_num_rows($res)==0) {
									$sql = "SELECT $produto as produto
											FROM tbl_produto_troca_opcao
											WHERE $produto = $produto_troca
											AND (select count(*) from tbl_produto_troca_opcao where produto = $produto) = 0
											LIMIT 1";
									$res = pg_query ($con,$sql);
								}

								if (pg_num_rows ($res) == 0) {
									$msg_erro = " Produto $referencia_troca não encontrado como opção de troca para o produto $referencia";
									$linha_erro = $i;
								}else{
									$produto_troca = pg_fetch_result ($res,0,produto);
								}
							}
						}

						if (strlen ($msg_erro) == 0) {

							if($login_fabrica == 1 && $kit_produto != 0){
								$kit = $kit_produto;
							}


							$sql = "INSERT INTO tbl_os_revenda_item (
									os_revenda                     ,
									produto                        ,
									serie                          ,
									codigo_fabricacao              ,
									nota_fiscal                    ,
									data_nf                        ,
									type                           ,
									embalagem_original             ,
									sinal_de_uso                   ,
									defeito_constatado_descricao   ,
									valor_troca                    ,
									produto_troca				   ,
									kit                             ,
									causa_troca                     ,
									obs_causa                       ,
									tipo_atendimento
                                    {$sql_prateleira_box["insert"]["campo"]}
								) VALUES (
									$os_revenda                    ,
									$produto                       ,
									$serie                         ,
									$codigo_fabricacao             ,
									$xnota_fiscal                  ,
									$xdata_nf                      ,
									$type                          ,
									'$embalagem_original'          ,
									'$sinal_de_uso'                ,
									'$defeito_constatado_descricao',
									'$valor_troca'                 ,
									$produto_troca				   ,
									$kit                            ,
									$causa_troca_item               ,
									$obs_causa_item                 ,
									$tipo_atendimento_item
                                    {$sql_prateleira_box["insert"]["valor"]}
								)";

								$res = pg_query ($con,$sql);
								$msg_erro = pg_errormessage($con);

							if (strlen($msg_erro) == 0) {
								$res        = pg_query ($con,"SELECT CURRVAL ('seq_os_revenda_item')");
								$os_revenda_item = pg_fetch_result ($res,0,0);
								$msg_erro   = pg_errormessage($con);

								$conta_qtde++;

							}
							if (strlen ($msg_erro) == 0) {
								$sql = "SELECT fn_valida_os_item_revenda_black($os_revenda,$login_fabrica,$produto,$os_revenda_item)";
								$res = @pg_query ($con,$sql);
								$msg_erro = pg_errormessage($con);
							}

							if (strlen ($msg_erro) > 0) {
								$linha_erro = $i;
								break ;
							}
						}
						/*hd 20176 16/5/2008*/
						if (strlen ($msg_erro) > 0) {
							$linha_erro = $i;
							break ;
						}
					}
				}
			}

			if($causa_troca == 124 ) {
				if(count($multi_peca) > 0) {

					$sqlProdutoOrigem = "SELECT produto AS produto_origem FROM tbl_produto where fabrica = $login_fabrica and referencia = '".trim($produto_origem_arr[0])."'";
					$resProdutoOrigem = pg_query($con, $sqlProdutoOrigem);

					for($i =0;$i<count($multi_peca);$i++) {
						$sql = "SELECT DISTINCT tbl_peca.referencia,
										tbl_peca.descricao 
								FROM tbl_lista_basica
								JOIN tbl_peca USING(peca)
								WHERE  tbl_lista_basica.fabrica = $login_fabrica
								AND    tbl_lista_basica.produto IN (
									SELECT DISTINCT produto
									FROM tbl_os_revenda_item
									WHERE os_revenda = $os_revenda
								)
								AND   referencia  = '".$multi_peca[$i]."'";
						$res = pg_query($con,$sql);
						if(pg_num_rows($res) > 0){
							$pecas .="<br>".pg_fetch_result($res,0,referencia)." - ". pg_fetch_result($res,0,descricao);
						}else{
							$msg_erro = $multi_peca[$i]." não encontrada no sistema ou não está na lista básica de produto <br>";
						}
					}
				}else{
					$msg_erro = "É necessário informar a peça faltante<br/>";
				}

				if(empty($msg_erro)) {
					$sql = " UPDATE tbl_os_revenda
								SET obs_causa = '$pecas'
							WHERE os_revenda = $os_revenda";
					$res = pg_query($con,$sql);
					$msg_erro = pg_last_error($con);
				}
			}

			if (strlen ($msg_erro) == 0) {
				$sql = "SELECT fn_valida_os_revenda($os_revenda,$posto,$login_fabrica)";
				$res = @pg_query ($con,$sql);
				$msg_erro = pg_errormessage($con);
			}
		}
	}

	if (strlen ($msg_erro) == 0) {
		$res = pg_query ($con,"COMMIT TRANSACTION");

		// MLG - Movi o UPLOAD depois do COMMIT para evitar problemas com o nº da OS
		// durante a transação...
		if($login_fabrica == 1 and empty($msg_erro)) {
			foreach (range(0, 4) as $idx) {
				if ($_FILES["foto_nf"]['tmp_name'][$idx][0] != '') {
					$file = array(
						"name" => $_FILES["foto_nf"]["name"][$idx][0],
						"type" => $_FILES["foto_nf"]["type"][$idx][0],
						"tmp_name" => $_FILES["foto_nf"]["tmp_name"][$idx][0],
						"error" => $_FILES["foto_nf"]["error"][$idx][0],
						"size" => $_FILES["foto_nf"]["size"][$idx][0]
					);
					$anexou = anexaNF("r_".$os_revenda, $file);

					if ($anexou !== 0) $msg_erro .= (is_numeric($anexou)) ? $msgs_erro[$anexou] : $anexou; // '0' é que executou OK
				}
			}
		}

		if ($causa_troca == 125) {
			$sql = "SELECT email,codigo_posto from tbl_posto_fabrica join tbl_admin ON tbl_posto_fabrica.admin_sap = tbl_admin.admin where tbl_admin.fabrica = $login_fabrica and posto =$posto";
			$res = pg_query($con,$sql);

			if (pg_num_rows($res)>0) {

				$email        = pg_fetch_result($res,0,email);
				$codigo_posto = pg_fetch_result($res,0,codigo_posto);

				if(!empty($email)) {
					$sqls="SELECT sua_os,descricao FROM tbl_os_revenda WHERE os_revenda = $os_revenda";
					$ress = pg_query($con,$sqls);
					$sua_os   = pg_fetch_result($ress,0,sua_os);
					$descricao= pg_fetch_result($ress,0,descricao);
					$message = "OS $codigo_posto"."$sua_os de troca de produto ($descricao) lançada com o motivo Falha do posto.\n<br/>Falha informada: $obs_causa";

					$assunto = "Troca de produto por falha do posto ($codigo_posto).";

					$headers = "From: Telecontrol <telecontrol@telecontrol.com.br>\n";

					$headers .= "MIME-Version: 1.0\n";
					$headers .= "Content-type: text/html; charset=iso-8859-1\n";

					if (mail("$email", utf8_encode($assunto), utf8_encode($message), $headers)) {
					}
				}
			}
		}

		if ($causa_troca == 130){

			$sql = "SELECT email from tbl_admin where tbl_admin.fabrica = $login_fabrica and responsavel_postos and ativo";
			$res = pg_query($con,$sql);
			$numrows = pg_last_error($res);
			if (pg_num_rows($res) > 0) {

				$sqls="SELECT sua_os,descricao FROM tbl_os_revenda WHERE os_revenda = $os_revenda";
					$ress = pg_query($con,$sqls);
					$sua_os   = pg_fetch_result($ress,0,sua_os);
					$descricao= pg_fetch_result($ress,0,descricao);

				$admin_responsavel_postos = array();

				for ($i=0; $i < pg_num_rows($res); $i++) {
					$admin_responsavel_postos[] = pg_fetch_result($res, $i, 'email');
				}

				$admin_responsavel_postos = implode(', ', $admin_responsavel_postos);

				$email        = $admin_responsavel_postos;

				$sql = "SELECT codigo_posto from tbl_posto_fabrica where fabrica = $login_fabrica and posto=$posto";
				$res = pg_query($con,$sql);
				$codigo_posto = pg_fetch_result($res, 0, 'codigo_posto');

				if (!empty($email)) {

					$message = "OS $codigo_posto"."$sua_os de troca de produto foi cadastrada com o motivo Falta de Posto de serviço.\n<br> Cidade: $falta_cidade\n<br/>Estado: $falta_estado \n<br><br><b>Suporte Telecontrol</b>";

					$assunto = "OS $codigo_posto"."$sua_os Falta de Posto de serviço.";
					$headers  = "From: Telecontrol <suporte@telecontrol.com.br>\n";
					$headers .= "MIME-Version: 1.0\n";
					$headers .= "Content-type: text/html; charset=iso-8859-1\n";

					mail("$email", utf8_encode($assunto), utf8_encode($message), $headers);

				}

			}

		}
            /*-- Por que é que anexava 2x a img???
		if($login_fabrica == 1 && empty($msg_erro)){
			$anexou = anexaNF("r_".$os_revenda, $_FILES['foto_nf']);
			if ($anexou !== 0) $msg_erro .= (is_numeric($anexou)) ? $msgs_erro[$anexou] : $anexou; // '0' é que executou OK
        } --*/

		header ("Location: os_revenda_finalizada.php?os_revenda=$os_revenda");
		exit;

	}else{
		if (strpos ($msg_erro,"tbl_os_revenda_unico") > 0) $msg_erro = " O Número da Ordem de Serviço do fabricante já está cadastrado.";
		if (strpos ($msg_erro,"null value in column \"data_abertura\" violates not-null constraint") > 0) $msg_erro = "Data da abertura deve ser informada.";

		if (strpos ($msg_erro,'date/time field value out of range') > 0) $msg_erro ="O formato da data incorreto, por favor, verificar.";
		$os_revenda = trim($_POST['os_revenda']);

		$res = pg_query ($con,"ROLLBACK TRANSACTION");

		if(empty($qtde_item)){
			$codigo_fabricacao             = $_POST['codigo_fabricacao2'];
			$serie                         = $_POST['produto_serie2'];
			$referencia_produto            = $_POST['produto_referencia2'];
			$produto_descricao             = $_POST['produto_descricao2'];
			$produto_voltagem              = $_POST['produto_voltagem2'];
			$type                          = $_POST['type2'];
			$embalagem_original2           = $_POST['embalagem_original2'];
			$sinal_de_uso2                 = $_POST['sinal_de_uso2'];
			$defeito_constatado_descricao2 = $_POST['defeito_constatado_descricao2'];
			$produto_referencia_troca2     = $_POST['produto_referencia_troca2'];
			$produto_descricao_troca2      = $_POST['produto_descricao_troca2'];
			$produto_voltagem_troca2       = $_POST['produto_voltagem_troca2'];
			$produto_qtde                  = $_POST['produto_qtde'];
		}
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
					tbl_os_revenda.admin_autoriza                                        ,
					tbl_os_revenda.causa_troca                                           ,
					tbl_os_revenda.obs_causa                                             ,
					tbl_posto_fabrica.codigo_posto                                       ,
					tbl_posto.nome
			FROM	tbl_os_revenda
			JOIN	tbl_revenda ON tbl_os_revenda.revenda = tbl_revenda.revenda
			JOIN	tbl_fabrica USING (fabrica)
			JOIN    tbl_posto USING (posto)
			JOIN    tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
					AND tbl_posto_fabrica.fabrica = tbl_fabrica.fabrica
			WHERE	tbl_os_revenda.os_revenda = $os_revenda
			AND		tbl_os_revenda.fabrica    = $login_fabrica ";
	$res = pg_query($con, $sql);

	if (pg_num_rows($res) > 0){
		$sua_os           = pg_fetch_result($res,0,sua_os);
		$data_abertura    = pg_fetch_result($res,0,data_abertura);
		$data_nf          = pg_fetch_result($res,0,data_nf);
		$nota_fiscal      = pg_fetch_result($res,0,nota_fiscal);
		$revenda_nome     = pg_fetch_result($res,0,revenda_nome);
		$revenda_cnpj     = pg_fetch_result($res,0,revenda_cnpj);
		$revenda_fone     = pg_fetch_result($res,0,revenda_fone);
		$revenda_email    = pg_fetch_result($res,0,revenda_email);
		$obs              = pg_fetch_result($res,0,obs);
		$contrato         = pg_fetch_result($res,0,contrato);
		$explodida        = pg_fetch_result($res,0,explodida);
		$posto            = pg_fetch_result($res,0,posto);
		$posto_codigo     = pg_fetch_result($res,0,codigo_posto);
		$posto_nome       = pg_fetch_result($res,0,nome);
		$causa_troca      = pg_fetch_result($res,0,causa_troca);
		$admin_autoriza   = pg_fetch_result($res,0,admin_autoriza);
		$obs_causa        = pg_fetch_result($res,0,obs_causa);

		if($causa_troca == 124) {
			$multi_peca = explode("<br>",$obs_causa);
			$obs_causa = "";
		}

		if($causa_troca == 130) {
			$falta_posto = explode("<br>",$obs_causa);
			$falta_cidade = $falta_posto[0];
			$falta_estado = $falta_posto[1];
			$obs_causa = "";
		}

		if (strlen($explodida) > 0){
			header("Location:os_revenda_parametros.php");
			exit;
		}

		$sql = "SELECT *
				FROM   tbl_os
				WHERE  sua_os ILIKE '$sua_os-%'
				AND    posto   = $posto
				AND    fabrica = $login_fabrica";
		$resX = pg_query($con, $sql);

		if (pg_num_rows($resX) == 0) $exclui = 1;

		$sql = "SELECT  tbl_os_revenda_item.nota_fiscal,
						to_char(tbl_os_revenda_item.data_nf, 'DD/MM/YYYY') AS data_nf
				FROM	tbl_os_revenda_item
				JOIN	tbl_os_revenda ON tbl_os_revenda.os_revenda = tbl_os_revenda_item.os_revenda
				WHERE	tbl_os_revenda.os_revenda = $os_revenda
				AND		tbl_os_revenda.fabrica    = $login_fabrica
				AND		tbl_os_revenda.posto      = $posto
				AND		tbl_os_revenda_item.nota_fiscal NOTNULL
				AND		tbl_os_revenda_item.data_nf     NOTNULL LIMIT 1";
		$res = pg_query($con, $sql);

		if (pg_num_rows($res) > 0){
			$nota_fiscal = pg_fetch_result($res,0,nota_fiscal);
			$data_nf     = pg_fetch_result($res,0,data_nf);
		}
	}else{
		header('Location: os_revenda_troca.php');
		exit;
	}
}
$title			= "CADASTRO DE ORDEM DE SERVIÇO DE TROCA - REVENDA";
$layout_menu	= 'callcenter';
include "cabecalho.php";
include "javascript_pesquisas.php";

?>
<link rel="stylesheet" type="text/css" href="../plugins/shadowbox/shadowbox.css" media="all">
<script src="../plugins/shadowbox/shadowbox.js" type="text/javascript"></script>
<script type='text/javascript'>
	$(function() {
		Shadowbox.init();

        $('#numero_bo_info').blur(function(){

            if($(this).val() != ""){

                var bo = $(this).val();

                $.ajax({
                    url : "<?php echo $_SERVER['PHP_SELF']; ?>",
                    type : "POST",
                    data: {
                        verifica_bo : "ok",
                        bo : bo
                    },
                    beforeSend: function(){
                        $('#desc_bo').html('<em>verificando...</em>');
                    },
                    complete: function(data){

                        data = data.responseText;
                        $('#desc_bo').html(data);

                    }
                });

            }

        });

		$('#numero_bo_info_item').blur(function(){

			if($(this).val() != ""){

				var bo = $(this).val();

				$.ajax({
					url : "<?php echo $_SERVER['PHP_SELF']; ?>",
					type : "POST",
					data: {
						verifica_bo : "ok",
						bo : bo
					},
					beforeSend: function(){
						$('#desc_bo_item').html('<em>verificando...</em>');
					},
                    complete: function(data){

                        data = data.responseText;
                        $('#desc_bo_item').html(data);

                    }
                });
			}

		});

	});
	<?php if ($login_fabrica == 1) { ?>
		$(window).load(function (){
			if ($('#tipo_atendimento').val() == 18 ) {
		      	$('#TDOrdemDeServicoSemNF').css('display','block');
		      	$('#OrdemDeServicoSemNF').css('display','block');
			}
		});
	<?php } ?>

   function tipoatendimento(n) {
      <?php if ($login_fabrica == 1) { ?>
             	if(n == "button"){
                	n = $('#tipo_atendimento').val();
                }

              	if(n == 18){
                  	$('#TDOrdemDeServicoSemNF').css('display','block');
                  	$('#OrdemDeServicoSemNF').css('display','block');
              	} else {
              		$('#TDOrdemDeServicoSemNF').css('display','none');
                  	$('#OrdemDeServicoSemNF').css('display','none');
              	}
      <?php } ?>
    }

    function limpa_troca(){
    	$('input[name="produto_referencia_troca2"]').val('');
		$('input[name="produto_descricao_troca2"]').val('');
		$('input[name="produto_troca"]').val('');
		$('input[name="produto_voltagem_troca2"]').val('');
		$('input[name="kit_produto"]').val('');
    }

    function pesquisaProduto(produto,tipo,posicao,posto){

        var posto = jQuery.trim(posto.value);
        if ((jQuery.trim(produto.value).length > 2) && posto.length > 0){
            Shadowbox.open({
<?php if ($login_fabrica == 1) { ?>
                content:"produto_pesquisa_2_nv.php?id_posto="+posto+"&"+tipo+"="+produto.value+"&posicao="+posicao+"&troca=TRUE",
<?php } else { ?>
                content:"produto_pesquisa_2_nv.php?id_posto="+posto+"&"+tipo+"="+produto.value+"&posicao="+posicao,
<?php }?>
                player: "iframe",
                title:"Produto",
                width:800,
                height:500
            });
        }else{
            alert("Informar posto e parte da informação para realizar a pesquisa!");
            produto.focus();
        }
    }

    function pesquisaProdutoTroca(referencia, descricao, voltagem, referencia2, voltagem2, tipo, id_produto2,posicao){

        if (jQuery.trim(referencia2.value).length == 0){
            alert("Informe um produto para troca!");
            referencia2.focus();
            return false;
        }

            Shadowbox.open({
                content:    "pesquisa_produto_troca_nv.php?referencia=" + referencia.value + "&descricao=" + descricao.value + "&voltagem=" + voltagem.value + "&referencia_produto=" + referencia2.value + "&voltagem_produto=" + voltagem2.value + "&tipo=" + tipo+"&produto="+id_produto2+"&posicao="+posicao,
                player: "iframe",
                title:      "Produto Troca",
                width:  800,
                height: 500
            });
    }

	function pesquisaProdutoOrigem(produto,tipo,posicao){
		var novo_tipo = tipo.split("_");

		$('input[name^=produto_referencia_troca]').val('');
		$('input[name^=produto_descricao_troca]').val('');

		if (jQuery.trim(produto.value).length > 2){
            Shadowbox.open({
                content:    "produto_pesquisa_2_nv.php?"+novo_tipo[1]+"="+produto.value+"&posicao="+posicao+"&origem=true",
                player: "iframe",
                title:      "Produto Origem",
                width:  800,
                height: 500
            });
        }else{
            alert("Informar toda ou parte da informação para realizar a pesquisa!");
            produto.focus();
        }
	}

	function pesquisaRevenda(campo,tipo){
		var campo = campo.value;

		if (jQuery.trim(campo).length > 2){
			Shadowbox.open({
				content:	"pesquisa_revenda_nv.php?"+tipo+"="+campo+"&tipo="+tipo,
				player:	"iframe",
				title:		"Pesquisa Revenda",
				width:	800,
				height:	500
			});
		}else
			alert("Informar toda ou parte da informação para realizar a pesquisa!");
	}

	function pesquisaPosto(campo,tipo){
		var campo = campo.value;

		if (jQuery.trim(campo).length > 2){
			Shadowbox.open({
				content:	"posto_pesquisa_2_nv.php?"+tipo+"="+campo+"&tipo="+tipo,
				player:	"iframe",
				title:		"Pesquisa Posto",
				width:	800,
				height:	500
			});
		}else
			alert("Informar toda ou parte da informação para realizar a pesquisa!");
	}

	function pesquisaSerie(referencia, descricao, serie, posicao){
		var serie = serie.value;

		if (jQuery.trim(serie).length > 2){
			Shadowbox.open({
				content:	"produto_serie_pesquisa2_nv.php?serie="+serie+"&posicao="+posicao,
				player:	"iframe",
				title:		"Pesquisa Serie",
				width:	800,
				height:	500
			});
		}else
			alert("Informar toda ou parte da informação para realizar a pesquisa!");
	}

	function pesquisaPeca(peca,tipo,item, status = ''){
		if (jQuery.trim(peca.value).length > 2){
			Shadowbox.open({
				content:	"peca_pesquisa_nv.php?"+tipo+"="+peca.value+"&item="+item+"&status="+status.value,
				player:	"iframe",
				title:		"Peça",
				width:	800,
				height:	500
			});
		}else{
			alert("Informar toda ou parte da informação para realizar a pesquisa!");
			peca.focus();
		}

	}


	function retorna_dados_produto(produto,linha,nome_comercial,voltagem,referencia,descricao,referencia_fabrica,garantia,ativo,valor_troca,troca_garantia,troca_faturada,mobra,off_line,capacidade,ipi,troca_obrigatoria, posicao,origem){

		if(posicao.length > 0 && posicao != 'undefined' && origem.length == 0){
			gravaDados('produto_referencia_'+posicao,referencia);
			gravaDados('produto_descricao_'+posicao,descricao);
			gravaDados('produto_voltagem_'+posicao,voltagem);
		}else{
            if(origem == 'true'){
                gravaDados('produto_origem_referencia_item',referencia);
                gravaDados('produto_origem_descricao_item',descricao);
            }else{
                gravaDados('produto_referencia2',referencia);
                gravaDados('produto_descricao2',descricao);
                gravaDados('produto_voltagem2',voltagem);
            }
		}

		<?php
		if ($login_fabrica == 1) { ?>
			verifica_produtos_troca(referencia);
		<?php
		} ?>
	}

	function retorna_dados_produto_troca(produto, referencia, descricao, voltagem, posicao, kit){

		if(posicao.length > 0 && posicao != 'undefined'){
			gravaDados('produto_troca_'+posicao,produto);
			gravaDados('produto_referencia_troca_'+posicao,referencia);
			gravaDados('produto_descricao_troca_'+posicao,descricao);
			gravaDados('produto_voltagem_troca_'+posicao,voltagem);
		}else{
			gravaDados('produto_troca',produto);
			gravaDados('produto_referencia_troca2',referencia);
			gravaDados('produto_descricao_troca2',descricao);
			gravaDados('produto_voltagem_troca2',voltagem);
			gravaDados('kit_produto',kit);
		}

	}

	function retorna_posto(codigo_posto,posto,nome,cnpj,cidade,estado,credenciamento,num_posto){
		gravaDados('posto',posto);
		gravaDados('posto_codigo',codigo_posto);
		gravaDados('posto_nome',nome);

        <?php
        if ($login_fabrica == '1') {
            echo 'verificaPostoCredenciamento();';
        }
        ?>
	}

	function retorna_revenda(nome,cnpj,nome_cidade,fone,endereco,numero,complemento,bairro,cep,estado,email){
		gravaDados("revenda_nome",nome);
		gravaDados("revenda_cnpj",cnpj);
		gravaDados("revenda_fone",fone);
		gravaDados("revenda_email",email);
		gravaDados("revenda_cidade",nome_cidade);
		gravaDados("revenda_estado",estado);
		gravaDados("revenda_endereco",endereco);
		gravaDados("revenda_cep",cep);
		gravaDados("revenda_numero",numero);
		gravaDados("revenda_complemento",complemento);
		gravaDados("revenda_bairro",bairro);
	}

	function retorna_serie(referencia, descricao, serie,posicao){
		if(posicao.length > 0 && posicao != 'undefined'){
			gravaDados('produto_referencia_'+posicao,referencia);
			gravaDados('produto_descricao_'+posicao,descricao);
			gravaDados('produto_serie_'+posicao,serie);
		}else{
			gravaDados('produto_referencia2',referencia);
			gravaDados('produto_descricao2',descricao);
			gravaDados('produto_serie2',serie);
		}
	}

	function retorna_dados_peca(peca,referencia,descricao,ipi,origem,estoque,unidade,ativo,posicao,item){
		if(item == 'total'){
            gravaDados('peca_referencia_multi',referencia);
            gravaDados('peca_descricao_multi',descricao);
		}else{
            gravaDados('peca_referencia_multi_item',referencia);
            gravaDados('peca_descricao_multi_item',descricao);
		}
	}

	function gravaDados(name, valor){
		try{
			$("input[name="+name+"]").val(valor);
		} catch(err){
			return false;
		}
	}

function fnc_pesquisa_produto (campo, campo2, campo3, tipo) {
	if (tipo == "referencia" ) {
		var xcampo = campo;
	}

	if (tipo == "descricao" ) {
		var xcampo = campo2;
	}

	if (xcampo.value != "") {
		var url = "";
		url = "produto_pesquisa_2.php?campo=" + xcampo.value + "&tipo=" + tipo + "&exibe=<? echo $_SERVER['REQUEST_URI']; ?>";
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

    function verifica_produtos_troca(referencia){

	    $.ajax({
	        type: 'POST',
	        dataType:"JSON",
	        url: 'ajax_verifica_troca.php',
	        data: {
	            ajax_verifica_troca : true,
	            produto : referencia,
	            admin : true
	        },
	    }).done(function(data) {
	        if (data.mostra_shadowbox) {
	        	informa_produtos_troca(data.produto);
	        }
	    });

	}

	function informa_produtos_troca(produto) {
		setTimeout(function() {
	    	Shadowbox.open({
				content :   "produtos_disponiveis_troca.php?produto="+produto,
				player  :   "iframe",
				title   :   "Produtos disponíveis para troca",
				width   :   800,
				height  :   500
			});
		}, 2000);
	}
</script>

<script type="text/javascript" src="js/verifica_posto_credenciamento.js"></script>

<?php include "../js/js_css.php";?>
<script language='javascript' src='../ajax.js'></script>
<script type="text/javascript" src="js/jquery.alphanumeric.js"></script>
<script type="text/javascript" charset="utf-8">
	$(function(){

		//$("#nota_fiscal").numeric();
		$("#data_nf").mask("99/99/9999");
		$("#revenda_fone").mask("(99) 9999-9999");
		$('#produto_qtde').numeric();
		$("input[name='revenda_cnpj']").numeric({allow:"./-"});
		$("input[name='nota_fiscal']").numeric({allow:"-"});
		$('#img_gravar').click(function(){
			$('#multi_peca option').attr('selected','selected');
		})
		$('.datapicker').datepick({startDate : '01/01/2000'});
	});


	function addRowToTable(){
		var tbl = document.getElementById('tbl_produto');
		if($(".reverter_produto").is(":checked")){
			var valor_reverter_produto = $(".reverter_produto:checked").val();
		}

		var lastRow = tbl.rows.length;
		var iteration = lastRow -1 ;
		var row = tbl.insertRow(lastRow);
        var embalagem_value = "";
        $('input:radio[name=embalagem_original2]').each(function() {
            //Verifica qual está selecionado
            if ($(this).is(':checked'))
                embalagem_value = $(this).val();
        });
		var sinal_value = "";
		$('input:radio[name=sinal_de_uso2]').each(function() {
            //Verifica qual está selecionado
            if ($(this).is(':checked'))
                sinal_value = $(this).val();
        });
		var embalagem_text = "";
		var sinal_text = "";
		if(embalagem_value == 't'){
			var embalagem_text  = 'Sim';
		}else{
			if (embalagem_value == 'f'){
				var embalagem_text  = 'Não'
			}
		}

		if(sinal_value == 't'){
			var sinal_text  = 'Sim';
		}else{
			if (sinal_value == 'f'){
				var sinal_text  = 'Não';
			}
		}

		if(lastRow % 2 == 0){
			row.style.backgroundColor="#F7F5F0";
		}
		else{
			row.style.backgroundColor="#D9E2EF";
		}


		var cellRight1 = row.insertCell(0);
		cellRight1.appendChild(document.createTextNode(document.frm_os.codigo_fabricacao2.value));

		var el = document.createElement('input');
		el.setAttribute('type', 'hidden');
		el.setAttribute('name', 'codigo_fabricacao_' + iteration);
		el.setAttribute('value', document.frm_os.codigo_fabricacao2.value);
		el.setAttribute('id', 'codigo_fabricacao_' + iteration);
		cellRight1.appendChild(el);


		var cellRight1 = row.insertCell(1);
		cellRight1.appendChild(document.createTextNode(document.frm_os.produto_serie2.value));

		var el = document.createElement('input');
		el.setAttribute('type', 'hidden');
		el.setAttribute('name', 'produto_serie_' + iteration);
		el.setAttribute('value', document.frm_os.produto_serie2.value);
		el.setAttribute('id', 'produto_serie_' + iteration);
		cellRight1.appendChild(el);

		var cellRight1 = row.insertCell(2);
		cellRight1.appendChild(document.createTextNode(document.frm_os.produto_referencia2.value));

		var el = document.createElement('input');
		el.setAttribute('type', 'hidden');
		el.setAttribute('name', 'produto_referencia_' + iteration);
		el.setAttribute('value', document.frm_os.produto_referencia2.value);
		el.setAttribute('id', 'produto_referencia_' + iteration);
		cellRight1.appendChild(el);


		var el = document.createElement('input');
		el.setAttribute('type', 'hidden');
		el.setAttribute('name', 'item_' + iteration);
		el.setAttribute('id', 'item_' + iteration);
		el.setAttribute('value', iteration);
		cellRight1.appendChild(el);

		var cellRight1 = row.insertCell(3);
		cellRight1.setAttribute('align', 'center');
		cellRight1.appendChild(document.createTextNode(document.frm_os.produto_descricao2.value));

		var el = document.createElement('input');
		el.setAttribute('type', 'hidden');
		el.setAttribute('name', 'produto_descricao_' + iteration);
		el.setAttribute('id', 'produto_descricao_' + iteration);
		el.setAttribute('value', document.frm_os.produto_descricao2.value);
		cellRight1.appendChild(el);

		var cellRight1 = row.insertCell(4);
		cellRight1.setAttribute('align', 'center');
		cellRight1.appendChild(document.createTextNode(document.frm_os.produto_voltagem2.value));

		var el = document.createElement('input');
		el.setAttribute('type', 'hidden');
		el.setAttribute('name', 'produto_voltagem_' + iteration);
		el.setAttribute('value', document.frm_os.produto_voltagem2.value);
		el.setAttribute('id', 'produto_voltagem_' + iteration);
		cellRight1.appendChild(el);

		var cellRight1 = row.insertCell(5);
		var el = document.createElement('input');
		var tipo = document.frm_os.type2.selectedIndex;
		if (tipo <=0)	{
			tipo = 0;
		}
		cellRight1.appendChild(document.createTextNode(document.frm_os.type2.options[tipo].value));
		var el = document.createElement('input');
		el.setAttribute('type', 'hidden');
		el.setAttribute('name', 'type_' + iteration);
		el.setAttribute('value', document.frm_os.type2.options[tipo].value);
		el.setAttribute('id', 'type_' + iteration);
		cellRight1.appendChild(el);

		var cellRight1 = row.insertCell(6);
		var el = document.createElement('input');

		cellRight1.appendChild(document.createTextNode(embalagem_text));
		var el = document.createElement('input');
		el.setAttribute('type', 'hidden');
		el.setAttribute('name', 'embalagem_original_' + iteration);
		el.setAttribute('value', embalagem_value);
		el.setAttribute('id', 'embalagem_original_' + iteration);
		cellRight1.appendChild(el);

		var cellRight1 = row.insertCell(7);
		var el = document.createElement('input');

		cellRight1.appendChild(document.createTextNode(sinal_text));
		var el = document.createElement('input');
		el.setAttribute('type', 'hidden');
		el.setAttribute('name', 'sinal_de_uso_' + iteration);
		el.setAttribute('value', sinal_value);
		el.setAttribute('id', 'sinal_de_uso_' + iteration);
		cellRight1.appendChild(el);

		var cellRight1 = row.insertCell(8);
		cellRight1.setAttribute('align', 'center');
		cellRight1.appendChild(document.createTextNode(document.frm_os.defeito_constatado_descricao2.value));

		var el = document.createElement('input');
		el.setAttribute('type', 'hidden');
		el.setAttribute('name', 'defeito_constatado_descricao_' + iteration);
		el.setAttribute('id', 'defeito_constatado_descricao_' + iteration);
		el.setAttribute('value', document.frm_os.defeito_constatado_descricao2.value);
		cellRight1.appendChild(el);

		var cellRight1 = row.insertCell(9);
		cellRight1.appendChild(document.createTextNode(document.frm_os.produto_referencia_troca2.value));

		var el = document.createElement('input');
		el.setAttribute('type', 'hidden');
		el.setAttribute('name', 'produto_referencia_troca_' + iteration);
		el.setAttribute('value', document.frm_os.produto_referencia_troca2.value);
		el.setAttribute('id', 'produto_referencia_troca_' + iteration);
		cellRight1.appendChild(el);

		//HD 244476: Acrescentei o ID do produto trocado, que é também o número do KIT escolhido, se for o caso
		var el = document.createElement('input');
		el.setAttribute('type', 'hidden');
		el.setAttribute('name', 'produto_troca_' + iteration);
		el.setAttribute('value', document.frm_os.produto_troca.value);
		el.setAttribute('id', 'produto_troca_' + iteration);
		cellRight1.appendChild(el);

		var cellRight1 = row.insertCell(10);
		cellRight1.setAttribute('align', 'center');
		cellRight1.appendChild(document.createTextNode(document.frm_os.produto_descricao_troca2.value));

		var el = document.createElement('input');
		el.setAttribute('type', 'hidden');
		el.setAttribute('name', 'produto_descricao_troca_' + iteration);
		el.setAttribute('id', 'produto_descricao_troca_' + iteration);
		el.setAttribute('value', document.frm_os.produto_descricao_troca2.value);
		cellRight1.appendChild(el);
		
		var el = document.createElement('input');
		el.setAttribute('type', 'hidden');
		el.setAttribute('name', 'descricao_motivo_descontinuado_' + iteration);
		el.setAttribute('id', 'descricao_motivo_descontinuado_' + iteration);
		el.setAttribute('value', document.frm_os.motivo_descontinuado.value);
		cellRight1.appendChild(el);

        var cellRight1 = row.insertCell(11);
        cellRight1.setAttribute('align', 'center');
        cellRight1.appendChild(document.createTextNode(document.frm_os.produto_voltagem_troca2.value));

        var el = document.createElement('input');
        el.setAttribute('type', 'hidden');
        el.setAttribute('name', 'produto_voltagem_troca_' + iteration);
        el.setAttribute('value', document.frm_os.produto_voltagem_troca2.value);
        el.setAttribute('id', 'produto_voltagem_troca_' + iteration);
        cellRight1.appendChild(el);

        var el = document.createElement('input');
        el.setAttribute('type', 'hidden');
        el.setAttribute('name', 'kit_produto_' + iteration);
        el.setAttribute('value', document.frm_os.kit_produto.value);
        el.setAttribute('id', 'kit_produto_' + iteration);
        cellRight1.appendChild(el);
<?
if($login_fabrica == 1){
?>
		var cellRight1 = row.insertCell(12);
		cellRight1.setAttribute('align', 'center');
		var item = document.frm_os.causa_troca_item.selectedIndex;
		var texto       = "";
        var valor       = "";
		var valor_obs   = "";

        texto = document.frm_os.causa_troca_item.options[item].text;
        valor = document.frm_os.causa_troca_item.options[item].value;

        var codigo = $("#causa_troca_item option:selected").data('codigo');

        if(codigo == 'ATS'){
        	var el = document.createElement('input');
            el.setAttribute('type', 'hidden');
            el.setAttribute('name', 'pedido_item_' + iteration);
            el.setAttribute('value', document.frm_os.pedido_item.value );
            el.setAttribute('id', 'pedido_item_' + iteration);
            cellRight1.appendChild(el);
        }
 

        if(valor == 124  || valor == 380){
            var pecas = "";
            var pecasCount = document.frm_os.multi_peca_item.options.length;
            for(var i=0;i < pecasCount;i++){
                pecas += document.frm_os.multi_peca_item.options[i].value;
                if(i != pecasCount - 1){
                    pecas += "|";
                }
            }
            var el = document.createElement('input');
            el.setAttribute('type', 'hidden');
            el.setAttribute('name', 'obs_causa_item_' + iteration);
            el.setAttribute('value',pecas);
            el.setAttribute('id', 'obs_causa_item_' + iteration);
            cellRight1.appendChild(el);

            if (valor == 124) {

                var opt = $("select[name=prateleira_box] option:selected").val();
                var el1 = document.createElement('input');
                el1.setAttribute('type', 'hidden');
                el1.setAttribute('name', 'prateleira_box_' + iteration);
                el1.setAttribute('value',opt);
                el1.setAttribute('id', 'prateleira_box_' + iteration);
                cellRight1.appendChild(el1);

                if (opt) {
                    texto += " - " + opt.charAt(0).toUpperCase() + opt.slice(1);
                }
            }
        }else if(valor == 317){
            var pecas = "";
            var pecasCount = document.frm_os.multi_lista_item.options.length;
            for(var i=0;i < pecasCount;i++){
                pecas += document.frm_os.multi_lista_item.options[i].value;
                if(i != pecasCount - 1){
                    pecas += "|";
                }
            }
            var el = document.createElement('input');
            el.setAttribute('type', 'hidden');
            el.setAttribute('name', 'obs_causa_item_' + iteration);
            el.setAttribute('value',pecas);
            el.setAttribute('id', 'obs_causa_item_' + iteration);
            cellRight1.appendChild(el);
        }else if(valor == 130){
            var el = document.createElement('input');
            el.setAttribute('type', 'hidden');
            el.setAttribute('name', 'obs_causa_item_' + iteration);
            el.setAttribute('value', 'Estado: '+document.frm_os.falta_estado_item.options[document.frm_os.falta_estado_item.selectedIndex].value+'<br>Cidade: '+document.frm_os.falta_cidade_item.value);
            el.setAttribute('id', 'obs_causa_item_' + iteration);
            cellRight1.appendChild(el);
        }else if(valor == 125 || valor == 128 || valor == 131 || valor == 317 || valor == 316){

        	var info = document.frm_os.obs_causa_item.value; 

        	if(valor == 125){
        		var motivo_falha_posto = $(".motivo_falha_posto").val();
        		if(motivo_falha_posto == 'atraso_colocacao_pedido'){
        			var pedido_motivo_falha_posto = $(".pedido_motivo_falha_posto").val();
        			info = info + "Motivo: Atraso colocação do pedido Nº"+ pedido_motivo_falha_posto;
        		}else if(motivo_falha_posto == 'fez_pedido'){
        			var  pedido_motivo_falha_posto = $(".pedido_motivo_falha_posto").val();
        			info = info + "Motivo: Fez pedido "+pedido_motivo_falha_posto+", mas peça não foi enviada/atrasou"+ pedido_motivo_falha_posto;
        		}else{
        			var motivo_falha_posto = $(".motivo_falha_posto").text();
        			info = info + "Motivo: " + motivo_falha_posto;
        		}
        	}

            var el = document.createElement('input');
            el.setAttribute('type', 'hidden');
            el.setAttribute('name', 'obs_causa_item_' + iteration);
            el.setAttribute('value', info);
            el.setAttribute('id', 'obs_causa_item_' + iteration);
            cellRight1.appendChild(el);
        }else{

            var motivo = $("#causa_troca_item option:selected").text();
            var obs_val = '&nbsp;';

            if (motivo == "Exceção de troca") {
                obs_val = $("#obs_causa_item").val();
            }

            var el = document.createElement('input');
            el.setAttribute('type', 'hidden');
            el.setAttribute('name', 'obs_causa_item_' + iteration);
            el.setAttribute('value',obs_val);
            el.setAttribute('id', 'obs_causa_item_' + iteration);
            cellRight1.appendChild(el);
        }

        /*if(valor_reverter_produto == 'sim'){
            var produtoOrigem = "";
            produtoOrigem = document.frm_os.obs_causa_item.value+"<br />Produto Origem: "+document.frm_os.produto_origem_referencia_item.value+" - "+document.frm_os.produto_origem_descricao_item.value;

            var el = document.createElement('input');
            el.setAttribute('type', 'hidden');
            el.setAttribute('name', 'obs_causa_item_' + iteration);
            el.setAttribute('value', produtoOrigem);
            el.setAttribute('id', 'obs_causa_item_' + iteration);
            cellRight1.appendChild(el);
        }*/

		cellRight1.appendChild(document.createTextNode(texto));

        var el = document.createElement('input');
        el.setAttribute('type', 'hidden');
        el.setAttribute('name', 'causa_troca_item_' + iteration);
        el.setAttribute('value', valor);
        el.setAttribute('id', 'causa_troca_item_' + iteration);
        cellRight1.appendChild(el);

        var tipo_atendimento = $("#tipo_atendimento").val();
        var tipo_atendimento_nome = $("#tipo_atendimento option:selected").text();
        var cellRight1 = row.insertCell(13);

        if(tipo_atendimento == 204){
            var el1 = document.createElement('input');
            var text = document.createTextNode("GAR");
            var text2 = document.createTextNode("FAT");
            el1.setAttribute('type','radio');
            el1.setAttribute('name','tipo_atendimento_item_'+iteration);
            el1.setAttribute('value','17');
            cellRight1.appendChild(el1);
            cellRight1.appendChild(text);

            var el2 = document.createElement('input');
            el2.setAttribute('type','radio');
            el2.setAttribute('name','tipo_atendimento_item_'+iteration);
            el2.setAttribute('value','18');
            cellRight1.appendChild(el2);
            cellRight1.appendChild(text2);
        }else{
            var text = document.createTextNode(tipo_atendimento_nome);
            cellRight1.appendChild(text);
            var el = document.createElement('input');
            el.setAttribute('type', 'hidden');
            el.setAttribute('name', 'tipo_atendimento_item_' + iteration);
            el.setAttribute('value', tipo_atendimento);
            el.setAttribute('id', 'tipo_atendimento_item_' + iteration);
            cellRight1.appendChild(el);
        }
<?
}
?>
	}

	function removeRowFromTable(){
		var tbl = document.getElementById('tbl');
		var lastRow = tbl.rows.length;
		if (lastRow > 2) tbl.deleteRow(lastRow - 1);
	}

	function adicionaLinha(linha){
		var tbl = document.getElementById('tbl_produto');
		var lastRow = tbl.rows.length;

		var valor_reverter_produto = $(".reverter_produto:checked").val();
		if (valor_reverter_produto == 'sim') {
			$("#produto_origem").val($("#produto_origem_referencia_item").val()+ " | " + $("#produto_origem_descricao_item").val());
        }

		if (document.frm_os.produto_referencia2.value.length == 0 || document.frm_os.produto_descricao2.value.length == 0 || document.frm_os.produto_referencia_troca2.value.length == 0 || document.frm_os.produto_descricao_troca2.value.length == 0){
			alert('Selecione o produto e produto para troca antes de clicar em adicionar');
		}else if(document.frm_os.produto_qtde.value.length == 0){
			alert('Coloque a quantidade do produto');
		}else if(document.frm_os.causa_troca_item.value == 318 && frm_os.produto_origem_referencia_item.value == 0){
			alert('Selecione o produto de origem antes de clicar em adicionar ');
		}else{
			for (i=1;i<=linha;i++) {
				addRowToTable();
				document.getElementById("total_linhas").value = tbl.rows.length - 1;
			}

            <?php if ($login_fabrica == 1) { ?>
                    $("#data_nf").prop("readonly", true);
                    $("#data_nf").datepick("destroy");
            <?php } ?>

			document.frm_os.codigo_fabricacao2.value = "";
			document.frm_os.produto_serie2.value = "";
			document.frm_os.produto_voltagem2.value = "";
            document.frm_os.type2.value = "";
			document.frm_os.causa_troca_item.value = "";
			document.frm_os.embalagem_original2[0].checked = "";
			document.frm_os.embalagem_original2[1].checked = "";
			document.frm_os.sinal_de_uso2[0].checked = "";
			document.frm_os.sinal_de_uso2[1].checked = "";
			document.frm_os.defeito_constatado_descricao2.value = "";
			document.frm_os.produto_qtde.value = "";
			document.frm_os.produto_referencia_troca2.value = "";
			document.frm_os.produto_descricao_troca2.value = "";
			document.frm_os.produto_voltagem_troca2.value = "";
			document.frm_os.produto_referencia2.value = "";
			document.frm_os.produto_descricao2.value = "";
			document.frm_os.kit_produto.value = "";
		}

	}

	function fnc_verifica_descontinuado(){	

		var tipo_atend      = $('#tipo_atendimento').val();
		var causa_troca     = $('#causa_troca_item').val();

		var multi_peca_item = $('#multi_peca_item').html(); 

		if (multi_peca_item.length == 33 && (causa_troca == '124' || causa_troca == '380' )) {
			alert('Insira a descrição da peça');

			return false;
		}


		if (!tipo_atend) {
			alert('Selecione o tipo de atendimento');
			return false;
		}
		if (!causa_troca) {
			alert('Selecione o motivo da troca');
			return false;
		}
		
		<?php if ($login_fabrica == 1) { ?>

				var campo_pecas = $("#multi_peca_item").html();
				var prateleira_box = $("[name='prateleira_box']").val();

				if (campo_pecas.length == 33 && causa_troca == 124 && prateleira_box == "nao_cadastrada") {
					
					alert('Selecione a descrição da peça');
					return false;
				}

				var rev_prod = $("input[name='reverter_produto']:checked").val();

				// Valida garantia produto origem
				if (causa_troca == 124 && rev_prod == "sim") {
					var prod_ref_origem  = $("#produto_origem_referencia_item").val();
					var data_nf = $("#data_nf").val();

					if (data_nf == "" || data_nf == undefined) {
						alert("Informe a Data da Nota");
						return false;
					}

					if (prod_ref_origem == "" || prod_ref_origem == undefined) {
						alert("Selecione o Produto de Origem");
						return false;
					}

					$.ajax({
			        type: 'POST',
			        async: false,
			        url: 'os_revenda_troca.php',
			        data: {
			            verifica_garantia_origem : true,
			            produto_referencia_origem : prod_ref_origem,
			            data_nf : data_nf
			        },
				    }).done(function(data) {
				        if (data == "ok"){
				        	valida_motivo_descontinuado();
				        } else {
				        	alert(data);
				        	return false;
				        }
				    });
				} else {
					valida_motivo_descontinuado();
				}
		<?php } else { ?>
				valida_motivo_descontinuado();
		<?php } ?>
	}

	function valida_motivo_descontinuado() {
		var produto_referencia = $("#produto_referencia2").val(); 
		var data_abertura = $('input[name="data_abertura"]').val();
		var tipo_atend = $('#tipo_atendimento').val();

		if (produto_referencia == "" || produto_referencia == undefined) {
			alert("Informe o produto");
			return false;
		}

		if(tipo_atend == 18){

			$.ajax({
		        type: 'POST',
		        dataType:"JSON",
		        url: 'os_revenda_troca.php',
		        data: {
		            verifica_descontinuado : true,
		            produto_referencia : produto_referencia,
		            data_abertura : data_abertura
		        },
		    }).done(function(data) {
		        if(data.motivo){
		        	Shadowbox.open({
						content :   "motivo_descontinuado.php?tipo_consumidor=revenda",
						player  :   "iframe",
						title   :   "Pesquisa Produto de Origem",
						width   :   800,
						height  :   300
					});
		        }else{
		    		verificaSerie();
		        }        
		    });	    
		}else{
			verificaSerie();
		}
	}

	function gravaMotivo(motivo){
		if(motivo.length > 0 ){
			$("#motivo_descontinuado").val(motivo);
		}
		verificaSerie();
	}

	function verificaSerie(){
	var tipo_atend = $('#tipo_atendimento').val();
	var causa_troca = $('#causa_troca_item').val();

	if ($('#produto_referencia2').length == 0 && $('#produto_serie2').length == 0) {
		adicionaLinha(document.frm_os.produto_qtde.value)
	}else{
		var resposta =$.ajax({
			url:'ajax_verifica_serie.php',
			data:'produto_referencia='+$('#produto_referencia2').val()+'&produto_serie='+$('#produto_serie2').val(),
			async:false,
			complete: function(respostas){
			}
		}).responseText;

		if (!tipo_atend) {
			alert('Selecione o tipo de atendimento');
			return false;
		}

		if (!causa_troca) {
			alert('Selecione o motivo da troca');
			return false;
		}

		if (resposta == 'erro' && tipo_atend == 35){
			if (confirm('Esse número de série e produto('+$('#produto_serie2').val()+' - ' +$('#produto_referencia2').val()+') foi identificado em nosso arquivo de vendas para locadoras. As locadoras têm acesso à pedido em garantia através da Telecontrol. Esse atendimento poderá ser gravado, mas irá para um relatório gerencial. Deseja prosseguir?') == true){
				adicionaLinha(document.frm_os.produto_qtde.value)
			}else{
				return false;
			}
		}else{

			adicionaLinha(document.frm_os.produto_qtde.value)
			$('#multi_peca_item').html("");
	}
}

}
</script>


<script type='text/javascript' src='js/jquery.autocomplete.js'></script>
<link rel="stylesheet" type="text/css" href="js/jquery.autocomplete.css" />

<script type="text/javascript" src="js/jquery.blockUI_2.39.js"></script>
<script type="text/javascript" src="js/plugin_verifica_servidor.js"></script>

<script language="JavaScript">
$().ready(function() {

	function formatItem(row) {
		return row[0] + " - " + row[1];
	}

	function formatResult(row) {
		return row[0];
	}

	$(".reverter_produto").change(function () {

		if($(".reverter_produto").is(":checked")){
			var valor_reverter_produto = $(this).val();
		}
		if (valor_reverter_produto == 'sim') {
            $("#produto_origem_item").show();
            $("#reverter_produto").val('sim');
        } else {
            $("#produto_origem_item").hide();
            $("#reverter_produto").val('nao');
        }
    });

	/* Busca pelo Código */
	$("#posto_codigo").autocomplete("<?echo $PHP_SELF.'?busca=codigo&busca_tipo=posto'; ?>", {
		minChars: 3,
		delay: 150,
		width: 350,
		matchContains: true,
		formatItem: formatItem,
		formatResult: function(row) {return row[0];}
	});

	$("#posto_codigo").result(function(event, data, formatted) {
		$("#posto_nome").val(data[1]) ;
		$("#posto").val(data[2]);
	});

	/* Busca pelo Nome */
	$("#posto_nome").autocomplete("<?echo $PHP_SELF.'?busca=nome&busca_tipo=posto'; ?>", {
		minChars: 3,
		delay: 150,
		width: 350,
		matchContains: true,
		formatItem: formatItem,
		formatResult: function(row) {return row[1];}
	});

	$("#posto_nome").result(function(event, data, formatted) {
		$("#posto_codigo").val(data[0]) ;
		$("#posto").val(data[2]);
	});

    $("#falta_estado").change(function () {
        if ($(this).val().length > 0) {
            $("#falta_cidade").removeAttr("readonly");
        } else {
            $("#falta_cidade").attr({"readonly": "readonly"});
        }
    });

    $("#obs_causa_item").keydown(function(event){
        if(event.which == 13){
            $("#obs_causa_item").append(
                $("#obs_causa_item").val()+"\n"
            );
        }
    });

    var extraParamEstado = {
        estado: function () {
            return $("#falta_estado").val()
        }
    };

    $("#falta_cidade").autocomplete("autocomplete_cidade_new.php", {
        minChars: 3,
        delay: 150,
        width: 350,
        matchContains: true,
        extraParams: extraParamEstado,
        formatItem: function (row) { return row[0]; },
        formatResult: function (row) { return row[0]; }
    });

    $("#falta_cidade").result(function(event, data, formatted) {
        $("#falta_cidade").val(data[0]);
    });

	$("#falta_estado_item").change(function () {
		if ($(this).val().length > 0) {
			$("#falta_cidade_item").removeAttr("readonly");
		} else {
			$("#falta_cidade_item").attr({"readonly": "readonly"});
		}
	});

	$(".motivo_falha_posto").change(function(){
		
		var motivo_falha_posto = $(".motivo_falha_posto").val();

		if(motivo_falha_posto == "atraso_colocacao_pedido" || motivo_falha_posto == "fez_pedido"){
			$(".div_pedido_falha").show();
		}else{
			$(".div_pedido_falha").hide();
		}

	});

	var extraParamEstadoItem = {
		estado: function () {
			return $("#falta_estado_item").val()
		}
	};

	$("#falta_cidade_item").autocomplete("autocomplete_cidade_new.php", {
		minChars: 3,
		delay: 150,
		width: 350,
		matchContains: true,
		extraParams: extraParamEstadoItem,
		formatItem: function (row) { return row[0]; },
		formatResult: function (row) { return row[0]; }
	});

	$("#falta_cidade_item").result(function(event, data, formatted) {
		$("#falta_cidade_item").val(data[0]);
	});

	$("select[name=prateleira_box]").change(function(){
		var status_peca = $("select[name=prateleira_box] option:selected").val();
		$('.nc').show();
		if(status_peca == 'nao_cadastrada'){
			$('#posicao').show();
			$('.nc').hide();
		}else{
			$('#posicao').hide();
		}
	});
});

	function mostraObs(campo){

		$('#div_obs_causa').css('display','none');
		$('#id_peca_avulsa').css('display','none');

        if (campo.value == '124' || campo.value == '380' ) {
            $('#id_peca_multi').css('display','block');
        }else{
            $('#id_peca_multi').css('display','none');
        }

        if (campo.value =='125' || campo.value=='128' || campo.value=='131') {

            $('#div_obs_causa').css('display','block');

            if(campo.value == '125'){
                $('#numero_bo').css('display','block');                
            }
        }else{
            $('#div_obs_causa').css('display','none');
            $('#numero_bo').css('display','none');
        }

        if (campo.value =='130') {
            $('#div_falta_posto').css('display','block');
        }else{
            $('#div_falta_posto').css('display','none');
        }

        if(campo.value == '317'){
            $('#id_peca_avulsa').css('display','block');
        }
    }

    function mostraObsItem(campo){

    	var codigo = $("#causa_troca_item option:selected").data('codigo');
    	$(".nc").show();

		$("#div_pedido").hide();
		if(codigo == 'ATS'){
			$("#div_pedido").show();
		}

		$("#display_reverter_produto").hide();
		if(campo.value.length > 0){
			$("#display_reverter_produto").show();
		}

		if(campo.value == '318'){
            $('#produto_origem_item').css('display','block');
		}else{
            $('#produto_origem_item').css('display','none');
		}

        if (campo.value == '124') {
            $("#prateleira_box").show();
        } else {
            $("#prateleira_box").hide();
        }

		if (campo.value == '124' || campo.value == 380) {
			$('#id_peca_multi_item').css('display','block');
		}else{
			$('#id_peca_multi_item').css('display','none');
		}
		// || campo.value == '318'
		if (campo.value =='125' || campo.value=='128' || campo.value=='131' || campo.value == '317' || campo.value == '316') {

			$('#div_obs_causa_item').css('display','block');

			if(campo.value == '125'){
                $('#numero_bo_item').css('display','block');
                $('.div_motivo_falha_posto').css('display','block');
			}

		}else{
			$('#div_obs_causa_item').css('display','none');
            $('#numero_bo_item').css('display','none');
            $('#produto_origem_item').css('display','none');
            $('.div_motivo_falha_posto').css('display','none');
		}

        if (campo.value =='130') {
            $('#div_falta_posto_item').css('display','block');
        }else{
            $('#div_falta_posto_item').css('display','none');
        }

		if (campo.value =='317') {
			$('#div_lista_item').css('display','block');
		}else{
			$('#div_lista_item').css('display','none');
		}

        var motivo = $("#causa_troca_item option:selected").text();

        if (motivo == "Exceção de troca") {
			$('#div_obs_causa_item').css('display','block');
        }
	}

    function addItPeca() {

        if ($('#peca_referencia_multi').val()=='') {
            return false;
        }

        if ($('#peca_descricao_multi').val()==''){
            return false;
        }

        $('#multi_peca').append("<option value='"+$('#peca_referencia_multi').val()+"'>"+$('#peca_referencia_multi').val()+"-"+ $('#peca_descricao_multi').val()+"</option>");

        if($('.select').length ==0) {
            $('#multi_peca').addClass('select');
        }

        $('#peca_referencia_multi').val("").focus();
        $('#peca_descricao_multi').val("");

    }

    function delItPeca() {
        $('#multi_peca option:selected').remove();
        if($('.select').length ==0) {
            $('#multi_peca').addClass('select');
        }

    }

    function fnc_busca_previsao(referencia){
		var retorno;
		$.ajax({
            url: "busca_previsao_peca.php",
            type: 'POST',
            async: false,
            data: {busca_previsao_peca:true, referencia_peca : referencia},
            dataType: "json",
            success: function(dados) {
                retorno = dados.previsao;
            }
        });

        return retorno;
	}

    function addItPecaItem() {

    	var status_peca = $("select[name=prateleira_box] option:selected").val();

    	var previsao = "";
    	if(status_peca == 'estoque'){
    		var referencia = $("#peca_referencia_multi_item").val();
        	previsao = fnc_busca_previsao(referencia);
    	}

    	var posicao = "";
    	if(status_peca == 'nao_cadastrada'){
    		posicao = $("select[name=posicao] option:selected").val();
    	}

        <?php if ($login_fabrica != 1) { ?>
		        if ($('#peca_referencia_multi_item').val()=='') {
		            return false;
		        }
        <?php } ?>

        if ($('#peca_descricao_multi_item').val()==''){
            return false;
        }

        if(previsao.length > 0){
        	$('#multi_peca_item').append("<option value='"+$('#peca_referencia_multi_item').val()+"'>"+$('#peca_referencia_multi_item').val()+"-"+ $('#peca_descricao_multi_item').val()+ "- "+ previsao + "</option>");
        }else if(posicao.length > 0 ){
        	<?php if ($login_fabrica == 1) { ?>
        			$('#multi_peca_item').append("<option value='"+$('#peca_descricao_multi_item').val()+"'>"+ posicao + " - " + $('#peca_descricao_multi_item').val()+"</option>");

        	<?php } else { ?>

        			$('#multi_peca_item').append("<option value='"+$('#peca_referencia_multi_item').val()+"'>"+ posicao + " - " + $('#peca_referencia_multi_item').val()+"-"+ $('#peca_descricao_multi_item').val()+"</option>");
        	<?php } ?>

        		var campos_posicao = $("#campos_posicao").val();

        		if(campos_posicao.length >0 ){
        			campos_posicao += "|";
        		}

        		campos_posicao +=  posicao + " - " + $('#peca_referencia_multi_item').val()+"-"+ $('#peca_descricao_multi_item').val();

        		$("#campos_posicao").val(campos_posicao);

        }else{
        	$('#multi_peca_item').append("<option value='"+$('#peca_referencia_multi_item').val()+"'>"+$('#peca_referencia_multi_item').val()+"-"+ $('#peca_descricao_multi_item').val()+"</option>");
        }        

        if($('.select').length ==0) {
            $('#multi_peca_item').addClass('select');
        }

        $('#peca_referencia_multi_item').val("").focus();
        $('#peca_descricao_multi_item').val("");

    }

    function delItPecaItem() {
        $('#multi_peca_item option:selected').remove();
        if($('.select').length ==0) {
            $('#multi_peca_item').addClass('select');
        }

    }

	function addItListaItem() {

		if ($('#peca_lista_item').val()==''){
			return false;
		}

		$('#multi_lista_item').append("<option value='"+$('#peca_lista_item').val()+"'>"+$('#peca_lista_item').val()+"</option>");

		if($('.select').length ==0) {
			$('#multi_lista_item').addClass('select');
		}

		$('#peca_lista_item').val("").focus();
	}

	function delItListaItem() {
		$('#multi_lista_item option:selected').remove();
		if($('.select').length ==0) {
			$('#multi_lista_item').addClass('select');
		}

	}
</script>


<style type="text/css">

#prateleira_box{ margin-bottom: 10px; font: 11px "Arial";}

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
	width: 681px;
}

.subtitulo{

	background-color: #7092BE;
	font:bold 11px Arial;
	color: #FFFFFF;
}

table.tabela tr td{
	font-family: verdana;
	font-size: 11px;

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
<?php

if (strlen ($msg_erro) > 0) {
?>
<table border="0" cellpadding="0" cellspacing="0" align="center" width="700">
<tr class="msg_erro">
	<td height="27" valign="middle" align="center">

<?
	if ( strpos($msg_erro,"É necessário informar o type para o produto") !== false OR strpos($msg_erro,"Type informado para o produto não é válido") !== false  ) {
		$sqlT =	"SELECT tbl_lista_basica.type, tbl_produto.referencia
				FROM tbl_produto
				JOIN tbl_lista_basica USING (produto)
				WHERE UPPER(tbl_produto.produto) = UPPER('$produto')
				AND   tbl_lista_basica.fabrica = $login_fabrica
				AND   tbl_produto.ativo IS TRUE
				GROUP BY tbl_lista_basica.type, tbl_produto.referencia
				ORDER BY tbl_lista_basica.type;";
		$resT = @pg_query ($con,$sqlT);
		if (@pg_num_rows($resT) > 0) {
			$s = pg_num_rows($resT) - 1;
			for ($t = 0 ; $t < pg_num_rows($resT) ; $t++) {
				$typeT = pg_fetch_result($resT,$t,type);
				$result_type = $result_type.$typeT;

				if ($t == $s) $result_type = $result_type.".";
				else          $result_type = $result_type.",";
			}
			if (strpos($msg_erro,"É necessário informar o type para o produto") !== false) $msg_erro = "É necessário informar o type para o produto ".pg_fetch_result($resT,0,referencia).".<br>";
			if (strpos($msg_erro,"Type informado para o produto não é válido") !== false) $msg_erro = "Type informado para o produto ".pg_fetch_result($resT,0,referencia)." não é válido.<br>";
			$msg_erro = "Selecione o Type: $result_type";
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

	</td>
</tr>
</table>
<?
}

	if($causa_troca=='124') {
		$display_multi_pecas= "display:inline";
	}else{
		$display_multi_pecas= "display:none";
	}

	if(in_array($causa_troca,array(125,128,131))) {
		$display_obs_causa= "display:inline";
	}else{
		if($causa_troca == '316' || $causa_troca == '317'){
			$display_obs_causa= "display:block";
		}else{
			$display_obs_causa= "display:none";
		}

	}

	if($causa_troca=='130') {
		$display_falta_posto= "display:inline";
	}else{
		$display_falta_posto= "display:none";
	}
?>
<table width="700" border="0" cellpadding="3" cellspacing="3" align="center"  class="texto_avulso">
	<tr>
		<td nowrap>ATENÇÃO: <br>As OS digitadas neste módulo só terão validade após clicar em gravar e depois em explodir</td>
	</tr>
</table>

<form name="frm_os" id="frm_os" method="POST" action="<? echo $PHP_SELF ?>" enctype="multipart/form-data">
<table width="700" border="0" cellpadding="0" cellspacing="0" align="center"  class="formulario">
	<tr class="titulo_tabela"><td colspan="3">Cadastrar Ordem de Serviço de Troca - Revenda</td></tr>
	<tr><td colspan='3'>&nbsp;</td></tr>
	<tr>
		<td colspan="2" align="center">

			<a href="mostra_valor_troca_faturada.php" style="font-size: 15px;" target="__blank"> CONSULTAR VALOR DE TROCA DE PRODUTOS</a>

		</td>
	</tr>
	<tr>
		<td colspan='3'>
			<table width="100%" border='0' cellpadding='1' cellspacing='2' align='center' class='formulario'>

			 <tr class="subtitulo" style="font-size:14px;" ><td colspan="13" align="center">Informações Revenda</td></tr>
			</table>
		</td>
	</tr>
	<tr >
		<td><img height="1" width="5" src="imagens/spacer.gif"></td>
		<td valign="top" align="left">

			<!-- ------------- Formulário ----------------- -->

			<table width="100%" border="0" cellspacing="3" cellpadding="2" class="formulario">
			<input type='hidden' name='os_revenda' value='<? echo $os_revenda; ?>'>
			<input name="sua_os" type="hidden" value="<? echo $sua_os ?>">
			<input name="posto"  type="hidden" value="<?=$posto?>" id="posto">
			<input name="produto_origem" id="produto_origem" type="hidden" value="<? echo $produto_origem ?>">
			<input name="reverter_produto" id="reverter_produto" type="hidden" value="<? echo $reverter_produto ?>">
                <tr >
					<td nowrap colspan='2'>
					Código do Posto
					</td>
					<td nowrap colspan='2'>Nome do Posto</td>
                </tr>
                <tr>
                    <td nowrap colspan='2'>
                    <?php
                    if ($login_fabrica == '1') {
                        echo '<input type="hidden" id="confirmouPosto" name="confirmouPosto" value="f" />';
                    }
                    ?>
                    <input class="frm" type="text" id="posto_codigo" name="posto_codigo" size="15" value="<? echo $posto_codigo ?>" >
					<img border="0" src="imagens/lupa.png" style="cursor: pointer;" align="absmiddle" alt="Clique aqui para pesquisar postos pelo código" onclick="javascript: pesquisaPosto (document.frm_os.posto_codigo, 'codigo');">
					</td>
					<td nowrap colspan='2'>
						<input class="frm" id="posto_nome" type="text" name="posto_nome" size="30" value="<? echo $posto_nome ?>">
					<img border="0" src="imagens/lupa.png" style="cursor: pointer;" align="absmiddle" alt="Clique aqui para pesquisar postos pelo código" onclick="javascript: pesquisaPosto (document.frm_os.posto_nome, 'nome');">
					</td>

				</tr>
				<tr >
					<td nowrap  colspan='2'>Data Abertura
					</td>
					<td nowrap width="125">Nota Fiscal
					</td>
					<td nowrap>Data Nota
					</td>
				</tr>
                <tr>
                    <td nowrap colspan='2'>
                        <input class="frm" type="text" name="data_abertura" size="15" value="<? if (strlen($data_abertura) == 0) $data_abertura = date("d/m/Y"); echo $data_abertura; ?>" readonly>
                    </td>
                    <td nowrap >
                        <input name="nota_fiscal" size="8" maxlength="14" id="nota_fiscal" value="<? echo $nota_fiscal ?>" type="text" class="frm" tabindex="0" >
                    </td>
                    <td nowrap >
                        <input name="data_nf" id='data_nf' size="12" maxlength="10"value="<? echo $data_nf ?>" type="text" class="frm datapicker" tabindex="0" >
                    </td>
                </tr>
				<tr >

<?
if($login_fabrica != 1){
?>
					<td nowrap colspan='4'>Motivo da Troca</td>
<?
}
?>
				</tr>
				<tr>
<?
if($login_fabrica != 1){
?>
					<td nowrap colspan='4'>
						<select name="causa_troca" size="1" style='width:200px; height=18px;' onchange='mostraObs(this)' class="frm">
						<option value=""></option>
						<?
						$sql = "SELECT causa_troca,descricao
								FROM tbl_causa_troca
								WHERE fabrica = $login_fabrica
								AND   tipo in ('T','R')
								AND   ativo
								ORDER BY descricao";
						$res = pg_query ($con,$sql) ;
						for ($i = 0 ; $i < pg_num_rows ($res) ; $i++ ) {

							echo "<option ";
							if ($causa_troca == pg_fetch_result ($res,$i,causa_troca) ) echo " selected ";
							echo " value='" . pg_fetch_result ($res,$i,causa_troca) . "'>" ;
							echo pg_fetch_result ($res,$i,descricao) ;
							echo "</option>";
						}
						?>
						</select>

					</td>
<?
}
?>
				</tr>
			</table>



			<div id='id_peca_multi' style='<?echo $display_multi_pecas;?>'>
			<table class='formulario' width="100%" border="0" cellspacing="3" cellpadding="2" >
				<tr>
					<td width='268'>
						Referência <br /><input class='frm' type="text" name="peca_referencia_multi"  id="peca_referencia_multi" value="" size="15" maxlength="20">&nbsp;<IMG src='imagens/lupa.png' onClick="javascript: pesquisaPeca (document.frm_os.peca_referencia_multi,'referencia','total')"  style='cursor:pointer;'>
					</td>

					<td width='250'>
						Descrição <br /><input class='frm' type="text" name="peca_descricao_multi" id="peca_descricao_multi" value="" size="30" maxlength="50">&nbsp;<IMG src='imagens/lupa.png' onClick="javascript: pesquisaPeca(document.frm_os.peca_descricao_multi,'descricao','total')"  style='cursor:pointer;' align='absmiddle'>
					</td>

					<td>
						<input type='button' name='adicionar_peca' id='adicionar_peca' value='Adicionar' class='frm' onClick='addItPeca();'>
					</td>
				</tr>
				<tr>
					<td colspan='3'>
						<select multiple="multiple" SIZE='6' id='multi_peca' name="multi_peca[]" class='frm' style='width:610px'>
						<?
							if(count($multi_peca) > 0) {
								for($i =0;$i<count($multi_peca);$i++) {
									if(!empty($os_revenda)) {
										$xmulti_peca = explode(" - ",$multi_peca[$i]);
										$sql = " SELECT tbl_peca.referencia,
													tbl_peca.descricao
											FROM tbl_peca
											WHERE fabrica = $login_fabrica
											AND   referencia  = '".$xmulti_peca[0]."'";
									}else{
										$sql = " SELECT tbl_peca.referencia,
														tbl_peca.descricao
												FROM tbl_peca
												WHERE fabrica = $login_fabrica
												AND   referencia  = '".$multi_peca[$i]."'";
									}
									$res = pg_query($con,$sql);
									if(pg_num_rows($res) > 0){
										$referencia = pg_fetch_result($res,0,referencia);
										if(!empty($referencia)) {
											echo "<option value='".$referencia."' >".$referencia . " - " . pg_fetch_result($res,0,descricao) ."</option>";
										}

									}
								}
							}
						?>
						</select>
						<br>
						<input TYPE="BUTTON" VALUE="Remover" onClick="delItPeca();" class='frm'></input>
						<strong style='font-weight:normal;color:gray;font-size:10px; float:right;'>(Selecione a peça e clique em 'Adicionar')</strong>

					</td>
				</tr>
			</table>
			</div>
			<div id='div_obs_causa' style='<?echo $display_obs_causa;?>'>
				<table class='formulario' width="100%" border="0" cellspacing="3" cellpadding="2">
					<tr>
						<td>
							Justificativa <br />
							<textarea name="obs_causa" id="obs_causa" rows="4" cols="80" class='frm'><?=$obs_causa?></textarea>
						</td>
					</tr>
				</table>
			</div>

			<input type="hidden" name="produto_troca_garantia_faturada">
			<input type="hidden" name="motivo_descontinuado" id="motivo_descontinuado" value="<?=utf8_decode($motivo_descontinuado)?>">

			<div id="numero_bo" style="display: none ;">
				<table class='formulario' width="100%" border="0" cellspacing="3" cellpadding="2">
					<tr>
						<td>
							Informe o Número de B.O. <br />
							<input type="text" id="numero_bo_info" class="frm" /> <span id="desc_bo"></span>
						</td>
					</tr>
				</table>
			</div>

			<div id='div_falta_posto' style='<?=$display_falta_posto?>'>
				<table class='formulario' width="100%" border="0" cellspacing="3" cellpadding="2">
					<tr><td colspan='2'>Informe a cidade e o Estado</td></tr>
					<tr>
						<td>
							 Estado <br />
							 <select name="falta_estado" id='falta_estado' style='width:81px;' class='frm'>
							<? $ArrayEstados = array('','AC','AL','AM','AP',
														'BA','CE','DF','ES',
														'GO','MA','MG','MS',
														'MT','PA','PB','PE',
														'PI','PR','RJ','RN',
														'RO','RR','RS','SC',
														'SE','SP','TO'
													);
							for ($i=0; $i<=27; $i++){
								echo"<option value='".$ArrayEstados[$i]."'";
								if ($falta_estado == $ArrayEstados[$i]) echo " selected";
								echo ">".$ArrayEstados[$i]."</option>\n";
							}?>
						</select>
						</td>

						<td>
							Cidade <br />
							<input type='text' <?=(!strlen($falta_estado)) ? "readonly" : ""?> name='falta_cidade' id='falta_cidade' value='<?=$falta_cidade?>' class='frm'>
						</td>


					</tr>
				</table>
			</div>
			<table width="100%" border="0" cellspacing="3" cellpadding="2" class="formulario">
				<tr >
					<td>Nome Revenda</td>
					<td>CNPJ Revenda</td>
                    <td nowrap>Autorização</td>
				</tr>

				<tr>
					<td >
						<input class="frm" type="text" name="revenda_nome" size="24" maxlength="50" value="<? echo $revenda_nome ?>" onkeyup="somenteMaiusculaSemAcento(this)">&nbsp;<img src='imagens/lupa.png' border='0' align='absmiddle' onclick='javascript: pesquisaRevenda (document.frm_os.revenda_nome, "nome")' style='cursor:pointer;'>
					</td>
					<td >
						<input class="frm" type="text" name="revenda_cnpj" size="20" maxlength="14" value="<? echo $revenda_cnpj ?>">&nbsp;<img src='imagens/lupa.png' border='0' align='absmiddle' onclick='javascript: pesquisaRevenda (document.frm_os.revenda_cnpj, "cnpj")' style='cursor:pointer;'>
					</td>
                    <td nowrap ><?// HD 221627?>
                        <select name="admin_autoriza" size="1" style='width:200px; height=18px;' class="frm">
                        <option selected></option>
                        <option value="<?php echo $login_admin;?>">Próprio usuário</option>
                        <?
                        $sql = "SELECT admin,nome_completo
                                FROM tbl_admin
                                WHERE fabrica = $login_fabrica
                                    AND ativo = 't'  /* HD 944675 - Retirado o admin 257 - Miguel Pereira, deixando apenas usuários ativo = 't' */
                                    AND admin IN(112, 626, 155,2655,2967,5043)
                                ORDER BY nome_completo";
                        $res = pg_query ($con,$sql) ;
                        for ($i = 0 ; $i < pg_num_rows ($res) ; $i++ ) {

                            echo "<option ";
                            if ($admin_autoriza == pg_fetch_result ($res,$i,admin) ) echo " selected ";
                            echo " value='" . pg_fetch_result ($res,$i,admin) . "'>" ;
                            echo pg_fetch_result ($res,$i,nome_completo) ;
                            echo "</option>";
                        }
                        ?>
                        </select>
                    </td>
				</tr>
				<tr>
					<td>Fone Revenda</td>
					<td colspan="2">E-mail Revenda</td>
				</tr>
					<tr>
					<td >
						<input class="frm" type="text" name="revenda_fone" id='revenda_fone' size="11"  maxlength="20"  value="<? echo $revenda_fone ?>" >
					</td>
					<td colspan="2">
						<input class="frm" type="text" name="revenda_email" size="50" maxlength="50" value="<? echo $revenda_email ?>" tabindex="0">
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

			<table width="100%" border="0" cellspacing="3" cellpadding="2" class="formulario" id="input_anexos">
				<tr >
					<td>Observações</td>
				</tr>
				<tr>
					<td>
						<input class="frm" type="text" name="obs" size="68" value="<? echo $obs ?>">
					</td>
				</tr>
				<tr>
					<td align="center">
						<?php

                        $anexoTpl = '';

						if($login_fabrica == 1){
                            $inputNotaFiscalTpl = str_replace('foto_nf', 'foto_nf[@ID@]', $inputNotaFiscal);
                            $anexoTpl = '
                                <tr id="anexoTpl" style="display: none">
                                    <td align="center">
                                        ' . $inputNotaFiscalTpl . '
                                    </td>
                                </tr>
                                ';

							if ($anexaNotaFiscal) {
								if ($os) {
									echo (temNF($os, 'bool')) ? "<h1>Imagem anexa</h1>" . temNF($os) . $include_imgZoom : $inputNotaFiscal;
								} else {
                                    echo str_replace('@ID@', '0', $inputNotaFiscalTpl);
								}
							} else {
                                echo str_replace('@ID@', '0', $inputNotaFiscalTpl);
							}
                            echo '<input type="hidden" id="qtde_anexos" name="qtde_anexos" value="1" />';
						} else {
							if ($anexaNotaFiscal) {
							if ($os_revenda)
								$temAnexos = temNF("r_$os_revenda", 'count');

							if ($temAnexos)
								echo temNF('r' . $os_revenda, 'link') . $include_imgZoom;

							if (($anexa_duas_fotos and $temAnexos < LIMITE_ANEXOS) or $temAnexos == 0)
									echo  $inputNotaFiscal;
								}
						}
						?>
					</td>
                </tr>

                <?php echo $anexoTpl ?>

			</table>
            <?php
            if ($login_fabrica == '1') {
                echo '<div align="center"><input value="Adicionar novo arquivo" onclick="addAnexoUpload()" type="button"></div>';
            }
            ?>



<?
if (strlen($os_revenda) > 0) {
	$sql = "SELECT      tbl_produto.produto
			FROM        tbl_os_revenda_item
			JOIN        tbl_produto   USING (produto)
			JOIN        tbl_os_revenda USING (os_revenda)
			WHERE       tbl_os_revenda_item.os_revenda = $os_revenda
			ORDER BY    tbl_os_revenda_item.os_revenda_item";
	$res_os = pg_query ($con,$sql);
}

// monta o FOR

if($btn_acao =='gravar') {
	$qtde_item = $_POST['total_linhas'];
}
?>
<input class='frm' type='hidden' name='qtde_item' value='<?=$qtde_item?>'>
<input type="hidden" name="campos_posicao" id="campos_posicao" value="<?=$xcampos_posicao?>" >
<input type='hidden' name='btn_acao' value=''>
<input type='hidden' name='total_linhas' id='total_linhas' value='<?=$qtde_item?>'>

<?
if((strlen($os_revenda) == 0 AND $btn_acao <> 'gravar')  or $qtde_item < 0 or (empty($qtde_item) AND !empty($msg_erro)) ) {
?>
<table  width="100%" border="0" cellspacing="3" cellpadding="2" class='formulario' >
	<tr><td colspan='3'>&nbsp;</td></tr>
	<tr>
        <td colspan='3'>
            <table  width="100%" border="0" cellspacing="3" cellpadding="2" class='formulario' >
                <tr class="subtitulo" style="font-size:14px;" >
                    <td colspan="13" align="center">Dados Produto/Troca</td>
                </tr>
            </table>
        </td>
	</tr >
	<tr >
        <td nowrap>Cod. Fabricação</td>
        <td nowrap>Número de Série</td>
<?
if($login_fabrica == 1){
?>
        <td nowrap valign='top'>Tipo de Atendimento</td>
<?
}
?>
	</tr>

	<tr>
        <td nowrap>
            <input class='frm' type='text' id='codigo_fabricacao2' name='codigo_fabricacao2' size='9' maxlength='20' value='<?=$codigo_fabricacao?>'>
        </td>
        <td nowrap>
            <input class='frm' type='text' id='produto_serie2' name='produto_serie2'  size='10'  maxlength='20' value='<?=$serie?>'>&nbsp;<img src='imagens/lupa.png' border='0' align='absmiddle' onclick="javascript: pesquisaSerie (document.frm_os.produto_referencia2,document.frm_os.produto_descricao2,document.frm_os.produto_serie2);" style='cursor:pointer;'>
        </td>
<?
if($login_fabrica == 1){
?>
        <td nowrap>
            <select name="tipo_atendimento" id="tipo_atendimento" onchange="tipoatendimento(this.value)" size="1" style='width:200px; height=18px;' class="frm">
                <option></option>
                <?
                // hd 15197
                $sql = "SELECT *
                        FROM tbl_tipo_atendimento
                        WHERE fabrica = $login_fabrica
                        AND   tipo_atendimento IN (17,18,35)
                        ORDER BY tipo_atendimento";

                $res = pg_query ($con,$sql) ;
                for ($i = 0 ; $i < pg_num_rows ($res) ; $i++ ) {

                    echo "<option ";
                    if ($tipo_atendimento == pg_fetch_result ($res,$i,tipo_atendimento) ) echo " selected ";
                    echo " value='" . pg_fetch_result ($res,$i,tipo_atendimento) . "'>" ;
                    echo pg_fetch_result ($res,$i,descricao) ;
                    echo "</option>";
                }
                ?>
            </select>
        </td>
<?
}
?>

	</tr>
<?php
if ($login_fabrica == 1) {
?>
    <tr>
        <td colspan="3">
            <label id="OrdemDeServicoSemNF" style="display:none" >
                <b>OS sem Nota Fiscal</b>
            </label>
            <input id="TDOrdemDeServicoSemNF" style="display:none" type="checkbox" <?php if (isset($_POST["TDOrdemDeServicoSemNF"])) { echo 'checked="checked"'; }?>    name="TDOrdemDeServicoSemNF" value="1" >
        </td>
    </tr>
<?php
}
?>
	<tr>
        <td>Produto</td>
        <td colspan='2'>Descrição do Produto</td>
	</tr>

	<TR>
        <td nowrap>
            <input class='frm' type='text' id='produto_referencia2' name='produto_referencia2' size='15' maxlength='50' value='<?=$referencia_produto?>'  >&nbsp;<img src='imagens/lupa.png' border='0' align='absmiddle' onclick="javascript: pesquisaProduto(document.frm_os.produto_referencia2,'referencia','',document.frm_os.posto);" style='cursor:pointer;'>
        </td>
        <td nowrap colspan='2'>
            <input class='frm' type='text' name='produto_descricao2' size='30' maxlength='50' value='<?=$produto_descricao?>' id='produto_descricao2'>&nbsp;<img src='imagens/lupa.png' border='0' align='absmiddle' onclick="javascript: pesquisaProduto (document.frm_os.produto_descricao2,'descricao','',document.frm_os.posto);" style='cursor:pointer;'>
        </td>
	</TR>

	<TR>
        <td>Voltagem</TD>
        <TD>Type</td>
<?
if($login_fabrica == 1){
?>
        <td>Motivo da Troca</td>
	</TR>

	<tr>
        <td ><input class='frm' type='text' name='produto_voltagem2' size='5' value='<?=$produto_voltagem?>' id='produto_voltagem2'></td>
        <td>
		    <?
		     GeraComboType::makeComboType($parametrosAdicionaisObject, $type, "type2", array("class"=>"frm"));
      		     echo GeraComboType::getElement();
		    ?>
		</td>
<?
}
if($login_fabrica == 1){
?>
		<td>
            <select name="causa_troca_item" size="1" id="causa_troca_item" style='width:200px; height=18px;' onchange='mostraObsItem(this)' class="frm">
            <option selected></option>
            <?
            $sql = "SELECT  causa_troca AS causa_troca_item,
                            descricao,
                            codigo
                    FROM    tbl_causa_troca
                    WHERE   fabrica = $login_fabrica
                    AND     tipo in ('T','R')
                    AND     ativo
              ORDER BY      descricao";
            $res = pg_query ($con,$sql) ;
            for ($i = 0 ; $i < pg_num_rows ($res) ; $i++ ) {
            	
            	$codigo = trim(pg_fetch_result($res, $i, codigo));
            	if(empty($codigo)){
            		$codigo = "";
            	}

                echo "<option ";
                if ($causa_troca_item == pg_fetch_result ($res,$i,causa_troca_item) ) echo " selected ";
                echo " value='" . pg_fetch_result ($res,$i,causa_troca_item) . "' 
                data-codigo='$codigo'>" ;
                echo pg_fetch_result ($res,$i,descricao) ;
                echo "</option>";
            }
            ?>
            </select>
        </td>
<?
}
?>
	</tr>
	</table>
<?
if(in_array($causa_troca_item,array(124))) {
    $display_multi_pecas_item = "display:inline";
}else{
    $display_multi_pecas_item = "display:none";
}

if(in_array($causa_troca_item,array(125,128,131,317,316,318))) {
    $display_obs_causa_item = "display:inline";
    if($causa_troca_item == 318){
        $display_produto_origem_item = "display:inline";
    }else{
        $display_produto_origem_item = "display:none";
    }
}else{
    $display_obs_causa_item         = "display:none";
    $display_produto_origem_item    = "display:none";
}

if($causa_troca_item == 130) {
    $display_falta_posto_item= "display:inline";
}else{
    $display_falta_posto_item = "display:none";
}

if($causa_troca_item == 317) {
    $display_lista_item= "display:inline";
}else{
    $display_lista_item = "display:none";
}
?>
    <div id="display_reverter_produto" style="display: <?=$display_reverter_produto ?>">
    	<table class='formulario'  width="250" border="0" cellspacing="3" cellpadding="2">
	    	<tr>
				<td>Reverter Produto <br /> 
					<input type="radio" class="reverter_produto" name="reverter_produto" value="sim" <?if($reverter_produto == 'sim'){echo " checked ";} ?> >Sim 
					<input type="radio" class="reverter_produto" name="reverter_produto" value="nao" <?if($reverter_produto == 'nao' OR $reverter_produto == "" ){echo " checked ";} ?> >Não
				</td>
			</tr>
		</table>
    </div>
	<div id='produto_origem_item' style='<?echo $display_obs_causa_item;?>'>
        <table class='formulario'  width="100%" border="0" cellspacing="3" cellpadding="2">
            <tr>
                <td>Produto Origem <br /><input class='frm' type='text' id='produto_origem_referencia_item' name='produto_origem_referencia_item' size='15' maxlength='50' value='<?=$produto_origem_referencia_item?>'  >&nbsp;<img src='imagens/lupa.png' border='0' align='absmiddle' onclick='javascript: pesquisaProdutoOrigem(document.frm_os.produto_origem_referencia_item,"origem_referencia")' style='cursor:pointer;'></td>
                <td>Referência Origem<br /><input class='frm' type='text' name='produto_origem_descricao_item' size='30' maxlength='50' value='<?=$produto_origem_descricao_item?>' id='produto_origem_descricao_item'>&nbsp;<img src='imagens/lupa.png' border='0' align='absmiddle' onclick='javascript: pesquisaProdutoOrigem (document.frm_os.produto_origem_descricao_item,"origem_descricao")' style='cursor:pointer;'></td>
            </tr>
        </table>
    </div>
	<div id='id_peca_multi_item' style='<?echo $display_multi_pecas_item;?>'>	
    <table class='formulario'  width="100%" border="0" cellspacing="3" cellpadding="2">
    	<?php if ($login_fabrica != 1) { ?>
	    	<tr>
	    		<td width='268'>
	            	Status das Peças na OS <br />
	            	<select  name="prateleira_box"  class="frm">
	                    <option value="" selected="selected"></option>
	                    <option value="obsoleto" <?php if($_POST['prateleira_box']== "obsoleto"){ echo " selected ";} ?>>Obsoleto</option>
	                    <option value="impinat" <?php if($_POST['prateleira_box']== "impinat"){ echo " selected ";} ?>>Impinat</option>
	                    <option value="indispl" <?php if($_POST['prateleira_box']== "indispl"){ echo " selected ";} ?>>Indispl</option>

	                    <option value="estoque" <?php if($_POST['prateleira_box']== "estoque"){ echo " selected ";} ?>>Estoque</option>
	                    <option value="nao_cadastrada" <?php if($_POST['prateleira_box']== "nao_cadastrada"){ echo " selected ";} ?>>Não Cadastrada</option>
	                    <option value="cabo_eletrico" <?php if($_POST['prateleira_box']== "cabo_eletrico"){ echo " selected ";} ?>>Cabo Elétrico</option>
	                </select>
	            </td>
	            <td id="posicao" style="display: none">
	            	Posição <br>
	            	<select name="posicao" class="frm">
	            		<?php for($pos=1; $pos <=400; $pos++){
	            			echo "<option value='$pos'>$pos</option>";
	            		 }  ?>
	            	</select>
	            </td>
	    	</tr>
	    <?php } ?>
    	<?php if ($login_fabrica == 1) { ?>
	    	<tr>
	    		<td width='268'>
	            	Status das Peças na OS <br />
	            	<select  name="prateleira_box"  class="frm">
	                    <option value="" selected="selected"></option>
	                    <option value="obsoleto" <?php if($_POST['prateleira_box']== "obsoleto"){ echo " selected ";} ?>>Obsoleto</option>
	                    <option value="impinat" <?php if($_POST['prateleira_box']== "impinat"){ echo " selected ";} ?>>Impinat</option>
	                    <option value="indispl" <?php if($_POST['prateleira_box']== "indispl"){ echo " selected ";} ?>>Indispl</option>

	                    <option value="estoque" <?php if($_POST['prateleira_box']== "estoque"){ echo " selected ";} ?>>Estoque</option>
	                    <option value="nao_cadastrada" <?php if($_POST['prateleira_box']== "nao_cadastrada"){ echo " selected ";} ?>>Não Cadastrada</option>
	                    <option value="cabo_eletrico" <?php if($_POST['prateleira_box']== "cabo_eletrico"){ echo " selected ";} ?>>Cabo Elétrico</option>
	                </select>
	            </td>
	            <td id="posicao" style="display: none">
	            	Posição <br>
	            	<select name="posicao" class="frm">
	            		<?php for($pos=1; $pos <=400; $pos++){
	            			echo "<option value='$pos'>$pos</option>";
	            		 }  ?>
	            	</select>
	            </td>
	    	</tr>
	    <?php } ?>
        <tr>
            <td width='268' class="nc">
                Referência <br /><input class='frm' type="text" name="peca_referencia_multi_item"  id="peca_referencia_multi_item" value="" size="15" maxlength="20">&nbsp;<IMG src='imagens/lupa.png' onClick="javascript: pesquisaPeca (document.frm_os.peca_referencia_multi_item,'referencia','item', <?=($login_fabrica == 1)? "document.frm_os.prateleira_box": "''" ?>)"  style='cursor:pointer;'>
            </td>

            <td width='250'>
            	<div id="lbl_descricao">Descrição</div>
            	<script type="text/javascript">
            		<?php if ($login_fabrica == 1) { ?> 
	            		$(function() { 

	            			$("[name='prateleira_box']").change(function() {
		            	
		            			if ($("[name='prateleira_box']").val() == "nao_cadastrada") {

		            				$("#lbl_descricao").html('<div style="color: red;">Descrição * </div>');
		            			} else {
		            				$("#lbl_descricao").html('<div">Descrição</div>');
		            			} 
		            		});
	            		});
					<?php } ?>  
            	</script>
            	
                
                		
            	
                <br /><input class='frm' type="text" name="peca_descricao_multi_item" id="peca_descricao_multi_item" value="" size="30" maxlength="50">&nbsp;<IMG class='nc' src='imagens/lupa.png' onClick="javascript: pesquisaPeca(document.frm_os.peca_descricao_multi_item,'descricao','item', <?=($login_fabrica == 1)? "document.frm_os.prateleira_box": "''" ?>)"  style='cursor:pointer;' align='absmiddle'>
            </td>

            <td>
                <input type='button' name='adicionar_peca' id='adicionar_peca' value='Adicionar' class='frm' onClick='addItPecaItem();'>
            </td>
        </tr>
        <tr>
            <td colspan='3'>


                <select multiple="multiple" SIZE='6' id='multi_peca_item' name="multi_peca_item[]" class='frm' style='width:610px'>
                <?
                    if(count($multi_peca_item) > 0) {
                        for($i =0;$i<count($multi_peca_item);$i++) {
                            if(!empty($os_revenda)) {
                                $xmulti_peca_item = explode(" - ",$multi_peca_item[$i]);
                                $sql = "SELECT  tbl_peca.referencia,
                                                tbl_peca.descricao
                                        FROM    tbl_peca
                                        WHERE   fabrica     = $login_fabrica
                                        AND     referencia  = '".$xmulti_peca_item[0]."'";
                            }else{
                                $sql = " SELECT tbl_peca.referencia,
                                                tbl_peca.descricao
                                        FROM    tbl_peca
                                        WHERE   fabrica = $login_fabrica
                                        AND     referencia  = '".$multi_peca_item[$i]."'";
                            }
                            $res = pg_query($con,$sql);
                            if(pg_num_rows($res) > 0){
                                $referencia = pg_fetch_result($res,0,referencia);
                                if(!empty($referencia)) {
                                    echo "<option value='".$referencia."' >".$referencia . " - " . pg_fetch_result($res,0,descricao) ."</option>";
                                }

                            }
                        }
                    }
                ?>
                </select>
                <br>
                <input TYPE="BUTTON" VALUE="Remover" onClick="delItPecaItem();" class='frm'></input>
                <strong style='font-weight:normal;color:gray;font-size:10px; float:right;'>(Selecione a peça e clique em 'Adicionar')</strong>

            </td>
        </tr>
    </table>
    </div>

    <div id='div_obs_causa_item' style='<?echo $display_obs_causa_item;?>'>
        <table class='formulario'  width="100%" border="0" cellspacing="3" cellpadding="2">
        	<tr>
        		<td class="div_motivo_falha_posto" style="display: none">
    				Motivo Falha do Posto: <br>
					<select name="motivo_falha_posto" class="frm motivo_falha_posto" > 
						<option value=""></option>
						<option value="demora_reparo" <?=$chkd_demora ?>>Demora no reparo</option>
						<option value="nao_fez_pedido" <?=$chkd_nao_fez ?>>Não fez pedido</option>
						<option value="atraso_colocacao_pedido" <?=$chkd_atraso ?>>Atraso colocação do pedido</option>
						<option value="fez_pedido" <?=$chkd_fez ?>>Fez pedido, mas peça não foi enviada/atrasou</option>
					</select>
        		</td>
        		<td class="div_pedido_falha" style="display: none">
        			Número Pedido: <br>
					<input type="text" name="pedido_motivo_falha_posto" class="pedido_motivo_falha_posto frm " maxlength="10" value="<?=$pedido_motivo_falha_posto?>" >
        		</td>
        	</tr>
            <tr>
                <td colspan="2">
                    Justificativa <br />
                    <textarea name="obs_causa_item" id="obs_causa_item" rows="4" cols="80" class='frm' ><?=$obs_causa_item?></textarea>
                </td>
            </tr>
        </table>
    </div>
    <div id='div_pedido' style="display: none">
        <table class='formulario'  width="100%" border="0" cellspacing="3" cellpadding="2">
            <tr>
                <td>
                    Pedido <br />
                    <input type="text" name="pedido_item" id="pedido_item" class="frm" maxlength="10" value="<?=$num_pedido?>">
                </td>
            </tr>
        </table>
    </div>

    

    <div id="div_lista_item" style='<?=$display_lista_item?>'>
        <table class='formulario'  width="100%" border="0" cellspacing="3" cellpadding="2">
            <tr>
                <td width='250'>
                    Descrição <br /><input class='frm' type="text" name="peca_lista_item" id="peca_lista_item" value="" size="30" maxlength="50">
                </td>
                <td style="vertical-align:bottom;">
                    <input type='button' name='adicionar_lista' id='adicionar_lista' value='Adicionar' class='frm' onClick='addItListaItem();'>
                </td>
            </tr>
            <tr>
            <td colspan='2'>
                <select multiple="multiple" SIZE='6' id='multi_lista_item' name="multi_lista_item[]" class='frm' style='width:610px'>
                <br>
                <input TYPE="BUTTON" VALUE="Remover" onClick="delItListaItem();" class='frm'></input>
                <strong style='font-weight:normal;color:gray;font-size:10px; float:right;'>(Selecione a peça e clique em 'Adicionar')</strong>
            </td>
        </table>
    </div>

    <div id="numero_bo_item" style="display: none;">
        <table class='formulario'  width="100%" border="0" cellspacing="3" cellpadding="2">
            <tr>
                <td>
                    Informe o Número de B.O. <br />
                    <input type="text" id="numero_bo_info_item" class="frm" /> <span id="desc_bo_item"></span>
                </td>
            </tr>
        </table>
    </div>

    <div id='div_falta_posto_item' style='<?=$display_falta_posto_item?>'>
        <table class='formulario'  width="100%" border="0" cellspacing="3" cellpadding="2">
            <tr><td colspan='2'>Informe a cidade e o Estado</td></tr>
            <tr>
                <td>
                    Estado <br />
                    <select name="falta_estado_item" id='falta_estado_item' style='width:81px;' class='frm'>
                    <? $ArrayEstados = array('','AC','AL','AM','AP',
                                                'BA','CE','DF','ES',
                                                'GO','MA','MG','MS',
                                                'MT','PA','PB','PE',
                                                'PI','PR','RJ','RN',
                                                'RO','RR','RS','SC',
                                                'SE','SP','TO'
                                            );
                    for ($i=0; $i<=27; $i++){
                        echo"<option value='".$ArrayEstados[$i]."'";
                        if ($falta_estado_item == $ArrayEstados[$i]) echo " selected";
                        echo ">".$ArrayEstados[$i]."</option>\n";
                    }?>
                </select>
                </td>

                <td>
                    Cidade <br />
                    <input type='text' <?=(!strlen($falta_estado_item)) ? "readonly" : ""?> name='falta_cidade_item' id='falta_cidade_item' value='<?=$falta_cidade_item?>' class='frm'>
                </td>
            </tr>
        </table>
    </div>

    <table class='formulario'  width="100%" border="0" cellspacing="3" cellpadding="2">
    <?

	echo "<tr>";
	echo "<input type='hidden' name='novo_$i' value='$novo'>\n";
	echo "<input type='hidden' name='item_$i' value='$os_revenda_item'>\n";


		?>

		<td  nowrap>
			<fieldset style="width:120px;">
			<legend>Embalagem Original</legend>
			<input class='frm' type="radio" name="embalagem_original2" value="t" <? echo ($embalagem_original2 == "t") ? "CHECKED" : "";?>>
			<font size='1' face='Verdana, Tahoma, Geneva, Arial, Helvetica, san-serif'>Sim</font>
			<input class='frm' type="radio" name="embalagem_original2" value="f" <? echo ($embalagem_original2 == "f") ? "CHECKED" : "";?>>
			<font size='1' face='Verdana, Tahoma, Geneva, Arial, Helvetica, san-serif'>Não</font>
			</fieldset>

		</td>
		<td  nowrap>
			<fieldset style="width:120px;">
			<legend>Sinal de Uso</legend>
			<input class='frm' type="radio" name="sinal_de_uso2" value="t" <? echo ($sinal_de_uso2 == "t") ? "CHECKED" : "";?>>
			<font size='1' face='Verdana, Tahoma, Geneva, Arial, Helvetica, san-serif'>Sim</font>
			<input class='frm' type="radio" name="sinal_de_uso2" value="f" <? echo ($sinal_de_uso2 == "f") ? "CHECKED" : "";?>>
			<font size='1' face='Verdana, Tahoma, Geneva, Arial, Helvetica, san-serif'>Não</font>
			</fieldset>
		</td>
		<td  nowrap>
		Defeito Constatado<br>
		<input class='frm' type='text' name='defeito_constatado_descricao2' size ="20" maxlength="150" value='<?=$defeito_constatado_descricao2?>' >
		</td>
	</tr>

	<tr>
	<td nowrap align="left">
		<!-- HD 244476: Este hidden não estava sendo usado para nada, coloquei um ID nele para que seja alimentado com o ID do produto selecionado ou o número do KIT se for o caso -->
		<input type='hidden' name='produto_troca' id='produto_troca' value='<?php echo $produto_troca;?>'>
		<input type='hidden' name='kit_produto' id='kit_produto' value='<?php echo $kit_produto;?>'>

		<font size='1' face='Geneva, Arial, Helvetica, san-serif'>Trocar por</font><br>
		<input class='frm' type='text' name='produto_referencia_troca2' size='15' maxlength='50' value='<?=$produto_referencia_troca2?>' onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Entre com a referência do produto e clique na lupa para efetuar a pesquisa.');">&nbsp;
		<!-- HD 244476: A chamada da função de pesquisa foi alterada para levar o novo parâmetro -->
		<img src='imagens/lupa.png' border='0' align='absmiddle' onclick="javascript: pesquisaProdutoTroca (document.frm_os.produto_referencia_troca2,document.frm_os.produto_descricao_troca2,document.frm_os.produto_voltagem_troca2,document.frm_os.produto_referencia2, document.frm_os.produto_voltagem2,'referencia', document.frm_os.produto_troca)" style='cursor: hand'>
		</td>
		<td nowrap valign='left' align="left">
		<font size='1' face='Geneva, Arial, Helvetica, san-serif'>Descrição do Produto</font>
		<br>
		<input class='frm' type='text' name='produto_descricao_troca2' size='30' value='<?=$produto_descricao_troca2?>' onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Digite aqui o modelo do produto e clique na lupa para efetuar a pesquisa.');">&nbsp;
		<!-- HD 244476: A chamada da função de pesquisa foi alterada para levar o novo parâmetro -->
		<img src='imagens/lupa.png' border='0' align='absmiddle' onclick="javascript: pesquisaProdutoTroca (document.frm_os.produto_referencia_troca2,document.frm_os.produto_descricao_troca2,document.frm_os.produto_voltagem_troca2,document.frm_os.produto_referencia2, document.frm_os.produto_voltagem2, 'descricao', document.frm_os.produto_troca)"  style='cursor: pointer'></A>
		</td>
		<td nowrap  align='left'>Voltagem<br>
		<input class='frm' type='text' name='produto_voltagem_troca2' size='10' value='<?=$produto_voltagem_troca2?>' readonly></td>

	</tr>

	<tr>
		<td colspan="3" align="left">
			Quantidade do Produto<br><INPUT TYPE='text' maxlength="3"  NAME='produto_qtde' id='produto_qtde' size='3' CLASS="frm" value='<?=$produto_qtde?>'>&nbsp;&nbsp;<input type="button" style='background:url(imagens/btn_adicionar_azul.gif); width:90px; cursor:pointer;' value="&nbsp;" onClick="javascript: fnc_verifica_descontinuado();" border='0'>
		</td>
	</tr>

	<tr>
		<td colspan="3">
			<?php
			if($login_fabrica == 1 and $anexaNotaFiscal and $os and temNF($os)) {
				echo temNF($os, 'link');
				echo $include_imgZoom;
			}
			?>
		</td>


	</table>
		</td>
		<td><img height="1" width="5" src="imagens/spacer.gif"></td>
	</tr>
</table>
	<div style="clear:both;">&nbsp;</div>
	<br>
	<table width="85%" align="center">
		<tr class="subtitulo" ><td colspan="13" align="center" style="font-size:14px;">Dados Produto/Troca</td></tr>
	</table>

	<table  width="85%" align='center' class="tabela" border='0' cellspacing='1' cellpadding='0' id='tbl_produto' >
		<tr class='titulo_coluna'>
			<td align='center'>Cod.<br> Fabricação</td>
			<td align='center'>N°<br> Série</td>
			<td align='center'>Produto</td>
			<td align='center'>Descrição Produto</td>
			<td align='center'> Voltagem </td>
			<td align='center'>Type</td>
			<td align='center'>Embalagem Original</td>
			<td align='center'>Sinal Uso</td>
			<td align='center'>Defeito Constatado</td>
			<td align='center'>Trocar por</td>
			<td align='center'>Produto</td>
			<td align='center'>Voltagem</td>
            <?=$login_fabrica == 1 ? "<td align='center'>Motivo Troca</td>" : ""?>
            <?=$login_fabrica == 1 ? "<td align='center'>Tipo Atendimento</td>" : ""?>
		</tr>
	</table>

<? }elseif(strlen($os_revenda) > 0 OR $btn_acao == 'gravar'){

	if(strlen($qtde_item) == 0) {
		$qtde_item = $_POST['total_linhas'];
	}

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
		$valor_troca                  = "";
		$referencia_produto_troca     = "";
		$produto_descricao_troca      = "";
		$produto_voltagem_troca       = "";
		$kit_produto      	 		  = "";
		//HD 244476
		$produto_troca				  = "";
		$kit          				  = "";
		$pedido_item 					= "";

		if($i%2==0)$bgcor = '#F7F5F0';
		else       $bgcor = '#F1F4FA';


			echo "<table width='98%' border='0' cellpadding='0' cellspacing='1' align='center' class='tabela'>";
			echo "<tr class='titulo_coluna'>";
			echo "<td align='center'>Cod. <br>Fabricação</td>\n";
			echo "<td align='center'>N°<br> Série</td>";
			echo "<td align='center'>Produto</td>";
			echo "<td align='center'>Descrição Produto</td>";
			echo "<td align='center'>Voltagem </td>";
			echo "<td align='center'>Type</td>\n";
			echo "<td align='center'>Embalagem Original</td>\n";
			echo "</tr>";

		if (strlen($os_revenda) > 0){
			if (@pg_num_rows($res_os) > 0) {
				$produto = trim(@pg_fetch_result($res_os,$i,produto));
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
                                 tbl_os_revenda_item.causa_troca        AS causa_troca_item ,
								 tbl_os_revenda_item.obs_causa          AS obs_causa_item   ,
								 /* HD 244476 */
								 tbl_os_revenda_item.produto_troca		,
								 tbl_os_revenda_item.kit          		,
								 tbl_produto.referencia                 ,
								 tbl_produto.descricao                  ,
								 tbl_produto.voltagem                   ,
								 tbl_os_revenda_item.defeito_constatado_descricao                             ,
                                 tbl_os_revenda_item.valor_troca,
								 tbl_os_revenda_item.tipo_atendimento,
								 produto_troca.referencia as referencia_troca,
								 produto_troca.descricao as descricao_troca,
								 produto_troca.voltagem as  voltagem_troca
						FROM	 tbl_os_revenda
						JOIN	 tbl_os_revenda_item ON tbl_os_revenda.os_revenda = tbl_os_revenda_item.os_revenda
						JOIN	 tbl_produto ON tbl_produto.produto = tbl_os_revenda_item.produto
						LEFT JOIN	 tbl_produto produto_troca ON tbl_os_revenda_item.produto_troca = produto_troca.produto
						WHERE	 tbl_os_revenda_item.os_revenda = $os_revenda";
	//echo $sql;
				$res = pg_query($con,$sql);

				if (@pg_num_rows($res) == 0) {
					$novo                        = 't';
					$os_revenda_item             = $_POST["item_".$i];
					$referencia_produto          = $_POST["produto_referencia_".$i];
					$serie                       = $_POST["produto_serie_".$i];
					$produto_descricao           = $_POST["produto_descricao_".$i];
					$capacidade                  = $_POST["produto_capacidade_".$i];
					$type                        = $_POST["type_".$i];
					$embalagem_original          = $_POST["embalagem_original_".$i];
                    $sinal_de_uso                = $_POST["sinal_de_uso_".$i];
                    $causa_troca_item            = $_POST["causa_troca_item_".$i];
                    $obs_causa_item              = $_POST["obs_causa_item_".$i];
                    $prateleira_box              = $_POST["prateleira_box_".$i];
					$tipo_atendimento_item       = $_POST["tipo_atendimento_item_".$i];
					$codigo_fabricacao           = $_POST["codigo_fabricacao_".$i];
					$produto_voltagem            = $_POST["produto_voltagem_".$i];
					$defeito_constatado_descricao= $_POST["defeito_constatado_descricao_".$i];
					$valor_troca                 = $_POST["valor_troca_".$i];
					$referencia_produto_troca    = $_POST["produto_referencia_troca_".$i];
					$produto_descricao_troca     = $_POST["produto_descricao_troca_".$i];
					$produto_voltagem_troca      = $_POST["produto_voltagem_troca_".$i];
					$kit_produto      			 = $_POST["kit_produto_".$i];

					if($login_fabrica == 1){
						$pedido_item = $_POST["pedido_item_".$i];
					}

					//HD 244476
                    $produto_troca               = $_POST["produto_troca_".$i];
					$produto_troca               = $_POST["produto_troca_".$i];
					if ($referencia_produto_troca == "KIT") {
						$kit                         = $_POST["produto_troca_".$i];
					}
				}else{
					$novo                           = 'f';
					$os_revenda_item                = pg_fetch_result($res,$i,os_revenda_item);
					$referencia_produto             = pg_fetch_result($res,$i,referencia);
					$produto_descricao              = pg_fetch_result($res,$i,descricao);
					$serie                          = pg_fetch_result($res,$i,serie);
					$capacidade                     = pg_fetch_result($res,$i,capacidade);
					$type                           = pg_fetch_result($res,$i,type);
					$embalagem_original             = pg_fetch_result($res,$i,embalagem_original);
                    $sinal_de_uso                   = pg_fetch_result($res,$i,sinal_de_uso);
                    $causa_troca_item               = pg_fetch_result($res,$i,causa_troca_item);
                    $obs_causa_item                 = pg_fetch_result($res,$i,obs_causa_item);
					$tipo_atendimento_item          = pg_fetch_result($res,$i,tipo_atendimento);
					$codigo_fabricacao              = pg_fetch_result($res,$i,codigo_fabricacao);
					$produto_voltagem               = pg_fetch_result($res,$i,voltagem);
					$defeito_constatado_descricao   = pg_fetch_result($res,$i,defeito_constatado_descricao);
					$valor_troca                    = pg_fetch_result($res,$i,valor_troca);
					$referencia_produto_troca       = @pg_fetch_result($res,$i,referencia_troca);
					$produto_descricao_troca        = @pg_fetch_result($res,$i,descricao_troca);
					$produto_voltagem_troca         = @pg_fetch_result($res,$i,voltagem_troca);
					//HD 244476
					$produto_troca                  = pg_result($res,$i,produto_troca);
					$kit                            = pg_result($res,$i,kit);

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
            $causa_troca_item            = $_POST["causa_troca_item_".$i];
            $obs_causa_item              = $_POST["obs_causa_item_".$i];
            $prateleira_box              = $_POST["prateleira_box_".$i];
			$tipo_atendimento_item       = $_POST["tipo_atendimento_item_".$i];
			$codigo_fabricacao           = $_POST["codigo_fabricacao_".$i];
			$produto_voltagem            = $_POST["produto_voltagem_".$i];
			$defeito_constatado_descricao= $_POST["defeito_constatado_descricao_".$i];
			$valor_troca                 = $_POST["valor_troca_".$i];
			$referencia_produto_troca    = $_POST["produto_referencia_troca_".$i];
			$produto_descricao_troca     = $_POST["produto_descricao_troca_".$i];
			$produto_voltagem_troca      = $_POST["produto_voltagem_troca_".$i];
			//HD 244476
			$produto_troca               = $_POST["produto_troca_".$i];

			if($login_fabrica == 1){
				$pedido_item = $_POST["pedido_item_".$i];
			}

			if ($referencia_produto_troca == "KIT") {
				$kit                         = $_POST["produto_troca_".$i];
			}
		}
        $sqlTroca = "SELECT descricao
                    FROM    tbl_causa_troca
                    WHERE   fabrica = $login_fabrica
                    AND     tipo in ('T','R')
                    AND     ativo IS TRUE
                    AND     causa_troca = $causa_troca_item";
        $resTroca = pg_query($con,$sqlTroca);
        $causa_troca_item_nome = pg_fetch_result($resTroca,0,descricao);

		echo "<input type='hidden' name='novo_$i' value='$novo'>\n";
		echo "<input type='hidden' name='item_$i' value='$os_revenda_item'>\n";
		echo "<tr ";
		if ($linha_erro == $i AND strlen ($msg_erro) > 0) echo "bgcolor='#ffcccc'";
		else echo " bgcolor='$bgcor' ";
		echo ">\n";
		echo "<td align='center'><input class='frm' type='text' name='codigo_fabricacao_$i' size='9' maxlength='20' value='$codigo_fabricacao'></td>\n";
		echo "<td align='center' nowrap><input class='frm' type='text' name='produto_serie_$i'  size='10'  maxlength='20'  value='$serie'>&nbsp;<img src='imagens/lupa.png' border='0' align='absmiddle' onclick=\"javascript: pesquisaSerie (document.frm_os.produto_referencia_$i,document.frm_os.produto_descricao_$i,document.frm_os.produto_serie_$i, $i)\" style='cursor:pointer;'></td>\n";
		echo "<td align='center' nowrap><input class='frm' type='text' name='produto_referencia_$i' size='15' maxlength='50' value='$referencia_produto'>&nbsp;<img src='imagens/lupa.png' border='0' align='absmiddle' onclick='javascript: pesquisaProduto (document.frm_os.produto_referencia_$i,\"referencia\",$i)' style='cursor:pointer;'></td>\n";
		echo "<td align='center' nowrap><input class='frm' type='text' name='produto_descricao_$i' size='30' maxlength='50' value='$produto_descricao'>&nbsp;<img src='imagens/lupa.png' border='0' align='absmiddle' onclick='javascript: pesquisaProduto (document.frm_os.produto_descricao_$i,\"descricao\",$i)' style='cursor:pointer;'></td>\n";
		echo "<td align='center'><input class='frm' type='text' name='produto_voltagem_$i' size='5' value='$produto_voltagem'>

		<input class='frm' type='hidden' name='valor_troca_$i' size='5' value='$valor_troca' >
		</td>\n";

		?>
		<td align='center' nowrap>
		&nbsp;
		<select name='type_<? echo $i ?>' class='frm'>
			<option selected></option>
			<option value='Tipo 1' <? if($type == 'Tipo 1') echo "selected"; ?>>Tipo 1</option>
			<option value='Tipo 2' <? if($type == 'Tipo 2') echo "selected"; ?>>Tipo 2</option>
			<option value='Tipo 3' <? if($type == 'Tipo 3') echo "selected"; ?>>Tipo 3</option>
			<option value='Tipo 4' <? if($type == 'Tipo 4') echo "selected"; ?>>Tipo 4</option>
			<option value='Tipo 5' <? if($type == 'Tipo 5') echo "selected"; ?>>Tipo 5</option>
			<option value='Tipo 6' <? if($type == 'Tipo 6') echo "selected"; ?>>Tipo 6</option>
			<option value='Tipo 7' <? if($type == 'Tipo 7') echo "selected"; ?>>Tipo 7</option>
			<option value='Tipo 8' <? if($type == 'Tipo 8') echo "selected"; ?>>Tipo 8</option>
			<option value='Tipo 9' <? if($type == 'Tipo 9') echo "selected"; ?>>Tipo 9</option>
			<option value='Tipo 10' <? if($type == 'Tipo 10') echo "selected"; ?>>Tipo 10</option>
		</select>
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
		echo "<tr class='titulo_coluna'";
		if ($linha_erro == $i AND strlen ($msg_erro) > 0) echo "bgcolor='#ffcccc'";
		echo " >";
		echo "<td align='center'>Sinal de Uso</td>\n";
		echo "<td align='center'>Defeito Constatado</td>\n";
		echo "<td align='center'>Trocar Por</td>\n";
		echo "<td align='center'>Produto</td>\n";
		echo "<td align='center'>Voltagem</td>\n";
		echo "<td align='center'>Motivo da Troca</td>\n";
		echo "<td align='center'>Tipo Atendimento</td>\n";
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
		echo "<td align='center' nowrap><input class='frm' type='hidden' name='produto_troca_$i' id='produto_troca_$i' value='$produto_troca'><input class='frm' type='text' name='produto_referencia_troca_$i' size='15' maxlength='50' value='$referencia_produto_troca'>&nbsp;<img src='imagens/lupa.png' border='0' align='absmiddle' onclick='javascript: pesquisaProdutoTroca (document.frm_os.produto_referencia_troca_$i,document.frm_os.produto_descricao_troca_$i,document.frm_os.produto_voltagem_troca_$i,document.frm_os.produto_referencia_$i,document.frm_os.produto_voltagem_$i,\"referencia\", document.frm_os.produto_troca_$i,$i)' style='cursor:pointer;'></td>\n";
		//HD 244476: A chamada da função de pesquisa foi alterada para levar o novo parâmetro
		echo "<td align='center' nowrap><input class='frm' type='text' name='produto_descricao_troca_$i' size='30' maxlength='50' value='$produto_descricao_troca'>&nbsp;<img src='imagens/lupa.png' border='0' align='absmiddle' onclick='javascript: pesquisaProdutoTroca (document.frm_os.produto_referencia_troca_$i,document.frm_os.produto_descricao_troca_$i,document.frm_os.produto_voltagem_troca_$i,document.frm_os.produto_referencia_$i,document.frm_os.produto_voltagem_$i,\"descricao\", document.frm_os.produto_troca_$i,$i)' style='cursor:pointer;'></td>\n";
        echo "<td align='center' nowrap><input class='frm' type='text' name='produto_voltagem_troca_$i' size='5' value='$produto_voltagem_troca'></td>\n";
        if($login_fabrica == 1){

        	if(strlen(trim($pedido_item)) > 0  ){
        		$obs_causa_item = "Pedido: ".$pedido_item;
        	}

            echo "<td align='center' nowrap>
                <input class='frm' type='hidden' name='causa_troca_item_$i' value='$causa_troca_item'>
                <input class='frm' type='hidden' name='kit_produto_$i' value='$kit_produto_'>
                <input class='frm' type='text' name='causa_troca_item_nome_$i' value='$causa_troca_item_nome'>
                <input class='frm' type='text' name='obs_causa_item_$i' value='$obs_causa_item'>
                <input class='frm' type='text' name='prateleira_box_$i' value='$prateleira_box'>
                </td>\n";
		}
// 		echo "<td align='center'>&nbsp;</td>\n";
		echo "<td align='center'>";
?>
        <select name="tipo_atendimento_item_<?=$i?>" id="tipo_atendimento_item" onchange="tipoatendimento(this.value)" size="1" style='width:200px; height=18px;' class="frm">
            <option></option>
<?
                // hd 15197
                $sql = "SELECT *
                        FROM tbl_tipo_atendimento
                        WHERE fabrica = $login_fabrica
                        AND   tipo_atendimento IN (17,18,35)
                        ORDER BY tipo_atendimento";

                $res = pg_query ($con,$sql) ;
                for ($j = 0 ; $j < pg_num_rows ($res) ; $j++ ) {

                    echo "<option ";
                    if ($tipo_atendimento_item == pg_fetch_result ($res,$j,tipo_atendimento) ) echo " selected ";
                    echo " value='" . pg_fetch_result ($res,$j,tipo_atendimento) . "'>" ;
                    echo pg_fetch_result ($res,$j,descricao) ;
                    echo "</option>";
                }
?>
            </select>
<?
		echo "</td>\n";
		echo "</tr>";
		echo "<tr ><td colspan='100%' style='border:0px;'><br><br></tr>";
		echo "</table>";
		// limpa as variaveis
		$novo               = '';
		$os_revenda_item    = '';
		$referencia_produto = '';
		$serie              = '';
		$produto_descricao  = '';
		$capacidade         = '';

	}
}
echo "<table width='98%' align='center'>";
echo "<tr >";
echo "<td colspan='7' align='center' style='border:0px;'>";
echo "<br>";

if ($login_fabrica == '1') {
    $funcao_submit = ' verificaSubmit() ';
} else {
    $funcao_submit = ' document.frm_os.submit() ';
}

echo "<input type='button' rel='sem_submit' class='' style='background:url(imagens_admin/btn_gravar.gif); width:75px; cursor:pointer;' value='&nbsp;' id='img_gravar' onclick=\"javascript: if (document.frm_os.btn_acao.value == '' ) { document.frm_os.btn_acao.value='gravar' ; $('#multi_peca option').attr('selected','selected'); {$funcao_submit} } else { alert ('Aguarde submissão') }\" ALT='Gravar' border='0' > ";

if (strlen($os_revenda) > 0 AND strlen($exclui) > 0) {
	echo "&nbsp;&nbsp;<img src='imagens/btn_apagar.gif' name='name_sem_submit' class='verifica_servidor' style='cursor:pointer' onclick=\"javascript: if (document.frm_os.btn_acao.value == '' ) { if(confirm('Deseja realmente apagar esta OS?') == true) { document.frm_os.btn_acao.value='apagar'; document.frm_os.submit(); }else{ return; }; } else { alert ('Aguarde submissão') }\" ALT='Apagar a Ordem de Serviço' border='0'>";
}

echo "</td>";
echo "</tr>";
echo "</table>";
?>
</form>

<br>

<script type="text/javascript">
	$(document).ready(function(){
        <?php if ($login_fabrica == 1) { ?>
  		    var sp = $("select[name=prateleira_box] option:selected").val();
  		    if (sp == 'nao_cadastrada') {
  			   $("select[name=prateleira_box] option:selected").trigger('change');
  		    }
            
            if ($("#total_linhas").val() >= 1) {
                $("#data_nf").prop("readonly", true);
                $("#data_nf").datepick("destroy");
            } 
        <?php } ?>
 	});
</script>
<? include "rodape.php";?>
